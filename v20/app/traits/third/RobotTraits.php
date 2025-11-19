<?php
    /**
     * +----------------------------------------------------------------------
     * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
     * +----------------------------------------------------------------------
     * @author    合肥快鲸科技有限公司
     * @copyright 合肥快鲸科技有限公司
     * @link      https://www.kuaijing.com.cn
     * @Desc      代码功能描述 机器人-压入队列和一些公用方法
     */

    namespace app\traits\third;

    use app\consts\RobotConst;
    use app\job\third\RobotJob;
    use think\facade\Queue;
    use think\facade\Cache;
    trait RobotTraits
    {
        /**
         * 队列名称
         * @return string
         */
        public function queueNameThirdRobotTraits()
        {
            return 'third-robot';
        }


        /**
         * 下发队列
         * @param $queueData
         * @param int $time 延迟时间 单位秒
         * @return mixed
         */
        public function traitCommonRobot($queueData, $time = 0, $expire = 600) {
            $cacheTag  = RobotConst::ROBOT_JOB_REDIS_TAG;
            $cacheKey  = RobotConst::ROBOT_TRAITS_REDIS_KEY . md5(\json_encode($queueData));
            fdump_api(['queueData'=>$queueData], 'thirdRobot/traitCommonRobotLog', 1);
            $jobRedis  = Cache::store('redis')->get($cacheKey);
            $queueData['toTraitTimeRecord'] = time();
            $queueData['toTraitJobSpaceTime'] = $time;
            if ($jobRedis) {
                $cacheJobKey   = RobotConst::ROBOT_JOB_REDIS_KEY . $jobRedis;
                $doJob  = Cache::store('redis')->get($cacheJobKey);
                fdump_api([
                    'msg' => '读取缓存',
                    'queueData' => $queueData,
                    'cacheTag'  => $cacheTag,
                    'cacheKey'  => $cacheKey,
                    'jobRedis'  => $jobRedis,
                    'cacheJobKey'  => $cacheJobKey,
                    'doJob'     => $doJob
                ], 'thirdRobot/traitCommonRobotLog', 1);
                if (!$doJob) {
                    // todo 调用地方通过判断 -1 得知相同的队列正在等候执行
                    return -1;
                }
            }
            if ($time > 0) {
                $job_id    = $this->RobotCommonLaterToJob($queueData, $time);
            } else {
                $job_id    = $this->RobotCommonPushToJob($queueData);
            }
            if ($job_id) {
                Cache::store('redis')->tag($cacheTag)->set($cacheKey,$job_id, $expire);
            }
            return $job_id;
        }

        /**
         * 公用的立即下发队列
         * @param $queueData
         * @return mixed
         */
        protected function RobotCommonPushToJob($queueData)
        {
            return  Queue::push(RobotJob::class,$queueData,$this->queueNameThirdRobotTraits());
        }

        /**
         * 公用的延迟下发队列
         * @param $queueData
         * @param int $time 延迟时间 单位秒
         * @return mixed
         */
        protected function RobotCommonLaterToJob($queueData, $time = 5)
        {
            return Queue::later($time, RobotJob::class, $queueData, $this->queueNameThirdRobotTraits());
        }
    }