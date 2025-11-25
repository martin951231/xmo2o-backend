<?php

/**
 * 餐饮套餐相关核销套餐
 * Created by PhpStorm.
 * Author: 钱大双
 * Date Time: 2020年12月30日13:46:39
 */

namespace app\group\model\service;

use app\group\model\db\GroupFoodshopPackageData as GroupFoodshopPackageDataModel;


class GroupFoodshopPackageDataService
{
    /**获取餐饮套餐核销记录
     * @param $where
     * @return array
     */
    public function getPackageVerificList($where)
    {
        $res = (new GroupFoodshopPackageDataModel())->getPackageVerificList($where);
        return $res;
    }
}