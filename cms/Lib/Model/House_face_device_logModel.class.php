<?php
/**
 * 小区人脸设备相关日志 处理和人员信息同步至设备相关信息记录
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/11/26 17:57
 */
class House_face_device_logModel extends Model
{
    public $deviceGroupType = 51; // 51设备组 此处不可修改
    public $workGroupType = 52; // 52工作组 添加后不删除 此处不可修改
    public $allGroupType = 53; // 全员组 添加后不删除 此处不可修改
    public $newGroupTypeArr = [51,52,53];// 新设备组类型数组集合 51设备组 52工作组 53 全员组
    // 记录计划任务执行顺序 数字越小执行越早
    public $a5AddDeviceAuthGroupPlanTime = -200; // A5设备添加权限组
    public $a5DeviceBindAuthGroup = -195; // 设备绑定权限组
    public $a5DeviceAllSynPlanTime = -190; // A5设备一键同步
    public $a5SubDeviceSynPlanTime = -180; // A5设备分发子计划的计划任务任务
    public $a5SingleSynPlanTime = -251; // A5单个人员同步
    public $a5CardSynPlanTime = -146; // A5单个卡片同步
    public $a5PlanDeviceAuthGroup = -190; // A5计划任务处理权限组相关
    public $a5DeviceAuthGroupBindALlMember = -80;// 设备相关人员全部绑定对应设备权限组
    public $a5AuthGroupBindMember = -70;// 设备相关人员全部绑定对应设备权限组
    public $a5SyncFaceGroupMembers = -141;// 人员数据下发（2次同步下发）
    public $a5SyncFaceGroupMember = -60;// 人员数据下发（1次同步下发）
    public $a5IssuedByGroup = -50;// 人员数据下发设备

    public $operator_type = ['houseAdmin' => '小区管理员','community' => '街道社区','propertyAdmin' => '物业总管理员','propertyWork' => '物业普通管理员'];

    public $logNameArr =[
        'adminAddDevice' => '管理员添加设备','adminEditDevice'=> '管理员编辑设备','adminDelDevice' =>'管理员删除设备','adminRestartDevice'=>'管理员重启设备',
        'adminSynDevice' => '管理员同步设备','adminAddAuthGroupPlan' => '设备添加相关权限组计划任务','adminAddAuthGroup' => '设备添加相关权限组',
        'AdminUserToDevicePlan' => '管理员操作用户同步数据至设备计划任务',
        'adminDeviceBindAuthGroup' => '设备绑定权限组','adminDeviceUnbindAuthGroup' => '设备解绑权限组','adminSynVillage' => '管理员同步小区','adminSynDoor' => '管理员同步门禁','adminDeviceAllSyn' => '管理员门禁设备一键同步',
        'planDeviceSynSingle' => '生成分发计划任务','planSynSingle' => '单人同步计划任务','planSynUser' => '同步添加人员','planSynUserEdit' => '同步更新人员',
        'planSynCard' => '同步卡片','planSynCardDel' => '同步删除卡片','planAddAuthGroup' => '设备添加相关权限组','planDeviceBindAuthGroup' => '设备绑定权限组',
        'planDeviceAuthGroupBindMember' => '设备相关权限组绑定人员计划任务','planSingleAuthGroupBindMember' => '单个权限组绑定人员计划任务','planAuthGroupBindMember' => '设备相关权限组绑定单个人员计划任务',
        'planAuthGroupMember' => '权限组绑定人员','adminDelDoor' => '注销设备',
        'userToDevicePlan' => '用户同步数据至设备计划任务',
        'MobileAdminUserToDevicePlan' => '移动管理端操作用户同步数据至设备计划任务',
    ];
    // 0-9 添加设备相关  10-19编辑设备（包含配置变更）20-29 设备一键同步，获取状态等操作 30-49同步人员相关
    public $logStatusArr = [
        1 => '成功添加设备组',2 => '添加设备组失败',3 => '成功同步设备',4 => '同步设备失败',5=> '添加设备成功',6=> '编辑设备成功',7=>'删除设备',8=>'重启设备',
        10 => '添加设备权限组计划任务',11 => '添加权限组成功',12 => '添加权限组失败',13 => '设备绑定权限组计划任务',14=>'设备绑定权限组失败',15=>'设备绑定权限组成功',
        16 => '设备相关权限组绑定人员计划任务',17 => '单个权限组绑定人员计划任务',18 => '设备相关权限组绑定单个人员',19 => '权限组无绑定对象',
        20 => '设备一键同步计划任务',21 => '一键同步分发单人计划任务',
        30 => '单人同步计划任务',31 => '同步人员成功',32 => '同步人员失败',33 => '更新人员成功',34 => '删除人员成功',35 => '更新人员失败',36 => '删除人员失败',37 => '单人卡片添加计划任务',
        38 => '添加卡片成功',39 => '添加卡片失败',40 => '删除卡片成功',41 => '删除卡片失败',42=>'权限组绑定人员成功',43=>'权限组绑定人员失败',44 => '无权限组',
        51 => '权限组下发成功', 52 => '权限组下发失败',
        61 => '设备解绑权限组成功', 62 => '设备解绑权限组失败',
        101 => '用户同步设备计划任务',
    ];
    public $operatorTypeArr = [
        'house_system' => '系统后台直接进入小区操作','house_system_property' => '系统后台进入物业再进入小区操作','house_auth_property' => '系统后台进入物业再进入小区操作',
        'house_admin_property' => '物业普通管理员进入小区操作','house_property' => '物业总管理员进入小区操作','house_admin' => '小区普通管理员操作',
        'community' => '街道社区操作', ''
    ];
    // 会记录关联的相关
    public $logAddUserToDevice = [
        'adminSynVillage','adminSynDoor','adminAddAuthGroup','adminDelDoor','adminDeviceBindAuthGroup','adminDeviceUnbindAuthGroup',
        'planSynUser','planSynUserEdit','planSynCard','planSynCardDel','planAddAuthGroup','planAuthGroupMember',

    ];
    // A4 类
    public $a4_face_class = '';

