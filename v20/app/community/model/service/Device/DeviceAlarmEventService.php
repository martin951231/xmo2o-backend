<?php


namespace app\community\model\service\Device;


use app\common\model\service\send_message\SmsService;
use app\common\model\service\weixin\TemplateNewsService;
use app\community\model\db\Device\GarbageCollectionMachineList;
use app\community\model\db\Device\HouseMaintenan;
use app\community\model\db\HouseMaintenanLog;
use app\community\model\db\HouseMaintenanWorker;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageFloor;
use app\community\model\db\HouseVillageInfo;
use app\community\model\db\HouseVillagePileEquipment;
use app\community\model\db\HouseVillagePublicArea;
use app\community\model\db\HouseVillageSingle;
use app\community\model\db\HouseWorker;
use app\community\model\service\HouseFaceDeviceService;
use app\consts\DeviceConst;
use app\common\model\db\Config;

class DeviceAlarmEventService
{
    public $next_key = [1,5,10,20,30,40,60,90,120,150,180,240,300]; // 通知时间间隔 单位分钟
    protected $title,$remark,$device_name,$village_id;

    /**
     * 故障通知
     * @param $device_id
     * @param $reason
     * @param bool $notice
     * @param string $type
     * @return false
     * @throws \think\Exception
     */
    public function faultNotice($device_id, $reason, $notice = true, $type = DeviceConst::DEVICE_TYPE_FACE)
    {
        $dbHouseMaintenan = new HouseMaintenan();
        $whereWork = [];
        $whereWork[] = ['status', '=', 1];
        $whereWork[] = ['openid', '<>', ''];
        $maintenanList = $dbHouseMaintenan->getSome($whereWork, 'id,name,phone,openid,nickname', 'add_time desc,id desc');
        if ($maintenanList && !is_array($maintenanList)) {
            $maintenanList = $maintenanList->toArray();
        }
        //todo 运维人员
        if (empty($maintenanList)) {
            fdump_api(['device_id' => $device_id, 'reason' => $reason, 'notice' => $notice, 'type' => $type, 'tip' => '--暂无运维人员'], 'maintenan/faultLog', 1);
        }

        $log = $this->getMaintenanceLog($type, $device_id);
        $time = time();
        if ($log && $log['next_key'] && intval($log['next_key']) > 0 && isset($log['next_time'])) {
            if ($log['next_time'] > $time) {
                return false;
            }
            $key = $this->getNextKey($log['next_key']);
            if ($key == -1) {
                //todo 不在阶段性参数内 不处理
                return false;
            } else if ($key == 0) {
                $key = $this->next_key[0];
            }
            $next_key = $key;
            $next_time = $time + ($key * 60);
        } else {
            $next_key = $this->next_key[0];
            $next_time = $time + ($this->next_key[0] * 60);
        }
        $this->title = '';
        $this->remark = '';
        $this->device_name = '';
        $this->village_id = 0;
        $data = [];
        $deviceInfo = $this->getDeviceInfo($device_id, $type, $data);
        if (empty($data)) {
            fdump_api(['device_id' => $device_id, 'reason' => $reason, 'notice' => $notice, 'type' => $type, 'tip' => '--data数据为空'], 'maintenan/faultLog', 1);
            return false;
        }
        $data['reason'] = $reason;
        $data['add_time'] = $time;
        $data['next_key'] = $next_key;
        $data['next_time'] = $next_time;
        $data['maintenan_id'] = empty($maintenanList) ? '' : implode(',', array_column($maintenanList, 'id'));
        $dbHouseMaintenanLog = new HouseMaintenanLog();
        $dbHouseMaintenanLog->addOne($data);

        $configDb = new Config();
        $configTmp = $configDb->getDataOne(array('name' => 'is_maintenan_send'));

        if ($configTmp && isset($configTmp['value']) && $configTmp['value'] < 1) {
            fdump_api(['device_id' => $device_id, 'is_maintenan_send' => $configTmp['value'], 'reason' => $reason, 'notice' => $notice, 'type' => $type, 'data' => $data, 'tip' => '--未开启配置开关'], 'maintenan/faultLog', 1);
            return false;
        }
        $templateNewsService = new TemplateNewsService();
        if (!empty($maintenanList) && $notice == true) {
            $tempKey = 'TM0001801';
            $timeString = date('H时i分', $time);
            foreach ($maintenanList as $v) {
                $send = $templateNewsService->sendTempMsg($tempKey,
                    array(
                        'href' => '',
                        'wecha_id' => $v['openid'],
                        'first' => '报警信息',
                        'keyword1' => $this->title,
                        'keyword2' => $reason,
                        'keyword3' => $timeString,
                        'remark' => $this->remark
                    )
                );
                fdump_api([__LINE__, $send, [
                    'href' => '',
                    'wecha_id' => $v['openid'],
                    'first' => '报警信息',
                    'keyword1' => $this->title,
                    'keyword2' => $reason,
                    'keyword3' => $timeString,
                    'remark' => $this->remark
                ]], 'maintenan/faultLog', 1);
            }
        }
        fdump_api(['village_id' => $this->village_id], 'maintenan/faultLog', 1);
        if ($this->village_id > 0) {
            $dbHouseVillageInfo = new HouseVillageInfo();
            $whereHouseVillageInfo = [];
            $whereHouseVillageInfo[] = ['village_id', '=', $this->village_id];
            $village_info_extend = $dbHouseVillageInfo->getOne($whereHouseVillageInfo, 'urge_notice_type');
            if ($village_info_extend && !is_array($village_info_extend)) {
                $village_info_extend = $village_info_extend->toArray();
            }
            $urge_notice_type = isset($village_info_extend['urge_notice_type']) && $village_info_extend['urge_notice_type'] ? $village_info_extend['urge_notice_type'] : 3;

            //发短信
            $workers = array();
            $whereMaintenanWorker = [];
            $whereMaintenanWorker[] = ['village_id', '=', $this->village_id];
            $whereMaintenanWorker[] = ['is_del_time', '=', 0];
            $dbHouseMaintenanWorker = new HouseMaintenanWorker();
            $workerIds = $dbHouseMaintenanWorker->getColumn($whereMaintenanWorker, 'wid');
            if (empty($workerIds)) {
                $workers = [];
            } else {
                $dbHouseWorker = new HouseWorker();
                $whereWork = [];
                $whereWork[] = ['wid', 'in', $workerIds];
                $whereWork[] = ['status', '=', 1];
                $whereWork[] = ['is_del', '=', 0];
                $workers = $dbHouseWorker->getAll($whereWork, 'wid,name,village_id,nickname,phone,openid');
                if ($workers && !is_array($workers)) {
                    $workers = $workers->toArray();
                }
            }
            fdump_api(['workers' => $workers], 'maintenan/faultLog', 1);
            if (in_array($urge_notice_type, array(1, 3)) && !empty($workers)) {
                $timeTip = date('Y-m-d H:i:s', $time);
                foreach ($workers as $vv) {
                    if (!empty($vv['phone']) && strlen($vv['phone']) == 11) {
                        $text = '您所管理的' . $this->title . '，有' . $this->device_name . '智能设备于' . $timeTip . ' 离线，请您尽快处理。';
                        $sms_data = array(
                            'mer_id' => 0,
                            'store_id' => 0,
                            'sendto' => 'worker',
                            'content' => $text,
                            'mobile' => $vv['phone'],
                            'uid' => 0,
                            'type' => 'house_worker',
                            'village_id' => $this->village_id
                        );
                        $sms = (new SmsService())->sendSms($sms_data);
                    }
                }

            }
            //发模板消息
            if (in_array($urge_notice_type, array(2, 3)) && !empty($workers)) {
                $tempKey = 'OPENTM400166399';
                $timeString = date('Y-m-d H:i', $time);
                foreach ($workers as $vv) {
                    if (!empty($vv['openid'])) {
                        $send = $templateNewsService->sendTempMsg($tempKey,
                            array(
                                'href' => '',
                                'wecha_id' => $vv['openid'],
                                'first' => '报警信息',
                                'keyword1' => $this->title . '，有' . $this->device_name . '智能设备离线',
                                'keyword2' => '报警信息',
                                'keyword3' => $timeString,
                                'keyword4' => $reason,
                                'remark' => $this->remark
                            )
                        );
                    }
                }

            }
        }
    }

