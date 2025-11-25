<?php
/**
 * Created by PhpStorm.
 * Author: weili
 * Date Time: 2020/7/8 11:38
**/

namespace app\community\model\service;
use app\community\model\db\HouseMenuNew;

class HouseMenuNewService
{
    public function getMenuList($where,$field =true){
        if(empty($where)){
            return false;
        }
        $houseMenuNewDb = new HouseMenuNew();
        $houseMenuNew=$houseMenuNewDb->getList($where,$field);
        if (!$houseMenuNew || $houseMenuNew->isEmpty()) {
            $houseMenuNew = [];
        }else{
            $houseMenuNew=$houseMenuNew->toArray();
        }
        return $houseMenuNew;
    }
}
