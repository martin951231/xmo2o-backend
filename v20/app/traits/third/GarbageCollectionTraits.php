<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      代码功能描述 智能垃圾回收设备人脸同步队列
 */

namespace app\traits\third;

use app\consts\DeviceConst;
use app\job\third\GarbageCollectionJob;
use think\facade\Cache;
use think\facade\Queue;

trait GarbageCollectionTraits
{
    /**
     * 队列名称
     * @return string
     */
    public function queueNameGarbageCollection()
    {
        return 'garbage-collection';
    }
    
    /**
     * 公用的延迟下发队列
     * @param $queueData
     * @param int $time 延迟时间 单位秒
     * @return mixed
     */
    public function traitCommonGarbageCollection($queueData, $time = 0)
    {
        $cacheTag  = DeviceConst::GARBAGE_COLLECTION_JOB_REDIS_TAG;
        $dataTrait = $queueData;
        $cacheKey  = DeviceConst::GARBAGE_COLLECTION_TRAITS_REDIS_KEY . md5(\json_encode($dataTrait));
        $jobRedis  = Cache::store('redis')->get($cacheKey);
        if ($jobRedis) {
            $cacheJobKey   = DeviceConst::GARBAGE_COLLECTION_JOB_REDIS_KEY . $jobRedis;
            $doJob  = Cache::store('redis')->get($cacheJobKey);
            if (!$doJob) {
                // todo 调用地方通过判断 -1 得知相同的队列正在等候执行
                return -1;
            }
        }
        if ($time > 0) {
            $job_id    = $this->GarbageCollectionCommonLaterToJob($queueData, $time);
        } else {
            $job_id    = $this->GarbageCollectionCommonPushToJob($queueData);
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
    protected function GarbageCollectionCommonPushToJob($queueData)
    {
        return  Queue::push(GarbageCollectionJob::class,$queueData,$this->queueNameGarbageCollection());
    }

    /**
     * 公用的延迟下发队列
     * @param $queueData
     * @param int $time 延迟时间 单位秒
     * @return mixed
     */
    protected function GarbageCollectionCommonLaterToJob($queueData, $time = 5)
    {
        return Queue::later($time, GarbageCollectionJob::class, $queueData, $this->queueNameGarbageCollection());
    }
}