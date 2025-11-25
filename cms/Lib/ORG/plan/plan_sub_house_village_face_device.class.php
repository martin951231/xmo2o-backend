<?php
/*
 *社区人脸门禁设备相关计划任务
 * */
class plan_sub_house_village_face_device extends plan_base{


    public $loopUSleepTime = 500000; // 循环插入避免拥堵休眠毫秒数  暂定500毫秒
    public function runTask($param){

        fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/face_device/plan_sub_house_village_face_device',true);
        //删除计划任务
        if($param['plan_task_id'] && $param['type'] != 'a5_get_channel_msg'){
            $this->dropPlan($param['plan_task_id']);
        }
        if (isset($param['type']) && 'repairUserCard'==$param['type']) {
            $employeeNo = $param['employeeNo'];
            $ic_card = $param['ic_card'];
            $bind_type = $param['bind_type']?$param['bind_type']:0;
            $device_type = $param['device_type']?$param['device_type']:5;
            $otherParam = $param['otherParam']?$param['otherParam']:[];
            usleep($this->loopUSleepTime);// 避免超频调用
            $this->keepThread();
            $delInfo = D('Face_door_a5_service')->deleteCards($employeeNo,$ic_card,$bind_type,$device_type,$otherParam,1);
            fdump_api(['先删除'.__LINE__,$param,$employeeNo,$ic_card,$bind_type,$device_type,$delInfo],'a5/repairUserCardLog',1);
            if ($delInfo) {
                usleep($this->loopUSleepTime);// 避免超频调用
                $this->keepThread();
                $addInfo = D('Face_door_a5_service')->addCard($employeeNo,$ic_card,$bind_type,$device_type,$otherParam,1);
                fdump_api(['后添加'.__LINE__,$addInfo],'a5/repairUserCardLog',1);
            }
        }
        elseif (isset($param['type']) && 'sync_a5_face_member'==$param['type']) {
            $device_id = $param['device_id'];
            $village_id = $param['village_id']?$param['village_id']:0;
            $floor_id = $param['floor_id']?$param['floor_id']:0;
            $pigcms_id = $param['pigcms_id']?$param['pigcms_id']:0;
            $a5Service = D('Face_door_a5_service');
            $orderGroupId = isset($param['orderGroupId'])&&$param['orderGroupId']?trim($param['orderGroupId']):md5(uniqid().time());// 标记统一执行命令,没有就自行标记
            if (!$village_id) {
                fdump_api(['同步缺少参数'.__LINE__, $param],'a5/err_sync_a5_face_member_log',1);
            } else {
                usleep($this->loopUSleepTime);// 避免超频调用
                $this->keepThread();
                $info = $this->addMember($village_id,$device_id, $pigcms_id, $floor_id, $orderGroupId);
                $this->keepThread();
                fdump(['plan-sync_a5_face_member:----'.__LINE__,$param, $info],'api/log/'.date('Ymd').'/a5/sync_a5_face_member_log',true);
                if ($info) {
                    // 人脸门禁类型A5和A6 统一考虑
                    $param = array();
                    $param['type'] = 'sync_a5_face_floor';
                    $param['device_id'] = $device_id;
                    $param['village_id'] = $village_id;
                    if (strpos($floor_id,',') !== false) {
                        // 多floor_id  不传参
                        $floor_id = 0;
                    } elseif ($floor_id>0) {
                        $param['floor_id'] = $floor_id;
                    }
                    if ($pigcms_id>0) {
                        $param['pigcms_id'] = $pigcms_id;
                    }
                    if ($orderGroupId) {
                        $param['orderGroupId'] = $orderGroupId;
                    }
                    $param['unique_id'] = 'a5_face_floor_'.$village_id.'_'.$device_id.'_'.$floor_id.'_'.$pigcms_id;
                    $a5PlanDeviceAuthGroup = D('House_face_device_log')->a5PlanDeviceAuthGroup;
                    D('Face_door_service')->add_plan($param, 'sub_house_village_face_device', $a5PlanDeviceAuthGroup);
                }
            }
        }
        elseif (isset($param['type']) && 'a5GetDeviceStatus'==$param['type']) {
            // 获取门禁状态
            $a5Service = D('Face_door_a5_service');

            $d_house_face_device = M('House_face_device');
            $whereDevices = [
                'device_type' => ['in', [5,6]],
                'is_del' => 0
            ];
            $device_face_info = $d_house_face_device->where($whereDevices)->order('device_status DESC')->select();
            if (!empty($device_face_info)) {
                foreach ($device_face_info as $val) {
                    usleep($this->loopUSleepTime);// 避免超频调用
                    $this->keepThread();
                    $a5Service->getEquipmentInfo($val['device_sn']);
                }
            }
        }
        elseif (isset($param['type']) && 'a5RepairGetAuthGroups'==$param['type']) {
            // 获取门禁状态
            for ($pageNo = 1; $pageNo < 10; $pageNo++) {
                usleep($this->loopUSleepTime);// 避免超频调用
                $this->keepThread();
                $groups = $this->repairGetAuthGroupList($pageNo);
                $total = $groups['total'];
                $pageSize = $groups['pageSize'];
                $pageNo = $groups['pageNo'];
                $count = $groups['count'];
                $getTotal = intval($pageSize)*intval($pageNo);
                if ($count>0 && $total>$getTotal) {
                    // 如果获取的数量存在且当前页数据获取总计小区总数继续执行
                    continue;
                } else {
                    break;
                }
            }
        } else {
            fdump($param,'api/log/'.date('Ymd').'/face_device/plan_sub_house_village_face_device',true);
            usleep($this->loopUSleepTime);// 避免超频调用
            $this->keepThread();
            $res = D('House_face_device')->face_device_runtask($param);
        }
        return true;
    }

