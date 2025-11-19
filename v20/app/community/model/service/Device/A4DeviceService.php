<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      kumike（酷米客-杂淘）-A4(咚咚)人脸设备处理
 */

namespace app\community\model\service\Device;

use app\traits\device\A4DeviceTraits;

class A4DeviceService
{
    use A4DeviceTraits;

    /**
     * 老版调用新版本A4
     */
    public function a4ArrPersonMsgBox($toNewA4, $orderGroupId = '') {
        $queueData = [
            'toNewA4' => $toNewA4, 'orderGroupId' => $orderGroupId, 'jobFunc' => 'a4ArrPersonMsgHandleBox',
        ];
        $job_id = $this->traitCommonA4Device($queueData);
        fdump_api(['msg' => '老版调用新版本A4队列', 'parameter' => func_get_args(), 'job_id' => $job_id], 'A4DeviceService/a4ArrPersonMsgBoxLog', 1);
        return $job_id;
    }

    /**
     * 处理A4同步队列.
     */
    public function a4ArrPersonMsgHandleBox($params) {
        $toNewA4 = isset($params['toNewA4']) ? $params['toNewA4'] : [];
        $orderGroupId = isset($params['orderGroupId']) ? $params['orderGroupId'] : '';
        $toJobArr = [];
        foreach ($toNewA4 as $item) {
            $device_id = isset($item['device_id']) ? intval($item['device_id']) : 0;
            $bind_arr_id = isset($item['bind_arr_id']) ? $item['bind_arr_id'] : [];
            foreach ($bind_arr_id as $pigcms_id) {
                $boxParam = [
                    'jobFunc' => 'addA4MemberHouseholdBox', 'bind_id' => $pigcms_id,
                    'communityId' => 0, 'roomId' => 0, 'device_id' => $device_id,
                ];
                if ($orderGroupId) {
                    $boxParam['orderGroupId'] = $orderGroupId;
                }
                $job_id = $this->traitCommonA4Device($boxParam);
                $toJobArr[] = [
                    'job_id' => $job_id, 'boxParam' => $boxParam
                ];
            }
        }
        fdump_api(['msg' => '处理A4同步队列', 'parameter' => func_get_args(), 'toJobArr' => $toJobArr], 'A4DeviceService/a4ArrPersonMsgHandleBoxLog', 1);
        return $toJobArr;
    }
    
    public function addA4MemberHouseholdJobBox($params)
    {
        $bind_id = $params['bind_id'];
        $device_id = $params['device_id'] ? $params['device_id'] : 0;
        $roomId = $params['roomId'] ? $params['roomId'] : 0;
        $communityId = $params['communityId'] ? $params['communityId'] : 0;
        $orderGroupId = isset($params['orderGroupId']) ? $params['orderGroupId'] : '';
        $boxParam = [
            'jobFunc' => 'addA4MemberHouseholdBox', 'bind_id' => $bind_id,
            'communityId' => $communityId, 'roomId' => $roomId, 'device_id' => $device_id,
        ];
        if ($orderGroupId) {
            $boxParam['orderGroupId'] = $orderGroupId;
        }
        $job_id = $this->traitCommonA4Device($boxParam);
        fdump_api(['msg' => '同步人脸信息队列', 'parameter' => func_get_args(), 'boxParam' => $boxParam, 'job_id' => $job_id], 'A4DeviceService/addA4MemberHouseholdJobBoxLog', 1);
        return $job_id;
    }

    /**
     * 新版调取老版方法
     */
    public function addA4MemberHouseholdBox($params)
    {
        $bind_id = $params['bind_id'];
        $device_id = $params['device_id'] ? $params['device_id'] : 0;
        $roomId = $params['roomId'] ? $params['roomId'] : 0;
        $communityId = $params['communityId'] ? $params['communityId'] : 0;
        $is_plan = isset($params['is_plan']) ? $params['is_plan'] : 3;
        $invokeParam = [
            'bind_id' => $bind_id, 'member' => [], 'roomId' => $roomId,
            'communityId' => $communityId, 'device_id' => $device_id, 'is_plan' => $is_plan
        ];
        $result = invoke_cms_model('Face_door_a4_service/addA4MemberHousehold', $invokeParam);
        fdump_api(['msg' => '单个同步用户信息-含有成员信息，直接同步', 'parameter' => func_get_args(), 'invokeParam' => $invokeParam, 'result' => $result], 'A4DeviceService/addA4MemberHouseholdBoxLog', 1);
        return $result;
    }
    
