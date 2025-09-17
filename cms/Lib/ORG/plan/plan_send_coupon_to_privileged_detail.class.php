<?php
//派发优惠券
class plan_send_coupon_to_privileged_detail extends plan_base{
	public function runTask($param, $plan_time){
		if(empty($param['uid']) || empty(trim($param['coupon_ids'],','))) return true;

		//开始发券
		$model = D('System_coupon');
		$hadpull = M('System_coupon_hadpull');
		$coupon_list = explode(',', $param['coupon_ids']);
		foreach ($coupon_list as $item) {
			$coupon_info = $model->where(['coupon_id'=>$item])->find();
			$this->keepThread();
			if(intval($coupon_info['limit']) > 0){
				$coupon_info['limit'] = $coupon_info['limit'] > 10 ? 10 : $coupon_info['limit'];//最多一次干10张，不然用户输入过多导致程序错误
				for($i = 0 ; $i < intval($coupon_info['limit']) ; $i++){
					$insert = [
						'coupon_id' => $item,
						'uid' => $param['uid'],
						'num' => 1,
						'receive_time' => time(),
						'status' => 0,
					];
					$hadpull->data($insert)->add();	
				}
				$data = [];
				$data['uid'] = $param['uid'];
				$data['coupon_id'] = $item;
				$data['msg']  ='特权用户，领取成功';
				$data['add_time']  =time();
				M('System_coupon_send_history')->add($data);
			}

			
			
			
		}
		return true;
	}
}
?>