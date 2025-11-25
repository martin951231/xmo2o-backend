#!/usr/bin/env php
<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2022/1/6 13:35
 */
namespace think;

use Workerman\Mqtt\Client;
use Workerman\MySQL\Connection;
use Workerman\Worker;

require realpath( __DIR__ . '/../../vendor/autoload.php');
$database_config = include realpath(  __DIR__ . '/../../../conf/db.php');


$worker = new Worker();
$db = new Connection($database_config['DB_HOST'], $database_config['DB_PORT'], $database_config['DB_USER'], $database_config['DB_PWD'], $database_config['DB_NAME']);
$config_info1 = $db->query('select value,name,type from pigcms_config where name="denake_mqtt_ip" ');
$config_info2 = $db->query('select value,name,type from pigcms_config where name="denake_mqtt_username" ');
$config_info3 = $db->query('select value,name,type from pigcms_config where name="denake_mqtt_password" ');
if (empty($config_info1)||empty($config_info1[0]['value'])){
    return ;
}
$config=[];
$config['ip']=$config_info1[0]['value'];
$config['username']=$config_info2[0]['value'];
$config['password']=$config_info3[0]['value'];

$worker->onWorkerStart = function()use ($config){
    $message = 1;
    $address = $config['ip'];
   //  $address = 'mqtt://60.173.195.178:2021';
    $optioins = [
        'username'=>$config['username'],
        'password'=>$config['password'],
        'debug'=>true
    ];
	$mqtt = new Client($address,$optioins);
    $mqtt->onConnect = function($mqtt) {
        $mqtt->subscribe('dnake/cms/deviceTopics');
        subscribe($mqtt);
        /*$topicuri = 'dnake/cms/AB900DXbeec6a87ad209/respond';
        $mqtt->subscribe($topicuri);*/
    };
    $mqtt->onMessage = function($topic, $content,$mqtt) use ($message){
        onMessage($topic, $content,$mqtt,$message);
    };
    $mqtt->connect();
};
Worker::runAll();


