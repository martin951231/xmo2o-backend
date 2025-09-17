<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      工单处理通知或者相关事项队列（目前用于定制-推送督查室）
 */

namespace app\job\workorder;

use app\community\model\service\workorder\HouseCommunityHandleNoticeService;
use think\Exception;
use think\queue\Job as DoJob;

class WorkOrderToCenterHandleJob
{

    public function fire(DoJob $job , $data)
    {
        if ($job->attempts() >= 1){
            $job->delete();
        }
        try {
            // todo 对应执行
            $job_id = $job->getJobId();
            fdump_api([
                '$data'     => $data,
                '$job_id'   => $job_id,
            ], 'jobCommonWorkOrderToCenterHandleLog', 1);
            $data['doJobId'] = $job_id;
            (new HouseCommunityHandleNoticeService())->jobHandle($data);
        } catch (Exception $e){
            fdump_api([$data,$e->getMessage()],'ErrjobCommonWorkOrderToCenterHandleLog',1);
        }
    }


    public function failed($data)
    {
    }

}