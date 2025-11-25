<?php
/**
 * 人脸请求或者回调接口（暂时为A7）
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2020/9/4 11:15
 */
class Face_request_recordAction extends BaseAction {

    /**
     * d3心跳
     * @author liukezhu
     * @date 2021/4/15
     */
    public function d3_face_request(){
        $i = 0;
        while(true){
            if($i > 20){
                // 暂无操作
                $data['version'] = D('Face_d3_request_record')->version;
                $data['cmd'] = '';
                echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
            }
            $this->d3_face_request_body();
            $i++;
            sleep(2);
        }
    }

    /**
     *
     * @author liukezhu
     * @date 2021/4/15
     */
    public function d3_face_request_body() {
        $arrData = $_POST;
        // 设备序列号
        $db_house_face_device = M('House_face_device');
        $where = [
            'is_del' => 0,
            'device_sn' => $arrData['device_sn'],
        ];
        $face_device = $db_house_face_device->where($where)->find();
        $device_id = $face_device['device_id'];


        if ($face_device) {
            $tmpDataTimeData = [
                'login_time' => time(),
                'notify_time' => time(),
                'device_status' => 1
            ];
            $db_house_face_device->where($where)->data($tmpDataTimeData)->save();
        }
        // 超过30分钟未进行交互的设备 默认离线
        $this->face_device_check(23,$face_device);

        if ($arrData && $face_device) {
            $face_d3_request_record = D('Face_d3_request_record');

            if('face' ==$arrData['cmd']){
                //抓拍
                fdump($arrData,'api/log/'.date('Ymd').'/d3/d3_face',true);
                $face_d3_request_record->d3_face_notify($face_device, $arrData);
            }
            elseif ('add person jpg'==$arrData['cmd']){
                //添加人员
                fdump($arrData,'api/log/'.date('Ymd').'/d3/d3_device_create_face',true);
                $face_d3_request_record->d3_device_create_face($device_id, $arrData);
            }
            elseif ('update person jpg'==$arrData['cmd']){
                //编辑人员
                fdump($arrData,'api/log/'.date('Ymd').'/d3/d3_device_update_face',true);
                $face_d3_request_record->d3_device_update_face($face_device, $arrData);
            }
            elseif ('reboot'==$arrData['cmd']){
                //重启
                fdump($arrData,'api/log/'.date('Ymd').'/d3/d3_get_dev_reboot',true);
                $face_d3_request_record->d3_device_reboot_cam($device_id, $arrData);
            }
            elseif ('delete person(s)'==$arrData['cmd']){
                //删除
                fdump($arrData,'api/log/'.date('Ymd').'/d3/d3_device_delete_face',true);
                $face_d3_request_record->d3_device_delete_face($device_id, $arrData);
            }
            elseif ('update app params'==$arrData['cmd']){
                //设置是否开启活体检测
                fdump($arrData,'api/log/'.date('Ymd').'/d3/d3_ht_params',true);
                $face_d3_request_record->d3_device_set_attr($device_id, $arrData);
            }
            elseif ('request app params'==$arrData['cmd']){
                //设备参数获取
                fdump($arrData,'api/log/'.date('Ymd').'/d3/d3_get_params',true);
                $face_d3_request_record->d3_device_get($device_id, $arrData);
            }
            elseif ('gpio control'==$arrData['cmd']){
                //远程开门
                fdump($arrData,'api/log/'.date('Ymd').'/d3/d3_gpio',true);
                $face_d3_request_record->d3_device_control($device_id, $arrData);
            }
            else{
                $face_d3_request_record->d3_get_request_record($device_id);
            }
        }

    }

    // 心跳请求地址
    public function a7_face_request() {
		
		$i = 0;
		while(true){
			if($i > 20){
				// 暂无操作
				$data['version'] = D('Face_a7_request_record')->version;
				$data['cmd'] = '';
				echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
			}
			$this->a7_face_request_body();
			$i++;
			sleep(1);
		}
    }

    //todo 超过30分钟未进行交互的设备 默认离线
    public function face_device_check($device_type,$face_device=[]){
        $where_other = [
            'login_time' => ['elt', time()-1800],
            'device_status' => 1,
            'device_type' => $device_type
        ];
        if (!empty($face_device)) {
            $where_other['device_id'] = ['neq', $face_device['device_id']];
            //todo 故障解除 A7/D3
            D('House_maintenan')->normalLog(1,$face_device['device_id']);
        }
        $db_house_face_device = M('House_face_device');
        $list=$db_house_face_device->where($where_other)->getField('device_id',true);
        if(empty($list)){
            return false;
        }
        $set_device = [
            'device_status' => 2
        ];
        $db_house_face_device->where($where_other)->save($set_device);
        foreach ($list as $v){
            //todo 故障通知 A7/D3
            D('House_maintenan')->faultLog(1,$v,'设备不在线');
        }
        return true;
    }

    // 心跳请求地址执行
    public function a7_face_request_body() {
        $arrData = $_POST;
        if ($arrData['last_result']) {
            $last_result = htmlspecialchars_decode($arrData['last_result']);
            $last_result = json_decode($last_result,true);
            $arrData['last_result'] = $last_result;
        }
        // 设备序列号
        $db_house_face_device = M('House_face_device');
        $db_face_a7_request_record = D('Face_a7_request_record');
        $serialno = $arrData['serialno'];
        $device_type = 7;
        $where = [
            'device_type' => $device_type,
            'device_sn' => $serialno,
            'is_del' => 0,
        ];
        $face_device = S($serialno.'/device_a'.$device_type);
        if (! $face_device) {
            $face_device = $db_house_face_device->where($where)->field('device_id,device_sn,is_del')->find();
        }
        if ($face_device) {
            $tmpDataTimeData = [
                'login_time' => time(),
                'notify_time' => time(),
                'device_status' => 1
            ];
            $db_house_face_device->where($where)->data($tmpDataTimeData)->save();
            $options = [];
            $options['expire'] = 3600;
            S($serialno.'/device_a'.$device_type, $face_device, $options);
        }
        $device_id = $face_device['device_id'];

        // 超过30分钟未进行交互的设备 默认离线
        $this->face_device_check(7,$face_device);

        $ipaddr = $arrData['ipaddr'] ? $arrData['ipaddr'] : '';
        if ($last_result && $face_device) {
            if ($last_result=='1') {
                // 不是执行结果回馈  那么就是获取需要执行的操作
                $this->get_request_record($device_id, $ipaddr);
            } elseif ('reboot_cam'==$last_result['cmd']) {
                // 重启反馈
                fdump(['重启反馈-'.__LINE__,$device_id,$last_result],'api/log/'.date('Ymd').'/a7/a7_get_dev_network',true);
                $this->device_reboot_cam($device_id, $last_result);
            } elseif ('get_dev_network'==$last_result['cmd']) {
                // 获取设备是否连接外网-反馈
                fdump(['获取设备是否连接外网-反馈-'.__LINE__,$device_id,$last_result],'api/log/'.date('Ymd').'/a7/a7_device_get_dev_network',true);
                $this->device_get_dev_network($device_id, $last_result);
            } elseif ('create_face'==$last_result['cmd']) {
                // 添加人脸反馈
                fdump(['添加人脸反馈-'.__LINE__,$device_id,$last_result],'api/log/'.date('Ymd').'/a7/a7_device_create_face',true);
                $this->device_create_face($device_id, $last_result);
            } elseif ('update_face'==$last_result['cmd']) {
                // 添加人脸反馈
                fdump(['添加人脸反馈-'.__LINE__,$device_id,$last_result],'api/log/'.date('Ymd').'/a7/a7_device_update_face',true);
                $this->device_update_face($device_id, $last_result);
            } elseif ('set_attr'==$last_result['cmd']) {
                // 设备活体检测设置反馈
                fdump(['设备活体检测设置反馈-'.__LINE__,$device_id,$last_result],'api/log/'.date('Ymd').'/a7/a7_device_set_attr',true);
                $this->device_set_attr($device_id, $last_result);
            } elseif ('set_match_score'==$last_result['cmd']) {
                // 设置是否开启活体检测
                fdump(['设置是否开启活体检测-'.__LINE__,$device_id,$last_result],'api/log/'.date('Ymd').'/a7/a7_device_set_match_score',true);
                $this->device_set_match_score($device_id, $last_result);
            }  elseif ('set_spoofing'==$last_result['cmd']) {
                // 设置是否开启活体检测
                fdump(['设置是否开启活体检测-'.__LINE__,$device_id,$last_result],'api/log/'.date('Ymd').'/a7/a7_device_set_spoofing',true);
                $this->device_set_spoofing($device_id, $last_result);
            } elseif ('delete_face'==$last_result['cmd']) {
                // 删除人脸反馈
                fdump(['删除人脸反馈-'.__LINE__,$device_id,$last_result],'api/log/'.date('Ymd').'/a7/a7_device_delete_face',true);
                $this->device_delete_face($device_id, $last_result);
            } elseif ('gpio control'==$last_result['cmd']) {
                // 远程开门反馈
                fdump(['远程开门反馈-'.__LINE__,$device_id,$last_result],'api/log/'.date('Ymd').'/a7/a7_device_gpiocontrol',true);
                $this->device_gpiocontrol($device_id, $last_result);
            } elseif ('send_rs485'==$last_result['cmd']) {
                // 呼叫电梯反馈
                fdump(['呼叫电梯反馈-'.__LINE__,$device_id,$last_result],'api/log/'.date('Ymd').'/a7/a7_device_sendRs485',true);
                $this->device_sendRs485($device_id, $last_result);
            } else {
                // 不是执行结果回馈  那么就是获取需要执行的操作
                $this->get_request_record($device_id, $ipaddr);
            }
            // 记录下当前设备ip
        }
    }

