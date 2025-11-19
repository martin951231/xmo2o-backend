<?php

namespace app\shop\model\db;

use think\Model;

/**
 * 申请售后记录
 * @author: 张涛
 * @date: 2020/11/13
 * @package app\shop\model\db
 */
class ShopOrderRefund extends Model
{

    /**
     * 根据条件获取一条售后记录
     * @return mixed
     * @author: 张涛
     * @date: 2020/11/13
     */
    public function getOneRefund($where = [], $fields = '*')
    {
        return $this->where($where)->field($fields)->findOrEmpty()->toArray();
    }

    /**
     * 获取订单退款金额
     * @author: 张涛
     * @date: 2020/11/23
     */
    public function getAllRefundSuccessRecords($orderId)
    {
        return $this->where('order_id', $orderId)->where('status', 1)->select()->toArray();
    }

    /**
     * 获取订单退款金额
     * @author: 张涛
     * @date: 2020/11/23
     */
    public function getAllRefundingRecords($orderId)
    {
        return $this->where('order_id', $orderId)->whereIn('status', [0, 1, 3])->select()->toArray();
    }
}