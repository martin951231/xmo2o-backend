<?php

/**
 * ======================================================
 * Desc: 农行查询的订单30分钟后去查询一下流水状态，防止出现由于网络等因素可能存在销账回调丢数据情况的发生（每分钟运行一次）
 *  ======================================================
 */
namespace app\task;

use app\community\model\service\AbChinaService;
use app\pay\controller\NotifyController;
use yunwuxin\cron\Task;

class AbChinaOrderPayInfoQueryTask extends Task
{
    public function configure ()
    {
        $this->everyMinute();  //每分钟
    }

    public function execute ()
    {
        $notifyController = new AbChinaService();
        $notifyController->getAbChinaOrderInfo(); 
    }
}