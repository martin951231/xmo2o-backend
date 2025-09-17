<?php
/**
 * Created by PhpStorm.
 * Author: weili
 * Date Time: 2020/7/7 11:02
 */

namespace app\community\model\service;
use app\community\model\db\HouseVillageConfig;
class HouseVillageConfigService
{
    /**
     * 添加数据
     * @author weili
     * @datetime: 2020/7/7 11:04
     * @param array $data
     * @return int
     **/
    public function addData($data)
    {
        $houseVillageConfigDb = new HouseVillageConfig();
        $res = $houseVillageConfigDb->addOne($data);
        return $res;
    }

    /**
     * 获取数据
     * @author lijie
     * @date_time 2021/06/24
     * @param array $where
     * @param bool $field
     * @return array
     */
    public function getConfig($where=[],$field=true)
    {
        $houseVillageConfigDb = new HouseVillageConfig();
        $data = $houseVillageConfigDb->getOne($where,$field);
        return $data;
    }
}
