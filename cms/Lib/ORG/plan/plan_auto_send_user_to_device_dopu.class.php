<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2021/9/14
 * Time: 10:44
 */

class plan_auto_send_user_to_device_dopu extends plan_base
{
    public function runTask($param){
        //删除计划任务
        if($param['plan_task_id']){
            $this->dropPlan($param['plan_task_id']);
        }
        $model = '\app\community\model\service\CloudIntercomService/sendThirdUserToDeviceDeal';
        $this->keepThread();
        invoke_v20_service($model,[$param['village_id'],$param['deviceSn'],$param['thirdUser']]);
        return true;
    }
}