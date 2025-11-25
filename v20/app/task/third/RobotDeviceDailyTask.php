<?php
    /**
     * ======================================================
     * Author: cc
     * Created by PhpStorm.
     * Copyright (c)  cc Inc. All rights reserved.
     * Desc: 每五分钟执行一次的任务
     *  ======================================================
     */

    namespace app\task\third;

    use app\thirdRobot\model\service\ThirdRobotDeviceCmdService;
    use yunwuxin\cron\Task;

    class RobotDeviceDailyTask extends Task
    {
        public function configure ()
        {
            // 按天执行
            $this->daily();
        }

        public function execute ()
        {
            try {
                fdump_api(['robotDeviceDailyTask最新执行时间'], 'RobotDeviceDailyTask');
                (new ThirdRobotDeviceCmdService())->jobToGetRobotsBizPoints();
            }catch (\Exception $e){
                fdump_api(['robotDeviceDailyTask执行失败', 'err' => $e->getMessage()], 'errRobotDeviceDailyTask');
            }
        }

    }