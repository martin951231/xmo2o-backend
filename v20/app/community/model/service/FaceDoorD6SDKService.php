<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2022/1/10 11:33
 */
namespace app\community\model\service;

use app\common\model\service\image\ImageService;
use app\community\model\db\FaceD6RequestRecord;
use app\community\model\db\FaceDoorD6Qrcode;
use app\community\model\db\HouseFaceDevice;
use tools;

require_once dirname(dirname(dirname(dirname(__DIR__)))).'/extend/phpqrcode/phpqrcode.php';


class FaceDoorD6SDKService
{
    public $key='dnakeiot';

    //生成住户和访客二维码
    public function addQrcode($data){
        $string=$data['device_sn'].'_'.$data['single_num'].'-'.$data['floor_num'].'-'.$data['layer_num'].'-'.$data['room_num'].'_'.$data['start_time'].'_'.$data['end_time'];
       //  print_r($string);exit;
        $Des = new tools\Des();
        //生成二维码的加密字符串
        $qrcode_key=  $Des->encrypt($string,$this->key);
        $res=$this->setQrcode($qrcode_key,$data);
        return $res;
    }
    //生成物业工作人员开门二维码
    public function addWorkerQrcode($data){
        $string=$data['managerId'];
        $Des = new tools\Des();
        //生成二维码的加密字符串
        $qrcode_key=  $Des->encrypt($string,$this->key);
        $res=$this->setQrcode($qrcode_key,$data);
        return $res;
    }

    public function setQrcode($qrcode_key,$data){
        if (!isset($data['device_sn'])||empty($data['device_sn'])){
            if (isset($data['managerId'])&&!empty($data['managerId'])){
                $data['device_sn']=$data['managerId'];
            }else{
                $data['device_sn']=$data['pigcms_id'];
            }
        }
        if (strlen($data['device_sn'])>200){
            $path=substr($data['device_sn'],0,200);
        }else{
            $path=$data['device_sn'];
        }
        // 创建目录
        $filename =rtrim($_SERVER['DOCUMENT_ROOT'],'/').'/upload/houseface/'.date('Ymd').'/qrcode_'.$path.'_'.time().'.png';
        $dirName = dirname($filename);
        if(!file_exists($dirName)){
            mkdir($dirName,0777,true);
        }
        if(!file_exists($filename)){
            $QRcode = new \QRcode();
            $errorCorrectionLevel = 'L';  //容错级别
            $matrixPointSize = 5;      //生成图片大小
            $QRcode::png('iot:'.$qrcode_key,$filename,$errorCorrectionLevel,$matrixPointSize,2);
        }
        $filename_logo=replace_file_domain(cfg('site_logo'));
        if ($filename_logo !== FALSE) {
            $filename = imagecreatefromstring ( file_get_contents ($filename) );
            $filename_logo = imagecreatefromstring ( file_get_contents ($filename_logo) );
            $QR_width = imagesx ( $filename );
            $QR_height = imagesy ( $filename );
            $logo_width = imagesx ( $filename_logo );
            $logo_height = imagesy ( $filename_logo );
            $logo_qr_width = $QR_width / 5;
            $scale = $logo_width / $logo_qr_width;
            $logo_qr_height = $logo_height / $scale;
            $from_width = ($QR_width - $logo_qr_width) / 2;
            imagecopyresampled ( $filename, $filename_logo, $from_width, $from_width, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height );
        }
        $filename_logo1 = '/upload/houseface/'.date('Ymd').'/backgroud_'.$path.'_'.time().'.png';
        imagepng ($filename, rtrim($_SERVER['DOCUMENT_ROOT'],'/').$filename_logo1 );//带Logo二维码的文件名
        if($filename_logo1){
            $returnArr['share_image'] = cfg('site_url').$filename_logo1;
        }else{
            $returnArr['share_image'] = '';
        }
        return $returnArr;
    }

    //下发物业标识配置权限
    public function syncQrManager($data){
        $db_face_d6_request_record=new FaceD6RequestRecord();
        //查询设备
        $condition_door['village_id'] = $data['village_id'];
        $condition_door['is_del'] = 0;
        $faceDoorField = 'device_id,device_name,device_type,device_sn,floor_id,public_area_id';
        $db_house_face_device=new HouseFaceDevice();
        $aDoorList = $db_house_face_device->getList($condition_door,$faceDoorField,1);
        // 存不存在A1
        $device_sn_arr=[];
        if (!empty($aDoorList)){
            $aDoorList=$aDoorList->toArray();
            //  print_r($aDoorList);exit;
            if (!empty($aDoorList)){
                foreach ($aDoorList as $kk => &$vv) {
                        //当前只有D6支持二维码开门
                        if(in_array($vv['device_type'],[26])){
                            $param[] = [
                                "type"=> $data['type'],
                                "managerId"=>$data['managerId'],
                            ];
                            $command=[
                                'cmd' => 'syncQrManager',
                                'device_sn'=>$vv['device_sn'],
                                'params'=>$param
                            ];
                            // 整合需要添加的数据
                            $add_data = [
                                'village_id' => $data['village_id'],
                                'device_sn' => $vv['device_sn'],
                                'device_id' => $vv['device_id'],
                                'bind_id' => $data['pigcms_id'],
                                'operator_type' => 0,
                                'operator_id' => $data['pigcms_id'],
                                'cmd_name' => 'syncQrManager',
                                'status' => 0,
                                'command' => serialize($command),
                                'add_time' => time(),
                                'type' => 1
                            ];
                            $add_id = $db_face_d6_request_record->addFind($add_data);
                            if ($add_id>0){
                                $device_sn_arr[]=$add_id;
                            }
                        }
                }
            }
        }
        $ret_data['data'] = $device_sn_arr;
        return $ret_data;

    }
}