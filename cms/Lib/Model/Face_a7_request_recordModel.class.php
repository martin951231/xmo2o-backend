<?php
/**
 * a7人脸设备相关下发或者操作记录
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2020/9/5 10:23
 *
 */

class Face_a7_request_recordModel extends Model {

    public $version = '0.2';

    public $create_face_code = [
        400 => '操作错误',
        401 => '操作错误',
        402 => '设备已存在该人脸信息',
        403 => '录入人脸的name非uft8编码',
        404 => '录入人脸失败',
        406 => '设备人脸录入数量已达上限',
        407 => '人脸像素太大（大于1072）',
        408 => '图片格式错误',
        409 => '图片分辨率错误',
        410 => '获取到的特征数据为空',
        411 => '下发协议数据格式错误（姓名或id不为string）',
        -1 => '图像尺寸超出允许范围',
        -2 => '图像预处理失败',
        -3 => '人脸检测出错',
        -4 => '未检测到人脸',
        -5 => '检测到图片中存在多个人脸',
        -6 => '未通过人脸质量筛选',
        -7 => '提取特征失败',
        -8 => '特写图像JPEG数据缓存区过小',
        -9 => '编码JPEG失败',
    ];
    public $update_face_code = [
        400 => '操作错误',
        401 => '操作错误',
        402 => '设备已存在该人脸信息',
        403 => '更新人脸的name非uft8编码',
        404 => '录入人脸失败',
        406 => '设备人脸录入数量已达上限',
        407 => '人脸像素太大（大于1072）',
        408 => '图片格式错误',
        409 => '图片分辨率错误',
        410 => '图片中未检测到合格的人脸',
        415 => '人员id非string类型',
        416 => '更新设备人脸库失败',
        417 => '设备不存在该人员,请重新同步',
        -1 => '图像尺寸超出允许范围',
        -2 => '图像预处理失败',
        -3 => '人脸检测出错',
        -4 => '未检测到人脸',
        -5 => '检测到图片中存在多个人脸',
        -6 => '未通过人脸质量筛选',
        -7 => '提取特征失败',
        -8 => '特写图像JPEG数据缓存区过小',
        -9 => '编码JPEG失败',
    ];

    public $image;

    /**
     * Notes: 设备重启系统
     * @param int $device_id 设备id
     * @param array $data 需要添加的相关数据
     * @param int $village_id 小区id
     * @param array $device_info 设备信息-选传
     * @return array
     * @author: wanzy
     * @date_time: 2020/9/5 10:44
     */
    public function add_reboot_cam_record($device_id,$data,$village_id=0,$device_info=[]) {
        if (empty($device_info)) {
            $device_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        }
        if (!$village_id && $device_info['village_id']) {
            $village_id = $device_info['village_id'];
        }
        // 具体请求区分名称
        $cmd_name = 'reboot_cam';
        $ret_data = [
            'error' => 0,
            'msg' => ''
        ];
        $where_repeat = [
            'device_id' => $device_id,
            'cmd_name' => $cmd_name,
            'status' => 0,
        ];
        $repeat_id = M('Face_a7_request_record')->where($where_repeat)->getField('id');
        if ($repeat_id) {
            // 直接将之前未执行的命令改为已执行
            M('Face_a7_request_record')->where(['id' => $repeat_id])->data(['status' => 1, 'update_time' => time()])->save();
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
            fdump_api(['添加失败：'.__LINE__,$device_id, $data,$village_id,$device_info,$data_msg,$add_data,$this->_sql(),$this->getDbError(), $ret_data],'a7/add_reboot_cam_record_log',1);
            return $ret_data;
        }
    }

    /**
     * Notes: 设置人脸比对确性分数
     * @param int $device_id 设备id
     * @param array $data 需要添加的相关数据
     * @param int $village_id 小区id
     * @param array $device_info 设备信息-选传
     * @return array
     * @author: wanzy
     * @date_time: 2020/9/5 11:05
     */
    public function add_set_match_score_record($device_id,$data=[],$village_id=0,$device_info=[]) {
        if (empty($device_info)) {
            $device_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        }
        if (!$village_id && $device_info['village_id']) {
            $village_id = $device_info['village_id'];
        }
        // 具体请求区分名称
        $cmd_name = 'set_match_score';
        $ret_data = [
            'error' => 0,
            'msg' => ''
        ];
        $where_repeat = [
            'device_id' => $device_id,
            'cmd_name' => $cmd_name,
            'status' => 0,
        ];
        $repeat_id = M('Face_a7_request_record')->where($where_repeat)->getField('id');
        if ($repeat_id) {
            // 直接将之前未执行的命令改为已执行
            M('Face_a7_request_record')->where(['id' => $repeat_id])->data(['status' => 1, 'update_time' => time()])->save();
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
            'body' => [
                'score' => isset($data['device_score'])?intval($data['device_score']) : intval($device_info['device_score'])
            ],
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
            $ret_data['error'] = 1;
            $ret_data['msg'] = '添加失败';
            fdump_api(['添加失败：'.__LINE__,$device_id, $data,$village_id,$device_info,$data_msg,$add_data,$this->_sql(),$this->getDbError(), $ret_data],'a7/add_set_match_score_record_log',1);
            return $ret_data;
        }
    }

    /**
     * Notes:设置是否开启活体检测
     * @param int $device_id 设备id
     * @param array $data 需要添加的相关数据
     * @param int $village_id 小区id
     * @param array $device_info 设备信息-选传
     * @return array
     * @author: wanzy
     * @date_time: 2020/9/5 11:05
     */
    public function add_set_spoofing_record($device_id,$data=[],$village_id=0,$device_info=[]) {
        if (empty($device_info)) {
            $device_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        }
        if (!$village_id && $device_info['village_id']) {
            $village_id = $device_info['village_id'];
        }
        // 具体请求区分名称
        $cmd_name = 'set_spoofing';
        $ret_data = [
            'error' => 0,
            'msg' => ''
        ];
        $where_repeat = [
            'device_id' => $device_id,
            'cmd_name' => $cmd_name,
            'status' => 0,
        ];
        $repeat_id = M('Face_a7_request_record')->where($where_repeat)->getField('id');
        if ($repeat_id) {
            // 直接将之前未执行的命令改为已执行
            M('Face_a7_request_record')->where(['id' => $repeat_id])->data(['status' => 1, 'update_time' => time()])->save();
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
            'body' => [
                'status' => isset($data['device_alive'])?intval($data['device_alive']) : intval($device_info['device_alive'])
            ],
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
            $ret_data['error'] = 1;
            $ret_data['msg'] = '添加失败';
            fdump_api(['添加失败：'.__LINE__,$device_id, $data,$village_id,$device_info,$data_msg,$add_data,$this->_sql(),$this->getDbError(), $ret_data],'a7/add_set_spoofing_record_log',1);
            return $ret_data;
        }
    }

