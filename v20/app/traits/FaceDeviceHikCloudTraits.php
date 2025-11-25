<?php
	/**
	 * +----------------------------------------------------------------------
	 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
	 * +----------------------------------------------------------------------
	 * @author    合肥快鲸科技有限公司
	 * @copyright 合肥快鲸科技有限公司
	 * @link      https://www.kuaijing.com.cn
	 * @Desc      人脸设备同步
	 */

	namespace app\traits;

    use app\consts\HikConst;
    use app\consts\DeviceConst;
    use think\facade\Queue;
    use app\job\FaceDeviceHikCloudJob;
    use app\community\model\service\FaceDeviceService;
    use think\facade\Cache;

    trait FaceDeviceHikCloudTraits
    {
        /**
         * 海康云眸内部应用 队列名称
         * @return string
         */
        public function queueNameHikDataToCloudTraits()
        {
            return 'hik-nei-bu-data-to-cloud';
        }

        /**
         * 同步小区信息到设备方云平台
         * 字段参考下面对应设备类型处理传参
         * @param $queueData
         * thirdProtocol 协议区分
         * @param int $time
         * @return mixed
         */
        public function traitCommonHikCloudVillages($queueData)
        {
            if ($queueData && isset($queueData['thirdProtocol'])) {
                switch ($queueData['thirdProtocol']) {
                    case DeviceConst::HIK_YUNMO_NEIBU_SHEQU:
                        $queueData['jobType'] = 'villageToDeviceCloud';
                        return $this->HiKCommonPushToJob($queueData);
                }
            }
        }
        
        public function traitCommonHikCloudBuildings($queueData) {
            if ($queueData && isset($queueData['thirdProtocol'])) {
                switch ($queueData['thirdProtocol']) {
                    case DeviceConst::HIK_YUNMO_NEIBU_SHEQU:
                        $queueData['jobType'] = 'buildToDeviceCloud';
                        return $this->HiKCommonPushToJob($queueData);
                }
            }
        }
        
        public function traitCommonHikCloudUnits($queueData) {
            if ($queueData && isset($queueData['thirdProtocol'])) {
                switch ($queueData['thirdProtocol']) {
                    case DeviceConst::HIK_YUNMO_NEIBU_SHEQU:
                        if (!isset($queueData['jobType']) || !$queueData['jobType']) {
                            $queueData['jobType'] = 'floorToDeviceBox';
                        }
                        return $this->HiKCommonPushToJob($queueData);
                }
            }
        }

        /**
         * 同步设备信息到设备方云平台
         * 字段参考下面对应设备类型处理传参
         * @param $queueData
         * thirdProtocol 协议区分
         * @param int $time
         * @return mixed
         */
        public function traitCommonHikCloudDevices($queueData)
        {
            if ($queueData && isset($queueData['thirdProtocol'])) {
                switch ($queueData['thirdProtocol']) {
                    case DeviceConst::HIK_YUNMO_NEIBU_SHEQU:
                        $queueData['jobType'] = 'addDeviceToCloud';
                        return $this->HiKCommonPushToJob($queueData);
                }
            }
        }

        /**
         * 其他海康公用方法
         * @param $queueData
         * @return mixed
         */
        public function traitCommonHikToJob($queueData)
        {
            if ($queueData && isset($queueData['thirdProtocol']) && isset($queueData['jobType'])) {
                switch ($queueData['thirdProtocol']) {
                    case DeviceConst::HIK_YUNMO_NEIBU_SHEQU:
                        return $this->HiKCommonPushToJob($queueData);
                }
            }
            return true;
        }



        /**
         * 公用下发队列
         * 字段参考下面对应设备类型处理传参
         * @param $queueData
         * thirdProtocol 协议区分
         * @param int $time
         * @return mixed
         */
        public function traitCommonHikCloud($queueData)
        {
            if ($queueData && isset($queueData['thirdProtocol'])) {
                if (!isset($queueData['jobType']) || !$queueData['jobType']) {
                    return false;
                }
                switch ($queueData['thirdProtocol']) {
                    case DeviceConst::HIK_YUNMO_NEIBU_SHEQU:
                    case DeviceConst::HIK_YUNMO_NEIBU_6000C:
                        return $this->HiKCommonPushToJob($queueData);
                }
            }
            return true;
        }
        


        /**
         * 其他海康延迟下发队列公用方法
         * @param $queueData
         * @param int $time 延迟时间 单位秒
         * @return mixed
         */
        public function traitCommonHikLaterToJob($queueData, $time = 5)
        {
            if ($queueData && isset($queueData['thirdProtocol']) && isset($queueData['jobType'])) {
                switch ($queueData['thirdProtocol']) {
                    case DeviceConst::HIK_YUNMO_NEIBU_SHEQU:
                        return $this->HiKCommonLaterToJob($queueData, $time);
                }
            }
            return true;
        }
        /**
         * 公用的立即下发队列
         * @param $queueData
         * @return mixed
         */
        protected function HiKCommonPushToJob($queueData)
        {
            /*
            $cacheTag  = HikConst::HIK_JOB_REDIS_TAG;
            $cacheKey  = HikConst::HIK_TRAITS_REDIS_KEY . md5(\json_encode($queueData));
            $jobRedis  = Cache::store('redis')->get($cacheKey);
            if (0 && $jobRedis) {
                $cacheJobKey   = HikConst::HIK_JOB_REDIS_KEY . $jobRedis;
                $doJob  = Cache::store('redis')->get($cacheJobKey);
                if (!$doJob) {
                    // todo 调用地方通过判断 -1 得知相同的队列正在等候执行
                    return -1;
                }
            }
            */
            $job_id = Queue::push(FaceDeviceHikCloudJob::class, $queueData, $this->queueNameHikDataToCloudTraits());
            /*
            if ($job_id) {
                Cache::store('redis')->tag($cacheTag)->set($cacheKey,$job_id, 600);
            }
            */
            return $job_id;
        }

        /**
         * 公用的延迟下发队列
         * @param $queueData
         * @param int $time 延迟时间 单位秒
         * @return mixed
         */
        protected function HiKCommonLaterToJob($queueData, $time = 5)
        {
            $cacheTag  = HikConst::HIK_JOB_REDIS_TAG;
            $dataTrait = $queueData;
            if (isset($dataTrait['step_num'])) {
                unset($dataTrait['step_num']);
            }
            $cacheKey  = HikConst::HIK_TRAITS_REDIS_KEY . md5(\json_encode($dataTrait));
            $jobRedis  = Cache::store('redis')->get($cacheKey);
            if ($jobRedis) {
                $cacheJobKey   = HikConst::HIK_JOB_REDIS_KEY . $jobRedis;
                $doJob  = Cache::store('redis')->get($cacheJobKey);
                if (!$doJob) {
                    // todo 调用地方通过判断 -1 得知相同的队列正在等候执行
                    return -1;
                }
            }
            $job_id = Queue::later($time,FaceDeviceHikCloudJob::class, $queueData, $this->queueNameHikDataToCloudTraits());
            if ($job_id) {
                Cache::store('redis')->tag($cacheTag)->set($cacheKey,$job_id, $time + 100);
            }
            return $job_id;
        }
    }