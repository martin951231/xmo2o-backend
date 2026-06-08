<?php

use app\task\AbChinaOrderPayInfoQueryTask;
use app\task\EveryDayTask;
use app\task\HandleThirdWaterElectricMeterTask;
use app\task\AutomaticCallPlanTask;
use app\task\CarFlowClearTask;
use app\task\ChinapopinAutoTask;
use app\task\ChinaumsMerchantWithdrawStatusO2OTask;
use app\task\DeviceCmdTask;
use app\task\EcardMonthBillTask;
use app\task\EveryFiveMinuteSysTask;
use app\task\EveryMinuteO2OTask;
use app\task\EveryOneMinuteSysTask;
use app\task\EveryTenMinutesSysTask;
use app\task\EveryThirtyMinutesSysTask;
use app\task\GiftOrderAutoConfirmO2OTask;
use app\task\GroupOrderAutoEvaluationO2OTask;
use app\task\HandleUserWaterElectricToSmsTask;
use app\task\HandleUserWaterElectricUseTask;
use app\task\HikGetEventMsgTask;
use app\task\MeterDirectorNoticeHouseTask;
use app\task\NoticeSendTask;
use app\task\NoticeWxHouseTask;
use app\task\PileNoticeTask;
use app\task\RepairSubPlanTask;
use app\task\StatisticStoreMoneyBillTask;
use app\task\third\RobotDeviceDailyTask;
use app\task\third\RobotGetDeviceDatasTask;
use app\task\third\RobotGetRunningInfoTask;
use app\task\third\RobotGetTaskDatasTask;
use app\task\TwSyncO2OTask;
use app\task\WorksOrderAutoEvaluateTask;
use app\task\WorksOrderAutoCloseTask;
use app\task\WorkWeiXinHourlyTask;

return [
    'tasks' => [
        HandleThirdWaterElectricMeterTask::class,
        AutomaticCallPlanTask::class,
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
        AbChinaOrderPayInfoQueryTask::class,
        ChinapopinAutoTask::class,
        \app\task\device\HikGateWayDayTask::class,
        NoticeSendTask::class,
        EveryMinuteO2OTask::class,
        WorksOrderAutoCloseTask::class,
        StatisticStoreMoneyBillTask::class,
        EcardMonthBillTask::class,
        ChinaumsMerchantWithdrawStatusO2OTask::class,
        GroupOrderAutoEvaluationO2OTask::class,
        EveryThirtyMinutesSysTask::class,
	    EveryDayTask::class,
	\app\task\SocialActivityOrderO2OTask::class,
    ]
];
