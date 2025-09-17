<?php

namespace app\job\Car;

use app\community\model\service\Car\CarFlowJobService;
use think\Exception;
use think\queue\Job as DoJob;

class CarFlowInJob
{
    public function fire(DoJob $job, $data)
    {
        if ($job->attempts() >= 1) {
            $job->delete();
        }
        try {
            $this->todoInJob($data);
        } catch (\Exception $e) {
            // todo 异常重启一次队列执行.
            $again = isset($data['again']) ? intval($data['again']) : 0;
            if ($again != 1) {
                (new CarFlowJobService())->againToInTraits($data);
            }
        }
    }

    public function failed($data)
    {
    }

    public function todoInJob($data)
    {
        $jobType = isset($data['jobType']) && $data['jobType'] ? $data['jobType'] : '';
        switch ($jobType) {
            case 'addInPark':
                (new CarFlowJobService())->addInPark($data);
                break;
            case 'addCarAccessRecord':
                (new CarFlowJobService())->addCarAccessRecord($data);
                break;
        }
        return true;
    }
}