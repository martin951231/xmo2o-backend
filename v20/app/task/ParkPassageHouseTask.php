<?php

/**
 * Author lkz
 * Date: 2023/5/27
 * Time: 15:49
 */

namespace app\task;

use app\community\model\service\ParkPassageService;
use think\Exception;
use yunwuxin\cron\Task;

class ParkPassageHouseTask extends Task
{


    public function configure ()
    {
        // 每30分钟执行
        $this->everyThirtyMinutes();
    }

    public function execute ()
    {
        try {
            (new ParkPassageService())->syncDeviceVolumeJob();
        }catch (Exception $e){

        }
    }

}