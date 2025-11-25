<?php

namespace app\job\Car;

use app\community\model\service\Car\CarFlowJobService;
use app\community\model\service\Car\CarFlowShowScreenService;
use app\consts\Car\CarFlowConst;
use think\Exception;
use think\queue\Job as DoJob;

class CarFlowClearJob
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
            case 'clearOverShowScreen':
                (new CarFlowShowScreenService())->clearOverShowScreen($data);
                break;
            case 'clearOverParkOpen':
                (new CarFlowJobService())->clearOverParkOpen($data);
                break;
            case 'clearOverPayRecord':
                (new CarFlowJobService())->clearOverPayRecord($data);
                break;
            case 'clearOverParkSerialLog':
                (new CarFlowJobService())->clearOverParkSerialLog($data);
                break;
        }
        return true;
    }
}