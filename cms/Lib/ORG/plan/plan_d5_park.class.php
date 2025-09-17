<?php

class plan_d5_park extends plan_base{
    public function runTask(){
        //todo D5停车设备 ParkMonth
        invoke_v20_service('\app\community\model\service\Park\D5Service/villageD5Park');
        return true;
    }
}
?>