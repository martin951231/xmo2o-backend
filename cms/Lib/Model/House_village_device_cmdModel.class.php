<?php
/**
 * This file is part of Kuaijing Bailing.
 *
 * @link     https://www.kuaijingai.com
 * @document https://help.kuaijingai.com
 * @contact  www.kuaijingai.com 7*12 9:00-21:00
 */
class House_village_device_cmdModel extends Model
{
    /**
     * @var int 主动触发云端轮训时间（以秒为单位）
     */
    public const PARAMETER_POLLING = 3;


    /**
     * @var int 指令方向 0-反馈设备
     */
    public const CMD_DIRECTION0 = 0;

    /**
     * @var int 指令方向 1-反馈平台
     */
    public const CMD_DIRECTION1 = 1;
    
    public const DEVICE_260_CLOUD_VERSION = 'v2.0-20230515';
    
    /**
     * 设备相关设置同步指令.
     */
    public function addOrUpdateDeviceCmd($data) {
        $device_id = isset($data['device_id']) && $data['device_id'] ? intval($data['device_id']) : 0;
        $device_sn = isset($data['device_sn']) && $data['device_sn'] ? trim($data['device_sn']) : '';
        $device_type = isset($data['device_type']) && $data['device_type'] ? intval($data['device_type']) : 30;
        if (! $device_id && ! $device_sn) {
            return false;
        }
        $layer_id = isset($data['layer_id']) && $data['layer_id'] ? intval($data['layer_id']) : 0;
        if ($layer_id) {
            $layerInfo = M('House_village_layer')->where(['id' => $layer_id])->field('id, layer_name, layer_number')->find();
        }
        // 260云 将设备相关信息加入指令中等候
        $parameterFaceThreshold = isset($data['device_score']) && $data['device_score'] ? intval($data['device_score']) : 80;
        $parameterFaceThreshold = $parameterFaceThreshold / 100;
        $parameterFaceThreshold = round_number($parameterFaceThreshold, 2);
        $cmd = 'selectParameterInfo';
        $parameterInfo = [
            'parameterVolume' => 80, // 音量大小（范围：0-100）
            'parameterPreview' => isset($data['device_alive']) && intval($data['device_alive']) === 0 ? 0 : 1, // 1：表示打开活检预览 0：表示关闭活检预览
            'parameterPolling' => self::PARAMETER_POLLING, // 主动触发云端轮训时间（以秒为单位）
            'parameterFaceThreshold' => $parameterFaceThreshold, // 人脸比对阈值
            'parameterSystemTime' => time(), // 同步系统时间（时间戳，比如：1584427174 对应是 2020-03-17 14:39:34）
        ];
        $parameterLoginName = isset($data['thirdLoginName']) && $data['thirdLoginName'] ? $data['thirdLoginName'] : '';
        $parameterLoginPassword = isset($data['parameterLoginPassword']) && $data['parameterLoginPassword'] ? $data['parameterLoginPassword'] : '';
        if ($parameterLoginName && $parameterLoginPassword) {
            $parameterInfo['parameterLoginName'] = $parameterLoginName;
            $parameterInfo['parameterLoginPassword'] = $parameterLoginPassword;
        }
        if (isset($data['device_direction'])) {
            // 出入类型 0:入闸 1：出闸
            $parameterInfo['parameterAccessType'] = intval($data['device_direction']) === 0 ? 0 : 1;
        }
        $parameterInfo['parameterRecgEnable'] = 1; // 人脸识别 0:关 1:开
        if (isset($data['device_name']) && $data['device_name']) {
            $parameterProjectName = isset($data['device_name']) ? trim($data['device_name']) : '';
            $parameterProjectName && $parameterInfo['parameterProjectName'] = $parameterProjectName;
        }
        if (isset($layerInfo['id']) && $layerInfo['id']) {
            $parameterFloorNumber = isset($layerInfo['layer_number']) ? intval($layerInfo['layer_number']) : '';
            ! $parameterFloorNumber && $parameterFloorNumber = isset($layerInfo['layer_name']) ? trim($layerInfo['layer_name']) : '';
            $parameterFloorNumber && $parameterInfo['parameterFloorNumber'] = $parameterFloorNumber;
        }
        // 测温模式开关。0：非测温模式 1：测温模
        $parameterInfo['parameterTempSwitch'] = 0;
        // 固定给予设备版本进行升级匹配
//        $parameterInfo['parameterFirmwareMsg'] = 'v2.0-20230505';
//        $parameterInfo['parameterFirmwareMd5'] = '90a926591df617d78d3ddd6576bc2f2a';
//        $parameterInfo['parameterFirmwarePath'] = 'https://netdisk.260cloud.com/api/alien/download/6f1bf0b8-15b7-4994-44d8-23174e08b919/EUPGRADE-90a926591df617d78d3ddd6576bc2f2a.efw';
        // https://netdisk.260cloud.com/api/alien/download/a3801e6e-3505-43c4-7e72-605937e807b9/EUPGRADE-5a36ad33b32ce0930a25ea132bc81eaf.efw
        $parameterInfo['parameterFirmwareMsg'] = self::DEVICE_260_CLOUD_VERSION;
        $parameterInfo['parameterFirmwareMd5'] = '5a36ad33b32ce0930a25ea132bc81eaf';
        $parameterInfo['parameterFirmwarePath'] = 'https://netdisk.260cloud.com/api/alien/download/a3801e6e-3505-43c4-7e72-605937e807b9/EUPGRADE-5a36ad33b32ce0930a25ea132bc81eaf.efw';
        
        $cmd_order = $parameterInfo;
        $cmd_data = [
            'type' => 'face',
            'device_type' => $device_type,
            'cmd' => $cmd,
            'cmd_direction' => self::CMD_DIRECTION0,
            'device_sn' => $device_sn,
        ];
        $unique_cmd = md5(json_encode($cmd_data,JSON_UNESCAPED_UNICODE));
        $cmd_data['device_id'] = $device_id;
        $cmd_data['cmd_order'] = json_encode($cmd_order,JSON_UNESCAPED_UNICODE);
        $cmd_data['unique_cmd'] = $unique_cmd;
        $cmd_data['status'] = 0;
        $cmd_data['cmd_id'] = $device_id;
        $whereUnique = [
            'unique_cmd' => $unique_cmd,
            'status' => 0,
        ];
        $id = $this->where($whereUnique)->getField('id');
        if ($id) {
            $cmd_data['last_time'] = time();
            $this->where($whereUnique)->save($cmd_data);
        } else {
            $cmd_data['add_time'] = time();
            $this->add($cmd_data);
        }
        return true;
    }

