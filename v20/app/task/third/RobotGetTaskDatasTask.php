<?php
    /**
     * ======================================================
     * Author: cc
     * Created by PhpStorm.
     * Copyright (c)  cc Inc. All rights reserved.
     * Desc: 每五分钟执行一次的任务
     * old:cms/Lib/ORG/plan/plan_apDevice_status.class.php
     *  ======================================================
     */

    namespace app\task\third;

    use app\thirdRobot\model\service\ThirdRobotDeviceService;
    use yunwuxin\cron\Task;

    class RobotGetTaskDatasTask extends Task
    {
        public function configure ()
        {
            // 每十分钟执行一次
            //$this->everyTenMinutes();
            //$this->hourlyAt(20); //每小时的第20分钟执行
            $this->everyThirtyMinutes();   //每30分钟
        }
        public function execute ()
        {
            try {
                $thirdRobotDeviceService= new ThirdRobotDeviceService();
                $thirdRobotDeviceService->getPerRoboterRemoteHistoryTask();
            }catch (\Exception $e){

            }
        }
    }