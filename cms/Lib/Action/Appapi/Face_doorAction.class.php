<?php

/*
 * 关于我们
 *
 */

class Face_doorAction extends BaseAction
{
    // 设备初始登录
    public function face_login()
    {
        $sign_type = $_POST['sign_type'];
        $device_sn = $_POST['device_sn'];
        $sign_code = $_POST['sign_code'];
        fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/a1/face_login_log',true);

        $d_house_face_device = M('House_face_device');
        $d_house_village_floor = M('House_village_floor');
        $d_house_village = M('House_village');

        if ($device_sn && $sign_code>0) {
            $house_face_device_info = $d_house_face_device->where(array('device_sn' => $device_sn, 'village_id' => $sign_code))->find();
        } else {
            $house_face_device_info = array();
            fdump($_POST,'api/log/'.date('Ymd').'/a1/face_login_log',true);
        }
        $data = array();
        $token = '';
        if ($house_face_device_info) {
            $house_village_info = $d_house_village->where(array('village_id' => $house_face_device_info['village_id']))->find();
            $data['village_name'] = $house_village_info['village_name'];
            $data['village_address'] = $house_village_info['village_address'];
            if ($house_face_device_info['floor_id']==-1) {
                $data['floor_name']	=	C('config.house_name') ? C('config.house_name') : L_('小区');
                $data['floor_layer']	=	L_('大门');
            } else {
                $house_village_floor_info = $d_house_village_floor->where(array('floor_id' => $house_face_device_info['floor_id']))->find();
                $data['floor_layer'] = $house_village_floor_info['floor_layer'] ? $house_village_floor_info['floor_layer'] : '';
                $data['floor_name'] = $house_village_floor_info['floor_name'] ? $house_village_floor_info['floor_name'] : '';
            }
            $token = ticket::create($house_face_device_info['device_id'],$device_sn, true);
            if ($token['ticket']) {
                $token = $token['ticket'];
            }
            $save['login_time'] = time();
            $d_house_face_device->where(array('device_sn' => $device_sn, 'village_id' => $sign_code))->data($save)->save();
        }else{
			$arr = [
				'success' => false,
				'code' => 1,
				'message' => '设备未注册',
			];
			exit(json_encode($arr));
		}

        $arr = array();
        if ($token) {
			//初始化 MQ队列
			import('@.ORG.rabbit_mq');
			$rabbit_mq = new rabbit_mq();
			//$rabbit_mq->send_message($device_sn);
			
            $rabbitmq = array(
                'host' => $rabbit_mq->host,
                'port' => $rabbit_mq->port,
                'login' => $rabbit_mq->user,
                'password' => $rabbit_mq->pwd,
                'vhost' => $rabbit_mq->vhost,
                'queue' => $device_sn,
                'exchange' => array(
                    'name' => 'rabbit-mq-exchange',
                    'type'=> 'topic',
                    'passive'=> false,
                    'durable'=> true,
                    'auto_delete'=> false,
                    'arguments'=> null
                )
            );
            $arr['success'] = true;
            $arr['code'] = 0;
            $arr['message'] = '登录成功';
            $arr['data'] = $data;
            $arr['device_sn'] = $device_sn;
            $arr['token'] = $token;
            $arr['score'] = $house_face_device_info['device_score'];
            $arr['alive'] = $house_face_device_info['device_alive']>0 ? true:false;
            $arr['rabbitmq'] = $rabbitmq;
        } else {
            $arr['success'] = false;
            $arr['code'] = 1;
            $arr['message'] = '登录失败';
        }
        fdump($arr,'api/log/'.date('Ymd').'/a1/face_login_log',true);

        exit(json_encode($arr));
    }

    private function add_person() {
        $device_id = 1;

        $d_house_face_device = M('House_face_device');

        $house_face_device_info = $d_house_face_device->where(array('device_id' => $device_id))->find();

        import('@.ORG.rabbit_mq');
        $rabbit_mq = new rabbit_mq();

        $site = $this->config['site_url'];
        $params = array('limit'=> 100, 'size'=>100);
        $params = (object)$params;
        $data = array(
            'method' => 'sync-person',
            'person_list' => [376],
            'path' => $site . '/person-list',
            'notify' => $site . '/person-notify',
            'params' =>$params
        );
        dump($data);
        $rabbit_mq->send_message($house_face_device_info['device_sn'], $data);
    }

    public function person_list() {
        $device_sn = $_GET['device_sn'];
        $token = $_GET['token'];
        $info = ticket::get($token , $device_sn, true);
        fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/a1/face_door_notify_log',true);
        fdump($_GET,'api/log/'.date('Ymd').'/a1/face_door_notify_log',true);
        fdump($_POST,'api/log/'.date('Ymd').'/a1/face_door_notify_log',true);
        fdump($info,'api/log/'.date('Ymd').'/a1/face_door_notify_log',true);
        $device_id = $info['uid'];
        $d_house_face_device = M('House_face_device');
        $d_house_village = M('House_village');
        $house_face_device_info = $d_house_face_device->where(array('device_id' => $device_id))->find();
        $house_village_info = $d_house_village->where(array('village_id' => $house_face_device_info['village_id']))->find();
        $village_name = $house_village_info['village_name'];

        $person_list = $_POST['person_list'];
        $person_msg = array();
        if ($person_list) {
            foreach ($person_list as $item) {
                if ($item>0) {
                    $info = $this->get_person_info($item);
                    if ($info) {
                        $info['group_id'] = $house_face_device_info['village_id'];
                        $info['group_name'] = $village_name;
                        $info['group_type'] = $house_face_device_info['device_type'];
                        $person_msg[] = $info;
                    }
                }
            }
        }
        $arr = array();
        $arr['success'] = true;
        $arr['code'] = 0;
        $arr['message'] = '获取成功';
        $arr['person_list'] =$person_msg;
        fdump($arr,'api/log/'.date('Ymd').'/a1/face_door_notify_log',true);
        exit(json_encode($arr));
    }

