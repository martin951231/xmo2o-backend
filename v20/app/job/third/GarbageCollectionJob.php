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

use app\community\model\service\Device\GarbageCollectionMachineService;
use app\consts\DeviceConst;
use think\queue\Job as DoJob;
use think\facade\Cache;

class GarbageCollectionJob
{

    public function fire(DoJob $job , $data)
    {
        if ($job->attempts() >= 1) {
            $job->delete();
        }
        $job_id = $job->getJobId();
        $cacheTag  = DeviceConst::GARBAGE_COLLECTION_JOB_REDIS_TAG;
        $cacheKey   = DeviceConst::GARBAGE_COLLECTION_TRAITS_REDIS_KEY . $job_id;
        Cache::store('redis')->tag($cacheTag)->set($cacheKey,1);
        try {
            // todo 对应执行
            fdump_api([
                '$data'     => $data,
                '$cacheTag' => $cacheTag,
                '$cacheKey' => $cacheKey,
                '$job_id'   => $job_id,
            ], 'fireGarbageLog', 1);
            $jobInfo = (new GarbageCollectionMachineService())->handleGarbageCollectionJob($data);
            fdump_api([
                '$jobInfo'     => $jobInfo,
            ], 'fireGarbageLog', 1);
            return true;
        } catch (\Exception $e) {
            fdump_api(['data' => $data,'err' => $e->getMessage(), 'line' => $e->getLine(), 'file' => $e->getFile()],'ErrGarbageJobFire',1);
        }
    }
    public function failed($data){

        fdump_api(['msg' => 'error',], 'garbageCollection/pushVillageAllFace',1);
    }
}