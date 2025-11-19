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

class PileNoticeTask extends Task
{

    public function configure ()
    {
        // 每分钟执行一次
        $this->everyMinute();
    }

    public function execute ()
    {
        try {
            fdump_api(['pile最新执行时间'], 'PileNoticeTaskLog');
            (new PileUserService())->PileCommand();
        }catch (Exception $e){
            fdump_api(['pile执行失败', 'err' => $e->getMessage()], 'PileNoticeTask');
        }
    }
}