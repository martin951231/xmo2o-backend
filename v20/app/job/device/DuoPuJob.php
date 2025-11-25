<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      朵普 执行队列
 */

namespace app\job\device;

use app\community\model\service\CloudIntercomService;
use app\consts\device\DuoPuConst;
use think\Exception;
use think\facade\Cache;
use think\queue\Job as DoJob;

class DuoPuJob
{
    public function fire(DoJob $job, $data)
    {
        if ($job->attempts() >= 1) {
            $job->delete();
        }
        try {
            $job_id = $job->getJobId();
            $cacheTag = DuoPuConst::DUO_PU_JOB_REDIS_TAG;
            $cacheKey = DuoPuConst::DUO_PU_JOB_REDIS_KEY . $job_id;
            Cache::store('redis')->tag($cacheTag)->set($cacheKey, 1);
            (new CloudIntercomService())->jobToMethod($data);
        } catch (Exception $e) {
            fdump_api(['data' => $data, 'errMsg' => $e->getMessage(), 'errCode' => $e->getCode(), 'errLine' => $e->getLine(), 'errDate' => $e->getData()], 'job/errDuoPuJobFire', 1);
        }
    }

    public function failed($data)
    {
        fdump_api(['data' => $data], 'job/errDuoPuJobFire', 1);
    }
}