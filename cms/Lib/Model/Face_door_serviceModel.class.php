<?php
/**
 * Created by PhpStorm.
 * Author: wanziyang
 * Date Time: 2020/6/8 10:55
 */
class Face_door_serviceModel extends Model{
    public function face_device_check($device_id, $face_device_data = [],$otherParam=[]) {
        $hfd = D('House_face_device');
        if (empty($face_device_data) || !$face_device_data['village_id'] || !$face_device_data['device_type'] || !$face_device_data['device_sn']) {
            $face_device_data = $hfd->where(array('device_id' => $device_id))->find();
        }
        if (empty($face_device_data) || !$face_device_data['device_type'] || !$face_device_data['village_id']) {
            return true;
        }
        $thirdProtocol = isset($face_device_data['thirdProtocol']) && intval($face_device_data['thirdProtocol']) > 0 ? intval($face_device_data['thirdProtocol']) : 0;
        $device_type = $face_device_data['device_type'];
        $village_id = $face_device_data['village_id'];
        $logPath = 'a'.$device_type;
        // 生成一键同步命令组
        $orderGroupId = isset($otherParam['orderGroupId'])&&$otherParam['orderGroupId']?trim($otherParam['orderGroupId']):md5(uniqid().$_SERVER['REQUEST_TIME']);// 标记统一执行命令
        fdump_api(['一键同步'.__LINE__,$device_id, $face_device_data,$logPath],"{$logPath}/face_device_check_log",1);
        $deviceLogParam = [
            'log_name' => 'adminDeviceAllSyn','logType' => 'houseAdmin','log_status' => 20,
            'log_content' => '设备一键同步','device_id' => $device_id,
            'device_sn' => $face_device_data['device_sn']?$face_device_data['device_sn']:'',
            'device_type' => $device_type,'village_id' => $village_id,
            'addTime' => time(),'orderGroupId' => $orderGroupId,
        ];
        try {
            D('House_face_device_log')->addLog($deviceLogParam);
        } catch( \Exception $e) {
            // 记录日志报错
            fdump_api(['日志记录出错[设备一键同步]'.__LINE__.'方法'.__FUNCTION__,$deviceLogParam,$e->getMessage()],'faceDeviceLog/errDeviceLog',1);
        }
        $hikGateKeyVillage = $hfd::HIK_DEVICE_GATEWAY_CUSTOM;
        $hikGateKey = $hfd::HIK_DEVICE_GATEWAY;
        $hikYunMoNeiBuSheQu = $hfd::HIK_YUNMO_NEIBU_SHEQU;
        if ($thirdProtocol > 0 && $thirdProtocol == $hikYunMoNeiBuSheQu) {
            $models = '\app\community\model\service\FaceDeviceService/deviceDhAllSyn';
            $param = [
                'device_id' => $device_id, 'device_sn' => $face_device_data['device_sn'],
                'village_id' => $village_id, 'thirdProtocol' => $thirdProtocol, 'deviceType' => 'face',
            ];
            invoke_v20_service($models,$param);
        } elseif ($thirdProtocol > 0 && in_array($thirdProtocol, [$hikGateKey, $hikGateKeyVillage])) {
            $models = '\app\community\model\service\Device\HikGateWayDeviceService/deviceAllToCloud';
            $param = [
                'deviceInfo' => [],
                'param'     => [
                    'village_id'    => $village_id,
                    'device_id'     => $device_id,
                    'operation'     => 'deviceAllToCloud',
                    'device_ip'     => $face_device_data['device_ip'],
                    'orderGroupId'  => $orderGroupId,
                    'thirdProtocol' => $thirdProtocol,
                    'deviceType'    => 'face',
                ]
            ];
            invoke_v20_service($models,$param);
        } elseif (3==$device_type) {
            // 直接进行同步-是否有需要同步的改为计划任务中进行判断
//            $this->sync_a3_face_device_door($device_id);
            $param = array();
            $param['type'] = 'sync_a3_face_device_door';
            $param['device_id'] = $device_id;
            $param['orderGroupId'] = $orderGroupId;
            fdump_api(['A3智能门禁参数'.__LINE__,$param],"{$logPath}/face_device_check_log",1);
            $this->add_plan($param,'sub_house_village', -11);
        }
        elseif (in_array($device_type,[5,6])) {
            // 人脸门禁类型A5和A6 统一考虑
            $param = array();
            $param['type'] = 'sync_a5_face_member';
            $param['device_id'] = $device_id;
            $param['village_id'] = $face_device_data['village_id'];
            $param['device_sn'] = $face_device_data['device_sn'];
            if (trim($face_device_data['floor_id']) && trim($face_device_data['floor_id'])!=-1) {
                $param['floor_id'] = trim($face_device_data['floor_id']);
            }
            $param['unique_id'] = 'a5_face_device_'.$param['village_id'].'_'.$device_id;
            $param['orderGroupId'] = $orderGroupId;
            fdump_api(['A5智能门禁参数'.__LINE__,$param],"{$logPath}/face_device_check_log",1);
            $a5DeviceAllSynPlanTime = D('House_face_device_log')->a5DeviceAllSynPlanTime;
            $this->add_plan($param, 'sub_house_village_face_device', $a5DeviceAllSynPlanTime);
            $a5_get_channel_msg = M('Process_sub_plan')->where(['unique_id' => 'a5_get_channel_msg'])->find();
            //2020/8/1 消息通道调试  修改  start(新增)
            if (!$a5_get_channel_msg) { //之前存在 2020/8/1
                // 15秒执行一次
                $now_time = time();
                $param = array();
                $param['type'] = 'a5_get_channel_msg';
                $param['device_id'] = $device_id;
                $param['village_id'] = $face_device_data['village_id'];
                $param['time'] = $now_time;
                $param['orderGroupId'] = $orderGroupId;
                $arr['param'] = serialize($param);
                $arr['plan_time'] = time();
                $arr['space_time'] = 15;
                $arr['add_time'] = $now_time;
                $arr['file'] = 'sub_house_village_face_device';
                $arr['time_type'] = 1;
                $arr['unique_id'] = 'a5_get_channel_msg';
				$arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
                fdump_api(['消息通道调试,15秒执行一次'.__LINE__,$param],"{$logPath}/face_device_check_log",1);
                M('Process_sub_plan')->data($arr)->add();
            }
        }
        elseif (7==$device_type) {
            $param = array();
            $param['type'] = 'sync_a7_face_all_per';
            $param['device_id'] = $device_id;
            $param['village_id'] = $face_device_data['village_id'];
            if ($face_device_data['floor_id']) {
                $param['floor_id'] = $face_device_data['floor_id'];
            }
            $param['orderGroupId'] = $orderGroupId;
            $param['unique_id'] = 'sync_a7_face_all_per_'.$param['village_id'].'_'.$device_id;
            fdump_api(['A7智能门禁参数'.__LINE__,$param],"$logPath/face_device_check_log",1);
            $this->add_plan($param, 'sub_house_village_face_device', -11);
        }
        elseif (4==$face_device_data['device_type']) {
            $config_data = D('Config_data')->get_config_data();
            $a4_to_new_traits_job = isset($config_data['a4_to_new_traits_job']) ? intval($config_data['a4_to_new_traits_job']) : 0;
            if ($a4_to_new_traits_job == 1) {
                $params = ['type' => 'add_a4_community', 'village_id' => $face_device_data['village_id'], 'device_id' => $device_id, 'orderGroupId' => $orderGroupId];
                $invokeV20ServiceParams = ['params' => $params];
                $models = '\app\community\model\service\Device\A4DeviceService/addA4CommunityJobBox';
                return invoke_v20_service($models, $invokeV20ServiceParams);
            }
            $param = array();
            $param['type'] = 'add_a4_community';
            $param['device_id'] = $device_id;
            $param['village_id'] = $face_device_data['village_id'];
            $unique_id = 'a3_face_device';
            // 处理下字段问题-如果存在拼接到唯一键值上
            if (isset($param['village_id'])) {
                $unique_id .='_'.$param['village_id'];
            }
            if (isset($param['communityId'])) {
                $unique_id .='_'.$param['communityId'];
            }
            if (isset($param['zoneId'])) {
                $unique_id .='_'.$param['zoneId'];
            }
            if (isset($param['single_id'])) {
                $unique_id .='_'.$param['single_id'];
            }
            if (isset($param['buildingId'])) {
                $unique_id .='_'.$param['buildingId'];
            }
            if (isset($param['roomId'])) {
                $unique_id .='_'.$param['roomId'];
            }
            if (isset($param['device_id'])) {
                $unique_id .='_'.$param['device_id'];
            }
            $param['unique_id'] = $unique_id;
            $param['orderGroupId'] = $orderGroupId;
            fdump_api(['A4智能门禁参数'.__LINE__,$param],"$logPath/face_device_check_log",1);
            $this->add_plan($param, 'sub_house_village_face_device');
        }
        elseif (21==$face_device_data['device_type']) {
            $face_device_data['orderGroupId'] = $orderGroupId;
            D('Face_door_d1_service')->face_d1_door_syn($device_id, $face_device_data, 'all_syn',[]);
        }
        elseif (23==$face_device_data['device_type']) {
            /**
             * todo d3计划任务
             */
            $param = array();
            $param['type'] = 'sync_d3_face_all_per';
            $param['device_id'] = $device_id;
            $param['village_id'] = $face_device_data['village_id'];
            if ($face_device_data['floor_id']) {
                $param['floor_id'] = $face_device_data['floor_id'];
            }
            $param['unique_id'] = 'sync_d3_face_all_per_'.$param['village_id'].'_'.$device_id;
            $param['orderGroupId'] = $orderGroupId;
            fdump_api(['D3智能门禁参数'.__LINE__,$param],"$logPath/face_device_check_log",1);
            $this->add_plan($param, 'sub_house_village_face_device', -11);
        }
        elseif (61==$face_device_data['device_type']) {
            /**
             * todo A185计划任务
             */

            $param = array();
            $param['type'] = 'sync_a185_face_all_per';

            $param['device_id'] = $device_id;
            $param['village_id'] = $face_device_data['village_id'];
            if ($face_device_data['floor_id']) {
                $param['floor_id'] = $face_device_data['floor_id'];
            }

            $param['unique_id'] = 'sync_a185_face_all_per_'.$param['village_id'].'_'.$device_id;
            $param['orderGroupId'] = $orderGroupId;
            fdump_api(['A185智能门禁参数'.__LINE__,$param],"$logPath/face_device_check_log",1);
            $this->add_plan($param, 'sub_house_village_face_device', -11);
        }
        elseif (24 ==$face_device_data['device_type']) {
            /**
             * todo D4智能门禁 计划任务
             */
            $param = array();
            $param['type'] = 'sync_d4_face_all_per';
            $param['device_id'] = $device_id;
            $param['village_id'] = $face_device_data['village_id'];
            if ($face_device_data['floor_id']) {
                $param['floor_id'] = $face_device_data['floor_id'];
            }
            $param['unique_id'] = 'sync_d4_face_all_per_'.$param['village_id'].'_'.$device_id;
            $param['orderGroupId'] = $orderGroupId;
            fdump_api(['D4智能门禁参数'.__LINE__,$param],"$logPath/face_device_check_log",1);
            $this->add_plan($param, 'sub_house_village_face_device', -11);
        }
        return true;
    }


