<?php
/**
 * Created by PigCMS.
 * User: wanzy
 * Date: 2019/9/3.
 * Time: 15:16.
 */
class House_face_imgModel extends Model{
    // 一个人员同时可存在的人脸图片数上限
    public $userMaxFaceImgNum = 5;

    /**
     * 获取access token url
     * @var string
     */
    protected $accessTokenUrl = 'https://aip.baidubce.com/oauth/2.0/token';

    /**
     * 人脸注册 url
     * @var string
     */
    protected $baidu_face_add = 'https://aip.baidubce.com/rest/2.0/face/v3/faceset/user/add';
    /**
     * 人脸删除 url
     * @var string
     */
    protected $baidu_face_delete = 'https://aip.baidubce.com/rest/2.0/face/v3/faceset/face/delete';
    /**
     * 人脸搜索 $url
     * @var string
     */
    protected $baidu_face_search = 'https://aip.baidubce.com/rest/2.0/face/v3/search';

    protected $deliver_group_id = 'deliver_user_face';//跑腿人脸group_id

    // 支持过期同步的设备类型
    protected $synOutTimeType = [5,6];

    public function house_user_face_device_info($uid, $pigcms_id=0, $is_return=false, $otherParam=[], $synOut = true,$is_plan=true) {
        if (!$uid && !$pigcms_id) {
            return true;
        }
        // 如果不是返回直接走计划任务
        if ($is_plan && !$is_return) {
            $planParam = [
                'synOut' => $synOut,
            ];
            $unique_id = 'subUserToDevice';
            $whereParam = [];
            if ($uid) {
                $whereParam['uid'] = $uid;
                $planParam['uid'] = $uid;
                $unique_id .= '_uid'.$uid;
            }
            if ($pigcms_id) {
                $whereParam['pigcms_id'] = $pigcms_id;
                $planParam['pigcms_id'] = $pigcms_id;
                if (!$uid) {
                    $unique_id .= '_pigcmsId'.$pigcms_id;
                }
            }
            $orderGroupId = isset($otherParam['orderGroupId'])&&$otherParam['orderGroupId']?trim($otherParam['orderGroupId']):md5(uniqid().$_SERVER['REQUEST_TIME']);// 标记统一执行命令
            if ($orderGroupId) {
                $planParam['orderGroupId'] = $orderGroupId;
            }
            $now_time = time();
            $arr = [];
            $arr['param'] = serialize($planParam);
            $arr['plan_time'] = -9999;
            // 优先级暂定为高
            $arr['space_time'] = 0;
            $arr['add_time'] = $now_time;
            $arr['file'] = 'sub_user_to_face_device';
            $arr['time_type'] = 1;
            $arr['unique_id'] = $unique_id;
            $arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
            $task_id = M('Process_sub_plan')->data($arr)->add();

            $house_a5_client_id = C('config.house_a5_client_id');
            $HikCloudClientId   = C('config.HikCloudClientId');
            $protocol = C('config.hikGatewayProtocol');
            fdump_api(['$house_a5_client_id' => $house_a5_client_id, '$HikCloudClientId' => $HikCloudClientId, '$protocol' => $protocol], 'v20Face/userSynDevice', 1);
            if ($house_a5_client_id || $HikCloudClientId || $protocol) {
                // 暂时只是支持 海康内部+大华云睿 存在配置即调用 避免无用调用
                $models = '\app\community\model\service\FaceDeviceService/userSynDevice';
                $param = [
                    'pigcms_id'     => $pigcms_id,
                    'village_id'    => 0,
                ];
                $a = invoke_v20_service($models,$param);
            }
            $whereFaceDevice = [
                'device_type' => 30,
                'is_del' => 0,
            ];
            $d_house_face_device = M('House_face_device');
            $count = $d_house_face_device->where($whereFaceDevice)->count();
            if ($count > 0) {
                D('House_village_device_cmd')->userSyn($uid, $pigcms_id);
            }
            $userBindInfo = M('House_village_user_bind')->where($whereParam)->find();
            if($userBindInfo && C('config.garbage_collection_status')){
                $garbageCount = M('Garbage_collection_machine_list')->where(['village_id'=>$userBindInfo['village_id']])->count();
                if ($garbageCount > 0) {
                    D('House_village_device_cmd')->userGarbageSyn($uid, $pigcms_id);
                }
            }
            $third_robot_recognition_open = C('config.third_robot_recognition_open');
            $third_robot_recognition_appid = C('config.third_robot_recognition_appid');
            $third_robot_recognition_appsecret = C('config.third_robot_recognition_appsecret');
            if ($third_robot_recognition_open && $third_robot_recognition_appid && $third_robot_recognition_appsecret) {
                $models = '\app\thirdRobot\model\service\ThirdRobotDeviceCmdService/personToRobotsJob';
                $param = [
                    'pigcms_id'     => $pigcms_id,
                    'uid'    => $uid,
                ];
                invoke_v20_service($models,$param);
            }
            
            if (isset($otherParam['log_name'])&&$otherParam['log_name']) {
                $log_name = $otherParam['log_name'];
            } else {
                $log_name = 'AdminUserToDevicePlan';
            }
            if (isset($otherParam['logType'])&&$otherParam['logType']) {
                $logType = $otherParam['logType'];
            } else {
                $logType = 'houseAdmin';
            }
            if (isset($otherParam['operator_type'])&&$otherParam['operator_type']) {
                $operator_type = $otherParam['operator_type'];
                $log_content = '用户同步数据至设备计划任务';
            } else {
                $operator_type = 'house_admin';
                $log_content = '管理员操作用户同步数据至设备计划任务';
            }
            if (isset($otherParam['log_operator'])&&$otherParam['log_operator']) {
                $log_operator = $otherParam['log_operator'];
            } else {
                $log_operator = '';
            }
            if (isset($otherParam['operator_id'])&&$otherParam['operator_id']) {
                $operator_id = $otherParam['operator_id'];
            } elseif ($operator_type=='user' && $pigcms_id) {
                $operator_id = $pigcms_id;
            } else {
                $operator_id = 0;
            }
            if (isset($otherParam['log_phone'])&&$otherParam['log_phone']) {
                $log_phone = $otherParam['log_phone'];
            } else {
                $log_phone = '';
            }
            $logParam = [
                'log_name' => $log_name,'logType' => $logType,'log_status' => 101,'orderGroupId' => $orderGroupId,
                'log_content' => $log_content,'log_uid' => $uid?$uid:0,'bind_id' => $pigcms_id?$pigcms_id:0,
                'log_operator' => $log_operator,'operator_id' => $operator_id,'log_phone' => $log_phone,
                'uid' => $uid?$uid:0,'addTime' => time(),
            ];
            try {
                D('House_face_device_log')->addLog($logParam);
            } catch( \Exception $e) {
                // 记录日志报错
                fdump_api(['日志记录[设备添加权限组计划任务]出错'.__LINE__.'方法'.__FUNCTION__,$logParam,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
            }
            return array('task_id'=>$task_id);
        }

        $m_house_worker = M('House_worker');
        $m_house_face_device = M('House_face_device');
        $m_house_village_user_bind = M('House_village_user_bind');
        $conditioFaveDevice = [];
        $conditioFaveDevice['is_del'] = 0;
        $faceDeviceCount = $m_house_face_device->field(true)->where($conditioFaveDevice)->count();
        if (!$faceDeviceCount) {
            // 如果没有人脸设备不进行同步
            return true;
        }

        if ($uid>0) {
            $now_user = D('User')->get_user($uid);
            $where['a.uid'] = $uid;
        } else {
            $now_user = [];
        }
        if (!$uid && $pigcms_id>0) {
            $where['a.pigcms_id'] = $pigcms_id;
        }
        $where['a.status'] = 1;
        $where['b.status'] = 1;
        $aUserSelect = $m_house_village_user_bind->alias('a')
            ->join('LEFT JOIN pigcms_house_village as b on b.village_id=a.village_id')
            ->group('a.pigcms_id')
            ->field(array('a.pigcms_id','a.parent_id','a.type','a.name','a.village_id', 'a.floor_id', 'a.property_price', 'a.property_endtime', 'a.phone', 'a.open_door_day', 'b.village_name', 'b.village_address', 'b.owe_property_open_door', 'b.owe_property_open_door_day'))
            ->where($where)
            ->order('a.type DESC')
            ->select();
        // 处理数据-层次为  小区-门禁-具体信息
        $open_list_info = array();
        // 查询出来的门禁信息存储
        $door_list_info = array();
        // 查询出来的单元信息存储
        $floor_list_info = array();
        // 查询出来的可以开门的信息存储
        $aDoor_is_open = array();
        // 存储信息
        $bind_info = array();
        // 不能同步的原因
        $err_msg = '';

        $baidu_ai_appid = C('config.baidu_ai_appid');
        if (!empty($aUserSelect)) {
            foreach ($aUserSelect as $k => $v) {
                if(isset($v['open_door_day']) && $v['open_door_day']>0){
                    $v['owe_property_open_door_day']=$v['open_door_day'];
                }
                if ($v['village_id'] && $uid && $baidu_ai_appid) {
                    $this->create_baidu_ai_face_plan($v['village_id'], 'baidu_face_edit', $uid);
                }
                if ($v['type'] == 1 || $v['type'] == 2) {
                    // 家属和租客 物业费信息以业主为准
                    if ($bind_info[$v['parent_id']]) {
                        $v['property_price'] = $bind_info[$v['parent_id']]['property_price'];
                        $v['property_endtime'] = $bind_info[$v['parent_id']]['property_endtime'];
                    } else {
                        $info = $m_house_village_user_bind->where(array('pigcms_id' => $v['parent_id']))->field('property_price, property_endtime')->find();
                        $v['property_price'] = $info['property_price'];
                        $v['property_endtime'] = $info['property_endtime'];
                    }
                }
                $bind_info[$v['pigcms_id']] = $v;
                if (!$open_list_info[$v['village_id']]) {
                    // 处理小区层信息
                    $open_list_info[$v['village_id']] = array(
                        'village_name' => $v['village_name'],
                        'village_address' => $v['village_address']
                    );
                    // 放置门禁信息数组
                    $open_list_info[$v['village_id']]['door_list'] = array();
                }
                // 避免重复查询门禁信息
                if ($door_list_info && $door_list_info[$v['village_id']]) {
                    $aDoorList = $door_list_info[$v['village_id']];
                } else {
                    $condition_door['village_id'] = $v['village_id'];
                    $condition_door['is_del'] = 0;
                    $condition_door['thirdProtocol'] = ['not in', [12,13]];
                    $aDoorList = $m_house_face_device->field(true)->where($condition_door)->select();
                    $door_list_info[$v['village_id']] = $aDoorList;
                }
                // 默认存在单元门禁
                if (empty($aDoorList)) {
                    unset($open_list_info[$v['village_id']]);
                    continue;
                } else {
                    $open_list_info[$v['village_id']]['have_village_door'] = true;
                    $open_list_info[$v['village_id']]['tip_info'] = '';
                }
                if ($v['type'] == 4 && $now_user['phone']) {
                    // 获取工作人员信息
                    $worker_info = $m_house_worker->where(array('phone'=>$now_user['phone'],'status'=>array('neq',4), 'village_id' => $v['village_id']))->field('village_id,wid,phone,status,open_door')->find();

                    if ($worker_info['open_door'] == 1) {
                        $work_open_status = 1;
                        $work_open_info = L_('可以打开');
                        foreach ($aDoorList as $kk => &$vv) {
                            if (!empty($aDoor_is_open) && !empty($aDoor_is_open[$vv['device_id']])) {
                                // 门禁信息已经查询过，直接赋予新的状态
                                $door = $aDoor_is_open[$vv['device_id']];
                                $door['door_end_time'] = 0;
                                $door['door_user_type'] = 3; // 工作人员
                                if (empty($aDoor_is_open[$vv['device_id']]['bind_arr_id'])) {
                                    $aDoor_is_open[$vv['device_id']]['bind_arr_id'] = array();
                                }
                                $aDoor_is_open[$vv['device_id']]['bind_arr_id'][] = $v['pigcms_id'];
                                $aDoor_is_open[$vv['device_id']]['door_end_time'] = 0; // 工作人员开门权限无时间限制
                                $aDoor_is_open[$vv['device_id']]['door_user_type'] = 3;
                                $open_list_info[$vv['village_id']]['door_list'][$vv['device_id']] = $door;
                                continue;
                            }
                            $floor_arr = [];
                            if ($vv['floor_id'] != "-1") {
                                $floor_arr = explode(',',$vv['floor_id']);
                                $vv['door_type'] = 1; // 小区单元门
                            } else {
                                $vv['door_type'] = 2; // 小区大门
                            }
                            $vv['door_user_type'] = 3; // 工作人员
                            $vv['door_end_time'] = 0;
                            $vv['open_status'] = $work_open_status;
                            $vv['open_status_txt'] = $work_open_info;
                            if (empty($vv['bind_arr_id'])) {
                                $vv['bind_arr_id'] = array();
                            }
                            $vv['bind_arr_id'][] = $v['pigcms_id'];
                            $aDoor[] = $vv;
                            $aDoor_is_open[$vv['device_id']] = $vv;
                            $open_list_info[$vv['village_id']]['door_list'][$vv['device_id']] = $vv;
                            $open_list_info[$vv['village_id']]['have_floor_door'] = true;
                        }
                    }
                } else {
                    // 绑定的单元-即拥有该小区的大门和改单元门禁权限
                    $village_floor_id = $v['floor_id'];
                    // 开门到期时间
                    $property_endtime = 0;
                    if (!$v['property_endtime']) {
                        $ban_open_status = false;   // 是否禁止开门  false 可以开门  true 禁止开门
                    } else {
                        $ban_open_status = false; // 默认可以开门
                        $owe_property_days = 0;
                        if ( $v['property_endtime'] > 0) {
                            // 计算 当前时间到物业截止时间的天数

                            $end_time = D('House_village_property_paylist')->where(array('bind_id'=>$v['pigcms_id']))->order('add_time desc')->getField('end_time');
                            if ($end_time>$v['property_endtime']) {
                                $v['property_endtime'] = $end_time;
                            }

                            $owe_property_days = (strtotime(date('y-m-d', time())) - strtotime(date('y-m-d', $v['property_endtime']))) / 86400;
                        }
                        if (!$v['owe_property_open_door'] && $v['property_endtime']>0 && (time() > $v['property_endtime'])) {
                            // 判断  不能欠款延期开门 且 当前时间大于 物业截止时间  开门禁止
                            $ban_open_status = true;
                        } else if ($v['property_endtime']>0 && $v['owe_property_open_door_day'] > 0 && $owe_property_days >= 0 && $owe_property_days > $v['owe_property_open_door_day']) {
                            // 判断  可以欠款延期开门 且 延期天数大于0 且  当前时间到物业截止时间的天数存在 且大于 延期天数 开门禁止
                            $ban_open_status = true;
                        }
                        // 处理下开门到期时间
                        if ($v['property_endtime'] > 0) {
                            $property_endtime = $v['property_endtime'];
                            if ($v['owe_property_open_door'] && $v['owe_property_open_door_day'] > 0) {
                                $property_endtime +=  $v['owe_property_open_door_day'] * 86400;
                            }else if($v['owe_property_open_door']){
                                $property_endtime = 0;
                            }
                        }
                    }
                    foreach ($aDoorList as $kk => &$vv) {
                        $floor_arr = [];
                        if ($vv['floor_id'] != "-1") {
                            $floor_arr = explode(',',$vv['floor_id']);
                            $vv['door_type'] = 1; // 小区单元门
                        } else {
                            $vv['door_type'] = 2; // 小区大门
                        }
                        if ($v['type'] == 1 || $v['type'] == 2) {
                            $vv['door_user_type'] = 4; // 家属
                        } else {
                            $vv['door_user_type'] = 1; // 业主
                        }

                        // 个别设备时间过期也继续同步  便于更改过期时间
                        if ($synOut && in_array($vv['device_type'],$this->synOutTimeType)) {
                            $ban_open_status = false;
                        }

                        $vv['door_end_time'] = $property_endtime>0?date('Y-m-d H:i', $property_endtime):0;
                        if (!$ban_open_status && $vv['floor_id'] == -1) {
                            if (empty($aDoor_is_open) || empty($aDoor_is_open[$vv['device_id']])) {
                                $vv['open_status'] = 1;
                                $vv['open_status_txt'] = L_('可以打开');
                                if (empty($vv['bind_arr_id'])) {
                                    $vv['bind_arr_id'] = array();
                                }
                                $vv['bind_arr_id'][] = $v['pigcms_id'];
                                $aDoor[] = $vv;
                                $aDoor_is_open[$vv['device_id']] = $vv;
                            } else {
                                if (empty($aDoor_is_open[$vv['device_id']]['bind_arr_id'])) {
                                    $aDoor_is_open[$vv['device_id']]['bind_arr_id'] = array();
                                }
                                $aDoor_is_open[$vv['device_id']]['bind_arr_id'][] = $v['pigcms_id'];
                            }
                            $aDoor_is_open[$vv['device_id']]['door_user_type'] = 1;
                            $open_list_info[$vv['village_id']]['door_list'][$vv['device_id']] = $vv;
                        } elseif (!$ban_open_status && ($village_floor_id==0 || $vv['floor_id'] == $village_floor_id || in_array($village_floor_id, $floor_arr))) {
                            if (empty($aDoor_is_open) || empty($aDoor_is_open[$vv['device_id']])) {
                                $vv['open_status'] = 1;
                                $vv['open_status_txt'] = L_('可以打开');
                                if (empty($vv['bind_arr_id'])) {
                                    $vv['bind_arr_id'] = array();
                                }
                                $vv['bind_arr_id'][] = $v['pigcms_id'];
                                $aDoor[] = $vv;
                                $aDoor_is_open[$vv['device_id']] = $vv;
                            } else {
                                if (empty($aDoor_is_open[$vv['device_id']]['bind_arr_id'])) {
                                    $aDoor_is_open[$vv['device_id']]['bind_arr_id'] = array();
                                }
                                $aDoor_is_open[$vv['device_id']]['bind_arr_id'][] = $v['pigcms_id'];
                            }
                            $aDoor_is_open[$vv['device_id']]['door_user_type'] = 1;
                            $open_list_info[$vv['village_id']]['door_list'][$vv['device_id']] = $vv;
                        } elseif ($ban_open_status && ($village_floor_id==0 || $vv['floor_id'] == $village_floor_id || in_array($village_floor_id, $floor_arr))) {
                            $vv['open_status']	=	3;//欠物业费
                            $vv['open_status_txt']	=	L_('您已欠物业费不能开门');
                            $err_msg = '已欠物业费，不能进行同步';
                            $open_list_info[$vv['village_id']]['door_list'][$vv['device_id']] = $vv;
                        } elseif ($ban_open_status && $vv['floor_id'] == -1) {
                            $vv['open_status']	=	3;//欠物业费
                            $vv['open_status_txt']	=	L_('您已欠物业费不能开门');
                            $err_msg = '已欠物业费，不能进行同步';
                            $open_list_info[$vv['village_id']]['door_list'][$vv['device_id']] = $vv;
                        }
                    }
                    if (empty($open_list_info[$v['village_id']]['door_list']) && $open_list_info[$v['village_id']]['have_village_door']) {
                        $open_list_info[$v['village_id']]['have_floor_door'] = false;
                        $open_list_info[$v['village_id']]['tip_info'] = L_('您入住的单元暂未添加门禁');
                        $err_msg = '入住的单元和小区暂未添加门禁';
                    } else {
                        $open_list_info[$v['village_id']]['have_floor_door'] = true;
                    }
                }
            }
        }
        if ($is_return) {
            return $aDoor_is_open;
        }
        $config_data = D('Config_data')->get_config_data();
        $a4_to_new_traits_job = isset($config_data['a4_to_new_traits_job']) ? intval($config_data['a4_to_new_traits_job']) : 0;
        $toNewA4 = [];

        if (!empty($aDoor_is_open)) {
            fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/aDoor_is_open',true);
            fdump($aDoor_is_open,'api/log/'.date('Ymd').'/aDoor_is_open',true);
			
			import('@.ORG.rabbit_mq');
            $this->rabbit_mq = new rabbit_mq();

            set_time_limit(0);
            ini_set('memory_limit', '1024M');

            //A4 是同步到设备方 实际和设备 不直接关联同步 所以重复的不用同步 浪费资源
            $repeat = [];
            foreach ($aDoor_is_open as $val) {
                if (!empty($val['bind_arr_id']) && is_array($val['bind_arr_id'])) {
                    if (1==$val['device_type']) {
                        try {
                            $this->add_person($val['device_id'],$val['bind_arr_id'],$val['device_sn']);
                        }catch (Exception $e) {
                            fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/a1/a1_door_is_open_err',true);
                            fdump($val,'api/log/'.date('Ymd').'/a1/a1_door_is_open_err',true);
                            fdump($e->getMessage(),'api/log/'.date('Ymd').'/a1/a1_door_is_open_err',true);
                        }
                    } elseif (2==$val['device_type'] && $val['a2_face_door_doorkeeperId']>0) {
                        try {
                            D('House_village_face_door')->modify_arr($val['bind_arr_id']);
                        }catch (Exception $e) {
                            fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/a2/a2_door_is_open_err',true);
                            fdump($val,'api/log/'.date('Ymd').'/a2/a2_door_is_open_err',true);
                            fdump($e->getMessage(),'api/log/'.date('Ymd').'/a2/a2_door_is_open_err',true);
                        }
                    } elseif (3==$val['device_type']) {
                        try {
                            D('House_village_face_door')->a3_arr_person_msg($val['device_id'],$val['bind_arr_id'],$val['device_sn']);
                        }catch (Exception $e) {
                            fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/a3/a3_door_is_open_err',true);
                            fdump($val,'api/log/'.date('Ymd').'/a3/a3_door_is_open_err',true);
                            fdump($e->getMessage(),'api/log/'.date('Ymd').'/a3/a3_door_is_open_err',true);
                        }
                    } else if (5 == $val['device_type'] || 6 == $val['device_type']) {
                        try {
                            D('Face_door_a5_service')->synDeviceDispose($val['device_id'],$val['bind_arr_id'],$val['device_sn']);
                        }catch (Exception $e) {
                            fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/a5/a5_door_is_open_err',true);
                            fdump($val,'api/log/'.date('Ymd').'/a5/a5_door_is_open_err',true);
                            fdump($e->getMessage(),'api/log/'.date('Ymd').'/a5/a5_door_is_open_err',true);
                        }
                    } elseif (7==$val['device_type']) {
                        try {
                            D('Face_door_a7_service')->a7_arr_person_msg($val['device_id'],$val['bind_arr_id']);
                        }catch (Exception $e) {
                            fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/a7/a7_door_is_open_err',true);
                            fdump($val,'api/log/'.date('Ymd').'/a7/a7_door_is_open_err',true);
                            fdump($e->getMessage(),'api/log/'.date('Ymd').'/a7/a7_door_is_open_err',true);
                        }
                    } elseif (4==$val['device_type']) {
                        if ($a4_to_new_traits_job) {
                            $toNewA4[] = [
                                'device_id' => $val['device_id'], 'bind_arr_id' => $val['bind_arr_id']
                            ];
                        } else {
                            try {
                                if ($repeat) {
                                    $diff = array_diff($val['bind_arr_id'], $repeat);
                                    if (!$diff) {
                                        continue;
                                    }
                                }
                                D('Face_door_a4_service')->a4_arr_person_msg($val['device_id'],$val['bind_arr_id']);
                                $repeat = array_merge($repeat, $val['bind_arr_id']);
                            }catch (Exception $e) {
                                fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/A4/a4_door_is_open_err',true);
                                fdump($val,'api/log/'.date('Ymd').'/A4/a4_door_is_open_err',true);
                                fdump($e->getMessage(),'api/log/'.date('Ymd').'/A4/a4_door_is_open_err',true);
                            }
                        }
                    } elseif(8==$val['device_type']){
                        try {
                            D('Face_local_area_device')->synDeviceDispose($val['device_id'],$val['bind_arr_id'],$val['device_sn']);
                        }catch (Exception $e) {
                            fdump($e->getMessage(),'api/log/'.date('Ymd').'/A8/a8_door_is_open_err',true);
                        }
                    } elseif (21==$val['device_type']) {
                        try {
                            D('Face_door_d1_service')->d1_arr_person_msg($val['device_id'],$val['bind_arr_id']);
                        }catch (Exception $e) {
                            fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/D1/d1_door_is_open_err',true);
                            fdump($val,'api/log/'.date('Ymd').'/D1/d1_door_is_open_err',true);
                            fdump($e->getMessage(),'api/log/'.date('Ymd').'/D1/d1_door_is_open_err',true);
                        }
                    }elseif (23==$val['device_type']) {
                        /**
                         * todo D3智能门禁机
                         *
                         */
                        try {
                          return  D('Face_d3_request_record')->d3_arr_person_msg($val['device_id'],$val['bind_arr_id']);
                        }catch (Exception $e) {
                            fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/d3/d1_door_is_open_err',true);
                            fdump($val,'api/log/'.date('Ymd').'/d3/d1_door_is_open_err',true);
                            fdump($e->getMessage(),'api/log/'.date('Ymd').'/d3/d1_door_is_open_err',true);
                        }
                    }
                    elseif (24 == $val['device_type']) {
                        /**
                         * todo D4智能门禁机
                         *
                         */
                        try {

                            return D('Face_d4_request_record')->d4_arr_person_msg($val['device_id'], $val['bind_arr_id']);
                        } catch (Exception $e) {
                            fdump(date('Y-m-d H:i:s'), 'api/log/' . date('Ymd') . '/d4/d1_door_is_open_err', true);
                            fdump($val, 'api/log/' . date('Ymd') . '/d4/d1_door_is_open_err', true);
                            fdump($e->getMessage(), 'api/log/' . date('Ymd') . '/d4/d1_door_is_open_err', true);

                        }
                    }elseif (61==$val['device_type']) {
                        /**
                         * todo A185智能门禁机
                         *
                         */
                        try {
                            D('Face_a185_request_record')->a185_arr_person_msg($val['device_id'],$val['bind_arr_id']);
                            fdump_api(['人员数据写入>>'.__LINE__,$val['bind_arr_id']],'a185/a185_arr_person',1);
                        }catch (Exception $e) {
                            fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/a185/a185_door_is_open_err',true);
                            fdump($val,'api/log/'.date('Ymd').'/a185/a185_door_is_open_err',true);
                            fdump($e->getMessage(),'api/log/'.date('Ymd').'/a185/a185_door_is_open_err',true);

                        }
                    }elseif(88==$val['device_type']){
                        /**
                         * todo 大华人脸门禁
                         *
                         */
                        try {
                            D('Dahua_service')->dahua_arr_person_msg($val['device_id'],$val['bind_arr_id']);
                            fdump_api(['人员数据写入>>'.__LINE__,$val['bind_arr_id']],'dahua/dahua_arr_person',1);
                        }catch (Exception $e) {
                            fdump_api($val,'dahua/dahua_door_is_open_err',true);
                            fdump_api($e->getMessage(),'dahua/dahua_door_is_open_err',true);
                        }
                    }
                    elseif (26==$val['device_type']) {
                        /**
                         * todo d6智能门禁机
                         *
                         */
                        try {
                            D('Face_d6_request_record')->d6_arr_person_msg($val['device_id'],$val['bind_arr_id']);
                        }catch (Exception $e) {
                            fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/a7/a7_door_is_open_err',true);
                            fdump($val,'api/log/'.date('Ymd').'/a7/a7_door_is_open_err',true);
                            fdump($e->getMessage(),'api/log/'.date('Ymd').'/a7/a7_door_is_open_err',true);
                        }
                    }
                    elseif (29==$val['device_type']){
                        /*** todo 朵普*/
                        try {
                            $models = '\app\community\model\service\CloudIntercomService/sendThirdUserToDeviceDeal';
                            $rr=invoke_v20_service($models,[
                                'village_id'=>$val['village_id'],
                                'deviceSn'=>$val['device_sn'],
                                'thirdUser'=>$val['bind_arr_id'],
                                'operation'=>'add',
                                'source'=>'house_village'
                            ]);
                            fdump_api(['朵普：'.__LINE__,$val,$rr],'duopu/v20_log',1);
                        } catch (Exception $e) {
                            fdump_api([$val,$e->getMessage()],'duopu/open_err',1);
                        }
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
        } elseif($err_msg) {
            $arr = [
                'aDoor_is_open' => $aDoor_is_open,
                'err_msg' => $err_msg,
            ];
            return $arr;
        }
        return $aDoor_is_open;
    }

    protected $rabbit_mq;
    /**
     * 门禁下发添加编辑用户
     * @param $device_id
     * @param $person_list
     * @param string $device_sn
     * @return bool
     */
    public function add_person($device_id,$person_list,$device_sn='') {
        $d_house_face_device = M('House_face_device');

        if (!$device_sn) {
            $house_face_device_info = $d_house_face_device->where(array('device_id' => $device_id))->find();
            $device_sn = $house_face_device_info['device_sn'];
        }
        if (!$this->rabbit_mq) {
            import('@.ORG.rabbit_mq');
            $this->rabbit_mq = new rabbit_mq();
        }

        $site = C('config.site_url');
        $params = array('limit'=> 100, 'size'=>100);
        $params = (object)$params;
        $person_str = implode(",",$person_list);
        $data = array(
            'method' => 'sync-person',
            'person_list' => $person_list,
            'path' => $site . '/person-list?person_list='.$person_str,
            'notify' => $site . '/person-notify',
            'params' =>$params
        );
        fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/a1/face_add_person_log',true);
        fdump($person_str,'api/log/'.date('Ymd').'/a1/face_add_person_log',true);
        fdump($data,'api/log/'.date('Ymd').'/a1/face_add_person_log',true);
        $this->rabbit_mq->send_message($device_sn, $data);
        return true;
    }


    /**
     * 门禁下发删除用户
     * @param $device_id
     * @param $person_list
     * @param string $device_sn
     * @return bool
     */
    public function del_person($device_id,$person_list,$device_sn='') {
        $d_house_face_device = M('House_face_device');

        if (!$device_sn) {
            $house_face_device_info = $d_house_face_device->where(array('device_id' => $device_id))->find();
            $device_sn = $house_face_device_info['device_sn'];
        }
        if (!$this->rabbit_mq) {
            import('@.ORG.rabbit_mq');
            $this->rabbit_mq = new rabbit_mq();
        }

        $site = C('config.site_url');
        $params = array('limit'=> 100, 'size'=>100);
        $params = (object)$params;
        $data = array(
            'method' => 'delete-person',
            'person_list' => $person_list,
            'path' => $site . '/person-list',
            'notify' => $site . '/person-notify',
            'params' =>$params
        );
        fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/a1/face_del_person_log',true);
        fdump($data,'api/log/'.date('Ymd').'/a1/face_del_person_log',true);
        $this->rabbit_mq->send_message($device_sn, $data);
        return true;
    }

    public function device_restart($device_id,$device_sn='') {
        $d_house_face_device = M('House_face_device');

        if (!$device_sn) {
            $house_face_device_info = $d_house_face_device->where(array('device_id' => $device_id))->find();
            $device_sn = $house_face_device_info['device_sn'];
        }
        if (!$this->rabbit_mq) {
            import('@.ORG.rabbit_mq');
            $this->rabbit_mq = new rabbit_mq();
        }

        $site = C('config.site_url');
        $params = array();
        $params = (object)$params;
        $data = array(
            'method' => 'device-restart',
            'data' => (object)array(),
            'notify' => $site . '/device-restart',
            'params' =>$params
        );
        fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/a1/face_device_restart_log',true);
        fdump($data,'api/log/'.date('Ymd').'/a1/face_device_restart_log',true);
        $this->rabbit_mq->send_message($device_sn, $data);
        return true;
    }

    // 重新同步广告
    public function sync_advert($device_id,$device_sn='', $rabbit_mq='') {
        $d_house_face_device = M('House_face_device');

        if (!$device_sn) {
            $house_face_device_info = $d_house_face_device->where(array('device_id' => $device_id))->find();
            $device_sn = $house_face_device_info['device_sn'];
        }
        if (!$rabbit_mq) {
            import('@.ORG.rabbit_mq');
            $rabbit_mq = new rabbit_mq();
        }

        $site = C('config.site_url');
        $params = array();
        $params = (object)$params;
        $data = array(
            'method' => 'sync-advert',
            'data' => (object)array(),
            'path' => $site . '/attendance-advert',
            'notify' => $site . '/attendance-advert-notify',
            'params' =>$params
        );
        fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/a1/face_sync_advert_log',true);
        fdump($data,'api/log/'.date('Ymd').'/a1/face_sync_advert_log',true);
        $rabbit_mq->send_message($device_sn, $data);
        return true;
    }

    // A1门禁开门
    public function open_a1_face_door($device_id,$device_sn='', $params = array(), $rabbit_mq='') {
        $d_house_face_device = M('House_face_device');
        if (!$device_sn) {
            $house_face_device_info = $d_house_face_device->where(array('device_id' => $device_id))->find();
            $device_sn = $house_face_device_info['device_sn'];
        }
        if (!$rabbit_mq) {
            import('@.ORG.rabbit_mq');
            $rabbit_mq = new rabbit_mq();
        }
        $site = C('config.site_url');
        $params = (object)$params;
        $data = array(
            'method' => 'open-door',
            'data' => (object)array(),
            'path' => '',
            'notify' => $site . '/open-door-notify',
            'params' =>$params
        );
        fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/a1/face_sync_advert_log',true);
        fdump($data,'api/log/'.date('Ymd').'/a1/face_sync_advert_log',true);
        $rabbit_mq->send_message($device_sn, $data);
        return true;

    }


    //	修改人脸识别门禁设备
    public function save_door($where,$data){
        if(!$where || !$data){
            return	false;
        }
        $addDoor = $this->where($where)->data($data)->save();
        return	$addDoor;
    }



    /**
     * 生成计划任务
     * @param integer $type_id  社区id 或者其他相关参数
     * @param string $type   人脸注册 baidu_face_add; 人脸编辑 baidu_face_edit;
     * @param integer $uid
     */
    public function create_baidu_ai_face_plan($type_id, $type = 'baidu_face_add', $uid=0) {
        $param = array();
        $param['type'] = $type;
        if ($uid>0) { // 需要操作的用户
            $param['uid'] = $uid;
        } else {
            $param['uid'] = 0;
        }
        if ($type_id) { // 需要对应操作的社区id
            $param['type_id'] = $type_id;
        } else {
            $param['type_id'] = 0;
        }
        if ($param) {
            $this->add_plan($param);
        }
    }

    public function add_plan($param){
        $arr= array();
        $now_time = time();
        $arr['param'] = serialize($param);
        $arr['plan_time'] = 0;
        $arr['space_time'] = 0;
        $arr['add_time'] = $now_time;
        $arr['file'] = 'sub_baidu_ai_face';
        $arr['time_type'] = 1;
        $arr['unique_id'] = 'plan_'.$param['type'].'_'.$param['type_id'].'_'.$param['uid'];
		$arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
        $task_id = M('Process_sub_plan')->data($arr)->add();
        return array('task_id'=>$task_id);
    }

    public function face_runtask($param){
        import('@.ORG.plan');
        $plan_class = new plan();
        switch ($param['type']) {
            case 'baidu_face_add': //人脸注册
                $village_id = $param['type_id'];
                $uid = $param['uid'];
                $this->baidu_face_add($village_id,$uid);
                return 0;
            case 'baidu_face_edit': //人脸编辑
                $village_id = $param['type_id'];
                $uid = $param['uid'];
                $this->baidu_face_edit($village_id,$uid);
                return 0;
        }
    }

    /**百度AI 人脸识别*/
    // 获取 access_token
    public function get_token() {

        $d_baidu_ai_code = M('Baidu_ai_code');
        $baidu_ai_code = $d_baidu_ai_code->find();
        fdump_api(['get_token'.__LINE__, $baidu_ai_code],'baidu_face/get_ai_token_log',true);

        if (empty($baidu_ai_code) || intval($baidu_ai_code['expires_in'])+intval($baidu_ai_code['token_time']) < time()) {
            // 重新获取
            $url = $this->accessTokenUrl;
            $post_data['grant_type'] = 'client_credentials';
            $post_data['client_id'] = C('config.baidu_ai_api_key');
            $post_data['client_secret'] = C('config.baidu_ai_secret_key');
            $post_data = http_build_query($post_data);

            if (!$this->http) {
                import('ORG.Net.Http');
                $this->http = new Http();
            }
            fdump_api(['get_token'.__LINE__, $url],'baidu_face/get_ai_token_log',true);
            $result_info = $this->http->curlPostOwn($url, $post_data);
            $result = json_decode($result_info, true);
            fdump_api(['get_token'.__LINE__, $post_data, $result_info, $result],'baidu_face/get_ai_token_log',true);
            if ($result['errcode']) {
                fdump_api(['get_token_err'.__LINE__, $url, $post_data, $result_info, $result],'baidu_face/get_ai_token_err_log',true);
                return array('error'=>1,'msg'=>'刷新token获取失败');
            }else{
                $data = array();
                if ($baidu_ai_code) {
                    $data['code_id'] = $baidu_ai_code['code_id'];
                    $data['access_token'] = $result['access_token'];
                    $data['refresh_token'] = $result['refresh_token'];
                    $data['expires_in'] = $result['expires_in'];
                    $data['session_key'] = $result['session_key'];
                    $data['session_secret'] = $result['session_secret'];
                    $data['token_time'] = time();
                    $add_id = $d_baidu_ai_code->save($data);
                } else {
                    $data['access_token'] = $result['access_token'];
                    $data['refresh_token'] = $result['refresh_token'];
                    $data['expires_in'] = $result['expires_in'];
                    $data['session_key'] = $result['session_key'];
                    $data['session_secret'] = $result['session_secret'];
                    $data['token_time'] = time();
                    $data['create_time'] = time();
                    $add_id = $d_baidu_ai_code->data($data)->add();
                }
                fdump_api(['get_token_err'.__LINE__, $add_id, $data, $d_baidu_ai_code->_sql()],'baidu_face/get_ai_token_err_log',true);
                return array('error'=>0,'access_token'=>$result['access_token']);
            }
        }else{
            return array('error'=>0,'access_token'=>$baidu_ai_code['access_token']);
        }
    }

    // 百度AI 人脸注册
    public function baidu_face_add($village_id,$uid) {
        $url = $this->baidu_face_add;
        $data = array();
        fdump_api(['baidu_face_add>>'.__LINE__, $village_id, $uid],'baidu_face/baidu_face_add_log',true);

        $access_token = $this->get_token();
        fdump_api(['line>>'.__LINE__, $access_token],'baidu_face/baidu_face_add_log',true);
        if (!$access_token['error'] && $access_token['access_token']) {
            $access_token = $access_token['access_token'];
        } else {
            fdump_api(['token获取失败>>'.__LINE__, $access_token],'baidu_face/baidu_face_add_log',true);
            return true;
        }
        if (!$uid) {
            fdump_api(['$uid不存在>>'.__LINE__, $access_token],'baidu_face/baidu_face_add_log',true);
            return true;
        }

        $url .= '?access_token='.$access_token;

        $image_url = M('House_face_img')->where(array('uid'=>$uid,'status'=>['in', [0,3]],'img_url'=>array('neq','')))->order('add_time ASC')->select();
        $house_village = M('House_village')->where(array('village_id' => $village_id))->find();

        $city_id = $house_village['city_id'];
        $d_baidu_ai_face_log = M('Baidu_ai_face_log');

        if (!$this->http) {
            import('ORG.Net.Http');
            $this->http = new Http();
        }
        import('@.ORG.face.word_encryption');
        $word_encryption = new word_encryption();
        foreach ($image_url as $val) {
            if (3==$val['status']) {
                // 加密的图片解密
                $val['img_url'] = $word_encryption->text_decrypt($val['img_url']);
            }
            $img_filename = replace_file_domain($val['img_url']);
//            $image = D('House_village_face_door')->imgToBase64($img_filename,false);
            $data['image'] = $img_filename;
            $data['image_type'] = 'URL';
            $data['group_id'] = $city_id;
            $data['user_id'] = $uid;

            // 非必传
            $data['quality_control'] = 'NONE'; // 图片质量控制 NONE: 不进行控制 LOW:较低的质量要求 NORMAL: 一般的质量要求 HIGH: 较高的质量要求 默认 NONE 若图片质量不满足要求，则返回结果中会提示质量检测失败
            $data['liveness_control'] = 'NONE'; // 活体检测控制 NONE: 不进行控制 LOW:较低的活体要求(高通过率 低攻击拒绝率) NORMAL: 一般的活体要求(平衡的攻击拒绝率, 通过率) HIGH: 较高的活体要求(高攻击拒绝率 低通过率) 默认NONE 若活体检测结果不满足要求，则返回结果中会提示活体检测失败
            $data['action_type'] = 'APPEND'; // APPEND: 当user_id在库中已经存在时，对此user_id重复注册时，新注册的图片默认会追加到该user_id下 REPLACE : 当对此user_id重复注册时,则会用新图替换库中该user_id下所有图片 默认使用APPEND

            $post_data = http_build_query($data);
            $msg_arr = $this->http->curlPostOwn($url, $post_data);
            $info = json_decode($msg_arr, true);
            fdump_api(['line>>'.__LINE__, $url, $post_data, $msg_arr, $info, $img_filename],'baidu_face/baidu_face_add_log',true);
            if (!$info['error_code']) {
                $log = array();
                $log['city_id'] = $city_id;
                $log['village_id'] = $village_id;
                $log['uid'] = $uid;
                $log['face_img_id'] = $val['id'];
                $msg = $d_baidu_ai_face_log->where($log)->find();
                $log['baidu_log_id'] = $info['log_id'];
                $log['timestamp'] = $info['timestamp'];
                $log['face_token'] = $info['result']['face_token'];
                $log['location'] = serialize($info['result']['location']);
                if ($msg) {
                    $log['log_id'] = $msg['log_id'];
                    $log['last_time'] = time();
                    $d_baidu_ai_face_log->data($log)->save();
                } else {
                    $log['add_time'] = time();
                    $d_baidu_ai_face_log->data($log)->add();
                }
            }
            usleep(200000);// sleep 200 ms
        }

        return $info;
    }
    // 百度AI 人脸编辑 先对应删除 再进行添加
    public function baidu_face_edit($village_id,$uid) {
        fdump_api(['baidu_face_edit>>'.__LINE__, $village_id, $uid],'baidu_face/baidu_face_edit_log',true);
        $house_village = M('House_village')->where(array('village_id' => $village_id))->find();

        $city_id = $house_village['city_id'];
        $d_baidu_ai_face_log = M('Baidu_ai_face_log');

        $image_url = $d_baidu_ai_face_log->where(array('uid'=>$uid,'city_id'=>$city_id,'village_id'=>$village_id,'status'=>0))->order('add_time ASC')->select();
        if ($image_url) {
            $url = $this->baidu_face_delete;
            $access_token = $this->get_token();
            fdump_api(['access_token>>'.__LINE__, $access_token],'baidu_face/baidu_face_edit_log',true);
            if (!$access_token['error'] && $access_token['access_token']) {
                $access_token = $access_token['access_token'];
            } else {
                fdump_api(['token获取失败>>'.__LINE__, $access_token],'baidu_face/baidu_face_edit_log',true);
                return true;
            }
            $url .= '?access_token='.$access_token;
            $data = array();
            $data['user_id'] = $uid;
            $data['group_id'] = $city_id;
            if (!$this->http) {
                import('ORG.Net.Http');
                $this->http = new Http();
            }
            foreach ($image_url as $item) {
                $data['face_token'] = $item['face_token'];
                $post_data = http_build_query($data);
                $msg_arr = $this->http->curlPostOwn($url, $post_data);
                $info = json_decode($msg_arr, true);
                fdump_api(['请求>>'.__LINE__, $url, $post_data, $msg_arr, $info],'baidu_face/baidu_face_edit_log',true);
            }
        }
        $this->baidu_face_add($village_id,$uid);
    }

    public function baidu_face_search($img_id) {
        fdump_api(['baidu_face_search>>'.__LINE__, $img_id],'baidu_face/baidu_face_search_log',true);
        $url = $this->baidu_face_search;
        $data = array();

        $access_token = $this->get_token();
        fdump_api(['line>>'.__LINE__, $access_token],'baidu_face/baidu_face_search_log',true);
        if (!$access_token['error'] && $access_token['access_token']) {
            $access_token = $access_token['access_token'];
        } else {
            fdump_api(['token获取失败>>'.__LINE__, $access_token],'baidu_face/baidu_face_search_log',true);;
            return true;
        }
        $url .= '?access_token='.$access_token;

        $d_house_camera_img = M('House_camera_img');
        $house_camera_img = $d_house_camera_img->where(array('img_id'=>$img_id))->find();
        $whereArr=array('village_id'=>array('gt',0));
        if($house_camera_img['camera_sn']){
            $whereArr['camera_sn']=$house_camera_img['camera_sn'];
        }else{
            $whereArr['camera_status']=array('lt',1);
            $whereArr['device_code']=array('neq','');
            $whereArr['device_brand']='brand_haikang';
        }
        $house_camera_device = M('House_camera_device')->where($whereArr)->order('camera_id desc')->find();
        if($house_camera_device && empty($house_camera_img['camera_sn'])){
            $house_camera_img['camera_sn']=$house_camera_device['camera_sn'];
        }
        $house_village = M('House_village')->where(array('village_id' => $house_camera_device['village_id']))->find();
        if (!$this->http) {
            import('ORG.Net.Http');
            $this->http = new Http();
        }
        $city_id = $house_village['city_id'];
        $image = $house_camera_img['camera_url'];
        $imageArr=explode('/',$image);
        $img_index=count($imageArr)-1;
        $imgName=$imageArr[$img_index];
        $img_filename = replace_file_domain($image);
        $camera_path = $img_filename;
        $file_handle = new file_handle();
        fdump_api(['$img_filename>>'.__LINE__, $img_filename],'baidu_face/baidu_face_search_log',true);
        if($file_handle->check_open_oss()) {
            //$file_handle->download($img_filename);
           // $img_filename = $file_handle->get_path($img_filename);
            
            $up_dir = $_SERVER['DOCUMENT_ROOT'].'/runtime/imgTmp/';
            if (!is_dir($up_dir)) {
                mkdir($up_dir, 0777, true);
            }
            $new_file = $up_dir. $imgName;
            $this->http->curlDownload($img_filename,$new_file);
            $image = $this->imgToBase64($new_file,false, 10*1024*1024);
            fdump_api([__LINE__, 'new_file'=>$new_file, 'img_filename'=>$img_filename],'baidu_face/baidu_face_search_log',true);
            unlink($new_file);
        }else{
            $img_filename = $_SERVER['DOCUMENT_ROOT'].str_replace(C('config.site_url'),'',$img_filename);
            $image = $this->imgToBase64($img_filename,false, 10*1024*1024);
        }
        fdump_api(['【BASE64】>>'.__LINE__, $img_filename, $image],'baidu_face/baidu_face_search_log',true);
        $data['image_type'] = 'BASE64';
        $data['image'] = $image;
//        $data['image_type'] = 'URL';
        $data['group_id_list'] = $city_id;

        // 非必传
        $data['quality_control'] = 'NONE'; // 图片质量控制 NONE: 不进行控制 LOW:较低的质量要求 NORMAL: 一般的质量要求 HIGH: 较高的质量要求 默认 NONE 若图片质量不满足要求，则返回结果中会提示质量检测失败
        $data['liveness_control'] = 'NONE'; // 活体检测控制 NONE: 不进行控制 LOW:较低的活体要求(高通过率 低攻击拒绝率) NORMAL: 一般的活体要求(平衡的攻击拒绝率, 通过率) HIGH: 较高的活体要求(高攻击拒绝率 低通过率) 默认NONE 若活体检测结果不满足要求，则返回结果中会提示活体检测失败

        $post_data = http_build_query($data);


        $msg_arr = $this->http->curlPostOwn($url, $post_data);
        $info = json_decode($msg_arr, true);
        fdump_api(['请求接口>>'.__LINE__, $url, $post_data, $msg_arr, $info],'baidu_face/baidu_face_search_log',true);
        if (!$info['error_code']) {
            if ($info['result']) {
                $user_list = $info['result']['user_list'];
                $face_token = $info['result']['face_token'];
                fdump_api(['返回参数>>'.__LINE__, $img_filename, $user_list],'baidu_face/baidu_face_search_log',true);
                if ($user_list) {
                    $d_house_village_user_bind = M('House_village_user_bind');
                    $d_house_camera_tip = M('House_camera_tip');
//        $img_mer_id = sprintf("%09d", $id);
//        $rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
                    /*
                    $rand_num = date('Ymd');// 换成日期存储
                    $up_dir = $_SERVER['DOCUMENT_ROOT'].'/upload/camera_log/'.$rand_num.'/';
                    if (!is_dir($up_dir)) {
                        mkdir($up_dir, 0777, true);
                    }
                    $img_type = pathinfo($img_filename, PATHINFO_EXTENSION);
                    $file_name = date('YmdHis_').uniqid().'.'.$img_type;
                    $new_file = $up_dir.$file_name;
                    $camera_img = '/upload/camera_log/'.$rand_num.'/'.$file_name;
                    $img = copy($img_filename,$new_file);
                    fdump_api(['图片处理>>'.__LINE__, $img, $camera_img, $new_file, $img_type, $img_filename, $up_dir, $camera_path],'baidu_face/baidu_face_search_log',true);
                    if ($img) {
                        if($file_handle->check_open_oss()) {
                            unlink($img_filename); //直接删除旧目录下的文件
                            $file_handle->unlink($img_filename);
                        }else{
                            unlink($img_filename); //直接删除旧目录下的文件
                        }
                    }
                    */
                    if($file_handle->check_open_oss()) {
                        unlink($img_filename); //直接删除旧目录下的文件
                        $file_handle->unlink($img_filename);
                    }else{
                        unlink($img_filename); //直接删除旧目录下的文件
                    }
                    foreach ($user_list as $val) {
                        $user_id = $val['user_id'];
                        $user_bind = $d_house_village_user_bind->where(array('uid'=>$user_id, 'village_id' => $house_camera_device['village_id'],array('type'=> array('neq',4))))->find();
                        if (empty($user_bind)) {
                            $user_bind = $d_house_village_user_bind->where(array('uid'=>$user_id, 'village_id' => $house_camera_device['village_id']))->find();
                        }
                        $tip = array(
                            'bind_id' =>  $user_bind['pigcms_id'],
                            'uid' => $user_id,
                            'village_id' => $user_bind['village_id'],
                            'camera_sn' => $house_camera_img['camera_sn'],
                            'city_id' => $city_id,
                            'camera_time' => $house_camera_img['camera_time'],
                            'camera_img' => $house_camera_img['camera_url'],
                            'tip_time' => time()
                        );
                        $d_house_camera_tip->data($tip)->add();
                        fdump_api(['记录>>'.__LINE__, $tip, $d_house_camera_tip->_sql()],'baidu_face/baidu_face_search_log',true);
                    }
                } else {
                    if($file_handle->check_open_oss()) {
                        unlink($img_filename); //直接删除旧目录下的文件
                        $file_handle->unlink($img_filename);
                    }else{
                        unlink($img_filename); //直接删除旧目录下的文件
                    }
                }
            } else {
                if($file_handle->check_open_oss()) {
                    unlink($img_filename); //直接删除旧目录下的文件
                    $file_handle->unlink($img_filename);
                }else{
                    unlink($img_filename); //直接删除旧目录下的文件
                }
            }
        } else {
            fdump_api(['返回错误>>'.__LINE__, $info],'baidu_face/baidu_face_search_log',true);
        }
        // 记录摄像机图片对比和对比结果
        $img = array();
        $img['img_id'] = $img_id;
        $img['is_ai_face'] = 1;
        $img['ai_error_code'] = $info['error_code'];
        $img['ai_error_msg'] = $info['error_msg'];
        $img['ai_timestamp'] = $info['timestamp'];
        $d_house_camera_img->data($img)->save();

        return $info;
    }

    // 百度AI 人脸注册获取
    public function baidu_face_get($village_id=49,$uid = 1358622,$bind_id = 3019) {
        $url = 'https://aip.baidubce.com/rest/2.0/face/v3/faceset/user/get';
        $data = array();

        $access_token = $this->get_token();
        if (!$access_token['error']) {
            $access_token = $access_token['access_token'];
        } else {
            fdump('>>baidu_face_get>>'.__LINE__,'api/log/'.date('Ymd').'/baidu_face/plan_access_token_log',true);
            fdump($access_token,'api/log/'.date('Ymd').'/baidu_face/plan_access_token_log',true);
            return true;
        }

        $url .= '?access_token='.$access_token;

        $image_url = M('House_face_img')->where(array('uid'=>$uid,'status'=>['in', [0,3]],'img_url'=>array('neq','')))->order('add_time ASC')->find();

        import('@.ORG.face.word_encryption');
        $word_encryption = new word_encryption();
        foreach($image_url as $kk=>&$vv){
            if (3==$vv['status']) {
                // 加密的图片解密
                $vv['img_url'] = $word_encryption->text_decrypt($vv['img_url']);
            }
        }
        $house_village = M('House_village')->where(array('village_id' => $village_id))->find();

        $city_id = $house_village['city_id'];

        $data['group_id'] = $city_id;
        $data['user_id'] = $bind_id;
        $post_data = http_build_query($data);

        import('ORG.Net.Http');
        $http = new Http();
        $info = Http::curlPostOwn($url, $post_data);
        $info = json_decode($info, true);

        return $info;
    }

    /**
     * 获取图片的Base64编码(不支持url) 绝对路劲
     * @date 2017-02-20 19:41:22
     *
     * @param string $img_file 传入本地图片地址
     * @param bool $base_head 是否需要base64头部，默认需要
     * @param integer $max_size 所需最大转换base64 默认 2*1024*1024
     *
     * @return string
     */
    function imgToBase64($img_file, $base_head=true, $max_size=2097152) {

        $img_base64 = '';
        if (file_exists($img_file)) {
            $app_img_file = $img_file; // 图片路径
            $img_info = getimagesize($app_img_file); // 取得图片的大小，类型等
            dump($img_info);

            $fp = fopen($app_img_file, "r"); // 图片是否可读权限

            if ($fp) {
                $filesize = filesize($app_img_file);
                if ($filesize>intval($max_size)) {
                    $filesize = $max_size;
                }
                dump($filesize);
                $content = fread($fp, $filesize);
                $file_content = chunk_split(base64_encode($content)); // base64编码

                if ($base_head) {
                    $img_base64 = 'data:image/' . $img_info['mime']  . ';base64,' . $file_content;//合成图片的base64编码
                } else {
                    $img_base64 = $file_content;//合成图片的base64编码
                }

            }
            fclose($fp);
        }

        return $img_base64; //返回图片的base64
    }

    public function add_face_img($data)
    {
        return $this->data($data)->add();
    }

    // 处理下访客通行和通行证相关

    /**
     * Notes:
     * @param $pigcms_id
     * @param $village_id
     * @param array $area_info // 具体位置  包含单元和公共区域
     * {
     *    '0'=> 'public_11', // 公共区域在其id前置拼接一个 public_
     *    '1'=> 'floor_11',  // 对应单元在其id前置拼接一个 floor_
     * }
     * @param int $owner_id
     * @param array $bind_info
     * @return bool
     * @author: wanzy
     * @date_time: 2021/1/8 13:28
     */
    public function face_user_to_device($pigcms_id, $village_id, $area_info = [], $owner_id=0, $bind_info=[]) {
        if (!$pigcms_id || !$village_id || (!$area_info && !$owner_id)) {
            fdump_api(['msg' => '参数不全','pigcms_id' => $pigcms_id, 'village_id' => $village_id, 'area_info' => $area_info, 'owner_id' => $owner_id, 'bind_info' => $bind_info], 'face_user/face_user_to_device_log', 1);
            return false;
        }
        $db_house_village_user_bind = M('House_village_user_bind');
        if (!$bind_info) {
            $where_bind = [
                'pigcms_id' => $pigcms_id
            ];
            $bind_info = $db_house_village_user_bind->where($where_bind)->find();
        }
        if (!$bind_info['uid']) {
            fdump_api(['msg' => 'uid不存在不执行同步','pigcms_id' => $pigcms_id, 'village_id' => $village_id, 'area_info' => $area_info, 'owner_id' => $owner_id, 'bind_info' => $bind_info], 'face_user/face_user_to_device_log', 1);
            return false;
        }
        if ($area_info) {
            $public_area_arr = [];
            $floor_arr = [];
            foreach ($area_info as $val) {
                $area_arr = explode('_',$val);
                if ($area_arr && 'public'==$area_arr[0] && $area_arr[1]) {
                    $public_area_arr[] = $area_arr[1];
                } elseif($area_arr && 'floor'==$area_arr[0] && $area_arr[1]) {
                    $floor_arr[] = $area_arr[1];
                }
            }
            if (empty($public_area_arr) && empty($floor_arr)) {
                fdump_api(['msg' => '处理后地址信息不正确','pigcms_id' => $pigcms_id, 'village_id' => $village_id, 'area_info' => $area_info, 'owner_id' => $owner_id, 'bind_info' => $bind_info], 'face_user/face_user_to_device_log', 1);
                return false;
            }
            $db_house_face_device = M('House_face_device');
            if ($public_area_arr) {
                $where_public = [
                    'public_area_id' => ['in', $public_area_arr],
                    'is_del' => 0
                ];
                $device_arr = $db_house_face_device->where($where_public)->select();
            }
            if ($floor_arr) {
                $floor_arr[] = -1;
                $where_floor = [
                    'floor_id' => ['in', $floor_arr],
                    'is_del' => 0
                ];
                $device_floor_arr = $db_house_face_device->where($where_floor)->select();
            }
            if ($device_floor_arr) {
                foreach ($device_floor_arr as $vb) {
                    $device_arr[] = $vb;
                }
            }
        } else {
            $device_arr = $this->house_user_face_device_info(0, $owner_id, true);
        }
        if ($device_arr) {
            foreach ($device_arr as $val) {
                if ($val['device_id']) {
                    if (1==$val['device_type']) {
                        try {
                            $this->add_person($val['device_id'],[$pigcms_id],$val['device_sn']);
                        }catch (Exception $e) {
                            fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/a1/face_user_a1_door_is_open_err',true);
                            fdump($val,'api/log/'.date('Ymd').'/a1/face_user_a1_door_is_open_err',true);
                            fdump($e->getMessage(),'api/log/'.date('Ymd').'/a1/face_user_a1_door_is_open_err',true);
                        }
                    } elseif (2==$val['device_type'] && $val['a2_face_door_doorkeeperId']>0) {
                        try {
                            D('House_village_face_door')->modify_arr([$pigcms_id]);
                        }catch (Exception $e) {
                            fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/a2/face_user_a2_door_is_open_err',true);
                            fdump($val,'api/log/'.date('Ymd').'/a2/face_user_a2_door_is_open_err',true);
                            fdump($e->getMessage(),'api/log/'.date('Ymd').'/a2/face_user_a2_door_is_open_err',true);
                        }
                    } elseif (3==$val['device_type']) {
                        try {
                            D('House_village_face_door')->a3_arr_person_msg($val['device_id'],[$pigcms_id],$val['device_sn']);
                        }catch (Exception $e) {
                            fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/a3/face_user_a3_door_is_open_err',true);
                            fdump($val,'api/log/'.date('Ymd').'/a3/face_user_a3_door_is_open_err',true);
                            fdump($e->getMessage(),'api/log/'.date('Ymd').'/a3/face_user_a3_door_is_open_err',true);
                        }
                    } else if (5 == $val['device_type'] || 6 == $val['device_type']) {
                        try {
                            D('Face_door_a5_service')->synDeviceDispose($val['device_id'],[$pigcms_id],$val['device_sn']);
                        }catch (Exception $e) {
                            fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/a5/face_user_a5_door_is_open_err',true);
                            fdump($val,'api/log/'.date('Ymd').'/a5/face_user_a5_door_is_open_err',true);
                            fdump($e->getMessage(),'api/log/'.date('Ymd').'/a5/face_user_a5_door_is_open_err',true);
                        }
                    } elseif (7==$val['device_type']) {
                        try {
                            D('Face_door_a7_service')->a7_arr_person_msg($val['device_id'],[$pigcms_id]);
                        }catch (Exception $e) {
                            fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/a7/face_user_a7_door_is_open_err',true);
                            fdump($val,'api/log/'.date('Ymd').'/a7/face_user_a7_door_is_open_err',true);
                            fdump($e->getMessage(),'api/log/'.date('Ymd').'/a7/face_user_a7_door_is_open_err',true);
                        }
                    } elseif (4==$val['device_type']) {
                        try {
                            D('Face_door_a4_service')->a4_arr_person_msg($val['device_id'],[$pigcms_id]);
                        }catch (Exception $e) {
                            fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/A4/face_user_a4_door_is_open_err',true);
                            fdump($val,'api/log/'.date('Ymd').'/A4/face_user_a4_door_is_open_err',true);
                            fdump($e->getMessage(),'api/log/'.date('Ymd').'/A4/face_user_a4_door_is_open_err',true);
                        }
                    }elseif(88 == $val['device_type']){
                        try {
                            D('Dahua_service')->dahua_arr_person_msg($val['device_id'],[$pigcms_id]);
                            fdump_api(['人员数据写入>>'.__LINE__,$val['bind_arr_id']],'dahua/dahua_arr_person',1);
                        }catch (Exception $e) {
                            fdump_api($val,'dahua/dahua_door_is_open_err',true);
                            fdump_api($e->getMessage(),'dahua/dahua_door_is_open_err',true);
                        }
                    }
                }
            }
        } else {
            fdump('处理后发现没有对应门禁>>','api/log/'.date('Ymd').'/face_user/face_user_to_device_log',true);
            fdump($pigcms_id,'api/log/'.date('Ymd').'/face_user/face_user_to_device_log',true);
            fdump($village_id,'api/log/'.date('Ymd').'/face_user/face_user_to_device_log',true);
            fdump($public_area_arr,'api/log/'.date('Ymd').'/face_user/face_user_to_device_log',true);
            fdump($floor_arr,'api/log/'.date('Ymd').'/face_user/face_user_to_device_log',true);
            fdump($device_arr,'api/log/'.date('Ymd').'/face_user/face_user_to_device_log',true);
            return true;
        }
    }


    //================跑腿人脸注册===============

    // 百度AI 人脸注册
    public function deliver_baidu_face_add($uid) {
        $url = $this->baidu_face_add;
        $data = array();
        fdump_api(['baidu_face_add>>'.__LINE__, $uid],'baidu_face/baidu_face_add_log',true);

        $access_token = $this->get_deliver_token();
        fdump_api(['line>>'.__LINE__, $access_token],'baidu_face/baidu_face_add_log',true);
        if (!$access_token['error'] && $access_token['access_token']) {
            $access_token = $access_token['access_token'];
        } else {
            fdump_api(['token获取失败>>'.__LINE__, $access_token],'baidu_face/baidu_face_add_log',true);
            return false;
        }
        if (!$uid) {
            fdump_api(['$uid不存在>>'.__LINE__, $access_token],'baidu_face/baidu_face_add_log',true);
            return false;
        }

        $url .= '?access_token='.$access_token;

        $uData = M('Deliver_user')->where(array('uid' => $uid))->find();
        $image_url = $uData['face_recognition_images'];

        $city_id = $this->deliver_group_id;
        $d_baidu_ai_face_log = M('Baidu_ai_face_log');

        if (!$this->http) {
            import('ORG.Net.Http');
            $this->http = new Http();
        }
        import('@.ORG.face.word_encryption');
        $img_filename = replace_file_domain($image_url);
        $data['image'] = $img_filename;
        $data['image_type'] = 'URL';
        $data['group_id'] = $city_id;
        $data['user_id'] = $uid;

        // 非必传
        $data['quality_control'] = 'NONE'; // 图片质量控制 NONE: 不进行控制 LOW:较低的质量要求 NORMAL: 一般的质量要求 HIGH: 较高的质量要求 默认 NONE 若图片质量不满足要求，则返回结果中会提示质量检测失败
        $data['liveness_control'] = 'NONE'; // 活体检测控制 NONE: 不进行控制 LOW:较低的活体要求(高通过率 低攻击拒绝率) NORMAL: 一般的活体要求(平衡的攻击拒绝率, 通过率) HIGH: 较高的活体要求(高攻击拒绝率 低通过率) 默认NONE 若活体检测结果不满足要求，则返回结果中会提示活体检测失败
        $data['action_type'] = 'APPEND'; // APPEND: 当user_id在库中已经存在时，对此user_id重复注册时，新注册的图片默认会追加到该user_id下 REPLACE : 当对此user_id重复注册时,则会用新图替换库中该user_id下所有图片 默认使用APPEND

        $post_data = http_build_query($data);
        $msg_arr = $this->http->curlPostOwn($url, $post_data);
        $info = json_decode($msg_arr, true);
        fdump_api(['line>>'.__LINE__, $url, $post_data, $msg_arr, $info, $img_filename],'baidu_face/baidu_face_add_log',true);
        if (!$info['error_code']) {
            $log = array();
            $log['city_id'] = $city_id;
            $log['village_id'] = 0;
            $log['uid'] = $uid;
            $log['face_img_id'] = 0;
            $msg = $d_baidu_ai_face_log->where($log)->find();
            $log['baidu_log_id'] = $info['log_id'];
            $log['timestamp'] = $info['timestamp'];
            $log['face_token'] = $info['result']['face_token'];
            $log['location'] = serialize($info['result']['location']);
            if ($msg) {
                $log['log_id'] = $msg['log_id'];
                $log['last_time'] = time();
                $d_baidu_ai_face_log->data($log)->save();
            } else {
                $log['add_time'] = time();
                $d_baidu_ai_face_log->data($log)->add();
            }
        }

        return $info;
    }
    // 百度AI 人脸编辑 先对应删除 再进行添加
    public function deliver_baidu_face_edit($uid) {
        fdump_api(['baidu_face_edit>>'.__LINE__, $uid],'baidu_face/baidu_face_edit_log',true);
        M('Deliver_user')->where(array('uid' => $uid))->data(['face_recognition_images' => ''])->save();

        $city_id = $this->deliver_group_id;
        $d_baidu_ai_face_log = M('Baidu_ai_face_log');

        $image_url = $d_baidu_ai_face_log->where(array('uid'=>$uid,'city_id'=>$city_id,'village_id'=>0,'status'=>0))->order('add_time ASC')->select();
        if ($image_url) {
            $url = $this->baidu_face_delete;
            $access_token = $this->get_deliver_token();
            fdump_api(['access_token>>'.__LINE__, $access_token],'baidu_face/baidu_face_edit_log',true);
            if (!$access_token['error'] && $access_token['access_token']) {
                $access_token = $access_token['access_token'];
            } else {
                fdump_api(['token获取失败>>'.__LINE__, $access_token],'baidu_face/baidu_face_edit_log',true);
                return true;
            }
            $url .= '?access_token='.$access_token;
            $data = array();
            $data['user_id'] = $uid;
            $data['group_id'] = $city_id;
            if (!$this->http) {
                import('ORG.Net.Http');
                $this->http = new Http();
            }
            foreach ($image_url as $item) {
                $data['face_token'] = $item['face_token'];
                $post_data = http_build_query($data);
                $msg_arr = $this->http->curlPostOwn($url, $post_data);
                $info = json_decode($msg_arr, true);
                fdump_api(['请求>>'.__LINE__, $url, $post_data, $msg_arr, $info],'baidu_face/baidu_face_edit_log',true);
            }
        }
        $info = $this->deliver_baidu_face_add($uid);
        return $info;
    }
    // 百度AI 人脸查询
    public function deliver_baidu_face_search($img_url) {
        fdump_api(['baidu_face_search>>'.__LINE__, $img_url],'baidu_face/baidu_face_search_log',true);
        $url = $this->baidu_face_search;
        $data = array();

        $access_token = $this->get_deliver_token();
        fdump_api(['line>>'.__LINE__, $access_token],'baidu_face/baidu_face_search_log',true);
        if (!$access_token['error'] && $access_token['access_token']) {
            $access_token = $access_token['access_token'];
        } else {
            fdump_api(['token获取失败>>'.__LINE__, $access_token],'baidu_face/baidu_face_search_log',true);;
            return true;
        }
        $url .= '?access_token='.$access_token;

        $img_filename = replace_file_domain($img_url);
        $camera_path = $img_filename;
        $data['image_type'] = 'URL';
        $data['image'] = $img_filename;
        $data['group_id_list'] = $this->deliver_group_id;

        // 非必传
        $data['quality_control'] = 'NONE'; // 图片质量控制 NONE: 不进行控制 LOW:较低的质量要求 NORMAL: 一般的质量要求 HIGH: 较高的质量要求 默认 NONE 若图片质量不满足要求，则返回结果中会提示质量检测失败
        $data['liveness_control'] = 'NONE'; // 活体检测控制 NONE: 不进行控制 LOW:较低的活体要求(高通过率 低攻击拒绝率) NORMAL: 一般的活体要求(平衡的攻击拒绝率, 通过率) HIGH: 较高的活体要求(高攻击拒绝率 低通过率) 默认NONE 若活体检测结果不满足要求，则返回结果中会提示活体检测失败

        $post_data = http_build_query($data);

        if (!$this->http) {
            import('ORG.Net.Http');
            $this->http = new Http();
        }
        $msg_arr = $this->http->curlPostOwn($url, $post_data);
        $info = json_decode($msg_arr, true);
        fdump_api(['请求接口>>'.__LINE__, $url, $post_data, $msg_arr, $info],'baidu_face/baidu_face_search_log',true);
        if (!$info['error_code']) {
            if ($info['result']) {
                $user_list = $info['result']['user_list'];
                $face_token = $info['result']['face_token'];
                fdump_api(['返回参数>>'.__LINE__, $img_filename, $user_list],'baidu_face/baidu_face_search_log',true);
                if ($user_list) {
                    $rand_num = date('Ymd');// 换成日期存储
                    $up_dir = $_SERVER['DOCUMENT_ROOT'].'/upload/camera_log/'.$rand_num.'/';
                    if (!is_dir($up_dir)) {
                        mkdir($up_dir, 0777, true);
                    }
                    $img_type = pathinfo($img_filename, PATHINFO_EXTENSION);
                    $file_name = date('YmdHis_').uniqid().'.'.$img_type;
                    $new_file = $up_dir.$file_name;
                    $camera_img = '/upload/camera_log/'.$rand_num.'/'.$file_name;
                    $img = copy($img_filename,$new_file);
                    fdump_api(['图片处理>>'.__LINE__, $img, $camera_img, $new_file, $img_type, $img_filename, $up_dir, $camera_path],'baidu_face/baidu_face_search_log',true);
                    if ($img) {
                        unlink($img_filename); //直接删除旧目录下的文件
                    }
                    $user_id = $user_list[0]['user_id'];
                    $user_bind = M('Deliver_user')->where(array('uid' => $user_id))->find();
                    return $user_bind;
                } else {
                    unlink($img_filename); //直接删除旧目录下的文件
                }
            } else {
                unlink($img_filename); //直接删除旧目录下的文件
            }
        } else {
            fdump_api(['返回错误>>'.__LINE__, $info],'baidu_face/baidu_face_search_log',true);
        }

        return false;
    }
    // 百度AI 人脸删除
    public function deliver_baidu_face_del($uid) {
        fdump_api(['baidu_face_edit>>'.__LINE__, $uid],'baidu_face/baidu_face_edit_log',true);
        M('Deliver_user')->where(array('uid' => $uid))->data(['face_recognition_images' => ''])->save();

        $city_id = $this->deliver_group_id;
        $d_baidu_ai_face_log = M('Baidu_ai_face_log');

        $image_url = $d_baidu_ai_face_log->where(array('uid'=>$uid,'city_id'=>$city_id,'village_id'=>0,'status'=>0))->order('add_time ASC')->select();
        if ($image_url) {
            $url = $this->baidu_face_delete;
            $access_token = $this->get_deliver_token();
            fdump_api(['access_token>>'.__LINE__, $access_token],'baidu_face/baidu_face_edit_log',true);
            if (!$access_token['error'] && $access_token['access_token']) {
                $access_token = $access_token['access_token'];
            } else {
                fdump_api(['token获取失败>>'.__LINE__, $access_token],'baidu_face/baidu_face_edit_log',true);
                return true;
            }
            $url .= '?access_token='.$access_token;
            $data = array();
            $data['user_id'] = $uid;
            $data['group_id'] = $city_id;
            if (!$this->http) {
                import('ORG.Net.Http');
                $this->http = new Http();
            }
            foreach ($image_url as $item) {
                $data['face_token'] = $item['face_token'];
                $post_data = http_build_query($data);
                $msg_arr = $this->http->curlPostOwn($url, $post_data);
                $info = json_decode($msg_arr, true);
                fdump_api(['请求>>'.__LINE__, $url, $post_data, $msg_arr, $info],'baidu_face/baidu_face_edit_log',true);
            }
        }
        return true;
    }

    /**百度AI 人脸识别*/
    // 获取 access_token
    public function get_deliver_token() {
        $d_baidu_ai_code = M('Deliver_baidu_ai_code');
        $baidu_ai_code = $d_baidu_ai_code->where(['api_key' => C('config.deliver_baidu_ai_api_key')])->find();
        fdump_api(['get_token'.__LINE__, $baidu_ai_code],'baidu_face/get_ai_token_log',true);

        if (empty($baidu_ai_code) || intval($baidu_ai_code['expires_in'])+intval($baidu_ai_code['token_time']) < time()) {
            // 重新获取
            $url = $this->accessTokenUrl;
            $post_data['grant_type'] = 'client_credentials';
            $post_data['client_id'] = C('config.deliver_baidu_ai_api_key');
            $post_data['client_secret'] = C('config.deliver_baidu_ai_secret_key');
            $post_data = http_build_query($post_data);

            if (!$this->http) {
                import('ORG.Net.Http');
                $this->http = new Http();
            }
            fdump_api(['get_token'.__LINE__, $url],'baidu_face/get_ai_token_log',true);
            $result_info = $this->http->curlPostOwn($url, $post_data);
            $result = json_decode($result_info, true);
            fdump_api(['get_token'.__LINE__, $post_data, $result_info, $result],'baidu_face/get_ai_token_log',true);
            if ($result['errcode']) {
                fdump_api(['get_token_err'.__LINE__, $url, $post_data, $result_info, $result],'baidu_face/get_ai_token_err_log',true);
                return array('error'=>1,'msg'=>'刷新token获取失败');
            }else{
                $data = array();
                if ($baidu_ai_code) {
                    $data['code_id'] = $baidu_ai_code['code_id'];
                    $data['access_token'] = $result['access_token'];
                    $data['refresh_token'] = $result['refresh_token'];
                    $data['expires_in'] = $result['expires_in'];
                    $data['session_key'] = $result['session_key'];
                    $data['session_secret'] = $result['session_secret'];
                    $data['token_time'] = time();
                    $add_id = $d_baidu_ai_code->where(['api_key' => $baidu_ai_code['api_key']])->save($data);
                } else {
                    $data['access_token'] = $result['access_token'];
                    $data['refresh_token'] = $result['refresh_token'];
                    $data['expires_in'] = $result['expires_in'];
                    $data['session_key'] = $result['session_key'];
                    $data['session_secret'] = $result['session_secret'];
                    $data['token_time'] = time();
                    $data['create_time'] = time();
                    $data['api_key'] = C('config.deliver_baidu_ai_api_key');
                    $add_id = $d_baidu_ai_code->data($data)->add();
                }
                fdump_api(['get_token_err'.__LINE__, $add_id, $data, $d_baidu_ai_code->_sql()],'baidu_face/get_ai_token_err_log',true);
                return array('error'=>0,'access_token'=>$result['access_token']);
            }
        }else{
            return array('error'=>0,'access_token'=>$baidu_ai_code['access_token']);
        }
    }

}