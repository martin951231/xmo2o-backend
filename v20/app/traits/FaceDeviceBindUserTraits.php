<?php
	/**
	 * +----------------------------------------------------------------------
	 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
	 * +----------------------------------------------------------------------
	 * @author    合肥快鲸科技有限公司
	 * @copyright 合肥快鲸科技有限公司
	 * @link      https://www.kuaijing.com.cn
	 * @Desc      人脸设备绑定用户
	 */

	namespace app\traits;

    use app\consts\DeviceConst;
    use think\facade\Queue;
    use app\job\FaceDeviceBindUserJob;
    use app\consts\DahuaConst;
    use think\facade\Cache;

    trait FaceDeviceBindUserTraits
    {
        /**
         * 队列名称
         * @return string
         */
        public function queueNameFaceDeviceBindUserTraits()
        {
            return 'face-device-bind-user';
        }
        
        /**
         * 公用的立即下发队列
         * @param $queueData
         * @return mixed
         */
        protected function BindCommonPushToJob($queueData)
        {
            fdump_api($queueData,'faceDeviceBindUser/test',1);
            $cacheTag  = DeviceConst::FACE_DEVICE_USER_JOB_REDIS_TAG;
            $dataTrait = $queueData;
            $cacheKey  = DeviceConst::FACE_DEVICE_USER_TRAITS_REDIS_KEY . md5(\json_encode($dataTrait));
            $jobRedis  = Cache::store('redis')->get($cacheKey);
            if ($jobRedis) {
                $cacheJobKey   = DahuaConst::DH_JOB_REDIS_KEY . $jobRedis;
                $doJob  = Cache::store('redis')->get($cacheJobKey);
                if (!$doJob) {
                    // todo 调用地方通过判断 -1 得知相同的队列正在等候执行
                    return -1;
                }
            }
            $job_id = Queue::push(FaceDeviceBindUserJob::class,$queueData,$this->queueNameFaceDeviceBindUserTraits());
            if ($job_id) {
                Cache::store('redis')->tag($cacheTag)->set($cacheKey,$job_id, 600);
            }
            fdump_api($job_id,'faceDeviceBindUser/test',1);
            return  $job_id;
        }

        /**
         * 公用的延迟下发队列
         * @param $queueData
         * @param int $time
         * @return mixed
         */
        protected function BindCommonLaterToJob($queueData, $time = 5)
        {
            $cacheTag  = DeviceConst::FACE_DEVICE_USER_JOB_REDIS_TAG;
            $dataTrait = $queueData;
            if (isset($dataTrait['step_num'])) {
                unset($dataTrait['step_num']);
            }
            $cacheKey  = DeviceConst::FACE_DEVICE_USER_TRAITS_REDIS_KEY . md5(\json_encode($dataTrait));
            $jobRedis  = Cache::store('redis')->get($cacheKey);
            if ($jobRedis) {
                $cacheJobKey   = DahuaConst::DH_JOB_REDIS_KEY . $jobRedis;
                $doJob  = Cache::store('redis')->get($cacheJobKey);
                if (!$doJob) {
                    // todo 调用地方通过判断 -1 得知相同的队列正在等候执行
                    return -1;
                }
            }
            $job_id = Queue::later($time, FaceDeviceBindUserJob::class, $queueData, $this->queueNameFaceDeviceBindUserTraits());
            if ($job_id) {
                Cache::store('redis')->tag($cacheTag)->set($cacheKey,$job_id, $time + 600);
            }
            return $job_id;
        }
    }