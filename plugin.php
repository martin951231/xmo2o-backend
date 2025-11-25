<?php
header("Content-type: text/html; charset=utf-8");

$plugin = $_GET['plugin'];
$now_dir = dirname(__FILE__);

$plugin_file = $now_dir.'/plugin/'.$plugin.'/index.php';

//尝试生成新的插件文件
if(!file_exists($plugin_file)){
	//文件不存在，但是目录存在。
	if(file_exists($now_dir.'/plugin/'.$plugin)){
		copy($now_dir.'/plugin/plugin_public/public/install/index.php',$plugin_file);
	}else{	//目录也不存在，直接报错。
		die('应用不存在，请查看plugin目录下的 “readme.txt”');
	}
}

define('PLUGIN_NAME',$plugin);
define('PLUGIN_PUBLIC_PATH',$now_dir.'/plugin/plugin_public/');

include($plugin_file);