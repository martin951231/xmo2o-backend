<?php
	/*
	 *社区人脸门禁计划任务
	 * */
class plan_sub_house_village extends plan_base{
	public function runTask($param){
        fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/plan_sub_house_village_log',true);
		//删除计划任务
		if($param['plan_task_id']){
			$this->dropPlan($param['plan_task_id']);
		}

        fdump($param,'api/log/'.date('Ymd').'/plan_sub_house_village_log',true);
		if($param['type'] == 'repair_appoint_work'){
            D('House_worker')->appoint_work($param);
        }elseif ($param['type'] == 'grab_order_notice'){
            invoke_v20_service('\app\community\model\service\HouseNewRepairService/sendGrabOrderNoticeWork',['village_id'=>$param['village_id'],'cate_id'=>$param['cate_id']]);
        }else{
            $res = D('House_village_face_door')->village_runtask($param);
        }

		return true;
	}
}
?>