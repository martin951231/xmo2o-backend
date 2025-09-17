<?php


/**
 * 企业微信 违规群聊监听提醒
 */
class plan_house_violation_group_remind extends plan_base
{
    //主计划任务 需要跑入的日期
    public function runTask()
    {
        //todo 违规群聊发送
        invoke_v20_service('\app\community\model\service\ViolationRemindService/groupRemind');

        return true;
    }

}