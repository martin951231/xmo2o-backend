<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2021/9/14
 * Time: 10:44
 */

class plan_automatic_late_payment extends plan_base
{
    public function runTask(){
        $model = '\app\community\model\service\HouseNewCashierService/automaticLatePayment';
        $this->keepThread();
        $v20res = invoke_v20_service($model);
        return true;
    }
}