<?php
/**
 * @author : liukezhu
 * @date : 2022/10/25
 */

namespace app\task;

use app\community\model\service\HouseNewMeterService;
use think\Exception;
use yunwuxin\cron\Task;


class MeterDirectorNoticeHouseTask extends Task
{

    public function configure ()
    {
        // 每分钟执行一次
        $this->everyMinute();
    }

    public function execute ()
    {
        try {
            (new HouseNewMeterService())->meterDirectorTriggerNotice();
        }catch (Exception $e){

        }
    }


}