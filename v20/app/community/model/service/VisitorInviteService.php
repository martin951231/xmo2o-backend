<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2022/1/7 11:12
 */
namespace app\community\model\service;

use app\community\model\db\FaceDoorD6Qrcode;
use app\community\model\db\HouseContactWayUser;
use app\community\model\db\HouseFaceDevice;
use app\community\model\db\HouseNewOrderLog;
use app\community\model\db\HouseUserLog;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageFloor;
use app\community\model\db\HouseVillageLayer;
use app\community\model\db\HouseVillagePropertyPaylist;
use app\community\model\db\HouseVillageSingle;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\HouseWorker;
use app\community\model\db\User;
use app\community\model\db\WorkMsgAuditInfo;
use app\community\model\db\WorkMsgAuditInfoGroup;
use app\community\model\db\WorkMsgAuditInfoMarkdown;
use app\community\model\db\WorkMsgAuditInfoMonitor;
use app\community\model\db\WorkMsgAuditInfoText;
use app\community\model\db\WorkMsgAuditInfoSensitive;

class VisitorInviteService
{

    public $user_type=['0'=>'房主','1'=>'家人','2'=>'租客','3'=>'更新房主','4'=>'工作人员','5'=>'访客','6'=>'出入证'];
    public $setTime=600;
    /**
     * 查询访客邀请页面基础信息
     * @author:zhubaodi
     * @date_time: 2022/1/7 16:24
     */
    public function getBaseInfo($village_id,$pigcms_id){
        $db_house_village_user_bind=new HouseVillageUserBind();
        $db_house_face_device=new HouseFaceDevice();
        $where=[
            'village_id'=>$village_id,
            'device_type'=>26,
        ];
        $device_count=$db_house_face_device->getCount($where);
        if ($device_count>0){
            $device_count=1;
        }
        $user_info=$db_house_village_user_bind->getOne(['pigcms_id'=>$pigcms_id]);
        $userInfo=[];
        if (!empty($user_info)){
            $number=$this->getAddress($user_info['vacancy_id'],$village_id);
            if (!empty($user_info['name'])){
                $userInfo['user_name']=mb_substr($user_info['name'],0,1).'**';
            }else{
                $userInfo['user_name']='';
            }
            if (!empty($user_info['phone'])&&strlen($user_info['phone'])>=7){
                $userInfo['phone']=substr_replace($user_info['phone'],'****','3','4');
            }else{
                $userInfo['phone']='';
            }
            $userInfo['address']=$number;
            $userInfo['type']=$this->user_type[$user_info['type']];
        }

        $data=[];
        $data['user_info']=$userInfo;
        $data['device_count']=$device_count;
        return $data;
    }

    /**
     * 查询二维码生成纪录
     * @author:zhubaodi
     * @date_time: 2022/1/7 16:23
     */
    public function getQrcodeList($uid,$village_id){
        $db_face_door_D6_qrcode=new FaceDoorD6Qrcode();
        $db_house_village_user_bind=new HouseVillageUserBind();
        $where=[];
        $where['uid']=$uid;
        $where['village_id']=$village_id;
        $list=$db_face_door_D6_qrcode->getList($where);
        $data=[];
        $qrcode_list=[];
        if (!empty($list)){
            $list=$list->toArray();
            if (!empty($list)){
                foreach ($list as $value){
                    $user_info=$db_house_village_user_bind->getOne(['pigcms_id'=>$value['pigcms_id']]);
                    $data['address']='';
                    if (!empty($user_info)){
                        $data['address']=$this->getAddress($user_info['vacancy_id'],$village_id);
                    }
                    $data['id']=$value['id'];
                    $start_time='';
                    $end_time='';
                    if (!empty($value['start_time'])){
                        $data['invite_date']=date('Y-m-d',$value['start_time']);
                        $start_time=date('H:i',$value['start_time']);
                    }
                    if (!empty($value['end_time'])){
                        $end_time=date('H:i',$value['end_time']);
                    }
                    $data['url']=$value['url'];
                    $data['time_arr']=$start_time.'至'.$end_time;
                    $qrcode_list[]=$data;
                  }
            }

        }
        $qrcodeList=[];
        $qrcodeList['list']=$qrcode_list;
        return $qrcodeList;
    }

