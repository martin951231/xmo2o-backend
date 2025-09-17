<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      Hik Device Gateway 海康网关 下发队列
 */

namespace app\traits\device;

use app\consts\HikConst;
use app\job\device\HikGateWayJob;
use think\facade\Cache;
use think\facade\Queue;

trait HikGateWayTraits
{
    /**
     * 队列名称
     * @return string
     */
    public function getHikDeviceGateWayTraits()
    {
        return 'hik-device-gate-way';
    }

    public function traitCommonHikDeviceGateWay($queueData, $time = 0, $redisExpire = 120) 
    {
        $cacheTag  = HikConst::HIK_DEVICE_GATE_WAY_JOB_REDIS_TAG;
        $cacheKey  = HikConst::HIK_DEVICE_GATE_WAY_TRAITS_REDIS_KEY . md5(\json_encode($queueData));
        $jobRedis  = Cache::store('redis')->get($cacheKey);
        $nowTime = time();
        if ($jobRedis) {
            $cacheJobKey   = HikConst::HIK_DEVICE_GATE_WAY_JOB_REDIS_KEY . $jobRedis;
            $doJob  = Cache::store('redis')->get($cacheJobKey);
            if ($doJob > 100 && $doJob > $nowTime) {
                // todo 调用地方通过判断 时效 得知相同的队列正在等候执行
                return -1;
            }
            if (!$doJob) {
                // todo 调用地方通过判断 -1 得知相同的队列正在等候执行
                return -1;
            }
        }
        $queueData['handleTypeTime'] = date('Y-m-d H:i:s', $nowTime);
        if ($time > 0) {
            $job_id    = $this->hikDeviceGateWayLaterToJob($queueData, $time);
        } else {
            $job_id    = $this->hikDeviceGateWayPushToJob($queueData);
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
    public function hikDeviceGateWayPushToJob($queueData) {
        return Queue::push(HikGateWayJob::class, $queueData, $this->getHikDeviceGateWayTraits());
    }

    /**
     * 公用的延迟下发队列
     * @param $queueData
     * @param int $time 延迟时间 单位秒
     * @return mixed
     */
    public function hikDeviceGateWayLaterToJob($queueData, $time = 5) {
        return Queue::later($time,HikGateWayJob::class, $queueData, $this->getHikDeviceGateWayTraits());
    }
}