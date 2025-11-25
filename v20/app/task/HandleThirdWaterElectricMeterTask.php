<?php

namespace app\task;

use app\common\model\service\config\ConfigCustomizationService;
use yunwuxin\cron\Task;

class HandleThirdWaterElectricMeterTask extends Task
{
    public function configure()
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
        $this->hourly();  //每个小时执行一次
    }

    public function execute()
    {
        $configCustomizationService = new ConfigCustomizationService();
        $is_yumenlao_special_meter = $configCustomizationService->getFuncYumenLaoSpecialMeter();

        if (!$is_yumenlao_special_meter) {
            return true;
        }
        $hourly = date('H');
        $hourly = intval($hourly);

        $special_meter_easy_key = cfg('special_meter_easy_key');
        if ($special_meter_easy_key) {
            try {
                fdump_api([__LINE__, 'xtime' => time(), 'hourly' => $hourly], 'easymeter/handleMeterOrderTask', 1);
                $nbLotEasyMeterApiService = new \app\community\model\service\Device\NbLotEasyMeterApiService();
                if (in_array($hourly, [2, 4, 6, 8, 9]) && $special_meter_easy_key) {
                    /****每天的2,4,6,8小时检测生成账单*******/
                    $ret = $nbLotEasyMeterApiService->handleWaterElectricOrder();
                    fdump_api([__LINE__, 'handleWaterElectricOrder', 'ret' => $ret, 'hourly' => $hourly, 'xtime' => time()], 'easymeter/handleMeterOrderTask', 1);
                }
            } catch (\Exception $ee) {
                fdump_api([__LINE__, $ee->getMessage()], 'easymeter/handleMeterOrderTask', 1);
            }
        }

        $jisheyun_electric_authorize_code = cfg('jisheyun_electric_authorize_code');
        $jisheyun_electric_project_id = cfg('jisheyun_electric_project_id'); //电表项目id

        $jisheyun_water_authorize_code = cfg('jisheyun_water_authorize_code');
        $jisheyun_water_project_id = cfg('jisheyun_water_project_id'); //水表项目id
        if (($jisheyun_electric_authorize_code && $jisheyun_electric_project_id) || ($jisheyun_water_authorize_code && $jisheyun_water_project_id)) {
            try {
                fdump_api([__LINE__, 'xtime' => time(), 'hourly' => $hourly], 'jisheyun/handleMeterOrderTask', 1);
                $jiSheYunMeterApiService = new \app\community\model\service\Device\JiSheYunMeterApiService();
                if (in_array($hourly, [1, 3, 5, 7, 9])) {
                    /****每天的1,3,5,7小时检测生成账单*******/
                    $ret = $jiSheYunMeterApiService->handleWaterElectricOrder();
                    fdump_api([__LINE__, 'handleWaterElectricOrder', 'ret' => $ret, 'hourly' => $hourly, 'xtime' => time()], 'jisheyun/handleMeterOrderTask', 1);
                }
                if (in_array($hourly, [21, 22, 23, 0])) {
                    //检查电表的third_meter_id
                    $ret = $jiSheYunMeterApiService->checkMeterDetail();
                    fdump_api([__LINE__, 'checkMeterDetail', 'ret' => $ret, 'xtime' => time()], 'jisheyun/checkMeterDetail', 1);
                }
            } catch (\Exception $ee) {
                fdump_api([__LINE__, $ee->getMessage()], 'jisheyun/handleMeterOrderTask', 1);
            }
        }

        $lingyin_meter_pfaccount = cfg('lingyin_meter_pfaccount');
        $lingyin_meter_pfpwd = cfg('lingyin_meter_pfpwd');
        if ($lingyin_meter_pfaccount && $lingyin_meter_pfpwd) {
            try {
                fdump_api([__LINE__, 'xtime' => time(), 'hourly' => $hourly], 'lingyinmeter/handleMeterOrderTask', 1);
                $lingYinPfMeterApiService = new \app\community\model\service\Device\LingYinPfMeterApiService();
                if (in_array($hourly, [1, 2, 3, 4, 5])) {
                    /****每天的1, 2, 3, 4, 5小时检测生成账单*******/
                    $ret = $lingYinPfMeterApiService->handleWaterElectricOrder();
                    fdump_api([__LINE__, 'handleWaterElectricOrder', 'ret' => $ret, 'hourly' => $hourly, 'xtime' => time()], 'lingyinmeter/handleMeterOrderTask', 1);
                }
            } catch (\Exception $ee) {
                fdump_api([__LINE__, $ee->getMessage()], 'lingyinmeter/handleMeterOrderTask', 1);
            }
        }

        $boming_meter_pfaccount = cfg('boming_meter_pfaccount');    //博铭智能平台用户名
        $boming_meter_pfaccount = $boming_meter_pfaccount ? trim($boming_meter_pfaccount) : '';
        $boming_meter_pfpwd = cfg('boming_meter_pfpwd');    //博铭智能平台密码
        $boming_meter_pfpwd = $boming_meter_pfpwd ? trim($boming_meter_pfpwd) : '';
        if ($boming_meter_pfaccount && $boming_meter_pfpwd) {
            try {
                fdump_api([__LINE__, 'xtime' => time(), 'hourly' => $hourly], 'bomingmeter/handleMeterOrderTask', 1);
                $boMingPfMeterApiService = new \app\community\model\service\Device\BoMingPfMeterApiService();
                if (in_array($hourly, [4, 5, 6, 7, 8]) && ($lingyin_meter_pfaccount && $lingyin_meter_pfpwd)) {
                    /****每天的4, 5,6,7, 8小时检测生成账单*******/
                    $ret = $boMingPfMeterApiService->handleWaterElectricOrder();
                    fdump_api([__LINE__, 'handleWaterElectricOrder', 'ret' => $ret, 'hourly' => $hourly, 'xtime' => time()], 'bomingmeter/handleMeterOrderTask', 1);
                }
            } catch (\Exception $ee) {
                fdump_api([__LINE__, $ee->getMessage()], 'bomingmeter/handleMeterOrderTask', 1);
            }

        }
        
    }
}