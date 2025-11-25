<?php
/**
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/6/16 15:23
 */

class plan_d1_door_open_record extends plan_base{
    public function runTask($param=[]){
        //删除计划任务
        if($param['plan_task_id']){
            $this->dropPlan($param['plan_task_id']);
        }
        $db_house_face_device = M('House_face_device');
        $where_d1 = [
            'device_type' => 21,
            'is_del' => 0,
        ];

        $face_list = $db_house_face_device->where($where_d1)->select();

        if (!empty($face_list)) {
            // 定制计划任务跑获取开门记录
            $db_face_bind_about_info = M('Face_bind_about_info');
            $village_arr = [];
            $now_time = time();
            foreach ($face_list as $item) {
                $this->keepThread();
                if (isset($village_arr[$item['village_id']]) && $village_arr[$item['village_id']]) {
                    $project_uid = $village_arr[$item['village_id']];
                } else {
                    $where_bind = [
                        'bind_type' => 0,
                        'bind_id' => $item['village_id'],
                        'device_id' => 0,
                        'device_type' => 21,
                    ];
                    $project_uid = $db_face_bind_about_info->where($where_bind)->order('id ASC')->getField('third_id');
                    if ($project_uid) {
                        $village_arr[$item['village_id']] = $project_uid;
                    }
                }
                if ($project_uid) {
                    $param = array();
                    $arr= array();
                    $param['type'] = 'd1_open_door_list';
                    $unique_id = 'd1_open_door_list';
                    $param['device_id'] = $item['device_id'];
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
            }
        }
        return true;
    }
}