<?php
class Activity_orderModel extends Model{
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
        return $this->config['site_url'].'/wap.php?c=Platform&a=indexDetail&activity_id='.$now_order['activity_id'];
    }
    public function after_pay($order_id,$plat_order_info){
        $now_order = $this->get_order($order_id);
        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
        $this->where(array('order_id'=>$order_id))->setField('status',1);//修改订单状态
        M('Activity')->where(array('activity_id'=>$now_order['activity_id']))->setField('pay_status',2);//修改活动付款状态
        $activity_info = M('Activity_order')->alias('a')->field('a.*,b.*')->where(array('a.activity_id'=>$now_order['activity_id']))->join('join pigcms_activity as b on a.activity_id=b.activity_id')->find();
        $join_info = M('Activity_join')->field(true)->where(array('activity_id'=>$now_order['activity_id'],'uid'=>$now_order['uid']))->find();
        $user_info = M('User')->field(true)->where(array('uid'=>$now_order['uid']))->find();
        if($join_info){//修改报名付款状态
            M('Activity_join')->where(array('activity_id'=>$now_order['activity_id'],'uid'=>$now_order['uid']))->data(array('status'=>1))->save();
            $activity = M('Activity')->field(true)->where(array('activity_id'=>$now_order['activity_id']))->find();
            $owner_user_info = M('User')->field(true)->where(array('uid'=>$activity['uid']))->find();
            if ($owner_user_info['openid']) {//通知活动发起者
                $href = C('config.site_url') . '/wap.php?g=Wap&c=Platform&a=activityParticPation&activity_id=' . $now_order['activity_id'];
                $model->sendTempMsg('TM00017', array(
                    'href' => $href,
                    'wecha_id' => $owner_user_info['openid'],
                    'first' => L_('X1已报名活动【X2】',array('X1'=>$user_info['nickname'],'X2'=>$activity['title'])),
                    'OrderSn' => $activity_info['order_id'],
                    'OrderStatus' => L_('报名成功'),
                    'remark' => date('Y-m-d H:i:s')
                ));//活动报名成功模板推送
            }
        }


        if ($user_info['openid']) {
            $href = C('config.site_url') . '/wap.php?g=Wap&c=Platform&a=indexDetail&activity_id=' . $now_order['activity_id'];
            $model->sendTempMsg('TM00017', array(
                'href' => $href,
                'wecha_id' => $user_info['openid'],
                'first' => L_('活动【X1】已支付',$activity_info['title']),
                'OrderSn' => $activity_info['order_id'],
                'OrderStatus' => L_('支付成功'),
                'remark' => date('Y-m-d H:i:s')
            ));//活动创建成功模板推送
        }

        //给分类管理员进行推送通知
        if(!$join_info){
            $manager_area = M('Manager_area')
                ->alias('a')
                ->field('b.openid')
                ->distinct(true)
                ->where(array('province_id'=>$activity_info['province_id'],'city_id'=>$activity_info['city_id'],'cate_id'=>$activity_info['cate_id']))
                ->join('LEFT JOIN pigcms_user as b on a.uid=b.uid')
                ->select();

            if($manager_area){
                foreach ($manager_area as $key => $value) {
                    if($value['openid']){
                        $check_href = C('config.site_url') . '/wap.php?g=Wap&c=Platform&a=indexDetail&examine_status=1&activity_id=' . $now_order['activity_id'];
                        $model->sendTempMsg('TM00017', array(
                            'href' => $check_href,
                            'wecha_id' => $value['openid'],
                            'first' => L_('用户X1发布活动【X2】',array('X1'=>$user_info['nickname'],'X2'=>$activity_info['title'])),
                            'OrderSn' => '',
                            'OrderStatus' => L_('活动创建成功,请及时审核'),
                            'remark' => date('Y-m-d H:i:s')
                        ));//活动创建成功模板推送给分类管理员
                    }
                }
            }
        }
    }
}
?>