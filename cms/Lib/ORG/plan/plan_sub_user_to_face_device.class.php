<?php

/**
 * 下发人员信息至相关设备处理
 */
class plan_sub_user_to_face_device extends plan_base{
    // 支持过期同步的设备类型
    protected $synOutTimeType = [3,5,6,29,24,61];

    // open_status 1 可以开门  3 欠物业费
    public function runTask($param){
        fdump_api(['1信息：'.__LINE__,$param,microtime(true)],'plan_sub/subUserToFaceDevice',1);
        //删除计划任务
        if($param['plan_task_id']){
            $this->dropPlan($param['plan_task_id']);
        }
        // todo 修复计划任务
        $uid = isset($param['uid'])&&$param['uid']?intval($param['uid']):0;// 平台用户uid
        $pigcms_id = isset($param['pigcms_id'])&&$param['pigcms_id']?intval($param['pigcms_id']):0;// 小区住户身份绑定id
        $synOut = !isset($param['synOut']) || $param['synOut'];// 是否同步服务时间过期信息至设备
        $orderGroupId = isset($param['orderGroupId'])&&$param['orderGroupId']?trim($param['orderGroupId']):md5(uniqid().time());// 标记统一执行命令,没有就自行标记
        if (!$uid && !$pigcms_id) {
            fdump_api(['由于必要信息不全无法同步：'.__LINE__,$param],'plan_sub/errSubUserToFaceDevice',1);
            return true;
        }
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        $this->keepThread();
        $m_house_worker = M('House_worker');
        $m_house_face_device = M('House_face_device');
        $d_house_village_user_bind = D('House_village_user_bind');
        $d_house_face_img = D('House_face_img');
        $where = [];
        if ($pigcms_id>0) {
            $where['a.pigcms_id'] = $pigcms_id;
        }
        if ($uid>0) {
            $where_user = ['uid'=>$uid];
            $now_user = D('User')->where($where_user)->field('uid,phone')->find();
            $where['a.uid'] = $uid;
        } else {
            $now_user = [];
        }
        $where['a.status'] = 1;
        $where['b.status'] = 1;
        $this->keepThread();
        $field = array(
            'a.pigcms_id','a.parent_id','a.type', 'a.village_id', 'a.floor_id','a.vacancy_id', 'a.property_endtime',
            'a.phone','a.open_door_day', 'b.owe_property_open_door', 'b.owe_property_open_door_day','a.third_id',
        );
        $aUserSelect = $d_house_village_user_bind->alias('a')
            ->join('LEFT JOIN pigcms_house_village as b on b.village_id=a.village_id')
            ->group('a.pigcms_id')
            ->field($field)
            ->where($where)
            ->order('a.type DESC')
            ->select();
        $this->keepThread();
        // 查询出来的门禁信息存储
        $door_list_info = array();
        // 查询出来的可以开门的信息存储
        $aDoor_is_open = array();
        // 存储信息
        $bind_info = array();
        // 百度参数
        $baidu_ai_appid = C('config.baidu_ai_appid');
        // 存不存在A1
        $is_have_a1 = false;
        if (!empty($aUserSelect)) {
            $now_time = time();
            $baiduPlan = [];// 迁移人脸图片至百度人脸库 避免重复迁移
            foreach ($aUserSelect as $k => $v) {
                if(isset($v['open_door_day']) && $v['open_door_day']>0){
                    $v['owe_property_open_door_day']=$v['open_door_day'];
                }
                $this->keepThread();
                if ((!$v['floor_id'] || !$v['vacancy_id']) && in_array($v['type'],[0,1,2,3])) {
                    // 如果是 业主 家属 租客类型 且未关联单元和房屋的 自动放弃同步
                    fdump_api(['未关联单元和房屋的：'.__LINE__,$v,$param],'plan_sub/errSubUserToFaceDeviceNoFloorRoom',1);
                    continue;
                }
                // 处理下  如果是
                $baiduKey = $v['village_id'].'_'.$uid;
                if ($v['village_id'] && $uid && $baidu_ai_appid && !isset($baiduPlan[$baiduKey])) {
                    $d_house_face_img->create_baidu_ai_face_plan($v['village_id'], 'baidu_face_edit', $uid);
                    $baiduPlan[$baiduKey] = 1;
                    $this->keepThread();
                }
                // 避免重复查询门禁信息
                if ($door_list_info && $door_list_info[$v['village_id']]) {
                    $aDoorList = $door_list_info[$v['village_id']];
                } else {
                    $condition_door = [];
                    $condition_door['village_id'] = $v['village_id'];
                    $condition_door['is_del'] = 0;
                    $condition_door['thirdProtocol'] = ['not in', [12,13]];
                    $faceDoorField = 'device_id,device_name,device_type,device_sn,floor_id,public_area_id,village_id,thirdProtocol';
                    $aDoorList = $m_house_face_device->field($faceDoorField)->where($condition_door)->select();
                    $door_list_info[$v['village_id']] = $aDoorList;
                    $this->keepThread();
                }
                if ($v['type'] == 4 && ($now_user['phone'] || $v['phone'])) {
                    // 获取工作人员信息
                    $phoneWork = $v['phone']?$v['phone']:$now_user['phone'];
                    $where_work = array('phone'=>$phoneWork,'status'=>array('neq',4), 'village_id' => $v['village_id']);
                    $work_field = 'village_id,wid,phone,status,open_door';
                    $worker_info = $m_house_worker->where($where_work)->field($work_field)->find();
                    $this->keepThread();
                    if (($worker_info&&$worker_info['open_door'] == 1) || (isset($v['third_id'])&&$v['third_id'])) {
                        $work_open_status = 1;
                        foreach ($aDoorList as $kk => &$vv) {
                            $this->keepThread();
                            if ($vv['device_type']==1 && !$is_have_a1) {
                                $is_have_a1 = true;
                            }
                            if ($vv['device_type']==5 && intval($vv['thirdProtocol']) > 0 && intval($vv['thirdProtocol']) != 11) {
                                continue;
                            }
                            if (!empty($aDoor_is_open) && isset($aDoor_is_open[$vv['device_id']]) && !empty($aDoor_is_open[$vv['device_id']])) {
                                // 门禁信息已经查询过，直接赋予新的状态
                                if (empty($aDoor_is_open[$vv['device_id']]['bind_arr_id'])) {
                                    $aDoor_is_open[$vv['device_id']]['bind_arr_id'] = array();
                                }
                                $aDoor_is_open[$vv['device_id']]['bind_arr_id'][] = $v['pigcms_id'];
                                $aDoor_is_open[$vv['device_id']]['door_end_time'] = 0; // 工作人员开门权限无时间限制
                                $aDoor_is_open[$vv['device_id']]['door_user_type'] = 3;
                                continue;
                            }
                            if ($vv['floor_id'] != "-1") {
                                $vv['door_type'] = 1; // 小区单元门
                            } else {
                                $vv['door_type'] = 2; // 小区大门
                            }
                            $vv['door_user_type'] = 3; // 工作人员
                            $vv['door_end_time'] = 0;
                            $vv['open_status'] = $work_open_status;
                            if (empty($vv['bind_arr_id'])) {
                                $vv['bind_arr_id'] = array();
                            }
                            $vv['bind_arr_id'][] = $v['pigcms_id'];
                            $aDoor_is_open[$vv['device_id']] = $vv;
                        }
                    }
                } else {
                    $parentField = 'pigcms_id,uid,phone,status,property_starttime,property_endtime';
                    $isChildren = false;
                    if (in_array($v['type'],[1,2,5])) {
                        $isChildren = true;
                    }
                    $userEndTime = -1;
                    if ($v['parent_id'] && $isChildren && isset($bind_info[$v['parent_id']]) && $bind_info[$v['parent_id']] && isset($bind_info[$v['parent_id']]['userEndTime'])) {
                        $userEndTime = $bind_info[$v['parent_id']]['userEndTime'];
                    } elseif ($v['parent_id'] && $isChildren && isset($bind_info[$v['parent_id']]) && $bind_info[$v['parent_id']]) {
                        $parentInfo = $bind_info[$v['parent_id']]['property_endtime'];
                    } else {
                        $parentInfo = [];
                    }
                    if ($userEndTime==-1) {
                        $this->keepThread();
                        $endParam = [
                            'type' => $v['type'],'parent_id' => $v['parent_id'],'endTime' => $v['property_endtime'],
                            'owe_property_open_door' => $v['owe_property_open_door'],'owe_property_open_door_day' => $v['owe_property_open_door_day'],
                        ];
                        try {
                            $info = $d_house_village_user_bind->getUserEndTime($v['pigcms_id'],true,$endParam,$parentInfo,$parentField);
                        }catch (Exception $e) {
                            fdump_api(['获取物业到期时间错误：'.__LINE__,$e->getMessage(),$v['pigcms_id'],$endParam,$parentInfo,$parentField,microtime(true)],'plan_sub/errGetUserEndTime',1);
                            continue;
                        }
                        if (empty($info)) {
                            fdump_api(['要同步的用户不存在：'.__LINE__,$info,$v,$param,$endParam],'plan_sub/errSubUserToFaceDeviceNoUser',1);
                            continue;
                        }
                        $userEndTime = isset($info['propertyEndTime'])&&$info['propertyEndTime']?$info['propertyEndTime']:0;
                    }
                    // 开门到期时间--即用户的服务到期时间 包含物业允许延期时间算在内
                    $v['userEndTime'] = $userEndTime;
                    if (!isset($bind_info[$v['pigcms_id']]) || !$bind_info[$v['pigcms_id']]) {
                        $bind_info[$v['pigcms_id']] = $v;
                    }
                    // 绑定的单元-即拥有该小区的大门和该单元门禁权限
                    $user_floor_id = $v['floor_id'];
                    if (!$userEndTime) {
                        $ban_open_status = false;   // 是否禁止开门  false 可以开门  true 禁止开门
                    } elseif ($userEndTime>$now_time) {
                        $ban_open_status = false;   // 服务时间超过当前时间 可以开门
                    } elseif ($userEndTime<$now_time) {
                        $ban_open_status = true;   // 服务时间已过期 不可以开门
                    }
                    foreach ($aDoorList as $kk => &$vv) {
                        $this->keepThread();
                        if ($vv['device_type']==1 && !$is_have_a1) {
                            $is_have_a1 = true;
                        }
                        if ($vv['device_type']==5 && intval($vv['thirdProtocol']) > 0 && intval($vv['thirdProtocol']) != 11) {
                            continue;
                        }
                        $floor_arr = [];
                        if ($vv['floor_id'] != "-1") {
                            $floor_arr = explode(',',$vv['floor_id']);
                            $vv['door_type'] = 1; // 小区单元门
                        } else {
                            $vv['door_type'] = 2; // 小区大门
                        }
                        if ($isChildren) {
                            $vv['door_user_type'] = 4; // 家属
                        } else {
                            $vv['door_user_type'] = 1; // 业主
                        }
                        // 个别设备时间过期也继续同步  便于更改过期时间
                        if ($synOut && in_array($vv['device_type'],$this->synOutTimeType)) {
                            $ban_open_status = false;
                        }
                        $vv['door_end_time'] = $userEndTime>0?date('Y-m-d H:i', $userEndTime):0;
                        // 处理判断凭依 是否可以开门
                        $judge = 0;
                        if (!$ban_open_status && $vv['floor_id'] <= 0) {
                            // 公开区域门禁 不被禁止开门即可开门
                            $judge = 1;
                            $is_open = true;
                        } elseif (!$ban_open_status && !$vv['floor_id'] && $vv['public_area_id']) {
                            // 公开区域门禁 不被禁止开门即可开门
                            $judge = 2;
                            $is_open = true;
                        }elseif (!$ban_open_status && ($user_floor_id==0 || $vv['floor_id'] == $user_floor_id || in_array($user_floor_id, $floor_arr))) {
                            // 不被禁止开门  且归属于对应门禁单元的用户（特殊情况存在下的单元未归属）
                            $judge = 3;
                            $is_open = true;
                        } else {
                            // 其他情况不可开门
                            $judge = 4;
                            $is_open = false;
                        }
                        $this->keepThread();
                        if ($is_open) {
                            if (empty($aDoor_is_open) || !isset($aDoor_is_open[$vv['device_id']]) || empty($aDoor_is_open[$vv['device_id']])) {
                                $vv['open_status'] = 1;
                                $vv['open_status_txt'] = L_('可以打开');
                                if (empty($vv['bind_arr_id'])) {
                                    $vv['bind_arr_id'] = array();
                                }
                                $vv['bind_arr_id'][] = $v['pigcms_id'];
                                $aDoor_is_open[$vv['device_id']] = $vv;
                            } else {
                                if (empty($aDoor_is_open[$vv['device_id']]['bind_arr_id'])) {
                                    $aDoor_is_open[$vv['device_id']]['bind_arr_id'] = array();
                                }
                                $aDoor_is_open[$vv['device_id']]['bind_arr_id'][] = $v['pigcms_id'];
                            }
                            $aDoor_is_open[$vv['device_id']]['door_user_type'] = 1;
                            fdump_api(['用户可同步门禁：'.__LINE__,$v,$vv,$aDoor_is_open,$judge,microtime(true)],'plan_sub/subUserToFaceDevice',1);
                        } else {
                            fdump_api(['用户不可同步门禁：'.__LINE__,$v,$vv,$judge,microtime(true)],'plan_sub/subUserToFaceDevice',1);
                        }
                    }
                }
            }
        } else {
            fdump_api(['无同步对象：'.__LINE__,$aUserSelect,$d_house_village_user_bind->_sql(),microtime(true)],'plan_sub/noticeSubUserToFaceDevice',1);
        }
        if (!empty($aDoor_is_open)) {
            $config_data = D('Config_data')->get_config_data();
            $a4_to_new_traits_job = isset($config_data['a4_to_new_traits_job']) ? intval($config_data['a4_to_new_traits_job']) : 0;
            $toNewA4 = [];
            $aDoor_is_open = array_values($aDoor_is_open);
            //A4 是同步到设备方 实际和设备 不直接关联同步 所以重复的不用同步 浪费资源
            $repeat = [];
            $d_house_village_face_door = D('House_village_face_door');
            $d_face_door_a4_service = D('Face_door_a4_service');
            $d_face_door_a5_service = D('Face_door_a5_service');
            $d_face_door_a7_service = D('Face_door_a7_service');
            $d_face_door_d1_service = D('Face_door_d1_service');
            $d_face_d3_request_record = D('Face_d3_request_record');
            $d_face_d4_request_record = D('Face_d4_request_record');
            $d_face_a185_request_record = D('Face_a185_request_record');
            $face_door_xihan_service = D('Face_door_xihan_service');
            fdump_api(['msg' => '12同步对象', 'line' => __LINE__, 'a4_to_new_traits_job' => $a4_to_new_traits_job, 'aDoor_is_open' => $aDoor_is_open, 'time' => microtime(true)], 'plan_sub/noticeSubUserToFaceDevice', 1);
            foreach ($aDoor_is_open as $val) {
                $this->keepThread();
                if ($val['device_type']==5 && intval($val['thirdProtocol']) > 0 && intval($val['thirdProtocol']) != 11) {
                    continue;
                }
                if (!empty($val['bind_arr_id']) && is_array($val['bind_arr_id'])) {
                    $bind_arr_id = array_unique($val['bind_arr_id']);
                    switch ($val['device_type']) {
                        case 1:
                            /**A1智能门禁机*/
                            try {
                                $d_house_face_img->add_person($val['device_id'],$bind_arr_id,$val['device_sn']);
                            }catch (Exception $e) {
                                fdump_api([$val,$param,$e->getMessage()],'a1/a1_door_is_open_err',1);
                            }
                            break;
                        case 2:
                            /**A2智能门禁机*/
                            try {
                                $d_house_village_face_door->modify_arr($bind_arr_id);
                            }catch (Exception $e) {
                                fdump_api([$val,$param,$e->getMessage()],'a2/a2_door_is_open_err',1);
                            }
                            break;
                        case 3:
                            /**A3智能门禁机*/
                            try {
                                $d_house_village_face_door->a3_arr_person_msg($val['device_id'],$bind_arr_id,$val['device_sn']);
                            }catch (Exception $e) {
                                fdump_api([$val,$param,$e->getMessage()],'a3/a3_door_is_open_err',1);
                            }
                            break;
                        case 4:
                            /**A4智能门禁机*/
                            if ($repeat) {
                                $diff = array_diff($bind_arr_id, $repeat);
                                if (!$diff) {
                                    break;
                                }
                            }
                            if ($a4_to_new_traits_job) {
                                $toNewA4[] = [
                                    'device_id' => $val['device_id'], 'bind_arr_id' => $val['bind_arr_id']
                                ];
                            } else {
                                try {
                                    $d_face_door_a4_service->a4_arr_person_msg($val['device_id'], $bind_arr_id);
                                    $repeat = array_merge($repeat, $val['bind_arr_id']);
                                } catch (Exception $e) {
                                    fdump_api([$val, $param, $e->getMessage()], 'A4/a4_door_is_open_err', 1);
                                }
                            }
                            break;
                        case 5:
                        case 6:
                            /**A5A6智能门禁机*/
                            usleep(500000);//休眠500毫秒
                            try {
                                $d_face_door_a5_service->synDeviceDispose($val['device_id'],$bind_arr_id,$val['device_sn']);
                            }catch (Exception $e) {
                                fdump_api([$val,$param,$e->getMessage()],'a5/a5_door_is_open_err',1);
                            }
                            break;
                        case 7:
                            /**A7智能门禁机*/
                            try {
                                fdump_api(['22同步对象：'.__LINE__,$val['device_id'],$bind_arr_id,microtime(true)],'plan_sub/noticeSubUserToFaceDevice',1);
                                $d = $d_face_door_a7_service->a7_arr_person_msg($val['device_id'],$bind_arr_id);
                                fdump_api(['22同步对象：'.__LINE__,$d,microtime(true)],'plan_sub/noticeSubUserToFaceDevice',1);
                            }catch (Exception $e) {
                                fdump_api([$val,$param,$e->getMessage()],'plan_sub/noticeSubUserToFaceDevice',1);
                            }
                            break;
                        case 8:
                            /**A8智能门禁机*/
                            try {
                                D('Face_local_area_device')->synDeviceDispose($val['device_id'],$val['bind_arr_id']);
                            }catch (Exception $e) {
                                fdump_api([$val,$param,$e->getMessage()],'a8/a8_door_is_open_err',1);
                            }
                            break;
                        case 21:
                            /**D1智能门禁机*/
                            try {
                                $d_face_door_d1_service->d1_arr_person_msg($val['device_id'],$bind_arr_id,1);
                            }catch (Exception $e) {
                                fdump_api([$val,$param,$e->getMessage()],'D1/d1_door_is_open_err',1);
                            }
                            break;
                        case 23:
                            /**D3智能门禁机*/
                            try {
                                $d_face_d3_request_record->d3_arr_person_msg($val['device_id'],$bind_arr_id);
                            }catch (Exception $e) {
                                fdump_api([$val,$param,$e->getMessage()],'d3/d3_door_is_open_err',1);
                            }
                            break;
                        case 24:
                            /**todo D4智能门禁机*/
                            try {
                                $d_face_d4_request_record->d4_arr_person_msg($val['device_id'], $bind_arr_id);
                            } catch (Exception $e) {
                                fdump_api([$val,$param,$e->getMessage()],'d4/d4_door_is_open_err',1);
                            }
                            break;
                        case 61:
                            /**A185智能门禁机*/
                            try {
                                $d_face_a185_request_record->a185_arr_person_msg($val['device_id'],$bind_arr_id);
                            }catch (Exception $e) {
                                fdump_api([$val,$param,$e->getMessage()],'a185/a185_door_is_open_err',1);
                            }
                            break;
                        case 88:
                            /**大华人脸门禁*/
                            try {
                                D('Dahua_service')->dahua_arr_person_msg($val['device_id'],$bind_arr_id);
                                fdump_api(['人员数据写入>>'.__LINE__,$val['bind_arr_id']],'dahua/dahua_arr_person',1);
                            }catch (Exception $e) {
                                fdump_api($val,'dahua/dahua_door_is_open_err',true);
                                fdump_api($e->getMessage(),'dahua/dahua_door_is_open_err',true);
                            }
                        case 29:
                            /*** todo 朵普*/
                            try {
                                $models = '\app\community\model\service\CloudIntercomService/sendThirdUserToDeviceDeal';
                                $rr=invoke_v20_service($models,[
                                    'village_id'=>$val['village_id'],
                                    'deviceSn'=>$val['device_sn'],
                                    'thirdUser'=>$bind_arr_id,
                                    'operation'=>'add',
                                    'source'=>'house_village'
                                ]);
                                fdump_api(['朵普：'.__LINE__,$val,$rr],'duopu/v20_log',1);
                            } catch (Exception $e) {
                                fdump_api([$val,$param,$e->getMessage()],'duopu/open_err',1);
                            }
                            break;
                        case 31:
                            /**西函智能门禁*/
                            try {
                                $face_door_xihan_service->xihan_add_person($val['device_id'],$bind_arr_id);
                            }catch (Exception $e) {
                                fdump_api([$val,$param,$e->getMessage()],'xihan/plan_face_device_err',1);
                            }
                            break;

                            
                    }
                }
            }

            if (!empty($toNewA4)) {
                try {
                    $models = '\app\community\model\service\Device\A4DeviceService/a4ArrPersonMsgBox';
                    $invokeV20ServiceUrl = ['toNewA4' => $toNewA4];
                    $result = invoke_v20_service($models, $invokeV20ServiceUrl);
                    fdump_api(['toNewA4' => $toNewA4, 'result' => $result], 'A4DeviceService/a4ArrPersonMsgBoxLog', 1);
                } catch (Exception $e) {
                    fdump_api(['toNewA4' => $toNewA4, 'err' => $e->getMessage()], 'A4DeviceService/errA4ArrPersonMsgBox', 1);
                }
            }
        } else {
            fdump_api(['无对应符合条件的同步设备：'.__LINE__,$param],'plan_sub/errSubUserToFaceDeviceNoTo',1);
            if (empty($aUserSelect)) {
                if ($pigcms_id>0) {
                    $where = [];
                    $where['pigcms_id'] = $pigcms_id;
                    $status = D('House_village_user_bind')->where($where)->getField('status');
                    $statusArr = [0=>'禁止',2=>'审核中',3=>'删除',4=>'删除'];
                    if (isset($statusArr[$status])) {
                        D('House_village_user_bind')
                            ->where(array('pigcms_id' => $pigcms_id))
                            ->save(array('face_img_status' => 2, 'face_img_reason' => "目前处于【$statusArr[$status]】状态，无法同步，请前往【业主审核列表】进行处理"));
                    }
                }
            }
        }

        fdump_api(['信息：'.__LINE__,$aDoor_is_open,$param,microtime(true)],'plan_sub/subUserToFaceDevice',1);
        return true;
    }
}
?>