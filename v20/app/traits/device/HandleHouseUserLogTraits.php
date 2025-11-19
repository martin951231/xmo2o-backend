<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      通行日志记录 队列
 */

namespace app\traits\device;

use app\job\device\HandleHouseUserLogJob;
use think\facade\Queue;

trait HandleHouseUserLogTraits
{
    /**
     * 通行记录 队列名称
     * @return string
     */
    public function queueNameHandleHouseUserLogTraits()
    {
        return 'handle-house-user-log-interactive';
    }

    /**
     * 下发队列
     * @param $queueData
     * @param int $time 延迟时间 单位秒
     * @return mixed
     */
    public function traitCommonHandleHouseUserLog($queueData, $time = 0) 
    {
        if ($time > 0) {
            $job_id    = $this->HandleHouseUserLogCommonLaterToJob($queueData, $time);
        } else {
            $job_id    = $this->HandleHouseUserLogCommonPushToJob($queueData);
        }
        return $job_id;
    }

    /**
     * 公用的立即下发队列
     * @param $queueData
     * @return mixed
     */
    protected function HandleHouseUserLogCommonPushToJob($queueData)
    {
        return  Queue::push(HandleHouseUserLogJob::class,$queueData,$this->queueNameHandleHouseUserLogTraits());
    }

    /**
     * 公用的延迟下发队列
     * @param $queueData
     * @param int $time 延迟时间 单位秒
     * @return mixed
     */
    protected function HandleHouseUserLogCommonLaterToJob($queueData, $time = 5)
    {
        return Queue::later($time, HandleHouseUserLogJob::class, $queueData, $this->queueNameHandleHouseUserLogTraits());
    }
}