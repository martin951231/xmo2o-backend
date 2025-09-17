<?php
class Merchant_store_staffModel extends Model
{
    public function sendMsgToVoiceBox($storeid,$msg, $beginWithShortName = true)
    {
        if ($storeid < 1 || !$msg) {
            return;
        }
        $siteShortName = C('config.site_short_name') ? C('config.site_short_name') : '';
        $beginWithShortName && $msg = $siteShortName . $msg;

        $voice_box = M('Voice_box')->field('`imei`, `voice_number`,`brand`')->where(['store_id' => $storeid, 'imei' => ['NEQ', '']])->select();
        if ($voice_box) {
            import('@.ORG.plan.plan_msg');
            $plan_msg = new plan_msg();

            foreach ($voice_box as $value) {
                //只能填数字，不填写或填写0则不播放，最多6次
                $voice_second = min($value['voice_number'], 6);
                if (!$voice_second || empty($value['imei'])) {
                    continue;
                }

                $voice_msg = str_repeat($msg . '。', $voice_second);

                if ($value['brand'] == 2) {
                    httpRequest(C('config.site_url') . '/source/mqtt_pub.php', 'POST', ['message' => $voice_msg, 'imei' => $value['imei']]);
                } else {
                    httpRequest('http://o2o-mqtt.pigcms.com/publish.php', 'POST', ['message' => $voice_msg, 'imei' => $value['imei']]);
                }
            }
        }
    }

    /*
	 *v20自动登录
    */
    public function v20Login($id)
	{
		$where['id'] = $id;
		$now_staff = $this->where($where)->find();
		if(empty($now_staff)){
			return false;
		}
		
		
		session('staff',$now_staff);
		session('merchant',null);
		return true;
	}

