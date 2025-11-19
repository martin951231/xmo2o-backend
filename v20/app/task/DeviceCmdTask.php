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

use app\community\model\service\Device\HouseVillageDeviceCmdService;
use yunwuxin\cron\Task;

class DeviceCmdTask extends Task
{
    public function configure ()
    {
        // 指定每日 01:05 执行
        $this->dailyAt('01:05');
    }

    public function execute()
    {
        try {
            (new HouseVillageDeviceCmdService())->clearRepeatCmdTask();
        } catch (\Exception $e) {
            fdump_api(['msg' => '任务错误', 'err' => $e->getMessage(), 'line' => $e->getLine(), 'code' => $e->getCode()], '260Cloud/DeviceCmdTask');
        }
    }
}