    public function get_person_info($bind_id) {
        $d_house_village_user_bind = D('House_village_user_bind');
        $d_user = D('User');
        $d_house_face_img = D('House_face_img');
        $house_village_bind = $d_house_village_user_bind->get_one_by_bindId($bind_id);
        $uid = $house_village_bind['uid'];
        if ($uid) {
            $user_info = $d_user->get_user($uid);
            $img_info = $d_house_face_img->where(array('uid' => $uid, 'status' => ['in', [0,3]], 'img_url' => array('neq', '')))->limit(3)->field('id as face_id, img_url,status')->select();
        }
        if ($img_info) {
            import('@.ORG.face.word_encryption');
            $word_encryption = new word_encryption();
            foreach ($img_info as $key => &$val) {
                if (3==$val['status']) {
                    // 加密的图片解密
                    $val['img_url'] = $word_encryption->text_decrypt($val['img_url']);
                }
                $val['img_url'] = thumb_img($val['img_url'], 500,1000);
                // 去除status 字段
                unset($img_info[$key]['status']);
            }
        } else {
            $img_info = array();
        }
        $person_msg = array();
        $person_msg['person_id'] = $bind_id;
        $person_msg['person_name'] = $house_village_bind['name'] ? $house_village_bind['name']:$user_info['nickname'];
        $person_msg['person_type'] = 1; // 1 白名单， 2 黑名单， 3 访客， 4 VIP ， 5 陌生人
        $person_msg['face_list'] = $img_info; // 人脸信息 1-3 张
        $person_msg['id_card'] = $house_village_bind['id_card']; // 身份证号
        $person_msg['ic_card'] = $house_village_bind['ic_card']; // IC 卡号
        $person_msg['sex'] = $user_info['sex']==1?'男':($user_info['sex']==2?'女':''); // 性别  男，女
        $person_msg['birthday'] = $user_info['birthday']!='0000-00-00'?$user_info['birthday']:''; // 出生日期
        $person_msg['valid_time'] = (object)array(
            'start_time' => time(),
            'end_time' => '',
            'count' => 0
        ); // 通行时段限制  举例 {start_time: ‘2019-01-01 01:11:11’, end_time: ‘’,count:10 }  次数： 0 无限次 或 指定次数
        return $person_msg;
    }

    public function person_notify() {
        $device_sn = $_GET['device_sn'];
        $token = $_GET['token'];
        $info = ticket::get($token , $device_sn, true);
        $device_id = $info['uid'];
        $d_house_face_device = M('House_face_device');
        $d_house_village_user_bind = M('House_village_user_bind');
        $d_house_face_log = M('House_face_log');
        $house_face_device_info = $d_house_face_device->where(array('device_id' => $device_id))->find();
        fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/a1/face_person_notify_log',true);
        fdump($_GET,'api/log/'.date('Ymd').'/a1/face_person_notify_log',true);
        fdump($_POST,'api/log/'.date('Ymd').'/a1/face_person_notify_log',true);

        $successful = $_POST['successful'];
        $failed = $_POST['failed'];
        $method = $_POST['method'];
        if ($method=='sync-person') {
            $log_type = 1;
        } else {
            $log_type = 2;
        }
        if ($successful) {
            foreach ($successful as $item) {
                if ($item>0) {
                    $info = $d_house_face_log->where(array('uid' => $item, 'device_sn' => $device_sn, 'status'=>0))->find();
                    $log = array(
                        'status' => 1,
                        'msg' => '操作成功',
                        'last_time' => time(),
                        'log_type' => $log_type,
                    );
                    if ($info) {
                        $d_house_face_log->where(array('uid' => $item, 'device_sn' => $device_sn))->data($log)->save();
                    } else {
                        $log['uid'] = $item;
                        $log['device_sn'] = $device_sn;
                        $log['log_type'] = $log_type;
                        $d_house_face_log->data($log)->add();
                    }
                    $save = array(
                        'face_img_status' => 1,
                        'face_img_reason' => '',
                    );
                    $d_house_village_user_bind->where(array('pigcms_id' => $item))->data($save)->save();
                }
            }
        }
        if ($failed) {
            foreach ($failed as $items) {
                if ($items['person_id']>0) {
                    $info = $d_house_face_log->where(array('uid' => $items['person_id'], 'device_sn' => $device_sn, 'status'=>0))->find();
                    $log = array(
                        'status' => 2,
                        'msg' => $items['reason'],
                        'last_time' => time(),
                        'log_type' => $log_type,
                    );
                    if ($info) {
                        $d_house_face_log->where(array('uid' => $items['person_id'], 'device_sn' => $device_sn))->data($log)->save();
                    } else {
                        $log['uid'] = $items['person_id'];
                        $log['device_sn'] = $device_sn;
                        $log['log_type'] = $log_type;
                        $d_house_face_log->data($log)->add();
                    }
                    $save = array(
                        'face_img_status' => 2,
                        'face_img_reason' => $items['reason'],
                    );
                    $d_house_village_user_bind->where(array('pigcms_id' => $items['person_id']))->data($save)->save();
                }
            }
        }
        $arr = array();
        $arr['success'] = true;
        $arr['code'] = 0;
        $arr['message'] = '获取成功';
        $arr['data'] = (object)array();
        exit(json_encode($arr));
    }

