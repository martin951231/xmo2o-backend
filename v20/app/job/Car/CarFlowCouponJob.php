<?php

namespace app\job\Car;

use app\community\model\service\Car\CarFlowJobService;
use think\Exception;
use think\queue\Job as DoJob;

class CarFlowCouponJob
{
    public function fire(DoJob $job, $data)
    {
        if ($job->attempts() >= 1) {
            $job->delete();
        }
        try {
            $this->todoCouponJob($data);
        } catch (\Exception $e) {
            // todo 异常重启一次队列执行.
            $again = isset($data['again']) ? intval($data['again']) : 0;
            if ($again != 1) {
                (new CarFlowJobService())->againToCouponTraits($data);
            }
        }
    }

    public function failed($data)
    {
    }

    public function todoCouponJob($data)
    {
        $jobType = isset($data['jobType']) && $data['jobType'] ? $data['jobType'] : '';
        switch ($jobType) {
            case 'updateCouponToNotUsed':
                (new CarFlowJobService())->updateCouponToNotUsed($data);
                break;
            case 'updateCouponToUsed':
                (new CarFlowJobService())->updateCouponToUsed($data);
                break;
        }
        return true;
    }
}