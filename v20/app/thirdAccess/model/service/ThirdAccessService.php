<?php
namespace app\thirdAccess\model\service;

use app\community\model\service\HouseVillageSingleService;
use app\community\model\service\HouseVillageUserVacancyService;
use app\community\model\service\HouseVillageUserBindService;
use app\community\model\service\UserService;

class ThirdAccessService {

    public $village_id;// 小区id
    public $thirdType; // 三方类型 目前楼宇  linghui
    public $businessType; // 父级业务类型  默认小区 village
    public $businessId; // 父级对应业务id  默认小区id village_id
    public $childrenBusinessType; // 子级业务类型  默认楼栋 buildings
    public $childrenBusinessId; // 子级业务对应id 对应楼栋id
    public $operation; // 操作类型 默认 add 添加  edit 编辑  （del 删除）暂未实现
    public $thirdId; // 对应三方业务id  当前默认是对应楼栋的三方楼栋id
    public $thirdSite; // 对应三方域名
    public $thirdName; // 对应三方业务名称
    public $sort; // 排序值  值越大排序约靠前
    public $thirdAppid; // 三方对应appid默认无时候是0
    public $thirdAppkey; // 三方对应appkey
    // 楼栋相关
    public $buildName; // 对应添加的楼栋名称
    public $buildNumber; // 楼栋编号
    public $buildId; // 楼栋id
    // 单元相关
    public $unitName; // 对应添加的单元名称
    public $unitNumber; // 单元编号
    public $unitId; // 单元id
    // 楼层相关
    public $layerName; // 对应添加的楼层名称
    public $layerNumber; // 楼层编号
    public $layerId; // 楼层id
    // 房屋相关
    public $roomId; // 房屋id
    public $roomName; // 房屋名称
    public $roomNumber; // 房屋编号
    public $roomType;// 类型 1住宅 2商铺 3办公
    public $useStatus;// 使用状态 1业主入住 2未入住 3租客入住
    public $sellStatus;// 出售状态 1正常居住 2出售中 3出租中
    public $userNum;// 物业原始编号 未传生成逻辑（小区id-楼栋id-楼层id-房屋id）
    public $propertyNumber;// 物业编号 未传生成逻辑（楼栋编号[2位]楼层编号[2位]房屋编号[2位]）
    // 房屋住户相关
    public $roomUserId; // 住户Id
    public $userName; // 住户姓名
    public $userPhone; // 住户手机号
    public $userType; // 住户类型  1房主  2家人  3租客  记得对应转换  1对应0，添加业主保持一个 第二个不允许添加  2对应1  3对应2
    public $relativesType; // 住户类型userType 类型为2 家人时候起效 1配偶 2 父母 3子女 4亲朋好友 5公司负责人 6公司人事 7公司财务  默认亲朋好友
    public $startTime; // 物业服务开始时间
    public $endTime; // 物业服务截止时间
    public $IDCard; // 住户身份证
    public $ICCard; // 住户ic卡 主要用于刷卡开门
    public $memo; // 备注
    // 类型限制
    public $userTypeArr = [1,2,3];
    public $relativesTypeArr = [1,2,3,4,5,6,7];
    // 时间
    public $nowTime;
    // 查看id
    public $lookId;
    // 删除id
    public $deleteId;

