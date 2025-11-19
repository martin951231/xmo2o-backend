<?php

namespace app\community\model\service;

use app\community\model\db\HouseVillagePropertyStandard;
use app\community\model\db\HouseVillagePropertyStandardBind;

class HouseVillagePropertyFeeService
{
    private $db_house_village_property_standard = '';
    private $de_house_village_property_standard_bind = '';

    public function __construct()
    {
        $this->db_house_village_property_standard = new HouseVillagePropertyStandard();
        $this->de_house_village_property_standard_bind = new HouseVillagePropertyStandardBind();
    }

    /**
     * 获取物业费标准信息
     * @author lijie
     * @date_time 2020/11/10
     * @param $where
     * @param bool $field
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getStandard($where,$field=true)
    {
        $data = $this->db_house_village_property_standard->getOne($where,$field);
        return $data;
    }

    /**
     * 获取房间绑定的物业费标准
     * @author lijie
     * @date_time 2020/10/11
     * @param $where
     * @param bool $field
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getBindStandard($where,$field=true)
    {
        $data = $this->de_house_village_property_standard_bind->getOne($where,$field);
        return $data;
    }

    /**
     * 获取当月物业费收费标准
     * @author lijie
     * @date_time 2020/07/30
     * @param $chargingStandard
     * @param $month
     * @return mixed
     */
    public function getNowPropertyFee($chargingStandard,$month)
    {
        foreach ($chargingStandard as $value) {
            if (strtotime($value['date_month']) <= $month && $value['date_month'] != '') {
                $price = $value['price'];
            }
        }
        return isset($price) ? $price : 0;
    }
}