    public function addA4DeviceJobBox($params)
    {
        $village_id = $params['village_id'];
        $device_id = $params['device_id'] ? $params['device_id'] : 0;
        $communityId = $params['communityId'] ? $params['communityId'] : 0;
        $zoneId = $params['zoneId'] ? $params['zoneId'] : 0;
        $unitId = $params['unitId'] ? $params['unitId'] : 0;
        $orderGroupId = isset($params['orderGroupId']) ? $params['orderGroupId'] : '';
        $boxParam = [
            'jobFunc' => 'addA4Device', 'village_id' => $village_id, 'device_id' => $device_id,
            'communityId' => $communityId, 'zoneId' => $zoneId, 'unitId' => $unitId,
        ];
        if ($orderGroupId) {
            $boxParam['orderGroupId'] = $orderGroupId;
        }
        $job_id = $this->traitCommonA4Device($boxParam);
        fdump_api(['msg' => '同步设备队列', 'parameter' => func_get_args(), 'boxParam' => $boxParam, 'job_id' => $job_id], 'A4DeviceService/addA4DeviceJobBoxLog', 1);
        return $job_id;
    }
    
    public function addA4Device($params)
    {
        $village_id = $params['village_id'];
        $device_id = $params['device_id'] ? $params['device_id'] : 0;
        $communityId = $params['communityId'] ? $params['communityId'] : 0;
        $zoneId = $params['zoneId'] ? $params['zoneId'] : 0;
        $unitId = $params['unitId'] ? $params['unitId'] : 0;
        $orderGroupId = isset($params['orderGroupId']) ? $params['orderGroupId'] : '';
        $invokeParam = [
            'device_id' => $device_id, 'zoneId' => $zoneId,  'unitId' => $unitId, 'face_device_data' => []
        ];
        $result = invoke_cms_model('Face_door_a4_service/addA4Device', $invokeParam);
        fdump_api(['msg' => '添加设备', 'parameter' => func_get_args(), 'invokeParam' => $invokeParam, 'result' => $result], 'A4DeviceService/addA4DeviceLog', 1);
        return $result;
    }
    
    public function addA4CommunityJobBox($params)
    {
        $village_id = $params['village_id'];
        $device_id = $params['device_id'] ? $params['device_id'] : 0;
        $orderGroupId = isset($params['orderGroupId']) ? $params['orderGroupId'] : '';
        $boxParam = [
            'jobFunc' => 'addA4Community', 'village_id' => $village_id, 'device_id' => $device_id,
        ];
        if ($orderGroupId) {
            $boxParam['orderGroupId'] = $orderGroupId;
        }
        $job_id = $this->traitCommonA4Device($boxParam);
        fdump_api(['msg' => '同步社区队列', 'parameter' => func_get_args(), 'boxParam' => $boxParam, 'job_id' => $job_id], 'A4DeviceService/addA4CommunityJobBoxLog', 1);
        return $job_id;
    }
    
    public function addA4Community($params)
    {
        $village_id = $params['village_id'];
        $device_id = isset($params['device_id']) ? $params['device_id'] : 0;
        $is_plan = isset($params['is_plan']) ? $params['is_plan'] : 3;
        $invokeParam = [
            'village_id' => $village_id, 'village_info' => [],  'device_id' => $device_id, 'is_plan' => $is_plan
        ];
        $result = invoke_cms_model('Face_door_a4_service/addA4Community', $invokeParam);
        fdump_api(['msg' => '添加社区', 'parameter' => func_get_args(), 'invokeParam' => $invokeParam, 'result' => $result], 'A4DeviceService/addA4CommunityLog', 1);
        return $result;
    }
    
