<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      公共订单业务关联记录表 队列相关属性
 */

namespace app\traits;

use think\facade\Queue;
use app\job\order\CommonOrderBusinessRecordJob;

trait CommonOrderBusinessRecordJobTraits
{
    /**
     * 海康云眸内部应用 队列名称
     * @return string
     */
    public function queueNameCommonOrderTraits()
    {
        return 'common-order-business-record';
    }

    /**
     * 下发队列
     * @param $queueData
     * @param int $time 延迟时间 单位秒
     * @return mixed
     */
    public function traitCommonOrderBusinessRecordJobTraits($queueData, $time = 0) {
        if ($time > 0) {
            $job_id    = $this->CommonOrderBusinessRecordLaterToJob($queueData, $time);
        } else {
            $job_id    = $this->CommonOrderBusinessRecordPushToJob($queueData);
        }
        return $job_id;
    }

    /**
     * 公用的立即下发队列
     * @param $queueData
     * @return mixed
     */
    protected function CommonOrderBusinessRecordPushToJob($queueData)
    {
        return  Queue::push(CommonOrderBusinessRecordJob::class,$queueData,$this->queueNameCommonOrderTraits());
    }

    /**
     * 公用的延迟下发队列
     * @param $queueData
     * @param int $time 延迟时间 单位秒
     * @return mixed
     */
    protected function CommonOrderBusinessRecordLaterToJob($queueData, $time = 5)
    {
        return Queue::later($time, CommonOrderBusinessRecordJob::class, $queueData, $this->queueNameCommonOrderTraits());
    }
}