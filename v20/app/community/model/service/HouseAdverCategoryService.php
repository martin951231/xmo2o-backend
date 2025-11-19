<?php


namespace app\community\model\service;

use app\community\model\db\HouseAdverCategory;

class HouseAdverCategoryService
{
    /**
     * 获取广告位分类
     * @author lijie
     * @date_time 2020/11/30
     * @param $where
     * @param bool $field
     * @return mixed
     */
    public function getOne($where,$field=true)
    {
        $db_house_village_adver_category = new HouseAdverCategory();
        $data = $db_house_village_adver_category->getFind($where,$field);
        return $data;
    }
}