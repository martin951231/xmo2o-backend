<?php
header("Content-type: text/html; charset=utf-8");

// if(get_magic_quotes_gpc()){
// 	function stripslashes_deep($value){
// 		$value = is_array($value) ? array_map('stripslashes_deep',$value) : stripslashes($value);
// 		return $value;
// 	}
// 	$_POST = array_map('stripslashes_deep',$_POST);
// 	$_GET = array_map('stripslashes_deep',$_GET);
// 	$_COOKIE = array_map('stripslashes_deep',$_COOKIE);
// }

define('APP_NAME', 'cms');	//项目名称
define('APP_PATH','../cms/');	//项目目录
define('CONF_PATH','../conf/');	//配置文件地址
define('RUNTIME_PATH','../runtime/');	//缓存文件地址
define('TMPL_PATH','../tpl/');	//模板目录
define('APP_DEBUG',true);	//开启DEBUG
define('MEMORY_LIMIT_ON',function_exists('memory_get_usage'));

$_GET['g'] = 'Merchant';
$_GET['c'] = 'Pay';
$_GET['a'] = 'return_url';
$_GET['pay_type'] = 'weixinh5';

$filename = 'pay';
$fileName = rtrim($_SERVER['DOCUMENT_ROOT'],'/').'/runtime/'.$filename.'_fdump.php';
file_put_contents($fileName,'<?php'.PHP_EOL.date('Y-m-d H:i:s').PHP_EOL.var_export($_GET,true));
file_put_contents($fileName,PHP_EOL.date('Y-m-d H:i:s').PHP_EOL.var_export($_POST,true).PHP_EOL,FILE_APPEND);

$runtime = '~Web_weixinh5_source_runtime.php';
define('RUNTIME_FILE',RUNTIME_PATH.$runtime);
if(!APP_DEBUG && is_file(RUNTIME_FILE)){
    require RUNTIME_FILE;
}else{
    define('THINK_PATH', dirname(__FILE__).'/../core/');
    require THINK_PATH.'Common/runtime.php';
}
?>