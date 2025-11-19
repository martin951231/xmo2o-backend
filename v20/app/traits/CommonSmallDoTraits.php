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

namespace app\traits;


use app\job\CommonSmallDoJob;
use think\Exception;
use think\facade\Queue;

trait CommonSmallDoTraits
{
    
    /**
     * 队列名称
     * @return string
     */
    public function queueNameComonSmallDo()
    {
        return 'common-small-do';
    }
    public function laterCommonSmallDoToJob($queueData, $time = 10)
    {
        try {
            return Queue::later($time, CommonSmallDoJob::class, $queueData, $this->queueNameComonSmallDo());
        } catch (\Exception $e) {
            return '';
        }
    }

    public function commonSmallDoToJob($queueData)
    {
        try {
            return Queue::push(CommonSmallDoJob::class, $queueData, $this->queueNameComonSmallDo());
        } catch (\Exception $e) {
            return '';
        }
        
    }
}