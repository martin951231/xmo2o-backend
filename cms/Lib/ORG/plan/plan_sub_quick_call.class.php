<?php
	/*
	 *新版收费快速生成账单到期自动作废账单计划任务
	 * */
class plan_sub_quick_call extends plan_base{
	public function runTask($param){
        // invoke_v20_service('\app\community\model\service\HouseNewCashierService/discardQuickCall', $param);
        return true;
	}
}
?>