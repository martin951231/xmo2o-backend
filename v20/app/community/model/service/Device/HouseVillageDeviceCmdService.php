<?php

declare(strict_types=1);
/**
 * This file is part of Kuaijing Bailing.
 *
 * @link     https://www.kuaijingai.com
 * @document https://help.kuaijingai.com
 * @contact  www.kuaijingai.com 7*12 9:00-21:00
 */

namespace app\community\model\service\Device;

use app\common\model\service\config\ConfigCustomizationService;
use app\community\model\db\Device\HouseFaceDeviceAbout;
use app\community\model\db\Device\HouseVillageDeviceCmd;
use app\community\model\db\FaceUserBindDevice;
use app\community\model\db\HouseCameraTip;
use app\community\model\db\HouseFaceDevice;
use app\community\model\db\HouseUserLog;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageFloor;
use app\community\model\db\HouseVillageLayer;
use app\community\model\db\HouseVillageSingle;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\HouseVillageVisitor;
use app\community\model\db\HouseVillageVisitorPass;
use app\community\model\service\VisitorInviteService;
use app\consts\Cloud260Const;
use app\consts\DeviceConst;
use app\traits\house\DeviceTraits;
use app\traits\third\Cloud260Traits;

class HouseVillageDeviceCmdService
{
    use Cloud260Traits;
    use DeviceTraits;

    /**
     * device-to-platform 设备数据推送到平台 [2.5 设备上传当前设备初始参数信息].
     */
    public function inertParameter($params) {
        $deviceId = isset($params['deviceId']) ? trim($params['deviceId']) : '';
        if (! $deviceId) {
            return false;
        }
        return $this->deviceUploadCommon('inertParameter', $params);
    }

    /**
     * device-to-platform 设备数据推送到平台 [2.7 验证设备在线状态].
     */
    public function updateStateDevice($params) {
        $deviceId = isset($params['deviceId']) ? trim($params['deviceId']) : '';
        if (! $deviceId) {
            return false;
        }
        $onlineTimeRequest = isset($params['onlineTimeRequest']) ? $params['onlineTimeRequest'] : '';
        $dbHouseFaceDevice = new HouseFaceDevice();
        $whereFaceDevice = [
            'device_sn' => $deviceId,
            'is_del' => 0,
            'device_type' => Cloud260Const::CLOUD_260_DEVICE_TYPE,
        ];
        $deviceInfo = $dbHouseFaceDevice->getOne($whereFaceDevice);
        if (isset($deviceInfo['device_id'])) {
            // 设备存在
            $this->updateDeviceStatus($deviceId);
            // 记录额外数据
            if ($onlineTimeRequest) {
                $data = [
                    'polling_space' => $onlineTimeRequest,
                ];
                $this->updateSyncTime($deviceInfo['device_id'], $data);
            }
        }
        return true;
    }

    /**
     * device-to-platform 设备数据推送到平台 [3.0 验证设备识别二维码是否可以开门].
     */
    public function qrCodePassOpen($params) {
        $deviceId = isset($params['deviceId']) ? trim($params['deviceId']) : '';
        if (! $deviceId) {
            fdump_api(['msg' => '缺少参数设备id（deviceId）', 'params' => $params], 'FaceCloud260/errQrCodePassOpen', 1);
            return [
                "success" => 1,
                "msg" => 'DEVICE_ID_NO_HAVE',
            ];
        }
        $employeeQRCode = isset($params['employeeQRCode']) && $params['employeeQRCode'] ? $params['employeeQRCode'] : '';
        if (! $employeeQRCode) {
            fdump_api(['msg' => '缺少参数二位识别的字符串（employeeQRCode）', 'params' => $params], 'FaceCloud260/errQrCodePassOpen', 1);
            return [
                "success" => 2,
                "msg" => 'EMPLOYEE_QRCODE_NO_HAVE',
            ];
        }
        $dbHouseFaceDevice = new HouseFaceDevice();
        $whereFaceDevice = [
            'device_sn' => $deviceId,
            'is_del' => 0,
            'device_type' => Cloud260Const::CLOUD_260_DEVICE_TYPE,
        ];
        $deviceInfo = $dbHouseFaceDevice->getOne($whereFaceDevice);
        if (! $deviceInfo) {
            fdump_api(['msg' => '对应设备不存在（平台未添加或者已删除）', 'params' => $params], 'FaceCloud260/errQrCodePassOpen', 1);
            return [
                "success" => 3,
                "msg" => 'DEVICE_NO_HAVE',
            ];
        }
        $dbFaceUserBindDevice = new FaceUserBindDevice();
        $whereFaceUserBindDevice = [];
        $whereFaceUserBindDevice[] = ['bind_type', '=', Cloud260Const::CLOUD_260_FACE_USER_BIND_DEVICE_BIND_TYPE];
        $whereFaceUserBindDevice[] = ['code', '=', $employeeQRCode];
        $whereFaceUserBindDevice[] = ['device_type', '=', Cloud260Const::CLOUD_260_DEVICE_TYPE];
        $fieldFaceUserBindDevice = 'id, bind_type, bind_id, device_type, open_key, open_key_time, code, person_id, person_text, add_time';
        $infoFaceUserBindDevice = $dbFaceUserBindDevice->getOne($whereFaceUserBindDevice, $fieldFaceUserBindDevice);
        $infoFaceUserBindDevice = ($infoFaceUserBindDevice && ! is_array($infoFaceUserBindDevice)) ? $infoFaceUserBindDevice->toArray() : [];
        fdump_api(['msg' => '设备获取二维码是否可以开门', 'employeeQRCode' => $employeeQRCode, 'whereFaceUserBindDevice' => $whereFaceUserBindDevice, 'infoFaceUserBindDevice' => $infoFaceUserBindDevice], 'FaceCloud260/qrCodePassOpen', 1);
        if (! isset($infoFaceUserBindDevice['id'])) {
            fdump_api(['msg' => '对应二维码不存在或者已过期', 'params' => $params], 'FaceCloud260/errQrCodePassOpen', 1);
            return [
                "success" => 4,
                "msg" => 'QR_CODE_NO_HAVE',
            ];
        }
        fdump_api(['msg' => '反馈可以开门', 'employeeQRCode' => $employeeQRCode, 'infoFaceUserBindDevice' => $infoFaceUserBindDevice], 'FaceCloud260/qrCodePassOpen', 1);
        $nowTime = time();
        $person_text = isset($infoFaceUserBindDevice['person_text']) ? json_decode($infoFaceUserBindDevice['person_text'], true) : [];
        $effectTime = isset($person_text['effectTime']) && $person_text['effectTime'] ? strtotime($person_text['effectTime']) : 0;
        $expireTime = isset($person_text['expireTime']) && $person_text['expireTime'] ? strtotime($person_text['expireTime']) : 0;
        $openTimes = isset($person_text['openTimes']) && $person_text['openTimes'] ? intval($person_text['openTimes']) : 0;
        $open_key_time = isset($infoFaceUserBindDevice['open_key_time']) && $infoFaceUserBindDevice['open_key_time'] ? intval($infoFaceUserBindDevice['open_key_time']) : 0;
        $surplusOpenTimes = isset($person_text['surplusOpenTimes']) && $person_text['surplusOpenTimes'] ? intval($person_text['surplusOpenTimes']) : 0;
        if ($effectTime > $nowTime) {
            fdump_api(['msg' => '二维码未生效，认证失败', 'params' => $params, 'person_text' => $person_text, 'effectTime' =>$effectTime], 'FaceCloud260/errQrCodePassOpen', 1);
            return [
                "success" => 5,
                "msg" => 'QR_CODE_NO_EFFECT',
            ];
        }
        fdump_api(['msg' => '反馈可以开门', 'employeeQRCode' => $employeeQRCode, 'infoFaceUserBindDevice' => $infoFaceUserBindDevice], 'FaceCloud260/qrCodePassOpen', 1);
        if ($open_key_time > 0 && $open_key_time < $nowTime) {
            fdump_api(['msg' => '二维码已失效，需要刷新，认证失败', 'params' => $params, 'person_text' => $person_text, 'open_key_time' =>$open_key_time], 'FaceCloud260/errQrCodePassOpen', 1);
            return [
                "success" => 6,
                "msg" => 'QR_CODE_NO_EXPIRE',
            ];
        }
        fdump_api(['msg' => '反馈可以开门', 'employeeQRCode' => $employeeQRCode, 'infoFaceUserBindDevice' => $infoFaceUserBindDevice], 'FaceCloud260/qrCodePassOpen', 1);
        if ($expireTime > 0 && $expireTime < $nowTime) {
            fdump_api(['msg' => '二维码已失效，认证失败', 'params' => $params, 'person_text' => $person_text, 'expireTime' =>$expireTime], 'FaceCloud260/errQrCodePassOpen', 1);
            return [
                "success" => 6,
                "msg" => 'QR_CODE_NO_EXPIRE',
            ];
        }
        fdump_api(['msg' => '反馈可以开门', 'employeeQRCode' => $employeeQRCode, 'infoFaceUserBindDevice' => $infoFaceUserBindDevice], 'FaceCloud260/qrCodePassOpen', 1);
        if ($openTimes > 0) {
            $whereUserLog = [];
            $whereUserLog[] = ['log_business_id', '=', $deviceInfo['village_id']];
            $whereUserLog[] = ['device_id', '=', $deviceInfo['device_id']];
            $whereUserLog[] = ['log_bind_id', '=', $infoFaceUserBindDevice['bind_id']];
            $whereUserLog[] = ['log_status', '=', 0];
            if (isset($infoFaceUserBindDevice['add_time']) && $infoFaceUserBindDevice['add_time']) {
                $whereUserLog[] = ['log_time', '>=', $infoFaceUserBindDevice['add_time']];
            }
            fdump_api(['msg' => '反馈可以开门', 'employeeQRCode' => $employeeQRCode, 'infoFaceUserBindDevice' => $infoFaceUserBindDevice], 'FaceCloud260/qrCodePassOpen', 1);
            // 查询开门总次数受总次数限制
            $count = (new HouseUserLog())->getCount($whereUserLog);
            if ($count > 0 && $openTimes <= $count) {
                $surplusOpenTimes = 0;
            }
            if ($count > 0 && $openTimes > $count) {
                $surplusOpenTimes = $openTimes - $count;
            }
            fdump_api(['msg' => '反馈可以开门', 'employeeQRCode' => $employeeQRCode, 'infoFaceUserBindDevice' => $infoFaceUserBindDevice], 'FaceCloud260/qrCodePassOpen', 1);
            if ($surplusOpenTimes <= 0) {
                fdump_api(['msg' => '二维码已失效，认证失败', 'params' => $params, 'person_text' => $person_text, 'openTimes' =>$openTimes, 'surplusOpenTimes' =>$surplusOpenTimes], 'FaceCloud260/errQrCodePassOpen', 1);
                return [
                    "success" => 10,
                    "msg" => 'OPEN_TIMES_NO_HAVE',
                ];
            }
        }
        fdump_api(['msg' => '反馈可以开门', 'employeeQRCode' => $employeeQRCode, 'infoFaceUserBindDevice' => $infoFaceUserBindDevice], 'FaceCloud260/qrCodePassOpen', 1);
        $user = (new HouseVillageUserBind())->getOne(['pigcms_id' => $infoFaceUserBindDevice['bind_id']]);
        if (! $user) {
            fdump_api(['msg' => '用户不存在，认证失败', 'params' => $params], 'FaceCloud260/errQrCodePassOpen', 1);
            return [
                "success" => 7,
                "msg" => 'NO_USER',
            ];
        }
        fdump_api(['msg' => '反馈可以开门', 'employeeQRCode' => $employeeQRCode, 'infoFaceUserBindDevice' => $infoFaceUserBindDevice], 'FaceCloud260/qrCodePassOpen', 1);
        // 单元
        $floor_id = isset($user['floor_id']) && $user['floor_id'] ? intval($user['floor_id']) : 0;
        $deviceFloorId = isset($deviceInfo['floor_id']) ? $deviceInfo['floor_id'] : 0;
        if ($deviceFloorId != -1 && $deviceFloorId != 0 && $deviceFloorId != '0' && $deviceFloorId) {
            $deviceFloorIds = explode(',', strval($deviceFloorId));
            if ($floor_id !== 0 && ! in_array($floor_id, $deviceFloorIds)) {
                // 设备有归属单元 且人员不属于该单元 所以不能同步到此设备上
                fdump_api(['msg' => '权限校验失败，认证失败', 'params' => $params], 'FaceCloud260/errQrCodePassOpen', 1);
                return [
                    "success" => 8,
                    "msg" => 'NO_AUTH',
                ];
            }
        } else {
            $deviceFloorIds = [];
        }
        if (isset($deviceInfo['thirdChnNum']) && $deviceInfo['thirdChnNum'] && ! empty($deviceFloorIds) && intval($user['vacancy_id']) > 0) {
            $db_room   = new HouseVillageUserVacancy();
            $whereRoom = [];
            $whereRoom[] = ['pigcms_id', '=', $user['vacancy_id']];
            $whereRoom[] = ['room', 'like', '%' . $deviceInfo['thirdChnNum']];
            $roomInfo = $db_room->getOne($whereRoom, 'pigcms_id');
            if (! isset($roomInfo['pigcms_id']) || ! $roomInfo['pigcms_id']) {
                fdump_api(['msg' => '权限校验失败，认证失败', 'params' => $params, 'deviceFloorIds' => $deviceFloorIds, 'thirdChnNum' => $deviceInfo['thirdChnNum'], 'vacancy_id' => $user['vacancy_id']], 'FaceCloud260/errQrCodePassOpen', 1);
                return [
                    "success" => 8,
                    "msg" => 'NO_AUTH',
                ];
            }
        }
        
        fdump_api(['msg' => '反馈可以开门', 'employeeQRCode' => $employeeQRCode, 'infoFaceUserBindDevice' => $infoFaceUserBindDevice], 'FaceCloud260/qrCodePassOpen', 1);
        // 更新下剩余次数
        if ($openTimes > 0) {
            $person_text['surplusOpenTimes'] = $surplusOpenTimes - 1;
            $dataSave = [
                'person_text' => json_encode($person_text,JSON_UNESCAPED_UNICODE),
                'last_time' => time()
            ];
            $dbFaceUserBindDevice->saveData([['id', '=', $infoFaceUserBindDevice['id']]], $dataSave);
        }
        fdump_api(['msg' => '反馈可以开门', 'employeeQRCode' => $employeeQRCode, 'infoFaceUserBindDevice' => $infoFaceUserBindDevice], 'FaceCloud260/qrCodePassOpen', 1);
        $data = [
            "success" => 0,
            "employeeQRCode" => strval($employeeQRCode),
            "employeeId" => strval($infoFaceUserBindDevice['bind_id']),
            "msg" => 'OK',
        ];
        // 楼栋
        $single_id = isset($user['single_id']) && $user['single_id'] ? intval($user['single_id']) : 0;
        if ($single_id) {
            $dbHouseVillageSingle = new HouseVillageSingle();
            $singleInfo = $dbHouseVillageSingle->getOne(['id' => $single_id], 'id, single_name, single_number');
        }
        // 单元
        if ($floor_id) {
            $dbHouseVillageFloor = new HouseVillageFloor();
            $floorInfo = $dbHouseVillageFloor->getOne(['floor_id' => $floor_id], 'floor_id, floor_name, floor_number');
        }
        if (isset($singleInfo['id']) && $singleInfo['id']) {
            $single_number = isset($singleInfo['single_number']) ? trim($singleInfo['single_number']) : '';
        } else {
            $single_number = '';
        }
        if (isset($floorInfo['floor_id']) && $floorInfo['floor_id']) {
            $floor_number = isset($floorInfo['floor_number']) ? trim($floorInfo['floor_number']) : '';
        } else {
            $floor_number = '';
        }
        ($single_number || $floor_number) && $data['build_number'] = strval($single_number . $floor_number);

        if ($floor_id) {
            $uid = isset($user['uid']) ? $user['uid'] : 0;
            $phone = isset($user['phone']) ? $user['phone'] : '';
            if ($uid > 0) {
                $phone = '';
            }
            // 楼层
            $layer_id = isset($user['layer_id']) && $user['layer_id'] ? intval($user['layer_id']) : 0;
            $floor_number_arr = $this->getFloorUserAllLayer($deviceInfo['village_id'], $floor_id, $uid, $phone, $layer_id, $deviceInfo['sdk_version']);
            if (!empty($floor_number_arr)) {
                $data['floor_number'] = $floor_number_arr;
            }
        }
        return $data;
    }
    