    public function add_plan($param, $file = 'sub_house_village', $plan_time=0){
        // 处理下字段问题-如果不存在或者小于0 均重置为0
        if (!$param['device_id'] || intval($param['device_id'])<0) {
            $param['device_id'] = 0;
        }
        $now_time = time();
        $arr= array();
        $arr['param'] = serialize($param);
        $arr['plan_time'] = $plan_time;
        $arr['space_time'] = 0;
        $arr['add_time'] = $now_time;
        $arr['file'] = $file;
        if (!isset($param['time_type'])) {
            $arr['time_type'] = 1;
        } else {
            $arr['time_type'] = $param['time_type'];
        }
        if (!isset($param['unique_id'])) {
            $arr['unique_id'] = $param['type'].'_'.$param['device_id'];
        } else {
            $arr['unique_id'] = $param['unique_id'];
        }
		$arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
        $task_id = M('Process_sub_plan')->data($arr)->add();
        return array('task_id'=>$task_id);
    }


    public function sync_a3_face_device_door($device_id,$face_device_data=[]) {
        if (empty($face_device_data)) {
            $face_device_data = M('House_face_device')->where(array('device_id' => $device_id))->find();
        }
        if (empty($face_device_data)) {
            return true;
        }
        // 查询下同属于一个单元 或者 都是 公共区域的 门禁
        $where_face_img = [
            'hvu.village_id' => $face_device_data['village_id'],
        ];
        if ($face_device_data['floor_id']!=-1) {
            $floor_arr = explode(',',$face_device_data['floor_id']);
            if (count($floor_arr)==1) {
                $floor_id = $floor_arr[0];
            } else {
                $floor_id = 0;
            }
            if ($floor_id>0) {
                $where_face_img['_string'] = "hvu.floor_id={$floor_id} OR hvu.type=4";
            } else if($floor_arr && !$floor_id) {
                $where_face_img['_string'] = "hvu.floor_id in ({$face_device_data['floor_id']}) OR hvu.type=4";
            }
        }
        $where_face_img['hfi.status'] = ['in', [0,3]];
        $where_face_img['hfi.img_url'] = array('neq','');
        $bind_list = M('House_village_user_bind')
            ->join('as hvu LEFT JOIN '.C('DB_PREFIX').'house_face_img as hfi ON hfi.uid = hvu.uid')
            ->where($where_face_img)
            ->group('hvu.pigcms_id')
            ->field('hvu.pigcms_id')
            ->select();
        if ($bind_list) {
            $d_house_village_face_door = D('House_village_face_door');
            $now_time = time();
            foreach($bind_list as $val) {
                $param = array();
                $param['type'] = 'sync_a3_face';
                $param['device_id'] = $device_id;
                $param['pigcms_id'] = $val['pigcms_id'];
                $arr= array();
                $arr['param'] = serialize($param);
                $arr['plan_time'] = -9;
                $arr['space_time'] = 0;
                $arr['add_time'] = $now_time;
                $arr['file'] = 'sub_house_face';
                $arr['time_type'] = 1;
                $arr['unique_id'] = 'a3_face_device_'.$device_id.'_'.$val['pigcms_id'];
				$arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
                $task_id = M('Process_sub_plan')->data($arr)->add();
            }
        } else {
            fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/sync_a3_face_device_door_err',true);
            fdump($device_id,'api/log/'.date('Ymd').'/sync_a3_face_device_door_err',true);
            fdump($face_device_data,'api/log/'.date('Ymd').'/sync_a3_face_device_door_err',true);
            fdump(M('House_village_user_bind')->getDbError(),'api/log/'.date('Ymd').'/sync_a3_face_device_door_err',true);
            fdump('<<<<<<<<<<<<<<<<-line-'.__LINE__,'api/log/'.date('Ymd').'/sync_a3_face_device_door_err',true);
        }
        return true;
    }
    
