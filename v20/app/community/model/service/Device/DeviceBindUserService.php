<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      处理 设备和人员绑定相关记录（所有相关下发记录）
 */

namespace app\community\model\service\Device;


use app\community\model\db\Device\DeviceAccessState;
use app\community\model\db\Device\DeviceBindUser;
use app\community\model\db\FaceUserBindDevice;
use app\community\model\db\HouseFaceDevice;
use app\community\model\service\HouseVillageUserBindService;
use app\consts\DeviceConst;
use app\consts\HikConst;

class DeviceBindUserService
{
    protected $dbDeviceBindUser, $dbDeviceAccessState, $dbHouseFaceDevice, $nowTime, $dbFaceUserBindDevice;
    public function handleCommunityMessageAccessState(array $param = []) {
        $thirdProtocol = isset($param['thirdProtocol']) && $param['thirdProtocol'] ? $param['thirdProtocol'] : '';
        if (!$thirdProtocol) {
            return false;
        }
        switch ($thirdProtocol) {
            case DeviceConst::HIK_YUNMO_NEIBU_SHEQU:
                $this->handleHikMessageAccessState();
                break;
        }
        return false;
    }
    
    public function handleHikMessageAccessState() {
        if (!$this->dbDeviceAccessState) {
            $this->dbDeviceAccessState = new DeviceAccessState();
        }
        if (!$this->dbDeviceBindUser) {
            $this->dbDeviceBindUser = new DeviceBindUser();
        }
        if (!$this->dbHouseFaceDevice) {
            $this->dbHouseFaceDevice = new HouseFaceDevice();
        }
        $this->nowTime = time();
        $where = [];
        $where[] = ['handle_status', 'in', [0, 3]];
        $stateList = $this->dbDeviceAccessState->getSome($where, true, 'issued_time ASC',1,100);
        if ($stateList && !is_array($stateList)) {
            $stateList = $stateList->toArray();
        }
        $faceDeviceArr = [];
        foreach ($stateList as $state) {
            switch ($state['message_code']) {
                case HikConst::HIK_EVENT_COMMUNITY_MESSAGE_ACCESS_STATE_AUTH_FACE:
                case HikConst::HIK_EVENT_COMMUNITY_MESSAGE_ACCESS_STATE_AUTH_CARD:
                case HikConst::HIK_EVENT_COMMUNITY_MESSAGE_ACCESS_STATE_AUTH_ACTIVE_PASS:
                    try {
                        $this->stateFace($state, $faceDeviceArr);
                    } catch (\Exception $e) {
                        //fdump_sql(['msg'=>$e->getMessage()],'stateFace');
                    }
                    break;
                case HikConst::HIK_EVENT_COMMUNITY_MESSAGE_ACCESS_STATE_AUTH_PERSON_ALL:
                    try {
                        $this->statePersonAll($state, $faceDeviceArr);
                    } catch (\Exception $e) {
                        //fdump_sql(['msg'=>$e->getMessage()],'statePersonAll');
                    }
                    break;
                case HikConst::HIK_EVENT_COMMUNITY_MESSAGE_ACCESS_STATE_SUCCESS_PERSON_DELETE:
                case HikConst::HIK_EVENT_COMMUNITY_MESSAGE_ACCESS_STATE_AUTH_FACE_DELETE:
                    try {
                        $this->statePersonDelete($state, $faceDeviceArr);
                    } catch (\Exception $e) {
                        //fdump_sql(['msg'=>$e->getMessage()],'statePersonDelete');
                    }
                    break;
            }
            $whereDel = [];
            $whereDel[] = ['id', '=', $state['id']];
            $this->dbDeviceAccessState->deleteInfo($whereDel);
        }
    }

