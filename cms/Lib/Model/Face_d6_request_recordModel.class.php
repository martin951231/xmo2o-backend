<?php
/**
 * d6人脸门禁相关业务
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2022/1/13 19:58
 */

class Face_d6_request_recordModel extends Model
{
    public $time=180;
    // 循环处理步人员
    public function d6_arr_person_msg($device_id,$bind_arr_id) {
        if ($bind_arr_id) {
            foreach ($bind_arr_id as $item) {
                $this->sync_d6_face_per($device_id, $item);
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
    public function sync_d6_face_per($device_id, $pigcms_id,$village_id=0, $device_info=[]) {
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
            $db_face_door_service = D('Face_door_service');
            $param = array();
            $param['type'] = 'sync_d6_face_single_per';
            $param['device_id'] = $device_id;
            $param['village_id'] = $village_id;
            $param['pigcms_id'] = $pigcms_id;
            $param['bind_info'] = [];
            $param['device_info'] = $device_info;
            $param['unique_id'] = 'sync_d6_face_single_per_'.$param['village_id'].'_'.$device_id.'_'.$pigcms_id;
            $db_face_door_service->add_plan($param, 'sub_house_village_face_device',-10);
//            $db_face_a7_request_record->add_create_face_record($device_id, $pigcms_id, $village_id, $user_bind, $data, $device_info);
        }
        return true;
    }

    /**
     * Notes: 添加人脸
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
        if (!$village_id && $bind_info['village_id']) {
            $village_id = $bind_info['village_id'];
        } elseif (!$village_id && $device_info['village_id']) {
            $village_id = $device_info['village_id'];
        }
        $cmd_name = 'syncFace';
        $where_repeat = [
            'device_id' => $device_id,
            'bind_id' => $pigcms_id,
            'cmd_name' => $cmd_name,
            'status' => 0,
        ];
        $repeat_id = M('Face_d6_request_record')->where($where_repeat)->getField('id');
        if ($repeat_id) {
            // 直接将之前未执行的命令改为已执行
            M('Face_d6_request_record')->where(['id' => $repeat_id])->data(['status' => 1, 'update_time' => time()])->save();
        }
        $device_sn = $device_info['device_sn'];
        $operator_type = isset($data['operator_type']) ? intval($data['operator_type']) : 1;
        $operator_id = isset($data['operator_id']) ? intval($data['operator_id']) : $village_id;
        $status = 0;
        $add_time = time();

        // 获取下图片
        set_time_limit(0);
        ini_set('memory_limit', '1024M');
        if ($bind_info['uid']>0) {
            $d_house_face_img = M('House_face_img');
            $image_info = $d_house_face_img->where(array('uid'=>$bind_info['uid'],'status'=>['in', [0,3]],'img_url'=>array('neq','')))->order('add_time ASC')->find();
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
                    $face_id = $image_info['id'];
                } catch( \Exception $e) {
                    $img_url = '';
                }
            }
        }
        if (!in_array($bind_info['type'],[0,3,4,5,6]) && $bind_info['parent_id']) {
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
        } elseif ($bind_info['type']!=4) {
            $property_starttime = $bind_info['property_starttime']?$bind_info['property_starttime']:0;
            $property_endtime = $bind_info['property_endtime']?$bind_info['property_endtime']:0;
        }
        if (in_array($bind_info['type'],[5,6]) && !$property_endtime) {
            fdump_api(['临时访客和通行证必定有时效 没有时效不做同步：'.__LINE__,$device_id, $pigcms_id,$village_id,$bind_info,$data,$device_sn,$bind_info],'d6/add_create_face_record_err_log',1);
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

        $data_msg[] = [
            'id' => $face_id, // 人员id：不超过32位
            'faceUrl' => $img_url ? $img_url : '',// base64 人脸图片
        ];
        $command=[
            'cmd' => $cmd_name,
            'device_sn'=>$device_sn,
            'params'=>$data_msg
        ];
        // 整合需要添加的数据
        $add_data = [
            'village_id' => $village_id,
            'device_sn' => $device_sn,
            'device_id' => $device_id,
            'bind_id' => $pigcms_id,
            'operator_type' => $operator_type,
            'operator_id' => $operator_id,
            'cmd_name' => $cmd_name,
            'status' => $status,
            'command' => json_encode($command),
            'add_time' => $add_time,
            'type' => 1
        ];
        $add_id = $this->data($add_data)->add();
        if ($add_id) {
            $ret_data['data'] = $add_id;
            return $ret_data;
        } else {
            fdump_api(['记录失败>：'.__LINE__,$device_id,$data,$pigcms_id,$village_id,$device_info,$data_msg,$add_data,$bind_info],'d6/add_create_face_record_err_log',1);
            $ret_data['error'] = 1;
            $ret_data['msg'] = '添加失败';
            return $ret_data;
        }
    }


    /**
     * 添加、编辑设备
     * @author: liukezhu
     * @date : 2021/7/14
     * @param $device_id 设备号
     * @param $type     1：添加 2：编辑
     *
     */
    public function d6_device($device_id,$type){
        $House_face_device=M('House_face_device');
        $device = $House_face_device->where(array('device_id' => $device_id))->find();
        if(!$device){
            fdump('设备不存在','api/log/'.date('Ymd').'/d6/d6_device_log',true);
            return false;
        }
        $data=[];
        $status = 1;
        $add_time = time();
        $cmd_name = 'deviceSet';

        $param=[];
       // $param['deviceType']='0103';//围墙机
        if($device['floor_id']!='-1'){
            $param['deviceType']='0101';//门口机
            $where_floor = [
                'a.floor_id' => $device['floor_id']
            ];
            $floor_info = M('House_village_floor')
                ->join('as a LEFT JOIN '.C('DB_PREFIX')."house_village_single as b ON a.single_id = b.id")
                ->field('a.floor_number,a.floor_name, b.single_name,b.single_number')
                ->group('a.floor_id')
                ->where($where_floor)
                ->find();
            if (!$floor_info) {
                fdump('地址信息不存在','api/log/'.date('Ymd').'/d6/d6_device_log',true);
                return false;
            }

            if (!empty($floor_info['single_number'])){
                $param['build']=$floor_info['single_number'];//楼栋编号
            }else{
                fdump('楼栋编号不存在','api/log/'.date('Ymd').'/d6/d6_device_log',true);
                return false;
            }
            if (!empty($floor_info['floor_number'])){
                $param['unit']=$floor_info['floor_number'];//单元编号
            }else{
                fdump('单元编号不存在','api/log/'.date('Ymd').'/d6/d6_device_log',true);
                return false;
            }
        }

        if (!empty($device_id)){
            $param['number']=$device_id;//设备编号
        }else{
            fdump('单元编号不存在','api/log/'.date('Ymd').'/d6/d6_device_log',true);
            return false;
        }
        $command=[
            'cmd' => $cmd_name,
            'device_sn'=>$device['device_sn'],
            'params'=>$param
        ];
        // 整合需要添加的数据
        $add_data = [
            'village_id' => $device['village_id'],
            'device_id' => $device['device_id'],
            'device_sn' => $device['device_sn'],
            'bind_id' => 0,
            'operator_type' => 0,
            'operator_id' => $device['village_id'],
            'cmd_name' => $cmd_name,
            'status' => $status,
            'command' => json_encode($command),
            'add_time' => $add_time,
            'sort' => 99,
            'type' => 1
        ];
        $add_id = $this->data($add_data)->add();
        return $add_id;
    }

    /**
     * Notes: 设备重启
     * @param $device_id
     * @param int $village_id
     * @param array $device_info
     * @return bool
     * @author: wanzy
     * @date_time: 2020/9/5 16:22
     */
    public function d6_reboot_cam($device_id,$village_id=0, $device_info=[]) {
        $db_face_a7_request_record = D('Face_a7_request_record');
        // 设置是否开启活体检测
        $db_face_a7_request_record->add_reboot_cam_record($device_id, [], $village_id, $device_info);
        return true;
    }

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
        $repeat_id = M('Face_d6_request_record')->where($where_repeat)->getField('id');
        if ($repeat_id) {
            // 直接将之前未执行的命令改为已执行
            M('Face_d6_request_record')->where(['id' => $repeat_id])->data(['status' => 1, 'update_time' => time()])->save();
        }

        $device_sn = $device_info['device_sn'];
        $operator_type = isset($data['operator_type']) ? intval($data['operator_type']) : 1;
        $operator_id = isset($data['operator_id']) ? intval($data['operator_id']) : $village_id;
        $status = 0;
        $add_time = time();
        $data_msg = [
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
            'status' => $status,
            'command' => json_encode($data_msg),
            'add_time' => $add_time,
            'sort' => 100,
            'type' => 1
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
    public function sync_d6_face_all_per($device_id, $village_id=0, $floor_id='', $device_info=[],$is_plan=1) {

        fdump([$device_id, $village_id, $floor_id, $device_info,$is_plan],'api/log/'.date('Ymd').'/d6/sync_d6_face_all_per',true);
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
                    $param['type'] = 'sync_d6_face_single_per';
                    $param['device_id'] = $device_id;
                    $param['village_id'] = $village_id;
                    $param['pigcms_id'] = $val['pigcms_id'];
                    $param['bind_info'] = $val;
                    $param['device_info'] = $device_info;
                    $param['unique_id'] = 'sync_d6_face_single_per_'.$param['village_id'].'_'.$device_id.'_'.$val['pigcms_id'];
                    $db_face_door_service->add_plan($param, 'sub_house_village_face_device',-10);
                } else {
                    $this->add_create_face_record($device_id, $val['pigcms_id'], $village_id, $val, [], $device_info);
                }
            }
        }
        return true;
    }


