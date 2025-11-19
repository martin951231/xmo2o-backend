<?php

namespace app\thirdAccess\model\service;


use app\common\model\service\UserService;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\HouseVillageUserBind;

use app\community\model\db\HouseVillage;
use app\community\model\db\HouseFaceDevice;
use app\community\model\db\HouseUserLog;
use app\community\model\service\HouseNewParkingService;
use app\community\model\service\HouseVillageService;
use think\facade\Db;
class DevLogRecordService
{
    public $device_type_value = array(1 => 'A1智能门禁机', 2 => 'A2智能门禁机', 3 => 'A3智能门禁机', 4 => 'A4智能门禁机', 5 => 'A5智能门禁机', 6 => 'A6智能门禁机', 7 => 'A7智能门禁机', 8 => 'A8智能门禁机', 9 => 'A9智能门禁机', 10 => 'A10智能门禁机', 21 => 'D1智能门禁机', 22 => 'D2智能门禁机', 23 => 'D3智能门禁机', 24 => 'D4智能门禁机', 25 => 'D5智能门禁机', 26 => 'D6智能门禁机', 27 => 'D7智能门禁机', 28 => 'D8智能门禁机', 29 => '朵普智能门禁机', 30 => '260云智能门禁机', 31 => '西函智能门禁机', 61 => 'A185智能门禁机', 88 => '大华人脸门禁',);

    public function getVillageData($paramData = array())
    {
        $whereVillage = [];
        $whereVillage[] = ['status', '=', 1];
        if (isset($paramData['property_id']) && $paramData['property_id']) {
            $whereVillage[] = ['property_id', '=', intval($paramData['property_id'])];
        }
        $arr = array('total_num' => 0);
        $fieldVillage = 'village_id,village_name,village_address,property_id';
        $orderVillage = 'village_id DESC';
        $page = isset($paramData['page']) && $paramData['page'] ? intval($paramData['page']) : 0;
        $limit = isset($paramData['limit']) && $paramData['limit'] ? intval($paramData['limit']) : 20;
        $villages = array();
        $houseVillageDb = new HouseVillage();
        $villagesObj = $houseVillageDb->getVillageList($whereVillage, $fieldVillage, $page, $limit, $orderVillage);
        if (!empty($villagesObj) && !$villagesObj->isEmpty()) {
            $villages = $villagesObj->toArray();
            $total_num = $houseVillageDb->getNum($whereVillage);
            $arr['total_num'] = $total_num > 0 ? $total_num : 0;
        }
        $arr['list'] = $villages;
        return $arr;
    }

    /***
     **获取人脸门禁设备
     *****/
    public function getFaceDoorList($paramData = array())
    {
        $whereArr = [];
        $whereArr[] = ['is_del', '<', 4];
        if (isset($paramData['village_id']) && $paramData['village_id']) {
            $whereArr[] = ['village_id', '=', $paramData['village_id']];
        }
        $arr = array('total_num' => 0);
        $fieldStr = 'device_id,device_name,device_type,device_sn,village_id,device_status,device_direction,last_open_time,device_ip';
        $orderBy = 'device_id desc';
        $page = isset($paramData['page']) && $paramData['page'] ? intval($paramData['page']) : 0;
        $limit = isset($paramData['limit']) && $paramData['limit'] ? intval($paramData['limit']) : 20;
        $faceDevices = array();
        $houseFaceDeviceDb = new HouseFaceDevice();
        $faceDeviceObj = $houseFaceDeviceDb->getWhereList($whereArr, $fieldStr, $orderBy, $page, $limit);
        if (!empty($faceDeviceObj) && !$faceDeviceObj->isEmpty()) {
            $faceDevices = $faceDeviceObj->toArray();
            foreach ($faceDevices as $kk => $vv) {
                $faceDevices[$kk]['device_type_str'] = isset($this->device_type_value[$vv['device_type']]) ? $this->device_type_value[$vv['device_type']] : '';
            }
            $total_num = $houseFaceDeviceDb->getCount($whereArr);
            $arr['total_num'] = $total_num > 0 ? $total_num : 0;
        }
        $arr['list'] = $faceDevices;
        return $arr;
    }

