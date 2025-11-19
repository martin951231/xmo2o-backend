<?php
class plan_msg extends plan_base{
	public function runTask($msg_task_id = 0){
		$now_time = time();
		
		//计划任务runtask会传入主计划任务数组，直接调用传入消息ID。临时这样处理。
		if(is_array($msg_task_id)){
			$msg_list = M('Process_plan_msg')->field(true)->where(['send_time'=>['elt',$now_time], 'status' => '0'])->order('`sort` DESC,`type` ASC,`send_time` ASC')->limit(5)->select();
		}else{
			$msg_list = M('Process_plan_msg')->field(true)->where(['id' => $msg_task_id])->select();
		}

		if(!$msg_list){
			return true;
		}

		//尽量避免重复执行，先去除任务
		$msg_id = array();
		foreach($msg_list as $value){
			$msg_id[] = $value['id'];
		}
        D()->startTrans();
		M('Process_plan_msg')->where(array('id'=>array('in',$msg_id)))->data(array('status'=>'1'))->save();
        D()->commit();
        $pay_scrcu_cash_open = C('config.pay_scrcu_cash_open');
        $judgeTime = 0;
		foreach($msg_list as $value){
			$this->keepThread();
			if($value['type'] == 1){
				$sms_data = unserialize($value['content']);
				foreach($sms_data as $v){
					$this->keepThread();
					Sms::sendSmsData($v);
				}
			} elseif($value['type'] == 2) {
				$wechat_data = unserialize($value['content']);
				$wechat_data = $wechat_data[0];
				$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
				$mer_id = isset($wechat_data['mer_id']) && $wechat_data['mer_id'] ? intval($wechat_data['mer_id']) : 0;
                $property_id = isset($wechat_data['property_id']) && $wechat_data['property_id'] ? intval($wechat_data['property_id']) : 0;
	            $model->sendWeixinTempMsg($wechat_data['content'], $mer_id,$property_id,$wechat_data['callback']??null);
			} elseif ($value['type'] == 4) {
				$app_data = array_shift(unserialize($value['content']));
			    if (isset($app_data['from']) && $app_data['from'] == 'delivery') {
					if($app_data['audience']['tag'][0] == '020000000000'){
						return true;
					}
					$deliver = D('Deliver_user')->where(array('device_id'=>$app_data['audience']['tag'][0]))->find();
					fdump($app_data,'deliver_app_data',true);
					fdump($deliver,'deliver_deliver',true);
					if($app_data['platform'][0]=='android' && $deliver['device_token'] &&  C('config.umenPush_deliver_appkey')!='' && $deliver['app_version']>=800){
						$app_data['send_type'] = 'delivery';
						$app_data['device_token']=$deliver['device_token'];
						$app_data['appMasterSecret']=C('config.umenPush_deliver_secret');
						$app_data['appkey']=C('config.umenPush_deliver_appkey');
						UmenPush::sendAndroid($app_data);
					}else if(C('config.alipush_accessKeyId')!='' && $app_data['platform'][0]=='android' && $deliver['app_version']>=500){
						$app_data['send_type'] = 'delivery';
						AliPush::push($app_data);
					}else if($app_data['platform'][0]=='ios' && C('config.pushkit_deliver_cert')!='' && $deliver['app_version']>=500){
						$cert=$_SERVER['DOCUMENT_ROOT'].C('config.pushkit_deliver_cert');
						$password=C('config.pushkit_deliver_password');
						$pushkit=new Pushkit($cert,$password);
						$info=$pushkit->reg_push_message($app_data);
						$info['type']='deliver';
						fdump($info,'pushkit_log',1);
					}else{
						if (!($jpushDeliver instanceof Jpush)) {
							$jpushDeliver = new Jpush(C('config.deliver_jpush_appkey'), C('config.deliver_jpush_secret'));
						}

						$app_data['message']['send_type'] = 'delivery';

                        if (isset($app_data['audience']['registration_id']) && $app_data['audience']['registration_id']) {
                            unset($app_data['audience']['tag']);
                        }
						$jpushDeliver->send($app_data['platform'], $app_data['audience'], $app_data['notification'], $app_data['message']);
					}
				} elseif (isset($app_data['from']) && $app_data['from'] == 'storestaff') {
					$staff = M('Merchant_store_staff')->where(array('device_id'=>$app_data['audience']['tag'][0]))->find();
					if($app_data['audience']['tag'][0] == '020000000000'){
						return true;
					}
					
					// 执行店员推送的钩子
					$hook_result = hook::hook_exec('plan.msg_staff_push_before',['staff'=>$staff,'app_data'=>$app_data]);
					
					if($app_data['platform'][0]=='android' && $staff['device_token'] && C('config.umenPush_appkey')!='' && $staff['app_version']>=800 ){
						fdump($app_data,'storestaff_app_data',true);
						fdump($staff,'storestaff_staff',true);
						$app_data['send_type'] = 'storestaff';
						$app_data['device_token']=$staff['device_token'];
						$app_data['appMasterSecret']=C('config.umenPush_secret');
						$app_data['appkey']=C('config.umenPush_appkey');
						UmenPush::sendAndroid($app_data);
					}else if(C('config.alipush_accessKeyId')!='' && $app_data['platform'][0]=='android' &&  $staff['app_version']>=500){
						$app_data['send_type'] = 'storestaff';
						AliPush::push($app_data);
					}else if($app_data['platform'][0]=='ios' && C('config.pushkit_cert')!='' && $staff['app_version']>=500){
						$cert=$_SERVER['DOCUMENT_ROOT'].C('config.pushkit_cert');
						$password=C('config.pushkit_password');
						$pushkit=new Pushkit($cert,$password);
						$info=$pushkit->reg_push_message($app_data,0);
						fdump($info,'pushkit_log',1);
					}else {
						if (!($jpushStaff instanceof Jpush)) {
					        $jpushStaff = new Jpush(C('config.staff_jpush_appkey'), C('config.staff_jpush_secret'));
					    }
						
						$app_data['message']['send_type'] = 'storestaff';

                        if (isset($app_data['audience']['registration_id']) && $app_data['audience']['registration_id']) {
                            unset($app_data['audience']['tag']);
                        }
						$jpushStaff->send($app_data['platform'], $app_data['audience'], $app_data['notification'], $app_data['message']);
					}
				} else {
    				if (!($jpush instanceof Jpush)) {
    					$jpush = new Jpush(C('config.weixin_push_jpush_appkey'), C('config.weixin_push_jpush_secret'));
    				}
					
					//测试设备，发测试环境
					$test_devices = [];
					if(in_array($app_data['audience']['tag'][0], $test_devices)){
						$is_product = false;
					}else{
						$is_product = true;
					}
                    if (isset($app_data['audience']['registration_id']) && $app_data['audience']['registration_id']) {
                        unset($app_data['audience']['tag']);
                    }
					$jpush->send($app_data['platform'], $app_data['audience'], $app_data['notification'], $app_data['message'], $is_product);
				}
			} elseif($value['type'] == 5) {
                $wechat_data = unserialize($value['content']);
                $wechat_data = $wechat_data[0];
                $model = new templateWxapp(C('config.pay_wxapp_group_appid'), C('config.pay_wxapp_group_appsecret'));
                fdump(__LINE__ . '   <-小程序推送->  ' . __FUNCTION__, 'community_send', true);
                $info = $model->sendWeixinTempMsg($wechat_data['content']);
                fdump($info, 'community_send', true);
            } elseif ($value['type'] == 6) {
                $content = unserialize($value['content']);
                $push_data = isset($content[0]) ? $content[0] : [];
                if ($push_data['message'] && $push_data['imei']) {
                    httpRequest('http://o2o-mqtt.pigcms.com/publish.php', 'POST', $push_data);
                }
            }
            $judgeTimeKey = time();
            if ($pay_scrcu_cash_open == 1 && $judgeTime <= $judgeTimeKey) {
                $judgeTime += $judgeTimeKey + 2;
                // todo 目前由于发送消息会占用大量的计划任务 影响支付查询 这里进行直接调用 避免频率过高 间隔2S
                $this->keepThread();
                try{
                    // 退款
                    $scrcu = A('Index/LowFeePayNotice');
                    $scrcu->scrcu_record();
                    $this->keepThread();
                    invoke_v20_service('\app\pay\controller\NotifyController/scrcu');
                } catch(Exception $e) {
                    fdump_api(['通知消息直接查询出错' => $e->getMessage()],'scrcu_log_err',1);
                }
            }
        }
		return true;
	}
	
