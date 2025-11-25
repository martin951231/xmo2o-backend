<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      机器人-CROSS-指令接口
 * @documentLink https://cross-fit86.harix.iamidata.com/crss-docs/#/
 */

namespace app\thirdRobot\model\service;

use app\community\model\db\Device\DeviceBindUser;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\User;
use app\consts\DeviceConst;
use app\consts\RobotConst;
use app\thirdRobot\model\db\ThirdRobotCallRecord;
use app\thirdRobot\model\db\ThirdRobotDevice;
use app\thirdRobot\model\db\ThirdRobotHistoryTask;
use app\thirdRobot\model\db\ThirdRobotMap;
use app\thirdRobot\model\db\ThirdRobotMapPoints;
use app\traits\house\DeviceTraits;
use app\traits\third\RobotTraits;

class ThirdRobotDeviceCmdService
{
    use RobotTraits;
    use DeviceTraits;

    /**
     * 一键同步触发.
     */
    public function robotOneClickSync($robotCode)
    {
        if (!$robotCode) {
            return false;
        }
        $db_third_robot_device = new ThirdRobotDevice();
        $whereRobot = [];
        $whereRobot[] = ['robot_code', '=', $robotCode];
        $whereRobot[] = ['del_time', '=', 0];
        $robotInfo = $db_third_robot_device->getOneData($whereRobot);
        if ($robotInfo && !is_array($robotInfo)) {
            $robotInfo = $robotInfo->toArray();
        }
        $branchId = isset($robotInfo['branch_id']) && $robotInfo['branch_id'] ? $robotInfo['branch_id'] : 0;
        $tenantId = isset($robotInfo['tenant_id']) && $robotInfo['tenant_id'] ? $robotInfo['tenant_id'] : 0;
        $tenantCode = isset($robotInfo['tenant_code']) && $robotInfo['tenant_code'] ? $robotInfo['tenant_code'] : '';
        $village_id = isset($robotInfo['village_id']) && $robotInfo['village_id'] ? intval($robotInfo['village_id']) : 0;
        if (!$village_id) {
            return false;
        }
        $params = [
            'jobType' => 'villageSyncAll',
            'robotCode' => $robotCode,
            'branchId' => $branchId,
            'tenantId' => $tenantId,
            'tenantCode' => $tenantCode,
            'village_id' => $village_id,
        ];
        try {
            $this->traitCommonRobot($params);
        } catch (\Exception $e) {
            fdump_api([$params, $e->getMessage()], 'thirdRobot/ErrRobotOneClickSync');
        }
        return true;
    }

    /**
     * 设备一键同步分发队列.
     */
    public function villageSyncAll($params)
    {
        $branchId = isset($params['branchId']) && $params['branchId'] ? $params['branchId'] : 0;
        $tenantId = isset($params['tenantId']) && $params['tenantId'] ? $params['tenantId'] : 0;
        $village_id = isset($params['village_id']) && $params['village_id'] ? intval($params['village_id']) : 0;
        $robotCode = isset($params['robotCode']) && $params['robotCode'] ? trim($params['robotCode']) : '';
        $tenantCode = isset($params['tenantCode']) && $params['tenantCode'] ? trim($params['tenantCode']) : '';
        if (!$branchId || !$village_id) {
            $db_third_robot_device = new ThirdRobotDevice();
            $whereRobot = [];
            $whereRobot[] = ['robot_code', '=', $robotCode];
            $whereRobot[] = ['del_time', '=', 0];
            $robotInfo = $db_third_robot_device->getOneData($whereRobot);
            if ($robotInfo && !is_array($robotInfo)) {
                $robotInfo = $robotInfo->toArray();
            }
            !$branchId && $branchId = isset($robotInfo['branch_id']) && $robotInfo['branch_id'] ? $robotInfo['branch_id'] : 0;
            !$tenantId && $tenantId = isset($robotInfo['tenant_id']) && $robotInfo['tenant_id'] ? $robotInfo['tenant_id'] : 0;
            !$tenantCode && $tenantCode = isset($robotInfo['tenant_code']) && $robotInfo['tenant_code'] ? $robotInfo['tenant_code'] : 0;
            !$village_id && $village_id = isset($robotInfo['village_id']) && $robotInfo['village_id'] ? intval($robotInfo['village_id']) : 0;
        }
        if (!$robotCode || !$branchId || !$village_id) {
            fdump_api(['params' => $params, 'err' => '参数不齐'], 'thirdRobot/ErrVillageSyncAllLog', 1);
            return false;
        }
        $db_house_village_user_bind = new HouseVillageUserBind();
        $whereUser = [];
        $whereUser[] = ['village_id', '=', $village_id];
        $whereUser[] = ['status', '=', 1];
        $whereUser[] = ['uid', '>', 0];
        $userList = $db_house_village_user_bind->getList($whereUser, 'pigcms_id, village_id, uid, name, phone, id_card');
        if ($userList && !is_array($userList)) {
            $userList = $userList->toArray();
        }
        foreach ($userList as $user) {
            $params = [
                'jobType' => 'addRoboterFace',
                'robotCode' => $robotCode,
                'branchId' => $branchId,
                'tenantId' => $tenantId,
                'tenantCode' => $tenantCode,
                'village_id' => $village_id,
                'pigcms_id' => $user['pigcms_id'],
                'uid' => $user['uid'],
            ];
            try {
                $this->traitCommonRobot($params);
            } catch (\Exception $e) {
                fdump_api([$params, $e->getMessage()], 'thirdRobot/ErrRobotOneClickSync');
            }
        }
        return true;
    }

    /**
     * 单个人员下发转队列.
     */
    public function personToRobotsJob($pigcms_id, $uid = 0)
    {
        $params = [
            'jobType' => 'personToRobots',
            'pigcms_id' => $pigcms_id,
            'uid' => $uid,
        ];
        try {
            $this->traitCommonRobot($params);
        } catch (\Exception $e) {
            fdump_api([$params, $e->getMessage()], 'thirdRobot/ErrPersonToRobotsJob');
        }
    }

    /**
     * 单个人员分发不同设备队列.
     */
    public function personToRobots($params)
    {
        $pigcms_id = isset($params['pigcms_id']) && $params['pigcms_id'] ? intval($params['pigcms_id']) : 0;
        $uid = isset($params['uid']) && $params['uid'] ? intval($params['uid']) : 0;
        if (!$pigcms_id) {
            fdump_api(['params' => $params, 'err' => '单个人员同步[参数不齐]'], 'thirdRobot/ErrPersonToRobotsLog', 1);
            return false;
        }
        $db_house_village_user_bind = new HouseVillageUserBind();
        $whereUser = [];
        $whereUser[] = ['pigcms_id', '=', $pigcms_id];
        $userInfo = $db_house_village_user_bind->getOne($whereUser, 'pigcms_id, uid, village_id');
        if ($userInfo && !is_array($userInfo)) {
            $userInfo = $userInfo->toArray();
        }else{
            $userInfo=array();
        }
        if (empty($userInfo)) {
            fdump_api(['params' => $params, 'err' => '单个人员同步[对应人员不存在]'], 'thirdRobot/ErrPersonToRobotsLog', 1);
            return false;
        }
        !$uid && $uid = isset($userInfo['uid']) && $userInfo['uid'] ? intval($userInfo['uid']) : 0;
        $village_id = isset($userInfo['village_id']) && $userInfo['village_id'] ? intval($userInfo['village_id']) : 0;
        $db_third_robot_device = new ThirdRobotDevice();
        $whereRobot = [];
        $whereRobot[] = ['village_id', '=', $userInfo['village_id']];
        $whereRobot[] = ['del_time', '=', 0];
        $robotList = $db_third_robot_device->getListData($whereRobot, 'branch_id, robot_code, tenant_id, tenant_code');
        if ($robotList && !is_array($robotList)) {
            $robotList = $robotList->toArray();
        }
        foreach ($robotList as $robotInfo) {
            $branchId = isset($robotInfo['branch_id']) && $robotInfo['branch_id'] ? $robotInfo['branch_id'] : 0;
            $tenantId = isset($robotInfo['tenant_id']) && $robotInfo['tenant_id'] ? $robotInfo['tenant_id'] : 0;
            $tenantCode = isset($robotInfo['tenant_code']) && $robotInfo['tenant_code'] ? $robotInfo['tenant_code'] : '';
            $robotCode = isset($robotInfo['robot_code']) && $robotInfo['robot_code'] ? $robotInfo['robot_code'] : 0;
            !$village_id && $village_id = isset($robotInfo['village_id']) && $robotInfo['village_id'] ? intval($robotInfo['village_id']) : 0;
            $params = [
                'jobType' => 'addRoboterFace',
                'robotCode' => $robotCode,
                'branchId' => $branchId,
                'tenantId' => $tenantId,
                'tenantCode' => $tenantCode,
                'village_id' => $village_id,
                'pigcms_id' => $pigcms_id,
                'uid' => $uid,
            ];
            try {
                $this->traitCommonRobot($params);
            } catch (\Exception $e) {
                fdump_api([$params, $e->getMessage()], 'thirdRobot/ErrPersonToRobotsLog', 1);
            }
        }
        return true;
    }


