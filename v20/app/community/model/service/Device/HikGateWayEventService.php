<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      海康网关-事件
 */

namespace app\community\model\service\Device;

use app\community\model\db\Car\HandleImg;
use app\community\model\db\Device\CommunityEventAlarm;
use app\community\model\db\Device\HouseDeviceEvent;
use app\community\model\db\Device\HouseDeviceNetwork;
use app\community\model\db\HouseCameraDevice;
use app\community\model\db\HouseUserLog;
use app\community\model\db\HouseVillageVisitor;
use app\community\model\db\HouseVillageVisitorPass;
use app\community\model\service\HouseFaceDeviceService;
use app\community\model\service\NewBuildingService;
use app\consts\DeviceConst;
use app\consts\EventAlarmConst;
use Fastwhal\HikDeviceGateway\AlarmDeviceErrorMsg;

class HikGateWayEventService
{
    /**
     * 获取事件具体描述.
     */
    public function getEventTypeMsg($majorEventType, $subEventType)
    {
        $errorMsgArr = [];
        $eventTypeMsg = [];
        $alarmDeviceErrorMsg = new AlarmDeviceErrorMsg();
        switch ($majorEventType) {
            case 1:
                $errorMsgArr = $alarmDeviceErrorMsg->alarmDevMsg;
                $eventTypeMsg['msg_title'] = '设备报警';
                break;
            case 2:
                $errorMsgArr = $alarmDeviceErrorMsg->deviceAbnormalityMsg;
                $eventTypeMsg['msg_title'] = '设备异常';
                break;
            case 3:
                $errorMsgArr = $alarmDeviceErrorMsg->deviceOperationMsg;
                $eventTypeMsg['msg_title'] = '设备操作';
                break;
            case 5:
                $errorMsgArr = $alarmDeviceErrorMsg->deviceEventMsg;
                $eventTypeMsg['msg_title'] = '设备事件';
                break;
        }
        $eventTypeMsg['msg'] = isset($errorMsgArr[$subEventType]) ? trim($errorMsgArr[$subEventType]) : '';
        return $eventTypeMsg;
    }

