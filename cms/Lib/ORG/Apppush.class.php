<?php
class Apppush
{
	public function send($order, $order_type)
	{
		//计划任务发送排序
		$sendSort = 10;
		$sendStatus = 0;
        $login_log = D("Appapi_app_login_log")->field(true)->where(array(
            'uid' => $order['uid'],
            'client'=>['neq',0],
			'device_id'=>['neq','packapp']
        ))->order("pigcms_id DESC")->find();
		if (empty($login_log)) return false;
		$client = $login_log['client'];
		if ($client == 1) {
			$device_id = str_replace('-', '', $login_log['device_id']);
			$audience = array('tag' => array($device_id));
		} else {
			$audience = array('tag' => array($login_log['device_id']));
		}
        if(!empty($login_log['jpush_registrationId'])){
            $audience['registration_id'] = [$login_log['jpush_registrationId']];
        }
		$notification = $message = '';
		$jpush = new Jpush();
		switch ($order_type) {
			case 'shop':
				$status = array('下单成功', '支付成功', '店员已接单', '配送员已接单', '配送员已到店' , '配送员配送中', '配送结束', '店员验证消费', '完成评论', '已完成退款', '已取消订单', '商家分配自提点', '商家发货到自提点', '自提点已接货', '自提点已发货', '您在自提完成取货', 30 => '店员修改价格', 31 => '配送员放弃配送', 34 => '订单已被取消', 35 => '配送员已接单', 36 => '更换配送员');
				if($order['order_from']==1){
					$extra = array('pigcms_tag' => 'shop_order', 'tag_desc' => $order['real_orderid'], 'url' => C('config.site_url') . '/wap.php?c=Mall&a=status&order_id=' . $order['order_id']);
					$title = C('config.mall_alias_name') . L_('订单状态变更提醒');
					$msg = C('config.mall_alias_name') . L_('订单状态修改成【' . $status[$order['status']] . '】，更多信息请查看详情！');
				}else{
					$extra = array('pigcms_tag' => 'shop_order', 'tag_desc' => $order['real_orderid'], 'url' => C('config.site_url') . '/wap.php?c=Shop&a=status&order_id=' . $order['order_id']);
					$title = C('config.shop_alias_name') . L_('订单状态变更提醒');
					$msg = C('config.shop_alias_name') . L_('订单状态修改成【' . $status[$order['status']] . '】，更多信息请查看详情！');
				}
				$notification = $jpush->createBody(3, $title, $msg, $extra);
				$message = $jpush->createMsg($title, $msg, $extra);
				break;
            case 'group':
                $status = array(0=>'下单成功',1=> '支付成功',  2=>'完成评论',3=>'拼团成功', 4=>'已完成退款', 5=>'已取消订单',6=>'店员已发货' , 7=>'店员验证消费',8=>'开团成功',9=>'参团成功');
                $extra = array('pigcms_tag' => 'group_order', 'tag_desc' => $order['real_orderid'], 'url' => C('config.site_url') . '/wap.php?c=My&a=group_order&order_id=' . $order['order_id']);
                $title = C('config.group_alias_name') . L_('订单状态变更提醒');
                $msg = C('config.group_alias_name') . L_('订单状态修改成【' . $status[$order['status']] . '】，更多信息请查看详情！');
                $notification = $jpush->createBody(3, $title, $msg, $extra);
                $message = $jpush->createMsg($title, $msg, $extra);
                break;
            case 'publish':
                $status = array(2=>'订单支付成功',4=> '订单已完成',  8=>'配送员已接单', 9=>'配送员送货中');
                $catgory_type = array(2=>'帮我买', 3=>'帮我送');
                $extra = array('pigcms_tag' => 'publish_order', 'tag_desc' => $order['order_sn'], 'url' => C('config.site_url') . '/wap.php?g=Wap&c=Service&a=price_list&publish_id=' . $order['order_id']);
                $title = $catgory_type[$order['catgory_type']] . L_('订单状态变更提醒');
                $msg = $catgory_type[$order['catgory_type']] . L_('订单状态修改成【' . $status[$order['status']] . '】，更多信息请查看详情！');
                $notification = $jpush->createBody(3, $title, $msg, $extra);
                $message = $jpush->createMsg($title, $msg, $extra);
                break;
            case 'face_door':
                $extra = array('pigcms_tag' => 'face_door', 'tag_desc' => $order['device_sn'], 'url' => C('config.site_url') . '/wap.php?g=Wap&c=My&a=my_house_tip');
                $title = $order['title'];
                $msg = $order['msg'];
                $notification = $jpush->createBody(3, $title, $msg, $extra);
                $message = $jpush->createMsg($title, $msg, $extra);
                break;
            case 'liveshow_remind':
                $extra = array('pigcms_tag' => 'liveshow_remind', 'tag_desc' => $order['device_sn'], 'url' => C('config.site_url') . '/wap.php?g=Wap&c=My&a=my_house_tip');
                $title = $order['title'];
                $msg = $order['msg'];
                $notification = $jpush->createBody(3, $title, $msg, $extra);
                $message = $jpush->createMsg($title, $msg, $extra);
                break;
            case 'house_notice':
                $extra = array('pigcms_tag' => 'house_notice', 'tag_desc' => $order['bind_id'], 'url' => $order['url']);
                if(isset($order['pigcms_id'])){
                    $extra['pigcms_id']=$order['pigcms_id'];
                }
                if(isset($order['village_id'])){
                    $extra['village_id']=$order['village_id'];
                }
                $title = $order['title'];
                $msg = $order['msg'];
                $notification = $jpush->createBody(3, $title, $msg, $extra);
                $message = $jpush->createMsg($title, $msg, $extra);
                break;
            case 'visual':
                if ($order['voice_mp3']) {
                    $voice_mp3 = $order['voice_mp3'];
                } else {
                    $voice_mp3 = '';
                }
                $cloud_protocol = isset($order['cloud_protocol']) ? trim($order['cloud_protocol']) : 'hik_protocol';
                $voice_second = 6;
                $extra = array('pigcms_tag' => 'house_visual','cloud_protocol' => $cloud_protocol, 'voice_mp3' => $voice_mp3,'voice_second' => $voice_second, 'tag_desc' => $order['bind_id'], 'url' => $order['url'],'device_sn'=>$order['device_sn'],'periodNumber'=>$order['periodNumber'], 'buildingNumber'=>$order['buildingNumber'], 'unitNumber'=>$order['unitNumber'], 'floorNumber'=>$order['floorNumber'], 'roomNumber'=>$order['roomNumber'], 'unitType'=>$order['unitType'],'channelNo'=>$order['channelNo'],'device_id'=>$order['device_id'],'pigcms_id'=>$order['pigcms_id']);
                $title = $order['title'];
                $msg = $order['msg'];
                $notification = $jpush->createBody(3, $title, $msg, $extra,'doorbell.caf');
                $message = $jpush->createMsg($title, $msg, $extra);
				
				//发送排序超前
				$sendSort = 9998;
				$sendStatus = 1;
				
                break;
            case 'appoint':
                $extra = array('pigcms_tag' => 'appoint', 'tag_desc' => $order['real_orderid'], 'url' => C("config.site_url") . "/wap.php?c=My&a=appoint_order&order_id=" . $order['order_id']);
                $title = C('config.appoint_alias_name') . L_('订单状态变更提醒');
                $status = '';
                if ($order['paid'] == 2) {
                    $status = L_('已退款');
                } else if ($order['paid'] == 3) {
                    $status = L_('已取消');
                } else if ($order['paid'] == 1) {
                    if ($order['service_status'] == 1) {
                        $status = L_('已服务');
                    } else if ($order['service_status'] == 2) {
                        $status = L_('已评价');
                    } else {
                        $status = L_('已支付');
                    }
                }
                if ($status) {
                    $msg = C('config.appoint_alias_name') . L_('订单状态修改成【' . $status . '】，更多信息请查看详情！');
                    $notification = $jpush->createBody(3, $title, $msg, $extra);
                    $message = $jpush->createMsg($title, $msg, $extra);
                }
                break;
            case 'village_group':
                $status = [
                    0 => L_('未支付'),
                    1 => L_('已支付'),
                    2 => L_('已消费'),
                    3 => L_('部分消费'),
                    4 => L_('退款'),
                    5 => L_('已发货'),
                    6 => L_('团长收货 待自提'),
                    7 => L_('部分商品发货'),
                    8 => L_('部分商品收货，待自提'),
                    10 => L_('超时支付'),
                    11 => L_('已取消'),
                    12 => L_('已退款'),
                    13 => L_('已评价')
                ];
                if (isset($status[$order['status']])) {
                    $extra = array('pigcms_tag' => 'village_group', 'tag_desc' => $order['real_orderid'], 'url' => C("config.site_url") . "/wap.php?c=Village_group&a=orderDetail&order_id=" . $order['order_id']);
                    $title = L_('社区团购') . L_('订单状态变更提醒');
                    $msg = L_('社区团购') . L_('订单状态修改成【' . $status[$order['status']] . '】，更多信息请查看详情！');
                    $notification = $jpush->createBody(3, $title, $msg, $extra);
                    $message = $jpush->createMsg($title, $msg, $extra);
                }
                break;
            case 'gift':
                $status = [
                    0 => L_('未发货'),
                    1 => L_('已发货'),
                    2 => L_('已完成'),
                    3 => L_('待取货'),
                    5 => L_('已退款')
                ];
                if (isset($status[$order['status']])) {
                    $extra = array('pigcms_tag' => 'gift', 'tag_desc' => $order['order_id'], 'url' => C("config.site_url") . "/wap.php?c=My&a=gift_order&order_id=" . $order['order_id']);
                    $title = C('config.gift_alias_name') . L_('订单状态变更提醒');
                    $msg = C('config.gift_alias_name') . L_('订单状态修改成【' . $status[$order['status']] . '】，更多信息请查看详情！');
                    $notification = $jpush->createBody(3, $title, $msg, $extra);
                    $message = $jpush->createMsg($title, $msg, $extra);
                }
                break;
            case 'store':
                $status = '';
                if ($order['refund'] == 1) {
                    $status = L_('已退款');
                } else if ($order['refund'] == 2) {
                    $status = L_('退款失败');
                } else if ($order['paid'] > 0) {
                    $status = L_('已支付');
                }
                if ($status) {
                    $extra = array('pigcms_tag' => 'store', 'tag_desc' => $order['orderid'], 'url' => C("config.site_url") . "/wap.php?c=My&a=store_order_detail&order_id=" . $order['order_id']);
                    $title = C('config.cash_alias_name') . L_('订单状态变更提醒');
                    $msg = C('config.cash_alias_name') . L_('订单状态修改成【' . $status . '】，更多信息请查看详情！');
                    $notification = $jpush->createBody(3, $title, $msg, $extra);
                    $message = $jpush->createMsg($title, $msg, $extra);
                }
                break;
            case 'mall3':
                $status = [
                    '0' => '待支付',
                    '1' => '待支付',//尾款待支付
                    '10' => '待发货',
                    '11' => '备货中',
                    '12' => '已顺延',
                    '13' => '待成团',
                    '20' => '已发货',
                    '30' => '已收货',//用户端的已收货对应的平台 商家  店铺的已完成
                    '31' => '已自提',//用户端确认自提
                    '32' => '已核销',//店员确认核销
                    '33' => '骑手已送达',//骑手确认送达
                    '40' => '已完成',
                    '50' => '已取消',
                    '51' => '超时取消',
                    '52' => '用户取消',
                    '60' => '申请售后',
                    '70' => '已退款',
                ];
                if (isset($status[$order['status']])) {
                    $extra = array('pigcms_tag' => 'mall3', 'tag_desc' => $order['order_no'], 'url' => C("config.site_url") . D('House_village')->base_url . 'pages/shopmall_third/orderDetails?order_id=' . $order['order_id']);
                    $title = C('config.mall_alias_name_new') . L_('订单状态变更提醒');
                    $msg = C('config.mall_alias_name_new') . L_('订单状态修改成【' . $status[$order['status']] . '】，更多信息请查看详情！');
                    $notification = $jpush->createBody(3, $title, $msg, $extra);
                    $message = $jpush->createMsg($title, $msg, $extra);
                }
                break;
            case 'dining':
                $status = L_('订单生成');
                if ($order['status'] == 1) {
                    if (($order['book_price'] > 0 && !$order['is_book_pay']) || $order['status'] == 2) {
                        $status = L_('订金待支付');
                    } else {
                        $status = L_('未接单');
                    }
                } elseif ($order['status'] == 4) {
                    $status = L_('待落座');

                } elseif ($order['status'] >= 20 && $order['status'] < 40) {
                    $status = L_('就餐中');
                } elseif ($order['status'] >= 40 && $order['status'] < 60) {
                    $status = L_('已完成');
                } elseif ($order['status'] == 3) { //尾款待支付
                    $status = L_('待支付');
                } else {
                    $status = L_('已取消');
                }
                if ($status) {
                    $extra = array('pigcms_tag' => 'dining', 'tag_desc' => $order['real_orderid'], 'url' => C("config.site_url") . '/packapp/plat/pages/foodshop/order/orderDetail?order_id=' . $order['order_id']);
                    $title = C('config.meal_alias_name') . L_('订单状态变更提醒');
                    $msg = C('config.meal_alias_name') . L_('订单状态修改成【' . $status . '】，更多信息请查看详情！');
                    $notification = $jpush->createBody(3, $title, $msg, $extra);
                    $message = $jpush->createMsg($title, $msg, $extra);
                }
                break;
		}

		$columns = array();
		$columns['platform'] = $client == 1 ? array('ios') : array('android');
		$columns['audience'] = $audience;
		$columns['notification'] = $notification;
		$columns['message'] = $message;
		import('@.ORG.plan.plan_msg');
		$plan_msg = new plan_msg();
		$msg_content = [
			'type' => '4',
			'content' => [$columns], 
			'sort' => intval($sendSort),
			'status' => $sendStatus
		];
		$task_id = $plan_msg->addTask($msg_content);
		
		// 如果消息状态为1，代表重要，手动发送。
		if($sendStatus == 1){
			import('@.ORG.plan.plan_msg');
			$plan_msg_class = new plan_msg();
			$plan_msg_class->runTask($task_id);
		}
		return ;
	}
}