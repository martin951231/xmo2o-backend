<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2022/3/16 17:02
 */

namespace app\community\controller\common;


use app\community\controller\CommunityBaseController;
use app\community\model\service\Car\HouseVillageCarFlowService;
use app\community\model\service\HouseMeterService;
use app\community\model\service\HouseVillageParkingService;
use app\community\model\service\Park\D3ShowScreenService;

class ParkController extends CommunityBaseController
{
    /**
     * D3停车系统轮询心跳
     */
    public function receivedeviceinfo()
    {
        if (!empty($_POST)){
            $service_house_village_service=new HouseVillageParkingService();
            $parkShowScreen=new D3ShowScreenService();
            $serialno = isset($_POST['serialno']) && $_POST['serialno'] ? trim($_POST['serialno']) : '';
            $showScreenCacheKey = $serialno.'_show_screen_';
            $showScreenCacheEffective = 10;
            $receiveTime = date('mdH');
            try {
                fdump_api(['轮询心跳' => $serialno,$_POST],'park_temp/heartbeat_'.$serialno.'_log');
                $toNew = (new HouseVillageCarFlowService())->judgeToNew($serialno);
                if ($toNew) {
                    $res = (new HouseVillageCarFlowService())->handleHeartbeat($_POST);
                } else {
                    $res = $service_house_village_service->open_gate($_POST);
                }
                if($res && isset($res['is_close_park_passage']) && $res['is_close_park_passage']==1){
                    //http 不支持
                    echo json_encode(['code' => 200, 'msg' => 'OK']);
                    exit();
                }
                //fdump_api(['serialno' => $serialno,'res'=>$res],'park_temp/heartbeat_'.$receiveTime.'_log',1);
                $yk_heart_time = cache('park:'.$serialno.':heartbeat'); 
                cache('park:'.$serialno.':heartbeat', time());
            }catch (\Exception $e){
                $txt=$e->getMessage();
                fdump_api(['执行错误'=>$e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'post' => $_POST],'D3Park/errReceivedeviceinfo',true);
                $zz=$parkShowScreen->showVoiceTxt($txt,1,'01');
                $showScreenCacheKey .= md5($zz);
                $showScreenCacheTime = cache($showScreenCacheKey);
                if ($showScreenCacheTime && $showScreenCacheTime > 0) {
                    fdump_api(['推送车牌识别反馈重复' => $serialno, 'err' => $e->getMessage(), 'showScreenCacheTime' => $showScreenCacheTime],'park_temp/heartbeat_'.$serialno.'_log',1);
                    echo json_encode(['code' => 200, 'msg' => 'OK']);
                    exit();
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
                $msg = json_encode($return,JSON_UNESCAPED_UNICODE);
                cache($showScreenCacheKey, time(), $showScreenCacheEffective);
                echo $msg;exit();
            }
           // fdump_api(['推送车牌识别结果'=>$res,gettype($res)],'devicement1',true);
            $serialData=[];
            $white_list_operate=[];
            $showcontent=(isset($res['showscreen']['showcontent']) && !empty($res['showscreen']['showcontent'])) ? json_decode($res['showscreen']['showcontent'],true) : [];

            if ($res['data']==true||$res['pay_info']==true){
                fdump_api(['serialno' => $serialno,'res'=>$res],'park_temp/heartbeat_'.$receiveTime.'_log',1);
                if (empty($res['showscreen'])&&(!isset($res['white_list'])||empty($res['white_list']))){
                    fdump_api(['serialno' => $serialno,'res'=>$res,'msg'=>'{"Response_AlarmInfoPlate":{"info":"ok","content":"OK111","is_pay":"true"}}'],'park_temp/heartbeat_'.$receiveTime.'_log',1);
                    echo '{"Response_AlarmInfoPlate":{"info":"ok","content":"OK111","is_pay":"true"}}';
                    exit;
                }else{
                //     $txt='停车测试';
                    if ($res['showscreen']['content_type']==1 && !$showcontent){
                        $txt=$res['showscreen']['content'];
                        $zz=$parkShowScreen->showVoiceTxt($txt,$res['showscreen']['screen_row'],$res['showscreen']['serial'],$res['showscreen']['orderType'], $res['showscreen']['passage_type']);
                        $showScreenCacheKey .= md5($zz);
                        $showScreenCacheTime = cache($showScreenCacheKey);
                        if ($showScreenCacheTime && $showScreenCacheTime > 0) {
                            fdump_api(['推送车牌识别反馈重复' => $serialno, 'txt' => $txt, 'zz' => $zz, 'showScreenCacheTime' => $showScreenCacheTime],'park_temp/heartbeat_'.$serialno.'_log',1);
                            echo json_encode(['code' => 200, 'msg' => 'OK']);
                            exit();
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
                            fdump_api(['推送车牌识别反馈重复' => $serialno, 'txt' => $txt, 'zz' => $zz, 'showScreenCacheTime' => $showScreenCacheTime],'park_temp/heartbeat_'.$serialno.'_log',1);
                            echo json_encode(['code' => 200, 'msg' => 'OK']);
                            exit();
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
                    $msg = json_encode($return,JSON_UNESCAPED_UNICODE);
                    cache($showScreenCacheKey, time(), $showScreenCacheEffective);
                    fdump_api(['serialno' => $serialno,'res'=>$res,'msg'=>$msg],'park_temp/heartbeat_'.$receiveTime.'_log',1);
                    echo $msg;exit();
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
                    $msg = json_encode($return, JSON_UNESCAPED_UNICODE);
                    cache('park:'.$serialno.':cmd', time());
                    if (!$serialno) {
                        fdump_api(['获取推送的解析$msg' => $msg, 'txt' => '下发指令', 'zz' => $zz, 'base64DataText' => $base64DataText, 'return' => $return], 'A11/heart_zhiling', true);
                    } else {
                        fdump_api(['下发遥控上报指令' => $serialno, 'msg' => $msg, 'txt' => '下发指令', 'zz' => $zz, 'base64DataText' => $base64DataText, 'return' => $return],'park_temp/heartbeat_'.$serialno.'_log',1);
                    }
                    echo $msg;
                    exit();
                }
                if (empty($res['showscreen'])&&(!isset($res['white_list'])||empty($res['white_list']))){
                    echo json_encode(['code' => 200, 'msg' => 'OK']);
                    exit;
                }else{
                    $txt='停车测试';
                    if ($res['showscreen']['content_type']==1 && !$showcontent){
                        $txt=$res['showscreen']['content'];
                       $zz=$parkShowScreen->showVoiceTxt($txt,$res['showscreen']['screen_row'],$res['showscreen']['serial'],$res['showscreen']['orderType'], $res['showscreen']['passage_type']);
                        $showScreenCacheKey .= md5($zz);
                        $showScreenCacheTime = cache($showScreenCacheKey);
                        if ($showScreenCacheTime && $showScreenCacheTime > 0) {
                            fdump_api(['推送车牌识别反馈重复' => $serialno, 'txt' => $txt, 'zz' => $zz, 'showScreenCacheTime' => $showScreenCacheTime],'park_temp/heartbeat_'.$serialno.'_log',1);
                            echo json_encode(['code' => 200, 'msg' => 'OK']);
                            exit();
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
                            fdump_api(['推送车牌识别反馈重复' => $serialno, 'txt' => $txt, 'zz' => $zz, 'showScreenCacheTime' => $showScreenCacheTime],'park_temp/heartbeat_'.$serialno.'_log',1);
                            echo json_encode(['code' => 200, 'msg' => 'OK']);
                            exit();
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
                    $msg = json_encode($return,JSON_UNESCAPED_UNICODE);
                    cache($showScreenCacheKey, time(), $showScreenCacheEffective);
                    echo $msg;exit();
                }
            }
        }
        echo json_encode(['code' => 200, 'msg' => 'OK']);
        exit;
    }

    /**
     * D3停车系统推送车牌识别结果
     */
    public function plateresult()
    {
        $input = file_get_contents("php://input");
        if (1 || !empty($input)){
            $parkTime = date('YmdH');
            try {
                $data=json_decode($input,true);
                if (!$data) {
                    $data = json_decode(preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $input), true);
                }
            }catch (\Exception $e){
                fdump_api(['执行错误'=>$e->getMessage(),'car_number'=>$car_number, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'data' => $data],'D3Park/errLog',true);
            }catch (\Throwable $e){
                fdump_api(['执行错误'=>$e->getMessage(),'car_number'=>$car_number, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'data' => $data],'D3Park/errLog',true);
            }catch (\RuntimeException $e){
                fdump_api(['执行错误'=>$e->getMessage(),'car_number'=>$car_number, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'data' => $data],'D3Park/errLog',true);
            }catch (\InvalidArgumentException $e){
                fdump_api(['执行错误'=>$e->getMessage(),'car_number'=>$car_number, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'data' => $data],'D3Park/errLog',true);
            }
            $aa=$data;
            $car_number = '';
            if (isset($data['AlarmInfoPlate']['result']['PlateResult']['license'])) {
                $car_number = $data['AlarmInfoPlate']['result']['PlateResult']['license'];
            }
            if (isset($data['AlarmInfoPlate']['result']['PlateResult']['imageFile'])) {
                unset($aa['AlarmInfoPlate']['result']['PlateResult']['imageFile']);
            }
            if (isset($data['AlarmInfoPlate']['result']['PlateResult']['imageFragmentFile'])) {
                unset($aa['AlarmInfoPlate']['result']['PlateResult']['imageFragmentFile']);
            }
            fdump_api(['推送车牌识别结果'=>$aa,'car_number'=>$car_number],'D3Park/plateresult'.$parkTime,true);
            $service_house_village_service=new HouseVillageParkingService();
            try {
                if (isset($data['AlarmInfoPlate']['serialno'])) {
                    $serialno = $data['AlarmInfoPlate']['serialno'];
                } else {
                    $serialno = '';
                }
                $toNew = (new HouseVillageCarFlowService())->judgeToNew($serialno);
                if ($toNew) {
                    $res = (new HouseVillageCarFlowService())->handlePlateResult($data, $parkTime);
                } else {
                    $res = $service_house_village_service->add_park_info($data, $parkTime);
                }
            }catch (\Exception $e){
                $parkShowScreen=new D3ShowScreenService();
                fdump_api(['执行错误'=>$e->getMessage(),'car_number'=>$car_number, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'data' => $data],'D3Park/plateresult'.$parkTime,true);
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
                $msg = json_encode($return,JSON_UNESCAPED_UNICODE);
                fdump_api(['获取推送的解析msg'=>$msg,'car_number'=>$car_number,'txt' =>$txt,'zz' =>$zz,'base64DataText' =>$base64DataText,'return' =>$return],'D3Park/plateresult'.$parkTime,true);
                if ($txt == "该停车场未绑定收费规则") {
                    echo $msg;exit;
                }
                if ($txt == "没有停车纪录") {
                    echo $msg;exit;
                }
                if ($txt == "没有停车时长") {
                    echo $msg;exit;
                }
                fdump_api(['出场异常','car_number'=>$car_number],'D3Park/plateresult'.$parkTime,true);
                echo json_encode(['code' => 200, 'msg' => '访问成功']);
                exit;
            }
            fdump_api(['推送车牌识别结果'=>$res,'type' =>gettype($res),'car_number'=>$car_number],'D3Park/plateresult'.$parkTime,true);
            if ($res){
                $msg = '{"Response_AlarmInfoPlate":{"info":"ok","content":"下发开闸指令","is_pay":"true"}}';
                fdump_api(['返回开闸指令'=>$msg,'car_number'=>$car_number],'D3Park/plateresult'.$parkTime,true);
                echo $msg;
                exit;
            }else{
                echo json_encode(['code' => 200, 'msg' => '访问ok']);
                exit;
            }
        } else {
            echo json_encode(['code' => 200, 'msg' => '未传任何参数']);
            exit;
        }
    }

    /**
     * D3停车系统推送端口触发信息
     */
    public function gio()
    {
        fdump_api(['推送端口触发信息',$_GET,$_POST,file_get_contents("php://input")],'A11/gio',true);
        $datajson = file_get_contents("php://input");
        if(!empty($datajson)){
            $data=json_decode($datajson,true);
            fdump_api(['dataArr'=>$data],'A11/gio',true);
            if(!empty($data) && isset($data['AlarmGioIn']) && !empty($data['AlarmGioIn'])){
                $alarmGioIn=$data['AlarmGioIn'];
                $param=array();
                $param['serialno']=$alarmGioIn['serialno'];
                $param['device_name']=$alarmGioIn['deviceName'];
                $param['channel']=isset($alarmGioIn['channel']) ? $alarmGioIn['channel']:'';
                $param['opt_source']=-1;
                $param['opt_value']=-1;
                if(isset($alarmGioIn['result']) && isset($alarmGioIn['result']['TriggerResult']) && isset($alarmGioIn['result']['TriggerResult']['source'])){
                    $param['opt_source']=$alarmGioIn['result']['TriggerResult']['source'];  //0开闸 1关闸 其他值暂时用不上
                    $param['opt_source']=intval($param['opt_source']);
                    $param['opt_value']=$alarmGioIn['result']['TriggerResult']['value'];//0 表示开了 是1的  表示复位了 暂时用不上
                    $param['opt_value']=intval($param['opt_value']);
                    if($param['opt_source']===0 && $param['opt_value']===0){
                        //opt_value为1时 表示复位 不处理
                        $toNew = (new HouseVillageCarFlowService())->judgeToNew($param['serialno']);
                        if ($toNew) {
                            $param['fromType'] = 'gio';
                            (new HouseVillageCarFlowService())->addPortData($param);
                        } else {
                            $service_house_village_service=new HouseVillageParkingService();
                            $service_house_village_service->addPortData($param);
                        }
                    }
                }
                
            }
        }
        echo json_encode(['code' => 200, 'msg' => '访问成功']);
        exit;
    }

    /**
     * D3停车系统推送串口数据
     */
    public function serial()
    {
       $serialTime = date('YmdH');
       fdump_api(['推送串口数据',$_GET,$_POST,file_get_contents("php://input")],'A11/serial'.$serialTime,true);
        $data = file_get_contents("php://input");
       // $data='{"SerialData":{"channel":0,"data":"q1UBeAEGAAAAAK+rVQF4ARQA3awkrw==","dataLen":22,"deviceName":"IVS","ipaddr":"192.168.2.201","serialChannel":0,"serialno":"141443bb-3ffb303a"}}';
        if (!empty($data)){
            $data=json_decode($data,true);
            $service_house_village_service=new HouseVillageParkingService();
            $service_D3_show_service=new D3ShowScreenService();
            $msg=$data['SerialData']['data'];
            try {
                $tt=$service_D3_show_service->deTranslation($msg);
                if (!empty($tt) && isset($data['SerialData']['serialno'])){
                    $param=[
                        'serialno'=> $data['SerialData']['serialno'],
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
                fdump_api(['推送串口数据--错误了'=>$exception->getMessage()],'A11/serial'.$serialTime,true);
            }catch (\Throwable $exception){
                fdump_api(['推送串口数据--Throwable--错误了'=>$exception->getMessage()],'A11/serial'.$serialTime,true);
            }
        }
        echo json_encode(['code' => 200, 'msg' => '访问成功']);
        exit;
    }

    public function test(){
        //AA5501640027000C01000100CDA3B3B5B2E2CAD4B167AF
        //AA5501640027000C01000100CDA3B3B5B2E2CAD4B167AF
        $service_D3_show_service=new D3ShowScreenService();
        $zz='AB55017700FF00000000AF';
        $zz='AB55017700FF00000000AF';
        $zz='q1UBdwD/AAAAAK8=';
        $zz='q1UBeAEGAAAAAK+rVQF4ARQA3awkrw==';
        $base64DataText = $service_D3_show_service->deTranslation($zz);
        print_r($base64DataText);die;
        fdump_api(['16禁止数据转换',$zz,$txt],'rece1111',true);
        $tt=$service_D3_show_service->translation($zz);
        print_r($tt);
    }
}