    /**
     * Notes: 记录设备相关操作日志
     * @param $param
     * @return array|false
     * @author: wanzy
     * @date_time: 2021/11/27 16:18
     */
    public function addLog($param)
    {
        if (!$param['log_name']) {
            return false;
        }
        fdump_api(['日志记录'.__LINE__.'方法'.__FUNCTION__,$param],'faceDeviceLog/deviceLog',1);
        $village_id = isset($param['village_id'])?intval($param['village_id']):0;
        $property_id = isset($param['property_id'])?intval($param['property_id']):0;
        $operator_id = 0;
        $operator_type = '';
        $log_operator = '';
        if (isset($param['operator_type']) && $param['operator_type']) {
            // 传值了以传值为准
            $operator_type = trim($param['operator_type']);
            $operator_id = isset($param['operator_id'])?intval($param['operator_id']):0;
            $log_operator = isset($param['operator_id'])?trim($param['operator_id']):'';
        } elseif (isset($param['logType']) && 'houseAdmin'==$param['logType']) {
            // 暂时只有小区后台
            $houseSLoginRole = isset(session('house')['sLoginRole']) ? session('house')['sLoginRole'] : 0;
            $houseSLoginUid = isset(session('house')['sLoginUid']) ? session('house')['sLoginUid'] : 0;
            if (6==$houseSLoginRole) {
                // 小区超级管理员-系统后台直接进入
                $systemSLoginUid = isset(session('system')['id'])?session('system')['id']:0;
                $operator_type = 'house_system';
                $operator_id = $systemSLoginUid;
                if (!$village_id &&  $houseSLoginUid) {
                    $village_id = $houseSLoginUid;
                }
                $log_operator = isset(session('system')['show_account'])?session('system')['show_account']:'';
            } elseif (7==$houseSLoginRole) {
                // 小区超级管理员-物业后台直接进入
                $propertySLoginRole = isset(session('property')['sLoginRole'])?session('property')['sLoginRole']:0;
                if (8==$propertySLoginRole) {
                    $systemSLoginUid = isset(session('system')['id'])?session('system')['id']:0;
                    // 系统后台进入物业后台然后进入小区后台
                    $operator_type = 'house_system_property';
                    $operator_id = $systemSLoginUid;
                    if (!$village_id &&  $houseSLoginUid) {
                        $village_id = $houseSLoginUid;
                    }
                    $log_operator = isset(session('system')['show_account'])?session('system')['show_account']:'';
                } elseif(61==$propertySLoginRole) {
                    $propertySLoginUid = isset(session('property')['sLoginUid'])?session('property')['sLoginUid']:0;
                    //  物业总管理员 物业授权进入
                    $operator_type = 'house_auth_property';
                    $operator_id = $propertySLoginUid;
                    if (!$village_id &&  $houseSLoginUid) {
                        $village_id = $houseSLoginUid;
                    }
                    $log_operator = isset(session('property')['show_account'])?session('property')['show_account']:'';
                } elseif(4==$propertySLoginRole) {
                    $propertySLoginUid = isset(session('property')['sLoginUid'])?session('property')['sLoginUid']:0;
                    //  物业普通管理员进入
                    $operator_type = 'house_admin_property';
                    $operator_id = $propertySLoginUid;
                    if (!$village_id &&  $houseSLoginUid) {
                        $village_id = $houseSLoginUid;
                    }
                    $log_operator = isset(session('property')['show_account'])?session('property')['show_account']:'';
                } elseif(3==$propertySLoginRole) {
                    $propertySLoginUid = isset(session('property')['sLoginUid'])?session('property')['sLoginUid']:0;
                    //  物业总管理员进入
                    $operator_type = 'house_property';
                    $operator_id = $propertySLoginUid;
                    if (!$village_id &&  $houseSLoginUid) {
                        $village_id = $houseSLoginUid;
                    }
                    $log_operator = isset(session('property')['show_account'])?session('property')['show_account']:'';
                }
            } elseif (5==$houseSLoginRole) {
                // 小区普通管理员
                $operator_type = 'house_admin';
                $operator_id = $houseSLoginUid;
                $log_operator = isset(session('house')['show_account'])?session('house')['show_account']:'';
            }
        }
        if ($village_id && !$property_id) {
            $property_id = D('House_village')->where(array('village_id'=>$village_id))->getField('property_id');
        }
        $errCode = isset($param['errCode'])?trim($param['errCode']):'';
        $errReason = isset($param['errReason'])?trim($param['errReason']):'';
        $addTime = isset($param['addTime'])?intval($param['addTime']):0;
        if (!$addTime) {
            $addTime = time();
        }
        if ($errReason) {
            $errHintArr = [$errReason,microtime(true)];
            $errHintStr = json_encode($errHintArr,JSON_UNESCAPED_UNICODE);
            $errHint = md5($errHintStr);
        } else {
            $errHint = '';
        }
        $log_name = $param['log_name'];
        // 命令权限组 如果不存在就是单独唯一的
        $orderGroupId = isset($param['orderGroupId'])&&$param['orderGroupId']?trim($param['orderGroupId']):md5(uniqid().$_SERVER['REQUEST_TIME']);;
        $logData = [
            'log_name' => $log_name,
            'operator_type' => $operator_type,
            'operator_id' => $operator_id,
            'log_operator' => $log_operator,
            'log_uid' => isset($param['log_uid'])&&$param['log_uid']?intval($param['log_uid']):0,
            'log_status' => isset($param['log_status'])&&$param['log_status']?intval($param['log_status']):0,
            'log_phone' => isset($param['log_phone'])&&$param['log_phone']?trim($param['log_phone']):'',
            'log_content' => isset($param['log_content'])&&$param['log_content']?trim($param['log_content']):'',
            'device_id' => isset($param['device_id'])&&$param['device_id']?intval($param['device_id']):0,
            'device_sn' => isset($param['device_sn'])&&$param['device_sn']?trim($param['device_sn']):'',
            'device_type' => isset($param['device_type'])&&$param['device_type']?intval($param['device_type']):0,
            'face_id' => isset($param['face_id'])&&$param['face_id']?intval($param['face_id']):0,
            'face_img' => isset($param['face_img'])&&$param['face_img']?trim($param['face_img']):'',
            'face_type' => isset($param['face_type'])&&$param['face_type']?intval($param['face_type']):1,
            'ic_card' => isset($param['ic_card'])&&$param['ic_card']?trim($param['ic_card']):'',
            'startTime' => isset($param['startTime'])&&$param['startTime']?intval($param['startTime']):0,
            'endTime' => isset($param['endTime'])&&$param['endTime']?intval($param['endTime']):0,
            'bind_id' => isset($param['bind_id'])&&$param['bind_id']?intval($param['bind_id']):0,
            'uid' => isset($param['uid'])&&$param['uid']?intval($param['uid']):0,
            'thirdNo' => isset($param['thirdNo'])&&$param['thirdNo']?trim($param['thirdNo']):'',
            'bindIds' => isset($param['bindIds'])&&$param['bindIds']?trim($param['bindIds']):'',
            'village_id' => $village_id?$village_id:0,
            'property_id' => $property_id?$property_id:0,
            'addTime' =>  $addTime,
            'orderGroupId' =>  $orderGroupId,
            'errHint' =>  $errHint?$errHint:'',
            'errCode' =>  $errCode?$errCode:'',
            'errReason' =>  $errReason?$errReason:'',
        ];
        $log_id = $this->add($logData);
        if (!$log_id) {
            fdump_api(['日志记录记录出错'.__LINE__.'方法'.__FUNCTION__,$param,$this->getDbError()],'faceDeviceLog/errDeviceLog',1);
        }
        $logAddUserToDevice = $this->logAddUserToDevice;
        if (in_array($log_name, $logAddUserToDevice) && isset($param['toType'])) {
            // 归属于判断操作类型的 记录关联信息
            $platformInfo =  isset($param['platformInfo'])&&$param['platformInfo']?trim($param['platformInfo']):'';
            if (5==$param['deviceType'] && !$platformInfo) {
                $platformInfo = C('config.house_a5_base_url').'_'.C('config.house_a5_client_id').'_'.C('config.house_a5_client_secret');
            } elseif (4==$param['deviceType'] && !$platformInfo) {
                if (!$this->a4_face_class) {
                    import('@.ORG.face.a4_face');
                    $this->a4_face_class = new a4_face();
                }
                $secret_key = $this->a4_face_class->secret_key;
                $apikey = $this->a4_face_class->apikey;
                $url = $this->a4_face_class->url;
                $accountId = $this->a4_face_class->accountId;
                $account = $this->a4_face_class->account;
                $password = $this->a4_face_class->password;
                $platformInfo = $secret_key.'_'.$apikey.'_'.$url.'_'.$accountId.'_'.$account.'_'.$password;
            } else {
                $platformInfo = '';
            }
            $isDel = isset($param['isDel'])&&$param['isDel']?trim($param['isDel']):0;
            $toParam = [
                'toType' => isset($param['toType'])&&$param['toType']?intval($param['toType']):'',
                'toTypeId' => isset($param['toTypeId'])&&$param['toTypeId']?intval($param['toTypeId']):'',
                'toUid' => isset($param['toUid'])&&$param['toUid']?intval($param['toUid']):0,
                'arriveType' => isset($param['arriveType'])&&$param['arriveType']?intval($param['arriveType']):'',
                'deviceType' => isset($param['deviceType'])&&$param['deviceType']?intval($param['deviceType']):'',
                'deviceId' => isset($param['deviceId'])&&$param['deviceId']?trim($param['deviceId']):'',
                'deviceSn' => isset($param['deviceSn'])&&$param['deviceSn']?trim($param['deviceSn']):'',
                'groupId' => isset($param['groupId'])&&$param['groupId']?trim($param['groupId']):'',
                'platformInfo' => $platformInfo,
                'toStatus' => isset($param['toStatus'])&&$param['toStatus']?trim($param['toStatus']):'',
                'arriveTypeId' => isset($param['arriveTypeId'])&&$param['arriveTypeId']?trim($param['arriveTypeId']):'',
                'addTime' => $addTime,
                'updateTime' => isset($param['updateTime'])&&$param['updateTime']?intval($param['updateTime']):0,
                'toReason' => $errReason?$errReason:'',
                'toReasonCode' => $errCode?$errCode:'',
                'toHint' => $errHint?$errHint:'',
            ];
            $this->userToDevice($toParam,$isDel);
        }
        // 返回一些数据
        return [
            'log_id' => $log_id,
            'errHint' => $errHint,
        ];
    }

