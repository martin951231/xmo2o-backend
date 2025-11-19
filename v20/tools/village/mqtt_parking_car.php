#!/usr/bin/env php
<?php
/**
 * Created by PhpStorm.
 * Date Time: 2024/9/4
 */

namespace think;

use Workerman\Mqtt\Client;
use Workerman\MySQL\Connection;
use Workerman\Worker;
use Workerman\Connection\TcpConnection;
use Workerman\Lib\Timer;
$file_path_d = __DIR__;
$realpath = dirname($file_path_d, 3);
defined('WEB_PATH') or define('WEB_PATH', $realpath . '/');
require realpath($realpath . '/v20/vendor/autoload.php');
$database_config = include realpath($realpath . '/conf/db.php');
// 初始化ThinkPHP应用实例
$v20Path = $realpath . '/v20/';
$app = App::getInstance();
$appRunObj = $app->initialize();
$appRootPath = $appRunObj->getRootPath();

use app\community\model\service\Park\ParkApiControlService;
// 初始化worker类
TcpConnection::$defaultMaxSendBufferSize=10485760*5;  //50M
TcpConnection::$defaultMaxPackageSize=10485760*10;  //100M

$worker = new Worker();
$db = new Connection($database_config['DB_HOST'], $database_config['DB_PORT'], $database_config['DB_USER'], $database_config['DB_PWD'], $database_config['DB_NAME']);
$_SERVER['DOCUMENT_ROOT']=$realpath;
//fdump_api([$file_path_d, $realpath, 'appRootPath' => $appRootPath, 'v20Path' => $v20Path, $database_config], 'mqtt_parking_car', 1);

/***  客户端  ***/
$config = array('ip' => 'mqtt://192.168.1.6:1883', 'username' => 'admin', 'password' => '','realpath'=>$realpath);
$worker->count = 1;
$worker->onWorkerStart = function () use ($config) {
    $is_message = 1;
    $realpath_tmp=$config['realpath'];
    $_SERVER['DOCUMENT_ROOT']=$realpath_tmp;
    $address = $config['ip'];
    $optioins = [
        'username' => $config['username'],
        'password' => $config['password'],
        'keepalive'=>10,
        'reconnect_period'=>2,
        'debug' => false
    ];
    $mqtt = new Client($address, $optioins);
    $mqtt->onConnect = function ($mqtt) {
        subscribeTopic($mqtt);
        Timer::add(1, function()use($mqtt){
            automaticHeartBeat($mqtt,'');
        });
    };
    $mqtt->onMessage = function ($topic, $content, $mqtt) use ($is_message,$realpath_tmp) {
        $_SERVER['DOCUMENT_ROOT']=$realpath_tmp;
        onMessage($topic, $content, $mqtt, $is_message);
    };
    $mqtt->connect();
   
};
Worker::runAll();

