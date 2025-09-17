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
    use app\job\XiHanFaceDeviceJob;
    use think\facade\Cache;

    trait XiHanFaceDeviceTraits
    {
        /**
         * 队列名称
         * @return string
         */
        public function getXiHanFaceDeviceTraits()
        {
            return 'xihan-face-device-data-syn';
        }

        /**
         * 下发队列
         * @param $queueData
         * @param int $time 延迟时间 单位秒
         * @return mixed
         */
        public function traitXiHanFaceDevice($queueData, $time = 0, $redisExpire = 300) {
            $cacheTag  ='xiHan:FaceDeviceBatch';
            $cacheKey  = 'xiHan:FaceDeviceBatch_'. md5(\json_encode($queueData));
            $jobRedis  = Cache::store('redis')->get($cacheKey);
            if ($jobRedis) {
                $cacheJobKey   ='xiHan:FaceDeviceBatch_Redis' . $jobRedis;
                $doJob  = Cache::store('redis')->get($cacheJobKey);
                if (!$doJob) {
                    // todo 调用地方通过判断 -1 得知相同的队列正在等候执行
                    //return -1;
                }
            }
            if ($time > 0) {
                $job_id    = $this->xiHanFaceDeviceLaterToJob($queueData, $time);
            } else {
                $job_id    = $this->xiHanFaceDevicePushToJob($queueData);
            }
            if ($job_id) {
                Cache::store('redis')->tag($cacheTag)->set($cacheKey,$job_id, $redisExpire);
            }
            return $job_id;
        }
        /**
         * 公用的立即下发队列
         * @param $queueData
         * @return mixed
         */
        public function xiHanFaceDevicePushToJob($queueData) {
            return Queue::push(XiHanFaceDeviceJob::class, $queueData, $this->getXiHanFaceDeviceTraits());
        }

        /**
         * 公用的延迟下发队列
         * @param $queueData
         * @param int $time 延迟时间 单位秒
         * @return mixed
         */
        public function xiHanFaceDeviceLaterToJob($queueData, $time = 5) {
            return Queue::later($time,XiHanFaceDeviceJob::class, $queueData, $this->getXiHanFaceDeviceTraits());
        }
    }