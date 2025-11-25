<?php

    namespace app\job\Car;
    use app\community\model\service\Car\CarFlowJobService;
    use think\Exception;
    use think\queue\Job as DoJob;

    class CarFlowPlateResultJob
    {
        public function fire(DoJob $job, $data)
        {
            if ($job->attempts() >= 1) {
                $job->delete();
            }
            try {
                $this->todoPlateResultJob($data);
            } catch (\Exception $e) {
                // todo 异常重启一次队列执行.
                $again = isset($data['again']) ? intval($data['again']) : 0;
                if ($again != 1) {
                    (new CarFlowJobService())->againToPlateResultTraits($data);
                }
            }
        }

        public function failed($data)
        {
        }
        
        public function todoPlateResultJob($data) {
            $jobType = isset($data['jobType']) && $data['jobType'] ? $data['jobType'] : '';
            switch ($jobType) {
                case 'plateResultLog':
                    (new CarFlowJobService())->plateResultLog($data);
                    break;
            }
            return true;
        }
    }