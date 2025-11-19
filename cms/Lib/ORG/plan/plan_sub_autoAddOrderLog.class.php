<?php
/**
 * Created by PigCMS.
 * User: zhubaodi.
 * Date: 2021/04/21.
 * Time: 10:43.
 */

/**
 * 同步物业服务时间
 */
class plan_sub_autoAddOrderLog extends plan_base
{
    //子计划任务
    public function runTask($param)
    {

        fdump_api($param,'sub_autoAddOrderLog');
        invoke_v20_service('\app\community\model\service\HouseNewCashierService/autoAddOrderLog', ['data'=>$param]);
        return true;
    }

}