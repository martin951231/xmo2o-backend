<?php
/*餐饮预订订单未支付定金超过创建时间15分钟自动取消订单 

 * */
class plan_foodshop_order_cancel extends plan_base{
	public function runTask(){
		$i = 0;
		$limit = 1000;
        $time = 900;
        $list = D('Foodshop_order')->where(array('is_foodshop_new'=>array('neq',1),'is_book_pay'=>array('neq',1),'book_pay_time'=>0,'status'=>0,'order_from'=>0,'create_time'=>array('lt',time()-$time)))->limit(0,$limit)->select();

		while( $list ){
			$this->keepThread();
			
			foreach ($list as $key => $value) {
				D('Foodshop_order')->where(array('order_id' => $value['order_id']))->save(array('cancel_time' => time(), 'status' => 5,'operate_status' => 3, 'cancel_reason' => '过期自动取消！'));
				
	            /* 增加系统订单记录 2019/01/29 */
                D('Foodshop_order')->add_system_order($value['order_id'],2);
	                  
    			$i += $limit;
                $list = D('Foodshop_order')->where(array('is_foodshop_new'=>array('neq',1),'is_book_pay'=>array('neq',1),'book_pay_time'=>0,'status'=>0,'order_from'=>0,'create_time'=>array('lt',time()-$time)))->limit($i,$limit)->select();
		    }
        }
		return true;
	}
}
?>