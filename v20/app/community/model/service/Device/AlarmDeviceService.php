<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      智能报警设备
 */

namespace app\community\model\service\Device;

use app\community\model\db\Device\AlarmNewRepair;
use app\community\model\db\Device\AlarmToWorksOrder;
use app\community\model\db\Device\CommunityEventAlarm;
use app\community\model\db\Device\HouseDevice;
use app\community\model\db\HouseVillageFloor;
use app\community\model\db\HouseVillageSingle;
use app\community\model\service\RepairCateService;
use app\consts\EventAlarmConst;
use app\traits\house\HouseTraits;

class AlarmDeviceService
{
    use HouseTraits;
    /**
     * 获取设备信息.
     */
    public function getAlarmDevice($where, $field = true, $order = []) {
        return (new HouseDevice())->getOne($where, $field, $order);
    }

    /**
     * 更新设备信息.
     */
    public function updateAlarmDevice($where, $data) {
        return (new HouseDevice())->updateThis($where, $data);
    }

    /**
     * 添加设备信息.
     */
    public function addAlarmDevice($data) {
        return (new HouseDevice())->add($data);
    }

    /**
     * 获取设备列表.
     */
    public function getAlarmDeviceList($where = [], $field = true, $order = true, $page = 1, $listRows = 15) {
        return (new HouseDevice())->getList($where, $field, $order, $page, $listRows);
    }
    
    /**
     * 获取设备数.
     */
    public function getAlarmDeviceCount($where = []) {
        return (new HouseDevice())->getCount($where);
    }

    /**
     * 获取设备状态数组.
     */
    public function getAlarmDeviceStatusArr() {
        // 0-未知  1-正常 2-停止/待机
        return [
            0 => ['label' => '未知', 'value' => 0, 'color' => 'gray'],
            1 => ['label' => '正常', 'value' => 1, 'color' => 'green'],
            2 => ['label' => '离线', 'value' => 2, 'color' => 'red'],
        ];
    }
    
    /**
     * 处理设备同步状态.
     */
    public function handleAlarmDeviceCloudStatus($cloud_status, $cloud_reason) {
        $cloud_arr = ['label' => '未同步', 'value' => 0, 'color' => 'gray'];
        // 同步状态 0-未知  1-同步中 2-同步成功 3-同步失败 
        switch ($cloud_status) {
            case 0:
                $cloud_arr = ['label' => '未同步', 'value' => 0, 'color' => 'gray'];
                break;
            case 1:
                $cloud_arr = ['label' => '同步中', 'value' => 1, 'color' => '#2681f3'];
                break;
            case 2:
                $cloud_arr = ['label' => '同步成功', 'value' => 2, 'color' => 'green'];
                break;
            case 3:
                $cloud_reason && $cloud_text = '同步失败'."($cloud_reason)";
                !$cloud_reason && $cloud_text = '同步失败';
                $cloud_arr = ['label' => $cloud_text, 'value' => 3, 'color' => 'red'];
                break;
        }
        return $cloud_arr;
    }

    /**
     * 获取设备告警工单配置.
     */
    public function getAlarmNewRepair($param) {
        $village_id = isset($param['village_id']) ? intval($param['village_id']) : 0;
        $device_type = isset($param['device_type']) ? trim($param['device_type']) : 'alarm';
        $dbAlarmNewRepair = new  AlarmNewRepair();
        $whereSet = [];
        $whereSet[] = ['village_id', '=', $village_id];
        $whereSet[] = ['device_type', '=', $device_type];
        $whereSet[] = ['business_type', '=', 'house_device'];
        $setInfo = $dbAlarmNewRepair->getOne($whereSet);
        if ($setInfo && !is_array($setInfo)) {
            $setInfo = $setInfo->toArray();
        }
        if (empty($setInfo)) {
            $setInfo = $this->setAlarmNewRepair($param);
        }
        return $setInfo;
    }

