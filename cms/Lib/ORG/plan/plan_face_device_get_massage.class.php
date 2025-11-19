<?php
class plan_face_device_get_massage extends plan_base{
    public function runTask(){
        // 处理下如果符合条件跑下获取设备状态
        $d_house_face_device = M('House_face_device');
        $whereDevices = [
            'device_type' => ['in', [5,6]],
            'is_del' => 0
        ];
        $deviceA5Count = $d_house_face_device->where($whereDevices)->count();
        if (!$deviceA5Count) {
            // 不存在A5设备后续不执行
            return true;
        }
        $a5Service = D('Face_door_a5_service');
        $a5Service->getChannelMsg();
        $this->keepThread();
        $now_time = time();
        $config_data = D('Config_data')->get_config_data();
        $getFaceDeviceStatusTime = ($config_data && $config_data['GetFaceDeviceStatusTime'])?$config_data['GetFaceDeviceStatusTime']:0;
        // 默认15分钟更新一次 900秒  配置项单位分钟
        $a5GetDeviceStatusInterval = C('config.a5GetDeviceStatusInterval')?C('config.a5GetDeviceStatusInterval'):0;
        if ($a5GetDeviceStatusInterval>0) {
            $a5GetDeviceStatusInterval = intval($a5GetDeviceStatusInterval)*60;
        } elseif (!$a5GetDeviceStatusInterval || $a5GetDeviceStatusInterval==0) {
            $a5GetDeviceStatusInterval = 900;
        }
        if ($a5GetDeviceStatusInterval<=0) {
            // 小于0的值默认不执行
            return true;
        }
        $judgeTimes = intval($getFaceDeviceStatusTime) + intval($a5GetDeviceStatusInterval);
        if ($judgeTimes<=intval($now_time)) {
            // 如果记录时间加上间隔时间小于当前时间 执行获取设备状态计划任务
            $arr= array();
            $param = array();
            $param['type'] = 'a5GetDeviceStatus';
            $param['time'] = $now_time;
            $arr['param'] = serialize($param);
            $arr['plan_time'] = $now_time;
            $arr['space_time'] = 0;
            $arr['add_time'] = $now_time;
            $arr['file'] = 'sub_house_village_face_device';
            $arr['time_type'] = 1;
            $arr['unique_id'] = 'a5GetDeviceStatus';
            $arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
            M('Process_sub_plan')->data($arr)->add();
            $this->keepThread();
            // 如果未记录或者记录时间小区当前时间
            D('Config_data')->save_config_data('GetFaceDeviceStatusTime',$now_time);
        }
        $this->keepThread();
        // 跑下充电桩
        $id = M('Process_sub_plan')->where(['file'=>'sub_pile_notice'])->getField('id');
        $otherTimeId =  M('Process_sub_plan')->where(['file'=>'sub_pile_notice','plan_time' => ['lt', $now_time+3]])->getField('id');
        if (!$id || $otherTimeId) {
            $arr = [];
            $unique_id = "pile_notice_timer";
            $arr['plan_time'] = -$now_time;
            $arr['space_time'] = 0;
            $arr['add_time'] = $now_time;
            $arr['file'] = 'sub_pile_notice';
            $arr['time_type'] = 1;
            $arr['unique_id'] = $unique_id;
            $addId = M('Process_sub_plan')->data($arr)->add();
            if ($addId && $otherTimeId) {
                M('Process_sub_plan')->where(['id'=>$otherTimeId])->delete();
            }
        }
        return true;
    }
}
