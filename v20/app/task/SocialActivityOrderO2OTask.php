<?php

namespace app\task;

use app\social_activity\model\service\SocialActivityOrderService;
use Exception;
use yunwuxin\cron\Task;

/**
 * 积分商城自提订单发货后超时自动确认收货
 *
 * @author: zt
 * @date: 2023/07/06
 */
class SocialActivityOrderO2OTask extends Task
{
    public function configure()
    {
        $this->everyMinute(); //每分钟执行
    }

    /**
     * 执行任务
     * @return mixed
     */
    protected function execute()
    {
        // 自动取消订单
        try {
            (new SocialActivityOrderService)->autoCancelOrder();
        } catch (Exception $e) {
            fdump_api([$e->getFile(), $e->getLine(), $e->getMessage()], 'task/SocialActivityOrderO2OTask');
        }

        //活动开始前30分钟提醒
        try {
            (new SocialActivityOrderService)->autoAdvanceNotice();
        } catch (Exception $e) {
            fdump_api([$e->getFile(), $e->getLine(), $e->getMessage()], 'task/SocialActivityOrderO2OTask');
        }
    }
}
