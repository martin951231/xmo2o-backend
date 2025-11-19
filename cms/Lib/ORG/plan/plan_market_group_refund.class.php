<?php
/*批发市场拼团失败退还库存和账户余额* */
class plan_market_group_refund extends plan_base{
	public function runTask(){
		$list=D('Market_order')->get_fail_group_list();
		dump($list);
		while( $list ){
			$this->keepThread();
			foreach ($list as $key => $value) {
				D('Market_order')->market_order_refund($value);
				exit;
		    }
        }
		return true;
	}
}
?>