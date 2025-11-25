<?php

namespace app\shop\model\db;

use think\Model;

/**
 * 订单商品
 * @author: 张涛
 * @date: 2020/9/15
 */
class ShopOrderDetail extends Model
{
    public function getGoodsDetail($orderId)
    {
        return self::where('order_id', $orderId)
            ->field('goods_id, name as goods_name, price, num as goods_num')
            ->select();
    }
}