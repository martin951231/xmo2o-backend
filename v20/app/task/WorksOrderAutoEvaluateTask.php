<?php

	/**
	 * ======================================================
	 * Author: cc
	 * Created by PhpStorm.
	 * Copyright (c)  cc Inc. All rights reserved.
	 * Desc: 社区欠费自动发送微信通知提醒 一天一次
	 * 替换 : cms/Lib/ORG/plan/plan_sub_house_new_notice_1.class.php cms/Lib/ORG/plan/plan_sub_house_new_notice_2.class.php
	 *  ======================================================
	 */
	namespace app\task;

    use app\community\model\service\HouseNewRepairService;
	use yunwuxin\cron\Task;

	class WorksOrderAutoEvaluateTask extends Task
	{
		public function configure ()
		{
			/*
		    *$this->daily();  //按天执行
            $this->everyMinute();  //每分钟
			*/
            $this->everyTenMinutes();
		}

		public function execute ()
		{
			$houseNewRepairService = new HouseNewRepairService();
            $houseNewRepairService->worksOrderAutoEvaluateVillage(); //工单自动评价
		}
	}