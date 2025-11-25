<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      普通队列相关属性
 */

namespace app\traits\Car;

use app\consts\Car\CarFlowConst;
use think\facade\Queue;
use app\job\Car\CarFlowInJob;

trait CarFlowInTraits
{
    /**
     * 停车入场记录队列
     * @return string
     */
    public function queueNameCarFlowInTraits()
    {
        return 'car-flow-in';
    }

    /**
     * 下发队列
     * @param $queueData
     * @param int $time 延迟时间 单位秒
     * @return mixed
     */
    public function traitCommonCarFlowIn($queueData, $time = 0)
    {
        if ($time > 0) {
            $job_id = $this->CarFlowInCommonLaterToJob($queueData, $time);
        } else {
            $job_id = $this->CarFlowInCommonPushToJob($queueData);
        }
        fdump_api(['queueData' => $queueData, 'job_id' => $job_id, 'time' => $time], 'PlateResultTraits/carFlowInLog', CarFlowConst::CAR_FLOW_TRAITS_LOG_APPEND);
        return $job_id;
    }

    /**
     * 公用的立即下发队列
     * @param $queueData
     * @return mixed
     */
    protected function CarFlowInCommonPushToJob($queueData)
    {
        return Queue::push(CarFlowInJob::class, $queueData, $this->queueNameCarFlowInTraits());
    }

    /**
     * 公用的延迟下发队列
     * @param $queueData
     * @param int $time 延迟时间 单位秒
     * @return mixed
     */
    protected function CarFlowInCommonLaterToJob($queueData, $time = 5)
    {
        return Queue::later($time, CarFlowInJob::class, $queueData, $this->queueNameCarFlowInTraits());
    }
}