    /**
     * Notes:设置是否开启安全帽检测
     * @param int $device_id 设备id
     * @param array $data 需要添加的相关数据
     * @param int $village_id 小区id
     * @param array $device_info 设备信息-选传
     * @return array
     * @author: wanzy
     * @date_time: 2021/1/20 17:24
     */
    public function add_set_attr_record($device_id,$data=[],$village_id=0,$device_info=[]) {
        if (empty($device_info)) {
            $device_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        }
        if (!$village_id && $device_info['village_id']) {
            $village_id = $device_info['village_id'];
        }
        // 具体请求区分名称
        $cmd_name = 'set_attr';
        $ret_data = [
            'error' => 0,
            'msg' => ''
        ];
        $where_repeat = [
            'device_id' => $device_id,
            'cmd_name' => $cmd_name,
            'status' => 0,
        ];
        $repeat_id = M('Face_a7_request_record')->where($where_repeat)->getField('id');
        if ($repeat_id) {
            // 直接将之前未执行的命令改为已执行
            M('Face_a7_request_record')->where(['id' => $repeat_id])->data(['status' => 1, 'update_time' => time()])->save();
        }

        $device_sn = $device_info['device_sn'];
        $operator_type = isset($data['operator_type']) ? intval($data['operator_type']) : 1;
        $operator_id = isset($data['operator_id']) ? intval($data['operator_id']) : $village_id;
        $version = isset($data['version']) ? intval($data['version']) : $this->version;
        $status = 0;
        $add_time = time();
        // 0：不开启，1：开启
        $data_msg = [
            'version' => $version,
            'cmd' => $cmd_name,
            'body' => [
                'status' => $status
            ],
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
            $ret_data['error'] = 1;
            $ret_data['msg'] = '添加失败';

            fdump_api(['设置是否开启安全帽检测：'.__LINE__,$device_id, $data,$village_id,$device_info,$data_msg,$add_data,$this->_sql(),$this->getDbError(), $ret_data],'a7/add_set_attr_record_log',1);
            return $ret_data;
        }
    }

    /**
     * Notes:设置是否可以进行刷卡
     * @param $device_id
     * @param array $data
     * @param int $village_id
     * @param array $device_info
     * @return array
     */
    public function add_set_readwg_way($device_id,$data=[],$village_id=0,$device_info=[]) {
        if (empty($device_info)) {
            $device_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        }
        if (!$village_id && $device_info['village_id']) {
            $village_id = $device_info['village_id'];
        }
        // 具体请求区分名称
        $cmd_name = 'set_readwg_way';
        $ret_data = [
            'error' => 0,
            'msg' => ''
        ];
        $where_repeat = [
            'device_id' => $device_id,
            'cmd_name' => $cmd_name,
            'status' => 0,
        ];
        $repeat_id = M('Face_a7_request_record')->where($where_repeat)->getField('id');
        if ($repeat_id) {
            // 直接将之前未执行的命令改为已执行
            M('Face_a7_request_record')->where(['id' => $repeat_id])->data(['status' => 1, 'update_time' => time()])->save();
        }

        $device_sn = $device_info['device_sn'];
        $operator_type = isset($data['operator_type']) ? intval($data['operator_type']) : 1;
        $operator_id = isset($data['operator_id']) ? intval($data['operator_id']) : $village_id;
        $version = isset($data['version']) ? intval($data['version']) : $this->version;
        $status = 0;
        $add_time = time();
        // 0：不开启，1：开启
        $data_msg = [
            'version' => $version,
            'cmd' => $cmd_name,
            'body' => [
                'way' => 0
            ],
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
            $ret_data['error'] = 1;
            $ret_data['msg'] = '添加失败';

            fdump_api(['设置是否开启可以进行刷卡：'.__LINE__,$device_id, $data,$village_id,$device_info,$data_msg,$add_data,$this->_sql(),$this->getDbError(), $ret_data],'a7/add_add_set_readwg_way_log',1);
            return $ret_data;
        }
    }