    /**
     * device-to-platform 设备数据推送到平台 [2.1 设备主动往云端实时上传打卡记录].
     */
    public function insertNoteFace($params) {
        $deviceId = isset($params['deviceId']) ? trim($params['deviceId']) : '';
        if (! $deviceId) {
            return false;
        }
        return $this->deviceUploadCommon('insertNoteFace', $params);
    }
    
    /**
     * device-to-platform 设备数据推送到平台 [2.3 导入雇员授权信息结果反馈].
     */
    public function setPassResult($params) {
        $deviceId = isset($params['deviceId']) ? trim($params['deviceId']) : '';
        if (! $deviceId) {
            return false;
        }
        return $this->deviceUploadCommon('setPassResult', $params);
    }

    /**
     * device-to-platform 设备数据推送到平台 [2.9 设备更新服务器路径结果反馈].
     */
    public function setPathNews($params) {
        $deviceId = isset($params['deviceId']) ? trim($params['deviceId']) : '';
        if (! $deviceId) {
            return false;
        }
        $dbHouseFaceDevice = new HouseFaceDevice();
        $whereFaceDevice = ['is_del' => 0, 'device_sn' => $deviceId, 'device_type' => Cloud260Const::CLOUD_260_DEVICE_TYPE];
        $deviceInfo = $dbHouseFaceDevice->getOne($whereFaceDevice);
        if (isset($deviceInfo['device_id'])) {
            // 设备存在
            $this->updateDeviceStatus($deviceId);
            // 记录额外数据
            $data = [];
            if (isset($params['severPath'])) {
                $data['severPath'] = trim($params['severPath']);
            }
            if (isset($params['newServerPath'])) {
                $data['newServerPath'] = trim($params['newServerPath']);
            }
            if (! empty($data)) {
                $this->updateSyncTime($deviceInfo['device_id'], $data);
            }
        }
        return true;
    }
    
    /**
     * device-to-platform 设备数据推送到平台 [公用设备反馈直接记录].
     */
    protected function deviceUploadCommon($cmd, $params) {
        $deviceId = isset($params['deviceId']) ? trim($params['deviceId']) : '';
        if (! $deviceId) {
            return false;
        }
        $dbHouseFaceDevice = new HouseFaceDevice();
        $whereFaceDevice = ['is_del' => 0, 'device_sn' => $deviceId, 'device_type' => Cloud260Const::CLOUD_260_DEVICE_TYPE];
        $deviceInfo = $dbHouseFaceDevice->getOne($whereFaceDevice);
        $cmd_id = isset($deviceInfo['device_id']) && intval($deviceInfo['device_id']) ? intval($deviceInfo['device_id']) : 0;
        $cmd_data = [
            'type' => 'face',
            'device_type' => Cloud260Const::CLOUD_260_DEVICE_TYPE,
            'cmd' => $cmd,
            'cmd_direction' => Cloud260Const::CMD_DIRECTION1,
            'device_sn' => $deviceId,
        ];
        $unique_cmd = md5(json_encode($cmd_data,JSON_UNESCAPED_UNICODE));
        $cmd_data['cmd_order'] = json_encode($params,JSON_UNESCAPED_UNICODE);
        $cmd_data['unique_cmd'] = $unique_cmd;
        $cmd_data['cmd_id'] = $cmd_id;
        $cmd_data['device_id'] = $cmd_id;
        $cmd_data['status'] = 0;
        $cmd_data['order_time'] = time();
        $nowTime = time();
        if (isset($params['msgId']) && $params['msgId']) {
            $msg_id = $params['msgId'];
            $cmd_data['msg_id'] = $msg_id;
        } elseif ('setPassResult' === $cmd || 'insertNoteFace' === $cmd) {
            $msg_id = md5(json_encode(['cmd_id' => $cmd_id, 'time' => $nowTime, 'deviceId' => $deviceId, 'uniqueId' => uniqid()],JSON_UNESCAPED_UNICODE));
            $cmd_data['msg_id'] = $msg_id;
        }
        $whereUnique = [
            'unique_cmd' => $unique_cmd,
            'status' => 0,
        ];
        if (isset($msg_id) && $msg_id) {
            $whereUnique['msg_id'] = $msg_id;
        }
        $dbHouseVillageDeviceCmd = new HouseVillageDeviceCmd();
        $uniqueInfo = $dbHouseVillageDeviceCmd->getOne($whereUnique, 'id');
        if ($uniqueInfo && isset($uniqueInfo['id'])) {
            $cmd_data['last_time'] = time();
            $dbHouseVillageDeviceCmd->updateThis($whereUnique, $cmd_data);
            $cmd_info_id = $uniqueInfo['id'];
        } else {
            $cmd_data['add_time'] = time();
            $cmd_info_id = $dbHouseVillageDeviceCmd->add($cmd_data);
        }
        // 有心跳推送 更改设备状态
        $this->updateDeviceStatus($deviceId);
        // 走队列进行处理
        $param = [];
        $param['cmd_info_id'] = $cmd_info_id;
        $param['job_type'] = $cmd . 'Upload';
        $job_id = $this->traitCommonCloud260($param);
        if ($job_id == -1) {
            return [
                'code'    => 1001,
                'message' => '任务正在等候执行',
            ];
        }
        return true;
    }

    /**
     * platform-to-device 平台数据返回给设备 [2.2 设备主动触发云端导入雇员授权信息].
     */
    public function selectPassInfo($params) {
        fdump_api(['msg' => '平台数据返回给设备', 'params' => $params], 'FaceCloud260/selectPassInfoLog', 1);
        $deviceId = isset($params['deviceId']) ? trim($params['deviceId']) : '';
        if (! $deviceId) {
            return false;
        }
        $where = [
            'type' => 'face',
            'device_type' => Cloud260Const::CLOUD_260_DEVICE_TYPE,
            'cmd' => 'selectPassInfo',
            'cmd_direction' => Cloud260Const::CMD_DIRECTION0,
            'status' => 0,
            'device_sn' => $deviceId,
        ];
        $dbHouseVillageDeviceCmd = new HouseVillageDeviceCmd();
        $field = 'id, device_id, device_sn, cmd_order';
        $order = 'last_time ASC, add_time ASC';
        $selectPassList = $dbHouseVillageDeviceCmd->getSome($where, $field, $order, 0, Cloud260Const::CLOUD_260_SELECT_PASS_INFO_SIZE);
        if ($selectPassList && ! is_array($selectPassList)) {
            $selectPassList = $selectPassList->toArray();
        }
        fdump_api(['msg' => '平台数据返回给设备', 'selectPassList' => $selectPassList, 'where' => $where, 'sql' => $dbHouseVillageDeviceCmd->getLastSql()], 'FaceCloud260/selectPassInfoLog', 1);
        $passInfo = [];
        $cmdIds = [];
        foreach ($selectPassList as $item) {
            $device_id = $item['device_id'];
            $cmd_order = json_decode($item['cmd_order'],true);
            $cmdIds[] = $item['id'];
            $passInfo[] = $cmd_order;
        }
        if (empty($passInfo)) {
            fdump_api(['msg' => '空数据直接返回'], 'FaceCloud260/selectPassInfoLog', 1);
            return [];
        }
        fdump_api(['msg' => '平台数据返回给设备', 'passInfo' => $passInfo], 'FaceCloud260/selectPassInfoLog', 1);
        $nowTime = time();
        $msg_id = md5(json_encode(['cmdIds' => $cmdIds, 'time' => $nowTime, 'deviceId' => $deviceId, 'uniqueId' => uniqid()],JSON_UNESCAPED_UNICODE));
        if (! empty($cmdIds)) {
            $whereCmd = [
                ['id', 'in', $cmdIds]
            ];
            $dbHouseVillageDeviceCmd->updateThis($whereCmd, ['order_time' => $nowTime, 'status' => 1, 'msg_id' => $msg_id]);
        }
        fdump_api(['msg' => '平台数据返回给设备', 'msg_id' => $msg_id], 'FaceCloud260/selectPassInfoLog', 1);
        if (isset($device_id) && $device_id) {
            $data = [
                'last_per_sync_time' => $nowTime,
            ];
            $this->updateSyncTime($device_id, $data);
        }
        fdump_api(['msg' => '1平台数据返回给设备', 'msg_id' => $msg_id], 'FaceCloud260/selectPassInfoLog', 1);
        // 是用队列移除重复的数据指令 注意保留最新的3个相同对应相同指令
        $num = 1;
        foreach ($cmdIds as $cmdId) {
            fdump_api(['msg' => '2平台数据返回给设备', 'cmdId' => $cmdId], 'FaceCloud260/selectPassInfoLog', 1);
            $clearRepeatCmdParam = [
                'job_type' => 'clearRepeatCmd',
                'reserveLastNum' => 3, // 保留最新相同指令数
                'cmd_info_id' => $cmdId,
            ];
            // 延迟120秒 避免短时间执行多次
            $this->traitCommonCloud260($clearRepeatCmdParam, Cloud260Const::CLOUD_260_CMD_CLEAR_SPACE_TIMES * $num);
            $num++;
        }
        fdump_api(['msg' => '平台数据返回给设备', 'num' => $num], 'FaceCloud260/selectPassInfoLog', 1);
        return [
            "success" => 0,
            "msg" => 'OK',
            "msgId" => $msg_id,
            "passInfo" => $passInfo,
        ];
    }

    /**
     * platform-to-device 平台数据返回给设备 [2.3 设备主动触发云端获取删除雇员信息].
     */
    public function selectDeleteInfo($params) {
        $deviceId = isset($params['deviceId']) ? trim($params['deviceId']) : '';
        if (! $deviceId) {
            return false;
        }
        $where = [
            'type' => 'face',
            'device_type' => Cloud260Const::CLOUD_260_DEVICE_TYPE,
            'cmd' => 'selectDeleteInfo',
            'cmd_direction' => Cloud260Const::CMD_DIRECTION0,
            'status' => 0,
            'device_sn' => $deviceId,
        ];
        $dbHouseVillageDeviceCmd = new HouseVillageDeviceCmd();
        $field = 'id, cmd_id, device_id, device_sn, cmd_order';
        $order = 'last_time ASC, add_time ASC';
        $selectDeleteList = $dbHouseVillageDeviceCmd->getSome($where, $field, $order, 0, Cloud260Const::CLOUD_260_SELECT_DELETE_INFO_SIZE);
        if ($selectDeleteList && ! is_array($selectDeleteList)) {
            $selectDeleteList = $selectDeleteList->toArray();
        }
        fdump_api(['msg' => '设备主动触发云端获取删除雇员信息', 'params' => $params, 'where' => $where, 'selectDeleteList' => $selectDeleteList], 'FaceCloud260/selectDeleteInfoLog', 1);
        $deleteInfo = [];
        $cmdIds = [];
        $pigcmsIds = [];
        foreach ($selectDeleteList as $item) {
            fdump_api(['msg' => '设备主动触发云端获取删除雇员信息', 'item' => $item], 'FaceCloud260/selectDeleteInfoLog', 1);
            $device_id = $item['device_id'];
            $cmd_order = json_decode($item['cmd_order'],true);
            fdump_api(['msg' => '设备主动触发云端获取删除雇员信息', 'cmd_order' => $cmd_order], 'FaceCloud260/selectDeleteInfoLog', 1);
            $cmdIds[] = $item['id'];
            $pigcmsIds[] = $item['cmd_id'];
            $deleteInfo[] = $cmd_order;
        }
        if (empty($deleteInfo)) {
            fdump_api(['msg' => '没有数据直接返回'], 'FaceCloud260/selectDeleteInfoLog', 1);
            return [];
        }
        fdump_api(['msg' => '设备主动触发云端获取删除雇员信息', 'deleteInfo' => $deleteInfo], 'FaceCloud260/selectDeleteInfoLog', 1);

        $nowTime = time();
        if (! empty($cmdIds)) {
            $whereCmd = [
                ['id', 'in', $cmdIds]
            ];
            // 删除指令直接完成
            $dbHouseVillageDeviceCmd->updateThis($whereCmd, ['order_time' =>$nowTime, 'status' => 2, 'finish_time' => $nowTime]);
        }
        if (! empty($pigcmsIds)) {
            $whereUser = [
                ['pigcms_id', 'in', $pigcmsIds]
            ];
            (new HouseVillageUserBind())->saveOne($whereUser, ['face_img_status' => 1, 'face_img_reason' => '']);
        }
        if (isset($device_id) && $device_id) {
            $data = [
                'last_per_sync_time' => $nowTime,
            ];
            $this->updateSyncTime($device_id, $data);
        }
        // 是用队列移除重复的数据指令 注意保留最新的3个相同对应相同指令
        $num = 1;
        foreach ($cmdIds as $cmdId) {
            $clearRepeatCmdParam = [
                'job_type' => 'clearRepeatCmd',
                'reserveLastNum' => 3, // 保留最新相同指令数
                'cmd_info_id' => $cmdId,
            ];
            // 延迟120秒 避免短时间执行多次
            $this->traitCommonCloud260($clearRepeatCmdParam, Cloud260Const::CLOUD_260_CMD_CLEAR_SPACE_TIMES * $num);
            $num++;
        }
        return [
            "success" => 0,
            "msg" => 'OK',
            "empty" => 0,// 1：清空所有雇员信息 0：按指定 id 删除
            "deleteInfo" => $deleteInfo,
        ];
    }

