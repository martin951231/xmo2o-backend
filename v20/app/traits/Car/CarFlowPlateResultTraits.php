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
use app\job\Car\CarFlowPlateResultJob;

trait CarFlowPlateResultTraits
{
    /**
     * 停车识别记录队列
     * @return string
     */
    public function queueNameCarFlowPlateResultTraits()
    {
        return 'car-flow-plate-result';
    }

    /**
     * 下发队列
     * @param $queueData
     * @param int $time 延迟时间 单位秒
     * @return mixed
     */
    public function traitCommonCarFlowPlateResult($queueData, $time = 0)
    {
        if ($time > 0) {
            $job_id = $this->CarFlowPlateResultCommonLaterToJob($queueData, $time);
        } else {
            $job_id = $this->CarFlowPlateResultCommonPushToJob($queueData);
        }
        fdump_api(['queueData'=>$queueData, 'job_id' => $job_id, 'time' => $time],'PlateResultTraits/carFlowPlateResultLog', CarFlowConst::CAR_FLOW_TRAITS_LOG_APPEND);
        return $job_id;
    }

    /**
     * 公用的立即下发队列
     * @param $queueData
     * @return mixed
     */
    protected function CarFlowPlateResultCommonPushToJob($queueData)
    {
        return Queue::push(CarFlowPlateResultJob::class, $queueData, $this->queueNameCarFlowPlateResultTraits());
    }

    /**
     * 公用的延迟下发队列
     * @param $queueData
     * @param int $time 延迟时间 单位秒
     * @return mixed
     */
    protected function CarFlowPlateResultCommonLaterToJob($queueData, $time = 5)
    {
        return Queue::later($time, CarFlowPlateResultJob::class, $queueData, $this->queueNameCarFlowPlateResultTraits());
    }
}