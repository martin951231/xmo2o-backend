<?php
class BaseAction extends Action{
	protected function _initialize(){
		//基类
		$plugin_base = include(PLUGIN_PATH.'../plugin_public/plugin_base.php');
		//基类公共参数
		foreach($plugin_base['action'] as $key => $value){
			$this->$key = $value;
		}
		foreach($plugin_base['tpl'] as $key => $value){
			$this->assign($key,$value);
		}
		
		if(session_status() !== PHP_SESSION_ACTIVE) session_start();
	}
}