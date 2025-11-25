<?php
header("Content-type: text/html; charset=utf-8");

define('PLUGIN_PATH',dirname(__FILE__).'/');

define('APP_NAME', 'cms');	//项目名称
define('APP_PATH',PLUGIN_PATH);	//项目目录
define('CONF_PATH',PLUGIN_PATH.'Conf/');	//配置文件地址
define('RUNTIME_PATH',PLUGIN_PATH.'../../runtime/plugin/'.PLUGIN_NAME.'/');	//缓存文件地址

define('APP_DEBUG',true);	//开启DEBUG

define('MEMORY_LIMIT_ON',function_exists('memory_get_usage'));
define('RUNTIME_FILE',RUNTIME_PATH.'~runtime.php');
define('THINK_PATH', PLUGIN_PATH.'../../core/');
if(!APP_DEBUG && is_file(RUNTIME_FILE)){
    require RUNTIME_FILE;
}else{
    require THINK_PATH.'Common/runtime.php';
}
?>