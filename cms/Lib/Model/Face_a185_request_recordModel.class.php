<?php
/**
 * @author liukezhu
 */

class Face_a185_request_recordModel extends Model {

    public $version = 1;

    public $code_ = [
        0 => '请求已成功处理',
        1 => '请求已成功处理',
        2 => '请求已成功处理',
        3 => '请求已成功处理',
        4 => '认证失败',
    ];
    public $voice_base_url='http://tsn.baidu.com/text2audio?vol=15&tex=';
    public $voice_cuid ='&ctp=1&cuid=9B9A62EE-3EE8-45E0-9C06-2E3245AE3FF5';
    public $help_countdown=10;  // 帮助倒计时 单位s(秒)
    public $call_countdown=59;  // 呼叫倒计时 单位s(秒)  59
    public $tips_time=3;        // 提示存在时长 单位s(秒)
    public $notice_countdown=10;// 公告倒计时 单位s(秒)


    private function api_output_model($status = 0, $data = [], $msg = '', $http_code = 200){
        if(!$data){
            $data = [];
        }
        $output = [
            'status' => $status  == '0' ? 1000 : $status,
            'msg' 	 => $msg,
            'data' 	 => $data
        ];
        $http_json = json_encode($output,JSON_UNESCAPED_UNICODE);
        echo $http_json;exit();
    }

    //----------------------------应答模块--------------------------------------------------

    /**
     * 请求和应答处理
     * @author liukezhu
     * @date 2021/4/26
     * @param $arrData
     */
    public function a185_request_body($face_device,$arrData){
        // 设备序列号
        $device_id = $face_device['device_id'];
        if ($arrData && $face_device && isset($arrData['data']['cmd'])) {
            $d=$arrData['data'];
            if($d['cmd']){
                fdump_api($arrData,'a185/a185_request_body',1);
            }
            switch ($d['cmd']) {
                case "updateConfig":
                    //更新配置接口
                    $this->configAnswer($face_device,$d);
                    break;
                case "updateAdvert":
                    //更新广告
                    $this->advertAnswer($face_device,$d);
                    break;
                case "gpioControl":
                    //远程开门
                    $this->gpioAnswer($face_device,$d);
                    break;
                case "deviceReboot":
                    //设备重启
                    $this->rebootAnswer($face_device,$d);
                    break;
                case "addPerson":
                    //添加人员
                    $this->personAnswer($face_device,$d);
                    break;
                case "delPerson":
                    //删除人员
                    $this->deleteAnswer($face_device,$d);
                    break;
                case "addPwd":
                    //添加密码
                    $this->addPwdAnswer($face_device,$d);
                    break;
                case "delPwd":
                    //删除密码
                    $this->delPwdAnswer($face_device,$d);
                    break;
                case "appUpgrade":
                    //app更新
                    $this->appUpgradeAnswer($face_device,$d);
                    break;
                default:
                   $this->get_a185_request_record($device_id);
            }
        }
    }

    /**
     * 配置应答
     * @author liukezhu
     * @date 2021/4/27
     * @param $device
     * @param $post
     */
    private function configAnswer($device,$post){
        $db_face_a185_request_record = D('Face_a185_request_record');
        $cmd_name='updateConfig';
        fdump_api('配置应答>>'.__LINE__,'a185/a185_config_answer',1);
        $where_record = [
            'device_id' => $device['device_id'],
            'status' => 0,
            'id'=>$post['index'],
            'cmd_name' => $cmd_name
        ];
        fdump_api('配置应答>>'.$post,'a185/a185_config_answer',1);
        if (1000==$post['code']) {
            // 反馈成功结果
            $data_recod = [
                'status' => 1,
                'is_implement' => 2,
                'sucess_time' => time()
            ];
//            $id = $db_face_a185_request_record->where()
            $db_face_a185_request_record->where($where_record)->data($data_recod)->save();
            $db_house_face_device = M('House_face_device');
            $data['login_time'] = time();
            $data['notify_time'] = time();
            $db_house_face_device->where(array('device_id' => $device['device_id']))->data($data)->save();
        }
        else {
            $remark = '更新配置失败,code: '.$post['code'] . ',reply: '.$post['reply'];
            // 反馈失败结果
            $data_recod = [
                'status' => 2,
                'is_implement' => 2,
                'remark' => $remark
            ];
            $db_face_a185_request_record->where($where_record)->data($data_recod)->save();
        }
        $this->api_output_model(1000,[],'ok');
    }

    /**
     * 广告应答
     * @author liukezhu
     * @date 2021/4/26
     * @param $device
     * @param $post
     */
    private function advertAnswer($device,$post){
        $db_face_a185_request_record = D('Face_a185_request_record');
        $cmd_name='updateAdvert';
        $where_record = [
            'device_id' => $device['device_id'],
            'status' => 0,
            'id'=>$post['index'],
            'cmd_name' => $cmd_name
        ];
        fdump_api('广告应答>>'.__LINE__,'a185/a185_advert_answer',1);
        if (1000==$post['code']) {
            // 反馈成功结果
            $data_recod = [
                'status' => 1,
                'is_implement' => 2,
                'sucess_time' => time()
            ];
            $db_face_a185_request_record->where($where_record)->data($data_recod)->save();
        }
        else {
            $remark = '更新广告失败,code: '.$post['code'] . ',reply: '.$post['reply'];
            // 反馈失败结果
            $data_recod = [
                'status' => 2,
                'is_implement' => 2,
                'remark' => $remark
            ];
            $db_face_a185_request_record->where($where_record)->data($data_recod)->save();
        }
        $this->api_output_model(1000,[],'ok');
    }

    /**
     * 重启应答
     * @author liukezhu
     * @date 2021/4/26
     * @param $device
     * @param $post
     *
     */
    private function rebootAnswer($device,$post){
        $db_face_a185_request_record = D('Face_a185_request_record');
        $cmd_name='deviceReboot';
        fdump_api('重启应答>>'.__LINE__,'a185/a185_reboot_answer',1);
        $where_record = [
            'device_id' => $device['device_id'],
            'status' => 0,
            'id'=>$post['index'],
            'cmd_name' => $cmd_name
        ];
        if (1000 == $post['code']) {
            // 重启成功反馈结果
            $data_recod = [
                'status' => 1,
                'is_implement' => 2,
                'sucess_time' => time()
            ];
            $db_face_a185_request_record->where($where_record)->data($data_recod)->save();
        } else {
            // 重启失败反馈结果
            $remark = '重启失败,code: '.$post['code'] . ',reply: '.$post['reply'];
            $data_recod = [
                'status' => 2,
                'is_implement' => 2,
                'remark' => $remark
            ];
            $db_face_a185_request_record->where($where_record)->data($data_recod)->save();
        }
        $this->api_output_model(1000,[],'ok');
    }

