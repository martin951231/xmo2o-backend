<?php
class CartModel extends Model{

    public function getShopGoodsList($where = [])
    {
        return $this->field('c.*,g.sort_id')
            ->join("as c left join ".C("DB_PREFIX")."shop_goods as g on c.goods_id = g.goods_id")
            ->where($where)
            ->select();
    }
}