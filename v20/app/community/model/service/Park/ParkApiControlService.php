<?php
namespace app\community\model\service\Park;

use app\community\model\db\ParkOpenLog;
use app\community\model\db\ParkPassage;
use app\community\model\db\ParkShowscreenLog;
use app\community\model\service\Car\HouseVillageCarFlowService;
use app\community\model\service\HouseVillageParkingService;
use app\community\model\service\Park\D3ShowScreenService;
use app\community\model\db\HouseVillageCarMqttRecord;

class ParkApiControlService
{
    /**
     * D3停车系统轮询心跳
     */
    public function receivedeviceinfo($postArr=array(),$xtype='')
    {
        if (!empty($postArr)){
            $service_house_village_service=new HouseVillageParkingService();
            $parkShowScreen=new D3ShowScreenService();
            $serialno = isset($postArr['serialno']) && $postArr['serialno'] ? trim($postArr['serialno']) : '';
            $showScreenCacheKey = $serialno.'_show_screen_';
            $showScreenCacheEffective = 10;
            $receiveTime = date('mdH');
            try {
                fdump_api(['ParkApiControlService','轮询心跳' => $serialno,$postArr],'park_temp/heartbeat_'.$serialno.'_log');
                $toNew = (new HouseVillageCarFlowService())->judgeToNew($serialno);
                if ($toNew) {
                    $res = (new HouseVillageCarFlowService())->handleHeartbeat($postArr);
                } else {
                    $res = $service_house_village_service->open_gate($postArr);
                }
                if($res && isset($res['is_close_park_passage']) && $res['is_close_park_passage']==1){
                    return ['code' => 200, 'msg' => 'OK','dataArr'=>array('is_close_park_passage'=>1)];
                }
                $yk_heart_time = cache('park:'.$serialno.':heartbeat'); 
                cache('park:'.$serialno.':heartbeat', time());
            }catch (\Exception $e){
                $txt=$e->getMessage();
                fdump_api(['ParkApiControlService','执行错误'=>$e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'post' => $postArr],'D3Park/errReceivedeviceinfo',true);
                $zz=$parkShowScreen->showVoiceTxt($txt,1,'01');
                $showScreenCacheKey .= md5($zz);
                $showScreenCacheTime = cache($showScreenCacheKey);
                if ($showScreenCacheTime && $showScreenCacheTime > 0) {
                    fdump_api(['ParkApiControlService','推送车牌识别反馈重复' => $serialno, 'err' => $e->getMessage(), 'showScreenCacheTime' => $showScreenCacheTime],'park_temp/heartbeat_'.$serialno.'_log',1);
                    return ['code' => 200, 'msg' => 'OK'];
                }
                $base64DataText = $parkShowScreen->translation($zz);
                $base64DataTextLength = strlen($base64DataText);
                $serialData[] = [
                    'serialChannel' => 0,
                    'data' => $base64DataText,
                    'dataLen' => $base64DataTextLength,
                ];
                $return  = [
                    'Response_AlarmInfoPlate' => [
                        'serialData' => $serialData,
                        'info' => '',
                        'content' => '',
                        'is_pay' => '',
                    ],
                ];
                cache($showScreenCacheKey, time(), $showScreenCacheEffective);
                return ['code' => 200, 'msg' => 'OK','dataArr'=>$return];
            }
            $serialData=[];
            $white_list_operate=[];
            $showcontent=(isset($res['showscreen']['showcontent']) && !empty($res['showscreen']['showcontent'])) ? json_decode($res['showscreen']['showcontent'],true) : [];

            if ($res['data']==true||$res['pay_info']==true){
                fdump_api(['ParkApiControlService','serialno' => $serialno,'res'=>$res],'park_temp/heartbeat_'.$receiveTime.'_log',1);
                if (empty($res['showscreen'])&&(!isset($res['white_list'])||empty($res['white_list']))){
                    fdump_api(['ParkApiControlService','serialno' => $serialno,'res'=>$res,'msg'=>'{"Response_AlarmInfoPlate":{"info":"ok","content":"OK111","is_pay":"true"}}'],'park_temp/heartbeat_'.$receiveTime.'_log',1);
                    $return  = [
                        'Response_AlarmInfoPlate' => [
                            'info' => 'ok',
                            'content' => 'OK111',
                            'is_pay' => 'true',
                        ],
                    ];
                    return ['code' => 200, 'msg' => 'OK','dataArr'=>$return];
                }else{
                    if ($res['showscreen']['content_type']==1 && !$showcontent){
                        $txt=$res['showscreen']['content'];
                        $zz=$parkShowScreen->showVoiceTxt($txt,$res['showscreen']['screen_row'],$res['showscreen']['serial'],$res['showscreen']['orderType'], $res['showscreen']['passage_type']);
                        $showScreenCacheKey .= md5($zz);
                        $showScreenCacheTime = cache($showScreenCacheKey);
                        if ($showScreenCacheTime && $showScreenCacheTime > 0) {
                            fdump_api(['ParkApiControlService','推送车牌识别反馈重复' => $serialno, 'txt' => $txt, 'zz' => $zz, 'showScreenCacheTime' => $showScreenCacheTime],'park_temp/heartbeat_'.$serialno.'_log',1);
                            return ['code' => 200, 'msg' => 'OK'];
                        }
                        $base64DataText = $parkShowScreen->translation($zz);
                        $base64DataTextLength = strlen($base64DataText);
                        $serialData[] = [
                            'serialChannel' => 0,
                            'data' => $base64DataText,
                            'dataLen' => $base64DataTextLength,
                        ];
                    }
                    if ($res['showscreen']['content_type']==2 && !$showcontent){
                        $txt=$res['showscreen'];
                        $zz=$parkShowScreen->showVoice($txt,'01', $res['showscreen']['passage_type']);
                        $showScreenCacheKey .= md5($zz);
                        $showScreenCacheTime = cache($showScreenCacheKey);
                        if ($showScreenCacheTime && $showScreenCacheTime > 0) {
                            fdump_api(['ParkApiControlService','推送车牌识别反馈重复' => $serialno, 'txt' => $txt, 'zz' => $zz, 'showScreenCacheTime' => $showScreenCacheTime],'park_temp/heartbeat_'.$serialno.'_log',1);
                            return ['code' => 200, 'msg' => 'OK'];
                        }
                        $base64DataText = $parkShowScreen->translation($zz);
                        $base64DataTextLength = strlen($base64DataText);
                        $serialData[] = [
                            'serialChannel' => 0,
                            'data' => $base64DataText,
                            'dataLen' => $base64DataTextLength,
                        ];
                    }
                    if (isset($res['white_list'])&&!empty($res['white_list'])){
                        $operate_type=$res['white_list']['operate_type'];//操作类型(0:增加，1：删除)
                        //白名单数组：单次操作，最大支持 5 条
                        $white_list_data[]=[
                            'plate'=>$res['white_list']['car_number'],//车牌号
                            'enable'=>1,//当前名单是否有效（0：无效， 1,：有效）
                            'need_alarm'=>$res['white_list']['need_alarm'],//当前名单是否为黑名单（0：否，1：黑名单）
                            'enable_time'=>date('Y-m-d H:i:s',$res['white_list']['start_time']),//当前名单生效时间，如：2018-01-01 11:11:11
                            'overdue_time'=>date('Y-m-d H:i:s',$res['white_list']['end_time']),//当前名单过期时间，如：2018-01-01 11:11:11
                        ];
                        $white_list_operate=[
                            'operate_type'=>$operate_type,
                            'white_list_data'=>$white_list_data,
                        ];
                    }
                    $return  = [
                        'Response_AlarmInfoPlate' => [
                            'serialData' => $showcontent ? $showcontent : $serialData,
                            'white_list_operate' => $white_list_operate,
                            'info' => 'ok',
                            'content' => 'OK111',
                            'is_pay' => 'true',
                        ],
                    ];

                    cache($showScreenCacheKey, time(), $showScreenCacheEffective);
                    fdump_api(['ParkApiControlService','serialno' => $serialno,'res'=>$res,'return'=>$return],'park_temp/heartbeat_'.$receiveTime.'_log',1);
                    return ['code' => 200, 'msg' => 'OK','dataArr'=>$return];
                }

            }else{
                $yk_cmd = cache('park:'.$serialno.':cmd');
                if ($yk_heart_time) {
                    $yk_heart_time1 = time() - $yk_heart_time;
                } else {
                    $yk_heart_time1 = 31;
                }
                if (!$yk_cmd || $yk_heart_time1 > 30) {
                    // 没有缓存或者心跳间隔超出30秒执行一次
                    $zz = 'AB55017700FF00000000AF';
                    $base64DataText = $parkShowScreen->translation($zz);
                    $base64DataTextLength = strlen($base64DataText);
                    $serialData[] = [
                        'serialChannel' => 0,
                        'data' => $base64DataText,
                        'dataLen' => $base64DataTextLength,
                    ];
                    $return = [
                        'Response_AlarmInfoPlate' => [
                            'serialData' => $showcontent ? $showcontent : $serialData,
                            'info' => '',
                            'content' => '',
                            'is_pay' => '',
                        ],
                    ];
         
                    cache('park:'.$serialno.':cmd', time());
                    if (!$serialno) {
                        fdump_api(['ParkApiControlService','txt' => '下发指令', 'zz' => $zz, 'base64DataText' => $base64DataText, 'return' => $return], 'A11/heart_zhiling', true);
                    } else {
                        fdump_api(['ParkApiControlService','下发遥控上报指令' => $serialno, 'txt' => '下发指令', 'zz' => $zz, 'base64DataText' => $base64DataText, 'return' => $return],'park_temp/heartbeat_'.$serialno.'_log',1);
                    }
                    return ['code' => 200, 'msg' => 'OK','dataArr'=>$return];
                }
                if (empty($res['showscreen'])&&(!isset($res['white_list'])||empty($res['white_list']))){
                    return ['code' => 200, 'msg' => 'OK'];
                }else{
                    $txt='停车测试';
                    if ($res['showscreen']['content_type']==1 && !$showcontent){
                        $txt=$res['showscreen']['content'];
                       $zz=$parkShowScreen->showVoiceTxt($txt,$res['showscreen']['screen_row'],$res['showscreen']['serial'],$res['showscreen']['orderType'], $res['showscreen']['passage_type']);
                        $showScreenCacheKey .= md5($zz);
                        $showScreenCacheTime = cache($showScreenCacheKey);
                        if ($showScreenCacheTime && $showScreenCacheTime > 0) {
                            fdump_api(['ParkApiControlService','推送车牌识别反馈重复' => $serialno, 'txt' => $txt, 'zz' => $zz, 'showScreenCacheTime' => $showScreenCacheTime],'park_temp/heartbeat_'.$serialno.'_log',1);
                            return ['code' => 200, 'msg' => 'OK'];
                        } 
                        $base64DataText = $parkShowScreen->translation($zz);
                        $base64DataTextLength = strlen($base64DataText);
                        $serialData[] = [
                            'serialChannel' => 0,
                            'data' => $base64DataText,
                            'dataLen' => $base64DataTextLength,
                        ];
                    }
                    if ($res['showscreen']['content_type']==2 && !$showcontent){
                        $txt=$res['showscreen'];
                        $zz=$parkShowScreen->showVoice($txt,'01', $res['showscreen']['passage_type']);
                        $showScreenCacheKey .= md5($zz);
                        $showScreenCacheTime = cache($showScreenCacheKey);
                        if ($showScreenCacheTime && $showScreenCacheTime > 0) {
                            fdump_api(['ParkApiControlService','推送车牌识别反馈重复' => $serialno, 'txt' => $txt, 'zz' => $zz, 'showScreenCacheTime' => $showScreenCacheTime],'park_temp/heartbeat_'.$serialno.'_log',1);
                            return ['code' => 200, 'msg' => 'OK'];
                        }
                        $base64DataText = $parkShowScreen->translation($zz);
                        $base64DataTextLength = strlen($base64DataText);
                        $serialData[] = [
                            'serialChannel' => 0,
                            'data' => $base64DataText,
                            'dataLen' => $base64DataTextLength,
                        ];
                    }
                    if (isset($res['white_list'])&&!empty($res['white_list'])){
                        $operate_type=$res['white_list']['operate_type'];//操作类型(0:增加，1：删除)
                        //白名单数组：单次操作，最大支持 5 条
                        $white_list_data[]=[
                            'plate'=>$res['white_list']['car_number'],//车牌号
                            'enable'=>1,//当前名单是否有效（0：无效， 1,：有效）
                            'need_alarm'=>$res['white_list']['need_alarm'],//当前名单是否为黑名单（0：否，1：黑名单）
                            'enable_time'=>date('Y-m-d H:i:s',$res['white_list']['start_time']),//当前名单生效时间，如：2018-01-01 11:11:11
                            'overdue_time'=>date('Y-m-d H:i:s',$res['white_list']['end_time']),//当前名单过期时间，如：2018-01-01 11:11:11
                        ];
                        $white_list_operate=[
                            'operate_type'=>$operate_type,
                            'white_list_data'=>$white_list_data,
                        ];
                    }
                    $return  = [
                        'Response_AlarmInfoPlate' => [
                            'serialData' => $showcontent ? $showcontent : $serialData,
                            'white_list_operate' => $white_list_operate,
                            'info' => '',
                            'content' => '',
                            'is_pay' => '',
                        ],
                    ];
                    cache($showScreenCacheKey, time(), $showScreenCacheEffective);
                    return ['code' => 200, 'msg' => '访问ok','dataArr'=>$return];
                }
            }
        }
        return ['code' => 200, 'msg' => 'OK'];
        
    }