    /**
     * 更新设备告警工单配置.
     */
    public function setAlarmNewRepair($param) {
        $village_id = isset($param['village_id']) ? intval($param['village_id']) : 0;
        $repair_cate_id = isset($param['repair_cate_id']) ? intval($param['repair_cate_id']) : 0;
        $repair_cate_sub_id = isset($param['repair_cate_sub_id']) ? intval($param['repair_cate_sub_id']) : 0;
        $device_type = isset($param['device_type']) ? trim($param['device_type']) : 'alarm';
        $switch_on = isset($param['switch_on']) ? intval($param['switch_on']) : 0;
        $business_type = 'house_device';
        $dbAlarmNewRepair = new  AlarmNewRepair();
        $whereSet = [];
        $whereSet[] = ['village_id', '=', $village_id];
        $whereSet[] = ['device_type', '=', $device_type];
        $whereSet[] = ['business_type', '=', $business_type];
        $setInfo = $dbAlarmNewRepair->getOne($whereSet, 'id');
        $id = isset($setInfo['id']) ? intval($setInfo['id']) : 0;
        $data = [
            'village_id' => $village_id,
            'device_type' => $device_type,
            'business_type' => $business_type,
            'repair_cate_id' => $repair_cate_id,
            'repair_cate_sub_id' => $repair_cate_sub_id,
            'switch_on' => $switch_on,
        ];
        $nowTime = time();
        if ($id > 0) {
            $data['switch_time'] = $nowTime;
            $dbAlarmNewRepair->updateThis(['id' => $id], $data);
            $data['id'] = $id;
        } else {
            $data['add_time'] = $nowTime;
            $data['switch_time'] = $nowTime;
            $id = $dbAlarmNewRepair->add($data);
            $data['id'] = $id;
        }
        return $data;
    }
    
