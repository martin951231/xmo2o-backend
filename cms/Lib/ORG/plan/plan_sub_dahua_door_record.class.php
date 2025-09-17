<?php


class plan_sub_dahua_door_record extends plan_base
{
    public function runTask()
    {
        $arr = [];
        $time = time();
        $unique_id = "dahua_door_record".$time.rand(1,9999);
        $arr['plan_time'] = time()+1800;
        $arr['space_time'] = 0;
        $arr['add_time'] = time();
        $arr['file'] = 'sub_dahua_door_record';
        $arr['time_type'] = 1;
        $arr['unique_id'] = $unique_id;
        $task_id = M('Process_sub_plan')->data($arr)->add();
        $dahua_service = D('Dahua_service');
        $res = $dahua_service->swing_card_record(1,500);//获取开门记录
        fdump_api(['door_record' => $res,'line' => __LINE__],'dahua/swing_card_record',1);
        if($res['status'] == 1){
            $door_record_list = $res['data']['pageData'];
            if($door_record_list){
                foreach ($door_record_list as $v){
                    fdump_api(['msg' => '获取开门记录失败','v' => $v],'dahua/swing_card_record',1);
                    $device_info = M('House_face_device')->where(['device_name'=>$v['deviceName'],'is_del'=>0])->field('device_id,village_id,floor_id')->find();
                    if(empty($device_info)){
                        fdump_api(['msg' => '设备没有'],'dahua/swing_card_record',1);
                        continue;
                    }
                    if(M('House_user_log')->where(['log_time'=>strtotime($v['swingTime']),'device_id'=>$device_info['device_id']])->find()){
                        fdump_api(['msg' => '记录已有'],'dahua/swing_card_record',1);
                        continue;
                    }
                    $village_info = M('House_village')->where(['village_id'=>$device_info['village_id']])->field('village_name')->find();
                    $log_name = $village_info['village_name'].$v['deviceName'];
                    $log_business_id = $device_info['village_id'];
                    $log_more_id = $device_info['floor_id'];
                    $device_id = $device_info['device_id'];
                    if($v['openResult'])
                        $log_status = 0;
                    else
                        $log_status = 1;
                    $log_time = strtotime($v['swingTime']);
                    $eventId = $v['id'];
                    $syn_third_status = 2;
                    $syn_third_time= $log_time;
                    if(empty($v['cardNumber'])){
                        /*$uid = 0;
                        $log_bind_id = 0;
                        $log_from = 2;*/
                        continue;
                    }else{
                        if($v['openType'] == 61 || $v['openType'] == 62) {
                            $log_from = 1;
                        } else {
                            $log_from = 2;
                        }
                        $detail['face_img'] = $v['recordImage'];
                        if (isset($v['personName'])&&$v['personName']) {
                            $detail['personName'] = $v['personName'];
                        }
                        if (isset($v['cardNumber'])&&$v['cardNumber']) {
                            $detail['cardNumber'] = $v['cardNumber'];
                        }
                        if (isset($v['personCode'])&&$v['personCode']) {
                            $detail['personCode'] = $v['personCode'];
                        }
                        if (isset($v['paperNumber'])&&$v['paperNumber']) {
                            $detail['paperNumber'] = $v['paperNumber'];
                        }
                        if (isset($v['personId'])&&$v['personId']) {
                            $detail['personId'] = $v['personId'];
                        }
                        $detail['face_img'] = $v['recordImage'];
                        $face_user_bind_device = M('Face_user_bind_device')->where(['cardid'=>$v['cardNumber']])->field('bind_id')->find();
                        if($face_user_bind_device){
                            $log_bind_id = $face_user_bind_device['bind_id'];
                            $uid = M('House_village_user_bind')->where(['pigcms_id'=>$log_bind_id])->getField('uid');
                        }else{
                            $uid = 0;
                            $log_bind_id = 0;
                        }
                    }
                    $dataLog = [
                        'uid'=>$uid,
                        'log_bind_id'=>$log_bind_id,
                        'log_name'=>$log_name,
                        'log_from'=>$log_from,
                        'log_business_id'=>$log_business_id,
                        'log_more_id'=>$log_more_id,
                        'device_id'=>$device_id,
                        'log_status'=>$log_status,
                        'log_time'=>$log_time,
                        'eventId'=>$eventId,
                        'syn_third_status'=>$syn_third_status,
                        'syn_third_time'=>$syn_third_time,
                        'log_detail' => serialize($detail)
                    ];
                    $dataLog['add_time']=time();
                    $logId = M('House_user_log')->data($dataLog)->add();
                    if (!$logId) {
                        fdump_api(['msg' => '获取开门记录失败','logId' => $logId,'dataLog' => $dataLog],'dahua/reorderErrorMessage',1);
                    }
                }
            }
        }else{
            fdump_api(['msg' => '获取开门记录失败','door_record' => $res,'line' => __LINE__],'dahua/reorderErrorMessage',1);
        }
        return true;
    }
}