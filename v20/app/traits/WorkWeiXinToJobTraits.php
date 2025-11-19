<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      企业微信 队列相关属性
 */

namespace app\traits;

use app\consts\WorkWeiXinConst;
use think\facade\Queue;
use app\job\WorkWeiXinJob;
use think\facade\Cache;

trait WorkWeiXinToJobTraits
{
    /**
     * 海康云眸内部应用 队列名称
     * @return string
     */
    public function queueNameWorkWeiXinTraits()
    {
        return 'work-wei-xin-interactive';
    }

    /**
     * 下发队列
     * @param $queueData
     * @param int $time 延迟时间 单位秒
     * @return mixed
     */
    public function traitCommonWorkWeiXin($queueData, $time = 0, $redisExpire = 600) {
        $cacheTag  = WorkWeiXinConst::WORK_WEI_XIN_JOB_REDIS_TAG;
        $cacheKey  = WorkWeiXinConst::WORK_WEI_XIN_TRAITS_REDIS_KEY . md5(\json_encode($queueData));
        fdump_api([
            'queueData' => $queueData,
            'time'  => $time,
            'cacheKey'  => $cacheKey,
            'cacheTag'  => $cacheTag,
        ], 'traitCommonWorkWeiXinLog', 1);
        $jobRedis  = Cache::store('redis')->get($cacheKey);
        fdump_api([
            'jobRedis' => $jobRedis,
        ], 'traitCommonWorkWeiXinLog', 1);
        if ($jobRedis) {
            $cacheJobKey   = WorkWeiXinConst::WORK_WEI_XIN_JOB_REDIS_KEY . $jobRedis;
            $doJob  = Cache::store('redis')->get($cacheJobKey);
            fdump_api([
                '$queueData' => $queueData, 
                '$cacheTag'  => $cacheTag,
                '$cacheKey'  => $cacheKey,
                '$jobRedis'  => $jobRedis,
                '$cacheJobKey'  => $cacheJobKey,
                '$doJob'     => $doJob
            ], 'traitCommonWorkWeiXinLog', 1);
            if (!$doJob) {
                // todo 调用地方通过判断 -1 得知相同的队列正在等候执行
                return -1;
            }
        }
        if ($time > 0) {
            $job_id    = $this->WorkWeiXinCommonLaterToJob($queueData, $time);
        } else {
            $job_id    = $this->WorkWeiXinCommonPushToJob($queueData);
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
    protected function WorkWeiXinCommonPushToJob($queueData)
    {
        return  Queue::push(WorkWeiXinJob::class,$queueData,$this->queueNameWorkWeiXinTraits());
    }

    /**
     * 公用的延迟下发队列
     * @param $queueData
     * @param int $time 延迟时间 单位秒
     * @return mixed
     */
    protected function WorkWeiXinCommonLaterToJob($queueData, $time = 5)
    {
        return Queue::later($time, WorkWeiXinJob::class, $queueData, $this->queueNameWorkWeiXinTraits());
    }
}