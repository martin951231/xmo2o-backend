<?php

namespace app\community\model\service;

use app\common\model\service\image\ImageService;
use app\community\model\db\DeviceAuth;
use app\community\model\db\FaceA185RequestRecord;
use app\community\model\db\FaceD3RequestRecord;
use app\community\model\db\FaceD4RequestRecord;
use app\community\model\db\FaceUserBindDevice;
use app\community\model\db\HouseFaceBindDevice;
use app\community\model\db\HouseFaceDevice;
use app\community\model\db\HouseFaceImg;
use app\community\model\db\HouseFaceSyncLog;
use app\community\model\db\HouseLog;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageFloor;
use app\community\model\db\HouseVillageInfo;
use app\community\model\db\HouseVillageLayer;
use app\community\model\db\HouseVillagePublicArea;
use app\community\model\db\HouseVillageSingle;
use app\community\model\db\HouseVillageThirdConfig;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\db\ProcessSubPlan;
use app\community\model\db\UserSet;
use app\community\model\service\Device\D4FaceDeviceService;
use app\community\model\service\Device\FaceDHYunRuiCloudDeviceService;
use app\community\model\service\Device\FaceHikCloudNeiBuDeviceService;
use app\community\model\service\Device\XiHanEventService;
use app\consts\DahuaConst;
use app\consts\DeviceConst;
use app\consts\HikConst;
use app\traits\FaceDeviceBindUserTraits;
use app\traits\FaceDeviceDHCloudTraits;
use app\traits\house\DeviceTraits;
use face\wordencryption;
use file_handle\FileHandle;
use think\facade\Db;

class OwnerService
{
    use FaceDeviceBindUserTraits;
    use FaceDeviceDHCloudTraits;
    use DeviceTraits;
    /**
     * 人脸下发设备列表
     */
    public function getFaceDeviceSyncList($param)
    {
        $where= [['village_id','=',$param['village_id']],['is_del','=',0]];
        $whereRaw = 'device_type in (3,7,61,31) or (device_type=5 and thirdProtocol='.DeviceConst::HIK_YUNMO_NEIBU_SHEQU.') or (device_type=88 and thirdProtocol='.DeviceConst::DH_YUNRUI.')';
        $field = 'device_id,device_name,device_sn,floor_id,public_area_id';
        $order = 'device_id desc';
        $data = (new HouseFaceDevice())->getDeviceLists($where,$field,$order,$param['pageSize'],$whereRaw);
        foreach ($data['data'] as $k=>$v){
            $floor_arr = explode(',',$v['floor_id']);
            $floor_about_info = [];
            if (isset($v['public_area_id']) && $v['public_area_id']>0) {
                $public_area_name = (new HouseVillagePublicArea())->where(array('public_area_id'=>$v['public_area_id']))->value('public_area_name');
                $floor_name	= $public_area_name;
                $floor_layer = cfg('house_name');
            } else if ( $v['floor_id'] == -1) {
                $floor_name	= '大门';
                $floor_layer = cfg('house_name');
            }else{
                if (count($floor_arr)==1) {
                    $v['floor_id'] = $floor_arr[0];
                    $aFloor = (new HouseVillageFloor())->where(['floor_id'=>$v['floor_id']])->field(['floor_name','floor_layer','single_id'])->find();
                    $single_name = (new HouseVillageSingle())->where(array('id'=>$aFloor['single_id']))->value('single_name');
                    $floor_name	=	$aFloor['floor_name'] ? $aFloor['floor_name'] : '';
                    $floor_layer = $single_name ? $single_name : $aFloor['floor_layer'];
                } else {
                    $floor_about_info = (new HouseVillageFloor())->floorSingleName([['floor_id','in',$floor_arr]],'f.floor_id, s.id as single_id, f.floor_name, s.single_name');
                    $floor_about_info = $floor_about_info ? $floor_about_info->toArray() : [];
                    $floor_name	= '';
                    $floor_layer = '关联多单元';
                }
            }
            $data['data'][$k]['address'] = '';
            if($floor_about_info && $floor_about_info[0]){
                foreach ($floor_about_info as $vv){
                    if($vv['single_name'] || $vv['floor_name']){
                        $data['data'][$k]['address'] .= ','.$vv['single_name'].'-'.$vv['floor_name'];
                    }
                }
                $data['data'][$k]['address'] = trim($data['data'][$k]['address'],',');
            }else{
                $floor_layer = $floor_name ? $floor_layer.'-'.$floor_name : $floor_layer;
                if($floor_layer){
                    $data['data'][$k]['address'] = $floor_layer;
                }
            }
            unset($data['data'][$k]['public_area_id']);
        }

        //获取所有设备id
        $data['all_device_id'] = (new HouseFaceDevice())->getDeviceListsColumn($where,'device_id',$order,$whereRaw);
        return $data;
    }

    /**
     * 查询用户绑定设备
     */
    public function getBindDevice($param)
    {
        $bind= [];
        if($param['checkId']){//查询用户已经绑定的设备id
            $bindDevice = (new HouseFaceBindDevice())->where('pigcms_id',$param['checkId'])->column('device_id');
            $bind = $bindDevice ? $bindDevice : [];
        }
        return $bind;
    }
    
    /**
     * 查询人脸下发记录
     */
    public function getSynLogList($param)
    {
        if(!$param['pigcms_id']){
            throw_exception(L_('请选择操作对象'));
        }
        $where = ['log.pigcms_id'=>$param['pigcms_id']];
        $field = 'log.log_id,d.device_sn,d.device_name,log.log_time,log.status,log.msg';
        $order = 'log_id desc';
        $list = (new HouseFaceSyncLog())->getListAndDeviceInfo($where,$field,$order,$param['pageSize']);
        foreach ($list['data'] as $k=>$v){
            $list['data'][$k]['log_time'] = $list['data'][$k]['log_time'] ? date('Y/m/d H:i:s',$list['data'][$k]['log_time']) : '';
            $list['data'][$k]['status'] = (new HouseFaceSyncLog())->getStatusStr($v['status']);
        }
        return $list;
    }

    /**
     * 绑定人脸设备
     */
    public function bindFaceDevice($param)
    {
        //记录小区后台操作日志
        (new HouseLog())->insert([
            'admin_id'=>$param['admin_id'],
            'village_id'=>$param['village_id'],
            'group'=>'community',
            'module'=>'village_api.Owner',
            'action'=>'bindFaceDevice',
            'content'=>serialize(array('GET' => $_GET, 'POST' => $_POST)),
            'add_time'=>$_SERVER['REQUEST_TIME'],
            'add_ip'=>get_client_ip(0),
        ]);
        $this->bindPersonal($param);
    }
    /**
     * 批量绑定单元楼人脸设备
     */
    public function bindFaceDevices($param)
    {
        //记录小区后台操作日志
        (new HouseLog())->insert([
            'admin_id'=>$param['admin_id'],
            'village_id'=>$param['village_id'],
            'group'=>'community',
            'module'=>'village_api.Owner',
            'action'=>'bindFaceDevice',
            'content'=>serialize(array('GET' => $_GET, 'POST' => $_POST)),
            'add_time'=>$_SERVER['REQUEST_TIME'],
            'add_ip'=>get_client_ip(0),
        ]);
        if(count($param['pigcms_ids'])>10){//用户超过10个走队列
            fdump_api($param['pigcms_ids'],'faceDeviceBindUser/test',1);
            foreach ($param['pigcms_ids'] as $v){
                fdump_api($v,'faceDeviceBindUser/test',1);
                $this->BindCommonPushToJob([
                    'village_id'=>$param['village_id'],
                    'pigcms_id'=>$v,  
                    'floor'=>1         
                ]);
            }
        }else{
            foreach ($param['pigcms_ids'] as $v){
                $this->bindPersonal([
                    'village_id'=>$param['village_id'],
                    'pigcms_id'=>$v,
                    'floor'=>1
                ]);
            }
        }
        return true;
    }

