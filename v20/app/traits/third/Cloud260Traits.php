<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      代码功能描述 亚茹队列和一些公用方法
 */

namespace app\traits\third;

use app\consts\Cloud260Const;
use app\job\third\Cloud260Job;
use think\facade\Cache;
use think\facade\Queue;

trait Cloud260Traits
{
    /**
     * 队列名称
     * @return string
     */
    public function queueNameCloud260()
    {
        return 'cloud-260';
    }
    
    /**
     * 公用的延迟下发队列
     * @param $queueData
     * @param int $time 延迟时间 单位秒
     * @return mixed
     */
    public function traitCommonCloud260($queueData, $time = 0)
    {
        $cacheTag  = Cloud260Const::ClOUD_260_JOB_REDIS_TAG;
        $dataTrait = $queueData;
        $cacheKey  = Cloud260Const::ClOUD_260_TRAITS_REDIS_KEY . md5(\json_encode($dataTrait));
        $jobRedis  = Cache::store('redis')->get($cacheKey);
        if ($jobRedis) {
            $cacheJobKey   = Cloud260Const::ClOUD_260_JOB_REDIS_KEY . $jobRedis;
            $doJob  = Cache::store('redis')->get($cacheJobKey);
            if (!$doJob) {
                // todo 调用地方通过判断 -1 得知相同的队列正在等候执行
                return -1;
            }
        }
        if ($time > 0) {
            $job_id    = $this->Cloud260CommonLaterToJob($queueData, $time);
        } else {
            $job_id    = $this->Cloud260CommonPushToJob($queueData);
        }
        if ($job_id) {
            if (! $time || intval($time) <= 0) {
                $expire = 60;
            } else {
                $expire = intval($time) + 60;
            }
            Cache::store('redis')->tag($cacheTag)->set($cacheKey,$job_id, $expire);
        }
        return $job_id;
    }


    /**
     * 公用的立即下发队列
     * @param $queueData
     * @return mixed
     */
    protected function Cloud260CommonPushToJob($queueData)
    {
        return  Queue::push(Cloud260Job::class,$queueData,$this->queueNameCloud260());
    }

    /**
     * 公用的延迟下发队列
     * @param $queueData
     * @param int $time 延迟时间 单位秒
     * @return mixed
     */
    protected function Cloud260CommonLaterToJob($queueData, $time = 5)
    {
        return Queue::later($time, Cloud260Job::class, $queueData, $this->queueNameCloud260());
    }
}