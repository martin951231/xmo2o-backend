<?php


namespace app\community\model\service\Device;

use app\common\model\db\AppapiAppLoginLog;
use app\common\model\service\ConfigDataService;
use app\common\model\service\weixin\TemplateNewsService;
use app\community\model\db\Device\CommunityEventAlarm;
use app\community\model\db\Device\DeviceAccessState;
use app\community\model\db\Device\DeviceBindUser;
use app\community\model\db\FaceBindAboutInfo;
use app\community\model\db\FaceUserBindDevice;
use app\community\model\db\HouseCameraTip;
use app\community\model\db\HouseDeviceChannel;
use app\community\model\db\HouseFaceDevice;
use app\community\model\db\HouseCameraDevice; //人脸识别摄像机
use app\community\model\db\HouseUserLog;
use app\community\model\db\HouseVillageFloor;
use app\community\model\db\HouseVillageLayer;
use app\community\model\db\HouseVillagePassBindMember;
use app\community\model\db\HouseVillagePublicArea;
use app\community\model\db\HouseVillageSingle;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\User;
use app\community\model\db\UserSet;
use app\community\model\service\FaceDeviceService;
use app\community\model\service\HouseFaceDeviceService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\HouseVillageUserBindService;
use app\community\model\service\OwnerService;
use app\community\model\service\VisitorInviteService;
use app\consts\DahuaConst;
use app\consts\DeviceConst;
use app\consts\EventAlarmConst;
use app\consts\HikConst;
use app\traits\device\HandleHouseUserLogTraits;
use app\traits\house\DeviceTraits;
use app\traits\house\DeviceUserTraits;
use app\traits\FaceDeviceHikCloudTraits;
use app\traits\house\HouseTraits;
use file_handle\FileHandle;
use think\facade\Cache;

class DeviceHkNeiBuHandleService
{
    use DeviceTraits,DeviceUserTraits,FaceDeviceHikCloudTraits,HouseTraits,HandleHouseUserLogTraits;

    /** @var int 当前时间 */
    public $nowTime = 0;
    public $sFaceHikCloudNeiBuDeviceService;

