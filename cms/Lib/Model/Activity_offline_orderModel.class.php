<?php
class Activity_offline_orderModel extends Model{
    public function get_order($order_id){
        return $this->where(array('order_id'=>$order_id))->find();
    }
    public function get_pay_order($order_id){
        $now_order = $this->get_order($order_id);
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
    
    public function get_order_url($order_id,$is_mobile){
        $now_order = $this->get_order($order_id);
        return $this->config['site_url'].'/wap.php?c=ActivityOffline&a=indexDetail&activity_id='.$now_order['activity_id'];
    }

    public function after_pay($order_id,$plat_order_info){
        $now_order = $this->get_order($order_id);
        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
        //修改订单状态
        $this->where(array('order_id'=>$order_id))->setField('status',1);

        //修改活动付款状态
        M('Activity_offline')->where(array('activity_id'=>$now_order['activity_id']))->setField('pay_status',2);

        // 活动详情
        $activity = M('Activity_offline')->field(true)->where(array('activity_id'=>$now_order['activity_id']))->find();

        // 报名详情
        $join_info = M('Activity_offline_join')->field(true)->where(array('activity_id'=>$now_order['activity_id'],'uid'=>$now_order['uid']))->find();

        // 活动所有者
        $user_info = D('User')->get_user($now_order['uid']);

        if($join_info){
            //修改报名付款状态
            if ($now_order['is_final_pay']) {
                M('Activity_offline_join')->where(array('activity_id'=>$now_order['activity_id'],'uid'=>$now_order['uid']))->data(array('final_pay_status'=>2))->save();
                $first = L_('X1已支付尾款【X2】',array('X1'=>$user_info['nickname'],'X2'=>$activity['title']));
            }else{
                M('Activity_offline_join')->where(array('activity_id'=>$now_order['activity_id'],'uid'=>$now_order['uid']))->data(array('status'=>1))->save();
                $first = L_('X1已报名活动【X2】',array('X1'=>$user_info['nickname'],'X2'=>$activity['title']));
                
                // 报名人数加1
                M('Activity_offline')->where(array('activity_id'=>$now_order['activity_id']))->setInc('number',1);
            }
            
            //活动报名成功模板推送
            $owner_user_info = D('User')->get_user($activity['uid']);
            if ($owner_user_info['openid']) {//通知活动发起者
                $href = C('config.site_url') . '/wap.php?g=Wap&c=ActivityOffline&a=activityParticPation&activity_id=' . $now_order['activity_id'];
                $model->sendTempMsg('TM00017', array(
                    'href' => $href,
                    'wecha_id' => $owner_user_info['openid'],
                    'first' => $first,
                    'OrderSn' => $order_id,
                    'OrderStatus' => L_('报名成功'),
                    'remark' => date('Y-m-d H:i:s')
                ));
            }
        }

        if ($user_info['openid']) {
            $href = C('config.site_url') . '/wap.php?g=Wap&c=ActivityOffline&a=indexDetail&activity_id=' . $now_order['activity_id'];
            $model->sendTempMsg('TM00017', array(
                'href' => $href,
                'wecha_id' => $user_info['openid'],
                'first' => L_('活动【X1】已支付',$activity_info['title']),
                'OrderSn' => $activity_info['order_id'],
                'OrderStatus' => L_('支付成功'),
                'remark' => date('Y-m-d H:i:s')
            ));
        }
    }

    //退款
    public function refund($now_order,$activity){
        $activity_offline_join = D('Activity_offline_join');
        $plat_order = D('Plat_order');

        $order_id = $now_order['order_id'];
        $join_id = $now_order['join_id'];

        if($now_order['status'] ==3 ){
            return array('error'=>1,'msg'=>'该订单已经退款!');
        } elseif($now_order['status'] == 2 ){
            return array('error'=>1,'msg'=>'该订单未支付');
        }elseif($now_order['status'] == 0 ){
            return array('error'=>1,'msg'=>'该订单已失效');
        }

        // 订单状态更新
        if(!$this->where(array('order_id'=>$order_id))->data(array('status'=>3))->save()){
            return array('error'=>1,'msg'=>'修改订单状态失败');
        }

        // 报名状态更新
        $join_info = $activity_offline_join->where(array('join_id'=>$join_id))->find();
        if ($join_info['status'] != 4) {
            if(!$activity_offline_join->where(array('join_id'=>$join_id))->data(array('status'=>4))->save()){
                return array('error'=>1,'msg'=>'修改报名状态失败');
            }
        }

        // 退款
        $refund  = $plat_order->order_refund(array('business_type'=>'activity_offline','business_id'=>$now_order['order_id']));
        if(!$refund['error']){
            return array('error'=>1,'msg'=>$refund['msg']);
        }

        //活动退款模板推送
        $user_info = M('User')->field(true)->where(array('uid'=>$now_order['uid']))->find();
        if ($user_info['openid']) {
            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
            $href = C('config.site_url') . '/wap.php?g=Wap&c=ActivityOffline&a=activityOrder&join_id='.$join_id.'&activity_id=' . $activity['activity_id'];
            $model->sendTempMsg('TM00017', array(
                'href' => $href,
                'wecha_id' => $user_info['openid'],
                'first' => '活动【'.$activity['title'].'】已取消',
                'OrderSn' => $order_id,
                'OrderStatus' => '已退款',
                'remark' => date('Y-m-d H:i:s')
            ));
        }
        return array('error'=>0,'msg'=>'退款成功');
    }

    // 获取抽成
    public function take_percentage($activity_id,$order_list){
        if (!$activity_id||!$order_list) {
            return array('error'=>1,'msg'=>'参数传递有误');
        }

        if (C('config.activity_offline_get_join_percent')<=0 && C('config.activity_offline_get_finalpay_percent')<=0) {
           return array('error'=>0,'msg'=>'没有设置抽成比例');
        }

        $activity = M('Activity_offline')->where(array('activity_id'=>$activity_id))->find();
        // 报名费
        $join_momey = 0;
        // 尾款
        $final_momey = 0;
        foreach ($order_list as $key => $value) {
            if ( $value['is_final_pay']==0) {
               $join_momey += $value['money']; 
            }else{
                $final_momey += $value['money'];
            }   
        }

        if ( C('config.activity_offline_get_join_percent')>0&&$join_momey) {
            // 抽成
            $money = $join_momey * C('config.activity_offline_get_join_percent') * 0.01; 
            $res = D('User')->user_money($activity['uid'], $money, L_("活动【X1】报名费抽成，减少余额",array("X1" => $activity['title']))); 

            fdump($res,'htm',true);
        }

        if ( C('config.activity_offline_get_finalpay_percent')>0&&$final_momey) {
            // 抽成
            $money = $final_momey * C('config.activity_offline_get_finalpay_percent') * 0.01; 
            $res = D('User')->user_money($activity['uid'], $money, L_("活动【X1】尾款抽成，减少余额",array("X1" => $activity['title']))); 
            fdump($res,'htm',true);
        }
    }
}
?>