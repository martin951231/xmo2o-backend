<?php


namespace app\task;


use yunwuxin\cron\Task;

class ChinaumsMerchantWithdrawStatusO2OTask extends Task
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
            invoke_cms_model('Merchant_withdraw_chinaums/queryWithdrawResults',['limit'=>20]);
        } catch (Exception $e) {
            fdump_api([$e->getFile(), $e->getLine(), $e->getMessage()], 'task/ChinaumsMerchantWithdrawStatusO2OTask');
        }
    }
}