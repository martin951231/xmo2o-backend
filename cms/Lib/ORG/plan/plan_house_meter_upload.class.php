<?php
/**
 * Created by PigCMS.
 * User: zhubaodi.
 * Date: 2021/04/21.
 * Time: 10:43.
 */

/**
 * Class plan_mer_revenue_statistics 商家营业统计->主计划任务
 */
class plan_house_meter_upload extends plan_base
{
    //主计划任务 需要跑入的日期
    public function runTask()
    {
        invoke_v20_service('\app\community\model\service\HouseMeterRedisService/upload_data');
        return true;

    }

}