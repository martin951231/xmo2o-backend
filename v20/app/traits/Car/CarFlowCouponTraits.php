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
use app\job\Car\CarFlowCouponJob;

trait CarFlowCouponTraits
{
    /**
     * 优惠券队列
     * @return string
     */
    public function queueNameCarFlowCouponTraits()
    {
        return 'car-flow-coupon';
    }

    /**
     * 下发队列
     * @param $queueData
     * @param int $time 延迟时间 单位秒
     * @return mixed
     */
    public function traitCommonCarFlowCoupon($queueData, $time = 0)
    {
        if ($time > 0) {
            $job_id = $this->CarFlowCouponCommonLaterToJob($queueData, $time);
        } else {
            $job_id = $this->CarFlowCouponCommonPushToJob($queueData);
        }
        fdump_api(['queueData' => $queueData, 'job_id' => $job_id, 'time' => $time], 'PlateResultTraits/carFlowCouponLog', CarFlowConst::CAR_FLOW_TRAITS_LOG_APPEND);
        return $job_id;
    }

    /**
     * 公用的立即下发队列
     * @param $queueData
     * @return mixed
     */
    protected function CarFlowCouponCommonPushToJob($queueData)
    {
        return Queue::push(CarFlowCouponJob::class, $queueData, $this->queueNameCarFlowCouponTraits());
    }

    /**
     * 公用的延迟下发队列
     * @param $queueData
     * @param int $time 延迟时间 单位秒
     * @return mixed
     */
    protected function CarFlowCouponCommonLaterToJob($queueData, $time = 5)
    {
        return Queue::later($time, CarFlowCouponJob::class, $queueData, $this->queueNameCarFlowCouponTraits());
    }
}