function automaticHeartBeat($mqtt,$channel_serialno='')
{
    $parkControllerHandle = new ParkApiControlService();
    $per_exec_times=5;
    if($channel_serialno){
        $per_exec_times=1;
    }
    for ($ii = 0; $ii < $per_exec_times; $ii++) {
        $now_time = time();
        if($channel_serialno){
            $channel_id_serialno=$channel_serialno;
        }else{
            $channel_id_serialno = $parkControllerHandle->getOneMqttSerialno();
        }
        //fdump_api(['channel_id_serialno'=>$channel_id_serialno,'per_exec_times'=>$per_exec_times,'channel_serialno'=>$channel_serialno,'now_time'=>$now_time], 'parkApi/00automaticHeartBeat'.date('H'), 1);
        if (!empty($channel_id_serialno)) {
            $device_sn = $channel_id_serialno;
            $topic = 'device/' . $device_sn . '/message/up/keep_alive';
            $idStr = uniqid();
            $idStr = 'BB' . $idStr . 'C';
            $idStr = strtoupper($idStr);
            $msgReturnBase = array('id' => $idStr, 'sn' => $device_sn, 'name' => '', 'version' => '1.0', 'timestamp' => $now_time);
            $pdata = array();
            $pdata['serialno'] = $device_sn;
            $pdata['channel_num'] = 1;
            $pdata['ipaddr'] = '';
            $responseJosn = $parkControllerHandle->receivedeviceinfo($pdata, 'hz_mqttRequest');
            //fdump_api(['keep_alive_automatic', 'device_sn' => $device_sn, 'responseJosn' => $responseJosn], 'parkApi/00automaticHeartBeat' . date('H'), 1);
            $responseArr = $responseJosn && isset($responseJosn['dataArr']) ? $responseJosn['dataArr'] : '';
            $response_time = time();
            $msgReturnBase['timestamp'] = time();
            $response_save = array('responseArr' => $responseArr);
            $is_have_handle = 0;
            
            if($responseArr && isset($responseArr['is_close_park_passage']) && $responseArr['is_close_park_passage']==1){
                  //关闸
                $topicuri = 'device/' . $device_sn . '/message/down/gpio_out';
                $msgReturnArr = $msgReturnBase;
                $msgReturnArr['name'] = 'gpio_out';
                $msgReturnArr['payload'] = array('type' => 'gpio_out', 'body' => array('delay' => 500, 'io' => 0, 'value' => 0));
                $msgReturnJson = json_encode($msgReturnArr, JSON_UNESCAPED_UNICODE);
                $ret=$mqtt->publish($topicuri, $msgReturnJson);
                fdump_api(['device_sn' => $device_sn,'close_park_passage'=>1,'topicuri'=>$topicuri,'msgReturnJson'=>$msgReturnJson,'gpio_out'=>$ret], 'parkApi/00automaticHeartBeat'.date('H'), 1);
                $msgReturnArr['topicuri'] = $topicuri;
                $response_save['gpio_out'] = $msgReturnArr;
            }
            
            if ($responseArr && isset($responseArr['Response_AlarmInfoPlate']) && isset($responseArr['Response_AlarmInfoPlate']['info']) && $responseArr['Response_AlarmInfoPlate']['info'] == 'ok') {
                $topicuri = 'device/' . $device_sn . '/message/down/gpio_out';
                $msgReturnArr = $msgReturnBase;
                $msgReturnArr['name'] = 'gpio_out';
                $msgReturnArr['payload'] = array('type' => 'gpio_out', 'body' => array('delay' => 500, 'io' => 0, 'value' => 2));
                $msgReturnJson = json_encode($msgReturnArr, JSON_UNESCAPED_UNICODE);
                $ret=$mqtt->publish($topicuri, $msgReturnJson);
                fdump_api(['device_sn' => $device_sn,'topicuri'=>$topicuri,'msgReturnJson'=>$msgReturnJson,'gpio_out'=>$ret], 'parkApi/00automaticHeartBeat'.date('H'), 1);
                $msgReturnArr['topicuri'] = $topicuri;
                $response_save['gpio_out'] = $msgReturnArr;
                $is_have_handle = 1;
            }
            $msgReturnBase['timestamp'] = time();
            if ($responseArr && isset($responseArr['Response_AlarmInfoPlate']) && isset($responseArr['Response_AlarmInfoPlate']['serialData']) && $responseArr['Response_AlarmInfoPlate']['serialData']) {
                $topicuri = 'device/' . $device_sn . '/message/down/serial_data';
                $msgReturnArr = $msgReturnBase;
                $msgReturnArr['name'] = 'serial_data';
                $msgReturnArr['payload'] = array('type' => 'serial_data', 'body' => array('interval' => 100, 'serialData' => $responseArr['Response_AlarmInfoPlate']['serialData']));
                $msgReturnJson = json_encode($msgReturnArr, JSON_UNESCAPED_UNICODE);
                $ret= $mqtt->publish($topicuri, $msgReturnJson);
                fdump_api(['device_sn' => $device_sn,'topicuri'=>$topicuri,'msgReturnJson'=>$msgReturnJson,'serial_data'=>$ret], 'parkApi/00automaticHeartBeat'.date('H'), 1);
                $msgReturnArr['topicuri'] = $topicuri;
                $response_save['serial_data'] = $msgReturnArr;
                $is_have_handle = 1;
            }
            $msgReturnBase['timestamp'] = time();
            if ($responseArr && isset($responseArr['Response_AlarmInfoPlate']) && isset($responseArr['Response_AlarmInfoPlate']['white_list_operate']) && $responseArr['Response_AlarmInfoPlate']['white_list_operate']) {
                $topicuri = 'device/' . $device_sn . '/message/down/white_list_operator';
                $msgReturnArr = $msgReturnBase;
                $bodyArr = $responseArr['Response_AlarmInfoPlate']['white_list_operate']['white_list_data'];
                $operate_type = $responseArr['Response_AlarmInfoPlate']['operate_type'];
                $operate_type_str = '';
                if ($operate_type == 0) {
                    $operate_type_str = 'update_or_add';
                } elseif ($operate_type == 1) {
                    $operate_type_str = 'delete';
                }
                //白名单处理
                foreach ($bodyArr as $kk => $vv) {
                    $bodyTmpArr = '';
                    if ($operate_type_str == 'update_or_add') {
                        $vv['create_time'] = $vv['enable_time'];
                        $vv['time_seg_enable'] = 0;
                        $bodyTmpArr = array('operator_type' => $operate_type_str, 'dldb_rec' => $vv);
                    } else if ($operate_type_str == 'delete') {
                        $bodyTmpArr = array('operator_type' => $operate_type_str, 'plate' => $vv['plate']);
                    }
                    if ($operate_type_str && $bodyTmpArr) {
                        $msgReturnArr['name'] = 'white_list_operator';
                        $msgReturnArr['payload'] = array('type' => 'white_list_operator', 'body' => $bodyTmpArr);
                        $msgReturnJson = json_encode($msgReturnArr, JSON_UNESCAPED_UNICODE);
                        $mqtt->publish($topicuri, $msgReturnJson);
                    }
                }
                $is_have_handle = 1;
            }
            $is_have_handle = 0;
            if ($is_have_handle > 0) {
                //fdump_api(['keep_alive_automatic', 'topic' => $topic, 'content' => '', 'pdata' => $pdata, 'responseJosn' => $responseJosn], 'parkApi/00automaticHeartBeat' . date('H'), 1);
                $response_save_str = json_encode($response_save, JSON_UNESCAPED_UNICODE);

                $insert_data = array('topic' => $topic, 'idstr' => $idStr, 'device_sn' => $device_sn, 'topic_name' => 'keep_alive_automatic', 'timestamp' => $now_time);
                $insert_data['payload_data'] = '';
                $insert_data['code'] = '';
                $insert_data['receive_time'] = $now_time;
                $insert_data['add_time'] = time();
                $insert_data['response_data'] = $response_save_str;
                $insert_data['response_time'] = $response_time;
                //$parkControllerHandle->addOneCarMqttRecord($insert_data);
            }
        }else{
            break;
        }
    }
    $dateM=date('i');
    $dateM=intval($dateM);
    $yushu=$dateM%5;
    $dateS=date('s');
    $dateS=intval($dateS);
    if($yushu==1 && in_array($dateS,[10,20,30,40,50])){
        dynamicsSubscribeTopic($mqtt);
    }
    return true;
}
function onMessage($topic = '', $content, $mqtt, $is_message)
{
    //fdump_api(['topic'=>$topic, 'xtime'=>time()], 'parkApi/mqtt_parking_car'.date('H'), 1);
    $msg = json_decode($content, true);
   // fdump_api(['topic'=>$topic, 'content'=>$content,'msg'=>$msg], 'parkApi/mqtt_parking_car'.date('H'), 1);
    if (empty($msg) || !isset($msg['name'])) {
        return false;
    }
    $name_topic = $msg['name'] ? trim($msg['name']) : '';
    $device_sn = $msg['sn'] ? trim($msg['sn']) : '';
    $id = $msg['id'];
    $timestamp = $msg['timestamp']; //时间戳
    $now_time = time();
    $payloadData = isset($msg['payload']) && $msg['payload'] ? $msg['payload'] : '';  //消息数据
    if ($payloadData == 'null') {
        $payloadData = '';
    }
    $code = '';
    $msgReturnBase = array('id' => $id, 'sn' => $device_sn, 'name' => '', 'version' => $msg['version'], 'timestamp' => $now_time);
    $receive_time = time();
    switch ($name_topic) {
        case 'ivs_result':
            fdump_api(['topic'=>$topic, 'xtime'=>time()], 'parkApi/mqtt_parking_car'.date('H'), 1);
            if (!empty($payloadData) && is_array($payloadData)) {
                /*
                $urlRequest=$site_url.'/v20/public/index.php/community/common.Park/plateresult';  //识别
                $payloadData['comefrom']='hz_mqttRequest';
                $pdataJson=json_encode($payloadData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                $responseJosn=httpCurlRequest($urlRequest,'POST',$pdataJson);
                $responseArr=$responseJosn ? json_decode($responseJosn,true):'';
                */
                $car_license='';
                if (isset($payloadData['AlarmInfoPlate']['result']['PlateResult']['license'])) {
                    $car_license=$payloadData['AlarmInfoPlate']['result']['PlateResult']['license'];
                    $car_license=base64_decode($car_license);
                    if(!empty($car_license)){
                        $payloadData['AlarmInfoPlate']['result']['PlateResult']['license']=$car_license;
                    }
                }
                fdump_api(['topic'=>$topic,'car_license'=>$car_license, 'xtime'=>time()], 'parkApi/mqtt_parking_car'.date('H'), 1);
                
                if(isset($payloadData['AlarmInfoPlate']['result']['PlateResult']['small_image_content'])){
                    $payloadData['AlarmInfoPlate']['result']['PlateResult']['imageFragmentFile']=$payloadData['AlarmInfoPlate']['result']['PlateResult']['small_image_content'];
                    unset($payloadData['AlarmInfoPlate']['result']['PlateResult']['small_image_content']);
                }
                if(isset($payloadData['AlarmInfoPlate']['result']['PlateResult']['full_image_content'])){
                    $payloadData['AlarmInfoPlate']['result']['PlateResult']['imageFile']=$payloadData['AlarmInfoPlate']['result']['PlateResult']['full_image_content'];
                    unset($payloadData['AlarmInfoPlate']['result']['PlateResult']['full_image_content']);
                }
                if(isset($payloadData['AlarmInfoPlate']['result']['PlateResult']['plates'])){
                    //没有的字段 将数据重置成空
                    $payloadData['AlarmInfoPlate']['result']['PlateResult']['plates']=array();
                }
                $parkControllerHandle = new ParkApiControlService();
                $responseJosn = $parkControllerHandle->plateresult($payloadData, 'hz_mqttRequest');
                if(isset($payloadData['AlarmInfoPlate']['result']['PlateResult']['imageFragmentFile'])){
                    $payloadData['AlarmInfoPlate']['result']['PlateResult']['imageFragmentFile']='';
                }
                if(isset($payloadData['AlarmInfoPlate']['result']['PlateResult']['imageFile'])){
                    $payloadData['AlarmInfoPlate']['result']['PlateResult']['imageFile']='';
                }
                fdump_api(['topic'=>$topic, 'payloadData'=>$payloadData,'responseArr'=>$responseJosn], 'parkApi/mqtt_parking_car'.date('H'), 1);
                $responseArr = $responseJosn && isset($responseJosn['dataArr']) ? $responseJosn['dataArr'] : '';
                $response_time = time();
                $msgReturnBase['timestamp'] = time();
                $response_save = array('responseArr' => $responseJosn);
                $msgReturnJson='';
                if ($responseArr && isset($responseArr['Response_AlarmInfoPlate']) && isset($responseArr['Response_AlarmInfoPlate']['info']) && $responseArr['Response_AlarmInfoPlate']['info'] == 'ok') {
                    $topicuri = 'device/' . $device_sn . '/message/down/gpio_out';
                    $msgReturnArr = $msgReturnBase;
                    $msgReturnArr['name'] = 'gpio_out';
                    $msgReturnArr['payload'] = array('type' => 'gpio_out', 'body' => array('delay' => 500, 'io' => 0, 'value' => 2));
                    $msgReturnJson = json_encode($msgReturnArr, JSON_UNESCAPED_UNICODE);
                    $mqtt->publish($topicuri, $msgReturnJson);
                    fdump_api(['topic'=>$topic,'topicuri'=>$topicuri,'msgReturnJson'=>$msgReturnJson], 'parkApi/mqtt_parking_car'.date('H'), 1);
                    $msgReturnArr['topicuri'] = $topicuri;
                    $response_save['gpio_out'] = $msgReturnArr;
                } elseif ($responseArr && isset($responseArr['Response_AlarmInfoPlate']) && isset($responseArr['Response_AlarmInfoPlate']['serialData']) && $responseArr['Response_AlarmInfoPlate']['serialData']) {
                    $topicuri = 'device/' . $device_sn . '/message/down/serial_data';
                    $msgReturnArr = $msgReturnBase;
                    $msgReturnArr['name'] = 'serial_data';
                    $msgReturnArr['payload'] = array('type' => 'serial_data', 'body' => array('interval' => 100, 'serialData' => $responseArr['Response_AlarmInfoPlate']['serialData']));
                    $msgReturnJson = json_encode($msgReturnArr, JSON_UNESCAPED_UNICODE);
                    $mqtt->publish($topicuri, $msgReturnJson);
                    fdump_api(['topic'=>$topic,'topicuri'=>$topicuri, 'msgReturnJson'=>$msgReturnJson], 'parkApi/mqtt_parking_car'.date('H'), 1);
                    $msgReturnArr['topicuri'] = $topicuri;
                    $response_save['serial_data'] = $msgReturnArr;
                }
                
                $payloadDataStr = $payloadData ? json_encode($payloadData, JSON_UNESCAPED_UNICODE) : '';
                $response_save_str = json_encode($response_save, JSON_UNESCAPED_UNICODE);
                $insert_data=array('topic'=>$topic,'idstr'=>$id,'device_sn'=>$device_sn,'topic_name'=>$name_topic,'timestamp'=>$timestamp);
                $insert_data['payload_data']=$payloadDataStr;
                $insert_data['code']=$code;
                $insert_data['receive_time']=$receive_time;
                $insert_data['add_time']= time();
                $insert_data['response_data']=$response_save_str;
                $insert_data['response_time']=$response_time;
                //$parkControllerHandle->addOneCarMqttRecord($insert_data);
                automaticHeartBeat($mqtt,$device_sn);  //有识别后 在走一次心跳
            }

            break;
        case 'ivs_result_offline':
            //订阅离线识别结果
            /*
            $urlRequest=$site_url.'/v20/public/index.php/community/common.Park/plateresult';  //识别
            $payloadData['comefrom']='hz_mqttRequest';
            $pdataJson=json_encode($payloadData,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            $responseJosn=httpCurlRequest($urlRequest,'POST',$pdataJson);
            $response_time=time();
            $responseArr=$responseJosn ? json_decode($responseJosn,true):'';
            */
            if (!empty($payloadData) && is_array($payloadData)) {
                if (isset($payloadData['AlarmInfoPlate']['result']['PlateResult']['license'])) {
                    $car_license=$payloadData['AlarmInfoPlate']['result']['PlateResult']['license'];
                    $car_license=base64_decode($car_license);
                    if(!empty($car_license)){
                        $payloadData['AlarmInfoPlate']['result']['PlateResult']['license']=$car_license;
                    }
                }
                if(isset($payloadData['AlarmInfoPlate']['result']['PlateResult']['small_image_content'])){
                    $payloadData['AlarmInfoPlate']['result']['PlateResult']['imageFragmentFile']=$payloadData['AlarmInfoPlate']['result']['PlateResult']['small_image_content'];
                    unset($payloadData['AlarmInfoPlate']['result']['PlateResult']['small_image_content']);
                }
                if(isset($payloadData['AlarmInfoPlate']['result']['PlateResult']['full_image_content'])){
                    $payloadData['AlarmInfoPlate']['result']['PlateResult']['imageFile']=$payloadData['AlarmInfoPlate']['result']['PlateResult']['full_image_content'];
                    unset($payloadData['AlarmInfoPlate']['result']['PlateResult']['full_image_content']);
                }
                if(isset($payloadData['AlarmInfoPlate']['result']['PlateResult']['plates'])){
                    //没有的字段 将数据重置成空
                    $payloadData['AlarmInfoPlate']['result']['PlateResult']['plates']=array();
                }
                $parkControllerHandle = new ParkApiControlService();
                $responseJosn = $parkControllerHandle->plateresult($payloadData, 'hz_mqttRequest');
                $response_time=time();
                $responseArr = $responseJosn && isset($responseJosn['dataArr']) ? $responseJosn['dataArr'] : '';
                $response_save = array('responseArr' => $responseJosn);
                if(isset($payloadData['AlarmInfoPlate']['result']['PlateResult']['imageFragmentFile'])){
                    $payloadData['AlarmInfoPlate']['result']['PlateResult']['imageFragmentFile']='';
                }
                if(isset($payloadData['AlarmInfoPlate']['result']['PlateResult']['imageFile'])){
                    $payloadData['AlarmInfoPlate']['result']['PlateResult']['imageFile']='';
                }
                $payloadDataStr = $payloadData ? json_encode($payloadData, JSON_UNESCAPED_UNICODE) : '';

                $response_save_str = json_encode($response_save, JSON_UNESCAPED_UNICODE);
                $insert_data=array('topic'=>$topic,'idstr'=>$id,'device_sn'=>$device_sn,'topic_name'=>$name_topic,'timestamp'=>$timestamp);
                $insert_data['payload_data']=$payloadDataStr;
                $insert_data['code']=$code;
                $insert_data['receive_time']=$receive_time;
                $insert_data['add_time']= time();
                $insert_data['response_data']=$response_save_str;
                $insert_data['response_time']=$response_time;
               // $parkControllerHandle->addOneCarMqttRecord($insert_data);
                //fdump_api(['topic'=>$topic, 'payloadData'=>$payloadData,'responseArr'=>$responseJosn], 'parkApi/mqtt_parking_car'.date('H'), 1);
            }
        case 'keep_alive':
            $pdata = array();
            $pdata['serialno'] = $device_sn;
            $pdata['channel_num'] = 1;
            $pdata['ipaddr'] = '';
            /*
            $urlRequest=$site_url.'/v20/public/index.php/community/common.Park/receivedeviceinfo';  //心跳
            $responseJosn=httpCurlRequest($urlRequest,'POST',$pdata);
            $responseArr=$responseJosn ? json_decode($responseJosn,true):'';
            */
            //fdump_api(['topic'=>$topic, 'content'=>$content], 'parkApi/keep_alive'.date('H'), 1);
            $parkControllerHandle = new ParkApiControlService();
            $responseJosn = $parkControllerHandle->receivedeviceinfo($pdata, 'hz_mqttRequest');
            //fdump_api(['responseJosn' => $responseJosn], 'parkApi/keep_alive'.date('H'), 1);
            $responseArr = $responseJosn && isset($responseJosn['dataArr']) ? $responseJosn['dataArr'] : '';
            $response_time = time();
            $msgReturnBase['timestamp'] = time();
            $response_save = array('responseArr' => $responseArr);
            $is_have_handle = 0;
            
            if($responseArr && isset($responseArr['is_close_park_passage']) && $responseArr['is_close_park_passage']==1){
                //关闸
                $topicuri = 'device/' . $device_sn . '/message/down/gpio_out';
                $msgReturnArr = $msgReturnBase;
                $msgReturnArr['name'] = 'gpio_out';
                $msgReturnArr['payload'] = array('type' => 'gpio_out', 'body' => array('delay' => 500, 'io' => 0, 'value' => 0));
                $msgReturnJson = json_encode($msgReturnArr, JSON_UNESCAPED_UNICODE);
                $ret=$mqtt->publish($topicuri, $msgReturnJson);
                fdump_api(['device_sn' => $device_sn,'close_park_passage'=>1,'topicuri'=>$topicuri,'msgReturnJson'=>$msgReturnJson,'gpio_out'=>$ret], 'parkApi/keep_alive'.date('H'), 1);
                $msgReturnArr['topicuri'] = $topicuri;
                $response_save['gpio_out'] = $msgReturnArr;
            }
            
            if ($responseArr && isset($responseArr['Response_AlarmInfoPlate']) && isset($responseArr['Response_AlarmInfoPlate']['info']) && $responseArr['Response_AlarmInfoPlate']['info'] == 'ok') {
                $topicuri = 'device/' . $device_sn . '/message/down/gpio_out';
                $msgReturnArr = $msgReturnBase;
                $msgReturnArr['name'] = 'gpio_out';
                $msgReturnArr['payload'] = array('type' => 'gpio_out', 'body' => array('delay' => 500, 'io' => 0, 'value' => 2));
                $msgReturnJson = json_encode($msgReturnArr, JSON_UNESCAPED_UNICODE);
                $ret=$mqtt->publish($topicuri, $msgReturnJson);
                fdump_api(['topicuri'=>$topicuri ,'msgReturnJson'=>$msgReturnJson,'gpio_out'=>$ret], 'parkApi/keep_alive'.date('H'), 1);
                $msgReturnArr['topicuri'] = $topicuri;
                $response_save['gpio_out'] = $msgReturnArr;
                $is_have_handle = 1;
            }
            $msgReturnBase['timestamp'] = time();
            if ($responseArr && isset($responseArr['Response_AlarmInfoPlate']) && isset($responseArr['Response_AlarmInfoPlate']['serialData']) && $responseArr['Response_AlarmInfoPlate']['serialData']) {
                $topicuri = 'device/' . $device_sn . '/message/down/serial_data';
                $msgReturnArr = $msgReturnBase;
                $msgReturnArr['name'] = 'serial_data';
                $msgReturnArr['payload'] = array('type' => 'serial_data', 'body' => array('interval' => 100, 'serialData' => $responseArr['Response_AlarmInfoPlate']['serialData']));
                $msgReturnJson = json_encode($msgReturnArr, JSON_UNESCAPED_UNICODE);
                $ret=$mqtt->publish($topicuri, $msgReturnJson);
                fdump_api(['topicuri'=>$topicuri, 'msgReturnJson'=>$msgReturnJson,'serial_data'=>$ret], 'parkApi/keep_alive'.date('H'), 1);
                $msgReturnArr['topicuri'] = $topicuri;
                $response_save['serial_data'] = $msgReturnArr;
                $is_have_handle = 1;
            }
            $msgReturnBase['timestamp'] = time();
            if ($responseArr && isset($responseArr['Response_AlarmInfoPlate']) && isset($responseArr['Response_AlarmInfoPlate']['white_list_operate']) && $responseArr['Response_AlarmInfoPlate']['white_list_operate']) {
                $topicuri = 'device/' . $device_sn . '/message/down/white_list_operator';
                $msgReturnArr = $msgReturnBase;
                $bodyArr = $responseArr['Response_AlarmInfoPlate']['white_list_operate']['white_list_data'];
                $operate_type = $responseArr['Response_AlarmInfoPlate']['operate_type'];
                $operate_type_str = '';
                if ($operate_type == 0) {
                    $operate_type_str = 'update_or_add';
                } elseif ($operate_type == 1) {
                    $operate_type_str = 'delete';
                }
                //白名单处理
                foreach ($bodyArr as $kk => $vv) {
                    $bodyTmpArr = '';
                    if ($operate_type_str == 'update_or_add') {
                        $vv['create_time'] = $vv['enable_time'];
                        $vv['time_seg_enable'] = 0;
                        $bodyTmpArr = array('operator_type' => $operate_type_str, 'dldb_rec' => $vv);
                    } else if ($operate_type_str == 'delete') {
                        $bodyTmpArr = array('operator_type' => $operate_type_str, 'plate' => $vv['plate']);
                    }
                    if ($operate_type_str && $bodyTmpArr) {
                        $msgReturnArr['name'] = 'white_list_operator';
                        $msgReturnArr['payload'] = array('type' => 'white_list_operator', 'body' => $bodyTmpArr);
                        $msgReturnJson = json_encode($msgReturnArr, JSON_UNESCAPED_UNICODE);
                        $mqtt->publish($topicuri, $msgReturnJson);
                    }
                }
                $is_have_handle = 1;
            }
            $is_have_handle = 0;
            if ($is_have_handle > 0) {
                //fdump_api(['topic'=>$topic, 'content'=>$content, 'pdata'=>$pdata,'responseJosn'=>$responseJosn], 'parkApi/mqtt_parking_car'.date('H'), 1);
                $payloadDataStr = $payloadData ? json_encode($payloadData, JSON_UNESCAPED_UNICODE) : '';
                $response_save_str = json_encode($response_save, JSON_UNESCAPED_UNICODE);

                $insert_data=array('topic'=>$topic,'idstr'=>$id,'device_sn'=>$device_sn,'topic_name'=>$name_topic,'timestamp'=>$timestamp);
                $insert_data['payload_data']=$payloadDataStr;
                $insert_data['code']=$code;
                $insert_data['receive_time']=$receive_time;
                $insert_data['add_time']= time();
                $insert_data['response_data']=$response_save_str;
                $insert_data['response_time']=$response_time;
                //$parkControllerHandle->addOneCarMqttRecord($insert_data);
            }

            break;
        case 'gpio_in':
            $pdata = $payloadData['body'];
            if (isset($pdata['AlarmGioIn']) && $pdata['AlarmGioIn']) {
                $pdata['AlarmGioIn']['serialno'] = $device_sn;
            }
            /*
            $urlRequest=$site_url.'/v20/public/index.php/community/common.Park/gio';  //IO输入事件
            $pdata['comefrom']='hz_mqttRequest';
            $pdataJson=json_encode($pdata,JSON_UNESCAPED_UNICODE);
            $responseJosn=httpCurlRequest($urlRequest,'POST',$pdataJson);
            $responseArr=$responseJosn ? json_decode($responseJosn,true):'';
            */
            $parkControllerHandle = new ParkApiControlService();
            $responseArr = $parkControllerHandle->gio($pdata, 'hz_mqttRequest');
            //fdump_api(['pdata' => $pdata, 'responseArr' => $responseArr], 'gpio_in', 1);
        case 'serial_data':
            if(strpos($topic,'message/up/serial_data')){
                $pdata = $payloadData['body'];
                /*
                $urlRequest=$site_url.'/v20/public/index.php/community/common.Park/serial';  //推送串口数据
                if(isset($pdata['SerialData']) && $pdata['SerialData']){
                    //$pdata['SerialData']['serialno']=$device_sn;
                }
                $pdata['comefrom']='hz_mqttRequest';
                $pdataJson=json_encode($pdata,JSON_UNESCAPED_UNICODE);
                $responseJosn=httpCurlRequest($urlRequest,'POST',$pdataJson);
                $responseArr=$responseJosn ? json_decode($responseJosn,true):'';
                */
                $parkControllerHandle = new ParkApiControlService();
                $responseArr = $parkControllerHandle->serial($pdata, 'hz_mqttRequest');
                //fdump_api(['pdata' => $pdata, 'responseArr' => $responseArr], 'up_serial_data', 1);
            }

            break;
        default:
            break;
    }

}