    /**
     * D3停车系统推送车牌识别结果
     */
    public function plateresult($postArr=array(),$xtype='')
    {

        if (!empty($postArr)){
            $parkTime = date('YmdH');
            $postArr['from_xtype']=$xtype;
            $aa=$postArr;
            $car_number = '';
            if (isset($postArr['AlarmInfoPlate']['result']['PlateResult']['license'])) {
                $car_number = $postArr['AlarmInfoPlate']['result']['PlateResult']['license'];
            }
            if (isset($postArr['AlarmInfoPlate']['result']['PlateResult']['imageFile'])) {
                unset($aa['AlarmInfoPlate']['result']['PlateResult']['imageFile']);
            }
            if (isset($postArr['AlarmInfoPlate']['result']['PlateResult']['imageFragmentFile'])) {
                unset($aa['AlarmInfoPlate']['result']['PlateResult']['imageFragmentFile']);
            }
            fdump_api(['ParkApiControlService','推送车牌识别结果'=>$aa,'car_number'=>$car_number],'D3Park/plateresult'.$parkTime,true);
            $service_house_village_service=new HouseVillageParkingService();
            try {
                if (isset($postArr['AlarmInfoPlate']['serialno'])) {
                    $serialno = $postArr['AlarmInfoPlate']['serialno'];
                } else {
                    $serialno = '';
                }
                $toNew = (new HouseVillageCarFlowService())->judgeToNew($serialno);
                if ($toNew) {
                    $res = (new HouseVillageCarFlowService())->handlePlateResult($postArr, $parkTime);
                } else {
                    $res = $service_house_village_service->add_park_info($postArr, $parkTime);
                }
            }catch (\Exception $e){
                $parkShowScreen=new D3ShowScreenService();
                fdump_api(['ParkApiControlService','执行错误'=>$e->getMessage(),'car_number'=>$car_number, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'data' => $postArr],'D3Park/plateresult'.$parkTime,true);
                $txt=$e->getMessage();
                $zz=$parkShowScreen->showVoiceTxt($txt,1,'01');
                $base64DataText = $parkShowScreen->translation($zz);
                $base64DataTextLength = strlen($base64DataText);
                $serialData[] = [
                    'serialChannel' => 0,
                    'data' => $base64DataText,
                    'dataLen' => $base64DataTextLength,
                ];
                $return  = [
                    'Response_AlarmInfoPlate' => [
                        'serialData' => $serialData,
                        'info' => '',
                        'content' => '',
                        'is_pay' => '',
                    ],
                ];
                fdump_api(['ParkApiControlService','获取推送的解析msg'=>$return,'car_number'=>$car_number,'txt' =>$txt,'zz' =>$zz,'base64DataText' =>$base64DataText,'return' =>$return],'D3Park/plateresult'.$parkTime,true);
                if ($txt == "该停车场未绑定收费规则") {
                    return ['code' => 200, 'msg' => '访问ok','dataArr'=>$return];
                }
                if ($txt == "没有停车纪录") {
                    return ['code' => 200, 'msg' => '访问ok','dataArr'=>$return ];
                }
                if ($txt == "没有停车时长") {
                    return ['code' => 200, 'msg' => '访问ok','dataArr'=>$return];
                }
                fdump_api(['ParkApiControlService','出场异常','car_number'=>$car_number],'D3Park/plateresult'.$parkTime,true);
                return ['code' => 200, 'msg' => '访问成功'];
            }
            fdump_api(['ParkApiControlService','推送车牌识别结果'=>$res,'type' =>gettype($res),'car_number'=>$car_number],'D3Park/plateresult'.$parkTime,true);
            if ($res){
                $msg = '{"Response_AlarmInfoPlate":{"info":"ok","content":"下发开闸指令","is_pay":"true"}}';
                $msgArr=array( 
                    'Response_AlarmInfoPlate' => [
                    'info' => 'ok',
                    'content' => '下发开闸指令',
                    'is_pay' => "true",
                ],);
                fdump_api(['ParkApiControlService','返回开闸指令'=>$msg,'car_number'=>$car_number],'D3Park/plateresult'.$parkTime,true);
                return ['code' => 200, 'msg' => '访问ok' ,'dataArr'=>$msgArr];
            }else{
                return ['code' => 200, 'msg' => '访问ok'];
            }
        } else {
            return ['code' => 200, 'msg' => '未传任何参数'];
           
        }
    }

