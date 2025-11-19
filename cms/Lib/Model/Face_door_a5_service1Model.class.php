<?php

/**
 * Class Face_door_a5_serviceModel
 * @author weili
 * @datetime 2020/07/20
 */
class Face_door_a5_service1Model extends Model
{
    public $a5_face_class = '';
    public $token = '';
    public $access_token = '';
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
        $info = $this->a5_face_class->curlPost($url,$postData);
//        fdump($info,'api/$info',true);
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
    public function addEquipment($device_id,$door_info=[])
    {
        if ($device_id && empty($door_info)) {
            $door_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
            if (empty($door_info)) {
                fdump_api(['添加设备[对应设备不存在]'.__LINE__,$device_id,$door_info],'a5/errAddEquipmentLog',1);
                $info['error'] = 1;
                $info['msg'] = '参数错误，对应设备不存在！';
                return $info;
            }
        }
        $village_info = M('House_village')->where(['village_id'=> $door_info['village_id'],'is_del'=>0])->field('village_id,village_name')->find();
        //同步云分组
        $groupName = $village_info['village_name'];
        $groupNo = $door_info['village_id'];
        $post_data = ['groupName' => $groupName,'groupNo' => $groupNo];
        $postData = json_encode($post_data);
        $groupInfo = $this->getGroupInfo($door_info['village_id']);

        if($groupInfo['code'] == 200)
        {
            $resGroup = $groupInfo;
        }else{
            $resGroup = $this->createGroup($postData);//同步云 创建分组
        }
        //同步云 创建设备
        if ($resGroup['code'] == 200 || $resGroup['code'] == 510107) {
            $post_arr = [
                'deviceSerial'=>$door_info['device_sn'],
                'groupNo'=>$door_info['village_id'],//组编号是社区id
                'validateCode'=>trim($door_info['cloud_code']),
            ];
            $postArr = json_encode($post_arr);
            $resData = $this->createMachine($device_id,$postArr);
            if($resData['code'] == 200){
                $info['error'] = 0;
                $info['msg'] = '同步设备成功！';
                if($door_info['is_visual'] == 0)
                {
                    $notifyUrl = C('config.site_url').'/appapi.php?c=Face_door&a=openVisual';
                    $info = $this->setNotifyUrl($notifyUrl);
                }
                // 添加一键同步功能
                D('Face_door_service')->face_device_check($device_id);
                return $info;
            }elseif($resData['code'] == 40012003){
                M('House_face_device')->where(array('device_id' => $device_id))->data(['device_status'=>2])->save();
                fdump_api(['添加设备[同步海康-已存在？]'.__LINE__,$device_id,$postArr,$resData],'a5/errAddEquipmentLog',true);
                $info['error'] = 1;
                $info['msg'] = '同步设备异常！';
                return $info;
            }else{
                M('House_face_device')->where(array('device_id' => $device_id))->data(['device_status'=>2])->save();
                fdump_api(['添加设备[错误]'.__LINE__,$device_id,$postArr,$resData],'a5/errAddEquipmentLog',true);
                if (isset($resData['message']) && $resData['message']) {
                    $msg = $resData['message'];
                    if (isset($resData['code']) && $resData['code']) {
                        $msg .= "【错误码：{$resData['code']}】";
                    }
                } else {
                    $msg = '设备不在线或设备不存在。请确认设备并连接好设备后，重新同步设备！';
                }
                $info['error'] = 1;
                $info['msg'] = $msg;
                return $info;
            }
        }else{
            M('House_face_device')->where(array('device_id' => $device_id))->data(['device_status'=>2])->save();
            fdump_api(['添加设备[同步分组异常]'.__LINE__,$device_id,$resGroup],'a5/errAddEquipmentLog',true);
            $info['error'] = 0;
            $info['msg'] = '同步分组异常！';
            return $info;
        }
    }
    //设置设备
    public function setVisual($device_id,$door_info=[]){
        $door_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        if (empty($door_info)) {
            $info['error'] = 1;
            $info['msg'] = '参数错误，对应设备不存在！';
            return $info;
        }
        $info = $this->getEquipmentInfo($door_info['device_sn']);
        if($info && $info['code'] == 510206){
            // 510206 设备不存在
            $this->addEquipment($device_id,$door_info);
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
    public function createMachine($device_id,$postData=[])
    {
        $url = $this->a5_face_class->machine_register_url;
        $info = $this->a5_face_class->curlPost($url,$postData,1,$this->token);
        if($info['code'] == 200) {
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
                fdump_api(['注册设备[本地记录失败]'.__LINE__,$device_id,$info,$insertData,M('House_face_device')->_sql()],'a5/errCreateMachineLog',true);
            }
        }else {
            fdump_api(['注册设备[返回错误]'.__LINE__,$url,$device_id,$postData,$this->token,$info],'a5/errCreateMachineLog',true);
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
    public function outMachine($deviceSerial='')
    {
        $url = $this->a5_face_class->machine_out_url;
        $url = $url.'?deviceSerial='.$deviceSerial;
        $info = $this->a5_face_class->curlPost($url,[],1,$this->token);
        fdump_api(['注销设备[日志]'.__LINE__,$deviceSerial,$url,$this->token,$info],'a5/outMachineLog',true);
        if($info['code'] == 200)
        {
            $face_device = D('House_face_device')->where(array('device_sn' => $deviceSerial,'is_del'=>0))->find();
            M('House_face_device')->where(array('device_sn' => $deviceSerial,'is_del'=>0))->data(['cloud_device_id'=>''])->save();
            //删除本地权限组
            D('Face_bind_device')->where(array('device_sn' => $deviceSerial,'device_type'=>['in',[5,6]]))->delete();
            //删除设备绑定成员
            D('Face_user_bind_device')->where(['device_id'=>$face_device['device_id']])->delete();
        }
        return $info;
    }
    //同步人脸 处理
    public function synDeviceDispose($device_id,$bind_arr_id,$device_sn,$orderGroupId='')
    {
        fdump_api(['同步人脸[日志]'.__LINE__,$device_id,$bind_arr_id,$device_sn],'a5/synDeviceDisposeLog',true);
        foreach ($bind_arr_id as $val){
            $this->synDevice($val,$device_id, $orderGroupId);
            usleep(500000);		//500毫秒
        }
        return true;
    }
    //同步人脸 a5
    public function synDevice($pigcms_id,$device_id='',$face_device_data=[],$orderGroupId='')
    {
        // 人脸门禁类型A5和A6 统一考虑
        if (empty($face_device_data)) {
            $face_device_data = M('House_face_device')->where(array('device_id' => $device_id))->find();
        }
        $param = array();
        $param['type'] = 'sync_a5_face_member';
        $param['device_id'] = $device_id;
        $param['village_id'] = $face_device_data['village_id'];
        $param['pigcms_id'] = $pigcms_id;
        $floor_id = D('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->getField('floor_id');
        $param['floor_id'] = $floor_id;
        if ($orderGroupId) {
            $param['orderGroupId'] = $orderGroupId;
        }
//        $param['unique_id'] = 'a5_face_syn_device_'.$param['village_id'].'_'.$device_id.'_'.$face_device_data['pigcms_id'];
        $param['unique_id'] = 'a5_face_syn_device_'.$param['village_id'].'_'.$device_id.'_'.$pigcms_id;

		//如果是物业人员，A5 A6需要传个 floor_id 才能继续使用，单元机情况
		if(!$floor_id && stripos($face_device_data['floor_id'], ',') === false){
			$param['floor_id'] = $face_device_data['floor_id'];
		} elseif(!$floor_id && stripos($face_device_data['floor_id'], ',') !== false){
            //如果是物业人员，A5 A6需要传个 floor_id 才能继续使用，围墙机情况，取第一个单元作为自己的单元
			$param['floor_id'] = explode(',', $face_device_data['floor_id'])[0];
		}

        fdump_api(['同步人脸[日志]'.__LINE__,$param,$pigcms_id,$device_id],'a5/synDeviceLog',true);
        D('Face_door_service')->add_plan($param, 'sub_house_village_face_device', -11);

        return $param;
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
            ->field('b.pigcms_id,b.uid,b.phone,b.name,b.ic_card,b.property_endtime')
            ->order('b.pigcms_id desc')->select();
        $dataArr = [];
        foreach ($userArr as $key => &$val) {
            $end_time = '';
            if($val['property_endtime']) {
                $houseVillage = D('House_village')->where(['village_id' => $village_id])->field('owe_property_open_door,owe_property_open_door_day')->find();
                if ($houseVillage['owe_property_open_door'] == 1)//1允许延长开门时间  0不允许
                {
                    $end_time = $val['property_endtime'] + ($houseVillage['owe_property_open_door_day'] * 24 * 3600);
                }
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
                fdump_api(['修改物业延长时间[错误]'.__LINE__,$village_id,$device_type,$group],'a5/errEditLengthenLog',true);
                break;
            }
        }
        return $dataArr;
    }
    //用户缴费后同步开门时间
    public function payOpenDoor($pigcms_id)
    {
        $userArr = D('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->field('pigcms_id,uid,phone,name,ic_card,property_endtime,village_id,floor_id')->find();
        $userArr['end_time'] = '';
        if($userArr['property_endtime'])
        {
            $houseVillage = D('House_village')->where(['village_id'=>$userArr['village_id']])->field('owe_property_open_door,owe_property_open_door_day')->find();
            if($houseVillage['owe_property_open_door'] == 1)//1允许延长开门时间  0不允许
            {
                $userArr['end_time'] = $userArr['property_endtime']+($houseVillage['owe_property_open_door_day']*24*3600);
            }
        }else{//当不限制用户开门
            $userArr['end_time'] = strtotime('2037-12-30');//云系统规定最长有效期2037-12-31
        }
        $this->editMember($userArr);
        //权限组下发设备
        for($i=0;$i<10;$i++){
            sleep(5);
            $group = $this->getAuthGroup($userArr['floor_id']);
            if($group['code'] != 40021003)
            {
                fdump_api(['用户缴费后同步开门时间[错误]'.__LINE__,$pigcms_id,$userArr['floor_id'],$group],'a5/errPayOpenDoorLog',true);
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
                    fdump_api(['解除人员[错误]'.__LINE__,$pigcms_id,$group_id,$infoGroup],'a5/errRemoveMemberLog',true);
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
    public function removeDevice($device_sn)
    {
        $group_id = D('Face_bind_device')->where(['device_sn'=>$device_sn,'bind_type'=>0])->field('id,group_id')->select();

        if($group_id){
            foreach ($group_id as $val){
                $deviceSerials=[$device_sn];
                $info = $this->remDevice($deviceSerials,$val['group_id'],$val['id']);
                if($info)//解绑成功下发设备
                {
                    set_time_limit(0);
                    ini_set('memory_limit', '2048M');
                    for($i=0;$i<10;$i++){
                        $ret = $this->issuedByGroup($val['group_id']);
                        if($ret['code'] != 40021003)
                        {
                            fdump_api(['解除设备[错误]'.__LINE__,$device_sn,$val['group_id'],$ret],'a5/errRemoveDeviceLog',true);
                            break;
                        }
                        sleep(5);
                    }
                }else{
                    $this->outMachine($device_sn);
                    D('Face_bind_device')->where(['id'=>$val['id']])->delete();
                }
            }
            $info['error'] = 0;
            $info['msg'] = '同步删除成功！';
            return $info;
        }else{
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
            } else {
                //其他消息
                $otherMsg[] = json_decode($val['content'],true);
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
            fdump_api(['人脸抓拍消息'.__LINE__,$openFaceCapture],'a5/getChannelMsgOpenFaceCaptureLog',true);
        }
        if (!empty($openEventAccess)) {
            fdump_api(['门禁事件消息'.__LINE__,$openEventAccess],'a5/getChannelMsgOpenEventAccessLog',true);
        }
        if (!empty($openIdcardEvent)) {
            fdump_api(['人证对比事件消息'.__LINE__,$openIdcardEvent],'a5/getChannelMsgOpenIdcardEventLog',true);
        }
        if (!empty($openAccessAuthSyncResult)) {
            fdump_api(['门禁权限下发结果消息'.__LINE__,$openAccessAuthSyncResult],'a5/getChannelMsgOpenAccessAuthSyncResultLog',true);
        }
        if (!empty($openBatchDelivered)) {
            fdump_api(['门禁批量下发消息'.__LINE__,$openBatchDelivered],'a5/getChannelMsgOpenBatchDeliveredLog',true);
        }
        if (!empty($openDeviceOnoffline)) {
            fdump_api(['设备上下线消息'.__LINE__,$openDeviceOnoffline],'a5/getChannelMsgOpenDeviceOnofflineLog',true);
        }
        if (!empty($otherMsg)) {
            fdump_api(['其他消息'.__LINE__,$otherMsg],'a5/getChannelMsgOpenOtherMsgLog',true);
        }
        foreach ($openEventAccess as $key=>$val){
            $deviceInfo = M('House_face_device')->where(array('device_sn' => $val['deviceSerial']))->find();
            $villageInfo = $d_house_village->where(array('village_id' => $deviceInfo['village_id']))->find();
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
                    $house_village_bind = $d_house_village_user_bind->get_one_by_bindId($val['employeeNo']);
                    $uid = $house_village_bind['uid'];
                    $log['uid'] = intval($uid);
                    $log['log_bind_id'] = $val['employeeNo'];
                    $log['log_name'] = $village_name. ' ' . $floor_layer. ' ' . $floor_name;
                    $log['log_from'] = 1; // 人脸识别门禁
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
                    $log['log_detail'] = serialize($val);
                    $log['eventId'] = $val['eventId'];
                    $d_house_user_log->data($log)->add();
                    $d_house_face_device->where(array('device_sn' => $val['deviceSerial']))->setField('last_open_time', time());

                }
            }else{
                //开锁失败
                $status = 1;
                $uid = 0;
                $bind_id = 0;
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
            D('face_bind_device')->where(['group_id'=>$val['permissionGroupId']])->data($data)->save();
        }
        //人脸抓拍数据处理
        foreach ($openFaceCapture as $k=>$val){
            $deviceInfo = M('House_face_device')->where(array('device_sn' => $val['deviceSerial']))->find();
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
            $id = $d_house_user_log->data($log)->add();
            if (!$id) {
                fdump_api(['人脸添加事件失败'.__LINE__,$val,$log],'a5/errGetChannelMsgLog',1);
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
        $info = $this->a5_face_class->curlPost($url,$arr,1,$this->token);
        if($info['code'] == 200)
        {
            return $info['data'];
        }else{
            fdump_api(['获取权限组下的所有设备[错误]'.__LINE__,$url,$arr,$this->token,$info],'a5/getDeviceSerialsErrLog',1);
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
    public function addMember($village_id,$device_id,$user_bind_id='',$floor_id='')
    {
        if(!$user_bind_id) {
            $where['village_id'] = $village_id;
            $where['status'] = 1;
            $where['uid'] = ['neq', '0'];
            if ($floor_id) {
                $where['_string'] = "(floor_id in ($floor_id) OR type=4)";//物业人员也同步
            }
            $userArr = D('House_village_user_bind')
                ->where($where)
                ->field('pigcms_id,property_endtime,parent_id,type,single_id,floor_id,layer_id,vacancy_id,uid,phone,name,ic_card')
                ->select();
			
			fdump_api(['添加人员'.__LINE__,$userArr], 'a5/addMemberUserArrLog', true);
			
			if($userArr){
                foreach($userArr as $k=>$v){
					$this->synDevice($v['pigcms_id'], $device_id);
					usleep(80000); // 80毫秒
				}
				return true;
			}
            fdump_api(['无添加人员'.__LINE__,$userArr,$village_id,$device_id,$user_bind_id,$floor_id,D('House_village_user_bind')->_sql()], 'a5/errAddMemberUserArrLog', true);
            return true;
        }else{
            $userArr = D('House_village_user_bind')->where(['pigcms_id'=>$user_bind_id])->field('pigcms_id,type,parent_id,uid,phone,name,single_id,floor_id,layer_id,vacancy_id,ic_card,property_endtime,village_id')->find();
            $userArr['end_time'] = '';
            $layer_info = D('House_village_layer')->where(['id'=>$userArr['layer_id']])->field('layer_name,layer_number')->find();
            $room_info = D('House_village_user_vacancy')->where(['pigcms_id'=>$userArr['vacancy_id']])->field('room,room_number')->find();
            if ($layer_info['layer_number']) {
                $layer_num = $layer_info['layer_number'];
            } elseif(!preg_match('/[\x7f-\xff]/',$layer_info['layer_name'])){
                $layer_num = $layer_info['layer_name'];
            }else {
                if ($layer_info['layer_number']) {
                    $layer_num = $layer_info['layer_number'];
                } else {
                    $layer_num = $this->findNum($layer_info['layer_name']);
                }
            }
            if ($room_info['room_number']) {
                $room_num = $room_info['room_number'];
            } elseif(!preg_match('/[\x7f-\xff]/',$room_info['room'])){
                $room_num = $room_info['room'];
            }else {
                if ($layer_info['room_number']) {
                    $room_num = $room_info['room_number'];
                } else {
                    $room_num = $this->findNum($room_info['room_number']);
                }
            }
            if(strlen($layer_num)>3){
                $layer_num =  substr($layer_num,-3,3);
            }
            if(strlen($room_num)>2){
                $room_num =  substr($room_num,-2,2);
            }
            $userArr['layer_num'] = $layer_num;
            $userArr['vacancy_num'] = $room_num;
            if (!in_array($userArr['type'],[0,3]) && $userArr['parent_id']) {
                $property_endtime = D('House_village_user_bind')->where(array('pigcms_id'=>$userArr['parent_id']))->getField('property_endtime');
                if ($property_endtime>0) {
                    $userArr['property_endtime'] = $property_endtime;
                } else {
                    $userArr['property_endtime'] = 0;
                }
            }
            if($userArr['property_endtime'])
            {
                $houseVillage = D('House_village')->where(['village_id'=>$userArr['village_id']])->field('owe_property_open_door,owe_property_open_door_day')->find();
                if($houseVillage['owe_property_open_door'] == 1)//1允许延长开门时间  0不允许
                {
                    $userArr['end_time'] = $userArr['property_endtime']+($houseVillage['owe_property_open_door_day']*24*3600);
                } else {
                    $userArr['end_time'] = $userArr['property_endtime'];
                }
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
                fdump_api(['1单用户同步>>>'.__LINE__,$imgUrl,$userArr], 'a5/addMemberSingleUserArr', true);
//                $imgUrl = Image::transform_image($imgUrl);
                $imgUrl = D('Image')->transform_image($imgUrl);
                fdump_api(['2单用户同步>>>'.__LINE__,$imgUrl,$userArr], 'a5/addMemberSingleUserArr', true);
                $img_url = thumb_img($imgUrl, 640, 480, '', true);
//                $img_url = thumb_img(replace_file_domain($imgUrl), 640, 640, '', false, 200);
                $userArr['img_url'] = $img_url;
                //添加人员 云设备
                fdump_api(['<<<<3单用户同步'.__LINE__,$img_url, $village_id,$device_id,$user_bind_id,$floor_id,$imgUrl, $imgUrlInfo], 'a5/addMemberSingleUserArr', true);
                $res = $this->addMembers($userArr);
                return $res;
            } elseif ($userArr) {
                $res = $this->addMembers($userArr);
                return $res;
            }else{
                return 0;
            }
        }
    }
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
    public function toBase64($img_file,$base_head=true)
    {
        $img_base64 = '';
        $img_file = str_replace('?x-oss-process=image/resize,m_fill,w_200,h_200', '', $img_file);
        if(C('config.static_oss_switch')==1) {
            $file_handle = new file_handle();
            $file_handle->download($img_file);
            $img_file = $_SERVER['DOCUMENT_ROOT'].str_replace($_SERVER['REQUEST_SCHEME'] . '://' . C('config.static_oss_access_domain_names'), '', $img_file);
        }else if(C('config.static_cos_switch')==1) {
            $file_handle = new file_handle();
            $file_handle->download($img_file);
            $img_file = $_SERVER['DOCUMENT_ROOT'].str_replace($_SERVER['REQUEST_SCHEME'] . '://'.C('config.static_cos_access_domain_names'), '', $img_file);
        }else if (strpos($img_file,'http://') !== false) {
            $site_url = C('config.site_url');
            $site_url = str_replace('https://','http://',$site_url);
            $searh_val = $site_url . '/index.php?c=Image&a=thumb&url=';
            $img_file = str_replace($searh_val,'',$img_file);
            $img_file = urldecode($img_file);
            $img_file = str_replace('&width=200&height=200&resize=fill','',$img_file);
            $img_file = $_SERVER['DOCUMENT_ROOT'].str_replace($site_url,'',$img_file);
        } else if (strpos($img_file,'https://') !== false) {
            $site_url = C('config.site_url');
            $site_url = str_replace('http://','https://',$site_url);
            $searh_val = $site_url . '/index.php?c=Image&a=thumb&url=';
            $img_file = str_replace($searh_val,'',$img_file);
            $img_file = urldecode($img_file);
            $img_file = str_replace('&width=200&height=200&resize=fill','',$img_file);
            $img_file = $_SERVER['DOCUMENT_ROOT'].str_replace($site_url,'',$img_file);
        } else{
            $img_file = $_SERVER['DOCUMENT_ROOT'].str_replace(C('config.site_url'),'',$img_file);
        }
        if (file_exists($img_file)) {
            import('@.ORG.Image');
            $img_mer_id = time();
            $rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
            $upload_dir = "./upload/face_door/{$rand_num}/";
            if(!is_dir($upload_dir)){
                mkdir($upload_dir, 0777, true);
            }
            $upload_dir = "/upload/face_door/{$rand_num}/";
            $new_img_file = $_SERVER['DOCUMENT_ROOT'].$upload_dir ."{$img_mer_id}.jpg";
            Image::thumb($img_file,$new_img_file,'jpg',640,640);
            fdump($new_img_file,'api/log/'.date('Ymd').'/new_img_file_log',true);
//            dump($new_img_file);
            $app_img_file = file_get_contents($new_img_file);
            $file_content = base64_encode($app_img_file); // base64编码
            if ($base_head) {
                $img_base64 = 'data:image/jpeg;base64,' . $file_content;//合成图片的base64编码
            } else {
                $img_base64 = $file_content;//合成图片的base64编码
            }
        }

        return $img_base64; //返回图片的base64
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
        fdump_api(['replaceWord_'.__LINE__, $pigcms_id,$word,$phone,$word1,$titleArray,$newWord,$txt,$str],'a5/a5ReplaceWord_Log',1);
        return $str;
    }

    public function replaceWord_1($pigcms_id,$word,$phone) {
        $pregTitleSave = "/[\x{4e00}-\x{9fa5}\·\.\",\?|A-Za-z\.\。\d？！!(（)）：:+-{}\[\]【】《》<>-_· “”：，]+/iu";
        $word1 = trim($word);
        preg_match_all($pregTitleSave,$word1,$titleArray);
        $newWord = implode('',$titleArray[0]);
        $pattern = "/[`~!@#$%^&*()=|{}':;',\\.<>《》\/?~！@#￥……&*（）—|{}‘；：”“'。，、？丶]/u";
        $txt = trim($newWord,$pattern);
        fdump_api(['replaceWord_'.__LINE__, $pigcms_id,$word,$phone,$word1,$titleArray,$txt],'a5/a5_replaceWord',1);
        $txt = str_replace(' ','', $txt);
        $txt = str_replace('丶','', $txt);
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
        return $str;
    }


    /**
     * Notes: 云添加人员
     * @param $postArr
     * @return mixed
     * @author: weili
     * @datetime: 2020/7/22 11:54
     */
    public function addMembers($postArr,$bind_type=0,$device_type=5)
    {
        $where['bind_type'] = $bind_type;
        $where['bind_id'] = $postArr['pigcms_id'];
        $where['device_type'] = $device_type;
        //本地用户表
        $userInfo = D('Face_user_bind_device')->where($where)->find();
        $postArr['validBeginTime'] = date('Y-m-d',time());
        // 没有截止时间 默认延期10年
        $postArr['validEndTime'] = date('Y-m-d',time()+3652*86400);
        if(!$userInfo) {
            if ($postArr['img_url']) {
                $postArr['img_url'] = $this->imgtobase64($postArr['img_url']);
            }
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

            if ($postArr['img_url']) {
                $arr['faceImageBase64'] = $postArr['img_url'];
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
            $arr = json_encode($arr,JSON_UNESCAPED_SLASHES);

            $info = $this->a5_face_class->curlPost($url, $arr, 1, $this->token);

            $fArr = $arr;
            if (isset($fArr['faceImageBase64'])) {
                unset($fArr['faceImageBase64']);
            }
            $fPostArr = $postArr;
            if (isset($fPostArr['img_url'])) {
                unset($fPostArr['img_url']);
                $fPostArr['img_url'] = '有图片看前置接口';
            }
            fdump_api(['a5添加成员[请求接口]'.__LINE__,$url, $fArr, $this->token, $info],'a5/addMembersLog',1);

            if($info['code'] == 200){
                if($postArr['ic_card']){
                    //新增卡片
                    $info = $this->addCard($info['data']['employeeNo'],$postArr['ic_card'],$bind_type);
                    fdump_api(['a5添加卡片[人员添加成功]'.__LINE__,$info, $fPostArr, $bind_type, $info],'a5/addCardLog',1);
                }
                $dataArr = [
                    'bind_id'=>$postArr['pigcms_id'],
                    'device_type'=>$device_type,
                    'info'=>serialize($info),
                    'add_time'=>time(),
                ];
                D('Face_user_bind_device')->data($dataArr)->add();

                D('House_village_user_bind')->where(array('pigcms_id' => $postArr['pigcms_id']))->save(array('face_img_status' => 1, 'face_img_reason' => ''));
                fdump_api(['a5添加人员成功'.__LINE__,$info, $fPostArr, $bind_type, $info],'a5/addMembersSuccessLog',1);
                return $info;
            }elseif ($info['code'] == 40013001){//用户已存储到云，本地没有的情况
                //修改云信息
                $yunData = $this->getMember($postArr['pigcms_id']);
                fdump_api(['a5获取人员[用户已存储到云，本地没有的情况]'.__LINE__, $userInfo,$yunData,$info],'a5/errAddMembersGetUserLog',1);

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
                    if(empty($yunData['data']['validEndTime'])){
                        $postArr['validEndTime'] = date('Y-m-d',time()+3652*24*3600);
                    }
                    $msg = $this->editMember($postArr);
                    fdump_api(['a5去编辑人员[用户已存储到云，本地没有记录并编辑]'.__LINE__, $fPostArr, $bind_type, $msg],'a5/editMemberLog',1);
                }else{
                    //云不存在，就删除本地重新添加
                    D('Face_user_bind_device')->where($where)->delete();
                    $msg = $this->addMembers($postArr,$bind_type);
                    fdump_api(['a5删除本地数据后重新添加人员'.__LINE__, $fPostArr, $bind_type, $msg],'a5/addMembersLog',1);
                }
                return $userInfo;
            }else{

                D('House_village_user_bind')->where(array('pigcms_id' => $postArr['pigcms_id']))->save(array('face_img_status' => 2, 'face_img_reason' => $info['message'] ? : '海康未返回错误信息，可重试同步'));

                fdump_api(['a5添加人员其他错误'.__LINE__, $fPostArr, $bind_type, $info],'a5/errAddMembersLog',1);
                return $info;
            }
        }else{
            //修改云信息
            $yunData = $this->getMember($userInfo['bind_id']);
            fdump_api(['a5获取人员[本地有记录信息获取云信息]'.__LINE__, $userInfo,$yunData,$postArr],'a5/errAddMembersGetUserLog',1);
            if($yunData['code'] == 200){
                if(empty($yunData['data']['validBeginTime'])){
                    $postArr['validBeginTime'] = date('Y-m-d',time());
                }
                if($postArr['end_time']){
                    $postArr['validEndTime']=date('Y-m-d',$postArr['end_time']);
                } elseif(empty($yunData['data']['validEndTime'])){
                    $postArr['validEndTime'] = date('Y-m-d',time()+3652*24*3600);
                }
                $msg = $this->editMember($postArr,$bind_type);
                fdump_api(['a5去编辑人员'.__LINE__, $postArr, $bind_type, $msg],'a5/editMemberLog',1);
            }else{
                //云不存在，就删除本地重新添加
                D('Face_user_bind_device')->where($where)->delete();
                $msg = $this->addMembers($postArr);
                fdump_api(['a5删除本地数据后重新添加人员'.__LINE__, $postArr, $bind_type, $msg],'a5/addMembersLog',1);
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
    public function editMember($postArr,$bind_type=0)
    {
        $url = $this->a5_face_class->editMember;
        $arr = [
            'employeeNo' => $postArr['pigcms_id'],
            'personName' =>$this->replaceWord_($postArr['pigcms_id'],$postArr['name'],$postArr['phone']),
        ];
        if ($postArr['layer_num']) {
            $arr['floorNo'] = $postArr['layer_num'];
        }
        if ($postArr['vacancy_num']) {
            $arr['roomNo'] = $postArr['vacancy_num'];
        }
//
        if($postArr['validBeginTime']){
            $arr['validBeginTime'] = $postArr['validBeginTime'];
        }
        if($postArr['validEndTime']){
            $arr['validEndTime'] = $postArr['validEndTime'];
        }

        if($postArr['img_url'])
        {
//            $arr['faceImageBase64'] = $this->toBase64($postArr['img_url'],false);
            $arr['faceImageBase64'] = $this->imgtobase64($postArr['img_url']);
        }
        if($postArr['phone']){
            $arr['personPhone'] = trim($postArr['phone']);
        }
        if($postArr['end_time']){
            $arr['validEndTime']=date('Y-m-d',$postArr['end_time']);
        }
        if ($arr['validEndTime'] && $arr['validBeginTime'] && intval(strtotime($arr['validBeginTime']))>intval(strtotime($arr['validEndTime']))) {
            // 如果当前时间大于到用户期时间 开始时间以结束时间往前推一天
            $now_time = strtotime($arr['validEndTime']) - 24*3600;
            $arr['validBeginTime']=date('Y-m-d',$now_time);
        }
        $arrOld = $arr;
        $arr = json_encode($arr,JSON_UNESCAPED_SLASHES);
        $info = $this->a5_face_class->curlPost($url,$arr,1,$this->token);
        fdump_api(['a5编辑人员[请求接口]'.__LINE__, $url, $arrOld, $this->token,$info],'a5/editMemberLog',1);

        if($info['code'] == 200){
            if($postArr['ic_card']){
                //新增卡片
                $this->addCard($postArr['pigcms_id'],$postArr['ic_card'],$bind_type);
            } elseif (isset($postArr['ic_card']) && !$postArr['ic_card']) {
                // 传了卡号字段 确没有值 默认为空置处理 如果曾经添加过卡号且同步成功了 当前没有卡号 直接删除
                $device_type = 5;
                $where_repeat = [
                    'bind_type' => $bind_type,
                    'bind_id' => $postArr['pigcms_id'],
                    'device_type' => $device_type,
                ];
                $db_face_user_bind_device = D('Face_user_bind_device');
                $repeat = $db_face_user_bind_device->where($where_repeat)->field('card_status,cardid,card_json')->find();
                if (!empty($repeat) && $repeat['card_status']==1 && $repeat['cardid']) {
                    $this->deletePersonCards($postArr['pigcms_id'],$repeat['cardid'],$bind_type,$device_type);
                }
            }
            $dataArr = [
                'info'=>serialize($info),
                'last_time'=>time(),
            ];
            D('Face_user_bind_device')->where(['id'=>$postArr['id']])->data($dataArr)->save();

            D('House_village_user_bind')->where(array('pigcms_id' => $postArr['pigcms_id']))->save(array('face_img_status' => 1, 'face_img_reason' => ''));
            fdump_api(['a5编辑人员成功'.__LINE__, $url, $arr, $this->token,$postArr,$info],'a5/editMemberSuccessLog',1);
            return $info;
        }else{

            D('House_village_user_bind')->where(array('pigcms_id' => $postArr['pigcms_id']))->save(array('face_img_status' => 2, 'face_img_reason' => $info['message']));
            fdump_api(['a5编辑人员错误'.__LINE__, $url, $arr, $this->token,$postArr,$info],'a5/errEditMemberlog',1);
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
    public function addCard($employeeNo,$ic_card,$bind_type,$device_type=5)
    {
        $where_repeat = [
            'bind_type' => $bind_type,
            'bind_id' => $employeeNo,
            'device_type' => $device_type,
        ];
        $db_face_user_bind_device = D('Face_user_bind_device');
        $repeat = $db_face_user_bind_device->where($where_repeat)->field('card_status,cardid,card_json')->find();
        $cards = [[
            'cardNo'=>$ic_card, //卡号
            'cardType'=>'normalCard',
            'employeeNo'=>$employeeNo,
        ]];
        $postArr = json_encode(['cards'=>$cards]);
        $card_json_md5 = md5(json_encode([$cards, date('YmdH')], JSON_UNESCAPED_UNICODE));
		
        if ($repeat && $repeat['card_status']==1 && $repeat['card_json']==$card_json_md5) {
            $info = array (
                'code' => 200,
                'message' => '操作成功【用户相同卡号已经添加】',
            );
			fdump_api(['a5添加卡片【用户相同卡号已经添加】'.__LINE__, $postArr, $repeat],'a5/errAddCardLog',1);
            return $info;
        } elseif ($repeat && $repeat['card_status']==0 && !$repeat['cardid']) {
            // 卡号已经删除过 实时查询下当前用户卡号 如果实际删除了不进行添加
            $user_ic_card = D('House_village_user_bind')->where(array('pigcms_id' => $employeeNo))->getField('ic_card');
            if (!$user_ic_card) {
                $info = array (
                    'code' => 200,
                    'message' => '操作成功【用户卡号已经同步删除了，不做添加】',
                );
				fdump_api(['a5添加卡片【用户卡号已经同步删除了，不做添加】'.__LINE__, $postArr, $repeat],'a5/errAddCardLog',1);
                return $info;
            }
        }
		
        $url = $this->a5_face_class->addCard;
        $info = $this->a5_face_class->curlPost($url,$postArr,1,$this->token);
        fdump_api(['a5添加卡片[请求接口]'.__LINE__,$url, $postArr, $this->token, $info,$repeat,$card_json_md5],'a5/addCardLog',1);
        if($info['code'] == 200)
        {
            $where['bind_type'] =$bind_type;
            $where['bind_id'] =$employeeNo;
            $where['device_type'] =$device_type;
            $set = [
                'card_status'=>1,
                'cardid' => $ic_card,
                'card_json' => $card_json_md5
            ];
            D('Face_user_bind_device')->where($where)->data($set)->save();
            fdump_api(['a5添加卡片成功'.__LINE__,$url, $postArr, $this->token, $info],'a5/addCardSuccessLog',1);
        }elseif($info['code'] == 40014001){
            fdump_api(['同一人员下卡号重复[不做处理]'.__LINE__,$url, $postArr, $this->token, $info],'a5/errAddCardLog',1);
//            $this->deletePersonCards($employeeNo,$ic_card,$bind_type,$device_type);
            // 重复下发了记录下避免不停重复下发
            $where['bind_type'] =$bind_type;
            $where['bind_id'] =$employeeNo;
            $where['device_type'] =$device_type;
            $set = [
                'card_status'=>1,
                'cardid' => $ic_card,
                'card_json' => $card_json_md5
            ];
            D('Face_user_bind_device')->where($where)->data($set)->save();
        }else{
            fdump_api(['a5添加卡片其他错误'.__LINE__,$url, $postArr, $this->token, $info],'a5/errAddCardLog',1);
        }
        return $info;
    }
    //删除卡
    public function deletePersonCards($employeeNo,$ic_card,$bind_type,$device_type)
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
        if ($repeat && $repeat['card_status']==0 && $repeat['card_json']==$card_json_md5) {
            $info = array (
                'code' => 200,
                'message' => '操作成功【用户相同卡号已经删除】',
            );
            fdump_api(['a5删除卡片失败[操作成功【用户相同卡号已经删除】]'.__LINE__,$repeat, $del, $employeeNo,$ic_card,$bind_type,$device_type],'a5/errDeletePersonCardsLog',1);
            return $info;
        }

        $url = 'https://api2.hik-cloud.com/api/v1/open/basic/cards/deletePersonCards';
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
            $this->addCard($employeeNo,$ic_card,$bind_type,$device_type);
        }else{
            fdump_api(['a5删除卡片失败'.__LINE__,$url, $postArr, $this->token, $info,$employeeNo,$ic_card,$bind_type,$device_type,$repeat,$card_json_md5],'a5/errDeletePersonCardsLog',1);
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
    public function getFloor($village_id,$device_id,$floor_id='')
    {

        fdump_api(['获取本地小区单元作为云设备权限组'.__LINE__,$village_id,$device_id,$floor_id],'a5/getFloorLog',1);
        if(!$floor_id) {
            $floor_ids = $this->getDeviceFloorId($device_id,'',$village_id);
            if($floor_ids && $floor_ids>0)
            {
                if(is_array($floor_ids))
                {
                    $where['floor_id'] = ['in',$floor_ids];
                }else{
                    $where['floor_id'] = $floor_ids;
                }
            }
            $where['village_id'] = $village_id;
            $where['status'] = 1;
            $data = D('House_village_floor')->where($where)->field('floor_id,village_id,floor_name')->select();
            //查询所有a5设备序列号 权限组绑定设备
//            $map['device_type'] = ['in',[5,6]];
//            $map['device_status'] = 1;
//            $map['is_del'] = 0;
//            $map['village_id'] = $village_id;
            $map['device_id'] = $device_id;
            $device_sn = D('House_face_device')->where($map)->getField('device_sn',true);
//            $map['device_id'] = $device_id;
//            $device_sn_arr = D('house_face_device')->where($map)->field('device_sn,device_type')->find();
//            $device_sn = [$device_sn_arr['device_sn']];
//            $device_type = $device_sn_arr['device_type'];
            if ($data) {
                foreach ($data as $val) {
                    $info = $this->addAuthGroup($val,$device_sn,$device_id);
                    sleep(1); // 1秒
                }
                fdump_api(['获取本地小区单元作为云设备权限组'.__LINE__,$data,$info],'a5/addAuthGroupFloorLog',1);
                return $info;
            } else {
                fdump_api(['获取本地小区单元作为云设备权限组'.__LINE__,$village_id,$floor_id,$data,$device_sn],'a5/getFloorErrLog',1);
                return $data;
            }
        }else{
            $where['floor_id'] = $floor_id;
            $info = D('House_village_floor')->where($where)->field('floor_id,village_id,floor_name')->find();
            $map['device_type'] = ['in',[5,6]];
            $map['device_status'] = 1;
            $map['is_del'] = 0;
//            $map['floor_id'] = $floor_id;
            $map['_string'] = "find_in_set($floor_id,floor_id)";
            $map['village_id'] = $info['village_id'];
            $device_sn = D('House_face_device')->where($map)->getField('device_sn',true);
//            $map['device_id'] = $device_id;
//            $device_sn_arr = D('house_face_device')->where($map)->field('device_sn,device_type')->find();
//            $device_sn = [$device_sn_arr['device_sn']];
//            $device_type = $device_sn_arr['device_type'];
            if($info)
            {
                //同步云权限分组
                $infoGroup = $this->addAuthGroup($info,$device_sn,$device_id);
                fdump_api(['获取本地小区单元作为云设备权限组'.__LINE__,$info,$device_sn,$device_id,$infoGroup,$info],'a5/addAuthGroupFloorLog',1);
                return $infoGroup;
            } else {
                fdump_api(['获取本地小区单元作为云设备权限组'.__LINE__,$info,$map,$device_sn],'a5/errGetFloorLog',1);
                return $info;
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
        fdump(['新增权限组'.__LINE__,$aboutInfo], 'api/log/' . date('Ymd') . '/a5/addAuthGroupLog', true);
        if(true || !$aboutInfo['group_id']) {
            $url = $this->a5_face_class->addAuthGroup;
            // 避免名称重复 后缀统一加 '_编号'
            $groupName = $post['floor_name'].'_'.$post['floor_id'];
            $postArr = json_encode([
                'groupName' => $groupName
            ]);
            $info = $this->a5_face_class->curlPost($url, $postArr, 1, $this->token);
            fdump(['新增权限组[接口请求]'.__LINE__,$url, $postArr, $this->token, $post, $info], 'api/log/' . date('Ymd') . '/a5/addAuthGroupLog', true);
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
                $this->bindAuthGroupEquipment($device_sn, $info['data']['groupId'],$post['floor_id'],$device_id);
                fdump(['新增权限组'.__LINE__,$arr], 'api/log/' . date('Ymd') . '/a5/addAuthGroupSuccessLog', true);
                return $arr;
            }else{
                if (40021002==$info['code']) {
                    // 如果返回权限组已经存在  查询下本地记录
                    $aboutInfo = D('Face_bind_about_info')->where(['bind_type'=>$bind_type,'bind_id'=>$post['floor_id'],'device_type'=>$device_type])->order('`id` DESC')->find();
                }
                if($aboutInfo && $aboutInfo['group_id']){
                    fdump(['新增权限组[错误情况下走了绑定权限组]'.__LINE__,$aboutInfo, $info], 'api/log/' . date('Ymd') . '/a5/addAuthGroupLog', true);
                    $infos = $this->bindAuthGroupEquipment($device_sn, $aboutInfo['group_id'],$post['floor_id'],$device_id);
                    return $infos;
                } else {
                    $list = $this->getAuthGroupList(1,100,$groupName,$post['floor_id'],$device_id);
                    fdump(['新增权限组[失败]'.__LINE__,$url, $postArr, 1, $this->token,$aboutInfo,$device_sn, $aboutInfo['group_id'],$post['floor_id'],$device_id,$list,$info], 'api/log/' . date('Ymd') . '/a5/errAddAuthGroupLog', true);
                }
//                foreach ($list['data']['rows'] as $val)
//                {
//					if($val['groupName'] == $post['floor_name']){
//						// $infoss = $this->delAuthGroup($val['groupId']);
//					}
//                    fdump($infoss, 'api/log/' . date('Ymd') . '/face/face_a5_add_auth_group_err_log', true);
//                }
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
    public function getAuthGroupList($pageNo = 1,$pageSize=10,$groupName='',$floor_id=0,$device_id=0)
    {
        $url = $this->a5_face_class->getAuthGroupList;
        $arr = json_encode([
            "pageNo"=> $pageNo,
            "pageSize"=> $pageSize
        ]);
        $info = $this->a5_face_class->curlPost($url,$arr,1,$this->token);

        if ($groupName && $info['data']['rows']) {
            foreach ($info['data']['rows'] as $val)
            {
                if($val['groupName'] == $groupName){
                    $arr = [
                        'bind_type' => 3,
                        'bind_id' => $floor_id,
                        'bind_name' => $groupName,
                        'group_id' => $val['groupId'],
                        'third_info' => serialize($val),
                        'device_type' => 5,
                        'add_time' => time(),
                        'last_time' => time(),
                        'device_id' =>$device_id,
                    ];
                    D('Face_bind_about_info')->data($arr)->add();
                    break;
                }
            }
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
        $info = $this->a5_face_class->curlPost($url,[],1,$this->token);
        fdump(['删除多余权限组'.__LINE__,$group_id,$url,$this->token,$info], 'api/log/' . date('Ymd') . '/a5/delAuthGroupLog', true);
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
    public function getBindAuthGroupMember($village_id, $floor_id='',$bind_id='')
    {
        // 数据写死，暂不考虑扩展
        $bind_type = 3;
        $device_type = 5;
//        $device_type = D('house_face_device')->where(['device_id'=>$device_id])->getField('device_type');
        if ($bind_id) {
            //单个
            $where['b.village_id']=$village_id;
            $where['b.status']=1;

            $where['_string'] = "b.floor_id='$floor_id' OR b.type='4'";

            $where['d.bind_id']=$bind_id;
            $where['d.device_type']=$device_type;
            $where['d.bind_type']=0;
            //$where['d.group_id']=['eq','']; //获取没有绑定分组的成员
            $userData = D('Face_user_bind_device')->alias('d')
                ->join(' LEFT JOIN '.C('DB_PREFIX').'house_village_user_bind as b ON d.bind_id=b.pigcms_id')
                ->where($where)
                ->field('d.bind_id,d.id')->select();

            fdump_api(['权限组绑定人员'.__LINE__,$userData, D('Face_user_bind_device')->getLastSql()], 'a5/getBindAuthGroupMember_userData', true);

            $group_id = D('Face_bind_about_info')->where(['bind_type'=>$bind_type,'bind_id'=>$floor_id,'device_type'=>$device_type])->order('`id` DESC')->getField('group_id');
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
                $where['b.floor_id'] = $floor_id;
            }
            $where['a.bind_type']=$bind_type;
            $where['a.device_type']=$device_type;
            $where['b.village_id']=$village_id;
            $where['b.type']=['neq', 4];
            $where['b.status']=1;
            $where['d.device_type']=$device_type;
            $where['d.bind_type']=0;
            //$where['d.group_id']=['eq','']; //获取没有绑定分组的成员
            $userData = D('Face_user_bind_device')->alias('d')
                ->join(' LEFT JOIN '.C('DB_PREFIX').'house_village_user_bind as b ON d.bind_id=b.pigcms_id')
                ->join(' LEFT JOIN '.C('DB_PREFIX').'face_bind_about_info as a ON b.floor_id=a.bind_id')
                ->where($where)
                ->field('b.floor_id, d.bind_id, d.id, a.group_id')
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
                        $info =  $this->bindAuthGroupMember($val['arr'],$key,$val['ids'],$val['bind_id']);
                        sleep(1);		//1秒
                    }
                }
                return $info;
            }
        }
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
            fdump(['权限组ID不能为空'.__LINE__,$employeeNos, $group_id, $ids, $floor_id], 'api/log/'.date('Ymd').'/a5/errBindAuthGroupMemberLog',true);
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
        $info = $this->a5_face_class->curlPost($url,$arr,1,$this->token);
        if($info['code'] == 200)
        {
            $map['id'] = ['in',$ids];
            D('Face_user_bind_device')->where($map)->data(['group_id'=>$group_id])->save();
            $group = $this->getAuthGroup($floor_id);
            fdump(['权限组绑定人员成功'.__LINE__,$employeeNos,$group_id,$ids,$floor_id,$info], 'api/log/' . date('Ymd') . '/a5/bindAuthGroupMemberLog', true);
            return $group;
        }else{
            if ($repeat==0 && !$info && !$info['code']) {
                // 如果没有反馈 休眠1秒然后重复执行一次
                sleep(1);
                $this->bindAuthGroupMember($employeeNos,$group_id,$ids,$floor_id,1);
            } elseif ($floor_id && 40021001==$info['code']) {
                D('Face_bind_about_info')->where(['bind_type'=>3,'bind_id'=>$floor_id,'device_type'=>5])->delete();
            }
            fdump(['权限组绑定人员失败'.__LINE__, $url,$employeeNos, $group_id, $ids, $floor_id, $info], 'api/log/'.date('Ymd').'/a5/errBindAuthGroupMemberLog',true);
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
        $info = $this->a5_face_class->curlPost($url,$arr,1,$this->token);
        fdump(['权限组获取人员列表'.__LINE__,$url,$arr,$this->token, $info], 'api/log/' . date('Ymd') . '/a5/getAuthGroupMemberListLog', true);
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
                $group_id = D('Face_bind_about_info')->where(['bind_type'=>$bind_type,'bind_id'=>$val,'device_type'=>$device_type])->order('`id` DESC')->getField('group_id');
                $res = $this->remDevice($deviceSerials,$group_id);
                fdump(['权限组获取人员列表'.__LINE__,$deviceSerials,$group_id,$res], 'api/log/' . date('Ymd') . '/a5/delDeviceGroupLog', true);
                usleep(500000); // 500毫秒
            }
        }else{
            $group_id = D('Face_bind_about_info')->where(['bind_type'=>$bind_type,'bind_id'=>$floor_id,'device_type'=>$device_type])->order('`id` DESC')->getField('group_id');
            $res = $this->remDevice($deviceSerials,$group_id);
            fdump(['权限组获取人员列表'.__LINE__,$deviceSerials,$group_id,$res], 'api/log/' . date('Ymd') . '/a5/delDeviceGroupLog', true);
            usleep(500000); // 500毫秒
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
            $newData = array_diff($deviceSns,$deviceData);
            $newData1 = array_diff($deviceData,$deviceSns);
            fdump(['权限组绑定设备'.__LINE__,$deviceData,$newData,$newData1,$deviceSns], 'api/log/' . date('Ymd') . '/a5/bindAuthGroupEquipmentLog', true);
            if($newData && count($newData)>0){
                $deviceSns = array_values($newData);
            }
//            else{
//                fdump($employeeNos, 'api/log/' . date('Ymd') . '/face/2face_a5_bind_auth_group_1_device_err_log', true);
//                return true;
//            }
        }else{
            $deviceSns = D('House_face_device')->where(['device_id'=>$device_id])->getField('device_sn',true);
        }
//        elseif(!$employeeNos) {
//            $wheres['device_type'] = ['in',[5,6]];
//            $wheres['device_status'] = 1;
//            $wheres['status'] = 1;
//            $employeeNos = D('House_face_device')->where($wheres)->getField('device_sn', true);
//        }
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


        $list = $this->getAuthGroupMemberList($group_id);
        fdump(['权限组绑定设备获取权限组绑定成员'.__LINE__,$list,$group_id], 'api/log/' . date('Ymd') . '/a5/bindAuthGroupEquipmentLog', true);
//        //权限组获取设备
        $group_list = $this->getAuthGroupEquipmentList($group_id);
        fdump(['权限组绑定设备获取权限组绑定设备'.__LINE__,$group_list,$group_id], 'api/log/' . date('Ymd') . '/a5/bindAuthGroupEquipmentLog', true);

//        if($group_list['code'] == 200){
//            $AuthGroup = $group_list['data']['rows'];
//            fdump('--------------','api/$group_list',true);
//            fdump($AuthGroup,'api/$group_list',true);
//            if($AuthGroup){
//                $deviceSerial_arr = array_column($AuthGroup,'deviceSerial');
//                fdump($deviceSerial_arr,'api/$group_list',true);
//                $array_diffs= array_diff($deviceSerial_arr,$employeeNos);
//                $array_diffs = array_values($array_diffs);
//                fdump($array_diffs,'api/$group_list',true);
//                if($array_diffs){
//                    $this->remDevice($array_diffs,$group_id);
//                }
//            }
//
//        }
//
        if(!$deviceSns || count($deviceSns)<=0){
            return true;
        }
        $url = $this->a5_face_class->bindAuthGroupEquipment;
        $arr = json_encode([
            "groupId"=>$group_id,
            "checkCapability"=>true,
            "deviceSerials"=>$deviceSns,
        ]);
        $info = $this->a5_face_class->curlPost($url,$arr,1,$this->token);
        fdump_api(['权限组绑定设备'.__LINE__,$url,$arr,1,$this->token,$info],'a5/bindAuthGroupEquipmentLog',1);
        if($info['code'] == 200)
        {
            foreach ($deviceSns as $val) {
                $data['bind_id']=$floor_id;
                $data['device_sn']=$val;
                $data['group_id']=$group_id;
                $data['info']=serialize($info);
                $data['add_time']=time();
                $bind_device = D('Face_bind_device')->where(['bind_id'=>$floor_id,'device_sn'=>$val,'group_id'=>$group_id])->find();
                if(!$bind_device){
                    D('Face_bind_device')->data($data)->add();
                }
            }
            fdump(['绑定权限组成功'.__LINE__,$deviceSns,$info], 'api/log/' . date('Ymd') . '/a5/bindAuthGroupEquipmentSuccessLog', true);
            return $info;
        }elseif ($info['code'] == 40022002){
//            foreach ($employeeNos as $val)
//            {
//                $this->getCapability($val);
//            }
            //$this->bindAuthGroupEquipment($employeeNos,$group_id,$floor_id);
            fdump(['40022002权限组绑定设备失败'.__LINE__,$url,$arr,1,$this->token,$info], 'api/log/' . date('Ymd') . '/a5/errBindAuthGroupEquipmentLog', true);
            return true;
        }else{
            $map = [];
            $map['bind_type'] = 0;
            $map['group_id'] = $group_id;
            $map['device_type'] = ['in',[5,6]];
            fdump(['权限组绑定设备失败'.__LINE__,$url,$arr,1,$this->token,$info,$map], 'api/log/' . date('Ymd') . '/a5/errBindAuthGroupEquipmentLog', true);
            if ($info['code']) {
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
        fdump($info, 'api/log/' . date('Ymd') . '/a5/getCapabilityLog', true);
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
                    //云 根据权限组ID下发权限 将该权限组下所有人员权限下发到每个设备中
                    $info = $this->issuedByGroup($val);
                    usleep(500000);		//500毫秒
                }
                return $info;
            } else {
                fdump(['获取权限分组失败>>>'.__LINE__,$floor_id,$village_id,$bind_type,$device_type,$data,D('face_bind_about_info')->getDbError()],'api/log/'.date('Ymd').'/a5/errGetAuthGroupLog',true);
                return $data;
            }
        }else{
            //单个
            $group_id = D('Face_bind_about_info')->where(['bind_type'=>$bind_type,'bind_id'=>$floor_id,'device_type'=>$device_type])->order('`id` DESC')->getField('group_id');
            if($group_id)
            {
                //云 根据权限组ID下发权限 将该权限组下所有人员权限下发到每个设备中
                $infoGroup = $this->issuedByGroup($group_id);
                usleep(500000);		//500毫秒
                fdump(['单个权限组同步>>>'.__LINE__,$group_id,$infoGroup],'api/log/'.date('Ymd').'/a5/getAuthGroupLog',true);
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
//        $member = $this->getAuthGroupMemberList($group_id);
//        fdump($member, 'api/log/' . date('Ymd') . '/face_a5_bind_auth_group_member_err_log', true);
        if($info['code'] == 200)
        {
            fdump(['根据权限组ID下发权限成功>>'.__LINE__,$group_id,$url,$postArr,$this->token,$info],'api/log/'.date('Ymd').'/a5/issuedByGroupLog',true);
            return $info;
        }else{
            $info['message'] = $info['message'].$group_id;
            fdump(['根据权限组ID下发权限失败>>'.__LINE__,$group_id,$url,$postArr,$this->token,$info],'api/log/'.date('Ymd').'/a5/errIssuedByGroupLog',true);
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
        $info = $this->a5_face_class->curlPost($url,$postArr,1,$this->token);
        fdump(['根据权限组/设备/人员查询同步失败状态>>'.__LINE__,$url,$postArr,$this->token,$info],'api/log/'.date('Ymd').'/a5/searchStatusLog',true);
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
        $url = $this->a5_face_class-> getAuthGroupEquipmentList;
        $arr = json_encode([
            "pageNo"=>$pageNo,
            "pageSize"=> $pageSize,
            "groupId"=> $groupId
        ]);
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
    public function imgtobase64($img='', $imgHtmlCode=true)
    {
//        $imageInfo = getimagesize($img);
//        $base64 = chunk_split(base64_encode(file_get_contents($img)));
//        return 'data:' . $imageInfo['mime'] . ';base64,' . chunk_split(base64_encode(file_get_contents($img)));
        fdump_api(['base64图片转译前'.__LINE__,$img],'imgtobase64',1);
        $img_content = file_get_contents($img);
        file_put_contents('./runtime/tmp.jpg', $img_content);
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
        if($info['code'] == 200)
        {
            //todo 故障解除 A5/A6
            D('House_maintenan')->normalLog(1,$device_id);
            fdump(['获取设备信息'.__LINE__, $url,$this->token,$info],'api/log/'.date('Ymd').'/a5/succGetEquipmentInfoLog',true);
            return $info['data'];
        }else{

            //todo 故障通知 A5/A6
            D('House_maintenan')->faultLog(1,$device_id,(isset($info['message']) ? $info['message'] : '设备不在线'));
            fdump($info, 'api/log/' . date('Ymd') . '/face/face_a5_get_equipment_info_err_log', true);

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
            fdump(['获取组下所有设备[失败]>>'.__LINE__, $url,$this->token,$info],'api/log/'.date('Ymd').'/a5/errGetEquipmentAllLog',true);
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
        fdump_api(['权限组解除成员'.__LINE__,$employeeNos,$groupId,$url,$arr,$this->token],'a5/remMemberLog',1);
        $info = $this->a5_face_class->curlPost($url,$arr,1,$this->token);
        fdump_api(['权限组解除成员1'.__LINE__,$info],'a5/remMemberLog',1);
        if($info['code'] == 200)
        {
            // 清除对应用户组
            D('Face_user_bind_device')->where(['group_id'=>$groupId,'bind_id'=>$employeeNos])->data(['group_id'=>''])->save();
            // 删除对应用户卡号
            $where_repeat = [
                'bind_type' => 0,
                'bind_id' => $employeeNos,
                'device_type' => 5,
            ];
            $db_face_user_bind_device = D('Face_user_bind_device');
            $repeat = $db_face_user_bind_device->where($where_repeat)->field('card_status,cardid,card_json')->find();
            if ($repeat && $repeat['cardid'] && $repeat['card_status']==1) {
                $pigcms_id = D('House_village_user_bind')->where(array('ic_card' => $repeat['cardid'],'pigcms_id' => $employeeNos))->getField('pigcms_id');
                // 确认解除的是本人的卡号没有被其他人绑定去 删除卡片
                if ($pigcms_id) {
                    $this->deletePersonCards($employeeNos,$repeat['cardid'],0,5);
                }
            }
            return $info['data'];
        }else{
            fdump_api(['权限组解除成员1'.__LINE__,$url,$arr,$this->token,$info],'a5/errRemMemberLog',1);
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
    public function remDevice($deviceSerials,$groupId,$id=''){
        $url = $this->a5_face_class->removeDevices;
        $arr = json_encode([
            "groupId"=> $groupId,
            "deviceSerials"=> $deviceSerials
        ]);
        $info = $this->a5_face_class->curlPost($url,$arr,1,$this->token);
        if($info['code'] == 200)
        {
            if($id)
            {
                $this->outMachine($deviceSerials[0]);
                D('Face_bind_device')->where(['id'=>$id])->delete();
            }
            return $info;
        }else{
            fdump_api(['权限组解绑设备[失败]'.__LINE__,$deviceSerials,$groupId,$id,$url,$arr,$this->token,$info],'a5/errRemDeviceLog',1);
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
        $info = $this->a5_face_class->curlPost($url,[],1,$this->token);
        if($info['code'] == 200)
        {
            //删除人员同时删除IC卡片，避免卡片还能开门
            if($cardNos && count($cardNos)>0)
            {
                $this->delCard($cardNos);
            }
            return $info['data'];
        }else{
            fdump_api(['删除云设备成员[失败]'.__LINE__,$url,$this->token,$info],'a5/errDeleteMemberLog',1);
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
        $url = $this->a5_face_class->deleteMember;
        $arr = json_encode([
            "cardNos"=> $cardNos
        ]);
        $info = $this->a5_face_class->curlPost($url,$arr,1,$this->token);
        if($info['code'] == 200)
        {
            return $info['data'];
        }else{
            fdump_api(['删除成员卡片[失败]'.__LINE__,$url,$this->token,$info],'a5/errDelCardLog',1);
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
            fdump(['远程开门失败>>'.__LINE__,$url,$arr,$info],'api/log/'.date('Ymd').'/a5/errRemoteOpenLog',true);
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
            fdump(['老版本远程开门>>'.__LINE__,$url,$arr,$info,$this->token],'api/log/'.date('Ymd').'/a5/errRemoteOpenOldLog',true);
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
        $info = $this->a5_face_class->curlPost($url,$arrData,0,$this->token);
        if($info['code'] == 200)
        {
            return $info['data'];
        }else{
            fdump(['创建消费者失败>>'.__LINE__,$url,$arr,$arrData,$info],'api/log/'.date('Ymd').'/a5/errCreateConsumersLog',true);
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
        $info = $this->a5_face_class->curlPost($url,$arrData,0,$this->token);
        if ($info['code'] == 200 && empty($info['data'])) {
            sleep(2);
            $info = $this->a5_face_class->curlPost($url,$arrData,0,$this->token);
        }
        if($info['code'] == 200)
        {
            return $info['data'];
        }else{
            fdump(['消费信息失败>>'.__LINE__,$url,$arr,$arrData,$info],'api/log/'.date('Ymd').'/a5/errGetMassageLog',true);
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
        fdump(['获取设备下的通道列表>>'.__LINE__,$info,$url,$info],'api/log/'.date('Ymd').'/a5/getChannelListLog',true);
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
        $info = $this->a5_face_class->curlPost($url,$arr,1,$this->token);
        if($info['code'] == 200)
        {
            fdump(['设备绑定通道>>'.__LINE__,$url,$arr,$info],'api/log/'.date('Ymd').'/a5/bindChannelLog',true);
            return $info['data'];
        }else{
            fdump(['设备绑定通道>>'.__LINE__,$url,$arr,$info],'api/log/'.date('Ymd').'/a5/errBindChannelLog',true);
            return $info;
        }
    }
    //
    //设置可视对讲通知地址
    public function setNotifyUrl($notifyUrl)
    {
        $url = $this->a5_face_class->setNotifyUrl;
        $arr = json_encode([
            "notifyUrl"=> $notifyUrl
        ]);
        $info = $this->a5_face_class->curlPost($url,$arr,1,$this->token);
        if($info['code'] == 200)
        {
            fdump(['设置可视对讲通知地址'.__LINE__,$url, $arr, $info], 'api/log/' . date('Ymd') . '/a5/setNotifyUrlSuccessLog', true);
            return $info;
        }else{
            fdump(['设置可视对讲通知地址失败'.__LINE__,$url, $arr, $info], 'api/log/' . date('Ymd') . '/a5/errSetNotifyUrlLog', true);
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
            fdump(['获取可视对讲通知地址'.__LINE__,$url,$info], 'api/log/' . date('Ymd') . '/a5/getNotifyUrlSuccessLog', true);
            return $info['data'];
        }else{
            fdump(['获取可视对讲通知地址失败'.__LINE__,$url, $info], 'api/log/' . date('Ymd') . '/a5/errGetNotifyUrlLog', true);
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
            fdump(['关闭视频加密'.__LINE__,$url,$info], 'api/log/' . date('Ymd') . '/a5/setVideoOffSuccessLog', true);
            return true;
        }else{
            fdump(['关闭视频加密失败'.__LINE__,$url, $info], 'api/log/' . date('Ymd') . '/a5/errSetVideoOffLog', true);
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
            fdump(['开启视频加密'.__LINE__,$url,$info], 'api/log/' . date('Ymd') . '/a5/setVideoOnSuccessLog', true);
            return true;
        }else{
            fdump(['开启视频加密失败'.__LINE__,$url, $info], 'api/log/' . date('Ymd') . '/a5/errSetVideoOnLog', true);
            return false;
        }
    }
    //获取视频取流时需要的认证信息
    public function getApprove()
    {
        $url = $this->a5_face_class->getApprove;
        $info = $this->a5_face_class->curlGet($url,$this->token);
        fdump(['获取视频取流时需要的认证信息'.__LINE__,$url,$info], 'api/log/' . date('Ymd') . '/a5/getApproveLog', true);
        return $info;
    }
    //获取可视对讲对应参数
    public function getVisualParam($deviceSerial,$uid)
    {
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
        $map['device_sn'] = $deviceSerial;
        $map['device_status'] = 1;
        $map['is_del'] = 0;
        $device_info = D('House_face_device')->where($map)->field('device_id,floor_id,village_id,public_area_id,cloud_code')->find();
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
                $single_name = $single_info['single_name'].'栋';
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
        if($device_info['floor_id'] && $device_info['floor_id'] != -1 && $unitType == 'outdoor'){

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
                $single_name = $single_info['single_name'].'栋';
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
                            'pigcms_id'=>$new_arr['pigcms_id']
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

}