    /**
     * 添加访客邀请二维码
     * @author:zhubaodi
     * @date_time: 2022/1/7 16:23
     */
    public function addQrcode($data){
        $db_face_door_D6_qrcode=new FaceDoorD6Qrcode();
        $db_house_village_user_bind=new HouseVillageUserBind();
        $user_info=$db_house_village_user_bind->getOne(['pigcms_id'=>$data['pigcms_id'],'status'=>1],'single_id,floor_id,layer_id,vacancy_id,type,parent_id,pigcms_id,uid,phone,status,property_starttime,property_endtime');
        if (empty($user_info)){
            throw new \think\Exception("用户未绑定房间");
        }
        $parent_user_info=[];
        if (in_array($user_info['type'],[1,2,5])) {
            $parent_user_info=$db_house_village_user_bind->getOne(['pigcms_id'=>$user_info['parent_id'],'status'=>1],'pigcms_id,uid,phone,status,property_starttime,property_endtime');
            if (empty($parent_user_info)){
                throw new \think\Exception("当前住户信息不正确");
            }
        }
        $isDelay = 1;
        if (!in_array($user_info['type'], [0, 1, 2, 3])) {
            $isDelay = 0;
        }
        $user_info['isDelay'] = $isDelay;
        $time_data=$this->getUserEndTime($data['pigcms_id'],$user_info,$parent_user_info);
        if (empty($time_data)||$time_data['propertyEndTime']<time()){
            throw new \think\Exception("当前住户无开门权限");
        }
        $dbHouseVillageSingle = new HouseVillageSingle(); //楼栋
        $dbHouseVillageFloor = new HouseVillageFloor();   //单元
        $dbHouseVillageLayer = new HouseVillageLayer();   //楼层
        $dbHouseVillageUserVacancy = new HouseVillageUserVacancy(); //门牌号

        if($user_info['vacancy_id'] == 0){
            $room = '';
        }else{
            $vacancy = $dbHouseVillageUserVacancy->getOne(['pigcms_id'=>$user_info['vacancy_id']],'room,room_number,layer_id');
            if (empty($vacancy['room_number'])){
                $room = $vacancy['room'];
            }else{
                $room = $vacancy['room_number'];
            }

        }
        if(empty($user_info['layer_id'])){
            if (!empty($vacancy['layer_id'])){
                $layer_info = $dbHouseVillageLayer->getOne(['id'=>$vacancy['layer_id']],'layer_name,layer_number');
                if (empty($layer_info['layer_number'])){
                    $layer = $layer_info['layer_name'];
                }else{
                    $layer = $layer_info['layer_number'];
                }
            }else{
                $layer = '';
            }
        }elseif (is_numeric($user_info['layer_id'])){
            $layer_info = $dbHouseVillageLayer->getOne(['id'=>$user_info['layer_id']],'layer_name,layer_number');
            if (empty($layer_info['layer_number'])){
                $layer = $layer_info['layer_name'];
            }else{
                $layer = $layer_info['layer_number'];
            }
        }
       //  print_r($user_info);exit;
        if($user_info['single_id'] == 0){
            $singleName = '';
        }else{
            $single = $dbHouseVillageSingle->getOne(['id'=>$user_info['single_id']],'id,single_name,single_number');
            if (empty($single['single_number'])){
                $singleName = $single['single_name'];
            }else{
                $singleName = $single['single_number'];
            }
        }
        if($user_info['floor_id'] == 0){
            $floorName = '';
        }else{
            $floor = $dbHouseVillageFloor->getOne(['floor_id'=>$user_info['floor_id']],'floor_name,floor_number');
            if (empty($floor['floor_number'])){
                $floorName = $floor['floor_name'];
            }else{
                $floorName = $floor['floor_number'];
            }
        }



        //查询设备
        $condition_door['village_id'] = $data['village_id'];
        $condition_door['is_del'] = 0;
        $faceDoorField = 'device_id,device_name,device_type,device_sn,floor_id,public_area_id';
        $db_house_face_device=new HouseFaceDevice();
        $aDoorList = $db_house_face_device->getList($condition_door,$faceDoorField,1);
        // 存不存在A1
        $is_have_a1 = false;
        $device_sn_arr=[];
        if (!empty($aDoorList)){
            $aDoorList=$aDoorList->toArray();
           //  print_r($aDoorList);exit;
            if (!empty($aDoorList)){
                foreach ($aDoorList as $kk => &$vv) {
                    if ($vv['device_type']==1 && !$is_have_a1) {
                        $is_have_a1 = true;
                    }
                    if ($vv['floor_id'] != "-1") {
                        $vv['door_type'] = 1; // 小区单元门
                        if ($vv['floor_id']==$user_info['floor_id']&&in_array($vv['device_type'],[26])){
                            $device_sn_arr[]=$vv['device_sn'];
                        }
                    } else {
                        $vv['door_type'] = 2; // 小区大门
                        //当前只有D6支持二维码开门
                        if(in_array($vv['device_type'],[26])){
                            $device_sn_arr[]=$vv['device_sn'];
                        }
                    }

                }
            }
        }
        $info=[];
        //访客邀请
        if ($data['type']==1){
           $visitor_invite_service = new FaceDoorD6SDKService();
           $arr=[];
           $arr['uid']=$data['uid'];
           $arr['pigcms_id']=$data['pigcms_id'];
           $arr['village_id']=$data['village_id'];
           $arr['start_time']=strtotime($data['invite_date'].''.$data['start_time']);
           $arr['end_time']=strtotime($data['invite_date'].''.$data['end_time']);
           $arr['create_time']=time();
           $arr['qrcode_type']=1;
           $arr['status']=1;

            $qrcode_arr=[];
            if (!empty($device_sn_arr)){
                $qrcode_arr['device_sn']=implode(',',$device_sn_arr);
            }else{
                throw new \think\Exception("当前小区未配置可二维码开门的设备");
            }
          //   print_r($singleName);echo '<br>'; print_r($floorName);echo '<br>'; print_r($layer);echo '<br>'; print_r($room);exit;
            if (!empty($singleName)&&!empty($floorName)&&!empty($layer)&&!empty($room)){
                $qrcode_arr['single_num']=$singleName;
                $qrcode_arr['floor_num']=$floorName;
                $qrcode_arr['layer_num']=$layer;
                $qrcode_arr['room_num']=$room;
            }else{
                throw new \think\Exception("当前住户地址不能为空");
            }
            if ($arr['start_time']>$arr['end_time']){
                throw new \think\Exception("邀请开始时间不能大于邀请结束时间");
            } elseif ($arr['start_time']<=time()&&$arr['end_time']<=time()){
                throw new \think\Exception("邀请结束时间不能小于当前时间");
            }else{
                $qrcode_arr['start_time']=$arr['start_time'];
                $qrcode_arr['end_time']=$arr['end_time'];
            }
          //  print_r($qrcode_arr);exit;
            $res1 = $visitor_invite_service->addQrcode($qrcode_arr);
            if (empty($res1['share_image'])){
                throw new \think\Exception("二维码生成失败");
            }
            $arr['url']=$res1['share_image'];
           $id=$db_face_door_D6_qrcode->addFind($arr);
            if ($id>0){
                $res=$db_face_door_D6_qrcode->getOne(['id'=>$id]);
                if (!empty($res)){
                    $address='';
                    if (!empty($user_info)){
                        $address=$this->getAddress($user_info['vacancy_id'],$res['village_id']);
                    }
                    $house_village_service=new HouseVillageService();
                    $village_name=$house_village_service->getHouseVillage($res['village_id'],'village_name,long,lat');
                    $info['village_name']=$village_name['village_name'];
                    $info['long']=$village_name['long'];
                    $info['lat']=$village_name['lat'];
                    $info['address']=isset($address)?$address:'';
                    if (!empty($res['start_time'])){
                        $invite_date=date('Y-m-d',$res['start_time']);
                        $start_time=date('H:i',$res['start_time']);
                    }
                    if (!empty($res['end_time'])){
                        $end_time=date('H:i',$res['end_time']);
                    }
                    $time_arr=$start_time.'至'.$end_time;
                    $info['time']=isset($invite_date)?$invite_date.''.$time_arr:'';
                    $info['qrcode_path']=$res1['share_image'];
                }

            }
        }
        //生成住户开门二维码
        elseif ($data['type']==2){
            $visitor_invite_service = new FaceDoorD6SDKService();
            $arr=[];
            $arr['uid']=$data['uid'];
            $arr['pigcms_id']=$data['pigcms_id'];
            $arr['village_id']=$data['village_id'];
            $arr['start_time']=time();
            $arr['end_time']=$this->setTime+time();
            $arr['create_time']=time();
            $arr['qrcode_type']=2;
            $arr['status']=1;

            $qrcode_arr=[];
            if (!empty($device_sn_arr)){
                $qrcode_arr['device_sn']=implode(',',$device_sn_arr);
            }else{
                throw new \think\Exception("当前小区未配置二维码开门的设备");
            }
            if (!empty($singleName)&&!empty($floorName)&&!empty($layer)&&!empty($room)){
                $qrcode_arr['single_num']=$singleName;
                $qrcode_arr['floor_num']=$floorName;
                $qrcode_arr['layer_num']=$layer;
                $qrcode_arr['room_num']=$room;
            }else{
                throw new \think\Exception("您当前没有二维码开门权限，请联系物业管理人员");
            }
            $qrcode_arr['start_time']=$arr['start_time'];
            $qrcode_arr['end_time']=$arr['end_time'];
            $res = $visitor_invite_service->addQrcode($qrcode_arr);
            if (empty($res['share_image'])){
                throw new \think\Exception("二维码生成失败");
            }
            $arr['url']=$res['share_image'];
            $id=$db_face_door_D6_qrcode->addFind($arr);
            if ($id>0){
                $info['remark']='提示：从生效时间开始10分钟以内二维码有效，支持开启门禁';
                $info['time']=date('Y-m-d H:i',$arr['create_time']);
                $info['qrcode_path']=$res['share_image'];
            }
        }
        return $info;

    }

