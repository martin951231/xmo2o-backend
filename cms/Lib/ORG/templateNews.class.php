<?php
class templateNews{
	

	public $thisWxUser;
	
	public $appid;
	
	public $appsecret;

	public function __construct($appid = null, $appsecret = null){
		
		$this->appid = $appid;
		$this->appsecret = $appsecret;
	}

    public function sendTempMsg($tempKey, $dataArr, $mer_id = 0,$property_id = 0,$type=0,$param=[],$callback = null)
    {
        $uniqueArr = ['tempKey' => $tempKey, 'dataArr' => $dataArr, 'mer_id' => $mer_id, 'property_id' => $property_id, 'type' => $type, 'param' => $param];
        $cache_key = md5(json_encode($uniqueArr, JSON_UNESCAPED_UNICODE));
        $planTime = S($cache_key);
        fdump_api(['uniqueArr' => $uniqueArr, 'cache_key' => $cache_key, 'planTime' => $planTime], 'weiXinTemp/sendTempMsg',1);
        if ($planTime > 100) {
            return true;
        }
        $oldTempMsg='';
        $jumpAbChina = false;//是否跳转到农行
        if(isset($dataArr['oldTempMsg']) && !empty($dataArr['oldTempMsg'])){
            $oldTempMsg=$dataArr['oldTempMsg'];
            unset($dataArr['oldTempMsg']);
        }
        if(isset($dataArr['oldTempMsg'])){
            unset($dataArr['oldTempMsg']);
        }

        //拦截下增加新版分类参数 new_info（订单支付通知）
        $dataArr = $this->wechatClassificationTemplateInterceptor($tempKey, $dataArr);

        $dbinfo = M('Tempmsg')->where(array('tempkey' => $tempKey, 'mer_id' => '0', 'property_id' => '0'))->order('status DESC,id ASC')->find();
        if((empty($dbinfo)||empty($dbinfo['tempid'])) && !empty($oldTempMsg) && is_array($oldTempMsg) && isset($oldTempMsg['tempKey'])&& !empty($oldTempMsg['tempKey']) && isset($oldTempMsg['dataArr'])){
            //新模板未配置 用老模板
            $tempKey=$oldTempMsg['tempKey'];
            $dataArr=$oldTempMsg['dataArr'];
            $dbinfo = M('Tempmsg')->where(array('tempkey' => $tempKey, 'mer_id' => '0', 'property_id' => '0'))->order('status DESC,id ASC')->find();
        }
        if (isset($dataArr['new_info']['tempKey']) || $dbinfo['is_category'] == 1) { //新版类目模板适配
            //兼容新类目模板，有新的用新的
            if ($mer_id > 0) {
                $new_dbinfo =  M('Tempmsg')->where(['tempkey' => $dataArr['new_info']['tempKey'], 'mer_id' => $mer_id, 'property_id' => 0, 'status' => 1])->find();
            } 
            /*
            if ($property_id > 0) {
                $new_dbinfo =  M('Tempmsg')->where(['tempkey' => $dataArr['new_info']['tempKey'], 'mer_id' => 0, 'property_id' => $property_id, 'status' => 1])->find();
            }*/
            //商家自有模板不存在则读取平台
            if (empty($new_dbinfo)) {
                $new_dbinfo =  M('Tempmsg')->where(['tempkey' => $dataArr['new_info']['tempKey'], 'mer_id' => 0, 'property_id' => 0, 'status' => 1])->find();
            }
            
            if ($new_dbinfo) {
                $newTempId = $new_dbinfo['tempid'];
                $tempKey = $dataArr['new_info']['tempKey'];
                $dataArr = $this->getDataArr($tempKey, $dataArr, 0, $mer_id);
                $dbinfo["tempid"] = $dataArr['tempid'];
                $dataArr = $dataArr['ary'];
                $dbinfo = $new_dbinfo;
            }
        }

		fdump_api([__LINE__,'dataArr'=>$dataArr,'mer_id'=>$mer_id,'tempKey'=>$tempKey,'property_id'=>$property_id],'weiXinTemp/sendTempMsg',1);
		if ($tempKey == 'TM00251') {
            if (!($dbinfo['status'] && $dbinfo['tempid'])){
                if (($dbinfo = M('Tempmsg')->where(array('tempkey' => 'OPENTM200772305', 'mer_id' => $mer_id))->order('status DESC,id ASC')->find()) && $dbinfo['status'] && $dbinfo['tempid']) {
                    $tempKey = 'OPENTM200772305';
					$dataArr['keyword1'] = $dataArr['toName'];
					$dataArr['keyword2'] = $dataArr['gift'];
					$dataArr['keyword3'] = $dataArr['time'];
					unset($dataArr['toName'],$dataArr['gift'],$dataArr['time']);
                }
                elseif(($dbinfo = M('Tempmsg')->where(array('tempkey' => 'OPENTM200772305', 'mer_id' => 0))->order('status DESC,id ASC')->find()) && $dbinfo['status'] && $dbinfo['tempid']){
                	$tempKey = 'OPENTM200772305';
					$dataArr['keyword1'] = $dataArr['toName'];
					$dataArr['keyword2'] = $dataArr['gift'];
					$dataArr['keyword3'] = $dataArr['time'];
					unset($dataArr['toName'],$dataArr['gift'],$dataArr['time']);
                }
            }
		}
        if($tempKey == 'TM01008'){
            //todo 新版模板消息
            if(isset($dataArr['new_info'])){
                //兼容新类目模板，有新的用新的
                if($dataArr['new_info'] && $dataArr['new_info']['type']==1){//缴费成功通知
                    $newTempId = M('Tempmsg')->where(['tempkey'=>'42822','status'=>1,'mer_id' => '0', 'property_id' => '0'])->getField('tempid');
                    $tempKey = $newTempId ? '42822' : $tempKey;
                }elseif($dataArr['new_info'] && $dataArr['new_info']['type']==0){//提醒缴费
                    $newTempId = M('Tempmsg')->where(['tempkey'=>'42804','status'=>1,'mer_id' => '0', 'property_id' => '0'])->getField('tempid');
                    $tempKey = $newTempId ? '42804' : $tempKey;
                    $jumpAbChina = true;
                }else{
                    unset($dataArr['new_info']);
                }
            }
			fdump_api(['dataArr'=>$dataArr,$tempKey],'weiXinTemp/sendTempMsg',1);
            if($tempKey != 'TM01008'){
                //类目模板
                $dbinfo = M('Tempmsg')->where(array('tempkey' => $tempKey, 'mer_id' => '0', 'property_id' => '0'))->order('status DESC,id ASC')->find();
//                $property_id=$dbinfo['property_id'];
                $dataArr = $this->getDataArr($tempKey,$dataArr,0,$mer_id);
                $dbinfo["tempid"] = $dataArr['tempid'];
                $dataArr = $dataArr['ary'];
            }else{
                //历史模板
                $keynote2=str_replace('(','',$dataArr['keynote2']);
                $keynote2=str_replace(')','',$keynote2);
                $keynote2=trim($keynote2);
                $keynote1=str_replace('(','',$dataArr['keynote1']);
                $keynote1=str_replace(')','',$keynote1);
                $keynote1=trim($keynote1);
                $remark=$dataArr['remark'];
                $remarkArr=explode('\n',$remark);
                $feeStr='';
                $keynote1Str='';
                if(strpos($dataArr['first'],'业主')!==false ){
                    $keynote1Str='业主缴费成功提醒';
                }
                foreach ($remarkArr as $rvv){
                    if(strpos($rvv,'金额')!==false){
                        $feeStr=str_replace(':','',$rvv);
                        $feeStr=str_replace('：','',$feeStr);
                    }
                    if(strpos($rvv,'社区')!==false ){
                        $keynote1Str='社区缴费成功提醒';
                    }
                }
                if(empty($keynote2)){
                    $dataArr['keynote2']=$feeStr;
                }else{
                    $dataArr['keynote2']=$dataArr['keynote2'].$feeStr;
                }
                if(empty($keynote1) && $keynote1Str){
                    $dataArr['keynote1']=$keynote1Str;
                }else if(empty($keynote1)){
                    $dataArr['keynote1']='缴费成功提醒';
                }
            }
        }
        if(isset($dataArr['new_info'])){
            unset($dataArr['new_info']);
        }
		if ($tempKey == 'OPENTM405462911') {
            if (!($dbinfo['status'] && $dbinfo['tempid'])){
                if (($dbinfo = M('Tempmsg')->where(array('tempkey' => 'OPENTM400166399', 'mer_id' => $mer_id))->order('status DESC,id ASC')->find()) && $dbinfo['status'] && $dbinfo['tempid']) {
                    $tempKey = 'OPENTM400166399';
                    unset($dataArr['keyword4']);
                }
                elseif(($dbinfo = M('Tempmsg')->where(array('tempkey' => 'OPENTM400166399', 'mer_id' => 0))->order('status DESC,id ASC')->find()) && $dbinfo['status'] && $dbinfo['tempid']){
                	$tempKey = 'OPENTM400166399';
                    unset($dataArr['keyword4']);
                }
            }
        }

        //店员新订单模板消息变更 （TM00017 =》 OPENTM400166399）
        if ($tempKey == 'TM00017') {
            if (!($dbinfo['status'] && $dbinfo['tempid'])) {
                $where['tempkey'] = 'OPENTM400166399';
                $where1['tempkey'] = 'OPENTM400166399';
				$t = $type;
				$type = $type == 2 ? 0 : $type;
                if($type){
                    $where['mer_id'] = 0;
                    $where['property_id'] = 0;
                    $where1['property_id'] = $property_id;
                    $where1['mer_id'] = 0;
                }else{
                    $where['mer_id'] = 0;
                    $where['property_id'] = 0;
                    $where1['mer_id'] = $mer_id;
                    $where1['property_id'] = 0;
                }
                if (($dbinfo = M('Tempmsg')->where($where)->order('status DESC,id ASC')->find()) && $dbinfo['status'] && $dbinfo['tempid']) {
                    $tempKey = 'OPENTM400166399';
                    $dataArr['keyword1'] = L_('新订单提醒').'（'.L_('订单号').':'.$dataArr['OrderSn'].'）';
                    $dataArr['keyword2'] = $dataArr['OrderStatus'];
                    $dataArr['keyword3'] = date('Y-m-d H:i:s');
                    unset($dataArr['OrderSn'], $dataArr['OrderStatus']);
                } elseif (($dbinfo = M('Tempmsg')->where($where1)->order('status DESC,id ASC')->find()) && $dbinfo['status'] && $dbinfo['tempid']) {
                    $tempKey = 'OPENTM400166399';
                    $dataArr['keyword1'] = L_('新订单提醒').'（'.L_('订单号').':'.$dataArr['OrderSn'].'）';
                    $dataArr['keyword2'] = $dataArr['OrderStatus'];
                    $dataArr['keyword3'] = date('Y-m-d H:i:s');
                    unset($dataArr['OrderSn'], $dataArr['OrderStatus']);
                }
				if($t == 2){
					$tempKey = 'OPENTM400166399';
                    $dataArr['keyword1'] = L_('寻人求助:').$dataArr['title'];
                    $dataArr['keyword2'] = $dataArr['status_text'];
                    $dataArr['keyword3'] = date('Y-m-d H:i:s');
				}
                if($t == 999){
                    $tempKey = 'OPENTM400166399';
                    $dataArr['keyword1'] = L_('求助信息:').$dataArr['title'];
                    $dataArr['keyword2'] = $dataArr['status_text'];
                    $dataArr['keyword3'] = date('Y-m-d H:i:s');
                }
            }
        }
        //海康推送
        if($tempKey == 'TM0001801'){
            if (!($dbinfo['status'] && $dbinfo['tempid'])) {
                $where['tempkey'] = 'OPENTM400166399';
                $where['mer_id'] = 0;
                $where['property_id'] = 0;
                if (($dbinfo = M('Tempmsg')->where($where)->order('status DESC,id ASC')->find()) && $dbinfo['status'] && $dbinfo['tempid']) {
                    $tempKey = 'OPENTM400166399';
                    unset($dataArr['OrderSn'], $dataArr['OrderStatus']);
                }
            }
            $where_visual = [];
            $where_visual['content'][] = ['like','%'.$dataArr['first'].'%'];
            $where_visual['content'][] = ['like','%'.$dataArr['wecha_id'].'%'];
            $where_visual['add_time'] = ['gt',time()-42];
            $Process_plan_msg = M('Process_plan_msg')->where($where_visual)->find();
            //$dataArr['is_repeat'] 表示可重复发送
            if($Process_plan_msg && !isset($dataArr['is_repeat'])){
                S($cache_key, time(), array('expire' => 300));
                return true;
            }
        }
        if(!isset($dataArr["href"])){
            $dataArr["href"]='';
        }
        if($property_id>0){
            $tempMsg = M('Tempmsg')->field(true)->order('status DESC,id ASC')->where(array('property_id' => $property_id,'mer_id'=>0, 'tempkey' => $tempKey))->find();
            $user = M('User')->field('uid')->where(array('openid' => $dataArr["wecha_id"]))->find();
            $where_bind=array();
            $where_bind['uid'] = $user['uid'];
            $where_bind['property_id'] = $property_id;
            $where_bind['type'] = 2;
            $where_bind['openid'] = array('neq','');
            $bindUer = M('Weixin_bind_user')->field('*')->where($where_bind)->find();
            $weixin_bind = D('Weixin_bind')->where(array('mer_id' => $property_id, 'type' => 2))->field('authorizer_appid')->find();
            if(!empty($weixin_bind) && $tempMsg && $tempMsg['tempid'] && $tempMsg['status'] && $bindUer && $bindUer['openid']){
                //物业配置了 就直走物业的，不走平台
                fdump_api(['只有物业的公众号'.__LINE__,$property_id, $tempMsg,$bindUer['openid'],$dataArr],'weiXinTemp/sendTempMsg',1);
                $dataArr = $this->customMadeData($dataArr,$property_id,$tempKey,$jumpAbChina);
                $this->sendOnlyPropertyWeixinTempMsg($property_id, $tempKey,$dataArr,$tempMsg,$param);
                S($cache_key, time(), array('expire' => 300));
                return true;
            }
        }
        if ($dbinfo['status']) {
            $data = $this->getData($tempKey, $dataArr, $dbinfo['textcolor']);
            $sendData = '{"touser":"' . $dataArr["wecha_id"] . '","wxapp_openid":"' . ($dataArr["wxapp_openid"] ? $dataArr["wxapp_openid"] : '') . '","template_id":"' . $dbinfo["tempid"] . '","url":"' . $dataArr["href"] . '",';

            $base_url = PD('House_village')->base_url;
            if(C('config.pay_wxapp_important') && !empty($dataArr["href"]) && stripos($dataArr['href'],$base_url.'pages/') !== false){
                // 小区原生页面 直接处理成原生地址
                $site_url = C('config.site_url');
                $pagepath = str_replace($site_url.$base_url, '', $dataArr["href"]);
                $sendData.= '"miniprogram":{"appid":"'.C('config.pay_wxapp_appid').'","pagepath":"'.$pagepath.'"},';
			}elseif(C('config.pay_wxapp_important') && !empty($dataArr["href"]) && stripos($dataArr['href'],'packapp/community/pages') !== false){
                //移动管理端
                $site_url = C('config.site_url');
                $site_url=trim($site_url);
                $site_url=rtrim($site_url,'/');
                $pagepath = str_replace($site_url.'/packapp/community/', '', $dataArr["href"]);
                //移动管理端
                $house_manage_wxapp_appid=C('config.house_manage_wxapp_appid');
                if(!empty($house_manage_wxapp_appid)){
                    $sendData.= '"miniprogram":{"appid":"'.$house_manage_wxapp_appid.'","pagepath":"'.$pagepath.'"},';
                }
            } elseif(C('config.pay_wxapp_important') && !empty($dataArr["href"])) {
                // 非原生地址首页定位跳转
                $pagepath = PD('House_village')->home_url."?redirect=webview&webview_url=".urlencode($dataArr["href"]);
                $sendData.= '"miniprogram":{"appid":"'.C('config.pay_wxapp_appid').'","pagepath":"'.$pagepath.'"},';
                
            }

			$sendData.= '"topcolor":"' . $dbinfo["topcolor"] . '","data":' . $data . '}';
			if(C('config.sms_send_type') || (isset($param['plan_send']) && $param['plan_send'])){
				import('@.ORG.plan.plan_msg');
				$msg_class = new plan_msg();
				$param = array(
					'type' => '2',
					'mer_id' => 0,
					'property_id'=>0,
					'content' => array('mer_id' => 0, 'content' => $sendData,'property_id'=>0,'callback'=>$callback)
				);
				$msg_class->addTask($param);
                S($cache_key, time(), array('expire' => 300));
			}else{
				$this->sendWeixinTempMsg($sendData, 0,0,$callback);
                S($cache_key, time(), array('expire' => 300));
			}
        }

		if(!$mer_id && !$property_id){
			return true;
		}
		if($type){
            $tempMsg = M('Tempmsg')->field(true)->order('status DESC,id ASC')->where(array('property_id' => $property_id,'mer_id'=>0, 'tempkey' => $tempKey))->find();
        }else{
            $tempMsg = M('Tempmsg')->field(true)->order('status DESC,id ASC')->where(array('mer_id' => $mer_id,'property_id'=>0, 'tempkey' => $tempKey))->find();
        }

		if ($tempKey == 'TM00251') {
			if (!($tempMsg['status'] && $tempMsg['tempid'])){
				$tempKey = 'OPENTM200772305';
				if (($tempMsg = M('Tempmsg')->where(array('tempkey' => $tempKey, 'mer_id' => $mer_id))->order('status DESC,id ASC')->find()) && $tempMsg['status'] && $tempMsg['tempid']) {
					$dataArr['keyword1'] = $dataArr['toName'];
					$dataArr['keyword2'] = $dataArr['gift'];
					$dataArr['keyword3'] = $dataArr['time'];
					unset($dataArr['toName'],$dataArr['gift'],$dataArr['time']);
				}
				$data = $this->getData($tempKey, $dataArr, $tempMsg['textcolor']);
			} else {
                $data = $this->getData($tempKey, $dataArr, $tempMsg['textcolor']);
            }
		} elseif($tempMsg)  {
            $data = $this->getData($tempKey, $dataArr, $tempMsg['textcolor']);
        }

        if (($mer_id || $property_id) && $tempMsg) {
            if ($user = M('User')->field('uid')->where(array('openid' => $dataArr["wecha_id"]))->find()) {
                if($type){
                    $where_bind=array();
                    $where_bind['uid'] = $user['uid'];
                    $where_bind['property_id'] = $property_id;
                    $where_bind['type'] = 2;
                    $where_bind['openid'] = array('neq','');
                    $bindUer = M('Weixin_bind_user')->field(true)->where($where_bind)->find();
                }else{
                    $bindUer = M('Weixin_bind_user')->field(true)->where(array('uid' => $user['uid'], 'mer_id' => $mer_id, 'openid' => array('neq','')))->find();
                }
                if ($bindUer && $bindUer['openid']) {
                    $sendData = '{"touser":"' . $bindUer['openid'] . '","wxapp_openid":"' . ($dataArr["wxapp_openid"] ? $dataArr["wxapp_openid"] : '') . '","template_id":"' . $tempMsg["tempid"] . '","url":"' . $dataArr["href"] . '","topcolor":"' . $tempMsg["topcolor"] . '","data":' . $data . '}';
                    if(C('config.sms_send_type') || (isset($param['plan_send']) && $param['plan_send'])){
						$param = array(
							'type' => '2',
							'mer_id' => $mer_id,
                            'property_id'=>$property_id,
							'content' => array('mer_id' => $mer_id, 'content' => $sendData,'property_id'=>$property_id,'callback'=>$callback)
						);
						import('@.ORG.plan.plan_msg');
						$msg_class = new plan_msg();
						$msg_class->addTask($param);
                        S($cache_key, time(), array('expire' => 300));
					}else{
						$this->sendWeixinTempMsg($sendData, $mer_id,$property_id,$callback);
                        S($cache_key, time(), array('expire' => 300));
					}
                }
            }
        }
		return true;
    }
    public function sendOnlyPropertyWeixinTempMsg($property_id=0, $tempKey='',$dataArr=array(),$tempMsg=array(),$param=array()){
        if($property_id<1 || empty($tempKey) || empty($dataArr)){
            return true;
        }
        if(empty($tempMsg)){
            $tempMsg = M('Tempmsg')->field(true)->order('status DESC,id ASC')->where(array('property_id' => $property_id,'mer_id'=>0, 'tempkey' => $tempKey))->find();
        }
        if(empty($tempMsg) || empty($tempMsg['tempid']) || $tempMsg['status']<1){
            return true;
        }
        $data = $this->getData($tempKey, $dataArr, $tempMsg['textcolor']);
        $user = M('User')->field('uid')->where(array('openid' => $dataArr["wecha_id"]))->find();
        if (!empty($user)) {
                $where_bind['uid'] = $user['uid'];
                $where_bind['property_id'] = $property_id;
                $where_bind['type'] = 2;
                $where_bind['openid'] = array('neq','');
                $bindUer = M('Weixin_bind_user')->field(true)->where($where_bind)->find();
            
            if ($bindUer && $bindUer['openid']) {
                $sendData = '{"touser":"' . $bindUer['openid'] . '","wxapp_openid":"' . ($dataArr["wxapp_openid"] ? $dataArr["wxapp_openid"] : '') . '","template_id":"' . $tempMsg["tempid"] . '","url":"' . $dataArr["href"] . '",';
                $base_url = PD('House_village')->base_url;
                
                $database_app_bind = D('Weixin_app_bind');
                $condition_app_bind['bind_type'] = '1';
                $condition_app_bind['status'] = '1';
                $condition_app_bind['other_id'] = $property_id;
                $now_property_bind = $database_app_bind->where($condition_app_bind)->find();
                $pay_wxapp_important=C('config.pay_wxapp_important');
                if(!empty($now_property_bind) && $now_property_bind['appid'] && !empty($dataArr["href"]) && $pay_wxapp_important && stripos($dataArr['href'],$base_url.'pages/') !== false){
                    // 小区原生页面 直接处理成原生地址
                    $site_url = C('config.site_url');
                    $pagepath = str_replace($site_url.$base_url, '', $dataArr["href"]);
                    $sendData.= '"miniprogram":{"appid":"'.$now_property_bind['appid'].'","pagepath":"'.$pagepath.'"},';
                }elseif($pay_wxapp_important && !empty($dataArr["href"]) && stripos($dataArr['href'],'packapp/community/pages') !== false){
                    //移动管理端
                    $site_url = C('config.site_url');
                    $site_url=trim($site_url);
                    $site_url=rtrim($site_url,'/');
                    $pagepath = str_replace($site_url.'/packapp/community/', '', $dataArr["href"]);
                    //移动管理端
                    $house_manage_wxapp_appid=C('config.house_manage_wxapp_appid');
                    if(!empty($house_manage_wxapp_appid)){
                        $sendData.= '"miniprogram":{"appid":"'.$house_manage_wxapp_appid.'","pagepath":"'.$pagepath.'"},';
                    }
                } elseif(!empty($now_property_bind) && $now_property_bind['appid'] && !empty($dataArr["href"]) && $pay_wxapp_important) {
                    // 非原生地址首页定位跳转
                    $pagepath = PD('House_village')->home_url."?redirect=webview&webview_url=".urlencode($dataArr["href"]);
                    $sendData.= '"miniprogram":{"appid":"'.$now_property_bind['appid'].'","pagepath":"'.$pagepath.'"},';
                }
                $sendData.= '"topcolor":"' . $tempMsg["topcolor"] . '","data":' . $data . '}';
                fdump_api(['只有物业的公众号'.__LINE__,$sendData,$bindUer['openid']],'wx_send_log',1);
                if(C('config.sms_send_type') || (isset($param['plan_send']) && $param['plan_send'])){
                    $param = array(
                        'type' => '2',
                        'mer_id' => 0,
                        'property_id'=>$property_id,
                        'content' => array('mer_id' => 0, 'content' => $sendData,'property_id'=>$property_id)
                    );
                    import('@.ORG.plan.plan_msg');
                    $msg_class = new plan_msg();
                    $msg_class->addTask($param);
                }else{
                    $this->sendWeixinTempMsg($sendData, 0,$property_id);
                }
            }
        }
        return true;
    }