    public function addSinglePersonToCloud(array $param)
    {
        try {
            return $this->addSinglePersonToCloudBox($param);
        } catch (\Exception $e){
            fdump_api(['执行错误'=>$e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'param' => $param], 'v20Face/addSinglePersonToCloud',1);
            return false;
        }
    }
    /**
     * 同步人员信息
     * @param array $param
     * @return array
     * @throws \think\Exception
     */
    public function addSinglePersonToCloudBox(array $param) {
        $this->clearRecordDeviceBindFilter(); // 清除下报错相关
        $param = $this->filterCommonParam($param);
        if (!$this->village_user_bind_id) {
            $this->record_bind_type  = DeviceConst::BIND_FACE_BIND_USER_BIND_CARD;
            $this->record_bind_id    = $this->village_user_bind_id;
            $this->syn_status        = DeviceConst::BIND_USER_ALL_SYN_ERR;
            $this->syn_status_txt    = '住户同步失败(无同步对象)';
            $this->err_reason        = "同步对象ID不存在";
            $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param];
            $this->recordDeviceBindFilterBox($param);
            return $this->backData($param, '同步对象ID不存在', 1041);
        }
        fdump_api(['param' => $param,'pigcms_id' => $param['pigcms_id'], 'msg' => '同步人员信息'], 'v20Face/addSinglePersonToCloud',1);
        try {
            fdump_api(['line' => __LINE__], 'v20Face/addSinglePersonToCloud',1);
            $userParam = $this->filterVillageBindUser($param);
            fdump_api(['userParam' => $userParam,'line' => __LINE__], 'v20Face/addSinglePersonToCloud',1);
        } catch (\Exception $e){
            fdump_api(['ermmsg' => $e->getMessage(),'line' => __LINE__], 'v20Face/addSinglePersonToCloud',1);
            $userParam = [];
        }
        if(empty($userParam)) {
            $this->record_bind_type  = DeviceConst::BIND_FACE_BIND_USER_BIND_CARD;
            $this->record_bind_id    = $this->village_user_bind_id;
            $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param];
            $this->recordDeviceBindFilterBox($param);
            if ($this->village_user_bind_id) {
                $houseVillageUserBindService = new HouseVillageUserBindService();
                $updateBindParam = [
                    'face_img_status' => 2,
                    'face_img_reason' => "同步对象不存在或者状态异常",
                ];
                $houseVillageUserBindService->saveUserBind(['pigcms_id' => $this->village_user_bind_id], $updateBindParam);
            }
            if($param['log_id']){
                (new OwnerService())->updateLog(['log_id'=>$param['log_id']],[
                   'status'=>2, 
                   'msg'=>"同步对象不存在或者状态异常", 
                    'last_time'=>time()
                ]);
            }
            fdump_api(['param' => $param, 'msg' => '同步对象不存在或者状态异常'], 'v20Face/addSinglePersonToCloud',1);
            return $this->backData($param, '同步对象不存在或者状态异常', 1041);
        }
        $userBind    = isset($userParam['userBind'])    && $userParam['userBind']    ? $userParam['userBind']    : [];
        $personParam = isset($userParam['personParam']) && $userParam['personParam'] ? $userParam['personParam'] : [];
        if (empty($personParam)) {
            fdump_api(['personParam' => $personParam,'pigcms_id' => $param['pigcms_id'], 'msg' => '同步的住户缺少手机号或者身份证信息'], 'v20Face/addSinglePersonToCloud',1);
            return $this->backData($param, '同步的住户缺少手机号或者身份证信息', 1041);
        }
        $uid   =  isset($userBind['uid'])     && $userBind['uid']     ? $userBind['uid']     : $this->user_id;
        $type  =  isset($userBind['type'])    && $userBind['type']    ? $userBind['type']    : 0;
        
        $judge = ($type == 4);

        if (isset($userBind['village_id']) && $userBind['village_id']) {
            $this->village_id = $userBind['village_id'];
        }
        if (isset($userBind['single_id']) && $userBind['single_id']) {
            $this->single_id = $userBind['single_id'];
        }
        if (isset($userBind['floor_id']) && $userBind['floor_id']) {
            $this->floor_id = $userBind['floor_id'];
        }
        if (!$this->room_id && isset($userBind['vacancy_id']) && $userBind['vacancy_id']) {
            $this->room_id = $userBind['vacancy_id'];
        }
        if (!$this->village_third_id) {
            // 如果没有  小区 同步到第三方的云对应id
            $villageAboutInfo = $this->filterVillageToData($this->village_id, $this->thirdProtocol);
        }
        if (!$this->village_third_id) {
            $this->record_bind_type  = DeviceConst::BIND_FACE_BIND_USER_BIND_CARD;
            $this->record_bind_id    = $this->village_user_bind_id;
            $this->syn_status        = DeviceConst::BIND_USER_ALL_SYN_ERR;
            fdump_api(['param' => $param, 'msg' => '归属小区id不存在'], 'v20Face/addSinglePersonToCloud',1);
            return $this->filterNonExistentVillage($param);
        }
        if (!$this->build_third_id && $this->single_id) {
            // 如果没有 楼栋 同步到第三方的云对应编号
            $singleAboutInfo = $this->filterBuildToData($this->single_id);
        }
        if (!$this->build_third_id && !$judge) {
            $this->record_bind_type    = DeviceConst::BIND_FACE_BIND_USER_BIND_CARD;
            $this->third_three_bind_id = $this->village_third_id;
            $this->record_bind_id      = $this->village_user_bind_id;
            $this->syn_status          = DeviceConst::BIND_USER_ALL_SYN_ERR;
            fdump_api(['param' => $param, 'msg' => '归属楼栋id不存在'], 'v20Face/addSinglePersonToCloud',1);
            return $this->filterNonExistentSingle($param);
        }
        if (!$this->unit_third_id && $this->floor_id) {
            // 如果没有 单元 同步到第三方的云对应编号
            $floorAboutInfo = $this->filterUnitToData($this->floor_id);
        }
        if (!$this->unit_third_id && !$judge) {
            $this->record_bind_type     = DeviceConst::BIND_FACE_BIND_USER_BIND_CARD;
            $this->record_bind_id       = $this->village_user_bind_id;
            $this->third_second_bind_id = $this->build_third_id;
            $this->third_three_bind_id  = $this->village_third_id;
            $this->syn_status           = DeviceConst::BIND_USER_ALL_SYN_ERR;
            fdump_api(['param' => $param, 'msg' => '归属单元id不存在'], 'v20Face/addSinglePersonToCloud',1);
            return $this->filterNonExistentFloor($param);
        }
        if (!$this->room_third_id && $this->room_id) {
            // 如果没有 房屋 同步到第三方的云对应编号
            $roomAboutInfo = $this->filterRoomToData($this->room_id);
        }
        if (!$this->room_third_id && !$judge) {
            $this->record_bind_type     = DeviceConst::BIND_FACE_BIND_USER_BIND_CARD;
            $this->record_bind_id       = $this->village_user_bind_id;
            $this->third_bind_id        = $this->unit_third_id;
            $this->third_second_bind_id = $this->build_third_id;
            $this->third_three_bind_id  = $this->village_third_id;
            $this->syn_status           = DeviceConst::BIND_USER_ALL_SYN_ERR;
            fdump_api(['param' => $param, 'msg' => '归属房屋id不存在'], 'v20Face/addSinglePersonToCloud',1);
            return $this->filterNonExistentRoom($param);
        }
        
        if ($uid) {
            $aboutInfo = $this->filterUserToDataFromUid($uid, DeviceConst::HK_UID_CLOUD_USER);
            $person_json = isset($aboutInfo['person_json']) && $aboutInfo['person_json'] ? $aboutInfo['person_json'] : '';
            $user_third_id = isset($aboutInfo['person_id']) && $aboutInfo['person_id'] ? $aboutInfo['person_id'] : '';
        } else {
            $this->record_bind_type  = DeviceConst::BIND_FACE_BIND_USER_BIND_CARD;
            $this->record_bind_id    = $this->village_third_id;
            $this->syn_status        = DeviceConst::BIND_USER_ALL_SYN_ERR;
            $this->syn_status_txt    = '住户同步失败(无同步对象)';
            $this->err_reason        = "同步对象UID不存在";
            $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param];
            $this->recordDeviceBindFilterBox($param);
            if ($this->village_user_bind_id) {
                $houseVillageUserBindService = new HouseVillageUserBindService();
                $updateBindParam = [
                    'face_img_status' => 2,
                    'face_img_reason' => "同步对象缺少平台身份",
                ];
                $houseVillageUserBindService->saveUserBind(['pigcms_id' => $this->village_user_bind_id], $updateBindParam);
            }
            if($param['log_id']){
                (new OwnerService())->updateLog(['log_id'=>$param['log_id']],[
                    'status'=>2,
                    'msg'=>"同步对象不存在或者状态异常",
                    'last_time'=>time()
                ]);
            }
            fdump_api(['param' => $param, 'msg' => '同步对象缺少平台身份'], 'v20Face/addSinglePersonToCloud',1);
            return $this->backData($param, '小区未绑定', 1005);
        }
        $person_param_json = json_encode($personParam, JSON_UNESCAPED_UNICODE);
        $this->clearRecordDeviceBindFilter();
        $this->syn_status        = DeviceConst::BIND_USER_ALL_SYN_START;
        $this->syn_status_txt    = "住户同步开始";
        $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__,'param' =>$param,'person_json' =>$person_json,'person_param_json' => $person_param_json,'personParam' =>$personParam];
        fdump_api(['param' => $param, 'msg' => '住户同步开始'], 'v20Face/addSinglePersonToCloud',1);
        $this->recordDeviceBindFilterBox($param);
        $this->step_num++;
        if (!$this->sFaceHikCloudNeiBuDeviceService) {
            $this->sFaceHikCloudNeiBuDeviceService = new FaceHikCloudNeiBuDeviceService();
        }
        if (in_array($type, [5, 6])) {
            // todo 5-访客通行证 6出入证
            $personType = 1;
            $addVisitorParam = [];
            // 查询下房间人员信息
            $whereUserBind = [];
            $whereUserBind[] = ['status', '=', 1];
            if (isset($userBind['vacancy_id']) && $userBind['vacancy_id']) {
                $whereUserBind[] = ['vacancy_id', '=', $userBind['vacancy_id']];
                $whereUserBind[] = ['type',       'in', [0, 3]];
                $houseVillageUserBindService = new HouseVillageUserBindService();
                $filed = true;
                $userBinds = $houseVillageUserBindService->getBindInfo($whereUserBind,$filed);
                if ($userBinds && !is_array($userBinds)) {
                    $userBinds = $userBinds->toArray();
                }
                $errNoSys = false;
                if (!isset($userBinds['uid']) || !$userBinds['uid']) {
                    $errNoSys = true;
                } else {
                    $ownerInfo = $this->filterUserToDataFromUid($userBinds['uid'], DeviceConst::HK_UID_CLOUD_USER, false);
                    $ownerPersonId  = isset($ownerInfo['person_id']) && $ownerInfo['person_id'] ? $ownerInfo['person_id'] : '';
                }
                if ($errNoSys || !isset($ownerPersonId) || !$ownerPersonId) {
                    $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param, 'userBinds' => $userBinds];
                    $this->record_bind_type     = DeviceConst::BIND_USER;
                    $this->record_bind_id       = $this->village_user_bind_id;
                    $this->third_bind_id        = $this->unit_third_id;
                    $this->third_second_bind_id = $this->build_third_id;
                    $this->third_three_bind_id  = $this->village_third_id;
                    $this->syn_status           = DeviceConst::BIND_USER_FAIL;
                    $this->filterErrMsg([], $param, '邀请者未同步对应设备');
                }
                $visitorEffectiveTime = isset($userBind['property_starttime']) ? $userBind['property_starttime'] : '';
                $visitorLeaveTime     = isset($userBind['property_endtime'])   ? $userBind['property_endtime']   : '';
                if (!$this->room_third_id) {
                    $this->record_bind_type     = DeviceConst::BIND_FACE_BIND_USER_BIND_CARD;
                    $this->record_bind_id       = $this->village_user_bind_id;
                    $this->third_bind_id        = $this->unit_third_id;
                    $this->third_second_bind_id = $this->build_third_id;
                    $this->third_three_bind_id  = $this->village_third_id;
                    $this->syn_status           = DeviceConst::BIND_USER_ALL_SYN_ERR;
                    return $this->filterNonExistentRoom($param);
                }
                $roomId = $this->room_third_id;
            } else {
                $personType = 1;
                // 查询下出入证信息
                $dbHouseVillagePassBindMember = new HouseVillagePassBindMember();
                $wherePass = [];
                $wherePass[] = ['bind_id',     '=', $this->village_user_bind_id];
                $wherePass[] = ['open_status', '=', 0];
                $wherePass[] = ['guard',      '<>', ''];
                $wherePass[] = ['status',     '=', 0];
                $wherePass[] = ['due_time',   '>', time()];
                $passInfo = $dbHouseVillagePassBindMember->getOne($wherePass);
                if ($passInfo && !is_array($passInfo)) {
                    $passInfo = $passInfo->toArray();
                }
                if (empty($passInfo)) {
                    $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param];
                    $this->record_bind_type     = DeviceConst::BIND_USER;
                    $this->record_bind_id       = $this->village_user_bind_id;
                    $this->third_bind_id        = $this->unit_third_id;
                    $this->third_second_bind_id = $this->build_third_id;
                    $this->third_three_bind_id  = $this->village_third_id;
                    $this->syn_status           = DeviceConst::BIND_USER_FAIL;
                    $this->filterErrMsg([], $param, '出入证不存在或者状态异常');
                }
                $visitorEffectiveTime = isset($passInfo['start_time']) ? $passInfo['start_time'] : '';
                $visitorLeaveTime     = isset($passInfo['due_time'])   ? $passInfo['due_time']   : '';
                $guard                = isset($passInfo['guard'])      ? $passInfo['guard']      : '';
                $guardArr = explode(',', $guard);
                $public_area_id_arr = [];
                $floor_id_arr = [];
                foreach ($guardArr as $item) {
                    $itemArr = explode('-', $item);
                    if (isset($itemArr[0]) && $itemArr[0] == 0 && isset($itemArr[1]) && $itemArr[1]) {
                        $public_area_id_arr[] = $itemArr[1];
                    } elseif (isset($itemArr[0]) && $itemArr[0] >= 0 && isset($itemArr[1]) && $itemArr[1]) {
                        $floor_id_arr[] = $itemArr[1];
                    }
                }
                $dbHouseFaceDevice = new HouseFaceDevice();
                $cloudDeviceIdArr = [];
                if (!empty($public_area_id_arr)) {
                    $whereFace = [] ;
                    $whereFace[] = ['public_area_id',  'in', $public_area_id_arr];
                    $whereFace[] = ['is_del',          '=', 0];
                    $whereFace[] = ['cloud_device_id', '<>', ''];
                    $cloudDeviceIdArr1 = $dbHouseFaceDevice->getColumn($whereFace, 'cloud_device_id');
                    if (!empty($cloudDeviceIdArr1)) {
                        $cloudDeviceIdArr = $cloudDeviceIdArr1;
                    }
                }
                if (!empty($floor_id_arr)) {
                    $whereFace = [] ;
                    $whereFace[] = ['floor_id',        'in', $floor_id_arr];
                    $whereFace[] = ['is_del',          '=', 0];
                    $whereFace[] = ['cloud_device_id', '<>', ''];
                    $cloudDeviceIdArr2 = $dbHouseFaceDevice->getColumn($whereFace, 'cloud_device_id');
                    if (!empty($cloudDeviceIdArr2)) {
                        $cloudDeviceIdArr = array_merge($cloudDeviceIdArr, $cloudDeviceIdArr2);
                    }
                }
                $deviceIds = implode(',', $cloudDeviceIdArr);
                if (empty($deviceIds)) {
                    $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param];
                    $this->record_bind_type     = DeviceConst::BIND_USER;
                    $this->record_bind_id       = $this->village_user_bind_id;
                    $this->third_bind_id        = $this->unit_third_id;
                    $this->third_second_bind_id = $this->build_third_id;
                    $this->third_three_bind_id  = $this->village_third_id;
                    $this->syn_status           = DeviceConst::BIND_USER_FAIL;
                    $this->filterErrMsg([], $param, '出入证缺少指定的设备');
                }
            }
            if (!$visitorEffectiveTime || !$visitorLeaveTime || $visitorLeaveTime<= time()) {
                $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param, 'personParam' => $personParam];
                $this->record_bind_type     = DeviceConst::BIND_USER;
                $this->record_bind_id       = $this->village_user_bind_id;
                $this->third_bind_id        = $this->unit_third_id;
                $this->third_second_bind_id = $this->build_third_id;
                $this->third_three_bind_id  = $this->village_third_id;
                $this->syn_status           = DeviceConst::BIND_USER_FAIL;
                $this->filterErrMsg([], $param, '访客或出入证缺少时间范围或已经失效');
                return $this->backData($param, '同步人员失败', 1005);
            }
            if (isset($ownerPersonId) && $ownerPersonId) {
                $addVisitorParam['personId']           = $ownerPersonId;
            }
            if (isset($deviceIds) && $deviceIds) {
                $addVisitorParam['deviceIds']          = $deviceIds;
            }
            if (isset($roomId) && $roomId) {
                $addVisitorParam['roomId']             = $roomId;
            }
            $addVisitorParam['personType']           = $personType;
            $addVisitorParam['visitorName']          = isset($userBind['name']) && $userBind['name'] ? $userBind['name'] : '访客'.$this->village_user_bind_id;
            $addVisitorParam['gender']               = 1;
            $addVisitorParam['phone']                = isset($userBind['phone']) ? $userBind['phone'] : '';
            $addVisitorParam['visitorEffectiveTime'] = date("c", $visitorEffectiveTime);
            $addVisitorParam['visitorLeaveTime']     = date("c", $visitorLeaveTime);
            $result = $this->sFaceHikCloudNeiBuDeviceService->addVisitor($addVisitorParam);
            if ($result['code'] == 200) {
                $houseVillageUserBindService = new HouseVillageUserBindService();
                $updateBindParam = [
                    'face_img_status' => 1,
                    'face_img_reason' => '',
                ];
                $houseVillageUserBindService->saveUserBind(['pigcms_id' => $this->village_user_bind_id], $updateBindParam);
                if($param['log_id']){
                    (new OwnerService())->updateLog(['log_id'=>$param['log_id']],[
                        'status'=>1,
                        'last_time'=>time()
                    ]);
                }
            }
            return $this->backData($param, '同步成功');
        } elseif ($user_third_id) {
            // todo 已经有了身份 绑定下小区楼栋单元和房屋 处理下卡片
            if ($person_param_json != $person_json) {
                $personParam['personId'] = $this->user_third_id;
                fdump_api(['personParam' => $personParam], 'v20Face/addSinglePersonToCloud',1);
                $result = $this->sFaceHikCloudNeiBuDeviceService->addPersonToCloud($personParam);
                fdump_api(['result' => $result], 'v20Face/addSinglePersonToCloud',1);
                if (isset($result['code']) &&  $result['code'] == HikConst::HIK_NON_EXISTENT_PERSON) {
                    unset($personParam['personId']);
                    fdump_api(['personParam' => $personParam], 'v20Face/addSinglePersonToCloud',1);
                    $result = $this->sFaceHikCloudNeiBuDeviceService->addPersonToCloud($personParam);
                    fdump_api(['result' => $result], 'v20Face/addSinglePersonToCloud',1);
                    if  (isset($result['data']['personId']) && $result['data']['personId']) {
                        $user_third_id = $result['data']['personId'];
                    } else {
                        $user_third_id = '';
                    }
                }
            }
        } else {
            // todo 无身份走添加逻辑
            fdump_api(['personParam' => $personParam], 'v20Face/addSinglePersonToCloud',1);
            if (empty($personParam)) {
                $user_third_id = '';
            } else {
                $result = $this->sFaceHikCloudNeiBuDeviceService->addPersonToCloud($personParam);
                fdump_api(['result' => $result], 'v20Face/addSinglePersonToCloud',1);
                if  (isset($result['data']['personId']) && $result['data']['personId']) {
                    $user_third_id = $result['data']['personId'];
                } else {
                    $user_third_id = '';
                }
            }
        }
        if (!$user_third_id) {
            // todo 没有获取到身份 所以记录错误
            $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param, 'personParam' => $personParam, 'result' => $result];
            $this->record_bind_type     = DeviceConst::BIND_USER;
            $this->record_bind_id       = $this->village_user_bind_id;
            $this->third_bind_id        = $this->unit_third_id;
            $this->third_second_bind_id = $this->build_third_id;
            $this->third_three_bind_id  = $this->village_third_id;
            $this->syn_status           = DeviceConst::BIND_USER_FAIL;
            $this->filterErrMsg($result, $param);
            return $this->backData($param, '同步人员失败', 1005);
        } else {
            // todo 添加成功但是 没有记录 记录下
            $this->recordHkPerson($user_third_id, DeviceConst::HK_UID_CLOUD_USER, $uid);
        }
        // todo 后期考虑走下队列
        // 操作下绑定身份
        fdump_api(['msg' => '操作下绑定身份', $user_third_id,$uid, $this->village_third_id, $this->village_id,$this->thirdProtocol, $param], 'v20Face/addSinglePersonToCloud',1);
        $relation1 = $this->addCommunityRelation($user_third_id,$uid, $this->village_third_id, $this->village_id,$this->thirdProtocol, $param);
        fdump_api(['relation1' => $relation1], 'v20Face/addSinglePersonToCloud',1);
        if (isset($relation1['code']) && $relation1['code'] != 200) {
            return $relation1;
        }
        if (isset($userBind['pass_time']) && $userBind['pass_time']) {
            $param['checkInDate'] = date('Y-m-d',$userBind['pass_time']);
        } elseif (isset($userBind['add_time']) && $userBind['add_time']) {
            $param['checkInDate'] = date('Y-m-d',$userBind['add_time']);
        }
        if (isset($userBind['type']) && in_array($userBind['type'], [0, 3])) {
            $param['identityType'] = 1;
        } elseif (isset($userBind['type']) && $userBind['type'] == 1) {
            $param['identityType'] = 3;
        } else {
            $param['identityType'] = 2;
            $param['checkOutDate'] = '2038-01-01';
        }
        $userTimes = (new VisitorInviteService())->getUserEndTime($userBind['pigcms_id'], ['isDelay' => 1]);
        if (isset($userTimes['propertyEndTime']) && intval($userTimes['propertyEndTime']) > 100) {
            $checkInDate = isset($param['checkInDate']) && $param['checkInDate'] ? @strtotime($param['checkInDate']) : 0;
            if (!$checkInDate || $checkInDate > $userTimes['propertyEndTime']) {
                $checkInDate = $userTimes['propertyEndTime'] - 86400;
                $param['checkInDate'] = date('Y-m-d', $checkInDate);
            }
            $param['checkOutDate'] = date('Y-m-d', $userTimes['propertyEndTime']);
        }
        if (!$judge) {
            // 工作人员不用设置房屋归属
            fdump_api(['msg' => '绑定归属房间',$user_third_id,$uid, $this->room_third_id, $this->room_id,$this->thirdProtocol, $param], 'v20Face/addSinglePersonToCloud',1);
            $relation2 = $this->addRoomRelation($user_third_id,$uid, $this->room_third_id, $this->room_id,$this->thirdProtocol, $param);
            fdump_api(['relation2' => $relation2], 'v20Face/addSinglePersonToCloud',1);
            if (isset($relation2['code']) && $relation2['code'] != 200) {
                return $relation2;
            }
        }
        if ($judge) {
            $personType = 1;
        } else {
            $personType = 1;
        }
        if (isset($userBind['ic_card']) && $userBind['ic_card']) {
            // 移除不在当前关联用户的卡号
            $dbFaceUserBindDevice = new FaceUserBindDevice();
            $whereCard = [];
            $whereCard[] = ['bind_type', '=', DeviceConst::HK_UID_BIND_CARD];
            $whereCard[] = ['bind_id', '=', $uid];
            $whereCard[] = ['person_id', '<>', $userBind['ic_card']];
            $whereCard[] = ['personID', '=', $user_third_id];
            $whereCard[] = ['code', '=', $personType];
            $whereCard[] = ['cardid', '<>', ''];
            $cardIds = $dbFaceUserBindDevice->getColumn($whereCard, 'cardid');
            if (!empty($cardIds)) {
                $cardIds = array_values(array_unique($cardIds));
                fdump_api(['msg' => '已经不使用的卡号进行移除了-代操作', 'cardIds' => $cardIds], 'v20Face/addSinglePersonToCloud', 1);
                foreach ($cardIds as $cardId) {
                    $refundParamsInfo = ['cardId' => $cardId];
                    $refundCardResult = $this->sFaceHikCloudNeiBuDeviceService->refundCard($refundParamsInfo);
                    if (isset($refundCardResult['code']) && ($refundCardResult['code'] == 200 || $refundCardResult['code'] == 511108)) {
                        $whereCardSave = [];
                        $whereCardSave[] = ['bind_type', '=', DeviceConst::HK_UID_BIND_CARD];
                        $whereCardSave[] = ['cardid', '=', $cardId];
                        $updateParam = [
                            'bind_type' => -150, 'bind_id' => 'delete_' . $uid, 'personID' => 'delete_' . $user_third_id,
                        ];
                        $set1 = $dbFaceUserBindDevice->saveData($whereCardSave, $updateParam);
                        fdump_api(['msg' => '已经不使用的卡号进行移除了-更改记录', 'whereCardSave' => $whereCardSave, 'updateParam' => $updateParam, 'set1' => $set1], 'v20Face/addSinglePersonToCloud', 1);
                    }
                    fdump_api(['msg' => '已经不使用的卡号进行移除了-进行退卡', 'refundParamsInfo' => $refundParamsInfo, 'refundCardResult' => $refundCardResult], 'v20Face/addSinglePersonToCloud', 1);
                }
            }
            fdump_api(['msg' => '下发卡号',$user_third_id, $uid, $userBind['ic_card'], $personType,$this->village_third_id, $this->thirdProtocol, $param], 'v20Face/addSinglePersonToCloud',1);
            $cardResult = $this->openCard($user_third_id, $uid, $userBind['ic_card'], $personType,$this->village_third_id, $this->thirdProtocol, $param);
            fdump_api(['cardResult' => $cardResult], 'v20Face/addSinglePersonToCloud',1);
            if (isset($cardResult['code']) && $cardResult['code'] != 200 && $cardResult['code'] != '511106') {
                if (isset($deviceInfo['cloud_device_id']) && $deviceInfo['cloud_device_id']) {
                    $issuedParam = [
                        'communityId' => $this->village_third_id,
                        'personType'  => $personType,
                        'personId'    => $user_third_id,
                        'deviceId'    => $deviceInfo['cloud_device_id'],
                    ];
                    $queueData = $param;
                    $queueData['village_user_bind_id'] = $this->village_user_bind_id;
                    $queueData['build_third_id']       = $this->build_third_id;
                    $queueData['unit_third_id']        = $this->unit_third_id;
                    $queueData['village_third_id']     = $this->village_third_id;
                    $queueData['issuedParam']          = $issuedParam;
                    $queueData['jobType']              = 'authorityIssued';
                    fdump_api(['msg' => '下发触发', '$queueData' => $queueData], 'v20Face/addSinglePersonToCloud',1);
                    $job_id = $this->traitCommonHikLaterToJob($queueData, 8);
                }
                return $cardResult;
            }
        } elseif (isset($userBind['ic_card']) && ! $userBind['ic_card']) {
            $listCardParam = ['personId' => $user_third_id, 'communityId' => $this->village_third_id];
            $getCards = $this->sFaceHikCloudNeiBuDeviceService->getCards($listCardParam);
            if (isset($getCards['data']) && $getCards['data']) {
                foreach ($getCards['data'] as $cardInfo) {
                    $refundParamsInfo = ['cardId' => $cardInfo['cardId']];
                    $refundCardResult = $this->sFaceHikCloudNeiBuDeviceService->refundCard($refundParamsInfo);
                    fdump_api(['msg' => '卡号移除了-进行退卡', 'refundParamsInfo' => $refundParamsInfo, 'refundCardResult' => $refundCardResult], 'v20Face/addSinglePersonToCloud', 1);
                }
            }
        }
        if ($this->device_sn || $this->device_id) {
            $this->device_equipment_type = DeviceConst::DEVICE_TYPE_FACE;
            $infoArr = $this->handleParamDeviceInfo($param);
            if (isset($infoArr) && isset($infoArr['deviceInfo']) && $infoArr['deviceInfo']) {
                $deviceInfo       = $infoArr['deviceInfo'];
            }
        } else {
            $deviceInfo = [];
        }
        if (isset($deviceInfo['cloud_device_id']) && $deviceInfo['cloud_device_id']) {
            $issuedParam = [
                'communityId' => $this->village_third_id,
                'personType'  => $personType,
                'personId'    => $user_third_id,
                'deviceId'    => $deviceInfo['cloud_device_id'],
            ];
            $queueData = $param;
            $queueData['village_user_bind_id'] = $this->village_user_bind_id;
            $queueData['build_third_id']       = $this->build_third_id;
            $queueData['unit_third_id']        = $this->unit_third_id;
            $queueData['village_third_id']     = $this->village_third_id;
            $queueData['issuedParam']          = $issuedParam;
            $queueData['jobType']              = 'authorityIssued';
            fdump_api(['msg' => '下发触发', '$queueData' => $queueData], 'v20Face/addSinglePersonToCloud',1);
            $job_id = $this->traitCommonHikLaterToJob($queueData, 8);
        } elseif ($this->village_user_bind_id) {
            $houseVillageUserBindService = new HouseVillageUserBindService();
            $updateBindParam = [
                'face_img_status' => 2,
                'face_img_reason' => '缺少可同步对象',
            ];
            $houseVillageUserBindService->saveUserBind(['pigcms_id' => $this->village_user_bind_id], $updateBindParam);

            if($param['log_id']){
                (new OwnerService())->updateLog(['log_id'=>$param['log_id']],[
                    'status'=>2,
                    'msg'=>"缺少可同步对象",
                    'last_time'=>time()
                ]);
            }
        }
        
        return $this->backData($param, '同步成功');
    }

    /**
     * 下发
     * @param $param
     * @param $issuedParam
     * @return array
     * @throws \think\Exception
     */
    public function IssuedPersonToDevice($param, $issuedParam) {
        $param = $this->filterCommonParam($param);
        if ($issuedParam) {
            if (!$this->sFaceHikCloudNeiBuDeviceService) {
                $this->sFaceHikCloudNeiBuDeviceService = new FaceHikCloudNeiBuDeviceService();
            }
            fdump_api(['msg' => '下发前', 'param' => $param,  'issuedParam' => $issuedParam], 'v20Face/addSinglePersonToCloud',1);
            $issuedDevice = $this->sFaceHikCloudNeiBuDeviceService->authorityIssued($issuedParam);
            fdump_api(['msg' => '下发结果', 'issuedDevice' => $issuedDevice], 'v20Face/addSinglePersonToCloud',1);
            if (isset($issuedDevice['code']) && $issuedDevice['code'] != 200) {
                $this->record_bind_type     = DeviceConst::ISSUED_USER_TO_DEVICE;
                $this->record_bind_id       = $this->village_user_bind_id;
                $this->third_bind_id        = $this->unit_third_id;
                $this->third_second_bind_id = $this->build_third_id;
                $this->third_three_bind_id  = $this->village_third_id;
                $this->syn_status           = DeviceConst::BIND_USER_FAIL;
                $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param, 'issuedParam' => $issuedParam, 'issuedDevice' => $issuedDevice];
                $this->filterErrMsg($issuedDevice, $param);
                return $this->backData($param, '下发人员失败', 1005);
            }
        }
        $village_user_bind_id = $this->village_user_bind_id;
        if (isset($param['pigcms_id']) && intval($param['pigcms_id']) > 0) {
            $village_user_bind_id = intval($param['pigcms_id']);
        }
        $houseVillageUserBindService = new HouseVillageUserBindService();
        $updateBindParam = [
            'face_img_status' => 1,
            'face_img_reason' => '',
        ];
        fdump_api(['msg' => '记录', 'pigcms_id' => $village_user_bind_id, 'updateBindParam' => $updateBindParam], 'v20Face/addSinglePersonToCloud',1);
        $houseVillageUserBindService->saveUserBind(['pigcms_id' => $village_user_bind_id], $updateBindParam);
        if($param['log_id']){
            (new OwnerService())->updateLog(['log_id'=>$param['log_id']],[
                'status'=>1,
                'last_time'=>time()
            ]);
        }
        return $this->backData($param, '同步成功');
    }
    
    public function filterErrMsg($result, $param, $err = '') {
        $message = isset($result['message']) && $result['message'] ? $result['message'] : '';
        $code    = isset($result['code'])    && $result['code']    ? $result['code']    : '';
        $this->syn_status_txt       = '同步人员失败';
        if ($err) {
            $this->err_reason           = $err;
            $face_img_reason            = $err;
        } else {
            $this->err_reason           = "同步人员失败";
            $face_img_reason            = "同步失败";
        }
        if ($message) {
            $this->err_reason .= "[云眸消息：{$message}]";
            $face_img_reason  .= "[云眸消息：{$message}]";
        }
        if ($code) {
            $this->err_reason .= "[云眸返回码：{$code}]";
        }
        $this->recordDeviceBindFilterBox($param);
        if ($this->village_user_bind_id) {
            $houseVillageUserBindService = new HouseVillageUserBindService();
            $updateBindParam = [
                'face_img_status' => 2,
                'face_img_reason' => $face_img_reason,
            ];
            $houseVillageUserBindService->saveUserBind(['pigcms_id' => $this->village_user_bind_id], $updateBindParam);
        }
        if($param['log_id']){
            (new OwnerService())->updateLog(['log_id'=>$param['log_id']],[
                'status'=>2,
                'msg'=>$face_img_reason,
                'last_time'=>time()
            ]);
        }
    }

    /**
     * 处理记录下 人员基础信息 平台住户和
     * @param string $person_id
     * @param int $bind_type
     * @param int $bind_id
     * @param array $param
     * @return int|mixed|string
     */
    protected function recordHkPerson(string $person_id, int $bind_type,int $bind_id,  array $param = []) {
        // todo 查询下 房屋的同步场所id
        $aboutInfo = $this->filterUserToDataFromUid($bind_id, $bind_type);
        $nowTime = $this->nowTime ? $this->nowTime : time();
        if ($aboutInfo && !is_array($aboutInfo)) {
            $aboutInfo = $aboutInfo->toArray();
        }
        $dbFaceUserBindDevice = new FaceUserBindDevice();
        if ($aboutInfo && isset($aboutInfo['id'])) {
            // 已经存在了 更新信息
            $updateParam = [
                'bind_type'     => $bind_type,
                'bind_id'       => $bind_id,
                'device_type'   => 0,
                'person_id'     => $person_id,
                'last_time'     => $nowTime,
            ];
            if (isset($param['personID']) && $param['personID']) {
                $updateParam['personID'] = $param['personID'];
            }
            if (isset($param['person_json_md5']) && $param['person_json_md5']) {
                $updateParam['person_json'] = $param['person_json_md5'];
            }
            if (isset($param['group_id']) && $param['group_id']) {
                $updateParam['group_id'] = $param['personID'];
            }
            if (isset($param['groupID']) && $param['groupID']) {
                $updateParam['groupID'] = $param['personID'];
            }
            $update = $dbFaceUserBindDevice->saveData(['id'=>$aboutInfo['id']], $updateParam);
            $id = $aboutInfo['id'];
            $this->user_third_id = $person_id;
        } else {
            $addParam = [
                'bind_type'     => $bind_type,
                'bind_id'       => $bind_id,
                'device_type'   => 0,
                'person_id'     => $person_id,
                'add_time'      => $nowTime,
            ];
            if (isset($param['personID']) && $param['personID']) {
                $addParam['personID'] = $param['personID'];
            }
            if (isset($param['person_json_md5']) && $param['person_json_md5']) {
                $addParam['person_json'] = $param['person_json_md5'];
            }
            if (isset($param['group_id']) && $param['group_id']) {
                $addParam['group_id'] = $param['group_id'];
            }
            if (isset($param['groupID']) && $param['groupID']) {
                $addParam['groupID'] = $param['groupID'];
            }
            $id = $dbFaceUserBindDevice->addData($addParam);
            $this->user_third_id = $person_id;
        }
        return $id;
    }
    
    public function addCommunityRelation($person_id, $uid, $village_third_id = '', $village_id = 0,$thirdProtocol = 0, $param = [])
    {
        if (!$village_third_id) {
            $village_third_id = $this->village_third_id;
        }
        if (!$village_id) {
            $village_id = $this->village_id;
        }
        if (!$thirdProtocol) {
            $thirdProtocol = $this->thirdProtocol;
        }
        $bindParam = [
            'person_id' => $village_third_id,
            'personID'  => $village_id,
            'code'      => $person_id,
        ];
        if (!$this->sFaceHikCloudNeiBuDeviceService) {
            $this->sFaceHikCloudNeiBuDeviceService = new FaceHikCloudNeiBuDeviceService();
        }
        $communityAboutInfo = $this->commonDhFilterUserToData(DeviceConst::HK_UID_BIND_COMMUNITY, $uid, false, $bindParam);
        if (!isset($communityAboutInfo['id']) || !$communityAboutInfo['id']) {
            // todo 设置人员所属社区
            $relationParam1 = [
                'personId'    => $person_id,
                'communityId' => $village_third_id,
            ];
            $communityResult = $this->sFaceHikCloudNeiBuDeviceService->addCommunityRelation($relationParam1);
            if (isset($communityResult['code']) && 511000 == $communityResult['code']) {
                $bind_type = DeviceConst::HK_TO_CLOUD_VILLAGE;
                $bind_id   = $village_id;
                $updateParam = [
                    'bind_type' => -$bind_type,
                    'bind_id'   => -$bind_id,
                ];
                // 云眸报错 社区不存在 本地存储变更 重新同步
                $this->filterUpdateFaceBindAboutInfo($bind_type, $bind_id, $thirdProtocol, $updateParam);

                $this->record_bind_type     = DeviceConst::BIND_COMMUNITY_RELATION;
                $this->record_bind_id       = $uid;
                $this->third_bind_id        = $person_id;
                $this->third_second_bind_id = $village_third_id;
                $this->syn_status           = DeviceConst::BIND_USER_TO_COMMUNITY_RELATION_ERR;
                return $this->filterNonExistentVillage($param);
            } elseif ($communityResult['code'] != 200 && $communityResult['code'] != 511023) {
                $this->record_bind_type     = DeviceConst::BIND_COMMUNITY_RELATION;
                $this->record_bind_id       = $uid;
                $this->third_bind_id        = $person_id;
                $this->third_second_bind_id = $village_third_id;
                $this->syn_status           = DeviceConst::BIND_USER_TO_COMMUNITY_RELATION_ERR;
                $this->syn_status_txt       = '住户绑定社区失败';
                $this->err_reason           = "住户绑定社区失败";
                $face_img_reason            = "同步失败";
                $message = isset($communityResult['message']) && $communityResult['message'] ? $communityResult['message'] : '';
                $code    = isset($communityResult['code'])    && $communityResult['code']    ? $communityResult['code']    : '';
                if ($message) {
                    $this->err_reason .= "[云眸消息：{$message}]";
                    $face_img_reason  .= "[云眸消息：{$message}]";
                }
                if ($code) {
                    $this->err_reason .= "[云眸返回码：{$code}]";
                }
                $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param];
                $this->recordDeviceBindFilterBox($param);
                if ($this->village_user_bind_id) {
                    $houseVillageUserBindService = new HouseVillageUserBindService();
                    $updateBindParam = [
                        'face_img_status' => 2,
                        'face_img_reason' => $face_img_reason,
                    ];
                    $houseVillageUserBindService->saveUserBind(['pigcms_id' => $this->village_user_bind_id], $updateBindParam);
                }
                if($param['log_id']){
                    (new OwnerService())->updateLog(['log_id'=>$param['log_id']],[
                        'status'=>2,
                        'msg'=>$face_img_reason,
                        'last_time'=>time()
                    ]);
                }
            } else {
                $communityResult['code'] = 200;
                // 记录下来
                if (!$this->dbFaceUserBindDevice) {
                    $this->dbFaceUserBindDevice = new FaceUserBindDevice();
                }
                $dataAbout = [
                    'bind_type'  => DeviceConst::HK_UID_BIND_COMMUNITY,
                    'bind_id'    => $uid,
                    'person_id'  => $village_third_id,
                    'personID'   => $village_id,
                    'code'       => $person_id,
                    'add_time'   => time()
                ];
                $this->dbFaceUserBindDevice->addData($dataAbout);
            }
            return $communityResult;
        }
        return true;
    }
    
    public function addRoomRelation($person_id, $uid, $room_third_id = '', $room_id = 0,$thirdProtocol = 0, $param = [])
    {
        if (isset($param['checkInDate'])) {
            $checkInDate = trim($param['checkInDate']);
            unset($param['checkInDate']);
        }
        if (isset($param['identityType'])) {
            $identityType = intval($param['identityType']);
            unset($param['identityType']);
        } else {
            $identityType = 1;
        }
        if (isset($param['checkOutDate'])) {
            $checkOutDate = trim($param['checkOutDate']);
            unset($param['checkOutDate']);
        } else {
            $checkOutDate = '';
        }
        if (!isset($checkInDate) || !$checkInDate) {
            $checkInDate = date('Y-m-d');
        }
        if (!$room_third_id) {
            $room_third_id = $this->room_third_id;
        }
        if (!$room_id) {
            $room_id = $this->room_id;
        }
        if (!$thirdProtocol) {
            $thirdProtocol = $this->thirdProtocol;
        }
        $bindParam = [
            'person_id' => $room_third_id,
            'personID'  => $room_id,
            'code'      => $person_id,
        ];
        if (!$this->sFaceHikCloudNeiBuDeviceService) {
            $this->sFaceHikCloudNeiBuDeviceService = new FaceHikCloudNeiBuDeviceService();
        }
        $repeat = isset($param['repeat']) ? intval($param['repeat']) : 0;
        $communityAboutInfo = $this->commonDhFilterUserToData(DeviceConst::HK_UID_BIND_ROOM, $uid, false, $bindParam);
        fdump_api(['msg' => '查詢绑定记录', 'communityAboutInfo' => $communityAboutInfo, 'parameter' => func_get_args()], 'v20Face/addSinglePersonToCloud',1);
        if (!isset($communityAboutInfo['id']) || !$communityAboutInfo['id']) {
            // todo 设置人员所属社区
            $relationParam2 = [
                'personId'      => $person_id,
                'roomId'        => $room_third_id,
                'identityType'  => $identityType,
                'checkInDate'   => $checkInDate,
                'checkOutDate'  => $checkOutDate,
            ];
            $roomResult = $this->sFaceHikCloudNeiBuDeviceService->addRoomRelation($relationParam2);
            fdump_api(['msg' => '绑定房屋', 'relationParam2' => $relationParam2, 'roomResult' => $roomResult], 'v20Face/addSinglePersonToCloud',1);
            if (isset($roomResult['code']) && 511026 == $roomResult['code'] && $repeat <= 0) {
                if (!$this->dbFaceUserBindDevice) {
                    $this->dbFaceUserBindDevice = new FaceUserBindDevice();
                }
                $whereAbout = [];
                $whereAbout[] = ['bind_type', '=', DeviceConst::HK_UID_BIND_ROOM];
                if ($room_third_id) {
                    $whereAbout[] = ['person_id', '=', $room_third_id];
                } else {
                    $whereAbout[] = ['personID', '=', $room_id];
                }
                // [1]业主[2]租客[3]家属
                $whereAbout[] = ['groupID', '=', 1];
                $columnInfo = $this->dbFaceUserBindDevice->getOne($whereAbout, 'id, bind_type, bind_id, person_id, personID, group_id, groupID, person_text, code');
                $person_text = isset($columnInfo['person_text']) && $columnInfo['person_text'] ? json_decode($columnInfo['person_text'], 1) : [];
                $groupID = isset($columnInfo['groupID']) ? intval($columnInfo['groupID']) : 0;
                $personCode = isset($columnInfo['code']) ? trim($columnInfo['code']) : '';
                fdump_api(['msg' => '已经存在业主了 查询下 看是否解绑', 'columnInfo' => $columnInfo, 'person_text' => $person_text, 'groupID' => $groupID], 'v20Face/addSinglePersonToCloud',1);
                if (!empty($person_text) && isset($person_text['identityType']) && intval($person_text['identityType']) == 1) {
                    $cloudWorkParam = ['personId' => $personCode, 'roomId' => $room_third_id];
                    $deleteResult = (new FaceHikCloudNeiBuDeviceService())->deleteRoomRelation($cloudWorkParam);
                    fdump_api(['msg' => '解绑房屋', 'cloudWorkParam' => $cloudWorkParam, 'deleteResult' => $deleteResult], 'v20Face/addSinglePersonToCloud',1);
                    if (isset($deleteResult['code']) && 200 == intval($deleteResult['code'])) {
                        $whereAbout = [];
                        $whereAbout[] = ['bind_type', '=', DeviceConst::HK_UID_BIND_ROOM];
                        $whereAbout[] = ['person_id', '=', $room_third_id];
                        $whereAbout[] = ['personID', '=', $room_id];
                        $updateParam = [
                            'person_id' => 'delete_' . $room_third_id, 'personID' => 'delete_' . $room_id,
                        ];
                        $this->dbFaceUserBindDevice->saveData($whereAbout, $updateParam);
                        fdump_api(['msg' => '解绑房屋记录', 'whereAbout' => $whereAbout, 'updateParam' => $updateParam], 'v20Face/addSinglePersonToCloud',1);
                        $param['repeat'] = 1;
                        return $this->addRoomRelation($person_id, $uid, $room_third_id, $room_id, $thirdProtocol, $param);
                    }
                } elseif ($groupID == 1) {
                    $cloudWorkParam = ['personId' => $personCode, 'roomId' => $room_third_id];
                    $deleteResult = (new FaceHikCloudNeiBuDeviceService())->deleteRoomRelation($cloudWorkParam);
                    fdump_api(['msg' => '解绑房屋', 'cloudWorkParam' => $cloudWorkParam, 'deleteResult' => $deleteResult], 'v20Face/addSinglePersonToCloud',1);
                    if (isset($deleteResult['code']) && 200 == intval($deleteResult['code'])) {
                        $whereAbout = [];
                        $whereAbout[] = ['bind_type', '=', DeviceConst::HK_UID_BIND_ROOM];
                        $whereAbout[] = ['person_id', '=', $room_third_id];
                        $whereAbout[] = ['personID', '=', $room_id];
                        $updateParam = [
                            'person_id' => 'delete_' . $room_third_id, 'personID' => 'delete_' . $room_id,
                        ];
                        $this->dbFaceUserBindDevice->saveData($whereAbout, $updateParam);
                        fdump_api(['msg' => '解绑房屋记录', 'whereAbout' => $whereAbout, 'updateParam' => $updateParam], 'v20Face/addSinglePersonToCloud',1);
                        $param['repeat'] = 1;
                        return $this->addRoomRelation($person_id, $uid, $room_third_id, $room_id, $thirdProtocol, $param);
                    }
                }
                fdump_api(['whereAbout' => $whereAbout], 'v20Face/addSinglePersonToCloud',1);
                // 该户室已经关联业主 查询下对应业主
            }
            if (isset($roomResult['code']) && 511029 == $roomResult['code']) {
                $bind_type = DeviceConst::HK_TO_CLOUD_ROOM;
                $bind_id   = $room_id;
                $updateParam = [
                    'bind_type' => -$bind_type,
                    'bind_id'   => -$bind_id,
                ];
                // 云眸报错 社区不存在 本地存储变更 重新同步
                $this->filterUpdateFaceBindAboutInfo($bind_type, $bind_id, $thirdProtocol, $updateParam);

                $this->record_bind_type     = DeviceConst::BIND_COMMUNITY_RELATION;
                $this->record_bind_id       = $uid;
                $this->third_bind_id        = $person_id;
                $this->third_second_bind_id = $room_third_id;
                $this->syn_status           = DeviceConst::BIND_USER_TO_ROOM_RELATION_ERR;
                return $this->filterNonExistentVillage($param);
            } elseif ($roomResult['code'] != 200 && $roomResult['code'] != 511028) {
                $message = isset($roomResult['message']) && $roomResult['message'] ? $roomResult['message'] : '';
                $code    = isset($roomResult['code'])    && $roomResult['code']    ? $roomResult['code']    : '';
                $this->record_bind_type     = DeviceConst::BIND_COMMUNITY_RELATION;
                $this->record_bind_id       = $uid;
                $this->third_bind_id        = $person_id;
                $this->third_second_bind_id = $room_third_id;
                $this->syn_status           = DeviceConst::BIND_USER_TO_ROOM_RELATION_ERR;
                $this->syn_status_txt       = '住户绑定社区失败';
                $this->err_reason           = "住户绑定社区失败";
                $face_img_reason            = "同步失败";
                if ($message) {
                    $this->err_reason .= "[云眸消息：{$message}]";
                    $face_img_reason  .= "[云眸消息：{$message}]";
                }
                if ($code) {
                    $this->err_reason .= "[云眸返回码：{$code}]";
                }
                $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param];
                $this->recordDeviceBindFilterBox($param);
                if ($this->village_user_bind_id) {
                    $houseVillageUserBindService = new HouseVillageUserBindService();
                    $updateBindParam = [
                        'face_img_status' => 2,
                        'face_img_reason' => $face_img_reason,
                    ];
                    $houseVillageUserBindService->saveUserBind(['pigcms_id' => $this->village_user_bind_id], $updateBindParam);
                }
                if($param['log_id']){
                    (new OwnerService())->updateLog(['log_id'=>$param['log_id']],[
                        'status'=>2,
                        'msg'=>$face_img_reason,
                        'last_time'=>time()
                    ]);
                }
            } else {
                $roomResult['code'] = 200;
                // 记录下来
                if (!$this->dbFaceUserBindDevice) {
                    $this->dbFaceUserBindDevice = new FaceUserBindDevice();
                }
                $person_text = json_encode($relationParam2, JSON_UNESCAPED_UNICODE);
                $pigcms_id = isset($param['pigcms_id']) ? intval($param['pigcms_id']) : 0;
                $dataAbout = [
                    'bind_type' => DeviceConst::HK_UID_BIND_ROOM, 'bind_id' => $uid, 'person_id' => $room_third_id,
                    'personID' => $room_id, 'code' => $person_id, 'group_id' => $pigcms_id, 'groupID' => $identityType,
                    'person_text' => $person_text, 'add_time' => time()
                ];
                $this->dbFaceUserBindDevice->addData($dataAbout);
            }
            return $roomResult;
        } else {
            $personTextArray = isset($communityAboutInfo['person_text']) ? json_decode($communityAboutInfo['person_text'], 1) : [];
            fdump_api(['msg' => '重新设置人员所属社区', 'line' => __LINE__, 'personTextArray' => $personTextArray, 'checkOutDate' => $checkOutDate], 'v20Face/addSinglePersonToCloud',1);
            if (!isset($personTextArray['checkOutDate']) || $personTextArray['checkOutDate'] != $checkOutDate) {
                // todo 重新设置人员所属社区
                $setRelationParam2 = [
                    'personId'      => $person_id,
                    'delRoomId'        => $room_third_id,
                    'addRoomId'        => $room_third_id,
                    'identityType'  => $identityType,
                    'checkInDate'   => $checkInDate,
                    'checkOutDate'  => $checkOutDate,
                ];
                fdump_api(['msg' => '重新设置人员所属社区', 'line' => __LINE__, 'setRelationParam2' => $setRelationParam2], 'v20Face/addSinglePersonToCloud',1);
                $setRoomResult = $this->sFaceHikCloudNeiBuDeviceService->setRoomRelation($setRelationParam2);
                fdump_api(['msg' => '重新设置人员所属社区', 'line' => __LINE__, 'setRoomResult' => $setRoomResult], 'v20Face/addSinglePersonToCloud',1);
                if ($setRoomResult['code'] == 200) {
                    $whereAbout = [];
                    $whereAbout[] = ['id', '=', $communityAboutInfo['id']];
                    $person_text = json_encode($setRelationParam2, JSON_UNESCAPED_UNICODE);
                    $updateParam = [
                        'person_text' => $person_text, 'last_time' => time()
                    ];
                    if (!$this->dbFaceUserBindDevice) {
                        $this->dbFaceUserBindDevice = new FaceUserBindDevice();
                    }
                    $this->dbFaceUserBindDevice->saveData($whereAbout, $updateParam);
                }
            }
        }
        return true;
    }
    
    public function openCard($person_id, $uid, $cardNumber, $personType, $village_third_id = '',$thirdProtocol = 0, $param = []) {
        if (!$thirdProtocol) {
            $thirdProtocol = $this->thirdProtocol;
        }
        if (!$village_third_id) {
            $village_third_id = $this->village_third_id;
        }
        $bindParam = [
            'person_id' => $cardNumber,
            'personID'  => $person_id,
        ];
        if (!$this->sFaceHikCloudNeiBuDeviceService) {
            $this->sFaceHikCloudNeiBuDeviceService = new FaceHikCloudNeiBuDeviceService();
        }
        $communityAboutInfo = $this->commonDhFilterUserToData(DeviceConst::HK_UID_BIND_CARD, $uid, false, $bindParam);
        $communityAboutInfoTime = isset($communityAboutInfo['last_time']) && $communityAboutInfo['last_time'] ? $communityAboutInfo['last_time'] : 0;
        !$communityAboutInfoTime && $communityAboutInfoTime = isset($communityAboutInfo['add_time']) && $communityAboutInfo['add_time'] ? $communityAboutInfo['add_time'] : 0;
        $nowJudge = time() - 18000; // 5小时内 不能 重复同步
        fdump_api(['msg' => '下发卡号', 'communityAboutInfoTime' => $communityAboutInfoTime, 'nowJudge' => $nowJudge,  'person_id' => $person_id,  'uid' => $uid,  'cardNumber' => $cardNumber, 'personType' => $personType, 'communityAboutInfo' => $communityAboutInfo], 'v20Face/addSinglePersonToCloud',1);
        if (!isset($communityAboutInfo['id']) || !$communityAboutInfo['id'] || $communityAboutInfoTime < $nowJudge) {
            // todo 设置人员所属社区
            $openCardParam = [
                'unionId'     => $uid,
                'personId'    => $person_id,
                'cardNumber'  => $cardNumber,
                'personType'  => $personType,
            ];
            $openCardResult = $this->sFaceHikCloudNeiBuDeviceService->openCard($openCardParam);
            fdump_api(['msg' => '下发卡号', 'openCardParam' => $openCardParam,  'openCardResult' => $openCardResult], 'v20Face/addSinglePersonToCloud',1);
            if (isset($openCardResult['code']) && HikConst::HIK_IC_CARD_EXISTENT == strval($openCardResult['code'])) {
                // todo 查询下对应人员拥有的卡片
                $listCardParam = [
                    'personId'       => $person_id,
                    'communityId'    => $village_third_id,
                ];
                $getCards = $this->sFaceHikCloudNeiBuDeviceService->getCards($listCardParam);
//                $listCardParam1 = [
//                    'personId'       => '4338c4f5e6924723b50939761e974ec1',
//                    'communityId'    => $village_third_id,
//                ];
//                $getCards1 = $this->sFaceHikCloudNeiBuDeviceService->getCards($listCardParam1);
                fdump_api(['msg' => '查询-下发卡号', 'listCardParam' => $listCardParam,  'getCards' => $getCards], 'v20Face/addSinglePersonToCloud',1);
//                if (isset($getCards['data']) && $getCards['data']) {
//                    
//                }
                if (isset($getCards['data']) && !empty($getCards['data'])) {
                    $cardDatas = isset($getCards['data']) ? $getCards['data'] : [];
                    if (count($cardDatas) == 1 && is_array($cardDatas)) {
                        $cardData = reset($cardDatas);
                        $cardId = isset($cardData['cardId']) && trim($cardData['cardId']) ? trim($cardData['cardId']) : '';
                    } elseif (is_array($cardDatas)) {
                        $cardData = reset($cardDatas);
                        $cardId = isset($cardData['cardId']) && trim($cardData['cardId']) ? trim($cardData['cardId']) : '';
                    } else {
                        $cardId = '';
                    }
                    fdump_api(['msg' => '退卡-卡号', 'cardId' => $cardId, 'communityAboutInfo' => $communityAboutInfo,  'cardDatas' => $cardDatas], 'v20Face/addSinglePersonToCloud',1);
                    if ($cardId && (!isset($communityAboutInfo['cardid']) || $communityAboutInfo['cardid'] != $cardId)) {
                        // 退卡操作
                        $refundParams = ['cardId' => $cardId];
                        $refundCardResult = $this->sFaceHikCloudNeiBuDeviceService->refundCard($refundParams);
                        fdump_api(['msg' => '退卡操作', 'refundParams' => $refundParams,  'refundCardResult' => $refundCardResult], 'v20Face/addSinglePersonToCloud',1);
                        if (isset($refundCardResult['code']) && intval($refundCardResult['code']) == 200) {
                            fdump_api(['msg' => '退卡操作后重新开卡参数', 'openCardParam' => $openCardParam], 'v20Face/addSinglePersonToCloud',1);
                            $openCardResult = $this->sFaceHikCloudNeiBuDeviceService->openCard($openCardParam);
                            fdump_api(['msg' => '退卡操作后重新开发结果', 'openCardResult' => $openCardResult], 'v20Face/addSinglePersonToCloud',1);
                        }
                    } elseif ($cardId && isset($communityAboutInfo['cardid']) && $communityAboutInfo['cardid'] == $cardId) {
                        $openCardResult['code'] = 200;
                        $openCardResult['codeMessage'] = '已经同步过了';
                    }
//                    if (!$this->dbFaceUserBindDevice) {
//                        $this->dbFaceUserBindDevice = new FaceUserBindDevice();
//                    }
//                    $dataAbout = [
//                        'bind_type' => DeviceConst::HK_UID_BIND_CARD, 'bind_id' => $uid, 'person_id' => $cardNumber,
//                        'personID' => $person_id, 'code' => $personType, 'cardid' => $cardId, 'add_time' => time()
//                    ];
//                    fdump_api(['msg' => '下发卡号-记录', 'dataAbout' => $dataAbout], 'v20Face/addSinglePersonToCloud',1);
//                    $this->dbFaceUserBindDevice->addData($dataAbout);
                }
            }
            if ($openCardResult['code'] != 200) {
                $this->record_bind_type     = DeviceConst::BIND_USER_OPEN_CARD;
                $this->record_bind_id       = $uid;
                $this->third_bind_id        = $person_id;
                $this->syn_status           = DeviceConst::BIND_USER_TO_OPEN_CARD_ERR;
                $this->syn_status_txt       = '人员开通卡片失败';
                $this->err_reason           = "人员开通卡片失败";
                $face_img_reason            = "人员开通卡片";
                $message = isset($openCardResult['message']) && $openCardResult['message'] ? $openCardResult['message'] : '';
                $code    = isset($openCardResult['code'])    && $openCardResult['code']    ? $openCardResult['code']    : '';
                if ($message) {
                    $this->err_reason .= "[云眸消息：{$message}]";
                    $face_img_reason  .= "[云眸消息：{$message}]";
                }
                if ($code) {
                    $this->err_reason .= "[云眸返回码：{$code}]";
                }
                $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param];
                $this->recordDeviceBindFilterBox($param);
                if ($this->village_user_bind_id) {
                    $houseVillageUserBindService = new HouseVillageUserBindService();
                    $updateBindParam = [
                        'face_img_status' => 2,
                        'face_img_reason' => $face_img_reason,
                    ];
                    $houseVillageUserBindService->saveUserBind(['pigcms_id' => $this->village_user_bind_id], $updateBindParam);
                }
                if($param['log_id']){
                    (new OwnerService())->updateLog(['log_id'=>$param['log_id']],[
                        'status'=>2,
                        'msg'=>$face_img_reason,
                        'last_time'=>time()
                    ]);
                }
            } else {
                $cardId = isset($openCardResult['data']['cardId']) && trim($openCardResult['data']['cardId']) ? trim($openCardResult['data']['cardId']) : '';
                // 记录下卡片
                // 记录下来
                if (!$this->dbFaceUserBindDevice) {
                    $this->dbFaceUserBindDevice = new FaceUserBindDevice();
                }
                $dataAbout = [
                    'bind_type' => DeviceConst::HK_UID_BIND_CARD, 'bind_id' => $uid, 'person_id' => $cardNumber,
                    'personID' => $person_id, 'code' => $personType, 'cardid' => $cardId
                ];
                fdump_api(['msg' => '下发卡号-记录', 'dataAbout' => $dataAbout], 'v20Face/addSinglePersonToCloud',1);
                if (isset($communityAboutInfo['id']) && intval($communityAboutInfo['id']) > 0) {
                    $whereAbout = [];
                    $whereAbout[] = ['id', '=', $communityAboutInfo['id']];
                    $dataAbout['last_time'] = time();
                    $this->dbFaceUserBindDevice->saveData($whereAbout, $dataAbout);
                } else {
                    $dataAbout['add_time'] = time();
                    $this->dbFaceUserBindDevice->addData($dataAbout);
                }
            }
            return $openCardResult;
        }
        return true;
    }

    /**
     * 同步楼栋信息到设备云
     * @param array $param
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function buildToDeviceCloud(array $param) {
        if (!isset($param['orderGroupId']) || $param['orderGroupType']) {
            $param['orderGroupType'] = 'build_to_device_cloud';
        }
        $this->clearRecordDeviceBindFilter();
        // 同步楼栋
        if (isset($param['single_param'])) {
            $single_param = $param['single_param'];
            unset($param['single_param']);
        } else {
            $this->syn_status        = DeviceConst::BINDS_SYN_BUILD_ERR;
            $this->syn_status_txt    = "同步楼栋失败";
            $this->err_reason        = "同步楼栋失败(缺少同步信息)";
            $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param];
            $this->recordDeviceBindFilterBox($param);
            return $this->backData($param, '缺少同步信息', 1018);
        }
        $param     = $this->filterCommonParam($param);
        $bind_id   = isset($single_param['bind_id']) ? $single_param['bind_id'] : 0;
        $single    = isset($single_param['single'])  ? $single_param['single']  : [];
        if (!$this->device_id || !$this->device_sn || !$this->face_device_type || !$this->device_equipment_type || !$this->thirdProtocol || !$this->village_id) {
            // 查询下相关设备信息
            $infoArr = $this->handleParamDeviceInfo($param);
            if (isset($infoArr['code']) && $infoArr['code']) {
                $this->syn_status        = DeviceConst::BINDS_SYN_BUILD_ERR;
                $this->syn_status_txt    = "同步楼栋失败";
                $this->err_reason        = "同步楼栋失败({$infoArr['msg']})";
                $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
                $this->second_bind_type  = DeviceConst::BIND_BUILD;
                $this->second_bind_id    = $bind_id;
                $this->third_name        = isset($single['single_name']) ? $single['single_name'] : '楼栋_' . $bind_id;
                $this->recordDeviceBindFilterBox($param);
                if ($this->device_equipment_type == DeviceConst::DEVICE_TYPE_ALARM) {
                    $alarmDeviceService = new AlarmDeviceService();
                    $whereAlarmUpdate = [];
                    $whereAlarmUpdate[] = ['village_id', '=', $this->village_id];
                    if ($this->device_id) {
                        $whereAlarmUpdate[] = ['device_id', '=', $this->device_id];
                    } elseif ($this->device_sn) {
                        $whereAlarmUpdate[] = ['device_serial', '=', $this->device_sn];
                    }
                    $whereAlarmUpdate[] = ['is_del', '=', 0];
                    $param1s = ['cloud_status' => 3, 'cloud_reason' => "同步楼栋失败({$infoArr['msg']})", 'update_time' => time()];
                    $alarmDeviceService->updateAlarmDevice($whereAlarmUpdate, $param1s);
                }
                return $infoArr;
            }
            $param = $infoArr['param'];
        }
        $result = $this->singleHikHBuildingToDeviceCloud($single_param, $param);

        if (empty($result['code']) && isset($result['third_id'])) {
            $param['step_num']       = $this->step_num + 1;
            $param['jobType']        = 'floorToDeviceBox';
            $param['build_third_id'] = $result['third_id'];
            $param['single_id']      = $bind_id;
            if ($this->village_third_id) {
                $param['village_third_id'] = $this->village_third_id;
            } elseif ($this->third_second_bind_id) {
                $param['village_third_id'] = $this->third_second_bind_id;
            }
            if (isset($result['thirdData']['bind_name']) && $result['thirdData']['bind_name']) {
                $this->third_name = $result['thirdData']['bind_name'];
            } else {
                $this->third_name = isset($single['single_name']) ? $single['single_name'] : '楼栋_' . $bind_id;
            }
            $param['third_name']     = $this->third_name;
            $job_id = $this->traitCommonHikCloudUnits($param);
            if (isset($job_id)) {
                $param['job_id']     = $job_id;
            }
            $this->syn_status        = DeviceConst::BINDS_SYN_BUILD_UNIT_QUEUE;
            $this->syn_status_txt    = '下发同步楼栋的单元队列';
            $this->err_reason        = "";
            $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
            $this->second_bind_type  = DeviceConst::BIND_BUILD;
            $this->second_bind_id    = $bind_id;
            $this->recordDeviceBindFilterBox($param);

            switch ($this->device_equipment_type) {
                case DeviceConst::DEVICE_TYPE_ALARM:
                    if ($this->thirdProtocol != DeviceConst::DH_YUNRUI) {
                        if (isset($deviceInfo['single_id']) && $deviceInfo['single_id'] && (!isset($deviceInfo['floor_id']) || !$deviceInfo['floor_id'])) {
                            $communityId      = isset($single_param['communityId']) ? $single_param['communityId'] : $this->village_third_id;
                            $bind_type = DeviceConst::HK_TO_CLOUD_VILLAGE;
                            $bind_id   = $this->village_id;
                            $aboutInfo = $this->commonFilterAboutToData($bind_type, $bind_id, $this->thirdProtocol);
                            (new FaceDeviceService())->deviceToDeviceFiler($this->thirdProtocol, $communityId, $aboutInfo, $param);
                        }
                    }
                    break;
            }
            // todo 临时调试 直接执行  要还原回 调取队列执行
//            $this->floorToDeviceBox($param);
        }
    }

    /**
     *  * @param array $single_param
     * [
     *    'bind_type' => '同步类型 必传'
     *    'bind_id'=> '楼栋id 必传'
     *    'thirdProtocol'=> '协议 必传'
     *    'single'=> '待同步的楼栋信息 必传'
     *    'device_type'=> '设备类型 必传'
     *    'communityId'=> '海康云眸-社区id 必传'
     * ]
     *  * @param array $param
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author:zhubaodi
     * @date_time: 2022/8/22 15:40
     */
    public function singleHikHBuildingToDeviceCloud(array $single_param, array $param = []){
        $single = isset($single_param['single']) ? $single_param['single'] : [];
        /** @var int nowTime 当前时间 */
        $nowTime          = $this->nowTime ? $this->nowTime : time();
        /** @var int bind_type 绑定类型 */
        $bind_type        = isset($single_param['bind_type']) ? $single_param['bind_type'] : 0;
        $bind_id          = isset($single_param['bind_id']) ? $single_param['bind_id'] : 0;
        /** @var int face_bind_type 人脸设备类型 */
        $face_device_type = isset($single_param['device_type']) ? $single_param['device_type'] : 0;
        $thirdProtocol    = isset($single_param['thirdProtocol']) ? $single_param['thirdProtocol'] : $this->thirdProtocol;

        $this->filterVillageToData();

        $communityId      = isset($single_param['communityId']) ? $single_param['communityId'] : $this->village_third_id;
        $param['communityId'] = $communityId;
        $buildingNumber   = isset($single['single_number']) ? intval($single['single_number']) : '';

        $this->clearRecordDeviceBindFilter();
        $this->syn_status           = DeviceConst::BINDS_SYN_BUILD_START;
        $this->syn_status_txt       = "同步楼栋开始";
        $this->line_func_txt_arr    = ['line' => __LINE__, 'funs' => __FUNCTION__];
        $this->second_bind_type     = DeviceConst::BIND_BUILD;
        $this->second_bind_id       = $bind_id;
        $this->third_second_bind_id = $communityId;
        if ($buildingNumber) {
            $this->third_code       = strval($buildingNumber);
        }
        $this->third_name           = isset($single['single_name']) ? $single['single_name'] : '楼栋_' . $bind_id;
        $this->recordDeviceBindFilterBox($param);
        $this->step_num++;

        $errMsg             = '同步楼栋失败';
        $code               = 1011;
        $thirdData          = [];
        $third_id           = '';
        $is_third_info_save = true;

        $thirdData = (new FaceHikCloudNeiBuDeviceService())->buildingsToDeviceCloud($bind_id,$single, $param);
        if (!empty($thirdData)){
            //同步到系统
            $third_info = $thirdData;
            if (isset($thirdData['buildingId']) && $thirdData['buildingId']) {
                // 这里是海康云眸 返回 请注意各自返回信息要存储的字段进行处理
                $third_id = $thirdData['buildingId'];
                $errMsg   = '';
                $code     = '';
                unset($third_info['buildingId']);
            }  elseif (isset($thirdData['data']['buildingId']) && $thirdData['data']['buildingId']) {
                $third_id = $thirdData['data']['buildingId'];
                $errMsg   = '';
                $code     = '';
            } elseif (isset($thirdData['code']) && $thirdData['code'] == HikConst::HIK_NON_EXISTENT_COMMUNITIES) {
                // todo 社区不存在  考虑是否应该自动走下小区添加
                $this->syn_status        = DeviceConst::BINDS_SYN_BUILD_FAIL;
                $this->syn_status_txt    = "同步楼栋失败(社区不存在)";
                $this->err_reason        = "同步楼栋失败({$thirdData['message']})[{$thirdData['code']}]";
                $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'bind_id' => $bind_id, 'single' => $single, 'param' => $param];
                $this->recordDeviceBindFilterBox($param);
                $this->step_num++;
            } elseif (isset($thirdData['code']) && ($thirdData['code'] == HikConst::HIK_EXISTENT_BUILD_BAN_ADD || $thirdData['code'] == HikConst::HIK_EXISTENT_BUILD_NUMBER)) {
                // todo 511001	该社区下楼栋已存在，无法添加	|| 511004	楼栋编号已经存在
                $aboutInfo = $this->commonFilterAboutToData($bind_type, $bind_id, $thirdProtocol);
                if ($aboutInfo && isset($aboutInfo['third_id']) && $aboutInfo['third_id']) {
                    $third_id = $aboutInfo['third_id'];
                    $thirdData['communityId'] = $communityId;
                    $thirdData['buildingId']  = $third_id;
                    $errMsg                   = '';
                    $code                     = '';
                    $is_third_info_save       = false;
                } else {
                    $this->syn_status        = DeviceConst::BINDS_SYN_BUILD_FAIL;
                    $this->syn_status_txt    = "同步楼栋失败";
                    $this->err_reason        = "同步楼栋失败({$thirdData['message']})[{$thirdData['code']}][名称：{$thirdData['bind_name']},编号：{$thirdData['bind_number']}]";
                    $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'bind_id' => $bind_id, 'single' => $single, 'param' => $param, 'thirdData' => $thirdData];
                    $this->recordDeviceBindFilterBox($param);
                    $this->step_num++;
                }
            } elseif (isset($thirdData['code']) && isset($thirdData['message']) && $thirdData['code']>0) {
                $this->syn_status        = DeviceConst::BINDS_SYN_BUILD_ERR;
                $this->syn_status_txt    = "同步楼栋失败";
                $this->err_reason        = "同步楼栋失败({$thirdData['message']})[{$thirdData['code']}]";
                $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'bind_id' => $bind_id, 'single' => $single, 'param' => $param, 'thirdData' => $thirdData];
                $this->recordDeviceBindFilterBox($param);
                $this->step_num++;
            }
            if (isset($third_id) && $third_id) {
                $this->syn_status        = DeviceConst::BINDS_SYN_BUILD_SUCCESS;
                $this->syn_status_txt    = "同步楼栋成功";
                $this->err_reason        = "";
                $this->third_bind_id     = $third_id;
                $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'thirdData' => $thirdData, 'bind_id' => $bind_id, 'single' => $single,'param' => $param];
                $this->recordDeviceBindFilterBox($param);
                $this->step_num++;
                // 添加关联记录
                $bindData = [
                    'bind_type'      => $bind_type,
                    'bind_id'        => $bind_id,
                    'third_id'       => $third_id,
                    'device_type'    => $face_device_type,
                    'third_protocol' => $thirdProtocol,
                    'add_time'       => $nowTime,
                ];
                if (isset($third_info['bind_number'])) {
                    $bindData['bind_number'] = $third_info['bind_number'];
                    unset($third_info['bind_number']);
                }
                if (isset($third_info['bind_name'])) {
                    $bindData['bind_name'] = $third_info['bind_name'];
                    unset($third_info['bind_name']);
                }
                if ($is_third_info_save && !empty($third_info)) {
                    $bindData['third_info'] = json_encode($third_info, JSON_UNESCAPED_UNICODE);
                }
                $this->commonSaveAboutInfo($bind_type, $bind_id, $bindData);
            }
        } else {
            $this->syn_status = DeviceConst::BINDS_SYN_BUILD_FAIL;
            $this->syn_status_txt = "同步楼栋失败";
            $this->err_reason = "同步楼栋失败(三方无返回)";
            $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'bind_id' => $bind_id, 'single' => $single, 'param' => $param];
            $this->recordDeviceBindFilterBox($param);
            $this->step_num++;
        }
        if ($this->err_reason && $this->device_equipment_type == DeviceConst::DEVICE_TYPE_ALARM) {
            $alarmDeviceService = new AlarmDeviceService();
            $whereAlarmUpdate = [];
            $whereAlarmUpdate[] = ['village_id', '=', $this->village_id];
            if ($this->device_id) {
                $whereAlarmUpdate[] = ['device_id', '=', $this->device_id];
            } elseif ($this->device_sn) {
                $whereAlarmUpdate[] = ['device_serial', '=', $this->device_sn];
            }
            $whereAlarmUpdate[] = ['single_id', '=', $bind_id];
            $whereAlarmUpdate[] = ['is_del', '=', 0];
            $param1s = ['cloud_status' => 3, 'cloud_reason' => $this->err_reason, 'update_time' => time()];
            $alarmDeviceService->updateAlarmDevice($whereAlarmUpdate, $param1s);
        }
        return [
            'third_id'  => $third_id,
            'thirdData' => $thirdData,
            'code'      => $code,
            'msg'       => $errMsg,
        ];
    }
    
    /**
     * 批量同步楼栋的单元的盒子
     * @param $param
     * @return array|bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function floorToDeviceBox($param) {
        $param     = $this->filterCommonParam($param);
        $this->clearRecordDeviceBindFilter();
        if (!isset($param['single_id']) || !$param['single_id'] || !$this->device_id || !$this->device_sn || !$this->face_device_type || !$this->device_equipment_type || !$this->thirdProtocol || !$this->village_id) {
            // 查询下相关设备信息
            $infoArr = $this->handleParamDeviceInfo($param);
            if (isset($infoArr['code']) && $infoArr['code']) {
                $bind_id                 = isset($param['single_id']) ? $param['single_id'] : 0;
                $third_name              = isset($param['third_name'])  ? $param['third_name']  : '';
                $this->syn_status        = DeviceConst::BINDS_SYN_BUILD_UNITS_ERR;
                $this->syn_status_txt    = "下发同步楼栋的单元队列失败";
                $this->err_reason        = "下发同步楼栋的单元队列失败({$infoArr['msg']})";
                $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param];
                $this->second_bind_type  = DeviceConst::BIND_BUILD;
                $this->second_bind_id    = $bind_id;
                $this->third_name        = $third_name;
                if ($this->build_third_id) {
                    $this->third_second_bind_id = $this->build_third_id;
                }
                if ($this->village_third_id) {
                    $this->third_three_bind_id  = $this->village_third_id;
                }
                $this->recordDeviceBindFilterBox($param);
                if ($this->err_reason && $this->device_equipment_type == DeviceConst::DEVICE_TYPE_ALARM) {
                    $alarmDeviceService = new AlarmDeviceService();
                    $whereAlarmUpdate = [];
                    $whereAlarmUpdate[] = ['village_id', '=', $this->village_id];
                    if ($this->device_id) {
                        $whereAlarmUpdate[] = ['device_id', '=', $this->device_id];
                    } elseif ($this->device_sn) {
                        $whereAlarmUpdate[] = ['device_serial', '=', $this->device_sn];
                    }
                    $whereAlarmUpdate[] = ['single_id', '=', $bind_id];
                    $whereAlarmUpdate[] = ['is_del', '=', 0];
                    $param1s = ['cloud_status' => 3, 'cloud_reason' => $this->err_reason, 'update_time' => time()];
                    $alarmDeviceService->updateAlarmDevice($whereAlarmUpdate, $param1s);
                }
                return $infoArr;
            }
            $param = $infoArr['param'];
        }
        if (isset($infoArr['deviceInfo']) && $infoArr['deviceInfo']) {
            $deviceInfo       = $infoArr['deviceInfo'];
        } else {
            $deviceInfo       = [];
        }
        return $this->floorToDeviceFilter($deviceInfo,$this->village_id, $param['single_id'], $this->thirdProtocol, $param);
    }
    
    /**
     * 海康同步单元信息
     * @param array $floor_param
     * @param array $param
     * [
     *    'bind_type' => '同步类型 必传'
     *    'bind_id'=> '单元id 必传'
     *    'thirdProtocol'=> '协议 必传'
     *    'floor'=> '待同步的单元信息 必传'
     *    'device_type'=> '设备类型 必传'
     *    'buildingId'=> '海康云眸-楼栋id 必传'
     * ]
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author:zhubaodi
     * @date_time: 2022/8/22 15:40
     */
    public function singleFloorToHikDeviceCloud(array $floor_param, array $param = []){
        $param     = $this->filterCommonParam($param);
        /** @var int nowTime 当前时间 */
        $nowTime          = $this->nowTime                        ? $this->nowTime                 : time();
        /** @var int bind_type 绑定类型 */
        $bind_type        = isset($floor_param['bind_type'])      ? $floor_param['bind_type']      : 0;
        $bind_id          = isset($floor_param['bind_id'])        ? $floor_param['bind_id']        : 0;
        /** @var int face_bind_type 人脸设备类型 */
        $face_device_type = isset($floor_param['device_type'])    ? $floor_param['device_type']    : 0;
        $thirdProtocol    = isset($floor_param['thirdProtocol'])  ? $floor_param['thirdProtocol']  : $this->thirdProtocol;

        $floor            = isset($floor_param['floor'])          ? $floor_param['floor']          : [];
        $buildingId       = isset($floor_param['buildingId'])     ? $floor_param['buildingId']     : $this->build_third_id;
        $unitNumber       = isset($floor['floor_number'])         ? intval($floor['floor_number']) : '';
        if (!$buildingId && !isset($param['single_id'])) {
            $this->filterBuildToData($param['single_id']);
            $buildingId = $this->build_third_id;
        }
        $param['buildingId'] = $buildingId;

        $this->clearRecordDeviceBindFilter();
        $this->syn_status           = DeviceConst::BINDS_SYN_UNIT_START;
        $this->syn_status_txt       = "同步单元开始";
        $this->line_func_txt_arr    = ['line' => __LINE__, 'funs' => __FUNCTION__];
        $this->second_bind_type     = DeviceConst::BIND_UNIT;
        $this->second_bind_id       = $bind_id;
        $this->third_second_bind_id = $buildingId;
        if ($this->village_third_id) {
            $this->third_three_bind_id  = $this->village_third_id;
        }
        if ($unitNumber) {
            $this->third_code       = strval($unitNumber);
        }
        $this->third_name           = isset($floor['floor_name']) ? $floor['floor_name'] : '单元_' . $bind_id;
        $this->recordDeviceBindFilterBox($param);
        $this->step_num++;

        $errMsg = '同步单元失败';
        $code = 1011;
        $thirdData = [];
        $third_id = '';
        $thirdData = (new FaceHikCloudNeiBuDeviceService())->unitsToDeviceCloud($bind_id,$floor, $param);
        $is_third_info_save = true;
        if (!empty($thirdData)){
            //同步到系统
            $third_info = $thirdData;
            if (isset($thirdData['unitId']) && $thirdData['unitId']) {
                // 这里是海康云眸 返回 请注意各自返回信息要存储的字段进行处理
                $third_id = $thirdData['unitId'];
                $errMsg = '';
                $code = '';
                unset($third_info['unitId']);
            }  elseif (isset($thirdData['success']) && $thirdData['success']) {
                $errMsg = '';
                $code = '';
            } elseif (isset($thirdData['code']) && $thirdData['code'] == HikConst::HIK_NON_EXISTENT_BUILD) {
                // todo 社区不存在  考虑是否应该自动走下小区添加
                $this->syn_status        = DeviceConst::BINDS_SYN_UNIT_FAIL;
                $this->syn_status_txt    = "同步单元失败(楼栋不存在)";
                $this->err_reason        = "同步单元失败({$thirdData['message']})[{$thirdData['code']}]";
                $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'bind_id' => $bind_id, 'floor' => $floor, 'param' => $param];
                $this->recordDeviceBindFilterBox($param);
                $this->step_num++;
            } elseif (isset($thirdData['code']) && ($thirdData['code'] == HikConst::HIK_EXISTENT_UNIT_BAN_ADD || $thirdData['code'] == HikConst::HIK_EXISTENT_UNIT_NUMBER)) {
                // todo 511001	该社区下楼栋已存在，无法添加	|| 511004	楼栋编号已经存在
                $aboutInfo = $this->commonFilterAboutToData($bind_type, $bind_id, $thirdProtocol);
                if ($aboutInfo && isset($aboutInfo['third_id']) && $aboutInfo['third_id']) {
                    $third_id = $aboutInfo['third_id'];
                    $thirdData['unitId']      = $third_id;
                    $thirdData['buildingId']  = $buildingId;
                    $errMsg                   = '';
                    $code                     = '';
                    $is_third_info_save       = false;
                } else {
                    $this->syn_status        = DeviceConst::BINDS_SYN_UNIT_FAIL;
                    $this->syn_status_txt    = "同步单元失败";
                    $this->err_reason        = "同步单元失败({$thirdData['message']})[{$thirdData['code']}]";
                    $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'bind_id' => $bind_id, 'floor' => $floor, 'param' => $param];
                    $this->recordDeviceBindFilterBox($param);
                    $this->step_num++;
                }
            } elseif (isset($thirdData['code']) && isset($thirdData['message']) && $thirdData['code']>0) {
                $this->syn_status        = DeviceConst::BINDS_SYN_UNIT_FAIL;
                $this->syn_status_txt    = "同步单元失败";
                $this->err_reason        = "同步单元失败({$thirdData['message']})[{$thirdData['code']}]";
                $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'bind_id' => $bind_id, 'floor' => $floor, 'param' => $param, 'thirdData' => $thirdData];
                $this->recordDeviceBindFilterBox($param);
                $this->step_num++;
            }
            // 添加关联记录
            if (isset($third_id) && $third_id) {
                $this->syn_status        = DeviceConst::BINDS_SYN_UNIT_SUCCESS;
                $this->syn_status_txt    = "同步单元成功";
                $this->err_reason        = "";
                $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'bind_id' => $bind_id, 'thirdData' => $thirdData, 'param' => $param];
                $this->third_bind_id     = $third_id;
                $this->recordDeviceBindFilterBox($param);
                $this->step_num++;
                $bindData = [
                    'bind_type'      => $bind_type,
                    'bind_id'        => $bind_id,
                    'third_id'       => $third_id,
                    'device_type'    => $face_device_type,
                    'third_protocol' => $thirdProtocol,
                    'add_time'       => $nowTime,
                ];
                if (isset($third_info['bind_number'])) {
                    $bindData['bind_number'] = $third_info['bind_number'];
                    unset($third_info['bind_number']);
                }
                if (isset($third_info['bind_name'])) {
                    $bindData['bind_name']  = $third_info['bind_name'];
                    unset($third_info['bind_name']);
                }
                if (!empty($third_info) && $is_third_info_save) {
                    $bindData['third_info'] = json_encode($third_info, JSON_UNESCAPED_UNICODE);
                }
                $this->commonSaveAboutInfo($bind_type, $bind_id, $bindData);

                $param['step_num']       = $this->step_num + 1;
                $param['jobType']        = 'roomToDeviceBox';
                $param['third_id']       = $third_id;
                $param['floor_id']       = $bind_id;
                if ($this->village_third_id) {
                    $param['village_third_id'] = $this->village_third_id;
                }
                if ($this->build_third_id) {
                    $param['build_third_id'] = $this->build_third_id;
                } elseif ($this->third_second_bind_id) {
                    $param['build_third_id'] = $this->third_second_bind_id;
                }
                if ($this->unit_third_id) {
                    $param['unit_third_id'] = $this->unit_third_id;
                } else {
                    $param['unit_third_id'] = $third_id;
                }
                if (isset($result['thirdData']['bind_name']) && $result['thirdData']['bind_name']) {
                    $this->third_name = $result['thirdData']['bind_name'];
                } elseif (!$this->third_name) {
                    $this->third_name = isset($floor['floor_name']) ? $floor['floor_name'] : '单元_' . $bind_id;
                }
                $param['third_name']     = $this->third_name;
                $job_id = $this->traitCommonHikCloudUnits($param);
                if (isset($job_id)) {
                    $param['job_id']     = $job_id;
                }
                $this->clearRecordDeviceBindFilter();
                $this->syn_status           = DeviceConst::BINDS_SYN_UNIT_ROOMS_QUEUE;
                $this->syn_status_txt       = '下发同步单元的房屋队列';
                $this->line_func_txt_arr    = ['line' => __LINE__, 'funs' => __FUNCTION__];
                $this->second_bind_type     = DeviceConst::BIND_UNIT;
                $this->second_bind_id       = $bind_id;
                $this->third_second_bind_id = $buildingId;
                if ($this->village_third_id) {
                    $this->third_three_bind_id  = $this->village_third_id;
                }
                if ($unitNumber) {
                    $this->third_code       = strval($unitNumber);
                }
                $this->third_name           = isset($floor['floor_name']) ? $floor['floor_name'] : '单元_' . $bind_id;
                $this->recordDeviceBindFilterBox($param);
                $this->step_num++;
                // todo 临时调试 直接执行  要还原回 调取队列执行