    public function addA4ZoneJobBox($params)
    {
        $village_id = $params['village_id'];
        $communityId = $params['communityId'] ? $params['communityId'] : 0;
        $device_id = $params['device_id'] ? $params['device_id'] : 0;
        $orderGroupId = isset($params['orderGroupId']) ? $params['orderGroupId'] : '';
        $boxParam = [
            'jobFunc' => 'addA4Zone', 'village_id' => $village_id, 'communityId' => $communityId, 'device_id' => $device_id,
        ];
        if ($orderGroupId) {
            $boxParam['orderGroupId'] = $orderGroupId;
        }
        $job_id = $this->traitCommonA4Device($boxParam);
        fdump_api(['msg' => '同步分区队列', 'parameter' => func_get_args(), 'boxParam' => $boxParam, 'job_id' => $job_id], 'A4DeviceService/addA4ZoneJobBoxLog', 1);
        return $job_id;
    }
    
    public function addA4Zone($params)
    {
        $village_id = $params['village_id'];
        $device_id = $params['device_id'] ? $params['device_id'] : 0;
        $communityId = $params['communityId'] ? $params['communityId'] : 0;
        $is_plan = isset($params['is_plan']) ? $params['is_plan'] : 3;
        $invokeParam = [
            'village_id' => $village_id, 'communityId' => $communityId,  'village_info' => [],
            'device_id' => $device_id, 'is_plan' => $is_plan
        ];
        $result = invoke_cms_model('Face_door_a4_service/addA4Zone', $invokeParam);
        fdump_api(['msg' => '添加分区', 'parameter' => func_get_args(), 'invokeParam' => $invokeParam, 'result' => $result], 'A4DeviceService/addA4ZoneLog', 1);
        return $result;
    }
    
    public function addVillageA4SingleJobBox($params)
    {
        $village_id = $params['village_id'];
        $device_id = $params['device_id'] ? $params['device_id'] : 0;
        $communityId = $params['communityId'] ? $params['communityId'] : 0;
        $zoneId = $params['zoneId'] ? $params['zoneId'] : 0;
        $orderGroupId = isset($params['orderGroupId']) ? $params['orderGroupId'] : '';
        $boxParam = [
            'jobFunc' => 'addVillageA4Single', 'village_id' => $village_id, 'device_id' => $device_id,
            'communityId' => $communityId, 'zoneId' => $zoneId,
        ];
        if ($orderGroupId) {
            $boxParam['orderGroupId'] = $orderGroupId;
        }
        $job_id = $this->traitCommonA4Device($boxParam);
        fdump_api(['msg' => '同步小区所有楼栋队列', 'parameter' => func_get_args(), 'boxParam' => $boxParam, 'job_id' => $job_id], 'A4DeviceService/addVillageA4SingleJobBoxLog', 1);
        return $job_id;
    }
    
    public function addVillageA4Single($params)
    {
        $village_id = $params['village_id'];
        $device_id = $params['device_id'] ? $params['device_id'] : 0;
        $communityId = $params['communityId'] ? $params['communityId'] : 0;
        $zoneId = $params['zoneId'] ? $params['zoneId'] : 0;
        $orderGroupId = isset($params['orderGroupId']) ? $params['orderGroupId'] : '';
        $is_plan = isset($params['is_plan']) ? $params['is_plan'] : 3;
        $invokeParam = [
            'village_id' => $village_id, 'zoneId' => $zoneId,  'communityId' => $communityId, 
            'device_id' => $device_id, 'is_plan' => $is_plan
        ];
        $result = invoke_cms_model('Face_door_a4_service/addVillageA4Single', $invokeParam);
        fdump_api(['msg' => '同步小区所有楼栋', 'parameter' => func_get_args(), 'invokeParam' => $invokeParam, 'result' => $result], 'A4DeviceService/addVillageA4SingleLog', 1);
        return $result;
    }
    
    public function addA4BuildingJobBox($params)
    {
        $single_id = $params['single_id'];
        $communityId = $params['communityId'] ? $params['communityId'] : 0;
        $zoneId = $params['zoneId'] ? $params['zoneId'] : 0;
        $device_id = $params['device_id'] ? $params['device_id'] : 0;
        $orderGroupId = isset($params['orderGroupId']) ? $params['orderGroupId'] : '';
        $boxParam = [
            'jobFunc' => 'addA4Building', 'single_id' => $single_id, 'communityId' => $communityId, 'zoneId' => $zoneId, 'device_id' => $device_id,
        ];
        if ($orderGroupId) {
            $boxParam['orderGroupId'] = $orderGroupId;
        }
        $job_id = $this->traitCommonA4Device($boxParam);
        fdump_api(['msg' => '同步小区楼栋队列', 'parameter' => func_get_args(), 'boxParam' => $boxParam, 'job_id' => $job_id], 'A4DeviceService/addA4BuildingJobBoxLog', 1);
        return $job_id;
    }
    