    /**
     * 单个人员对应单个机器人进行人员添加.
     */
    public function addRoboterFace($params)
    {
        $branchId = isset($params['branchId']) && $params['branchId'] ? $params['branchId'] : 0;
        $tenantId = isset($params['tenantId']) && $params['tenantId'] ? $params['tenantId'] : 0;
        $village_id = isset($params['village_id']) && $params['village_id'] ? intval($params['village_id']) : 0;
        $robotCode = isset($params['robotCode']) && $params['robotCode'] ? trim($params['robotCode']) : '';
        $tenantCode = isset($params['tenantCode']) && $params['tenantCode'] ? trim($params['tenantCode']) : '';
        $pigcms_id = isset($params['pigcms_id']) && $params['pigcms_id'] ? intval($params['pigcms_id']) : 0;
        $uid = isset($params['uid']) && $params['uid'] ? intval($params['uid']) : 0;
        if (!$robotCode || !$branchId || !$village_id || (!$pigcms_id && !$uid)) {
            fdump_api(['params' => $params, 'err' => '参数不齐'], 'thirdRobot/ErrAddRoboterFaceLog', 1);
            return false;
        }
        $db_house_village_user_bind = new HouseVillageUserBind();
        $whereUser = [];
        $whereUser[] = ['village_id', '=', $village_id];
        if ($pigcms_id > 0) {
            $whereUser[] = ['pigcms_id', '=', $pigcms_id];
        } else {
            $whereUser[] = ['uid', '=', $uid];
        }
        $userList = $db_house_village_user_bind->getList($whereUser, 'pigcms_id, village_id, uid, name, status, phone, id_card, authentication_field');
        if ($userList && !is_array($userList)) {
            $userList = $userList->toArray();
        }
        if (empty($userList)) {
            fdump_api(['params' => $params, 'whereUser' => $whereUser, 'err' => '缺少同步对象'], 'thirdRobot/ErrAddRoboterFaceLog', 1);
            return false;
        }
        // person_type 人员类型 0=陌生人 1=居民 2=vip 3=黑名单 4白名单
        $crossParams = [
            'branchId' => $branchId,
            'personType' => 1,
        ];
        $sex = 0;
        $name = '';
        $phone = '';
        $idCard = '';
        $statusText = [
            0 => '禁止', 1 => '正常', 2 => '审核中', 3 => '已删除', 4 => '已删除',
        ];
        $pigcmsIds = [];
        foreach ($userList as &$user) {
            if (intval($user['status']) != 1) {
                $whereUserBind = [];
                $whereUserBind[] = ['pigcms_id', '=', $user['pigcms_id']];
                $text = isset($statusText[$user['status']]) ? $statusText[$user['status']] : '';
                $saveUserBind = [
                    'face_img_status' => 2, 'face_img_reason' => '人员状态异常[' . $text . ']',
                ];
                $db_house_village_user_bind->saveOne($whereUserBind, $saveUserBind);
                continue;
            }
            if (!$uid && isset($user['uid']) && $user['uid']) {
                $uid = intval($user['uid']);
            }
            if (!$sex && isset($user['authentication_field']) && $user['authentication_field']) {
                $authentication_field = unserialize($user['authentication_field']);
                $sex = isset($authentication_field['sex']) ? trim($authentication_field['sex']['value']) : '';
                $sex = ($sex == '男') ? 1 : 0;
            }
            if (!$name && isset($user['name']) && $user['name']) {
                $name = trim($user['name']);
                $name = $this->replaceWord($user['pigcms_id'], $name, '', 25);
            }
            if (!$phone && isset($user['phone']) && $user['phone']) {
                $phone = trim($user['phone']);
            }
            if (!$idCard && isset($user['id_card']) && $user['id_card']) {
                $idCard = trim($user['id_card']);
            }
            unset($user['authentication_field']);
            $pigcmsIds[] = $user['pigcms_id'];
        }
        if ($uid) {
            $faceUrl = $this->getUserFaceImg($uid);
        } else {
            $faceUrl = '';
        }
        if (!$uid || !$faceUrl) {
            fdump_api(['params' => $params, 'userList' => count($userList), 'faceUrl' => $faceUrl, 'err' => '机器人同步需要人脸图片'], 'thirdRobot/ErrAddRoboterFaceLog', 1);
            if ($pigcms_id > 0) {
                $whereUserBind = [];
                $whereUserBind[] = ['pigcms_id', '=', $pigcms_id];
                $saveUserBind = [
                    'face_img_status' => 2, 'face_img_reason' => '同步机器人失败[缺少人脸]',
                ];
                $db_house_village_user_bind->saveOne($whereUserBind, $saveUserBind);
            }
            return false;
        }
        // 性别 0=女 1=男
        $crossParams['gender'] = $sex == 1 ? 1 : 0;
        $name && $crossParams['name'] = $name;
        $phone && $crossParams['phone'] = $phone;
        fdump_api(['crossParams' => $crossParams, 'faceUrl' => $faceUrl, 'uid' => $uid], 'thirdRobot/000addRoboterFace', 1);
        //$faceImg = chunk_split(base64_encode(file_get_contents($faceUrl)));
        $faceImg ='data:img/jpg;base64,'.base64_encode(file_get_contents($faceUrl));
        //data:image/jpeg;base64,
        $crossParams['faceImg'] = $faceImg;
        fdump_api(['crossParams' => $crossParams], 'thirdRobot/000addRoboterFace', 1);
        $third_robot_recognition_open_hi = cfg('third_robot_recognition_open_hi');
        $third_robot_recognition_hitext = cfg('third_robot_recognition_hitext');
        $greetings = '';
        $call_text = ($sex == 1) ? '先生' : (($sex == 2) ? '女士' : '');
        if ($name && $sex > 0) {
            $greetings .= $name . $call_text . '您好';
        }
        if (intval($third_robot_recognition_open_hi) === 1 && $third_robot_recognition_hitext) {
            // 打招呼标识 0=关闭 1=开启
            $crossParams['greetingsFlag'] = 1;
            $replaceText = RobotConst::DATA_GREETINGS_NAME;
            $greetings = $third_robot_recognition_hitext;
            $greetings = str_replace($replaceText, $name, $greetings);
            $replaceText1 = RobotConst::DATA_GENDER_CALL;
            $greetings = str_replace($replaceText1, $call_text, $greetings);
            $crossParams['greetings'] = $greetings;
        } else {
            $crossParams['greetingsFlag'] = 0;
        }
        $db_device_bind_user = new DeviceBindUser();
        $toUniqueIdArr = [
            'type' => DeviceConst::DEVICE_AUTH_TYPE_THIRD_ROBOT,
            'village_id' => $village_id,
            'uid' => $uid,
        ];

        $toUniqueId = md5(json_encode($toUniqueIdArr, JSON_UNESCAPED_UNICODE));
        $whereDeviceBindUser = [
            'to_unique_id' => $toUniqueId,
        ];
        try {
            $deviceBindUserInfo = $db_device_bind_user->getOne($whereDeviceBindUser);
            if (isset($deviceBindUserInfo['cloud_uid']) && $deviceBindUserInfo['cloud_uid']) {
                $crossParams['id'] = $deviceBindUserInfo['cloud_uid'];
                if (isset($deviceBindUserInfo['cloud_face_id']) && $deviceBindUserInfo['cloud_face_id']) {
                    $crossParams['faceId'] = $deviceBindUserInfo['cloud_face_id'];
                }
                if (!$tenantCode || !$tenantId) {
                    $db_third_robot_device = new ThirdRobotDevice();
                    $whereRobot = [];
                    $whereRobot[] = ['robot_code', '=', $robotCode];
                    $whereRobot[] = ['del_time', '=', 0];
                    $robotInfo = $db_third_robot_device->getOneData($whereRobot);
                    if ($robotInfo && !is_array($robotInfo)) {
                        $robotInfo = $robotInfo->toArray();
                    }
                    $tenantId = isset($robotInfo['tenant_id']) && $robotInfo['tenant_id'] ? $robotInfo['tenant_id'] : '';
                    $tenantCode = isset($robotInfo['tenant_code']) && $robotInfo['tenant_code'] ? $robotInfo['tenant_code'] : '';
                }
                $tenantId && $crossParams['tenantId'] = $tenantId;
                $tenantCode && $crossParams['tenantCode'] = $tenantCode;
                fdump_api(['crossParams' => $crossParams, 'faceUrl' => $faceUrl, 'uid' => $uid], 'thirdRobot/000addRoboterFace', 1);
                $result = (new ThirdRobotRecognitionService())->updateRoboterFace($crossParams);
            } else {
                fdump_api(['crossParams' => $crossParams, 'faceUrl' => $faceUrl, 'uid' => $uid], 'thirdRobot/000addRoboterFace', 1);
                $result = (new ThirdRobotRecognitionService())->addRoboterFace($crossParams);
            }
        } catch (\Exception $e) {
            fdump_api([__LINE__, $e->getMessage()], 'thirdRobot/000addRoboterFace');
        }
        fdump_api(['crossParams' => $crossParams, 'result' => $result], 'thirdRobot/000addRoboterFace', 1);
        $nowTime = time();
        if (isset($result['data']['id']) && $result['data']['id']) {
            $faceId = isset($result['data']['faceId']) ? $result['data']['faceId'] : '';
            $data = [
                'type' => DeviceConst::DEVICE_AUTH_TYPE_THIRD_ROBOT,
                'village_id' => $village_id,
                'uid' => $uid,
                'device_sn' => $robotCode,
                'cloud_uid' => $result['data']['id'],
                'cloud_face_id' => $faceId,
                'status' => 1,
                'to_cloud_json' => json_encode($crossParams, JSON_UNESCAPED_UNICODE),
                'to_unique_id' => $toUniqueId,
                'issued_time' => $nowTime,
            ];
            if ($pigcms_id) {
                $data['bind_id'] = $pigcms_id;
            }
            if (isset($deviceBindUserInfo['log_id']) && $deviceBindUserInfo['log_id']) {
                $db_device_bind_user->updateThis($whereDeviceBindUser, $data);
            } else {
                $data['add_time'] = $nowTime;
                $db_device_bind_user->add($data);
            }
            $whereUserBind = [];
            $whereUserBind[] = ['pigcms_id', 'in', $pigcmsIds];
            $saveUserBind = [
                'face_img_status' => 1, 'face_img_reason' => '', 'add_face_time' => $nowTime
            ];
            $db_house_village_user_bind->saveOne($whereUserBind, $saveUserBind);
        } else {
            $pageCrossParams = [
                'current' => 1,
                'size' => 100,
                'phone' => $phone,
            ];
            $result1 = (new ThirdRobotRecognitionService())->pageRoboterFace($pageCrossParams);
            fdump_api(['pageCrossParams' => $pageCrossParams, 'result' => $result], 'thirdRobot/000pageRoboterFace', 1);
            if (isset($result1['data']['records']) && isset($result1['data']['records'][0])) {
                $records = $result1['data']['records'][0];
                $faceId = isset($records['faceId']) ? $records['faceId'] : '';
                $id = isset($records['id']) ? $records['id'] : '';
                $data = [
                    'type' => DeviceConst::DEVICE_AUTH_TYPE_THIRD_ROBOT,
                    'village_id' => $village_id,
                    'uid' => $uid,
                    'device_sn' => $robotCode,
                    'cloud_uid' => $id,
                    'cloud_face_id' => $faceId,
                    'status' => 1,
                    'to_cloud_json' => json_encode($crossParams, JSON_UNESCAPED_UNICODE),
                    'to_unique_id' => $toUniqueId,
                    'issued_time' => $nowTime,
                ];
                if ($pigcms_id) {
                    $data['bind_id'] = $pigcms_id;
                }
                if (isset($deviceBindUserInfo['log_id']) && $deviceBindUserInfo['log_id']) {
                    $db_device_bind_user->updateThis($whereDeviceBindUser, $data);
                } else {
                    $data['add_time'] = $nowTime;
                    $db_device_bind_user->add($data);
                }
                $whereUserBind = [];
                $whereUserBind[] = ['pigcms_id', 'in', $pigcmsIds];
                $saveUserBind = [
                    'face_img_status' => 1, 'face_img_reason' => '', 'add_face_time' => $nowTime
                ];
                $db_house_village_user_bind->saveOne($whereUserBind, $saveUserBind);
            } else {
                $message = isset($result['message']) && $result['message'] ? $result['message'] : '人脸同步机器人失败';
                $whereUserBind = [];
                $whereUserBind[] = ['pigcms_id', 'in', $pigcmsIds];
                $saveUserBind = [
                    'face_img_status' => 2, 'face_img_reason' => $message,
                ];
                $db_house_village_user_bind->saveOne($whereUserBind, $saveUserBind);
            }
        }
        return true;
    }

