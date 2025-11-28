<?php

if(!defined('PigCms_VERSION')){
	exit('deny access!');
}

if(!function_exists('plan_curlGet')){
	function plan_curlGet($url,$timeout){
		$ch = curl_init($url);  
		curl_setopt($ch, CURLOPT_HEADER, 0);  
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 	//不需要等待返回结果，
		curl_setopt($ch, CURLOPT_NOSIGNAL, true);
		curl_setopt($ch, CURLOPT_TIMEOUT_MS, $timeout);
		$result = curl_exec($ch);  
		
		$curl_info  = curl_getinfo($ch);
		if(($curl_info['http_code'] == 301 || $curl_info['http_code'] == 302) && $curl_info['redirect_url']){
			return plan_curlGet($curl_info['redirect_url'],$timeout);
		}
		
		curl_close($ch);
		return $result;
	}
}

$plan_documentRoot = dirname(__file__).'/';	//当前文件的目录

$is_slave = file_get_contents($plan_documentRoot.'slave_server.txt');
if($is_slave){
	return true;
}

define('IS_RUN_PLAN',true);

$plan_timeDocumentRoot = $plan_documentRoot.'time/';		//时间文件存放目录

if(!file_exists($plan_timeDocumentRoot)){
	mkdir($plan_timeDocumentRoot,0777,true);
}

$plan_processTimeFile  = $plan_timeDocumentRoot.'process.time';	//线程当前执行时间

$plan_stopTheadFile  = $plan_timeDocumentRoot.'stop.thead';

$plan_selfUrl  = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].'/';

$plan_postTime = 600;

if((!file_exists($plan_processTimeFile) || file_get_contents($plan_processTimeFile) < time() - 20)){
	$plan_theadSafe = md5(mt_rand(100000,999999).uniqid().mt_rand(100000,999999));
	
	file_put_contents($plan_processTimeFile,time());
	file_put_contents($plan_timeDocumentRoot.'md5.php','<?php return \''.$plan_theadSafe.'\';?>');
	
	plan_curlGet($plan_selfUrl.'index.php?g=Index&c=Plan&pigcms_process_theadSafe='.$plan_theadSafe,$plan_postTime);
}

//OSS计划任务
if(C('config.static_oss_switch') || C('config.static_obs_switch') || C('config.static_cos_switch')){
	include(str_replace('\'','/',__DIR__).'/oss.php');
}