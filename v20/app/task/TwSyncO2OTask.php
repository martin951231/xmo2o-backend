<?php

namespace app\task;

use yunwuxin\cron\Task;

//图维商超数据定时任务
class TwSyncO2OTask extends Task
{
    public function configure()
    {
        $this->everyMinute();
    }

    public function execute()
    {
        if (customization('open_tuwei_sync') == 1) {
            invoke_cms_model('Tw_task/run');
            invoke_cms_model('Tw_task/updateTwGoodsStockByStoreId');
        }
    }
}
