<?php
require_once __DIR__.'/CommunityAgentBookingFlow.class.php';

/** Internal bridge only. Every operation requires a server-only random session proof. */
class CommunityAgentBookingModel extends Model
{
    public function gateway($sessionId, $proof, $operation, $form = [])
    {
        if (!is_string($sessionId) || !preg_match('/^[a-f0-9]{32}$/D', $sessionId) || !is_string($proof)) { throw new Exception('预约鉴权失败'); }
        $session = M('Community_agent_session')->where(['id'=>$sessionId, 'intent'=>'service_booking'])->find();
        $payload = $session ? json_decode($session['payload'], true) : [];
        if (empty($payload['booking_token']) || !hash_equals($payload['booking_token'], $proof)) { throw new Exception('预约鉴权失败'); }
        $bind = M('House_village_user_bind')->where(['pigcms_id'=>$session['bind_id'], 'uid'=>$session['uid'], 'village_id'=>$session['village_id'], 'status'=>1, 'type'=>['in',[0,1,2,3]]])->find();
        if (!$bind) { throw new Exception('住户身份已失效'); }
        try {
            if ($operation === 'result') { return $this->result($session); }
            if ($operation === 'metadata') {
                if (!in_array($session['state'], ['editing','ready'], true) || $session['expires_at'] <= time()) { throw new InvalidArgumentException('预约会话已过期'); }
                return $this->metadata($session, $form);
            }
            if ($operation !== 'submit' || $session['state'] !== 'submitting' || ($payload['form']['booking_kind'] ?? '') !== 'merchant') { throw new InvalidArgumentException('请先确认预约信息'); }
            return $this->submitOnce($session, $payload['form']);
        } catch (InvalidArgumentException $e) { return ['validation_error'=>$e->getMessage()]; }
    }