    /***
     **获取人脸门禁进出记录
     *****/
    public function getFaceDoorLogRecord($paramData = array())
    {
        $whereArr = [];
        $whereArr[] = ['log_id', '>', 0];
        if (isset($paramData['village_id']) && $paramData['village_id']) {
            $whereArr[] = ['log_business_id', '=', $paramData['village_id']];
        }
        $xstatus = isset($paramData['xstatus']) ? $paramData['xstatus'] : 1;
        $xstatus = intval($xstatus);
        switch ($xstatus) {
            case 0:
                break;
            case 1:
                $whereArr[] = ['log_status', '=', 0];
                break;
            case 2:
                $whereArr[] = ['log_status', '>', 0];
                break;
            default:
                break;
        }
        $device_id_arr = array();
        if (isset($paramData['device_id']) && $paramData['device_id']) {
            $device_id_arr[] = $paramData['device_id'];
        }
        $houseFaceDeviceDb = new HouseFaceDevice();
        if (isset($paramData['device_sn']) && $paramData['device_sn']) {

            $whereDevice = array(['device_sn', '=', $paramData['device_sn']]);
            if (isset($paramData['village_id']) && $paramData['village_id']) {
                $whereDevice[] = ['village_id', '=', $paramData['village_id']];
            }
            $faceDevice = $houseFaceDeviceDb->getOne($whereDevice, 'device_id');
            if ($faceDevice && !$faceDevice->isEmpty()) {
                $device_id_arr[] = $faceDevice['device_id'];
            }
        }
        if ($device_id_arr) {
            $device_id_arr = array_unique($device_id_arr);
            $deviceNum = count($device_id_arr);
            if ($deviceNum == 1) {
                $whereArr[] = ['device_id', '=', $device_id_arr['0']];
            } else {
                $whereArr[] = ['device_id', 'in', $device_id_arr['0']];
            }
        }
        //$whereArr[] = ['log_from', 'in', [1, 2, 3, 21,31]];
        if (isset($paramData['start_date']) && $paramData['start_date']) {
            $start_time = strtotime($paramData['start_date']);
            $whereArr[] = ['log_time', '>=', $start_time];
        }
        if (isset($paramData['end_date']) && $paramData['end_date']) {
            $end_time = strtotime($paramData['end_date']);
            $end_time = $end_time > 0 ? ($end_time + 86399) : 0;
            $whereArr[] = ['log_time', '<=', $end_time];
        }

        $arr = array('total_num' => 0);
        $fieldStr = 'log_id,uid,log_bind_id,log_business_id,log_name,log_from,device_id,log_status,log_detail,card_number,log_time,device_sn,person_name,cut_image,direction_type,add_time,device_event_msg';
        $orderBy = 'log_id desc';
        $page = isset($paramData['page']) && $paramData['page'] ? intval($paramData['page']) : 0;
        $limit = isset($paramData['limit']) && $paramData['limit'] ? intval($paramData['limit']) : 20;
        $logList = array();
        $houseUserLogDb = new HouseUserLog();
        $houseVillageDb = new HouseVillage();
        $houseVillageUserBind = new HouseVillageUserBind();
        $house_village_service = new HouseVillageService();
        $logListObj = $houseUserLogDb->getTableList($whereArr, $fieldStr, $page, $limit, $orderBy);
        $defaultImg = cfg('site_url') . '/static/default_img.png';
        if (!empty($logListObj) && !$logListObj->isEmpty()) {
            $logList = $logListObj->toArray();
            $faceDeviceArr = array();
            $villageArr = array();
            foreach ($logList as $kk => $vv) {
                $logList[$kk]['device_name'] = '';
                $logList[$kk]['device_sn'] = $vv['device_sn'];
                $logList[$kk]['device_type'] = '';
                $logList[$kk]['device_type_str'] = '';
                $logList[$kk]['village_name'] = '';
                $logList[$kk]['village_address'] = '';
                $logList[$kk]['property_id'] = '';
                $logList[$kk]['village_id'] = '';
                $logList[$kk]['person_phone'] = '';
                $logList[$kk]['person_address'] = '';
                $logList[$kk]['id_card'] = '';
                $village_id = $vv['log_business_id'];
                if (isset($faceDeviceArr[$vv['device_id']])) {
                    $logList[$kk]['device_name'] = $faceDeviceArr[$vv['device_id']]['device_name'];
                    $logList[$kk]['device_sn'] = $faceDeviceArr[$vv['device_id']]['device_sn'];
                    $logList[$kk]['device_type'] = $faceDeviceArr[$vv['device_id']]['device_type'];
                    $village_id = $faceDeviceArr[$vv['device_id']]['village_id'];
                    $logList[$kk]['device_type_str'] = isset($this->device_type_value[$faceDeviceArr[$vv['device_id']]['device_type']]) ? $this->device_type_value[$faceDeviceArr[$vv['device_id']]['device_type']] : '';
                } else {
                    $whereDevice = array(['device_id', '=', $vv['device_id']]);
                    $faceDevice = $houseFaceDeviceDb->getOne($whereDevice, 'village_id,device_name, device_sn, device_type');
                    if ($faceDevice && !$faceDevice->isEmpty()) {
                        $faceDeviceArr[$vv['device_id']] = $faceDevice->toArray();
                        $logList[$kk]['device_name'] = $faceDevice['device_name'];
                        $logList[$kk]['device_sn'] = $faceDevice['device_sn'];
                        $logList[$kk]['device_type'] = $faceDevice['device_type'];
                        $logList[$kk]['device_type_str'] = isset($this->device_type_value[$faceDevice['device_type']]) ? $this->device_type_value[$faceDevice['device_type']] : '';
                        $village_id = $faceDevice['village_id'];
                    }
                }
                $logList[$kk]['village_id'] = $village_id;
                if ($village_id > 0) {
                    if (isset($villageArr[$village_id])) {
                        $logList[$kk]['village_name'] = $villageArr[$village_id]['village_name'];
                        $logList[$kk]['village_address'] = $villageArr[$village_id]['village_address'];
                        $logList[$kk]['property_id'] = $villageArr[$village_id]['property_id'];
                    } else {
                        $houseVillageObj = $houseVillageDb->getInfo(array('village_id' => $village_id), 'village_id,village_name,village_address,property_id');
                        if ($houseVillageObj && !$houseVillageObj->isEmpty()) {
                            $logList[$kk]['village_name'] = $houseVillageObj['village_name'];
                            $logList[$kk]['village_address'] = $houseVillageObj['village_address'];
                            $logList[$kk]['property_id'] = $houseVillageObj['property_id'];
                            $villageArr[$village_id] = $houseVillageObj->toArray();
                        }
                    }
                }
                if ($vv['log_bind_id'] > 0) {
                    $whereBind = array(['pigcms_id', '=', $vv['log_bind_id']], ['village_id', '=', $village_id]);
                    $userBindObj = $houseVillageUserBind->getOne($whereBind, 'pigcms_id, village_id, single_id, floor_id, layer_id, vacancy_id, name, id_card,phone, address', 'pigcms_id DESC');
                    if ($userBindObj && !$userBindObj->isEmpty()) {
                        $logList[$kk]['person_name'] = $userBindObj['name'];
                        $logList[$kk]['person_phone'] = $userBindObj['phone'];
                        $logList[$kk]['id_card'] = $userBindObj['id_card'];
                        $person_address = $house_village_service->getSingleFloorRoom($userBindObj['single_id'], $userBindObj['floor_id'], $userBindObj['layer_id'], $userBindObj['vacancy_id'], $userBindObj['village_id']);
                        $logList[$kk]['person_address'] = $person_address;
                    }
                }
                $log_from_txt = $this->getLogFromText($vv['log_from'], $vv['card_number']);
                $logList[$kk]['open_door_des'] = $log_from_txt;
                $open_door_status = $this->getOpenDoorStatus($vv['log_status'], $vv['log_from']);
                $logList[$kk]['open_door_status'] = $open_door_status;
                if (!empty($vv['log_time'])) {
                    $logList[$kk]['log_time'] = date('Y-m-d H:i:s', $vv['log_time']);
                } else {
                    $logList[$kk]['log_time'] = '';
                }
                $face_img = '';
                if ($vv['log_detail']) {
                    $log_detail = @unserialize($vv['log_detail']);
                    if (!$log_detail) {
                        $log_detail = json_decode($vv['log_detail'], true);
                    }
                    if ($log_detail && isset($log_detail['face_img'])) {
                        $face_img = $log_detail['face_img'];
                    } elseif ($log_detail && isset($log_detail['imgUrl'])) {
                        $face_img = $log_detail['imgUrl'];
                    } elseif ($log_detail && isset($log_detail['mediaUrls'])) {
                        $face_img = $log_detail['mediaUrls'];
                    } elseif ($log_detail && isset($log_detail['pictureURL'])) {
                        $face_img = $log_detail['pictureURL'];
                    } elseif ($log_detail && isset($log_detail['faceCapture'][0]['bkgUrl'])) {
                        $face_img = $log_detail['faceCapture'][0]['bkgUrl'];
                    } elseif ($log_detail && isset($log_detail['face_img_url'])) {
                        $face_img = $log_detail['face_img_url'];
                    } elseif ($log_detail && isset($log_detail['capture_img_url'])) {
                        $face_img = $log_detail['capture_img_url'];
                    } elseif ($log_detail && isset($log_detail['capture_img'])) {
                        $face_img = $log_detail['capture_img'];
                    }

                }
                if (isset($vv['cut_image']) && trim($vv['cut_image'])) {
                    $face_img = $vv['cut_image'];
                }
                $face_img = $face_img ? replace_file_domain($face_img) : '';
                $logList[$kk]['cut_image'] = $face_img;
                unset($logList[$kk]['log_detail']);
            }
            $total_num = $houseUserLogDb->getCount($whereArr);
            $arr['total_num'] = $total_num > 0 ? $total_num : 0;
        }
        $arr['list'] = $logList;
        return $arr;
    }

