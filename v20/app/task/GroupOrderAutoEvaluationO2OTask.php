<?php

declare(strict_types=1);
namespace app\task;


use app\group\model\service\order\GroupOrderAutoEvaluationService;
use yunwuxin\cron\Task;

class GroupOrderAutoEvaluationO2OTask extends Task
{
    public function configure ()
    {
        $this->everyMinute(); //
    }

    public function execute ()
    {
        try {
            if(cfg('group_order_auto_evaluation')>0){
                (new GroupOrderAutoEvaluationService())->autoEvaluation();
            }
        } catch (\Exception $e) {
            fdump_api([$e->getFile(), $e->getLine(), $e->getMessage()], 'task/GroupOrderAutoEvaluationO2OTask');
        }
    }
}