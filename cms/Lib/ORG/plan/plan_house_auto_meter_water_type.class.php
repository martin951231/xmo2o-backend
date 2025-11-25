<?php

class plan_house_auto_meter_water_type extends plan_base{
    public function runTask(){
        //todo 拉取设备方数据，获取水表冷/热水类型
        invoke_v20_service('\app\community\model\service\CustomizeMeterWaterReadingService/getMeterWaterType');
        return true;
    }
}
?>