    /**
     * Notes: 记录人员信息同步至设备相关信息
     * @param $param
     * @return bool|array
     * @author: wanzy
     * @date_time: 2021/12/01 01:44
     */
    public function userToDevice($param,$isDel=0)
    {
        $toType = isset($param['toType'])&&$param['toType']?intval($param['toType']):'';
        $toTypeId = isset($param['toTypeId'])&&$param['toTypeId']?intval($param['toTypeId']):'';
        $arriveType = isset($param['arriveType'])&&$param['arriveType']?intval($param['arriveType']):'';
        $deviceType = isset($param['deviceType'])&&$param['deviceType']?intval($param['deviceType']):'';
        $arriveTypeId = isset($param['arriveTypeId'])&&$param['arriveTypeId']?trim($param['arriveTypeId']):'';
        if (!$toType || !$toTypeId || !$arriveType || !$deviceType || !$arriveTypeId) {
            fdump_api(['记录人员信息同步至设备相关信息'.__LINE__.'方法'.__FUNCTION__,$param,$isDel],'faceDeviceLog/errDeviceLog',1);
            return false;
        }
        $db_face_user_to_device = M('Face_user_to_device');
        $where_repeat = [
            'toType' => $toType,
            'toTypeId' => $toTypeId,
            'arriveType' => $arriveType,
            'deviceType' => $deviceType,
            'arriveTypeId' => $arriveTypeId,
        ];
        $errHint = isset($param['toHint'])&&$param['toHint']?trim($param['toHint']):'';
        $deviceId = isset($param['deviceId'])&&$param['deviceId']?trim($param['deviceId']):'';
        $toUid = isset($param['toUid'])&&$param['toUid']?intval($param['toUid']):0;
        $deviceSn = isset($param['deviceSn'])&&$param['deviceSn']?intval($param['deviceSn']):'';
        $groupId = isset($param['groupId'])&&$param['groupId']?intval($param['groupId']):'';
        $platformInfo = isset($param['platformInfo'])&&$param['platformInfo']?intval($param['platformInfo']):'';
        $toStatus = isset($param['toStatus'])&&$param['toStatus']?intval($param['toStatus']):0;
        $toReason = isset($param['toReason'])&&$param['toReason']?intval($param['toReason']):'';
        $toReasonCode = isset($param['toReasonCode'])&&$param['toReasonCode']?intval($param['toReasonCode']):'';
        $toData = [
            'toType' => $toType,'toTypeId' => $toTypeId,'arriveType' => $arriveType,'deviceType' => $deviceType,'arriveTypeId' => $arriveTypeId,
            'toReason' => $toReason,'toReasonCode' => $toReasonCode,'toHint' => $errHint,
        ];
        // 避免未传数据重置了数据
        if ($deviceId) $toData['deviceId'] = $deviceId;
        if ($toUid) $toData['toUid'] = $toUid;
        if ($deviceSn) $toData['deviceSn'] = $deviceSn;
        if ($groupId) $toData['groupId'] = $groupId;
        if ($platformInfo) $toData['platformInfo'] = $platformInfo;
        if ($toStatus) $toData['toStatus'] = $toStatus;
        $repeat_info = $db_face_user_to_device->where($where_repeat)->find();
        if ($repeat_info&&$repeat_info['toId']) {
            $orderNum = intval($repeat_info['orderNum']) + 1;
            $toData['updateTime'] = isset($param['updateTime'])&&$param['updateTime']?trim($param['updateTime']):time();
            $toData['orderNum'] = $orderNum;
            if ($isDel==1) {
                // 如果是删除关联关系 将对应关联id值记录为负值
                if ($deviceId) $toData['toTypeId'] = -$toTypeId;
                if ($deviceId) $toData['deviceId'] = -$deviceId;
                if ($arriveTypeId) $toData['arriveTypeId'] = -$arriveTypeId;
            }
            $db_face_user_to_device->where($where_repeat)->save($toData);
            $toId = $repeat_info['toId'];
        } elseif ($isDel!=1) {
            // 已经删除的不做添加记录
            $toData['orderNum'] = 1;
            $toData['addTime'] = isset($param['addTime'])&&$param['addTime']?trim($param['addTime']):time();
            $toId = $db_face_user_to_device->add($toData);
        }
        // 返回一些数据
        return [
            'toId' => $toId,
            'errHint' => $errHint,
        ];
    }

    // A5相关
    //权限类型（person、face、card、finger）
    public $authTypeArr = [
        'person' => '人员',
        'face' => '人脸',
        'card' => '卡号',
        'finger' => '指纹',
    ];
    public $operateTypeArr = [
        'add' => '添加',
        'edit' => '更新',
        'delete' => '删除',
    ];
    public $authStatusArr = [
        'failed' => '失败',
        'success' => '成功',
    ];
    // 人员类型， normal：普通人，visitor:访客，blackList:黑名单，默认：normal
    public $personTypeArr = [
        'normal' => '普通人',
        'visitor' => '访客',
        'blackList' => '黑名单',
    ];
    public $typeArr = [
        0 => '业主',
        1 => '家属',
        2 => '租客',
        3 => '更新业主',
        4 => '工作人员',
        5 => '访客',
        6 => '出入证',
    ];
    public $relativesTypeArr = [
        0 => '',
        1 => '配偶',
        2 => '父母',
        3 => '子女',
        4 => '亲朋好友',
        5 => '公司负责人',
        6 => '公司人事',
        7 => '公司财务',
    ];
    public $userStatusArr = [
        0 => '禁用',
        1 => '正常',
        2 => '待审核',
        3 => '已删除',
        4 => '已删除',
    ];
    // 0为待完善信息，1为正常，2为禁止使用  3为待审核 4审核拒绝 5删除
    public $villageStatusArr = [
        0 => '待完善信息',
        1 => '正常',
        2 => '禁止',
        3 => '待审核',
        4 => '审核拒绝',
        5 => '删除',
    ];
    // 0 同步中（未操作） 1 同步成功 2 同步失败  5人员同步中 6人脸同步中 7人员人脸同步中
    public $faceImgStatusArr = [
        0 => '未操作',
        1 => '同步成功',
        2 => '同步失败',
        5 => '人员同步中',
        6 => '人脸同步中',
        7 => '人员人脸同步中'
    ];
    public $word_encryption = '';

