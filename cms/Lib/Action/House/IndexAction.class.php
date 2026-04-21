<?php
/*
 * 社区首页
 *
 */
class IndexAction extends BaseAction{
	protected $village_id;
	protected $village;
	protected $huizhisq = false;
    protected $guess_you_like_goods = 0;
	public $worker_name=array(
		'0'=>'客服专员',
		'1'=>'维修技工',
//		'2'=>'物业人员',
		'3'=>'保洁人员',
		'4'=>'保安人员',
		'5'=>'招商开发',
		'6'=>'财务',
		'7'=>'人力资源',
		'8'=>'管理决策',
		'9'=>'后勤',
		'99'=>'其他',
	);
    protected $jsdhwy_new_wap_index=0;
    protected $yuanJino2oCustomized=0;
	protected $house_admin_wid;

	public function _initialize(){
		parent::_initialize();

        C('customization', D('Config_customization')->get_config());
        // 营山定制开关
        $this->huizhisq = !empty(C('customization.huizhisq')) ? true : false;
        $this->assign('yingshan',$this->huizhisq);
        $this->carPile = !empty(C('customization.device_car_pile')) ? true : false;
        $this->assign('carPile',$this->carPile);

		$this->village_id = $this->house_session['village_id'];
		$this->village = D('House_village')->field(true)->where(array('village_id'=>$this->village_id))->find();
		if(empty($this->village)){
			$this->error('该'.C('config.house_name').'不存在！');
		}
        if($this->village['status'] == 0 && MODULE_NAME == 'Index' && (ACTION_NAME != 'index' && ACTION_NAME != 'config' && ACTION_NAME != 'village_edit'&& ACTION_NAME != 'ajax_help')){
			$this->assign('jumpUrl',U('Index/config'));
			$this->error('您需要先完善信息才能继续操作');
		}
		$this->assign('worker_name',$this->worker_name);
        $this->house_admin_wid=( ($this->house_session['sLoginRole'] == 5) && isset($this->house_session['house_admin_wid'])) ? $this->house_session['house_admin_wid'] : 0;

        $jsdhwy_new_wap_index=D('Config_customization')->getWapIndexJsdhwyUi();
        $this->jsdhwy_new_wap_index=$jsdhwy_new_wap_index;
        $this->assign('jsdhwy_new_wap_index',$jsdhwy_new_wap_index);
        $yuanJino2oCustomized=D('Config_customization')->getYuanJino2oCustomized();
        $this->yuanJino2oCustomized=$yuanJino2oCustomized;
        $this->assign('yuanJino2oCustomized',$yuanJino2oCustomized);
        $getIsAihorseCustomized = D('Config_customization')->getIsAihorseCustomized();
        $this->assign('isAihorseCustomized',$getIsAihorseCustomized);
        $this->guess_you_like_goods=D('Config_customization')->getGuessYouLikeGoodsCustomized();
        $this->assign('guess_you_like_goods',$this->guess_you_like_goods);

	}

