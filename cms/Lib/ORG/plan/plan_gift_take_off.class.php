<?php
/*积分商城礼品为平台优惠券时，优惠券到期则自动下架该礼品

 * */
class plan_gift_take_off extends plan_base{
	public function runTask(){
		$i = 0;
		$limit = 1000;
        $time = 86400;
        $list = D('Gift')->field('g.*')->join(' as g LEFT JOIN '. C('DB_PREFIX').'system_coupon c ON g.coupon_id = c.coupon_id')->where('g.coupon_id>0 AND g.status=1 AND ( c.status in(0,2,4) OR c.delete=1 OR c.end_time<='.time().')')->limit(0,$limit)->select();

		while( $list ){
			$this->keepThread();
			foreach ($list as $key => $value) {
				D('Gift')->where(array('gift_id' => $value['gift_id']))->save(array('last_time' => time(), 'status' => 0));
	                  
    			$i += $limit;
        		$list = D('Gift')->field('g.*')->join(' as g LEFT JOIN '. C('DB_PREFIX').'system_coupon c ON g.coupon_id = c.coupon_id')->where('g.coupon_id>0 AND g.status=1 AND ( c.status in(0,2,4) OR c.delete=1 OR c.end_time<='.time().')')->limit(0,$limit)->select();
		    }
        }
		return true;
	}
}
?>