    /**
     * 单个人员删除下发转队列.
     */
    public function deleteRoboterFaceJob($pigcms_id, $uid = 0)
    {
        $params = [
            'jobType' => 'deleteRoboterFace',
            'pigcms_id' => $pigcms_id,
            'uid' => $uid,
        ];
        try {
            $this->traitCommonRobot($params);
        } catch (\Exception $e) {
            fdump_api([$params, $e->getMessage()], 'thirdRobot/ErrDeleteRoboterFaceJob');
        }
    }

    /**
     * 单个人员同步删除.
     */
    public function deleteRoboterFace($params)
    {
        $pigcms_id = isset($params['pigcms_id']) && $params['pigcms_id'] ? intval($params['pigcms_id']) : 0;
        $village_id = isset($params['village_id']) && $params['village_id'] ? intval($params['village_id']) : 0;
        $uid = isset($params['uid']) && $params['uid'] ? intval($params['uid']) : 0;
        if (!$pigcms_id && !$uid) {
            fdump_api(['params' => $params, 'err' => '同步删除失败[参数不齐]'], 'thirdRobot/ErrDeleteRoboterFacelLog', 1);
            return false;
        }
        $db_house_village_user_bind = new HouseVillageUserBind();
        $whereUser = [];
        if ($pigcms_id > 0) {
            $whereUser[] = ['pigcms_id', '>', $pigcms_id];
        }
        if ($uid > 0) {
            $whereUser[] = ['uid', '>', $uid];
            $whereUser[] = ['village_id', '=', $village_id];
        }
        $userList = $db_house_village_user_bind->getList($whereUser, 'pigcms_id, village_id, uid, status');
        if ($userList && !is_array($userList)) {
            $userList = $userList->toArray();
        }
        if (empty($userList)) {
            fdump_api(['params' => $params, 'whereUser' => $whereUser, 'err' => '缺少同步删除对象'], 'thirdRobot/ErrDeleteRoboterFacelLog', 1);
            return false;
        }
        foreach ($userList as $user) {
            $status = intval($user['status']);
            if (!in_array($status, [3, 4])) {
                fdump_api(['params' => $params, 'whereUser' => $whereUser, 'err' => '还存在相同身份不允许删除'], 'thirdRobot/ErrDeleteRoboterFacelLog', 1);
                return false;
            }
        }
        $nowTime = time();
        $db_device_bind_user = new DeviceBindUser();
        $toUniqueIdArr = [
            'type' => DeviceConst::DEVICE_AUTH_TYPE_THIRD_ROBOT,
            'village_id' => $village_id,
            'uid' => $uid,
        ];
        $toUniqueId = md5(json_encode($toUniqueIdArr, JSON_UNESCAPED_UNICODE));
        $whereDeviceBindUser = [
            'to_unique_id' => $toUniqueId,
        ];
        $deviceBindUserInfo = $db_device_bind_user->getOne($whereDeviceBindUser);
        if (isset($deviceBindUserInfo['cloud_uid']) && $deviceBindUserInfo['cloud_uid']) {
            $crossParams = [];
            $crossParams['ids'] = [];
            $crossParams['ids'][] = $deviceBindUserInfo['cloud_uid'];
            $result = (new ThirdRobotRecognitionService())->deleteRoboterFace($crossParams);
            if (isset($result['code']) && intval($result['code']) == 0) {
                $data = [
                    'status' => 4,
                    'issued_time' => $nowTime,
                ];
                $db_device_bind_user->updateThis($whereDeviceBindUser, $data);
            } else {
                $data = [
                    'status' => 5,
                    'issued_time' => $nowTime,
                ];
                $db_device_bind_user->updateThis($whereDeviceBindUser, $data);
            }
        }
        return true;
    }

    /**
     * 获取机器人所有业务点.
     */
    public function jobToGetRobotsBizPoints($params = [])
    {
        $jobParam = $params;
        $jobParam['jobType'] = 'getRobotsBizPoints';
        $jobParam['from'] = 'job';
        try {
            $this->traitCommonRobot($jobParam, 1);
        } catch (\Exception $e) {
            fdump_api([$jobParam, $e->getMessage(), 'line' => __LINE__], 'thirdRobot/ErrJobToGetRobotsBizPoints');
        }
        return true;
    }

