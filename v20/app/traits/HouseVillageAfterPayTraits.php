<?php
/**
 * @author : 合肥快鲸科技有限公司
 * @date : 2022/11/17
 */

namespace app\traits;
use app\job\HouseVillageAfterPayJob;
use think\facade\Queue;

trait HouseVillageAfterPayTraits
{


    /**
     * 队列名称
     * @author : lkz
     * @date : 2022/11/17
     * @return string
     */
    public function houseVillageAfterPayQueueName()
    {
        return 'house-village-after-pay-handle';
    }


    /**
     * 立即下发队列
     * @author : lkz
     * @date : 2022/11/17
     * @param $queueData
     * @return mixed
     */
    protected function houseVillageAfterPayQueuePushToJob($queueData)
    {
        return  Queue::push(HouseVillageAfterPayJob::class,$queueData,$this->houseVillageAfterPayQueueName());
    }

    /**
     *多少秒后执行
     ***/
    protected function houseVillageAfterPayQueueLaterPushToJob($queueData,$time=900)
    {
        $time=$time>0 ? $time:900;
        return Queue::later($time,HouseVillageAfterPayJob::class,$queueData,$this->houseVillageAfterPayQueueName());
    }
    
}