<?php
/**
 * 计划任务 待支付预订单 15分钟 超时取消
 * Author: hengtingmei
 * Date Time: 2020/07/01 17:48
 */

namespace app\common\model\service\plan;
use app\foodshop\model\service\order\DiningOrderService;
class PlanService {
    /**
	 * 计划任务执行
	 * @param  string  $param 参数
	 */
 
	public function runTask(){
        // v20 餐饮
        $where = [
            ['is_book_pay', 'neq', 1],
            ['book_pay_time', '=', 0],
            ['status', '=', 2],
            ['order_from', '=', 0],
            ['create_time', '<', time()-$time],
        ];
        $diningList = (new DiningOrderService())->getOrderListByCondition($where, '', 0);
        foreach ($dininglist as $key => $value) {
            (new DiningOrderService())->PlanCancelOrder($value);
        }
        
		return true;
	}
}