    /**
     * 获取所有机器人的业务点
     */
    public function getRobotsBizPoints($params = [])
    {
        $village_id = isset($params['village_id']) && $params['village_id'] ? intval($params['village_id']) : 0;
        $page = isset($params['page']) && $params['page'] ? intval($params['page']) : 1;
        $pageSize = isset($params['pageSize']) && $params['pageSize'] ? intval($params['pageSize']) : 100;
        $db_third_robot_device = new ThirdRobotDevice();
        $where = [];
        $where[] = ['del_time', '=', 0];
        if ($village_id > 0) {
            $where[] = ['village_id', '=', $village_id];
        }
        $count = $db_third_robot_device->getCount($where);
        $total = $page * $pageSize;
        $robotList = $db_third_robot_device->getListData($where, 'id, village_id, branch_id, robot_code, tenant_id, tenant_code', 'id DESC', $page, $pageSize);
        if ($robotList && !is_array($robotList)) {
            $robotList = $robotList->toArray();
        }
        foreach ($robotList as $robotInfo) {
            $jobParam = $robotInfo;
            $jobParam['jobType'] = 'getRobotBizPointList';
            $jobParam['from'] = 'job';
            try {
                $this->traitCommonRobot($jobParam, 1);
            } catch (\Exception $e) {
                fdump_api([$jobParam, $e->getMessage(), 'line' => __LINE__], 'thirdRobot/ErrGetRobotBizPointList');
            }
        }
        if (!empty($robotList) && $total < $count) {
            $jobParam1 = $params;
            $jobParam1['page'] = $page + 1;
            $jobParam1['from'] = 'job';
            $jobParam1['jobType'] = 'getRobotsBizPoints';
            try {
                $this->traitCommonRobot($jobParam1, 1);
            } catch (\Exception $e) {
                fdump_api([$jobParam1, $e->getMessage(), 'line' => __LINE__], 'thirdRobot/ErrGetRobotsBizPoints');
            }
        }
        return true;
    }

    public function getRobotBizPointList($params)
    {
        $robotCode = isset($params['robot_code']) && $params['robot_code'] ? trim($params['robot_code']) : '';
        if (!$robotCode) {
            fdump_api(['params' => $params, 'err' => '参数缺失', 'line' => __LINE__], 'thirdRobot/ErrGetRobotBizPointList');
            return false;
        }
        $param = [
            'robotCode' => $robotCode,
        ];
        $result = (new ThirdRobotRecognitionService())->getBizPointList($param);
        if (isset($result['data']) && $result['data']) {
            $nowTime = time();
            $db_third_robot_map = new ThirdRobotMap();
            $db_third_robot_map_points = new ThirdRobotMapPoints();
            $record_flag = md5(date('YmdHis') . uniqid() . $robotCode . microtime(true));
            foreach ($result['data'] as $data) {
                $thirdRobotMap = ['robot_code' => $robotCode];
                $thirdRobotMap['map_name'] = isset($data['mapName']) && $data['mapName'] ? trim($data['mapName']) : '';
                $mapCode = isset($data['mapCode']) && $data['mapCode'] ? trim($data['mapCode']) : '';
                $floor = isset($data['floor']) && $data['floor'] ? trim($data['floor']) : '';
                $thirdRobotMap['active'] = isset($data['active']) && $data['active'] ? intval($data['active']) : 0;
                $thirdRobotMap['adjusting_x'] = isset($data['adjustingX']) && $data['adjustingX'] ? trim($data['adjustingX']) : '';
                $thirdRobotMap['adjusting_y'] = isset($data['adjustingY']) && $data['adjustingY'] ? trim($data['adjustingY']) : '';
                $thirdRobotMap['width'] = isset($data['width']) && $data['width'] ? trim($data['width']) : '';
                $thirdRobotMap['height'] = isset($data['height']) && $data['height'] ? trim($data['height']) : '';
                $thirdRobotMap['floor_id'] = isset($data['floorId']) && $data['floorId'] ? trim($data['floorId']) : '';
                $floorName = isset($data['floorName']) && $data['floorName'] ? trim($data['floorName']) : '';
                !$floorName && $floor && $floorName = $floor;
                $thirdRobotMap['map_code'] = $mapCode;
                $thirdRobotMap['floor_name'] = $floorName;
                $map_extra = $data;
                $keyArr = [
                    'mapName', 'mapCode', 'floor', 'active', 'adjustingX',
                    'adjustingY', 'width', 'height', 'floorId', 'floorName', 'bizPoints',
                ];
                $map_extra = unsetWordParam($map_extra, $keyArr);
                if (!empty($map_extra)) {
                    $thirdRobotMap['map_extra'] = json_encode($map_extra, JSON_UNESCAPED_UNICODE);
                }

                $whereUnique = ['robot_code' => $robotCode, 'map_code' => $mapCode];
                $uniqueInfo = $db_third_robot_map->getOne($whereUnique, 'id');
                $thirdRobotMap['record_flag'] = $record_flag;
                if (isset($uniqueInfo['id']) && $uniqueInfo['id']) {
                    $thirdRobotMap['update_time'] = $nowTime;
                    $db_third_robot_map->updateThis(['id' => $uniqueInfo['id']], $thirdRobotMap);
                    $robot_map_id = $uniqueInfo['id'];
                } else {
                    $thirdRobotMap['add_time'] = $nowTime;
                    $robot_map_id = $db_third_robot_map->add($thirdRobotMap);
                }
                $bizPoints = isset($data['bizPoints']) && $data['bizPoints'] ? $data['bizPoints'] : [];
                foreach ($bizPoints as $point) {
                    $id = isset($point['id']) && $point['id'] ? trim($point['id']) : '';
                    $floor = isset($point['floor']) && $point['floor'] ? trim($point['floor']) : '';
                    $floorName = isset($point['floorName']) && $point['floorName'] ? trim($point['floorName']) : '';
                    !$floorName && $floor && $floorName = $floor;
                    !$floorName && $thirdRobotMap['floor_name'] && $floorName = $thirdRobotMap['floor_name'];
                    $robotMapPoints = ['robot_code' => $robotCode];
                    $robotMapPoints['point_id'] = $id;
                    $robotMapPoints['robot_map_id'] = $robot_map_id;
                    $robotMapPoints['floor'] = $floorName;
                    $robotMapPoints['name'] = isset($point['name']) && $point['name'] ? trim($point['name']) : '';
                    $robotMapPoints['map_code'] = isset($point['mapCode']) && $point['mapCode'] ? trim($point['mapCode']) : $mapCode;
                    $robotMapPoints['map_name'] = isset($point['mapName']) && $point['mapName'] ? trim($point['mapName']) : $thirdRobotMap['map_name'];
                    $robotMapPoints['poi_name'] = isset($point['poiName']) && $point['poiName'] ? trim($point['poiName']) : '';
                    $robotMapPoints['point_name'] = isset($point['pointName']) && $point['pointName'] ? trim($point['pointName']) : '';
                    $robotMapPoints['type'] = isset($point['type']) && $point['type'] ? intval($point['type']) : 1;
                    $robotMapPoints['phone'] = isset($point['phone']) && $point['phone'] ? trim($point['phone']) : '';
                    $robotMapPoints['sort'] = isset($point['sort']) && $point['sort'] ? trim($point['sort']) : '';
                    $robotMapPoints['class_code'] = isset($point['classCode']) && $point['classCode'] ? trim($point['classCode']) : '';
                    $robotMapPoints['class_name'] = isset($point['className']) && $point['className'] ? trim($point['className']) : '';
                    $robotMapPoints['create_time'] = isset($point['createTime']) && $point['createTime'] ? trim($point['createTime']) : '';
                    $robotMapPoints['update_time'] = isset($point['updateTime']) && $point['updateTime'] ? trim($point['updateTime']) : '';
                    $robotMapPoints['point_x_y_yaw'] = isset($point['point']) && $point['point'] ? trim($point['point']) : '';
                    $robotMapPoints['point_x'] = isset($point['x']) && $point['x'] ? trim($point['x']) : '';
                    $robotMapPoints['point_y'] = isset($point['y']) && $point['y'] ? trim($point['y']) : '';
                    $robotMapPoints['point_z'] = isset($point['z']) && $point['z'] ? trim($point['z']) : '';
                    $robotMapPoints['point_yaw'] = isset($point['yaw']) && $point['yaw'] ? trim($point['yaw']) : '';
                    $robotMapPoints['floor_id'] = isset($point['floorId']) && $point['floorId'] ? trim($point['floorId']) : $thirdRobotMap['floor_id'];

                    $point_extra = $data;
                    $keyArr = [
                        'id', 'floor', 'floorName', 'name', 'mapCode', 'mapName', 'poiName', 'pointName', 'type', 'phone',
                        'sort', 'classCode', 'className', 'createTime', 'updateTime', 'point', 'x', 'y', 'z', 'yaw', 'floorId',
                    ];
                    $point_extra = unsetWordParam($point_extra, $keyArr);
                    if (!empty($point_extra)) {
                        $thirdRobotMap['point_extra'] = json_encode($point_extra, JSON_UNESCAPED_UNICODE);
                    }
                    $whereUnique = ['point_id' => $id, 'robot_code' => $robotCode, 'map_code' => $robotMapPoints['map_code']];
                    $uniqueInfo = $db_third_robot_map_points->getOne($whereUnique, 'id');
                    $robotMapPoints['record_flag'] = $record_flag;
                    if (isset($uniqueInfo['id']) && $uniqueInfo['id']) {
                        $robotMapPoints['last_time'] = $nowTime;
                        $db_third_robot_map_points->updateThis(['id' => $uniqueInfo['id']], $robotMapPoints);
                        $uniqueInfo['id'];
                    } else {
                        $robotMapPoints['add_time'] = $nowTime;
                        $db_third_robot_map_points->add($robotMapPoints);
                    }
                }
            }

            $whereDelete = [
                ['robot_code', '=', $robotCode],
                ['record_flag', '<>', $record_flag],
            ];
            $db_third_robot_map->updateThis($whereDelete, ['delete_time' => $nowTime]);
            $db_third_robot_map_points->updateThis($whereDelete, ['delete_time' => $nowTime]);

            fdump_api($result, 'thirdRobot/' . $robotCode);
        }
        return $result;
    }

