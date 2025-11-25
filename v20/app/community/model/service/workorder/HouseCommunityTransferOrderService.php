<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      工单转单（跨小区）处理
 */

namespace app\community\model\service\workorder;

use app\community\model\db\HouseNewRepairWorksOrder;
use app\community\model\db\HouseNewRepairWorksOrderLog;
use app\community\model\db\HouseWorker;
use app\community\model\service\HouseNewRepairService;
use app\community\model\service\HouseVillageCheckauthApplyService;
use app\community\model\service\HouseVillageCheckauthSetService;

class HouseCommunityTransferOrderService
{
    /**
     * 处理需要走审核.
     */
    public function transferOrderToOtherVillage($params) {
        $bind_facilities_type = isset($params['bind_facilities_type']) ? $params['bind_facilities_type'] : 'supply_station';
        $operator_id = isset($params['operator_id']) ? $params['operator_id'] : 0;
        $order_id = isset($params['order_id']) ? intval($params['order_id']) : 0;
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $order_info = isset($params['order_info']) ? $params['order_info'] : [];
        $now_handle_village_id = isset($params['now_handle_village_id']) ? intval($params['now_handle_village_id']) : 0;
        if (!in_array($bind_facilities_type, ['supply_station', 'developer'])) {
            $bind_facilities_type = 'supply_station';
        }
        $params = ['bind_facilities_type' => $bind_facilities_type, 'order_id' => $order_id];
        $facilitiesList = (new HouseCommunityAboutService())->getVillageBindFacilitiesList($village_id, $params);
        $facilitiesInfo = reset($facilitiesList);
        $title = '供暖站';
        if ($bind_facilities_type == 'developer') {
            $title  = '开发商';
        }
        if (empty($facilitiesInfo)) {
            throw new \think\Exception('对应小区缺少可转单的' . $title);
        }

        if (empty($order_info)) {
            $db_house_new_repair_works_order = new HouseNewRepairWorksOrder();
            $where_order = [];
            $where_order[] = ['order_id', '=', $order_id];
            $order_info = $db_house_new_repair_works_order->getOne($where_order);
        }
        $notCheck = 1;
        if ($notCheck == 1) {
            $is_check = 0;
        } else {
            $houseVillageCheckauthSetService = new HouseVillageCheckauthSetService();
            $houseVillageCheckauthApplyService = new HouseVillageCheckauthApplyService();
            $orderRefundCheckWhere = array('village_id' => $village_id, 'xtype' => 'work_order_transfer_check');
            $checkAuthSet = $houseVillageCheckauthSetService->getOneCheckauthSet($orderRefundCheckWhere);
            if (isset($checkAuthSet['is_open']) && intval($checkAuthSet['is_open']) > 0 && isset($checkAuthSet['check_level']) && !empty($checkAuthSet['check_level'])) {
                $is_check = 1;
            } else {
                $is_check = 0;
            }
        }
        $login_role = isset($params['login_role']) ? $params['login_role'] : 0;
        $phone = isset($params['phone']) ? $params['phone'] : '';
        $name = isset($params['name']) ? $params['name'] : '';
        $worker_id = isset($params['worker_id']) ? $params['worker_id'] : 0;
        $order_content = isset($params['order_content']) ? $params['order_content'] : '工作人员转单';
        $nowTime = time();
        if ($is_check == 1 && $notCheck == 0) {
            // 需要审核才能通过转单.
            $orderRefundCheckArr = array('property_id' => $order_info['property_id'], 'village_id' => $order_info['village_id']);
            $orderRefundCheckArr['xtype'] = 'transfer_other_work';
            $orderRefundCheckArr['order_id'] = $order_info['order_id'];
            $orderRefundCheckArr['other_relation_id'] = $facilitiesInfo['bind_village_id'];
            $orderRefundCheckArr['money'] = 0;
            $orderRefundCheckArr['status'] = 0;  //0未审核 1审核中 2审核通过
            $orderRefundCheckArr['apply_login_role'] = $login_role;
            $orderRefundCheckArr['apply_name'] = $name;
            $orderRefundCheckArr['apply_phone'] = $phone;
            $orderRefundCheckArr['apply_uid'] = $worker_id;
            $orderRefundCheckArr['apply_reason'] = $order_content;
            $extra_data = array(
                'order_id' => $order_id, 'village_id' => $order_info['village_id'], 'bind_facilities_type' => $bind_facilities_type,
                'now_handle_village_id' => $facilitiesInfo['bind_village_id'],  'apply_time' => $nowTime, 'apply_reason' => $order_content, 
                'title' => '转单'.$title, 'event_status' => $order_info['event_status'], 'worker_id' => $order_info['worker_id']
            );
            $orderRefundCheckArr['extra_data'] = json_encode($extra_data, JSON_UNESCAPED_UNICODE);
            $extra['checkauth_set'] = $checkAuthSet;
            $operator_id && $extra['wid'] = $operator_id;
            fdump_api(['$orderRefundCheckArr' => $orderRefundCheckArr, '$extra' => $extra], '$addApply', 1);
            $insert_id = $houseVillageCheckauthApplyService->addApply($orderRefundCheckArr, $extra);
            if ($insert_id > 0) {
                $order_apply = $houseVillageCheckauthApplyService->getOneData(['id' => $insert_id]);
                if ($order_apply['status'] == 2) {
                    // todo 自动过审
                } else {
                    // todo 转单审核中
                    $event_status = 28;
                    if ($bind_facilities_type == 'developer') {
                        $event_status  = 29;
                    }
                    $order_set = [
                        'now_role' => 3, 'event_status' => $event_status,
                    ];
                    $status = 'processing'; // 已处理
                    $operator_type = 30;
                }
                $log_name = isset($params['log_name']) ? trim($params['log_name']) : '';
                if (!$log_name) {
                    $log_name = 'to_supply_station';
                    if ($bind_facilities_type == 'developer') {
                        $log_name = 'to_developer';
                    }
                }
                $log_name .= '_check';
                return [
                    'order_set' => $order_set,
                    'status' => $status,
                    'operator_type' => $operator_type,
                    'log_name' => $log_name,
                ];
            } else {
                throw new \think\Exception('已经存在待审核中的工单转单'.$title.'申请');
            }
        }
        
        $is_supply_station = 1;
        if ($bind_facilities_type == 'developer') {
            $is_supply_station  = 0;
        }
        $workOrderParam = [
            'village_id' => $facilitiesInfo['bind_village_id'],
            'is_supply_station' => $is_supply_station,
            'operator_id' => $operator_id,
        ];
        $assignRecord = (new HouseCommunityWorkOrderService())->toOtherVillageWorker($workOrderParam);
        $toWorkId = isset($assignRecord['toWorkId']) ? intval($assignRecord['toWorkId']) : 0;
        $workToOtherVillage = isset($assignRecord['workToOtherVillage']) ? $assignRecord['workToOtherVillage'] : [];

        $repairCateScheduling = [];
        if (!$toWorkId) {
            $service_house_new_repair = new HouseNewRepairService();
            $worker_ids = $service_house_new_repair->getWorkerByTime($order_info['cat_id'], $repairCateScheduling);
            if(!empty($worker_ids)){
                $index = array_rand($worker_ids);
                $toWorkId = $worker_ids[$index];
            }else{
                $toWorkId = 0;
            }
        }
        
        if ($toWorkId > 0) {
            $event_status = 26;
            if ($bind_facilities_type == 'developer') {
                $event_status  = 27;
            }
            $order_set = [
                'now_role' => 3, 'event_status' => $event_status, 'worker_type' => 0, 'worker_id' => $toWorkId,
            ];
            $status = 'processed'; // 已处理
            $operator_type = 30;
        } else {
            $event_status = 16;
            if ($bind_facilities_type == 'developer') {
                $event_status  = 17;
            }
            $order_set = [
                'now_role' => 2, 'event_status' => $event_status, 'worker_type' => 0, 'worker_id' => 0,
            ];
            $status = 'processed'; // 已处理
            $operator_type = 30;
        }
        if (!$now_handle_village_id) {
            $now_handle_village_id = $facilitiesInfo['bind_village_id'];
        }
        $order_set['now_handle_village_id']  = $now_handle_village_id;
        if ($bind_facilities_type == 'developer') {
            $order_set['to_developer_village_id']  = $now_handle_village_id;
        } else {
            $order_set['to_station_village_id']  = $now_handle_village_id;
        }
        if (!empty($workToOtherVillage)) {
            $serviceHouseCommunityWorkOrderConfig = new HouseCommunityWorkOrderService();
            $params = [
                'village_id' => $village_id, 'works_order_id' => $order_id
            ];
            $configInfo = $serviceHouseCommunityWorkOrderConfig->getHouseCommunityWorkOrderConfig($params, 'id, works_order_id, village_id, assign_record');
            $assign_record = isset($configInfo['assign_record']) && $configInfo['assign_record'] ? $configInfo['assign_record'] : '';
            if ($assign_record) {
                $assign_record_arr = json_decode($assign_record, true);
            }
            if (!empty($assign_record_arr)) {
                $assign_record_arr['workToOtherVillage'] = $workToOtherVillage;
                $repairCateScheduling && $assign_record_arr['otherVillageRepairCateScheduling'] = $repairCateScheduling;
                $fillData['assign_record'] = json_encode($assign_record_arr,JSON_UNESCAPED_UNICODE);
                $fillData['update_time'] = time();
                $serviceHouseCommunityWorkOrderConfig->updateHouseCommunityWorkOrderConfig($params, $fillData);
            }
        }
        if (!$toWorkId) {
            //todo 开启抢单模式
            $service_house_new_repair->grabOrder($village_id, $order_id, $order_info['cat_id']);
        }
        return [
            'order_set' => $order_set,
            'status' => $status,
            'operator_type' => $operator_type,
        ];
    }

