<?php
/**
 *
 */

namespace app\thirdRobot\model\service;

use app\thirdRobot\model\db\ThirdRobotMap;
use app\thirdRobot\model\db\ThirdRobotMapPoints;
use app\thirdRobot\model\service\ThirdRobotRecognitionService;
use app\thirdRobot\model\db\ThirdRobotDevice;
use app\thirdRobot\model\db\ThirdRobotDeviceSet;
use app\thirdRobot\model\db\ThirdRobotHistoryTask;
use app\thirdRobot\model\db\ThirdRobotHistoryTaskDetail;
use app\thirdRobot\model\db\ThirdRobotAlarmInfo;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseWorker;
use app\common\model\db\Admin;
use app\thirdRobot\model\db\ThirdRobotAlarmNoticeSet;
use app\thirdRobot\model\service\ThirdRobotDeviceCmdService;
use app\community\model\db\WisdomQrcodeCate;
use app\community\model\db\WisdomQrcode;
use app\community\model\db\WisdomQrcodePositionIndex;
use app\community\model\db\WisdomQrcodeRecordLog;
use app\community\model\db\HouseVillageUserBind;
use app\thirdRobot\model\service\ThirdRobotSubscribeMessageService;
use think\Exception;

class ThirdRobotDeviceService
{

    public $device_type_arr = ['1' => '掌上超声设备', '2' => '货柜', '3' => '服务机器', '4' => '送货', '5' => '机场', '6' => '清洁', '7' => 'cloudia'];
    public $running_status_arr = ['1' => '正常', '2' => '停用', '3' => '故障', '4' => '维修中'];
    public $work_status_arr = [['ikey' => 0, 'ivalue' => '非执行'], ['ikey' => 1, 'ivalue' => '执行中']];
    public $device_online_arr = [['ikey' => 0, 'ivalue' => '离线'], ['ikey' => 1, 'ivalue' => '在线']];
    public $alarm_confirm_flag_arr = ['-1' => '告警未处理', '0' => '手动消除', '1' => '自动解除'];
    public $rdm_alarm_type_arr = ['2' => '通讯告警(Communications)', '3' => 'QoS服务质量(Quality of Service)', '4' => '处理出错(Processing Error)', '5' => '设备告警(Equipment)', '6' => '环境告警(Environmental)'];
    //任务状态 -1：未执行 0：发起失败 1：进行中 2：异常挂起 3：异常结束 4：成功结束 5：取消结束 6：暂停中
    public $robot_task_status_arr = ['-1' => '未执行', '0' => '发起失败', '1' => '进行中', '2' => '异常挂起', '3' => '异常结束', '4' => '成功结束', '5' => '取消结束', '6' => '暂停中'];
    //任务节点状态 -1：未执行 节点状态 0：未开始 1：抵达中 2:异常挂起 3：异常结束 4：成功完成 5：取消结束 6：暂停中 7： 已抵达
    public $robot_task_node_status_arr = ['-1' => '未执行', '0' => '未开始', '1' => '抵达中', '2' => '异常挂起', '3' => '异常结束', '4' => '成功完成', '5' => '取消结束', '6' => '暂停中', '7' => '已抵达'];
    public $eventCodeArr=['2100001','2100002','2100028', '2100016', '2100028', '2100032', '2100033'];  //订阅事件code
    public $alarmCodeArr=['1000001','1000002','1000004','1000020','1000026','1000027','1000029','1000031','1000035','1000038','1000039','1000040','1000041','1000042','1000046','1000048','1000049','1000052','1000058','1000060','1000063','1000066','1000067','1000068','1000069','1000070','1000071','1000072','1000073','1000074','1000075','1000076','1000077','1000078','1000079','1000080','1000081','1000084','1000087','1000088','1000089','1000092','1000300','1000302','1000303','1000304','1000307','1000308','1000309','1000310','1000312','1000900'];  //订阅告警code

    public function checkConfigure($notify = false)
    {
        $appid = cfg('third_robot_recognition_appid');
        $appsecret = cfg('third_robot_recognition_appsecret');
        $third_robot_recognition_open = cfg('third_robot_recognition_open');
        $is_configure_right = 1;
        if ($third_robot_recognition_open < 1) {
            $is_configure_right = 0;
        }
        if (empty($appid)) {
            $is_configure_right = 0;
        }
        if (empty($appsecret)) {
            $is_configure_right = 0;
        }
        if ($notify && $is_configure_right > 0) {
            $thirdRobotDeviceSetDb = new ThirdRobotDeviceSet();
            $whereArr = array();
            $whereArr[] = ['app_id', '=', $appid];
            $deviceSet = $thirdRobotDeviceSetDb->getOneData($whereArr);
            if ($deviceSet && !$deviceSet->isEmpty()) {
                $deviceSet = $deviceSet->toArray();
            } else {
                $deviceSet = array('app_id' => $appid);
                $idd = $thirdRobotDeviceSetDb->addOneData($deviceSet);
                $deviceSet['id'] = $idd;
            }
            $thirdRobotRecognitionService = new ThirdRobotRecognitionService();
            $saveRegisterArr = array();
            $site_url = rtrim(cfg('site_url'), '/');
            $nowtime = time();
            $response_data = array();
            if (!isset($deviceSet['event_subscribe_time']) || $deviceSet['event_subscribe_time'] < 100) {
                // v20/public/index.php/thirdRobot/EventNotifyMsg/receiveMsg
                $params = array();
                $params['type'] = 'event';
                $params['msgTypeCodeList'] = $this->eventCodeArr;
                $params['notifyUrl'] = $site_url . '/v20/public/index.php/thirdRobot/EventNotifyMsg/receiveMsg';
                $retdata = $thirdRobotRecognitionService->registerSubscribe($params);
                if ($retdata && is_array($retdata) && isset($retdata['code'])) {
                    $code = intval($retdata['code']);
                    $saveRegisterArr['event_notify_url'] = $params['notifyUrl'];
                    $response_data['event'] = $retdata;
                    if ($code < 1) {
                        $saveRegisterArr['event_subscribe_time'] = $nowtime;
                    }
                }
            }
            if (!isset($deviceSet['alarm_subscribe_time']) || $deviceSet['alarm_subscribe_time'] < 100) {
                // v20/public/index.php/thirdRobot/AlarmNotifyMsg/receiveMsg
                $params = array();
                $params['type'] = 'alarm';
                $params['msgTypeCodeList'] = $this->alarmCodeArr;
                $params['notifyUrl'] = $site_url . '/v20/public/index.php/thirdRobot/AlarmNotifyMsg/receiveMsg';
                $retdata = $thirdRobotRecognitionService->registerSubscribe($params);
                if ($retdata && is_array($retdata) && isset($retdata['code'])) {
                    $code = intval($retdata['code']);
                    $saveRegisterArr['alarm_notify_url'] = $params['notifyUrl'];
                    $response_data['alarm'] = $retdata;
                    if ($code < 1) {
                        $saveRegisterArr['alarm_subscribe_time'] = $nowtime;
                    }
                }
            }
            if (!isset($deviceSet['task_subscribe_time']) || $deviceSet['task_subscribe_time'] < 100) {
                // v20/public/index.php/thirdRobot/TaskNotifyMsg/receiveMsg
                $params = array();
                $params['type'] = 'task';
                $params['msgTypeCodeList']=array();
                $params['notifyUrl'] = $site_url . '/v20/public/index.php/thirdRobot/TaskNotifyMsg/receiveMsg';
                $retdata = $thirdRobotRecognitionService->registerSubscribe($params);
                if ($retdata && is_array($retdata) && isset($retdata['code'])) {
                    $code = intval($retdata['code']);
                    $saveRegisterArr['task_notify_url'] = $params['notifyUrl'];
                    $response_data['task'] = $retdata;
                    if ($code < 1) {
                        $saveRegisterArr['task_subscribe_time'] = $nowtime;
                    }
                }
            }
            if (!isset($deviceSet['vision_log_subscribe_time']) || $deviceSet['vision_log_subscribe_time'] < 100) {
                // v20/public/index.php/thirdRobot/VisionLogNotifyMsg/receiveMsg
                $params = array();
                $params['type'] = 'visionLog';
                $params['msgTypeCodeList']=array();
                $params['notifyUrl'] = $site_url . '/v20/public/index.php/thirdRobot/VisionLogNotifyMsg/receiveMsg';
                $retdata = $thirdRobotRecognitionService->registerSubscribe($params);
                if ($retdata && is_array($retdata) && isset($retdata['code'])) {
                    $code = intval($retdata['code']);
                    $saveRegisterArr['vision_log_notify_url'] = $params['notifyUrl'];
                    $response_data['vision_log'] = $retdata;
                    if ($code < 1) {
                        $saveRegisterArr['vision_log_subscribe_time'] = $nowtime;
                    }
                }
            }
            if (!isset($deviceSet['checkin_subscribe_time']) || $deviceSet['checkin_subscribe_time'] < 100) {
                // v20/public/index.php/thirdRobot/CheckInNotifyMsg/receiveMsg
                $params = array();
                $params['type'] = 'CheckIn';
                $params['msgTypeCodeList'] = array();
                $params['notifyUrl'] = $site_url . '/v20/public/index.php/thirdRobot/CheckInNotifyMsg/receiveMsg';
                $retdata = $thirdRobotRecognitionService->registerSubscribe($params);
                if ($retdata && is_array($retdata) && isset($retdata['code'])) {
                    $code = intval($retdata['code']);
                    $saveRegisterArr['checkin_notify_url'] = $params['notifyUrl'];
                    $response_data['checkin'] = $retdata;
                    if ($code < 1) {
                        $saveRegisterArr['checkin_subscribe_time'] = $nowtime;
                    }
                }
            }

            if (!empty($saveRegisterArr)) {
                if (!empty($response_data)) {
                    if (isset($deviceSet['response_data']) && !empty($deviceSet['response_data'])) {
                        $deviceSet['response_data'] = json_decode($deviceSet['response_data'], 1);
                        $saveRegisterArr['response_data'] = array_merge($deviceSet['response_data'], $response_data);
                        $saveRegisterArr['response_data'] = json_encode($saveRegisterArr['response_data'], JSON_UNESCAPED_UNICODE);
                    } else {
                        $saveRegisterArr['response_data'] = json_encode($response_data, JSON_UNESCAPED_UNICODE);
                    }
                }
                $thirdRobotDeviceSetDb->saveOneData(['id' => $deviceSet['id']], $saveRegisterArr);
            }
        }
        return $is_configure_right;
    }

    public function getRemoteRobotData($current = 1, $pagesize = 100, $is_self = false)
    {
        $thirdRobotRecognitionService = new ThirdRobotRecognitionService();
        $params = array();
        $params['current'] = $current;
        $params['size'] = $pagesize;
        /**
         *  设备类型： 1-掌上超声设备 2-货柜 3-服务机器人 4-送货 5-机场； 6-清洁 7-cloudia
         * $params['deviceType']=3;
         */
        $ret = $thirdRobotRecognitionService->getRoboterList($params);
        $nowtime = time();
        $thirdRobotDeviceDb = new ThirdRobotDevice();
        
        if ($ret && is_array($ret) && isset($ret['data']) && is_array($ret['data']) && isset($ret['data']['records']) && !empty($ret['data']['records'])) {
            if (!$is_self) {
                $thirdRobotDeviceDb->saveOneData(array(['id', '>', 0], ['del_time', '<', 1]), array('del_flag_time' => $nowtime));
            }
            $records = $ret['data']['records'];
            $tmpsize = $ret['data']['size']; //每页记录数
            $tmptotal = $ret['data']['total'];  //总记录数
            //$tmpcurrent=$ret['data']['current']; //当前页号
            //$pages=$ret['data']['pages']; //总页数
            $this->saveRemoteRobotData($records);
            $offsize = $pagesize * $current;
            if ($tmptotal > $offsize) {
                $current = $current + 1;
                $this->getRemoteRobotData($current, 100, true);
            }
            if($is_self){
                return true;
            }
        }
        $thirdRobotDeviceDb->saveOneData(array(['id', '>', 0], ['del_flag_time', '>', 0]), array('del_time' => $nowtime, 'del_flag_time' => 0));
        (new ThirdRobotDeviceCmdService())->jobToGetRobotsBizPoints();
        if($ret && is_array($ret) && (!isset($ret['data']) || empty($ret['data'])) && !empty($ret['message']) && $ret['code']>0){
            return array('err_msg'=>$ret['message']);
        }
        return true;
    }