    /**
     * 设备重启/远程开门/恢复出厂设置.
     */
    public function addOrUpdateRestartCmd($data) {
        $device_id = isset($data['device_id']) && $data['device_id'] ? intval($data['device_id']) : 0;
        $device_sn = isset($data['device_sn']) && $data['device_sn'] ? trim($data['device_sn']) : '';
        $device_type = isset($data['device_type']) && $data['device_type'] ? intval($data['device_type']) : 30;
        $employeeId = isset($data['employeeId']) && $data['employeeId'] ? intval($data['employeeId']) : 0;
        if (! $device_id && ! $device_sn) {
            return ['msg' => '开门失败', 'err' => 1];
        }
        // 260云 将设备相关信息加入指令中等候
        $cmd = 'selectRestart';
        $cmd_data = [
            'type' => 'face',
            'device_type' => $device_type,
            'cmd' => $cmd,
            'cmd_direction' => self::CMD_DIRECTION0,
            'device_sn' => $device_sn,
        ];
        $unique_cmd_arr = $cmd_data;
        $parameterInfo = [];
        if ($employeeId > 0) {
            $whereDevice = [
                'is_del' => 0,
                'device_id' => $device_id,
            ];
            $deviceInfo = M('House_face_device')->where($whereDevice)->find();
            $deviceFloorId = isset($deviceInfo['floor_id']) ? $deviceInfo['floor_id'] : 0;
            if ($deviceFloorId !== -1 && $deviceFloorId !== 0 && $deviceFloorId !== '0' && $deviceFloorId) {
                $deviceFloorIds = explode(',', strval($deviceFloorId));
            } else {
                $deviceFloorIds = [];
            }
            $parameterInfo['employeeId'] = strval($employeeId);
            $unique_cmd_arr['employeeId'] = $parameterInfo['employeeId'];
            $user = M('House_village_user_bind')->where(['pigcms_id' => $parameterInfo['employeeId']])->field('pigcms_id, vacancy_id, village_id, uid, phone, single_id, floor_id, layer_id')->find();

            if (isset($deviceInfo['thirdChnNum']) && $deviceInfo['thirdChnNum'] && ! empty($deviceFloorIds) && intval($user['vacancy_id']) > 0) {
                $whereRoom = [];
                $whereRoom['pigcms_id'] = $user['vacancy_id'];
                $whereRoom['room'] = ['like', '%' . $deviceInfo['thirdChnNum']];
                $roomInfo = M('House_village_user_vacancy')->where($whereRoom)->find();
                if (! isset($roomInfo['pigcms_id']) || ! $roomInfo['pigcms_id']) {
                    return ['msg' => '没有开门权限', 'err' => 2];
                }
            }
            
            $single_id = isset($user['single_id']) && $user['single_id'] ? intval($user['single_id']) : 0;
            if ($single_id) {
                $singleInfo = M('House_village_single')->where(['id' => $single_id])->field('id, single_name, single_number')->find();
            }
            $floor_id = isset($user['floor_id']) && $user['floor_id'] ? $user['floor_id'] : 0;
            if ($floor_id) {
                $floorInfo = M('House_village_floor')->where(['floor_id' => $floor_id])->field('floor_id, floor_name, floor_number')->find();
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
            
            if ($floor_id) {
                $uid = isset($user['uid']) ? $user['uid'] : 0;
                $phone = isset($user['phone']) ? $user['phone'] : '';
                if ($uid > 0) {
                    $phone = '';
                }
                // 楼层
                $layer_id = isset($user['layer_id']) && $user['layer_id'] ? intval($user['layer_id']) : 0;
                fdump_api(['msg' => '单元下对应人员身份的所有楼层',$user['village_id'], $floor_id, $uid, $phone, $layer_id, 'user' => $user], '260Cloud/1getFloorUserAllLayer', 1);
                $floor_number_arr = $this->getFloorUserAllLayer($user['village_id'], $floor_id, $uid, $phone, $layer_id);
                if (!empty($floor_number_arr)) {
                    $parameterInfo['floor_number'] = $floor_number_arr;
                }
            }
        }
        // restart int 否 0:不重启，1:重启
        if (isset($data['restart'])) {
            $parameterInfo['restart'] = intval($data['restart']) === 1 ? 1 : 0;
            $unique_cmd_arr['restart'] = $parameterInfo['restart'];
        }
        // severUpdate int 否 0:不变更服务器路径，1:更新服务器路径信息
        // severPath String 否 服务器路径
        if (isset($data['severUpdate']) && isset($data['severPath'])) {
            $parameterInfo['severUpdate'] = intval($data['severUpdate']) === 1 ? 1 : 0;
            $parameterInfo['severPath'] = trim($data['severPath']);
            $unique_cmd_arr['recover'] = $parameterInfo['severPath'];
        }
        // recover int 否 0:正常，1：恢复出厂设置
        if (isset($data['recover'])) {
            $parameterInfo['recover'] = intval($data['recover']) === 1 ? 1 : 0;
            $unique_cmd_arr['recover'] = $parameterInfo['recover'];
        }
        // opendoor Int 否 0：不处理，1：打开继电器
        if (isset($data['opendoor'])) {
            $parameterInfo['opendoor'] = intval($data['opendoor']) === 1 ? 1 : 0;
            $unique_cmd_arr['opendoor'] = $parameterInfo['opendoor'];
        }
        $cmd_order = $parameterInfo;
        $unique_cmd = md5(json_encode($unique_cmd_arr,JSON_UNESCAPED_UNICODE));
        $cmd_data['device_id'] = $device_id;
        $cmd_data['cmd_order'] = json_encode($cmd_order,JSON_UNESCAPED_UNICODE);
        $cmd_data['unique_cmd'] = $unique_cmd;
        $cmd_data['status'] = 0;
        $cmd_data['cmd_id'] = $device_id;
        $whereUnique = [
            'unique_cmd' => $unique_cmd,
            'status' => 0,
        ];
        $id = $this->where($whereUnique)->getField('id');
        if ($id) {
            $cmd_data['last_time'] = time();
            $this->where($whereUnique)->save($cmd_data);
        } else {
            $cmd_data['add_time'] = time();
            $this->add($cmd_data);
        }
        return ['msg' => '开门成功', 'err' => 0];
    }

    /**
     * 删除设备上单个人员数据或者全员删除.
     */
    public function addOrUpdateDeleteCmd($data) {
        $device_id = isset($data['device_id']) && $data['device_id'] ? intval($data['device_id']) : 0;
        $device_sn = isset($data['device_sn']) && $data['device_sn'] ? trim($data['device_sn']) : '';
        $device_type = isset($data['device_type']) && $data['device_type'] ? intval($data['device_type']) : 30;
        // 1：清空所有雇员信息 0：按指定 id 删除
        $empty = isset($data['empty']) && $data['empty'] ? intval($data['empty']) : 0;
        $pigcms_id = isset($data['pigcms_id']) && $data['pigcms_id'] ? intval($data['pigcms_id']) : 0;
        if (! $device_id && ! $device_sn) {
            return false;
        }
        if ($empty !== 1 && !$pigcms_id) {
            return false;
        }
        // 260云 将设备相关信息加入指令中等候
        if ($empty === 1) {
            $cmd = 'selectDeleteAllInfo';
            $cmd_order = [];
        } else {
            $cmd = 'selectDeleteInfo';
            $cmd_order = [
                'employeeId' => $pigcms_id
            ];
        }
        $cmd_data = [
            'type' => 'face',
            'device_type' => $device_type,
            'cmd' => $cmd,
            'cmd_direction' => self::CMD_DIRECTION0,
            'device_sn' => $device_sn,
        ];
        $unique_cmd_data = $cmd_data;
        if ($empty != 1) {
            $unique_cmd_data['employeeId'] = strval($pigcms_id);
        }
        $unique_cmd = md5(json_encode($unique_cmd_data,JSON_UNESCAPED_UNICODE));
        $cmd_data['device_id'] = $device_id;
        $cmd_data['cmd_order'] = json_encode($cmd_order,JSON_UNESCAPED_UNICODE);
        $cmd_data['unique_cmd'] = $unique_cmd;
        $cmd_data['status'] = 0;
        $cmd_data['cmd_id'] = $device_id;
        $whereUnique = [
            'unique_cmd' => $unique_cmd,
            'status' => 0,
        ];
        $id = $this->where($whereUnique)->getField('id');
        if ($id) {
            $cmd_data['last_time'] = time();
            $this->where($whereUnique)->save($cmd_data);
        } else {
            $cmd_data['add_time'] = time();
            $this->add($cmd_data);
        }
        return true;
    }


    /**
     * 对应单元获取人员身份所有楼层
     */
    public function getFloorUserAllLayer($village_id, $floor_id, $uid = 0, $phone = '', $layer_id = 0) {
        if (! $floor_id || !$village_id) {
            return false;
        }
        if (! $uid && ! $phone) {
            return false;
        }
        if (self::DEVICE_260_CLOUD_VERSION == 'v2.0-20230515') {
            $layerInfo = M('House_village_layer')->where(['id' => $layer_id])->field( 'id, layer_name, layer_number')->find();
            $floor_number = '';
            if (isset($layerInfo['id']) && $layerInfo['id']) {
                $floor_number = isset($layerInfo['layer_number']) ? trim($layerInfo['layer_number']) : '';
            }
            return $floor_number;
        }
        // 单元下对应人员身份的所有楼层
        $whereColumn = [];
        if ($uid) {
            $whereColumn['uid'] = $uid;
        }
        if ($phone) {
            $whereColumn['phone'] = $phone;
        }
        $whereColumn['village_id'] = $village_id;
        $whereColumn['floor_id'] = $floor_id;
        $whereColumn['status'] = 1;
        $layerIds = M('House_village_user_bind')->where($whereColumn)->getField('layer_id', true);
        $whereLayerColumn = [];
        $whereLayerColumn['id'] = ['in', $layerIds];
        $layerList = M('House_village_layer')->where($whereLayerColumn)->field( 'id, layer_name, layer_number')->select();
        $floor_number_arr = [];
        foreach ($layerList as $layerInfo) {
            $floor_number = isset($layerInfo['layer_number']) ? trim($layerInfo['layer_number']) : '';
            $floor_number && $floor_number_arr[] = strval($floor_number);
        }
        fdump_api(['msg' => '0反馈', 'whereColumn' => $whereColumn, 'floor_number_arr' => $floor_number_arr], '260Cloud/1getFloorUserAllLayer', 1);
        if (empty($floor_number_arr) && $layer_id) {
            $layerInfo = M('House_village_layer')->where(['id' => $layer_id])->field( 'id, layer_name, layer_number')->find();
            if (isset($layerInfo['id']) && $layerInfo['id']) {
                $floor_number = isset($layerInfo['layer_number']) ? trim($layerInfo['layer_number']) : '';
                $floor_number && $floor_number_arr[] = strval($floor_number);
            }
        }
        fdump_api(['msg' => '1反馈', 'whereColumn' => $whereColumn, 'floor_number_arr' => $floor_number_arr], '260Cloud/1getFloorUserAllLayer', 1);
        return $floor_number_arr;
    }
    
    public function userSyn($uid, $pigcms_id) {
        $models = '\app\community\model\service\Device\HouseVillageDeviceCmdService/userSynDevice';
        $param = [
            'params' => [
                'uid' => $uid,
                'pigcms_id' => $pigcms_id
            ]
        ];
        invoke_v20_service($models,$param);
        return true;
    }
    
    public function userGarbageSyn($uid, $pigcms_id)
    {
        $models = '\app\community\model\service\Device\GarbageCollectionMachineService/userSynDevice';
        $param = [
            'params' => [
                'uid' => $uid,
                'pigcms_id' => $pigcms_id
            ]
        ];
        invoke_v20_service($models,$param);
        return true;
    }
}