    public function deviceAboutInfo($device_sn) {
        $db_face_door_a5_service = D('Face_door_a5_service');
        $deviceInfo = $db_face_door_a5_service->getEquipmentInfo($device_sn);
        $arr = [];
        $txtBox = [];// 展示信息
        if (!empty($deviceInfo)) {
            $arr['deviceInfo'] = $deviceInfo;
        }

        $txtBox[] = [
            'title'=> '海康设备信息',
            'titleColor'=> '#2681f3',
            'content'=> '',
            'contentColor'=> '#393939',
        ];
        if (isset($deviceInfo['deviceId'])&&$deviceInfo['deviceId']) {
            $txtBox[] = [
                'title'=> '海康设备ID',
                'titleColor'=> '#393939',
                'content'=> $deviceInfo['deviceId'],
                'contentColor'=> '#393939',
            ];
        }
        if (isset($deviceInfo['deviceName'])&&$deviceInfo['deviceName']) {
            $txtBox[] = [
                'title'=> '海康设备名称',
                'titleColor'=> '#393939',
                'content'=> $deviceInfo['deviceName'],
                'contentColor'=> '#393939',
            ];
        }
        if (isset($deviceInfo['deviceModel'])&&$deviceInfo['deviceModel']) {
            $txtBox[] = [
                'title'=> '海康设备型号',
                'titleColor'=> '#393939',
                'content'=> $deviceInfo['deviceModel'],
                'contentColor'=> '#393939',
            ];
        }
        if (isset($deviceInfo['deviceSerial'])&&$deviceInfo['deviceSerial']) {
            $txtBox[] = [
                'title'=> '海康设备序列号',
                'titleColor'=> '#393939',
                'content'=> $deviceInfo['deviceSerial'],
                'contentColor'=> '#393939',
            ];
        }
        if (isset($deviceInfo['groupId'])&&$deviceInfo['groupId']) {
            $txtBox[] = [
                'title'=> '海康设备组ID（非权限组）',
                'titleColor'=> '#393939',
                'content'=> $deviceInfo['groupId'],
                'contentColor'=> '#393939',
            ];
        }
        if (isset($deviceInfo['deviceStatus'])) {
            $txtBox[] = [
                'title'=> '海康设备状态',
                'titleColor'=> $deviceInfo['deviceStatus']==1?'green':'red',
                'content'=> $deviceInfo['deviceStatus']==1?'在线':'离线',
                'contentColor'=> $deviceInfo['deviceStatus']==1?'green':'red',
            ];
        }
        if (isset($deviceInfo['isEncrypt'])&&$deviceInfo['isEncrypt']) {
            $txtBox[] = [
                'title'=> '海康设备加密状态',
                'titleColor'=> '#393939',
                'content'=> $deviceInfo['isEncrypt']==0?'关闭':'开启',
                'contentColor'=> $deviceInfo['isEncrypt']==0?'red':'green',
            ];
        }
        $deviceMsg = M('House_face_device')->where(['device_sn'=>$device_sn])->find();
        $villageInfo = M('House_village')->where(['village_id' => $deviceMsg['village_id']])->field('village_id,village_name,status')->find();
        if ($villageInfo) {
            $txtBox[] = [
                'title'=> '归属小区',
                'titleColor'=> '#393939',
                'content'=> $villageInfo['village_name']."【ID:{$villageInfo['village_id']} 状态:{$this->villageStatusArr[$villageInfo['status']]}】",
                'contentColor'=> $villageInfo['status']==1?'#393939':'red',
            ];
        }

        $arr['txtBox'] = $txtBox;// 展示信息

        $txtBox = [];
        $txtBox[] = [
            'title'=> '海康设备关联权限组信息',
            'titleColor'=> '#2681f3',
            'content'=> '',
            'contentColor'=> '#393939',
        ];
        // 设备当前绑定权限组相关
        $group_id_arr = M('Face_bind_device')->where(['device_sn' => $device_sn,'device_type' => 5, 'bind_type' => ['in', [0,51,52,53]]])->group('bind_type,group_id')->select();
        $authGroupCount = count($group_id_arr);
        if (!$authGroupCount) {
            $authGroupCount = '无';
        }
        $txtBox[] = [
            'title'=> '海康设备关联权限组数量',
            'titleColor'=> '#393939',
            'content'=> $authGroupCount,
            'contentColor'=> '#393939',
        ];
        $site_url =  C('config.site_url');
        //0 单元组 51设备组  52 工作组  53 全员组
        $allAuthGroupPerson = [];
        $personTotalErr = [];
        $errMsgArr = [];
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        foreach ($group_id_arr as $vals) {
            $content = '';
            if (0==$vals['bind_type']) {
                $content .= "单元组【ID：{$vals['bind_id']}】";
            }elseif(51==$vals['bind_type']){
                $content .= '设备组';
            }elseif(52==$vals['bind_type']){
                $content .= '工作组';
            }elseif(53==$vals['bind_type']){
                $content .= '全员组';
            }
            $txtBox[] = [
                'title'=> '海康设备关联权限组',
                'titleColor'=> '#393939',
                'content'=> $content . "【{$vals['group_id']}】",
                'contentColor'=> '#393939',
            ];
            $infoToAuthGroup = $this->getMemberToAuthGroup($vals['group_id'],'',$device_sn,100,$errMsgArr);
            $info = $infoToAuthGroup['infoToAuths'];
            $errMsgArr = isset($infoToAuthGroup['errMsgArr'])&&$infoToAuthGroup['errMsgArr']?$infoToAuthGroup['errMsgArr']:[];
            if ($infoToAuthGroup['total']>0 && $info) {
                $txtBox[] = [
                    'title'=> "【{$vals['group_id']}】下发情况",
                    'titleColor'=> 'red',
                    'content'=> "下发失败{$infoToAuthGroup['total']}个（可能出现人员和图片均下发失败）",
                    'contentColor'=> 'red',
                ];
                if ($infoToAuthGroup['personErr']) {
                    $personTotalErr = array_merge($personTotalErr, $infoToAuthGroup['personErr']);
                }
                foreach ($info as $val1) {
                    $content = '';
                    if (isset($this->authTypeArr[$val1['authType']])) {
                        $content .= $this->authTypeArr[$val1['authType']];
                    }
                    if (isset($this->operateTypeArr[$val1['operateType']])) {
                        $content .= $this->operateTypeArr[$val1['operateType']];
                    }
                    if (isset($this->authStatusArr[$val1['authStatus']])) {
                        $content .= $this->authStatusArr[$val1['authStatus']];
                    }
                    if ($val1['errorMsg']) {
                        $content .= "【错误：{$val1['errorMsg']}】";
                    }
                    if ($val1['resolve']) {
                        $content .= "【解决：{$val1['resolve']}】";
                    }
                    if ($val1['errorCode']) {
                        $content .= "【错误码：{$val1['errorCode']}】";
                    }
                    if ($val1['updateTime']) {
                        $content .= "【时间：{$val1['updateTime']}】";
                    } elseif ($val1['createTime']) {
                        $content .= "【时间：{$val1['createTime']}】";
                    }
                    if ('20008'==$val1['errorCode']) {
                        $btnTxt = str_replace('请','',$val1['resolve']);
                        $btnPage = $site_url.'/shequ.php?g=House&c=User&a=faces&id='.$val1['employeeNo'];
                        $is_btn = true;
                        $otherPage = $site_url."/shequ.php?c=FaceDevice&a=housePersonPage&type=reSyn&personNo=".$val1['employeeNo'];
                        $otherTxt = '点击查看详情';
                    } elseif ('pictureModelingFailed'==$val1['errorCode']) {
                        $btnTxt = str_replace('请','',$val1['resolve']);
                        $btnPage = $site_url.'/shequ.php?g=House&c=User&a=faces&id='.$val1['employeeNo'];
                        $is_btn = true;
                        $otherPage = $site_url."/shequ.php?c=FaceDevice&a=housePersonPage&type=reSyn&personNo=".$val1['employeeNo'];
                        $otherTxt = '点击查看详情';
                    } else {
                        $btnTxt = '';
                        $btnPage = '';
                        $is_btn = false;
                        $otherPage = $site_url."/shequ.php?c=FaceDevice&a=housePersonPage&type=reSyn&personNo=".$val1['employeeNo'];
                        $otherTxt = '点击查看详情';
                    }
                    $txtBox[] = [
                        'title'=> "编号【{$val1['employeeNo']}】用户下发情况",
                        'titleColor'=> 'red',
                        'content'=> $content,
                        'contentColor'=> $val1['authStatus']=='failed'?'red':'green',
                    ];
                    $txtBox[] = [
                        'title'=> "",
                        'titleColor'=> '',
                        'content'=> '',
                        'contentColor'=> '',
                        'is_btn'=> $is_btn,
                        'btnTxt' => $btnTxt,
                        'btnPage' => $btnPage,
                        'otherPage' => $otherPage,
                        'otherTxt' => $otherTxt
                    ];
                }
            } elseif ($infoToAuthGroup['code']==200) {
                $txtBox[] = [
                    'title'=> "【{$vals['group_id']}】下发情况",
                    'titleColor'=> 'green',
                    'content'=> '下发成功',
                    'contentColor'=> 'green',
                ];
            } elseif($infoToAuthGroup['message']) {
                $txtBox[] = [
                    'title'=> "获取【{$vals['group_id']}】下发情况异常",
                    'titleColor'=> 'red',
                    'content'=> $infoToAuthGroup['message'],
                    'contentColor'=> 'red',
                ];
            }
            $groupPerson = $this->getAuthGroupAllMember($vals['group_id']);
            if ($groupPerson['total']>0 && $groupPerson['authGroupEmployeeNos']) {
                $txtBox[] = [
                    'title'=> "【{$vals['group_id']}】下绑定人员数",
                    'titleColor'=> 'green',
                    'content'=> count($groupPerson['authGroupEmployeeNos']),
                    'contentColor'=> 'green',
                ];
                $txtBox[] = [
                    'title'=> "【{$vals['group_id']}】对应编号",
                    'titleColor'=> '#393939',
                    'content'=> implode(',',$groupPerson['authGroupEmployeeNos']),
                    'contentColor'=> 'green',
                ];
                $allAuthGroupPerson = array_merge($allAuthGroupPerson,$groupPerson['authGroupEmployeeNos']);
            } elseif ($groupPerson['code']==200) {
                $txtBox[] = [
                    'title'=> "【{$vals['group_id']}】下绑定人员数",
                    'titleColor'=> '#F15C5CFF',
                    'content'=> $groupPerson['total'],
                    'contentColor'=> '#F15C5CFF',
                ];
            } elseif($groupPerson['message']) {
                $txtBox[] = [
                    'title'=> "获取【{$vals['group_id']}】下绑定人员异常",
                    'titleColor'=> 'red',
                    'content'=> $groupPerson['message'],
                    'contentColor'=> 'red',
                ];
            }
        }


        $personTotalErr = array_unique($personTotalErr);
        $allErrPersonNum = count($personTotalErr);
        $allAuthGroupPerson = array_unique($allAuthGroupPerson);
        $arr['txtBox'][] = [
            'title'=> "当前记录所有权限组人员总数",
            'titleColor'=> 'green',
            'content'=> count($allAuthGroupPerson),
            'contentColor'=> 'green',
        ];

        $db_house_village_user_bind = D('House_village_user_bind');
        $allUser = [];
        $whereUser = [];
        if ($deviceMsg['floor_id']!=-1 && $deviceMsg['floor_id']) {
            $whereUser['_string'] = "floor_id in ({$deviceMsg['floor_id']})";
        }
        $whereOwner = $whereUser;
        $whereOwner['type'] = array('in',[0,3]);
        $whereOwner['vacancy_id'] = ['gt',0];
        $whereOwner['village_id'] = $deviceMsg['village_id'];
        $whereOwner['status'] = 1;
        if (isset($whereOwner['_string'])) {
            $whereOwner['_string'] .= " AND (uid not in (0,'') OR phone not in (0,'') OR name<>'')";
        } else {
            $whereOwner['_string'] = "(uid not in (0,'') OR phone not in (0,'') OR name<>'')";
        }
        $userBindOwnerArr = $db_house_village_user_bind->where($whereOwner)->getField('pigcms_id',true);
        $userBindOwnerNum = count($userBindOwnerArr);
        $arr['txtBox'][] = [
            'title'=> "设备对应业主数",
            'titleColor'=> 'green',
            'content'=> $userBindOwnerNum,
            'contentColor'=> 'green',
        ];
        if (!empty($userBindOwnerArr)) {
            $allUser = array_merge($allUser, $userBindOwnerArr);
        }
        $whereChildren = $whereUser;
        $whereChildren['type'] = array('in',[1,2]);
        $whereChildren['vacancy_id'] = ['gt',0];
        $whereChildren['village_id'] = $deviceMsg['village_id'];
        $whereChildren['status'] = 1;
        $userBindChildrenArr = $db_house_village_user_bind->where($whereChildren)->getField('pigcms_id',true);
        $userBindChildrenNum = count($userBindChildrenArr);
        $arr['txtBox'][] = [
            'title'=> "设备对应家属租客数",
            'titleColor'=> 'green',
            'content'=> $userBindChildrenNum,
            'contentColor'=> 'green',
        ];
        if (!empty($userBindChildrenArr)) {
            $allUser = array_merge($allUser, $userBindChildrenArr);
        }
        $whereWork['type'] = 4;
        $whereWork['village_id'] = $deviceMsg['village_id'];
        $whereWork['status'] = 1;
        $userBindWorkArr = $db_house_village_user_bind->where($whereWork)->getField('pigcms_id',true);
        $userBindWorkNum = count($userBindWorkArr);
        $arr['txtBox'][] = [
            'title'=> "设备对应工作人员数",
            'titleColor'=> 'green',
            'content'=> $userBindWorkNum,
            'contentColor'=> 'green',
        ];
        if (!empty($userBindWorkArr)) {
            $allUser = array_merge($allUser, $userBindWorkArr);
        }
        $whereVisitor['type'] = 5;
        $whereVisitor['village_id'] = $deviceMsg['village_id'];
        $whereVisitor['status'] = 1;
        $userBindVisitorArr = $db_house_village_user_bind->where($whereVisitor)->getField('pigcms_id',true);
        $userBindVisitorNum = count($userBindVisitorArr);
        $arr['txtBox'][] = [
            'title'=> "设备对应访客人员数",
            'titleColor'=> 'green',
            'content'=> $userBindVisitorNum,
            'contentColor'=> 'green',
        ];
        if (!empty($userBindVisitorArr)) {
            $allUser = array_merge($allUser, $userBindVisitorArr);
        }
        $wherePass['type'] = 6;
        $wherePass['village_id'] = $deviceMsg['village_id'];
        $wherePass['status'] = 1;
        $userBindPassArr = $db_house_village_user_bind->where($wherePass)->getField('pigcms_id',true);
        $userBindPassNum = count($userBindPassArr);
        $arr['txtBox'][] = [
            'title'=> "设备对应出入证数",
            'titleColor'=> 'green',
            'content'=> $userBindPassNum,
            'contentColor'=> 'green',
        ];
        if (!empty($userBindPassArr)) {
            $allUser = array_merge($allUser, $userBindPassArr);
        }
        $countTotal = intval($userBindOwnerNum)+intval($userBindChildrenNum)+intval($userBindWorkNum)+intval($userBindVisitorNum)+intval($userBindPassNum);
        $arr['txtBox'][] = [
            'title'=> "总计",
            'titleColor'=> 'green',
            'content'=> $countTotal,
            'contentColor'=> 'green',
        ];
        $arr['txtBox'][] = [
            'title'=> "对应下发失败数(去重复总计)",
            'titleColor'=> 'red',
            'content'=> $allErrPersonNum,
            'contentColor'=> 'red',
        ];
        if (!empty($errMsgArr)) {
            foreach ($errMsgArr as $msgVal) {
                $employeeNos = array_unique($msgVal['employeeNos']);
                $arr['txtBox'][] = [
                    'title'=> $msgVal['title'],
                    'titleColor'=> 'red',
                    'content'=> count($employeeNos).'人',
                    'contentColor'=> 'red',
                ];
                $arr['txtBox'][] = [
                    'title'=> $msgVal['title'].'对应编号',
                    'titleColor'=> 'red',
                    'content'=> implode(',',$employeeNos),
                    'contentColor'=> 'red',
                ];
            }
        }
        asort($allAuthGroupPerson);
        asort($personTotalErr);
        asort($allUser);
        $diff1 = array_diff($allAuthGroupPerson, $allUser);
        $diff2 = array_diff($allUser,$allAuthGroupPerson);
        $diff1Num = count($diff1);

        if ($diff1) {
            $pageOtherIdArr = [];
            foreach ($diff1 as $v1) {
                $nowUserInfo = $db_house_village_user_bind->where(['pigcms_id' => $v1])->find();
                $otherPage = $site_url."/shequ.php?c=FaceDevice&a=housePersonPage&type=delSyn&personNo=".$v1;
                if (!$nowUserInfo) {
                    $status = '人员已删除';
                } else {
                    $status = isset($this->userStatusArr[$nowUserInfo['status']])?$this->userStatusArr[$nowUserInfo['status']]:$nowUserInfo['status'];
                }
                $pageA = '<div style="min-width: 245px;"><a style="color: red !important;text-decoration:underline;cursor:pointer;" href="'.$otherPage.'" title="编号【'.$v1.'】详情页" target="_blank">'.$v1."【状态：{$status}】".'</a></div>';
//                $pageA .= '<a class="delPagePath" style="color: red !important;text-decoration:underline;margin-left: 10px;cursor:pointer;" data-user_id='.$v1.'>点击设备方同步删除</a>';
                $pageOtherIdArr []= "<div style='display: flex;'>".$pageA."</div>";
            }
            $arr['txtBox'][] = [
                'title'=> "异常同步的用户({$diff1Num}人)[点击编号进入详情]",
                'titleColor'=> 'red',
                'content'=> implode('<br>',$pageOtherIdArr),
                'contentColor'=> 'red',
            ];
        }
        $diff2Num = count($diff2);
        if ($diff2) {
            $pageOtherIdArr = [];
            $num = 0;
            $diff2 = array_values($diff2);
            foreach ($diff2 as $key=>$v2) {
                $nowUserInfo = $db_house_village_user_bind->where(['pigcms_id' => $v2])->find();
                if (!$nowUserInfo) {
                    $status = '人员已删除';
                } else {
                    $status = isset($this->userStatusArr[$nowUserInfo['status']])?$this->userStatusArr[$nowUserInfo['status']]:$nowUserInfo['status'];
                }
                $otherPage = $site_url."/shequ.php?c=FaceDevice&a=housePersonPage&type=reSyn&personNo=".$v2;
                $title = $v2;
                if (!$nowUserInfo['uid']) {
                    $title .= "【状态：{$status}(无平台用户身份)】";
                } else {
                    $title .= "【状态：{$status} 】";
                }
                $num = $num + 1;
                $pageA = '<div style="min-width: 245px;"><a style="color: red !important;text-decoration:underline;cursor:pointer;" href="'.$otherPage.'" title="编号【'.$v2.'】详情页" target="_blank">'.$title.'</a></div>';
                $pageA .= "<a class='btnPagePath' style='color: red !important;text-decoration:underline;margin-left: 10px;cursor:pointer;' data-user_id=".$v2.">点击重新同步[".$num."]</a>";
                $pageOtherIdArr []= "<div style='display: flex;'>".$pageA."</div>";
            }
            $arr['txtBox'][] = [
                'title'=> "未成功同步的用户({$diff2Num}人)[点击编号进入详情]",
                'titleColor'=> 'red',
                'content'=> implode('<br>',$pageOtherIdArr),
                'contentColor'=> 'red',
            ];
            $is_sys_all = intval($_GET['is_sys_all']);
            if ($is_sys_all == 2) {
                foreach ($diff2 as $pigcms_id) {
                    D('House_face_img')->house_user_face_device_info(0,$pigcms_id,false,[],true,true);
                }
            }
            fdump_api([$is_sys_all, $diff2], 'diff2/'.$device_sn);
        }

        $arr['txtBox'] = array_merge($arr['txtBox'],$txtBox);// 展示信息

        $arr['txtBox'][] = [
            'title'=> "记录关联对应设备权限下人员组编号集合",
            'titleColor'=> '#393939',
            'content'=> implode(',',$allAuthGroupPerson),
            'contentColor'=> 'green',
        ];
        $arr['txtBox'][] = [
            'title'=> "记录关联对应设备权限下人员组失败编号集合",
            'titleColor'=> 'red',
            'content'=> implode(',',$personTotalErr),
            'contentColor'=> 'red',
        ];
        $arr['txtBox'][] = [
            'title'=> "本地相关人员编号集合",
            'titleColor'=> 'green',
            'content'=> implode(',',$allUser),
            'contentColor'=> 'green',
        ];
        return $arr;
    }
    public function getAuthGroupAllMember($group_id) {
        $authGroupEmployeeNos = [];
        $pageSize = 100;
        $total = 0;
        $person = D('Face_door_a5_service')->getAuthGroupMemberList($group_id,1,$pageSize);
        if (isset($person['data']) && $person['data']['total']>0 && $person['data']['rows']) {
            $total = intval($person['data']['total']);
            foreach ($person['data']['rows'] as $val2) {
                $authGroupEmployeeNos[] = $val2['employeeNo'];
            }
            $authGroupEmployeeNos = array_unique($authGroupEmployeeNos);
        }
        if ($total>$pageSize) {
            $pages = ceil($total/$pageSize);
            for ($i=2;$i<=$pages;$i++) {
                $person1 = D('Face_door_a5_service')->getAuthGroupMemberList($group_id,$i,$pageSize);
                if (isset($person1['data']) && $person1['data']['total']>0 && $person1['data']['rows']) {
                    foreach ($person1['data']['rows'] as $val21) {
                        $authGroupEmployeeNos[] = $val21['employeeNo'];
                    }
                    $authGroupEmployeeNos = array_unique($authGroupEmployeeNos);
                }
            }
        }
        return [
            'code' => $person['code'],
            'message' => $person['message'],
            'total' => $total,
            'authGroupEmployeeNos' => $authGroupEmployeeNos,
        ];
    }