    /**
     * 生成物业工作人员开门二维码
     * @author:zhubaodi
     * @date_time: 2022/1/7 15:00
     */
    public function addWorkerQrcode($data){
        $db_face_door_D6_qrcode=new FaceDoorD6Qrcode();
        $db_house_village_user_bind=new HouseVillageUserBind();
        $db_house_worker=new HouseWorker();
        $visitor_invite_service = new FaceDoorD6SDKService();
        $user_info=$db_house_village_user_bind->getOne(['pigcms_id'=>$data['pigcms_id'],'status'=>1],'vacancy_id,type,parent_id,pigcms_id,uid,phone,status,property_starttime,property_endtime');
        if (empty($user_info)){
            throw new \think\Exception("用户绑定信息不存在");
        }
        $info=[];
        if ($user_info['type']==4){
            // 获取工作人员信息
            $worker_info=$db_house_worker->get_one(['phone'=>$user_info['phone'],'status'=>1, 'village_id' => $data['village_id']],'village_id,wid,phone,status,open_door');
            if ($worker_info['open_door'] == 1) {
                $qrcode_arr=[];
                $qrcode_arr['managerId']=time();
                $qrcode_arr['type']=1;
                $qrcode_arr['village_id']=$data['village_id'];
                $qrcode_arr['pigcms_id']=$data['pigcms_id'];

                $res1 = $visitor_invite_service->syncQrManager($qrcode_arr);
                if (empty($res1)){
                    throw new \think\Exception("二维码生成失败");
                }
                $res = $visitor_invite_service->addWorkerQrcode($qrcode_arr);
                if (empty($res['share_image'])){
                    throw new \think\Exception("二维码生成失败");
                }
                $arr=[];
                $arr['uid']=$data['uid'];
                $arr['pigcms_id']=$data['pigcms_id'];
                $arr['village_id']=$data['village_id'];
                $arr['start_time']=$qrcode_arr['managerId'];
                $arr['create_time']=time();
                $arr['qrcode_type']=3;
                $arr['status']=1;
                $id=$db_face_door_D6_qrcode->addFind($arr);
                if ($id>0){
                    $info['remark']='提示：工作人员开门二维码使用后，需要重新生成。';
                    $info['time']=date('Y-m-d H:i',$arr['create_time']);
                    $info['qrcode_path']=$res['share_image'];
                }
            }
        }
        return $info;
    }

