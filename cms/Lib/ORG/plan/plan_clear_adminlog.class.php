<?php
class plan_clear_adminlog extends plan_base{
	public function runTask(){
		$clear_time = time() - 86400*30*3;
		M('Admin_log')->where(array('add_time'=>array('lt',$clear_time)))->delete();
		M('Merchant_log')->where(array('add_time'=>array('lt',$clear_time)))->delete();
		
		$this->keepThread();
		
		M('House_log')->where(array('add_time'=>array('lt',$clear_time)))->delete();
		M('Fdump_sql')->where(array('time'=>array('lt',$clear_time)))->delete();
		
		$this->keepThread();
		
		M('Face_a7_request_record')->where(array('add_time'=>array('lt', time() - 86400*3)))->delete();
		
		$this->keepThread();
		
		D('Cache_sql')->clearCache();

		$this->keepThread();

		M('App_push_msg')->where(array('add_time' => array('lt', time() - 86400 * 3)))->delete();

		$this->keepThread();

		M('Deliver_user_location_log')->where(array('create_time' => array('lt', time() - 86400 * 7)))->delete();
		
		return true;
	}
}
?>