    private function metadata($session, $form)
    {
        $village = M('House_village')->where(['village_id'=>$session['village_id']])->find();
        $city = (int)$village['city_id'];
        if (!$city) { throw new InvalidArgumentException('当前小区尚未配置城市，暂无法查询商家服务'); }
        $prefix = C('DB_PREFIX');
        $where = ['a.check_status'=>1,'a.appoint_status'=>0,'m.status'=>1,'a.start_time'=>['elt',time()],'a.end_time'=>['gt',time()],
            '_string'=>'(m.city_id='.$city.' OR ast.city_id='.$city.')'];
        $items = M('Appoint')->alias('a')->join($prefix.'merchant m ON m.mer_id=a.mer_id')->join('LEFT JOIN '.$prefix.'appoint_store ast ON ast.appoint_id=a.appoint_id')->where($where)->field('a.*')->group('a.appoint_id')->order('a.appoint_id DESC')->select() ?: [];
        $meta = ['booking_items'=>[], 'booking_fields'=>[], 'missing_fields'=>[], 'blocking_reason'=>'', 'quote'=>'', 'price_label'=>'', 'onsite'=>false];
        $item = null;
        foreach ($items as $row) {
            $pics=explode(';',$row['pic'] ?? '');
            $poster=$pics[0]!=='' ? (new appoint_image())->get_image_by_path($pics[0],'s') : '';
            $prices=M('Appoint_product')->where(['appoint_id'=>$row['appoint_id']])->getField('price',true);
            $price=$prices ? min(array_map('floatval',$prices)) : (float)($row['appoint_price'] ?? 0);
            $meta['booking_items'][] = ['id'=>(int)$row['appoint_id'],'label'=>$row['appoint_name'],'poster'=>$poster,
                'price_label'=>$price>0 ? '¥'.number_format($price,2,'.','').($prices ? '起' : '') : '面议',
                'service_label'=>!empty($row['appoint_type']) ? '上门服务' : '到店服务'];
            if ((int)$row['appoint_id'] === (int)($form['item_id'] ?? 0)) { $item = $row; }
        }
        if (empty($form['item_id'])) { $meta['missing_fields'][] = 'item_id'; return $meta; }
        if (!$item) { throw new InvalidArgumentException('该服务已下架、已结束或不在当前城市'); }
        $category = D('Appoint_category')->get_category_by_id($item['cat_id']);
        if (empty($category['cue_field'])) { $category = D('Appoint_category')->get_category_by_id($category['cat_fid']); }
        $platform = !empty($category['is_autotrophic']);
        $meta['onsite'] = !$platform && (int)$item['appoint_type'] === 1;
        $fields = @unserialize($category['cue_field'] ?: '', ['allowed_classes'=>false]);
        $fields = is_array($fields) ? array_values($fields) : [];
        usort($fields, function($a,$b) { return ($b['sort'] ?? 0) <=> ($a['sort'] ?? 0); });
        foreach ($fields as $i=>$field) {
            $type = (int)$field['type'];
            if ($type < 0 || $type > 9) { $meta['blocking_reason']='该服务使用了暂不支持的表单类型，请联系商家'; }
            $options = [];
            foreach (($field['use_field'] ?? []) as $value) { $options[] = ['id'=>(string)$value,'label'=>(string)$value]; }
            $kind = [0=>'text',1=>'textarea',2=>'address',3=>'select',4=>'number',5=>'email',6=>'date',7=>'time',8=>'phone',9=>'datetime'][$type] ?? 'text';
            $meta['booking_fields'][] = ['key'=>'custom_'.$i,'label'=>$field['name'],'kind'=>$kind,'required'=>!empty($field['iswrite']),'options'=>$options];
            $value = trim((string)($form['custom_values'][$i] ?? ''));
            if (!empty($field['iswrite']) && $value === '') { $meta['missing_fields'][] = 'custom_'.$i; }
            if ($value !== '' && (($type === 3 && !in_array($value, array_column($options,'id'), true)) || ($type === 4 && !is_numeric($value)) || ($type === 5 && !filter_var($value,FILTER_VALIDATE_EMAIL)) || ($type === 8 && !preg_match('/^[0-9+() -]{5,20}$/D',$value)) || ($type === 6 && !preg_match('/^\d{4}-\d{2}-\d{2}$/D',$value)) || ($type === 7 && !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/D',$value)) || ($type === 9 && strtotime($value) === false))) { $meta['blocking_reason'] = '请检查'.$field['name'].'的格式'; }
        }
        $meta['_custom_schema'] = $fields;
        $meta['_platform'] = $platform;
        if (!$platform) {
            $products = M('Appoint_product')->where(['appoint_id'=>$item['appoint_id']])->select() ?: [];
            $product = null; $options = [];
            foreach ($products as $row) { $options[] = ['id'=>(int)$row['id'],'label'=>$row['name']]; if ((int)$row['id'] === (int)$form['product_id']) { $product=$row; } }
            if ($products) { $this->selectField($meta,'product_id','服务规格',$options,$form); }
            elseif (!empty($form['product_id'])) { throw new InvalidArgumentException('服务规格无效'); }
            $stores = D('Appoint_store')->get_storelist_by_appointId($item['appoint_id']) ?: [];
            $storeOptions = [];
            foreach ($stores as $row) { $storeOptions[]=['id'=>(int)$row['store_id'],'label'=>$row['name']]; }
            if (!empty($item['is_store'])) { $this->selectField($meta,'store_id','服务门店',$storeOptions,$form); }
            elseif (!empty($form['store_id'])) { throw new InvalidArgumentException('该服务不支持指定门店'); }
            $workers = [];
            if (!empty($form['store_id'])) {
                $workers = (new CommunityAgentBookingRunner($session))->call('ajaxWorker',['appoint_id'=>$item['appoint_id'],'merchant_store_id'=>$form['store_id']])['worker_list'] ?? [];
            }
            $workerOptions = [];
            foreach ($workers as $row) { $workerOptions[]=['id'=>(int)$row['merchant_worker_id'],'label'=>$row['name']]; }
            if ($workerOptions) { $this->selectField($meta,'worker_id','服务人员',$workerOptions,$form); }
            elseif (!empty($form['worker_id'])) { throw new InvalidArgumentException('服务人员已停用或不属于当前门店'); }
            $timeData = (new CommunityAgentBookingRunner($session))->call('ajaxWorkerTime',['appoint_id'=>$item['appoint_id'],'worker_id'=>(int)$form['worker_id']]);
            $days=[]; $slots=[];
            foreach (($timeData['timeOrder'] ?? []) as $day=>$rows) {
                $available=[];
                foreach ($rows as $slot) { if ($slot['order']==='yes') { $available[]=['id'=>$slot['start'],'label'=>$slot['time']]; } }
                if ($available) { $days[]=['id'=>$day,'label'=>$day]; }
                if ($day === $form['day']) { $slots=$available; }
            }
            $this->selectField($meta,'day','预约日期',$days,$form);
            $this->selectField($meta,'slot','预约时段',$slots,$form);
            if (!$days) { $meta['blocking_reason']='暂无可预约时段，请选择其他服务或人员'; }
            $price = $product ? $product['price'] : $item['appoint_price'];
            $deposit = $product ? $product['payment_price'] : $item['payment_money'];
            $due = (int)$item['payment_status'] === 1 ? max(0,(float)$deposit) : max(0,(float)$price);
            $meta['price_label'] = '服务价格：'.((float)$price > 0 ? '¥'.number_format($price,2,'.','') : '面议').'；'.((int)$item['payment_status']===1 ? '本次定金' : '本次应付').'：¥'.number_format($due,2,'.','');
            $meta['quote'] = hash('sha256', json_encode([$item['appoint_id'],(int)$form['product_id'],$price,$deposit,$item['payment_status'],$fields]));
        } else { $meta['price_label']='平台预约申请，实际服务与费用由平台确认'; $meta['quote']=hash('sha256',json_encode(['platform',$item['appoint_id'],$fields])); }
        foreach (['contact_name','contact_phone'] as $key) { if (empty($form[$key])) { $meta['missing_fields'][]=$key; } }
        if (!empty($form['contact_phone']) && !preg_match('/^[0-9+() -]{5,20}$/D',$form['contact_phone'])) { $meta['blocking_reason']='请填写有效联系电话'; }
        if ($meta['onsite'] && empty($form['address_text'])) { $meta['missing_fields'][]='address_text'; }
        return $meta;
    }

    private function selectField(&$meta,$key,$label,$options,$form)
    {
        $meta['booking_fields'][]=['key'=>$key,'label'=>$label,'kind'=>'select','required'=>true,'options'=>$options];
        if (empty($form[$key])) { $meta['missing_fields'][]=$key; }
        elseif (!in_array((string)$form[$key],array_map('strval',array_column($options,'id')),true)) { $meta['blocking_reason']=$label.'已失效，请重新选择'; }
    }

    private function submitOnce($session,$form)
    {
        $db = M('Community_agent_booking_receipt');
        // Both the receipt and the legacy order must commit on the CMS connection.
        foreach (['community_agent_booking_receipt','appoint','appoint_order','appoint_supply','appoint_visit_order_info','merchant_workers','system_order','appoint_order_records'] as $name) {
            $table=C('DB_PREFIX').$name;
            $engine=$db->query("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='".$table."'");
            if (empty($engine) || strtoupper($engine[0]['ENGINE'])!=='INNODB') { throw new InvalidArgumentException('预约数据库尚未初始化，请联系管理员'); }
        }
        $db->startTrans();
        try {
            // The session ID is validated hex. INSERT IGNORE makes concurrent retries wait.
            $db->execute("INSERT IGNORE INTO `".C('DB_PREFIX')."community_agent_booking_receipt` (`session_id`,`uid`,`order_id`) VALUES ('".$session['id']."',".(int)$session['uid'].",0)");
            $receipt=$db->where(['session_id'=>$session['id']])->lock(true)->find();
            if (!$receipt || (int)$receipt['uid'] !== (int)$session['uid']) { throw new Exception('预约回执异常'); }
            if ($receipt['order_id']) { $db->commit(); return $this->result($session); }
            M('Appoint')->where(['appoint_id'=>(int)$form['item_id']])->lock(true)->find();
            $meta=$this->metadata($session,$form);
            if ($meta['missing_fields'] || $meta['blocking_reason']) { throw new InvalidArgumentException($meta['blocking_reason'] ?: '请补齐预约信息'); }
            if (!hash_equals($meta['quote'], $form['quote'])) { throw new InvalidArgumentException('服务价格发生变化，请重新核对后提交'); }
            $user=D('User')->get_user($session['uid']);
            if (empty($user['phone']) && !$meta['_platform']) { throw new InvalidArgumentException('请先为登录账号绑定手机号'); }
            $custom=[];
            foreach ($meta['_custom_schema'] as $i=>$field) {
                $field['value']=$form['custom_values'][$i] ?? '';
                if ((int)$field['type']===2) {
                    $location=$form['custom_locations'][$i] ?? ['longitude'=>$form['longitude'],'latitude'=>$form['latitude']];
                    $field['address']=$field['value']; $field['long']=$location['longitude']; $field['lat']=$location['latitude']; $field['phone']=$form['contact_phone']; $field['showName']=$form['contact_name'];
                }
                $custom[]=$field;
            }
            if ($meta['onsite']) { array_unshift($custom,['name'=>'服务位置','type'=>'2','value'=>$form['address_text'],'address'=>$form['address_text'],'long'=>$form['longitude'],'lat'=>$form['latitude'],'phone'=>$form['contact_phone'],'showName'=>$form['contact_name']]); }
            $custom[]=['name'=>'联系人','type'=>'0','value'=>$form['contact_name']];
            $custom[]=['name'=>'联系电话','type'=>'8','value'=>$form['contact_phone']];
            $result=(new CommunityAgentBookingRunner($session))->call('order_save',[
                'appoint_id'=>$form['item_id'],'product_id'=>$form['product_id'],'store_id'=>$form['store_id'],'worker_id'=>$form['worker_id'],
                'service_date'=>$form['day'],'service_time'=>$form['slot'],'custom_field'=>$custom,'content'=>$form['remark'],'adress_id'=>0,
            ]);
            if (empty($result['order_id'])) { throw new Exception('预约未返回订单编号'); }
            if ($db->where(['session_id'=>$session['id']])->save(['order_id'=>$result['order_id']])===false) { throw new Exception('预约回执保存失败'); }
            $db->commit();
        } catch (Throwable $e) { $db->rollback(); throw $e; }
        return $this->result($session);
    }

    private function result($session)
    {
        $receipt=M('Community_agent_booking_receipt')->where(['session_id'=>$session['id'],'uid'=>$session['uid']])->find();
        if (empty($receipt['order_id'])) { return ['pending'=>true]; }
        $order=M('Appoint_order')->where(['order_id'=>$receipt['order_id'],'uid'=>$session['uid']])->find();
        if (!$order) { throw new Exception('预约订单不存在'); }
        $pay=(int)$order['paid']===0 && (int)$order['is_del']===0 && (int)$order['type']!==1;
        $message=$pay ? '预约订单已创建，等待支付' : '预约已提交，等待服务安排';
        if ((int)$order['paid']===2 || (int)$order['paid']===3 || (int)$order['is_del']!==0) { $message='预约已取消或退款'; }
        elseif ((int)$order['service_status']>0) { $message='预约服务已完成'; }
        return ['application_id'=>(int)$order['order_id'],'order_id'=>(int)$order['order_id'],'application_status'=>(int)$order['paid'],'message'=>$message,'payment_required'=>$pay,'booking_kind'=>'merchant'];
    }
}

class CommunityAgentBookingResponse extends Exception { public $data; public function __construct($data) { parent::__construct('booking response'); $this->data=$data; } }
/** Runs the shared legacy methods with a trusted resident identity, without an Appapi base controller. */
class CommunityAgentBookingRunner
{
    use CommunityAgentBookingFlow;
    public $config, $_uid, $user_session, $DEVICE_ID='wxapp', $is_weixin_browser=false, $is_wxapp_browser=true, $is_app_browser=false;
    private $session;
    public function __construct($session) { $this->session=$session; $this->_uid=$session['uid']; $this->config=C('config'); $this->user_session=D('User')->get_user($session['uid']); }
    public function call($method,$input)
    {
        if (!in_array($method,['ajaxWorker','ajaxWorkerTime','order_save'],true)) { throw new Exception('预约方法无效'); }
        $post=$_POST; $get=$_GET; $request=$_REQUEST; $saved=$_SESSION;
        $_POST=$input; $_GET=[]; $_REQUEST=$input;
        $_SESSION['user']=$this->user_session; $_SESSION['openid']=$this->user_session['openid'] ?? '';
        $_SESSION['now_village_bind']=['village_id'=>$this->session['village_id']];
        try { $this->$method(); throw new Exception('预约接口未返回结果'); }
        catch (CommunityAgentBookingResponse $e) { return $e->data; }
        finally { $_POST=$post; $_GET=$get; $_REQUEST=$request; $_SESSION=$saved; }
    }
    public function returnCode($code,$data=[]) { if ((string)$code!=='0') { throw new InvalidArgumentException('预约信息校验失败，请重新检查'); } throw new CommunityAgentBookingResponse($data); }
    public function returnCodeError($message) { throw new InvalidArgumentException($message); }
}
