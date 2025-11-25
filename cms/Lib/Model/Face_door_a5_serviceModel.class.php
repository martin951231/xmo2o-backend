<?php

/**
 * Class Face_door_a5_serviceModel
 * @author weili
 * @datetime 2020/07/20
 */
class Face_door_a5_serviceModel extends Model
{
    public $a5_face_class = '';
    public $token = '';
    public $access_token = '';
    public $plan_plan_sub_device_syn = '';
    public $loopUSleepTime = 100000;
    public function _initialize(){
        parent::_initialize();
        import('@.ORG.face.a5_face');
        $this->a5_face_class = new a5_face();
        $tokenInfo = M('House_village_face_door_code')->where(['type'=>5])->order('id desc')->find();
        if($tokenInfo)
        {
            if(time() > $tokenInfo['expires_in']+$tokenInfo['token_time']){
                $info = $this->approves();

                $dataArr = [
                    'access_token'=>$info['access_token'],
                    'expires_in'=>$info['expires_in'],
                    'token_type'=>$info['token_type'],
                    'token_time'=>time(),
                ];
                M('House_village_face_door_code')->where('id',$tokenInfo['id'])->data($dataArr)->save();
                $this->token = $dataArr['token_type'].' '.$dataArr['access_token'];
                $this->access_token = $dataArr['access_token'];
            }else{
                $this->token = $tokenInfo['token_type'].' '.$tokenInfo['access_token'];
                $this->access_token = $tokenInfo['access_token'];
            }
        }else{
            $info = $this->approves();
            $dataArr = [
                'access_token'=>$info['access_token'],
                'expires_in'=>$info['expires_in'],
                'token_type'=>$info['token_type'],
                'token_time'=>time(),
                'create_time'=>time(),
                'type'=>5,
            ];
            M('House_village_face_door_code')->add($dataArr);
            $this->token = $dataArr['token_type'].' '.$dataArr['access_token'];
            $this->access_token = $dataArr['access_token'];
        }
        if (!$this->plan_plan_sub_device_syn) {
            import('@.ORG.plan.plan_sub_device_syn');
            $this->plan_plan_sub_device_syn = new plan_sub_device_syn();
        }
        $this->loopUSleepTime = $this->plan_plan_sub_device_syn->loopUSleepTime;
    }

    public function keepPlanThread() {
        if (!$this->plan_plan_sub_device_syn) {
            import('@.ORG.plan.plan_sub_device_syn');
            $this->plan_plan_sub_device_syn = new plan_sub_device_syn();
        }
        $this->plan_plan_sub_device_syn->keepThread();
    }

    /**
     * Notes: 门禁 身份认证
     * @return bool|mixed|string
     * @author: weili
     * @datetime: 2020/7/20 15:34
     */
    private function approves()
    {
        $clientSecret = $this->a5_face_class->client_secret;
        $clientId = $this->a5_face_class->client_id;
        $url = $this->a5_face_class->url;
        $grantType = $this->a5_face_class->grant_type;
        $arrContent = [
            'client_id'=> $clientId,
            'client_secret'=>$clientSecret,
            'grant_type'=>$grantType,
        ];
        $postData = $this->a5_face_class->getEncryptKey($arrContent);
        usleep($this->loopUSleepTime);// 避免超频调用
        $this->keepPlanThread();
        $info = $this->a5_face_class->curlPost($url,$postData);
        return $info;
    }

