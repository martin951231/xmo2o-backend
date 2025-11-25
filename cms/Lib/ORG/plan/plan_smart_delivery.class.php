<?php

class plan_smart_delivery extends plan_base
{
    //已迁移到新版计划任务
    public function runTask()
    {
        require_once APP_PATH . 'Lib/ORG/smart_delivery/Start.class.php';
        Start::run();
        return true;
    }
}

?>
