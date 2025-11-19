<?php
/**
 * a7人脸门禁相关业务
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2020/9/5 15:03
 */
class Face_door_a7_serviceModel extends Model{

    /**
     * Notes: 添加编辑设备的时候相关同步
     * @param $device_id
     * @param array $data
     * @return bool
     * @author: wanzy
     * @date_time: 2020/9/5 16:16
     */
    public function face_a7_door_add($device_id, $data = []) {
        // 同步下设备的 人脸比对确性分数
        $db_face_a7_request_record = D('Face_a7_request_record');
        // 设置设备支持刷卡
        $db_face_a7_request_record->add_set_readwg_way($device_id, $data);
        // 设置人脸比对确性分数
        $db_face_a7_request_record->add_set_match_score_record($device_id, $data);
        // 设置是否开启活体检测
        $db_face_a7_request_record->add_set_spoofing_record($device_id, $data);
        // 设置是否开启安全帽检测
        $db_face_a7_request_record->add_set_attr_record($device_id, $data);
        return true;
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
    public function face_a7_reboot_cam($device_id,$village_id=0, $device_info=[]) {
        $db_face_a7_request_record = D('Face_a7_request_record');
        // 设置是否开启活体检测
        $db_face_a7_request_record->add_reboot_cam_record($device_id, [], $village_id, $device_info);
        return true;
    }

    /**
     * Notes: 一键同步已有信息
     * @param $device_id
     * @param array $device_info
     * @return bool
     * @author: wanzy
     * @date_time: 2020/9/8 9:24
     */
    public function face_a7_all_sync_per($device_id, $device_info=[]) {
        // 直接执行 符合设备条件的用户 进行同步
        D('Face_door_service')->face_device_check($device_id, $device_info);
        return true;
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
    public function sync_a7_face_all_per($device_id, $village_id=0, $floor_id='', $device_info=[],$is_plan=1) {
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
            'status' => 1
        ];
        if ($floor_id!=-1 && $floor_id!=0) {
            $where['_string'] = "floor_id in ({$floor_id}) OR type=4";
        }
        $db_house_village_user_bind = M('House_village_user_bind');
        $user_bind = $db_house_village_user_bind->where($where)->select();
        if ($user_bind) {
            $db_face_a7_request_record = D('Face_a7_request_record');
            $db_face_door_service = D('Face_door_service');
            foreach ($user_bind as $val) {
                if ($is_plan) {
                    $param = array();
                    $param['type'] = 'sync_a7_face_single_per';
                    $param['device_id'] = $device_id;
                    $param['village_id'] = $village_id;
                    $param['pigcms_id'] = $val['pigcms_id'];
                    $param['bind_info'] = [];
                    $param['device_info'] = $device_info;
                    $param['unique_id'] = 'sync_a7_face_single_per_'.$param['village_id'].'_'.$device_id.'_'.$val['pigcms_id'];
                    $db_face_door_service->add_plan($param, 'sub_house_village_face_device',-10);
                } else {
                    $db_face_a7_request_record->add_create_face_record($device_id, $val['pigcms_id'], $village_id, $val, [], $device_info);
                }
            }
        }
        return true;
    }

    // 循环处理步人员
    public function a7_arr_person_msg($device_id,$bind_arr_id,$orderGroupId='') {
        fdump_api([$bind_arr_id,$device_id],'plan_sub/noticeSubUserToFaceDevice',1);
        if ($bind_arr_id) {
            foreach ($bind_arr_id as $item) {
                $this->sync_a7_face_per($device_id, $item,0,[],$orderGroupId);
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
    public function sync_a7_face_per($device_id, $pigcms_id,$village_id=0, $device_info=[],$orderGroupId='') {
        if (empty($device_info)) {
            $device_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        }
        fdump_api([$device_id,$pigcms_id,$village_id,$device_info],'plan_sub/noticeSubUserToFaceDevice',1);
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
//            $db_face_a7_request_record = D('Face_a7_request_record');
////            $data = [
////                'operator_type' => 0,
////                'operator_id' => $pigcms_id,
////            ];

            $db_face_door_service = D('Face_door_service');
            $param = array();
            $param['type'] = 'sync_a7_face_single_per';
            $param['device_id'] = $device_id;
            $param['village_id'] = $village_id;
            $param['pigcms_id'] = $pigcms_id;
            $param['bind_info'] = [];
            $param['device_info'] = $device_info;
            if ($orderGroupId) {
                $param['orderGroupId'] = $orderGroupId;
            }
            $param['unique_id'] = 'sync_a7_face_single_per_'.$param['village_id'].'_'.$device_id.'_'.$pigcms_id;
            $addId = $db_face_door_service->add_plan($param, 'sub_house_village_face_device',-10);
            fdump_api([$addId,$param],'sync_a7_face_per',1);
//            $db_face_a7_request_record->add_create_face_record($device_id, $pigcms_id, $village_id, $user_bind, $data, $device_info);
        }
        return true;
    }

    /**
     * Notes: 同步删除小区绑定用户信息
     * @param $device_id
     * @param $pigcms_id
     * @param int $village_id
     * @param array $device_info
     * @return bool
     * @author: wanzy
     * @date_time: 2020/9/8 15:14
     */
    public function sync_delete_face_per($device_id, $pigcms_id,$village_id=0, $device_info=[]) {
        if (empty($device_info)) {
            $device_info = M('House_face_device')->where(array('device_id' => $device_id))->find();
        }
        if (!$pigcms_id) {
            return false;
        }
        if (!$village_id && $device_info['village_id']) {
            $village_id = $device_info['village_id'];
        }
        $db_face_a7_request_record = D('Face_a7_request_record');
        $db_face_a7_request_record->add_delete_face_record($device_id, $pigcms_id, $village_id, [], $device_info);
        return true;
    }

    /**
     * Notes: 获取设备是否连接外网
     * @param $device_id
     * @param int $village_id
     * @param array $device_info
     * @return bool
     * @author: wanzy
     * @date_time: 2020/9/8 15:16
     */
    public function get_dev_network_record($device_id,$village_id=0, $device_info=[]) {
        $db_face_a7_request_record = D('Face_a7_request_record');
        $db_face_a7_request_record->add_get_dev_network_record($device_id, [], $village_id, $device_info);
        return true;
    }

    /**
     * Notes: 设备远程开门-IO设备控制
     * @param $device_id
     * @param int $village_id
     * @param array $data 需要添加的相关数据
     * @param array $device_info
     * @param int $pigcms_id 操作用户小区绑定id
     * @return bool
     * @author: wanzy
     * @date_time: 2020/9/8 15:45
     */
    public function face_a7_open_door_record($device_id,$pigcms_id=0,$data=[],$village_id=0, $device_info=[]) {
        $db_face_a7_request_record = D('Face_a7_request_record');
        $db_face_a7_request_record->add_open_door_record($device_id,$pigcms_id, $data, $village_id, $device_info);
        return true;
    }

    /**
     * Notes: 获取Http推送配置
     * @param $device_id
     * @param int $village_id
     * @param array $device_info
     * @return bool
     * @author: wanzy
     * @date_time: 2020/9/9 15:55
     */
    public function face_a7_get_http_cfg($device_id,$village_id=0, $device_info=[]) {
        $db_face_a7_request_record = D('Face_a7_request_record');
        $db_face_a7_request_record->add_get_http_cfg($device_id, [], $village_id, $device_info);
        return true;
    }

    public function add_send_rs485($device_id,$data=[],$village_id=0,$device_info=[]) {
        $db_face_a7_request_record = D('Face_a7_request_record');
        $db_face_a7_request_record->add_send_rs485($device_id,$data,$village_id,$device_info);
        return true;
    }
}