    public function face_runtask($param){
        import('@.ORG.plan');
        $plan_class = new plan();
        $log_id = $param['log_id']??0;
        switch ($param['type']) {
            case 'sync_a3_face': //同步a3人脸
                $device_id = $param['device_id'];
                $pigcms_id = $param['pigcms_id'];
                if ($device_id && $pigcms_id) {
                    set_time_limit(0);
                    ini_set('memory_limit', '1024M');
                    $this->sync_a3_face($device_id, $pigcms_id,$log_id);
                }
                return 0;
            case 'd1_get_region_info': // D1设备提前同步 城市信息
                $device_id = $param['device_id'];
                $village_id = $param['village_id'];
                if ($device_id && $village_id) {
                    $param = [
                        'device_id' => $device_id,
                        'village_id' => $village_id,
                    ];
                    $msg = D('Face_door_d1_service')->d1_get_region_info($param, 0);
                    if (isset($msg['errcode']) && $msg['errcode']>0) {
                        fdump('D1计划任务错误[d1_get_region_info]>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                        fdump($param,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                        fdump($msg,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                    } elseif (isset($param['handle_type']) && $param['handle_type']!='single_syn' && $device_id) {
                        // 存在同步类型 且不是单独同步  后续调用
                        D('Face_door_d1_service')->face_d1_door_syn($device_id, [], $param['handle_type'],[], 1);
                    }
                }
                return 0;
            case 'd1_create_project': // D1设备提前同步 城市信息
                $device_id = $param['device_id'];
                $village_id = $param['village_id'];
                $project_region_uid = $param['project_region_uid'];
                if ($village_id) {
                    $msg = D('Face_door_d1_service')->d1_create_project($village_id, $project_region_uid, []);
                    if (isset($msg['errcode']) && $msg['errcode']>0) {
                        fdump('D1计划任务错误[d1_create_project]>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                        fdump($param,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                        fdump($msg,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                    } elseif (isset($param['handle_type']) && $param['handle_type']!='single_syn' && $device_id) {
                        // 存在同步类型 且不是单独同步  后续调用
                        $param_syn = [
                            'project_uid' => $msg
                        ];
                        D('Face_door_d1_service')->face_d1_door_syn($device_id, [], $param['handle_type'],$param_syn, 1);
                    }
                }
                return 0;
            case 'd1_project_all_building': // 同步一个小区下所有楼栋
                $device_id = $param['device_id'];
                $village_id = $param['village_id'];
                $project_uid = $param['project_uid'] ? $param['project_uid'] : '';
                if ($village_id) {
                    $msg = D('Face_door_d1_service')->d1_project_all_building($village_id, $project_uid, $param['handle_type'], $device_id, 1);
                    if (isset($msg['errcode']) && $msg['errcode']>0) {
                        fdump('D1计划任务错误[d1_project_all_building]>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                        fdump($param,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                        fdump($msg,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                    } elseif (isset($param['handle_type']) && $param['handle_type']!='single_syn' && $device_id) {
                        // 存在同步类型 且不是单独同步  后续调用
                        $param_syn = [
                            'project_uid' => $project_uid,
                            'is_syn_all_building' => 1
                        ];
                        D('Face_door_d1_service')->face_d1_door_syn($device_id, [], $param['handle_type'],$param_syn, 1);
                    }
                }
                return 0;
            case 'd1_add_building': // 同步一个楼栋
                $single_id = $param['single_id'];
                $device_id = $param['device_id'];
                $village_id = $param['village_id'];
                $project_uid = $param['project_uid'] ? $param['project_uid'] : '';
                $single_info = $param['single_info'] ? $param['single_info'] : [];
                if ($single_id) {
                    $msg = D('Face_door_d1_service')->d1_add_building($single_id,$village_id, $project_uid, $single_info);
                    if (isset($msg['errcode']) && $msg['errcode']>0) {
                        fdump('D1计划任务错误[d1_add_building]>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                        fdump($param,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                        fdump($msg,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                    } elseif (isset($param['handle_type']) && $param['handle_type']!='single_syn' && $device_id) {
                        // 添加同步楼栋下 房屋计划任务
                        $params = array();
                        $params['type'] = 'd1_building_all_room';
                        $params['handle_type'] = $param['handle_type'];
                        $params['single_id'] = $single_id;
                        $params['device_id'] = $device_id;
                        $params['village_id'] = $village_id;
                        if ($project_uid) {
                            $params['project_uid'] = $project_uid;
                        }
                        $params['building_uid'] = $msg;
                        $unique_id = 'd1_building_all_room';
                        if (isset($params['village_id'])) {
                            $unique_id .='_'.$params['village_id'];
                        }
                        if (isset($params['device_id'])) {
                            $unique_id .='_'.$params['device_id'];
                        }
                        $params['unique_id'] = $unique_id;
                        $this->add_plan($params, 'sub_house_face', -5);
                        // 存在同步类型 且不是单独同步  后续调用
                        $param_syn = [
                            'project_uid' => $project_uid,
                            'is_syn_all_building' => 1
                        ];
                        D('Face_door_d1_service')->face_d1_door_syn($device_id, [], $param['handle_type'],$param_syn, 1);
                    } else {
                        // 添加同步楼栋下 房屋计划任务
                        $params = array();
                        $params['type'] = 'd1_building_all_room';
                        $params['handle_type'] = $param['handle_type'];
                        $params['single_id'] = $single_id;
                        $params['device_id'] = $device_id;
                        $params['village_id'] = $village_id;
                        if ($project_uid) {
                            $params['project_uid'] = $project_uid;
                        }
                        $params['building_uid'] = $msg;
                        $unique_id = 'd1_building_all_room';
                        if (isset($params['village_id'])) {
                            $unique_id .='_'.$params['village_id'];
                        }
                        if (isset($params['device_id'])) {
                            $unique_id .='_'.$params['device_id'];
                        }
                        $params['unique_id'] = $unique_id;
                        $this->add_plan($params, 'sub_house_face', -5);
                    }
                }
                return 0;
            case 'd1_add_work_msg': // 同步物业使用的楼栋房屋
                $device_id = $param['device_id'];
                $village_id = $param['village_id'];
                $project_uid = $param['project_uid'] ? $param['project_uid'] : '';
                if ($village_id) {
                    $msg = D('Face_door_d1_service')->d1_add_work_msg($village_id, $project_uid, 1);
                    if (isset($msg['errcode']) && $msg['errcode']>0) {
                        fdump('D1计划任务错误[d1_add_work_msg]>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                        fdump($param,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                        fdump($msg,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                        if ($msg['err_type']!='single' && isset($param['handle_type']) && $param['handle_type']!='single_syn') {
                                // 存在同步类型 且不是单独同步  后续调用
                            $param_syn = [
                                'project_uid' => $project_uid,
                                'is_syn_all_building' => 1,
                                'is_syn_work' => 1
                            ];
                            D('Face_door_d1_service')->face_d1_door_syn($device_id, [], $param['handle_type'],$param_syn, 1);
                        }
                    } elseif (isset($param['handle_type']) && $param['handle_type']!='single_syn') {
                        // 存在同步类型 且不是单独同步  后续调用
                        $param_syn = [
                            'project_uid' => $project_uid,
                            'is_syn_all_building' => 1,
                            'is_syn_work' => 1
                        ];
                        D('Face_door_d1_service')->face_d1_door_syn($device_id, [], $param['handle_type'],$param_syn, 1);
                    }
                }
                return 0;
            case 'd1_add_work': // 同步物业工作人员
                $device_id = $param['device_id'];
                $village_id = $param['village_id'];
                $project_uid = $param['project_uid'] ? $param['project_uid'] : '';
                $building_uid = $param['building_uid'] ? $param['building_uid'] : '';
                $room_uid = $param['room_uid'] ? $param['room_uid'] : '';
                if ($village_id) {
                    $msg = D('Face_door_d1_service')->d1_add_work($village_id, $project_uid, $building_uid, $room_uid, 1);
                    if (isset($msg['errcode']) && $msg['errcode']>0) {
                        fdump('D1计划任务错误[d1_add_work]>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                        fdump($param,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                        fdump($msg,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                    }
                }
                return 0;
            case 'd1_add_gate': // 同步设备
                $device_id = $param['device_id'];
                $village_id = $param['village_id'];
                $project_uid = $param['project_uid'] ? $param['project_uid'] : '';
                $device_info = $param['device_info'] ? $param['device_info'] : [];
                if ($device_id) {
                    $msg = D('Face_door_d1_service')->d1_add_gate($device_id, $project_uid, [], $device_info);
                    if (isset($msg['errcode']) && $msg['errcode']>0) {
                        fdump('D1计划任务错误[d1_add_gate]>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                        fdump($param,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                        fdump($msg,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                    } elseif (isset($param['handle_type']) && $param['handle_type']!='single_syn') {
                        // 存在同步类型 且不是单独同步  后续调用
                        $param_syn = [
                            'project_uid' => $project_uid,
                            'is_syn_all_building' => 1,
                            'is_syn_work' => 1,
                            'is_syn_device' => 1
                        ];
                        D('Face_door_d1_service')->face_d1_door_syn($device_id, [], $param['handle_type'],$param_syn, 1);
                    }
                }
                return 0;
            case 'd1_building_all_room': // 同步一个楼栋下所有房屋
                $single_id = $param['single_id'];
                $device_id = $param['device_id'];
                $village_id = $param['village_id'];
                $project_uid = $param['project_uid'] ? $param['project_uid'] : '';
                $building_uid = $param['building_uid'] ? $param['building_uid'] : '';
                if ($single_id) {
                    $msg = D('Face_door_d1_service')->d1_building_all_room($single_id, $village_id, $project_uid,$building_uid, $param['handle_type'], $device_id, 1);
                    if (isset($msg['errcode']) && $msg['errcode']>0) {
                        fdump('D1计划任务错误[d1_building_all_room]>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                        fdump($param,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                        fdump($msg,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                    }
                }
                return 0;
            case 'd1_add_room': // 同步一个楼栋下所有房屋
                $device_id = $param['device_id'];
                $vacancy_id = $param['vacancy_id'];
                $village_id = $param['village_id'];
                $project_uid = $param['project_uid'] ? $param['project_uid'] : '';
                $building_uid = $param['building_uid'] ? $param['building_uid'] : '';
                $vacancy_info = $param['vacancy_info'] ? $param['vacancy_info'] : [];
                if ($vacancy_id) {
                    $msg = D('Face_door_d1_service')->d1_add_room($vacancy_id,$project_uid, $building_uid, $vacancy_info);
                    if (isset($msg['errcode']) && $msg['errcode']>0) {
                        fdump('D1计划任务错误[d1_add_room]>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                        fdump($param,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                        fdump($msg,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                    } else {
                        // 添加同步房屋下人员计划任务
                        $params = array();
                        $room_uid = $msg;
                        $params['type'] = 'd1_room_all_user';
                        $params['vacancy_id'] = $vacancy_id;
                        $params['handle_type'] = $param['handle_type'];
                        $params['project_uid'] = $project_uid;
                        $params['building_uid'] = $building_uid;
                        $params['room_uid'] = $room_uid;
                        $params['device_id'] = $device_id;
                        $params['village_id'] = $village_id;
                        $params['vacancy_info'] = $vacancy_info;
                        if ($project_uid) {
                            $params['project_uid'] = $project_uid;
                        }
                        $params['building_uid'] = $msg;
                        $unique_id = 'd1_room_all_user';
                        if (isset($params['village_id'])) {
                            $unique_id .='_'.$params['village_id'];
                        }
                        if (isset($params['vacancy_id'])) {
                            $unique_id .='_'.$params['vacancy_id'];
                        }
                        if (isset($params['device_id'])) {
                            $unique_id .='_'.$params['device_id'];
                        }
                        $params['unique_id'] = $unique_id;
                        $this->add_plan($params, 'sub_house_face', -5);
                    }
                }
                return 0;
            case 'd1_room_all_user': // 同步一个房屋下所有人员
                $device_id = $param['device_id'];
                $vacancy_id = $param['vacancy_id'];
                $village_id = $param['village_id'];
                $project_uid = $param['project_uid'] ? $param['project_uid'] : '';
                $building_uid = $param['building_uid'] ? $param['building_uid'] : '';
                $room_uid = $param['room_uid'] ? $param['room_uid'] : '';
                $vacancy_info = $param['vacancy_info'] ? $param['vacancy_info'] : [];
                if ($vacancy_id) {
                    $msg = D('Face_door_d1_service')->d1_room_all_user($vacancy_id, $project_uid,$building_uid, $room_uid, $vacancy_info, 1);
                    if (isset($msg['errcode']) && $msg['errcode']>0) {
                        fdump('D1计划任务错误[d1_room_all_user]>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                        fdump($param,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                        fdump($msg,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                    }
                }
                return 0;
            case 'd1_add_user': // 同步一个房屋下所有人员
                $device_id = $param['device_id'];
                $bind_id = $param['bind_id'];
                $village_id = $param['village_id'];
                $project_uid = $param['project_uid'] ? $param['project_uid'] : '';
                $building_uid = $param['building_uid'] ? $param['building_uid'] : '';
                $room_uid = $param['room_uid'] ? $param['room_uid'] : '';
                $bind_info = $param['bind_info'] ? $param['bind_info'] : [];
                $otherData = [
                    'log_id'=>$param['log_id']??0
                ];
                if ($bind_id) {
                    $msg = D('Face_door_d1_service')->d1_add_user($bind_id,$project_uid, $building_uid,$room_uid, $bind_info,0,$otherData);
                    if (isset($msg['errcode']) && $msg['errcode']>0) {
                        fdump('D1计划任务错误[d1_add_user]>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                        fdump($param,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                        fdump($msg,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                    }
                }
                return 0;
            case 'd1_open_door_list': // 获取开门记录
                $device_id = $param['device_id'];
                $project_uid = $param['project_uid'] ? $param['project_uid'] : '';
                $device_info = $param['device_info'] ? $param['device_info'] : [];
                if ($device_id) {
                    $msg = D('Face_door_d1_service')->d1_open_door_list($device_id, $device_info, $project_uid);
                    if (isset($msg['errcode']) && $msg['errcode']>0) {
                        fdump('D1计划任务错误[d1_open_door_list]>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                        fdump($param,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                        fdump($msg,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                    }
                }
                return 0;
            case 'd1_sync_all_data': // 同步数据
                $msg = D('Face_door_d1_service')->d1_sync_all_data();
                if (isset($msg['errcode']) && $msg['errcode']>0) {
                    fdump('D1计划任务错误[d1_sync_all_data]>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                    fdump($param,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                    fdump($msg,'api/log/'.date('Ymd').'/D1/d1_plant_err_log',true);
                }
                return 0;
        }

    }

    public function sync_a3_face($device_id,$pigcms_id,$log_id=0) {
        if (empty($pigcms_id) || empty($device_id)) {
            fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/sync_a3_face_err',true);
            fdump($device_id,'api/log/'.date('Ymd').'/sync_a3_face_err',true);
            fdump($pigcms_id,'api/log/'.date('Ymd').'/sync_a3_face_err',true);
            fdump('<<<<<<<<<<<<<<<<-line-'.__LINE__,'api/log/'.date('Ymd').'/sync_a3_face_err',true);
            return true;
        }
        // 同步人脸
        fdump('line_______'.__LINE__,'api/log/'.date('Ymd').'/face_device_check_log',true);
        $d_house_village_face_door = D('House_village_face_door');
        try {
            if(!$log_id){
                $deviceInfo = M('House_face_device')->where(array('device_id' => $device_id))->find();
                if ($deviceInfo) {
                    $syncLogParams = [
                        'pigcms_id' => $pigcms_id, 'face_id' => 0, 'device_id' => $device_id, 'device_sn' => $deviceInfo['device_sn'],
                        'log_time' => time(), 'status' => 0, 'msg' => '', 'device_info' => $deviceInfo
                    ];
                    $log_id = $this->handleHouseFaceSyncLog($syncLogParams);
//                    $log_id = M('House_face_sync_log')->add([
//                        'pigcms_id'=>$pigcms_id,
//                        'face_id'=>0,
//                        'device_id'=>$device_id,
//                        'device_sn'=>$deviceInfo['device_sn'],
//                        'log_time'=>time(),
//                        'status'=>0,
//                        'device_info'=>json_encode($deviceInfo),
//                    ]);
                }
            }
            // 如果设备是A3 前往同步人员信息及人脸信息
            $info = $d_house_village_face_door->add_a3_person_info($device_id, $pigcms_id,'pigcms_id',false,$log_id);
            
            if(isset($info['error']) && $info['error']==1 && $info['msg']){
                if($log_id){
                    M('House_face_sync_log')->where(['log_id'=>$log_id,'status'=>['in',[0,1]]])->save([
                        'status'=>2,
                        'msg'=>$info['msg'],
                        'last_time'=>time()
                    ]);
                } else {
                    $syncLogParams = [
                        'pigcms_id' => $pigcms_id, 'face_id' => 0, 'device_id' => $device_id, 'device_sn' => $deviceInfo['device_sn'],
                        'log_time' => time(), 'status' => 2, 'msg' => $info['msg'], 'device_info' => $deviceInfo
                    ];
                    $log_id = $this->handleHouseFaceSyncLog($syncLogParams);
                }
            }else{
                if($log_id){
                    M('House_face_sync_log')->where(['log_id'=>$log_id,'status'=>0])->save([
                        'status'=>1,
                        'msg'=>'',
                        'last_time'=>time()
                    ]);
                } else {
                    $syncLogParams = [
                        'pigcms_id' => $pigcms_id, 'face_id' => 0, 'device_id' => $device_id, 'device_sn' => $deviceInfo['device_sn'],
                        'log_time' => time(), 'status' => 1, 'msg' => '同步成功', 'device_info' => $deviceInfo
                    ];
                    $log_id = $this->handleHouseFaceSyncLog($syncLogParams);
                }
            }
        }catch (Exception $e) {
            fdump_api(['error' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'line' => __LINE__], 'sync_a3_face_device_door_err', 1);
            if($log_id){
                M('House_face_sync_log')->where(['log_id'=>$log_id])->save([
                    'status'=>2,
                    'msg'=>$e->getMessage(),
                    'last_time'=>time()
                ]);
            } else {
                $syncLogParams = [
                    'pigcms_id' => $pigcms_id, 'face_id' => 0, 'device_id' => $device_id, 'device_sn' => $deviceInfo['device_sn'],
                    'log_time' => time(), 'status' => 2, 'msg' => $e->getMessage(), 'device_info' => $deviceInfo
                ];
                $log_id = $this->handleHouseFaceSyncLog($syncLogParams);
            }
        }
        return true;
    }


    public function get_bind_user_device_img($pigcms_id) {
        $d_house_face_img = M('House_face_img');
        $left_join = 'as hfi LEFT JOIN '.C('DB_PREFIX').'house_village_user_bind as hvu ON hfi.uid=hvu.uid';
        $where = [];
        $where['hvu.pigcms_id'] = $pigcms_id;
        $where['hfi.status'] = ['in',[0,3]];
        $where['hfi.uid'] = ['gt',0];
        $where['hfi.img_url'] = array('neq', '');
        $img_url_arr = $d_house_face_img->join($left_join)->field('hfi.*')->where($where)->order('hfi.id ASC')->limit(5)->select();
        $msg = [];
        $is_a1 = false;
        if ($img_url_arr) {
            import('@.ORG.face.word_encryption');
            $word_encryption = new word_encryption();
            foreach ($img_url_arr as &$val) {
                if ($val['face_img_status']) {
                    $face_img_status = $val['face_img_status'];
                }
                if (3==$val['status']) {
                    // 加密的图片解密
                    $val['img_url'] = $word_encryption->text_decrypt($val['img_url']);
                }
                $val['img_url'] = replace_file_domain($val['img_url']);
            }
        }
        $msg['img_info'] = $img_url_arr;
        $msg['face_img_status'] = $face_img_status;
        return $msg;
    }

    /**
     * Notes: 同步删除设备人员
     * @param $pigcms_id
     * @param array $bind_info
     * @author: wanzy
     * @date_time: 2020/9/8 10:27
     */
    public function sync_delete_face_per($pigcms_id, $bind_info = []) {
        $db_house_face_device = M('House_face_device');
        $db_face_door_a7_service = D('Face_door_a7_service');
        $where = [];
        $where['is_del'] = 0;
        $is_a2 = false;
        $is_a5 = false;

        if ($bind_info && 4==$bind_info['type']) {
            $where['village_id'] = $bind_info['village_id'];
            $device = $db_house_face_device->where($where)->select();
            if ($device) {
                foreach ($device as $val) {
                    if ($val['device_type']==5) {
                        $is_a5 = true;
                    } elseif ($val['device_type']==2) {
                        $is_a2 = true;
                    } elseif ($val['device_type']==7) {
                        try {
                            $db_face_door_a7_service->sync_delete_face_per($val['device_id'], $pigcms_id, $bind_info['village_id'], $val);
                        }catch (Exception $e) {
                            fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/a7/a7_sync_delete_face_per_err',true);
                            fdump($val,'api/log/'.date('Ymd').'/a7/a7_sync_delete_face_per_err',true);
                            fdump($e->getMessage(),'api/log/'.date('Ymd').'/a7/a7_sync_delete_face_per_err',true);
                        }
                    }
                }
            }
        }
        if ($is_a2) {
            // a2 删除  同步进行删除
            D('House_village_face_door')->delete_user($pigcms_id);
        }
        if ($is_a5) {
            // a5 a6
            D('Face_door_a5_service')->removeMember($pigcms_id);
            D('Face_door_a5_service')->deleteMember($pigcms_id);
        }

    }

    public function face_img_to_new_file($base_img, $id, $new_file_name='') {
        $rand_num = date('Ymd');// 换成日期存储
        $up_dir = './upload/face_log/'.$rand_num.'/';
        if (!is_dir($up_dir)) {
            mkdir($up_dir, 0777, true);
        }
        $file_info =  pathinfo($base_img);
        if ($base_img) {
            if (in_array($file_info['extension'],['jpg', 'png'])) {
                $type = $file_info['extension'];
            } else {
                $type = 'jpg';
            }
            if ($new_file_name) {
                $new_file = $up_dir.$new_file_name.'.'.$type;
            } elseif ($file_info['basename']) {
                $new_file = $up_dir.$file_info['basename'];
            } else {
                $new_file = $up_dir.date('YmdHis_').uniqid().'.'.$type;
            }
            # 远程文件处理
            $ch = curl_init();
            curl_setopt($ch,CURLOPT_URL,$base_img);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,5);  # 过期时间
            //当请求https的数据时，会要求证书，加上下面这两个参数，规避ssl的证书检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            $img = curl_exec($ch);
            curl_close($ch);
            $fp2 = @fopen($new_file ,'a');
            fwrite($fp2,$img);
            fclose($fp2);
            unset($img,$url);
            $img_path = trim($new_file,'.');
            //判断是否需要上传至云存储
            $file_handle = new file_handle();
            $file_handle_url = trim($new_file,'./');
            $file_handle->upload($file_handle_url);
            return $img_path;
        } else {
            return false;
        }
    }


    /**
     * Notes: 处理线上图片到本地存储
     * @param $base_img
     * @param $id
     * @return bool|string
     * @author: wanzy
     * @date_time: 2020/9/11 11:16
     */
    public function face_to_img($base_img, $id, $is_filename=false) {
//        $img_mer_id = sprintf("%09d", $id);
//        $rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
        $file_exists_msg = $this->file_exists($base_img);
        if (!$file_exists_msg) {
            return $base_img;
        }
        $rand_num = date('Ymd');// 换成日期存储
        $up_dir = './upload/face_log/'.$rand_num.'/';
        if (!is_dir($up_dir)) {
            mkdir($up_dir, 0777, true);
        }
        $file_info =  pathinfo($base_img);
        if ($base_img) {
            if ($is_filename) {
                $type = 'jpg';
                $new_file = $up_dir.$file_info['filename'].'.'.$type;
            } elseif ($file_info['basename']) {
                $new_file = $up_dir.$file_info['basename'];
            } elseif ($file_info['extension']) {
                $new_file = $up_dir.date('YmdHis_').uniqid().'.'.$file_info['extension'];
            } else {
                $type = 'jpg';
                $new_file = $up_dir.date('YmdHis_').uniqid().'.'.$type;
            }
            # 远程文件处理
            $ch = curl_init();
            curl_setopt($ch,CURLOPT_URL,$base_img);
            curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
            curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,5);  # 过期时间
            //当请求https的数据时，会要求证书，加上下面这两个参数，规避ssl的证书检查
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            $img = curl_exec($ch);
            curl_close($ch);
            $fp2 = @fopen($new_file ,'a');
            fwrite($fp2,$img);
            fclose($fp2);
            unset($img,$base_img);
            $img_path = trim($new_file,'.');
            //判断是否需要上传至云存储
            $file_handle = new file_handle();
            $file_handle_url = trim($new_file,'./');
            $file_handle->upload($file_handle_url);
            return $img_path;
        } else {
            return false;
        }
    }

    /**
     * Notes: 检验下图片是否实际存在
     * @param $url
     * @return bool
     * @author: wanzy
     * @date_time: 2021/4/16 9:24
     */
    public function file_exists($url)
    {
        if(file_get_contents($url,0,null,0,1)){
            return true;
        } else {
            return false;
        }
    }
    
    public function repair_bind_user_number($village_id=0) {
        $db_house_village_user_bind = M('House_village_user_bind');
        $where = [];
        $where['user_number'] = ['neq',''];
        $where['type'] = ['lt',4];
        $where['_string'] = "length(user_number) >18";
        if ($village_id) {
            $where['village_id'] = $village_id;
        }
        $list = $db_house_village_user_bind->group('village_id,user_number')->field('count(pigcms_id) as num,pigcms_id,village_id,room_number,user_number,vacancy_id,type')->where($where)->order('pigcms_id DESC')->select();
        if ($list) {
            $room_data = [];
            foreach ($list as $val) {
                if ($val['num']>=2 && $val['vacancy_id']) {
                    $room_data[] = $val;
                }
            }
            foreach ($room_data as $vals) {
                $pigcms_id_arr = $db_house_village_user_bind->where(['village_id' => $vals['village_id'], 'user_number' => $vals['user_number']])->order('pigcms_id ASC')->getField('pigcms_id',true);
                $where1 = [];
                $where1['vacancy_id']=$vals['vacancy_id'];
                $where1['pigcms_id']=['not in', $pigcms_id_arr];
                $room_number_max_info = $db_house_village_user_bind->where($where1)->field('MAX(room_number+0) as room_number_max')->find();
                if ($room_number_max_info) {
                    $room_number_max = intval($room_number_max_info['room_number_max']);
                } else {
                    $room_number_max = 0;
                }
                if (intval($vals['room_number'])>$room_number_max) {
                    $room_number_max = $vals['room_number'];
                }
                foreach ($pigcms_id_arr as $k=>$pigcms_id) {
                    if (0==$k) {
                        continue;
                    }
                    $room_number_max = intval($room_number_max) + 1;
                    if (strlen($room_number_max)<2) {
                        $room_number_max = str_pad($room_number_max,2,"0",STR_PAD_LEFT);
                    }
                    if (strlen($room_number_max)>2) {
                        $replacement = $room_number_max.'100000000';
                    } else {
                        $replacement = $room_number_max.'1000000000';
                    }
                    $user_number = substr_replace($vals['user_number'],$replacement,-12,12);
//                    $user_number = str_replace($vals['room_number'].'1000000000', $room_number_max.'1000000000', $vals['user_number']);
                    $set = [
                        'room_number' => $room_number_max,
                        'user_number' => $user_number,
                    ];
                    $db_house_village_user_bind->where(['pigcms_id'=>$pigcms_id])->save($set);
                }
                break;
            }
        }
        $where = [];
        $where['user_number'] = ['neq',''];
        $where['type'] = 4;
        $where['_string'] = "length(user_number) >18";
        if ($village_id) {
            $where['village_id'] = $village_id;
        }
        $list1 = $db_house_village_user_bind->group('village_id,user_number')->field('count(pigcms_id) as num,pigcms_id,village_id,room_number,user_number,vacancy_id,type')->where($where)->order('add_time DESC')->select();

        if ($list1) {
            $work_data = [];
            foreach ($list1 as $val) {
                if ($val['num']>=2) {
                    $work_data[] = $val;
                }
            }
            foreach ($work_data as $vals1) {
                $pigcms_id1_arr = $db_house_village_user_bind->where(['village_id' => $vals1['village_id'], 'user_number' => $vals1['user_number']])->getField('pigcms_id',true);
                $where1 = [];
                $where1['type']=4;
                $where1['village_id']=$vals1['village_id'];
                $where1['pigcms_id']=['not in', $pigcms_id1_arr];
                $room_number_max_info = $db_house_village_user_bind->where($where1)->field('MAX(room_number+0) as room_number_max')->find();
                if ($room_number_max_info) {
                    $room_number_max = intval($room_number_max_info['room_number_max']);
                } else {
                    $room_number_max = 0;
                }
                if (intval($vals1['room_number'])>$room_number_max) {
                    $room_number_max = $vals1['room_number'];
                }

                foreach ($pigcms_id1_arr as $k=>$pigcms_id) {
                    if (0==$k) {
                        continue;
                    }
                    $room_number_max = intval($room_number_max) + 1;
                    if (strlen($room_number_max)<4) {
                        $room_number_max = str_pad($room_number_max,4,"0",STR_PAD_LEFT);
                    }
                    $user_number = substr_replace($vals1['user_number'],$room_number_max.'2000000000',-14,14);
//                    $user_number = str_replace($vals1['room_number'].'2000000000', $room_number_max.'2000000000', $vals1['user_number']);
                    $set = [
                        'room_number' => $room_number_max,
                        'user_number' => $user_number,
                    ];
                    $db_house_village_user_bind->where(['pigcms_id'=>$pigcms_id])->save($set);
                }
            }
        }
        return true;
    }

    /**
     * 处理人脸下发人脸设备日志.
     */
    public function handleHouseFaceSyncLog($params) {
        $dbHouseFaceSyncLog = M('House_face_sync_log');
        $pigcms_id = isset($params['pigcms_id']) ? intval($params['pigcms_id']) : 0;
        $device_id = isset($params['device_id']) ? intval($params['device_id']) : 0;
        $device_sn = isset($params['device_sn']) ? trim($params['device_sn']) : '';
        $nowTime = isset($params['nowTime']) && $params['nowTime'] ? intval($params['nowTime']) : time();
        $where = [];
        $where['pigcms_id'] = $pigcms_id;
        if ($device_id) {
            $where['device_id'] = $device_id;
        } else {
            $where['device_sn'] = $device_sn;
        }
        $log_id = $dbHouseFaceSyncLog->where($where)->order('log_id desc')->getField('log_id');
        if ($log_id > 0) {
            $update = ['pigcms_id' => $pigcms_id, 'last_time' => $nowTime];
            if (isset($params['device_id'])) {
                $update['device_id'] = intval($params['device_id']);
            }
            if (isset($params['device_sn'])) {
                $update['device_sn'] = trim($params['device_sn']);
            }
            if (isset($params['face_id'])) {
                $update['face_id'] = intval($params['face_id']);
            }
            if (isset($params['log_time'])) {
                $update['log_time'] = intval($params['log_time']);
            }
            if (isset($params['status'])) {
                $update['status'] = intval($params['status']);
            }
            if (isset($params['msg'])) {
                $update['msg'] = trim($params['msg']);
            }
            if (isset($params['device_info'])) {
                $device_info = $params['device_info'] ? $params['device_info'] : [];
                $update['device_info'] = json_encode($device_info, JSON_UNESCAPED_UNICODE);
            }
            $dbHouseFaceSyncLog->where(['log_id' => $log_id])->data($update)->save();
        } else {
            $face_id = isset($params['face_id']) ? intval($params['face_id']) : 0;
            $log_time = isset($params['log_time']) ? intval($params['log_time']) : 0;
            $status = isset($params['status']) ? intval($params['status']) : 0;
            $msg = isset($params['msg']) ? trim($params['msg']) : '';
            $device_info = isset($params['device_info']) && $params['device_info'] ? $params['device_info'] : [];
            $data = [
                'pigcms_id' => $pigcms_id, 'face_id' => $face_id, 'device_id' => $device_id,
                'device_sn' => $device_sn, 'log_time' => $log_time, 'status' => $status,
                'msg' => $msg, 'last_time' => $nowTime, 'device_info' => json_encode($device_info, JSON_UNESCAPED_UNICODE),
            ];
            $log_id = $dbHouseFaceSyncLog->data($data)->add();
        }
        return $log_id;
    }
}