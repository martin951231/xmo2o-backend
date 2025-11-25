<?php
class Card_new_recharge_orderModel extends Model{
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
				'pay_system_balance' 	=> false,		  //平台余额
				'pay_system_coupon' 	=> false,		  //平台优惠券
				'pay_system_score' 		=> false,		  //平台积分抵现
				'order_info' 			=> $now_order,	  //平台积分抵现
		);
		$now_merchant = D('Merchant')->get_info($now_order['mer_id']);
		if(C('config.open_sub_mchid') && $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id']>0 ){
			$order_info['pay_merchant_ownpay'] = true;
		}
		return $order_info;
	}
	public function get_order_url($order_id,$is_mobile){
		$now_order = $this->get_order($order_id);

		return C('config.site_url') . '/wap.php?c=My_card&a=merchant_card&mer_id='.$now_order['mer_id'];
	}
	public function after_pay($order_id,$plat_order_info){
		fdump_sql(func_get_args(), 'cardNewRechargeOrderAfterPay');

		$now_order = $this->get_order($order_id);
		if (empty($now_order)) {
			return false;
		} else if ($now_order['receive_time'] > 0) {
			//已到账过，请勿重新到账
			return false;
		}

		$uniqueKey = md5($order_id . 'cardNewRechargeOrderAfterPay');
		$lockRs = setLock($uniqueKey, 10);
		if ($lockRs['error_no']) {
			return false;
		}

		if($plat_order_info['is_own']){
			$desc_name = L_('商家自有支付');
		}else{
			$desc_name = L_('子商家充值');
		}
		if(isset($plat_order_info['sub_mch_id']) && $plat_order_info['sub_mch_id'] >0 || $plat_order_info['is_own']>0){
			$ext = getFormatNumber($now_order['money']);
			if ($now_order['give_money'] > 0) {
				$ext .= "赠送" . getFormatNumber($now_order['give_money']);
			}
			$ext .= C('config.Currency_txt');
			D('Card_new')->add_user_money($now_order['mer_id'],$now_order['uid'],0,$now_order['give_money']+$now_order['money'],$now_order['give_score'],'','在线充值('.$desc_name.')'.$ext);
			if(C('config.user_own_pay_get_score')!=1){	
				if(C('config.open_score_get_percent')==1){
					$score_get = C('config.score_get_percent')/100;
				}else{
					$score_get =   C('config.user_score_get');
				}
				//D('User')->add_score($now_order['uid'], round(($now_order['money'] ) *$score_get), L_('会员卡在线充值获得').C('config.score_name'));
			}
		}else{
			D('Card_new')->add_user_money($now_order['mer_id'],$now_order['uid'],$now_order['money'],$now_order['give_money'],$now_order['give_score'],L_('在线充值').floatval($now_order['money']).C('config.Currency_txt'),L_('在线充值').floatval($now_order['money']).C('config.Currency_txt').L_('赠送'));
			if(C('config.open_score_get_percent')==1){
				$score_get = C('config.score_get_percent')/100;
			}else{
				$score_get =   C('config.user_score_get');
			}
			//D('User')->add_score($now_order['uid'], round(($now_order['money'] ) *$score_get), L_('会员卡在线充值获得').C('config.score_name'));
		}

		//TODO 充值会员卡发短信
		if (C('config.sms_group_success_order') == 2 || C('config.sms_group_success_order') == 3) {
			$merchant = D('Merchant')->where(array('mer_id' => $now_order['mer_id']))->find();
			$card_info = D('Card_new')->get_card_by_uid_and_mer_id($this->now_user['uid'], $this->mer_id);
			$sms_data['uid'] = 0;
			$sms_data['mobile'] = $merchant['phone'];
			$sms_data['sendto'] = 'merchant';
			$sms_data['content'] = L_('顾客在x1充值会员卡x2，卡号：x3',array('x1'=>date('Y-m-d H:i:s'),'x2'=>$now_order['money'].C('config.Currency_txt'),'x3'=>$card_info['card_id']));
			Sms::sendSms($sms_data);
		}

		$this->where(['order_id' => $now_order['order_id']])->save(['receive_time' => time()]);

	}
}
?>