    public function addA4Building($params)
    {
        $single_id = $params['single_id'];
        $communityId = $params['communityId'] ? $params['communityId'] : 0;
        $zoneId = $params['zoneId'] ? $params['zoneId'] : 0;
        $device_id = $params['device_id'] ? $params['device_id'] : 0;
        $is_plan = isset($params['is_plan']) ? $params['is_plan'] : 3;
        $invokeParam = [
            'single_id' => $single_id, 'zoneId' => $zoneId, 'communityId' => $communityId,
            'single_info' => [], 'device_id' => $device_id, 'is_plan' => $is_plan
        ];
        $result = invoke_cms_model('Face_door_a4_service/addA4Building', $invokeParam);
        fdump_api(['msg' => '同步小区楼栋', 'parameter' => func_get_args(), 'invokeParam' => $invokeParam, 'result' => $result], 'A4DeviceService/addA4BuildingLog', 1);
        return $result;
    }
    
    public function addAllA4PropertyJobBox($params)
    {
        $village_id = $params['village_id'];
        $communityId = $params['communityId'] ? $params['communityId'] : 0;
        $zoneId = $params['zoneId'] ? $params['zoneId'] : 0;
        $device_id = $params['device_id'] ? $params['device_id'] : 0;
        $orderGroupId = isset($params['orderGroupId']) ? $params['orderGroupId'] : '';
        $boxParam = [
            'jobFunc' => 'addAllA4Property', 'village_id' => $village_id, 'communityId' => $communityId, 
            'zoneId' => $zoneId, 'device_id' => $device_id,
        ];
        if ($orderGroupId) {
            $boxParam['orderGroupId'] = $orderGroupId;
        }
        $job_id = $this->traitCommonA4Device($boxParam);
        fdump_api(['msg' => '同步小区所有工作人员队列', 'parameter' => func_get_args(), 'boxParam' => $boxParam, 'job_id' => $job_id], 'A4DeviceService/addAllA4PropertyJobBoxLog', 1);
        return $job_id;
    }
    
    public function addAllA4Property($params)
    {
        $village_id = $params['village_id'];
        $communityId = $params['communityId'] ? $params['communityId'] : 0;
        $zoneId = $params['zoneId'] ? $params['zoneId'] : 0;
        $device_id = $params['device_id'] ? $params['device_id'] : 0;
        $is_plan = isset($params['is_plan']) ? $params['is_plan'] : 3;
        $invokeParam = [
            'village_id' => $village_id, 'zoneId' => $zoneId,  'communityId' => $communityId, 'is_plan' => $is_plan
        ];
        $result = invoke_cms_model('Face_door_a4_service/addAllA4Property', $invokeParam);
        fdump_api(['msg' => '同步小区所有工作人员', 'parameter' => func_get_args(), 'invokeParam' => $invokeParam, 'result' => $result], 'A4DeviceService/addAllA4PropertyLog', 1);
        return $result;
    }
    
    public function addA4PropertyJobBox($params)
    {
        $bind_id = $params['bind_id'];
        $village_id = $params['village_id'];
        $communityId = $params['communityId'] ? $params['communityId'] : 0;
        $zoneId = $params['zoneId'] ? $params['zoneId'] : 0;
        $device_id = $params['device_id'] ? $params['device_id'] : 0;
        $orderGroupId = isset($params['orderGroupId']) ? $params['orderGroupId'] : '';
        $boxParam = [
            'jobFunc' => 'addA4Property', 'bind_id' => $bind_id, 'village_id' => $village_id,
            'communityId' => $communityId, 'zoneId' => $zoneId, 'device_id' => $device_id,
        ];
        if ($orderGroupId) {
            $boxParam['orderGroupId'] = $orderGroupId;
        }
        $job_id = $this->traitCommonA4Device($boxParam);
        fdump_api(['msg' => '同步小区工作人员队列', 'parameter' => func_get_args(), 'boxParam' => $boxParam, 'job_id' => $job_id], 'A4DeviceService/addA4PropertyJobBoxLog', 1);
        return $job_id;
    }
    
