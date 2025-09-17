<?php


namespace app\common\model\service\plan\file;

use app\life_tools\model\db\LifeToolsDistributionOrder;

class LifeToolsDistributionOrderToStatementService
{
    /**
     * 分销清单转为待结算
     */
    public function runTask()
    {
        $LifeToolsDistributionOrder = (new LifeToolsDistributionOrder());
        $field = ['a.id','o.ticket_time', 's.update_status_time'];
        $condition = [];
        $condition[] = ['o.order_status', 'in', [30, 40, 70]];
        $condition[] = ['o.paid', '=', 2];
        $condition[] = ['a.status', '=', -2];
        $orderList = $LifeToolsDistributionOrder->getOrderList($condition, $field)->toArray();
        $ids = [];
        foreach($orderList as $order)
        {
            $ticket_time = strtotime($order['ticket_time'] . '23:59:59');
            if($ticket_time + ($order['update_status_time'] * 86400) <= time()){
            }
            $ids[] = $order['id'];
        }
        if(count($ids)){
            $LifeToolsDistributionOrder->where('id', 'in', $ids)->update([
                'status' => 0,
                'update_time'   =>  time()
            ]);
        }
        return true;
    }
}