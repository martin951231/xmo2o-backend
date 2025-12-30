<?php

    use app\task\EveryOneMinuteSysTask;
	use app\task\EveryFiveMinuteSysTask;
	use app\task\NoticeWxHouseTask;
	use app\task\EveryTenMinutesSysTask;
    use app\task\GiftOrderAutoConfirmO2OTask;
    use app\task\WorksOrderAutoEvaluateTask;
    use app\task\HandleUserWaterElectricUseTask;
    use app\task\HandleUserWaterElectricToSmsTask;
    use app\task\MeterDirectorNoticeHouseTask;
    use app\task\PileNoticeTask;
    use app\task\RepairSubPlanTask;
    use app\task\DeviceCmdTask;
    use app\task\third\RobotDeviceDailyTask;
    use app\task\third\RobotGetTaskDatasTask;
    use app\task\third\RobotGetDeviceDatasTask;
    use app\task\third\RobotGetRunningInfoTask;
    use app\task\WorkWeiXinHourlyTask;
    use app\task\CarFlowClearTask;
    use app\task\HikGetEventMsgTask;
    use app\task\TwSyncO2OTask;
    use app\task\AbChinaOrderPayInfoQueryTask;
    use app\task\ChinapopinAutoTask;
    use app\task\EveryMinuteO2OTask;
    use app\task\WechatMiniLiveSyncO2OTask;
    use app\task\NoticeSendTask;
    use app\task\WorksOrderAutoCloseTask;

	return [
	    'tasks' => [
            EveryOneMinuteSysTask::class,
			NoticeWxHouseTask::class,
	        EveryFiveMinuteSysTask::class,
	        EveryTenMinutesSysTask::class,
            WorksOrderAutoEvaluateTask::class,
            MeterDirectorNoticeHouseTask::class,
            PileNoticeTask::class,
            RepairSubPlanTask::class,
            DeviceCmdTask::class,
            RobotGetTaskDatasTask::class,
            RobotGetDeviceDatasTask::class,
            RobotDeviceDailyTask::class,
            RobotGetRunningInfoTask::class,
            HandleUserWaterElectricUseTask::class,
            HandleUserWaterElectricToSmsTask::class,
            WorkWeiXinHourlyTask::class,
            CarFlowClearTask::class,
            HikGetEventMsgTask::class,
            TwSyncO2OTask::class,
            GiftOrderAutoConfirmO2OTask::class,
            \app\task\ConstructionOrderAutoTask::class,
//            \app\task\ContractOverTimePushMessageTask::class,
            AbChinaOrderPayInfoQueryTask::class,
            ChinapopinAutoTask::class,
            \app\task\device\HikGateWayDayTask::class,
            //WechatMiniLiveSyncO2OTask::class,
            NoticeSendTask::class,
            EveryMinuteO2OTask::class,
            WorksOrderAutoCloseTask::class,
	    ]
];