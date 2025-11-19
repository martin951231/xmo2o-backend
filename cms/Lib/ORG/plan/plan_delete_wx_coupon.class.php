<?php
	/*
	 * 派发优惠券，平台和商家
	 * */
class plan_delete_wx_coupon extends plan_base{
	public function runTask($param){
		//删除计划任务
		if($param['plan_task_id']){
			$this->dropPlan($param['plan_task_id']);
		}
		
		$where['coupon_id']= $param['id'];
		if($param['type']==1){
			$wx_coupon_list = M('Card_coupon_hadpull')->where($where)->select();
		}else{
			$wx_coupon_list = M('System_coupon_hadpull')->where($where)->select();
		}

		import('@.ORG.weixincard');
		$card = new Create_card();

		foreach ($wx_coupon_list as $value) {
			$param['wx_card_code'] = $value['wx_card_code'];
			//$param['des'] = '此券已被商家删除';
			$card->unavailable($param);
		}

		return true;
	}



}
?>