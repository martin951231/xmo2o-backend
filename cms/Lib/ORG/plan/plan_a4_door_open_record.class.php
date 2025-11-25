<?php
/**
 * A4获取开门记录
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/6/16 15:23
 */

class plan_a4_door_open_record extends plan_base{
    public function runTask($param=[]){
        if (!empty(C('DISABLE_OLD_ALL_DB_PLAN')) && C('DISABLE_OLD_ALL_DB_PLAN') == true){
            return true;
        }
        //删除计划任务
        if($param['plan_task_id']){
            $this->dropPlan($param['plan_task_id']);
        }
        $db_house_face_device = M('House_face_device');
        $where_a4 = [
            'device_type' => 4,
            'is_del' => 0,
        ];

        $face_list = $db_house_face_device->where($where_a4)->group('village_id')->select();

        $now_time = time();
        if (!empty($face_list)) {
            // 定制计划任务跑获取开门记录
            $db_house_user_log = M('House_user_log');
            $log_time_min = $now_time - 1800;
            foreach ($face_list as $item) {
                $this->keepThread();
                $village_id = $item['village_id'];
                // todo 过滤蓝牙开门记录
                $house_user_log = $db_house_user_log->where([
                    'log_business_id'=>$village_id,
                    'device_id'=>$item['device_id'],
                    'log_from'=> 1
                ])->field('MAX(log_time) as log_time_max')->find();
                if ($house_user_log['log_time_max']) {
                    $log_time_max = $house_user_log['log_time_max'];
                } else {
                    $log_time_max = 0;
                }
                if (!$log_time_max) {
                    $log_time_max = $log_time_min;
                } elseif ($log_time_max>$log_time_min) {
                    $log_time_max = $log_time_min;
                }
                if ($village_id) {
                    $param = array();
                    $arr= array();
                    $param['type'] = 'searchUnlockRecordCount';
                    $unique_id = 'searchUnlockRecordCount';
                    $param['village_id'] = $item['village_id'];
                    $param['log_time_max'] = $log_time_max;
                    $param['device_info'] = [];
                    if (isset($param['village_id'])) {
                        $unique_id .='_'.$param['village_id'];
                    }
                    $param['unique_id'] = $unique_id;
                    $param['time'] = $now_time;
                    $arr['param'] = serialize($param);
                    $arr['plan_time'] = 0;
                    $arr['space_time'] = 0;
                    $arr['add_time'] = $now_time;
                    $arr['file'] = 'sub_house_village_face_device';
                    $arr['time_type'] = 1;
                    $arr['unique_id'] = $unique_id;
                    $arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
                    $add_id = M('process_sub_plan')->data($arr)->add();
                    if (!$add_id) {
                        fdump_api(['添加计划任务'.__LINE__,$add_id, $arr, M('process_sub_plan')->_sql(),M('process_sub_plan')->getDbError()],'A4/plan_a4_door_open_record_err_log',1);
                    }
                }
            }
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