<?php
	/**
	 * ======================================================
	 * Author: cc
	 * Created by PhpStorm.
	 * Copyright (c)  cc Inc. All rights reserved.
	 * Desc: 每10分钟执行一次的任务
	 * old:cms/Lib/ORG/plan/plan_a4_door_open_record.class.php
	 *  ======================================================
	 */

	namespace app\task;

	use app\community\model\service\FaceDeviceService;
    use app\community\model\service\HeatingService;
	use app\appoint\model\service\MerchantWorkService;
    use app\community\model\service\HouseFaceDeviceService;
    use app\villageGroup\model\service\VillageGroupOrderService;
    use think\Exception;
	use yunwuxin\cron\Task;

	class EveryTenMinutesSysTask extends Task
	{
		public function configure ()
		{
		    // 10分钟执行一次
			$this->everyTenMinutes();
		}

		public function execute ()
		{
			try {
                $faceDeviceService = new FaceDeviceService();
                $faceDeviceService->getDhVillageFaceOpenRecord();
			    $houseFaceDeviceService = new HouseFaceDeviceService();
                $houseFaceDeviceService->a4GetDoorOpenRecordTask();
                $heatingService = new HeatingService();
                $heatingService->getCloseHeatingOrderLists(); //新版暖气费账单，发给停暖用户
			}catch (Exception $e){}

            try {

                (new VillageGroupOrderService())->settlementLiveCommission();
            }catch (\Exception $e) {
                fdump_api([
                    'error_msg' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ], 'live_commission/village_group_order', true);
            }
            try {
                (new MerchantWorkService())->settlementServiceFee();
            }catch (\Exception $e){
			    fdump_api([
			        'msg' => $e->getMessage(),
			        'trace' => $e->getTraceAsString()
                ],'merchant_worker/service_fee', true);

            }
		}
	}