    /*人脸比对识别信息*/
    public function attendance_record() {
        $device_sn = $_GET['device_sn'];
        $token = $_GET['token'];
        $info = ticket::get($token , $device_sn, true);
        $device_id = $info['uid'];
        $d_house_face_device = M('House_face_device');
        $d_house_user_log = M('House_user_log');
        $d_house_village_floor = M('House_village_floor');
        $d_house_village = M('House_village');
        $d_house_village_user_bind = D('House_village_user_bind');
        $d_house_village_single = D('House_village_single');

        $house_face_device_info = $d_house_face_device->where(array('device_id' => $device_id))->find();
        $house_village_floor_info = $d_house_village_floor->where(array('floor_id' => $house_face_device_info['floor_id']))->find();
        $house_village_single_info = $d_house_village_single->where(['id' => $house_village_floor_info['single_id']])->field('id as single_id,single_name')->find();
        $house_village_info = $d_house_village->where(array('village_id' => $house_face_device_info['village_id']))->find();
        $village_name = $house_village_info['village_name'];
//        $village_address = $house_village_info['village_address'];

        if ( $house_face_device_info['public_area_id'] >0 ) {
            $public_area_name = M('House_village_public_area')->where(['public_area_id' => $house_face_device_info['public_area_id']])->getField('public_area_name');
            $floor_name = '';
            $floor_layer = $public_area_name;
        } elseif ( $house_face_device_info['floor_id'] == -1) {
            $floor_layer	= C('config.house_name');
            $floor_name = '大门';
        }else{
            $floor_layer = $house_village_single_info['single_name'] ? $house_village_single_info['single_name'] : $house_village_floor_info['floor_layer'];
            $floor_name = $house_village_floor_info['floor_name'];
        }

        $info = $_POST;
        $face_img = $info['face_img'];
        $capture_img = $info['capture_img'];
        if ($info['card_data']) {
            $card_data_picture = $info['card_data']['picture'];
        } else {
            $card_data_picture = '';
        }
        $log = array();
        $house_village_bind = $d_house_village_user_bind->get_one_by_bindId($info['person_id']);
        $uid = $house_village_bind['uid'];
        $log['uid'] = $uid;
        $log['log_bind_id'] = $info['person_id'];
        $log['log_name'] = $village_name . ' ' . $floor_layer . ' ' . $floor_name;
        $log['log_from'] = 1; // 人脸识别门禁
        $log['log_business_id'] = $house_face_device_info['village_id']; // 社区id
        $log['log_more_id'] = $house_face_device_info['floor_id']; // 单元id
        $log['device_id'] = $device_id; // 设备id
        if (isset($info['capture_time']) && $info['capture_time']) {
            $capture_time = strtotime($info['capture_time']);
        }
        $log['log_time'] = isset($capture_time) && $capture_time ? $capture_time : time(); // 记录时间
        $detail=$info;
        if ($capture_img) {
            $detail['face_img'] = $detail['face_img_url'] = $this->base64_to_img($capture_img,$device_id);
        }
        if ($face_img) {
            $detail['capture_img'] = $detail['capture_img_url'] = $this->base64_to_img($face_img,$device_id);
        }
        if ($card_data_picture) {
            $detail['card_data']['picture'] = $detail['card_data_picture_url'] = $this->base64_to_img($card_data_picture,$device_id);
        }
        // 0 比对通过，1 比对失败， 2 无模版， 3 无效卡 4 无现场照，5 非活体， 6 证件过期 7 超过时间限制， 8 超过此数限制 100 未知错误
        if (isset($info['capture_status'])) {
            $log['log_status'] = $info['capture_status'];
        }
        if (isset($detail['face_img']) && trim($detail['face_img'])) {
            $log['cut_image'] = trim($detail['face_img']);
        }
        $log['log_detail'] = serialize($detail);
        fdump_api([$_POST, $detail, $log],'a1/face_attendance_record_log',1);
        $whereReplace = [
            'log_from'    => 1,
            'log_time'    => $log['log_time'],
            'log_bind_id' => $info['person_id'],
            'log_status'  => $log['log_status'],
        ];
        $log['add_time']=time();
        if ($d_house_user_log->where($whereReplace)->find()) {
            $arr = array();
            $arr['success'] = true;
            $arr['code']    = 0;
            $arr['message'] = '获取成功';
            exit(json_encode($arr));
        }
        $d_house_user_log->data($log)->add();
        $d_house_face_device->where(array('device_id' => $device_id))->setField('last_open_time', time());
        // 同步记录到消息列表

        $d_house_camera_tip = M('House_camera_tip');
        $city_id = $house_village_info['city_id'];
        $tip = array(
            'bind_id' =>  $info['person_id'],
            'uid' => $uid,
            'type' => 1,
            'village_id' => $house_face_device_info['village_id'],
            'camera_sn' => $house_face_device_info['device_sn'],
            'city_id' => $city_id,
            'camera_time' => time(),
            'camera_img' => $detail['capture_img'],
            'tip_time' => time()
        );
        fdump_api($tip,'a1/face_attendance_record_log',1);
        $d_house_camera_tip->data($tip)->add();

        $arr = array();
        $arr['success'] = true;
        $arr['code']    = 0;
        $arr['message'] = '获取成功';
        exit(json_encode($arr));
    }

