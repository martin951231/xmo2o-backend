<?php

namespace app\task;

use yunwuxin\cron\Task;

/**
 * O2O每分钟计划任务
 *
 * @author: zt
 * @date: 2023/12/27
 */
class EveryMinuteO2OTask extends Task
{
    public function configure()
    {
        $this->everyMinute();
    }

    /**
     * 执行任务
     * @return mixed
     */
    protected function execute()
    {
        try {
            invoke_cms_model('Plan/smartDelivery');
            //同步批量上传商品图片
            invoke_cms_model('Shop_goods/picSync');
        } catch (\Exception $th) {
            //throw $th;
        }
    }
}
