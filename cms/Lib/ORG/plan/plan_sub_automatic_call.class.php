<?php


class plan_sub_automatic_call extends plan_base
{
    public function runTask($param){
        $model_call = '\app\community\model\service\HouseNewCashierService/call';
        $this->keepThread();
        $v20res = invoke_v20_service($model_call,['v'=>$param]);
        return true;
    }
}