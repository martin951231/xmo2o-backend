<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2022/1/6 14:54
 */

class House_village_face_door_D6_command extends Model
{
    public $fase_max=10;
    public $managere_max=10;
    public function set_download($data1){
       switch ($data1['cmd']){
           case 'deviceSet'://设备楼栋单元号设置
               if (!empty($data1['build'])){
                   $param['build']=$data1['build'];//楼栋编号
               }else{
                   return 0;
               }
               if (!empty($data1['unit'])){
                   $param['unit']=$data1['unit'];//单元编号
               }else{
                   return 0;
               }
               if (!empty($data1['number'])){
                   $param['number']=$data1['number'];//设备编号
               }else{
                   return 0;
               }
               break;
           case 'reboot'://设备重启
               $param='';
               break;
           case 'syncFace'://人脸注册
               if (!empty($data1['face_list'])){
                   if (count($data1['face_list'])>$this->fase_max){
                       return 0;
                   }else{
                       foreach ($data1['face_list'] as $k=>$value){
                           if (empty($value['id'])){
                               unset($data1['face_list'][$k]);
                               continue;
                           }
                           if (empty($value['faceUrl'])){
                               unset($data1['face_list'][$k]);
                               continue;
                           }
                       }
                   }
                   $param=$data1['face_list'];
               }else{
                   return 0;
               }
               break;
           case 'syncQrManager'://下发物业标识配置权限
               if (!empty($data1['managere_list'])) {
                   if (count($data1['managere_list']) > $this->managere_max) {
                       return 0;
                   } else {
                       foreach ($data1['managere_list'] as $k => $value) {
                           if (empty($value['type'])) {
                               unset($data1['managere_list'][$k]);
                               continue;
                           }
                           if (empty($value['managerId'])) {
                               unset($data1['managere_list'][$k]);
                               continue;
                           }
                       }
                       $param=$data1['managere_list'];
                   }
               }
               break;
           case 'syncCard'://传统卡权限配置
               if (!empty($data1['syncCard_list'])) {
                   if (count($data1['syncCard_list']) > $this->managere_max) {
                       return 0;
                   } else {
                       foreach ($data1['syncCard_list'] as $k => $value) {
                           if (empty($value['card'])) {
                               unset($data1['syncCard_list'][$k]);
                               continue;
                           }
                           if (empty($value['build'])) {
                               unset($data1['syncCard_list'][$k]);
                               continue;
                           }
                           if (empty($value['unit'])) {
                               unset($data1['syncCard_list'][$k]);
                               continue;
                           }
                           if (empty($value['floor'])) {
                               unset($data1['syncCard_list'][$k]);
                               continue;
                           }
                           if (empty($value['family'])) {
                               unset($data1['syncCard_list'][$k]);
                               continue;
                           }
                           if (empty($value['tag'])) {
                               unset($data1['syncCard_list'][$k]);
                               continue;
                           }
                           if (empty($value['startTime'])) {
                               unset($data1['syncCard_list'][$k]);
                               continue;
                           }
                           if (empty($value['endTime'])) {
                               unset($data1['syncCard_list'][$k]);
                               continue;
                           }
                       }
                       $param=$data1['syncCard'];
                   }
               }
               break;
           case 'faceClear'://人脸清空
               $param='';
               break;
           case 'remoteOpen'://远程开门
               $param='';
               break;
           default:
               return 0;
               break;

       }

        $data['type']=1;
        $data['status']=1;
        $data['addtime']=time();
        $data['command']=[
            'cmd' => $data1['cmd'],
            'device_id'=>$data1['device_id'],
            'params'=>$param
        ];
        $data['command']=json_encode( $data['command']);
        $res=$this->add($data);
        return $res;
    }

    public function get_upload(){


    }

}