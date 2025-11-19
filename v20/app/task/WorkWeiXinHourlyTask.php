<?php
    /**
     * ======================================================
     * Author: cc
     * Created by PhpStorm.
     * Copyright (c)  cc Inc. All rights reserved.
     * Desc: 每1小时执行一次的任务
     *  ======================================================
     */

    namespace app\task;

    use app\community\model\service\VillageQywxMessageService;
    use app\community\model\service\workweixin\WorkWeiXinTaskService;
    use yunwuxin\cron\Task;

    class WorkWeiXinHourlyTask extends Task
    {
        public function configure ()
        {
            // 每1小时执行一次
            $this->hourly();
        }

        public function execute ()
        {
            try {
                (new VillageQywxMessageService())->sendMessage();
                (new WorkWeiXinTaskService())->contactWayUserJob();
            }catch (\Exception $e){
                fdump_api(['WorkWeiXinHourlyTask执行失败', 'err' => $e->getMessage()], 'workWeiXinHourlyTask');
            }
        }
    }