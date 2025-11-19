<?php

namespace app\job\Car;

use app\community\model\service\Car\ParkCarToJobService;
use app\consts\Car\CarFlowConst;
use think\queue\Job as DoJob;

class CarFlowHandleJob
{
    public function fire(DoJob $job, $data)
    {
        if ($job->attempts() >= 1) {
            $job->delete();
        }
        try {
            $this->todoClearJob($data);
        } catch (\Exception $e) {
            fdump_api(['msg' => '队列执行错误', 'err' => $e->getMessage(), 'line' => $e->getLine(), 'code' => $e->getCode(), 'file' => $e->getFile()], 'PlateResultTraits/carFlowClearLog', CarFlowConst::CAR_FLOW_TRAITS_LOG_APPEND);
        }
    }
    
    public function failed($data)
    {
    }

    public function todoClearJob($data)
    {
        $jobType = isset($data['jobType']) && $data['jobType'] ? $data['jobType'] : '';
        switch ($jobType) {
            case 'resettingUseParkingPosition':
                (new ParkCarToJobService())->resettingUseParkingPosition($data);
                break;
            case 'handleUseParkingPosition':
                (new ParkCarToJobService())->handleUseParkingPosition($data);
                break;
            case 'update_park_passage':
                (new ParkCarToJobService())->updateParkPassage($data);
                break;
            case 'handleCarPassImageRecord':
                (new ParkCarToJobService())->handleCarPassImageRecord($data);
                break;
            case 'handleOutToInPark':
                (new ParkCarToJobService())->handleOutToInPark($data);
                break;
        }
        return true;
    }

}