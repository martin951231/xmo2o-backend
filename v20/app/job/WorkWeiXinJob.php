<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      企业微信 队列
 */

namespace app\job;

use app\community\model\service\workweixin\DepartmentUserService;
use app\community\model\service\workweixin\WeiXinUnionIdToExternalUserIdService;
use app\community\model\service\workweixin\WorkWeiXinGroupMsgService;
use app\community\model\service\workweixin\WorkWeiXinNewService;
use app\community\model\service\workweixin\WorkWeiXinTaskService;
use app\consts\WorkWeiXinConst;
use think\Exception;
use think\queue\Job as DoJob;
use think\facade\Cache;

class WorkWeiXinJob
{

    public function fire(DoJob $job , $data)
    {
        if ($job->attempts() >= 1){
            $job->delete();
        }
        try {
            // todo 对应执行
            $job_id = $job->getJobId();
            $cacheTag  = WorkWeiXinConst::WORK_WEI_XIN_JOB_REDIS_TAG;
            $cacheKey   = WorkWeiXinConst::WORK_WEI_XIN_JOB_REDIS_KEY . $job_id;
            Cache::store('redis')->tag($cacheTag)->set($cacheKey,1);

            $doJob  = Cache::store('redis')->get($cacheKey);
            fdump_api([
                '$data'     => $data,
                '$cacheTag' => $cacheTag,
                '$cacheKey' => $cacheKey,
                '$job_id'   => $job_id,
                '$doJob'    => $doJob,
            ], 'fireCommonWorkWeiXinLog', 1);
            if (isset($data['jobType'])) {
                $jobType = $data['jobType'];
                unset($data['jobType']);
            } else {
                $jobType = '';
            }
            if (isset($data['job_id'])) {
                unset($data['job_id']);
            }
            $this->getEnvFunction($jobType, $data);
        } catch (Exception $e){
            fdump_api([$data,$e->getMessage()],'ErrJobWorkWeiXinJobFire',1);
        }
    }


    public function failed($data)
    {
    }
    
    protected function getEnvFunction($jobType, $data) {
        switch ($jobType) {
            case 'getWorkWeiXinDepartmentList':
                (new DepartmentUserService())->getWorkWeiXinDepartmentList($data);
                break;
            case 'syncPropertyGroupToWorkWeiXin':
                $property_id = isset($data['property_id']) && $data['property_id'] ? $data['property_id'] : 0;
                $village_id  = isset($data['village_id'])  && $data['village_id']  ? $data['village_id']  : 0;
                (new DepartmentUserService())->syncPropertyGroupToWorkWeiXin($property_id, $village_id);
                break;
            case 'syncDepartmentToWorkWeiXin':
                (new DepartmentUserService())->syncDepartmentToWorkWeiXin($data);
                break;
            case 'syncDepartmentUsersToWorkWeiXin':
                (new DepartmentUserService())->syncDepartmentUsersToWorkWeiXin($data);
                break;
            case 'syncWorkUserToWorkWeiXin':
                (new DepartmentUserService())->syncWorkUserToWorkWeiXin($data);
                break;
            case 'workWeiXinNew':
                (new WorkWeiXinNewService())->workWeiXinNew($data);
                break;
            case 'workWeiXinGroupMsg':
                (new WorkWeiXinGroupMsgService())->workWeiXinGroupMsg($data);
                break;
            case 'workWeiXinTask':
                (new WorkWeiXinTaskService())->WorkWeiXinTask($data);
                break;
            case 'contactWayEvent':
                (new WorkWeiXinNewService())->contactWayEvent($data);
                break;
            case 'bindUserToWorkWeiXin':
                (new WeiXinUnionIdToExternalUserIdService())->bindUserToWorkWeiXin($data);
                break;
            case 'handleUserToExternalUserId':
                (new WeiXinUnionIdToExternalUserIdService())->handleUserToExternalUserId(0, $data);
                break;
        }
        return true;
    }
}