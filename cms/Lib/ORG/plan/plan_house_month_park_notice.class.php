<?php

class plan_house_month_park_notice extends plan_base{
    public function runTask(){
        //todo 月租车临期 模板消息/短信提醒
        invoke_v20_service('\app\community\model\service\HouseNewParkingService/triggerParkMonthExpiresNotice');
        return true;
    }
}

?>