<?php

namespace app\thirdAccess\controller\village;
use app\community\controller\CommunityBaseController;

use app\thirdAccess\model\service\ThirdAccessService;

class AssetsManagementController extends CommunityBaseController
{
    // 添加楼栋
    public function addBuildings() {
        $village_id = $this->request->param('village_id','','intval');
        $thirdErrCode = $this->request->thirdErrCode;
        $thirdErrMsg = $this->request->thirdErrMsg;
        $thirdType = $this->request->thirdType;
        $thirdSite = $this->request->thirdSite;
        if ($thirdErrMsg&&$thirdErrCode) {
            return api_output_error($thirdErrCode, $thirdErrMsg);
        }
        $thirdId = isset($_POST['thirdBuildId'])&&trim($_POST['thirdBuildId'])?trim($_POST['thirdBuildId']):'';
        $single_name = $this->request->param('buildName','','trim');
        $sort = $this->request->param('sort','0','intval');
        $single_number = $this->request->param('buildNumber');
        $childrenBusinessType = 'buildings';
        $paramData = [
            'village_id' => $village_id,
            'thirdType' => $thirdType,
            'businessType' => 'village',
            'operation' => 'add',
            'thirdId' => $thirdId,
            'thirdSite' => $thirdSite,
            'buildName' => $single_name,
            'sort' => $sort,
            'buildNumber' => $single_number,
            'childrenBusinessType' => $childrenBusinessType,
        ];
        $thirdAccessService = new ThirdAccessService();
        try {
           $info = $thirdAccessService->setThirdAccessData($paramData);
        } catch (\Exception $e) {
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0,$info);
    }
    // 更新楼栋
    public function updateBuildings() {
        $village_id = $this->request->param('village_id','','intval');
        $thirdErrCode = $this->request->thirdErrCode;
        $thirdErrMsg = $this->request->thirdErrMsg;
        $thirdType = $this->request->thirdType;
        $thirdSite = $this->request->thirdSite;
        if ($thirdErrMsg&&$thirdErrCode) {
            return api_output_error($thirdErrCode, $thirdErrMsg);
        }
        $thirdId = isset($_POST['thirdBuildId'])&&trim($_POST['thirdBuildId'])?trim($_POST['thirdBuildId']):'';
        $single_name = $this->request->param('buildName','','trim');
        $buildId = $this->request->param('buildId','0','intval');
        $sort = $this->request->param('sort','0','intval');
        $single_number = $this->request->param('buildNumber');
        $childrenBusinessType = 'buildings';
        $paramData = [
            'village_id' => $village_id,
            'thirdType' => $thirdType,
            'businessType' => 'village',
            'operation' => 'edit',
            'thirdId' => $thirdId,
            'thirdSite' => $thirdSite,
            'buildName' => $single_name,
            'sort' => $sort,
            'buildNumber' => $single_number,
            'buildId' => $buildId,
            'childrenBusinessType' => $childrenBusinessType,
        ];
        $thirdAccessService = new ThirdAccessService();
        try {
            $info = $thirdAccessService->setThirdAccessData($paramData);
        } catch (\Exception $e) {
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0,$info);
    }
    // 添加单元
    public function addUnits() {
        $village_id = $this->request->param('village_id','','intval');
        $thirdErrCode = $this->request->thirdErrCode;
        $thirdErrMsg = $this->request->thirdErrMsg;
        $thirdType = $this->request->thirdType;
        $thirdSite = $this->request->thirdSite;
        if ($thirdErrMsg&&$thirdErrCode) {
            return api_output_error($thirdErrCode, $thirdErrMsg);
        }
        $thirdId = isset($_POST['thirdUnitId'])&&trim($_POST['thirdUnitId'])?trim($_POST['thirdUnitId']):'';
        $floor_name = $this->request->param('unitName','','trim');
        $single_id = $this->request->param('buildId','0','intval');
        $sort = $this->request->param('sort','0','intval');
        $floor_number = $this->request->param('unitNumber');
        $childrenBusinessType = 'units';
        $paramData = [
            'village_id' => $village_id,
            'thirdType' => $thirdType,
            'businessType' => 'village',
            'operation' => 'add',
            'thirdId' => $thirdId,
            'thirdSite' => $thirdSite,
            'buildId' => $single_id,
            'unitName' => $floor_name,
            'sort' => $sort,
            'unitNumber' => $floor_number,
            'childrenBusinessType' => $childrenBusinessType,
        ];
        $thirdAccessService = new ThirdAccessService();
        try {
            $info = $thirdAccessService->setThirdAccessData($paramData);
        } catch (\Exception $e) {
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0,$info);
    }
    // 更新单元
    public function updateUnits() {
        $village_id = $this->request->param('village_id','','intval');
        $thirdErrCode = $this->request->thirdErrCode;
        $thirdErrMsg = $this->request->thirdErrMsg;
        $thirdType = $this->request->thirdType;
        $thirdSite = $this->request->thirdSite;
        if ($thirdErrMsg&&$thirdErrCode) {
            return api_output_error($thirdErrCode, $thirdErrMsg);
        }
        $thirdId = isset($_POST['thirdUnitId'])&&trim($_POST['thirdUnitId'])?trim($_POST['thirdUnitId']):'';
        $floor_name = $this->request->param('unitName','','trim');
        $single_id = $this->request->param('buildId','0','intval');
        $floor_id = $this->request->param('unitId','0','intval');
        $sort = $this->request->param('sort','0','intval');
        $floor_number = $this->request->param('unitNumber');

        $childrenBusinessType = 'units';
        $paramData = [
            'village_id' => $village_id,
            'thirdType' => $thirdType,
            'businessType' => 'village',
            'operation' => 'edit',
            'thirdId' => $thirdId,
            'thirdSite' => $thirdSite,
            'buildId' => $single_id,
            'unitId' => $floor_id,
            'unitName' => $floor_name,
            'sort' => $sort,
            'unitNumber' => $floor_number,
            'childrenBusinessType' => $childrenBusinessType,
        ];
        $thirdAccessService = new ThirdAccessService();
        try {
            $info = $thirdAccessService->setThirdAccessData($paramData);
        } catch (\Exception $e) {
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0,$info);
    }
    // 添加楼层
    public function addLayers() {
        $village_id = $this->request->param('village_id','','intval');
        $thirdErrCode = $this->request->thirdErrCode;
        $thirdErrMsg = $this->request->thirdErrMsg;
        $thirdType = $this->request->thirdType;
        $thirdSite = $this->request->thirdSite;
        if ($thirdErrMsg&&$thirdErrCode) {
            return api_output_error($thirdErrCode, $thirdErrMsg);
        }
        $thirdId = isset($_POST['thirdLayerId'])&&trim($_POST['thirdLayerId'])?trim($_POST['thirdLayerId']):'';
        $single_id = $this->request->param('buildId','0','intval');
        $floor_id = $this->request->param('unitId','0','intval');
        $sort = $this->request->param('sort','0','intval');
        $layer_name = $this->request->param('layerName','','trim');
        $layer_number = $this->request->param('layerNumber');
        $childrenBusinessType = 'layers';
        $paramData = [
            'village_id' => $village_id,
            'thirdType' => $thirdType,
            'businessType' => 'village',
            'operation' => 'add',
            'thirdId' => $thirdId,
            'thirdSite' => $thirdSite,
            'buildId' => $single_id,
            'unitId' => $floor_id,
            'layerName' => $layer_name,
            'sort' => $sort,
            'layerNumber' => $layer_number,
            'childrenBusinessType' => $childrenBusinessType,
        ];
        $thirdAccessService = new ThirdAccessService();
        try {
            $info = $thirdAccessService->setThirdAccessData($paramData);
        } catch (\Exception $e) {
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0,$info);
    }
    // 更新楼层
    public function updateLayers() {
        $village_id = $this->request->param('village_id','','intval');
        $thirdErrCode = $this->request->thirdErrCode;
        $thirdErrMsg = $this->request->thirdErrMsg;
        $thirdType = $this->request->thirdType;
        $thirdSite = $this->request->thirdSite;
        if ($thirdErrMsg&&$thirdErrCode) {
            return api_output_error($thirdErrCode, $thirdErrMsg);
        }
        $thirdId = isset($_POST['thirdLayerId'])&&trim($_POST['thirdLayerId'])?trim($_POST['thirdLayerId']):'';
        $single_id = $this->request->param('buildId','0','intval');
        $floor_id = $this->request->param('unitId','0','intval');
        $layer_id = $this->request->param('layerId','0','intval');
        $sort = $this->request->param('sort','0','intval');
        $layer_name = $this->request->param('layerName','','trim');
        $layer_number = $this->request->param('layerNumber');

        $childrenBusinessType = 'layers';
        $paramData = [
            'village_id' => $village_id,
            'thirdType' => $thirdType,
            'businessType' => 'village',
            'operation' => 'edit',
            'thirdId' => $thirdId,
            'thirdSite' => $thirdSite,
            'buildId' => $single_id,
            'unitId' => $floor_id,
            'layerId' => $layer_id,
            'layerName' => $layer_name,
            'sort' => $sort,
            'layerNumber' => $layer_number,
            'childrenBusinessType' => $childrenBusinessType,
        ];
        $thirdAccessService = new ThirdAccessService();
        try {
            $info = $thirdAccessService->setThirdAccessData($paramData);
        } catch (\Exception $e) {
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0,$info);
    }
    // 添加房屋
    public function addRooms() {
        $village_id = $this->request->param('village_id','','intval');
        $thirdErrCode = $this->request->thirdErrCode;
        $thirdErrMsg = $this->request->thirdErrMsg;
        $thirdType = $this->request->thirdType;
        $thirdSite = $this->request->thirdSite;
        if ($thirdErrMsg&&$thirdErrCode) {
            return api_output_error($thirdErrCode, $thirdErrMsg);
        }
        $thirdId = isset($_POST['thirdRoomId'])&&trim($_POST['thirdRoomId'])?trim($_POST['thirdRoomId']):'';
        $single_id = $this->request->param('buildId','0','intval');
        $floor_id = $this->request->param('unitId','0','intval');
        $layer_id = $this->request->param('layerId','0','intval');
        $sort = $this->request->param('sort','0','intval');
        $room = $this->request->param('roomName','','trim');
        $room_number = $this->request->param('roomNumber');
        $house_type = $this->request->param('roomType','3','trim'); // 类型 1住宅 2商铺 3办公
        $user_status = $this->request->param('useStatus',0,'intval'); // 使用状态 1业主入住 2未入住 3租客入住
        $sell_status = $this->request->param('sellStatus',0,'intval'); // 出售状态 1正常居住 2出售中 3出租中
        $usernum = $this->request->param('userNum','','trim'); // 物业原始编号
        $property_number = $this->request->param('propertyNumber','','trim'); // 物业编号
        $childrenBusinessType = 'rooms';
        $paramData = [
            'childrenBusinessType' => $childrenBusinessType,
            'village_id' => $village_id,
            'thirdType' => $thirdType,
            'businessType' => 'village',
            'operation' => 'add',
            'thirdId' => $thirdId,
            'thirdSite' => $thirdSite,
            'buildId' => $single_id,
            'unitId' => $floor_id,
            'layerId' => $layer_id,
            'roomName' => $room,
            'sort' => $sort,
            'roomNumber' => $room_number,
            'roomType' => $house_type,
            'useStatus' => $user_status,
            'sellStatus' => $sell_status,
            'userNum' => $usernum,
            'propertyNumber' => $property_number,
        ];
        $thirdAccessService = new ThirdAccessService();
        try {
            $info = $thirdAccessService->setThirdAccessData($paramData);
        } catch (\Exception $e) {
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0,$info);
    }
    // 更新房屋
    public function updateRooms() {
        $village_id = $this->request->param('village_id','','intval');
        $thirdErrCode = $this->request->thirdErrCode;
        $thirdErrMsg = $this->request->thirdErrMsg;
        $thirdType = $this->request->thirdType;
        $thirdSite = $this->request->thirdSite;
        if ($thirdErrMsg&&$thirdErrCode) {
            return api_output_error($thirdErrCode, $thirdErrMsg);
        }
        $thirdId = isset($_POST['thirdRoomId'])&&trim($_POST['thirdRoomId'])?trim($_POST['thirdRoomId']):'';
        $single_id = $this->request->param('buildId','0','intval');
        $floor_id = $this->request->param('unitId','0','intval');
        $layer_id = $this->request->param('layerId','0','intval');
        $room_id = $this->request->param('roomId','0','intval');
        $sort = $this->request->param('sort','0','intval');
        $room = $this->request->param('roomName','','trim');
        $room_number = $this->request->param('roomNumber');
        $house_type = $this->request->param('roomType',0,'intval'); // 类型 1住宅 2商铺 3办公
        $user_status = $this->request->param('useStatus',0,'intval'); // 使用状态 1业主入住 2未入住 3租客入住
        $sell_status = $this->request->param('sellStatus',0,'intval'); // 出售状态 1正常居住 2出售中 3出租中
        $usernum = $this->request->param('userNum','','trim'); // 物业原始编号
        $property_number = $this->request->param('propertyNumber','','trim'); // 物业编号
        $childrenBusinessType = 'rooms';
        $paramData = [
            'childrenBusinessType' => $childrenBusinessType,
            'village_id' => $village_id,
            'thirdType' => $thirdType,
            'businessType' => 'village',
            'operation' => 'edit',
            'thirdId' => $thirdId,
            'thirdSite' => $thirdSite,
            'buildId' => $single_id,
            'unitId' => $floor_id,
            'layerId' => $layer_id,
            'roomId' => $room_id,
            'roomName' => $room,
            'sort' => $sort,
            'roomNumber' => $room_number,
            'roomType' => $house_type,
            'useStatus' => $user_status,
            'sellStatus' => $sell_status,
            'userNum' => $usernum,
            'propertyNumber' => $property_number,
        ];
        $thirdAccessService = new ThirdAccessService();
        try {
            $info = $thirdAccessService->setThirdAccessData($paramData);
        } catch (\Exception $e) {
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0,$info);
    }
    // 清空房屋下所有人
    public function clearRoomUsers() {
        $village_id = $this->request->param('village_id','','intval');
        $thirdErrCode = $this->request->thirdErrCode;
        $thirdErrMsg = $this->request->thirdErrMsg;
        $thirdType = $this->request->thirdType;
        $thirdSite = $this->request->thirdSite;
        if ($thirdErrMsg&&$thirdErrCode) {
            return api_output_error($thirdErrCode, $thirdErrMsg);
        }
        $room_id = $this->request->param('roomId','0','intval');
        $thirdId = isset($_POST['thirdRoomId'])&&trim($_POST['thirdRoomId'])?trim($_POST['thirdRoomId']):'';
        $childrenBusinessType = 'clearRoomUsers';
        // 清空房屋下所有人员
        $paramData = [
            'village_id' => $village_id,
            'thirdType' => $thirdType,
            'businessType' => 'village',
            'operation' => 'clearRoomUsers',
            'thirdSite' => $thirdSite,
            'roomId' => $room_id,
            'thirdId' => $thirdId,
            'childrenBusinessType' => $childrenBusinessType,
        ];
        $thirdAccessService = new ThirdAccessService();
        try {
            $info = $thirdAccessService->clearRoomUsers($paramData);
        } catch (\Exception $e) {
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0,$info);
    }
    // 添加住户
    public function addRoomUsers() {
        $village_id = $this->request->param('village_id','','intval');
        $thirdErrCode = $this->request->thirdErrCode;
        $thirdErrMsg = $this->request->thirdErrMsg;
        $thirdType = $this->request->thirdType;
        $thirdSite = $this->request->thirdSite;
        if ($thirdErrMsg&&$thirdErrCode) {
            return api_output_error($thirdErrCode, $thirdErrMsg);
        }
        $thirdId = isset($_POST['thirdUserId'])&&trim($_POST['thirdUserId'])?trim($_POST['thirdUserId']):'';

        $single_id = $this->request->param('buildId','0','intval');
        $floor_id = $this->request->param('unitId','0','intval');
        $layer_id = $this->request->param('layerId','0','intval');
        $room_id = $this->request->param('roomId','0','intval');

        $name = $this->request->param('name','','trim');
        $phone = $this->request->param('phone','','trim');
        $type = $this->request->param('type','0','intval');
        // ↑ 类型  1房主  2家人  3租客  记得对应转换  1对应0，添加业主保持一个 第二个不允许添加  2对应1  3对应2
        $relatives_type = $this->request->param('relativesType','4','intval');
        // ↑ type类型为1 家人时候起效 1配偶 2 父母 3子女 4亲朋好友 5公司负责人 6公司人事 7公司财务  默认亲朋好友
        $property_starttime = $this->request->param('startTime','0','intval');
        // ↑ 物业服务开始时间
        $property_endtime = $this->request->param('endTime','0','intval');
        // ↑ 物业服务截止时间
        $id_card =  $this->request->param('IDCard','','trim');
        // ↑ 身份证
        $ic_card =  $this->request->param('ICCard','','trim');
        // ↑ ic卡

        $childrenBusinessType = 'roomUsers';
        // 清空房屋下所有人员
        $paramData = [
            'village_id' => $village_id,
            'thirdType' => $thirdType,
            'businessType' => 'village',
            'operation' => 'addRoomUsers',
            'thirdSite' => $thirdSite,
            'buildId' => $single_id,
            'unitId' => $floor_id,
            'layerId' => $layer_id,
            'roomId' => $room_id,
            'thirdId' => $thirdId,
            'childrenBusinessType' => $childrenBusinessType,
            'userName' => $name,
            'userPhone' => $phone,
            'userType' => $type,
            'relativesType' => $relatives_type,
            'startTime' => $property_starttime,
            'endTime' => $property_endtime,
            'IDCard' => $id_card,
            'ICCard' => $ic_card,
        ];
        $thirdAccessService = new ThirdAccessService();
        try {
            $info = $thirdAccessService->setThirdAccessData($paramData);
        } catch (\Exception $e) {
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0,$info);
    }
    // 更新住户
    public function updateRoomUsers() {
        $village_id = $this->request->param('village_id','','intval');
        $thirdErrCode = $this->request->thirdErrCode;
        $thirdErrMsg = $this->request->thirdErrMsg;
        $thirdType = $this->request->thirdType;
        $thirdSite = $this->request->thirdSite;
        if ($thirdErrMsg&&$thirdErrCode) {
            return api_output_error($thirdErrCode, $thirdErrMsg);
        }
        $thirdId = isset($_POST['thirdUserId'])&&trim($_POST['thirdUserId'])?trim($_POST['thirdUserId']):'';

        $single_id = $this->request->param('buildId','0','intval');
        $floor_id = $this->request->param('unitId','0','intval');
        $layer_id = $this->request->param('layerId','0','intval');
        $room_id = $this->request->param('roomId','0','intval');
        $roomUserId = $this->request->param('roomUserId','0','intval');

        $name = $this->request->param('name','','trim');
        $phone = $this->request->param('phone','','trim');
        $type = $this->request->param('type','0','intval');
        // ↑ 类型  1房主  2家人  3租客  记得对应转换  1对应0，添加业主保持一个 第二个不允许添加  2对应1  3对应2
        $relatives_type = $this->request->param('relativesType','4','intval');
        // ↑ type类型为1 家人时候起效 1配偶 2 父母 3子女 4亲朋好友 5公司负责人 6公司人事 7公司财务  默认亲朋好友
        $property_starttime = $this->request->param('startTime','0','intval');
        // ↑ 物业服务开始时间
        $property_endtime = $this->request->param('endTime','0','intval');
        // ↑ 物业服务截止时间
        $id_card =  $this->request->param('IDCard','','trim');
        // ↑ 身份证
        $ic_card =  $this->request->param('ICCard','','trim');
        // ↑ ic卡

        $childrenBusinessType = 'roomUsers';
        // 清空房屋下所有人员
        $paramData = [
            'village_id' => $village_id,
            'thirdType' => $thirdType,
            'businessType' => 'village',
            'operation' => 'editRoomUsers',
            'thirdSite' => $thirdSite,
            'thirdId' => $thirdId,
            'childrenBusinessType' => $childrenBusinessType,
            'buildId' => $single_id,
            'unitId' => $floor_id,
            'layerId' => $layer_id,
            'roomId' => $room_id,
            'roomUserId' => $roomUserId,
            'userName' => $name,
            'userPhone' => $phone,
            'userType' => $type,
            'relativesType' => $relatives_type,
            'startTime' => $property_starttime,
            'endTime' => $property_endtime,
            'IDCard' => $id_card,
            'ICCard' => $ic_card,
        ];
        $thirdAccessService = new ThirdAccessService();
        try {
            $info = $thirdAccessService->setThirdAccessData($paramData);
        } catch (\Exception $e) {
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0,$info);
    }
    // 查询信息
    public function lookAssetsDetail() {
        $village_id = $this->request->param('village_id','','intval');
        $thirdErrCode = $this->request->thirdErrCode;
        $thirdErrMsg = $this->request->thirdErrMsg;
        $thirdType = $this->request->thirdType;
        if ($thirdErrMsg&&$thirdErrCode) {
            return api_output_error($thirdErrCode, $thirdErrMsg);
        }
        $childrenBusinessType = isset($_POST['lookType'])&&trim($_POST['lookType'])?trim($_POST['lookType']):'buildings';
        $lookId = $this->request->param('lookId',0,'intval');
        $operation = 'detail';
        $paramData = [
            'village_id' => $village_id,
            'thirdType' => $thirdType,
            'businessType' => 'village',
            'operation' => $operation,
            'lookId' => $lookId,
            'childrenBusinessType' => $childrenBusinessType,
        ];
        $thirdAccessService = new ThirdAccessService();
        try {
            $info = $thirdAccessService->setThirdAccessData($paramData);
        } catch (\Exception $e) {
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0,$info);
    }
    // 删除信息
    public function deleteAssetsDetail() {
        $village_id = $this->request->param('village_id','','intval');
        $thirdErrCode = $this->request->thirdErrCode;
        $thirdErrMsg = $this->request->thirdErrMsg;
        $thirdType = $this->request->thirdType;
        if ($thirdErrMsg&&$thirdErrCode) {
            return api_output_error($thirdErrCode, $thirdErrMsg);
        }
        $childrenBusinessType = isset($_POST['delType'])&&trim($_POST['delType'])?trim($_POST['delType']):'buildings';
        $delId = $this->request->param('deleteId',0,'intval');
        $operation = 'delete';
        $paramData = [
            'village_id' => $village_id,
            'thirdType' => $thirdType,
            'businessType' => 'village',
            'operation' => $operation,
            'deleteId' => $delId,
            'childrenBusinessType' => $childrenBusinessType,
        ];
        $thirdAccessService = new ThirdAccessService();
        try {
            $info = $thirdAccessService->setThirdAccessData($paramData);
        } catch (\Exception $e) {
            return api_output_error(1001,$e->getMessage());
        }
        return api_output(0,$info);
    }
}