    /**
     * 呼叫机器人[并记录].
     */
    public function userCallRobot($param)
    {
        fdump_api(['msg' => '开始', 'time' => microtime(true)], '$userCallRobot', 1);
        $jobParam = $param;
        $uid = isset($param['uid']) && $param['uid'] ? intval($param['uid']) : 0;
        $robot_id = isset($param['robot_id']) && $param['robot_id'] ? intval($param['robot_id']) : 0;
        $robot_code = isset($param['robot_code']) && $param['robot_code'] ? trim($param['robot_code']) : '';
        $id = isset($param['id']) && $param['id'] ? intval($param['id']) : 0;
        $point_id = isset($param['point_id']) && $param['point_id'] ? trim($param['point_id']) : '';
        $nowTime = time();

        if (!$robot_id && !$robot_code) {
            throw new \Exception("呼叫机器人不存在！");
        }
        $thirdRobotDeviceService = new ThirdRobotDeviceService();
        $thirdRobotDeviceCmdService = new ThirdRobotDeviceCmdService();
        $whereThirdRobot = [];
        if ($robot_id) {
            $whereThirdRobot[] = ['id', '=', $robot_id];
        }
        if ($robot_code) {
            $whereThirdRobot[] = ['robot_code', '=', $robot_code];
        }
        $robotInfo = $thirdRobotDeviceService->getOneDataInfo($whereThirdRobot, 'id, robot_code, name, robot_type, online_flag, work_flag, charge_flag, running_status');
        if ($robotInfo && !is_array($robotInfo)) {
            $robotInfo = $robotInfo->toArray();
        }
        $robotInfo = $thirdRobotDeviceService->getRobotStatusText($robotInfo, $robotInfo);
        $thirdRobotDeviceCmdService->toGetThirdRobotInfoJob($robotInfo['robot_code']);
//        if (isset($robotInfo['isCall']) && ! $robotInfo['isCall'] && $robotInfo['callTip']) {
//            throw new \Exception($robotInfo['callTip']);
//        }
        if (!isset($robotInfo['id']) || !$robotInfo['id']) {
            throw new \Exception("呼叫机器人不存在！");
        }

        !$robot_id && $robot_id = isset($robotInfo['id']) && $robotInfo['id'] ? intval($robotInfo['id']) : 0;
        !$robot_code && $robot_code = isset($robotInfo['robot_code']) && $robotInfo['robot_code'] ? trim($robotInfo['robot_code']) : '';

        $db_third_robot_map_points = new ThirdRobotMapPoints();
        $wherePoints = [];
        if ($id) {
            $wherePoints[] = ['id', '=', $id];
        }
        if ($point_id) {
            $wherePoints[] = ['point_id', '=', $point_id];
        }
        $point_info = $db_third_robot_map_points->getOne($wherePoints);
        if (!isset($point_info['id']) || !$point_info['id']) {
            throw new \Exception("呼叫发起业务点不存在！");
        }
        if ($point_info && !is_array($point_info)) {
            $point_info = $point_info->toArray();
        }
        !$id && $id = isset($point_info['id']) && $point_info['id'] ? intval($point_info['id']) : 0;
        !$point_id && $point_id = isset($point_info['point_id']) && $point_info['point_id'] ? trim($point_info['point_id']) : '';
        if (!$point_id) {
            throw new \Exception("业务点信息有误！");
        }
        if(!empty($param['call_from']) && $param['call_from'] == 'robot_delivery'){//多点召唤
            return true;
        }
        fdump_api(['msg' => '获取业务点信息', 'time' => microtime(true)], '$userCallRobot', 1);
        $user_info = (new User())->getOne(['uid' => $uid], 'uid, phone');
        $user_phone = isset($user_info['phone']) && trim($user_info['phone']) ? trim($user_info['phone']) : '';
        $jobParam['user_phone'] = $user_phone;
        $jobParam['id'] = $id;
        $jobParam['point_id'] = $point_id;
        $jobParam['robot_id'] = $robot_id;
        $jobParam['robot_code'] = $robot_code;
        $jobParam['call_time'] = $nowTime;
        $this->checkCall($jobParam);

        if (preg_match('/^[0-9]{11}$/', $user_phone)) {
            $node = ['bizPointId' => $point_id, 'phone' => $user_phone];
        } else {
            $node = ['bizPointId' => $point_id];
        }

        $data = [
            'robotCode' => $robot_code,
            'nodes' => [$node],
        ];
        fdump_api(['msg' => '呼叫触发前', 'data' => $data, 'time' => microtime(true)], '$userCallRobot', 1);
        $result = (new ThirdRobotRecognitionService())->callRobot($data);
        fdump_api(['msg' => '呼叫触发结果', 'result' => $result, 'time' => microtime(true)], '$userCallRobot', 1);
        if (isset($result['data']['taskInstanceId']) && $result['data']['taskInstanceId']) {
            $taskInstanceId = $result['data']['taskInstanceId'];
            $jobParam['taskInstanceId'] = $taskInstanceId;
        }
        if (isset($result['code']) && intval($result['code']) > 0 && isset($result['message']) && $result['message']) {
            $jobParam['errorCode'] = $result['code'];
            $jobParam['errorMessage'] = $result['message'];
            $jobParam['errSubCode'] = isset($result['subCode']) && $result['subCode'] ? $result['subCode'] : '';
            fdump_api(['msg' => '记录结果前', 'time' => microtime(true)], '$userCallRobot', 1);
            $this->recordUserCallRobot($jobParam, $point_info, $robotInfo);
            throw new \Exception('机器人：' . $result['message']);
        }
        fdump_api(['msg' => '记录结果前', 'time' => microtime(true)], '$userCallRobot', 1);
        $record_id = $this->recordUserCallRobot($jobParam, $point_info, $robotInfo);
        $result['record_id'] = $record_id;
        return $result;
    }

