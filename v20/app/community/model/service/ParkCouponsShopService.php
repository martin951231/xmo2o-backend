<?php

/**
 * 优惠券店铺
 * @author hengtingmei
 * @datetime 2021/03/30
 */

namespace app\community\model\service;

use app\community\model\db\ParkCouponsShop;

class ParkCouponsShopService
{
    public $parkCouponsShopModel = null;
    public function __construct()
    {
        $this->parkCouponsShopModel = new ParkCouponsShop();
    }


    /**
     * 根据条件获得多条数据
     * @param $where array 条件
     * @author hengtingmei
     * @return array
     */
    public function getSome($where)
    {
        $res = $this->parkCouponsShopModel->getSome($where);
        if (!$res) {
            return [];
        }
        return $res->toArray();
    }
}
