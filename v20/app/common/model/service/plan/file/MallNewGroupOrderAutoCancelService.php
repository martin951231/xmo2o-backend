<?php

namespace app\common\model\service\plan\file;

use app\mall\model\db\MallNewGroupOrder;
use app\mall\model\service\MallOrderService;

class MallNewGroupOrderAutoCancelService
{
    /**
     * @param $order_id
     * @author zhumengqun
     * 自动同意取消拼团订单计划任务
     */
    public function runTask()
    {
        $team = new MallNewGroupOrder();
        $where_team = [
            ['m.status', '=', 0],
            ['s.status', '=', 1],
            ['m.end_time', '<', time()],
            ['od.pay_time', '>', 0],
            ['od.status', '=', 13],
        ];
        $ret = $team->getTeamOrderList($where_team);
        if (!empty($ret)) {
            foreach ($ret as $key => $val) {
                (new MallOrderService())->changeOrderStatus($val['order_id'], 51, '计划任务,拼团团队超时订单取消');
            }
        }
    }
}