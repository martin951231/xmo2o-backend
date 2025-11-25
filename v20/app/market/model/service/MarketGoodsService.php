<?php

/**
 * 批发市场商品
 * Author: hengtingmei
 * Date Time: 2020/12/18
 */

namespace app\market\model\service;

use app\market\model\db\MarketGoods;

class MarketGoodsService
{
    public $marketGoodsModel = null;
    public function __construct()
    {
        $this->marketGoodsModel = new MarketGoods();
    }

    /**
     * 根据条件返回
     * @param $where array 条件
     * @return array
     */
    public function getCount($where){
        $count = $this->marketGoodsModel->getCount($where);
        if(!$count) {
            return 0;
        }
        return $count;
    }
}