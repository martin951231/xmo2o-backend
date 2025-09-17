<?php
/*
  * 客户定制每日释放商家积分 子计划任务
  * @date 2021-09-09
 */
class plan_add_merchant_score_save extends plan_base{
	public function runTask($param){
		if(!$param['mer_id']){
			return true;
		}

		$res = D('Merchant_get_score')->addMerchantScore($param['mer_id']);
		return true;
	}
}
?>