    /**
     * 人脸权限下发 处理记录关联人员
     * @param integer $state
     * @param array   $faceDeviceArr
     * @return bool
     */
    public function stateFace($state, &$faceDeviceArr = []) {
        if (!$state) {
            return false;
        }
        if (!$this->dbDeviceAccessState) {
            $this->dbDeviceAccessState = new DeviceAccessState();
        }
        if (!$this->dbHouseFaceDevice) {
            $this->dbHouseFaceDevice = new HouseFaceDevice();
        }
        if (!$this->dbDeviceBindUser) {
            $this->dbDeviceBindUser = new DeviceBindUser();
        }
        if (!$this->dbFaceUserBindDevice) {
            $this->dbFaceUserBindDevice = new FaceUserBindDevice();
        }
        if (!$this->nowTime) {
            $this->nowTime = time();
        }
        if ($state['message_code'] == HikConst::HIK_EVENT_COMMUNITY_MESSAGE_ACCESS_STATE_AUTH_CARD) {
            $data = [
                'type'     => DeviceConst::DEVICE_AUTH_TYPE_CARD,
                'add_time' => $this->nowTime,
            ];
        } elseif ($state['message_code'] == HikConst::HIK_EVENT_COMMUNITY_MESSAGE_ACCESS_STATE_AUTH_ACTIVE_PASS) {
            $data = [
                'type'     => DeviceConst::DEVICE_AUTH_TYPE_PASSWORD,
                'add_time' => $this->nowTime,
            ];
        } else {
            $data = [
                'type'     => DeviceConst::DEVICE_AUTH_TYPE_FACE,
                'add_time' => $this->nowTime,
            ];
        }
        $whereUser   = [];
        $whereRepeat = [];
        $whereRepeat[] = ['type', '=', DeviceConst::DEVICE_AUTH_TYPE_FACE];
        $device_id = isset($state['device_id']) && $state['device_id'] ? $state['device_id'] : '';
        $filterDeviceInfo = $this->filterDevice($device_id, $faceDeviceArr);
        $faceDevice    = isset($filterDeviceInfo['faceDevice'])    && $filterDeviceInfo['faceDevice']    ? $filterDeviceInfo['faceDevice']    : [];
        $faceDeviceArr = isset($filterDeviceInfo['faceDeviceArr']) && $filterDeviceInfo['faceDeviceArr'] ? $filterDeviceInfo['faceDeviceArr'] : [];
        
        $village_id = isset($state['village_id']) && $state['village_id'] ? $state['village_id'] : '';
        $person_id  = isset($state['person_id'])  && $state['person_id']  ? $state['person_id']  : '';
        $uid        = 0;
        if ($person_id) {
            $whereAbout = [];
            $whereAbout[] = ['bind_type', '=', DeviceConst::HK_UID_CLOUD_USER];
            $whereAbout[] = ['person_id', '=', $person_id];
            $aboutInfo = $this->dbFaceUserBindDevice->getOneOrder($whereAbout, 'bind_id');
            if ($aboutInfo && !is_array($aboutInfo)) {
                $aboutInfo = $aboutInfo->toArray();
            }
            $data['cloud_uid'] = $person_id;
            $whereRepeat[] = ['cloud_uid', '=', $person_id];
        }
        if (isset($aboutInfo) && isset($aboutInfo['bind_id']) && $aboutInfo['bind_id']) {
            $data['uid'] = $aboutInfo['bind_id'];
            $uid = $data['uid'];
            $whereRepeat[] = ['uid', '=', $aboutInfo['bind_id']];
            $whereUser[] = ['uid', '=', $aboutInfo['bind_id']];
        }
        
        if (isset($faceDevice['device_id']) && $faceDevice['device_id']) {
            $data['device_id'] = $faceDevice['device_id'];
            $whereRepeat[] = ['device_id', '=', $faceDevice['device_id']];
        }
        if (isset($faceDevice['device_sn']) && $faceDevice['device_sn']) {
            $data['device_sn'] = $faceDevice['device_sn'];
            $whereRepeat[] = ['device_sn', '=', $faceDevice['device_sn']];
        }
        if (isset($faceDevice['cloud_device_id']) && $faceDevice['cloud_device_id']) {
            $data['cloud_device_id'] = $faceDevice['cloud_device_id'];
            $whereRepeat[] = ['cloud_device_id', '=', $faceDevice['cloud_device_id']];
        }
        if (!$village_id && isset($faceDevice['village_id']) && $faceDevice['village_id']) {
            $village_id = $faceDevice['village_id'];
        }
        if ($village_id) {
            $data['village_id'] = $village_id;
            $whereRepeat[] = ['village_id', '=', $village_id];
        }
        if (isset($state['issued_time']) && $state['issued_time']) {
            $data['issued_time'] = $state['issued_time'];
            $whereRepeat[] = ['issued_time', '=', $state['issued_time']];
        }
        if (isset($state['card_number']) && $state['card_number']) {
            $data['card_number'] = $state['card_number'];
            $whereRepeat[] = ['card_number', '=', $state['card_number']];
        }
        if (isset($state['reason']) && $state['reason']) {
            $data['reason'] = $state['reason'];
        }
        if (isset($state['describe']) && $state['describe']) {
            $data['describe'] = $state['describe'];
        }
        if (isset($state['access_state']) && $state['access_state']) {
            $status = $this->filterAccessState($state['access_state']);
            $data['status'] = $status;
            $whereRepeat[] = ['status', '=', $status];
        }
        
        $repeat = $this->dbDeviceBindUser->getOne($whereRepeat);
        if ($repeat && !is_array($repeat)) {
            $repeat = $repeat->toArray();
        }
        $deleteTrue = false;
        if (empty($repeat)) {
            $log_id = $this->dbDeviceBindUser->add($data);
            if ($log_id ) {
                $deleteTrue = true;
            }
        } else {
            $deleteTrue = true;
        }
        if ($village_id && $uid && isset($status) && $status!=3) {
            // 1成功  2失败 3下发中 4 同步删除成功  5同步删除失败
            $describe = isset($data['describe']) ? $data['describe'] : '';
            $reason   = isset($data['reason'])   ? $data['reason'] : '';
            $face_img_reason = $describe ? $describe : $reason;
            if ($status == 4) {
                //$face_img_status = 0;
                //$face_img_reason = '';
            } elseif ($status == 2) {
                $face_img_status = 2;
                $houseVillageUserBindService = new HouseVillageUserBindService();
                $updateBindParam = [
                    'face_img_status' => $face_img_status,
                    'face_img_reason' => $face_img_reason,
                ];
                $whereUserBind = [];
                $whereUserBind[] = ['village_id', '=', $village_id];
                $whereUserBind[] = ['uid', '=', $uid];
                $houseVillageUserBindService->saveUserBind($whereUserBind, $updateBindParam);
                
            }elseif ($status==1){
                $houseVillageUserBindService = new HouseVillageUserBindService();
                $updateBindParam = [
                    'face_img_status' => 1,
                    'face_img_reason' => '',
                ];
                $whereUserBind = [];
                $whereUserBind[] = ['village_id', '=', $village_id];
                $whereUserBind[] = ['uid',        '=', $uid];
                $whereUserBind[] = ['status',     '<>', 1];
                $houseVillageUserBindService->saveUserBind($whereUserBind, $updateBindParam);
            }
        }
        if (isset($deleteTrue) && $deleteTrue && isset($state['id']) && $state['id']) {
            $whereDel = [];
            $whereDel[] = ['id', '=', $state['id']];
            $this->dbDeviceAccessState->deleteInfo($whereDel);
        }
        $this->deleteOverLimit($whereUser);
        return true;
    }

