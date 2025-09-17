<?php
class ShopOrderDetailModel extends Model
{
	public function getGoodsDetail($orderId)
	{
		return self::where(['order_id' => $orderId])
			->field('goods_id, name as goods_name, price, num as goods_num')
			->select();
	}
}
