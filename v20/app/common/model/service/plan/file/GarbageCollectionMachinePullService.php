<?php


namespace app\common\model\service\plan\file;


use app\community\model\service\Device\GarbageCollectionMachineService;
use app\merchant\model\db\CardNewDepositGoods;
use app\merchant\model\db\CardNewDepositGoodsBindUser;

class GarbageCollectionMachinePullService
{
    /**
     * 计划任务执行--每天定时更新设备列表
     * @param  string  $param 参数
     */

    public function runTask()
    {
        try{
            (new GarbageCollectionMachineService())->pullMachine();
        } catch (\Exception $e) {
            fdump_api($e->getMessage(),'garbage_collection/runTaskError',true);
        }
        return true;
    }
}