    /**
     * 人员权限下发的全量状态 处理记录关联人员
     * @param $state
     * @param array $faceDeviceArr
     * @return bool
     */
    public function statePersonAll($state, &$faceDeviceArr = []) {
        if (!$state) {
            return false;
        }
        if (!$this->dbDeviceAccessState) {
            $this->dbDeviceAccessState = new DeviceAccessState();
        }
        if (!$this->dbHouseFaceDevice) {
            $this->dbHouseFaceDevice = new HouseFaceDevice();
        }
        if (!$this->dbDeviceBindUser) {
            $this->dbDeviceBindUser = new DeviceBindUser();
        }
        if (!$this->dbFaceUserBindDevice) {
            $this->dbFaceUserBindDevice = new FaceUserBindDevice();
        }
        if (!$this->nowTime) {
            $this->nowTime = time();
        }
        if (!isset($state['results']) || !$state['results']) {
            return false;
        }
        $device_id  = isset($state['device_id']) && $state['device_id'] ? $state['device_id'] : '';
        $filterDeviceInfo = $this->filterDevice($device_id, $faceDeviceArr);
        $faceDevice    = isset($filterDeviceInfo['faceDevice'])    && $filterDeviceInfo['faceDevice']    ? $filterDeviceInfo['faceDevice']    : [];
        $faceDeviceArr = isset($filterDeviceInfo['faceDeviceArr']) && $filterDeviceInfo['faceDeviceArr'] ? $filterDeviceInfo['faceDeviceArr'] : [];
        $village_id = isset($state['village_id']) && $state['village_id'] ? $state['village_id'] : '';
        $person_id  = isset($state['person_id'])  && $state['person_id']  ? $state['person_id']  : '';
        if (!$village_id && isset($faceDevice['village_id']) && $faceDevice['village_id']) {
            $village_id = $faceDevice['village_id'];
        }
        $dataParam = [
            'add_time' => $this->nowTime,
        ];
        $whereUser   = [];
        $whereRepeat = [];
        if ($person_id) {
            $whereAbout = [];
            $whereAbout[] = ['bind_type', '=', DeviceConst::HK_UID_CLOUD_USER];
            $whereAbout[] = ['person_id', '=', $person_id];
            $aboutInfo = $this->dbFaceUserBindDevice->getOneOrder($whereAbout, 'bind_id');
            if ($aboutInfo && !is_array($aboutInfo)) {
                $aboutInfo = $aboutInfo->toArray();
            }
            $dataParam['cloud_uid'] = $person_id;
            $whereRepeat[] = ['cloud_uid', '=', $person_id];
        }
        if (isset($aboutInfo) && isset($aboutInfo['bind_id']) && $aboutInfo['bind_id']) {
            $dataParam['uid'] = $aboutInfo['bind_id'];
            $whereRepeat[] = ['uid', '=', $aboutInfo['bind_id']];
            $whereUser[] = ['uid', '=', $aboutInfo['bind_id']];
        }

        if (isset($faceDevice['device_id']) && $faceDevice['device_id']) {
            $dataParam['device_id'] = $faceDevice['device_id'];
            $whereRepeat[] = ['device_id', '=', $faceDevice['device_id']];
        }
        if (isset($faceDevice['device_sn']) && $faceDevice['device_sn']) {
            $dataParam['device_sn'] = $faceDevice['device_sn'];
            $whereRepeat[] = ['device_sn', '=', $faceDevice['device_sn']];
        }
        if (isset($faceDevice['cloud_device_id']) && $faceDevice['cloud_device_id']) {
            $dataParam['cloud_device_id'] = $faceDevice['cloud_device_id'];
            $whereRepeat[] = ['cloud_device_id', '=', $faceDevice['cloud_device_id']];
        }
        if (!$village_id && isset($faceDevice['village_id']) && $faceDevice['village_id']) {
            $village_id = $faceDevice['village_id'];
        }
        if ($village_id) {
            $dataParam['village_id'] = $village_id;
            $whereRepeat[] = ['village_id', '=', $village_id];
        }
        if (isset($state['issued_time']) && $state['issued_time']) {
            $dataParam['issued_time'] = $state['issued_time'];
            $whereRepeat[] = ['issued_time', '=', $state['issued_time']];
        }
        $results = json_decode($state['results'], true);
        $deleteTrue = false;
        foreach ($results as $auth) {
            $data = $dataParam;
            if (isset($auth['authStatus']) && $auth['authStatus']) {
                if ($auth['authStatus'] == 'notDown') {
                    // 未下发 不做记录
                    continue;
                }
                $status = $this->filterAccessState($auth['authStatus']);
                $data['status'] = $status;
                $whereRepeat[] = ['status', '=', $status];
            }
            if (isset($auth['authType']) && $auth['authType'] == 'person') {
                $data['type'] = DeviceConst::DEVICE_AUTH_TYPE_PERSON;
            } elseif (isset($auth['authType']) && $auth['authType'] == 'card') {
                $data['type'] = DeviceConst::DEVICE_AUTH_TYPE_CARD;
            } elseif (isset($auth['authType']) && $auth['authType'] == 'face') {
                $data['type'] = DeviceConst::DEVICE_AUTH_TYPE_FACE;
            } elseif (isset($auth['authType']) && $auth['authType'] == 'finger') {
                $data['type'] = DeviceConst::DEVICE_AUTH_TYPE_FINGER;
            } else {
                continue;
            }
            if (isset($auth['reason']) && $auth['reason']) {
                $data['reason'] = $auth['reason'];
            }
            if (isset($auth['describe']) && $auth['describe']) {
                $data['describe'] = $auth['describe'];
            }
            if ( isset($auth['detailNo']) && $auth['detailNo'] && $auth['authType'] == 'card') {
                $data['card_number'] = $auth['detailNo'];
            }
            if ( isset($auth['detailNo']) && $auth['detailNo'] && $auth['authType'] == 'finger') {
                $data['finger_code'] = $auth['detailNo'];
            }
            $repeat = $this->dbDeviceBindUser->getOne($whereRepeat);
            if ($repeat && !is_array($repeat)) {
                $repeat = $repeat->toArray();
            }
            if (empty($repeat)) {
                $log_id = $this->dbDeviceBindUser->add($data);
                if ($log_id ) {
                    $deleteTrue = true;
                }
            } else {
                $deleteTrue = true;
            }
        }
        if (isset($deleteTrue) && $deleteTrue && isset($state['id']) && $state['id']) {
            $whereDel = [];
            $whereDel[] = ['id', '=', $state['id']];
            $this->dbDeviceAccessState->deleteInfo($whereDel);
        }
        $this->deleteOverLimit($whereUser);
        return true;
    }

