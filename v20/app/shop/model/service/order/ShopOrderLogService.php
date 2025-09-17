<?php

namespace app\shop\model\service\order;

use app\common\model\db\ShopOrderLog;

/**
 * 外卖订单日志
 * @author: 张涛
 * @date: 2020/11/9
 */
class ShopOrderLogService
{

    /**
     * 获取一条完成订单时间
     * @author：汪晨
     * @date：2021/3/16
     */
    public function getOneLog($where)
    {
        $logMod = new ShopOrderLog();
        return $logMod->where($where)->find();
    }


    /**
     * 日志状态描述
     * @author: 张涛
     * @date: 2020/11/9
     */
    public function status($is_pick_in_store = 0)
    {
        $pick_status = L_('店员验证消费');
        if ($is_pick_in_store == 3) {
            $pick_status = L_('店员已发货');
        }
        return array(
            0 => array('txt' => L_("订单生成成功"), 'img' => 3),
            1 => array('txt' => L_("订单支付成功"), 'img' => 3),
            2 => array('txt' => L_("店员接单"), 'img' => 1),
            3 => array('txt' => L_("骑手接单"), 'img' => 4),
            4 => array('txt' => L_("骑手已到店"), 'img' => 5),
            5 => array('txt' => L_("骑手配送中"), 'img' => 5),
            6 => array('txt' => L_("配送结束"), 'img' => 5),
            7 => array('txt' => $pick_status, 'img' => 4),
            8 => array('txt' => L_("完成评论"), 'img' => 3),
            9 => array('txt' => L_("已完成退款"), 'img' => 3),
            10 => array('txt' => L_("已取消订单"), 'img' => 3),
            11 => array('txt' => L_("商品分配自提点"), 'img' => 5),
            12 => array('txt' => L_("商家发货到自提点"), 'img' => 5),
            13 => array('txt' => L_("自提点已接货"), 'img' => 5),
            14 => array('txt' => L_("自提点已发货"), 'img' => 5),
            15 => array('txt' => L_("您在自提点取货"), 'img' => 3),
            30 => array('txt' => L_("店员为您修改了价格"), 'img' => 3),
            31 => array('txt' => L_("骑手放弃配送"), 'img' => 4),
            34 => array('txt' => L_("系统配送失败"), 'img' => 4),
            35 => array('txt' => L_("骑手接单"), 'img' => 4),
        );
    }

    /**
     * 获取订单状态日志
     * @param $orderId
     * @author: 张涛
     * @date: 2020/11/9
     */
    public function getLogByOrderId($orderId, $isPickInStore, $timeFormate = 'Y-m-d H:i:s')
    {
        $logMod = new ShopOrderLog();
        $logs = $logMod->where('order_id', $orderId)->order('id', 'desc')->select()->toArray();
        $status = $this->status($isPickInStore);
        $rs = [];
        foreach ($logs as $v) {
            $rs[] = [
                'time' => date($timeFormate, $v['dateline']),
                'status' => isset($status[$v['status']]) ? $status[$v['status']]['txt'] : '--'
            ];
        }
        return $rs;
    }
}