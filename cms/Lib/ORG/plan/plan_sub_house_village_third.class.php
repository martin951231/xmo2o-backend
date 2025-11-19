<?php
	/*
	 *社区人脸门禁计划任务
	 * */
class plan_sub_house_village_third extends plan_base{
	public function runTask($param){
        fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/plan_sub_house_village_third_log',true);
		if($param['type']=='village'){
		    $data=[];
		    $data['village_id']=$param['village_id'];
            invoke_v20_service('\app\community\model\service\HouseVillageThirdService/insertOrUpdateCommunity', $data);
            return true;
		}elseif ($param['type']=='house'){
            $data=[];
            $data['vacancy_id']=$param['vacancy_id'];
            invoke_v20_service('\app\community\model\service\HouseVillageThirdService/addOrUpdateBuilding', $data);
            return true;
        }else{
            $data=[];
            $data['bind_id']=$param['bind_id'];
            invoke_v20_service('\app\community\model\service\HouseVillageThirdService/addPersonInfo', $data);
            return true;
        }
	}
}
?>