<?php


namespace app\mall\model\service\activity;


use app\mall\model\db\MallShippingActGoods;

class MallShippingActGoodsService
{
    /**批量添加满包邮商品
     * @param $data
     * @return int
     */
    public function addAllShippingActGoods($data)
    {
        return (new MallShippingActGoods())->addAll($data);
    }

    /**获取满包邮商品信息
     * @param $act_id
     * @return array
     */
    public function getShippingActGoodsByActId($act_id)
    {
        return (new MallShippingActGoods())->getGoodsByActId($act_id);
    }

    /**删除满包邮活动商品
     * @param $where
     * @return bool
     */
    public function delShippingAct($where)
    {
        return (new MallShippingActGoods())->delShippingActGoods($where);
    }
}