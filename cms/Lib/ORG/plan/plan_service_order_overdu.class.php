<?php

class plan_service_order_overdu extends plan_base{
	public function runTask(){
		$list = D('Service_user_publish')->where([
			'status'=>2,
			'catgory_type'=>['neq',1],
			'third_type' => ['neq', 'maiyatian']
		])->limit(5)->select();
        foreach ($list as $key => $value) {
			$this->keepThread();
            $catInfo = D('Service_category')->where(array('cid'=>$value['cid']))->find();
            $time = $value['add_time']+(intval($catInfo['accept_time'])*60*60);
            if(time() > $time){
                $plat_info = D('Plat_order')->where(array('business_type'=>'service','business_id'=>$value['publish_id']))->find();
                $refund = array();
//                $payment_money_order = M('Payment_money_order')->where(array('order_id'=>$plat_info['order_id'],'order_type'=>'plat','pay_type'=>$plat_info['pay_type'],'paid_money'=>array('gt',0)))->find();
                $payment_money_order = M('Payment_money_order')->order('id DESC')->where(array('order_id'=>$plat_info['order_id'],'orderid'=> $plat_info['orderid'],'order_type'=>'plat','pay_type'=>$plat_info['pay_type'],'paid_money'=>array('gt',0)))->find();

                if($plat_info['pay_money'] > 0 && ($plat_info['is_refund'] == 0 || $plat_info['is_refund'] == 4) && $payment_money_order && ($payment_money_order['pay_method'] == 'wft' || $payment_money_order['pay_method'] == 'paylinx' || $payment_money_order['pay_method'] == 'wepayez' || $payment_money_order['pay_method'] == 'weikebaba')){

                    if($plat_info['pay_money'] == $payment_money_order['payment_money']){
                        //如果是全退，那就把用户实际支付的钱一起退了吧。包括支付手续费
                        $plat_info['pay_money'] = $payment_money_order['paid_money'];
                    }
                    $plat_info['payment_money'] = $plat_info['pay_money'];
                    if($payment_money_order['pay_method'] == 'wft'){
                        $pay_method = 'wftpay';
                    }
                    if($payment_money_order['pay_method'] == 'paylinx'){
                        $pay_method = 'paylinx';
                    }
                    if($payment_money_order['pay_method'] == 'wepayez'){
                        $pay_method = 'wepayez';
                    }
                    if($payment_money_order['pay_method'] == 'weikebaba'){
                        $pay_method = 'weikebaba';
                    }
                    import('@.ORG.LowFeePay');
                    $lowfeepay = new LowFeePay($pay_method);
                    $plat_info['order_type'] = 'service';
                    $plat_info['orderNo'] = $payment_money_order['orderNo'];
                    if($plat_info['mer_id'] && $payment_money_order['pay_method'] == 'wft'){
                        $mer_juhe = M('Merchant_juhe_config')->where(array('mer_id'=>$plat_info['mer_id']))->find();
                        if(!empty($mer_juhe)){
                            $lowfeepay->userId =$mer_juhe['userid'];
                            $plat_info['is_own'] =1 ;
                            $plat_info['orderNo'] =  $plat_info['orderNo'].'_1';
                        }

                    }elseif($plat_info['mer_id'] && $payment_money_order['pay_method'] == 'weikebaba'){
                        $mer_juhe = M('Weikebaba')->where(array('mer_id'=>$plat_info['mer_id']))->find();
                        if(!empty($mer_juhe)){
                            $lowfeepay->user_id = $mer_juhe['user_id'];
                            $lowfeepay->key = $mer_juhe['key'];
                            $plat_info['is_own'] =1 ;
                        }
                    }
                    $go_refund_param= $lowfeepay->refund($plat_info);
                    $data_plat_order['order_id'] = $plat_info['order_id'];
                    if(!$go_refund_param['error'] && $go_refund_param['type'] == 'ok'){
                        $data_plat_order['is_refund'] = 1;
                        $data_plat_order['refund_detail'] = serialize($go_refund_param);
                        $refund['error'] = 0;
                    }else{
                        $data_plat_order['refund_detail'] = serialize($go_refund_param);
                        $data_plat_order['is_refund'] = 4;
                        $refund['error'] = 1;
                    }
                    if ($plat_info['is_refund']==1) {
                        // 如果是推过款的重复性退款 只记录退款失败原因，依旧保持退款成功状态
                        $data_plat_order['is_refund'] = 1;
                        $refund['error'] = 0;
                    }
                    $data_plat_order['last_time'] = time();
                    D('Plat_order')->data($data_plat_order)->save();
                } else {
                    $refund = D('Plat_order')->order_refund(array('business_type' => 'service', 'business_id' => $value['publish_id']));
                }
                if(!$refund['error']) {
//                    if ($value['catgory_type'] == 2) {
//                        $cat_field_info = D('Service_user_publish_buy')->where(array('publish_id' => $value['publish_id']))->find();
//                    } else if ($value['catgory_type'] == 3) {
//                        $cat_field_info = D('Service_user_publish_give')->where(array('publish_id' => $value['publish_id']))->find();
//                    }
                    D('Service_user_publish')->where(array('publish_id' => $value['publish_id']))->save(array('status' => 11));
                    D('Deliver_supply')->updateStatusToZero($value['publish_id']);
//                    if ($res) {
//                        D('User')->where(array('uid' => $value['uid']))->setInc('now_money', $cat_field_info['total_price']);
//                        D('User_money_list')->add_row($value['uid'], 1, $cat_field_info['total_price'], $catInfo['cat_name'] . "超时退款 " . $cat_field_info['total_price'] . C('config.Currency_txt')."");
//                    }
                    D("System_order")->save_order_status('service',intval($plat_info['order_id']),5);


                    // 处理返回信息

                    // 添加记录
                    $log = array(
                        'publish_id' => $value['publish_id'],
                        'status' => 11,
                        'remark' => '后台修改，订单完成',
                        'type' => 2,
                        'admin_id' => $this->system_session['id']
                    );
                    D('Service_user_publish_log')->add_log($log);
                    if ($value['is_third']>0 && $value['notifyurl']) {
                        // 回调信息
                        $notifyurl = $value['notifyurl'];
                        $notify_data = array(
                            'third_id' => $value['third_id'],
                            'publish_id' => $value['publish_id'],
                            'status' => 11,
                            'msg' => '后台修改，订单完成',
                            'time' => time(),
                        );
                        D('Service_user_publish_log')->notify_url($notify_data, $notifyurl);
                    }
                } else {
                    fdump('跑腿用户退款失败>>>'.__LINE__,'api/log/'.date('Ymd').'/paotui/refund_err_log',true);
                    fdump($_POST,'api/log/'.date('Ymd').'/paotui/refund_err_log',true);
                    fdump($plat_info,'api/log/'.date('Ymd').'/paotui/refund_err_log',true);
                    fdump($payment_money_order,'api/log/'.date('Ymd').'/paotui/refund_err_log',true);
                    fdump($refund,'api/log/'.date('Ymd').'/paotui/refund_err_log',true);
                    M('Service_user_publish')->where(array('publish_id' => $value['publish_id']))->save(array('status' => 13));
                    M('Deliver_supply')->where(array('item' => 3, 'order_id' => $value['publish_id']))->save(array('status' => 7));
                    // 处理返回信息

                    $log = array(
                        'publish_id' => $value['publish_id'],
                        'status' => 12,
                        'remark' => '超时系统自动退款失败',
                        'type' => 2
                    );
                    D('Service_user_publish_log')->add_log($log);
                    if ($value['is_third']>0 && $value['notifyurl']) {
                        // 回调信息
                        $notifyurl = $value['notifyurl'];
                        $notify_data = array(
                            'third_id' => $value['third_id'],
                            'publish_id' => $value['publish_id'],
                            'status' => 12,
                            'msg' => '超时系统自动退款失败',
                            'time' => time(),
                        );
                        D('Service_user_publish_log')->notify_url($notify_data, $notifyurl);
                    }
                }
            }
        }
        return true;
    }
}
?>