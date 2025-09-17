<?php


class plan_sub_pile_notice extends plan_base
{
    public function runTask(){
        $model = '\app\community\model\service\PileUserService/PileCommand';
        $this->keepThread();
        $v20res = invoke_v20_service($model);
        return true;
    }
}