    public function personAboutInfo($pigcms_id,$type='') {
        $personMsg = D('Face_door_a5_service')->getMember($pigcms_id);
        fdump($personMsg,'$personMsg');
        if ($personMsg['data']) {
            $personInfo = $personMsg['data'];
        }
        $site_url =  C('config.site_url');
        if (isset($personMsg['code']) && $personMsg['code']!=200) {
            if ($personMsg['code']==40013002) {
                $personMsg['message'] = '人员未同步成功';
            }
            $txtBox[] = [
                'title'=> '获取海康人员信息异常',
                'titleColor'=> 'red',
                'content'=> isset($personMsg['message'])&&$personMsg['message']?$personMsg['message']:'人员未同步成功',
                'contentColor'=> 'red',
            ];
        } else {
            $txtBox[] = [
                'title'=> '海康人员信息',
                'titleColor'=> '#2681f3',
                'content'=> '',
                'contentColor'=> '#393939',
            ];
            if (isset($personInfo['employeeNo'])&&$personInfo['employeeNo']) {
                $txtBox[] = [
                    'title'=> '海康人员编号',
                    'titleColor'=> '#393939',
                    'content'=> $personInfo['employeeNo'],
                    'contentColor'=> '#393939',
                ];
            }
            if (isset($personInfo['personName'])&&$personInfo['personName']) {
                $txtBox[] = [
                    'title'=> '海康人员姓名',
                    'titleColor'=> '#393939',
                    'content'=> $personInfo['personName'],
                    'contentColor'=> '#393939',
                ];
            }
            if (isset($personInfo['personType'])&&$personInfo['personType']) {
                $txtBox[] = [
                    'title'=> '海康人员类型',
                    'titleColor'=> '#393939',
                    'content'=> isset($this->personTypeArr[$personInfo['personType']])?$this->personTypeArr[$personInfo['personType']]:'普通人',
                    'contentColor'=> '#393939',
                ];
            }
            if (isset($personInfo['faceUrl'])&&$personInfo['faceUrl']) {
                $txtBox[] = [
                    'title'=> '海康人脸图片url(有效期为24小时)',
                    'titleColor'=> '#393939',
                    'content'=> $personInfo['faceUrl'],
                    'contentColor'=> '#393939',
                ];
                $txtBox[] = [
                    'title'=> '',
                    'titleColor'=> '',
                    'content'=> '',
                    'img' => $personInfo['faceUrl'],
                    'contentColor'=> '',
                ];
            }
            if (isset($personInfo['floorNo'])&&$personInfo['floorNo']) {
                $txtBox[] = [
                    'title'=> '海康人员楼层号',
                    'titleColor'=> '#393939',
                    'content'=> $personInfo['floorNo'],
                    'contentColor'=> '#393939',
                ];
            }
            if (isset($personInfo['roomNo'])&&$personInfo['roomNo']) {
                $txtBox[] = [
                    'title'=> '海康人员房间号',
                    'titleColor'=> '#393939',
                    'content'=> $personInfo['roomNo'],
                    'contentColor'=> '#393939',
                ];
            }
            if (isset($personInfo['validBeginTime'])&&$personInfo['validBeginTime']) {
                $txtBox[] = [
                    'title'=> '海康人员权限开始时间',
                    'titleColor'=> '#393939',
                    'content'=> $personInfo['validBeginTime'],
                    'contentColor'=> '#393939',
                ];
            }
            if (isset($personInfo['validEndTime'])&&$personInfo['validEndTime']) {
                $txtBox[] = [
                    'title'=> '海康人员权限结束时间',
                    'titleColor'=> '#393939',
                    'content'=> $personInfo['validEndTime'],
                    'contentColor'=> '#393939',
                ];
            }
            $txtBox[] = [
                'title'=> '海康人员权限下发情况',
                'titleColor'=> '#2681f3',
                'content'=> '',
                'contentColor'=> '#393939',
            ];
            $infoToAuthGroup = $this->getMemberToAuthGroup('',$pigcms_id);
            $info = $infoToAuthGroup['infoToAuths'];
            if ($infoToAuthGroup['total']>0 && $info) {
                $txtBox[] = [
                    'title'=> "【{$pigcms_id}】下发情况",
                    'titleColor'=> 'red',
                    'content'=> "下发失败{$infoToAuthGroup['total']}次（展示去除重复报错）",
                    'contentColor'=> 'red',
                ];
                foreach ($info as $val1) {
                    $content = '';
                    if (isset($this->authTypeArr[$val1['authType']])) {
                        $content .= $this->authTypeArr[$val1['authType']];
                    }
                    if (isset($this->operateTypeArr[$val1['operateType']])) {
                        $content .= $this->operateTypeArr[$val1['operateType']];
                    }
                    if (isset($this->authStatusArr[$val1['authStatus']])) {
                        $content .= $this->authStatusArr[$val1['authStatus']];
                    }
                    if ($val1['errorMsg']) {
                        $content .= "【错误：{$val1['errorMsg']}】";
                    }
                    if ($val1['resolve']) {
                        $content .= "【解决：{$val1['resolve']}】";
                    }
                    if ($val1['errorCode']) {
                        $content .= "【错误码：{$val1['errorCode']}】";
                    }
                    if ($val1['updateTime']) {
                        $content .= "【时间：{$val1['updateTime']}】";
                    } elseif ($val1['createTime']) {
                        $content .= "【时间：{$val1['createTime']}】";
                    }
                    if ('20008'==$val1['errorCode']) {
                        $btnTxt = str_replace('请','',$val1['resolve']);
                        $btnPage = $site_url.'/shequ.php?g=House&c=User&a=faces&id='.$pigcms_id;
                        $is_btn = true;
                    } elseif ('pictureModelingFailed'==$val1['errorCode']) {
                        $btnTxt = str_replace('请','',$val1['resolve']);
                        $btnPage = $site_url.'/shequ.php?g=House&c=User&a=faces&id='.$pigcms_id;
                        $is_btn = true;
                    } else {
                        $btnTxt = '';
                        $btnPage = '';
                        $is_btn = false;
                    }
                    $txtBox[] = [
                        'title'=> "【{$val1['employeeNo']}】下发至设备【{$val1['deviceSerial']}】情况",
                        'titleColor'=> 'red',
                        'content'=> $content,
                        'contentColor'=> $val1['authStatus']=='failed'?'red':'green',
                    ];
                    $txtBox[] = [
                        'title'=> "",
                        'titleColor'=> '',
                        'content'=> '',
                        'contentColor'=> '',
                        'is_btn'=> $is_btn,
                        'btnTxt' => $btnTxt,
                        'btnPage' => $btnPage
                    ];
                }
            } elseif ($infoToAuthGroup['code']==200) {
                $txtBox[] = [
                    'title'=> "【{$pigcms_id}】下发情况",
                    'titleColor'=> 'green',
                    'content'=> '下发成功',
                    'contentColor'=> 'green',
                ];
            } elseif($infoToAuthGroup['message']) {
                $txtBox[] = [
                    'title'=> "获取【{$pigcms_id}】下发情况异常",
                    'titleColor'=> 'red',
                    'content'=> $infoToAuthGroup['message'],
                    'contentColor'=> 'red',
                ];
            }
        }
        $db_house_village_user_bind = D('House_village_user_bind');
        $userBind = $db_house_village_user_bind->where(['pigcms_id' => $pigcms_id])->find();
        $villageInfo = M('House_village')->where(['village_id' => $userBind['village_id']])->field('village_id,village_name,status')->find();

        $site_name = C('config.site_name');
        $txtBox[] = [
            'title'=> "归属【{$site_name}】信息",
            'titleColor'=> '#2681f3',
            'content'=> '',
            'contentColor'=> '',
        ];
        $statusColor = "green";
        if (0==$userBind['status']) {
            $statusColor = 'orange';
        } elseif (in_array($userBind['status'],[3,4])) {
            $statusColor = 'red';
        }
        $txtBox[] = [
            'title'=> '当前状态',
            'titleColor'=> '#393939',
            'content'=> isset($this->userStatusArr[$userBind['status']])?$this->userStatusArr[$userBind['status']]:$userBind['status'],
            'contentColor'=> $statusColor,
        ];
        if (in_array($userBind['face_img_status'],[0,5,6,7])) {
            $faceImgStatusColor = 'orange';
        } elseif ($userBind['face_img_status']==1) {
            $faceImgStatusColor = 'green';
        } elseif ($userBind['face_img_status']==2) {
            $faceImgStatusColor = 'red';
        } else {
            $faceImgStatusColor = '#393939';
        }
        $txtBox[] = [
            'title'=> '同步状态',
            'titleColor'=> '#393939',
            'content'=> isset($this->faceImgStatusArr[$userBind['face_img_status']])?$this->faceImgStatusArr[$userBind['face_img_status']]:$userBind['face_img_status'],
            'contentColor'=> $faceImgStatusColor,
        ];
        if ($userBind['face_img_status']==2 && $userBind['face_img_reason']) {
            $txtBox[] = [
                'title'=> '失败原因',
                'titleColor'=> '#393939',
                'content'=> $userBind['face_img_reason'],
                'contentColor'=> $faceImgStatusColor,
            ];
            if(stripos($userBind['face_img_reason'], '左右角过大') !== false){
                $jjContent = '请不要左右偏头，正视镜头进行拍照';
            }
            if(stripos($userBind['face_img_reason'], '瞳距不足') !== false){
                $jjContent = '图片中人脸可能占比较小，或更换清晰度较高的照片';
            }
            if(stripos($userBind['face_img_reason'], '俯仰角过大') !== false){
                $jjContent = '请不要抬头或者低头，正视镜头进行拍照';
            }
            if ($jjContent) {
                $txtBox[] = [
                    'title'=> '解决方案',
                    'titleColor'=> 'green',
                    'content'=>  $jjContent,
                    'contentColor'=> 'green',
                ];
            }
        }
        if ($userBind['uid']) {
            $d_house_face_img = D('House_face_img');
            $faces = $d_house_face_img->field('img_url,status')->where(array('uid' => $userBind['uid'], 'status'=>['in',[0,3]], 'img_url' => array('neq', '')))->select();
            if ($faces) {
                $imgs = [];
                foreach ($faces as $img) {
                    if (3==$img['status']) {
                        if (!$this->word_encryption) {
                            import('@.ORG.face.word_encryption');
                            $this->word_encryption = new word_encryption();
                        }
                        // 加密的图片解密
                        $img_url = $this->word_encryption->text_decrypt($img['img_url']);
                    } else {
                        $img_url = $img['img_url'];
                    }
                    if ($img_url) {
                        $imgs[] = replace_file_domain($img_url);
                    }
                }
                $txtBox[] = [
                    'title'=> '原图',
                    'titleColor'=> '#393939',
                    'content'=> '',
                    'contentColor'=> '#393939',
                    'imgs' => $imgs,
                ];
            }
        }
        $reSyn = false;
        if (in_array($userBind['face_img_status'],[0,2,5,6,7]) && !empty($imgs)) {
            $reSyn = true;
            $reSynContent = '';
            $btnTxt = '点击同步';
            $btnPage = $site_url.'/shequ.php?g=House&c=User&a=faces&id='.$pigcms_id;
            $is_btn = true;
        } elseif ($type=='reSyn' || in_array($userBind['face_img_status'],[0,2,5,6,7])) {
            $reSyn = true;
            $btnTxt = '';
            $btnPage = "";
            $is_btn = false;
            $reSynContent = "<a class='btnPagePath' style='color: red !important;text-decoration:underline;cursor:pointer;' data-user_id=".$pigcms_id.">点击重新同步</a>";
        }
        if ($reSyn) {
            $txtBox[] = [
                'title'=> "",
                'titleColor'=> '',
                'content'=> $reSynContent,
                'contentColor'=> '',
                'is_btn'=> $is_btn,
                'btnTxt' => $btnTxt,
                'btnPage' => $btnPage
            ];
        }
        $txtBox[] = [
            'title'=> "小区",
            'titleColor'=> '#393939',
            'content'=> $villageInfo['village_name']."【ID：{$villageInfo['village_id']}】",
            'contentColor'=> '#393939',
        ];
        $txtBox[] = [
            'title'=> "姓名",
            'titleColor'=> '#393939',
            'content'=> $userBind['name']."【ID：{$userBind['pigcms_id']}】",
            'contentColor'=> '#393939',
        ];
        $txtBox[] = [
            'title'=> "手机号码",
            'titleColor'=> '#393939',
            'content'=> $userBind['phone'],
            'contentColor'=> '#393939',
        ];
        $typeInfo = $userBind['type'];
        if (isset($this->typeArr[$userBind['type']])) {
            $typeInfo = $this->typeArr[$userBind['type']];
            if (isset($this->relativesTypeArr[$userBind['relatives_type']]) && $userBind['type']==1) {
                $typeInfo .= "【{$this->relativesTypeArr[$userBind['relatives_type']]}】";
            }
        }
        $txtBox[] = [
            'title'=> "身份",
            'titleColor'=> '#393939',
            'content'=> $typeInfo,
            'contentColor'=> '#393939',
        ];
        $txtBox[] = [
            'title'=> "卡号",
            'titleColor'=> '#393939',
            'content'=> $userBind['ic_card']?$userBind['ic_card']:'无',
            'contentColor'=> '#393939',
        ];
        $userAddress = $db_house_village_user_bind->getUserBindAddress($pigcms_id,$userBind);
        $txtBox[] = [
            'title'=> '地址',
            'titleColor'=> '#393939',
            'content'=> $userAddress,
            'contentColor'=> '#393939',
        ];
        $titleStartTxt = '物业开始时间';
        $titleEndTxt = '物业结束时间';
        $startTime = '未设置';
        $endTime = '未设置';
        $getUserEndTime = $db_house_village_user_bind->getUserEndTime($pigcms_id);
        if (in_array($userBind['type'],[0,3])) {
            $startTime = $getUserEndTime['propertyStartTime']? date('y-m-d H:i:s',$getUserEndTime['propertyStartTime']):'未设置';
            $endTime = $getUserEndTime['propertyEndTime']? date('y-m-d H:i:s',$getUserEndTime['propertyEndTime']):'未设置';
        } elseif (in_array($userBind['type'],[5,6])) {
            $titleStartTxt = '有效访问开始时间';
            $titleEndTxt = '有效访问结束时间';
            $startTime = $getUserEndTime['propertyStartTime']? date('y-m-d H:i:s',$getUserEndTime['propertyStartTime']):'未设置';
            $endTime = $getUserEndTime['propertyEndTime']? date('y-m-d H:i:s',$getUserEndTime['propertyEndTime']):'未设置';
        } elseif (in_array($userBind['type'],[1,2]) && $userBind['parent_id']) {
            $startTime = $getUserEndTime['propertyStartTime']? date('y-m-d H:i:s',$getUserEndTime['propertyStartTime']):'未设置';
            $endTime = $getUserEndTime['propertyEndTime']? date('y-m-d H:i:s',$getUserEndTime['propertyEndTime']):'未设置';
        }
        $txtBox[] = [
            'title'=> $titleStartTxt,
            'titleColor'=> '#393939',
            'content'=> $startTime,
            'contentColor'=> '#393939',
        ];
        $txtBox[] = [
            'title'=> $titleEndTxt,
            'titleColor'=> '#393939',
            'content'=> $endTime,
            'contentColor'=> '#393939',
        ];
        $txtBox[] = [
            'title'=> '单元ID',
            'titleColor'=> '#393939',
            'content'=> intval($userBind['floor_id']),
            'contentColor'=> '#393939',
        ];
        $whereFace = [];
        $whereFace['_string'] = "concat(',',floor_id,',') like '%,".$userBind['floor_id'].",%'";
        $whereFace['is_del'] = 0;
        $db_house_face_device = D('House_face_device');
        $faceArr = $db_house_face_device->where($whereFace)->field('device_id,device_name,device_sn,floor_id')->select();

        if (!empty($faceArr)) {
            $faceTxt = '';
            foreach ($faceArr as $val) {
                $faceTxt .= "<div style='display: flex;'><div style='min-width:100px;'>【ID:{$val['device_id']}</div> <div style='min-width:200px;margin-left: 5px;'>设备名称: {$val['device_name']}</div> <div style='min-width:200px;margin-left: 5px;'>设备序号:{$val['device_sn']}】</div></div><br>";
            }
            $txtBox[] = [
                'title'=> '涉及设备',
                'titleColor'=> '#393939',
                'content'=> $faceTxt,
                'contentColor'=> '#393939',
            ];
        }
        $whereBindFace = [
            'device_type' => 5,
            'bind_id' => $pigcms_id,
        ];
        $groupIdArr = D('Face_bind_device')->where($whereBindFace)->getField('group_id',true);
        if ($groupIdArr) {
            $groupIdArr = array_unique($groupIdArr);
            $txtBox[] = [
                'title'=> '绑定权限组',
                'titleColor'=> '#393939',
                'content'=> implode('<====>',$groupIdArr),
                'contentColor'=> '#393939',
            ];
        } else {
            // todo 未绑定权限组 对应查询下定位下原因
        }

        $arr['txtBox'] = $txtBox;// 展示信息
        return $arr;
    }

