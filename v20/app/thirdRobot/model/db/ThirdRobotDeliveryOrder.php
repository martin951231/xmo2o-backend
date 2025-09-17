<?php

namespace app\thirdRobot\model\db;

use think\Model;

class ThirdRobotDeliveryOrder extends Model
{
    //订单状态 status
    const ORDER_STATUS_10 = 10;  //10 待支付
    const ORDER_STATUS_12 = 12;  //12 取消订单 
    const ORDER_STATUS_20 = 20;  //20 已支付 
    const ORDER_STATUS_30 = 30;  //30 前往召唤人
    const ORDER_STATUS_40 = 40;  //40 准备送货
    const ORDER_STATUS_50 = 50;  //50 前往目的地 
    const ORDER_STATUS_60 = 60;  //60 订单完成 
    const ORDER_STATUS_70 = 70;  //70 订单取消并退款

    const IS_PAID_1 = 1;//已支付

    public function callRecord()
    {
        return $this->hasOne(ThirdRobotCallRecord::class, 'call_record_id', 'record_id');
    }
    public function robot()
    {
        return $this->belongsTo(ThirdRobotDevice::class, 'robot_id', 'id');
    }

    public function searchKeyWordsAttr($query, $value, $data)
    {
        if (empty($value)) {
            return;
        }
        $type = [
            'order_no'        => 'order_no',
            'start_user_name' => 'start_address_user_name',
            'end_user_name'   => 'end_address_user_name'
        ];

        if (isset($data['type']) && in_array($data['type'], $type)) {
            $query->where($data['type'], 'like', "%$value%");
        }
    }

    public function searchStartTimeAttr($query, $value)
    {
        $value && $query->where('create_time', '>=', $value . ' 00:00:00');
    }

    public function searchEndTimeAttr($query, $value)
    {
        $value && $query->where('create_time', '<', $value . ' 23:59:59');
    }

    public function searchStatusAttr($query, $value)
    {
        $status = [
            1 => [self::ORDER_STATUS_20, self::ORDER_STATUS_30],//待配送
            2 => [self::ORDER_STATUS_40, self::ORDER_STATUS_50],//配送中
            3 => [self::ORDER_STATUS_60],//已完成
        ];
        if (isset($status[$value])) {
            $query->where('status', 'IN', $value);
        } else {
            $value > 0 && $query->where('status', $value);
        }
    }
    
    public function searchUidAttr($query, $value)
    {
        $value && $query->where('uid', $value);
    }

    public function getStatusTextAttr($value,$data)
    {
        $status = [
            self::ORDER_STATUS_10 => '待支付',
            self::ORDER_STATUS_12 => '取消订单',
            self::ORDER_STATUS_20 => '已支付',
            self::ORDER_STATUS_30 => '前往召唤人',
            self::ORDER_STATUS_40 => '准备送货',
            self::ORDER_STATUS_50 => '前往目的地',
            self::ORDER_STATUS_60 => '订单完成',
            self::ORDER_STATUS_70 => '订单取消并退款',
        ];
        return $status[$data['status']] ?? '';
    }
}