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

	use app\community\model\service\Device\Hik6000CCameraService;
    use app\community\model\service\HouseNewCashierService;
	use yunwuxin\cron\Task;

	class NoticeWxHouseTask extends Task
	{
		public function configure ()
		{
//			$this->daily();
            $this->dailyAt('09:30');   //指定时间执行 每天早上九点半
		}

		public function execute ()
		{
            try {
                $houseNewCashierService = new HouseNewCashierService();
                $houseNewCashierService->getArrearsList(); //生成欠费账单给业主发送模板通知
                $houseNewCashierService->getArrearsOrderList(); //欠费发送模板通知
            }catch (Exception $e){
                
            }
            try {
                (new Hik6000CCameraService())->getDeviceByAllCommunityId();
            }catch (Exception $e){}
		}
	}