<?php
/**
 * 快店订单
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/6/1 11:44
 */

namespace app\common\model\service;

use app\common\model\db\ShopOrder;

class ShopOrderService
{
    public $shopOrderObj = null;
    public function __construct()
    {
        $this->shopOrderObj = new ShopOrder();
    }

    /**
     * 快店订单信息
     * User: chenxiang
     * Date: 2020/6/1 11:54
     * @param string $field
     * @param array $where
     * @param string $group
     * @return mixed
     */
    public function getList($field = '', $where = [], $group = '') {
        $result = $this->shopOrderObj->getList($field, $where, $group);
        return $result;
    }

    /**
     * 获取快店订单信息
     * User: chenxiang
     * Date: 2020/6/1 19:02
     * @param array $where
     * @param bool $field
     * @return array|\think\Model|null
     */
    public function getOne($where = [], $field = true) {
        $result = $this->shopOrderObj->getOne($where, $field);
        return $result;
    }
}