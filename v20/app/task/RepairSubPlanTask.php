<?php

declare(strict_types=1);
/**
 * This file is part of Kuaijing Bailing.
 *
 * @link     https://www.kuaijingai.com
 * @document https://help.kuaijingai.com
 * @contact  www.kuaijingai.com 7*12 9:00-21:00
 */

namespace app\task;

use app\community\model\service\PileUserService;
use yunwuxin\cron\Task;

class RepairSubPlanTask extends Task
{

    public function configure ()
    {
        // 每分钟执行一次
        $this->everyMinute();
    }

    public function execute ()
    {
        try {
            fdump_api(['subPlan最新执行时间'], 'repairSubPlanTaskLog');
            invoke_cms_model('RePairPlan/repairSubPlan');
        }catch (Exception $e){
            fdump_api(['subPlan执行失败', 'err' => $e->getMessage()], 'repairSubPlanTask');
        }
    }
}