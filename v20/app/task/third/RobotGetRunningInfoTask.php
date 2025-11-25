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

    class RobotGetRunningInfoTask extends Task
    {
        public function configure ()
        {
            // 每十分钟执行一次
            //$this->everyFiveMinutes();
            $this->everyMinute();
        }
        public function execute ()
        {
            try {
                $thirdRobotDeviceService= new ThirdRobotDeviceService();
                //实时更新 机器人运行状态
                $thirdRobotDeviceService->getRobotRunningInfo();
            }catch (\Exception $e){

            }
        }
    }