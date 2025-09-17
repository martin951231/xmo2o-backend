<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      物业相关新版收费逻辑相关
 */

namespace app\community\model\service\Property;

use app\community\model\db\HouseNewChargeProject;
use app\community\model\db\HouseNewOfflinePay;
use app\community\model\db\HouseNewPayOrder;
use app\community\model\db\HouseNewPayOrderSummary;
use app\community\model\db\HouseVillage;
use app\community\model\db\HouseVillageParkingGarage;
use app\community\model\db\HouseVillageParkingPosition;
use app\community\model\db\HouseVillageUserVacancy;
use app\community\model\service\HouseNewCashierService;
use app\community\model\service\HouseVillageService;

class HouseVillageNewOrderService
{
    /**
     * 获取物业自由支付相关标记类型 涉及表 pigcms_house_new_pay_order_summary 和 pigcms_house_new_pay_order 中字段 own_from 和 own_from_id
     * @return string[]
     */
    public function getPropertyOwnFrom() {
        $ownFrom = ['property_wxapp'];
        return $ownFrom;
    }
    
    public function propertyOwnVillageOrder($param) {
        $property_id = isset($param['property_id']) && $param['property_id'] ? $param['property_id'] : 0;
        $village_id  = isset($param['village_id'])  && $param['village_id']  ? $param['village_id']  : 0;
        $page        = isset($param['page'])        && $param['page']        ? $param['page']        : 0;
        $limit       = isset($param['limit'])       && $param['limit']       ? $param['limit']       : 0;
        if (!$property_id) {
            return [];
        }
        $db_house_new_pay_order_summary = new HouseNewPayOrderSummary();
        $ownFrom = $this->getPropertyOwnFrom();
        $whereSummary = [];
        $whereSummary[] = ['own_from',    'in', $ownFrom];
        $whereSummary[] = ['own_from_id', '=', $property_id];
        if ($village_id) {
            $whereSummary[] = ['village_id', '=', $village_id];
        }
        $summary_list = $db_house_new_pay_order_summary->getLists($whereSummary, 'summary_id,pay_time,pay_type,offline_pay_type,paid_orderid');
        if ($summary_list && !is_array($summary_list)) {
            $summary_list = $summary_list->toArray();
        }
        if (!$summary_list || empty($summary_list)) {
            return [];
        }
        $db_house_new_offline_pay = new HouseNewOfflinePay();
        $where_pay = [];
        $where_pay[] = ['property_id', '=', $property_id];
        $pay_list = $db_house_new_offline_pay->getColumn($where_pay, 'name', 'id');
         if ($pay_list && !is_array($pay_list)) {
             $pay_list = $pay_list->toArray();
         }
        $payList = [];
        if (!empty($pay_list)) {
            foreach ($pay_list as $vv) {
                $payList[$vv['id']] = $vv['name'];
            }
        }
        $summaryIdArr   = [];
        $payTimeArr     = [];
        $paidOrderIdArr = [];
        $payTypeArr     = [];
        $serviceHouseNewCashier = new HouseNewCashierService();
        $pay_type_arr        = $serviceHouseNewCashier->pay_type;
        $online_pay_type_arr = $serviceHouseNewCashier->pay_type_arr;
        foreach ($summary_list as $summary) {
            $summaryIdArr[] = $summary['summary_id'];
            $payTimeArr[$summary['summary_id']]     = $summary['pay_time'];
            if (isset($summary['paid_orderid']) && $summary['paid_orderid']) {
                $paidOrderIdArr[$summary['summary_id']] = $summary['paid_orderid'];
            }
            $pay_type         = $summary['pay_type'];
            $offline_pay_type = '';
            if (!empty($payList) && !empty($summary['offline_pay_type'])) {
                if (strpos($summary['offline_pay_type'], ',') > 0) {
                    $offline_pay_type_arr = explode(',', $summary['offline_pay_type']);
                    foreach ($offline_pay_type_arr as $opay) {
                        if (isset($payList[$opay])) {
                            $offline_pay_type .= empty($offline_pay_type) ? $payList[$opay] : '、' . $payList[$opay];
                        }
                    }
                } else {
                    $offline_pay_type = isset($payList[$summary['offline_pay_type']]) ? $payList[$summary['offline_pay_type']] : '';
                }
            }
            $pay_type_txt = '';
            if (!empty($pay_type)) {
                if (in_array($pay_type, [2, 22]) && isset($pay_type_arr[$pay_type])) {
                    $pay_type_txt = $pay_type_arr[$pay_type] . '-' . $offline_pay_type;
                } elseif (in_array($pay_type, [1, 3]) && isset($pay_type_arr[$pay_type])) {
                    $pay_type_txt = $pay_type_arr[$pay_type];
                } elseif ($pay_type == 4 && isset($pay_type_arr[$pay_type])) {
                    if (empty($online_pay_type)) {
                        $online_pay_type1 = '余额支付';
                    } else {
                        $online_pay_type1 = $online_pay_type_arr[$online_pay_type];
                    }
                    $pay_type_txt = $pay_type_arr[$pay_type] . '-' . $online_pay_type1;
                }
            }
            $payTypeArr[$summary['summary_id']]     = $pay_type_txt;
        }
        if (!$summaryIdArr || empty($summaryIdArr)) {
            return [];
        }
        $wherePayOrder = [];
        $wherePayOrder[] = ['is_discard',    '=', 1];
        $wherePayOrder[] = ['is_paid',       '=', 1];
        $wherePayOrder[] = ['summary_id',    'in', $summaryIdArr];
        if ($village_id) {
            $wherePayOrder[] = ['village_id', '=', $village_id];
        }
        $field = true;
        $db_house_new_pay_order = new HouseNewPayOrder();
        $list = $db_house_new_pay_order->getPayLists($wherePayOrder, $field, $page, $limit);
        if ($list && !is_array($list)) {
            $list = $list->toArray();
        }
        if (empty($list)) {
            return [];
        }
        $villageIdArr  = [];
        $positionIdArr = [];
        $roomIdArr     = [];
        $projectIdArr  = [];
        foreach ($list as $item) {
            if (isset($item['village_id']) && $item['village_id'] && !isset($villageIdArr[$item['village_id']])) {
                $villageIdArr[] = $item['village_id'];
            }
            if (isset($item['position_id']) && $item['position_id'] && !isset($positionIdArr[$item['position_id']])) {
                $positionIdArr[] = $item['position_id'];
            }
            if (isset($item['room_id']) && $item['room_id'] && !isset($roomIdArr[$item['room_id']])) {
                $roomIdArr[] = $item['room_id'];
            }
            if (isset($item['project_id']) && $item['project_id'] && !isset($projectIdArr[$item['project_id']])) {
                $projectIdArr[$item['project_id']] = $item['project_id'];
            }
        }
        $village_arr = [];
        if (!empty($villageIdArr)) {
            $db_house_village = new HouseVillage();
            $whereVillage = [];
            $whereVillage[] = ['village_id', 'in', $villageIdArr];
            $village_arr = $db_house_village->getColumn($whereVillage, 'village_id, village_name', 'village_id');
        }
        $position_arr = [];
        $garage_arr   = [];
        if (empty($positionIdArr)) {
            $db_house_village_parking_position = new HouseVillageParkingPosition();
            $db_house_village_parking_garage   = new HouseVillageParkingGarage();
            $wherePosition = [];
            $wherePosition[] = ['position_id', 'in', $positionIdArr];
            $garageIdArr  = $db_house_village_parking_position->getColumn($wherePosition, 'garage_id');
            $position_arr = $db_house_village_parking_position->getColumn($wherePosition, 'position_id, position_num, garage_id', 'position_id');
            $whereGarage = [];
            $whereGarage[] = ['garage_id', 'in', $garageIdArr];
            $garage_arr = $db_house_village_parking_garage->getColumn($whereGarage, 'garage_id, garage_num', 'garage_id');
        }
        $room_arr = [];
        if (empty($roomIdArr)) {
            $db_house_village_user_vacancy = new HouseVillageUserVacancy();
            $whereRoom = [];
            $whereRoom[] = ['pigcms_id', 'in', $roomIdArr];
            $room_arr  = $db_house_village_user_vacancy->getColumn($whereRoom, 'pigcms_id, room, village_id, single_id, floor_id, layer_id', 'pigcms_id');
        }
        $project_arr = [];
        if (empty($projectIdArr)) {
            $db_house_new_charge_project = new HouseNewChargeProject();
            $whereProject = [];
            $whereProject[] = ['pigcms_id', 'in', $roomIdArr];
            $project_arr  = $db_house_new_charge_project->getColumn($whereProject, 'id, name', 'id');
        }
        
        $house_village_service = new HouseVillageService();

        foreach ($list as &$item) {
            $item['village_name'] = '';
            $item['address_txt']  = '';
            $item['project_name'] = '';
            $item['pay_type_txt'] = '';
            $item['pay_time_txt'] = '';
            $village_id = isset($item['village_id']) && $item['village_id'] ? $item['village_id'] : 0;
            if ($village_id && !isset($village_arr[$item['village_id']])) {
                $item['village_name'] = isset($village_arr[$item['village_id']]['village_name']) ? $village_arr[$item['village_id']]['village_name'] : '';
            }
            if (isset($item['position_id']) && $item['position_id'] && !isset($position_arr[$item['position_id']])) {
                $garage_id    = isset($position_arr[$item['position_id']]['garage_id'])    ? intval($position_arr[$item['position_id']]['garage_id'])    : '';
                $position_num = isset($position_arr[$item['position_id']]['position_num']) ? intval($position_arr[$item['position_id']]['position_num']) : '';
                $garage_num   = isset($garage_arr[$garage_id]['garage_num']) ? intval($garage_arr[$garage_id]['garage_num']) : '';
                $item['address_txt'] = $garage_num . $position_num;
            }
            if (isset($item['room_id']) && $item['room_id'] && !isset($room_arr[$item['room_id']])) {
                $single_id = isset($room_arr[$item['room_id']]['single_id']) ? intval($room_arr[$item['room_id']]['single_id']) : 0;
                $floor_id  = isset($room_arr[$item['room_id']]['floor_id'])  ? intval($room_arr[$item['room_id']]['floor_id'])  : 0;
                $layer_id  = isset($room_arr[$item['room_id']]['layer_id'])  ? intval($room_arr[$item['room_id']]['layer_id'])  : 0;
                $room_id   = $item['room_id'];
                $item['address_txt'] = $house_village_service->getSingleFloorRoom($single_id, $floor_id, $layer_id, $room_id, $village_id);
            }
            if (isset($item['project_id']) && $item['project_id'] && !isset($project_arr[$item['project_id']])) {
                $item['project_name'] = isset($project_arr[$item['project_id']]['name']) ? $project_arr[$item['project_id']]['name'] : '';
            }
            if (isset($item['summary_id']) && $item['summary_id'] && !isset($payTypeArr[$item['summary_id']])) {
                $item['pay_type_txt'] = isset($payTypeArr[$item['summary_id']])     ? $payTypeArr[$item['project_id']]     : '';
                $item['pay_time_txt'] = isset($payTimeArr[$item['summary_id']])     ? $payTimeArr[$item['project_id']]     : '';
                $item['paid_orderid'] = isset($paidOrderIdArr[$item['summary_id']]) ? $paidOrderIdArr[$item['project_id']] : '';
            }
            if ($item['is_refund'] == 1) {
                $item['order_status'] = '正常';
            } else {
                if ($item['refund_money'] == $item['pay_money']) {
                    $item['order_status'] = '已退款';
                } else {
                    $item['order_status'] = '部分退款';
                }
            }
            if (isset($item['pay_bind_name']) && empty($item['pay_bind_name'])) {
                $item['pay_bind_name'] = '无';
            }
            if ($item['pay_bind_phone'] && empty($item['pay_bind_phone'])) {
                $item['pay_bind_phone'] = '无';
            }
        }
        
        
        $data = [];
        $data['list'] = $list;
        if ($page > 0 && $limit > 0) {
            $count = $db_house_new_pay_order->getPayCount($wherePayOrder);
            $data['count'] = $count;
        }
        return $data;
    }
}