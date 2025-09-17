<?php
class plan_privileged_user extends plan_base{
	public function runTask(){
		$db = M("User_privileged_rule");
		$data = $db->where(['timeint' => ['gt',-1],'coupon_ids' => ['neq', ''], 'last_date' => ['neq',date('Y-m-d')]])->select();
		if(empty($data)){
			return true;
		}
		
		fdump($data, 'User_privileged_rule', true);
		
		$subplan = M("Process_sub_plan");
		foreach ($data as $key => $value) {
			$this->keepThread();
			$plan_time = strtotime(date('Y-m-d 00:00:00'))+$value['timeint']*60;
			if($plan_time <= time()){
				$insert = [
					'param' => serialize(['city_id'=>$value['city_id'],'coupon_ids'=>$value['coupon_ids']]),
					'plan_time' => 0,
					'space_time' => 0,
					'add_time' => time(),
					'error_count' => 0,
					'url' => '',
					'file' => 'send_coupon_to_privileged',//发券给特权用户
					'time_type' => 1,
					'unique_id' => 'privileged_user_'.date('Y-m-d').$value['city_id'],
					'rand_number' => mt_rand(1, max(C('config.sub_process_num'), 3)),
				];
				$subplan->data($insert)->add();
				$db->data(['last_date'=>date('Y-m-d')])->where(['city_id'=>$value['city_id']])->save();
			}
		}

		return true;
	}
}
?>