    /**
     * 门禁事件详情处理.
     */
    public function handleAccessControllerEvent($event_log)
    {
        fdump_api(['msg' => '门禁事件详情处理', 'line' => __LINE__, 'event_log' => $event_log], 'event/handleAccessControllerEventLog');
        $sHouseFaceDeviceService = new HouseFaceDeviceService();
        $sNewBuildingService = new NewBuildingService();
        $houseUserLog = [];
        $deviceInfo = [];
        $nowTime = time();
        $eventPicture = isset($event_log['eventPicture']) && $event_log['eventPicture'] ? $event_log['eventPicture'] : [];
        $channelID = isset($event_log['channelID']) && $event_log['channelID'] ? intval($event_log['channelID']) : 0;
        // 消息通知时间
        $dateTime = isset($event_log['dateTime']) && $event_log['dateTime'] ? strtotime($event_log['dateTime']) : $nowTime;
        $deviceSerial = isset($event_log['deviceSerial']) && $event_log['deviceSerial'] ? trim($event_log['deviceSerial']) : '';
        $deviceID = isset($event_log['deviceID']) && $event_log['deviceID'] ? trim($event_log['deviceID']) : '';
        $devIndex = isset($event_log['devIndex']) && $event_log['devIndex'] ? trim($event_log['devIndex']) : '';
        $accessControllerEvent = isset($event_log['AccessControllerEvent']) ? $event_log['AccessControllerEvent'] : [];
        $record_log = isset($event_log['record_log']) ? $event_log['record_log'] : [];

        $deviceName = isset($accessControllerEvent['deviceName']) && trim($accessControllerEvent['deviceName']) ? trim($accessControllerEvent['deviceName']) : '';
        $majorEventType = isset($accessControllerEvent['majorEventType']) ? intval($accessControllerEvent['majorEventType']) : '';
        $subEventType = isset($accessControllerEvent['subEventType']) ? intval($accessControllerEvent['subEventType']) : '';
        $eventTypeMsg = (new HikGateWayEventService())->getEventTypeMsg($majorEventType, $subEventType);
        fdump_api(['msg' => '信息-文本', 'line' => __LINE__, 'eventTypeMsg' => $eventTypeMsg], 'event/handleAccessControllerEventLog', 1);

        $device_sn = $deviceID ?? $deviceSerial;
        $whereFace = [];
        $whereFace[] = ['is_del', '=', 0];
        if ($devIndex) {
            $whereFace[] = ['cloud_device_id', '=', $devIndex];
        } else {
            $whereFace[] = ['device_sn', '=', $device_sn];
        }
        $whereFace[] = ['thirdProtocol', 'in', [DeviceConst::HIK_DEVICE_GATEWAY, DeviceConst::HIK_DEVICE_GATEWAY_CUSTOM]];
        $deviceInfo = $sHouseFaceDeviceService->getHouseFaceDeviceInfo($whereFace, 'device_id, device_name, device_sn, village_id, thirdProtocol');
        if ((!isset($deviceInfo['device_id']) || !$deviceInfo['device_id']) && $devIndex) {
            $whereFace = [];
            $whereFace[] = ['is_del', '=', 0];
            $whereFace[] = ['device_sn', '=', $device_sn];
            $whereFace[] = ['thirdProtocol', 'in', [DeviceConst::HIK_DEVICE_GATEWAY, DeviceConst::HIK_DEVICE_GATEWAY_CUSTOM]];
            $deviceInfo = $sHouseFaceDeviceService->getHouseFaceDeviceInfo($whereFace, 'device_id, device_name, device_sn, village_id, thirdProtocol');
        }
        if ($deviceInfo && !is_array($deviceInfo)) {
            $deviceInfo = $deviceInfo->toArray();
        }
        fdump_api(['msg' => '信息-设备信息', 'line' => __LINE__, 'deviceInfo' => $deviceInfo], 'event/handleAccessControllerEventLog', 1);
        $device_id = isset($deviceInfo['device_id']) ? intval($deviceInfo['device_id']) : 0;
        $local_device_name = isset($deviceInfo['device_name']) ? trim($deviceInfo['device_name']) : '';
        $local_device_sn = isset($deviceInfo['device_sn']) ? trim($deviceInfo['device_sn']) : '';
        $village_id = isset($deviceInfo['village_id']) ? intval($deviceInfo['village_id']) : 0;
        $third_protocol = isset($deviceInfo['thirdProtocol']) ? intval($deviceInfo['thirdProtocol']) : 0;
        $device_equipment_type = DeviceConst::DEVICE_TYPE_FACE;
        if ($majorEventType == 2 && in_array($subEventType, [1062, 1063])) {
            // todo 设备离线在线
            // 设备离线在线
            $updateParam = [];
            $updateParam['notify_time'] = $dateTime;
            if ($device_id > 0 && $subEventType == 1063) {
                // 离线
                $reason = $eventTypeMsg ?? '人脸识别终端离线';
                if ($local_device_name && $device_sn) {
                    $reason .= "[设备：{$local_device_name}-序号：{$device_sn}]";
                } elseif ($device_sn) {
                    $reason .= "[序号：{$device_sn}]";
                }
                (new DeviceAlarmEventService())->faultNotice($device_id, $reason, true, $device_equipment_type);
                $updateParam['device_status'] = 2;
            } elseif ($device_id > 0) {
                // 在线
                (new DeviceAlarmEventService())->normalLog($device_id, $device_equipment_type);
                $updateParam['device_status'] = 1;
            }
            if ($device_id > 0 && !empty($updateParam)) {
                // todo 根据通知设备在线离线更新人脸设备状态
                $whereFace = [
                    'device_id' => $device_id
                ];
                $sHouseFaceDeviceService->updateHouseFaceDeviceInfo($whereFace, $updateParam);
            }
            $eventArray = [
                'errorCode' => 1, 'errorMsg' => 'success', 'statusCode' => 1, 'statusString' => 'OK', 'subStatusCode' => 'ok',
            ];
            echo json_encode($eventArray, JSON_UNESCAPED_UNICODE);
            die;
        }
        $dbHouseDeviceEvent = new HouseDeviceEvent();
        $whereDeviceEvent = [];
        $whereDeviceEvent[] = ['device_equipment_type', '=', $device_equipment_type];
        $whereDeviceEvent[] = ['device_id', '=', $device_id];
        $deviceEventInfo = $dbHouseDeviceEvent->getOne($whereDeviceEvent, 'id');
        $deviceEventInfoId = isset($deviceEventInfo['id']) && $deviceEventInfo['id'] ? intval($deviceEventInfo['id']) : 0;
        $device_event = [];
        switch ($majorEventType) {
            case 1:
                $device_event['alarm_dev_code'] = $majorEventType . '-' . $subEventType;
                $device_event['alarm_dev_msg'] = $eventTypeMsg['msg'];
                $device_event['alarm_dev_time'] = $dateTime;
                break;
            case 2:
                $device_event['device_abnormality_code'] = $majorEventType . '-' . $subEventType;
                $device_event['device_abnormality_msg'] = $eventTypeMsg['msg'];
                $device_event['device_abnormality_time'] = $dateTime;
                break;
            case 3:
                $device_event['device_operation_code'] = $majorEventType . '-' . $subEventType;
                $device_event['device_operation_msg'] = $eventTypeMsg['msg'];
                $device_event['device_operation_time'] = $dateTime;
                break;
            case 5:
                $device_event['device_event_code'] = $majorEventType . '-' . $subEventType;
                $device_event['device_event_msg'] = $eventTypeMsg['msg'];
                $device_event['device_event_time'] = $dateTime;
                break;
        }
        if ($deviceEventInfoId > 0) {
            fdump_api(['msg' => '信息-更新事件', 'line' => __LINE__, 'device_event' => $device_event], 'event/handleAccessControllerEventLog', 1);
            $dbHouseDeviceEvent->updateThis(['id' => $deviceEventInfoId], $device_event);
        } else {
            $device_event['device_equipment_type'] = $device_equipment_type;
            $device_event['device_id'] = $device_id;
            if ($local_device_sn) {
                $device_event['device_sn'] = $local_device_sn;
            } else {
                $device_event['device_sn'] = $device_sn;
            }
            $device_event['add_time'] = $nowTime;
            fdump_api(['msg' => '信息-添加事件', 'line' => __LINE__, 'device_event' => $device_event], 'event/handleAccessControllerEventLog', 1);
            $deviceEventInfoId = $dbHouseDeviceEvent->add($device_event);
        }
        fdump_api(['msg' => '设备相关最新信息记录', 'line' => __LINE__, 'device_event' => $device_event, 'deviceEventInfoId' => $deviceEventInfoId], 'event/handleAccessControllerEventLog', 1);

        $log_from = DeviceConst::DEVICE_FACE_OPEN;
        $log_status = 0;
        if ($majorEventType == 3 && $subEventType == 1024) {
            $log_from = DeviceConst::DEVICE_GATE_OPEN;
        }
        if (in_array($majorEventType, [1, 2, 3]) && $log_from != DeviceConst::DEVICE_GATE_OPEN) {
            $eventArray = [
                'errorCode' => 1, 'errorMsg' => 'success', 'statusCode' => 1, 'statusString' => 'OK', 'subStatusCode' => 'ok',
            ];
            echo json_encode($eventArray, JSON_UNESCAPED_UNICODE);
            die;
        }
        // 1-人脸识别门禁 2-用户操作开门 3-IC卡开门 6-摄像机抓拍 21-蓝牙开门记录 31-二维码开门 51-高空抛物
        if ($log_from != DeviceConst::DEVICE_GATE_OPEN && in_array($subEventType, [54, 57, 60, 63, 66, 75, 77])) {
            // 人脸开门成功
            $log_from = DeviceConst::DEVICE_FACE_OPEN;
            $log_status = 0;
        } elseif ($log_from != DeviceConst::DEVICE_GATE_OPEN && in_array($subEventType, [55, 56, 58, 59, 61, 62, 64, 65, 67, 68, 76, 78, 79, 80, 104, 142])) {
            // 人脸开门失败
            $log_from = DeviceConst::DEVICE_FACE_OPEN;
            $log_status = 1;
        } elseif ($subEventType == 33) {
            // 多重认证：远程开门
            $log_from = DeviceConst::DEVICE_GATE_OPEN;
            $log_status = 0;
        } elseif ($log_from != DeviceConst::DEVICE_GATE_OPEN && in_array($subEventType, [1, 2, 40, 43, 50, 60, 66])) {
            // 刷卡开门成功
            $log_from = DeviceConst::DEVICE_IC_CARD_OPEN;
            $log_status = 0;
        } elseif ($log_from != DeviceConst::DEVICE_GATE_OPEN && in_array($subEventType, [3, 4, 5, 7, 8, 9, 12, 13, 41, 42, 44, 45, 119, 162, 163, 188])) {
            // 刷卡开门失败
            $log_from = DeviceConst::DEVICE_IC_CARD_OPEN;
            $log_status = 1;
        } elseif ($log_from != DeviceConst::DEVICE_GATE_OPEN && $subEventType == 105) {
            // 人证开门成功
            $log_from = DeviceConst::DEVICE_CERTIFICATES_OPEN;
            $log_status = 0;
        } elseif ($log_from != DeviceConst::DEVICE_GATE_OPEN && $subEventType == 112) {
            // 人证开门失败
            $log_from = DeviceConst::DEVICE_CERTIFICATES_OPEN;
            $log_status = 1;
        } elseif ($log_from != DeviceConst::DEVICE_GATE_OPEN && $subEventType == 159) {
            // 蓝牙开门成功
            $log_from = DeviceConst::DEVICE_BLUETOOTH_OPEN;
            $log_status = 0;
        } elseif ($log_from != DeviceConst::DEVICE_GATE_OPEN && $subEventType == 160) {
            // 蓝牙开门失败
            $log_from = DeviceConst::DEVICE_BLUETOOTH_OPEN;
            $log_status = 1;
        } elseif ($log_from != DeviceConst::DEVICE_GATE_OPEN && $subEventType == 156) {
            // 二维码开门成功
            $log_from = DeviceConst::DEVICE_QR_CODE_OPEN;
            $log_status = 0;
        } elseif ($log_from != DeviceConst::DEVICE_GATE_OPEN && $subEventType == 181) {
            // 密码开门成功
            $log_from = DeviceConst::DEVICE_PASS_WORD_OPEN;
            $log_status = 0;
        } elseif ($log_from != DeviceConst::DEVICE_GATE_OPEN && $subEventType == 181) {
            // 密码开门成功
            $log_from = DeviceConst::DEVICE_PASS_WORD_OPEN;
            $log_status = 0;
        } elseif ($log_from != DeviceConst::DEVICE_GATE_OPEN && in_array($subEventType, [148, 151])) {
            // 密码开门失败
            $log_from = DeviceConst::DEVICE_PASS_WORD_OPEN;
            $log_status = 1;
        } elseif ($log_from != DeviceConst::DEVICE_GATE_OPEN && in_array($subEventType, [38, 46])) {
            // 指纹开门成功
            $log_from = DeviceConst::DEVICE_FINGERPRINT_OPEN;
            $log_status = 0;
        } elseif ($log_from != DeviceConst::DEVICE_GATE_OPEN && in_array($subEventType, [39, 47, 48, 49])) {
            // 指纹开门失败
            $log_from = DeviceConst::DEVICE_FINGERPRINT_OPEN;
            $log_status = 1;
        } else {
            $log_from = DeviceConst::DEVICE_OTHER_OPEN;
            $log_status = 0;
            return true;
        }
        $houseUserLog['log_from'] = $log_from;
        $houseUserLog['log_status'] = $log_status;
        fdump_api(['msg' => '处理结构', 'line' => __LINE__, 'houseUserLog' => $houseUserLog], 'event/handleAccessControllerEventLog', 1);

        $cardNo = isset($accessControllerEvent['cardNo']) ? trim($accessControllerEvent['cardNo']) : '';
        $name = isset($accessControllerEvent['name']) ? trim($accessControllerEvent['name']) : '';
        $employeeNoString = isset($accessControllerEvent['employeeNoString']) ? trim($accessControllerEvent['employeeNoString']) : '';
        $serialNo = isset($accessControllerEvent['serialNo']) ? intval($accessControllerEvent['serialNo']) : 0;
        $currTemperature = isset($accessControllerEvent['currTemperature']) ? trim($accessControllerEvent['currTemperature']) : '';
        $frontSerialNo = isset($accessControllerEvent['frontSerialNo']) ? intval($accessControllerEvent['frontSerialNo']) : 0;
        !$serialNo && $frontSerialNo && $serialNo = $frontSerialNo + 1;
        $pictureURL = isset($accessControllerEvent['pictureURL']) ? trim($accessControllerEvent['pictureURL']) : '';

        if ($employeeNoString || $cardNo) {
            $whereUserBind = [];
            $whereUserBind[] = ['village_id', '=', $village_id];
            if ($employeeNoString) {
                $whereUserBind[] = ['pigcms_id', '=', $employeeNoString];
            } else {
                $whereUserBind[] = ['ic_card', '=', $cardNo];
                $whereUserBind[] = ['status', '=', 1];
            }
            $userBind = $sNewBuildingService->getHouseVillageUserBindFindMethod($whereUserBind, 'pigcms_id, type, uid, name', false);
            $houseUserLog['uid'] = isset($userBind['uid']) ? intval($userBind['uid']) : 0;
            $houseUserLog['person_name'] = isset($userBind['person_name']) ? trim($userBind['person_name']) : '';
            $houseUserLog['log_bind_id'] = isset($userBind['pigcms_id']) ? intval($userBind['pigcms_id']) : 0;
        }
        fdump_api(['msg' => '处理结构', 'line' => __LINE__, 'houseUserLog' => $houseUserLog], 'event/handleAccessControllerEventLog', 1);
        $keyEventParams = ['deviceName', 'cardNo', 'name', 'employeeNoString', 'serialNo', 'currTemperature', 'frontSerialNo'];
        $accessControllerEventParams = unsetParam($accessControllerEvent, $keyEventParams);
        $keys = ['channelID', 'deviceID'];
        $log_detail = $event_log;
        $log_detail['AccessControllerEvent'] = $accessControllerEventParams;
        $log_detail = unsetParam($log_detail, $keys);

        if (!empty($log_detail)) {
            $houseUserLog['log_detail'] = json_encode($log_detail, JSON_UNESCAPED_UNICODE);
        }
        if (!empty($record_log)) {
            $houseUserLog['record_log'] = json_encode($record_log, JSON_UNESCAPED_UNICODE);
        }
        if ($local_device_sn) {
            $houseUserLog['device_sn'] = $local_device_sn;
        } else {
            $houseUserLog['device_sn'] = $device_sn;
        }
        fdump_api(['msg' => '处理结构', 'line' => __LINE__, 'houseUserLog' => $houseUserLog], 'event/handleAccessControllerEventLog', 1);
        $houseUserLog['device_id'] = $device_id;
        $houseUserLog['log_business_id'] = $village_id;
        $name && $houseUserLog['person_name'] = $name;
        $employeeNoString && $houseUserLog['log_bind_id'] = $employeeNoString;
        $houseUserLog['person_code'] = $employeeNoString;
        $houseUserLog['eventId'] = $serialNo;
        $houseUserLog['deviceChannelId'] = $channelID;
        $houseUserLog['log_time'] = $dateTime;
        $houseUserLog['add_time'] = $nowTime;
        $houseUserLog['card_number'] = $cardNo;
        fdump_api(['msg' => '处理结构', 'line' => __LINE__, 'houseUserLog' => $houseUserLog], 'event/handleAccessControllerEventLog', 1);
        if ($local_device_name) {
            $houseUserLog['log_name'] = $local_device_name;
        } else {
            $houseUserLog['log_name'] = $deviceName;
        }
        if (isset($eventPicture['url']) && trim($eventPicture['url'])) {
            $houseUserLog['cut_image'] = trim($eventPicture['url']);
        } elseif ($pictureURL) {
            $houseUserLog['cut_image'] = $pictureURL;
        }
        $houseUserLog['third_protocol'] = $third_protocol;
        $houseUserLog['temperature'] = $currTemperature;
        $whereEvent = [];
        $whereEvent[] = ['third_protocol', '=', $third_protocol];
        $whereEvent[] = ['eventId', '=', $serialNo];
        $whereEvent[]=['log_from','=',$log_from];
        $add_time_x=$nowTime-5;
        $whereEvent[] = ['add_time', '>', $add_time_x];
        if (isset($houseUserLog['device_sn']) && $houseUserLog['device_sn']) {
            $whereEvent[] = ['device_sn', '=', $houseUserLog['device_sn']];
        }
        if (isset($userBind['type']) && intval($userBind['type']) == 6) {
            $db_house_village_visitor = new HouseVillageVisitor();
            $whereVisitor = [];
            $whereVisitor[] = ['visitor_bind_id', '=', $userBind['pigcms_id']];
            $visitorInfo = $db_house_village_visitor->get_one($whereVisitor, 'id, visitor_phone, visit_owner_switch, visit_to_devices');
            // 是否访问业主 0-默认 1-不访问业主 2-访问业主
            $visit_owner_switch = isset($visitorInfo['visit_owner_switch']) ? intval($visitorInfo['visit_owner_switch']) : 0;
            $visitorInfoId = isset($visitorInfo['id']) ? intval($visitorInfo['id']) : 0;
            if ($visit_owner_switch >= 1) {
                $eventTypeMsg['msg'] .= "[访客(车辆)]";
            } else {
                $eventTypeMsg['msg'] .= "[访客]";
            }
        } else {
            $visitorInfoId = 0;
        }
        $houseUserLog['device_event_msg'] = $eventTypeMsg['msg'];
        
        fdump_api(['msg' => '处理结构', 'line' => __LINE__, 'houseUserLog' => $houseUserLog], 'event/handleAccessControllerEventLog', 1);
        $db_house_user_log = new HouseUserLog();
        $logInfo = $db_house_user_log->getFind($whereEvent, 'log_id');
        $log_id = isset($logInfo['log_id']) && $logInfo['log_id'] ? intval($logInfo['log_id']) : 0;
        if (!isset($logInfo['log_id']) || !$logInfo['log_id']) {
            $log_id = $db_house_user_log->addData($houseUserLog);
            if ($visitorInfoId > 0) {
                $dbHouseVillageVisitorPass = new HouseVillageVisitorPass();
                $db_house_village_visitor = new HouseVillageVisitor();
                $addArr = [
                    'visitor_id' => $visitorInfoId, 'village_id' => $village_id, 'user_bind_id' => $userBind['pigcms_id'],
                    'uid' => isset($houseUserLog['uid']) && $houseUserLog['uid'] ? intval($houseUserLog['uid']) : 0,
                    'phone' => isset($visitorInfo['visitor_phone']) && trim($visitorInfo['visitor_phone']) ? trim($visitorInfo['visitor_phone']) : '',
                ];
                $device_name = isset($deviceInfo['device_name']) && $deviceInfo['device_name'] ? $deviceInfo['device_name'] : '';
                if ($log_from == DeviceConst::DEVICE_FACE_OPEN) {
                    $addArr['uname'] = $device_name . ' 人脸门禁通行';
                } elseif ($log_from == DeviceConst::DEVICE_QR_CODE_OPEN) {
                    $addArr['uname'] = $device_name . ' 二维码门禁通行';
                } elseif ($log_from == DeviceConst::DEVICE_GATE_OPEN) {
                    $addArr['uname'] = $device_name . ' 远程开门门禁通行';
                } else {
                    $addArr['uname'] = $device_name . ' 门禁通行';
                }
                $addArr['addtime'] = $nowTime;
                fdump_api(['msg' => '访客通行记录', 'line' => __LINE__, 'addArr' => $addArr], 'event/handleAccessControllerEventLog', 1);
                $dbHouseVillageVisitorPass->add($addArr);
                $dataSave = [
                    'status'    => 5, 'pass_time' => $nowTime,
                ];
                $whereVisitor = ['id' => $visitorInfoId];
                fdump_api(['msg' => '更改访客状态', 'line' => __LINE__, 'whereVisitor' => $whereVisitor, 'dataSave' => $dataSave], 'event/handleAccessControllerEventLog', 1);
                $db_house_village_visitor->saveOne($whereVisitor, $dataSave);
            }
            if (!in_array($subEventType, [6, 10, 11, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37])) {
                $updateFace = [
                    'last_open_time' => $dateTime
                ];
                $sHouseFaceDeviceService->updateHouseFaceDeviceInfo($whereFace, $updateFace);
            }
            /*
            $file_path = isset($record_log['tmp_name']) && $record_log['tmp_name'] ? $record_log['tmp_name'] : '';
            if ($log_id > 0 && !empty($record_log) && $file_path) {
                $image_type = isset($record_log['type']) && $record_log['type'] ? $record_log['type'] : '';
                $file_size = isset($record_log['size']) && $record_log['size'] ? $record_log['size'] : 0;
                $handleImgData = [
                    'handle_id' => $log_id, 'handle_type' => 'house_user_log', 'file_type' => 0, 'file_path' => $file_path,
                    'image_type' => $image_type, 'file_size' => $file_size, 'handle_from' => 'hik_gate', 'add_time' => time(),
                ];
                $dbHandleImg = new HandleImg();
                $dbHandleImg->add($handleImgData);
            }
            */
        }
        fdump_api(['msg' => '抓拍图片', 'line' => __LINE__, 'houseUserLog' => $houseUserLog, 'eventTypeMsg' => $eventTypeMsg, 'log_id' => $log_id], 'event/handleAccessControllerEventLog', 1);
        return $log_id;
    }