    public function addA4Property($params)
    {
        $bind_id = $params['bind_id'];
        $village_id = $params['village_id'];
        $communityId = $params['communityId'] ? $params['communityId'] : 0;
        $zoneId = $params['zoneId'] ? $params['zoneId'] : 0;
        $device_id = $params['device_id'] ? $params['device_id'] : 0;
        $is_plan = isset($params['is_plan']) ? $params['is_plan'] : 3;
        $invokeParam = [
            'bind_id' => $bind_id, 'zoneId' => $zoneId, 'communityId' => $communityId, 'bind_info' => [], 'is_plan' => $is_plan
        ];
        $result = invoke_cms_model('Face_door_a4_service/addA4Property', $invokeParam);
        fdump_api(['msg' => '同步小区工作人员', 'parameter' => func_get_args(), 'invokeParam' => $invokeParam, 'result' => $result], 'A4DeviceService/addA4PropertyLog', 1);
        return $result;
    }
    
    public function addSingleA4FloorJobBox($params)
    {
        $single_id = $params['single_id'];
        $device_id = $params['device_id'] ? $params['device_id'] : 0;
        $communityId = $params['communityId'] ? $params['communityId'] : 0;
        $buildingId = $params['buildingId'] ? $params['buildingId'] : 0;
        $orderGroupId = isset($params['orderGroupId']) ? $params['orderGroupId'] : '';
        $boxParam = [
            'jobFunc' => 'addSingleA4Floor', 'single_id' => $single_id, 'device_id' => $device_id,
            'communityId' => $communityId, 'buildingId' => $buildingId,
        ];
        if ($orderGroupId) {
            $boxParam['orderGroupId'] = $orderGroupId;
        }
        $job_id = $this->traitCommonA4Device($boxParam);
        fdump_api(['msg' => '同步楼栋下所有单元队列', 'parameter' => func_get_args(), 'boxParam' => $boxParam, 'job_id' => $job_id], 'A4DeviceService/addSingleA4FloorJobBoxLog', 1);
        return $job_id;
    }
    
    public function addSingleA4Floor($params)
    {
        $single_id = $params['single_id'];
        $device_id = $params['device_id'] ? $params['device_id'] : 0;
        $buildingId = $params['buildingId'] ? $params['buildingId'] : 0;
        $communityId = $params['communityId'] ? $params['communityId'] : 0;
        $is_plan = isset($params['is_plan']) ? $params['is_plan'] : 3;
        $invokeParam = [
            'single_id' => $single_id, 'buildingId' => $buildingId,  'communityId' => $communityId,
            'device_id' => $device_id, 'is_plan' => $is_plan
        ];
        $result = invoke_cms_model('Face_door_a4_service/addSingleA4Floor', $invokeParam);
        fdump_api(['msg' => '同步楼栋下所有单元', 'parameter' => func_get_args(), 'invokeParam' => $invokeParam, 'result' => $result], 'A4DeviceService/addSingleA4FloorLog', 1);
        return $result;
    }
    
    public function addA4UnitJobBox($params)
    {
        $floor_id = $params['floor_id'];
        $device_id = $params['device_id'] ? $params['device_id'] : 0;
        $buildingId = $params['buildingId'] ? $params['buildingId'] : 0;
        $communityId = $params['communityId'] ? $params['communityId'] : 0;
        $orderGroupId = isset($params['orderGroupId']) ? $params['orderGroupId'] : '';
        $boxParam = [
            'jobFunc' => 'addA4Unit', 'floor_id' => $floor_id, 'communityId' => $communityId, 
            'buildingId' => $buildingId, 'device_id' => $device_id,
        ];
        if ($orderGroupId) {
            $boxParam['orderGroupId'] = $orderGroupId;
        }
        $job_id = $this->traitCommonA4Device($boxParam);
        fdump_api(['msg' => '同步单元队列', 'parameter' => func_get_args(), 'boxParam' => $boxParam, 'job_id' => $job_id], 'A4DeviceService/addA4UnitJobBoxLog', 1);
        return $job_id;
    }
    
