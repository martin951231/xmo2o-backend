<?php


class plan_sub_monitor_change_video extends plan_base
{
    public function runTask($param){
        //删除计划任务
        if($param['plan_task_id']){
            $this->dropPlan($param['plan_task_id']);
        }
        $where = [];
        if (isset($param['type'])&&$param['type']=='daHuaRtmpChangeAll') {
            $sort = '`rtmpStatus` ASC,`logId` ASC, `camera_id` ASC';
            if (isset($param['property_id'])&&$param['property_id']) {
                $where['property_id'] = $param['property_id'];
            }
            if (isset($param['village_id'])&&$param['village_id']) {
                $where['village_id'] = $param['village_id'];
            }
            $where['status'] = ['neq',4];
            $field = "camera_id,village_id,property_id";
            $list = D('house_village_monitor')->field($field)->where($where)->order($sort)->select();
            if (!empty($list)) {
                $param = array();
                $arr= array();
                $now_time = time();
                foreach ($list as $item) {
                    $this->keepThread();
                    $param['type'] = 'daHuaRtmpChangeSingle';
                    $unique_id = 'daHuaRtmpChangeSingle';
                    $param['property_id'] = $item['property_id'];
                    $param['village_id'] = $item['village_id'];
                    $param['camera_id'] = $item['camera_id'];
                    if (isset($param['camera_id'])) {
                        $unique_id .='_'.$param['camera_id'];
                    }
                    if (isset($param['village_id'])) {
                        $unique_id .='_'.$param['village_id'];
                    }
                    if (isset($param['property_id'])) {
                        $unique_id .='_'.$param['property_id'];
                    }
                    $param['unique_id'] = $unique_id;
                    $param['time'] = $now_time;
                    $arr['param'] = serialize($param);
                    $arr['plan_time'] = 0;
                    $arr['space_time'] = 0;
                    $arr['add_time'] = $now_time;
                    $arr['file'] = 'sub_monitor_change_video';
                    $arr['time_type'] = 1;
                    $arr['unique_id'] = $unique_id;
                    $arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
                    $add_id = M('process_sub_plan')->data($arr)->add();
                    if (!$add_id) {
                        fdump_api(['大华摄像头添加计划任务'.__LINE__,$add_id, $arr, M('process_sub_plan')->_sql(),M('process_sub_plan')->getDbError()],'plan_sub/sub_monitor_change_video',1);
                    }
                }
            }
        } elseif (isset($param['type'])&&$param['type']=='daHuaRtmpChangeSingle'&&isset($param['camera_id']) && $param['camera_id']) {
            $camera_id = trim($param['camera_id']);
            $where['camera_id'] = $camera_id;
            $cameraInfo = D('house_village_monitor')->where($where)->find();
            if (!$cameraInfo || 4==$cameraInfo['status']) {
                fdump_api(['大华摄像头状态异常'.__LINE__,$cameraInfo,$param],'plan_sub/sub_monitor_change_video',1);
                return true;
            }
            import('ORG.Net.Http');
            $cameraChangeUrl = C('config.cameraChangeUrl')?C('config.cameraChangeUrl'):'http://192.168.0.241';
            $rt = Http::curlGet("{$cameraChangeUrl}/dahua/index.php?village_id=50&camera_id=$camera_id",15);
            if (!$rt) {
                fdump_api(['大华摄像头转换'.__LINE__,$rt],'plan_sub/sub_monitor_change_video',1);
            }
        }
        return true;
    }
}