<?php

declare(strict_types=1);
/**
 * This file is part of Kuaijing Bailing.
 *
 * @link     https://www.kuaijingai.com
 * @document https://help.kuaijingai.com
 * @contact  www.kuaijingai.com 7*12 9:00-21:00
 */

namespace app\task;

use app\common\model\service\config\ConfigCustomizationService;
use app\community\model\service\HouseNewCashierService;
use yunwuxin\cron\Task;

class AutomaticCallPlanTask extends Task
{
    public $timeout = 7200;
    public function configure ()
    {
        $this->dailyAt('00:01');   //指定时间执行 每天凌晨01分钟执行
    }

    public function execute ()
    {
        set_time_limit(0);
        
        try {
            $configCustomizationService=new ConfigCustomizationService();
            $is_aihorse_customized=$configCustomizationService->getAihorseCustomizedJudge();
            if($is_aihorse_customized){
                fdump_api(['execute_time'=>date('Y-m-d H:i:s'),'autoCreateRoomRateSummaryRecord'], '000automaticCall');
                $houseNewTransactionSummaryService= new \app\community\model\service\HouseNewTransactionSummaryService();
                $houseNewTransactionSummaryService->autoCreateRoomRateSummaryRecord();
            }
            $houseNewCashierService= new HouseNewCashierService();
            fdump_api(['execute_time'=>date('Y-m-d H:i:s'),'start'], '000automaticCall',true);
            $houseNewCashierService->automaticCall(true);
            fdump_api(['execute_time'=>date('Y-m-d H:i:s'),'end'], '000automaticCall',true);
        }catch (Exception $e){
            fdump_api(['AutomaticCallPlan执行失败', 'err' => $e->getMessage()], '000automaticCall');
        }
    }
}