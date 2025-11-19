<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      kumike（酷米客-杂淘）-A4(咚咚)人脸设备队列
 */

namespace app\traits\device;

use app\consts\device\A4DeviceConst;
use app\job\device\A4DeviceJob;
use think\facade\Cache;
use think\facade\Queue;

trait A4DeviceTraits
{
    /**
     * kumike（酷米客-杂淘）-A4(咚咚)人脸设备队列 名称
     * @return string
     */
    public function queueNameA4DeviceJobTraits()
    {
        return 'a4-face-device-job';
    }
    
    /**
     * 下发队列
     * @param $queueData
     * @param int $time 延迟时间 单位秒
     * @return mixed
     */
    public function traitCommonA4Device($queueData, $time = 0, $redisExpire = 120) 
    {
        $cacheTag  = A4DeviceConst::A4_DEVICE_JOB_REDIS_TAG;
        $cacheKey  = A4DeviceConst::A4_DEVICE_TRAITS_REDIS_KEY . md5(\json_encode($queueData));
        $jobRedis  = Cache::store('redis')->get($cacheKey);
        if ($jobRedis) {
            $cacheJobKey   = A4DeviceConst::A4_DEVICE_JOB_REDIS_KEY . $jobRedis;
            $doJob  = Cache::store('redis')->get($cacheJobKey);
            if (!$doJob) {
                // todo 调用地方通过判断 -1 得知相同的队列正在等候执行
                return -1;
            }
        }
        if ($time > 0) {
            $job_id    = $this->a4DeviceLaterToJob($queueData, $time);
        } else {
            $job_id    = $this->a4DevicePushToJob($queueData);
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
    public function a4DevicePushToJob($queueData) {
        return Queue::push(A4DeviceJob::class, $queueData, $this->queueNameA4DeviceJobTraits());
    }

    /**
     * 公用的延迟下发队列
     * @param $queueData
     * @param int $time 延迟时间 单位秒
     * @return mixed
     */
    public function a4DeviceLaterToJob($queueData, $time = 5) {
        return Queue::later($time,A4DeviceJob::class, $queueData, $this->queueNameA4DeviceJobTraits());
    }
}