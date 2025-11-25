<?php
//事件交互
class Event_wayAction extends BaseAction{
	public function add(){
		$id = M('Event_way')->data(['msg'=> ''])->add();
		$this->returnCodeOk($id);
	}
	
	public function up(){
		if(M('Event_way')->where(['id'=>$_POST['id']])->data(['msg'=> $_POST['msg']])->save() !== false){
			$this->returnCodeOk([]);
		}else{
			$this->returnCodeError(L_('事件内容存储错误'));
		}
	}
	
	public function down(){
		$event_way = M('Event_way')->where(['id'=>$_POST['id']])->find();
		if(empty($event_way)){
			$this->returnCode(10001, [], L_('事件已被删除'));
		}else{
			if($event_way['msg']){
				M('Event_way')->where(['id'=>$_POST['id']])->delete();
			}
			$this->returnCodeOk($event_way['msg']);
		}
	}
}
?>