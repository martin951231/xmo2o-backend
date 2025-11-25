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
class plan_sub_house_meter_reading extends plan_base
{
    //子计划任务
    public function runTask($param)
    {

        fdump_api($param,'sub_house_meter_reading');
        invoke_v20_service('\app\community\model\service\HouseMeterRedisService/download_meter_reading', $param);
        return true;
    }

}