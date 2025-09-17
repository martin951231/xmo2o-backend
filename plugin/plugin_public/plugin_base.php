<?php
//插件基类
function returnCode($code=0,$result=array(),$error_msg=''){
	header("Content-type: application/json");
	if($code == 0){
		$array = array(
			'errorCode'=>0,
			'errorMsg'=>'success',
			'result'=>$result
		);
	}elseif(!empty($result)){
		$array = array(
			'errorCode'=>$code,
			'errorMsg'=>'success',
			'result'=>$result
		);
	}elseif(!empty($error_msg)){
		$array = array(
			'errorCode'=>$code,
			'errorMsg'=>$error_msg,
		);
	}else{
		include(WEB_PATH.'cms/Lib/ORG/app_api.class.php');
		$app_api = new app_api();
		$error = $app_api->errorTip($code);
		$array = array(
			'errorCode'=>$code,
			'errorMsg'=>$error
		);
	}

	echo json_encode($array);
	exit();
}

function returnCodeError($return){
	if(is_array($return)){
		returnCode(1001,$return);
	}else{
		returnCode(1001,array(),$return);
	}
}

function jsonError($return){
	returnCodeError($return);
}

function returnCodeOk($return = []){
	returnCode(0,$return);
}

function jsonOk($return = []){
	returnCodeOk($return);
}

function layerOk($count,$data,$retval=[]){
	$returnArr = [];
	$returnArr['code'] = 0;
	$returnArr['msg'] = '';
	$returnArr['count'] = $count;
	$returnArr['data'] = $data;
    $returnArr['retval'] = $retval;
	
	echo json_encode($returnArr);
	exit();
}
function layerError($msg){
	$returnArr = [];
	$returnArr['code'] = 1000;
	$returnArr['msg'] = $msg;
	
	echo json_encode($returnArr);
	exit();
}

function loadSystemOrg($class_name){
    if (!class_exists($class_name, false)) {
        // 如果类不存在 则导入类库文件
        include(WEB_PATH . 'cms/Lib/ORG/' . $class_name . '.class.php');
    }
}

function getObj(){
    $arr = array('test1'=>'test2');
    $arr = json_encode($arr);
    $arr = json_decode($arr);
    unset($arr->test1);
    return $arr;
}

// 多后台跳转到插件中心的统一方法
function build_redirect_info(){
	if(session_status() !== PHP_SESSION_ACTIVE) session_start();
	
	$from = trim($_GET['from']);
	if(empty($from)){
		die('访问形式不正确');
	}
	$returnArr['from'] = $from;
	switch($from){
		case 'merchant':
			//商家后台会话状态 开始
			$merchant_session = session('merchant');
			if(empty($merchant_session)){
				redirect(C('config.site_url') . '/merchant.php');
			}
			$returnArr['from_id'] = $merchant_session['mer_id'];
			$returnArr['from_type'] = '商家';
			$returnArr['from_name'] = $merchant_session['name'];
			break;
		case 'system':
			$system_session = session('system');
			if(empty($system_session)){
				redirect(C('config.site_url') . '/admin.php');
			}
			$returnArr['from_id'] = $system_session['admin_id'];
			$returnArr['from_type'] = '平台';
			$returnArr['from_name'] = '平台';
			break;
		case 'house':
			$house_session = session('house');
			fdump($house_session,'$house_session');
			if(empty($house_session)){
				redirect(C('config.site_url') . '/shequ.php');
			}
			$returnArr['from_id'] = $house_session['village_id'];
			$returnArr['from_type'] = C('config.house_name');
			$returnArr['from_name'] = $house_session['village_name'];
			break;
		case 'street':
			$house_street_session = session('house_street');
			fdump($house_street_session,'$house_street_session');
			if(empty($house_street_session)){
				redirect(C('config.site_url') . '/v20/public/platform/#/community/street_community.iframe/street_index');
			}
			$returnArr['from_id'] = $house_street_session['area_id'];
			$returnArr['from_type'] = '街道';
			$returnArr['from_name'] = $house_street_session['area_name'];
			break;
		case 'community':
			$house_street_session = session('house_street');
			fdump($house_street_session,'$house_community_session');
			if(empty($house_street_session)){
				redirect(C('config.site_url') . '/v20/public/platform/#/community/street_community.iframe/street_index');
			}
			$returnArr['from_id'] = $house_street_session['area_id'];
			$returnArr['from_type'] = '社区';
			$returnArr['from_name'] = $house_street_session['area_name'];
			break;
		default:
			die('访问形式不正确');
	}
	return $returnArr;
}

// 生成一个 Ticket
function getTicket($device_id, $uid){
	$ticketArr = ticket::create($uid, $device_id, true);
	$ticket = $ticketArr['ticket'];
	return $ticket;
}
// 解析出来 Ticket
function parseTicket($device_id= '', $ticket = ''){
	if(!$device_id){
		$device_id = trim($_POST['Device-Id']);
	}
	if(!$ticket){
		$ticket = trim($_POST['ticket']);
	}
	$info = ticket::get($ticket, $device_id, true);
	$uid = $info['uid'];
	return $uid;
}

function json($data){
    header('Content-type: application/json');
    echo json_encode($data,JSON_UNESCAPED_UNICODE);
    exit();
}

if(empty($_POST)){
	$input_post = file_get_contents('php://input');
	$_POST = json_decode($input_post,true);
	if(!empty($_POST)){
		$_REQUEST = array_merge($_REQUEST,$_POST);
	}
}

// action 控制器
// tpl    模板，会直接assign

if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])) {    //其他cdn
	$_SERVER['REQUEST_SCHEME'] = $_SERVER['HTTP_X_FORWARDED_PROTO'];
} else if (isset($_SERVER['HTTP_X_CLIENT_SCHEME'])) {    //阿里cdn
	$_SERVER['REQUEST_SCHEME'] = $_SERVER['HTTP_X_CLIENT_SCHEME'];
}

//全局配置项
$now_lang = '';
if($_POST['now_lang']){
	$now_lang = $_POST['now_lang'];
}else if(cookie('system_lang')){
	$now_lang = cookie('system_lang');
}
$config = plugin_model('config')->get_config($now_lang);
$config['site_url'] = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];

$plugin_base['action']['config'] = $config;
$plugin_base['tpl']['config'] = $config;
C('config', $config);

//网站根目录公用模板
$plugin_base['action']['static_public'] = $plugin_base['action']['config']['site_url'] . '/static/';
$plugin_base['tpl']['static_public'] = $plugin_base['action']['static_public'];

//插件根目录公用模板
$plugin_base['action']['plugin_public'] = $plugin_base['action']['config']['site_url'] . '/plugin/plugin_public/static/';
$plugin_base['tpl']['plugin_public'] = $plugin_base['action']['plugin_public'];

return $plugin_base;