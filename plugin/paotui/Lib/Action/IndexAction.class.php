<?php
class IndexAction extends BaseAction{
    public function index(){
		echo '插件文件已生成。<br/>目录所在位置：'.realpath(WEB_PATH.'plugin/'.PLUGIN_NAME);
    }
}