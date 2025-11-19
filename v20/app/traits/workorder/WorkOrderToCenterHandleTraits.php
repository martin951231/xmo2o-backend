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

namespace app\traits\workorder;
use app\job\workorder\WorkOrderToCenterHandleJob;
use think\facade\Queue;

trait WorkOrderToCenterHandleTraits
{
    /**
     * 工单处理相关事宜 队列名称
     * @return string
     */
    public function queueNameWorkOrderToCenterHandleTraits()
    {
        return 'work-order-handle-custom';
    }


    /**
     * 下发队列
     * @param $queueData
     * @param int $time 延迟时间 单位秒
     * @return mixed
     */
    public function traitCommonWorkOrderToCenterHandle($queueData, $time = 0) {
        fdump_api([
            'queueData' => $queueData,
            'time'  => $time,
        ], 'traitCommonWorkOrderToCenterHandleLog', 1);
        if ($time > 0) {
            $job_id    = $this->workOrderToCenterHandleCommonLaterToJob($queueData, $time);
        } else {
            $job_id    = $this->workOrderToCenterHandleCommonPushToJob($queueData);
        }
        return $job_id;
    }

    /**
     * 公用的立即下发队列
     * @param $queueData
     * @return mixed
     */
    protected function workOrderToCenterHandleCommonPushToJob($queueData)
    {
        return  Queue::push(WorkOrderToCenterHandleJob::class,$queueData,$this->queueNameWorkOrderToCenterHandleTraits());
    }

    /**
     * 公用的延迟下发队列
     * @param $queueData
     * @param int $time 延迟时间 单位秒
     * @return mixed
     */
    protected function workOrderToCenterHandleCommonLaterToJob($queueData, $time = 5)
    {
        return Queue::later($time, WorkOrderToCenterHandleJob::class, $queueData, $this->queueNameWorkOrderToCenterHandleTraits());
    }
}