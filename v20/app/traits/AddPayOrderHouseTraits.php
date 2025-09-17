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

use app\job\AddPayOrderHouseJob;
use think\facade\Queue;

trait AddPayOrderHouseTraits
{
    /**
     * 队列名称
     * @return string
     */
    public function queueNameAddPayOrder()
    {
        return 'add-pay-order-house';
    }

    public function laterAddPayOrderQueue($queuData, $time = 5)
    {
        Queue::later($time, AddPayOrderHouseJob::class, $queuData, $this->queueNameAddPayOrder());
    }

}