<?php


namespace app\mall\model\service\activity;


use app\mall\model\db\MallFullMinusDiscountActGoods;

class MallFullMinusDiscountActGoodsService
{
	/**批量添加满减满折商品
     * @param $data
     * @return int
     */
    public function addAllDiscountActGoods($data)
    {
        return (new MallFullMinusDiscountActGoods())->addAll($data);
    }

    /**删除满减满折活动商品
     * @param $where
     * @return bool
     */
    public function delDiscountActGoods($where)
    {
        return (new MallFullMinusDiscountActGoods())->delActGoods($where);
    }


    /**获取满减满折商品
     * @param $data
     * @return array
     */
    public function getDiscountActGoodsByActId($where,$fields)
    {
        return (new MallFullMinusDiscountActGoods())->getInfo($where,$fields);
    }
}