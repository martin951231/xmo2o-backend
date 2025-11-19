<?php
/**
 * 社区分类
 * @author weili
 * @datetime 2020/07/18
 */

namespace app\community\model\service;

use app\community\model\db\HouseVillageRepairCate;
class HouseVillageRepairCateService
{
    /**
     * Notes: 获取分类名称
     * @param:
     * @param $where
     * @param $field
     * @return mixed
     * @author: weili
     * @datetime: 2020/7/18 12:00
     */
    public function getCate($where,$field)
    {
        $dbHouseVillageRepairCate = new HouseVillageRepairCate();
        $data = $dbHouseVillageRepairCate->getRelevance($where,$field);
        return $data;
    }
}