    /**
     * D3停车系统推送端口触发信息
     */
    public function gio($postArr=array(),$type='')
    {
        if(!empty($postArr)){
            $serialTime = date('H');
            fdump_api(['ParkApiControlService','dataArr'=>$postArr],'parkApi/gio'.$serialTime,true);
            if(!empty($postArr) && isset($postArr['AlarmGioIn']) && !empty($postArr['AlarmGioIn'])){
                $alarmGioIn=$postArr['AlarmGioIn'];
                $param=array();
                $param['serialno']=$alarmGioIn['serialno'];
                $param['device_name']=$alarmGioIn['deviceName'];
                $param['channel']=isset($alarmGioIn['channel']) ? $alarmGioIn['channel']:'';
                $param['opt_source']=-1;
                $param['opt_value']=-1;
                if(isset($alarmGioIn['result']) && isset($alarmGioIn['result']['TriggerResult']) && isset($alarmGioIn['result']['TriggerResult']['source'])) {
                    $param['opt_source'] = $alarmGioIn['result']['TriggerResult']['source'];  //0开闸 1关闸 其他值暂时用不上
                    $param['opt_source'] = intval($param['opt_source']);
                    $param['opt_value'] = $alarmGioIn['result']['TriggerResult']['value'];//0 表示开了 是1的  表示复位了 暂时用不上
                    $param['opt_value'] = intval($param['opt_value']);
                    try {
                        if ($param['opt_source'] === 0 && $param['opt_value'] === 0) {
                            //opt_value为1时 表示复位 不处理
                            $toNew = (new HouseVillageCarFlowService())->judgeToNew($param['serialno']);
                            if ($toNew) {
                                $param['fromType'] = 'gio';
                                (new HouseVillageCarFlowService())->addPortData($param);
                            } else {
                                $service_house_village_service = new HouseVillageParkingService();
                                $service_house_village_service->addPortData($param);
                            }
                        }
                    }catch (\Exception $exception){
                        fdump_api(['ParkApiControlService','emsg'=>$exception->getMessage()],'parkApi/gio'.$serialTime,true);
                    }
                }
            }
        }
        return ['code' => 200, 'msg' => '访问成功'];
        
    }

