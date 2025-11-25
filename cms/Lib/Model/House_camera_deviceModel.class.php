<?php
/**
 * 摄像头
 * Class House_camera_deviceModel
 */

class House_camera_deviceModel extends Model{
    public $plan_plan_sub_device_syn = '';
    public $loopUSleepTime = '';
    public function _initialize(){
        parent::_initialize();
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

    public function addEquipment($camera_id,$camera_info=[],$otherParam=[])
    {
        if ($camera_id && empty($camera_info) || !$camera_info['village_id']) {
            $camera_info = $this->where(array('camera_id' => $camera_id))->find();
            if (empty($camera_info)) {
                fdump_api(['添加设备[对应设备不存在]'.__LINE__,$camera_id,$camera_info],'camera/errAddEquipmentLog',1);
                $info['error'] = 1;
                $info['msg'] = '参数错误，对应设备不存在！';
                return $info;
            }
        }
        $village_id = $camera_info['village_id'];
        $village_info = M('House_village')->where(['village_id'=> $village_id,'is_del'=>0])->field('village_id,village_name')->find();
        //同步云分组
        $groupName = $village_info['village_name'].'_监控';
        $groupNo = 'camera'.$village_id;
        $post_data = ['groupName' => $groupName,'groupNo' => $groupNo];
        $postData = json_encode($post_data,JSON_UNESCAPED_UNICODE);
        usleep($this->loopUSleepTime);// 避免超频调用
        $this->keepPlanThread();
        $d5Service = D('Face_door_a5_service');
        $groupInfo = $d5Service->getGroupInfo($groupNo);
        if($groupInfo['code'] == 200)
        {
            $resGroup = $groupInfo;
        }else{
            usleep($this->loopUSleepTime);// 避免超频调用
            $this->keepPlanThread();
            $resGroup = $d5Service->createGroup($postData);//同步云 创建分组
        }
        if ($resGroup['code'] == 200 || $resGroup['code'] == 510107) {
            $post_arr = [
                'deviceSerial' => $camera_info['camera_sn'],
                'groupNo' => $groupNo,
                'validateCode' => trim($camera_info['device_code']),
            ];
            $postArr = json_encode($post_arr);
            usleep($this->loopUSleepTime);// 避免超频调用
            $this->keepPlanThread();
            $logParam = [
                'sysType' => 'camera'
            ];
            $resData = $d5Service->createMachine($camera_id, $postArr, $logParam);
            fdump_api(['添加设备' . __LINE__, $camera_id, $postArr, $resData,$camera_info],'camera/addEquipmentLog', true);
            if ($resData['code'] == 200) {
                $info['error'] = 0;
                $info['msg'] = '同步设备成功！';
                return $info;
            } elseif ($resData['code'] == 40012003) {
                $this->where(array('camera_id' => $camera_id))->data(['camera_status' => 1])->save();
                $info['error'] = 1;
                $info['msg'] = '同步设备异常！';
                return $info;
            } elseif ($resData['code'] == '40012004' && $camera_info['cloud_device_id']) {
                $this->where(array('camera_id' => $camera_id))->data(['camera_status' => 0])->save();
                $info['error'] = 0;
                $info['msg'] = '同步设备成功！';
                return $info;
            } else {
                $this->where(array('camera_id' => $camera_id))->data(['camera_status' => 1])->save();
                fdump_api(['添加设备[异常]' . __LINE__, $camera_id, $postArr, $resData],'camera/errAddEquipmentLog', true);
                $info['error'] = 1;
                $info['msg'] = $resData['message'];
                return $info;
            }
        }else{
            $this->where(array('camera_id' => $camera_id))->data(['camera_status'=>1])->save();
            fdump_api(['添加设备[同步分组异常]'.__LINE__,$camera_id,$resGroup],'camera/errAddEquipmentLog',true);
            $info['error'] = 0;
            $info['msg'] = '同步分组异常！';
            return $info;
        }
    }

    public function setVideoOff($deviceSerial,$validateCode) {
        usleep($this->loopUSleepTime);// 避免超频调用
        $this->keepPlanThread();
        $d5Service = D('Face_door_a5_service');
        return $d5Service->setVideoOff($deviceSerial,$validateCode);
    }

    public function getApprove(){
        usleep($this->loopUSleepTime);// 避免超频调用
        $this->keepPlanThread();
        $d5Service = D('Face_door_a5_service');
        return $d5Service->getApprove();
    }


    public function geChannelList($deviceSerial, $device_id,$village_id=0, $isLiveVideoOpen=true, $pageNo=1,$pageSize=999){
        usleep($this->loopUSleepTime);// 避免超频调用
        $this->keepPlanThread();
        $d5Service = D('Face_door_a5_service');
        $list = $d5Service->getChannelList($deviceSerial,$pageNo,$pageSize);
        $logParam = [
            'msg' => '获取设备通道','$deviceSerial' => $deviceSerial,'$device_id' => $device_id,'$village_id' => $village_id,
            '$isLiveVideoOpen' => $isLiveVideoOpen,'$list' => $list,
        ];
        if (!$village_id) {
            $camera_info = $this->where(array('camera_sn' => $deviceSerial))->find();
            $village_id = $camera_info['village_id'];
        }
        fdump_api($logParam,'camera/geChannelList',1);
        if ($list && $list['data']['rows']) {
            $data = $list['data']['rows'];
            $nowTime = time();
            $db_house_device_channel = D('House_device_channel');
            $channelNos = [];
            $liveVideoOpenList = [];
            fdump_api($data,'camera/geChannelList',1);
            foreach ($data as $item) {
                $whereRepeat = [
                    'device_type' => 'house_camera_device',
                    'deviceSerial' => $deviceSerial,
                    'channelNo' => $item['channelNo'],
                    'isDel'=> 0,
                ];
                if ($village_id) {
                    $whereRepeat['village_id'] = $village_id;
                }
                $channel_id = $db_house_device_channel->where($whereRepeat)->getField('channel_id');
                $channelNos[] = $item['channelNo'];
                $liveVideoOpenList[$item['channelNo']] = ['channel_id' => $channel_id];
                if ($channel_id) {
                    $updateParam = [
                        'device_type' => 'house_camera_device',
                        'village_id' => $village_id,
                        'device_id' => $device_id,
                        'deviceSerial' => $deviceSerial,
                        'channelId' => $item['channelId'],
                        'channelName' => $item['channelName'],
                        'channelNo' => $item['channelNo'],
                        'channelStatus' => $item['channelStatus'],
                        'channelType' => $item['channelType'],
                        'isUse' => $item['isUse'],
                        'ipcSerial' => $item['ipcSerial'],
                        'update_time' => $nowTime,
                        'channel_txt' => json_encode($item,JSON_UNESCAPED_UNICODE),
                    ];
                    $db_house_device_channel->where(['channel_id'=> $channel_id])->save($updateParam);
                } else {
                    $addParam = [
                        'device_type' => 'house_camera_device',
                        'village_id' => $village_id,
                        'device_id' => $device_id,
                        'deviceSerial' => $deviceSerial,
                        'channelId' => $item['channelId'],
                        'channelName' => $item['channelName'],
                        'channelNo' => $item['channelNo'],
                        'channelStatus' => $item['channelStatus'],
                        'channelType' => $item['channelType'],
                        'isUse' => $item['isUse'],
                        'ipcSerial' => $item['ipcSerial'],
                        'add_time' => $nowTime,
                        'channel_txt' => json_encode($item,JSON_UNESCAPED_UNICODE),
                    ];
                    fdump_api($addParam,'camera/geChannelList',1);
                    $db_house_device_channel->add($addParam);
                }
            }
            if ($isLiveVideoOpen && !empty($channelNos)) {
                $this->liveVideoOpen($deviceSerial, $channelNos,$liveVideoOpenList,$village_id);
            }
        } elseif ($list['code']!=200) {
            $info['error'] = 1;
            $info['msg'] = $list['message'];
            return $info;
        }
        $info['error'] = 0;
        $info['msg'] = '同步设备成功！';
        return $info;
    }

    public function liveVideoOpen($deviceSerial, $channelNos=[],$liveVideoOpenList=[],$village_id=0) {
        usleep($this->loopUSleepTime);// 避免超频调用
        $this->keepPlanThread();
        $d5Service = D('Face_door_a5_service');
        $url = $d5Service->a5_face_class->liveVideoOpen;
        if (!$url) {
            $url = "https://api2.hik-cloud.com/api/v1/open/basic/channels/actions/live/video/open";
        }
        $arr = json_encode([
            "deviceSerial"=>$deviceSerial,
            "channelNos"=>$channelNos,
        ]);
        $openList = $d5Service->a5_face_class->curlPost($url,$arr,1,$d5Service->token);
        $logParam = [
            'msg' => '开通标准流预览功能','$deviceSerial' => $deviceSerial,'$channelNos' => $channelNos,'$liveVideoOpenList' => $liveVideoOpenList,
            '$village_id' => $village_id,'$url' => $url,'$token' => $d5Service->token,'$openList' => $openList,
        ];
        fdump_api($logParam,'camera/liveVideoOpen',1);
        if ($openList && $openList['data']) {
            $data = $openList['data'];
            $nowTime = time();
            $db_house_device_channel = D('House_device_channel');
            foreach ($data as $item) {
                $updateParam = [
                    'openReason' => $item['desc'],
                    'openCode' => $item['ret'],
                    'update_time' => $nowTime,
                ];
                if ($updateParam['ret']==200||$updateParam['ret']==60062) {
                    $updateParam['liveVideoOpen'] = 1;
                } else {
                    $updateParam['liveVideoOpen'] = 2;
                }
                $channel_id = 0;
                if (isset($item['channelNo'])&&isset($liveVideoOpenList[$item['channelNo']])&&isset($liveVideoOpenList[$item['channelNo']]['channel_id'])) {
                    $channel_id = $liveVideoOpenList[$item['channelNo']]['channel_id'];
                }
                $whereChannel = [];
                $whereChannel['isDel'] = 0;
                $whereChannel['device_type'] = 'house_camera_device';
                if ($village_id) {
                    $whereChannel['village_id'] = $village_id;
                }
                $itemDeviceSerial = isset($item['deviceSerial'])?$item['deviceSerial']:$deviceSerial;
                if ($channel_id>0) {
                    $whereChannel['channel_id'] = $channel_id;
                } else {
                    $whereChannel['deviceSerial'] = $itemDeviceSerial;
                    $whereChannel['channelNo'] = $item['channelNo'];
                }
                fdump_api($updateParam,'camera/liveVideoOpen',1);
                $saveJudge = $db_house_device_channel->where($whereChannel)->save($updateParam);
                if (!$saveJudge) {
                    $logParam['msg'] = '更新通道信息';
                    $logParam['$whereChannel'] = $whereChannel;
                    $logParam['$updateParam'] = $updateParam;
                    fdump_api($logParam,'camera/errUpdateLiveVideoOpen',1);
                }
                $this->liveGetVideoUrl($item['channelNo'],$deviceSerial,$village_id,$channel_id);
            }
        } elseif ($openList['code']!=200) {
            fdump_api($logParam,'camera/errLiveVideoOpen',1);
            $info['error'] = 1;
            $info['msg'] = $openList['message'];
            return $info;
        }
        $info['error'] = 0;
        $info['msg'] = '同步设备成功！';
        return $info;
    }

    public function liveGetVideoUrl($channelNo,$deviceSerial,$village_id=0,$channel_id=0,$protocol=4,$quality=2,$expireTime=86400) {
        usleep($this->loopUSleepTime);// 避免超频调用
        $this->keepPlanThread();
        $d5Service = D('Face_door_a5_service');
        $url = $d5Service->a5_face_class->liveGetVideoUrl;
        if (!$url) {
            $url = "https://api2.hik-cloud.com/v1/carrier/wing/endpoint/video/getVideoUrl";
        }
        $url .= "?channelNo={$channelNo}&deviceSerial={$deviceSerial}&protocol={$protocol}&quality={$quality}&expireTime={$expireTime}";
        $list = $d5Service->a5_face_class->curlGet($url,$d5Service->token);
        $logParam = [
            'msg' => '获取标准流预览地址','$channelNo' => $channelNo,'$deviceSerial' => $deviceSerial,'$village_id' => $village_id,
            '$channel_id' => $channel_id,'$url' => $url,'$token' => $d5Service->token,'$list' => $list,
        ];
        fdump_api($logParam,'camera/liveGetVideoUrl',1);
        if ($list && $list['data']) {
            $data = $list['data'];
            $updateParam = [];
            if (isset($data['id'])) {
                $updateParam['lookId'] = $data['id'];
            }
            if (isset($data['url'])) {
                $updateParam['lookUrl'] = $data['url'];
            }
            if (isset($data['expireTime'])) {
                $updateParam['urlExpireTime'] = $expireTime;
                $updateParam['urlEndTime'] = strtotime($data['expireTime']);
            }
            $whereChannel = [];
            $whereChannel['isDel'] = 0;
            $whereChannel['device_type'] = 'house_camera_device';
            if ($village_id) {
                $whereChannel['village_id'] = $village_id;
            }
            if ($channel_id>0) {
                $whereChannel['channel_id'] = $channel_id;
            } else {
                $whereChannel['deviceSerial'] = $deviceSerial;
                $whereChannel['channelNo'] = $channelNo;
            }
            $db_house_device_channel = D('House_device_channel');
            $saveJudge = $db_house_device_channel->where($whereChannel)->save($updateParam);
            if (!$saveJudge) {
                $logParam['msg'] = '更新通道-预览地址';
                $logParam['$whereChannel'] = $whereChannel;
                $logParam['$updateParam'] = $updateParam;
                fdump_api($logParam,'camera/errUpdateLiveGetVideoUrl',1);
            }
        } elseif ($list['code']!=200) {
            fdump_api($logParam,'camera/errLiveGetVideoUrl',1);
            $info['error'] = 1;
            $info['msg'] = $list['message'];
            return $info;
        }
        $info['error'] = 0;
        $info['msg'] = '同步设备成功！';
        return $info;
    }


    /**
     * 摄像头通道列表
     * @param $where
     * @param $page
     * @param $page_count
     * @return array|int
     */
    public function get_house_device_channel_list($where,$page,$page_count){
        if(!$where){
            return	array();
        }
        if(!isset($where['isDel'])) {
            $where['isDel'] = 0;
        }
        $count_arr = M('House_device_channel')->where($where)->count();
        if(!$count_arr){
            return	array();
        }
        $sort = '`channel_id` ASC';
        $acamera	=	M('House_device_channel')->field(true)->page($page,$page_count)->where($where)->order($sort)->select();
        if($acamera){
            import('@.ORG.merchant_page');
            $p = new Page($count_arr,$page_count,'page');
            $channelStatusArr = ['0'=>'离线','1'=>'在线','-1'=>'设备未上报'];
            $isUseArr = ['0'=>'禁用','1'=>'启用'];// 通道启用状态：0-禁用；1-启用
            foreach($acamera as &$item){
                if (isset($item['channelStatus'])) {
                    $item['channelStatusTxt'] = isset($channelStatusArr[$item['channelStatus']])?$channelStatusArr[$item['channelStatus']]:'未知';
                }
                if (isset($item['isUse'])) {
                    $item['isUseTxt'] = isset($isUseArr[$item['channelStatus']])?$isUseArr[$item['isUse']]:'未知';
                }
            }
            $arr = array(
                'list'	=>	isset($acamera)?$acamera:array(),
                'pagebar'	=>	$p->show(),
            );
            return	$arr;
        }else{
            return	2;
        }
    }


    public function getCameraReplyInfo($village_id,$camera_id,$pigcms_id) {
        $db_house_camera_reply = M('House_camera_reply');
        $where = [];
        $where['village_id'] = $village_id;
        $where['pigcms_id'] = $pigcms_id;
        $where['camera_id'] = $camera_id;
        $reply = $db_house_camera_reply->where($where)->order('add_time DESC')->find();
        if(empty($reply)){
            return ['status'=>0,'tip'=>'您没有相应的权限','btn' => '去申请','btnUrl' => "/pages/village/index/writeApplication?pigcms_id={$pigcms_id}&camera_id={$camera_id}&village_id={$village_id}"];
        }
        if($reply['reply_status'] == 1){
            return ['status'=>1,'tip'=>'查看权限审核中','btn' => '好的','btnUrl' => ""];
        }
        if($reply['reply_status'] == 2){
            if($reply['start_time'] && $reply['end_time']){
                if(time() < $reply['start_time']){
                    $start_time_txt = date('Y-m-d H:i', $reply['start_time']);
                    $end_time_txt = date('Y-m-d H:i', $reply['end_time']);
                    return ['status'=>4,'tip'=>"您当前可以在【{$start_time_txt}-{$end_time_txt}】进行查看",'btn' => '好的','btnUrl' => ""];
                } elseif(time() > $reply['end_time']){
                    return ['status'=>4,'tip'=>"您当前超出允许观看的时间范围",'btn' => '去申请','btnUrl' => "/pages/village/index/writeApplication?pigcms_id={$pigcms_id}&camera_id={$camera_id}&village_id={$village_id}"];
                }
            }
            return ['status'=>2,'tip'=>'申请成功','btn' => '好的','btnUrl' => ""];
        }
        if($reply['reply_status'] == 3){
            $tip = '申请被拒绝';
            if (isset($reply['reason'])&&$reply['reason']) {
                $tip .= "({$reply['reason']})";
            }
            return ['status'=>3,'tip'=>$tip,'btn' => '重新申请','btnUrl' => "/pages/village/index/writeApplication?pigcms_id={$pigcms_id}&camera_id={$camera_id}&village_id={$village_id}"];
        }
        return ['status'=>2,'tip'=>'申请成功','btnUrl' => ""];
    }
}