    /**
     * Notes: 获取当前需要执行的操作
     * @param $device_id
     * @author: wanzy
     * @date_time: 2020/9/8 13:42
     */
    private function get_request_record($device_id, $ipaddr='') {
        $db_house_face_device = M('House_face_device');
        $db_face_a7_request_record = M('Face_a7_request_record');
        $where_record = [
            'device_id' => $device_id,
            'status' => 0,
        ];
        $max_down_times = 7;
        $where_is_implement = $where_record;
        $where_is_implement['is_implement'] = 1;
        $where_is_implement['down_times'] = ['elt', $max_down_times];
        $order = 'sort DESC, down_times ASC, add_time ASC, id ASC';
        $face_a7_request_record_info = $db_face_a7_request_record->order($order)->where($where_is_implement)->find();
        if (!$face_a7_request_record_info) {
            $face_a7_request_record_info = $db_face_a7_request_record->order($order)->where($where_record)->find();
        }
        if ($face_a7_request_record_info['cmd_name']=='gpio control') {
            $max_down_times = 3;// 开门避免没开成功 重复3次
        }
        if ($face_a7_request_record_info['cmd_name']=='delete_face') {
            $max_down_times = 1;// 删除避免返回删除 只执行1次
        }
        if ($face_a7_request_record_info['cmd_name']=='reboot_cam') {
            $max_down_times = 2;// 重启 避免设备未响应 执行2次
        }
        if (intval($face_a7_request_record_info['down_times'])>= $max_down_times) {
            // 暂定 下行次数大于等于50次的 直接默认同步失败
            $data_recod = [
                'status' => 2,
                'is_implement' => 2,
                'remark' => "已经执行了{$max_down_times}次，默认失败"
            ];
            $db_face_a7_request_record->where(['id' => $face_a7_request_record_info['id']])->data($data_recod)->save();
            $data['version'] = $db_face_a7_request_record->version;
            $data['cmd'] = '';
            //fdump_api($data,'a7/a7_exe_cmd'.date('YmdH'),true);
            echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
        } elseif ($face_a7_request_record_info && $face_a7_request_record_info['cmd_name'] && $face_a7_request_record_info['data']) {
            $face_a7_request_record_info['data'] = unserialize($face_a7_request_record_info['data']);
            if ($ipaddr) {
                // 实时记录下当前执行操作时候的设备ip
                $db_face_a7_request_record->where(['id' => $face_a7_request_record_info['id']])->setField('device_ip', $ipaddr);
                // 实时更新下设备ip
                $db_house_face_device->where(['device_id' => $device_id])->setField('device_ip', $ipaddr);
            }
            // 每次查询添加次数，以防同一个下行卡主导致后续无法执行
            $down_times=$face_a7_request_record_info['down_times']+1;
            $data_recod=array('update_time'=>time(),'down_times'=>$down_times,'is_implement'=>1);
            $db_face_a7_request_record->where(['id' => $face_a7_request_record_info['id']])->data($data_recod)->save();
            $data = $face_a7_request_record_info['data'];
            $data['index'] = intval($face_a7_request_record_info['id']);
            //fdump_api($data,'a7/a7_exe_cmd'.date('YmdH'),true);
            echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
        }
    }

    /**
     * Notes: 重启处理
     * @param $device_id
     * @param $last_result
     * @return bool
     * @author: wanzy
     * @date_time: 2020/9/8 15:36
     */
    private function device_reboot_cam($device_id, $last_result) {
        $db_house_face_device = M('House_face_device');
        $db_face_a7_request_record = D('Face_a7_request_record');
        if (0==$last_result['code']) {
            // 重启成功反馈结果
            if ($last_result['index']>0) {
                $where_record = [
                    'id' => $last_result['index'],
                    'cmd_name' => 'reboot_cam',
                ];
            } else {
                $where_record = [
                    'device_id' => $device_id,
                    'status' => 0,
                    'cmd_name' => 'reboot_cam',
                ];
            }
            $data_recod = [
                'status' => 1,
                'is_implement' => 2,
                'sucess_time' => time()
            ];
            $db_face_a7_request_record->where($where_record)->delete();
            // 重启成功
            $data['login_time'] = time();
            $db_house_face_device->where(array('device_id' => $device_id))->data($data)->save();
        } else {
            // 重启失败反馈结果
            if ($last_result['reply']!='ACK') {
                $remark = '重启失败,code: '.$last_result['code'] . ',reply: '.$last_result['reply'];
            } else {
                $remark = '重启失败';
            }
            $where_record = [
                'device_id' => $device_id,
                'status' => 0,
                'cmd_name' => 'reboot_cam',
            ];
            $data_recod = [
                'status' => 2,
                'is_implement' => 2,
                'remark' => $remark
            ];
            $db_face_a7_request_record->where($where_record)->data($data_recod)->save();
        }
        $data['version'] = $db_face_a7_request_record->version;
        $data['cmd'] = '';
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }

    /**
     * Notes: 获取设备是否连接外网
     * @param $device_id
     * @param $last_result
     * @return bool
     * @author: wanzy
     * @date_time: 2020/9/8 15:36
     */
    private function device_get_dev_network($device_id, $last_result) {
        $db_house_face_device = M('House_face_device');
        $db_face_a7_request_record = D('Face_a7_request_record');
        if (0==$last_result['code']) {
            // 反馈成功结果
            if ($last_result['index']>0) {
                $where_record = [
                    'id' => $last_result['index'],
                    'cmd_name' => 'get_dev_network',
                ];
            } else {
                $where_record = [
                    'device_id' => $device_id,
                    'status' => 0,
                    'cmd_name' => 'get_dev_network',
                ];
            }
            $data_recod = [
                'status' => 1,
                'is_implement' => 2,
                'sucess_time' => time()
            ];
            $db_face_a7_request_record->where($where_record)->delete();
            if (isset($last_result['body']) && $last_result['body']['status']) {
                $data['device_status'] = 1;
                $data['last_time'] = time();
                $data['device_info'] = serialize($last_result);
                $db_house_face_device->where(array('device_id' => $device_id))->data($data)->save();
            } elseif (isset($last_result['body']) && !$last_result['body']['status']) {
                $data['device_status'] = 0;
                $data['last_time'] = time();
                $data['device_info'] = serialize($last_result);
                $db_house_face_device->where(array('device_id' => $device_id))->data($data)->save();
            }
        } else {
            // 反馈失败结果
            if ($last_result['reply']!='ACK') {
                $remark = '获取失败,code: '.$last_result['code'] . ',reply: '.$last_result['reply'];
            } else {
                $remark = '获取失败';
            }
            $where_record = [
                'device_id' => $device_id,
                'status' => 0,
                'cmd_name' => 'get_dev_network',
            ];
            $data_recod = [
                'status' => 2,
                'is_implement' => 2,
                'remark' => $remark
            ];
            $db_face_a7_request_record->where($where_record)->data($data_recod)->save();
        }
        $data['version'] = $db_face_a7_request_record->version;
        $data['cmd'] = '';
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }

