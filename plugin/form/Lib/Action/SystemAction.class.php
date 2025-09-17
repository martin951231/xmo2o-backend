<?php
class    SystemAction extends BaseAction{
	protected function _initialize(){
		parent::_initialize();
		
		//初始化 SESSION
        session_start();
		
		//系统后台会话状态 开始
		$this->system_session = session('system');
		if(empty($this->system_session)){
			header("Location: ".$this->config['site_url'].'/admin.php');
			exit();
		}
		$this->assign('system_session',$this->system_session);
		//系统后台会话状态 结束
	}
	
    public function index(){
		$this->display();
    }
}