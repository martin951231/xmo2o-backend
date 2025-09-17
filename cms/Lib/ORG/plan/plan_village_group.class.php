<?php
	/*
	 * 社区拼团计划任务
	 * */
class plan_village_group extends plan_base{
	public function runTask($param){
		//删除计划任务
		if($param['plan_task_id']){
			$this->dropPlan($param['plan_task_id']);
		}
		
		$res = D('Village_group_order')->village_group_runtask($param);

		return true;
	}
}
?>