    /**
     * Notes: 人脸添加反馈
     * @param $device_id
     * @param $last_result
     * @return bool
     * @author: wanzy
     * @date_time: 2020/9/8 15:36
     */
    private function device_create_face($device_id, $last_result) {
        $db_face_a7_request_record = D('Face_a7_request_record');
        $db_house_village_user_bind = D('House_village_user_bind');
        // 查出当前执行命令
        if ($last_result['index']>0) {
            $where_record = [
                'id' => $last_result['index'],
                'cmd_name' => 'create_face',
            ];
        } elseif ($last_result['pers_id']>0) {
            $where_record = [
                'device_id' => $device_id,
                'user_number' => $last_result['pers_id'],
                'status' => 0,
                'cmd_name' => 'create_face',
            ];
        } else {
            $where_record = [
                'device_id' => $device_id,
                'is_implement' => 1,
                'status' => 0,
                'cmd_name' => 'create_face',
            ];
        }
        $info = $db_face_a7_request_record->where($where_record)->find();
        if (0==$last_result['code'] && $info) {
            // 反馈成功结果
            $data_recod = [
                'status' => 1,
                'is_implement' => 2,
                'sucess_time' => time()
            ];
            if ($last_result['pers_id']) {
                $data_recod['user_number'] = $last_result['pers_id'];
            }
            $db_face_a7_request_record->where($where_record)->data($data_recod)->save();
            // 人脸记录成功
            if ($info['bind_id']) {
                $data_set = [
                    'face_img_status' => 1,
                    'face_img_reason' => '',
                ];
                $db_house_village_user_bind->where(['pigcms_id' => $info['bind_id']])->data($data_set)->save();
                $d_face_user_bind_device = M('Face_user_bind_device');
                $data_face_bind_about = [
                    'bind_type' => 0,
                    'bind_id' => $info['bind_id'],
                    'device_id' => $device_id,
                    'device_type' => 7,
                    'person_id' => $last_result['pers_id'] ? $last_result['pers_id'] : $info['bind_id'],
                    'add_time' => time()
                ];
                $d_face_user_bind_device->data($data_face_bind_about)->add();
            }
            $log_id = isset($info['log_id']) && $info['log_id'] ? intval($info['log_id']) : 0;
            if ($log_id) {
                M('House_face_sync_log')->where(['log_id' => $log_id])->save(['status' => 1, 'msg' => '', 'last_time' => time()]);
            } else {
                $device_sn = isset($info['device_sn']) && $info['device_sn'] ? trim($info['device_sn']) : '';
                $pigcms_id = isset($info['bind_id']) && $info['bind_id'] ? intval($info['bind_id']) : 0;
                $device_id = isset($info['device_id']) && $info['device_id'] ? intval($info['device_id']) : 0;
                $syncLogParams = [
                    'pigcms_id' => $pigcms_id, 'face_id' => 0, 'device_id' => $device_id, 'device_sn' => $device_sn,
                    'log_time' => time(), 'status' => 1, 'msg' => '',
                ];
                $log_id = D('Face_door_service')->handleHouseFaceSyncLog($syncLogParams);
            }
            $db_face_a7_request_record->where($where_record)->delete();
        } else {
            // 反馈失败结果
            if (402==$last_result['code'] && $info) {
                $data_recod = [
                    'status' => 1,
                    'is_implement' => 2,
                    'sucess_time' => time()
                ];
                if ($last_result['pers_id']) {
                    $data_recod['user_number'] = $last_result['pers_id'];
                }
                $d_face_user_bind_device = M('Face_user_bind_device');
                $where_face_bind_about = [
                    'bind_type' => 0,
                    'bind_id' => $info['bind_id'],
                    'device_type' => 7,
                    'device_id' => $device_id,
                ];
                $bind_user = $d_face_user_bind_device->where($where_face_bind_about)->find();
                if (!$bind_user) {
                    $data_face_bind_about = [
                        'bind_type' => 0,
                        'device_id' => $device_id,
                        'bind_id' => $info['bind_id'],
                        'device_type' => 7,
                        'person_id' => $last_result['pers_id'] ? $last_result['pers_id'] : $info['bind_id'],
                        'add_time' => time()
                    ];
                    $d_face_user_bind_device->data($data_face_bind_about)->add();
                } else {
                    $data_save = [
                        'person_id' => $last_result['pers_id'] ? $last_result['pers_id'] : $info['bind_id'],
                        'last_time' => time(),
                        'device_id' => $device_id,
                    ];
                    $d_face_user_bind_device->where($where_face_bind_about)->save($data_save);
                }
                $data_set = [
                    'face_img_status' => 1,
                    'face_img_reason' => '',
                ];
                $db_house_village_user_bind->where(['pigcms_id' => $info['bind_id']])->data($data_set)->save();
                $log_id = isset($info['log_id']) && $info['log_id'] ? intval($info['log_id']) : 0;
                if ($log_id) {
                    M('House_face_sync_log')->where(['log_id' => $log_id])->save(['status' => 1, 'msg' => '', 'last_time' => time()]);
                } else {
                    $device_sn = isset($info['device_sn']) && $info['device_sn'] ? trim($info['device_sn']) : '';
                    $pigcms_id = isset($info['bind_id']) && $info['bind_id'] ? intval($info['bind_id']) : 0;
                    $device_id = isset($info['device_id']) && $info['device_id'] ? intval($info['device_id']) : 0;
                    $syncLogParams = [
                        'pigcms_id' => $pigcms_id, 'face_id' => 0, 'device_id' => $device_id, 'device_sn' => $device_sn,
                        'log_time' => time(), 'status' => 1, 'msg' => '',
                    ];
                    $log_id = D('Face_door_service')->handleHouseFaceSyncLog($syncLogParams);
                }
                $db_face_a7_request_record->where($where_record)->delete();
                $param = array();
                $param['type'] = 'repair_bind_user_number';
                $param['unique_id'] = 'repair_bind_user_number';
                $param['village_id'] = $info['village_id'];
                D('Face_door_service')->add_plan($param, 'sub_house_village_face_device');
            } else {
                $create_face_code = $db_face_a7_request_record->create_face_code;
                if ($last_result['code'] && $create_face_code[$last_result['code']]) {
                    $remark = $create_face_code[$last_result['code']];
                } elseif ($last_result['reply']!='ACK') {
                    $remark = $last_result['reply'];
                } else {
                    $remark = '人脸同步添加失败';
                }
                $data_recod = [
                    'status' => 2,
                    'is_implement' => 2,
                    'remark' => $remark
                ];
                $db_face_a7_request_record->where($where_record)->data($data_recod)->save();
                // 人脸记录失败
                if ($info['bind_id']) {
                    $data_set = [
                        'face_img_status' => 2,
                        'face_img_reason' => $remark,
                    ];
                    $db_house_village_user_bind->where(['pigcms_id' => $info['bind_id']])->data($data_set)->save();
                }
                $log_id = isset($info['log_id']) && $info['log_id'] ? intval($info['log_id']) : 0;
                if ($log_id) {
                    M('House_face_sync_log')->where(['log_id' => $log_id])->save(['status' => 2, 'msg' => $remark, 'last_time' => time()]);
                } else {
                    $device_sn = isset($info['device_sn']) && $info['device_sn'] ? trim($info['device_sn']) : '';
                    $pigcms_id = isset($info['bind_id']) && $info['bind_id'] ? intval($info['bind_id']) : 0;
                    $device_id = isset($info['device_id']) && $info['device_id'] ? intval($info['device_id']) : 0;
                    $syncLogParams = [
                        'pigcms_id' => $pigcms_id, 'face_id' => 0, 'device_id' => $device_id, 'device_sn' => $device_sn,
                        'log_time' => time(), 'status' => 2, 'msg' => $remark,
                    ];
                    $log_id = D('Face_door_service')->handleHouseFaceSyncLog($syncLogParams);
                }
            }
        }
        $data['version'] = $db_face_a7_request_record->version;
        $data['cmd'] = '';
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }

    /**
     * Notes: 人脸编辑反馈
     * @param $device_id
     * @param $last_result
     * @author: wanzy
     * @date_time: 2020/9/9 13:52
     */
    private function device_update_face($device_id, $last_result) {
        $db_face_a7_request_record = D('Face_a7_request_record');
        $db_house_village_user_bind = D('House_village_user_bind');
        // 查出当前执行命令
        if ($last_result['index']>0) {
            $where_record = [
                'id' => $last_result['index'],
                'cmd_name' => 'update_face',
            ];
        } elseif ($last_result['pers_id']>0) {
            $where_record = [
                'device_id' => $device_id,
                'user_number' => $last_result['pers_id'],
                'status' => 0,
                'cmd_name' => 'update_face',
            ];
        } else {
            $where_record = [
                'device_id' => $device_id,
                'is_implement' => 1,
                'status' => 0,
                'cmd_name' => 'update_face',
            ];
        }
        $info = $db_face_a7_request_record->where($where_record)->find();
        if (0==$last_result['code']) {
            // 反馈成功结果
            $data_recod = [
                'status' => 1,
                'is_implement' => 2,
                'sucess_time' => time()
            ];
            if ($last_result['pers_id']) {
                $data_recod['user_number'] = $last_result['pers_id'];
            }
            $log_id = isset($info['log_id']) && $info['log_id'] ? intval($info['log_id']) : 0;
            if ($log_id) {
                M('House_face_sync_log')->where(['log_id' => $log_id])->save(['status' => 1, 'msg' => '', 'last_time' => time()]);
            } else {
                $device_sn = isset($info['device_sn']) && $info['device_sn'] ? trim($info['device_sn']) : '';
                $pigcms_id = isset($info['bind_id']) && $info['bind_id'] ? intval($info['bind_id']) : 0;
                $device_id = isset($info['device_id']) && $info['device_id'] ? intval($info['device_id']) : 0;
                $syncLogParams = [
                    'pigcms_id' => $pigcms_id, 'face_id' => 0, 'device_id' => $device_id, 'device_sn' => $device_sn,
                    'log_time' => time(), 'status' => 1, 'msg' => '',
                ];
                $log_id = D('Face_door_service')->handleHouseFaceSyncLog($syncLogParams);
            }
            $db_face_a7_request_record->where($where_record)->delete();
            // 人脸记录成功
            if ($info['bind_id']) {
                $data_set = [
                    'face_img_status' => 1,
                    'face_img_reason' => '',
                ];
                $db_house_village_user_bind->where(['pigcms_id' => $info['bind_id']])->data($data_set)->save();
                $d_face_user_bind_device = M('Face_user_bind_device');
                $where_face_bind_about = [
                    'bind_type' => 0,
                    'bind_id' => $info['bind_id'],
                    'device_type' => 7,
                    'device_id' => $device_id,
                ];
                $bind_user = $d_face_user_bind_device->where($where_face_bind_about)->find();
                if (!$bind_user) {
                    $data_face_bind_about = [
                        'bind_type' => 0,
                        'bind_id' => $info['bind_id'],
                        'device_type' => 7,
                        'person_id' => $last_result['pers_id'] ? $last_result['pers_id'] : $info['bind_id'],
                        'add_time' => time()
                    ];
                    $d_face_user_bind_device->data($data_face_bind_about)->add();
                } else {
                    $data_save = [
                        'person_id' => $last_result['pers_id'] ? $last_result['pers_id'] : $info['bind_id'],
                        'last_time' => time()
                    ];
                    $d_face_user_bind_device->where($where_face_bind_about)->save($data_save);
                }
            }
        } else {
            // 反馈失败结果
            if (402==$last_result['code'] && $info) {
                $data_recod = [
                    'status' => 1,
                    'is_implement' => 2,
                    'sucess_time' => time()
                ];
                if ($last_result['pers_id']) {
                    $data_recod['user_number'] = $last_result['pers_id'];
                }
                $db_face_a7_request_record->where($where_record)->data($data_recod)->save();
                // 反馈成功结果
                $data_recod = [
                    'status' => 1,
                    'is_implement' => 2,
                    'sucess_time' => time()
                ];
                if ($last_result['pers_id']) {
                    $data_recod['user_number'] = $last_result['pers_id'];
                }
                $db_face_a7_request_record->where($where_record)->data($data_recod)->save();
                $d_face_user_bind_device = M('Face_user_bind_device');
                $where_face_bind_about = [
                    'bind_type' => 0,
                    'bind_id' => $info['bind_id'],
                    'device_type' => 7,
                    'device_id' => $device_id,
                ];
                $bind_user = $d_face_user_bind_device->where($where_face_bind_about)->find();
                if (!$bind_user) {
                    $data_face_bind_about = [
                        'bind_type' => 0,
                        'bind_id' => $info['bind_id'],
                        'device_type' => 7,
                        'person_id' => $last_result['pers_id'] ? $last_result['pers_id'] : $info['bind_id'],
                        'add_time' => time()
                    ];
                    $d_face_user_bind_device->data($data_face_bind_about)->add();
                } else {
                    $data_save = [
                        'person_id' => $last_result['pers_id'] ? $last_result['pers_id'] : $info['bind_id'],
                        'last_time' => time()
                    ];
                    $d_face_user_bind_device->where($where_face_bind_about)->save($data_save);
                }
                $data_set = [
                    'face_img_status' => 1,
                    'face_img_reason' => '',
                ];
                $db_house_village_user_bind->where(['pigcms_id' => $info['bind_id']])->data($data_set)->save();
                $log_id = isset($info['log_id']) && $info['log_id'] ? intval($info['log_id']) : 0;
                if ($log_id) {
                    M('House_face_sync_log')->where(['log_id' => $log_id])->save(['status' => 1, 'msg' => '', 'last_time' => time()]);
                } else {
                    $device_sn = isset($info['device_sn']) && $info['device_sn'] ? trim($info['device_sn']) : '';
                    $pigcms_id = isset($info['bind_id']) && $info['bind_id'] ? intval($info['bind_id']) : 0;
                    $device_id = isset($info['device_id']) && $info['device_id'] ? intval($info['device_id']) : 0;
                    $syncLogParams = [
                        'pigcms_id' => $pigcms_id, 'face_id' => 0, 'device_id' => $device_id, 'device_sn' => $device_sn,
                        'log_time' => time(), 'status' => 1, 'msg' => '',
                    ];
                    $log_id = D('Face_door_service')->handleHouseFaceSyncLog($syncLogParams);
                }
            } else {
                $update_face_code = $db_face_a7_request_record->update_face_code;
                if ($last_result['code'] && $update_face_code[$last_result['code']]) {
                    $remark = $update_face_code[$last_result['code']];
                } elseif ($last_result['reply']!='ACK') {
                    $remark = $last_result['reply'];
                } else {
                    $remark = '人脸同步编辑失败';
                }
                $data_recod = [
                    'status' => 2,
                    'is_implement' => 2,
                    'remark' => $remark
                ];
                $db_face_a7_request_record->where($where_record)->data($data_recod)->save();
                // 人脸记录成功
                if ($info['bind_id']) {
                    $data_set = [
                        'face_img_status' => 2,
                        'face_img_reason' => $remark,
                    ];
                    $db_house_village_user_bind->where(['pigcms_id' => $info['bind_id']])->data($data_set)->save();
                }
                $log_id = isset($info['log_id']) && $info['log_id'] ? intval($info['log_id']) : 0;
                if ($log_id) {
                    M('House_face_sync_log')->where(['log_id' => $log_id])->save(['status' => 2, 'msg' => $remark, 'last_time' => time()]);
                } else {
                    $device_sn = isset($info['device_sn']) && $info['device_sn'] ? trim($info['device_sn']) : '';
                    $pigcms_id = isset($info['bind_id']) && $info['bind_id'] ? intval($info['bind_id']) : 0;
                    $device_id = isset($info['device_id']) && $info['device_id'] ? intval($info['device_id']) : 0;
                    $syncLogParams = [
                        'pigcms_id' => $pigcms_id, 'face_id' => 0, 'device_id' => $device_id, 'device_sn' => $device_sn,
                        'log_time' => time(), 'status' => 2, 'msg' => $remark,
                    ];
                    $log_id = D('Face_door_service')->handleHouseFaceSyncLog($syncLogParams);
                }
            }
            if (417==$last_result['code'] && $info) {
                // no this per_id 设备不存在该人员
                $d_face_user_bind_device = M('Face_user_bind_device');
                $where_face_bind_about = [
                    'bind_type' => 0,
                    'bind_id' => $info['bind_id'],
                    'device_type' => 7,
                    'device_id' => $device_id,
                ];
                $bind_user = $d_face_user_bind_device->where($where_face_bind_about)->find();
                if ($bind_user) {
                    $data_save = [
                        'device_id' => 'del_'.$device_id,
                        'last_time' => time()
                    ];
                    $d_face_user_bind_device->where($where_face_bind_about)->save($data_save);
                }
            }
        }
        $data['version'] = $db_face_a7_request_record->version;
        $data['cmd'] = '';
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }

    /**
     * Notes: 设置是否开启活体检测 反馈
     * @param $device_id
     * @param $last_result
     * @author: wanzy
     * @date_time: 2020/9/9 14:22
     */
    private function device_set_attr($device_id, $last_result) {
        $db_face_a7_request_record = D('Face_a7_request_record');
        if (0==$last_result['code']) {
            // 反馈成功结果
            if ($last_result['index']>0) {
                $where_record = [
                    'id' => $last_result['index'],
                    'cmd_name' => 'set_attr',
                ];
            } else {
                $where_record = [
                    'device_id' => $device_id,
                    'status' => 0,
                    'cmd_name' => 'set_attr',
                ];
            }
            $data_recod = [
                'status' => 1,
                'is_implement' => 2,
                'sucess_time' => time()
            ];
            $db_face_a7_request_record->where($where_record)->delete();
        } else {
            if ($last_result['reply']!='ACK') {
                $remark = '设置失败,code: '.$last_result['code'] . ',reply: '.$last_result['reply'];
            } else {
                $remark = '是否开启活体检测设置失败';
            }
            // 反馈失败结果
            $where_record = [
                'device_id' => $device_id,
                'status' => 0,
                'cmd_name' => 'set_attr',
            ];
            $data_recod = [
                'status' => 2,
                'is_implement' => 2,
                'remark' => $remark
            ];
            $db_face_a7_request_record->where($where_record)->data($data_recod)->save();
        }
        $data['version'] = $db_face_a7_request_record->version;
        $data['cmd'] = '';
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }

    /**
     * Notes: 设置人脸比对确性分数 反馈
     * @param $device_id
     * @param $last_result
     * @author: wanzy
     * @date_time: 2020/9/9 14:22
     */
    private function device_set_match_score($device_id, $last_result) {
        $db_face_a7_request_record = D('Face_a7_request_record');
        if (0==$last_result['code']) {
            // 反馈成功结果
            if ($last_result['index']>0) {
                $where_record = [
                    'id' => $last_result['index'],
                    'cmd_name' => 'set_match_score',
                ];
            } else {
                $where_record = [
                    'device_id' => $device_id,
                    'status' => 0,
                    'cmd_name' => 'set_match_score',
                ];
            }
            $data_recod = [
                'status' => 1,
                'is_implement' => 2,
                'sucess_time' => time()
            ];
            $db_face_a7_request_record->where($where_record)->delete();
        } else {
            if ($last_result['reply']!='ACK') {
                $remark = '设置失败,code: '.$last_result['code'] . ',reply: '.$last_result['reply'];
            } else {
                $remark = '设置识别阈值';
            }
            // 反馈失败结果
            $where_record = [
                'device_id' => $device_id,
                'status' => 0,
                'cmd_name' => 'set_match_score',
            ];
            $data_recod = [
                'status' => 2,
                'is_implement' => 2,
                'remark' => $remark
            ];
            $db_face_a7_request_record->where($where_record)->data($data_recod)->save();
        }
        $data['version'] = $db_face_a7_request_record->version;
        $data['cmd'] = '';
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }


    /**
     * Notes: 设置活体检车
     * @param $device_id
     * @param $last_result
     * @author: wanzy
     * @date_time: 2020/9/9 14:22
     */
    private function device_set_spoofing($device_id, $last_result) {
        $db_face_a7_request_record = D('Face_a7_request_record');
        if (0==$last_result['code']) {
            // 反馈成功结果
            if ($last_result['index']>0) {
                $where_record = [
                    'id' => $last_result['index'],
                    'cmd_name' => 'set_spoofing',
                ];
            } else {
                $where_record = [
                    'device_id' => $device_id,
                    'status' => 0,
                    'cmd_name' => 'set_spoofing',
                ];
            }
            $data_recod = [
                'status' => 1,
                'is_implement' => 2,
                'sucess_time' => time()
            ];
            $db_face_a7_request_record->where($where_record)->delete();
        } else {
            if ($last_result['reply']!='ACK') {
                $remark = '设置失败,code: '.$last_result['code'] . ',reply: '.$last_result['reply'];
            } else {
                $remark = '是否开启活体检测设置失败';
            }
            // 反馈失败结果
            $where_record = [
                'device_id' => $device_id,
                'status' => 0,
                'cmd_name' => 'set_spoofing',
            ];
            $data_recod = [
                'status' => 2,
                'is_implement' => 2,
                'remark' => $remark
            ];
            $db_face_a7_request_record->where($where_record)->data($data_recod)->save();
        }
        $data['version'] = $db_face_a7_request_record->version;
        $data['cmd'] = '';
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }


    /**
     * Notes: 同步删除设备用户信息
     * @param $device_id
     * @param $last_result
     * @author: wanzy
     * @date_time: 2020/9/9 16:42
     */
    private function device_delete_face($device_id, $last_result) {
        $db_face_a7_request_record = D('Face_a7_request_record');
        if ($last_result['index']>0) {
            $where_record = [
                'id' => $last_result['index'],
                'cmd_name' => 'delete_face',
            ];
        } elseif ($last_result['pers_id']>0) {
            $where_record = [
                'device_id' => $device_id,
                'user_number' => $last_result['pers_id'],
                'status' => 0,
                'cmd_name' => 'delete_face',
            ];
        } else {
            $where_record = [
                'device_id' => $device_id,
                'is_implement' => 1,
                'status' => 0,
                'cmd_name' => 'delete_face',
            ];
        }
        $info = $db_face_a7_request_record->where($where_record)->find();
        if (0==$last_result['code'] && $info) {
            // 反馈成功结果
            $data_recod = [
                'status' => 1,
                'is_implement' => 2,
                'sucess_time' => time()
            ];
            $db_face_a7_request_record->where($where_record)->delete();
            $d_face_user_bind_device = M('Face_user_bind_device');
            $where_face_bind_about = [
                'bind_type' => 0,
                'bind_id' => $last_result['pers_id'] ? $last_result['pers_id'] : $info['bind_id'],
                'device_type' => 7,
                'device_id' => $device_id,
            ];
            // 删除关联关系
            $d_face_user_bind_device->where($where_face_bind_about)->delete();
        } else {
            if ($last_result['reply']!='ACK') {
                $remark = '删除失败,code: '.$last_result['code'] . ',reply: '.$last_result['reply'];
            } else {
                $remark = '同步删除设备用户信息失败';
            }
            // 反馈失败结果
            $data_recod = [
                'status' => 2,
                'is_implement' => 2,
                'remark' => $remark
            ];
            $db_face_a7_request_record->where($where_record)->data($data_recod)->save();
        }
        $data['version'] = $db_face_a7_request_record->version;
        $data['cmd'] = '';
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }

    private function device_gpiocontrol($device_id, $last_result) {
        $db_face_a7_request_record = D('Face_a7_request_record');
        if ($last_result['index']>0) {
            $where_record = [
                'id' => $last_result['index'],
                'cmd_name' => 'gpio control',
            ];
        } elseif ($last_result['pers_id']>0) {
            $where_record = [
                'device_id' => $device_id,
                'user_number' => $last_result['pers_id'],
                'status' => 0,
                'cmd_name' => 'gpio control',
            ];
        } else {
            $where_record = [
                'device_id' => $device_id,
                'is_implement' => 1,
                'status' => 0,
                'cmd_name' => 'gpio control',
            ];
        }
        $info = $db_face_a7_request_record->where($where_record)->find();
        if (0==$last_result['code'] && $info) {
            // 反馈成功结果
            // 设备序列号
            $db_house_face_device = M('House_face_device');
            $d_house_user_log = M('House_user_log');
            $d_house_village_floor = M('House_village_floor');
            $d_house_village = M('House_village');
            $d_house_village_user_bind = D('House_village_user_bind');
            $d_house_camera_tip = M('House_camera_tip');
            $where = [
                'device_id' => $device_id,
            ];
            $face_device = $db_house_face_device->where($where)->find();
            $house_village_info = $d_house_village->where(array('village_id' => $face_device['village_id']))->find();
            $city_id = $house_village_info['city_id'];
            $log_name = '';
            $log_name .= $house_village_info['village_name'];
            if ($face_device['floor_id'] != -1) {
                $floor_arr = explode(",",$face_device['floor_id']);
                $house_village_floor_info = $d_house_village_floor
                    ->join('as a LEFT JOIN '.C('DB_PREFIX')."house_village_single as b ON a.single_id = b.id")
                    ->field('a.*, b.single_name')
                    ->where(array('floor_id' => ['in', $floor_arr]))
                    ->select();
                if ($house_village_floor_info) {
                    $data_msg = [];
                    foreach ($house_village_floor_info as $val) {
                        if ($val && $val['floor_name']) {
                            $data_msg[] = ($val['single_name'] ? $val['single_name'] : $val['floor_layer']) . $val['floor_name'];
                        }
                    }
                    if ($data_msg) {
                        $msg = implode(';', $data_msg);
                        $log_name .= ' ' . $msg;
                    }
                }
            } elseif ( $face_device['public_area_id'] >0 ) {
                $public_area_name = M('House_village_public_area')->where(['public_area_id' => $face_device['public_area_id']])->getField('public_area_name');
                $log_name .= ' ' . $public_area_name;
            } elseif ( $face_device['floor_id'] == -1) {
                $floor_layer	= C('config.house_name');
                $floor_name = '大门';
                $log_name .= ' ' . $floor_layer.$floor_name;
            }

            $house_village_bind = $d_house_village_user_bind->get_one_by_bindId($info['bind_id']);
            $uid = $house_village_bind['uid'];
            $log['uid'] = $uid;
            $log['log_bind_id'] = $last_result['pers_id'] ? $last_result['pers_id'] : $info['bind_id'];
            $log['log_name'] = $log_name;
            $log['log_from'] = 2; // 人脸识别门禁-用户操作开门
            $log['log_business_id'] = $face_device['village_id']; // 社区id
            $log['log_more_id'] = $face_device['floor_id']; // 单元id
            $log['device_id'] = $face_device['device_id']; // 设备id
            $log['log_time'] = time(); // 记录时间
            // 0 比对通过，1 比对失败， 2 无模版， 3 无效卡 4 无现场照，5 非活体， 6 证件过期 7 超过时间限制， 8 超过此数限制 100 未知错误
            if (isset($last_result['status'])) {
                $log['log_status'] = $last_result['status'];
            }
            // 比对成功呼叫电梯
            if (!$log['log_status'] && 1==$face_device['device_model'] && $face_device['floor_id'] && $face_device['floor_id']!=-1) {
                D('Face_door_a7_service')->add_send_rs485($face_device['device_id']);
            }
            $log['log_detail'] = serialize($last_result);
            $log['add_time']=time();
            $id = $d_house_user_log->data($log)->add();
            if (!$id) {
                fdump(['用户远程开门记录失败'.__LINE__,$_POST,$last_result,$log],'api/log/'.date('Ymd').'/a7/device_gpiocontrol_log',true);
            }
            $db_house_face_device->where(array('device_id' => $face_device['device_id']))->setField('last_open_time', time());
            // 同步记录到消息列表
            $tip = array(
                'bind_id' =>  $last_result['pers_id'] ? $last_result['pers_id'] : $info['bind_id'],
                'uid' => $uid,
                'type' => 1,
                'village_id' => $face_device['village_id'],
                'camera_sn' => $face_device['device_sn'],
                'city_id' => $city_id,
                'camera_time' => time(),
                'camera_img' => '',
                'tip_time' => time()
            );
            $ids = $d_house_camera_tip->data($tip)->add();
            if (!$ids) {
                fdump(['用户远程开门消息记录失败'.__LINE__,$_POST,$last_result,$log,$tip],'api/log/'.date('Ymd').'/a7/device_gpiocontrol_log',true);
            }
            $db_face_a7_request_record->where($where_record)->delete();
        } else {
            if ($last_result['reply']!='ACK') {
                $remark = '远程开门失败,code: '.$last_result['code'] . ',reply: '.$last_result['reply'];
            } else {
                $remark = '远程开门失败';
            }
            // 反馈失败结果
            $data_recod = [
                'status' => 2,
                'is_implement' => 2,
                'remark' => $remark
            ];
            $db_face_a7_request_record->where($where_record)->data($data_recod)->save();
        }
        $data['version'] = $db_face_a7_request_record->version;
        $data['cmd'] = '';
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }

    /**
     * Notes: 呼叫电梯 反馈
     * @param $device_id
     * @param $last_result
     * @author: wanzy
     * @date_time: 2020/11/12 19:42
     */
    private function device_sendRs485($device_id, $last_result) {
        $db_face_a7_request_record = D('Face_a7_request_record');
        if (0==$last_result['code']) {
            // 反馈成功结果
            $where_record = [
                'device_id' => $device_id,
                'status' => 0,
                'cmd_name' => 'send_rs485',
            ];
            $data_recod = [
                'status' => 1,
                'is_implement' => 2,
                'sucess_time' => time()
            ];
            $db_face_a7_request_record->where($where_record)->delete();
        } else {
            if ($last_result['reply']!='ACK') {
                $remark = '呼叫失败,code: '.$last_result['code'] . ',reply: '.$last_result['reply'];
            } else {
                $remark = '呼叫电梯失败';
            }
            // 反馈失败结果
            $where_record = [
                'device_id' => $device_id,
                'status' => 0,
                'cmd_name' => 'send_rs485',
            ];
            $data_recod = [
                'status' => 2,
                'is_implement' => 2,
                'remark' => $remark
            ];
            $db_face_a7_request_record->where($where_record)->data($data_recod)->save();
        }
        $data['version'] = $db_face_a7_request_record->version;
        $data['cmd'] = '';
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }

    /**
     * Notes: 识别结果推送请求地址
     * @author: wanzy
     * @date_time: 2020/11/6 13:14
     * { "type":"face_result",
     *  "body": {
     *      "message_type":"offline",   //离线推送数据标志（若不是离线数据，则无此字段）
     *      "sequence": 2025,   //消息标识
     *      "matched": 87, //比对结果，0：未比对。-1：比对失败。
     *      //大于0的取值：比对成功时的确信度分数（100分制）注意 = -2时为身份证通行模式,faceimgdata为空（无抓拍数据）
     *      "per_id":"123",   //识别人员 ID
     *      "name":"name",       //人员名称 utf8 编码
     *       "role":1,   //人员角色，0：普通人员。 1：白名单人员。 2：黑名单人员
     *      "hat":1,      //0:未带帽；1：带帽；-1：未检测
     *      "mask":1,   //0:未戴口罩；1：戴口罩；-1：未检测
     *      "sn": "fffffffffff", //设备序列号
     *      "tep":37.23,   //温度(double)
     *      "sec":1512938289,   //识别时间戳（S）
     *      "usec":389,   //时间戳毫秒后缀
     *      "code":0,   //识别错误码
     *      "face_imgsize":100,   //抓拍图片数据尺寸(Base64前)
     *      "model_imgsize":100, //模板图片数据尺寸(Base64前)
     *      "face_imgdata":"ffffffffff",   //抓拍图片数据（Base64编码）
     *      "model_imgdata":"ffffffffffffff",  //模板图片数据（Base64编码）
     *      "per_id_base64":"123",   //识别人员 ID(Base64编码,防止加密后数据丢失)
     *      "name_base64":"name",       //人员名称 utf8 编码(Base64编码,防止加密后数据丢失)
     *      }
     *  }
     */
    public function a7_face_notify() {
        $arrData = $_POST;
        if ($arrData['body']) {
            $last_result = $arrData['body'];
        }
        // 设备序列号
        $db_house_face_device = M('House_face_device');
        $d_house_user_log = M('House_user_log');
        $d_house_village_floor = M('House_village_floor');
        $d_house_village = M('House_village');
        $d_house_village_user_bind = D('House_village_user_bind');
        $d_house_camera_tip = M('House_camera_tip');
        $serialno = $last_result['sn'];
        $where = [
            'is_del' => 0,
            'device_sn' => $serialno,
        ];
        $face_device = $db_house_face_device->where($where)->find();
        if (!$face_device) {
            fdump(['对应设备不存在>'.__LINE__,$arrData],'api/log/'.date('Ymd').'/a7/a7_face_notify_err_log',true);
            return false;
        }
        $house_village_info = $d_house_village->where(array('village_id' => $face_device['village_id']))->find();
        $city_id = $house_village_info['city_id'];
        $log_name = '';
        $log_name .= $house_village_info['village_name'];
        if ($face_device['floor_id'] != -1) {
            $floor_arr = explode(",",$face_device['floor_id']);
            $house_village_floor_info = $d_house_village_floor
                ->join('as a LEFT JOIN '.C('DB_PREFIX')."house_village_single as b ON a.single_id = b.id")
                ->field('a.*, b.single_name')
                ->where(array('floor_id' => ['in', $floor_arr]))
                ->select();
            if ($house_village_floor_info) {
                $data_msg = [];
                foreach ($house_village_floor_info as $val) {
                    if ($val && $val['floor_name']) {
                        $data_msg[] = ($val['single_name'] ? $val['single_name'] : $val['floor_layer']) . $val['floor_name'];
                    }
                }
                if ($data_msg) {
                    $msg = implode(';', $data_msg);
                    $log_name .= ' ' . $msg;
                }
            }
        } elseif ( $face_device['public_area_id'] >0 ) {
            $public_area_name = M('House_village_public_area')->where(['public_area_id' => $face_device['public_area_id']])->getField('public_area_name');
            $log_name .= ' ' . $public_area_name;
        } elseif ( $face_device['floor_id'] == -1) {
            $floor_layer	= C('config.house_name');
            $floor_name = '大门';
            $log_name .= ' ' . $floor_layer.$floor_name;
        }
        // $arrData['last_result'] = $last_result;
        $log = array();
        $face_img = '';
        // 跑计划任务
        if ($face_device['village_id']>0) {
            $third_status = M('House_village_third_config')->where(['village_id' => $face_device['village_id']])->getField('status');
            if (1==$third_status) {
                $param = [];
                $param['village_id'] = $face_device['village_id'];
                $param['type'] = 'person_inout_info';
                D('House_village_third_user_info')->add_plan($param, 'person_inout_info', time() + (mt_rand(5, 20)));
            }
        }
        if ($last_result['per_id']) {
            $where_bind = [
                'user_number' => $last_result['per_id'],
                'village_id' => $face_device['village_id']
            ];
            $house_village_bind = $d_house_village_user_bind->where($where_bind)->field('`uid`,`pigcms_id`')->find();

            ##如果编号没找到人，就用人名找
            if(!$house_village_bind){
                $where_bind = [
                    'name' => $last_result['name'],
                    'village_id' => $face_device['village_id']
                ];
                $house_village_bind = $d_house_village_user_bind->where($where_bind)->field('`uid`,`pigcms_id`')->find();
            }

            $uid = $house_village_bind['uid'];
            if (isset($last_result['face_imgdata'])) {
                $face_imgdata = $this->base64_to_img($last_result['face_imgdata'],$last_result['per_id']);
            }
            if (isset($last_result['model_imgdata'])) {
                $model_imgdata = $this->base64_to_img($last_result['model_imgdata'],$last_result['per_id']);
            }

            if ($face_imgdata) {
                $last_result['face_imgdata'] = $face_imgdata;
                $face_img = $face_imgdata;
                $arrData['face_img'] = $face_imgdata;
                $arrData['model_img'] = $model_imgdata;

                unset($arrData['last_result'], $arrData['body']['face_imgdata'], $arrData['body']['model_imgdata']);
            }
            if (isset($arrData['face_img']) && trim($arrData['face_img'])) {
                $log['cut_image'] = trim($arrData['face_img']);
            } elseif (isset($arrData['model_img']) && trim($arrData['model_img'])) {
                $log['cut_image'] = trim($arrData['model_img']);
            }
            
            if ($model_imgdata) {
                $last_result['model_imgdata'] = $model_imgdata;
            }
            $log['uid'] = $uid ? $uid : 0;
            $log['log_bind_id'] = $uid ? $house_village_bind['pigcms_id'] : ($last_result['per_id'] ? $last_result['per_id'] : 0);
            $log['log_name'] = $log_name;
            $log['log_from'] = 1; // 人脸识别门禁
            $log['log_business_id'] = $face_device['village_id']; // 社区id
            $log['log_more_id'] = $face_device['floor_id']; // 单元id
            $log['device_id'] = $face_device['device_id']; // 设备id

            if (isset($last_result['message_type']) && 'offline'==$last_result['message_type'] && $last_result['sec']) {
                $log['log_time'] = $last_result['sec'] - 8 * 3600; // 记录时间
            } elseif (isset($last_result['sec'])) {
                $log['log_time'] = $last_result['sec'] - 8 * 3600; // 记录时间
            } else {
                $log['log_time'] = time(); // 记录时间
            }

            // 0 比对通过，1 比对失败， 2 无模版， 3 无效卡 4 无现场照，5 非活体， 6 证件过期 7 超过时间限制， 8 超过此数限制 100 未知错误
            if (isset($last_result['status'])) {
                $log['log_status'] = $last_result['status'];
            }
            if ($log['log_status']) {
                $status = 1;
            } else {
                $status = 0;
            }
            // 呼叫电梯
            if (!$log['log_status'] && 1==$face_device['device_model'] && $face_device['floor_id'] && $face_device['floor_id']!=-1) {
                D('Face_door_a7_service')->add_send_rs485($face_device['device_id']);
            }
            $log['temperature'] = floatval($last_result['tep'])>0 ? floatval($last_result['tep']) : '0.0';
            $log['log_detail'] = json_encode($arrData,JSON_UNESCAPED_UNICODE);
            $log['add_time']=time();
            $d_house_user_log->data($log)->add();
            $db_house_face_device->where(array('device_id' => $face_device['device_id']))->setField('last_open_time', time());
            // 同步记录到消息列表
            $tip = array(
                'bind_id' =>  $last_result['per_id'],
                'uid' => $uid,
                'type' => 1,
                'village_id' => $face_device['village_id'],
                'camera_sn' => $face_device['device_sn'],
                'city_id' => $city_id,
                'camera_time' => time(),
                'camera_img' => $face_img,
                'tip_time' => time(),
                'status' => $status,
                'tip_txt' => serialize($arrData),
                'temperature'=> floatval($last_result['tep'])>0 ? floatval($last_result['tep']) : '0.0'
            );
            $d_house_camera_tip->data($tip)->add();
        } elseif($last_result) {
            $log['uid'] = 0;
            $log['log_bind_id'] = 0;
            $log['log_name'] = $log_name;
            $log['log_from'] = 1; // 人脸识别门禁
            $log['log_business_id'] = $face_device['village_id']; // 社区id
            $log['log_more_id'] = $face_device['floor_id']; // 单元id
            $log['device_id'] = $face_device['device_id']; // 设备id
            if (isset($last_result['message_type']) && 'offline'==$last_result['message_type'] && $last_result['sec']) {
                $log['log_time'] = $last_result['sec'] - 8 * 3600; // 记录时间
            } elseif (isset($last_result['sec'])) {
                $log['log_time'] = $last_result['sec'] - 8 * 3600; // 记录时间
            } else {
                $log['log_time'] = time(); // 记录时间
            }
            // 0 比对通过，1 比对失败， 2 无模版， 3 无效卡 4 无现场照，5 非活体， 6 证件过期 7 超过时间限制， 8 超过此数限制 100 未知错误
            if (isset($last_result['status'])) {
                $log['log_status'] = $last_result['status'];
            }else{
                $log['log_status'] =1;
            }
            if (isset($last_result['face_imgdata'])) {
                $face_imgdata = $this->base64_to_img($last_result['face_imgdata'],$last_result['per_id']);
            }
            if (isset($last_result['model_imgdata'])) {
                $model_imgdata = $this->base64_to_img($last_result['model_imgdata'],$last_result['per_id']);
            }
            if ($face_imgdata) {
                $last_result['face_imgdata'] = $face_imgdata;
                $face_img = $face_imgdata;
                $arrData['face_img'] = $face_imgdata;
                $arrData['model_img'] = $model_imgdata;

                unset($arrData['last_result'], $arrData['body']['face_imgdata'], $arrData['body']['model_imgdata']);
            }
            if ($model_imgdata) {
                $last_result['model_imgdata'] = $model_imgdata;
            }
            if (isset($arrData['face_img']) && trim($arrData['face_img'])) {
                $log['cut_image'] = trim($arrData['face_img']);
            } elseif (isset($arrData['model_img']) && trim($arrData['model_img'])) {
                $log['cut_image'] = trim($arrData['model_img']);
            }
            
            $log['temperature'] = floatval($last_result['tep'])>0 ? floatval($last_result['tep']) : '0.0';
            $log['log_detail'] = json_encode($arrData,JSON_UNESCAPED_UNICODE);
            $log['add_time']=time();
            $d_house_user_log->data($log)->add();
            // 同步记录到消息列表
            $tip = array(
                'bind_id' =>  0,
                'uid' => 0,
                'type' => 1,
                'village_id' => $face_device['village_id'],
                'camera_sn' => $face_device['device_sn'],
                'city_id' => $city_id,
                'camera_time' => time(),
                'camera_img' => $face_img,
                'tip_time' => time(),
                'tip_txt' => serialize($arrData),
                'temperature'=> floatval($last_result['tep'])>0 ? floatval($last_result['tep']) : '0.0'
            );
            $d_house_camera_tip->data($tip)->add();
        }

        $data = [
            'code' => 0,
            'sequence' => $last_result['sequence'],
            'msg' => 'success'
        ];
        echo json_encode($data,JSON_UNESCAPED_UNICODE);
        exit();
    }