    /**
     * platform-to-device 平台数据返回给设备 [2.6 设备主动触发云端获取设备需要设置的参数信息].
     */
    public function selectParameterInfo($params) {
        fdump_api(['msg' => '设备主动触发云端获取设备需要设置的参数信息', 'params' => $params], 'FaceCloud260/selectParameterInfoLog', 1);
        $deviceId = isset($params['deviceId']) ? trim($params['deviceId']) : '';
        if (! $deviceId) {
            return false;
        }
        $where = [
            'type' => 'face',
            'device_type' => Cloud260Const::CLOUD_260_DEVICE_TYPE,
            'cmd' => 'selectParameterInfo',
            'cmd_direction' => Cloud260Const::CMD_DIRECTION0,
            'status' => 0,
            'device_sn' => $deviceId,
        ];
        $dbHouseVillageDeviceCmd = new HouseVillageDeviceCmd();
        $field = 'id, device_id, device_sn, cmd_order';
        $info = $dbHouseVillageDeviceCmd->getOne($where, $field);
        $parameterInfo = [];
        if ($info && isset($info['cmd_order'])) {
            $parameterInfo = json_decode($info['cmd_order'],true);
        }
        $nowTime = time();
        fdump_api(['msg' => '设备主动触发云端获取设备需要设置的参数信息', 'info' => $info && !is_array($info) ? $info->toArray() : $info], 'FaceCloud260/selectParameterInfoLog', 1);
        if (isset($info['id'])) {
            $whereCmd = [
                ['id', '=', $info['id']]
            ];
            // 反馈设备设置直接完成
            fdump_api(['msg' => '设备主动触发云端获取设备需要设置的参数信息', 'whereCmd' => $whereCmd], 'FaceCloud260/selectParameterInfoLog', 1);
            $dbHouseVillageDeviceCmd->updateThis($whereCmd, ['order_time' =>$nowTime, 'status' => 2, 'finish_time' => $nowTime]);
        }
        $device_id = isset($info['device_id']) && $info['device_id'] ? $info['device_id'] : 0;
        if (! empty($passInfo) && $device_id) {
            $data = [
                'last_dev_sync_time' => $nowTime,
            ];
            $this->updateSyncTime($device_id, $data);
        }
        // 是用队列移除重复的数据指令 注意保留最新的3个相同对应相同指令
        $clearRepeatCmdParam = [
            'job_type' => 'clearRepeatCmd',
            'reserveLastNum' => 3, // 保留最新相同指令数
            'cmd_info_id' => $info['id'],
        ];
        // 延迟120秒 避免短时间执行多次
        $this->traitCommonCloud260($clearRepeatCmdParam, Cloud260Const::CLOUD_260_CMD_CLEAR_SPACE_TIMES);
        if (!empty($parameterInfo)) {
            return [
                "success" => 0,
                "msg" => 'OK',
                "parameterInfo" => $parameterInfo,
            ];
        }
        return [];
    }

    /**
     * platform-to-device 平台数据返回给设备 [2.8 设备请求服务器特殊管理(重启、恢复出厂设置)].
     */
    public function selectRestart($params) {
        $deviceId = isset($params['deviceId']) ? trim($params['deviceId']) : '';
        if (! $deviceId) {
            return false;
        }
        $where = [
            'type' => 'face',
            'device_type' => Cloud260Const::CLOUD_260_DEVICE_TYPE,
            'cmd' => 'selectRestart',
            'cmd_direction' => Cloud260Const::CMD_DIRECTION0,
            'status' => 0,
            'device_sn' => $deviceId,
        ];
        $dbHouseVillageDeviceCmd = new HouseVillageDeviceCmd();
        $field = 'id, device_id, device_sn, cmd_order';
        $info = $dbHouseVillageDeviceCmd->getOne($where, $field);
        $parameterInfo = [];
        if ($info && isset($info['cmd_order'])) {
            $parameterInfo = json_decode($info['cmd_order'],true);
        }
        $result = [];
        if (isset($parameterInfo['employeeId'])) {
            $result['employeeId'] = strval($parameterInfo['employeeId']);
        }
        if (isset($parameterInfo['build_number'])) {
            $result['build_number'] = strval($parameterInfo['build_number']);
        }
        if (isset($parameterInfo['floor_number'])) {
            $result['floor_number'] = $parameterInfo['floor_number'];
        }
        // restart int 否 0:不重启，1:重启
        if (isset($parameterInfo['restart'])) {
            $result['restart'] = intval($parameterInfo['restart']) === 1 ? 1 : 0;
        }
        // severUpdate int 否 0:不变更服务器路径，1:更新服务器路径信息
        // severPath String 否 服务器路径
        if (isset($parameterInfo['severUpdate']) && isset($parameterInfo['severPath'])) {
            $result['severUpdate'] = intval($parameterInfo['severUpdate']) === 1 ? 1 : 0;
            $result['severPath'] = trim($parameterInfo['severPath']);
        }
        // recover int 否 0:正常，1：恢复出厂设置
        if (isset($parameterInfo['recover'])) {
            $result['recover'] = intval($parameterInfo['recover']) === 1 ? 1 : 0;
        }
        // opendoor Int 否 0：不处理，1：打开继电器
        if (isset($parameterInfo['opendoor'])) {
            $result['opendoor'] = intval($parameterInfo['opendoor']);
        }
        if (isset($result['restart']) && $result['restart'] === 1 && isset($info['device_id'])) {
            $data = [
                'restart_record_time' => time(),
            ];
            $this->updateSyncTime($info['device_id'], $data);
        }
        $nowTime = time();
        if (isset($info['id'])) {
            $whereCmd = [
                ['id', '=', $info['id']]
            ];
            // 反馈设备操作直接完成
            $dbHouseVillageDeviceCmd->updateThis($whereCmd, ['order_time' =>$nowTime, 'status' => 2, 'finish_time' => $nowTime]);
        }
        if (isset($result['opendoor']) && $result['opendoor'] === 1 && isset($info['device_id'])) {
            $data = [
                'open_door_time' => $nowTime,
            ];
            $this->updateSyncTime($info['device_id'], $data);
        }
        // 是用队列移除重复的数据指令 注意保留最新的3个相同对应相同指令
        $clearRepeatCmdParam = [
            'job_type' => 'clearRepeatCmd',
            'reserveLastNum' => 3, // 保留最新相同指令数
            'cmd_info_id' => $info['id'],
        ];
        // 延迟120秒 避免短时间执行多次
        $this->traitCommonCloud260($clearRepeatCmdParam, Cloud260Const::CLOUD_260_CMD_CLEAR_SPACE_TIMES);
        if (! empty($result)) {
            $result['success'] = 0;
            $result['msg'] = 'OK';
        }
        if (isset($result['opendoor']) && $result['opendoor'] === 1 && isset($parameterInfo['employeeId']) && $parameterInfo['employeeId']) {
            $dbHouseFaceDevice = new HouseFaceDevice();
            $whereFaceDevice = ['is_del' => 0, 'device_type' => Cloud260Const::CLOUD_260_DEVICE_TYPE];
            if (isset($info['device_id']) && $info['device_id']) {
                $whereFaceDevice['device_id'] = $info['device_id'];
            }
            if (isset($info['device_sn']) && $info['device_sn']) {
                $whereFaceDevice['device_sn'] = $info['device_sn'];
            }
            $deviceInfo = $dbHouseFaceDevice->getOne($whereFaceDevice);
            // 发起了远程开门记录下开门记录
            if ($deviceInfo && ! is_array($deviceInfo)) {
                $deviceInfo = $deviceInfo->toArray();
            }
            $village_id = isset($deviceInfo['village_id']) ? intval($deviceInfo['village_id']) : 0;
            $device_id = isset($deviceInfo['device_id']) ? intval($deviceInfo['device_id']) : 0;
            $device_sn = isset($deviceInfo['device_sn']) ? strval($deviceInfo['device_sn']) : '';
            $device_name = isset($deviceInfo['device_name']) ? strval($deviceInfo['device_name']) : '';
            $employeeId = intval($parameterInfo['employeeId']);
            $houseUserLog = [
                'log_business_id' => $village_id, 'device_id' => $device_id, 'device_sn' => $device_sn,
                'log_name' => $device_name,
            ];
            $houseUserLog['log_from'] = DeviceConst::DEVICE_GATE_OPEN;
            $houseUserLog['log_bind_id'] = $employeeId;
            $user = (new HouseVillageUserBind())->getOne(['pigcms_id' => $employeeId], 'pigcms_id, uid, name, type');
            if (isset($user['uid']) && $user['uid']) {
                $houseUserLog['uid'] = $user['uid'];
            }
            if (isset($user['name']) && trim($user['name'])) {
                $houseUserLog['person_name'] = trim($user['name']);
            }
            $houseUserLog['log_time'] = $nowTime;
            $houseUserLog['log_status'] = 0;
            $houseUserLog['log_detail'] = json_encode($result,JSON_UNESCAPED_UNICODE);
            (new HouseUserLog())->addData($houseUserLog);
        }
        return $result;
    }
    
    /**
     * 一键同步所有.
     */
    public function deviceAllSyncPer($params) {
        fdump_api(['msg' => '发起一键同步', 'params' => $params], '260Cloud/serviceDeviceAllSyncPer');
        $device_id = isset($params['device_id']) && $params['device_id'] ? intval($params['device_id']) : 0;
        $device_sn = isset($params['device_sn']) && $params['device_sn'] ? trim($params['device_sn']) : '';
        if (! $device_id && ! $device_sn) {
            fdump_api(['msg' => '发起一键同步-少参', 'device_sn' => $device_sn, 'device_id' => $device_id], '260Cloud/serviceDeviceAllSyncPer',1);
            return [
                'code'    => 1001,
                'message' => '缺少必要参数',
            ];
        }
        $params['job_type'] = 'handleDeviceAllSyncPer';
        $job_id = $this->traitCommonCloud260($params);
        fdump_api(['msg' => '发起一键同步-队列', 'job_id' => $job_id, 'params' => $params], '260Cloud/serviceDeviceAllSyncPer',1);
        if ($job_id == -1) {
            return [
                'code'    => 1001,
                'message' => '任务正在等候执行',
            ];
        }
        if ($job_id) {
            $data = [
                'one_click_sync_time' => time(),
            ];
            $this->updateSyncTime($device_id, $data);
        }
        return $job_id;
    }

    /**
     * 单人同步.
     */
    public function userSynDevice($params) {
        $uid = isset($params['uid']) && $params['uid'] ? intval($params['uid']) : 0;
        $pigcms_id = isset($params['pigcms_id']) && $params['pigcms_id'] ? intval($params['pigcms_id']) : 0;
        fdump_api(['msg' => '单人同步', 'pigcms_id' => $pigcms_id, 'params' => $params], '260Cloud/userSynDevice',1);
        if (! $uid && ! $pigcms_id) {
            return [
                'code'    => 1001,
                'message' => '缺少必要参数',
            ];
        }
        $params['job_type'] = 'handleUserSynDevice';
        $job_id = $this->traitCommonCloud260($params);
        fdump_api(['msg' => '单人同步-队列', 'job_id' => $job_id, 'params' => $params], '260Cloud/userSynDevice',1);
        // todo 临时调试
//        $this->handleUserSynDevice($params);
        if ($job_id == -1) {
            return [
                'code'    => 1001,
                'message' => '任务正在等候执行',
            ];
        }
        return $job_id;
    }
    