    /**
     * 单个用户绑定人脸设备
     */
    public function bindPersonal($param)
    {
        $param['device_ids'] = $param['device_ids'] ?? [];
        if(isset($param['floor'])){//获取单元楼绑定设备
            fdump_api(['msg'=>'开始绑定人脸','param'=>$param],'faceDeviceBindUser/bindPersonal',1);
            $userBindInfo = (new HouseVillageUserBind())->where('pigcms_id',$param['pigcms_id'])->field('floor_id,pigcms_id,vacancy_id,uid')->find();
            $userBindInfo = $userBindInfo && !$userBindInfo->isEmpty() ? $userBindInfo->toArray() : [];
            if(empty($userBindInfo)){
                $deviceId = [];
            }else{
                $where= [['village_id','=',$param['village_id']],['is_del','=',0]];
                $whereRaw = 'device_type in (3,7,61,31) or (device_type=5 and thirdProtocol='.DeviceConst::HIK_YUNMO_NEIBU_SHEQU.') or (device_type=88 and thirdProtocol='.DeviceConst::DH_YUNRUI.')';
                
                $whereRaw = '(find_in_set('.$userBindInfo['floor_id'].',floor_id) OR floor_id<1)';
                $whereRaw .= ' and (device_type in (3,7,61,31) or (device_type=5 and thirdProtocol='.DeviceConst::HIK_YUNMO_NEIBU_SHEQU.') or (device_type=88 and thirdProtocol='.DeviceConst::DH_YUNRUI.'))';
                $floorFaceDevice = (new HouseFaceDevice())->getDeviceLists([
                    ['village_id','=',$param['village_id']],
                    ['is_del','=',0]
                ], 'device_id,device_sn,village_id,thirdProtocol',
                    'device_id desc', 0, $whereRaw);
                $deviceId = array_column($floorFaceDevice,'device_id');
            }
            fdump_api(['msg'=>'获取私人设备列表','deviceId'=>$deviceId],'faceDeviceBindUser/bindPersonal',1);
        }else{//绑定选取的设备
            //查询设备是否有效，去除无效设备，获取到本次需要绑定的设备
            $deviceId = (new HouseFaceDevice())->where([
                ['device_id','in',$param['device_ids']],
                ['village_id','=',$param['village_id']],
                ['is_del','=',0]
            ])->column('device_id');
        }
        
        $deviceId = $deviceId?:[];
        //查询用户已绑定的设备
        $bindDeviceId = (new HouseFaceBindDevice())->where('pigcms_id',$param['pigcms_id'])->column('device_id');
        $bindDeviceId = $bindDeviceId ?: [];
        //互相取差集，分别获取需要新增的设备和需要删除的设备
        $delId = array_diff($bindDeviceId,$deviceId);
        $addId = array_diff($deviceId,$bindDeviceId);
        $addData = [];
        if($addId){
            foreach ($addId as $v){
                $addData[] = [
                    'village_id'=>$param['village_id'],
                    'pigcms_id'=>$param['pigcms_id'],
                    'device_id'=>$v,
                    'add_time'=>time(),
                ];
            }
        }
        Db::startTrans();
        try {
            //新增设备
            if($addData){
                (new HouseFaceBindDevice())->insertAll($addData);
            }
            //删除设备
            if($delId){
                (new HouseFaceBindDevice())->where([['pigcms_id','=',$param['pigcms_id']],['device_id','in',$delId]])->delete();
            }
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            throw_exception($e->getMessage());
        }
    }

    /**
     * 获取用户人脸信息
     */
    public function getFaceInfo($param)
    {
        $pigcmsId = $param['pigcms_id']??0;
        if(!$pigcmsId){
            throw_exception(L_('查询用户不能为空'));
        }
        $uid = (new HouseVillageUserBind())->where(['pigcms_id'=>$pigcmsId])->value('uid');
        $img = [];
        if($uid){
            $face = (new HouseFaceImg())->where([
                ['status','in',[0,3]],
                ['uid','=',$uid],
                ['img_url','<>','']
            ])->order('add_time desc')->field('id,img_url,status,add_time')->limit(5)->select();
            $img = $face ? $face->toArray() : [];
            $wordencryption=new wordencryption();
            foreach ($img as &$v){
                if (3==$v['status']) {
                    // 加密的图片解密
                    $v['img_url'] = $wordencryption->text_decrypt($v['img_url']);
                }
                $v['img_url'] = replace_file_domain($v['img_url']);
            }
            $add_times = array_column($img, 'add_time');
            array_multisort($img,SORT_ASC,$add_times);
        }
        $noUploadFace = false;
        $faceDoorSet = (new HouseVillageInfo())->where(['village_id' => $param['village_id']])->field('face_door_set')->find();
        $thirdConfig = (new HouseVillageThirdConfig())->where(['village_id' => $param['village_id']])->find();
        if($thirdConfig && $thirdConfig['type'] == 'chinapopin_sichuan' && $thirdConfig['status'] == '1'){
            $noUploadFace = true;
        }
        $return = [
            'img'=>$img,
            'face_door_set'=>$faceDoorSet,
            'no_upload_face'=>$noUploadFace,
        ];
        return $return;
    }
    
