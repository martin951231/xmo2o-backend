<?php
class Service_orderModel extends Model{

    public function get_order($order_id){
        return M('Service_user_publish')->where(array('publish_id'=>$order_id))->find();
    }
    
    public function get_pay_order($order_id){
        $now_order = $this->get_order($order_id);
        if(empty($now_order)){
            return array('error'=>1,'msg'=>L_('当前订单已失效或不存在！'));
        }
        if(($now_order['status'] == 2 && $now_order['is_voice'] == 0) || ($now_order['is_voice'] == 1 && in_array($now_order['voice_status'] , [10,20]))){
            return array('error'=>1,'msg'=>L_('您已经支付过此订单！'),'url'=>'/wap.php?c=Service&a=need_list');
        }

        if($now_order['catgory_type'] == 2){
            $now_order['order_name'] = L_('帮我买');
            $info = M('Service_user_publish_buy')->where(array('publish_id'=>$now_order['publish_id']))->find();
        }elseif ($now_order['catgory_type'] == 3) {
            $now_order['order_name'] = L_('帮我送');
            $info = M('Service_user_publish_give')->where(array('publish_id'=>$now_order['publish_id']))->find();
        }
        $now_order['order_num'] = 1;

        $order_info = array(
            'pay_offline' 			=> false,		  //线下支付
            'pay_merchant_balance' 	=> false,		  //商家余额
            'pay_merchant_coupon' 	=> false,		  //商家优惠券
            'pay_merchant_ownpay' 	=> false,		  //商家自有支付
            'pay_system_balance' 	=> true,		  //平台余额
            'pay_system_coupon' 	=> false,		  //平台优惠券
            'pay_system_score' 		=> false,		  //平台积分抵现
            'order_info' 			=> $now_order,	  //平台积分抵现
        );
        return $order_info;
    }

    public function after_pay($order_id,$plat_order_info){
        $where['publish_id'] = $order_id;

        // 订单详情
        $now_order = M('Service_user_publish')->where($where)->find();

        // 修改订单状态
        $data_order['status'] = 2;
        if($now_order['is_voice']){// 语音发单订单
            if($plat_order_info['is_final_pay']==1){// 支付尾款
                $data_order['status'] = 8;
                $data_order['voice_status'] = 20;
            }else{// 支付基础费用
                $data_order['voice_status'] = 10;
            }
        }

        M('Service_user_publish')->where($where)->data($data_order)->save();
        $this->template_message($order_id);

    }
    
    public function get_order_url($order_id){
        $now_order = $this->get_order($order_id);
        return C('config.site_url').'/wap.php?c=Service&a=price_list&publish_id='.$order_id;
    }

