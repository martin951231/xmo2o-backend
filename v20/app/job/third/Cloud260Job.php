<?php

declare(strict_types=1);
/**
 * This file is part of Kuaijing Bailing.
 *
 * @link     https://www.kuaijingai.com
 * @document https://help.kuaijingai.com
 * @contact  www.kuaijingai.com 7*12 9:00-21:00
 */

namespace app\job\third;

use app\community\model\service\Device\HouseVillageDeviceCmdService;
use app\consts\Cloud260Const;
use think\queue\Job as DoJob;
use think\facade\Cache;

class Cloud260Job
{

    public function fire(DoJob $job , $data)
    {
        if ($job->attempts() >= 1) {
            $job->delete();
        }
        $job_id = $job->getJobId();
        $cacheTag  = Cloud260Const::ClOUD_260_JOB_REDIS_TAG;
        $cacheKey   = Cloud260Const::ClOUD_260_TRAITS_REDIS_KEY . $job_id;
        Cache::store('redis')->tag($cacheTag)->set($cacheKey,1);
        try {
            // todo 对应执行
            fdump_api([
                '$data'     => $data,
                '$cacheTag' => $cacheTag,
                '$cacheKey' => $cacheKey,
                '$job_id'   => $job_id,
            ], 'fireCloud260XinLog', 1);
            $jobInfo = (new HouseVillageDeviceCmdService())->handleCloud260Job($data);
            fdump_api([
                '$jobInfo'     => $jobInfo,
            ], 'fireCloud260XinLog', 1);
            return true;
        } catch (\Exception $e) {
            fdump_api(['data' => $data,'err' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()],'ErrCloud260XinJobFire',1);
        }
    }
}