<?php
/**
 * A4获取开门记录
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2021/6/16 15:23
 */

class plan_face_d6_request_record extends plan_base{
    public function runTask($param=[]){
        $db_face_d6_request_record = M('Face_d6_request_record');
        $where_d6 = [
            'type' => 2,
            'is_implement' => 0,
            'cmd_name' => 'heartSync',
        ];
        //处理心跳信息
        $heartSync_info = $db_face_d6_request_record->where($where_d6)->order('id DESC')->field('device_sn,cmd_name,add_time,device_ip,id')->find();
        if (!empty($heartSync_info)) {
            D('Face_d6_request_record')->d6_heartSync_result($heartSync_info);
            $db_face_d6_request_record->where(['id'=>$heartSync_info['id']])->data(['is_implement'=>1])->save();
        }
        $where_d6_list = [
            'type' => 2,
            'is_implement' => 0,
            'cmd_name'=>array('neq','heartSync')
        ];
        //处理除心跳以外的设备反馈信息
        $request_list=$db_face_d6_request_record->where($where_d6_list)->select();
        if (!empty($request_list)){
            //todo 处理设备返回的信息
            foreach ($request_list as $value){
                $db_face_d6_request_record->where(['id'=>$value['id']])->data(['is_implement'=>1])->save();
                if ($value['cmd_name']=='remoteOpen'){
                    D('Face_d6_request_record')->d6_remoteOpen_result($value);
                }elseif($value['cmd_name']=='deviceSet'){
                    D('Face_d6_request_record')->d6_deviceSet_result($value);
                }elseif($value['cmd_name']=='syncFace'){
                    D('Face_d6_request_record')->d6_syncFace_result($value);
                }elseif($value['cmd_name']=='delFace'){
                    D('Face_d6_request_record')->d6_faceClear_result($value);
                }elseif($value['cmd_name']=='passEvent'){
                    D('Face_d6_request_record')->d6_passEvent_result($value);
                }elseif($value['cmd_name']=='syncQrManager'){
                    D('Face_d6_request_record')->d6_syncQrManager_result($value);
                }elseif($value['cmd_name']=='syncCard'){
                    D('Face_d6_request_record')->d6_syncCard_result($value);
                }
            }
        }
        return true;
    }
}