    public function printer(){
        //打印机管理-查看 权限
        if (!in_array(12, $this->house_session['menus']) && !in_array(462, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $where = array('village_id' => $this->house_session['village_id']);
        if ($this->house_session['village_id']) {
            $where['store_id'] = $this->house_session['village_id'];
        }
        $list = D('House_village_printer')->field(true)->where($where)->select();
        $tmp = array();

        $names = array('现场缴费', '支付成功后打印', '验证成功后打印', '退款成功后打印', '打印总单');
        foreach ($list as &$o) {
            if ($o['is_main']) {
                $o['name'] .= '(主打印机)';
            } else {
                $o['name'] = $o['name'] ? $o['name'] : '打印机-' . $o['pigcms_id'];
            }
            $o['str'] = '';
            if ($o['paid'] != '') {
                $o['paid'] = explode(',', $o['paid']);
                foreach ($o['paid'] as $id) {
                    $o['str'] .= '【' . $names[$id] . '】';
                }
            }
        }
        $this->assign('list', $list);
        $this->display();
    }

    public function printer_add(){
        //打印机管理-添加 编辑 查看 权限
        if (!in_array(13, $this->house_session['menus']) && !in_array(14, $this->house_session['menus']) && !in_array(12, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        
        $pigcms_id = isset($_REQUEST['pigcms_id']) ? intval($_REQUEST['pigcms_id']) : 0;
        $pigcms_id && $village_printer = D('House_village_printer')->field(true)->where(array('village_id' => $this->house_session['village_id'], 'pigcms_id' => $pigcms_id))->find();
        if (IS_POST) {
            if ($this->house_session['village_id']) {
                $_POST['village_id'] = $this->house_session['village_id'];
            }
            $data['village_id'] = isset($_POST['village_id']) ? intval($_POST['village_id']) : 0;
            $data['username'] = isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '';
            $data['name'] = isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '';
            $data['mp'] = isset($_POST['mp']) ? htmlspecialchars($_POST['mp']) : '';
            $data['mcode'] = isset($_POST['mcode']) ? htmlspecialchars($_POST['mcode']) : '';
            $data['mkey'] = isset($_POST['mkey']) ? htmlspecialchars($_POST['mkey']) : '';
            $data['qrcode'] = isset($_POST['qrcode']) ? $_POST['qrcode'] : '';
            $data['count'] = isset($_POST['count']) ? intval($_POST['count']) : 1;
            $data['is_main'] = isset($_POST['is_main']) ? intval($_POST['is_main']) : 0;
            $data['image'] = isset($_POST['image']) ? intval($_POST['image']) : 0;
            $data['paper'] = isset($_POST['paper']) ? intval($_POST['paper']) : 0;
            $data['is_big'] = isset($_POST['is_big']) ? intval($_POST['is_big']) : 0;
            $data['paid'] = isset($_POST['paid']) ? $_POST['paid'] : '';
            if ($data['count'] < 1 || $data['count'] > 100) {
                $this->error('份数在1~100之间的数字');
            }
            if ($data['paid']) $data['paid'] = implode(',', $data['paid']);
            if ($village_printer) {
                //打印机管理-编辑 权限
                if (!in_array(14, $this->house_session['menus'])) {
                    $this->error('对不起，您没有权限执行此操作');
                }

                D('House_village_printer')->where(array('village_id' => $this->house_session['village_id'], 'pigcms_id' => $pigcms_id))->save($data);
            } else {
                //打印机管理-添加 权限
                if (!in_array(13, $this->house_session['menus'])) {
                    $this->error('对不起，您没有权限执行此操作');
                }

                D('House_village_printer')->add($data);
            }
            $this->success('操作成功', U('Index/printer'));
        } else {
            $where = array('village_id' => $this->house_session['village_id'], 'status' => 1);
            if ($this->house_session['village_id']) {
                $where['village_id'] = $this->house_session['village_id'];
            }

            $village_printer['paid'] != '' && $village_printer['paid'] = explode(',', $village_printer['paid']);
            $this->assign('village_printer', $village_printer);
            $this->display();
        }
    }

    public function print_status() {
        $pigcms_id = intval($_POST['id']);
        $database = D('House_village_printer');
        if (!($village_printer = $database->field(true)->where(array('village_id' => $this->house_session['village_id'], 'pigcms_id' => $pigcms_id))->find())){
            exit('0');
        }

        $condition['pigcms_id'] = $pigcms_id;
        $data['is_main'] = $_POST['type'] == 'open' ? 1 : 0;
        if($database->where($condition)->data($data)->save()){
            exit('1');
        }else{
            exit;
        }
    }

    public function delprint() {
        //打印机管理-删除 权限
        if (!in_array(15, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        
        $pigcms_id = isset($_GET['pigcms_id']) ? intval($_GET['pigcms_id']) : 0;
        if ($village_printer = D('House_village_printer')->field(true)->where(array('village_id' => $this->house_session['village_id'], 'pigcms_id' => $pigcms_id))->find()) {
            D('House_village_printer')->where(array('village_id' => $this->house_session['village_id'], 'pigcms_id' => $pigcms_id))->delete();
            $this->success('打印机删除设置成功', U('Index/printer'));
        } else {
            $this->error('不合法的参数请求', U('Index/printer'));
        }
    }


    public function index(){
        $village_id = $this->village_id;
        $this->assign('village_id',$village_id);
        // 直接定向到新版页面
        if (!$_GET['iframe']) {
            $url = $this->config['site_url'] . '/v20/public/platform/#/village/village.iframe/house_index_index';
            redirect($url);
        }
        if($village_id){
            $village_info = D('House_village')->field(true)->where(array('village_id'=>$village_id))->find();
            $this->assign('village_info',$village_info);
            $weixin_bind = D('Weixin_bind')->where(array('mer_id' =>  $village_info['property_id'], 'type' => 2))->find();
            $property_weixin_qrcode_url = '';
            if ($weixin_bind && $weixin_bind['qrcode_url']) {
                $property_weixin_qrcode_url = $this->config['site_url'].'/index.php?c=Image&a=wx_image_download&url='.urlencode($weixin_bind['qrcode_url']).'&path='.$village_info['property_id'];
            }
            $this->assign('property_weixin_qrcode_url',$property_weixin_qrcode_url);


            /*查询下社区绑定的应用开始*/
            $application_arr = D('Application_list')->get_application_all_auth($village_id);
            $this->assign('application_bind',$application_arr);
            /*查询下社区绑定的应用结束*/

            // 房间数量
            $room_count =  D('House_village_user_vacancy')->where(array('is_del'=>'0','village_id'=>$this->village_id))->count();
            // 业主数量
            $where_user = [
                'type'=>array('in',[0,3]),
                'vacancy_id'=>['gt',0],
                'village_id'=>$this->village_id,
                'status'=>1
            ];
            $where_user['_string'] = "(uid>0 OR phone<>'' OR name<>'')";
            $user_count =  D('House_village_user_bind')
                ->where($where_user)
                ->count();
            // 车辆数量
            $car_count =  D('House_village_parking_car')->where(array('village_id'=>$this->village_id))->count();
            // 车位数量
            $position_count =  D('House_village_parking_position')->where(array('village_id'=>$this->village_id,'position_pattern'=>1))->count();
            
            //新老版停车判断
            $park_new_switch=D('House_village_info')->where(array('village_id'=>$this->village_id))->getField('park_new_switch');
            $this->assign('park_new_switch',$park_new_switch);
            $this->assign('room_count',$room_count);
            $this->assign('user_count',$user_count);
            $this->assign('car_count',$car_count);
            $this->assign('position_count',$position_count);

            // 待审核业主
//            $audit_user_count =  D('House_village_user_vacancy')->where(array('is_del'=>'0','status'=>'2','village_id'=>$this->village_id))->count();
            $audit_user_count =  D('House_village_user_bind')->where(array('type'=>['in', [0,3]],'status'=>'2','village_id'=>$this->village_id))->count();

            // 待审核家属
            $audit_child_count =  D('House_village_user_bind')->where(array('type'=>1,'status'=>'2','village_id'=>$this->village_id))->count();
            // 待审核租客
            $audit_tenant_count =  D('House_village_user_bind')->where(array('type'=>2,'status'=>'2','village_id'=>$this->village_id))->count();
            // 待处理报修
            $baoxiu_count =  D('House_village_repair_list')->where(array('type'=>1,'status'=>'0','village_id'=>$this->village_id))->count();
            // 待处理投诉建议
            $suggest_count =  D('House_village_repair_list')->where(array('type'=>3,'status'=>'0','village_id'=>$this->village_id))->count();
            // 待处理水电煤气上报
            $water_count =  D('House_village_repair_list')->where(array('type'=>2,'status'=>'0','village_id'=>$this->village_id))->count();
            // 待处理快递
            $express_count =  D('House_village_express')->where(array('status'=>'0','village_id'=>$this->village_id))->count();
            // 待申请解绑
            $unbind_count =  D('House_village_user_unbind')->where(array('status'=>'1','village_id'=>$this->village_id))->count();

            // 待审核文章
            $left_join = 'as a left join '.C('DB_PREFIX').'bbs_ban_list as b on `a`.`uid` = `b`.`uid` left join '.C('DB_PREFIX').'bbs_category as c on `a`.`cat_id` = `c`.`cat_id` left join '.C('DB_PREFIX').'bbs as bbs on `bbs`.`bbs_id` = `c`.`bbs_id`';
            $bbs_count = M('Bbs_aricle')->join($left_join)->where('a.aricle_status=2 AND `bbs`.`third_id`='.$this->village_id)->count();
            
            $jz_open = D('Config_customization')->getWSXCustomizedOpen();
            // 查询待审核车辆
            $showCheckCarNum = false;
            if (in_array(112025, $this->house_session['menus'])) {
                $whereCarCheck = ['village_id' => $this->village_id, 'examine_status' => 0];
                $checkCarNum = M('House_village_parking_car')->where($whereCarCheck)->count();
                $this->assign('checkCarNum',$checkCarNum);
                $showCheckCarNum = true;
                $style = 1;
                if (!in_array(127, $this->house_session['menus']) && !in_array(216, $this->house_session['menus'])) {
                    $style = 2;
                }
                $path = '/village/yardManagement/vehicleManagement';
                if ($checkCarNum > 0) {
                    $path .= '?checkType=check';
                } else {
                    $path .= '?checkType=all';
                }
                $showCheckCarUrlArr = [
                    'style' => $style,
                    'path' => $path,
                    'path_name' => 'new_v20',
                ];
                $this->assign('showCheckCarUrlArr',$showCheckCarUrlArr);
            }
            $this->assign('showCheckCarNum',$showCheckCarNum);
            
            //待审核装修申请
            $online_files_count=M('Plugin_material_diy_value')->where([
                'from'=>'house',
                'from_id'=>$this->village_id,
                'diy_tatus'=>0
            ])->count();
            $online_files=[
                'url'=>'/village/village.iframe/house_plugin_material_diy',
                'count'=>$online_files_count
            ];
            $this->assign('online_files',$online_files);

            $this->assign('audit_user_count',$audit_user_count);
            $this->assign('audit_child_count',$audit_child_count);
            $this->assign('audit_tenant_count',$audit_tenant_count);
            $this->assign('baoxiu_count',$baoxiu_count);
            $this->assign('suggest_count',$suggest_count);
            $this->assign('water_count',$water_count);
            $this->assign('express_count',$express_count);
            $this->assign('unbind_count',$unbind_count);
            $this->assign('bbs_count',$bbs_count);

            $water_count_auth   = 0;
            $express_count_auth = 0;
            $baoxiu_count_auth  = 0;
            $suggest_count_auth = 0;
            if (in_array(222,$this->house_session['menus']) && in_array(18,$application_arr)) {
                // 待处理水电煤气上报
                $water_count_auth = 1;
            }
            $this->assign('water_count_auth',$water_count_auth);

            if (in_array(207,$this->house_session['menus']) && in_array(14,$application_arr)) {
                // 待处理快递
                $express_count_auth = 1;
            }
            $this->assign('express_count_auth',$express_count_auth);

            if (in_array(219,$this->house_session['menus']) && in_array(16,$application_arr)) {
                // 待处理报修
                $baoxiu_count_auth = 1;
            }
            $this->assign('baoxiu_count_auth',$baoxiu_count_auth);

            if (in_array(224,$this->house_session['menus']) && in_array(19,$application_arr)) {
                // 待处理投诉建议
                $suggest_count_auth = 1;
            }
            $this->assign('suggest_count_auth',$suggest_count_auth);
            
			$my_need_order_discard=0;
            $my_need_order_refund=0;
            $my_need_property_check=0;  //当前人 需要审核的物业时间 数量
            $my_modify_money_check = 0;
            if ($this->house_session['role_id'] > 0) {
                $village_id = $this->village_id;
                $admin_info = M('House_admin')->where(['id' => $this->house_session['role_id']])->find();
                //订单审核
                if (!empty($admin_info) && $admin_info['wid'] > 0) {
                    $left_join_str = 'LEFT JOIN ' . C('DB_PREFIX') . 'house_village_checkauth_apply as cca on ccd.apply_id=cca.id';
                    $whereDiscard = array('cca.xtype' => 'order_discard', 'ccd.property_id' => $village_info['property_id'], 'ccd.village_id' => $village_id, 'ccd.wid' => $admin_info['wid'], 'ccd.status' => 0);
                    $my_need_order_discard = M('House_village_checkauth_detail as ccd')->join($left_join_str)->where($whereDiscard)->count();
                    //echo M('House_village_checkauth_detail as ccd')->getLastSql();
                    $whereRefund = array('cca.xtype' => 'order_refund', 'ccd.property_id' => $village_info['property_id'], 'ccd.village_id' => $village_id, 'ccd.wid' => $admin_info['wid'], 'ccd.status' => 0);
                    $my_need_order_refund = M('House_village_checkauth_detail as ccd')->join($left_join_str)->where($whereRefund)->count();
                    $whereService = array('cca.xtype' => 'service_time_check', 'ccd.property_id' => $village_info['property_id'], 'ccd.village_id' => $village_id, 'ccd.wid' => $admin_info['wid'], 'ccd.status' => 0);
                    $my_need_property_check = M('House_village_checkauth_detail as ccd')->join($left_join_str)->where($whereService)->count();
                    $whereService = array('cca.xtype' => 'modify_money_check', 'ccd.property_id' => $village_info['property_id'], 'ccd.village_id' => $village_id, 'ccd.wid' => $admin_info['wid'], 'ccd.status' => 0);
                    $my_modify_money_check = M('House_village_checkauth_detail as ccd')->join($left_join_str)->where($whereService)->count();
                }
            }
            $this->assign('my_need_order_discard',$my_need_order_discard);
            $this->assign('my_need_order_refund',$my_need_order_refund);
            $this->assign('my_need_property_check',$my_need_property_check);
            $this->assign('my_modify_money_check',$my_modify_money_check);

			//平台快报
			$news = M('System_news');
            $where=array();
			$where['n.status'] = 1;
            $where['c.status'] = 1;
			$plat_news_list = $news->field('n.id,n.title,n.add_time,c.name')->join('as n left join '.C('DB_PREFIX').'system_news_category c ON c.id = n.category_id ')->where($where)->order('n.sort DESC,n.id DESC')->limit(10)->select();
			$this->assign('plat_news_list',$plat_news_list);

            $visitorWhere=array('village_id'=>$this->village_id);
            //$visitorWhere['status']=array('neq',3);
            $visitorWhere['_string']='add_time>='.strtotime(date('Y-m-d').' 00:00:00').' AND add_time<='.strtotime(date('Y-m-d').' 23:59:59');
            $visitor_count=M('House_village_visitor')->where($visitorWhere)->count();
            $visitor_count=$visitor_count>0 ? $visitor_count:0;
            $this->assign('visitor_count',$visitor_count);
        }
        $this->display();
    }
    //  审核提醒！
    public function reminder(){
        //在线报修-查看 权限
        if (in_array(219, $this->house_session['menus'])) {     
            $sum = M('House_village')->where(array('village_id'=>$this->village_id))->getField('repair_reminder');
            if($sum > 0){
                M('House_village')->where(array('village_id'=>$this->village_id))->setField('repair_reminder',0);
                $url = $this->config['site_url'].'/v20/public/platform/#/village/village.iframe/house_repair_index';
                $arr[] = array('name'=>'repair','msg'=>'您有 ('.$sum.') 在线报修提醒！','url'=>$url);
            }
        }

        //投诉建议-查看 权限
        if (in_array(224, $this->house_session['menus'])) {     
            $sum = M('House_village')->where(array('village_id'=>$this->village_id))->getField('suggest_reminder');
            if($sum > 0){
                M('House_village')->where(array('village_id'=>$this->village_id))->setField('suggest_reminder',0);
                $url = $this->config['site_url'].'/v20/public/platform/#/village/village.iframe/house_repair_village_suggest';
                $arr[] = array('name'=>'suggest','msg'=>'您有 ('.$sum.') 投诉建议提醒！','url'=>$url);
            }
        }

        //论坛文章审核 权限
        if (in_array(132, $this->house_session['menus'])) {
            $sum = M('House_village')->where(array('village_id'=>$this->village_id))->getField('bbs_aricle_reminder');
            if($sum > 0){
                M('House_village')->where(array('village_id'=>$this->village_id))->setField('bbs_aricle_reminder',0);
                $url = $this->config['site_url'].'/v20/public/platform/#/village/village.iframe/house_bbs_index';
                $arr[] = array('name'=>'bbs_aricle','msg'=>'您有 ('.$sum.') 论坛文章审核提醒！','url'=>$url);
            }
        }

        //收费 权限
        if (in_array(32, $this->house_session['menus'])) {
            $sum = M('House_village')->where(array('village_id'=>$this->village_id))->getField('payment_reminder');
            if($sum > 0){
                M('House_village')->where(array('village_id'=>$this->village_id))->setField('payment_reminder',0);
                $is_new_charge=D('House_village')->checkNewCharge($this->house_session['property_id']);
                if ($is_new_charge) {
                    $url = $this->config['site_url'].'/v20/public/platform/#/village/village.charge.cashier/payableOrderList';
                } else {
                    $url = $this->config['site_url'].'/v20/public/platform/#/village/village.iframe/house_unit_pay_order';
                }
                $arr[] = array('name'=>'payment','msg'=>'您有 ('.$sum.') 条缴费提醒！','url'=>$url);
            }
        }

        //废品回收 权限
        if (in_array(279, $this->house_session['menus'])) {
            $sum = M('House_village')->where(array('village_id'=>$this->village_id))->getField('waste_reminder');
            if($sum > 0){
                M('House_village')->where(array('village_id'=>$this->village_id))->setField('waste_reminder',0);
                $url = $this->config['site_url'].'/v20/public/platform/#/village/village.iframe/house_repair_waste_recovery';
                $arr[] = array('name'=>'waste','msg'=>'您有 ('.$sum.') 条废品回收提醒！','url'=>$url);
            }
        }
         
        exit(json_encode(array('error'=>1,'arr'=>$arr)));

    }

    //近七日交易走势
    public function get_week_order_list(){
        $return['order_money_list'] = array();
        $return['order_count_list'] = array();
        //todo 【新版收费】
        $is_new_charge=D('House_village')->checkNewCharge($this->property_id);
        if ($is_new_charge) {
            for($i=6;$i>=0;$i--){
                if($i == 0){
                    $today_zero = strtotime(date('Y-m-d').' 00:00:00');
                }else{
                    $today_zero = strtotime('-'.$i.' day',strtotime(date('Y-m-d').' 00:00:00'));
                }
                $condition=[];
                $condition['village_id'] = $this->village_id;
                $condition['is_paid'] = 1;
                $condition['is_discard'] = 1;
                $condition['_string'] = '(pay_time >= '.$today_zero.' AND pay_time <= '.($today_zero+86400-1).") AND ( refund_money < pay_money)";
                $field1='sum(pay_money) as pay_money,sum(refund_money) as refund_money';
                $order=M('house_new_pay_order')->where($condition)->field($field1)->find();
                $num=M('house_new_pay_order')->where($condition)->count();
                $return['order_money_list'][] = floatval(($order ? $order['pay_money'] - $order['refund_money'] : 0));
                $return['order_count_list'][] =  intval($num);
                $return['xAxis_txt'][] =  date('Y-m-d',$today_zero);
            }

        }else{
            for($i=6;$i>=0;$i--){
                if($i == 0){
                    $today_zero = strtotime(date('Y-m-d'));
                }else{
                    $today_zero = strtotime('-'.$i.' day',strtotime(date('Y-m-d')));
                }
                $condition=[];
                $condition['village_id'] = $this->village_id;
                $condition['_string'] = 'use_time>'.$today_zero.' AND use_time <='.($today_zero+86400)." AND type <> 'withdraw'";
                $return['order_money_list'][] = floatval(M('Village_money_list')->where($condition)->sum('money'));
                $return['order_count_list'][] =  intval(M('Village_money_list')->where($condition)->count());
                $return['xAxis_txt'][] =  date('Y-m-d',$today_zero);
            }
        }
        $this->success($return);
    }
    public function config(){
        //基本信息-查看 权限
        if (!in_array(2, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
		$village_id = $this->village_id;
		if($village_id){
            $isPmwlcDomain = false ;
		    if (intval(C('config.cockpit'))){
                $isPmwlcDomain = true ;
            }
            $isHzDomain = false ;
            if (intval(C('config.cockpit_hz'))){
                $isHzDomain = true ;
            }
            $this->assign('isPmwlcDomain',$isPmwlcDomain);
            $this->assign('isHzDomain',$isHzDomain);
            $d_house_village = D('House_village');
			$village_info = $d_house_village->where(array('village_id'=>$village_id))->find();
            $property = M('House_property')->where(array('id'=>$village_info['property_id']))->find();
			$village_info['long'] = floatval($village_info['long']);
			$village_info['lat'] = floatval($village_info['lat']);
            $village_info['door_pwd'] = explode(',', $village_info['door_pwd']);

            // 处理 个性化名称
            if (empty($village_info['diy_village_name'])) {
                $village_info['diy_village_name'] = $d_house_village->defaulf_diy_txt;
            }
            $this->assign('village_param_txt',$d_house_village->village_param_txt);
            $database_house_village_config = D('House_village_config');
            $config_info = $database_house_village_config->field(true)->where(array('village_id'=>$village_id))->find();
            if($config_info){
                $village_info['village_single_support_digit'] = $config_info['village_single_support_digit'];
            }
            
            //todo 办公时间配置读取
            $village_info['start_time']= $village_info['end_time']='';
            if(!empty($village_info['work_time'])){
                $work_time=explode('-',$village_info['work_time']);
                $village_info['start_time']=$work_time[0];
                $village_info['end_time']=$work_time[1];

            }
            
            //如果未配置。默认继承系统后台配置的别名
            if(C('customization.set_house_alias') == 1){
                $village_info['house_name'] = C('config.house_name');
                $village_info['single_name'] = $village_info['single_name'] ?: C('config.single_name');
                $village_info['floor_name'] = $village_info['floor_name'] ?: C('config.floor_name');
                $village_info['room_name'] = $village_info['room_name'] ?: C('config.room_name');
            }
            
           $this->assign('set_house_alias',C('customization.set_house_alias'));
            //海康网关
            $db_house_device_gateway = D('House_device_gateway');
            $checkGateway = $db_house_device_gateway->where(['village_id'=>$village_id])->find();
            if (!empty($checkGateway)){
                $village_info['hik_gateway_protocol'] = $checkGateway['protocol'];
                $village_info['hik_gateway_host'] = $checkGateway['host'];
                $village_info['hik_gateway_port'] = $checkGateway['port'];
                $village_info['hik_gateway_username'] = $checkGateway['username'];
                $village_info['hik_gateway_password'] = $checkGateway['password'];
                $village_info['hik_gateway_open'] = $checkGateway['is_open'];
                $village_info['hik_gateway_network_host'] = $checkGateway['network_host'];
                $village_info['hik_gateway_rtsp_port'] = $checkGateway['rtsp_port'];
                $village_info['hik_gateway_http_port'] = $checkGateway['http_port'];
            }
           $this->assign('village_info',$village_info);
			$this->assign('property',$property);
            $limitOpen = [];
            $openNumberMaxLimit = D('House_face_device')->getOpenNumberMaxLimit($village_id);
            $visitor_open_number_limit = D('House_face_device')->getQrCodeNumber($village_id);
            $limitOpen['vistor_open_number_limit'] = $visitor_open_number_limit;
            $select = [];
            for ($i = 1; $i <= $openNumberMaxLimit; $i++) {
                $select[] = ['title' => $i . '次', 'value' => $i];
            }
            $limitOpen['select'] = $select;
            $this->assign('limitOpen',$limitOpen);
            
            $tipQRCode = '';
            $getSwitchQrCode = D('Config_customization')->getSwitchQrCode();
            if (!$getSwitchQrCode) {
                $deviceQrCodeJudge = D('Config_customization')->deviceQrCodeJudge($village_id);
                $deviceQrCodeJudge && $getSwitchQrCode = true;
            }
            $this->assign('getSwitchQrCode',$getSwitchQrCode);
            if ($getSwitchQrCode) {
                $tipQRCode = '社区访客住户通过“通行人脸门禁二维码”开门次数设置（单个门禁），二维码过期则不能开门(目前仅支持海康门禁、260云门禁)。注意设置只影响后添加的访客。';
            }
            $this->assign('tipQRCode',$tipQRCode);

            // 人脸门禁
            $village_face_door = D('House_village_door_code')->field(true)->find();
            $this->assign('village_face_door',$village_face_door);

            // 查询下是否存在人脸识别门禁设备A2
            $device = M('House_face_device')->where(array('village_id' => $village_id,'device_type'=>2))->find();
            if ($device) {
                $is_device = true;
            } else {
                $is_device = false;
            }
            $this->assign('is_device',$is_device);
            $village_about_info = M('House_village_info')->where(array('village_id'=>$village_id))->find();
            if (!empty($village_about_info)){
                if ($village_about_info['contract_time_start']>1){
                    $village_about_info['contract_time_start']=date('Y-m-d',$village_about_info['contract_time_start']);
                }else{
                    $village_about_info['contract_time_start']='';
                }
                if ($village_about_info['contract_time_end']>1){
                    $village_about_info['contract_time_end']=date('Y-m-d',$village_about_info['contract_time_end']);
                }else{
                    $village_about_info['contract_time_end']='';
                }
            }
            if(isset($village_about_info['grab_order_time']) && $village_about_info['grab_order_time']<1){
                $village_about_info['grab_order_time']=1;
            }
            $meter_apiset=array();
            if(isset($village_about_info['meter_extended_data']) && $village_about_info['meter_extended_data']){
               $meter_extended_data = json_decode($village_about_info['meter_extended_data'],true);
               if($meter_extended_data && isset($meter_extended_data['meter_apiset'])){
                   $meter_apiset=$meter_extended_data['meter_apiset'];
               }
               unset($village_about_info['meter_extended_data']);
            }
            $this->assign('meter_apiset',$meter_apiset);
            $this->assign('village_about_info',$village_about_info);
            $whereArr=array('village_id'=>$village_id,'property_id'=>$village_info['property_id'],'xtype'=>'order_refund_check');
            $order_refund_check=D('House_village_checkauth_set')->get_one_set($whereArr);
            $refund_order_check_open=0;
            $order_check_level=array();
            $check_level0_wid=0;
            if(!empty($order_refund_check)){
                $refund_order_check_open=$order_refund_check['is_open'];
                if(!empty($order_refund_check['set_datas'])){
                    $order_check_level=json_decode($order_refund_check['set_datas'],1);
                    $check_level0_wid=$order_check_level['0']['level_wid'];
                    unset($order_check_level['0']);
                }
            }
            $fieldStr='wid,village_id,phone,name,department_id,people_type';
            $workerList = M('House_worker')->where(array('property_id'=>$village_info['property_id'],'department_id'=>array('gt',0),'is_del'=>0,'village_id'=>$village_id,'status'=>1))->field($fieldStr)->order('department_id ASC,wid desc')->select();
            if (!empty($workerList)){
                foreach ($workerList as $kk=>$vv){
                    $workerList[$kk]['department']=array();
                    $workerList[$kk]['department_str']='';
                    $department=$this->get_department($vv);
                    if(!empty($department)){
                        $department_str='';
                        foreach ($department as $dvv){
                            if($dvv['type']==0 && $dvv['village_id']==$village_id){
                                $dvv['name'] = $village_info['village_name'];
                            }
                            $department_str =$dvv['name'].' > '.$department_str;
                        }
                        $workerList[$kk]['department_str']=$department_str;
                        $workerList[$kk]['department']=$department;
                    }
                }
            }else{
                $workerList=array();
            }
            $whereArr=array('village_id'=>$village_id,'property_id'=>$village_info['property_id'],'xtype'=>array('in',array('order_refund','order_discard')),'status'=>array('in',array('0','1')));
            $applyhas=M('House_village_checkauth_apply')->where($whereArr)->order('id desc')->find();
            $msg='';
            if($applyhas){
                if($applyhas['xtype']=='order_refund'){
                    $msg='由'.$applyhas['apply_name'].'发起的退款审核，订单ID为'.$applyhas['order_id'];
                }elseif ($applyhas['xtype']=='order_discard'){
                    $msg='由'.$applyhas['apply_name'].'发起的作废审核，订单ID为'.$applyhas['order_id'];
                }
                $applyhas['msg']=$msg;
            }

            $whereArr=array('village_id'=>$village_id,'property_id'=>$village_info['property_id'],'xtype'=>'order_servicetime_check');
            $servicetime_check=D('House_village_checkauth_set')->get_one_set($whereArr);
            $servicetime_check_open=0;
            $servicetime_check_level=array();
            $servicetime_level0_wid=0;
            if(!empty($servicetime_check)){
                $servicetime_check_open=$servicetime_check['is_open'];
                if(!empty($servicetime_check['set_datas'])){
                    $servicetime_check_level=json_decode($servicetime_check['set_datas'],1);
                    $servicetime_level0_wid=$servicetime_check_level['0']['level_wid'];
                    unset($servicetime_check_level['0']);
                }
            }
            $whereArr=array('village_id'=>$village_id,'property_id'=>$village_info['property_id'], 'xtype' => 'order_servicetime_check','status'=>array('in',array('0','1')));
            $servicetimeapplyhas=M('House_village_checkauth_apply')->where($whereArr)->order('id desc')->find();
            if($servicetimeapplyhas){
                $msg='由'.$servicetimeapplyhas['apply_name'].'发起的房间计费时间修改审核设置';
                $extra_data=$servicetimeapplyhas['extra_data'] ? json_decode($servicetimeapplyhas['extra_data'],true):'';
                if($extra_data && isset($extra_data['project_name'])){
                    $msg.='，审核的项目是 '.$extra_data['project_name'];
                }
                $servicetimeapplyhas['msg']=$msg;
            }
            $this->assign('servicetime_check_open',$servicetime_check_open);
            $this->assign('servicetime_level0_wid',$servicetime_level0_wid);
            $this->assign('servicetime_check_level',$servicetime_check_level);
            $servicetime_check_level_num=count($servicetime_check_level);
            $this->assign('servicetime_check_level_num',$servicetime_check_level_num);
            $this->assign('servicetimeapplyhas',$servicetimeapplyhas);

            $whereArr=array('village_id'=>$village_id,'property_id'=>$village_info['property_id'],'xtype'=>'order_paytime_check');
            $paytime_check=D('House_village_checkauth_set')->get_one_set($whereArr);
            $paytime_check_open=0;
            $paytime_check_level=array();
            $paytime_level0_wid=0;
            if(!empty($paytime_check)){
                $paytime_check_open=$paytime_check['is_open'];
                if(!empty($paytime_check['set_datas'])){
                    $paytime_check_level=json_decode($paytime_check['set_datas'],1);
                    $paytime_level0_wid=$paytime_check_level['0']['level_wid'];
                    unset($paytime_check_level['0']);
                }
            }
            $whereArr=array('village_id'=>$village_id,'property_id'=>$village_info['property_id'], 'xtype' => 'order_paytime_check','status'=>array('in',array('0','1')));
            $paytimeapplyhas=M('House_village_checkauth_apply')->where($whereArr)->order('id desc')->find();
            if($paytimeapplyhas){
                $msg='由'.$paytimeapplyhas['apply_name'].'发起的房间计费时间修改审核设置';
                $extra_data=$paytimeapplyhas['extra_data'] ? json_decode($paytimeapplyhas['extra_data'],true):'';
                if($extra_data && isset($extra_data['project_name'])){
                    $msg.='，审核的项目是 '.$extra_data['project_name'];
                }
                $paytimeapplyhas['msg']=$msg;
            }
            $this->assign('paytime_check_open',$paytime_check_open);
            $this->assign('paytime_level0_wid',$paytime_level0_wid);
            $this->assign('paytime_check_level',$paytime_check_level);
            $paytime_check_level_num=count($paytime_check_level);
            $this->assign('paytime_check_level_num',$paytime_check_level_num);
            $this->assign('paytimeapplyhas',$paytimeapplyhas);
            
            $isCanHaveModifyMoney  = D('Config_customization')->orderNoPayCanModifyMoney();
            if ($isCanHaveModifyMoney) {
                // 订单修改费用审核设置
                $whereOrderModifyArr = array('village_id' => $village_id, 'property_id' => $village_info['property_id'], 'xtype' => 'order_modify_check');
                $orderModifyCheck = D('House_village_checkauth_set')->get_one_set($whereOrderModifyArr);
                $orderModifyCheckOpen = 0;
                $orderModifyCheckLevel = array();
                $orderModifyCheckLevelWid0 = 0;
                if (!empty($orderModifyCheck)) {
                    $orderModifyCheckOpen = $orderModifyCheck['is_open'];
                    if (!empty($orderModifyCheck['set_datas'])) {
                        $orderModifyCheckLevel = json_decode($orderModifyCheck['set_datas'], 1);
                        $orderModifyCheckLevelWid0 = $orderModifyCheckLevel['0']['level_wid'];
                        unset($orderModifyCheckLevel['0']);
                    }
                }
                $whereOrderModifyArr = array(
                    'village_id' => $village_id, 'property_id' => $village_info['property_id'],
                    'xtype' => 'modify_money_check',
                    'status' => array('in', array('0', '1'))
                );
                $applyModify = M('House_village_checkauth_apply')->where($whereOrderModifyArr)->order('id desc')->find();
                $msg = '';
                if ($applyModify) {
                    if ($applyModify['xtype'] == 'modify_money_check') {
                        $msg = '由' . $applyModify['apply_name'] . '发起的修改订单金额，订单ID为' . $applyModify['order_id'];
                    }
                    $applyModify['msg'] = $msg;
                }
                $this->assign('applyModify', $applyModify);
                $this->assign('orderModifyCheckLevelWid0', $orderModifyCheckLevelWid0);
                $this->assign('orderModifyCheckLevel', $orderModifyCheckLevel);
                $orderModifyCheckLevelNum = count($orderModifyCheckLevel);
                $this->assign('orderModifyCheckLevelNum', $orderModifyCheckLevelNum);
                $this->assign('orderModifyCheckOpen',$orderModifyCheckOpen);
                $this->assign('orderModifyTitle','待支付订单修改费用审核');
            }
            
            
            $haveVcon=M('House_menu_new')->where(array('fid'=>284,'name'=>'视频监控','show'=>1,'status'=>1))->field('id')->find();
            $this->assign('haveVcon',$haveVcon);
            $this->assign('applyhas',$applyhas);
            $this->assign('check_level0_wid',$check_level0_wid);
            $this->assign('order_check_level',$order_check_level);
            $order_check_level_num=count($order_check_level);
            $this->assign('order_check_level_num',$order_check_level_num);
            $this->assign('refund_order_check_open',$refund_order_check_open);
            $this->assign('worker_list',$workerList);
            $this->assign('worker_list_json',json_encode($workerList,JSON_UNESCAPED_UNICODE));
            //$this->assign('jz_open',$jz_wsxdsj_net_customized_open);

		}
        $redirect_uri = urlencode(C('config.site_url').'/shequ.php?g=House&c=Index&a=getCode') ;
        $this->assign('redirect_uri',$redirect_uri);

        if ($this->config['property_self_logo']==1) {
            // 开启了自定义
            $house_village_login = $this->config['site_url'].'/v20/public/platform/#/user/community/login?village='.$village_id;
            $this->assign('house_village_login',$house_village_login);
        }
        //寻求帮助返回数据
        $this->assign('help_info',D('House_village_info')->getHelpInfo($village_id));
        //校验新版收费是否开启
        $is_new_charge=D('House_village')->checkNewCharge($this->house_session['property_id']);
        $this->assign('is_new_charge',$is_new_charge);
        $show_sky_cockpit_entrance=C('config.show_sky_cockpit_entrance');
        $show_sky_cockpit_entrance=$show_sky_cockpit_entrance ? intval($show_sky_cockpit_entrance):0;
        $this->assign('show_sky_cockpit_entrance',$show_sky_cockpit_entrance);
        $third_import_data_switch_judge = D('Config_customization')->getThirdImportDataSwitch();
        $this->assign('third_import_data_switch_judge',$third_import_data_switch_judge);
        if ($third_import_data_switch_judge) {
            $this->assign('third_import_url',$this->config['site_url']."/shequ.php?g=House&c=ThirdImport&a=index");
        }
        $haveXiHanFaceDoor=0;
        $whereFace=array('village_id'=>$village_id,'device_type'=>31,'is_del'=>array('lt',4));
        $xihan_face_device=M('House_face_device')->where($whereFace)->field('device_id,device_name,device_type')->find();
        if($xihan_face_device && $xihan_face_device['device_id']){
            $haveXiHanFaceDoor=1;
        }
        $is_aihorse_customized = D('Config_customization')->getIsAihorseCustomized();
        $this->assign('is_aihorse_customized',$is_aihorse_customized);
        $this->assign('haveXiHanFaceDoor',$haveXiHanFaceDoor);
        $isFuncKaidalaicnSpecial=D('Config_customization')->getfuncKaidalaicnSpecial();
        $this->assign('isFuncKaidalaicnSpecial',$isFuncKaidalaicnSpecial);
        $orderModifyDiscount='';
        if(isset($village_about_info['grab_order_time']) && $village_about_info['grab_order_time']<1){
            $village_about_info['grab_order_time']=1;
        }
        if(($is_aihorse_customized ||$isFuncKaidalaicnSpecial)&& isset($village_about_info['order_modify_discount']) && !empty($village_about_info['order_modify_discount'])){
            $orderModifyDiscount=json_decode($village_about_info['order_modify_discount'],true);
        }
        $this->assign('orderModifyDiscount',$orderModifyDiscount);
        $isYumenLaoSpecialMeter=D('Config_customization')->getFuncYumenLaoSpecialMeter();
        $this->assign('isYumenLaoSpecialMeter',$isYumenLaoSpecialMeter);
		$this->display();
    }

    protected function get_department($worker = array())
    {
        if (empty($worker) || ($worker['department_id'] < 1)) {
            return array();
        }
        $departmentArr = array();
        $subinfo=array();
        $fieldStr = 'id,fid,name,type,status,village_id';
        $property_group = M('Property_group')->where(array('id' => $worker['department_id'], 'is_del' => 0))->field($fieldStr)->find();
        if (!empty($property_group)) {
            if ($property_group['fid'] > 0) {
                $worker['department_id'] = $property_group['fid'];
                $departmentTree=array();
                $subinfo =$this->get_department_tree($property_group,$departmentTree);
            }

        }
        if(!empty($subinfo)){
            $departmentArr=$subinfo;
        }else{
            $departmentArr[]=$property_group;
        }
        return $departmentArr;
    }

    protected function get_department_tree($_group=array(),&$departmentTree = array(),$is_deep=0)
    {
        $departmentTree[]=$_group;
        if (empty($_group) || $_group['fid']<1) {
            return $departmentTree;
        }
        $fieldStr = 'id,fid,name,type,status,village_id';
        $property_group_sub = M('Property_group')->where(array('id' => $_group['fid'], 'is_del' => 0))->field($fieldStr)->find();
        if (!empty($property_group_sub)) {
            if ($property_group_sub['fid'] > 0) {
                if($is_deep==1){
                    return $this->get_department_tree($property_group_sub,$departmentTree,1);
                }else{
                    $this->get_department_tree($property_group_sub,$departmentTree,1);
                }
            }else{
                $departmentTree[]=$property_group_sub;
                return $departmentTree;
            }
        }
        return $departmentTree;
    }

    // 人脸门禁同步社区
    public function getCode(){
        $village_id = $this->village_id;
        $code = $_GET['code'] ;

        // 获取token        
        fdump($code,'door');
        $params = array();
        $params['client_id'] = 'auth_yunnan_id';
        $params['client_secret'] = 'auth_yunnan_secret';
        $params['grant_type'] = 'authorization_code';
        $params['code'] = $code;
        $params['redirect_uri'] = C('config.site_url').'/shequ.php?g=House&c=Index&a=getCode';
        $url = 'https://openapi.vooct.com/openapi/oauth/token';

        $result = D('House_village_door_code')->curl_post($url,$params);
        fdump($result,'door',true);
        if ($result['error']) {
            $this->error('授权失败');
        }else{
            $data['code'] = $code;
            $data['access_token'] = $result['access_token'];
            $data['refresh_token'] = $result['refresh_token'];
            $data['expires_in'] = $result['expires_in'];
            $data['token_time'] = time();
            $door_code = M('House_village_door_code')->where(array('village_id'=>$village_id))->find();
            if ($door_code) {
                M('House_village_door_code')->where(array('village_id'=>$village_id))->save($data);
            }else{
                $data['create_time'] = time();
                $data['village_id'] = $village_id;
                M('House_village_door_code')->where(array('village_id'=>$village_id))->add($data);
            }
        }
        redirect(U('Index/config'));
        // echo json_encode($result);
    }


    // 人脸门禁同步社区
    public function sync_data(){
        $village_id = $this->village_id;
        $type = $_POST['type'] ? $_POST['type'] : 'community';
        switch ($type) {
            case 'community':
                $result = D('House_village_door_code')->sync_community($this->village_id);
                break;
            case 'floor':
                $result = D('House_village_door_code')->sync_floor($this->village_id);
                break;
            case 'house':
                $result = D('House_village_door_code')->sync_house($this->village_id);
                break;
            case 'user':
                $result = D('House_village_door_code')->sync_user($this->village_id);
                break;
            default:
                $result = array('code'=>0,'msg'=>'参数传递不对');
                break;
        }
        echo json_encode($result);
    }

    // ZDU36QI6
    // 人脸门禁添加设备
    public function door_add(){
        //人脸门禁添加设备-添加 权限
        if (IS_POST) {
            $name = trim($_POST['door_name']);
            if (!$name) {
                $this->error('名称不能为空');
            }
            $_POST['add_time'] = time();
            $_POST['village_id'] = $this->village_id;
            if ($_POST['location']) {
                $location = explode(',', $_POST['location']);
                $_POST['lng'] = $location[0];
                $_POST['lat'] = $location[1];
            }
            if ($_POST['type'] == 1) {
                $_POST['floor_id'] = 0;
            }
            $village_info = D('House_village')->field(true)->where(array('village_id'=>$this->village_id))->find();
            $insert_id = M('House_village_door_face')->add($_POST);
            if ($insert_id) {
                $this->success('添加成功');
            }else{
                $this->error('添加失败');                
            }
        }else{
            // 单元列表
            $aFloor = M('House_village_floor')->where(array('village_id'=>$this->village_id))->select();
            $this->assign('aFloor',$aFloor);
            $this->display();;
        }
    }

    // 编辑人脸门禁设备
    public function door_edit(){
        if (IS_POST) {
            $name = trim($_POST['door_name']);
            if (!$name) {
                $this->error('名称不能为空');
            }  
            $door_id = intval($_POST['door_id']);
            $now_door = D('House_village_door_face')->field(true)->where(array('door_id'=>$door_id,'village_id'=>$this->village_id))->find();
             if (!$now_door) {
                $this->error('该内容不存在或已删除');
            }
            if ($_POST['location']) {
                $location = explode(',', $_POST['location']);
                $_POST['lng'] = $location[0];
                $_POST['lat'] = $location[1];
            }

            if ($now_door['doorKeeperId']) {
                // 已对接 更新对接信息
                $token_info = D('House_village_door_code')->get_token($this->village_id);
                if ($token_info['error']==0) {
                    $now_village = D('House_village')->field(true)->where(array('village_id'=>$this->village_id))->find();
                    $url = 'https://openapi.vooct.com/openapi/v1/doorkeeper/modifyDKDevice';
                    $params = array();
                    $params['sn'] = $now_door['door_device_id'];
                    $params['communityId'] = $now_village['communityId'];
                    $params['communityName'] = $now_village['village_name'];
                    $params['buildId'] = intval($now_door['buildId']);
                    $params['type'] = $now_door['type'];
                    $params['deviceName'] = $_POST['door_name'];
                    $params['x'] = $_POST['lat'];
                    $params['y'] = $_POST['lng'];
                    $params['doorKeeperId'] = $now_door['doorKeeperId'];
                    $result = D('House_village_door_code')->curl_post($url,$params,$token_info['access_token']);
                    if($result['code'] == 1){ 
                        $update = M('House_village_door_face')->save($_POST);
                        if ($update) {
                            $this->success('编辑成功');
                        }else{
                            $this->error('编辑失败');                
                        }
                    }else{
                        $this->error('设备同步失败');
                    }
                }else{
                    $this->error('token获取失败');
                }                
            }else{
                // 未对接的,直接编辑
                $update = M('House_village_door_face')->save($_POST);
                if ($update) {
                    $this->success('编辑成功');
                }else{
                    $this->error('编辑失败');                
                }
            }
        }else{
            $door_id = intval($_GET['door_id']);
            if (!$door_id) {
                $this->error('参数传递错误');
            }
           
            $now_door = M('House_village_door_face')->where(array('door_id'=>$door_id,'village_id'=>$this->village_id))->find();
            if (!$now_door) {
                $this->error('该内容不存在或已删除');
            } 


            // 单元列表
            $now_floor = M('House_village_floor')->where(array('floor_id'=>$now_door['floor_id']))->find();
            $this->assign('now_floor',$now_floor);
            $this->assign('aDoor',$now_door);
            $this->display();;
        }
    }

    // 删除人脸门禁设备
    public function door_del(){
        $door_id = intval($_GET['door_id']);
        $now_door = D('House_village_door_face')->field(true)->where(array('door_id'=>$door_id,'village_id'=>$this->village_id))->find();
         if (!$now_door) {
            $this->error('该内容不存在或已删除');
        }
      
        if ($now_door['doorKeeperId']) {
            // 已对接 更新对接信息
            $token_info = D('House_village_door_code')->get_token($this->village_id);
            if ($token_info['error']==0) {
                $now_village = D('House_village')->field(true)->where(array('village_id'=>$this->village_id))->find();
                $url = 'https://openapi.vooct.com/openapi/v1/doorkeeper/delDevice';
                $params = array();
                $params['doorKeeperId'] = $now_door['doorKeeperId'];
                $result = D('House_village_door_code')->curl_post($url,$params,$token_info['access_token']);
                if($result['code'] == 1){ 
                    $del = M('House_village_door_face')->where(array('door_id'=>$door_id))->delete();
                    if ($del) {
                        $this->success('删除成功');
                    }else{
                        $this->error('删除失败');                
                    }
                }else{
                    $this->error('设备同步失败');
                }
            }else{
                $this->error('token获取失败');
            }                
        }else{
            $del = M('House_village_door_face')->where(array('door_id'=>$door_id))->delete();
            // var_dump(M('House_village_door_face')->_sql());die;
            if ($del) {
                $this->success('删除成功');
            }else{
                $this->error('删除失败');                
            }
        }
    }

    // 对接门禁设备
    public function door_docking(){
        $village_id = $this->village_id;
        $door_id = $_POST['door_id'] ? $_POST['door_id'] : '0';

        $info['error'] = 0;
        $info['msg'] = '';
        if (!$door_id) {
            $info['msg'] = '参数传递有误';
            echo json_encode($info);exit;
        }

        $now_door = M('House_village_door_face')->where(array('door_id'=>$door_id,'village_id'=>$this->village_id))->find();
        if (!$now_door) {
            $info['msg'] = '该内容不存在或已删除';
            echo json_encode($info);exit;
        }

        $now_village = D('House_village')->field(true)->where(array('village_id'=>$this->village_id))->find();
        // 小区对接id
        $communityId = $now_village['communityId'];
        if (!$communityId) {
            $info['msg'] = '小区未同步，请先同步小区';
            echo json_encode($info);exit;
        }

        if ($now_door['type'] == 2 ) {
            if (!$now_door['floor_id']) {
                $info['msg'] = '未指定单元';
                echo json_encode($info);exit;
            }
            $now_floor = D('House_village_floor')->field(true)->where(array('floor_id'=>$now_door['floor_id']))->find();
            // 楼栋对接id
            $buildId = $now_floor['buildId'];

            if (!$buildId) {
                $info['msg'] = '单元未同步，请先同步单元';
                echo json_encode($info);exit;
            }

        }

        $token_info = D('House_village_door_code')->get_token($village_id);
        if ($token_info['error']==0) {
            $url = 'https://openapi.vooct.com/openapi/v1/doorkeeper/addDKDevice';
            $params = array();
            $params['deviceName'] = $now_door['door_name'];
            $params['sn'] = $now_door['door_device_id'];
            $params['communityId'] = $communityId;
            $params['communityName'] = $now_village['village_name'];
            $params['buildId'] = intval($buildId);
            $params['type'] = $now_door['type'];
            $params['x'] = $now_door['lat'];
            $params['y'] = $now_door['lng'];
            $params['remark'] = '';
            //   broadbandId  宽带账号     broadbandPwd  宽带密码     networkMethod  联⽹⽅式     networkOperator ⽹络运营商
            $result = D('House_village_door_code')->curl_post($url,$params,$token_info['access_token']);
            if($result['code'] == 1){
                $info['data'] = $result['data'];
                $data['door_id'] = $door_id;
                $data['docking_status'] = 1;
                $data['communityId'] = $params['communityId'];
                $data['buildId'] = $params['buildId'];
                $data['doorKeeperId'] = $result['data'][0]['doorkeeperId'];
                // $data['doorkeeperNPwd'] = $result['data']['doorkeeperNPwd'];
                fdump($data,'door',true);
                fdump($result,'door',true);
                $update = M('House_village_door_face')->save($data);
                fdump(M('House_village_door_face')->_sql(),'door',true);

                $info['msg'] = '同步成功';
            }else{
                $info['error'] = 1;
                if ($result['msg']) {
                    $info['msg'] = $result['msg'];
                }else{
                    $info['msg'] = '同步失败，请重试';
                }
            }
        }else{
            $info['error'] = 1;
            $info['msg'] = '同步失败，请重试';
        }
        echo json_encode($info);
    }


    //  门禁设备列表
    public function door_list(){
        //门禁设置-查看 权限
        // if (!in_array(226, $this->house_session['menus'])) {
        //     $this->error('对不起，您没有权限执行此操作');
        // }

        $where['village_id'] = $this->village_id;
     
        $door_list  =   D('House_village_door_code')->get_door_list($where);
        $this->assign('door_list',$door_list);
        $this->display();
    }
    
    
    public function hikDeviceDatewayConfig($village_id,$property_id)
    {
        $hik_gateway_open = (int)$_POST['hik_gateway_open'];
        $hik_gateway_protocol = trim($_POST['hik_gateway_protocol']);
        $hik_gateway_host = trim($_POST['hik_gateway_host']);
        $hik_gateway_port = trim($_POST['hik_gateway_port']);
        $hik_gateway_username = trim($_POST['hik_gateway_username']);
        $hik_gateway_password = trim($_POST['hik_gateway_password']);
        $hik_gateway_network_host = trim($_POST['hik_gateway_network_host']);
        $hik_gateway_rtsp_port = trim($_POST['hik_gateway_rtsp_port']);
        $hik_gateway_http_port = trim($_POST['hik_gateway_http_port']);
        
        if (empty($hik_gateway_protocol)
            || empty($hik_gateway_host)
            || empty($hik_gateway_port)
            || empty($hik_gateway_username)
            || empty($hik_gateway_password)
            || empty($hik_gateway_network_host)
            || empty($hik_gateway_rtsp_port)
            || empty($hik_gateway_http_port)
        ){
            $this->error('所有的当前表单都是必填项，请检查！！');
        }
        if (!preg_match('/^((\d{1,3}\.){3}\d{1,3})|([a-zA-Z0-9-]+\.[a-zA-Z]{2,})$/',$hik_gateway_host)){
            $this->error('请检查提交的网关IP或域名是否正确！');
        }
        $hik_gateway_protocol = strtolower($hik_gateway_protocol);
        if (!in_array($hik_gateway_protocol,['http','https'])){
            $this->error('协议类型不对，只支持 http,https！');
        }


        $db_house_device_gateway = D('House_device_gateway');
        $checkGateway = $db_house_device_gateway->where(['village_id'=>$village_id])->find();
        if (empty($checkGateway)){
            //add
            $data = [
                'property_id'=> $property_id,
                'village_id'=>$village_id,
                'protocol'=>strtolower($hik_gateway_protocol),
                'host'=>$hik_gateway_host,
                'port'=>$hik_gateway_port,
                'username'=>$hik_gateway_username,
                'password'=>$hik_gateway_password,
                'network_host'=>$hik_gateway_network_host,
                'rtsp_port'=>$hik_gateway_rtsp_port,
                'http_port'=>$hik_gateway_http_port,
                'is_open'=>$hik_gateway_open,
            ];
            $db_house_device_gateway->add($data);
            $this->success('添加网关信息成功');
        }else{
            //update
            $data = [
                'protocol'=>$hik_gateway_protocol,
                'host'=>$hik_gateway_host,
                'port'=>$hik_gateway_port,
                'username'=>$hik_gateway_username,
                'password'=>$hik_gateway_password,
                'network_host'=>$hik_gateway_network_host,
                'rtsp_port'=>$hik_gateway_rtsp_port,
                'http_port'=>$hik_gateway_http_port,
                'is_open'=>$hik_gateway_open,
            ];
            $db_house_device_gateway->where(['id'=>$checkGateway['id']])->save($data);
            $this->success('网关信息更新成功');

        }
    }
    
    public function village_edit(){
    	$village_id = $this->village_id;
    	$condition_village['village_id'] = $village_id;
        $property_id = $this->house_session['property_id'];
    
        
        //基本信息-查看 权限
        if (!empty($this->house_session['menus']) && !in_array(2, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
    	if(IS_POST){
    		if(empty($_POST['long_lat'])){
    			$this->error(C('config.house_name').'经纬度必填！');
    		}
    		if(empty($_POST['village_address'])){
    			$this->error(C('config.house_name').'地址必填！');
    		}
            
            if (isset($_POST['hikDeviceGateway']) && $_POST['hikDeviceGateway'] ==1){
                return $this->hikDeviceDatewayConfig($village_id,$property_id);
            }

            $village_info = D('House_village')->where($condition_village)->find();
            $house_village_fields = D('House_village')->getDbFields();
            if (isset($_POST['seal_img_new']) && (!is_array($house_village_fields) || !in_array('seal_img_new', $house_village_fields))) {
                unset($_POST['seal_img_new']);
            }

			if($_POST['pwd']){
                $_POST['pwd'] = md5($_POST['pwd']);
                if ($village_info['pwd'] != md5($_POST['old_pwd'])) {
                    $this->error('原密码不正确！');
                }
                if (mb_strlen($_POST['old_pwd']) < 6) {
                    $this->error('密码最少6位！');
                }
			}else{
                unset($_POST['pwd']);
            }
            $newData=$_POST;
			$_POST['door_pwd'] = implode(',',$_POST['door_pwd']);
    		$long_lat = explode(',',$_POST['long_lat']);
    		$_POST['long'] = floatval($long_lat[0]);
    		$_POST['lat'] = floatval($long_lat[1]);
    		$_POST['status'] = 1;
			if(empty($_POST['long']) || empty($_POST['lat'])){
    			$this->error('请选取'.C('config.house_name').'经纬度！');
    		}


			//todo 办公时间配置写入
			if(!empty($_POST['work_time']) && is_array($_POST['work_time'])){
			    if($_POST['work_time'][0] >= $_POST['work_time'][1]){
                    $this->error('请重新选择办公时间');
                }
                $_POST['work_time']=implode('-',$_POST['work_time']);
            }

            if(!empty($_POST['contract_time_start'])&&empty($_POST['contract_time_end'])){
                $this->error('请选取合同结束时间');
            }
            if(empty($_POST['contract_time_start'])&&!empty($_POST['contract_time_end'])){
                $this->error('请选取合同开始时间');

            }
            if(!empty($_POST['contract_time_start'])&&!empty($_POST['contract_time_end'])&&$_POST['contract_time_start']>=$_POST['contract_time_end']){
                $this->error('合同开始时间不能大于合同结束时间');
            }
            if (false && !empty($_POST['contract_time_start'])&&!empty($_POST['contract_time_end'])){
                $room_data=[];
                $room_data['village_id']=$village_id;
                $room_data['contract_time_start']=strtotime($_POST['contract_time_start'].' 00:00:00');
                $room_data['contract_time_end']=strtotime($_POST['contract_time_end'].' 23:59:59');
                $res=D('House_village_user_vacancy')->getVacancyServiceTime($room_data);
                if ($res['status']!=1){
                    $this->error($res['msg']);
                }
            }

            if($_POST['province_idss']){
                $_POST['province_id'] = $_POST['province_idss'];
                unset($_POST['province_idss']);
            }
            if($_POST['city_idss']){
                $_POST['city_id'] = $_POST['city_idss'];
                unset($_POST['city_idss']);
            }
            if($_POST['street_idss']){
                $_POST['street_id'] = $_POST['street_idss'];
                unset($_POST['street_idss']);
            }
            if($_POST['community_idss']){
                $_POST['community_id'] = $_POST['community_idss'];
                unset($_POST['community_idss']);
            }

			$_SESSION['house']['long'] = $_POST['long'];
			$_SESSION['house']['lat'] = $_POST['lat'];
			$_SESSION['house']['has_index_nav'] = $_POST['has_index_nav'];
			$_SESSION['house']['has_service_slide'] = $_POST['has_service_slide'];

            // 去除相关信息
            $village_about_data = [];
            if (isset($_POST['contract_time_start'])&&!empty($_POST['contract_time_start'])) {
                $village_about_data['contract_time_start'] =strtotime($_POST['contract_time_start'].' 00:00:00');
                unset($_POST['contract_time_start']);
            }
            if (isset($_POST['contract_time_end'])&&!empty($_POST['contract_time_end'])) {
                $village_about_data['contract_time_end'] = strtotime($_POST['contract_time_end'].' 23:59:59');
                unset($_POST['contract_time_end']);
            }
            if (isset($_POST['face_door_set'])) {
                $village_about_data['face_door_set'] =$_POST['face_door_set'];
                unset($_POST['face_door_set']);
            }
            if (isset($_POST['work_order_set'])) {
                $village_about_data['work_order_set'] =$_POST['work_order_set'];
                unset($_POST['work_order_set']);
            }
            if (isset($_POST['wisdom_qrcode_set'])) {
                $village_about_data['wisdom_qrcode_set'] =$_POST['wisdom_qrcode_set'];
                unset($_POST['wisdom_qrcode_set']);
            }
           //  print_r($village_about_data);exit;
            if (isset($_POST['plot_area'])) {
                $village_about_data['plot_area'] = $_POST['plot_area'];
                unset($_POST['plot_area']);
            }
            if (isset($_POST['building_area'])) {
                $village_about_data['building_area'] = $_POST['building_area'];
                unset($_POST['building_area']);
            }
            if (isset($_POST['public_area'])) {
                $village_about_data['public_area'] = $_POST['public_area'];
                unset($_POST['public_area']);
            }
            if (isset($_POST['green_area'])) {
                $village_about_data['green_area'] = $_POST['green_area'];
                unset($_POST['green_area']);
            }
            if (isset($_POST['garage_area'])) {
                $village_about_data['garage_area'] = $_POST['garage_area'];
                unset($_POST['garage_area']);
            }
            if (isset($_POST['design_parking_area'])) {
                $village_about_data['design_parking_area'] = $_POST['design_parking_area'];
                unset($_POST['design_parking_area']);
            }
            if (isset($_POST['cars_park_num'])) {
                $village_about_data['cars_park_num'] = $_POST['cars_park_num'];
                unset($_POST['cars_park_num']);
            }

            if (isset($_POST['house']['zhuzhai']['yishou'])) {
                $village_about_data['zhuzhai_yishou'] = $_POST['house']['zhuzhai']['yishou'];
                unset($_POST['house']['zhuzhai']['yishou']);
            }
            if (isset($_POST['house']['zhuzhai']['weishou'])) {
                $village_about_data['zhuzhai_weishou'] = $_POST['house']['zhuzhai']['weishou'];
                unset($_POST['house']['zhuzhai']['weishou']);
            }
            if (isset($_POST['house']['zhuzhai']['yijiao'])) {
                $village_about_data['zhuzhai_yijiao'] = $_POST['house']['zhuzhai']['yijiao'];
                unset($_POST['house']['zhuzhai']['yijiao']);
            }
            if (isset($_POST['house']['zhuzhai']['weijiao'])) {
                $village_about_data['zhuzhai_weijiao'] = $_POST['house']['zhuzhai']['weijiao'];
                unset($_POST['house']['zhuzhai']['weijiao']);
            }

            if (isset($_POST['house']['shangpu']['yishou'])) {
                $village_about_data['shangpu_yishou'] = $_POST['house']['shangpu']['yishou'];
                unset($_POST['house']['shangpu']['yishou']);
            }
            if (isset($_POST['house']['shangpu']['weishou'])) {
                $village_about_data['shangpu_weishou'] = $_POST['house']['shangpu']['weishou'];
                unset($_POST['house']['shangpu']['weishou']);
            }
            if (isset($_POST['house']['shangpu']['yijiao'])) {
                $village_about_data['shangpu_yijiao'] = $_POST['house']['shangpu']['yijiao'];
                unset($_POST['house']['shangpu']['yijiao']);
            }
            if (isset($_POST['house']['shangpu']['weijiao'])) {
                $village_about_data['shangpu_weijiao'] = $_POST['house']['shangpu']['weijiao'];
                unset($_POST['house']['shangpu']['weijiao']);
            }

            if (isset($_POST['house']['chewei']['yishou'])) {
                $village_about_data['chewei_yishou'] = $_POST['house']['chewei']['yishou'];
                unset($_POST['house']['chewei']['yishou']);
            }
            if (isset($_POST['house']['chewei']['weishou'])) {
                $village_about_data['chewei_weishou'] = $_POST['house']['chewei']['weishou'];
                unset($_POST['house']['chewei']['weishou']);
            }
            if (isset($_POST['house']['chewei']['yijiao'])) {
                $village_about_data['chewei_yijiao'] = $_POST['house']['chewei']['yijiao'];
                unset($_POST['house']['chewei']['yijiao']);
            }
            if (isset($_POST['house']['chewei']['weijiao'])) {
                $village_about_data['chewei_weijiao'] = $_POST['house']['chewei']['weijiao'];
                unset($_POST['house']['chewei']['weijiao']);
            }


            if (isset($_POST['decoration_num'])) {
                $village_about_data['decoration_num'] = $_POST['decoration_num'];
                unset($_POST['decoration_num']);
            }
            if (isset($_POST['decorated_num'])) {
                $village_about_data['decorated_num'] = $_POST['decorated_num'];
                unset($_POST['decorated_num']);
            }
            if (isset($_POST['undecorate_num'])) {
                $village_about_data['undecorate_num'] = $_POST['undecorate_num'];
                unset($_POST['undecorate_num']);
            }
            if (isset($_POST['non_motor_num'])) {
                $village_about_data['non_motor_num'] = $_POST['non_motor_num'];
                unset($_POST['non_motor_num']);
            }
            if (isset($_POST['charger_num'])) {
                $village_about_data['charger_num'] = $_POST['charger_num'];
                unset($_POST['charger_num']);
            }
            if (isset($_POST['new_energy_num'])) {
                $village_about_data['new_energy_num'] = $_POST['new_energy_num'];
                unset($_POST['new_energy_num']);
            }
            if (isset($_POST['ground_charger_num'])) {
                $village_about_data['ground_charger_num'] = $_POST['ground_charger_num'];
                unset($_POST['ground_charger_num']);
            }
            if (isset($_POST['fuel_num'])) {
                $village_about_data['fuel_num'] = $_POST['fuel_num'];
                unset($_POST['fuel_num']);
            }
            if (isset($_POST['garage_charger_num'])) {
                $village_about_data['garage_charger_num'] = $_POST['garage_charger_num'];
                unset($_POST['garage_charger_num']);
            }


            if(isset($_POST['meter_type']) && $_POST['meter_type'] == 1){
                $_POST['meter_time'] = $_POST['meter_day'];
                unset($_POST['meter_day']);
            }
            if(isset($_POST['meter_type']) && $_POST['meter_type'] == 2){
                unset($_POST['meter_day']);
            }
            if (isset($_POST['remark'])) {
                $village_about_data['remark'] = trim($_POST['remark']);
                unset($_POST['remark']);
            }
            if (isset($_POST['village_floor'])) {
                $village_about_data['village_floor'] = trim($_POST['village_floor']);
                unset($_POST['village_floor']);
            }
            if (isset($_POST['village_photo'])) {
                $village_about_data['village_photo'] = trim($_POST['village_photo']);
                unset($_POST['village_photo']);
            }


            if (isset($_POST['village_photo1'])) {
                $village_about_data['village_photo1'] = trim($_POST['village_photo1']);
                unset($_POST['village_photo1']);
            }
            if (isset($_POST['village_photo2'])) {
                $village_about_data['village_photo2'] = trim($_POST['village_photo2']);
                unset($_POST['village_photo2']);
            }
            if (isset($_POST['village_photo3'])) {
                $village_about_data['village_photo3'] = trim($_POST['village_photo3']);
                unset($_POST['village_photo3']);
            }
            if (isset($_POST['village_cockpit_logo'])) {
                $village_about_data['village_cockpit_logo'] = trim($_POST['village_cockpit_logo']);
                unset($_POST['village_cockpit_logo']);
            }
            $village_floor = M('House_village_info')->where(array('village_id'=>$village_id))->getField('village_floor');

            if (isset($_POST['vistor_open_number_limit'])) {
                $configData = [];
                $configData['vistor_open_number_limit'] = trim($_POST['vistor_open_number_limit']);
                $database_house_village_config = D('House_village_config');
                $database_house_village_config->where(array('village_id'=>$village_id))->save($configData);
                unset($_POST['vistor_open_number_limit']);
            }

            if (isset($_POST['is_new_storage'])) {
                $village_about_data['is_new_storage'] = trim($_POST['is_new_storage']);
                unset($_POST['is_new_storage']);
            }
            if (isset($_POST['is_limit_date'])) {
                $village_about_data['is_limit_date'] = trim($_POST['is_limit_date']);
                unset($_POST['is_new_storage']);
            }
            $meter_apiset='';
            if(isset($_POST['meter_apiset'])){
                $meter_apiset=$_POST['meter_apiset'];
                unset($_POST['meter_apiset']);
            }
            $village_about_data['repair_wid'] = 0;
            $village_about_data['advise_wid'] = 0;
            if(intval($_POST['handle_type']) == 1){
                if(intval($_POST['hour']) > 0){
                    if(intval($_POST['repair_wid']) > 0 && intval($_POST['advise_wid']) > 0){
                        $village_about_data['repair_wid'] = intval($_POST['repair_wid']);
                        $village_about_data['advise_wid'] = intval($_POST['advise_wid']);
                        unset($_POST['repair_wid'],$_POST['advise_wid']);
                    }
                }
            }
            //收费预存配置项
            if (isset($_POST['is_new_storage'])) {
                $village_about_data['is_new_storage'] = trim($_POST['is_new_storage']);
                unset($_POST['is_new_storage']);
            }
            // 新版首页页面
            if (isset($_POST['visualization_page_type'])) {
                $village_about_data['visualization_page_type'] = trim($_POST['visualization_page_type']);
                unset($_POST['visualization_page_type']);
            }
            // 允许已缴账单打印多次
            if (isset($_POST['print_number_times'])) {
                $village_about_data['print_number_times'] = trim($_POST['print_number_times']);
                unset($_POST['print_number_times']);
            }
            // 允许已缴账单打印多次
            if (isset($_POST['print_orders_merge_project'])) {
                $village_about_data['print_orders_merge_project'] = intval($_POST['print_orders_merge_project']);
                unset($_POST['print_orders_merge_project']);
            }
            //小区业务短信发送配置
            if (isset($_POST['sms_type'])) {
                $village_about_data['sms_type'] = trim($_POST['sms_type']);
                unset($_POST['sms_type']);
            }

            // 催缴通知方式
            if (isset($_POST['urge_notice_type'])) {
                $village_about_data['urge_notice_type'] = intval($_POST['urge_notice_type']);
                unset($_POST['urge_notice_type']);
            }
            //寻求帮助
            if(isset($_POST['help_info']) && !empty($_POST['help_info'])){
                $help_key=isset($_POST['help_info']['type']) ? array_search('3',$_POST['help_info']['type']) : false;
                if($help_key !== false){
                    if(!isset($_POST['help_info']['custom'][$help_key]) || empty(trim($_POST['help_info']['custom'][$help_key]))){
                        $this->error('功能配置=>寻求帮助下自定义内容不可为空，请重新选择自定义');
                    }
                }
                $village_about_data['help_info']=serialize($_POST['help_info']);
                unset($_POST['help_info']);
            }
            //新版工单及时率
            if(isset($_POST['is_timely'])){
                $village_about_data['is_timely']=intval($_POST['is_timely']);
                unset($_POST['is_timely']);
            }
            //新版工单自动抢单
            if(isset($_POST['is_grab_order'])){
                $village_about_data['is_grab_order']=intval($_POST['is_grab_order']);
                $village_about_data['grab_order_time']=intval($_POST['grab_order_time']);
                if(!$village_about_data['is_grab_order']){
                    $village_about_data['grab_order_time']=0;
                }
                unset($_POST['is_grab_order'],$_POST['grab_order_time']);
            }
            //新/老版工单启用配置
            if(isset($_POST['works_order_switch'])){
                $village_about_data['works_order_switch']=intval($_POST['works_order_switch']);
                unset($_POST['works_order_switch']);
            }
            //新/老版智慧停车配置
            if(isset($_POST['park_new_switch'])){
                $village_about_data['park_new_switch']=intval($_POST['park_new_switch']);
                unset($_POST['park_new_switch']);
            }

            //定制水表
            if(isset($_POST['company_name']) && !empty($_POST['company_name'])){
                $_POST['company_name'] = $_POST['company_name'];
            }
            if(isset($_POST['meter_nopayorder_date'])){
                $_POST['meter_nopayorder_date'] = intval($_POST['meter_nopayorder_date']);
            }
            if(isset($_POST['village_house_door_close'])){
                $village_about_data['village_house_door_close'] = intval($_POST['village_house_door_close']);
                unset($_POST['village_house_door_close']);
            }
            if(isset($_POST['room_bind_people_num'])){
                $village_about_data['room_bind_people_num'] = intval($_POST['room_bind_people_num']);
                unset($_POST['room_bind_people_num']);
            }
            if(isset($_POST['house_room_replace_owner'])){
                $village_about_data['house_room_replace_owner'] = intval($_POST['house_room_replace_owner']);
                unset($_POST['house_room_replace_owner']);
            }
            if(isset($_POST['third_pay_url'])){
                $village_about_data['third_pay_url'] =$_POST['third_pay_url'] ?  trim($_POST['third_pay_url']):'';
                unset($_POST['third_pay_url']);
            }
            if(isset($_POST['third_ccb_pay_url'])){
                $village_about_data['third_ccb_pay_url'] = $_POST['third_ccb_pay_url'] ? trim($_POST['third_ccb_pay_url']):'';
                unset($_POST['third_ccb_pay_url']);
            }
            if(isset($village_about_data['third_pay_url']) && $village_about_data['third_pay_url'] && isset($village_about_data['third_ccb_pay_url']) && $village_about_data['third_ccb_pay_url']){
                $this->error('通联缴费链接 和 建行缴费链接 不允许同时填写！');
                exit();
            }
            if(isset($_POST['special_set_price'])){
                $village_about_data['special_set_price'] = $_POST['special_set_price'] ? $_POST['special_set_price']*1:0;
                unset($_POST['special_set_price']);
            }
            
            if(isset($_POST['card_expire_time_type'])){
                $village_about_data['card_expire_time_type'] = trim($_POST['card_expire_time_type']);
                unset($_POST['card_expire_time_type']);
            }
            $result = D('House_village')->where($condition_village)->data($_POST)->save();

    		if($result !== false || !empty($village_about_data)){
    		    if(!empty($village_about_data['village_floor'])&&$village_floor!=$village_about_data['village_floor']){
                    M('House_village_single')->where(array('village_id'=>$village_id))->data(['area'=>''])->save();
                 //   print_r(M('House_village_single')->_sql());exit;
                }
                $vinfo = M('House_village_info')->where(array('village_id'=>$village_id))->find();
                if (!empty($vinfo) && isset($vinfo['info_id'])) {
                    $village_about_data['add_time'] = time();
                    if($meter_apiset) {
                        $meter_extended_data = $vinfo['meter_extended_data'] ? json_decode($vinfo['meter_extended_data'], true):array();
                        $meter_extended_data=is_array($meter_extended_data) ? $meter_extended_data:array();
                        $meter_extended_data['meter_apiset']=$meter_apiset;
                        $village_about_data['meter_extended_data']=json_encode($meter_extended_data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                    }
                    M('House_village_info')->where(array('info_id'=>$vinfo['info_id']))->save($village_about_data);
                } else {
                    $village_about_data['village_id'] = $village_id;
                    $village_about_data['property_id'] = $village_info['property_id'];
                    $village_about_data['add_time'] = time();
                    if($meter_apiset){
                        $village_about_data['meter_extended_data']=json_encode(array('meter_apiset'=>$meter_apiset),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                    }
                    M('House_village_info')->data($village_about_data)->add();
                }
                D('House_new_repair_cate')->check_bind_worker($village_id,$village_about_data);
    		    if ($_POST['owe_property_open_door']!=$village_info['owe_property_open_door'] || $_POST['owe_property_open_door_day']!=$village_info['owe_property_open_door_day'] || $_POST['property_endtime']!=$village_info['property_endtime']) {
                    // 计划任务 修改了相关数据 同步所有业主钥匙信息
//                    D('House_village_face_door')->create_village_plan($village_id,'sync_user');
                    D('House_village_face_door')->sync_user($village_id);
                }
                $community_id = $village_info['community_id'];
                if ($community_id && $_POST['community_id'] && $community_id != $_POST['community_id']) {
                    $where_bind = array(
                        'type' => 2,
                        'bind_id' => $village_id,
                        'fid' => $community_id
                    );
                    // 修改了关联
                    $set_data = array(
                        'fid' => $_POST['community_id']
                    );
                    D('House_village_custom_polygon')->change_village_custom($where_bind,$set_data);
                }
                //todo a185触发下发配置命令
                D('Face_a185_request_record')->a185_sync_command(true,false,$this->config);
                
                $models = '\app\common\model\service\CommonSystemLogService/addLaterLogInQueue';
                $logData=array('tbname'=>'小区设置-基本信息','table'=>'house_village','client'=>'小区后台','trigger_path'=>'基本信息->保存','trigger_type'=>'update','village_id'=>$village_id,'property_id'=>0);
                $logData['login_uid']=$this->login_uid;
                $logData['login_role']=$this->login_role;
                $logData['newData']=$newData;
                $logData['oldData']='';
                $param=array('logData'=>$logData);
                $result = invoke_v20_service($models, $param);
    			$this->success('修改成功！');
    		}else{
    			$this->error('修改失败！请重试。');
    		}
    	}
    }

    // 快店管理
    public function active_store_list(){
        //快店管理-查看 权限
        if (!in_array(150, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $village_id = $this->village_id;
        $store_list = D('House_village_store')->get_limit_list($village_id);
        $this->assign('store_list',$store_list);
        $this->display();
    }

    // 快店管理 添加
    public function active_store(){
        //快店管理-添加 权限
        if (!in_array(151, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        if(IS_POST){
            $store_id = intval($_POST['store_id']);
            $sort = $_POST['sort']?intval($_POST['sort']):0;

            $result = $this->checkVillageStore($store_id);
            if($result['error'] == 0){

                $data['village_id'] = $this->village_id;
                $data['store_id'] = $store_id;
				
				if(D('House_village_store')->where($data)->find()){
					$this->error('该店铺已经添加');
				}
				
                $data['sort'] = $sort;
                $data['url'] = $this->config['site_url'].'/wap.php?c=Shop&a=classic_shop&shop_id='.$store_id;

                if(D('House_village_store')->add($data)){
                    $this->success('添加成功');
                }else{
                    $this->error('保存失败请重试');
                }
            }elseif($result['error'] == 1){
                $this->error($result['msg']);
            }else{
                $this->error('保存失败请重试');
            }
        }else{
            $this->display();
        }
    }


    public function check_store(){
        if(IS_AJAX){
            $village_id = $this->village_id;
            $store_id = $_POST['store_id'];
            $result = $this->checkVillageStore($store_id);
            if($result['error'] == 0){
                $this->ajaxReturn($result);
            }elseif($result['error'] == 1){
                $this->ajaxReturn(array('msg'=>$result['msg'],'error'=>1));
            }else{
                $this->ajaxReturn(array('msg'=>'暂无匹配的信息','error'=>1));;
            }
            exit;
        }
    }

    public function checkVillageStore($store_id){
        $village_id = $this->village_id;
        if(!$store_id){
            return array('msg'=>'检测通过之后才能保存哦','error'=>1);
        }
        $store_info = D('')->field('`s`.`name` AS store_name  ,`m`.`name` AS `merchant_name` ,`s`.`available_state`,`s`.`auth`')->table(array(C('DB_PREFIX').'merchant_store'=>'s',C('DB_PREFIX').'merchant'=>'m'))->where("`s`.`store_id`='".$store_id."' AND `s`.`mer_id`=`m`.`mer_id` AND `m`.`status`=1 AND `s`.`status`=1")->find();
        $condition = array('village_id'=>$village_id,'store_id'=>$store_id);
        if($_GET['id']){
            $condition['pigcms_id'] = array('neq',$_GET['id']);
        }
        if(!empty($store_info)){
            $store_info['error'] = 0;

            if (C('config.store_shop_auth') == 1 && $store_info['auth']<=2) {
                return array('msg'=>'检测到该快店店铺暂未通过平台审核，请重新添加','error'=>1);
            }
            return $store_info;
        }
        return array('msg'=>'暂无匹配的信息','error'=>1);
    }

    // 快店管理 编辑
    public function active_store_edit(){
        $now_acitive = D('House_village_store')->field(true)->where(array('pigcms_id'=>$_GET['id'],'village_id'=>$this->village['village_id']))->find();
        if(empty($now_acitive)){
            $this->error(L_('当前推荐的X1不存在',['X1'=>$this->config['shop_alias_name']]));
        }
        if(IS_POST){
            //快店管理-编辑 权限
            if (!in_array(152, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }

            $data['pigcms_id'] = $now_acitive['pigcms_id'];
            $store_id = intval($_POST['store_id']);
            $sort = $_POST['sort']?intval($_POST['sort']):0;

            $result = $this->checkVillageStore($store_id);

            if($result['error'] == 0){
                $data['village_id'] = $this->village_id;
                $data['store_id'] = $store_id;
                $data['sort'] = $sort;
                if(D('House_village_store')->data($data)->save()){
                    $this->success('编辑成功');
                }else{
                    $this->error('保存失败请重试');
                }
            }elseif($result['error'] == 1){
                $this->error($result['msg']);
            }else{
                $this->error('保存失败请重试');
            }
        }else{
            //快店管理-查看 权限
            if (!in_array(150, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }

            $this->assign('now_acitive',$now_acitive);
            $this->display();
        }
    }

    // 快店管理 删除
    public function active_store_del(){
        if(IS_AJAX){
            //快店管理-删除 权限
            if (!in_array(153, $this->house_session['menus'])) {
                $this->ajaxReturn(array('msg'=>'对不起，您没有权限执行此操作','error'=>1));
            }

            $store_id = $_POST['store_id'];
            if(!$store_id){
                $this->ajaxReturn( array('msg'=>'信息错误','error'=>1));
            }
            $condition['village_id'] = $this->village_id;
            $condition['store_id'] = $store_id;
            $village_store = D('House_village_store')->where($condition)->find();
            if(!$village_store){
                $this->ajaxReturn( array('msg'=>'暂无此信息','error'=>1));
            }
            if(D('House_village_store')->where($condition)->delete()){
                $this->ajaxReturn( array('msg'=>'删除成功','error'=>0));
            }
            $this->ajaxReturn( array('msg'=>'删除失败','error'=>1));
            exit;
        }
    }

    // 团购列表
    public function active_group_list(){
        //团购列表-查看 权限
        if (!in_array(140, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

    	$village_id = $this->village_id;
    	$group = D('House_village_group')->get_limit_list_page($village_id);

    	$this->assign('group',$group);

    	$this->display();
    }

    // 团购列表 添加
    public function active_group(){
    	if(IS_AJAX){
            //团购列表-添加 权限
            if (!in_array(141, $this->house_session['menus'])) {
                $this->ajaxReturn(array('msg'=>'对不起，您没有权限执行此操作','error'=>1));
            }

    		$group_id = intval($_POST['group_id']);
    		$sort = $_POST['sort']?intval($_POST['sort']):0;

    		$result = $this->checkVillageGroup($group_id);
    		if($result['error'] == 0){

    			$data['village_id'] = $this->village_id;
    			$data['group_id'] = $group_id;
    			$data['sort'] = $sort;
				$data['url'] =$this->config['site_url'].'/wap.php?c=Group&a=detail&group_id='.$group_id;
    			if(D('House_village_group')->add($data)){
    				$this->ajaxReturn(array('error'=>0));
    			}


    			$this->ajaxReturn(array('msg'=>'保存失败请重试','error'=>1));
    		}elseif($result['error'] == 1){
    			$this->ajaxReturn(array('msg'=>$result['msg'],'error'=>1));
    		}else{
    			$this->ajaxReturn(array('msg'=>'保存失败请重试','error'=>1));
    		}
    		exit;
    	}else{
            //团购列表-查看 权限
            if (!in_array(140, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }
    		$this->display();
    	}
    }

    public function check_group(){
    	if(IS_AJAX){
    		$village_id = $this->village_id;
    		$group_id = $_POST['group_id'];

    		$result = $this->checkVillageGroup($group_id);
    		if($result['error'] == 0){
    			$this->ajaxReturn($result);
    		}elseif($result['error'] == 1){
    			$this->ajaxReturn(array('msg'=>$result['msg'],'error'=>1));
    		}else{
    			$this->ajaxReturn(array('msg'=>'暂无匹配的团购信息','error'=>1));;
    		}
    		exit;
    	}
    }

   	public function active_group_del(){
   		if(IS_AJAX){
            //团购列表-删除 权限
            if (!in_array(142, $this->house_session['menus'])) {
                $this->ajaxReturn(array('msg'=>'对不起，您没有权限执行此操作','error'=>1));
            }

   			$group_id = $_POST['group_id'];
   			if(!$group_id){
   				$this->ajaxReturn( array('msg'=>'信息错误','error'=>1));
   			}
   			$condition['village_id'] = $this->village_id;
   			$condition['group_id'] = $group_id;
   			$village_group = D('House_village_group')->where($condition)->find();
   			if(!$village_group){
   				$this->ajaxReturn( array('msg'=>'暂无此信息','error'=>1));
   			}
   			if(D('House_village_group')->where($condition)->delete()){
   				$this->ajaxReturn( array('msg'=>'删除成功','error'=>0));
   			}
   			$this->ajaxReturn( array('msg'=>'删除失败','error'=>1));
   			exit;
   		}
   	}

    public function checkVillageGroup($group_id){
    	$village_id = $this->village_id;

    	if(!$group_id){
    		return array('msg'=>'检测通过之后才能保存哦','error'=>1);
    	}

    	$village_group = D('House_village_group')->field(true)->where(array('village_id'=>$village_id,'group_id'=>$group_id))->find();
    	if($village_group){
    		return array('msg'=>'您已经保存该团购了，请勿重复操作','error'=>1);
    	}

    	$now_time = $_SERVER['REQUEST_TIME'];
    	$group_info = D('')->field('`g`.`name` AS `group_name`,`m`.`name` AS `merchant_name`')->table(array(C('DB_PREFIX').'group'=>'g',C('DB_PREFIX').'merchant'=>'m'))->where("`g`.`mer_id`=`m`.`mer_id` AND `g`.`status`='1' AND `m`.`status`='1' AND `g`.`type`='1' AND `g`.`begin_time`<'$now_time' AND `g`.`end_time`>'$now_time' AND `g`.`group_id` ='$group_id'")->find();
    	if(!empty($group_info)){
    		$group_info['error'] = 0;
    		return $group_info;
    	}
    	return array('msg'=>'暂无匹配的团购信息','error'=>1);
    }

    // 预约相关
    public function active_appoint_list(){
        //预约列表-查看 权限
        if (!in_array(146, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

    	$village_id = $this->village_id;
    	$appoint = D('House_village_appoint')->get_limit_list_page($village_id);
    	$this->assign('appoint',$appoint);
    	$this->display();
    }

    public function active_appoint(){
        //预约列表-添加 权限
        if (!in_array(147, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

    	if(IS_POST){
    		$appoint_id = intval($_POST['appoint_id']);
    		$sort = $_POST['sort']?intval($_POST['sort']):0;

    		$result = $this->checkVillageAppoint($appoint_id);
    		if($result['error'] == 0){
				if($_FILES['img']['error'] != 4){
					$image = D('Image')->handle($this->village['village_id'],'house/appoint',3,array('size'=>5),false);
					if (!$image['error']) {
						$data['pic'] = str_replace('/upload/house/appoint/','',$image['url']['img']);
					}else{
						$this->error($image['msg']);
					}
					$data['label'] = $_POST['label'];
					$data['index_show'] = '1';
				}
    			$data['village_id'] = $this->village_id;
    			$data['appoint_id'] = $appoint_id;
    			$data['sort'] = $sort;
    			$data['url'] = $this->config['site_url'].'/wap.php?c=Appoint&a=detail&appoint_id='.$appoint_id;

    			if(D('House_village_appoint')->add($data)){
					$this->success('添加成功');
    			}else{
					$this->error('保存失败请重试');
				}
    		}elseif($result['error'] == 1){
				$this->error($result['msg']);
    		}else{
				$this->error('保存失败请重试');
    		}
    	}else{
    		$this->display();
    	}
    }
	public function active_appoint_edit(){
		$now_acitive = D('House_village_appoint')->field(true)->where(array('pigcms_id'=>$_GET['id'],'village_id'=>$this->village['village_id']))->find();
		if(empty($now_acitive)){
			$this->error('当前推荐的预约不存在');
		}
    	if(IS_POST){
            //预约列表-编辑 权限
            if (!in_array(148, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }

			$data['pigcms_id'] = $now_acitive['pigcms_id'];
    		$appoint_id = intval($_POST['appoint_id']);
    		$sort = $_POST['sort']?intval($_POST['sort']):0;

    		$result = $this->checkVillageAppoint($appoint_id);
    		if($result['error'] == 0){
				if($_FILES['img']['error'] != 4){
					$image = D('Image')->handle($this->village['village_id'],'house/appoint',3,array('size'=>5),false);
					if (!$image['error']) {
						$data['pic'] = str_replace('/upload/house/appoint/','',$image['url']['img']);
					}else{
						$this->error($image['msg']);
					}
				}
				$data['label'] = $_POST['label'];
				if(!empty($data['label'])){
					$data['index_show'] = '1';
				}else{
					$data['index_show'] = '0';
				}

    			$data['village_id'] = $this->village_id;
    			$data['appoint_id'] = $appoint_id;
    			$data['sort'] = $sort;

    			if(D('House_village_appoint')->data($data)->save()){
					$this->success('编辑成功');
    			}else{
					$this->error('保存失败请重试');
				}
    		}elseif($result['error'] == 1){
				$this->error($result['msg']);
    		}else{
				$this->error('保存失败请重试');
    		}
    	}else{
            //预约列表-查看 权限
            if (!in_array(146, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }

			$this->assign('now_acitive',$now_acitive);
    		$this->display();
    	}
    }

    public function check_appoint(){
    	if(IS_AJAX){
    		$village_id = $this->village_id;
    		$appoint_id = $_POST['appoint_id'];

    		$result = $this->checkVillageAppoint($appoint_id);
    		if($result['error'] == 0){
    			$this->ajaxReturn($result);
    		}elseif($result['error'] == 1){
    			$this->ajaxReturn(array('msg'=>$result['msg'],'error'=>1));
    		}else{
    			$this->ajaxReturn(array('msg'=>'暂无匹配的预约信息','error'=>1));;
    		}
    		exit;
    	}
    }

    public function active_appoint_del(){
    	if(IS_AJAX){
            //美预约列表-删除 权限
            if (!in_array(149, $this->house_session['menus'])) {
                $this->ajaxReturn(array('msg'=>'对不起，您没有权限执行此操作','error'=>1));
            }

    		$appoint_id = $_POST['appoint_id'];
    		if(!$appoint_id){
    			$this->ajaxReturn( array('msg'=>'信息错误','error'=>1));
    		}
    		$condition['village_id'] = $this->village_id;
    		$condition['appoint_id'] = $appoint_id;
    		$village_appoint = D('House_village_appoint')->field(true)->where($condition)->find();
    		if(!$village_appoint){
    			$this->ajaxReturn( array('msg'=>'暂无此信息','error'=>1));
    		}
    		if(D('House_village_appoint')->where($condition)->delete()){
    			$this->ajaxReturn( array('msg'=>'删除成功','error'=>0));
    		}
    		$this->ajaxReturn( array('msg'=>'删除失败','error'=>1));
    		exit;
    	}
    }

    public function checkVillageAppoint($appoint_id){
    	$village_id = $this->village_id;

    	if(!$appoint_id){
    		return array('msg'=>'检测通过之后才能保存哦','error'=>1);
    	}
    	$condition = array('village_id'=>$village_id,'appoint_id'=>$appoint_id);
		if($_GET['id']){
			$condition['pigcms_id'] = array('neq',$_GET['id']);
		}
    	$village_appoint = D('House_village_appoint')->field(true)->where($condition)->find();

    	if($village_appoint){
    		return array('msg'=>'您已经保存该预约了，请勿重复操作','error'=>1);
    	}

    	$now_time = $_SERVER['REQUEST_TIME'];
    	$appoint_info = D('')->field('`a`.`appoint_name` ,`m`.`name` AS `merchant_name`')->table(array(C('DB_PREFIX').'appoint'=>'a',C('DB_PREFIX').'merchant'=>'m'))->where("`a`.`mer_id`=`m`.`mer_id` AND `a`.`check_status`='1' AND `a`.`appoint_status`='0' AND `m`.`status`='1' AND `a`.`start_time`<'$now_time' AND `a`.`end_time`>'$now_time' AND `a`.`appoint_id`='$appoint_id'")->find();
    	if(!empty($appoint_info)){
    		$appoint_info['error'] = 0;
    		return $appoint_info;
    	}
    	return array('msg'=>'暂无匹配的预约信息','error'=>1);
    }

    // 美食列表 快店相关
    public function active_meal_list(){
        //美食列表-查看 权限
        if (!in_array(143, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

    	$village_id = $this->village_id;
    	$meal = D('House_village_meal')->get_limit_list_page($village_id);

    	$this->assign('meal',$meal);
    	$this->display();
    }

    // 美食列表 添加
    public function active_meal(){
    	if(IS_AJAX){
            //美食列表-添加 权限
            if (!in_array(144, $this->house_session['menus'])) {
                $this->ajaxReturn(array('msg'=>'对不起，您没有权限执行此操作','error'=>1));
            }

    		$store_id = intval($_POST['store_id']);
    		$sort = $_POST['sort']?intval($_POST['sort']):0;

    		$result = $this->checkVillageMeal($store_id);
    		if($result['error'] == 0){

    			$data['village_id'] = $this->village_id;
    			$data['store_id'] = $store_id;
    			$data['sort'] = $sort;
				$data['url'] =$this->config['site_url'].'/wap.php?c=Foodshop&a=shop&store_id='.$store_id;
    			if(D('House_village_meal')->add($data)){
    				$this->ajaxReturn(array('error'=>0));
    			}

    			$this->ajaxReturn(array('msg'=>'保存失败请重试','error'=>1));
    		}elseif($result['error'] == 1){
    			$this->ajaxReturn(array('msg'=>$result['msg'],'error'=>1));
    		}else{
    			$this->ajaxReturn(array('msg'=>'保存失败请重试','error'=>1));
    		}
    		exit;
    	}else{
            //美食列表-查看 权限
            if (!in_array(143, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }

    		$this->display();
    	}
    }

    public function check_meal(){
    	if(IS_AJAX){
    		$village_id = $this->village_id;
    		$store_id = $_POST['store_id'];

    		$result = $this->checkVillageMeal($store_id);
    		if($result['error'] == 0){
    			$this->ajaxReturn($result);
    		}elseif($result['error'] == 1){
    			$this->ajaxReturn(array('msg'=>$result['msg'],'error'=>1));
    		}else{
    			$this->ajaxReturn(array('msg'=>'暂无匹配的餐饮信息','error'=>1));;
    		}
    		exit;
    	}
    }

    public function active_meal_del(){
    	if(IS_AJAX){
            //美食列表-删除 权限
            if (!in_array(145, $this->house_session['menus'])) {
                $this->ajaxReturn(array('msg'=>'对不起，您没有权限执行此操作','error'=>1));
            }

    		$store_id = $_POST['store_id'];
    		if(!$store_id){
    			$this->ajaxReturn( array('msg'=>'信息错误','error'=>1));
    		}
    		$condition['village_id'] = $this->village_id;
    		$condition['store_id'] = $store_id;
    		$village_appoint = D('House_village_meal')->where($condition)->find();
    		if(!$village_appoint){
    			$this->ajaxReturn( array('msg'=>'暂无此信息','error'=>1));
    		}
    		if(D('House_village_meal')->where($condition)->delete()){
    			$this->ajaxReturn( array('msg'=>'删除成功','error'=>0));
    		}
    		$this->ajaxReturn( array('msg'=>'删除失败','error'=>1));
    		exit;
    	}
    }

    public function checkVillageMeal($store_id){
    	$village_id = $this->village_id;

    	if(!$store_id){
    		return array('msg'=>'检测通过之后才能保存哦','error'=>1);
    	}

    	$village_meal= D('House_village_meal')->where(array('village_id'=>$village_id,'store_id'=>$store_id))->find();
    	if($village_meal){
    		return array('msg'=>'您已经保存该餐饮了，请勿重复操作','error'=>1);
    	}

    	$now_time = $_SERVER['REQUEST_TIME'];
    	$meal_info = D('')->field('`m`.`name` AS `merchant_name`,`ms`.`name` AS `store_name`')->table(array(C('DB_PREFIX').'merchant_store'=>'ms',C('DB_PREFIX').'merchant_store_foodshop'=>'msm',C('DB_PREFIX').'merchant'=>'m'))->where("`ms`.`have_meal`='1' AND `ms`.`status`='1' AND `m`.`mer_id`=`ms`.`mer_id` AND `ms`.`store_id`=`msm`.`store_id` AND `ms`.`store_id`='$store_id'")->find();
    	if(!empty($meal_info)){
    		$meal_info['error'] = 0;
    		return $meal_info;
    	}
    	return array('msg'=>'暂无匹配的餐饮信息','error'=>1);
    }



	public function ajax_help($group, $module, $action)
	{
		$url = strtolower($group . '_' . $module . '_' . $action);
		$url = 'http://o2o-service.pigcms.com/workorder/serviceAnswerApi.php?url=' . $url;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_TIMEOUT, 4);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		$content = curl_exec($ch);
		curl_close($ch);

		$content = json_decode($content,true);
		foreach ($content as $value) {
			$class = $value['is_video'] == 1 ? 'class="video"' : 'class="writing"';
			$html .= '<p class="lianjie zuoce_clear "><a ' . $class . ' href="javascript:openwin(' . "'" . U('Index/help', array('answer_id' => $value['answer_id'])) . "'" .',768,960)">'.$value['title'].'</a></p>';
		}
		if (empty($html)) {
			$html = '<p class="lianjie zuoce_clear">没有帮助教程！</a>';
		}
		echo $html;
	}

	public function help()
	{
		$this->assign('answer_id', $_GET['answer_id']);
		$this->display();
	}

	public function cancel()
	{
		if (D('House_village')->where(array('village_id' => $this->village_id))->save(array('openid' => '', 'avatar' => '', 'nickname' => ''))) {
			exit(json_encode(array('error_code' => 0, 'msg' => 'ok')));
		} else {
			exit(json_encode(array('error_code' => 1, 'msg' => '取消失败')));
		}
	}

	public function check_bind()
	{
		if ($this->village['openid']) {
			exit(json_encode(array('error_code' => 0, 'msg' => 'ok', 'nickname' => $this->village['nickname'], 'avatar' => $this->village['avatar'])));
		} else {
			exit(json_encode(array('error_code' => 1, 'msg' => 'no')));
		}
	}

	public function worker()
	{
        //工作人员-查看 权限
        if (!in_array(4, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

        $v20GroupUrl = "/village/village.iframe/house_villagegroup_index_list";
        $this->assign('v20GroupUrl', $v20GroupUrl);

		$workers = D('House_worker')->field(true)->where(array('village_id' => $this->village_id,'is_del'=>0))->select();
		$this->assign('workers', $workers);
		$this->display();
	}

	public function worker_add()
	{
        //工作人员-添加 权限
        if (!in_array(5, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

		if (IS_POST) {
			$data = array('village_id' => $this->village_id);
			$data['name'] = isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '';
			$data['phone'] = isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; 
			$data['type'] = isset($_POST['type']) ? intval($_POST['type']) : 0;
            $data['open_door'] = isset($_POST['open_door']) ? intval($_POST['open_door']) : 0;
            $data['ncov_2019_service'] = isset($_POST['ncov_2019_service']) ? intval($_POST['ncov_2019_service']) : 0;
            $data['id_card'] = isset($_POST['id_card']) ? trim($_POST['id_card']) : '';
            $data['ic_card'] = isset($_POST['ic_card']) ? trim($_POST['ic_card']) : '';
			if (empty($data['name']) || empty($data['phone'])) {
				$this->error('姓名和电话不能为空');
			}

            if(!preg_match('/^[0-9]{11}$/',$data['phone'])){
                $this->error('请输入正确的手机号！');
            }

			if (D('House_worker')->field(true)->where(array('village_id' => $this->village_id, 'phone' => $data['phone']))->find()) {
				$this->error('电话已存在，不能重复增加');
			}

            $add_id = D('House_worker')->add($data);
            if ($add_id) {
                $this->worker_user_amend($data);
            }
			$this->success('增加成功');
		} else {

			$this->display();
		}
	}

	public function worker_edit()
	{
		$wid = isset($_GET['wid']) ? intval($_GET['wid']) : 0;
		$worker = D('House_worker')->field(true)->where(array('village_id' => $this->village_id, 'wid' => $wid))->find();
		if (empty($worker)) {
			$this->error('没有该条工作人员记录 ');
		}
        //工作人员-查看 权限
        if (!in_array(4, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
		if (IS_POST) {
            //工作人员-编辑 权限
            if (!in_array(7, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }
			$data = array('village_id' => $this->village_id);
			$data['name'] = isset($_POST['name']) ? htmlspecialchars($_POST['name']) : '';
			$data['phone'] = isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : '';
			//$data['type'] = isset($_POST['type']) ? intval($_POST['type']) : 0;
			$data['open_door'] = isset($_POST['open_door']) ? intval($_POST['open_door']) : 0;
			$data['ncov_2019_service'] = isset($_POST['ncov_2019_service']) ? intval($_POST['ncov_2019_service']) : 0;
            $data['id_card'] = isset($_POST['id_card']) ? trim($_POST['id_card']) : '';
            $data['ic_card'] = isset($_POST['ic_card']) ? trim($_POST['ic_card']) : '';
			if (isset($_POST['status']) && $worker['openid']) {
				$data['status'] = $_POST['status'];
			}

			if (empty($data['name']) || empty($data['phone'])) {
				$this->error('姓名和电话不能为空');
			}

            if(!preg_match('/^[0-9]{11}$/',$data['phone'])){
                $this->error('请输入正确的手机号！');
            }

			$this_worker = D('House_worker')->field(true)->where(array('village_id' => $this->village_id, 'phone' => $data['phone']))->find();
			if (!empty($this_worker) && ($worker['wid'] != $this_worker['wid']) ) {
				$this->error('电话已存在，不能重复增加');
			}
			D('House_worker')->where(array('village_id' => $this->village_id, 'wid' => $wid))->save($data);
            $data['phone'] = $worker['phone'];
            $this->worker_user_amend($data,$wid);
			$this->success('修改成功');
		} else {

			$this->assign('worker', $worker);
            $this->assign('worker_name',$this->worker_name[$worker['type']]);
			$this->display();
		}
	}

    public function ajax_get_worker()
    {
        $condition_where['village_id'] = $this->village_id;
        $condition_where['is_del'] = 0;
        if ($_POST['search']) {
            $where['name'] = array('like','%'.trim($_POST['search']).'%');
            $where['phone'] = array('like','%'.trim($_POST['search']).'%');
            $where['_logic'] = 'OR';
            $condition_where['_complex'] = $where;
        }
        $workers = D('House_worker')->field(true)->where($condition_where)->select();
        exit(json_encode($workers));
    }

    //工作人员对应的用户更新
    public function worker_user_amend($data,$wid=0){
        $data['type'] = 4 ;//工作人员
        $data['memo'] ='工作人员自动绑定小区';
        $data['status'] = 1 ;
        $data['pass_time'] = $_SERVER['REQUEST_TIME'];
        if($wid){
            $worker = D('House_worker')->field(true)->where(array('village_id' => $this->village_id, 'wid' => $wid))->find();
            // 原绑定信息
            $where['phone'] = $data['phone']; // 原手机号
            $where['village_id'] = $worker['village_id'];
            $where['type'] = 4;
            $bind_info = M('House_village_user_bind')->where($where)->find();

            if ($worker['phone'] != $where['phone'] || $bind_info['uid']<=0) { // 修改手机号
                $data['phone'] = $worker['phone'];
                $now_user = D('User')->get_user($data['phone'], 'phone');
                $data['uid'] = $now_user ? $now_user['uid'] : 0;
            }

            if ($bind_info) { // 已有业主信息 更新
                M('House_village_user_bind')->where($where)->save($data);
            }else{
                $data['usernum'] = rand(0,99999) . '-' . time();
                $data['add_time'] = $_SERVER['REQUEST_TIME'] ;
                M('House_village_user_bind')->add($data);
            }
        }else{
            $data['add_time'] = $_SERVER['REQUEST_TIME'] ;
            $data['usernum'] = rand(0,99999) . '-' . time();
            M('House_village_user_bind')->add($data);
        }
    }

	public function check_worker()
	{
		$wid = isset($_GET['wid']) ? intval($_GET['wid']) : 0;
		$worker = D('House_worker')->field(true)->where(array('village_id' => $this->village_id, 'wid' => $wid))->find();
		if (empty($worker)) {
			exit(json_encode(array('error_code' => 1, 'msg' => 'no')));
		}
		if ($worker['openid']) {
			exit(json_encode(array('error_code' => 0, 'msg' => 'ok', 'nickname' => $worker['nickname'], 'avatar' => $worker['avatar'])));
		} else {
			exit(json_encode(array('error_code' => 1, 'msg' => 'no')));
		}
	}

	public function cancel_worker()
	{

        //工作人员-编辑 权限
        if (!in_array(7, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
		$wid = isset($_GET['wid']) ? intval($_GET['wid']) : 0;
		if (D('House_worker')->where(array('village_id' => $this->village_id, 'wid' => $wid,'is_del'=>0))->save(array('status' => 0, 'openid' => '', 'avatar' => '', 'nickname' => ''))) {
			exit(json_encode(array('error_code' => 0, 'msg' => 'ok')));
		} else {
			exit(json_encode(array('error_code' => 1, 'msg' => '取消失败')));
		}
	}
	
	public function worker_delete()
	{
        //工作人员-禁用 权限
        if (!in_array(7, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

		$wid = isset($_GET['wid']) ? intval($_GET['wid']) : 0;
		$now_worker = D('House_worker')->where(array('village_id' => $this->village_id, 'wid' => $wid))->find();
		if(!$now_worker){
			$this->error('该工作人员未找到');
		}
		if (D('House_worker')->where(array('village_id' => $this->village_id, 'wid' => $wid))->save(array('status' => 4, 'openid' => '', 'avatar' => '', 'nickname' => '','phone'=>$now_worker['phone'].'~nouse'))) {
			$where['phone'] = $now_worker['phone'];
			$where['village_id'] = $now_worker['village_id'];
			$where['type'] = 4;
			D('House_village_user_bind')->where($where)->delete();
			$this->success('禁用成功');
		} else {
			$this->error('禁用失败，请重试');
		}
	}

	public function worker_order()
	{
        //工作人员-查看 权限
        if (!in_array(4, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }

		$wid = isset($_GET['wid']) ? intval($_GET['wid']) : 0;
		$begin_time = isset($_GET['begin_time']) ? htmlspecialchars($_GET['begin_time']) : '';
		$end_time = isset($_GET['end_time']) ? htmlspecialchars($_GET['end_time']) : '';

		$worker = D('House_worker')->field(true)->where(array('village_id' => $this->village_id, 'wid' => $wid))->find();
		if (empty($worker)) {
			$this->error('不存在的工作人员，或已辞职');
		}
		$where = array('village_id' => $this->village_id, 'wid' => $wid);
		if ($begin_time && $end_time) {
			$where['begin_time'] = strtotime($begin_time . '00:00:00');
			$where['end_time'] = strtotime($end_time . '23:59:59');
		}elseif($begin_time){
			$where['begin_time'] = strtotime($begin_time . '00:00:00');
		}elseif($end_time){
			$where['end_time'] = strtotime($end_time . '23:59:59');
		}

		$status = $_GET['status'] + 0;
		if($status > 0){
			$where['status'] = $status - 1;
		}

		$order = [];
		if($_GET['time']){
			$order['time'] = $_GET['time'];
		}


		$repair_list = D('House_village_repair_list')->getlist($where , 20 , $order);
		$this->assign('repair_list', $repair_list);
		$this->assign('wid', $wid);
		$this->assign(array('begin_time' => $begin_time, 'end_time' => $end_time));
		$this->display();
	}

    public function info()
    {
    	$bind_id = isset($_GET['bindid']) ? intval($_GET['bindid']) : 0;
    	$cms_id = isset($_GET['pid']) ? intval($_GET['pid']) : 0;
    	if ($bind_id && $cms_id) {
    		$condition['bind_id'] = $bind_id;
    		$condition['pigcms_id'] = $cms_id;
    		$condition['village_id'] = $this->village_id;
    		$repair = D('House_village_repair_list')->getlist($condition, 1);
			$repair = $repair['repair_list'][0];

    		$this->assign('repair', $repair);
    		if ($repair['status']) {
    			$worker = D('House_worker')->field(true)->where(array('wid' => $repair['wid'], 'village_id' => $this->village_id))->find();
    			$this->assign('worker', $worker);
    		} else {
// 	    		$type = $repair['type'] == 1 ? 1 : 0;
// 		    	$workers = D('House_worker')->field(true)->where(array('type' => $type, 'status' => 1, 'village_id' => $this->village_id))->select();
// 		    	$this->assign('workers', $workers);
    		}
    	}
    	$this->display();
    }


	public function worker_export(){
			$begin_time = 0;
			$end_time = 0;
			if(isset($_GET['begin_time']) && !empty($_GET['begin_time'])){
				$begin_time = strtotime($_GET['begin_time'] . '00:00:00');
			}

			if(isset($_GET['end_time']) && !empty($_GET['end_time'])){
				$end_time = strtotime($_GET['end_time'] . '23:59:59');
			}

			if(($begin_time > 0) && ($end_time > 0)){
				if($begin_time > $end_time){
					$this->error("结束时间应大于开始时间");
				}

				$where['begin_time'] = $begin_time;
				$where['end_time'] = $end_time;
			}elseif(isset($begin_time)){
				$where['begin_time'] = $begin_time;
			}elseif(isset($end_time)){
				$where['end_time'] = $end_time;
			}

			$where['village_id'] = $this->village_id;

			$wid = $_GET['wid'] + 0;
			$where['wid'] = $wid;

			$repair_list = D('House_village_repair_list')->getlist($where,1000);
			$repair_list = $repair_list['repair_list'];

			if(count($repair_list) <= 0 ){
				$this->error('无数据导出！');
			}

			require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';

			$title = $this->village['village_name'] . '小区-工作人员任务列表';

			$objExcel = new PHPExcel();
			$objProps = $objExcel->getProperties();
			// 设置文档基本属性
			$objProps->setCreator($title);
			$objProps->setTitle($title);
			$objProps->setSubject($title);
			$objProps->setDescription($title);

			$length = ceil(count($repair_list)/1000);

			for ($i = 0; $i < $length; $i++) {
				$i && $objExcel->createSheet();
				$objExcel->setActiveSheetIndex($i);

				$objExcel->getActiveSheet()->setTitle('第' . ($i+1) . '个一千个用户');
				$objActSheet = $objExcel->getActiveSheet();

				$objActSheet->setCellValue('A1', '业主编号');
				$objActSheet->setCellValue('B1', '报修人');
				$objActSheet->setCellValue('C1', '状态');
				$objActSheet->setCellValue('D1', '报修内容');
				$objActSheet->setCellValue('E1', '报修时间');
				$objActSheet->setCellValue('F1', '报修地址');
				$objActSheet->setCellValue('G1', '评分');
				$objActSheet->setCellValue('H1', '处理人员');
				$objActSheet->setCellValue('I1', '处理人员手机号码');
				$objActSheet->setCellValue('J1', '回复内容');
				$objActSheet->setCellValue('K1', '回复时间');
				$objActSheet->setCellValue('L1', '评论内容');
				$objActSheet->setCellValue('M1', '评论时间');

				if (!empty($repair_list)) {
					$index = 2;

					$cell_list = range('A','M');
					foreach ($cell_list as $cell) {
						$objActSheet->getColumnDimension($cell)->setWidth(40);
					}

					foreach ($repair_list as $value) {
						$objActSheet->setCellValueExplicit('A' . $index, $value['usernum']);
						$objActSheet->setCellValueExplicit('B' . $index, $value['name']);

						if($value['status'] == 0){
							$status_val = '未指派';
						}elseif($value['status'] == 1){
							$status_val = '已指派';
						}elseif($value['status'] == 2){
							$status_val = '已受理';
						}elseif($value['status'] == 3){
							$status_val = '已处理';
						}elseif($value['status'] == 4){
							$status_val = '业主已评价';
						}
						$objActSheet->setCellValueExplicit('C' . $index, $status_val);
						$objActSheet->setCellValueExplicit('D' . $index, $value['content']);
						$objActSheet->setCellValueExplicit('E' . $index, date('Y-m-d H:i:s',$value['time']));
						$objActSheet->setCellValueExplicit('F' . $index, $value['address']);
						$objActSheet->setCellValueExplicit('G' . $index, $value['score']);




						$bind_id = $value['bind_id'];
						$cms_id = $value['pid'];
						if ($bind_id && $cms_id) {
							$condition['bind_id'] = $bind_id;
							$condition['pigcms_id'] = $cms_id;
							$condition['village_id'] = $this->village_id;
							$repair = D('House_village_repair_list')->getlist($condition, 1);
							$repair = $repair['repair_list'][0];

							if ($repair['status']) {
								$worker = D('House_worker')->field(true)->where(array('wid' => $repair['wid'], 'village_id' => $this->village_id))->find();
							}

							$objActSheet->setCellValueExplicit('H' . $index, $worker['name']);
							$objActSheet->setCellValueExplicit('I' . $index, $worker['phone']);
							$objActSheet->setCellValueExplicit('J' . $index, $repair['reply_content']);
							$objActSheet->setCellValueExplicit('K' . $index, $repair['reply_time']>0 ? date('Y-m-d H:i:s',$repair['reply_time']) : "");
							$objActSheet->setCellValueExplicit('L' . $index, $repair['comment']);
							$objActSheet->setCellValueExplicit('M' . $index, $repair['comment_time']>0 ? date('Y-m-d H:i:s',$repair['comment_time']) : "");
						}
						$index++;
					}
				}
				sleep(2);
				//输出
				$objWriter = new PHPExcel_Writer_Excel5($objExcel);
				ob_end_clean();
				header("Pragma: public");
				header("Expires: 0");
				header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
				header("Content-Type:application/force-download");
				header("Content-Type:application/vnd.ms-execl");
				header("Content-Type:application/octet-stream");
				header("Content-Type:application/download");
				header('Content-Disposition:attachment;filename="'.$title.'_' . date("Y-m-d h:i:sa", time()) . '.xls"');
				header("Content-Transfer-Encoding:binary");
				$objWriter->save('php://output');
				exit();
		}
	}

    // 业主属性列表
    public function attribute_list() {
        //业主属性-查看 权限
        if (!in_array(280, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        $attribute_list = M('House_village_user_attribute')->where(array('village_id'=>$this->village_id))->select();
        $this->assign('attribute_list',$attribute_list);
        $this->display();
    }

    // 业主属性编辑
    public function attribute_add() {
        //业主属性-添加 权限
        if (!in_array(281, $this->house_session['menus'])) {
            $this->error('对不起，您没有权限执行此操作');
        }
        
        if (IS_POST) {
            $name = trim($_POST['name']);
            if (!$name) {
                $this->error('属性名不能为空');
            }
            $data['name'] = $name;
            $data['time'] = time();
            $data['village_id'] = $this->village_id;
            $insert_id = M('House_village_user_attribute')->add($data);
            if ($insert_id) {
                $this->success('添加成功');
            }else{
                $this->error('添加失败');                
            }
        }else{
            $this->display();;
        }
    }  

    // 业主属性编辑
    public function attribute_edit() {
       
        if (IS_POST) {
            //业主属性-编辑 权限
            if (!in_array(282, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }
            $name = trim($_POST['name']);
            if (!$name) {
                $this->error('属性名不能为空');
            }
            $data['name'] = $name;
            if (!$_POST['id']) {
                $this->error('参数传递错误');
            }
            $pigcms_id = intval($_POST['id']);
            $update = M('House_village_user_attribute')->where(array('pigcms_id'=>$pigcms_id,'village_id'=>$this->village_id))->save($data);
            if ($update!==false) {
                $this->success('修改成功');
            }else{
                $this->error('修改失败');                
            }

        }else{ 
            $pigcms_id = intval($_GET['id']);
            if (!$pigcms_id) {
                $this->error('参数传递错误');
            }
           
            $attribute_detail = M('House_village_user_attribute')->where(array('pigcms_id'=>$pigcms_id,'village_id'=>$this->village_id))->find();
            if (!$attribute_detail) {
                $this->error('该内容不存在或已删除');
            }
            $this->assign('attribute_detail',$attribute_detail);
            
            $this->display();
        }
    }

        // 业主属性删除
        public function attribute_del() {
            //业主属性-删除 权限
            if (!in_array(283, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }
            $pigcms_id = intval($_GET['id']);
            if (!$pigcms_id) {
                $this->error('参数传递错误');
            }
            $res = M('House_village_user_attribute')->where(array('pigcms_id'=>$pigcms_id,'village_id'=>$this->village_id))->delete();
            if ($res) {
                $this->success('删除成功');
            }else{
                $this->error('删除失败');
            }
        }

        public function viewpage_list()
        {
            //业主属性-查看 权限
            if (!in_array(287, $this->house_session['menus'])) {
                $this->error('对不起，您没有权限执行此操作');
            }
            $village_id = $this->house_session['village_id'];
            $page_url = get_base_url("pages/village_menu/index?village_id={$village_id}&viewpage=true");
            $this->assign('page_url', $page_url);

            $this->assign('assign_type', D('House_visualize_assign')->house_visualize_assign($village_id,7));
            $now_village = D('House_village')->where(array('village_id'=>$this->house_session['village_id']))->find();
            $this->assign('village_id', $village_id);
            $this->assign('now_village', $now_village);
            
            $jz_open = D('Config_customization')->getWSXCustomizedOpen();
            if ($jz_open) {
                // 定制返回对应尺寸
                $database_adver_category  = D('House_adver_category');
                $condition_top = [];
                $condition_top['cat_key'] = 'house_app_index_top';
                $index_top_size = $database_adver_category->where($condition_top)->getField('size_info');
                $condition_center = [];
                $condition_center['cat_key'] = 'house_app_index_center';
                $index_center_size = $database_adver_category->where($condition_center)->getField('size_info');
            }
            if (!trim($index_top_size)) {
                $index_top_size = '640*278';
            }
            if (!trim($index_center_size)) {
                $index_center_size = '640*206';
            }
            $this->assign('index_top_size', $index_top_size);
            $this->assign('index_center_size', $index_center_size);
            $this->assign('village_id', $village_id);
            $this->display();

        }



        public function footer_list()
        {
            $village_id = $this->house_session['village_id'];
            $base_url = D('House_village')->base_url;
            $page_url = $this->config['site_url'] . "/packapp/village/pages/village_menu/index?village_id={$village_id}&viewpage=true";
            $this->assign('page_url', $page_url);

            $now_village = D('House_village')->where(array('village_id'=>$this->house_session['village_id']))->find();
            $this->assign('now_village', $now_village);

            $this->assign('assign_type', D('House_visualize_assign')->house_visualize_assign($village_id,7));

            // 底部导航模板
            $footer_config = D('House_village_info');
            $footer_template = $footer_config->where(array('village_id' => $village_id))->field('template_floor')->find();
            switch ($footer_template['template_floor']) {
                case 3:
                    $footer_template['template'] = '模板三';
                    break;
                case 2:
                    $footer_template['template'] = '模板二';
                    break;
                default :
                    $footer_template['template'] = '模板一';
                    break;
            }
            $this->assign('footer_template', $footer_template['template']);

            $this->display();
        }

    public function top_list()
    {
        $village_id = $this->house_session['village_id'];
        $base_url = D('House_village')->base_url;
        $page_url = $this->config['site_url'] . "/packapp/village/pages/village_menu/index?village_id={$village_id}&viewpage=true";
        $this->assign('page_url', $page_url);
        $assign_type=D('House_visualize_assign')->house_visualize_assign($village_id,12);
        $this->assign('assign_type', $assign_type);

        // 顶部导航模板
        $footer_config = D('House_village_info');
        $footer_template = $footer_config->where(array('village_id' => $village_id))->field('template_top')->find();
        $template_id = (int)$footer_template['template_top'];
        $this->assign('template_id',$template_id);
        $data = [
            [
                'id' => 1,
                'template' => '模板一',
                'image' => $this->config['site_url'].'/static/images/house/top_template_1.png'
            ],
            [
                'id' => 2,
                'template' => '模板二',
                'image' => $this->config['site_url'].'/static/images/house/top_template_2.png'
            ],
            [
            'id' => 3,
            'template' => '模板三',
            'image' => $this->config['site_url'].'/static/images/house/top_template_3.png'
        ]
        ];
        $jsdhwy_new_wap_index=D('Config_customization')->getWapIndexJsdhwyUi();
        if($jsdhwy_new_wap_index>0){
            $data[]=array( 'id' => 4, 'template' => '模板四', 'image' => $this->config['site_url'].'/static/images/house/top_template_4.png');
        }
        if($this->yuanJino2oCustomized>0){
            $data = [
                [
                    'id' => 1,
                    'template' => '模板一',
                    'image' => $this->config['site_url'].'/static/images/house/top_template_1.png'
                ],
                [
                    'id' => 5,
                    'template' => '模板二',
                    'image' => $this->config['site_url'].'/static/images/house/top_template_5.png'
                ],
            ];
        }
        $this->assign('template',$data);
        $this->display();
    }


        public function adver_list(){

            $database_adver = D('House_adver');
            if($_GET['cat_key']){
                $database_adver_category  = D('House_adver_category');
                $condition_adver_category['cat_key'] = $_GET['cat_key'];
                $now_category = $database_adver_category->field(true)->where($condition_adver_category)->find();
                $condition_adver['cat_id'] = $now_category['cat_id'];
                $adver_list = $database_adver->field(true)->where($condition_adver)->order('`id` DESC')->select();

            }else{
                $adver_list = $database_adver->field(true)->order('`id` DESC')->select();
            }

            $this->assign('adver_list', $adver_list);
            $this->display();
        }

        public function adver_add()
        {
            $database_adver_category  = D('House_adver_category');


            if(IS_POST){
                //$_POST['village_id'] = $_SESSION['house']['village_id'];
                //var_dump($_POST);die;
                $data['name']=$_POST['name'];
                $data['sub_name']=$_POST['sub_name'];
                $data['pic']=$_POST['pic'];
                $data['url']=$_POST['url'];
                $data['bg_color']=$_POST['bg_color'];
                $data['sort']=$_POST['sort'];
                $data['status']=$_POST['status'];
                $data['last_time']=time();

                $result = M('House_adver')->add($data);
                if($result){
                    $this->success('添加成功');
                }else{
                    $this->error('添加失败');
                }
            }else{
                $condition_adver_category['cat_key'] = $_GET['cat_key'];
                $now_category = $database_adver_category->field(true)->where($condition_adver_category)->find();
                $this->assign('now_category', $now_category);
                $this->display();
            }



        }

        public function adver_edit()
        {
            if(IS_POST){
                //$_POST['village_id'] = $_SESSION['house']['village_id'];

                $result = M('House_adver')->where(array('id'=>$_POST['id']))->save($_POST);
                if($result){
                    $this->success('修改成功');
                }else{
                    $this->error('修改失败');
                }
            }else{
                $slider_info = M('House_adver')->where(array('id'=>$_GET['id']))->find();

                $this->assign('slider_info',$slider_info);
                $this->display();
            }



        }

        public function adver_del()
        {
            $id = $_GET['id'] + 0;
            if(!$id){
                $this->error('传递参数有误！');
            }

            $result = M('House_adver')->where(array('id'=>$id))->delete();
            if($result){
                $this->success('删除成功');
            }else{
                $this->error('删除失败');
            }

        }


        protected function get_category($cat_id){
            $database_adver_category  = D('House_adver_category');
            $condition_adver_category['cat_id'] = $cat_id;
            $now_category = $database_adver_category->field(true)->where($condition_adver_category)->find();
            return $now_category;
        }



    public function foot_menu_add() {
        if (IS_POST) {

            if (($_FILES['pic_path']['error'] != 4) || ($_FILES['hover_pic_path']['error'] != 4)) {
                $image = D('Image')->handle($this->village_id, 'slider');
                if (!$image['error']) {
                    if($image['url']['pic_path']){
                        if (is_array($image['url']['pic_path'])) {
                            $pic_path = reset($image['url']['pic_path']);
                        } else {
                            $pic_path = $image['url']['pic_path'];
                        }
                        $_POST['pic_path'] =str_replace('/upload/slider/', '', $pic_path);
                    }

                    if($image['url']['hover_pic_path']){
                        if (is_array($image['url']['hover_pic_path'])) {
                            $hover_pic_path = reset($image['url']['hover_pic_path']);
                        } else {
                            $hover_pic_path = $image['url']['hover_pic_path'];
                        }
                        $_POST['hover_pic_path'] = str_replace('/upload/slider/', '', $hover_pic_path);
                    }
                } else {
                    //$this->frame_submit_tips(0, $image['msg'] ? $image['msg'] : $image['message']);
                    $this->error($image['msg'] ? $image['msg'] : $image['message']);
                }
            }

            $_POST['url'] = htmlspecialchars_decode($_POST['url']);
            $_POST['village_id'] = $this->house_session['village_id'];
            $_POST['type'] = 0;
            $_POST['add_time'] = time();

            $result = M('House_village_footer_menu')->data($_POST)->add();
            if ($result) {
                $id = $result;
                D('Image')->update_table_id('/upload/slider/' . $_POST['pic'], $id, 'slider');
                //$this->frame_submit_tips(1,'添加成功！');
                exit('<html><head><script>window.top.msg(1,"保存成功！",true,2);window.parent.closeiframe();window.parent.main_refresh();</script></head></html>');
            } else {
                exit('<html><head><script>window.top.msg(0,"添加失败，请重试！",true,2);window.parent.closeiframe();</script></head></html>');
            }

        } else {
            //小程序列表
            $wxapp_list = M('Wxapp_other')->field(true)->select();
            $this->assign('wxapp_list',$wxapp_list);
            $this->assign('bg_color','#F3F3F3');
            $this->display();
        }
    }

    public function foot_menu_edit()
    {
        $this->assign('bg_color', '#F3F3F3');
        $id = $this->_get('id');
        $where['id'] = $id;
        $village_id = $this->house_session['village_id'];
        if (IS_POST) {
            $now_adver = M('House_village_footer_menu')->field(true)->where(['id'=>$_POST['id']])->find();
            if (($_FILES['pic_path']['error'] != 4) || ($_FILES['hover_pic_path']['error'] != 4)) {
                $image = D('Image')->handle($this->system_session['id'], 'slider');
                if (!$image['error']) {
                    if($image['url']['pic_path']){
                        if (is_array($image['url']['pic_path'])) {
                            $pic_path = reset($image['url']['pic_path']);
                        } else {
                            $pic_path = $image['url']['pic_path'];
                        }
                        $_POST['pic_path'] =str_replace('/upload/slider/', '', $pic_path);
                    }

                    if($image['url']['hover_pic_path']){
                        if (is_array($image['url']['hover_pic_path'])) {
                            $hover_pic_path = reset($image['url']['hover_pic_path']);
                        } else {
                            $hover_pic_path = $image['url']['hover_pic_path'];
                        }
                        $_POST['hover_pic_path'] = str_replace('/upload/slider/', '', $hover_pic_path);
                    }
                } else {
                    $this->frame_submit_tips(0, $image['msg']);
                }
            }

            $_POST['url'] = htmlspecialchars_decode($_POST['url']);
            $_POST['add_time'] = time();
            $now_adver['url']=htmlspecialchars_decode($now_adver['url']);

            //todo 小区复制平台广告 生成属于小区的广告
            $rel=D('House_link')->get_filter_village_id($village_id,$now_adver);
            if(!empty($rel)){
                $insert_data=$now_adver;
                unset($insert_data['id'],$insert_data['filter_village_ids']);
                $insert_data['village_id']=$village_id;
                $insert_data['name']=empty($_POST['name']) ? $now_adver['name'] : $_POST['name'];
                $insert_data['pic_path']=empty($_POST['pic_path']) ? $now_adver['pic_path'] : $_POST['pic_path'];
                $insert_data['hover_pic_path']=empty($_POST['hover_pic_path']) ? $now_adver['hover_pic_path'] : $_POST['hover_pic_path'];
                $insert_data['sort']=empty($_POST['sort']) ? $now_adver['sort'] : $_POST['sort'];
                $insert_data['add_time']=time();
                $insert_data['source_type']=0;
                $insert_data['url']=D('House_link')->replace_village_parameter((empty($_POST['url']) ? $now_adver['url'] : $_POST['url']),$village_id);
                if(isset($_POST['wxapp_id'])){
                    $insert_data['wxapp_id']=trim($_POST['wxapp_id']);
                    $insert_data['wxapp_page']=trim($_POST['wxapp_page']);
                }
                // 原广告数据写入关联village_ids
                M('House_village_footer_menu')->where(['id'=>$now_adver['id']])->data(['filter_village_ids'=>implode(',',$rel)])->save();
                M('House_village_footer_menu')->data($insert_data)->add();
                $this->frame_submit_tips(1,'修改成功');
            }else{
                $result = M('House_village_footer_menu')->data($_POST)->save();
                if($result){
                    $this->frame_submit_tips(1,'修改成功');
                }else{
                    $this->frame_error_tips(0,'修改失败！请重试~');
                }
            }
        } else {
            $this->assign('bg_color','#F3F3F3');
            $condition_adver['id'] = $_GET['id'];
            $foot_menu_info = M('House_village_footer_menu')->where($condition_adver)->find();
            if (!$foot_menu_info) {
                $this->frame_error_tips('该信息不存在！');
            } else {
                $this->assign('foot_menu_info', $foot_menu_info);
            }
            //小程序列表
            $wxapp_list = M('Wxapp_other')->field(true)->select();
            $this->assign('wxapp_list',$wxapp_list);
            $this->display();
        }
    }

    public function foot_menu_del(){
        $where['id'] = $_POST['id'];
        $now_adver = M('House_village_footer_menu')->field(true)->where($where)->find();
        if(!$now_adver){
            $this->error('数据不存在!');
        }
        $village_id = $this->house_session['village_id'];
        //todo 小区复制平台广告 生成属于小区的广告
        $rel=D('House_link')->get_filter_village_id($village_id,$now_adver,'del');
        if(!empty($rel)){
            $result=  M('House_village_footer_menu')->where(['id'=>$now_adver['id']])->data(['filter_village_ids'=>implode(',',$rel)])->save();
        }else{
            $result = M('House_village_footer_menu')->where($where)->delete();
        }
        if($result){
            $this->success('删除成功!');
        }else{
            $this->error('删除失败!');
        }
    }

    public function foot_menu_list(){
        $assign_type=D('House_visualize_assign')->house_visualize_assign($this->house_session['village_id'],7);
        $where['status']=['in',[0,1,2]];
        $village_id=$this->house_session['village_id'];
        $where['_string'] =' (village_id = '.$village_id.')';
        if(!$assign_type['type_0']){
            $where['_string'] .= '  or ((village_id =0) and (filter_village_ids is null OR !FIND_IN_SET('.$village_id.', filter_village_ids ) ))';
        }
        $home_menu_list = M('House_village_footer_menu')->where($where)->order('village_id asc,sort desc,id asc')->select();
        $this->assign('assign_type',$assign_type);
        $this->assign('home_menu_list',$home_menu_list);
        $this->display();
    }




    //更换底部导航颜色
    public function change_footer_color()
    {

        $name = $_POST['name'];


        if ($name == 'app_footer_bgcolor') {
            $condition = $name;
            $value = $_POST['color'];

        }
        if ($name == 'app_footer_txtactivecolor') {
            $condition = $name;
            $value = $_POST['color'];

        }

        if ($name == 'app_footer_txtcolor') {
            $condition = $name;
            $value = $_POST['color'];

        }
        $now_village = D('House_village')->where(array('village_id'=>$this->house_session['village_id']))->data(array($name=>$value))->save();
        if($now_village){
            echo json_encode(array('status'=>1));
        }else{
            echo json_encode(array('status'=>0));
        }


    }



    public function see_qrcode() {
        $village_id = $this->village_id;

        $village_info = M('House_village')->where(['village_id'=>$this->village_id])->find();

        $weixin = D('Weixin_bind')->where(['mer_id'=>$village_info['property_id'], 'service_type_info'=>2, 'type'=>2])->find();
        if($weixin) {
            $weixin_url = $this->config['site_url'] . '/index.php?c=Recognition&a=get_weixin_qrcode&qrcode_id=' . ($village_id + 1300000000) . '&village_id=' . $village_id;
        }else{
            $weixin_url = $this->config['site_url'] . '/index.php?c=Recognition&a=get_tmp_qrcode&qrcode_id=' . ($village_id + 1300000000) . '&village_id=' . $village_id;
        }
        $this->assign('weixin_url', $weixin_url);

        if ($this->config['house_manage_wxapp_appid'] && $this->config['house_manage_wxapp_appsecret']) {
            $wxapp_url = $this->config['site_url'] . "/index.php?g=Index&c=Recognition_wxapp&a=see_qrcode&type=community&img=1&id=" . $this->village_id;
            $this->assign('wxapp_url', $wxapp_url);
        }
        if ($this->config['hideAppDownQrCode']!=1) {
            $app_down_url = $this->config['site_url'] . "/topic/app_wap_house_manager.html";
            $app_url = $this->config['site_url'] . "/index.php?c=Recognition&a=get_own_qrcode&qrCon={$app_down_url}";
            $this->assign('app_url', $app_url);
        }
	    $this->display();
    }

    /**
     * 查询小区列表
     * @author:zhubaodi
     * @date_time: 2021/7/1 13:43
     */
    public function house_village_list()
    {
        $order_string = '`village_id` DESC';
      //  $village_id = $_GET['village_id'];
        $village_id=  $this->house_session['village_id'];
        $condition_house_village = array('status'=>array('neq',5));
        if (!empty($village_id)){
            $condition_house_village['village_id'] = array('neq',$village_id);
        }
        //搜索
        if (!empty($_GET['village_name'])) {
            $condition_house_village['village_name'] = array('like', '%' . $_GET['village_name'] . '%');
        }
       /* if ($condition_house_village) {
            $count = 10000;
        } else {
            $count = 10;
        }*/
        $count = 10;

        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if ($now_area['area_type'] == 3) {
                $area_index = 'area_id';
            } elseif ($now_area['area_type'] == 2) {
                $area_index = 'city_id';
            } elseif ($now_area['area_type'] == 1) {
                $area_index = 'province_id';
            }
            $this->assign('admin_area', $now_area['area_type']);
            $condition_house_village[$area_index] = $this->system_session['area_id'];
        }

        if ($_GET['city_idss'] && $this->config['many_city']) {
            $condition_house_village['city_id'] = $where['city_id'] = $_GET['city_idss'];
        }

        if ($this->config['many_city'] && $_GET['province_idss']) {
            $condition_house_village['province_id'] = $where['province_id'] = $_GET['province_idss'];
        }

        if ($_GET['area_id']) {
            $condition_house_village['area_id'] = $where['area_id'] = $_GET['area_id'];
        }
        if ($_GET['street_idss']) {
            $condition_house_village['street_id'] = $where['street_id'] = $_GET['street_idss'];
        }
        if ($_GET['community_idss']) {
            $condition_house_village['community_id'] = $where['community_id'] = $_GET['community_idss'];
        }

        $database_house_village = D('House_village');
        $count_village = $database_house_village->where($condition_house_village)->count();
        import('@.ORG.house_page');
        $p = new Page($count_village, $count);
        $field = "village_id,village_name,village_address,(CASE WHEN (`status`='0') then 1 WHEN (`status` = 1) then 0 ELSE 2 END) as `t_sort`";
        $village_list = $database_house_village->field($field)->where($condition_house_village)->order($order_string)->limit($p->firstRow . ',' . $p->listRows)->select();
        //   print_r($village_list);exit;
        $this->assign('village_list', $village_list);
        $pagebar = $p->show();
        $this->assign('pagebar', $pagebar);
        $this->assign('village_id', $village_id);
        $this->display();
    }

    /**
     * 复制其他小区信息
     * @author:zhubaodi
     * @date_time: 2021/7/1 14:01
     */
    public function copy_village_info(){
        $village_id=  $this->house_session['village_id'];
        $village_copy=$_POST['village_id'];
        if (empty($village_copy)||empty($village_id)){
            $this->frame_error_tips('小区id不能为空！');
        }
       /* print_r($village_id);
        print_r($village_copy);exit;*/
        //查询小区底部导航的三个颜色信息
        $village_copy_info = D('House_village')->where(array('village_id'=>$village_copy))->find();
        $village_info = D('House_village')->where(array('village_id'=>$village_id))->find();

        if (empty($village_copy_info)||empty($village_info)){
            $this->frame_error_tips('小区信息不存在！');
        }
        $jsdhwy_new_wap_index=D('Config_customization')->getWapIndexJsdhwyUi();
        
        //查询小区首页功能导航信息
        $database_house_village_nav = D('House_village_nav');
        $where=array();
        $where['village_id'] = $village_copy;
        $result_copy_list = $database_house_village_nav->house_village_nav_page_list1($where , true , 'sort desc');
        $where1=array();
        $where1['village_id'] = $village_id;
        $result_copy_list1 = $database_house_village_nav->house_village_nav_page_list1($where1 , true , 'sort desc');

        fdump_api([$result_copy_list,$where,$result_copy_list1,$where1],'copy_village_info_0525',1);
        //查询小区首页广告位列表信息
        $database_adver = D('House_adver');
        $adver_list = $database_adver->field(true)->where(array('village_id'=>$village_copy))->order('`id` DESC')->select();
        $adver_list1 = $database_adver->field(true)->where(array('village_id'=>$village_id))->order('`id` DESC')->select();

        //查询小区底部导航列表信息
        $home_menu_list = M('House_village_footer_menu')->where(array('village_id'=>$village_copy))->order('sort desc,id ASC')->select();
        $home_menu_list1 = M('House_village_footer_menu')->where(array('village_id'=>$village_id))->order('sort desc,id ASC')->select();

       M()->startTrans();
        $village_data=[
            'app_footer_txtcolor'=>$village_copy_info['app_footer_txtcolor'],
            'app_footer_txtactivecolor'=>$village_copy_info['app_footer_txtactivecolor'],
            'app_footer_bgcolor'=>$village_copy_info['app_footer_bgcolor'],
        ];
        //复制小区底部导航的三个颜色信息
        $save_village_info = D('House_village')->where(array('village_id'=>$village_id))->data($village_data)->save();

        //复制小区首页功能导航信息
        if (!empty($result_copy_list1)&&count($result_copy_list1['result']['list'])>=1){
            $del_result_info =$database_house_village_nav->where(array('village_id'=>$village_id))->delete();
        }else{
            $del_result_info =1;
        }
        $save_result_info=array();
        $nowtime=time();
        $house_village_nav_moduleDb = D('House_village_nav_module');
        if($jsdhwy_new_wap_index>0){
            $whereModuleArr=array('village_id'=>$village_id,'is_community_platform'=>'0');
            $house_village_nav_moduleDb->saveData($whereModuleArr,array('del_time'=>$nowtime));
            $whereCopyModuleArr=array('village_id'=>$village_copy,'is_community_platform'=>'0','del_time'=>array('lt',1));
            $copyNavModule=$house_village_nav_moduleDb->getDataList($whereCopyModuleArr);
            if($copyNavModule){
                foreach ($copyNavModule as $nm){
                    $nm['village_id']=$village_id;
                    $nm['add_time']=$nowtime;
                    unset($nm['id']);
                    $save_result_info[]= $house_village_nav_moduleDb->addData($nm);
                }
            }
        }
        if (!empty($result_copy_list)&&count($result_copy_list['result']['list'])>=1){
            foreach ($result_copy_list['result']['list'] as $v){
                $v['village_id']=$village_id;
                $v['url']=str_replace("village_id=".$village_copy,"village_id=".$village_id,$v['url']);
                if(strpos($v['url'],'area_street_id='.$village_copy_info['community_id']) !== false){
                    if (empty($village_info['community_id'])){
                        continue;
                    }else{
                        $v['url']=str_replace("area_street_id=".$village_copy_info['community_id'],"area_street_id=".$village_info['community_id'],$v['url']);
                    }
                }
                if(strpos($v['url'],'area_street_id='.$village_copy_info['street_id']) !== false){
                    if (empty($village_info['street_id'])){
                        continue;
                    }else{
                        $v['url']=str_replace("area_street_id=".$village_copy_info['street_id'],"area_street_id=".$village_info['street_id'],$v['url']);
                    }
                }
                unset($v['id']);
               $save_result_info[] = $database_house_village_nav->add($v);
            }
        }else{
            $save_result_info=[1];
        }


        //复制小区首页广告位列表信息
        if (!empty($adver_list1)) {
            $del_adver_info = $database_adver->where(array('village_id' => $village_id))->delete();
        }else{
            $del_adver_info=1;
        }
        if (!empty($adver_list)){
            foreach ($adver_list as $v){
                $v['village_id']=$village_id;
                $v['url']=str_replace("village_id=".$village_copy,"village_id=".$village_id,$v['url']);
                if(strpos($v['url'],'area_street_id='.$village_copy_info['community_id']) !== false){
                    if (empty($village_info['community_id'])){
                        continue;
                    }else{
                        $v['url']=str_replace("area_street_id=".$village_copy_info['community_id'],"area_street_id=".$village_info['community_id'],$v['url']);
                    }
                }
                if(strpos($v['url'],'area_street_id='.$village_copy_info['street_id']) !== false){
                    if (empty($village_info['street_id'])){
                        continue;
                    }else{
                        $v['url']=str_replace("area_street_id=".$village_copy_info['street_id'],"area_street_id=".$village_info['street_id'],$v['url']);
                    }
                }
                unset($v['id']);
                $save_adver_info[] = $database_adver->add($v);
            }
        }else{
            $save_adver_info=[1];
        }


        //复制小区首页广告位列表信息
        if (!empty($home_menu_list1)) {
            $del_menu_info = M('House_village_footer_menu')->where(array('village_id' => $village_id))->delete();
        }else{
            $del_menu_info=1;
        }
        if (!empty($home_menu_list)){
            foreach ($home_menu_list as $v){
                $v['village_id']=$village_id;
                $v['url']=str_replace("village_id=".$village_copy,"village_id=".$village_id,$v['url']);
                if(strpos($v['url'],'area_street_id='.$village_copy_info['community_id']) !== false){
                    if (empty($village_info['community_id'])){
                        continue;
                    }else{
                        $v['url']=str_replace("area_street_id=".$village_copy_info['community_id'],"area_street_id=".$village_info['community_id'],$v['url']);
                    }
                }
                if(strpos($v['url'],'area_street_id='.$village_copy_info['street_id']) !== false){
                    if (empty($village_info['street_id'])){
                        continue;
                    }else{
                        $v['url']=str_replace("area_street_id=".$village_copy_info['street_id'],"area_street_id=".$village_info['street_id'],$v['url']);
                    }
                }
                unset($v['id']);
                $save_menu_info[] = M('House_village_footer_menu')->add($v);
            }
        }else{
            $save_menu_info=[1];
        }
        
        if (empty($save_result_info)||empty($del_result_info)||empty($save_adver_info)||empty($del_adver_info)||empty($save_menu_info)||empty($del_menu_info)){
            M()->rollback();
            echo json_encode(array('status'=>0));
        }else{
            M()->commit();
            echo json_encode(array('status'=>1));
        }

    }

    //保存订单退款（作废）审核设置
    public function order_check_set()
    {
        $village_id = $this->village_id;
        $property_id = $this->village['property_id'];
        if (IS_POST) {
            $order_check_open = isset($_POST['order_check_open']) ? $_POST['order_check_open'] : 0;
            $order_check_open = intval($order_check_open);
            $check_level = isset($_POST['check_level']) ? $_POST['check_level'] : '';
            $xtype = isset($_POST['xtype']) ? $_POST['xtype'] : 'order_refund_check';
            $xtype = trim($xtype);
            $xtype = htmlspecialchars($xtype, ENT_QUOTES);
            $order_modify_discount= isset($_POST['order_modify_discount']) && $_POST['order_modify_discount'] ? $_POST['order_modify_discount'] : '';
            $savedata = array('village_id' => $village_id, 'property_id' => $property_id);
            $savedata['xtype'] = $xtype;
            if ($xtype == 'order_refund_check') {
                $savedata['tname'] = '订单退款（作废）审核设置';
            }else if ($xtype == 'order_modify_check') {
                $savedata['tname'] = '订单修改费用审核设置';
            }else if ($xtype == 'order_servicetime_check') {
                $savedata['tname'] = '房间计费时间修改审核设置';
            }else if ($xtype == 'order_paytime_check') {
                $savedata['tname'] = '已缴账单修改支付时间审核设置';
            }
            $set_datas = array();
            if ($order_check_open == 1 && empty($check_level)) {
                $this->error('请至少选择设置一项审批人！');
            }
            $is_aihorse_customized = D('Config_customization')->getIsAihorseCustomized();
            $isFuncKaidalaicnSpecial=D('Config_customization')->getfuncKaidalaicnSpecial();
            if($xtype == 'order_modify_check' && ($is_aihorse_customized||$isFuncKaidalaicnSpecial)){
                $order_modify_discount=!empty($order_modify_discount) && is_array($order_modify_discount) ? json_encode($order_modify_discount,JSON_UNESCAPED_UNICODE):'';
                M('House_village_info')->where(array('village_id' => $village_id))->data(array('order_modify_discount'=>$order_modify_discount))->save();
            }
            foreach ($check_level as $vv) {
                $set_datas[$vv['level_v']] = $vv;
            }
            ksort($set_datas);
            if (count($set_datas) > 1) {
                $chooseWids = [];
                foreach ($check_level as $key => $level) {
                    $num = $key + 1;
                    if (!isset($level['level_wid']) || !$level['level_wid']) {
                        $this->error("请选择第{$num}项审批人！");
                        break;
                    }
                    if (in_array($level['level_wid'], $chooseWids)) {
                        $this->error("第{$num}项审批人已经选择过了，请选择其他人！");
                        break;
                    }
                    $chooseWids[] = $level['level_wid'];
                }
            }
            $savedata['is_open'] = $order_check_open;
            $savedata['set_datas'] = json_encode($set_datas, JSON_UNESCAPED_UNICODE);
            D('House_village_checkauth_set')->save_set($savedata);
            $models = '\app\common\model\service\CommonSystemLogService/addLaterLogInQueue';
            $logData=array('tbname'=>'小区设置-基本信息','table'=>'house_village_checkauth_set','client'=>'小区后台','trigger_path'=>'基本信息->'.$savedata['tname'],'trigger_type'=>'update','village_id'=>$village_id,'property_id'=>0);
            $logData['login_uid']=$this->login_uid;
            $logData['login_role']=$this->login_role;
            $logData['newData']=$_POST;
            $logData['oldData']='';
            $param=array('logData'=>$logData);
            $result = invoke_v20_service($models, $param);
            $this->success('设置成功！');
        }
    }
    /**
     * 绑定指定工作人员
     * @author: liukezhu
     * @date : 2022/2/9
     */
    public function bind_worker(){
        $village_id=$this->village_id;
        $village_info = M('House_village_info')->where(array('village_id'=>$village_id))->field('info_id,repair_wid,advise_wid')->find();
        $workers = D('House_worker')->field(true)->where(array('status' => 1,'is_del' => 0, 'village_id' => $village_id))->order('type asc')->select();
        $this->assign('workers', $workers);
        $this->assign('village_info', $village_info);
        $this->display();
    }

     /**
      * 猜你喜欢页面及信息
     * User: zhanghan
     * Date: 2022/1/23
     * Time: 12:17
     */
    public function index_guess_like(){

        $source = remove_xss($_GET['source']);
        $village_id = $this->house_session['village_id'];
        if($source == 'sys'){
            $village_id = 0;
        }

        $assign_type=D('House_visualize_assign')->house_visualize_assign($this->house_session['village_id'],10);

        // 获取列表
        $condition_guess_like = [];
        $database_guess_like  = D('House_village_guess_like');
        $condition_guess_like['_string'] =' village_id = '.$village_id;
        if(!$assign_type['type_0']){
            $condition_guess_like['_string'] .= '  or village_id = 0';
        }
        $condition_guess_like['type'] = 0;
        // 总条数
        $adver_count = $database_guess_like->where($condition_guess_like)->count();

        // 分页
        import('@.ORG.merchant_page');
        $p = new Page($adver_count,10,'page');
        // 列表
        $guess_like = $database_guess_like->field(true)->where($condition_guess_like)->order('sort DESC,id DESC')->limit($p->firstRow.','.$p->listRows)->select();
        $result['list'] = $guess_like;
        $result['pagebar'] = $p->show();

        $village_nav = D('House_village_info')->where(array('village_id'=>$this->house_session['village_id']))->field('is_guess_like')->find();
        $this->assign('assign_type',$assign_type);
        $this->assign('village_nav',$village_nav);
        $this->assign('result',$result);
        $this->display();
    }

    /**
     * 添加猜你喜欢
     * User: zhanghan
     * Date: 2022/1/23
     * Time: 12:18
     */
    public function index_guess_add(){
        if(IS_POST){
            $title = remove_xss($_POST['title']);
            $store_type = remove_xss($_POST['store_type']);
            $sort = remove_xss($_POST['sort']);
            $status = remove_xss($_POST['status']);
            $source = remove_xss($_POST['source']);

            $data = [];
            $data['village_id'] = $this->house_session['village_id'];
            if($source == 'sys'){
                $data['village_id'] = 0;
            }
            $data['title'] = $title;
            $data['store_type'] = $store_type;
            $data['sort'] = $sort;
            $data['status'] = $status;
            $data['create_at'] = time();
            $data['update_at'] = time();
            $database_guess_like  = D('House_village_guess_like');
            if($id = $database_guess_like->data($data)->add()){
                exit('<html><head><script>window.top.msg(1,"添加成功",true,2);window.parent.closeiframe();</script></head></html>');
            }else{
                exit('<html><head><script>window.top.msg(0,"添加失败！请重试~",true,2);window.parent.closeiframe();</script></head></html>');
            }
        }

        $this->display();
    }

    /**
     * 编辑猜你喜欢
     * User: zhanghan
     * Date: 2022/1/23
     * Time: 13:27
     */
    public function index_guess_edit(){
        if(IS_POST){
            $database_guess_like = D('House_village_guess_like');

            $condition_guess = [];
            $condition_guess['id'] = remove_xss($_POST['id']);
            $guess_like = $database_guess_like->field('id')->where($condition_guess)->find();
            if(!$guess_like){
                exit('<html><head><script>window.top.msg(0,"数据不存在",true,2);window.parent.closeiframe();</script></head></html>');
            }

            $title = remove_xss($_POST['title']);
            $store_type = remove_xss($_POST['store_type']);
            $sort = remove_xss($_POST['sort']);
            $status = remove_xss($_POST['status']);

            $data = [];
            $data['title'] = $title;
            $data['store_type'] = $store_type;
            $data['sort'] = $sort;
            $data['status'] = $status;
            if($id = $database_guess_like->where($condition_guess)->data($data)->save()){
                exit('<html><head><script>window.top.msg(1,"添加成功",true,2);window.parent.closeiframe();</script></head></html>');
            }else{
                exit('<html><head><script>window.top.msg(0,"添加失败！请重试~",true,2);window.parent.closeiframe();</script></head></html>');
            }
        }
        $id = remove_xss($_GET['id']);
        if(!$id){
            exit('<html><head><script>window.top.msg(0,"宫格参数错误",true,2);window.parent.closeiframe();</script></head></html>');
        }

        $condition_guess_like = [];
        $database_guess_like = D('House_village_guess_like');
        $condition_guess_like['id'] = $id;
        $guess_like = $database_guess_like->where($condition_guess_like)->find();
        if(empty($guess_like)){
            $this->frame_error_tips('该宫格广告不存在！');
        }

        $this->assign('guess_like',$guess_like);
        $this->display();
    }

    /**
     * 删除猜你喜欢
     * User: zhanghan
     * Date: 2022/1/23
     * Time: 13:52
     */
    public function index_guess_del(){
        $database_guess_like = D('House_village_guess_like');

        $condition_guess = [];
        $condition_guess['id'] = remove_xss($_POST['id']);
        $guess_like = $database_guess_like->field('id')->where($condition_guess)->find();
        if(!$guess_like){
            exit('<html><head><script>window.top.msg(0,"数据不存在",true,2);window.parent.closeiframe();</script></head></html>');
        }

        if($database_guess_like->where($condition_guess)->delete()){
            $this->success('删除成功');
        }else{
            $this->error('删除失败！请重试~');
        }
    }

    /**
     * 猜你喜欢管理店铺页面
     * User: zhanghan
     * Date: 2022/1/23
     * Time: 14:03
     */
    public function index_guess_store(){
        $id = remove_xss($_GET['id']);
        if(!$id){
            exit('<html><head><script>window.top.msg(0,"宫格参数错误",true,2);window.parent.closeiframe();</script></head></html>');
        }

        $this->assign('id',$id);
        $this->display();
    }

    /**
     * 猜你喜欢管理店铺列表
     * User: zhanghan
     * Date: 2022/1/22
     * Time: 16:06
     */
    public function index_guess_list(){
        $id = remove_xss($_GET['id']);
        $page = remove_xss($_POST['page']);
        $limit = remove_xss($_POST['limit']);
        $database_guess_like  = D('House_village_guess_like');
        $database_guess_like_store  = D('House_village_guess_like_store');

        $condition_guess = [];
        $condition_guess['id'] = $id;
        $guess_like = $database_guess_like->where($condition_guess)->field('store_type')->find();
        if(empty($guess_like)){
            $return['code'] = 1;
            $return['msg'] = '该宫格广告不存在!';
            exit(json_encode($return));
        }

        $sort = [];
        $store_id = [];
        // 查询已有关联商品ID
        $guess_list = $database_guess_like_store->where(array('guess_like_id' => $id))->field('store_id,sort')->select();
        if(!empty($guess_list)){
            $store_id = array_column($guess_list,'store_id');
            foreach ($guess_list as $val){
                $sort[$val['store_id']] = $val['sort'];
            }
        }

        // 分页
        import('@.ORG.merchant_page');

        $where = [];
        $where['ss.close_old_store'] = 0;
        $where['s.status'] = 1;
        $where['s.is_business_open'] = 1;
        // 查询店铺 外卖店铺
        if($guess_like['store_type'] == 0){
            $count = D('Merchant_store_shop as ss')
                ->join('LEFT JOIN pigcms_merchant_store as s on ss.store_id=s.store_id')
                ->where($where)
                ->count();
            $list = D('Merchant_store_shop as ss')
                ->join('LEFT JOIN pigcms_merchant_store as s on ss.store_id=s.store_id')
                ->field('s.name,s.phone,s.store_id')
                ->where($where)
                ->order('ss.store_id DESC')
                ->limit(($page-1)*$limit.','.$limit)
                ->select();
        }else{
            // 非外卖店铺
            $count = D('pigcms_merchant_store as s')
                ->where(array('status' => 1,'is_business_open' => 1))
                ->count();
            $list = D('pigcms_merchant_store as s')
                ->field('name,phone,store_id')
                ->where(array('status' => 1,'is_business_open' => 1))
                ->order('store_id DESC')
                ->limit(($page-1)*$limit.','.$limit)
                ->select();
        }
        if(!empty($list)){
            foreach ($list as $key => $value){
                $list[$key]['is_related'] = 0;
                if(!empty($store_id) && in_array($value['store_id'],$store_id)){
                    $list[$key]['is_related'] = 1;
                }
                $list[$key]['sort'] = isset($sort[$value['store_id']]) ? $sort[$value['store_id']] : 0;
            }
        }

        $return['code'] = 0;
        $return['msg'] = '店铺列表';
        $return['data'] = $list;
        $return['count'] = $count;
        exit(json_encode($return));
    }

    /**
     * 关联和取消关联
     * User: zhanghan
     * Date: 2022/1/22
     * Time: 17:56
     */
    public function relateStore(){
        $id = remove_xss($_GET['id']);
        $store_id = remove_xss($_POST['store_id']);
        $type = remove_xss($_POST['type']);
        $sort = remove_xss($_POST['sort']);

        $database_guess_like_store  = D('House_village_guess_like_store');

        $temp = [
            'store_id' => $store_id,
            'guess_like_id' => $id
        ];
        // 添加
        if($type == 'add'){
            $temp['create_at'] = time();
            $temp['update_at'] = time();
            $temp['sort'] = $sort;
            $res = $database_guess_like_store->data($temp)->add();
        }else{
            $res = $database_guess_like_store->where($temp)->delete();
        }
        if($res){
            exit(json_encode(array('status'=>0,'msg'=>'操作成功')));
        }else{
            exit(json_encode(array('status'=>1,'msg'=>'操作失败')));
        }
    }

    /**
     * 底部模板导航
     * User: zhanghan
     * Date: 2022/1/23
     * Time: 17:36
     */
    public function foot_menu_template(){
        $village_id = $this->house_session['village_id'];
        // 底部导航模板
        $footer_config = D('House_village_info');
        $footer_template = $footer_config->where(array('village_id' => $village_id))->field('template_floor')->find();
        $template_id = (int)$footer_template['template_floor'];
        $this->assign('template_id',$template_id);
        $data = [
            [
                'id' => 1,
                'template' => '模板一',
                'image' => $this->config['site_url'].'/static/images/house/template1.png'
            ],
            [
                'id' => 2,
                'template' => '模板二',
                'image' => $this->config['site_url'].'/static/images/house/template2.png'
            ]
        ];
        $jsdhwy_new_wap_index=D('Config_customization')->getWapIndexJsdhwyUi();
        if($jsdhwy_new_wap_index>0){
            $data[]=array( 'id' => 3, 'template' => '模板三', 'image' => $this->config['site_url'].'/static/images/house/template3.png');
        }
        $this->assign('template',$data);
        $this->display();
    }

    /**
     * 设置底部、头部导航模板
     * User: zhanghan
     * Date: 2022/1/23
     * Time: 17:43
     */
    public function set_footer_template(){
        $type = remove_xss($_POST['type']);
        $template = remove_xss($_POST['template']);
        $template=intval($template);
        $village_id = $this->house_session['village_id'];

        $where = [];
        $where['village_id'] = $village_id;

        $village_info = D('House_village_info');
        $village_info_template = $village_info->where($where)->find();
        if(empty($village_info_template)){
            exit(json_encode(array('status'=>1,'msg'=>'查询不到相应的小区信息')));
        }else{
            // 1是顶部 0是底部
            if($type == 1){
                if($this->yuanJino2oCustomized>0 && !in_array($template,array(1,5))){
                    $template=1;
                }
                $data = [
                    'template_top' => $template,
                    'update_at' => time()
                ];
            }else{
                $data = [
                    'template_floor' => $template,
                    'update_at' => time()
                ];
            }
            
            $res = $village_info->where($where)->data($data)->save();
        }
        if($res){
            exit(json_encode(array('status'=>0,'msg'=>'设置成功。')));
        }else{
            exit(json_encode(array('status'=>1,'msg'=>'设置失败！请重试。')));

        }
    }


    //工作量统计图
    public function get_work_order_stack(){
        if(IS_POST){
            $village_id = $this->village_id;
            $date_type=remove_xss($_POST['date_type']);
            $data_type=remove_xss($_POST['data_type']);
            $list=D('House_new_repair_works_order_timely')->get_work_order_stack($village_id,$date_type,$data_type,$this->house_admin_wid);
            if(!$list['error']){
                $list['data']=['xAxisData'=>[],'seriesData'=>[],'groupData'=>[]];
            }
            $this->success($list);
        }
    }

    //部门分类
    public function get_work_order_class(){
        $group_id = remove_xss($_GET['group_id']);
        $date_type = remove_xss($_GET['date_type']);
        $data_type = remove_xss($_GET['data_type']);
        $list=D('House_new_repair_works_order_timely')->get_tissue_user($this->village_id,$group_id,$this->house_admin_wid);
        $this->assign('data_type',$data_type);
        $this->assign('date_type',$date_type);
        $this->assign('group_id',$group_id);
        $this->assign('list',$list);
        $this->display();
    }

    //获取部门分类饼状图
    public function get_work_order_simple(){
        if (IS_POST){
            $village_id = $this->village_id;
            $param=[
                'village_id'=>$village_id,
                'group_id'=>remove_xss($_POST['group_id']),
                'name'=>remove_xss(trim($_POST['group_name'])),
                'date_type'=>remove_xss($_POST['date_type']),
                'personnel_id'=>remove_xss($_POST['personnel_id']),
                'is_tab'=>remove_xss($_POST['is_tab']),
                'start_time'=>remove_xss($_POST['start_time']) ? strtotime(remove_xss($_POST['start_time']).' 00:00:00') : 0,
                'end_time'=>remove_xss($_POST['end_time']) ? strtotime(remove_xss($_POST['end_time']).' 23:59:59') : 0,
            ];
            $list=D('House_new_repair_works_order_timely')->get_work_order_simple($param);
            $this->success($list);
        }
    }

    //新版工单绑定指定工作人员
    public function new_repair_bind_worker(){
        $village_id=$this->village_id;
        if (IS_POST){
            if(!isset($_POST['repair_wid']) || empty($_POST['repair_wid'])){
                $this->success(['status'=>0,'msg'=>'请选择指定工作人员']);
            }
            $res=D('House_new_repair_cate')->cate_bind_worker($village_id,$_POST['repair_wid']);
            if(!$res['error']){
                $this->success(['status'=>0,'msg'=>$res['msg']]);
            }else{
                $this->success(['status'=>1,'msg'=>$res['msg']]);
            }
        }else{
            $list=D('House_new_repair_cate')->get_cate_list($village_id);
            $this->assign('res', $list);
            $this->display();
        }
    }
}
?>