    /**
     * 事件详情处理.
     */
    public function handleCommonEvent($event_log)
    {
        fdump_api(['msg' => '事件详情处理', 'line' => __LINE__, 'event_log' => $event_log], 'event/handleCommonEvent');
        $nowTime = time();
        $channelID = isset($event_log['channelID']) && $event_log['channelID'] ? trim($event_log['channelID']) : '';
        $dateTime = isset($event_log['dateTime']) && $event_log['dateTime'] ? $event_log['dateTime'] : '';
        $event_time = $dateTime ? strtotime($dateTime) : $nowTime;
        $eventType = isset($event_log['eventType']) && $event_log['eventType'] ? trim($event_log['eventType']) : '';
        $eventDescription = isset($event_log['eventDescription']) && $event_log['eventDescription'] ? trim($event_log['eventDescription']) : '';
        $activePostCount = isset($event_log['activePostCount']) && $event_log['activePostCount'] ? intval($event_log['activePostCount']) : 0;
        $devIndex = isset($event_log['devIndex']) && $event_log['devIndex'] ? trim($event_log['devIndex']) : '';
        $channelName = isset($event_log['channelName']) && $event_log['channelName'] ? trim($event_log['channelName']) : '';
        $deviceID = isset($event_log['deviceID']) && $event_log['deviceID'] ? trim($event_log['deviceID']) : '';
        $deviceSerial = isset($event_log['deviceSerial']) && $event_log['deviceSerial'] ? trim($event_log['deviceSerial']) : '';

        $ipAddress = isset($event_log['ipAddress']) && $event_log['ipAddress'] ? trim($event_log['ipAddress']) : '';
        $protocolType = isset($event_log['protocolType']) && $event_log['protocolType'] ? trim($event_log['protocolType']) : '';
        $macAddress = isset($event_log['macAddress']) && $event_log['macAddress'] ? trim($event_log['macAddress']) : '';
        $event_source = isset($event_log['eventState']) && $event_log['eventState'] ? trim($event_log['eventState']) : '';

        $camera_sn = $deviceID ?? $deviceSerial;

        $db_house_camera_device = new HouseCameraDevice();
        $whereCamera = [];
        if ($devIndex) {
            $whereCamera[] = ['cloud_device_id', '=', $devIndex];
        } elseif ($camera_sn) {
            $whereCamera[] = ['camera_sn', '=', $camera_sn];
        }
        $cloud_device_id = '';
        $device_name = '';
        if (!empty($whereCamera)) {
            $whereCamera[] = ['camera_status', '<>', 4];
            $whereCamera[] = ['thirdProtocol', 'in', [DeviceConst::HIK_DEVICE_GATEWAY, DeviceConst::HIK_DEVICE_GATEWAY_CUSTOM]];
            $cameraInfo = $db_house_camera_device->getOne($whereCamera, 'camera_id, camera_name, camera_sn, village_id, device_model, cloud_device_id');
            if ($cameraInfo && !is_array($cameraInfo)) {
                $cameraInfo = $cameraInfo->toArray();
            }
            $village_id = isset($cameraInfo['village_id']) ? intval($cameraInfo['village_id']) : 0;
            $equipment_id = isset($cameraInfo['camera_id']) ? intval($cameraInfo['camera_id']) : 0;
            $device_name = isset($cameraInfo['camera_name']) ? trim($cameraInfo['camera_name']) : '';
            $device_serial = isset($cameraInfo['camera_sn']) ? trim($cameraInfo['camera_sn']) : '';
            $device_model = isset($cameraInfo['device_model']) ? trim($cameraInfo['device_model']) : '';
            $cloud_device_id = isset($cameraInfo['cloud_device_id']) ? trim($cameraInfo['cloud_device_id']) : '';
            fdump_api(['msg' => $eventDescription.'-cameraInfo', 'line' => __LINE__, 'cameraInfo' => $cameraInfo], 'event/handleCommonEvent', 1);
        } else {
            $db_house_device_network = new HouseDeviceNetwork();
            if ($macAddress) {
                $whereNetWork = [];
                $whereNetWork[] = ['device_type', '=', 'house_camera_device'];
                $whereNetWork[] = ['mac_address', '=', $macAddress];
                $whereNetWork[] = ['delete_time', '=', 0];
                $netWorkInfo = $db_house_device_network->getOne($whereNetWork, 'id, village_id, device_id, deviceSerial');
            }
            if ((!isset($netWorkInfo) || !isset($netWorkInfo['id']) || !$netWorkInfo['id']) && $ipAddress) {
                $whereNetWork = [];
                $whereNetWork[] = ['device_type', '=', 'house_camera_device'];
                $whereNetWork[] = ['ip_address', '=', $ipAddress];
                $whereNetWork[] = ['delete_time', '=', 0];
                $netWorkInfo = $db_house_device_network->getOne($whereNetWork, 'id, village_id, device_id, deviceSerial');
            }
            if ($netWorkInfo && !is_array($netWorkInfo)) {
                $netWorkInfo = $netWorkInfo->toArray();
            }
            $village_id = isset($netWorkInfo['village_id']) ? intval($netWorkInfo['village_id']) : 0;
            $equipment_id = isset($netWorkInfo['device_id']) ? intval($netWorkInfo['device_id']) : 0;
            $device_serial = isset($netWorkInfo['deviceSerial']) ? trim($netWorkInfo['deviceSerial']) : '';
            $whereCamera = [];
            if ($equipment_id) {
                $whereCamera[] = ['camera_id', '=', $equipment_id];
            } elseif ($device_serial) {
                $whereCamera[] = ['camera_sn', '=', $device_serial];
                $whereCamera[] = ['camera_status', '<>', 4];
                $whereCamera[] = ['thirdProtocol', 'in', [DeviceConst::HIK_DEVICE_GATEWAY, DeviceConst::HIK_DEVICE_GATEWAY_CUSTOM]];
            }
            if (!empty($whereCamera)) {
                $cameraInfo = $db_house_camera_device->getOne($whereCamera, 'camera_id, camera_name, camera_sn, village_id, device_model, cloud_device_id');
                if ($cameraInfo && !is_array($cameraInfo)) {
                    $cameraInfo = $cameraInfo->toArray();
                }
                !$village_id && $village_id = isset($cameraInfo['village_id']) ? intval($cameraInfo['village_id']) : 0;
                $device_name = isset($cameraInfo['camera_name']) ? trim($cameraInfo['camera_name']) : '';
                if (!$equipment_id || !$device_serial) {
                    $device_serial = isset($cameraInfo['camera_sn']) ? trim($cameraInfo['camera_sn']) : '';
                    $equipment_id = isset($cameraInfo['camera_id']) ? trim($cameraInfo['camera_id']) : '';
                }
                $device_model = isset($cameraInfo['device_model']) ? trim($cameraInfo['device_model']) : '';
                $cloud_device_id = isset($cameraInfo['cloud_device_id']) ? trim($cameraInfo['cloud_device_id']) : '';
                fdump_api(['msg' => $eventDescription.'-cameraInfo', 'line' => __LINE__, 'cameraInfo' => $cameraInfo], 'event/handleCommonEvent', 1);
            }
        }
        $eventIdArr = [
            'ipAddress' => $ipAddress, 'protocolType' => $protocolType, 'macAddress' => $macAddress,
            'channelID' => $channelID, 'dateTime' => $dateTime, 'activePostCount' => $activePostCount,
            'eventType' => $eventType, 'eventState' => $event_source, 'eventDescription' => $eventDescription,
        ];
        $event_id = md5(json_encode($eventIdArr, JSON_UNESCAPED_UNICODE));
        fdump_api(['msg' => $eventDescription.'-事件id', 'line' => __LINE__, 'eventIdArr' => $eventIdArr], 'event/handleCommonEvent', 1);

        $keys = ['channelID', 'eventType', 'eventDescription', 'devIndex', 'channelName', 'deviceID'];
        $event_txt_arr = unsetParam($event_log, $keys);

        $db_community_event_alarm = new CommunityEventAlarm();
        $event_description = $eventDescription;
        $status = isset($event_log['status']) && $event_log['status'] ? trim($event_log['status']) : '';
        switch ($eventDescription) {
            case 'Motion alarm':
                $event_description = '移动报警（监控区域有异动）';
                break;
            case 'shelteralarm alarm':
                $event_description = '遮挡报警(有异物遮镜头)';
                break;
            case 'Scene Change Detection':
                $event_description = '遮挡报警(有异物遮镜头)';
                break;
            case 'rollover alarm':
                $event_description = '侧翻报警(有物品侧翻)';
                break;
            case 'PIR alarm':
                $event_description = 'PIR报警';
                break;
            case 'PALMismatch alarm':
                $event_description = '视频制式不匹配';
                break;
            case 'overSpeed alarm':
                $event_description = '超速报警';
                break;
            case 'mobile people counting':
                $event_description = '车载客流量';
                break;
            case 'IO Alarm':
                $event_description = '信号量报警';
                break;
            case 'high temperature alarm':
                $event_description = '温度过高报警';
                break;
            case 'Heartbeat':
                $event_description = '设备上传心跳';
                break;
            case 'GPS Info':
                $event_description = 'GPS信息上传';
                break;
            case 'Face Detection':
                $event_description = '人脸侦测事件';
                break;
            case 'faceCapture':
                $event_description = '人脸抓拍报警';
                break;
            case 'diskfull alarm':
                $event_description = '磁盘满溢';
                break;
            case 'diskerror alarm':
                $event_description = '磁盘错误';
                break;
            case 'device status about online or offline changed':
                if ($status == 'offline') {
                    $event_description = '设备离线告警';
                } else {
                    $event_description = '设备上线告警';
                }
                break;
            case 'device sleep alarm':
                $event_description = '设备休眠';
                break;
            case 'collision alarm':
                $event_description = '碰撞探测事件';
                break;
            case 'Battery Status':
                $batteryPercentage = isset($event_log['batteryPercentage']) && $event_log['batteryPercentage'] ? trim($event_log['batteryPercentage']) . '%' : '';
                // 电池类型：1（蓄电池），0（干电池）
                $batteryType = isset($event_log['batteryPercentage']) ? intval($event_log['batteryPercentage']) : 0;
                // 剩余电量，单位：mA（毫安）
                $remainingBattery = isset($event_log['remainingBattery']) && $event_log['remainingBattery'] ? trim($event_log['remainingBattery']) : '';
                // 充电状态：1（正在充电），0（未充电）
                $chargingStatus = isset($event_log['chargingStatus']) ? intval($event_log['chargingStatus']) : 0;
                $event_description = '电池状态';
                $event_remark = '';
                if ($batteryType == 0) {
                    $event_remark .= "电池类型: 干电池；";
                } elseif ($batteryType == 1) {
                    $event_remark .= "电池类型: 蓄电池；";
                }
                if ($batteryPercentage) {
                    $event_remark .= "电池电量剩余：{$batteryPercentage}；";
                }
                if ($remainingBattery) {
                    $event_remark .= "剩余电量：{$remainingBattery}mA（毫安）；";
                }
                if ($chargingStatus == 0) {
                    $event_remark .= "充电状态: 未充电；";
                } elseif ($chargingStatus == 1) {
                    $event_remark .= "充电状态: 正在充电；";
                }
                break;
            case 'Address change alarm':
                $newAddress = isset($event_log['NewAddress']) && $event_log['NewAddress'] ? $event_log['NewAddress'] : '';
                $oldAddress = isset($event_log['OldAddress']) && $event_log['OldAddress'] ? $event_log['OldAddress'] : '';
                $event_description = '设备地址变化报警';
                $event_remark = '';
                if (isset($newAddress['ipAddress']) && isset($newAddress['portNo'])) {
                    $event_remark .= "设备新地址: {$newAddress['ipAddress']}:{$newAddress['portNo']}；";
                }
                if (isset($oldAddress['ipAddress']) && isset($oldAddress['portNo'])) {
                    $event_remark .= "设备旧地址: {$oldAddress['ipAddress']}:{$oldAddress['portNo']}；";
                }
                break;
            case 'videoloss alarm':
                $event_description = '视频丢失事件';
                break;
        }
        switch ($eventType) {
            case 'regionExiting':
                $event_description = '离开区域(有物体离开监控区域)';
                break;
            case 'regionEntrance':
                $event_description = '进入区域(有物体进入监控区域)';
                break;
            case 'rapidMove':
                $event_description = '快速移动(有物体快速移动)';
                break;
            case 'PIR':
                $event_description = 'PIR报警';
                break;
            case 'parking':
                $event_description = '停车侦测';
                break;
            case 'PALMismatch':
                $event_description = '视频制式不匹配';
                break;
            case 'overSpeed':
                $event_description = '超速报警';
                break;
            case 'mobilePeopleCounting':
                $event_description = '车载客流量';
                break;
            case 'loitering':
                $event_description = '徘徊侦测';
                break;
            case 'linedetection':
                $event_description = '越界侦测';
                break;
            case 'IO':
                $event_description = '信号量报警';
                break;
            case 'highTempAlarm':
                $event_description = '温度过高报警';
                break;
            case 'heartBeat':
                $event_description = '设备上传心跳';
                break;
            case 'group':
                $event_description = '人员聚集';
                break;
            case 'GPSUpload':
                $event_description = 'GPS信息上传';
                break;
            case 'flowOverrun':
                $event_description = '流量超限报警';
                break;
            case 'fielddetection':
                $event_description = '区域入侵';
                break;
            case 'facedetection':
                $event_description = '人脸侦测事件';
                break;
            case 'faceCapture':
                $event_description = '人脸抓拍报警';
                break;
            case 'diskfull':
                $event_description = '磁盘满溢';
                break;
            case 'diskerror':
                $event_description = '磁盘错误';
                break;
            case 'devStatusChanged':
                if ($status == 'offline') {
                    $event_description = '设备离线告警';
                } else {
                    $event_description = '设备上线告警';
                }
                break;
            case 'devSleep':
                $event_description = '设备休眠';
                break;
            case 'defocus':
                $event_description = '虚焦侦测报警';
                break;
            case 'collision':
                $event_description = '碰撞探测事件';
                break;
            case 'batteryStatus':
                $batteryPercentage = isset($event_log['batteryPercentage']) && $event_log['batteryPercentage'] ? trim($event_log['batteryPercentage']) . '%' : '';
                // 电池类型：1（蓄电池），0（干电池）
                $batteryType = isset($event_log['batteryPercentage']) ? intval($event_log['batteryPercentage']) : 0;
                // 剩余电量，单位：mA（毫安）
                $remainingBattery = isset($event_log['remainingBattery']) && $event_log['remainingBattery'] ? trim($event_log['remainingBattery']) : '';
                // 充电状态：1（正在充电），0（未充电）
                $chargingStatus = isset($event_log['chargingStatus']) ? intval($event_log['chargingStatus']) : 0;
                $event_description = '电池状态';
                $event_remark = '';
                if ($batteryType == 0) {
                    $event_remark .= "电池类型: 干电池；";
                } elseif ($batteryType == 1) {
                    $event_remark .= "电池类型: 蓄电池；";
                }
                if ($batteryPercentage) {
                    $event_remark .= "电池电量剩余：{$batteryPercentage}；";
                }
                if ($remainingBattery) {
                    $event_remark .= "剩余电量：{$remainingBattery}mA（毫安）；";
                }
                if ($chargingStatus == 0) {
                    $event_remark .= "充电状态: 未充电；";
                } elseif ($chargingStatus == 1) {
                    $event_remark .= "充电状态: 正在充电；";
                }
                break;
            case 'audioexception':
                $event_description = '音频异常事件';
                break;
            case 'attendedBaggage':
                $event_description = '物品拿取事件';
                break;
            case 'addressChange':
                $newAddress = isset($event_log['NewAddress']) && $event_log['NewAddress'] ? $event_log['NewAddress'] : '';
                $oldAddress = isset($event_log['OldAddress']) && $event_log['OldAddress'] ? $event_log['OldAddress'] : '';
                $event_description = '设备地址变化报警';
                $event_remark = '';
                if (isset($newAddress['ipAddress']) && isset($newAddress['portNo'])) {
                    $event_remark .= "设备新地址: {$newAddress['ipAddress']}:{$newAddress['portNo']}；";
                }
                if (isset($oldAddress['ipAddress']) && isset($oldAddress['portNo'])) {
                    $event_remark .= "设备旧地址: {$oldAddress['ipAddress']}:{$oldAddress['portNo']}；";
                }
                break;
            case 'ADAS':
                $event_description = '辅助驾驶报警';
                break;
            case 'unattendedBaggage':
                $event_description = '物品遗留事件';
                break;
            case 'videoloss':
                $event_description = '视频丢失事件';
                break;
        }
        $alarm = [];
        $village_id && $alarm['village_id'] = $village_id;
        // 业务类型 3-Hik Device Gateway 海康网关-监控
        $alarm['business_type'] = EventAlarmConst::HIK_DEVICE_GATEWAY_EVENT_ALARM;
        $alarm['event_id'] = $event_id;
        $alarm['date_time'] = $dateTime;
        $alarm['device_id'] = $cloud_device_id;
        $alarm['device_name'] = $device_name;
        $alarm['channel_id'] = $channelID;
        $channelName && $alarm['channel_name'] = $channelName;
        isset($device_model) && $device_model && $alarm['device_model'] = $device_model;
        $alarm['device_type'] = '摄像机';
        $alarm['event_type'] = $eventType;
        $alarm['event_description'] = $event_description;
        $alarm['event_source'] = $event_source;
        $alarm['event_time'] = $event_time;
        $alarm['event_txt'] = json_encode($event_txt_arr, JSON_UNESCAPED_UNICODE);
        isset($device_serial) && $device_serial && $alarm['device_serial'] = $device_serial;
        $alarm['equipment_id'] = $event_time;
        isset($equipment_id) && $equipment_id && $alarm['equipment_id'] = $equipment_id;
        isset($event_remark) && $event_remark && $alarm['event_remark'] = $event_remark;

        $whereAlarm = [];
        $whereAlarm[] = ['business_type', '=', EventAlarmConst::HIK_DEVICE_GATEWAY_EVENT_ALARM];
        $whereAlarm[] = ['event_id', '=', $event_id];
        $alarmInfo = $db_community_event_alarm->getOne($whereAlarm, 'id');
        $alarmId = isset($alarmInfo['id']) && $alarmInfo['id'] ? intval($alarmInfo['id']) : 0;
        if ($alarmId <= 0) {
            $alarm['add_time'] = $nowTime;
            $alarmId = $db_community_event_alarm->add($alarm);
        }
        if ($status && isset($equipment_id) && $equipment_id > 0) {
            $updateParam = [];
            if ($status == 'offline') {
                $updateParam['camera_status'] = 0;
            }
            if ($status == 'online') {
                $updateParam['camera_status'] = 1;
            }
            if (!empty($updateParam)) {
                $updateParam['last_time'] = $nowTime;
                $whereCameraUpdate = [];
                $whereCameraUpdate[] = ['camera_id', '=', $equipment_id];
                $db_house_camera_device->saveOne($whereCameraUpdate, $updateParam);
            }
        }
        fdump_api(['msg' => $eventDescription.'记录信息', 'line' => __LINE__, 'whereAlarm' => $whereAlarm, 'alarm' => $alarm, 'alarmId' => $alarmId], 'event/handleCommonEvent', 1);
        return $alarmId;
    }

