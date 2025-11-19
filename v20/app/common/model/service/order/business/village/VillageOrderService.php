<?php
/**
 * 小区相关订单处理整合.
 */

namespace app\common\model\service\order\business\village;

use app\common\model\service\order\CommonOrderBusinessRecordService;
use app\community\model\db\HouseVillagePayOrder;
use app\community\model\db\HouseVillagePileEquipment;
use app\consts\OrderRecordConst;
use app\pay\model\db\PayOrderInfo;

class VillageOrderService
{
    /**
     * 公共过滤处理小区订单记录.
     */
    public function recordHandle($param) {
        $device_type = isset($param['device_type']) && $param['device_type'] ? trim($param['device_type']) : '';
        $order_type = isset($param['order_type']) && $param['order_type'] ? trim($param['order_type']) : '';
        if (strval($device_type) === OrderRecordConst::DEVICE_TYPE_PILE || strval($order_type) === OrderRecordConst::ORDER_TYPE_PILE_TABLE) {
            return $this->pileRecordHandle($param);
        }
    }

    /**
     * 处理充电桩订单关联记录.
     */
    public function pileRecordHandle($param) {
        $id = isset($param['id']) && $param['id'] ? intval($param['id']) : '';
        $order_id = isset($param['order_id']) && $param['order_id'] ? intval($param['order_id']) : '';
        $service_order = new HouseVillagePayOrder();
        $whereOrder = [];
        if ($id) {
            $whereOrder[] = ['id', '=', $id];
        } elseif ($order_id) {
            $whereOrder[] = ['order_id', '=', $order_id];
        }
        $order_info = $service_order->get_one($whereOrder);
        if ($order_info && !is_array($order_info)) {
            $order_info = $order_info->toArray();
        }
        if (empty($order_info)) {
            return false;
        }
        !$id && $id = isset($order_info['id']) && $order_info['id'] ? intval($order_info['id']) : $order_id;
        $orderid = isset($order_info['order_no']) && $order_info['order_no'] ? trim($order_info['order_no']) : '';
        $db_house_village_pile_equipment = new HouseVillagePileEquipment();
        $equipment_id = isset($order_info['equipment_id']) && $order_info['equipment_id'] ? intval($order_info['equipment_id']) : 0;
        $whereEquipment = [];
        $whereEquipment[] = ['id', '=', $equipment_id];
        $equipment_info = $db_house_village_pile_equipment->getInfo($whereEquipment, 'id, equipment_num');
        if ($equipment_info && !is_array($equipment_info)) {
            $equipment_info = $equipment_info->toArray();
        }
        $equipment_num = isset($equipment_info['equipment_num']) && $equipment_info['equipment_num'] ? trim($equipment_info['equipment_num']) : '';

        $db_pay_order_info = new PayOrderInfo();
        $wherePayOrder = [];
        $wherePayOrder[] = ['business', '=', 'pile'];
        $wherePayOrder[] = ['business_order_id', '=', $id];
        $payOrderInfo = $db_pay_order_info->getOne($wherePayOrder, 'id, orderid, pay_type, money, pay_type, env, channel, paid_time, paid_extra');
        if ($payOrderInfo && !is_array($payOrderInfo)) {
            $payOrderInfo = $payOrderInfo->toArray();
        }
        !$orderid && $orderid = isset($payOrderInfo['orderid']) && $payOrderInfo['orderid'] ? trim($payOrderInfo['orderid']) : '';
        $pay_order_info_id = isset($payOrderInfo['id']) && $payOrderInfo['id'] ? intval($payOrderInfo['id']) : 0;
        $pay_type = isset($payOrderInfo['pay_type']) && $payOrderInfo['pay_type'] ? trim($payOrderInfo['pay_type']) : '';
        $money = isset($payOrderInfo['money']) && $payOrderInfo['money'] ? $payOrderInfo['money'] : '';
        $moneyUnit = '分';
        $pay_env = isset($payOrderInfo['env']) && $payOrderInfo['env'] ? trim($payOrderInfo['env']) : '';
        $pay_channel = isset($payOrderInfo['channel']) && $payOrderInfo['channel'] ? trim($payOrderInfo['channel']) : '';
        $pay_time = isset($payOrderInfo['paid_time']) && $payOrderInfo['paid_time'] ? $payOrderInfo['paid_time'] : 0;
        $pay_order = isset($payOrderInfo['paid_extra']) && $payOrderInfo['paid_extra'] ? trim($payOrderInfo['paid_extra']) : '';

        $village_id = isset($param['village_id']) && $param['village_id'] ? intval($param['village_id']) : 0;
        !$village_id && $village_id = isset($order_info['village_id']) && $order_info['village_id'] ? intval($order_info['village_id']) : 0;
        $recordParam = [
            'business_type' => OrderRecordConst::ORDER_TYPE_PILE_TABLE,
            'business_id' => $order_id,
            'business_source' => OrderRecordConst::BUSINESS_SOURCE_VILLAGE,
            'village_id' => $village_id,
            'business_source_1_id' => $village_id,
            'device_type' => OrderRecordConst::DEVICE_TYPE_PILE,
            'device_id' => $equipment_id,
            'device_number' => $equipment_num,
            'order_type' => OrderRecordConst::ORDER_TYPE_PILE_TABLE,
            'order_id' => $id,
            'orderid' => $orderid,
            'pay_order_info_id' => $pay_order_info_id,
            'pay_type' => $pay_type,
            'money' => $money,
            'moneyUnit' => $moneyUnit,
            'pay_env' => $pay_env,
            'pay_channel' => $pay_channel,
            'pay_time' => $pay_time,
            'pay_order' => $pay_order,
        ];
        $record_id = (new CommonOrderBusinessRecordService())->handleRecordOrder($recordParam);
        return $record_id;
    }
}