    public function getMemberToAuthGroup($groupId='',$employeeNo='',$deviceSerial='',$pageSize=100,$errMsgArr = [],$isAll=true) {
        $infoToAuths = [];
        $personErr = [];
        $infoToAuthSuccess = [];
        $personErrSuccess = [];
        $total = 0;
        // 分别统计人员人脸数

        $info = D('Face_door_a5_service')->getAuthSearchStatus($groupId,1,$pageSize,$employeeNo,$deviceSerial);
        if (isset($info['data']) && $info['data']['total']>0 && $info['data']['rows']) {
            $total = intval($info['data']['total']);
            $uniqueArr = [];
            foreach ($info['data']['rows'] as $val3) {
                $key = $val3['deviceSerial'].'_'.$val3['employeeNo'].'_'.$val3['authType'].'_'.$val3['operateType'].'_'.$val3['authStatus'].'_'.$val3['errorCode'];
                if (isset($uniqueArr[$key]) && $uniqueArr[$key]) {
                    continue;
                }
                $uniqueArr[$key] = 1;
                if ($val3['authStatus']=='failed') {
                    // 失败数据
                    $infoToAuths[] = $val3;
                    $personErr[] = $val3['employeeNo'];
                    $keyArr = $val3['authType'].$val3['operateType'].$val3['authStatus'];
                    if (isset($errMsgArr[$keyArr]) && $errMsgArr[$keyArr] && isset($errMsgArr[$keyArr]['employeeNos']) && $errMsgArr[$keyArr]['employeeNos']) {
                        $errMsgArr[$keyArr]['employeeNos'][] = $val3['employeeNo'];
                    } else {
                        $errMsgArr[$keyArr] = [
                            'title' => $this->authTypeArr[$val3['authType']].$this->operateTypeArr[$val3['operateType']].$this->authStatusArr[$val3['authStatus']],
                            'keyArr' => $keyArr,
                            'employeeNos' => []
                        ];
                        $errMsgArr[$keyArr]['employeeNos'][] = $val3['employeeNo'];
                    }
                } else {
                    // 成功数据
                    $infoToAuthSuccess[] = $val3;
                    $personErrSuccess[] = $val3['employeeNo'];
                }
            }
        }
        if ($isAll && $total>$pageSize) {
            $pages = ceil($total/$pageSize);
            for ($i=2;$i<=$pages;$i++) {
                $info1 = D('Face_door_a5_service')->getAuthSearchStatus($groupId,$i,$pageSize,$employeeNo,$deviceSerial);
                if (isset($info1['data']) && $info1['data']['total']>0 && $info1['data']['rows']) {
                    foreach ($info1['data']['rows'] as $val4) {
                        $key = $val4['deviceSerial'].'_'.$val4['employeeNo'].'_'.$val4['authType'].'_'.$val4['operateType'].'_'.$val4['authStatus'].'_'.$val4['errorCode'];
                        if (isset($uniqueArr[$key]) && $uniqueArr[$key]) {
                            continue;
                        }
                        $uniqueArr[$key] = 1;
                        if ($val4['authStatus']=='failed') {
                            $infoToAuths[] = $val4;
                            $personErr[] = $val4['employeeNo'];
                            $keyArr = $val4['authType'].$val4['operateType'].$val4['authStatus'];
                            if (isset($errMsgArr[$keyArr]) && $errMsgArr[$keyArr] && isset($errMsgArr[$keyArr]['employeeNos']) && $errMsgArr[$keyArr]['employeeNos']) {
                                $errMsgArr[$keyArr]['employeeNos'][] = $val4['employeeNo'];
                            } else {
                                $errMsgArr[$keyArr] = [
                                    'title' => $this->authTypeArr[$val4['authType']].$this->operateTypeArr[$val4['operateType']].$this->authStatusArr[$val4['authStatus']],
                                    'keyArr' => $keyArr,
                                    'employeeNos' => []
                                ];
                                $errMsgArr[$keyArr]['employeeNos'][] = $val4['employeeNo'];
                            }
                        } else {
                            // 成功数据
                            $infoToAuthSuccess[] = $val4;
                            $personErrSuccess[] = $val4['employeeNo'];
                        }
                    }
                }
            }
        }
        return [
            'code' => $info['code'],
            'message' => $info['message'],
            'total' => count($infoToAuths),
            'infoToAuths' => $infoToAuths,
            'personErr' => $personErr,
            'infoToAuthSuccess' => $infoToAuthSuccess,
            'personErrSuccess' => $personErrSuccess,
            'errMsgArr' => $errMsgArr,
        ];
    }
}