    //增加callback回调函数，该函数第一个参数必须是发送结果 1：发送成功  0：发送失败
    public function sendWeixinTempMsg($sendData, $mer_id = 0,$property_id = 0,$callback = null)
    {
        if ($mer_id || $property_id) {
            if($mer_id){
                $token_data = PD('Weixin_bind')->get_access_token($mer_id);
            }else{
                $token_data = PD('Weixin_bind')->get_access_token($property_id,2);
            }
            if ($token_data['errcode']){
                fdump_api([__LINE__, 'mer_id'=>$mer_id,'token_data'=>$token_data,'property_id'=>$property_id],'weiXinTemp/sendTempMsg',1);
                is_callable($callback) && $callback(0);
                return '获取access_token发生错误：' . $token_data['errmsg'];
            } 
            $access_token = $token_data['access_token'];
        } else {
			$sendDataArr = json_decode($sendData,true);
			
			if($sendDataArr['touser']){
				$nowUser = PD('User')->get_user($sendDataArr['touser'],'openid');
			}else{
				$nowUser = PD('User')->get_user($sendDataArr['wxapp_openid'],'wxapp_openid');
			}
			
			// if(C('config.is_demo_domain')){
			// 	$nowUser['is_follow'] = 0;
			// }
			// if(stripos($sendDataArr['url'],C('config.site_url')) === false){
			// 	$nowUser['is_follow'] = 1;
			// }
			
			/*if($nowUser && $nowUser['wxapp_openid'] && !$nowUser['is_follow']){
				$tempmsg = M('Tempmsg')->where(array('tempid'=>$sendDataArr['template_id']))->find();
				if($tempmsg['wxapp_tempid']){
					$formidArr = D('Wxapp_formid')->get($nowUser['uid']);
					if($formidArr && $formidArr['formid']){
						$this->sendWeixinWxappMsg($tempmsg,$sendDataArr,$nowUser,$formidArr['formid']);
						return true;
					}
				}
			}*/

			//未关注的用户，不推送模板消息，避免网络卡
            if($nowUser && !$nowUser['is_follow']){
                is_callable($callback) && $callback(0);
                return true;
            }

			$access_token_array = PD('Access_token_expires')->get_access_token();
            if ($access_token_array['errcode']) {
                fdump_api(['模板消息推送'.__LINE__,$access_token_array],'weiXinTemp/sendTempMsg',1);
                is_callable($callback) && $callback(0);
                return '获取access_token发生错误：错误代码' . $access_token_array['errcode'] . ',微信返回错误信息：' . $access_token_array['errmsg'];
            }
            $access_token = $access_token_array['access_token'];
        }
        $requestUrl = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=' . $access_token;
        $sendData = $this->handleSendData($sendData);
		$result = $this->postCurl($requestUrl, $sendData);
        fdump_api(['模板消息推送'.__LINE__,$requestUrl, $sendData,$result],'weiXinTemp/sendTempMsg',1);
        if ((isset($result['errcode']) && $result['errcode'] != 0) || (isset($result['errorno']) && $result['errorno']!=0)) {
            fdump_sql([$sendData, $result], 'sendWeixinTempMsgFail');
            is_callable($callback) && $callback(0);
        }else {
            is_callable($callback) && $callback(1);
        }
    }