	/*
	 *  添加任务
	 *
	 *  param array 消息参数
	 *
	 *  param.type   1为短信通知，2为模板消息，3为图文消息，4为APP通知
	 *
	 *
	 *
	 *  param.content 不同的业务，不同的值，传数组
	 *
	 *  param.send_time  发送时间
	 *
	 *
	 *
	 *
	 */
	public function addTask($param, $is_sub=0){
		$data['sort'] = intval($param['sort']);
		$data['type'] = $param['type'];
		$data['label'] = $param['label'] ? (is_array($param['label']) ? implode(',',$param['label']) : $param['label']) : '';
		if(!is_array($param['content'])){
			$param['content'] = array($param['content']);
		}else if(empty($param['content'][0])){
			$param['content'] = array($param['content']);
		}
		$data['content'] = serialize($param['content']);

		if($param['send_time']){
			$data['send_time'] = $param['send_time'];
		}else if(C('config.sms_send_type')){		// 如果开启了计划任务推送消息，则需要消息类的优先发送给用户。
			$data['send_time'] = time() - 3600;
		}else{
			$data['send_time'] = time();
		}
		
		//若需要不通过计划任务，直发状态下，status 传 1，自行使用返回的 task_id 传入使用。
		if($param['status']){
			$data['status'] = $param['status'];
		}
		
		$data['add_time'] = time();

        //额外记录推送表，供配送员3.0使用http轮询查询使用
        $appPushMsg = [];
        $originContent = $param['content'][0];
        $tm = time();
        $serializeParam = serialize($data);
        if ($param['type'] == 4 && $originContent['from'] == 'delivery') {
            foreach ($originContent['audience']['tag'] as $v) {
                $appPushMsg[] = [
                    'device_id' => $v,
                    'status' => 0,
                    'platform' => $originContent['platform'][0] ?? '',
                    'business_type' => $originContent['business_type'] ?? '',
                    'add_time' => $tm,
                    'data' => $serializeParam
                ];
            }
        }

        if ($param['type'] == 4 && $originContent['from'] == 'storestaff' && $originContent['business_type']) {
            fdump_api($originContent, 'app_push_msg/add_task_push_msg', true);
            foreach ($originContent['audience']['tag'] as $v) {//处理数据重复写入问题
                $pushMsg = M('App_push_msg')
                    ->where([
                        'device_id' => $v,
                        'platform' => $originContent['platform'][0] ?? '',
                        'business_type' => $originContent['business_type'] ?? '',
                        'add_time' => $tm
                    ])->field('data')->find();
                if(!empty($pushMsg)){
                    $oldContent = unserialize($pushMsg['data']);
                    $oldContent = unserialize($oldContent['content']);
                    $nowContent = unserialize($data['content']);
                    if(
                        $oldContent[0]['message']['extras']['tag_desc'] == $nowContent[0]['message']['extras']['tag_desc'] && 
                        $oldContent[0]['message']['extras']['pigcms_tag'] == $nowContent[0]['message']['extras']['pigcms_tag'] 
                    ){
                        fdump_api($originContent, 'app_push_msg/add_task_push_msg2', true);
                        continue;
                    }
                }
                $appPushMsg[] = [
                    'device_id' => $v,
                    'status' => 0,
                    'platform' => $originContent['platform'][0] ?? '',
                    'business_type' => $originContent['business_type'] ?? '',
                    'add_time' => $tm,
                    'data' => $serializeParam
                ];
            }
            if(empty($appPushMsg)){
                return false;
            }
        }

        if ($appPushMsg) {
            M('App_push_msg')->addAll($appPushMsg);
            return false;
        }
        $content_data = $param['content'];
        if($content_data[0]['message']['extras']['pigcms_tag'] == 'house_visual')
        {
            $where_visual = [];
			$where_visual['add_time'] = ['gt',time() - 15];
            $where_visual['content'][] = ['like','%'.$content_data[0]['audience']['tag'][0].'%'];
            $where_visual['content'][] = ['like','%'.$content_data[0]['message']['msg_content'].'%'];
            $Process_plan_msg = M('Process_plan_msg')->where($where_visual)->find();
            if($Process_plan_msg){
                return ;
            }
        }
        $Process_plan_msg = M('Process_plan_msg')->where(['content'=>$data['content'],'status'=>0])->find();
        if($Process_plan_msg){
            fdump($Process_plan_msg,'api/$Process_plan_msg',true);
            $task_id = $Process_plan_msg['id'];
        }else {
            $task_id = M('Process_plan_msg')->data($data)->add();
        }
		import('@.ORG.plan');
		$plan_class = new plan();
		$param = array(
			'file'=>'msg',
			'plan_time'=>$data['send_time'],
			'param'=>array(
				'id'=>$task_id,
			),
		);
		$plan_class->addTask($param, $is_sub);
		
		return $task_id;
	}
}
?>