<?php
/**
 * Created by PigCMS.
 * User: zhubaodi.
 * Date: 2021/07/5.
 * Time: 15:43.
 */

/**
 * 社区欠费自动发送微信通知提醒 一天一次
 */
class plan_sub_house_new_notice_1  extends plan_base
{
    //子计划任务
    public function runTask($param)
    {
	    if (!empty(C('DISABLE_OLD_ALL_DB_PLAN')) && C('DISABLE_OLD_ALL_DB_PLAN') == true){
		    return ;
	    }
        invoke_v20_service('\app\community\model\service\HouseNewCashierService/getArrearsList');
        return true;
    }

}