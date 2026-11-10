<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2021/9/16
 * Time: 13:37
 */

class plan_sub_auto_discard_prepaid_order extends plan_base
{
    public function runTask(){
        $model = '\app\community\model\service\HouseNewCashierService/discardPrepaidOrder';
        $this->keepThread();
        // $v20res = invoke_v20_service($model); // 关闭预缴账单30分钟未支付自动作废
        return true;
    }
}
