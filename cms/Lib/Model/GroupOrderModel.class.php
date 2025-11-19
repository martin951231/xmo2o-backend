<?php
class GroupOrderModel extends Model
{
    public function getGoodsDetail($orderId)
    {
        return self::where(['order_id' => $orderId])
            ->field('group_id goods_id, order_name as goods_name, price, num as goods_num')
            ->select();
    }
}
