<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2021/9/14
 * Time: 18:24
 */

class plan_apDevice_status extends plan_base
{
    public function runTask(){
	    if (!empty(C('DISABLE_OLD_ALL_DB_PLAN')) && C('DISABLE_OLD_ALL_DB_PLAN') == true){
		    return ;
	    }
        $model = '\app\community\model\service\ApDeviceService/getDeviceStatus';
        invoke_v20_service($model);
        return true;
    }
}