    /**
     * 获取二维码判断条件。
     */
    public function deviceQrCodeJudge($village_id) {
        $protocol = cfg('hikGatewayProtocol');
        $host = cfg('hikGatewayHost');
        $port = cfg('hikGatewayPort');
        $username = cfg('hikGatewayUserName');
        $password = cfg('hikGatewayPassword');
        if ($protocol && $host && $port && $username && $password) {
            return true;
        }
        $villageGateway = (new HikGateWayDeviceService())->getHouseDeviceGatewayInfo(['village_id'=>$village_id,'is_open'=>1]);
        $protocol = isset($villageGateway['protocol']) && $villageGateway['protocol'] ? $villageGateway['protocol'] : '';
        $host = isset($villageGateway['host']) && $villageGateway['host'] ? $villageGateway['host'] : '';
        $port = isset($villageGateway['port']) && $villageGateway['port'] ? $villageGateway['port'] : '';
        $username = isset($villageGateway['username']) && $villageGateway['username'] ? $villageGateway['username'] : '';
        $password = isset($villageGateway['password']) && $villageGateway['password'] ? $villageGateway['password'] : '';
        if ($protocol && $host && $port && $username && $password) {
            return true;
        }
        $house_a5_base_url = cfg('house_a5_base_url');
        $house_a5_client_id = cfg('house_a5_client_id');
        $house_a5_client_secret = cfg('house_a5_client_secret');
        if ($house_a5_base_url && $house_a5_client_id && $house_a5_client_secret) {
            return true;
        }
        /*
		$hikCloudClientId = cfg('HikCloudClientId');
        $hikCloudClientSecret = cfg('HikCloudClientSecret');
        if ($hikCloudClientId && $hikCloudClientSecret) {
            return true;
        }
        */
        return false;
    }

