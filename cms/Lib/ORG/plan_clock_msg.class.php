<?php
//计划定时推送任务
class plan_clock_msg extends plan_base{
	public function runTask(){
		$now_time = time();
		$model=M('plan_clock_msg');	
		$where=array();
		$where['send_time']=array('elt',$now_time);
		$where['status']=0;
		$msg_list=$model->where($where)->order('send_time asc')->limit(5)->select();
		
		//尽量避免重复执行，先去除任务
		$msg_id = array();
		foreach($msg_list as $value){
			$msg_id[] = $value['id'];
		}
		$model->where(array('id'=>array('in',$msg_id)))->data(array('status'=>'1'))->save();
		import('@.ORG.plan');
		$plan_class = new plan();
		foreach($msg_list as $value){
			$this->keepThread();

			//把推送写入及时推送的数据表中
			$data=array();
			$data['type']=$value['type'];
			$data['label']='';
			$data['content']=$value['content'];
			$data['send_time']=time();
			$data['add_time']=time();
			$task_id = M('Process_plan_msg')->data($data)->add();
			//添加及时发送定时任务
			$param = array(
				'file'=>'msg',
				'plan_time'=>$data['send_time'],
				'param'=>array(
					'id'=>$task_id,
				),
			);
			$plan_class->addTask($param);
        }
		return true;
	}	
}
?>