<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      车辆通行相关额外处理
 */

namespace app\traits\Car;

use app\consts\Car\CarFlowConst;
use app\job\Car\CarFlowHandleJob;
use think\facade\Queue;

trait CarFlowHandleTraits
{
    // 停车图片异步处理 间隔时间
    public $parkCarImageSpaceTimes = 6;
    // 停车出场异步处理 间隔时间
    public $parkOutSpaceTimes = 10;
    // 停车图片异步处理 间隔时间
    public $parkCarFlowHandleSpaceTimes = 6;
    
    // 停车图片异步处理 间隔时间
    public $passageHeartSpaceTimes = 3;
    
    /**
     * 优惠券队列
     * @return string
     */
    public function queueNameCarFlowHandleTraits()
    {
        return 'car-flow-handle';
    }


    /**
     * 下发队列
     * @param $queueData
     * @param int $time 延迟时间 单位秒
     * @return mixed
     */
    public function traitCommonCarFlowHandle($queueData, $time = 0)
    {
        if ($time > 0) {
            $job_id = $this->CarFlowHandleCommonLaterToJob($queueData, $time);
        } else {
            $job_id = $this->CarFlowHandleCommonPushToJob($queueData);
        }
        fdump_api(['queueData' => $queueData, 'job_id' => $job_id, 'time' => $time], 'PlateResultTraits/carFlowHandleLog', CarFlowConst::CAR_FLOW_TRAITS_LOG_APPEND);
        return $job_id;
    }

    /**
     * 公用的立即下发队列
     * @param $queueData
     * @return mixed
     */
    protected function CarFlowHandleCommonPushToJob($queueData)
    {
        return Queue::push(CarFlowHandleJob::class, $queueData, $this->queueNameCarFlowHandleTraits());
    }

    /**
     * 公用的延迟下发队列
     * @param $queueData
     * @param int $time 延迟时间 单位秒
     * @return mixed
     */
    protected function CarFlowHandleCommonLaterToJob($queueData, $time = 5)
    {
        return Queue::later($time, CarFlowHandleJob::class, $queueData, $this->queueNameCarFlowHandleTraits());
    }
    
    protected function getCarOtherRedisConfig(){
        $redisConfig=array();
        $redisConfig['host'] = '192.168.1.12';  //192.168.1.100
        $redisConfig['port'] = 6379;
        $redisConfig['password'] = 'IayKMjXkdupUoQ90'; //123456hz
        $redisConfig['select']     = 1;
        $redisConfig['timeout']    =300;
        $redisConfig['expire']     =  86400;
        $redisConfig['persistent'] = false;
        $redisConfig['prefix']     ='cache-data:';
        $redisConfig['tag_prefix'] =  'cache-data:';
        $redisConfig['serialize']  =  '';
        return $redisConfig;
    }
}