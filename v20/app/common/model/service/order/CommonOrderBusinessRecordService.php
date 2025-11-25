<?php
/**
 * 公共订单业务关联记录表.
 * 便于方便直接通过订单查询相关订单来源和详情.
 */

namespace app\common\model\service\order;

use app\common\model\db\CommonOrderBusinessRecord;
use app\common\model\service\order\business\village\VillageOrderService;
use app\consts\OrderRecordConst;
use app\traits\CommonOrderBusinessRecordJobTraits;

class CommonOrderBusinessRecordService
{
    use CommonOrderBusinessRecordJobTraits;

    public function toExecute($param) {
        $type = isset($param['type']) ? $param['type'] : '';
        switch ($type) {
            case 'handleRecordOrder':
                $this->handleRecordOrder($param);
                break;
            case 'afterPayOder':
                $this->afterPayOder($param);
                break;
        }
        return true;
    }

    /**
     * 支付回调按照业务区分下
     */
    public function afterPayOderToJob($param) {
        $param['jobType'] = 'afterPayOder';
        try {
            $job_id = $this->traitCommonOrderBusinessRecordJobTraits($param);
        } catch (\Exception $e) {
            fdump_api(['param' => $param, 'err' => $e->getMessage(), 'line' => $e->getLine()], 'commonRecord/errHandleRecordOrderToJobLog', 1);
            $job_id = 0;
        }
        return $job_id;
    }

    /**
     * 已经处理好数据了 直接走记录队列，建议按照业务区分下。
     */
    public function handleRecordOrderToJob($param) {
        $param['type'] = 'handleRecordOrder';
        try {
            $job_id = $this->traitCommonOrderBusinessRecordJobTraits($param);
        } catch (\Exception $e) {
            fdump_api(['param' => $param, 'err' => $e->getMessage(), 'line' => $e->getLine()], 'commonRecord/errHandleRecordOrderToJobLog', 1);
            $job_id = 0;
        }
        return $job_id;
    }

    /**
     * 按照业务进行区分【不支持直接调用走队列】。
     */
    protected function afterPayOder($param) {
        $business_source = isset($param['business_source']) ? trim($param['business_source']) : '';
        $isHandle = 0;
        switch ($business_source) {
            case OrderRecordConst::BUSINESS_SOURCE_VILLAGE:
                $handleService = new VillageOrderService();
                $isHandle = 1;
                break;
        }
        $isHandle == 1 && $handleService->recordHandle($param);
        return true;
    }

    /**
     * 公共订单业务关联记录处理入口.
     */
    public function handleRecordOrder($param) {
        try {
            $result = $this->recordOrder($param);
            if (! $result) {
                fdump_api(['param' => $param, 'result' => $result], 'commonRecord/errHandleRecordOrderLog', 1);
            }
        } catch (\Exception $e) {
            fdump_api(['param' => $param, 'err' => $e->getMessage(), 'line' => $e->getLine()], 'commonRecord/errHandleRecordOrderLog', 1);
            $result = 0;
        }
        return $result;
    }

