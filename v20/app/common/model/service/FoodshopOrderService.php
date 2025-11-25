<?php
/**
 * 餐饮订单
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/6/1 16:30
 */

namespace app\common\model\service;

use app\common\model\db\FoodshopOrder;

class FoodshopOrderService
{
    public $foodshopOrderObj = null;
    public function __construct()
    {
        $this->foodshopOrderObj = new FoodshopOrder();
    }

    /**
     * 获取餐饮订单列表
     * User: chenxiang
     * Date: 2020/6/1 16:55
     * @param array $where
     * @param bool $field
     * @return \think\Collection
     */
    public function getOrderList($where = [], $field = true, $group = '', $join = '')
    {
        $result = $this->foodshopOrderObj->getOrderList($where, $field, $group, $join);
        return $result;
    }

    /**
     * 更新某个字段信息
     * User: chenxiang
     * Date: 2020/6/1 17:15
     * @param array $where
     * @param string $field
     * @param string $value
     * @return FoodshopOrder
     */
    public function setField($where = [], $field = '', $value= '')
    {
        $result = $this->foodshopOrderObj->setField($where, $field, $value);
        return $result;
    }

}