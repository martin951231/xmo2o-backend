<?php
/**
 * 团体票订单过期自动退款
 */

namespace app\common\model\service\plan\file;

use app\life_tools\model\db\LifeToolsGroupOrder;
use app\life_tools\model\service\LifeToolsOrderService;

class LifeToolsGroupOrderAutoRefundService
{

    public function runTask()
    { 
        $LifeToolsOrderService = new LifeToolsOrderService();
        $LifeToolsGroupOrderModel = new LifeToolsGroupOrder();

        $condition = [];
        $condition[] = ['o.is_group', '=', 1];
        $condition[] = ['o.order_status', '=', 20];
        $condition[] = ['o.paid', '=', 2];
        $condition[] = ['go.group_status', '=', 0];
        $condition[] = ['go.submit_audit_time', '<>', 0];

        $order = $LifeToolsGroupOrderModel->getDataOrNumByCondition($condition);
        $time = time();
        foreach ($order as $key => $item) {
            if($item['submit_audit_time'] <= $time){
                $LifeToolsGroupOrderModel->where('id', $item['group_order_id'])->update(['group_status' => 40]);
                $LifeToolsOrderService->supplyRefund(['order_id' => $item['order_id'], 'reason' => '到期未提交审核自动退款'], '到期未提交审核自动退款', 0); //模拟用户申请退款
                $LifeToolsOrderService->agreeRefund([$item['order_id']], '到期未提交审核自动退款'); //同意退款
            }
        }
        return true;
    }

}