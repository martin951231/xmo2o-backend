<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      朵普 下发队列
 */

namespace app\traits\device;

use app\consts\device\DuoPuConst;
use app\job\device\DuoPuJob;
use think\facade\Cache;
use think\facade\Queue;

trait DuoPuTraits
{
    /**
     * 队列名称
     * @return string
     */
    public function getDuoPuJobNameTraits()
    {
        return 'duo-pu-device';
    }

    public function traitCommonDuoPu($queueData, $time = 0, $redisExpire = 120)
    {
        $cacheTag = DuoPuConst::DUO_PU_JOB_REDIS_TAG;
        $cacheKey = DuoPuConst::DUO_PU_TRAITS_REDIS_KEY . md5(\json_encode($queueData));
        $jobRedis = Cache::store('redis')->get($cacheKey);
        if ($jobRedis) {
            $cacheJobKey = DuoPuConst::DUO_PU_JOB_REDIS_KEY . $jobRedis;
            $doJob = Cache::store('redis')->get($cacheJobKey);
            if (!$doJob) {
                // todo 调用地方通过判断 -1 得知相同的队列正在等候执行
                return -1;
            }
        }
        if ($time > 0) {
            $job_id = $this->duoPuLaterToJob($queueData, $time);
        } else {
            $job_id = $this->duoPuPushToJob($queueData);
        }
        if ($job_id) {
            Cache::store('redis')->tag($cacheTag)->set($cacheKey, $job_id, $redisExpire);
        }
        return $job_id;
    }

    /**
     * 公用的立即下发队列
     * @param $queueData
     * @return mixed
     */
    public function duoPuPushToJob($queueData)
    {
        return Queue::push(DuoPuJob::class, $queueData, $this->getDuoPuJobNameTraits());
    }

    /**
     * 公用的延迟下发队列
     * @param $queueData
     * @param int $time 延迟时间 单位秒
     * @return mixed
     */
    public function duoPuLaterToJob($queueData, $time = 5)
    {
        return Queue::later($time, DuoPuJob::class, $queueData, $this->getDuoPuJobNameTraits());
    }
}