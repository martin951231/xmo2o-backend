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
namespace  socket;
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
    public static $mark='d6park_';
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

    //todo 查询车牌用户数据
    protected static function dbGetUser($village_id,$car_number){
        $car=self::getCarNumber($car_number);
        return self::$db->select('c.car_id,c.car_number,u.uid,u.pigcms_id,u.name,u.phone')
            ->from(self::$table_alias.'house_village_parking_car c')
            ->leftJoin(self::$table_alias.'house_village_bind_car b','b.car_id = c.car_id AND b.village_id = c.village_id')
            ->leftJoin(self::$table_alias.'house_village_user_bind u','u.pigcms_id = b.user_id ')
            ->where('c.village_id = '.$village_id.' and c.province = "'.$car['province'].'" and c.car_number = "'.$car['car_number'].'"')->row();
    }

    //todo 获取执行命令记录
    protected static function dbGetRecord($client_id){
        return self::$db->select('r.id,r.data,r.sort,r.status')
            ->from(self::$table_alias.'house_village_park_config c')
            ->leftJoin(self::$table_alias.'park_d6_request_record r','c.village_id = r.village_id')
            ->where('r.status = 0 and c.d6_client_id = "'.$client_id.'"')
            ->orderByDESC(array('r.sort'))
            ->query();
    }


    //========================todo end ===========================

    public static function getCarNumber($car_number){
        if(preg_match('/[\x7f-\xff]/', $car_number)){
            return [
                'province'=>mb_substr($car_number,0,1),
                'car_number'=>mb_substr($car_number,1)
            ];
        }
        else{
            return [
                'province'=>'',
                'car_number'=>$car_number
            ];
        }
    }

    //todo 日志写入
    public static function fdump_socket($data, $filename='test', $append=false,$is_write=1){
        if($is_write && $_SERVER['REMOTE_ADDR'] == '60.173.195.178'){
            return true;
        }
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

    //todo 命令执行后更改状态
    public static function recordState($requestid,$data){
        if(empty($requestid)){
            return true;
        }
        $record=self::dbRow('park_d6_request_record','id = '.$requestid.' and state = 0','id,comid,cmd_name,temp_id');
        if(empty($record)){
            return false;
        }
        $state=2;$remark='';
        if(isset($data['code']) && $data['code'] == 0){
            $state=1;
        }
        if(isset($data['msg'])){
            $remark=$data['msg'];
        }
        if($state == 2 && $record['cmd_name'] == 'charging_query' && $record['temp_id'] && $remark){
            self::dbUpdate('house_village_parking_temp','id = '.$record['temp_id'].' and service_name = "d6_charging"',[
                'state'=>1,
                'errmsg'=>$remark,
            ]);
        }
        self::dbUpdate('park_d6_request_record','id = '.$requestid,[
            'state'=>$state,
            'remark'=>$remark,
            'return_info'=>serialize($data),
            'sucess_time'=>time()
        ]);
        return true;

    }

    //当$worker进程启动时触发。每个进程生命周期内都只会触发一次。
    public static function onWorkerStart($worker)
    {
        $database_config = include( dirname(dirname(dirname(__DIR__))).'/conf/db.php');
        self::$db = new \Workerman\MySQL\Connection($database_config['DB_HOST'], $database_config['DB_PORT'], $database_config['DB_USER'], $database_config['DB_PWD'], $database_config['DB_NAME']);
        self::$table_alias=$database_config['DB_PREFIX'];
        Timer::add(3, function(){
            self::readRecord();
        });
    }

    //当客户端连接上gateway完成websocket握手时触发的回调函数
    public static function onWebSocketConnect($client_id, $data)
    {
        if(isset($data['server']['HTTP_STATION_ID']) && !empty($data['server']['HTTP_STATION_ID'])){
            Gateway::bindUid($client_id, json_encode([
                'station_id'=>$data['server']['HTTP_STATION_ID'],
                'timestamp'=>$data['server']['HTTP_TIMESTAMP'],
                'sign'=>$data['server']['HTTP_SIGN']
            ],JSON_UNESCAPED_UNICODE));
        }
        self::fdump_socket(['websocket握手时--'.__LINE__,$client_id,$data],'d6_park/socket/socketHead',1,0);
    }

    /**
     * 当客户端连接时触发
     * 如果业务不需此回调可以删除onConnect
     * @param int $client_id 连接id
     */
    public static function onConnect($client_id)
    {
        self::fdump_socket(['当客户端连接时触发--'.__LINE__,$_SERVER,$client_id],'d6_park/socket/onConnect',1);
        $output =[
            'code' => 0,
            'msg'  => '握手成功',
            'data' => [
                'client_id'=>$client_id,
            ]
        ];
        // 向当前client_id发送数据
        Gateway::sendToClient($client_id, json_encode($output,JSON_UNESCAPED_UNICODE));
    }

    /**
     * 当用户断开连接时触发
     * @author: liukezhu
     * @date : 2022/3/14
     * @param $client_id 连接id
     * @throws \Exception
     */
    public static function onClose($client_id)
    {
        $result=self::$db->update('pigcms_house_village_park_config')->cols(['d6_client_id'=>''])->where('d6_client_id = "'.$client_id.'"')->query();
        self::fdump_socket(['当用户断开连接时触发--'.__LINE__,$_SERVER,$client_id,$result],'d6_park/socket/onClose',1,0);
    }

    /**
     * 当客户端发来消息时触发
     * @author: liukezhu
     * @date : 2022/3/14
     * @param $client_id 连接id
     * @param $message   具体消息
     * @throws \Exception
     */
   public static function onMessage($client_id, $message){
       if(!is_null(json_decode($message))){
           $message=json_decode($message,true);
       }
       self::fdump_socket(['当客户端发来消息时触发--'.__LINE__,$client_id,$message],'d6_park/socket/onMessage',1);
       if(is_array($message)){
           $command=isset($message['command']) ? intval($message['command']) : 0;
           /*if(isset($message['type']) && $message['type'] == 1){
               $ss =array (
                   'command' => 5002,
                   'requestid' => 'a70ae0727b23435f9872b35c325f3367',
                   'version' => '1.0',
                   'timestamp' => 1658472488610,
                   'data' =>
                       array (
                           'seq' => '2022072214480861062357551',
                           'plate' => '苏LRS778',
                           'vehicletype' => 1,
                           'freeberth' => 996,
                           'parktype' => 2,
                           'lanetype' => 2,
                           'datatime' => 1658472488000,
                       ),
               );
               $config22=self::dbRow('house_village_park_config','d6_comid = "05zc671i"','id,village_id,d6_comid,d6_total,d6_free,d6_client_id,park_sys_type');
               $result33= self::carInto($config22,$ss['data']);

//               if(isset($ss['data']) && !empty($ss['data'])){ //车辆进场数据列表
//                   foreach ($ss['data']['arrive'] as $v){
//                       self::carInto($config22,$v);
//                       self::fdump_socket(['rrr--'.__LINE__,$v],'d6_park/socket/aaa',1,0);
//                   }
//               }
               self::fdump_socket(['rrr--'.__LINE__,$result33],'d6_park/socket/aaa',1,0);
           }*/
           $config=self::checkConfig($client_id);
           $is_send=true;
           if($config['code'] > 0){
               $result =[
                   'code' => $config['code'],
                   'msg'  => $config['msg'],
                   'data' => []
               ];
           }
           else{
               $requestid=0;
               if(isset($message['requestid']) && strpos($message['requestid'], self::$mark) !== false){
                   $requestid=(int)(explode('_',$message['requestid'])[1]);
               }
               $data=isset($message['data']) ? $message['data'] : [];
               $result =[
                   'code' => 0,
                   'msg'  => '请求成功',
                   'data' => []
               ];
               switch ($command){
                   case 5001:// 上传车场
                       $total=isset($data['totalcount']) ? $data['totalcount'] :  0;
                       $free=isset($data['freeberth']) ? $data['freeberth'] :  0;
                       $result= self::uploadPark($client_id,$config['data'],$total,$free);
                       break;
                   case 5002:// 车辆进场
                       $result= self::carInto($config['data'],$data);
                       break;
                   case 5003:// 车辆出场
                       $result= self::carOut($config['data'],$data);
                       break;
                   case 5004:// 数据补传
                       if(isset($data['arrive']) && !empty($data['arrive'])){ //车辆进场数据列表
                           foreach ($data['arrive'] as $v){
                               self::carInto($config['data'],$v);
                           }
                       }
                       if(isset($data['leave']) && !empty($data['leave'])){ //车辆离场数据列表
                           foreach ($data['leave'] as $v){
                               self::carOut($config['data'],$v);
                           }
                       }
                       break;
                   case 5008:// 计费信息查询
                       $result= self::uploadQueryPrice($config['data'],$data);
                       break;
               }
               $is_send=self::recordState($requestid,$message);
           }
           if($command){
               $message['data']=null;
               $message['code']=$result['code'];
               $message['msg']=$result['msg'];
               $result=$message;
           }
           if($is_send){
               $rr=Gateway::sendToClient($client_id, json_encode($result,JSON_UNESCAPED_UNICODE));
               self::fdump_socket(['返回客户端--'.__LINE__,$client_id,$message,$result,$rr],'d6_park/socket/send',1);
           }
       }
   }


    //========================todo 业务逻辑方法===========================

    //todo 校验合法参数
    public static function checkSign($data){
       if(empty($data)){
           return false;
       }
       $sign=$data['station_id'].$data['timestamp'].'HOMEN';
       if(strtoupper(md5($sign)) != $data['sign']){
           return false;
       }
       return $data['station_id'];
    }

    //todo 校验处理设备传参的合法性
    public static function checkConfig($client_id){
        $head=Gateway::getUidByClientId($client_id);
        if($head){
            $head=json_decode($head,true);
        }
        if(!isset($head['station_id'])){
            return ['code'=>4003,'msg'=>'缺少头部参数','data'=>[]];
        }
        $d6_comid=self::checkSign($head);
        if(!$d6_comid){
            return ['code'=>4003,'msg'=>'缺少必要参数或sign校验失败','data'=>[]];
        }
        $config=self::dbRow('house_village_park_config','d6_comid = "'.$d6_comid.'"','id,village_id,d6_comid,d6_total,d6_free,d6_client_id,park_sys_type');
        if(empty($config)){
            return ['code'=>4003,'msg'=>'无效停车场ID','data'=>[]];
        }
        if($config['park_sys_type'] != 'D6'){
            return ['code'=>4003,'msg'=>'请在小区后台=>停车设置=>停车设备类型选择【D6智慧停车】并保存','data'=>[]];
        }
        return ['code'=>0,'msg'=>'ok','data'=>$config];
    }

    //todo 上传车场
    public static function uploadPark($client_id,$config,$total,$free){
        $data=[];
        if($total != $config['d6_total']){
            $data['d6_total']=$total;
        }
        if($free != $config['d6_free']){
            $data['d6_free']=$free;
        }
        if($client_id != $config['d6_client_id']){
            $data['d6_client_id']=$client_id;
        }
        if(!empty($data)){
            self::dbUpdate('house_village_park_config','id = '.$config['id'],$data);
        }
        return ['code'=>0,'msg'=>'ok','data'=>$data];
    }

    //todo 拉取进出场车辆数据  $accessType 1进场  2出场
    private static function pullCarPark($accessType,$config,$param){
       $accessMode=($param['laneType'] == 1) ? 7 : 3;
       if($param['parkType'] == 1){
           $car_type='temporaryCar';
       }
       elseif ($param['parkType'] == 2){
           $car_type='monthCar';
       }
       else{
           $car_type='appointmenPark';
       }
       $pay_type='';
       $payMoney=0;
       $parkingTime=0;
       $is_out=0;
       if($accessType == 2){ //1进场  2出场
           $pay_type=$param['payType'];
           $payMoney=$param['payMoney'] * 0.01; //接口返回单位：分：写入金额元
           $parkingTime=$param['parkingTime'];
       }
       if(!empty($param['dataTime'])){
           $accessTime=intval($param['dataTime'] * 0.001);
       }else{
           $accessTime=0;
       }
       $data=[
           'order_id'=>$param['seq'],
           'third_id'=>$param['seq'],
           'business_type'=>0,
           'business_id'=>$config['village_id'],
           'car_number'=>$param['plate'],
           'accessType'=>$accessType,
           'accessTime'=>$accessTime,
           'accessImage'=>'',
           'accessBigImage'=>'',
           'channel_number'=>'',
           'channel_name'=>'',
           'accessMode'=>$accessMode,
           'park_sys_type'=>'D6',
           'park_id'=>$config['village_id'],
           'park_name'=>'',
           'empty_plot'=>(int)$param['freeBerth'],
           'uid'=>0,
           'car_id'=>0,
           'bind_id'=>0,
           'user_name'=>'',
           'user_phone'=>'',
           'totalMoney'=>$payMoney,
           'total'=>$payMoney,
           'pay_type'=>$pay_type,
           'update_time'=>time(),
           'park_time'=>$parkingTime,
           'car_type'=>$car_type,
           'is_out'=>$is_out,
           'vehicle_type'=>(int)$param['vehicleType'],
           'token'=>'',
           'sign'=>'',
           'auth_code'=>'',
           'trade_no'=>'',
           'service_name'=>'',
           'optname'=>''
        ];
       $user=self::dbGetUser($config['village_id'],$data['car_number']);
       if($user && !empty($user)){
            $data['uid']=intval($user['uid']);
            $data['car_id']=intval($user['car_id']);
            $data['bind_id']=intval($user['pigcms_id']);
            $data['user_name']=$user['name'] ? $user['name'] : '';
            $data['user_phone']=$user['phone'] ? $user['phone'] : '';
       }
       $res= self::dbInsert('house_village_car_access_record',$data);
       if($accessType == 2){
           self::dbUpdate('house_village_car_access_record','business_id = '.$config['village_id'].' and park_sys_type = "'.$data['park_sys_type'].'" and third_id = "'.$param['seq'].'" and order_id= "'.$param['seq'].'"',['is_out'=>1]);
       }
       return $res;
    }

    //todo 车辆进场
    public static function carInto($config,$param){
        $data=[
            'seq'           =>  isset($param['seq']) ? $param['seq'] : '',
            'plate'         =>  isset($param['plate']) ? $param['plate'] : '',
            'vehicleType'   =>  isset($param['vehicletype']) ? $param['vehicletype'] : '',
            'freeBerth'     =>  isset($param['freeberth']) ? $param['freeberth'] : '',
            'parkType'      =>  isset($param['parktype']) ? $param['parktype'] : '',
            'laneType'      =>  isset($param['lanetype']) ? $param['lanetype'] : '',
            'dataTime'      =>  isset($param['datatime']) ? $param['datatime'] : ''
        ];
        $result=self::pullCarPark(1,$config,$data);
        return ['code'=>0,'msg'=>'ok','data'=>$result];
    }

    //todo 车辆出场
    public static function carOut($config,$param){
        $data=[
            'seq'           =>  isset($param['seq']) ? $param['seq'] : '',
            'plate'         =>  isset($param['plate']) ? $param['plate'] : '',
            'vehicleType'   =>  isset($param['vehicletype']) ? $param['vehicletype'] : '',
            'freeBerth'     =>  isset($param['freeberth']) ? $param['freeberth'] : '',
            'parkingTime'   =>  isset($param['parkingtime']) ? $param['parkingtime'] : '',
            'parkType'      =>  isset($param['parktype']) ? $param['parktype'] : '',
            'payMoney'      =>  isset($param['paymoney']) ? $param['paymoney'] : '',
            'payType'       =>  isset($param['paytype']) ? $param['paytype'] : '',
            'laneType'      =>  isset($param['lanetype']) ? $param['lanetype'] : '',
            'dataTime'      =>  isset($param['datatime']) ? $param['datatime'] : ''
        ];
        $result=self::pullCarPark(2,$config,$data);
        return ['code'=>0,'msg'=>'ok','data'=>$result];
    }

    //todo  读取执行命令
    public static function readRecord(){
        $list=GateWay::getAllClientIdList();
        if(empty($list)){
            return true;
        }
        foreach ($list as $v){
            $data=self::dbGetRecord($v);
            if(empty($data)){
                continue;
            }
            foreach ($data as $v2){
                $info=unserialize($v2['data']);
                $info['requestid']=self::$mark.$v2['id'];
                $rr=Gateway::sendToClient($v, json_encode($info,JSON_UNESCAPED_UNICODE));
                if($rr){
                    $status=1;
                }else{
                    $status=2;
                }
                self::dbUpdate('park_d6_request_record','id = '.$v2['id'],[
                    'status'=>$status,
                    'update_time'=>time()
                ]);
                self::fdump_socket(['心跳--'.__LINE__,$v,$rr,$data,$info],'d6_park/socket/heartbeat',1,0);
            }
        }
        return true;
    }

    //todo 同步更新订单金额
    public static function uploadQueryPrice($config,$param){
        $data=[
            'orderId'       =>  isset($param['orderid']) ? $param['orderid'] : '',
            'plate'         =>  isset($param['plate']) ? $param['plate'] : '',
            'vehicleType'   =>  isset($param['vehicletype']) ? $param['vehicletype'] : '',
            'inTime'        =>  isset($param['intime']) ? $param['intime'] : 0,
            'totalMoney'    =>  isset($param['totalmoney']) ? $param['totalmoney'] : 0,
            'reduceMoney'   =>  isset($param['reducemoney']) ? $param['reducemoney'] : 0,
            'reduceTime'    =>  isset($param['reducetime']) ? $param['reducetime'] : 0,
            'dueMoney'      =>  isset($param['duemoney']) ? $param['duemoney'] : 0,
            'remark'        =>  isset($param['remark']) ? $param['remark'] : '',
            'dataTime'      =>  isset($param['datatime']) ? $param['datatime'] :0
        ];
        $parking_temp=self::dbSelect('house_village_parking_temp','state = 0 and service_name = "d6_charging" and village_id = '.$config['village_id'].' and  car_number= "'.$data['plate'].'"','id','id',2);
        if(!isset($parking_temp[0]['id'])){
            self::fdump_socket(['同步更新订单金额error--'.__LINE__,$config,$param,$parking_temp],'d6_park/uploadQueryPrice',1);
            return ['code'=>-1,'msg'=>'订单不存在','data'=>[]];
        }
        $data['dataTime']=intval($data['dataTime'] * 0.001);
        $data['inTime']=intval($data['inTime'] * 0.001);
        $duration=intval(($data['dataTime'] -  $data['inTime']) / 60);
        $rr=[
            'state'             =>  1,
            'free_out_time'     =>  0,
            'query_time'        =>  $data['dataTime'],
            'duration'          =>  $duration,
            'derate_money'      =>  $data['reduceMoney'] * 0.01,
            'derate_duration'   =>  $data['reduceTime'],
            'order_id'          =>  $data['orderId'],
            'query_order_no'    =>  '',
            'errmsg'            =>  $data['remark'],
            'price'             =>  $data['dueMoney'] * 0.01,
            'total'             =>  $data['totalMoney'] * 0.01,
            'position'          =>  '',
            'in_time'           =>  $data['inTime'],
        ];
        if($data['vehicleType']){
            $rr['vehicle_type']=$data['vehicleType'];
        }
        $result= self::dbUpdate('house_village_parking_temp','id = '.$parking_temp[0]['id'],$rr);
        self::fdump_socket(['同步更新订单金额data--'.__LINE__,$config,$param,$parking_temp,$rr,$result],'d6_park/uploadQueryPrice',1);
        return ['code'=>0,'msg'=>'ok','data'=>$result];
    }

}
