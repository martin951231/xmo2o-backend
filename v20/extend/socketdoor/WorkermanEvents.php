<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */

//declare(ticks=1);
namespace  socketdoor;
use \GatewayWorker\Lib\Gateway;
use Workerman\Lib\Timer;

/**
 * 主逻辑
 * 主要是处理 onConnect onMessage onClose 三个方法
 * onConnect 和 onClose 如果不需要可以不用实现并删除
 */
class WorkermanEvents
{
    public static $db = null;
    public static $table_alias='';
    public static $mark='door_access_';
    public static $clientArr=array();
    public function __construct(){

    }

    //========================todo 数据库查询方法===========================

    //todo 查询单条数据
    protected static function dbRow($table,$where,$field='*'){
        $tableName=self::$table_alias.$table;
        return self::$db->select($field)->from($tableName)->where($where)->row();
    }

    //todo 查询多条数据
    protected static function dbSelect($table,$where,$field='*',$sort,$limit=20){
        $tableName=self::$table_alias.$table;
        return self::$db->select($field)->from($tableName)->where($where) ->orderByDESC(array($sort))->limit($limit)->query();
    }

    //todo 更新数据
    protected static function updateHouseFaceDevice($where,$data=array()){
        $tableName=self::$table_alias.'house_face_device';
        $ret=self::$db->update($tableName)->cols($data)->where($where)->query();
        self::fdump_socket(['sql'=>self::$db->lastSQL(),'where'=>$where,'data'=>$data],'socketdoor/updateHouseFaceDevice',1);
        return $ret;
    }
    protected static function dbUpdate($table,$where,$data){
        $tableName=self::$table_alias.$table;
        return self::$db->update($tableName)->cols($data)->where($where)->query();
    }
    //todo 写入数据
    protected static function dbInsert($table,$data){
        $tableName=self::$table_alias.$table;
        return self::$db->insert($tableName)->cols($data)->query();
    }

    //todo 执行sql
    protected static function dbQuery($str){
        self::$db->query($str);
    }
    //todo 日志写入
    public static function fdump_socket($data, $filename='test', $append=false){
        $root=dirname(dirname(dirname(__DIR__)));
        $fileName = rtrim($root,'/') . '/api/log/' . date('Ymd') . '/' . $filename . '_fdump.php';
        $dirName = dirname($fileName);
        if(!file_exists($dirName)){
            mkdir($dirName, 0777, true);
        }
        $debug_trace = debug_backtrace();
        $file = __FILE__ ;
        $line = "unknown";
        if (isset($debug_trace[0]) && isset($debug_trace[0]['file'])) {
            $file = $debug_trace[0]['file'] ;
            $line = $debug_trace[0]['line'];
        }
        $f_l = '['.$file.' : '.$line.']';

        if($append){
            if(!file_exists($fileName)){
                file_put_contents($fileName,'<?php');
            }
            file_put_contents($fileName,PHP_EOL.$f_l.PHP_EOL.date('Y-m-d H:i:s').' '.PHP_EOL.var_export($data,true).PHP_EOL,FILE_APPEND);
        }else{
            file_put_contents($fileName,'<?php'.PHP_EOL.date('Y-m-d H:i:s').' '.PHP_EOL.var_export($data,true));
        }
    }
    //当$worker进程启动时触发。每个进程生命周期内都只会触发一次。
    public static function onWorkerStart($worker)
    {
        $database_config = include( dirname(dirname(dirname(__DIR__))).'/conf/db.php');
        self::$db = new \Workerman\MySQL\Connection($database_config['DB_HOST'], $database_config['DB_PORT'], $database_config['DB_USER'], $database_config['DB_PWD'], $database_config['DB_NAME']);
        self::$table_alias=$database_config['DB_PREFIX'];
        Timer::add(3, function(){
            //每隔3秒执行命令
            self::execCommand();
        });
        
    }

