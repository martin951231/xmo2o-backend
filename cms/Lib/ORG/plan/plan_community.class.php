<?php
	/*
	 * 社群，群頭像生成
	 * */
class plan_community extends plan_base{
	public function runTask($param){
		//删除计划任务
		if($param['plan_task_id']){
			$this->dropPlan($param['plan_task_id']);
		}
		
		$res = D('Community_info')->comm_runtask($param);

		return true;
	}
}
?>