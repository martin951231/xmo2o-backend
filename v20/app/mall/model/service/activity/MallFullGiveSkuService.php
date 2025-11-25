<?php


namespace app\mall\model\service\activity;


use app\mall\model\db\MallFullGiveGiftSku;

class MallFullGiveSkuService
{
    /**
     * @param $goods_id
     * @param $store_id
     * @return array
     * @author mrdeng
     * 获取活动id
     */
    public function getActId($goods_id,$store_id){
        return (new MallFullGiveGiftSku())->getActId($goods_id,$store_id);
    }

    /** 添加数据 获取插入的数据id
     * Date: 2020-10-16 15:42:29
     * @param $data
     * @return int|string
     */
    public function addGiveSku($data) {
        return (new MallFullGiveGiftSku())->addOne($data);
    }

    /** 更新数据
     * Date: 2020-10-16 15:42:29
     * @param array $data
     * @param array|mixed $where
     * @return boolean
     */
    public function updateGiveSku($data,$where) {
        return (new MallFullGiveGiftSku())->updateOne($data,$where);
    }
}