    public function addA4Unit($params)
    {
        $floor_id = $params['floor_id'];
        $device_id = $params['device_id'] ? $params['device_id'] : 0;
        $buildingId = $params['buildingId'] ? $params['buildingId'] : 0;
        $communityId = $params['communityId'] ? $params['communityId'] : 0;
        $is_plan = isset($params['is_plan']) ? $params['is_plan'] : 3;
        $invokeParam = [
            'floor_id' => $floor_id, 'buildingId' => $buildingId,  'communityId' => $communityId,
            'floor_info' => [], 'device_id' => $device_id, 'is_plan' => $is_plan
        ];
        $result = invoke_cms_model('Face_door_a4_service/addA4Unit', $invokeParam);
        fdump_api(['msg' => '同步单元', 'parameter' => func_get_args(), 'invokeParam' => $invokeParam, 'result' => $result], 'A4DeviceService/addA4UnitLog', 1);
        return $result;
    }
    
    public function addUnitA4RoomJobBox($params)
    {
        $floor_id = $params['floor_id'];
        $device_id = $params['device_id'] ? $params['device_id'] : 0;
        $communityId = $params['communityId'] ? $params['communityId'] : 0;
        $unitId = $params['unitId'] ? $params['unitId'] : 0;
        $orderGroupId = isset($params['orderGroupId']) ? $params['orderGroupId'] : '';
        $boxParam = [
            'jobFunc' => 'addUnitA4Room', 'floor_id' => $floor_id, 'device_id' => $device_id,
            'communityId' => $communityId, 'unitId' => $unitId,
        ];
        if ($orderGroupId) {
            $boxParam['orderGroupId'] = $orderGroupId;
        }
        $job_id = $this->traitCommonA4Device($boxParam);
        fdump_api(['msg' => '同步单元所有房屋队列', 'parameter' => func_get_args(), 'boxParam' => $boxParam, 'job_id' => $job_id], 'A4DeviceService/addUnitA4RoomJobBoxLog', 1);
        return $job_id;
    }
    
    public function addUnitA4Room($params)
    {
        $floor_id = $params['floor_id'];
        $device_id = $params['device_id'] ? $params['device_id'] : 0;
        $communityId = $params['communityId'] ? $params['communityId'] : 0;
        $unitId = $params['unitId'] ? $params['unitId'] : 0;
        $is_plan = isset($params['is_plan']) ? $params['is_plan'] : 3;
        $invokeParam = [
            'floor_id' => $floor_id, 'communityId' => $communityId,  'unitId' => $unitId,
            'floor_info' => [], 'device_id' => $device_id, 'is_plan' => $is_plan
        ];
        $result = invoke_cms_model('Face_door_a4_service/addUnitA4Room', $invokeParam);
        fdump_api(['msg' => '同步单元所有房屋', 'parameter' => func_get_args(), 'invokeParam' => $invokeParam, 'result' => $result], 'A4DeviceService/addUnitA4RoomLog', 1);
        return $result;
    }

    public function addA4RoomJobBox($params)
    {
        $vacancy_id = $params['bind_id'];
        $communityId = $params['communityId'] ? $params['communityId'] : 0;
        $device_id = $params['device_id'] ? $params['device_id'] : 0;
        $orderGroupId = isset($params['orderGroupId']) ? $params['orderGroupId'] : '';
        $boxParam = [
            'jobFunc' => 'addA4Room', 'bind_id' => $vacancy_id, 'communityId' => $communityId, 'device_id' => $device_id,
        ];
        if ($orderGroupId) {
            $boxParam['orderGroupId'] = $orderGroupId;
        }
        $job_id = $this->traitCommonA4Device($boxParam);
        fdump_api(['msg' => '同步房屋队列', 'parameter' => func_get_args(), 'boxParam' => $boxParam, 'job_id' => $job_id], 'A4DeviceService/addA4RoomJobBoxLog', 1);
        return $job_id;
    }