    /**
     * 多点召唤
     * @param $param
     * @return array|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function multipointCall($param)
    {
        $jobParam    = $param;
        if (isset($param['end_point_id']) && $param['end_point_id']) {
            $param['id'] = $param['end_point_id'];
            $this->userCallRobot($param);
        }
        $param['id'] = $param['start_point_id'];
        $this->userCallRobot($param);

        $startPoint = ThirdRobotMapPoints::where('id', $param['start_point_id'])->find();
        if (isset($param['end_point_id']) && $param['end_point_id']) {
            $endPoint = ThirdRobotMapPoints::where('id', $param['end_point_id'])->find();
        }
        $robotInfo  = ThirdRobotDevice::where('id', $param['robot_id'])->find();

        $jobParam['third_start_point_id'] = $startPoint['point_id'];
        $jobParam['user_phone']           = $jobParam['start_user_phone'];
        if (isset($param['end_point_id']) && $param['end_point_id']) {
            $jobParam['third_end_point_id'] = $endPoint['point_id'];
        }
        $jobParam['robot_id']             = $param['robot_id'];
        $jobParam['robot_code']           = $param['robot_code'];
        $jobParam['call_time']            = time();
        $this->checkCall($jobParam);
        $nodes = [];
        if (isset($startPoint) && isset($startPoint['point_id'])) {
            $nodes[] = ['bizPointId' => $startPoint['point_id'], 'phone' => $param['start_user_phone']];//召唤地
        }
        if (isset($endPoint) && isset($endPoint['point_id'])) {
            $nodes[] = ['bizPointId' => $endPoint['point_id'], 'phone' => $param['end_user_phone']];//目的地
        }

        $data = [
            'robotCode' => $param['robot_code'],
            'nodes' => $nodes,
        ];
        fdump_api(
            ['msg' => '呼叫触发前', 'data' => $data, 'params' => $param, 'time' => microtime(true)], 
            'robot_order/userCallRobot', 
            1
        );
        $result = (new ThirdRobotRecognitionService())->callRobot($data);
        fdump_api(
            ['msg' => '呼叫触发结果', 'result' => $result, 'time' => microtime(true)], 
            'robot_order/userCallRobot', 
            1
        );
        if (isset($result['data']['taskInstanceId']) && $result['data']['taskInstanceId']) {
            $taskInstanceId = $result['task_instance_id'] = $result['data']['taskInstanceId'];
            $jobParam['taskInstanceId'] = $taskInstanceId;
        }
        if (isset($result['code']) && intval($result['code']) > 0 && isset($result['message']) && $result['message']) {
            $jobParam['errorCode'] = $result['code'];
            $jobParam['errorMessage'] = $result['message'];
            $jobParam['errSubCode'] = isset($result['subCode']) && $result['subCode'] ? $result['subCode'] : '';
            fdump_api(['msg' => '结果异常', 'job_params' => $jobParam], 'robot_order/userCallRobot', 1);
            $this->recordUserCallRobot($jobParam, $startPoint, $robotInfo);
            throw new \Exception('机器人：' . $result['message']);
        }
        fdump_api(['msg' => '记录结果', 'job_params' => $jobParam], 'robot_order/userCallRobot', 1);
        $record_id = $this->recordUserCallRobot($jobParam, $startPoint, $robotInfo);
        !empty($taskInstanceId) && app(ThirdRobotDeliveryOrderService::class)->orderRobotEndLocation($taskInstanceId);
        $result['record_id'] = $record_id;
        return $result;
    }
    /**
     * 检查是否可以呼叫.
     */
    public function checkCall($param)
    {
        $uid = isset($param['uid']) && $param['uid'] ? intval($param['uid']) : 0;
        $robot_code = isset($param['robot_code']) && $param['robot_code'] ? trim($param['robot_code']) : '';
        $nowTime = isset($param['nowTime']) && $param['nowTime'] ? $param['nowTime'] : time();
        if (!$uid || !$robot_code) {
            return true;
        }
        $timeLimit = $nowTime - RobotConst::TIME_CALL_SPACE;

        $dbThirdRobotCallRecord = new ThirdRobotCallRecord();
        $whereRecord = [];
        $whereRecord[] = ['uid', '=', $uid];
        $whereRecord[] = ['robot_code', '=', $robot_code];
        $whereRecord[] = ['update_time', '>', 0];
        $whereRecord[] = ['update_time', '>=', $timeLimit];
        $recordInfo = $dbThirdRobotCallRecord->getOne($whereRecord, 'record_id');
        fdump_api($recordInfo, '$recordInfo');
        if (isset($recordInfo['record_id']) && $recordInfo['record_id']) {
            throw new \Exception('请不要频繁呼叫机器人');
        } else {
            return true;
        }
    }

    /**
     * 记录呼叫.
     */
    public function recordUserCallRobot($param, $point_info = [], $robotInfo = [])
    {
        fdump_api(['msg' => '记录结果-开始', 'time' => microtime(true)], '$userCallRobot', 1);
        $uid = isset($param['uid']) && $param['uid'] ? intval($param['uid']) : 0;
        $robot_id = isset($param['robot_id']) && $param['robot_id'] ? intval($param['robot_id']) : 0;
        $robot_code = isset($param['robot_code']) && $param['robot_code'] ? trim($param['robot_code']) : '';
        $id = isset($param['id']) && $param['id'] ? intval($param['id']) : 0;
        $point_id = isset($param['point_id']) && $param['point_id'] ? trim($param['point_id']) : '';
        $user_phone = isset($param['user_phone']) && $param['user_phone'] ? trim($param['user_phone']) : '';
        $task_instance_id = isset($param['taskInstanceId']) && $param['taskInstanceId'] ? trim($param['taskInstanceId']) : '';
        $error_code = isset($param['errorCode']) && $param['errorCode'] ? trim($param['errorCode']) : '';
        $error_message = isset($param['errorMessage']) && $param['errorMessage'] ? trim($param['errorMessage']) : '';
        $err_sub_code = isset($param['errSubCode']) && $param['errSubCode'] ? trim($param['errSubCode']) : '';
        $call_from = isset($param['call_from']) && $param['call_from'] ? trim($param['call_from']) : '';
        $call_from_id = isset($param['call_from_id']) && $param['call_from_id'] ? trim($param['call_from_id']) : '';
        $nowTime = time();
        $call_time = isset($param['call_time']) && $param['call_time'] ? $param['call_time'] : $nowTime;

        if (empty($point_info)) {
            $db_third_robot_map_points = new ThirdRobotMapPoints();
            $wherePoints = [];
            if ($id) {
                $wherePoints[] = ['id', '=', $id];
            }
            if ($point_id) {
                $wherePoints[] = ['point_id', '=', $point_id];
            }
            $point_info = $db_third_robot_map_points->getOne($wherePoints, true);
            if ($point_info && !is_array($point_info)) {
                $point_info = $point_info->toArray();
            }
        }
        !$id && $id = isset($point_info['id']) && $point_info['id'] ? intval($point_info['id']) : 0;
        !$point_id && $point_id = isset($point_info['point_id']) && $point_info['point_id'] ? trim($point_info['point_id']) : '';
        $map_code = isset($point_info['map_code']) && $point_info['map_code'] ? trim($point_info['map_code']) : '';
        $map_name = isset($point_info['map_name']) && $point_info['map_name'] ? trim($point_info['map_name']) : '';
        $point_name = isset($point_info['point_name']) && $point_info['point_name'] ? trim($point_info['point_name']) : '';

        $thirdEndPointInfo = NULL;
        if(!empty($param['third_end_point_id'])){
            $thirdEndPointInfo = ThirdRobotMapPoints::where('point_id', $param['third_end_point_id'])
                ->find()
                ->toArray();
        }
        $record = [
            'uid' => $uid,
            'user_phone' => $user_phone,
            'task_instance_id' => $task_instance_id,
            'map_points_id' => $id,
            'map_end_points_id' => $param['end_point_id'] ?? 0,
            'point_id' => $point_id,
            'end_point_id' => $param['third_end_point_id'] ?? 0,
            'map_code' => $map_code,
            'robot_id' => $robot_id,
            'robot_code' => $robot_code,
            'robot_name' => isset($robotInfo['name']) ? trim($robotInfo['name']) : '',
            'map_name' => $map_name,
            'point_name' => $point_name,
            'end_point_name' => $thirdEndPointInfo['point_name'] ?? '',
            'call_from' => $call_from,
            'call_from_id' => $call_from_id,
            'call_time' => $call_time,
            'status' => 0,
            'error_code' => $error_code,
            'err_sub_code' => $err_sub_code,
            'error_message' => $error_message,
        ];
        $keyArr = ['point_id', 'map_code', 'map_name', 'point_name'];
        fdump_api(['msg' => '记录结果-处理参数', 'time' => microtime(true)], '$userCallRobot', 1);
        $point_info_arr = unsetWordParam($point_info, $keyArr);
        $recordInfo['point_info'] = json_encode($point_info_arr, JSON_UNESCAPED_UNICODE);
        if($thirdEndPointInfo){
            $recordInfo['point_info'] = trim($recordInfo['point_info'].','.json_encode($thirdEndPointInfo, JSON_UNESCAPED_UNICODE),',');
        }
        if (! empty($robotInfo)) {
            $recordInfo['robot_info'] = json_encode($robotInfo, JSON_UNESCAPED_UNICODE);
        }
        $dbThirdRobotCallRecord = new ThirdRobotCallRecord();
        fdump_api(['msg' => '记录结果-记录前', 'record' => $record, 'time' => microtime(true)], '$userCallRobot', 1);

        if ($robot_code && $task_instance_id) {
            $whereRecord = [];
            $whereRecord[] = ['robot_code', '=', $robot_code];
            $whereRecord[] = ['task_instance_id', '=', $task_instance_id];
            $recordInfo = $dbThirdRobotCallRecord->getOne($whereRecord, 'record_id');
        }
        if (isset($recordInfo) && isset($recordInfo['record_id']) && $recordInfo['record_id']) {
            $record_id = $recordInfo['record_id'];
            $recordInfo['update_time'] = $nowTime;
            $dbThirdRobotCallRecord->updateThis($whereRecord, $record);
        } else {
            $recordInfo['add_time'] = $nowTime;
            $recordInfo['update_time'] = $nowTime;
            $record_id = $dbThirdRobotCallRecord->add($record);
        }
        fdump_api(['msg' => '记录结果-记录后', 'record_id' => $record_id, 'time' => microtime(true)], '$userCallRobot', 1);

        if ($task_instance_id && $record_id) {
            fdump_api(['msg' => '记录结果-调取获取任务详情', 'record_id' => $record_id, 'time' => microtime(true)], '$userCallRobot', 1);
            // 存在【任务实例id】调取获取任务详情
            $jobParam = [];
            $jobParam['jobType'] = 'getTaskDetail';
            $jobParam['from'] = 'job';
            $jobParam['record_id'] = $record_id;
            $jobParam['task_instance_id'] = $task_instance_id;
            $jobParam['robot_code'] = $robot_code;
            try {
                $this->traitCommonRobot($jobParam, 1);
            } catch (\Exception $e) {
                fdump_api([$jobParam, $e->getMessage(), 'line' => __LINE__], 'thirdRobot/ErrRecordUserCallRobot');
            }
        }
        return $record_id;
    }