    /**
     * 故障解除
     * @param $device_id
     * @param string $type
     * @return bool
     */
    public function normalLog($device_id, $type = DeviceConst::DEVICE_TYPE_FACE){
        $log = $this->getMaintenanceLog($type, $device_id);
        if (isset($log['next_key']) && intval($log['next_key']) > 0) {
            $dbHouseMaintenanLog = new HouseMaintenanLog();
            $whereLog = [];
            $whereLog[] = ['id', '=', $log['id']];
            $save = ['next_key' => 0];
            $dbHouseMaintenanLog->save_one($whereLog, $save);
        }
        return true;
    }
    
    
    // 1:人脸门禁 2:充电桩 3人脸识别摄像机 4智能快递柜 5智慧停车 6 蓝牙门禁
    protected function getMaintenanceLog($type, $device_id) {
        $dbHouseMaintenanLog = new HouseMaintenanLog();
        switch ($type) {
            case DeviceConst::DEVICE_TYPE_FACE:
                $device_type = 1;
                break;
            case DeviceConst::DEVICE_TYPE_CHARGE_PILE:
                $device_type = 2;
                break;
            case DeviceConst::DEVICE_TYPE_CAMERA:
                $device_type = 3;
                break;
            case DeviceConst::DEVICE_TYPE_INTELLIGENT_EXPRESS_CABINET:
                $device_type = 4;
                break;
            case DeviceConst::DEVICE_TYPE_SMART_PARKING:
                $device_type = 5;
                break;
            case DeviceConst::DEVICE_TYPE_BLUETOOTH_DOOR:
                $device_type = 6;
                break;
            case DeviceConst::DEVICE_TYPE_GARBAGE_COLLECTION:
                $device_type = 7;
                break;
        }
        if (isset($device_type) && $device_id) {
            $where = [];
            $where[] = ['device_type', '=', $device_type];
            $where[] = ['device_id',   '=', $device_id];
            $log = $dbHouseMaintenanLog->get_one($where, true, 'id desc');
            if ($log && !is_array($log)) {
                $log = $log->toArray();
            }
            if (!$log) {
                $log = [];
            }
            return $log;
        } else {
            return [];
        }
    }
    
