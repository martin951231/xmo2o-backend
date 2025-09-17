<?php
//派发优惠券
class plan_send_coupon_to_privileged extends plan_base{
	public function runTask($param, $plan_time){
		if(empty($param['city_id']) || empty(trim($param['coupon_ids'],','))) return true;
		$db = M("User_privilege");
		$where = [
			'city_id' => $param['city_id'],
			'status' => 1,
			'promotion_from' => ['elt', date('Y-m-d H:i:s')],
			'promotion_to' => ['gt', date('Y-m-d H:i:s')]
		];
		$users = array_unique(array_column($db->where($where)->field("uid")->select(), 'uid'));
		
		fdump($db->getLastSql(), 'User_privilege', true);
		fdump($users, 'User_privilege', true);

		$subplan = M("Process_sub_plan");
		foreach($users as $user){
			$insert = [
				'param' => serialize(['uid'=>$user,'coupon_ids'=>$param['coupon_ids']]),
				'plan_time' => 0,
				'space_time' => 0,
				'add_time' => time(),
				'error_count' => 0,
				'url' => '',
				'file' => 'send_coupon_to_privileged_detail',//发券给特权用户
				'time_type' => 1,
				'unique_id' => 'sendcouponto'.date('Ymd') . $param['city_id'] . '_'.$user,
				'rand_number' => mt_rand(1, max(C('config.sub_process_num'), 3)),
			];
			$a = $subplan->data($insert)->add();
		}
		return true;
	}
}
?>