<?php

/**
 * 预订单提前五分钟提醒
 * @author: 张涛
 * @date: 2020/11/17
 */
class plan_booking_order_remind extends plan_base
{

    public function runTask()
    {
        //提前5分钟通知
        $second = 300;
        $tm = time();
        $where = [
            'is_handle' => 0,
            'order_status' => 1,
            'status' => ['lt', 2],
            'is_booking' => 1,
            'is_remind' => 0,
            'expect_take_time' => [['gt', $tm - 86400], ['lt', $tm + $second]]
        ];

        $orderMod = D('Shop_order');
        $staffMod = new Merchant_store_staffModel();
        $printHaddle = new PrintHaddle();

        $orders = $orderMod->where($where)->order('order_id asc')->limit(10)->select();
        foreach ($orders as $o) {
            $orderMod->where(['order_id' => $o['order_id']])->save(['is_remind' => 1]);
            $staffMod->sendBookOrderRemind($o['store_id'], $o);
            $printHaddle->book_order_printit($o['order_id'], 'shop_order', 2, 1);
        }
        return true;
    }
}