    public function getLogFromText($logFrom, $card_number = '')
    {
        $logFromTxt = '';
        switch ($logFrom) {
            case 1:
                $logFromTxt = '人脸识别';
                break;
            case 2:
                $logFromTxt = '用户操作';
                break;
            case 3:
                if ($card_number && intval($card_number) > 100000000000) {
                    $logFromTxt = 'IC卡刷卡(访客二维码)';
                } else {
                    $logFromTxt = 'IC卡刷卡';
                }
                break;
            case 6:
                $logFromTxt = '摄像机抓拍';
                break;
            case 10:
                $logFromTxt = '证件开门';
                break;
            case 11:
                $logFromTxt = '身份证开门';
                break;
            case 21:
                $logFromTxt = '蓝牙开门';
                break;
            case 31:
                if (strpos($card_number, 'owner') !== false) {
                    $logFromTxt = '二维码开门(业主)';
                } elseif (strpos($card_number, 'family') !== false) {
                    $logFromTxt = '二维码开门(家属)';
                } elseif (strpos($card_number, 'tenant') !== false) {
                    $logFromTxt = '二维码开门(租客)';
                } elseif (strpos($card_number, 'worker') !== false) {
                    $logFromTxt = '二维码开门(工作人员)';
                } elseif (strpos($card_number, 'pass') !== false) {
                    $logFromTxt = '二维码开门(通行证)';
                } elseif (strpos($card_number, 'visitor') !== false) {
                    $logFromTxt = '二维码开门(访客)';
                } else {
                    $logFromTxt = '二维码开门';
                }
                break;
            case 41:
                $logFromTxt = '密码开门';
                break;
            case 42:
                $logFromTxt = '动态密码开锁';
                break;
            case 45:
                $logFromTxt = '指纹开门';
                break;
            case 51:
                $logFromTxt = '高空抛物';
                break;
            case 61:
            case 62:
                $logFromTxt = '健康码开门';
                break;
            case 70:
                $logFromTxt = '组合认证刷卡加密码开锁';
                break;
            case 71:
                $logFromTxt = '组合认证刷卡加指纹开锁';
                break;
            case 72:
                $logFromTxt = '组合认证刷卡加指纹加密码开锁';
                break;
            case 73:
                $logFromTxt = '组合认证指纹加密码开锁';
                break;
            case 74:
                $logFromTxt = '组合认证人脸加指纹开锁';
                break;
            case 75:
                $logFromTxt = '组合认证人脸加密码开锁';
                break;
            case 76:
                $logFromTxt = '组合认证人脸加刷卡开锁';
                break;
            case 77:
                $logFromTxt = '组合认证人脸加密码加指纹开锁';
                break;
            case 78:
                $logFromTxt = '组合认证人脸加刷卡加指纹开锁';
                break;
            case 79:
                $logFromTxt = '组合认证工号加指纹开锁';
                break;
            case 80:
                $logFromTxt = '组合认证工号加指纹加密码开锁';
                break;
            case 81:
                $logFromTxt = '组合认证工号加人脸开锁';
                break;
            case 90:
                $logFromTxt = '组合认证';
                break;
        }
        return $logFromTxt;
    }

