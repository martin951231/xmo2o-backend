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

    use app\common\model\service\config\ConfigCustomizationService;
    use app\community\model\service\CustomizeMeterWaterReadingService;
	use yunwuxin\cron\Task;

	class HandleUserWaterElectricUseTask extends Task
	{
		public function configure ()
		{
			/*
		    *$this->daily();  //按天执行
            $this->everyMinute();  //每分钟
			$this->everyTenMinutes();  //每十分钟
			$this->dailyAt('05:00');   //指定时间执行
			$this->dailyAt('01:30');   //指定时间执行 每天凌晨一点半
			$this->everyThirtyMinutes(); //每半个小时检测一次
			$this->hourly();  //每个小时执行一次
			*/
            $this->everyThirtyMinutes(); //每半个小时检测一次
		}

		public function execute ()
		{
            $customized_meter_reading = cfg('customized_meter_reading');
            $customized_meter_reading=$customized_meter_reading? intval($customized_meter_reading):0;
            if($customized_meter_reading>0){
                $meterWaterReadingService = new CustomizeMeterWaterReadingService();
                $meterWaterReadingService->handleUserWaterElectricUse(); //抄表后 多长时间内订单未支付断电 断水
            }
            $configCustomizationService= new ConfigCustomizationService();
            $is_yumenlao_special_meter=$configCustomizationService->getFuncYumenLaoSpecialMeter();
            $special_meter_easy_key = cfg('special_meter_easy_key');
            if($is_yumenlao_special_meter && $special_meter_easy_key){
                try{
                    $hourly=date('H');
                    $hourly=intval($hourly);
                    fdump_api([__LINE__,'xtime'=>time(),'hourly'=>$hourly], 'easymeter/handleEasyMeterTask', 1);
                    $nbLotEasyMeterApiService=new \app\community\model\service\Device\NbLotEasyMeterApiService();
                    //多长时间内订单未支付断电 断水
                    $ret=$nbLotEasyMeterApiService->handleEasyMeterWaterElectricUse();
                    fdump_api([__LINE__,'handleEasyMeterWaterElectricUse' , 'ret'=>$ret,'xtime'=>time()], 'easymeter/handleEasyMeterTask', 1);
                }catch (\Exception $ee){
                    fdump_api([__LINE__, $ee->getMessage()], 'easymeter/handleEasyMeterTask', 1);
                }
            }
            $jisheyun_electric_authorize_code = cfg('jisheyun_electric_authorize_code');
            $jisheyun_electric_project_id = cfg('jisheyun_electric_project_id'); //电表项目id
            
            $jisheyun_water_authorize_code = cfg('jisheyun_water_authorize_code');
            $jisheyun_water_project_id = cfg('jisheyun_water_project_id'); //水表项目id
            if($is_yumenlao_special_meter && (($jisheyun_electric_authorize_code && $jisheyun_electric_project_id) || ($jisheyun_water_authorize_code && $jisheyun_water_project_id))){
                try{
                    $hourly=date('H');
                    $hourly=intval($hourly);
                    fdump_api([__LINE__,'xtime'=>time(),'hourly'=>$hourly], 'jisheyun/handleMeterUseTask', 1);
                    $jiSheYunMeterApiService=new \app\community\model\service\Device\JiSheYunMeterApiService();
                    //多长时间内订单未支付断电 断水
                    $ret=$jiSheYunMeterApiService->handleMeterWaterElectricUse();
                    fdump_api([__LINE__,'handleMeterWaterElectricUse' , 'ret'=>$ret,'xtime'=>time()], 'jisheyun/handleMeterUseTask', 1);

                }catch (\Exception $ee){
                    fdump_api([__LINE__, $ee->getMessage()], 'jisheyun/handleMeterUseTask', 1);
                }
            }
            
            $lingyin_meter_pfaccount = cfg('lingyin_meter_pfaccount');
            $lingyin_meter_pfpwd = cfg('lingyin_meter_pfpwd');
            if($is_yumenlao_special_meter && $lingyin_meter_pfaccount && $lingyin_meter_pfpwd){
                try{
                    $hourly=date('H');
                    $hourly=intval($hourly);
                    fdump_api([__LINE__,'xtime'=>time(),'hourly'=>$hourly], 'lingyinmeter/handleLingYinMeterWaterElectricUse', 1);
                    $lingYinPfMeterApiService=new \app\community\model\service\Device\LingYinPfMeterApiService();
                    //多长时间内订单未支付断电 断水
                    $ret=$lingYinPfMeterApiService->handleLingYinMeterWaterElectricUse();
                    fdump_api([__LINE__,'handleLingYinMeterWaterElectricUse' , 'ret'=>$ret,'xtime'=>time()], 'lingyinmeter/handleLingYinMeterWaterElectricUse', 1);
                }catch (\Exception $ee){
                    fdump_api([__LINE__, $ee->getMessage()], 'lingyinmeter/handleLingYinMeterWaterElectricUse', 1);
                }
            }
            
            $boming_meter_pfaccount = cfg('boming_meter_pfaccount');    //博铭智能平台用户名
            $boming_meter_pfaccount = $boming_meter_pfaccount ? trim($boming_meter_pfaccount) : '';
            $boming_meter_pfpwd = cfg('boming_meter_pfpwd');    //博铭智能平台密码
            $boming_meter_pfpwd = $boming_meter_pfpwd ? trim($boming_meter_pfpwd) : '';
            if ($boming_meter_pfaccount && $boming_meter_pfpwd) {
                try {
                    $hourly=date('H');
                    $hourly=intval($hourly);
                    fdump_api([__LINE__, 'xtime' => time(), 'hourly' => $hourly], 'bomingmeter/handleMeterOrderTask', 1);
                    $boMingPfMeterApiService = new \app\community\model\service\Device\BoMingPfMeterApiService();
                    //多长时间内订单未支付断电 断水
                    $ret = $boMingPfMeterApiService->handleLingYinMeterWaterElectricUse();
                    fdump_api([__LINE__,'handleLingYinMeterWaterElectricUse' , 'ret'=>$ret,'xtime'=>time()], 'bomingmeter/handleLingYinMeterWaterElectricUse', 1);
                } catch (\Exception $ee) {
                    fdump_api([__LINE__, $ee->getMessage()], 'bomingmeter/handleLingYinMeterWaterElectricUse', 1);
                }
            }
            
            return true;
		}
	}