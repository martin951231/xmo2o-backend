<?php
	/**
        ***** 立林社区平台设备相关
	 */

	namespace app\traits;
    
    use think\facade\Queue;
    use app\job\LilinPfCommunityDeviceJob;
    use think\facade\Cache;

    trait LilinPfCommunityDeviceTraits
    {
        /**
         * 队列名称
         * @return string
         */
        public function getLilinPfCommunityDeviceTraits()
        {
            return 'lilin-pf-community-device';
        }

        /**
         * 下发队列
         * @param $queueData
         * @param int $time 延迟时间 单位秒
         * @return mixed
         */
        public function traitLilinPfCommunityUserInfo($queueData, $time = 0, $redisExpire = 120) {
            $cacheTag  = 'LilinPfCommunityUserInfoTag';
            $queueDataJson=json_encode($queueData,JSON_UNESCAPED_UNICODE);
            $cacheKey  ='LilinPfCommunityUserInfoTag:traits:'. md5($queueDataJson);
            $jobRedis  = Cache::store('redis')->get($cacheKey);
            if ($jobRedis) {
                // todo 调用地方通过判断 -1 得知相同的队列正在等候执行
                return -1;
            }
            if ($time > 0) {
                $job_id    = $this->lilinPfCommunityDeviceLaterToJob($queueData, $time);
            } else {
                $job_id    = $this->lilinPfCommunityDevicePushToJob($queueData);
            }
            if ($job_id) {
                Cache::store('redis')->tag($cacheTag)->set($cacheKey,$job_id, $redisExpire);
            }
            return $job_id;
        }
        
        /**
         * 下发队列
         * @param $queueData
         * @param int $time 延迟时间 单位秒
         * @return mixed
         */
        public function traitLilinPfCommunityDevice($queueData, $time = 0, $redisExpire = 120) {
            $cacheTag  = 'LilinPfCommunityDeviceTag';
            $queueDataJson=json_encode($queueData,JSON_UNESCAPED_UNICODE);
            $cacheKey  ='LilinPfCommunityDeviceTag:traits:'. md5($queueDataJson);
            $jobRedis  = Cache::store('redis')->get($cacheKey);
            if ($jobRedis) {
                // todo 调用地方通过判断 -1 得知相同的队列正在等候执行
                return -1;
            }
            if ($time > 0) {
                $job_id    = $this->lilinPfCommunityDeviceLaterToJob($queueData, $time);
            } else {
                $job_id    = $this->lilinPfCommunityDevicePushToJob($queueData);
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
        public function lilinPfCommunityDevicePushToJob($queueData) {
            return Queue::push(LilinPfCommunityDeviceJob::class, $queueData, $this->getLilinPfCommunityDeviceTraits());
        }

        /**
         * 公用的延迟下发队列
         * @param $queueData
         * @param int $time 延迟时间 单位秒
         * @return mixed
         */
        public function lilinPfCommunityDeviceLaterToJob($queueData, $time = 5) {
            return Queue::later($time,LilinPfCommunityDeviceJob::class, $queueData, $this->getLilinPfCommunityDeviceTraits());
        }
    }