    protected function getDeviceInfo($device_id, $type, &$data) {
        $deviceInfo = [];
        switch ($type) {
            case DeviceConst::DEVICE_TYPE_FACE:
                $whereFace = [];
                $whereFace[] = ['is_del',    '=', 0];
                $whereFace[] = ['device_id', '=', $device_id];
                $deviceInfo = (new HouseFaceDeviceService())->getHouseFaceDeviceInfo($whereFace);
                if ($deviceInfo && !is_array($deviceInfo)) {
                    $deviceInfo = $deviceInfo->toArray();
                }
                if(empty($deviceInfo) || !isset($deviceInfo['village_id'])){
                    return [];
                }
                $device_type  = 1;
                $this->village_id   = $deviceInfo['village_id'];
                $dbHouseVillage = new HouseVillage();
                $village_info = $dbHouseVillage->getOne($this->village_id, 'village_name');
                $village_name = isset($village_info['village_name']) ? $village_info['village_name'] : '';
                $data['device_type']   = $device_type;
                $data['village_id']    = $this->village_id;
                $data['village_name']  = $village_name;
                $data['device_id']     = $device_id;
                $data['device_name']   = $deviceInfo['device_name'];
                if ($deviceInfo['floor_id'] < 0 && isset($deviceInfo['public_area_id']) && $deviceInfo['public_area_id']) {
                    $dbHouseVillagePublicArea = new HouseVillagePublicArea();
                    $where = [];
                    $where[] = ['public_area_id', '=', $deviceInfo['public_area_id']];
                    $areaInfo = $dbHouseVillagePublicArea->getOne($where, 'public_area_name');
                    if ($areaInfo && !is_array($areaInfo)) {
                        $areaInfo = $areaInfo->toArray();
                    }
                    $data['address'] = isset($areaInfo['public_area_name']) ? $areaInfo['public_area_name'] : '';
                } elseif ($deviceInfo['floor_id'] < 0) {
                    $data['address'] = '大门';
                } elseif ($deviceInfo['floor_id'] > 0) {
                    $dbHouseVillageSingle = new HouseVillageSingle();
                    $dbHouseVillageFloor  = new HouseVillageFloor();
                    $whereFloor = [];
                    $whereFloor[] = ['floor_id', '=', $deviceInfo['floor_id']];
                    $floorInfo = $dbHouseVillageFloor->getOne($whereFloor, 'floor_id,floor_name,single_id');
                    if ($floorInfo && !is_array($floorInfo)) {
                        $floorInfo = $floorInfo->toArray();
                    }
                    $single_id  = isset($floorInfo['single_id'])  ? $floorInfo['single_id']  : 0;
                    $floor_name = isset($floorInfo['floor_name']) ? $floorInfo['floor_name'] : ''; 
                    $whereSingle = [];
                    $whereSingle[] = ['id', '=', $single_id];
                    $singleInfo = $dbHouseVillageSingle->getOne($whereSingle, 'id,single_name');
                    if ($singleInfo && !is_array($singleInfo)) {
                        $singleInfo = $singleInfo->toArray();
                    }
                    $single_name = isset($singleInfo['single_name']) ? $singleInfo['single_name'] : '';
                    $data['address'] = $single_name . $floor_name;
                } else {
                    $data['address'] = '';
                }
                $this->title       = $village_name;
                $this->device_name = $data['device_name'];
                $this->remark      = $data['device_name'];
                break;
            case DeviceConst::DEVICE_TYPE_CHARGE_PILE:
                $dbHouseVillagePileEquipment = new HouseVillagePileEquipment();
                $whereFace = [];
                $whereFace[] = ['is_del', '=', 0];
                $whereFace[] = ['id',     '=', $device_id];
                $deviceInfo = $dbHouseVillagePileEquipment->getInfo($whereFace);
                if ($deviceInfo && !is_array($deviceInfo)) {
                    $deviceInfo = $deviceInfo->toArray();
                }
                if(empty($deviceInfo) || !isset($deviceInfo['village_id']) ){
                    return [];
                }
                $device_type  = 2;
                $this->village_id   = $deviceInfo['village_id'];
                $dbHouseVillage = new HouseVillage();
                $village_info = $dbHouseVillage->getOne($this->village_id, 'village_name');
                $village_name = isset($village_info['village_name']) ? $village_info['village_name'] : '';
                $data['device_type']   = $device_type;
                $data['village_id']    = $this->village_id;
                $data['village_name']  = $village_name;
                $data['device_id']     = $device_id;
                $data['device_name']   = $deviceInfo['equipment_name'];
                $data['address']       = '--';
                $this->title       = $village_name;
                $this->device_name = $data['device_name'];
                $this->remark      = $data['device_name'];
                break;
            case DeviceConst::DEVICE_TYPE_CAMERA:
                $whereCamera = [];
                $whereCamera[] = ['camera_status', '<>', 4];
                $whereCamera[] = ['camera_id', '=', $device_id];
                $cameraField = true;
                $deviceInfo = (new HouseFaceDeviceService())->getCameraInfo($whereCamera, $cameraField);
                if ($deviceInfo && !is_array($deviceInfo)) {
                    $deviceInfo = $deviceInfo->toArray();
                }
                if(empty($deviceInfo)|| !isset($deviceInfo['village_id'])){
                    return [];
                }
                $device_type  = 3;
                $this->village_id   = $deviceInfo['village_id'];
                $dbHouseVillage = new HouseVillage();
                $village_info = $dbHouseVillage->getOne($this->village_id, 'village_name');
                $village_name = isset($village_info['village_name']) ? $village_info['village_name'] : '';
                $data['device_type']   = $device_type;
                $data['village_id']    = $this->village_id;
                $data['village_name']  = $village_name;
                $data['device_id']     = $device_id;
                $data['device_name']   = $deviceInfo['camera_name'];
                if ($deviceInfo['floor_id'] < 0 && isset($deviceInfo['public_area_id']) && $deviceInfo['public_area_id']) {
                    $dbHouseVillagePublicArea = new HouseVillagePublicArea();
                    $where = [];
                    $where[] = ['public_area_id', '=', $deviceInfo['public_area_id']];
                    $areaInfo = $dbHouseVillagePublicArea->getOne($where, 'public_area_name');
                    if ($areaInfo && !is_array($areaInfo)) {
                        $areaInfo = $areaInfo->toArray();
                    }
                    $data['address'] = isset($areaInfo['public_area_name']) ? $areaInfo['public_area_name'] : '';
                } elseif ($deviceInfo['floor_id'] < 0) {
                    $data['address'] = '大门';
                } elseif ($deviceInfo['floor_id'] > 0) {
                    $dbHouseVillageSingle = new HouseVillageSingle();
                    $dbHouseVillageFloor  = new HouseVillageFloor();
                    $whereFloor = [];
                    $whereFloor[] = ['floor_id', '=', $deviceInfo['floor_id']];
                    $floorInfo = $dbHouseVillageFloor->getOne($whereFloor, 'floor_id,floor_name,single_id');
                    if ($floorInfo && !is_array($floorInfo)) {
                        $floorInfo = $floorInfo->toArray();
                    }
                    $single_id  = isset($floorInfo['single_id'])  ? $floorInfo['single_id']  : 0;
                    $floor_name = isset($floorInfo['floor_name']) ? $floorInfo['floor_name'] : '';
                    $whereSingle = [];
                    $whereSingle[] = ['id', '=', $single_id];
                    $singleInfo = $dbHouseVillageSingle->getOne($whereSingle, 'id,single_name');
                    if ($singleInfo && !is_array($singleInfo)) {
                        $singleInfo = $singleInfo->toArray();
                    }
                    $single_name = isset($singleInfo['single_name']) ? $singleInfo['single_name'] : '';
                    $data['address'] = $single_name . $floor_name;
                } else {
                    $data['address'] = '';
                }
                $this->title       = $village_name;
                $this->device_name = $data['device_name'];
                $this->remark      = $data['device_name'];
                break;
            case DeviceConst::DEVICE_TYPE_INTELLIGENT_EXPRESS_CABINET:
                $device_type = 4;
                break;
            case DeviceConst::DEVICE_TYPE_SMART_PARKING:
                $device_type = 5;
                break;
            case DeviceConst::DEVICE_TYPE_BLUETOOTH_DOOR:
                $device_type = 6;
                break;
            case DeviceConst::DEVICE_TYPE_GARBAGE_COLLECTION:
                $device_type = 7;
                $deviceInfo = (new GarbageCollectionMachineList())->where('id',$device_id)->field('village_id,name,address')->find();
                if(empty($deviceInfo) || !isset($deviceInfo['village_id'])){
                    return [];
                }
                $this->village_id   = $deviceInfo['village_id'];
                $dbHouseVillage = new HouseVillage();
                $village_info = $dbHouseVillage->getOne($this->village_id, 'village_name');
                $village_name = isset($village_info['village_name']) ? $village_info['village_name'] : '';
                $data['device_type']   = $device_type;
                $data['village_id']    = $this->village_id;
                $data['village_name']  = $village_name;
                $data['device_id']     = $device_id;
                $data['device_name']   = $deviceInfo['name'];
                $data['address'] = $deviceInfo['address'];
                $this->title       = $village_name;
                $this->device_name = $data['device_name'];
                $this->remark      = $data['device_name'];
                break;
        }
        return $deviceInfo;
    }

    //todo 获取下一个参数值
    protected function getNextKey($value)
    {
        $data = 0;
        $next_key = $this->next_key;
        foreach ($next_key as $k => $v) {
            if ($v == $value) {
                if (isset($next_key[$k + 1])) {
                    $data = $next_key[$k + 1];
                    break;
                }else{
                    $data=-1;
                }
            }
        }
        return $data;
    }
}