    public function getOpenDoorStatus($log_status = 0, $log_from = 0)
    {
        $log_status = intval($log_status);
        $statusStr = '';
        switch ($log_status) {
            case 0:
                $statusStr = '成功';
                break;
            case 1:
                $statusStr = '比对失败';
                if ($log_from == 2) {
                    $statusStr = '开门失败';
                }
                break;
            case 2:
                $statusStr = '无模版';
                break;
            case 3:
                $statusStr = '无效卡';
                break;
            case 4:
                $statusStr = '无现场照';
                break;
            case 5:
                $statusStr = '非活体';
                break;
            case 6:
                $statusStr = '证件过期';
                break;
            case 7:
                $statusStr = '超过时间限制';
                break;
            case 8:
                $statusStr = '超过此数限制';
                break;
            case 100:
                $statusStr = '未知错误';
                break;
        }
        return $statusStr;
    }

    public function getParkCarAccessRecord($paramData = array())
    {
        $db_house_new_parking_service = new HouseNewParkingService();
        $where = [];
        $where[] = ['a.record_id', '>', 0];
        $village_id = 0;
        $where[] = ['a.del_time', '<', 1];
        if (isset($paramData['village_id']) && $paramData['village_id']) {
            $where[] = ['a.park_id', '=', $paramData['village_id']];
            $where[] = ['b.park_id', '=', $paramData['village_id']];
            $village_id = $paramData['village_id'];
        }else{
            $where[] = ['b.park_id', 'exp',  Db::raw('= `a`.`park_id`')];
        }
        if(isset($paramData['park_type']) && $paramData['park_type']==1){
             //1以月租车通行的记录
            $where[] = ['a.accessMode', '=', 5];
        }elseif (isset($paramData['park_type']) && $paramData['park_type']==2){
            //2以临时车通行的记录
            //$where[] = ['b.accessMode', 'in', [3, 4, 6, 9, 7]];
            $where[] = ['a.accessMode', '<>', 5];
        }
        if(isset($paramData['date_type']) && $paramData['date_type']==1){
            //1日期按进场日期搜索
            $where[] = ['a.accessType', '=', 1];
            $where[] = ['b.accessType', '=', 2];
        }elseif (isset($paramData['date_type']) && $paramData['date_type']==2){
            //2日期按出场日期搜索
            $where[] = ['a.accessType', '=', 2];
            $where[] = ['b.accessType', '=', 1];
        }

        $where[] = ['a.order_id', '<>', ''];
        $where[] = ['a.del_time', '<', 10];
        $where[] = ['b.del_time', '<', 10];
        /*
        if (isset($paramData['pay_status']) && $paramData['pay_status'] == 'free') {
            $where[] = ['b.total', '=', 0];
        } else if (isset($paramData['pay_status']) && $paramData['pay_status'] == 'paied') {
            $where[] = ['b.total', '>', 0];
        }
        if (isset($paramData['stop_time']) && $paramData['stop_time'] > 0) {
            //小时转化成秒
            $stop_time = $paramData['stop_time'] * 3600;
            $where['_string'] = '(b.accessTime-a.accessTime)<=' . $stop_time;
        }
        */
        
        if (isset($paramData['start_date']) && !empty($paramData['start_date'])) {
            $where[] = ['a.accessTime', '>=', strtotime($paramData['start_date'] . ' 00:00:00')];
        }
        if (isset($paramData['end_date']) && !empty($paramData['end_date'])) {
            $where[] = ['a.accessTime', '<=', strtotime($paramData['end_date'] . ' 23:59:59')];
        }

        if (isset($paramData['car_number']) && !empty($paramData['car_number'])) {
            //$where[] = ['b.car_number', 'like', '%' . $paramData['car_number'] . '%'];
            $where[] = ['a.car_number', '=', $paramData['car_number']];
        }
        
        $res = $db_house_new_parking_service->getParkCarAccessRecords($where,$paramData);
        return $res;
    }
}