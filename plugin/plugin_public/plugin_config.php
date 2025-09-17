<?php
/*
 * 项目公共配置
 *
 */
if(empty($_SERVER['REQUEST_SCHEME'])){
	if($_SERVER['SERVER_PORT'] == '443'){
		$_SERVER['REQUEST_SCHEME'] = 'https';
	}else{
		$_SERVER['REQUEST_SCHEME'] = 'http';
	}
}

$global_config = array(
	/*URL配置*/
	'URL_MODEL' 			=> '0',
	'APP_AUTOLOAD_PATH'     => '@.ORG',
	
	/*系统变量名称设置*/
	'VAR_MODULE'  			=> 'c',				//将系统默认的m改为c

	/*Cookie配置*/
	'COOKIE_PATH'           => '/',     		// Cookie路径
    'COOKIE_PREFIX'         => '',      		// Cookie前缀 避免冲突

	/*定义模版标签*/
	'TMPL_L_DELIM'   		=> '{pigcms{',		//模板引擎普通标签开始标记
	'TMPL_R_DELIM'			=> '}',				//模板引擎普通标签结束标记
	'TMPL_TEMPLATE_SUFFIX'	=> '.php',			//默认模板文件后缀

	/*常用文件定义*/
	'JQUERY_FILE' 			=> '/static/js/jquery-1.7.min.js',				// Jquery 文件
	'JQUERY_FILE_190' 		=> '/static/js/jquery-1.9.min.js',				// Jquery 文件 1.9.0
	'JQUERY_FILE_191' 		=> '/static/js/jquery-1.9.1.min.js',			// Jquery 文件 1.9.1
	
	/*SESSION*/
	'SESSION_AUTO_START'    => false,
	/*跳转的模板文件*/
	'TMPL_ACTION_SUCCESS'   => TMPL_PATH.'error.php',
	'TMPL_ACTION_ERROR'     => TMPL_PATH.'error.php',

	/*缓存*/
	'DATA_CACHE_SUBDIR'		=> true,
	'DATA_PATH_LEVEL'		=> 2,

	'VAR_FILTERS' => 'arr_htmlspecialchars',
	
	/*调试*/
	//'SHOW_PAGE_TRACE' =>true,
	'URL_HTML_SUFFIX'=>'html|shtml|xml',	//限制伪静态的后缀

	'DB_CHARSET' => 'utf8mb4',				//卢敏新增，为了适应emoji表情需求
);

$db_config = include(WEB_PATH.'conf/db.php');

$config = array_merge($global_config,$db_config);

return $config;


?>