    /**
     * 查询呼叫任务详情.
     */
    public function getTaskDetail($param)
    {
        $task_instance_id = isset($param['task_instance_id']) && $param['task_instance_id'] ? trim($param['task_instance_id']) : '';
        $record_id = isset($param['record_id']) && $param['record_id'] ? intval($param['record_id']) : 0;
        $robot_code = isset($param['robot_code']) && $param['robot_code'] ? trim($param['robot_code']) : '';
        if (!$robot_code && $record_id) {
            $whereRecord = [];
            $dbThirdRobotCallRecord = new ThirdRobotCallRecord();
            $whereRecord[] = ['record_id', '=', $record_id];
            $recordInfo = $dbThirdRobotCallRecord->getOne($whereRecord, 'robot_code');
            $robot_code = isset($recordInfo['robot_code']) && $recordInfo['robot_code'] ? trim($recordInfo['robot_code']) : '';
        }
        if (!$task_instance_id) {
            return false;
        }

        $db_third_robot_history_task = new ThirdRobotHistoryTask();
        $where = [];
        $where['task_instance_id'] = $task_instance_id;
        $taskInfo = $db_third_robot_history_task->getOneData($where, 'id, task_id');
        if ($taskInfo && !is_array($taskInfo)) {
            $taskInfo = $taskInfo->toArray();
        }
        $id = isset($taskInfo['id']) && $taskInfo['id'] ? intval($taskInfo['id']) : 0;
        $task_id = isset($taskInfo['task_id']) && $taskInfo['task_id'] ? trim($taskInfo['task_id']) : 0;
        $thirdRobotDeviceService = new ThirdRobotDeviceService();
        if ($id || $task_id) {
            $params = [
                'record_id' => $record_id,
            ];
            $thirdRobotDeviceService->getRobotRemoteTaskDetails($task_instance_id, $id,$task_id, false, $params);
        } else if ($robot_code) {
            $params = [
                'isRobotCall' => true,
                'robot_code' => $robot_code,
                'record_id' => $record_id,
            ];
            $thirdRobotDeviceService->getRobotRemoteTaskDetails($task_instance_id,0, 0, false, $params);
        }
        return true;
    }

    /**
     * 用户的呼叫历史.
     */
    public function callHistory($param)
    {
        $uid = isset($param['uid']) && $param['uid'] ? intval($param['uid']) : 0;
        $page = isset($param['page']) && $param['page'] ? intval($param['page']) : 0;
        $robot_id = isset($param['robot_id']) && $param['robot_id'] ? intval($param['robot_id']) : 0;
        $name = isset($param['name']) && $param['name'] ? trim($param['name']) : '';
        $robot_code = isset($param['robot_code']) && $param['robot_code'] ? trim($param['robot_code']) : '';
        $isHandleUserShow = isset($param['isHandleUserShow']) && $param['isHandleUserShow'] ? $param['isHandleUserShow'] : false;
        $whereRecord = [];
        if ($uid) {
            $whereRecord[] = ['uid', '=', $uid];
        }
        if ($robot_code) {
            $whereRecord[] = ['robot_code', '=', $robot_code];
        }
        if ($robot_id) {
            $whereRecord[] = ['robot_id', '=', $robot_id];
        }
        if ($name) {
            $whereRecord[] = ['robot_name|map_name|point_name', 'like', '%'. $name . '%'];
        }
        $result = $this->getRobotCallRecordList($whereRecord, true, $page);
        if ($isHandleUserShow) {
            $arr = [];
            $arr['count'] = $result['count'];
            $arr['list'] = [];
            $siteUrl = cfg('site_url');
            $static = $siteUrl . '/v20/public/static/third_robot/robot_type/';
            $thirdRobotDeviceService = new ThirdRobotDeviceService();
            foreach ($result['list'] as $recordInfo) {
                $recordArr = [];
                $robot_info = isset($recordInfo['robot_info']) && $recordInfo['robot_info'] ? json_decode($recordInfo['robot_info'], true) : [];
                $robot_type = isset($robot_info['robot_type']) && $robot_info['robot_type'] ? trim($robot_info['robot_type']) : '';
                $robot_name = isset($robot_info['name']) && $robot_info['name'] ? trim($robot_info['name']) : trim($recordInfo['robot_name']);
                $recordArr['record_id'] = $recordInfo['record_id'];
                $recordArr['robot_name'] = $robot_name;
                $recordArr['robot_img'] = $thirdRobotDeviceService->getRobotImg($robot_type, $static);
                $call_time = isset($recordInfo['call_time']) && $recordInfo['call_time'] ? $recordInfo['call_time'] : $recordInfo['add_time'];
                $call_time_text = date('Y-m-d H:i:s', $call_time);
                $map_name = isset($recordInfo['map_name']) && $recordInfo['map_name'] ? $recordInfo['map_name'] : '';
                $point_name = isset($recordInfo['point_name']) && $recordInfo['point_name'] ? $recordInfo['point_name'] : '';
                $error_code = isset($recordInfo['error_code']) && $recordInfo['error_code'] ? $recordInfo['error_code'] : '';
                $error_message = isset($recordInfo['error_message']) && $recordInfo['error_message'] ? $recordInfo['error_message'] : '';
                $content = [
                    ['label' => '呼叫时间', 'value' => $call_time_text],
                ];
                if ($map_name) {
                    $content[] = ['label' => '呼叫地图', 'labelColor' => '#333', 'value' => $map_name, 'valueColor' => '#333'];
                }
                if ($point_name) {
                    $content[] = ['label' => '呼叫地点', 'labelColor' => '#333', 'value' => $point_name, 'valueColor' => '#333'];
                }
                if (! $error_code || ! $error_message) {
                    $content[] = ['label' => '呼叫结果', 'labelColor' => '#333', 'value' => '呼叫成功', 'valueColor' => 'green'];
                } else {
                    $content[] = ['label' => '呼叫结果', 'labelColor' => '#333', 'value' => '呼叫失败', 'valueColor' => 'red'];
                    $errMsg = $thirdRobotDeviceService->getCallRobotErrMsg($error_code, $error_message);
                    $content[] = ['label' => '失败原因', 'labelColor' => '#333', 'value' => $errMsg, 'valueColor' => '#d61a1a'];
                }
                $recordArr['content'] = $content;
                $arr['list'][] = $recordArr;
            }
        } else {
            $arr = $result;
        }
        return $arr;
    }

    /**
     * 获取呼叫历史列表.
     */
    public function getRobotCallRecordList($where = array(), $field = true, $page = 1, $page_size = 20, $order = 'add_time DESC')
    {
        $db_third_robot_call_record = new ThirdRobotCallRecord();
        $list = $db_third_robot_call_record->getList($where, $field, $order, $page, $page_size);
        if ($list && !is_array($list)) {
            $list = $list->toArray();
        }
        $count = $db_third_robot_call_record->getCount($where);
        $result = [];
        $result['list'] = $list;
        $result['count'] = $count;
        return $result;
    }

