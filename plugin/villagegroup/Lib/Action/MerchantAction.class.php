<?php
class MerchantAction extends BaseAction{
	protected function _initialize(){
		parent::_initialize();
		
		//初始化 SESSION
        session_start();
		
		//系统后台会话状态 开始
		$this->merchant_session = session('merchant');
		if(empty($this->merchant_session)){
			header("Location: ".$this->config['site_url'].'/merchant.php');
			exit();
		}
		$this->assign('merchant_session',$this->merchant_session);
		//系统后台会话状态 结束
	}
	
    public function index(){
		$this->display();
    }
}