	public function sendMsgGroupOrder($now_order){
		$staffs = D('Merchant_store_staff')->field(true)->where(array('store_id' => $now_order['store_id'], 'is_notice' => 0, 'last_time'=>array('neq',0)))->select();
		$jpush = new Jpush();
		$href = C('config.config_site_url') . '/packapp/storestaff/index.html?gopage=group_list';
		$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));

        if(empty($staffs)){
            return true;
        }

        // PC店员通知
        $data['business_type'] = 'group';
        $data['operate_type'] = 'new_order';
        $data['platform'] = 'pc';
        $data['store_id'] = $now_order['store_id'];
        $data['add_time'] = time();
        D('Web_push_msg')->add($data);

        $this->sendMsgToVoiceBox($now_order['store_id'], C('config.group_alias_name') . L_('新订单提醒，请及时查看！'));
		foreach ($staffs as $staff) {
			if($staff['client'] == 0 && $staff['openid']){
			    // 团购新订单-提醒店员
				$model->sendTempMsg('TM00017',
                    array('href' => $href,
                        'wecha_id' => $staff['openid'],
                        'first' => L_("X1您好！X2新订单提醒！",array("X1" => $staff['name'],"X2" => C('config.group_alias_name'))), 
                        'OrderSn' => $now_order['real_orderid'],
                        'OrderStatus' => L_('待接单'),
                        'remark' => L_('请您及时处理！')),
                    $now_order['mer_id']);
			}else if($staff['client'] > 0){
				if (! C('config.staff_jpush_appkey') && ($staff['client']!=1 || !C('config.pushkit_cert'))) continue;
				if ($staff['device_id']) {
					$client = $staff['client'];
					$device_id = str_replace('-', '', $staff['device_id']);
					$audience = array('tag' => array($device_id));

                    if($staff['jpush_registrationId']){
                        $audience['registration_id'] = [$staff['jpush_registrationId']];
                    }
					
					$notification = $message = '';
					
					$title = L_('订单提醒');
					$msg = C('config.group_alias_name') . L_('新订单提醒，请及时查看！');
					
					//$voice_return = json_decode($this->voic_baidu(), true);
					//$voice_access_token = $voice_return['access_token'];
					//$voice_mp3 = 'http://tsn.baidu.com/text2audio?tex=' . $msg . '&lan=zh&tok=' . $voice_access_token . '&ctp=1&cuid=9B9A62EE-3EE8-45E0-9C06-2E3245AE3FF5';
                    $voice_mp3 = text2audio($msg);
				
					$url = C('config.config_site_url') . '/packapp/storestaff/index.html?gopage=group_list';
					$js_url = C('config.config_site_url') . '/packapp/storestaff/group_list.html';
					
					$url = str_replace('http://','https://',$url);
					$js_url = str_replace('http://','https://',$js_url);
					
					$voice_second = C('config.storestaff_app_voice_time');
					if(empty($voice_second)){
						$voice_second = 6;
					}
					
					$extra = array(
						'pigcms_tag' => 'group_order',
                        'tag_desc' => $now_order['real_orderid'],
						'voice_mp3' => $voice_mp3,
						'voice_second' => $voice_second, 
						'url' => $url,
						'js_url' => $js_url,
						'mp3_label' => 'new_group_order'.substr(md5(C('config.config_site_url')),0,16)
					);
					
					$notification = $jpush->createBody($client, $title, $msg, $extra, 'group_sound.caf');
					$message = $jpush->createMsg($title, $msg, $extra);
					
					$columns = array();
					$columns['platform'] = $client == 1 ? array('ios') : array('android');
					$columns['audience'] = $audience;
					$columns['notification'] = $notification;
					$columns['message'] = $message;
					$columns['from'] = 'storestaff';
                    $staff['is_app_native'] && $columns['business_type'] = 'new_order_group';
					import('@.ORG.plan.plan_msg');
					$plan_msg = new plan_msg();
					$plan_msg->addTask(array('type' => '4', 'content' => array($columns)));
				}
			}
		}
	}
	public function sendMsgStoreOrder($now_order,$score=0){
		$staffs = D('Merchant_store_staff')->field(true)->where(array('store_id' => $now_order['store_id'], 'is_notice' => 0, 'last_time'=>array('neq',0)))->select();
		$jpush = new Jpush();
		if($now_order['from_plat'] == 2 || $now_order['from_plat'] == 3){
            $type = 'store';
			$href = C('config.config_site_url') . '/packapp/storestaff/index.html?gopage=cashier';
		}else{
            $type = 'cash';
			$href = C('config.config_site_url') . '/packapp/storestaff/index.html?gopage=store';
		}
        
        // PC店员通知
        $data['business_type'] = $type;
        $data['operate_type'] = 'new_order';
        $data['platform'] = 'pc';
        $data['store_id'] = $now_order['store_id'];
        $data['add_time'] = time();
        D('Web_push_msg')->add($data);

        if($score > 0){
            $this->sendMsgToVoiceBox($now_order['store_id'],  C('config.cash_alias_name') . L_('收款成功') . floatval($now_order['price']) . C('config.Currency_txt') . "。本次消费返" . getFormatNumber($score) . C('config.score_name'));
        }else{
            $this->sendMsgToVoiceBox($now_order['store_id'],  C('config.cash_alias_name') . L_('收款成功').floatval($now_order['price']).C('config.Currency_txt'));
        }
		$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
		foreach ($staffs as $staff) {
			if($now_order['from_plat'] == 2 || $now_order['from_plat'] == 3){
				$msg = L_('店员收银收款成功').floatval($now_order['price']).C('config.Currency_txt');
			}else{
				$msg = C('config.cash_alias_name') . L_('收款成功').floatval($now_order['price']).C('config.Currency_txt');
			}
			if($now_order['ticketNum']){
				$msg.= L_("，含X1张票",array("X1" => $now_order['ticketNum'])); 
				if(floatval($now_order['ticketInsure'])){
					$msg.= L_('，含保险');
				}else{
					if(C('config.store_ticket_have')){
						$database_store_trade_ticket = D('Merchant_store_trade_ticket');
						$condition_store_trade_ticket = array('store_id'=>$now_order['store_id']);
						$store_trade_ticket = $database_store_trade_ticket->where($condition_store_trade_ticket)->find();
						if($store_trade_ticket['have_insure']){
							$msg.= L_('，不含保险');
						}
					}				
				}
				
			}
			
			if($staff['client'] == 0 && $staff['openid']){
                // 模板-提醒店员
                $model->sendTempMsg('TM00017',
                    array('href' => $href,
                        'wecha_id' => $staff['openid'],
                        'first' => $staff['name'] . L_('您好！') . $msg,
                        'OrderSn' => $now_order['orderid'],
                        'OrderStatus' => L_('待处理'),
                        'remark' => L_('请您及时处理！')),
                    $now_order['mer_id']);
			}else if($staff['client'] > 0){
				if (! C('config.staff_jpush_appkey') && ($staff['client']!=1 || !C('config.pushkit_cert'))) continue;
				if ($staff['device_id']) {
					$client = $staff['client'];
					$device_id = str_replace('-', '', $staff['device_id']);
					$audience = array('tag' => array($device_id));
                    if($staff['jpush_registrationId']){
                        $audience['registration_id'] = [$staff['jpush_registrationId']];
                    }
					
					$notification = $message = '';
					
					$title = L_('订单提醒');

					//$voice_return = json_decode($this->voic_baidu(), true);
					//$voice_access_token = $voice_return['access_token'];
					//$voice_mp3 = 'http://tsn.baidu.com/text2audio?tex=' . $msg . '&lan=zh&tok=' . $voice_access_token . '&ctp=1&cuid=9B9A62EE-3EE8-45E0-9C06-2E3245AE3FF5';
                    $voice_mp3 = text2audio($msg);
					
					if($now_order['from_plat'] == 2 || $now_order['from_plat'] == 3){
						$url = C('config.config_site_url') . '/packapp/storestaff/index.html?gopage=cashier';
						$js_url = C('config.config_site_url') . '/packapp/storestaff/cashier.html';
					}else{
						$url = C('config.config_site_url') . '/packapp/storestaff/index.html?gopage=store';
						$js_url = C('config.config_site_url') . '/packapp/storestaff/store.html';
					}
					
					$url = str_replace('http://','https://',$url);
					$js_url = str_replace('http://','https://',$js_url);
					
					$voice_second = C('config.storestaff_app_voice_time');
					if(empty($voice_second)){
						$voice_second = 6;
					}
					
					$extra = array(
						'pigcms_tag' => 'store_order_'.$now_order['from_plat'],
                        'tag_desc' => $now_order['real_orderid'],
						'voice_mp3' => $voice_mp3,
						'voice_second' => $voice_second, 
						'url' => $url,
						'js_url' => $js_url,
						'mp3_label' => 'new_store_order'.substr(md5($voice_mp3),8,16).substr(md5(C('config.config_site_url')),8,16)
					);
					
					$notification = $jpush->createBody($client, $title, $msg, $extra, 'store_pay.caf');
					$message = $jpush->createMsg($title, $msg, $extra);
					
					$columns = array();
					$columns['platform'] = $client == 1 ? array('ios') : array('android');
					$columns['audience'] = $audience;
					$columns['notification'] = $notification;
					$columns['message'] = $message;
					$columns['from'] = 'storestaff';
                    $staff['is_app_native'] && $columns['business_type'] = ($now_order['from_plat'] == 2 || $now_order['from_plat'] == 3) ? 'new_order_store_arrival' : 'new_order_cash';
					import('@.ORG.plan.plan_msg');
					$plan_msg = new plan_msg();
					$plan_msg->addTask(array('type' => '4', 'content' => array($columns)));
				}
			}
		}
	}

	public function sendMsgAppointOrder($now_order){
		$staffs = D('Merchant_store_staff')->field(true)->where(array('store_id' => $now_order['store_id'], 'is_notice' => 0, 'last_time'=>array('neq',0)))->select();
        
        // PC店员通知
        $data['business_type'] = 'appoint';
        $data['operate_type'] = 'new_order';
        $data['platform'] = 'pc';
        $data['store_id'] = $now_order['store_id'];
        $data['add_time'] = time();
        D('Web_push_msg')->add($data);

		$jpush = new Jpush();
		$href = C('config.config_site_url') . '/packapp/storestaff/index.html?gopage=appoint';
        $this->sendMsgToVoiceBox($now_order['store_id'], C('config.appoint_alias_name') . L_('新订单提醒，请及时查看！'));
		$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
		foreach ($staffs as $staff) {
			if($staff['client'] == 0 && $staff['openid']){
                $model->sendTempMsg('TM00017',
                    array('href' => $href,
                        'wecha_id' => $staff['openid'],
                        'first' => L_("X1您好！X2新订单提醒！",array("X1" => $staff['name'],"X2" => C('config.appoint_alias_name'))), 
                        'OrderSn' => $now_order['orderid'],
                        'OrderStatus' => L_('待处理'),
                        'remark' => L_('请您及时处理！')),
                    $now_order['mer_id']);
			}else if($staff['client'] > 0){
				if (! C('config.staff_jpush_appkey') && ($staff['client']!=1 || !C('config.pushkit_cert'))) continue;
				if ($staff['device_id']) {
					$client = $staff['client'];
					$device_id = str_replace('-', '', $staff['device_id']);
					$audience = array('tag' => array($device_id));
                    if($staff['jpush_registrationId']){
                        $audience['registration_id'] = [$staff['jpush_registrationId']];
                    }

					$notification = $message = '';

					$title = C('config.appoint_alias_name') . L_('新订单提醒');
					$msg = C('config.appoint_alias_name') . L_('新订单提醒，请及时查看！');

                    $voice_mp3 = C('config.storestaff_appoint_new_order');
                    if (substr($voice_mp3, -3) == 'mp3') {
                        $voice_mp3 = replace_file_domain($voice_mp3);
                    } else {
                        $voice_mp3 = text2audio($msg);
                    }

					$url = C('config.config_site_url') . '/packapp/storestaff/index.html?gopage=appoint';
					$js_url = C('config.config_site_url') . '/packapp/storestaff/appoint.html';
					
					$url = str_replace('http://','https://',$url);
					$js_url = str_replace('http://','https://',$js_url);
					
					$voice_second = C('config.storestaff_app_voice_time');
					if(empty($voice_second)){
						$voice_second = 6;
					}

                    $voiceMd5File = md5_file($voice_mp3);

					$extra = array(
						'pigcms_tag' => 'appoint_order',
						'tag_desc' => $now_order['order_id'],
						'voice_mp3' => $voice_mp3,
						'voice_second' => $voice_second,
						'url' => $url,
						'js_url' => $js_url,
						'mp3_label' => 'new_appoint_order'. ($voiceMd5File ? $voiceMd5File : substr(md5(C('config.config_site_url')), 0, 16))
					);

					$notification = $jpush->createBody($client, $title, $msg, $extra,  'sound.caf');
					$message = $jpush->createMsg($title, $msg, $extra);

					$columns = array();
					$columns['platform'] = $client == 1 ? array('ios') : array('android');
					$columns['audience'] = $audience;
					$columns['notification'] = $notification;
					$columns['message'] = $message;
					$columns['from'] = 'storestaff';
                    $staff['is_app_native'] && $columns['business_type'] = 'new_order_appoint';
					import('@.ORG.plan.plan_msg');
					$plan_msg = new plan_msg();
					$plan_msg->addTask(array('type' => '4', 'content' => array($columns)));
				}
			}
		}
	}

	//$isAutoOrder  是否自动接单 true:是 false:否
    public function sendMsg($storeId,$order=array(),$isAutoOrder = false)
    {
        $staffs = $this->field(true)->where(array('store_id' => $storeId, 'is_notice' => 0, 'last_time'=>array('neq',0)))->select();
		
		//外卖订单店员推送日志，全都记录
		fdump([$storeId, $staffs, $order, $this->getLastSql()], 'api/log/'.date('Ymd').'/shopStaffSendMsg', true);
		
		if($staffs){
			if (C('config.open_multilingual')) {
				$merchant_lang = M('Merchant')->where(['mer_id' => $staffs[0]['token']])->getField('merchant_default_lang');
				if($merchant_lang != C('config.system_lang')){
					C('config.tmp_system_lang',$merchant_lang);
					$tmp_config = D('Config')->get_config($merchant_lang);
				}
			}
		}else{
			fdump_html(['店铺ID' => $storeId, '商家ID' => $order['mer_id'], '订单ID' => $order['real_orderid']], 'api/log/noStaffMsg', true);
		}
		
        // PC店员通知
        $data['business_type'] = $order['order_from'] != 1 ? 'shop' : 'mall';
        $data['operate_type'] = 'new_order';
        $data['platform'] = 'pc';
        $data['store_id'] = $order['store_id'];
        $data['add_time'] = time();
        D('Web_push_msg')->add($data);

        $jpush = new Jpush();
        $href = C('config.config_site_url') . '/packapp/storestaff/index.html';
        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
        if ($order['real_orderid']) {
            $real_orderid = $order['real_orderid'];
        } else {
            $real_orderid = '';
        }
        /*区分商城*/
        $alias_name = $tmp_config ? $tmp_config['shop_alias_name'] : C('config.shop_alias_name');
        $shop_type=1;
        if($order['order_from']==1){
        	$alias_name = $tmp_config ? $tmp_config['mall_alias_name'] : C('config.mall_alias_name');
        	$shop_type=2;
        }
        if ($isAutoOrder) {
            $this->sendMsgToVoiceBox($storeId, L_('你有一笔新的X1订单待处理，已为你自动接单', ['X1' => $alias_name]));
        } else {
            $this->sendMsgToVoiceBox($storeId, L_('X1新订单提醒，请及时查看！', ['X1' => $alias_name]));
        }
        
        foreach ($staffs as $staff) {
            if ($staff['client'] == 0 && $staff['openid']) {
                $model->sendTempMsg('TM00017',
                    array('href' => $href,
                        'wecha_id' => $staff['openid'],
                        'first' => L_("X1您好！X2新订单提醒！",array("X1" => $staff['name'],"X2" => $alias_name)), 
                        'OrderSn' => $real_orderid,
                        'OrderStatus' => L_('待处理'),
                        'remark' => L_('请您及时处理！')),
                    $order['mer_id']);
            } elseif($staff['client'] > 0){
                if (! C('config.staff_jpush_appkey') && ($staff['client']!=1 || !C('config.pushkit_cert'))) continue;
                if ($staff['device_id']) {
                    $client = $staff['client'];
                    if ($client == 1) {
                        $device_id = str_replace('-', '', $staff['device_id']);
                        $audience = array('tag' => array($device_id));
                    } else {
                        $audience = array('tag' => array($staff['device_id']));
                    }

                    if($staff['jpush_registrationId']){
                        $audience['registration_id'] = [$staff['jpush_registrationId']];
                    }

                    $notification = $message = '';

                    if ($isAutoOrder) {
                        $title = L_('X1新订单提醒', ['X1' => $alias_name]);
                        $msg = L_('你有一笔新的订单待处理，已为你自动接单');
                        $voice_mp3 = C('config.storestaff_shop_is_auto_new_order');
                    } else {
                        $title = L_('X1新订单提醒', ['X1' => $alias_name]);
                        $msg = L_('X1新订单提醒，请及时查看！', ['X1' => $alias_name]);
                        $voice_mp3 = C('config.storestaff_shop_new_order');
                    }

                    if (substr($voice_mp3, -3) == 'mp3') {
                        $voice_mp3 = file_domain() . $voice_mp3;
                    } else {
                        //$voice_return = json_decode($this->voic_baidu(), true);
                        //$voice_access_token = $voice_return['access_token'];
                        //$voice_mp3 = 'http://tsn.baidu.com/text2audio?tex=' . $msg . '&lan=zh&tok=' . $voice_access_token . '&ctp=1&cuid=9B9A62EE-3EE8-45E0-9C06-2E3245AE3FF5';
                        $voice_mp3 = text2audio($msg);
                    }

                    if($shop_type==2){
                    	$url = C('config.config_site_url') . '/packapp/storestaff/index.html?gopage=mall' ;
                    	$js_url = C('config.config_site_url') . '/packapp/storestaff/mall.html';
                    }else{
                    	$url = C('config.config_site_url') . '/packapp/storestaff/index.html?gopage=shop';

                    	if($staff['app_version'] < 30200){
                        	$js_url = C('config.config_site_url') . '/packapp/storestaff/shop.html';
                    	}else{
                    		$js_url = 'miniapp://__UNI__8799035&pages/shop/order/order?tab_status=new';
                    	}
                    }
                    
					
					$url = str_replace('http://','https://',$url);
					$js_url = str_replace('http://','https://',$js_url);
					
					$voice_second = C('config.storestaff_app_voice_time');
					if(empty($voice_second)){
						$voice_second = 6;
					}
                    
                    $extra = array(
                        'pigcms_tag' => 'shop_order',
                        'tag_desc' => $order['real_orderid'],
                        'voice_mp3' => $voice_mp3,
                        'voice_second' => $voice_second,
                        'url' => $url,
                        'js_url' => $js_url,
						'mp3_label' => 'new_shop_order'.substr(md5($voice_mp3),8,16).substr(md5(C('config.config_site_url')),8,16)
                    );
                    
                    $notification = $jpush->createBody($client, $title, $msg, $extra, 'group_sound.caf');
                    $message = $jpush->createMsg($title, $msg, $extra);
                    
                    $columns = array();
                    $columns['platform'] = $client == 1 ? array('ios') : array('android');
                    $columns['audience'] = $audience;
                    $columns['notification'] = $notification;
                    $columns['message'] = $message;
                    $columns['from'] = 'storestaff';
                    $staff['is_app_native'] && $columns['business_type'] = $shop_type == 1 ? 'new_order_shop' : 'new_order_mall';
					import('@.ORG.plan.plan_msg');
                    $plan_msg = new plan_msg();
                    $plan_msg->addTask(array('type' => '4', 'content' => array($columns)));
                }
            }
        }
		
		C('config.tmp_system_lang',null);
    }
    
    
    public function sendMsgFoodShop($order)
    {
        $staffs = array();
        if ($order['table_id']) {
            $table = D('Foodshop_table')->field(true)->where(array('id' => $order['table_id']))->find();
            if ($table && $table['staff_id']) {
                $staffs = $this->field(true)->where(array('id' => $table['staff_id']))->select();
            }
        }
        if (empty($staffs)) {
            $staffs = $this->field(true)->where(array('store_id' => $order['store_id'], 'is_notice' => 0, 'last_time'=>array('neq',0)))->select();
        }
        $isNewOrder = false;//新订单提醒
        $jpush = new Jpush();
        if ($order['status'] == 100) {
            $href = C('config.config_site_url') . '/packapp/storestaff/foodshop_order_list.html?status=1&from=message';
            $title = L_('客人呼叫服务');
            $msg = L_("【X1】的客人呼叫服务，请您及时提供服务",array("X1" => $table['name'])); 
			$mp3_label = 'new_foodshop_order_hujiaofuwu_'.substr(md5($table['name']),0,16).substr(md5(C('config.config_site_url')),0,16);
        } elseif ($order['status'] == 1) {
        	if ($order['order_from'] == 2) { //店员下单不提示
	    		return false;
	    	}
        	$isNewOrder = true;
    	 	$href = C('config.config_site_url') . '/packapp/storestaff/foodshop_order_list.html?status=1&from=message';
            $title = C('config.meal_alias_name') . L_('新订单提醒');
            $msg = C('config.meal_alias_name') . L_('新订单提醒，请及时查看！');
            $mp3_label = 'new_foodshop_order_neworder_'.substr(md5(C('config.config_site_url')),0,16);
            $this->sendMsgToVoiceBox($order['store_id'], $msg);
        } elseif ($order['status'] == 3) {
        	if ($order['order_from'] == 2) { //店员下单不提示
	    		return false;
	    	}

	    	if ($order['is_foodshop_new'] == 1) {
	    		// 堂扫
            	$href = C('config.config_site_url') . '/packapp/storestaff/foodshop_order_list_new.html?from=message';
	    	}else{
            	$href = C('config.config_site_url') . '/packapp/storestaff/foodshop_order_list.html?status=3&from=message';
	    	}
            $title = C('config.meal_alias_name') .L_('订单支付成功');
            $msg = C('config.meal_alias_name') . L_('订单支付成功，请及时处理！');
            $mp3_label = 'new_foodshop_order_neworder_'.substr(md5(C('config.config_site_url')),0,16);
        }else {
            $href = C('config.config_site_url') . '/packapp/storestaff/foodshop_order_list.html?status=4&from=message';
            $title = C('config.meal_alias_name') . L_('上菜提醒');
            $msg = C('config.meal_alias_name') . L_('上菜提醒，请及时确认菜品！');
			$mp3_label = 'new_foodshop_order_shangcaitixing_'.substr(md5(C('config.config_site_url')),0,16);
        }

        if($isNewOrder){
            // PC店员通知
            $data['business_type'] = 'foodshop';
            $data['operate_type'] = 'new_order';
            $data['platform'] = 'pc';
            $data['store_id'] = $order['store_id'];
            $data['add_time'] = time();
            D('Web_push_msg')->add($data);
        }

        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
        foreach ($staffs as $staff) {
        	if($staff['use_lang']){
        		C('config.tmp_system_lang', $staff['use_lang']);
        		$titlel = L_($title);
        		$hello = L_('您好！');
        		fdump($staff, "msg_lumin",1);
        		fdump($msg, "msg_lumin",1);
        		$msgl = L_($msg);
        		fdump($msgl, "msg_lumin",1);
        	}
            if ($staff['client'] == 0 && $staff['openid']) {
                $model->sendTempMsg('TM00017',
                    array('href' => $href,
                        'wecha_id' => $staff['openid'],
                        'first' => $staff['name'] . $hello . $titlel,
                        'OrderSn' => $order['real_orderid'],
                        'OrderStatus' => L_('待处理'),
                        'remark' => L_('请您及时处理！')),
                    $order['mer_id']);
            } elseif($staff['client'] > 0){
                if (! C('config.staff_jpush_appkey') && ($staff['client']!=1 || !C('config.pushkit_cert'))) continue;
                if ($staff['device_id']) {
                    $client = $staff['client'];
                    if ($client == 1) {
                        $device_id = str_replace('-', '', $staff['device_id']);
                        $audience = array('tag' => array($device_id));
                    } else {
                        $audience = array('tag' => array($staff['device_id']));
                    }
                    if($staff['jpush_registrationId']){
                        $audience['registration_id'] = [$staff['jpush_registrationId']];
                    }
                    $notification = $message = '';
                    
                    
                    
                    //$voice_return = json_decode($this->voic_baidu(), true);
                    //$voice_access_token = $voice_return['access_token'];
                    //$voice_mp3 = 'http://tsn.baidu.com/text2audio?tex=' . $msgl . '&lan=zh&tok=' . $voice_access_token . '&ctp=1&cuid=9B9A62EE-3EE8-45E0-9C06-2E3245AE3FF5';
                    $voice_mp3 = text2audio($msgl);
                    
                    $url = C('config.config_site_url') . '/packapp/storestaff/index.html?gopage=foodshop';
                    $js_url = $href;
                    
					$url = str_replace('http://','https://',$url);
					$js_url = str_replace('http://','https://',$js_url);
					
					$voice_second = C('config.storestaff_app_voice_time');
					if(empty($voice_second)){
						$voice_second = 6;
					}
                    
                    $extra = array(
                        'pigcms_tag' => 'foodshop_order',
                        'tag_desc' => $order['real_orderid'],
                        'voice_mp3' => $voice_mp3,
                        'voice_second' => $voice_second,
                        'url' => $url,
                        'js_url' => $js_url,
						'mp3_label' => $mp3_label ? $mp3_label : 'new_foodshop_order_new'.substr(md5(C('config.config_site_url')),0,16)
                    );
                    
                    $notification = $jpush->createBody($client, $titlel, $msgl, $extra, 'sound.caf');
                    $message = $jpush->createMsg($titlel, $msgl, $extra);
                    
                    $columns = array();
                    $columns['platform'] = $client == 1 ? array('ios') : array('android');
                    $columns['audience'] = $audience;
                    $columns['notification'] = $notification;
                    $columns['message'] = $message;
                    $columns['from'] = 'storestaff';
                    ($staff['is_app_native'] && $isNewOrder) && $columns['business_type'] = 'new_order_appoint';
					import('@.ORG.plan.plan_msg');
                    $plan_msg = new plan_msg();
                    $plan_msg->addTask(array('type' => '4', 'content' => array($columns)));
                }
            }
        }
    }
	
	private function voic_baidu()
    {
        static $return;

        if (empty($return)) {
            $voic_baidu = "https://openapi.baidu.com/oauth/2.0/token?grant_type=client_credentials&client_id=XU7nTWQzS9vn32bckmrhHbTu&client_secret=1375720f23eed9b7b787e728de5dd1c2";
            import('ORG.Net.Http');
            $return = Http::curlGet($voic_baidu);
        }
        return $return;
    }


    /**
     * 预订单待接单提醒
     * @param $storeId
     * @param array $order
     * @author: 张涛
     * @date: 2020/11/23
     */
    public function sendBookOrderRemind($storeId, $order = array())
    {
        $staffs = $this->field(true)->where(array('store_id' => $storeId, 'is_notice' => 0, 'last_time' => array('neq', 0)))->select();
        if ($staffs) {
            if (C('config.open_multilingual')) {
                $merchant_lang = M('Merchant')->where(['mer_id' => $staffs[0]['token']])->getField('merchant_default_lang');
                if ($merchant_lang != C('config.system_lang')) {
                    C('config.tmp_system_lang', $merchant_lang);
                    $tmp_config = D('Config')->get_config($merchant_lang);
                }
            }
        }

        $jpush = new Jpush();
        $href = C('config.config_site_url') . '/packapp/storestaff/index.html';
        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
        if ($order['real_orderid']) {
            $real_orderid = $order['real_orderid'];
        } else {
            $real_orderid = '';
        }
        /*区分商城*/
        $alias_name = $tmp_config ? $tmp_config['shop_alias_name'] : C('config.shop_alias_name');
        $shop_type = 1;
        if ($order['order_from'] == 1) {
            $alias_name = $tmp_config ? $tmp_config['mall_alias_name'] : C('config.mall_alias_name');
            $shop_type = 2;
        }

        // PC店员通知
        $data['business_type'] = 'shop';
        $data['operate_type'] = 'new_order';
        $data['platform'] = 'pc';
        $data['store_id'] = $order['store_id'];
        $data['add_time'] = time();
        D('Web_push_msg')->add($data);

        $title = L_('预订单到期提醒');
        $msg = L_('您的X1预订单快到时间啦，请及时备餐', ['X1' => $alias_name]);

        $this->sendMsgToVoiceBox($storeId, $msg);

        $voice_mp3 = C('config.shop_storestaff_booking_order');
        if (substr($voice_mp3, -3) == 'mp3') {
            $voice_mp3 = file_domain() . $voice_mp3;
        } else {
            //$voice_return = json_decode($this->voic_baidu(), true);
            //$voice_access_token = $voice_return['access_token'];
            //$voice_mp3 = 'http://tsn.baidu.com/text2audio?tex=' . $msg . '&lan=zh&tok=' . $voice_access_token . '&ctp=1&cuid=9B9A62EE-3EE8-45E0-9C06-2E3245AE3FF5';
            $voice_mp3 = text2audio($msg);
        }

        if ($shop_type == 2) {
            $url = C('config.config_site_url') . '/packapp/storestaff/index.html?gopage=mall';
            $js_url = C('config.config_site_url') . '/packapp/storestaff/mall.html';
        } else {
            $url = C('config.config_site_url') . '/packapp/storestaff/index.html?gopage=shop';
            $js_url = C('config.config_site_url') . '/packapp/storestaff/shop.html';
        }
        $voice_second = C('config.storestaff_app_voice_time');
        if (empty($voice_second)) {
            $voice_second = 6;
        }
        foreach ($staffs as $staff) {
            if ($staff['client'] > 0) {
                if (!C('config.staff_jpush_appkey') && ($staff['client'] != 1 || !C('config.pushkit_cert'))) continue;
                if ($staff['device_id']) {
                    $client = $staff['client'];
                    if ($client == 1) {
                        $device_id = str_replace('-', '', $staff['device_id']);
                        $audience = array('tag' => array($device_id));
                    } else {
                        $audience = array('tag' => array($staff['device_id']));
                    }
                    if($staff['jpush_registrationId']){
                        $audience['registration_id'] = [$staff['jpush_registrationId']];
                    }
                    $notification = $message = '';

                    $url = str_replace('http://', 'https://', $url);
                    $js_url = str_replace('http://', 'https://', $js_url);

                    $extra = array(
                        'pigcms_tag' => 'shop_order',
                        'tag_desc' => $order['real_orderid'],
                        'voice_mp3' => $voice_mp3,
                        'voice_second' => $voice_second,
                        'url' => $url,
                        'js_url' => $js_url,
                        'mp3_label' => 'booking_order_remind' . substr(md5($voice_mp3), 8, 16) . substr(md5(C('config.config_site_url')), 8, 16)
                    );

                    $notification = $jpush->createBody($client, $title, $msg, $extra, 'group_sound.caf');
                    $message = $jpush->createMsg($title, $msg, $extra);

                    $columns = array();
                    $columns['platform'] = $client == 1 ? array('ios') : array('android');
                    $columns['audience'] = $audience;
                    $columns['notification'] = $notification;
                    $columns['message'] = $message;
                    $columns['from'] = 'storestaff';
                    import('@.ORG.plan.plan_msg');
                    $plan_msg = new plan_msg();
                    $plan_msg->addTask(array('type' => '4', 'content' => array($columns)));
                }
            }
        }
        C('config.tmp_system_lang', null);
    }

    /**
     * 发送用户申请售后语言推送
     * @author: 张涛
     * @date: 2020/11/24
     */
    public function sendOrderApplyRefundMsg($storeId, $order = array())
    {
        $staffs = $this->field(true)->where(array('store_id' => $storeId, 'is_notice' => 0, 'last_time' => array('neq', 0)))->select();
        if ($staffs) {
            if (C('config.open_multilingual')) {
                $merchant_lang = M('Merchant')->where(['mer_id' => $staffs[0]['token']])->getField('merchant_default_lang');
                if ($merchant_lang != C('config.system_lang')) {
                    C('config.tmp_system_lang', $merchant_lang);
                    $tmp_config = D('Config')->get_config($merchant_lang);
                }
            }
        }

        // PC店员通知
        $data['business_type'] = $order['order_from'] != 1 ? 'shop' : 'mall';
        $data['operate_type'] = 'refund';
        $data['platform'] = 'pc';
        $data['store_id'] = $order['store_id'];
        $data['add_time'] = time();
        D('Web_push_msg')->add($data);

        $jpush = new Jpush();
        $href = C('config.config_site_url') . '/packapp/storestaff/index.html';
        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
        if ($order['real_orderid']) {
            $real_orderid = $order['real_orderid'];
        } else {
            $real_orderid = '';
        }
        /*区分商城*/
        $alias_name = $tmp_config ? $tmp_config['shop_alias_name'] : C('config.shop_alias_name');
        $shop_type = 1;
        if ($order['order_from'] == 1) {
            $alias_name = $tmp_config ? $tmp_config['mall_alias_name'] : C('config.mall_alias_name');
            $shop_type = 2;
        }

        $title = L_('申请售后订单提醒');
        $msg = L_('您有新的X1申请退款订单，请及时查收', ['X1' => $alias_name]);

        $this->sendMsgToVoiceBox($storeId, $msg);

        $voice_mp3 = C('config.shop_storestaff_order_refund');
        if (substr($voice_mp3, -3) == 'mp3') {
            $voice_mp3 = file_domain() . $voice_mp3;
        } else {
            //$voice_return = json_decode($this->voic_baidu(), true);
            //$voice_access_token = $voice_return['access_token'];
            //$voice_mp3 = 'http://tsn.baidu.com/text2audio?tex=' . $msg . '&lan=zh&tok=' . $voice_access_token . '&ctp=1&cuid=9B9A62EE-3EE8-45E0-9C06-2E3245AE3FF5';
            $voice_mp3 = text2audio($voice_mp3 ?: $msg);
        }

        $voice_second = C('config.storestaff_app_voice_time');
        if (empty($voice_second)) {
            $voice_second = 6;
        }

        foreach ($staffs as $staff) {
            if ($staff['client'] > 0) {
                if (!C('config.staff_jpush_appkey') && ($staff['client'] != 1 || !C('config.pushkit_cert'))) continue;
                if ($staff['device_id']) {
                    $client = $staff['client'];
                    if ($client == 1) {
                        $device_id = str_replace('-', '', $staff['device_id']);
                        $audience = array('tag' => array($device_id));
                    } else {
                        $audience = array('tag' => array($staff['device_id']));
                    }
                    if($staff['jpush_registrationId']){
                        $audience['registration_id'] = [$staff['jpush_registrationId']];
                    }
                    $notification = $message = '';

                    if ($shop_type == 2) {
                        $url = C('config.config_site_url') . '/packapp/storestaff/index.html?gopage=mall';
                        $js_url = C('config.config_site_url') . '/packapp/storestaff/mall.html';
                    } else {
                        $url = C('config.config_site_url') . '/packapp/storestaff/index.html?gopage=shop';
                        $js_url = C('config.config_site_url') . '/packapp/storestaff/shop.html';
                    }
                    $url = str_replace('http://', 'https://', $url);
                    $js_url = str_replace('http://', 'https://', $js_url);

                    $extra = array(
                        'pigcms_tag' => 'shop_order',
                        'tag_desc' => $order['real_orderid'],
                        'voice_mp3' => $voice_mp3,
                        'voice_second' => $voice_second,
                        'url' => $url,
                        'js_url' => $js_url,
                        'mp3_label' => 'send_order_apply_refund' . substr(md5($voice_mp3), 8, 16) . substr(md5(C('config.config_site_url')), 8, 16)
                    );

                    $notification = $jpush->createBody($client, $title, $msg, $extra, 'group_sound.caf');
                    $message = $jpush->createMsg($title, $msg, $extra);

                    $columns = array();
                    $columns['platform'] = $client == 1 ? array('ios') : array('android');
                    $columns['audience'] = $audience;
                    $columns['notification'] = $notification;
                    $columns['message'] = $message;
                    $columns['from'] = 'storestaff';
                    import('@.ORG.plan.plan_msg');
                    $plan_msg = new plan_msg();
                    $plan_msg->addTask(array('type' => '4', 'content' => array($columns)));
                }
            }
        }
        C('config.tmp_system_lang', null);
    }
}
?>