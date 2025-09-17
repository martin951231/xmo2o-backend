<?php


namespace app\mall\model\service\activity;

use app\mall\model\db\MallPrepareOrder;

class MallPrepareOrderService
{
    /**查询预售活动商品定金/尾款支付单数
     * @param $where
     * @param string $field
     * @return array
     */
    public function getPrepareOrderList($where, $field = '*')
    {
        $list = (new MallPrepareOrder())->getList($where, $field);
        return $list;
    }
}