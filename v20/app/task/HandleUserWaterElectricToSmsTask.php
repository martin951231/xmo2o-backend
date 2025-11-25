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

    use app\community\model\service\CustomizeMeterWaterReadingService;
	use yunwuxin\cron\Task;

	class HandleUserWaterElectricToSmsTask extends Task
	{
		public function configure ()
		{
			/*
		    *$this->daily();  //按天执行
            $this->everyMinute();  //每分钟
			$this->everyTenMinutes();  //每十分钟
			$this->dailyAt('05:00');   //指定时间执行
			*/
            //$this->daily();
            $this->dailyAt('10:30');   //指定时间执行 每天早上六点半
		}

		public function execute ()
		{
			$meterWaterReadingService = new CustomizeMeterWaterReadingService();
            $meterWaterReadingService->handleUserWaterElectricToSmsTask(); //余额不足 发短信 短暂断水断电
		}
	}