    public function doorQrcode($data){
        $db_house_village_user_bind=new HouseVillageUserBind();
        $user_info=$db_house_village_user_bind->getOne(['pigcms_id'=>$data['pigcms_id'],'status'=>1],'vacancy_id,type,parent_id,pigcms_id,uid,phone,status,property_starttime,property_endtime');
        if (empty($user_info)){
            throw new \think\Exception("用户未绑定房间");
        }
        $res=[];
        if (in_array($user_info['type'],[0,1,2,5])){
            $data['type']=2;
            $res=$this->addQrcode($data);
        }elseif ($user_info['type']==4){
            $data['type']=3;
            $res=$this->addWorkerQrcode($data);
        }
        return $res;
    }

    /**
     * 查询二维码详情
     * @author:zhubaodi
     * @date_time: 2022/1/7 16:23
     */
    public function getQrcodeInfo($uid,$qrcode_id){
        $db_face_door_D6_qrcode=new FaceDoorD6Qrcode();
        $db_house_village_user_bind=new HouseVillageUserBind();
        $where=[
            'uid'=>$uid,
            'id'=>$qrcode_id,
            'qrcode_type'=>1,
        ];
        $qrcode_info=$db_face_door_D6_qrcode->getOne($where);
        $logList=[];
        $logList1=[];
        $qrcodeInfo=[];
        if (!empty($qrcode_info)){
            if (!empty($qrcode_info['start_time'])){
                $invite_date=date('Y-m-d',$qrcode_info['start_time']);
                $start_time=date('H:i',$qrcode_info['start_time']);
            }
            if (!empty($qrcode_info['end_time'])){
                $end_time=date('H:i',$qrcode_info['end_time']);
            }
            $qrcodeInfo['id']=$qrcode_info['id'];
            $qrcodeInfo['invite_date']=isset($invite_date)?$invite_date:'';
            $qrcodeInfo['start_time']=isset($start_time)?$start_time:'';
            $qrcodeInfo['end_time']=isset($end_time)?$end_time:'';
            $qrcodeInfo['qrcode_path']=$qrcode_info['url'];
            $user_info=$db_house_village_user_bind->getOne(['pigcms_id'=>$qrcode_info['pigcms_id']]);
            $address='';
            if (!empty($user_info)){
                $address=$this->getAddress($user_info['vacancy_id'],$qrcode_info['village_id']);
            }
            $house_user_log=new HouseUserLog();
            $where_log=[
              'qrcode_id'=>$qrcode_id,
              'log_from'=>31,
            ];
           $log_list= $house_user_log->getLists($where_log);
           if (!empty($log_list)){
               $log_list=$log_list->toArray();
               if (!empty($log_list)){
                   foreach ($log_list as $value) {
                       $logList1['time']=date('Y-m-d H:i:s',$value['log_time']);
                       $logList1['status']='成功';
                       $logList1['color']='#17C18B';
                       $logList1['address']=$address;
                       $logList[]=$logList1;
                   }

               }
           }
        }
        $data=[];
        $data['qrcode_info']=$qrcodeInfo;
        $data['log_list']=$logList;
        return $data;
    }