    /**
     * 同步人脸
     */
    public function syncFace($param)
    {
        $pigcmsIds = $param['pigcms_ids'];
        if(!$pigcmsIds){
            throw_exception('操作对象不能为空');
        }
        $xiHanEventService=new XiHanEventService();
        foreach ($pigcmsIds as $v){
            $userBindInfo = (new HouseVillageUserBind())->where('pigcms_id',$v)->field('pigcms_id,village_id,single_id,floor_id,layer_id,parent_id,vacancy_id,uid,type,name,phone,ic_card,id_card,property_starttime')->find();
            if(!$userBindInfo || $userBindInfo->isEmpty()){
                continue;
            }
            $userBindInfo = $userBindInfo->toArray();
            //查询用户绑定的设备
            $floorFaceDevice = (new HouseFaceBindDevice())->getDeviceList(['fbd.pigcms_id'=>$v,'fd.is_del'=>0],'fd.device_id,fd.device_sn,fd.village_id,fd.thirdProtocol,fd.device_type');
            $step_num = $this->step_num + 1;
            foreach ($floorFaceDevice as $device){
                $logId = 0;
                if(in_array($device['device_type'],[3,7,61]) ||//A3、A7、A185
                    ($device['device_type']==5 && $device['thirdProtocol'] == DeviceConst::HIK_YUNMO_NEIBU_SHEQU) || //海康内部
                    ($device['device_type']==88 && $device['thirdProtocol'] == DeviceConst::DH_YUNRUI))//大华
                {
                    $logId = (new HouseFaceSyncLog())->insertGetId([
                        'pigcms_id'=>$userBindInfo['pigcms_id'],
                        'face_id'=>0,
                        'device_id'=>$device['device_id'],
                        'device_sn'=>$device['device_sn'],
                        'log_time'=>time(),
                        'status'=>0,
                        'device_info'=>json_encode($device),
                    ]);
                }
                $this->nowTime = time();
                //剩下的规矩设备类型来区分
                switch ($device['device_type']){
                    case 1:
                        /**A1智能门禁机 暂时不用管*/
                        break;
                    case 2:
                        /**A2智能门禁机（不支持设备的绑定，不考虑）*/
                        break;
                    case 3:
                        /**A3智能门禁机*/
                        $paramPlan = [
                            'type' => 'sync_a3_face',
                            'device_id' =>$device['device_id'],
                            'pigcms_id' =>$userBindInfo['pigcms_id'],
                            'unique_id' =>'a3_face_device_'.$device['device_id'].'_'.$userBindInfo['pigcms_id'],
                            'log_id' => $logId
                        ];
                        $plan_time = time();
                        $result = $this->add_plan($paramPlan,'sub_house_face',$plan_time,['device_info'=>$device]);
                        $addId = $result['task_id'];
                        if(!$addId){
                            $this->updateLog(['log_id'=>$logId],[
                                'status'=>2,
                                'msg'=>L_('上次待同步的记录还未处理'),
                                'last_time'=>time(),
                            ]);
                        }
                        fdump_api([$addId,$paramPlan],'sync_a3_face_per',1);
                        break;
                    case 4:
                        /**A4智能门禁机(暂不支持)*/
//                        if(!in_array($userBindInfo['pigcms_id'],$repeat)){
//                            $paramPlan = [
//                                'type' => 'add_a4_member_household',
//                                'bind_id' =>$userBindInfo['pigcms_id'],
//                                'communityId' =>0,
//                                'roomId' => 0,
//                                'device_id' =>$device['device_id'],
//                            ];
//                            $this->add_plan($paramPlan,'sub_house_village_face_device',0,['device_info'=>$device]);
//                        }
//                        $repeat = array_merge($repeat, [$userBindInfo['pigcms_id']]);
                        break;
                    case 5:
//                    case 6:
                        /**A5A6智能门禁机  除了海康内部，其他暂时不用考虑*/
//                        usleep(800000);//休眠500毫秒
//                        fdump_api(['同步人脸[日志]'.__LINE__,$device['device_id'],[$userBindInfo['pigcms_id']],$device['device_sn']],'a5New/synDeviceDisposeLog',true);mPlan = [
//                            'paramType' => 'addSingleMember',
//                            'device_id' =>$device['device_id'],
//                            'village_id' =>$device['village_id'],
//                            'pigcms_id' =>$userBindInfo['pigcms_id'],
//                            'unique_id' =>'addSingleMember_'.$device['village_id'].'_'.$device['device_id'].'_'.$userBindInfo['pigcms_id'],
//                            'log_id' => $logId
//                        ];
//                        $plan_time = -151;
//                        $result = $this->add_plan($paramPlan,'sub_device_syn',$plan_time,['device_info'=>$device]);
//                        $addId = $result['task_id'];
//                        if(!$addId){
//                            $this->updateLog(['log_id'=>$logId],[
//                                'status'=>2,
//                                'msg'=>L_('上次待同步的记录还未处理'),
//                                'last_time'=>time(),
//                            ]);
//                        }
//                        fdump_api([$addId,$paramPlan],'sync_a5_face_per',1);
                        if($device['thirdProtocol'] == DeviceConst::HIK_YUNMO_NEIBU_SHEQU){
                            $param['log_id'] = $logId;
                            $this->hikYunMo($device,$userBindInfo,$step_num,$param);
                        }
                        break;
                    case 7:
                        /**A7智能门禁机*/
                        $paramPlan = [
                            'type' => 'sync_a7_face_single_per',
                            'device_id' =>$device['device_id'],
                            'village_id' =>$device['village_id'],
                            'pigcms_id' =>$userBindInfo['pigcms_id'],
                            'bind_info' =>[],
                            'device_info' =>[],
                            'unique_id' =>'sync_a7_face_single_per_'.$device['village_id'].'_'.$device['device_id'].'_'.$userBindInfo['pigcms_id'],
                            'log_id' => $logId
                        ];
                        $result = $this->add_plan($paramPlan,'sub_house_village_face_device',-10,['device_info'=>$device]);
                        $addId = $result['task_id'];
                        if(!$addId){
                            $this->updateLog(['log_id'=>$logId],[
                                'status'=>2,
                                'msg'=>L_('上次待同步的记录还未处理'),
                                'last_time'=>time(),
                            ]);
                        }
                        fdump_api([$addId,$paramPlan],'sync_a7_face_per',1);
                        break;
                    case 8:
                        /**A8智能门禁机(定制，暂时不考虑)*/
//                        usleep(800000);// 避免超频调用
                        break;
                    case 21:
                        /**D1智能门禁机(定制，暂时不考虑)*/
//                        $paramPlan = [
//                            'type' => 'd1_add_user',
//                            'bind_id' => $userBindInfo['pigcms_id'],
//                            'unique_id' =>'d1_add_user_'.$userBindInfo['pigcms_id'],
//                            'log_id' => $logId
//                        ];
//                        $result = $this->add_plan($paramPlan,'sub_house_face',-2,['device_info'=>$device]);
//                        $addId = $result['task_id'];
//                        if(!$addId){
//                            $this->updateLog(['log_id'=>$logId],[
//                                'status'=>2,
//                                'msg'=>L_('上次待同步的记录还未处理'),
//                                'last_time'=>time(),
//                            ]);
//                        }
//                        fdump_api([$addId,$paramPlan],'sync_a7_face_per',1);
                        break;
                    case 23:
                        /**D3智能门禁机 定制暂时不考虑*/
//                        $this->d3CreateFaceRecord($device,$userBindInfo,$logId);
                        break;
                    case 24:
                        /**D4智能门禁机  定制暂时不考虑*/
//                        $this->D4CreateFaceRecord($device,$userBindInfo,$logId);
                        break;
                    case 61:
                        /**A185智能门禁机*/
                        $this->a185CreateFaceRecord($device,$userBindInfo,$logId);
                        break; 
                    case 88:
                        /**大华人脸门禁*/
                        if($device['thirdProtocol'] == DeviceConst::DH_YUNRUI){
                            $param['log_id'] = $logId;
                            $this->dhYunRui($device,$userBindInfo,$step_num,$param);
                        }
                        break;
                    case 29:
                        /**朵普  定制暂时不考虑*/
//                        (new CloudIntercomService())->sendThirdUserToDeviceDeal($device['village_id'],$device['device_sn'],[$userBindInfo['pigcms_id']],'add','house_village',['log_id'=>$logId]);
//                        break;
                    case 31:
                        /**西涵人脸门禁*/
                        $xiHanEventService->xihan_add_person($device['device_id'],$userBindInfo,$userBindInfo['pigcms_id'],$device['village_id']);
                        break;
                }
            }
        }
        return true;
    }
    
