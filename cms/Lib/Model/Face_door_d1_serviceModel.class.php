<?php
/**
 * D1人脸识别门禁机相关处理
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2020/12/17 9:27
 */
class Face_door_d1_serviceModel extends Model {

    public $d1_base_url = 'https://api-developer.zhiguohulian.com';

    public $type = 'house_d1_zhiguohulian';

    public $user_type = 'user_d1_zhiguohulian';
    // 账号
    public $userphone = '';
    // 密码
    public $password = '';
    // APP ID
    public $app_id = '';
    // APP Secret
    public $app_secret = '';
    // 过滤非数字部分
    public $pattern_number = "/[^0-9]/";

    public function d1_get_config() {
        $this->userphone = C('config.userphone_facedoor_d1') ? trim(C('config.userphone_facedoor_d1')) : '';
        $this->password = C('config.password_facedoor_d1') ? trim(C('config.password_facedoor_d1')) : '';
        $this->app_id = C('config.app_id_facedoor_d1') ? trim(C('config.app_id_facedoor_d1')) : '';
        $this->app_secret = C('config.app_secret_facedoor_d1') ? trim(C('config.app_secret_facedoor_d1')) : '';
    }

    /**
     * Notes: 代理商登录管理后台  反馈登录token凭证
     * @return array
     * @author: wanzy
     * @date_time: 2020/12/17 15:57
     */
    public function d1_login() {
        if (!$this->userphone) {
            $this->d1_get_config();
        }
        if (!$this->userphone || !$this->password || !$this->app_id || !$this->app_secret) {
            return array('errcode' => '1001', 'errmsg' => '缺少必要配置项');
        }
        $db_access_token_common_expires = M('Access_token_common_expires');
        $where_token = [
            'type' => $this->type,
            'access_id' => $this->app_id,
        ];
        // 查询下是否有记录下登录凭证
        $access_token_info = $db_access_token_common_expires->where($where_token)->find();
        $now = time();
        // 过期时间  向前1800秒 提前刷新
        if ($access_token_info && intval($access_token_info['access_token_expire']) > intval($now + 1800)) {
            return array('errcode' => 0, 'access_token' =>$access_token_info['access_token']);
        } elseif($access_token_info['refresh_token_expire']) {
            // 如果刷新token没有过期调用刷新接口
            if ($access_token_info['refresh_token'] && $access_token_info['refresh_token_expire'] && intval($access_token_info['refresh_token_expire']) > intval($now + 60)) {
                $msg = $this->d1_refresh_token();
                if ($msg['errcode']==0) {
                    fdump_api(['刷新登录token失败',$access_token_info,$msg],'d1/d1_login_refresh_err_log',1);
                    return $msg;
                }
                // 添加处理  刷新失败 直接 使用下面的重新获取接口
            }
        }
        $authorization_msg = [
            'app_id' => $this->app_id,
            'app_secret' => $this->app_secret,
        ];
        $authorization_json = json_encode($authorization_msg,true);
        $authorization = base64_encode($authorization_json);
        $url = $this->d1_base_url . '/openapi/v3/login';
        $params = [
            'userphone' => $this->userphone,
            'password' => $this->password,
        ];
        $info = $this->curl_post($url,$params,$authorization);
        if ($info && $info['data'] && $info['data']['access_token']) {
            $access_token_common_expires = [];
            $data = $info['data'];
            if ($data['access_token']) {
                $access_token_common_expires['access_token'] = $data['access_token'];
            }
            if ($data['access_token_expire']) {
                $access_token_common_expires['access_token_expire'] = intval($data['access_token_expire']);
            }
            if ($data['refresh_token']) {
                $access_token_common_expires['refresh_token'] = $data['refresh_token'];
            }
            if ($data['refresh_token_expire']) {
                $access_token_common_expires['refresh_token_expire'] = $data['refresh_token_expire'];
            }
            $access_token_common_expires['type'] = $this->type;
            $access_token_common_expires['access_id'] = $this->app_id;
            $where_token = [
                'type' => $this->type,
                'access_id' => $this->app_id,
            ];
            // 查询下是否有记录下登录凭证
            $db_access_token_common_expires->where($where_token)->delete();
            $db_access_token_common_expires->data($access_token_common_expires)->add();
            return array('errcode' => 0, 'access_token' =>$data['access_token']);
        } else {
            return array('errcode' => $info['code'], 'errmsg' => $info['msg']);
        }
    }

    /**
     * Notes: 刷新登录token,当刷新token未过期时可用此重新生成登录token,做到无感登录
     * @param $access_token_info
     * @return array
     * @author: wanzy
     * @date_time: 2020/12/17 15:57
     */
    private function d1_refresh_token() {
        if (!$this->userphone) {
            $this->d1_get_config();
        }
        if (!$this->userphone || !$this->password || !$this->app_id || !$this->app_secret) {
            return array('errcode' => '1001', 'errmsg' => '缺少必要配置项');
        }
        $db_access_token_common_expires = M('Access_token_common_expires');
        $where_token = [
            'type' => $this->type,
            'access_id' => $this->app_id,
        ];
        // 查询下是否有记录下登录凭证
        $access_token_info = $db_access_token_common_expires->where($where_token)->find();
        if ($access_token_info['refresh_token']) {
            $authorization = $access_token_info['refresh_token'];
            $url = $this->d1_base_url . '/openapi/v3/refresh_token';
            $refresh_info = $this->curl_post($url, [], $authorization,'GET');
            fdump_api(['刷新登录token',$url,$authorization,'GET',$refresh_info],'d1/d1_refresh_token_log',1);

            if ($refresh_info && $refresh_info['data'] && $refresh_info['data']['access_token']) {

                $token_data = [];
                $data = $refresh_info['data'];
                if ($data['access_token']) {
                    $token_data['access_token'] = $data['access_token'];
                }
                if ($data['access_token_expire']) {
                    $token_data['access_token_expire'] = intval($data['access_token_expire']);
                }
                if (!$access_token_info['id']) {
                    $where_token = [
                        'type' => $this->type,
                        'access_id' => $this->app_id,
                    ];
                    // 查询下是否有记录下登录凭证
                    $id = $db_access_token_common_expires->where($where_token)->getField('id');
                } else {
                    $id = $access_token_info['id'];
                }
                if ($id) {
                    $db_access_token_common_expires->where(['id' => $id])->data($token_data)->save();
                } else {
                    $db_access_token_common_expires->data($token_data)->add();
                }
                return array('errcode' => 0, 'access_token' =>$data['access_token']);
            } else {
                if (508==$refresh_info['code']) {
                    $db_access_token_common_expires = M('Access_token_common_expires');
                    if (!$access_token_info['id']) {
                        $where_token = [
                            'type' => $this->type,
                            'access_id' => $this->app_id,
                        ];
                        // 查询下是否有记录下登录凭证
                        $id = $db_access_token_common_expires->where($where_token)->getField('id');
                    } else {
                        $id = $access_token_info['id'];
                    }
                    if ($id) {
                        $token_data['access_token_expire'] = time();
                        $token_data['refresh_token_expire'] = time();
                        $db_access_token_common_expires->where(['id' => $id])->data($token_data)->save();
                    }
                    return $this->d1_login();
                }
                return array('errcode' => $refresh_info['code'], 'errmsg' => $refresh_info['msg']);
            }
        } else {
            return array('errcode' => '1001', 'errmsg' => '缺少必要刷新参数');
        }
    }

    /**
     * Notes: 综合调控接口-主要是添加设备后调取和 一键同步调取
     * @param $device_id
     * @param array $face_device_data
     * @param string $handle_type
     * @param array $param_syn
     * @param int $is_plan
     * @return array|bool
     * @author: wanzy
     * @date_time: 2020/12/24 17:19
     */
    public function face_d1_door_syn($device_id, $face_device_data = [],$handle_type='single_syn', $param_syn=[], $is_plan=1) {
        if (empty($face_device_data)) {
            $face_device_data = M('House_face_device')->where(array('device_id' => $device_id))->find();
        }
        // 判断对应上级是否进行过同步  如果没有一一进行同步
        $village_id = $face_device_data['village_id'];
        $d_face_bind_about_info = M('Face_bind_about_info');
        $d_face_door_service = D('Face_door_service');

        if (!$this->userphone) {
            $this->d1_get_config();
        }
        if (!$this->userphone || !$this->password || !$this->app_id || !$this->app_secret) {
            return array('errcode' => '1001', 'errmsg' => '缺少必要配置项');
        }
        $db_third_region_info = M('Third_region_info');
        $where_replace = [
            'key_id' => $this->app_id,
            'type' => $this->type,
            'region_level' => 3,
        ];
        // 最后一层数据存在不再进行同步
        $replace_last = $db_third_region_info->where($where_replace)->getField('id');
        if (!$replace_last) {
            // [省/市/区]级联信息, 直接从同步[省/市/区]级联信息开始
            if ($is_plan) {
                $param = array();
                $param['type'] = 'd1_get_region_info';
                $param['handle_type'] = $handle_type;
                $param['device_id'] = $device_id;
                $param['village_id'] = $village_id;
                $unique_id = 'd1_get_region_info';
                if (isset($param['village_id'])) {
                    $unique_id .='_'.$param['village_id'];
                }
                if (isset($param['device_id'])) {
                    $unique_id .='_'.$param['device_id'];
                }
                $param['unique_id'] = $unique_id;
                if (isset($face_device_data['orderGroupId']) && $face_device_data['orderGroupId']) {
                    $param['orderGroupId'] = $face_device_data['orderGroupId'];
                }
                $d_face_door_service->add_plan($param, 'sub_house_face', -9);
                return true;
            } else {
                $param = array();
                $param['device_id'] = $device_id;
                $param['village_id'] = $village_id;
                $param['handle_type'] = $handle_type;
                $this->d1_get_region_info($param, 0);
            }
        }
        $where_face_bind_about = [
            'bind_type' => 0,
            'bind_id' => $village_id,
            'device_id' => 0,
            'device_type' => 21,
        ];
        $community_third_info =  $d_face_bind_about_info->where($where_face_bind_about)->field('third_id, group_id, bind_name')->find();
        if ($community_third_info) {
            $communityId = $community_third_info['third_id'];
            if ($community_third_info['group_id']) {
                $project_region_uid = $community_third_info['group_id'];
            }
            $village_name = M('House_village')->where(['village_id'=>$village_id])->getField('village_name');
            $bind_name = $village_name.'_'.$village_id;
        } else {
            $project_region_uid = '';
        }
        if (!$communityId || ($bind_name && $bind_name!=$community_third_info['bind_name'])) {
            // 对应社区没有同步, 直接从同步社区开始
            if ($is_plan) {
                $param = array();
                $param['type'] = 'd1_create_project';
                $param['handle_type'] = $handle_type;
                $param['device_id'] = $device_id;
                $param['village_id'] = $village_id;
                if ($project_region_uid) {
                    $param['project_region_uid'] = $project_region_uid;
                }
                $unique_id = 'd1_create_project';
                if (isset($param['village_id'])) {
                    $unique_id .='_'.$param['village_id'];
                }
                if (isset($param['device_id'])) {
                    $unique_id .='_'.$param['device_id'];
                }
                $param['unique_id'] = $unique_id;
                $d_face_door_service->add_plan($param, 'sub_house_face', -8);
                return true;
            } else {
                $this->d1_create_project($village_id, $project_region_uid, []);
            }
        }
        // 对应社区楼栋同步
        if (!$param_syn['is_syn_all_building']) {
            if ($is_plan) {
                $param = array();
                $param['type'] = 'd1_project_all_building';
                $param['handle_type'] = $handle_type;
                $param['device_id'] = $device_id;
                $param['village_id'] = $village_id;
                if ($param_syn['project_uid']) {
                    $param['project_uid'] = $param_syn['project_uid'];
                }
                $unique_id = 'd1_project_all_building';
                if (isset($param['village_id'])) {
                    $unique_id .='_'.$param['village_id'];
                }
                if (isset($param['device_id'])) {
                    $unique_id .='_'.$param['device_id'];
                }
                $param['unique_id'] = $unique_id;
                $d_face_door_service->add_plan($param, 'sub_house_face', -7);
                return true;
            } else {
                if ($param_syn['project_uid']) {
                    $project_uid = $param_syn['project_uid'];
                } else {
                    $project_uid = '';
                }
                $this->d1_project_all_building($village_id, $project_uid, $handle_type, $device_id, 0);
            }
        }
        if (!$param_syn['is_syn_work']) {
            if ($is_plan) {
                $param = array();
                $param['type'] = 'd1_add_work_msg';
                $param['handle_type'] = $handle_type;
                $param['device_id'] = $device_id;
                $param['village_id'] = $village_id;
                if ($param_syn['project_uid']) {
                    $param['project_uid'] = $param_syn['project_uid'];
                }
                $unique_id = 'd1_add_work_msg';
                if (isset($param['village_id'])) {
                    $unique_id .='_'.$param['village_id'];
                }
                if (isset($param['device_id'])) {
                    $unique_id .='_'.$param['device_id'];
                }
                $param['unique_id'] = $unique_id;
                $d_face_door_service->add_plan($param, 'sub_house_face', -7);
                return true;
            } else {
                if ($param_syn['project_uid']) {
                    $project_uid = $param_syn['project_uid'];
                } else {
                    $project_uid = '';
                }
                $this->d1_add_work_msg($village_id, $project_uid, $is_plan);
            }
        }
        // 楼栋同步完成 同步设备 d1_add_gate
        if (!$param_syn['is_syn_device']) {
            if ($is_plan) {
                $param = array();
                $param['type'] = 'd1_add_gate';
                $param['handle_type'] = $handle_type;
                $param['device_id'] = $device_id;
                $param['village_id'] = $village_id;
                if ($param_syn['project_uid']) {
                    $param['project_uid'] = $param_syn['project_uid'];
                }
                $unique_id = 'd1_add_gate';
                if (isset($param['village_id'])) {
                    $unique_id .='_'.$param['village_id'];
                }
                if (isset($param['device_id'])) {
                    $unique_id .='_'.$param['device_id'];
                }
                $param['unique_id'] = $unique_id;
                $device_msg = [
                    'device_id' => $face_device_data['device_id'],
                    'village_id' => $village_id,
                    'device_name' => $face_device_data['device_name'],
                    'device_type' => $face_device_data['device_type'],
                    'device_sn' => $face_device_data['device_sn'],
                    'floor_id' => $face_device_data['floor_id'],
                    'public_area_id' => $face_device_data['public_area_id'],
                    'ai_device_sn' => $face_device_data['ai_device_sn'],
                ];
                $param['device_info'] = $device_msg;
                $d_face_door_service->add_plan($param, 'sub_house_face', -6);
            } else {
                if ($param_syn['project_uid']) {
                    $project_uid = $param_syn['project_uid'];
                } else {
                    $project_uid = '';
                }
                $this->d1_add_gate($device_id, $project_uid, [], $face_device_data);
            }
            return true;
        }
    }

