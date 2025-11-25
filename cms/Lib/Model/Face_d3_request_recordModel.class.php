<?php
/**
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2020/9/5 10:23
 *
 */

class Face_d3_request_recordModel extends Model {

    public $version = '0.2';

    public $code_ = [
        0 => '请求已成功处理',
        1 => '请求已成功处理',
        2 => '请求已成功处理',
        3 => '请求已成功处理',
        4 => '认证失败',
        5 => '系统繁忙',
        6 => '资源不足',
        7 => '系统功能授权失败',
        8 => '系统功能已授权',
        9 => '系统功能已授权',
        10 => '文件下载失败',
        11 => '文件完整性校验失败',
        20 => '数据录入达到上限',
        21 => '记录已经存在',
        22 => '记录不存在',
        23 => '写入数据失败',
        24 => '读取数据失败',
        25 => '提取特征值失败',
        26 => '人像质量不满足注册需求',
        30 => '韦根卡号不支持模糊匹配',
        35 => '图像解码失败',
        36=>'图像太大，用于提取特征的jpg图像不能超过10M',
        37=>'归一化图像失败',
        38=>'人脸尺寸太小',
        39=>'人像质量太差',
        40=>'图像中人脸数不为1',
        41=>'图像中人脸不完整'
    ];


    /**
     * 重启后处理
     * @author liukezhu
     * @date 2021/4/15
     * @param $device_id
     * @param $last_result
     */
    public function d3_device_reboot_cam($device_id, $last_result) {
        $db_house_face_device = M('House_face_device');
        $db_face_d3_request_record = D('Face_d3_request_record');
        if (0==$last_result['code']) {
            // 重启成功反馈结果
            $where_record = [
                'device_id' => $device_id,
                'status' => 0,
                'cmd_name' => 'reboot',
            ];
            $data_recod = [
                'status' => 1,
                'is_implement' => 2,
                'sucess_time' => time()
            ];
            $db_face_d3_request_record->where($where_record)->data($data_recod)->save();
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
                'cmd_name' => 'reboot',
            ];
            $data_recod = [
                'status' => 2,
                'is_implement' => 2,
                'remark' => $remark
            ];
            $db_face_d3_request_record->where($where_record)->data($data_recod)->save();
        }
        $data['version'] = $db_face_d3_request_record->version;
        $data['cmd'] = '';
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }


    /**
     * 人脸识别开门
     * @author liukezhu
     * @date 2021/4/21
     */
    public function d3_face_notify($face_device, $last_result) {
        fdump(['11--',$face_device, $last_result],'api/log/'.date('Ymd').'/d3/d3_face_notify1',true);
        if (isset($last_result['match']['person_id']) && !empty($last_result['match']['person_id'])) {
            // 设备序列号
            $db_house_face_device = M('House_face_device');
            $d_house_user_log = M('House_user_log');
            $d_house_village_floor = M('House_village_floor');
            $d_house_village = M('House_village');
            $d_house_village_user_bind = D('House_village_user_bind');
            $d_house_camera_tip = M('House_camera_tip');
            $house_village_info = $d_house_village->where(array('village_id' => $face_device['village_id']))->find();
            $where_bind = [
                'pigcms_id' => $last_result['match']['person_id']
            ];
            $house_village_bind = $d_house_village_user_bind->where($where_bind)->field('uid,pigcms_id')->find();
            if(!$house_village_bind){
                $data=array(
                    'reply'=>'ACK',
                    'cmd'=>'face',
                    'code'=>0,
                    'sequence_no'=>$last_result['sequence_no'],
                    'cap_time'=>$last_result['cap_time'],
                    'gateway_ctrl'=>array(
                        'ctrl_mode'=>'force',
                        'person_id'=>isset($last_result['match']['person_id']) ? $last_result['match']['person_id'] : ''
                    ),
                );
                fdump(['22--',$data],'api/log/'.date('Ymd').'/d3/d3_face_notify2',true);
                echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
            }
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
                        $msg = implode($data_msg,';');
                        $log_name .= ' ' . $msg;
                    }
                }
            }
            elseif ( $face_device['public_area_id'] >0 ) {
                $public_area_name = M('House_village_public_area')->where(['public_area_id' => $face_device['public_area_id']])->getField('public_area_name');
                $log_name .= ' ' . $public_area_name;
            }
            elseif ( $face_device['floor_id'] == -1) {
                $floor_layer	= C('config.house_name');
                $floor_name = '大门';
                $log_name .= ' ' . $floor_layer.$floor_name;
            }
            $arrData['last_result'] = $last_result;
            $log = array();
            $face_img = '';

            $uid = $house_village_bind['uid'];
            $face_imgdata = $this->base64_to_img($last_result['match']['image'],$last_result['match']['person_id']);

            if ($face_imgdata) {
                $last_result['face_imgdata'] = $face_imgdata;
                $face_img = $face_imgdata;
                $arrData['face_img'] = $face_imgdata;
            }
            $log['uid'] = $uid ? $uid : 0;
            $log['log_bind_id'] = $last_result['match']['person_id'];
            $log['log_name'] = $log_name;
            $log['log_from'] = 1; // 人脸识别门禁
            $log['log_business_id'] = $face_device['village_id']; // 社区id
            $log['log_more_id'] = $face_device['floor_id']; // 单元id
            $log['device_id'] = $face_device['device_id']; // 设备id
            if (isset($last_result['cap_time']) && $last_result['cap_time']) {
                $log['log_time'] = strtotime($last_result['cap_time']); // 记录时间
            } else {
                $log['log_time'] = time(); // 记录时间
            }
            // 0 比对通过，1 比对失败， 2 无模版， 3 无效卡 4 无现场照，5 非活体， 6 证件过期 7 超过时间限制， 8 超过此数限制 100 未知错误
            $log['log_status']=0;

            if ($log['log_status']) {
                $status = 1;
            } else {
                $status = 0;
            }
            if (isset($arrData['face_img']) && trim($arrData['face_img'])) {
                $log['cut_image'] = trim($arrData['face_img']);
            }
            $log['temperature'] = floatval($last_result['person']['temperatur'])>0 ? floatval($last_result['person']['temperatur']) : '0.0';
            $log['log_detail'] = serialize($arrData);
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
                'camera_time' => $log['log_time'],
                'camera_img' => $face_img,
                'tip_time' => time(),
                'status' => $status,
                'tip_txt' => serialize($arrData),
                'temperature'=> floatval($last_result['tep'])>0 ? floatval($last_result['tep']) : '0.0'
            );
            $d_house_camera_tip->data($tip)->add();
            $msg='刷脸开门';
            //todo 轨迹信息 d3刷脸开门
            D('House_user_trajectory_log')->addLog(4,$face_device['village_id'],0,$uid,$house_village_bind['pigcms_id'],'d3-device_face_open','D3智能门禁'.$msg,$msg);
        }
        $data=array(
            'reply'=>'ACK',
            'cmd'=>'face',
            'code'=>0,
            'sequence_no'=>$last_result['sequence_no'],
            'cap_time'=>$last_result['cap_time'],
            'gateway_ctrl'=>array(
                'ctrl_mode'=>'force',
                'person_id'=>isset($last_result['match']['person_id']) ? $last_result['match']['person_id'] : ''
            ),
        );
        fdump(['33--',$data],'api/log/'.date('Ymd').'/d3/d3_face_notify3',true);
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }

    /**
     * 人脸添加后处理
     * @author liukezhu
     * @date 2021/4/15
     * @param $device_id
     * @param $last_result
     */
    public function d3_device_create_face($device_id, $last_result) {
        $Face_d3_request_record = D('Face_d3_request_record');
        $db_house_village_user_bind = D('House_village_user_bind');
        // 查出当前执行命令
        $where_record = [
            'device_id' => $device_id,
            'is_implement' => 1,
            'status' => 0,
            'cmd_name' => 'add person jpg',
        ];
        $info = $Face_d3_request_record->where($where_record)->find();
        if (0==$last_result['code'] && $info) {
            // 反馈成功结果
            $data_recod = [
                'status' => 1,
                'is_implement' => 2,
                'sucess_time' => time()
            ];
            $Face_d3_request_record->where($where_record)->data($data_recod)->save();
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
                    'device_type' => 23,
                    'device_id' => $device_id,
                ];
                $bind_user = $d_face_user_bind_device->where($where_face_bind_about)->find();

                $uid=$db_house_village_user_bind->where(['pigcms_id' => $info['bind_id']])->getField('uid');
                //todo 轨迹信息 d3添加人脸
                D('House_user_trajectory_log')->addLog(4,$info['village_id'],0,$uid,$info['bind_id'],'d3-device_face_add','D3智能门禁录入人脸信息','添加人员应答');

                if (!$bind_user) {
                    $data_face_bind_about = [
                        'bind_type' => 0,
                        'bind_id' => $info['bind_id'],
                        'device_id' => $device_id,
                        'device_type' => 23,
                        'person_id' =>  $info['bind_id'],
                        'add_time' => time()
                    ];
                    $d_face_user_bind_device->data($data_face_bind_about)->add();
                } else {
                    $data_save = [
                        'person_id' => $info['bind_id'],
                        'last_time' => time()
                    ];
                    $d_face_user_bind_device->where($where_face_bind_about)->save($data_save);
                }
            }
        } else {
            $remark = $this->code_[$last_result['code']];
            $data_recod = [
                'status' => 2,
                'is_implement' => 2,
                'remark' =>$remark
            ];
            $Face_d3_request_record->where($where_record)->data($data_recod)->save();
            // 人脸记录失败
            if ($info['bind_id']) {
                $data_set = [
                    'face_img_status' => 2,
                    'face_img_reason' => $remark,
                ];
                $db_house_village_user_bind->where(['pigcms_id' => $info['bind_id']])->data($data_set)->save();
            }
        }
        $data['version'] = $Face_d3_request_record->version;
        $data['cmd'] = '';
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }


    /**
     * 人脸编辑后处理
     * @author liukezhu
     * @date 2021/4/15
     * @param $face_device
     * @param $last_result
     */
    public function d3_device_update_face($face_device, $last_result) {
        $db_face_d3_request_record = D('Face_d3_request_record');
        $db_house_village_user_bind = D('House_village_user_bind');
        // 查出当前执行命令
        $where_record = [
            'device_id' => $face_device['device_id'],
            'is_implement' => 1,
            'status' => 0,
            'cmd_name' => 'update person jpg',
        ];
        $info = $db_face_d3_request_record->where($where_record)->find();
        if (0==$last_result['code'] && $info) {
            // 反馈成功结果
            $data_recod = [
                'status' => 1,
                'is_implement' => 2,
                'sucess_time' => time()
            ];
            $db_face_d3_request_record->where($where_record)->data($data_recod)->save();
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
                    'device_type' => 23,
                    'device_id' => $face_device['device_id'],
                ];
                $bind_user = $d_face_user_bind_device->where($where_face_bind_about)->find();
                if (!$bind_user) {
                    $data_face_bind_about = [
                        'bind_type' => 0,
                        'bind_id' => $info['bind_id'],
                        'device_type' => 23,
                        'person_id' =>  $info['bind_id'],
                        'add_time' => time()
                    ];
                    $d_face_user_bind_device->data($data_face_bind_about)->add();
                } else {
                    $data_save = [
                        'person_id' => $info['bind_id'],
                        'last_time' => time()
                    ];
                    $d_face_user_bind_device->where($where_face_bind_about)->save($data_save);
                }

                $uid=$db_house_village_user_bind->where(['pigcms_id' => $info['bind_id']])->getField('uid');
                //todo 轨迹信息 d3编辑人脸
                D('House_user_trajectory_log')->addLog(4,$info['village_id'],0,$uid,$info['bind_id'],'d3-device_face_add','D3智能门禁录入人脸信息','编辑人员应答');
            }
        } else {

            fdump([$last_result,$info],'api/log/'.date('Ymd').'/d3/d3_update_face_notify',true);
            $remark =  $this->code_[$last_result['code']];
            $data_recod = [
                'status' => 2,
                'is_implement' => 2,
                'remark' => $remark
            ];
            $db_face_d3_request_record->where($where_record)->data($data_recod)->save();
            // 人脸记录失败
            if ($info['bind_id']) {
                $data_set = [
                    'face_img_status' => 2,
                    'face_img_reason' => $remark,
                ];
                $db_house_village_user_bind->where(['pigcms_id' => $info['bind_id']])->data($data_set)->save();
            }

            //todo  处理设备【记录不存在】由编辑转添加下发命令
            if($last_result['code'] == 22 && $info){
                fdump([$last_result,$info],'api/log/'.date('Ymd').'/d3/d3_add_face_record',true);
                $this->d3_add_face_record($info['bind_id'],$face_device);
            }

        }
        $data['version'] = $db_face_d3_request_record->version;
        $data['cmd'] = '';
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }


    /**
     * 删除人脸后处理
     * @author liukezhu
     * @date 2021/4/15
     * @param $device_id
     * @param $last_result
     */
    public function d3_device_delete_face($device_id, $last_result) {
        $db_face_d3_request_record = D('Face_d3_request_record');
        $where_record = [
            'device_id' => $device_id,
            'is_implement' => 1,
            'status' => 0,
            'cmd_name' => 'delete person(s)',
        ];
        $info = $db_face_d3_request_record->where($where_record)->find();
        if (0==$last_result['code'] && $info) {
            // 反馈成功结果
            $data_recod = [
                'status' => 1,
                'is_implement' => 2,
                'sucess_time' => time()
            ];
            $db_face_d3_request_record->where($where_record)->data($data_recod)->save();
            $d_face_user_bind_device = M('Face_user_bind_device');
            $where_face_bind_about = [
                'bind_type' => 0,
                'bind_id' => $info['bind_id'],
                'device_type' => 23,
                'device_id' => $device_id,
            ];
            // 删除关联关系
            $d_face_user_bind_device->where($where_face_bind_about)->delete();
        } else {
            if ($last_result['reply']!='ACK') {
                $remark = '删除失败,code: '.$last_result['code'] . ',reply: '. $this->code_[$last_result['code']];;
            } else {
                $remark = '同步删除设备用户信息失败';
            }
            // 反馈失败结果
            $data_recod = [
                'status' => 2,
                'is_implement' => 2,
                'remark' => $remark
            ];
            $db_face_d3_request_record->where($where_record)->data($data_recod)->save();
        }
        $data['version'] = $db_face_d3_request_record->version;
        $data['cmd'] = '';
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }

    /**
     * 设置开启活体检测后处理
     * @author liukezhu
     * @date 2021/4/17
     * @param $device_id
     * @param $last_result
     */
    public function d3_device_set_attr($device_id, $last_result) {
        $db_face_d3_request_record = D('Face_d3_request_record');
        if (0==$last_result['code']) {
            // 反馈成功结果
            $where_record = [
                'device_id' => $device_id,
                'status' => 0,
                'cmd_name' => 'update app params',
            ];
            $data_recod = [
                'status' => 1,
                'is_implement' => 2,
                'sucess_time' => time()
            ];
            $db_face_d3_request_record->where($where_record)->data($data_recod)->save();
        } else {
            if ($last_result['reply']!='ACK') {
                $remark = '设置失败,code: '.$last_result['code'] . ',reply: '.$this->code_[$last_result['code']];
            } else {
                $remark = '是否开启活体检测设置失败';
            }
            // 反馈失败结果
            $where_record = [
                'device_id' => $device_id,
                'status' => 0,
                'cmd_name' => 'update app params',
            ];
            $data_recod = [
                'status' => 2,
                'is_implement' => 2,
                'remark' => $remark
            ];
            $db_face_d3_request_record->where($where_record)->data($data_recod)->save();
        }
        $data['version'] = $db_face_d3_request_record->version;
        $data['cmd'] = '';
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }


    /**
     * 开闸后处理
     * @author liukezhu
     * @date 2021/4/17
     * @param $device_id
     * @param $last_result
     */
    public function d3_device_control($device_id, $last_result) {
        $db_face_d3_request_record = D('Face_d3_request_record');
        $where_record = [
            'device_id' => $device_id,
            'is_implement' => 1,
            'status' => 0,
            'cmd_name' => 'gpio control',
        ];
        $info = $db_face_d3_request_record->where($where_record)->find();
        if (0==$last_result['code'] && $info) {
            // 反馈成功结果
            $data_recod = [
                'status' => 1,
                'is_implement' => 2,
                'sucess_time' => time()
            ];
            $db_face_d3_request_record->where($where_record)->data($data_recod)->save();
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
                        $msg = implode($data_msg,';');
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
            $log['log_bind_id'] = $info['bind_id'];
            $log['log_name'] = $log_name;
            $log['log_from'] = 2; // 人脸识别门禁-用户操作开门
            $log['log_business_id'] = $face_device['village_id']; // 社区id
            $log['log_more_id'] = $face_device['floor_id']; // 单元id
            $log['device_id'] = $face_device['device_id']; // 设备id
            $log['log_time'] = time(); // 记录时间
            $log['log_status']=0;
            // 0 比对通过，1 比对失败， 2 无模版， 3 无效卡 4 无现场照，5 非活体， 6 证件过期 7 超过时间限制， 8 超过此数限制 100 未知错误
            $log['log_detail'] = serialize($last_result);
            $log['add_time']=time();
            $d_house_user_log->data($log)->add();

            $db_house_face_device->where(array('device_id' => $face_device['device_id']))->setField('last_open_time', time());
            // 同步记录到消息列表
            $tip = array(
                'bind_id' => $info['bind_id'],
                'uid' => $uid,
                'type' => 1,
                'village_id' => $face_device['village_id'],
                'camera_sn' => $face_device['device_sn'],
                'city_id' => $city_id,
                'camera_time' => time(),
                'camera_img' => '',
                'tip_time' => time()
            );
            $d_house_camera_tip->data($tip)->add();
        } else {
            if ($last_result['reply']!='ACK') {
                $remark = '远程开门失败,code: '.$last_result['code'] . ',reply: '.$this->code_[$last_result['code']];
            } else {
                $remark = '远程开门失败';
            }
            // 反馈失败结果
            $data_recod = [
                'status' => 2,
                'is_implement' => 2,
                'remark' => $remark
            ];
            $db_face_d3_request_record->where($where_record)->data($data_recod)->save();
        }
        $data['version'] = $db_face_d3_request_record->version;
        $data['cmd'] = '';
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }


    //-------------------------------


    /**
     * 设置人脸比对确性分数
     * @author liukezhu
     * @date 2021/4/15
     * @param $device_id
     * @param array $data
     * @param int $village_id
     * @param array $device_info
     * @return array
     *
     */
    public function d3_set_match_score_record($device_id,$data=[],$village_id=0,$device_info=[]) {
        if (empty($device_info)) {
            $device_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        }
        if (!$village_id && $device_info['village_id']) {
            $village_id = $device_info['village_id'];
        }
        // 具体请求区分名称
        $cmd_name = 'update app params';
        $ret_data = [
            'error' => 0,
            'msg' => ''
        ];
        $where_repeat = [
            'device_id' => $device_id,
            'cmd_name' => $cmd_name,
            'status' => 0,
        ];
        $repeat_id = M('Face_d3_request_record')->where($where_repeat)->getField('id');
        if ($repeat_id) {
            // 直接将之前未执行的命令改为已执行
            $this->where(['id' => $repeat_id])->data(['status' => 1, 'update_time' => time()])->save();
        }

        $device_sn = $device_info['device_sn'];
        $operator_type = isset($data['operator_type']) ? intval($data['operator_type']) : 1;
        $operator_id = isset($data['operator_id']) ? intval($data['operator_id']) : $village_id;
        $version = isset($data['version']) ? intval($data['version']) : $this->version;
        $status = 0;
        $add_time = time();
        // 如果有评分以评分为主  不然默认 80 分
        $data_msg = [
            'version' => $version,
            'cmd' => $cmd_name,
            'face'=>array(
                'match_score'=>isset($data['device_score'])?intval($data['device_score']) : 80,
                'enable_alive'=>(intval($data['device_alive']) == 1) ? true : false,
                'enable_dereplication'=>true,
                'derep_timeout'=>3
            )

        ];
        // 整合需要添加的数据
        $add_data = [
            'village_id' => $village_id,
            'device_sn' => $device_sn,
            'device_id' => $device_id,
            'operator_type' => $operator_type,
            'operator_id' => $operator_id,
            'cmd_name' => $cmd_name,
            'version' => $version,
            'status' => $status,
            'data' => serialize($data_msg),
            'add_time' => $add_time,
            'sort' => 99
        ];
        $add_id = $this->data($add_data)->add();
        if ($add_id) {
            $ret_data['data'] = $add_id;
            return $ret_data;
        } else {
            fdump($this->_sql(),'api/log/'.date('Ymd').'/d3/add_set_match_score_record_log',true);
            $ret_data['error'] = 1;
            $ret_data['msg'] = '添加失败';
            return $ret_data;
        }
    }


    /**
     * 设备参数获取
     * @author liukezhu
     * @date 2021/4/17
     * @param $device_id
     * @param $last_result
     */
    public function d3_device_get($device_id, $last_result) {
        $db_house_face_device = M('House_face_device');
        $db_face_d3_request_record = D('Face_d3_request_record');
        if (0==$last_result['code']) {
            // 反馈成功结果

            $where_record = [
                'device_id' => $device_id,
                'status' => 0,
                'cmd_name' => 'request app params',
            ];
            $data_recod = [
                'status' => 1,
                'is_implement' => 2,
                'sucess_time' => time()
            ];
            $db_face_d3_request_record->where($where_record)->data($data_recod)->save();

            $data['device_status'] = 1;
            $data['last_time'] = time();
            $data['login_time'] = time();
            $data['device_info'] = serialize($last_result);
            if (isset($last_result['face']['match_score']) && $last_result['face']['match_score']) {
                $data['device_score'] = $last_result['face']['match_score'];
            }
            if (isset($last_result['face']['enable_alive']) && !$last_result['face']['enable_alive']) {
                $data['device_alive'] = $last_result['face']['enable_alive'] ? 1 : 0;
            }
            $db_house_face_device->where(array('device_id' => $device_id))->data($data)->save();
        } else {
            // 反馈失败结果
            if ($last_result['reply']!='ACK') {
                $remark = '获取失败,code: '.$last_result['code'] . ',reply: '.$this->code_[$last_result['code']];
            } else {
                $remark = '获取失败';
            }
            $where_record = [
                'device_id' => $device_id,
                'status' => 0,
                'cmd_name' => 'request app params',
            ];
            $data_recod = [
                'status' => 2,
                'is_implement' => 2,
                'remark' => $remark
            ];
            $db_face_d3_request_record->where($where_record)->data($data_recod)->save();
        }
        $data['version'] = $db_face_d3_request_record->version;
        $data['cmd'] = '';
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }

    /**
     * d3重启命令
     * @author liukezhu
     * @date 2021/4/16
     * @param $device_id
     * @param $data
     * @param int $village_id
     * @param array $device_info
     * @return array
     */
    public function d3_reboot_cam($device_id,$village_id=0,$device_info=[]) {
        $data=[];
        if (empty($device_info)) {
            $device_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        }
        if (!$village_id && $device_info['village_id']) {
            $village_id = $device_info['village_id'];
        }
        // 具体请求区分名称
        $cmd_name = 'reboot';
        $ret_data = [
            'error' => 0,
            'msg' => ''
        ];
        $where_repeat = [
            'device_id' => $device_id,
            'cmd_name' => $cmd_name,
            'status' => 0,
        ];
        $repeat_id = $this->where($where_repeat)->getField('id');
        if ($repeat_id) {
            // 直接将之前未执行的命令改为已执行
            $this->where(['id' => $repeat_id])->data(['status' => 1, 'update_time' => time()])->save();
        }
        $device_sn = $device_info['device_sn'];
        $operator_type = isset($data['operator_type']) ? intval($data['operator_type']) : 1;
        $operator_id = isset($data['operator_id']) ? intval($data['operator_id']) : $village_id;
        $version = isset($data['version']) ? intval($data['version']) : $this->version;
        $status = 0;
        $add_time = time();
        $data_msg = [
            'version' => $version,
            'cmd' => $cmd_name,
        ];
        // 整合需要添加的数据
        $add_data = [
            'village_id' => $village_id,
            'device_sn' => $device_sn,
            'device_id' => $device_id,
            'operator_type' => $operator_type,
            'operator_id' => $operator_id,
            'cmd_name' => $cmd_name,
            'version' => $version,
            'status' => $status,
            'data' => serialize($data_msg),
            'add_time' => $add_time,
            'sort' => 100
        ];
        $add_id = $this->data($add_data)->add();
        if ($add_id) {
            $ret_data['data'] = $add_id;
            return $ret_data;
        } else {
            fdump($this->_sql(),'api/log/'.date('Ymd').'/d3/add_reboot_cam_record_log',true);
            $ret_data['error'] = 1;
            $ret_data['msg'] = '添加失败';
            return $ret_data;
        }
    }

    /**
     * 开闸
     * @author liukezhu
     * @date 2021/4/19
     * @param $device_id
     * @param int $village_id
     * @param array $device_info
     * @return array
     */
    public function d3_gpio($device_id,$village_id=0,$uid) {
        $data=[];
        fdump($device_id,'api/log/'.date('Ymd').'/d3/d3_gpio',true);
        $device_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        if (!$village_id && $device_info['village_id']) {
            $village_id = $device_info['village_id'];
        }
        // 具体请求区分名称
        $cmd_name = 'gpio control';
        $ret_data = [
            'error' => 0,
            'msg' => ''
        ];
        $where_repeat = [
            'device_id' => $device_id,
            'cmd_name' => $cmd_name,
            'status' => 0,
        ];
        $repeat_id = $this->where($where_repeat)->getField('id');
        if ($repeat_id) {
            // 直接将之前未执行的命令改为已执行
            $this->where(['id' => $repeat_id])->data(['status' => 1, 'update_time' => time()])->save();
        }
        $device_sn = $device_info['device_sn'];
        $operator_type = isset($data['operator_type']) ? intval($data['operator_type']) : 1;
        $operator_id = isset($data['operator_id']) ? intval($data['operator_id']) : $village_id;
        $version = isset($data['version']) ? intval($data['version']) : $this->version;
        $status = 0;
        $add_time = time();
        $data_msg = [
            'version' => $version,
            'cmd' => $cmd_name,
            'port'=>1,
            'ctrl_type'=>'on',
            'ctrl_mode'=>'force',
            'person_id'=>$uid

        ];
        // 整合需要添加的数据
        $add_data = [
            'village_id' => $village_id,
            'device_sn' => $device_sn,
            'device_id' => $device_id,
            'operator_type' => $operator_type,
            'operator_id' => $operator_id,
            'bind_id'=>$uid,
            'cmd_name' => $cmd_name,
            'version' => $version,
            'status' => $status,
            'data' => serialize($data_msg),
            'add_time' => $add_time,
            'sort' => 100
        ];
        $add_id = $this->data($add_data)->add();
        if ($add_id) {
            $ret_data['data'] = $add_id;
            return $ret_data;
        } else {
            $ret_data['error'] = 1;
            $ret_data['msg'] = '添加失败';
            return $ret_data;
        }
    }


    /**
     * d3获取状态
     * @author liukezhu
     * @date 2021/4/17
     * @param $device_id
     * @param int $village_id
     * @param array $device_info
     * @return bool
     */
    public function d3_get_status($device_id,$village_id=0, $device_info=[]) {
        $this->d3_get_status_($device_id, [], $village_id, $device_info);
        return true;
    }

    /**
     * d3 获取状态写入
     * @author liukezhu
     * @date 2021/4/17
     * @param $device_id
     * @param $data
     * @param int $village_id
     * @param array $device_info
     * @return array
     */
    public function d3_get_status_($device_id,$data,$village_id=0,$device_info=[]) {
        if (empty($device_info)) {
            $device_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        }
        if (!$village_id && $device_info['village_id']) {
            $village_id = $device_info['village_id'];
        }
        // 具体请求区分名称
        $cmd_name = 'request app params';
        $ret_data = [
            'error' => 0,
            'msg' => ''
        ];
        $where_repeat = [
            'device_id' => $device_id,
            'cmd_name' => $cmd_name,
            'status' => 0,
        ];
        $repeat_id = $this->where($where_repeat)->getField('id');
        if ($repeat_id) {
            // 直接将之前未执行的命令改为已执行
            $this->where(['id' => $repeat_id])->data(['status' => 1, 'update_time' => time()])->save();
        }
        $device_sn = $device_info['device_sn'];
        $operator_type = isset($data['operator_type']) ? intval($data['operator_type']) : 1;
        $operator_id = isset($data['operator_id']) ? intval($data['operator_id']) : $village_id;
        $version = isset($data['version']) ? intval($data['version']) : $this->version;
        $status = 0;
        $add_time = time();
        $data_msg = [
            'version' => $version,
            'cmd' => $cmd_name,
        ];
        // 整合需要添加的数据
        $add_data = [
            'village_id' => $village_id,
            'device_sn' => $device_sn,
            'device_id' => $device_id,
            'operator_type' => $operator_type,
            'operator_id' => $operator_id,
            'cmd_name' => $cmd_name,
            'version' => $version,
            'status' => $status,
            'data' => serialize($data_msg),
            'add_time' => $add_time,
            'sort' => 100
        ];
        $add_id = $this->data($add_data)->add();
        if ($add_id) {
            $ret_data['data'] = $add_id;
            return $ret_data;
        } else {
            $ret_data['error'] = 1;
            $ret_data['msg'] = '添加失败';
            return $ret_data;
        }
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
            fdump($new_file,'$new_file');
            if(file_put_contents($new_file, base64_decode($base_img))){
                //判断是否需要上传至云存储
                $file_handle = new file_handle();
                $file_handle->upload($new_file);
                $img_path = trim($new_file,'.');
                return $img_path;
            }else{
                return false;
            }
        } else {
            return false;
        }
    }


    /**
     * Notes: 转换base64
     * @param string $img
     * @param bool $imgHtmlCode
     * @return string
     * @author: wanzy
     * @date_time: 2020/9/5 14:05
     */
    public function imgtobase64($img='', $imgHtmlCode=true)
    {
//        $imageInfo = getimagesize($img);
//        $base64 = chunk_split(base64_encode(file_get_contents($img)));
//        return 'data:' . $imageInfo['mime'] . ';base64,' . chunk_split(base64_encode(file_get_contents($img)));
        return base64_encode(file_get_contents($img));
    }


    //todo 处理设备不存在人员数据，由编辑转添加
    public function d3_add_face_record($pigcms_id,$face_device){
        $bind_info = M('House_village_user_bind')->where(array('pigcms_id' => $pigcms_id))->find();
        if(!$bind_info){
            fdump(['$bind_info不存在',$pigcms_id,$face_device],'api/log/'.date('Ymd').'/d3/d3_add_face_record',true);
            return false;
        }
        $cmd_name = 'add person jpg';
        $img_data='';
        // 获取下图片
        if ($bind_info['uid']>0) {
            $d_house_face_img = M('House_face_img');
            $image_info = $d_house_face_img->where(array('uid'=>$bind_info['uid'],'status'=>['in', [0,3]],'img_url'=>array('neq','')))->order('add_time ASC')->find();
            $img_url = $image_info['img_url'];
            if (3==$image_info['status']) {
                import('@.ORG.face.word_encryption');
                $word_encryption = new word_encryption();
                // 加密的图片解密
                $img_url = $word_encryption->text_decrypt($img_url);
            }
            $img_url = thumb_img($img_url,600,600,'', true);
            $img_url = Image::transform_image($img_url);

            $file_handle = new file_handle();
            if($file_handle->check_open_oss()){
                $img_url = thumb_img($img_url,600,600,'',true);
            }
            $img_data = $this->imgtobase64($img_url);
        }
        $img_=$img_data ? $img_data : '';
        if(empty($img_)){
            fdump(['$img_不存在',$img_data],'api/log/'.date('Ymd').'/d3/d3_add_face_record',true);
            $data_set = [
                'face_img_status' => 2,
                'face_img_reason' => '用户缺少上传头像',
            ];
            M('House_village_user_bind')->where(array('pigcms_id' => $pigcms_id))->data($data_set)->save();
            return false;
        }
        $data_msg=array(
            'version'=>$this->version,
            'cmd'=>$cmd_name,
            'id'=>$bind_info['pigcms_id'],
            'name'=>$bind_info['name'],
            'role'=>1,
            'cover'=>false,
            'kind'=>0,
            'feature_unit_size'=>0,
            'feature_num'=>0,
            'feature_data'=>[],
            'image_num'=>1,
            'reg_images'=>array(
                array(
                    'format'=>'jpg',
                    'image_data'=>$img_
                )
            ),
            'norm_image_num'=>1,
            'norm_images'=>array(
                array(
                    'width'=>150,
                    'height'=>150,
                    'image_data'=>$img_
                )
            ),
            'customer_text'=>''
        );
        // 整合需要添加的数据
        $add_data = [
            'village_id' => $bind_info['village_id'],
            'device_sn' => $face_device['device_sn'],
            'device_id' => $face_device['device_id'],
            'bind_id' => $pigcms_id,
            'operator_type' => 1,
            'operator_id' => $bind_info['village_id'],
            'cmd_name' => $cmd_name,
            'version' => $this->version,
            'status' => 0,
            'data' => serialize($data_msg),
            'add_time' => time(),
        ];
        return $this->data($add_data)->add();
    }

    /**
     * Notes: 添加或者编辑人脸
     * @param int $device_id 设备id
     * @param int $pigcms_id 小区用户绑定id
     * @param int $village_id 小区id
     * @param array $data 内容数组
     * @param array $bind_info 用户绑定身份信息
     * @param array $device_info 设备信息
     * @return mixed
     * @author: wanzy
     * @date_time: 2020/9/5 14:32
     */
    public function add_create_face_record($device_id,$pigcms_id,$village_id=0,$bind_info = [],$data=[],$device_info=[]) {
        if (empty($bind_info)) {
            $bind_info = M('House_village_user_bind')->where(array('pigcms_id' => $pigcms_id))->find();
        }
        if (empty($device_info)) {
            $device_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        }
        $log_id = 0;
//        $log_id = M('House_face_sync_log')->add([
//            'pigcms_id'=>$pigcms_id,
//            'face_id'=>0,
//            'device_id'=>$device_id,
//            'device_sn'=>$device_info['device_sn'],
//            'log_time'=>time(),
//            'status'=>0,
//            'device_info'=>json_encode($device_info),
//        ]);
        if (!$village_id && $bind_info['village_id']) {
            $village_id = $bind_info['village_id'];
        } elseif (!$village_id && $device_info['village_id']) {
            $village_id = $device_info['village_id'];
        }
        $d_face_user_bind_device = M('Face_user_bind_device');
        $where_face_bind_about = [
            'bind_type' => 0,
            'bind_id' => $pigcms_id,
            'device_type' => 23,
            'device_id' => $device_id,
        ];
        $bind_user = $d_face_user_bind_device->where($where_face_bind_about)->find();
        if ($bind_user) {
            // 如果存在同步过数据 即为编辑
            // 具体请求区分名称
            $cmd_name = 'update person jpg';
        } else {
            // 未同步过进行添加
            $cmd_name = 'add person jpg';
        }
        $where_repeat = [
            'device_id' => $device_id,
            'bind_id' => $pigcms_id,
            'cmd_name' => $cmd_name,
            'status' => 0,
        ];
        $repeat_id = M('Face_d3_request_record')->where($where_repeat)->getField('id');
        if ($repeat_id) {
            // 直接将之前未执行的命令改为已执行
            M('Face_d3_request_record')->where(['id' => $repeat_id])->data(['status' => 1, 'update_time' => time()])->save();
        }

        $device_sn = $device_info['device_sn'];
        $operator_type = isset($data['operator_type']) ? intval($data['operator_type']) : 1;
        $operator_id = isset($data['operator_id']) ? intval($data['operator_id']) : $village_id;
        $version = isset($data['version']) ? intval($data['version']) : $this->version;
        $status = 0;
        $add_time = time();

        // 获取下图片
        if ($bind_info['uid']>0) {
            $d_house_face_img = M('House_face_img');
            $image_info = $d_house_face_img->where(array('uid'=>$bind_info['uid'],'status'=>['in', [0,3]],'img_url'=>array('neq','')))->order('add_time ASC')->find();
            $img_url = $image_info['img_url'];
            if (3==$image_info['status']) {
                import('@.ORG.face.word_encryption');
                $word_encryption = new word_encryption();
                // 加密的图片解密
                $img_url = $word_encryption->text_decrypt($img_url);
            }
//            $imgurl_=$img_url;
            $img_url = thumb_img($img_url,600,600,'', true);

            //,pathinfo($imgurl_, PATHINFO_EXTENSION)
            $img_url = Image::transform_image($img_url);

            $file_handle = new file_handle();
            if($file_handle->check_open_oss()){
                $img_url = thumb_img($img_url,600,600,'',true);
            }
            $img_data = $this->imgtobase64($img_url);
            $face_id = $image_info['id'];
        }

        if (!in_array($bind_info['type'],[0,3,4,5,6]) && intval($bind_info['parent_id']) > 0) {
            // 查询业主
            $parent_user = D('House_village_user_bind')->where(array('pigcms_id'=>$bind_info['parent_id']))->find();
            if ($parent_user['property_endtime']>0) {
                $property_endtime = $parent_user['property_endtime'];
            } else {
                $property_endtime = 0;
            }
            if ($parent_user['property_starttime']>0) {
                $property_starttime = $parent_user['property_starttime'];
            } else {
                $property_starttime = 0;
            }
        }
        elseif ($bind_info['type']!=4) {
            $property_starttime = $bind_info['property_starttime']?$bind_info['property_starttime']:0;
            $property_endtime = $bind_info['property_endtime']?$bind_info['property_endtime']:0;
        }
        if (in_array($bind_info['type'],[5,6]) && !$property_endtime) {
            if($log_id){
                M('House_face_sync_log')->where(['log_id'=>$log_id])->save([
                    'status'=>2,
                    'msg'=>L_('访客或者临时出入证无法同步人脸'),
                    'last_time'=>time()
                ]);
            }
            fdump($device_id,'api/log/'.date('Ymd').'/d3/add_create_face_record_err_log',true);
            return false;
        }

        if ($property_endtime>0 && in_array($bind_info['type'],[0,1,2,3])) {
            if ($property_starttime>0) {
                $s_time = $property_starttime;
            } else {
                $s_time = time();
            }
            $e_time = $property_endtime;
            $where = [
                'village_id' => $village_id
            ];
            $village_info = M('House_village')->where($where)->field('owe_property_open_door, owe_property_open_door_day')->find();
            if(isset($bind_info['open_door_day']) && $bind_info['open_door_day']>0){
                $village_info['owe_property_open_door_day']=$bind_info['open_door_day'];
            }
            if ($village_info['owe_property_open_door']==1 && intval($village_info['owe_property_open_door_day'])>0) {
                $e_time += intval($village_info['owe_property_open_door_day']) * 86400;
            }
        }

        $img_=$img_data ? $img_data : '';
        if(empty($img_)){
            fdump(['$img_不存在',$img_data],'api/log/'.date('Ymd').'/d3/add_create_face_record',true);
            $data_set = [
                'face_img_status' => 2,
                'face_img_reason' => '用户缺少上传头像',
            ];
            M('House_village_user_bind')->where(array('pigcms_id' => $pigcms_id))->data($data_set)->save();
            if($log_id){
                M('House_face_sync_log')->where(['log_id'=>$log_id])->save([
                    'status'=>2,
                    'msg'=>L_('用户缺少上传头像'),
                    'last_time'=>time()
                ]);
            }
            return false;
        }
        $data_msg=array(
            'version'=>$version,
            'cmd'=>$cmd_name,
            'id'=>$bind_info['pigcms_id'],
            'name'=>$bind_info['name'],
            'role'=>1,
            'cover'=>false,
            'kind'=>0,
            'feature_unit_size'=>0,
            'feature_num'=>0,
            'feature_data'=>[],
            'image_num'=>1,
            'reg_images'=>array(
                array(
                    'format'=>'jpg',
                    'image_data'=>$img_
                )
            ),
            'norm_image_num'=>1,
            'norm_images'=>array(
                array(
                    'width'=>150,
                    'height'=>150,
                    'image_data'=>$img_
                )
            ),
            'customer_text'=>''
        );
        if ($bind_info['ic_card']) {
            $data_msg['long_card_id'] = intval($bind_info['ic_card']); // 韦根号,智能卡号(可选)
        }
        if ($s_time && $e_time) {
            $data_msg['term_start'] = date('Y/m/d H:i:s',$s_time);// 启用日期时间戳(可选)
            $data_msg['term'] = date('Y/m/d H:i:s',$e_time); // 有效日期时间戳(可选)
        }
        // 整合需要添加的数据
        $add_data = [
            'village_id' => $village_id,
            'device_sn' => $device_sn,
            'device_id' => $device_id,
            'bind_id' => $pigcms_id,
            'operator_type' => $operator_type,
            'operator_id' => $operator_id,
            'cmd_name' => $cmd_name,
            'version' => $version,
            'status' => $status,
            'data' => serialize($data_msg),
            'add_time' => $add_time,
        ];
        $add_id = $this->data($add_data)->add();
        if ($add_id) {
            $ret_data['data'] = $add_id;
            if($log_id){
                M('House_face_sync_log')->where(['log_id'=>$log_id])->save([
                    'status'=>1,
                    'msg'=>'',
                    'last_time'=>time()
                ]);
            }
            return $ret_data;
        }
        else {
            fdump('>>>>>>>>>>>>>>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/d3/add_create_face_record_err_log',true);
            $ret_data['error'] = 1;
            $ret_data['msg'] = '添加失败';
            if($log_id){
                M('House_face_sync_log')->where(['log_id'=>$log_id])->save([
                    'status'=>2,
                    'msg'=>L_('人脸同步失败'),
                    'last_time'=>time()
                ]);
            }
            return $ret_data;
        }
    }

    /**
     * 循环处理同步人员
     * @author liukezhu
     * @date 2021/4/16
     * @param $device_id
     * @param $bind_arr_id
     * @return bool
     */
    public function d3_arr_person_msg($device_id,$bind_arr_id,$orderGroupId='') {
        if ($bind_arr_id) {
            foreach ($bind_arr_id as $item) {
                $this->sync_d3_face_per($device_id, $item,0,[],$orderGroupId);
            }
        }
        return true;
    }

    /**
     * Notes: 单个小区绑定用户同步
     * @param $device_id
     * @param $pigcms_id
     * @param int $village_id
     * @param array $device_info
     * @return bool
     * @author: wanzy
     * @date_time: 2020/9/8 9:31
     */
    public function sync_d3_face_per($device_id, $pigcms_id,$village_id=0, $device_info=[],$orderGroupId='') {
        if (empty($device_info)) {
            $device_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        }
        if (!$pigcms_id) {
            return false;
        }
        if (!$village_id && $device_info['village_id']) {
            $village_id = $device_info['village_id'];
        }
        // 不论是单个还是全部转换成 数组
        $where = [
            'village_id' => $village_id,
            'pigcms_id' => $pigcms_id,
            'status' => 1
        ];
        $db_house_village_user_bind = M('House_village_user_bind');
        $user_bind = $db_house_village_user_bind->where($where)->find();
        if ($user_bind) {
            $data = [
                'operator_type' => 0,
                'operator_id' => $pigcms_id,
            ];
            $this->add_create_face_record($device_id, $pigcms_id, $village_id, $user_bind, $data, $device_info);
        }
        return true;
    }

    /**
     * 同步删除设备上人员
     * @author liukezhu
     * @date 2021/4/16
     * @param $pigcms_id
     * @return array|bool
     */
    public function d3_delete_face($device_id,$pigcms_id,$user_bind) {
        $device_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        if(!$device_info){
            return false;
        }
        //软删除图片资源
        D('House_face_img')->where(array(
            'uid'=>$user_bind['uid']
        ))->data(['status' => 1, 'del_time' => time()])->save();

        $ret_data = [
            'error' => 0,
            'msg' => ''
        ];
        $d_face_user_bind_device = M('Face_user_bind_device');
        $where_face_bind_about = [
            'bind_type' => 0,
            'bind_id' => $pigcms_id,
            'device_type' => 23,
            'device_id' => $device_info['device_id'],
        ];
        $bind_user = $d_face_user_bind_device->where($where_face_bind_about)->find();

        if (empty($bind_user)) {
            $ret_data['error'] = 0;
            $ret_data['msg'] = '已经删除过了';
            // 以防万一还在同步添加或者更新的一并改为已执行
            $where_repeat = [
                'device_id' => $device_info['device_id'],
                'bind_id' => $pigcms_id,
                'cmd_name' => ['in', ['add person jpg', 'update person jpg']],
                'status' => 0,
            ];
            // 直接将之前未执行的命令改为已执行
            $this->where($where_repeat)->data(['status' => 1, 'update_time' => time()])->save();
        }
        // 具体请求区分名称
        $cmd_name = 'delete person(s)';
        $where_repeat = [
            'device_id' => $device_info['device_id'],
            'bind_id' => strval($pigcms_id),
            'cmd_name' => $cmd_name,
            'status' => 0,
        ];
        $repeat_id = $this->where($where_repeat)->getField('id');
        if ($repeat_id) {
            // 直接将之前未执行的命令改为已执行
            $this->where(['id' => $repeat_id])->data(['status' => 1, 'update_time' => time()])->save();
        }

        $device_sn = $device_info['device_sn'];
        $status = 0;
        $add_time = time();
        $data_msg = [
            'version' => $this->version,
            'cmd' => $cmd_name,
            'flag' => -1,
            'id' => (string) $pigcms_id,
        ];
        // 整合需要添加的数据
        $add_data = [
            'village_id' => $device_info['village_id'],
            'device_sn' => $device_sn,
            'device_id' => $device_info['device_id'],
            'bind_id' => $pigcms_id,
            'operator_type' => 0,
            'operator_id' => $device_info['village_id'],
            'cmd_name' => $cmd_name,
            'version' => $this->version,
            'status' => $status,
            'data' => serialize($data_msg),
            'add_time' => $add_time,
            'sort' => 99
        ];
        $add_id = $this->data($add_data)->add();
        if ($add_id) {
            $ret_data['data'] = $add_id;
            // 以防万一还在同步添加或者更新的一并改为已执行
            $where_repeat = [
                'device_id' => $device_info['device_id'],
                'bind_id' => $pigcms_id,
                'cmd_name' => ['in', ['add person jpg', 'update person jpg']],
                'status' => 0,
            ];
            // 直接将之前未执行的命令改为已执行
            $this->where($where_repeat)->data(['status' => 1, 'update_time' => time()])->save();
            return $ret_data;
        } else {
            $ret_data['error'] = 1;
            $ret_data['msg'] = '添加失败';
            fdump($ret_data,'api/log/'.date('Ymd').'/d3/add_delete_face_record_log',true);
            return $ret_data;
        }
    }

    /**
     * Notes: 同步所有用户信息
     * @param $device_id
     * @param int $village_id
     * @param string $floor_id
     * @param array $device_info
     * @param int $is_plan 是否为计划任务
     * @return bool
     * @author: wanzy
     * @date_time: 2020/9/8 9:25
     */
    public function sync_d3_face_all_per($device_id, $village_id=0, $floor_id='', $device_info=[],$is_plan=1) {
        if (empty($device_info)) {
            $device_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        }
        if (!$floor_id) {
            $floor_id = $device_info['floor_id'];
        }
        if (!$village_id && $device_info['village_id']) {
            $village_id = $device_info['village_id'];
        }
        // 不论是单个还是全部转换成 数组
        $where = [
            'village_id' => $village_id,
            'status' => 1,
            'type' => ['neq', 4]
        ];
        if ($floor_id!=-1 && $floor_id!=0) {
            $floor_arr =  explode(',', $floor_id);
            $where['floor_id'] = ['in', $floor_arr];
        }
        $db_house_village_user_bind = M('House_village_user_bind');
        $user_bind = $db_house_village_user_bind->where($where)->field('pigcms_id, single_id, floor_id, layer_id,vacancy_id,type, village_id, uid, property_endtime, property_starttime, name, parent_id,ic_card, id_card, user_number,bind_number')->order('parent_id desc,pigcms_id asc')->select();

        if ($user_bind) {
            $db_face_door_service = D('Face_door_service');
            foreach ($user_bind as $val) {
                if ($is_plan) {
                    $param = array();
                    $param['type'] = 'sync_d3_face_single_per';
                    $param['device_id'] = $device_id;
                    $param['village_id'] = $village_id;
                    $param['pigcms_id'] = $val['pigcms_id'];
                    $param['bind_info'] = $val;
                    $param['device_info'] = $device_info;
                    $param['unique_id'] = 'sync_d3_face_single_per_'.$param['village_id'].'_'.$device_id.'_'.$val['pigcms_id'];
                    $db_face_door_service->add_plan($param, 'sub_house_village_face_device',-10);
                } else {
                    $this->add_create_face_record($device_id, $val['pigcms_id'], $village_id, $val, [], $device_info);
                }
            }
        }
        return true;
    }

    /**
     * 获取当前需要执行的操作
     * @author liukezhu
     * @date 2021/4/15
     * @param $device_id
     * @param string $ipaddr
     */
    public function d3_get_request_record($device_id) {
        $db_face_d3_request_record = M('Face_d3_request_record');
        $where_record = [
            'device_id' => $device_id,
            'status' => 0,
            'is_implement'=>0
        ];
        $order = 'sort DESC, down_times ASC, add_time ASC, id ASC';
        $face_a7_request_record_info = $db_face_d3_request_record->order($order)->where($where_record)->find();
        if(!$face_a7_request_record_info){
            $data['version'] = $this->version;
            $data['cmd'] = '';
        }else{
            $face_a7_request_record_info['data'] = unserialize($face_a7_request_record_info['data']);
            // 每次查询添加次数，以防同一个下行卡主导致后续无法执行
            $db_face_d3_request_record->where(array('id'=>$face_a7_request_record_info['id']))->setField('is_implement',1);
            $db_face_d3_request_record->where(array('id'=>$face_a7_request_record_info['id']))->setInc('down_times',1);
            $data = $face_a7_request_record_info['data'];
        }
        echo json_encode($data,JSON_UNESCAPED_UNICODE);exit();
    }

}