    // 刷卡/证结果推送请求地址
    public function a7_face_card() {
        $arrData = $_POST;
        if ($arrData['body']) {
            $last_result = $arrData['body'];
        }
        fdump(['刷卡/证结果推送请求地址'.__LINE__,$arrData,$_GET,$last_result],'api/log/'.date('Ymd').'/a7/a7_face_card_log');
        // 设备序列号
        $db_house_face_device = M('House_face_device');
        $d_house_user_log = M('House_user_log');
        $d_house_village_floor = M('House_village_floor');
        $d_house_village = M('House_village');
        $d_house_village_user_bind = D('House_village_user_bind');
        $d_house_camera_tip = M('House_camera_tip');
        $serialno = $arrData['sn'];
        $where = [
            'is_del' => 0,
            'device_sn' => $serialno,
        ];
        $face_device = $db_house_face_device->where($where)->find();
        $house_village_info = $d_house_village->where(array('village_id' => $face_device['village_id']))->find();
        $city_id = $house_village_info['city_id'];
        $log_name = '';
        $log_name .= $house_village_info['village_name'];
        if ($face_device['floor_id'] != -1) {
            $floor_arr = explode(",",$face_device['floor_id']);
            $house_village_floor_info = $d_house_village_floor
                ->join('as a LEFT JOIN '.C('DB_PREFIX')."house_village_single as b ON a.single_id = b.id")
                ->field('a.*, b.single_name')
                ->where(array('floor_id' => ['in', $floor_arr]))
                ->select();
            if ($house_village_floor_info) {
                $data_msg = [];
                foreach ($house_village_floor_info as $val) {
                    if ($val && $val['floor_name']) {
                        $data_msg[] = ($val['single_name'] ? $val['single_name'] : $val['floor_layer']) . $val['floor_name'];
                    }
                }
                if ($data_msg) {
                    $msg = implode(';', $data_msg);
                    $log_name .= ' ' . $msg;
                }
            }
        } elseif ( $face_device['public_area_id'] >0 ) {
            $public_area_name = M('House_village_public_area')->where(['public_area_id' => $face_device['public_area_id']])->getField('public_area_name');
            $log_name .= ' ' . $public_area_name;
        } elseif ( $face_device['floor_id'] == -1) {
            $floor_layer	= C('config.house_name');
            $floor_name = '大门';
            $log_name .= ' ' . $floor_layer.$floor_name;
        }
        $arrData['last_result'] = $last_result;
        $face_img = $last_result['face_img'];
        $log = array();
        if ($last_result['per_id']>0) {
            if (isset($last_result['tvsec'])) {
                $log_time_tmp = $last_result['tvsec'] - 8 * 3600; // 记录时间
            } else {
                $log_time_tmp = time(); // 记录时间
            }
            $logWhere=array('log_bind_id'=>$last_result['per_id'],'log_business_id'=>$face_device['village_id'],'device_id'=>$face_device['device_id']);
            $log_time_tmp1=$log_time_tmp-30;
            $log_time_tmp2=$log_time_tmp+20;
            $logWhere['log_time']=array('between',$log_time_tmp1.','.$log_time_tmp2);
            $oneLog = $d_house_user_log->where($logWhere)->field('log_id')->order('log_id desc')->find();
            if(!empty($oneLog)){
                $dataRet = [
                    'code' => 0,
                    'sequence' => $last_result['sequence'],
                    'msg' => 'success'
                ];
                echo json_encode($dataRet,JSON_UNESCAPED_UNICODE);
                exit();
            }
            $where_bind = [
                'user_number' => $last_result['per_id'],
                'village_id' => $face_device['village_id']
            ];
            $house_village_bind = $d_house_village_user_bind->where($where_bind)->field('`uid`,`pigcms_id`')->find();
            ##如果编号没找到人，就用人名找
            if(!$house_village_bind){
                $where_bind = [
                    'name' => $last_result['name'],
                    'village_id' => $face_device['village_id']
                ];
                $house_village_bind = $d_house_village_user_bind->where($where_bind)->field('`uid`,`pigcms_id`')->find();
            }
            if (isset($last_result['imgdata'])) {
                $face_imgdata = $this->base64_to_img($last_result['imgdata'],$last_result['per_id']);
            }

            if ($face_imgdata) {
                $last_result['imgdata'] = $face_imgdata;
                $face_img = $face_imgdata;
                $arrData['face_img'] = $face_imgdata;
                unset($arrData['body']['imgdata']);
            }
            $uid = $house_village_bind['uid'] ? $house_village_bind['uid'] : 0;
            $log['uid'] = $uid;
            $log['log_bind_id'] = $last_result['per_id'];
            $log['log_name'] = $log_name;
            $log['log_from'] = 3; // 人脸识别门禁
            $log['log_business_id'] = $face_device['village_id']; // 社区id
            $log['log_more_id'] = $face_device['floor_id']; // 单元id
            $log['device_id'] = $face_device['device_id']; // 设备id
            $log['log_time'] = $log_time_tmp; // 记录时间
            $log['card_number'] = isset($last_result['wg_num']) && $last_result['wg_num'] ? $last_result['wg_num'] : '';
            // 0 比对通过，1 比对失败， 2 无模版， 3 无效卡 4 无现场照，5 非活体， 6 证件过期 7 超过时间限制， 8 超过此数限制 100 未知错误
            if (isset($last_result['error_msg']) && $last_result['error_msg'] == 'success and io on') {
                $log['log_status'] = 0;
            } elseif (isset($last_result['code']) && $last_result['code'] == 601) {
                $log['log_status'] = 0;
            } else {
                $log['log_status'] = 1;
            }
            if (isset($arrData['face_img']) && trim($arrData['face_img'])) {
                $log['cut_image'] = trim($arrData['face_img']);
            }
            $log['log_detail'] = serialize($arrData);
            $log['temperature'] = floatval($last_result['tep'])>0 ? floatval($last_result['tep']) : '0.0';
            $log['add_time']=time();
            $id = $d_house_user_log->data($log)->add();
            if (!$id) {
                fdump(['记录信息失败'.__LINE__,$log],'api/log/'.date('Ymd').'/a7/a7_face_card_log',true);
            }
            $db_house_face_device->where(array('device_id' => $face_device['device_id']))->setField('last_open_time', time());
            // 同步记录到消息列表
            $tip = array(
                'bind_id' =>  $last_result['per_id'],
                'uid' => $uid,
                'type' => 1,
                'village_id' => $face_device['village_id'],
                'camera_sn' => $face_device['device_sn'],
                'city_id' => $city_id,
                'camera_time' => time(),
                'camera_img' => $face_img,
                'tip_time' => time()
            );
            $ids = $d_house_camera_tip->data($tip)->add();
            if (!$ids) {
                fdump(['记录信息失败'.__LINE__,$tip],'api/log/'.date('Ymd').'/a7/a7_face_card_log',true);
            }
        } else {
            $log['uid'] = 0;
            $log['log_bind_id'] = 0;
            $log['log_name'] = $log_name;
            $log['log_from'] = 3; // 人脸识别门禁
            $log['log_business_id'] = $face_device['village_id']; // 社区id
            $log['log_more_id'] = $face_device['floor_id']; // 单元id
            $log['device_id'] = $face_device['device_id']; // 设备id
            $log['log_time'] = time(); // 记录时间
            // 0 比对通过，1 比对失败， 2 无模版， 3 无效卡 4 无现场照，5 非活体， 6 证件过期 7 超过时间限制， 8 超过此数限制 100 未知错误

            $log['log_status'] = 1;
            $log['log_detail'] = serialize($arrData);
            $log['add_time']=time();
            $id = $d_house_user_log->data($log)->add();
            if (!$id) {
                fdump(['记录信息失败'.__LINE__,$_POST,$last_result,$log],'api/log/'.date('Ymd').'/a7/a7_face_card_err_log',true);
            }
            // 同步记录到消息列表
            $tip = array(
                'bind_id' =>  0,
                'uid' => 0,
                'type' => 1,
                'village_id' => $face_device['village_id'],
                'camera_sn' => $face_device['device_sn'],
                'city_id' => $city_id,
                'camera_time' => time(),
                'camera_img' => $face_img,
                'tip_time' => time()
            );
            $ids = $d_house_camera_tip->data($tip)->add();
            if (!$ids) {
                fdump(['记录信息失败'.__LINE__,$tip],'api/log/'.date('Ymd').'/a7/a7_face_card_err_log',true);
            }
        }
        $dataRet = [
            'code' => 0,
            'sequence' => $last_result['sequence'],
            'msg' => 'success'
        ];
        echo json_encode($dataRet,JSON_UNESCAPED_UNICODE);
        exit();
    }


    private function base64_to_img($base_img, $id='') {
//        $img_mer_id = sprintf("%09d", $id);
//        $rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
        $rand_num = date('Ymd');// 换成日期存储
        $up_dir = './upload/face_log/'.$rand_num.'/';
        if (!is_dir($up_dir)) {
            mkdir($up_dir, 0777, true);
        }
        if ($base_img) {
            $type = 'jpg';
            $new_file = $up_dir.date('YmdHis_').uniqid().'.'.$type;
            if(file_put_contents($new_file, base64_decode($base_img))){
                //判断是否需要上传至云存储
                $file_handle = new file_handle();
                $file_handle->upload($new_file);
                $img_path = trim($new_file,'.');
                if ($file_handle->check_open_oss() && file_exists($new_file)) {
                    unlink($new_file);
                }
                return $img_path;
            }else{
                return false;
            }
        } else {
            return false;
        }
    }
}