    /**
     * 开发商将工单打回原小区.
     */
    public function transferOrderToOriginVillage($params) {
        $operator_id = isset($params['operator_id']) ? $params['operator_id'] : 0;
        $order_id = isset($params['order_id']) ? intval($params['order_id']) : 0;
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $order_info = isset($params['order_info']) ? $params['order_info'] : [];
        $nowTime = isset($params['nowTime']) && $params['nowTime'] ? $params['nowTime'] : time();
        !$village_id && $village_id = isset($order_info['village_id']) ? intval($order_info['village_id']) : 0;

        $db_house_new_repair_works_order_log = new HouseNewRepairWorksOrderLog();
        $whereOrderLog = [];
        $whereOrderLog[] = ['order_id', '=', $order_id];
        $whereOrderLog[] = ['log_name', '=', 'to_developer'];
        $logToDeveloperInfo = $db_house_new_repair_works_order_log->getOne($whereOrderLog);
        if ($logToDeveloperInfo && !is_array($logToDeveloperInfo)) {
            $logToDeveloperInfo = $logToDeveloperInfo->toArray();
        }
        if (empty($logToDeveloperInfo)) {
            throw new \think\Exception('缺少楼栋管家转单开发商记录');
        }
        $toWorkId = isset($logToDeveloperInfo['operator_id']) ? intval($logToDeveloperInfo['operator_id']) : 0;
        
        $workToOriginVillage = ['title' => '退回（楼栋管家）', 'msg' => '指派回原小区原工作人员', 'village_id' => $village_id, 'operator_id' => $operator_id, 'time' => $nowTime];

        $serviceHouseCommunityWorkOrderConfig = new HouseCommunityWorkOrderService();
        $params = [
            'village_id' => $village_id, 'works_order_id' => $order_id
        ];
        $configInfo = $serviceHouseCommunityWorkOrderConfig->getHouseCommunityWorkOrderConfig($params, 'id, works_order_id, village_id, assign_record');
        $assign_record = isset($configInfo['assign_record']) && $configInfo['assign_record'] ? $configInfo['assign_record'] : '';
        if ($assign_record) {
            $assign_record_arr = json_decode($assign_record, true);
        }
        if (!empty($assign_record_arr)) {
            $assign_record_arr['workToOriginVillage'] = $workToOriginVillage;
            $fillData['assign_record'] = json_encode($assign_record_arr,JSON_UNESCAPED_UNICODE);
            $fillData['update_time'] = time();
            $serviceHouseCommunityWorkOrderConfig->updateHouseCommunityWorkOrderConfig($params, $fillData);
        }
        $toOriginVillageParams = [
            'village_id' => $village_id, 'works_order_id' => $order_id, 'now_handle_village_id' => $village_id, 'to_work_id' => $toWorkId
        ];
        (new HouseCommunityWorkOrderService())->toOtherVillage($toOriginVillageParams);

        $order_set = ['now_role' => 3, 'event_status' => 25, 'worker_type' => 0, 'worker_id' => $toWorkId, 'now_handle_village_id' => 0];
        $status = 'todo'; // 待处理
        $operator_type = 30;
        return [
            'order_set' => $order_set,
            'status' => $status,
            'operator_type' => $operator_type,
        ];
    }
    