    /**
     * 公共订单业务关联记录处理.
     */
    protected function recordOrder($param) {
        $business_type = isset($param['business_type']) && $param['business_type'] ? trim($param['business_type']) : '';
        $business_id = isset($param['business_id']) && $param['business_id'] ? trim($param['business_id']) : '';
        if (! $business_type) {
            return false;
        }
        $nowTime = time();
        $data = [
            'business_type' => $business_type,
        ];
        $uniqueArr = [
            'business_type' => $business_type,
        ];
        $business_id && $uniqueArr['business_id'] = $business_id;
        $business_id && $data['business_id'] = $business_id;
        $business_source = isset($param['business_source']) && $param['business_source'] ? trim($param['business_source']) : '';
        $business_source_1_id = isset($param['business_source_1_id']) && $param['business_source_1_id'] ? intval($param['business_source_1_id']) : 0;
        $business_source_2_id = isset($param['business_source_2_id']) && $param['business_source_2_id'] ? intval($param['business_source_2_id']) : 0;
        $business_source_3_id = isset($param['business_source_3_id']) && $param['business_source_3_id'] ? intval($param['business_source_3_id']) : 0;
        $business_source_4_id = isset($param['business_source_4_id']) && $param['business_source_4_id'] ? intval($param['business_source_4_id']) : 0;
        $business_source_5_id = isset($param['business_source_5_id']) && $param['business_source_5_id'] ? intval($param['business_source_5_id']) : 0;
        $keyArr = [
            'business_type', 'business_id', 'business_source', 'business_source_1_id', 'business_source_2_id', 'business_source_3_id',
            'business_source_4_id', 'business_source_5_id',
        ];
        $uniqueArr['business_source'] = $business_source;
        // 业务来源 和 对应业务id 记录
        switch ($business_source) {
            case OrderRecordConst::BUSINESS_SOURCE_ROBOT:
                // 机器人业务  如果都有 依次记录 小区id-物业id-社区id-街道id-区域id
            case OrderRecordConst::BUSINESS_SOURCE_VILLAGE:
                // 小区业务 如果都有 依次记录 小区id-物业id-社区id-街道id-区域id
                $village_id = isset($param['village_id']) && $param['village_id'] ? intval($param['village_id']) : 0;
                $village_id && $business_source_1_id = $village_id;
                $property_id = isset($param['property_id']) && $param['property_id'] ? intval($param['property_id']) : 0;
                $property_id && $business_source_2_id = $property_id;
                $community_id = isset($param['community_id']) && $param['community_id'] ? intval($param['community_id']) : 0;
                $community_id && $business_source_3_id = $community_id;
                $street_id = isset($param['street_id']) && $param['street_id'] ? intval($param['street_id']) : 0;
                $street_id && $business_source_4_id = $street_id;
                $area_id = isset($param['area_id']) && $param['area_id'] ? intval($param['area_id']) : 0;
                $area_id && $business_source_5_id = $area_id;
                $keyArr[] = 'village_id';
                $keyArr[] = 'property_id';
                $keyArr[] = 'community_id';
                $keyArr[] = 'street_id';
                $keyArr[] = 'area_id';
                break;
            case OrderRecordConst::BUSINESS_SOURCE_PROPERTY:
                // 物业业务 如果都有 依次记录 物业id-社区id-街道id-区域id
                $property_id = isset($param['property_id']) && $param['property_id'] ? intval($param['property_id']) : 0;
                $property_id && $business_source_1_id = $property_id;
                $community_id = isset($param['community_id']) && $param['community_id'] ? intval($param['community_id']) : 0;
                $community_id && $business_source_2_id = $community_id;
                $street_id = isset($param['street_id']) && $param['street_id'] ? intval($param['street_id']) : 0;
                $street_id && $business_source_3_id = $street_id;
                $area_id = isset($param['area_id']) && $param['area_id'] ? intval($param['area_id']) : 0;
                $area_id && $business_source_4_id = $area_id;
                $keyArr[] = 'property_id';
                $keyArr[] = 'community_id';
                $keyArr[] = 'street_id';
                $keyArr[] = 'area_id';
                break;
            case OrderRecordConst::BUSINESS_SOURCE_MERCHANT:
                // 商家业务 如果都有 依次记录 商家id-区域id
                $mer_id = isset($param['mer_id']) && $param['mer_id'] ? intval($param['mer_id']) : 0;
                $mer_id && $business_source_1_id = $mer_id;
                $area_id = isset($param['area_id']) && $param['area_id'] ? intval($param['area_id']) : 0;
                $area_id && $business_source_2_id = $area_id;
                $keyArr[] = 'mer_id';
                $keyArr[] = 'area_id';
                break;
            case OrderRecordConst::BUSINESS_SOURCE_STORE:
                // 店铺业务 如果都有 依次记录 店铺id-商家id-区域id
                $store_id = isset($param['store_id']) && $param['store_id'] ? intval($param['store_id']) : 0;
                $store_id && $business_source_1_id = $store_id;
                $mer_id = isset($param['mer_id']) && $param['mer_id'] ? intval($param['mer_id']) : 0;
                $mer_id && $business_source_2_id = $mer_id;
                $area_id = isset($param['area_id']) && $param['area_id'] ? intval($param['area_id']) : 0;
                $area_id && $business_source_3_id = $area_id;
                $keyArr[] = 'store_id';
                $keyArr[] = 'mer_id';
                $keyArr[] = 'area_id';
                break;
        }
        $uniqueArr['business_source_1_id'] = $business_source_1_id;
        $business_source && $data['business_source'] = $business_source;
        $business_source_1_id && $data['business_source_1_id'] = $business_source_1_id;
        $business_source_2_id && $data['business_source_2_id'] = $business_source_2_id;
        $business_source_3_id && $data['business_source_3_id'] = $business_source_3_id;
        $business_source_4_id && $data['business_source_4_id'] = $business_source_4_id;
        $business_source_5_id && $data['business_source_5_id'] = $business_source_5_id;

        // 对应订单业务涉及机器人 处理相关记录
        $device_type = isset($param['device_type']) && $param['device_type'] ? trim($param['device_type']) : '';
        $device_id = isset($param['device_id']) && $param['device_id'] ? intval($param['device_id']) : 0;
        $device_number = isset($param['device_number']) && $param['device_number'] ? trim($param['device_number']) : '';
        $keyArr[] = 'device_type';
        $keyArr[] = 'device_id';
        $keyArr[] = 'device_number';
        switch ($device_type) {
            case OrderRecordConst::DEVICE_TYPE_FACE:
                $device_sn = isset($param['device_sn']) && $param['device_sn'] ? trim($param['device_sn']) : '';
                $device_sn && $device_number = $device_sn;
                $keyArr[] = 'device_sn';
                break;
            case OrderRecordConst::DEVICE_TYPE_CAMERA:
                $camera_id = isset($param['camera_id']) && $param['camera_id'] ? intval($param['camera_id']) : 0;
                $camera_id && $device_id = $camera_id;
                $camera_sn = isset($param['camera_sn']) && $param['camera_sn'] ? trim($param['camera_sn']) : '';
                $camera_sn && $device_number = $camera_sn;
                $keyArr[] = 'camera_id';
                $keyArr[] = 'camera_sn';
                break;
            case OrderRecordConst::DEVICE_TYPE_PILE_HZ:
                $id = isset($param['id']) && $param['id'] ? intval($param['id']) : 0;
                $id && $device_id = $id;
                $keyArr[] = 'id';
                break;
            case OrderRecordConst::DEVICE_TYPE_PILE:
                $id = isset($param['equipment_id']) && $param['equipment_id'] ? intval($param['equipment_id']) : 0;
                $id && $device_id = $id;
                $equipment_num = isset($param['equipment_num']) && $param['equipment_num'] ? trim($param['equipment_num']) : '';
                $equipment_num && $device_number = $equipment_num;
                $keyArr[] = 'equipment_id';
                $keyArr[] = 'equipment_num';
                break;
            case OrderRecordConst::DEVICE_TYPE_NEW_PILE:
                $id = isset($param['equipment_id']) && $param['equipment_id'] ? intval($param['equipment_id']) : 0;
                $id && $device_id = $id;
                $equipment_num = isset($param['equipment_num']) && $param['equipment_num'] ? trim($param['equipment_num']) : '';
                $equipment_num && $device_number = $equipment_num;
                $keyArr[] = 'equipment_id';
                $keyArr[] = 'equipment_num';
                break;
            case OrderRecordConst::DEVICE_TYPE_ROBOT:
                $id = isset($param['robot_id']) && $param['robot_id'] ? intval($param['robot_id']) : 0;
                $id && $device_id = $id;
                $robot_code = isset($param['robot_code']) && $param['robot_code'] ? trim($param['robot_code']) : '';
                $robot_code && $device_number = $robot_code;
                $robotCode = isset($param['robotCode']) && $param['robotCode'] ? trim($param['robotCode']) : '';
                $robotCode && !$robot_code && $device_number = $robotCode;
                $keyArr[] = 'robot_id';
                $keyArr[] = 'robot_code';
                $keyArr[] = 'robotCode';
                break;
        }
        $device_type && $data['device_type'] = $device_type;
        $device_id && $data['device_id'] = $device_id;
        $device_number && $data['device_number'] = $device_number;

        // 对应订单
        // order_type-订单类型（建议直接用对应订单表名）
        $order_type = isset($param['order_type']) && $param['order_type'] ? trim($param['order_type']) : '';
        $order_type && $uniqueArr['order_type'] = $order_type;
        $order_type && $data['order_type'] = $order_type;
        $keyArr[] = 'order_type';
        // order_id-对应订单表id
        $order_id = isset($param['order_id']) && $param['order_id'] ? intval($param['order_id']) : '';
        $order_id && $uniqueArr['order_id'] = $order_id;
        $order_id && $data['order_id'] = $order_id;
        $keyArr[] = 'order_id';
        // orderid-长订单id(由发起平台生成)
        $orderid = isset($param['orderid']) && $param['orderid'] ? trim($param['orderid']) : '';
        $orderid && $data['orderid'] = $orderid;
        $keyArr[] = 'orderid';
        // pay_order-支付订单号(对应支付三方反馈)
        $pay_order = isset($param['pay_order']) && $param['pay_order'] ? trim($param['pay_order']) : '';
        $pay_order && $data['pay_order'] = $pay_order;
        $keyArr[] = 'pay_order';
        // refund_orderid-退款长订单号(由发起平台生成)
        $refund_orderid = isset($param['refund_orderid']) && $param['refund_orderid'] ? trim($param['refund_orderid']) : '';
        $refund_orderid && $data['refund_orderid'] = $refund_orderid;
        $keyArr[] = 'refund_orderid';
        // refund_pay_order-退款订单号(对应支付三方反馈)
        $refund_pay_order = isset($param['refund_pay_order']) && $param['refund_pay_order'] ? trim($param['refund_pay_order']) : '';
        $refund_pay_order && $data['refund_pay_order'] = $refund_pay_order;
        $keyArr[] = 'refund_pay_order';
        // pay_time-订单支付时间
        $pay_time = isset($param['pay_time']) && $param['pay_time'] ? intval($param['pay_time']) : 0;
        $pay_time && $data['pay_time'] = $pay_time;
        $keyArr[] = 'pay_time';
        // pay_type-支付方式
        $pay_type = isset($param['pay_type']) && $param['pay_type'] ? trim($param['pay_type']) : '';
        $pay_type && $data['pay_type'] = $pay_type;
        $keyArr[] = 'pay_type';
        // pay_env-支付环境标识，对应Agent中间件中
        $pay_env = isset($param['pay_env']) && $param['pay_env'] ? trim($param['pay_env']) : '';
        ! $pay_env && $pay_env = isset($param['env']) && $param['env'] ? trim($param['env']) : '';
        $pay_env && $data['pay_env'] = $pay_env;
        $keyArr[] = 'pay_env';
        $keyArr[] = 'env';
        $record_json_data = unsetWordParam($param, $keyArr);
        $uniqueId = md5(json_encode($uniqueArr, JSON_UNESCAPED_UNICODE));
        $whereUnique = [
            'unique_id' => $uniqueId
        ];
        $db_common_order_business_record = new CommonOrderBusinessRecord();
        $info = $db_common_order_business_record->getOne($whereUnique, 'record_id, record_json');
        if (isset($info['record_id']) && $info['record_id']) {
            $record_id = $info['record_id'];
            $record_json = isset($info['record_json']) && $info['record_json'] ? json_decode($info['record_json'], true) : [];
            if (! empty($record_json)) {
                $record_json = array_merge($record_json, $record_json_data);
            } else {
                $record_json = $record_json_data;
            }
            $record_json && $data['record_json'] = json_encode($record_json, JSON_UNESCAPED_UNICODE);
            $data['update_time'] = $nowTime;
            $db_common_order_business_record->updateThis([['record_id', '=', $record_id]], $data);
        } else {
            $record_json_data && $data['record_json'] = json_encode($record_json_data, JSON_UNESCAPED_UNICODE);
            $data['add_time'] = $nowTime;
            $record_id = $db_common_order_business_record->add($data);
        }
        return $record_id;
    }
}