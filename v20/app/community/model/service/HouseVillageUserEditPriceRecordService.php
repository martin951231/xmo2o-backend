<?php


namespace app\community\model\service;

use app\community\model\db\HouseVillageUserEditPriceRecord;

class HouseVillageUserEditPriceRecordService
{
    /**
     * 添加记录
     * @author lijie
     * @date_time 2020/11/30
     * @param $data
     * @return int|string
     */
    public function addRecord($data)
    {
        $db_house_village_user_edit_price_record = new HouseVillageUserEditPriceRecord();
        $res = $db_house_village_user_edit_price_record->addOne($data);
        return $res;
    }
}