    /**
     * 呼叫任务详细.
     */
    public function callRecordDetail($param) {
        $uid = isset($param['uid']) && $param['uid'] ? intval($param['uid']) : 0;
        $record_id = isset($param['record_id']) && $param['record_id'] ? intval($param['record_id']) : 0;
        $dbThirdRobotCallRecord = new ThirdRobotCallRecord();
        $whereRecord = [];
        $whereRecord[] = ['uid', '=', $uid];
        $whereRecord[] = ['record_id', '=', $record_id];
        $recordInfo = $dbThirdRobotCallRecord->getOne($whereRecord);
        if ($recordInfo && !is_array($recordInfo)) {
            $recordInfo = $recordInfo->toArray();
        }
        if (empty($recordInfo)) {
            throw new \Exception('呼叫记录不存在');
        }

        $siteUrl = cfg('site_url');
        $static = $siteUrl . '/v20/public/static/third_robot/robot_type/';
        $thirdRobotDeviceService = new ThirdRobotDeviceService();
        $recordArr = [];
        $robot_info = isset($recordInfo['robot_info']) && $recordInfo['robot_info'] ? json_decode($recordInfo['robot_info'], true) : [];
        $robot_type = isset($robot_info['robot_type']) && $robot_info['robot_type'] ? trim($robot_info['robot_type']) : '';
        $robot_name = isset($robot_info['name']) && $robot_info['name'] ? trim($robot_info['name']) : trim($recordInfo['robot_name']);
        $recordArr['record_id'] = $recordInfo['record_id'];
        $recordArr['robot_name'] = $robot_name;
        $recordArr['robot_img'] = $thirdRobotDeviceService->getRobotImg($robot_type, $static);
        $call_time = isset($recordInfo['call_time']) && $recordInfo['call_time'] ? $recordInfo['call_time'] : $recordInfo['add_time'];
        $call_time_text = date('Y-m-d H:i:s', $call_time);
        $map_name = isset($recordInfo['map_name']) && $recordInfo['map_name'] ? $recordInfo['map_name'] : '';
        $point_name = isset($recordInfo['point_name']) && $recordInfo['point_name'] ? $recordInfo['point_name'] : '';
        $error_code = isset($recordInfo['error_code']) && $recordInfo['error_code'] ? $recordInfo['error_code'] : '';
        $error_message = isset($recordInfo['error_message']) && $recordInfo['error_message'] ? $recordInfo['error_message'] : '';
        $content = [
            ['label' => '呼叫时间', 'value' => $call_time_text],
        ];
        if ($map_name) {
            $content[] = ['label' => '呼叫地图', 'labelColor' => '#333', 'value' => $map_name, 'valueColor' => '#333'];
        }
        if ($point_name) {
            $content[] = ['label' => '呼叫地点', 'labelColor' => '#333', 'value' => $point_name, 'valueColor' => '#333'];
        }
        if (isset($recordInfo['call_time']) && $recordInfo['call_time']) {
            $content[] = ['label' => '呼叫时间', 'labelColor' => '#333', 'value' => date('Y-m-d H:i:s', $recordInfo['call_time']), 'valueColor' => '#333'];
        }
        if (! $error_code || ! $error_message) {
            $content[] = ['label' => '呼叫结果', 'labelColor' => '#333', 'value' => '呼叫成功', 'valueColor' => 'green'];
        } else {
            $content[] = ['label' => '呼叫结果', 'labelColor' => '#333', 'value' => '呼叫失败', 'valueColor' => 'red'];
            $errMsg = $thirdRobotDeviceService->getCallRobotErrMsg($error_code, $error_message);
            $content[] = ['label' => '失败原因', 'labelColor' => '#333', 'value' => $errMsg, 'valueColor' => '#d61a1a'];
        }
        $task_instance_id = isset($recordInfo['task_instance_id']) && $recordInfo['task_instance_id'] ? trim($recordInfo['task_instance_id']) : '';
        if ($task_instance_id) {
            $content[] = ['label' => '任务ID', 'labelColor' => '#333', 'value' => $task_instance_id, 'valueColor' => '#333', 'valueFontSize' => '24rpx'];
        }
        $recordArr['content'] = $content;

        $arr = [
            'recordInfo' => $recordArr,
        ];
        if ($task_instance_id) {
            $db_third_robot_history_task = new ThirdRobotHistoryTask();
            $where = [];
            $where['task_instance_id'] = $task_instance_id;
            $taskInfo = $db_third_robot_history_task->getOneData($where, 'id, task_id');
            if ($taskInfo && !is_array($taskInfo)) {
                $taskInfo = $taskInfo->toArray();
            }
            $id = isset($taskInfo['id']) && $taskInfo['id'] ? intval($taskInfo['id']) : 0;
            $task_id = isset($taskInfo['task_id']) && $taskInfo['task_id'] ? trim($taskInfo['task_id']) : 0;

            if ($id || $task_id) {
                $params = [
                    'record_id' => $record_id,
                ];
                $taskList = $thirdRobotDeviceService->getRobotRemoteTaskDetails($task_instance_id, $id,$task_id, false, $params);
            } else if (isset($recordInfo['robot_code']) && $recordInfo['robot_code']) {
                $params = [
                    'isRobotCall' => true,
                    'robot_code' => $recordInfo['robot_code'],
                    'record_id' => $record_id,
                ];
                $taskList = $thirdRobotDeviceService->getRobotRemoteTaskDetails($task_instance_id,0, 0,  false, $params);
            }
            if ($taskList && is_array($taskList) && count($taskList) > 1) {
                $taskList = $this->getArraySort($taskList, 'start_time');
            }
            $taskTimeLine = [];// 任务时间轴
            if (! empty($taskList)) {
                foreach ($taskList as $task) {
                    $timeLine = [
                        'point_name' => $task['point_name'],
                    ];
                    if (isset($task['targetPointFlag']) && intval($task['targetPointFlag']) === 1) {
                        $timeLine['flag'] = '目标点';
                    } else {
                        $timeLine['flag'] = '';
                    }
                    if (isset($task['status_str']) && $task['status_str']) {
                        $timeLine['status_str'] = $task['status_str'];
                        $timeLine['status'] = $task['status'];
                        $status = intval($task['status']);
                        if (in_array($status, [-1, 0])) {
                            $timeLine['status_type'] = 'info';
                        } elseif (in_array($status, [1])) {
                            $timeLine['status_type'] = 'success';
                        } elseif (in_array($status, [2, 3])) {
                            $timeLine['status_type'] = 'error';
                        } elseif (in_array($status, [5, 6])) {
                            $timeLine['status_type'] = 'warning';
                        } else {
                            $timeLine['status_type'] = 'primary';
                        }
                    } else {
                        $timeLine['status_str'] = '';
                    }
                    $content = [];
                    if (isset($task['actions']) && $task['actions']) {
                        $actions = $this->handleActions($task['actions']);
                        $content[] = ['label' => '执行动作', 'labelColor' => '#000', 'value' => $actions, 'valueType' => 'array', 'valueColor' => '#333'];
                    }
                    if (isset($task['map_name']) && $task['map_name']) {
                        $map_name = isset($task['map_name']) && $task['map_name'] ? trim($task['map_name']) : '';
                        $content[] = [
                            'label' => '地图', 'labelColor' => '#333', 'value' => $map_name, 'valueType' => 'string', 'valueColor' => '#333',
                        ];
                    }
                    if (isset($task['map_code']) && $task['map_code']) {
                        $map_code = isset($task['map_code']) && $task['map_code'] ? trim($task['map_code']) : '';
                        $content[] = [
                            'label' => '地图ID', 'labelColor' => '#333', 'value' => $map_code, 'valueType' => 'string', 'valueColor' => '#666',
                            'valueFontSize' => '24rpx',
                        ];
                    }
                    if (isset($task['map_floor']) && $task['map_floor']) {
                        $content[] = ['label' => '楼层', 'labelColor' => '#333', 'value' => trim($task['map_floor']), 'valueType' => 'string', 'valueColor' => '#333'];
                    }
                    if (isset($task['update_time_str']) && $task['update_time_str']) {
                        $content[] = ['label' => '更新时间', 'labelColor' => '#333', 'value' => trim($task['update_time_str']), 'valueType' => 'string', 'valueColor' => '#333'];
                    }
                    $timeLine['content'] = $content;
                    $taskTimeLine[] = $timeLine;
                }
            }
//            $arr['taskList'] = $taskList;
            $arr['taskTimeLine'] = $taskTimeLine;
        }
        return $arr;
    }

    public function handleActions($actions) {
        $actionArr = [];
        $thirdRobotDeviceService = new ThirdRobotDeviceService();
        foreach ($actions as $action) {
            $actionArr[] = $thirdRobotDeviceService->getTaskActionNameText($action['actionName'], $action);
        }
        return $actionArr;
    }

    /**
     * @param $list
     * @param $field
     * @param $sort_order
     * @return mixed
     */
    public function getArraySort($list, $field, $sort_order = SORT_ASC)
    {
        $orders = [];
        foreach ($list as $val) {
            $orders[] = $val[$field];
        }
        array_multisort($orders, $sort_order, $list);
        return $list;
    }

    /**
     * 走队列获取机器人运行信息[todo 推送等相关地方都走下这个].
     */
    public function toGetThirdRobotInfoJob($robotCode)
    {
        $params = [
            'jobType' => 'getThirdRobotInfo',
            'robotCode' => $robotCode,
        ];
        try {
            $this->traitCommonRobot($params, 1);
        } catch (\Exception $e) {
            fdump_api([$params, $e->getMessage()], 'thirdRobot/ErrToGetThirdRobotInfoJob');
        }
        return true;
    }

}