    /**
     * D3停车系统推送串口数据
     */
    public function serial($postArr=array(),$type='')
    {
       $serialTime = date('H');
       fdump_api([__LINE__,'ParkApiControlService',$postArr],'parkApi/serial'.$serialTime,true);
       // $data='{"SerialData":{"channel":0,"data":"q1UBeAEGAAAAAK+rVQF4ARQA3awkrw==","dataLen":22,"deviceName":"IVS","ipaddr":"192.168.2.201","serialChannel":0,"serialno":"141443bb-3ffb303a"}}';
        if (!empty($postArr)){
            $service_house_village_service=new HouseVillageParkingService();
            $service_D3_show_service=new D3ShowScreenService();
            $msg=$postArr['SerialData']['data'];
            try {
                $tt=$service_D3_show_service->deTranslation($msg);
                if (!empty($tt) && isset($postArr['SerialData']['serialno'])){
                    $param=[
                        'serialno'=> $postArr['SerialData']['serialno'],
                        'msg'=>$tt,
                        'content'=>$msg,
                        'serialTime' => $serialTime,
                    ];
                    $toNew = (new HouseVillageCarFlowService())->judgeToNew($param['serialno']);
                    if ($toNew) {
                        $param['fromType'] = 'serial';
                        (new HouseVillageCarFlowService())->addPortData($param);
                    } else {
                        $service_house_village_service->addSerialData($param);
                    }
                }
            }catch (\Exception $exception){
                fdump_api([__LINE__,'ParkApiControlService','emsg'=>$exception->getMessage()],'parkApi/serial'.$serialTime,true);
            }catch (\Throwable $exception){
                fdump_api([__LINE__,'ParkApiControlService','emsg'=>$exception->getMessage()],'parkApi/serial'.$serialTime,true);
            }
        }
        return ['code' => 200, 'msg' => '访问成功'];
    }