    /**
     * 远程开门应答
     * @author liukezhu
     * @date 2021/4/27
     * @param $device
     * @param $post
     */
    private function gpioAnswer($device, $post) {
        $db_face_a185_request_record = D('Face_a185_request_record');
        $cmd_name='gpioControl';
        fdump_api(['远程开门应答>>'.__LINE__,$post],'a185/a185_gpio_answer',1);
        $where_record = [
            'device_id' => $device['device_id'],
            'is_implement' => 1,
            'status' => 0,
            'id'=>$post['index'],
            'cmd_name' => $cmd_name,
        ];
        $info = $db_face_a185_request_record->where($where_record)->find();
        if (1000 == $post['code'] && $info) {
            // 反馈成功结果
            $data_recod = [
                'status' => 1,
                'is_implement' => 2,
                'sucess_time' => time()
            ];
            $db_face_a185_request_record->where($where_record)->data($data_recod)->save();
            // 设备序列号
            $db_house_face_device = M('House_face_device');
            $d_house_user_log = M('House_user_log');
            $d_house_village_floor = M('House_village_floor');
            $d_house_village = M('House_village');
            $d_house_village_user_bind = D('House_village_user_bind');
            $d_house_camera_tip = M('House_camera_tip');
            $where = [
                'device_id' => $device['device_id'],
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
            if(intval($info['bind_id']) > 0){
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
                if (isset($post['face_img']) && trim($post['face_img'])) {
                    $log['cut_image'] = trim($post['face_img']);
                } elseif (isset($post['mediaUrls']) && trim($post['imgUrl'])) {
                    $log['cut_image'] = trim($post['imgUrl']);
                } elseif (isset($post['pictureURL']) && trim($post['pictureURL'])) {
                    $log['cut_image'] = trim($post['pictureURL']);
                } elseif (isset($post['faceCapture']) && isset($post['faceCapture'][0]['bkgUrl']) && trim($post['faceCapture'][0]['bkgUrl'])) {
                    $log['cut_image'] = trim($post['faceCapture'][0]['bkgUrl']);
                } elseif (isset($post['face_img_url']) && trim($post['face_img_url'])) {
                    $log['cut_image'] = trim($post['face_img_url']);
                } elseif (isset($post['capture_img_url']) && trim($post['capture_img_url'])) {
                    $log['cut_image'] = trim($post['capture_img_url']);
                } elseif (isset($post['card_data_picture_url']) && trim($post['card_data_picture_url'])) {
                    $log['cut_image'] = trim($post['card_data_picture_url']);
                }
                $log['log_detail'] = serialize($post);
                $log['add_time']=time();
                $rr=$d_house_user_log->data($log)->add();
                fdump_api(['开门应答二>>'.__LINE__,$rr],'a185/a185_gpio_answer',1);
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
                //todo 轨迹信息 a185远程开门
                D('House_user_trajectory_log')->addLog(4,$info['village_id'],0,$uid,$info['bind_id'],'a185-device_remote_open','A185智能门禁远程开门','远程开门应答');
            }
        }
        else {
            $remark = '远程开门失败,code: '.$post['code'] . ',reply: '.$post['reply'];
            // 反馈失败结果
            $data_recod = [
                'status' => 2,
                'is_implement' => 2,
                'remark' => $remark
            ];
            $db_face_a185_request_record->where($where_record)->data($data_recod)->save();
        }
        $this->api_output_model(1000,[],'ok');
    }

    /**
     * 添加、编辑人员应答
     * @author liukezhu
     * @date 2021/4/27
     * @param $device
     * @param $post
     */
    private function personAnswer($device, $post) {
        $db_face_a185_request_record = D('Face_a185_request_record');
        $db_house_village_user_bind = D('House_village_user_bind');
        fdump_api('人员添加或编辑应答>>'.__LINE__,'a185/a185_person_answer',1);
        $cmd_name='addPerson';
        // 查出当前执行命令
        $where_record = [
            'device_id' => $device['device_id'],
            'is_implement' => 1,
            'status' => 0,
            'id'=>$post['index'],
            'cmd_name' => $cmd_name,
        ];
        $info = $db_face_a185_request_record->where($where_record)->find();
        if (1000 == $post['code'] && $info) {
            // 反馈成功结果
            $data_recod = [
                'status' => 1,
                'is_implement' => 2,
                'sucess_time' => time()
            ];
            $db_face_a185_request_record->where($where_record)->data($data_recod)->save();
            if($info['log_id']){
                M('House_face_sync_log')->where(['log_id'=>$info['log_id']])->save([
                    'status'=>1,
                    'msg'=>'',
                    'last_time'=>time()
                ]);
            }
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
                    'device_type' => 61,
                    'device_id' => $device['device_id'],
                ];
                $bind_user = $d_face_user_bind_device->where($where_face_bind_about)->find();

                $uid=$db_house_village_user_bind->where(['pigcms_id' => $info['bind_id']])->getField('uid');
                //todo 轨迹信息 a185添加人脸
                D('House_user_trajectory_log')->addLog(4,$info['village_id'],0,$uid,$info['bind_id'],'a185-device_face_add','A185智能门禁录入人脸信息','添加、编辑人员应答');
                if (!$bind_user) {
                    $data_face_bind_about = [
                        'bind_type' => 0,
                        'bind_id' => $info['bind_id'],
                        'device_type' => 61,
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
        }
        else {
            $remark = '人员添加或编辑失败,code: '.$post['code'] . ',reply: '.$post['reply'];
            $data_recod = [
                'status' => 2,
                'is_implement' => 2,
                'remark' => $remark
            ];
            $db_face_a185_request_record->where($where_record)->data($data_recod)->save();
            // 人脸记录失败
            if ($info['bind_id']) {
                $data_set = [
                    'face_img_status' => 2,
                    'face_img_reason' => $remark,
                ];
                $db_house_village_user_bind->where(['pigcms_id' => $info['bind_id']])->data($data_set)->save();
            }
            if($info['log_id']){
                M('House_face_sync_log')->where(['log_id'=>$info['log_id']])->save([
                    'status'=>2,
                    'msg'=>$remark,
                    'last_time'=>time()
                ]);
            }
        }
        $this->api_output_model(1000,[],'ok');
    }

    /**
     * 删除应答
     * @author liukezhu
     * @date 2021/4/27
     * @param $device
     * @param $post
     *
     */
    private function deleteAnswer($device, $post) {
        $db_face_a185_request_record = D('Face_a185_request_record');
        fdump_api('人员删除应答>>'.__LINE__,'a185/a185_delete_answer',1);
        $cmd_name='delPerson';
        $where_record = [
            'device_id' => $device['device_id'],
            'is_implement' => 1,
            'status' => 0,
            'id'=>$post['index'],
            'cmd_name' =>$cmd_name,
        ];
        $info = $db_face_a185_request_record->where($where_record)->find();
        if (1000 == $post['code'] && $info) {
            // 反馈成功结果
            $data_recod = [
                'status' => 1,
                'is_implement' => 2,
                'sucess_time' => time()
            ];
            $db_face_a185_request_record->where($where_record)->data($data_recod)->save();
            $d_face_user_bind_device = M('Face_user_bind_device');
            $where_face_bind_about = [
                'bind_type' => 0,
                'bind_id' => $info['bind_id'],
                'device_type' => 61,
                'device_id' => $device['device_id'],
            ];
            // 删除关联关系
            $d_face_user_bind_device->where($where_face_bind_about)->delete();
        } else {
            $remark = '删除失败,code: '.$post['code'] . ',reply: '.$post['reply'];
            // 反馈失败结果
            $data_recod = [
                'status' => 2,
                'is_implement' => 2,
                'remark' => $remark
            ];
            $db_face_a185_request_record->where($where_record)->data($data_recod)->save();
        }
        $this->api_output_model(1000,[],'ok');
    }

    /**
     * 添加密码
     * @author liukezhu
     * @date 2021/4/27
     * @param $device
     * @param $post
     */
    private function addPwdAnswer($device,$post){
        $db_face_a185_request_record = D('Face_a185_request_record');
        $cmd_name='addPwd';
        fdump_api('添加密码应答>>'.__LINE__,'a185/addPwdAnswer',1);
        $where_record = [
            'device_id' => $device['device_id'],
            'status' => 0,
            'id'=>$post['index'],
            'cmd_name' => $cmd_name
        ];
        if (1000 == $post['code']) {
            // 成功反馈结果
            $data_recod = [
                'status' => 1,
                'is_implement' => 2,
                'sucess_time' => time()
            ];
            $db_face_a185_request_record->where($where_record)->data($data_recod)->save();
        } else {
            // 反馈结果
            $remark = '添加密码失败,code: '.$post['code'] . ',reply: '.$post['reply'];
            $data_recod = [
                'status' => 2,
                'is_implement' => 2,
                'remark' => $remark
            ];
            $db_face_a185_request_record->where($where_record)->data($data_recod)->save();
        }
        $this->api_output_model(1000,[],'ok');
    }

    /**
     * 删除密码
     * @author liukezhu
     * @date 2021/4/27
     * @param $device
     * @param $post
     */
    private function delPwdAnswer($device,$post){
        $db_face_a185_request_record = D('Face_a185_request_record');
        $cmd_name='delPwd';
        fdump_api('删除密码应答>>'.__LINE__,'a185/addPwdAnswer',1);
        $where_record = [
            'device_id' => $device['device_id'],
            'status' => 0,
            'id'=>$post['index'],
            'cmd_name' => $cmd_name
        ];
        if (1000 == $post['code']) {
            // 成功反馈结果
            $data_recod = [
                'status' => 1,
                'is_implement' => 2,
                'sucess_time' => time()
            ];
            $db_face_a185_request_record->where($where_record)->data($data_recod)->save();
        } else {
            // 反馈结果
            $remark = '删除密码失败,code: '.$post['code'] . ',reply: '.$post['reply'];
            $data_recod = [
                'status' => 2,
                'is_implement' => 2,
                'remark' => $remark
            ];
            $db_face_a185_request_record->where($where_record)->data($data_recod)->save();
        }
        $this->api_output_model(1000,[],'ok');
    }

    //todo app更新成功应答
    private function appUpgradeAnswer($device,$post){
        $db_face_a185_request_record = D('Face_a185_request_record');
        $cmd_name='appUpgrade';
        fdump_api('更新应答>>'.__LINE__,'a185/a185_appUpgrade_answer',1);
        $where_record = [
            'device_id' => $device['device_id'],
            'status' => 0,
            'id'=>$post['index'],
            'cmd_name' => $cmd_name
        ];
        if (1000 == $post['code']) {
            // 重启成功反馈结果
            $data_recod = [
                'status' => 1,
                'is_implement' => 2,
                'sucess_time' => time()
            ];
            $db_face_a185_request_record->where($where_record)->data($data_recod)->save();
        } else {
            // 重启失败反馈结果
            $remark = '更新失败,code: '.$post['code'] . ',reply: '.$post['reply'];
            $data_recod = [
                'status' => 2,
                'is_implement' => 2,
                'remark' => $remark
            ];
            $db_face_a185_request_record->where($where_record)->data($data_recod)->save();
        }
        $this->api_output_model(1000,[],'ok');
    }

    /**
     * 获取当前需要执行的操作
     * @author liukezhu
     * @date 2021/4/15
     * @param $device_id
     * @param string $ipaddr
     */
    public function get_a185_request_record($device_id) {
        $db_face_a185_request_record = M('Face_a185_request_record');
        $where_record = [
            'device_id' => $device_id,
            'status' => 0,
            'is_implement'=>0
        ];
        $order = 'sort DESC, down_times ASC, add_time ASC, id ASC';
        $face_a7_request_record_info = $db_face_a185_request_record->order($order)->where($where_record)->find();
        fdump($db_face_a185_request_record->_sql(),'$db_face_a185_request_record');
        if(!$face_a7_request_record_info){
            $data=array(
                'cmd'=>'',
                'version'=>$this->version,
                'code'=>1000,
                'msg'=>'暂无命令',
                'index'=>0,
                'data'=>[]
            );
        }else{
            $face_a7_request_record_info['data'] = unserialize($face_a7_request_record_info['data']);
            // 每次查询添加次数，以防同一个下行卡主导致后续无法执行
            $db_face_a185_request_record->where(array('id'=>$face_a7_request_record_info['id']))->setField('is_implement',1);
            $db_face_a185_request_record->where(array('id'=>$face_a7_request_record_info['id']))->setInc('down_times',1);
            $data = $face_a7_request_record_info['data'];
        }
        $this->api_output_model(1000,$data,'');
    }



    //----------------------------------人脸识别开门-------------------------------------

    /**
     * 刷脸开门、刷卡开门
     * @author: liukezhu
     * @date : 2021/4/27
     * @param $face_device
     * @param $post
     * @return bool
     */
    public function a185_face_notify($face_device,$post) {
        if(!isset($post['data']['data']) && empty($post['data']['data'])){
            fdump_api(['参数记录>>'.__LINE__,$face_device,$post],'a185/a185_face_notify',1);
            return false;
        }
        $d=$post['data']['data'];
        $log_from=0;
        if($d['face_type'] == 'FaceAlignment'){
            //刷脸开门
            $log_from=1;
        }
        elseif ($d['face_type'] == 'FaceCard'){
            //IC卡开门
            $log_from=3;
        }
        elseif ($d['face_type'] == 'FaceStranger'){
            fdump($post,'api/log/'.date('Ymd').'/a185/a185_face_FaceStranger',true);
        }
        $msg=($log_from == 1 ? '刷脸开门' : 'IC卡开门');
        fdump_api(['人脸识别开门>>'.__LINE__,'type'=>$msg,'log_from'=>$log_from,'post'=>$post],'a185/a185_face_notify',1);
        if($log_from > 0){
            // 设备序列号
            $db_house_face_device = M('House_face_device');
            $d_house_user_log = M('House_user_log');
            $d_house_village_floor = M('House_village_floor');
            $d_house_village = M('House_village');
            $d_house_village_user_bind = D('House_village_user_bind');
            $d_house_camera_tip = M('House_camera_tip');
            $house_village_info = $d_house_village->where(array('village_id' => $face_device['village_id']))->find();
            $person_id=intval($d['person_id']);
            $uid=0;
            if($person_id > 0){
                $where_bind = [
                    'pigcms_id' => $d['person_id']
                ];
                $house_village_bind = $d_house_village_user_bind->where($where_bind)->field('uid')->find();
                if(!$house_village_bind){
                    fdump_api(['人员数据>>'.__LINE__,$d_house_village_user_bind->_sql()],'a185/a185_face_notify',1);
                    return false;
                }
                $uid = $house_village_bind['uid'];
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
            $log = array();
            $face_img = '';
            if(isset($d['catch_img']) && !empty($d['catch_img'])){
                $face_imgdata = $this->base64_to_img($d['catch_img']);
                fdump($face_imgdata,'api/log/'.date('Ymd').'/a185/a185_catch_img',true);
                if ($face_imgdata) {
                    $last_result['face_imgdata'] = $face_imgdata;
                    $face_img = $face_imgdata;
                    $d['face_img'] = $face_imgdata;
                    unset($d['catch_img']);
                }
            }
            $face_status=0; //识别成功
            if(!isset($d['face_status']) || !$d['face_status']){
                $face_status=1; //识别失败
            }
            $log['uid'] = $uid ? $uid : 0;
            $log['log_bind_id'] = $person_id;
            $log['log_name'] = $log_name;
            $log['log_from'] = $log_from; // 人脸识别门禁
            $log['log_business_id'] = $face_device['village_id']; // 社区id
            $log['log_more_id'] = $face_device['floor_id']; // 单元id
            $log['device_id'] = $face_device['device_id']; // 设备id
            $log['log_time'] = time(); // 记录时间
            // 0 比对通过，1 比对失败， 2 无模版， 3 无效卡 4 无现场照，5 非活体， 6 证件过期 7 超过时间限制， 8 超过此数限制 100 未知错误
            $log['log_status']=$face_status;
            $log['temperature'] = '0.0';

            if (isset($d['face_img']) && trim($d['face_img'])) {
                $log['cut_image'] = trim($d['face_img']);
            } elseif (isset($d['mediaUrls']) && trim($d['imgUrl'])) {
                $log['cut_image'] = trim($d['imgUrl']);
            } elseif (isset($d['pictureURL']) && trim($d['pictureURL'])) {
                $log['cut_image'] = trim($d['pictureURL']);
            } elseif (isset($d['faceCapture']) && isset($d['faceCapture'][0]['bkgUrl']) && trim($d['faceCapture'][0]['bkgUrl'])) {
                $log['cut_image'] = trim($d['faceCapture'][0]['bkgUrl']);
            } elseif (isset($d['face_img_url']) && trim($d['face_img_url'])) {
                $log['cut_image'] = trim($d['face_img_url']);
            } elseif (isset($d['capture_img_url']) && trim($d['capture_img_url'])) {
                $log['cut_image'] = trim($d['capture_img_url']);
            } elseif (isset($d['card_data_picture_url']) && trim($d['card_data_picture_url'])) {
                $log['cut_image'] = trim($d['card_data_picture_url']);
            }
            $log['log_detail'] = serialize($d);
            $log['add_time']=time();
            $d_house_user_log->data($log)->add();
            $db_house_face_device->where(array('device_id' => $face_device['device_id']))->setField('last_open_time', time());
            // 同步记录到消息列表
            $tip = array(
                'bind_id' =>  $person_id,
                'uid' => $uid,
                'type' => 1,
                'village_id' => $face_device['village_id'],
                'camera_sn' => $face_device['device_sn'],
                'city_id' => $city_id,
                'camera_time' => time(),
                'camera_img' => $face_img,
                'tip_time' => time(),
                'status' => $face_status,
                'tip_txt' => serialize($post),
                'temperature'=> '0.0'
            );
            $d_house_camera_tip->data($tip)->add();

            //todo 轨迹信息 a185刷脸开门、刷卡开门
            $type='a185-device_card_open';
            if($log_from == 1){
                $type='a185-device_face_open';
            }
            D('House_user_trajectory_log')->addLog(4,$face_device['village_id'],0,$uid,$log['log_bind_id'],$type,'A185智能门禁'.$msg,$msg);
        }
       return true;
    }


    //同步修改状态
    public function a185_face_report($pigcms_id,$status,$msg=''){
        $data_set = [
            'face_img_status' => $status,
            'face_img_reason' =>$msg,
        ];
       return D('House_village_user_bind')->where(['pigcms_id' => $pigcms_id])->data($data_set)->save();
    }


    //----------------------------------发起命令模块---------------------------------------

    public function voice_baidu()
    {
        static $return;

        if (empty($return)) {
            $voice_baidu = "https://openapi.baidu.com/oauth/2.0/token?grant_type=client_credentials&client_id=XU7nTWQzS9vn32bckmrhHbTu&client_secret=1375720f23eed9b7b787e728de5dd1c2";
            import('ORG.Net.Http');
            $return = Http::curlGet($voice_baidu);
        }
        return $return;
    }

    /**
     * 获取设备基础参数
     * @author liukezhu
     * @date 2021/4/25
     * @param $device_sn
     * @return array
     */
    public function get_a185_config($device_sn,$device_info=[],$config_){
        if(empty($device_info)){
            $where_device = [
                'device_sn' => $device_sn,
                'device_type'=>61,
                'is_del'=>0
            ];
            $device_info = M('House_face_device')->where($where_device)->find();
            if (empty($device_info)) {
                return [];
            }
        }
        $where_village = [
            'village_id' => $device_info['village_id']
        ];
        $village_info = M('House_village')->where($where_village)->field('village_name,owe_property_open_door,property_phone')->find();

        $config_info = M('House_village_about_config')->where(['village_id'=>$device_info['village_id']])->find();

        if(empty($config_info) || empty($config_info['a185_open_succ'])){
            $config_info['a185_open_succ']='开门成功';
        }
        if(empty($config_info) || empty($config_info['a185_open_fail'])){
            $config_info['a185_open_fail']='开门失败';
        }
        $addr = '';
        if ($device_info['floor_id'] != "-1") {
            $device_type = 1;
            $floor_arr = explode(',',$device_info['floor_id']);
            $check_msg = '/[\x7f-\xff]/';
            if (count($floor_arr)==1) {
                $where_floor = [
                    'a.floor_id' => $floor_arr[0]
                ];
                $floor_info = M('House_village_floor')
                    ->join('as a LEFT JOIN '.C('DB_PREFIX')."house_village_single as b ON a.single_id = b.id")
                    ->field('a.floor_layer,a.floor_name, b.single_name')
                    ->group('a.floor_id')
                    ->where($where_floor)
                    ->find();
                if ($floor_info) {
                    if(preg_match($check_msg, $floor_info['floor_name'])) {
                        $floor_name = $floor_info['floor_name'];
                    }else{
                        $floor_name = $floor_info['floor_name'].'单元';
                    }
                    $single_name = $floor_info['single_name']?$floor_info['single_name']:$floor_info['floor_layer'];
                    if(!preg_match($check_msg, $single_name)) {
                        $single_name = $single_name.(D('House_village')->getHouseAlias('single_name')?:'栋');
                    }
                    $addr .= $single_name . $floor_name;
                }
            } else {
                $where_floor = [
                    'a.floor_id' => ['in', $floor_arr]
                ];
                $floor_list = M('House_village_floor')
                    ->join('as a LEFT JOIN '.C('DB_PREFIX')."house_village_single as b ON a.single_id = b.id")
                    ->field('a.floor_layer,a.floor_name, b.single_name')
                    ->group('a.floor_id')
                    ->where($where_floor)
                    ->select();
                if (!empty($floor_list)) {
                    $addr .='(';
                    foreach ($floor_list as $floor_info) {
                        if(preg_match($check_msg, $floor_info['floor_name'])) {
                            $floor_name = $floor_info['floor_name'];
                        }else{
                            $floor_name = $floor_info['floor_name'].'单元';
                        }
                        $single_name = $floor_info['single_name']?$floor_info['single_name']:$floor_info['floor_layer'];
                        if(!preg_match($check_msg, $single_name)) {
                            $single_name = $single_name.(D('House_village')->getHouseAlias('single_name')?:'栋');
                        }
                        $addr .= $single_name . $floor_name;
                    }
                    $addr .=')';
                }
            }
        }
        elseif ($device_info['public_area_id']) {
            $device_type = 2;
            $where_area = [
                'public_area_id' => $device_info['public_area_id']
            ];
            $public_area_name = M('House_village_public_area')->where($where_area)->getField('public_area_name');
            $addr .= $public_area_name;
        }
        $config = [];
        // 设备相关信息
        $config['device_address'] = $village_info['village_name'].$addr;
        // 是否开启活体检测  0 不开启  1开启
        $config['device_alive'] = $device_info['device_alive'];
        // 人脸识别阀值 0到100，越高越准确，也越可能出错。建议80左右。
        $config['device_score'] = $device_info['device_score'] ? $device_info['device_score'] : 80;
        // 门禁机类型 1 单元机  2 围墙机
        $config['device_type'] = $device_type ? $device_type : 1;
        $config['a185_close_time'] = intval($device_info['a185_close_time']);
        //todo 参数配置开关
        $sdkappid=trim(D("Config")->get_config_by_name("sdkappid_facedoor_a185")['value']);
        $config['sdkappid'] =  $sdkappid ? $sdkappid : '';
        //todo 物业联系方式
        $property_phone = $village_info['property_phone'] ? '<p style="color: black">物业联系方式：'.$village_info['property_phone'].'。</p>' : '';

        $config['help_txt'] = '<p style="color: black">
            1、支持使用人脸直接刷脸开门，若没有人脸，可联系物业进行上传，
        </p>'.$property_phone.'
        <p style="color: black">
            2、支持IC卡刷卡开门，若没有IC卡，可联系物业进行办理。
        </p>
        <p style="color: black">
            3、可视对讲支持输入对方的手机号进行呼叫，输入后，点击确定即可。
        </p>
        <p style="color: black">
            4、如果是单元门禁机,呼叫业主直接输入楼层房间即可，例1层101房间输入1101。
        </p>
        <p style="color: black">
            5、如果是大门门禁机,呼叫业主则需要输入楼栋#单元#楼层#房间号，例1栋1单元1层101输入1#1#1#101。
        </p>';
        $config['notice_countdown'] = $this->notice_countdown;
        $config['help_countdown'] = $this->help_countdown;
        $config['call_countdown'] = $this->call_countdown;
        $config['tips_time'] = $this->tips_time;
        // 超级密码
        $config['super_password'] = $device_info['a185_super_password'];
        // 管理密码  6位  每个设备一个
        $config['admin_password'] =$device_info['a185_admin_password'];
        // 设备维护 即每日定时重启 支持多个时段 为空 即 无需重启 只有时分
        $equipment_maintenance = [
            [
                'is_restart'=> empty($device_info['a185_restart_time']) ? 0 :1,
                'restart_time' => $device_info['a185_restart_time']
            ]
        ];
        $config['equipment_maintenance'] = $equipment_maintenance;

        $tele_card=unserialize($device_info['a185_tele_card']);
        $config['tele_card'] =array(
            'is_tele_card'=>(isset($tele_card['is_tele_card']) && !empty($tele_card['tele'])) ? $tele_card['is_tele_card'] : 0,
            'tele'=>(isset($tele_card['is_tele_card']) && !empty($tele_card['tele'])) ? $tele_card['tele'] : '',
        );

        // 提示语部分
        // 音量时段列表  0-100
        $volume_list=[];
        if($device_info['a185_volume']){
            $a185_volume=unserialize($device_info['a185_volume']);
            $volume_list = array(
                'day'=>array(
                    'start_time' => $a185_volume['day']['time'][0],
                    'end_time' => $a185_volume['day']['time'][1],
                    'volume' =>empty($a185_volume['day']['size']) ? 0 : $a185_volume['day']['size']
                ),
                'night'=>array(
                    'start_time' => $a185_volume['night']['time'][0],
                    'end_time' => $a185_volume['night']['time'][1],
                    'volume' =>empty($a185_volume['night']['size']) ? 0 : $a185_volume['night']['size']
                )
            );
        }

        $config['volume_list'] = $volume_list;
        $config['open_success_tips'] = $config_info['a185_open_succ'];

        $voice_return = json_decode($this->voice_baidu(), true);
        $voice_access_token = $voice_return['access_token'];
        $voice_open_success_mp3 = $this->voice_base_url . urlencode($config_info['a185_open_succ']) . '&lan=zh&tok=' . $voice_access_token . $this->voice_cuid .'&success.mp3';
        $config['open_success_voice'] = $voice_open_success_mp3;

        $config['open_fail_tips'] = $config_info['a185_open_fail'];
        if (!$voice_access_token) {
            $voice_return = json_decode($this->voice_baidu(), true);
            $voice_access_token = $voice_return['access_token'];
        }
        $voice_open_fail_mp3 = $this->voice_base_url . urlencode($config_info['a185_open_fail']) . '&lan=zh&tok=' . $voice_access_token . $this->voice_cuid.'&fail.mp3';
        $config['open_fail_voice'] = $voice_open_fail_mp3;

        $config['property_tip_day'] = 15; // 注意小于等于0的时候不生效 物业欠费提示界限天数  即到物业费到期15天的时候提示下面一句 %d 替换成对应时间倒数时间
        $config['property_pay_tip'] = '您的物业费还有%d天到期，请及时缴费';
        // 欠费开门失败提示

        $config['property_end_tip'] = '开门失败，您已欠物业费，请及时缴费';
        if (!$voice_access_token) {
            $voice_return = json_decode($this->voice_baidu(), true);
            $voice_access_token = $voice_return['access_token'];
        }
        $property_end_voice = $this->voice_base_url . urlencode($config['property_end_tip']) . '&lan=zh&tok=' . $voice_access_token . $this->voice_cuid.'&end.mp3';
        $config['property_pay_voice']=$this->voice_base_url . urlencode('您的物业费即将到期，请及时缴费') . '&lan=zh&tok=' . $voice_access_token . $this->voice_cuid.'&end.mp3';
        $config['property_end_voice'] = $property_end_voice;


        $config['is_open']=intval($village_info['owe_property_open_door']);

        // 返回云平台参数
        $cloud_platform = [];
        // 域名信息
        $content = [];
        $child = [
            'title' => '网站',
            'content' =>$config_['site_name']
        ];
        $content[] = $child;
        $child = [
            'title' => '网址',
            'content' =>$config_['site_url']
        ];
        $content[] = $child;
        $child = [
            'title' => '配置接口',
            'content' => '/appapi.php?c=Face_door185&a=getConfig'
        ];
        $content[] = $child;
        $child = [
            'title' => '广告接口',
            'content' => '/appapi.php?c=Face_door185&a=getAdvertList'
        ];
        $content[] = $child;
        $child = [
            'title' => '下行接口',
            'content' => '/appapi.php?c=Face_door185&a=download'
        ];
        $content[] = $child;

        $cloud_block = [
            'title' => '域名信息',
            'child' => $content
        ];
        $cloud_platform[] = $cloud_block;
        // 展示时间
        $content = [];
        $child = [
            'title' => '公告',
            'content' => $this->notice_countdown . 's'
        ];
        $content[] = $child;
        $child = [
            'title' => '帮助',
            'content' => $this->help_countdown . 's'
        ];
        $content[] = $child;
        $child = [
            'title' => '呼叫',
            'content' => $this->call_countdown . 's'
        ];
        $content[] = $child;
        $child = [
            'title' => '提示',
            'content' => $this->tips_time . 's'
        ];
        $content[] = $child;

        $cloud_block = [
            'title' => '展示时间',
            'child' => $content
        ];
        $cloud_platform[] = $cloud_block;
        // 重启时间
        if (!empty($equipment_maintenance)) {
            $content = [];
            foreach ($equipment_maintenance as $val) {
                if ($val && $val['restart_time']) {
                    $restart_time = str_replace('-',':',$val['restart_time']);
                    $child = [
                        'title' => '每日',
                        'content' => $restart_time
                    ];
                    $content[] = $child;
                }
            }
            if (!empty($content)) {
                $cloud_block = [
                    'title' => '重启时间',
                    'child' => $content
                ];
                $cloud_platform[] = $cloud_block;
            }
        }
        // 音量控制
        if (!empty($volume_list)) {
            $content = [];
            foreach ($volume_list as $val1) {
                if ($val1 && $val1['start_time'] && $val1['end_time']) {
                    $start_time = str_replace('-',':',$val1['start_time']);
                    $end_time = str_replace('-',':',$val1['end_time']);
                    $child = [
                        'title' => $start_time.'-'.$end_time,
                        'content' => intval($val1['volume'])
                    ];
                    $content[] = $child;
                }
            }
            if (!empty($content)) {
                $cloud_block = [
                    'title' => '音量控制',
                    'child' => $content
                ];
                $cloud_platform[] = $cloud_block;
            }
        }
        $config['cloud_platform'] = $cloud_platform;
        return $config;
    }

    /**
     * 新增设备or编辑设备 更新配置接口
     * @author liukezhu
     * @date 2021/4/25
     * @param int $device_id 设备id
     * @param array $data 需要添加的相关数据
     * @param int $village_id 小区id
     * @param array $device_info 设备信息-选传
     * @return array
     */
    public function updateConfig($device_id,$device_info,$config) {
        // 具体请求区分名称
        $cmd_name = 'updateConfig';
        $ret_data = [
            'error' => 0,
            'msg' => ''
        ];
        $where_repeat = [
            'device_id' => $device_id,
            'cmd_name' => $cmd_name,
            'status' => 0,
        ];
        $repeat_id = M('Face_a185_request_record')->where($where_repeat)->getField('id');
        if ($repeat_id) {
            // 直接将之前未执行的命令改为已执行
            M('Face_a185_request_record')->where(['id' => $repeat_id])->data(['status' => 1, 'update_time' => time()])->save();
        }
        $device_sn = $device_info['device_sn'];
        $operator_type = isset($device_info['operator_type']) ? intval($device_info['operator_type']) : 1;
        $operator_id = isset($device_info['operator_id']) ? intval($device_info['operator_id']) : $device_info['village_id'];
        $version = isset($device_info['version']) ? intval($device_info['version']) : $this->version;
        $status = 0;
        $add_time = time();

        // 整合需要添加的数据
        $add_data = [
            'village_id' => $device_info['village_id'],
            'device_sn' => $device_sn,
            'device_id' => $device_id,
            'operator_type' => $operator_type,
            'operator_id' => $operator_id,
            'cmd_name' => $cmd_name,
            'version' => $version,
            'status' => $status,
            'add_time' => $add_time,
            'sort' => 99
        ];
        $add_id = $this->data($add_data)->add();
        if ($add_id) {
            $this->where(['id' => $add_id])->data([
                'data'=>serialize(array(
                    'cmd'=>$cmd_name,
                    'version'=>$this->version,
                    'index'=>$add_id,
                    'data'=>$this->get_a185_config($device_info['device_sn'],[],$config)
                ))
            ])->save();
            $ret_data['data'] = $add_id;
            return $ret_data;
        } else {
            fdump(['sql'=>$this->data($add_data)->_sql(),'data'=>$device_info],'api/log/'.date('Ymd').'/a185/updateConfig',true);
            $ret_data['error'] = 1;
            $ret_data['msg'] = '添加失败';
            return $ret_data;
        }
    }

    /**
     * 获取设备广告
     * @author liukezhu
     * @date 2021/4/25
     */
    public function getAdvert($device){
        $where = array(
            'b.equipment_type'=>$device['device_type'],
            'a.village_id'=>$device['village_id'],
            'a.status'=>1,
            'b.status'=>1,
            'b.begin_time'=>array('lt',time()),
            'b.end_time'=>array('gt',time())
        );
        $list = M('Adver_village')
            ->join('as a LEFT JOIN '.C('DB_PREFIX')."house_open_door_adver as b ON a.adver_id = b.id")
            ->field('b.name,b.a185_url,b.play_time,b.advert_type')
            ->where($where)
            ->select();
        if($list){
            foreach ($list as &$v){
                $v['a185_url']=unserialize($v['a185_url']);
                if(isset($v['a185_url']['android_pic'])){
                    $v['a185_url']['android_pic']=replace_file_domain($v['a185_url']['android_pic']);
                }
                if(isset($v['a185_url']['android_video'])){
                    $v['a185_url']['android_video']=replace_file_domain($v['a185_url']['android_video']);
                }
            }
            unset($v);
        }
        return $list;
    }

    /**
     * 重新获取广告
     * @author liukezhu
     * @date 2021/4/25
     * @param $device_id
     * @param $device_info
     */
    public function updateAdvert($device_id,$device_info){
        // 具体请求区分名称
        $cmd_name = 'updateAdvert';
        $ret_data = [
            'error' => 0,
            'msg' => ''
        ];
        $where_repeat = [
            'device_id' => $device_id,
            'cmd_name' => $cmd_name,
            'status' => 0,
        ];
        $repeat_id = M('Face_a185_request_record')->where($where_repeat)->getField('id');
        if ($repeat_id) {
            // 直接将之前未执行的命令改为已执行
            M('Face_a185_request_record')->where(['id' => $repeat_id])->data(['status' => 1, 'update_time' => time()])->save();
        }
        $device_sn = $device_info['device_sn'];
        $operator_type = isset($device_info['operator_type']) ? intval($device_info['operator_type']) : 1;
        $operator_id = isset($device_info['operator_id']) ? intval($device_info['operator_id']) : $device_info['village_id'];
        $version = isset($device_info['version']) ? intval($device_info['version']) : $this->version;
        $status = 0;
        $add_time = time();

        // 整合需要添加的数据
        $add_data = [
            'village_id' => $device_info['village_id'],
            'device_sn' => $device_sn,
            'device_id' => $device_id,
            'operator_type' => $operator_type,
            'operator_id' => $operator_id,
            'cmd_name' => $cmd_name,
            'version' => $version,
            'status' => $status,
            'add_time' => $add_time,
            'sort' => 0
        ];
        $add_id = $this->data($add_data)->add();
        if ($add_id) {
            $this->where(['id' => $add_id])->data([
                'data'=>serialize(array(
                    'cmd'=>$cmd_name,
                    'version'=>$this->version,
                    'index'=>$add_id,
                    'data'=>[]
                ))
            ])->save();
            $ret_data['data'] = $add_id;
            return $ret_data;
        } else {
            fdump(['sql'=>$this->data($add_data)->_sql(),'data'=>$device_info],'api/log/'.date('Ymd').'/a185/updateConfig',true);
            $ret_data['error'] = 1;
            $ret_data['msg'] = '添加失败';
            return $ret_data;
        }
    }

    /**
     * 设备重启
     * @author liukezhu
     * @date 2021/4/26
     * @param $device_id
     * @param int $village_id
     * @param array $device_info
     * @return array
     */
    public function a185_reboot_cam($device_id,$village_id=0,$device_info=[]) {
        $data=[];
        if (empty($device_info)) {
            $device_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        }
        if (!$village_id && $device_info['village_id']) {
            $village_id = $device_info['village_id'];
        }
        // 具体请求区分名称
        $cmd_name = 'deviceReboot';
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
            'add_time' => $add_time,
            'sort' => 100
        ];
        $add_id = $this->data($add_data)->add();
        if ($add_id) {
            $this->where(['id' => $add_id])->data([
                'data'=>serialize(array(
                    'cmd'=>$cmd_name,
                    'version'=>$this->version,
                    'index'=>$add_id,
                    'data'=>[]
                ))
            ])->save();
            $ret_data['data'] = $add_id;
            return $ret_data;
        } else {
            fdump($this->_sql(),'api/log/'.date('Ymd').'/a185/a185_reboot_cam_error_log',true);
            $ret_data['error'] = 1;
            $ret_data['msg'] = '添加失败';
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
    public function a185_arr_person_msg($device_id,$bind_arr_id,$orderGroupId='') {
        if ($bind_arr_id) {
            foreach ($bind_arr_id as $item) {
                $this->sync_a185_face_per($device_id, $item,0,[],$orderGroupId);
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
    public function sync_a185_face_per($device_id, $pigcms_id,$village_id=0, $device_info=[],$orderGroupId='') {
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
            'status' => 1,
            'type'=>['in', [0,1,2,3,4]]
        ];
        $db_house_village_user_bind = M('House_village_user_bind');
        $user_bind = $db_house_village_user_bind->where($where)->field('village_id,uid,pigcms_id,name,phone,ic_card, id_card,type,parent_id,property_starttime,property_endtime,single_id,floor_id,layer_id,vacancy_id,user_number,bind_number')->find();
        fdump_api(['单个小区绑定用户同步>>'.__LINE__,$db_house_village_user_bind->_sql(),$user_bind],'a185/sync_a185_user',1);
        if ($user_bind) {
            $data = [
                'operator_type' => 0,
                'operator_id' => $pigcms_id,
            ];
            $this->a185_create_face_record($device_id, $pigcms_id, $village_id, $user_bind, $data, $device_info);
        }
        return true;
    }



    /**
     * 添加或者编辑人脸
     * @author: liukezhu
     * @date : 2022/1/24
     * @param $device_id 设备id
     * @param $pigcms_id 小区用户绑定id
     * @param int $village_id 小区id
     * @param array $bind_info  用户绑定身份信息
     * @param array $data  内容数组
     * @param array $device_info 设备信息
     * @param int $faceId 当前设备人脸图片id
     * @param string $msg 错误提示
     * @return bool
     */
    public function a185_create_face_record($device_id,$pigcms_id,$village_id=0,$bind_info = [],$data=[],$device_info=[],$faceId=0,$msg='') {
        fdump_api(['添加或者编辑人脸>>'.__LINE__,$device_id,$pigcms_id,$village_id,$bind_info ,$data,$device_info,$faceId,$msg],'a185/a185_create_face',1);
        if (empty($bind_info)) {
            $bind_info = M('House_village_user_bind')->where(array('pigcms_id' => $pigcms_id))->find();
        }
        if (empty($device_info)) {
            $device_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        }
        //添加同步记录
        $log_id = M('House_face_sync_log')->add([
            'pigcms_id'=>$pigcms_id,
            'face_id'=>0,
            'device_id'=>$device_id,
            'device_sn'=>$device_info['device_sn'],
            'log_time'=>time(),
            'status'=>0,
            'device_info'=>json_encode($device_info),
        ]);
        if (!$village_id && $bind_info['village_id']) {
            $village_id = $bind_info['village_id'];
        }
        elseif (!$village_id && $device_info['village_id']) {
            $village_id = $device_info['village_id'];
        }
        $d_face_user_bind_device = M('Face_user_bind_device');
        $d_house_face_img = M('House_face_img');
        $where_face_bind_about = [
            'bind_type' => 0,
            'bind_id' => $pigcms_id,
            'device_type' => 61,
            'device_id' => $device_id,
        ];
        $bind_user = $d_face_user_bind_device->where($where_face_bind_about)->find();
        if ($bind_user) {
            // 如果存在同步过数据 即为编辑
            // 具体请求区分名称
            $cmd_name = 'addPerson';
        }
        else {
            // 未同步过进行添加
            $cmd_name = 'addPerson';
        }
        $where_repeat = [
            'device_id' => $device_id,
            'bind_id' => $pigcms_id,
            'cmd_name' => $cmd_name,
            'status' => 0,
        ];
        $repeat_id = M('Face_a185_request_record')->where($where_repeat)->getField('id');
        if ($repeat_id) {
            // 直接将之前未执行的命令改为已执行
            M('Face_a185_request_record')->where(['id' => $repeat_id])->data(['status' => 1, 'update_time' => time()])->save();
        }
        $device_sn = $device_info['device_sn'];
        $operator_type = isset($data['operator_type']) ? intval($data['operator_type']) : 1;
        $operator_id = isset($data['operator_id']) ? intval($data['operator_id']) : $village_id;
        $version = isset($data['version']) ? intval($data['version']) : $this->version;
        $status = 0;
        $add_time = time();
        $img_data='';
        $property_endtime=0;
        $property_starttime=0;
        $s_time=0;
        $e_time=0;
        $face_id=0; //使用中的人脸图片id

        // 获取下图片
        if ($bind_info['uid']>0) {
            $face_img_where=[
                'uid'=>$bind_info['uid'],
                'status'=>['in', [0,3]],
                'img_url'=>array('neq','')
            ];
            if($faceId > 0){
                $face_img_where['id']= array('gt' , $faceId);
            }
            $house_face_img = $d_house_face_img->where($face_img_where)->order('id asc')->field('id,img_url,status')->select();
            if($faceId > 0 && (empty($house_face_img))){
                fdump_api(['返回数据'.__LINE__,$faceId,$house_face_img,$d_house_face_img->_sql()],'a185/a185_is_next',1);
                if($log_id){
                    M('House_face_sync_log')->where(['log_id'=>$log_id])->save([
                        'status'=>2,
                        'msg'=>L_('人脸识别失败'),
                        'last_time'=>time()
                    ]);
                }
                $this->a185_face_report_notice($bind_info,$faceId,$msg);
                return true;
            }
            if(!empty($house_face_img)){
                $image_info=$house_face_img[0];
                $img_url = $image_info['img_url'];
                if (3==$image_info['status']) {
                    import('@.ORG.face.word_encryption');
                    $word_encryption = new word_encryption();
                    // 加密的图片解密
                    $img_url = $word_encryption->text_decrypt($img_url);
                }
                try {
                    $img_url = thumb_img($img_url,600,600,'', true);
                    $img_url = D('Image')->transform_image($img_url);
                    $file_handle = new file_handle();
                    if($file_handle->check_open_oss()){
                        $img_url = thumb_img($img_url,600,600,'',true);
                    }
                    $img_data = $this->imgtobase64($img_url);
                    $face_id = $image_info['id'];
                } catch( \Exception $e) {
                    $img_data = '';
                }
            }
        }
        $userTimes =  D('House_village_user_bind')->getUserEndTime($pigcms_id);
        if (isset($userTimes['propertyEndTime'])) {
            $e_time = intval($userTimes['propertyEndTime']);
        } else {
            $e_time = 0;
        }
        if (isset($userTimes['propertyStartTime'])) {
            $s_time = intval($userTimes['propertyStartTime']);
        } elseif ($e_time && $e_time>time()) {
            $s_time = time();
        } elseif ($e_time) {
            $s_time = $e_time-86400*30; // 如果结束时间小于当前时间 且 无开始时间  开始时间默认回退1个月
        } else {
            $s_time = 0;
        }
        if (in_array($bind_info['type'],[5,6]) && !$e_time) {
            fdump($device_id,'api/log/'.date('Ymd').'/a185/add_create_face_record_err_log',true);
            if($log_id){
                M('House_face_sync_log')->where(['log_id'=>$log_id])->save([
                    'status'=>2,
                    'msg'=>L_('访客或者临时出入证无法同步人脸'),
                    'last_time'=>time()
                ]);
            }
            return false;
        }
        //楼栋
        $single_info = M('House_village_single')->where(['id' => $bind_info['single_id']])->field('single_number')->find();
        //单元
        $floor_info = M('House_village_floor')->where(['floor_id' => $bind_info['floor_id']])->field('floor_number')->find();
        // 楼层
        $layer_info = M('House_village_layer')->where(['id' => $bind_info['layer_id']])->field('layer_number')->find();
        // 房屋编号
        $vacancy_info = M('House_village_user_vacancy')->field('room_number')->where(['pigcms_id' => $bind_info['vacancy_id']])->find();

        if(in_array($bind_info['type'],[0,1,2,3])){
            if(empty($single_info['single_number']) || empty($floor_info['floor_number']) || empty($layer_info['layer_number']) || empty($vacancy_info['room_number'])){
                $msg='请先填写对应';

                if(empty($single_info['single_number'])){
                    $msg.='【楼栋编号】';
                }
                if(empty($floor_info['floor_number'])){
                    $msg.='【单元编号】';
                }
                if(empty($layer_info['layer_number'])){
                    $msg.='【楼层编号】';
                }
                if(empty($vacancy_info['room_number'])){
                    $msg.='【房间编号】';
                }
                if($log_id){
                    M('House_face_sync_log')->where(['log_id'=>$log_id])->save([
                        'status'=>2,
                        'msg'=>$msg,
                        'last_time'=>time()
                    ]);
                }
                $this->a185_face_report($bind_info['pigcms_id'],2,$msg);
                fdump([
                    'bind_info'=>$bind_info,
                    'single'=>$single_info,
                    'floor'=>$floor_info,
                    'layer'=>$layer_info,
                    'vacancy'=>$vacancy_info
                ],'api/log/'.date('Ymd').'/a185/info',true);
                return false;
            }
        }

        if(!in_array($bind_info['type'],[0,1,2,3])){
            $single_info['single_number']   = '00';
            $floor_info['floor_number']     = '00';
            $layer_info['layer_number']     = '00';
            $vacancy_info['room_number']    = '00';
            $dial_room='0000';
            $dial_number='00000000';
        }
        else{
            //针对房间编号格式为0001 拼接成楼层编号和房间编号 0201
            $room_number=$vacancy_info['room_number'];
            if(intval($vacancy_info['room_number']) < 100){
                $vacancy_info['room_number'] = str_pad(intval($vacancy_info['room_number']), 2, "0", STR_PAD_LEFT);
            }
            if(strlen($vacancy_info['room_number']) == 1){
                $dial_room=$layer_info['layer_number'].'0'.$vacancy_info['room_number'];
                $dial_number=$single_info['single_number'].$floor_info['floor_number'].$layer_info['layer_number'].'0'.$vacancy_info['room_number'];
            }
            elseif (strlen($vacancy_info['room_number']) == 2){
                $dial_room=$layer_info['layer_number'].$vacancy_info['room_number'];
                $dial_number=$single_info['single_number'].$floor_info['floor_number'].$layer_info['layer_number'].$vacancy_info['room_number'];
            }
            elseif (strlen($vacancy_info['room_number']) == 3){
                $dial_room='0'.$vacancy_info['room_number'];
                $dial_number=$single_info['single_number'].$floor_info['floor_number'].'0'.$vacancy_info['room_number'];
            }
            elseif (strlen($vacancy_info['room_number']) == 4){
                $dial_room=$vacancy_info['room_number'];
                $dial_number=$single_info['single_number'].$floor_info['floor_number'].$vacancy_info['room_number'];
            }
            else{
                $dial_room=$vacancy_info['room_number'];
                $dial_number=$single_info['single_number'].$floor_info['floor_number'].$vacancy_info['room_number'];
            }
            if($room_number != $dial_room){
                M('House_village_user_vacancy')->where(['pigcms_id' => $bind_info['vacancy_id']])->save(['room_number'=>$dial_room]);
                fdump_api(['编辑房间编号>>'.__LINE__,M('House_village_user_vacancy')->_sql(),$room_number,$dial_room],'a185/a185_edit_room_number',1);
            }
        }
        $db_user_set=M('user_set')->where(['uid'=>$bind_info['uid']])->getField('allow_cloud_calls');
        //todo 是否允许用户被呼叫
        $is_oncalled=1;
        if($db_user_set && $db_user_set == 2){
            $is_oncalled=2;
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
            'add_time' => $add_time,
            'face_id'=>$face_id,
            'sort'=>70
        ];
        $add_id = $this->data($add_data)->add();
        if ($add_id) {
            $ret_data['data'] = $add_id;
            $data_msg=array(
                'cmd'=>$cmd_name,
                'version'=>$version,
                'index'=>$add_id,
                'data'=>array(
                    'person_id'=>$bind_info['pigcms_id'],
                    'name'=>$bind_info['name'],
                    'phone'=>$bind_info['phone'],
                    'ic_card'=>!empty($bind_info['ic_card']) ? ((string)$bind_info['ic_card']) : '0',
                    'id_card'=>trim($bind_info['id_card']),
                    'face_id'=>$face_id,
                    'face_img'=>$img_data,
                    'img_type'=>'jpg',
                    'start_time'=>($s_time && $e_time) ? $s_time : 0,
                    'end_time'=>($s_time && $e_time) ? $e_time : 0,
                    'type'=>$bind_info['type'],
                    'is_oncalled'=>$is_oncalled,
                    'room_id'=>$bind_info['vacancy_id'],
                    'single_number'=>$single_info['single_number'],
                    'floor_number'=>$floor_info['floor_number'],
                    'layer_number'=>$layer_info['layer_number'],
                    'room_number'=>$vacancy_info['room_number'],
                    'dial_room'=>$dial_room,
                    'dial_number'=>$dial_number,
                    'indoor_number'=>'',
                    'log_id'=>$log_id
                )
            );
            $this->where(['id' => $add_id])->data([
                'data'=>serialize($data_msg)
            ])->save();
            return true;
        }
        else {
            if($log_id){
                M('House_face_sync_log')->where(['log_id'=>$log_id])->save([
                    'status'=>2,
                    'msg'=>L_('人脸同步失败'),
                    'last_time'=>time()
                ]);
            }
            fdump(['添加失败--'.__LINE__,$this->_sql()],'api/log/'.date('Ymd').'/a185/add_create_face_record_err_log',true);
            return false;
        }
    }

    /**
     * 远程开门
     * @author liukezhu
     * @date 2021/4/27
     * @param $device_id
     * @param int $village_id
     * @param $uid
     * @return array
     *
     */
    public function a185_gpio($device_id,$village_id=0,$uid=0) {
        $data=[];
        $device_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        if (!$village_id && $device_info['village_id']) {
            $village_id = $device_info['village_id'];
        }
        // 具体请求区分名称
        $cmd_name = 'gpioControl';
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
            'add_time' => $add_time,
            'sort' => 80
        ];
        $add_id = $this->data($add_data)->add();
        if ($add_id) {
            $ret_data['data'] = $add_id;
            $data_msg=array(
                'cmd'=>$cmd_name,
                'version'=>$version,
                'index'=>$add_id,
                'data'=>array(
                    'status'=>1
                )
            );
            $this->where(['id' => $add_id])->data([
                'data'=>serialize($data_msg)
            ])->save();
            return $ret_data;
        } else {
            $ret_data['error'] = 1;
            $ret_data['msg'] = '添加失败';
            return $ret_data;
        }
    }

    /**
     * 删除设备人员
     * @author liukezhu
     * @date 2021/4/27
     * @param $device_id
     * @param $pigcms_id
     * @param $user_bind
     * @return array|bool
     *
     */
    public function a185_delete_face($device_id,$pigcms_id,$user_bind) {
        fdump(__FUNCTION__.'删除设备人员>>>>'.__LINE__,'api/log/'.date('Ymd').'/a185/a185_delete_face',true);
        $device_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        if(!$device_info){
            return false;
        }
        $cmd_name = 'delPerson';
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
            'device_type' => 61,
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
                'cmd_name' => $cmd_name,
                'status' => 0,
            ];
            // 直接将之前未执行的命令改为已执行
            $this->where($where_repeat)->data(['status' => 1, 'update_time' => time()])->save();
        }
        // 具体请求区分名称
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
            'add_time' => $add_time,
            'sort' => 99
        ];
        $add_id = $this->data($add_data)->add();
        if ($add_id) {
            $ret_data['data'] = $add_id;
            $this->where(['id' => $add_id])->data([
                'data'=>serialize(array(
                    'cmd'=>$cmd_name,
                    'version'=>$this->version,
                    'index'=>$add_id,
                    'data'=>[
                        'person_id'=>$pigcms_id
                    ]
                ))
            ])->save();
            return $ret_data;
        } else {
            $ret_data['error'] = 1;
            $ret_data['msg'] = '添加失败';
            fdump($ret_data,'api/log/'.date('Ymd').'/a185/add_delete_face_record_log',true);
            return $ret_data;
        }
    }

    /**
     * 添加密码
     * @author liukezhu
     * @date 2021/4/27
     * @param $device
     * @param $post
     *
     */
    public function a185_add_pwd($device_id,$uid,$pwd){
        $data=[];
        $device_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        $village_id = $device_info['village_id'];
        // 具体请求区分名称
        $cmd_name = 'addPwd';
        $ret_data = [
            'error' => 0,
            'msg' => ''
        ];
        $where_repeat = [
            'device_id' => $device_id,
            'cmd_name' => $cmd_name,
            'status' => 0,
        ];
        $repeat = $this->where($where_repeat)->find();
        if ($repeat) {
            $ret_data['error'] = 0;
            $ret_data['msg'] = '【添加密码】命令已经记录等待设备执行';
            return $ret_data;
        }
        $device_sn = $device_info['device_sn'];
        $operator_type = isset($data['operator_type']) ? intval($data['operator_type']) : 1;
        $operator_id = isset($data['operator_id']) ? intval($data['operator_id']) : $village_id;
        $version = isset($data['version']) ? intval($data['version']) : $this->version;
        $status = 0;
        $add_time = time();
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
            'add_time' => $add_time,
            'sort' => 60
        ];
        $add_id = $this->data($add_data)->add();
        if ($add_id) {
            $ret_data['data'] = $add_id;
            $data_msg=array(
                'cmd'=>$cmd_name,
                'version'=>$version,
                'index'=>$add_id,
                'data'=>array(
                    'pwd'=>'*'.$pwd
                )
            );
            $this->where(['id' => $add_id])->data([
                'data'=>serialize($data_msg)
            ])->save();
            return $ret_data;
        } else {
            $ret_data['error'] = 1;
            $ret_data['msg'] = '添加失败';
            return $ret_data;
        }
    }

    /**
     * 删除密码
     * @author liukezhu
     * @date 2021/4/27
     * @param $device
     * @param $post
     *
     */
    public function a185_del_pwd($device_id,$uid,$pwd){
        $data=[];
        $device_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        $village_id = $device_info['village_id'];
        // 具体请求区分名称
        $cmd_name = 'delPwd';
        $ret_data = [
            'error' => 0,
            'msg' => ''
        ];
        $where_repeat = [
            'device_id' => $device_id,
            'cmd_name' => $cmd_name,
            'status' => 0,
        ];
        $repeat = $this->where($where_repeat)->find();
        if ($repeat) {
            $ret_data['error'] = 0;
            $ret_data['msg'] = '【删除密码】命令已经记录等待设备执行';
            return $ret_data;
        }
        $device_sn = $device_info['device_sn'];
        $operator_type = isset($data['operator_type']) ? intval($data['operator_type']) : 1;
        $operator_id = isset($data['operator_id']) ? intval($data['operator_id']) : $village_id;
        $version = isset($data['version']) ? intval($data['version']) : $this->version;
        $status = 0;
        $add_time = time();
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
            'add_time' => $add_time,
            'sort' => 60
        ];
        $add_id = $this->data($add_data)->add();
        if ($add_id) {
            $ret_data['data'] = $add_id;
            $data_msg=array(
                'cmd'=>$cmd_name,
                'version'=>$version,
                'index'=>$add_id,
                'data'=>array(
                    'pwd'=>'*'.$pwd
                )
            );
            $this->where(['id' => $add_id])->data([
                'data'=>serialize($data_msg)
            ])->save();
            return $ret_data;
        } else {
            $ret_data['error'] = 1;
            $ret_data['msg'] = '添加失败';
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
    public function sync_a185_face_all_per($device_id, $village_id=0, $floor_id='', $device_info=[],$is_plan=1) {
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
            'type'=>['in', [0,1,2,3,4]]
        ];
        if ($floor_id!=-1 && $floor_id!=0) {
            if(is_array($floor_id)){
                $floor_arr = implode(",",$floor_id);
            }else{
                $floor_arr =  $floor_id;
            }
            $where['_string'] = "(floor_id in ($floor_arr) OR type=4)";//物业人员也同步
        }
        $db_house_village_user_bind = M('House_village_user_bind');
        $user_bind = $db_house_village_user_bind->where($where)->field('village_id,uid,pigcms_id,name,phone,ic_card, id_card,type,parent_id,property_starttime,property_endtime,single_id,floor_id,layer_id,vacancy_id,user_number,bind_number')->order('parent_id desc,pigcms_id asc')->select();
        fdump_api(['同步所有用户信息>>'.__LINE__,$device_id, $village_id, $floor_id, $device_info,$is_plan,$db_house_village_user_bind->_sql(),$user_bind],'a185/sync_a185_user',1);
        if ($user_bind) {
            $db_face_door_service = D('Face_door_service');
            foreach ($user_bind as $val) {
                if ($is_plan) {
                    $param = array();
                    $param['type'] = 'sync_a185_face_single_per';
                    $param['device_id'] = $device_id;
                    $param['village_id'] = $village_id;
                    $param['pigcms_id'] = $val['pigcms_id'];
                    $param['bind_info'] = $val;
                    $param['device_info'] = $device_info;
                    $param['unique_id'] = 'sync_a185_face_single_per_'.$param['village_id'].'_'.$device_id.'_'.$val['pigcms_id'];
                    $db_face_door_service->add_plan($param, 'sub_house_village_face_device',-10);
                } else {
                    $this->a185_create_face_record($device_id, $val['pigcms_id'], $village_id, $val, [], $device_info);
                }
            }
        }
        return true;
    }

    public function base64_to_img($base_img, $id='') {
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

    /**
     * todo 同步a185设备命令
     */
    public function a185_sync_command($is_device=false,$is_advert=false,$config=[]){
        $where_device = [
            'device_type'=>61,
            'is_del'=>0
        ];
        $device_info = M('House_face_device')->where($where_device)->select();
        if($device_info){
            foreach ($device_info as $v){
                if($is_device && !empty($config)){
                    //更新设备数据
                    $this->updateConfig($v['device_id'], $v,$config);
                }
                if($is_advert){
                    //更新广告设备
                    $this->updateAdvert($v['device_id'], $v);
                }
            }
        }
        return true;
    }

    //todo a185 下发app更新命令
    public function a185_update_app($device_info,$url){
        $data=[];
        $village_id = $device_info['village_id'];
        $uid=0;
        // 具体请求区分名称
        $cmd_name = 'appUpgrade';
        $where_repeat = [
            'device_id' => $device_info['device_id'],
            'cmd_name' => $cmd_name,
            'status' => 0,
        ];
        $repeat_id = $this->where($where_repeat)->getField('id');
        if ($repeat_id) {
            return ['error'=>false,'msg'=>'升级设备命令已下发，等待设备处理'];
        }
        $device_sn = $device_info['device_sn'];
        $operator_type = isset($data['operator_type']) ? intval($data['operator_type']) : 1;
        $operator_id = isset($data['operator_id']) ? intval($data['operator_id']) : $village_id;
        $version = isset($data['version']) ? intval($data['version']) : $this->version;
        $status = 0;
        $add_time = time();
        // 整合需要添加的数据
        $add_data = [
            'village_id' => $village_id,
            'device_sn' => $device_sn,
            'device_id' => $device_info['device_id'],
            'operator_type' => $operator_type,
            'operator_id' => $operator_id,
            'bind_id'=>$uid,
            'cmd_name' => $cmd_name,
            'version' => $version,
            'status' => $status,
            'add_time' => $add_time,
            'sort' => 80
        ];
        $add_id = $this->data($add_data)->add();
        if ($add_id) {
            $data_msg=array(
                'cmd'=>$cmd_name,
                'version'=>$version,
                'index'=>$add_id,
                'data'=>array(
                    'url'=>$url
                )
            );
            $this->where(['id' => $add_id])->data([
                'data'=>serialize($data_msg)
            ])->save();
            return ['error'=>true,'msg'=>'命令下发成功，等待设备处理'];
        } else {
            return ['error'=>false,'msg'=>'命令下发失败'];
        }
    }

    //todo a185设备更新app
    public function a185_upgrade($device_id){
        $where_device = [
            'device_id' => $device_id,
            'device_type'=>61,
            'is_del'=>0
        ];
        $device_info = M('House_face_device')->where($where_device)->find();
        if(empty($device_info)){
            return ['error'=>false,'msg'=>'设备不存在'];
        }
        $url='https://o2o-service.pigcms.com/face_door_config.php?type=get_version';
        import('ORG.Net.Http');
        $pigcms_version = Http::curlGet($url);
        if($pigcms_version){
            $pigcms_version=json_decode($pigcms_version,true);
        }
        if(!isset($pigcms_version['versionNum']) || empty($pigcms_version['versionNum'])){
            return ['error'=>false,'msg'=>'请求的链接无效'];
        }
        $device_info['sdk_version']=str_replace('.',"",(string)$device_info['sdk_version']);
        if(empty($device_info['sdk_version']) || $device_info['sdk_version'] < $pigcms_version['versionNum']){
            return $this->a185_update_app($device_info,$pigcms_version['downUrl']);
        }else{
            return ['error'=>false,'msg'=>'设备版本已最新，无需升级'];
        }

    }


    //todo 人脸上传失败 通知模板消息或短信
    public function a185_face_report_notice($user_bind,$faceId,$msg){
        $type=intval(M('House_village_about_config')->where(['village_id'=>$user_bind['village_id']])->getField('a185_face_notice'));
        if(!$type){
            fdump_api(['返回数据'.__LINE__,M('House_village_about_config')->_sql(),$user_bind,$msg],'a185/a185_face_report_notice',1);
            return true;
        }
        $time=$_SERVER['REQUEST_TIME'];
        $openid = D('User')->where(['uid'=>$user_bind['uid']])->getField('openid');
        $add_time=M('House_face_img')->where(['id'=>$faceId])->getField('add_time');
        if(!$add_time){
            $add_time=$time;
        }
        $add_time=date('Y-m-d H:i:s',$add_time);
        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
        $tempKey='OPENTM400166399';
        $href = C('config.site_url').(D('House_village')->base_url) . 'pages/village_menu/my';
        $keyword1='A185人脸门禁识别通知';
        $keyword2='人脸识别失败';
        $remark='请重新上传人脸，以免造成您无法开门';
        $name=!empty($user_bind['name']) ? $user_bind['name'] : substr_replace($user_bind['phone'], '****', 3, 4);
        $msg='尊敬的'.$name.',您于'.$add_time.'提交的人脸图片由于【'.$msg.'】审核不通过，请重新录入上传或者联系物业工作人员处理，谢谢。';
        $wx_data=[
            'href'      => $href,
            'wecha_id'  => $openid,
            'first'     => $msg,
            'keyword1'  => $keyword1,
            'keyword2'  => $keyword2,
            'keyword3'  => date('H时i分', $time),
            'remark'    => $remark
        ];
        $sms_arr=[
            'mer_id'     => $user_bind['village_id'],
            'store_id'   => 0,
            'village_id' => $user_bind['village_id'],
            'type'       => 'a185_face_report',
            'uid'        => $user_bind['uid'],
            'mobile'     => $user_bind['phone'],
            'sendto'     => 'user',
            'content'    => $msg
        ];
        fdump_api(['返回数据'.__LINE__,$user_bind,$faceId,$msg,$type,$wx_data,$sms_arr],'a185/a185_face_report_notice',1);
        switch ($type)
        {
            case 1:
               //微信模板消息通知
                if(!empty($openid)){
                    $model->sendTempMsg($tempKey,$wx_data);
                }
                break;
            case 2:
                //手机短信通知
                Sms::sendSms($sms_arr);
                break;
            case 3:
                //微信模板消息 + 手机短信通知
                if(!empty($openid)){
                    $model->sendTempMsg($tempKey,$wx_data);
                }
                Sms::sendSms($sms_arr);
                break;
        }
        return true;
    }
}