    public function transferOrderToOtherWork($params) {
        $operator_id = isset($params['operator_id']) ? $params['operator_id'] : 0;
        $order_id = isset($params['order_id']) ? intval($params['order_id']) : 0;
        $village_id = isset($params['village_id']) ? intval($params['village_id']) : 0;
        $order_info = isset($params['order_info']) ? $params['order_info'] : [];
        $notCheck = isset($params['notCheck']) ? intval($params['notCheck']) : 0;
        $to_work_id = isset($params['to_work_id']) ? intval($params['to_work_id']) : 0;
        $worker_id = isset($params['worker_id']) ? intval($params['worker_id']) : 0;
        $log_name = isset($params['log_name']) && trim($params['log_name']) ? trim($params['log_name']) : 'work_change';
        if ($notCheck == 1) {
            $is_check = 0;
        } else {
            // 同小区不同部门的工作人员之间在转单时，不需要进审批
            $dbHouseWork = new HouseWorker();
            $toWorkInfo = $dbHouseWork->getOne(['wid' => $to_work_id], 'wid, village_id, department_id');
            $workInfo = $dbHouseWork->getOne(['wid' => $worker_id], 'wid, village_id, department_id');
            if (isset($toWorkInfo['village_id']) && isset($workInfo['village_id']) && $toWorkInfo['village_id'] == $workInfo['village_id'] && isset($toWorkInfo['department_id']) && isset($workInfo['department_id']) && $toWorkInfo['department_id'] == $workInfo['department_id']) {
                // 同小区同部门下的工作人员之间在转单时，需要由设置的审批人进行审批 其他情况不需要审批
                $is_check = 1;
            } else {
                $is_check = 0;
            }
            if ($is_check == 1) {
                // 需要审核 获取下配置看是否需要审核
                $houseVillageCheckauthSetService = new HouseVillageCheckauthSetService();
                $houseVillageCheckauthApplyService = new HouseVillageCheckauthApplyService();
                $orderRefundCheckWhere = array('village_id' => $village_id, 'xtype' => 'work_order_transfer_check');
                $checkAuthSet = $houseVillageCheckauthSetService->getOneCheckauthSet($orderRefundCheckWhere);
                if (isset($checkAuthSet['is_open']) && intval($checkAuthSet['is_open']) > 0 && isset($checkAuthSet['check_level']) && !empty($checkAuthSet['check_level'])) {
                    $is_check = 1;
                } else {
                    $is_check = 0;
                }
            }
        }
        $login_role = isset($params['login_role']) ? $params['login_role'] : 0;
        $phone = isset($params['phone']) ? $params['phone'] : '';
        $name = isset($params['name']) ? $params['name'] : '';
        !$worker_id && $worker_id = isset($order_info['worker_id']) ? $order_info['worker_id'] : 0;
        $order_content = isset($params['order_content']) ? $params['order_content'] : '工作人员转单';
        $nowTime = time();
        if ($is_check == 1 && $notCheck == 0) {
            // 需要审核才能通过转单.
            $orderRefundCheckArr = array('property_id' => $order_info['property_id'], 'village_id' => $order_info['village_id']);
            $orderRefundCheckArr['xtype'] = 'transfer_other_work';
            $orderRefundCheckArr['order_id'] = $order_info['order_id'];
            $orderRefundCheckArr['other_relation_id'] = $to_work_id;
            $orderRefundCheckArr['money'] = 0;
            $orderRefundCheckArr['status'] = 0;  //0未审核 1审核中 2审核通过
            $orderRefundCheckArr['apply_login_role'] = $login_role;
            $orderRefundCheckArr['apply_name'] = $name;
            $orderRefundCheckArr['apply_phone'] = $phone;
            $orderRefundCheckArr['apply_uid'] = $worker_id;
            $orderRefundCheckArr['apply_reason'] = $order_content;
            $extra_data = array(
                'order_id' => $order_id, 'village_id' => $order_info['village_id'],
                'to_work_id' => $to_work_id, 'apply_time' => $nowTime, 'apply_reason' => $order_content, 'title' => '处理人员转单',
                'event_status' => $order_info['event_status'], 'worker_id' => $worker_id
            );
            $orderRefundCheckArr['extra_data'] = json_encode($extra_data, JSON_UNESCAPED_UNICODE);
            $extra['checkauth_set'] = $checkAuthSet;
            $operator_id && $extra['wid'] = $operator_id;
            fdump_api(['$orderRefundCheckArr' => $orderRefundCheckArr, '$extra' => $extra], '$addApply', 1);
            $insert_id = $houseVillageCheckauthApplyService->addApply($orderRefundCheckArr, $extra);
            if ($insert_id > 0) {
                $order_apply = $houseVillageCheckauthApplyService->getOneData(['id' => $insert_id]);
                if ($order_apply['status'] == 2) {
                    // todo 自动过审
                } else {
                    // todo 转单审核中
                    $event_status = 28;
                    $order_set = [
                        'now_role' => 3, 'event_status' => $event_status,
                    ];
                    $status = 'processing'; // 已处理
                    $operator_type = 30;
                }
                $log_name .= '_check';
                return [
                    'order_set' => $order_set,
                    'status' => $status,
                    'operator_type' => $operator_type,
                    'log_name' => $log_name,
                ];
            } else {
                throw new \think\Exception('已经存在待审核中的工单转单申请');
            }
        }
        $order_set = [
            'now_role' => 3,
            'event_status' => 24,
            'worker_type' => 0,
            'worker_id' => $to_work_id,
        ];
        $status = 'processed'; // 已处理
        $operator_type = 30;
        return [
            'order_set' => $order_set,
            'status' => $status,
            'operator_type' => $operator_type,
            'log_name' => $log_name,
        ];
    }
}