    /**
     * 处理队列.
     */
    public function handleCloud260Job($params)
    {
        $job_type = isset($params['job_type']) && $params['job_type'] ? $params['job_type'] : '';
        if (! $job_type) {
            return true;
        }
        try {
            switch ($job_type) {
                case 'handleDeviceAllSyncPer':
                    // 一键同步所有
                    $this->handleDeviceAllSyncPer($params);
                    break;
                case 'handleUser':
                    // 同步单个人员
                    $this->handleUser($params);
                    break;
                case 'handleUserSynDevice':
                    // 人员同步
                    $this->handleUserSynDevice($params);
                    break;
                case 'inertParameterUpload':
                    // 设备上传当前设备初始参数信息
                    $this->inertParameterUpload($params);
                    break;
                case 'insertNoteFaceUpload':
                    // 设备主动往云端实时上传打卡记录
                    $this->insertNoteFaceUpload($params);
                    break;
                case 'setPassResultUpload':
                    // 导入雇员授权信息结果反馈
                    $this->setPassResultUpload($params);
                    break;
                case 'clearRepeatCmd':
                    // 清除重复队列
                    $this->clearRepeatCmd($params);
                    break;
            }
        } catch (\Exception $e) {
            fdump_api(['msg' => '处理队列', 'job_type' => $job_type, 'params' => $params, 'err' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' =>$e->getCode()], '260Cloud/handleCloud260Job',1);
        }
        return true;
    }

    /**
     * 设备触发一键同步所有.
     */
    public function handleDeviceAllSyncPer($params) {
        $device_id = isset($params['device_id']) && $params['device_id'] ? intval($params['device_id']) : 0;
        $device_sn = isset($params['device_sn']) && $params['device_sn'] ? trim($params['device_sn']) : '';
        $village_id = isset($params['village_id']) && $params['village_id'] ? intval($params['village_id']) : 0;
        $device_type = isset($params['device_type']) && $params['device_type'] ? intval($params['device_type']) : Cloud260Const::CLOUD_260_DEVICE_TYPE;
        fdump_api(['msg' => '一键同步', 'params' => $params, 'device_type' => $device_type], '260Cloud/handleDeviceAllSyncPer', 1);
        if (! $device_id || ! $device_sn) {
            return [
                'code'    => 1001,
                'message' => '缺少必要参数',
            ];
        }
        $whereDevice = [
            'is_del' => 0,
        ];
        $device_id && $whereDevice['device_id'] = $device_id;
        $device_sn && $whereDevice['device_sn'] = $device_sn;
        $village_id && $whereDevice['village_id'] = $village_id;
        $device_type && $whereDevice['device_type'] = $device_type;
        $field = 'device_id, device_sn, village_id, floor_id, public_area_id, thirdChnNum';
        $deviceInfo = (new HouseFaceDevice())->getOne($whereDevice, $field);
        if ($deviceInfo && ! is_array($deviceInfo)) {
            $deviceInfo = $deviceInfo->toArray();
        }
        fdump_api(['msg' => '一键同步-设备信息', 'whereDevice' => $whereDevice, 'deviceInfo' => $deviceInfo], '260Cloud/handleDeviceAllSyncPer', 1);
        if (empty($deviceInfo)) {
            return [
                'code'    => 1001,
                'message' => '对应设备不存在',
            ];
        }
        ! $device_id && $device_id = $deviceInfo['device_id'];
        ! $device_sn && $device_sn = $deviceInfo['device_sn'];
        ! $village_id && $village_id = $deviceInfo['village_id'];
        $whereUserBind = [];
        $village_id && $whereUserBind[] = ['village_id', '=', $village_id];
        $whereUserBind[] = ['status', '=', 1];
        $floor_id = isset($deviceInfo['floor_id']) ? $deviceInfo['floor_id'] : 0;
        if ($floor_id !== -1 && $floor_id !== 0 && $floor_id !== '0' && $floor_id) {
            $floorIds = explode(',', strval($floor_id));
            $whereUserBind[] = ['floor_id', 'in', $floorIds];
        }
        if (isset($floorIds) && isset($deviceInfo['thirdChnNum']) && $deviceInfo['thirdChnNum']) {
            $db_room   = new HouseVillageUserVacancy();
            $whereRoom = [];
            $whereRoom[] = ['village_id', '=', $village_id];
            $whereRoom[] = ['floor_id', 'in', $floorIds];
            $thirdChnNum = strval($deviceInfo['thirdChnNum']);
            $whereRoom[] = ['room', 'like', '%' . $thirdChnNum];
            $roomIds = $db_room->getColumn($whereRoom, 'pigcms_id');
            if (is_array($roomIds)) {
                $roomIds[] = 0;
            } else {
                $roomIds = [];
                $roomIds[] = 0;
            }
            $whereUserBind[] = ['vacancy_id', 'in', $roomIds];
        }
        
        $field = 'pigcms_id, single_id, floor_id, layer_id,vacancy_id, type, village_id, uid, phone, property_endtime, property_starttime, name, parent_id,ic_card, id_card, user_number,bind_number';
        $userList = (new HouseVillageUserBind())->getList($whereUserBind, $field);
        if ($userList && ! is_array($userList)) {
            $userList = $userList->toArray();
        }
        if (! $userList) {
            return [
                'code'    => 1001,
                'message' => '缺少同步对象',
            ];
        }
        if (count($userList) < 5) {
            // 小于5条数据直接执行 超出走队列
            foreach ($userList as $user) {
                $params = [];
                $params['device_type'] = $device_type;
                $params['device_sn'] = $device_sn;
                $params['device_id'] = $device_id;
                $params['cmd_id'] = $user['pigcms_id'];
                $params['user'] = $user;
                $this->handleUser($params);
            }
        } else {
            foreach ($userList as $user) {
                $params = [];
                $params['device_type'] = $device_type;
                $params['device_sn'] = $device_sn;
                $params['device_id'] = $device_id;
                $params['cmd_id'] = $user['pigcms_id'];
//                $params['user'] = $user;
                $params['job_type'] = 'handleUser';
                $this->traitCommonCloud260($params);
            }
        }
        return false;
    }

    /**
     * 单人同步.
     */
    public function handleUserSynDevice($params) {
        $uid = isset($params['uid']) && $params['uid'] ? intval($params['uid']) : 0;
        $pigcms_id = isset($params['pigcms_id']) && $params['pigcms_id'] ? intval($params['pigcms_id']) : 0;
        $device_type = isset($params['device_type']) && $params['device_type'] ? intval($params['device_type']) : Cloud260Const::CLOUD_260_DEVICE_TYPE;
        fdump_api(['msg' => '单人同步', 'pigcms_id' => $pigcms_id, 'params' => $params, 'device_type' => $device_type], '260Cloud/handleUserSynDevice', 1);
        $dbHouseVillageUserBind = (new HouseVillageUserBind());
        if ($uid) {
            $whereUserBind = [];
            $whereUserBind[] = ['uid', '=', $uid];
            $whereUserBind[] = ['status', '=', 1];
            $field = 'pigcms_id, single_id, floor_id, layer_id,vacancy_id, type, village_id, uid,status, phone, property_endtime, property_starttime, name, parent_id,ic_card, id_card, user_number,bind_number';
            $userList = $dbHouseVillageUserBind->getList($whereUserBind, $field);
            if ($userList && ! is_array($userList)) {
                $userList = $userList->toArray();
            }
        } elseif ($pigcms_id) {
            $whereUserBind = [];
            $whereUserBind[] = ['pigcms_id', '=', $pigcms_id];
            $whereUserBind[] = ['status', '=', 1];
            $field = 'pigcms_id, single_id, floor_id, layer_id,vacancy_id, type, village_id, uid,status, phone, property_endtime, property_starttime, name, parent_id,ic_card, id_card, user_number,bind_number';
            $userList = $dbHouseVillageUserBind->getList($whereUserBind, $field);
            if ($userList && ! is_array($userList)) {
                $userList = $userList->toArray();
            }
        } else {
            fdump_api(['msg' => '单人同步-无同步对象', 'pigcms_id' => $pigcms_id, 'params' => $params], '260Cloud/handleUserSynDevice', 1);
            return false;
        }
        fdump_api(['msg' => '单人同步', 'pigcms_id' => $pigcms_id, 'userList' => $userList], '260Cloud/handleUserSynDevice', 1);
        $devices = [];
        $dbHouseFaceDevice = new HouseFaceDevice();
        $paramArr = [];
        $idStatusErr = [];
        try {
            foreach ($userList as $user) {
                if (intval($user['status']) != 1) {
                    $idStatusErr[] = $user['pigcms_id'];
                    continue;
                }
                $village_id = isset($user['village_id']) && $user['village_id'] ? $user['village_id'] : 0;
                if ($devices && isset($devices[$village_id])) {
                    $deviceList = $devices[$village_id];
                } else {
                    $whereDeviceFace = [];
                    $whereDeviceFace[] = ['village_id', '=', $village_id];
                    $whereDeviceFace[] = ['is_del', '=', 0];
                    $whereDeviceFace[] = ['device_type', '=', $device_type];
                    $deviceList = $dbHouseFaceDevice->getList($whereDeviceFace, 'device_id, device_sn, device_type, floor_id, thirdChnNum', 1);
                    if ($deviceList && ! is_array($deviceList)) {
                        $deviceList = $deviceList->toArray();
                    }
                }
                $user_floor_id = intval($user['floor_id']);
                $hasSyn = false; // 是否有同步对象
                foreach ($deviceList as $device) {
                    $floor_id = isset($device['floor_id']) ? $device['floor_id'] : 0;
                    $thirdChnNum = isset($device['thirdChnNum']) ? strval($device['thirdChnNum']) : '';
                    if ($floor_id !== -1 && $floor_id !== 0 && $floor_id !== '0' && $floor_id) {
                        $floorIds = explode(',', strval($floor_id));
                        if ($user_floor_id !== 0 && ! in_array($user_floor_id, $floorIds)) {
                            // 设备有归属单元 且人员不属于该单元 所以不能同步到此设备上
                            fdump_api(['msg' => '设备有归属单元 且人员不属于该单元 所以不能同步到此设备上', 'user' => $user, 'device' => $device], '260Cloud/handleUserSynDevice', 1);
                            continue;
                        }
                    } else {
                        $floorIds = [];
                    }
                    if ($thirdChnNum && ! empty($floorIds) && isset($user['vacancy_id']) && intval($user['vacancy_id']) > 0) {
                        $db_room   = new HouseVillageUserVacancy();
                        $whereRoom = [];
                        $whereRoom[] = ['pigcms_id', '=', $user['vacancy_id']];
                        $whereRoom[] = ['room', 'like', '%' . $thirdChnNum];
                        $roomInfo = $db_room->getOne($whereRoom, 'pigcms_id');
                        if (! isset($roomInfo['pigcms_id']) || ! $roomInfo['pigcms_id']) {
                            // 设备有房号匹配 且人员不属于该房号 所以不能同步到此设备上
                            fdump_api(['msg' => '设备有房号匹配 且人员不属于该房号 所以不能同步到此设备上', 'user' => $user, 'vacancy_id' => $user['vacancy_id'], 'device' => $device], '260Cloud/handleUserSynDevice', 1);
                            continue;
                        }
                    }
                    $hasSyn = true;
                    $param = [];
                    $param['device_type'] = $device_type;
                    $param['device_sn'] = $device['device_sn'];
                    $param['device_id'] = $device['device_id'];
                    $param['cmd_id'] = $user['pigcms_id'];
                    $param['user'] = $user;
                    $paramArr[] = $param;
                }
                if (! $hasSyn) {
                    $whereUserBindFace = [];
                    $whereUserBindFace[] = ['pigcms_id','=',$user['pigcms_id']];
                    $dbHouseVillageUserBind->saveOne($whereUserBindFace, ['face_img_status' => 2, 'face_img_reason' => "无对应符合条件的同步设备【对应单元或者公共设备】，或者物业费到期"]);
                }
            }
        } catch (\Exception $e) {
            fdump_api(['userList' => $userList,'err' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()],'260Cloud/handleUserSynDevice',1);
            return false;
        }
        fdump_api(['msg' => '单人同步', 'pigcms_id' => $pigcms_id, 'paramArr' => $paramArr, 'idStatusErr' => $idStatusErr], '260Cloud/handleUserSynDevice', 1);
        if (! empty($idStatusErr)) {
            // 状态异常
            $whereStatus = [];
            $whereStatus[] = ['pigcms_id','in',$idStatusErr];
            $dbHouseVillageUserBind->saveOne($whereStatus, ['face_img_status' => 2, 'face_img_reason' => "目前处于状态异常，无法同步"]);
        }
        if (count($paramArr) < 5) {
            // 小于5条数据直接执行 超出走队列
            foreach ($paramArr as $params) {
                $this->handleUser($params);
            }
        } else {
            foreach ($paramArr as $params) {
                if (isset($params['user'])) {
                    unset($params['user']);
                }
                $params['job_type'] = 'handleUser';
                $this->traitCommonCloud260($params);
            }
        }
        return true;
    }
    
    /**
     * 单个用户写入指令表.
     */
    public function handleUser($params) {
        $device_type = isset($params['device_type']) ? $params['device_type'] : Cloud260Const::CLOUD_260_DEVICE_TYPE;
        $device_id = isset($params['device_id']) ? $params['device_id'] : 0;
        $device_sn = isset($params['device_sn']) ? $params['device_sn'] : '';
        $cmd_id = isset($params['cmd_id']) ? $params['cmd_id'] : $params['pigcms_id'];
        fdump_api(['msg' => '单个用户写入指令表', 'cmd_id' => $cmd_id, '$params' => $params], '260Cloud/handleUser', 1);
        $user = isset($params['user']) ? $params['user'] : [];
        if ($cmd_id && empty($user)) {
            $user = (new HouseVillageUserBind())->getOne(['pigcms_id' => $cmd_id]);
            if (intval($user['status'] != 1)) {
                // 状态异常
                $whereStatus = [];
                $whereStatus[] = ['pigcms_id','=',$cmd_id];
                (new HouseVillageUserBind())->saveOne($whereStatus, ['face_img_status' => 2, 'face_img_reason' => "目前处于状态异常，无法同步"]);
                return true;
            }
        }
        $uid = isset($user['uid']) ? $user['uid'] : 0;
        $phone = isset($user['phone']) ? $user['phone'] : '';
        $village_id = isset($user['village_id']) ? $user['village_id'] : 0;
        $cmd = 'selectPassInfo';
        $cmd_data = [
            'type' => 'face',
            'device_type' => $device_type,
            'cmd' => $cmd,
            'cmd_direction' => Cloud260Const::CMD_DIRECTION0,
            'device_sn' => $device_sn,
        ];
        $nowTime = time();
        $type = isset($user['type']) ? intval($user['type']) : -1;
        $dbFaceUserBindDevice = new FaceUserBindDevice();
        $whereFaceUserBindDevice = [];
        $whereFaceUserBindDevice[] = ['bind_type', '=', Cloud260Const::CLOUD_260_FACE_USER_BIND_DEVICE_BIND_TYPE];
        $whereFaceUserBindDevice[] = ['bind_id', '=', $cmd_id];
        $whereFaceUserBindDevice[] = ['device_type', '=', Cloud260Const::CLOUD_260_DEVICE_TYPE];
        $fieldFaceUserBindDevice = 'id, bind_type, bind_id, device_type, open_key, open_key_time, code, person_id, person_text';
        $infoFaceUserBindDevice = $dbFaceUserBindDevice->getOne($whereFaceUserBindDevice, $fieldFaceUserBindDevice);
        $infoFaceUserBindDevice = ($infoFaceUserBindDevice && ! is_array($infoFaceUserBindDevice)) ? $infoFaceUserBindDevice->toArray() : [];
        fdump_api(['msg' => '单个用户写入指令表', 'cmd_id' => $cmd_id, 'whereFaceUserBindDevice' => $whereFaceUserBindDevice, 'infoFaceUserBindDevice' => $infoFaceUserBindDevice], '260Cloud/handleUser', 1);
        $open_key_time = isset($infoFaceUserBindDevice['open_key_time']) && $infoFaceUserBindDevice['open_key_time'] > 0 ? $infoFaceUserBindDevice['open_key_time'] : 0;

        $nowTimeOpen = $nowTime + 10;// 避免下发不及时问题导致误差 扩充10进行界限判断
        $codeRefresh = true; // 二维码是否需要队列刷新
        if ($type === 6 || (isset($infoFaceUserBindDevice['open_key']) && $infoFaceUserBindDevice['open_key'] === 'visitor')) {
            // 访客二维码
            $codeRefresh = false;
            $employeeQRCode = isset($infoFaceUserBindDevice['code']) && $infoFaceUserBindDevice['code'] ? $infoFaceUserBindDevice['code'] : '';
            
            $person_text = isset($infoFaceUserBindDevice['person_text']) ? json_decode($infoFaceUserBindDevice['person_text'], true) : [];
            if (isset($person_text['openTimes']) && $person_text['openTimes']) {
                $openTimes = $person_text['openTimes'];
            } else {
                $openTimes = $this->getQrCodeNumber($village_id);
            }
        } elseif ($open_key_time > 1 && $open_key_time > $nowTimeOpen) {
            // 未失效内二维码 当前时间+时效间隔
            $employeeQRCode = isset($infoFaceUserBindDevice['code']) && $infoFaceUserBindDevice['code'] ? $infoFaceUserBindDevice['code'] : '';
        }
        // 类型  0房主  1，家人  2租客  3，更新房主  4工作人员 5访客 6出入证
        $openKeyArr = [0 => 'owner', 1 => 'family', 2 => 'tenant', 3 => 'owner', 4 => 'work', 5 => 'visitor', 6 => 'visitor'];
        $open_key = isset($openKeyArr[$type]) && $openKeyArr[$type] ? $openKeyArr[$type] : '';

        $bind_arr = [
            'bind_type'   => Cloud260Const::CLOUD_260_FACE_USER_BIND_DEVICE_BIND_TYPE,
            'bind_id'     => $cmd_id,
            'device_type' => Cloud260Const::CLOUD_260_DEVICE_TYPE,
        ];
        if (! isset($employeeQRCode) || ! $employeeQRCode) {
            $openKeyTime = time() + Cloud260Const::CLOUD_260_QRCODE_REFRESH_INTERVAL;
            $bind_arr['open_key_time'] = $openKeyTime;
            // 当前没有二维码 或者已失效 重新生成二维码串
            $employeeQRCodeArr = [
                'cmd_id' => $cmd_id,
                'cmd' => $cmd,
                'device_text' => 'cloud260',
                'device_type' => $device_type,
                'type' => 'face',
                'time' => time(),
                'unique' => uniqid(),
            ];
            $employeeQRCode = md5(json_encode($employeeQRCodeArr,JSON_UNESCAPED_UNICODE));
            if ($type === 6) {
                $codeRefresh = false;
                $openTimes = $this->getQrCodeNumber($village_id);
            }
        }
        $parameterInfo = [
            'employeeId' => strval($cmd_id),
            'employeeName' => isset($user['name']) && trim($user['name']) ? trim($user['name']) : '用户'.$cmd_id,
            'employeeQRCode' => $employeeQRCode,
        ];
        (new HouseVillageUserBind())->saveOne(['pigcms_id' => $cmd_id], ['bind_third_id' => $employeeQRCode]);
        fdump_api(['msg' => '单个用户写入指令表', 'cmd_id' => $cmd_id, 'cmd_data' => $cmd_data, 'parameterInfo' => $parameterInfo], '260Cloud/handleUser', 1);
        if ($uid) {
            $employeePhoto = $this->getUserFaceImg($uid);
            fdump_api(['msg' => '单个用户写入指令表', 'cmd_id' => $cmd_id, 'employeePhoto' => $employeePhoto, 'uid' => $uid, 'user' => $user], '260Cloud/handleUser', 1);
        }
        if (isset($employeePhoto) && $employeePhoto) {
            $parameterInfo['employeePhotoWay'] = 'path';
            $parameterInfo['employeePhoto'] = $employeePhoto;
        }
        if (isset($user['ic_card']) && trim($user['ic_card'])) {
            $parameterInfo['employeeIc'] = trim($user['ic_card']);
        }
        if (isset($user['phone']) && $user['phone']) {
            $parameterInfo['employeePhone'] = $user['phone'];
        }
        if (isset($user['id_card']) && trim($user['id_card'])) {
            $parameterInfo['employeeIdentity'] = trim($user['id_card']);
        }
        // 楼栋
        $single_id = isset($user['single_id']) && $user['single_id'] ? intval($user['single_id']) : 0;
        if ($single_id) {
            $dbHouseVillageSingle = new HouseVillageSingle();
            $singleInfo = $dbHouseVillageSingle->getOne(['id' => $single_id], 'id, single_name, single_number');
        }
        // 单元
        $floor_id = isset($user['floor_id']) && $user['floor_id'] ? intval($user['floor_id']) : 0;
        if ($floor_id) {
            $dbHouseVillageFloor = new HouseVillageFloor();
            $floorInfo = $dbHouseVillageFloor->getOne(['floor_id' => $floor_id], 'floor_id, floor_name, floor_number');
        }
        if (isset($singleInfo['id']) && $singleInfo['id']) {
            $single_number = isset($singleInfo['single_number']) ? trim($singleInfo['single_number']) : '';
        } else {
            $single_number = '';
        }
        if (isset($floorInfo['floor_id']) && $floorInfo['floor_id']) {
            $floor_number = isset($floorInfo['floor_number']) ? trim($floorInfo['floor_number']) : '';
        } else {
            $floor_number = '';
        }
        ($single_number || $floor_number) && $parameterInfo['build_number'] = strval($single_number . $floor_number);
        
        // 取改单元下对应人员身份的所有楼层
        if ($floor_id) {
            if ($uid > 0) {
                $phone = '';
            }
            // 楼层
            $layer_id = isset($user['layer_id']) && $user['layer_id'] ? intval($user['layer_id']) : 0;
            $floor_number_arr = $this->getFloorUserAllLayer($village_id, $floor_id, $uid, $phone, $layer_id);
            if (!empty($floor_number_arr)) {
                $parameterInfo['floor_number'] = $floor_number_arr;
            }
        }
        
        fdump_api(['msg' => '单个用户写入指令表', 'cmd_id' => $cmd_id, 'user' => $user, '$single_id' => $single_id, '$floor_id' => $floor_id], '260Cloud/handleUser', 1);
        try {
            $userTimes = (new VisitorInviteService())->getUserEndTime($cmd_id, ['isDelay' => 1]);
        } catch (\Exception $e){
            fdump_api(['msg' => '单个用户写入指令表', 'cmd_id' => $cmd_id, 'err' => $e->getMessage()], '260Cloud/handleUser', 1);
            return '';
        }
        fdump_api(['msg' => '单个用户写入指令表', 'cmd_id' => $cmd_id, 'userTimes' => $userTimes], '260Cloud/handleUser', 1);
        $propertyStartTime = isset($userTimes['propertyStartTime']) ? intval($userTimes['propertyStartTime']) : 0;
        $propertyEndTime = isset($userTimes['propertyEndTime']) ? intval($userTimes['propertyEndTime']) : 0;
        $devicePassStart = $propertyStartTime > 0 ? date('Y-m-d H:i:s', $propertyStartTime) : date('Y-m-d H:i:s');
        $devicePassEnd = $propertyEndTime > 0 ? date('Y-m-d H:i:s', $propertyEndTime) : '2038-12-31 23:59:59';
        if (intval($openTimes) > 65535) {
            $openTimes = 65534;
        }
        if (intval($openTimes) > 0) {
            $devicePassNumber = $openTimes;
        } else {
            $devicePassNumber = 0;
        }
        $devicePassBean = [
            'devicePassStart' => $devicePassStart,
            'devicePassEnd' => $devicePassEnd,
            'devicePassTimeOver' => ($propertyStartTime === 0 && $propertyEndTime === 0) ? 0 : 1,
            'devicePassNumber' => $devicePassNumber,
        ];
        $parameterInfo['devicePassBean'] = $devicePassBean;
        $cmd_order = $parameterInfo;
        $cmd_data['cmd_id'] = $cmd_id;
        $unique_cmd = md5(json_encode($cmd_data,JSON_UNESCAPED_UNICODE));
        $cmd_data['device_id'] = $device_id;
        $cmd_data['cmd_order'] = json_encode($cmd_order,JSON_UNESCAPED_UNICODE);
        $cmd_data['unique_cmd'] = $unique_cmd;
        $cmd_data['status'] = 0;
        $whereUnique = [
            'unique_cmd' => $unique_cmd,
            'status' => 0,
        ];
        $dbHouseVillageDeviceCmd = new HouseVillageDeviceCmd();
        fdump_api(['msg' => '单个用户写入指令表', 'cmd_id' => $cmd_id, 'whereUnique' => $whereUnique, 'cmd_data' => $cmd_data], '260Cloud/handleUser', 1);
        $uniqueInfo = $dbHouseVillageDeviceCmd->getOne($whereUnique, 'id');
        if (isset($uniqueInfo['id']) && $uniqueInfo['id']) {
            $cmd_data['last_time'] = $nowTime;
            $dbHouseVillageDeviceCmd->updateThis($whereUnique, $cmd_data);
            $cmd_info_id = $uniqueInfo['id'];
        } else {
            $cmd_data['add_time'] = $nowTime;
            $cmd_info_id = $dbHouseVillageDeviceCmd->add($cmd_data);
        }

        $bind_arr['code'] = $employeeQRCode;
        ! isset($infoFaceUserBindDevice['id']) && $bind_arr['open_key'] = $open_key;
        if (isset($employeeQRCodeArr) && ! empty($employeeQRCodeArr)) {
            $bind_arr['face_txt'] = json_encode($employeeQRCodeArr,JSON_UNESCAPED_UNICODE);
        }
        if ($type === 6) {
            // 查询下 看是否可以定位到访客id
            $dbHouseVillageVisitor = new HouseVillageVisitor();
            $whereVisitor = [
                ['visitor_bind_id', '=', $cmd_id],
                ['village_id', '=', $village_id],
            ];
            $visitorInfo = $dbHouseVillageVisitor->get_one($whereVisitor, 'id, owner_uid, vacancy_id, village_id');
            if (isset($visitorInfo['id']) && $visitorInfo['id']) {
                $bind_arr['person_id'] = $visitorInfo['id'];
                isset($visitorInfo['owner_uid']) && $bind_arr['personID'] = intval($visitorInfo['owner_uid']);
                isset($visitorInfo['vacancy_id']) && $bind_arr['group_id'] = intval($visitorInfo['vacancy_id']);
                $bind_arr['groupID'] = $village_id;
            }
        } else {
            ! isset($bind_arr['person_id']) && $bind_arr['person_id'] = $cmd_info_id;
            isset($user['vacancy_id']) && $bind_arr['group_id'] = $user['vacancy_id'];
            $bind_arr['groupID'] = $village_id;
        }
        $bind_arr['face_status'] = 1;
        fdump_api(['msg' => '关联记录', 'cmd_id' => $cmd_id, 'bind_arr' => $bind_arr, 'infoFaceUserBindDevice' => $infoFaceUserBindDevice], '260Cloud/handleUser', 1);
        if (! isset($infoFaceUserBindDevice['id'])) {
            $bind_arr['open_key'] = $open_key;
            $bind_arr['add_time'] = $nowTime;
            $visitor_param = [
                'cardNo'     => $employeeQRCode,
                'effectTime' => $devicePassStart,
                'expireTime' => $devicePassEnd,
                'openTimes'  => $devicePassNumber, // 开门总次数 0为不限制
                'surplusOpenTimes' => $devicePassNumber, // 剩余次数
            ];
            $bind_arr['person_text'] = json_encode($visitor_param,JSON_UNESCAPED_UNICODE);
            $dbFaceUserBindDevice->addData($bind_arr);
        } else {
            $bind_arr['last_time'] = $nowTime;
            $dbFaceUserBindDevice->saveData(['id' => $infoFaceUserBindDevice['id']], $bind_arr);
        }
        if ($employeeQRCode) {
            // 同步刷新;
            $whereFaceUserBindDevice = [];
            $whereFaceUserBindDevice[] = ['bind_type', '=', DeviceConst::GARBAGE_COLLECTION_BIND_TYPE];
            $whereFaceUserBindDevice[] = ['bind_id', '=', $cmd_id];
            $whereFaceUserBindDevice[] = ['code', '<>', $employeeQRCode];
            $dbFaceUserBindDevice->saveData($whereFaceUserBindDevice, ['code' => $employeeQRCode]);
        }
        
        // 使用延时队列进行更新二维码
        if ($codeRefresh) {
            $userParams = [
                'uid' => $uid,
                'pigcms_id' => $cmd_id,
            ];
            $userParams['job_type'] = 'handleUserSynDevice';
            $job_id = $this->traitCommonCloud260($userParams, Cloud260Const::CLOUD_260_QRCODE_REFRESH_INTERVAL - 5);
            fdump_api(['msg' => '用户2小时刷新机制', 'userParams' => $userParams, 'job_id' => $job_id], '260Cloud/handleUser', 1);
        }
        return true;
    }
    
    /**
     * 更新状态.
     */
    protected function updateDeviceStatus($device_sn, $device_id = 0) {
        if (! $device_sn && ! $device_id) {
            return false;
        }
        $dbHouseFaceDevice = new HouseFaceDevice();
        $whereFaceDevice = [
            'is_del' => 0,
            'device_type' => Cloud260Const::CLOUD_260_DEVICE_TYPE,
        ];
        if ($device_sn) {
            $whereFaceDevice['device_sn'] = $device_sn;
        }
        if ($device_id) {
            $whereFaceDevice['device_id'] = $device_id;
        }
        $saveData = [
            'device_status' => 1,
            'login_time' => time(),
            'notify_time' => time(),
        ];
        $dbHouseFaceDevice->saveData($whereFaceDevice, $saveData);
        return true;
    }

    /**
     * 更新附加信息.
     */
    public function updateSyncTime($device_id, $data = []) {
        if (! $device_id || ! $data) {
            return false;
        }
        $dbHouseFaceDeviceAbout = new HouseFaceDeviceAbout();
        $aboutInfo = $dbHouseFaceDeviceAbout->getOne(['device_id' => $device_id]);
        if (isset($aboutInfo['device_id'])) {
            $data['last_time'] = time();
            $dbHouseFaceDeviceAbout->updateThis(['device_id' => $device_id], $data);
        } else {
            $data['device_id'] = $device_id;
            $data['add_time'] = time();
            $dbHouseFaceDeviceAbout->add($data);
        }
        return true;
    }

    /**
     * 处理-设备上传当前设备初始参数信息.
     */
    public function inertParameterUpload($params) {
        $cmd_info_id = isset($params['cmd_info_id']) && $params['cmd_info_id'] ? $params['cmd_info_id'] : 0;
        if (! $cmd_info_id) {
            return ['code'    => 1001, 'message' => '缺少必要参数'];
        }
        $whereUnique = ['id' => $cmd_info_id, 'status' => 0];
        $dbHouseVillageDeviceCmd = new HouseVillageDeviceCmd();
        $dbHouseFaceDevice = new HouseFaceDevice();
        $cmdInfo = $dbHouseVillageDeviceCmd->getOne($whereUnique);
        if (isset($cmdInfo['cmd_order']) && $cmdInfo['cmd_order']) {
            $deviceInfo = $this->commonUpload($cmdInfo, $whereUnique);
            if (isset($deviceInfo['code']) && isset($deviceInfo['message'])) {
                return $deviceInfo;
            }
            $cmd_order = json_decode($cmdInfo['cmd_order'],true);
            // 音量大小(范围 0-84)
            $about = [];
            $device = [];
            if (isset($cmd_order['parameterVolume'])) {
                $about['device_volume'] = $cmd_order['parameterVolume'];
                unset($cmd_order['parameterVolume']);
            }
            // 主动触发云端轮训时间（以秒为单位）
            if (isset($cmd_order['parameterPolling'])) {
                $about['polling_space'] = $cmd_order['parameterPolling'];
                unset($cmd_order['parameterPolling']);
            }
            // 活体检测,1 打开，0 关闭
            if (isset($cmd_order['parameterPreview'])) {
                $device['device_alive'] = intval($cmd_order['parameterPreview']) === 0 ? 0 : 1;
                unset($cmd_order['parameterPreview']);
            }
            // 人脸比对阈值 0.01 - 1
            if (isset($cmd_order['parameterFaceThreshold'])) {
                $parameterFaceThreshold = $cmd_order['parameterFaceThreshold'] * 100;
                $device['device_score'] = round_number($parameterFaceThreshold, 0);
                unset($cmd_order['parameterFaceThreshold']);
            }
            // 设备登陆账号
            if (isset($cmd_order['parameterLoginName']) && $cmd_order['parameterLoginName']) {
                $device['thirdLoginName'] = trim($cmd_order['parameterLoginName']);
                unset($cmd_order['parameterLoginName']);
            }
            // 设备登陆密码
            if (isset($cmd_order['parameterLoginPassword']) && $cmd_order['parameterLoginPassword']) {
                $device['thirdLoginPassword'] = trim($cmd_order['parameterLoginPassword']);
                unset($cmd_order['parameterLoginPassword']);
            }
            // 设备登陆密码
            if (isset($cmd_order['parameterFirmwareMsg']) && $cmd_order['parameterFirmwareMsg']) {
                $device['sdk_version'] = trim($cmd_order['parameterFirmwareMsg']);
            }
            // 人脸识别距离 范围：50-150 单位 cm
            if (isset($cmd_order['parameterRecgDistance'])) {
                $about['recg_distance'] = $cmd_order['parameterRecgDistance'];
                unset($cmd_order['parameterRecgDistance']);
            }
            // 设备重启周期 0：月 1：周 2：日
            if (isset($cmd_order['parameterRebootInterval'])) {
                $about['reboot_interval'] = $cmd_order['parameterRebootInterval'];
                unset($cmd_order['parameterRebootInterval']);
            }
            // 设备重启时间 例子：03:00
            if (isset($cmd_order['parameterRebootTime'])) {
                $about['reboot_time'] = $cmd_order['parameterRebootTime'];
                unset($cmd_order['parameterRebootTime']);
            }
            $about['parameter_json'] = json_encode($cmd_order,JSON_UNESCAPED_UNICODE);
            if (! empty($device)) {
                $whereFaceDevice = ['is_del' => 0, 'device_type' => Cloud260Const::CLOUD_260_DEVICE_TYPE];
                if (isset($cmdInfo['device_id']) && $cmdInfo['device_id']) {
                    $whereFaceDevice['device_id'] = $cmdInfo['device_id'];
                }
                if (isset($cmdInfo['device_sn']) && $cmdInfo['device_sn']) {
                    $whereFaceDevice['device_sn'] = $cmdInfo['device_sn'];
                }
                $dbHouseFaceDevice->saveData($whereFaceDevice, $device);
            }
            $device_id = $deviceInfo['device_id'];
            $dbHouseFaceDeviceAbout = new HouseFaceDeviceAbout();
            $aboutInfo = $dbHouseFaceDeviceAbout->getOne(['device_id' => $device_id]);
            if (isset($aboutInfo['device_id'])) {
                $about['last_time'] = time();
                $dbHouseFaceDeviceAbout->updateThis(['device_id' => $device_id], $about);
            } else {
                $about['device_id'] = $device_id;
                $about['add_time'] = time();
                $id = $dbHouseFaceDeviceAbout->add($about);
            }
            $data = ['status' => 2, 'reason' => '', 'last_time' => time(), 'finish_time' => time()];
            $dbHouseVillageDeviceCmd->updateThis($whereUnique, $data);
        } else {
            $data = ['status' => 3, 'reason' => '缺少内容', 'last_time' => time(), 'finish_time' => time()];
            $dbHouseVillageDeviceCmd->updateThis($whereUnique, $data);
        }
        // 是用队列移除重复的数据指令 注意保留最新的3个相同对应相同指令
        $clearRepeatCmdParam = [
            'job_type' => 'clearRepeatCmd',
            'reserveLastNum' => 3, // 保留最新相同指令数
            'cmd_info_id' => $cmd_info_id,
        ];
        // 延迟120秒 避免短时间执行多次
        $this->traitCommonCloud260($clearRepeatCmdParam, Cloud260Const::CLOUD_260_CMD_CLEAR_SPACE_TIMES);
        return true;
    }

    /**
     * 处理-设备主动往云端实时上传打卡记录.
     */
    public function insertNoteFaceUpload($params) {
        $cmd_info_id = isset($params['cmd_info_id']) && $params['cmd_info_id'] ? $params['cmd_info_id'] : 0;
        fdump_api(['msg' => '初入-设备主动往云端实时上传打卡记录', 'cmd_info_id' => $cmd_info_id], '260Cloud/insertNoteFaceUploadLog', 1);
        if (! $cmd_info_id) {
            return ['code'    => 1001, 'message' => '缺少必要参数'];
        }
        $whereUnique = ['id' => $cmd_info_id, 'status' => 0];
        $dbHouseVillageDeviceCmd = new HouseVillageDeviceCmd();
        $cmdInfo = $dbHouseVillageDeviceCmd->getOne($whereUnique);
        fdump_api(['msg' => '指令信息-设备主动往云端实时上传打卡记录', 'cmd_info_id' => $cmd_info_id, 'cmdInfo' => $cmdInfo&&!is_array($cmdInfo) ? $cmdInfo->toArray() : $cmdInfo], '260Cloud/insertNoteFaceUploadLog', 1);
        $allOk = true;
        $dbHouseVillageVisitor = new HouseVillageVisitor();
        $dbHouseVillageVisitorPass = new HouseVillageVisitorPass();
        try {
            if (isset($cmdInfo['cmd_order']) && $cmdInfo['cmd_order']) {
                fdump_api(['msg' => '设备信息-设备主动往云端实时上传打卡记录', 'cmd_info_id' => $cmd_info_id, 'whereUnique' => $whereUnique], '260Cloud/insertNoteFaceUploadLog', 1);
                $deviceInfo = $this->commonUpload($cmdInfo, $whereUnique);
                if (isset($deviceInfo['code']) && isset($deviceInfo['message'])) {
                    fdump_api(['msg' => '设备信息-设备主动往云端实时上传打卡记录', 'cmd_info_id' => $cmd_info_id, 'deviceInfo' => $deviceInfo&&!is_array($deviceInfo) ? $deviceInfo->toArray() : $deviceInfo], '260Cloud/insertNoteFaceUploadLog', 1);
                    return $deviceInfo;
                }
                fdump_api(['msg' => '设备信息-设备主动往云端实时上传打卡记录', 'cmd_info_id' => $cmd_info_id, 'deviceInfo' => $deviceInfo&&!is_array($deviceInfo) ? $deviceInfo->toArray() : $deviceInfo], '260Cloud/insertNoteFaceUploadLog', 1);
                $cmd_order = json_decode($cmdInfo['cmd_order'],true);
                $device_id = $deviceInfo['device_id'];
                $village_id = $deviceInfo['village_id'];
                $device_sn = $deviceInfo['device_sn'];
                $eventIdArr = [
                    'village_id' => $village_id, 'device_id' => $device_id
                ];
                $houseUserLog = [
                    'log_business_id' => $village_id, 'device_id' => $device_id, 'device_sn' => $device_sn,
                    'log_name' => $deviceInfo['device_name'],
                ];
                $tip = [
                    'village_id' => $village_id, 'type' => 1, 'camera_sn' => $device_sn,
                ];
                // 0:刷脸，1:打卡 2:远程 3:二维码
                $noteWay = isset($cmd_order['noteWay']) ? intval($cmd_order['noteWay']) : 0;
                $noteWayToLogFromArr = [
                    0 => 1, 1 => 3, 2 => 2, 3 => 31,
                ];
                $eventIdArr['noteWay'] = $noteWay;
                $log_from = isset($noteWayToLogFromArr[$noteWay]) ? $noteWayToLogFromArr[$noteWay] : 1;
                $houseUserLog['log_from'] = $log_from;
                if (isset($cmd_order['employeeName']) && $cmd_order['employeeName']) {
                    $employeeName = $cmd_order['employeeName'];
                    $houseUserLog['person_name'] = $employeeName;
                }
                if (isset($cmd_order['employeeId']) && $cmd_order['employeeId']) {
                    $employeeId = $cmd_order['employeeId'];
                    $eventIdArr['employeeId'] = $employeeId;
                    $houseUserLog['log_bind_id'] = $employeeId;
                    $tip['bind_id'] = $employeeId;
                    $user = (new HouseVillageUserBind())->getOne(['pigcms_id' => $employeeId], 'pigcms_id, uid, name, type');
                    fdump_api(['msg' => '用户信息-设备主动往云端实时上传打卡记录', 'cmd_info_id' => $cmd_info_id, 'user' => $user&&!is_array($user) ? $user->toArray() : $user], '260Cloud/insertNoteFaceUploadLog', 1);
                    if (isset($user['uid']) && $user['uid']) {
                        $houseUserLog['uid'] = $user['uid'];
                        $tip['uid'] = $user['uid'];
                    }
                    if (! isset($houseUserLog['person_name']) && isset($user['name']) && trim($user['name'])) {
                        $houseUserLog['person_name'] = trim($user['name']);
                    }
                } else {
                    $employeeId = 0;
                    $user = [];
                }
                if (isset($cmd_order['noteTime'])) {
                    $noteTime = strtotime($cmd_order['noteTime']) ? strtotime($cmd_order['noteTime']) : time();
                } else {
                    $noteTime = time();
                }
                if (isset($cmd_order['employeeIc']) && $cmd_order['employeeIc']) {
                    $houseUserLog['card_number'] = $cmd_order['employeeIc'];
                }
                $eventIdArr['log_time'] = $noteTime;
                $houseUserLog['log_time'] = $noteTime;
                $tip['camera_time'] = $noteTime;
                $cut_image1 = '';
                if (isset($cmd_order['noteImg']) && $cmd_order['noteImg']) {
                    $datePath = date('Ymd');
                    $imgPath = '/upload/face_log/' . $datePath . '/';
                    $savePath = app()->getRootPath() . '..' . $imgPath;
                    $noteImg = str_replace(['data:image/png;base64,','data:image/jpg;base64,'], '', $cmd_order['noteImg']);
                    if (isset($cmd_order['noteImgName']) && $cmd_order['noteImgName']) {
                        $cut_image = base64_to_img($savePath, $cmd_order['noteImgName'], $noteImg, '');
                        if ($cut_image) {
                            $cut_image1 = $imgPath . $cmd_order['noteImgName'];
                        }
                    } else {
                        $file_name = md5($cmd_info_id . $noteTime);
                        $cut_image = base64_to_img($savePath, $file_name, $noteImg, 'jpg');
                        if ($cut_image) {
                            $cut_image1 = $imgPath . $file_name . '.jpg';
                        }
                    }
                    fdump_api(['msg' => '图片-设备主动往云端实时上传打卡记录', 'cmd_info_id' => $cmd_info_id, 'cut_image' => $cut_image], '260Cloud/insertNoteFaceUploadLog', 1);
                    if ($cut_image1) {
                        $houseUserLog['cut_image'] = $cut_image1;
                        $tip['camera_img'] = $cut_image1;
                    } else {
                        $allOk = false;
                        fdump_api(['cmdInfo' => $cmdInfo, 'savePath' => $savePath, 'file_name' => $file_name, 'cut_image' => $cut_image, 'cut_image1' => $cut_image1], '260Cloud/err_cut_image');
                    }
                }
                $log_status = 0;
                if (isset($cmd_order['notePass'])) {
                    // 打卡是否通过（若设备只在打卡成功后才做记录，可删除此字段） 0:未通过 1：通过 2：无权限 4：已过期 5：次数用完
                    $notePass = intval($cmd_order['notePass']);
                    $notePassToLogStatusArr = [
                        0 => 1, 1 => 0, 2 => 1, 4 => 7, 5 => 8,
                    ];
                    $log_status = isset($notePassToLogStatusArr[$notePass]) ? $notePassToLogStatusArr[$notePass] : 0;
                }
                $houseUserLog['log_status'] = $log_status;
                $tip['status'] = $log_status === 0 ? 0 : 1;
                if (isset($cmd_order['notetemptype']) || isset($cmd_order['humTemp'])) {
                    // 0:默认摄氏度，1:华氏度
                    $notetemptype = isset($cmd_order['notetemptype']) ? intval($cmd_order['notetemptype']) : 0;
                    // 人体测量温度
                    $humTemp = isset($cmd_order['humTemp']) ? intval($cmd_order['humTemp']) : 0;
                    $temperature = 0;
                    if ($notetemptype === 1 && $humTemp > 0) {
                        $temperature = (double)(($humTemp - 32) * 5) / 9;
                    } elseif ($humTemp > 0) {
                        $temperature = $humTemp;
                    }
                    $temperature = round_number($temperature, 2);
                    $houseUserLog['temperature'] = $temperature;
                    $tip['temperature'] = $temperature;
                }
                $log_detail = $cmd_order;
                if ($cut_image1) {
                    $log_detail['face_img'] = $cut_image1;
                }
                $houseUserLog['log_detail'] = json_encode($log_detail,JSON_UNESCAPED_UNICODE);
                if (isset($log_detail['face_img']) && trim($log_detail['face_img'])) {
                    $houseUserLog['cut_image'] = trim($log_detail['face_img']);
                }
                $dbHouseUserLog = new HouseUserLog();
                $eventId = md5(json_encode($eventIdArr,JSON_UNESCAPED_UNICODE));
                $houseUserLog['eventId'] = $eventId;
                $logInfo = $dbHouseUserLog->getFind(['eventId' => $eventId], 'log_id');
                fdump_api(['msg' => '记录-设备主动往云端实时上传打卡记录', 'cmd_info_id' => $cmd_info_id, 'logInfo' => $logInfo&&!is_array($logInfo) ? $logInfo->toArray() : $logInfo], '260Cloud/insertNoteFaceUploadLog', 1);

                if (!$logInfo || ! isset($logInfo['log_id'])) {
                    fdump_api(['msg' => '记录参数-设备主动往云端实时上传打卡记录', 'cmd_info_id' => $cmd_info_id, 'houseUserLog' => $houseUserLog], '260Cloud/insertNoteFaceUploadLog', 1);
                    $logId = $dbHouseUserLog->addData($houseUserLog);
                    $villageInfo = (new HouseVillage())->getOne($village_id, 'village_id, city_id');
                    $tip['city_id'] = isset($villageInfo['city_id']) ? intval($villageInfo['city_id']) : 0;
                    fdump_api(['msg' => '记录捕捉事件参数-设备主动往云端实时上传打卡记录', 'cmd_info_id' => $cmd_info_id, 'tip' => $tip], '260Cloud/insertNoteFaceUploadLog', 1);
                    $tipId = (new HouseCameraTip())->addOne($tip);
                    if (! $logId || ! $tipId) {
                        $allOk = false;
                    }
                    // 判断是否是访客添加访客通行记录
                    $visitor_id = 0;
                    if ($employeeId && isset($user['type']) && intval($user['type']) === 6) {
                        $whereVisitor = [
                            ['visitor_bind_id', '=', $employeeId],
                            ['village_id', '=', $village_id],
                        ];
                        $visitorInfo = $dbHouseVillageVisitor->get_one($whereVisitor, 'id, owner_uid, vacancy_id, village_id, visitor_phone');
                        $visitor_id = isset($visitorInfo['id']) && $visitorInfo['id'] ? $visitorInfo['id'] : 0;
                        fdump_api(['msg' => '访客-设备主动往云端实时上传打卡记录', 'cmd_info_id' => $cmd_info_id, 'visitorInfo' => $visitorInfo&&!is_array($visitorInfo) ? $visitorInfo->toArray() : $visitorInfo], '260Cloud/insertNoteFaceUploadLog', 1);
                    }
                    if ($visitor_id > 0 && $log_status === 0) {
                        // 访客通行添加通行记录 成功通行才进行记录
                        $addArr = [
                            'visitor_id' => $visitor_id,
                            'village_id' => $village_id,
                            'uid' => isset($houseUserLog['uid']) && $houseUserLog['uid'] ? intval($houseUserLog['uid']) : 0,
                            'user_bind_id' => $employeeId,
                            'phone' => isset($visitorInfo['visitor_phone']) && trim($visitorInfo['visitor_phone']) ? trim($visitorInfo['visitor_phone']) : '',
                        ];
                        $device_name = isset($deviceInfo['device_name']) && $deviceInfo['device_name'] ? $deviceInfo['device_name'] : '';
                        if ($log_from == 1) {
                            $addArr['uname'] = $device_name . ' 人脸门禁通行';
                        } elseif ($log_from == 31) {
                            $addArr['uname'] = $device_name . ' 二维码门禁通行';
                        } elseif ($log_from == 2) {
                            $addArr['uname'] = $device_name . ' 远程开门门禁通行';
                        } else {
                            $addArr['uname'] = $device_name . ' 门禁通行';
                        }
                        $addArr['addtime'] = $noteTime;
                        fdump_api(['msg' => '访客通行记录-设备主动往云端实时上传打卡记录', 'cmd_info_id' => $cmd_info_id, 'addArr' => $addArr], '260Cloud/insertNoteFaceUploadLog', 1);
                        $dbHouseVillageVisitorPass->add($addArr);
                        $dataSave = [
                            'status'    => 5,
                            'pass_time' => $noteTime,
                        ];
                        $whereVisitor = [
                            'id' => $visitor_id
                        ];
                        fdump_api(['msg' => '更新访客为最新通行时间-设备主动往云端实时上传打卡记录', 'cmd_info_id' => $cmd_info_id, 'whereVisitor' => $whereVisitor, 'dataSave' => $dataSave], '260Cloud/insertNoteFaceUploadLog', 1);
                        $dbHouseVillageVisitor->saveOne($whereVisitor, $dataSave);
                    }
                }
                $data = [
                    'status' => 2,
                    'reason' => '',
                    'last_time' => time(),
                    'finish_time' => time(),
                ];
                $dbHouseVillageDeviceCmd->updateThis($whereUnique, $data);
            } else {
                $data = [
                    'status' => 3, 'reason' => '缺少内容', 'last_time' => time(), 'finish_time' => time(),
                ];
                fdump_api(['msg' => '缺少指令信息-设备主动往云端实时上传打卡记录', 'cmd_info_id' => $cmd_info_id, 'data' => $data], '260Cloud/insertNoteFaceUploadLog', 1);
                $dbHouseVillageDeviceCmd->updateThis($whereUnique, $data);
                $allOk = false;
            }
        } catch (\Exception $e){
            fdump_api([
                'msg' => '错误-设备主动往云端实时上传打卡记录', 'cmd_info_id' => $cmd_info_id, 'getCode' => $e->getCode(), 
                'getMessage' => $e->getMessage()
            ], '260Cloud/insertNoteFaceUploadLog', 1);
            return '';
        }
        if ($allOk) {
            // 完整记录了 就删除 中间指令表中对应数据
            $dbHouseVillageDeviceCmd->deleteInfo($whereUnique);
        }
        // 是用队列移除重复的数据指令 注意保留最新的3个相同对应相同指令
        $clearRepeatCmdParam = [
            'job_type' => 'clearRepeatCmd',
            'reserveLastNum' => 3, // 保留最新相同指令数
            'cmd_info_id' => $cmd_info_id,
        ];
        // 延迟120秒 避免短时间执行多次
        $this->traitCommonCloud260($clearRepeatCmdParam, Cloud260Const::CLOUD_260_CMD_CLEAR_SPACE_TIMES);
        return true;
    }

    /**
     * 处理-导入雇员授权信息结果反馈.
     */
    public function setPassResultUpload($params) {
        $cmd_info_id = isset($params['cmd_info_id']) && $params['cmd_info_id'] ? $params['cmd_info_id'] : 0;
        fdump_api(['msg' => '初入-导入雇员授权信息结果反馈', 'cmd_info_id' => $cmd_info_id], '260Cloud/setPassResultUploadLog', 1);
        if (! $cmd_info_id) {
            return ['code'    => 1001, 'message' => '缺少必要参数'];
        }
        $whereUnique = ['id' => $cmd_info_id, 'status' => 0];
        $dbHouseVillageDeviceCmd = new HouseVillageDeviceCmd();
        $cmdInfo = $dbHouseVillageDeviceCmd->getOne($whereUnique);
        fdump_api(['msg' => '查询指令-导入雇员授权信息结果反馈', 'cmd_info_id' => $cmd_info_id, 'cmdInfo' => $cmdInfo&&!is_array($cmdInfo) ? $cmdInfo->toArray() : $cmdInfo], '260Cloud/setPassResultUploadLog', 1);
        if (isset($cmdInfo['cmd_order']) && $cmdInfo['cmd_order']) {
            $deviceInfo = $this->commonUpload($cmdInfo, $whereUnique);
            if (isset($deviceInfo['code']) && isset($deviceInfo['message'])) {
                return $deviceInfo;
            }
            fdump_api(['msg' => '设备信息-导入雇员授权信息结果反馈', 'cmd_info_id' => $cmd_info_id, 'deviceInfo' => $deviceInfo&&!is_array($deviceInfo) ? $deviceInfo->toArray() : $deviceInfo], '260Cloud/setPassResultUploadLog', 1);
            $device_id = $deviceInfo['device_id'];
            $cmd_order = json_decode($cmdInfo['cmd_order'],true);
            $msgId = isset($cmd_order['msgId']) && trim($cmd_order['msgId']) ? trim($cmd_order['msgId']) : '';
            $successNumber = isset($cmd_order['successNumber']) && intval($cmd_order['successNumber']) ? intval($cmd_order['successNumber']) : 0;
            $failNumber = isset($cmd_order['failNumber']) && intval($cmd_order['failNumber']) ? intval($cmd_order['failNumber']) : 0;
            $failEmployeeId = isset($cmd_order['failEmployeeId']) && $cmd_order['failEmployeeId'] ? $cmd_order['failEmployeeId'] : [];
            $noInPigcmsIds = [];
            $page = -1;
            $pageSize = $successNumber + $failNumber;
            $whereCmd = [
                ['type', '=', 'face'],
                ['device_type', '=', Cloud260Const::CLOUD_260_DEVICE_TYPE],
                ['cmd', '=', 'selectPassInfo'],
                ['cmd_direction', '=', Cloud260Const::CMD_DIRECTION0],
                ['status', '=', 1],
                ['device_id', '=', $device_id]
            ];
            if ($msgId) {
                $whereCmd[] = ['msg_id', '=', $msgId];
            } else {
                $whereCmd[] = ['order_time', '>', 0];
                $page = 1;
            }
            fdump_api(['msg' => '处理条件-导入雇员授权信息结果反馈', 'cmd_info_id' => $cmd_info_id, 'pageSize' => $pageSize, 'whereCmd' => $whereCmd], '260Cloud/setPassResultUploadLog', 1);
            $nowTime = time();
            $dbHouseVillageUserBind = new HouseVillageUserBind();
            $allUserIds = [];
            if (! empty($failEmployeeId) && is_array($failEmployeeId)) {
                fdump_api(['msg' => '失败处理-导入雇员授权信息结果反馈', 'cmd_info_id' => $cmd_info_id, 'failEmployeeId' => $failEmployeeId], '260Cloud/setPassResultUploadLog', 1);
                foreach ($failEmployeeId as $employ) {
                    $whereCmdEmploy = $whereCmd;
                    $employeeId = isset($employ['employeeId']) ? strval($employ['employeeId']) : 0;
                    $whereCmdEmploy[] = ['cmd_id', '=', $employeeId];
                    $errorCode = isset($employ['errorCode']) ? intval($employ['errorCode']) : -1;
                    $errorMsg = $this->handleMsg($errorCode, '', 0);
                    if ($employeeId) {
                        $allUserIds[] = $employeeId;
                        $noInPigcmsIds[] = $employeeId;
                        $dataCmd = [
                            'finish_time' => $nowTime, 'status' => 3, 'reason' => $errorMsg ?? '同步失败',
                        ];
                        fdump_api([
                            'msg' => '失败处理-导入雇员授权信息结果反馈', 'cmd_info_id' => $cmd_info_id,
                            'dataCmd' => $dataCmd, 'employ' => $employ, '$whereCmdEmploy' => $whereCmdEmploy, '$dataCmd' => $dataCmd
                        ], '260Cloud/setPassResultUploadLog', 1);
                        $dbHouseVillageDeviceCmd->updateThis($whereCmdEmploy, $dataCmd);
                        $whereUser = [
                            ['pigcms_id', '=', $employeeId]
                        ];
                        fdump_api([
                            'msg' => '失败处理-导入雇员授权信息结果反馈', 'cmd_info_id' => $cmd_info_id,
                            'whereUser' => $whereUser, 'save' => ['face_img_status' => 2, 'face_img_reason' => $errorMsg ?? '同步失败']
                        ], '260Cloud/setPassResultUploadLog', 1);
                        $dbHouseVillageUserBind->saveOne($whereUser, ['face_img_status' => 2, 'face_img_reason' => $errorMsg ?? '同步失败']);
                        $pageSize--;
                    } else {
                        fdump_api(['msg' => '导入雇员授权信息结果反馈处理失败', 'employ' => $employ, 'cmd_order' => $cmd_order], '260Cloud/errSetPassResultUpload_'.$cmd_info_id, 1);
                    }
                }
            }
            // 处理完成的
            if (! empty($noInPigcmsIds)) {
                $whereCmd[] = ['cmd_id', 'not in', $noInPigcmsIds];
            }
            if ($page === 1 && $pageSize > 0) {
                $field = 'id, device_id, device_sn, cmd_id';
                $order = 'last_time ASC, add_time ASC';
                $selectPassList = $dbHouseVillageDeviceCmd->getSome($whereCmd, $field, $order, 0, $pageSize);
            } else {
                $field = 'id, device_id, device_sn, cmd_id';
                $order = 'last_time ASC, add_time ASC';
                $selectPassList = $dbHouseVillageDeviceCmd->getSome($whereCmd, $field, $order, 0, 0);
            }
            fdump_api(['msg' => '设备信息-导入雇员授权信息结果反馈', 'cmd_info_id' => $cmd_info_id, 'selectPassList' => $selectPassList->toArray(), 'whereCmd' => $whereCmd], '260Cloud/setPassResultUploadLog', 1);
            if (! empty($selectPassList)) {
                $cmdData = [
                    'status' => 2, 'reason' => '', 'finish_time' => $nowTime,
                ];
                $userData = [
                    'face_img_status' => 1, 'face_img_reason' => ''
                ];
                $cmdSaveIds = [];
                $userIds = [];
                foreach ($selectPassList as $pass) {
                    $cmdSaveIds[] = $pass['id'];
                    $userIds[] = $pass['cmd_id'];
                    $allUserIds[] = $pass['cmd_id'];
                }
                fdump_api(['msg' => '记录结果-导入雇员授权信息结果反馈', 'cmd_info_id' => $cmd_info_id, 'cmdData' => $cmdData, 'userData' => $userData], '260Cloud/setPassResultUploadLog', 1);
                if (! empty($cmdSaveIds)) {
                    $dbHouseVillageDeviceCmd->updateThis([['id', 'in', $cmdSaveIds]], $cmdData);
                }
                if (! empty($userIds)) {
                    $dbHouseVillageUserBind->saveOne([['pigcms_id', 'in', $userIds]], $userData);
                }
                if (! empty($allUserIds)) {
                    // 是用队列移除重复人员信息下发的数据指令 注意保留最新的3个相同对应相同指令
                    $num = 1;
                    foreach ($allUserIds as $userCmdId) {
                        $clearRepeatCmdParam = [
                            'job_type' => 'clearRepeatCmd',
                            'reserveLastNum' => 3, // 保留最新相同指令数
                            'cmd_info_id' => $userCmdId,
                        ];
                        // 延迟120秒 避免短时间执行多次
                        $this->traitCommonCloud260($clearRepeatCmdParam, Cloud260Const::CLOUD_260_CMD_CLEAR_SPACE_TIMES * $num);
                        $num++;
                    }
                }
            }
            $data = [
                'status' => 2, 'reason' => '', 'last_time' => time(), 'finish_time' => time(),
            ];
            $dbHouseVillageDeviceCmd->updateThis($whereUnique, $data);
        } else {
            $data = [
                'status' => 3, 'reason' => '缺少内容', 'last_time' => time(), 'finish_time' => time(),
            ];
            $dbHouseVillageDeviceCmd->updateThis($whereUnique, $data);
        }
        // 是用队列移除重复的数据指令 注意保留最新的3个相同对应相同指令
        $clearRepeatCmdParam = [
            'job_type' => 'clearRepeatCmd',
            'reserveLastNum' => 3, // 保留最新相同指令数
            'cmd_info_id' => $cmd_info_id,
        ];
        // 延迟120秒 避免短时间执行多次
        $this->traitCommonCloud260($clearRepeatCmdParam, Cloud260Const::CLOUD_260_CMD_CLEAR_SPACE_TIMES);
        fdump_api(['msg' => '延迟120秒 避免短时间执行多次', 'clearRepeatCmdParam' => $clearRepeatCmdParam], '260Cloud/setPassResultUploadLog', 1);

        return true;
    }
    
    protected function commonUpload($cmdInfo, $whereUnique) {
        $dbHouseVillageDeviceCmd = new HouseVillageDeviceCmd();
        $dbHouseFaceDevice = new HouseFaceDevice();
        $whereFaceDevice = ['is_del' => 0, 'device_type' => Cloud260Const::CLOUD_260_DEVICE_TYPE];
        if (isset($cmdInfo['device_id']) && $cmdInfo['device_id']) {
            $whereFaceDevice['device_id'] = $cmdInfo['device_id'];
        }
        if (isset($cmdInfo['device_sn']) && $cmdInfo['device_sn']) {
            $whereFaceDevice['device_sn'] = $cmdInfo['device_sn'];
        }
        if (! isset($whereFaceDevice['device_id']) && ! isset($whereFaceDevice['device_sn'])) {
            $data = ['status' => 3, 'reason' => '缺少对应设备', 'last_time' => time(), 'finish_time' => time()];
            $dbHouseVillageDeviceCmd->updateThis($whereUnique, $data);
            return ['code'    => 1001, 'message' => '缺少必要参数'];
        }
        $deviceInfo = $dbHouseFaceDevice->getOne($whereFaceDevice);
        if (! isset($deviceInfo['device_id'])) {
            $data = ['status' => 3, 'reason' => '对应设备不存在', 'last_time' => time(), 'finish_time' => time()];
            $dbHouseVillageDeviceCmd->updateThis($whereUnique, $data);
            return ['code'    => 1001, 'message' => '缺少必要参数'];
        }
        return $deviceInfo;
    }
    
    public function clearRepeatCmdTask() {
        $dbHouseVillageDeviceCmd = new HouseVillageDeviceCmd();
        $list = $dbHouseVillageDeviceCmd->getGroupList('id');
        if ($list && ! is_array($list)) {
            $list = $list->toArray();
            $num = 1;
            foreach ($list as $cmdInfo) {
                // 是用队列移除重复的数据指令 注意保留最新的3个相同对应相同指令
                $clearRepeatCmdParam = [
                    'job_type' => 'clearRepeatCmd',
                    'reserveLastNum' => 3, // 保留最新相同指令数
                    'cmd_info_id' => $cmdInfo['id'],
                ];
                // 延迟120秒 避免短时间执行多次
                $time = Cloud260Const::CLOUD_260_CMD_CLEAR_SPACE_TIMES * $num;
                $this->traitCommonCloud260($clearRepeatCmdParam, $time);
                $num++;
            }
        }
        return $list;
    }
    
    public function clearRepeatCmd($params) {
        $reserveLastNum = isset($params['reserveLastNum']) && $params['reserveLastNum'] ? intval($params['reserveLastNum']) : 3;
        $cmd_info_id = isset($params['cmd_info_id']) && $params['cmd_info_id'] ? intval($params['cmd_info_id']) : 3;
        fdump_api(['msg' => '删除重复指令', 'params' => $params], '260Cloud/clearRepeatCmdLog', 1);
        if (! $cmd_info_id) {
            return false;
        }
        $whereUnique = ['id' => $cmd_info_id];
        $dbHouseVillageDeviceCmd = new HouseVillageDeviceCmd();
        $cmdInfo = $dbHouseVillageDeviceCmd->getOne($whereUnique, 'id, type, device_type, cmd, cmd_direction, cmd_id, device_id, device_sn');
        fdump_api(['msg' => '删除重复指令-信息', 'cmdInfo' => $cmdInfo && !is_array($cmdInfo) ? $cmdInfo->toArray() : $cmdInfo], '260Cloud/clearRepeatCmdLog', 1);
        if (! $cmdInfo || ! isset($cmdInfo['id'])) {
            return false;
        }
        $whereDelete = [];
        $whereDelete[] = ['type', '=', $cmdInfo['type']];
        $whereDelete[] = ['device_type', '=', $cmdInfo['device_type']];
        $whereDelete[] = ['cmd', '=', $cmdInfo['cmd']];
        $whereDelete[] = ['cmd_direction', '=', $cmdInfo['cmd_direction']];
        $whereDelete[] = ['cmd_id', '=', $cmdInfo['cmd_id'] ?? 0];
        $whereDelete[] = ['device_id', '=', $cmdInfo['device_id'] ?? 0];
        $whereDelete[] = ['device_sn', '=', $cmdInfo['device_sn'] ?? ''];
        if ($reserveLastNum > 0) {
            $cmdIds = $dbHouseVillageDeviceCmd->getColumn($whereDelete, 'id', '', 'last_time DESC, add_time DESC,id DESC', $reserveLastNum);
            fdump_api(['msg' => '删除重复指令-无需删除的', 'whereDelete' => $whereDelete, 'cmdIds' => $cmdIds], '260Cloud/clearRepeatCmdLog', 1);
            if (! empty($cmdIds)) {
                $whereDelete[] = ['id', 'not in', $cmdIds];
            }
        }
        fdump_api(['msg' => '删除多余指令', 'params' => $params, 'whereDelete' => $whereDelete], '260Cloud/clearRepeatCmdLog', 1);
        $dbHouseVillageDeviceCmd->deleteInfo($whereDelete);
        return true;
    }
    
    /**
     * 返回错误处理.
     */
    public function handleMsg($success, $msg, $isBase = 1) {
        $msgArr = [
            'OK' => '成功',
            'PASS' => '成功',
            'FAIL' => '失败',
            'USER IS EXIST' => '管理员名称已存在',
            'IS LASTEST VERSION' => '已是最新版本',
            'IS NOT VALIDATE FIRMWARE' => '不是有效的固件',
            'IS NOT ENOUGH SPACE' => '没有足够的存储空间',
            'IS_NOT_LOGIN' => '未登录',
            'IS_NOT_ENOUGH_MEMORY' => '内存不足',
            'PWD_ERROR' => '密码错误',
            'SSID_TOO_LONG' => '热点的 SSID 太长(超过 31 个字符)',
            'PSK_TOO_LONG' => '热点的密码太长(超过 8 个字符)',
            'OPEN_ DOOR_TOO_OFTEN' => '开门太频繁',
            'PERSON_REG_OK' => '导入成功',
            'PERSON_REG_REFUSE' => '人脸被拒绝',
            'PERSON_INFO_DATA_ERROR' => '人员信息数据错误',
            'PERSON_INFO_NO_DETECT_FACE' => '无法检测脸部',
            'PERSON_INFO_FACE_FEATURE_FAIL' => '人脸特征值提取失败',
            'PERSON_INFO_MULTIPLE_FACE' => '检测到多张脸',
            'PERSON_INFO_FACE_SIZE_ERROR' => '图像尺寸不符合要求',
            'PERSON_INFO_IMAGE_DECODE_FAIL' => '图片解码出错',
            'PERSON_INFO_PIC_COPY_ERROR' => '图片拷贝失败',
            'PERSON_INFO_PIC_NO_HAVE' => '无图片',
            'PERSON_INFO_INSERT_DB_ERROR' => '人员信息写入数据库失败',
            'PERSON_INFO_FACE_QUALITY_TOO_LOW' => '图像画质过低',
            'PERSON_INFO_FACE_SIMILARITY_TOO_HIGH' => '人脸相似度太高',
            'PERSON_INFO_MORE_THAN_SINGLE_REG_LIMIT' => '超过单次注册上限',
            'PERSON_INFO_IMAGE_FORMAT_ERROR' => '图像格式不符合要求',
            'PERSON_INFO_INTERNAL_ERROR' => '应用程序内部错误',
            'PERSON_REGISTRATION_ERROR' => '人员重复注册',
            'PERSON_MIS_RECOGNIZED' => '人脸被人为是别人',
            'PERSON_REG_COMPARE_RESULT_OK' => '人脸注册校验成功',
            'PERSON_REG_COMPARE_RESULT_FAIL' => '人脸注册校验失败',
            'EMPLOYEE_JSON_STRUCT_ERROR' => 'Json 数据解析出错',
        ];
        if ($isBase === 1) {
            $successArr = [
                0 => '成功',
                -1 => '失败',
                1 => '管理员名称已存在',
                2 => '人员信息错误',
                3 => '无法检测到人脸',
                4 => '提取特征值失败',
                5 => '检测到多个人脸',
                6 => '人脸大小不符合要求',
                7 => '图片解码出错',
                8 => '图片拷贝失败',
                9 => '无图片',
                10 => '数据库注册失败',
                11 => '人脸质量过低',
                12 => '人脸相似度过高',
                13 => '超过单次注册上限',
                14 => '图片格式不符合要求',
                15 => '程序内部错误',
                16 => '人员重复注册',
                17 => '误识别',
                18 => '已是最新版本',
                19 => '不是有效的固件',
                20 => '没有足够的存储空间',
                21 => '未登录',
                22 => '内存不足',
                23 => '密码错误',
                24 => '热点的 SSID 太长(超过 31 个字符)',
                25 => '热点的密码太长(超过 8 个字符)',
                26 => '开门太频繁',
            ];
        } else {
            $successArr = [
                0 => '导入成功',
                1 => '人脸被拒绝',
                2 => '人员信息数据错误',
                3 => '无法检测脸部',
                4 => '人脸特征值提取失败',
                5 => '检测到多张脸',
                6 => '图像尺寸不符合要求',
                7 => '图像解码失败',
                8 => '图像拷贝出错',
                9 => '没有图像',
                10 => '人员信息写入数据库失败',
                11 => '图像画质过低',
                12 => '人脸相似度太高',
                13 => '超过一个注册限制',
                14 => '图像格式不符合要求',
                15 => '应用程序内部错误',
                16 => '重复注册错误',
                17 => '人脸被人为是别人',
                18 => '人脸注册校验成功',
                19 => '人脸注册校验失败',
                20 => 'Json 数据解析出错',
            ];
        }
        $message = isset($msgArr[$msg]) ? $msgArr[$msg] : '';
        if (! $message) {
            $success = intval($success);
            $message = isset($successArr[$success]) ? $successArr[$success] : '';
        }
        return $message;
    }

    /**
     * 二维码开门.
     */
    public function getUserOpenCode($params) {
        $getFaceCloud260Judge = (new ConfigCustomizationService())->getFaceCloud260Judge();
        if (! $getFaceCloud260Judge) {
            throw new \think\Exception("不支持此功能");
        }
        $uid = isset($params['uid']) && $params['uid'] ? intval($params['uid']) : 0;
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $pigcms_id = isset($params['pigcms_id']) ? intval($params['pigcms_id']) : 0;
        $dbHouseVillageUserBind = (new HouseVillageUserBind());
        $whereUserBind = [
            ['pigcms_id', '=', $pigcms_id]
        ];
        $userBind = $dbHouseVillageUserBind->getOne($whereUserBind, 'pigcms_id, status, village_id, uid, bind_third_id');
        $userBind = $userBind ? $userBind->toArray() : [];
        if (empty($userBind)) {
            throw new \think\Exception("对应住户不存在");
        }
        if (intval($userBind['status']) !== 1) {
            throw new \think\Exception("当前住户状态不支持使用此功能");
        }
        if ($uid && intval($userBind['uid']) !== $uid) {
            throw new \think\Exception("住户与当前登录人员身份不符");
        }
        if ($village_id && intval($userBind['village_id']) !== $village_id) {
            throw new \think\Exception("住户小区与当前小区不符");
        }
        $dbFaceUserBindDevice = new FaceUserBindDevice();
        $whereFaceUserBindDevice = [];
        $whereFaceUserBindDevice[] = ['bind_type', 'in', [Cloud260Const::CLOUD_260_FACE_USER_BIND_DEVICE_BIND_TYPE, DeviceConst::GARBAGE_COLLECTION_BIND_TYPE]];
        $whereFaceUserBindDevice[] = ['bind_id', '=', $pigcms_id];
        $whereFaceUserBindDevice[] = ['device_type', '=', Cloud260Const::CLOUD_260_DEVICE_TYPE];
        $whereFaceUserBindDevice[] = ['code', '<>', ''];
        $fieldFaceUserBindDevice = 'id, bind_type, bind_id, device_type, open_key, open_key_time, code, person_id, person_text';
        $infoFaceUserBindDevice = $dbFaceUserBindDevice->getOne($whereFaceUserBindDevice, $fieldFaceUserBindDevice);
        $infoFaceUserBindDevice = ($infoFaceUserBindDevice && ! is_array($infoFaceUserBindDevice)) ? $infoFaceUserBindDevice->toArray() : [];

        if (empty($infoFaceUserBindDevice)) {
            $whereFaceUserBindDevice = [];
            $whereFaceUserBindDevice[] = ['bind_type', '=', DeviceConst::GARBAGE_COLLECTION_BIND_TYPE];
            $whereFaceUserBindDevice[] = ['personID', '=', $uid];
            $whereFaceUserBindDevice[] = ['code', '<>', ''];
            $fieldFaceUserBindDevice = 'id, bind_type, bind_id, device_type, open_key, open_key_time, code, person_id, person_text';
            $infoFaceUserBindDevice = $dbFaceUserBindDevice->getOne($whereFaceUserBindDevice, $fieldFaceUserBindDevice);
            $infoFaceUserBindDevice = ($infoFaceUserBindDevice && ! is_array($infoFaceUserBindDevice)) ? $infoFaceUserBindDevice->toArray() : [];
        }
        if (empty($infoFaceUserBindDevice)) {
            //判断是否是垃圾设备绑定小区的用户，是的话需要生成二维码
            $infoFaceUserBindDevice = (new GarbageCollectionMachineService())->createQrCode($pigcms_id);
        }
        
        $employeeQRCode = isset($infoFaceUserBindDevice['code']) && $infoFaceUserBindDevice['code'] ? $infoFaceUserBindDevice['code'] : '';
        if (empty($infoFaceUserBindDevice) || ! $employeeQRCode) {
            throw new \think\Exception("请前往人脸识别门禁处重新保存同步设备");
        }
        $person_text = isset($infoFaceUserBindDevice['person_text']) ? json_decode($infoFaceUserBindDevice['person_text'], true) : [];
        $codeUrl = cfg('site_url') . '/index.php?c=Recognition&a=get_own_qrcode&qrCon=' . $employeeQRCode;
        $effectTime = isset($person_text['effectTime']) ? $person_text['effectTime'] : '';
        $expireTime = isset($person_text['expireTime']) ? $person_text['expireTime'] : '';
        $openTimes = isset($person_text['openTimes']) ? intval($person_text['openTimes']) : 0;
        $surplusOpenTimes = isset($person_text['surplusOpenTimes']) ? intval($person_text['surplusOpenTimes']) : 0;
        $open_key_time = isset($infoFaceUserBindDevice['open_key_time']) ? intval($infoFaceUserBindDevice['open_key_time']) : 0;
        $nowTime = time();
        $effectTime && $effectTimeStamp = strtotime($effectTime);
        $expireTime && $expireTimeStamp = strtotime($expireTime);
        $effectTimeTip = "";
        $expireTimeTip = "";
        if (isset($effectTimeStamp) && $effectTimeStamp && $effectTimeStamp > $nowTime) {
            $effectTimeTip = "起效时间：" . $effectTime;
        }
        $open_key_time_delay = $open_key_time > 0 ? $nowTime + Cloud260Const::CLOUD_260_QRCODE_REFRESH_INTERVAL : 0;
        if ($open_key_time > 0 && $open_key_time <= ($nowTime + 10) && isset($expireTimeStamp) && $expireTimeStamp > $open_key_time_delay) {
            // 有效期过了 +2个小时
            $open_key_time = $open_key_time_delay;
            $userParams = [
                'uid' => $uid,
                'pigcms_id' => $pigcms_id
            ];
            $this->userSynDevice($userParams);
        }
        if (isset($expireTimeStamp) && $expireTimeStamp && $expireTimeStamp > $nowTime) {
            if ($open_key_time > 0 && $open_key_time < $expireTimeStamp) {
                $open_key_time_txt = date('Y-m-d H:i:s', $open_key_time);
                $expireTimeTip = "失效时间：" . $open_key_time_txt;
            } else {
                $expireTimeTip = "失效时间：" . $expireTime;
            }
        }
        if (isset($expireTimeStamp) && $expireTimeStamp && $expireTimeStamp <= $nowTime) {
            $codeUrl = cfg('site_url').'/static/images/house/visitor/QRCodeinvalid.png';// 默认失效图片
        }
        if ($openTimes > 0 && $surplusOpenTimes <= 0) {
            $codeUrl = cfg('site_url').'/static/images/house/visitor/QRCodeinvalid.png';// 默认失效图片
        }
        $openTimesTip = "";
        if ($openTimes > 0) {
            $openTimesTip = "剩余开门次数：".$surplusOpenTimes;
        }
        return [
            'codeUrl' => $codeUrl,
            'effectTimeTip' => $effectTimeTip,
            'expireTimeTip' => $expireTimeTip,
            'openTimesTip' => $openTimesTip,
            'tip' => '点击图片可刷新',
            'title' => '请将二维码对准设备识别位置',
        ];
    }

    /**
     * 对应单元获取人员身份所有楼层
     */
    public function getFloorUserAllLayer($village_id, $floor_id, $uid = 0, $phone = '', $layer_id = 0, $sdk_version = Cloud260Const::DEVICE_260_CLOUD_VERSION) {
        if (! $floor_id || !$village_id) {
            return false;
        }
        if (! $uid && ! $phone) {
            return false;
        }
        if ($sdk_version == 'v2.0-20230515') {
            $dbHouseVillageLayer = new HouseVillageLayer();
            $layerInfo = $dbHouseVillageLayer->getOne(['id' => $layer_id], 'id, layer_name, layer_number');
            $floor_number = '';
            if (isset($layerInfo['id']) && $layerInfo['id']) {
                $floor_number = isset($layerInfo['layer_number']) ? trim($layerInfo['layer_number']) : '';
            }
            return $floor_number;
        }
        // 单元下对应人员身份的所有楼层
        $whereColumn = [];
        if ($uid) {
            $whereColumn[] = ['uid', '=', $uid];
        }
        if ($phone) {
            $whereColumn[] = ['phone', '=', $phone];
        }
        $whereColumn[] = ['village_id', '=', $village_id];
        $whereColumn[] = ['floor_id', '=', $floor_id];
        $whereColumn[] = ['status', '=', 1];
        $layerIds = (new HouseVillageUserBind())->getColumn($whereColumn, 'layer_id');
        fdump_api(['msg' => '单元下对应人员身份的所有楼层', 'whereColumn' => $whereColumn, 'layerIds' => $layerIds], '260Cloud/getFloorUserAllLayer', 1);
        is_array($layerIds) && $layerIds = array_values($layerIds);
        $dbHouseVillageLayer = new HouseVillageLayer();
        $whereLayerColumn = [];
        $whereLayerColumn[] = ['id', 'in', $layerIds];
        $layerList = $dbHouseVillageLayer->getOneColumn($whereLayerColumn, 'id, layer_name, layer_number');
        $floor_number_arr = [];
        foreach ($layerList as $layerInfo) {
            $floor_number = isset($layerInfo['layer_number']) ? trim($layerInfo['layer_number']) : '';
            $floor_number && $floor_number_arr[] = strval($floor_number);
        }
        fdump_api(['msg' => '0反馈', 'whereColumn' => $whereColumn, 'floor_number_arr' => $floor_number_arr], '260Cloud/getFloorUserAllLayer', 1);
        if (empty($floor_number_arr) && $layer_id) {
            $dbHouseVillageLayer = new HouseVillageLayer();
            $layerInfo = $dbHouseVillageLayer->getOne(['id' => $layer_id], 'id, layer_name, layer_number');
            if (isset($layerInfo['id']) && $layerInfo['id']) {
                $floor_number = isset($layerInfo['layer_number']) ? trim($layerInfo['layer_number']) : '';
                $floor_number && $floor_number_arr[] = strval($floor_number);
            }
        }
        fdump_api(['msg' => '1反馈', 'whereColumn' => $whereColumn, 'floor_number_arr' => $floor_number_arr], '260Cloud/getFloorUserAllLayer', 1);
        return $floor_number_arr;
    }
}