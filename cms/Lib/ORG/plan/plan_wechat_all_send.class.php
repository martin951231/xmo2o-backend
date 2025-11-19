<?php
class plan_wechat_all_send extends plan_base{
	public function runTask($param=array()){
		//删除计划任务
		if($param['plan_task_id']){
			$this->dropPlan($param['plan_task_id']);
		}
		
		if($param){
			$where['pigcms_id']=$send_id =$param['id'];
			$send_log =  M('Send_log')->where($where)->find();
		}else{
			$where['type'] = 10;
			$where['dateline'] = array('elt',time());
			$where['status'] = 0;
			$send_log =  M('Send_log')->where($where)->find();
			$send_id = $send_log['pigcms_id'];
		}
		
		$this->keepThread();
		
		if($send_log['send_type']==0){
			$openids  = M('User')->field('uid,openid')->where(array('level'=>$send_log['send_to'],'openid'=>array('neq',''),'status'=>1))->select();
		}elseif($send_log['send_type']==1){
			$openids = M('User')->field('uid,openid')->where(array('uid'=>array('in',trim($send_log['send_to'],',')),'openid'=>array('neq','')))->select();
		}elseif($send_log['send_type']==3){		//新增按照标签推送信息
			//$openids  = M('User')->field('openid')->where(array('_string'=>'FIND_IN_SET('.$send_log['send_to'].',`label_id`)','openid'=>array('neq','')))->select();
            $openids  = M('User_label_bind')->alias('l')->join('INNER JOIN '.C('DB_PREFIX').'user as u ON l.uid=u.uid')->where(['l.label_id'=>$send_log['send_to'],'u.openid'=>['NEQ',''],'u.status'=>1])->field('u.uid,u.openid')->select();
		}else{
			$openids= M('User')->field('uid,openid')->where(array('openid'=>array('neq',''),'status'=>1))->select();
		}
		
		fdump_sql([M('User')->getLastSql(), $send_log],'plan_wechat_all_send');
		
		$this->keepThread();

		
		$subplan = M("Process_sub_plan");
		foreach($openids as $user){
			
			$this->keepThread();
			
			$insert = [
				'param' => serialize(['uid'=>$user['uid'],'openid'=>$user['openid'],'log_id'=>$send_id,'c_id'=>$send_log['c_id']]),
				'plan_time' => 0,
				'space_time' => 0,
				'add_time' => time(),
				'error_count' => 0,
				'url' => '',
				'file' => 'wechat_all_send_sub',
				'time_type' => 1,
				'unique_id' => uniqid().substr($user['openid'],0,10),
				'rand_number' => mt_rand(1, max(C('config.sub_process_num'), 3)),
			];
			$a = $subplan->data($insert)->add();
		}
		M('Send_log')->where(array('pigcms_id' => $send_id))->save(array('status' => 1));
		return true;
	}
}
?>