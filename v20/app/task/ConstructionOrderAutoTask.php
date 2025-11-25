<?php

namespace app\task;

use app\community\model\service\ConstructionPayService;
use Exception;
use yunwuxin\cron\Task;

/**
 * 处理情况：建行支付（v2.2）订单1小时未获取到回调查询订单状态、退款后15分钟退矿状态未知
 */
class ConstructionOrderAutoTask extends Task
{
    public function configure()
    {
        $this->everyFiveMinutes(); //每五分钟执行
    }

    /**
     * 执行任务
     * @return mixed
     */
    protected function execute()
    {
        try {
            if(customization('jz_wsxdsj_net_customized_open') == 1){
                (new ConstructionPayService())->handleOrderNotice();
                (new ConstructionPayService())->handleOrderRefund();
            }
        } catch (Exception $e) {
            fdump_api([$e->getFile(), $e->getLine(), $e->getMessage()], 'task/GiftOrderAutoConfirmO2OTask');
        }
    }
}
