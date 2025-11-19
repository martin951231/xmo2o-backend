<?php

namespace app\task;

use Exception;
use yunwuxin\cron\Task;

/**
 * 积分商城自提订单发货后超时自动确认收货
 *
 * @author: zt
 * @date: 2023/07/06
 */
class GiftOrderAutoConfirmO2OTask extends Task
{
    public function configure()
    {
        $this->everyTenMinutes(); //每十分钟执行
    }

    /**
     * 执行任务
     * @return mixed
     */
    protected function execute()
    {
        try {
            invoke_cms_model('Gift_order/giftOrderAutoConfirm');
        } catch (Exception $e) {
            fdump_api([$e->getFile(), $e->getLine(), $e->getMessage()], 'task/GiftOrderAutoConfirmO2OTask');
        }
    }
}
