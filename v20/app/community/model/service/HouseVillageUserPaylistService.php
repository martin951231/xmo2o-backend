<?php


namespace app\community\model\service;

use app\community\model\db\HouseVillageUserPaylist;

class HouseVillageUserPaylistService
{
    /**
     * 添加记录
     * @author lijie
     * @date_time 2020/11/02
     * @param $data
     * @return int|string
     */
    public function addPaylist($data)
    {
        $db_house_village_user_paylist = new HouseVillageUserPaylist();
        $res = $db_house_village_user_paylist->addOne($data);
        return $res;
    }
}