    public function template_message($publish_id){
        $info = $this->get_order($publish_id);
        if($info['deliver_type'] == 1 && ($info['is_voice'] == 0 || ($info['is_voice'] == 1 && $info['voice_status'] == 10))){
            //推送配送订单，
            if($info['catgory_type']==3&&$info['is_third_deliver']){
                $service_give = D('Service_user_publish_give')->where(['publish_id'=>$publish_id])->find();
                if($service_give){
                    $order = [
                        'business_type' => 'service',
                        'address_lat' => $service_give['end_adress_lat'],
                        'address_lng' => $service_give['end_adress_lng'],
                        'order_no' => $info['order_sn'],
                        'username' => $service_give['end_adress_nickname'],
                        'phone' => $service_give['end_adress_phone'],
                        'address' => $service_give['end_adress_name'],
                        'create_time' => $service_give['addtime'],
                        'money_freight' => $service_give['basic_distance_price'],
                        'money_total' => $service_give['total_price'],
                        'express_send_time' => strtotime($service_give['arrival_time_info'].':00'),
                        'remark' => $service_give['remarks'],
                        'from_lat' => $service_give['start_adress_lng'],
                        'from_lng' => $service_give['start_adress_lat'],
                        'from_adress' => $service_give['start_adress_name'],
                        'from_name' => $service_give['start_adress_nickname'],
                        'from_phone' => $service_give['start_adress_phone'],
                    ];
                    $detail = [
                        [
                            'name' => $service_give['goods_catgory'], //商品名称
                            'num' => 1, //数量
                            'price' => $service_give['basic_distance_price'], //单价价格
                        ]
                    ];
                    $res = invoke_v20_service('\app\mall\model\service\third_order_deliver\DeliverApiService/assigned',['order'=>$order,'detail'=>$detail]);
                    fdump_api([$order,$detail,$res],'mall_third_deliver/assigned',1);
                }
            }else {
                D('Deliver_supply')->saveOrder($publish_id, null, 3, 'service_user_publish');
            }
        }else{
            //模板消息
            $where = "`cid`={$info['cid']} AND `area_id`={$info['address_area_id']} AND (ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$info['address_lat']}*PI()/180-`lat`*PI()/180)/2),2)+COS({$info['address_lat']}*PI()/180)*COS(`lat`*PI()/180)*POW(SIN(({$info['address_lng']}*PI()/180-`lng`*PI()/180)/2),2)))*1000) <= `radius`*1000 OR `radius` = 0) AND `uid` != {$this->user_session['uid']} ";
            $plist = D("Service_provider_category")->where($where)->field('uid,cat_name')->select();
            
            // 多维数组去重复
            foreach ($plist as $v){
                $temp[] = serialize($v);
            }
            $temp=array_unique($temp); //去掉重复的字符串,也就是重复的数组
            foreach ($temp as $k => $v){
                $temp[$k]=unserialize($v); //再将拆开的数组重新组装
            }
            $plist = $temp;
        
            if($plist){
                foreach ($plist as $key => $value) {
                    $user_info = D("User")->where(array('uid'=>$value['uid']))->field('uid,openid,nickname')->find();
                    if ($user_info['openid']) {
                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                        $href = C('config.site_url') . '/wap.php?g=Wap&c=Service&a=detail_special&publish_id='.$publish_id;
                        $model->sendTempMsg('TM00017',
                            array('href' => $href,
                                'wecha_id' => $user_info['openid'],
                                'first' => L_("X1用户发布了一个符合您的“X2”需求，请及时接单！",array("X1" => $user_info['nickname'],"X2" => $value['cat_name'])), 
                                'OrderSn' => $info['order_sn'],
                                'OrderStatus' => L_('待处理'),
                                'remark' => L_('请您及时处理！')
							)
						);
                    }
                }
            }
        }

        // D('User')->where(array('uid'=>$this->user_session['uid']))->setDec('now_money',$_POST['total_price']);
        // D('User_money_list')->add_row($this->user_session['uid'],2,$_POST['total_price'],"支付帮我买服务费 ".$_POST['total_price'].C('config.Currency_txt')."",true,4,$res);

        D('Service_offer_record')->data(array('offer_id'=>0,'publish_id'=>$publish_id,'add_time'=>time(),'remarks'=>L_('订单支付成功')))->add();

        $now_user = D('User')->get_user($info['uid']);
        if ($now_user['openid']) {
            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
            $href = C('config.site_url') . '/wap.php?g=Wap&c=Service&a=price_list&publish_id=' . $publish_id;
            // 帮买帮送修改  待办事项通知 模板 为 订单状态更新 模板
            $model->sendTempMsg('TM00017', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' => $now_user['nickname'] . L_('您好！'), 'OrderSn' => $info['order_sn'], 'OrderStatus' =>L_('您发布的需求已经完成支付，请查看。'), 'remark' => date('Y.m.d H:i:s')));
        }
        // 进行极光App推送
        if ($now_user) {
            if (!$info['order_sn']) $info['order_sn'] = $publish_id;
            import('@.ORG.Apppush');
            $order['status'] = 2;
            $order['order_id'] = $publish_id;
            $order['order_sn'] = $info['order_sn'];
            $order['uid'] = $info['uid'];
            $order['catgory_type'] = $info['catgory_type'];
            $apppush = new Apppush();
            $apppush->send($order, 'publish');
        }

    }
}

?>