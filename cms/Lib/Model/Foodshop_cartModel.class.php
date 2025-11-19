<?php
class Foodshop_cartModel extends Model
{
	public function count_price($goods_list)
	{
		$price = 0;
		foreach ($goods_list as $info) {
		    if (empty($info['package_id'])) {
			    $price += $info['price'] * $info['num'];
			} elseif ($info['ishide']) {//计算修改后的套餐存储方式
			    $price += $info['price'] * $info['num'];
			}
		}
		$price = max(0, $price);
		return $price;
	}

	public function getGoodsList($uid,$store_id)
	{
		$goods_list = $this->where(['uid'=>$uid,'store_id'=>$store_id])->select();
		return $goods_list;
	}


	

	



}
?>