    //当客户端连接上gateway完成websocket握手时触发的回调函数
    public static function onWebSocketConnect($client_id='', $data=array())
    {
        $_get=$data && isset($data['get']) ? $data['get']:array();
        self::$clientArr[$client_id]=array('client_id'=>$client_id,'param'=>$data,'_get'=>$_get);
        
        $dev_serial_no=$_get && isset($_get['SerialNo']) ? $_get['SerialNo']:'';
        $dev_serial_no=trim($dev_serial_no);
        if($dev_serial_no){
            $whereStr='device_type=31 AND device_sn="'.$dev_serial_no.'"';
            $nowtime=time();
            $saveArr=array('socket_client_id'=>$client_id,'notify_time'=>strval($nowtime),'login_time'=>strval($nowtime));
            if(isset($_SERVER['REMOTE_ADDR']) && $_SERVER['REMOTE_ADDR']){
                $saveArr['device_ip']=$_SERVER['REMOTE_ADDR'];
            }
            self::updateHouseFaceDevice($whereStr,$saveArr); 
        }
        Gateway::sendToClient($client_id, 'pong');
        self::fdump_socket(['websocket握手时--'.__LINE__,$client_id,$data,'SERVER'=>$_SERVER],'socketdoor/socketHead',1);
    }

    /**
     * 当客户端连接时触发
     * 当客户端连接上gateway进程时(TCP三次握手完毕时)触发的回调函数
     * 如果业务不需此回调可以删除onConnect
     * @param int $client_id 连接id
     */
    public static function onConnect($client_id='')
    {
        self::fdump_socket(['当客户端连接时触发--'.__LINE__,$_SERVER,$client_id,'get'=>$_GET],'socketdoor/onConnect',1);
        // 向当前client_id发送数据
        Gateway::sendToClient($client_id, 'pong');
    }

    /**
     * 当用户断开连接时触发
     * @date : 2022/3/14
     * @param $client_id 连接id
     * @throws \Exception
     */
    public static function onClose($client_id='')
    {
        $allClient=self::$clientArr;
        $param=array();
        if(isset($allClient[$client_id])){
            $param=$allClient[$client_id];
            unset($allClient[$client_id]);
        }
        $dev_serial_no='';
       if(!empty($param) && isset($param['_get']) && isset($param['_get']['SerialNo'])){
           $dev_serial_no=trim($param['_get']['SerialNo']);
       }
        $nowtime=time();
       if($dev_serial_no){
           $whereStr = 'device_type=31 AND device_sn="' . $dev_serial_no . '" AND socket_client_id="'.$client_id.'"';
           $saveArr = array('socket_client_id' => '');
           self::updateHouseFaceDevice($whereStr, $saveArr);
       }
       self::fdump_socket(['当用户断开连接时触发--'.__LINE__,$_SERVER,$client_id],'socketdoor/onClose',1);
    }

