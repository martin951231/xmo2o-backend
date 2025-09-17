<?php
/**
 * +----------------------------------------------------------------------
 * 版权所有 2018~2022 合肥快鲸科技有限公司 [ https://www.kuaijing.com.cn ]
 * +----------------------------------------------------------------------
 * @author    合肥快鲸科技有限公司
 * @copyright 合肥快鲸科技有限公司
 * @link      https://www.kuaijing.com.cn
 * @Desc      合同逾期按照配置进行通知.
 */

namespace app\task;

use app\community\model\service\contract\ContractHandleService;
use yunwuxin\cron\Task;

class ContractOverTimePushMessageTask extends Task
{
    public function configure ()
    {
        $this->dailyAt('8:21');   // 指定时间执行 每天8:30
    }

    public function execute ()
    {
        try {
            (new ContractHandleService())->pushMessageToWork();
        } catch (\Exception $e) {
            fdump_api(['file' => $e->getFile(), 'line' => $e->getLine(), 'err' => $e->getMessage()], 'task/ContractOverTimePushMessageTask');
        }
    }
}