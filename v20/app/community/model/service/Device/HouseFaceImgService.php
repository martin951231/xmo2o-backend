<?php

declare(strict_types=1);
/**
 * This file is part of Kuaijing Bailing.
 *
 * @link     https://www.kuaijingai.com
 * @document https://help.kuaijingai.com
 * @contact  www.kuaijingai.com 7*12 9:00-21:00
 */

namespace app\community\model\service\Device;

use app\common\model\db\ProcessSubPlan;
use app\community\model\db\HouseFaceImg;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\service\FaceDeviceService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\ManageAppLoginService;
use app\job\FaceDeviceHikCloudJob;
use face\wordencryption;
use think\facade\Queue;

class HouseFaceImgService
{
    /**
     * 同步人员触发盒子.
     */
    public function commonUserToBox($uid, $pigcms_id = 0, $otherParam = [], $synOut = true) {
        try {
            $this->commonUserToDevice($uid, $pigcms_id, $otherParam, $synOut);
        } catch (\Exception $e){
            fdump_api($e->getMessage(),'$commonUserToBox');
        }
        return true;
    }
    
    public function commonUserToDevice($uid, $pigcms_id = 0, $otherParam = [], $synOut = true)
    {
        $planParam = [
            'synOut' => $synOut,
        ];
        $unique_id = 'subUserToDevice';
        if ($uid) {
            $planParam['uid'] = $uid;
            $unique_id .= '_uid' . $uid;
        }
        if ($pigcms_id) {
            $planParam['pigcms_id'] = $pigcms_id;
            if (!$uid) {
                $unique_id .= '_pigcmsId' . $pigcms_id;
            }
        }
        $orderGroupId = isset($otherParam['orderGroupId']) && $otherParam['orderGroupId'] ? trim($otherParam['orderGroupId']) : md5(uniqid() . $_SERVER['REQUEST_TIME']);// 标记统一执行命令
        if ($orderGroupId) {
            $planParam['orderGroupId'] = $orderGroupId;
        }
        $now_time = time();
        $arr = [];
        $arr['param'] = serialize($planParam);
        $arr['plan_time'] = -9999;
        // 优先级暂定为高
        $arr['space_time'] = 0;
        $arr['add_time'] = $now_time;
        $arr['file'] = 'sub_user_to_face_device';
        $arr['time_type'] = 1;
        $arr['unique_id'] = $unique_id;
        $sub_process_num = cfg('sub_process_num');
        $sub_process_num=intval($sub_process_num);
        if ($sub_process_num<1) {
            $sub_process_num = 1;
        }
        $maxArr=array($sub_process_num,3);
        $maxV=max($maxArr);
        $arr['rand_number'] = mt_rand(1,$maxV);
        (new ProcessSubPlan())->add($arr);
        $house_a5_client_id = cfg('house_a5_client_id');
        $HikCloudClientId   = cfg('HikCloudClientId');
        if ($house_a5_client_id || $HikCloudClientId) {
            // 暂时只是支持 海康内部+大华云睿 存在配置即调用 避免无用调用
            try {
                $param=array('pigcms_id'=>$pigcms_id,'uid'=>$uid);
                $param['jobType']       = 'userSynDeviceByPigcmsId';
                $param['thirdProtocol'] =12;
                $job_id =Queue::push(FaceDeviceHikCloudJob::class, $param, 'hik-nei-bu-data-to-cloud');
                fdump_api(['param'=>$param,'job_id'=>$job_id],'00commonUserToDevice');
                //(new FaceDeviceService())->userSynDevice($pigcms_id, 0);
            } catch (\Exception $e){
                fdump_api($e->getMessage(),'$commonUserToDevice');
            }
        }
        return true;
    }
    
    /**
     * 获取人脸列表
     */
    public function getFaceList($param)
    {
        $where= [
//            ['hfi.status','in',[0,3]],
//            ['hfi.uid','>',0],
            ['ub.village_id','=',$param['village_id']],
            ['ub.status','=',1],
            ['ub.type','in',[0,1,2,3]],
        ];
        if($param['name']){
            $where[] = ['ub.name','like','%'.$param['name'].'%'];
        }
        if($param['phone']){
            $where[] = ['ub.phone','=',$param['phone']];
        }
//        $whereRaw = ' LENGTH(hfi.img_url) > 0';
        $field = 'ub.pigcms_id,ub.usernum,ub.name,ub.phone,ub.type,ub.relatives_type,ub.add_face_time,ub.face_img_status,ub.face_img_reason,single_id,floor_id,layer_id,vacancy_id,address,ub.uid';
        $order = 'ub.pigcms_id desc';
        $list = (new HouseVillageUserBind())->getFaceList($where,$field,$order,$param['page_size']);
        $houseVillageService = new HouseVillageService();
        $wordencryption=new wordencryption();
        $manageAppLoginService=new ManageAppLoginService();
        $houseFaceImg=new HouseFaceImg();
        foreach ($list['data'] as &$v){
            $v['bind_type'] = $manageAppLoginService->user_bind_type_arr[$v['type']] ?? '';
            if($v['type']==1){
                $v['bind_type'] = $manageAppLoginService->user_bind_relatives_type_arr[$v['relatives_type']] ?? '';
            }
            $v['add_face_time'] = $v['add_face_time'] ? date('Y-m-d H:i:s',$v['add_face_time']) : '';
            $v['img_url']='';
            $v['add_time']='';
            //查询最新的图片
            $newImg = $houseFaceImg->where([
                ['status','in',[0,3]],
                ['bind_pigcms_id','=',$v['pigcms_id']],
            ])->whereRaw('LENGTH(img_url) > 0')->order('id desc')->field('status,img_url,add_time')->find();
            if((empty($newImg) || $newImg->isEmpty()) && $v['uid']>0){
                $newImg = $houseFaceImg->where([
                    ['status','in',[0,3]],
                    ['uid','=',$v['uid']],
                ])->whereRaw('LENGTH(img_url) > 0')->order('id desc')->field('status,img_url,add_time')->find();
            }
            if(!empty($newImg) && !$newImg->isEmpty()){
                if (3==$newImg['status']) {
                    // 加密的图片解密
                    $newImg['img_url'] = $wordencryption->text_decrypt($newImg['img_url']);
                    $v['img_url'] = $newImg['img_url'] ? replace_file_domain($newImg['img_url']) : '';
                }
                $v['add_time'] = $newImg['add_time'] ?? '';
                $v['add_time'] = $v['add_time'] ? date('Y-m-d H:i:s',$v['add_time']) : '';
            }

            $address = $houseVillageService->getSingleFloorRoom($v['single_id'],$v['floor_id'],$v['layer_id'],$v['vacancy_id'],$param['village_id']);
            
            $v['address'] = !empty($address) ? $address : $v['address'];
            if(!$v['add_face_time'] && !$v['img_url']){
                $v['face_img_reason'] = $v['face_img_reason_short'] = '';
            }else{
                $v['face_img_reason'] = $v['face_img_status']==2 ? $v['face_img_reason'] : '';
                $v['face_img_reason_short'] = mb_strlen($v['face_img_reason']) > 10 ? (mb_substr($v['face_img_reason'],0,10).'...') : $v['face_img_reason'];
            }
            //unset($v['single_id'],$v['floor_id'],$v['layer_id'],$v['vacancy_id'],$v['type'],$v['relatives_type'],$v['uid']);
        }
        $list['role_add_face'] = 1;
        $list['role_show_log'] = 1;
        $list['role_sync'] = 1;
        $list['role_sync_all'] = 1;
        return $list;
    }
}