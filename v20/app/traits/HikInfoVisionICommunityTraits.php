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
    use app\job\HikInfoVisionICommunityJob;
    use think\facade\Cache;

    trait HikInfoVisionICommunityTraits
    {
        /**
         * 队列名称
         * @return string
         */
        public function getHikInfoVisionCommunityRegionsHousesTraits()
        {
            return 'hik-info-vision-i-community-regions-houses';
        }

        /**
         * 下发队列
         * @param $queueData
         * @param int $time 延迟时间 单位秒
         * @return mixed
         */
        public function traitCommonHikInfoVisionCommunity($queueData, $time = 0, $redisExpire = 120) {
            $cacheTag  = HikConst::HIK_COMMUNITY_JOB_REDIS_TAG;
            $cacheKey  = HikConst::HIK_COMMUNITY_TRAITS_REDIS_KEY . md5(\json_encode($queueData));
            $jobRedis  = Cache::store('redis')->get($cacheKey);
            if ($jobRedis) {
                $cacheJobKey   = HikConst::HIK_COMMUNITY_JOB_REDIS_KEY . $jobRedis;
                $doJob  = Cache::store('redis')->get($cacheJobKey);
                if (!$doJob) {
                    // todo 调用地方通过判断 -1 得知相同的队列正在等候执行
                    return -1;
                }
            }
            if ($time > 0) {
                $job_id    = $this->hikInfoVisionCommunityLaterToJob($queueData, $time);
            } else {
                $job_id    = $this->hikInfoVisionCommunityPushToJob($queueData);
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
        public function hikInfoVisionCommunityPushToJob($queueData) {
            return Queue::push(HikInfoVisionICommunityJob::class, $queueData, $this->getHikInfoVisionCommunityRegionsHousesTraits());
        }

        /**
         * 公用的延迟下发队列
         * @param $queueData
         * @param int $time 延迟时间 单位秒
         * @return mixed
         */
        public function hikInfoVisionCommunityLaterToJob($queueData, $time = 5) {
            return Queue::later($time,HikInfoVisionICommunityJob::class, $queueData, $this->getHikInfoVisionCommunityRegionsHousesTraits());
        }
    }