    /**
     * Notes: 添加设备
     * @param $device_id
     * @param array $door_info
     * @return mixed
     * @author: weili
     * @datetime: 2020/7/22 16:18
     */
    public function addEquipment($device_id,$door_info=[],$otherParam=[])
    {
        if ($device_id && empty($door_info) || !$door_info['village_id']) {
            $door_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
            if (empty($door_info)) {
                fdump_api(['添加设备[对应设备不存在]'.__LINE__,$device_id,$door_info],'a5New/errAddEquipmentLog',1);
                $info['error'] = 1;
                $info['msg'] = '参数错误，对应设备不存在！';
                return $info;
            }
        }
        $village_id = $door_info['village_id'];
        $village_info = M('House_village')->where(['village_id'=> $village_id,'is_del'=>0])->field('village_id,village_name')->find();
        //同步云分组
        $groupName = $village_info['village_name'];
        $groupNo = $village_id;
        $post_data = ['groupName' => $groupName,'groupNo' => $groupNo];
        $postData = json_encode($post_data,JSON_UNESCAPED_UNICODE);
        usleep($this->loopUSleepTime);// 避免超频调用
        $this->keepPlanThread();
        $groupInfo = $this->getGroupInfo($village_id);

        if($groupInfo['code'] == 200)
        {
            $resGroup = $groupInfo;
        }else{
            usleep($this->loopUSleepTime);// 避免超频调用
            $this->keepPlanThread();
            $resGroup = $this->createGroup($postData);//同步云 创建分组
        }
        //同步云 创建设备
        // 记录日志
        $thirdNo = isset($resGroup['data'])&&isset($resGroup['data']['groupId'])?$resGroup['data']['groupId']:'';
        $device_sn = isset($door_info['device_sn'])&&$door_info['device_sn']?$door_info['device_sn']:'';
        $device_type = isset($door_info['device_type'])&&$door_info['device_type']?$door_info['device_type']:5;
        $orderGroupId = isset($otherParam['orderGroupId'])&&$otherParam['orderGroupId']?trim($otherParam['orderGroupId']):md5(uniqid().$_SERVER['REQUEST_TIME']);// 标记统一执行命令
        $param = [
            'log_name' => 'adminSynVillage','logType' => 'houseAdmin','log_status' => 1,'orderGroupId' => $orderGroupId,
            'log_content' => '创建设备组成功','device_id' => $device_id,'device_sn' => $device_sn,
            'device_type' => $device_type,'village_id' => $village_id,'thirdNo' => $thirdNo,'addTime' => time(),
            'toType' => 8,'toTypeId' => $door_info['village_id'],'arriveType' => 2,'deviceType' => $device_type,
            'deviceId' => $device_id,'deviceSn' => $device_sn,'groupId' => $thirdNo,'toStatus' => 2,'arriveTypeId' => $thirdNo,
        ];
        fdump_api(['postData'=>$postData,'resGroup'=>$resGroup],'faceDeviceLog/00addEquipment',1);
        if ($resGroup['code'] == 200 || $resGroup['code'] == 510107) {
            try {
                $param['log_status'] = 1;
                D('House_face_device_log')->addLog($param);
            } catch( \Exception $e) {
                // 记录日志报错
                fdump_api(['日志记录[创建设备组]出错'.__LINE__.'方法'.__FUNCTION__,$param,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
            }
            $post_arr = [
                'deviceSerial'=>$door_info['device_sn'],
                'groupNo'=>$door_info['village_id'],//组编号是社区id
                'validateCode'=>trim($door_info['cloud_code']),
            ];
            $postArr = json_encode($post_arr);
            $logParam = $param;
            usleep($this->loopUSleepTime);// 避免超频调用
            $this->keepPlanThread();
            $resData = $this->createMachine($device_id,$postArr,$logParam);
            fdump_api(['resData'=>$resData,'postArr'=>$postArr,'logParam'=>$logParam],'faceDeviceLog/00addEquipment',1);
            if($resData['code'] == 200){
                $info['error'] = 0;
                $info['msg'] = '同步设备成功！';
                if($door_info['is_visual'] == 0)
                {
                    $notifyUrl = C('config.site_url').'/appapi.php?c=Face_door&a=openVisual';
                    $info = $this->setNotifyUrl($notifyUrl);
                }
                // 查询下该设备会否是新权限组架构
                $judgeNewAuthGroup = $this->judgeNewAuthGroup($device_id,$door_info['device_sn']);
                if (!$judgeNewAuthGroup['judge']) {
                    // 添加一键同步功能
                    $otherParam = [
                        'orderGroupId' => $orderGroupId,
                    ];
                    D('Face_door_service')->face_device_check($device_id,$door_info,$otherParam);
                } else {
                    $param = array();
                    $param['type'] = 'addDeviceAuthGroup';
                    $param['device_id'] = $device_id;
                    $param['village_id'] = $village_id;
                    $param['orderGroupId'] = $orderGroupId;
                    $param['is_all'] = 1;
                    $param['unique_id'] = 'addDeviceAuthGroup_'.$village_id.'_'.$device_id;
                    // 添加权限组优先
                    $addDeviceAuthGroupPlanTime = D('House_face_device_log')->a5AddDeviceAuthGroupPlanTime;
                    D('Face_door_service')->add_plan($param, 'sub_device_syn', $addDeviceAuthGroupPlanTime);
                    $param = [
                        'log_name' => 'adminAddAuthGroupPlan','logType' => 'houseAdmin','log_status' => 10,'orderGroupId' => $orderGroupId,
                        'log_content' => '设备添加权限组计划任务','device_id' => $device_id,'device_sn' => $device_sn,
                        'device_type' => $device_type,'village_id' => $village_id,'addTime' => time(),
                    ];
                    try {
                        D('House_face_device_log')->addLog($param);
                    } catch( \Exception $e) {
                        // 记录日志报错
                        fdump_api(['日志记录[设备添加权限组计划任务]出错'.__LINE__.'方法'.__FUNCTION__,$param,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
                    }
                }
                return $info;
            }elseif($resData['code'] == 40012003){
                M('House_face_device')->where(array('device_id' => $device_id))->data(['device_status'=>2])->save();
                fdump_api(['添加设备[异常]'.__LINE__,$device_id,$postArr,$resData],'a5New/errAddEquipmentLog',true);
                $info['error'] = 1;
                $info['msg'] = '同步设备异常！';
                return $info;
            }else{
                M('House_face_device')->where(array('device_id' => $device_id))->data(['device_status'=>2])->save();
                fdump_api(['添加设备[错误]'.__LINE__,$device_id,$postArr,$resData],'a5New/errAddEquipmentLog',true);
                if (isset($resData['message']) && $resData['message']) {
                    $msg = $resData['message'];
                    if (isset($resData['code']) && $resData['code']) {
                        $msg .= "【错误码：{$resData['code']}】";
                    }
                } else {
                    $msg = '设备不在线或设备不存在。请确认设备并连接好设备后，重新同步设备！';
                }
                $param['log_status'] = 2;
                $param['toStatus'] = 3;
                $param['log_content'] = '创建设备组失败';
                $param['errCode'] = $resData['code'];
                $param['errReason'] = $msg;
                try {
                    D('House_face_device_log')->addLog($param);
                } catch( \Exception $e) {
                    // 记录日志报错
                    fdump_api(['日志记录出错[添加设备[同步分组异常]]'.__LINE__.'方法'.__FUNCTION__,$param,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
                }
                $info['error'] = 1;
                $info['msg'] = $msg;
                return $info;
            }
        }else{
            M('House_face_device')->where(array('device_id' => $device_id))->data(['device_status'=>2])->save();
            fdump_api(['添加设备[同步分组异常]'.__LINE__,$device_id,$resGroup],'a5New/errAddEquipmentLog',true);
            $param['log_status'] = 2;
            $param['toStatus'] = 3;
            $param['log_content'] = '创建设备组失败';
            $param['errCode'] = $resGroup['code'];
            $param['errReason'] = $resGroup['message'];
            try {
                D('House_face_device_log')->addLog($param);
            } catch( \Exception $e) {
                // 记录日志报错
                fdump_api(['日志记录出错[添加设备[同步分组异常]]'.__LINE__.'方法'.__FUNCTION__,$param,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
            }
            $info['error'] = 0;
            $info['msg'] = '同步分组异常！';
            return $info;
        }
    }

    // 判断是否存在新权限组 相关情况
    public function judgeNewAuthGroup($device_id,$device_sn='',$returnNewGroup=false) {
        $device_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        if (!$device_sn) {
            $device_sn = $device_info['device_sn'];
        }
        $where_device_floor = [
            'device_sn' => $device_sn,
            'bind_type' => 0,
            'bind_id' => ['gt', 0],
        ];
        $bindCount = M('Face_bind_device')->where($where_device_floor)->count();
        $arr = [];
        if ($bindCount && $bindCount>0) {
            $arr['floorGroup'] = true;
            $arr['judge'] = true;
        } else {
            $arr['floorGroup'] = false;
            $arr['judge'] = false;
            if ($device_info['floor_id']!=-1&&$device_info['floor_id']) {
                $floorIds = explode(',',$device_info['floor_id']);
                if (!empty($floorIds)&&$floorIds[0]) {
                    $where_device_floor1 = [
                        'bind_id' => ['in', $floorIds],
                        'bind_type' => 0,
                    ];
                    $bindCount1 = M('Face_bind_device')->where($where_device_floor1)->count();
                    if ($bindCount1 && $bindCount1>0) {
                        $arr['floorGroup'] = true;
                        $arr['judge'] = true;
                    }
                }
            } elseif ($device_info['floor_id']=-1) {
                $where_device_floor1 = [
                    'bind_id' => ['gt', 0],
                    'bind_type' => 0,
                ];
                $bindCount2 = M('Face_bind_device')->where($where_device_floor1)->count();
                if ($bindCount2 && $bindCount2>0) {
                    $arr['floorGroup'] = true;
                    $arr['judge'] = true;
                }
            }
        }
        $where_new_group = [
            'device_sn' => $device_sn,
            'bind_type' => ['in', [51,52,53]],
            'bind_id' => ['gt', 0]
        ];
        if ($returnNewGroup) {
            $group_arr = M('Face_bind_device')->where($where_new_group)->getField('group_id',true);
            $bindNewCount = count($group_arr);
            $arr['group_arr'] = $group_arr;
        } else {
            $bindNewCount = M('Face_bind_device')->where($where_new_group)->count();
        }
        if ($bindNewCount && $bindNewCount>0) {
            $arr['newGroup'] = true;
            $arr['judge'] = true;
        } else {
            $arr['newGroup'] = false;
            $arr['judge'] = false;
        }
        return $arr;
    }

    public function addDeviceAuthGroup($device_id, $village_id=0, $door_info=[],$is_all=0,$otherParam=[]) {
        if (empty($door_info) || !$village_id) {
            $door_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
            if (empty($door_info)) {
                fdump_api(['添加设备[对应设备不存在]'.__LINE__,$device_id,$door_info],'a5New/errAddDeviceAuthGroupLog',1);
                $info['error'] = 1;
                $info['msg'] = '参数错误，对应设备不存在！';
                return $info;
            }
        }
        if (!$otherParam['device_sn'] && $door_info['device_sn']) {
            $otherParam['device_sn'] = $door_info['device_sn'];
        }
        if ($door_info['village_id']) {
            $village_id = $door_info['village_id'];
        }
        if (!$otherParam['village_id'] && $village_id) {
            $otherParam['village_id'] = $village_id;
        }
        // 处理权限组问题 对应设备添加权限组（如果是公共区域设备添加1和2 否则是2和3）
        // 1）全员组（包含工作人员），添加组永存，数据可后续绑定或者解绑
        // 2）工作人员组（对应小区所有工作人员），添加后组永存，数据可后续续绑定或者解绑
        // 3)  设备组（对应非公共区域设备一个设备一个组），添加和删除跟随设备变化，如果设备是断电操作，切换小区，添加时候提示会清空上一个小区数据，进行解绑操作
        if ($door_info['floor_id']!=-1 && trim($door_info['floor_id'])) {
            // 设备组(对应非公共区域设备一个设备一个组 保持唯一)
            $groupName = '设备组_'.$device_id;
            // 工作人员组（对应小区所有工作人员 保持唯一）
            $groupWorkName = '工作组_'.$village_id;
        } else {
            // 全员组
            $groupAllName = '全员组_'.$village_id;
        }
        // 51设备组  52 工作组  53 全员组
        $device_type = 5;
        // 添加权限组是否均成功
        if ($groupName) {
            $bind_type = 51;
            $bind_id = $device_id;
            usleep($this->loopUSleepTime);// 避免超频调用
            $this->keepPlanThread();
            $groupNameInfo = $this->addSingleAuthGroup($groupName,$bind_type,$bind_id,$device_type,$device_id,$is_all,$otherParam);
            fdump_api(['添加设备权限组'.__LINE__,$groupName,$bind_type,$bind_id,$device_type,$device_id,$groupNameInfo],'a5New/addDeviceAuthGroupLog',1);
        }
        if ($groupWorkName) {
            $bind_type = 52;
            $bind_id = $village_id;
            usleep($this->loopUSleepTime);// 避免超频调用
            $this->keepPlanThread();
            $groupWorkNameInfo = $this->addSingleAuthGroup($groupWorkName,$bind_type,$bind_id,$device_type,$device_id,$is_all,$otherParam);
            fdump_api(['添加工作权限组'.__LINE__,$groupWorkName,$bind_type,$bind_id,$device_type,$device_id,$groupWorkNameInfo],'a5New/addDeviceAuthGroupLog',1);
        }
        if ($groupAllName) {
            $bind_type = 53;
            $bind_id = $village_id;
            usleep($this->loopUSleepTime);// 避免超频调用
            $this->keepPlanThread();
            $groupAllNameInfo = $this->addSingleAuthGroup($groupAllName,$bind_type,$bind_id,$device_type,$device_id,$is_all,$otherParam);
            fdump_api(['添加全员组'.__LINE__,$groupAllName,$bind_type,$bind_id,$device_type,$device_id,$groupAllNameInfo],'a5New/addDeviceAuthGroupLog',1);
        }
        return [
            'groupNameInfo' => $groupNameInfo?$groupNameInfo:[],
            'groupWorkNameInfo' => $groupWorkNameInfo?$groupWorkNameInfo:[],
            'groupAllNameInfo' => $groupAllNameInfo?$groupAllNameInfo:[],
        ];
    }

    public function addSingleAuthGroup($groupName,$bind_type,$bind_id,$device_type,$device_id,$is_all=0,$otherParam=[]) {
        // 添加权限组
        $addUrl = $this->a5_face_class->addAuthGroup;
        $postArr = json_encode([
            'groupName' => $groupName
        ]);
        $groupNameInfo = $this->a5_face_class->curlPost($addUrl, $postArr, 1, $this->token);
        fdump_api([__LINE__,'is_all'=>$is_all,$addUrl,'postArr'=>$postArr,$this->token,'groupNameInfo'=>$groupNameInfo,$device_id],'a5New/addSingleAuthGroup',1);
        $orderGroupId = isset($otherParam['orderGroupId'])&&$otherParam['orderGroupId']?trim($otherParam['orderGroupId']):'';// 标记统一执行命令
        $isAuthGroupBind = isset($otherParam['isAuthGroupBind'])&&$otherParam['isAuthGroupBind']?trim($otherParam['isAuthGroupBind']):0;// 判断是否直接执行后续
        $device_sn = isset($otherParam['device_sn'])&&$otherParam['device_sn']?trim($otherParam['device_sn']):'';
        $village_id = isset($otherParam['village_id'])&&$otherParam['village_id']?trim($otherParam['village_id']):'';
        // 成功添加日志
        if ($is_all==1) {
            $logType = 'houseAdmin';
            $log_name = 'adminAddAuthGroup';
            $logBindName = 'adminDeviceBindAuthGroup';
        } else {
            $logType = 'planA5';
            $log_name = 'planAddAuthGroup';
            $logBindName = 'planDeviceBindAuthGroup';
        }
        $arriveType = 5;// 都是添加权限组
        if (51==$bind_type) {
            // 设备组
            $toType = 10;
        } elseif (52==$bind_type) {
            // 工作组
            $toType = 2;
        } elseif (53==$bind_type) {
            // 全员组
            $toType = 8;
        } else {
            fdump_api(['类型有误'.__LINE__,$groupName,$bind_type,$bind_id,$device_type,$device_id,$is_all,$otherParam],'a5New/errAddSingleAuthGroup',1);
            return true;
        }
        $logParam = [
            'log_name' => $log_name,'logType' => $logType,'log_status' => 11,'orderGroupId' => $orderGroupId,
            'log_content' => '添加权限组成功','device_id' => $device_id,'device_sn' => $device_sn,
            'device_type' => $device_type,'village_id' => $village_id, 'addTime' => time(),
            'toType' => $toType,'toTypeId' => $device_id,'arriveType' => $arriveType,'deviceType' => $device_type,
            'deviceId' => $device_id,'deviceSn' => $device_sn,'toStatus' => 2,'arriveTypeId' => $bind_id,
        ];
        $group_id = '';
        if ($groupNameInfo['code']==200) {
            $arr = [
                'bind_type' => $bind_type,
                'bind_id' => $bind_id,
                'bind_name' => $groupName,
                'group_id' => $groupNameInfo['data']['groupId'],
                'third_info' => serialize($groupNameInfo),
                'device_type' => $device_type,
                'add_time' => time(),
                'last_time' => time(),
                'device_id' =>$device_id,
            ];
            // 设备绑定权限组
            $aboutId = D('Face_bind_about_info')->data($arr)->add();
            if (!$aboutId) {
                fdump_api(['记录权限组信息失败'.__LINE__,$groupName,$bind_type,$bind_id,$device_type,$device_id,$is_all,$otherParam,$arr,D('Face_bind_about_info')->getDbError()],'a5New/errAddSingleAuthGroup',1);
            }
            $logParam['groupId'] = $arr['group_id'];
            $logParam['arriveTypeId'] = $arr['group_id'];
            $groupNameInfo['groupId'] = $arr['group_id'];
            // 绑定权限组
            if (1==$isAuthGroupBind && $arr['group_id']) {
                usleep($this->loopUSleepTime);// 避免超频调用
                $this->keepPlanThread();
                return $this->authGroupBindDevice($arr['group_id'], $device_id, '',$bind_id,$bind_type,0,$otherParam);
            } elseif ($arr['group_id']) {
                $group_id = $arr['group_id'];
                if ($is_all) {
                    $is_all += 1;//执行了下一个计划任务 一键同步延后
                }
            } else {
                // 没有权限id
                fdump_api(['缺少权限组id'.__LINE__,$addUrl,$postArr,$this->token,$groupNameInfo,$device_id,$arr],'a5New/errAddSingleAuthGroup',1);
            }
        } elseif (40021002==$groupNameInfo['code']) {
            // 如果返回权限组已经存在  查询下本地记录
            $aboutInfo = D('Face_bind_about_info')->where(['bind_type'=>$bind_type,'bind_id'=>$bind_id,'device_type'=>$device_type])->order('`id` DESC')->find();
            if ($aboutInfo && $aboutInfo['group_id']) {
                $group_id = $aboutInfo['group_id'];
                $logParam['log_content'] = '权限组已经添加';
                $logParam['groupId'] = $aboutInfo['group_id'];
                $logParam['arriveTypeId'] = $aboutInfo['group_id'];
                $groupNameInfo['groupId'] = $aboutInfo['group_id'];
                if ($is_all) {
                    $is_all += 1;//执行了下一个计划任务 一键同步延后
                }
            }else{
                $groupAuthInfo = $this->getAuthGroupList(1,100,$groupName,$bind_id,$device_id,$bind_type,true);
                
                fdump_api([__LINE__,'is_all'=>$is_all,'groupAuthInfo'=>$groupAuthInfo,$device_id],'a5New/addSingleAuthGroup',1);
                if($groupAuthInfo && isset($groupAuthInfo['groupId'])){
                    $group_id = $groupAuthInfo['group_id'];
                    $logParam['log_content'] = '以获取已经添的权限组';
                    $logParam['groupId'] = $group_id;
                    $logParam['arriveTypeId'] = $group_id;
                    $groupNameInfo['groupId'] = $group_id;
                }
            }
            // 如果本地没有记录成功 走修复计划
        } else {
            $logParam['log_content'] = '权限组添加失败';
            $logParam['log_status'] = 12;
            $logParam['toStatus'] = 3;
            if (isset($info['code'])) {
                $logParam['errCode'] = $info['code'];
            }
            if (isset($info['message'])) {
                $logParam['errReason'] = $info['message'];
            } else {
                $logParam['errReason'] = '未知错误';
            }
        }
        try {
            D('House_face_device_log')->addLog($logParam);
        } catch( \Exception $e) {
            // 记录日志报错
            fdump_api(['日志记录[权限组添加失败]出错'.__LINE__.'方法'.__FUNCTION__,$logParam,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
        }
        if ($is_all==1) {
            // 如果是一键同步触发  直接走这个逻辑
            D('Face_door_service')->face_device_check($device_id);
        }
        if ($group_id) {
            $param = array();
            $param['type'] = 'authGroupBindDevice';
            $param['group_id'] = $group_id;
            $param['device_id'] = $device_id;
            $param['bind_id'] = $bind_id;
            $param['bind_type'] = $bind_type;
            $param['is_all'] = $is_all;
            if ($orderGroupId) {
                $param['orderGroupId'] = $orderGroupId;
            }
            $param['unique_id'] = 'authGroupBindDevice'.$device_id.'_'.$bind_type.'_'.$bind_id;
            // 添加权限组优先
            $a5DeviceBindAuthGroup = D('House_face_device_log')->a5DeviceBindAuthGroup;
            D('Face_door_service')->add_plan($param, 'sub_device_syn', $a5DeviceBindAuthGroup);
            $deviceLogParam = [
                'log_name' => $logBindName,'logType' => $logType,'log_status' => 13,
                'log_content' => '设备绑定权限组计划任务','device_id' => $device_id, 'device_sn' => $device_sn,
                'device_type' => $device_type,'village_id' => $village_id,'addTime' => time(),'orderGroupId' => $orderGroupId,
            ];
            try {
                D('House_face_device_log')->addLog($deviceLogParam);
            } catch( \Exception $e) {
                // 记录日志报错
                fdump_api(['日志记录出错[设备一键同步]'.__LINE__.'方法'.__FUNCTION__,$deviceLogParam,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
            }
            usleep($this->loopUSleepTime);// 避免超频调用
        }
        return $groupNameInfo;
    }
    // 权限组绑定设备
    public function authGroupBindDevice($group_id, $device_id, $device_sn='',$bind_id=0,$bind_type=0,$is_all=0,$otherParam=[]) {
        $orderGroupId = isset($otherParam['orderGroupId'])&&$otherParam['orderGroupId']?trim($otherParam['orderGroupId']):'';// 标记统一执行命令
        $device_sn = isset($otherParam['device_sn'])&&$otherParam['device_sn']?trim($otherParam['device_sn']):'';
        $village_id = isset($otherParam['village_id'])&&$otherParam['village_id']?trim($otherParam['village_id']):'';
        if (!$device_sn) {
            $device_sn = M('House_face_device')->where(array('device_id' => $device_id))->getField('device_sn');
            if (empty($device_sn)) {
                fdump_api(['添加设备[对应设备不存在]'.__LINE__,$device_id,$device_sn,$bind_id,$is_all],'a5New/errAddDeviceAuthGroupLog',1);
                $info['error'] = 1;
                $info['msg'] = '参数错误，对应设备不存在！';
                return $info;
            }
        }
        $bindUrl = $this->a5_face_class->bindAuthGroupEquipment;
        $arr = json_encode([
            "groupId"=>$group_id,
            "checkCapability"=>true,
            "deviceSerials"=>[$device_sn],
        ]);
        usleep($this->loopUSleepTime);// 避免超频调用
        $this->keepPlanThread();
        $info = $this->a5_face_class->curlPost($bindUrl,$arr,1,$this->token);

        // 成功添加日志
        $device_type = 5;
        if ($is_all==1) {
            $logType = 'houseAdmin';
            $log_name = 'adminDeviceBindAuthGroup';
        } else {
            $logType = 'planA5';
            $log_name = 'planDeviceBindAuthGroup';
        }
        $logParam = [
            'log_name' => $log_name,'logType' => $logType,'log_status' => 15,'orderGroupId' => $orderGroupId,
            'log_content' => '添加权限组成功','device_id' => $device_id,'device_sn' => $device_sn,
            'device_type' => $device_type,'village_id' => $village_id, 'addTime' => time(),
            'toType' => 10,'toTypeId' => $device_id,'arriveType' => 6,'deviceType' => $device_type,
            'deviceId' => $device_id,'deviceSn' => $device_sn,'toStatus' => 2,'arriveTypeId' => $group_id,'groupId' => $group_id,
        ];
        fdump_api(['绑定权限组成功'.__LINE__,$group_id,$device_id, $device_sn,$bind_id,$bind_type,$is_all,$info], 'a5New/authGroupBindDeviceLog', true);
        if($info['code'] == 200)
        {
            $data['device_type']=5;
            $data['bind_type']=$bind_type;
            $data['bind_id']=$bind_id;
            $data['device_sn']=$device_sn;
            $data['group_id']=$group_id;
            $data['info']=serialize($info);
            $data['add_time']=time();
            $bind_device = D('Face_bind_device')->where(['bind_type'=>$bind_type,'bind_id'=>$bind_id,'device_sn'=>$device_sn,'group_id'=>$group_id])->find();
            if(!$bind_device){
                D('Face_bind_device')->data($data)->add();
            }
            $info['group_id'] = $group_id;
        }elseif ($info['code'] == 40022002){
            $bindAuthGroupEquipmentCapability=$this->a5_face_class->bindAuthGroupEquipmentCapability;
            $bindAuthGroupEquipmentCapability=$bindAuthGroupEquipmentCapability.'?deviceSerial='.$device_sn;
            $equipmentCapability = $this->a5_face_class->curlGet($bindAuthGroupEquipmentCapability,$this->token);
            fdump_api(['1权限组绑定设备失败'.__LINE__,$bindUrl,$group_id,$device_id, $device_sn,$bind_id,$bind_type,$is_all,$info,'bindAuthGroupEquipmentCapability'=>$bindAuthGroupEquipmentCapability,'equipmentCapability'=>$equipmentCapability], 'a5New/errAuthGroupBindDeviceLog', true);
            $info['group_id'] = $group_id;
            $logParam['log_content'] = '对应权限组已经绑定过了';
        } else {
            $logParam['log_content'] = '权限组绑定失败';
            $logParam['log_status'] = 14;
            $logParam['toStatus'] = 3;
            if (isset($info['code'])) {
                $logParam['errCode'] = $info['code'];
            }
            if (isset($info['message'])) {
                $logParam['errReason'] = $info['message'];
            } else {
                $logParam['errReason'] = '未知错误1';
            }
        }
        try {
            D('House_face_device_log')->addLog($logParam);
        } catch( \Exception $e) {
            // 记录日志报错
            fdump_api(['日志记录[权限组添加失败]出错'.__LINE__.'方法'.__FUNCTION__,$logParam,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
        }
        if ($is_all) {
            // 如果是一键同步触发  直接走这个逻辑
            D('Face_door_service')->face_device_check($device_id);
        }
        return $info;
    }

    //设置设备
    public function setVisual($device_id,$door_info=[],$otherParam=[]){
        $door_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        if (empty($door_info)) {
            $info['error'] = 1;
            $info['msg'] = '参数错误，对应设备不存在！';
            return $info;
        }
        $info = $this->getEquipmentInfo($door_info['device_sn']);
        if($info && $info['code'] == 510206){
            // 510206 设备不存在
            $this->addEquipment($device_id,$door_info,$otherParam);
        } elseif($door_info['is_visual'] == 0)
        {
            // 已经添加成功设备相关了 设置可视对讲通知地址
			$notifyUrl = C('config.site_url').'/appapi.php?c=Face_door&a=openVisual';
            $info = $this->setNotifyUrl($notifyUrl);
        }
        return true;
    }
    /**
     * Notes: 创建分组
     * @return false|string
     * @author: weili
     * @datetime: 2020/7/20 18:07
     * @param json $postData
     */
    public function createGroup($postData=[])
    {
        $url = $this->a5_face_class->group_url;
        $info = $this->a5_face_class->curlPost($url,$postData,1,$this->token);
        return $info;
    }

    /**
     * Notes: 获取分组
     * @param $groupNo
     * @return mixed
     * @author: weili
     * @datetime: 2020/7/27 19:57
     */
    public function getGroupInfo($groupNo){
        $url = $this->a5_face_class->getGroupFind;
        $url = $url.'?groupNo='.$groupNo;
        $info = $this->a5_face_class->curlGet($url,$this->token);
        return $info;
    }
    /**
     * Notes: 注册设备
     * @param array $postData
     * @return mixed
     * @author: weili
     * @datetime: 2020/7/21 15:24
     */
    public function createMachine($device_id, $postData=[], $logParam=[])
    {
        $url = $this->a5_face_class->machine_register_url;
        $info = $this->a5_face_class->curlPost($url,$postData,1,$this->token);
        if (isset($logParam['sysType'])&&$logParam['sysType']) {
            $sysType = $logParam['sysType'];
            unset($logParam['sysType']);
        } else {
            $sysType = 'faceDevice';
        }
        // 记录日志
        if (!empty($logParam) && is_array($logParam)) {
            $logParam['log_name'] = 'adminSynDoor';
            $logParam['logType'] = 'houseAdmin';
            $logParam['log_content'] = '同步设备成功';
            $logParam['toType'] = 10;
            $logParam['toTypeId'] = $device_id;
            $logParam['arriveType'] = 3;
        }
        if($info['code'] == 200) {
            if ('camera' == $sysType) {
                $insertData = [
                    'cloud_device_id' => $info['data']['deviceId'],
                    'device_model' => $info['data']['deviceModel'],
                    'cloud_group_id' => $info['data']['groupId'],
                    'camera_status' => $info['data']['deviceStatus']==1?0:1,
                    'cloud_device_name' => $info['data']['deviceName'],
                    'cloud_is_encrypt' => $info['data']['isEncrypt'],
                    'login_time' => time(),
                    'cloud_txt' => json_encode($info,JSON_UNESCAPED_UNICODE)
                ];
                $add = M('House_camera_device')->where(array('camera_id' => $device_id))->data($insertData)->save();
                if (!$add) {
                    fdump_api(['注册设备[本地记录失败]'.__LINE__,$device_id,$info,$insertData,M('House_camera_device')->_sql()],$sysType.'/errCreateMachineLog',true);
                }
            } else {
                $insertData = [
                    'cloud_device_id' => $info['data']['deviceId'],
                    'cloud_device_model' => $info['data']['deviceModel'],
                    'cloud_group_id' => $info['data']['groupId'],
                    'device_status' => $info['data']['deviceStatus'],
                    'cloud_device_name' => $info['data']['deviceName'],
                    'cloud_is_encrypt' => $info['data']['isEncrypt'],
                    'login_time' => time(),
                    'a3_info' => serialize($info)
                ];
                $add = M('House_face_device')->where(array('device_id' => $device_id))->data($insertData)->save();
                if (!$add) {
                    fdump_api(['注册设备[本地记录失败]'.__LINE__,$device_id,$info,$insertData,M('House_face_device')->_sql()],'a5New/errCreateMachineLog',true);
                }
            }
            if (!empty($logParam) && is_array($logParam)) {
                $logParam['log_status'] = 3;
                $logParam['toStatus'] = 2;
                $logParam['groupId'] = $insertData['cloud_group_id'];
                $logParam['arriveTypeId'] = $insertData['cloud_device_id'];
            }
        }else {
            if (!empty($logParam) && is_array($logParam)) {
                $logParam['log_status'] = 4;
                $logParam['log_content'] = '同步设备失败';
                $logParam['toStatus'] = 3;
                $logParam['errCode'] = isset($info['code'])&&$info['code']?$info['code']:'0';
                $logParam['errReason'] = isset($info['message'])&&$info['message']?$info['message']:'可能是接口无响应';
            }
            fdump_api(['注册设备[返回错误]'.__LINE__,$url,$device_id,$postData,$this->token,$info],'a5New/errCreateMachineLog',true);
        }
        if (!empty($logParam) && is_array($logParam)) {
            try {
                D('House_face_device_log')->addLog($logParam);
            } catch( \Exception $e) {
                // 记录日志报错
                fdump_api(['日志记录出错'.__LINE__.'方法'.__FUNCTION__,$logParam,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
            }
        }
        return $info;
    }

    /**
     * Notes:注销设备
     * @param array $postData
     * @return mixed
     * @author: weili
     * @datetime: 2020/7/21 16:01
     */
    public function outMachine($deviceSerial='',$otherParam=[],$logParam=[])
    {
        $url = $this->a5_face_class->machine_out_url;
        $url = $url.'?deviceSerial='.$deviceSerial;
        $info = $this->a5_face_class->curlPost($url,[],1,$this->token);
        fdump_api(['注销设备[日志]'.__LINE__,$deviceSerial,$url,$this->token,$info],'a5New/outMachineLog',true);
        // 记录日志
        $orderGroupId = $otherParam['orderGroupId']?$otherParam['orderGroupId']:'';
        $device_id = $otherParam['device_id']?$otherParam['device_id']:'';
        $village_id = $otherParam['village_id']?$otherParam['village_id']:'';
        $cloud_group_id = $otherParam['cloud_group_id']?$otherParam['cloud_group_id']:'';
        $cloud_device_id = $otherParam['cloud_device_id']?$otherParam['cloud_device_id']:'';
        if (isset($logParam['sysType'])&&$logParam['sysType']) {
            $sysType = $logParam['sysType'];
            unset($logParam['sysType']);
        } else {
            $sysType = 'faceDevice';
        }
        if (!empty($logParam) && is_array($logParam)) {
            $logParam['log_name'] = 'adminDelDoor';
            $logParam['logType'] = 'houseAdmin';
            $logParam['log_content'] = '注销设备成功';
            $logParam['toType'] = 10;
            $logParam['toTypeId'] = $device_id;
            $logParam['arriveType'] = 3;
        }
        if($info['code'] == 200)
        {
            if (!empty($logParam) && is_array($logParam)) {
                $logParam['log_status'] = 3;
                $logParam['toStatus'] = 2;
                $logParam['groupId'] = $cloud_group_id;
                $logParam['arriveTypeId'] = $cloud_device_id;
            }
            if ('camera' == $sysType) {
                $whereDevice = [
                    'device_type' => 'house_camera_device',
                    'deviceSerial' => $deviceSerial,
                ];
                $insertData = [
                    'isDel' => time()
                ];
                $add = M('House_device_channel')->where($whereDevice)->data($insertData)->save();
                if (!$add) {
                    fdump_api(['注销设备[摄像机本地记录失败]'.__LINE__,$whereDevice,$info,$insertData,M('House_device_channel')->_sql()],$sysType.'/errCreateMachineLog',true);
                }
            } else {
                $face_device = D('House_face_device')->where(array('device_sn' => $deviceSerial,'is_del'=>0))->find();
                M('House_face_device')->where(array('device_sn' => $deviceSerial,'is_del'=>0))->data(['cloud_device_id'=>''])->save();
                //删除本地权限组
                $bind_type_arr = [0,51,52,53];
                $whereDe = array('bind_type'=>['in', $bind_type_arr],'device_sn' => $deviceSerial,'device_type'=>['in',[5,6]]);
                D('Face_bind_device')->where($whereDe)->delete();
                //删除设备绑定成员
                D('Face_user_bind_device')->where(['device_id'=>$face_device['device_id']])->delete();
            }
        }
        try {
            D('House_face_device_log')->addLog($logParam);
        } catch( \Exception $e) {
            // 记录日志报错
            fdump_api(['日志记录[注销设备成功]出错'.__LINE__.'方法'.__FUNCTION__,$logParam,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
        }
        return $info;
    }
    //同步人脸 处理
    public function synDeviceDispose($device_id,$bind_arr_id,$device_sn = '',$orderGroupId='')
    {
        fdump_api(['同步人脸[日志]'.__LINE__,$device_id,$bind_arr_id,$device_sn],'a5New/synDeviceDisposeLog',true);
        foreach ($bind_arr_id as $val){
            usleep($this->loopUSleepTime);// 避免超频调用
            $this->keepPlanThread();
            $this->synDevice($val,$device_id, [], $orderGroupId);
        }
        return true;
    }
    //同步人脸 a5
    public function synDevice($pigcms_id,$device_id='',$face_device_data=[],$orderGroupId='',$uid=0)
    {
        // 人脸门禁类型A5和A6 统一考虑

        if (empty($face_device_data)) {
            $face_device_data = M('House_face_device')->where(array('device_id' => $device_id))->find();
        }
        if(isset($face_device_data['thirdProtocol']) && $face_device_data['thirdProtocol']>0 && $face_device_data['thirdProtocol'] != 11){
            return true;
        }
        $planParam = array();
        $planParam['paramType'] = 'addSingleMember';
        $planParam['device_id'] = $device_id;
        $planParam['village_id'] = $face_device_data['village_id'];
        $planParam['pigcms_id'] = $pigcms_id;
        if ($orderGroupId) {
            $planParam['orderGroupId'] = $orderGroupId;
        }
        $unique_id = 'addSingleMember_'.$planParam['village_id'].'_'.$device_id.'_'.$pigcms_id;
        $planParam['unique_id'] = $unique_id;
        $a5SingleSynPlanTime = D('House_face_device_log')->a5SingleSynPlanTime;
        D('Face_door_service')->add_plan($planParam, 'sub_device_syn', $a5SingleSynPlanTime);


        // 添加了计划任务
        $logParam = [
            'log_name' => 'planSynSingle','logType' => 'plantA5','log_status' => 1,'orderGroupId' => $orderGroupId,
            'log_content' => '单人同步计划任务','device_id' => $device_id,'device_sn' => $face_device_data['device_sn']?$face_device_data['device_sn']:'',
            'device_type' => 5,'village_id' => $planParam['village_id']?$planParam['village_id']:0,'uid' => $uid,
            'addTime' => time(),'bind_id'=> $pigcms_id,
        ];
        try {
            D('House_face_device_log')->addLog($logParam);
        } catch( \Exception $e) {
            // 记录日志报错
            fdump_api(['日志记录[单人同步计划任务]出错'.__LINE__.'方法'.__FUNCTION__,$logParam,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
        }

//        老版本计划任务
//        $param = array();
//        $param['type'] = 'sync_a5_face_member';
//        $param['device_id'] = $device_id;
//        $param['village_id'] = $face_device_data['village_id'];
//        $param['pigcms_id'] = $pigcms_id;
//        if ($orderGroupId) {
//            $param['orderGroupId'] = $orderGroupId;
//        }
//        $param['unique_id'] = 'a5_face_syn_device_'.$param['village_id'].'_'.$device_id.'_'.$pigcms_id;
//		//如果是物业人员，A5 A6需要传个 floor_id 才能继续使用，单元机情况
//        fdump_api(['同步人脸[日志]'.__LINE__,$param,$pigcms_id,$device_id],'a5New/synDeviceLog',true);
//        D('Face_door_service')->add_plan($param, 'sub_house_village_face_device', -62);
        return $planParam;
    }

    //修改物业延长时间
    public function editLengthen($village_id,$device_type=5){
        $where['b.village_id'] = $village_id;
        $where['b.status'] = 1;
        $where['b.type'] = ['in', [0, 1, 2]];
        $where['b.uid'] = ['neq', '0'];
        $where['d.bind_type'] = 0;
        $where['d.device_type'] = $device_type;
        $where['d.group_id'] = ['neq',''];
        $userArr = D('House_village_user_bind')->alias('b')
            ->join('__FACE_USER_BIND_DEVICE__ d ON b.pigcms_id=d.bind_id')
            ->where($where)
            ->field('b.pigcms_id,b.uid,b.phone,b.name,b.ic_card')
            ->order('b.pigcms_id desc')->select();
        $dataArr = [];
        foreach ($userArr as $key => &$val) {
            $end_time = '';
            $userTimes =  D('House_village_user_bind')->getUserEndTime($val['pigcms_id']);
            if (isset($userTimes['propertyEndTime'])) {
                $end_time = intval($userTimes['propertyEndTime']);
            } else {
                $end_time = 0;
            }
            $dataArr[$key] = [
                'pigcms_id' => $val['pigcms_id'],
                'name' => $val['name'],
                'end_time'=>$end_time,
            ];
            $this->editMember($dataArr[$key]);
        }
        set_time_limit(0);
        //权限组下发设备
        for($i=0;$i<10;$i++){
            sleep(5);
            $group = $this->getAuthGroup();
            if($group['code'] != 40021003)
            {
                fdump_api(['修改物业延长时间[错误]'.__LINE__,$village_id,$device_type,$group],'a5New/errEditLengthenLog',true);
                break;
            }
        }
        return $dataArr;
    }
    //用户缴费后同步开门时间
    public function payOpenDoor($pigcms_id)
    {
        $userArr = D('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->field('pigcms_id,uid,phone,name,ic_card,village_id,floor_id')->find();
        $userArr['end_time'] = '';
        $userTimes =  D('House_village_user_bind')->getUserEndTime($pigcms_id);
        if (isset($userTimes['propertyEndTime'])) {
            $userArr['end_time'] = intval($userTimes['propertyEndTime']);
        } else {
            $userArr['end_time'] = 0;
        }
        $this->editMember($userArr);
        //权限组下发设备
        for($i=0;$i<10;$i++){
            sleep(5);
            $group = $this->getAuthGroup($userArr['floor_id']);
            if($group['code'] != 40021003)
            {
                fdump_api(['用户缴费后同步开门时间[错误]'.__LINE__,$pigcms_id,$userArr['floor_id'],$group],'a5New/errPayOpenDoorLog',true);
                break;
            }
        }
    }
    //解除人员
    public function removeMember($pigcms_id)
    {
        $group_id = D('Face_user_bind_device')->where(['bind_id'=>$pigcms_id])->getField('group_id');
        if(!$group_id){
            return true;
        }
        $employeeNos=[$pigcms_id];
        $info = $this->remMember($employeeNos,$group_id);
        if($info){
            set_time_limit(0);
            for ($i=0;$i<=10;$i++)
            {
                $infoGroup = $this->issuedByGroup($group_id);
                if($infoGroup['code'] != 40021003){
                    fdump_api(['解除人员[错误]'.__LINE__,$pigcms_id,$group_id,$infoGroup],'a5New/errRemoveMemberLog',true);
                    break;
                }
                sleep(5);
            }
            return $infoGroup;
        }else{
            return false;
        }
    }
    //解除设备
    public function removeDevice($device_sn,$otherParam=[])
    {
        $bind_type_arr = [0,51,52,53];
        $whereRem = ['device_sn'=>$device_sn,'bind_type'=>['in', $bind_type_arr]];
        $group_id = D('Face_bind_device')->where($whereRem)->field('id,group_id')->select();

        if($group_id){

            $orderGroupId = $otherParam['orderGroupId']?$otherParam['orderGroupId']:'';
            $device_id = $otherParam['device_id']?$otherParam['device_id']:'';
            $village_id = $otherParam['village_id']?$otherParam['village_id']:'';
            $device_type = 5;
            $logType = 'houseAdmin';
            $log_name = 'adminDeviceUnbindAuthGroup';
            $logParam = [
                'log_name' => $log_name,'logType' => $logType,'log_status' => 61,'orderGroupId' => $orderGroupId,
                'log_content' => '设备解绑权限组成功','device_id' => $device_id,'device_sn' => $device_sn,
                'device_type' => $device_type,'village_id' => $village_id, 'addTime' => time(),
                'toType' => 10,'toTypeId' => $device_id,'arriveType' => 6,'deviceType' => $device_type,
                'deviceId' => $device_id,'deviceSn' => $device_sn,'toStatus' => 2,
            ];

            foreach ($group_id as $val){
                $deviceSerials=[$device_sn];
                $groupId = $val['group_id'];
                $logParam['arriveTypeId'] = $groupId;
                $logParam['groupId'] = $groupId;
                $info = $this->remDevice($deviceSerials,$val['group_id'],$val['id'],$otherParam,$logParam);
                if($info)//解绑成功下发设备
                {
                    set_time_limit(0);
                    ini_set('memory_limit', '2048M');
                    for($i=0;$i<10;$i++){
                        usleep($this->loopUSleepTime);// 避免超频调用
                        $this->keepPlanThread();
                        $ret = $this->issuedByGroup($val['group_id']);
                        if($ret['code'] != 40021003)
                        {
                            fdump_api(['解除设备[错误]'.__LINE__,$device_sn,$val['group_id'],$ret],'a5New/errRemoveDeviceLog',true);
                            break;
                        }
                        sleep(5);
                    }
                }else{
                    usleep($this->loopUSleepTime);// 避免超频调用
                    $this->keepPlanThread();
                    $this->outMachine($device_sn,$logParam);
                    D('Face_bind_device')->where(['id'=>$val['id']])->delete();
                }
            }
            $info['error'] = 0;
            $info['msg'] = '同步删除成功！';
            return $info;
        }else{
            usleep($this->loopUSleepTime);// 避免超频调用
            $this->keepPlanThread();
            $this->outMachine($device_sn);
            $info['error'] = 0;
            $info['msg'] = '同步删除成功！';
            return $info;
        }
    }
    //处理消息通道 消息类型数据
    public function getChannelMsg()
    {
        $consumerId = S('consumerId');
        if(!$consumerId){
            //创建消费者
            $info = $this->createConsumers();
            S('consumerId', $info['consumerId'], 300);
            $consumerId = $info['consumerId'];
        }
        if(!$consumerId){
            return true;
        }

        $list = $this->getMassage($consumerId);
        rsort($list);
        $openIdcardEvent = []; //人证比对事件
        $openFaceCapture = []; //人脸抓拍消息
        $openDeviceOnoffline = []; //设备上下线消息
        $openEventAccess = [];  //门禁事件消息
        $openBatchDelivered = []; //门禁批量下发消息
        $openAccessAuthSyncResult = []; //门禁权限下发结果消息
        $openObjectsThrownDetection = []; // 高空抛物事件消息
        $openAccessQrcodeEvent = []; // 门禁二维码事件消息
        $otherMsg = []; //其他消息
        foreach ($list as $key=>$val)
        {
            //人证比对事件
            if($val['msgType'] == 'open_idcard_event'){
                $openIdcardEvent[]=json_decode($val['content'],true);
            } elseif($val['msgType'] == 'open_face_capture'){
                //人脸抓拍消息
                $openFaceCapture[] = json_decode($val['content'],true);
            } elseif($val['msgType'] == 'open_device_onoffline'){
                //设备上下线消息
                $openDeviceOnoffline[] = json_decode($val['content'],true);
            } elseif($val['msgType'] == 'open_event_access'){
                //门禁事件消息
                $openEventAccess[] = json_decode($val['content'],true);
            } elseif($val['msgType'] == 'open_event_access_batch_delivered'){
                //门禁批量下发消息
                $openBatchDelivered[] = json_decode($val['content'],true);
            } elseif($val['msgType'] == 'open_access_auth_sync_result'){
                //门禁权限下发结果消息
                $openAccessAuthSyncResult[] = json_decode($val['content'],true);
            }  elseif($val['msgType'] == 'open_objects_thrown_detection'){
                //高空抛物信息
                $openObjectsThrownDetection[] = json_decode($val['content'],true);
            }  elseif($val['msgType'] == 'open_access_qrcode_event '){
                //门禁二维码事件消息
                $openAccessQrcodeEvent[] = json_decode($val['content'],true);
            } else {
                //其他消息
                $otherMsg[] = $val;
            }
        }
        //排序处理 按完成时间排序
        $openAccessAuthSyncResult = $this->getRsort($openAccessAuthSyncResult,'finishTime'); //门禁权限下发结果消息
        $openEventAccess = $this->getRsort($openEventAccess,'dateTime');  //门禁事件消息
        $openIdcardEvent = $this->getRsort($openIdcardEvent,'eventTime'); //人证比对事件
        $openFaceCapture = $this->getRsort($openFaceCapture,'faceTime');  //人脸抓拍消息
        $d_house_face_device = M('House_face_device');
        $d_house_user_log = M('House_user_log');
        $d_house_village_floor = M('House_village_floor');
        $d_house_village = M('House_village');
        $d_house_village_user_bind = D('House_village_user_bind');
        $d_house_village_single = D('House_village_single');
        $d_house_camera_tip = M('House_camera_tip');
        if (!empty($openFaceCapture)) {
            fdump_api(['人脸抓拍消息'.__LINE__,$openFaceCapture],'a5New/getChannelMsgOpenFaceCaptureLog',true);
        }
        if (!empty($openEventAccess)) {
            fdump_api(['门禁事件消息'.__LINE__,$openEventAccess],'a5New/getChannelMsgOpenEventAccessLog',true);
        }
        if (!empty($openIdcardEvent)) {
            fdump_api(['人证对比事件消息'.__LINE__,$openIdcardEvent],'a5New/getChannelMsgOpenIdcardEventLog',true);
        }
        if (!empty($openAccessAuthSyncResult)) {
            fdump_api(['门禁权限下发结果消息'.__LINE__,$openAccessAuthSyncResult],'a5New/getChannelMsgOpenAccessAuthSyncResultLog',true);
        }
        if (!empty($openBatchDelivered)) {
            fdump_api(['门禁批量下发消息'.__LINE__,$openBatchDelivered],'a5New/getChannelMsgOpenBatchDeliveredLog',true);
        }
        if (!empty($openDeviceOnoffline)) {
            fdump_api(['设备上下线消息'.__LINE__,$openDeviceOnoffline],'a5New/getChannelMsgOpenDeviceOnofflineLog',true);
        }
        if (!empty($openAccessQrcodeEvent)) {
            fdump_api(['门禁二维码事件消息'.__LINE__,$openAccessQrcodeEvent],'a5New/getChannelMsgOpenAccessQrcodeEventLog',true);
        }
        if (!empty($openObjectsThrownDetection)) {
            fdump_api(['高空抛物信息'.__LINE__,$openObjectsThrownDetection],'a5New/getChannelMsgOpenObjectsThrownDetectionLog',true);
            $db_house_camera_device = M('House_camera_device');
            $villageNameArr = [];
            $singleNameArr = [];
            $floorNameArr = [];
            $nowTime = time();
            $tempKey='TM0001801';
            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
            foreach ($openObjectsThrownDetection as $thrown) {
                $whereCameraDevice = [
                    'device_sn' => $thrown['deviceSerial'],
                    'camera_status' => ['neq', 4],
                ];
                $deviceInfo = $db_house_camera_device->where($whereCameraDevice)->find();
                if (isset($villageNameArr[$deviceInfo['village_id']]) && $villageNameArr[$deviceInfo['village_id']]) {
                    $village_info = $villageNameArr[$deviceInfo['village_id']];
                    $village_name = $village_info['village_name'];
                    $openid = isset($village_info['openid'])&&$village_info['openid']?$village_info['openid']:'';
                } else {
                    $village_info = $d_house_village->where(array('village_id' => $deviceInfo['village_id']))->field('village_id,village_name,openid')->find();
                    $village_name = $village_info['village_name'];
                    $villageNameArr[$deviceInfo['village_id']] = $village_info;
                    $openid = isset($village_info['openid'])&&$village_info['openid']?$village_info['openid']:'';
                }
                if ($deviceInfo['floor_id'] && isset($floorNameArr[$deviceInfo['floor_id']]) && $floorNameArr[$deviceInfo['floor_id']]) {
                    $floor_name = $floorNameArr[$deviceInfo['floor_id']]['floor_name'];
                    $single_id = $floorNameArr[$deviceInfo['floor_id']]['single_id'];
                } elseif($deviceInfo['floor_id']) {
                    $whereFloor = [
                        'string' => "floor_id in ({$deviceInfo['floor_id']})",
                        'status' => ['neq', 4],
                    ];
                    $floor_info = $d_house_village_floor->where($whereFloor)->field('floor_name,single_id')->find();
                    $floor_name = $floor_info['floor_name'];
                    $floorNameArr[$deviceInfo['floor_id']] = $floor_info['floor_name']?$floor_info:[];
                    $single_id =  $floor_info['single_id']?$floor_info['single_id']:0;
                } else {
                    $single_id = 0;
                    $floor_name = '';
                }
                if ($single_id && isset($singleNameArr[$single_id]) && $singleNameArr[$single_id]) {
                    $single_name = $singleNameArr[$single_id];
                } elseif ($single_id) {
                    $single_name = $d_house_village_single->where(['id' => $single_id])->getField('single_name');
                    $singleNameArr[$single_id] = $single_name;
                } else {
                    $single_name = '';
                }
                $log_detail = $thrown;
                unset($thrown['dateTime'],$thrown['isDataRetransmission'],$thrown['channelID']);
                if (isset($thrown['ObjectsThrownDetection'])&&isset($thrown['ObjectsThrownDetection']['Image']['resourcesContent'])) {
                    $log_detail['Image'] = $thrown['ObjectsThrownDetection']['Image']['resourcesContent'];
                    $deviceImage = D('Face_door_service')->face_to_img($log_detail['Image'],$thrown['channelID']);
                    if (!$deviceImage) {
                        $deviceImage = $log_detail['Image'];
                    }
                } else {
                    $deviceImage = '';
                }
                if (isset($thrown['ObjectsThrownDetection'])&&isset($thrown['ObjectsThrownDetection']['Video']['resourcesContent'])) {
                    $log_detail['Video'] = $thrown['ObjectsThrownDetection']['Video']['resourcesContent'];
                    $deviceVideo = $log_detail['Video'];
                } else {
                    $deviceVideo = '';
                }
                $log_detail['startTime'] = isset($thrown['ObjectsThrownDetection']['startTime'])?$thrown['ObjectsThrownDetection']['startTime']:'';
                $log_detail['endTime'] = isset($thrown['ObjectsThrownDetection']['endTime'])?$thrown['ObjectsThrownDetection']['endTime']:'';
                $log_detail['RuleInfo'] = isset($thrown['ObjectsThrownDetection']['RuleInfo'])?$thrown['ObjectsThrownDetection']['RuleInfo']:[];
                $channelName = isset($thrown['channelName'])?$thrown['channelName']:'';

                $houseUserLog = [
                    'log_name' => $village_name .' '.$single_name.''.$floor_name,
                    'log_from' => 51,
                    'log_business_id' => $deviceInfo['village_id'],
                    'log_more_id' => $deviceInfo['floor_id'],
                    'device_id' => $deviceInfo['camera_id'],
                    'log_time' => isset($thrown['dateTime'])&&$thrown['dateTime']?strtotime($thrown['dateTime']):$nowTime,
                    'log_status' => !$thrown['isDataRetransmission']?1:2, // 重传数据标记,2-重传数据，1-实时数据
                    'log_detail' => unserialize($log_detail),
                    'eventId' => isset($thrown['channelID'])&&$thrown['channelID']?$thrown['channelID']:'',
                    'deviceChannelId' => isset($thrown['channelID'])&&$thrown['channelID']?$thrown['channelID']:'',
                    'deviceImage' => $deviceImage,
                    'deviceVideo' => $deviceVideo,
                ];
                if ($deviceImage) {
                    $houseUserLog['cut_image'] = $deviceImage;
                }
                $houseUserLog['add_time']=time();
                $ids = $d_house_user_log->data($houseUserLog)->add();
                if (!$ids) {
                    fdump_api(['高空抛物信息记录失败'.__LINE__,$houseUserLog],'a5New/errGetChannelMsgOpenObjectsThrownDetectionLog',true);
                }
                if ($openid) {
                    if ($channelName) {
                        $title = "监控 {$deviceInfo['camera_name']}-{$channelName} 捕捉到有高空抛物";
                    } else {
                        $title = "监控 {$deviceInfo['camera_name']} 捕捉到有高空抛物";
                    }
                    $remark = '位置：'.$village_name .' '.$single_name.''.$floor_name .' '.$deviceInfo['camera_name'];
                    $arr = array(
                        'href' => '',
                        'wecha_id' => $openid,
                        'first' => '高空报警-监控',
                        'keyword1' => $title,
                        'keyword2' => $houseUserLog['log_status']==1?'实时上报':'重传上报',
                        'keyword3' => date('H时i分',$houseUserLog['log_time']),
                        'remark' => $remark
                    );
                    $send = $model->sendTempMsg($tempKey,$arr);
                    fdump_api(['高空抛物信息推送记录'.__LINE__,$send, $arr],'a5New/sendOpenObjectsThrownDetection',true);
                }
            }
        }
        if (!empty($otherMsg)) {
            fdump_api(['其他消息'.__LINE__,$otherMsg],'a5New/getChannelMsgOpenOtherMsgLog',true);
        }
        $database_house_village_visitor = D('House_village_visitor');
        $db_face_user_bind_device = D('Face_user_bind_device');

        foreach ($openEventAccess as $key=>$val){
            $whereDevice = [
                'device_sn' => $val['deviceSerial'],
                'is_del' => 0
            ];
            $deviceInfo = $d_house_face_device->where($whereDevice)->find();
            $villageInfo = $d_house_village->where(array('village_id' => $deviceInfo['village_id']))->find();
            $currTemperature = isset($val['currTemperature']) && $val['currTemperature'] ? $val['currTemperature'] : '';
            if (!$currTemperature && isset($val['bodyTemperature']) && $val['bodyTemperature']) {
                $currTemperature = $val['bodyTemperature'];
            }
            $log_from = 1;
            switch ($val['eventCode']) {
                case 10101:
                    $log_from = 3;
                    break;
                case 10102:
                    $log_from = 70;
                    break;
                case 10103:
                    $log_from = 71;
                    break;
                case 10104:
                    $log_from = 45;
                    break;
                case 10105:
                    $log_from = 72;
                    break;
                case 10106:
                    $log_from = 73;
                    break;
                case 10107:
                    $log_from = 74;
                    break;
                case 10108:
                    $log_from = 75;
                    break;
                case 10109:
                    $log_from = 76;
                    break;
                case 10110:
                    $log_from = 77;
                    break;
                case 10111:
                    $log_from = 78;
                    break;
                case 10112:
                    $log_from = 79;
                    break;
                case 10113:
                    $log_from = 80;
                    break;
                case 10114:
                case 10125:
                    $log_from = 1;
                    break;
                case 10116:
                case 10123:
                case 10124:
                    $log_from = 2;
                    break;
                case 10119:
                    $log_from = 21;
                    break;
                case 10120:
                    $log_from = 41;
                    break;
                case 10121:
                    $log_from = 90;
                    break;
                case 10122:
                    $log_from = 31;
                    break;
            }
            if(array_key_exists('employeeNo',$val)){
//            if(array_key_exists('employeeNo',$val) && array_key_exists('name',$val)){
                //视为开锁成功
                $logInfo = $d_house_user_log->where(['eventId'=>$val['eventId']])->find();
                if(!$logInfo) {
                    $floorInfo = $d_house_village_floor->where(array('floor_id' => $deviceInfo['floor_id']))->find();
                    $singleInfo = $d_house_village_single->where(['id' => $floorInfo['single_id']])->field('id as single_id,single_name')->find();

                    $village_name = $villageInfo['village_name'];
                    if ($deviceInfo['public_area_id'] > 0) {
                        $public_area_name = M('House_village_public_area')->where(['public_area_id' => $deviceInfo['public_area_id']])->getField('public_area_name');
                        $floor_name = '';
                        $floor_layer = $public_area_name;
                    } elseif ($deviceInfo['floor_id'] == -1) {
                        $floor_layer = C('config.house_name');
                        $floor_name = '大门';
                    } else {
                        $floor_layer = $singleInfo['single_name'] ? $singleInfo['single_name'] : $floorInfo['floor_layer'];
                        $floor_name = $floorInfo['floor_name'];
                    }
                    $log = array();
                    $condition_user['pigcms_id'] = $val['employeeNo'];
                    $house_village_bind = $d_house_village_user_bind->field('pigcms_id, uid, type')->where($condition_user)->find();
                    $uid = $house_village_bind['uid'];
                    $log['uid'] = intval($uid);
                    $log['log_bind_id'] = $val['employeeNo'];
                    $log['log_name'] = $village_name. ' ' . $floor_layer. ' ' . $floor_name;
                    $log['log_from'] = $log_from; // 人脸识别门禁
                    $log['log_business_id'] = $deviceInfo['village_id']; // 社区id
                    $log['log_more_id'] = $deviceInfo['floor_id']; // 单元id
                    $log['device_id'] = $deviceInfo['device_id']; // 设备id
                    if (isset($val['dateTime'])) {
                        $log_time = strtotime($val['dateTime']);
                    }
                    if (!$log_time) {
                        $log_time = time();
                    }
                    $log['log_time'] = $log_time; // 记录时间
                    // 0 比对通过，1 比对失败， 2 无模版， 3 无效卡 4 无现场照，5 非活体， 6 证件过期 7 超过时间限制， 8 超过此数限制 100 未知错误
                    $code = [10114, 10118, 10119, 10120, 10101, 10104, 10116, 10123, 10122];
                    if (in_array($val['eventCode'], $code)) {
                        $log['log_status'] = 0;
                        $status = 0;
                    } else {
                        $log['log_status'] = 1;
                        $status = 1;
                    }
                    if (isset($val['cardNo']) && $val['cardNo']) {
                        $log['card_number'] = $val['cardNo'];
                    }
                    if (isset($val['name']) && $val['name']) {
                        $log['person_name'] = $val['name'];
                    }
                    $log['log_detail'] = serialize($val);
                    $log['eventId'] = $val['eventId'];
                    $log['temperature'] = $currTemperature;
                    if (isset($val['pictureURL']) && trim($val['pictureURL'])) {
                        $log['cut_image'] = trim($val['pictureURL']);
                    }
                    $log['add_time']=time();
                    $d_house_user_log->data($log)->add();
                    $d_house_face_device->where(array('device_sn' => $val['deviceSerial']))->setField('last_open_time', time());
                    if ($house_village_bind['type'] == 6) {
                        $visitor_id = 0;
                        if (isset($val['cardNo']) && $val['cardNo']) {
                            $whereBindType = [
                                'bind_type'   => 40,
                                'device_type' => 5,
                                'code'        => $val['cardNo'],// 卡号
                            ];
                            $aboutFaceUser = $db_face_user_bind_device->where($whereBindType)->find();
                            $visitor_id = isset($aboutFaceUser['bind_id']) && $aboutFaceUser['bind_id'] ? $aboutFaceUser['bind_id'] : 0;
                        }
                        if (!$visitor_id) {
                            $whereVisitor = [
                                'village_id'      => $deviceInfo['village_id'],
                                'visitor_bind_id' => $house_village_bind['pigcms_id'],
                            ];
                            $visitorInfo = $database_house_village_visitor->where($whereVisitor)->order('id DESC')->find();
                            $visitor_id = isset($visitorInfo['id']) && $visitorInfo['id'] ? $visitorInfo['id'] : 0;
                        }
                        if ($visitor_id) {
                            $house_village_visitor_pass = M('House_village_visitor_pass');
                            $addArr = array('visitor_id' => $visitor_id);
                            $addArr['village_id'] = $deviceInfo['village_id'];
                            $addArr['uid'] = 0;
                            $addArr['user_bind_id'] = 0;
                            $addArr['phone'] = 0;
                            if ($log_from == 1) {
                                $addArr['uname'] = $village_name . ' ' . $deviceInfo['device_name'] . ' 人脸门禁通行';
                            } elseif (isset($val['cardNo']) && $val['cardNo'] && intval($val['cardNo']) > 100000000000) {
                                $addArr['uname'] = $village_name . ' ' . $deviceInfo['device_name'] . ' 二维码门禁通行';
                            } else {
                                $addArr['uname'] = $village_name . ' ' . $deviceInfo['device_name'] . ' 门禁通行';
                            }
                            $addArr['addtime'] = $log_time;
                            $house_village_visitor_pass->data($addArr)->add();
                            $dataSave = [
                                'status'    => 5,
                                'pass_time' => $log_time,
                            ];
                            $whereVisitor = [
                                'id' => $visitor_id
                            ];
                            $database_house_village_visitor->where($whereVisitor)->save($dataSave);
                        }
                    }
                }
            }else{
                //开锁失败
                $status = 1;
                $uid = 0;
                $bind_id = 0;
                $eventCodeArr = [
                    '10125',// 人脸认证失败
                ];
                if (isset($val['eventCode'])&&in_array($val['eventCode'],$eventCodeArr)) {
                    $deviceInfo = $d_house_face_device->where(array('device_sn' => $val['deviceSerial']))->find();
                    $log = array();
                    $log['uid'] = $uid;
                    $log['log_bind_id'] = $bind_id;
                    $log['log_name'] = $deviceInfo['device_name'];
                    $log['log_from'] = $log_from; // 人脸识别门禁
                    $log['log_business_id'] = $deviceInfo['village_id']; // 社区id
                    $log['log_more_id'] = $deviceInfo['floor_id']; // 单元id
                    $log['device_id'] = $deviceInfo['device_id']; // 设备id
                    if (isset($val['dateTime'])) {
                        $log_time = strtotime($val['dateTime']);
                    }
                    if (!$log_time) {
                        $log_time = time();
                    }
                    $log['log_time'] = $log_time; // 记录时间
                    $log['log_status'] = 1;
                    $log['log_detail'] = serialize($val);
                    $log['eventId'] = $val['eventId'];
                    $log['temperature'] = $currTemperature;
                    if (isset($val['pictureURL']) && trim($val['pictureURL'])) {
                        $log['cut_image'] = trim($val['pictureURL']);
                    }
                    $log['add_time']=time();
                    $d_house_user_log->data($log)->add();
                }
            }
            if ($val['dateTime']) {
                $camera_time = strtotime($val['dateTime']);
            } else {
                $camera_time = time();
            }
            $city_id = $villageInfo['city_id'];
            $tip = array(
                'bind_id' =>  $bind_id,
                'uid' => $uid,
                'type' => 1,
                'village_id' => $deviceInfo['village_id'],
                'camera_sn' => $deviceInfo['device_sn'],
                'city_id' => $city_id,
                'camera_time' => time(),
                'camera_img' => $val['pictureURL'],
                'tip_time' => $camera_time,
                'status' => $status,
                'tip_txt' => serialize($val)
            );
            $d_house_camera_tip->data($tip)->add();
        }

        foreach ($openAccessAuthSyncResult as $key=>$val){
            $data['status'] = $val['syncStatus'];
            $data['info'] = serialize($val);
            $bind_type_arr = [0,51,52,53];
            $whereOpen = ['bind_type'=>['in', $bind_type_arr],'group_id'=>$val['permissionGroupId']];
            D('face_bind_device')->where($whereOpen)->data($data)->save();
        }
        //人脸抓拍数据处理
        foreach ($openFaceCapture as $k=>$val){
            $deviceInfo = $d_house_face_device->where(array('device_sn' => $val['deviceSerial']))->find();
            $villageInfo = $d_house_village->where(array('village_id' => $deviceInfo['village_id']))->find();
            $floorInfo = $d_house_village_floor->where(array('floor_id' => $deviceInfo['floor_id']))->find();
            $singleInfo = $d_house_village_single->where(['id' => $floorInfo['single_id']])->field('id as single_id,single_name')->find();

            $village_name = $villageInfo['village_name'];
            if ($deviceInfo['public_area_id'] > 0) {
                $public_area_name = M('House_village_public_area')->where(['public_area_id' => $deviceInfo['public_area_id']])->getField('public_area_name');
                $floor_name = '';
                $floor_layer = $public_area_name;
            } elseif ($deviceInfo['floor_id'] == -1) {
                $floor_layer = C('config.house_name');
                $floor_name = '大门';
            } else {
                $floor_layer = $singleInfo['single_name'] ? $singleInfo['single_name'] : $floorInfo['floor_layer'];
                $floor_name = $floorInfo['floor_name'];
            }
            $log = array();
            $log['uid'] = $uid;
            $log['log_bind_id'] = 0;
            $log['log_name'] = $village_name. ' ' . $floor_layer. ' ' . $floor_name;
            $log['log_from'] = 6; // 摄像头门禁
            $log['log_business_id'] = $deviceInfo['village_id']; // 社区id
            $log['log_more_id'] = $deviceInfo['floor_id']; // 单元id
            $log['device_id'] = $deviceInfo['device_id']; // 设备id
            if (isset($val['faceCapture']) && $val['faceCapture'][0] && $val['faceCapture'][0]['faceTime']) {
                $log_time = strtotime($val['faceCapture'][0]['faceTime']);
            }
            if (!$log_time) {
                $log_time = time();
            }
            $log['log_time'] = $log_time; // 记录时间
            $log['log_detail'] = serialize($val);
            if (isset($val['pictureURL']) && trim($val['pictureURL'])) {
                $log['cut_image'] = trim($val['pictureURL']);
            }
            $log['add_time']=time();
            $id = $d_house_user_log->data($log)->add();
            if (!$id) {
                fdump_api(['人脸添加事件失败'.__LINE__,$val,$log],'a5New/errGetChannelMsgLog',1);
            }
        }
        // 如果存在设备上下线信息 处理对应  当前海康可能会返回多条上下线消息 无法准确定位是那一条为最新  这里注释调 改为计划任务定时获取设备在线状态
        if (0 && !$openDeviceOnoffline) {
            $db_house_face_device = D('House_face_device');
            foreach ($openDeviceOnoffline as $device) {
                if (isset($device['deviceSerial'])) {
                    $whereDevice = [
                        'device_sn' => $device['deviceSerial'],
                        'is_del' => 0,
                    ];
                    $faceDevice = $db_house_face_device->where($whereDevice)->find();
                    if ($faceDevice) {
                        $deviceSave = [];
                        if ($device['msgType']=='OFFLINE') {
                            // 离线
                            $deviceSave['device_status'] = 2;
                        } elseif ($device['msgType']=='ONLINE') {
                            // 在线
                            $deviceSave['device_status'] = 1;
                        }
                        if (!$faceDevice['device_ip'] && isset($device['natIp']) && $device['natIp']) {
                            $deviceSave['device_ip'] = $device['natIp'];
                        }
                        if (!$faceDevice['cloud_device_model'] && isset($device['devType']) && $device['devType']) {
                            $deviceSave['cloud_device_model'] = $device['devType'];
                        }
                        if (!$faceDevice['cloud_group_id'] && isset($device['groupId']) && $device['groupId']) {
                            $deviceSave['cloud_group_id'] = $device['groupId'];
                        }
                        if (!$faceDevice['cloud_device_id'] && isset($device['deviceId']) && $device['deviceId']) {
                            $deviceSave['cloud_device_id'] = $device['deviceId'];
                        }
                        if (!$faceDevice['cloud_device_name'] && isset($device['deviceName']) && $device['deviceName']) {
                            $deviceSave['cloud_device_name'] = $device['deviceName'];
                        }
                        if (!empty($deviceSave)) {
                            $db_house_face_device->where($whereDevice)->save($faceDevice);
                        }
                    }
                }
            }
        }

    }
    //远程开门记录
    public function openDoor($device_sn,$pigcms_id,$type='')
    {
        $data = [];
        $log_from = 2;
        if($type){
            $log_status= 1;
        }else{
            $log_status= 0;
        }
        $log_time = time();

        $device_info = D('House_face_device')->where(['device_sn'=>$device_sn])->field('device_id,village_id,floor_id,public_area_id')->find();
        $device_id = $device_info['device_id'];
        $map['village_id'] = $device_info['village_id'];
        if($device_info['floor_id'] && $device_info['floor_id'] <> '-1'){
            $floor_id = explode(',',$device_info['floor_id']);
            $map['floor_id'] = ['in',$floor_id];
        }
        if($pigcms_id) {
            $user_bind = D('House_village_user_bind')->where($map)
                ->field('pigcms_id,uid,village_id,single_id,floor_id')->find();
        }
        if($device_info['floor_id'] == '-1'){
            $log_name = D('House_village_public_area')->where(['public_area_id'=>$device_info['public_area_id']])->getField('public_area_name');
        }else{
            $floor_data = D('House_village_floor')->where(['floor_id'=>$device_info['floor_id']])->field('floor_name,single_id')->find();
            if($floor_data) {
                $single_name = D('house_village_single')->where(['id' => $floor_data['single_id']])->getField('single_name');
                if (preg_match('/[\x7f-\xff]/', $single_name . $floor_data['floor_name'])) {
                    $log_name = $single_name . $floor_data['floor_name'];
                } else {
                    $log_name = $single_name . '号楼-' . $floor_data['floor_name'] . '单元';
                }
            }else{
                $log_name = '--';
            }
        }
        $user_data = [
            'uid'=>isset($user_bind['uid'])?$user_bind['uid']:0,
            'log_bind_id'=>$pigcms_id?$pigcms_id:0,
            'log_name'=>$log_name,
            'log_from'=>$log_from,
            'log_business_id'=>$device_info['village_id']?$device_info['village_id']:0,
            'log_more_id'=>$user_bind['floor_id']?$user_bind['floor_id']:0,
            'device_id'=>$device_id,
            'log_status'=>$log_status,
            'log_detail'=>serialize($data),
            'log_time'=>$log_time,
        ];
        $user_data['add_time']=time();
        $res = D('House_user_log')->add($user_data);
        return true;
    }
    //排序倒叙
    public function getRsort($list,$field){
        $finishTime = [];
        foreach ($list as $val) {
            $finishTime[] = $val[$field];
        }
        array_multisort($finishTime,SORT_DESC,$list);
        return $list;
    }
    //远程开门
    public function userOpenDoor($user_bind_id)
    {
        $device_sn = D('Face_user_bind_device')->alias('ub')
            ->leftJion(C('DB_PREFIX')."face_bind_device as bd ON ub.group_id=bd.group_id")
            ->where(['ub.bind'=>$user_bind_id])->getField('device_sn');
        if($device_sn){
            $res = $this->remoteOpen($device_sn);
            if($res)
            {
                $msg['code'] = 0;
                $msg['msg'] = $res['message'];
                return $msg;
            }else{
                $msg['code'] = 1;
                $msg['msg'] = $res['message'];
                return $msg;
            }
        }else{
            $msg['code'] = 1;
            $msg['msg'] = '您的信息还没有同步，请联系客服人员同步您的信息！';
            return $msg;
        }
    }
    /**
     * Notes: 获取权限组下的所有设备
     * @return mixed
     * @author: weili
     * @datetime: 2020/7/24 14:18
     */
    public function getDeviceSerials($group_id)
    {
        $url = $this->a5_face_class->getDeviceSerials;
        $arr = json_encode([
            "pageNo"=> 1,
            "pageSize"=> 100,
            "groupId"=> $group_id
        ]);
        usleep($this->loopUSleepTime);// 避免超频调用
        $this->keepPlanThread();
        $info = $this->a5_face_class->curlPost($url,$arr,1,$this->token);
        if($info['code'] == 200)
        {
            return $info['data'];
        }else{
            fdump_api(['获取权限组下的所有设备[错误]'.__LINE__,$url,$arr,$this->token,$info],'a5New/getDeviceSerialsErrLog',1);
            return $info;
        }
    }

    /**
     * Notes: 添加人员
     * @param $village_id
     * @param string $user_bind_id
     * @param string $floor_id 单元id 可不传
     * @author: weili
     * @datetime: 2020/7/21 18:23
     * @return array|int|mixed
     */
    public function addMember($village_id,$device_id,$user_bind_id='',$floor_id='',$otherParam=[])
    {
        $orderGroupId = $otherParam['orderGroupId']?$otherParam['orderGroupId']:'';
        $device_sn = $otherParam['device_sn']?$otherParam['device_sn']:'';
        if (!$otherParam['village_id'] && $village_id) {
            $otherParam['village_id'] = $village_id;
        }
        if(!$user_bind_id) {
            $planParam = [
                'village_id' => $village_id,
                'device_id' => $device_id,
            ];
            $unique_id = 'subDeviceSyn';
            $unique_id .= '_village_id'.$village_id;
            $unique_id .= '_device_id'.$device_id;
            if ($floor_id) {
                $planParam['floor_id'] = $floor_id;
            }
            if ($orderGroupId) {
                $planParam['orderGroupId'] = $orderGroupId;
            }
            $planParam['unique_id'] = $unique_id;
            $a5SubDeviceSynPlanTime = D('House_face_device_log')->a5SubDeviceSynPlanTime;
            $plantDevice = D('Face_door_service')->add_plan($planParam, 'sub_device_syn', $a5SubDeviceSynPlanTime);
            if ($plantDevice) {
                $param = [
                    'log_name' => 'planDeviceSynSingle','logType' => 'planA5','log_status' => 21,'orderGroupId' => $orderGroupId,
                    'log_content' => '分发单人计划任务','device_id' => $device_id,'device_sn' => $device_sn,
                    'device_type' => $otherParam['device_type']?$otherParam['device_type']:5,'village_id' =>$village_id,
                    'addTime' => time(),
                ];
                try {
                    D('House_face_device_log')->addLog($param);
                } catch( \Exception $e) {
                    // 记录日志报错
                    fdump_api(['日志记录[添加设备]出错'.__LINE__.'方法'.__FUNCTION__,$param,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
                }
            }
   	        return true;
        }else{
            $userArr = D('House_village_user_bind')->where(['pigcms_id'=>$user_bind_id])->field('pigcms_id,type,parent_id,uid,phone,name,single_id,floor_id,layer_id,vacancy_id,ic_card,village_id')->find();
            $userArr['end_time'] = '';
            $layer_info = D('House_village_layer')->where(['id'=>$userArr['layer_id']])->field('layer_name,layer_number')->find();
            $room_info = D('House_village_user_vacancy')->where(['pigcms_id'=>$userArr['vacancy_id']])->field('room,room_number')->find();
            if ($layer_info['layer_number']) {
                $layer_num = $layer_info['layer_number'];
            } elseif(!preg_match('/[\x7f-\xff]/',$layer_info['layer_name'])){
                $layer_num = $layer_info['layer_name'];
            }else {
                $layer_num = $this->findNum($layer_info['layer_name']);
            }
            if ($room_info['room_number']) {
                $room_num = $room_info['room_number'];
            } elseif(!preg_match('/[\x7f-\xff]/',$room_info['room'])){
                $room_num = $room_info['room'];
            }else {
                $room_num = $this->findNum($room_info['room_number']);
            }
            if(strlen($layer_num)>3){
                $layer_num =  substr($layer_num,-3,3);
            }
            if(strlen($room_num)>2){
                $room_num =  substr($room_num,-2,2);
            }
            $userArr['layer_num'] = $layer_num;
            $userArr['vacancy_num'] = $room_num;
            $userTimes =  D('House_village_user_bind')->getUserEndTime($user_bind_id);
            if (isset($userTimes['propertyEndTime'])&&$userTimes['propertyEndTime']>0) {
                $userArr['end_time'] = intval($userTimes['propertyEndTime']);
            } else {
                $userArr['end_time'] = 0;
            }
            if ($userArr['uid']) {
                $imgUrlInfo = D('House_face_img')->where(['uid'=>$userArr['uid'], 'status' => ['in', [0,3]]])->order('id desc')->field('img_url,status')->find();
            }
            $userArr['device_id'] = $device_id;
            if($userArr && $imgUrlInfo)
            {
                $imgUrl = $imgUrlInfo['img_url'];
                if (3==$imgUrlInfo['status']) {
                    import('@.ORG.face.word_encryption');
                    $word_encryption = new word_encryption();
                    // 针对加密图片解密
                    $imgUrl = $word_encryption->text_decrypt($imgUrl);
                }
                fdump_api(['同步前处理前原图片'.__LINE__,$imgUrl], 'a5New/addMemberSingleUserArr', true);
                try {
                    $imgUrl = D('Image')->transform_image($imgUrl);
                    $imagesize1 = getimagesize($imgUrl);
                    fdump_api(['同步前处理后图片'.__LINE__,$imgUrl,$imagesize1], 'a5New/addMemberSingleUserArr', true);
                    if ($imagesize1[0]&&$imagesize1[0]<=420&&$imagesize1[1]&&$imagesize1[1]<=560) {
                        $img_url = $imgUrl;
                        $imagesize = $imagesize1;
                    } else {
                        $img_url = thumb_img($imgUrl, 400, 540, '', true);
                        $imagesize = getimagesize($img_url);
                        if ($imagesize[0] && $imagesize[0] < 400) {
                            // 如果压缩出来的图片小于宽度480  执行裁剪
                            $img_url = thumb_img($imgUrl, 400, 540, 'fill', true);
                        }
                    }
                    $userArr['img_url'] = $img_url;
                } catch( \Exception $e) {
                    fdump_api(['同步前处理后图片错误'.__LINE__,$imgUrl,$e->getMessage()], 'a5New/addMemberSingleUserArr', true);
                }
                //添加人员 云设备
                fdump_api(['图片压缩后和相关信息'.__LINE__,$imagesize,$img_url, $village_id,$device_id,$user_bind_id,$floor_id,$imgUrl, $imgUrlInfo,$userArr], 'a5New/addMemberSingleUserArr', true);
                usleep($this->loopUSleepTime);// 避免超频调用
                $this->keepPlanThread();
                $res = $this->addMembers($userArr,0,5,$otherParam);
                return $res;
            } elseif ($userArr) {
                fdump_api(['无图片相关'.__LINE__, $village_id,$device_id,$user_bind_id,$floor_id,$userArr], 'a5New/addMemberSingleUserArr', true);
                usleep($this->loopUSleepTime);// 避免超频调用
                $this->keepPlanThread();
                $res = $this->addMembers($userArr,0,5,$otherParam);
                return $res;
            }else{
                return 0;
            }
        }
    }

    /**
     * Notes: 云添加人员
     * @param $postArr
     * @return mixed
     * @author: weili
     * @datetime: 2020/7/22 11:54
     */
    public function addMembers($postArr,$bind_type=0,$device_type=5,$otherParam=[])
    {
        $where['bind_type'] = $bind_type;
        $where['bind_id'] = $postArr['pigcms_id'];
        $where['device_type'] = $device_type;
        //本地用户表
        $userInfo = D('Face_user_bind_device')->where($where)->find();
        $postArr['validBeginTime'] = date('Y-m-d',time());
        // 没有截止时间 默认延期10年
        $postArr['validEndTime'] = date('Y-m-d',time()+3652*86400);
        $log_id = $postArr['log_id'];
        if(!$userInfo) {
            $url = $this->a5_face_class->addMember;
            $arr = [
                'employeeNo' => $postArr['pigcms_id'],
                'personName' => $this->replaceWord_($postArr['pigcms_id'],$postArr['name'],$postArr['phone']),
            ];
            if ($postArr['layer_num']) {
                $arr['floorNo'] = $postArr['layer_num'];
            }
            if ($postArr['vacancy_num']) {
                $arr['roomNo'] = $postArr['vacancy_num'];
            }
            $arr['personName'] = trim($arr['personName']);
            if ($postArr['img_url']) {
                $arr['faceImageBase64'] = $this->imgtobase64($postArr['img_url'],$postArr);
                if (!$arr['faceImageBase64']) {
                    // 如果图片解析失败 直接报错
                    $file_handle = new file_handle();
                    if($file_handle->check_open_oss()) {
                        usleep($this->loopUSleepTime);// 避免超频调用
                        $this->keepPlanThread();
                        $img_filename = $file_handle->get_path($postArr['img_url']);
                        $file_handle->upload('.'.$img_filename);
                        $arr['faceImageBase64'] = $this->imgtobase64($postArr['img_url']);
                    }
                    fdump_api(['a5添加成员[图片转base64失败]'.__LINE__,$arr, $postArr,$img_filename],'a5New/errAddMembersLog',1);
                    if (!$arr['faceImageBase64']) {
                        D('House_village_user_bind')
                            ->where(array('pigcms_id' => $postArr['pigcms_id']))
                            ->save(array('face_img_status' => 2, 'face_img_reason' => '图片解析失败，请重新同步'));
                        if($log_id){
                            M('House_face_sync_log')->where(['log_id'=>$log_id])->save([
                                'status'=>2,
                                'msg'=>L_('图片解析失败，请重新同步'),
                                'last_time'=>time()
                            ]);
                        } else {
                            $device_sn = isset($postArr['device_sn']) && $postArr['device_sn'] ? trim($postArr['device_sn']) : '';
                            $syncLogParams = [
                                'pigcms_id' => $postArr['pigcms_id'], 'face_id' => 0, 'device_id' => $postArr['device_id'], 'device_sn' => $device_sn,
                                'log_time' => time(), 'status' => 2, 'msg' => '图片解析失败，请重新同步'
                            ];
                            $log_id = D('Face_door_service')->handleHouseFaceSyncLog($syncLogParams);
                        }
                        return $postArr;
                    }
                }
            }
            if($postArr['personType']){
                $arr['personType'] = 'visitor';//访客
            }
            if($postArr['phone']){
                $arr['personPhone'] = trim($postArr['phone']);
            }
            $now_time = time();
            if($postArr['end_time']){
                if (intval($now_time)>intval($postArr['end_time'])) {
                    // 如果当前时间大于到用户期时间 开始时间以结束时间往前推一天
                    $now_time = $postArr['end_time'] - 86400;
                }
                $arr['validEndTime']=date('Y-m-d',$postArr['end_time']);
            } else {
                // 没有截止时间 默认延期10年
                $arr['validEndTime'] = date('Y-m-d',time()+3652*86400);
            }
            if (isset($postArr['start_time']) && $postArr['start_time']) {
                $arr['validBeginTime'] = date('Y-m-d',$postArr['start_time']);
            } else {
                $arr['validBeginTime'] = date('Y-m-d',$now_time);
            }
            // A5限制  2145887999  不可超出 2037-12-31 23:59:59
            $judeTimes = strtotime($arr['validEndTime']);
            $judeTimes = intval($judeTimes);
            if (isset($arr['validEndTime'])&&$judeTimes>2145887999) {
                // 如果超出就赋予这个值
                $arr['validEndTime'] = '2037-12-31';
                $judeTimess = strtotime($arr['validBeginTime']);
                $judeTimess = intval($judeTimess);
                if (isset($arr['validBeginTime'])&&$judeTimess>2145887999) {
                    // 开始时间如果超出就赋予 这一天0点对应值
                    $arr['validBeginTime'] = '2037-11-30';
                }
            }
            $validEndTimes = strtotime($arr['validEndTime']);
            if (!$validEndTimes) {
                $validEndTimes = time();
            }
            if ($arr['validEndTime'] && $arr['validBeginTime'] && intval(strtotime($arr['validBeginTime']))>intval($validEndTimes)) {
                // 如果当前时间大于到用户期时间 开始时间以结束时间往前推一天
                $now_time = strtotime($arr['validEndTime']) - 86400;
                $arr['validBeginTime']=date('Y-m-d',$now_time);
            }
            $jsonArr = $arr;
            $arr = json_encode($arr,JSON_UNESCAPED_SLASHES);

            usleep($this->loopUSleepTime);// 避免超频调用
            $this->keepPlanThread();
            $info = $this->a5_face_class->curlPost($url, $arr, 1, $this->token);

            $fArr = $arr;
            if (is_array($fArr) && isset($fArr['faceImageBase64'])) {
                unset($fArr['faceImageBase64']);
            }
            fdump_api(['a5添加成员[请求接口]'.__LINE__,$url, $fArr, $this->token, $info],'a5New/addMembersLog',1);
            $device_id = $postArr['device_id'];
            $device_sn = $otherParam['device_sn']?$otherParam['device_sn']:'';
            $orderGroupId = $otherParam['orderGroupId']?$otherParam['orderGroupId']:'';
            $village_id = $otherParam['village_id']?$otherParam['village_id']:0;
            $uid = $otherParam['uid']?$otherParam['uid']:0;
            if($info['code'] == 200){
                if (isset($postArr['type']) && $postArr['type'] == 6) {
                    // 访客通行出入
                    $whereBindType = [
                        'bind_type'   => 40,
                        'device_type' => 5,
                        'person_id'   => $postArr['pigcms_id'],// 访客对应小区身份id
                        'groupID'     => $village_id,// 访客要拜访小区
                    ];
                    $db_face_user_bind_device = D('Face_user_bind_device');
                    $aboutFaceUser = $db_face_user_bind_device->where($whereBindType)->order('id DESC')->find();
                    $cardNo = isset($aboutFaceUser['code']) && $aboutFaceUser['code'] ? $aboutFaceUser['code'] : '';
                    if ($cardNo) {
                        $this->addCard($postArr['pigcms_id'],$cardNo,$bind_type,$device_type);
                    }
                }
                
                if($postArr['ic_card']){
                    //新增卡片
                    $param = array();
                    $param['type'] = 'addA5Card';
                    $param['employeeNo'] = $info['data']['employeeNo'];
                    $param['ic_card'] = $postArr['ic_card'];
                    $param['bind_type'] = $bind_type;
                    $param['unique_id'] = 'addA5Card_'.$param['employeeNo'].'_'.$postArr['ic_card'].'_'.$bind_type;
                    // 添加权限组优先
                    fdump_api(['a5添加卡片[人员添加成功]'.__LINE__,$info, $postArr, $param],'a5New/addA5CardLog',1);
                    $a5CardSynPlanTime = D('House_face_device_log')->a5CardSynPlanTime;
                    D('Face_door_service')->add_plan($param, 'sub_house_village_face_device', $a5CardSynPlanTime);

                    // 添加了计划任务
                    $logParam = [
                        'log_name' => 'planSynCard','logType' => 'planA5','log_status' => 37,'orderGroupId' => $orderGroupId,
                        'log_content' => '单人卡片添加计划任务','device_type' => $device_type,'ic_card' => $postArr['ic_card'],
                        'bind_id' => $postArr['pigcms_id'],'uid' => $uid,'startTime' => $arr['validBeginTime'],'endTime' => $arr['validEndTime'],
                        'village_id' => $village_id,'addTime' => time(),
                    ];
                    try {
                        D('House_face_device_log')->addLog($logParam);
                    } catch( \Exception $e) {
                        // 记录日志报错
                        fdump_api(['日志记录[单人卡片添加计划任务]出错'.__LINE__.'方法'.__FUNCTION__,$logParam,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
                    }
//                    $info = $this->addCard($info['data']['employeeNo'],$postArr['ic_card'],$bind_type);
                }
                $person_json_md5 = md5(json_encode([$jsonArr, date('Y-m-d H',time())], JSON_UNESCAPED_UNICODE));
                $dataArr = [
                    'bind_id'=>$postArr['pigcms_id'],
                    'device_type'=>$device_type,
                    'person_json'=>$person_json_md5,
                    'info'=>serialize($info),
                    'add_time'=>time(),
                ];
                D('Face_user_bind_device')->data($dataArr)->add();
                D('House_village_user_bind')->where(array('pigcms_id' => $postArr['pigcms_id']))->save(array('face_img_status' => 1, 'face_img_reason' => ''));
                if($log_id){
                    M('House_face_sync_log')->where(['log_id'=>$log_id])->save([
                        'status'=>1,
                        'msg'=>'',
                        'last_time'=>time()
                    ]);
                } else {
                    $device_sn = isset($postArr['device_sn']) && $postArr['device_sn'] ? trim($postArr['device_sn']) : '';
                    $syncLogParams = [
                        'pigcms_id' => $postArr['pigcms_id'], 'face_id' => 0, 'device_id' => $postArr['device_id'], 'device_sn' => $device_sn,
                        'log_time' => time(), 'status' => 1, 'msg' => ''
                    ];
                    $log_id = D('Face_door_service')->handleHouseFaceSyncLog($syncLogParams);
                }
                fdump_api(['a5添加人员成功'.__LINE__,$info, $postArr, $bind_type, $info],'a5New/addMembersSuccessLog',1);
                // 成功添加日志
                $logParam = [
                    'log_name' => 'planSynUser','logType' => 'planA5','log_status' => 31,'orderGroupId' => $orderGroupId,
                    'log_content' => '同步人员成功','device_id' => $device_id,'device_sn' => $device_sn,
                    'device_type' => $device_type,'village_id' => $village_id, 'addTime' => time(),
                    'bind_id' => $postArr['pigcms_id'],'uid' => $uid,'startTime' => $arr['validBeginTime'],'endTime' => $arr['validEndTime'],
                    'toType' => 1,'toTypeId' => $postArr['pigcms_id'],'arriveType' => 3,'deviceType' => $device_type,
                    'deviceId' => $device_id,'deviceSn' => $device_sn,'toStatus' => 2,'arriveTypeId' => $postArr['pigcms_id'],
                ];
                try {
                    D('House_face_device_log')->addLog($logParam);
                } catch( \Exception $e) {
                    // 记录日志报错
                    fdump_api(['日志记录[同步人员成功]出错'.__LINE__.'方法'.__FUNCTION__,$logParam,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
                }
                return $info;
            }elseif ($info['code'] == 40013001){//用户已存储到云，本地没有的情况
                //修改云信息
                usleep($this->loopUSleepTime);// 避免超频调用
                $this->keepPlanThread();
                $yunData = $this->getMember($postArr['pigcms_id']);
                fdump_api(['a5获取人员[用户已存储到云，本地没有的情况]'.__LINE__, $userInfo,$yunData,$info],'a5New/errAddMembersGetUserLog',1);
                if($yunData['code'] == 200){
                    $dataArr = [
                        'bind_id'=>$postArr['pigcms_id'],
                        'device_type'=>$device_type,
                        'info'=>serialize($info),
                        'add_time'=>time(),
                    ];
                    D('Face_user_bind_device')->data($dataArr)->add();
                    if(empty($yunData['data']['validBeginTime'])){
                        $postArr['validBeginTime'] = date('Y-m-d',time());
                    }
                    if(empty($yunData['data']['validEndTime']) && !$postArr['validEndTime']){
                        $postArr['validEndTime'] = date('Y-m-d',time()+3652*86400);
                    }
                    usleep($this->loopUSleepTime);// 避免超频调用
                    $this->keepPlanThread();
                    if (isset($yunData['data']['faceUrl']) && $yunData['data']['faceUrl'] && $postArr['img_url']) {
                        $face_img_reason = D('House_village_user_bind')->where(array('pigcms_id' => $postArr['pigcms_id']))->getField('face_img_reason');
                        if ($face_img_reason) {
                            unset($postArr['img_url']);
                            $postArr['face_img'] = $postArr['img_url'];
                            fdump_api(['a5二次同步图片异常不再同步'.__LINE__, $postArr, $yunData],'a5New/noticeNoImgEditMemberLog',1);
                        }
                        $this->keepPlanThread();
                    }
                    $msg = $this->editMember($postArr);
                    fdump_api(['a5去编辑人员[用户已存储到云，本地没有记录并编辑]'.__LINE__, $postArr, $bind_type, $msg],'a5New/editMemberLog',1);
                }else{
                    //云不存在，就删除本地重新添加
                    usleep($this->loopUSleepTime);// 避免超频调用
                    $this->keepPlanThread();
                    D('Face_user_bind_device')->where($where)->delete();
                    $msg = $this->addMembers($postArr,$bind_type);
                    fdump_api(['a5删除本地数据后重新添加人员'.__LINE__, $postArr, $bind_type, $msg],'a5New/addMembersLog',1);
                }
                return $userInfo;
            }else{
                $errMessage = $info['message'] ? : '未知错误，请重试同步';
                if($log_id){
                    M('House_face_sync_log')->where(['log_id'=>$log_id])->save([
                        'status'=>2,
                        'msg'=> $errMessage,
                        'last_time'=>time()
                    ]);
                } else {
                    $device_sn = isset($postArr['device_sn']) && $postArr['device_sn'] ? trim($postArr['device_sn']) : '';
                    $syncLogParams = [
                        'pigcms_id' => $postArr['pigcms_id'], 'face_id' => 0, 'device_id' => $postArr['device_id'], 'device_sn' => $device_sn,
                        'log_time' => time(), 'status' => 2, 'msg' => $errMessage
                    ];
                    $log_id = D('Face_door_service')->handleHouseFaceSyncLog($syncLogParams);
                }
                D('House_village_user_bind')->where(array('pigcms_id' => $postArr['pigcms_id']))->save(array('face_img_status' => 2, 'face_img_reason' => $errMessage));
                fdump_api(['a5添加人员其他错误'.__LINE__, $postArr, $bind_type, $info],'a5New/errAddMembersLog',1);
                // 失败添加日志
                $logParam = [
                    'log_name' => 'planSynUser','logType' => 'planA5','log_status' => 31,'orderGroupId' => $orderGroupId,
                    'log_content' => '同步人员失败','device_id' => $device_id,'device_sn' => $device_sn,
                    'device_type' => $device_type,'village_id' => $village_id, 'addTime' => time(),
                    'bind_id' => $postArr['pigcms_id'],'uid' => $uid,'startTime' => $arr['validBeginTime'],'endTime' => $arr['validEndTime'],
                    'toType' => 1,'toTypeId' => $postArr['pigcms_id'],'arriveType' => 3,'deviceType' => $device_type,
                    'deviceId' => $device_id,'deviceSn' => $device_sn,'arriveTypeId' => $postArr['pigcms_id'],
                ];
                $logParam['log_status'] = 32;
                $logParam['toStatus'] = 3;
                if (isset($info['code'])) {
                    $logParam['errCode'] = $info['code'];
                }
                if (isset($info['message'])) {
                    $logParam['errReason'] = $info['message'];
                } else {
                    $logParam['errReason'] = '未知错误4';
                }
                try {
                    D('House_face_device_log')->addLog($logParam);
                } catch( \Exception $e) {
                    // 记录日志报错
                    fdump_api(['日志记录[同步人员失败]出错'.__LINE__.'方法'.__FUNCTION__,$logParam,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
                }
                return $info;
            }
        }else{
            //修改云信息
            usleep($this->loopUSleepTime);// 避免超频调用
            $this->keepPlanThread();
            $yunData = $this->getMember($userInfo['bind_id']);
            fdump_api(['a5获取人员[本地有记录信息获取云信息]'.__LINE__, $userInfo,$yunData,$postArr],'a5New/errAddMembersGetUserLog',1);
            if($yunData['code'] == 200){
                if(empty($yunData['data']['validBeginTime'])){
                    $postArr['validBeginTime'] = date('Y-m-d',time());
                }
                if($postArr['end_time']){
                    $postArr['validEndTime']=date('Y-m-d',$postArr['end_time']);
                } elseif(empty($yunData['data']['validEndTime'])){
                    $postArr['validEndTime'] = date('Y-m-d',time()+3652*86400);
                }
                usleep($this->loopUSleepTime);// 避免超频调用
                $this->keepPlanThread();
                if (isset($yunData['data']['faceUrl']) && $yunData['data']['faceUrl'] && $postArr['img_url']) {
                    $face_json_md5 = md5(json_encode([$postArr['img_url'], date('Y-m-d H',time())], JSON_UNESCAPED_UNICODE));
                    if ($face_json_md5==$userInfo['face_json']) {
                        $face_img_reason = D('House_village_user_bind')->where(array('pigcms_id' => $postArr['pigcms_id']))->getField('face_img_reason');
                        if ($face_img_reason) {
                            $postArr['face_img'] = $postArr['img_url'];
                            unset($postArr['img_url']);
                            fdump_api(['a5二次同步图片异常不再同步'.__LINE__, $postArr, $yunData],'a5New/noticeNoImgEditMemberLog',1);
                        }
                        $this->keepPlanThread();
                    } elseif (!$userInfo['face_json'] && $postArr['validEndTime'] && strtotime($postArr['validEndTime']) > time()) {
                        if (strtotime($yunData['data']['validEndTime']) != strtotime($postArr['validEndTime'])) {
                            $face_img_reason = D('House_village_user_bind')->where(array('pigcms_id' => $postArr['pigcms_id']))->getField('face_img_reason');
                            if ($face_img_reason) {
                                $postArr['face_img'] = $postArr['img_url'];
                                unset($postArr['img_url']);
                                fdump_api(['a5二次同步图片异常不再同1步'.__LINE__, $postArr, $yunData],'a5New/noticeNoImgEditMemberLog',1);
                            }
                        }
                    }
                } elseif (isset($yunData['data']['faceUrl']) && $yunData['data']['faceUrl'] && $postArr['img_url']==$userInfo['face_img']) {
                    $postArr['face_img'] = $postArr['img_url'];
                    unset($postArr['img_url']);
                    fdump_api(['a5二次同步图片异常不再同步'.__LINE__, $postArr, $yunData],'a5New/noticeNoImgEditMemberLog',1);
                }
                $msg = $this->editMember($postArr,$bind_type,$device_type,$otherParam);
                if (isset($msg['code']) && $msg['code'] != 200 && isset($postArr['img_url']) && $postArr['img_url'] && isset($yunData['data']['faceUrl']) && $yunData['data']['faceUrl'] && $postArr['img_url']) {
                    $postArr['face_img'] = $postArr['img_url'];
                    unset($postArr['img_url']);
                    $msg = $this->editMember($postArr,$bind_type,$device_type,$otherParam);
                }
                fdump_api(['a5去编辑人员'.__LINE__, $postArr, $bind_type, $msg],'a5New/editMemberLog',1);
            }else{
                //云不存在，就删除本地重新添加
                D('Face_user_bind_device')->where($where)->delete();
                usleep($this->loopUSleepTime);// 避免超频调用
                $this->keepPlanThread();
                $msg = $this->addMembers($postArr,$bind_type,$device_type,$otherParam);
                fdump_api(['a5删除本地数据后重新添加人员'.__LINE__, $postArr, $bind_type, $msg],'a5New/addMembersLog',1);
            }
            return $userInfo;
        }
    }

    /**
     * Notes: 获取云设备人员
     * @param $member_id
     * @return mixed
     * @author: weili
     * @datetime: 2020/7/22 14:10
     */
    public function getMember($member_id)
    {
        $url = $this->a5_face_class->getMember;
        $url = $url.'?employeeNo='.$member_id;
        $info = $this->a5_face_class->curlGet($url,$this->token);
        return $info;
    }
    /**
     * Notes: 修个人员
     * @param $postArr
     * @return mixed
     * @author: weili
     * @datetime: 2020/7/22 9:58
     */
    public function editMember($postArr,$bind_type=0,$device_type=5,$otherParam=[])
    {
        $log_id = $postArr['log_id']??0;
        $whereBind = [];
        $whereBind['bind_type'] = $bind_type;
        $whereBind['bind_id'] = $postArr['pigcms_id'];
        $whereBind['device_type'] = $device_type;
        //本地用户表
        $userInfo = D('Face_user_bind_device')->where($whereBind)->field('person_json')->find();

        $url = $this->a5_face_class->editMember;
        $arr = [
            'employeeNo' => $postArr['pigcms_id'],
            'personName' =>$this->replaceWord_($postArr['pigcms_id'],$postArr['name'],$postArr['phone']),
        ];
        $arr['personName'] = trim($arr['personName']);
        if ($postArr['layer_num']) {
            $arr['floorNo'] = $postArr['layer_num'];
        }
        if ($postArr['vacancy_num']) {
            $arr['roomNo'] = $postArr['vacancy_num'];
        }
        if($postArr['validBeginTime']){
            $arr['validBeginTime'] = $postArr['validBeginTime'];
        }
        if($postArr['validEndTime']){
            $arr['validEndTime'] = $postArr['validEndTime'];
        }
        if($postArr['img_url'])
        {
            $arr['faceImageBase64'] = $this->imgtobase64($postArr['img_url']);
            if (!$arr['faceImageBase64']) {
                // 如果图片解析失败 直接报错
                $file_handle = new file_handle();
                if($file_handle->check_open_oss()) {
                    usleep($this->loopUSleepTime);// 避免超频调用
                    $this->keepPlanThread();
                    $img_filename = $file_handle->get_path($postArr['img_url']);
                    $file_handle->upload('.'.$img_filename);
                    $arr['faceImageBase64'] = $this->imgtobase64($postArr['img_url']);
                }
                fdump_api(['a5添加成员[图片转base64失败]'.__LINE__,$arr, $postArr,$img_filename],'a5New/errAddMembersLog',1);
                if (!$arr['faceImageBase64']) {
                    D('House_village_user_bind')
                        ->where(array('pigcms_id' => $postArr['pigcms_id']))
                        ->save(array('face_img_status' => 2, 'face_img_reason' => '图片解析失败，请重新同步'));
                    if($log_id){
                        M('House_face_sync_log')->where(['log_id'=>$log_id])->save([
                            'status'=>2,
                            'msg'=>L_('图片解析失败，请重新同步'),
                            'last_time'=>time()
                        ]);
                    } else {
                        $device_sn = isset($postArr['device_sn']) && $postArr['device_sn'] ? trim($postArr['device_sn']) : '';
                        $syncLogParams = [
                            'pigcms_id' => $postArr['pigcms_id'], 'face_id' => 0, 'device_id' => $postArr['device_id'], 'device_sn' => $device_sn,
                            'log_time' => time(), 'status' => 2, 'msg' => '图片解析失败，请重新同步'
                        ];
                        $log_id = D('Face_door_service')->handleHouseFaceSyncLog($syncLogParams);
                    }
                    return $postArr;
                }
            }
        }
        if($postArr['phone']){
            $arr['personPhone'] = trim($postArr['phone']);
        }
        if($postArr['end_time']){
            $arr['validEndTime']=date('Y-m-d',$postArr['end_time']);
        }
        $arrOld = $arr;
        // A5限制  2145887999  不可超出 2037-12-31 23:59:59
        $judeTimes = strtotime($arr['validEndTime']);
        $judeTimes = intval($judeTimes);
        if (isset($arr['validEndTime'])&&$judeTimes>2145887999) {
            // 如果超出就赋予这个值
            $arr['validEndTime'] = '2037-12-31';
            $judeTimess = strtotime($arr['validBeginTime']);
            $judeTimess = intval($judeTimess);
            if (isset($arr['validBeginTime'])&&$judeTimess>2145887999) {
                // 开始时间如果超出就赋予 这一天0点对应值
                $arr['validBeginTime'] = '2037-11-30';
            }
        }
        $validEndTimes = strtotime($arr['validEndTime']);
        if (!$validEndTimes) {
            $validEndTimes = time();
        }
        if ($arr['validEndTime'] && $arr['validBeginTime'] && intval(strtotime($arr['validBeginTime']))>intval($validEndTimes)) {
            // 如果当前时间大于到用户期时间 开始时间以结束时间往前推一天
            $now_time = strtotime($arr['validEndTime']) - 86400;
            $arr['validBeginTime']=date('Y-m-d',$now_time);
        }
        $jsonArr = $arr;
        $arr = json_encode($arr,JSON_UNESCAPED_SLASHES);
        $face_json_md5 = md5(json_encode([$postArr['img_url'], date('Y-m-d H',time())], JSON_UNESCAPED_UNICODE));
        $person_json_md5 = md5(json_encode([$jsonArr, date('Y-m-d H',time())], JSON_UNESCAPED_UNICODE));
        $village_id = $otherParam['village_id']?$otherParam['village_id']:0;
        if ($userInfo && isset($userInfo['person_json']) && $userInfo['person_json']==$person_json_md5) {
            D('House_village_user_bind')->where(array('pigcms_id' => $postArr['pigcms_id']))->save(array('face_img_status' => 1, 'face_img_reason' => ''));
            if($log_id){
                M('House_face_sync_log')->where(['log_id'=>$log_id])->save([
                    'status'=>1,
                    'msg'=>'',
                    'last_time'=>time()
                ]);
            } else {
                $device_sn = isset($postArr['device_sn']) && $postArr['device_sn'] ? trim($postArr['device_sn']) : '';
                $syncLogParams = [
                    'pigcms_id' => $postArr['pigcms_id'], 'face_id' => 0, 'device_id' => $postArr['device_id'], 'device_sn' => $device_sn,
                    'log_time' => time(), 'status' => 1, 'msg' => ''
                ];
                $log_id = D('Face_door_service')->handleHouseFaceSyncLog($syncLogParams);
            }

            if (isset($postArr['type']) && $postArr['type'] == 6) {
                // 访客通行出入
                $whereBindType = [
                    'bind_type'   => 40,
                    'device_type' => 5,
                    'person_id'   => $postArr['pigcms_id'],// 访客对应小区身份id
                    'groupID'     => $village_id,// 访客要拜访小区
                ];
                $db_face_user_bind_device = D('Face_user_bind_device');
                $aboutFaceUser = $db_face_user_bind_device->where($whereBindType)->order('id DESC')->find();
                $cardNo = isset($aboutFaceUser['code']) && $aboutFaceUser['code'] ? $aboutFaceUser['code'] : '';
                if ($cardNo) {
                    $this->addCard($postArr['pigcms_id'],$cardNo,$bind_type,$device_type);
                }
            }
            
            if($postArr['ic_card']) {
                //新增卡片
                $param = array();
                $param['type'] = 'addA5Card';
                $param['employeeNo'] = $postArr['pigcms_id'];
                $param['ic_card'] = $postArr['ic_card'];
                $param['bind_type'] = $bind_type;
                $param['unique_id'] = 'addA5Card_' . $param['employeeNo'] . '_' . $postArr['ic_card'] . '_' . $bind_type;
                // 添加权限组优先
                fdump_api(['a5添加卡片[人员添加成功]' . __LINE__, $postArr, $param, $otherParam], 'a5New/addA5CardLog', 1);
                $a5CardSynPlanTime = D('House_face_device_log')->a5CardSynPlanTime;
                D('Face_door_service')->add_plan($param, 'sub_house_village_face_device', $a5CardSynPlanTime);
            } elseif (isset($postArr['ic_card']) && !$postArr['ic_card']) {
                $ic_card = M('House_village_user_bind')->where(['pigcms_id'=>$postArr['pigcms_id']])->getField('ic_card');
                // 确保当前用户确实清空了卡号
                // 传了卡号字段 确没有值 默认为空置处理 如果曾经添加过卡号且同步成功了 当前没有卡号 直接删除
                if (!$ic_card) {
                    $device_type = 5;
                    $where_repeat = [
                        'bind_type' => $bind_type,
                        'bind_id' => $postArr['pigcms_id'],
                        'device_type' => $device_type,
                    ];
                    $db_face_user_bind_device = D('Face_user_bind_device');
                    $repeat = $db_face_user_bind_device->where($where_repeat)->field('card_status,cardid,card_json')->find();
                    if (!empty($repeat) && $repeat['card_status']==1 && $repeat['cardid']) {
                        usleep($this->loopUSleepTime);// 避免超频调用
                        $this->keepPlanThread();
                        $this->deletePersonCards($postArr['pigcms_id'],$repeat['cardid'],$bind_type,$device_type,$otherParam);
                    }
                }
            }
            return true;
        }
        usleep($this->loopUSleepTime);// 避免超频调用
        $this->keepPlanThread();
        $info = $this->a5_face_class->curlPost($url,$arr,1,$this->token);
        fdump_api(['a5编辑人员[请求接口]'.__LINE__, $url, $arrOld, $this->token,$info],'a5New/editMemberLog',1);

        $device_id = $postArr['device_id'];
        $device_sn = $otherParam['device_sn']?$otherParam['device_sn']:'';
        $orderGroupId = $otherParam['orderGroupId']?$otherParam['orderGroupId']:'';
        $village_id = $otherParam['village_id']?$otherParam['village_id']:0;
        $uid = $otherParam['uid']?$otherParam['uid']:'';
        if($info['code'] == 200){

            if (isset($postArr['type']) && $postArr['type'] == 6) {
                // 访客通行出入
                $whereBindType = [
                    'bind_type'   => 40,
                    'device_type' => 5,
                    'person_id'   => $postArr['pigcms_id'],// 访客对应小区身份id
                    'groupID'     => $village_id,// 访客要拜访小区
                ];
                $db_face_user_bind_device = D('Face_user_bind_device');
                $aboutFaceUser = $db_face_user_bind_device->where($whereBindType)->order('id DESC')->find();
                $cardNo = isset($aboutFaceUser['code']) && $aboutFaceUser['code'] ? $aboutFaceUser['code'] : '';
                if ($cardNo) {
                    $this->addCard($postArr['pigcms_id'],$cardNo,$bind_type,$device_type);
                }
            }
            if($postArr['ic_card']){
                //新增卡片
                $param = array();
                $param['type'] = 'addA5Card';
                $param['employeeNo'] = $postArr['pigcms_id'];
                $param['ic_card'] = $postArr['ic_card'];
                $param['bind_type'] = $bind_type;
                $param['unique_id'] = 'addA5Card_'.$param['employeeNo'].'_'.$postArr['ic_card'].'_'.$bind_type;
                // 添加权限组优先
                fdump_api(['a5添加卡片[人员添加成功]'.__LINE__,$info, $postArr, $param,$otherParam],'a5New/addA5CardLog',1);
                $a5CardSynPlanTime = D('House_face_device_log')->a5CardSynPlanTime;
                D('Face_door_service')->add_plan($param, 'sub_house_village_face_device', $a5CardSynPlanTime);
                // 添加了计划任务
                $logParam = [
                    'log_name' => 'planSynCard','logType' => 'planA5','log_status' => 37,'orderGroupId' => $orderGroupId,
                    'log_content' => '单人卡片添加计划任务','device_type' => $device_type,'village_id' => $village_id,
                    'ic_card' => $postArr['ic_card'],'bind_id' => $postArr['pigcms_id'],'uid' => $uid,'startTime' => $arr['validBeginTime'],'endTime' => $arr['validEndTime'],
                    'addTime' => time(),
                ];
                try {
                    D('House_face_device_log')->addLog($logParam);
                } catch( \Exception $e) {
                    // 记录日志报错
                    fdump_api(['日志记录[单人卡片添加计划任务]出错'.__LINE__.'方法'.__FUNCTION__,$param,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
                }
            } elseif (isset($postArr['ic_card']) && !$postArr['ic_card']) {
                $ic_card = M('House_village_user_bind')->where(['pigcms_id'=>$postArr['pigcms_id']])->getField('ic_card');
                // 确保当前用户确实清空了卡号
                // 传了卡号字段 确没有值 默认为空置处理 如果曾经添加过卡号且同步成功了 当前没有卡号 直接删除
                if (!$ic_card) {
                    $device_type = 5;
                    $where_repeat = [
                        'bind_type' => $bind_type,
                        'bind_id' => $postArr['pigcms_id'],
                        'device_type' => $device_type,
                    ];
                    $db_face_user_bind_device = D('Face_user_bind_device');
                    $repeat = $db_face_user_bind_device->where($where_repeat)->field('card_status,cardid,card_json')->find();
                    if (!empty($repeat) && $repeat['card_status']==1 && $repeat['cardid']) {
                        usleep($this->loopUSleepTime);// 避免超频调用
                        $this->keepPlanThread();
                        $this->deletePersonCards($postArr['pigcms_id'],$repeat['cardid'],$bind_type,$device_type,$otherParam);
                    }
                }
            }
            $dataArr = [
                'person_json'=>$person_json_md5,
                'face_json'=>$face_json_md5,
                'info'=>serialize($info),
                'last_time'=>time(),
            ];
            if (isset($postArr['face_img'])&&$postArr['face_img']) {
                $dataArr['face_img'] = $postArr['face_img'];
            } elseif (isset($postArr['img_url'])&&$postArr['img_url']) {
                $dataArr['face_img'] = $postArr['img_url'];
            }
            D('Face_user_bind_device')->where(['bind_id'=>$postArr['pigcms_id'],'device_type'=> 5])->data($dataArr)->save();
            D('House_village_user_bind')->where(array('pigcms_id' => $postArr['pigcms_id']))->save(array('face_img_status' => 1, 'face_img_reason' => ''));
            if($log_id){
                M('House_face_sync_log')->where(['log_id'=>$log_id])->save([
                    'status'=>1,
                    'msg'=>'',
                    'last_time'=>time()
                ]);
            } else {
                $device_sn = isset($postArr['device_sn']) && $postArr['device_sn'] ? trim($postArr['device_sn']) : '';
                $syncLogParams = [
                    'pigcms_id' => $postArr['pigcms_id'], 'face_id' => 0, 'device_id' => $postArr['device_id'], 'device_sn' => $device_sn,
                    'log_time' => time(), 'status' => 1, 'msg' => ''
                ];
                $log_id = D('Face_door_service')->handleHouseFaceSyncLog($syncLogParams);
            }
            fdump_api(['a5编辑人员成功'.__LINE__, $url, $arr, $this->token,$postArr,$info,$dataArr],'a5New/editMemberSuccessLog',1);
            // 成功添加日志
            $logParam = [
                'log_name' => 'planSynUserEdit','logType' => 'planA5','log_status' => 33,'orderGroupId' => $orderGroupId,
                'log_content' => '同步更新人员成功','device_id' => $device_id,'device_sn' => $device_sn,
                'device_type' => $device_type,'village_id' => $village_id, 'addTime' => time(),
                'bind_id' => $postArr['pigcms_id'],'uid' => $uid,'startTime' => $arr['validBeginTime'],'endTime' => $arr['validEndTime'],
                'toType' => 1,'toTypeId' => $postArr['pigcms_id'],'arriveType' => 3,'deviceType' => $device_type,
                'deviceId' => $device_id,'deviceSn' => $device_sn,'toStatus' => 2,'arriveTypeId' => $postArr['pigcms_id'],
            ];
            try {
                D('House_face_device_log')->addLog($logParam);
            } catch( \Exception $e) {
                // 记录日志报错
                fdump_api(['日志记录[同步更新人员成功]出错'.__LINE__.'方法'.__FUNCTION__,$logParam,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
            }
            return $info;
        }else{
            if (isset($postArr['type']) && $postArr['type'] == 6) {
                // 访客通行出入
                $whereBindType = [
                    'bind_type'   => 40,
                    'device_type' => 5,
                    'person_id'   => $postArr['pigcms_id'],// 访客对应小区身份id
                    'groupID'     => $village_id,// 访客要拜访小区
                ];
                $db_face_user_bind_device = D('Face_user_bind_device');
                $aboutFaceUser = $db_face_user_bind_device->where($whereBindType)->order('id DESC')->find();
                $cardNo = isset($aboutFaceUser['code']) && $aboutFaceUser['code'] ? $aboutFaceUser['code'] : '';
                if ($cardNo) {
                    $this->addCard($postArr['pigcms_id'],$cardNo,$bind_type,$device_type);
                }
            }
            if($postArr['ic_card']){
                //新增卡片
                $param = array();
                $param['type'] = 'addA5Card';
                $param['employeeNo'] = $postArr['pigcms_id'];
                $param['ic_card'] = $postArr['ic_card'];
                $param['bind_type'] = $bind_type;
                $param['unique_id'] = 'addA5Card_'.$param['employeeNo'].'_'.$postArr['ic_card'].'_'.$bind_type;
                // 添加权限组优先
                fdump_api(['a5添加卡片[人员添加成功]'.__LINE__,$info, $postArr, $param,$otherParam],'a5New/addA5CardLog',1);
                $a5CardSynPlanTime = D('House_face_device_log')->a5CardSynPlanTime;
                D('Face_door_service')->add_plan($param, 'sub_house_village_face_device', $a5CardSynPlanTime);
                // 添加了计划任务
                $logParam = [
                    'log_name' => 'planSynCard','logType' => 'planA5','log_status' => 37,'orderGroupId' => $orderGroupId,
                    'log_content' => '单人卡片添加计划任务','device_type' => $device_type,'village_id' => $village_id,
                    'ic_card' => $postArr['ic_card'],'bind_id' => $postArr['pigcms_id'],'uid' => $uid,'startTime' => $arr['validBeginTime'],'endTime' => $arr['validEndTime'],
                    'addTime' => time(),
                ];
                try {
                    D('House_face_device_log')->addLog($logParam);
                } catch( \Exception $e) {
                    // 记录日志报错
                    fdump_api(['日志记录[单人卡片添加计划任务]出错'.__LINE__.'方法'.__FUNCTION__,$param,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
                }
            } elseif (isset($postArr['ic_card']) && !$postArr['ic_card']) {
                $ic_card = M('House_village_user_bind')->where(['pigcms_id'=>$postArr['pigcms_id']])->getField('ic_card');
                // 确保当前用户确实清空了卡号
                // 传了卡号字段 确没有值 默认为空置处理 如果曾经添加过卡号且同步成功了 当前没有卡号 直接删除
                if (!$ic_card) {
                    $device_type = 5;
                    $where_repeat = [
                        'bind_type' => $bind_type,
                        'bind_id' => $postArr['pigcms_id'],
                        'device_type' => $device_type,
                    ];
                    $db_face_user_bind_device = D('Face_user_bind_device');
                    $repeat = $db_face_user_bind_device->where($where_repeat)->field('card_status,cardid,card_json')->find();
                    if (!empty($repeat) && $repeat['card_status']==1 && $repeat['cardid']) {
                        usleep($this->loopUSleepTime);// 避免超频调用
                        $this->keepPlanThread();
                        $this->deletePersonCards($postArr['pigcms_id'],$repeat['cardid'],$bind_type,$device_type,$otherParam);
                    }
                }
            }
            D('House_village_user_bind')->where(array('pigcms_id' => $postArr['pigcms_id']))->save(array('face_img_status' => 2, 'face_img_reason' => $info['message']));
            if($log_id){
                M('House_face_sync_log')->where(['log_id'=>$log_id])->save([
                    'status'=>2,
                    'msg'=>$info['message']??'',
                    'last_time'=>time()
                ]);
            } else {
                $device_sn = isset($postArr['device_sn']) && $postArr['device_sn'] ? trim($postArr['device_sn']) : '';
                $syncLogParams = [
                    'pigcms_id' => $postArr['pigcms_id'], 'face_id' => 0, 'device_id' => $postArr['device_id'], 'device_sn' => $device_sn,
                    'log_time' => time(), 'status' => 2, 'msg' => $info['message']??''
                ];
                $log_id = D('Face_door_service')->handleHouseFaceSyncLog($syncLogParams);
            }
            fdump_api(['a5编辑人员错误'.__LINE__, $url, $arr, $this->token,$postArr,$info],'a5New/errEditMemberlog',1);

            // 失败添加日志
            $logParam = [
                'log_name' => 'planSynUserEdit','logType' => 'planA5','log_status' => 35,'orderGroupId' => $orderGroupId,
                'log_content' => '同步更新人员失败','device_id' => $device_id,'device_sn' => $device_sn,
                'device_type' => $device_type,'village_id' => $village_id, 'addTime' => time(),
                'bind_id' => $postArr['pigcms_id'],'uid' => $uid,'startTime' => $arr['validBeginTime'],'endTime' => $arr['validEndTime'],
                'toType' => 1,'toTypeId' => $postArr['pigcms_id'],'arriveType' => 3,'deviceType' => $device_type,
                'deviceId' => $device_id,'deviceSn' => $device_sn,'arriveTypeId' => $postArr['pigcms_id'],
            ];
            $logParam['log_status'] = 32;
            $logParam['toStatus'] = 3;
            if (isset($info['code'])) {
                $logParam['errCode'] = $info['code'];
            }
            if (isset($info['message'])) {
                $logParam['errReason'] = $info['message'];
            } else {
                $logParam['errReason'] = '未知错误4';
            }
            try {
                D('House_face_device_log')->addLog($logParam);
            } catch( \Exception $e) {
                // 记录日志报错
                fdump_api(['日志记录[同步更新人员失败]出错'.__LINE__.'方法'.__FUNCTION__,$logParam,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
            }

            return $info;
        }
    }
    /**
     * Notes: 新增卡片
     * @param $postArr
     * @return mixed
     * @author: weili
     * @datetime: 2020/7/22 10:00
     */
    public function addCard($employeeNo, $ic_card, $bind_type, $device_type = 5, $otherParam = [], $repeatAdd = 0)
    {
        $where_repeat = [
            'bind_type' => $bind_type,
            'bind_id' => $employeeNo,
            'device_type' => $device_type,
        ];
        $db_face_user_bind_device = D('Face_user_bind_device');
        $repeat = $db_face_user_bind_device->where($where_repeat)->field('card_status,cardid,card_json')->find();
        $cards = [[
            'cardNo' => $ic_card, //卡号
            'cardType' => 'normalCard',
            'employeeNo' => $employeeNo,
        ]];
        $postArr = json_encode(['cards' => $cards]);
        $msgTime = date('YmdH');// 每个小时可以重置一次记录
        $card_json_md5 = md5(json_encode([$cards, $msgTime], JSON_UNESCAPED_UNICODE));

        $orderGroupId = isset($otherParam['orderGroupId']) && $otherParam['orderGroupId'] ? trim($otherParam['orderGroupId']) : '';// 标记统一执行命令
        $village_id = $otherParam['village_id'] ? $otherParam['village_id'] : 0;
        $uid = $otherParam['uid'] ? $otherParam['uid'] : '';
        $startTime = $otherParam['startTime'] ? $otherParam['startTime'] : 0;
        $endTime = $otherParam['endTime'] ? $otherParam['endTime'] : 0;
        $logCardParam = [
            'log_name' => 'planSynCard', 'logType' => 'planA5', 'log_status' => 38, 'orderGroupId' => $orderGroupId,
            'log_content' => '同步卡片成功', 'device_type' => $device_type, 'village_id' => $village_id, 'addTime' => time(),
            'bind_id' => $employeeNo, 'uid' => $uid, 'startTime' => $startTime, 'endTime' => $endTime,
            'toType' => 11, 'toTypeId' => $employeeNo, 'toUid' => $uid, 'arriveType' => 3, 'deviceType' => $device_type,
            'toStatus' => 2, 'arriveTypeId' => $ic_card,
        ];
        if (!$repeatAdd && $repeat && $repeat['card_status'] == 1 && $repeat['card_json'] == $card_json_md5) {
            $info = array(
                'code' => 200,
                'message' => '操作成功【用户相同卡号已经添加】',
            );
            fdump_api(['a5添加卡片【用户相同卡号已经添加】' . __LINE__, $postArr, $repeat], 'a5New/noticeAddCardLog', 1);
            try {
                $logCardParam['log_content'] = '添加卡片成功【用户相同卡号已经添加】';
                D('House_face_device_log')->addLog($logCardParam);
            } catch (\Exception $e) {
                // 记录日志报错
                fdump_api(['日志记录[用户相同卡号已经添加]出错' . __LINE__ . '方法' . __FUNCTION__, $logCardParam, $e->getMessage()], 'faceDeviceLog/errDeviceLog', 1);
            }
            return $info;
        } elseif (!$repeatAdd && $repeat && $repeat['card_status'] == 0 && !$repeat['cardid']) {
            // 卡号已经删除过 实时查询下当前用户卡号 如果实际删除了不进行添加
            $user_ic_card = D('House_village_user_bind')->where(array('pigcms_id' => $employeeNo))->getField('ic_card');
            if (!$user_ic_card) {
                $info = array(
                    'code' => 200,
                    'message' => '操作成功【用户卡号已经同步删除了，不做添加】',
                );
                fdump_api(['a5添加卡片【用户卡号已经同步删除了，不做添加】' . __LINE__, $postArr, $repeat], 'a5New/errAddCardLog', 1);
                $logParam['log_status'] = 41;
                $logCardParam['log_status'] = 39;
                $logCardParam['log_content'] = '添加卡片失败';
                $logCardParam['toStatus'] = 3;
                $logCardParam['errReason'] = '用户卡号已经删除';
                try {
                    D('House_face_device_log')->addLog($logCardParam);
                } catch (\Exception $e) {
                    // 记录日志报错
                    fdump_api(['日志记录[用户卡号已经同步删除了]出错' . __LINE__ . '方法' . __FUNCTION__, $logCardParam, $e->getMessage()], 'faceDeviceLog/errDeviceLog', 1);
                }
                // 确定已经不存在了走下删除方法，避免未删除占用卡号导致后续卡号无法写入
                $this->deletePersonCards($employeeNo, $ic_card, $bind_type, $device_type, $otherParam);
                return $info;
            }
        }

        $url = $this->a5_face_class->addCard;
        usleep($this->loopUSleepTime);// 避免超频调用
        $this->keepPlanThread();
        $info = $this->a5_face_class->curlPost($url, $postArr, 1, $this->token);
        fdump_api(['a5添加卡片[请求接口]' . __LINE__, $url, $postArr, $this->token, $info, $repeat, $card_json_md5], 'a5New/addCardLog', 1);
        if ($info['code'] == 200) {
            $where['bind_type'] = $bind_type;
            $where['bind_id'] = $employeeNo;
            $where['device_type'] = $device_type;
            $set = [
                'card_status' => 1,
                'cardid' => $ic_card,
                'card_json' => $card_json_md5
            ];
            D('Face_user_bind_device')->where($where)->data($set)->save();
            fdump_api(['a5添加卡片成功' . __LINE__, $url, $postArr, $this->token, $info], 'a5New/addCardSuccessLog', 1);
            try {
                D('House_face_device_log')->addLog($logCardParam);
            } catch (\Exception $e) {
                // 记录日志报错
                fdump_api(['日志记录[用户卡号已经同步删除了]出错' . __LINE__ . '方法' . __FUNCTION__, $logCardParam, $e->getMessage()], 'faceDeviceLog/errDeviceLog', 1);
            }
        } elseif ($info['code'] == 40014001) {
            fdump_api(['同一人员下卡号重复[不做处理]' . __LINE__, $url, $postArr, $this->token, $info], 'a5New/errAddCardLog', 1);
            // 重复下发了单独处理修复逻辑（先删除后重新添加）
            if (!$repeatAdd) {
                $param = array();
                $param['type'] = 'repairUserCard';
                $param['employeeNo'] = $employeeNo;
                $param['ic_card'] = $ic_card;
                $param['bind_type'] = $bind_type;
                $param['device_type'] = $device_type;
                $param['unique_id'] = 'repairUserCard' . $employeeNo . '_' . $ic_card . '_' . $device_type;
                D('Face_door_service')->add_plan($param, 'sub_house_village_face_device', -900);
            }
        } else {
            fdump_api(['a5添加卡片其他错误' . __LINE__, $url, $postArr, $this->token, $info], 'a5/errAddCardLog', 1);
        }
        return $info;
    }

    /**
     * 添加卡片.
     */
    public function singleAddCard($ic_card, $employeeNo) {
        $cards = [[
            'cardNo' => $ic_card, //卡号
            'cardType' => 'normalCard',
            'employeeNo' => $employeeNo,
        ]];
        $postArr = json_encode(['cards' => $cards]);
        $url = $this->a5_face_class->addCard;
        usleep($this->loopUSleepTime);// 避免超频调用
        $this->keepPlanThread();
        $info = $this->a5_face_class->curlPost($url, $postArr, 1, $this->token);
        return $info;
    }
    
    //删除卡
    public function deletePersonCards($employeeNo,$ic_card,$bind_type=0,$device_type=5,$otherParam=[],$is_plan=0)
    {
        $where_repeat = [
            'bind_type' => $bind_type,
            'bind_id' => $employeeNo,
            'device_type' => $device_type,
        ];
        $db_face_user_bind_device = D('Face_user_bind_device');
        $repeat = $db_face_user_bind_device->where($where_repeat)->field('card_status,cardid,card_json')->find();
        $del = [
            'cardNos'=>[$ic_card], //卡号
            'employeeNo'=>$employeeNo,
        ];
        $postArr = json_encode($del);
        $card_json_md5 = md5(json_encode($del, JSON_UNESCAPED_UNICODE));
        $orderGroupId = $otherParam['orderGroupId']?$otherParam['orderGroupId']:'';
        $village_id = $otherParam['village_id']?$otherParam['village_id']:0;
        $uid = $otherParam['uid']?$otherParam['uid']:'';
        $logParam = [
            'log_name' => 'planSynCardDel','logType' => 'planA5','log_status' => 40,'orderGroupId' => $orderGroupId,
            'log_content' => '单人卡片删除','device_type' => $device_type,'village_id' =>$village_id,
            'ic_card' => $ic_card,'bind_id' => $employeeNo,'uid' => $uid,
            'toType' => 11,'toTypeId' => $employeeNo,'arriveType' => 3,'deviceType' => $device_type,
            'toStatus' => 2,'arriveTypeId' => $ic_card,
            'isDel' => 1, 'addTime' => time(),
        ];
        if ($repeat && $repeat['card_status']==0 && $repeat['card_json']==$card_json_md5) {
            $info = array (
                'code' => 200,
                'message' => '操作成功【用户相同卡号已经删除】',
            );
            fdump_api(['a5删除卡片失败[操作成功【用户相同卡号已经删除】]'.__LINE__,$repeat, $del, $employeeNo,$ic_card,$bind_type,$device_type],'a5/errDeletePersonCardsLog',1);
            try {
                D('House_face_device_log')->addLog($logParam);
            } catch( \Exception $e) {
                // 记录日志报错
                fdump_api(['日志记录[卡片已经删除]出错'.__LINE__.'方法'.__FUNCTION__,$logParam,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
            }
            return $info;
        }

        $url = 'https://api2.hik-cloud.com/api/v1/open/basic/cards/deletePersonCards';
        usleep($this->loopUSleepTime);// 避免超频调用
        $this->keepPlanThread();
        $info = $this->a5_face_class->curlPost($url,$postArr,1,$this->token);
        if($info['code'] == 200)
        {
            // 记录删除卡号 防止重复操作
            $where['bind_type'] =$bind_type;
            $where['bind_id'] =$employeeNo;
            $where['device_type'] =$device_type;
            $set = [
                'card_status'=>0,
                'cardid' => '',
                'card_json' => $card_json_md5
            ];
            D('Face_user_bind_device')->where($where)->data($set)->save();
            fdump_api(['a5删除卡片成功'.__LINE__,$url, $postArr, $this->token, $info,$employeeNo,$ic_card,$bind_type,$device_type,$repeat,$card_json_md5],'a5/deletePersonCardsSuccessLog',1);
            if (!$is_plan) {
                $this->addCard($employeeNo,$ic_card,$bind_type,$device_type);
            }
            $logParam['log_status'] = 40;
        }else{
            fdump_api(['a5删除卡片失败'.__LINE__,$url, $postArr, $this->token, $info,$employeeNo,$ic_card,$bind_type,$device_type,$repeat,$card_json_md5],'a5/errDeletePersonCardsLog',1);
            $logParam['log_status'] = 41;
            $logParam['log_content'] = '删除卡片失败';
            $logParam['toStatus'] = 3;
            $logParam['isDel'] = 0;
            if (isset($info['code'])) {
                $logParam['errCode'] = $info['code'];
            }
            if (isset($info['message'])) {
                $logParam['errReason'] = $info['message'];
            } else {
                $logParam['errReason'] = '未知错误3';
            }
        }
        try {
            D('House_face_device_log')->addLog($logParam);
        } catch( \Exception $e) {
            // 记录日志报错
            fdump_api(['日志记录[删除卡片失败]出错'.__LINE__.'方法'.__FUNCTION__,$logParam,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
        }
        return $info;
    }


    //删除卡
    public function deleteCards($employeeNo,$ic_card,$bind_type,$device_type,$otherParam=[])
    {
        $where_repeat = [
            'bind_type' => $bind_type,
            'bind_id' => $employeeNo,
            'device_type' => $device_type,
        ];
        $db_face_user_bind_device = D('Face_user_bind_device');
        $repeat = $db_face_user_bind_device->where($where_repeat)->field('card_status,cardid,card_json')->find();
        $del = [
            'cardNos'=>[$ic_card],
        ];
        $postArr = json_encode($del);
        $card_json_md5 = md5(json_encode(['deCard',$del], JSON_UNESCAPED_UNICODE));
        $orderGroupId = $otherParam['orderGroupId']?$otherParam['orderGroupId']:'';
        $village_id = $otherParam['village_id']?$otherParam['village_id']:0;
        $uid = $otherParam['uid']?$otherParam['uid']:'';
        $logParam = [
            'log_name' => 'planSynCardDel','logType' => 'planA5','log_status' => 40,'orderGroupId' => $orderGroupId,
            'log_content' => '卡片删除[全局删除]','device_type' => $device_type,'village_id' =>$village_id,
            'ic_card' => $ic_card,'bind_id' => $employeeNo,'uid' => $uid,
            'toType' => 11,'toTypeId' => $employeeNo,'arriveType' => 3,'deviceType' => $device_type,
            'toStatus' => 2,'arriveTypeId' => $ic_card,
            'isDel' => 1, 'addTime' => time(),
        ];
        if ($repeat && $repeat['card_status']==0 && $repeat['card_json']==$card_json_md5) {
            $info = array (
                'code' => 200,
                'message' => '操作成功【用户相同卡号已经删除】',
            );
            fdump_api(['a5删除卡片失败[操作成功【用户相同卡号已经删除】]'.__LINE__,$repeat, $del, $employeeNo,$ic_card,$bind_type,$device_type],'a5New/errDeletePersonCardsLog',1);
            try {
                D('House_face_device_log')->addLog($logParam);
            } catch( \Exception $e) {
                // 记录日志报错
                fdump_api(['日志记录[卡片已经删除]出错'.__LINE__.'方法'.__FUNCTION__,$logParam,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
            }
            return $info;
        }

        $url = 'https://api2.hik-cloud.com/api/v1/open/basic/cards/batchDelete';
        usleep($this->loopUSleepTime);// 避免超频调用
        $this->keepPlanThread();
        $info = $this->a5_face_class->curlPost($url,$postArr,1,$this->token);
        if($info['code'] == 200)
        {
            // 记录删除卡号 防止重复操作
            $where['bind_type'] =$bind_type;
            $where['bind_id'] =$employeeNo;
            $where['device_type'] =$device_type;
            $set = [
                'card_status'=>0,
                'cardid' => '',
                'card_json' => $card_json_md5
            ];
            D('Face_user_bind_device')->where($where)->data($set)->save();
            fdump_api(['a5删除卡片成功'.__LINE__,$url, $postArr, $this->token, $info,$employeeNo,$ic_card,$bind_type,$device_type,$repeat,$card_json_md5],'a5New/deletePersonCardsSuccessLog',1);
            $logParam['log_status'] = 40;
            fdump_api(['a5删除卡片成功'.__LINE__,$url, $postArr, $this->token, $info,$employeeNo,$ic_card,$bind_type,$device_type,$repeat,$card_json_md5],'a5/deletePersonCardsSuccessLog',1);
        }else{
            fdump_api(['a5删除卡片失败'.__LINE__,$url, $postArr, $this->token, $info,$employeeNo,$ic_card,$bind_type,$device_type,$repeat,$card_json_md5],'a5New/errDeletePersonCardsLog',1);
            $logParam['log_status'] = 41;
            $logParam['log_content'] = '删除卡片失败';
            $logParam['toStatus'] = 3;
            $logParam['isDel'] = 0;
            if (isset($info['code'])) {
                $logParam['errCode'] = $info['code'];
            }
            if (isset($info['message'])) {
                $logParam['errReason'] = $info['message'];
            } else {
                $logParam['errReason'] = '未知错误2';
            }
        }
        try {
            D('House_face_device_log')->addLog($logParam);
        } catch( \Exception $e) {
            // 记录日志报错
            fdump_api(['日志记录[卡片删除成功]出错'.__LINE__.'方法'.__FUNCTION__,$logParam,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
        }
        return $info;
    }

    //外围机情况处理
    public function getDeviceFloorId($device_id,$device_sn,$village_id)
    {
        if(!$device_id && !$device_sn){
            return '';
        }
        $where['village_id'] = $village_id;
        $where['is_del'] = 0;
        if($device_id){
            $where['device_id'] =$device_id;
        }else{
            $where['device_sn'] =$device_sn;
        }
        $floor_id =  D('House_face_device')->where($where)->getField('floor_id');
        if($floor_id && $floor_id<=0)
        {
            return '';
        }
        if($floor_id && strstr($floor_id, ',') != false){
            $floor_id = explode(',',$floor_id);
        }
        return $floor_id;
    }
    /**
     * Notes:获取本地小区单元作为云设备权限组
     * @param $village_id
     * @author: weili
     * @datetime: 2020/7/22 12:55
     */
    public function getFloor($village_id,$device_id,$floor_id='',$otherParam=[])
    {
        $orderGroupId = isset($otherParam['orderGroupId'])&&$otherParam['orderGroupId']?trim($otherParam['orderGroupId']):'';// 标记统一执行命令
        // 查询下该设备会否是新权限组架构
        $judgeNewAuthGroup = $this->judgeNewAuthGroup($device_id);
        fdump_api(['获取本地小区单元作为云设备权限组'.__LINE__,$judgeNewAuthGroup,$village_id,$device_id,$floor_id,$orderGroupId,$otherParam],'a5New/getFloorLog',1);
        if ($judgeNewAuthGroup['newGroup'] || !$judgeNewAuthGroup['floorGroup']) {
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
            $param['unique_id'] = 'addDeviceAuthGroup_'.$village_id.'_'.$device_id;
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
        } else {
            $floor_ids = $this->getDeviceFloorId($device_id,'',$village_id);
            if (is_array($floor_ids) && !in_array($floor_id, $floor_ids)) {
                $floor_id = '';
            } elseif ($floor_id && $floor_ids>0 && $floor_id!=$floor_ids) {
                $floor_id = '';
            }
            // 存在原来的单元组
            if(!$floor_id) {
                if (isset($otherParam['pigcms_id'])&&$otherParam['pigcms_id']) {
                    $floorPigcmsId = D('House_village_user_bind')->where(array('pigcms_id' => $otherParam['pigcms_id']))->getField('floor_id');
                } else {
                    $floorPigcmsId = 0;
                }
                $floorIdArrs = [];
                if($floor_ids && $floor_ids>0)
                {
                    if(is_array($floor_ids))
                    {
                        $floorIdArrs = $floor_ids;
                    }else{
                        $floorIdArrs[] = $floor_ids;
                    }
                }
                if ($floorPigcmsId) {
                    $floorIdArrs[] = $floorPigcmsId;
                }
                if (!empty($floorIdArrs)) {
                    $where['floor_id'] = ['in',$floorIdArrs];
                }
                $where['village_id'] = $village_id;
                $where['status'] = 1;
                $data = D('House_village_floor')->where($where)->field('floor_id,village_id,floor_name')->select();
                //查询所有a5设备序列号 权限组绑定设备
                $map = [];
                $map['device_id'] = $device_id;
                $device_info = D('House_face_device')->where($map)->field('device_sn,village_id')->find();
                $device_sn = $device_info['device_sn'];
                if ($data) {
                    foreach ($data as $val) {
                        if (isset($val['village_id']) && isset($device_info['village_id']) && $val['village_id']!=$device_info['village_id']) {
                            fdump_api(['获取本地小区单元作为云设备权限组'.__LINE__,$device_info,$val,$village_id,$device_id,$floor_id,$otherParam],'a5New/errGetFloorLog1',1);
                            continue;
                        }
                        sleep(1); // 1秒
                        $info = $this->addAuthGroup($val,$device_sn,$device_id);
                    }
                    fdump_api(['获取本地小区单元作为云设备权限组'.__LINE__,$data,$info,$device_sn,$device_id,$floorIdArrs],'a5New/addAuthGroupFloorLog',1);
                    return $info;
                } else {
                    fdump_api(['获取本地小区单元作为云设备权限组'.__LINE__,$village_id,$floor_id,$data,$device_sn],'a5New/getFloorErrLog',1);
                    return $data;
                }
            }else{
                $where['village_id'] = $village_id;
                $where['floor_id'] = $floor_id;
                $info = D('House_village_floor')->where($where)->field('floor_id,village_id,floor_name')->find();
                $map = [];
                $map['device_type'] = ['in',[5,6]];
                $map['device_status'] = 1;
                $map['is_del'] = 0;
                $map['_string'] = "find_in_set($floor_id,floor_id)";
                $map['village_id'] = $info['village_id'];
                $device_info = D('House_face_device')->where($map)->field('device_sn,village_id')->find();
                $device_sn = $device_info['device_sn'];
                if($info)
                {
                    if (isset($info['village_id']) && isset($device_info['village_id']) && $info['village_id']!=$device_info['village_id']) {
                        fdump_api(['获取本地小区单元作为云设备权限组'.__LINE__,$info,$device_info,$device_sn],'a5New/errGetFloorLog1',1);
                        return $info;
                    }
                    //同步云权限分组
                    $infoGroup = $this->addAuthGroup($info,$device_sn,$device_id);
                    fdump_api(['获取本地小区单元作为云设备权限组'.__LINE__,$info,$device_sn,$device_id,$infoGroup,$info],'a5New/addAuthGroupFloorLog',1);
                    return $infoGroup;
                } else {
                    fdump_api(['获取本地小区单元作为云设备权限组'.__LINE__,$info,$map,$device_sn],'a5New/errGetFloorLog',1);
                    return $info;
                }
            }
        }
    }
    /**
     * Notes: 新增权限组
     * @param $postArr
     * @return mixed
     * @author: weili
     * @datetime: 2020/7/22 10:02
     */
    public function addAuthGroup($post,$device_sn,$device_id,$device_type=5,$bind_type=3)
    {
        $aboutInfo = D('Face_bind_about_info')->where(['bind_type'=>$bind_type,'bind_id'=>$post['floor_id'],'device_type'=>$device_type])->order('`id` DESC')->find();
        $whereFloor = [];
        $whereFloor['floor_id'] = $post['floor_id'];
        $village_id = D('House_village_floor')->where($whereFloor)->getField('village_id');
        $mapDevice = [
            'device_sn' => $device_sn,
            'is_del' => 0,
        ];
        $device_village_id = D('House_face_device')->where($mapDevice)->getField('village_id');
        if ($village_id!=$device_village_id) {
            fdump_api(['新增权限组错误'.__LINE__,$post,$device_sn,$device_id,$device_type,$bind_type,$village_id,$device_village_id], 'a5New/errAddAuthGroupLog', true);
            return false;
        }
        fdump(['新增权限组'.__LINE__,$aboutInfo], 'api/log/' . date('Ymd') . '/a5New/addAuthGroupLog', true);
        if(true || !$aboutInfo['group_id']) {
            $url = $this->a5_face_class->addAuthGroup;
            // 避免名称重复 后缀统一加 '_编号'
            $groupName = $post['floor_name'].'_'.$post['floor_id'];
            $postArr = json_encode([
                'groupName' => $groupName
            ]);
            usleep($this->loopUSleepTime);// 避免超频调用
            $this->keepPlanThread();
            $info = $this->a5_face_class->curlPost($url, $postArr, 1, $this->token);
            fdump(['新增权限组[接口请求]'.__LINE__,$url, $postArr, $this->token, $post, $info], 'api/log/' . date('Ymd') . '/a5New/addAuthGroupLog', true);
            if ($info['code']==200) {
                $arr = [
                    'bind_type' => $bind_type,
                    'bind_id' => $post['floor_id'],
                    'bind_name' => $groupName,
                    'group_id' => $info['data']['groupId'],
                    'third_info' => serialize($info),
                    'device_type' => $device_type,
                    'add_time' => time(),
                    'last_time' => time(),
                    'device_id' =>$device_id,
                ];
                // 设备绑定权限组
                D('Face_bind_about_info')->data($arr)->add();
                usleep($this->loopUSleepTime);// 避免超频调用
                $this->keepPlanThread();
                $this->bindAuthGroupEquipment($device_sn, $info['data']['groupId'],$post['floor_id'],$device_id);
                fdump(['新增权限组'.__LINE__,$arr], 'api/log/' . date('Ymd') . '/a5New/addAuthGroupSuccessLog', true);
                return $arr;
            }else{
                if (40021002==$info['code']) {
                    // 如果返回权限组已经存在  查询下本地记录
                    $aboutInfo = D('Face_bind_about_info')->where(['bind_type'=>$bind_type,'bind_id'=>$post['floor_id'],'device_type'=>$device_type])->order('`id` DESC')->find();
                }
                if($aboutInfo && $aboutInfo['group_id']){
                    fdump(['新增权限组[错误情况下走了绑定权限组]'.__LINE__,$device_sn,$aboutInfo, $info,$post['floor_id'],$device_id], 'api/log/' . date('Ymd') . '/a5New/addAuthGroupLog', true);
                    usleep($this->loopUSleepTime);// 避免超频调用
                    $this->keepPlanThread();
                    $infos = $this->bindAuthGroupEquipment($device_sn, $aboutInfo['group_id'],$post['floor_id'],$device_id);
                    return $infos;
                } else {
//                    $list = $this->getAuthGroupList(1,100,$groupName,$post['floor_id'],$device_id);
//                  // 走下修复权限逻辑
                    $param = [];
                    $param['type'] = 'a5RepairGetAuthGroups';
                    $param['time_type'] = 1;
                    $param['unique_id'] = 'a5RepairGetAuthGroups';
                    $plan_time = -999999; // 优先等级最高
                    fdump(['新增权限组[走下修复权限逻辑]'.__LINE__,$url, $postArr,$this->token,$aboutInfo,$device_sn,$post,$info,$param], 'api/log/' . date('Ymd') . '/a5/addAuthGroupLog', true);
                    D('Face_door_service')->add_plan($param, 'sub_house_village_face_device', $plan_time);
                }
                return false;
            }
        }else{
            $infos = $this->bindAuthGroupEquipment($device_sn, $aboutInfo['group_id'],$post['floor_id'],$device_id);
            return $infos;
        }
    }

    /**
     * Notes: 获取所有权限分组列表
     * @param int $pageNo
     * @param int $pageSize
     * @return mixed
     * @author: weili
     * @datetime: 2020/7/23 10:35
     */
    public function getAuthGroupList($pageNo = 1,$pageSize=10,$groupName='',$floor_id=0,$device_id=0,$bind_type=3,$returnFind=false)
    {
        $url = $this->a5_face_class->getAuthGroupList;
        $arr = json_encode([
            "pageNo"=> $pageNo,
            "pageSize"=> $pageSize
        ]);
        $findData='';
        $info = $this->a5_face_class->curlPost($url,$arr,1,$this->token);
        fdump_api([__LINE__,'info'=>$info,'groupName'=>$groupName,$url,'postArr'=>$arr,$this->token,$device_id],'a5New/getAuthGroupList',1);
        if ($groupName && $info['data']['rows']) {
            $face_bind_about_info_db=D('Face_bind_about_info');
            foreach ($info['data']['rows'] as $val)
            {
                if($val['groupName'] == $groupName){
                    $findData=$val;
                    $arr = [
                        'bind_type' => $bind_type,
                        'bind_id' => $floor_id,
                        'bind_name' => $groupName,
                        'group_id' => $val['groupId'],
                        'third_info' => serialize($val),
                        'device_type' => 5,
                        'add_time' => time(),
                        'last_time' => time(),
                        'device_id' =>$device_id,
                    ];
                    $whereArr=array('bind_type'=>$bind_type,'bind_id'=>$floor_id,'bind_name'=>$groupName,'group_id'=>$val['groupId'],'device_id'=>$device_id);
                    $about_info= $face_bind_about_info_db->where($whereArr)->field('id,bind_type,bind_name,device_type,device_id,group_id')->find();
                    if(empty($about_info)){
                        $face_bind_about_info_db->data($arr)->add();
                    }
                    
                    break;
                }
            }
        }
        if($returnFind){
            return $findData;
        }
        return $info;
    }

    /**
     * Notes: 删除权限分组
     * @param int $pageNo
     * @param int $pageSize
     * @return mixed
     * @author: weili
     * @datetime: 2020/7/23 10:36
     */
    public function delAuthGroup($group_id)
    {
        $url = $this->a5_face_class->delAuthGroup;
        $url = $url.'?groupId='.$group_id;
        usleep($this->loopUSleepTime);// 避免超频调用
        $this->keepPlanThread();
        $info = $this->a5_face_class->curlPost($url,[],1,$this->token);
        fdump(['删除多余权限组'.__LINE__,$group_id,$url,$this->token,$info], 'api/log/' . date('Ymd') . '/a5New/delAuthGroupLog', true);
        return $info;
    }
    /**
     * Notes:权限组更新
     * @param $postArr
     * @return mixed
     * @author: weili
     * @datetime: 2020/7/22 10:03
     */
    public function editAuthGroup($postArr)
    {
        $url = $this->a5_face_class->editAuthGroup;
        $info = $this->a5_face_class->curlPost($url,$postArr,1,$this->token);
        return $info;
    }

    /**
     * Notes: 获取 成员信息  权限组绑定人员
     * @param $village_id
     * @param string $floor_id
     * @param string $bind_id
     * @author: weili
     * @datetime: 2020/7/22 13:35
     * @return bool|mixed
     */
    public function getBindAuthGroupMember($village_id, $floor_id='',$bind_id='',$device_id='',$otherParam=[])
    {
        // 查询下该设备会否是新权限组架构
        $orderGroupId = $otherParam['orderGroupId']?$otherParam['orderGroupId']:'';
        $device_sn = $otherParam['device_sn']?$otherParam['device_sn']:'';
        $uid = $otherParam['uid']?$otherParam['uid']:'';
        if ($device_id) {
            $judgeNewAuthGroup = $this->judgeNewAuthGroup($device_id,'',true);
        }
        if ($judgeNewAuthGroup && $judgeNewAuthGroup['newGroup']) {
            // 存在新版权限组
            if (!$bind_id) {
                // 不存在单个人员id，走设备获取对应需要同步的所有
                $planParam = array();
                $planParam['paramType'] = 'deviceAuthGroupBindALlMember';
                $planParam['device_id'] = $device_id;
                $planParam['village_id'] = $village_id;
                if ($orderGroupId) {
                    $planParam['orderGroupId'] = $orderGroupId;
                }
                $unique_id = 'deviceAuthGroupBindALlMember_'.$village_id.'_'.$device_id;
                $planParam['unique_id'] = $unique_id;
                $a5DeviceAuthGroupBindALlMember = D('House_face_device_log')->a5DeviceAuthGroupBindALlMember;
                D('Face_door_service')->add_plan($planParam, 'sub_device_syn', $a5DeviceAuthGroupBindALlMember);
                $logParam = [
                    'log_name' => 'planDeviceAuthGroupBindMember','logType' => 'planA5','log_status' => 16,'orderGroupId' => $orderGroupId,
                    'log_content' => '设备相关权限组绑定人员','device_id' => $device_id,'device_sn' => $device_sn,
                    'device_type' => 5,'village_id' => $village_id,'addTime' => time(),
                ];
                try {
                    D('House_face_device_log')->addLog($logParam);
                } catch( \Exception $e) {
                    // 记录日志报错
                    fdump_api(['日志记录[设备相关权限组绑定人员]出错'.__LINE__.'方法'.__FUNCTION__,$planParam,$logParam,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
                }
            } else {
                if (isset($judgeNewAuthGroup['group_arr']) && $judgeNewAuthGroup['group_arr']) {
                    foreach ($judgeNewAuthGroup['group_arr'] as $val) {
                        $param = array();
                        $param['paramType'] = 'authGroupBindMember';
                        $param['device_id'] = $device_id;
                        $param['village_id'] = $village_id;
                        $param['bind_id'] = $bind_id;
                        $param['group_id'] = $val;
                        if ($orderGroupId) {
                            $param['orderGroupId'] = $orderGroupId;
                        }
                        $param['unique_id'] = 'authGroupBindMember_'.$village_id.'_'.$device_id.'_'.$bind_id;
                        $a5AuthGroupBindMember = D('House_face_device_log')->a5AuthGroupBindMember;
                        D('Face_door_service')->add_plan($param, 'sub_device_syn', $a5AuthGroupBindMember);
                        $logParam = [
                            'log_name' => 'planSingleAuthGroupBindMember','logType' => 'planA5','log_status' => 17,'orderGroupId' => $orderGroupId,
                            'log_content' => '单个权限组绑定人员','device_id' => $device_id,'device_sn' => $device_sn,
                            'bind_id' => $bind_id,'uid' => $uid,'thirdNo' => $val,
                            'device_type' => 5,'village_id' => $village_id,'addTime' => time(),
                        ];
                        try {
                            D('House_face_device_log')->addLog($logParam);
                        } catch( \Exception $e) {
                            // 记录日志报错
                            fdump_api(['日志记录[单个权限组绑定人员]出错'.__LINE__.'方法'.__FUNCTION__,$param,$logParam,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
                        }
                    }
                } else {
                    $param = array();
                    $param['paramType'] = 'authGroupBindMember';
                    $param['device_id'] = $device_id;
                    $param['village_id'] = $village_id;
                    $param['bind_id'] = $bind_id;
                    if ($orderGroupId) {
                        $param['orderGroupId'] = $orderGroupId;
                    }
                    $param['unique_id'] = 'authGroupBindMember_'.$village_id.'_'.$device_id.'_'.$bind_id;
                    $a5AuthGroupBindMember = D('House_face_device_log')->a5AuthGroupBindMember;
                    D('Face_door_service')->add_plan($param, 'sub_device_syn', $a5AuthGroupBindMember);
                    $logParam = [
                        'log_name' => 'planAuthGroupBindMember','logType' => 'planA5','log_status' => 18,'orderGroupId' => $orderGroupId,
                        'log_content' => '设备相关权限组绑定单个人员','device_id' => $device_id,'device_sn' => $device_sn,
                        'bind_id' => $bind_id,'uid' => $uid,
                        'device_type' => 5,'village_id' => $village_id,'addTime' => time(),
                    ];
                    try {
                        D('House_face_device_log')->addLog($logParam);
                    } catch( \Exception $e) {
                        // 记录日志报错
                        fdump_api(['日志记录[设备相关权限组绑定单个人员]出错'.__LINE__.'方法'.__FUNCTION__,$param,$logParam,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
                    }
                }
            }
        }
        if (!$judgeNewAuthGroup || $judgeNewAuthGroup['floorGroup']) {
            // 数据写死，暂不考虑扩展
            $bind_type = 3;
            $device_type = 5;
            if ($bind_id) {
                //单个
                $where['b.village_id']=$village_id;
                $where['b.status']=1;
                $where['d.bind_id']=$bind_id;
                $where['d.device_type']=$device_type;
                $where['d.bind_type']=0;
                $userData = D('Face_user_bind_device')->alias('d')
                    ->join(' LEFT JOIN '.C('DB_PREFIX').'house_village_user_bind as b ON d.bind_id=b.pigcms_id')
                    ->where($where)
                    ->field('d.bind_id,d.id,b.pigcms_id,b.type,b.floor_id')->select();
                if (!empty($userData)) {
                    $userInfo = reset($userData);
                } else {
                    $userInfo =  D('House_village_user_bind')->where(['pigcms_id' => $bind_id])->field('pigcms_id,type,floor_id')->find();
                    $dataArr = [
                        'bind_id'=>$bind_id,
                        'device_type'=>$device_type,
                        'add_time'=>time(),
                    ];
                    $bindDeviceId = D('Face_user_bind_device')->data($dataArr)->add();
                    if ($userInfo&&$bindDeviceId) {
                        $userData = [];
                        $userData[] = [
                            'bind_id' => $bind_id,
                            'id' => $bindDeviceId,
                        ];
                    }
                }
                if ($userInfo['floor_id']>0) {
                    $group_id = D('Face_bind_about_info')->where(['bind_type'=>$bind_type,'device_type'=>$device_type,'bind_id'=>$userInfo['floor_id']])->order('`id` DESC')->getField('group_id');
                }
                if ($userInfo['type']==4 && $device_id) {
                    if (!$group_id) {
                        $floor_ids = $this->getDeviceFloorId($device_id,'',$village_id);
                        if (!$floor_ids) {
                            if (!$device_sn) {
                                $device_sn = D('House_face_device')->where(['device_id' => $device_id])->getField('device_sn');
                            }
                            $group_id = D('Face_bind_device')->where(['bind_type'=>0,'device_id'=>$device_id,'device_sn'=>$device_sn])->order('`id` DESC')->getField('group_id');
                        } else {
                            $group_id = D('Face_bind_about_info')->where(['bind_type'=>$bind_type,'bind_id'=>['in',$floor_ids],'device_type'=>$device_type])->order('`id` DESC')->getField('group_id');
                        }
                    }
                }
                $arr = [];
                $ids = [];
                foreach ($userData as $val)
                {
                    $arr[] = $val['bind_id'];
                    $ids[] = $val['id'];
                }
                if($arr){
                    $info = $this->bindAuthGroupMember($arr,$group_id,$ids,$floor_id);
                    return $info;
                }else{
                    return true;
                }
            }else{
                // 获取相关用户
                if ($floor_id) {
                    $where['_string'] = "(b.floor_id in ({$floor_id}) OR b.type='4')";
                }
                $where['b.village_id']=$village_id;
                $where['b.status']=1;
                $where['d.device_type']=$device_type;
                $where['d.bind_type']=0;
                $where['d.group_id']=['neq',''];
                $userData = D('Face_user_bind_device')->alias('d')
                    ->join(' LEFT JOIN '.C('DB_PREFIX').'house_village_user_bind as b ON d.bind_id=b.pigcms_id')
                    ->where($where)
                    ->field('b.floor_id, d.bind_id, d.id, d.group_id')
                    ->select();
                if($userData){
                    $group_list = [];
                    foreach ($userData as $v) {
                        if (empty($group_list[$v['group_id']])) {
                            $group_list[$v['group_id']] = [];
                            $group_list[$v['group_id']]['arr'] = [];
                            $group_list[$v['group_id']]['ids'] = [];
                            $group_list[$v['group_id']]['bind_id'] = $v['floor_id'];
                        } else{
                            if (empty($group_list[$v['group_id']]['arr'])) {
                                $group_list[$v['group_id']]['arr'] = [];
                            }
                            if (empty($group_list[$v['group_id']]['id'])) {
                                $group_list[$v['group_id']]['ids'] = [];
                            }
                        }
                        $group_list[$v['group_id']]['arr'][] =  $v['bind_id'];
                        $group_list[$v['group_id']]['ids'][] =  $v['id'];
                    }
                    foreach ($group_list as $key=>$val) {
                        if($val['arr'] && $val['ids']){
                            sleep(1);		//1秒
                            $info =  $this->bindAuthGroupMember($val['arr'],$key,$val['ids'],$val['bind_id']);
                        }
                    }
                    return $info;
                }
            }
        }
        return true;
    }

    public function bindUserToGroup($employeeNos, $group_id, $device_sn)
    {
        $url = $this->a5_face_class->bindAuthGroupMember;
        $arr = json_encode([
            "groupId" => $group_id,
            "employeeNos" => $employeeNos,
        ]);
        $info = $this->a5_face_class->curlPost($url, $arr, 1, $this->token);
        if($info['code'] == 200)
        {
            $db_face_bind_device = D('face_bind_device');
            foreach ($employeeNos as $bind_id) {
                $where = [
                    'bind_type' => 61,
                    'bind_id' => $bind_id,
                    'device_sn' => $device_sn,
                    'device_type' =>  5,
                    'group_id' =>  $group_id,
                ];
                $id = $db_face_bind_device->where($where)->getField('id');
                if (! $id) {
                    $data = [
                        'bind_type' => 61,
                        'bind_id' => $bind_id,
                        'device_sn' => $device_sn,
                        'device_type' =>  5,
                        'group_id' =>  $group_id,
                        'add_time' =>  time(),
                        'status' =>  3,
                    ];
                    $db_face_bind_device->add($data);
                }
            }
        }
        return $info;
    }

    /**
     * Notes: 权限组绑定人员
     * @param $postArr
     * @return mixed
     * @author: weili
     * @datetime: 2020/7/22 10:05
     */
    public function bindAuthGroupMember($employeeNos,$group_id,$ids,$floor_id,$repeat=0)
    {
        if (!$group_id) {
            fdump(['权限组ID不能为空'.__LINE__,$employeeNos, $group_id, $ids, $floor_id], 'api/log/'.date('Ymd').'/a5New/errBindAuthGroupMemberLog',true);
            return array (
                'code' => 510001,
                'message' => '权限组ID不能为空',
            );
        }
        $url = $this->a5_face_class->bindAuthGroupMember;
        $arr = json_encode([
            "groupId"=>$group_id,
            "employeeNos"=>$employeeNos,
        ]);
        usleep($this->loopUSleepTime);// 避免超频调用
        $this->keepPlanThread();
        $info = $this->a5_face_class->curlPost($url,$arr,1,$this->token);
        fdump_api(['权限组绑定人员'.__LINE__,$url, $arr,$this->token,$info], 'a5New/bindAuthGroupMemberLog', true);
        if($info['code'] == 200)
        {
            $map['id'] = ['in',$ids];
            D('Face_user_bind_device')->where($map)->data(['group_id'=>$group_id])->save();
            usleep($this->loopUSleepTime);// 避免超频调用
            $this->keepPlanThread();
            $group = $this->getAuthGroup($floor_id);
            fdump(['权限组绑定人员成功'.__LINE__,$employeeNos,$group_id,$ids,$floor_id,$info], 'api/log/' . date('Ymd') . '/a5New/bindAuthGroupMemberLog', true);
            return $group;
        }else{
            if ($repeat==0 && !$info && !$info['code']) {
                // 如果没有反馈 休眠1秒然后重复执行一次
                sleep(1);
                $this->bindAuthGroupMember($employeeNos,$group_id,$ids,$floor_id,1);
            } elseif ($floor_id && 40021001==$info['code']) {
                D('Face_bind_about_info')->where(['bind_type'=>3,'bind_id'=>$floor_id,'device_type'=>5])->delete();
            }
            fdump(['权限组绑定人员失败'.__LINE__, $url,$employeeNos, $group_id, $ids, $floor_id, $info], 'api/log/'.date('Ymd').'/a5New/errBindAuthGroupMemberLog',true);
            return $info;
        }

    }

    /**
     * Notes: 权限组获取人员列表
     * @param $groupId
     * @param int $pageNo
     * @param int $pageSize
     * @return mixed
     * @author: weili
     * @datetime: 2020/7/23 12:37
     */
    public function getAuthGroupMemberList($groupId,$pageNo=1,$pageSize=10)
    {
        $url = $this->a5_face_class->getAuthGroupMemberList;
        $arr = json_encode([
            "pageNo"=> $pageNo,
            "pageSize"=> $pageSize,
            "groupId"=> $groupId
        ]);
        usleep($this->loopUSleepTime);// 避免超频调用
        $this->keepPlanThread();
        $info = $this->a5_face_class->curlPost($url,$arr,1,$this->token);
        fdump(['权限组获取人员列表'.__LINE__,$url,$arr,$this->token, $info], 'api/log/' . date('Ymd') . '/a5New/getAuthGroupMemberListLog', true);
        return $info;
    }
    //外围机 删除设备下对应的楼栋
    public function delDeviceGroup($floor_id,$device_id,$device_sn='',$device_type=5,$bind_type=3)
    {
        if(!$device_sn)
        {
            $device_sn = D('House_face_device')->where(['device_id'=>$device_id])->getField('device_sn');
        }
        $deviceSerials = [$device_sn];
        if(is_array($floor_id)){
            foreach ($floor_id as $val)
            {
                usleep($this->loopUSleepTime);// 避免超频调用
                $this->keepPlanThread();
                $group_id = D('Face_bind_about_info')->where(['bind_type'=>$bind_type,'bind_id'=>$val,'device_type'=>$device_type])->order('`id` DESC')->getField('group_id');
                $res = $this->remDevice($deviceSerials,$group_id);
                fdump(['权限组获取人员列表'.__LINE__,$deviceSerials,$group_id,$res], 'api/log/' . date('Ymd') . '/a5New/delDeviceGroupLog', true);
            }
        }else{
            usleep($this->loopUSleepTime);// 避免超频调用
            $this->keepPlanThread();
            $group_id = D('Face_bind_about_info')->where(['bind_type'=>$bind_type,'bind_id'=>$floor_id,'device_type'=>$device_type])->order('`id` DESC')->getField('group_id');
            $res = $this->remDevice($deviceSerials,$group_id);
            fdump(['权限组获取人员列表'.__LINE__,$deviceSerials,$group_id,$res], 'api/log/' . date('Ymd') . '/a5New/delDeviceGroupLog', true);
        }

        return true;
    }
    /**
     * Notes: 权限组绑定设备
     * @param $employeeNos
     * @param $group_id
     * @param $ids
     * @return mixed
     * @author: weili
     * @datetime: 2020/7/23 11:32
     */
    public function bindAuthGroupEquipment($deviceSns,$group_id,$floor_id,$device_id)
    {
        $map['bind_type'] = 0;
        $map['group_id'] = $group_id;
        if($floor_id)
        {
            $map['bind_id'] = $floor_id;
        }
        $map['device_type'] = ['in',[5,6]];
        $deviceData = D('Face_bind_device')->where($map)->getField('device_sn',true);
        if($deviceData){
            $newData = array_diff([$deviceSns],$deviceData);
            $newData1 = array_diff($deviceData,[$deviceSns]);
            fdump(['权限组绑定设备'.__LINE__,$deviceData,$newData,$newData1,$deviceSns], 'api/log/' . date('Ymd') . '/a5New/bindAuthGroupEquipmentLog', true);
            if($newData && count($newData)>0){
                $deviceSns = array_values($newData);
            }
        }else{
            $deviceSns = D('House_face_device')->where(['device_id'=>$device_id])->getField('device_sn',true);
        }
        if($floor_id){
            $village_id = D('House_face_device')->where(['device_id'=>$device_id])->getField('village_id');
            $wheres['device_type'] = ['in',[5,6]];
            $wheres['device_status'] = 1;
            $wheres['status'] = 1;
            $wheres['device_id'] = ['neq',$device_id];
            $wheres['village_id'] = $village_id;
            $wheres['floor_id'] = -1;
            $wheres['is_del'] = 0;
            $device_sn_arr = D('House_face_device')->where($wheres)->getField('device_sn', true);
            if($device_sn_arr && count($device_sn_arr)>0)
            {
                $deviceSns = array_merge($deviceSns,$device_sn_arr);
            }
            unset($wheres['floor_id']);
            $device_sn_new_arr = D('House_face_device')->where($wheres)->where("find_in_set($floor_id,floor_id)")->getField('device_sn', true);
            if($device_sn_new_arr && count($device_sn_new_arr)>0)
            {
                $deviceSns = array_merge($deviceSns,$device_sn_new_arr);
            }
        }
        fdump_api(['msg' => '00权限组绑定设备'.__LINE__,'$floor_id' => $floor_id,'$deviceSns'=>$deviceSns,'$group_id'=>$group_id], 'a5New/bindAuthGroupEquipmentLog', true);
        if(!$deviceSns || count($deviceSns)<=0){
            return true;
        }
        $url = $this->a5_face_class->bindAuthGroupEquipment;
        $arr = json_encode([
            "groupId"=>$group_id,
            "checkCapability"=>true,
            "deviceSerials"=>$deviceSns,
        ]);
        usleep($this->loopUSleepTime);// 避免超频调用
        $this->keepPlanThread();
        $info = $this->a5_face_class->curlPost($url,$arr,1,$this->token);
        fdump_api(['权限组绑定设备'.__LINE__,$url,$arr,1,$this->token,$info,$deviceSns,$group_id,$floor_id,$device_id],'a5New/bindAuthGroupEquipmentLog',1);
        if($info['code'] == 200)
        {
            foreach ($deviceSns as $val) {
                $data['bind_id']=$floor_id;
                $data['device_sn']=$val;
                $data['group_id']=$group_id;
                $data['info']=serialize($info);
                $data['add_time']=time();

                $wheres = ['bind_type' => 0,'bind_id'=>$floor_id,'device_sn'=>$val,'group_id'=>$group_id];
                $bind_device = D('Face_bind_device')->where($wheres)->find();
                if(!$bind_device){
                    D('Face_bind_device')->data($data)->add();
                }
            }
            fdump(['绑定权限组成功'.__LINE__,$deviceSns,$info], 'api/log/' . date('Ymd') . '/a5New/bindAuthGroupEquipmentSuccessLog', true);
            return $info;
        }elseif ($info['code'] == 40022002){
            fdump(['40022002权限组绑定设备失败'.__LINE__,$url,$arr,1,$this->token,$info], 'api/log/' . date('Ymd') . '/a5New/errBindAuthGroupEquipmentLog', true);
            return true;
        }else{
            $map = [];
            $map['bind_type'] = 0;
            $map['group_id'] = $group_id;
            $map['device_type'] = ['in',[5,6]];
            fdump(['权限组绑定设备失败'.__LINE__,$url,$arr,1,$this->token,$info,$map], 'api/log/' . date('Ymd') . '/a5/errBindAuthGroupEquipmentLog', true);
            if ($info['code']&&$info['code']!=10290002) {
                $deviceData = D('Face_bind_device')->where($map)->delete();
                D('Face_bind_about_info')->where(['group_id'=>$group_id,'device_sn'=>['in',$deviceSns]])->delete();
            }
            return $info;
        }

    }
    public function getCapability($deviceSerial)
    {
        $url = 'https://api2.hik-cloud.com/api/v1/open/accessControl/allots/actions/getCapability?deviceSerial='.$deviceSerial;
        $info = $this->a5_face_class->curlGet($url,$this->token);
        fdump($info, 'api/log/' . date('Ymd') . '/a5New/getCapabilityLog', true);
        return $info;
    }
    /**
     * Notes: 获取权限分组
     * @author: weili
     * @datetime: 2020/7/22 15:16
     */
    public function getAuthGroup($floor_id=0,$village_id=0,$bind_type=3,$device_type=5)
    {
//        $device_type = D('house_face_device')->where(['device_id'=>$device_id])->getField('device_type');
        if(!$floor_id) {
            //多个同步
            $map['a.bind_type'] = $bind_type;
            $map['a.device_type'] = $device_type;
            $map['a.group_id'] = ['neq',''];
            if ($village_id) {
                $map['b.village_id'] = $village_id;
            }
            $data = D('Face_bind_about_info')
                ->join(' as a LEFT JOIN '.C('DB_PREFIX').'house_village_floor as b ON a.bind_id=b.floor_id')
                ->where($map)
                ->group('group_id')
                ->order('`id` DESC')
                ->getField('group_id',true);
            if ($data) {
                foreach ($data as $val) {
                    usleep($this->loopUSleepTime);// 避免超频调用
                    $this->keepPlanThread();
                    //云 根据权限组ID下发权限 将该权限组下所有人员权限下发到每个设备中
                    $info = $this->issuedByGroup($val);
                }
                return $info;
            } else {
                fdump(['获取权限分组失败>>>'.__LINE__,$floor_id,$village_id,$bind_type,$device_type,$data,D('face_bind_about_info')->_sql()],'api/log/'.date('Ymd').'/a5New/errGetAuthGroupLog',true);
                return $data;
            }
        }else{
            //单个
            $group_id = D('Face_bind_about_info')->where(['bind_type'=>$bind_type,'bind_id'=>$floor_id,'device_type'=>$device_type])->order('`id` DESC')->getField('group_id');
            if($group_id)
            {
                usleep($this->loopUSleepTime);// 避免超频调用
                $this->keepPlanThread();
                //云 根据权限组ID下发权限 将该权限组下所有人员权限下发到每个设备中
                $infoGroup = $this->issuedByGroup($group_id);
                fdump(['单个权限组同步>>>'.__LINE__,$group_id,$infoGroup],'api/log/'.date('Ymd').'/a5New/getAuthGroupLog',true);
                return $infoGroup;
            } else {
                return $group_id;
            }
        }
    }
    /**
     * Notes: 根据权限组ID下发权限
     * @return mixed
     * @author: weili
     * @datetime: 2020/7/22 12:45
     */
    public function issuedByGroup($group_id)
    {
        D('Face_bind_device')->where(['group_id'=>$group_id])->data(['status'=>3])->save();
        $url = $this->a5_face_class->issuedByGroup;
        $postArr = json_encode([
            'groupId'=>$group_id
        ]);
        $info = $this->a5_face_class->curlPost($url,$postArr,1,$this->token);
        if($info['code'] == 200)
        {
            fdump(['根据权限组ID下发权限成功>>'.__LINE__,$group_id,$url,$postArr,$this->token,$info],'api/log/'.date('Ymd').'/a5New/issuedByGroupLog',true);
            return $info;
        }else{
            $info['message'] = $info['message'].$group_id;
            if ($info['code'] == 40021005) {
                fdump(['group_id' => $group_id, 'info' => $info],'api/log/noUserDataGroup',1);
            }
            fdump(['根据权限组ID下发权限失败>>'.__LINE__,$group_id,$url,$postArr,$this->token,$info],'api/log/'.date('Ymd').'/a5New/errIssuedByGroupLog',true);
            return $info;
        }
    }

    /**
     * Notes:根据权限组/设备/人员查询同步失败状态
     * @param $post
     * @return mixed
     * @author: weili
     * @datetime: 2020/7/22 12:50
     */
    public function searchStatus($groupId,$deviceSerial,$employeeNo,$pageNo=1,$pageSize=10)
    {
        $url = $this->a5_face_class->searchStatus;
        $postArr = json_encode([
            "pageNo"=> $pageNo,
            "pageSize"=> $pageSize,
            "groupId"=> $groupId,
            "deviceSerial"=> $deviceSerial,
            "employeeNo"=> $employeeNo
        ]);
        usleep($this->loopUSleepTime);// 避免超频调用
        $this->keepPlanThread();
        $info = $this->a5_face_class->curlPost($url,$postArr,1,$this->token);
        fdump(['根据权限组/设备/人员查询同步失败状态>>'.__LINE__,$url,$postArr,$this->token,$info],'api/log/'.date('Ymd').'/a5New/searchStatusLog',true);
        return $info;
    }

    /**
     * Notes: 权限组获取设备
     * @param $groupId
     * @param int $pageNo
     * @param int $pageSize
     * @author: weili
     * @datetime: 2020/7/23 11:26
     */
    public function getAuthGroupEquipmentList($groupId,$pageNo=1,$pageSize=10)
    {
        $url = $this->a5_face_class->getAuthGroupEquipmentList;
        $arr = json_encode([
            "pageNo"=>$pageNo,
            "pageSize"=> $pageSize,
            "groupId"=> $groupId
        ]);
        usleep($this->loopUSleepTime);// 避免超频调用
        $this->keepPlanThread();
        $info = $this->a5_face_class->curlPost($url,$arr,1,$this->token);
        return $info;
    }
    /**
     * Notes: 转换base64
     * @param string $img
     * @param bool $imgHtmlCode
     * @return string
     * @author: weili
     * @datetime: 2020/7/21 19:09
     */
    public function imgtobase64($img='', $postArr=[])
    {
        fdump_api(['base64图片转译前'.__LINE__,$img,$postArr],'imgtobase64',1);
        $img_content = file_get_contents($img);
        file_put_contents("./runtime/a5New.jpg", $img_content);
        return base64_encode($img_content);
    }

    /**
     * Notes: 获取设备信息 包含状态
     * @author: weili
     * @datetime: 2020/7/23 17:16
     */
    public function getEquipmentInfo($device_sn)
    {
        $url = $this->a5_face_class->getEquipmentInfo;
        $url = $url.'?deviceSerial='.$device_sn;
        $info = $this->a5_face_class->curlGet($url,$this->token);
        $device_id= D('House_face_device')->where(['device_sn'=>$device_sn])->getField('device_id');
		fdump([__LINE__, ['url'=>$url,'token'=>$this->token,'ret'=>$info,'device_id'=>$device_id]],'api/log/'.date('Ymd').'/a5/succGetEquipmentInfoLog',true);
        if($info['code'] == 200)
        {
            // 获取时候直接变更对应设备在线状态
            $device_status = isset($info['data']['deviceStatus'])&&$info['data']['deviceStatus']==1? 1 : 2;
            D('House_face_device')->where(['device_id'=>$device_id])->data(['device_status' => $device_status])->save();
            if (1==$device_status) {
                // 故障解除 A5/A6
                fdump(['故障解除'.__LINE__, $device_id,$info],'api/log/'.date('Ymd').'/a5/succGetEquipmentInfoLog',true);
                D('House_maintenan')->normalLog(1,$device_id);
            } else {
                // 故障通知 A5/A6
                fdump(['故障通知'.__LINE__, $device_id,$info],'api/log/'.date('Ymd').'/a5/succGetEquipmentInfoLog',true);
                D('House_maintenan')->faultLog(1,$device_id,'设备离线');
            }
            fdump(['获取设备信息'.__LINE__, $device_id,$url,$this->token,$info],'api/log/'.date('Ymd').'/a5/succGetEquipmentInfoLog',true);
            return $info['data'];
        }else{
            fdump(['url'=>$url,'token'=>$this->token,'ret'=>$info], 'api/log/' . date('Ymd') . '/face/face_a5_get_equipment_info_err_log', true);
            return $info;
        }
    }

    /**
     * Notes: 获取组下所有设备 （这里的组不是权限组）
     * @param $groupNo
     * @param int $pageNo
     * @param int $pageSize
     * @return mixed
     * @author: weili
     * @datetime: 2020/7/23 17:54
     */
    public function getEquipmentAll($groupNo,$pageNo=1,$pageSize=50)
    {
        $url = $this->a5_face_class->getEquipmentAll;
        $url = $url.'?groupNo='.$groupNo.'&pageNo='.$pageNo.'&pageSize='.$pageSize;
        $info = $this->a5_face_class->curlGet($url,$this->token);
        if($info['code'] == 200)
        {
            return $info['data'];
        }else{
            fdump(['获取组下所有设备[失败]>>'.__LINE__, $url,$this->token,$info],'api/log/'.date('Ymd').'/a5New/errGetEquipmentAllLog',true);
            return $info;
        }
    }

    /**
     * Notes: 权限组解除成员
     * @param $employeeNos
     * @param $groupId
     * @return mixed
     * @author: weili
     * @datetime: 2020/7/24 9:58
     */
    public function remMember($employeeNos,$groupId){
        $url = $this->a5_face_class->removeMember;
        $arr = json_encode([
            "groupId"=> $groupId,
            "employeeNos"=> $employeeNos
        ]);
        fdump_api(['权限组解除成员'.__LINE__,$employeeNos,$groupId,$url,$arr,$this->token],'a5New/remMemberLog',1);
        usleep($this->loopUSleepTime);// 避免超频调用
        $this->keepPlanThread();
        $info = $this->a5_face_class->curlPost($url,$arr,1,$this->token);
        fdump_api(['权限组解除成员1'.__LINE__,$info],'a5New/remMemberLog',1);
        if($info['code'] == 200)
        {
            // 清除对应用户组
            D('Face_user_bind_device')->where(['group_id'=>$groupId,'bind_id'=>['in', $employeeNos]])->data(['group_id'=>''])->save();
            // 删除对应用户卡号
            $where_repeat = [
                'bind_type' => 0,
                'bind_id' => ['in',$employeeNos],
                'device_type' => 5,
            ];
            $db_face_user_bind_device = D('Face_user_bind_device');
            $repeat = $db_face_user_bind_device->where($where_repeat)->field('bind_id,card_status,cardid,card_json')->select();
            if ($repeat) {
                foreach ($repeat as $card) {
                    if ($card && $card['cardid'] && $card['card_status']==1) {
                        $pigcms_id = D('House_village_user_bind')->where(array('ic_card' => $card['cardid'],'pigcms_id' => $card['bind_id']))->getField('pigcms_id');
                        $other_pigcms_id = D('House_village_user_bind')->where(array('ic_card' => $card['cardid'],'pigcms_id' => ['neq', $card['bind_id']]))->getField('pigcms_id');
                        // 确认解除的是本人的卡号没有被其他人绑定去 删除卡片
                        if (!$pigcms_id&&!$other_pigcms_id) {
                            $this->deletePersonCards($card['bind_id'],$card['cardid'],0,5);
                        }
                    }
                }
            }
            return $info['data'];
        }else{
            fdump_api(['权限组解除成员1'.__LINE__,$url,$arr,$this->token,$info],'a5New/errRemMemberLog',1);
            return false;
        }
    }

    /**
     * Notes: 权限组解绑设备
     * @param $deviceSerials
     * @param $groupId
     * @return bool|mixed
     * @author: weili
     * @datetime: 2020/7/24 13:31
     */
    public function remDevice($deviceSerials,$groupId,$id='',$otherParam=[],$logParam=[]){
        $url = $this->a5_face_class->removeDevices;
        $arr = json_encode([
            "groupId"=> $groupId,
            "deviceSerials"=> $deviceSerials
        ]);
        usleep($this->loopUSleepTime);// 避免超频调用
        $this->keepPlanThread();
        $info = $this->a5_face_class->curlPost($url,$arr,1,$this->token);

        if($info['code'] == 200)
        {
            $logParam['log_status'] = 61;
            $logParam['log_content'] = '设备解绑权限组成功';
            $logParam['isDel'] = 1;
            try {
                D('House_face_device_log')->addLog($logParam);
            } catch( \Exception $e) {
                // 记录日志报错
                fdump_api(['日志记录[设备解绑权限组成功]出错'.__LINE__.'方法'.__FUNCTION__,$logParam,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
            }
            if($id)
            {
                usleep($this->loopUSleepTime);// 避免超频调用
                $this->keepPlanThread();
                $this->outMachine($deviceSerials[0],$otherParam,$logParam);
                D('Face_bind_device')->where(['id'=>$id])->delete();
            }
            return $info;
        }else{
            $logParam['log_status'] = 62;
            $logParam['log_content'] = '设备解绑权限组失败';
            $logParam['isDel'] = 0;
            $logParam['toStatus'] = 3;
            $logParam['errCode'] = $info['code'];
            if (isset($info['message'])) {
                $logParam['errReason'] = $info['message'];
            } else {
                $logParam['errReason'] = '设备解绑权限组失败';
            }
            try {
                D('House_face_device_log')->addLog($logParam);
            } catch( \Exception $e) {
                // 记录日志报错
                fdump_api(['日志记录[设备解绑权限组失败]出错'.__LINE__.'方法'.__FUNCTION__,$logParam,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
            }
            fdump_api(['权限组解绑设备[失败]'.__LINE__,$deviceSerials,$groupId,$id,$url,$arr,$this->token,$info],'a5New/errRemDeviceLog',1);
            return false;
        }
    }
    /**
     * Notes: 删除云设备成员
     * @param $employeeNo
     * @return mixed
     * @author: weili
     * @datetime: 2020/7/24 9:09
     */
    public function deleteMember($employeeNo,$cardNos=[])
    {
        $url = $this->a5_face_class->deleteMember;
        $url = $url.'?employeeNo='.$employeeNo;
        usleep($this->loopUSleepTime);// 避免超频调用
        $this->keepPlanThread();
        $info = $this->a5_face_class->curlPost($url,[],1,$this->token);
        if($info['code'] == 200)
        {
            //删除人员同时删除IC卡片，避免卡片还能开门
            if($cardNos && count($cardNos)>0)
            {
                $this->delCard($cardNos);
            }
            return $info;
        }else{
            fdump_api(['删除云设备成员[失败]'.__LINE__,$url,$this->token,$info],'a5New/errDeleteMemberLog',1);
            return $info;
        }
    }
    /**
     * Notes: 删除成员卡片
     * @param  array $cardNos  ["123","c01"]卡号数组
     * @return mixed
     * @author: weili
     * @datetime: 2020/7/24 9:09
     */
    public function delCard($cardNos)
    {
        $url = $this->a5_face_class->delCard;
        $arr = json_encode([
            "cardNos"=> $cardNos
        ]);
        usleep($this->loopUSleepTime);// 避免超频调用
        $this->keepPlanThread();
        $info = $this->a5_face_class->curlPost($url,$arr,1,$this->token);
        if($info['code'] == 200)
        {
            return $info['data'];
        }else{
            fdump_api(['删除成员卡片[失败]'.__LINE__,$url,$this->token,$info],'a5New/errDelCardLog',1);
            return $info;
        }
    }

    /**
     * Notes: 远程开门
     * @param $device_id
     * @return mixed
     * @author: weili
     * @datetime: 2020/7/24 14:41
     */
    public function remoteOpen($device_sn){
        $url = $this->a5_face_class->remoteOpen;
        $arr = json_encode([
            "deviceSerial"=> $device_sn
        ]);
        $info = $this->a5_face_class->curlPost($url,$arr,1,$this->token);
        if($info['code'] == 200)
        {
            return $info;
        }else{
            $res = $this->remoteOpenOld($device_sn);
            fdump(['远程开门失败>>'.__LINE__,$url,$arr,$info,$res,$this->token],'api/log/'.date('Ymd').'/a5New/errRemoteOpenLog',true);
            if($res){
                return true;
            }else{
                return false;
            }
        }
    }
    //老版本远程开门
    public function remoteOpenOld($device_sn){
        $url = 'https://api2.hik-cloud.com/api/v1/community/access/actions/remoteControl';
//        $arr = json_encode([
//            "deviceSerial"=> $device_sn,
//            "cmd"=>"open"
//        ]);
        $arr = "deviceSerial=".$device_sn."&cmd=open";
        $info = $this->a5_face_class->curlPost($url,$arr,0,$this->token);
        if($info['code'] == 200)
        {
            return $info;
        }else{
            fdump(['老版本远程开门>>'.__LINE__,$url,$arr,$info,$this->token],'api/log/'.date('Ymd').'/a5New/errRemoteOpenOldLog',true);
            return false;
        }
    }
    /**
     * Notes: 创建消费者
     * @return mixed
     * @author: weili
     * @datetime: 2020/7/27 9:15
     */
    public function createConsumers()
    {
        $url = $this->a5_face_class->createConsumers;
        $arr = [
            'consumerName'=>'group1'
        ];
        $arrData = $this->a5_face_class->getEncryptKey($arr);
        usleep($this->loopUSleepTime);// 避免超频调用
        $this->keepPlanThread();
        $info = $this->a5_face_class->curlPost($url,$arrData,0,$this->token);
        if($info['code'] == 200)
        {
            return $info['data'];
        }else{
            fdump(['创建消费者失败>>'.__LINE__,$url,$arr,$arrData,$info],'api/log/'.date('Ymd').'/a5New/errCreateConsumersLog',true);
            return $info;
        }
    }

    /**
     * Notes: 消费信息
     * @return mixed
     * @author: weili
     * @datetime: 2020/7/27 9:28
     */
    public function getMassage($consumerId)
    {
        $url = $this->a5_face_class->msgData;
        $arr = [
            'consumerId'=>$consumerId,
            'autoCommit'=> true,
        ];

        $arrData = $this->a5_face_class->getEncryptKey($arr);
        usleep($this->loopUSleepTime);// 避免超频调用
        $this->keepPlanThread();
        $info = $this->a5_face_class->curlPost($url,$arrData,0,$this->token);
        if ($info['code'] == 200 && empty($info['data'])) {
            sleep(2);
            $this->keepPlanThread();
            $info = $this->a5_face_class->curlPost($url,$arrData,0,$this->token);
        }
        if($info['code'] == 200)
        {
            return $info['data'];
        }else{
            fdump(['消费信息失败>>'.__LINE__,$url,$arr,$arrData,$info],'api/log/'.date('Ymd').'/a5New/errGetMassageLog',true);
            return $info;
        }
    }

    /**
     * Notes: 获取设备下的通道列表
     * @return mixed
     * @author: weili
     * @datetime: 2020/7/27 10:02
     */
    public function getChannelList($deviceSerial,$pageNo=1,$pageSize=50)
    {
        $url = $this->a5_face_class->geChannelList;
        $url = $url.'?deviceSerial='.$deviceSerial.'&pageNo='.$pageNo.'&pageSize='.$pageSize;
        $info = $this->a5_face_class->curlGet($url,$this->token);
        fdump(['获取设备下的通道列表>>'.__LINE__,$info,$url,$info],'api/log/'.date('Ymd').'/a5New/getChannelListLog',true);
        return $info;
    }
    /**
     * Notes: 设备绑定通道
     * @return mixed
     * @author: weili
     * @datetime: 2020/7/27 10:02
     */
    public function bindChannel($deviceSerial)
    {

        $url = $this->a5_face_class->bindChannel;
        $arr = json_encode([
            "deviceSerial"=> $deviceSerial
        ]);
        usleep($this->loopUSleepTime);// 避免超频调用
        $this->keepPlanThread();
        $info = $this->a5_face_class->curlPost($url,$arr,1,$this->token);
        if($info['code'] == 200)
        {
            fdump(['设备绑定通道>>'.__LINE__,$url,$arr,$info],'api/log/'.date('Ymd').'/a5New/bindChannelLog',true);
            return $info['data'];
        }else{
            fdump(['设备绑定通道>>'.__LINE__,$url,$arr,$info],'api/log/'.date('Ymd').'/a5New/errBindChannelLog',true);
            return $info;
        }
    }
    //
    //设置可视对讲通知地址
    public function setNotifyUrl($notifyUrl)
    {
        $url = $this->a5_face_class->setNotifyUrl;
        $arr = json_encode(["notifyUrl"=> $notifyUrl]);
        usleep($this->loopUSleepTime);// 避免超频调用
        $this->keepPlanThread();
        $info = $this->a5_face_class->curlPost($url,$arr,1,$this->token);
        if($info['code'] == 200)
        {
            fdump(['设置可视对讲通知地址'.__LINE__,$url, $arr, $info], 'api/log/' . date('Ymd') . '/a5New/setNotifyUrlSuccessLog', true);
            return $info;
        }else{
            fdump(['设置可视对讲通知地址失败'.__LINE__,$url, $arr, $info], 'api/log/' . date('Ymd') . '/a5New/errSetNotifyUrlLog', true);
            return $info;
        }
    }
    //获取可视对讲通知地址
    public function getNotifyUrl()
    {
        $url = $this->a5_face_class->getNotifyUrl;
        $info = $this->a5_face_class->curlGet($url,$this->token);
        if($info['code'] == 200)
        {
            fdump(['获取可视对讲通知地址'.__LINE__,$url,$info], 'api/log/' . date('Ymd') . '/a5New/getNotifyUrlSuccessLog', true);
            return $info['data'];
        }else{
            fdump(['获取可视对讲通知地址失败'.__LINE__,$url, $info], 'api/log/' . date('Ymd') . '/a5New/errGetNotifyUrlLog', true);
            return $info;
        }
    }
    //关闭视频加密
    public function setVideoOff($deviceSerial,$validateCode)
    {
        $url = $this->a5_face_class->setOff;
        $arr = json_encode([
            "deviceSerial"=> $deviceSerial,
            "validateCode"=> $validateCode,
        ]);
        $info = $this->a5_face_class->curlPost($url,$arr,1,$this->token);
        if($info['code'] == 200)
        {
            fdump(['关闭视频加密'.__LINE__,$url,$info], 'api/log/' . date('Ymd') . '/a5New/setVideoOffSuccessLog', true);
            return true;
        }else{
            fdump(['关闭视频加密失败'.__LINE__,$url, $info], 'api/log/' . date('Ymd') . '/a5New/errSetVideoOffLog', true);
            return false;
        }
    }
    //开启视频加密
    public function setVideoOn($deviceSerial)
    {
        $url = $this->a5_face_class->setOn;
        $arr = json_encode([
            "deviceSerial"=> $deviceSerial,
        ]);
        $info = $this->a5_face_class->curlPost($url,$arr,1,$this->token);
        if($info['code'] == 200)
        {
            fdump(['开启视频加密'.__LINE__,$url,$info], 'api/log/' . date('Ymd') . '/a5New/setVideoOnSuccessLog', true);
            return true;
        }else{
            fdump(['开启视频加密失败'.__LINE__,$url, $info], 'api/log/' . date('Ymd') . '/a5New/errSetVideoOnLog', true);
            return false;
        }
    }
    //获取视频取流时需要的认证信息
    public function getApprove()
    {
        $url = $this->a5_face_class->getApprove;
        $info = $this->a5_face_class->curlGet($url,$this->token);
        fdump(['获取视频取流时需要的认证信息'.__LINE__,$url,$info], 'api/log/' . date('Ymd') . '/a5New/getApproveLog', true);
        return $info;
    }
    //获取可视对讲对应参数
    public function getVisualParam($deviceSerial,$uid)
    {
        $map = [];
        $map['device_sn'] = $deviceSerial;
        $map['device_status'] = 1;
        $map['device_type'] = 5;
        $map['is_del'] = 0;
        $device_info = D('House_face_device')->where($map)->field('device_id,village_id,floor_id,public_area_id,device_name,device_sn,thirdProtocol,cloud_code')->find();

        fdump_api(['获取视频通道号：'.__LINE__,'$device_info' => $device_info],'get_channel',1);
        if (isset($device_info['thirdProtocol']) && intval($device_info['thirdProtocol']) == 12) {
            // 内部应用走内部应用获取 \app\community\model\service\Device\DeviceHkNeiBuHandleService::getVisualParam
            $models = '\app\community\model\service\Device\DeviceHkNeiBuHandleService/getVisualParam';
            $device_id = isset($device_info['device_id']) ? intval($device_info['device_id']) : 0;
            fdump_api(['获取视频通道号：'.__LINE__,'device_id' => $device_id, 'deviceSerial' => $deviceSerial, 'uid' => $uid],'get_channel',1);
            $result = invoke_v20_service($models, ['device_id' => $device_id, 'deviceSerial' => $deviceSerial, 'uid' => $uid]);
            fdump_api(['获取视频通道号：'.__LINE__,'$result' => $result],'get_channel',1);
            $result = $result['data']['retval'];
            return $result;
        }
        //设备通道号
        $info = $this->getChannelList($deviceSerial);
        $channelNo = '';
        if($info['code'] == 200){
            if($info['data']['rows']) {
                foreach ($info['data']['rows'] as $val) {
                    if ($val['isUse'] == 1 && $val['channelType'] == 10300) {
                        $channelNo = $val['channelNo'];
                    }
                }
            }
        }
        //获取access_token
        $access_token = $this->access_token;
        $arr = [
            'oauthToken'=>$access_token,
            'deviceSerial'=>$deviceSerial,
            'channelNo'=>$channelNo,
        ];
        //获取取流认证信息
        $approve_info = $this->getApprove();
        if($approve_info['code'] == 200){
            $arr['approve'] = $approve_info['data'];
        }
        if($device_info['floor_id'] && $device_info['floor_id'] != -1){
            if(strstr($device_info['floor_id'],',') !== false){
                $where_bind = [
                    'uid'=>$uid,
                    'village_id'=>$device_info['village_id'],
                    'type'=>['in',[0,3]]
                ];
                $where_bind['_string'] = "(floor_id in ({$device_info['floor_id']}))";
                $user_info = D('House_village_user_bind')->where($where_bind)->find();
                $floor_where['floor_id'] = $user_info['floor_id'];
                $user_bind_where['floor_id'] = $user_info['floor_id'];
            }else{
                $floor_where['floor_id'] = $device_info['floor_id'];
                $user_bind_where['floor_id'] = $device_info['floor_id'];
            }
            $floor_info = D('House_village_floor')->where($floor_where)->field('single_id,floor_name')->find();
            $user_bind_where['single_id'] = $floor_info['single_id'];
            $single_info = D('House_village_single')->where(['id'=>$floor_info['single_id']])->field('id,single_name')->find();
            if(!preg_match('/[\x7f-\xff]/', $single_info['single_name'])){
                $single_name = $single_info['single_name'].(D('House_village')->getHouseAlias('single_name')?:'栋');
            }else{
                $single_name = $single_info['single_name'];
            }
            if(!preg_match('/[\x7f-\xff]/', $floor_info['floor_name'])){
                $floor_name = $floor_info['floor_name'].'单元';
            }else{
                $floor_name = $floor_info['floor_name'];
            }
            $name = $single_name.$floor_name;
        }else{
            $name = D('House_village_public_area')->where(['public_area_id'=>$device_info['public_area_id']])->getField('public_area_name');
        }
        $arr['cloud_code'] = $device_info['cloud_code'];
        $arr['village_title'] = $name;
        $arr['web_name'] = C('config.site_name');
        return $arr;
    }
    //视频接口 推给前端
    public function sendPush($data)
    {
        $deviceSerial = $data['deviceSerial'];//设备序列号
        $dateTime = strtotime($data['dateTime']);//消息时间
        $cmdType = $data['cmdType'];//设备序列号
        $periodNumber = $data['periodNumber'];//期号
        $single = $data['buildingNumber'];//楼号 (可能没有)
        $floor = $data['unitNumber'];//单元号 （可能没有）
        $layer = (int)$data['floorNumber'];//层号
        $roomNumber = (int)$data['roomNumber'];//房间号
        $unitType = $data['unitType'];//类型: outdoor门口机，wall围墙机

        //海康会推送呼叫（request）、接听（cancel）、挂断（hangUp）。如果不是呼叫，则不继续通知，原来挂断的也会呼叫导致BUG。
        fdump_api(['视频推送用户端：'.__LINE__,$data],'a5SendPush/sendPushLog',1);
        if($cmdType != 'request'){
            return false;
        }

        $user_bind_where = [];
        $device_info = D('House_face_device')->where(['device_sn'=>$deviceSerial])->field('device_id,floor_id,village_id,public_area_id')->find();
        if($device_info['floor_id'] && $device_info['floor_id'] != -1 && $device_info['floor_id'] != 0){
            if(strstr($device_info['floor_id'],',') == false) {
                $floor_where['floor_id'] = $device_info['floor_id'];
            }else{
                if($single) {
                    if (strlen($single)<2) {
                        $single_number = str_pad($single,2,"0",STR_PAD_LEFT);
                    } else {
                        $single_number = $single;
                    }
                    $single_where['village_id'] = $device_info['village_id'];
                    $single_where['status'] = 1;
                    $single_where['platform'] = 'swap';
                    $sing['single_name'] = $single;
                    $sing['single_number'] =  $single_number;
                    $sing['_logic'] = 'or';
                    $single_where['_complex'] = $sing;
                    $single_id = D('House_village_single')->where($single_where)->getField('id');
                    fdump_api(['查询到的信息0：'.__LINE__,$single_id,D('House_village_single')->_sql()],'a5SendPush/sendPushLog',1);
                }else{
                    fdump_api(['视频推送用户端错误：'.__LINE__,$data],'a5SendPush/errSendPushLog',1);
                    return false;
                }
                if (strlen($floor)<2) {
                    $floor_number = str_pad($floor,2,"0",STR_PAD_LEFT);
                } else {
                    $floor_number = $floor;
                }
                $floor_arr = explode(',',$device_info['floor_id']);
                $floor_where['floor_id'] = ['in',$floor_arr];
                $floor_where['single_id'] = $single_id;
                $floor_where['village_id'] = $device_info['village_id'];
                $floor_where['status'] = 1;
                $floor_where['platform'] = 'swap';
                $flo['floor_name'] = $floor;
                $flo['floor_number'] = $floor_number;
                $flo['_logic'] = 'or';
                $floor_where['_complex'] = $flo;
            }
            $floor_info = D('House_village_floor')->where($floor_where)->field('floor_id,single_id,floor_name')->find();
            $floor_id = $floor_info['floor_id'];
            fdump_api(['查询到的信息0-1：'.__LINE__,$floor_info,$device_info,D('House_village_floor')->_sql()],'a5SendPush/sendPushLog',1);
            $user_bind_where['single_id'] = $floor_info['single_id'];
            if($floor_id){
                $user_bind_where['floor_id'] = $floor_id;
            } elseif ($device_info['floor_id']) {
                $user_bind_where['floor_id'] = $device_info['floor_id'];
            }
            $single_info = D('House_village_single')->where(['id'=>$floor_info['single_id']])->field('id,single_name')->find();
            if(!preg_match('/[\x7f-\xff]/', $single_info['single_name'])){
                $single_name = $single_info['single_name'].(D('House_village')->getHouseAlias('single_name')?:'栋');
            }else{
                $single_name = $single_info['single_name'];
            }
            if(!preg_match('/[\x7f-\xff]/', $floor_info['floor_name'])){
                $floor_name = $floor_info['floor_name'].'单元';
            }else{
                $floor_name = $floor_info['floor_name'];
            }
            $name = $single_name.$floor_name;
        }else{
            if($single){
                if (strlen($single)<2) {
                    $single_number = str_pad($single,2,"0",STR_PAD_LEFT);
                } else {
                    $single_number = $single;
                }
                $single_where['village_id'] = $device_info['village_id'];
                $single_where['status'] = 1;
                $single_where['platform'] = 'swap';
                $sing['single_name'] = $single;
                $sing['single_number'] = $single_number;
                $sing['_logic'] = 'or';
                $single_where['_complex'] = $sing;
                $single_id = D('House_village_single')->where($single_where)->getField('id');
                if($single_id){
                    $user_bind_where['single_id'] = $single_id;
                }
            }
            if($floor){
                if (strlen($floor)<2) {
                    $floor_number = str_pad($floor,2,"0",STR_PAD_LEFT);
                } else {
                    $floor_number = $floor;
                }
                $floor_where['village_id'] = $device_info['village_id'];
                $floor_where['status'] = 1;
                $floor_where['platform'] = 'swap';
                if($user_bind_where['single_id']){
                    $floor_where['single_id'] = $user_bind_where['single_id'];
                }
                $flo['floor_name'] = $floor;
                $flo['floor_number'] = $floor_number;
                $flo['_logic'] = 'or';
                $floor_where['_complex'] = $flo;
                $floor_id = D('house_village_floor')->where($floor_where)->getField('floor_id');
                if($floor_id){
                    $user_bind_where['floor_id'] = $floor_id;
                }
            }
            $name = D('House_village_public_area')->where(['public_area_id'=>$device_info['public_area_id']])->getField('public_area_name');
        }
        if (strlen($layer)<2) {
            $layer_number = str_pad($layer,2,"0",STR_PAD_LEFT);
        } else {
            $layer_number = $layer;
        }
        fdump_api(['查询到的信息1：'.__LINE__,$device_info,$name,$layer, $layer_number, $user_bind_where,D('house_village_floor')->_sql()],'a5SendPush/sendPushLog',1);
        //查楼层
        $layer_where = $user_bind_where;
        $layer_where['village_id'] = $device_info['village_id'];
        $layer_where['status'] = 1;
        $layer_where['platform'] = 'swap';
        $lay['layer_number'] = $layer;
        $lay['layer_name'] = $layer_number;
        $lay['_logic'] = 'or';
        $layer_where['_complex'] = $lay;
        $layer_id = D('House_village_layer')->where($layer_where)->order('id desc')->getField('id');

        //查询房间
        $vacancy_where = $user_bind_where;
        $vacancy_where['village_id'] = $device_info['village_id'];
        $vacancy_where['status'] = ['in',[1,3]];
        $vacancy_where['is_del'] = ['neq',1];
        $vacancy_where['layer_id'] = $layer_id;
        $vacancy_where['platform'] = 'swap';
        $vac['room_number'] = $roomNumber;
        $vac['room'] = ['like','%'.$roomNumber];
        $vac['_logic'] = 'or';
        $vacancy_where['_complex'] = $vac;
        $vacancy_id = D('House_village_user_vacancy')->where($vacancy_where)->order('pigcms_id desc')->getField('pigcms_id');


        fdump_api(['查询到的信息2：'.__LINE__,$layer_id,D('House_village_layer')->_sql(),$vacancy_id, D('House_village_user_vacancy')->_sql()],'a5SendPush/sendPushLog',1);

        $user_bind_where['village_id'] = $device_info['village_id'];
        $user_bind_where['status'] = 1;
        $user_bind_where['layer_id'] = $layer_id;
        $user_bind_where['vacancy_id'] = $vacancy_id;
        // 当前推送对象 只是包含 业主 家属 租客 更新业主
        $user_bind_where['type'] = ['in', [0,1,2,3]];
        $user_bind_where['uid'] = ['neq',0];
        $user_bind_where['name'] = ['neq',''];
        $user_bind_where['phone'] = ['neq',''];
        $owner_info_list = D('House_village_user_bind')->where($user_bind_where)->order('type ASC')->select();

        //设备通道号
        $info = $this->getChannelList($deviceSerial);
        $channelNo = '';
        if($info['code'] == 200){
            if($info['data']['rows']) {
                foreach ($info['data']['rows'] as $val) {
                    if ($val['isUse'] == 1 && $val['channelType'] == 10300) {
                        $channelNo = $val['channelNo'];
                    }
                }
            }
        }
        fdump_api(['查询到的信息3：'.__LINE__,$owner_info_list,D('House_village_user_bind')->_sql()],'a5SendPush/sendPushLog',1);
        if($owner_info_list){
            $user_set = M('User_set');
            foreach($owner_info_list as $owner_info){
                $user_info = D('User')->where(['uid'=>$owner_info['uid']])->field('device_id,client,jpush_registrationId,openid')->find();
                if(empty($user_info['device_id'])){
                    fdump_api(['循环错误1：'.__LINE__,$user_info,$owner_info],'face/sendPushErrLog',1);
                    continue;
                }
                $info = $user_set->where(['uid' => $owner_info['uid']])->find();
                if(!empty($info) && $info['allow_cloud_calls']==2){
                    fdump_api(['用户设置了不允许推送：'.__LINE__,$user_info,$owner_info,$info],'face/sendPushErrLog',1);
                    continue;
                }

                $tal_device_id = D("Appapi_app_login_log")->where(array('uid' => $owner_info['uid'],'client'=>['neq',0]))->order('pigcms_id desc')->getField('device_id');
                $uid = D("Appapi_app_login_log")->where(array('device_id' => $tal_device_id,'client'=>['neq',0]))->order('pigcms_id desc')->getField('uid');

                fdump_api(['循环1：'.__LINE__,$tal_device_id,$uid, $owner_info['uid']],'a5SendPush/sendPushLog',1);
                if($uid != $owner_info['uid']){
                    fdump_api(['循环错误2：'.__LINE__,$tal_device_id,$uid, $owner_info['uid'], $owner_info],'a5SendPush/sendPushErrLog',1);
                    continue;
                }
                if($user_info){
                    //极光推送给业主用户
                    $new_arr = [
                        'device_sn'=>$deviceSerial,
                        'dateTime'=>$dateTime,
                        'cmdType'=>$cmdType,
                        'uid'=>$owner_info['uid'],
                        'url'=>'',
                        'bind_id'=>$owner_info['pigcms_id'],
                        'title'=>'智慧社区',
                        'msg'=>'您的好友正在通过'.$name.'的门禁机邀请您视频，请点击进行视频沟通',
                        'periodNumber'=>$periodNumber,
                        'buildingNumber'=>$single,
                        'unitNumber'=>$floor,
                        'floorNumber'=>$layer,
                        'roomNumber'=>$layer.'0'.$roomNumber,
                        'unitType'=>$unitType,
                        'channelNo'=>$channelNo,
                        'device_id'=>$device_info['device_id'],
                        'pigcms_id'=>$owner_info['pigcms_id'],
                    ];
                    fdump_api(['循环3：'.__LINE__,$new_arr],'a5SendPush/sendPushLog',1);
                    $cloud_call_bell = $info['cloud_call_bell'];
                    if ($cloud_call_bell) {
                        $new_arr['voice_mp3'] = replace_file_domain($info['cloud_call_bell']);
                    } elseif (C('config.house_a5_cloud_bell')) {
                        $new_arr['voice_mp3'] = replace_file_domain(C('config.house_a5_cloud_bell'));
                    } else {
                        $new_arr['voice_mp3'] = '';
                    }

                    import('@.ORG.Apppush');
                    $apppush = new Apppush();
                    $apppush->send($new_arr, 'visual');

                    //公众号推送
                    if($user_info['openid']){
                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));

                        $extinfo = [
                            'pigcms_tag' => 'house_visual',
                            'tag_desc' => $new_arr['bind_id'],
                            'url' => $new_arr['url'],
                            'device_sn'=>$new_arr['device_sn'],
                            'periodNumber'=>$new_arr['periodNumber'],
                            'buildingNumber'=>$new_arr['buildingNumber'],
                            'unitNumber'=>$new_arr['unitNumber'],
                            'floorNumber'=>$new_arr['floorNumber'],
                            'roomNumber'=>$new_arr['roomNumber'],
                            'unitType'=>$new_arr['unitType'],
                            'channelNo'=>$new_arr['channelNo'],
                            'device_id'=>$new_arr['device_id'],
                            'pigcms_id'=>$new_arr['pigcms_id'],
                            'cloud_protocol'=> 'hik_protocol'
                        ];

                        $href = C('config.site_url') . '/topic/app_wap_platform.html?extinfo=' . urlencode(json_encode($extinfo));
                        fdump_api(['循环4：'.__LINE__,$href,$extinfo],'a5SendPush/sendPushLog',1);
                        $model->sendTempMsg('TM0001801',
                            array(
                                'href' => $href,
                                'wecha_id' => $user_info['openid'],
                                'first' => $new_arr['msg'],
                                'keyword1' => '可视对讲',
                                'keyword2' => '待接听',
                                'keyword3' => date('H时i分', $_SERVER['REQUEST_TIME']),
                                'remark' => '请使用APP接听！'
                            )
                        );
                    }
                }
            }
        }
        return true;
    }


    public function getAuthSearchStatus($groupId='',$pageNo=1,$pageSize=10,$employeeNo='',$deviceSerial='')
    {
        $url = 'https://api2.hik-cloud.com/api/v1/open/accessControl/allots/actions/searchStatus';
        $data = [
            "pageNo"=> $pageNo,
            "pageSize"=> $pageSize,
        ];
        if ($groupId) {
            $data['groupId'] = $groupId;
        }
        if ($deviceSerial) {
            $data['deviceSerial'] = $deviceSerial;
        }
        if ($employeeNo) {
            $data['employeeNo'] = $employeeNo;
        }
        $arr = json_encode($data);
        $info = $this->a5_face_class->curlPost($url,$arr,1,$this->token);
        fdump(['查询下发失败记录'.__LINE__,$url,$arr,$this->token, $info], 'api/log/' . date('Ymd') . '/a5/searchStatusErrLog', true);
        return $info;
    }

    public function a5FaceScore($baseImg,$dataType=1,$operation='faceScore') {
        if (!$baseImg || !$dataType || !$operation) {
            // 没有参数直接返回错误
            fdump_api(['A5图片评分参数缺失'.__LINE__,$baseImg,$dataType,$operation], 'a5/errA5FaceScoreLog', true);
            return false;
        }
        $url = "https://api2.hik-cloud.com/api/v1/ai/intelligence/face/analysis/score";
        $data = [
            'dataType' => intval($dataType),
            'operation' => $operation,
            'image' => $baseImg,
        ];
        $info = $this->curl_post($url,$data,$this->token);
        return $info;
    }
    // https://api2.hikcloud.com/api/v1/ai/intelligence/face/analysis/score
    // 图片评分
    public function faceAnalysisScore($img,$isBase64=false,$uid=0,$bind_id =0) {
        if (!$isBase64 && $img) {
            $imgUrl = D('Image')->transform_image($img);
            $imagesize1 = getimagesize($imgUrl);
            if ($imagesize1[0]&&$imagesize1[0]<=420&&$imagesize1[1]&&$imagesize1[1]<=560) {
                $img_url = $imgUrl;
            } else {
                $img_url = thumb_img($imgUrl, 560, 420, '', true);
                $imagesize = getimagesize($img_url);
                if ($imagesize[0]&&$imagesize[0]<420) {
                    // 如果压缩出来的图片小于宽度480  执行裁剪
                    $img_url = thumb_img($imgUrl,420,560,'fill',true);
                }
            }
            $baseImg = $this->imgtobase64($img_url);
        } else {
            $baseImg = $img;
        }
        $info = $this->a5FaceScore($baseImg);
        // 参考值
        // 特征点置信度 最小值
        $pointsQualityMin = 0.9;
        // 瞳距 最小值
        $eyeDistanceMin = 60;
        // 俯仰角 最大值
        $posePitchMax = 15;
        // 左右角 最大值
        $poseYawMax = 15;
        // 可见性 最小值
        $uncoveredMin = 0.9;
        // 清晰度 最小值
        $clearityMin = 0.8;
        $faceImageScore = [];
        $checkImg = true;
        $faceImageScore['checkImgErr'] = '';
        if (isset($info['code']) && 200==$info['code']) {
            $faceImageScore['code'] = $info['code'];
            $faceScore = isset($info['data']['faceScore'])?$info['data']['faceScore']:[];
            if ($faceScore) {
                $faceScore = reset($faceScore);
                if (isset($faceScore['pointsQuality']) && abs($faceScore['pointsQuality'])<$pointsQualityMin) {
                    $faceImageScore['pointsQuality'] = $faceScore['pointsQuality'];
                    $faceImageScore['pointsQualityTitle'] = '特征点置信度[是否人脸]';
                    $faceImageScore['pointsQualityCheck'] = false;
                    $faceImageScore['pointsQualityErr'] = '图片上未捕捉到人脸信息';
                    $faceImageScore['checkImgErr'] = '图片上未捕捉到人脸信息;';
                    $checkImg = false;
                    $faceImageScore['checkImg'] = $checkImg;
                    return $faceImageScore;
                } elseif (isset($faceScore['pointsQuality']) && $faceScore['pointsQuality']) {
                    $faceImageScore['pointsQuality'] = $faceScore['pointsQuality'];
                    $faceImageScore['pointsQualityTitle'] = '特征点置信度[是否人脸]';
                    $faceImageScore['pointsQualityCheck'] = true;
                    $faceImageScore['pointsQualityErr'] = '';
                }
                if (isset($faceScore['eyeDistance']) && abs($faceScore['eyeDistance'])<$eyeDistanceMin) {
                    $faceImageScore['eyeDistance'] = $faceScore['eyeDistance'];
                    $faceImageScore['eyeDistanceTitle'] = '瞳距[同像素人脸占比;同占比像素高低]';
                    $faceImageScore['eyeDistanceCheck'] = false;
                    $faceImageScore['eyeDistanceErr'] = '图片中人脸可能占比较小，或更换清晰度较高的照片';
                    $faceImageScore['checkImgErr'] .= '图片中人脸可能占比较小，或更换清晰度较高的照片;';
                    $checkImg = false;
                } elseif (isset($faceScore['eyeDistance']) && $faceScore['eyeDistance']) {
                    $faceImageScore['eyeDistance'] = $faceScore['eyeDistance'];
                    $faceImageScore['eyeDistanceTitle'] = '瞳距[同像素人脸占比;同占比像素高低]';
                    $faceImageScore['eyeDistanceCheck'] = true;
                    $faceImageScore['eyeDistanceErr'] = '';
                }
                if (isset($faceScore['posePitch']) && abs($faceScore['posePitch'])>$posePitchMax) {
                    $faceImageScore['posePitch'] = $faceScore['posePitch'];
                    $faceImageScore['posePitchTitle'] = '俯仰角[抬头或低头]';
                    $faceImageScore['posePitchCheck'] = false;
                    $faceImageScore['posePitchErr'] = '请不要抬头或者低头，正视镜头进行拍照';
                    $faceImageScore['checkImgErr'] .= '请不要抬头或者低头，正视镜头进行拍照;';
                    $checkImg = false;
                } elseif (isset($faceScore['posePitch']) && $faceScore['posePitch']) {
                    $faceImageScore['posePitch'] = $faceScore['posePitch'];
                    $faceImageScore['posePitchTitle'] = '俯仰角[抬头或低头]';
                    $faceImageScore['posePitchCheck'] = true;
                    $faceImageScore['posePitchErr'] = '';
                }
                if (isset($faceScore['poseYaw']) && abs($faceScore['poseYaw'])>$poseYawMax) {
                    $faceImageScore['poseYaw'] = $faceScore['poseYaw'];
                    $faceImageScore['poseYawTitle'] = '左右角[左右偏头]';
                    $faceImageScore['poseYawCheck'] = false;
                    $faceImageScore['poseYawErr'] = '请不要左右偏头，正视镜头进行拍照';
                    $faceImageScore['checkImgErr'] .= '请不要左右偏头，正视镜头进行拍照;';
                    $checkImg = false;
                } elseif (isset($faceScore['poseYaw']) && $faceScore['poseYaw']) {
                    $faceImageScore['poseYaw'] = $faceScore['poseYaw'];
                    $faceImageScore['poseYawTitle'] = '左右角[左右偏头]';
                    $faceImageScore['poseYawCheck'] = true;
                    $faceImageScore['poseYawErr'] = '';
                }
                if (isset($faceScore['uncovered']) && abs($faceScore['uncovered'])<$uncoveredMin) {
                    $faceImageScore['uncovered'] = $faceScore['uncovered'];
                    $faceImageScore['uncoveredTitle'] = '可见性[遮挡,轮廓不清晰]';
                    $faceImageScore['uncoveredCheck'] = false;
                    $faceImageScore['uncoveredErr'] = '当前人脸部分存在遮挡轮廓不清晰,请不要带帽或者遮挡人脸';
                    $faceImageScore['checkImgErr'] .= '当前人脸部分存在遮挡轮廓不清晰,请不要带帽或者遮挡人脸;';
                    $checkImg = false;
                } elseif (isset($faceScore['uncovered']) && $faceScore['uncovered']) {
                    $faceImageScore['uncovered'] = $faceScore['uncovered'];
                    $faceImageScore['uncoveredTitle'] = '可见性[遮挡]';
                    $faceImageScore['uncoveredCheck'] = true;
                    $faceImageScore['uncoveredErr'] = '';
                }
                if (isset($faceScore['clearity']) && abs($faceScore['clearity'])<$clearityMin) {
                    $faceImageScore['clearity'] = $faceScore['clearity'];
                    $faceImageScore['clearityTitle'] = '清晰度[过亮过暗或不清晰]';
                    $faceImageScore['clearityCheck'] = false;
                    $faceImageScore['clearityErr'] = '请检查是否背景过暗、过亮或者摄像头摆动导致图片不清晰';
                    $faceImageScore['checkImgErr'] .= '请检查是否背景过暗、过亮或者摄像头摆动导致图片不清晰;';
                    $checkImg = false;
                } elseif (isset($faceScore['clearity']) && $faceScore['clearity']) {
                    $faceImageScore['clearity'] = $faceScore['clearity'];
                    $faceImageScore['clearityTitle'] = '清晰度[过亮过暗或不清晰]';
                    $faceImageScore['clearityCheck'] = true;
                    $faceImageScore['clearityErr'] = '';
                }
            }
        } elseif (isset($info['message'])) {
            $faceImageScore['code'] = $info['code'];
            $faceImageScore['checkImgErr'] = $info['message'];
            $checkImg = false;
        }
        if ($checkImg && $uid && $img_url) {
            S('UserFaceImage'.$uid,$img_url);
        } elseif ($checkImg && $bind_id && $img_url) {
            S('HouseFaceImage'.$bind_id,$img_url);
        }
        $faceImageScore['checkImg'] = $checkImg;
        return $faceImageScore;
    }

    public function curl_post($url,$params,$access_token=''){
        $ch = curl_init();
        $header =  array(
            "Content-type: application/x-www-form-urlencoded;charset=utf-8",
        );
        if ($access_token) {
            $header[] =   "Authorization:".$access_token;
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        $result = curl_exec($ch);
//        $request_header = curl_getinfo( $ch, CURLINFO_HEADER_OUT);
        curl_close($ch);
        $result = json_decode($result,true);
        return $result;
    }

    // 过滤方法集中放后面
    public function findNum($str=''){
        $str=trim($str);
        if(empty($str)){return '';}
        $result='';
        for($i=0;$i<strlen($str);$i++){
            if(is_numeric($str[$i])){
                $result.=$str[$i];
            }
        }
        return $result;
    }
    public function replaceWord_($pigcms_id,$word,$phone) {
        $pregTitleSave = "/[\x{4e00}-\x{9fa5}\·\.\",\?|A-Za-z\.\。\d？！!(（)）：:+-{}\[\]【】《》<>-_· “”：，]+/iu";
        $word1 = trim($word);
        preg_match_all($pregTitleSave,$word1,$titleArray);
        $newWord = implode('',$titleArray[0]);
//        $pattern = "/[`~!@#$%^&*()=|{}':;',\\.<>《》\/?~！@#￥……&*（）—|{}【】‘；：”“'。，、？丶]/u";
//        $txt = trim($newWord,$pattern);
        $search = array(
            '/', '\/','\\', '`', '~', '!', '@','#',
            '$','%','^','&','*','(',')',
            '=','|','{','}',':',';',',','[',']',
            '.','<','>','《','》',' ',
            '?','~','！','#','￥',
            '…','…','&','*','（','）',
            '—','|','{','}','【','】','·',
            '‘','‘’','；','：','”','“',"'",
            '。','，','、','？','丶','+','-','_','='
        );
        $txt = str_replace($search,'', $newWord);
        if(empty($phone)){
            //手机号为空
            if($word1 != $txt){
                //存在特殊字符 取组合昵称
                $str='用户'.$pigcms_id;
            }else{
                //不存在特殊字符 取用户姓名
                $str= $txt;
            }
        }else{
            //手机号不为空
            if($word1 != $txt){
                //存在特殊字符，取手机号
                $str= $phone;
            }else{
                $str=$txt;
            }
        }
        if(empty($str) || strlen($str) > 16){
            $str='用户'.$pigcms_id;
        }
        fdump_api(['replaceWord_'.__LINE__, $pigcms_id,$word,$phone,$word1,$titleArray,$newWord,$txt,$str],'a5New/a5ReplaceWord_Log',1);
        return $str;
    }


    public function repairDeviceErrBindAuth($village_id, $device_sn='',$is_sys=false) {
        if (!$village_id && !$device_sn) {
            return false;
        }
        $floorIds = [];
        if ($device_sn) {
            $floor_ids = $this->getDeviceFloorId(0,$device_sn,$village_id);
            if(is_array($floor_ids)){
                $floorIds = $floor_ids;
            } elseif ($floor_ids>0) {
                $floorIds[] = $floor_ids;
            }
        }
        if (empty($floorIds)) {
            $whereFloor = [
                'village_id' => $village_id,
                'status' => ['neq',4],
            ];
            $floorIds = D('House_village_floor')->where($whereFloor)->getField('floor_id',true);
        }
        if (empty($floorIds)) {
            return false;
        }
        $whereErrDeviceBind = [
            'bind_type' => 0,
            'device_type' => 5,
            'bind_id' => ['not in', $floorIds],
        ];
        if ($device_sn) {
            $whereErrDeviceBind['device_sn'] = $device_sn;
        } else {
            $where_face_device = [
                'village_id' => $village_id,
                'is_del' => 0,
            ];
            $device_sns = M('House_face_device')->where($where_face_device)->getField('device_sn',true);
            $whereErrDeviceBind['device_sn'] = ['in', $device_sns];
        }
        $errDeviceBind = D('Face_bind_device')->where($whereErrDeviceBind)->order('bind_id ASC')->select();
        $msg = [];
        $errDeviceBindGroup = [];
        if (!empty($errDeviceBind)) {
            set_time_limit(0);
            ini_set('memory_limit', '2048M');
            foreach ($errDeviceBind as $item) {
                $groupId = $item['group_id'];
                $deviceSerial = $item['device_sn'];
                $id = $item['id'];
                if ($is_sys && $device_sn) {
                    $deviceSerials = [$deviceSerial];
                    $ids = [$id];
                    usleep($this->loopUSleepTime);// 避免超频调用
                    $this->keepPlanThread();
                    $msg[$id] = $this->removeDevices($deviceSerials,$groupId,$ids);
                    $msg[$id]['item'] = $item;
                } else {

                    if (!isset($errDeviceBindGroup[$groupId])) {
                        $errDeviceBindGroup[$groupId] = [];
                        $errDeviceBindGroup[$groupId]['groupId'] = $groupId;
                        $errDeviceBindGroup[$groupId]['bind_id'] = $item['bind_id'];
//                        $errDeviceBindGroup[$groupId]['items'] = [];
                        $errDeviceBindGroup[$groupId]['ids'] = [];
                        $errDeviceBindGroup[$groupId]['deviceSerials'] = [];
                    }
//                    $errDeviceBindGroup[$groupId]['items'][] = $item;
                    $errDeviceBindGroup[$groupId]['ids'][] = $id;
                    $errDeviceBindGroup[$groupId]['deviceSerials'][] = $deviceSerial;
                }
            }
        }
        $errDeviceBindGroup = array_values($errDeviceBindGroup);
        if ($is_sys && !empty($errDeviceBindGroup)) {
            foreach ($errDeviceBindGroup as $val) {
                usleep($this->loopUSleepTime);// 避免超频调用
                $this->keepPlanThread();
                $deviceSerials = $val['deviceSerials'];
                $ids =  $val['ids'];
                $groupId = $val['groupId'];
                $msg[$groupId] = $this->removeDevices($deviceSerials,$groupId,$ids);
            }
        }
        return ['msg' => $msg,'errDeviceBindGroup' => $errDeviceBindGroup,'errDeviceBind' => $errDeviceBind];
    }
    /**
     * Notes: 单纯权限组解绑设备并更改关联记录
     * @param $deviceSerials
     * @param $groupId
     * @return bool|mixed
     * @author: weili
     * @datetime: 2020/7/24 13:31
     */
    public function removeDevices($deviceSerials,$groupId,$ids=[],$logParam=[]){
        $url = $this->a5_face_class->removeDevices;
        $arr = json_encode([
            "groupId"=> $groupId,
            "deviceSerials"=> $deviceSerials
        ]);
        usleep($this->loopUSleepTime);// 避免超频调用
        $this->keepPlanThread();
        $info = $this->a5_face_class->curlPost($url,$arr,1,$this->token);
        fdump_api(['绑定权限组解绑设备[请求]'.__LINE__,$deviceSerials,$groupId,$ids,$url,$arr,$this->token,$info],'a5New/removeDevicesLog',1);
        if($info['code'] == 200)
        {
            $logParam['log_status'] = 61;
            $logParam['log_content'] = '设备解绑权限组成功';
            $logParam['isDel'] = 1;
            if($ids)
            {
                $updateParam = [
                    'group_id' => 'del_'.$groupId,
                    'bind_type' => 44,
                    'device_type' => -5,
                ];
                $msg = D('Face_bind_device')->where(['id'=>['in', $ids]])->save($updateParam);
                if ($msg) {
                    usleep($this->loopUSleepTime);// 避免超频调用
                    $this->keepPlanThread();
                    D('Face_door_a5_service')->issuedByGroup($groupId);
                }
            } else {
                $updateParam = [
                    'group_id' => 'del_'.$groupId,
                    'bind_type' => 44,
                    'device_type' => -5,
                ];
                $whereBind = [
                    'group_id' => $groupId,
                    'device_sn' =>['in', $deviceSerials],
                ];
                $msg = D('Face_bind_device')->where($whereBind)->save($updateParam);
                if ($msg!==false) {
                    usleep($this->loopUSleepTime);// 避免超频调用
                    $this->keepPlanThread();
                    D('Face_door_a5_service')->issuedByGroup($groupId);
                }
            }
            return $info;
        }else{
            $logParam['log_status'] = 62;
            $logParam['log_content'] = '设备解绑权限组失败';
            $logParam['isDel'] = 0;
            $logParam['toStatus'] = 3;
            $logParam['errCode'] = $info['code'];
            if (isset($info['message'])) {
                $logParam['errReason'] = $info['message'];
            } else {
                $logParam['errReason'] = '设备解绑权限组失败';
            }
            fdump_api(['错误绑定权限组解绑设备[失败]'.__LINE__,$logParam],'a5New/removeDevicesLog',1);
            return false;
        }
    }

    // 访客二维码生成
    public function getVisitorQrcode($param = []) {
        // 卡号（只支持纯数字） 最长20位
        $cardNo     = isset($param['cardNo'])     && $param['cardNo']     ? $param['cardNo']     : date('ymdHis');
        // 生效时间，时间格式yyMMddHHmmss，例如181205180000  长度12位
        $effectTime = isset($param['effectTime']) && $param['effectTime'] ? $param['effectTime'] : '';
        // 失效时间，时间格式yyMMddHHmmss，例如181205200000，与生效时间间隔最大48小时	长度12位
        $expireTime = isset($param['expireTime']) && $param['expireTime'] ? $param['expireTime'] : '';
        // 开门次数，最大9次  1-9
        $openTimes  = isset($param['openTimes'])  && $param['openTimes']  ? $param['openTimes']  : 1;
        if (!$cardNo || !$effectTime || !$expireTime || !$openTimes) {
            fdump_api(['param' => $param, 'err' => '缺少必传参数'], 'a5New/errGetVisitorQrcodeLog', 1);
            return false;
        }
        if ($openTimes < 1 || $openTimes > 9) {
            fdump_api(['param' => $param, 'err' => '开门次数需要在1-9'], 'a5New/errGetVisitorQrcodeLog', 1);
            return false;
        }
        $url = 'https://api2.hik-cloud.com/api/v1/community/access/visitors/actions/getQrcode';
        $urlParam = "cardNo={$cardNo}&effectTime={$effectTime}&expireTime={$expireTime}&openTimes={$openTimes}";
        $info = $this->postCode($url,$urlParam,$this->token);
        $qrCodeUrl='';
        if($info && isset($info['data']) && isset($info['data']['qrCodeUrl']) && $info['data']['qrCodeUrl']){
            $imgUrl = D('Image')->image_download($info['data']['qrCodeUrl'],'visitor_qrcode');
            $qrCodeUrl=$info['data']['qrCodeUrl'];
            $info['data']['qrCodeUrl']=$imgUrl;
        }
        fdump_api(['info' => $info,'qrCodeUrl'=>$qrCodeUrl, 'url' => $url, 'urlParam' => $urlParam, 'token' => $this->token, 'msg' => '获取访客二维码'], 'a5New/getVisitorQrcodeLog', 1);
        return $info;
    }
    
    public function postCode($url, $urlParam, $token) {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $urlParam,
            CURLOPT_HTTPHEADER => array(
                'Authorization: '.$token,
                'Content-Type: application/x-www-form-urlencoded'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $result = json_decode($response, true);
        return $result;
    }
}