    public function saveRemoteRobotData($records = array())
    {
        if (empty($records) || !is_array($records)) {
            return false;
        }
        $nowtime = time();
        $branchArr = array();
        $thirdRobotDeviceDb = new ThirdRobotDevice();
        $thirdRobotRecognitionService = new ThirdRobotRecognitionService();
        $appid=$thirdRobotRecognitionService->appid;
        foreach ($records as $vv) {
            $name = trim($vv['name']);
            $saveData = array('name' => $name);
            $robot_code = $vv['robotCode'] ? trim($vv['robotCode']) : '';   //机器人编码
            if (empty($robot_code)) {
                continue;
            }
            $saveData['appid'] =$appid;
            $saveData['robot_sku'] = $vv['sku'] ? trim($vv['sku']) : '';
            $saveData['robot_model'] = $vv['model'] ? trim($vv['model']) : '';
            $saveData['device_type'] = $vv['deviceType'] ? trim($vv['deviceType']) : 0;
            $saveData['robot_type'] = $vv['robotType'] ? trim($vv['robotType']) : '';
            $saveData['tenant_code'] = $vv['tenantCode'] ? trim($vv['tenantCode']) : '';
            $saveData['tenant_name'] = $vv['tenantName'] ? trim($vv['tenantName']) : '';
            $saveData['tenant_id'] = $vv['tenantId'] ? trim($vv['tenantId']) : 0;
            $saveData['branch_id'] = $vv['branchId'] ? trim($vv['branchId']) : 0;
            if (isset($vv['branchName'])) {
                $vv['branchName'] = trim($vv['branchName']);
            } else {
                $vv['branchName'] = '';
            }
            if (empty($vv['branchName']) && $saveData['branch_id'] && $saveData['branch_id'] > 0 && isset($branchArr[$saveData['branch_id']])) {
                $vv['branchName'] = $branchArr[$saveData['branch_id']]['name'];
            } else if (empty($vv['branchName']) && $saveData['branch_id'] && $saveData['branch_id'] > 0 && !isset($branchArr[$saveData['branch_id']])) {
                $params = array();
                $params['id'] = $saveData['branch_id'];
                $branchInfo = $thirdRobotRecognitionService->getRemoteBranchById($params);
                if ($branchInfo && is_array($branchInfo) && isset($branchInfo['data']) && is_array($branchInfo['data']) && isset($ret['data']['id'])) {
                    $branchArr[$saveData['branch_id']] = array('branch_id' => $saveData['branch_id'], 'name' => trim($ret['data']['name']));
                    $vv['branchName'] = $branchArr[$saveData['branch_id']]['name'];
                }
            }
            $saveData['branch_name'] = isset($vv['branchName']) && $vv['branchName'] ? trim($vv['branchName']) : '';
            $saveData['online_flag'] = $vv['onlineFlag'] ? trim($vv['onlineFlag']) : 0;
            $saveData['work_flag'] = $vv['workFlag'] ? trim($vv['workFlag']) : 0;
            $saveData['alarming_num'] = $vv['alarmingNum'] ? trim($vv['alarmingNum']) : 0;
            $saveData['network_flag'] = $vv['networkFlag'] ? trim($vv['networkFlag']) : '';
            $saveData['charge_flag'] = $vv['chargeFlag'] ? trim($vv['chargeFlag']) : 0;
            $saveData['network_signal'] = $vv['networkSignal'] ? trim($vv['networkSignal']) : '';
            $robot_create_time = $vv['createTime'] ? trim($vv['createTime']) : 0;   //毫秒
            $robot_create_time = $robot_create_time / 1000;
            $robot_create_time = round($robot_create_time);
            $saveData['robot_create_time'] = $robot_create_time;
            $robot_update_time = $vv['updateTime'] ? trim($vv['updateTime']) : 0;   //毫秒
            $robot_update_time = $robot_update_time / 1000;
            $robot_update_time = round($robot_update_time);
            $saveData['robot_update_time'] = $robot_update_time;
            $saveData['running_status'] = $vv['runningStatus'] ? trim($vv['runningStatus']) : 0;
            if (isset($vv['battery']) && trim($vv['battery'])) {
                $saveData['battery'] = trim($vv['battery']);
            }
            $keyArr = [
                'model', 'deviceType', 'robotType', 'tenantName', 'onlineFlag',
                'onlineFlag', 'workFlag', 'alarmingNum', 'networkFlag', 'chargeFlag',
                'networkSignal', 'runningStatus',
            ];
            $response_data = $vv;
            //$response_data = unsetWordParam($response_data, $keyArr);
            $saveData['response_data'] = json_encode($response_data, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            $oneDataObj = $thirdRobotDeviceDb->getOneData(array(['robot_code', '=', $robot_code]));
            if ($oneDataObj && !$oneDataObj->isEmpty()) {
                $saveData['update_time'] = $nowtime;
                $saveData['del_time'] = 0;
                $saveData['del_flag_time'] = 0;
                $thirdRobotDeviceDb->saveOneData(['id' => $oneDataObj['id']], $saveData);
            } else {
                $saveData['robot_code'] = $robot_code;
                $saveData['add_time'] = $nowtime;
                $saveData['del_time'] = 0;
                $saveData['del_flag_time'] = 0;
                $thirdRobotDeviceDb->addOneData($saveData);
            }
        }
        return true;
    }

    public function getRobotSelectList($where = array(), $field = true, $orderby = 'id DESC')
    {
        $thirdRobotDeviceDb = new ThirdRobotDevice();
        $dataObj = $thirdRobotDeviceDb->getListData($where, $field, $orderby, 0, 0);
        $res = array('list' => array(), 'levels_arr' => array());
        if ($dataObj && !$dataObj->isEmpty()) {
            $datas = $dataObj->toArray();
            if ($datas) {
                $res['list'] = $datas;
            }
        }
        $levels_arr = array(['levelv' => 1], ['levelv' => 2], ['levelv' => 3], ['levelv' => 4]);
        $res['levels_arr'] = $levels_arr;
        return $res;
    }

    /**
     * 获取机器人列表.
     */
    public function getRobotList($where = array(), $field = true, $page = 1, $limit = 10, $orderby = 'id DESC', $handleUserData = false)
    {
        $thirdRobotDeviceDb = new ThirdRobotDevice();
        $dataObj = $thirdRobotDeviceDb->getListData($where, $field, $orderby, $page, $limit);
        $res = array('list' => array(), 'count' => 0, 'total_limit' => $limit, 'device_type_arr' => array());
        if ($dataObj && !$dataObj->isEmpty()) {
            $datas = $dataObj->toArray();
        }
        $base_url = '/packapp/plat/';
        if(cfg('system_type') == 'village'){
            $base_url = '/packapp/village/';
        }
        if (!empty($datas)) {
            if (!empty($handleUserData)) {
                // 设备类型: 1-掌上超声 2-货柜 3-服务 4-送货 5-机场 6-清洁 7-cloudia
                $userData = [];
                $siteUrl = cfg('site_url');
                $siteUrl=rtrim($siteUrl,'/');
                $static = $siteUrl . '/v20/public/static/third_robot/robot_type/';
                $houseVillageUserBindDb=new HouseVillageUserBind();
                $room_user_type=0;
                if(is_array($handleUserData) && isset($handleUserData['pigcms_id']) && $handleUserData['pigcms_id']>0 ){
                    $whereArr=array(['pigcms_id','=',$handleUserData['pigcms_id']]);
                    $userBindObj=$houseVillageUserBindDb->getOne($whereArr);
                    if($userBindObj && !$userBindObj->isEmpty()){
                       if($userBindObj['type']==0 || $userBindObj['type']==3){
                           $room_user_type=1;
                       }elseif ($userBindObj['type']==1){
                           $room_user_type=2;
                       }
                    }
                }
                foreach ($datas as $rvv) {
                    $item = [];
                    $item['id'] = $rvv['id'];
                    $item['village_id'] = $rvv['village_id'];
                    $item['name'] = $rvv['name'];
                    $item['robot_code'] = $rvv['robot_code'];
                    $item['delivery_price'] = $rvv['delivery_price'];
                    $device_type = isset($rvv['device_type']) && $rvv['device_type'] ? intval($rvv['device_type']) : 0;
                    $robot_type = isset($rvv['robot_type']) && $rvv['robot_type'] ? trim($rvv['robot_type']) : '';
                    $item = $this->getRobotStatusText($item, $rvv);
                    if (!isset($userData[$device_type]) || empty($userData[$device_type])) {
                        $userData[$device_type] = [];
                        $userData[$device_type]['list'] = [];
                    }
                    $item['btn_info']=array();
                    $jump_url=$siteUrl.$base_url.'pages/robot/call_robot_release_order_2?robot_id='.$rvv['id'].'&call_from=village_my&village_id='.$rvv['village_id'];
                    $item['btn_info'][]=array('btn_name'=>'到我这','jump_url'=>$jump_url, 'auth' => true, 'tip' => '');
                    $jump_url=$siteUrl.$base_url.'pages/robot/call_robot_release_order?village_id='. $rvv['village_id'].'&robot_id='.$rvv['id'];
                    $item['btn_info'][]=array('btn_name'=>'跑腿配送服务','jump_url'=>$jump_url, 'auth' => true, 'tip' => '');
                    if($rvv['online_flag']==1 && isset($rvv['look_video_type']) && (($rvv['look_video_type']==1 && $room_user_type==1) || ($rvv['look_video_type']==2 && in_array($room_user_type,[1,2])))){
                        $jump_url=$siteUrl. '/wap.php?g=Wap&c=House&a=robot_look_video&robot_device_id='.$rvv['id'].'&robot_code='.$rvv['robot_code'];
                        $item['btn_info'][] = array('btn_name' => '实况直播', 'jump_url' => $jump_url, 'auth' => true, 'tip' => '');
                    } elseif ($rvv['online_flag']==1 && isset($rvv['look_video_type']) && intval($rvv['look_video_type']) > 0) {
                        $item['btn_info'][] = array('btn_name' => '实况直播', 'jump_url' => '', 'auth' => false, 'tip' => '您没有权限查看，请绑定业主或家属身份后再来试试！');
                    }
                    $userData[$device_type]['deviceTypeText'] = isset($this->device_type_arr[$device_type]) ? $this->device_type_arr[$device_type] : '未知';
                    $userData[$device_type]['device_type'] = $device_type;
                    $item['robot_img'] = $this->getRobotImg($robot_type, $static);
                    $userData[$device_type]['list'][] = $item;
                }
                $userData = array_values($userData);
                $res['list'] = $userData;
            } else {
                $houseVillageDb = new HouseVillage();
                foreach ($datas as $kk => $rvv) {
                    if (isset($rvv['response_data'])) {
                        unset($datas[$kk]['response_data']);
                    }
                    $datas[$kk]['bind_village_name'] = '';
                    if ($rvv['village_id'] > 0) {
                        $houseVillageInfo = $houseVillageDb->getOne($rvv['village_id'], 'village_id,village_name,village_address');
                        if ($houseVillageInfo && !$houseVillageInfo->isEmpty()) {
                            $datas[$kk]['bind_village_name'] = $houseVillageInfo['village_name'];
                        }
                    }
                    $datas[$kk]['del_time_str'] = '';
                    if ($rvv['del_time'] > 0) {
                        $datas[$kk]['del_time_str'] = '已删除';
                    }
                    $datas[$kk]['device_type_str'] = isset($this->device_type_arr[$rvv['device_type']]) ? $this->device_type_arr[$rvv['device_type']] : '未知';
                    $datas[$kk]['work_flag_str'] = $rvv['work_flag'] == 1 ? '执行中' : '非执行';
                    $datas[$kk]['online_flag_str'] = $rvv['online_flag'] == 1 ? '在线' : '离线';
                    $datas[$kk]['bind_village_str'] = $rvv['is_bind_village'] == 1 ? '已绑定' : '未绑定';
                    $datas[$kk]['running_status_str'] = isset($this->running_status_arr[$rvv['running_status']]) ? $this->running_status_arr[$rvv['running_status']] : '异常';
                }
                $res['list'] = $datas;
            }
            $count = $thirdRobotDeviceDb->getCount($where);
            $res['count'] = $count;
        }
        foreach ($this->device_type_arr as $kk => $vv) {
            $tmpV = array('ikey' => $kk, 'ivalue' => $vv);
            $res['device_type_arr'][] = $tmpV;
        }
        $res['work_status_arr'] = $this->work_status_arr;
        $res['device_online_arr'] = $this->device_online_arr;
        $res['is_configure_right'] = $this->checkConfigure();
        return $res;
    }

    /**
     * 获取单个机器人信息.
     */
    public function getOneDataInfo($where = array(), $field = true, $orderby = 'id DESC', $handleUserData = false, $uid = 0)
    {
        $thirdRobotDeviceDb = new ThirdRobotDevice();
        $dataObj = $thirdRobotDeviceDb->getOneData($where, $field, $orderby);
        $oneInfo = array();
        if ($dataObj && !$dataObj->isEmpty()) {
            $oneInfo = $dataObj->toArray();
            if (isset($oneInfo['response_data']) && !empty($oneInfo['response_data'])) {
                $response_data = json_decode($oneInfo['response_data'], 1);
                $oneInfo['response_data'] = $response_data;
            }
            $oneInfo['village_name']='';
            if(isset($oneInfo['village_id']) && !empty($oneInfo['village_id'])){
                $houseVillageDb=new HouseVillage();
                $houseVillageObj =$houseVillageDb->getInfo(array('village_id'=>$oneInfo['village_id']),'village_id,village_name');
                if($houseVillageObj && !$houseVillageObj->isEmpty()){
                    $oneInfo['village_name']=$houseVillageObj['village_name'];
                }
            }
            if ($handleUserData) {
                $siteUrl = cfg('site_url');
                $siteUrl = rtrim($siteUrl, '/');
                $static = $siteUrl . '/v20/public/static/third_robot/robot_type/';
                $houseVillageUserBindDb = new HouseVillageUserBind();
                $room_user_type = 0;
                if ($uid > 0) {
                    $whereArr = [];
                    $whereArr[] = ['uid', '=', $uid];
                    $whereArr[] = ['status', '=', 1];
                    $whereArr[] = ['type', 'in', [0,3]];
                    $userBindObj = $houseVillageUserBindDb->getOne($whereArr);
                    if (isset($userBindObj['pigcms_id']) && intval($userBindObj['pigcms_id']) > 0) {
                        $room_user_type = 1;
                    } else {
                        $whereArr = [];
                        $whereArr[] = ['uid', '=', $uid];
                        $whereArr[] = ['status', '=', 1];
                        $whereArr[] = ['type', '=', 1];
                        $userBindObj = $houseVillageUserBindDb->getOne($whereArr);
                        if (isset($userBindObj['pigcms_id']) && intval($userBindObj['pigcms_id']) > 0) {
                            $room_user_type = 2;
                        }
                    }
                }
                $item = [];
                $item['id'] = $oneInfo['id'];
                $item['village_id'] = $oneInfo['village_id'];
                $item['name'] = $oneInfo['name'];
                $item['robot_code'] = $oneInfo['robot_code'];
                $item['delivery_price'] = $oneInfo['delivery_price'];
                $item['call_price'] = $oneInfo['call_price'];
                $device_type = isset($oneInfo['device_type']) && $oneInfo['device_type'] ? intval($oneInfo['device_type']) : 0;
                $robot_type = isset($oneInfo['robot_type']) && $oneInfo['robot_type'] ? trim($oneInfo['robot_type']) : '';
                $item = $this->getRobotStatusText($item, $oneInfo);
                $jump_url = get_base_url('pages/robot/maps_point_list?robot_id=' . $oneInfo['id'] . '&call_from=village_my&call_from_id=' . $oneInfo['village_id']);
                $item['call_url'] = $jump_url;
                $jump_url = get_base_url('pages/robot/call_robot_release_order?village_id=' . $oneInfo['village_id'] . '&robot_id=' . $oneInfo['id']);
                $item['delivery_url'] = $jump_url;
                if ($oneInfo['online_flag'] == 1 && isset($oneInfo['look_video_type']) && (($oneInfo['look_video_type'] == 1 && $room_user_type == 1) || ($oneInfo['look_video_type'] == 2 && in_array($room_user_type, [1, 2])))) {
                    $jump_url = $siteUrl . '/wap.php?g=Wap&c=House&a=robot_look_video&robot_device_id=' . $oneInfo['id'] . '&robot_code=' . $oneInfo['robot_code'];
                    $item['video_url'] = $jump_url;
                }
                $item['device_type'] = $device_type;
                $item['robot_type'] = $robot_type;
                $item['deviceTypeText'] = isset($this->device_type_arr[$device_type]) ? $this->device_type_arr[$device_type] : '未知';
                $item['robot_img'] = $this->getRobotImg($robot_type, $static);
                $oneInfo = $item;
            }
        }
        return $oneInfo;
    }
    public function saveOneRobotInfo($where = array(), $saveData = array())
    {
        $thirdRobotDeviceDb = new ThirdRobotDevice();
        $ret = $thirdRobotDeviceDb->saveOneData($where, $saveData);
        return $ret;
    }
    /**
     * 查询设备登录信息
     * $robot_device_id 是 third_robot_device id
     */
    public function getRobotLoginInfo($robot_code = '', $robot_device_id = 0)
    {
        $thirdRobotRecognitionService = new ThirdRobotRecognitionService();
        $paramInfo = [
            'robotCode' => $robot_code,
        ];
        $data = [];
        $queryRet = $thirdRobotRecognitionService->queryRobotLoginInfo($paramInfo);
        if ($queryRet && is_array($queryRet) && isset($queryRet['data']) && is_array($queryRet['data']) && isset($queryRet['data']['accountId'])) {
            $thirdRobotDeviceDb = new ThirdRobotDevice();
            $whereArr = array('robot_code' => $robot_code);
            if ($robot_device_id > 0) {
                $whereArr = array('id' => $robot_device_id);
            }
            $robot_login_lnfo = $queryRet['data'];
            $robot_login_lnfo['got_time'] = time();
            $robot_login_lnfo = json_encode($robot_login_lnfo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $saveData = array('robot_login_lnfo' => $robot_login_lnfo);
            $ret = $thirdRobotDeviceDb->saveOneData($whereArr, $saveData);
            $data = $queryRet['data'];
            
        }
        $where = [
            'robot_code' => $robot_code,
        ];
        if ($robot_device_id > 0) {
            $where['id'] = $robot_device_id;
        }
        $thirdRobotDeviceDb = new ThirdRobotDevice();
        $data['robot'] = $thirdRobotDeviceDb->getOneData($where, 'id, name, robot_code', 'id DESC');
        return $data;
    }
    
    public function getOneDetailInfo($where = array(), $xtype = 0, $orderby = 'id DESC')
    {
        $thirdRobotDeviceDb = new ThirdRobotDevice();
        $dataObj = $thirdRobotDeviceDb->getOneData($where, '*', $orderby);
        $oneInfo = array();
        if ($dataObj && !$dataObj->isEmpty()) {
            $oneInfo = $dataObj->toArray();
            if ($xtype > 0) {
                unset($oneInfo['response_data']);
            }
            if (isset($oneInfo['response_data']) && !empty($oneInfo['response_data'])) {
                $response_data = json_decode($oneInfo['response_data'], 1);
                $response_data['softwareVersion'] = $response_data['softwareVersion'] ? $response_data['softwareVersion'] : '';
                $response_data['firmwareVersion'] = $response_data['firmwareVersion'] ? $response_data['firmwareVersion'] : '';
                $response_data['rcuChargeFlagStr'] = $response_data['rcuChargeFlag'] && $response_data['rcuChargeFlag'] == 1 ? '充电中' : '未充电';
                $response_data['emergencyStopStr'] = $response_data['emergencyStop'] && $response_data['emergencyStop'] == 1 ? '开' : '关';
                $oneInfo['response_data'] = $response_data;
            }
            $oneInfo['bind_village_time'] = $oneInfo['bind_village_time'] > 0 ? date('Y-m-d H:i:s', $oneInfo['bind_village_time']) : '';
            $oneInfo['bind_village_name'] = '';
            if ($oneInfo['village_id'] > 0) {
                $houseVillageDb = new HouseVillage();
                $houseVillageInfo = $houseVillageDb->getOne($oneInfo['village_id'], 'village_id,village_name,village_address');
                if ($houseVillageInfo && !$houseVillageInfo->isEmpty()) {
                    $oneInfo['bind_village_name'] = $houseVillageInfo['village_name'];
                }
            }
            $oneInfo['device_type_str'] = isset($this->device_type_arr[$oneInfo['device_type']]) ? $this->device_type_arr[$oneInfo['device_type']] : '未知';
            $oneInfo['work_flag_str'] = $oneInfo['work_flag'] == 1 ? '执行中' : '非执行';
            $oneInfo['online_flag_str'] = $oneInfo['online_flag'] == 1 ? '在线' : '离线';
            $oneInfo['bind_village_str'] = $oneInfo['is_bind_village'] == 1 ? '已绑定' : '未绑定';
            $oneInfo['charge_flag_str'] = $oneInfo['charge_flag'] == 1 ? '充电中' : '未充电';
            $oneInfo['running_status_str'] = isset($this->running_status_arr[$oneInfo['running_status']]) ? $this->running_status_arr[$oneInfo['running_status']] : '异常';
            $oneInfo['robot_create_time'] = $oneInfo['robot_create_time'] > 0 ? date('Y-m-d H:i:s', $oneInfo['robot_create_time']) : '';
            $oneInfo['robot_update_time'] = $oneInfo['robot_update_time'] > 0 ? date('Y-m-d H:i:s', $oneInfo['robot_update_time']) : '';

        }
        return $oneInfo;
    }

    public function robotBindVillageOpt($whereArr = array(), $xtype = 0, $village_id = 0)
    {
        $field = 'id,village_id,is_bind_village,bind_village_time,branch_id,branch_name,name,robot_code,alarm_notice_set';
        $robot = $this->getOneDataInfo($whereArr, $field);
        if (empty($robot)) {
            throw new \think\Exception("机器人信息不存在！");
        }
        $nowtime = time();
        $thirdRobotDeviceDb = new ThirdRobotDevice();
        $thirdRobotAlarmNoticeSetDb =new ThirdRobotAlarmNoticeSet();
        $ret = '';
        $not_bind_village_count = 0;
        if ($xtype == 1) {
            //绑定
            $saveData = array('village_id' => $village_id, 'is_bind_village' => 1, 'bind_village_time' => $nowtime);
            $ret = $thirdRobotDeviceDb->saveOneData(['id' => $robot['id']], $saveData);
            // 一键同步人脸
            (new ThirdRobotDeviceCmdService())->robotOneClickSync($robot['robot_code']);
        } else if ($xtype == 2) {
            //解绑
            $saveData = array('village_id' => 0, 'is_bind_village' => 0, 'bind_village_time' => $nowtime);
            $ret = $thirdRobotDeviceDb->saveOneData(['id' => $robot['id']], $saveData);
        }
        if($robot['alarm_notice_set']){
            $alarm_notice_set=json_decode($robot['alarm_notice_set'],true);
            $new_alarm_notice_set=array();
            if($alarm_notice_set && is_array($alarm_notice_set)){
                foreach ($alarm_notice_set as $avv){
                    if(isset($avv['id_type']) && $avv['id_type']==1){
                        //在切换绑定小区时  删除掉已绑定小区工作人员
                        $delSaveArr = array('del_time' => $nowtime);
                        $thirdRobotAlarmNoticeSetDb->saveOneData(array('id'=>$avv['id']), $delSaveArr);
                    }else{
                        $new_alarm_notice_set[]=$avv;
                    }
                }
            }
            $alarm_notice_set='';
            if(!empty($new_alarm_notice_set)){
                $alarm_notice_set=json_encode($new_alarm_notice_set,JSON_UNESCAPED_UNICODE);
            }
            $save_alarm_notice_set = array('alarm_notice_set' => $alarm_notice_set);
            $thirdRobotDeviceDb->saveOneData(['id' => $robot['id']], $save_alarm_notice_set);
        }
        if ($robot['branch_id'] > 0) {
            $whereArrTmp = array();
            $whereArrTmp[] = ['branch_id', '=', $robot['branch_id']];
            $whereArrTmp[] = ['del_time', '<', 1];
            $whereArrTmp[] = ['is_bind_village', '<', 1];
            $not_bind_village_count = $thirdRobotDeviceDb->getCount($whereArrTmp);
            $not_bind_village_count = $not_bind_village_count > 0 ? $not_bind_village_count : 0;
        }
        $retTemp['robot'] = $robot;
        $retTemp['not_bind_village_count'] = $not_bind_village_count;
        $retTemp['xtype'] = $xtype;
        $retTemp['village_id'] = $village_id;
        $retTemp['ret'] = $ret;
        return $retTemp;
    }

    public function robotBranchBindVillageOpt($branch_id = 0, $village_id = 0)
    {

        if ($branch_id < 0 || $village_id < 0) {
            return $branch_id;
        }
        $thirdRobotDeviceDb = new ThirdRobotDevice();
        $whereArrTmp = array();
        $whereArrTmp[] = ['branch_id', '=', $branch_id];
        $whereArrTmp[] = ['del_time', '<', 1];
        $whereArrTmp[] = ['is_bind_village', '<', 1];
        $nowtime = time();
        $rootDataObj = $thirdRobotDeviceDb->getListData($whereArrTmp, 'id,robot_code', 'id DESC', 0, 0);
        if ($rootDataObj && !$rootDataObj->isEmpty()) {
            $rootDatas = $rootDataObj->toArray();
            $saveData = array('village_id' => $village_id, 'is_bind_village' => 1, 'bind_village_time' => $nowtime);
            $ret = $thirdRobotDeviceDb->saveOneData($whereArrTmp, $saveData);
            $thirdRobotDeviceCmdService = new ThirdRobotDeviceCmdService();
            foreach ($rootDatas as $vvv) {
                // 一键同步人脸
                $thirdRobotDeviceCmdService->robotOneClickSync($vvv['robot_code']);
            }
        }
        return $branch_id;
    }
    /**
     * 更新机器人状态
     ***/
    public function getRobotRunningInfo(){
        $thirdRobotDeviceDb = new ThirdRobotDevice();
        $whereArr=array();
        $whereArr[]=['id','>',0];
        $whereArr[]=['del_time','<',1];
        $field='id,village_id,robot_code';
        $nowtime=time();
        $dataObj = $thirdRobotDeviceDb->getListData($whereArr, $field);
        if ($dataObj && !$dataObj->isEmpty()) {
            $datas = $dataObj->toArray();
            $thirdRobotRecognitionService=new ThirdRobotRecognitionService();
            foreach ($datas as $dvv) {
                $paramInfo = [
                    'robotCode' => $dvv['robot_code'],
                ];
                $robotRunInfo = $thirdRobotRecognitionService->getRobotInfo($paramInfo);
                if ($robotRunInfo && is_array($robotRunInfo) && isset($robotRunInfo['data']) && is_array($robotRunInfo['data'])) {
                    $runInfoArr = $robotRunInfo['data'];
                    $curLocation = isset($runInfoArr['curLocation']) && $runInfoArr['curLocation'] ? json_decode($runInfoArr['curLocation'], true) : '';
                    $updateInfo = array();
                    if (isset($runInfoArr['networkFlag'])) {
                        $updateInfo['network_flag'] = trim($runInfoArr['networkFlag']);
                    }
                    if (isset($runInfoArr['networkSignal'])) {
                        $updateInfo['network_signal'] = trim($runInfoArr['networkSignal']);
                    }
                    if (isset($runInfoArr['battery'])) {
                        $updateInfo['battery'] = trim($runInfoArr['battery']);
                    }
                    if (isset($runInfoArr['onlineFlag'])) {
                        $updateInfo['online_flag'] = trim($runInfoArr['onlineFlag']);
                    }
                    if (isset($runInfoArr['workFlag'])) {
                        $updateInfo['work_flag'] = trim($runInfoArr['workFlag']);
                    }
                    if (isset($runInfoArr['chargeFlag'])) {
                        $updateInfo['charge_flag'] = trim($runInfoArr['chargeFlag']);
                    }
                    if (isset($loc['lng']) && isset($loc['lat']) && trim($loc['lng']) && trim($loc['lat'])) {
                        $updateInfo['lng'] = trim($loc['lng']);
                        $updateInfo['lat'] = trim($loc['lat']);
                        $report_time = isset($loc['reportTime']) && $loc['reportTime'] ? round($loc['reportTime'] / 1000) : $nowtime;
                        $updateInfo['report_time'] = $report_time;
                    } elseif (isset($curLocation['lng']) && isset($curLocation['lat']) && trim($curLocation['lng']) && trim($curLocation['lat'])) {
                        $updateInfo['lng'] = trim($curLocation['lng']);
                        $updateInfo['lat'] = trim($curLocation['lat']);
                        $report_time = isset($curLocation['reportTime']) && $curLocation['reportTime'] ? round($curLocation['reportTime'] / 1000) : $nowtime;
                        $updateInfo['report_time'] = $report_time;
                    }
                    $updateInfo['run_info'] = json_encode($runInfoArr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $updateInfo['get_info_time'] = $nowtime;
                    $updateInfo['update_time'] = $nowtime;
                    $thirdRobotDeviceDb->saveOneData(['id' => $dvv['id']], $updateInfo);
                }
            }
        }
        return true;
    }
    /**
     * 计划任务拉3天前的任务
    ***/
    public function getPerRoboterRemoteHistoryTask(){
        $thirdRobotDeviceDb = new ThirdRobotDevice();
        $whereArr=array();
        $whereArr[]=['id','>',0];
        $whereArr[]=['del_time','<',1];
        $field='id,village_id,robot_code';
        $dataObj = $thirdRobotDeviceDb->getListData($whereArr, $field);
        if ($dataObj && !$dataObj->isEmpty()) {
            $datas = $dataObj->toArray();
            $nowtime=time();
            $endTime=($nowtime+300)*1000;
            $startTime=($nowtime-3*24*3600)*1000;
            $params=array('startTime'=>$startTime,'endTime'=>$endTime);
            $thirdRobotRecognitionService=new ThirdRobotRecognitionService();
            foreach ($datas as $dvv){
                /*
                $paramInfo = [
                    'robotCode' => $dvv['robot_code'],
                ];
                $robotRunInfo=$thirdRobotRecognitionService->getRobotInfo($paramInfo);
                if($robotRunInfo && is_array($robotRunInfo) && isset($robotRunInfo['data']) && is_array($robotRunInfo['data'])){
                    $runInfoArr=$robotRunInfo['data'];
                    $curLocation = isset($runInfoArr['curLocation']) && $runInfoArr['curLocation'] ? json_decode($runInfoArr['curLocation'], true) : '';
                    $updateInfo=array();
                    if(isset($runInfoArr['networkFlag'])){
                        $updateInfo['network_flag']=trim($runInfoArr['networkFlag']);
                    }
                    if(isset($runInfoArr['networkSignal'])){
                        $updateInfo['network_signal']=trim($runInfoArr['networkSignal']);
                    }
                    if(isset($runInfoArr['battery'])){
                        $updateInfo['battery']=trim($runInfoArr['battery']);
                    }
                    if(isset($runInfoArr['onlineFlag'])){
                        $updateInfo['online_flag']=trim($runInfoArr['onlineFlag']);
                    }
                    if(isset($runInfoArr['workFlag'])){
                        $updateInfo['work_flag']=trim($runInfoArr['workFlag']);
                    }
                    if(isset($runInfoArr['chargeFlag'])){
                        $updateInfo['charge_flag']=trim($runInfoArr['chargeFlag']);
                    }
                    if (isset($loc['lng']) && isset($loc['lat']) && trim($loc['lng']) && trim($loc['lat'])) {
                        $updateInfo['lng'] = trim($loc['lng']);
                        $updateInfo['lat'] = trim($loc['lat']);
                        $report_time = isset($loc['reportTime']) && $loc['reportTime'] ? round($loc['reportTime'] / 1000) : $nowtime;
                        $updateInfo['report_time'] = $report_time;
                    } elseif (isset($curLocation['lng']) && isset($curLocation['lat']) && trim($curLocation['lng']) && trim($curLocation['lat'])) {
                        $updateInfo['lng'] = trim($curLocation['lng']);
                        $updateInfo['lat'] = trim($curLocation['lat']);
                        $report_time = isset($curLocation['reportTime']) && $curLocation['reportTime'] ? round($curLocation['reportTime'] / 1000) : $nowtime;
                        $updateInfo['report_time'] = $report_time;
                    }
                    $updateInfo['run_info'] = json_encode($runInfoArr, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                    $updateInfo['get_info_time'] = $nowtime;
                    $updateInfo['update_time'] = $nowtime;
                    $thirdRobotDeviceDb->saveOneData(['id' => $dvv['id']], $updateInfo);
                }
                */
                $this->getRemoteRoboterHistoryTask($dvv['robot_code'],1,100,$params);
            }
        }
        return true;
    }
    public function getRemoteRoboterHistoryTask($robot_code = '', $current = 1, $pagesize = 100, $params = array())
    {
        $thirdRobotRecognitionService = new ThirdRobotRecognitionService();
        if ($robot_code) {
            $params['robotCode'] = $robot_code;
        }
        $params['current'] = $current;
        $params['size'] = $pagesize;
        $thirdRobotDeviceDb = new ThirdRobotDevice();
        $robotInfo = $thirdRobotDeviceDb->getOneData(['robot_code' => $robot_code], 'id,robot_code,village_id,is_bind_village,bind_village_time,del_time');
        if ($robotInfo && !$robotInfo->isEmpty()) {
            $robotInfo = $robotInfo->toArray();
        } else {
            $robotInfo = array();
        }
        $ret = $thirdRobotRecognitionService->getRoboterHistoryTask($params);
        $nowtime = time();
        if ($ret && is_array($ret) && isset($ret['data']) && is_array($ret['data']) && isset($ret['data']['records']) && !empty($ret['data']['records'])) {
            $records = $ret['data']['records'];
            $tmpsize = $ret['data']['size']; //每页记录数
            $tmptotal = $ret['data']['total'];  //总记录数
            //$tmpcurrent=$ret['data']['current']; //当前页号
            //$pages=$ret['data']['pages']; //总页数
            $this->saveRemoteRoboterHistoryTask($records, $robotInfo);
            $offsize = $pagesize * $current;
            if ($tmptotal > $offsize) {
                $current = $current + 1;
                $this->getRemoteRoboterHistoryTask($robot_code, $current, $pagesize, $params);
            }
            return true;
        }
        return true;
    }

    public function saveRemoteRoboterHistoryTask($records = array(), $robotInfo = array())
    {
        $nowtime = time();
        $thirdRobotHistoryTaskDb = new ThirdRobotHistoryTask();
        foreach ($records as $vv) {
            $task_id = trim($vv['id']);
            $task_name = $vv['taskName'] ? trim($vv['taskName']) : '';
            $task_instance_id = $vv['taskInstanceId'] ? trim($vv['taskInstanceId']) : '';
            $saveData = array();
            $task_id = !empty($task_id) ? trim($task_id) : '';   //任务日志id
            if (empty($task_id) || empty($task_instance_id)) {
                continue;
            }
            $saveData['task_name'] = $task_name;
            $saveData['task_instance_id'] = $task_instance_id;
            $saveData['task_type'] = isset($vv['taskType']) && $vv['taskType'] ? trim($vv['taskType']) : '';
            $saveData['robot_code'] = $vv['robotCode'] ? trim($vv['robotCode']) : '';
            $saveData['robot_type'] = isset($vv['robotType']) && $vv['robotType'] ? trim($vv['robotType']) : '';
            $saveData['robot_name'] = $vv['robotName'] ? trim($vv['robotName']) : '';

            $saveData['source'] = $vv['source'] ? trim($vv['source']) : '';
            $saveData['status'] = $vv['status'] ? trim($vv['status']) : -1;
            $start_time = $vv['startTimestamp'] ? trim($vv['startTimestamp']) : 0;   //毫秒
            $start_time = $start_time / 1000;
            $start_time = round($start_time);
            $saveData['start_time'] = $start_time;
            $end_time = $vv['endTimestamp'] ? trim($vv['endTimestamp']) : 0;   //毫秒
            $end_time = $end_time / 1000;
            $end_time = round($end_time);
            $saveData['end_time'] = $end_time;
            $saveData['response_data'] = json_encode($vv, JSON_UNESCAPED_UNICODE);
            $idd = 0;
            $whereArr = array();
            if ($task_instance_id) {
                $whereArr[] = array('task_instance_id', '=', $task_instance_id);
            }
            if ($task_id) {
                $whereArr[] = array('task_id', '=', $task_id);
            }
            $oneDataObj = $thirdRobotHistoryTaskDb->getOneData($whereArr);
            if ($oneDataObj && !$oneDataObj->isEmpty()) {
                $saveData['update_time'] = $nowtime;
                $idd = $oneDataObj['id'];
                $thirdRobotHistoryTaskDb->saveOneData(['id' => $oneDataObj['id']], $saveData);
            } else {
                $saveData['task_id'] = $task_id;
                $saveData['add_time'] = $nowtime;
                $idd = $thirdRobotHistoryTaskDb->addOneData($saveData);
            }
            $saveData['task_id'] = $task_id;
            $saveData['id'] = $idd;
            //绑定了小区同步小巡检数据
            $task_type=$saveData['task_type'] ? intval($saveData['task_type']):0;
            if ($robotInfo && $robotInfo['is_bind_village'] > 0 && $robotInfo['village_id'] > 0 && $robotInfo['bind_village_time'] <= $start_time && $task_type==28) {
                $this->save_wisdom_qrcode_cate($robotInfo, $saveData);
            }
        }
        return true;
    }

    public function save_wisdom_qrcode_cate($robotInfo = array(), $taskData = array())
    {
        $wisdomQrcodeCate = new WisdomQrcodeCate();
        $saveCateData = array();
        $nowtime = time();
        $saveCateData['cate_name'] = $taskData['task_name'];
        $saveCateData['village_id'] = $robotInfo['village_id'];
        $saveCateData['from_type'] = 1;
        $saveCateData['robot_code'] = $taskData['robot_code'];
        $saveCateData['task_instance_id'] = $taskData['task_instance_id'];
        $saveCateData['task_type'] = $taskData['task_type'];
        $whereArr = array();
        $whereArr[] = array('village_id', '=', $robotInfo['village_id']);
        $whereArr[] = array('robot_code', '=', $taskData['robot_code']);
        $whereArr[] = array('task_instance_id', '=', $taskData['task_instance_id']);
        $tmpQrcodeCate = $wisdomQrcodeCate->getFind($whereArr);
        $cate_id = 0;
        if ($tmpQrcodeCate && !$tmpQrcodeCate->isEmpty()) {
            $cate_id = $tmpQrcodeCate['id'];
            $wisdomQrcodeCate->saveOneData(['id' => $tmpQrcodeCate['id']], $saveCateData);
        } else {
            $saveCateData['add_time'] = $taskData['start_time'] > 0 ? $taskData['start_time'] : $nowtime;
            $cate_id = $wisdomQrcodeCate->addOneData($saveCateData);
        }
        if ($cate_id > 0) {
            $details = $this->getRobotRemoteTaskDetails($taskData['task_instance_id'], $taskData['id'], $taskData['task_id'], true);
            if ($details) {
                $wisdomQrcodeDb = new WisdomQrcode();
                $wisdomQrcodePositionIndexDb = new WisdomQrcodePositionIndex();
                foreach ($details as $qvv) {
                    $wisdom_qrcode = array();
                    $wisdom_Position = array();
                    $wisdom_Position['point_name'] = $qvv['point_name'];
                    $wisdom_qrcode['num_id'] = 9;
                    $wisdom_qrcode['name'] = $qvv['point_name'];
                    $add_time = $qvv['create_time'];
                    if ($add_time < 100 && $qvv['start_time'] > 100) {
                        $add_time = $qvv['start_time'];
                    } elseif ($add_time < 100 && $qvv['start_time'] <= 100) {
                        $add_time = $nowtime;
                    }
                    $wisdom_Position['cate_id'] = $cate_id;
                    $wisdom_Position['village_id'] = $robotInfo['village_id'];
                    $wisdom_qrcode['cate_id'] = $cate_id;
                    $wisdom_qrcode['task_node_id'] = $qvv['id'];
                    $wisdom_qrcode['from_type'] = 1;
                    $wisdom_qrcode['robot_code'] = $taskData['robot_code'];
                    $wisdom_qrcode['task_instance_id'] = $qvv['task_instance_id'];
                    $wisdom_qrcode['map_code_point'] = $qvv['map_code_point'];
                    $wisdom_qrcode['map_point'] = $qvv['point'];
                    $wisdom_qrcode['task_type'] = $taskData['task_type'];
                    
                    $wisdom_Position['task_instance_id'] = $taskData['task_instance_id'];
                    $wisdom_Position['task_node_id'] = $qvv['id'];
                    $wisdom_Position['from_type'] = 1;
                    $wisdom_Position['robot_code'] = $taskData['robot_code'];
                    $wisdom_Position['add_time'] = $add_time;
                    $wisdom_Position['update_time'] = $qvv['end_time'];

                    $whereArr = array();
                    $whereArr[] = array('cate_id', '=', $cate_id);
                    $whereArr[] = array('task_node_id', '=', $wisdom_qrcode['task_node_id']);
                    $whereArr[] = array('task_instance_id', '=', $taskData['task_instance_id']);
                    $tmpQrcode = $wisdomQrcodeDb->getFind($whereArr);
                    if ($tmpQrcode && !$tmpQrcode->isEmpty()) {
                        $wisdomQrcodeDb->saveOneData(['id' => $tmpQrcode['id']], $wisdom_qrcode);
                    } else {
                        $wisdom_qrcode['add_time'] = $add_time;
                        $wisdomQrcodeDb->addOneData($wisdom_qrcode);
                    }
                    $wisdom_Position['status'] = 0;
                    if (in_array($qvv['status'], array(3, 4, 5))) {
                        $wisdom_Position['status'] = 1;
                    }
                    $wisdom_Position['extra_data'] = json_encode($qvv, JSON_UNESCAPED_UNICODE);
                    $wherePosition = array();
                    $wherePosition[] = array('cate_id', '=', $cate_id);
                    $wherePosition[] = array('village_id', '=', $robotInfo['village_id']);
                    $wherePosition[] = array('task_node_id', '=', $qvv['id']);
                    $wherePosition[] = array('task_instance_id', '=', $taskData['task_instance_id']);
                    $tmpPosition = $wisdomQrcodePositionIndexDb->getFind($wherePosition);
                    fdump_api([$tmpPosition], '00thirdRobotLog', 1);
                    if ($tmpPosition && !$tmpPosition->isEmpty()) {
                        $wisdomQrcodePositionIndexDb->editFind(['id' => $tmpPosition['id']], $wisdom_Position);
                    } else {
                        fdump_api([$wisdom_Position], '00thirdRobotLog', 1);
                        $wisdomQrcodePositionIndexDb->addFind($wisdom_Position);
                    }
                }
            }
        }
        return true;
    }

    public function getThirdRobotTaskList($whereArr, $field = '*', $page = 0, $limit = 10, $orderby = 'start_time desc')
    {
        $thirdRobotHistoryTaskDb = new ThirdRobotHistoryTask();
        $dataObj = $thirdRobotHistoryTaskDb->getListData($whereArr, $field, $orderby, $page, $limit);
        $res = array('list' => array(), 'count' => 0, 'total_limit' => $limit, 'device_type_arr' => array());
        if ($dataObj && !$dataObj->isEmpty()) {
            $datas = $dataObj->toArray();
            if ($datas) {
                foreach ($datas as $kk => $rvv) {
                    if (isset($rvv['response_data'])) {
                        unset($datas[$kk]['response_data']);
                    }
                    $datas[$kk]['status_str'] = isset($this->robot_task_status_arr[$rvv['status']]) ? $this->robot_task_status_arr[$rvv['status']] : '未知';
                    $datas[$kk]['start_time_str'] = $rvv['start_time'] > 0 ? date('Y-m-d H:i:s', $rvv['start_time']) : '';
                    $datas[$kk]['end_time_str'] = $rvv['end_time'] > 0 ? date('Y-m-d H:i:s', $rvv['end_time']) : '';
                }
                $res['list'] = $datas;
            }
            $count = $thirdRobotHistoryTaskDb->getCount($whereArr);
            $res['count'] = $count;
        }
        $res['is_configure_right'] = $this->checkConfigure();
        return $res;
    }

    public function getRobotRemoteTaskDetails($task_instance_id = '', $id = 0, $task_id = 0, $is_return = false, $extraParams = [])
    {
        $thirdRobotRecognitionService = new ThirdRobotRecognitionService();
        $params = array();
        if (!empty($task_instance_id)) {
            $params['taskInstanceId'] = $task_instance_id;
        } else {
            $params['id'] = $task_id;
        }
        $isRobotCall = isset($extraParams['isRobotCall']) ? $extraParams['isRobotCall'] : false;
        $thirdRobotHistoryTaskDb = new ThirdRobotHistoryTask();
        if (!$isRobotCall) {
            $whereArr = array();
            if ($id > 0) {
                $whereArr[] = array('id', '=', $id);
            }
            if (!empty($task_instance_id)) {
                $whereArr[] = array('task_instance_id', '=', $task_instance_id);
            } else {
                $whereArr[] = array('task_id', '=', $task_id);
            }
            $tmpTask = $thirdRobotHistoryTaskDb->getOneData($whereArr);
            if ($tmpTask && !$tmpTask->isEmpty()) {
                $tmpTask = $tmpTask->toArray();
            } else {
                $tmpTask = array();
            }
            if (empty($tmpTask)) {
                if ($is_return) {
                    return array();
                }
                throw new \think\Exception("机器人此任务信息不存在！");
            }
        } else {
            $robot_code = isset($extraParams['robot_code']) ? $extraParams['robot_code'] : '';
            $tmpTask = [
                'robot_code' => $robot_code,
            ];
        }
        $ret = $thirdRobotRecognitionService->getRoboterTaskTaskDetails($params);
        $returnArr = array();
        if ($ret && is_array($ret) && isset($ret['data']) && is_array($ret['data']) && !empty($ret['data'])) {
            $dataJson = json_encode($ret['data'], JSON_UNESCAPED_UNICODE);
            $saveArr = array('task_details' => $dataJson);
            if (!empty($whereArr)) {
                $thirdRobotHistoryTaskDb->saveOneData($whereArr, $saveArr);
            }
            $returnArr = $this->robotTaskDetailsDataHandle($ret['data'], $tmpTask);
        }
        return $returnArr;
    }

    public function robotTaskDetailsDataHandle($details = array(), $tmpTask = array())
    {
        $returnArr = array();
        if (empty($details) || !is_array($details)) {
            return $returnArr;
        }
        $thirdRobotHistoryTaskDetail = new ThirdRobotHistoryTaskDetail();
        $statusArr = array('-1' => '未执行', '0' => '未开始', '1' => '抵达中', '2' => '异常挂起', '3' => '异常结束', '4' => '成功完成', '5' => '取消结束', '6' => '暂停中', '7' => '已抵达');
        foreach ($details as $pvv) {
            $taskInstanceId = isset($pvv['taskInstanceId']) && $pvv['taskInstanceId'] ? trim($pvv['taskInstanceId']) : '';
            $tmpArr = array();
            $tmpDetail = array();
            $tmpArr['id'] = !empty($pvv['id']) ? trim($pvv['id']) : '';
            $task_node_id = $tmpArr['id'];
            if (isset($tmpTask['task_id']) && $tmpTask['task_id']) {
                $tmpDetail['task_id'] = $tmpTask['task_id'];
            }
            $tmpDetail['robot_code'] = $tmpTask['robot_code'];
            $tmpArr['task_instance_id'] = $taskInstanceId;
            $tmpDetail['task_instance_id'] = $tmpArr['task_instance_id'];
            $tmpArr['sort_no'] = !empty($pvv['sortNo']) ? trim($pvv['sortNo']) : '';
            $tmpArr['point_name'] = !empty($pvv['pointName']) ? trim($pvv['pointName']) : '';
            $tmpDetail['point_name'] = $tmpArr['point_name'];
            $tmpArr['point'] = !empty($pvv['point']) ? trim($pvv['point']) : '';
            $tmpDetail['point'] = $tmpArr['point'];
            $tmpArr['point_type'] = !empty($pvv['pointType']) ? trim($pvv['pointType']) : '';
            $tmpDetail['point_type'] = $tmpArr['point_type'];
            $startTime = $pvv['startTime'] > 0 ? $pvv['startTime'] / 1000 : 0;
            $startTime = round($startTime);
            $tmpDetail['start_time'] = $startTime;
            $tmpArr['start_time'] = $startTime;
            $tmpArr['start_time_str'] = $startTime > 0 ? date('Y-m-d H:i:s', $startTime) : '';
            $arrivalTime = $pvv['arrivalTime'] > 0 ? $pvv['arrivalTime'] / 1000 : 0;
            $arrivalTime = round($arrivalTime);
            $tmpDetail['arrival_time'] = $arrivalTime;
            $tmpArr['arrival_time_str'] = $arrivalTime > 0 ? date('Y-m-d H:i:s', $arrivalTime) : '';
            $endTime = $pvv['endTime'] > 0 ? $pvv['endTime'] / 1000 : 0;
            $endTime = round($endTime);
            $tmpDetail['end_time'] = $endTime;
            $tmpArr['end_time'] = $endTime;
            $tmpArr['end_time_str'] = $endTime > 0 ? date('Y-m-d H:i:s', $endTime) : '';
            $updateTime = $pvv['updateTime'] > 0 ? $pvv['updateTime'] / 1000 : 0;
            $updateTime = round($updateTime);
            $tmpDetail['update_time'] = $updateTime;
            $tmpArr['update_time'] = $updateTime;
            $tmpArr['update_time_str'] = $updateTime > 0 ? date('Y-m-d H:i:s', $updateTime) : '';
            $createTime = $pvv['createTime'] > 0 ? $pvv['createTime'] / 1000 : 0;
            $createTime = round($createTime);
            $tmpDetail['create_time'] = $createTime;
            $tmpArr['create_time'] = $createTime;
            $tmpArr['create_time_str'] = $createTime > 0 ? date('Y-m-d H:i:s', $createTime) : '';

            $tmpArr['targetPointFlag'] = !empty($pvv['targetPointFlag']) ? trim($pvv['targetPointFlag']) : 0;
            $tmpDetail['target_point_flag'] = $tmpArr['targetPointFlag'];
            $tmpArr['target_point_flag'] = $tmpArr['targetPointFlag'] > 0 ? '目标点' : '非目标点';
            $tmpArr['target_point_name'] = !empty($pvv['targetPointName']) ? trim($pvv['targetPointName']) : '';
            $tmpDetail['target_point_name'] = $tmpArr['target_point_name'];
            $tmpArr['map_code'] = !empty($pvv['mapCode']) ? trim($pvv['mapCode']) : '';
            $tmpDetail['map_code'] = $tmpArr['map_code'];
            $tmpArr['map_name'] = !empty($pvv['mapName']) ? trim($pvv['mapName']) : '';
            $tmpDetail['map_name'] = $tmpArr['map_name'];
            $tmpArr['map_floor'] = !empty($pvv['mapFloor']) ? trim($pvv['mapFloor']) : '';
            $tmpArr['status'] = isset($pvv['status']) ? trim($pvv['status']) : 0;
            $tmpDetail['status'] = $tmpArr['status'];
            $tmpArr['status_str'] = isset($statusArr[$tmpArr['status']]) ? $statusArr[$tmpArr['status']] : '未知';
            $tmpArr['error_code'] = !empty($pvv['errorCode']) ? trim($pvv['errorCode']) : '';
            $tmpArr['error_message'] = !empty($pvv['errorMessage']) ? trim($pvv['errorMessage']) : '';
            $tmpArr['actions'] = !empty($pvv['actions']) ? $pvv['actions'] : '';
            $tmpDetail['extra_data'] = json_encode($pvv, JSON_UNESCAPED_UNICODE);
            $tmpDetail['map_code_point'] = '';
            if (!empty($tmpDetail['point']) && strpos($tmpDetail['point'], ',')) {
                $pointArr = explode(',', $tmpDetail['point']);
                foreach ($pointArr as $kk => $dvv) {
                    $dvv = round($dvv);
                    $dvv = $dvv * 1;
                    $pointArr[$kk] = $dvv;
                }
                $pointStr = implode(',', $pointArr);
                $tmpDetail['map_code_point'] = $tmpArr['map_code'] . '_' . $pointStr;
            }
            $tmpArr['map_code_point'] = $tmpDetail['map_code_point'];
            $detail_id = 0;
            if (!empty($task_node_id)) {
                $whereDetail = array();
                $whereDetail[] = array('task_node_id', '=', $task_node_id);
                $whereDetail[] = array('task_instance_id', '=', $taskInstanceId);
                $taskdetail = $thirdRobotHistoryTaskDetail->getOneData($whereDetail);
                if ($taskdetail && !$taskdetail->isEmpty()) {
                    $detail_id = $taskdetail['id'];
                }
            }
            if ($detail_id > 0) {
                $taskdetail = $thirdRobotHistoryTaskDetail->saveOneData(['id' => $detail_id], $tmpDetail);
            } else {
                $tmpDetail['task_node_id'] = $task_node_id;
                $taskdetail = $thirdRobotHistoryTaskDetail->addOneData($tmpDetail);
            }
            $returnArr[] = $tmpArr;
        }
        return $returnArr;
    }

    public function getOneRobotTask($where = array())
    {
        $thirdRobotHistoryTaskDb = new ThirdRobotHistoryTask();
        $oneRobotTask = $thirdRobotHistoryTaskDb->getOneData($where);
        if ($oneRobotTask && !$oneRobotTask->isEmpty()) {
            $oneRobotTask = $oneRobotTask->toArray();
        } else {
            $oneRobotTask = array();
        }
        return $oneRobotTask;
    }

    public function saveOneRobotTask($where = array(), $saveData = array())
    {
        $thirdRobotHistoryTaskDb = new ThirdRobotHistoryTask();
        $ret = $thirdRobotHistoryTaskDb->saveOneData($where, $saveData);
        return $ret;
    }

    public function addOneRobotTask($addTask = array())
    {
        $thirdRobotHistoryTaskDb = new ThirdRobotHistoryTask();
        $inset_id = $thirdRobotHistoryTaskDb->addOneData($addTask);
        return $inset_id;
    }

    public function getRobotAlarmList($robot_code = '', $current = 1, $pagesize = 20, $params = array())
    {
        $res = array('list' => array(), 'count' => 0, 'total_limit' => $pagesize);
        $thirdRobotRecognitionService = new ThirdRobotRecognitionService();
        if ($robot_code) {
            $params['robotCode'] = $robot_code;
        }
        $params['current'] = $current;
        $params['size'] = $pagesize;
        $ret = $thirdRobotRecognitionService->getRobotAlarmList($params);
        if ($ret && is_array($ret) && isset($ret['data']) && is_array($ret['data']) && isset($ret['data']['records']) && !empty($ret['data']['records'])) {
            $records = $ret['data']['records'];
            $tmpsize = $ret['data']['size']; //每页记录数
            $tmptotal = $ret['data']['total'];  //总记录数
            $res['count'] = $tmptotal;
            $datas = $this->saveRemoteRoboterAlarmList($records, true);
            $res['list'] = $datas;
        }
        return $res;
    }

    public function getRemoteRobotAlarmList($robot_code = '', $current = 1, $pagesize = 100, $params = array())
    {
        $thirdRobotRecognitionService = new ThirdRobotRecognitionService();
        if ($robot_code) {
            $params['robotCode'] = $robot_code;
        }
        $params['current'] = $current;
        $params['size'] = $pagesize;
        $ret = $thirdRobotRecognitionService->getRobotAlarmList($params);
        $nowtime = time();
        if ($ret && is_array($ret) && isset($ret['data']) && is_array($ret['data']) && isset($ret['data']['records']) && !empty($ret['data']['records'])) {
            $records = $ret['data']['records'];
            $tmpsize = $ret['data']['size']; //每页记录数
            $tmptotal = $ret['data']['total'];  //总记录数
            //$tmpcurrent=$ret['data']['current']; //当前页号
            //$pages=$ret['data']['pages']; //总页数
            $this->saveRemoteRoboterAlarmList($records);
            $offsize = $pagesize * $current;
            if ($tmptotal > $offsize) {
                $current = $current + 1;
                $this->getRemoteRobotAlarmList($robot_code, $current, $pagesize, $params);
            }
            return true;
        }
        return true;
    }

    public function saveRemoteRoboterAlarmList($records, $is_return_data = false)
    {
        $nowtime = time();
        $thirdRobotAlarmInfoDb = new ThirdRobotAlarmInfo();
        $return_data = array();
        foreach ($records as $vv) {
            $alarm_id = trim($vv['id']);
            $saveData = array();
            $alarm_id = !empty($alarm_id) ? trim($alarm_id) : '';   //任务日志id
            if (empty($alarm_id)) {
                continue;
            }
            $saveData['robot_name'] = $vv['robotName'] ? trim($vv['robotName']) : '';
            $saveData['robot_code'] = $vv['robotCode'] ? trim($vv['robotCode']) : '';
            $saveData['type_name'] = $vv['typeName'] ? trim($vv['typeName']) : '';
            $saveData['type_cname'] = $vv['typeCname'] ? trim($vv['typeCname']) : '';
            $saveData['type_code'] = $vv['typeId'] ? trim($vv['typeId']) : '';
            $saveData['level'] = $vv['level'] ? trim($vv['level']) : 0;
            $saveData['details'] = $vv['details'] ? trim($vv['details']) : '';
            $saveData['event_status'] = $vv['status'] ? trim($vv['status']) : 0;
            $saveData['confirm_flag'] = $vv['confirmFlag'] ? trim($vv['confirmFlag']) : -1;
            $saveData['rdm_alarm_type'] = $vv['rdmAlarmType'] ? trim($vv['rdmAlarmType']) : 0;
            $saveData['entity_type'] = $vv['entityType'] ? trim($vv['entityType']) : '';
            $saveData['remark'] = $vv['remark'] ? trim($vv['remark']) : '';

            $event_time = $vv['eventTime'] ? trim($vv['eventTime']) : 0;   //毫秒
            $event_time = $event_time / 1000;
            $event_time = round($event_time);
            $saveData['event_time'] = $event_time;
            $start_time = $vv['startTime'] ? trim($vv['startTime']) : 0;   //毫秒
            $start_time = $start_time / 1000;
            $start_time = round($start_time);
            $saveData['start_time'] = $start_time;
            $end_time = $vv['endTime'] ? trim($vv['endTime']) : 0;   //毫秒
            $end_time = $end_time / 1000;
            $end_time = round($end_time);
            $saveData['end_time'] = $end_time;
            $keyArr = [
                'robotName', 'typeName', 'typeCname', 'typeId', 'level',
                'details', 'confirmFlag', 'rdmAlarmType', 'entityType', 'remark',
            ];
            $response_data = $vv;
            $response_data = unsetWordParam($response_data, $keyArr);
            $saveData['response_data'] = json_encode($response_data, JSON_UNESCAPED_UNICODE);
            $idd = 0;
            $oneDataObj = $thirdRobotAlarmInfoDb->getOneData(array(['alarm_id', '=', $alarm_id]));
            if ($oneDataObj && !$oneDataObj->isEmpty()) {
                $idd = $oneDataObj['id'];
                $saveData['update_time'] = $nowtime;
                $thirdRobotAlarmInfoDb->saveOneData(['id' => $oneDataObj['id']], $saveData);
            } else {
                $saveData['alarm_id'] = $alarm_id;
                $saveData['add_time'] = $nowtime;
                $idd = $thirdRobotAlarmInfoDb->addOneData($saveData);
            }
            if ($is_return_data) {
                $tmpArr = $saveData;
                $tmpArr['id'] = $idd;
                $tmpArr['alarm_id'] = $alarm_id;
                $tmpArr['event_time_str'] = $tmpArr['event_time'] > 0 ? date('Y-m-d H:i:s', $tmpArr['event_time']) : '';
                $tmpArr['start_time_str'] = $tmpArr['start_time'] > 0 ? date('Y-m-d H:i:s', $tmpArr['start_time']) : '';
                $tmpArr['end_time_str'] = $tmpArr['end_time'] > 0 ? date('Y-m-d H:i:s', $tmpArr['end_time']) : '';
                $tmpArr['event_status_str'] = $tmpArr['event_status'] == 1 ? '已消除' : '告警中';
                $tmpArr['confirm_flag_str'] = isset($this->alarm_confirm_flag_arr[$tmpArr['confirm_flag']]) ? $this->alarm_confirm_flag_arr[$tmpArr['confirm_flag']] : '未知';
                $tmpArr['rdm_alarm_type_str'] = isset($this->rdm_alarm_type_arr[$tmpArr['rdm_alarm_type']]) ? $this->rdm_alarm_type_arr[$tmpArr['rdm_alarm_type']] : '其他';
                $tmpArr['details_remark'] = '';
                if (!empty($tmpArr['details'])) {
                    $details = json_decode($tmpArr['details'], 1);
                    if (empty($details)) {
                        $tmpArr['details_remark'] = $tmpArr['details'];
                    }
                }
                if ($tmpArr['remark']) {
                    $tmpArr['details_remark'] .= ' 备注：' . $tmpArr['remark'];
                }
                $return_data[] = $tmpArr;
            }
        }
        if ($is_return_data) {
            return $return_data;
        }
        return true;
    }

    public function getAdminWorkers($whereArr, $field = '*')
    {
        $adminDb = new Admin();
        $adminList = $adminDb->getList($whereArr, $field);
        $setedIds = array();
        if ($adminList && !$adminList->isEmpty()) {
            $adminList = $adminList->toArray();
            $thirdRobotAlarmNoticeSetDb = new ThirdRobotAlarmNoticeSet();
            foreach ($adminList as $akk => $avv) {

                $whereArr = array();
                $whereArr[] = ['person_id', '=', $avv['id']];
                $whereArr[] = ['id_type', '<', 1];
                $whereArr[] = ['del_time', '<', 1];
                $oneTempObj = $thirdRobotAlarmNoticeSetDb->getOneData($whereArr);
                $adminList[$akk]['is_seted'] = 0;
                if ($oneTempObj && !$oneTempObj->isEmpty()) {
                    $adminList[$akk]['is_seted'] = 1;
                    $setedIds[] = $avv['id'];
                }
                $adminList[$akk]['openid_desc'] = '未绑';
                if (!empty($avv['openid']) && strlen($avv['openid']) > 10) {
                    $adminList[$akk]['openid_desc'] = '已绑';
                }
            }
        } else {
            $adminList = array();
        }
        $returnArr = array('list' => $adminList, 'seted_ids' => $setedIds);
        return $returnArr;
    }
    public function getVillageWorkers($whereArr, $field = '*',$robot_id=0){
        $houseWorkerDb = new HouseWorker();
        $thirdRobotDeviceDb= new ThirdRobotDevice();
        $adminList = $houseWorkerDb->getAll($whereArr, $field);
        
        $setedIds = array();
        if ($adminList && !$adminList->isEmpty()) {
            $adminList = $adminList->toArray();
            $thirdRobotAlarmNoticeSetDb = new ThirdRobotAlarmNoticeSet();
            $robot_code='';
            if($robot_id>0){
                $oneDataObj = $thirdRobotDeviceDb->getOneData(array(['id', '=', $robot_id]),'id,village_id,robot_code,name');
                if($oneDataObj && !$oneDataObj->isEmpty()){
                    $robot_code=$oneDataObj['robot_code'];
                }
            }
            foreach ($adminList as $akk => $avv) {
                $adminList[$akk]['id']=$avv['wid'];
                $whereArr = array();
                $whereArr[] = ['person_id', '=', $avv['wid']];
                $whereArr[] = ['id_type', '=', 1];
                $whereArr[] = ['village_id', '=', $avv['village_id']];
                $whereArr[] = ['del_time', '<', 1];
                if($robot_code){
                    $whereArr[] = ['robot_code', '=', $robot_code];
                }
                $oneTempObj = $thirdRobotAlarmNoticeSetDb->getOneData($whereArr);
                $adminList[$akk]['is_seted'] = 0;
                if ($oneTempObj && !$oneTempObj->isEmpty()) {
                    $adminList[$akk]['is_seted'] = 1;
                    $setedIds[] = $avv['wid'];
                }
                $adminList[$akk]['openid_desc'] = '未绑';
                if (!empty($avv['openid']) && strlen($avv['openid']) > 10) {
                    $adminList[$akk]['openid_desc'] = '已绑';
                }
            }
        } else {
            $adminList = array();
        }
        $returnArr = array('list' => $adminList, 'seted_ids' => $setedIds);
        return $returnArr;
    }
    public function saveRobotAlarmNoticeSet($saveArr = array(), $admin_id = 0, $id = 0,$xtype='')
    {
        if ($admin_id < 1 || empty($saveArr) || !isset($saveArr['levels']) || !isset($saveArr['robot_code'])) {
            return false;
        }
        $nowtime = time();
        $levels = $saveArr['levels'];
        $robot_codes = is_array($saveArr['robot_code']) ? $saveArr['robot_code']:array($saveArr['robot_code']);
        $thirdRobotAlarmNoticeSetDb = new ThirdRobotAlarmNoticeSet();
        $whereArr = array();
        $whereArr[] = ['del_time', '<', 1];
        $id_type=1; //1house_worker表wid
        if($xtype=='village_worker'){
            $whereArr[] = ['id_type', '=', 1];
            $whereArr[] = ['robot_id', '=', $saveArr['robot_id']];
        }else{
            $whereArr[] = ['id_type', '<', 1];
            $id_type=0;
        }
        if ($id > 0) {
            $whereArr[] = ['id', '=', $id];
        }else if($admin_id>0){
            $whereArr[]=array('person_id','=',$admin_id);
        }
        $tmpDataObj = $thirdRobotAlarmNoticeSetDb->getOneData($whereArr);
        $idd = 0;
        $old_robot_code = array();
        if ($tmpDataObj && !$tmpDataObj->isEmpty()) {
            $idd = $tmpDataObj['id'];
            if($tmpDataObj['id_type']==1){
               
            }else{
                $old_robot_code = $tmpDataObj['robot_code'] ? json_decode($tmpDataObj['robot_code']) : array();
            }
            if($xtype!='village_worker'){
                $saveArr['robot_code'] = json_encode($saveArr['robot_code'], JSON_UNESCAPED_UNICODE);   
            }
            $saveArr['person_id'] = $admin_id;
            $saveArr['levels'] = json_encode($saveArr['levels'], JSON_UNESCAPED_UNICODE);
            $saveArr['update_time'] = $nowtime;
            $thirdRobotAlarmNoticeSetDb->saveOneData(['id' => $idd], $saveArr);
        } else {
            $saveArr['levels'] = json_encode($saveArr['levels'], JSON_UNESCAPED_UNICODE);
            if($xtype!='village_worker'){
                $saveArr['robot_code'] = json_encode($saveArr['robot_code'], JSON_UNESCAPED_UNICODE);
            }
            $saveArr['person_id'] = $admin_id;
            $saveArr['id_type'] = 0;
            if($xtype=='village_worker'){
                $saveArr['id_type'] = 1;
            }
            $saveArr['add_time'] = $nowtime;
            $idd = $thirdRobotAlarmNoticeSetDb->addOneData($saveArr);
        }
        $old_robot_code = $old_robot_code ? $old_robot_code : array();
        $notice = array('id' => $idd, 'person_id' => $admin_id, 'levels' => $levels,'id_type'=>$id_type);
        $thirdRobotDeviceDb = new ThirdRobotDevice();
        foreach ($robot_codes as $code) {
            $oneData = $thirdRobotDeviceDb->getOneData(['robot_code' => $code], 'id,alarm_notice_set');
            if ($oneData && !$oneData->isEmpty()) {
                if ($old_robot_code && in_array($code, $old_robot_code)) {
                    $keyid = array_search($code, $old_robot_code);
                    if ($keyid !== false && $keyid >= 0) {
                        unset($old_robot_code[$keyid]);
                    }
                }
                $alarm_notice_set = $oneData['alarm_notice_set'] ? json_decode($oneData['alarm_notice_set'], 1) : array();
                $alarm_notice_set = is_array($alarm_notice_set) ? $alarm_notice_set : array();
                $alarm_notice_set[$idd] = $notice;
                $save_alarm_notice_set = array('alarm_notice_set' => json_encode($alarm_notice_set, JSON_UNESCAPED_UNICODE));
                $thirdRobotDeviceDb->saveOneData(['id' => $oneData['id']], $save_alarm_notice_set);
            }
        }
        if (!empty($old_robot_code)) {
            //将编辑中去掉的 去掉
            foreach ($old_robot_code as $old_code) {
                $oneData = $thirdRobotDeviceDb->getOneData(['robot_code' => $old_code], 'id,alarm_notice_set');
                if ($oneData && !$oneData->isEmpty()) {
                    $alarm_notice_set = $oneData['alarm_notice_set'] ? json_decode($oneData['alarm_notice_set'], 1) : array();
                    $alarm_notice_set = is_array($alarm_notice_set) ? $alarm_notice_set : array();
                    if ($alarm_notice_set && isset($alarm_notice_set[$idd])) {
                        unset($alarm_notice_set[$idd]);
                        $alarm_notice_set = empty($alarm_notice_set) ? '' : json_encode($alarm_notice_set, JSON_UNESCAPED_UNICODE);
                        $save_alarm_notice_set = array('alarm_notice_set' => $alarm_notice_set);
                        $thirdRobotDeviceDb->saveOneData(['id' => $oneData['id']], $save_alarm_notice_set);
                    }
                }
            }
        }
        return $idd;
    }

    public function getAlarmNoticeSetList($whereArr = array(), $field = '*', $page = 1, $limit = 10)
    {
        $res = array('list' => array(), 'count' => 0, 'total_limit' => $limit);
        $thirdRobotAlarmNoticeSetDb = new ThirdRobotAlarmNoticeSet();
        $robotAlarmNotice = $thirdRobotAlarmNoticeSetDb->getListData($whereArr, $field, $page, $limit);
        if ($robotAlarmNotice && !$robotAlarmNotice->isEmpty()) {
            $adminDb = new Admin();
            $houseWorkerDb = new HouseWorker();
            $robotAlarmNotice = $robotAlarmNotice->toArray();
            $fieldStr = 'id,account,realname,phone,openid,nickname,level';
            $fieldHouseStr = 'wid,account,name,phone,openid,nickname';
            foreach ($robotAlarmNotice as $rkk => $rvv) {
                $levels = $rvv['levels'] ? json_decode($rvv['levels']) : '';
                $robotAlarmNotice[$rkk]['levels'] = $levels ? $levels : array();
                $levels = is_array($levels) ? implode('，', $levels) : '';
                $robotAlarmNotice[$rkk]['levels_str'] = $levels;
                if($rvv['id_type']==1){
                    $robotAlarmNotice[$rkk]['robot_code'] = $rvv['robot_code'] ? array($rvv['robot_code']): array();
                    $robotAlarmNotice[$rkk]['robot_code_str'] = $rvv['robot_code'];
                }else{
                    $robot_code = $rvv['robot_code'] ? json_decode($rvv['robot_code']) : '';
                    $robotAlarmNotice[$rkk]['robot_code'] = $robot_code ? $robot_code : array();
                    $robot_code = is_array($robot_code) ? implode('，', $robot_code) : '';
                    $robotAlarmNotice[$rkk]['robot_code_str'] = $robot_code;
                }

                $robotAlarmNotice[$rkk]['add_time'] = date('Y-m-d H:i:s', $rvv['add_time']);
                $robotAlarmNotice[$rkk]['person_str'] = '';
                if($rvv['id_type']==1){
                    $houseWorkerObj=$houseWorkerDb->get_one(['wid' => $rvv['person_id']], $fieldHouseStr);
                    if ($houseWorkerObj && !$houseWorkerObj->isEmpty()) {
                        $robotAlarmNotice[$rkk]['person_str'] = $houseWorkerObj['account'] . '（【姓名：' . $houseWorkerObj['name'] . '】【手机号：' . $houseWorkerObj['phone'] . '）';
                    }
                }else{
                    $adminTmp = $adminDb->getOne(['id' => $rvv['person_id']], $fieldStr);
                    if ($adminTmp && !$adminTmp->isEmpty()) {
                        $robotAlarmNotice[$rkk]['person_str'] = $adminTmp['account'] . '（【姓名：' . $adminTmp['realname'] . '】【手机号：' . $adminTmp['phone'] . '）';
                    }
                }
            }
            $res['list'] = $robotAlarmNotice;
            $count = $thirdRobotAlarmNoticeSetDb->getCount($whereArr);
            $res['count'] = $count;
        }
        return $res;
    }

    public function delAlarmNoticeSet($idd = 0)
    {
        if ($idd < 1) {
            return false;
        }
        $whereArr = array();
        $whereArr[] = ['id', '=', $idd];
        $whereArr[] = ['id_type', '<=', 1];
        $thirdRobotAlarmNoticeSetDb = new ThirdRobotAlarmNoticeSet();
        $oneTempObj = $thirdRobotAlarmNoticeSetDb->getOneData($whereArr);
        $nowTime = time();
        if ($oneTempObj && !$oneTempObj->isEmpty()) {
            if($oneTempObj['id_type']==1){
                $robot_codes=array($oneTempObj['robot_code']);
            }else{
                $robot_codes = $oneTempObj['robot_code'] ? json_decode($oneTempObj['robot_code']) : '';
            }
            $delSaveArr = array('del_time' => $nowTime);
            $thirdRobotAlarmNoticeSetDb->saveOneData($whereArr, $delSaveArr);
            $robot_codes = $robot_codes ? $robot_codes : array();
            //将删除了 去掉
            $thirdRobotDeviceDb = new ThirdRobotDevice();
            foreach ($robot_codes as $code) {
                $oneData = $thirdRobotDeviceDb->getOneData(['robot_code' => $code], 'id,alarm_notice_set');
                if ($oneData && !$oneData->isEmpty()) {
                    $alarm_notice_set = $oneData['alarm_notice_set'] ? json_decode($oneData['alarm_notice_set'], 1) : array();
                    $alarm_notice_set = is_array($alarm_notice_set) ? $alarm_notice_set : array();
                    if ($alarm_notice_set && isset($alarm_notice_set[$idd])) {
                        unset($alarm_notice_set[$idd]);
                        $alarm_notice_set = empty($alarm_notice_set) ? '' : json_encode($alarm_notice_set, JSON_UNESCAPED_UNICODE);
                        $save_alarm_notice_set = array('alarm_notice_set' => $alarm_notice_set);
                        $thirdRobotDeviceDb->saveOneData(['id' => $oneData['id']], $save_alarm_notice_set);
                    }
                }
            }
        } else {
            throw new \think\Exception("数据记录不存在！");
        }
        return $idd;
    }

    /**
     * 获取地图点位列表.
     */
    public function getRobotBizPointList($where = array(), $field = true, $page = 1, $page_size = 20, $order = 'sort ASC, create_time DESC')
    {
        $db_third_robot_map_points = new ThirdRobotMapPoints();
        $list = $db_third_robot_map_points->getList($where, $field, $order, $page, $page_size);
        if ($list && !is_array($list)) {
            $list = $list->toArray();
        }
        $count = $db_third_robot_map_points->getCount($where);
        $result = [];
        $result['list'] = $list;
        $result['count'] = $count;
        return $result;
    }

    /**
     * 获取地图列表.
     */
    public function getRobotBizMapsList($where = array(), $field = true, $page = 1, $page_size = 20, $order = 'update_time DESC, add_time DESC')
    {
        $db_third_robot_map = new ThirdRobotMap();
        $list = $db_third_robot_map->getList($where, $field, $order, $page, $page_size);
        if ($list && !is_array($list)) {
            $list = $list->toArray();
        }
        $count = $db_third_robot_map->getCount($where);
        $result = [];
        $result['list'] = $list;
        $result['count'] = $count;
        return $result;
    }

    /**
     * 获取机器人的运行时状态数据，包括离线、在线、电量、运行的版本信息、工作状态、急停等信息[并记录][todo 离线告警可以写在这里 也可以放在事件推送].
     */
    public function getThirdRobotInfo($robotCode)
    {
        $param = [
            'robotCode' => $robotCode,
        ];
        $info = (new ThirdRobotRecognitionService())->getRobotInfo($param);
        if (isset($info['data']) && !empty($info['data'])) {
            $data = $info['data'];
            $robotCode = isset($data['robotCode']) && $data['robotCode'] ? $data['robotCode'] : $robotCode;
            $battery = isset($data['battery']) && $data['battery'] ? $data['battery'] : '';
            $loc = isset($data['loc']) && $data['loc'] ? json_decode($data['loc'], true) : '';
            $curLocation = isset($data['curLocation']) && $data['curLocation'] ? json_decode($data['curLocation'], true) : '';
            $onlineFlag = isset($data['onlineFlag']) ? intval($data['onlineFlag']) : -2;
            $workFlag = isset($data['workFlag']) ? intval($data['workFlag']) : -2;
            $chargeFlag = isset($data['chargeFlag']) ? intval($data['chargeFlag']) : -2;
            $nowTime = time();
            $keyArr = [
                'robotCode', 'battery', 'onlineFlag', 'workFlag', 'chargeFlag',
            ];
            $runInfo = $data;
            //$runInfo = unsetWordParam($runInfo, $keyArr);
            $saveData = [];
            if ($battery) {
                $saveData['battery'] = $battery;
            }
            if (isset($loc['lng']) && isset($loc['lat']) && trim($loc['lng']) && trim($loc['lat'])) {
                $saveData['lng'] = trim($loc['lng']);
                $saveData['lat'] = trim($loc['lat']);
                $report_time = isset($loc['reportTime']) && $loc['reportTime'] ? round($loc['reportTime'] / 1000) : $nowTime;
                $saveData['report_time'] = $report_time;
            } elseif (isset($curLocation['lng']) && isset($curLocation['lat']) && trim($curLocation['lng']) && trim($curLocation['lat'])) {
                $saveData['lng'] = trim($curLocation['lng']);
                $saveData['lat'] = trim($curLocation['lat']);
                $report_time = isset($curLocation['reportTime']) && $curLocation['reportTime'] ? round($curLocation['reportTime'] / 1000) : $nowTime;
                $saveData['report_time'] = $report_time;
            }
            if ($onlineFlag != -2) {
                $saveData['online_flag'] = $onlineFlag;
            }
            if ($workFlag != -2) {
                $saveData['work_flag'] = $workFlag;
            }
            if ($chargeFlag != -2) {
                $saveData['charge_flag'] = $chargeFlag;
            }
            $saveData['run_info'] = json_encode($runInfo, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            $saveData['get_info_time'] = $nowTime;

            $whereRobot = [
                'robot_code' => $robotCode,
            ];
            $db_third_robot_device = new ThirdRobotDevice();
            $robotInfo = $db_third_robot_device->getOneData($whereRobot, 'id', 'del_time DESC, id DESC');
            if (isset($robotInfo['id']) && $robotInfo['id']) {
                $saveData['update_time'] = $nowTime;
                $db_third_robot_device->saveOneData(['id' => $robotInfo['id']], $saveData);
            }
        }
        return $info;
    }

    /**
     * 获取对应机器人图片.
     */
    public function getRobotImg($robot_type, $static = '')
    {
        if (!$static) {
            $siteUrl = cfg('site_url');
            $static = $siteUrl . '/v20/public/static/third_robot/robot_type/';
        }
        $robot_img = '';
        switch ($robot_type) {
            case 'CloudFarming':
                $robot_img = $static . 'CloudFarming.png';
                break;
            case 'cleaning':
                $robot_img = $static . 'cleaning.png';
                break;
            case 'ginger':
                $robot_img = $static . 'ginger.png';
                break;
            case 'cloudia':
                $robot_img = $static . 'cloudia.png';
                break;
            case 'vending':
                $robot_img = $static . 'vending.png';
                break;
            case 'pepper':
                $robot_img = $static . 'pepper.png';
                break;
            case 'CloudIRIS':
                $robot_img = $static . 'CloudIRIS.png';
                break;
            case 'ginger-lite':
            case 'CloudGingerLite':
                $robot_img = $static . 'CloudGingerLite.png';
                break;
            case 'MoverMini':
            case 'MoverMiniD':
                $robot_img = $static . 'MoverMini.png';
                break;
            case 'patrol':
                $robot_img = $static . 'patrol.png';
                break;
            case 'seeds':
                $robot_img = $static . 'seeds.png';
                break;
            default:
                $robot_img = $static . 'ginger.png';
                break;
        }
        return $robot_img;
    }

    /**
     * 获取对应机器人状态和颜色及拦截提示.
     */
    public function getRobotStatusText($item, $info)
    {
        // 0-离线 1-在线
        $online_flag = isset($info['online_flag']) ? intval($info['online_flag']) : 0;
        // 工作状态 0-非执行 1-执行中
        $work_flag = isset($info['work_flag']) ? intval($info['work_flag']) : 0;
        // 0-未充电 1-充电中
        $charge_flag = isset($info['charge_flag']) ? intval($info['charge_flag']) : 0;
        // 运行状态 1正常 2停用 3故障 4维修中
        $running_status = isset($info['running_status']) ? intval($info['running_status']) : 0;

        $isCall = true; // 是否可以呼叫
        $callTip = '';// 不可呼叫提示
        if ($running_status == 1 && $online_flag == 1 && $charge_flag == 1) {
            $item['status_text'] = '充电中';
            $item['status_color'] = 'green';
            $isCall = true;
            $callTip = '';//客户要求充电也可以呼叫
        } elseif ($running_status == 1 && $online_flag == 1 && $work_flag == 1) {
            $item['status_text'] = '工作中';
            $item['status_color'] = 'green';
            $isCall = false;
            $callTip = '机器人工作中无法呼叫！';
        } elseif ($running_status == 1 && $online_flag == 1) {
            $item['status_text'] = '正常';
            $item['status_color'] = 'black';
        } elseif ($running_status == 1 && $online_flag != 1) {
            $item['status_text'] = '离线中';
            $item['status_color'] = 'red';
            $isCall = false;
            $callTip = '机器人离线了无法呼叫！';
        } elseif ($running_status == 2) {
            $item['status_text'] = '停用中';
            $item['status_color'] = 'red';
            $isCall = false;
            $callTip = '机器人已停用无法呼叫！';
        } elseif ($running_status == 3) {
            $item['status_text'] = '故障中';
            $item['status_color'] = 'red';
            $isCall = false;
            $callTip = '机器人故障了无法呼叫！';
        } elseif ($running_status == 4) {
            $item['status_text'] = '维修中';
            $item['status_color'] = '#2681f3';
            $isCall = false;
            $callTip = '机器人正在维修无法呼叫！';
        } else {
            $item['status_text'] = '异常';
            $item['status_color'] = 'red';
            $isCall = false;
            $callTip = '机器人异常无法呼叫！';
        }
        $item['isCall'] = $isCall;
        $item['callTip'] = $callTip;
        return $item;
    }

    /**
     * 整合反馈失败原因
     */
    public function getCallRobotErrMsg($errCode, $errMessage = '')
    {
        switch ($errCode) {
            case 4000:
                $errMessage = '无响应';
                break;
            case 4002:
                $errMessage = '响应超时';
                break;
            case 4004:
                $errMessage = '响应错误';
                break;
        }
        !$errMessage && $errMessage = '响应失败';
        return $errMessage;
    }

    public function getTaskActionNameText($actionName, $action = [])
    {
        $actionNameText = $actionName;// 如果没有对应上的原样返回
        $handleAction = [];
        switch ($actionName) {
            case 'PlayTTS':
                $actionNameText = '定点单次播报';
                $actionParam = isset($action['actionParam']) && $action['actionParam'] ? $action['actionParam'] : [];
                $content = [];
                if (isset($actionParam['tts']) && $actionParam['tts']) {
                    $content[] = ['label' => '播报内容', 'labelColor' => '#333', 'value' => trim($actionParam['tts']), 'valueColor' => '#333'];
                }
                if (isset($actionParam['actionMode']) && $actionParam['actionMode']) {
                    $actionModeArr = [
                        'NONE' => '无动作', 'RANDOM' => '随机动作'
                    ];
                    $actionMode = isset($actionModeArr[$actionParam['actionMode']]) ? $actionModeArr[$actionParam['actionMode']] : $actionParam['actionMode'];
                    $content[] = ['label' => '动作模式', 'labelColor' => '#333', 'value' => trim($actionMode), 'valueColor' => '#333'];
                }
                if (isset($actionParam['interruptTime']) && $actionParam['interruptTime']) {
                    $content[] = ['label' => '打断超时时长', 'labelColor' => '#333', 'value' => trim($actionParam['interruptTime']) . '秒', 'valueColor' => '#333'];
                }
                break;
            case 'StartTTS':
                $actionNameText = '开始循环播报';
                $actionParam = isset($action['actionParam']) && $action['actionParam'] ? $action['actionParam'] : [];
                $content = [];
                if (isset($actionParam['tts']) && $actionParam['tts']) {
                    $content[] = ['label' => '播报内容', 'labelColor' => '#333', 'value' => trim($actionParam['tts']), 'valueColor' => '#333'];
                }
                break;
            case 'StopTTS':
                $actionNameText = '结束循环播报';
                $actionParam = isset($action['actionParam']) && $action['actionParam'] ? $action['actionParam'] : [];
                $content = [];
                if (isset($actionParam['tts']) && $actionParam['tts']) {
                    $content[] = ['label' => '播报内容', 'labelColor' => '#333', 'value' => trim($actionParam['tts']), 'valueColor' => '#333'];
                }
                break;
            case 'DeliveryNotify':
                $actionNameText = '送货通知';
                $actionParam = isset($action['actionParam']) && $action['actionParam'] ? $action['actionParam'] : [];
                $content = [];
                if (isset($actionParam['phone']) && $actionParam['phone']) {
                    $content[] = ['label' => '手机号', 'labelColor' => '#333', 'value' => trim($actionParam['phone']), 'valueColor' => '#333'];
                }
                if (isset($actionParam['pointName']) && $actionParam['pointName']) {
                    $content[] = ['label' => '点位', 'labelColor' => '#333', 'value' => trim($actionParam['pointName']), 'valueColor' => '#333'];
                }
                if (isset($actionParam['pwd']) && $actionParam['pwd']) {
                    $content[] = ['label' => '密码', 'labelColor' => '#333', 'value' => trim($actionParam['pwd']), 'valueColor' => '#333'];
                }
                if (isset($actionParam['content']) && $actionParam['content']) {
                    $content[] = ['label' => '提示', 'labelColor' => '#333', 'value' => trim($actionParam['content']), 'valueColor' => '#333'];
                }
                if (isset($actionParam['tts']) && $actionParam['tts']) {
                    $content[] = ['label' => '播报内容', 'labelColor' => '#333', 'value' => trim($actionParam['tts']), 'valueColor' => '#333'];
                }
                break;
            case 'ReachNotify':
                $actionNameText = '抵达通知';
                $actionParam = isset($action['actionParam']) && $action['actionParam'] ? $action['actionParam'] : [];
                $content = [];
                if (isset($actionParam['phone'])) {
                    $phone = $actionParam['phone'] ? trim($actionParam['phone']) : '无';
                    $content[] = ['label' => '手机号', 'labelColor' => '#333', 'value' => $phone, 'valueColor' => '#333'];
                }
                if (isset($actionParam['pointName'])) {
                    $pointName = $actionParam['pointName'] ? trim($actionParam['pointName']) : '无';
                    $content[] = ['label' => '点位', 'labelColor' => '#333', 'value' => $pointName, 'valueColor' => '#333'];
                }
                if (isset($actionParam['pwd']) && $actionParam['pwd']) {
                    $content[] = ['label' => '密码', 'labelColor' => '#333', 'value' => trim($actionParam['pwd']), 'valueColor' => '#333'];
                }
                if (isset($actionParam['content']) && $actionParam['content']) {
                    $content[] = ['label' => '通知', 'labelColor' => '#333', 'value' => trim($actionParam['content']), 'valueColor' => '#333'];
                }
                if (isset($actionParam['tts']) && $actionParam['tts']) {
                    $content[] = ['label' => '播报内容', 'labelColor' => '#333', 'value' => trim($actionParam['tts']), 'valueColor' => '#333'];
                }
                break;
            case 'GetDelivery':
                $actionNameText = '取货';
                $actionParam = isset($action['actionParam']) && $action['actionParam'] ? $action['actionParam'] : [];
                $content = [];
                if (isset($actionParam['drawer']) && $actionParam['drawer']) {
                    $content[] = ['label' => '箱子编号', 'labelColor' => '#333', 'value' => trim($actionParam['drawer']), 'valueColor' => '#333'];
                }
                if (isset($actionParam['pointName1']) && $actionParam['pointName1']) {
                    $content[] = ['label' => '点位1', 'labelColor' => '#333', 'value' => trim($actionParam['pointName1']), 'valueColor' => '#333'];
                }
                if (isset($actionParam['pointName2']) && $actionParam['pointName2']) {
                    $content[] = ['label' => '点位2', 'labelColor' => '#333', 'value' => trim($actionParam['pointName2']), 'valueColor' => '#333'];
                }
                break;
            case 'CheckIn':
                $actionNameText = '打卡';
                $actionParam = isset($action['actionParam']) && $action['actionParam'] ? $action['actionParam'] : [];
                $perceptionTypes = isset($actionParam['perceptionTypes']) && $actionParam['perceptionTypes'] ? $actionParam['perceptionTypes'] : [];
                $code = isset($perceptionTypes['code']) && $perceptionTypes['code'] ? $perceptionTypes['code'] : '';
                $name = isset($perceptionTypes['name']) && $perceptionTypes['name'] ? $perceptionTypes['name'] : '';
                $perceptionText = $this->getPerceptionTypeName($code, $name);
                if ($perceptionText) {
                    $content[] = ['label' => '识别项', 'labelColor' => '#333', 'value' => trim($perceptionText), 'valueColor' => '#333'];
                }
                if (isset($perceptionTypes['interval']) && $perceptionTypes['interval']) {
                    $content[] = ['label' => '打卡间隔', 'labelColor' => '#333', 'value' => trim($perceptionTypes['interval']) . '秒', 'valueColor' => '#333'];
                } elseif (isset($actionParam['interval']) && $actionParam['interval']) {
                    $content[] = ['label' => '打卡间隔', 'labelColor' => '#333', 'value' => trim($actionParam['interval']) . '秒', 'valueColor' => '#333'];
                }
                if (isset($perceptionTypes['loop'])) {
                    if (intval($perceptionTypes['loop']) === 0) {
                        $loopText = '循环到超时';
                    } else {
                        $loopText = intval($perceptionTypes['loop']) . '次';
                    }
                    $content[] = ['label' => '打卡次数', 'labelColor' => '#333', 'value' => $loopText, 'valueColor' => '#333'];
                } elseif (isset($actionParam['loop'])) {
                    if (intval($actionParam['loop']) === 0) {
                        $loopText = '循环到超时';
                    } else {
                        $loopText = intval($actionParam['loop']) . '次';
                    }
                    $content[] = ['label' => '打卡次数', 'labelColor' => '#333', 'value' => $loopText, 'valueColor' => '#333'];
                }
                if (isset($actionParam['control']['zoom']) && $actionParam['control']['zoom']) {
                    $content[] = ['label' => '镜头', 'labelColor' => '#333', 'value' => trim($actionParam['control']['zoom']), 'valueColor' => '#333'];
                } elseif (isset($actionParam['zoom']) && $actionParam['zoom']) {
                    $content[] = ['label' => '镜头', 'labelColor' => '#333', 'value' => trim($actionParam['zoom']), 'valueColor' => '#333'];
                }
                break;
            case 'DoAction':
                $actionNameText = '行动';
                $actionParam = isset($action['actionParam']) && $action['actionParam'] ? $action['actionParam'] : [];
                if (isset($actionParam['name']) && $actionParam['name']) {
                    $nameArr = [
                        'DeliveryDone' => '配送完成'
                    ];
                    $name = isset($nameArr[$actionParam['name']]) ? $nameArr[$actionParam['name']] : $actionParam['name'];
                    $content[] = ['label' => '名称', 'labelColor' => '#333', 'value' => trim($name), 'valueColor' => '#333'];
                }
                break;
            case 'DeliveryDone':
                $actionNameText = '配送完成';
                break;
        }
        if (! empty($action)) {
            if (isset($actionParam) && isset($actionParam['timeout']) && $actionParam['timeout']) {
                $content[] = ['label' => '超时时长', 'labelColor' => '#333', 'value' => trim($actionParam['timeout']) . '秒', 'valueColor' => '#333'];
            }
            if (isset($actionParam) && isset($actionParam['delay']) && $actionParam['delay']) {
                $content[] = ['label' => '等待时长', 'labelColor' => '#333', 'value' => trim($actionParam['delay']) . '秒', 'valueColor' => '#333'];
            }
            $handleAction['actionName'] = $actionNameText;
            if (! empty($content)) {
                $handleAction['content'] = $content;
            }
            return $handleAction;
        }
        return $actionNameText;
    }

    public function getPerceptionTypeName($code = '', $name = '') {
        $perceptionTypeCodeArr = [
            '2000' => '在指定位置开启AI自动检测小区大门',
            '2001' => '在指定位置开启AI自动检测车辆乱停放',
            '2002' => '在指定位置开启AI自动检测车辆乱停放',
            '2006' => '在指定位置开启AI自动检测绿化带垃圾',
            '2007' => '在指定位置开启AI自动检测翻越围栏',
            '2008' => '在指定位置开启AI自动检测人员聚集',
            '2009' => '在指定位置开启AI自动检测车辆堵塞',
            '2010' => '在指定位置开启AI自动车位识别',
        ];
        if ($code && isset($perceptionTypeCodeArr[$code])) {
            return $perceptionTypeCodeArr[$code];
        }
        $perceptionTypeNameArr = [
            'doorStatus' => '在指定位置开启AI自动检测小区大门',
            'vehicle' => '在指定位置开启AI自动检测车辆乱停放',
            'garbageCan' => '在指定位置开启AI自动检测车辆乱停放',
            'trash' => '在指定位置开启AI自动检测绿化带垃圾',
            'climb' => '在指定位置开启AI自动检测翻越围栏',
            'crowd' => '在指定位置开启AI自动检测人员聚集',
            'countVehicle' => '在指定位置开启AI自动检测车辆堵塞',
            'carPlate' => '在指定位置开启AI自动车位识别',
        ];
        if ($name && isset($perceptionTypeNameArr[$name])) {
            return $perceptionTypeNameArr[$name];
        }
        return $name;
    }

    public function getAlarmCodeTitle($alarmCode=''){
        $alarmCodeInfo=array(
            '1000001'=>'连接中断',
            '1000002'=>'WiFi信号弱',
            '1000004'=>'HARI连接中断',
            '1000020'=>'系统重启',
            '1000026'=>'软件重启',
            '1000027'=>'底盘连接中断',
            '1000029'=>'电池故障',
            '1000031'=>'碰撞',
            '1000035'=>'麦克风故障',
            '1000038'=>'惯性测量单元故障',
            '1000039'=>'轮子故障',
            '1000040'=>'摄像头故障',
            '1000041'=>'关节故障',
            '1000042'=>'激光雷达故障',
            '1000046'=>'温度超限保护',
            '1000048'=>'低电量',
            '1000049'=>'跌落',
            '1000052'=>'超声传感器故障',
            '1000058'=>'门故障',
            '1000060'=>'锁故障',
            '1000063'=>'MCU模块故障',
            '1000066'=>'远程急停',
            '1000067'=>'跌落锁定',
            '1000068'=>'碰撞锁定',
            '1000069'=>'充电失败',
            '1000070'=>'硬件不存在',
            '1000071'=>'红外回充故障',
            '1000072'=>'驱动器短路',
            '1000073'=>'驱动器霍尔故障',
            '1000074'=>'驱动器过流',
            '1000075'=>'驱动器欠压',
            '1000076'=>'驱动器过压',
            '1000077'=>'驱动器软件禁能',
            '1000078'=>'电机过流',
            '1000079'=>'飞车故障',
            '1000080'=>'激光不匹配',
            '1000081'=>'弱网告警',
            '1000084'=>'制动器故障',
            '1000087'=>'ECU故障',
            '1000088'=>'视频转码失败',
            '1000089'=>'重力传感器故障',
            '1000092'=>'DS连接中断',
            '1000300'=>'地图不存在或错误',
            '1000302'=>'导航定位丢失',
            '1000303'=>'路径不存在或错误',
            '1000304'=>'缺水',
            '1000307'=>'紧急停止',
            '1000308'=>'路径规划失败',
            '1000309'=>'遇阻滞留',
            '1000310'=>'悬崖检测',
            '1000312'=>'蓝图加载失败',
            '1000900'=>'性能越限',
        );
        if(empty($alarmCode)){
            return '';
        }
        $title=isset($alarmCodeInfo[$alarmCode]) ? $alarmCodeInfo[$alarmCode]:'未知';
        return $title;
    }

    public function getVisionLogCodeTitle($visionLogCode=''){
        $visionLogCodeInfo=array(
            'doorStatus'=>'大门检测',
            'vehicle'=>'车辆乱停放',
            'garbageCan'=>'垃圾桶满溢',
            'trash'=>'绿化带垃圾',
            'climb'=>'翻越围栏',
            'crowd'=>'人员聚集',
            'countVehicle'=>'车辆堵塞',
            'carPlate'=>'车位识别',
            'lawnTrample'=>'践踏草坪',
            'cvMeter'=>'巡检仪表识别',
            'countCan'=>'垃圾桶数量识别',
            'trashbin'=>'垃圾桶盖',
            'bottle'=>'地面垃圾',
            'occupyEA'=>'应急通道占用',
        );
        if(empty($visionLogCode)){
            return '';
        }
        $title=isset($visionLogCodeInfo[$visionLogCode]) ? $visionLogCodeInfo[$visionLogCode]:'';
        return $title;
    }

    public function saveWisdomQrcodeRecord($recognitionData=array(),$village_id=0){
        $wisdomQrcodeDb= new WisdomQrcode();
        $wisdomQrcodeCateDb= new WisdomQrcodeCate();

        $whereArr = array();
        if($village_id>0){
            $whereArr[] = array('village_id', '=', $village_id);
        }
        $whereArr[] = array('robot_code', '=', $recognitionData['robot_code']);
        $whereArr[] = array('task_instance_id', '=', $recognitionData['task_instance_id']);
        $tmpQrcodeCate = $wisdomQrcodeCateDb->getFind($whereArr);
        $cate_id = 0;
        $cate_name='';
        if ($tmpQrcodeCate && !$tmpQrcodeCate->isEmpty()) {
            $cate_id = $tmpQrcodeCate['id'];
            $village_id = $tmpQrcodeCate['village_id'];
            $cate_name=$tmpQrcodeCate['cate_name'];
        }
        if($cate_id<1){
            return true;
        }
        $whereArr = array();
        $whereArr[] = array('cate_id', '=',$cate_id);
        $whereArr[] = array('task_node_id', '=', $recognitionData['task_node_id']);
        $whereArr[] = array('task_instance_id', '=', $recognitionData['task_instance_id']);
        $tmpQrcode = $wisdomQrcodeDb->getFind($whereArr);
        $qrcode_id=0;
        $qrcode_name ='';
        if ($tmpQrcode && !$tmpQrcode->isEmpty()) {
            $qrcode_id=$tmpQrcode['id'];
            $cate_id=$tmpQrcode['cate_id'];
            $qrcode_name=$tmpQrcode['name'];
        }
        if($qrcode_id>0){
            $qrcodeRecord=array();
            $qrcodeRecord['cate_id']=$cate_id;
            $qrcodeRecord['qrcode_id']=$qrcode_id;
            $qrcodeRecord['add_time']=isset($recognitionData['add_time'])? $recognitionData['add_time']:time();
            $qrcodeRecord['uid']=0;
            $qrcodeRecord['village_id']=$village_id;
            $qrcodeRecord['robot_code']=$recognitionData['robot_code'];
            $qrcodeRecord['from_type']=1;
            $qrcodeRecord['task_instance_id']=$recognitionData['task_instance_id'];
            $qrcodeRecord['task_node_id']=$recognitionData['task_node_id'];
            $qrcodeRecord['rtype']=$recognitionData['rtype'];  //1巡检 2识别
            if(isset($recognitionData['item_code'])){
                $qrcodeRecord['robot_recognition_type']=$recognitionData['item_code'] ? $recognitionData['item_code']:'';
            }
            $robot_record=array();
            $robot_record['title']=isset($recognitionData['title']) ? $recognitionData['title']:'';
            $robot_record['remark']=isset($recognitionData['remark']) ? $recognitionData['remark']:'';
            $robot_record['point_name']=$recognitionData['point_name'];
            $robot_record['image_urls']=$recognitionData['image_urls'];
            $robot_record['video_urls']=$recognitionData['video_urls'];
            $robot_record['task_node_id']=$recognitionData['task_node_id'];
            $robot_record['task_instance_id']=$recognitionData['task_instance_id'];
            $robot_record['start_time']=$recognitionData['start_time'];
            $robot_record['end_time']=$recognitionData['end_time'];
            $qrcodeRecord['robot_record']= json_encode($robot_record,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
            $wisdomQrcodeRecordLog=new WisdomQrcodeRecordLog();
            $wisdomQrcodeRecordLog->addData($qrcodeRecord);
            $thirdRobotSubscribeMessageService = new ThirdRobotSubscribeMessageService();
            $infoArr=array('cate_id'=>$cate_id,'cate_name'=>$cate_name,'qrcode_id'=>$qrcode_id,'qrcode_name'=>$qrcode_name);
            $infoArr['robot_code']=$recognitionData['robot_code'];
            $infoArr['task_instance_id']=$recognitionData['task_instance_id'];
            $infoArr['task_node_id']=$recognitionData['task_node_id'];
            $infoArr['point_name']=$recognitionData['point_name'];
            $infoArr['title']=isset($recognitionData['title']) ? $recognitionData['title']:''; //识别类别
            $infoArr['item_code']=isset($recognitionData['item_code']) ? $recognitionData['item_code']:''; //识别类别
            $thirdRobotSubscribeMessageService->sendNoticeToHouseWorker($recognitionData['rtype'],$village_id,$infoArr);
        }
        return true;
    }
    
    public function noPointRobotId()
    {
        return ThirdRobotDevice::alias('r')
            ->leftJoin('third_robot_map_points p', 'r.robot_code = p.robot_code')
            ->where(['p.id' => NULL])
            ->group('r.id')
            ->column('r.id');
    }
}