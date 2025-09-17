<?php


namespace app\community\model\service\Device;


use app\common\model\db\UserScoreList;
use app\common\model\service\send_message\SmsService;
use app\common\model\service\UserService;
use app\common\model\service\weixin\TemplateNewsService;
use app\community\model\db\Device\GarbageCollectionMachineList;
use app\community\model\db\Device\GarbageCollectionPutList;
use app\community\model\db\Device\GarbageCollectionWarnList;
use app\community\model\db\FaceUserBindDevice;
use app\community\model\db\HouseMaintenanWorker;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageUserBind;
use app\community\model\db\User;
use app\community\model\service\HouseMaintenanWorkerService;
use app\community\model\service\HouseVillageService;
use app\community\model\service\HouseWorkerService;
use app\consts\Cloud260Const;
use app\consts\DeviceConst;
use app\traits\house\DeviceTraits;
use app\traits\third\GarbageCollectionTraits;
use garbage_collection\GarbageCollection;
use think\facade\Db;

class GarbageCollectionMachineService
{
    use GarbageCollectionTraits;
    use DeviceTraits;
    /**
     * 设备列表
     */
    public function machineList($param)
    {
        $where = [];
        $field= 'ml.id,ml.device_no,ml.name,ml.type,ml.address,ml.status,ml.add_time,ml.village_id,v.village_name';
        $order = 'ml.add_time desc';
        if($param['device_no']){
            $where[] = ['ml.device_no','=',$param['device_no']];
        }
        if($param['name']){
            $where[] = ['ml.name','like','%'.$param['name'].'%'];
        }
        if($param['type']){
            $where[] = ['ml.type','=',$param['type']];
        }
        if($param['status']){
            $where[] = ['ml.status','=',$param['status']];
        }
        if($param['village_id']){
            $where[] = ['ml.village_id','=',$param['village_id']];
        }
        $list = (new GarbageCollectionMachineList())->getList($where,$field,$order,$param['pageSize']);
        foreach ($list['data'] as &$item){
            $item['add_time'] = date('Y-m-d H:i:s',$item['add_time']);
            $item['type_str'] = (new GarbageCollectionMachineList())->getTypeText($item['type']);
            $item['status_str'] = (new GarbageCollectionMachineList())->getStatusText($item['status']);
            $item['village_name'] = $item['village_name'] ? : '';
        }
        return $list;
    }
    /**
     * 设备详情
     */
    public function machineDetail($param)
    {
        if(!$param['id']){
            throw_exception(L_('查询对象不能为空'));
        }
        $detail = (new GarbageCollectionMachineList())->getDetail(['id'=>$param['id']],'id,device_no,name,type,address,longitude,latitude,agent_name,agent_phone,add_time,status,village_id');
        if($detail){
            $detail['type_str'] = (new GarbageCollectionMachineList())->getTypeText($detail['type']);
            $detail['add_time'] = date('Y-m-d H:i:s',$detail['add_time']);
            $detail['status_str'] = (new GarbageCollectionMachineList())->getStatusText($detail['status']);
            $detail['village_name'] = $detail['village_id'] ? (new HouseVillage())->where(['village_id'=>$detail['village_id']])->value('village_name') : '';
        }
        return $detail;
    }
    /**
     * 设备绑定/解绑小区
     */
    public function machineBind($param)
    {
        if(!$param['id']){
            throw_exception(L_('操作对象不能为空'));
        }
        if($param['status']==-1){
            throw_exception(L_('绑定关系异常'));
        }
        if($param['status']==1 && !$param['village_id']){
            throw_exception(L_('绑定小区不能为空'));
        }
        $info = (new GarbageCollectionMachineList())->getDetail(['id'=>$param['id']],'id,village_id,device_village_id,device_no,type');
        if(!$info){
            throw_exception(L_('操作对象不存在'));
        }
        if(!$info['device_village_id']){
            throw_exception(L_('请先于设备平台绑定小区，然后拉取设备数据后操作'));
        }
        if($param['status']==0 && !$info['village_id']){
            throw_exception(L_('解绑失败，设备未绑定小区'));
        }
        if($param['status']==1 && $info['village_id']==$param['village_id']){
            throw_exception(L_('绑定失败，设备已绑定选定小区'));
        }
        if($param['status']==1 && $info['village_id']){
            throw_exception(L_('绑定失败，设备已绑定其他小区'));
        }
        if($param['status']){
            $updateData = [
                'village_id'=>$param['village_id'],
                'update_time'=>time()
            ];
            //查询小区是否绑定过其他设备
            $villageBindDevice = (new GarbageCollectionMachineList())->getDetail(['village_id'=>$param['village_id']],'device_village_id');
            if($villageBindDevice && $villageBindDevice['device_village_id']!=$info['device_village_id']){
                throw_exception(L_('该小区不能绑定不同小区的垃圾回收设备'));
            }

            //同步小区全部人脸信息
            if($info){
                $info['village_id'] = $param['village_id'];
            }
            $result = $this->pushVillageAllFace($info);
//            $result = $this->pushUserFace($info);
            if(isset($result['message'])){
                fdump_api(['msg' => $result['message'], 'info' => $info], 'garbageCollection/machineBindErr',1);
            }
        }else{
            $updateData = [
                'village_id'=>0,
                'update_time'=>time()
            ];
        }
        //绑定小区id
        $update = (new GarbageCollectionMachineList())->where('id',$param['id'])->update($updateData);
        if(!$update){
            throw_exception(L_('操作失败'));
        }
        return true;
    }
    /**
     * 同步整个小区的人脸信息到垃圾回收设备
     */
    public function pushVillageAllFace($params)
    {
        fdump_api(['msg' => '发起一键同步', 'params' => $params], 'garbageCollection/pushVillageAllFace');
        $village_id = isset($params['village_id']) && $params['village_id'] ? intval($params['village_id']) : 0;
        $device_village_id = isset($params['device_village_id']) && $params['device_village_id'] ? trim($params['device_village_id']) : '';
        if (! $village_id && ! $device_village_id) {
            fdump_api(['msg' => '发起一键同步-少参', 'device_village_id' => $device_village_id, 'village_id' => $village_id], 'garbageCollection/pushVillageAllFace',1);
            return [
                'code'    => 1001,
                'message' => '缺少必要参数',
            ];
        }
        $params['job_type'] = 'pushUserFace';
        $job_id = $this->traitCommonGarbageCollection($params);
        fdump_api(['msg' => '发起一键同步-队列', 'job_id' => $job_id, 'params' => $params], 'garbageCollection/pushVillageAllFace',1);
        if ($job_id == -1) {
            return [
                'code'    => 1001,
                'message' => '任务正在等候执行',
            ];
        }
        return $job_id;
    }
    /**
     * 处理队列
     */
    public function handleGarbageCollectionJob($params)
    {
        $job_type = isset($params['job_type']) && $params['job_type'] ? $params['job_type'] : '';
        if (! $job_type) {
            return true;
        }
        try {
            switch ($job_type) {
                case 'pushUserFace':
                    // 一键同步所有
                    $this->pushUserFace($params);
                    break;
                case 'handleUser':
                    // 同步单个人员
                    $this->handleUser($params);
                    break;
                case 'handleUserSynDevice':
                    // 同步单个人员
                    $this->handleUserSynDevice($params);
                    break;
            }
        } catch (\Exception $e) {
            fdump_api(['msg' => '处理队列', 'job_type' => $job_type, 'params' => $params, 'err' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' =>$e->getCode()], 'garbageCollection/handleGarbageCollectionJob',1);
        }
        return true;
    }
    /**
     * 同步人脸信息到设备
     */
    public function pushUserFace($deviceInfo)
    {
        $return = [
            'error'=>0,
            'msg'=>''
        ];
        $villageId = $deviceInfo['village_id'];
        if(!$deviceInfo){
            $return['error'] = 1;
            $return['msg'] = L('设备信息不能为空');
            return $return;
        }
        if(!$villageId){
            $return['error'] = 1;
            $return['msg'] = L('小区id不能为空');
            return $return;
        }
        //获取设备绑定的小区，小区的所有人员
        $field = 'ub.pigcms_id, ub.single_id, ub.floor_id, ub.layer_id,ub.vacancy_id, ub.type, ub.village_id, ub.uid, ub.phone, ub.property_endtime, ub.property_starttime, ub.name, ub.parent_id,ub.ic_card, ub.id_card, ub.user_number,ub.bind_number';
        $userList = (new HouseVillageUserBind())->getGroupUserList([['ub.village_id','=',$villageId],['ub.status','=',1],['ub.uid','>',0]], $field,'ub.phone','ub.pigcms_id ASC');
        if ($userList && ! is_array($userList)) {
            $userList = $userList->toArray();
        }
        if (!$userList) {//没有需要绑定的用户
            return $return;
        }
        
        //对用户做单个处理(小于5个直接处理，否则做队列)
        if (count($userList) < 5) {
            foreach ($userList as $user) {
                $params = [];
                $params['device_type'] = $deviceInfo['type'];
                $params['device_id'] = $deviceInfo['id']??0;
                $params['cmd_id'] = $user['pigcms_id'];
                $params['user'] = $user;
                $params['device_village_id'] = $deviceInfo['device_village_id'];
                $this->handleUser($params);
            }
        } else {
            foreach ($userList as $user) {
                $params = [];
                $params['device_type'] = $deviceInfo['type'];
                $params['device_id'] = $deviceInfo['id']??0;
                $params['cmd_id'] = $user['pigcms_id'];
                $params['device_village_id'] = $deviceInfo['device_village_id'];
                $params['job_type'] = 'handleUser';
                $this->traitCommonGarbageCollection($params);
            }
        }
        return true;
    }
    /**
     * 单个用户同步人脸信息
     */
    public function handleUser($params)
    {
        $device_type = isset($params['device_type']) ? $params['device_type'] : 1;
        $device_id = isset($params['device_id']) ? $params['device_id'] : 0;
        $cmd_id = isset($params['cmd_id']) ? $params['cmd_id'] : $params['pigcms_id'];
        fdump_api(['msg' => '单个用户同步人脸信息', 'cmd_id' => $cmd_id, '$params' => $params], 'garbageCollection/handleUser', 1);
        $user = isset($params['user']) ? $params['user'] : [];
        if ($cmd_id && empty($user)) {
            $user = (new HouseVillageUserBind())->getOne(['pigcms_id' => $cmd_id]);
            if (intval($user['status'] != 1)) {
                // 状态异常
                $whereStatus = [];
                $whereStatus[] = ['pigcms_id','=',$cmd_id];
                (new HouseVillageUserBind())->saveOne($whereStatus, ['face_img_status' => 2, 'face_img_reason' => "目前处于状态异常，无法同步"]);
                return true;
            }
        }
        if(!$user){
            fdump_api(['msg' => '未查询到小区用户绑定信息', 'cmd_id' => $cmd_id], 'garbageCollection/handleUserErr', 1);
            return true;
        }
        $employeePhoto = $this->getUserFaceImg($user['uid']);
        if(!$employeePhoto){//没有人脸照片的用户不用同步处理
            return true;
        }
        
        $village_id = isset($user['village_id']) ? $user['village_id'] : 0;
        $bind_arr = [
            'bind_type'   => DeviceConst::GARBAGE_COLLECTION_BIND_TYPE,
            'bind_id'     => $cmd_id,
            'device_id' => $device_id,
            'device_type' => $device_type,
            'personID' => $user['uid'],
            'group_id' => $user['phone'],
        ];
        $employeeQRCode = '';
        
        //查询是否已经同步过人脸
        $dbFaceUserBindDevice = new FaceUserBindDevice();
        $whereFaceUserBindDevice = [];
        $whereFaceUserBindDevice[] = ['bind_type', '=', DeviceConst::GARBAGE_COLLECTION_BIND_TYPE];
        $whereFaceUserBindDevice[] = ['group_id', '=', $user['phone']];//相同的手机号只能同步一次
        $fieldFaceUserBindDevice = 'id, bind_type, bind_id, device_type, open_key, open_key_time, code, person_id, person_text';
        $infoFaceUserBindDevice = $dbFaceUserBindDevice->getOne($whereFaceUserBindDevice, $fieldFaceUserBindDevice);
        if(!isset($infoFaceUserBindDevice['id'])){
            //未同步过人脸，查询是否该用户同步过260
            $whereFaceUser260BindDevice = [];
            $whereFaceUser260BindDevice[] = ['bind_type', '=', Cloud260Const::CLOUD_260_FACE_USER_BIND_DEVICE_BIND_TYPE];
            $whereFaceUser260BindDevice[] = ['bind_id', '=', $cmd_id];
            $whereFaceUser260BindDevice[] = ['device_type', '=', Cloud260Const::CLOUD_260_DEVICE_TYPE];
            $fieldFaceUserBindDevice = 'id, bind_type, bind_id, device_type, open_key, open_key_time, code, person_id, person_text';
            $infoFaceUserBindDevice260 = $dbFaceUserBindDevice->getOne($whereFaceUser260BindDevice, $fieldFaceUserBindDevice);
            if(isset($infoFaceUserBindDevice260['id'])){
                //260曾经同步过，直接使用他的code
                $employeeQRCode = $infoFaceUserBindDevice260['code'];
                $bind_arr['face_txt'] = $infoFaceUserBindDevice260['face_txt'];
            }
        }
        
        if(!$employeeQRCode){
            // 当前没有二维码 重新生成二维码串
            $cmd = 'selectPassInfo';
            $employeeQRCodeArr = [
                'cmd_id' => $cmd_id,
                'cmd' => $cmd,
                'device_text' => 'garbage',
                'device_type' => $device_type,
                'type' => 'face',
                'time' => time(),
                'unique' => uniqid(),
            ];
            $employeeQRCode = md5(json_encode($employeeQRCodeArr,JSON_UNESCAPED_UNICODE));
        }
        
        $bind_arr['code'] = $employeeQRCode;
        if (isset($employeeQRCodeArr) && ! empty($employeeQRCodeArr)) {
            $bind_arr['face_txt'] = json_encode($employeeQRCodeArr,JSON_UNESCAPED_UNICODE);
        }
        $bind_arr['groupID'] = $village_id;
        $bind_arr['face_status'] = 1;

        //查询人员楼栋号、单元、室号、积分、人脸照片
        $where = ['hvb.pigcms_id'=>$cmd_id];
        $fieldUserVillage = 's.single_name,f.floor_name,l.layer_name,v.village_name,r.room,u.score_count';
        $userVillageInfo = (new HouseVillageUserBind())->getInfo($where, $fieldUserVillage);

        fdump_api(['msg' => '关联记录', 'cmd_id' => $cmd_id, 'bind_arr' => $bind_arr, 'infoFaceUserBindDevice' => $infoFaceUserBindDevice], 'garbageCollection/handleUser', 1);
        $garbageCollection = new GarbageCollection();
        $addData = [
            'type'=>1,//用户账号类型1:手机号2:卡号3:二维码4:RFID
            'account'=>$user['phone'],//用户账号
            'communityId'=>$params['device_village_id'],//设备小区主键
            'name'=>$user['name'],//姓名
            'phone'=>$user['phone'],//联系方式
            'building'=>$userVillageInfo['floor_name'],//楼栋号
            'unit'=>$userVillageInfo['single_name'],//单元
            'room'=>$userVillageInfo['room'],//室号
            'points'=>get_format_number($userVillageInfo['score_count'],1),//积分，保留1位小数，默认0
            'faceUrl'=>$employeePhoto,//人脸照片url
        ];
        if(!isset($infoFaceUserBindDevice['id'])){
            //新增人脸同步
            $data = $garbageCollection->saveUser($addData);
            if(isset($data['code']) && $data['code']==1003){
                //用户已存在，更新即可
                unset($addData['communityId']);
                unset($addData['points']);
                $data = $garbageCollection->editUser($addData);
            }
            if(!isset($data['code']) || $data['code']!=0){
                $errMsg = $data['errMsg']??L_('人脸同步失败');
                $errMsg = 'garbage:'.$errMsg;
                //同步失败
                $whereStatus = [];
                $whereStatus[] = ['pigcms_id','=',$cmd_id];
                (new HouseVillageUserBind())->saveOne($whereStatus, ['face_img_status' => 2, 'face_img_reason' => $errMsg]);
                return true;
            }
            if(isset($data['data']) && $data['data']){
                $bind_arr['person_id'] = $data['data'];
            }
            $bind_arr['face_status'] = 2;
            $bind_arr['add_time'] = time();
            $visitor_param = [
                'cardNo'     => $employeeQRCode,
                'effectTime' => date('Y-m-d H:i:s'),
                'expireTime' => 0,
                'openTimes'  => 0, // 开门总次数 0为不限制
                'surplusOpenTimes' => 0, // 剩余次数
            ];
            $bind_arr['person_text'] = json_encode($visitor_param,JSON_UNESCAPED_UNICODE);
            $addData = $dbFaceUserBindDevice->addData($bind_arr);
            if($addData){
                $whereStatus = [];
                $whereStatus[] = ['pigcms_id','=',$cmd_id];
                (new HouseVillageUserBind())->saveOne($whereStatus, ['face_img_status' => 1,'face_img_reason' => '']);
            }
        }else{//已经同步过的人脸需要更新人脸照片
            //更新人脸
            $updateData = $addData;
            unset($updateData['communityId']);
            unset($updateData['points']);
            $data = $garbageCollection->editUser($updateData);
            if(isset($data['code']) && $data['code']==1000){
                //用户不存在，需要新增用户
                $data = $garbageCollection->saveUser($addData);
            }
            if(!isset($data['code']) || $data['code']!=0){
                $errMsg = $data['errMsg']??L_('人脸同步失败');
                $errMsg = 'garbage:'.$errMsg;
                //同步失败
                $whereStatus = [];
                $whereStatus[] = ['pigcms_id','=',$cmd_id];
                (new HouseVillageUserBind())->saveOne($whereStatus, ['face_img_status' => 2, 'face_img_reason' => $errMsg]);
                return true;
            }
            if(isset($data['data']) && $data['data']){
                $dbFaceUserBindDevice->saveData($whereFaceUserBindDevice,['person_id'=>$data['data']]);
            }
        }
        return true;
    }
    /**
     * 生成二维码
     */
    public function createQrCode($pigcms_id)
    {
        if(!$pigcms_id){
            return [];
        }
        $user = (new HouseVillageUserBind())->getOne(['pigcms_id' => $pigcms_id]);
        //检查是否已经生成
        $dbFaceUserBindDevice = new FaceUserBindDevice();
        $whereFaceUserBindDevice = [];
        $whereFaceUserBindDevice[] = ['bind_type', 'in', [Cloud260Const::CLOUD_260_FACE_USER_BIND_DEVICE_BIND_TYPE, DeviceConst::GARBAGE_COLLECTION_BIND_TYPE]];
        $whereFaceUserBindDevice[] = ['bind_id', '=', $pigcms_id];
        $fieldFaceUserBindDevice = 'id, bind_type, bind_id, device_type, open_key, open_key_time, code, person_id, person_text';
        $infoFaceUserBindDevice = $dbFaceUserBindDevice->getOne($whereFaceUserBindDevice, $fieldFaceUserBindDevice);
        $infoFaceUserBindDevice = ($infoFaceUserBindDevice && ! is_array($infoFaceUserBindDevice)) ? $infoFaceUserBindDevice->toArray() : [];
        if($infoFaceUserBindDevice){
            return $infoFaceUserBindDevice;
        }
        //检查用户的小区是否已经绑定设备
        $deviceId = (new GarbageCollectionMachineList())->where('village_id',$user['village_id'])->value('id');
        if(!$deviceId){
            return [];
        }
        //生成二维码
        $bind_arr = [
            'bind_type'   => DeviceConst::GARBAGE_COLLECTION_BIND_TYPE,
            'bind_id'     => $pigcms_id,
            'device_id' => $deviceId,
            'device_type' => 0,
            'personID' => $user['uid'],
            'group_id' => $user['phone'],
        ];
        // 当前没有二维码 重新生成二维码串
        $cmd = 'selectPassInfo';
        $employeeQRCodeArr = [
            'cmd_id' => $pigcms_id,
            'cmd' => $cmd,
            'device_text' => 'garbage',
            'device_type' => 0,
            'type' => 'face',
            'time' => time(),
            'unique' => uniqid(),
        ];
        $employeeQRCode = md5(json_encode($employeeQRCodeArr,JSON_UNESCAPED_UNICODE));

        $bind_arr['code'] = $employeeQRCode;
        $bind_arr['face_txt'] = json_encode($employeeQRCodeArr,JSON_UNESCAPED_UNICODE);
        $bind_arr['groupID'] = $user['village_id'];
        $bind_arr['person_id'] = 0;
        $bind_arr['face_status'] = 0;
        $bind_arr['add_time'] = time();
        $visitor_param = [
            'cardNo'     => $employeeQRCode,
            'effectTime' => date('Y-m-d H:i:s'),
            'expireTime' => 0,
            'openTimes'  => 0, // 开门总次数 0为不限制
            'surplusOpenTimes' => 0, // 剩余次数
        ];
        $bind_arr['person_text'] = json_encode($visitor_param,JSON_UNESCAPED_UNICODE);
        $addData = $dbFaceUserBindDevice->addData($bind_arr);
        if(!$addData){
            return [];
        }
        return ['code'=>$employeeQRCode,'person_text'=>$bind_arr['person_text']];
    }


    /**
     * 单人同步.
     */
    public function userSynDevice($params) {
        $uid = isset($params['uid']) && $params['uid'] ? intval($params['uid']) : 0;
        $pigcms_id = isset($params['pigcms_id']) && $params['pigcms_id'] ? intval($params['pigcms_id']) : 0;
        fdump_api(['msg' => '单人同步', 'pigcms_id' => $pigcms_id, 'params' => $params], 'garbageCollection/userSynDevice',1);
        if (! $uid && ! $pigcms_id) {
            return [
                'code'    => 1001,
                'message' => '缺少必要参数',
            ];
        }
        $params['job_type'] = 'handleUserSynDevice';
        $job_id = $this->traitCommonGarbageCollection($params);
        fdump_api(['msg' => '单人同步-队列', 'job_id' => $job_id, 'params' => $params], 'garbageCollection/userSynDevice',1);
        if ($job_id == -1) {
            return [
                'code'    => 1001,
                'message' => '任务正在等候执行',
            ];
        }
        return $job_id;
    }
    /**
     * 单人同步.
     */
    public function handleUserSynDevice($params) {
        $uid = isset($params['uid']) && $params['uid'] ? intval($params['uid']) : 0;
        $pigcms_id = isset($params['pigcms_id']) && $params['pigcms_id'] ? intval($params['pigcms_id']) : 0;
        $device_type = isset($params['device_type']) && $params['device_type'] ? intval($params['device_type']) : Cloud260Const::CLOUD_260_DEVICE_TYPE;
        fdump_api(['msg' => '单人同步', 'pigcms_id' => $pigcms_id, 'params' => $params, 'device_type' => $device_type], 'garbageCollection/handleUserSynDevice', 1);
        $dbHouseVillageUserBind = (new HouseVillageUserBind());
        if ($uid) {
            $whereUserBind = [];
            $whereUserBind[] = ['uid', '=', $uid];
            $whereUserBind[] = ['status', '=', 1];
            $field = 'pigcms_id, single_id, floor_id, layer_id,vacancy_id, type, village_id, uid,status, phone, property_endtime, property_starttime, name, parent_id,ic_card, id_card, user_number,bind_number';
            $userList = $dbHouseVillageUserBind->getList($whereUserBind, $field);
            if ($userList && ! is_array($userList)) {
                $userList = $userList->toArray();
            }
        } elseif ($pigcms_id) {
            $whereUserBind = [];
            $whereUserBind[] = ['pigcms_id', '=', $pigcms_id];
            $whereUserBind[] = ['status', '=', 1];
            $field = 'pigcms_id, single_id, floor_id, layer_id,vacancy_id, type, village_id, uid,status, phone, property_endtime, property_starttime, name, parent_id,ic_card, id_card, user_number,bind_number';
            $userList = $dbHouseVillageUserBind->getList($whereUserBind, $field);
            if ($userList && ! is_array($userList)) {
                $userList = $userList->toArray();
            }
        } else {
            fdump_api(['msg' => '单人同步-无同步对象', 'pigcms_id' => $pigcms_id, 'params' => $params], 'garbageCollection/handleUserSynDevice', 1);
            return false;
        }
        fdump_api(['msg' => '单人同步', 'pigcms_id' => $pigcms_id, 'userList' => $userList], 'garbageCollection/handleUserSynDevice', 1);
        $devices = [];
        $dbGarbageDevice = new GarbageCollectionMachineList();
        $paramArr = [];
        $idStatusErr = [];
        try {
            foreach ($userList as $user) {
                $employeePhoto = $this->getUserFaceImg($user['uid']);
                if(!$employeePhoto){//没有人脸照片的用户不用同步处理
                    continue;
                }
                if (intval($user['status']) != 1) {
                    $idStatusErr[] = $user['pigcms_id'];
                    continue;
                }
                $village_id = isset($user['village_id']) && $user['village_id'] ? $user['village_id'] : 0;
                $whereDeviceFace = [];
                $whereDeviceFace[] = ['ml.village_id', '=', $village_id];
                $deviceList = $dbGarbageDevice->getList($whereDeviceFace, 'ml.id, ml.device_no, ml.village_id,ml.device_village_id', 'ml.id desc',0);
                if ($deviceList && ! is_array($deviceList)) {
                    $deviceList = $deviceList->toArray();
                }
                
                $user_floor_id = intval($user['floor_id']);
                foreach ($deviceList as $device) {
                    $param = [];
                    $param['device_type'] = $device_type;
                    $param['device_id'] = $device['id'];
                    $param['cmd_id'] = $user['pigcms_id'];
                    $param['user'] = $user;
                    $param['device_village_id'] = $device['device_village_id'];
                    $paramArr[] = $param;
                }
            }
        } catch (\Exception $e) {
            fdump_api(['userList' => $userList,'err' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()],'garbageCollection/handleUserSynDevice',1);
            return false;
        }
        fdump_api(['msg' => '单人同步', 'pigcms_id' => $pigcms_id, 'paramArr' => $paramArr, 'idStatusErr' => $idStatusErr], 'garbageCollection/handleUserSynDevice', 1);
        if (! empty($idStatusErr)) {
            // 状态异常
            $whereStatus = [];
            $whereStatus[] = ['pigcms_id','in',$idStatusErr];
            $dbHouseVillageUserBind->saveOne($whereStatus, ['face_img_status' => 2, 'face_img_reason' => "目前处于状态异常，无法同步"]);
        }
        if (count($paramArr) < 5) {
            // 小于5条数据直接执行 超出走队列
            foreach ($paramArr as $params) {
                $this->handleUser($params);
            }
        } else {
            foreach ($paramArr as $params) {
                if (isset($params['user'])) {
                    unset($params['user']);
                }
                $params['job_type'] = 'handleUser';
                $this->traitCommonGarbageCollection($params);
            }
        }
        return true;
    }
    public function pullAllMachine($i,$machineAry=[])
    {
        global $machineAry;
        $garbageCollection = new GarbageCollection();
        $machineOnlineAry = $garbageCollection->pullMachine($i,10);
        if($machineOnlineAry){
            $machineAry = $machineAry?:[];
            $machineAry = array_merge($machineOnlineAry,$machineAry);
            $i += 1;
            $this->pullAllMachine($i,$machineAry);
        }elseif(!$machineOnlineAry && $machineOnlineAry!==[]){
            return false;
        }
        return $machineAry;
    }
    /**
     * 设备拉取
     */
    public function pullMachine()
    {
        //设备key=设备编号+设备类型来区分是否是新设备
        
        $onlineInfoAry = [];//线上所有设备信息，以设备key作为key
        $localInfoAry = [];//本地平台所有设备信息，以设备key作为key
        $onlineAry = [];//线上所有设备key
        $localAry = [];//本地平台所有设备key
        
        $addData = [];//需要新增的数据
        $delData = [];//需要删除的设备列表id
        $editData = [];//需要编辑的数据

        //拉取所有设备信息
        $machineOnlineAry = $this->pullAllMachine(0);
        if(!$machineOnlineAry && $machineOnlineAry!==[]){
            throw_exception(L_('拉取失败，接口报错(请检查账号或者密码是否填写错误)'));
        }
        foreach ($machineOnlineAry as $vMO){
            $key = $vMO['deviceNo'].'_'.$vMO['type'];
            $onlineAry[] = $key;
            $onlineInfoAry[$key] = $vMO;
        }
        //获取平台所有的设备信息
        $machineLocalAry = (new GarbageCollectionMachineList())->field('id,device_no,type,content')->select()->toArray();
        foreach ($machineLocalAry as $vML){
            $key = $vML['device_no'].'_'.$vML['type'];
            $localAry[] = $key;
            $localInfoAry[$key] = $vML;
        }
        
        //分别获取需要新增的设备、需要删除的设备、需要编辑的设备
        $addAry = array_diff($onlineAry,$localAry);//需要新增的设备key
        $delAry = array_diff($localAry,$onlineAry);//需要删除的设备key
        $editAry = array_diff($localAry,$delAry);//需要编辑的设备key
        
        //检查需要编辑的设备，json相同的就不用更新了
        foreach ($machineLocalAry as $kkML=>$vvML){
            $keyOnlineInfo = $vvML['device_no'].'_'.$vvML['type'];
            if(in_array($keyOnlineInfo,$editAry) && $vvML['content']!=json_encode($onlineInfoAry[$keyOnlineInfo]??[])){
                $editData[] = [
                    'device_no'=>$onlineInfoAry[$keyOnlineInfo]['deviceNo'],
                    'name'=>$onlineInfoAry[$keyOnlineInfo]['name'],
                    'type'=>$onlineInfoAry[$keyOnlineInfo]['type'],
                    'address'=>$onlineInfoAry[$keyOnlineInfo]['location']?:'',
                    'longitude'=>$onlineInfoAry[$keyOnlineInfo]['longitude']?:0,
                    'latitude'=>$onlineInfoAry[$keyOnlineInfo]['latitude']?:0,
                    'agent_name'=>$onlineInfoAry[$keyOnlineInfo]['agentName']?:'',
                    'agent_phone'=>$onlineInfoAry[$keyOnlineInfo]['agentPhone']?:'',
                    'status'=>$onlineInfoAry[$keyOnlineInfo]['status'],
                    'add_time'=>strtotime($onlineInfoAry[$keyOnlineInfo]['dateCreated']),
                    'update_time'=>time(),
                    'device_id'=>$onlineInfoAry[$keyOnlineInfo]['id'],
                    'device_village_id'=>$onlineInfoAry[$keyOnlineInfo]['communityId'],
                    'content'=>json_encode($onlineInfoAry[$keyOnlineInfo])
                ];
            }
        }
        
        //获取需要新增的数组
        foreach ($addAry as $vAR){
            if(isset($onlineInfoAry[$vAR])){
                $addData[] = [
                    'device_no'=>$onlineInfoAry[$vAR]['deviceNo'],
                    'name'=>$onlineInfoAry[$vAR]['name'],
                    'type'=>$onlineInfoAry[$vAR]['type'],
                    'address'=>$onlineInfoAry[$vAR]['location']?:'',
                    'longitude'=>$onlineInfoAry[$vAR]['longitude']?:0,
                    'latitude'=>$onlineInfoAry[$vAR]['latitude']?:0,
                    'agent_name'=>$onlineInfoAry[$vAR]['agentName']?:'',
                    'agent_phone'=>$onlineInfoAry[$vAR]['agentPhone']?:'',
                    'status'=>$onlineInfoAry[$vAR]['status'],
                    'add_time'=>strtotime($onlineInfoAry[$vAR]['dateCreated']),
                    'device_id'=>strtotime($onlineInfoAry[$vAR]['id']),
                    'device_village_id'=>$onlineInfoAry[$vAR]['communityId'],
                    'content'=>json_encode($onlineInfoAry[$vAR])
                ];
            }
        }
        
        //获取需要删除的设备记录id
        foreach ($delAry as $vD){
            $delData[] = $localInfoAry[$vD]['id'];
        }

        $errorUpdateNum = 0;//修改数据失败次数
        $errorUpdateIdAry = [];//修改数据失败的数据id
        $errorMsg = '';
        // 启动事务
        Db::startTrans();
        try {
            //编辑
            if($editData){
                foreach ($editData as $vED){
                    $update = (new GarbageCollectionMachineList())->where(['device_no'=>$vED['device_no'],'type'=>$vED['type']])->update($vED);
                    fdump_api((new GarbageCollectionMachineList())->getLastSql(), 'garbageCollection/testBind',1);
                    if(!$update){
                        //记录失败信息
                        $errorUpdateNum += 1;
                        $errorUpdateIdAry[] = $vED['deviceNo'].'_'.$vED['type'];
                    }
                }
                if($errorUpdateIdAry){
                    $errorMsg = '修改数据失败'.$errorUpdateNum.'次，失败key:【'.implode('、',$errorUpdateIdAry).'】';
                }
            }
            //删除
            if($delData){
                $del = (new GarbageCollectionMachineList())->where([['id','IN',$delData]])->delete();
                if(!$del){
                    $errorMsg = $errorMsg ? $errorMsg.';' : '';
                    $errorMsg .= '删除数据失败,失败id：【'.implode('、',$delData).'】';
                }
            }
            //新增
            if($addData){
                $add = (new GarbageCollectionMachineList())->insertAll($addData);
                if(!$add){
                    $errorMsg = $errorMsg ? $errorMsg.';' : '';
                    $errorMsg .= '新增数据失败';
                }
            }
            if($errorMsg){
                fdump_api($errorMsg,'garbage_collection/pullMachineError');
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw_exception($e->getMessage());
        }
        return 'success';
    }
    
    /**
     * 获取用户信息
     */
    public function getUser($param)
    {
        $deviceNo = $param['device_no'] ? $param['device_no'] : '';
        $accountType = $param['account_type'] ? $param['account_type'] : 0;
        $account = $param['account'] ? $param['account'] : '';
        if(!$accountType){
            throw_exception(L_('account_type不能为空'));
        }
        if(!$account){
            throw_exception(L_('account不能为空'));
        }
        if(!$deviceNo){
            throw_exception(L_('device_no不能为空'));
        }
        if($param['account_type']==1){//手机号
            $where = ['hvb.phone'=>$param['account']];
        }elseif ($param['account_type']==2){//卡号
            $where = ['hvb.ic_card'=>$param['account']];
        }elseif ($param['account_type']==3){//二维码编号
            $bindFace = (new FaceUserBindDevice())->getOneOrder([
                ['bind_type','IN',[Cloud260Const::CLOUD_260_FACE_USER_BIND_DEVICE_BIND_TYPE,DeviceConst::GARBAGE_COLLECTION_BIND_TYPE]],
                ['code','=',$param['account']]
            ],'bind_id','bind_type desc');
            if(!$bindFace){
                throw_exception(L_('未查询到用户信息'));
            }
            $where = ['hvb.pigcms_id'=>$bindFace['bind_id']];
        }else{
            throw_exception(L_('暂不支持该账号类型'));
        }
        
        //查询用户信息
        $userInfo = (new HouseVillageUserBind())->getUserBindInfo($where,'hvb.name,hvb.phone,u.score_count,u.uid');
        if(!$userInfo){
            throw_exception(L_('未查询到用户信息'));
        }
        //设备关联的小区
        $villageId = (new GarbageCollectionMachineList())->where('device_no',$deviceNo)->value('village_id');
        if(!$villageId){
            throw_exception(L_('设备未关联小区'));
        }
        
        $villageUser = (new HouseVillageUserBind())->getOne(['village_id'=>$villageId,'uid'=>$userInfo['uid']],'single_id,floor_id,layer_id,vacancy_id,address,status');
        if(!$villageUser){
            throw_exception(L_('非当前小区用户'));
        }
        if($villageUser['status']!=1){
            throw_exception(L_('用户身份审核中或者已经被禁用'));
        }
        $houseVillageService = new HouseVillageService();
        $address = $houseVillageService->getSingleFloorRoom($villageUser['single_id'],$villageUser['floor_id'],$villageUser['layer_id'],$villageUser['vacancy_id'],$villageId);
        //用户的小区名称
        $userInfo['village_name'] = (new HouseVillage())->where('village_id',$villageId)->value('village_name');
        //用户的楼栋单元室号
        $userInfo['village_address'] =  !empty($address) ? $address : $villageUser['address'];;
        //用户的每日积分上限
        $userInfo['score_day_max'] = 0;
        //用户的今日已获得积分
        $beginTime = strtotime(date('Y-m-d', time()));
        $nowTime = time();
        $whereScore = [['put_time','between',[$beginTime,$nowTime]],['device_no','=',$deviceNo],['uid','=',$userInfo['uid']]];
        if($param['account_type']==1){//手机号
            $whereScore[] = ['clnt_type','=',0];
        }elseif($param['account_type']==2){//卡号
            $whereScore[] = ['clnt_type','=',4];
        }else{//二维码
            $whereScore[] = ['clnt_type','=',7];
        }
        $userInfo['score_day'] = (new GarbageCollectionPutList())->where($whereScore)->sum('score');
        $userInfo['score_day'] = intval(bcmul(strval($userInfo['score_day']),10,0));
        
        $userInfo['score_count'] = intval(bcmul(strval($userInfo['score_count']),10,0));
        unset($userInfo['uid']);
        return $userInfo;
    }
    
    /**
     * 接收通知
     */
    public function addNotice($param)
    {
        if(!in_array($param['action'],['THROW','WARN','RELIEVE'])){
            throw_exception(L_('不支持的通知事件类型'));
        }
        $checkNoytNullParam = [
            'deviceNo'=>$param['deviceNo'],
            'machineName'=>$param['machineName'],
            'timestamp'=>$param['timestamp'],
            'sign'=>$param['sign'],
        ];
        foreach ($checkNoytNullParam as $key=>$check){
            if(!$check){
                throw_exception($key.' '.L_('不能为空'));
            }
        }
        
        //检查sign
        if($param['action']=='THROW'){
            //投放
            if(!$param['garbage']){
                throw_exception('garbage '.L_('不能为空'));
            }
            if(!$param['time']){
                throw_exception('time '.L_('不能为空'));
            }
            //创建记录
            $add = $this->addPut($param);
        }else{
            //预警
            if(!$param['bucketNo']){
                throw_exception('bucketNo '.L_('不能为空'));
            }
            if(!$param['type']){
                throw_exception('type '.L_('不能为空'));
            }
            if($param['action']=='WARN' && !$param['dateWarn']){
                throw_exception('dateWarn '.L_('不能为空'));
            }
            //创建记录
            $add = $this->addWarn($param);
        }
        return true;
    }
    
    /**
     * 新增投放记录
     */
    public function addPut($param)
    {
        $garbage = is_array($param['garbage']) ? $param['garbage'] : json_decode($param['garbage'],true);
        if(count($garbage)<1){
            return true;
        }
        $param['phone'] = $param['phone']??'';
        // 启动事务
        Db::startTrans();
        try {
            $where = [];
            if($param['phone']){
                $where = ['phone'=>$param['phone']];
            }elseif(in_array($param['accountType'],[1,2,3])){
                if($param['accountType']==1){//手机号
                    $where = ['phone'=>$param['account']];
                }elseif($param['accountType']==2){//卡编号
                    $userBind = (new HouseVillageUserBind())->where('ic_card',$param['account'])->value('uid');
                    if(!$userBind){
                        throw_exception(L_('用户信息不存在'));
                    }
                    $where = ['uid'=>$userBind];
                }elseif($param['accountType']==3){//二维码编号
                    $faceUserBindDevice = (new FaceUserBindDevice())->getOneOrder([
                        ['bind_type','IN',[Cloud260Const::CLOUD_260_FACE_USER_BIND_DEVICE_BIND_TYPE,DeviceConst::GARBAGE_COLLECTION_BIND_TYPE]],
                        ['code','=',$param['account']]
                    ],'bind_id','bind_type desc');
                    if(!$faceUserBindDevice){
                        throw_exception(L_('用户信息不存在'));
                    }
                    $userBind = (new HouseVillageUserBind())->where('pigcms_id',$faceUserBindDevice['bind_id'])->value('uid');
                    if(!$userBind){
                        throw_exception(L_('用户信息不存在'));
                    }
                    $where = ['uid'=>$userBind];
                }
            }
            $userInfo = (new User())->where($where)->field('uid,openid,score_count as score,phone')->find();
            if(!$userInfo){
                throw_exception(L_('用户信息不存在'));
            }
            $uid = $userInfo['uid'];
            $openid = $userInfo['openid'];
            $oldScore = strval($userInfo['score']);
            //获取投放方式，根据账号类型来贩毒案，目前只能分辨出刷卡、扫码、rfid 标签投递三种方式
            $clntType = 0;
            if($param['accountType']==2){
                $clntType = 4;//刷卡
            }elseif($param['accountType']==3){
                $clntType = 7;//扫码
            }elseif($param['accountType']==4){
                $clntType = 8;//rfid 标签投递
            }
            //根据设备编号查询绑定的小区
            $machineInfo = (new GarbageCollectionMachineList())->where('device_no',$param['deviceNo'])->field('village_id')->find();
            if(!$machineInfo){
                throw_exception(L_('设备信息不存在'));
            }
            $villageId = $machineInfo['village_id'];
            //新增投放记录
            $scoreAll = '0';
            $weightAll = '0';
            $paramAry = request()->param();
            foreach ($garbage as $v){
                $score = bcdiv(strval($v['points']??0),10,2);
                $scoreAll = bcadd($scoreAll,$score,2);
                $weightAll = bcadd($weightAll,strval($v['weight']),2);
            }
            $type = '可回收垃圾';
            $paramAry['garbage'] = is_array($paramAry['garbage']) ? $paramAry['garbage'] : json_decode($paramAry['garbage'],true);
            foreach ($paramAry['garbage'] as $kk=>$vv){
                $paramAry['garbage'][$kk]['garbageCategoryMsg'] = (new GarbageCollectionPutService())->getGarbageType($vv['type'],$vv['garbageCategory']??0);
                $type = $paramAry['garbage'][$kk]['garbageCategoryMsg'];
            }
            if(count($paramAry['garbage'])>1){
                $type = '多类型';
            }
            $add = (new GarbageCollectionPutList())->insertGetId([
                'village_id'=>$villageId??0,
                'device_no'=>$param['deviceNo'],
                'name'=>$param['machineName'],
                'clnt_type'=>$clntType,
                'user_phone'=>$param['phone']?:$userInfo['phone'],
                'uid'=>$uid?:0,
                'weight'=>$weightAll,
                'score'=>$scoreAll,
                'status'=>$param['status']??2,
                'ic_card'=>$param['accountType']==2 ? ($param['account']??'') : '',//卡编号
                'put_time'=>strtotime($param['time']),
                'add_time'=>time(),
                'content'=>json_encode($paramAry),
            ]);
            if(!$add){
                throw_exception(L_('增加投放记录失败'));
            }
            //用户增加积分
            if($add && $uid && $scoreAll>0){
                $info = [
                    'order_id' => $add,
                    'order_type' => 'garbage_collection',
                ];
                $ret=(new UserService())->addScore($uid,$scoreAll,L_("垃圾分类投递获取").cfg('score_name'),0,$info);
                if($ret['error_code']){
                    fdump_sql(['garbage_collection_info'=>$param,'msg'=>$ret['msg']],'addScoreError');
                }
                if($openid){
                    //公众号通知
                    $msgDataWx = [
                        'href' => '',
                        'wecha_id' => $openid,
                        'first' => '感谢使用'.$param['machineName'],
                        'keyword1' => $param['machineName'],
                        'keyword2' => $type,
                        'keyword3' => $weightAll.'g',
                        'keyword4' => '+'.$scoreAll,
                        'keyword5' => getFormatNumber(bcadd($oldScore,$scoreAll,3)),
                        'remark' => '',
                        'new_info' => [
                            'tempKey'=>'55036',//新模板号
                            'thing1'=>$param['machineName'],//设备名称
                            'const2'=>$type,//垃圾类型
                            'character_string3'=>$weightAll.'g'//投递重量
                        ]
                    ];
                    (new TemplateNewsService())->sendTempMsg('OPENTM418366503', $msgDataWx);
                }
                //短信通知
                $data = array('type' => 'garbage_collection');
                $data['uid'] = $uid;
                $data['mobile'] = $param['phone']?:$userInfo['phone'];
                $data['sendto'] = 'user';
                $data['mer_id'] = 0;
                $data['store_id'] = 0;
                $data['content'] = L_('尊敬的用户，您在x1设备x2投递的垃圾回收增加x3积分，请前往用户端查看详情。', array(
                    'x1' => $param['machineName'],
                    'x2' => $param['time'],
                    'x3' => getFormatNumber($scoreAll)
                ));
                (new SmsService())->sendSms($data);
            }
            
            
            
            
            
            
            
            
//            foreach ($garbage as $v){
//                $score = bcdiv(strval($v['points']??0),10,2);
//                $add = (new GarbageCollectionPutList())->insertGetId([
//                    'village_id'=>$villageId??0,
//                    'device_no'=>$param['deviceNo'],
//                    'name'=>$param['machineName'],
//                    'clnt_type'=>$clntType,
//                    'user_phone'=>$param['phone']?:$userInfo['phone'],
//                    'uid'=>$uid?:0,
//                    'weight'=>$v['weight']??0,
//                    'score'=>$score,
//                    'status'=>$param['status']??2,
//                    'ic_card'=>$param['accountType']==2 ? ($param['account']??'') : '',//卡编号
//                    'put_time'=>strtotime($param['time']),
//                    'add_time'=>time(),
//                    'content'=>json_encode(request()->param()),
//                ]);
//                if(!$add){
//                    throw_exception(L_('增加投放记录失败'));
//                }
//                //用户增加积分
//                if($add && $uid && $score){
//                    $info = [
//                        'order_id' => $add,
//                        'order_type' => 'garbage_collection',
//                    ];
//                    $ret=(new UserService())->addScore($uid,$score,L_("垃圾分类投递获取").cfg('score_name'),0,$info);
//                    if($ret['error_code']){
//                        fdump_sql(['garbage_collection_info'=>$param,'msg'=>$ret['msg']],'addScoreError');
//                    }
//                    if($openid){
//                        //公众号通知
//                        $msgDataWx = [
//                            'href' => '',
//                            'wecha_id' => $openid,
//                            'first' => '感谢使用'.$param['machineName'],
//                            'keyword1' => $param['machineName'],
//                            'keyword2' => (new GarbageCollectionPutList())->getGarbageType($v['type']??0),
//                            'keyword3' => ($v['weight']??'0').'g',
//                            'keyword4' => '+'.$score,
//                            'keyword5' => getFormatNumber(bcadd($oldScore,$score,3)),
//                            'remark' => ''
//                        ];
//                        (new TemplateNewsService())->sendTempMsg('OPENTM418366503', $msgDataWx);
//                    }
//                    //短信通知
//                    $data = array('type' => 'garbage_collection');
//                    $data['uid'] = $uid;
//                    $data['mobile'] = $param['phone']?:$userInfo['phone'];
//                    $data['sendto'] = 'user';
//                    $data['mer_id'] = 0;
//                    $data['store_id'] = 0;
//                    $data['content'] = L_('尊敬的用户，您在x1设备x2投递的垃圾回收增加x3积分，请前往用户端查看详情。', array(
//                        'x1' => $param['machineName'],
//                        'x2' => $param['time'],
//                        'x3' => getFormatNumber($score)
//                    ));
//                    (new SmsService())->sendSms($data);
//                }
//            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw_exception($e->getMessage());
        }
        return true;
    }
    /**
     * 新增预警记录
     */
    public function addWarn($param)
    {
        // 启动事务
        Db::startTrans();
        try {
            //根据设备编号查询绑定的小区
            $machineInfo = (new GarbageCollectionMachineList())->where('device_no',$param['deviceNo'])->field('village_id,id')->find();
            if(!$machineInfo){
                throw_exception(L_('设备信息不存在'));
            }
            $villageId = $machineInfo['village_id'];
            $machineId = $machineInfo['id'];
            
            $status = $param['action']=='WARN' ? 1 : 2;
            if(isset($param['dateWarn']) && strlen($param['dateWarn'])==13){
                $param['dateWarn'] = substr($param['dateWarn'], 0, -3);
            }
            //新增预警记录
            $add = (new GarbageCollectionWarnList())->insertGetId([
                'village_id'=>$villageId,
                'device_no'=>$param['deviceNo'],
                'name'=>$param['machineName'],
                'address'=>$param['address'],
                'bucke_no'=>$param['bucketNo'],
                'type'=>$param['type']??0,
                'warn_time'=>isset($param['dateWarn']) ? $param['dateWarn'] : 0,
                'status'=>$status,
                'content'=>json_encode(request()->param()),
                'add_time'=>time(),
                'last_time'=>time(),
                'last_status'=>$status
            ]);
            if(!$add){
                throw_exception(L_('增加预警记录失败'));
            }
            if($param['action']=='RELIEVE' && $param['timestamp'] > 0){//解除设备对应报警类型的预警
                $updateWhere = [
                    ['device_no','=',$param['deviceNo']],
                    ['type','=',$param['type']],
                    ['status','=',1],
                    ['last_status','=',1],
                    ['warn_time','<=',$param['timestamp']],
                ];
                if($param['bucketNo']){
                    $updateWhere[] = ['bucke_no','=',$param['bucketNo']];
                }
                (new GarbageCollectionWarnList())->where($updateWhere)->update([
                    'last_status'=>2,
                    'last_time'=>$param['timestamp']
                ]);
            }
            
            $worker = [];
            if($villageId && $param['action']=='WARN'){//只在预警时通知工作人员
                $worker = (new HouseMaintenanWorker())->getWorkerList(['hw.village_id'=>$villageId,'hw.status'=>1,'hw.is_del'=>0,'hmw.village_id'=>$villageId,'hmw.is_del_time'=>0],'hw.wid,hw.name,hw.village_id,hw.nickname,hw.phone,hw.openid','hw.wid desc');
            }
            //通知小区预警通知人员
            if($worker){
                $typeTxt = L_('设备预警').'：';
                $typeTxt .= (new GarbageCollectionWarnList())->getTypeText($param['type']??0);
                //查询小区预警通知人员
                (new DeviceAlarmEventService())->faultNotice($machineId, $typeTxt, true, DeviceConst::DEVICE_TYPE_GARBAGE_COLLECTION);
            }
            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw_exception($e->getMessage());
        }
        return true;
    }
    
    /**
     * 获取设备类型
     */
    public function getMachineType()
    {
        $list = [];
        $typeList = (new GarbageCollectionMachineList())->type;
        foreach ($typeList as $key=>$type){
            $list[] = [
                'key'=>$key,
                'value'=>$type
            ];
        }
        return $list;
    }
    
    /**
     * 查询小区列表
     */
    public function villageList($param)
    {
        $where = [['status','=',1]];
        if($param['keyword']){
            $where[] = ['village_name','like','%' . $param['keyword'] . '%'];
        }
        if($param['property_id']){
            $where[] = ['property_id','=',$param['property_id']];
        }
        if($param['city_id']){
            $where[] = ['city_id','=',$param['city_id']];
        }
        if($param['area_id']){
            $where[] = ['area_id','=',$param['area_id']];
        }
        if($param['street_id']){
            $where[] = ['street_id','=',$param['street_id']];
        }
        if($param['community_id']){
            $where[] = ['community_id','=',$param['community_id']];
        }
        $field = 'village_id,village_name,village_address';
        $order = 'village_id desc';
        $list = (new HouseVillage())->getVillageLists($where,$field,$order,$param['pageSize']);
        return $list;
    }
}