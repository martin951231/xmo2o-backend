<?php

declare(strict_types=1);

namespace app\task;

use app\internal_card\model\service\InternalCardMoneyBillService;
use yunwuxin\cron\Task;

class StatisticStoreMoneyBillTask extends Task
{
    public function configure ()
    {
        $this->dailyAt('02:00');//指定时间执行 每天凌晨2点执行
    }

    public function execute ()
    {
        fdump_api(['StatisticStoreMoneyBillTask开始执行'], 'task/StatisticStoreMoneyBillTask',1);
        try {
            (new InternalCardMoneyBillService())->merchantStoreBill();
        }catch (\Exception $e){
            fdump_api(['StatisticStoreMoneyBillTask执行失败', 'error' => $e->getMessage()], 'task/StatisticStoreMoneyBillTask',1);
        }
    }
}