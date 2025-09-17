<?php

/**
 * ======================================================

 */
namespace app\task;

use app\pay\controller\NotifyController;
use yunwuxin\cron\Task;

class ScrcuOrderPayInfoQueryTask extends Task
{
    public function configure ()
    {
        /*
        *$this->daily();  //按天执行
        $this->dailyAt();
        */
        $this->everyMinute();  //每分钟
        
    }

    public function execute ()
    {
        try{
            $notifyController = new NotifyController();
            $notifyController->scrcu(true);
        }catch (\Exception $e){
            fdump_api(['e' => $e->getMessage(), 'line' =>$e->getLine(), 'code' => $e->getCode(), 'file' => $e->getFile()], 'scrcu/errScrcuOrderPayInfoQueryTask', 1);
        }
    }
}