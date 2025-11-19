<?php
/**
 * @author : liukezhu
 * @date : 2022/10/25
 */

namespace app\job;

use app\community\model\service\HouseNewMeterService;
use think\Exception;
use think\queue\Job as DoJob;

class MeterDirectorNoticeHouseJob
{


    public function fire(DoJob $job , $data)
    {
        if ($job->attempts() >= 1){
            $job->delete();
        }
        try {
            (new HouseNewMeterService())->meterDirectorSendNotice($data['id']);

        }catch (Exception $e){}
    }

    public function failed($data)
    {
    }
}