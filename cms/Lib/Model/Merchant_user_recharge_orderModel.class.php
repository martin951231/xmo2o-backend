<?php
class Merchant_user_recharge_orderModel extends Model
{
	public function get_order_by_id($uid, $order_id, $orderid = 0)
	{
		$where = array();
		$order_id && $where['order_id'] = $order_id;
		$orderid && $where['orderid'] = $orderid;
		$where['uid'] = $uid;
		return $this->field(true)->where($where)->find();
	}

	public function get_order_by_orderid($order_id)
	{
		$where = array();


		$order_id && $where['order_id'] = $order_id;
		if (strlen($order_id) > 10){
			$where['orderid'] = $order_id;
			unset($where['order_id']);
		}
		return $this->field(true)->where($where)->find();
	}

	public function get_order($order_id){
		return $this->where(array('order_id'=>$order_id))->find();
	}

    public function get_pay_order($order_id)
    {
        $now_order = $this->get_order($order_id);
		$now_order['order_num'] = 1; // 已点菜品总数
        return array(
            'pay_offline' => false,//D('Percent_rate')->pay_offline($now_order['mer_id'],'meal'), // 线下支付 D('Percent_rate')->pay_offline($now_order['mer_id'],'meal')
            'pay_merchant_balance' => false, // 商家余额
            'pay_merchant_coupon' => false, // 商家优惠券
            'pay_merchant_ownpay' => false, // 商家自有支付
            'pay_system_balance' => true, // 平台余额
            'pay_system_coupon' => false, // 平台优惠券
            'pay_system_score' => false, // 平台积分抵现
            'discount_status' => false, // 平台积分抵现
            'order_info' => $now_order,
            'status' => $now_order['status']
        );
    }
	public function get_order_url($order_id, $is_mobile)
	{
		$now_order = $this->get_order($order_id);
		if ($now_order) {
			return C('config.site_url').'/wap.php?c=Company_campus&a=rechargeRecord';
		}
		
	}

	public function after_pay($order_id, $plat_order_info = array(), $pay_type = 0)
	{
		$now_order = $this->get_order($order_id);
		
		$now_user = D('User')->get_user($now_order['uid']);
		$save_data = '';

		$payMoney = 0 ;
		$plat_order = M('Plat_order')->where(array('business_id' => $order_id, 'business_type' => 'merchant_user_recharge', 'paid' => 1))->find();
		
		$save_data = array('payment_money' => $plat_order['total_money'], 'paid' => 1);
		$save_data['pay_time'] = $_SERVER['REQUEST_TIME'];
		$save_data['pay_type'] = $plat_order_info['pay_type'] ? $plat_order_info['pay_type'] :'';
		$order_info = $plat_order;;

		$order_info['balance_pay'] = $plat_order['system_balance'];
		$order_info['payment_money'] =  $plat_order['pay_money'];
		$order_info['merchant_balance'] =  $plat_order['merchant_balance'];
		$order_info['order_id'] = $now_order['order_id'];
		$order_info['order_type'] = 'merchant_user_recharge';
		$order_info['total_price'] = $now_order['money'];//$save_data['total_price'];
		$order_info['is_own'] = $plat_order_info['is_own'];
		$order_info['mer_id'] = $now_order['mer_id'];
		$order_info['card_id'] = $now_order['card_id'];
		$order_info['uid'] = $now_order['uid'];
		$order_info['desc']=L_('商家会员卡充值订单');
        if ($this->where(array('order_id' => $order_id))->save($save_data)) {
            /* 增加系统订单记录 2019/01/29 */
			$this->add_system_order($order_id,2);

			// 对账
			$res = D('SystemBill')->bill_method($order_info['is_own'],$order_info);

        }
	} 

	// 写入平台总订单
    public function add_system_order($order_id = 0,$step = 1){
    	if(!$order_id) return false;

		$now_order = $this->get_order($order_id);
		
		$now_order['pay_money'] = $now_order['payment_money'];
		$now_order['total_price'] = $now_order['money'];
		$now_order['price'] = $now_order['price'];
	    $res = D('System_order')->add_order('merchant_user_recharge',$now_order,$order_id,$step);
	    return $res;
    }


	
}
?>