    public function repairGetAuthGroupList($pageNo=1,$pageSize=100) {
        $db_face_bind_about_info = M('Face_bind_about_info');
        $this->keepThread();
        $msg1 = D('Face_door_a5_service')->getAuthGroupList($pageNo,$pageSize);
        $total = 0;
        $count = 0;
        if (isset($msg1['data']['rows']) && !empty($msg1['data']['rows'])) {
            $total = $msg1['data']['total'];
            $count = count($msg1['data']['rows']);
            foreach ($msg1['data']['rows'] as $val) {
                $this->keepThread();
                $info1 = $db_face_bind_about_info->where(['bind_name' => $val['groupName']])->find();
                if (empty($info1) && $val['groupName'] && strpos($val['groupName'],'_') !== false) {
                    $this->keepThread();
                    $groupNameArr = explode('_',$val['groupName']);
                    if ($groupNameArr[1]) {
                        $data = [
                            'bind_type' => 3,
                            'bind_id' => $groupNameArr[1],
                            'bind_name' => $val['groupName'],
                            'third_info' => serialize($val),
                            'device_type' => 5,
                            'device_id' => 0,
                            'add_time' => strtotime($val['createTime'])?strtotime($val['createTime']):time(),
                            'last_time' => strtotime($val['updateTime'])?strtotime($val['updateTime']):0,
                            'group_id' => $val['groupId'],
                        ];
                        $db_face_bind_about_info->add($data);
                    }
                }
            }
        }
        return [
            'total' => $total,
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
            'count' => $count,
        ];
    }