//                $this->roomToDeviceBox($param);


                if ($this->device_sn || $this->device_id) {
                    $infoArr = $this->handleParamDeviceInfo($param);
                    if (isset($infoArr['code']) && $infoArr['code']) {
                        $this->clearRecordDeviceBindFilter();
                        $this->syn_status        = DeviceConst::BINDS_SYN_DEVICE_ERR;
                        $this->syn_status_txt    = "同步设备失败";
                        $this->err_reason        = "同步设备失败({$infoArr['msg']})";
                        $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
                        $this->recordDeviceBindFilterBox($param);
                        return $infoArr;
                    }
                    if (isset($infoArr) && isset($infoArr['deviceInfo']) && $infoArr['deviceInfo']) {
                        $deviceInfo       = $infoArr['deviceInfo'];
                    } else {
                        $deviceInfo       = [];
                    }
                    if (isset($deviceInfo['floor_id']) && $deviceInfo['floor_id'] == $bind_id) {
                        // todo 存在设备且设备 且同步成功单元和设备相同 同步设备
                        $param   = $infoArr['param'];
                        $deviceParams = $param;
                        $deviceParams['step_num']            = $this->step_num;
                        $job_id = $this->traitCommonHikCloudDevices($deviceParams);
                        if (isset($job_id)) {
                            $param['job_id']     = $job_id;
                        }
                        $this->syn_status        = DeviceConst::BINDS_SYN_DEVICE_QUEUE;
                        $this->syn_status_txt    = '下发执行同步设备队列';
                        $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
                        $this->recordDeviceBindFilterBox($param);

                        // todo 临时调试 直接执行  要还原回 调取队列执行
//                        $this->addDeviceToCloud($deviceParams);
                    }
                }
            }
        } else {
            $this->syn_status        = DeviceConst::BINDS_SYN_UNIT_FAIL;
            $this->syn_status_txt    = "同步单元失败";
            $this->err_reason        = "同步单元失败(三方无返回)";
            $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'bind_id' => $bind_id, 'floor' => $floor, 'param' => $param];
            $this->recordDeviceBindFilterBox($param);
            $this->step_num++;
        }
        if ($this->err_reason && $this->device_equipment_type == DeviceConst::DEVICE_TYPE_ALARM) {
            $alarmDeviceService = new AlarmDeviceService();
            $whereAlarmUpdate = [];
            $whereAlarmUpdate[] = ['village_id', '=', $this->village_id];
            if ($this->device_id) {
                $whereAlarmUpdate[] = ['device_id', '=', $this->device_id];
            } elseif ($this->device_sn) {
                $whereAlarmUpdate[] = ['device_serial', '=', $this->device_sn];
            }
            $whereAlarmUpdate[] = ['floor_id', '=', $bind_id];
            $whereAlarmUpdate[] = ['is_del', '=', 0];
            $param1s = ['cloud_status' => 3, 'cloud_reason' => $this->err_reason, 'update_time' => time()];
            $alarmDeviceService->updateAlarmDevice($whereAlarmUpdate, $param1s);
        }
        return [
            'third_id' => $third_id,
            'thirdData' => $thirdData,
            'code' => $code,
            'msg' => $errMsg,
        ];
    }
    
    /**
     * 下发同步单元的房屋队列
     * @param $param
     * @return array|bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function roomToDeviceBox($param) {
        $param     = $this->filterCommonParam($param);
        $nowTime   = $this->nowTime ? $this->nowTime : time();
        $this->clearRecordDeviceBindFilter();
        $bind_id                 = isset($param['floor_id'])    ? $param['floor_id']    : 0;
        $third_name              = isset($param['third_name'])  ? $param['third_name']  : '';
        if (!$bind_id || !$this->device_id || !$this->device_sn || !$this->face_device_type || !$this->device_equipment_type || !$this->thirdProtocol || !$this->village_id) {
            // 查询下相关设备信息
            $infoArr = $this->handleParamDeviceInfo($param);
            if (isset($infoArr['code']) && $infoArr['code']) {
                $this->syn_status        = DeviceConst::BINDS_SYN_UNIT_ROOMS_ERR;
                $this->syn_status_txt    = "下发同步单元的房屋队列失败";
                $this->err_reason        = "下发同步元的房屋队列失败({$infoArr['msg']})";
                $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param];
                $this->second_bind_type  = DeviceConst::BIND_UNIT;
                $this->second_bind_id    = $bind_id;
                $this->third_name        = $third_name;
                if ($this->unit_third_id) {
                    $this->third_second_bind_id = $this->unit_third_id;
                }
                if ($this->build_third_id) {
                    $this->third_three_bind_id  = $this->build_third_id;
                }
                $this->recordDeviceBindFilterBox($param);
                return $infoArr;
            }
            $param = $infoArr['param'];
        }
        if (isset($infoArr) && isset($infoArr['deviceInfo']) && $infoArr['deviceInfo']) {
            $deviceInfo       = $infoArr['deviceInfo'];
        } else {
            $deviceInfo       = [];
        }
        $this->syn_status        = DeviceConst::BINDS_SYN_UNIT_ROOMS_START;
        $this->syn_status_txt    = "下发同步单元的房屋队列开始";
        $this->err_reason        = "";
        $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'deviceInfo' => $deviceInfo, 'village_id' => $this->village_id, 'floor_id' => $param['floor_id'], 'param' => $param];
        $this->second_bind_type  = DeviceConst::BIND_UNIT;
        $this->second_bind_id    = $bind_id;
        $this->third_name        = $third_name;
        if ($this->unit_third_id) {
            $this->third_second_bind_id = $this->unit_third_id;
        }
        if ($this->build_third_id) {
            $this->third_three_bind_id  = $this->build_third_id;
        }
        $this->recordDeviceBindFilterBox($param);
        $this->step_num++;
        $faceDeviceService= new FaceDeviceService();
        return $faceDeviceService->roomToDeviceFilter($deviceInfo, $this->village_id, $param['floor_id'], $this->thirdProtocol, $param);
    }
    
    /**
     * 单个同步房屋信息
     * @param array $param
     * @return array
     */
    public function singleRoomToDeviceCloud(array $param) {
        if (isset($param['room_param'])) {
            $room_param = $param['room_param'];
            unset($param['room_param']);
        } else {
            $room_param = [];
        }
        $param     = $this->filterCommonParam($param);
        /** @var int nowTime 当前时间 */
        $nowTime          = $this->nowTime                        ? $this->nowTime                 : time();
        /** @var int bind_type 绑定类型 */
        $bind_type        = isset($room_param['bind_type'])       ? $room_param['bind_type']       : 0;
        $bind_id          = isset($room_param['bind_id'])         ? $room_param['bind_id']         : 0;
        /** @var int face_bind_type 人脸设备类型 */
        $face_device_type = isset($room_param['device_type'])     ? $room_param['device_type']     : $this->face_device_type;
        $thirdProtocol    = isset($room_param['thirdProtocol'])   ? $room_param['thirdProtocol']   : $this->thirdProtocol;

        $room             = isset($room_param['room'])            ? $room_param['room']            : [];
        $unitId           = isset($room_param['unitId'])          ? $room_param['unitId']          : $this->unit_third_id;
        $unitNumber       = isset($floor['floor_number'])         ? intval($floor['floor_number']) : '';
        if (!$unitId && isset($param['floor_id']) && $param['floor_id']) {
            $floor_id = $param['floor_id'];
        } elseif (!$unitId && isset($room_param['floor_id']) && $room_param['floor_id']) {
            $floor_id = $room_param['floor_id'];
        } elseif (!$unitId && isset($room['floor_id']) && $room['floor_id']) {
            $floor_id = $room['floor_id'];
        }
        if (!$unitId && isset($floor_id)) {
            $this->filterUnitToData($floor_id);
            $unitId = $this->unit_third_id;
        }
        $param['unitId'] = $unitId;


        $this->clearRecordDeviceBindFilter();
        $this->syn_status           = DeviceConst::BINDS_SYN_ROOM_START;
        $this->syn_status_txt       = "同步房屋开始";
        $this->line_func_txt_arr    = ['line' => __LINE__, 'funs' => __FUNCTION__];
        $this->second_bind_type     = DeviceConst::BIND_ROOM;
        $this->second_bind_id       = $bind_id;
        $this->third_second_bind_id = $unitId;
        if ($this->build_third_id) {
            $this->third_three_bind_id  = $this->build_third_id;
        }
        if ($unitNumber) {
            $this->third_code       = strval($unitNumber);
        }
        $this->third_name           = isset($room['room']) ? $room['room'] : '户室_' . $bind_id;
        $this->recordDeviceBindFilterBox($param);
        $this->step_num++;

        $errMsg      = '同步房屋失败';
        $code        = 1013;
        $third_id    = '';
        $room_number = '';
        $floorNumber = '';
        if (isset($room['room_number']) && $room['room_number']) {
            if (strlen($room['room_number'])>2) {
                $room_number = substr($room['room_number'], -2, 2);
                $floorNumber = substr($room['room_number'], 0, 2);
            } elseif (strlen($room['room_number']) < 2) {
                // 不足2位 补足2位
                $room_number = str_pad($room['room_number'], 2, "0", STR_PAD_LEFT);
            } else {
                $room_number = $room['room_number'];
            }
        }
        if (!$room_number) {
            $this->syn_status           = DeviceConst::BINDS_SYN_ROOM_ERR;
            $this->syn_status_txt       = "同步房屋错误（缺少房屋编号）";
            $this->err_reason           = "同步房屋错误（缺少房屋编号）[房屋ID：{$bind_id}]";
            $this->line_func_txt_arr    = ['line' => __LINE__, 'funs' => __FUNCTION__, 'room_number' => $room['room_number'], 'room_number1' => $room_number, 'param' => $param];
            $this->recordDeviceBindFilterBox($param);
            $this->step_num++;
            return [
                'third_id'  => '',
                'thirdData' => [],
                'code'      => 1011,
                'msg'       => "同步房屋错误（缺少房屋编号）",
            ];
        }
        if (isset($room['layer_number']) && $room['layer_number']) {
            $floorNumber = $room['layer_number'];
        }
        if (!$floorNumber) {
            $this->syn_status           = DeviceConst::BINDS_SYN_ROOM_ERR;
            $this->syn_status_txt       = "同步房屋错误（缺少楼层编号）";
            $this->err_reason           = "同步房屋错误（缺少楼层编号）[房屋ID：{$bind_id}]";
            $this->line_func_txt_arr    = ['line' => __LINE__, 'funs' => __FUNCTION__, 'layer_number' => $room['layer_number'], 'floorNumber' => $floorNumber, 'param' => $param];
            $this->recordDeviceBindFilterBox($param);
            $this->step_num++;
            return [
                'third_id'  => '',
                'thirdData' => [],
                'code'      => 1011,
                'msg'       => "同步房屋错误（缺少楼层编号）",
            ];
        }
        $room['room_number'] = $room_number;
        $room['floorNumber'] = $floorNumber;

        $thirdData = (new FaceHikCloudNeiBuDeviceService())->roomsToDeviceCloud($bind_id, $room, $param);
        $is_third_info_save = true;
        if (!empty($thirdData)){
            //同步到系统
            $third_info = $thirdData;
            $roomId = isset($thirdData['roomId']) && $thirdData['roomId'] ? $thirdData['roomId'] : '';
            !$roomId && $roomId = isset($thirdData['data']['roomId']) && $thirdData['data']['roomId'] ? $thirdData['data']['roomId'] : '';
            if ($roomId) {
                // 这里是海康云眸 返回 请注意各自返回信息要存储的字段进行处理
                $third_id = $roomId;
                $errMsg = '';
                $code = '';
            } elseif (isset($thirdData['code']) && $thirdData['code'] == HikConst::HIK_NON_EXISTENT_UNIT) {
                // todo 单元不存在  考虑是否应该自动走下单元添加
                $this->syn_status        = DeviceConst::BINDS_SYN_ROOM_FAIL;
                $this->syn_status_txt    = "同步房屋失败(单元不存在)";
                $this->err_reason        = "同步房屋失败({$thirdData['message']})[{$thirdData['code']}]";
                $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'bind_id' => $bind_id, 'room' => $room, 'param' => $param];
                $this->recordDeviceBindFilterBox($param);
                $this->step_num++;
            } elseif (isset($thirdData['code']) && ($thirdData['code'] == HikConst::HIK_EXISTENT_ROOM_BAN_ADD || $thirdData['code'] == HikConst::HIK_EXISTENT_ROOM_NUMBER)) {
                // todo 511001	该社区下楼栋已存在，无法添加	|| 511004	楼栋编号已经存在
                $aboutInfo = $this->commonFilterAboutToData($bind_type, $bind_id, $thirdProtocol);
                if ($aboutInfo && isset($aboutInfo['third_id']) && $aboutInfo['third_id']) {
                    $third_id = $aboutInfo['third_id'];
                    $thirdData['roomId']      = $third_id;
                    $thirdData['unitId']      = $unitId;
                    $errMsg                   = '';
                    $code                     = '';
                    $is_third_info_save       = false;
                } else {
                    $this->syn_status        = DeviceConst::BINDS_SYN_ROOM_FAIL;
                    $this->syn_status_txt    = "同步房屋失败";
                    $this->err_reason        = "同步房屋失败({$thirdData['message']})[{$thirdData['code']}]";
                    $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'bind_id' => $bind_id, 'room' => $room, 'param' => $param];
                    $this->recordDeviceBindFilterBox($param);
                    $this->step_num++;
                }
            } elseif (isset($thirdData['code']) && isset($thirdData['message']) && $thirdData['code']>0) {
                $this->syn_status        = DeviceConst::BINDS_SYN_ROOM_FAIL;
                $this->syn_status_txt    = "同步房屋失败";
                $this->err_reason        = "同步房屋失败({$thirdData['message']})[{$thirdData['code']}]";
                $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'bind_id' => $bind_id, 'room' => $room, 'param' => $param, 'thirdData' => $thirdData];
                $this->recordDeviceBindFilterBox($param);
                $this->step_num++;
            }
            // 添加关联记录
            if (isset($third_id) && $third_id) {
                $this->syn_status        = DeviceConst::BINDS_SYN_ROOM_SUCCESS;
                $this->syn_status_txt    = "同步房屋成功";
                $this->err_reason        = "";
                $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'bind_id' => $bind_id, 'thirdData' => $thirdData, 'room' => $room, 'param' => $param];
                $this->third_bind_id     = $third_id;
                $this->recordDeviceBindFilterBox($param);
                $this->step_num++;
                $bindData = [
                    'bind_type'      => $bind_type,
                    'bind_id'        => $bind_id,
                    'third_id'       => $third_id,
                    'device_type'    => $face_device_type,
                    'third_protocol' => $thirdProtocol,
                    'add_time'       => $nowTime,
                ];
                if (isset($third_info['bind_number'])) {
                    $bindData['bind_number'] = $third_info['bind_number'];
                    unset($third_info['bind_number']);
                }
                if (isset($third_info['bind_name'])) {
                    $bindData['bind_name']  = $third_info['bind_name'];
                    unset($third_info['bind_name']);
                }
                if (!empty($third_info) && $is_third_info_save) {
                    $bindData['third_info'] = json_encode($third_info, JSON_UNESCAPED_UNICODE);
                }
                $this->commonSaveAboutInfo($bind_type, $bind_id, $bindData);
            }
        } else {
            $this->syn_status        = DeviceConst::BINDS_SYN_UNIT_FAIL;
            $this->syn_status_txt    = "同步房屋失败";
            $this->err_reason        = "同步房屋失败(三方无返回)";
            $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'bind_id' => $bind_id, 'room' => $room, 'param' => $param];
            $this->recordDeviceBindFilterBox($param);
            $this->step_num++;
        }
        return [
            'third_id'  => $third_id,
            'thirdData' => $thirdData,
            'code'      => $code,
            'msg'       => $errMsg,
        ];
    }

    /**
     * 远程开门
     * @param $device_id
     * @param $pigcms_id
     * @param array $deviceInfo
     * @param int $thirdProtocol
     * @return array|string[]
     */
    public function gateControl($device_id, $pigcms_id, $deviceInfo = [], $thirdProtocol = 0) {
        $userBind = $this->getDeviceBindUser($pigcms_id, 0, false);
        if (isset($userBind['uid']) && $userBind['uid']) {
            
        } elseif ($userBind) {
            return ['errMsg' => '您没有相关权限，请联系物业或者上传人脸图片', 'message' => '缺少可开门权限'];
        }
        $aboutInfo = $this->filterUserToDataFromUid($userBind['uid'], DeviceConst::HK_UID_CLOUD_USER);
        if (!$this->user_third_id) {
            return ['errMsg' => '您没有相关权限，请联系物业或者上传人脸图片', 'message' => '缺少可开门权限'];
        }
        if (!isset($deviceInfo['cloud_device_id']) || !$deviceInfo['cloud_device_id']) {
            $whereFace  = [
                'device_id' => $device_id
            ];
            $faceField  = 'device_id,device_name,device_type,device_sn,village_id,floor_id,device_status,thirdProtocol,cloud_device_id';
            $deviceInfo = (new HouseFaceDeviceService())->getHouseFaceDeviceInfo($whereFace, $faceField);
            if ($deviceInfo && !is_array($deviceInfo)) {
                $deviceInfo = $deviceInfo->toArray();
            }
        }
        $deviceId = $deviceInfo['cloud_device_id'];
        $params = [
            'personId' => $this->user_third_id,
            'deviceId' => $deviceId,
        ];
        $result = (new FaceHikCloudNeiBuDeviceService())->gateControl($params);
        return $result;
    }

    public function getEventMsg($consumerId = '', $repeat = false, $debug = false)
    {
        // 注：6000C场景，不支持边缘侧门禁权限下发状态订阅消息和广告下发状态订阅消息
        $cacheTag    = HikConst::HIK_JOB_REDIS_TAG;
        $cacheKey    = HikConst::HIK_EVENT_REDIS_KEY . 'consumerId';
        if (!$consumerId) {
            $consumerId  = Cache::store('redis')->get($cacheKey);
            if (!$this->sFaceHikCloudNeiBuDeviceService) {
                $this->sFaceHikCloudNeiBuDeviceService = new FaceHikCloudNeiBuDeviceService();
            }
        }
        if (!$consumerId) {
            //创建消费者
            $info = $this->sFaceHikCloudNeiBuDeviceService->createCustomer();
            if (isset($info['data']['consumerId']) && $info['data']['consumerId']) {
                $consumerId = $info['data']['consumerId'];
                Cache::store('redis')->tag($cacheTag)->set($cacheKey, $consumerId, HikConst::HIK_CONSUMER_ID_REDIS_TIMES);
                sleep(4);
            }
        }
        if (!$consumerId) {
            return true;
        }
        $param = [
            'consumerId' => $consumerId,
        ];
        $list = $this->sFaceHikCloudNeiBuDeviceService->getCustomer($param);
        if (isset($list['code']) && $list['code'] == 514002) {
            Cache::store('redis')->tag($cacheTag)->set($cacheKey,null);
        }
        fdump_api(['list'=>$list,'consumerId'=>$consumerId], 'face/getEventMsg'.date('H'), 1);
        if (isset($list['code']) && $list['code'] == 200) {
            $data = isset($list['data']) ? $list['data'] : [];
            if (empty($data) && !$repeat) {
                sleep(HikConst::HIK_CONSUMER_REPEATED_TIMES);// 休眠10秒调用
                return $this->getEventMsg($consumerId, true, $debug);
            } elseif (empty($data)) {
                return $list;
            }
            // 社区资料变动的推送消息
            $communityMessageCommunityArr        = [];
            // 楼栋资料变动的推送消息                       
            $communityMessageBuildingArr         = [];
            // 单元资料变动的推送消息                       
            $communityMessageUnitArr             = [];
            // 房屋资料变动的推送消息                       
            $communityMessageRoomArr             = [];
            // 人员资料变动的推送消息                       
            $communityMessagePersonArr           = [];
            // 设备资料变动的推送消息                       
            $communityMessageDeviceArr           = [];
            // 门禁权限下发状态的推送消息                     
            $communityMessageAccessStateArr      = [];
            // 访客门禁权限下发状态订阅消息                    
            $communityMessageVisitorAccessArr    = [];
            // 门禁事件订阅的推送消息                       
            $communityEventAccessArr             = [];
            // 报警事件订阅的推送消息                       
            $communityEventAlarmArr              = [];
            // 对讲事件订阅的推送消息                       
            $communityEventIntercomArr           = [];
            // 广告下发状态订阅消息                        
            $communityAdvertStateArr             = [];
            // 房屋审核通知订阅消息                        
            $communityMessageAuditStateArr       = [];
            // 停车场车辆出入通知订阅消息
            $communityEventParkingPassVehicleArr = [];
            foreach ($data as $key=>$val){
                switch ($val['msgType']) {
                    case HikConst::HIK_EVENT_COMMUNITY_MESSAGE_COMMUNITY:
                        $communityMessageCommunityArr[]        = json_decode($val['content'],true);
                        break;
                    case HikConst::HIK_EVENT_COMMUNITY_MESSAGE_BUILDING:
                        $communityMessageBuildingArr[]         = json_decode($val['content'],true);
                        break;
                    case HikConst::HIK_EVENT_COMMUNITY_MESSAGE_UNIT:
                        $communityMessageUnitArr[]             = json_decode($val['content'],true);
                        break;
                    case HikConst::HIK_EVENT_COMMUNITY_MESSAGE_ROOM:
                        $communityMessageRoomArr[]             = json_decode($val['content'],true);
                        break;
                    case HikConst::HIK_EVENT_COMMUNITY_MESSAGE_PERSON:
                        $communityMessagePersonArr[]           = json_decode($val['content'],true);
                        break;
                    case HikConst::HIK_EVENT_COMMUNITY_MESSAGE_DEVICE:
                        $communityMessageDeviceArr[]           = json_decode($val['content'],true);
                        break;
                    case HikConst::HIK_EVENT_COMMUNITY_MESSAGE_ACCESS_STATE:
                        $communityMessageAccessStateArr[]      = json_decode($val['content'],true);
                        break;
                    case HikConst::HIK_EVENT_COMMUNITY_MESSAGE_VISITOR_ACCESS:
                        $communityMessageVisitorAccessArr[]    = json_decode($val['content'],true);
                        break;
                    case HikConst::HIK_EVENT_COMMUNITY_EVENT_ACCESS:
                        $communityEventAccessArr[]             = json_decode($val['content'],true);
                        break;
                    case HikConst::HIK_EVENT_COMMUNITY_EVENT_ALARM:
                        $communityEventAlarmArr[]              = json_decode($val['content'],true);
                        break;
                    case HikConst::HIK_EVENT_COMMUNITY_EVENT_INTERCOM:
                        $communityEventIntercomArr[]           = json_decode($val['content'],true);
                        break;
                    case HikConst::HIK_EVENT_COMMUNITY_ADVERT_STATE:
                        $communityAdvertStateArr[]             = json_decode($val['content'],true);
                        break;
                    case HikConst::HIK_EVENT_COMMUNITY_MESSAGE_AUDIT_STATE:
                        $communityMessageAuditStateArr[]       = json_decode($val['content'],true);
                        break;
                    case HikConst::HIK_EVENT_COMMUNITY_EVENT_PARKING_PASS_VEHICLE:
                        $communityEventParkingPassVehicleArr[] = json_decode($val['content'],true);
                        break;
                }
            }

//            fdump_api($communityEventAlarmArr,'$communityEventAlarmArr',1);
//
//            fdump_api($data,'$data',1);
            // todo 暂时只处理门禁事件
            if (!empty($communityEventAccessArr)) {
               // fdump_api($communityEventAccessArr, '$communityEventAccessArr', 1);
                $configDataService = new ConfigDataService();
                $configData = $configDataService->getDataOne(['name' => 'toHandleHouseUserLogTraits']);
                $toHandleHouseUserLogTraits = isset($configData['value']) && intval($configData['value']) > 0 ? intval($configData['value']) : 0;
                if (count($communityEventAccessArr) > 50 && ($debug || $toHandleHouseUserLogTraits == 1)) {
                    $jobParam = ['jobType' => 'filterCommunityEventAccess', 'communityEventAccessArr' => $communityEventAccessArr];
                    $job_id = $this->traitCommonHandleHouseUserLog($jobParam);
                    //fdump_api(['job_id' => $job_id, 'jobParam' => $jobParam], '$communityEventAccessArr', 1);
                } else {
                    $this->filterCommunityEventAccess($communityEventAccessArr);
                }
            }
            if (!empty($communityEventAlarmArr)) {
                $this->filterCommunityEventAlarm($communityEventAlarmArr);
                // todo 走下队列 告警器的告警对应按照设置走下工单
                $queueData  = [];
                $queueData['thirdProtocol'] = DeviceConst::HIK_YUNMO_NEIBU_SHEQU;
                $queueData['jobType']       = 'alarmToWorkOrder';
                $job_id = $this->traitCommonHikToJob($queueData);
            }
            if (!empty($communityMessageAccessStateArr)) {
                $this->filterCommunityMessageAccessState($communityMessageAccessStateArr);
                $queueData  = [];
                $queueData['thirdProtocol'] = DeviceConst::HIK_YUNMO_NEIBU_SHEQU;
                $queueData['jobType']       = 'handleCommunityMessageAccessState';
                $job_id = $this->traitCommonHikToJob($queueData);
                if (isset($job_id)) {
                    $param['job_id']     = $job_id;
                }
            }
//            // 消费掉这个消息
            $params = [
                'consumerId' => $consumerId,
            ];
            $offsetResult = $this->sFaceHikCloudNeiBuDeviceService->offsetCustomer($params);
            //fdump_api(['params' => $params, 'offsetResult' => $offsetResult], '$communityEventAccessArr', 1);
        }
        Cache::store('redis')->tag($cacheTag)->set($cacheKey,null);
        return true;
    }
    
    public function filterCommunityEventAlarm($communityEventAlarmArr) {
        $dbCommunityEventAlarm = new CommunityEventAlarm();
        $nowTime = time();
        $deviceArr = [];
        $db_house_camera_device = new HouseCameraDevice();
        foreach ($communityEventAlarmArr as $event) {
            $eventId = isset($event['eventId']) ? $event['eventId'] : '';
            if (!$eventId) {
                // 事件ID 不存在暂时不做记录
                return false;
            }
            if ('AlarmEvent' != $event['eventType']) {
                // 目前只处理 AlarmEvent 报警事件	
                return false;
            }
            $whereAlarm = [];
            $whereAlarm[] = ['event_id', '=', $eventId];
            $alarmInfo = $dbCommunityEventAlarm->getOne($whereAlarm, 'id');
            if ($alarmInfo && !is_array($alarmInfo)) {
                $alarmInfo = $alarmInfo->toArray();
            }
            if (!empty($alarmInfo)) {
                // 目前不做记录更新 只添加
                return false;
            }
            $event_time = isset($event['dateTime']) && $event['dateTime'] ? strtotime($event['dateTime']) : time();
            $dateTime = isset($event['dateTime']) && $event['dateTime'] ? trim($event['dateTime']) : '';
            $deviceId = isset($event['deviceId']) && $event['deviceId'] ? trim($event['deviceId']) : '';
//            if (isset($deviceArr[$deviceId]) && $deviceArr[$deviceId]) {
//                $deviceInfo = $deviceArr[$deviceId];
//            } else {
//                $whereDevice = [];
//                $whereDevice[] = ['cloud_device_id', '=', $deviceId];
//                $whereDevice[] = ['is_del',          '=', 0];
//                $deviceField = 'device_id,village_id,floor_id,public_area_id,device_name,device_sn,thirdProtocol';
//                $deviceInfo = $this->dbHouseFaceDevice->getOne($whereDevice, $deviceField);
//                if ($deviceInfo && !is_array($deviceInfo)) {
//                    $deviceInfo = $deviceInfo->toArray();
//                }
//            }
//            if (!empty($deviceInfo)) {
//                $deviceArr[$deviceId] = $deviceInfo;
//            } else {
//                // 没有对应设备不进行相关处理
//                return false;
//            }
            $deviceName = isset($event['deviceName']) && $event['deviceName'] ? trim($event['deviceName']) : '';
            $channelId = isset($event['channelId']) && $event['channelId'] ? trim($event['channelId']) : '';
            $device_serial= isset($event['deviceSerial']) && $event['deviceSerial'] ? trim($event['deviceSerial']) : '';
            if( empty($channelId) && isset($event['channelID']) && $event['channelID']){
                $channelId= trim($event['channelID']);
                if($device_serial && is_numeric($channelId) && strlen($channelId)<15){
                    $houseDeviceChannelDb=new HouseDeviceChannel();
                    $whereArr=array(['deviceSerial','=',$device_serial],['deviceId','=',$deviceId],['channelNo','=',$channelId]);
                    $deviceChannel=$houseDeviceChannelDb->getOne($whereArr,'*','channel_id desc');
                    if($deviceChannel && isset($deviceChannel['channelId']) && $deviceChannel['channelId']){
                        $channelId=$deviceChannel['channelId'];
                    }
                }
            }
            $channelName = isset($event['channelName']) && $event['channelName'] ? trim($event['channelName']) : '';
            $deviceModel = isset($event['deviceModel']) && $event['deviceModel'] ? trim($event['deviceModel']) : '';
            $deviceType = isset($event['deviceType']) && $event['deviceType'] ? trim($event['deviceType']) : '';
            $eventType = isset($event['eventType']) && $event['eventType'] ? trim($event['eventType']) : '';
            $eventCode = isset($event['eventCode']) && $event['eventCode'] ? trim($event['eventCode']) : '';
            $eventDescription = isset($event['eventDescription']) && $event['eventDescription'] ? trim($event['eventDescription']) : '';
            $eventSource = isset($event['eventSource']) && $event['eventSource'] ? trim($event['eventSource']) : '';
            $pictureURL = isset($event['pictureURL']) && $event['pictureURL'] ? trim($event['pictureURL']) : '';
            $eventRemark = isset($event['eventRemark']) && $event['eventRemark'] ? trim($event['eventRemark']) : '';
            $bodyTemperature = isset($event['bodyTemperature']) && $event['bodyTemperature'] ? trim($event['bodyTemperature']) : '';
            $masks = isset($event['masks']) && $event['masks'] ? trim($event['masks']) : '';
            $rawData = isset($event['rawData']) && $event['rawData'] ? trim($event['rawData']) : '';
            $licencePlate = isset($event['licencePlate']) && $event['licencePlate'] ? trim($event['licencePlate']) : '';
            $communityId = isset($event['communityId']) && $event['communityId'] ? trim($event['communityId']) : '';
            $communityName = isset($event['communityName']) && $event['communityName'] ? trim($event['communityName']) : '';
            $personId = isset($event['personId']) && $event['personId'] ? trim($event['personId']) : '';
            $personName = isset($event['personName']) && $event['personName'] ? trim($event['personName']) : '';
            $fileHandle   = new FileHandle();
            if ($pictureURL) {
                try {
                    $rand_num = date('Ymd');// 换成日期存储
                    $local_url_save = '/upload/alarm_log/'.$rand_num.'/'.$eventId.'.jpg';
                    $savePath = root_path() . '..' . $local_url_save;
                    $saveImg = $this->traitSaveImage($pictureURL, $savePath);
                    $local_type = 'image';
                    if ($saveImg) {
                        $local_url = $local_url_save;
                        if ($fileHandle->check_open_oss()) {
                            try {
                                fdump_api(['local_url_save'=>'.'.$local_url_save], '00alarm_log_img',false);
                                $info = $fileHandle->upload('.'.$local_url_save);
                                fdump_api(['local_url_save'=>'.'.$local_url_save,'info' => $info], '00alarm_log_img', true);
                                @unlink('.'.$local_url_save);
                            } catch (\Exception $e) {
                                fdump_api(['msg' => 'oss上传大图执行错误', 'message' => $e->getMessage(), 'pictureURL' => $pictureURL, 'savePath' => $savePath, 'info' => $info], '00alarm_log_img', true);
                            } 
                        }
                    } else {
                        $local_url = $pictureURL;
                    }
                } catch (\Exception $e) {
                    $local_type = 'image';
                    $local_url = $pictureURL;
                }
            } else {
                $local_url = '';
                $local_type = '';
            }
            $alarm_info = [
                'business_type' => EventAlarmConst::HIK_NEI_BU_EVENT_ALARM, 'event_id' => $eventId,
                'date_time' => $dateTime, 'device_id' => $deviceId,
                'device_name' => $deviceName, 'channel_id' => $channelId,
                'channel_name' => $channelName, 'device_model' => $deviceModel,
                'device_type' => $deviceType, 'event_type' => $eventType,
                'event_code' => $eventCode, 'event_description' => $eventDescription,
                'event_source' => $eventSource, 'picture_url' => $pictureURL,
                'event_remark' => $eventRemark, 'body_temperature' => $bodyTemperature,
                'masks' => $masks, 'raw_data' => $rawData,
                'licence_plate' => $licencePlate, 'community_id' => $communityId,
                'community_name' => $communityName, 'person_id' => $personId,
                'person_name' => $personName, 'local_url' => $local_url,'local_type' => $local_type,
                'event_time' => $event_time, 'add_time' => $nowTime,
                'device_serial'=>$device_serial
            ];
            
            $eventRemark=$eventRemark ? strtolower($eventRemark):'';
            $whereCamera = [];
            $whereCamera[] = ['cloud_device_id', '=', $deviceId];
            $whereCamera[] = ['camera_sn', '=', $device_serial];
            $whereCamera[] = ['camera_status', 'in', [0,1]];
            $camera_device_obj=$db_house_camera_device->getOne($whereCamera,'camera_id,camera_sn,village_id,cloud_device_id');
            if($camera_device_obj && !$camera_device_obj->isEmpty()){
                if($eventRemark=='offline' || $eventCode==10201){
                    //设备离线
                    $db_house_camera_device->saveOne(array('camera_id'=>$camera_device_obj['camera_id']),array('camera_status'=>1));
                }else{
                    $db_house_camera_device->saveOne(array('camera_id'=>$camera_device_obj['camera_id']),array('camera_status'=>0));
                }
                $alarm_info['village_id']=$camera_device_obj['village_id'];
                $alarm_info['equipment_id']=$camera_device_obj['camera_id'];
            }
            
            $dbCommunityEventAlarm->add($alarm_info);
            
        }
        return true;
    }
    
    protected $dbHouseUserLog, $dbHouseFaceDevice, $dbFaceUserBindDevice, $dbHouseVillageUserBind, $sHouseVillageService;
    public function filterCommunityEventAccess($communityEventAccessArr = [], $debug = false) {
        if (empty($communityEventAccessArr)) {
            return [];
        }
        if (!$this->dbHouseUserLog) {
            $this->dbHouseUserLog = new HouseUserLog();
        }
        if (!$this->dbHouseFaceDevice) {
            $this->dbHouseFaceDevice = new HouseFaceDevice();
        }
        if (!$this->dbFaceUserBindDevice) {
            $this->dbFaceUserBindDevice = new FaceUserBindDevice();
        }
        if (!$this->dbHouseVillageUserBind) {
            $this->dbHouseVillageUserBind = new HouseVillageUserBind();
        }
        if (!$this->sHouseVillageService) {
            $this->sHouseVillageService = new HouseVillageService();
        }
        $configDataService = new ConfigDataService();
        $configData = $configDataService->getDataOne(['name' => 'toHandleHouseUserLogTraits']);
        $toHandleHouseUserLogTraits = isset($configData['value']) && intval($configData['value']) > 0 ? intval($configData['value']) : 0;
        $deviceArr = [];
        foreach ($communityEventAccessArr as $key => $event) {
            if (0 && ($debug || $toHandleHouseUserLogTraits == 1)) {
                $jobParam = ['jobType' => 'filterCommunityEventAccessLog', 'event' => $event, 'deviceArr' => $deviceArr];
                $time = 1;
                $job_id = $this->traitCommonHandleHouseUserLog($jobParam, $time);
                fdump_api(['job_id' => $job_id, 'time' => $time, 'jobParam' => $jobParam], '$filterCommunityEventAccess', 1);
            } else {
                $this->filterCommunityEventAccessLog($event, $deviceArr);
            }
        }
        return true;
    }
    
    public function filterCommunityEventAccessLog($event, $deviceArr = [], $debug = false) {
        $ymdH = date('YmdH');
        fdump_api(['line' => __LINE__, 'event' => $event, 'msg' => '事件信息'], 'face/filterCommunityEventAccessLog' . $ymdH);
        $eventId = isset($event['eventId']) ? $event['eventId'] : '';
        if (!$eventId) {
            // 事件ID 不存在暂时不做记录
            fdump_api(['line' => __LINE__, 'event' => $event, 'msg' => '事件ID 不存在暂时不做记录'], 'face/errFilterCommunityEventAccessLog');
            return false;
        }
        if ('AccessEvent' != $event['eventType']) {
            // 目前只处理 AccessEvent	门禁事件
            fdump_api(['line' => __LINE__, 'event' => $event,  'msg' => '目前只处理 AccessEvent	门禁事件'], 'face/errFilterCommunityEventAccessLog');
            return false;
        }
        if (!$this->dbHouseUserLog) {
            $this->dbHouseUserLog = new HouseUserLog();
        }
        if (!$this->dbHouseFaceDevice) {
            $this->dbHouseFaceDevice = new HouseFaceDevice();
        }
        if (!$this->dbFaceUserBindDevice) {
            $this->dbFaceUserBindDevice = new FaceUserBindDevice();
        }
        if (!$this->dbHouseVillageUserBind) {
            $this->dbHouseVillageUserBind = new HouseVillageUserBind();
        }
        $dateTime = isset($event['dateTime']) && $event['dateTime'] ? strtotime($event['dateTime']) : time();
        $nowtime=time();
        $whereLog = [];
        $whereLog[] = ['eventId', '=', $eventId];
        $whereLog[] = ['log_time', '=', $dateTime];
        $logInfo = $this->dbHouseUserLog->getFind($whereLog, 'log_id');
        if ($logInfo && !is_array($logInfo)) {
            $logInfo = $logInfo->toArray();
        }
        if ($logInfo && isset($logInfo['log_id']) && intval($logInfo['log_id']) > 0) {
            // 目前不做记录更新 只添加
            fdump_api(['line' => __LINE__, 'event' => $event, 'logInfo' => $logInfo, 'msg' => '目前不做记录更新 只添加'], 'face/errFilterCommunityEventAccessLog');
            return false;
        }
        $deviceId = isset($event['deviceId']) && $event['deviceId'] ? $event['deviceId'] : '';
        $deviceSerial = isset($event['deviceSerial']) && $event['deviceSerial'] ? $event['deviceSerial'] : '';
        if (isset($deviceArr[$deviceId]) && $deviceArr[$deviceId]) {
            $deviceInfo = $deviceArr[$deviceId];
        } else {
            $whereDevice = [];
            if ($deviceSerial) {
                $whereDevice[] = ['device_sn', '=', $deviceSerial];
            } else {
                $whereDevice[] = ['cloud_device_id', '=', $deviceId];
            }
            $whereDevice[] = ['is_del',          '=', 0];
            $deviceField = 'device_id,village_id,floor_id,public_area_id,device_name,device_sn,thirdProtocol';
            $deviceInfo = $this->dbHouseFaceDevice->getOne($whereDevice, $deviceField);
            if ($deviceInfo && !is_array($deviceInfo)) {
                $deviceInfo = $deviceInfo->toArray();
            }
        }
        if (!empty($deviceInfo)) {
            $deviceArr[$deviceId] = $deviceInfo;
        } else {
            // 没有对应设备不进行相关处理
            fdump_api(['line' => __LINE__, 'event' => $event, 'msg' => '没有对应设备不进行相关处理'], 'face/errFilterCommunityEventAccessLog');
            return false;
        }
        $log_detail = $event;
        $path             = isset($event['path'])             && $event['path']             ? trim($event['path'])                 : '';
        $address          = isset($event['address'])          && $event['address']          ? trim($event['address'])              : '';
        $personId         = isset($event['personId'])         && $event['personId']         ? trim($event['personId'])             : '';
        $personName       = isset($event['personName'])       && $event['personName']       ? trim($event['personName'])           : '';
        $cardNumber       = isset($event['cardNumber'])       && $event['cardNumber']       ? trim($event['cardNumber'])           : '';
        $deviceName       = isset($event['deviceName'])       && $event['deviceName']       ? trim($event['deviceName'])           : $deviceInfo['device_name'];
        $channelId        = isset($event['channelId'])        && $event['channelId']        ? intval($event['channelId'])          : '';
        $channelName      = isset($event['channelName'])      && $event['channelName']      ? trim($event['channelName'])          : '';
        $channelName      = isset($event['channelName'])      && $event['channelName']      ? trim($event['channelName'])          : '';
        $eventCode        = isset($event['eventCode'])        && $event['eventCode']        ? strval($event['eventCode'])          : '';
        $eventCodeMsg     = $this->filterHikNeiBuEventCode($eventCode);                                                            
        $eventDescription = isset($event['eventDescription']) && $event['eventDescription'] ? trim($event['eventDescription'])     : '';
        $eventSource      = isset($event['eventSource'])      && $event['eventSource']      ? trim($event['eventSource'])          : '';
        $pictureURL       = isset($event['pictureURL'])       && $event['pictureURL']       ? trim($event['pictureURL'])           : '';
        $bodyTemperature  = isset($event['bodyTemperature'])  && $event['bodyTemperature']  ? floatval($event['bodyTemperature'])  : 0;
        $masks            = isset($event['masks'])            && $event['masks']            ? trim($event['masks'])                : '';
        $licencePlate     = isset($event['licencePlate'])     && $event['licencePlate']     ? trim($event['licencePlate'])         : '';
        $communityId      = isset($event['communityId'])      && $event['communityId']      ? trim($event['communityId'])          : '';
        $communityName    = isset($event['communityName'])    && $event['communityName']    ? trim($event['communityName'])        : '';
        $village_id     = $deviceInfo['village_id'];
        $log_more_id    = isset($deviceInfo['floor_id']) && $deviceInfo['floor_id'] ? $deviceInfo['floor_id'] : 0;
        $device_id      = $deviceInfo['device_id'];
        $third_protocol = $deviceInfo['thirdProtocol'];
        $device_sn      = $deviceInfo['device_sn'];
        $log_from       = $this->filterLogFrom($eventCode);
        if (isset($event['dateTime'])) {
            unset($log_detail['dateTime']);
        }
        if (isset($event['address'])) {
            unset($log_detail['address']);
        }
        if (isset($event['personName'])) {
            unset($log_detail['personName']);
        }
        if (isset($event['deviceName'])) {
            unset($log_detail['deviceName']);
        }
        if (isset($event['channelId'])) {
            unset($log_detail['channelId']);
        }
        $toImgJob = false;
        $jobParam = [];
        if ($pictureURL) {
            $face_img = $pictureURL;
            $configDataService = new ConfigDataService();
            $configData = $configDataService->getDataOne(['name' => 'toHandleHouseUserLogTraits']);
            $toHandleHouseUserLogTraits = isset($configData['value']) && intval($configData['value']) > 0 ? intval($configData['value']) : 0;
            if ($debug || $toHandleHouseUserLogTraits == 1) {
                $toImgJob = true;
                $jobParam = ['jobType' => 'handleUserLogToSaveImage', 'eventId' => $eventId, 'picture_url' => $pictureURL];
            } else {
                try {
                    $rand_num = date('Ymd');// 换成日期存储
                    $face_img_save = '/upload/face_log/'.$rand_num.'/'.$eventId.'.jpg';
                    $savePath = root_path() . '..' . $face_img_save;
                    $saveImg = $this->traitSaveImage($pictureURL, $savePath);
                    if ($saveImg) {
                        $face_img = $face_img_save;
                    } else {
                        $face_img = $pictureURL;
                    }
                } catch (\Exception $e) {
                    $face_img = $pictureURL;
                }
            }
            $log_detail['face_img'] = $face_img;
        } else {
            $face_img = '';
        }
        $log = [
            'log_time'         => $dateTime,
            'eventId'          => $eventId,
            'log_business_id'  => $village_id,
            'log_more_id'      => $log_more_id,
            'device_id'        => $device_id,
            'temperature'      => $bodyTemperature,
            'cut_image'        => $pictureURL,
            'card_number'      => $cardNumber,
            'person_code'      => $personId,
            'person_name'      => $personName,
            'third_protocol'   => $third_protocol,
            'device_sn'        => $device_sn,
            'deviceChannelId'  => $channelId,
            'log_detail'       => json_encode($log_detail, JSON_UNESCAPED_UNICODE),
        ];
        if (isset($log_detail['face_img']) && trim($log_detail['face_img'])) {
            $log['cut_image'] = trim($log_detail['face_img']);
        }
        if ($personId) {
            $whereAbout = [];
            $whereAbout[] = ['bind_type', '=', DeviceConst::HK_UID_CLOUD_USER];
            $whereAbout[] = ['person_id', '=', $personId];
            $aboutInfo = $this->dbFaceUserBindDevice->getOneOrder($whereAbout, 'bind_id');
            if ($aboutInfo && !is_array($aboutInfo)) {
                $aboutInfo = $aboutInfo->toArray();
            }
            if (isset($aboutInfo['bind_id']) && $aboutInfo['bind_id']) {
                $whereBind = [];
                $whereBind[] = ['village_id', '=', $village_id];
                $whereBind[] = ['uid',        '=', $aboutInfo['bind_id']];
                $whereBind[] = ['status',     '=', 1];
                $userBind = $this->dbHouseVillageUserBind->getOne($whereBind, 'pigcms_id, uid, name, village_id, single_id, floor_id, layer_id, vacancy_id', 'type ASC');
                if ($userBind && !is_array($userBind)) {
                    $userBind = $userBind->toArray();
                }
                if (isset($userBind['pigcms_id'])) {
                    $log['log_bind_id'] = $userBind['pigcms_id'];
                }
                $log['uid'] = $aboutInfo['bind_id'];
                if (isset($userBind['name']) && $userBind['name'] && !$log['person_name']) {
                    $log['person_name'] = $userBind['name'];
                }
                if (!$address && isset($userBind['vacancy_id']) && $userBind['vacancy_id']) {
                    $address = $this->sHouseVillageService->getSingleFloorRoom($userBind['single_id'], $userBind['floor_id'], $userBind['layer_id'], $userBind['vacancy_id'], $userBind['village_id']);
                }
            }
        }
        $log_name       = $address .' '. $deviceName;
        $log['log_from'] = $log_from;
        $log['log_name'] = $log_name;
        if ($eventCode == 10126) {
            $log['log_status'] = 1;
            $status = 1;
        } else if($personId) {
            $log['log_status'] = 0;
            $status = 0;
        } else {
            $log['log_status'] = 1;
            $status = 1;
        }
        $log['add_time'] = $nowtime;
        fdump_api(['line' => __LINE__, 'log' => $log, 'event' => $event, 'deviceArr' => $deviceArr], 'face/addDataLog' . $ymdH);
        $log_id = $this->dbHouseUserLog->addData($log);
        if ($log_id && $toImgJob) {
            if($device_id){
                $this->dbHouseFaceDevice->saveData(array('device_id'=>$device_id),array('last_open_time'=>$nowtime));
            }
            $jobParam['log_id'] = $log_id;
            $job_id = $this->traitCommonHandleHouseUserLog($jobParam, 15);
            fdump_api(['line' => __LINE__, 'job_id' => $job_id, 'time' => 15, 'jobParam' => $jobParam], 'face/filterCommunityEventAccessLog' . $ymdH);
        }
        if ($log_id && isset($log['log_bind_id']) && $log['log_bind_id']) {
            $tip = array(
                'bind_id' => isset($log['log_bind_id']) ? $log['log_bind_id'] : 0,
                'uid'     => isset($log['uid']) ? $log['uid'] : 0,
                'type'    => 1,
                'village_id'  => $village_id,
                'camera_sn'   => $device_sn,
                'city_id'     => 0,
                'camera_time' => time(),
                'camera_img'  => $face_img,
                'tip_time'    => $dateTime,
                'status'      => $status,
                'tip_txt'     => serialize($log_detail)
            );
            (new HouseCameraTip())->addOne($tip);
        }
        return true;
    }
    
    public function handleUserLogToSaveImage($params)
    {
        $log_id = isset($params['log_id']) ? intval($params['log_id']) : 0;
        $eventId = isset($params['eventId']) ? trim($params['eventId']) : '';
        fdump_api(['msg' => '通行记录图片$params', 'line' => __LINE__, 'params' => $params], 'face/dbHouseUserLog', 1);
        if (!$log_id && !$eventId) {
            return true;
        }
        $picture_url = isset($params['picture_url']) ? trim($params['picture_url']) : '';
        $updateData = [];
        try {
            $rand_num = date('Ymd');// 换成日期存储
            $face_img_save = '/upload/face_log/' . $rand_num . '/' . $eventId . '.jpg';
            fdump_api(['msg' => '通行记录图片-存储', 'line' => __LINE__, 'eventId' => $eventId, 'face_img_save' => $face_img_save, 'picture_url' => $picture_url], 'face/dbHouseUserLog', 1);
            $savePath = app()->getRootPath() . '..' . $face_img_save;
            $savePath = str_replace('v20/..', '', $savePath);
            $saveImg = $this->traitSaveImage($picture_url, $savePath);
            fdump_api(['msg' => '通行记录图片-压缩存储结果', 'line' => __LINE__, 'icture_url' => $picture_url, 'savePath' => $savePath, 'savePathExists' => file_exists($savePath)], 'face/dbHouseUserLog', 1);
            if ($saveImg && file_exists($savePath)) {
                $updateData['cut_image'] = $face_img_save;
            }
        } catch (\Exception $e) {
            fdump_api(['msg' => '通行记录图片-存储图片', 'line' => __LINE__, 'errMsg' => $e->getMessage(), 'errLine' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode()], 'face/dbHouseUserLog', 1);
        }
        fdump_api(['msg' => '通行记录图片', 'line' => __LINE__, 'updateData' => $updateData], 'face/dbHouseUserLog', 1);
        if (!$this->dbHouseUserLog) {
            $this->dbHouseUserLog = new HouseUserLog();
        }
        $whereLog = [];
        if ($eventId) {
            $whereLog[] = ['eventId', '=', $eventId];
        } else {
            $whereLog[] = ['log_id', '=', $log_id];
        }
        if (!empty($updateData)) {
            fdump_api(['msg' => '更改识别图片', 'line' => __LINE__, 'whereLog' => $whereLog, 'updateData' => $updateData], 'face/dbHouseUserLog', 1);
            $this->dbHouseUserLog->updateThis($whereLog, $updateData);
        }
        return true;
    }
    
    // 记录下下发事件
    protected function filterCommunityMessageAccessState($communityMessageAccessStateArr = []) {
        if (empty($communityMessageAccessStateArr)) {
            return [];
        }
        $dbDeviceAccessState = new DeviceAccessState();
        foreach ($communityMessageAccessStateArr as $event) {
            if (!isset($event['messageCode'])) {
                continue;
            }
            switch ($event['messageCode']) {
                case HikConst::HIK_EVENT_COMMUNITY_MESSAGE_ACCESS_STATE_AUTH_FACE:
                case HikConst::HIK_EVENT_COMMUNITY_MESSAGE_ACCESS_STATE_AUTH_FINGER:
                case HikConst::HIK_EVENT_COMMUNITY_MESSAGE_ACCESS_STATE_AUTH_CARD:
                case HikConst::HIK_EVENT_COMMUNITY_MESSAGE_ACCESS_STATE_AUTH_VISITOR:
                case HikConst::HIK_EVENT_COMMUNITY_MESSAGE_ACCESS_STATE_AUTH_VISITOR_QR:
                case HikConst::HIK_EVENT_COMMUNITY_MESSAGE_ACCESS_STATE_AUTH_ACTIVE_PASS:
                case HikConst::HIK_EVENT_COMMUNITY_MESSAGE_ACCESS_STATE_AUTH_FACE_DELETE:
                case HikConst::HIK_EVENT_COMMUNITY_MESSAGE_ACCESS_STATE_AUTH_PERSON_ALL:
                case HikConst::HIK_EVENT_COMMUNITY_MESSAGE_ACCESS_STATE_SUCCESS_PERSON_DELETE:
                    // authType	  权限类型 person-人员; card-卡;face-人脸;finger-指纹
                    // authStatus 下发状态：success-下发成功；failed-下发失败；process-下发中；deleteFailed-权限删除失败；notDown-未下发
                    // reason	  错误码
                    // describe	  中文失败描述
                    // detailNo   authType为card时是卡号，为finger时是指纹编号
                    // cardNumber 卡号
                    $messageCode   = isset($event['messageCode']) && $event['messageCode'] ? trim($event['messageCode']) : '';
                    $personId      = isset($event['personId'])    && $event['personId']    ? trim($event['personId'])    : '';
                    $personName    = isset($event['personName'])  && $event['personName']  ? trim($event['personName'])  : '';
                    $deviceId      = isset($event['deviceId'])    && $event['deviceId']    ? trim($event['deviceId'])    : '';
                    $deviceName    = isset($event['deviceName'])  && $event['deviceName']  ? trim($event['deviceName'])  : '';
                    $accessState   = isset($event['accessState']) && $event['accessState'] ? trim($event['accessState']) : '';
                    $issuedTimeTxt = isset($event['issuedTime'])  && $event['issuedTime']  ? trim($event['issuedTime'])  : '';
                    $cardNumber    = isset($event['cardNumber'])  && $event['cardNumber']  ? trim($event['cardNumber'])  : '';
                    $reason        = isset($event['reason'])      && $event['reason']      ? trim($event['reason'])      : '';
                    $describe      = isset($event['describe'])    && $event['describe']    ? trim($event['describe'])    : '';
                    $results       = isset($event['results'])     && $event['results']     ? $event['results']           : [];
                    $whereRepeat = [];
                    $whereRepeat[] = ['message_code',    '=', $messageCode];
                    $whereRepeat[] = ['person_id',       '=', $personId];
                    $whereRepeat[] = ['device_id',        '=', $deviceId];
                    $whereRepeat[] = ['issued_time_txt', '=', $issuedTimeTxt];
                    $whereRepeat[] = ['card_number',     '=', $cardNumber];
                    $repeat = $dbDeviceAccessState->getOne($whereRepeat);
                    if ($repeat && !is_array($repeat)) {
                        $repeat = $repeat->toArray();
                    }
                    if (empty($repeat) || !$repeat) {
                        if ($issuedTimeTxt) {
                            $issuedTime = strtotime($issuedTimeTxt);
                        } else {
                            $issuedTime = 0;
                        }
                        $data = [
                            'message_code'    => $messageCode,
                            'person_id'       => $personId,
                            'person_name'     => $personName,
                            'device_id'       => $deviceId,
                            'device_name'     => $deviceName,
                            'access_state'    => $accessState,
                            'issued_time_txt' => $issuedTimeTxt,
                            'issued_time'     => $issuedTime,
                            'card_number'     => $cardNumber,
                            'reason'          => $reason,
                            'describe'        => $describe,
                            'add_time'        => time(),
                        ];
                        if ($results) {
                            $data['results'] = json_encode($results, JSON_UNESCAPED_UNICODE);
                        }
                        $dbDeviceAccessState->add($data);
                    }
                    break;
            }
        }
    }
    
    /**
     * 海康同步单元信息整理
     * @param array   $deviceInfo    设备信息
     * @param string $village_id   小区id
     * @param string $single_id    楼栋id
     * @param string  $thirdProtocol 协议
     * @param array  $param
     * @return bool
     */
    protected function floorToDeviceFilter(array $deviceInfo,string $village_id,string $single_id,string $thirdProtocol, $param = []) {
        $db_layer  = new HouseVillageLayer();
        $db_floor  = new HouseVillageFloor();
        if ($single_id) {
            $whereFloor    = [];
            $whereFloor[]  = ['status', 'in', [0,1]];
            $whereFloor[]  = ['single_id', '=', $single_id];
            $whereFloor[]  = ['floor_number', '<>', ''];
            $floorInfoArrId = $db_floor->getList($whereFloor, 'floor_id,floor_name,floor_number');
        } elseif (isset($deviceInfo['layer_id']) && $deviceInfo['layer_id']) {
            $layerIds = explode(',', $deviceInfo['layer_id']);
            $whereLayer    = [];
            $whereLayer[]  = ['layer_id', 'in', $layerIds];
            $whereLayer[]  = ['status', 'in', [0,1]];
            $whereLayer[]  = ['village_id', '=', $village_id];
            $floorIds = $db_layer->getOneColumn($whereLayer, 'floor_id');
        } elseif (isset($deviceInfo['floor_id']) && $deviceInfo['floor_id']) {
            $floorIds = explode(',', $deviceInfo['floor_id']);
        } else {
            $floorInfoArrId = [];
        }
        if (isset($floorIds) && empty($floorIds)) {
            $whereFloor    = [];
            $whereFloor[]  = ['floor_id', 'in', $floorIds];
            $whereFloor[]  = ['status', 'in', [0,1]];
            $whereFloor[]  = ['village_id', '=', $village_id];
            $whereFloor[]  = ['floor_number', '<>', ''];
            $floorInfoArrId = $db_floor->getList($whereFloor, 'floor_id,floor_name,floor_number');
        }

        if (!empty($floorInfoArrId) && !is_array($floorInfoArrId)) {
            $floorInfoArrId = $floorInfoArrId->toArray();
        }
        if (!empty($floorInfoArrId)) {
            $third_name              = isset($param['third_name'])  ? $param['third_name']  : '';
            $this->syn_status        = DeviceConst::BINDS_SYN_BUILD_UNITS_START;
            $this->syn_status_txt    = "下发同步楼栋的单元开始";
            $this->err_reason        = "";
            $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param];
            $this->second_bind_type  = DeviceConst::BIND_BUILD;
            $this->second_bind_id    = $single_id;
            $this->third_name        = $third_name;
            if ($this->build_third_id) {
                $this->third_second_bind_id = $this->build_third_id;
            }
            if ($this->village_third_id) {
                $this->third_three_bind_id  = $this->village_third_id;
            }
            $this->recordDeviceBindFilterBox($param);
            $step_num = $this->step_num + 1;
            $this->step_num++;
            $this->clearRecordDeviceBindFilter();
            foreach ($floorInfoArrId as $floor) {
                switch ($thirdProtocol) {
                    case DeviceConst::HIK_YUNMO_NEIBU_SHEQU:
                        $bind_type   = DeviceConst::HK_TO_CLOUD_FLOOR;
                        $bind_id     = $floor['floor_id'];
                        $floor_param = [];
                        $floor_param['bind_type']     = $bind_type;
                        $floor_param['bind_id']       = $bind_id;
                        $floor_param['thirdProtocol'] = $thirdProtocol;
                        $floor_param['floor']         = $floor;
                        $floor_param['device_type']   = $this->face_device_type;
                        $floor_param['buildingId']    = $this->build_third_id;

                        $param['jobType']        = 'singleFloorToHikDeviceCloud';
                        $param['floor_param']    = $floor_param;
                        $param['step_num']       = $step_num;
                        $job_id = $this->traitCommonHikCloudUnits($param);
                        if (isset($job_id)) {
                            $param['job_id']     = $job_id;
                        }
                        $this->syn_status        = DeviceConst::BINDS_SYN_UNIT_QUEUE;
                        $this->syn_status_txt    = '下发同步单元队列';
                        $this->err_reason        = "";
                        $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__];
                        $this->second_bind_type  = DeviceConst::BIND_UNIT;
                        $this->second_bind_id    = $bind_id;
                        $this->third_name        = isset($floor['floor_name']) && $floor['floor_name'] ? $floor['floor_name'] : '单元——'.$bind_id;
                        if ($this->build_third_id) {
                            $this->third_second_bind_id = $this->build_third_id;
                        }
                        if ($this->village_third_id) {
                            $this->third_three_bind_id  = $this->village_third_id;
                        }
                        $this->recordDeviceBindFilterBox($param);

                        // todo 走队列执行 同步单元
                        unset($param['floor_param']);
//                        $result = $this->singleFloorToHikDeviceCloud($floor_param, $param);
                        break;
                }
            }
            $this->clearRecordDeviceBindFilter();
            $this->syn_status        = DeviceConst::BINDS_SYN_BUILD_UNITS_END;
            $this->syn_status_txt    = "下发同步楼栋的单元单个队列结束";
            $this->err_reason        = "";
            $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param];
            $this->second_bind_type  = DeviceConst::BIND_BUILD;
            $this->second_bind_id    = $single_id;
            $this->third_name        = $third_name;
            if ($this->build_third_id) {
                $this->third_second_bind_id = $this->build_third_id;
            }
            if ($this->village_third_id) {
                $this->third_three_bind_id  = $this->village_third_id;
            }
            $this->recordDeviceBindFilterBox($param);
        } else {
            $third_name              = isset($param['third_name'])  ? $param['third_name']  : '';
            $this->syn_status        = DeviceConst::BINDS_SYN_BUILD_UNITS_ERR;
            $this->syn_status_txt    = "下发同步楼栋的单元队列失败(无对应单元)";
            $this->err_reason        = "下发同步楼栋的单元队列失败(无对应单元)";
            $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param, 'whereFloor' => $whereFloor, 'deviceInfo' => $deviceInfo];
            $this->second_bind_type  = DeviceConst::BIND_BUILD;
            $this->second_bind_id    = $single_id;
            $this->third_name        = $third_name;
            if ($this->build_third_id) {
                $this->third_second_bind_id = $this->build_third_id;
            }
            if ($this->village_third_id) {
                $this->third_three_bind_id  = $this->village_third_id;
            }
            $this->recordDeviceBindFilterBox($param);
            $this->step_num++;
        }
        return true;
    }

    /**
     * 缺少海康云眸内部应用社区ID记录错误
     * @param $param
     * @return array
     * @throws \think\Exception
     */
    protected function filterNonExistentVillage($param) {
        $queueData = [
            'thirdProtocol'  => $this->thirdProtocol,
            'village_id'     => $this->village_id,
            'device_id'      => $this->device_id,
            'device_sn'      => $this->device_sn,
            'deviceType'     => $this->device_equipment_type,
            'device_type'    => $this->face_device_type,
            'step_num'       => $this->step_num + 1,
            'orderGroupId'   => $this->order_group_id,
            'orderGroupType' => $this->order_group_type,
        ];
        $job_id = $this->traitCommonHikCloudVillages($queueData);
        $this->syn_status_txt    = '小区未同步(社区ID不能为空,触发一次同步小区)';
        $this->err_reason        = "海康云眸内部社区ID为空";
        $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param];
        $this->recordDeviceBindFilterBox($param);
        if ($this->village_user_bind_id) {
            $houseVillageUserBindService = new HouseVillageUserBindService();
            $updateBindParam = [
                'face_img_status' => 2,
                'face_img_reason' => '小区未同步',
            ];
            $houseVillageUserBindService->saveUserBind(['pigcms_id' => $this->village_user_bind_id], $updateBindParam);
        }
        if($param['log_id']){
            (new OwnerService())->updateLog(['log_id'=>$param['log_id']],[
                'status'=>2,
                'msg'=>'小区未同步',
                'last_time'=>time()
            ]);
        }
        return $this->backData($param, '小区未同步设备云', 1015);
    }

    /**
     * 缺少海康云眸内部应用楼栋ID记录错误
     * @param $param
     * @return array
     * @throws \think\Exception
     */
    protected function filterNonExistentSingle($param) {
        $db_single = new HouseVillageSingle();
        $whereSingle    = [];
        $whereSingle[]  = ['id',         '=', $this->single_id];
        $whereSingle[]  = ['village_id', '=', $this->village_id];
        $single = $db_single->getOne($whereSingle, 'id,single_name,single_number,status');
        if ($single && !is_array($single)) {
            $single = $single->toArray();
        }
        if (!isset($single['id'])) {
            $this->syn_status_txt    = '对应楼栋不存在或者已经被删除';
            $this->err_reason        = "对应楼栋不存在或者已经被删除";
            $face_img_reason         = '对应楼栋不存在或者已经被删除';
        } elseif (isset($single['single_number']) && !$single['single_number']) {
            $this->syn_status_txt    = '楼栋编号不能为空';
            $this->err_reason        = "楼栋编号不能为空";
            $face_img_reason         = '楼栋编号不能为空';
        } elseif (isset($single['status']) && $single['status'] == 4) {
            $this->syn_status_txt    = '对应楼栋已经被删除';
            $this->err_reason        = "对应楼栋已经被删除";
            $face_img_reason         = '对应楼栋已经被删除';
        } else {
            $queueData = [
                'thirdProtocol'    => $this->thirdProtocol,
                'village_id'       => $this->village_id,
                'device_id'        => $this->device_id,
                'device_sn'        => $this->device_sn,
                'deviceType'       => $this->device_equipment_type,
                'device_type'      => $this->face_device_type,
                'step_num'         => $this->step_num + 1,
                'orderGroupId'     => $this->order_group_id,
                'orderGroupType'   => $this->order_group_type,
                'village_third_id' => $this->village_third_id,
            ];
            $bind_type    = DeviceConst::HK_TO_CLOUD_SINGLE;
            $bind_id      = $this->single_id;
            $single_param = [];
            $single_param['bind_type']       = $bind_type;
            $single_param['bind_id']         = $bind_id;
            $single_param['thirdProtocol']   = $this->thirdProtocol;
            $single_param['single']          = $single;
            $single_param['device_type']     = $this->face_device_type;
            $single_param['communityId']     = $this->village_third_id;
            $queueData['single_param'] = $single_param;
            $queueData['jobType']      = 'buildToDeviceCloud';

            $job_id = $this->traitCommonHikCloudBuildings($queueData);
            $this->syn_status_txt    = '楼栋未同步(楼栋ID不能为空,触发一次同步楼栋)';
            $this->err_reason        = "海康云眸内部楼栋ID为空";
            $face_img_reason         = '楼栋未同步';
        }
        $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param];
        $this->recordDeviceBindFilterBox($param);
        if ($this->village_user_bind_id) {
            $houseVillageUserBindService = new HouseVillageUserBindService();
            $updateBindParam = [
                'face_img_status' => 2,
                'face_img_reason' => $face_img_reason,
            ];
            $houseVillageUserBindService->saveUserBind(['pigcms_id' => $this->village_user_bind_id], $updateBindParam);
        }
        if($param['log_id']){
            (new OwnerService())->updateLog(['log_id'=>$param['log_id']],[
                'status'=>2,
                'msg'=>$face_img_reason,
                'last_time'=>time()
            ]);
        }
        return $this->backData($param, '楼栋未同步', 1023);
    }

    /**
     * 缺少海康云眸内部应用单元ID记录错误
     * @param $param
     * @return array
     * @throws \think\Exception
     */
    protected function filterNonExistentFloor($param) {
        $db_floor = new HouseVillageFloor();
        $whereFloor    = [];
        $whereFloor[]  = ['floor_id',   '=', $this->floor_id];
        $whereFloor[]  = ['village_id', '=', $this->village_id];
        $floor = $db_floor->getOne($whereFloor, 'floor_id,floor_name,floor_number,status');
        if ($floor && !is_array($floor)) {
            $floor = $floor->toArray();
        }
        if (!isset($floor['floor_id'])) {
            $this->syn_status_txt    = '对应单元不存在或者已经被删除';
            $this->err_reason        = "对应单元不存在或者已经被删除";
            $face_img_reason         = '对应单元不存在或者已经被删除';
        } elseif (isset($floor['floor_number']) && !$floor['floor_number']) {
            $this->syn_status_txt    = '单元编号不能为空';
            $this->err_reason        = "单元编号不能为空";
            $face_img_reason         = '单元编号不能为空';
        } elseif (isset($floor['status']) && $floor['status'] == 4) {
            $this->syn_status_txt    = '对应单元已经被删除';
            $this->err_reason        = "对应单元已经被删除";
            $face_img_reason         = '对应单元已经被删除';
        } else {
            $queueData = [
                'thirdProtocol'    => $this->thirdProtocol,
                'village_id'       => $this->village_id,
                'device_id'        => $this->device_id,
                'device_sn'        => $this->device_sn,
                'deviceType'       => $this->device_equipment_type,
                'device_type'      => $this->face_device_type,
                'step_num'         => $this->step_num + 1,
                'orderGroupId'     => $this->order_group_id,
                'orderGroupType'   => $this->order_group_type,
                'village_third_id' => $this->village_third_id,
                'build_third_id'   => $this->build_third_id,
                'single_id'        => $this->single_id,
            ];
            $bind_type    = DeviceConst::HK_TO_CLOUD_FLOOR;
            $bind_id      = $this->floor_id;
            $floor_param = [];
            $floor_param['bind_type']       = $bind_type;
            $floor_param['bind_id']         = $bind_id;
            $floor_param['thirdProtocol']   = $this->thirdProtocol;
            $floor_param['single']          = $floor;
            $floor_param['device_type']     = $this->face_device_type;
            $floor_param['buildingId']      = $this->build_third_id;
            $queueData['floor_param']  = $floor_param;
            $queueData['step_num']     = $this->step_num + 1;
            $queueData['jobType']      = 'singleFloorToHikDeviceCloud';

            $job_id = $this->traitCommonHikCloudUnits($queueData);
            $this->syn_status_txt    = '单元未同步(单元ID不能为空,触发一次同步单元)';
            $this->err_reason        = "海康云眸内部单元ID为空";
            $face_img_reason         = '单元未同步';
        }
        $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param];
        $this->recordDeviceBindFilterBox($param);
        if ($this->village_user_bind_id) {
            $houseVillageUserBindService = new HouseVillageUserBindService();
            $updateBindParam = [
                'face_img_status' => 2,
                'face_img_reason' => $face_img_reason,
            ];
            $houseVillageUserBindService->saveUserBind(['pigcms_id' => $this->village_user_bind_id], $updateBindParam);
        }
        if($param['log_id']){
            (new OwnerService())->updateLog(['log_id'=>$param['log_id']],[
                'status'=>2,
                'msg'=>$face_img_reason,
                'last_time'=>time()
            ]);
        }
        return $this->backData($param, '单元未同步', 1024);
    }

    /**
     * 缺少海康云眸内部应用户室ID记录错误
     * @param $param
     * @return array
     * @throws \think\Exception
     */
    protected function filterNonExistentRoom($param) {
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        $whereRoom    = [];
        $whereRoom[]  = ['pigcms_id',   '=', $this->room_id];
        $whereRoom[]  = ['village_id', '=', $this->village_id];
        fdump_api(['whereRoom' => $whereRoom, 'msg' => '缺少海康云眸内部应用户室ID记录错误'], 'v20Face/addSinglePersonToCloud',1);
        $room = $db_house_village_user_vacancy->getOne($whereRoom, 'pigcms_id,room,room_number,layer,pigcms_id,village_id,floor_id,layer_id,status,is_del');
        if ($room && !is_array($room)) {
            $room = $room->toArray();
        }
        fdump_api(['room' => $room, 'msg' => '缺少海康云眸内部应用户室ID记录错误'], 'v20Face/addSinglePersonToCloud',1);
        $job_id = -2;
        if (!isset($room['pigcms_id'])) {
            $this->syn_status_txt    = '对应房屋不存在或者已经被删除';
            $this->err_reason        = "对应房屋不存在或者已经被删除";
            $face_img_reason         = '对应房屋不存在或者已经被删除';
        } elseif (isset($room['room_number']) && !$room['room_number']) {
            $this->syn_status_txt    = '房屋编号不能为空';
            $this->err_reason        = "房屋编号不能为空";
            $face_img_reason         = '房屋编号不能为空';
        } elseif ($room['status'] == 4 || $room['is_del'] == 1) {
            $this->syn_status_txt    = '对应房屋已经被删除';
            $this->err_reason        = "对应房屋已经被删除";
            $face_img_reason         = '对应房屋已经被删除';
        } else {
            $db_layer  = new HouseVillageLayer();
            $queueData = [
                'thirdProtocol'    => $this->thirdProtocol,
                'village_id'       => $this->village_id,
                'device_id'        => $this->device_id,
                'device_sn'        => $this->device_sn,
                'deviceType'       => $this->device_equipment_type,
                'device_type'      => $this->face_device_type,
                'orderGroupId'     => $this->order_group_id,
                'orderGroupType'   => $this->order_group_type,
                'village_third_id' => $this->village_third_id,
                'build_third_id'   => $this->build_third_id,
                'unit_third_id'    => $this->unit_third_id,
                'single_id'        => $this->single_id,
                'floor_id'         => $this->floor_id,
            ];
            $whereLayer = [];
            $whereLayer[] = ['id', '=', $room['layer_id']];
            $layer = $db_layer->getOne($whereLayer, 'id,layer_number,layer_name');
            if ($layer && !is_array($layer)) {
                $layer = $layer->toArray();
            }
            if (isset($layer['layer_number']) && $layer['layer_number']) {
                $room['layer_number'] = $layer['layer_number'];
            }
            if (isset($layer['layer_name']) && $layer['layer_name']) {
                $room['layer_name'] = $layer['layer_name'];
            }
            $bind_type  = DeviceConst::HK_TO_CLOUD_ROOM;
            $bind_id      = $this->room_id;
            $room_param = [];
            $room_param['bind_type']       = $bind_type;
            $room_param['bind_id']         = $bind_id;
            $room_param['thirdProtocol']   = $this->thirdProtocol;
            $room_param['room']            = $room;
            $room_param['device_type']     = $this->face_device_type;
            $room_param['floor_id']        = $this->floor_id;
            $room_param['unitId']          = $this->unit_third_id;
            $queueData['room_param']  = $room_param;
            $queueData['step_num']     = $this->step_num + 1;
            $queueData['jobType']      = 'singleRoomToDeviceCloud';

            fdump_api(['queueData' => $queueData, 'msg' => '下发房屋'], 'v20Face/addSinglePersonToCloud',1);
            $job_id = $this->traitCommonHikCloudUnits($queueData);
            $this->syn_status_txt    = '房屋未同步(房屋ID不能为空,触发一次同步房屋)';
            $this->err_reason        = "海康云眸内部户室ID为空";
            $face_img_reason         = '房屋未同步';
        }
        $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param, 'job_id' => $job_id];
        $this->recordDeviceBindFilterBox($param);
        if ($this->village_user_bind_id) {
            $houseVillageUserBindService = new HouseVillageUserBindService();
            $updateBindParam = [
                'face_img_status' => 2,
                'face_img_reason' => $face_img_reason,
            ];
            $houseVillageUserBindService->saveUserBind(['pigcms_id' => $this->village_user_bind_id], $updateBindParam);
        }
        if($param['log_id']){
            (new OwnerService())->updateLog(['log_id'=>$param['log_id']],[
                'status'=>2,
                'msg'=>$face_img_reason,
                'last_time'=>time()
            ]);
        }
        return $this->backData($param, '房屋未同步', 1025);
    }
    
    /**
     * 公用处理人员信息方法
     * @param array $param
     * @return array
     */
    protected function filterVillageBindUser(array $param) {
        if (isset($param['pigcms_id']) && $param['pigcms_id']) {
            $pigcms_id = $param['pigcms_id'];
        } else {
            $pigcms_id =  $this->village_user_bind_id;
        }
        $userBind = $this->getDeviceBindUser($pigcms_id, $this->village_id);
        fdump_api(['userBind' => $userBind,'line' => __LINE__], 'v20Face/addSinglePersonToCloud',1);
        if (!empty($userBind)) {
            $personParam = [];
            $personParam = $this->filterHikBindUser($userBind);
            return [
                'userBind'    => $userBind,
                'personParam' => $personParam,
            ];
        } else {
            return [];
        }
    }

    /**
     * 海康人员信息关联
     * @param array $userBind
     * @param bool  $isCard  是否返回卡号
     * @return array
     */
    protected function filterHikBindUser($userBind, $isCard = false) {
        $unionId          = isset($userBind['pigcms_id'])      && $userBind['pigcms_id']      ? $userBind['pigcms_id']      : $this->village_user_bind_id;
        $personName       = isset($userBind['name'])           && $userBind['name']           ? $userBind['name']           : '';
        $personName       = $this->replaceWord($unionId, $personName, '', 25);                                              
        $mobile           = isset($userBind['phone'])          && $userBind['phone']          ? $userBind['phone']          : '';
        /** @var int credentialType 证件类型	Integer		是	[1]身份证[2护照[3]其他 */
        $credentialType   = isset($userBind['credentialType']) && $userBind['credentialType'] ? $userBind['credentialType'] : 1;
        $credentialNumber = isset($userBind['id_card'])        && $userBind['id_card']        ? $userBind['id_card']        : '';
        $id_card          = isset($userBind['ic_card'])        && $userBind['ic_card']        ? trim($userBind['ic_card'])  : '';
        if ((!$personName || !$mobile) && !$credentialNumber) {
            $updateBindParam = [
                'face_img_status' => 2,
                'face_img_reason' => '缺少手机号或者身份证信息',
            ];
            $houseVillageUserBindService = new HouseVillageUserBindService();
            $houseVillageUserBindService->saveUserBind(['pigcms_id' => $unionId], $updateBindParam);
            $this->syn_status     = DeviceConst::BIND_USER_ALL_SYN_ERR;
            $this->syn_status_txt = '缺少手机号或者身份证信息';
            $this->err_reason     = "同步的住户缺少手机号或者身份证信息";
            if($this->log_id){
                (new OwnerService())->updateLog(['log_id'=>$this->log_id],[
                    'status'=>2,
                    'msg'=>'缺少手机号或者身份证信息',
                    'last_time'=>time()
                ]);
            }
            return [];
        }
        $uid = isset($userBind['uid']) && $userBind['uid'] ? $userBind['uid'] : 0;

        fdump_api(['uid' => $uid,'line' => __LINE__], 'v20Face/addSinglePersonToCloud',1);
        $faceUrl = $this->getUserFaceImg($uid);
        $personParam = [
            'unionId'    => $unionId,
            'personName' => $personName,
        ];
        if ($credentialNumber) {
            $personParam['credentialType']   = $credentialType;
            $personParam['credentialNumber'] = $credentialNumber;
        }
        if ($mobile) {
            $personParam['mobile']   = $mobile;
        }
        if ($faceUrl) {
            $personParam['faceUrl']   = $faceUrl;
        }
        if ($isCard && $id_card) {
            $cardNumbers   = [];
            $cardNumbers[] = $id_card;
            $personParam['cardNumbers'] = $cardNumbers;
        }
        return $personParam;
    }
    
    /**
     * 获取可视对讲对应参数.
     */
    public function getVisualParam($device_id, $deviceSerial, $uid)
    {
        try {
            fdump_api(['获取可视对讲对应参数：'.__LINE__,$device_id, $deviceSerial, $uid],'getVisualParam',1);
            $whereDevice = [];
            if ($device_id) {
                $whereDevice[] = ['device_id', '=', $device_id];
            } else {
                $whereDevice[] = ['device_sn', '=', $deviceSerial];
            }
            $dbHouseFaceDevice = new HouseFaceDevice();
            $whereDevice[] = ['is_del', '=', 0];
            $deviceField = 'device_id,village_id,floor_id,public_area_id,device_name,device_sn,thirdProtocol,cloud_code,cloud_device_id';
            $deviceInfo = $dbHouseFaceDevice->getOne($whereDevice, $deviceField);
            if ($deviceInfo && !is_array($deviceInfo)) {
                $deviceInfo = $deviceInfo->toArray();
            }
            fdump_api(['获取可视对讲对应参数：'.__LINE__,'$deviceInfo' => $deviceInfo],'getVisualParam',1);
            !$deviceSerial && $deviceSerial = isset($deviceInfo['device_sn']) ? trim($deviceInfo['device_sn']) : '';
            !$device_id && $device_id = isset($deviceInfo['device_id']) ? intval($deviceInfo['device_id']) : 0;
            $serviceFaceHikCloudNeiBuDevice = new FaceHikCloudNeiBuDeviceService();
            $tokenResult = $serviceFaceHikCloudNeiBuDevice->requestAccessToken();
            $access_token = isset($tokenResult['access_token']) ? trim($tokenResult['access_token']) : '';
            $channelNo = '';
            $arr = [
                'oauthToken' => $access_token,
                'deviceSerial' => $deviceSerial,
            ];
            fdump_api(['获取可视对讲对应参数：'.__LINE__,'$arr' => $arr],'getVisualParam',1);
            $village_id = isset($deviceInfo['village_id']) ? intval($deviceInfo['village_id']) : 0;
            $thirdProtocol = isset($deviceInfo['thirdProtocol']) ? intval($deviceInfo['thirdProtocol']) : 0;
            $cloud_device_id = isset($deviceInfo['cloud_device_id']) ? trim($deviceInfo['cloud_device_id']) : '';
            $device_name = isset($deviceInfo['device_name']) ? trim($deviceInfo['device_name']) : '';
            $cloud_code = isset($deviceInfo['cloud_code']) ? trim($deviceInfo['cloud_code']) : '';
            $this->thirdProtocol = $thirdProtocol;
            $this->village_id = $village_id;
            $aboutInfo = $this->filterVillageToData($village_id, $thirdProtocol);
            fdump_api(['获取可视对讲对应参数：'.__LINE__,'$aboutInfo' => $aboutInfo],'getVisualParam',1);
            $communityId = isset($aboutInfo['third_id']) ? trim($aboutInfo['third_id']) : '';
            //获取取流认证信息
            $approve_info = $serviceFaceHikCloudNeiBuDevice->getEzvizInfo();
            if ($approve_info['code'] == 200) {
                $arr['approve'] = $approve_info['data'];
            }
            fdump_api(['获取可视对讲对应参数：'.__LINE__,'$arr' => $arr],'getVisualParam',1);
            if ($communityId) {
                $paramChannel = ['pageNo' => 1, 'pageSize' => 100];
                $channelRecord = $this->getFaceDeviceChannel($communityId, $cloud_device_id, $paramChannel, $village_id, $device_id);
                fdump_api(['获取可视对讲对应参数：'.__LINE__,'$channelRecord' => $channelRecord],'getVisualParam',1);
                if (!empty($channelRecord)) {
                    foreach ($channelRecord as $val) {
                        if (isset($val['channelType']) && $val['channelType'] != 10300) {
                            continue;
                        }
                        if ($val['channelStatus'] == 1) {
                            $channelNo = $val['channelNo'];
                        }
                    }
                }
            }
            $arr['channelNo'] = $channelNo;
            $floor_id = isset($deviceInfo['floor_id']) ? $deviceInfo['floor_id'] : -1;
            $public_area_id = isset($deviceInfo['public_area_id']) ? intval($deviceInfo['public_area_id']) : 0;
            if ($floor_id && $floor_id != -1) {
                if(strstr($floor_id,',') !== false){
                    $where_bind = [];
                    $floorIds = explode(',', $floor_id);
                    $where_bind[] = ['uid', '=', $uid];
                    $where_bind[] = ['village_id', '=', $village_id];
                    $where_bind[] = ['type', 'in', [0,1,2,3]];
                    $where_bind[] = ['floor_id', 'in', $floorIds];
                    $user_info = (new HouseVillageUserBind())->getOne($where_bind, 'floor_id');
                    $floor_where_id = isset($user_info['floor_id']) ? intval($user_info['floor_id']) : 0;
                    !$floor_where_id && $floor_where_id = $floorIds[0];
                } else {
                    $floor_where_id = $floor_id;
                }
                $floor_where = [];
                $floor_where[] = ['floor_id', '=', $floor_where_id];
                $floor_info = (new HouseVillageFloor())->getOne($floor_where, 'floor_id,single_id,floor_name');
                $single_id = isset($floor_info['single_id']) ? intval($floor_info['single_id']) : 0;
                $single_where = [];
                $single_where[] = ['id', '=', $single_id];
                $single_info = (new HouseVillageSingle())->getOne($single_where, 'id,single_name');

                $single_name = isset($single_info['single_name']) ? trim($single_info['single_name']) : '';
                $single_name = $this->traitAutoFixLouDongTips($single_name, true);
                $floor_name = isset($floor_info['floor_name']) ? trim($floor_info['floor_name']) : '';
                $floor_name = $this->traitAutoFixDanyuanTips($floor_name, true);
                $name = $single_name.$floor_name;
            } else {
                $db_house_village_public_area = new HouseVillagePublicArea();
                $where = ['public_area_id' => $public_area_id];
                $public_area_info = $db_house_village_public_area->getOne($where, 'public_area_name');
                $public_area_name = isset($public_area_info['public_area_name']) && $public_area_info['public_area_name'] ? trim($public_area_info['public_area_name']) : '';
                $name = $public_area_name ? $public_area_name : $device_name;
            }
            $arr['cloud_code'] = $cloud_code;
            $arr['village_title'] = $name;
            $arr['web_name'] = cfg('site_name');
            fdump_api(['获取可视对讲对应参数：'.__LINE__,'$arr' => $arr],'getVisualParam',1);
        } catch (\Exception $e) {
            $arr = [];
            fdump_api(['err'=>$e->getMessage(), 'line'=>$e->getLine(), 'code'=>$e->getCode()],'errgetVisualParam',true);
        }
        
        return $arr;
    }

    /**
     * 获取人脸设备通道，按需保存数据.
     */
    public function getFaceDeviceChannel($communityId, $cloud_device_id, $param, $village_id, $device_id) {
        $channelRecord = (new FaceDeviceService())->getHikDeviceChannels($communityId, $cloud_device_id, $param);
        fdump_api(['1获取可视对讲对应参数：'.__LINE__,'$channelRecord' => $channelRecord],'getVisualParam',1);
        $dbHouseDeviceChannel = new HouseDeviceChannel();
        $nowTime = isset($param['nowTime']) ? intval($param['nowTime']) : time();
        $isSave = isset($param['isSave']) ? intval($param['isSave']) : 1;
        if ($isSave == 1) {
            foreach ($channelRecord as $channel) {
                $whereRepeat = [
                    'device_type' => 'house_face_device',
                    'channelNo'   => $channel['channelNo'],
                    'channelId'   => $channel['channelId'],
                    'isDel'       => 0,
                ];
                if (isset($channel['deviceId'])) {
                    $whereRepeat['deviceId'] = $channel['deviceId'];
                }
                $repeatChannel = $dbHouseDeviceChannel->getOne($whereRepeat);
                $channelId     = $channel['channelId'];
                $channelName   = $channel['channelName'];
                $channelNo     = $channel['channelNo'];
                $channelStatus = $channel['channelStatus'];
                $deviceModel   = isset($channel['deviceModel']) ? $channel['deviceModel'] : '';
                $channelType   = isset($channel['channelType']) ? $channel['channelType'] : '';
                $isUse         = isset($channel['isUse']) ? $channel['isUse'] : 1;
                $ipcSerial     = isset($channel['ipcSerial']) ? $channel['ipcSerial'] : '';
                $deviceId      = isset($channel['deviceId']) ? $channel['deviceId'] : '';
                $deviceSerial  = isset($channel['deviceSerial']) ? $channel['deviceSerial'] : '';
                $channelPicUrl = isset($channel['channelPicUrl']) ? $channel['channelPicUrl'] : '';
                $deviceStatus  = isset($channel['deviceStatus']) ? $channel['deviceStatus'] : '';
                $updateParam = [
                    'device_type'   => 'house_face_device',
                    'village_id'    => $village_id,
                    'device_id'     => $device_id,
                    'deviceSerial'  => $deviceSerial,
                    'channelId'     => $channelId,
                    'channelName'   => $channelName,
                    'channelNo'     => $channelNo,
                    'channelStatus' => $channelStatus,
                    'channelType'   => $deviceModel ? $deviceModel : $channelType,
                    'isUse'         => $isUse,
                    'ipcSerial'     => $ipcSerial,
                    'deviceId'      => $deviceId,
                    'channelPicUrl' => $channelPicUrl,
                    'channel_txt'   => json_encode($channel, JSON_UNESCAPED_UNICODE),
                ];
                if (isset($repeatChannel['channel_id']) && $repeatChannel['channel_id']) {
                    $updateParam['update_time'] = $nowTime;
                    $updateChannel = $dbHouseDeviceChannel->updateThis($whereRepeat, $updateParam);
                    if ($updateChannel !== false) {
                        $channel_id = $repeatChannel['channel_id'];
                    } else {
                        $channel_id = 0;
                    }
                    $channelIdArr[] = $channelId;
                } else {
                    $addParam = $updateParam;
                    $addParam['add_time'] = $nowTime;
                    $channel_id = $dbHouseDeviceChannel->add($addParam);
                    if ($channel_id) {
                        $channelIdArr[] = $channelId;
                    }
                }
            }
        }
        return $channelRecord;
    }

    /**
     * 关闭加密.
     */
    public function offVideoEncrypt($device_id, $deviceSerial)
    {
        $whereDevice = [];
        if ($device_id) {
            $whereDevice[] = ['device_id', '=', $device_id];
        } else {
            $whereDevice[] = ['device_sn', '=', $deviceSerial];
        }
        $dbHouseFaceDevice = new HouseFaceDevice();
        $whereDevice[] = ['is_del', '=', 0];
        $deviceField = 'device_id,village_id,floor_id,public_area_id,device_name,device_sn,thirdProtocol,cloud_code';
        $deviceInfo = $dbHouseFaceDevice->getOne($whereDevice, $deviceField);
        if ($deviceInfo && !is_array($deviceInfo)) {
            $deviceInfo = $deviceInfo->toArray();
        }
        !$deviceSerial && $deviceSerial = isset($deviceInfo['device_sn']) ? trim($deviceInfo['device_sn']) : '';
        $village_id = isset($deviceInfo['village_id']) ? intval($deviceInfo['village_id']) : 0;
        $thirdProtocol = isset($deviceInfo['thirdProtocol']) ? intval($deviceInfo['thirdProtocol']) : 0;
        $cloud_device_id = isset($deviceInfo['cloud_device_id']) ? trim($deviceInfo['cloud_device_id']) : '';
        $cloud_code = isset($deviceInfo['cloud_code']) ? trim($deviceInfo['cloud_code']) : '';
        $serviceFaceHikCloudNeiBuDevice = new FaceHikCloudNeiBuDeviceService();
        $result = $serviceFaceHikCloudNeiBuDevice->offDeviceVoideEncrypt($cloud_device_id, $cloud_code);
        return $result;
    }

    //视频接口 推给前端
    public function sendPush($data)
    {
        $deviceSerial = $data['deviceSerial']; //设备序列号
        $dateTime = $data['dateTime']; //消息时间（UTC+08:00）
        $cmdType = $data['cmdType']; //操作类型
        $periodNumber = $data['periodNumber'];//期号
        $buildingNumber = $data['buildingNumber']; //楼号
        $unitNumber = $data['unitNumber']; //单元号
        $floorNumber = $data['floorNumber']; // 层号
        $roomNumber = $data['roomNumber']; // 房间号
        $devIndex = $data['devIndex'] ?? ''; //设备序号
        $unitType = $data['unitType']; //类型: outdoor门口机，wall围墙机
        $devicePath = $data['devicePath']; //设备位置路径

        //海康会推送呼叫（request）、接听（cancel）、挂断（hangUp）。如果不是呼叫，则不继续通知，原来挂断的也会呼叫导致BUG。
        fdump_api(['视频推送用户端：' . __LINE__, $data], 'a5SendPushLog', 1);
        if ($cmdType != 'request') {
            fdump_api(['非呼叫不推送：' . __LINE__, 'data' => $data], 'errA5SendPushLog', 1);
            return false;
        }
        $dbHouseFaceDevice = new HouseFaceDevice();
        $whereDevice = [];
        $whereDevice[] = ['device_sn', '=', $deviceSerial];
        $whereDevice[] = ['is_del', '=', 0];
        $deviceField = 'device_id,village_id,floor_id,public_area_id,device_name,device_sn,thirdProtocol,cloud_code';
        $deviceInfo = $dbHouseFaceDevice->getOne($whereDevice, $deviceField);
        if ($deviceInfo && !is_array($deviceInfo)) {
            $deviceInfo = $deviceInfo->toArray();
        }
        $device_id = isset($deviceInfo['device_id']) ? intval($deviceInfo['device_id']) : 0;
        $floor_id = isset($deviceInfo['floor_id']) ? $deviceInfo['floor_id'] : -1;
        $village_id = isset($deviceInfo['village_id']) ? intval($deviceInfo['village_id']) : 0;
        $public_area_id = isset($deviceInfo['public_area_id']) ? intval($deviceInfo['public_area_id']) : 0;
        $device_name = isset($deviceInfo['device_name']) ? trim($deviceInfo['device_name']) : '';
        $thirdProtocol = isset($deviceInfo['thirdProtocol']) ? intval($deviceInfo['thirdProtocol']) : 0;
        $cloud_device_id = isset($deviceInfo['cloud_device_id']) ? trim($deviceInfo['cloud_device_id']) : '';
        $dbHouseVillageSingle = new HouseVillageSingle();
        $dbHouseVillageFloor = new HouseVillageFloor();
        $send_single_id = 0;
        $send_floor_id = 0;
        if ($floor_id && $floor_id != -1 && $unitType == 'outdoor') {
            fdump_api(['视频推送用户端：' . __LINE__, $data], 'a5SendPushLog', 1);
            if (strstr($floor_id, ',') !== false && !$buildingNumber) {
                // 多单元归属设备 但是推送消息中无楼栋 没法定位人员 
                return false;
            }
            if (strstr($floor_id, ',') !== false) {
                fdump_api(['视频推送用户端：' . __LINE__, $data], 'a5SendPushLog', 1);
                $whereSingle = [];
                $whereSingle[] = ['village_id', '=', $village_id];
                $whereSingle[] = ['status', '=', 1];
                $whereSingle[] = ['single_name|single_number', '=', $buildingNumber];
                $singleInfo = $dbHouseVillageSingle->getOne($whereSingle, 'id, single_name');
                $send_single_id = isset($singleInfo['id']) ? intval($singleInfo['id']) : 0;
                if (!$send_single_id) {
                    if (strlen($buildingNumber) < 2) {
                        $single_number = str_pad($buildingNumber, 2, "0", STR_PAD_LEFT);
                    } else {
                        $single_number = $buildingNumber;
                    }
                    $whereSingle = [];
                    $whereSingle[] = ['village_id', '=', $village_id];
                    $whereSingle[] = ['status', '=', 1];
                    $whereSingle[] = ['single_number', '=', $single_number];
                    $singleInfo = $dbHouseVillageSingle->getOne($whereSingle, 'id, single_name');
                    $send_single_id = isset($singleInfo['id']) ? intval($singleInfo['id']) : 0;
                }
                $single_name = isset($single_info['single_name']) ? trim($single_info['single_name']) : '';
                $floor_arr = explode(',', $floor_id);
                $whereFloor = [];
                $whereFloor[] = ['village_id', '=', $village_id];
                $whereFloor[] = ['floor_id', 'in', $floor_arr];
                $whereFloor[] = ['single_id', '=', $send_single_id];
                $whereFloor[] = ['status', '=', 1];
                $whereFloor[] = ['floor_name|floor_number', '=', $unitNumber];
                $floorInfo = $dbHouseVillageFloor->getOne($whereFloor, 'floor_id,single_id,floor_name');
                $send_floor_id = isset($floorInfo['floor_id']) ? intval($floorInfo['floor_id']) : 0;
                if (!$send_floor_id) {
                    if (strlen($unitNumber) < 2) {
                        $floor_number = str_pad($unitNumber, 2, "0", STR_PAD_LEFT);
                    } else {
                        $floor_number = $unitNumber;
                    }
                    $whereFloor = [];
                    $whereFloor[] = ['village_id', '=', $village_id];
                    $whereFloor[] = ['floor_id', 'in', $floor_arr];
                    $whereFloor[] = ['single_id', '=', $send_single_id];
                    $whereFloor[] = ['status', '=', 1];
                    $whereFloor[] = ['floor_number', '=', $floor_number];
                    $floorInfo = $dbHouseVillageFloor->getOne($whereFloor, 'floor_id,single_id,floor_name');
                    $send_floor_id = isset($floorInfo['id']) ? intval($floorInfo['id']) : 0;
                }
                $floor_name = isset($floorInfo['floor_name']) ? trim($floorInfo['floor_name']) : '';
            } else {
                fdump_api(['视频推送用户端：' . __LINE__, $data], 'a5SendPushLog', 1);
                $whereFloor = [];
                $whereFloor[] = ['village_id', '=', $village_id];
                $whereFloor[] = ['status', '=', 1];
                $whereFloor[] = ['floor_id', '=', $floor_id];
                $floorInfo = $dbHouseVillageFloor->getOne($whereFloor, 'floor_id,single_id,floor_name');
                $send_single_id = isset($floorInfo['single_id']) ? intval($floorInfo['single_id']) : 0;
                $whereSingle = [];
                $whereSingle[] = ['id', '=', $send_single_id];
                $singleInfo = $dbHouseVillageSingle->getOne($whereSingle, 'id, single_name');
                $send_floor_id = $floor_id;
                $single_name = isset($single_info['single_name']) ? trim($single_info['single_name']) : '';
                $floor_name = isset($floorInfo['floor_name']) ? trim($floorInfo['floor_name']) : '';
            }
            $single_name = $this->traitAutoFixLouDongTips($single_name, true);
            $floor_name = $this->traitAutoFixDanyuanTips($floor_name, true);
            $name = $single_name . $floor_name;
        } else {
            $whereSingle = [];
            $whereSingle[] = ['village_id', '=', $village_id];
            $whereSingle[] = ['status', '=', 1];
            $whereSingle[] = ['single_name|single_number', '=', $buildingNumber];
            $singleInfo = $dbHouseVillageSingle->getOne($whereSingle, 'id');
            $send_single_id = isset($singleInfo['id']) ? intval($singleInfo['id']) : 0;
            if (!$send_single_id) {
                if (strlen($buildingNumber) < 2) {
                    $single_number = str_pad($buildingNumber, 2, "0", STR_PAD_LEFT);
                } else {
                    $single_number = $buildingNumber;
                }
                $whereSingle = [];
                $whereSingle[] = ['village_id', '=', $village_id];
                $whereSingle[] = ['status', '=', 1];
                $whereSingle[] = ['single_number', '=', $single_number];
                $singleInfo = $dbHouseVillageSingle->getOne($whereSingle, 'id');
                $send_single_id = isset($singleInfo['id']) ? intval($singleInfo['id']) : 0;
            }
            $whereFloor = [];
            $whereFloor[] = ['village_id', '=', $village_id];
            $whereFloor[] = ['single_id', '=', $send_single_id];
            $whereFloor[] = ['status', '=', 1];
            $whereFloor[] = ['floor_name|floor_number', '=', $unitNumber];
            $floorInfo = $dbHouseVillageFloor->getOne($whereFloor, 'floor_id,single_id,floor_name');
            $send_floor_id = isset($floorInfo['floor_id']) ? intval($floorInfo['floor_id']) : 0;
            if (!$send_floor_id) {
                if (strlen($unitNumber) < 2) {
                    $floor_number = str_pad($unitNumber, 2, "0", STR_PAD_LEFT);
                } else {
                    $floor_number = $unitNumber;
                }
                $whereFloor = [];
                $whereFloor[] = ['village_id', '=', $village_id];
                $whereFloor[] = ['single_id', '=', $send_single_id];
                $whereFloor[] = ['status', '=', 1];
                $whereFloor[] = ['floor_number', '=', $floor_number];
                $floorInfo = $dbHouseVillageFloor->getOne($whereFloor, 'floor_id,single_id,floor_name');
                $send_floor_id = isset($floorInfo['floor_id']) ? intval($floorInfo['floor_id']) : 0;
            }
            $db_house_village_public_area = new HouseVillagePublicArea();
            $where = ['public_area_id' => $public_area_id];
            $public_area_info = $db_house_village_public_area->getOne($where, 'public_area_name');
            $public_area_name = isset($public_area_info['public_area_name']) && $public_area_info['public_area_name'] ? trim($public_area_info['public_area_name']) : '';
            $name = $public_area_name ? $public_area_name : $device_name;
        }
        if (!$send_single_id || !$send_floor_id) {
            // 未定位到对应楼栋或单元
            fdump_api(['未定位到对应楼栋或单元：' . __LINE__, 'data' => $data, 'send_single_id' => $send_single_id, 'floor_id' => $floor_id, 'send_floor_id' => $send_floor_id, 'whereSingle' => $whereSingle, 'whereFloor' => $whereFloor], 'errA5SendPushLog', 1);
            return false;
        }
        $dbHouseVillageLayer = new HouseVillageLayer();
        $whereLayer = [];
        $whereLayer[] = ['village_id', '=', $village_id];
        $whereLayer[] = ['single_id', '=', $send_single_id];
        $whereLayer[] = ['floor_id', '=', $send_floor_id];
        $whereLayer[] = ['status', '=', 1];
        $whereLayer[] = ['layer_name|layer_number', '=', $floorNumber];
        $layerInfo = $dbHouseVillageLayer->getOne($whereLayer, 'id, layer_name');
        $send_layer_id = isset($layerInfo['id']) ? intval($layerInfo['id']) : 0;
        if (!$send_layer_id) {
            if (strlen($floorNumber) < 2) {
                $layer_number = str_pad($floorNumber, 2, "0", STR_PAD_LEFT);
            } else {
                $layer_number = $floorNumber;
            }
            $whereLayer = [];
            $whereLayer[] = ['village_id', '=', $village_id];
            $whereLayer[] = ['single_id', '=', $send_single_id];
            $whereLayer[] = ['floor_id', '=', $send_floor_id];
            $whereLayer[] = ['status', '=', 1];
            $whereLayer[] = ['layer_number', '=', $layer_number];
            $layerInfo = $dbHouseVillageLayer->getOne($whereLayer, 'id, layer_name');
            $send_layer_id = isset($layerInfo['id']) ? intval($layerInfo['id']) : 0;
        }
        $dbHouseVillageUserVacancy = new HouseVillageUserVacancy();
        $whereRoom = [];
        $whereRoom[] = ['village_id', '=', $village_id];
        $whereRoom[] = ['single_id', '=', $send_single_id];
        $whereRoom[] = ['floor_id', '=', $send_floor_id];
        $whereRoom[] = ['layer_id', '=', $send_layer_id];
        $whereRoom[] = ['is_del', '=', 0];
        $whereRoom[] = ['room|room_number', '=', $roomNumber];
        $roomInfo = $dbHouseVillageUserVacancy->getOne($whereRoom, 'pigcms_id');
        $send_room_id = isset($roomInfo['pigcms_id']) ? intval($roomInfo['pigcms_id']) : 0;
        fdump_api(['1视频推送用户端：' . __LINE__, $whereRoom, $roomInfo, $dbHouseVillageUserVacancy->getLastSql()], 'a5SendPushLog', 1);
        if (!$send_room_id) {
            if (strlen($roomNumber) < 2) {
                $room_number = str_pad($roomNumber, 2, "0", STR_PAD_LEFT);
            } else {
                $room_number = $roomNumber;
            }
            if (strlen($room_number) < 4) {
                if (!isset($layer_number)) {
                    if (strlen($floorNumber) < 2) {
                        $layer_number = str_pad($floorNumber, 2, "0", STR_PAD_LEFT);
                    } else {
                        $layer_number = $floorNumber;
                    }
                }
                $room_number = $layer_number . $room_number;
            }
            $whereRoom = [];
            $whereRoom[] = ['village_id', '=', $village_id];
            $whereRoom[] = ['single_id', '=', $send_single_id];
            $whereRoom[] = ['floor_id', '=', $send_floor_id];
            $whereRoom[] = ['layer_id', '=', $send_layer_id];
            $whereRoom[] = ['is_del', '=', 0];
            $whereRoom[] = ['room_number', '=', $room_number];
            $roomInfo = $dbHouseVillageUserVacancy->getOne($whereRoom, 'pigcms_id');
            $send_room_id = isset($roomInfo['pigcms_id']) ? intval($roomInfo['pigcms_id']) : 0;
        }
        if (!$send_room_id) {
            // 未定位到对应房间
            fdump_api(['未定位到对应房间：' . __LINE__, 'data' => $data, 'whereRoom' => $whereRoom], 'errA5SendPushLog', 1);
            return false;
        }
        $whereUserBind = [];
        $whereUserBind[] = ['village_id', '=', $village_id];
        $whereUserBind[] = ['vacancy_id', '=', $send_room_id];
        $whereUserBind[] = ['status', '=', 1];
        $whereUserBind[] = ['type', 'in', [0, 1, 2, 3]];
        $whereUserBind[] = ['uid', '>', 0];
        $userBindList = (new HouseVillageUserBind())->getList($whereUserBind, 'pigcms_id, uid', 'type ASC');
        if ($userBindList && !is_array($userBindList)) {
            $userBindList = $userBindList->toArray();
        }
        if (empty($userBindList)) {
            fdump_api(['未定位到对应房间：' . __LINE__, 'data' => $data, 'whereRoom' => $whereRoom], 'errA5SendPushLog', 1);
            return false;
        }
        $aboutInfo = $this->filterVillageToData($village_id, $thirdProtocol);
        $communityId = isset($aboutInfo['third_id']) ? trim($aboutInfo['third_id']) : '';
        $channelNo = '';
        if ($communityId) {
            if (!empty($channelRecord)) {
                $param = ['pageNo' => 1, 'pageSize' => 100];
            }
            $channelRecord = $this->getFaceDeviceChannel($communityId, $cloud_device_id, $param, $village_id, $device_id);
            if (!empty($channelRecord)) {
                foreach ($channelRecord as $val) {
                    if (isset($val['channelType']) && $val['channelType'] != 10300) {
                        continue;
                    }
                    if ($val['channelStatus'] == 1) {
                        $channelNo = $val['channelNo'];
                    }
                }
            }
        }
        $dbUserSet = new UserSet();
        $dbUser = new User();
        $db_appapi_app_login_log = new AppapiAppLoginLog();
        $toUserErrArr = [];
        foreach ($userBindList as $userBind) {
            $uid = isset($userBind['uid']) ? intval($userBind['uid']) : 0;
            $pigcms_id = isset($userBind['pigcms_id']) ? intval($userBind['pigcms_id']) : 0;
            if ($uid <= 0) {
                $toUserErrArr[] = ['err' => 'uid没有值', 'userBind' => $userBind];
                continue;
            }
            $userInfo = $dbUser->getOne(['uid' => $uid], 'device_id,client,jpush_registrationId,openid,phone');
            if ($userInfo && !is_array($userInfo)) {
                $userInfo = $userInfo->toArray();
            }
            $user_device_id = isset($userInfo['device_id']) && $userInfo['device_id'] ? trim($userInfo['device_id']) : '';
            if (!$user_device_id) {
                $toUserErrArr[] = ['err' => '没有通过手机登录过', 'userBind' => $userBind, 'userInfo' => $userInfo];
                continue;
            }
            $setInfo = $dbUserSet->getOne(['uid' => $uid], 'allow_cloud_calls, cloud_call_bell');
            if ($setInfo && !is_array($setInfo)) {
                $setInfo = $setInfo->toArray();
            }
            $allow_cloud_calls = isset($setInfo['allow_cloud_calls']) ? intval($setInfo['allow_cloud_calls']) : 0;
            $cloud_call_bell = isset($setInfo['cloud_call_bell']) ? trim($setInfo['cloud_call_bell']) : '';
            if ($allow_cloud_calls == 2) {
                $toUserErrArr[] = ['err' => '用户设置了不允许推送', 'userBind' => $userBind, 'setInfo' => $setInfo];
                continue;
            }
            $where = [];
            $where[] = ['uid', '=', $uid];
            $where[] = ['client', '<>', 0];
            $user_last_login_log = $db_appapi_app_login_log->getOne($where, 'device_id', 'pigcms_id desc');
            if ($user_last_login_log && !is_array($user_last_login_log)) {
                $user_last_login_log = $user_last_login_log->toArray();
            }
            $user_last_device_id = isset($user_last_login_log['device_id']) && $user_last_login_log['device_id'] ? $user_last_login_log['device_id'] : '';

            $where = [];
            $where[] = ['device_id', '=', $user_last_device_id];
            $where[] = ['client', '<>', 0];
            $device_last_login_log = $db_appapi_app_login_log->getOne($where, 'uid', 'pigcms_id desc');
            if ($device_last_login_log && !is_array($device_last_login_log)) {
                $device_last_login_log = $device_last_login_log->toArray();
            }
            $device_last_uid = isset($device_last_login_log['uid']) ? intval($device_last_login_log['uid']) : 0;
            if ($device_last_uid != $uid) {
                $toUserErrArr[] = ['err' => '用户登录的最后设备的最后登录记录不是用户本身', 'userBind' => $userBind, 'user_last_login_log' => $user_last_login_log, 'device_last_login_log' => $device_last_login_log];
                continue;
            }

            //极光推送给业主用户
            $new_arr = [
                'device_sn' => $deviceSerial,
                'dateTime' => $dateTime,
                'cmdType' => $cmdType,
                'uid' => $uid,
                'url' => '',
                'bind_id' => $pigcms_id,
                'title' => '智慧社区',
                'msg' => '您的好友正在通过' . $name . '的门禁机邀请您视频，请点击进行视频沟通',
                'periodNumber' => $periodNumber,
                'buildingNumber' => $buildingNumber,
                'unitNumber' => $unitNumber,
                'floorNumber' => $floorNumber,
                'roomNumber' => $floorNumber . '0' . $roomNumber,
                'unitType' => $unitType,
                'channelNo' => $channelNo,
                'device_id' => $device_id,
                'pigcms_id' => $pigcms_id,
            ];
            if ($cloud_call_bell) {
                $new_arr['voice_mp3'] = replace_file_domain($cloud_call_bell);
            } elseif (cfg('house_a5_cloud_bell')) {
                $new_arr['voice_mp3'] = replace_file_domain(cfg('house_a5_cloud_bell'));
            } else {
                $new_arr['voice_mp3'] = '';
            }
            fdump_api(['极光推送：' . __LINE__, 'data' => $data, 'new_arr' => $new_arr], 'a5SendPushLog', 1);
            $res = invoke_cms_model('AppPush/send', [$new_arr, 'visual']);

            //公众号推送
            if ($userInfo['openid']) {
                $extInfo = [
                    'pigcms_tag' => 'house_visual',
                    'tag_desc' => $new_arr['bind_id'],
                    'url' => $new_arr['url'],
                    'device_sn' => $new_arr['device_sn'],
                    'periodNumber' => $new_arr['periodNumber'],
                    'buildingNumber' => $new_arr['buildingNumber'],
                    'unitNumber' => $new_arr['unitNumber'],
                    'floorNumber' => $new_arr['floorNumber'],
                    'roomNumber' => $new_arr['roomNumber'],
                    'unitType' => $new_arr['unitType'],
                    'channelNo' => $new_arr['channelNo'],
                    'device_id' => $new_arr['device_id'],
                    'pigcms_id' => $new_arr['pigcms_id'],
                    'cloud_protocol' => 'hik_protocol'
                ];
                $href = cfg('site_url') . '/topic/app_wap_platform.html?extinfo=' . urlencode(json_encode($extInfo));
                (new TemplateNewsService())->sendTempMsg('TM0001801',
                    array(
                        'href' => $href,
                        'wecha_id' => $userInfo['openid'],
                        'first' => $new_arr['msg'],
                        'keyword1' => '可视对讲',
                        'keyword2' => '待接听',
                        'keyword3' => date('H时i分', $_SERVER['REQUEST_TIME']),
                        'remark' => '请使用APP接听！'
                    )
                );
                fdump_api(['模板消息推送：' . __LINE__, 'data' => $data, 'extInfo' => $extInfo, 'href' => $href], 'a5SendPushLog', 1);
            }
        }
        if (!empty($toUserErrArr)) {
            fdump_api(['推送的用户错误：' . __LINE__, 'data' => $data, 'toUserErrArr' => $toUserErrArr], 'errA5SendPushLog', 1);
        }
        return true;
    }
    
    /**
     * 获取设备告警列表
     * @param $where
     * @param $field
     * @param $order
     * @param $page
     * @param $pageSize
     * @return \think\Collection
     */
    public function getCameraAlarmEventList($where = [], $field = true, $order = true, $page = 1, $pageSize = 15)
    {
        return (new CommunityEventAlarm())->getList($where, $field, $order, $page, $pageSize);
    }

    /**
     * 获取设备告警数量
     */
    public function getCameraAlarmEventCount($where = [])
    {
        return (new CommunityEventAlarm())->getCount($where);
    }

    /**
     * 获取设备告警信心
     */
    public function getCameraAlarmEventInfo($where = [], $field = true, $order = true)
    {
        return (new CommunityEventAlarm())->getOne($where, $field, $order);
    }

    /**
     * 同步删除.
     */
    public function deleteCloudUser($pigcms_id, $param = [])
    {
        $room_id = isset($param['room_id']) ? intval($param['room_id']) : 0;
        $village_id = isset($param['village_id']) ? intval($param['village_id']) : 0;
        $uid = isset($param['uid']) ? intval($param['uid']) : 0;
        $type = isset($param['type']) ? intval($param['type']) : 0;
        $phone = isset($param['phone']) ? trim($param['phone']) : '';
        $dbHouseVillageUserBind = new HouseVillageUserBind();
        if (!$room_id || !$village_id || !$uid) {
            $whereUserBind = [];
            $whereUserBind[] = ['pigcms_id', '=', $pigcms_id];
            $userBind = $dbHouseVillageUserBind->getOne($whereUserBind, 'pigcms_id, village_id, uid, vacancy_id, type, phone');

            $village_id = isset($userBind['village_id']) ? intval($userBind['village_id']) : 0;
            $room_id = isset($userBind['vacancy_id']) ? intval($userBind['vacancy_id']) : 0;
            $uid = isset($userBind['uid']) ? intval($userBind['uid']) : 0;
            $type = isset($userBind['type']) ? intval($userBind['type']) : 0;
            $phone = isset($userBind['phone']) ? trim($userBind['phone']) : '';
        }
        if (!$uid) {
            fdump_api(['msg' => '缺少平台身份无法同步删除', 'pigcms_id' => $pigcms_id, 'param' => $param], 'deleteHikCloudUser/hikNeiBuHandleLog', 1);
            return true;
        }
        $dbFaceBindAboutInfo = new FaceBindAboutInfo();
        $dbFaceUserBindDevice = new FaceUserBindDevice();
        if ($type == 4) {
            $whereOtherWork = [];
            $whereOtherWork[] = ['pigcms_id', '<>', $pigcms_id];
            $whereOtherWork[] = ['type', '=', $type];
            $whereOtherWork[] = ['village_id', '=', $village_id];
            $whereOtherWork[] = ['uid', '=', $uid];
            $whereOtherWork[] = ['status', '=', 1];
            $workInfo = $dbHouseVillageUserBind->getOne($whereOtherWork, 'pigcms_id, village_id, uid, vacancy_id, type, phone');
            $work_pigcms_id = isset($workInfo['pigcms_id']) ? intval($workInfo['pigcms_id']) : 0;
            if ($work_pigcms_id > 0) {
                fdump_api(['msg' => '是否存在相同账号工作人员，存在不做删除同步', 'pigcms_id' => $pigcms_id, 'param' => $param, 'work_pigcms_id' => $work_pigcms_id], 'deleteHikCloudUser/hikNeiBuHandleLog', 1);
                return true;
            }
            // todo 直接查询下是否同步过 同步过 走下删除
            $whereFaceBind = [];
            $whereFaceBind[] = ['bind_type', '=', DeviceConst::HK_UID_CLOUD_USER];
            $whereFaceBind[] = ['bind_id', '=', $uid];
            $faceBindInfo = $dbFaceUserBindDevice->getOne($whereFaceBind, 'id, bind_type, bind_id, person_id');
            if ($faceBindInfo && !is_array($faceBindInfo)) {
                $faceBindInfo = $faceBindInfo->toArray();
            }
            $person_id = isset($faceBindInfo['person_id']) ? trim($faceBindInfo['person_id']) : '';
            if (!$person_id) {
                fdump_api(['msg' => '没有同步记录', 'pigcms_id' => $pigcms_id, 'param' => $param, 'whereFaceBind' => $whereFaceBind, 'faceBindInfo' => $faceBindInfo], 'deleteHikCloudUser/hikNeiBuHandleLog', 1);
                return true;
            }
            $whereVillageAbout = [];
            $whereVillageAbout[] = ['bind_type' => DeviceConst::HK_TO_CLOUD_VILLAGE];
            $whereVillageAbout[] = ['bind_id' => $village_id];
            $villageAboutInfo = $dbFaceBindAboutInfo->getOne($whereVillageAbout, 'id, bind_type, bind_id, bind_name, third_id');
            if ($villageAboutInfo && !is_array($villageAboutInfo)) {
                $villageAboutInfo = $villageAboutInfo->toArray();
            }
            $communityId = isset($villageAboutInfo['communityId']) ? trim($villageAboutInfo['communityId']) : '';
            if (!$communityId) {
                $cloudWorkParam = ['personId' => $person_id];
                $deleteResult = (new FaceHikCloudNeiBuDeviceService())->deletePersonToCloud($cloudWorkParam);
                if (isset($deleteResult['code']) && 200 == intval($deleteResult['code'])) {
                    $whereFaceBind[] = ['bind_type', '=', DeviceConst::HK_UID_CLOUD_USER];
                    if ($uid) {
                        $whereFaceBind[] = ['bind_id', '=', $uid];
                    } else {
                        $whereFaceBind[] = ['person_id', '=', $person_id];
                    }
                    $updateParam = [
                        'bind_id' => -$uid, 'person_id' => 'delete_' . $person_id,
                    ];
                    $dbFaceUserBindDevice->saveData($whereFaceBind, $updateParam);
                }
                fdump_api(['msg' => '没有社区同步记录-直接删除', 'pigcms_id' => $pigcms_id, 'param' => $param, 'whereVillageAbout' => $whereVillageAbout, 'villageAboutInfo' => $villageAboutInfo, 'cloudWorkParam' => $cloudWorkParam, 'deleteResult' => $deleteResult], 'deleteHikCloudUser/hikNeiBuHandleLog', 1);
            } else {
                $cloudWorkParam = ['personId' => $person_id, 'communityId' => $communityId];
                $deleteResult = (new FaceHikCloudNeiBuDeviceService())->deleteCommunityRelation($cloudWorkParam);
                if (isset($deleteResult['code']) && 200 == intval($deleteResult['code'])) {
                    $whereAbout = [];
                    $whereAbout[] = ['bind_type', '=', DeviceConst::HK_UID_BIND_COMMUNITY];
                    if ($uid) {
                        $whereAbout[] = ['bind_id', '=', $uid];
                    } else {
                        $whereAbout[] = ['code', '=', $person_id];
                    }
                    $whereAbout[] = ['person_id', '=', $communityId];
                    $updateParam = [
                        'bind_id' => -$uid, 'person_id' => 'delete_' . $communityId, 'code' => 'delete_' . $person_id,
                    ];
                    $dbFaceUserBindDevice->saveData($whereAbout, $updateParam);
                }
                fdump_api(['msg' => '删除工作人员信息', 'pigcms_id' => $pigcms_id, 'param' => $param, 'cloudWorkParam' => $cloudWorkParam, 'deleteResult' => $deleteResult], 'deleteHikCloudUser/hikNeiBuHandleLog', 1);
            }
            return true;
        }
        if (!$room_id && $pigcms_id) {
            $whereOtherUser = [];
            $whereOtherUser[] = ['pigcms_id', '<>', $pigcms_id];
            $whereOtherUser[] = ['village_id', '=', $village_id];
            $whereOtherUser[] = ['uid', '=', $uid];
            $whereOtherUser[] = ['status', '=', 1];
            $userInfo = $dbHouseVillageUserBind->getOne($whereOtherUser, 'pigcms_id, village_id, uid, vacancy_id, type, phone');
            $user_pigcms_id = isset($userInfo['pigcms_id']) ? intval($userInfo['pigcms_id']) : 0;
            if ($user_pigcms_id > 0) {
                fdump_api(['msg' => '是否存在相同账号住户，存在不做删除同步', 'pigcms_id' => $pigcms_id, 'param' => $param, 'user_pigcms_id' => $user_pigcms_id], 'deleteHikCloudUser/hikNeiBuHandleLog', 1);
                return true;
            }
            // todo 直接查询下是否同步过 同步过 走下删除
            $whereFaceBind = [];
            $whereFaceBind[] = ['bind_type', '=', DeviceConst::HK_UID_CLOUD_USER];
            $whereFaceBind[] = ['bind_id', '=', $uid];
            $faceBindInfo = $dbFaceUserBindDevice->getOne($whereFaceBind, 'id, bind_type, bind_id, person_id');
            if ($faceBindInfo && !is_array($faceBindInfo)) {
                $faceBindInfo = $faceBindInfo->toArray();
            }
            $person_id = isset($faceBindInfo['person_id']) ? trim($faceBindInfo['person_id']) : '';
            if (!$person_id) {
                fdump_api(['msg' => '没有同步记录', 'pigcms_id' => $pigcms_id, 'param' => $param, 'whereFaceBind' => $whereFaceBind, 'faceBindInfo' => $faceBindInfo], 'deleteHikCloudUser/hikNeiBuHandleLog', 1);
                return true;
            }
            $whereVillageAbout = [];
            $whereVillageAbout[] = ['bind_type' => DeviceConst::HK_TO_CLOUD_VILLAGE];
            $whereVillageAbout[] = ['bind_id' => $village_id];
            $villageAboutInfo = $dbFaceBindAboutInfo->getOne($whereVillageAbout, 'id, bind_type, bind_id, bind_name, third_id');
            if ($villageAboutInfo && !is_array($villageAboutInfo)) {
                $villageAboutInfo = $villageAboutInfo->toArray();
            }
            $communityId = isset($villageAboutInfo['communityId']) ? trim($villageAboutInfo['communityId']) : '';
            if (!$communityId) {
                $cloudWorkParam = ['personId' => $person_id];
                $deleteResult = (new FaceHikCloudNeiBuDeviceService())->deletePersonToCloud($cloudWorkParam);
                if (isset($deleteResult['code']) && 200 == intval($deleteResult['code'])) {
                    $whereFaceBind[] = ['bind_type', '=', DeviceConst::HK_UID_CLOUD_USER];
                    if ($uid) {
                        $whereFaceBind[] = ['bind_id', '=', $uid];
                    } else {
                        $whereFaceBind[] = ['person_id', '=', $person_id];
                    }
                    $updateParam = [
                        'bind_id' => -$uid, 'person_id' => 'delete_' . $person_id,
                    ];
                    $dbFaceUserBindDevice->saveData($whereFaceBind, $updateParam);
                }
                fdump_api(['msg' => '没有社区同步记录-直接删除', 'pigcms_id' => $pigcms_id, 'param' => $param, 'whereVillageAbout' => $whereVillageAbout, 'villageAboutInfo' => $villageAboutInfo, 'cloudWorkParam' => $cloudWorkParam, 'deleteResult' => $deleteResult], 'deleteHikCloudUser/hikNeiBuHandleLog', 1);
            } else {
                $cloudWorkParam = ['personId' => $person_id, 'communityId' => $communityId];
                $deleteResult = (new FaceHikCloudNeiBuDeviceService())->deleteCommunityRelation($cloudWorkParam);
                if (isset($deleteResult['code']) && 200 == intval($deleteResult['code'])) {
                    $whereAbout = [];
                    $whereAbout[] = ['bind_type', '=', DeviceConst::HK_UID_BIND_COMMUNITY];
                    if ($uid) {
                        $whereAbout[] = ['bind_id', '=', $uid];
                    } else {
                        $whereAbout[] = ['code', '=', $person_id];
                    }
                    $whereAbout[] = ['person_id', '=', $communityId];
                    $updateParam = [
                        'bind_id' => -$uid, 'person_id' => 'delete_' . $communityId,
                    ];
                    $dbFaceUserBindDevice->saveData($whereAbout, $updateParam);
                }
                fdump_api(['msg' => '删除工作人员信息', 'pigcms_id' => $pigcms_id, 'param' => $param, 'cloudWorkParam' => $cloudWorkParam, 'deleteResult' => $deleteResult], 'deleteHikCloudUser/hikNeiBuHandleLog', 1);
            }
            return true;
        }
        $whereFaceBind = [];
        $whereFaceBind[] = ['bind_type', '=', DeviceConst::HK_UID_CLOUD_USER];
        $whereFaceBind[] = ['bind_id', '=', $uid];
        $faceBindInfo = $dbFaceUserBindDevice->getOne($whereFaceBind, 'id, bind_type, bind_id, person_id');
        if ($faceBindInfo && !is_array($faceBindInfo)) {
            $faceBindInfo = $faceBindInfo->toArray();
        }
        $person_id = isset($faceBindInfo['person_id']) ? trim($faceBindInfo['person_id']) : '';
        if (!$person_id) {
            fdump_api(['msg' => '没有同步记录', 'pigcms_id' => $pigcms_id, 'param' => $param, 'whereFaceBind' => $whereFaceBind, 'faceBindInfo' => $faceBindInfo], 'deleteHikCloudUser/hikNeiBuHandleLog', 1);
            return true;
        }
        $whereOtherUser = [];
        $whereOtherUser[] = ['pigcms_id', '<>', $pigcms_id];
        $whereOtherUser[] = ['village_id', '=', $village_id];
        $whereOtherUser[] = ['uid', '=', $uid];
        $whereOtherUser[] = ['status', '=', 1];
        $userInfo = $dbHouseVillageUserBind->getOne($whereOtherUser, 'pigcms_id, village_id, uid, vacancy_id, type, phone');
        $other_pigcms_id = isset($userInfo['pigcms_id']) ? intval($userInfo['pigcms_id']) : 0;
        if (!$other_pigcms_id) {
            $whereVillageAbout = [];
            $whereVillageAbout[] = ['bind_type' => DeviceConst::HK_TO_CLOUD_VILLAGE];
            $whereVillageAbout[] = ['bind_id' => $village_id];
            $villageAboutInfo = $dbFaceBindAboutInfo->getOne($whereVillageAbout, 'id, bind_type, bind_id, bind_name, third_id');
            if ($villageAboutInfo && !is_array($villageAboutInfo)) {
                $villageAboutInfo = $villageAboutInfo->toArray();
            }
            $communityId = isset($villageAboutInfo['communityId']) ? trim($villageAboutInfo['communityId']) : '';
            $cloudWorkParam = ['personId' => $person_id, 'communityId' => $communityId];
            $deleteResult = (new FaceHikCloudNeiBuDeviceService())->deleteCommunityRelation($cloudWorkParam);
            if (isset($deleteResult['code']) && 200 == intval($deleteResult['code'])) {
                $whereAbout = [];
                $whereAbout[] = ['bind_type', '=', DeviceConst::HK_UID_BIND_COMMUNITY];
                if ($uid) {
                    $whereAbout[] = ['bind_id', '=', $uid];
                } else {
                    $whereAbout[] = ['code', '=', $person_id];
                }
                $whereAbout[] = ['person_id', '=', $communityId];
                $updateParam = [
                    'bind_id' => -$uid, 'person_id' => 'delete_' . $communityId,
                ];
                $dbFaceUserBindDevice->saveData($whereAbout, $updateParam);
            }
            fdump_api(['msg' => '删除小区归属住户信息', 'pigcms_id' => $pigcms_id, 'param' => $param, 'cloudWorkParam' => $cloudWorkParam, 'deleteResult' => $deleteResult], 'deleteHikCloudUser/hikNeiBuHandleLog', 1);
        } else {
            $whereAbout = [];
            $whereAbout[] = ['bind_type' => DeviceConst::HK_TO_CLOUD_ROOM];
            $whereAbout[] = ['bind_id' => $room_id];
            $aboutInfo = $dbFaceBindAboutInfo->getOne($whereAbout, 'id, bind_type, bind_id, bind_name, third_id');
            if ($aboutInfo && !is_array($aboutInfo)) {
                $aboutInfo = $aboutInfo->toArray();
            }
            $roomId = isset($aboutInfo['third_id']) ? trim($aboutInfo['third_id']) : '';
            if (!$roomId) {
                fdump_api(['msg' => '没有房屋同步记录', 'pigcms_id' => $pigcms_id, 'param' => $param, 'whereAbout' => $whereAbout, 'aboutInfo' => $aboutInfo], 'deleteHikCloudUser/hikNeiBuHandleLog', 1);
                return true;
            }
            $cloudWorkParam = ['personId' => $person_id, 'roomId' => $roomId];
            $deleteResult = (new FaceHikCloudNeiBuDeviceService())->deleteRoomRelation($cloudWorkParam);
            if (isset($deleteResult['code']) && 200 == intval($deleteResult['code'])) {
                $whereAbout = [];
                $whereAbout[] = ['bind_type', '=', DeviceConst::HK_UID_BIND_ROOM];
                $whereAbout[] = ['person_id', '=', $person_id];
                $whereAbout[] = ['personID', '=', $room_id];
                $updateParam = [
                    'person_id' => 'delete_' . $person_id, 'personID' => 'delete_' . $room_id,
                ];
                $dbFaceUserBindDevice->saveData($whereAbout, $updateParam);
            }
            fdump_api(['msg' => '删除房屋归属住户信息', 'pigcms_id' => $pigcms_id, 'param' => $param, 'cloudWorkParam' => $cloudWorkParam, 'deleteResult' => $deleteResult], 'deleteHikCloudUser/hikNeiBuHandleLog', 1);
        }
        return true;
    }
    
    public function getOneHouseDeviceChannel($whereArr,$field='*'){
        $houseDeviceChannelDb= new HouseDeviceChannel();
        $deviceChannelObj=$houseDeviceChannelDb->getOne($whereArr,$field);
        $deviceChannelInfo=array();
        if($deviceChannelObj && !$deviceChannelObj->isEmpty()){
            $deviceChannelInfo=$deviceChannelObj->toArray();
        }
        return $deviceChannelInfo;
    }
}