    /**
     * 当客户端发来消息时触发
     * @date : 2022/3/14
     * @param $client_id 连接id
     * @param $message   具体消息
     * @throws \Exception
     */
   public static function onMessage($client_id='', $message=''){
       $messageArr=json_decode($message,true);
       if(empty($messageArr)){
           $messageArr=array();
       }
       $nowtime=time();
       $allClient=self::$clientArr;
       $param= isset($allClient[$client_id]) ? $allClient[$client_id]:array();
       $dev_serial_no=$param && isset($param['_get']) && isset($param['_get']['SerialNo']) ? $param['_get']['SerialNo']:'';
       $dev_serial_no=trim($dev_serial_no);
       $dateH=date('H');
       self::fdump_socket(['当客户端发来消息时触发--'.__LINE__,$client_id,$message,'param'=>$param,'dev_serial_no'=>$dev_serial_no],'socketdoor/onMessage_'.$dateH,1);
       $device_cmd = [];
       if (!empty($messageArr) && isset($messageArr['id']) && $messageArr['id']) {
           self::fdump_socket([__LINE__, $client_id, $message], 'socketdoor/onMessage_'.$dateH, 1);
           $device_cmd = self::dbRow('house_face_device_cmd', 'id=' . $messageArr['id'], '*');
           $updateArr = array('reply_time' => time(), 'reply_ret' => json_encode($messageArr, JSON_UNESCAPED_UNICODE));
           if (isset($messageArr['result']) && !$messageArr['result']) {
               $updateArr['exec_status'] = 2;
           }
           self::$db->update(self::$table_alias . 'house_face_device_cmd')->cols($updateArr)->where('id=' . $messageArr['id'])->query();
       }
       if ($message && empty($messageArr) && strpos($message, 'ping') !== false && strpos($message, $dev_serial_no) !== false) {
           self::fdump_socket(['当客户端发来消息时触发--' . __LINE__, $client_id, $message, 'param' => $param, 'dev_serial_no' => $dev_serial_no], 'socketdoor/xpong_'.$dateH, 1);
           //心跳
           $whereStr = 'device_type=31 AND device_sn="' . $dev_serial_no . '"';
           $saveArr = array('socket_client_id' => $client_id, 'notify_time' => $nowtime, 'login_time' => $nowtime);
           self::updateHouseFaceDevice($whereStr, $saveArr);
           Gateway::sendToClient($client_id, 'pong');
       } else if (!empty($messageArr) && isset($messageArr['method']) && $messageArr['method'] == 'cardManager.removeCard') {
            //删除IC卡
           
       } else if (!empty($messageArr) && isset($messageArr['method']) && $messageArr['method'] == 'personnelData.savePersons') {
           if ($device_cmd['relate_id'] > 0 && $device_cmd['relate_type'] == 'face_user_bind_device') {
               $params = $messageArr['params']['0'];
               $status = 0;
               $fail_msg = '';
               $face_status = 1;
               if ($params['Result'] && (!isset($params['ErrorMessage']) || empty($params['ErrorMessage']))) {
                   //成功了
                   $face_status = 2;
                   $status = 1;
               } else if (!$params['Result'] || (isset($params['ErrorMessage']) && !empty($params['ErrorMessage']))) {
                   //失败了
                   $status = 2;
                   $face_status = 3;
                   $fail_msg = isset($params['ErrorMessage']) && $params['ErrorMessage'] ? $params['ErrorMessage'] : '同步失败了！';
               }
               //face_user_bind_device表
               $saveTmpData = array('info' => json_encode($messageArr, JSON_UNESCAPED_UNICODE), 'last_time' => $nowtime, 'face_time' => $nowtime, 'face_status' => $face_status);
               $saveTmpData['face_reason'] = $fail_msg;
               self::$db->update(self::$table_alias . 'face_user_bind_device')->cols($saveTmpData)->where('id=' . $device_cmd['relate_id'])->query();
               //house_face_sync_log表
               $saveSyncLog = array('last_time' => $nowtime, 'status' => $status, 'msg' => $fail_msg);
               self::$db->update(self::$table_alias . 'house_face_sync_log')->cols($saveSyncLog)->where('device_cmd_id=' . $device_cmd['id'])->query();
               if ($device_cmd['bind_id'] > 0) {
                   //house_village_user_bind 表
                   $saveUserBind = array('add_face_time' => $nowtime, 'face_img_status' => $status, 'face_img_reason' => $fail_msg);
                   self::$db->update(self::$table_alias . 'house_village_user_bind')->cols($saveUserBind)->where('pigcms_id=' . $device_cmd['bind_id'])->query();
               }
           }
           $cmd_data = isset($device_cmd['cmd_data']) && $device_cmd['cmd_data'] ? json_decode($device_cmd['cmd_data'], true) : [];
           $messageParams = isset($messageArr['params']['0']) && $messageArr['params']['0'] ? $messageArr['params']['0'] : [];
           $faceSuccess = 0;
           if ($messageParams['Result'] && (!isset($messageParams['ErrorMessage']) || empty($messageParams['ErrorMessage']))) {
               $faceSuccess = 1;
           }
           self::fdump_socket([__LINE__, $client_id, ['cmd_data' => $cmd_data]], 'socketdoor/onMessage_'.$dateH, 1);
            if (isset($cmd_data['params']) && $faceSuccess == 1 && isset($cmd_data['params'][0]) && isset($cmd_data['params'][0]['Person'])) {
                $village_id = isset($device_cmd['village_id']) && $device_cmd['village_id'] ? $device_cmd['village_id'] : 0;
                $device_id = isset($device_cmd['device_id']) && $device_cmd['device_id'] ? $device_cmd['device_id'] : 0;
                $device_sn = isset($device_cmd['device_sn']) && $device_cmd['device_sn'] ? $device_cmd['device_sn'] : '';
                $bind_id = isset($device_cmd['bind_id']) && $device_cmd['bind_id'] ? $device_cmd['bind_id'] : 0;
                $person = isset($cmd_data['params'][0]['Person']) && $cmd_data['params'][0]['Person'] ? $cmd_data['params'][0]['Person'] : [];
                self::fdump_socket([__LINE__, $client_id, ['person' => $person]], 'socketdoor/onMessage_'.$dateH, 1);
                $cards = isset($person['Cards']) && $person['Cards'] ? $person['Cards'] : [];
                foreach ($cards as $card) {
                    $type = isset($card['Type']) && $card['Type'] ? $card['Type'] : 0;
                    $qr_content_txt = isset($card['ID']) && $card['ID'] ? $card['ID'] : '';
                    $open_times = isset($card['LeftTimes']) && $card['LeftTimes'] ? $card['LeftTimes'] : 0;
                    $validity = isset($card['Validity']) && $card['Validity'] ? $card['Validity'] : [];
                    if ($type == 6) {
                        // 已经同步过了二维码卡号
                        $where = "device_equipment_type='face' AND bind_type='village_user' AND bind_from='house_village_user_bind' AND bind_id={$bind_id}";
                        $where .= " AND device_id > 0 AND delete_time = 0 AND device_type = 31";
                        $device_qrcode = self::dbRow('house_device_qrcode', $where, '*');
                        $opened_times = isset($device_qrcode['opened_times']) && $device_qrcode['opened_times'] ? $device_qrcode['opened_times'] : 0;
                        $deviceQrcodeId = isset($device_qrcode['id']) && $device_qrcode['id'] ? $device_qrcode['id'] : 0;
                        self::fdump_socket([__LINE__, $client_id, ['deviceQrcodeId' => $deviceQrcodeId]], 'socketdoor/onMessage_'.$dateH, 1);
                        $whereIcCard = "village_id={$village_id} AND bind_id={$bind_id} AND del_time=0 AND type=6 AND end_time>".$nowtime;
                        $bindIcCardInfo = self::dbRow('house_village_user_bind_iccard', $whereIcCard, '*');
                        self::fdump_socket([__LINE__, $client_id, ['bindIcCardInfo' => $bindIcCardInfo]], 'socketdoor/onMessage_'.$dateH, 1);
                        !$open_times && $open_times = isset($bindIcCardInfo['open_times']) && $bindIcCardInfo['open_times'] ? $bindIcCardInfo['open_times'] : 0;
                        !$qr_content_txt && $qr_content_txt = isset($bindIcCardInfo['ic_card_no']) && $bindIcCardInfo['ic_card_no'] ? $bindIcCardInfo['ic_card_no'] : '';
                        $effective_start_time = isset($validity[0]) && $validity[0] ? strtotime($validity[0]) : 0;
                        $effective_end_time = isset($validity[1]) && $validity[1] ? strtotime($validity[1] . ' 23:59:59') : 0;
                        if ($effective_start_time <= 0 || $effective_end_time <= 0) {
                            $effective_start_time = isset($bindIcCardInfo['start_time']) && $bindIcCardInfo['start_time'] ? $bindIcCardInfo['start_time'] : 0;
                            $effective_end_time = isset($bindIcCardInfo['end_time']) && $bindIcCardInfo['end_time'] ? $bindIcCardInfo['end_time'] : 0;
                        }
                        $url = isset($bindIcCardInfo['url']) && $bindIcCardInfo['url'] ? $bindIcCardInfo['url'] : '';
                        $dataQrcode = [
                            'device_equipment_type' => 'face', 'village_id' => $village_id, 'device_id' => $device_id, 
                            'device_sn' => $device_sn, 'bind_type' => 'village_user', 'bind_from' => 'house_village_user_bind', 
                            'bind_id' => $bind_id, 'qr_content_txt' => $qr_content_txt, 'qr_syn_time' => $nowtime,  'qr_syn_status' => 1, 
                            'open_times' => $open_times, 'opened_times' => $opened_times, 'device_type' => 31, 'add_time' => $nowtime,
                            'effective_start_time' => $effective_start_time, 'effective_end_time' => $effective_end_time,
                            'qr_content_url' => $url,
                        ];
                        self::fdump_socket([__LINE__, $client_id, ['dataQrcode' => $dataQrcode, 'deviceQrcodeId' => $deviceQrcodeId]], 'socketdoor/onMessage_'.$dateH, 1);
                        if ($deviceQrcodeId) {
                            self::$db->update(self::$table_alias . 'house_device_qrcode')->cols($dataQrcode)->where('id=' . $deviceQrcodeId)->query();
                        } else {
                            self::$db->insert(self::$table_alias . 'house_device_qrcode')->cols($dataQrcode)->query();
                        }
                    }
                }
            }
       }
       elseif(!empty($messageArr) && isset($messageArr['method']) && $messageArr['method'] == 'personnelData.removePersons'){
            //人员删除
           $params=$messageArr['params']['0'];
           $status=0;
           $fail_msg='';
           if($params['Result'] && (!isset($params['ErrorMessage']) || empty($params['ErrorMessage']))){
               //成功了
               $status=1;
           }else if(!$params['Result'] ||(isset($params['ErrorMessage']) && !empty($params['ErrorMessage'])) ){
               //失败了
               $status=2;
               $fail_msg=isset($params['ErrorMessage']) && $params['ErrorMessage'] ? $params['ErrorMessage']:'删除失败了！';
           }
           //house_face_sync_log表
           $saveSyncLog=array('last_time'=>$nowtime,'status'=>$status,'msg'=>$fail_msg);
           self::$db->update(self::$table_alias . 'house_face_sync_log')->cols($saveSyncLog)->where('device_cmd_id=' . $device_cmd['id'])->query();
       } 
       else if (!empty($messageArr) && isset($messageArr['method']) && $messageArr['method'] == 'devDoor.openOnce') {
           // 远程开门 处理
           if (isset($messageArr['id']) && $messageArr['id']) {
               if (isset($messageArr['result']) && !$messageArr['result']) {
                   $where = "eventId='openOnce_". $messageArr['id'] ."'";
                   $tableName=self::$table_alias.'house_user_log';
                   $data = ['log_status' => 1];
                   self::$db->update($tableName)->cols($data)->where($where)->query();
               }
           }
       }
   }


