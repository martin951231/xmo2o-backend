<?php

/**
 * 企业微信 违规员工监听提醒
 */
class plan_house_violation_staff_remind extends plan_base
{
    //主计划任务 需要跑入的日期
    public function runTask()
    {
        //todo 违规员工发送
        invoke_v20_service('\app\community\model\service\ViolationRemindService/staffRemind');

        return true;
    }

}