function dynamicsSubscribeTopic($mqtt)
{
    $parkApiControlService=new ParkApiControlService();
    $passageList=$parkApiControlService->getMqttPassages();
    //fdump_api(['datetime' => date('Y-m-d H:i:s'), 'passageList' => $passageList], 'parkApi/dynamicsSubscribeTopic', 1);
    if (!empty($passageList)) {
        foreach ($passageList as $vf) {
            $device_sn = $vf['device_number'];
            if($vf['device_protocol']==0 && $vf['opt_protocol_type']==2 && $device_sn){
                 //取消订阅
                $ivs_result_topic = 'device/' . $device_sn . '/message/up/ivs_result'; //订阅识别结果
                $mqtt->unsubscribe($ivs_result_topic);
                $ivs_result_offline_topic = 'device/' . $device_sn . '/message/up/ivs_result_offline';//订阅离线识别结果
                $mqtt->unsubscribe($ivs_result_offline_topic);
                $keep_alive_topic = 'device/' . $device_sn . '/message/up/keep_alive'; //订阅心跳
                $mqtt->unsubscribe($keep_alive_topic);

                $gpio_in_topic = 'device/' . $device_sn . '/message/up/gpio_in'; //订阅IO输入事件
                $mqtt->unsubscribe($gpio_in_topic);
                $serial_data_topic = 'device/' . $device_sn . '/message/up/serial_data'; //订阅串口数据接收
                $mqtt->unsubscribe($serial_data_topic);
                $white_list_operator_reply_topic = 'device/' . $device_sn . '/message/down/white_list_operator/reply'; //白名单操作回执
                $mqtt->unsubscribe($white_list_operator_reply_topic);
                $parkApiControlService->updatePassageInfo(array('id'=>$vf['id']),array('opt_protocol_type'=>3));
                
            }else if($vf['device_protocol']==1 && $vf['opt_protocol_type']==0 && $device_sn){
                //订阅
                $ivs_result_topic = 'device/' . $device_sn . '/message/up/ivs_result'; //订阅识别结果
                $mqtt->subscribe($ivs_result_topic);
                $ivs_result_offline_topic = 'device/' . $device_sn . '/message/up/ivs_result_offline';//订阅离线识别结果
                $mqtt->subscribe($ivs_result_offline_topic);
                $keep_alive_topic = 'device/' . $device_sn . '/message/up/keep_alive'; //订阅心跳
                $mqtt->subscribe($keep_alive_topic);

                $gpio_in_topic = 'device/' . $device_sn . '/message/up/gpio_in'; //订阅IO输入事件
                $mqtt->subscribe($gpio_in_topic);
                $serial_data_topic = 'device/' . $device_sn . '/message/up/serial_data'; //订阅串口数据接收
                $mqtt->subscribe($serial_data_topic);
                $white_list_operator_reply_topic = 'device/' . $device_sn . '/message/down/white_list_operator/reply'; //白名单操作回执
                $mqtt->subscribe($white_list_operator_reply_topic);
                $parkApiControlService->updatePassageInfo(array('id'=>$vf['id']),array('opt_protocol_type'=>1));
                
            }
        }
    }
    return true;
}
function subscribeTopic($mqtt)
{
    global $db;
    $whereStr = ' village_id>0 AND park_sys_type in ("D3","A11") AND status="1" AND device_protocol="1"';
    $sqlStr = 'select id,village_id,passage_direction,device_number,garage_id,device_protocol,opt_protocol_type from pigcms_park_passage where ' . $whereStr;
    $face_list = $db->query($sqlStr);
    $parkApiControlService=new ParkApiControlService();
    if (!empty($face_list)) {
        foreach ($face_list as $vf) {
            $device_sn = $vf['device_number'];
            if(empty($device_sn)){
                continue;
            }
            $ivs_result_topic = 'device/' . $device_sn . '/message/up/ivs_result'; //订阅识别结果
            $mqtt->subscribe($ivs_result_topic);
            $ivs_result_offline_topic = 'device/' . $device_sn . '/message/up/ivs_result_offline';//订阅离线识别结果
            $mqtt->subscribe($ivs_result_offline_topic);
            $keep_alive_topic = 'device/' . $device_sn . '/message/up/keep_alive'; //订阅心跳
            $mqtt->subscribe($keep_alive_topic);

            $gpio_in_topic = 'device/' . $device_sn . '/message/up/gpio_in'; //订阅IO输入事件
            $mqtt->subscribe($gpio_in_topic);
            $serial_data_topic = 'device/' . $device_sn . '/message/up/serial_data'; //订阅串口数据接收
            $mqtt->subscribe($serial_data_topic);
            $white_list_operator_reply_topic = 'device/' . $device_sn . '/message/down/white_list_operator/reply'; //白名单操作回执
            $mqtt->subscribe($white_list_operator_reply_topic);
            if($vf['opt_protocol_type']!=1){
                $parkApiControlService->updatePassageInfo(array('id'=>$vf['id']),array('opt_protocol_type'=>1));
            }
            /*
             * 
            $snapshot_topic = 'device/'.$device_sn.'/message/up/snapshot'; //订阅抓图结果
            $gpio_in_topic = 'device/'.$device_sn.'/message/up/gpio_in'; //订阅IO输入事件
            $serial_data_topic = 'device/'.$device_sn.'/message/up/serial_data'; //订阅串口数据接收
            $serial_data_reply= 'device/'.$device_sn.'/message/down/serial_data/reply';
            $barr_gate_status_topic = 'device/'.$device_sn.'/message/up/barr_gate_status'; //订阅道闸状态
            */
        }
    }
}