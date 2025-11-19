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
use app\job\Car\CarFlowOutJob;

trait CarFlowOutTraits
{
    /**
     * 停车出场记录队列
     * @return string
     */
    public function queueNameCarFlowOutTraits()
    {
        return 'car-flow-out';
    }

    /**
     * 下发队列
     * @param $queueData
     * @param int $time 延迟时间 单位秒
     * @return mixed
     */
    public function traitCommonCarFlowOut($queueData, $time = 0)
    {
        if ($time > 0) {
            $job_id = $this->CarFlowOutCommonLaterToJob($queueData, $time);
        } else {
            $job_id = $this->CarFlowOutCommonPushToJob($queueData);
        }
        fdump_api(['queueData'=>$queueData, 'job_id' => $job_id, 'time' => $time],'PlateResultTraits/carFlowOutLog', CarFlowConst::CAR_FLOW_TRAITS_LOG_APPEND);
        return $job_id;
    }

    /**
     * 公用的立即下发队列
     * @param $queueData
     * @return mixed
     */
    protected function CarFlowOutCommonPushToJob($queueData)
    {
        return Queue::push(CarFlowOutJob::class, $queueData, $this->queueNameCarFlowOutTraits());
    }

    /**
     * 公用的延迟下发队列
     * @param $queueData
     * @param int $time 延迟时间 单位秒
     * @return mixed
     */
    protected function CarFlowOutCommonLaterToJob($queueData, $time = 5)
    {
        return Queue::later($time, CarFlowOutJob::class, $queueData, $this->queueNameCarFlowOutTraits());
    }
}