    // 初始化
    public $thirdAccessCheckService;
    public $houseVillageSingleService;
    public $houseVillageUserVacancyService;
    public $houseVillageUserBindService;
    public $userService;
    public function __construct()
    {
        $this->thirdAccessCheckService = new ThirdAccessCheckService();
        $this->houseVillageSingleService = new HouseVillageSingleService();
        $this->houseVillageUserVacancyService = new HouseVillageUserVacancyService();
        $this->houseVillageUserBindService = new HouseVillageUserBindService();
        $this->userService = new UserService();
        $this->nowTime = time();
    }
    // 楼栋 单元 楼层  房屋 数据写入/变更
    public function setThirdAccessData($param) {
        $this->village_id = trim($param['village_id']);
        $this->thirdType = trim($param['thirdType']);
        $this->businessType = isset($param['businessType'])&&trim($param['businessType'])?trim($param['businessType']):'village';
        $this->childrenBusinessType = isset($param['childrenBusinessType'])&&trim($param['childrenBusinessType'])?trim($param['childrenBusinessType']):'buildings';
        $this->businessId = $this->village_id;

        $thirdAccessConfig = $this->filterVillageAuth();

        $this->operation = isset($param['operation'])&&trim($param['operation'])?trim($param['operation']):'add';
        $this->thirdId = isset($param['thirdId'])&&trim($param['thirdId'])?trim($param['thirdId']):'';
        $this->thirdSite = isset($param['thirdSite'])&&trim($param['thirdSite'])?trim($param['thirdSite']):'';
        $this->sort = isset($param['sort'])&&trim($param['sort'])?trim($param['sort']):'';
        $this->thirdName = isset($_POST['thirdName'])&&trim($_POST['thirdName'])?trim($_POST['thirdName']):'';
        $this->buildId = isset($param['buildId'])&&trim($param['buildId'])?trim($param['buildId']):'';
        if ('detail'==$this->operation) {
            $this->lookId = isset($param['lookId'])&&trim($param['lookId'])?trim($param['lookId']):'';
            return $this->lookDetail();
        } elseif ('delete'==$this->operation) {
            $this->deleteId = isset($param['deleteId'])&&trim($param['deleteId'])?trim($param['deleteId']):'';
            $this->lookId = $this->deleteId;
            return $this->delDetail();
        } elseif ('buildings'==$this->childrenBusinessType) {
            // 楼栋相关
            $this->buildName = isset($param['buildName'])&&trim($param['buildName'])?trim($param['buildName']):'';
            $this->buildNumber = isset($param['buildNumber'])&&trim($param['buildNumber'])?trim($param['buildNumber']):'';
            return $this->updateBuildings($param,$thirdAccessConfig);
        } elseif ('units'==$this->childrenBusinessType) {
            // 单元相关
            $this->unitName = isset($param['unitName'])&&trim($param['unitName'])?trim($param['unitName']):'';
            $this->unitNumber = isset($param['unitNumber'])&&trim($param['unitNumber'])?trim($param['unitNumber']):'';
            $this->unitId = isset($param['unitId'])&&trim($param['unitId'])?trim($param['unitId']):'';
            return $this->updateUnits($param,$thirdAccessConfig);
        } elseif ('layers'==$this->childrenBusinessType) {
            // 楼层相关
            $this->layerName = isset($param['layerName'])&&trim($param['layerName'])?trim($param['layerName']):'';
            $this->layerNumber = isset($param['layerNumber'])&&trim($param['layerNumber'])?trim($param['layerNumber']):'';
            $this->unitId = isset($param['unitId'])&&trim($param['unitId'])?trim($param['unitId']):'';
            $this->layerId = isset($param['layerId'])&&trim($param['layerId'])?trim($param['layerId']):'';
            return $this->updateLayers($param,$thirdAccessConfig);
        } elseif ('rooms'==$this->childrenBusinessType) {
            // 房屋相关
            $this->roomName = isset($param['roomName'])&&trim($param['roomName'])?trim($param['roomName']):'';
            $this->roomNumber = isset($param['roomNumber'])&&trim($param['roomNumber'])?trim($param['roomNumber']):'';
            $this->unitId = isset($param['unitId'])&&trim($param['unitId'])?trim($param['unitId']):'';
            $this->layerId = isset($param['layerId'])&&trim($param['layerId'])?trim($param['layerId']):'';
            $this->roomId = isset($param['roomId'])&&trim($param['roomId'])?trim($param['roomId']):'';
            $this->roomType = isset($param['roomType'])&&trim($param['roomType'])?trim($param['roomType']):'';
            $this->useStatus = isset($param['useStatus'])&&trim($param['useStatus'])?trim($param['useStatus']):'';
            $this->sellStatus = isset($param['sellStatus'])&&trim($param['sellStatus'])?trim($param['sellStatus']):'';
            $this->userNum = isset($param['userNum'])&&trim($param['userNum'])?trim($param['userNum']):'';
            $this->propertyNumber = isset($param['propertyNumber'])&&trim($param['propertyNumber'])?trim($param['propertyNumber']):'';
            return $this->updateRooms($param,$thirdAccessConfig);
        } elseif ('roomUsers'==$this->childrenBusinessType) {
            // 房屋住户相关
            $this->userName = isset($param['userName'])&&trim($param['userName'])?trim($param['userName']):'';
            $this->userPhone = isset($param['userPhone'])&&trim($param['userPhone'])?trim($param['userPhone']):'';
            $this->unitId = isset($param['unitId'])&&trim($param['unitId'])?trim($param['unitId']):'';
            $this->layerId = isset($param['layerId'])&&trim($param['layerId'])?trim($param['layerId']):'';
            $this->roomId = isset($param['roomId'])&&trim($param['roomId'])?trim($param['roomId']):'';
            $this->memo = isset($param['memo'])&&trim($param['memo'])?trim($param['memo']):'';
            $this->roomUserId = isset($param['roomUserId'])&&trim($param['roomUserId'])?trim($param['roomUserId']):'';

            $this->userType = isset($param['userType'])&&trim($param['userType'])?trim($param['userType']):'';
            $this->relativesType = isset($param['relativesType'])&&trim($param['relativesType'])?trim($param['relativesType']):4;
            $this->IDCard = isset($param['IDCard'])&&trim($param['IDCard'])?trim($param['IDCard']):'';
            $this->ICCard = isset($param['ICCard'])&&trim($param['ICCard'])?trim($param['ICCard']):'';
            $this->startTime = isset($param['startTime'])&&trim($param['startTime'])?trim($param['startTime']):0;
            $this->endTime = isset($param['endTime'])&&trim($param['endTime'])?trim($param['endTime']):0;

            return $this->updateRoomUsers($param,$thirdAccessConfig);
        }
        return [];
    }
    // 楼栋数据写入/变更
    public function updateBuildings($param, $thirdAccessConfig=[],$name='楼栋') {
        if ($this->operation=='add' && (!$this->thirdId || !$this->buildName || !$this->buildName)) {
            // 添加的时候名称和对应对接方的楼栋id必传
            throw new \Exception('缺少必要参数');
        }
        $buildingsThirdAccess = $this->filterData();

        if ($this->buildName && $this->buildNumber) {
            $whereRaw = "(`single_name` = '{$this->buildName}'  OR `single_number` = '{$this->buildNumber}') AND `village_id` = {$this->village_id}  AND `status` <> 4";
        } elseif ($this->buildName) {
            $whereRaw = "`single_name` = '{$this->buildName}' AND `village_id` = {$this->village_id}  AND `status` <> 4";
        } elseif ($this->buildNumber) {
            $whereRaw = "`single_number` = '{$this->buildNumber}' AND `village_id` = {$this->village_id}  AND `status` <> 4";
        } else {
            $whereRaw = '';
        }
        if ($whereRaw) {
            $info = $this->houseVillageSingleService->getSingleRawInfo($whereRaw);
        } else {
            $info = [];
        }
        if ($this->operation=='add' && isset($info['id'])&&$info['id']) {
            throw new \Exception("对应{$name}名称或者{$name}编号已经存在");
        } elseif ($this->operation!='add' && isset($info['id'])&&$info['id']&&$info['id']!=$buildingsThirdAccess['businessId']) {
            throw new \Exception("变更{$name}名称或者{$name}编号已经存在");
        }
        if ($buildingsThirdAccess['businessId']) {
            // 如果是更新 对应处理
            $buildData = [];
            if (isset($param['buildName'])) {
                $buildData['single_name'] = $this->buildName;
            }
            if (isset($param['sort'])) {
                $buildData['sort'] = $this->sort;
            }
            if (isset($param['single_number'])) {
                $buildData['single_number'] = $this->buildNumber;
            }
            $buildData['update_time'] = $this->nowTime;
            $whereSingle = [];
            $whereSingle[] = ['id','=', $buildingsThirdAccess['businessId']];
            $saveId = $this->houseVillageSingleService->saveSingleInfo($whereSingle,$buildData);
            if (!$saveId) {
                throw new \Exception("编辑{{$name}}失败");
            }
            $this->childrenBusinessId = $buildingsThirdAccess['businessId'];
        } else {
            $buildData = [
                'single_name' =>$this->buildName,
                'village_id' =>$this->village_id,
                'update_time' =>$this->nowTime,
                'sort' =>$this->sort,
            ];
            if ($this->buildNumber) {
                $buildData['single_number'] = $this->buildNumber;
            }
            $this->childrenBusinessId = $this->houseVillageSingleService->addSingleInfo($buildData);
            if (!$this->childrenBusinessId) {
                throw new \Exception("添加{{$name}}失败");
            }
        }
        return $this->saveThirdConfigData($thirdAccessConfig);
    }
    // 单元数据写入/变更
    public function updateUnits($param, $thirdAccessConfig=[],$name='单元') {
        if ($this->operation=='add' && (!$this->thirdId || !$this->unitName || !$this->buildId)) {
            // 添加的时候名称和对应对接方的楼栋id必传
            throw new \Exception('缺少必要参数');
        } elseif (!$this->thirdId || !$this->buildId) {
            // 添加的时候名称和对应对接方的楼栋id必传
            throw new \Exception('缺少必要参数');
        }
        $infoThirdAccess = $this->filterData();
        if ($this->unitName && $this->unitNumber) {
            $whereRaw = "(`floor_name` = '{$this->unitName}'  OR `floor_number` = '{$this->unitNumber}') AND `village_id` = {$this->village_id} AND `single_id` = {$this->buildId}  AND `status` <> 4";
        } elseif ($this->unitName) {
            $whereRaw = "`floor_name` = '{$this->unitName}' AND `village_id` = {$this->village_id} AND `single_id` = {$this->buildId}  AND `status` <> 4";
        } elseif ($this->unitNumber) {
            $whereRaw = "`floor_number` = '{$this->unitNumber}' AND `village_id` = {$this->village_id} AND `single_id` = {$this->buildId}  AND `status` <> 4";
        } else {
            $whereRaw = '';
        }
        if ($whereRaw) {
            $info = $this->houseVillageSingleService->getFloorRawInfo($whereRaw);
        } else {
            $info = [];
        }
        if ($this->operation=='add' && isset($info['floor_id'])&&$info['floor_id']) {
            throw new \Exception("对应{$name}名称或者{$name}编号已经存在");
        } elseif ($this->operation!='add' && isset($info['floor_id'])&&$info['floor_id']&&$info['floor_id']!=$infoThirdAccess['businessId']) {
            throw new \Exception("变更{$name}名称或者{$name}编号已经存在");
        }
        if ($infoThirdAccess['businessId']) {
            // 如果是更新 对应处理
            $saveData = [];
            if (isset($param['unitName'])) {
                $saveData['floor_name'] = $this->unitName;
            }
            if (isset($param['sort'])) {
                $saveData['sort'] = $this->sort;
            }
            if (isset($param['unitNumber'])) {
                $saveData['floor_number'] = $this->unitNumber;
            }
            $saveData['update_time'] = $this->nowTime;
            $whereSave = [];
            $whereSave[] = ['floor_id','=', $infoThirdAccess['businessId']];
            $saveId = $this->houseVillageSingleService->saveFloorInfo($whereSave,$saveData);
            if (!$saveId) {
                throw new \Exception("编辑{{$name}}失败");
            }
            $this->childrenBusinessId = $infoThirdAccess['businessId'];
        } else {
            $saveData = [
                'floor_name' =>$this->unitName,
                'village_id' =>$this->village_id,
                'single_id' =>$this->buildId,
                'add_time' =>$this->nowTime,
                'sort' =>$this->sort,
            ];
            if ($this->unitNumber) {
                $saveData['floor_number'] = $this->unitNumber;
            }
            $this->childrenBusinessId = $this->houseVillageSingleService->addFloorInfo($saveData);
            if (!$this->childrenBusinessId) {
                throw new \Exception("添加{{$name}}失败");
            }
        }
        return $this->saveThirdConfigData($thirdAccessConfig);
    }
    // 楼层数据写入/变更
    public function updateLayers($param, $thirdAccessConfig=[],$name='楼层') {
        if ($this->operation=='add' && (!$this->thirdId || !$this->layerName || !$this->buildId || !$this->unitId)) {
            // 添加的时候名称和对应对接方的楼栋id必传
            throw new \Exception('缺少必要参数');
        } elseif (!$this->thirdId || !$this->buildId || !$this->unitId) {
            // 添加的时候名称和对应对接方的楼栋id必传
            throw new \Exception('缺少必要参数');
        }
        $infoThirdAccess = $this->filterData();

        if ($this->layerName && $this->layerNumber) {
            $whereRaw = "(`layer_name` = '{$this->layerName}'  OR `layer_number` = '{$this->layerNumber}') AND `village_id` = {$this->village_id} AND `single_id` = {$this->unitId}  AND `status` <> 4";
        } elseif ($this->layerName) {
            $whereRaw = "`layer_name` = '{$this->layerName}' AND `village_id` = {$this->village_id} AND `single_id` = {$this->unitId}  AND `status` <> 4";
        } elseif ($this->layerNumber) {
            $whereRaw = "`layer_number` = '{$this->layerNumber}' AND `village_id` = {$this->village_id} AND `single_id` = {$this->unitId}  AND `status` <> 4";
        } else {
            $whereRaw = '';
        }
        if ($whereRaw) {
            $info = $this->houseVillageSingleService->getLayerRawInfo($whereRaw);
        } else {
            $info = [];
        }
        if ($this->operation=='add' && isset($info['id'])&&$info['id']) {
            throw new \Exception("对应{$name}名称或者{$name}编号已经存在");
        } elseif ($this->operation!='add' && isset($info['id'])&&$info['id']&&$info['id']!=$infoThirdAccess['businessId']) {
            throw new \Exception("变更{$name}名称或者{$name}编号已经存在");
        }
        if ($infoThirdAccess['businessId']) {
            // 如果是更新 对应处理
            $saveData = [];
            if (isset($param['layerName'])) {
                $saveData['layer_name'] = $this->layerName;
            }
            if (isset($param['sort'])) {
                $saveData['sort'] = $this->sort;
            }
            if (isset($param['layerNumber'])) {
                $saveData['layer_number'] = $this->layerNumber;
            }
            $whereSave = [];
            $whereSave[] = ['id','=', $infoThirdAccess['businessId']];
            $saveId = $this->houseVillageSingleService->saveLayerInfo($whereSave,$saveData);
            if ($saveId===false) {
                throw new \Exception("编辑{{$name}}失败");
            }
            $this->childrenBusinessId = $infoThirdAccess['businessId'];
        } else {
            $saveData = [
                'layer_name' =>$this->layerName,
                'village_id' =>$this->village_id,
                'single_id' =>$this->buildId,
                'floor_id' =>$this->unitId,
                'sort' =>$this->sort,
            ];
            if ($this->layerNumber) {
                $saveData['layer_number'] = $this->layerNumber;
            }
            $this->childrenBusinessId = $this->houseVillageSingleService->addLayerInfo($saveData);
            if (!$this->childrenBusinessId) {
                throw new \Exception("添加{{$name}}失败");
            }
        }
        return $this->saveThirdConfigData($thirdAccessConfig);
    }
    // 房屋数据写入/变更
    public function updateRooms($param, $thirdAccessConfig=[],$name='房屋') {
        if ($this->operation=='add' && (!$this->thirdId || !$this->roomName || !$this->buildId || !$this->unitId || !$this->layerId)) {
            // 添加的时候名称和对应对接方的楼栋id必传
            throw new \Exception('缺少必要参数');
        } elseif (!$this->thirdId || !$this->buildId || !$this->unitId || !$this->layerId) {
            // 添加的时候名称和对应对接方的楼栋id必传
            throw new \Exception('缺少必要参数');
        }
        $infoThirdAccess = $this->filterData();
        if ($this->roomName && $this->roomNumber) {
            $whereRaw = "(`room` = '{$this->roomName}'  OR `room_number` = '{$this->roomNumber}') AND `village_id` = {$this->village_id} AND `layer_id` = {$this->layerId}  AND `status` <> 4";
        } elseif ($this->roomName) {
            $whereRaw = "`room` = '{$this->roomName}' AND `village_id` = {$this->village_id} AND `layer_id` = {$this->layerId}  AND `status` <> 4";
        } elseif ($this->roomNumber) {
            $whereRaw = "`room_number` = '{$this->roomNumber}' AND `village_id` = {$this->village_id} AND `layer_id` = {$this->layerId}  AND `status` <> 4";
        } else {
            $whereRaw = '';
        }
        if ($whereRaw) {
            $info = $this->houseVillageUserVacancyService->getRoomRawInfo($whereRaw);
        } else {
            $info = [];
        }
        if ($this->operation=='add' && isset($info['pigcms_id'])&&$info['pigcms_id']) {
            throw new \Exception("对应{$name}名称或者{$name}编号已经存在");
        } elseif ($this->operation!='add' && isset($info['pigcms_id'])&&$info['pigcms_id']&&$info['pigcms_id']!=$infoThirdAccess['businessId']) {
            throw new \Exception("变更{$name}名称或者{$name}编号已经存在");
        }
        if ($infoThirdAccess['businessId']) {
            $this->roomId = $infoThirdAccess['businessId'];
            $this->getRoomNumbers();
            // 如果是更新 对应处理
            $saveData = [];
            if (isset($param['roomName'])) {
                $saveData['room'] = $this->roomName;
            }
            if ($this->useStatus) {
                $saveData['user_status'] = $this->useStatus;
            }
            if ($this->sellStatus) {
                $saveData['sell_status'] = $this->sellStatus;
            }
            if ($this->userNum) {
                $saveData['usernum'] = $this->userNum;
            }
            if ($this->propertyNumber) {
                $saveData['property_number'] = $this->propertyNumber;
            }
            if (isset($param['sort'])) {
                $saveData['sort'] = $this->sort;
            }
            if (isset($param['roomNumber'])) {
                $saveData['room_number'] = $this->roomNumber;
            }
            if (isset($param['roomType'])&&$param['roomType']) {
                $saveData['house_type'] = $this->roomType;
            }
            $whereSave = [];
            $whereSave[] = ['pigcms_id','=', $infoThirdAccess['businessId']];
            $saveId = $this->houseVillageUserVacancyService->saveRoomsInfo($whereSave,$saveData);
            if ($saveId===false) {
                throw new \Exception("编辑{{$name}}失败");
            }
            $this->childrenBusinessId = $infoThirdAccess['businessId'];
        } else {
            $saveData = [
                'room' =>$this->roomName,
                'village_id' =>$this->village_id,
                'single_id' =>$this->buildId,
                'floor_id' =>$this->unitId,
                'layer_id' =>$this->layerId,
                'sort' =>$this->sort,
                'status' => 1,
            ];
            if ($this->roomNumber) {
                $saveData['room_number'] = $this->roomNumber;
            }
            if ($this->useStatus) {
                $saveData['user_status'] = $this->useStatus;
            }
            if ($this->sellStatus) {
                $saveData['sell_status'] = $this->sellStatus;
            }
            if ($this->userNum) {
                $saveData['usernum'] = $this->userNum;
            }
            if ($this->propertyNumber) {
                $saveData['property_number'] = $this->propertyNumber;
            }
            if (isset($param['roomType'])&&$param['roomType']) {
                $saveData['house_type'] = $this->roomType;
            }
            $this->childrenBusinessId = $this->houseVillageUserVacancyService->addRoomsInfo($saveData);
            if (!$this->childrenBusinessId) {
                throw new \Exception("添加{{$name}}失败");
            }
            $this->roomId = $this->childrenBusinessId;
            // 添加成功后更新下编号信息
            $this->getRoomNumbers([],1);
        }
        return $this->saveThirdConfigData($thirdAccessConfig);
    }
    // 住户信息写入/更新
    public function updateRoomUsers($param, $thirdAccessConfig=[], $name='住户') {
        if ($this->operation=='addRoomUsers' && (!$this->userType || !$this->thirdId || !$this->userName || !$this->userPhone || !$this->buildId || !$this->unitId || !$this->layerId || !$this->roomId)) {
            // 添加的时候名称和对应对接方的楼栋id必传
            fdump([$param,$this->userType,$this->thirdId, $this->userName,$this->userPhone,$this->buildId,$this->unitId,$this->layerId,$this->roomId],'$paramData1');
            throw new \Exception('缺少必要参数');
        }
        if ($this->roomId) {
            // 存在房屋id 查询下房屋信息
            $roomInfo = $this->resetRooms();
        } else {
            $roomInfo = [];
        }
        $infoThirdAccess = $this->filterData();
        if ($this->IDCard && $this->userPhone && $this->roomId) {
            $whereRaw = "`phone` = '{$this->userPhone}'  OR `id_card` = '{$this->IDCard}') AND `vacancy_id` = {$this->roomId} AND `status` in (1,2)";
            $info = $this->houseVillageUserBindService->getUserBindRawInfo($whereRaw);
        } elseif ($this->IDCard && $this->roomId) {
            $whereRaw = "`id_card` = '{$this->IDCard}' AND `vacancy_id` = {$this->roomId} AND `status` in (1,2)";
            $info = $this->houseVillageUserBindService->getUserBindRawInfo($whereRaw);
        } elseif ($this->userPhone && $this->roomId) {
            $whereRaw = "`phone` = '{$this->userPhone}' AND `vacancy_id` = {$this->roomId} AND `status` in (1,2)";
            $info = $this->houseVillageUserBindService->getUserBindRawInfo($whereRaw);
        } else {
            $info = [];
        }
        if ($this->operation=='editRoomUsers') {
            $whereUser = [];
            $whereUser[] = ['pigcms_id','=',$infoThirdAccess['businessId']];
            if ($this->roomId) {
                $whereUser[] = ['vacancy_id','=',$this->roomId];
            }
            $homeUser = $this->houseVillageUserBindService->getBindInfo($whereUser);
            if (empty($homeUser)) {
                throw new \Exception("{$name}不存在或者房屋关联有误");
            }
        }
        if ($this->operation=='addRoomUsers' && isset($info['pigcms_id'])&&$info['pigcms_id']&&$info['pigcms_id']) {
            throw new \Exception("同房间{$name}该手机号或者身份证已经存在");
        } elseif ($this->operation=='editRoomUsers' && isset($info['pigcms_id'])&&$info['pigcms_id']&&$info['pigcms_id']!=$infoThirdAccess['businessId']) {
            throw new \Exception("同房间{$name}该手机号或者身份证已经存在");
        }
        if (!in_array($this->userType,$this->userTypeArr)) {
            throw new \Exception('住户类型错误');
        }
        if (!in_array($this->relativesType,$this->relativesTypeArr)) {
            throw new \Exception('住户类型错误');
        }
        if ($this->userType>0) {
            // 如果存在值 减1
            $this->userType -= 1;
        }
        // 查询下该房间的房主
        $whereHomeowner = [];
        $whereHomeowner[] = ['type','in',[0,3]];
        $whereHomeowner[] = ['status','=',1];
        $whereHomeowner[] = ['vacancy_id','=',$this->roomId];
        $homeowner = $this->houseVillageUserBindService->getBindInfo($whereHomeowner);
        $saveParam = [];
        if (0==$this->userType&&$this->operation=='addRoomUsers'&&$this->roomId) {
            // 如果是业主 查看下是否已经存在业主了 添加业主不允许重复
            if (!empty($homeowner)||(isset($homeowner['pigcms_id'])&&$homeowner['pigcms_id'])) {
                throw new \Exception('对应房屋房主已存在');
            }
        } elseif (0==$this->userType&&$this->operation=='editRoomUsers'&&!empty($homeowner)&&isset($homeowner['pigcms_id'])&&$homeowner['pigcms_id']!=$infoThirdAccess['businessId']) {
            // 编辑业主时候查询到的业主不是当前业主
            throw new \Exception('对应房屋房主已存在');
        } elseif(!empty($homeowner)||(isset($homeowner['pigcms_id'])&&$homeowner['pigcms_id'])) {
            $saveParam['parent_id'] = $homeowner['pigcms_id'];
        } else {
            // 如果没有业主 添加虚拟业主
            $parent_id = $this->houseVillageUserBindService->addVirtualHomeowner($this->roomId,$roomInfo);
            if (!$parent_id) {
                throw new \Exception('添加父级失败');
            }
            $saveParam['parent_id'] = $parent_id;
        }
        if ($this->buildId) {
            $saveParam['single_id'] = $this->buildId;
        }
        if ($this->unitId) {
            $saveParam['floor_id'] = $this->unitId;
        }
        if ($this->layerId) {
            $saveParam['layer_id'] = $this->layerId;
        }
        if (isset($param['roomId'])&&$this->roomId) {
            $saveParam['vacancy_id'] = $this->roomId;
        }
        if ($this->userName || $this->userPhone) {
            if ($this->userName) {
                $saveParam['name'] = $this->userName;
            }
            if ($this->userPhone) {
                $saveParam['phone'] = $this->userPhone;
            }
            $uid = $this->getUserUid();
            if ($this->operation=='addRoomUsers') {
                $saveParam['uid'] = $uid;
            }
        }
        if (isset($param['userType'])) {
            $saveParam['type'] = $this->userType;
        }
        if ($this->relativesType) {
            $saveParam['relatives_type'] = $this->relativesType;
        }
        if (isset($param['startTime'])) {
            $saveParam['property_starttime'] = $this->startTime;
        }
        if (isset($param['endTime'])) {
            $saveParam['property_endtime'] = $this->endTime;
        }
        if (isset($param['IDCard'])) {
            $saveParam['id_card'] = $this->IDCard;
        }
        if (isset($param['ICCard'])) {
            $saveParam['ic_card'] = $this->ICCard;
        }
        if (isset($param['memo'])&&$this->memo) {
            $saveParam['memo'] = $this->memo;
        }
        $saveParam['village_id'] = $this->village_id;
        if ($this->operation=='addRoomUsers'||!$infoThirdAccess['businessId']) {
            $saveParam['add_time'] = $this->nowTime;
            $saveParam['usernum'] = $this->thirdType .'_'. createRandomStr().'_'.$this->nowTime;
            $this->childrenBusinessId = $this->houseVillageUserBindService->addUserBindOne($saveParam);
        } elseif ($this->operation=='editRoomUsers'||$infoThirdAccess['businessId']) {
            $saveParam['add_time'] = $this->nowTime;
            $this->childrenBusinessId = $infoThirdAccess['businessId'];
            $whereBind = [];
            $whereBind[] = ['pigcms_id','=',$infoThirdAccess['businessId']];
            $saveId = $this->houseVillageUserBindService->saveUserBind($whereBind,$saveParam);
            if ($saveId===false) {
                throw new \Exception("编辑{{$name}}失败");
            }
            $this->childrenBusinessId = $infoThirdAccess['businessId'];
        }
        return $this->saveThirdConfigData($thirdAccessConfig);
    }
    // 清空房屋下人员 变更状态为 4  已删除
    public function clearRoomUsers($param) {
        $this->village_id = trim($param['village_id']);
        $this->thirdType = trim($param['thirdType']);
        $this->businessType = isset($param['businessType'])&&trim($param['businessType'])?trim($param['businessType']):'village';
        $this->childrenBusinessType = isset($param['childrenBusinessType'])&&trim($param['childrenBusinessType'])?trim($param['childrenBusinessType']):'buildings';
        $this->businessId = $this->village_id;

        $thirdAccessConfig = $this->filterVillageAuth();

        $this->operation = isset($param['operation'])&&trim($param['operation'])?trim($param['operation']):'add';
        $this->thirdId = isset($param['thirdId'])&&trim($param['thirdId'])?trim($param['thirdId']):'';
        $this->thirdSite = isset($param['thirdSite'])&&trim($param['thirdSite'])?trim($param['thirdSite']):'';
        $this->roomId = isset($param['roomId'])&&trim($param['roomId'])?trim($param['roomId']):'';
        if ($this->operation!='clearRoomUsers' || !$this->roomId || !$this->thirdId) {
            // 添加的时候名称和对应对接方的楼栋id必传
            throw new \Exception('缺少必要参数');
        }

        $infoThirdAccess = $this->filterData();

        $whereRoomUsers = [];
        $whereRoomUsers[] = ['vacancy_id','=', $this->roomId];
        $whereRoomUsers[] = ['status','<>', 4];
        $saveRoomUsers = [
            'status' => 4,
        ];
        $saveMsg = $this->houseVillageUserBindService->saveUserBind($whereRoomUsers, $saveRoomUsers);
        if ($saveMsg===false) {
            throw new \Exception('清空失败');
        }
        $paramData = [
            'businessType' => $this->childrenBusinessType,
            'businessId' => $this->roomId,
            'thirdType' => $this->thirdType,
            'thirdSite' => $this->thirdSite,
            'thirdId' => $this->thirdId,
            'thirdName' => $this->thirdName,
            'nowTime' => time(),
        ];
        if (isset($thirdAccessConfig['third_id'])&&$thirdAccessConfig['third_id']) {
            $paramData['thirdFid'] = $thirdAccessConfig['third_id'];
        }
        $this->thirdAccessCheckService->setHouseThirdConfig($paramData);
        unset($paramData['thirdName']);
        unset($paramData['nowTime']);
        return $paramData;
    }
    // 小区操作权限过滤
    public function filterVillageAuth() {
        $whereThirdConfig = [];
        $whereThirdConfig[] = ['thirdType','=',$this->thirdType];
        $whereThirdConfig[] = ['businessType','=',$this->businessType];
        $whereThirdConfig[] = ['businessId','=',$this->businessId];
        $thirdAccessConfig = $this->thirdAccessCheckService->getThirdConfigOne($whereThirdConfig);
        if (empty($thirdAccessConfig)||!isset($thirdAccessConfig['third_id'])||!$thirdAccessConfig['third_id']) {
            throw new \Exception('没有权限进行当前操作');
        }
        return $thirdAccessConfig;
    }
    // 过滤 避免重复操作 不要动 很多方法走这里
    public function filterData() {
        $whereThirdBusiness = [];
        $whereThirdBusiness[] = ['thirdType','=',$this->thirdType];
        $whereThirdBusiness[] = ['businessType','=',$this->childrenBusinessType];
        if ('rooms'==$this->childrenBusinessType&&$this->roomId) {
            // 存在己方房屋id 使用该id
            $whereThirdBusiness[] = ['businessId','=',$this->roomId];
        } elseif ('layers'==$this->childrenBusinessType&&$this->layerId) {
            // 存在己方楼层id 使用该id
            $whereThirdBusiness[] = ['businessId','=',$this->layerId];
        } elseif ('units'==$this->childrenBusinessType&&$this->unitId) {
            // 存在己方单元id 使用该id
            $whereThirdBusiness[] = ['businessId','=',$this->unitId];
        } elseif ('buildings'==$this->childrenBusinessType&&$this->buildId) {
            // 存在己方楼栋id 使用该id
            $whereThirdBusiness[] = ['businessId','=',$this->buildId];
        } elseif ('clearRoomUsers'==$this->childrenBusinessType&&$this->roomId) {
            // 清空房屋下人员-查询2个条件 三方也为必传
            $whereThirdBusiness[] = ['businessId','=',$this->roomId];
            $whereThirdBusiness[] = ['thirdId','=',$this->thirdId];
        }  elseif ('editRoomUsers'==$this->operation&&'roomUsers'==$this->childrenBusinessType&&$this->roomUserId) {
            // 添加人员
            $whereThirdBusiness[] = ['businessId','=',$this->roomUserId];
        } else {
            // 否则使用三方id
            $whereThirdBusiness[] = ['thirdId','=',$this->thirdId];
        }
        $infoThirdAccess = $this->thirdAccessCheckService->getThirdConfigOne($whereThirdBusiness);
        if ($this->operation=='add' && isset($infoThirdAccess['third_id'])&&$infoThirdAccess['third_id']) {
            $this->childrenBusinessId = $infoThirdAccess['businessId'];
            return [
                'businessType' => $this->childrenBusinessType, 'businessId' => $this->childrenBusinessId,
                'thirdType' => $this->thirdType, 'thirdSite' => $this->thirdSite, 'thirdId' => $this->thirdId,
            ];
        } elseif ($this->operation=='edit' && (!isset($infoThirdAccess['third_id']) || !$infoThirdAccess['third_id'])) {
            throw new \Exception('缺少更新对象');
        } elseif ($this->operation=='clearRoomUsers' && (!isset($infoThirdAccess['third_id']) || !$infoThirdAccess['third_id'])) {
            throw new \Exception('缺少清空对象');
        } elseif ($this->operation=='addRoomUsers' && isset($infoThirdAccess['third_id'])&&$infoThirdAccess['third_id']) {
            $this->childrenBusinessId = $infoThirdAccess['businessId'];
            return [
                'businessType' => $this->childrenBusinessType, 'businessId' => $this->childrenBusinessId,
                'thirdType' => $this->thirdType, 'thirdSite' => $this->thirdSite, 'thirdId' => $this->thirdId,
            ];
        }
        return $infoThirdAccess;
    }
    // 统一保存变更三方信息
    private function saveThirdConfigData($thirdAccessConfig=[]) {
        $paramData = [
            'businessType' => $this->childrenBusinessType,
            'businessId' => $this->childrenBusinessId,
            'thirdType' => $this->thirdType,
            'thirdSite' => $this->thirdSite,
            'thirdId' => $this->thirdId,
            'thirdName' => $this->thirdName,
            'nowTime' => $this->nowTime?$this->nowTime:time(),
        ];
        if (isset($thirdAccessConfig['third_id'])&&$thirdAccessConfig['third_id']) {
            $paramData['thirdFid'] = $thirdAccessConfig['third_id'];
        }
        $this->thirdAccessCheckService->setHouseThirdConfig($paramData);
        unset($paramData['thirdName']);
        unset($paramData['nowTime']);
        unset($paramData['thirdFid']);
        return $paramData;
    }
    // 房屋重置信息
    public function resetRooms() {
        // 存在房屋id 查询下房屋信息
        $whereRooms = [];
        $whereRooms[] = ['pigcms_id','=', $this->roomId];
        $roomFields = "pigcms_id,uid,name,phone,floor_id,village_id,layer,room,housesize,single_id,layer_id,usernum,property_number,room,room_number";
        $roomInfo = $this->houseVillageUserVacancyService->getUserVacancyInfo($whereRooms, $roomFields);
        if (empty($roomInfo) || !isset($roomInfo['pigcms_id'])) {
            throw new \Exception('对应房屋不存在');
        }
        if (!$this->buildId&&isset($roomInfo['single_id'])&&$roomInfo['single_id']) {
            $this->buildId = $roomInfo['single_id'];
        } elseif ($this->buildId&&isset($roomInfo['single_id'])&&$roomInfo['single_id']&&$this->buildId!=$roomInfo['single_id']) {
            throw new \Exception('关联楼栋ID有误');
        }
        if (!$this->unitId&&isset($roomInfo['floor_id'])&&$roomInfo['floor_id']) {
            $this->unitId = $roomInfo['floor_id'];
        } elseif ($this->unitId&&isset($roomInfo['floor_id'])&&$roomInfo['floor_id']&&$this->unitId!=$roomInfo['floor_id']) {
            throw new \Exception('关联单元ID有误');
        }
        if (!$this->layerId&&isset($roomInfo['layer_id'])&&$roomInfo['layer_id']) {
            $this->layerId = $roomInfo['layer_id'];
        } elseif ($this->layerId&&isset($roomInfo['layer_id'])&&$roomInfo['layer_id']&&$this->layerId!=$roomInfo['layer_id']) {
            throw new \Exception('关联楼层ID有误');
        }
        return $roomInfo;
    }
    // 处理下uid问题
    public function getUserUid($isAdd=false) {
        if ($this->userPhone) {
            $where_user = [];
            $where_user[] = ['phone','=',$this->userPhone];
            $user = $this->userService->getUserOne($where_user,'uid');
            if (!empty($user)&&isset($user['uid'])&&$user['uid']) {
                return $user['uid'];
            } elseif ($isAdd) {
                // 如果默认注册一个
                $data = [
                    'phone'=>$this->userPhone,
                ];
                if ($this->userName) {
                    $data['truename'] = $this->userName;
                }
                $data['source'] = 'houseautoreg_third';
                $uid = $this->userService->addUser($data);
                return $uid;
            }
        } elseif ($isAdd&&$this->userName) {
            $where_user = [];
            $where_user[] = ['truename','=',$this->userName];
            $where_user[] = ['phone','=',''];
            $where_user[] = ['openid','=',''];
            $where_user[] = ['wxapp_openid','=',''];
            $where_user[] = ['paotui_openid','=',''];
            $where_user[] = ['union_id','=',''];
            $user = $this->userService->getUserOne($where_user,'uid');
            if (!empty($user)&&isset($user['uid'])&&$user['uid']) {
                return $user['uid'];
            } else {
                // 如果默认注册一个
                $data = [
                    'truename'=>$this->userName,
                ];
                $data['source'] = 'houseautoreg_third';
                $uid = $this->userService->addUser($data);
                return $uid;
            }
        }
        return 0;
    }
    // 处理房屋物业编号问题
    public function getRoomNumbers($roomParams=[],$save=0) {
        if ($this->roomId>0) {
            // ↑ 存在房屋编辑信息
            if (empty($roomParams)) {
                // 存在房屋id 查询下房屋信息
                $roomParams = $this->resetRooms();
                $saveData = [];
                if (empty($roomParams['usernum'])&&empty($this->userNum)) {
                    // 如果 查询结果 旧物业编号为空且接口没有传参 生成对应物业编号
                    $this->userNum = $roomParams['village_id'].'-'.$roomParams['single_id'].'-'.$roomParams['floor_id'].'-'.$roomParams['layer_id'].'-'.$this->roomId;
                    $saveData['usernum'] = $this->userNum;
                }
                if (empty($roomParams['property_number'])&&empty($this->propertyNumber)) {
                    // 如果 查询结果 旧物业编号为空且接口没有传参 生成对应物业编号
                    $whereSingle = [];
                    $whereSingle[] = ['id','=', $roomParams['single_id']];
                    $buildInfo = $this->houseVillageSingleService->getSingleInfo($whereSingle,'id,single_number');
                    $single_number = isset($buildInfo['single_number'])&&$buildInfo['single_number']?$buildInfo['single_number']:'';
                    if (!$single_number) {
                        $this->propertyNumber = '';
                        return true;
                    }
                    $whereFloor = [];
                    $whereFloor[] = ['floor_id','=', $roomParams['floor_id']];
                    $floorInfo = $this->houseVillageSingleService->getFloorInfo($whereFloor,'floor_id,floor_number');
                    $floor_number = isset($floorInfo['floor_number'])&&$floorInfo['floor_number']?$floorInfo['floor_number']:'';
                    if (!$floor_number) {
                        $this->propertyNumber = '';
                        return true;
                    }
                    $whereLayer = [];
                    $whereLayer[] = ['id','=', $roomParams['layer_id']];
                    $layerInfo = $this->houseVillageSingleService->getLayerInfo($whereLayer,'id,layer_number');
                    $layer_number = isset($layerInfo['layer_number'])&&$layerInfo['layer_number']?$layerInfo['layer_number']:'';
                    if (!$layer_number) {
                        $this->propertyNumber = '';
                        return true;
                    }
                    $room_number = isset($roomParams['room_number'])&&$roomParams['room_number']?$roomParams['room_number']:'';
                    if (!$room_number) {
                        $pattern_number = "/[^0-9]/";
                        $room_number = preg_replace($pattern_number,'',$roomParams['room']);
                    }
                    if (!$room_number) {
                        $this->propertyNumber = '';
                        return true;
                    }if (strlen($room_number)<=2) {
                        // 不足2位 补足4位
                        $room_number = str_pad($room_number,2,"0",STR_PAD_LEFT);
                        $room_number = $layer_number . $room_number;
                    } elseif (strlen($room_number)<=4) {
                        // 不足4位 补足4位
                        $room_number = str_pad($room_number,4,"0",STR_PAD_LEFT);
                    }
                    $this->propertyNumber = $single_number.$floor_number.$layer_number.$room_number;
                    $saveData['property_number'] = $this->propertyNumber;
                }
                if ($save==1&&!empty($saveData)) {
                    $whereRoom = [];
                    $whereRoom[] = ['pigcms_id','=',$this->roomId];
                    $this->houseVillageUserVacancyService->saveRoomsInfo($whereRoom,$saveData);
                }
            }
        }
        return true;
    }
    // 查看详情
    public function lookDetail() {
        if (!$this->lookId) {
            return [];
        } elseif ('buildings'==$this->childrenBusinessType) {
            // 楼栋相关
            $this->buildId = $this->lookId;
            $whereSingle = [];
            $whereSingle[] = ['id','=', $this->buildId];
            $lookInfo = $this->houseVillageSingleService->getSingleInfo($whereSingle,'id as buildId,single_name as buildName,sort,single_number as buildNumber');
            if (empty($lookInfo)||!isset($lookInfo['buildId'])||!$lookInfo['buildId']) {
                $lookInfo = [];
            }
            return $lookInfo;
        } elseif ('units'==$this->childrenBusinessType) {
            // 单元相关
            $this->unitId = $this->lookId;
            $whereFloor = [];
            $whereFloor[] = ['floor_id','=', $this->unitId];
            $lookInfo = $this->houseVillageSingleService->getFloorInfo($whereFloor,'floor_id as unitId,floor_name as unitName,sort,floor_number as unitNumber');
            if (empty($lookInfo)||!isset($lookInfo['unitId'])||!$lookInfo['unitId']) {
                $lookInfo = [];
            }
            return $lookInfo;
        } elseif ('layers'==$this->childrenBusinessType) {
            // 楼层相关
            $this->layerId = $this->lookId;
            $whereLayer = [];
            $whereLayer[] = ['id','=', $this->layerId];
            $lookInfo = $this->houseVillageSingleService->getLayerInfo($whereLayer,'id as layerId,layer_name as layerName,sort,layer_number as layerNumber');
            if (empty($lookInfo)||!isset($lookInfo['layerId'])||!$lookInfo['layerId']) {
                $lookInfo = [];
            }
            return $lookInfo;
        } elseif ('rooms'==$this->childrenBusinessType) {
            // 房屋相关
            $this->roomId = $this->lookId;
            $whereRooms = [];
            $whereRooms[] = ['pigcms_id','=', $this->roomId];
            $roomFiled = 'pigcms_id as roomId,room as roomName,sort,room_number as roomNumber,house_type as roomType,user_status as useStatus,sell_status as sellStatus,usernum as userNum,property_number as propertyNumber';
            $lookInfo = $this->houseVillageUserVacancyService->getUserVacancyInfo($whereRooms,$roomFiled);
            if (empty($lookInfo)||!isset($lookInfo['roomId'])||!$lookInfo['roomId']) {
                $lookInfo = [];
            }
            return $lookInfo;
        } elseif ('roomUsers'==$this->childrenBusinessType) {
            // 房屋相关
            $this->roomUserId = $this->lookId;
            $whereRoomUsers = [];
            $whereRoomUsers[] = ['pigcms_id','=', $this->roomUserId];
            $userFiled = 'pigcms_id as roomUserId,name,phone,type,relatives_type as relativesType,property_starttime as startTime,property_endtime as endTime,id_card as IDCard,ic_card as ICCard';
            $lookInfo = $this->houseVillageUserBindService->getBindInfo($whereRoomUsers,$userFiled);
            if (empty($lookInfo)||!isset($lookInfo['roomUserId'])||!$lookInfo['roomUserId']) {
                $lookInfo = [];
            }
            return $lookInfo;
        }
        return [];
    }