    private function base64_to_img($base_img, $path='face_log') {
//        $img_mer_id = sprintf("%09d", $id);
//        $rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
        $rand_num = date('Ymd');// 换成日期存储
        $up_dir = "./upload/{$path}/".$rand_num.'/';
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
                return $img_path;
            }else{
                return false;
            }
        } else {
            return false;
        }
    }

    /*设备当前状态上报*/
    public function device_notify() {
        $device_sn = $_GET['device_sn'];
        $token = $_GET['token'];
        $info = ticket::get($token , $device_sn, true);
        $device_id = $info['uid'];
        $d_house_face_device = M('House_face_device');
        $house_face_device_info = $d_house_face_device->where(array('device_id' => $device_id))->find();
        if ($house_face_device_info) {
            if (isset($_POST['device_status'])) {
                $save['device_status'] = $_POST['device_status'];
            }
            if (isset($_POST['sdk_version'])) {
                $save['sdk_version'] = $_POST['sdk_version'];
            }
            if (isset($_POST['cpu_usage'])) {
                $save['cpu_usage'] = $_POST['cpu_usage'];
            }
            if (isset($_POST['memory_size'])) {
                $save['memory_size'] = $_POST['memory_size'];
            }
            if (isset($_POST['memory_usage'])) {
                $save['memory_usage'] = $_POST['memory_usage'];
            }
            if (isset($_POST['disk_size'])) {
                $save['disk_size'] = $_POST['disk_size'];
            }
            if (isset($_POST['disk_usage'])) {
                $save['disk_usage'] = $_POST['disk_usage'];
            }
            if ($save) {
                $save['notify_time'] = time();
                $d_house_face_device->where(array('device_id' => $device_id))->data($save)->save();
            }
        }
        $arr = array();
        $arr['success'] = true;
        $arr['code'] = 0;
        $arr['message'] = '登录成功';
        $arr['data'] = array();
        exit(json_encode($arr));
    }

    public function device_restart() {
        fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/a1/face_device_restart_log',true);
        fdump($_GET,'api/log/'.date('Ymd').'/a1/face_device_restart_log',true);
        fdump($_POST,'api/log/'.date('Ymd').'/fa1/ace_device_restart_log',true);
        $device_sn = $_GET['device_sn'];
        $token = $_GET['token'];
        $info = ticket::get($token , $device_sn, true);
        $device_id = $info['uid'];
        $d_house_face_device = M('House_face_device');
        $house_face_device_info = $d_house_face_device->where(array('device_id' => $device_id))->find();
        if ($house_face_device_info) {
            $save['login_time'] = 0;
            $d_house_face_device->where(array('device_id' => $device_id))->data($save)->save();
        }
    }


    /**广告-仅在设备版本 2.3.10 以后版本支持*/
    public function attendance_advert() {
        $device_sn = $_GET['device_sn'];
        $token = $_GET['token'];
        $info = ticket::get($token , $device_sn, true);
        fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/face_door_attendance_advert_log',true);
        fdump($_GET,'api/log/'.date('Ymd').'/face_door_attendance_advert_log',true);
        fdump($_POST,'api/log/'.date('Ymd').'/face_door_attendance_advert_log',true);
        fdump($info,'api/log/'.date('Ymd').'/face_door_attendance_advert_log',true);
        $device_id = $info['uid'];
        $d_house_face_device = M('House_face_device');
        $house_face_device_info = $d_house_face_device->where(array('device_id' => $device_id))->find();
        fdump('-----$house_face_device_info------'.__LINE__,'api/log/'.date('Ymd').'/face_door_attendance_advert_log',true);
        fdump($house_face_device_info,'api/log/'.date('Ymd').'/face_door_attendance_advert_log',true);

        $adver_list = M('House_open_door_adver')->order('add_time DESC')->where(array('status'=>1,'intelligent_hardware_type'=>1, 'equipment_type'=>'A1', 'begin_time' => array('elt', time()), 'end_time' => array('egt', time())))->select();
        $adver_village_list = M('Adver_village')->where(array('status'=>1,'village_id'=>$house_face_device_info['village_id']))->getField('adver_id,village_id');
        fdump('-----$adver_list------'.__LINE__,'api/log/'.date('Ymd').'/face_door_attendance_advert_log',true);
        fdump($adver_list,'api/log/'.date('Ymd').'/face_door_attendance_advert_log',true);
        fdump('-----$adver_village_list------'.__LINE__,'api/log/'.date('Ymd').'/face_door_attendance_advert_log',true);
        fdump($adver_village_list,'api/log/'.date('Ymd').'/face_door_attendance_advert_log',true);
        $adver_msg = array();
        if ($adver_list) {
            foreach ($adver_list as $item) {
                if ($adver_village_list && $adver_village_list[$item['id']]) {
                    $mediaType = 'image';
                    if (1==$item['advert_type']) {
                        $mediaType = 'video';
                    }
                    $advert = array(
                        'title' => $item['name'],
                        'media' => replace_file_domain($item['android_pic']),
                        'mediaType' => $mediaType,
                        'duration' => $item['play_time'],
                        'startDate' => $item['begin_time'],
                        'endDate' => $item['end_time'],
                    );
                    $adver_msg[] = $advert;
                }
            }
        }
        $arr = array();
        $arr['success'] = true;
        $arr['code'] = 0;
        $arr['message'] = '获取成功';
        $arr['data'] = $adver_msg;
        fdump('-----$arr------'.__LINE__,'api/log/'.date('Ymd').'/face_door_attendance_advert_log',true);
        fdump($arr,'api/log/'.date('Ymd').'/face_door_attendance_advert_log',true);
        exit(json_encode($arr));
    }

    // 广告更改后回调信息-文档为提供后续处理实际操作获取打印信息后再决定操作
    public function attendance_advert_notify() {
        fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/face_attendance_advert_notify_log',true);
        fdump($_GET,'api/log/'.date('Ymd').'/face_attendance_advert_notify_log',true);
        fdump($_POST,'api/log/'.date('Ymd').'/face_attendance_advert_notify_log',true);
    }


    public function open_door_notify() {
        fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/face_open_door_notify_log',true);
        fdump($_GET,'api/log/'.date('Ymd').'/face_open_door_notify_log',true);
        fdump($_POST,'api/log/'.date('Ymd').'/face_open_door_notify_log',true);
        $device_sn = $_GET['device_sn'];
        $token = $_GET['token'];
        $info = ticket::get($token , $device_sn, true);
        $device_id = $info['uid'];
        $d_house_face_device = M('House_face_device');
        $d_house_user_log = M('House_user_log');
        $d_house_village_floor = M('House_village_floor');
        $d_house_village = M('House_village');
        $d_house_village_user_bind = D('House_village_user_bind');
        $d_house_village_single = D('House_village_single');

        $house_face_device_info = $d_house_face_device->where(array('device_id' => $device_id))->find();
        $house_village_floor_info = $d_house_village_floor->where(array('floor_id' => $house_face_device_info['floor_id']))->field('floor_id,floor_layer,floor_name,single_id')->find();
        $house_village_single_info = $d_house_village_single->where(['id' => $house_village_floor_info['single_id']])->field('id as single_id,single_name')->find();
        $house_village_info = $d_house_village->where(array('village_id' => $house_face_device_info['village_id']))->field('village_id,village_name,city_id')->find();

        $village_name = $house_village_info['village_name'];


        if ( $house_face_device_info['public_area_id'] >0 ) {
            $public_area_name = M('House_village_public_area')->where(['public_area_id' => $house_face_device_info['public_area_id']])->getField('public_area_name');
            $floor_name = '';
            $floor_layer = $public_area_name;
        } elseif ( $house_face_device_info['floor_id'] == -1) {
            $floor_layer	= C('config.house_name');
            $floor_name = '大门';
        }else{
            $floor_layer = $house_village_single_info['single_name'] ? $house_village_single_info['single_name'] : $house_village_floor_info['floor_layer'];
            $floor_name = $house_village_floor_info['floor_name'];
        }

        $info = $_POST;
        $log = array();
        $house_village_bind = $d_house_village_user_bind->get_one_by_bindId($info['person_id']);

        $uid = $house_village_bind['uid'];
        $log['uid'] = $uid;
        $log['log_bind_id'] = $info['params']['pigcms_id'];
        $log['log_name'] = $village_name . ' ' . $floor_layer . ' ' . $floor_name;
        $log['log_from'] = 1; // 人脸识别门禁
        $log['log_business_id'] = $house_face_device_info['village_id']; // 社区id
        $log['log_more_id'] = $house_face_device_info['floor_id']; // 单元id
        $log['device_id'] = $device_id; // 设备id
        $log['log_time'] = time(); // 记录时间
        $detail=$info;
        // 0 开门成功，1 开门失败
        if ($info['success']) {
            $log['log_status'] = 1;
        } else {
            $log['log_status'] = 0;
        }
        if (isset($detail['imgUrl']) && trim($detail['imgUrl'])) {
            $log['cut_image'] = trim($detail['imgUrl']);
        } elseif (isset($detail['face_img']) && trim($detail['face_img'])) {
            $log['cut_image'] = trim($detail['face_img']);
        } elseif (isset($detail['mediaUrls']) && trim($detail['mediaUrls'])) {
            $log['cut_image'] = trim($detail['mediaUrls']);
        } elseif (isset($detail['pictureURL']) && trim($detail['pictureURL'])) {
            $log['cut_image'] = trim($detail['pictureURL']);
        }
        $log['log_detail'] = serialize($detail);
        $log['add_time']=time();
        fdump($detail,'api/log/'.date('Ymd').'/face_open_door_notify_log',true);
        fdump($log,'api/log/'.date('Ymd').'/face_open_door_notify_log',true);
        $d_house_user_log->data($log)->add();
        $d_house_face_device->where(array('device_id' => $device_id))->setField('last_open_time', time());

    }

    //模拟添加设备信息
    public function other_device() {
        $uid = '1358622';
       D('House_face_img')->house_user_face_device_info($uid);
    }
	
	public function recive_msg(){
		import('@.ORG.rabbit_mq');
        $qcloud_im = new rabbit_mq('DG160811816ADOOR9997901809');
		$qcloud_im->receive_message();
	}

    // A3通知
    public function a3_open_door_notify() {

        $device_sn = $_POST['deviceSn'];

        $d_house_face_device = M('House_face_device');
        $d_house_user_log = M('House_user_log');
        $d_house_village_floor = M('House_village_floor');
        $d_house_village = M('House_village');
        $d_house_village_user_bind = D('House_village_user_bind');
        $d_house_village_single = D('House_village_single');
        $d_face_user_bind_device = D('Face_user_bind_device');

        $house_face_device_info = $d_house_face_device->where(array('device_sn' => $device_sn))->find();
        // 跑计划任务
        if ($house_face_device_info['village_id']>0) {
            $param = [];
            $param['village_id'] = $house_face_device_info['village_id'];
            $param['type'] = 'person_inout_info';
            D('House_village_third_user_info')->add_plan($param, 'person_inout_info', time() + (mt_rand(5, 20)));
        } elseif (!$house_face_device_info) {
            fdump(['对应设备不存在》》》》'.__LINE__,$_REQUEST],'api/log/'.date('Ymd').'/a3/face_a3_open_door_notify_log',true);
            return false;
        }

        $house_village_info = $d_house_village->where(array('village_id' => $house_face_device_info['village_id']))->field('village_id,village_name,city_id')->find();

        $village_name = $house_village_info['village_name'];

        if ( $house_face_device_info['public_area_id'] >0 ) {
            $public_area_name = M('House_village_public_area')->where(['public_area_id' => $house_face_device_info['public_area_id']])->getField('public_area_name');
            $floor_name = '';
            $floor_layer = $public_area_name;
        } elseif ( $house_face_device_info['floor_id'] == -1) {
            $floor_layer	= C('config.house_name');
            $floor_name = '大门';
        }else{
            $house_village_floor_info = $d_house_village_floor->where(array('floor_id' => $house_face_device_info['floor_id']))->field('floor_id,floor_layer,floor_name,single_id')->find();

            $house_village_single_info = $d_house_village_single->where(['id' => $house_village_floor_info['single_id']])->field('id as single_id,single_name')->find();

            $floor_layer = $house_village_single_info['single_name'] ? $house_village_single_info['single_name'] : $house_village_floor_info['floor_layer'];
            $floor_name = $house_village_floor_info['floor_name'];
        }

        $info = $_POST['info'];
        $info_arr = json_decode($info, true);
        $code = $_POST['code'];
        // 用户信息数组
        $target_info = $info_arr['Target'];

        $d_house_camera_tip = M('House_camera_tip');

        $city_id = $house_village_info['city_id'];

        foreach($target_info as $val) {
            // 用户具体信息
            $log = array();
            if ($val['occurTime']) {
                $camera_time = strtotime($val['occurTime']);
            } elseif (isset($_POST['time'])) {
                if (strlen($_POST['time'])>strlen(time())) {
                    $time = $_POST['time']/1000;
                } else {
                    $time = $_POST['time'];
                }
                $camera_time = $time;
            } else {
                $camera_time = time();
            }
            if ($val['person']) {
                $status = 0;
                $person = $val['person'];
                $personID = $person['personID'] ? $person['personID'] : '';
                $remark = $person['remark'];
                if (isset($remark['pigcms_id']) && intval($remark['pigcms_id'])>0) {
                    $where_bind = [
                        'bind_type'=>0,
                        'bind_id' => intval($remark['pigcms_id']),
                        'device_id'=>$house_face_device_info['device_id']
                    ];
                } else {
                    $where_bind = [
                        'bind_type'=>0,
                        'personID' => $personID,
                        'device_id'=>$house_face_device_info['device_id']
                    ];
                }
                $face_bind = $d_face_user_bind_device->where($where_bind)->field('id,bind_id')->find();
                if (!$face_bind) {
                    $where_face_bind = [];
                    $where_face_bind['_string'] = "`info` LIKE '%\"personIdOnDevice\";s:5:\"{$personID}\"%'";
                    $where_face_bind['bind_type'] = 0;
                    $where_face_bind['device_id'] = $house_face_device_info['device_id'];
                    $face_bind = $d_face_user_bind_device->where($where_face_bind)->field('id,bind_id')->find();
                }
                $house_village_bind = $d_house_village_user_bind->where(['pigcms_id' => $face_bind['bind_id']])->field('uid,pigcms_id')->find();
                $uid = $house_village_bind['uid'];
                $bind_id = $house_village_bind['pigcms_id'];
                if ($bind_id) {
                    hook::hook_exec('house_cost_notice',array('bind_id'=>$bind_id));
                }
                $log['uid'] = $uid ? $uid : 0;
                $log['log_bind_id'] = $bind_id ? $bind_id : 0;
                $log['log_name'] = $village_name . ' ' . $floor_layer . ' ' . $floor_name;
                $log['log_from'] = 1; // 人脸识别门禁
                $log['log_business_id'] = $house_face_device_info['village_id']?$house_face_device_info['village_id']:0; // 社区id
                $log['log_more_id'] = $house_face_device_info['floor_id']?$house_face_device_info['floor_id']:0; // 单元id
                $log['device_id'] = $house_face_device_info['device_id']; // 设备id
                $log['log_time'] = $camera_time; // 记录时间
                // 0 比对通过，1 比对失败， 2 无模版， 3 无效卡 4 无现场照，5 非活体， 6 证件过期 7 超过时间限制， 8 超过此数限制 100 未知错误
                if (isset($_POST['code']) && $_POST['code']=='FaceAlignment') {
                    $log['log_status'] = 0;
                    // 比对成功呼叫电梯
                    if (1==$house_face_device_info['device_model'] && $house_face_device_info['floor_id'] && $house_face_device_info['floor_id']!=-1) {
                        D('Face_door_a7_service')->add_send_rs485($house_face_device_info['device_id']);
                    }
                } elseif (isset($_POST['code']) && $_POST['code']=='FaceStranger') {
                    $log['log_status'] = 1;
                    $status = 1;
                }
                if ($_POST['mediaUrls']) {
                    $_POST['face_img'] = D('Face_door_service')->face_to_img($_POST['mediaUrls'],$bind_id);
                }
                if (isset($_POST['face_img']) && trim($_POST['face_img'])) {
                    $log['cut_image'] = trim($_POST['face_img']);
                } elseif (isset($_POST['mediaUrls']) && trim($_POST['mediaUrls'])) {
                    $log['cut_image'] = trim($_POST['mediaUrls']);
                }
                $log['log_detail'] = serialize($_POST);
                $log['eventId'] = $_POST['id'];
                $log['add_time']=time();
                $whereArr=array('log_bind_id'=>$bind_id,'uid'=>$uid,'log_business_id'=>$log['log_business_id'],'device_id'=>$log['device_id'],'log_time'=>$log['log_time']);
                $onetmp=$d_house_user_log->where($whereArr)->find();
                if(empty($onetmp)){
                    $id = $d_house_user_log->data($log)->add();
                    if (!$id) {
                        fdump(['比对成功但是记录失败》》'.__LINE__,$log],'api/log/'.date('Ymd').'/a3/face_a3_open_door_notify_log',true);
                    }  
                }
                if (0==$log['log_status']) {
                    $d_house_face_device->where(array('device_sn' => $device_sn))->setField('last_open_time', time());
                }
            } elseif ($code && 'FaceStranger'==$code) {
                $uid = 0;
                $bind_id = 0;
                $log['uid'] = 0;
                $log['log_bind_id'] = 0;
                $log['log_name'] = $village_name . ' ' . $floor_layer . ' ' . $floor_name;
                $log['log_from'] = 1; // 人脸识别门禁
                $log['log_business_id'] = $house_face_device_info['village_id']?$house_face_device_info['village_id']:0; // 社区id
                $log['log_more_id'] = $house_face_device_info['floor_id']?$house_face_device_info['floor_id']:0; // 单元id
                $log['device_id'] = $house_face_device_info['device_id']; // 设备id
                $log['log_time'] = $camera_time; // 记录时间
                // 0 比对通过，1 比对失败， 2 无模版， 3 无效卡 4 无现场照，5 非活体， 6 证件过期 7 超过时间限制， 8 超过此数限制 100 未知错误
                $log['log_status'] = 1;
                $status = 1;
                if ($_POST['mediaUrls']) {
                    $_POST['face_img'] =  D('Face_door_service')->face_to_img($_POST['mediaUrls'],$bind_id);
                }
                if (isset($_POST['face_img']) && trim($_POST['face_img'])) {
                    $log['cut_image'] = trim($_POST['face_img']);
                } elseif (isset($_POST['mediaUrls']) && trim($_POST['mediaUrls'])) {
                    $log['cut_image'] = trim($_POST['mediaUrls']);
                }
                $log['log_detail'] = serialize($_POST);
                $log['eventId'] = $_POST['id'];
                $log['add_time']=time();
                $id = $d_house_user_log->data($log)->add();
                if (!$id) {
                    fdump(['比对失败》》'.__LINE__,$log],'api/log/'.date('Ymd').'/a3/face_a3_open_door_notify_log',true);
                }
                if (0==$log['log_status']) {
                    $d_house_face_device->where(array('device_sn' => $device_sn))->setField('last_open_time', time());
                }
            } else {
                $status = 1;
                $uid = 0;
                $bind_id = 0;
            }

            $tip = array(
                'bind_id' =>  $bind_id ? $bind_id : 0,
                'uid' => $uid ? $uid : 0,
                'type' => 1,
                'village_id' => $house_face_device_info['village_id'] ? $house_face_device_info['village_id'] : 0,
                'camera_sn' => $device_sn,
                'city_id' => $city_id ? $city_id : 0,
                'camera_time' => time(),
                'camera_img' => $_POST['mediaUrls'],
                'tip_time' => $camera_time,
                'status' => $status,
                'tip_txt' => serialize($_POST)
            );
            $d_house_camera_tip->data($tip)->add();
        }
        return true;
    }


    /**
     * Notes:
     * @param $base_img
     * @param $id
     * @return bool|string
     * @author: wanzy
     * @date_time: 2020/9/11 11:16
     */
    private function face_to_img($base_img, $id) {
//        $img_mer_id = sprintf("%09d", $id);
//        $rand_num = substr($img_mer_id, 0, 3) . '/' . substr($img_mer_id, 3, 3) . '/' . substr($img_mer_id, 6, 3);
        $file_exists_msg = D('Face_door_service')->file_exists($base_img);
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
            if ($file_info['basename']) {
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
            unset($img,$url);
            //判断是否需要上传至云存储
            $file_handle = new file_handle();
            $file_handle_url = trim($new_file,'./');
            $file_handle->upload($file_handle_url);
            $img_path = trim($new_file,'.');
            return $img_path;
        } else {
            return false;
        }
    }

    /**
     * Notes: 四川人口信息网对接人脸信息返回
     * @author: wanzy
     * @date_time: 2020/9/23 14:36
     */
    public function third_sichuan_img() {
		header("Content-type: application/json");
        $data = $_POST;
		
		##绵阳市返回的是一维数组
		if($data['name']){
			$data = [$data];
		}
		
        unset($data['now_lang']);
        fdump_api(['>>>>>>'.__LINE__,$data],'third/third_sichuan_img_log',1);
        $village_id = intval($_GET['village_id'])>0 ? intval($_GET['village_id']):0;
        $db_house_village_third_user_info = D('House_village_third_user_info');
        $where = [
            'village_id' => $village_id,
            'third_type' => 'chinapopin_sichuan'
        ];
        foreach ($data as $val) {
            $where['ywlsh'] = $val['ywlsh'];
            $third_user_info = $db_house_village_third_user_info->field('third_user_id, img_url')->where($where)->find();
//            if ($third_user_info && $third_user_info['img_url']) {
//                // 同一小区处理过不在做记录
//                continue;
//            }
            $address_arr = explode('号', $val['address']);
            $address = array_pop($address_arr);
            if (count($address_arr)>1 && !$address && $address_arr[1]) {
                $address = $address_arr[1];
            } elseif ($address_arr && $address_arr[1]) {
                $address = $address_arr[1];
            } else {
                $address = '';
            }
			
			##绵阳市返回的是图片地址
			if(stripos($val['photo'], 'http') === 0){
				$rand_num = date('Ymd');// 换成日期存储
				$up_dir = "./upload/houseThird/".$rand_num.'/';
				if (!is_dir($up_dir)) {
					mkdir($up_dir, 0777, true);
				}
				$type = 'jpg';
				$new_file = $up_dir.date('YmdHis_').uniqid().'.jpg';
				if(file_put_contents($new_file, file_get_contents($val['photo']))){
					//判断是否需要上传至云存储
					$file_handle = new file_handle();
					$file_handle->upload($new_file);
					$img_path = trim($new_file, '.');
					$img_url = $img_path;
				}else{
					$ret_msg = [
						'code' => 0,
						'message' => '照片下载失败，请重试。',
						'data' => '',
					];
					echo json_encode($ret_msg,JSON_UNESCAPED_UNICODE);
					exit();
				}
			} else if ($val['photo']) {
                $id = $village_id ? $village_id : $val['ywlsh'];
                $photo = str_replace('data:image/jpeg;base64,','',$val['photo']);
                $img_url = $this->base64_to_img($photo,'houseThird');
            } else {
                $img_url = '';
            }
            $data = [
                'village_id' => $village_id,
                'source' => $val['source'],
                'photo' => $val['photo'],
                'phone' => $val['phone'],
                'type' => $val['type'],
                'ywlsh' => $val['ywlsh'],
                'from' => 1,
                'third_ryid' => '',
                'third_xqryid' => $val['ywlsh'],
                'third_xm' => $val['name'],
                'third_xbdm' => isset($val['xbdm']) ? ($val['xbdm'] == 1 ? 2 : 1) : 0,
                'third_zjhm' => $val['idCard'],
                'third_jzd_dzxz' => $val['address'],
                'address' => $address,
                'img_url' => $img_url,
                'status' => 0,
                'add_time' => time(),
            ];
			
			if($val['ownerId']){
				$data['ownerId'] = $val['ownerId'];
			}
			if($val['visitTime']){
				$data['visitTime'] = substr($val['visitTime'], 0, 10);
			}
			if($val['effectiveVisitTime']){
				$data['effectiveVisitTime'] = substr($val['effectiveVisitTime'], 0, 10);
			}
			
            if ($third_user_info) {
                $add_id = $db_house_village_third_user_info->where(['third_user_id' => $third_user_info['third_user_id']])->data($data)->save();
            } else {
                $add_id = $db_house_village_third_user_info->data($data)->add();
            }
            if (!$add_id) {
                fdump_api(['记录失败>>>'.__LINE__,$db_house_village_third_user_info->_sql(),$db_house_village_third_user_info->getDbError()],'third/third_sichuan_img_log',1);
            }
        }
        // 添加计划任务同步数据
        $param = [];
        $param['village_id'] = $village_id;
        $param['type'] = 'handle_third_user_info';
        $db_house_village_third_user_info->add_plan($param);

        $ret_msg = [
            'code' => 1,
            'message' => '',
            'data' => '',
        ];
        echo json_encode($ret_msg,JSON_UNESCAPED_UNICODE);
		exit();
    }
    /**
     * Notes: 智慧平安小区
     */
    public function third_chinapopin() {
        header("Content-type: application/json");
        $data = $_POST;

        ##绵阳市返回的是一维数组
        if($data['name']){
            $data = [$data];
        }

        unset($data['now_lang']);
        fdump_api(['>>>>>>'.__LINE__,$data],'third/third_chinapopin',1);
        $village_id = intval($_GET['village_id'])>0 ? intval($_GET['village_id']):0;
        $db_house_village_third_user_info = D('House_village_third_user_info');
        $where = [
            'village_id' => $village_id,
            'type' => 'ezc_chinapopin'
        ];
        //检查配置是否开启
        $third_config = M('House_village_third_config')->where($where)->find();
        if(!$third_config || $third_config['status']==0){
            //未开启，不录入
            $ret_msg = [
                'code' => 1,
                'message' => '',
                'data' => '',
            ];
            echo json_encode($ret_msg,JSON_UNESCAPED_UNICODE);
            exit();
        }
        foreach ($data as $val) {
            $val['ywlsh'] = $val['ywlsh'] ?? $val['id'];
            $where['ywlsh'] = $val['ywlsh'];
            $third_user_info = $db_house_village_third_user_info->field('third_user_id, img_url')->where($where)->find();
//            if ($third_user_info && $third_user_info['img_url']) {
//                // 同一小区处理过不在做记录
//                continue;
//            }
            $address_arr = explode('号', $val['address']);
            $address = array_pop($address_arr);
            if (count($address_arr)>1 && !$address && $address_arr[1]) {
                $address = $address_arr[1];
            } elseif ($address_arr && $address_arr[1]) {
                $address = $address_arr[1];
            } else {
                $address = '';
            }

            ##绵阳市返回的是图片地址
            if(stripos($val['photo'], 'http') === 0){
                $rand_num = date('Ymd');// 换成日期存储
                $up_dir = "./upload/houseThird/".$rand_num.'/';
                if (!is_dir($up_dir)) {
                    mkdir($up_dir, 0777, true);
                }
                $type = 'jpg';
                $new_file = $up_dir.date('YmdHis_').uniqid().'.jpg';
                if(file_put_contents($new_file, file_get_contents($val['photo']))){
                    //判断是否需要上传至云存储
                    $file_handle = new file_handle();
                    $file_handle->upload($new_file);
                    $img_path = trim($new_file, '.');
                    $img_url = $img_path;
                }else{
                    $ret_msg = [
                        'code' => 0,
                        'message' => '照片下载失败，请重试。',
                        'data' => '',
                    ];
                    echo json_encode($ret_msg,JSON_UNESCAPED_UNICODE);
                    exit();
                }
            } else if ($val['photo']) {
                $id = $village_id ? $village_id : $val['ywlsh'];
                $photo = str_replace('data:image/jpeg;base64,','',$val['photo']);
                $img_url = $this->base64_to_img($photo,'houseThird');
            } else {
                $img_url = '';
            }
            $data = [
                'village_id' => $village_id,
                'source' => $val['source'],
                'photo' => $val['photo'],
                'phone' => $val['phone'],
                'type' => $val['type'],
                'ywlsh' => $val['ywlsh'],
                'from' => 1,
                'third_ryid' => '',
                'third_xqryid' => $val['ywlsh'],
                'third_xm' => $val['name'],
                'third_xbdm' => isset($val['xbdm']) ? ($val['xbdm'] == 1 ? 2 : 1) : 0,
                'third_zjhm' => $val['idCard'],
                'third_jzd_dzxz' => $val['address'],
                'address' => $address,
                'img_url' => $img_url,
                'status' => 0,
                'third_type' => 'ezc_chinapopin',
                'add_time' => time(),
            ];

            if($val['ownerId']){
                $data['ownerId'] = $val['ownerId'];
            }
            if($val['visitTime']){
                $data['visitTime'] = substr($val['visitTime'], 0, 10);
            }
            if($val['effectiveVisitTime']){
                $data['effectiveVisitTime'] = substr($val['effectiveVisitTime'], 0, 10);
            }

            fdump_api(['>>>>>>'.__LINE__,'third_user_info' => $third_user_info, 'val' => $val],'third/third_chinapopin',1);
            if ($third_user_info) {
                $data['check_status'] = 0;
                $data['check_reason'] = '';
                $data['check_time'] = 0;
                $data['status'] = 0;
                $add_id = $db_house_village_third_user_info->where(['third_user_id' => $third_user_info['third_user_id']])->data($data)->save();
            } else {
                $add_id = $db_house_village_third_user_info->data($data)->add();
            }
            if (!$add_id) {
                fdump_api(['记录失败>>>'.__LINE__,$db_house_village_third_user_info->_sql(),$db_house_village_third_user_info->getDbError()],'third/third_chinapopin',1);
            }
        }
        // 添加计划任务同步数据
        $param = [];
        $param['village_id'] = $village_id;
        $param['type'] = 'handle_third_user_info';
        $db_house_village_third_user_info->add_plan($param);
//        $db_house_village_third_user_info->handle_third_user_info('50');

        $ret_msg = [
            'code' => 1,
            'message' => '',
            'data' => '',
        ];
        echo json_encode($ret_msg,JSON_UNESCAPED_UNICODE);
        exit();
    }

    /**
     * Notes视频接口
     * @datetime: 2021/1/13 16:27
     */
    public function openVisual(){
		fdump_api($_POST, 'a5/openVisual', true);
		
        $data = $_POST;
        if(!$data){
            return true;
        }
        $res = D('Face_door_a5_service')->sendPush($data);
        if($res){
            return true;
        }
    }
    //设置视频加密/不加密
    public function set_video_encrypt()
    {
        fdump_api(['设置视频加密/不加密：'.__LINE__,'REQUEST' => $_REQUEST],'set_video_encrypt',1);
        $type = I('type');
        if(!in_array($type,[1,2])){
            $this->returnCodeError('参数异常');
        }
        $device_sn = I('device_sn');
        if(!$device_sn){
            $this->returnCodeError('参数异常');
        }
        $where['device_sn'] = $device_sn;
        $where['device_status'] = 1;
        $where['is_del'] = 0;
        $device_info = D('House_face_device')->where($where)->find();
        fdump_api(['设置视频加密/不加密：'.__LINE__,'device_info' => $device_info],'set_video_encrypt',1);
        if($type == 1){//关闭
            if (isset($device_info['thirdProtocol']) && intval($device_info['thirdProtocol']) == 12) {
                // 内部应用走内部应用获取 \app\community\model\service\Device\DeviceHkNeiBuHandleService::getVisualParam
                $models = '\app\community\model\service\Device\DeviceHkNeiBuHandleService/offVideoEncrypt';
                $device_id = isset($device_info['device_id']) ? intval($device_info['device_id']) : 0;
                fdump_api(['设置视频加密/不加密：'.__LINE__,'$device_id' => $device_id,'deviceSerial' => $device_sn],'set_video_encrypt',1);
                $result = invoke_v20_service($models, ['device_id' => $device_id, 'deviceSerial' => $device_sn]);
                fdump_api(['设置视频加密/不加密：'.__LINE__,'$result' => $result],'set_video_encrypt',1);
                $result = $result['data']['retval'];
                if($result){
                    $this->returnCode(0, $result);
                }else{
                    $this->returnCodeError('设置失败');
                }
            }
            $res = D('Face_door_a5_service')->setVideoOff($device_info['device_sn'],$device_info['cloud_code']);
            if($res){
                $this->returnCode(0, $res);
            }else{
                $this->returnCodeError('设置失败');
            }

        }else{//开启
            if (isset($device_info['thirdProtocol']) && intval($device_info['thirdProtocol']) == 12) {
                $this->returnCode(0, []);
            }
            $res = D('Face_door_a5_service')->setVideoOn($device_info['device_sn']);
            if($res){
                $this->returnCode(0, $res);
            }else{
                $this->returnCodeError('设置失败');
            }
        }
    }
    //获取视频取流时需要的认证信息
    public function get_approve()
    {
        $res = D('Face_door_a5_service')->getApprove();
        if($res['code'] == 200)
        {
            $this->returnCode(0, $res['data']);
        }else{
            $this->returnCodeError($res['message']);
        }
    }
    //获取视频通道号
    public function get_channel()
    {
        $device_sn = I('device_sn');
        $uid = $this->_uid;
        if(!$device_sn){
            $this->returnCodeError('设备序列号异常');
        }
        //$pigcms_id = I('pigcms_id');
        //$user_bind = D('House_village_user_bind')->where(['pigcms_id'=>$pigcms_id])->field('village_id,single_id,floor_id,layer_id,vacancy_id')->find();
        fdump_api(['获取视频通道号：'.__LINE__,'$device_sn' => $device_sn,'$uid' => $uid],'get_channel',1);
        $info = D('Face_door_a5_service')->getVisualParam($device_sn,$uid);
        fdump_api(['获取视频通道号：'.__LINE__,'$info' => $info],'get_channel',1);
        $this->returnCode(0, $info);
    }
}