    public function addA4Room($params)
    {
        $bind_id = $params['bind_id'];
        $communityId = $params['communityId'] ? $params['communityId'] : 0;
        $unitId = $params['unitId'] ? $params['unitId'] : 0;
        $device_id = isset($params['device_id']) ? $params['device_id'] : 0;
        $is_plan = isset($params['is_plan']) ? $params['is_plan'] : 3;
        $invokeParam = [
            'bind_id' => $bind_id, 'communityId' => $communityId,  'unitId' => $unitId,
            'vacancy_info' => [], 'device_id' => $device_id, 'is_plan' => $is_plan
        ];
        $result = invoke_cms_model('Face_door_a4_service/addA4Room', $invokeParam);
        fdump_api(['msg' => '同步房屋', 'parameter' => func_get_args(), 'invokeParam' => $invokeParam, 'result' => $result], 'A4DeviceService/addA4RoomLog', 1);
        return $result;
    }
    
    public function addA4RoomHoldsJobBox($params)
    {
        $vacancy_id = $params['vacancy_id'];
        $communityId = $params['communityId'] ? $params['communityId'] : 0;
        $roomId = $params['roomId'] ? $params['roomId'] : 0;
        $device_id = $params['device_id'] ? $params['device_id'] : 0;
        $orderGroupId = isset($params['orderGroupId']) ? $params['orderGroupId'] : '';
        $boxParam = [
            'jobFunc' => 'addA4RoomHolds', 'vacancy_id' => $vacancy_id, 'communityId' => $communityId, 
            'roomId' => $roomId, 'device_id' => $device_id,
        ];
        if ($orderGroupId) {
            $boxParam['orderGroupId'] = $orderGroupId;
        }
        $job_id = $this->traitCommonA4Device($boxParam);
        fdump_api(['msg' => '同步房屋人员队列', 'parameter' => func_get_args(), 'boxParam' => $boxParam, 'job_id' => $job_id], 'A4DeviceService/addA4RoomHoldsJobBoxLog', 1);
        return $job_id;
    }
    
    public function addA4RoomHolds($params)
    {
        $vacancy_id = $params['vacancy_id'];
        $communityId = $params['communityId'] ? $params['communityId'] : 0;
        $roomId = $params['roomId'] ? $params['roomId'] : 0;
        $device_id = $params['device_id'] ? $params['device_id'] : 0;
        $is_plan = isset($params['is_plan']) ? $params['is_plan'] : 3;
        $invokeParam = [
            'vacancy_id' => $vacancy_id, 'communityId' => $communityId, 'roomId' => $roomId, 'device_id' => $device_id, 'is_plan' => $is_plan
        ];
        $result = invoke_cms_model('Face_door_a4_service/addA4RoomHolds', $invokeParam);
        fdump_api(['msg' => '同步房屋人员', 'parameter' => func_get_args(), 'invokeParam' => $invokeParam, 'result' => $result], 'A4DeviceService/addA4RoomHoldsLog', 1);
        return $result;
    }
    
    public function notifyA4ImgUpJobBox($params)
    {
        $bind_id = $params['bind_id'];
        $communityId = $params['communityId'] ? $params['communityId'] : 0;
        $orderGroupId = isset($params['orderGroupId']) ? $params['orderGroupId'] : '';
        $boxParam = [
            'jobFunc' => 'notifyA4ImgUp', 'bind_id' =>$bind_id, 'communityId' =>$communityId, 'userId' => 0,
        ];
        if ($orderGroupId) {
            $boxParam['orderGroupId'] = $orderGroupId;
        }
        $job_id = $this->traitCommonA4Device($boxParam);
        fdump_api(['msg' => '同步人员人脸队列', 'parameter' => func_get_args(), 'boxParam' => $boxParam, 'job_id' => $job_id], 'A4DeviceService/notifyA4ImgUpJobBoxLog', 1);
        return $job_id;
    }
    
    public function notifyA4ImgUp($params)
    {
        $bind_id = $params['bind_id'];
        $communityId = $params['communityId'] ? $params['communityId'] : 0;
        $userId = $params['userId'] ? $params['userId'] : 0;
        $is_plan = isset($params['is_plan']) ? $params['is_plan'] : 3;
        $invokeParam = [
            'bind_id' => $bind_id, 'userId' => $userId, 'communityId' => $communityId, 'bind_info' => []
        ];
        $result = invoke_cms_model('Face_door_a4_service/notifyA4ImgUp', $invokeParam);
        fdump_api(['msg' => '同步人员人脸', 'parameter' => func_get_args(), 'invokeParam' => $invokeParam, 'result' => $result], 'A4DeviceService/notifyA4ImgUpLog', 1);
        return $result;
    }
}