    // 获取[省/市/区]级联信息
    public function d1_get_region_info($param_msg=[], $pid=0) {
        if (!$this->userphone) {
            $this->d1_get_config();
        }
        if (!$this->userphone || !$this->password || !$this->app_id || !$this->app_secret) {
            return array('errcode' => '1001', 'errmsg' => '缺少必要配置项');
        }
        $db_third_region_info = M('Third_region_info');
        $where_replace = [
            'key_id' => $this->app_id,
            'type' => $this->type,
            'region_level' => 3,
        ];
        // 最后一层数据存在不再进行同步
        $replace_last = $db_third_region_info->where($where_replace)->getField('id');
        if ($replace_last) {
            return $param_msg;
        }
        $access_token_info = $this->d1_login();
        if (0==$access_token_info['errcode']) {
            $access_token = $access_token_info['access_token'];
            $authorization = $access_token;
            $url = $this->d1_base_url . '/openapi/v3/public/getRegionCascade';
            $param = [];
            if ($pid) {
                $url.= '?pid='.$pid;
            }
            $info = $this->curl_post($url, $param, $authorization, 'GET');

            if ($info && $info['data'] && $info['code']==200) {
                $data = $info['data'];
                foreach ($data as $val) {
                    if (!$val['uid'] && !$val['region_id']) {
                        continue;
                    }
                    $third_region_info = [
                        'key_id' => $this->app_id,
                        'type' => $this->type,
                        'uid' => $val['uid'],
                        'region_id' => $val['region_id'],
                        'region_pid' => $val['region_pid'] ? $val['region_pid'] : 0,
                        'region_pid_path' => $val['region_pid_path'] ? $val['region_pid_path'] : '',
                        'region_level' => $val['region_level'] ? $val['region_level'] : '',
                        'region_name' => $val['region_name'] ? $val['region_name'] : '',
                        'region_name_path' => $val['region_name_path'] ? $val['region_name_path'] : '',
                    ];
                    $where_replace = [
                        'key_id' => $this->app_id,
                        'type' => $this->type,
                        'uid' => $val['uid'],
                        'region_id' => $val['region_id'],
                    ];
                    $replace_id = $db_third_region_info->where($where_replace)->getField('id');
                    if ($replace_id) {
                        $third_region_info['last_time'] = time();
                        $db_third_region_info->where($where_replace)->data($third_region_info)->save();
                    } else {
                        $third_region_info['add_time'] = time();
                        $db_third_region_info->data($third_region_info)->add();
                    }
                    if ($val['region_level'] && intval($val['region_level'])<3) {
                        $this->d1_get_region_info($param_msg, $val['region_id']);
                    }
                }
                return $param_msg;
            } else {
                fdump('获取城市区域错误>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_get_region_info_err_log',true);
                fdump($url,'api/log/'.date('Ymd').'/D1/d1_get_region_info_err_log',true);
                fdump($authorization,'api/log/'.date('Ymd').'/D1/d1_get_region_info_err_log',true);
                fdump($this->userphone,'api/log/'.date('Ymd').'/D1/d1_get_region_info_err_log',true);
                fdump($this->password,'api/log/'.date('Ymd').'/D1/d1_get_region_info_err_log',true);
                fdump($this->app_id,'api/log/'.date('Ymd').'/D1/d1_get_region_info_err_log',true);
                fdump($this->app_secret,'api/log/'.date('Ymd').'/D1/d1_get_region_info_err_log',true);
                fdump($info,'api/log/'.date('Ymd').'/D1/d1_get_region_info_err_log',true);
                return array('errcode' => '1001', 'errmsg' => '获取失败');
            }
        } else {
            fdump('获取城市区域token错误>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_get_region_info_err_log',true);
            fdump($this->userphone,'api/log/'.date('Ymd').'/D1/d1_get_region_info_err_log',true);
            fdump($this->password,'api/log/'.date('Ymd').'/D1/d1_get_region_info_err_log',true);
            fdump($this->app_id,'api/log/'.date('Ymd').'/D1/d1_get_region_info_err_log',true);
            fdump($this->app_secret,'api/log/'.date('Ymd').'/D1/d1_get_region_info_err_log',true);
            fdump($access_token_info,'api/log/'.date('Ymd').'/D1/d1_get_region_info_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '获取失败');
        }
    }

    /**
     * Notes:创建项目【同步小区】
     * @param $village_id
     * @param string $project_region
     * @param array $village_info
     * @return array
     * @author: wanzy
     * @date_time: 2020/12/21 15:40
     */
    public function d1_create_project($village_id, $project_region_uid='', $village_info = [], $repeat_post = 0) {
        $db_face_bind_about_info = M('Face_bind_about_info');
        $where_bind = [
            'bind_type' => 0,
            'bind_id' => $village_id,
            'device_id' => 0,
            'device_type' => 21,
        ];
        $project_uid = $db_face_bind_about_info->where($where_bind)->order('id ASC')->getField('third_id');
        if (!$this->userphone) {
            $this->d1_get_config();
        }
        if (!$this->userphone || !$this->password || !$this->app_id || !$this->app_secret) {
            return array('errcode' => '1001', 'errmsg' => '缺少必要配置项');
        }
        $access_token_info = $this->d1_login();
        if (0==$access_token_info['errcode']) {
            if (empty($village_info)) {
                $village_info = M('House_village')->where(['village_id' => $village_id])->find();
            }
            if (!$project_region_uid) {
                if ($village_info['area_id']) {
                    $where_area = [
                        'area_id' => $village_info['area_id']
                    ];
                    $area_name = M('Area')->where($where_area)->getField('area_name');
                }
                if (!$area_name){
                    $area_name = '东城区';
                }
                $where_region = [
                    'region_name' => $area_name,
                    'region_level' => 3,
                    'key_id' => $this->app_id
                ];
                $third_region_info = M('Third_region_info')->where($where_region)->field('id, uid, region_name, region_name_path')->find();
                if ($third_region_info) {
                    $project_region_uid = $third_region_info['uid'];
                } else {
                    // 如果没有查询到对应的  默认取最早添加的的一个城区
                    $where_region = [
                        'region_level' => 3,
                        'key_id' => $this->app_id
                    ];
                    $third_region_info = M('Third_region_info')->where($where_region)->order('id ASC')->field('id, uid, region_name, region_name_path')->find();
                    $project_region_uid = $third_region_info['uid'];
                    $area_name = $third_region_info['region_name'];
                }
            }
            if ($village_info['long'] && $village_info['lat']) {
                $long = $village_info['long'];
                $lat = $village_info['lat'];
            } else {
                $where_area = [
                    'area_name' => $area_name,
                    'area_lng' => ['neq', '0.000000'],
                    'area_lat' => ['neq', '0.000000'],
                    'is_open' => ['in', [0,1]],
                ];
                $area_info = M('Area')->where($where_area)->order('is_open DESC')->field('area_name')->find();
                if ($area_info['area_lng'] && $area_info['area_lat']) {
                    $long = $area_info['area_lng'];
                    $lat = $area_info['area_lat'];
                } else {
                    $long = '117.2285309';
                    $lat = '31.8291397';
                }
            }
            $project_name = $village_info['village_name'].'_'.$village_id;
            $project_gps = $long.','.$lat;
            $project_contact = $village_info['property_address'] ? $village_info['property_address'] : $village_info['village_name'];

            $property_phone = $village_info['property_phone'];
            $property_phone = explode(' ', $property_phone);
            if ($property_phone && $property_phone[0]) {
                $project_contact_phone = $property_phone[0];
            } else {
                $project_contact_phone = '12345678910';
            }
            $where_property =[
                'id' => $village_info['property_id']
            ];
            $property_info = M('House_property')->where($where_property)->field('property_name, property_phone')->find();
            if ($property_info) {
                $name = $property_info['property_name'];
                $property_phone_arr = explode(' ', $property_info['property_phone']);
                if ($property_phone_arr && $property_phone_arr[0]) {
                    $phone = $property_phone_arr[0];
                }
            }
            if (!$name && $village_info['property_name']) {
                $name = $village_info['property_name'];
            } else {
                $name = $village_info['village_name'].'物业';
            }
            if (!$phone) {
                $phone = $project_contact_phone;
            }
            $manager = (Object)[
                'name' => $name,
                'phone' => $phone,
            ];
            $project_manager = (Object)[$manager];
            $param = [];
            if ($project_name) {
                $param['project_name'] = $project_name;
            } else {
                fdump('创建项目[同步小区]失败 缺少项目名称>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
                fdump($village_id,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
                fdump($project_region_uid,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
                fdump($village_info,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
                fdump($project_name,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
                return array('errcode' => '1001', 'errmsg' => '缺少项目名称');
            }
            if ($project_region_uid) {
                $param['project_region'] = $project_region_uid;
            } else {
                fdump('创建项目[同步小区]失败 缺少区域uid>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
                fdump($village_id,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
                fdump($village_info,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
                fdump($project_region_uid,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
                return array('errcode' => '1001', 'errmsg' => '缺少区域信息');
            }
            if ($project_gps) {
                $param['project_gps'] = $project_gps;
            } else {
                fdump('创建项目[同步小区]失败 缺少区域经纬坐标>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
                fdump($village_id,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
                fdump($village_info,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
                fdump($project_region_uid,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
                fdump($project_gps,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
                return array('errcode' => '1001', 'errmsg' => '缺少区域经纬坐标');
            }
            if ($project_contact) {
                $param['project_contact'] = $project_contact;
            } else {
                fdump('创建项目[同步小区]失败 缺少项目负责人>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
                fdump($village_id,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
                fdump($village_info,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
                fdump($project_region_uid,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
                fdump($project_contact,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
                return array('errcode' => '1001', 'errmsg' => '缺少项目负责人');
            }
            if ($project_contact_phone) {
                $param['project_contact_phone'] = $project_contact_phone;
            } else {
                fdump('创建项目[同步小区]失败 缺少项目负责人电话>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
                fdump($village_id,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
                fdump($village_info,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
                fdump($project_region_uid,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
                fdump($project_contact_phone,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
                return array('errcode' => '1001', 'errmsg' => '缺少项目负责人电话');
            }
            if ($project_manager) {
                $param['project_manager'] = $project_manager;
            } else {
                fdump('创建项目[同步小区]失败 缺少物业信息>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
                fdump($village_id,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
                fdump($village_info,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
                fdump($project_region_uid,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
                fdump($project_manager,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
                return array('errcode' => '1001', 'errmsg' => '缺少物业信息');
            }
            $access_token = $access_token_info['access_token'];
            $authorization = $access_token;
            $url = $this->d1_base_url . '/openapi/v3/project';
            $param['project_guide_pic'] = '';
            if ($project_uid) {
                $param['uid'] = $project_uid;
                $request = 'PUT';
            } else {
                $request = 'POST';
            }
            $info = $this->curl_post($url, $param, $authorization, $request);
            fdump('创建项目[同步小区]>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_create_project_log',true);
            fdump($url,'api/log/'.date('Ymd').'/D1/d1_create_project_log',true);
            fdump($param,'api/log/'.date('Ymd').'/D1/d1_create_project_log',true);
            fdump($authorization,'api/log/'.date('Ymd').'/D1/d1_create_project_log',true);
            fdump($info,'api/log/'.date('Ymd').'/D1/d1_create_project_log',true);
            if ($info && $info['code']==200) {
                if (!$project_uid) {
                    $data = $info['data'];
                    $project_uid = $data['uid'];
                    $bind_data = [
                        'bind_type' => 0,
                        'bind_id' => $village_id,
                        'bind_name' => $project_name,
                        'third_id' => $project_uid,
                        'third_info' => serialize($data),
                        'group_id' => $project_region_uid,
                        'device_type' => 21,
                        'device_id' => 0,
                        'add_time' => time(),
                    ];
                    $db_face_bind_about_info->data($bind_data)->add();
                } else {
                    $where_bind = [
                        'bind_type' => 0,
                        'bind_id' => $village_id,
                        'device_id' => 0,
                        'device_type' => 21,
                    ];
                    $set = [
                        'bind_name' => $project_name,
                        'third_id' => $project_uid,
                        'group_id' => $project_region_uid,
                        'last_time' => time(),
                    ];
                    $db_face_bind_about_info->where($where_bind)->data($set)->save();
                }
                $this->d1_set_project_config($village_id, $project_uid);
                return $project_uid;
            } elseif (400==$info['code'] && 0==$repeat_post) {
                $msg = $this->d1_refresh_token();
                if (0==$msg['errcode']) {
                    return $this->d1_create_project($village_id, $project_region_uid, $village_info, 1);
                }
            } else {
                return array('errcode' => '1001', 'errmsg' => $info['msg']);
            }
        }else {
            fdump('创建项目[同步小区]失败token错误>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
            fdump($this->userphone,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
            fdump($this->password,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
            fdump($this->app_id,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
            fdump($this->app_secret,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
            fdump($access_token_info,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '获取失败');
        }
    }

    /**
     * Notes:获取项目配置
     * @param $village_id
     * @param string $project_uid
     * @return bool|mixed|string|array
     * {
     *      app_accredit_number	int	app授权管理人数
     *      app_accredit_status	int	app授权功能状态(1:开启 0:关闭)
     *      app_ad_number	int	app显示广告数量
     *      app_ad_status	int	app推送广告开启状态(1:开启 0:关闭)
     *      face_charge_mode	int	人脸识别收费（0免费1收费）
     *      gate_ad_number	int	门禁显示广告数量
     *      gate_ad_report	int	门禁广告上报开关(1:开启 0:关闭)
     *      gate_ad_status	int	门禁广告开启状态(1:开启 0:关闭)
     *      gate_baidu_ad_number	int	百度广告位
     *      gate_baidu_ad_report	int	门禁百度广告上报开关(1:开启 0:关闭)
     *      gate_baidu_ad_status	int	百度广告开放状态(1:开启 0:关闭)
     *      gate_button_password	int	密码开门(1:开启 0:关闭)
     *      gate_button_phone	int	手机号呼叫开门(1:开启 0:关闭)
     *      gate_button_room	int	房号呼叫开门(1:开启 0:关闭)
     *      gate_button_room_input	int	输入房号呼叫开门(1:开启 0:关闭)
     *      gate_call_manager	int	门禁呼叫管理处开关(1:开启 0:关闭)
     *      gate_capture_picture	int	开门抓拍是否上传图片(1:开启 0:关闭)
     *      gate_check_face_switch	int	活体检测开关(1:开启 0:关闭)
     *      gate_notice_number	int	门禁公告数
     *      gate_notice_status	int	门禁公告开关(1:开启 0:关闭)
     *      gate_open_face	int	门禁机人脸识别开门(0关闭1开启)
     *      gate_qr_pwd_state	int	扫码开门(1:开启 0:关闭)
     *      gate_register_face	int	门禁机注册人脸开关(1:开启 0:关闭)
     *      health_qr_code_switch	int	健康码开门(1:开启 0:关闭)
     *      join_ad_plan	int	是否参与广告计划(1:开启 0:关闭)
     *      manage_card_expired	int	管理卡有效期/月
     *      member_expired	int	业主成员有效期(0:永久,其他数字代表月)
     *      normal_card_auth	int	门禁自助授权开关(1:开启 0:关闭)
     *      normal_card_auth_expired	int	门禁自助授权截止日期时间戳
     *      normal_card_expired	int	普通卡有效期/月
     *      open_key_apply	int	是否开户申请钥匙(1:开启 0:关闭)
     *      owner_expired	int	业主有效期(0:永久,其他数字代表月)
     *      pop_ad_switch	int	弹屏广告(1:开启 0:关闭)
     *      property_fee_switch	int	物业费催交(1:开启 0:关闭)
     *      renter_expired	int	租客有效期(0:永久,其他数字代表月)
     *      stranger_captures	int	陌生人抓拍(1:开启 0:关闭)，注：慎开，打开将会迅速消耗流量
     * }
     * @author: wanzy
     * @date_time: 2020/12/21 16:05
     */
    public function d1_get_project_config($village_id, $project_uid='', $repeat_post=0) {
        if (!$project_uid) {
            $db_face_bind_about_info = M('Face_bind_about_info');
            $where_bind = [
                'bind_type' => 0,
                'bind_id' => $village_id,
                'device_id' => 0,
                'device_type' => 21,
            ];
            $project_uid = $db_face_bind_about_info->where($where_bind)->order('id ASC')->getField('third_id');
        }
        if (!$project_uid) {
            return array('errcode' => '1001', 'errmsg' => '参数缺失');
        }
        if (!$this->userphone) {
            $this->d1_get_config();
        }
        if (!$this->userphone || !$this->password || !$this->app_id || !$this->app_secret) {
            return array('errcode' => '1001', 'errmsg' => '缺少必要配置项');
        }
        $access_token_info = $this->d1_login();
        if (0==$access_token_info['errcode']) {

            $access_token = $access_token_info['access_token'];
            $authorization = $access_token;
            $url = $this->d1_base_url . '/openapi/v3/projectConfig';
            $param = [];
            if ($project_uid) {
                $url.= '?uid='.$project_uid;
            }
            $info = $this->curl_post($url, $param, $authorization, 'GET');
            if (400==$info['code'] && 0==$repeat_post) {
                $msg = $this->d1_refresh_token();
                if (0==$msg['errcode']) {
                    return $this->d1_get_project_config($village_id, $project_uid, 1);
                }
            }
            return $info;
        } else {
            fdump('创建项目[同步小区]失败token错误>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
            fdump($this->userphone,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
            fdump($this->password,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
            fdump($this->app_id,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
            fdump($this->app_secret,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
            fdump($access_token_info,'api/log/'.date('Ymd').'/D1/d1_create_project_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '获取失败');
        }
    }

    /**
     * Notes: 配置设置
     * @param $village_id
     * @param string $project_uid
     * @param int $repeat_post
     * @return array|bool|mixed|string
     * @author: wanzy
     * @date_time: 2021/1/5 14:02
     */
    public function d1_set_project_config($village_id, $project_uid='', $repeat_post=0) {
        if (!$project_uid) {
            $db_face_bind_about_info = M('Face_bind_about_info');
            $where_bind = [
                'bind_type' => 0,
                'bind_id' => $village_id,
                'device_id' => 0,
                'device_type' => 21,
            ];
            $project_uid = $db_face_bind_about_info->where($where_bind)->order('id ASC')->getField('third_id');
        }
        if (!$project_uid) {
            return array('errcode' => '1001', 'errmsg' => '参数缺失');
        }
        if (!$this->userphone) {
            $this->d1_get_config();
        }
        if (!$this->userphone || !$this->password || !$this->app_id || !$this->app_secret) {
            return array('errcode' => '1001', 'errmsg' => '缺少必要配置项');
        }
        $access_token_info = $this->d1_login();
        if (0==$access_token_info['errcode']) {

            $access_token = $access_token_info['access_token'];
            $authorization = $access_token;
            $url = $this->d1_base_url . '/openapi/v3/projectConfig';
            $param = [];
            $param['uid'] = $project_uid;
            $param['gate_open_face'] = 1;
            $param['open_key_apply'] = 1;
            $param['gate_check_face_switch'] = 1;
            $info = $this->curl_post($url, $param, $authorization, 'PUT');
            fdump('设置项目配置[同步小区]>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_set_project_config_log',true);
            fdump($url,'api/log/'.date('Ymd').'/D1/d1_set_project_config_log',true);
            fdump($param,'api/log/'.date('Ymd').'/D1/d1_set_project_config_log',true);
            fdump($authorization,'api/log/'.date('Ymd').'/D1/d1_set_project_config_log',true);
            if (400==$info['code'] && 0==$repeat_post) {
                $msg = $this->d1_refresh_token();
                if (0==$msg['errcode']) {
                    return $this->d1_set_project_config($village_id, $project_uid, 1);
                }
            }
            return $info;
        } else {
            fdump('设置项目配置[同步小区]失败token错误>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_set_project_config_err_log',true);
            fdump($this->userphone,'api/log/'.date('Ymd').'/D1/d1_set_project_config_err_log',true);
            fdump($this->password,'api/log/'.date('Ymd').'/D1/d1_set_project_config_err_log',true);
            fdump($this->app_id,'api/log/'.date('Ymd').'/D1/d1_set_project_config_err_log',true);
            fdump($this->app_secret,'api/log/'.date('Ymd').'/D1/d1_set_project_config_err_log',true);
            fdump($access_token_info,'api/log/'.date('Ymd').'/D1/d1_set_project_config_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '获取失败');
        }
    }

    /**
     * Notes: 同步所有楼栋
     * @param $village_id
     * @param string $project_uid
     * @param string $handle_type
     * @param string $device_id
     * @param int $is_plan
     * @return array|bool
     * @author: wanzy
     * @date_time: 2020/12/24 15:54
     */
    public function d1_project_all_building($village_id, $project_uid='', $handle_type='', $device_id='', $is_plan=1) {
        if (!$project_uid) {
            $db_face_bind_about_info = M('Face_bind_about_info');
            $where_bind = [
                'bind_type' => 0,
                'bind_id' => $village_id,
                'device_id' => 0,
                'device_type' => 21,
            ];
            $project_uid = $db_face_bind_about_info->where($where_bind)->order('id ASC')->getField('third_id');
        }
        if (!$project_uid) {
            return array('errcode' => '1001', 'errmsg' => '参数缺失');
        }
        $where_single = [
            'village_id' => $village_id,
            'status' => 1
        ];
        $single_list = M('House_village_single')->where($where_single)->field('id, village_id, single_name, single_number')->select();
        if (empty($single_list)) {
            return array('errcode' => '1001', 'errmsg' => '该小区缺少楼栋');
        }
        $last_key = count($single_list) - 1;
        $d_face_door_service = D('Face_door_service');
        foreach ($single_list as $key => $val) {
            if ($is_plan) {
                $param = array();
                $param['type'] = 'd1_add_building';
                $param['handle_type'] = $handle_type;
                $param['single_id'] = $val['id'];
                $param['device_id'] = $device_id;
                $param['village_id'] = $village_id;
                $param['project_uid'] = $project_uid;
                $param['single_info'] = $val;
                $unique_id = 'd1_add_building';
                if (isset($param['single_id'])) {
                    $unique_id .='_'.$param['single_id'];
                }
                if (isset($param['village_id'])) {
                    $unique_id .='_'.$param['village_id'];
                }
                if (isset($param['project_uid'])) {
                    $unique_id .='_'.$param['project_uid'];
                }
                if (isset($param['device_id'])) {
                    $unique_id .='_'.$param['device_id'];
                }
                $param['unique_id'] = $unique_id;
                if ($key==$last_key) {
                    $param['is_last'] = true;
                } else {
                    $param['is_last'] = false;
                }
                $d_face_door_service->add_plan($param, 'sub_house_face',-7);
            } else {
                $msg = $this->d1_add_building($val['id'],$village_id, $project_uid, $val);
                if ((!isset($msg['errcode']) || $msg['errcode']==0) && $msg) {
                    $building_uid = $msg;
                    $this->d1_building_all_room($val['id'], $village_id, $project_uid,$building_uid, $handle_type, $device_id, 0);
                }
            }
        }
        return true;
    }

    /**
     * Notes: 添加楼栋
     * @param $single_id
     * @param int $village_id
     * @param string $project_uid
     * @param array $single_info
     * @return array|mixed
     * @author: wanzy
     * @date_time: 2020/12/22 11:02
     */
    public function d1_add_building($single_id,$village_id=0, $project_uid='', $single_info=[], $repeat_post=0) {
        $db_face_bind_about_info = M('Face_bind_about_info');
        $where_bind = [
            'bind_type' => 2,
            'bind_id' => $single_id,
            'device_id' => 0,
            'device_type' => 21,
        ];
        $building_uid = $db_face_bind_about_info->where($where_bind)->order('id ASC')->getField('third_id');
        if ($building_uid) {
            // 先不做编辑  直接调取全部房屋同步   存在值直接返回
            $this->d1_building_all_room($single_id, $village_id, $project_uid,$building_uid, '', 0, 1);
            return $building_uid;
        }
        if (!$single_info) {
            $where_single = [
                'id' => $single_id,
            ];
            $single_info = M('House_village_single')->where($where_single)->find();
        }
        if (!$village_id) {
            $village_id = $single_info['village_id'];
        }
        if (!$project_uid) {
            $where_bind = [
                'bind_type' => 0,
                'bind_id' => $village_id,
                'device_id' => 0,
                'device_type' => 21,
            ];
            $project_uid = $db_face_bind_about_info->where($where_bind)->order('id ASC')->getField('third_id');
        }
        if (!$project_uid) {
            fdump('创建楼栋[同步楼栋]失败 参数缺失>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_add_building_err_log',true);
            fdump($single_id,'api/log/'.date('Ymd').'/D1/d1_add_building_err_log',true);
            fdump($village_id,'api/log/'.date('Ymd').'/D1/d1_add_building_err_log',true);
            fdump($project_uid,'api/log/'.date('Ymd').'/D1/d1_add_building_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '参数缺失');
        }
        if (!$this->userphone) {
            $this->d1_get_config();
        }
        if (!$this->userphone || !$this->password || !$this->app_id || !$this->app_secret) {
            return array('errcode' => '1001', 'errmsg' => '缺少必要配置项');
        }
        $access_token_info = $this->d1_login();

        if (0==$access_token_info['errcode']) {
            $building_name = $single_info['single_name'].'_'.$single_id;
            if ($single_info['single_number']) {
                $building_number = $single_info['single_number'];
            } else {
                $building_number = $single_info['single_name'];
            }
            $building_number = preg_replace($this->pattern_number,'',$building_number);
            if (!$building_number || strlen($building_number)>4) {
                fdump('创建楼栋[同步楼栋]楼栋编号超出4位长度或不存在纯数字>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_add_building_err_log',true);
                fdump($single_id,'api/log/'.date('Ymd').'/D1/d1_add_building_err_log',true);
                fdump($village_id,'api/log/'.date('Ymd').'/D1/d1_add_building_err_log',true);
                fdump($project_uid,'api/log/'.date('Ymd').'/D1/d1_add_building_err_log',true);
                fdump($building_number,'api/log/'.date('Ymd').'/D1/d1_add_building_err_log',true);
                return false;
            }
            $building_number = str_pad($building_number,4,"0",STR_PAD_LEFT);
            $access_token = $access_token_info['access_token'];
            $authorization = $access_token;
            $url = $this->d1_base_url . '/openapi/v3/building';

            $param = [];
            if ($building_uid) {
                $param['uid'] = $building_uid;
                $request = 'PUT';
            } else {
                $param['uid'] = $project_uid;
                $request = 'POST';
            }
            $param['building_name'] = $building_name;
            $param['building_number'] = $building_number;
            $info = $this->curl_post($url, $param, $authorization, $request);
            fdump('创建楼栋[同步楼栋]>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_add_building_log',true);
            fdump($url,'api/log/'.date('Ymd').'/D1/d1_add_building_log',true);
            fdump($param,'api/log/'.date('Ymd').'/D1/d1_add_building_log',true);
            fdump($authorization,'api/log/'.date('Ymd').'/D1/d1_add_building_log',true);
            fdump($info,'api/log/'.date('Ymd').'/D1/d1_add_building_log',true);
            if ($info && $info['code']==200) {
                $data = $info['data'];
                if ($building_uid) {
                    $bind_data = [
                        'bind_name' => $building_name,
                        'third_id' => $building_uid,
                        'last_time' => time(),
                    ];
                    $db_face_bind_about_info->where($where_bind)->save($bind_data);
                } else {
                    $building_uid = $data['uid'];
                    $bind_data = [
                        'bind_type' => 2,
                        'bind_id' => $single_id,
                        'bind_name' => $building_name,
                        'third_id' => $building_uid,
                        'third_info' => serialize($data),
                        'device_type' => 21,
                        'device_id' => 0,
                        'add_time' => time(),
                    ];
                    $db_face_bind_about_info->data($bind_data)->add();
                }
                return $building_uid;
            } elseif (1046==$info['code'] && 0==$repeat_post){
                $param = [
                    'uid' => $project_uid,
                    'building_number' =>$building_number,
                    'page' => 1,
                    'size' => 100
                ];
                return $this->d1_get_building($single_id,$param);
            } elseif (400==$info['code'] && 0==$repeat_post) {
                $msg = $this->d1_refresh_token();
                if (0==$msg['errcode']) {
                    return $this->d1_add_building($single_id,$village_id, $project_uid, $single_info, 1);
                }
            } else {
                fdump('创建楼栋[同步楼栋]失败>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_add_building_post_err_log',true);
                fdump($param,'api/log/'.date('Ymd').'/D1/d1_add_building_post_err_log',true);
                fdump($info,'api/log/'.date('Ymd').'/D1/d1_add_building_post_err_log',true);
                return array('errcode' => '1001', 'errmsg' => $info['msg']);
            }
        } else {
            fdump('创建楼栋[同步楼栋]失败token错误>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_add_building_err_log',true);
            fdump($this->userphone,'api/log/'.date('Ymd').'/D1/d1_add_building_err_log',true);
            fdump($this->password,'api/log/'.date('Ymd').'/D1/d1_add_building_err_log',true);
            fdump($this->app_id,'api/log/'.date('Ymd').'/D1/d1_add_building_err_log',true);
            fdump($this->app_secret,'api/log/'.date('Ymd').'/D1/d1_add_building_err_log',true);
            fdump($access_token_info,'api/log/'.date('Ymd').'/D1/d1_add_building_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '获取失败');
        }
    }

    public function d1_get_building($single_id,$param=[]) {
        $access_token_info = $this->d1_login();
        if (0!=$access_token_info['errcode']) {
            fdump_api(['查询楼栋token失败'.__LINE__, $single_id, $param, $access_token_info],'D1/d1_get_building_err_log',1);
            return array('errcode' => $access_token_info['errcode'], 'errmsg' => $access_token_info['errmsg']);
        }
        $access_token = $access_token_info['access_token'];
        if (empty($param) || !$param['building_number']) {
            fdump_api(['查询参数不齐'.__LINE__, $single_id, $param, $access_token_info],'D1/d1_get_building_err_log',1);
            return array('errcode' => 1001, 'errmsg' => '查询参数不齐');
        }
        $url = $this->d1_base_url . '/openapi/v3/building';
        $authorization = $access_token;
        $request = 'GET';
        $query_param = http_build_query($param);
        $url .= '?'.$query_param;
        $info = $this->curl_post($url, [], $authorization, $request);
        fdump_api(['查询'.__LINE__, $single_id, $param, $url],'D1/d1_get_building_log',1);

        if ($info && $info['code']==200) {
            $data = $info['data'];
            $build_data = $data['data'];
            $total = $data['total'];
            fdump_api(['查询结果数量'.__LINE__, $total],'D1/d1_get_building_log',1);
            if ($build_data) {
                $db_face_bind_about_info = M('Face_bind_about_info');
                $where_bind = [
                    'bind_type' => 2,
                    'bind_id' => $single_id,
                    'device_id' => 0,
                    'device_type' => 21,
                ];
                $building_uid = $db_face_bind_about_info->where($where_bind)->order('id ASC')->getField('third_id');
                foreach ($build_data as $val) {
                    if ($val['building_number']!=$param['building_number']) {
                        continue;
                    } elseif ($val['building_number']==$param['building_number']) {
                        fdump_api(['查询符合条件结果'.__LINE__, $param,$val],'D1/d1_get_building_log',1);
                        $uid = $val['uid'];
                        $name = $val['building_name'];
                        if ($building_uid) {
                            $bind_data = [
                                'bind_name' => $name,
                                'third_id' => $uid,
                                'last_time' => time(),
                            ];
                            $db_face_bind_about_info->where($where_bind)->save($bind_data);
                        } else {
                            $building_uid = $data['uid'];
                            $bind_data = [
                                'bind_type' => 2,
                                'bind_id' => $single_id,
                                'bind_name' => $name,
                                'third_id' => $uid,
                                'third_info' => serialize($val),
                                'device_type' => 21,
                                'device_id' => 0,
                                'add_time' => time(),
                            ];
                            $db_face_bind_about_info->data($bind_data)->add();
                        }
                    }
                }
            }
        }
        return $info;
    }

    /**
     * Notes: 同步一个楼栋下所有房屋
     * @param $single_id
     * @param int $village_id
     * @param string $project_uid
     * @param string $building_uid
     * @param string $handle_type
     * @param string $device_id
     * @param int $is_plan
     * @return array
     * @author: wanzy
     * @date_time: 2020/12/24 16:40
     */
    public function d1_building_all_room($single_id, $village_id=0, $project_uid='',$building_uid='', $handle_type='', $device_id='', $is_plan=1) {
        if (!$project_uid) {
            $db_face_bind_about_info = M('Face_bind_about_info');
            $where_bind = [
                'bind_type' => 0,
                'bind_id' => $village_id,
                'device_id' => 0,
                'device_type' => 21,
            ];
            $project_uid = $db_face_bind_about_info->where($where_bind)->order('id ASC')->getField('third_id');
        }
        if (!$building_uid) {
            $where_bind = [
                'bind_type' => 2,
                'bind_id' => $single_id,
                'device_id' => 0,
                'device_type' => 21,
            ];
            $building_uid = $db_face_bind_about_info->where($where_bind)->order('id ASC')->getField('third_id');
        }
        if (!$project_uid || !$building_uid) {
            fdump('创建楼栋[同步楼栋]失败 参数缺失>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_building_all_room_err_log',true);
            fdump($single_id,'api/log/'.date('Ymd').'/D1/d1_building_all_room_err_log',true);
            fdump($village_id,'api/log/'.date('Ymd').'/D1/d1_building_all_room_err_log',true);
            fdump($project_uid,'api/log/'.date('Ymd').'/D1/d1_building_all_room_err_log',true);
            fdump($building_uid,'api/log/'.date('Ymd').'/D1/d1_building_all_room_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '参数缺失');
        }
        $where_room = [
            'village_id' => $village_id,
            'single_id' => $single_id,
            'status' => ['neq', 0],
            'is_del' => 0,
        ];
        $room_list = M('House_village_user_vacancy')->where($where_room)->field('pigcms_id, village_id, single_id, room, room_number')->select();
        if (empty($room_list)) {
            return array('errcode' => '1001', 'errmsg' => '该楼栋缺少房屋');
        }
        $last_key = count($room_list) - 1;
        $d_face_door_service = D('Face_door_service');
        $msg = '';
        foreach ($room_list as $key => $val) {
            if ($is_plan) {
                $param = array();
                $param['type'] = 'd1_add_room';
                $param['handle_type'] = $handle_type;
                $param['vacancy_id'] = $val['pigcms_id'];
                $param['device_id'] = $device_id;
                $param['village_id'] = $village_id;
                $param['project_uid'] = $project_uid;
                $param['building_uid'] = $building_uid;
                $param['vacancy_info'] = $val;
                $unique_id = 'd1_add_room';
                if (isset($param['vacancy_id'])) {
                    $unique_id .='_'.$param['vacancy_id'];
                }
                if (isset($param['village_id'])) {
                    $unique_id .='_'.$param['village_id'];
                }
                if (isset($param['project_uid'])) {
                    $unique_id .='_'.$param['project_uid'];
                }
                if (isset($param['device_id'])) {
                    $unique_id .='_'.$param['device_id'];
                }
                $param['unique_id'] = $unique_id;
                if ($key==$last_key) {
                    $param['is_last'] = true;
                } else {
                    $param['is_last'] = false;
                }
                $d_face_door_service->add_plan($param, 'sub_house_face',-4);
            } else {
                $msg = $this->d1_add_room($val['pigcms_id'],$project_uid, $building_uid, $val);
                if ((!isset($msg['errcode']) || $msg['errcode']==0) && $msg) {
                    $room_uid = $msg;
                    $this->d1_room_all_user($val['pigcms_id'], $project_uid,$building_uid, $room_uid, $val, $is_plan);
                }
            }
        }
        return true;
    }

    /**
     * Notes: 添加房屋
     * @param $vacancy_id
     * @param int $single_id
     * @param string $project_uid
     * @param string $building_uid
     * @param array $vacancy_info
     * @return array|mixed
     * @author: wanzy
     * @date_time: 2020/12/22 13:23
     */
    public function d1_add_room($vacancy_id, $project_uid='',$building_uid='', $vacancy_info=[], $repeat_post=0) {
        $db_face_bind_about_info = M('Face_bind_about_info');
        $where_bind = [
            'bind_type' => 4,
            'bind_id' => $vacancy_id,
            'device_id' => 0,
            'device_type' => 21,
        ];
        $room_uid = $db_face_bind_about_info->where($where_bind)->order('id ASC')->getField('third_id');
        if ($room_uid) {
            // 房屋暂时也不做编辑 直接同步下方人员信息
            $this->d1_room_all_user($vacancy_id, $project_uid,$building_uid, $room_uid, $vacancy_info, 1);
            return $room_uid;
        }
        if (!$vacancy_info || !$vacancy_info['layer_id'] || !$vacancy_info['village_id'] || !$vacancy_info['single_id'] || !$vacancy_info['floor_id']) {
            $where_vacancy = [
                'pigcms_id' => $vacancy_id,
            ];
            $vacancy_info = M('House_village_user_vacancy')->where($where_vacancy)->find();
        }
        $village_id = $vacancy_info['village_id'];
        if (!$project_uid) {
            $where_bind = [
                'bind_type' => 0,
                'bind_id' => $village_id,
                'device_id' => 0,
                'device_type' => 21,
            ];
            $project_uid = $db_face_bind_about_info->where($where_bind)->order('id ASC')->getField('third_id');
        }
        $single_id = $vacancy_info['single_id'];
        if (!$building_uid) {
            $where_bind = [
                'bind_type' => 2,
                'bind_id' => $single_id,
                'device_id' => 0,
                'device_type' => 21,
            ];
            $building_uid = $db_face_bind_about_info->where($where_bind)->order('id ASC')->getField('third_id');
        }
        if (!$project_uid || !$building_uid) {
            fdump('创建房屋[同步房屋]失败 参数缺失>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_add_room_err_log',true);
            fdump($vacancy_id,'api/log/'.date('Ymd').'/D1/d1_add_room_err_log',true);
            fdump($single_id,'api/log/'.date('Ymd').'/D1/d1_add_room_err_log',true);
            fdump($village_id,'api/log/'.date('Ymd').'/D1/d1_add_room_err_log',true);
            fdump($project_uid,'api/log/'.date('Ymd').'/D1/d1_add_room_err_log',true);
            fdump($building_uid,'api/log/'.date('Ymd').'/D1/d1_add_room_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '参数缺失');
        }
        if (!$this->userphone) {
            $this->d1_get_config();
        }
        if (!$this->userphone || !$this->password || !$this->app_id || !$this->app_secret) {
            return array('errcode' => '1001', 'errmsg' => '缺少必要配置项');
        }
        $access_token_info = $this->d1_login();

        if (0==$access_token_info['errcode']) {
            $room_name = $vacancy_info['room'];
            if ($vacancy_info['room_number']) {
                $room_number = $vacancy_info['room_number'];
            } else {
                $room_number = $vacancy_info['room'];
            }
            if ($vacancy_id<0 && !$room_name) {
                $room_name = '工作人员房屋';
            }
            if ($vacancy_id<0 && !$room_number) {
                $room_number = '0000';
            }
            if (2==strlen($room_number)){
                $layer_info = M('House_village_layer')->where(['id' => $vacancy_info['layer_id']])->field('id,layer_name,layer_number')->find();
                if ($layer_info['layer_number'] && 2==strlen($layer_info['layer_number'])) {
                    $room_number = $layer_info['layer_number'] . $room_number;
                }
            }
            $access_token = $access_token_info['access_token'];
            $authorization = $access_token;
            $url = $this->d1_base_url . '/openapi/v3/room';

            $param = [];
            $param['project_uid'] = $project_uid;
            $param['building_uid'] = $building_uid;
            if ($room_uid) {
                $param['room_uid'] = $room_uid;
                $request = 'PUT';
            } else {
                $request = 'POST';
            }
            $db_house_village_floor = M('House_village_floor');
            $floor_info = $db_house_village_floor->where(['floor_id'=> $vacancy_info['floor_id']])->field('floor_id,floor_name,floor_number')->find();
            if ($floor_info['floor_name']) {
                if(preg_match('/[\x7f-\xff]/', $floor_info['floor_name'])) {
                }else{
                    $floor_info['floor_name'] = $floor_info['floor_name'].'单元';
                }
                $room_name = $floor_info['floor_name'].$room_name;
            }
            if ($floor_info['floor_number']) {
                $room_number = $floor_info['floor_number'].$room_number;
            }
            $param['room_name'] = $room_name;
            $param['room_number'] = $room_number;
            $info = $this->curl_post($url, $param, $authorization, $request);
            fdump('创建房屋[同步房屋]>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_add_room_log',true);
            fdump($vacancy_id,'api/log/'.date('Ymd').'/D1/d1_add_room_log',true);
            fdump($url,'api/log/'.date('Ymd').'/D1/d1_add_room_log',true);
            fdump($param,'api/log/'.date('Ymd').'/D1/d1_add_room_log',true);
            fdump($authorization,'api/log/'.date('Ymd').'/D1/d1_add_room_log',true);
            fdump($info,'api/log/'.date('Ymd').'/D1/d1_add_room_log',true);
            if ($info && $info['code']==200) {
                $data = $info['data'];
                $room_uid = $data['uid'];
                $bind_data = [
                    'bind_type' => 4,
                    'bind_id' => $vacancy_id,
                    'bind_name' => $room_number,
                    'third_id' => $room_uid,
                    'third_info' => serialize($data),
                    'device_type' => 21,
                    'device_id' => 0,
                    'add_time' => time(),
                ];
                $db_face_bind_about_info->data($bind_data)->add();
                return $room_uid;
            } elseif (400==$info['code'] && 0==$repeat_post) {
                $msg = $this->d1_refresh_token();
                if (0==$msg['errcode']) {
                    return $this->d1_add_room($vacancy_id, $project_uid,$building_uid, $vacancy_info, 1);
                }
            } else {
                fdump('创建房屋[同步房屋]失败>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_add_room_post_err_log',true);
                fdump($param,'api/log/'.date('Ymd').'/D1/d1_add_room_post_err_log',true);
                fdump($info,'api/log/'.date('Ymd').'/D1/d1_add_room_post_err_log',true);
                return array('errcode' => '1001', 'errmsg' => $info['msg']);
            }
        } else {
            fdump('创建楼栋[同步楼栋]失败token错误>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_add_room_err_log',true);
            fdump($this->userphone,'api/log/'.date('Ymd').'/D1/d1_add_room_err_log',true);
            fdump($this->password,'api/log/'.date('Ymd').'/D1/d1_add_room_err_log',true);
            fdump($this->app_id,'api/log/'.date('Ymd').'/D1/d1_add_room_err_log',true);
            fdump($this->app_secret,'api/log/'.date('Ymd').'/D1/d1_add_room_err_log',true);
            fdump($access_token_info,'api/log/'.date('Ymd').'/D1/d1_add_room_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '获取失败');
        }
    }

    /**
     * Notes: 同步一个房屋下所有用户
     * @param $vacancy_id
     * @param string $project_uid
     * @param string $building_uid
     * @param string $room_uid
     * @param array $vacancy_info
     * @param int $is_plan
     * @return array|bool
     * @author: wanzy
     * @date_time: 2020/12/24 17:18
     */
    public function d1_room_all_user($vacancy_id, $project_uid='',$building_uid='', $room_uid='', $vacancy_info=[], $is_plan=1) {
        if (!$vacancy_info) {
            $where_vacancy = [
                'pigcms_id' => $vacancy_id,
            ];
            $vacancy_info = M('House_village_user_vacancy')->where($where_vacancy)->find();
        }
        $village_id = $vacancy_info['village_id'];
        if (!$project_uid) {
            $db_face_bind_about_info = M('Face_bind_about_info');
            $where_bind = [
                'bind_type' => 0,
                'bind_id' => $village_id,
                'device_id' => 0,
                'device_type' => 21,
            ];
            $project_uid = $db_face_bind_about_info->where($where_bind)->order('id ASC')->getField('third_id');
        }
        $single_id = $vacancy_info['single_id'];
        if (!$building_uid) {
            $where_bind = [
                'bind_type' => 2,
                'bind_id' => $single_id,
                'device_id' => 0,
                'device_type' => 21,
            ];
            $building_uid = $db_face_bind_about_info->where($where_bind)->order('id ASC')->getField('third_id');
        }
        if (!$room_uid) {
            $where_bind = [
                'bind_type' => 4,
                'bind_id' => $vacancy_id,
                'device_id' => 0,
                'device_type' => 21,
            ];
            $room_uid = $db_face_bind_about_info->where($where_bind)->order('id ASC')->getField('third_id');
        }
        if (!$project_uid || !$building_uid || !$room_uid) {
            fdump('同步房屋下所有人员失败 参数缺失>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_room_all_user_err_log',true);
            fdump($single_id,'api/log/'.date('Ymd').'/D1/d1_room_all_user_err_log',true);
            fdump($village_id,'api/log/'.date('Ymd').'/D1/d1_room_all_user_err_log',true);
            fdump($project_uid,'api/log/'.date('Ymd').'/D1/d1_room_all_user_err_log',true);
            fdump($building_uid,'api/log/'.date('Ymd').'/D1/d1_room_all_user_err_log',true);
            fdump($room_uid,'api/log/'.date('Ymd').'/D1/d1_room_all_user_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '参数缺失');
        }
        $where_user = [
            'village_id' => $village_id,
            'single_id' => $single_id,
            'vacancy_id' => $vacancy_id,
            'type' => ['in', [0,1,2,3]],
            'status' => 1
        ];
        $field='pigcms_id,uid, village_id, single_id,vacancy_id, name, phone, type, property_endtime, id_card, parent_id';
        $user_list = M('House_village_user_bind')->where($where_user)->field($field)->select();
        if (empty($user_list)) {
            fdump('同步房屋下所有人员失败 无人员数据>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_room_all_user_err_log',true);
            fdump($vacancy_id,'api/log/'.date('Ymd').'/D1/d1_room_all_user_err_log',true);
            fdump($single_id,'api/log/'.date('Ymd').'/D1/d1_room_all_user_err_log',true);
            fdump($village_id,'api/log/'.date('Ymd').'/D1/d1_room_all_user_err_log',true);
            fdump($project_uid,'api/log/'.date('Ymd').'/D1/d1_room_all_user_err_log',true);
            fdump($building_uid,'api/log/'.date('Ymd').'/D1/d1_room_all_user_err_log',true);
            fdump($room_uid,'api/log/'.date('Ymd').'/D1/d1_room_all_user_err_log',true);
            fdump($user_list,'api/log/'.date('Ymd').'/D1/d1_room_all_user_err_log',true);
            fdump(M('House_village_user_bind')->getDbError(),'api/log/'.date('Ymd').'/D1/d1_room_all_user_err_log',true);
            fdump(M('House_village_user_bind')->_sql(),'api/log/'.date('Ymd').'/D1/d1_room_all_user_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '该房屋暂无人员');
        }
        $last_key = count($user_list) - 1;
        $d_face_door_service = D('Face_door_service');
        foreach ($user_list as $key => $val) {
            if ($is_plan) {
                $param = array();
                $param['type'] = 'd1_add_user';
                $param['bind_id'] = $val['pigcms_id'];
                $param['village_id'] = $village_id;
                $param['project_uid'] = $project_uid;
                $param['building_uid'] = $building_uid;
                $param['room_uid'] = $room_uid;
                $param['bind_info'] = $val;
                $unique_id = 'd1_add_user';
                $unique_id .='_'.$param['bind_id'];
                $param['unique_id'] = $unique_id;
                if ($key==$last_key) {
                    $param['is_last'] = true;
                } else {
                    $param['is_last'] = false;
                }
                $d_face_door_service->add_plan($param, 'sub_house_face',-2);
            } else {
                $this->d1_add_user($val['pigcms_id'],$project_uid, $building_uid,$room_uid, $val);
            }
        }
        return true;
    }

    /**
     * Notes: 多个用户同步
     * @param $device_id
     * @param $bind_arr_id
     * @return bool
     * @author: wanzy
     * @date_time: 2020/12/24 17:27
     */
    public function d1_arr_person_msg($device_id, $bind_arr_id, $is_plan=1,$orderGroupId='') {
        $d_face_door_service = D('Face_door_service');
        fdump('多个用户同步>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_arr_person_msg_log',true);
        fdump($device_id,'api/log/'.date('Ymd').'/D1/d1_arr_person_msg_log',true);
        fdump($bind_arr_id,'api/log/'.date('Ymd').'/D1/d1_arr_person_msg_log',true);
        foreach ($bind_arr_id as $val) {
            if (!$val) {
                continue;
            }
            if ($is_plan) {
                $param = array();
                $param['type'] = 'd1_add_user';
                $param['bind_id'] = $val;
                if ($orderGroupId) {
                    $param['orderGroupId'] = $orderGroupId;
                }
                $unique_id = 'd1_add_user';
                $unique_id .='_'.$param['bind_id'];
                $param['unique_id'] = $unique_id;
                $d_face_door_service->add_plan($param, 'sub_house_face',-2);
            } else {
                $this->d1_add_user($val);
            }
        }
        return true;
    }

    /**
     * Notes: 添加/编辑业主
     * @param $bind_id
     * @param string $project_uid
     * @param string $building_uid
     * @param string $room_uid
     * @param array $bind_info
     * @return array|bool|string
     * @author: wanzy
     * @date_time: 2020/12/22 16:57
     */
    public function d1_add_user($bind_id, $project_uid='',$building_uid='', $room_uid='', $bind_info=[], $repeat_post=0,$otherData=[]) {
        ini_set('memory_limit', '2048M');
        $db_face_user_bind_device = M('Face_user_bind_device');
        $where_bind = [
            'bind_type' => 0,
            'bind_id' => $bind_id,
            'device_id' => 0,
            'device_type' => 21,
        ];
        $user_info = $db_face_user_bind_device->where($where_bind)->order('id ASC')->field('person_id, group_id, info')->find();
        if ($user_info) {
            $user_uid = $user_info['person_id'];
            if ($user_info['group_id']) {
                $userroom_uid = $user_info['group_id'];
            }
            if ($user_info['info']) {
                $user_data = unserialize($user_info['info']);
                if (!$userroom_uid && $user_data['userroom_uid']) {
                    $userroom_uid = $user_data['userroom_uid'];
                }
            }
        }
        if (!$bind_info) {
            $where_user = [
                'pigcms_id' => $bind_id,
            ];
            $bind_info = M('House_village_user_bind')->where($where_user)->find();
        }
        $db_face_bind_about_info = M('Face_bind_about_info');
        $village_id = $bind_info['village_id'];
        if (!$project_uid) {
            $where_village = [
                'bind_type' => 0,
                'bind_id' => $village_id,
                'device_id' => 0,
                'device_type' => 21,
            ];
            $project_uid = $db_face_bind_about_info->where($where_village)->order('id ASC')->getField('third_id');
        }
        if ($bind_info['type']==4) {
            $single_id = -$village_id;
        } else {
            $single_id = $bind_info['single_id'];
        }
        if (!$single_id && $bind_info['vacancy_id']) {
            $where_vacancy = [
                'pigcms_id' => $bind_info['vacancy_id'],
            ];
            $single_id = M('House_village_user_vacancy')->where($where_vacancy)->getField('single_id');
        }
        $where_single = [
            'bind_type' => 2,
            'bind_id' => $single_id,
            'device_id' => 0,
            'device_type' => 21,
        ];
        $building_uid = $db_face_bind_about_info->where($where_single)->order('id ASC')->getField('third_id');
        if ($bind_info['type']==4) {
            $vacancy_id = -($village_id+1);
        } else {
            $vacancy_id = $bind_info['vacancy_id'];
        }
        $where_room = [
            'bind_type' => 4,
            'bind_id' => $vacancy_id,
            'device_id' => 0,
            'device_type' => 21,
        ];
        $room_info = $db_face_bind_about_info->where($where_room)->order('id ASC')->field('third_id, bind_name')->find();
        if ($room_info && $room_info['third_id']) {
            $room_uid = $room_info['third_id'];
        }
        if (!$project_uid || !$building_uid || !$room_uid) {
            fdump('添加/编辑业主[同步用户]失败 参数缺失>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
            fdump($bind_id,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
            fdump($bind_info,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
            fdump($single_id,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
            fdump($village_id,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
            fdump($project_uid,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
            fdump($building_uid,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
            fdump($room_uid,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
            if ($project_uid && $building_uid && !$room_uid) {
                $d_face_door_service = D('Face_door_service');
                $param = array();
                $param['type'] = 'd1_add_room';
                $param['handle_type'] = 'single_syn';
                $param['vacancy_id'] = $vacancy_id;
                $param['village_id'] = $village_id;
                $param['project_uid'] = $project_uid;
                $param['building_uid'] = $building_uid;
                $unique_id = 'd1_add_room';
                if (isset($param['vacancy_id'])) {
                    $unique_id .='_'.$param['vacancy_id'];
                }
                if (isset($param['village_id'])) {
                    $unique_id .='_'.$param['village_id'];
                }
                if (isset($param['project_uid'])) {
                    $unique_id .='_'.$param['project_uid'];
                }
                $param['unique_id'] = $unique_id;
                $d_face_door_service->add_plan($param, 'sub_house_face',-4);
                fdump('添加/编辑业主[同步用户]去添加房屋>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
                fdump($param,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
            } elseif($project_uid && !$building_uid) {
                $d_face_door_service = D('Face_door_service');
                $param = array();
                $param['type'] = 'd1_add_building';
                $param['handle_type'] = 'single_syn';
                $param['single_id'] = $single_id;
                $param['village_id'] = $village_id;
                $param['project_uid'] = $project_uid;
                $unique_id = 'd1_add_building';
                if (isset($param['single_id'])) {
                    $unique_id .='_'.$param['single_id'];
                }
                if (isset($param['village_id'])) {
                    $unique_id .='_'.$param['village_id'];
                }
                if (isset($param['project_uid'])) {
                    $unique_id .='_'.$param['project_uid'];
                }
                if (isset($param['device_id'])) {
                    $unique_id .='_'.$param['device_id'];
                }
                $param['unique_id'] = $unique_id;
                $d_face_door_service->add_plan($param, 'sub_house_face',-7);
                fdump('添加/编辑业主[同步用户]去添加楼栋>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
                fdump($param,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
            } elseif(!$project_uid) {
                $d_face_door_service = D('Face_door_service');
                $param = array();
                $param['type'] = 'd1_create_project';
                $param['handle_type'] = 'single_syn';
                $param['village_id'] = $village_id;
                $unique_id = 'd1_create_project';
                if (isset($param['village_id'])) {
                    $unique_id .='_'.$param['village_id'];
                }
                $param['unique_id'] = $unique_id;
                $d_face_door_service->add_plan($param, 'sub_house_face', -8);
                fdump('添加/编辑业主[同步用户]去添加小区>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
                fdump($param,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
            }
            return array('errcode' => '1001', 'errmsg' => '参数缺失');
        }

        if (!$this->userphone) {
            $this->d1_get_config();
        }
        if (!$this->userphone || !$this->password || !$this->app_id || !$this->app_secret) {
            fdump('添加/编辑业主[同步用户]缺少必要配置项>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
            fdump([$bind_id, $project_uid,$building_uid, $room_uid, $bind_info, $repeat_post],'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '缺少必要配置项');
        }
        $access_token_info = $this->d1_login();

        if (0==$access_token_info['errcode']) {
            $access_token = $access_token_info['access_token'];
            $authorization = $access_token;
            $url = $this->d1_base_url . '/openapi/v3/host';

            $param = [];
            if ($user_uid) {
                $param['user_uid'] = $user_uid;
                $request = 'PUT';
                if ($userroom_uid) {
                    $param['uid'] = $userroom_uid;
                } elseif ($user_data && $user_data['add_param']) {
                    // 更新业主的时候需要查询下关联uid
                    $search = [
                        'page' => 1,
                        'size' => 10
                    ];
                    $add_param = $user_data['add_param'];
                    $search['project_uid'] = $add_param['project_uid'] ? $add_param['project_uid'] : $project_uid;
                    $search['building_uid'] = $add_param['building_uid'] ? $add_param['building_uid'] : $building_uid;
                    if ($add_param && $add_param['user_realname']) {
                        $search['user_realname'] = $add_param['user_realname'];
                    }
                    if ($add_param && $add_param['ur_identity']) {
                        $search['ur_identity'] = $add_param['ur_identity'];
                    }
                    if ($add_param && $add_param['user_id_card']) {
                        $search['user_id_card'] = $add_param['user_id_card'];
                    }
                    if ($add_param && $add_param['user_phone']) {
                        $search['user_phone'] = $add_param['user_phone'];
                    }
                    if ($room_info && $room_info['bind_name']) {
                        $search['room_number'] = $room_info['bind_name'];
                    }
                    $query_param = http_build_query($search);
                    $url .= '?'.$query_param;
                    $search_info = $this->curl_post($url, $query_param, $authorization, 'GET');
                    fdump('更新业主[同步业主]查询>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_add_user_search_log',true);
                    fdump($url,'api/log/'.date('Ymd').'/D1/d1_add_user_search_log',true);
                    fdump($query_param,'api/log/'.date('Ymd').'/D1/d1_add_user_search_log',true);
                    fdump($authorization,'api/log/'.date('Ymd').'/D1/d1_add_user_search_log',true);
                    fdump($search_info,'api/log/'.date('Ymd').'/D1/d1_add_user_search_log',true);
                    if ($search_info && $search_info['data'] && $search_info['data']['data']) {
                        $user = reset($search_info['data']['data']);
                        if ($user['uid']) {
                            $userroom_uid = $user['uid'];
                            $param['uid'] = $userroom_uid;
                        } else {
                            fdump('创建业主[同步业主]缺少业主和房屋关系唯一uid(业主列表中获取)>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
                            fdump($user_data,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
                            fdump($url,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
                            fdump($query_param,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
                            fdump($search_info,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
                        }
                    } elseif (400==$search_info['code'] && 0==$repeat_post) {
                        $msg = $this->d1_refresh_token();
                        if (0==$msg['errcode']) {
                            $this->d1_add_user($bind_id, $project_uid,$building_uid, $room_uid, $bind_info, 1);
                        }
                    } else {
                        fdump('创建业主[同步业主]缺少业主和房屋关系唯一uid(业主列表中获取)>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
                        fdump($user_data,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
                        fdump($url,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
                        fdump($query_param,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
                        fdump($search_info,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
                        return array('errcode' => '1001', 'errmsg' => '缺少必要参数');
                    }
                } else {
                    fdump('创建业主[同步业主]缺少业主和房屋关系唯一uid(业主列表中获取)>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);

                    fdump($single_id,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
                    fdump($village_id,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
                    fdump($project_uid,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
                    fdump($building_uid,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
                    fdump($room_uid,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
                    fdump($user_info,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
                    fdump($user_data,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
                    return array('errcode' => '1001', 'errmsg' => '缺少必要参数');
                }
            } else {
                $param['project_uid'] = $project_uid;
                $param['building_uid'] = $building_uid;
                $param['room_uid'] = $room_uid;
                $request = 'POST';
            }
            $param['user_realname'] = $bind_info['name'] ? $bind_info['name'].'_'.$bind_id : '用户_'.$bind_id;
            if (!$bind_info['phone']) {
                fdump('创建业主[同步业主]缺少参数[手机号]错误>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
                fdump($bind_info,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
                $data_set = [
                    'face_img_status' => 2,
                    'face_img_reason' => '缺少手机号',
                ];
                D('House_village_user_bind')->where(['pigcms_id' => $bind_id])->data($data_set)->save();
                return array('errcode' => '1001', 'errmsg' => '缺少必要参数');
            }
            $param['user_phone'] = $bind_info['phone'];
            // 业主身份 1业主，3业主家庭成员
            if ($bind_info['type']==4) {
                $param['ur_identity'] = 3;
            } else {
                $param['ur_identity'] = (in_array($bind_info['type'],[0,3])) ? 1 : 3;
            }

            $userEndTime = '2082643200';// 默认时间 2035-12-31
            // 小区信息
            $village_info = D('House_village')->field(true)->where(array('village_id'=>$village_id))->find();

            if(isset($bind_info['open_door_day']) && $bind_info['open_door_day']>0){
                $village_info['owe_property_open_door_day']=$bind_info['open_door_day'];
            }
            if (in_array($bind_info['type'],[1,2]) && $bind_info['parent_id']) {
                // 查询业主
                $parent_user = D('House_village_user_bind')->where(array('pigcms_id'=>$bind_info['parent_id']))->find();
                if ($parent_user['property_endtime']>0) {
                    if ($village_info['owe_property_open_door'] && $village_info['owe_property_open_door_day']>0) {
                        $userEndTime = $parent_user['property_endtime']+($village_info['owe_property_open_door_day'] * 86400);
                    } else {
                        $userEndTime = $parent_user['property_endtime'];
                    }
                }
            } elseif (in_array($bind_info['type'],[1,2]) && !$bind_info['parent_id']) {
                // 查询业主
                $parent_user = D('House_village_user_bind')->where(array('vacancy_id'=>$bind_info['vacancy_id'],'type'=>['in', [0,3]], 'status'=>1))->find();
                if ($parent_user['property_endtime']>0) {
                    if ($village_info['owe_property_open_door'] && $village_info['owe_property_open_door_day']>0) {
                        $userEndTime = $parent_user['property_endtime']+($village_info['owe_property_open_door_day'] * 86400);
                    } else {
                        $userEndTime = $parent_user['property_endtime'];
                    }
                }
                $where_user = [
                    'pigcms_id' => $bind_info['pigcms_id'],
                ];
                M('House_village_user_bind')->where($where_user)->setField('parent_id', $parent_user['pigcms_id']);
            } elseif (4==$bind_info['type']) {
                // 暂不处理
                $userEndTime = '2082643200';// 默认时间 2035-12-31
            } elseif (in_array($bind_info['type'],[0,3])) {
                if ($bind_info['property_endtime']>0) {
                    if ($village_info['owe_property_open_door'] && $village_info['owe_property_open_door_day']>0) {
                        $userEndTime = $bind_info['property_endtime']+($village_info['owe_property_open_door_day'] * 86400);
                    } else {
                        $userEndTime = $bind_info['property_endtime'];
                    }
                }
            } else {
                // 暂不处理
                return false;
            }
            $now_time = time();
            if (intval($now_time)>intval($userEndTime)) {
                $userEndTime = $now_time + 60;
            }
            $param['ur_expired'] = $userEndTime;
            if (trim($bind_info['id_card'])) {
                $param['user_id_card'] = trim($bind_info['id_card']);
            }

            $d_house_face_img = D('House_face_img');
            if ($bind_info['uid']) {
                $img_info = $d_house_face_img->where(array('uid' => $bind_info['uid'], 'status' => ['in', [0,3]], 'img_url' => array('neq', '')))->order('add_time ASC')->limit(3)->field('id, status, img_url')->select();
                if ($img_info) {
                    $word_encryption = '';
                    $face_msg = [];
                    foreach ($img_info as $val) {
                        $img_url = $val['img_url'];
                        if (3==$val['status']) {
                            if (!$word_encryption) {
                                import('@.ORG.face.word_encryption');
                                $word_encryption = new word_encryption();
                            }
                            // 加密的图片解密
                            $img_url = $word_encryption->text_decrypt($img_url);
                        }
                        $upload_img = $this->d1_qiniu_file($img_url, $bind_info['uid']);

                        if ((!isset($upload_img['errcode']) || $upload_img['errcode']==0) && $upload_img['face_image']) {
                            $face_image = $upload_img['face_image'];
                            $face_img_reason = '';
                            $face_msg = $upload_img;
                            break;
                        } else {
                            $face_img_reason = $upload_img['errmsg'] ?  $upload_img['errmsg'] : '同步失败';
                        }
                    }
                }
            }
            if ($face_image) {
                $param['face_image'] = $face_image;
            } elseif ($face_img_reason) {
                fdump('创建业主[同步业主]人脸图片错误>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_add_user_face_err_log',true);
                fdump($img_url,'api/log/'.date('Ymd').'/D1/d1_add_user_face_err_log',true);
                fdump($face_msg,'api/log/'.date('Ymd').'/D1/d1_add_user_face_err_log',true);
                fdump($face_img_reason,'api/log/'.date('Ymd').'/D1/d1_add_user_face_err_log',true);
            }

            $info = $this->curl_post($url, $param, $authorization, $request);
            fdump('创建业主[同步业主]>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_add_user_log',true);
            fdump($img_url,'api/log/'.date('Ymd').'/D1/d1_add_user_log',true);
            fdump($url,'api/log/'.date('Ymd').'/D1/d1_add_user_log',true);
            fdump($param,'api/log/'.date('Ymd').'/D1/d1_add_user_log',true);
            fdump($authorization,'api/log/'.date('Ymd').'/D1/d1_add_user_log',true);
            fdump($request,'api/log/'.date('Ymd').'/D1/d1_add_user_log',true);
            fdump($info,'api/log/'.date('Ymd').'/D1/d1_add_user_log',true);
            if ($info && $info['code']==200) {
                $data = $info['data'];
                if (!$user_uid) {
                    $user_uid = $data['user_uid'];
                }
                $bind_data = [
                    'bind_type' => 0,
                    'bind_id' => $bind_id,
                    'device_id' => 0,
                    'device_type' => 21,
                    'person_id' => $user_uid,
                    'info' => serialize($data),
                ];
                if (!$user_uid) {
                    $data['add_param'] = $param;
                    $bind_data['info'] = serialize($data);
                } elseif($user_data) {
                    $user_data['add_param'] = $param;
                    $bind_data['info'] = serialize($user_data);
                } else {
                    $data['add_param'] = $param;
                    $bind_data['info'] = serialize($data);
                }
                if ($data['userroom_uid']) {
                    $bind_data['group_id'] = $data['userroom_uid'];
                } elseif($userroom_uid) {
                    $bind_data['group_id'] = $userroom_uid;
                }
                if ($face_image) {
                    $bind_data['face_img'] = $face_image;
                }
                if ($face_msg) {
                    $bind_data['face_txt'] = serialize($face_msg);
                }
                if (!$user_info) {
                    $bind_data['add_time'] = time();
                    $db_face_user_bind_device->data($bind_data)->add();
                } else {
                    $bind_data['last_time'] = time();
                    $where_bind = [
                        'bind_type' => 0,
                        'bind_id' => $bind_id,
                        'device_id' => 0,
                        'device_type' => 21,
                    ];
                    $save = $db_face_user_bind_device->where($where_bind)->save($bind_data);
                    if (!$save) {
                        fdump('创建业主[同步业主]记录信息错误>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
                        fdump($save,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
                        fdump($where_bind,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
                        fdump($bind_data,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
                        fdump($db_face_user_bind_device->getDbError(),'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
                    }
                }

                // 上传了图片情况下
                if (!$face_image && $face_img_reason) {
                    $data_set = [
                        'face_img_status' => 2,
                        'face_img_reason' => $face_img_reason,
                    ];
                    D('House_village_user_bind')->where(['pigcms_id' => $bind_id])->data($data_set)->save();
                } else {
                    $data_set = [
                        'face_img_status' => 1,
                        'face_img_reason' => '',
                    ];
                    D('House_village_user_bind')->where(['pigcms_id' => $bind_id])->data($data_set)->save();
                }
                $apply_param = [
                    'village_id' => $village_id,
                    'single_id' => $single_id,
                    'floor_id' => $bind_info['floor_id'] ? $bind_info['floor_id'] : 0,
                    'user_realname' => $param['user_realname'],
                    'record_identity' =>$param['ur_identity'],
                    'phone' => $param['user_phone'],
                    'project_uid' => $project_uid,
                    'building_uid' => $building_uid,
                    'room_uid' => $room_uid,
                    'user_uid' => $user_uid
                ];
                $this->d1_apply_user_gate($bind_id, $apply_param);
                return $user_uid;
            } elseif (400==$info['code'] && 0==$repeat_post) {
                $msg = $this->d1_refresh_token();
                if (0==$msg['errcode']) {
                    return $this->d1_add_user($bind_id, $project_uid,$building_uid, $room_uid, $bind_info, 1);
                }
            } elseif (1242==$info['code']) {
                $param_msg = [
                    'village_id' => $village_id,
                    'single_id' => $single_id,
                    'project_uid' => $project_uid,
                    'building_uid' => $building_uid,
                    'room_uid' => $room_uid,
                    'user_realname' => $param['user_realname'],
                    'ur_identity' => $param['ur_identity'],
                    'user_phone' => $param['user_phone'],
                    'room_number' => $room_info['bind_name'],
                ];
                $user_uid = $this->d1_get_user($bind_id, $param_msg);
                if ($user_uid && $user_uid['errcode']) {
                    $data_set = [
                        'face_img_status' => 2,
                        'face_img_reason' =>$info['msg']?$info['msg']:'用户房屋关系已经存在,请重新同步下',
                    ];
                    D('House_village_user_bind')->where(['pigcms_id' => $bind_id])->data($data_set)->save();
                } else {
                    $data_set = [
                        'face_img_status' => 1,
                        'face_img_reason' => '',
                    ];
                    D('House_village_user_bind')->where(['pigcms_id' => $bind_id])->data($data_set)->save();
                }
                return $user_uid;
            } else {
                fdump('创建业主[同步业主]>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_add_user_post_err_log',true);
                fdump($param,'api/log/'.date('Ymd').'/D1/d1_add_user_post_err_log',true);
                fdump($info,'api/log/'.date('Ymd').'/D1/d1_add_user_post_err_log',true);
                fdump($face_image,'api/log/'.date('Ymd').'/D1/d1_add_user_post_err_log',true);
                fdump($face_img_reason,'api/log/'.date('Ymd').'/D1/d1_add_user_post_err_log',true);
                if (!$face_img_reason) {
                    $face_img_reason = $info['msg'];
                }
                $data_set = [
                    'face_img_status' => 2,
                    'face_img_reason' => $face_img_reason,
                ];
                D('House_village_user_bind')->where(['pigcms_id' => $bind_id])->data($data_set)->save();
                return array('errcode' => '1001', 'errmsg' => $info['msg']);
            }
        } else {
            fdump('创建业主[同步业主]失败token错误>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
            fdump($this->userphone,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
            fdump($this->password,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
            fdump($this->app_id,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
            fdump($this->app_secret,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
            fdump($access_token_info,'api/log/'.date('Ymd').'/D1/d1_add_user_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '获取失败');
        }
    }

    /**
     * Notes: 查询
     * @param $bind_id
     * @param $param
     * @return array
     * @author: wanzy
     * @date_time: 2021/1/5 11:04
     */
    public function d1_get_user($bind_id, $param) {
        if (!$param['village_id'] || !$param['single_id'] || !$param['project_uid'] || !$param['building_uid'] || !$param['user_realname'] || !isset($param['ur_identity']) || !$param['user_phone'] || !$param['room_number']) {
            fdump('查询业主[同步业主]缺少参数错误>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_get_user_err_log',true);
            fdump($bind_id,'api/log/'.date('Ymd').'/D1/d1_get_user_err_log',true);
            fdump($param,'api/log/'.date('Ymd').'/D1/d1_get_user_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '缺少必要参数');
        }
        if (!$this->userphone) {
            $this->d1_get_config();
        }
        if (!$this->userphone || !$this->password || !$this->app_id || !$this->app_secret) {
            return array('errcode' => '1001', 'errmsg' => '缺少必要配置项');
        }
        $access_token_info = $this->d1_login();

        if (0==$access_token_info['errcode']) {
            $access_token = $access_token_info['access_token'];
            $authorization = $access_token;
            $url = $this->d1_base_url . '/openapi/v3/host';
            $param_msg = [];
            $request = 'GET';
            $page = 1;
            $size = 10;
            $param_msg['page'] = $page;
            $param_msg['size'] = $size;
            $param_msg['project_uid'] = $param['project_uid'];
            $param_msg['building_uid'] = $param['building_uid'];
            $param_msg['user_phone'] = $param['user_phone'];
            $param_msg['room_number'] = $param['room_number'];
            $query_param = http_build_query($param_msg);
            $url .= '?'.$query_param;
            $info = $this->curl_post($url, [], $authorization, $request);
            fdump('查询业主>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_get_user_log',true);
            fdump($url,'api/log/'.date('Ymd').'/D1/d1_get_user_log',true);
            fdump($param,'api/log/'.date('Ymd').'/D1/d1_get_user_log',true);
            fdump($authorization,'api/log/'.date('Ymd').'/D1/d1_get_user_log',true);
            fdump($info,'api/log/'.date('Ymd').'/D1/d1_get_user_log',true);

            if ($info && $info['code']==200) {
                $data_msg = $info['data'];
                if ($data_msg['data']) {
                    $data = $data_msg['data'];
                    if (is_array($data)) {
                        $msg = reset($data);
                    }
                    if ($msg) {
                        $bind_uid = $msg['uid'];
                        return $this->d1_user_info($bind_id, $param, $bind_uid);
                    }
                }
            } else {
                return array('errcode' => '1001', 'errmsg' => '查询错误');
            }
        } else {
            fdump('创建业主[同步业主]失败token错误>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_get_user_err_log',true);
            fdump($this->userphone,'api/log/'.date('Ymd').'/D1/d1_get_user_err_log',true);
            fdump($this->password,'api/log/'.date('Ymd').'/D1/d1_get_user_err_log',true);
            fdump($this->app_id,'api/log/'.date('Ymd').'/D1/d1_get_user_err_log',true);
            fdump($this->app_secret,'api/log/'.date('Ymd').'/D1/d1_get_user_err_log',true);
            fdump($access_token_info,'api/log/'.date('Ymd').'/D1/d1_get_user_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '获取失败');
        }
    }

    /**
     * Notes: 查看详细信息
     * @param $bind_id
     * @param $param
     * @param $uid
     * @return array|mixed
     * @author: wanzy
     * @date_time: 2021/1/5 14:50
     */
    public function d1_user_info($bind_id, $param, $uid) {
        if (!$param['village_id'] || !$param['single_id'] || !$param['project_uid'] || !$param['building_uid'] || !$param['user_realname'] || !isset($param['ur_identity']) || !$param['user_phone'] || !$param['room_number']) {
            fdump('查询业主详情[同步业主]缺少参数错误>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_user_info_err_log',true);
            fdump($bind_id,'api/log/'.date('Ymd').'/D1/d1_user_info_err_log',true);
            fdump($param,'api/log/'.date('Ymd').'/D1/d1_user_info_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '缺少必要参数');
        }
        if (!$this->userphone) {
            $this->d1_get_config();
        }
        if (!$this->userphone || !$this->password || !$this->app_id || !$this->app_secret) {
            return array('errcode' => '1001', 'errmsg' => '缺少必要配置项');
        }
        $access_token_info = $this->d1_login();

        if (0==$access_token_info['errcode']) {
            $access_token = $access_token_info['access_token'];
            $authorization = $access_token;
            $url = $this->d1_base_url . '/openapi/v3/host/info';
            $param_msg = [];
            $request = 'GET';
            $param_msg['uid'] = $uid;
            $query_param = http_build_query($param_msg);
            $url .= '?'.$query_param;
            $info = $this->curl_post($url, [], $authorization, $request);
            fdump('查询业主详情>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_user_info_log',true);
            fdump($url,'api/log/'.date('Ymd').'/D1/d1_user_info_log',true);
            fdump($param,'api/log/'.date('Ymd').'/D1/d1_user_info_log',true);
            fdump($authorization,'api/log/'.date('Ymd').'/D1/d1_user_info_log',true);
            fdump($info,'api/log/'.date('Ymd').'/D1/d1_user_info_log',true);
            if ($info && $info['code']==200) {
                $data = $info['data'];

                $db_face_user_bind_device = M('Face_user_bind_device');
                $where_bind = [
                    'bind_type' => 0,
                    'bind_id' => $bind_id,
                    'device_id' => 0,
                    'device_type' => 21,
                ];
                $user_info = $db_face_user_bind_device->where($where_bind)->order('id ASC')->field('person_id, group_id, info')->find();
                $user_uid = $data['user_uid'];
                $bind_data = [
                    'bind_type' => 0,
                    'bind_id' => $bind_id,
                    'device_id' => 0,
                    'device_type' => 21,
                    'person_id' => $user_uid,
                    'info' => serialize($data),
                ];
                $data['add_param'] = $param;
                $bind_data['info'] = serialize($data);
                $bind_data['group_id'] = $uid;
                if ($data['face_image_url']) {
                    $bind_data['face_img'] = $data['face_image_url'];
                }
                if (!$user_info) {
                    $bind_data['add_time'] = time();
                    $db_face_user_bind_device->data($bind_data)->add();
                } else {
                    $bind_data['last_time'] = time();
                    $where_bind = [
                        'bind_type' => 0,
                        'bind_id' => $bind_id,
                        'device_id' => 0,
                        'device_type' => 21,
                    ];
                    $save = $db_face_user_bind_device->where($where_bind)->save($bind_data);
                    if (!$save) {
                        fdump('查询业主详情[同步业主]记录信息错误>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_user_info_err_log',true);
                        fdump($save,'api/log/'.date('Ymd').'/D1/d1_user_info_err_log',true);
                        fdump($where_bind,'api/log/'.date('Ymd').'/D1/d1_user_info_err_log',true);
                        fdump($bind_data,'api/log/'.date('Ymd').'/D1/d1_user_info_err_log',true);
                        return array('errcode' => '1001', 'errmsg' => '记录错误');
                    }
                }
                $apply_param = [
                    'village_id' => $param['village_id'],
                    'single_id' => $param['single_id'],
                    'floor_id' => $param['floor_id'] ? $param['floor_id'] : 0,
                    'user_realname' => $param['user_realname'],
                    'record_identity' =>$param['ur_identity'],
                    'phone' => $param['user_phone'],
                    'project_uid' => $param['project_uid'],
                    'building_uid' => $param['building_uid'],
                    'room_uid' => $param['room_uid'],
                    'user_uid' => $user_uid
                ];
                $this->d1_apply_user_gate($bind_id, $apply_param);
                return $user_uid;
            } else {
                return array('errcode' => '1001', 'errmsg' => '查询错误');
            }
        } else {
            fdump('查询业主详情[同步业主]失败token错误>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_user_info_err_log',true);
            fdump($this->userphone,'api/log/'.date('Ymd').'/D1/d1_user_info_err_log',true);
            fdump($this->password,'api/log/'.date('Ymd').'/D1/d1_user_info_err_log',true);
            fdump($this->app_id,'api/log/'.date('Ymd').'/D1/d1_user_info_err_log',true);
            fdump($this->app_secret,'api/log/'.date('Ymd').'/D1/d1_user_info_err_log',true);
            fdump($access_token_info,'api/log/'.date('Ymd').'/D1/d1_user_info_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '获取失败');
        }
    }

    /**
     * Notes: 添加物业工作人员使用的楼栋和房屋
     * @param $village_id
     * @param string $project_uid
     * @param int $is_plan
     * @return array|mixed
     * @author: wanzy
     * @date_time: 2020/12/24 17:55
     */
    public function d1_add_work_msg($village_id, $project_uid='', $is_plan=1) {
        // 添加物业工作人员使用的楼栋 和 房屋
        $single_id = -$village_id;
        $single_info = [
            'id' => $single_id,
            'village_id' => $village_id,
            'single_name' => '工作楼栋',
            'single_number' => '0000',
        ];
        $msg = $this->d1_add_building($single_id,$village_id, $project_uid, $single_info);
        if (isset($msg['errcode']) && $msg['errcode']>0) {
            $msg_room['err_type'] = 'single';
            return $msg;
        } else {
            $building_uid = $msg;
        }
        $pigcms_id = -($village_id+1);
        $room = [
            'pigcms_id' => $pigcms_id,
            'village_id' => $village_id,
            'single_id' => $single_id,
            'room' => '工作小屋',
            'room_number' => '0000',
        ];
        $msg_room = $this->d1_add_room($pigcms_id,$project_uid, $building_uid, $room);
        if (isset($msg_room['errcode']) && $msg_room['errcode']>0) {
            $msg_room['err_type'] = 'room';
            return $msg_room;
        } else {
            $room_uid = $msg_room;
        }
        $data = [
            'building_uid' => $building_uid,
            'room_uid' => $room_uid,
        ];
        $d_face_door_service = D('Face_door_service');
        if ($is_plan) {
            $param = array();
            $param['type'] = 'd1_add_work';
            $param['village_id'] = $village_id;
            $param['project_uid'] = $project_uid;
            $param['building_uid'] = $building_uid;
            $param['room_uid'] = $room_uid;
            $unique_id = 'd1_add_work';
            if (isset($param['village_id'])) {
                $unique_id .='_'.$param['village_id'];
            }
            if (isset($param['project_uid'])) {
                $unique_id .='_'.$param['project_uid'];
            }
            if (isset($param['building_uid'])) {
                $unique_id .='_'.$param['building_uid'];
            }
            if (isset($param['room_uid'])) {
                $unique_id .='_'.$param['room_uid'];
            }
            if (isset($param['device_id'])) {
                $unique_id .='_'.$param['device_id'];
            }
            $param['unique_id'] = $unique_id;
            $d_face_door_service->add_plan($param, 'sub_house_face',-2);
        } else {
            $this->d1_add_work($village_id, $project_uid, $building_uid, $room_uid, $is_plan);
        }
        return $data;
    }

    /**
     * Notes: 同步同坐人员至设备
     * @param $village_id
     * @param string $project_uid
     * @param string $building_uid
     * @param string $room_uid
     * @param int $is_plan
     * @return array|bool
     * @author: wanzy
     * @date_time: 2020/12/24 19:02
     */
    public function d1_add_work($village_id, $project_uid='', $building_uid='', $room_uid='', $is_plan=1) {
        $db_face_bind_about_info = M('Face_bind_about_info');
        if (!$project_uid) {
            $where_bind = [
                'bind_type' => 0,
                'bind_id' => $village_id,
                'device_id' => 0,
                'device_type' => 21,
            ];
            $project_uid = $db_face_bind_about_info->where($where_bind)->order('id ASC')->getField('third_id');
        }
        $single_id = -$village_id;
        if (!$building_uid) {
            $where_bind = [
                'bind_type' => 2,
                'bind_id' => $single_id,
                'device_id' => 0,
                'device_type' => 21,
            ];
            $building_uid = $db_face_bind_about_info->where($where_bind)->order('id ASC')->getField('third_id');
        }
        $vacancy_id = -($village_id+1);
        if (!$room_uid) {
            $where_bind = [
                'bind_type' => 4,
                'bind_id' => $vacancy_id,
                'device_id' => 0,
                'device_type' => 21,
            ];
            $room_uid = $db_face_bind_about_info->where($where_bind)->order('id ASC')->getField('third_id');
        }
        if (!$project_uid || !$building_uid || !$room_uid) {
            fdump('同步工作人员失败 参数缺失>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_add_work_err_log',true);
            fdump($single_id,'api/log/'.date('Ymd').'/D1/d1_add_work_err_log',true);
            fdump($village_id,'api/log/'.date('Ymd').'/D1/d1_add_work_err_log',true);
            fdump($project_uid,'api/log/'.date('Ymd').'/D1/d1_add_work_err_log',true);
            fdump($building_uid,'api/log/'.date('Ymd').'/D1/d1_add_work_err_log',true);
            fdump($room_uid,'api/log/'.date('Ymd').'/D1/d1_add_work_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '参数缺失');
        }
        $where_user = [
            'village_id' => $village_id,
            'type' => 4,
            'status' => 1
        ];
        $field='pigcms_id, uid, village_id, single_id,vacancy_id, name, phone, type, property_endtime, id_card, parent_id';
        $user_list = M('House_village_user_bind')->where($where_user)->field($field)->select();
        if (empty($user_list)) {
            return array('errcode' => '1001', 'errmsg' => '该房屋暂无人员');
        }
        $last_key = count($user_list) - 1;
        $d_face_door_service = D('Face_door_service');
        foreach ($user_list as $key => $val) {
            if ($is_plan) {
                $param = array();
                $param['type'] = 'd1_add_user';
                $param['bind_id'] = $val['pigcms_id'];
                $param['village_id'] = $village_id;
                $param['project_uid'] = $project_uid;
                $param['building_uid'] = $building_uid;
                $param['room_uid'] = $room_uid;
                $param['bind_info'] = $val;
                $unique_id = 'd1_add_user';
                $unique_id .='_'.$param['bind_id'];
                $param['unique_id'] = $unique_id;
                if ($key==$last_key) {
                    $param['is_last'] = true;
                } else {
                    $param['is_last'] = false;
                }
                $d_face_door_service->add_plan($param, 'sub_house_face',-2);
            } else {
                $this->d1_add_user($val['pigcms_id'],$project_uid, $building_uid,$room_uid, $val);
            }
        }
        return true;
    }

    /**
     * Notes: 业主注销[同步删除]
     * @param $bind_id
     * @param string $user_uid
     * @return array|bool
     * @author: wanzy
     * @date_time: 2020/12/22 17:30
     */
    public function d1_del_user($bind_id, $user_uid='', $repeat_post=0) {
        $db_face_user_bind_device = M('Face_user_bind_device');
        $where_bind = [
            'bind_type' => 0,
            'bind_id' => $bind_id,
            'device_id' => 0,
            'device_type' => 21,
        ];
        $user_info = $db_face_user_bind_device->where($where_bind)->order('id ASC')->field('person_id, group_id, info')->find();
        if ($user_info) {
            $user_uid = $user_info['person_id'];
            if ($user_info['group_id']) {
                $userroom_uid = $user_info['group_id'];
            } elseif ($user_info['info']) {
                $user_data = unserialize($user_info['info']);
                if ($user_data['userroom_uid']) {
                    $userroom_uid = $user_data['userroom_uid'];
                }
            }

        }
        if (!$user_uid) {
            $user_uid = $user_info['person_id'];
        }
        if (!$user_uid || !$userroom_uid) {
            fdump('业主注销[同步删除]失败 参数缺失>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_del_user_err_log',true);
            fdump($bind_id,'api/log/'.date('Ymd').'/D1/d1_del_user_err_log',true);
            fdump($user_uid,'api/log/'.date('Ymd').'/D1/d1_del_user_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '参数缺失');
        }
        if (!$this->userphone) {
            $this->d1_get_config();
        }
        if (!$this->userphone || !$this->password || !$this->app_id || !$this->app_secret) {
            return array('errcode' => '1001', 'errmsg' => '缺少必要配置项');
        }
        $access_token_info = $this->d1_login();

        if (0==$access_token_info['errcode']) {
            $access_token = $access_token_info['access_token'];
            $authorization = $access_token;
            $url = $this->d1_base_url . '/openapi/v3/host';
            $param = [];
            $request = 'DELETE';
            $url .= '?uid='.$userroom_uid;
            $info = $this->curl_post($url, $param, $authorization, $request);
            fdump('业主注销[同步删除]>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_del_user_log',true);
            fdump($url,'api/log/'.date('Ymd').'/D1/d1_del_user_log',true);
            fdump($param,'api/log/'.date('Ymd').'/D1/d1_del_user_log',true);
            fdump($authorization,'api/log/'.date('Ymd').'/D1/d1_del_user_log',true);
            fdump($info,'api/log/'.date('Ymd').'/D1/d1_del_user_log',true);
            if ($info && $info['code']==200) {
                $where_bind = [
                    'bind_type' => 0,
                    'bind_id' => $bind_id,
                    'device_id' => 0,
                    'device_type' => 21,
                ];
                $del = $db_face_user_bind_device->where($where_bind)->delete();
                return true;
            } elseif (400==$info['code'] && 0==$repeat_post) {
                $msg = $this->d1_refresh_token();
                if (0==$msg['errcode']) {
                    return $this->d1_del_user($bind_id, $user_uid, 1);
                }
            } else {
                fdump('业主注销[同步删除]失败>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_del_user_post_err_log',true);
                fdump($bind_id,'api/log/'.date('Ymd').'/D1/d1_del_user_post_err_log',true);
                fdump($user_uid,'api/log/'.date('Ymd').'/D1/d1_del_user_post_err_log',true);
                fdump($param,'api/log/'.date('Ymd').'/D1/d1_del_user_post_err_log',true);
                fdump($info,'api/log/'.date('Ymd').'/D1/d1_del_user_post_err_log',true);
                return array('errcode' => '1001', 'errmsg' => $info['msg']);
            }
        } else {
            fdump('业主注销[同步删除]失败token错误>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_del_user_err_log',true);
            fdump($this->userphone,'api/log/'.date('Ymd').'/D1/d1_del_user_err_log',true);
            fdump($this->password,'api/log/'.date('Ymd').'/D1/d1_del_user_err_log',true);
            fdump($this->app_id,'api/log/'.date('Ymd').'/D1/d1_del_user_err_log',true);
            fdump($this->app_secret,'api/log/'.date('Ymd').'/D1/d1_del_user_err_log',true);
            fdump($access_token_info,'api/log/'.date('Ymd').'/D1/d1_del_user_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '获取失败');
        }
    }

    /**
     * Notes: 上传文件
     * @param $filePath
     * @param $id
     * @return array|bool|mixed|string
     * @author: wanzy
     * @date_time: 2020/12/25 16:59
     */
    public function d1_qiniu_file($filePath, $id, $repeat_post=0) {
        if (!$this->userphone) {
            $this->d1_get_config();
        }
        if (!$this->userphone || !$this->password || !$this->app_id || !$this->app_secret) {
            return array('errcode' => '1001', 'errmsg' => '缺少必要配置项');
        }
        $access_token_info = $this->d1_login();

        if (0==$access_token_info['errcode']) {
            $access_token = $access_token_info['access_token'];
            $authorization = $access_token;
            $url = $this->d1_base_url . '/openapi/v3/face/token';
            $param = [];
            $request = 'GET';
            $info = $this->curl_post($url, $param, $authorization, $request);
            if ($info['code']==200) {
                $data = $info['data'];
                $token = $data['token'];
                $file_name = $data['fileName'] ? $data['fileName'] : '';
                if ($token && $file_name) {
                    import('@.ORG.d1_qiniu');
                    $d1_qiniu = new d1_qiniu($token);
                    $file_handle = new file_handle();
                    if($file_handle->check_open_oss()) {
                        $filePath = thumb_img($filePath,480,600,'', true);
                        $img_path = D('Face_door_service')->face_img_to_new_file($filePath, $id, $file_name);
                    } else {
                        $filePath = thumb_img($filePath,480,600,'', true);
                        $img_path = str_replace(C('config.site_url'),'',$filePath);
                    }
                    fdump_api(['msg' => '转存图片到本地', '$filePath' => $filePath, '$id' => $id, '$file_name' => $file_name, '$img_path' => $img_path, ],'D1/d1_qiniu_file_log',1);

                    $img = $_SERVER['DOCUMENT_ROOT'] . $img_path;
                    $msg = $d1_qiniu->upload($img, $file_name);
                    if (!$msg['error']) {
                        // 上传成功后 调用人脸检查接口
                        $check_msg = $this->d1_qiniu_face_check($file_name);
                        if ($check_msg['code']==200) {
                            // 检查返回正常 直接返回图片名称
                            $data_arr = [
                                'id' => $id,
                                'filePath' => $filePath,
                                'face_image' => $file_name,
                            ];
                            return $data_arr;
                        } else {
                            fdump_api(['msg' => '人脸图片上传检查返回错误', '$filePath' => $filePath, '$id' => $id, '$file_name' => $file_name, '$img' => $img, '$msg' => $msg, '$check_msg' => $check_msg, ],'D1/d1_qiniu_file_err_log',1);
                            return array('errcode' => '1001', 'errmsg' => $check_msg['msg']);
                        }
                    } else {
                        fdump_api(['msg' => '人脸图片上传返回错误', '$filePath' => $filePath, '$id' => $id, '$file_name' => $file_name, '$img' => $img, '$msg' => $msg, ],'D1/d1_qiniu_file_err_log',1);
                        return array('errcode' => '1001', 'errmsg' => $msg['msg']);
                    }
                } else {
                    fdump_api(['msg' => '人脸图片上传获取token返回值不全', '$url' => $url, '$param' => $param, '$authorization' => $authorization, '$request' => $request, '$info' => $info, ],'D1/d1_qiniu_file_err_log',1);
                    return array('errcode' => '1001', 'errmsg' => '返回参数不全');
                }
            } elseif (400==$info['code'] && 0==$repeat_post) {
                $msg = $this->d1_refresh_token();
                if (0==$msg['errcode']) {
                    return $this->d1_qiniu_file($filePath, $id, 1);
                }
            } else {
                fdump_api(['msg' => '人脸图片上传 失败', '$url' => $url, '$param' => $param, '$authorization' => $authorization, '$request' => $request, '$info' => $info, ],'D1/d1_qiniu_file_err_log',1);
                return array('errcode' => '1001', 'errmsg' => $info['msg']);
            }
        } else {
            fdump_api(['msg' => '人脸图片上传 失败token错误', '$userphone' => $this->userphone, '$password' => $this->password, '$app_id' => $this->app_id, '$app_secret' => $this->app_secret, '$access_token_info' => $access_token_info, ],'D1/d1_qiniu_file_err_log',1);
            return array('errcode' => '1001', 'errmsg' => '获取失败');
        }

    }

    /**
     * Notes: 获取人脸图片链接
     * @param $file_name
     * @return array|bool|mixed
     * @author: wanzy
     * @date_time: 2020/12/25 16:01
     */
    public function d1_qiniu_face_geturl($file_name, $repeat_post=0) {
        if (!$file_name) {
            return false;
        }
        if (!$this->userphone) {
            $this->d1_get_config();
        }
        if (!$this->userphone || !$this->password || !$this->app_id || !$this->app_secret) {
            return array('errcode' => '1001', 'errmsg' => '缺少必要配置项');
        }
        $access_token_info = $this->d1_login();

        if (0==$access_token_info['errcode']) {
            $access_token = $access_token_info['access_token'];
            $authorization = $access_token;
            $url = $this->d1_base_url . '/openapi/v3/face/geturl';
            $param = [];
            $request = 'GET';
            $url .= "?fileName={$file_name}";
            $info = $this->curl_post($url, $param, $authorization, $request);
            if ($info['code']==200) {
                $data = $info['data'];
                return $data;
            } elseif (400==$info['code'] && 0==$repeat_post) {
                $msg = $this->d1_refresh_token();
                if (0==$msg['errcode']) {
                    return $this->d1_qiniu_face_geturl($file_name, 1);
                }
            } else {
                return false;
            }
        } else {
            fdump('人脸照片获取上传后的链接 失败token错误>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_qiniu_face_geturl_err_log',true);
            fdump($this->userphone,'api/log/'.date('Ymd').'/D1/d1_qiniu_face_geturl_err_log',true);
            fdump($this->password,'api/log/'.date('Ymd').'/D1/d1_qiniu_face_geturl_err_log',true);
            fdump($this->app_id,'api/log/'.date('Ymd').'/D1/d1_qiniu_face_geturl_err_log',true);
            fdump($this->app_secret,'api/log/'.date('Ymd').'/D1/d1_qiniu_face_geturl_err_log',true);
            fdump($access_token_info,'api/log/'.date('Ymd').'/D1/d1_qiniu_face_geturl_err_log',true);
            return false;
        }
    }

    /**
     * Notes: 人脸照片检测
     * @param $file_name
     * @return array|bool|mixed
     * @author: wanzy
     * @date_time: 2020/12/25 16:03
     */
    public function d1_qiniu_face_check($file_name, $repeat_post=0) {
        if (!$file_name) {
            return false;
        }
        if (!$this->userphone) {
            $this->d1_get_config();
        }
        if (!$this->userphone || !$this->password || !$this->app_id || !$this->app_secret) {
            return array('errcode' => '1001', 'errmsg' => '缺少必要配置项');
        }
        $access_token_info = $this->d1_login();

        if (0==$access_token_info['errcode']) {
            $access_token = $access_token_info['access_token'];
            $authorization = $access_token;
            $url = $this->d1_base_url . '/openapi/v3/face/check';
            $param = [];
            $request = 'POST';
            $param['face_image'] = $file_name;
            $info = $this->curl_post($url, $param, $authorization, $request);
            if (400==$info['code'] && 0==$repeat_post) {
                $msg = $this->d1_refresh_token();
                if (0==$msg['errcode']) {
                    return $this->d1_qiniu_face_check($file_name, 1);
                }
            }
            return $info;
        } else {
            fdump('人脸照片检测[人脸检查是否合格]失败token错误>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_qiniu_face_check_err_log',true);
            fdump($this->userphone,'api/log/'.date('Ymd').'/D1/d1_qiniu_face_check_err_log',true);
            fdump($this->password,'api/log/'.date('Ymd').'/D1/d1_qiniu_face_check_err_log',true);
            fdump($this->app_id,'api/log/'.date('Ymd').'/D1/d1_qiniu_face_check_err_log',true);
            fdump($this->app_secret,'api/log/'.date('Ymd').'/D1/d1_qiniu_face_check_err_log',true);
            fdump($access_token_info,'api/log/'.date('Ymd').'/D1/d1_qiniu_face_check_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '获取失败');
        }
    }

    /**
     * Notes:添加/编辑设备
     * @param $device_id
     * @param string $project_uid
     * @param array $building
     * @param array $device_info
     * @return array|bool|mixed
     * @author: wanzy
     * @date_time: 2020/12/22 20:26
     */
    public function d1_add_gate($device_id, $project_uid='',$building=[], $device_info = [], $repeat_post=0) {
        $db_house_face_device = M('House_face_device');
        $where_device = [
            'device_id' => $device_id
        ];
        $device_info = $db_house_face_device->where($where_device)->find();
        $db_face_bind_about_info = M('Face_bind_about_info');
        $village_id = $device_info['village_id'];
        if (!$project_uid) {
            $where_bind = [
                'bind_type' => 0,
                'bind_id' => $village_id,
                'device_id' => 0,
                'device_type' => 21,
            ];
            $project_uid = $db_face_bind_about_info->where($where_bind)->order('id ASC')->getField('third_id');
        }
        $long = '117.2285309';
        $lat = '31.8291397';
        if (!$building) {
            if ($device_info['floor_id']==-1) {
                // 设备类型 1:单元机 2:围墙机
                $gate_type = 2;
                $db_house_village_single = M('House_village_single');
                $where_single = [
                    'village_id' => $village_id,
                    'status' => 1
                ];
                $single_arr = $db_house_village_single->where($where_single)->getField('id', true);
                // 是否存在公共区域
                if ($device_info['public_area_id']) {
                    $db_house_village_public_area = M('House_village_public_area');
                    $where_public_area = [
                        'public_area_id' => $device_info['public_area_id']
                    ];
                    $public_area = $db_house_village_public_area->where($where_public_area)->find();
                    if ($public_area['lng'] && $public_area['lat']) {
                        $long = $public_area['lng'];
                        $lat = $public_area['lat'];
                    }
                }
            } else {
                // 设备类型 1:单元机 2:围墙机
                $gate_type = 2; // 由于存在多个楼栋  单元机子楼栋和物业工作人员楼栋 所以还是得使用围墙机
                $db_house_village_floor = M('House_village_floor');
                $where_floor = [
                    'floor_id' => $device_info['floor_id']
                ];
                $floor_info = $db_house_village_floor->where($where_floor)->find();
                $single_arr = [];
                if ($floor_info['single_id']) {
                    $single_arr[] = $floor_info['single_id'];
                }
                if ($floor_info['long'] && $floor_info['lat']) {
                    $long = $floor_info['long'];
                    $lat = $floor_info['lat'];
                }
            }

            if (empty($single_arr)) {
                fdump('设备添加[同步设备]没有相关楼栋>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_add_gate_err_log',true);
                fdump($device_id,'api/log/'.date('Ymd').'/D1/d1_add_gate_err_log',true);
                fdump($project_uid,'api/log/'.date('Ymd').'/D1/d1_add_gate_err_log',true);
                fdump( M('House_village_single')->getDbError(),'api/log/'.date('Ymd').'/D1/d1_add_gate_err_log',true);
                fdump( M('House_village_single')->_sql(),'api/log/'.date('Ymd').'/D1/d1_add_gate_err_log',true);
                return false;
            } else {
                $single_arr[] = -$village_id; // 每台设备加上 物业工作人员的楼栋
                $where_bind = [
                    'bind_type' => 2,
                    'bind_id' => ['in', $single_arr],
                    'device_id' => 0,
                    'device_type' => 21,
                ];
                $building = $db_face_bind_about_info->where($where_bind)->order('id ASC')->getField('third_id', true);
                if (!$building) {
                    fdump('设备添加[同步设备]缺少必传参数>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_add_gate_err_log',true);
                    fdump($device_id,'api/log/'.date('Ymd').'/D1/d1_add_gate_err_log',true);
                    fdump($project_uid,'api/log/'.date('Ymd').'/D1/d1_add_gate_err_log',true);
                    fdump($db_face_bind_about_info->getDbError(),'api/log/'.date('Ymd').'/D1/d1_add_gate_err_log',true);
                    fdump($db_face_bind_about_info->_sql(),'api/log/'.date('Ymd').'/D1/d1_add_gate_err_log',true);
                }
            }
        }
        if (!$project_uid || !$building) {
            fdump('设备添加[同步设备]缺少必传参数>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_add_gate_err_log',true);
            fdump($device_id,'api/log/'.date('Ymd').'/D1/d1_add_gate_err_log',true);
            fdump($project_uid,'api/log/'.date('Ymd').'/D1/d1_add_gate_err_log',true);
            fdump(M('House_village_single')->getDbError(),'api/log/'.date('Ymd').'/D1/d1_add_gate_err_log',true);
            fdump(M('House_village_single')->_sql(),'api/log/'.date('Ymd').'/D1/d1_add_gate_err_log',true);
            return false;
        }

        if (!$this->userphone) {
            $this->d1_get_config();
        }
        if (!$this->userphone || !$this->password || !$this->app_id || !$this->app_secret) {
            return array('errcode' => '1001', 'errmsg' => '缺少必要配置项');
        }
        $access_token_info = $this->d1_login();

        if (0==$access_token_info['errcode']) {
            $access_token = $access_token_info['access_token'];
            $authorization = $access_token;
            $url = $this->d1_base_url . '/openapi/v3/gate';
            $param = [];
            $gate_gps = $long.','.$lat;
            $gate_network_method = $device_info['network_method'] ? $device_info['network_method'] : 'WIFI';
            $gate_network_operator = $device_info['network_operator'] ? $device_info['network_operator'] : 'DX';
            if ($device_info['ai_device_sn']) {
                // 通不过 编辑
                $param['uid'] = $device_info['ai_device_sn'];
                $request = 'PUT';
            } else {
                $request = 'POST';
            }
            $param['project_uid'] = $project_uid;
            $param['building'] = $building;
            $param['gate_name'] = '人脸门禁_'.$device_info['device_id'];
            $param['gate_guid'] = $device_info['device_sn'];
            $param['gate_alias'] = $device_info['device_name'].'_'.$device_info['device_id'];
            $param['gate_gps'] = $gate_gps;
            $param['gate_type'] = $gate_type;
            $param['gate_network_method'] = $gate_network_method;
            $param['gate_network_operator'] = $gate_network_operator;


            $info = $this->curl_post($url, $param, $authorization, $request);
            fdump('设备添加[同步设备]>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_add_gate_log',true);
            fdump($request,'api/log/'.date('Ymd').'/D1/d1_add_gate_log',true);
            fdump($url,'api/log/'.date('Ymd').'/D1/d1_add_gate_log',true);
            fdump($param,'api/log/'.date('Ymd').'/D1/d1_add_gate_log',true);
            fdump($authorization,'api/log/'.date('Ymd').'/D1/d1_add_gate_log',true);
            fdump($info,'api/log/'.date('Ymd').'/D1/d1_add_gate_log',true);
            fdump($device_id,'api/log/'.date('Ymd').'/D1/d1_add_gate_log',true);

            if ($info && $info['code']==200) {
                $data = $info['data'];
                if ($data['uid'] && $device_id) {
                    $ai_device_sn = $data['uid'];
                    $where_device = [
                        'device_id' => $device_id
                    ];
                    $data_save = [
                        'ai_device_sn' => $ai_device_sn,
                        'login_time' => time(),
                        'device_info' => serialize($data)
                    ];
                    $db_house_face_device->where($where_device)->save($data_save);
                } elseif (isset($param['uid']) && $param['uid'] && $device_id) {
                    $ai_device_sn = $param['uid'];
                    $where_device = [
                        'device_id' => $device_id
                    ];
                    $data_save = [
                        'ai_device_sn' => $ai_device_sn,
                        'login_time' => time()
                    ];
                    $db_house_face_device->where($where_device)->save($data_save);
                } elseif ($data['uid'] && $param['gate_guid']) {
                    $ai_device_sn = $data['uid'];
                    $where_device = [
                        'device_sn' => $param['gate_guid']
                    ];
                    $data_save = [
                        'ai_device_sn' => $ai_device_sn,
                        'login_time' => time(),
                        'device_info' => serialize($data)
                    ];
                    $db_house_face_device->where($where_device)->save($data_save);
                } elseif (isset($param['uid']) && $param['uid'] && $param['gate_guid']) {
                    $ai_device_sn = $param['uid'];
                    $where_device = [
                        'device_sn' => $param['gate_guid']
                    ];
                    $data_save = [
                        'ai_device_sn' => $ai_device_sn,
                        'login_time' => time(),
                        'device_info' => serialize($data)
                    ];
                    $db_house_face_device->where($where_device)->save($data_save);
                }

                // 设备添加成功后查询下
                $this->d1_get_gate_list($device_id, $project_uid, $device_info);
                // 设备添加成功后处理后续功能
                $this->add_data($device_id, $device_info, $project_uid);
                return $ai_device_sn;
            } elseif (400==$info['code'] && 0==$repeat_post) {
                $msg = $this->d1_refresh_token();
                if (0==$msg['errcode']) {
                    return $this->d1_add_gate($device_id, $project_uid,$building, $device_info, 1);
                }
            } elseif ($info['code']==1243 || $info['msg']=='设备ID已经存在'){
                return $this->d1_get_gate_list($device_id, $project_uid, $device_info);
            } else {
                return array('errcode' => '1001', 'errmsg' => $info['msg']);
            }

        } else {
            fdump('设备添加[同步设备]失败token错误>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_add_gate_err_log',true);
            fdump($this->userphone,'api/log/'.date('Ymd').'/D1/d1_add_gate_err_log',true);
            fdump($this->password,'api/log/'.date('Ymd').'/D1/d1_add_gate_err_log',true);
            fdump($this->app_id,'api/log/'.date('Ymd').'/D1/d1_add_gate_err_log',true);
            fdump($this->app_secret,'api/log/'.date('Ymd').'/D1/d1_add_gate_err_log',true);
            fdump($access_token_info,'api/log/'.date('Ymd').'/D1/d1_add_gate_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '获取失败');
        }
    }

    /**
     * Notes: 查询下设备信息
     * @param $device_id
     * @param string $project_uid
     * @param array $device_info
     * @return array|bool|mixed
     * @author: wanzy
     * @date_time: 2020/12/28 9:23
     */
    public function d1_get_gate_list($device_id, $project_uid='', $device_info = [], $repeat_post=0) {
        $db_house_face_device = M('House_face_device');
        if (empty($device_info)) {
            $where_device = [
                'device_id' => $device_id
            ];
            $device_info = $db_house_face_device->where($where_device)->find();
        }
        $db_face_bind_about_info = M('Face_bind_about_info');
        $village_id = $device_info['village_id'];
        if (!$project_uid) {
            $where_bind = [
                'bind_type' => 0,
                'bind_id' => $village_id,
                'device_id' => 0,
                'device_type' => 21,
            ];
            $project_uid = $db_face_bind_about_info->where($where_bind)->order('id ASC')->getField('third_id');
        }
        if (!$project_uid) {
            fdump('获取设备信息 缺少必传参数>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_get_gate_list_err_log',true);
            fdump($device_id,'api/log/'.date('Ymd').'/D1/d1_get_gate_list_err_log',true);
            fdump($project_uid,'api/log/'.date('Ymd').'/D1/d1_get_gate_list_err_log',true);
            fdump(M('House_village_single')->getDbError(),'api/log/'.date('Ymd').'/D1/d1_get_gate_list_err_log',true);
            fdump(M('House_village_single')->_sql(),'api/log/'.date('Ymd').'/D1/d1_get_gate_list_err_log',true);
            return false;
        }

        if (!$this->userphone) {
            $this->d1_get_config();
        }
        if (!$this->userphone || !$this->password || !$this->app_id || !$this->app_secret) {
            return array('errcode' => '1001', 'errmsg' => '缺少必要配置项');
        }
        $access_token_info = $this->d1_login();

        if (0==$access_token_info['errcode']) {
            $access_token = $access_token_info['access_token'];
            $authorization = $access_token;
            $url = $this->d1_base_url . '/openapi/v3/gate';
            $param = [];
            $request = 'GET';
            $param['project_uid'] = $project_uid;
            $param['gate_name'] = '人脸门禁_'.$device_info['device_id'];
            $param['gate_guid'] = $device_info['device_sn'];

            $info = $this->curl_post($url, $param, $authorization, $request);
            fdump('获取设备信息 >>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_get_gate_list_log',true);
            fdump($request,'api/log/'.date('Ymd').'/D1/d1_get_gate_list_log',true);
            fdump($url,'api/log/'.date('Ymd').'/D1/d1_get_gate_list_log',true);
            fdump($param,'api/log/'.date('Ymd').'/D1/d1_get_gate_list_log',true);
            fdump($authorization,'api/log/'.date('Ymd').'/D1/d1_get_gate_list_log',true);
            fdump($info,'api/log/'.date('Ymd').'/D1/d1_get_gate_list_log',true);

            if ($info && $info['code']==200) {
                $data = $info['data'];
                if ($data['data']) {
                    $list = $data['data'];
                    if ($list) {
                        foreach ($list as $val) {
                            if ($val['gate_name']==$param['gate_name'] && $param['gate_guid']==$val['gate_guid']) {
                                $where_device = [
                                    'device_id' => $device_id
                                ];
                                $ai_device_sn = $val['uid'];
                                $data_save = [
                                    'ai_device_sn' => $ai_device_sn,
                                    'login_time' => time(),
                                    'device_info' => serialize($val)
                                ];
                                $db_house_face_device->where($where_device)->save($data_save);
                            }
                        }
                    }
                }
                return $data;
            } elseif (400==$info['code'] && 0==$repeat_post) {
                $msg = $this->d1_refresh_token();
                if (0==$msg['errcode']) {
                    return $this->d1_get_gate_list($device_id, $project_uid, $device_info, 1);
                }
            } else {
                return array('errcode' => '1001', 'errmsg' => $info['msg']);
            }

        } else {
            fdump('获取设备信息 失败token错误>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_get_gate_list_err_log',true);
            fdump($this->userphone,'api/log/'.date('Ymd').'/D1/d1_get_gate_list_err_log',true);
            fdump($this->password,'api/log/'.date('Ymd').'/D1/d1_get_gate_list_err_log',true);
            fdump($this->app_id,'api/log/'.date('Ymd').'/D1/d1_get_gate_list_err_log',true);
            fdump($this->app_secret,'api/log/'.date('Ymd').'/D1/d1_get_gate_list_err_log',true);
            fdump($access_token_info,'api/log/'.date('Ymd').'/D1/d1_get_gate_list_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '获取失败');
        }
    }

    /**
     * Notes: 设备操作后操作
     * @param $device_id
     * @param array $device_info
     * @param string $project_uid
     * @param int $is_plan
     * @author: wanzy
     * @date_time: 2020/12/26 17:04
     */
    public function add_data($device_id, $device_info = [], $project_uid='', $is_plan=1) {
        $d_face_door_service = D('Face_door_service');
        if ($is_plan) {
            $param = array();
            $param['type'] = 'd1_sync_all_data';
            $unique_id = 'd1_sync_all_data';
            $param['unique_id'] = $unique_id;
            $d_face_door_service->add_plan($param, 'sub_house_face',-1);
            // 定制计划任务跑获取开门记录
            $param = array();
            $arr= array();
            $now_time = time();
            $param['type'] = 'd1_open_door_list';
            $unique_id = 'd1_open_door_list';
            $param['device_id'] = $device_id;
            $param['project_uid'] = $project_uid;
            $param['device_info'] = $device_info;
            if (isset($param['project_uid'])) {
                $unique_id .='_'.$param['project_uid'];
            }
            if (isset($param['device_id'])) {
                $unique_id .='_'.$param['device_id'];
            }
            $param['unique_id'] = $unique_id;
            $param['time'] = $now_time;
            $arr['param'] = serialize($param);
            $arr['plan_time'] = 1;
            $arr['space_time'] = 0;
            $arr['add_time'] = $now_time;
            $arr['file'] = 'sub_house_face_open_list';
            $arr['time_type'] = 0;
            $arr['unique_id'] = $unique_id;
            $arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
            $add_id = M('process_sub_plan')->data($arr)->add();
            if (!$add_id) {
                fdump_api(['添加计划任务'.__LINE__,$add_id, $arr, M('process_sub_plan')->_sql(),M('process_sub_plan')->getDbError()],'D1/plan_d1_door_open_record_err_log',1);
            }
        } else {
            $this->d1_sync_all_data();
//            $this->d1_open_door_list($device_id, $device_info, $project_uid);
            // 定制计划任务跑获取开门记录
            $param = array();
            $arr= array();
            $now_time = time();
            $param['type'] = 'd1_open_door_list';
            $unique_id = 'd1_open_door_list';
            $param['device_id'] = $device_id;
            $param['project_uid'] = $project_uid;
            $param['device_info'] = $device_info;
            if (isset($param['project_uid'])) {
                $unique_id .='_'.$param['project_uid'];
            }
            if (isset($param['device_id'])) {
                $unique_id .='_'.$param['device_id'];
            }
            $param['unique_id'] = $unique_id;
            $param['time'] = $now_time;
            $arr['param'] = serialize($param);
            $arr['plan_time'] = 1;
            $arr['space_time'] = 0;
            $arr['add_time'] = $now_time;
            $arr['file'] = 'sub_house_face_open_list';
            $arr['time_type'] = 0;
            $arr['unique_id'] = $unique_id;
            $arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
            $add_id = M('process_sub_plan')->data($arr)->add();
            if (!$add_id) {
                fdump_api(['添加计划任务'.__LINE__,$add_id, $arr, M('process_sub_plan')->_sql(),M('process_sub_plan')->getDbError()],'D1/plan_d1_door_open_record_err_log',1);
            }
        }
    }

    /**
     * Notes: 设备重启
     * @param $device_id
     * @param string $gate_uid
     * @param array $device_info
     * @return array|bool|mixed
     * @author: wanzy
     * @date_time: 2020/12/22 20:41
     */
    public function d1_restart_gate($device_id, $uid='', $device_info = [], $repeat_post=0) {
        $db_house_face_device = M('House_face_device');
        if (empty($device_info)) {
            $where_device = [
                'device_id' => $device_id
            ];
            $device_info = $db_house_face_device->where($where_device)->find();
        }
        if (!$uid) {
            $uid = $device_info['ai_device_sn'];
        }
        if (!$uid) {
            fdump('设备重启缺少必传参数>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_restart_gate_err_log',true);
            fdump($device_id,'api/log/'.date('Ymd').'/D1/d1_restart_gate_err_log',true);
            fdump($device_info,'api/log/'.date('Ymd').'/D1/d1_restart_gate_err_log',true);
            return false;
        }

        if (!$this->userphone) {
            $this->d1_get_config();
        }
        if (!$this->userphone || !$this->password || !$this->app_id || !$this->app_secret) {
            return array('errcode' => '1001', 'errmsg' => '缺少必要配置项');
        }
        $access_token_info = $this->d1_login();

        if (0==$access_token_info['errcode']) {
            $access_token = $access_token_info['access_token'];
            $authorization = $access_token;
            $url = $this->d1_base_url . '/openapi/v3/gate/restartGate';
            $param = [];
            $param['gate_uid'] = (object)[$uid];
            $request = 'PUT';
            $info = $this->curl_post($url, $param, $authorization, $request);
            fdump('设备重启>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_restart_gate_log',true);
            fdump($url,'api/log/'.date('Ymd').'/D1/d1_restart_gate_log',true);
            fdump($param,'api/log/'.date('Ymd').'/D1/d1_restart_gate_log',true);
            fdump($authorization,'api/log/'.date('Ymd').'/D1/d1_restart_gate_log',true);
            fdump($info,'api/log/'.date('Ymd').'/D1/d1_restart_gate_log',true);

            if ($info && $info['code']==200) {
                $where_device = [
                    'device_id' => $device_id
                ];
                // 重启成功记录重启时间为新的设备登录时间
                $data_save = [
                    'login_time' => time(),
                ];
                $db_house_face_device->where($where_device)->save($data_save);
                return $device_id;
            } elseif (400==$info['code'] && 0==$repeat_post) {
                $msg = $this->d1_refresh_token();
                if (0==$msg['errcode']) {
                    return $this->d1_restart_gate($device_id, $uid, $device_info, 1);
                }
            } else {
                return array('errcode' => '1001', 'errmsg' => $info['msg']);
            }

        } else {
            fdump('设备重启 失败token错误>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_restart_gate_err_log',true);
            fdump($this->userphone,'api/log/'.date('Ymd').'/D1/d1_restart_gate_err_log',true);
            fdump($this->password,'api/log/'.date('Ymd').'/D1/d1_restart_gate_err_log',true);
            fdump($this->app_id,'api/log/'.date('Ymd').'/D1/d1_restart_gate_err_log',true);
            fdump($this->app_secret,'api/log/'.date('Ymd').'/D1/d1_restart_gate_err_log',true);
            fdump($access_token_info,'api/log/'.date('Ymd').'/D1/d1_restart_gate_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '获取失败');
        }
    }

    /** 设备详情
     * Notes:
     * @param $device_id
     * @param string $uid
     * @param array $device_info
     * @return array|bool
     * @author: wanzy
     * @date_time: 2020/12/23 11:24
     */
    public function d1_get_gate($device_id, $uid='', $device_info = [], $repeat_post=0) {
        $db_house_face_device = M('House_face_device');
        if (empty($device_info)) {
            $where_device = [
                'device_id' => $device_id
            ];
            $device_info = $db_house_face_device->where($where_device)->find();
        }
        if (!$uid) {
            $uid = $device_info['ai_device_sn'];
        }
        if (!$uid) {
            fdump('设备详情缺少必传参数>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_get_gate_err_log',true);
            fdump($device_id,'api/log/'.date('Ymd').'/D1/d1_get_gate_err_log',true);
            fdump($device_info,'api/log/'.date('Ymd').'/D1/d1_get_gate_err_log',true);
            return false;
        }

        if (!$this->userphone) {
            $this->d1_get_config();
        }
        if (!$this->userphone || !$this->password || !$this->app_id || !$this->app_secret) {
            return array('errcode' => '1001', 'errmsg' => '缺少必要配置项');
        }
        $access_token_info = $this->d1_login();

        if (0==$access_token_info['errcode']) {
            $access_token = $access_token_info['access_token'];
            $authorization = $access_token;
            $url = $this->d1_base_url . '/openapi/v3/gate/info';
            $param = [];
            $url .= '?uid='.$uid;
            $request = 'GET';
            $info = $this->curl_post($url, $param, $authorization, $request);
            fdump('设备详情>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_get_gate_log',true);
            fdump($url,'api/log/'.date('Ymd').'/D1/d1_get_gate_log',true);
            fdump($param,'api/log/'.date('Ymd').'/D1/d1_get_gate_log',true);
            fdump($authorization,'api/log/'.date('Ymd').'/D1/d1_get_gate_log',true);
            fdump($info,'api/log/'.date('Ymd').'/D1/d1_get_gate_log',true);

            if ($info && $info['code']==200) {
                $data = $info['data'];
                $where_device = [
                    'device_id' => $device_id
                ];
                // 获取成功记录重启时间为新的设备登录时间
                $data_save = [
                    'login_time' => time(),
                ];
                if ($data['gate_online'] && $data['gate_online']=='离线') {
                    $data_save['device_status'] = 2;
                } else {
                    $data_save['device_status'] = 1;
                }
                if ($data['gate_remote_ip']) {
                    $data_save['device_ip'] = $data['gate_remote_ip'];
                }
                if ($data['gate_app_ver']) {
                    $data_save['sdk_version'] = $data['gate_app_ver'];
                }
                $data_save['device_info'] = serialize($data);
                $db_house_face_device->where($where_device)->save($data_save);
                return $device_id;
            } elseif (400==$info['code'] && 0==$repeat_post) {
                $msg = $this->d1_refresh_token();
                if (0==$msg['errcode']) {
                    return $this->d1_get_gate($device_id, $uid, $device_info, 1);
                }
            } else {
                return array('errcode' => '1001', 'errmsg' => $info['msg']);
            }

        } else {
            fdump('设备详情 失败token错误>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_get_gate_err_log',true);
            fdump($this->userphone,'api/log/'.date('Ymd').'/D1/d1_get_gate_err_log',true);
            fdump($this->password,'api/log/'.date('Ymd').'/D1/d1_get_gate_err_log',true);
            fdump($this->app_id,'api/log/'.date('Ymd').'/D1/d1_get_gate_err_log',true);
            fdump($this->app_secret,'api/log/'.date('Ymd').'/D1/d1_get_gate_err_log',true);
            fdump($access_token_info,'api/log/'.date('Ymd').'/D1/d1_get_gate_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '获取失败');
        }
    }

    /**
     * Notes: 获取开门记录
     * @param $device_id
     * @param array $device_info
     * @param string $project_uid
     * @return array|bool|mixed
     * @author: wanzy
     * @date_time: 2020/12/26 15:18
     */
    public function d1_open_door_list($device_id, $device_info = [], $project_uid='', $repeat_post=0, $start_time=0) {
        $db_house_face_device = M('House_face_device');
        $d_house_user_log = M('House_user_log');
        if (empty($device_info)) {
            $where_device = [
                'device_id' => $device_id
            ];
            $device_info = $db_house_face_device->where($where_device)->find();
        }
        $db_face_bind_about_info = M('Face_bind_about_info');
        $village_id = $device_info['village_id'];
        if (!$project_uid) {
            $where_bind = [
                'bind_type' => 0,
                'bind_id' => $village_id,
                'device_id' => 0,
                'device_type' => 21,
            ];
            $project_uid = $db_face_bind_about_info->where($where_bind)->order('id ASC')->getField('third_id');
        }
        if (!$project_uid) {
            fdump('开门抓拍记录[开门记录]缺少必传参数>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_open_door_list_err_log',true);
            fdump($device_id,'api/log/'.date('Ymd').'/D1/d1_open_door_list_err_log',true);
            fdump($project_uid,'api/log/'.date('Ymd').'/D1/d1_open_door_list_err_log',true);
            return false;
        }
        $where_log = [
            'device_id' => $device_id,
            'log_from' => ['in',[1,2,3]],
        ];
        $user_log = $d_house_user_log->where($where_log)->field('log_time')->order('log_time DESC')->find();
        $is_last = false;
        if (($user_log && $user_log['log_time']) || $start_time>0) {
            if (intval($start_time)<=0) {
                $start_time = $user_log['log_time'];
            }
            $now_time = time();
            $end_time = $now_time;
            $months = date('Ym', $start_time);
            $monthe = date('Ym', $end_time);
            if ($months==$monthe) {
                $from_catch_time = date('Y-m-d H:i:s', $start_time);
                $to_catch_time = date('Y-m-d H:i:s', $end_time);
            } elseif (intval($monthe)>intval($months)) {
                $firstday = date('Y-m-01', $start_time);
                $end_time = date('Y-m-d 23:59:59', strtotime("$firstday +1 month -1 day"));
                $from_catch_time = date('Y-m-d H:i:s', $start_time);
                $to_catch_time = $end_time;
                $is_last = true;
                // 计算下下个开始
                $last_start_time_str = date('Y-m-d 00:00:00', strtotime("$firstday +1 month"));
                $last_start_time = strtotime($last_start_time_str);
                if (intval($last_start_time)>intval($now_time)) {
                    $is_last = false;
                }
            } else {
                fdump('开门抓拍记录[开门记录]时间参数有误>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_open_door_list_err_log',true);
                fdump($device_id,'api/log/'.date('Ymd').'/D1/d1_open_door_list_err_log',true);
                fdump($project_uid,'api/log/'.date('Ymd').'/D1/d1_open_door_list_err_log',true);
                fdump($start_time,'api/log/'.date('Ymd').'/D1/d1_open_door_list_err_log',true);
                fdump($end_time,'api/log/'.date('Ymd').'/D1/d1_open_door_list_err_log',true);
                fdump($months,'api/log/'.date('Ymd').'/D1/d1_open_door_list_err_log',true);
                fdump($monthe,'api/log/'.date('Ymd').'/D1/d1_open_door_list_err_log',true);
                return false;
            }
        }
        $page=0;
        $size = 50;
        $gate_name = '人脸门禁_'.$device_info['device_id'];
        if (!$this->userphone) {
            $this->d1_get_config();
        }
        if (!$this->userphone || !$this->password || !$this->app_id || !$this->app_secret) {
            return array('errcode' => '1001', 'errmsg' => '缺少必要配置项');
        }
        $access_token_info = $this->d1_login();

        if (0==$access_token_info['errcode']) {
            $access_token = $access_token_info['access_token'];
            $authorization = $access_token;
            $url = $this->d1_base_url . '/openapi/v3/capture/opendoor';
            $param = [];
            $request = 'GET';
            $param['page'] = $page;
            $param['size'] = $size;
            $param['project_uid'] = $project_uid;
            if ($from_catch_time && $to_catch_time) {
                $param['from_catch_time'] = $from_catch_time;
                $param['to_catch_time'] = $to_catch_time;
            }
            $param['gate_name'] = $gate_name;

            $query_param = http_build_query($param);
            $url .= '?'.$query_param;

            fdump('开门抓拍记录[开门记录]>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_open_door_list_log',true);
            $info = $this->curl_post($url, [], $authorization, $request);
            fdump($url,'api/log/'.date('Ymd').'/D1/d1_open_door_list_log',true);
            fdump($param,'api/log/'.date('Ymd').'/D1/d1_open_door_list_log',true);
            fdump($authorization,'api/log/'.date('Ymd').'/D1/d1_open_door_list_log',true);
            fdump($info,'api/log/'.date('Ymd').'/D1/d1_open_door_list_log',true);

            if ($info && $info['code']==200) {
                $data_msg = $info['data'];
                $data = $data_msg['data'];
                $total = $data_msg['total'];
                if ($data) {
                    $d_house_village_user_bind = D('House_village_user_bind');
                    $d_house_village = M('House_village');

                    $house_village_info = $d_house_village->where(array('village_id' => $village_id))->field('village_id,village_name,city_id')->find();
                    $village_name = $house_village_info['village_name'];
                    $city_id = $house_village_info['city_id'];

                    if ( $device_info['public_area_id'] >0 ) {
                        $public_area_name = M('House_village_public_area')->where(['public_area_id' => $device_info['public_area_id']])->getField('public_area_name');
                        $floor_name = '';
                        $floor_layer = $public_area_name;
                    } elseif ($device_info['floor_id'] == -1) {
                        $floor_layer = C('config.house_name');
                        $floor_name = '大门';
                    } else {
                        $d_house_village_single = D('House_village_single');
                        $d_house_village_floor = M('House_village_floor');
                        $house_village_floor_info = $d_house_village_floor->where(array('floor_id' => $device_info['floor_id']))->field('floor_id,floor_layer,floor_name,single_id')->find();
                        $house_village_single_info = $d_house_village_single->where(['id' => $device_info['single_id']])->field('id as single_id,single_name')->find();
                        $floor_layer = $house_village_single_info['single_name'] ? $house_village_single_info['single_name'] : $house_village_floor_info['floor_layer'];
                        $floor_name = $house_village_floor_info['floor_name'];
                    }
                    $d_house_camera_tip = M('House_camera_tip');
                    fdump('获取开门信息》》》'.__LINE__,'api/log/'.date('Ymd').'/D1/face_d1_open_door_notify_log',true);
                    foreach ($data as $val) {
                        $log = [];
                        $where_repeat = [
                            'eventId' => $val['uid']
                        ];
                        $repeat_id = $d_house_user_log->where($where_repeat)->getField('log_id');
                        if ($repeat_id) {
                            continue;
                        }
                        if ($val['user_realname']) {
                            $bind_arr = explode('_',$val['user_realname']);
                            $bind_id = array_pop($bind_arr);
                            $where_user_bind = [
                                'pigcms_id' => $bind_id
                            ];
                            $house_village_bind = $d_house_village_user_bind->where($where_user_bind)->field('uid,pigcms_id')->find();
                        }
                        if ($val['capture_type']=='人脸开门') {
                            $log_from = 1;
                        } elseif ($val['capture_type']=='门卡开门') {
                            $log_from = 3;
                        } else {
                            $log_from = 2;
                        }
                        if ($house_village_bind) {
                            $uid = $house_village_bind['uid'];
                            $bind_id = $house_village_bind['pigcms_id'];
                            if ($bind_id) {
                                hook::hook_exec('house_cost_notice',array('bind_id'=>$bind_id));
                            }
                            $log['uid'] = $uid ? $uid : 0;
                            $log['log_bind_id'] = $bind_id ? $bind_id : 0;
                            $log['log_name'] = $village_name . ' ' . $floor_layer . ' ' . $floor_name;
                            $log['log_from'] = $log_from; // 人脸识别门禁
                            $log['log_business_id'] = $village_id?$village_id:0; // 社区id
                            $log['log_more_id'] = $device_info['floor_id']?$device_info['floor_id']:0; // 单元id
                            $log['device_id'] = $device_info['device_id']; // 设备id
                            if ($val['created_at']) {
                                $log['log_time'] = $val['created_at']; // 记录时间
                            } else {
                                $log['log_time'] = time(); // 记录时间
                            }
                            $log['temperature'] = $val['temp'] ? $val['temp'] : '0.0';
                            $log['log_status'] = 0;
                            $status = 0;
                            if ($val['origin_thumb']) {
                                $origin_thumb = $val['origin_thumb'];
                                $val['face_img'] = D('Face_door_service')->face_to_img($origin_thumb,$device_id, true);
                            }
                            if ($val['small_thumb']) {
                                $small_thumb = $val['small_thumb'];
                                $val['small_face_img'] = D('Face_door_service')->face_to_img($small_thumb,$device_id, true);
                            }
                            $log['log_detail'] = serialize($val);
                            $log['eventId'] = $val['uid'];
                            if (isset($val['face_img']) && trim($val['face_img'])) {
                                $log['cut_image'] = trim($val['face_img']);
                            } elseif (isset($val['small_face_img']) && trim($val['small_face_img'])) {
                                $log['cut_image'] = trim($val['small_face_img']);
                            }
                            $log['add_time']=time();
                            $d_house_user_log->data($log)->add();
                        } else {
                            $log['uid'] = 0;
                            $log['log_bind_id'] = 0;
                            $log['log_name'] = $village_name . ' ' . $floor_layer . ' ' . $floor_name;
                            $log['log_from'] = $log_from; // 人脸识别门禁
                            $log['log_business_id'] = $village_id?$village_id:0; // 社区id
                            $log['log_more_id'] = $device_info['floor_id']?$device_info['floor_id']:0; // 单元id
                            $log['device_id'] = $device_info['device_id']; // 设备id
                            if ($val['created_at']) {
                                $log['log_time'] = $val['created_at']; // 记录时间
                            } else {
                                $log['log_time'] = time(); // 记录时间
                            }
                            $log['temperature'] = $val['temp'] ? $val['temp'] : '0.0';
                            $log['log_status'] = 0;
                            if ($val['origin_thumb']) {
                                $origin_thumb = $val['origin_thumb'];
                                $val['face_img'] = D('Face_door_service')->face_to_img($origin_thumb,$device_id, true);
                            }
                            if ($val['small_thumb']) {
                                $small_thumb = $val['small_thumb'];
                                $val['small_face_img'] = D('Face_door_service')->face_to_img($small_thumb,$device_id, true);
                            }
                            $log['log_detail'] = serialize($val);
                            $log['eventId'] = $val['uid'];
                            if (isset($val['face_img']) && trim($val['face_img'])) {
                                $log['cut_image'] = trim($val['face_img']);
                            } elseif (isset($val['small_face_img']) && trim($val['small_face_img'])) {
                                $log['cut_image'] = trim($val['small_face_img']);
                            }
                            $log['add_time']=time();
                            $d_house_user_log->data($log)->add();
                            $status = 1;
                            $uid = 0;
                            $bind_id = 0;
                        }

                        $tip = array(
                            'bind_id' =>  $bind_id,
                            'uid' => $uid,
                            'type' => 1,
                            'village_id' => $village_id ? $village_id : 0,
                            'camera_sn' => $device_info['device_sn'],
                            'city_id' => $city_id ? $city_id : 0,
                            'camera_time' => time(),
                            'camera_img' => $log['face_img']?$log['face_img']:$val['origin_thumb'],
                            'tip_time' => $log['log_time'],
                            'status' => $status,
                            'tip_txt' => serialize($val),
                            'temperature' => $val['temp'] ? $val['temp'] : '0.0',
                        );
                        $d_house_camera_tip->data($tip)->add();
                    }
                }
                if ($is_last && $last_start_time) {
                    return $this->d1_open_door_list($device_id, $device_info, $project_uid, 0, $last_start_time);
                } elseif ($data && $total && $size && $total>$size) {
                    $param = array();
                    $arr= array();
                    $param['type'] = 'd1_open_door_list';
                    $unique_id = 'd1_open_door_list';
                    $param['device_id'] = $device_id;
                    $param['project_uid'] = $project_uid;
                    $param['device_info'] = [];
                    if (isset($param['project_uid'])) {
                        $unique_id .='_'.$param['project_uid'];
                    }
                    if (isset($param['device_id'])) {
                        $unique_id .='_'.$param['device_id'];
                    }
                    $param['unique_id'] = $unique_id;
                    $param['time'] = $now_time;
                    $arr['param'] = serialize($param);
                    $arr['plan_time'] = 1;
                    $arr['space_time'] = 0;
                    $arr['add_time'] = $now_time;
                    $arr['file'] = 'sub_house_face_open_list';
                    $arr['time_type'] = 1;
                    $arr['unique_id'] = $unique_id;
                    $arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
                    $add_id = M('process_sub_plan')->data($arr)->add();
                    if (!$add_id) {
                        fdump_api(['添加计划任务'.__LINE__,$add_id, $arr, M('process_sub_plan')->_sql(),M('process_sub_plan')->getDbError()],'D1/plan_d1_door_open_record_err_log',1);
                    }
                }
                return $data;
            } elseif (400==$info['code'] && 0==$repeat_post) {
                $msg = $this->d1_refresh_token();
                if (0==$msg['errcode']) {
                    return $this->d1_open_door_list($device_id, $device_info, $project_uid, 1);
                }
            } else {
                return array('errcode' => '1001', 'errmsg' => $info['msg']);
            }
        } else {
            fdump('开门抓拍记录[开门记录]失败token错误>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_open_door_list_err_log',true);
            fdump($this->userphone,'api/log/'.date('Ymd').'/D1/d1_open_door_list_err_log',true);
            fdump($this->password,'api/log/'.date('Ymd').'/D1/d1_open_door_list_err_log',true);
            fdump($this->app_id,'api/log/'.date('Ymd').'/D1/d1_open_door_list_err_log',true);
            fdump($this->app_secret,'api/log/'.date('Ymd').'/D1/d1_open_door_list_err_log',true);
            fdump($access_token_info,'api/log/'.date('Ymd').'/D1/d1_open_door_list_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '获取失败');
        }
    }

    /**
     * Notes: 设备解绑
     * @param $device_id
     * @param string $gate_uid
     * @param array $device_info
     * @return array|bool
     * @author: wanzy
     * @date_time: 2020/12/23 10:53
     */
    public function d1_del_gate($device_id, $gate_uid='', $device_info = [], $repeat_post=0) {
        $db_house_face_device = M('House_face_device');
        if (empty($device_info)) {
            $where_device = [
                'device_id' => $device_id
            ];
            $device_info = $db_house_face_device->where($where_device)->find();
        }
        if (!$gate_uid) {
            $gate_uid = $device_info['ai_device_sn'];
        }
        if (!$gate_uid) {
            fdump('设备解绑缺少必传参数>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_del_gate_err_log',true);
            fdump($device_id,'api/log/'.date('Ymd').'/D1/d1_del_gate_err_log',true);
            fdump($device_info,'api/log/'.date('Ymd').'/D1/d1_del_gate_err_log',true);
            $info['error'] = 1001;
            $info['msg'] = '设备解绑缺少必传参数！';
            return $info;
        }

        if (!$this->userphone) {
            $this->d1_get_config();
        }
        if (!$this->userphone || !$this->password || !$this->app_id || !$this->app_secret) {
            $info['error'] = 1001;
            $info['msg'] = '缺少必要配置项！';
            return $info;
        }
        $access_token_info = $this->d1_login();

        if (0==$access_token_info['errcode']) {
            $access_token = $access_token_info['access_token'];
            $authorization = $access_token;
            $url = $this->d1_base_url . '/openapi/v3/gate';
            $param = [];
            $url .= '?uid='.$gate_uid;
            $request = 'DELETE';
            $info = $this->curl_post($url, $param, $authorization, $request);
            fdump('设备解绑>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_del_gate_log',true);
            fdump($url,'api/log/'.date('Ymd').'/D1/d1_del_gate_log',true);
            fdump($param,'api/log/'.date('Ymd').'/D1/d1_del_gate_log',true);
            fdump($authorization,'api/log/'.date('Ymd').'/D1/d1_del_gate_log',true);
            fdump($info,'api/log/'.date('Ymd').'/D1/d1_del_gate_log',true);

            if ($info && $info['code']==200) {
                $data = $info['data'];
                $ai_device_sn = $data['uid'];
                $where_device = [
                    'device_id' => $device_id
                ];
                // 设备解绑
                $data_save = [
                    'device_sn' => $device_info['device_sn'].'~no_use',
                    'is_del' => 4,
                    'is_syn_del' => 1,
                ];
                $db_house_face_device->where($where_device)->save($data_save);
                $info['error'] = 0;
                $info['msg'] = '同步删除成功！';
                return $info;
            } elseif ($info['code']==1228 || $info['msg']=='门禁设备不存在'){
                $where_device = [
                    'device_id' => $device_id
                ];
                // 设备解绑
                $data_save = [
                    'device_sn' => $device_info['device_sn'].'~no_use',
                    'is_del' => 4,
                    'is_syn_del' => 1,
                ];
                $db_house_face_device->where($where_device)->save($data_save);
                $info['error'] = 0;
                $info['msg'] = '同步删除成功！';
                return $info;
            } elseif (400==$info['code'] && 0==$repeat_post) {
                $msg = $this->d1_refresh_token();
                if (0==$msg['errcode']) {
                    return $this->d1_del_gate($device_id, $gate_uid, $device_info, 1);
                }
            } else {
                $info['error'] = 1001;
                return $info;
            }
        } else {
            fdump('设备解绑 失败token错误>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_del_gate_err_log',true);
            fdump($this->userphone,'api/log/'.date('Ymd').'/D1/d1_del_gate_err_log',true);
            fdump($this->password,'api/log/'.date('Ymd').'/D1/d1_del_gate_err_log',true);
            fdump($this->app_id,'api/log/'.date('Ymd').'/D1/d1_del_gate_err_log',true);
            fdump($this->app_secret,'api/log/'.date('Ymd').'/D1/d1_del_gate_err_log',true);
            fdump($access_token_info,'api/log/'.date('Ymd').'/D1/d1_del_gate_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '获取失败');
        }
    }

    // 远程开门相关处理
    // 模拟用户端登录
    public function d1_user_login($phone, $platform='developer', $repeat_post=0) {
        if (!$this->userphone) {
            $this->d1_get_config();
        }
        if (!$this->userphone || !$this->password || !$this->app_id || !$this->app_secret) {
            return array('errcode' => '1001', 'errmsg' => '缺少必要配置项');
        }
        $db_access_token_common_expires = M('Access_token_common_expires');
        $where_token = [
            'type' => $this->user_type,
            'access_id' => $this->app_id.'_'.$phone.'_'.$platform,
        ];
        // 查询下是否有记录下登录凭证
        $access_token_info = $db_access_token_common_expires->where($where_token)->find();
        // 过期时间  向前1800秒 提前刷新
        if ($access_token_info && $access_token_info['access_token'] && 0==$repeat_post) {
            return array('errcode' => 0, 'access_token' =>$access_token_info['access_token']);
        }
        $authorization_msg = [
            'app_id' => $this->app_id,
            'app_secret' => $this->app_secret,
        ];
        $authorization_json = json_encode($authorization_msg,true);
        $authorization = base64_encode($authorization_json);
        $url = $this->d1_base_url . '/webapp/v1/login';
        $params = [
            'phone' => $phone,
            'platform' => $platform,
        ];
        $info = $this->curl_post($url,$params,'Bearer '.$authorization);
        if ($info && $info['data'] && $info['data']['token']) {
            $access_token_common_expires = [];
            $data = $info['data'];
            if ($data['token']) {
                $access_token_common_expires['access_token'] = $data['token'];
            }
            if ($data['token_expire']) {
                $access_token_common_expires['access_token_expire'] = intval($data['access_token_expire']);
            }
            if ($data['refresh_token']) {
                $access_token_common_expires['refresh_token'] = $data['refresh_token'];
            }
            if ($data['refresh_token_expire']) {
                $access_token_common_expires['refresh_token_expire'] = $data['refresh_token_expire'];
            }
            $access_token_common_expires['type'] = $this->user_type;
            $access_token_common_expires['access_id'] = $this->app_id.'_'.$phone.'_'.$platform;
            $where_token = [
                'type' => $this->user_type,
                'access_id' => $this->app_id.'_'.$phone.'_'.$platform,
            ];
            // 查询下是否有记录下登录凭证
            $db_access_token_common_expires->where($where_token)->delete();
            $db_access_token_common_expires->data($access_token_common_expires)->add();
            return array('errcode' => 0, 'access_token' =>$data['token']);
        } else {
            return array('errcode' => $info['code'], 'errmsg' => $info['msg']);
        }
    }

    // 获取三方的用户相关信息或者申请钥匙
    public function d1_third_user_info($bind_id,$bind_info=[], $device_id=0,$device_type=21,$is_return=false) {
        ini_set('memory_limit', '2048M');
        $db_face_user_bind_device = M('Face_user_bind_device');
        $where_bind = [
            'bind_type' => 0,
            'bind_id' => $bind_id,
            'device_id' => 0,
            'device_type' => 21,
        ];
        $user_info = $db_face_user_bind_device->where($where_bind)->order('id ASC')->field('person_id, group_id, info')->find();
        if ($user_info) {
            $user_uid = $user_info['person_id'];
            if ($user_info['group_id']) {
                $userroom_uid = $user_info['group_id'];
            }
            if ($user_info['info']) {
                $user_data = unserialize($user_info['info']);
                if (!$userroom_uid && $user_data['userroom_uid']) {
                    $userroom_uid = $user_data['userroom_uid'];
                }
            }
        }
        if (!$bind_info || !$bind_info['village_id'] || !$bind_info['type']) {
            $where_user = [
                'pigcms_id' => $bind_id,
            ];
            $bind_info = M('House_village_user_bind')->where($where_user)->find();
        }
        $db_face_bind_about_info = M('Face_bind_about_info');
        $village_id = $bind_info['village_id'];
        $where_village = [
            'bind_type' => 0,
            'bind_id' => $village_id,
            'device_id' => 0,
            'device_type' => 21,
        ];
        $project_uid = $db_face_bind_about_info->where($where_village)->order('id ASC')->getField('third_id');
        if ($bind_info['type']==4) {
            $single_id = -$village_id;
        } else {
            $single_id = $bind_info['single_id'];
        }
        if (!$single_id && $bind_info['vacancy_id']) {
            $where_vacancy = [
                'pigcms_id' => $bind_info['vacancy_id'],
            ];
            $single_id = M('House_village_user_vacancy')->where($where_vacancy)->getField('single_id');
        }
        $where_single = [
            'bind_type' => 2,
            'bind_id' => $single_id,
            'device_id' => 0,
            'device_type' => 21,
        ];
        $building_uid = $db_face_bind_about_info->where($where_single)->order('id ASC')->getField('third_id');
        if ($bind_info['type']==4) {
            $vacancy_id = -($village_id+1);
        } else {
            $vacancy_id = $bind_info['vacancy_id'];
        }
        $where_room = [
            'bind_type' => 4,
            'bind_id' => $vacancy_id,
            'device_id' => 0,
            'device_type' => 21,
        ];
        $room_info = $db_face_bind_about_info->where($where_room)->order('id ASC')->field('third_id, bind_name')->find();
        if ($room_info && $room_info['third_id']) {
            $room_uid = $room_info['third_id'];
        }

        $user_realname = $bind_info['name'] ? $bind_info['name'].'_'.$bind_id : '用户_'.$bind_id;
        // 业主身份 1业主，3业主家庭成员
        if ($bind_info['type']==4) {
            $ur_identity = 3;
        } else {
            $ur_identity = (in_array($bind_info['type'],[0,3])) ? 1 : 3;
        }
        $user_phone = $bind_info['phone'];
        $param_msg = [
            'village_id' => $village_id,
            'single_id' => $single_id,
            'project_uid' => $project_uid,
            'building_uid' => $building_uid,
            'room_uid' => $room_uid,
            'user_realname' => $user_realname,
            'record_identity' => $ur_identity,
            'phone' => $user_phone,
            'room_number' => $room_info['bind_name'],
            'user_uid' => $user_uid,
            'device_id' => $device_id,
            'device_type' => $device_type,
        ];
        if ($is_return) {
            return $param_msg;
        }
        $info = $this->d1_apply_user_gate($bind_id, $param_msg);
        return $info;
    }

    /**
     * Notes: 模拟用户去申请开门钥匙
     * @param $bind_id
     * @param $param
     * @param int $repeat_post
     * @return array|mixed|string
     * @author: wanzy
     * @date_time: 2021/1/4 9:46
     */
    public function d1_apply_user_gate($bind_id, $param, $repeat_post=0) {
        if (!$param['phone'] || !$param['project_uid'] || !$param['building_uid'] || !$param['user_uid'] || !$param['room_uid'] || !$param['user_realname'] || !$param['record_identity']) {
            fdump('申请钥匙[远程开门]>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_apply_user_gate_err_log',true);
            fdump($bind_id,'api/log/'.date('Ymd').'/D1/d1_apply_user_gate_err_log',true);
            fdump($param,'api/log/'.date('Ymd').'/D1/d1_apply_user_gate_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '缺少必要参数');
        }
        $db_face_user_open_key = M('Face_user_open_key');
        $where_open_key = [
            'bind_id' => $bind_id,
        ];
        if ($param['device_id']) {
            $where_open_key['device_id'] = $param['device_id'];
        }
        if ($param['device_type']) {
            $where_open_key['device_type'] = $param['device_type'];
        }
        $key_info = $db_face_user_open_key->where($where_open_key)->find();
        if ($key_info && 1==$key_info['open_key_status']) {
            return $key_info['open_key'];
        } elseif ($key_info && 0==$key_info['open_key_status']) {
            // 调用通过审核 审核成功返回钥匙
            $param['record_type'] = $key_info['record_type'];
            $param['open_key'] = $key_info['open_key'];
            return $this->d1_check_user_gate($bind_id, $param);
        } elseif ($key_info) {
            return array('errcode' => '1001', 'errmsg' => '该用户开门钥匙被禁止');
        }

        $db_face_user_bind_device = M('Face_user_bind_device');
        $where_bind = [
            'bind_type' => 0,
            'bind_id' => $bind_id,
            'device_id' => 0,
            'device_type' => 21,
        ];
        $bind_info = $db_face_user_bind_device->where($where_bind)->find();
        $now_time = time();
        if (!$bind_info){
            fdump('申请钥匙[远程开门]>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_apply_user_gate_err_log',true);
            fdump($bind_id,'api/log/'.date('Ymd').'/D1/d1_apply_user_gate_err_log',true);
            fdump($param,'api/log/'.date('Ymd').'/D1/d1_apply_user_gate_err_log',true);
            fdump($where_bind,'api/log/'.date('Ymd').'/D1/d1_apply_user_gate_err_log',true);
            fdump($bind_info,'api/log/'.date('Ymd').'/D1/d1_apply_user_gate_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '缺少必要参数');
        }elseif ($bind_info && $bind_info['open_key'] && 0==$bind_info['open_key_status'] && $key_info) {
            // 此处直接调用审核接口
            $param['record_type'] = $key_info['record_type'];
            $param['open_key'] = $key_info['open_key'];
            return $this->d1_check_user_gate($bind_id, $param);
        } elseif ($bind_info && $bind_info['open_key'] && $bind_info['open_key_time'] && intval($now_time)>intval($bind_info['open_key_time'])) {
            // 此处标记为 续费
            $param['record_type'] = 2;
        } else {
            // 此处标记为 申请
            $param['record_type'] = 1;
        }

        $param_msg = [];
        $param_msg['project_id'] = $param['project_uid'];
        $param_msg['building_id'] = $param['building_uid'];
        $param_msg['room_id'] = $param['room_uid'];
        $param_msg['user_realname'] = $param['user_realname'];
        if ($param['user_id_card']) {
            $param_msg['user_id_card'] = $param['user_id_card'];
        }
        $param_msg['record_type'] = $param['record_type'];
        $param_msg['record_identity'] = $param['record_identity'];

        $access_token_info = $this->d1_user_login($param['phone'], 'developer');

        if (0==$access_token_info['errcode']) {
            $access_token = $access_token_info['access_token'];
            $authorization = $access_token;
            $url = $this->d1_base_url . '/webapp/v1/roomrecord/apply';
            $request = 'POST';
            $info = $this->curl_post($url, $param_msg, $authorization, $request);
            fdump('申请钥匙[远程开门]>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_apply_user_gate_log',true);
            fdump($access_token_info,'api/log/'.date('Ymd').'/D1/d1_apply_user_gate_log',true);
            fdump($url,'api/log/'.date('Ymd').'/D1/d1_apply_user_gate_log',true);
            fdump($param,'api/log/'.date('Ymd').'/D1/d1_apply_user_gate_log',true);
            fdump($authorization,'api/log/'.date('Ymd').'/D1/d1_apply_user_gate_log',true);
            fdump($info,'api/log/'.date('Ymd').'/D1/d1_apply_user_gate_log',true);
            if ($info && $info['code']==200) {
                $data = $info['data'];
                if ($data['uid']) {
                    $where_bind = [
                        'bind_type' => 0,
                        'bind_id' => $bind_id,
                        'device_id' => 0,
                        'device_type' => 21,
                    ];
                    // 记录开门钥匙
                    $open_key = $data['uid'];
                    $bind_data['open_key'] = $open_key;
                    $db_face_user_bind_device->where($where_bind)->save($bind_data);
                    // 添加开门钥匙表信息
                    $user_open_key = [];
                    $user_open_key['bind_id'] = $bind_id;
                    $user_open_key['device_type'] = 21;
                    $user_open_key['phone'] = $param['phone'];
                    $user_open_key['record_type'] = $param['record_type'];
                    if ($param['user_uid']) {
                        $user_open_key['third_bind'] = $param['user_uid'];
                    }
                    if ($param['device_id']) {
                        $user_open_key['device_id'] = $param['device_id'];
                    }
                    if ($param['device_type']) {
                        $user_open_key['device_type'] = $param['device_type'];
                    }
                    $where_bind = [
                        'pigcms_id' => $bind_id
                    ];
                    $user_info = M('House_village_user_bind')->where($where_bind)->find();
                    if ($user_info['village_id']) {
                        $user_open_key['village_id'] = $user_info['village_id'];
                    }
                    $user_open_key['third_village'] = $param['project_uid'];
                    if ($user_info['single_id']) {
                        $user_open_key['building_id'] = $user_info['single_id'];
                    }
                    if ($user_info['floor_id']) {
                        $user_open_key['floor_id'] = $user_info['floor_id'];
                    }
                    $user_open_key['third_building'] = $param['building_uid'];
                    if ($user_info['vacancy_id']) {
                        $user_open_key['room_id'] = $user_info['vacancy_id'];
                    }
                    $user_open_key['third_room'] = $param['room_uid'];
                    $user_open_key['open_key'] = $data['uid'];
                    $data['apply_param'] = $param_msg;
                    $user_open_key['third_other'] = serialize($data);
                    $user_open_key['add_time'] = time();
                    $db_face_user_open_key->data($user_open_key)->add();
                    $param['open_key'] = $open_key;
                    return $this->d1_check_user_gate($bind_id, $param);
                } else {
                    return $this->d1_get_user_gate($bind_id, $param, $param_msg);
                }
            } elseif (400==$info['code'] && 0==$repeat_post) {
                $msg = $this->d1_user_login($param['phone'], 'developer',1);
                if (0==$msg['errcode']) {
                    return $this->d1_apply_user_gate($bind_id, $param, 1);
                }
            } else {
                return array('errcode' => '1001', 'errmsg' => $info['msg']);
            }
        } else {
            fdump('申请钥匙[远程开门] 登录错误>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_apply_user_gate_err_log',true);
            fdump($bind_id,'api/log/'.date('Ymd').'/D1/d1_apply_user_gate_err_log',true);
            fdump($param,'api/log/'.date('Ymd').'/D1/d1_apply_user_gate_err_log',true);
            fdump($where_bind,'api/log/'.date('Ymd').'/D1/d1_apply_user_gate_err_log',true);
            fdump($bind_info,'api/log/'.date('Ymd').'/D1/d1_apply_user_gate_err_log',true);
            fdump($access_token_info,'api/log/'.date('Ymd').'/D1/d1_apply_user_gate_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '登录错误');
        }
    }

    /**
     * Notes: 获取用户申请记录
     * @param $bind_id
     * @param $param
     * @param int $repeat_post
     * @return array|mixed|string
     * @author: wanzy
     * @date_time: 2020/12/30 18:45
     */
    public function d1_get_user_gate($bind_id, $param, $param_msg=[], $repeat_post=0) {
        if (!$param['phone']) {
            fdump('获取对应用户所有钥匙[远程开门]>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_get_user_gate_err_log',true);
            fdump($param,'api/log/'.date('Ymd').'/D1/d1_get_user_gate_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '缺少必要参数');
        }
        $access_token_info = $this->d1_user_login($param['phone'], 'developer');

        if (0==$access_token_info['errcode']) {
            $access_token = $access_token_info['access_token'];
            $authorization = $access_token;
//            $url = $this->d1_base_url . '/webapp/v1/usergate/all';
            $url = $this->d1_base_url . '/webapp/v1/roomrecord/get';
            $request = 'GET';
            $info = $this->curl_post($url, [], $authorization, $request);
            fdump('申请钥匙[远程开门]>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_get_user_gate_log',true);
            fdump($url,'api/log/'.date('Ymd').'/D1/d1_get_user_gate_log',true);
            fdump($param,'api/log/'.date('Ymd').'/D1/d1_get_user_gate_log',true);
            fdump($authorization,'api/log/'.date('Ymd').'/D1/d1_get_user_gate_log',true);
            fdump($info,'api/log/'.date('Ymd').'/D1/d1_get_user_gate_log',true);
            if ($info && $info['code']==200) {
                $data_msg = $info['data'];
                $data = $data_msg['data'];
                if ($data) {
                    $user_open_key = [];
                    foreach ($data as $key=>$val) {
                        if (isset($param['user_realname']) && $param['user_realname']==$val['user_realname']) {
                            if (isset($param['record_identity']) && $param['record_identity']==$val['record_identity']) {
                                $project_arr = explode('_',$val['project']);
                                $village_id = array_pop($project_arr);
                                $building_arr = explode('_',$val['building']);
                                $single_id = array_pop($building_arr);
                                if (isset($param['village_id']) && $param['village_id']==$village_id && isset($param['single_id']) && $param['single_id']==$single_id) {
                                    $user_open_key = $val;
                                }
                            }
                        }
                    }
                    if (!$user_open_key) {
                        $user_open_key = reset($data);
                    }
                    $open_key = $user_open_key['uid'] ? $user_open_key['uid'] : '';
                }
                if ($open_key && $bind_id) {
                    $db_face_user_bind_device = M('Face_user_bind_device');
                    $where_bind = [
                        'bind_type' => 0,
                        'bind_id' => $bind_id,
                        'device_id' => 0,
                        'device_type' => 21,
                    ];
                    // 记录开门钥匙
                    $bind_data['open_key'] = $open_key;
                    $db_face_user_bind_device->where($where_bind)->save($bind_data);
                    // 添加开门钥匙表信息
                    $db_face_user_open_key = M('Face_user_open_key');
                    $user_open_key = [];
                    $user_open_key['bind_id'] = $bind_id;
                    $user_open_key['device_type'] = 21;
                    $user_open_key['phone'] = $param['phone'];
                    $user_open_key['record_type'] = $param['record_type'];
                    if ($param['user_uid']) {
                        $user_open_key['third_bind'] = $param['user_uid'];
                    }
                    if ($param['device_id']) {
                        $user_open_key['device_id'] = $param['device_id'];
                    }
                    if ($param['device_type']) {
                        $user_open_key['device_type'] = $param['device_type'];
                    }
                    $where_bind = [
                        'pigcms_id' => $bind_id
                    ];
                    $user_info = M('House_village_user_bind')->where($where_bind)->find();
                    if ($user_info['village_id']) {
                        $user_open_key['village_id'] = $user_info['village_id'];
                    }
                    $user_open_key['third_village'] = $param['project_uid'];
                    if ($user_info['single_id']) {
                        $user_open_key['building_id'] = $user_info['single_id'];
                    }
                    if ($user_info['floor_id']) {
                        $user_open_key['floor_id'] = $user_info['floor_id'];
                    }
                    $user_open_key['third_building'] = $param['building_uid'];
                    if ($user_info['vacancy_id']) {
                        $user_open_key['room_id'] = $user_info['vacancy_id'];
                    }
                    $user_open_key['third_room'] = $param['room_uid'];
                    $user_open_key['open_key'] = $data['uid'];
                    $data['apply_param'] = $param_msg;
                    $user_open_key['third_other'] = serialize($data);
                    $user_open_key['add_time'] = time();
                    $db_face_user_open_key->data($user_open_key)->add();
                    $param['open_key'] = $open_key;
                    return $this->d1_check_user_gate($bind_id, $param);
                }
                return $open_key;
            } elseif (400==$info['code'] && 0==$repeat_post) {
                $msg = $this->d1_user_login($param['phone'], 'developer', 1);
                if (0==$msg['errcode']) {
                    return $this->d1_get_user_gate($bind_id, $param, $param_msg, 1);
                }
            } else {
                return array('errcode' => '1001', 'errmsg' => $info['msg']);
            }
        }
    }

    /**
     * Notes: 审核申请钥匙
     * @param $bind_id
     * @param $param
     * @param int $repeat_post
     * @return array
     * @author: wanzy
     * @date_time: 2021/1/4 11:54
     */
    public function d1_check_user_gate($bind_id, $param, $repeat_post=0) {
        if (!$param['phone'] || !$param['project_uid'] || !$param['building_uid'] || !$param['user_uid'] || !$param['room_uid'] || !$param['user_realname'] || !$param['record_identity']) {
            fdump('审核申请钥匙[远程开门]>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_check_user_gate_err_log',true);
            fdump($bind_id,'api/log/'.date('Ymd').'/D1/d1_check_user_gate_err_log',true);
            fdump($param,'api/log/'.date('Ymd').'/D1/d1_check_user_gate_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '缺少必要参数');
        }
        $db_face_user_open_key = M('Face_user_open_key');
        $db_face_user_bind_device = M('Face_user_bind_device');
        $where_open_key = [
            'bind_id' => $bind_id,
        ];
        if ($param['device_id']) {
            $where_open_key['device_id'] = $param['device_id'];
        }
        $where_open_key['device_type'] = 21;
        $key_info = $db_face_user_open_key->where($where_open_key)->find();
        if ($key_info && 1==$key_info['open_key_status']) {
            return $key_info['open_key'];
        } elseif ($key_info && 2==$key_info['open_key_status']) {
            return array('errcode' => '1001', 'errmsg' => '该用户开门钥匙被禁止');
        }
        // 过期时间默认
        $ur_expired = strtotime('2035-12-31 23:59:59');
        $param_msg = [];
        $param_msg['uid'] = strval($param['open_key']);
        $param_msg['record_type'] = intval($param['record_type']);
        $param_msg['record_remark'] = '审核通过';
        $param_msg['ur_expired'] = intval($ur_expired);
        // 审核操作 1.通过 2.拒绝
        $param_msg['record_audit'] = 1;
        $param_msg['user_realname'] = strval($param['user_realname']);
        $param_msg['ur_identity'] = intval($param['record_identity']);
        $param_msg['project_uid'] = strval($param['project_uid']);
        $param_msg['building_uid'] = strval($param['building_uid']);
        $param_msg['room_uid'] = strval($param['room_uid']);
        $param_msg['user_uid'] = strval($param['user_uid']);

        $access_token_info = $this->d1_login();

        if (0==$access_token_info['errcode']) {
            $access_token = $access_token_info['access_token'];
            $authorization = $access_token;
            $url = $this->d1_base_url . '/openapi/v3/key';
            $request = 'PUT';
            $info = $this->curl_post($url, $param_msg, $authorization, $request);
            fdump('审核申请钥匙[远程开门]>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_check_user_gate_log',true);
            fdump($url,'api/log/'.date('Ymd').'/D1/d1_check_user_gate_log',true);
            fdump($param_msg,'api/log/'.date('Ymd').'/D1/d1_check_user_gate_log',true);
            fdump($authorization,'api/log/'.date('Ymd').'/D1/d1_check_user_gate_log',true);
            fdump($info,'api/log/'.date('Ymd').'/D1/d1_check_user_gate_log',true);
            if ($info && $info['code']==200) {
                $set_data = [
                    'open_key_status' => 1,
                    'open_key_time' => $ur_expired,
                    'last_time' => time()
                ];
                $db_face_user_open_key->where($where_open_key)->data($set_data)->save();
                $where_bind = [
                    'bind_type' => 0,
                    'bind_id' => $bind_id,
                    'device_id' => 0,
                    'device_type' => 21,
                ];
                // 记录开门钥匙
                $bind_data = [
                    'open_key_status' => 1,
                    'open_key_time' => $ur_expired,
                    'last_time' => time()
                ];
                $db_face_user_bind_device->where($where_bind)->save($bind_data);
                return $param['open_key'];
            } elseif (400==$info['code'] && 0==$repeat_post) {
                $msg = $this->d1_refresh_token();
                if (0==$msg['errcode']) {
                    return $this->d1_check_user_gate($bind_id, $param, 1);
                }
            } else {
                return array('errcode' => '1001', 'errmsg' => $info['msg']);
            }
        }
    }

    /** 人员开门
     * Notes:
     * @param $device_id
     * @param $pigcms_id
     * @param array $device_info
     * @return array|mixed|string
     * @author: wanzy
     * @date_time: 2021/1/4 16:09
     */
    public function d1_send_open($device_id, $pigcms_id, $device_info=[]) {
        $db_house_face_device = M('House_face_device');
        if (empty($device_info)) {
            $where_device = [
                'device_id' => $device_id
            ];
            $device_info = $db_house_face_device->where($where_device)->find();
        }
        $guid = $device_info['device_sn'];

        $db_face_user_open_key = M('Face_user_open_key');
        $where_open_key = [
            'bind_id' => $pigcms_id,
            'open_key_status' => 1,
            'phone' => ['neq', ''],
            'open_key_time' => ['gt', time()],
        ];
        $where_open_key['device_type'] = 21;
        $key_info = $db_face_user_open_key->where($where_open_key)->find();
        if (empty($key_info)) {
            $db_face_user_bind_device = M('Face_user_bind_device');
            $where_bind = [
                'bind_type' => 0,
                'bind_id' => $pigcms_id,
                'device_id' => 0,
                'device_type' => 21,
                'open_key_time' => ['gt', time()],
            ];
            $bind_key = $db_face_user_bind_device->where($where_bind)->order('open_key_time DESC, id DESC')->getField('open_key');
            $where_bind = [
                'pigcms_id' => $pigcms_id,
                'status' => 1
            ];
            $phone = M('House_village_user_bind')->where($where_bind)->getField('phone');
            if ($bind_key && $phone) {
                $key_info['phone'] = $phone;
            }
        }
        if ($key_info) {
            $msg = $this->d1_user_open($pigcms_id,$key_info['phone'], $guid);
            if ($msg['code']==1) {
                // 定制计划任务跑获取开门记录
                $db_face_bind_about_info = M('Face_bind_about_info');
                $where_bind = [
                    'bind_type' => 0,
                    'bind_id' => $device_info['village_id'],
                    'device_id' => 0,
                    'device_type' => 21,
                ];
                $project_uid = $db_face_bind_about_info->where($where_bind)->order('id ASC')->getField('third_id');
                if ($project_uid) {
                    $param = array();
                    $arr= array();
                    $now_time = time();
                    $param['type'] = 'd1_open_door_list';
                    $unique_id = 'd1_open_door_list';
                    $param['device_id'] = $device_id;
                    $param['project_uid'] = $project_uid;
                    $param['device_info'] = $device_info;
                    if (isset($param['project_uid'])) {
                        $unique_id .='_'.$param['project_uid'];
                    }
                    if (isset($param['device_id'])) {
                        $unique_id .='_'.$param['device_id'];
                    }
                    $param['unique_id'] = $unique_id;
                    $param['time'] = $now_time;
                    $arr['param'] = serialize($param);
                    $arr['plan_time'] = 1;
                    $arr['space_time'] = 0;
                    $arr['add_time'] = $now_time;
                    $arr['file'] = 'sub_house_face_open_list';
                    $arr['time_type'] = 1;
                    $arr['unique_id'] = $unique_id;
                    $arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
                    $add_id = M('process_sub_plan')->data($arr)->add();
                    if (!$add_id) {
                        fdump_api(['添加计划任务'.__LINE__,$add_id, $arr, M('process_sub_plan')->_sql(),M('process_sub_plan')->getDbError()],'D1/plan_d1_door_open_record_err_log',1);
                    }
                }
            }
            return $msg;
        } else {
            // 如果当前用户不存在钥匙  直接使用其他钥匙进行开门
            $where_open_key = [
                'open_key_status' => 1,
                'phone' => ['neq', ''],
                'open_key_time' => ['gt', time()],
            ];
            if ($device_info['floor_id']) {
                $floor_info = M('House_village_floor')->where(['floor_id' => $device_info['floor_id']])->field('floor_id, single_id')->find();
                if ($floor_info && $floor_info['single_id']) {
                    $where_open_key['_string'] = "building_id = {$floor_info['single_id']}";
                } else {
                    $where_open_key['_string'] = "floor_id = {$device_info['floor_id']}";
                }
                $where_open_key['_string'] .= " OR building_id=0";
            }
            $where_open_key['device_type'] = 21;
            $key_info = $db_face_user_open_key->where($where_open_key)->order('key_id DESC')->find();
            if ($key_info) {
                $msg=$this->d1_user_open($pigcms_id,$key_info['phone'], $guid);
                if ($msg['code']==1) {
                    // 定制计划任务跑获取开门记录
                    $db_face_bind_about_info = M('Face_bind_about_info');
                    $where_bind = [
                        'bind_type' => 0,
                        'bind_id' => $device_info['village_id'],
                        'device_id' => 0,
                        'device_type' => 21,
                    ];
                    $project_uid = $db_face_bind_about_info->where($where_bind)->order('id ASC')->getField('third_id');
                    if ($project_uid) {
                        $param = array();
                        $arr= array();
                        $now_time = time();
                        $param['type'] = 'd1_open_door_list';
                        $unique_id = 'd1_open_door_list';
                        $param['device_id'] = $device_id;
                        $param['project_uid'] = $project_uid;
                        $param['device_info'] = $device_info;
                        if (isset($param['project_uid'])) {
                            $unique_id .='_'.$param['project_uid'];
                        }
                        if (isset($param['device_id'])) {
                            $unique_id .='_'.$param['device_id'];
                        }
                        $param['unique_id'] = $unique_id;
                        $param['time'] = $now_time;
                        $arr['param'] = serialize($param);
                        $arr['plan_time'] = 1;
                        $arr['space_time'] = 0;
                        $arr['add_time'] = $now_time;
                        $arr['file'] = 'sub_house_face_open_list';
                        $arr['time_type'] = 1;
                        $arr['unique_id'] = $unique_id;
                        $arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
                        $add_id = M('process_sub_plan')->data($arr)->add();
                        if (!$add_id) {
                            fdump_api(['添加计划任务'.__LINE__,$add_id, $arr, M('process_sub_plan')->_sql(),M('process_sub_plan')->getDbError()],'D1/plan_d1_door_open_record_err_log',1);
                        }
                    }
                }
                return $msg;
            } else {
                return array('code' => '200', 'msg' => '开门失败');
            }
        }
    }

    /**
     * Notes:远程开门
     * @param $bind_id
     * @param $phone
     * @param $guid
     * @param int $repeat_post
     * @return array|mixed|string
     * @author: wanzy
     * @date_time: 2021/1/4 15:16
     */
    public function d1_user_open($bind_id,$phone, $guid, $repeat_post=0) {
        $access_token_info = $this->d1_user_login($phone, 'developer');
        if (0==$access_token_info['errcode']) {
            $access_token = $access_token_info['access_token'];
            $authorization = $access_token;
            $param = [];
            $url = $this->d1_base_url . '/webapp/v1/usergate/open';
            $param['guid'] = $guid;
            $request = 'POST';
            $info = $this->curl_post($url, $param, $authorization, $request);
            fdump(['钥匙开门[远程开门]>>>'.__LINE__,$url,$param,$authorization,$info,$bind_id,$phone,$access_token_info],'api/log/'.date('Ymd').'/D1/d1_user_open_log',true);
            if ($info && $info['code']==200) {
                return array('code'=>1,'msg'=>'开门成功','data'=>$info);
            } elseif (400==$info['code'] && 0==$repeat_post) {
                $msg = $this->d1_user_login($phone, 'developer',1);
                if (0==$msg['errcode']) {
                    return $this->d1_user_open($bind_id, $phone, $guid, 1);
                }
            } elseif (1006==$info['code'] && 0==$repeat_post) {
                $msg = $this->d1_user_login($phone, 'developer',1);
                if (0==$msg['errcode']) {
                    return $this->d1_user_open($bind_id, $phone, $guid, 1);
                }
            } elseif (1021==$info['code'] && 0==$repeat_post) {
                $this->d1_third_user_info($bind_id);
                return array('code' => '200', 'msg' => $info['msg']);
            } elseif (14015 == $info['code'] && 0==$repeat_post) {
                $msg = $this->d1_user_login($phone, 'developer',1);
                if (0==$msg['errcode']) {
                    return $this->d1_user_open($bind_id, $phone, $guid, 1);
                }
            } else {
                // 不论什么报错 todo 清除重新调用一次
                $msg = $this->d1_user_login($phone, 'developer',1);
                if (0==$msg['errcode']) {
                    return $this->d1_user_open($bind_id, $phone, $guid, 1);
                }
            }
            return array('code' => '200', 'msg' => $info['msg']);
        } else {
            fdump('钥匙开门[远程开门] 登录错误>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_user_open_err_log',true);
            fdump($bind_id,'api/log/'.date('Ymd').'/D1/d1_user_open_err_log',true);
            fdump($phone,'api/log/'.date('Ymd').'/D1/d1_user_open_err_log',true);
            fdump($access_token_info,'api/log/'.date('Ymd').'/D1/d1_user_open_err_log',true);
            return array('code' => '1001', 'msg' => '登录错误');
        }
    }

    // 同步调用一旦存在一个设备同步成功后进行调用  只用同步一次
    public function d1_sync_all_data() {
        $config_data = D('Config_data')->get_config_data();
        if (!$config_data['d1_sync_projects'] || $config_data['d1_sync_projects']!=1) {
            $this->d1_sync_projects();
        }
        if (!$config_data['d1_sync_buildings'] || $config_data['d1_sync_buildings']!=1) {
            $this->d1_sync_buildings();
        }
        if (!$config_data['d1_sync_rooms'] || $config_data['d1_sync_rooms']!=1) {
            $this->d1_sync_rooms();
        }
//        if (!$config_data['d1_sync_cards'] || $config_data['d1_sync_cards']!=1) {
//            $this->d1_sync_cards();
//        }
        if (!$config_data['d1_sync_hosts'] || $config_data['d1_sync_hosts']!=1) {
            $this->d1_sync_hosts();
        }
        if (!$config_data['d1_sync_tenants'] || $config_data['d1_sync_tenants']!=1) {
            $this->d1_sync_tenants();
        }
        if (!$config_data['d1_sync_captures'] || $config_data['d1_sync_captures']!=1) {
            $this->d1_sync_captures();
        }
        return true;
    }
    /**
     * Notes: 同步所有项目
     * @return array|bool
     * @author: wanzy
     * @date_time: 2020/12/26 15:45
     */
    public function d1_sync_projects($repeat_post=0) {
        if (!$this->userphone) {
            $this->d1_get_config();
        }
        if (!$this->userphone || !$this->password || !$this->app_id || !$this->app_secret) {
            return array('errcode' => '1001', 'errmsg' => '缺少必要配置项');
        }
        $access_token_info = $this->d1_login();

        if (0==$access_token_info['errcode']) {
            $access_token = $access_token_info['access_token'];
            $authorization = $access_token;
            $url = $this->d1_base_url . '/openapi/v3/sync/projects';
            $param = [];
            $param['page'] = 1;
            $param['size'] = 100;
            $query_param = http_build_query($param);
            $url .= '?'.$query_param;
            $request = 'GET';
            $info = $this->curl_post($url, [], $authorization, $request);
            fdump('同步所有项目[同步所有小区]>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_sync_projects_log',true);
            fdump($url,'api/log/'.date('Ymd').'/D1/d1_sync_projects_log',true);
            fdump($param,'api/log/'.date('Ymd').'/D1/d1_sync_projects_log',true);
            fdump($authorization,'api/log/'.date('Ymd').'/D1/d1_sync_projects_log',true);
            fdump($info,'api/log/'.date('Ymd').'/D1/d1_sync_projects_log',true);
            if ($info && $info['code']==200) {
                D('Config_data')->save_config_data('d1_sync_projects', 1);
                return true;
            } elseif (400==$info['code'] && 0==$repeat_post) {
                $msg = $this->d1_refresh_token();
                if (0==$msg['errcode']) {
                    return $this->d1_sync_projects(1);
                }
            } else {
                return array('errcode' => '1001', 'errmsg' => $info['msg']);
            }

        } else {
            fdump('同步所有项目[同步所有小区] 失败token错误>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_sync_projects_err_log',true);
            fdump($this->userphone,'api/log/'.date('Ymd').'/D1/d1_sync_projects_err_log',true);
            fdump($this->password,'api/log/'.date('Ymd').'/D1/d1_sync_projects_err_log',true);
            fdump($this->app_id,'api/log/'.date('Ymd').'/D1/d1_sync_projects_err_log',true);
            fdump($this->app_secret,'api/log/'.date('Ymd').'/D1/d1_sync_projects_err_log',true);
            fdump($access_token_info,'api/log/'.date('Ymd').'/D1/d1_sync_projects_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '获取失败');
        }
    }

    /**
     * Notes: 同步所有楼栋
     * @return array|bool
     * @author: wanzy
     * @date_time: 2020/12/26 15:47
     */
    public function d1_sync_buildings($repeat_post=0) {
        if (!$this->userphone) {
            $this->d1_get_config();
        }
        if (!$this->userphone || !$this->password || !$this->app_id || !$this->app_secret) {
            return array('errcode' => '1001', 'errmsg' => '缺少必要配置项');
        }
        $access_token_info = $this->d1_login();

        if (0==$access_token_info['errcode']) {
            $access_token = $access_token_info['access_token'];
            $authorization = $access_token;
            $url = $this->d1_base_url . '/openapi/v3/sync/buildings';
            $param = [];
            $param['page'] = 1;
            $param['size'] = 100;
            $query_param = http_build_query($param);
            $url .= '?'.$query_param;
            $request = 'GET';
            $info = $this->curl_post($url, [], $authorization, $request);
            fdump('同步所有楼栋>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_sync_buildings_log',true);
            fdump($url,'api/log/'.date('Ymd').'/D1/d1_sync_buildings_log',true);
            fdump($param,'api/log/'.date('Ymd').'/D1/d1_sync_buildings_log',true);
            fdump($authorization,'api/log/'.date('Ymd').'/D1/d1_sync_buildings_log',true);
            fdump($info,'api/log/'.date('Ymd').'/D1/d1_sync_buildings_log',true);
            if ($info && $info['code']==200) {
                D('Config_data')->save_config_data('d1_sync_buildings', 1);
                return true;
            } elseif (400==$info['code'] && 0==$repeat_post) {
                $msg = $this->d1_refresh_token();
                if (0==$msg['errcode']) {
                    return $this->d1_sync_buildings(1);
                }
            } else {
                return array('errcode' => '1001', 'errmsg' => $info['msg']);
            }
        } else {
            fdump('同步所有楼栋 失败token错误>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_sync_buildings_err_log',true);
            fdump($this->userphone,'api/log/'.date('Ymd').'/D1/d1_sync_buildings_err_log',true);
            fdump($this->password,'api/log/'.date('Ymd').'/D1/d1_sync_buildings_err_log',true);
            fdump($this->app_id,'api/log/'.date('Ymd').'/D1/d1_sync_buildings_err_log',true);
            fdump($this->app_secret,'api/log/'.date('Ymd').'/D1/d1_sync_buildings_err_log',true);
            fdump($access_token_info,'api/log/'.date('Ymd').'/D1/d1_sync_buildings_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '获取失败');
        }
    }

    /** 同步所有房屋
     * Notes:
     * @return array|bool
     * @author: wanzy
     * @date_time: 2020/12/26 15:57
     */
    public function d1_sync_rooms($repeat_post=0) {
        if (!$this->userphone) {
            $this->d1_get_config();
        }
        if (!$this->userphone || !$this->password || !$this->app_id || !$this->app_secret) {
            return array('errcode' => '1001', 'errmsg' => '缺少必要配置项');
        }
        $access_token_info = $this->d1_login();

        if (0==$access_token_info['errcode']) {
            $access_token = $access_token_info['access_token'];
            $authorization = $access_token;
            $url = $this->d1_base_url . '/openapi/v3/sync/rooms';
            $param = [];
            $param['page'] = 1;
            $param['size'] = 100;
            $query_param = http_build_query($param);
            $url .= '?'.$query_param;
            $request = 'GET';
            $info = $this->curl_post($url, [], $authorization, $request);
            fdump('同步所有房屋>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_sync_rooms_log',true);
            fdump($url,'api/log/'.date('Ymd').'/D1/d1_sync_rooms_log',true);
            fdump($param,'api/log/'.date('Ymd').'/D1/d1_sync_rooms_log',true);
            fdump($authorization,'api/log/'.date('Ymd').'/D1/d1_sync_rooms_log',true);
            fdump($info,'api/log/'.date('Ymd').'/D1/d1_sync_rooms_log',true);
            if ($info && $info['code']==200) {
                D('Config_data')->save_config_data('d1_sync_rooms', 1);
                return true;
            } elseif (400==$info['code'] && 0==$repeat_post) {
                $msg = $this->d1_refresh_token();
                if (0==$msg['errcode']) {
                    return $this->d1_sync_rooms(1);
                }
            } else {
                return array('errcode' => '1001', 'errmsg' => $info['msg']);
            }
        } else {
            fdump('同步所有房屋 失败token错误>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_sync_rooms_err_log',true);
            fdump($this->userphone,'api/log/'.date('Ymd').'/D1/d1_sync_rooms_err_log',true);
            fdump($this->password,'api/log/'.date('Ymd').'/D1/d1_sync_rooms_err_log',true);
            fdump($this->app_id,'api/log/'.date('Ymd').'/D1/d1_sync_rooms_err_log',true);
            fdump($this->app_secret,'api/log/'.date('Ymd').'/D1/d1_sync_rooms_err_log',true);
            fdump($access_token_info,'api/log/'.date('Ymd').'/D1/d1_sync_rooms_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '获取失败');
        }
    }

    /**
     * Notes:同步所有门卡(暂时不同步)
     * @return array|bool
     * @author: wanzy
     * @date_time: 2020/12/26 15:58
     */
    public function d1_sync_cards($repeat_post=0) {
        if (!$this->userphone) {
            $this->d1_get_config();
        }
        if (!$this->userphone || !$this->password || !$this->app_id || !$this->app_secret) {
            return array('errcode' => '1001', 'errmsg' => '缺少必要配置项');
        }
        $access_token_info = $this->d1_login();

        if (0==$access_token_info['errcode']) {
            $access_token = $access_token_info['access_token'];
            $authorization = $access_token;
            $url = $this->d1_base_url . '/openapi/v3/sync/cards';
            $param = [];
            $param['page'] = 1;
            $param['size'] = 100;
            $query_param = http_build_query($param);
            $url .= '?'.$query_param;
            $request = 'GET';
            $info = $this->curl_post($url, [], $authorization, $request);
            fdump('同步所有门卡>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_sync_cards_log',true);
            fdump($url,'api/log/'.date('Ymd').'/D1/d1_sync_cards_log',true);
            fdump($param,'api/log/'.date('Ymd').'/D1/d1_sync_cards_log',true);
            fdump($authorization,'api/log/'.date('Ymd').'/D1/d1_sync_cards_log',true);
            fdump($info,'api/log/'.date('Ymd').'/D1/d1_sync_cards_log',true);
            if ($info && $info['code']==200) {
                D('Config_data')->save_config_data('d1_sync_cards', 1);
                return true;
            } elseif (400==$info['code'] && 0==$repeat_post) {
                $msg = $this->d1_refresh_token();
                if (0==$msg['errcode']) {
                    return $this->d1_sync_cards(1);
                }
            } else {
                return array('errcode' => '1001', 'errmsg' => $info['msg']);
            }
        } else {
            fdump('同步所有门卡 失败token错误>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_sync_cards_err_log',true);
            fdump($this->userphone,'api/log/'.date('Ymd').'/D1/d1_sync_cards_err_log',true);
            fdump($this->password,'api/log/'.date('Ymd').'/D1/d1_sync_cards_err_log',true);
            fdump($this->app_id,'api/log/'.date('Ymd').'/D1/d1_sync_cards_err_log',true);
            fdump($this->app_secret,'api/log/'.date('Ymd').'/D1/d1_sync_cards_err_log',true);
            fdump($access_token_info,'api/log/'.date('Ymd').'/D1/d1_sync_cards_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '获取失败');
        }
    }

    /**
     * Notes: 同步所有业主
     * @return array|bool
     * @author: wanzy
     * @date_time: 2020/12/26 16:09
     */
    public function d1_sync_hosts($repeat_post=0) {
        if (!$this->userphone) {
            $this->d1_get_config();
        }
        if (!$this->userphone || !$this->password || !$this->app_id || !$this->app_secret) {
            return array('errcode' => '1001', 'errmsg' => '缺少必要配置项');
        }
        $access_token_info = $this->d1_login();

        if (0==$access_token_info['errcode']) {
            $access_token = $access_token_info['access_token'];
            $authorization = $access_token;
            $url = $this->d1_base_url . '/openapi/v3/sync/hosts';
            $param = [];
            $param['page'] = 1;
            $param['size'] = 100;
            $query_param = http_build_query($param);
            $url .= '?'.$query_param;
            $request = 'GET';
            $info = $this->curl_post($url, [], $authorization, $request);
            fdump('同步所有业主>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_sync_hosts_log',true);
            fdump($url,'api/log/'.date('Ymd').'/D1/d1_sync_hosts_log',true);
            fdump($param,'api/log/'.date('Ymd').'/D1/d1_sync_hosts_log',true);
            fdump($authorization,'api/log/'.date('Ymd').'/D1/d1_sync_hosts_log',true);
            fdump($info,'api/log/'.date('Ymd').'/D1/d1_sync_hosts_log',true);
            if ($info && $info['code']==200) {
                D('Config_data')->save_config_data('d1_sync_hosts', 1);
                return true;
            } elseif (400==$info['code'] && 0==$repeat_post) {
                $msg = $this->d1_refresh_token();
                if (0==$msg['errcode']) {
                    return $this->d1_sync_hosts(1);
                }
            } else {
                return array('errcode' => '1001', 'errmsg' => $info['msg']);
            }
        } else {
            fdump('同步所有业主 失败token错误>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_sync_hosts_err_log',true);
            fdump($this->userphone,'api/log/'.date('Ymd').'/D1/d1_sync_hosts_err_log',true);
            fdump($this->password,'api/log/'.date('Ymd').'/D1/d1_sync_hosts_err_log',true);
            fdump($this->app_id,'api/log/'.date('Ymd').'/D1/d1_sync_hosts_err_log',true);
            fdump($this->app_secret,'api/log/'.date('Ymd').'/D1/d1_sync_hosts_err_log',true);
            fdump($access_token_info,'api/log/'.date('Ymd').'/D1/d1_sync_hosts_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '获取失败');
        }
    }

    /**
     * Notes: 同步所有租客
     * @return array|bool
     * @author: wanzy
     * @date_time: 2020/12/26 16:13
     */
    public function d1_sync_tenants($repeat_post=0) {
        if (!$this->userphone) {
            $this->d1_get_config();
        }
        if (!$this->userphone || !$this->password || !$this->app_id || !$this->app_secret) {
            return array('errcode' => '1001', 'errmsg' => '缺少必要配置项');
        }
        $access_token_info = $this->d1_login();

        if (0==$access_token_info['errcode']) {
            $access_token = $access_token_info['access_token'];
            $authorization = $access_token;
            $url = $this->d1_base_url . '/openapi/v3/sync/tenants';
            $param = [];
            $param['page'] = 1;
            $param['size'] = 100;
            $query_param = http_build_query($param);
            $url .= '?'.$query_param;
            $request = 'GET';
            $info = $this->curl_post($url, [], $authorization, $request);
            fdump('同步所有租客>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_sync_tenants_log',true);
            fdump($url,'api/log/'.date('Ymd').'/D1/d1_sync_tenants_log',true);
            fdump($param,'api/log/'.date('Ymd').'/D1/d1_sync_tenants_log',true);
            fdump($authorization,'api/log/'.date('Ymd').'/D1/d1_sync_tenants_log',true);
            fdump($info,'api/log/'.date('Ymd').'/D1/d1_sync_tenants_log',true);
            if ($info && $info['code']==200) {
                D('Config_data')->save_config_data('d1_sync_tenants', 1);
                return true;
            } elseif (400==$info['code'] && 0==$repeat_post) {
                $msg = $this->d1_refresh_token();
                if (0==$msg['errcode']) {
                    return $this->d1_sync_tenants(1);
                }
            } else {
                return array('errcode' => '1001', 'errmsg' => $info['msg']);
            }
        } else {
            fdump('同步所有租客 失败token错误>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_sync_tenants_err_log',true);
            fdump($this->userphone,'api/log/'.date('Ymd').'/D1/d1_sync_tenants_err_log',true);
            fdump($this->password,'api/log/'.date('Ymd').'/D1/d1_sync_tenants_err_log',true);
            fdump($this->app_id,'api/log/'.date('Ymd').'/D1/d1_sync_tenants_err_log',true);
            fdump($this->app_secret,'api/log/'.date('Ymd').'/D1/d1_sync_tenants_err_log',true);
            fdump($access_token_info,'api/log/'.date('Ymd').'/D1/d1_sync_tenants_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '获取失败');
        }
    }

    /**
     * Notes: 同步所有开门记录
     * @return array|bool
     * @author: wanzy
     * @date_time: 2020/12/26 16:14
     */
    public function d1_sync_captures($repeat_post=0) {
        if (!$this->userphone) {
            $this->d1_get_config();
        }
        if (!$this->userphone || !$this->password || !$this->app_id || !$this->app_secret) {
            return array('errcode' => '1001', 'errmsg' => '缺少必要配置项');
        }
        $access_token_info = $this->d1_login();

        if (0==$access_token_info['errcode']) {
            $access_token = $access_token_info['access_token'];
            $authorization = $access_token;
            $url = $this->d1_base_url . '/openapi/v3/sync/captures';
            $param = [];
            $param['page'] = 1;
            $param['size'] = 100;
            $query_param = http_build_query($param);
            $url .= '?'.$query_param;
            $request = 'GET';
            $info = $this->curl_post($url, [], $authorization, $request);
            fdump('同步所有开门记录>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_sync_captures_log',true);
            fdump($url,'api/log/'.date('Ymd').'/D1/d1_sync_captures_log',true);
            fdump($param,'api/log/'.date('Ymd').'/D1/d1_sync_captures_log',true);
            fdump($authorization,'api/log/'.date('Ymd').'/D1/d1_sync_captures_log',true);
            fdump($info,'api/log/'.date('Ymd').'/D1/d1_sync_captures_log',true);
            if ($info && $info['code']==200) {
                $data = $info['data']['data'];
                if ($data) {
                    $d_face_user_bind_device = D('Face_user_bind_device');
                    $d_house_village_user_bind = D('House_village_user_bind');
                    $d_house_village = M('House_village');

                    $d_house_camera_tip = M('House_camera_tip');
                    $db_house_face_device = M('House_face_device');
                    $d_house_user_log = M('House_user_log');
                    foreach ($data as $val) {
                        $log = [];
                        $where_repeat = [
                            'eventId' => $val['uid']
                        ];
                        $repeat_id = $d_house_user_log->where($where_repeat)->getField('log_id');
                        if ($repeat_id) {
                            continue;
                        } elseif ($val['gate_name']) {
                            $gaet_arr = explode('_',$val['gate_name']);
                            $device_id = array_pop($gaet_arr);
                            $where_device = [
                                'device_id' => $device_id
                            ];
                            $device_info = $db_house_face_device->where($where_device)->find();
                            $village_id = $device_info['village_id'];

                            $house_village_info = $d_house_village->where(array('village_id' => $village_id))->field('village_id,village_name,city_id')->find();
                            $village_name = $house_village_info['village_name'];
                            $city_id = $house_village_info['city_id'];

                            if ( $device_info['public_area_id'] >0 ) {
                                $public_area_name = M('House_village_public_area')->where(['public_area_id' => $device_info['public_area_id']])->getField('public_area_name');
                                $floor_name = '';
                                $floor_layer = $public_area_name;
                            } elseif ($device_info['floor_id'] == -1) {
                                $floor_layer = C('config.house_name');
                                $floor_name = '大门';
                            } else {
                                $d_house_village_single = D('House_village_single');
                                $d_house_village_floor = M('House_village_floor');
                                $house_village_floor_info = $d_house_village_floor->where(array('floor_id' => $device_info['floor_id']))->field('floor_id,floor_layer,floor_name,single_id')->find();
                                $house_village_single_info = $d_house_village_single->where(['id' => $device_info['single_id']])->field('id as single_id,single_name')->find();
                                $floor_layer = $house_village_single_info['single_name'] ? $house_village_single_info['single_name'] : $house_village_floor_info['floor_layer'];
                                $floor_name = $house_village_floor_info['floor_name'];
                            }
                        } else {
                            continue;
                        }
                        if ($val['user_realname']) {
                            $bind_arr = explode('_',$val['user_realname']);
                            $bind_id = array_pop($bind_arr);
                            $where_user_bind = [
                                'pigcms_id' => $bind_id
                            ];
                            $user_bind_device = $d_face_user_bind_device->where($where_user_bind)->field('id,bind_id')->find();
                        }
                        if ($user_bind_device) {
                            $house_village_bind = $d_house_village_user_bind->where(['pigcms_id' => $user_bind_device['bind_id']])->field('uid,pigcms_id')->find();
                            $uid = $house_village_bind['uid'];
                            $bind_id = $house_village_bind['pigcms_id'];
                            if ($bind_id) {
                                hook::hook_exec('house_cost_notice',array('bind_id'=>$bind_id));
                            }
                            $log['uid'] = $uid;
                            $log['log_bind_id'] = $bind_id;
                            $log['log_name'] = $village_name . ' ' . $floor_layer . ' ' . $floor_name;
                            $log['log_from'] = 1; // 人脸识别门禁
                            $log['log_business_id'] = $village_id?$village_id:0; // 社区id
                            $log['log_more_id'] = $device_info['floor_id']?$device_info['floor_id']:0; // 单元id
                            $log['device_id'] = $device_info['device_id']; // 设备id
                            if ($val['created_at']) {
                                $log['log_time'] = $val['created_at']; // 记录时间
                            } else {
                                $log['log_time'] = time(); // 记录时间
                            }
                            $log['temperature'] = $val['temp'] ? $val['temp'] : '0.0';
                            $log['log_status'] = 0;
                            if ($val['origin_thumb']) {
                                $val['face_img'] = D('Face_door_service')->face_to_img($val['origin_thumb'],$device_id);
                            }
                            $log['log_detail'] = serialize($val);
                            $log['eventId'] = $_POST['id'];
                            fdump('同步开门信息》》》'.__LINE__,'api/log/'.date('Ymd').'/D1/face_d1_open_door_notify_log',true);
                            fdump($log,'api/log/'.date('Ymd').'/D1/face_d1_open_door_notify_log',true);
                            if (isset($val['face_img']) && trim($val['face_img'])) {
                                $log['cut_image'] = trim($val['face_img']);
                            }
                            $log['add_time']=time();
                            $d_house_user_log->data($log)->add();
                        } else {
                            $log['uid'] = 0;
                            $log['log_bind_id'] = 0;
                            $log['log_name'] = $village_name . ' ' . $floor_layer . ' ' . $floor_name;
                            $log['log_from'] = 1; // 人脸识别门禁
                            $log['log_business_id'] = $village_id?$village_id:0; // 社区id
                            $log['log_more_id'] = $device_info['floor_id']?$device_info['floor_id']:0; // 单元id
                            $log['device_id'] = $device_info['device_id']; // 设备id
                            if ($val['created_at']) {
                                $log['log_time'] = $val['created_at']; // 记录时间
                            } else {
                                $log['log_time'] = time(); // 记录时间
                            }
                            $log['temperature'] = $val['temp'] ? $val['temp'] : '0.0';
                            $log['log_status'] = 0;
                            if ($val['origin_thumb']) {
                                $val['face_img'] = D('Face_door_service')->face_to_img($val['origin_thumb'],$device_id);
                            }
                            if ($val['small_thumb']) {
                                $val['small_face_img'] = D('Face_door_service')->face_to_img($val['small_thumb'],$device_id);
                            }
                            $log['log_detail'] = serialize($val);
                            $log['eventId'] = $val['uid'];
                            fdump('同步开门信息》》》'.__LINE__,'api/log/'.date('Ymd').'/D1/face_d1_open_door_notify_log',true);
                            fdump($log,'api/log/'.date('Ymd').'/D1/face_d1_open_door_notify_log',true);
                            if (isset($val['face_img']) && trim($val['face_img'])) {
                                $log['cut_image'] = trim($val['face_img']);
                            } elseif (isset($val['small_face_img']) && trim($val['small_face_img'])) {
                                $log['cut_image'] = trim($val['small_face_img']);
                            }
                            $log['add_time']=time();
                            $d_house_user_log->data($log)->add();
                            $status = 1;
                            $uid = 0;
                            $bind_id = 0;
                        }

                        $tip = array(
                            'bind_id' =>  $bind_id,
                            'uid' => $uid,
                            'type' => 1,
                            'village_id' => $village_id ? $village_id : 0,
                            'camera_sn' => $device_info['device_sn'],
                            'city_id' => $city_id ? $city_id : 0,
                            'camera_time' => time(),
                            'camera_img' => $log['face_img']?$log['face_img']:$val['origin_thumb'],
                            'tip_time' => $log['log_time'],
                            'status' => $status,
                            'tip_txt' => serialize($val),
                            'temperature' => $val['temp'] ? $val['temp'] : '0.0',
                        );
                        fdump('同步开门信息》》》'.__LINE__,'api/log/'.date('Ymd').'/D1/face_d1_open_door_notify_log',true);
                        fdump($tip,'api/log/'.date('Ymd').'/D1/face_d1_open_door_notify_log',true);
                        $d_house_camera_tip->data($tip)->add();
                    }
                }



                D('Config_data')->save_config_data('d1_sync_captures', 1);
                return true;
            } elseif (400==$info['code'] && 0==$repeat_post) {
                $msg = $this->d1_refresh_token();
                if (0==$msg['errcode']) {
                    return $this->d1_sync_captures(1);
                }
            } else {
                return array('errcode' => '1001', 'errmsg' => $info['msg']);
            }
        } else {
            fdump('同步所有开门记录 失败token错误>>>'.__LINE__,'api/log/'.date('Ymd').'/D1/d1_sync_captures_err_log',true);
            fdump($this->userphone,'api/log/'.date('Ymd').'/D1/d1_sync_captures_err_log',true);
            fdump($this->password,'api/log/'.date('Ymd').'/D1/d1_sync_captures_err_log',true);
            fdump($this->app_id,'api/log/'.date('Ymd').'/D1/d1_sync_captures_err_log',true);
            fdump($this->app_secret,'api/log/'.date('Ymd').'/D1/d1_sync_captures_err_log',true);
            fdump($access_token_info,'api/log/'.date('Ymd').'/D1/d1_sync_captures_err_log',true);
            return array('errcode' => '1001', 'errmsg' => '获取失败');
        }
    }

    /**
     * Notes: 接口调用
     * @param string $url 请求路径
     * @param array $params 请求参数
     * @param string $authorization 请求权限头部
     * @param string $request 请求方式
     * @return bool|mixed|string|array
     * @author: wanzy
     * @date_time: 2020/12/21 16:03
     */
    public function curl_post($url,$params,$authorization='',$request='POST'){
        $ch = curl_init();
        $header =  array(
            "Accept: application/json",
        );
        if ($authorization) {
            $header[] =   "Authorization: ".$authorization;
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $request);
        curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        $result = curl_exec($ch);
        $request_header = curl_getinfo( $ch, CURLINFO_HEADER_OUT);
        curl_close($ch);
        $result = json_decode($result,true);
        return $result;
    }
}