<?php
class IndexAction extends BaseAction{
    public function index(){
		echo '插件文件已生成。<br/>目录所在位置：'.realpath(WEB_PATH.'plugin/'.PLUGIN_NAME);
    }
	
	//后台跳转	系统后台、商家后台、社区后台
	public function redirect(){
		$returnArr = build_redirect_info();
		dump($returnArr);
	}
}