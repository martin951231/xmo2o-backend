<?php
/**
 * 团购订单退款
 * Created by PhpStorm.
 * Author: 衡婷妹
 * Date Time: 2020/11/19 17:01
 */

namespace app\group\model\service\order;

class RefundService
{

    public function __construct()
    {
    }

    /**
     * 超时自动取消订单
     * @param $param array 数据
     * @return array
     */
    public function autoCancelOrder($param){
        //订单id
        $orderId = $param['order_id'] ?? 0;

        if(empty($orderId)){
            throw new \think\Exception(L_("缺少参数"), 1003);
        }

        // 订单详情
        $orderDetail = (new GroupOrderService())->getOrderDetail($param);

        if(empty($orderDetail)){
            throw new \think\Exception(L_("订单不存在"), 1003);
        }

        if($orderDetail['status'] == 3 || $orderDetail['status'] == 4){
            throw new \think\Exception(L_("该订单已取消，不可重复操作"), 1003);
        }

        if($orderDetail['paid'] == 1){
            throw new \think\Exception(L_("该订单已支付不可以取消"), 1003);
        }

        $where = [
            'order_id' => $orderId
        ];

        $data = [
            'status' => 4,
            'cancel_reason' => $param['cancel_reason'] ?? L_('超时自动取消')
        ];

        $res = (new GroupOrderService())->updateThis($where, $data);
        if($res === false){
            throw new \think\Exception(L_("取消失败"), 1003);
        }

        // 回滚库存
        (new GroupOrderService())->rollBackStock($orderId);
        return true;
    }
}