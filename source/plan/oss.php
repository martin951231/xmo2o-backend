<?php

if(!defined('PigCms_VERSION')){
	exit('deny access!');
}

$plan_documentRoot = dirname(__file__).'/';	//当前文件的目录

$plan_timeDocumentRoot = $plan_documentRoot.'time/';		//时间文件存放目录

$plan_postTimeFile 	= $plan_timeDocumentRoot.'post_oss.time';	//执行任务的间隔时间

if(!file_exists($plan_timeDocumentRoot)){//
	mkdir($plan_timeDocumentRoot,0777,true);
}

$plan_processTimeFile  = $plan_timeDocumentRoot.'process_oss.time';	//线程当前执行时间

$plan_stopTheadFile  = $plan_timeDocumentRoot.'stop_oss.thead';

$plan_selfUrl  = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].'/';

// $plan_postTime = gethostbyname($_SERVER['HTTP_HOST']) == '127.0.0.1' ? 200 : 600;
$plan_postTime = 600;

if((!file_exists($plan_processTimeFile) || file_get_contents($plan_processTimeFile) < time() - 120)){
// if((!file_exists($plan_processTimeFile) || file_get_contents($plan_processTimeFile) < time() - 20)){
	$plan_theadSafe = md5(mt_rand(100000,999999).uniqid().mt_rand(100000,999999));
	
	file_put_contents($plan_processTimeFile,time());
	
	file_put_contents($plan_timeDocumentRoot.'md5_oss.php','<?php return \''.$plan_theadSafe.'\';?>');
	
	// echo $plan_selfUrl.'?c=Plan&pigcms_process_theadSafe='.$plan_theadSafe;exit;
	
	fdump($plan_selfUrl.'index.php?g=Index&c=Planoss&pigcms_process_theadSafe='.$plan_theadSafe,'oss_plan');
	fdump(plan_curlGet($plan_selfUrl.'index.php?g=Index&c=Planoss&pigcms_process_theadSafe='.$plan_theadSafe,$plan_postTime),'oss_plan',true);
	return true;
}else{
	return true;
}