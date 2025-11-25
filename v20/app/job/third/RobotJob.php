<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      机器人队列
 */

namespace app\job\third;

use app\consts\RobotConst;
use app\thirdRobot\model\service\ThirdRobotJobService;
use think\queue\Job as DoJob;
use think\facade\Cache;

class RobotJob
{
    public function fire(DoJob $job , $data)
    {
        if ($job->attempts() >= 1){
            $job->delete();
        }
        try {
            // todo 对应执行
            $job_id = $job->getJobId();
            $cacheTag  = RobotConst::ROBOT_JOB_REDIS_TAG;
            $cacheKey   = RobotConst::ROBOT_JOB_REDIS_KEY . $job_id;
            Cache::store('redis')->tag($cacheTag)->set($cacheKey,1);

            $doJob  = Cache::store('redis')->get($cacheKey);
            fdump_api([
                'data'     => $data,
                'cacheTag' => $cacheTag,
                'cacheKey' => $cacheKey,
                'job_id'   => $job_id,
                'doJob'    => $doJob,
            ], 'thirdRobot/fireRobotJobLog', 1);
            if (isset($data['jobType'])) {
                $jobType = $data['jobType'];
                unset($data['jobType']);
            } else {
                $jobType = '';
            }
            if (isset($data['job_id'])) {
                unset($data['job_id']);
            }
            (new ThirdRobotJobService())->robotToJob($jobType, $data);
        } catch (\Exception $e){
            fdump_api([$data,$e->getMessage()],'thirdRobot/ErrJobRobotJobFire');
        }
    }
}