    //========================todo 业务逻辑方法===========================


    //todo  读取执行命令
    public static function execCommand(){
        $clientArr=GateWay::getAllClientIdList();
        if(empty($clientArr)){
            return true;
        }
        $dateH=date('H');
        self::fdump_socket(['clientArr'=>$clientArr],'socketdoor/execCommand_'.$dateH,1);
        foreach ($clientArr as $client_id){
            $nowtime=time();
            $add_time_tmp=$nowtime-10*24*3600; //命令超过10天时间的 不执行
            $notify_time_time=$nowtime-600; //心跳10分钟不在线的 不执行他的命令
            $whereStr='dc.device_type=31 AND dc.village_id>0 AND dc.is_del<4 AND dc.socket_client_id = "'.$client_id.'" AND dcmd.exec_status <1 AND dcmd.add_time>'.$add_time_tmp.' AND dc.notify_time>='.$notify_time_time;
            $cmdData= self::$db->select('dcmd.*')
            ->from(self::$table_alias.'house_face_device dc')
            ->leftJoin(self::$table_alias.'house_face_device_cmd dcmd','dcmd.device_id = dc.device_id')
            ->limit(1)
            ->where($whereStr)
            ->orderByASC(array('dcmd.add_time'))
            ->query();
            
            if(!empty($cmdData)){
                self::fdump_socket(['cmdData'=>$cmdData],'socketdoor/execCommand_'.$dateH,1);
                foreach($cmdData as $cmdV){
                    $cmd_data=json_decode($cmdV['cmd_data'],true);
                    if($cmd_data && !isset($cmd_data['id'])){
                        $cmd_data['id']=intval($cmdV['id']);
                    }
                    $cmd_data_josn=json_encode($cmd_data,JSON_UNESCAPED_UNICODE);
                    Gateway::sendToClient($client_id, $cmd_data_josn);
                    self::fdump_socket(['client_id'=>$client_id,'cmd_data'=>$cmd_data_josn],'socketdoor/execCommand_'.$dateH,1);
                    $updateArr=array('exec_status'=>1,'exec_time'=>$nowtime);
                    $ret=self::$db->update(self::$table_alias.'house_face_device_cmd')->cols($updateArr)->where('id='.$cmdV['id'])->query();
                }
            }
        }
        return true;
    }
}
