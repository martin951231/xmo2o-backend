<?php
	/*
	 *社区人脸门禁开门记录子计划任务
	 * */
class plan_sub_house_face_open_list extends plan_base{
	public function runTask($param){
		//删除计划任务
		if($param['plan_task_id']){
			$this->dropPlan($param['plan_task_id']);
		}

		$res = D('Face_door_service')->face_runtask($param);
		return true;
	}
}
?>