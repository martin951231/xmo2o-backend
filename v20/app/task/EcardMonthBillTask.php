<?php

namespace app\task;

use app\internal_card\model\service\InternalCardMoneyBillService;
use yunwuxin\cron\Task;

class EcardMonthBillTask extends Task
{
    public function configure()
    {
        $this->monthlyOn(15);
    }

    public function execute()
    {
        fdump_api(['EcardMonthBillTask执行', '时间' => date('Y-m-d H:i:s')], 'task/EcardMonthBillTask', 1);
        try {
            (new InternalCardMoneyBillService())->internalCardBill();
        } catch (\Exception $e) {
            fdump_api(['EcardMonthBillTask执行失败', 'err' => $e->getMessage()], 'task/EcardMonthBillTask', 1);
        }
    }
}