    /**
     * 查询住户地址
     * @author:zhubaodi
     * @date_time: 2022/1/8 17:12
     */
    public function getAddress($vacancy_id,$village_id){
        $db_house_village_user_vacancy = new HouseVillageUserVacancy();
        $room = $db_house_village_user_vacancy->getLists(['pigcms_id' => $vacancy_id]);
        $number='';
        if (!empty($room)) {
            $room = $room->toArray();
            if (!empty($room)) {
                $room1 = $room[0];
                $house_village_service=new HouseVillageService();
                $village_name=$house_village_service->getHouseVillage($village_id,'village_name');
                $number =$house_village_service->word_replce_msg(array('single_name'=>$room1['single_name'],'floor_name'=>$room1['floor_name'],'layer'=>$room1['layer_name'],'room'=>$room1['room']),$village_id);
                $number=$village_name['village_name'].$number;
            }
        }
        return $number;
    }

    /**
     * 获取对应绑定小区身份的物业服务时间
     * @author:zhubaodi
     * @date_time: 2022/1/10 19:45
     */
    public function getUserEndTime($pigcms_id, $param=[],$parentInfo=[],$parentField='pigcms_id,uid,phone,status,property_starttime,property_endtime')
    {
        $arr = [];
        $db_house_village_user_bind=new HouseVillageUserBind();
        $isHasTime = 1;
        $isDelay = isset($param['isDelay']) ? intval($param['isDelay']) : 1;
        $param = unsetParam($param, 'isDelay');
        if ($pigcms_id>0 && (empty($param) || !isset($param['owe_property_open_door']))) {
            $db_house_village = new HouseVillage();
            $field = 'pigcms_id,parent_id,type,village_id,vacancy_id,property_starttime,property_endtime,phone,open_door_day';
            $bindUser = $db_house_village_user_bind->getOne(['pigcms_id' => $pigcms_id], $field);
            if (empty($bindUser)) {
                // 如果查询不到用户直接返回空
                fdump_api(['pigcms_id' => $pigcms_id], '$getUserEndTime', 1);
                return $arr;
            }
            if (!in_array($bindUser['type'], [0, 1, 2, 3])) {
                $isDelay = 0;
            }
            $today_date=date('Y-m-d');
            if($bindUser['type']==4){
                //工作人员
                $arr['parentInfo'] = [];
                $arr['propertyStartTime'] =strtotime($today_date);
                $arr['propertyEndTime'] = 2146665600;
                return $arr;
            }
            $field1 = 'owe_property_open_door,owe_property_open_door_day,property_id';
            $villageInfo=$db_house_village->getOne($bindUser['village_id'],$field1);
            if (empty($villageInfo)){
                fdump_api(['village_id' => $bindUser['village_id']], '$getUserEndTime', 1);
                return $arr;
            }
            if(isset($bindUser['open_door_day']) && $bindUser['open_door_day']>0){
                $villageInfo['owe_property_open_door_day']=$bindUser['open_door_day'];
            }
            $param['vacancy_id'] = $bindUser['vacancy_id'];
            $param['property_id'] = $villageInfo['property_id'];
            $param['type'] = $bindUser['type'];
            $param['parent_id'] = $bindUser['parent_id'];
            $param['startTime'] = $bindUser['property_starttime'];
            $param['endTime'] = $bindUser['property_endtime'];
            $param['owe_property_open_door'] = $villageInfo['owe_property_open_door'];
            $param['owe_property_open_door_day'] = $villageInfo['owe_property_open_door_day'];
            if (isset($param['type']) && in_array($param['type'],[5, 6])) {
                $param['property_starttime'] = $bindUser['property_starttime'];
                $param['property_endtime'] = $bindUser['property_endtime'];
                $isHasTime = 0;
            }
        }
        fdump_api(['pigcms_id' => $pigcms_id, 'param' => $param, 'isDelay' => $isDelay], '$getUserEndTime', 1);

        if (isset($param['type']) && isset($param['parent_id']) && $param['parent_id'] && in_array($param['type'],[1,2])) {
            if (empty($parentInfo)) {
                $parentInfo =$db_house_village_user_bind->getOne(['pigcms_id' => $param['parent_id']],$parentField);
            }
            if (!$parentInfo) $parentInfo = [];
            $arr['parentInfo'] = $parentInfo;
            if ($parentInfo) {
                // -1标识 无限制
                $arr['propertyEndTime'] = isset($parentInfo['property_endtime'])&&$parentInfo['property_endtime']?$parentInfo['property_endtime']:0;
                $arr['propertyStartTime'] = isset($parentInfo['property_starttime'])&&$parentInfo['property_starttime']?$parentInfo['property_starttime']:0;
            }
            $bind_id = $param['parent_id'];
        } else {
            $bind_id = $pigcms_id;
            if (isset($param['property_starttime'])) {
                $arr['propertyStartTime'] = isset($param['property_starttime'])&&$param['property_starttime']?$param['property_starttime']:0;
            }
            if (isset($param['property_endtime'])) {
                $arr['propertyEndTime'] = isset($param['property_endtime'])&&$param['property_endtime']?$param['property_endtime']:0;
            }
        }
        fdump_api(['$arr' => $arr, '$isHasTime' => $isHasTime], '$getUserEndTime', 1);

        if ($isHasTime == 1 && isset($param['property_id'])&&$param['property_id']) {
            $houseNewPorptertyService = new HouseNewPorpertyService();
            $houseNewCashierService = new HouseNewCashierService();
            $db_house_new_order_log=new HouseNewOrderLog();
            // 存在物业id，查询下物业新版收费生效与否 确定物业服务时间截止
            $is_new_charge=$houseNewPorptertyService->isUseNewCharge($param['property_id']);
            if ($is_new_charge) {
                // 新版生效了  查询下
                $whereNewOrder = array('room_id'=>$param['vacancy_id'], 'order_type'=>'property');
                //查当前房间的服务结束时间
                $order_log = $db_house_new_order_log->getOne($whereNewOrder);
                //查当前房间的服务开始时间
                $order_log_start = $db_house_new_order_log->getOne($whereNewOrder,true,'id ASC');
				$arr['propertyStartTime'] = 0;
				$arr['propertyEndTime'] = 0;
				$contract_time=$houseNewCashierService->getContractTime($param['vacancy_id']);
				if($contract_time && isset($contract_time['contract_time_start'])){
					$arr['propertyStartTime'] = $contract_time['contract_time_start'];
					$arr['propertyEndTime'] = $contract_time['contract_time_end'];
				}
                if($contract_time['contract_time_start']>0&&!empty($order_log)){
                    $arr['propertyEndTime'] = $order_log['service_end_time'];
                    if ($contract_time['contract_time_start']<$order_log_start['service_start_time']){
                        $arr['propertyStartTime'] = $order_log_start['service_start_time'];
                        if($order_log['service_end_time']<$arr['propertyStartTime']){
                            $arr['propertyEndTime'] = $order_log_start['service_end_time'];
                        }
                    }else{
                        $arr['propertyStartTime'] = $contract_time['contract_time_start'];
                        if($order_log['service_end_time']<$arr['propertyStartTime']){
                            $arr['propertyEndTime'] = $contract_time['contract_time_end'];
                        }
                    }
                }else if($order_log_start && isset($order_log_start['service_start_time']) && ($order_log_start['service_start_time']>100)){
                    $arr['propertyStartTime'] = $order_log_start['service_start_time'];
                    $arr['propertyEndTime'] = $order_log_start['service_end_time'];
                    if($order_log && isset($order_log['service_end_time']) && ($order_log['service_end_time']>$arr['propertyEndTime']) && ($order_log['service_end_time']>$arr['propertyEndTime'])){
                        $arr['propertyEndTime'] = $order_log['service_end_time'];
                    }
                }

                fdump_api(['pigcms_id' => $pigcms_id, 'isDelay' => $isDelay], '$getUserEndTime', 1);
                if ($isDelay == 1) {
                    $owe_property_open_door = isset($param['owe_property_open_door']) ? intval($param['owe_property_open_door']) : 0;
                    $owe_property_open_door_day = isset($param['owe_property_open_door_day']) ? intval($param['owe_property_open_door_day']) : 0;
                    fdump_api(['pigcms_id' => $pigcms_id, 'owe_property_open_door' => $owe_property_open_door, 'owe_property_open_door_day' => $owe_property_open_door_day], '$getUserEndTime', 1);
                    if ($owe_property_open_door == 1 && isset($arr['propertyEndTime']) && $arr['propertyEndTime'] && $owe_property_open_door_day > 0) {
                        $arr['propertyEndTime'] +=  $owe_property_open_door_day * 86400;
                    }
                    else if ($owe_property_open_door == 1 && $owe_property_open_door_day <= 0) {
                        // 允许延期开门 且为0 默认不限制 2035
                        $arr['propertyEndTime'] =  2082729599;
                    }
                }

                fdump_api(['pigcms_id' => $pigcms_id, 'arr' => $arr], '$getUserEndTime', 1);

                $arr['parentInfo'] = $parentInfo?$parentInfo:[];
                return $arr;
            }
        }
        
        // 计算 当前时间到物业截止时间的天数
        if ($isHasTime == 1 && isset($arr['propertyEndTime']) && $arr['propertyEndTime']>0) {
            $db_house_village_property_paylist=new HouseVillagePropertyPaylist();
            $end_time_data=$db_house_village_property_paylist->getOne(['bind_id'=>$bind_id],'end_time');
            $end_time=$end_time_data['end_time'];
            if ($end_time>$arr['propertyEndTime']) {
                $arr['propertyEndTime'] = $end_time;
            }
        }
        $owe_property_open_door = isset($param['owe_property_open_door']) ? intval($param['owe_property_open_door']) : 0;
        $owe_property_open_door_day = isset($param['owe_property_open_door_day']) ? intval($param['owe_property_open_door_day']) : 0;
        fdump_api(['owe_property_open_door' => $owe_property_open_door, 'owe_property_open_door_day' => $owe_property_open_door_day], '$getUserEndTime', 1);
        if ($isHasTime == 1 && $owe_property_open_door == 1 && isset($arr['propertyEndTime']) && $arr['propertyEndTime'] && $owe_property_open_door_day > 0) {
            $arr['propertyEndTime'] +=  $owe_property_open_door_day * 86400;
            fdump_api(['$arr' => $arr, 'owe_property_open_door_day' => $owe_property_open_door_day], '$getUserEndTime', 1);
        }
        else if ($isHasTime == 1 && $owe_property_open_door == 1 && $owe_property_open_door_day <= 0) {
            // 允许延期开门 且为0 默认不限制 2035
            $arr['propertyEndTime'] =  2082729599;
        }
        fdump_api(['$arr' => $arr, '$isHasTime' => $isHasTime], '$getUserEndTime', 1);
        if (!isset($arr['parentInfo']) || !$arr['parentInfo']) {
            $arr['parentInfo'] = [];
        }
        if (!isset($arr['propertyEndTime'])) {
            $arr['propertyEndTime'] = 0;
        }
        if (!isset($arr['propertyStartTime'])) {
            $arr['propertyStartTime'] = 0;
        }
        fdump_api(['$arr' => $arr, '$isHasTime' => $isHasTime], '$getUserEndTime', 1);
        return $arr;
    }

    public function share_info($uid,$village_id,$pigcms_id){
        $db_user_bind=new HouseVillageUserBind();
        $db_house_village=new HouseVillage();
        $field1 = 'village_name';
        $villageInfo=$db_house_village->getOne($village_id,$field1);
        $user_info=$db_user_bind->getOne(['pigcms_id'=>$pigcms_id],'name');
        $share_info=[
            'share_img'=> cfg('site_logo'),
            'share_wx'=>cfg('pay_wxapp_important') && cfg('pay_wxapp_username') ? 'wxapp' : 'h5',
            'userName'=>cfg('pay_wxapp_username'),
            'title'=>'【'.$user_info['name'].'】分享了一个【'.$villageInfo['village_name'].'】的开门权限',
            'info'=>'进入可查看详情。'
        ];
        return $share_info;
    }
}