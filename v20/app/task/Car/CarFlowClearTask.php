<?php

namespace app\task;

use app\consts\Car\CarFlowConst;
use yunwuxin\cron\Task;

/**
 * ======================================================
 * Author: cc
 * Created by PhpStorm.
 * Copyright (c)  cc Inc. All rights reserved.
 * Desc: 每10分钟执行一次的任务
 *  ======================================================
 */
class CarFlowClearTask extends Task
{
    use \app\traits\Car\CarFlowClearTraits;
    
    public function configure()
    {
        $this->everyTenMinutes();
    }

    public function execute()
    {
        try {
            $nowTime = time();
            // todo 走下队列 执行下 清空超时的车闸显屏
            $showLimitTime = $nowTime - CarFlowConst::CAR_FLOW_PARK_SHOW_SCREEN_LIMIT_TIME;
            $clearData = [
                'jobType' => 'clearOverShowScreen',
                'showLimitTime' => $showLimitTime,
                'nowTime' => $nowTime,
                'limitTime' => CarFlowConst::CAR_FLOW_PARK_SHOW_SCREEN_LIMIT_TIME,
            ];
            $this->traitCommonCarClearScreen($clearData);

            // todo 走下队列 执行下 清空超时的开闸命令
            $openLimitTime = $nowTime - CarFlowConst::CAR_FLOW_OPEN_LOG_LIMIT_TIME;
            $clearData = [
                'jobType' => 'clearOverParkOpen',
                'openLimitTime' => $openLimitTime,
                'nowTime' => $nowTime,
                'limitTime' => CarFlowConst::CAR_FLOW_OPEN_LOG_LIMIT_TIME,
            ];
            $this->traitCommonCarClearScreen($clearData);

            // todo 走下队列 执行下 清空超时的支付待查询记录
            $payRecordLimitTime = $nowTime - CarFlowConst::CAR_FLOW_SCRCU_RECORD_LIMIT_TIME;
            $clearData = [
                'jobType' => 'clearOverPayRecord',
                'payRecordLimitTime' => $payRecordLimitTime,
                'nowTime' => $nowTime,
                'limitTime' => CarFlowConst::CAR_FLOW_SCRCU_RECORD_LIMIT_TIME,
            ];
            $this->traitCommonCarClearScreen($clearData);

            // todo 走下队列 执行下 清空超时的智能停车485串口数据上报表
            $parkSerialLogLimitTime = $nowTime - CarFlowConst::PARK_SERIAL_LOG_LIMIT_TIME;
            $clearData = [
                'jobType' => 'clearOverParkSerialLog',
                'parkSerialLogLimitTime' => $parkSerialLogLimitTime,
                'nowTime' => $nowTime,
                'limitTime' => CarFlowConst::PARK_SERIAL_LOG_LIMIT_TIME,
            ];
            $this->traitCommonCarClearScreen($clearData);
            
        } catch (\Exception $e) {
            fdump_api(['msg' => 'task队列执行错误', 'err' => $e->getMessage(), 'line' => $e->getLine(), 'code' => $e->getCode(), 'file' => $e->getFile()], 'PlateResultTraits/CarFlowClearTaskLog', CarFlowConst::CAR_FLOW_TRAITS_LOG_APPEND);
        }
    }
}