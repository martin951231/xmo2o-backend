<?php

/**
 * 预订单自动处理到进行中
 * @author: 张涛
 * @date: 2020/11/17
 */
class plan_booking_order_auto_handle extends plan_base
{

    public function runTask()
    {
        $tm = time();
        $where = [
            'is_handle' => 0,
            'order_status' => 1,
            'status' => ['lt', 2],
            'is_booking' => 1,
            'expect_take_time' => [['gt', $tm - 86400], ['lt', $tm]]
        ];

        $orderMod = D('Shop_order');
        $storeMod = D('Merchant_store');
        $deliverSupplyMod = D('Deliver_supply');
        $shopOrderDetailMod = D('Shop_order_detail');

        $orders = $orderMod->where($where)->field('order_id,store_id')->limit(10)->select();

        foreach ($orders as $o) {
            $orderMod->where(['order_id' => $o['order_id']])->save(['is_handle' => 1]);

            $isNotWriteWeight = $shopOrderDetailMod->where(['order_id' => $o['order_id'], 'sell_type' => 2, 'real_weight' => 0])->find();
            if (empty($isNotWriteWeight)) {
                $store = $storeMod->where(['store_id' => $o['store_id']])->find();
                $rs = $deliverSupplyMod->saveOrder($o['order_id'], $store);
                if (isset($rs['error_code']) && $rs['error_code']) {
                    fdump_sql(['order_id' => $o['order_id'], 'error_msg' => $rs['msg']], 'booking_order_auto_handle_fail');
                }
            }
        }
        return true;
    }
}