function onMessage($topic, $content,$mqtt,$message) {
    echo $message;
    echo "onMessage【当客户端收到Publish报文时触发】-->>";
    var_dump($topic, $content);
    $msg = \json_decode($content,true);
    $uid=$msg['from'];
    $data=$content;
    $time = time();
    global $db;
    $order_info = $db->query('select device_id,device_name,device_type,device_sn,village_id from pigcms_house_face_device where device_sn = '."'$uid'");
    if (!empty($order_info)){
        $order_info=$order_info[0];
    }
    switch ($msg['action']) {
        case 'heartSync':
            heartSync($msg);
            break;
        case 'syncFace':
           if (!empty($order_info)){
               $db->query('insert into pigcms_face_d6_request_record (village_id,device_sn,device_id,operator_type,operator_id,cmd_name,type,command,add_time,update_time) VALUES ('.$order_info['village_id'].','."'$uid'".','.$order_info['device_id'].',1,'.$order_info['village_id'].',"syncFace",2,'."'$data'".','.$time.','.$time.')');
            }
            break;
        case 'delFace':
            if (!empty($order_info)){
                $db->query('insert into pigcms_face_d6_request_record (village_id,device_sn,device_id,operator_type,operator_id,cmd_name,type,command,add_time,update_time) VALUES ('.$order_info['village_id'].','."'$uid'".','.$order_info['device_id'].',1,'.$order_info['village_id'].',"delFace",2,'."'$data'".','.$time.','.$time.')');
            }
            break;
        case 'deviceSet':
            if (!empty($order_info)){
                $db->query('insert into pigcms_face_d6_request_record (village_id,device_sn,device_id,operator_type,operator_id,cmd_name,type,command,add_time,update_time) VALUES ('.$order_info['village_id'].','."'$uid'".','.$order_info['device_id'].',1,'.$order_info['village_id'].',"deviceSet",2,'."'$data'".','.$time.','.$time.')');
            }
            break;
        case 'passEvent':
            if (!empty($order_info)){
                $db->query('insert into pigcms_face_d6_request_record (village_id,device_sn,device_id,operator_type,operator_id,cmd_name,type,command,add_time,update_time) VALUES ('.$order_info['village_id'].','."'$uid'".','.$order_info['device_id'].',1,'.$order_info['village_id'].',"passEvent",2,'."'$data'".','.$time.','.$time.')');
            }
            break;
        case 'cardDel':
            if (!empty($order_info)){
                $db->query('insert into pigcms_face_d6_request_record (village_id,device_sn,device_id,operator_type,operator_id,cmd_name,type,command,add_time,update_time) VALUES ('.$order_info['village_id'].','."'$uid'".','.$order_info['device_id'].',1,'.$order_info['village_id'].',"cardDel",2,'."'$data'".','.$time.','.$time.')');
            }
            break;
        case 'syncQrManager':
            if (!empty($order_info)){
                $db->query('insert into pigcms_face_d6_request_record (village_id,device_sn,device_id,operator_type,operator_id,cmd_name,type,command,add_time,update_time) VALUES ('.$order_info['village_id'].','."'$uid'".','.$order_info['device_id'].',1,'.$order_info['village_id'].',"syncQrManager",2,'."'$data'".','.$time.','.$time.')');
            }
            break;
        case 'syncCard':
            if (!empty($order_info)){
                $db->query('insert into pigcms_face_d6_request_record (village_id,device_sn,device_id,operator_type,operator_id,cmd_name,type,command,add_time,update_time) VALUES ('.$order_info['village_id'].','."'$uid'".','.$order_info['device_id'].',1,'.$order_info['village_id'].',"syncCard",2,'."'$data'".','.$time.','.$time.')');
            }
            break;
        default:
            break;
    }
    $cmd_list = $db->query('select * from pigcms_face_d6_request_record where is_implement=0 and  type=1 and  device_sn = '."'$uid'");
    if(!empty($cmd_list)){
        foreach ($cmd_list as $value){
            if (!empty($value['cmd_name'])&&$value['cmd_name']=='reboot'){
                reboot($msg,$mqtt);
            }elseif (!empty($value['cmd_name'])&&$value['cmd_name']=='remoteOpen'){
                remoteOpen($msg,$mqtt);
            }elseif (!empty($value['cmd_name'])&&$value['cmd_name']=='deviceSet'){
                if (!empty($value['command'])){
                    $data=\json_decode($value['command']);
                    deviceSet($msg,$mqtt,$data);
                }
            }elseif (!empty($value['cmd_name'])&&$value['cmd_name']=='syncFace'){
                if (!empty($value['command'])){
                    $data=\json_decode($value['command']);
                    syncFace($msg,$mqtt,$data);
                }
            }elseif (!empty($value['cmd_name'])&&$value['cmd_name']=='syncQrManager'){
                if (!empty($value['command'])){
                    $data=\json_decode($value['command']);
                    syncQrManager($msg,$mqtt,$data);
                }
            }elseif (!empty($value['cmd_name'])&&$value['cmd_name']=='faceClear'){
                if (!empty($value['command'])){
                    faceClear($msg,$mqtt);
                }
            }elseif (!empty($value['cmd_name'])&&$value['cmd_name']=='syncCard'){
                if (!empty($value['command'])){
                    $data=\json_decode($value['command']);
                    syncCard($msg,$mqtt,$data);
                }
            }elseif (!empty($value['cmd_name'])&&$value['cmd_name']=='cardDel'){
                if (!empty($value['command'])){
                    $data=\json_decode($value['command']);
                    cardDel($msg,$mqtt,$data);
                }
            }
        }
    }

    // syncCard($msg,$mqtt,$message);
}

function subscribe($mqtt){
    global $db;
    $face_list = $db->query('select device_id,device_name,device_type,device_sn,village_id from pigcms_house_face_device where device_type =26');
    if (!empty($face_list)){
        foreach ($face_list as $vf){
            $uid=$vf['device_sn'];
            $topicuri = 'dnake/cms/'.$uid.'/respond';
            $mqtt->subscribe($topicuri);
        }
    }
}

function heartSync($msg) {
    global $db;
    echo "心跳�包，来自设备UID={$msg['from']} , IP：{$msg['params']['ip']}".PHP_EOL;
    $uid=$msg['from'];
    $data=\json_encode($msg);
    $time = time();
    $ip=$msg['params']['ip'];
    $order_info = $db->query('select device_id,device_name,device_type,device_sn,village_id from pigcms_house_face_device where device_sn = '."'$uid'");
    if (!empty($order_info)){
        $order_info=$order_info[0];
        var_dump($order_info['village_id']);
    }else{
        $order_info['village_id']=0;
        $order_info['device_id']=0;
    }
    $order_info1 = $db->query('select device_sn,id,command,add_time,update_time,device_ip from pigcms_face_d6_request_record where device_sn = '."'$uid'");
    if (empty($order_info1)){
        $db->query('insert into pigcms_face_d6_request_record (village_id,device_sn,device_id,operator_type,operator_id,cmd_name,type,command,add_time,update_time,device_ip) VALUES ('.$order_info['village_id'].','."'$uid'".','.$order_info['device_id'].',1,'.$order_info['village_id'].',"heartSync",2,'."'$data'".','.$time.','.$time.','."'$ip'".')');
    }else{
        $db->query('UPDATE pigcms_face_d6_request_record SET update_time ='."'$time'".',is_implement=0  where device_sn = '."'$uid'");
    }
    var_dump('insert into pigcms_face_d6_request_record (village_id,device_sn,device_id,operator_type,operator_id,cmd_name,type,command,add_time,update_time,device_ip) VALUES ('.$order_info['village_id'].','."'$uid'".','.$order_info['device_id'].',1,'.$order_info['village_id'].',"heartSync",2,'."'$data'".','.$time.','.$time.','."'$ip'".')');
}