    /**
     * 修改下发记录
     */
    public function updateLog($where,$data)
    {
        (new HouseFaceSyncLog())->where($where)->save($data);
        return true; 
    }

    /**
     * 海康云睿内部
     */
    public function hikYunMo($device,$userBindInfo,$step_num,$param)
    {
        //获取下发记录
        $logId = $param['log_id'];
        //海康内部应用
        $this->thirdProtocol = DeviceConst::HIK_YUNMO_NEIBU_SHEQU;
        $paramData = [
            'pigcms_id'     => $userBindInfo['pigcms_id'],
            'device_id'     => $device['device_id'],
            'room_id'       => $userBindInfo['vacancy_id'],
            'uid'           => isset($userBindInfo['uid']) ? $userBindInfo['uid'] : 0,
            'village_id'    => $device['village_id'],
            'thirdProtocol' => DeviceConst::HIK_YUNMO_NEIBU_SHEQU,
            'device_sn'     => $device['device_sn'],
            'deviceType'    => DeviceConst::DEVICE_TYPE_FACE,
            'step_num'      => $step_num,
            'log_id'      => $logId,
        ];
        $paramData['jobType'] = 'addPersonsToCloud';
        $job_id = (new FaceDeviceService())->traitCommonHikToJob($paramData);
        $paramData = $this->filterCommonParam($paramData);
        if (isset($job_id)) {
            $paramData['job_id']     = $job_id;
        }
        $this->step_num          = $step_num;
        $this->record_bind_type  = DeviceConst::BIND_FACE_BIND_USER_BIND_CARD;
        $this->record_bind_id    = $userBindInfo['pigcms_id'];
        $this->syn_status        = DeviceConst::BIND_USER_TO_DEVICE_SYN_QUEUE;
        $this->syn_status_txt    = '下发住户同步设备队列';
        $this->err_reason        = "";
        $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $param];
        fdump_api(['param' => $paramData, 'job_id' => $job_id, 'msg' => '下发住户同步设备队列'], 'v20Face/syncFace',1);
        $this->recordDeviceBindFilterBox($paramData);
        return true;
    }

    /**
     * 大华云眸
     */
    public function dhYunRui($device,$userBindInfo,$step_num,$param){
        //获取下发记录
        $logId = $param['log_id'];
        //大华云睿
        $thirdProtocol = $this->thirdProtocol = DeviceConst::DH_YUNRUI;
        $this->record_bind_type  = DeviceConst::BIND_FACE_BIND_USER_BIND_CARD;
        $this->record_bind_id    = $userBindInfo['pigcms_id'];
        $this->syn_status        = DeviceConst::BIND_USER_ALL_SYN_START;
        $this->syn_status_txt    = '住户同步开始';
        $this->err_reason        = "";
        $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'pigcms_id' => $userBindInfo['pigcms_id'], 'village_id' => $device['village_id']];
        $this->step_num++;

        //查询设备权限
        $deviceAuth = new DeviceAuth();
        $sourceWhereAuth = [];
        $sourceWhereAuth[] = ['device_equipment_type', '=', 'face'];
        $sourceWhereAuth[] = ['village_id', '=', $device['village_id']];
        $sourceWhereAuth[] = ['delete_time', '=', 0];
        $sourceWhereAuth[] = ['device_id', '=', $device['device_id']];
        $whereAuth = $sourceWhereAuth;
        $whereAuth[] = ['all_type', '=', 'allVillages'];
        $deviceId = $deviceAuth->getOneColumn($whereAuth, 'device_id');
        if(!$deviceId){
            //不符合权限
            (new OwnerService())->updateLog(['log_id'=>$logId],[
                'status'=>2,
                'msg'=>"设备信息获取失败",
                'last_time'=>time()
            ]);
            return false;
        }

        $paramData = [
            'pigcms_id'     => $userBindInfo['pigcms_id'],
            'device_id'     => $device['device_id'],
            'room_id'       => $userBindInfo['vacancy_id'],
            'uid'           => isset($userBindInfo['uid']) ? $userBindInfo['uid'] : 0,
            'village_id'    => $device['village_id'],
            'thirdProtocol' => $thirdProtocol,
            'deviceType'    => DeviceConst::DEVICE_TYPE_FACE,
            'step_num'      => $step_num,
            'device_sn'      => $device['device_sn'],
            'log_id'      => $logId,
        ];
        $paramData['jobType'] = 'addPersonsToCloud';
        $job_id = $this->traitCommonDHCloudPersons($paramData);

        $paramData = $this->filterCommonParam($paramData);
        if (isset($job_id)) {
            $paramData['job_id']     = $job_id;
        }
        $this->step_num          = $step_num;
        $this->record_bind_type  = DeviceConst::BIND_FACE_BIND_USER_BIND_CARD;
        $this->record_bind_id    = $userBindInfo['pigcms_id'];
        $this->syn_status        = DeviceConst::BIND_USER_TO_DEVICE_SYN_QUEUE;
        $this->syn_status_txt    = '下发住户同步设备队列';
        $this->err_reason        = "";
        $this->line_func_txt_arr = ['line' => __LINE__, 'funs' => __FUNCTION__, 'param' => $paramData];
        $this->recordDeviceBindFilterBox($paramData);
        return true;
    }
    
    /**
     * A185添加人脸
     */
    public function a185CreateFaceRecord($device,$userBindInfo,$logId)
    {
        fdump_api(['添加或者编辑人脸>>'.__LINE__,$device['device_id'],$userBindInfo['pigcms_id'],$device['village_id'],[] ,[],[],0,''],'a185/a185_create_face',1);
        $cmd_name = 'addPerson';
        $where_repeat = [
            'device_id' => $device['device_id'],
            'bind_id' => $userBindInfo['pigcms_id'],
            'cmd_name' => $cmd_name,
            'status' => 0,
        ];
        $repeat_id = (new FaceA185RequestRecord())->where($where_repeat)->value('id');
        if ($repeat_id) {
            // 直接将之前未执行的命令改为已执行
            (new FaceA185RequestRecord())->where(['id' => $repeat_id])->update(['status' => 1, 'update_time' => time()]);
        }
        $img_data='';
        $face_id=0; //使用中的人脸图片id
        if($userBindInfo['uid']>0){
            $d_house_face_img = (new HouseFaceImg());
            $face_img_where=[
                ['uid','=',$userBindInfo['uid']],
                ['status','in',[0,3]],
                ['img_url','<>','']
            ];
            $house_face_img = $d_house_face_img->where($face_img_where)->order('id asc')->field('id,img_url,status')->select();
            if(!empty($house_face_img)){
                $image_info=$house_face_img[0];
                $img_url = $image_info['img_url'];
                if (3==$image_info['status']) {
                    $word_encryption=new wordencryption();
                    // 加密的图片解密
                    $img_url = $word_encryption->text_decrypt($img_url);
                }
                try {
                    $img_url = thumb_img($img_url,600,600,'');
                    $img_url = (new ImageService())->transform_image($img_url);
                    $file_handle = new FileHandle();
                    if($file_handle->check_open_oss()){
                        $img_url = thumb_img($img_url,600,600,'');
                    }
                    $img_data = base64_encode(file_get_contents($img_url));
                    $face_id = $image_info['id'];
                } catch( \Exception $e) {
                    $img_data = '';
                }
            }
        }
        $userTimes =  (new VisitorInviteService())->getUserEndTime($userBindInfo['pigcms_id']);
        if (isset($userTimes['propertyEndTime'])) {
            $e_time = intval($userTimes['propertyEndTime']);
        } else {
            $e_time = 0;
        }
        if (isset($userTimes['propertyStartTime'])) {
            $s_time = intval($userTimes['propertyStartTime']);
        } elseif ($e_time && $e_time>time()) {
            $s_time = time();
        } elseif ($e_time) {
            $s_time = $e_time-86400*30; // 如果结束时间小于当前时间 且 无开始时间  开始时间默认回退1个月
        } else {
            $s_time = 0;
        }
        if (in_array($userBindInfo['type'],[5,6]) && !$e_time) {
            $this->updateLog(['log_id'=>$logId],[
                'status'=>2,
                'msg'=>L_('访客或者临时出入证无法同步人脸'),
                'last_time'=>time(),
            ]);
            fdump($device['device_id'],'api/log/'.date('Ymd').'/a185/add_create_face_record_err_log',true);
            return false;
        }
        //楼栋
        $single_info = (new HouseVillageSingle())->where(['id' => $userBindInfo['single_id']])->field('single_number')->find();
        //单元
        $floor_info = (new HouseVillageFloor())->where(['floor_id' => $userBindInfo['floor_id']])->field('floor_number')->find();
        // 楼层
        $layer_info = (new HouseVillageLayer())->where(['id' => $userBindInfo['layer_id']])->field('layer_number')->find();
        // 房屋编号
        $vacancy_info = (new HouseVillageUserVacancy())->field('room_number')->where(['pigcms_id' => $userBindInfo['vacancy_id']])->find();

        if(in_array($userBindInfo['type'],[0,1,2,3])){
            if(empty($single_info['single_number']) || empty($floor_info['floor_number']) || empty($layer_info['layer_number']) || empty($vacancy_info['room_number'])){
                $msg='请先填写对应';

                if(empty($single_info['single_number'])){
                    $msg.='【楼栋编号】';
                }
                if(empty($floor_info['floor_number'])){
                    $msg.='【单元编号】';
                }
                if(empty($layer_info['layer_number'])){
                    $msg.='【楼层编号】';
                }
                if(empty($vacancy_info['room_number'])){
                    $msg.='【房间编号】';
                }
                $data_set = [
                    'face_img_status' => 2,
                    'face_img_reason' =>$msg,
                ];
                (new HouseVillageUserBind())->where(['pigcms_id' => $userBindInfo['pigcms_id']])->data($data_set)->save();
                $this->updateLog(['log_id'=>$logId],[
                    'status'=>2,
                    'msg'=>$msg,
                    'last_time'=>time(),
                ]);
                fdump([
                    'bind_info'=>$userBindInfo,
                    'single'=>$single_info,
                    'floor'=>$floor_info,
                    'layer'=>$layer_info,
                    'vacancy'=>$vacancy_info
                ],'api/log/'.date('Ymd').'/a185/info',true);
                return false;
            }
        }

        if(!in_array($userBindInfo['type'],[0,1,2,3])){
            $single_info['single_number']   = '00';
            $floor_info['floor_number']     = '00';
            $layer_info['layer_number']     = '00';
            $vacancy_info['room_number']    = '00';
            $dial_room='0000';
            $dial_number='00000000';
        }
        else{
            //针对房间编号格式为0001 拼接成楼层编号和房间编号 0201
            $room_number=$vacancy_info['room_number'];
            if(intval($vacancy_info['room_number']) < 100){
                $vacancy_info['room_number'] = str_pad(intval($vacancy_info['room_number']), 2, "0", STR_PAD_LEFT);
            }
            if(strlen($vacancy_info['room_number']) == 1){
                $dial_room=$layer_info['layer_number'].'0'.$vacancy_info['room_number'];
                $dial_number=$single_info['single_number'].$floor_info['floor_number'].$layer_info['layer_number'].'0'.$vacancy_info['room_number'];
            }
            elseif (strlen($vacancy_info['room_number']) == 2){
                $dial_room=$layer_info['layer_number'].$vacancy_info['room_number'];
                $dial_number=$single_info['single_number'].$floor_info['floor_number'].$layer_info['layer_number'].$vacancy_info['room_number'];
            }
            elseif (strlen($vacancy_info['room_number']) == 3){
                $dial_room='0'.$vacancy_info['room_number'];
                $dial_number=$single_info['single_number'].$floor_info['floor_number'].'0'.$vacancy_info['room_number'];
            }
            elseif (strlen($vacancy_info['room_number']) == 4){
                $dial_room=$vacancy_info['room_number'];
                $dial_number=$single_info['single_number'].$floor_info['floor_number'].$vacancy_info['room_number'];
            }
            else{
                $dial_room=$vacancy_info['room_number'];
                $dial_number=$single_info['single_number'].$floor_info['floor_number'].$vacancy_info['room_number'];
            }
            if($room_number != $dial_room){
                (new HouseVillageUserVacancy())->where(['pigcms_id' => $userBindInfo['vacancy_id']])->update(['room_number'=>$dial_room]);
                fdump_api(['编辑房间编号>>'.__LINE__,(new HouseVillageUserVacancy())->getLastSql(),$room_number,$dial_room],'a185/a185_edit_room_number',1);
            }
            $db_user_set=(new UserSet())->where(['uid'=>$userBindInfo['uid']])->value('allow_cloud_calls');
            //todo 是否允许用户被呼叫
            $is_oncalled=1;
            if($db_user_set && $db_user_set == 2){
                $is_oncalled=2;
            }
            // 整合需要添加的数据
            $add_data = [
                'village_id' => $device['village_id'],
                'device_sn' => $device['device_sn'],
                'device_id' => $device['device_id'],
                'bind_id' => $userBindInfo['pigcms_id'],
                'operator_type' => 1,
                'operator_id' => $device['village_id'],
                'cmd_name' => $cmd_name,
                'version' => 1,
                'status' => 0,
                'add_time' => time(),
                'face_id'=>$face_id,
                'sort'=>70,
                'log_id'=>$logId
            ];
            $add_id = (new FaceA185RequestRecord())->insertGetId($add_data);
            if ($add_id) {
                $ret_data['data'] = $add_id;
                $data_msg=array(
                    'cmd'=>$cmd_name,
                    'version'=>1,
                    'index'=>$add_id,
                    'data'=>array(
                        'person_id'=>$userBindInfo['pigcms_id'],
                        'name'=>$userBindInfo['name'],
                        'phone'=>$userBindInfo['phone'],
                        'ic_card'=>!empty($userBindInfo['ic_card']) ? ((string)$userBindInfo['ic_card']) : '0',
                        'id_card'=>trim($userBindInfo['id_card']),
                        'face_id'=>$face_id,
                        'face_img'=>$img_data,
                        'img_type'=>'jpg',
                        'start_time'=>($s_time && $e_time) ? $s_time : 0,
                        'end_time'=>($s_time && $e_time) ? $e_time : 0,
                        'type'=>$userBindInfo['type'],
                        'is_oncalled'=>$is_oncalled,
                        'room_id'=>$userBindInfo['vacancy_id'],
                        'single_number'=>$single_info['single_number'],
                        'floor_number'=>$floor_info['floor_number'],
                        'layer_number'=>$layer_info['layer_number'],
                        'room_number'=>$vacancy_info['room_number'],
                        'dial_room'=>$dial_room,
                        'dial_number'=>$dial_number,
                        'indoor_number'=>''
                    )
                );
                (new FaceA185RequestRecord())->where(['id' => $add_id])->update([
                    'data'=>serialize($data_msg)
                ]);
                return true;
            }
            else {
                $this->updateLog(['log_id'=>$logId],[
                    'status'=>2,
                    'msg'=>L_('人脸同步失败'),
                    'last_time'=>time(),
                ]);
                fdump(['添加失败--'.__LINE__,(new FaceA185RequestRecord())->getLastSql()],'api/log/'.date('Ymd').'/a185/add_create_face_record_err_log',true);
                return false;
            }
        }
    }

    /**
     * D3添加人脸
     */
    public function d3CreateFaceRecord($device, $userBindInfo,$logId)
    {
        $device_id = $device['device_id'];
        $pigcms_id = $userBindInfo['pigcms_id'];
        $device_sn = $device['device_sn'];
        $village_id = $device['village_id'];
        $where_face_bind_about = [
            'bind_type' => 0,
            'bind_id' => $pigcms_id,
            'device_type' => 23,
            'device_id' => $device_id,
        ];
        $bind_user = (new FaceUserBindDevice())->where($where_face_bind_about)->find();
        if ($bind_user) {
            // 如果存在同步过数据 即为编辑
            // 具体请求区分名称
            $cmd_name = 'update person jpg';
        } else {
            // 未同步过进行添加
            $cmd_name = 'add person jpg';
        }
        $where_repeat = [
            'device_id' => $device_id,
            'bind_id' => $pigcms_id,
            'cmd_name' => $cmd_name,
            'status' => 0,
        ];
        $repeat_id = (new FaceD3RequestRecord())->where($where_repeat)->value('id');
        if ($repeat_id) {
            // 直接将之前未执行的命令改为已执行
            (new FaceD3RequestRecord())->where(['id' => $repeat_id])->update(['status' => 1, 'update_time' => time()]);
        }
        
        
        $operator_type = 0;
        $operator_id = $userBindInfo['pigcms_id'];
        $version = 1;
        $status = 0;
        $add_time = time();
        $img_data = '';

        // 获取下图片
        if ($userBindInfo['uid']>0) {
            $d_house_face_img = (new HouseFaceImg());
            $image_info = $d_house_face_img->where([['uid','=',$userBindInfo['uid']],['status','in', [0,3]],['img_url','<>','']])->order('add_time ASC')->find();
            $img_url = $image_info['img_url'];
            if (3==$image_info['status']) {
                $word_encryption=new wordencryption();
                // 加密的图片解密
                $img_url = $word_encryption->text_decrypt($img_url);
            }
            $img_url = thumb_img($img_url,600,600,'');
            $img_url = (new ImageService())->transform_image($img_url);
            $file_handle = new FileHandle();
            if($file_handle->check_open_oss()){
                $img_url = thumb_img($img_url,600,600,'');
            }
            $img_data = base64_encode(file_get_contents($img_url));
            $face_id = $image_info['id'];
        }

        $property_starttime = 0;
        $property_endtime = 0;
        if (!in_array($userBindInfo['type'],[0,3,4,5,6]) && intval($userBindInfo['parent_id']) > 0) {
            // 查询业主
            $parent_user = (new HouseVillageUserBind())->where(array('pigcms_id'=>$userBindInfo['parent_id']))->find();
            if ($parent_user['property_endtime']>0) {
                $property_endtime = $parent_user['property_endtime'];
            } else {
                $property_endtime = 0;
            }
            if ($parent_user['property_starttime']>0) {
                $property_starttime = $parent_user['property_starttime'];
            } else {
                $property_starttime = 0;
            }
        }
        elseif ($userBindInfo['type']!=4) {
            $property_starttime = $userBindInfo['property_starttime']?$userBindInfo['property_starttime']:0;
            $property_endtime = $userBindInfo['property_endtime']?$userBindInfo['property_endtime']:0;
        }
        if (in_array($userBindInfo['type'],[5,6]) && !$property_endtime) {
            $this->updateLog(['log_id'=>$logId],[
                'status'=>2,
                'msg'=>L_('访客或者临时出入证无法同步人脸'),
                'last_time'=>time(),
            ]);
            fdump($device_id,'api/log/'.date('Ymd').'/d3/add_create_face_record_err_log',true);
            return false;
        }

        $s_time = 0;
        $e_time = 0;
        if ($property_endtime>0 && in_array($userBindInfo['type'],[0,1,2,3])) {
            if ($property_starttime>0) {
                $s_time = $property_starttime;
            } else {
                $s_time = time();
            }
            $e_time = $property_endtime;
            $where = [
                'village_id' => $village_id
            ];
            $village_info = (new HouseVillage())->where($where)->field('owe_property_open_door, owe_property_open_door_day')->find();
            if(isset($userBindInfo['open_door_day']) && $userBindInfo['open_door_day']>0){
                $village_info['owe_property_open_door_day']=$userBindInfo['open_door_day'];
            }
            if ($village_info['owe_property_open_door']==1 && intval($village_info['owe_property_open_door_day'])>0) {
                $e_time += intval($village_info['owe_property_open_door_day']) * 86400;
            }
        }

        $img_=$img_data ? $img_data : '';
        if(empty($img_)){
            fdump(['$img_不存在',$img_data],'api/log/'.date('Ymd').'/d3/add_create_face_record',true);
            $data_set = [
                'face_img_status' => 2,
                'face_img_reason' => '用户缺少上传头像',
            ];
            (new HouseVillageUserBind())->where(array('pigcms_id' => $pigcms_id))->data($data_set)->save();
            $this->updateLog(['log_id'=>$logId],[
                'status'=>2,
                'msg'=>L_('用户缺少上传头像'),
                'last_time'=>time(),
            ]);
            return false;
        }
        $data_msg=array(
            'version'=>$version,
            'cmd'=>$cmd_name,
            'id'=>$userBindInfo['pigcms_id'],
            'name'=>$userBindInfo['name'],
            'role'=>1,
            'cover'=>false,
            'kind'=>0,
            'feature_unit_size'=>0,
            'feature_num'=>0,
            'feature_data'=>[],
            'image_num'=>1,
            'reg_images'=>array(
                array(
                    'format'=>'jpg',
                    'image_data'=>$img_
                )
            ),
            'norm_image_num'=>1,
            'norm_images'=>array(
                array(
                    'width'=>150,
                    'height'=>150,
                    'image_data'=>$img_
                )
            ),
            'customer_text'=>''
        );
        if ($userBindInfo['ic_card']) {
            $data_msg['long_card_id'] = intval($userBindInfo['ic_card']); // 韦根号,智能卡号(可选)
        }
        if ($s_time && $e_time) {
            $data_msg['term_start'] = date('Y/m/d H:i:s',$s_time);// 启用日期时间戳(可选)
            $data_msg['term'] = date('Y/m/d H:i:s',$e_time); // 有效日期时间戳(可选)
        }
        // 整合需要添加的数据
        $add_data = [
            'village_id' => $village_id,
            'device_sn' => $device_sn,
            'device_id' => $device_id,
            'bind_id' => $pigcms_id,
            'operator_type' => $operator_type,
            'operator_id' => $operator_id,
            'cmd_name' => $cmd_name,
            'version' => $version,
            'status' => $status,
            'data' => serialize($data_msg),
            'add_time' => $add_time,
        ];
        $add_id = (new FaceD3RequestRecord())->insertGetId($add_data);
        if ($add_id) {
            $ret_data['data'] = $add_id;
            $this->updateLog(['log_id'=>$logId],[
                'status'=>1,
                'msg'=>'',
                'last_time'=>time(),
            ]);
            return $ret_data;
        }
        else {
            fdump('>>>>>>>>>>>>>>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/d3/add_create_face_record_err_log',true);
            $ret_data['error'] = 1;
            $ret_data['msg'] = '添加失败';
            $this->updateLog(['log_id'=>$logId],[
                'status'=>2,
                'msg'=>L_('人脸同步失败'),
                'last_time'=>time(),
            ]);
            return $ret_data;
        }
    }

    /**
     * D4添加人脸
     */
    public function D4CreateFaceRecord($device, $userBindInfo,$logId)
    {
        $pigcms_id = $userBindInfo['pigcms_id'];
        $village_id = $device['village_id'];
        $device_id = $device['device_id'];
        $where_face_bind_about = [
            'bind_type' => 0,
            'bind_id' => $pigcms_id,
            'device_type' => 24,
            'device_id' => $device_id,
        ];
        $bind_user = (new FaceUserBindDevice())->where($where_face_bind_about)->find();
        if ($bind_user) {
            // 如果存在同步过数据 即为编辑
            // 具体请求区分名称
            $cmd_name = 'update_person';
        } else {
            // 未同步过进行添加
            $cmd_name = 'add_person';
        }
        $device_sn = $device['device_sn'];
        $operator_type = 0;
        $operator_id = $pigcms_id;
        $status = 0;
        $add_time = time();
        $img_url='';
        $e_time=0;
        // 获取下图片
        if($userBindInfo['uid']>0){
            $image_info = (new HouseFaceImg())->where([['uid','=',$userBindInfo['uid']],['status','in',[0,3]],['img_url','<>','']])->order('add_time ASC')->find();
            $img_url = $image_info['img_url'];
            if (3==$image_info['status']) {
                $word_encryption=new wordencryption();
                // 加密的图片解密
                $img_url = $word_encryption->text_decrypt($img_url);
            }
            $img_url = thumb_img($img_url,600,600,'');
            $img_url = (new ImageService())->transform_image($img_url);
            $file_handle = new FileHandle();
            if($file_handle->check_open_oss()){
                $img_url = thumb_img($img_url,600,600,'');
            }
            $img_data = base64_encode(file_get_contents($img_url));
            $face_id = $image_info['id'];
        }
        if(empty($img_url)){
            $data_set = [
                'face_img_status' => 2,
                'face_img_reason' =>'请上传图片',
            ];
            (new HouseVillageUserBind())->where(['pigcms_id' => $userBindInfo['pigcms_id']])->update($data_set);
            fdump(['没有图片',$userBindInfo],'api/log/'.date('Ymd').'/d4/face_img_log',true);
            if($logId){
                $this->updateLog(['log_id'=>$logId],[
                    'status'=>2,
                    'msg'=>L_('没有图片'),
                    'last_time'=>time()
                ]);
            }
            return false;
        }

        $userTimes =  (new VisitorInviteService())->getUserEndTime($userBindInfo['pigcms_id']);
        if (isset($userTimes['propertyEndTime'])) {
            $e_time = intval($userTimes['propertyEndTime']);
        } else {
            $e_time = 0;
        }
        if (in_array($userBindInfo['type'],[5,6]) && !$e_time) {
            fdump($device_id,'api/log/'.date('Ymd').'/d4/add_create_face_record_err_log',true);
            if($logId){
                $this->updateLog(['log_id'=>$logId],[
                    'status'=>2,
                    'msg'=>L_('游客或者临时出入证无法同步'),
                    'last_time'=>time()
                ]);
            }
            return false;
        }

        if ($userBindInfo['ic_card']) {
            $data_msg['long_card_id'] = intval($userBindInfo['ic_card']); // 韦根号,智能卡号(可选)
        }
        $endTime=date('Y-m-d H:i:s',2145888000);
        if(!empty($e_time)){
            $endTime=date('Y-m-d H:i:s',$e_time);
        }

        /**
         * d4 添加人员
         */
        $rr=(new D4FaceDeviceService())->addPerson($device['device_sn'],array(
            array(
                'guid'=>$pigcms_id,
                'url'=>$img_url,
                'name'=>$userBindInfo['name'],
                'endTime'=>$endTime
            )
        ));
        $save_bind = [];
        $remark='';
        if($rr && isset($rr['ret'])){
            $remark=json_encode($rr['ret'],JSON_UNESCAPED_UNICODE);
        }elseif($rr){
            $remark=json_encode($rr,JSON_UNESCAPED_UNICODE);
        }
        if($rr['error']){
            $save_bind['face_img_status'] = 1;
            /**
             * todo d4新增卡片
             */
            if(intval($userBindInfo['ic_card']) > 0 && $endTime){
                //todo 删除卡号
                (new D4FaceDeviceService())->delCard($device['device_sn'],$userBindInfo['ic_card']);
                //todo 添加新的卡号
                (new D4FaceDeviceService())->addCard($device['device_sn'],$userBindInfo['ic_card'],$endTime);
            }
        }
        else{
            $save_bind['face_img_status'] = 2;
            $save_bind['face_img_reason'] = $rr['data'];
        }

        if($bind_user){
            $data_save = [
                'person_id' =>$pigcms_id,
                'last_time' => time()
            ];
            (new FaceUserBindDevice())->where(['id'=>$bind_user['id']])->update($data_save);
        }else{
            $data_face_bind_about = [
                'bind_type' => 0,
                'bind_id' => $pigcms_id,
                'device_id' => $device_id,
                'device_type' => 24,
                'person_id' =>  $pigcms_id,
                'add_time' => time()
            ];
            (new FaceUserBindDevice())->insert($data_face_bind_about);
        }


        // 整合需要添加的数据
        $add_data = [
            'village_id' => $village_id,
            'device_sn' => $device_sn,
            'device_id' => $device_id,
            'bind_id' => $pigcms_id,
            'operator_type' => $operator_type,
            'operator_id' => $operator_id,
            'cmd_name' => $cmd_name,
            'status' => $status,
            'data' => serialize($rr),
            'add_time' => $add_time,
            'remark'=>$remark
        ];
        $add_id = (new FaceD4RequestRecord())->insertGetId($add_data);
        if ($add_id) {
            $ret_data['data'] = $add_id;
            $db_House_village_user_bind=(new HouseVillageUserBind());
            $db_House_village_user_bind->where(['pigcms_id' => $pigcms_id])->update($save_bind);
            if($logId){
                $this->updateLog(['log_id'=>$logId],[
                    'status'=>1,
                    'msg'=>'',
                    'last_time'=>time()
                ]);
            }
            return $ret_data;
        }
        else {
            fdump('>>>>>>>>>>>>>>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/d4/add_create_face_record_err_log',true);
            $ret_data['error'] = 1;
            $ret_data['msg'] = '同步失败';
            if($logId){
                $this->updateLog(['log_id'=>$logId],[
                    'status'=>2,
                    'msg'=>L_('添加失败'),
                    'last_time'=>time()
                ]);
            }
            return $ret_data;
        }
    }
    
    /**
     * Notes: 添加计划任务
     */
    public function add_plan($param,$file = 'sub_house_village_face_device', $plan_time=0,$data=[]){
        if (!$param['type']) {
            // 需要传值type 否则直接返回
            return false;
        }
        $unique_id = $param['type'];
        // 处理下字段问题-如果存在拼接到唯一键值上
        if (isset($param['village_id'])) {
            $unique_id .='_'.$param['village_id'];
        }
        if (isset($param['communityId'])) {
            $unique_id .='_'.$param['communityId'];
        }
        if (isset($param['zoneId'])) {
            $unique_id .='_'.$param['zoneId'];
        }
        if (isset($param['single_id'])) {
            $unique_id .='_'.$param['single_id'];
        }
        if (isset($param['buildingId'])) {
            $unique_id .='_'.$param['buildingId'];
        }
        if (isset($param['vacancy_id'])) {
            $unique_id .='_'.$param['vacancy_id'];
        }
        if (isset($param['roomId'])) {
            $unique_id .='_'.$param['roomId'];
        }
        if (isset($param['floor_id'])) {
            $unique_id .='_'.$param['floor_id'];
        }
        if (isset($param['unitId'])) {
            $unique_id .='_'.$param['unitId'];
        }
        if (isset($param['bind_id'])) {
            $unique_id .='_'.$param['bind_id'];
        }
        if (isset($param['userId'])) {
            $unique_id .='_'.$param['userId'];
        }
        if (isset($param['device_id'])) {
            $unique_id .='_'.$param['device_id'];
        }

        $now_time = time();
        $arr= array();
        $arr['param'] = serialize($param);
        $arr['plan_time'] = $plan_time;
        $arr['space_time'] = 0;
        $arr['add_time'] = $now_time;
        $arr['file'] = $file;
        if (!isset($param['time_type'])) {
            $arr['time_type'] = 1;
        } else {
            $arr['time_type'] = $param['time_type'];
        }
        if (!isset($param['unique_id'])) {
            $arr['unique_id'] = $unique_id;
        } else {
            $arr['unique_id'] = $param['unique_id'];
        }
        $arr['rand_number'] = mt_rand(1, max(cfg('sub_process_num'), 3));
        //先查询有未同步完成的记录
        $info = (new ProcessSubPlan())->where('unique_id',$arr['unique_id'])->count();
        if($info>0){//有未完成的记录，不新增记录
            return ['task_id'=>0];
        }
        $task_id = (new ProcessSubPlan())->insertGetId($arr);
        if (!$task_id) {
            fdump_api([$task_id, $arr, (new ProcessSubPlan())->getLastSql()], 'v20Face/add_sub_plan_err', 1);
        }
        return array('task_id'=>$task_id);
    }

    /**
     * 处理人脸下发人脸设备日志.
     */
    public function handleHouseFaceSyncLog($params) {
        $dbHouseFaceSyncLog = new HouseFaceSyncLog();
        $pigcms_id = isset($params['pigcms_id']) ? intval($params['pigcms_id']) : 0;
        $device_id = isset($params['device_id']) ? intval($params['device_id']) : 0;
        $device_sn = isset($params['device_sn']) ? trim($params['device_sn']) : '';
        $nowTime = isset($params['nowTime']) && $params['nowTime'] ? intval($params['nowTime']) : time();
        $where = [];
        $where[] = ['pigcms_id', '=', $pigcms_id];
        if ($device_id) {
            $where[] = ['device_id', '=', $device_id];
        } else {
            $where[] = ['device_sn', '=', $device_sn];
        }
        $syncLogInfo = $dbHouseFaceSyncLog->getOne($where, 'log_id');
        $log_id = isset($syncLogInfo['log_id']) && $syncLogInfo['log_id'] ? intval($syncLogInfo['log_id']) : 0;
        if ($log_id > 0) {
            $update = ['pigcms_id' => $pigcms_id, 'last_time' => $nowTime];
            if (isset($params['device_id'])) {
                $update['device_id'] = intval($params['device_id']);
            }
            if (isset($params['device_sn'])) {
                $update['device_sn'] = trim($params['device_sn']);
            }
            if (isset($params['face_id'])) {
                $update['face_id'] = intval($params['face_id']);
            }
            if (isset($params['log_time'])) {
                $update['log_time'] = intval($params['log_time']);
            }
            if (isset($params['status'])) {
                $update['status'] = intval($params['status']);
            }
            if (isset($params['msg'])) {
                $update['msg'] = trim($params['msg']);
            }
            $device_info = isset($params['device_info']) && $params['device_info'] ? $params['device_info'] : [];
            if (!empty($device_info)) {
                $update['device_info'] = json_encode($device_info, JSON_UNESCAPED_UNICODE);
            }
            if (isset($params['device_cmd_id'])) {
                $update['device_cmd_id'] = $params['device_cmd_id'];
            }
            
            $dbHouseFaceSyncLog->updateThis(['log_id' => $log_id], $update);
        } else {
            $face_id = isset($params['face_id']) ? intval($params['face_id']) : 0;
            $log_time = isset($params['log_time']) ? intval($params['log_time']) : 0;
            $status = isset($params['status']) ? intval($params['status']) : 0;
            $msg = isset($params['msg']) ? trim($params['msg']) : '';
            $device_info = isset($params['device_info']) && $params['device_info'] ? $params['device_info'] : [];
            $data = [
                'pigcms_id' => $pigcms_id, 'face_id' => $face_id, 'device_id' => $device_id,
                'device_sn' => $device_sn, 'log_time' => $log_time, 'status' => $status,
                'msg' => $msg, 'last_time' => $nowTime, 'device_info' => json_encode($device_info, JSON_UNESCAPED_UNICODE),
            ];
            if (isset($params['device_cmd_id'])) {
                $data['device_cmd_id'] = $params['device_cmd_id'];
            }
            $log_id = $dbHouseFaceSyncLog->add($data);
        }
        return $log_id;
    }
}