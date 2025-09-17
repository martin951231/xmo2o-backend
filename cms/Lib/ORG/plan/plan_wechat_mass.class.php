<?php
class plan_wechat_mass extends plan_base{
	public function runTask($param){
		//删除计划任务
		if($param['plan_task_id']){
			$this->dropPlan($param['plan_task_id']);
		}
		$send_id = $param['id'];
		$log = D('Send_log')->where(array('pigcms_id' => $send_id))->find();
		if(empty($log)) return true;
		$where = array('mer_id' => $log['mer_id']);
		$log['type']>0 && $where['from_merchant']  = $log['type'];
		$mer_use_obj = D('Merchant_user_relation');
		$openids = $mer_use_obj->field('openid')->where($where)->order('dateline DESC')->select();
		
		$subplan = M("Process_sub_plan");
		foreach ($openids as $user) {
			$this->keepThread();
			$insert = [
				'param' => serialize(['openid'=>$user['openid'], 'send_id'=>$send_id]),
				'plan_time' => 0,
				'space_time' => 0,
				'add_time' => time(),
				'error_count' => 0,
				'url' => '',
				'file' => 'wechat_mass_send',
				'time_type' => 1,
				'unique_id' => 'wms'.date('Ymd').substr($user['openid'], 0,20),
				'rand_number' => mt_rand(1, max(C('config.sub_process_num'), 3)),
			];
			$subplan->data($insert)->add();
		}
		D('Send_log')->where(array('pigcms_id' => $send_id))->save(array('status' => 1));
		return true;
	}
	
	/*
	 *  添加任务
	 *
	 *  param array 消息参数
	 *
	 *  param.type   1为短信通知，2为模板消息，3为图文消息，4为APP通知
	 *
	 *
	 *
	 *  param.content 不同的业务，不同的值，传数组
	 *
	 *  param.send_time  发送时间
	 *
	 *
	 *
	 *
	 */
	public function addTask($param){
		$data['type'] = $param['type'];
		$data['label'] = $param['label'] ? (is_array($param['label']) ? implode(',',$param['label']) : $param['label']) : '';
		if(!is_array($param['content'])){
			$param['content'] = array($param['content']);
		}else if(empty($param['content'][0])){
			$param['content'] = array($param['content']);
		}
		$data['content'] = serialize($param['content']);
		$data['send_time'] = $param['send_time'] ? $param['send_time'] : time();
		$data['add_time'] = time();
		$task_id = M('Process_plan_msg')->data($data)->add();
		
		import('@.ORG.plan');
		$plan_class = new plan();
		$param = array(
			'file'=>'msg',
			'plan_time'=>$data['send_time'],
			'param'=>array(
				'id'=>$task_id,
			),
		);
		$plan_class->addTask($param);
	}
}
?>