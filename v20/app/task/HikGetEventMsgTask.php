<?php


namespace app\task;

use app\community\model\service\Device\DeviceHkNeiBuHandleService;
use app\community\model\service\Device\HikInfoVisionICommunityService;
use think\Exception;
use yunwuxin\cron\Task;


class HikGetEventMsgTask extends Task
{

    public function configure ()
    {
        //$this->everyFiveMinutes();
        $this->hourlyAt('*/2'); //每2分钟执行一次
    }

    public function execute ()
    {
        try {
            (new DeviceHkNeiBuHandleService())->getEventMsg();
            (new HikInfoVisionICommunityService())->getDoorEvents();
        }catch (Exception $e){
            
        }

    }
}