    /**
     * 队列任务执行图片处理.
     */
    public function TaskHandleImage() {
        $dbHandleImg = new HandleImg();
        $where = [];
        $where[] = ['handle_type', '=', 'house_user_log'];
        $where[] = ['file_type', '=', 0];
        $imageList = $dbHandleImg->getList($where, true, '', 1, 100);
        if ($imageList && !is_array($imageList)) {
            $imageList = $imageList->toArray();
        }
        if (empty($imageList)) {
            return true;
        }
        $nowTime = time();
        $pathFirst = app()->getRootPath() . '../upload/face_log/';
        $db_house_user_log = new HouseUserLog();
        $handleDelete = [];
        foreach ($imageList as $value) {
            $handle_id = isset($value['handle_id']) && $value['handle_id'] ? intval($value['handle_id']) : 0;
            $file_path = isset($value['file_path']) && $value['file_path'] ? $value['file_path'] : '';
            $handle_id && $handleDelete[] = $handle_id;
            if (! $file_path || ! $handle_id) {
                continue;
            }
            if (!file_exists($file_path)) {
                continue;
            }
//            $image_type = isset($value['image_type']) && $value['image_type'] ? $value['image_type'] : '';
            $add_time = isset($value['add_time']) && $value['add_time'] ? intval($value['add_time']) : $nowTime;
            $imgExit = '.jpg';
            $imageName = $handle_id . $imgExit;
            $pathTime = date('Ymd', $add_time);
            $to = $pathFirst . $pathTime . '/';
            if (!is_dir($to)) {
                mkdir($to, 0777, true);
            }
            $to .= $imageName;
            fdump_api(['line' => __LINE__, 'file_path' => $file_path, 'to' => $to], 'event/00TaskHandleImage',1);
            if (copy($file_path, $to)) {
                $cut_image = "/upload/face_log/" . $pathTime . '/' . $imageName;
                fdump_api(['line' => __LINE__, 'cut_image' => $cut_image], 'event/00TaskHandleImage',1);
                $db_house_user_log->updateThis(['log_id' => $handle_id], ['cut_image' => $cut_image]);
            }
            if (file_exists($file_path)) {
                //unlink($file_path);
            }
            // 休眠50毫秒
            usleep(50000);
        }
        if (!empty($handleDelete)) {
            // 处理完的数据进行删除
            $deleteHandle = [];
            $deleteHandle[] = ['handle_id', 'in', $handleDelete];
            $deleteHandle[] = ['handle_type', '=', 'house_user_log'];
            $deleteHandle[] = ['file_type', '=', 0];
            //$dbHandleImg->deleteInfo($deleteHandle);
        }
        return true;
    }
}