    /**
     * 人员权限删除成功 处理记录关联人员
     * @param $state
     * @param array $faceDeviceArr
     * @return bool
     */
    public function statePersonDelete($state, &$faceDeviceArr = []) {
        if (!$state) {
            return false;
        }
        if (!$this->dbDeviceAccessState) {
            $this->dbDeviceAccessState = new DeviceAccessState();
        }
        if (!$this->dbHouseFaceDevice) {
            $this->dbHouseFaceDevice = new HouseFaceDevice();
        }
        if (!$this->dbDeviceBindUser) {
            $this->dbDeviceBindUser = new DeviceBindUser();
        }
        if (!$this->dbFaceUserBindDevice) {
            $this->dbFaceUserBindDevice = new FaceUserBindDevice();
        }
        if (!$this->nowTime) {
            $this->nowTime = time();
        }
        $device_id  = isset($state['device_id']) && $state['device_id'] ? $state['device_id'] : '';
        $filterDeviceInfo = $this->filterDevice($device_id, $faceDeviceArr);
        $faceDevice    = isset($filterDeviceInfo['faceDevice'])    && $filterDeviceInfo['faceDevice']    ? $filterDeviceInfo['faceDevice']    : [];
        $faceDeviceArr = isset($filterDeviceInfo['faceDeviceArr']) && $filterDeviceInfo['faceDeviceArr'] ? $filterDeviceInfo['faceDeviceArr'] : [];
        $village_id = isset($state['village_id']) && $state['village_id'] ? $state['village_id'] : '';
        $person_id  = isset($state['person_id'])  && $state['person_id']  ? $state['person_id']  : '';
        if (!$village_id && isset($faceDevice['village_id']) && $faceDevice['village_id']) {
            $village_id = $faceDevice['village_id'];
        }
        if ($state['message_code'] == HikConst::HIK_EVENT_COMMUNITY_MESSAGE_ACCESS_STATE_AUTH_FACE_DELETE) {
            $dataParam = [
                'type'     => DeviceConst::DEVICE_AUTH_TYPE_FACE,
                'add_time' => $this->nowTime,
            ];
        } else {
            $dataParam = [
                'type' => DeviceConst::DEVICE_AUTH_TYPE_PERSON,
                'add_time' => $this->nowTime,
            ];
        }
        $whereUser   = [];
        $whereRepeat = [];
        if ($person_id) {
            $whereAbout = [];
            $whereAbout[] = ['bind_type', '=', DeviceConst::HK_UID_CLOUD_USER];
            $whereAbout[] = ['person_id', '=', $person_id];
            $aboutInfo = $this->dbFaceUserBindDevice->getOneOrder($whereAbout, 'bind_id');
            if ($aboutInfo && !is_array($aboutInfo)) {
                $aboutInfo = $aboutInfo->toArray();
            }
            $dataParam['cloud_uid'] = $person_id;
            $whereRepeat[] = ['cloud_uid', '=', $person_id];
        }
        if (isset($aboutInfo) && isset($aboutInfo['bind_id']) && $aboutInfo['bind_id']) {
            $dataParam['uid'] = $aboutInfo['bind_id'];
            $whereRepeat[] = ['uid', '=', $aboutInfo['bind_id']];
            $whereUser[] = ['uid', '=', $aboutInfo['bind_id']];
        }

        if (isset($faceDevice['device_id']) && $faceDevice['device_id']) {
            $dataParam['device_id'] = $faceDevice['device_id'];
            $whereRepeat[] = ['device_id', '=', $faceDevice['device_id']];
        }
        if (isset($faceDevice['device_sn']) && $faceDevice['device_sn']) {
            $dataParam['device_sn'] = $faceDevice['device_sn'];
            $whereRepeat[] = ['device_sn', '=', $faceDevice['device_sn']];
        }
        if (isset($faceDevice['cloud_device_id']) && $faceDevice['cloud_device_id']) {
            $dataParam['cloud_device_id'] = $faceDevice['cloud_device_id'];
            $whereRepeat[] = ['cloud_device_id', '=', $faceDevice['cloud_device_id']];
        }
        if (!$village_id && isset($faceDevice['village_id']) && $faceDevice['village_id']) {
            $village_id = $faceDevice['village_id'];
        }
        if ($village_id) {
            $dataParam['village_id'] = $village_id;
            $whereRepeat[] = ['village_id', '=', $village_id];
        }
        if (isset($state['issued_time']) && $state['issued_time']) {
            $dataParam['issued_time'] = $state['issued_time'];
            $whereRepeat[] = ['issued_time', '=', $state['issued_time']];
        }
        if (isset($state['card_number']) && $state['card_number']) {
            $dataParam['card_number'] = $state['card_number'];
            $whereRepeat[] = ['card_number', '=', $state['card_number']];
        }
        if (isset($state['reason']) && $state['reason']) {
            $dataParam['reason'] = $state['reason'];
        }
        if (isset($state['describe']) && $state['describe']) {
            $dataParam['describe'] = $state['describe'];
        }
        if (isset($state['access_state']) && $state['access_state']) {
            $status = $this->filterAccessState($state['access_state'], true);
            $dataParam['status'] = $status;
            $whereRepeat[] = ['status', '=', $status];
        }

        $repeat = $this->dbDeviceBindUser->getOne($whereRepeat);
        if ($repeat && !is_array($repeat)) {
            $repeat = $repeat->toArray();
        }
        $deleteTrue = false;
        if (empty($repeat)) {
            $log_id = $this->dbDeviceBindUser->add($dataParam);
            if ($log_id ) {
                $deleteTrue = true;
            }
        } else {
            $deleteTrue = true;
        }
        if (isset($deleteTrue) && $deleteTrue && isset($state['id']) && $state['id']) {
            $whereDel = [];
            $whereDel[] = ['id', '=', $state['id']];
            $this->dbDeviceAccessState->deleteInfo($whereDel);
        }
        $this->deleteOverLimit($whereUser);
        return true;
    }
    
