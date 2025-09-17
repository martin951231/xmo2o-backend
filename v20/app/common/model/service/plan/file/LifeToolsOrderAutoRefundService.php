<?php
/**
 * 体育健身订单申请退款超时自动退款
 */

namespace app\common\model\service\plan\file;

use app\life_tools\model\db\LifeToolsOrder;
use app\life_tools\model\db\LifeToolsOrderBindSportsActivity;
use app\life_tools\model\service\LifeToolsOrderService;

class LifeToolsOrderAutoRefundService
{

    public function runTask()
    {
        $LifeToolsOrderService = new LifeToolsOrderService();
        $LifeToolsOrderBindSportsActivity = new LifeToolsOrderBindSportsActivity();
    	$timeout = time() - cfg('life_tools_sports_order_refund_time') * 24 * 3600;
    	$where   = [
            ['o.order_status', '=', 45],
            ['o.reply_refund_time', '<', $timeout],
            ['a.type', 'in', ['stadium', 'course']],
    	];
        // $data = $LifeToolsOrderService->getSome($where, 'order_id', 'order_id asc', 1, 20);
        $data = (new LifeToolsOrder())->getList($where);
        if (!empty($data)) {
    		foreach ($data as $value) {
	    		try {
                    $LifeToolsOrderService->agreeRefund([$value['order_id']]);
                    //运动约战
                    $activityData = $LifeToolsOrderBindSportsActivity->getOne([['order_id', '=', $value['order_id']], ['group_status', '<=', 20]]);
                    if (!empty($activityData)) {
                        $LifeToolsOrderBindSportsActivity->updateThis(['pigcms_id' => $activityData['pigcms_id']], ['group_status' => $activityData['group_status'] == 20 ? 40 : 30]);
                    }
                } catch (\Exception $e) {
                    fdump("订单ID：" . $value['order_id'] . $e->getMessage(), "LifeToolsOrderAutoRefundService", 1);
                }
	    	}
    	}
        return true;
    }

}