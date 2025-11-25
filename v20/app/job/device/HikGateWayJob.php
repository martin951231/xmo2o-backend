<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      Hik Device Gateway 海康网关 执行队列
 */

namespace app\job\device;

use app\community\model\service\Device\HikGateWayJobService;
use app\consts\HikConst;
use think\Exception;
use think\facade\Cache;
use think\queue\Job as DoJob;

class HikGateWayJob
{
    public function fire(DoJob $job, $data)
    {
        if ($job->attempts() >= 1) {
            $job->delete();
        }
        try {
            $job_id = $job->getJobId();
            $cacheTag = HikConst::HIK_DEVICE_GATE_WAY_JOB_REDIS_TAG;
            $cacheKey = HikConst::HIK_DEVICE_GATE_WAY_JOB_REDIS_KEY . $job_id;
            if (isset($data['handleType']) && trim($data['handleType']) == 'addUsersBox') {
                $cacheExpireTime = time() + 7200; // 2小时内 不重复执行相同
                Cache::store('redis')->tag($cacheTag)->set($cacheKey, $cacheExpireTime);
            } else {
                $cacheExpireTime = time() + 1800; // 30分钟内 不重复执行相同
                Cache::store('redis')->tag($cacheTag)->set($cacheKey, $cacheExpireTime);
            }
            
            $hikGateWayJobService = new HikGateWayJobService();
            $hikGateWayJobService->jobToMethod($data);
        } catch (Exception $e) {
            fdump_api([$data, $e->getMessage()], 'job/errHikGateWayJobFire', 1);
        }
    }

    public function failed($data)
    {

    }

}