    public function getDeviceFloorId($device_id,$village_id,$device_sn='')
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
        return $floor_id;
    }

    public function addMember($village_id,$device_id,$user_bind_id='',$floor_id='',$orderGroupId='')
    {
        $a5Service = D('Face_door_a5_service');
        if(!$user_bind_id) {
            $where['village_id'] = $village_id;
            $where['status'] = 1;
            if (!$floor_id || $floor_id=='-1') {
                $floor_id = $this->getDeviceFloorId($device_id,$village_id);
            }
            if ($floor_id) {
                $where['_string'] = "(floor_id in ($floor_id) OR type=4)";//物业人员也同步
            }
            $userArr = D('House_village_user_bind')
                ->where($where)
                ->field('pigcms_id,vacancy_id,uid,phone')
                ->select();
            $this->keepThread();
            fdump_api(['添加人员'.__LINE__,$userArr], 'a5/addMemberUserArrLog', true);
            if($userArr){
                foreach($userArr as $k=>$v){
                    $this->keepThread();
                    $a5Service->synDevice($v['pigcms_id'], $device_id, [], $orderGroupId, $v['uid']);
                }
                return true;
            }
            fdump_api(['无添加人员'.__LINE__,$userArr,$village_id,$device_id,$user_bind_id,$floor_id,D('House_village_user_bind')->_sql()], 'a5/errAddMemberUserArrLog', true);
            return true;
        }else{
            $userArr = D('House_village_user_bind')->where(['pigcms_id'=>$user_bind_id])->field('pigcms_id,type,parent_id,uid,phone,name,single_id,floor_id,layer_id,vacancy_id,ic_card,village_id')->find();
            $userArr['end_time'] = '';
            $this->keepThread();
            $layer_info = D('House_village_layer')->where(['id'=>$userArr['layer_id']])->field('layer_name,layer_number')->find();
            $this->keepThread();
            $room_info = D('House_village_user_vacancy')->where(['pigcms_id'=>$userArr['vacancy_id']])->field('room,room_number')->find();
            $this->keepThread();
            if ($layer_info['layer_number']) {
                $layer_num = $layer_info['layer_number'];
            } elseif(!preg_match('/[\x7f-\xff]/',$layer_info['layer_name'])){
                $layer_num = $layer_info['layer_name'];
            }else {
                if ($layer_info['layer_number']) {
                    $layer_num = $layer_info['layer_number'];
                } else {
                    $this->keepThread();
                    $layer_num = $a5Service->findNum($layer_info['layer_name']);
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
                    $this->keepThread();
                    $room_num = $a5Service->findNum($room_info['room_number']);
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
            $userTimes =  D('House_village_user_bind')->getUserEndTime($user_bind_id);
            if (isset($userTimes['propertyEndTime'])&&$userTimes['propertyEndTime']>0) {
                $userArr['end_time'] = intval($userTimes['propertyEndTime']);
            } else {
                $userArr['end_time'] = 0;
            }
            if ($userArr['uid']) {
                $this->keepThread();
                $imgUrlInfo = D('House_face_img')->where(['uid'=>$userArr['uid'], 'status' => ['in', [0,3]]])->order('id desc')->field('img_url,status')->find();
            }
            $userArr['device_id'] = $device_id;
            if($userArr && $imgUrlInfo)
            {
                $imgUrl = $imgUrlInfo['img_url'];
                $this->keepThread();
                if (3==$imgUrlInfo['status']) {
                    import('@.ORG.face.word_encryption');
                    $word_encryption = new word_encryption();
                    // 针对加密图片解密
                    $imgUrl = $word_encryption->text_decrypt($imgUrl);
                }
                fdump_api(['1单用户同步>>>'.__LINE__,$imgUrl,$userArr,$userTimes], 'a5/addMemberSingleUserArr', true);
                $this->keepThread();
                try {
                    $imgUrl = D('Image')->transform_image($imgUrl);
                    fdump_api(['2单用户同步>>>'.__LINE__,$imgUrl,$userArr], 'a5/addMemberSingleUserArr', true);
                    $this->keepThread();
                    $img_url = thumb_img($imgUrl, 640, 480, '', true);
                    $imagesize = getimagesize($img_url);
                    if ($imagesize[0]&&$imagesize[0]<480) {
                        // 如果压缩出来的图片小于宽度480  执行裁剪
                        $img_url = thumb_img($imgUrl,480,640,'fill',true);
                    }
                    $userArr['img_url'] = $img_url;
                } catch( \Exception $e) {
                    fdump_api(['2单用户同步图片错误>>>'.__LINE__,$e->getMessage()], 'a5/addMemberSingleUserArr', true);
                }
                //添加人员 云设备
                fdump_api(['<<<<3单用户同步'.__LINE__,$img_url, $village_id,$device_id,$user_bind_id,$floor_id,$imgUrl, $imgUrlInfo], 'a5/addMemberSingleUserArr', true);
                usleep($this->loopUSleepTime);// 避免超频调用
                $this->keepThread();
                $res = $this->addMembers($userArr);
                return $res;
            } elseif ($userArr) {
                usleep($this->loopUSleepTime);// 避免超频调用
                $this->keepThread();
                $res = $this->addMembers($userArr);
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
    public function addMembers($postArr,$bind_type=0,$device_type=5,$repeat=0)
    {
        $where['bind_type'] = $bind_type;
        $where['bind_id'] = $postArr['pigcms_id'];
        $where['device_type'] = $device_type;
        //本地用户表
        $this->keepThread();
        $userInfo = D('Face_user_bind_device')->where($where)->find();
        $postArr['validBeginTime'] = date('Y-m-d',time());
        $postArr['validEndTime'] = date('Y-m-d',time()+3652*24*3600);
        $a5Service = D('Face_door_a5_service');
        if(!$userInfo) {
            if ($postArr['img_url']) {
                $this->keepThread();
                $postArr['img_url'] = $a5Service->imgtobase64($postArr['img_url']);
            }
            $url = $a5Service->a5_face_class->addMember;
            $arr = [
                'employeeNo' => $postArr['pigcms_id'],
                'personName' => $a5Service->replaceWord_($postArr['pigcms_id'],$postArr['name'],$postArr['phone']),
            ];
            $this->keepThread();
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
                    $now_time = $postArr['end_time'] - 24*3600;
                }
                $arr['validEndTime']=date('Y-m-d',$postArr['end_time']);
            } else {
                // 没有截止时间 默认延期10年
                $arr['validEndTime'] = date('Y-m-d',time()+3652*24*3600);
            }
            if (isset($postArr['start_time']) && $postArr['start_time']) {
                $arr['validBeginTime'] = date('Y-m-d',$postArr['start_time']);
            } else {
                $arr['validBeginTime'] = date('Y-m-d',$now_time);
            }
            $arr = json_encode($arr,JSON_UNESCAPED_SLASHES);
            $info = $a5Service->a5_face_class->curlPost($url, $arr, 1, $a5Service->token);
            $this->keepThread();

            $fArr = $arr;
            if (isset($fArr['faceImageBase64'])) {
                unset($fArr['faceImageBase64']);
            }
            $fPostArr = $postArr;
            if (isset($fPostArr['img_url'])) {
                unset($fPostArr['img_url']);
                $fPostArr['img_url'] = '有图片看前置接口';
            }
            fdump_api(['a5添加成员[请求接口]'.__LINE__,$url, $fArr, $a5Service->token, $info],'a5/addMembersLog',1);

            if($info['code'] == 200){
                if($postArr['ic_card']){
                    //新增卡片
                    $this->keepThread();
                    $info = $a5Service->addCard($info['data']['employeeNo'],$postArr['ic_card'],$bind_type);
                    fdump_api(['a5添加卡片[人员添加成功]'.__LINE__,$info, $fPostArr, $bind_type, $info],'a5/addCardLog',1);
                }
                $dataArr = [
                    'bind_id'=>$postArr['pigcms_id'],
                    'device_type'=>$device_type,
                    'info'=>serialize($info),
                    'add_time'=>time(),
                ];
                D('Face_user_bind_device')->data($dataArr)->add();

                $this->keepThread();
                D('House_village_user_bind')->where(array('pigcms_id' => $postArr['pigcms_id']))->save(array('face_img_status' => 1, 'face_img_reason' => ''));
                fdump_api(['a5添加人员成功'.__LINE__,$info, $fPostArr, $bind_type, $info],'a5/addMembersSuccessLog',1);
                return $info;
            }elseif ($info['code'] == 40013001){//用户已存储到云，本地没有的情况
                //修改云信息
                usleep($this->loopUSleepTime);// 避免超频调用
                $this->keepThread();
                $yunData = $a5Service->getMember($postArr['pigcms_id']);
                fdump_api(['a5获取人员[用户已存储到云，本地没有的情况]'.__LINE__, $userInfo,$yunData,$info],'a5/errAddMembersGetUserLog',1);

                if($yunData['code'] == 200){
                    $dataArr = [
                        'bind_id'=>$postArr['pigcms_id'],
                        'device_type'=>$device_type,
                        'info'=>serialize($info),
                        'add_time'=>time(),
                    ];
                    D('Face_user_bind_device')->data($dataArr)->add();
                    $this->keepThread();
                    if(empty($yunData['data']['validBeginTime'])){
                        $postArr['validBeginTime'] = date('Y-m-d',time());
                    }
                    if(empty($yunData['data']['validEndTime'])){
                        $postArr['validEndTime'] = date('Y-m-d',time()+3652*24*3600);
                    }
                    usleep($this->loopUSleepTime);// 避免超频调用
                    $this->keepThread();
                    if (isset($yunData['data']['faceUrl']) && $yunData['data']['faceUrl'] && $postArr['img_url']) {
                        $face_img_reason = D('House_village_user_bind')->where(array('pigcms_id' => $postArr['pigcms_id']))->getField('face_img_reason');
                        if ($face_img_reason) {
                            unset($postArr['img_url']);
                            $postArr['face_img'] = $postArr['img_url'];
                            fdump_api(['a5二次同步图片异常不再同步'.__LINE__, $postArr, $yunData],'a5New/noticeNoImgEditMemberLog',1);
                        }
                        $this->keepThread();
                    }
                    $msg = $a5Service->editMember($postArr);
                    fdump_api(['a5去编辑人员[用户已存储到云，本地没有记录并编辑]'.__LINE__, $fPostArr, $bind_type, $msg],'a5/editMemberLog',1);
                }elseif (isset($yunData['code'])){
                    //云不存在，就删除本地重新添加
                    D('Face_user_bind_device')->where($where)->delete();
                    if (!$repeat) {
                        usleep($this->loopUSleepTime);// 避免超频调用
                        $this->keepThread();
                        $msg = $this->addMembers($postArr,$bind_type,$device_type,1);
                        fdump_api(['a5删除本地数据后重新添加人员'.__LINE__, $fPostArr, $bind_type, $msg],'a5/addMembersLog',1);
                    }
                }
                return $userInfo;
            }else{
                D('House_village_user_bind')->where(array('pigcms_id' => $postArr['pigcms_id']))->save(array('face_img_status' => 2, 'face_img_reason' => $info['message'] ? : '海康未返回错误信息，可重试同步'));
                fdump_api(['a5添加人员其他错误'.__LINE__, $fPostArr, $bind_type, $info],'a5/errAddMembersLog',1);
                return $info;
            }
        }else{
            //修改云信息
            usleep($this->loopUSleepTime);// 避免超频调用
            $this->keepThread();
            $yunData = $a5Service->getMember($userInfo['bind_id']);
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
                usleep($this->loopUSleepTime);// 避免超频调用
                $this->keepThread();
                if (isset($yunData['data']['faceUrl']) && $yunData['data']['faceUrl'] && $postArr['img_url']) {
                    $face_img_reason = D('House_village_user_bind')->where(array('pigcms_id' => $postArr['pigcms_id']))->getField('face_img_reason');
                    if ($face_img_reason) {
                        unset($postArr['img_url']);
                        $postArr['face_img'] = $postArr['img_url'];
                        fdump_api(['a5二次同步图片异常不再同步'.__LINE__, $postArr, $yunData],'a5New/noticeNoImgEditMemberLog',1);
                    }
                    $this->keepThread();
                } elseif (isset($yunData['data']['faceUrl']) && $yunData['data']['faceUrl'] && $postArr['img_url']==$userInfo['face_img']) {
                    unset($postArr['img_url']);
                    $postArr['face_img'] = $postArr['img_url'];
                    fdump_api(['a5二次同步图片异常不再同步'.__LINE__, $postArr, $yunData],'a5New/noticeNoImgEditMemberLog',1);
                }
                $msg = $a5Service->editMember($postArr,$bind_type);
                fdump_api(['a5去编辑人员'.__LINE__, $postArr, $bind_type, $msg],'a5/editMemberLog',1);
            }elseif (isset($yunData['code'])){
                //云不存在，就删除本地重新添加
                D('Face_user_bind_device')->where($where)->delete();
                if (!$repeat) {
                    usleep($this->loopUSleepTime);// 避免超频调用
                    $this->keepThread();
                    $msg = $this->addMembers($postArr,$bind_type,$device_type,1);
                }
                fdump_api(['a5删除本地数据后重新添加人员'.__LINE__, $postArr, $bind_type, $msg],'a5/addMembersLog',1);
            }
            return $userInfo;
        }
    }
}
?>