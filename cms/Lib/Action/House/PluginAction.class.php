<?php
class PluginAction extends BaseAction {
	public function index(){
		$this->display();
	}
	// 客服应用跳转
	public function kefu(){
		$this->display();
	}
	// 投票应用跳转
	public function vote(){
		$this->display('kefu');
	}
	// 模板应用跳转
	public function material_diy(){
		$this->display('kefu');
	}
	// 蓝牙门禁
	public function door_list() {
		$this->display('kefu');
	}


	// 第三方跳转
	public function page(){
		$this->display();
	}
}
?>