    public function alarmToWorkOrder($param) {
        $village_id = isset($param['village_id']) ? intval($param['village_id']) : 0;
        $device_type = isset($param['device_type']) ? trim($param['device_type']) : 'alarm';
        $business_type = 'house_device';
        $dbAlarmNewRepair = new  AlarmNewRepair();
        $whereSet = [];
        if ($village_id > 0) {
            $whereSet[] = ['village_id', '=', $village_id];
        }
        $whereSet[] = ['device_type', '=', $device_type];
        $whereSet[] = ['business_type', '=', $business_type];
        $whereSet[] = ['switch_on', '=', 1];
        $whereSet[] = ['repair_cate_id', '>', 0];
        $whereSet[] = ['repair_cate_sub_id', '>', 0];
        $bindRepair = $dbAlarmNewRepair->getList($whereSet, true, 'switch_time ASC');
        if ($bindRepair && !is_array($bindRepair)) {
            $bindRepair = $bindRepair->toArray();
        }
        if (empty($bindRepair)) {
            // 没有开启的配置 跳过
            return [];
        }
        $villageIds = array_unique(array_column($bindRepair, 'village_id'));
        $db_house_device = new HouseDevice();
        $whereDevice = [];
        $whereDevice[] = ['device_type', '=', $device_type];
        $whereDevice[] = ['village_id', 'in', $villageIds];
        $whereDevice[] = ['is_del', '=', 0];
        $whereDevice[] = ['cloud_device_id', '<>', ''];
        $deviceField = 'device_id, device_name, device_type, device_serial, village_id, single_id, floor_id, cloud_device_id';
        $deviceList = $db_house_device->getList($whereDevice, $deviceField);
        if ($deviceList && !is_array($deviceList)) {
            $deviceList = $deviceList->toArray();
        }
        if (empty($deviceList)) {
            // 没有符合条件的设备 跳过
            return [];
        }
        $db_house_village_single = new HouseVillageSingle();
        $db_house_village_floor = new HouseVillageFloor();
        $singleIds = array_unique(array_column($deviceList, 'single_id'));
        $floorIds = array_unique(array_column($deviceList, 'floor_id'));

        $singleArr = [];
        if (!empty($singleIds)) {
            $whereSingle = [];
            $whereSingle[] = ['id', 'in', $singleIds];
            $whereSingle[] = ['status', '=', 1];
            $singleField = 'id, single_name';
            $singleList = $db_house_village_single->getList($whereSingle, $singleField);
            if ($singleList && !is_array($singleList)) {
                $singleList = $singleList->toArray();
            }
            foreach ($singleList as $single) {
                $id = $single['id'];
                $singleArr[$id] = $single;
            }
        }
        $floorArr = [];
        if (!empty($floorIds)) {
            $whereFloor = [];
            $whereFloor[] = ['floor_id', 'in', $floorIds];
            $whereFloor[] = ['status', '=', 1];
            $floorField = 'floor_id, floor_name';
            $floorList = $db_house_village_floor->getList($whereFloor, $floorField);
            if ($floorList && !is_array($floorList)) {
                $floorList = $floorList->toArray();
            }
            foreach ($floorList as $floor) {
                $floor_id = $floor['floor_id'];
                $floorArr[$floor_id] = $floor;
            }
        }
        $deviceArr = [];
        foreach ($deviceList as $device) {
            $cloud_device_id = $device['cloud_device_id'];
            $single_id = isset($device['single_id']) ? intval($device['single_id']) : 0;
            $floor_id = isset($device['floor_id']) ? intval($device['floor_id']) : 0;
            $single_name = isset($singleArr[$single_id]) && $singleArr[$single_id]['single_name'] ? trim($singleArr[$single_id]['single_name']) : '';
            $floor_name = isset($floorArr[$floor_id]) && $floorArr[$floor_id]['floor_name'] ? trim($floorArr[$floor_id]['floor_name']) : '';
            $single_name && $device['single_name'] = $single_name;
            $floor_name && $device['floor_name'] = $floor_name;
            $deviceArr[$cloud_device_id] = $device;
        }
        
        $db_community_event_alarm = new CommunityEventAlarm();
        $db_alarm_to_works_order = new AlarmToWorksOrder();
        $nowTime = time();
        fdump_api($bindRepair, '$bindRepair');
        fdump_api($deviceArr, '$deviceArr');
        foreach ($bindRepair as $item) {
            $bindRepairId = intval($item['id']);
            $switch_time = intval($item['switch_time']) > 0 ? intval($item['switch_time']) : $item['add_time'];
            $village_id = isset($item['village_id']) ? intval($item['village_id']) : 0;
            $whereAlarm = [];
//            $whereAlarm[] = ['village_id', '=', $village_id];
            $whereAlarm[] = ['business_type', '=', EventAlarmConst::HIK_NEI_BU_EVENT_ALARM];
            $whereAlarm[] = ['event_time', '>', $switch_time];
            $alarmField = 'id, village_id, event_id, device_id, device_name, channel_id, channel_name, event_code, event_description, community_name, local_type, local_url, event_time';
            $alarmList = $db_community_event_alarm->getList($whereAlarm, $alarmField, 'event_time ASC');
            if ($alarmList && !is_array($alarmList)) {
                $alarmList = $alarmList->toArray();
            }
            fdump_api($alarmList, '$alarmList');
            $cat_fid = isset($item['repair_cate_id']) ? intval($item['repair_cate_id']) : 0;
            $cat_id = isset($item['repair_cate_sub_id']) ? intval($item['repair_cate_sub_id']) : 0;
            foreach ($alarmList as $alarm) {
                $id = isset($alarm['id']) ? intval($alarm['id']) : 0;
                $event_time = isset($alarm['event_time']) ? intval($alarm['event_time']) : 0;
                $village_id = isset($alarm['village_id']) ? intval($alarm['village_id']) : $village_id;
                $event_device_id = isset($alarm['device_id']) ? trim($alarm['device_id']) : '';
                $device = isset($deviceArr[$event_device_id]) ? $deviceArr[$event_device_id] : [];
                $device_id = isset($device['device_id']) ? intval($device['device_id']) : 0;
                !$village_id && $village_id = isset($device['village_id']) ? intval($device['village_id']) : 0;
                $single_name = isset($device['single_name']) ? trim($device['single_name']) : '';
                $floor_name = isset($device['floor_name']) ? trim($device['floor_name']) : '';
                $event_id = isset($alarm['event_id']) ? trim($alarm['event_id']) : '';
                $device_name = isset($alarm['device_name']) ? trim($alarm['device_name']) : '';
                $channel_name = isset($alarm['channel_name']) ? trim($alarm['channel_name']) : '';
                $event_description = isset($alarm['event_description']) ? trim($alarm['event_description']) : '';
                $local_type = isset($alarm['local_type']) ? trim($alarm['local_type']) : '';
                $local_url = isset($alarm['local_url']) ? trim($alarm['local_url']) : '';
                
                $whereToOrder = [];
                $whereToOrder[] = ['village_id', '=', $village_id];
                $whereToOrder[] = ['device_type', '=', $device_type];
                $whereToOrder[] = ['business_type', '=', $business_type];
                $id >0 && $whereToOrder[] = ['event_alarm_id', '=', $id];
                $event_id && $whereToOrder[] = ['event_id', '=', $event_id];
                $whereToOrder[] = ['event_device_id', '=', $event_device_id];
                $toOrderInfo = $db_alarm_to_works_order->getOne($whereToOrder, 'id, works_order_id');
                fdump_api($toOrderInfo, '$toOrderInfo', 1);
                if (isset($toOrderInfo['works_order_id']) && $toOrderInfo['works_order_id']) {
                    // 已经提交过工单的跳过
                    continue;
                }
                
                $order_imgs = '';
                if ($local_type == 'image') {
                    $order_imgs = $local_url;
                }
                $address_txt = '';
                $single_name && $address_txt .= $this->traitAutoFixLouDongTips($single_name);
                $floor_name && $address_txt .= ' ' . $this->traitAutoFixDanyuanTips($floor_name);
                $device_name && $address_txt .= ' ' . $device_name . '(设备)';
                $channel_name && $address_txt .= ' ' . $channel_name . '(通道)';
                $order_content = $event_description;
                $data = [
                    'village_id' => $village_id, 'category_id' => 0, 'type_id' => 0,
                    'cat_fid' => $cat_fid, 'cat_id' => $cat_id,
                    'address_type' => '',
                    'address_id' => 0,
                    'label_txt' => '',
                    'order_imgs' => $order_imgs ? $order_imgs : '',
                    'order_content' => $order_content ? $order_content : '',
                    'address_txt' => $address_txt,
                    'event_status' => 10,
                    'go_time' => date('Y-m-d H:i:s', $nowTime+600),
                ];
                fdump_api($data, '$data', 1);
                $repair_cate_service = new RepairCateService();
                try {
                    $info = $repair_cate_service->addWorksOrder($data);
                    if (isset($info['order_id']) && $info['order_id']) {
                        $data = [
                            'village_id' => $village_id,
                            'device_type' => $device_type,
                            'business_type' => $business_type,
                            'event_alarm_id' => $id,
                            'event_id' => $event_id,
                            'event_time' => $event_time,
                            'event_device_id' => $event_device_id,
                            'device_id' => $device_id,
                            'works_order_id' => $info['order_id'],
                            'order_time' => $nowTime,
                        ];
                        if (isset($toOrderInfo['id']) && $toOrderInfo['id']) {
                            // 更新
                            $data['update_time'] = $nowTime;
                            $result = $db_alarm_to_works_order->updateThis(['id' => $toOrderInfo['id']], $data);
                        } else {
                            $data['add_time'] = $nowTime;
                            $result = $db_alarm_to_works_order->add($data);
                        }
                        if ($result && $switch_time < $event_time) {
                            $dbAlarmNewRepair->updateThis(['id' => $bindRepairId], ['switch_time' => $event_time]);
                        }
                    }
                } catch (\Exception $e) {
                    fdump_api(['line' => $e->getLine(), 'msg' => $e->getMessage(), 'file' =>$e->getFile(), 'code' =>$e->getCode(), 'data' =>$data], '1errAlarmToWorkOrder', 1);
                }
            }
        }
        return true;
    }
}