    public function addOneCarMqttRecord($addData=array()){
        $houseVillageCarMqttRecordDb= new HouseVillageCarMqttRecord();
        $insert_id= $houseVillageCarMqttRecordDb->addOneData($addData);
        return $insert_id;
    }

    public function getOneMqttSerialno()
    {
        $db_park_showscreen_log = new ParkShowscreenLog();
        $db_park_open_log = new ParkOpenLog();
        $db_park_passage = new ParkPassage();
        $now_time=time();
        $tmp_time=$now_time-600;
        $where = array(['channel_id', '<>', '']);
        $where[]= array('add_time', '>', $tmp_time);
        $log_info = $db_park_open_log->get_one($where, 'channel_id');
        $channel_id_serialno = '';
        if ($log_info && !$log_info->isEmpty() && $log_info['channel_id']) {
            $channel_id_serialno = $log_info['channel_id'];
        }
        if (empty($channel_id_serialno)) {
            $showscreen_info = $db_park_showscreen_log->getFind($where, 'channel_id','id desc');
            if ($showscreen_info && !$showscreen_info->isEmpty() && $showscreen_info['channel_id']) {
                $channel_id_serialno = $showscreen_info['channel_id'];
            }
        }
        if ($channel_id_serialno) {
            $whereArr = ['device_number' => $channel_id_serialno, 'status' => '1', 'device_protocol' => '1'];
            $passage_info_obj = $db_park_passage->getFind($whereArr, 'id,village_id');
            if (empty($passage_info_obj) || $passage_info_obj->isEmpty()) {
                //不是mqtt协议的
                $channel_id_serialno = '';
            }
        }
        return $channel_id_serialno;

    }

    public function updatePassageInfo($whereArr=array(),$saveArr=array()){
        $db_park_passage = new ParkPassage();
        $ret= $db_park_passage->save_one($whereArr,$saveArr);
        return $ret;
    }

    public function  getMqttPassages(){
        $db_park_passage = new ParkPassage();
        $whereArr=array(['village_id','>',0],['park_sys_type','in',["D3","A11"]],['status','=',1]);
        $whereArr['_string']='((device_protocol="1" AND opt_protocol_type="0") OR (device_protocol="0"  AND opt_protocol_type="2"))';
        $mqttPassagesObj=$db_park_passage->getList($whereArr,'id,village_id,passage_direction,device_number,garage_id,device_protocol,opt_protocol_type','id DESC',5);
        $infoPassage=array();
        if($mqttPassagesObj && !$mqttPassagesObj->isEmpty()){
            $infoPassage= $mqttPassagesObj->toArray();
        }
        return $infoPassage;
    }
    
}