    /**
     * Notes: 添加卡号
     * @param string $card 卡号
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
    public function add_create_card_record($card,$device_id,$pigcms_id,$village_id=0,$bind_info = [],$data=[],$device_info=[]) {
        if (empty($bind_info)) {
            $bind_info = M('House_village_user_bind')->where(array('pigcms_id' => $pigcms_id))->find();
        }
        if (empty($device_info)) {
            $device_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        }
        if (!$village_id && $bind_info['village_id']) {
            $village_id = $bind_info['village_id'];
        } elseif (!$village_id && $device_info['village_id']) {
            $village_id = $device_info['village_id'];
        }
        $cmd_name = 'syncCard';
        $where_repeat = [
            'device_id' => $device_id,
            'bind_id' => $pigcms_id,
            'cmd_name' => $cmd_name,
            'status' => 0,
        ];
        $repeat_id = M('Face_d6_request_record')->where($where_repeat)->getField('id');
        if ($repeat_id) {
            // 直接将之前未执行的命令改为已执行
            M('Face_d6_request_record')->where(['id' => $repeat_id])->data(['status' => 1, 'update_time' => time()])->save();
        }
        $device_sn = $device_info['device_sn'];
        $operator_type = isset($data['operator_type']) ? intval($data['operator_type']) : 1;
        $operator_id = isset($data['operator_id']) ? intval($data['operator_id']) : $village_id;
        $status = 0;
        $add_time = time();

        $property_starttime = $bind_info['property_starttime']?$bind_info['property_starttime']:0;
        $property_endtime = $bind_info['property_endtime']?$bind_info['property_endtime']:0;

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

        if ($device_info['floor_id']!="-1"){
            $address_data=$this->getAddress($bind_info);

            $data_msg[] = [
                'card' => $card, // 卡号
                'build' => $address_data['singleName'], // 卡号
                'unit' => $address_data['floorName'], // 卡号
                'floor' => $address_data['layer'], // 卡号
                'startTime' => $s_time ? $s_time : '',// 开始时间
                'endTime' => $e_time ? $e_time : '',// 结束时间
            ];
        }else{
            $data_msg[] = [
                'card' => $card, // 卡号
                'startTime' => $s_time ? $s_time : '',// 开始时间
                'endTime' => $e_time ? $e_time : '',// 结束时间
            ];
        }

        $command=[
            'cmd' => $cmd_name,
            'device_sn'=>$device_sn,
            'params'=>$data_msg
        ];
        // 整合需要添加的数据
        $add_data = [
            'village_id' => $village_id,
            'device_sn' => $device_sn,
            'device_id' => $device_id,
            'bind_id' => $pigcms_id,
            'operator_type' => $operator_type,
            'operator_id' => $operator_id,
            'cmd_name' => $cmd_name,
            'status' => $status,
            'command' => json_encode($command),
            'add_time' => $add_time,
            'type' => 1
        ];
        $add_id = $this->data($add_data)->add();
        if ($add_id) {
            $ret_data['data'] = $add_id;
            return $ret_data;
        } else {
            fdump_api(['记录失败>：'.__LINE__,$device_id,$data,$pigcms_id,$village_id,$device_info,$data_msg,$add_data,$bind_info],'d6/add_create_face_record_err_log',1);
            $ret_data['error'] = 1;
            $ret_data['msg'] = '添加失败';
            return $ret_data;
        }
    }

    public function getAddress($user_info){
        //  print_r($user_info);exit;
        if($user_info['single_id'] == 0){
            $singleName = '';
        }else{
            $single = D('House_village_single')->where(['id'=>$user_info['single_id']])->field('id,single_name,single_number')->find();
            if (empty($single['single_number'])){
                $singleName = $single['single_name'];
            }else{
                $singleName = $single['single_number'];
            }
        }
        if($user_info['floor_id'] == 0){
            $floorName = '';
        }else{
            $floor = D('House_village_floor')->where(['floor_id'=>$user_info['floor_id']])->field('floor_name,floor_number')->find();
            if (empty($floor['floor_number'])){
                $floorName = $floor['floor_name'];
            }else{
                $floorName = $floor['floor_number'];
            }
        }
        if($user_info['layer_id'] == ''){
            $layer = '';
        }elseif (is_numeric($user_info['layer_id'])){
            $layer_info = D('House_village_layer')->where(['id'=>$user_info['layer_id']])->field('layer_name,layer_number')->find();
            if (empty($layer_info['layer_number'])){
                $layer = $layer_info['layer_name'];
            }else{
                $layer = $layer_info['layer_number'];
            }
        }
        if($user_info['vacancy_id'] == 0){
            $room = '';
        }else{
            $vacancy = D('House_village_user_vacancy')->where(['pigcms_id'=>$user_info['vacancy_id']])->field('room,room_number')->find();
            if (empty($vacancy['room_number'])){
                $room = $vacancy['room'];
            }else{
                $room = $vacancy['room_number'];
            }

        }
        $arr=[];
        $arr['singleName']=$singleName;
        $arr['floorName']=$floorName;
        $arr['layer']=$layer;
        $arr['room']=$room;
        return  $arr;
    }

    public function del_card_record($card,$device_id,$pigcms_id,$village_id=0){
        $device_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        $cmd_name = 'cardDel';
        $where_repeat = [
            'device_id' => $device_id,
            'bind_id' => $pigcms_id,
            'cmd_name' => $cmd_name,
            'status' => 0,
        ];
        $repeat_id = M('Face_d6_request_record')->where($where_repeat)->getField('id');
        if ($repeat_id) {
            // 直接将之前未执行的命令改为已执行
            M('Face_d6_request_record')->where(['id' => $repeat_id])->data(['status' => 1, 'update_time' => time()])->save();
        }
        $device_sn = $device_info['device_sn'];
        $operator_type = isset($data['operator_type']) ? intval($data['operator_type']) : 1;
        $operator_id = isset($data['operator_id']) ? intval($data['operator_id']) : $village_id;
        $status = 0;
        $add_time = time();
        $data_msg[] = [
            'card' => $card, // 卡号
        ];
        $command=[
            'cmd' => $cmd_name,
            'device_sn'=>$device_sn,
            'params'=>$data_msg
        ];
        // 整合需要添加的数据
        $add_data = [
            'village_id' => $village_id,
            'device_sn' => $device_sn,
            'device_id' => $device_id,
            'bind_id' => $pigcms_id,
            'operator_type' => $operator_type,
            'operator_id' => $operator_id,
            'cmd_name' => $cmd_name,
            'status' => $status,
            'command' => json_encode($command),
            'add_time' => $add_time,
            'type' => 1
        ];
        $add_id = $this->data($add_data)->add();
        if ($add_id) {
            $ret_data['data'] = $add_id;
            return $ret_data;
        } else {
            fdump_api(['记录失败>：'.__LINE__,$device_id,$data,$pigcms_id,$village_id,$device_info,$data_msg,$add_data],'d6/add_create_face_record_err_log',1);
            $ret_data['error'] = 1;
            $ret_data['msg'] = '添加失败';
            return $ret_data;
        }
    }

    //修改设备信息反馈
    public function d6_deviceSet_result($data){
        $db_house_face_device = M('house_face_device');
        $device_info=$db_house_face_device->where(['device_sn'=>$data['device_sn']])->find();
        return true;
    }
    //人脸注册反馈
    public function d6_syncFace_result($data){
        $db_house_face_device = M('house_face_device');
        $db_house_village_user_bind=M('House_village_user_bind');
        $device_info=$db_house_face_device->where(['device_sn'=>$data['device_sn']])->find();
        if (!empty($device_info)){
            $msg=json_decode($data['command'],true);
            if ($msg['params']['code']==0){
                $user_info=$db_house_village_user_bind->where(['pigcms_id'=>$msg['params']['id']])->find();
               if (!empty($user_info)){
                   $db_house_village_user_bind->where(['pigcms_id'=>$msg['params']['id']])->data(['face_img_status'=>1])->save();
               }
            }else{
                $db_house_village_user_bind->where(['pigcms_id'=>$msg['params']['id']])->data(['face_img_status'=>2])->save();
            }
        }
        return true;
    }
    //下发物业标识配置权限反馈
    public function d6_syncQrManager_result($data){
        $db_house_face_device = M('house_face_device');
        $device_info=$db_house_face_device->where(['device_sn'=>$data['device_sn']])->find();
        return true;
    }
    //人脸清空反馈
    public function d6_faceClear_result($data){
        return true;
    }
    //传统卡权限配置反馈
    public function d6_syncCard_result($data){
        $db_house_face_device = M('House_face_device');
        $device_info=$db_house_face_device->where(['device_sn'=>$data['device_sn']])->find();
        return true;
    }
    //开门信息反馈
    public function d6_passEvent_result($data){
        $db_house_face_device = M('House_face_device');
        $db_house_user_log = M('House_user_log');
        $db_house_village_user_bind=M('House_village_user_bind');
        $db_house_village=M('House_village');
        $device_info=$db_house_face_device->where(['device_sn'=>$data['device_sn']])->find();
        if (!empty($device_info)){
            $msg=json_decode($data['command'],true);
            if ($msg['params']['type']=='tradCard'){
                $user_info=$db_house_village_user_bind->where(['ic_card'=>$msg['params']['content'],'village_id'=>$device_info['village_id']])->find();
                $arr=[];
                $arr['log_from']=3;
                $arr['log_status']=1;
                $arr['uid']=$user_info['uid']??0;
                $arr['log_bind_id']=$user_info['pigcms_id']??0;
            }elseif($msg['params']['type']=='face'){
                $user_info=$db_house_village_user_bind->where(['pigcms_id'=>$msg['params']['extInfo']['faceId'],'village_id'=>$device_info['village_id']])->find();
                $arr=[];
                $arr['log_from']=1;
                $arr['log_status']=0;
                $arr['uid']=$user_info['uid'];
                $arr['log_bind_id']=$msg['params']['extInfo']['faceId'];
            }elseif($msg['params']['type']=='qrCode'){
                $arr=[];
                $arr['log_from']=31;
                $arr['log_status']=1;
                $arr['uid']=0;
                $arr['log_bind_id']=0;
            }
            if ($device_info['floor_id']=='-1'){
                $village_info=$db_house_village->where(['village_id'=>$device_info['village_id']])->field('village_id,village_name')->find();
                $arr['log_name']=$village_info['village_name'];
            }else{
                $village_info=$db_house_village->where(['village_id'=>$device_info['village_id']])->field('village_id,village_name')->find();
                $floor = D('House_village_floor')->where(['floor_id'=>$device_info['floor_id']])->field('single_id,floor_name,floor_number')->find();
                $single = D('House_village_single')->where(['id'=>$floor['single_id']])->field('id,single_name,single_number')->find();
                $arr['log_name']=$village_info['village_name'].$single['single_name'].$floor['floor_name'];
            }
            $arr['log_business_id']=$device_info['village_id'];
            $arr['log_more_id']=$device_info['floor_id'];
            $arr['device_id']=$device_info['device_id'];
            $arr['log_detail']=$data['command'];
            $arr['log_time']=time();
            $arr['add_time']=time();
            $db_house_user_log->add($arr);
        }
        return true;
    }
    //重启反馈
    public function d6_reboot_result($data){
        return true;
    }
    //远程开门反馈
    public function d6_remoteOpen_result($data){
        return true;
    }
    //获取心跳
    public function d6_heartSync_result($heartSync_info){
        $db_face_d6_device = M('Face_d6_device');
        $db_house_face_device = M('House_face_device');
        $db_house_maintenan = D('House_maintenan');
        $device_info=$db_face_d6_device->where(['device_sn'=>$heartSync_info['device_sn']])->find();
        if (empty($device_info)){
            $arr=[];
            $arr['device_sn']=$heartSync_info['device_sn'];
            $arr['status']=0;
            $arr['device_ip']=$heartSync_info['device_ip'];
            $arr['add_time']=time();
            $db_face_d6_device->add($arr);
        }
        $face_device_info=$db_house_face_device->where(['device_sn'=>$heartSync_info['device_sn']])->find();
        if (!empty($face_device_info)){
            $nowtime=time();
            $time=$nowtime-$this->time;
            if ($heartSync_info['add_time']<$time&&$face_device_info['device_status']==1){
                $db_house_face_device->where(['device_sn'=>$heartSync_info['device_sn']])->data(['device_status'=>2])->save();
                //添加故障信息
                $db_house_maintenan->faultLog(1,$face_device_info['device_id'],'设备离线',false);
            }elseif($face_device_info['device_status']==2&&$heartSync_info['add_time']>$time){
                $db_house_face_device->where(['device_sn'=>$heartSync_info['device_sn']])->data(['device_status'=>1])->save();
               //故障解除
                $db_house_maintenan->normalLog(1,$face_device_info['device_id']);
            }
        }
        return true;
    }
}