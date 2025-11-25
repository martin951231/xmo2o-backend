<?php

/**
 * 同步设备处理
 */
class plan_sub_device_syn extends plan_base{

    // open_status 1 可以开门  3 欠物业费
    public function runTask($param){
        fdump_api(['信息：'.__LINE__,$param,microtime(true)],'plan_sub/subDeviceSyn',1);
        //删除计划任务
        if($param['plan_task_id']){
            $this->dropPlan($param['plan_task_id']);
        }
        if (isset($param['type'])&&!$param['paramType']) {
            $param['paramType'] = $param['type'];
        }
        switch ($param['paramType']) {
            case 'addDeviceAuthGroup':
                // 添加权限组
                $device_id = $param['device_id'];
                $pigcms_id = $param['pigcms_id'];
                $village_id = $param['village_id']?$param['village_id']:0;
                $is_all = $param['is_all']?$param['is_all']:0;
                $orderGroupId = $param['orderGroupId']?$param['orderGroupId']:'';
                set_time_limit(0);
                ini_set('memory_limit', '2048M');
                $a5Service = D('Face_door_a5_service');
                fdump_api(['添加设备先关权限组'.__LINE__,$device_id,$village_id,$is_all],'plan_sub/planAddDeviceAuthGroupLog',1);
                $otherParam = [
                    'orderGroupId' => $orderGroupId
                ];
                $deviceInfo = M('House_face_device')->where(array('device_id' => $device_id))->find();
                if (isset($deviceInfo['village_id'])&&$deviceInfo['village_id']) {
                    $village_id = $deviceInfo['village_id'];
                }
                $info = $a5Service->addDeviceAuthGroup($device_id, $village_id,[],$is_all,$otherParam);
                usleep(200000);//休眠200毫秒
                if (intval($pigcms_id)>0) {
                    $db_house_village_user_bind = D('House_village_user_bind');
                    $userArr = $db_house_village_user_bind->where(['pigcms_id'=>$pigcms_id])
                        ->field('pigcms_id,type,parent_id,uid,phone,name,single_id,floor_id,layer_id,vacancy_id,ic_card,property_endtime,village_id')
                        ->find();
                    $whereBind = [];
                    $judgeStr = '';
                    if (!$deviceInfo['floor_id']||$deviceInfo['floor_id']==-1) {
                        $groupWorkName = '全员组_'.$village_id;
                        $judgeStr = 'all';
                    } elseif ($userArr['type']==4) {
                        $groupWorkName = '工作组_'.$village_id;
                        $judgeStr = 'work';
                    } else {
                        $whereBind['device_id'] = $device_id;
                        $groupWorkName = '设备组_'.$device_id;
                        $judgeStr = 'device';
                    }
                    $whereBind['bind_name'] = $groupWorkName;
                    $group_id = D('Face_bind_about_info')->where($whereBind)->getField('group_id');
                    fdump_api(['添加权限组' . __LINE__, $whereBind, $group_id], 'plan_sub/planAddDeviceAuthGroupLog', 1);
                    if (!$group_id) {
                        $groupNameInfo     = isset($info['groupNameInfo'])     && $info['groupNameInfo']     ? $info['groupNameInfo'] : '';
                        $groupWorkNameInfo = isset($info['groupWorkNameInfo']) && $info['groupWorkNameInfo'] ? $info['groupWorkNameInfo'] : '';
                        $groupAllNameInfo  = isset($info['groupAllNameInfo'])  && $info['groupAllNameInfo']  ? $info['groupAllNameInfo'] : '';
                        if ($judgeStr == 'all' && isset($groupAllNameInfo['groupId']) && $groupAllNameInfo['groupId']) {
                            $group_id = $groupAllNameInfo['groupId'];
                        } elseif ($judgeStr == 'work' && isset($groupWorkNameInfo['groupId']) && $groupWorkNameInfo['groupId']) {
                            $group_id = $groupWorkNameInfo['groupId'];
                        } elseif ($judgeStr == 'device' && isset($groupNameInfo['groupId']) && $groupNameInfo['groupId']) {
                            $group_id = $groupNameInfo['groupId'];
                        }
                        fdump_api(['添加权限组' . __LINE__, 'judgeStr' => $judgeStr, 'group_id' => $group_id], 'plan_sub/planAddDeviceAuthGroupLog', 1);
                    }
                    
                    if ($group_id) {
                        $param = array();
                        $param['paramType'] = 'authGroupBindMember';
                        $param['device_id'] = $device_id;
                        $param['village_id'] = $village_id;
                        $param['bind_id'] = $pigcms_id;
                        $param['group_id'] = $group_id;
                        if ($orderGroupId) {
                            $param['orderGroupId'] = $orderGroupId;
                        }
                        $param['unique_id'] = 'authGroupBindMember_'.$group_id.'_'.$pigcms_id;
                        $a5AuthGroupBindMember = D('House_face_device_log')->a5AuthGroupBindMember;
                        D('Face_door_service')->add_plan($param, 'sub_device_syn', $a5AuthGroupBindMember);
                        $logParam = [
                            'log_name' => 'planAuthGroupBindMember','logType' => 'planA5','log_status' => 18,'orderGroupId' => $orderGroupId,
                            'log_content' => '设备相关权限组绑定单个人员','device_id' => $device_id,
                            'bind_id' => $pigcms_id,
                            'device_type' => 5,'village_id' => $village_id,'addTime' => time(),
                        ];
                        try {
                            D('House_face_device_log')->addLog($logParam);
                        } catch( \Exception $e) {
                            // 记录日志报错
                            fdump_api(['日志记录[设备相关权限组绑定单个人员]出错'.__LINE__.'方法'.__FUNCTION__,$param,$logParam,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
                        }
                    } else {
                        $device_sn = M('House_face_device')->where(array('device_id' => $device_id))->getField('device_sn');
                        $judgeNewAuthGroup = $a5Service->judgeNewAuthGroup($device_id,$device_sn,true);
                        $group_arr = [];
                        if (isset($judgeNewAuthGroup['floorGroup'])&&$judgeNewAuthGroup['floorGroup']) {
                            if ($userArr['type']!=4) {
                                $groupAbout = [
                                    'bind_type' => 3,
                                    'bind_id' => $userArr['floor_id'],
                                ];
                                $group_arr = M('Face_bind_about_info')->where($groupAbout)->getField('group_id',true);
                            }
                            if (empty($group_arr)) {
                                $where_new_group = [
                                    'bind_type' => 0,
                                    'device_type' => 5,
                                    'device_sn' => $device_sn,
                                ];
                                if ($userArr['type']!=4) {
                                    $where_new_group['bind_id'] = $userArr['floor_id'];
                                }
                                $group_arr = M('Face_bind_device')->where($where_new_group)->getField('group_id',true);
                            }
                        }
                        if ($group_arr) {
                            foreach ($group_arr as $val) {
                                $param = array();
                                $param['paramType'] = 'authGroupBindMember';
                                $param['device_id'] = $device_id;
                                $param['village_id'] = $village_id;
                                $param['bind_id'] = $pigcms_id;
                                $param['group_id'] = $val;
                                if ($orderGroupId) {
                                    $param['orderGroupId'] = $orderGroupId;
                                }
                                $param['unique_id'] = 'authGroupBindMember_'.$group_id.'_'.$pigcms_id;
                                $a5AuthGroupBindMember = D('House_face_device_log')->a5AuthGroupBindMember;
                                D('Face_door_service')->add_plan($param, 'sub_device_syn', $a5AuthGroupBindMember);
                                $logParam = [
                                    'log_name' => 'planSingleAuthGroupBindMember','logType' => 'planA5','log_status' => 17,'orderGroupId' => $orderGroupId,
                                    'log_content' => '单个权限组绑定人员','device_id' => $device_id,
                                    'bind_id' => $pigcms_id,'thirdNo' => $val,
                                    'device_type' => 5,'village_id' => $village_id,'addTime' => time(),
                                ];
                                try {
                                    D('House_face_device_log')->addLog($logParam);
                                } catch( \Exception $e) {
                                    // 记录日志报错
                                    fdump_api(['日志记录[单个权限组绑定人员]出错'.__LINE__.'方法'.__FUNCTION__,$param,$logParam,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
                                }
                            }
                        }
                    }
                }
                return $info;
            case 'authGroupBindDevice':
                // 权限组绑定设备
                $group_id = $param['group_id'];
                $device_id = $param['device_id'];
                $bind_id = $param['bind_id'];
                $bind_type = $param['bind_type'];
                $device_sn = $param['device_sn']?$param['device_sn']:'';
                $is_all = $param['is_all']?$param['is_all']:0;
                $orderGroupId = $param['orderGroupId']?$param['orderGroupId']:'';
                set_time_limit(0);
                ini_set('memory_limit', '2048M');
                $a5Service = D('Face_door_a5_service');
                fdump_api(['权限组绑定设备'.__LINE__,$param,$group_id, $device_id, $device_sn,$bind_id,$bind_type,$is_all],'plan_sub/planAuthGroupBindDeviceLog',1);
                $otherParam = [
                    'orderGroupId' => $orderGroupId
                ];
                usleep(200000);//休眠200毫秒
                $info = $a5Service->authGroupBindDevice($group_id, $device_id, $device_sn,$bind_id,$bind_type,$is_all,$otherParam);
                return $info;
            case 'deviceAuthGroupBindALlMember':
                // 设备权限组绑定所有相关人员
                $village_id = isset($param['village_id'])&&$param['village_id']?intval($param['village_id']):0;// 小区id
                $device_id = isset($param['device_id'])&&$param['device_id']?intval($param['device_id']):0;// 设备id
                $orderGroupId = isset($param['orderGroupId'])&&$param['orderGroupId']?trim($param['orderGroupId']):md5(uniqid().time());// 标记统一执行命令,没有就自行标记
                $otherParam = [
                    'orderGroupId' => $orderGroupId
                ];
                $info = $this->deviceAuthGroupBindALlMember($village_id,$device_id,$otherParam);
                return $info;
            case 'authGroupBindMember':
                // 单个人员绑定权限组
                $bind_id = $param['bind_id'];
                $village_id = isset($param['village_id'])&&$param['village_id']?intval($param['village_id']):0;// 小区id
                $device_id = isset($param['device_id'])&&$param['device_id']?intval($param['device_id']):0;// 设备id
                $orderGroupId = isset($param['orderGroupId'])&&$param['orderGroupId']?trim($param['orderGroupId']):md5(uniqid().time());// 标记统一执行命令,没有就自行标记
                $group_id = $param['group_id']?$param['group_id']:'';
                if (isset($param['group_id']) && !$group_id) {
                    return false;
                }
                $otherParam = [
                    'orderGroupId' => $orderGroupId
                ];
                $device_type = 5;
                $logParam = [
                    'log_name' => 'planAuthGroupMember','logType' => 'planA5','log_status' => 42,'orderGroupId' => $orderGroupId,
                    'log_content' => '单个权限组绑定人员','device_id' => $device_id,
                    'device_type' => $device_type,'village_id' => $village_id,'addTime' => time(),
                ];
                $info = $this->authGroupBindMember([$bind_id],$device_id, $group_id,$device_type, $otherParam,$logParam);
                $planParam = array();
                $planParam['type'] = 'a5IssuedByGroup';
                $planParam['device_id'] = $device_id;
                $planParam['group_id'] = $group_id;
                $planParam['village_id'] = $village_id;
                $planParam['unique_id'] = 'a5_face_issued_by_group_' . $village_id . '_' . $group_id;
                fdump_api(['权限组下发' . __LINE__, $param, $planParam], 'plan_sub/planSyncA5FaceFloorLog', 1);
                $a5IssuedByGroupTimes = D('House_face_device_log')->a5IssuedByGroup;
                D('Face_door_service')->add_plan($planParam, 'sub_device_syn', $a5IssuedByGroupTimes);
                return $info;
            case 'addSingleMember':
                // 单个人员添加
                $pigcms_id = $param['pigcms_id'];
                $orderGroupId = isset($param['orderGroupId'])&&$param['orderGroupId']?trim($param['orderGroupId']):md5(uniqid().time());// 标记统一执行命令,没有就自行标记
                $village_id = isset($param['village_id'])&&$param['village_id']?intval($param['village_id']):0;// 小区id
                $device_id = isset($param['device_id'])&&$param['device_id']?intval($param['device_id']):0;// 设备id
                if (!$village_id) {
                    $village_id = M('House_face_device')->where(array('device_id' => $device_id))->getField('village_id');
                }
                $otherParam = [
                    'orderGroupId' => $orderGroupId,
                    'village_id' => $village_id,
                    'log_id' => $param['log_id']??0
                ];
                $info = $this->addSingleMember($village_id,$device_id,$pigcms_id,$otherParam);
                fdump_api(['添加人员'.__LINE__,$village_id,$device_id, $pigcms_id, $otherParam,$info],'plan_sub/planAddSingleMemberLog',1);
                if ($info) {
                    $map = [];
                    $map['device_id'] = $device_id;
                    $device_sn = D('House_face_device')->where($map)->getField('device_sn');
                    // 存在新版权限组
                    $param = array();
                    $param['type'] = 'addDeviceAuthGroup';
                    $param['device_id'] = $device_id;
                    if ($orderGroupId) {
                        $param['orderGroupId'] = $orderGroupId;
                    }
                    $param['is_all'] = 0;
                    $param['pigcms_id'] = $pigcms_id;
                    $param['unique_id'] = 'addDeviceAuthGroup_'.$village_id.'_'.$device_id.'_'.$pigcms_id;
                    // 添加权限组优先
                    $addDeviceAuthGroupPlanTime = D('House_face_device_log')->a5AddDeviceAuthGroupPlanTime;
                    D('Face_door_service')->add_plan($param, 'sub_device_syn', $addDeviceAuthGroupPlanTime);
                    $param = [
                        'log_name' => 'adminAddAuthGroupPlan','logType' => 'planA5','log_status' => 10,'orderGroupId' => $orderGroupId,
                        'log_content' => '设备添加权限组计划任务','device_id' => $device_id,'device_sn' => $device_sn,
                        'device_type' => 5,'village_id' => $village_id,'addTime' => time(),
                    ];
                    try {
                        D('House_face_device_log')->addLog($param);
                    } catch( \Exception $e) {
                        // 记录日志报错
                        fdump_api(['日志记录[设备添加权限组计划任务]出错'.__LINE__.'方法'.__FUNCTION__,$param,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
                    }
                }
                return $info;
            case 'a5IssuedByGroup': // A5和A6 权限组信息下发
                $group_id = $param['group_id'];
                $otherParam = $param;
                $info = $this->a5IssuedByGroup($group_id,1,$otherParam);
                return $info;
        }
        $village_id = isset($param['village_id'])&&$param['village_id']?intval($param['village_id']):0;// 小区id
        $device_id = isset($param['device_id'])&&$param['device_id']?intval($param['device_id']):0;// 设备id
        $floor_id = isset($param['floor_id'])&&$param['floor_id']?trim($param['floor_id']):'';// 单元id
        $orderGroupId = isset($param['orderGroupId'])&&$param['orderGroupId']?trim($param['orderGroupId']):md5(uniqid().time());// 标记统一执行命令,没有就自行标记
        if (!$device_id) {
            fdump_api(['由于必要信息不全无法同步：'.__LINE__,$param],'plan_sub/errSubDeviceSyn',1);
            return true;
        }
        $door_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        if (empty($door_info)) {
            fdump_api(['添加设备[对应设备不存在]'.__LINE__,$device_id,$door_info],'plan_sub/errSubDeviceSyn',1);
            return true;
        }
        if (!$floor_id && $door_info['floor_id']!=-1) {
            $floor_id = $door_info['floor_id'];
        }
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $this->keepThread();
        // 暂时只是实现了A5
        switch ($door_info['device_type']) {
            case 5:
                $db_face_door_a5_service = D('Face_door_a5_service');
                $where = [];
                $where['village_id'] = $village_id;
                $where['status'] = 1;
                if ($floor_id && $floor_id != -1) {
                    // 存在单元 过滤对应单元和工作人员出来 否则是该小区所有住户
                    $where['_string'] = "(floor_id in ($floor_id) OR type=4)";//物业人员也同步
                }
                $this->keepThread();
                $userArr = D('House_village_user_bind')
                    ->where($where)
                    ->field('pigcms_id,uid')
                    ->select();
                $this->keepThread();
                if ($userArr) {
                    foreach ($userArr as $k => $v) {
                        usleep($this->loopUSleepTime);// 避免超频调用
                        $this->keepThread();
                        $db_face_door_a5_service->synDevice($v['pigcms_id'], $device_id,$door_info,$orderGroupId,$v['uid']);
                    }
                    return true;
                }
                break;
        }
        return true;
    }

    private function deviceAuthGroupBindALlMember($device_id,$village_id,$otherParam=[],$device_type=5) {
        $deviceInfo = D('House_face_device')->where(['device_id'=>$device_id])->find();
        // 1）全员组（包含工作人员），添加组永存，数据可后续绑定或者解绑
        // 2）工作人员组（对应小区所有工作人员），添加后组永存，数据可后续续绑定或者解绑
        // 3)  设备组（对应非公共区域设备一个设备一个组），添加和删除跟随设备变化，如果设备是断电操作，切换小区，添加时候提示会清空上一个小区数据，进行解绑操作
        $db_house_face_device_log = D('House_face_device_log');
        $this->keepThread();
        if ($deviceInfo && $deviceInfo['village_id']) {
            $village_id = $deviceInfo['village_id'];
        }
        // 设备限制1000 这边限制900条数据绑定
        if (!$otherParam['village_id'] && $village_id) {
            $otherParam['village_id'] = $village_id;
        }
        if (!$otherParam['device_sn'] && $deviceInfo['device_sn']) {
            $otherParam['device_sn'] = $deviceInfo['device_sn'];
        }
        // 避免 多子计划任务执行导致 处理失败 对应限制数量除以子计划任务数量 默认最少3个计划任务执行
        $sub_process_num = max(C('config.sub_process_num'), 3);
        if (!$sub_process_num) {
            $sub_process_num = 3;
        }
        // 海康云眸限制为1000数量  除以子计划任务数 后  默认再减少30个 减少出现api请求限制的可能性
        $personBindLimit = floor(1000/$sub_process_num)-30;

        if ($deviceInfo['floor_id']!=-1 && trim($deviceInfo['floor_id'])) {
            // 设备组(对应非公共区域设备一个设备一个组 保持唯一)
            $groupName = '设备组_'.$device_id;
            $deviceGroupType = $db_house_face_device_log->deviceGroupType;
            // 查询对应设备所有人员
            $whereDevice = [];
            $whereDevice['b.village_id']=$village_id;
            $whereDevice['b.status']=1;
            $whereDevice['_string']="(b.floor_id in ({$deviceInfo['floor_id']}))";
            $whereDevice['a.device_type']=$device_type;
            $whereDevice['a.bind_type']=0;
            $whereDevice['a.group_id']=['neq',''];
            $loopDevice = $this->loopAuthBindGroup($groupName,$deviceGroupType,$village_id,$device_type,$whereDevice,$personBindLimit,$otherParam);
            // 工作人员组（对应小区所有工作人员 保持唯一）
            $this->keepThread();
            $groupWorkName = '工作组_'.$village_id;
            $workGroupType = $db_house_face_device_log->workGroupType;
            // 查询对应小区所有工作人员
            $whereWork = [];
            $whereWork['b.village_id']=$village_id;
            $whereWork['b.status'] = 1;
            $whereWork['b.type'] = 4;
            $whereWork['a.device_type']=$device_type;
            $whereWork['a.bind_type']=0;
            $whereWork['a.group_id']=['neq',''];
            $loopWork = $this->loopAuthBindGroup($groupWorkName,$workGroupType,$village_id,$device_type,$whereWork,$personBindLimit,$otherParam);
            return [
                'loopDevice' => $loopDevice,
                'loopWork' => $loopWork,
            ];
        } else {
            // 全员组
            $groupAllName = '全员组_'.$village_id;
            $allGroupType = $db_house_face_device_log->allGroupType;
            $whereAll = [];
            $whereAll['b.village_id']=$village_id;
            $whereAll['b.status'] = 1;
            $whereAll['a.device_type']=$device_type;
            $whereAll['a.bind_type']=0;
            $whereAll['a.group_id']=['neq',''];
            $loopAll = $this->loopAuthBindGroup($groupAllName,$allGroupType,$village_id,$device_type,$whereAll,$personBindLimit,$otherParam);
            return $loopAll;
        }
    }

    // 多个权限公用
    private function loopAuthBindGroup($groupName,$bindType,$bindId,$device_id,$device_type,$whereBind,$personBindLimit,$otherParam=[]) {
        $this->keepThread();
        $orderGroupId = isset($otherParam['orderGroupId'])&&$otherParam['orderGroupId']?trim($otherParam['orderGroupId']):'';// 标记统一执行命令
        $device_sn = isset($otherParam['device_sn'])&&$otherParam['device_sn']?trim($otherParam['device_sn']):'';
        $village_id = isset($otherParam['village_id'])&&$otherParam['village_id']?trim($otherParam['village_id']):'';
        $db_face_user_bind_device = D('Face_user_bind_device');
        $bindCount = $db_face_user_bind_device->alias('a')
            ->join(' LEFT JOIN '.C('DB_PREFIX').'house_village_user_bind as b ON a.bind_id=b.pigcms_id')
            ->where($whereBind)->count();
        $logParam = [
            'log_name' => 'planAuthGroupMember','logType' => 'planA5','log_status' => 42,'orderGroupId' => $orderGroupId,
            'log_content' => '单个权限组绑定人员','device_id' => $device_id,'device_sn' => $device_sn,
            'device_type' => $device_type,'village_id' => $village_id,'addTime' => time(),
        ];
        if ($bindCount>0) {
            $bindGroupId = $this->getDeviceGroupId($groupName, $bindType, $bindId, $device_id,$device_type,$otherParam);
        } else {
            fdump_api(["[{$groupName}]组无关联已同步的人员：".__LINE__,$groupName,$bindType,$bindId,$device_id,$device_type,$whereBind,$personBindLimit,$otherParam,$db_face_user_bind_device->getDbError()],'plan_sub/noticeSubDeviceSynLoopAuthBindGroupLog',1);
            $logParam['log_content'] = "{$groupName}权限组无关联已同步的人员";
            $logParam['log_status'] = 19;
            $logParam['errReason'] = "{$groupName}权限组无关联已同步的人员";
            try {
                D('House_face_device_log')->addLog($logParam);
            } catch( \Exception $e) {
                // 记录日志报错
                fdump_api(['日志记录[权限组无关联已同步的人员]出错'.__LINE__.'方法'.__FUNCTION__,$db_face_user_bind_device->getDbError(),$logParam,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
            }
            return true;
        }
        $this->keepThread();
        $otherParam['groupName'] = $groupName;
        $otherParam['bindType'] = $bindType;
        $otherParam['bindId'] = $bindId;
        if ($bindGroupId && $bindCount>$personBindLimit) {
            // 对应设备权限组存在 且超出数量限制 循环下发
            $pages = ceil($bindCount / $personBindLimit); // 获取需要循环的页数
            //权限组下发设备
            for ($page = 1; $page <= $pages; $page++) {
                // 一次下发休眠2秒
                sleep(2);
                $this->keepThread();
                $bindAllArr = $db_face_user_bind_device->alias('a')
                    ->join(' LEFT JOIN '.C('DB_PREFIX').'house_village_user_bind as b ON a.bind_id=b.pigcms_id')
                    ->page($page,$personBindLimit)->where($whereBind)->getField('bind_id',true);
                if (!empty($bindAllArr)) {
                    $this->authGroupBindMember($bindAllArr,$device_id,$bindGroupId,$device_type,$otherParam,$logParam);
                } else {
                    fdump_api(["第{$page}页全员组无关联已同步的人员：".__LINE__,$db_face_user_bind_device->getDbError()],'plan_sub/errSubDeviceSynLoopAuthBindGroupLog',1);
                }
            }
        } elseif ($bindGroupId && $bindCount>0) {
            // 对应设备权限组存在 900 条一下 设备组的所有人员 直接下发
            $bindAllArr = $db_face_user_bind_device->alias('a')
                ->join(' LEFT JOIN '.C('DB_PREFIX').'house_village_user_bind as b ON a.bind_id=b.pigcms_id')
                ->where($whereBind)->getField('bind_id',true);
            $this->keepThread();
            if (!empty($bindAllArr)) {
                // 一次下发休眠2秒
                sleep(2);
                $bind = $this->authGroupBindMember($bindAllArr,$device_id,$bindGroupId,$device_type,$otherParam,$logParam);
            } else {
                fdump_api(["[{$groupName}]组无关联已同步的人员：".__LINE__,$groupName,$bindType,$bindId,$device_id,$device_type,$whereBind,$personBindLimit,$otherParam,$db_face_user_bind_device->getDbError()],'plan_sub/noticeSubDeviceSynLoopAuthBindGroupLog',1);
                $logParam['log_content'] = "{$groupName}权限组无关联已同步的人员";
                $logParam['log_status'] = 19;
                $logParam['errReason'] = "{$groupName}权限组无关联已同步的人员";
                try {
                    D('House_face_device_log')->addLog($logParam);
                } catch( \Exception $e) {
                    // 记录日志报错
                    fdump_api(['1日志记录[权限组无关联已同步的人员]出错'.__LINE__.'方法'.__FUNCTION__,$db_face_user_bind_device->getDbError(),$logParam,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
                }
            }
        }
        if ($bindGroupId) {
            usleep($this->loopUSleepTime);// 避免超频调用
            $this->keepThread();
            $this->a5IssuedByGroup($bindGroupId,$bindCount,$otherParam);
        }
        return $bindGroupId;
    }

    // 获取对应设备对应权限组id
    private function getDeviceGroupId($groupName, $bind_type, $bind_id, $device_id,$device_type=5,$otherParam=[]) {
        $this->keepThread();
        $db_face_bind_about_info = D('Face_bind_about_info');
        $db_face_door_a5_service = D('Face_door_a5_service');
        $whereBindGroup = [
            'bind_type' => $bind_type,
            'bind_id' => $bind_id,
            'bind_name' => $groupName,
            'group_id' => ['neq', ''],
        ];
        // 设备绑定权限组
        $deviceGroupId = $db_face_bind_about_info->where($whereBindGroup)->getField('group_id');
        $this->keepThread();
        if (!$deviceGroupId) {
            $bind_type = 51;
            $bind_id = $device_id;
            $otherParam['isAuthGroupBind'] = 1;
            usleep($this->loopUSleepTime);// 避免超频调用
            $groupNameInfo = $db_face_door_a5_service->addSingleAuthGroup($groupName,$bind_type,$bind_id,$device_type,$device_id,0,$otherParam);
            if (isset($groupNameInfo['groupId']) &&  $groupNameInfo['groupId']) {
                $deviceGroupId = $groupNameInfo['groupId'];
            }
            $this->keepThread();
        }
        return $deviceGroupId;
    }

    public $bind_type_user = 61; // face_bind_device表中小区用户绑定类型
    public $loopUSleepTime = 1000000; // 循环插入避免拥堵休眠毫秒数  暂定500毫秒

    public function authGroupBindMember($bind_id_arr,$device_id,$group_id='',$device_type=5,$otherParam=[],$logParam=[],$repeat=0) {
        $device_sn = D('House_face_device')->where(['device_id'=>$device_id])->getField('device_sn');
        $this->keepThread();
        $db_face_bind_device = D('Face_bind_device');
        $db_house_face_device_log = D('House_face_device_log');
        $newGroupTypeArr = $db_house_face_device_log->newGroupTypeArr;
        if (!$group_id) {
            $where_new_group = [
                'device_sn' => $device_sn,
                'bind_type' => ['in', $newGroupTypeArr],
                'bind_id' => ['gt', 0]
            ];
            $group_arr = $db_face_bind_device->where($where_new_group)->getField('group_id',true);
            if (!empty($group_arr)) {
                foreach ($group_arr as $val) {
                    usleep($this->loopUSleepTime);// 避免数据库拥堵
                    $this->keepThread();
                    $this->authGroupSingleBindMember($bind_id_arr,$device_id,$val,$device_sn,$device_type,$otherParam,$logParam);
                }
            } else {
                fdump_api(['权限组绑定人员缺少权限组'.__LINE__,$bind_id_arr,$device_id,$group_id,$device_type,$otherParam], 'plan_sub/errSubDeviceSynAuthGroupBindMemberLog',true);
                $groupName = $otherParam['groupName'];
                $bindType = $otherParam['bindType'];
                $bindId = $otherParam['bindId'];
                if ($repeat==0 && $groupName && $bindType && $bindId) {
                    // 走生成权限组 然后重新走一遍原方法
                    $this->keepThread();
                    $db_face_door_a5_service = D('Face_door_a5_service');
                    $groupNameInfo = $db_face_door_a5_service->addSingleAuthGroup($groupName,$bindType,$bindId,$device_type,$device_id,0,$otherParam);
                    if (isset($groupNameInfo['groupId']) && $groupNameInfo['groupId']) {
                        $this->keepThread();
                        return $this->authGroupBindMember($bind_id_arr,$device_id,$groupNameInfo['groupId'],$device_type,$otherParam,$logParam,1);
                    }
                } else {
                    $logParam['log_content'] = "无相关权限组权限组";
                    $logParam['log_status'] = 44;
                    $logParam['errReason'] = "设备ID：{$device_id}权限组不存在";
                    $logParam['bindIds'] = implode(',',$bind_id_arr);
                    try {
                        D('House_face_device_log')->addLog($logParam);
                    } catch( \Exception $e) {
                        // 记录日志报错
                        fdump_api(['日志记录[权限组无关联已同步的人员]出错'.__LINE__.'方法'.__FUNCTION__,$db_face_bind_device->getDbError(),$logParam,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
                    }
                }
            }
        } else {
            usleep($this->loopUSleepTime);// 避免数据库拥堵
            $this->authGroupSingleBindMember($bind_id_arr,$device_id,$group_id,$device_sn,$device_type,$otherParam,$logParam);
        }
        return $group_id;
    }

    private function authGroupSingleBindMember($bind_id_arr,$device_id,$group_id,$device_sn='',$device_type=5,$otherParam=[],$logParam=[],$repeat=0) {
        if (!$device_sn) {
            $device_sn = D('House_face_device')->where(['device_id'=>$device_id])->getField('device_sn');
        }
        $village_id = isset($otherParam['village_id'])&&$otherParam['village_id']?trim($otherParam['village_id']):'';
        $orderGroupId = isset($otherParam['orderGroupId'])&&$otherParam['orderGroupId']?trim($otherParam['orderGroupId']):'';// 标记统一执行命令
        $this->keepThread();
        $db_face_bind_device = D('Face_bind_device');
        $nowTime = time();
        // 日志参数处理
        $logParam['thirdNo'] = $group_id;
        $logParam['village_id'] = $village_id;
        $logParam['device_sn'] = $device_sn;
        $logParam['toType'] = 1;
        $logParam['arriveTypeId'] = $group_id;
        $logParam['groupId'] = $group_id;
        $logParam['deviceType'] = $device_type;
        $logParam['deviceId'] = $device_id;
        $logParam['deviceSn'] = $device_sn;
        $logParam['orderGroupId'] = $orderGroupId;
        // 请求海康接口
        $db_face_door_a5_service = D('Face_door_a5_service');
        $url = $db_face_door_a5_service->a5_face_class->bindAuthGroupMember;
        $arr = json_encode([
            "groupId"=>$group_id,
            "employeeNos"=>$bind_id_arr,
        ]);
        usleep($this->loopUSleepTime);// 避免数据库拥堵
        $this->keepThread();
        $info = $db_face_door_a5_service->a5_face_class->curlPost($url,$arr,1,$db_face_door_a5_service->token);
        fdump_api(['权限组绑定人员调用'.__LINE__,$url,$arr,$db_face_door_a5_service->token,$info],'plan_sub/subDeviceSynAuthGroupSingleBindMemberLog',1);
        if($info['code'] == 200)
        {
            $bind_type_user = $this->bind_type_user;
            $addIdArr = [];
            // 日志参数处理
            $logParam['log_content'] = "权限组绑定人员成功";
            $logParam['log_status'] = 42;
            $logParam['arriveType'] = 6;
            $logParam['toStatus'] = 2;
            // 去重查询
            $where = [];
            $where['bind_type'] = $bind_type_user;
            $where['group_id'] = $group_id;
            $where['device_type'] = $device_type;
            // 保存参数
            $bindData = [
                'bind_type' =>$bind_type_user,
                'device_sn' =>$device_sn,
                'device_type' =>$device_type,
                'group_id' =>$group_id,
                'info' =>serialize($info),
                'add_time' =>$nowTime,
                'status' =>0,
            ];
            foreach ($bind_id_arr as $vals) {
                $this->keepThread();
                $where['bind_id'] = $vals;
                $msgId = $db_face_bind_device->where($where)->getField('id');
                if (!$msgId) {
                    $this->keepThread();
                    $bindData['bind_id'] = $vals;
                    $addId = $db_face_bind_device->add($bindData);
                    fdump_api(['记录'.__LINE__,$addId,$bindData],'plan_sub/subDeviceSynAuthGroupSingleBindMemberLog',1);
                    if (!$addId) {
                        fdump_api(['人员绑定权限组记录失败'.__LINE__,$addId,$bindData,$db_face_bind_device->getDbError(),$db_face_bind_device->_sql()],'plan_sub/errSubDeviceSynAuthGroupSingleBindMemberLog',1);
                    }
                    $addIdArr[] = $addId;
                } else {
                    $saveData = $bindData;
                    $saveData['bind_id'] = $vals;
                    unset($saveData['add_time']);
                    $db_face_bind_device->where($where)->save($saveData);
                    fdump_api(['保存'.__LINE__,$msgId,$saveData],'plan_sub/subDeviceSynAuthGroupSingleBindMemberLog',1);
                    $addIdArr[] = $msgId;
                }
                $this->keepThread();
                $logParam['bind_id'] = $vals;
                $logParam['toTypeId'] = $vals;
                try {
                    D('House_face_device_log')->addLog($logParam);
                } catch( \Exception $e) {
                    // 记录日志报错
                    fdump_api(['日志记录[权限组绑定人员成功]出错'.__LINE__.'方法'.__FUNCTION__,$logParam,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
                }
            }
            $whereUserBindDevice = [];
            $whereUserBindDevice['device_type'] = 5;
            $whereUserBindDevice['bind_id'] = ['in',$bind_id_arr];
            D('Face_user_bind_device')->where($whereUserBindDevice)->data(['group_id'=>$group_id])->save();
            fdump_api(['人员绑定权限组人员'.__LINE__,$addIdArr,$bind_id_arr,$group_id,$device_sn,$device_type],'plan_sub/subDeviceSynAuthGroupSingleBindMemberLog',1);
        }else{
            if ($repeat==0 && !$info && !$info['code']) {
                // 如果没有反馈 休眠1秒然后重复执行一次
                sleep(1);
                $this->keepThread();
                return $this->authGroupSingleBindMember($bind_id_arr,$device_id,$group_id,$device_sn,$device_type,$otherParam,$logParam,1);
            }
            $logParam['log_content'] = "权限组绑定人员失败";
            $logParam['log_status'] = 43;
            $logParam['arriveType'] = 6;
            $logParam['toStatus'] = 3;
            if (isset($info['message'])) {
                $logParam['errReason'] = "{$group_id}权限组绑定人员失败:".$info['message'];
            } else {
                $logParam['errReason'] = "{$group_id}权限组绑定人员失败";
            }
            foreach ($bind_id_arr as $vals) {
                usleep($this->loopUSleepTime);// 避免数据库拥堵
                $this->keepThread();
                $logParam['bind_id'] = $vals;
                $logParam['toTypeId'] = $vals;
                try {
                    D('House_face_device_log')->addLog($logParam);
                } catch( \Exception $e) {
                    // 记录日志报错
                    fdump_api(['日志记录[权限组绑定人员失败]出错'.__LINE__.'方法'.__FUNCTION__,$logParam,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
                }
            }
            fdump_api(['权限组绑定人员失败'.__LINE__, $url,$arr,$db_face_door_a5_service->token, $info,$bind_id_arr,$device_id,$group_id,$device_type,$otherParam], 'plan_sub/errSubDeviceSynAuthGroupSingleBindMemberLog',true);
            return $info;
        }
        return true;
    }

    // 权限组下发
    public function a5IssuedByGroup($group_id,$bindCount=0,$otherParam=[])
    {
        $db_face_door_a5_service = D('Face_door_a5_service');
        D('Face_bind_device')->where(['group_id'=>$group_id])->data(['status'=>3])->save();
        $url = $db_face_door_a5_service->a5_face_class->issuedByGroup;
        $postArr = json_encode([
            'groupId'=>$group_id
        ]);
        usleep($this->loopUSleepTime);// 避免接口请求超频
        $info = $db_face_door_a5_service->a5_face_class->curlPost($url,$postArr,1,$db_face_door_a5_service->token);

        $orderGroupId = isset($otherParam['orderGroupId'])&&$otherParam['orderGroupId']?trim($otherParam['orderGroupId']):'';// 标记统一执行命令
        $device_id = isset($otherParam['device_id'])&&$otherParam['device_id']?trim($otherParam['device_id']):'';
        $device_sn = isset($otherParam['device_sn'])&&$otherParam['device_sn']?trim($otherParam['device_sn']):'';
        $village_id = isset($otherParam['village_id'])&&$otherParam['village_id']?trim($otherParam['village_id']):'';

        $logParam = [
            'log_name' => 'planIssuedAuthGroup','logType' => 'plantA5','log_status' => 51,'orderGroupId' => $orderGroupId,
            'log_content' => '下发权限组成功','device_id' => $device_id,'device_sn' => $device_sn,
            'device_type' => 5,'village_id' => $village_id,'thirdNo' => $group_id,
            'addTime' => time(),
        ];
        if($info['code'] == 200)
        {
            fdump_api(['根据权限组ID下发权限成功>>'.__LINE__,$group_id,$url,$postArr,$db_face_door_a5_service->token,$info],'plan_sub/issuedByGroupLog',true);
            if ($bindCount>0) {
                // todo 根据一条人员下发数据最长时间5秒时间计算获取实际结果时间更新人员下发结果
            }
            try {
                D('House_face_device_log')->addLog($logParam);
            } catch( \Exception $e) {
                // 记录日志报错
                fdump_api(['日志记录[权限组下发]出错'.__LINE__.'方法'.__FUNCTION__,$logParam,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
            }
            return $info;
        }else{
            $info['message'] = $info['message'].$group_id;
            fdump_api(['根据权限组ID下发权限失败>>'.__LINE__,$group_id,$url,$postArr,$db_face_door_a5_service->token,$info],'plan_sub/errIssuedByGroupLog',true);
            $logParam['log_status'] = 52;
            $logParam['log_content'] = '下发权限组失败';
            $logParam['toStatus'] = 3;
            $logParam['errReason'] = '用户卡号已经删除';
            try {
                D('House_face_device_log')->addLog($logParam);
            } catch( \Exception $e) {
                // 记录日志报错
                fdump_api(['日志记录[权限组下发]出错'.__LINE__.'方法'.__FUNCTION__,$logParam,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
            }
            return $info;
        }
    }

    // 单个人员信息处理
    public function addSingleMember($village_id,$device_id,$pigcms_id,$otherParam) {
        $this->keepThread();
        $db_face_door_a5_service = D('Face_door_a5_service');
        $db_house_village_user_bind = D('House_village_user_bind');
        $db_house_village_layer = D('House_village_layer');
        $db_house_village_user_vacancy = D('House_village_user_vacancy');
        $userArr = $db_house_village_user_bind->where(['pigcms_id'=>$pigcms_id])
            ->field('pigcms_id,type,parent_id,uid,phone,name,single_id,floor_id,layer_id,vacancy_id,ic_card,property_endtime,village_id')
            ->find();
        $this->keepThread();
        //增加日志
        $device_info = M('House_face_device')->where(['device_id'=>$device_id])->field('device_id,device_sn,village_id,thirdProtocol,device_type')->find();
        $log_id = $otherParam['log_id']??0;
//        if(!$log_id){
//            $log_id = M('House_face_sync_log')->add([
//                'pigcms_id'=>$pigcms_id,
//                'face_id'=>0,
//                'device_id'=>$device_id,
//                'device_sn'=>$device_info['device_sn']?:'',
//                'log_time'=>time(),
//                'status'=>0,
//                'device_info'=>json_encode($device_info),
//            ]);
//        }
        
        $userArr['end_time'] = '';
        $layer_info = $db_house_village_layer->where(['id'=>$userArr['layer_id']])->field('layer_name,layer_number')->find();
        $room_info = $db_house_village_user_vacancy->where(['pigcms_id'=>$userArr['vacancy_id']])->field('room,room_number')->find();
        $this->keepThread();
        if ($layer_info['layer_number']) {
            $layer_num = $layer_info['layer_number'];
        } elseif(!preg_match('/[\x7f-\xff]/',$layer_info['layer_name'])){
            $layer_num = $layer_info['layer_name'];
        }else {
            $layer_num = $db_face_door_a5_service->findNum($layer_info['layer_name']);
        }
        if ($room_info['room_number']) {
            $room_num = $room_info['room_number'];
        } elseif(!preg_match('/[\x7f-\xff]/',$room_info['room'])){
            $room_num = $room_info['room'];
        }else {
            $room_num = $db_face_door_a5_service->findNum($room_info['room_number']);
        }
        if(strlen($layer_num)>3){
            $layer_num =  substr($layer_num,-3,3);
        }
        if(strlen($room_num)>2){
            $room_num =  substr($room_num,-2,2);
        }
        $userArr['layer_num'] = $layer_num;
        $userArr['vacancy_num'] = $room_num;
        $this->keepThread();
        $info = $db_house_village_user_bind->getUserEndTime($pigcms_id);
        $userArr['property_endtime'] = $info['propertyEndTime'];
        $this->keepThread();
        $userArr['end_time'] = $info['propertyEndTime'];
        if ($userArr['uid']) {
            $imgUrlInfo = D('House_face_img')->where(['uid'=>$userArr['uid'], 'status' => ['in', [0,3]]])->order('id desc')->field('img_url,status')->find();
        }
        $userArr['device_id'] = $device_id;
        $userArr['log_id'] = $log_id;
        $this->keepThread();
        if($userArr && $imgUrlInfo)
        {
            $imgUrl = $imgUrlInfo['img_url'];
            if (3==$imgUrlInfo['status']) {
                import('@.ORG.face.word_encryption');
                $word_encryption = new word_encryption();
                // 针对加密图片解密
                $imgUrl = $word_encryption->text_decrypt($imgUrl);
            }
            $this->keepThread();
            fdump_api(['同步前处理前原图片'.__LINE__,$imgUrl], 'plan_sub/planAddSingleMemberLog', true);
            try {
                $imgUrl = D('Image')->transform_image($imgUrl);
                $this->keepThread();
                $imagesize1 = getimagesize($imgUrl);
                fdump_api(['同步前处理后图片'.__LINE__,$imgUrl,$imagesize1], 'a5New/addMemberSingleUserArr', true);
                if ($imagesize1[0]&&$imagesize1[0]<=420&&$imagesize1[1]&&$imagesize1[1]<=560) {
                    $img_url = $imgUrl;
                    $imagesize = $imagesize1;
                } else {
                    $img_url = thumb_img($imgUrl, 400, 540, '', true);
                    $imagesize = getimagesize($img_url);
                    if ($imagesize[0]&&$imagesize[0]<400) {
                        // 如果压缩出来的图片小于宽度480  执行裁剪
                        $img_url = thumb_img($imgUrl,400,540,'fill',true);
                    }
                }
                $this->keepThread();
                $userArr['img_url'] = $img_url;
            } catch( \Exception $e) {
                fdump_api(['同步前处理后图片错误'.__LINE__,$imgUrl,$e->getMessage()], 'a5New/addMemberSingleUserArr', true);
            }
            //添加人员 云设备
            usleep($this->loopUSleepTime);// 避免超频调用
            $res = $db_face_door_a5_service->addMembers($userArr,0,5,$otherParam);
            fdump_api(['图片压缩后和相关信息'.__LINE__,$imagesize,$res, $img_url, $village_id,$device_id,$pigcms_id,$otherParam,$imgUrl, $imgUrlInfo,$userArr], 'plan_sub/planAddSingleMemberLog', true);
            return $res;
        } elseif ($userArr) {
            usleep($this->loopUSleepTime);// 避免超频调用
            $res = $db_face_door_a5_service->addMembers($userArr,0,5,$otherParam);
            fdump_api(['无图片相关'.__LINE__, $res,$village_id,$device_id,$pigcms_id,$otherParam,$userArr], 'plan_sub/planAddSingleMemberLog', true);
            return $res;
        }else{
            return 0;
        }
        return true;
    }
}
?>