    /**
     * 处理微信模板消息发送的数据格式
     */
    public function handleSendData($sendData)
    {
        $sendData = json_decode($sendData,true);
        foreach ($sendData as $k=>&$v){
            //去掉头部颜色(老版)
            if($k=='topcolor'){
                unset($sendData[$k]);
                continue;
            }
            if($k=='data'){
                foreach ($v as $name=>$word){
                    if(in_array($name,['first','remark'])){//去掉头尾(老版)
                        unset($v[$name]);
                        continue;
                    }
                    if(isset($word['color'])){//去掉文字颜色(老版)
                        unset($v[$name]['color']);
                        continue;
                    }
                }
            }
        }
        $sendData = json_encode($sendData,JSON_UNESCAPED_UNICODE);
        return $sendData;
    }
	
	public function sendWeixinWxappMsg($tempmsg,$sendDataArr,$nowUser,$formid){
		$access_token_array = D('Access_token_wxapp_expires')->get_access_token();
		if ($access_token_array['errcode']) {
			return array('errcode'=>1,'errmsg'=>'获取access_token发生错误：错误代码' . $access_token_array['errcode'] .',微信返回错误信息：' . $access_token_array['errmsg']);
		}
		$access_token = $access_token_array['access_token'];
		
		$postData['touser'] = $nowUser['wxapp_openid'];
		$postData['template_id'] = $tempmsg['wxapp_tempid'];
		$postData['page'] = 'pages/index/index?redirect=webview&webview_url='.urlencode($sendDataArr['url']);
		$postData['form_id'] = $formid;
		
		foreach($sendDataArr['data'] as $key=>$value){
			$sendDataArr['data'][$key]['value'] = str_replace(array("\n",'\n',PHP_EOL),'',$sendDataArr['data'][$key]['value']);
		}
		if($tempmsg['wxapp_id'] == 'AT1168'){
			$postData['data'] = array(
				'keyword1' => array(
					'value'	=> $sendDataArr['data']['keyword1']['value'],
				),
				'keyword2' => array(
					'value'	=> $sendDataArr['data']['keyword2']['value'],
				),
				'keyword3' => array(
					'value'	=> $sendDataArr['data']['keyword3']['value'],
				),
				'keyword4' => array(
					'value'	=> $sendDataArr['data']['remark']['value'],
				),
			);
		}else if($tempmsg['wxapp_id'] == 'AT0009'){
			$keyword5Arr = explode('您的消费码：',$sendDataArr['data']['remark']['value']);
			if(count($keyword5Arr) == 2){
				$keyword5 = $keyword5Arr[1];
			}
			$postData['data'] = array(
				'keyword1' => array(
					'value'	=> $sendDataArr['data']['keyword2']['value'],
				),
				'keyword2' => array(
					'value'	=> $sendDataArr['data']['keyword1']['value'],
				),
				'keyword3' => array(
					'value'	=> $sendDataArr['data']['keyword3']['value'],
				),
				'keyword4' => array(
					'value'	=> $sendDataArr['data']['keyword4']['value'],
				),
				'keyword5' => array(
					'value'	=> $keyword5 ? $keyword5 : '无需消费密码',
				),
				'keyword6' => array(
					'value'	=> $keyword5 ? '消费时向商家出示消费密码，请在'.C('config.group_alias_name').'有效期内进行消费,如有疑问请致电商家' : '点击查看订单详情',
				),
			);
			$postData['emphasis_keyword'] = 'keyword3.DATA';
		}else if($tempmsg['wxapp_id'] == 'AT0218'){
			$postData['data'] = array(
				'keyword1' => array(
					'value'	=> $sendDataArr['data']['keyword1']['value'],
				),
				'keyword2' => array(
					'value'	=> $sendDataArr['data']['keyword3']['value'],
				),
				'keyword3' => array(
					'value'	=> $sendDataArr['data']['first']['value'],
				),
				'keyword4' => array(
					'value'	=> $sendDataArr['data']['remark']['value'],
				),
			);
			if(msubstr($postData['data']['keyword1']['value'],0,6) == $postData['data']['keyword1']['value']){
				$postData['emphasis_keyword'] = 'keyword1.DATA';
			}
		}else if($tempmsg['wxapp_id'] == 'AT0202'){
			$postData['data'] = array(
				'keyword1' => array(
					'value'	=> $sendDataArr['data']['first']['value'],
				),
				'keyword2' => array(
					'value'	=> $sendDataArr['data']['OrderSn']['value'],
				),
				'keyword3' => array(
					'value'	=> $sendDataArr['data']['OrderStatus']['value'],
				),
				'keyword4' => array(
					'value'	=> $sendDataArr['data']['remark']['value'],
				),
			);
			if(msubstr($postData['data']['keyword3']['value'],0,6) == $postData['data']['keyword3']['value']){
				$postData['emphasis_keyword'] = 'keyword3.DATA';
			}
		}else if($tempmsg['wxapp_id'] == 'AT0157'){
			$postData['data'] = array(
				'keyword1' => array(
					'value'	=> $sendDataArr['data']['first']['value'],
				),
				'keyword2' => array(
					'value'	=> $sendDataArr['data']['keyword1']['value'],
				),
				'keyword3' => array(
					'value'	=> $sendDataArr['data']['keyword2']['value'],
				),
				'keyword4' => array(
					'value'	=> $sendDataArr['data']['keyword3']['value'],
				),
				'keyword5' => array(
					'value'	=> $sendDataArr['data']['keyword4']['value'],
				),
			);
			$postData['emphasis_keyword'] = 'keyword4.DATA';
		}
		
		if($postData['data']){
			$requestUrl = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token=' . $access_token;
			$result = $this->postCurl($requestUrl, json_encode($postData,JSON_UNESCAPED_UNICODE));
			D('Wxapp_formid')->del($formid);
			if($result['rt']){
				return array();
			}
		}
	}

// Get Data.data
	public function getData($key, $dataArr, $color)
    {
        // $tempsArr = $this->templates();
        
        // $data = $tempsArr["$key"]['vars'];
        // $data = array_flip($data);
        unset($dataArr['wecha_id'], $dataArr['href']);
        $jsonData = '';
        foreach ($dataArr as $k => $v) {
//             if (in_array($k, array_flip($data))) {
//            $jsonData .= '"' . $k . '":{"value":"' . $v . '","color":"' . $color . '"},';
            $jsonData .= '"' . $k . '":{"value":"' . addslashes($v) . '"},';//模板消息更新后去掉了颜色
//             }
        }
        
        $jsonData = rtrim($jsonData, ',');
        
        return "{" . $jsonData . "}";
    }

	
	public function templates(){
		return array(
            'OPENTM201752540' => array(
				'cat_id'  => '2',
                'name'    => '订单支付成功通知',
                'vars'    => array('first', 'keyword1', 'keyword2', 'keyword3', 'keyword4', 'remark'),
                'content' => '
{{first.DATA}}
订单商品：{{keyword1.DATA}}
订单编号：{{keyword2.DATA}}
支付金额：{{keyword3.DATA}}
支付时间：{{keyword4.DATA}}
{{remark.DATA}}',
				'wxapp_id'	=> 'AT0009',
				'wxapp_content' =>'
单号 {{keyword1.DATA}}
物品名称 {{keyword2.DATA}}
支付金额 {{keyword3.DATA}}
支付时间 {{keyword4.DATA}}
消费密码 {{keyword5.DATA}}
订单内容 {{keyword6.DATA}}',
			),
            'OPENTM201682460' => array(
				'cat_id'  => '1',
                'name'    => '订单生成通知',
                'vars'    => array('first', 'keyword1', 'keyword2', 'keyword3', 'keyword4', 'remark'),
                'content' => '
{{first.DATA}}
时间：{{keyword1.DATA}}
商品名称：{{keyword2.DATA}}
订单号：{{keyword3.DATA}}
{{remark.DATA}}',
				'wxapp_id'	=> 'AT1168',
				'wxapp_content' =>'
生成时间 {{keyword1.DATA}}
商品信息 {{keyword2.DATA}}
订单编号 {{keyword3.DATA}}
备注 {{keyword4.DATA}}'
			),
            'OPENTM202521011' => array(
				'cat_id'  => '4',
                'name'    => '订单完成通知',
                'vars'    => array('first', 'keyword1', 'keyword2', 'remark'),
                'content' => '
{{first.DATA}}
订单号：{{keyword1.DATA}}
完成时间：{{keyword2.DATA}}
{{remark.DATA}}'),
            'TM00785' => array(
                'cat_id'  => '5',
                'name'    => '开奖结果通知',
                'vars'    => array('first', 'program', 'result', 'remark'),
                'content' => '
{{first.DATA}}
开奖项目：{{program.DATA}}
中奖情况：{{result.DATA}}
{{remark.DATA}}'),
            'OPENTM206854010' => array(
                'cat_id'  => '5',
                'name'    => '开奖结果通知',
                'vars'    => array('first', 'keyword1', 'keyword2', 'remark'),
                'content' => '
{{first.DATA}}
活动奖品：{{keyword1.DATA}}
开奖时间：{{keyword2.DATA}}
{{remark.DATA}}'),
			'TM01008' => array(
				'cat_id'  => '6',
                'name'    => '缴费提醒通知',
                'vars'    => array('first', 'keynote1', 'keynote2', 'remark'),
                'content' => '
{{first.DATA}}
收费单位：{{keynote1.DATA}}
缴费账号：{{keynote2.DATA}}
{{remark.DATA}}'),
			'OPENTM201812627' => array(
				'cat_id'  => '8',
                'name'    => '佣金提醒',
                'vars'    => array('first', 'keyword1', 'keyword2','remark'),
                'content' => '
{{first.DATA}}
佣金金额：{{keyword1.DATA}}
时间：{{keyword2.DATA}}
{{remark.DATA}}'
			),
			'TM00017' => array(
				'cat_id'  => '9',
                'name'    => '订单状态更新',
                'vars'    => array('first', 'OrderSn', 'OrderStatus','remark'),
                'content' => '
{{first.DATA}}
订单编号：{{OrderSn.DATA}}
订单状态：{{OrderStatus.DATA}}
{{remark.DATA}}',
				'wxapp_id'	=> 'AT0202',
				'wxapp_content' =>'
商品信息 {{keyword1.DATA}}
订单号 {{keyword2.DATA}}
订单状态 {{keyword3.DATA}}
回复内容 {{keyword4.DATA}}'
			),
			'OPENTM200964573' => array(
				'cat_id'  => '11',
                'name'    => '会员卡领取通知',
                'vars'    => array('first', 'keyword1', 'keyword2', 'keyword3','keyword4', 'remark'),
                'content' => '
{{first.DATA}}
会员编号：{{keyword1.DATA}}
会员姓名：{{keyword2.DATA}}
会员电话：{{keyword3.DATA}}
申请时间：{{keyword4.DATA}}
{{remark.DATA}}'
			),
			'TM00251' => array(
				'cat_id'  => '12',
                'name'    => '领取成功通知(领取优惠券)',
                'vars'    => array('first', 'toName', 'gift', 'time', 'remark'),
                'content' => '
{{first.DATA}}
领取人：{{toName.DATA}}
赠品：{{gift.DATA}}
领取时间：{{time.DATA}}
{{remark.DATA}}'
			),
			'OPENTM200772305' => array(
				'cat_id'  => '12',
                'name'    => '礼品领取成功通知(领取优惠券)',
                'vars'    => array('first', 'toName', 'gift', 'time', 'remark'),
                'content' => '
{{first.DATA}}
领取人：{{keyword1.DATA}}
礼品：{{keyword2.DATA}}
领取时间：{{keyword3.DATA}}
{{remark.DATA}}'
			),
			'OPENTM205984119' => array(
				'cat_id'  => '13',
                'name'    => '排号提醒通知',
                'vars'    => array('first', 'keyword1', 'keyword2', 'keyword3', 'remark'),
                'content' => '
{{first.DATA}}
队列号：{{keyword1.DATA}}
取号时间：{{keyword2.DATA}}
等待人数：{{keyword3.DATA}}
{{remark.DATA}}'
			),
		'OPENTM402026291' => array(
				'cat_id'  => '14',
				'name'    => '收款成功',
				'vars'    => array('first', 'keyword1', 'keyword2','keyword3','keyword4','keyword5', 'remark'),
				'content' => '
{{first.DATA}}
费用类型：{{keyword1.DATA}}
费用金额：{{keyword2.DATA}}
消费门店：{{keyword3.DATA}}
消费时间：{{keyword4.DATA}}
订单编号：{{keyword5.DATA}}
{{remark.DATA}}'
				),
		'OPENTM414860535' => array(
				'cat_id'  => '16',
				'name'    => '挪车提醒',
				'vars'    => array('first', 'keyword1', 'keyword2', 'remark'),
				'content' => '
{{first.DATA}}
车牌号：{{keynote1.DATA}}
发送时间：{{keynote2.DATA}}
{{remark.DATA}}'
				),
		'OPENTM408101810' => array(
				'cat_id'  => '17',
				'name'    => '来访通知',
				'vars'    => array('first', 'keyword1', 'keyword2','keyword3', 'remark'),
				'content' => '
{{first.DATA}}
访客名称：{{keynote1.DATA}}
访客电话：{{keynote2.DATA}}
来访时间：{{keynote3.DATA}}
{{remark.DATA}}'
				),
		'OPENTM412319702' => array(
				'cat_id'  => '19',
				'name'    => '提现审核提醒',
				'vars'    => array('first', 'keyword1', 'keyword2', 'keyword3', 'remark'),
				'content' => '
{{first.DATA}}
申请人：{{keynote1.DATA}}
申请时间：{{keynote2.DATA}}
提现金额：{{keynote3.DATA}}
{{remark.DATA}}'
				),
		'OPENTM202425107' => array(
				'cat_id'  => '19',
				'name'    => '提现审核结果通知',
				'vars'    => array('first', 'keyword1', 'keyword2', 'keyword3','keyword4','keyword5', 'remark'),
				'content' => '
{{first.DATA}}
提现金额：{{keyword1.DATA}}
提现方式：{{keyword2.DATA}}
申请时间：{{keyword3.DATA}}
审核结果：{{keyword4.DATA}}
审核时间：{{keyword5.DATA}}
{{remark.DATA}}'
				),
		'OPENTM405462911' => array(
			'cat_id'  => '20',
			'name'    => '消息发送状态提醒',
			'vars'    => array('first', 'keyword1', 'keyword2', 'keyword3','keyword4', 'remark'),
			'content' => '
{{first.DATA}}
消息类型：{{keyword1.DATA}}
发送状态：{{keyword2.DATA}}
发送时间：{{keyword3.DATA}}
发送对象：{{keyword4.DATA}}
{{remark.DATA}}',
			'wxapp_id'	=> 'AT0218',
			'wxapp_content' =>'
项目 {{keyword1.DATA}}
时间 {{keyword2.DATA}}
温馨提示 {{keyword4.DATA}}
备注 {{keyword5.DATA}}'),
		'OPENTM400166399' => array(
			'cat_id'  => '20',
			'name'    => '项目状态提醒',
			'vars'    => array('first', 'keyword1', 'keyword2', 'keyword3', 'remark'),
			'content' => '
{{first.DATA}}
项目名称：{{keyword1.DATA}}
最新状态：{{keyword2.DATA}}
时间：{{keyword3.DATA}}
{{remark.DATA}}',
				'wxapp_id'	=> 'AT0218',
				'wxapp_content' =>'
项目 {{keyword1.DATA}}
时间 {{keyword2.DATA}}
温馨提示 {{keyword4.DATA}}
备注 {{keyword5.DATA}}'
				),
            'OPENTM407216824' => array(
                'cat_id'  => '20',
                'name'    => '服务状态提醒',
                'vars'    => array('first', 'keyword1', 'keyword2', 'keyword3','keyword4', 'remark'),
                'content' => '
{{first.DATA}}
服务类型：{{keyword1.DATA}}
订单编号：{{keyword2.DATA}}
订单状态：{{keyword3.DATA}}
更新时间：{{keyword4.DATA}}
{{remark.DATA}}',
                'wxapp_id'	=> 'AT0218',
                'wxapp_content' =>'
项目 {{keyword1.DATA}}
时间 {{keyword2.DATA}}
温馨提示 {{keyword4.DATA}}
备注 {{keyword5.DATA}}'
            ),
			'OPENTM401833445' => array(
				'cat_id'  => '21',
				'name'    => '余额变动提示',
				'vars'    => array('first', 'keyword1', 'keyword2', 'keyword3','keyword4', 'remark'),
				'content' => '
{{first.DATA}}
变动时间：{{keyword1.DATA}}
变动类型：{{keyword2.DATA}}
变动金额：{{keyword3.DATA}}
当前余额：{{keyword4.DATA}}
{{remark.DATA}}',
				'wxapp_id'	=> 'AT0157',
				'wxapp_content' =>'
温馨提示 {{keyword1.DATA}}
变动时间 {{keyword2.DATA}}
变动原因 {{keyword3.DATA}}
变动金额 {{keyword4.DATA}}
当前余额 {{keyword5.DATA}}'
				),
			'OPENTM401300510' => array(
				'cat_id'  => '24',
				'name'    => '欠款通知',
				'vars'    => array('first', 'keyword1', 'keyword2', 'remark'),
				'content' => '
{{first.DATA}}
商家：{{keyword1.DATA}}
欠款金额：{{keyword2.DATA}}
{{remark.DATA}}'
				)
				,'OPENTM405733036' => array(
                'cat_id'  => '23',
                'name'    => '商户入驻申请通知',
                'vars'    => array('first', 'keyword1', 'keyword2', 'keyword3', 'keyword4','remark'),
                'content' => '
{{first.DATA}}
商户名称：{{keyword1.DATA}}
所属区域：{{keyword2.DATA}}
申请时间：{{keyword3.DATA}}
申请信息：{{keyword4.DATA}}
{{remark.DATA}}'
            ),'OPENTM405627933' => array(
                'cat_id'  => '25',
                'name'    => '退款提醒',
                'vars'    => array('first', 'keyword1', 'keyword2', 'keyword3','remark'),
                'content' => '
{{first.DATA}}
订单号：{{keyword1.DATA}}
退款金额：{{keyword2.DATA}}
时间：{{keyword3.DATA}}
{{remark.DATA}}'
            ),'OPENTM417740354' => array(
                'cat_id'  => '25',
                'name'    => '审批抄送提醒',
                'vars'    => array('first', 'keyword1', 'keyword2', 'keyword3','remark'),
                'content' => '
{{first.DATA}}
流程类型：{{keyword1.DATA}}
申请人：{{keyword2.DATA}}
申请时间：{{keyword3.DATA}}
{{remark.DATA}}'
            ),
            'OPENTM416545460' => array(
                'cat_id'  => '26',
                'name'    => '门禁提醒',
                'vars'    => array( 'first', 'keyword1', 'keyword2', 'keyword3', 'keyword4', 'keyword5','remark'),
                'content' => '{{first.DATA}}
姓名：{{keyword1.DATA}}
工号：{{keyword2.DATA}}
时间：{{keyword3.DATA}}
地点名称：{{keyword4.DATA}}
出入类型：{{keyword5.DATA}}
{{remark.DATA}}'
            ),
            'OPENTM411328351' => array(
                'cat_id'  => '6',
                'name'    => '车辆缴费提醒',
                'vars'    => array( 'first', 'keyword1', 'keyword2', 'keyword3', 'keyword4','remark'),
                'content' => '{{first.DATA}}
车牌号：{{keyword1.DATA}}
缴费类型：{{keyword2.DATA}}
到期日：{{keyword3.DATA}}
提醒时间：{{keyword4.DATA}}
{{remark.DATA}}'
            ),
 'OPENTM207117419' => array(
                'cat_id'  => '26',
                'name'    => '车辆进出场通知',
                'vars'    => array( 'first', 'keyword1', 'keyword2', 'keyword3','remark'),
                'content' => '{{first.DATA}}
车牌号：{{keyword1.DATA}}
停车场：{{keyword2.DATA}}
时间：{{keyword3.DATA}}
{{remark.DATA}}'
            ),
            '61396' => array(
                'cat_id'  => '26',
                'name'    => '物料借用申请结果通知',
                'vars'    => array( 'thing1', 'const2', 'time3'),
                'is_category'=>1,
                'content' => '物料名称：{{thing1.DATA}}
物料状态：{{const2.DATA}}
时间：{{time3.DATA}}'
            ),
            'OPENTM414750160' => array(
                'cat_id'  => '28',
                'name'    => '设备告警通知',
                'vars'    => array( 'first', 'keyword1', 'keyword2', 'keyword3', 'keyword4','keyword5','remark'),
                'content' => '{{first.DATA}}
设备名称：{{keyword1.DATA}}
告警类型：{{keyword2.DATA}}
设备位置：{{keyword3.DATA}}
告警数据：{{keyword4.DATA}}
告警时间：{{keyword4.DATA}}
{{remark.DATA}}'
            ),
		);
	}

    public function wxapp_templates(){
        return array(
            'AT0196' => array(
                'cat_id'  => '0',
                'name'    => '投票结果通知',
                'vars'    => array('keyword1', 'keyword2', 'keyword3', 'keyword4'),
                'content' => '
投票标题：{{keyword1.DATA}}
投票类型：{{keyword2.DATA}}
投票发起人：{{keyword3.DATA}}
投票结果：{{keyword4.DATA}}
参与人数：{{keyword5.DATA}}'),
            'AT0052' => array(
                'cat_id'  => '0',
                'name'    => '审核通知',
                'vars'    => array('keyword1', 'keyword2', 'keyword3', 'keyword4'),
                'content' => '
姓名：{{keyword1.DATA}}
申请项目：{{keyword2.DATA}}
状态：{{keyword3.DATA}}
日期：{{keyword4.DATA}}'),
            'AT0751' => array(
                'cat_id'  => '0',
                'name'    => '成员退出通知',
                'vars'    => array('keyword1', 'keyword2', 'keyword3', 'keyword4'),
                'content' => '
用户昵称：{{keyword1.DATA}}
退出方式：{{keyword2.DATA}}
备注：{{keyword3.DATA}}
退出时间：{{keyword4.DATA}}'),
            'AT0322' => array(
                'cat_id'  => '0',
                'name'    => '活动创建成功提醒',
                'vars'    => array('keyword1', 'keyword2', 'keyword3', 'keyword4', 'keyword5', 'keyword6'),
                'content' => '
活动名称：{{keyword1.DATA}}
活动时间：{{keyword2.DATA}}
发布人：{{keyword3.DATA}}
发布时间：{{keyword4.DATA}}
活动人数：{{keyword5.DATA}}
报名费用：{{keyword6.DATA}}'),
            'AT0130' => array(
                'cat_id'  => '0',
                'name'    => '投票创建成功通知',
                'vars'    => array('keyword1', 'keyword2', 'keyword3', 'keyword4'),
                'content' => '
投票标题：{{keyword1.DATA}}
创建时间：{{keyword2.DATA}}
发起人：{{keyword3.DATA}}
投票内容：{{keyword4.DATA}}'),
            'AT1175' => array(
                'cat_id'  => '0',
                'name'    => '内容创建成功通知',
                'vars'    => array('keyword1', 'keyword2', 'keyword3', 'keyword4'),
                'content' => '
主题：{{keyword1.DATA}}
内容类型：{{keyword2.DATA}}
创建人：{{keyword3.DATA}}
创建时间：{{keyword4.DATA}}'),
            'AT1454' => array(
                'cat_id'  => '0',
                'name'    => '已报名活动参加提醒',
                'vars'    => array('keyword1', 'keyword2', 'keyword3', 'keyword4'),
                'content' => '
活动时间：{{keyword1.DATA}}
活动主题：{{keyword2.DATA}}
参加人员：{{keyword3.DATA}}
费用：{{keyword4.DATA}}'),
            'AT0027' => array(
                'cat_id'  => '0',
                'name'    => '报名成功通知',
                'vars'    => array('keyword1', 'keyword2', 'keyword3', 'keyword4'),
                'content' => '
报名项目：{{keyword1.DATA}}
活动主题：{{keyword2.DATA}}
报名姓名：{{keyword3.DATA}}
报名时间：{{keyword4.DATA}}'),
            'AT0036' => array(
                'cat_id'  => '0',
                'name'    => '退款通知',
                'vars'    => array('keyword1', 'keyword2', 'keyword3', 'keyword4'),
                'content' => '
退款类型：{{keyword1.DATA}}
退款原因：{{keyword2.DATA}}
退款金额：{{keyword3.DATA}}
退款时间：{{keyword4.DATA}}'
            ),
            'AT0225' => array(
                'cat_id'  => '0',
                'name'    => '活动取消通知',
                'vars'    => array('keyword1', 'keyword2', 'keyword3', 'keyword4', 'keyword5'),
                'content' => '
活动名称：{{keyword1.DATA}}
取消原因：{{keyword2.DATA}}
取消时间：{{keyword3.DATA}}
报名人数：{{keyword4.DATA}}
备注：{{keyword5.DATA}}'
            ),
            'AT0878' => array(
                'cat_id'  => '0',
                'name'    => '消息已送达通知',
                'vars'    => array( 'keyword1', 'keyword2', 'keyword3'),
                'content' => '
发送人：{{keyword1.DATA}}
详情：{{keyword2.DATA}}
发送时间：{{keyword3.DATA}}'
            ),

        );
    }




// Post Request// 支付方式：{{keyword4.DATA}}
	function postCurl($url, $data){
		$ch = curl_init();
		$header = "Accept-Charset: utf-8";
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$tmpInfo = curl_exec($ch);
		$errorno=curl_errno($ch);
		if ($errorno) {
			return array('rt'=>false,'errorno'=>$errorno);
		}else{
			$js=json_decode($tmpInfo,1);
			if ($js['errcode']=='0'){
				return array('rt'=>true,'errorno'=>0);
			}else {
				//exit('模板消息发送失败。错误代码'.$js['errcode'].',错误信息：'.$js['errmsg']);
				return array('rt'=>false,'errorno'=>$js['errcode'],'errmsg'=>$js['errmsg']);

			}
		}
	}




// Get Access_token Request
	function curlGet($url){
		$ch = curl_init();
		$header = "Accept-Charset: utf-8";
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
		//curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$temp = curl_exec($ch);
		return $temp;
	}



	/**
     * 兼容类目模板（新版）
     */
	public function getDataArr($tempKey,$dataAry,$property_id,$mer_id)
    {
        $data = [];
        //缴费成功通知
		fdump_api([$property_id,$mer_id,'dataAry'=>$dataAry,$tempKey],'weiXinTemp/sendTempMsg',1);
        $tempInfo = $this->getWordsAry($tempKey,$property_id,$mer_id);
        if($tempInfo['tempid'] && $tempInfo['words']){
            foreach ($tempInfo['words'] as $v){
                $content = $dataAry['new_info'][$v]??'无';
                $data[$v] = $content?:'无';
                if(strpos($v,'thing')!==false && mb_strlen($data[$v], 'utf8') > 20){
                    $data[$v] = mb_substr($data[$v], 0, 17, 'utf8') . '...';
                }
                if(strpos($v,'character_string')!==false && mb_strlen($data[$v], 'utf8') > 32){
                    $data[$v] = mb_substr($data[$v], 0, 29, 'utf8') . '...';
                }
                if(strpos($v,'phone_number')!==false && mb_strlen($data[$v], 'utf8') > 17){
                    $data[$v] = mb_substr($data[$v], 0, 14, 'utf8') . '...';
                }
                
                //const不用裁剪，都是枚举值。微信平台审核不通过的枚举值都发送不了
                // if(strpos($v,'const')!==false && mb_strlen($data[$v], 'utf8') > 20){
                //     $data[$v] = mb_substr($data[$v], 0, 20, 'utf8');
                // }

                if(strpos($v,'phrase')!==false && mb_strlen($data[$v], 'utf8') > 5){
                    $data[$v] = mb_substr($data[$v], 0, 5, 'utf8');
                }
            }
            foreach ($dataAry as $kk=>$vv){
                if(strpos($kk,'keynote')===false && strpos($kk,'keyword')===false && !in_array($kk,['first','remark','new_info'])){
                    $data[$kk] = $vv;
                }else if(isset($data[$kk]) && $data[$kk]=='无'){
                    $data[$kk] = $vv;
                }
            }
        }
        return ['ary'=>$data,'tempid'=>$tempInfo['tempid']];
    }
    /**
     * 获取类目模板实际使用的字段数组等信息
     */
    public function getWordsAry($tempKey, $property_id = 0, $mer_id = 0)
    {
        $return = [
            'tempid'=>'',
            'words'=>[],
        ];
       
        $tempInfo = M('tempmsg')->where(['tempkey'=>$tempKey,'property_id'=>0, 'mer_id' => 0])->field('new_content,tempid')->find();
        if($property_id > 0){//物业有模板走物业模板，没有物业模板走系统模板
            $temp = M('tempmsg')->where(['tempkey'=>$tempKey,'property_id'=>$property_id,'status'=>1])->field('new_content,tempid')->find();
            $tempInfo = ($temp && $temp['new_content']) ? $temp : $tempInfo;
        }else if($mer_id > 0){
            $temp = M('tempmsg')->where(['tempkey'=>$tempKey,'mer_id'=>$mer_id,'status'=>1])->field('new_content,tempid')->find();
            $tempInfo = ($temp && $temp['new_content']) ? $temp : $tempInfo;
        }
        fdump_api([$property_id,$tempInfo,$tempKey],'weiXinTemp/sendTempMsg',1);
        if(!$tempInfo){
            return $return;
        }
        $return['tempid'] = $tempInfo['tempid'];
        $wordsAry = explode('}}',$tempInfo['new_content']);
        foreach ($wordsAry as $k=>$v){
            $v = trim($v);
            $vAry = explode('{{',$v);
            $wordsAry[$k] = explode('.',$vAry[1])[0];
            if(!$v){
                unset($wordsAry[$k]);
            }
        }
        $return['words'] = $wordsAry;
        return $return;
    }
    
    /**
     * 处理定制数据
     */
    public function customMadeData($dataArr,$property_id,$tempKey,$jumpAbChina)
    {
        $pay_url = '';
        if($property_id>0 && in_array($tempKey,['TM01008','42804']) && $jumpAbChina && C('customization.abchina') && (!$dataArr["href"] || strstr($dataArr["href"],C('config.site_url')))){//跳转到农行缴费页面(已有通联链接不走这里)
            //查询物业配置的农行跳转路径
            $abchina_set = M('House_abchina_set')->where(['property_id'=>$property_id])->find();
            if($abchina_set && $abchina_set['epay_code'] && $abchina_set['merchant_id'] && $abchina_set['merchant_pfx'] && $abchina_set['trust_pay_cer'] && $abchina_set['abchina_prikey'] && $abchina_set['pay_url']){
                $pay_url = $abchina_set['pay_url'];
            }
        }
        if($pay_url){
            $dataArr["href"] = $pay_url;
        }
        return $dataArr;
    }

    /**
     * 旧版模板消息缺少类目消息new_info字段，这里全局拦截下已经适配过的类目模板消息
     * 新的消息非必要不要继续增加，尽可能使用新的类目模板适配
     *
     * @param string $tempKey
     * @param array $dataArr
     * @return void
     * @date: 2023/10/19
     */
    public function wechatClassificationTemplateInterceptor($tempKey, $dataArr)
    {

        //店员操作通知转换下
        if ($tempKey == 'TM00017') {
            $tempKey = 'OPENTM400166399';
            $dataArr['keyword1'] = $dataArr['first'];
            $dataArr['keyword2'] = $dataArr['OrderStatus'];
            $dataArr['keyword3'] = date('Y-m-d H:i:s');
			$dataArr['keyword4'] = $dataArr['OrderSn'];
        }

        $config = [
            'OPENTM201682460' => [ //订单生成通知
                'tempKey' => '49282',
                'fieldsMap' => [
                    'keyword1' => 'time3',
                    'keyword2' => 'thing5',
                    'keyword3' => 'character_string1',
                ]
            ],
            'OPENTM201752540' => [ //订单支付成功通知
                'tempKey' => '50096',
                'fieldsMap' => [
                    'keyword1' => 'thing3',
                    'keyword2' => 'character_string1',
                    'keyword3' => 'amount4',
                    'keyword4' => 'time5',
                ]
            ],
            'OPENTM400166399' => [ //消息发送状态提醒
                'tempKey' => '44716',
                'fieldsMap' => [
                    'keyword1' => 'thing12',
                    'keyword2' => 'phrase2',
                    'keyword3' => 'time13',
                    'keyword4' => 'thing11'
                ]
            ],
            'OPENTM405462911' => [ //消息发送状态提醒
                'tempKey' => '44716',
                'fieldsMap' => [
                    'keyword1' => 'thing12',
                    'keyword2' => 'phrase2',
                    'keyword3' => 'time13',
                    'keyword4' => 'thing11'
                ]
            ],
            'OPENTM414860535' => [ //挪车提醒
                'tempKey' => '51212',
                'fieldsMap' => [
                    'keyword1' => 'car_number1',
                    'keyword2' => 'time2'
                ]
            ],
            'OPENTM402026291' => [ //商家收款成功提醒
                'tempKey' => '56650',
                'fieldsMap' => [
                    'keyword1' => 'thing8',
                    'keyword2' => 'amount9',
                    'keyword3' => 'thing10',
                    'keyword4' => 'time11',
                    'keyword5' => 'character_string12',
                ]
            ]
        ];

        if (isset($config[$tempKey]) && empty($dataArr['new_info'])) {
            $map =  $config[$tempKey];
            $newInfo = ['tempKey' => $map['tempKey']];
            foreach ($map['fieldsMap'] as $k => $v) {
                $tmpStr='无';
                if(isset($dataArr[$k]) && $dataArr[$k]){
                    $tmpStr=str_replace(['\r','\n','\t'],' ',$dataArr[$k]);
                }
                if($tempKey=='OPENTM400166399' && $k=='keyword4' && (!isset($dataArr[$k])||empty($dataArr[$k])) && isset($dataArr['first']) && $dataArr['first']){
                    $tmpStr=str_replace(['\r','\n','\t'],' ',$dataArr['first']);
                }
                $newInfo[$v] = $tmpStr;
            }
            $dataArr['new_info'] = $newInfo;
        }
        return $dataArr;
    }
}

?>