    /**
     * 删除超出限制条数的记录
     * @param $whereUser
     * @return bool
     */
    protected function deleteOverLimit($whereUser) {
        if (!$this->dbDeviceBindUser) {
            $this->dbDeviceBindUser = new DeviceBindUser();
        }
        if (!empty($whereUser)) {
            $limit_user_auth_record = DeviceConst::LIMIT_USER_AUTH_RECORD;
            $userCount = $this->dbDeviceBindUser->getCount($whereUser);
            if ($userCount > $limit_user_auth_record) {
                // 需要删除对应数量的记录
                $deleteNum = $userCount - $limit_user_auth_record;
                $idArr = $this->dbDeviceBindUser->getColumn($whereUser, 'log_id', '', $deleteNum, 'issued_time ASC, log_id ASC');
                if (!empty($idArr)) {
                    $whereDelete = $whereUser;
                    $whereDelete[] = ['log_id', 'in', $idArr];
                    $this->dbDeviceBindUser->deleteInfo($whereDelete);
                }
            }
        }
        return true;
    }

    /**
     * 查询设备
     * @param $device_id
     * @param array $faceDeviceArr
     * @return array
     */
    protected function filterDevice($device_id, $faceDeviceArr = []) {
        if (!$this->dbHouseFaceDevice) {
            $this->dbHouseFaceDevice = new HouseFaceDevice();
        }
        if ($device_id && isset($faceDeviceArr[$device_id])) {
            $faceDevice = $faceDeviceArr[$device_id];
        } elseif ($device_id) {
            $whereFace = [];
            $whereFace[] = ['cloud_device_id', '=', $device_id];
            $whereFace[] = ['is_del',          '=', 0];
            $faceField = 'device_id, device_sn, cloud_device_id, village_id';
            $faceDevice = $this->dbHouseFaceDevice->getOne($whereFace, $faceField);
            if ($faceDevice && !is_array($faceDevice)) {
                $faceDevice = $faceDevice->toArray();
            }
            if (!empty($faceDevice)) {
                $faceDeviceArr[$device_id] = $faceDevice;
            }
        } else {
            $faceDevice = [];
        }
        return [
            'faceDevice'    => $faceDevice,
            'faceDeviceArr' => $faceDeviceArr,
        ];
    }
    
    // 状态 1成功  2失败 3下发中 4同步删除成功  5同步删除失败
    protected function filterAccessState($access_state, $isDel = false) {
        $status = DeviceConst::STATE_SUCCESS;
        switch ($access_state) {
            case 'SUCCESS':
            case 'success':
                if ($isDel) {
                    $status = DeviceConst::STATE_DELETE_SUCCESS;
                } else {
                    $status = DeviceConst::STATE_SUCCESS;
                }
                break;
            case 'FAILED':
            case 'failed':
                if ($isDel) {
                    $status = DeviceConst::STATE_DELETE_FAILED;
                } else {
                    $status = DeviceConst::STATE_FAILED;
                }
                break;
            case 'PROCESS':
            case 'process':
                $status = DeviceConst::STATE_PROCESS;
                break;
            case 'DELETEFAILED':
            case 'deleteFailed':
                $status = DeviceConst::STATE_DELETE_FAILED;
                break;
            case 'notDown':
                $status = DeviceConst::STATE_NOT_DOWN;
                break;
        }
        return $status;
    }
}