    public function delDetail() {
        $detail = $this->lookDetail();
        if (empty($detail)) {
            throw new \Exception('删除对象不存在或者已经被删除');
        }
        if (!$this->deleteId) {
            throw new \Exception('删除对象不存在或者已经被删除');
        } elseif ('buildings'==$this->childrenBusinessType) {
            // 楼栋相关
            $whereFloor = [];
            $whereFloor[] = ['status','<>', 4];
            $whereFloor[] = ['single_id','=', $this->deleteId];
            $childInfo = $this->houseVillageSingleService->getFloorInfo($whereFloor,'floor_id');
            if (!empty($childInfo)&&isset($childInfo['floor_id'])) {
                throw new \Exception('清先删除楼栋下单元信息');
            }
            $buildData = [];
            $buildData['status'] = 4;
            $buildData['update_time'] = $this->nowTime;
            $whereSingle = [];
            $whereSingle[] = ['id','=', $this->deleteId];
            $saveId = $this->houseVillageSingleService->saveSingleInfo($whereSingle,$buildData);
            if ($saveId===false) {
                throw new \Exception('删除失败');
            }
            return $detail;
        } elseif ('units'==$this->childrenBusinessType) {
            // 单元相关
            $whereLayer = [];
            $whereLayer[] = ['status','<>',4];
            $whereLayer[] = ['floor_id','=', $this->deleteId];
            $childInfo = $this->houseVillageSingleService->getLayerInfo($whereLayer,'id');
            if (!empty($childInfo)&&isset($childInfo['id'])) {
                throw new \Exception('清先删除单元下楼层信息');
            }
            $saveData = [];
            $saveData['status'] = 4;
            $saveData['update_time'] = $this->nowTime;
            $whereSave = [];
            $whereSave[] = ['floor_id','=', $this->deleteId];
            $saveId = $this->houseVillageSingleService->saveFloorInfo($whereSave,$saveData);
            if ($saveId===false) {
                throw new \Exception('删除失败');
            }
            return $detail;
        } elseif ('layers'==$this->childrenBusinessType) {
            // 楼层相关
            $whereRooms = [];
            $whereRooms[] = ['is_del','=', 0];
            $whereRooms[] = ['layer_id','=', $this->deleteId];
            $roomFiled = 'pigcms_id';
            $childInfo = $this->houseVillageUserVacancyService->getUserVacancyInfo($whereRooms,$roomFiled);
            if (!empty($childInfo)&&isset($childInfo['pigcms_id'])) {
                throw new \Exception('清先删除楼层下房屋信息');
            }
            $saveData = [];
            $saveData['status'] = 4;
            $whereSave = [];
            $whereSave[] = ['id','=', $this->deleteId];
            $saveId = $this->houseVillageSingleService->saveLayerInfo($whereSave,$saveData);
            if ($saveId===false) {
                throw new \Exception('删除失败');
            }
            return $detail;
        } elseif ('rooms'==$this->childrenBusinessType) {
            // 房屋相关
            $whereRoomUsers = [];
            $whereRoomUsers[] = ['status','<>', 4];
            $whereRoomUsers[] = ['vacancy_id','=', $this->deleteId];
            $userFiled = 'pigcms_id';
            $childInfo = $this->houseVillageUserVacancyService->getBindInfo($whereRoomUsers,$userFiled);
            if (!empty($childInfo)&&isset($childInfo['pigcms_id'])) {
                throw new \Exception('清先删除房屋下人员信息');
            }
            $saveData = [];
            $saveData['is_del'] = 1;
            $saveData['del_time'] = $this->nowTime;
            $saveData['usernum'] = isset($detail['userNum'])&&trim($detail['userNum'])?'del'.$detail['userNum']:'del_'.$this->deleteId.'_'.$this->nowTime;
            $whereSave = [];
            $whereSave[] = ['pigcms_id','=', $this->deleteId];
            $saveId = $this->houseVillageUserVacancyService->saveRoomsInfo($whereSave,$saveData);
            if ($saveId===false) {
                throw new \Exception('删除失败');
            }
            return $detail;
        } elseif ('roomUsers'==$this->childrenBusinessType) {
            // 房屋相关
            $saveParam = [];
            $saveParam['status'] = 4;
            $saveParam['usernum'] = 'del_'.$this->deleteId.'_'.$this->nowTime;
            $whereBind = [];
            $whereBind[] = ['pigcms_id','=',$this->deleteId];
            $saveId = $this->houseVillageUserBindService->saveUserBind($whereBind,$saveParam);
            if ($saveId===false) {
                throw new \Exception('删除失败');
            }
            return $detail;
        }
        return [];
    }
}