    /**
     * Notes:获取设备信息
     * @param int $device_id 设备id
     * @param array $data 需要添加的相关数据
     * @param int $village_id 小区id
     * @param array $device_info 设备信息-选传
     * @return array
     * @author: wanzy
     * @date_time: 2020/9/5 11:25
     */
    public function add_get_dev_info_record($device_id,$data=[],$village_id=0,$device_info=[]) {
        if (empty($device_info)) {
            $device_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        }
        if (!$village_id && $device_info['village_id']) {
            $village_id = $device_info['village_id'];
        }
        // 具体请求区分名称
        $cmd_name = 'get_dev_info';
        $ret_data = [
            'error' => 0,
            'msg' => ''
        ];
        $where_repeat = [
            'device_id' => $device_id,
            'cmd_name' => $cmd_name,
            'status' => 0,
        ];
        $repeat_id = M('Face_a7_request_record')->where($where_repeat)->getField('id');
        if ($repeat_id) {
            // 直接将之前未执行的命令改为已执行
            M('Face_a7_request_record')->where(['id' => $repeat_id])->data(['status' => 1, 'update_time' => time()])->save();
        }
        $device_sn = $device_info['device_sn'];
        $operator_type = isset($data['operator_type']) ? intval($data['operator_type']) : 1;
        $operator_id = isset($data['operator_id']) ? intval($data['operator_id']) : $village_id;
        $version = isset($data['version']) ? intval($data['version']) : $this->version;
        $status = 0;
        $add_time = time();
        $data_msg = [
            'version' => $version,
            'cmd' => $cmd_name
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
            'sort' => 98
        ];
        $add_id = $this->data($add_data)->add();
        if ($add_id) {
            $ret_data['data'] = $add_id;
            return $ret_data;
        } else {
            $ret_data['error'] = 1;
            $ret_data['msg'] = '添加失败';
            fdump_api(['设置是否开启安全帽检测：'.__LINE__,$device_id, $data,$village_id,$device_info,$data_msg,$add_data,$this->_sql(),$this->getDbError(), $ret_data],'a7/add_get_dev_info_record_log',1);
            return $ret_data;
        }
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
        if (empty($bind_info) || !isset($bind_info['property_starttime'])) {
            $bind_info = M('House_village_user_bind')->where(array('pigcms_id' => $pigcms_id))->find();
        }
        if (empty($device_info)) {
            $device_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        }
        if(isset($data['log_id']) && $data['log_id']>0){
            $log_id = $data['log_id'];
        }else{
//            $log_id = M('House_face_sync_log')->add([
//                'pigcms_id'=>$pigcms_id,
//                'face_id'=>0,
//                'device_id'=>$device_id,
//                'device_sn'=>$device_info['device_sn'],
//                'log_time'=>time(),
//                'status'=>0,
//                'device_info'=>json_encode($device_info)
//            ]);
            $device_sn = isset($device_info['device_sn']) && $device_info['device_sn'] ? trim($device_info['device_sn']) : '';
            $syncLogParams = [
                'pigcms_id' => $pigcms_id, 'face_id' => 0, 'device_id' => $device_id, 'device_sn' => $device_sn,
                'log_time' => time(), 'status' => 0, 'msg' => '', 'device_info' => $device_info
            ];
            $log_id = D('Face_door_service')->handleHouseFaceSyncLog($syncLogParams);
        }
        if (!$village_id && $bind_info['village_id']) {
            $village_id = $bind_info['village_id'];
        } elseif (!$village_id && $device_info['village_id']) {
            $village_id = $device_info['village_id'];
        }
        $d_face_user_bind_device = M('Face_user_bind_device');
        $where_face_bind_about = [
            'bind_type' => 0,
            'bind_id' => $pigcms_id,
            'device_type' => 7,
            'device_id' => $device_id,
        ];
        $bind_user = $d_face_user_bind_device->where($where_face_bind_about)->find();
        if ($bind_user) {
            // 如果存在同步过数据 即为编辑
            // 具体请求区分名称
            $cmd_name = 'update_face';
            if ($bind_info['user_number'] && $bind_user['person_id']!=$bind_info['user_number']) {
                $user_number = $bind_info['user_number'];
            } else {
                $user_number = $bind_user['person_id'];
            }
        } else {
            // 未同步过进行添加
            $cmd_name = 'create_face';
        }
        $where_repeat = [
            'device_id' => $device_id,
            'bind_id' => $pigcms_id,
            'cmd_name' => $cmd_name,
            'status' => 0,
        ];
        $repeat_id = M('Face_a7_request_record')->where($where_repeat)->getField('id');
        if ($repeat_id) {
            // 直接将之前未执行的命令改为已执行
            M('Face_a7_request_record')->where(['id' => $repeat_id])->data(['status' => 1, 'update_time' => time()])->save();
        }
        // 把其中非数字部分去除
        $pattern = "/[^0-9]/";

        $device_sn = $device_info['device_sn'];
        $operator_type = isset($data['operator_type']) ? intval($data['operator_type']) : 1;
        $operator_id = isset($data['operator_id']) ? intval($data['operator_id']) : $village_id;
        $version = isset($data['version']) ? intval($data['version']) : $this->version;
        $status = 0;
        $add_time = time();

        // 获取下图片
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        if ($bind_info['uid']>0) {
            $d_house_face_img = M('House_face_img');
            $image_info = $d_house_face_img->where(array('uid'=>$bind_info['uid'],'status'=>['in', [0,3]],'img_url'=>array('neq','')))->order('add_time DESC')->find();
            $img_url = $image_info['img_url'];
            if ($img_url) {
                if (3 == $image_info['status']) {
                    import('@.ORG.face.word_encryption');
                    $word_encryption = new word_encryption();
                    // 加密的图片解密
                    $img_url = $word_encryption->text_decrypt($img_url);
                }
                try {
                    $img_url = thumb_img($img_url, 600, 600, '', true);
                    $img_url = D('Image')->transform_image($img_url);
                    $file_handle = new file_handle();
                    if ($file_handle->check_open_oss()) {
                        $img_url = thumb_img($img_url, 600, 600, '', true);
                    }
                    $img_data = $this->imgtobase64($img_url,[$device_id,$pigcms_id,$village_id,$bind_info]);
                    if (!$img_data) {
                        fdump_api(['A7图片base失败：'.__LINE__,$img_data],'a7/'.$pigcms_id.'-'.$bind_info['uid'].'/image_log',1);
                    }
                } catch( \Exception $e) {
                    $img_data = '';
                }
                $face_id = $image_info['id'];
            }
        }
        if($bind_info['type']==6 ){
            $s_time=$bind_info['property_starttime'];
            $e_time=$bind_info['property_endtime'];
        }else{
            $userTimes =  D('House_village_user_bind')->getUserEndTime($pigcms_id);
            if (isset($userTimes['propertyEndTime'])&&$userTimes['propertyEndTime']>0) {
                $e_time = intval($userTimes['propertyEndTime']);
            } else {
                $e_time = 0;
            }

            if (isset($userTimes['propertyStartTime'])&&$userTimes['propertyStartTime']>0) {
                $s_time = intval($userTimes['propertyStartTime']);
            } else {
                $s_time = time();
            }
            if ($s_time && !$e_time) {
                $e_time = 2082729599;
            } 
        }
        if (in_array($bind_info['type'],[5,6]) && !$e_time) {
            fdump_api(['临时访客和通行证必定有时效 没有时效不做同步：'.__LINE__,$device_id, $pigcms_id,$village_id,$bind_info,$data,$device_sn,$bind_user,$bind_info],'a7/add_create_face_record_err_log',1);
            return false;
        }

        if ($user_number) {
            // 如果存在同一个小区相同编号去重
            $replace_info = M('House_village_user_bind')->where(array('village_id'=>$bind_info['village_id'],'status'=>['not in', [0,3,4]],'user_number' => $user_number,'pigcms_id'=>['neq', $pigcms_id]))->find();
            if ($replace_info) {
                fdump_api(['编号重复了：'.__LINE__,$user_number,$bind_info,$replace_info],'a7/add_create_face_record_log',1);
                $user_number = '';// 编号重新生成
                $bind_info['user_number'] = '';// 编号重新生成
                $bind_info['room_number'] = '';// 编号重新生成
                // 直接将之前未执行的命令改为已执行
                $where_repeat = [
                    'device_id' => $device_id,
                    'bind_id' => $pigcms_id,
                    'cmd_name' => $cmd_name,
                    'status' => 0,
                ];
                M('Face_a7_request_record')->where(['id' => $repeat_id])->data(['status' => 1, 'update_time' => time()])->save();
            }
        }
        if (!$user_number && $bind_info['user_number']) {
            $user_number = $bind_info['user_number'];
        }
//        elseif (!$user_number && $bind_info['bind_number']) {
//            暂时不取自行生成的编号  保持A7的20位编号
//            $user_number = $bind_info['bind_number'];
//        }
        elseif (!$user_number && ($bind_info['type']==4 || $bind_info['type']==6)) {
            // 处理下用户编号问题-梯控相关
            $user_number = '000099';
            $where_max_number = [
                'type' => ['in', [4,6]],
                'village_id' => $village_id,
                'pigcms_id' => ['neq', $bind_info['pigcms_id']],
                'status' => ['neq', 4],
            ];
            $room_number_max_info = M('House_village_user_bind')->where($where_max_number)->field('MAX(room_number+0) as room_max_number')->find();
            if (!$room_number_max_info) {
                $room_max_number = 0;
            } else {
                $room_max_number = intval($room_number_max_info['room_max_number']);
            }
            $room_number = $room_max_number + 1;
            $room_number = str_pad($room_number,4,"0",STR_PAD_LEFT);
            if (strlen($room_number)>4) {
                M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->data([
                    'face_img_status'=>2,'face_img_reason' => '该小区工作人员加入数量已经超过了4位。',
                ])->save();
                if($log_id){
                    M('House_face_sync_log')->where(['log_id'=>$log_id])->save([
                        'status'=>2,
                        'msg'=>L_('该小区工作人员加入数量已经超过了4位。'),
                        'last_time'=>time()
                    ]);
                } else {
                    $device_sn = isset($device_info['device_sn']) && $device_info['device_sn'] ? trim($device_info['device_sn']) : '';
                    $syncLogParams = [
                        'pigcms_id' => $pigcms_id, 'face_id' => 0, 'device_id' => $device_id, 'device_sn' => $device_sn,
                        'log_time' => time(), 'status' => 2, 'msg' => '该小区工作人员加入数量已经超过了4位', 'device_info' => $device_info
                    ];
                    $log_id = D('Face_door_service')->handleHouseFaceSyncLog($syncLogParams);
                }
                fdump_api(['该小区工作人员加入数量已经超过了4位：'.__LINE__,$room_max_number, $where_max_number,$room_number],'a7/add_create_face_record_err_log',1);
                return false;
            }
            M('House_village_user_bind')->where(['pigcms_id' => $bind_info['pigcms_id']])->setField('room_number', $room_number);
            // 2位楼栋2位单元2位层2位房号2位人员编号1位业主物业标识2位楼层2位楼层2位楼层2位楼层
            $user_number .= $room_number . '2000000000';
        } elseif (!$user_number) {
            // 楼栋
            $single_info = M('House_village_single')->where(['id' => $bind_info['single_id']])->field('id,single_name,single_number,village_id,third_single_id')->find();

            if ($single_info['single_number']) {
                $singleNum = $single_info['single_number'];
            } else {
                $singleName = $single_info['single_name'];
                $singleNum = preg_replace($pattern,'',$singleName);
                if ($singleNum) {
                    // 不能生成  需要获取实际的楼栋
                    $singleNum = str_pad($singleNum,2,"0",STR_PAD_LEFT);
                } else {
                    M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->data([
                        'face_img_status'=>2,'face_img_reason' => '楼栋没有数字编号，无法生成房屋信息。',
                    ])->save();
                    if($log_id){
                        M('House_face_sync_log')->where(['log_id'=>$log_id])->save([
                            'status'=>2,
                            'msg'=>L_('楼栋没有数字编号，无法生成房屋信息。'),
                            'last_time'=>time()
                        ]);
                    } else {
                        $device_sn = isset($device_info['device_sn']) && $device_info['device_sn'] ? trim($device_info['device_sn']) : '';
                        $syncLogParams = [
                            'pigcms_id' => $pigcms_id, 'face_id' => 0, 'device_id' => $device_id, 'device_sn' => $device_sn,
                            'log_time' => time(), 'status' => 2, 'msg' => '楼栋没有数字编号，无法生成房屋信息。', 'device_info' => $device_info
                        ];
                        $log_id = D('Face_door_service')->handleHouseFaceSyncLog($syncLogParams);
                    }
                    fdump_api(['楼栋没有数字编号，无法生成房屋信息：'.__LINE__,$bind_info, $single_info,$singleName,$singleNum],'a7/add_create_face_record_err_log',1);
                    return false;
                }
            }
            $singleNum = substr($singleNum, -2,2);
            // 单元
            $floor_info = M('House_village_floor')->where(['floor_id' => $bind_info['floor_id']])->field('floor_id,floor_name,floor_number,single_id,village_id,third_floor_id')->find();

            if ($floor_info['floor_number']) {
                $floorNum = $floor_info['floor_number'];
            } else {
                $floorName = $floor_info['floor_name'];
                // 把其中汉字去除
                $floorNum = preg_replace($pattern,'',$floorName);
                if ($floorNum) {
                    $floorNum = str_pad($floorNum,2,"0",STR_PAD_LEFT);
                } else {
                    M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->data([
                        'face_img_status'=>2,'face_img_reason' => '单元没有数字编号，无法生成房屋信息。',
                    ])->save();
                    if($log_id){
                        M('House_face_sync_log')->where(['log_id'=>$log_id])->save([
                            'status'=>2,
                            'msg'=>L_('单元没有数字编号，无法生成房屋信息。'),
                            'last_time'=>time()
                        ]);
                    } else {
                        $device_sn = isset($device_info['device_sn']) && $device_info['device_sn'] ? trim($device_info['device_sn']) : '';
                        $syncLogParams = [
                            'pigcms_id' => $pigcms_id, 'face_id' => 0, 'device_id' => $device_id, 'device_sn' => $device_sn,
                            'log_time' => time(), 'status' => 2, 'msg' => '单元没有数字编号，无法生成房屋信息。', 'device_info' => $device_info
                        ];
                        $log_id = D('Face_door_service')->handleHouseFaceSyncLog($syncLogParams);
                    }
                    fdump_api(['单元没有数字编号，无法生成房屋信息：'.__LINE__,$bind_info, $single_info,$singleName,$singleNum,$floor_info,$floorName,$floorNum],'a7/add_create_face_record_err_log',1);
                    return false;
                }
            }
            $floorNum = substr($floorNum, -2,2);
            // 层号
            $layer_info = M('House_village_layer')->where(['id' => $bind_info['layer_id']])->field('id,layer_name,layer_number,floor_id,village_id,third_layer_id')->find();

            if ($layer_info['layer_number']) {
                $layerNum = $layer_info['layer_number'];
            } else {
                $layerName = $layer_info['layer_name'];
                // 把其中汉字去除
                $layerNum = preg_replace($pattern,'',$layerName);
                if ($layerNum) {
                    $layerNum = str_pad($layerNum,2,"0",STR_PAD_LEFT);
                } else {
                    M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->data([
                        'face_img_status'=>2,'face_img_reason' => '楼层没有数字编号，无法生成房屋信息。',
                    ])->save();
                    if($log_id){
                        M('House_face_sync_log')->where(['log_id'=>$log_id])->save([
                            'status'=>2,
                            'msg'=>L_('楼层没有数字编号，无法生成房屋信息。'),
                            'last_time'=>time()
                        ]);
                    } else {
                        $device_sn = isset($device_info['device_sn']) && $device_info['device_sn'] ? trim($device_info['device_sn']) : '';
                        $syncLogParams = [
                            'pigcms_id' => $pigcms_id, 'face_id' => 0, 'device_id' => $device_id, 'device_sn' => $device_sn,
                            'log_time' => time(), 'status' => 2, 'msg' => '楼层没有数字编号，无法生成房屋信息。', 'device_info' => $device_info
                        ];
                        $log_id = D('Face_door_service')->handleHouseFaceSyncLog($syncLogParams);
                    }
                    fdump_api(['楼层没有数字编号，无法生成房屋信息：'.__LINE__,$bind_info, $single_info,$singleName,$singleNum,$floor_info,$floorName,$floorNum,$layer_info,$layerName,$layerNum],'a7/add_create_face_record_err_log',1);
                    return false;
                }
            }
            $layerNum = substr($layerNum, -2,2);
            // 房屋编号
            $vacancy_info = M('House_village_user_vacancy')->field('pigcms_id,village_id,floor_id,room,room_number')->where(['pigcms_id' => $bind_info['vacancy_id']])->find();

            if ($vacancy_info['room_number']) {
                $roomNum = $vacancy_info['room_number'];
            } else {
                $roomName = $vacancy_info['room'];
                if (!$roomName && $bind_info['room_addrss']) {
                    $roomName = $bind_info['room_addrss'];
                }
                // 把房屋中汉字去除
                $roomNum = preg_replace($pattern,'',$roomName);
                $roomNum =  strval($roomNum);
                if (!$roomNum) {
                    M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->data([
                        'face_img_status'=>2,'face_img_reason' => '房屋没有数字编号，无法生成房屋信息。',
                    ])->save();
                    if($log_id){
                        M('House_face_sync_log')->where(['log_id'=>$log_id])->save([
                            'status'=>2,
                            'msg'=>L_('房屋没有数字编号，无法生成房屋信息。'),
                            'last_time'=>time()
                        ]);
                    } else {
                        $device_sn = isset($device_info['device_sn']) && $device_info['device_sn'] ? trim($device_info['device_sn']) : '';
                        $syncLogParams = [
                            'pigcms_id' => $pigcms_id, 'face_id' => 0, 'device_id' => $device_id, 'device_sn' => $device_sn,
                            'log_time' => time(), 'status' => 2, 'msg' => '房屋没有数字编号，无法生成房屋信息。', 'device_info' => $device_info
                        ];
                        $log_id = D('Face_door_service')->handleHouseFaceSyncLog($syncLogParams);
                    }
                    fdump_api(['房屋没有数字编号，无法生成房屋信息>>>'.__LINE__, $bind_info, $single_info, $singleNum, $floor_info, $floorNum, $layer_info, $layerNum, $vacancy_info, $roomName,$roomNum],'a7/add_create_face_record_err_log',1);
                    return false;
                } elseif ($roomNum) {
                    $roomNum = str_pad($roomNum,2,"0",STR_PAD_LEFT);
                }
            }
            // 新的房屋编号截取房屋编号后2位
            $roomNum = substr($roomNum,-2);

            // 加入房屋序列号
            if (!$bind_info['room_number'] || intval($bind_info['room_number'])<=0) {
                $where_max_worker_number = [
                    'vacancy_id' => intval($bind_info['vacancy_id']),
                    'village_id' => $village_id,
                    'pigcms_id' => ['neq', $bind_info['pigcms_id']],
                    'status' => ['neq', 4],
                ];
                // 获取同一个小区下工作人员加入序号
                $room_number_max_info = M('House_village_user_bind')->where($where_max_worker_number)->field('MAX(room_number+0) as room_max_number')->find();
                if (!$room_number_max_info) {
                    $room_max_number = 0;
                } else {
                    $room_max_number = intval($room_number_max_info['room_max_number']);
                }
                $room_number = $room_max_number + 1;
                $room_number = str_pad($room_number,2,"0",STR_PAD_LEFT);
                if (strlen($room_number)>2) {
                    M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->data([
                        'face_img_status'=>2,'face_img_reason' => '该房屋人员加入数量已经超过了2位。',
                    ])->save();
                    if($log_id){
                        M('House_face_sync_log')->where(['log_id'=>$log_id])->save([
                            'status'=>2,
                            'msg'=>L_('该房屋人员加入数量已经超过了2位。'),
                            'last_time'=>time()
                        ]);
                    } else {
                        $device_sn = isset($device_info['device_sn']) && $device_info['device_sn'] ? trim($device_info['device_sn']) : '';
                        $syncLogParams = [
                            'pigcms_id' => $pigcms_id, 'face_id' => 0, 'device_id' => $device_id, 'device_sn' => $device_sn,
                            'log_time' => time(), 'status' => 2, 'msg' => '该房屋人员加入数量已经超过了2位。', 'device_info' => $device_info
                        ];
                        $log_id = D('Face_door_service')->handleHouseFaceSyncLog($syncLogParams);
                    }
                    fdump_api(['该房屋人员加入数量已经超过了2位：'.__LINE__,$bind_info, $where_max_worker_number,$room_max_number,$room_max_number],'a7/add_create_face_record_err_log',1);
                    return false;
                }
                M('House_village_user_bind')->where(['pigcms_id' => $bind_info['pigcms_id']])->setField('room_number', $room_number);
            } else {
                $room_number = $bind_info['room_number'];
                if (strlen($room_number)>2) {
                    M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->data([
                        'face_img_status'=>2,'face_img_reason' => '该房屋人员加入数量已经超过了2位。',
                    ])->save();
                    if($log_id){
                        M('House_face_sync_log')->where(['log_id'=>$log_id])->save([
                            'status'=>2,
                            'msg'=>L_('该房屋人员加入数量已经超过了2位。'),
                            'last_time'=>time()
                        ]);
                    } else {
                        $device_sn = isset($device_info['device_sn']) && $device_info['device_sn'] ? trim($device_info['device_sn']) : '';
                        $syncLogParams = [
                            'pigcms_id' => $pigcms_id, 'face_id' => 0, 'device_id' => $device_id, 'device_sn' => $device_sn,
                            'log_time' => time(), 'status' => 2, 'msg' => '该房屋人员加入数量已经超过了2位。', 'device_info' => $device_info
                        ];
                        $log_id = D('Face_door_service')->handleHouseFaceSyncLog($syncLogParams);
                    }
                    fdump_api(['该房屋人员加入数量已经超过了2位：'.__LINE__,$bind_info, $room_number],'a7/add_create_face_record_err_log',1);
                    return false;
                }
            }

            // 查询下该用户是否在同一个单元下有多个楼层房屋
            $where_bind_layer = [
                'h.village_id' => $village_id,
                'h.pigcms_id' => ['neq', $bind_info['pigcms_id']],
                'h.uid' => $bind_info['uid'],
                'h.floor_id' => $bind_info['floor_id']
            ];
            $field_bind_layer = 'l.layer_name';
            $layer_info = M('House_village_layer')
                ->join('as l left join '.C('DB_PREFIX').'house_village_user_bind as h ON l.id = h.layer_id')
                ->field($field_bind_layer)
                ->group('l.layer_name')
                ->limit(4)
                ->where($where_bind_layer)
                ->select();
            if (empty($layer_info)) {
                $room_number .= '1000000000';
            } else {
                $room_number .= '1';
                foreach ($layer_info as $val) {
                    $val = $val['layer_name'];
                    // 把其中汉字去除
                    $singlelayerNum = preg_replace($pattern,'',$val);
                    if ($singlelayerNum) {
                        $singlelayerNum = str_pad($singlelayerNum,2,"0",STR_PAD_LEFT);
                        if (strlen($singlelayerNum)>2) {

                            fdump_api(['获取用户多余楼层数字编号长度超过了2位,默认00：'.__LINE__,$bind_info, $val, $singlelayerNum],'a7/add_create_face_record_err_log',1);
                            $room_number .= '00';
                        }
                        $room_number .= $singlelayerNum;
                    } else {
                        fdump_api(['获取用户多余楼层没有数字编号,默认00：'.__LINE__,$bind_info, $val],'a7/add_create_face_record_err_log',1);
                        $room_number .= '00';
                    }
                }
                if (strlen($room_number)<12) {
                    $n = 12 - strlen($room_number);
                    $room_number = $this->str_suffix($room_number, $n, "0");
                }
            }
            fdump_api(['用户尾部编号,默认00：'.__LINE__,$room_number],'a7/add_create_face_record_log',1);
            if (strlen($room_number)>12) {
                fdump_api(['编号超过了12位：'.__LINE__,$bind_info,$single_info,$singleName,$singleNum,$floor_info,$floorName,$floorNum,$vacancy_info,$roomName,$roomNum,$room_number],'a7/add_create_face_record_err_log',1);
                return false;
            }
            if ($singleNum && $floorNum && $layerNum && $roomNum && $room_number) {
                $user_number = $singleNum . $floorNum . $layerNum . $roomNum . $room_number;
                if (strlen($user_number)>20) {
                    fdump_api(['编号超过了20位：'.__LINE__,$bind_info,$single_info,$singleName,$singleNum,$floor_info,$floorName,$floorNum,$vacancy_info,$roomName,$roomNum,$room_number],'a7/add_create_face_record_err_log',1);
                    return false;
                }
            } else {
                fdump_api(['无法生成房屋信息：'.__LINE__,$bind_info,$single_info,$singleName,$singleNum,$floor_info,$floorName,$floorNum,$vacancy_info,$roomName,$roomNum,$room_number],'a7/add_create_face_record_err_log',1);
                return false;
            }
        }

        $data_msg = [
            'version' => $version,
            'cmd' => $cmd_name, // 创建或者编辑人员指令
            'per_id' => strval($user_number), // 人员id：不超过32位
            'per_name' => trim($bind_info['name']) ? trim($bind_info['name']) : '住户_'.$pigcms_id, // 姓名：不超过10字节utf8编码
            'per_type' => 0, // 0：普通人员。1：白名单人员。2：黑名单人员(可选)
            'usr_type' => 0, // 权限类型，取值0-5，默认0(可选)
        ];
        $has = 0;
        if (isset($img_data) && $img_data) {
            $has = 1;
            $data_msg['img_data'] = $img_data; // base64 人脸图片
        }
        if ($face_id) {
            $data_msg['face_id'] = $face_id; // 人脸ID（不填则自动生成）(可选)
        }
        if (trim($bind_info['ic_card'])) {
            $has = 1;
            $data_msg['idcardNum'] = trim($bind_info['ic_card']); // 韦根号,智能卡号(可选)
        }
        if (trim($bind_info['id_card'])) {
            $has = 1;
            $data_msg['idcardper'] = trim($bind_info['id_card']); // 身份证号：18位(可选)
        }
        if ($s_time && $e_time) {
            $data_msg['s_time'] = intval($s_time);// 启用日期时间戳(可选)
            $data_msg['e_time'] = intval($e_time); // 有效日期时间戳(可选)
        }
        if ($has != 1) {
            $ret_data['error'] = 0;
            $ret_data['msg'] = '没有同步的参数不进行同步';
            M('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->data(['face_img_status'=>1,'face_img_reason' => ''])->save();
            return $ret_data;
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
            'user_number' => strval($user_number),
            'data' => serialize($data_msg),
            'add_time' => $add_time,
            'log_id' => $log_id,
        ];
        $add_id = $this->data($add_data)->add();
        //fdump_api(['add_id'=>$add_id,'add_data'=>$add_data],1,'a7/add_create_face_record_data_log'.date('YmdH'));
        if ($add_id) {
            $ret_data['data'] = $add_id;
            $save_bind = [
                'user_number' => $user_number
            ];
            M('House_village_user_bind')->where(['pigcms_id' => $pigcms_id])->save($save_bind);
            return $ret_data;
        } else {
            fdump_api(['记录失败>：'.__LINE__,$device_id,$data,$pigcms_id,$village_id,$device_info,$data_msg,$add_data,$bind_info,$single_info,$singleName,$singleNum,$floor_info,$floorName,$floorNum,$vacancy_info,$roomName,$roomNum,$room_number],'a7/add_create_face_record_err_log',1);
            $ret_data['error'] = 1;
            $ret_data['msg'] = '添加失败';
            return $ret_data;
        }
    }

    // 后续添加0
    public function str_suffix($str, $n=1, $char="0"){
        for ($x=0;$x<$n;$x++){$str = $str.$char;}
        return $str;
    }

    /**
     * Notes: 设备远程开门-IO设备控制
     * @param int $device_id 设备id
     * @param int $pigcms_id 小区用户绑定id
     * @param array $data 需要添加的相关数据
     * @param int $village_id 小区id
     * @param array $device_info 设备信息-选传
     * @return array
     * @author: wanzy
     * @date_time: 2020/9/5 10:44
     */
    public function add_open_door_record($device_id,$pigcms_id=0,$data=[],$village_id=0,$device_info=[]) {
        if (empty($device_info)) {
            $device_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        }
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
        $repeat = $this->where($where_repeat)->find();
        if ($repeat) {
            $ret_data['error'] = 1;
            $ret_data['msg'] = '【开门】命令已经记录等待设备执行';
            return $ret_data;
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
            'ctrl_type' => "on",
        ];
        // 整合需要添加的数据
        $add_data = [
            'village_id' => $village_id,
            'device_sn' => $device_sn,
            'device_id' => $device_id,
            'operator_type' => $operator_type,
            'operator_id' => $operator_id,
            'bind_id' => $pigcms_id?$pigcms_id:0,
            'cmd_name' => $cmd_name,
            'version' => $version,
            'status' => $status,
            'data' => serialize($data_msg),
            'add_time' => $add_time,
            'sort' => 101
        ];
        $add_id = $this->data($add_data)->add();
        if ($add_id) {
            $ret_data['data'] = $add_id;
            return $ret_data;
        } else {
            $ret_data['error'] = 1;
            $ret_data['msg'] = '添加失败';
            fdump_api(['记录失败：'.__LINE__,$device_id,$data,$village_id,$device_info,$data_msg,$add_data,$this->_sql(),$this->getDbError(),$ret_data],'a7/add_open_door_record_log',1);
            return $ret_data;
        }
    }

    /**
     * Notes: 删除注册人员
     * @param int $device_id 设备id
     * @param int $pigcms_id 用户小区绑定身份id
     * @param int $village_id 小区id
     * @param array $data 涉及到的内容信息数组
     * @param array $device_info 设备信息
     * @return array
     * @author: wanzy
     * @date_time: 2020/9/5 14:45
     */
    public function add_delete_face_record($device_id,$pigcms_id,$village_id=0,$data=[],$device_info=[]) {
        if (empty($device_info)) {
            $device_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        }
        if (!$village_id && $device_info['village_id']) {
            $village_id = $device_info['village_id'];
        }
        $ret_data = [
            'error' => 0,
            'msg' => ''
        ];
        $d_face_user_bind_device = M('Face_user_bind_device');
        $where_face_bind_about = [
            'bind_type' => 0,
            'bind_id' => $pigcms_id,
            'device_type' => 7,
            'device_id' => $device_id,
        ];
        $bind_user = $d_face_user_bind_device->where($where_face_bind_about)->find();
        if (empty($bind_user)) {
            $ret_data['error'] = 0;
            $ret_data['msg'] = '已经删除过了';
            // 以防万一还在同步添加或者更新的一并改为已执行
            $where_repeat = [
                'device_id' => $device_id,
                'bind_id' => $pigcms_id,
                'cmd_name' => ['in', ['create_face', 'update_face']],
                'status' => 0,
            ];
            // 直接将之前未执行的命令改为已执行
            $this->where($where_repeat)->data(['status' => 1, 'update_time' => time()])->save();
            return $ret_data;
        }
        // 具体请求区分名称
        $cmd_name = 'delete_face';
        $where_repeat = [
            'device_id' => $device_id,
            'bind_id' => strval($pigcms_id),
            'cmd_name' => $cmd_name,
            'status' => 0,
        ];
        $repeat = $this->where($where_repeat)->find();
        if ($repeat) {
            $ret_data['error'] = 1;
            $ret_data['msg'] = '【删除注册人员】命令已经记录等待设备执行';
            return $ret_data;
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
            'type' => 0,
            'per_id' => strval($bind_user['person_id']),
        ];
        // 整合需要添加的数据
        $add_data = [
            'village_id' => $village_id,
            'device_sn' => $device_sn,
            'device_id' => $device_id,
            'bind_id' => $pigcms_id,
            'user_number' => $data_msg['per_id'],
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
            // 以防万一还在同步添加或者更新的一并改为已执行
            $where_repeat = [
                'device_id' => $device_id,
                'bind_id' => $pigcms_id,
                'cmd_name' => ['in', ['create_face', 'update_face']],
                'status' => 0,
            ];
            // 直接将之前未执行的命令改为已执行
            $this->where($where_repeat)->data(['status' => 1, 'update_time' => time()])->save();
            return $ret_data;
        } else {
            $ret_data['error'] = 1;
            $ret_data['msg'] = '添加失败';
            fdump_api(['添加失败：'.__LINE__,$device_id,$data,$pigcms_id,$village_id,$device_info,$data_msg,$add_data,$this->_sql(),$this->getDbError(),$ret_data],'a7/add_delete_face_record_log',1);

            return $ret_data;
        }
    }

    /**
     * Notes: 获取设备是否连接外网
     * @param int $device_id 设备id
     * @param array $data 需要添加的相关数据
     * @param int $village_id 小区id
     * @param array $device_info 设备信息-选传
     * @return array
     * @author: wanzy
     * @date_time: 2020/9/5 11:16
     */
    public function add_get_dev_network_record($device_id,$data=[],$village_id=0,$device_info=[]) {
        if (empty($device_info)) {
            $device_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        }
        // 具体请求区分名称
        $cmd_name = 'get_dev_network';
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
            $ret_data['error'] = 1;
            $ret_data['msg'] = '【获取设备是否连接外网】命令已经记录等待设备执行';
            return $ret_data;
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
        ];
        $add_id = $this->data($add_data)->add();
        if ($add_id) {
            $ret_data['data'] = $add_id;
            return $ret_data;
        } else {
            $ret_data['error'] = 1;
            $ret_data['msg'] = '添加失败';
            fdump_api(['添加失败：'.__LINE__,$device_id,$data,$village_id,$device_info,$data_msg,$add_data,$this->_sql(),$this->getDbError(),$ret_data],'a7/add_delete_face_record_log',1);
            return $ret_data;
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
    public function imgtobase64($img='', $param=[])
    {
        fdump_api(['base64图片转译前'.__LINE__,$img,$param],'a7/imgtobase64',1);
        $img_content = file_get_contents($img);
        $up_dir = './runtime/a7/';
        if (!is_dir($up_dir)) {
            mkdir($up_dir, 0777, true);
        }
        file_put_contents($up_dir.md5(uniqid()).'_tmp.jpg', $img_content);
        $base64img = base64_encode($img_content);
        if ($base64img) {
            return $base64img;
        }
        $img_base64 = '';
        if (strpos($img,'?x-oss-process=image') !== false) {
            // 有压缩直接base64
            return base64_encode(file_get_contents($img));
        }
        if (strpos($img,'?x-image-process=') !== false) {
            // 有压缩直接base64
            return base64_encode(file_get_contents($img));
        }
        if (strpos($img,'?imageView2') !== false) {
            // 有压缩直接base64
            return base64_encode(file_get_contents($img));
        }
        //检测图片是否已经包含压缩，若压缩则去除
        $tmp_img_arr = explode('?x-image-process=', $img);
        if(count($tmp_img_arr) >= 2){
            $img_file = $tmp_img_arr[0];
        } else {
            $img_file = $img;
        }
        $tmp_img_arr = explode('?x-oss-process=', $img_file);
        if(count($tmp_img_arr) >= 2){
            $img_file = $tmp_img_arr[0];
        }
        $tmp_img_arr = explode('?imageView2', $img_file);
        if(count($tmp_img_arr) >= 2){
            $img_file = $tmp_img_arr[0];
        }
        $file_handle = new file_handle();
        if($file_handle->check_open_oss()) {
            $file_handle->download($img_file);
            $img_file = $file_handle->get_path($img_file);
            $img_file = $_SERVER['DOCUMENT_ROOT'] . $img_file;
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
            fdump_api(['A7图片base64处理：'.__LINE__,$img,$img_file],'a7/user/image_log',1);
            $app_img_file = file_get_contents($img_file);
            $file_content = base64_encode($app_img_file); // base64编码
            $img_base64 = $file_content;//合成图片的base64编码
        } else {
            fdump_api(['A7图片base64处理：'.__LINE__,$img,$img_file],'a7/user/image_err_log',1);
        }

        return $img_base64; //返回图片的base64
//        $imageInfo = getimagesize($img);
//        $base64 = chunk_split(base64_encode(file_get_contents($img)));
//        return 'data:' . $imageInfo['mime'] . ';base64,' . chunk_split(base64_encode(file_get_contents($img)));
    }

    /**
     * Notes:获取Http推送配置
     * @param $device_id
     * @param array $data
     * @param int $village_id
     * @param array $device_info
     * @return array
     * @author: wanzy
     * @date_time: 2020/9/9 15:55
     */
    public function add_get_http_cfg($device_id,$data=[],$village_id=0,$device_info=[]) {
        if (empty($device_info)) {
            $device_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        }
        // 具体请求区分名称
        $cmd_name = 'get_http_cfg';
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
            $ret_data['error'] = 1;
            $ret_data['msg'] = '【获取Http推送配置】命令已经记录等待设备执行';
            return $ret_data;
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
        ];
        $add_id = $this->data($add_data)->add();
        if ($add_id) {
            $ret_data['data'] = $add_id;
            return $ret_data;
        } else {
            $ret_data['error'] = 1;
            $ret_data['msg'] = '添加失败';
            fdump_api(['添加失败：'.__LINE__,$device_id,$data,$village_id,$device_info,$data_msg,$add_data,$this->_sql(),$this->getDbError(),$ret_data],'a7/add_get_http_cfg_log',1);
            return $ret_data;
        }
    }

    /**
     * Notes: 串口透传协议 呼叫电梯
     * @param $device_id
     * @param array $data
     * @param int $village_id
     * @param array $device_info
     * @return array|bool
     * @author: wanzy
     * @date_time: 2020/11/12 15:17
     */
    public function add_send_rs485($device_id,$data=[],$village_id=0,$device_info=[]) {
        if (empty($device_info)) {
            $device_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        }
        if ($device_info['device_model']!=1) {
            fdump_api(['改设备不是电梯控制类型：'.__LINE__,$device_id,$data,$village_id,$device_info],'a7/add_send_rs485_log',1);
            return false;
        }
        // 具体请求区分名称
        $cmd_name = 'send_rs485';
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
            $ret_data['error'] = 1;
            $ret_data['msg'] = '【呼叫电梯】命令已经记录等待设备执行';
            return $ret_data;
        }
        if (!$village_id) {
            $device_info['village_id'] = $village_id;
        }

        $device_sn = $device_info['device_sn'];
        $operator_type = isset($data['operator_type']) ? intval($data['operator_type']) : 1;
        $operator_id = isset($data['operator_id']) ? intval($data['operator_id']) : $village_id;
        $version = isset($data['version']) ? intval($data['version']) : $this->version;
        $status = 0;
        $add_time = time();
        if (!$device_info['floor_id'] && !$device_info['layer_id']) {
            fdump_api(['设备既没有单元也没有楼层：'.__LINE__,$device_id,$data,$village_id,$device_info],'a7/add_send_rs485_log',1);
            return false;
        }
        // 把其中非数字部分去除
        $pattern = "/[^0-9]/";
        // 层号
        $layer_info = M('House_village_layer')->where(['id' => $device_info['layer_id']])->field('id,layer_name,floor_id,single_id,village_id,third_layer_id')->find();
        $layerName = $layer_info['layer_name'];
        // 把其中汉字去除
        $layerNum = preg_replace($pattern,'',$layerName);
        if ($layerNum) {
            $layerNum = str_pad($layerNum,2,"0",STR_PAD_LEFT);
        } else {
            fdump_api(['楼层没有数字编号，无法生成房屋信息：'.__LINE__,$device_info,$layer_info,$layerName,$layerNum],'a7/add_send_rs485_log',1);
            return false;
        }
        $layerNum = substr($layerNum, -2,2);
        // 单元
        if ($device_info['floor_id']) {
            $floor_id = $device_info['floor_id'];
        } elseif ($layer_info['floor_id']) {
            $floor_id = $layer_info['floor_id'];
        }
        $floor_info = M('House_village_floor')->where(['floor_id' => $floor_id])->field('floor_id,floor_name,single_id,village_id,third_floor_id')->find();
        $floorName = $floor_info['floor_name'];
        // 把其中汉字去除
        $floorNum = preg_replace($pattern,'',$floorName);
        if ($floorNum) {
            $floorNum = str_pad($floorNum,2,"0",STR_PAD_LEFT);
        } else {
            fdump_api(['单元没有数字编号，无法生成房屋信息：'.__LINE__,$device_info,$floor_info,$floorName,$floorNum],'a7/add_send_rs485_log',1);
            return false;
        }
        $floorNum = substr($floorNum, -2,2);

        // 楼栋
        if ($floor_info['single_id']) {
            $single_id = $floor_info['single_id'];
        } elseif ($layer_info['single_id']) {
            $single_id = $layer_info['single_id'];
        }
        $single_info = M('House_village_single')->where(['id' => $single_id])->field('id,single_name,village_id,third_single_id')->find();
        $singleName = $single_info['single_name'];
        // 把其中汉字去除
        $singleNum = preg_replace($pattern,'',$singleName);
        if ($singleNum) {
            // 不能生成  需要获取实际的楼栋
            $singleNum = str_pad($singleNum,2,"0",STR_PAD_LEFT);
        } else {
            fdump_api(['楼栋没有数字编号，无法生成房屋信息：'.__LINE__,$device_info,$single_info,$singleName,$singleNum],'a7/add_send_rs485_log',1);
            return false;
        }
        $singleNum = substr($singleNum, -2,2);

        $user_number = $singleNum . $floorNum . $layerNum . $layerNum . '011';
        // 获取 按照规则生成 串
        $user_number_code = $this->getElevatorControlCode($user_number);
        $user_number_code_base64 = base64_encode($user_number_code);
        fdump_api(['参数：'.__LINE__,$user_number,$user_number_code,$user_number_code_base64],'a7/add_send_rs485_log',1);
        $body = [
            'source' => 0,
            'data' => $user_number_code_base64,
        ];
        $data_msg = [
            'version' => $version,
            'cmd' => $cmd_name,
            'body' => $body,
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
            'sort' => 101
        ];
        $add_id = $this->data($add_data)->add();
        if ($add_id) {
            $ret_data['data'] = $add_id;
            fdump_api(['呼叫记录：'.__LINE__,$add_data,$data_msg,$add_id],'a7/add_send_rs485_log',1);
            return $ret_data;
        } else {
            $ret_data['error'] = 1;
            $ret_data['msg'] = '添加失败';
            fdump_api(['添加失败：'.__LINE__,$device_id,$data,$village_id,$device_info,$data_msg,$add_data,$this->_sql(),$this->getDbError(),$ret_data],'a7/add_send_rs485_log',1);
            return $ret_data;
        }
    }
    private function getElevatorControlCode($peopleId){
        $str16 = 'FF 99 E4 07 0B 15 0D 34 29 01 5D';        // FF固定协议头、99固定命令号、7位字节的时间固定不处理
        $peopleIdArr = str_split($peopleId);        //将人员ID转换为数组
        $peopleId16Arr = [];        // 16位的人员ID数组
        foreach($peopleIdArr as $value){
            $peopleId16Arr[] = bin2hex($value);
        }
        $str16.= ' ' . implode(' ', $peopleId16Arr);

        $str16.= ' 00 00 00 00 00 00 00 00 00';        //补足人员ID的字符
        $str16.= ' 31 00 00 00 00 00 00 00 00 00 00 00 00 00 00 00';        //人员名称，固定不动
        $str16.= ' 01';        //白名单的标识，固定为 1

        //开始换算校验码
        $str16_arr = explode(' ', $str16);
        unset($str16_arr[0]);
        unset($str16_arr[1]);
        $str16_arr = array_values($str16_arr);
        $xor = '';
        for($i=1;$i < count($str16_arr);$i++){
            if($i == 1){
                $xor = hexdec($str16_arr[0]) ^ hexdec($str16_arr[$i]);
            }else if(isset($str16_arr[$i])){
                $xor = $xor ^ hexdec($str16_arr[$i]);
            }
        }
        $str16.= ' ' . strtoupper(base_convert($xor, 10, 16));

        return $str16;
    }
}