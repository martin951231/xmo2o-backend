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

    use think\facade\Queue;
    use app\job\FaceDeviceDHCloudJob;
    use app\consts\DahuaConst;
    use app\consts\DeviceConst;
    use think\facade\Cache;

    trait FaceDeviceDHCloudTraits
    {
        /**
         * 大华云睿 队列名称
         * @return string
         */
        public function queueNameDHYunRuiDataToCloudTraits()
        {
            return 'dh-yun-rui-data-to-cloud';
        }

        /**
         * 同步物业这一层信息到设备方云平台
         * @param $queueData
         * @return mixed
         */
        public function traitCommonDHProperty($queueData) {
            if ($queueData && isset($queueData['thirdProtocol'])) {
                switch ($queueData['thirdProtocol']) {
                    case DeviceConst::DH_YUNRUI:
                        $queueData['jobType'] = 'groupToDeviceCloud';
                        return $this->DHCommonPushToJob($queueData);
                }
            }
            return true;
        }

        /**
         * 同步小区信息到设备方云平台
         * 字段参考下面对应设备类型处理传参
         * @param $queueData
         * thirdProtocol 协议区分
         * @return mixed
         */
        public function traitCommonDHCloudVillages($queueData)
        {
            if ($queueData && isset($queueData['thirdProtocol'])) {
                switch ($queueData['thirdProtocol']) {
                    case DeviceConst::DH_YUNRUI:
                        $queueData['jobType'] = 'villageToDeviceCloud';
                        return $this->DHCommonPushToJob($queueData);
                }
            }
            return true;
        }

        /**
         * 同步小区信息到设备方云平台
         * 字段参考下面对应设备类型处理传参
         * @param $queueData
         * thirdProtocol 协议区分
         * @return mixed
         */
        // todo 现有逻辑 大华云睿不使用队列同步 楼栋
        public function traitCommonDHCloudBuildings($queueData){
            if ($queueData && isset($queueData['thirdProtocol'])) {
                switch ($queueData['thirdProtocol']) {
                    case DeviceConst::DH_YUNRUI:
                        if (!isset($queueData['jobType']) || !$queueData['jobType']) {
                            $queueData['jobType'] = 'autoDHBuildDataBind';
                        }
                        return $this->DHCommonPushToJob($queueData);
                }
            }
            return true;
        }
        
        /**
         * 同步设备信息到设备方云平台
         * 字段参考下面对应设备类型处理传参
         * @param $queueData
         * thirdProtocol 协议区分
         * @return mixed
         */
        public function traitCommonDHCloudDevices($queueData)
        {
            if ($queueData && isset($queueData['thirdProtocol'])) {
                switch ($queueData['thirdProtocol']) {
                    case DeviceConst::DH_YUNRUI:
                        $queueData['jobType'] = 'addDeviceToCloud';
                        return $this->DHCommonPushToJob($queueData);
                }
            }
            return true;
        }

        /**
         * 同步人员立即执行
         * @param $queueData
         * @return mixed
         */
        public function traitCommonDHCloudPersons($queueData)
        {
            if ($queueData && isset($queueData['thirdProtocol'])) {
                switch ($queueData['thirdProtocol']) {
                    case DeviceConst::DH_YUNRUI:
                        if (!isset($queueData['jobType']) || !$queueData['jobType']) {
                            $queueData['jobType'] = 'addPersonToDhYunRuiCloud';
                        }
                        return $this->DHCommonPushToJob($queueData);
                }
            }
            return true;
        }



        /**
         * 同步人员立即执行
         * @param $queueData
         * @return mixed
         */
        public function traitCommonDHOpenDoorRecord($queueData)
        {
            if ($queueData && isset($queueData['thirdProtocol'])) {
                switch ($queueData['thirdProtocol']) {
                    case DeviceConst::DH_YUNRUI:
                        $queueData['jobType'] = 'getDhWonerDoorOpenRecord';
                        return $this->DHCommonPushToJob($queueData);
                }
            }
            return true;
        }


        /**
         * 获取设备一些实时更新的数据
         * @param $queueData
         * @return mixed
         */
        public function traitCommonDHDeviceStatus($queueData)
        {
            if ($queueData && isset($queueData['thirdProtocol'])) {
                switch ($queueData['thirdProtocol']) {
                    case DeviceConst::DH_YUNRUI:
                        if (!isset($queueData['jobType']) || !$queueData['jobType']) {
                            $queueData['jobType'] = 'getDeviceStatus';
                        }
                        return $this->DHCommonPushToJob($queueData);
                }
            }
            return true;
        }


        /**
         * 其他大华云睿公用方法
         * @param $queueData
         * @return mixed
         */
        public function traitCommonDHToJob($queueData)
        {
            if ($queueData && isset($queueData['thirdProtocol']) && isset($queueData['jobType'])) {
                switch ($queueData['thirdProtocol']) {
                    case DeviceConst::DH_YUNRUI:
                        return $this->DHCommonPushToJob($queueData);
                }
            }
            return true;
        }
        
        /**
         * 公用的立即下发队列
         * @param $queueData
         * @return mixed
         */
        protected function DHCommonPushToJob($queueData)
        {
            $cacheTag  = DahuaConst::DH_JOB_REDIS_TAG;
            $dataTrait = $queueData;
            if (isset($dataTrait['step_num'])) {
                unset($dataTrait['step_num']);
            }
            $cacheKey  = DahuaConst::DH_TRAITS_REDIS_KEY . md5(\json_encode($dataTrait));
            $jobRedis  = Cache::store('redis')->get($cacheKey);
            if ($jobRedis) {
                $cacheJobKey   = DahuaConst::DH_JOB_REDIS_KEY . $jobRedis;
                $doJob  = Cache::store('redis')->get($cacheJobKey);
                if (!$doJob) {
                    // todo 调用地方通过判断 -1 得知相同的队列正在等候执行
                    return -1;
                }
            }
            $job_id = Queue::push(FaceDeviceDHCloudJob::class,$queueData,$this->queueNameDHYunRuiDataToCloudTraits());
            if ($job_id) {
                Cache::store('redis')->tag($cacheTag)->set($cacheKey,$job_id, 600);
            }
            return  $job_id;
        }


        /**
         * @param int $time
         * 公用的延迟下发队列
         * @param $queueData
         * @param int $time
         * @return mixed
         */
        public function traitCommonDHLaterToJob($queueData, $time = 5)
        {
            if ($queueData && isset($queueData['thirdProtocol']) && isset($queueData['jobType'])) {
                switch ($queueData['thirdProtocol']) {
                    case DeviceConst::DH_YUNRUI:
                        return $this->DHCommonLaterToJob($queueData, $time);
                }
            }
            return true;
        }

        /**
         * 公用的延迟下发队列
         * @param $queueData
         * @param int $time
         * @return mixed
         */
        protected function DHCommonLaterToJob($queueData, $time = 5)
        {
            $cacheTag  = DahuaConst::DH_JOB_REDIS_TAG;
            $dataTrait = $queueData;
            if (isset($dataTrait['step_num'])) {
                unset($dataTrait['step_num']);
            }
            $cacheKey  = DahuaConst::DH_TRAITS_REDIS_KEY . md5(\json_encode($dataTrait));
            $jobRedis  = Cache::store('redis')->get($cacheKey);
            if ($jobRedis) {
                $cacheJobKey   = DahuaConst::DH_JOB_REDIS_KEY . $jobRedis;
                $doJob  = Cache::store('redis')->get($cacheJobKey);
                if (!$doJob) {
                    // todo 调用地方通过判断 -1 得知相同的队列正在等候执行
                    return -1;
                }
            }
            $job_id = Queue::later($time, FaceDeviceDHCloudJob::class, $queueData, $this->queueNameDHYunRuiDataToCloudTraits());
            if ($job_id) {
                Cache::store('redis')->tag($cacheTag)->set($cacheKey,$job_id, $time + 600);
            }
            return $job_id;
        }
    }