//注意防止重复调用
function reboot($msg,$mqtt) {
    while (true) {
        //$status = file_get_contents('reboot.txt');
            $uid = $msg['from'];
            echo "重启设备「{$uid}」..".PHP_EOL;
            $topicuri = 'dnake/cms/'.$uid.'/reboot';
            $mqtt->publish($topicuri, '');
    }
}

//远程开门
function remoteOpen($msg,$mqtt) {

        $uid = $msg['from'];
        echo "远程开门「{$uid}」..".PHP_EOL;
        $topicuri = 'dnake/cms/'.$uid.'/remoteOpen';
        $mqtt->publish($topicuri, '');

        // file_put_contents('remoteOpen.txt',0);
}

//设备楼栋单元号设置
function deviceSet($msg,$mqtt,$data) {
        $uid = $msg['from'];
        echo "设备楼栋单元号设置「{$uid}」..".PHP_EOL;
        $topicuri = 'dnake/cms/'.$uid.'/deviceSet';
        $options = [
            'build' => intval($data['params']['build']),
            'unit' => intval($data['params']['unit']),
            'number' =>intval($data['params']['number']),
           /* 'deviceType' => '0101',
            'dhcp' => 0*/
        ];
        $options=\json_encode($options);
        $mqtt->publish($topicuri, $options);
}




//人脸注册
function syncFace($msg,$mqtt,$data) {
        $uid = $msg['from'];
        echo "人脸注册「{$uid}」..".PHP_EOL;
        $topicuri = 'dnake/cms/'.$uid.'/syncFace';
        $options1[] = [
            'id' => $data['params']['id'],
            'faceUrl' => $data['params']['faceUrl'],
        ];
        $aa=[
            'msgId'=> "1234",
            'from'=> $uid,
            'action'=> 'syncFace',
            "params"=>$options1,
        ];
        $options=\json_encode($aa);
        $mqtt->publish($topicuri, $options);

    //TODO更新数据库

}

//下发物业标识配置权限
function syncQrManager($msg,$mqtt,$data) {
        $uid = $msg['from'];
        echo "下发物业标识配置权限「{$uid}」..".PHP_EOL;
        $topicuri = 'dnake/cms/'.$uid.'/syncQrManager';
        $options1[] = [
            "type"=> $data['params']['type'],
            "managerId"=>$data['params']['managerId'],
        ];
        $aa=[
            'action'=> 'syncQrManager',
            "params"=>$options1,
        ];
        $options=\json_encode($aa);
        $mqtt->publish($topicuri, $options);

    //TODO更新数据库

}

//人脸清空
function faceClear($msg,$mqtt) {
    $uid = $msg['from'];
    echo "人脸清空「{$uid}」..".PHP_EOL;
    $topicuri = 'dnake/cms/'.$uid.'/faceClear';
    $mqtt->publish($topicuri,'');
    //TODO更新数据库

}

//传统卡权限配置
function syncCard($msg,$mqtt,$data) {
        $uid = $msg['from'];
        echo "传统卡权限配置「{$uid}」..".PHP_EOL;
        $topicuri = 'dnake/cms/'.$uid.'/syncCard';
        $options1[] = [
            "card"=> $data['params']['card'],
            "build"=>intval($data['params']['build']),
            "unit"=>intval($data['params']['unit']),
            "floor"=>intval($data['params']['floor']),
            "family"=> intval($data['params']['family']),
            "tag"=> "组",
            "startTime"=>intval($data['params']['startTime']),
            "endTime"=>intval($data['params']['endTime']),
        ];
        $aa=[
            'action'=> 'syncCard',
            "from"=>$uid,
            "params"=>$options1,
        ];
        $options=\json_encode($aa);
        $mqtt->publish($topicuri, $options);

    //TODO更新数据库

}


//传统卡权限配置
function cardDel($msg,$mqtt,$data) {
        $uid = $msg['from'];
        echo "传统卡权限配置「{$uid}」..".PHP_EOL;
        $topicuri = 'dnake/cms/'.$uid.'/syncCard';
        $options1[] = [
            "card"=> $data['params']['card'],
        ];
        $aa=[
            'action'=> 'syncCard',
            "from"=>$uid,
            "params"=>$options1,
        ];
        $options=\json_encode($aa);
        $mqtt->publish($topicuri, $options);

    //TODO更新数据库

}
