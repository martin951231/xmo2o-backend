<?php
/**
 * 计划任务 团购订单 待支付预订单 15分钟 超时取消
 * Author: hengtingmei
 * Date Time: 2020/11/24 16:10
 */

namespace app\common\model\service\plan\file;
use app\group\model\service\order\GroupOrderService;
use app\group\model\service\order\RefundService;

class GroupCombineOrderCancleService {
    /**
	 * 计划任务执行
	 * @param  string  $param 参数
	 */
 
	public function runTask(){
        $time = 900;
        // v20 餐饮
        $where = [
            ['paid', '=', 0],
            ['status', '=', 0],
            ['add_time', '<', time()-$time],
        ];
        $orderList = (new GroupOrderService())->getSome($where);
        foreach ($orderList as $key => $value) {
            $value['cancel_reason'] = L_('超时自动取消');
            (new RefundService())->autoCancelOrder($value);
        }
		return true;
	}
}