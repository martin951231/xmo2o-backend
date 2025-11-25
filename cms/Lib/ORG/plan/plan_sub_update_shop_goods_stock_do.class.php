<?php
/*
 * 更新外卖商品库存-子计划任务（更新库存）
 * date 2020-04-13
 * author hengtingmei
 */
class plan_sub_update_shop_goods_stock_do extends plan_base{
	public function runTask($param){
		if (!$param['goods_id']) {
			return true;
		}

		$today = date('Ymd');

		// 查找商品
		$where = [];
		$where['goods_id'] = $param['goods_id'];
		$where['sell_day'] = ['neq',$today];
		$now_goods = M('Shop_goods')->field('goods_id,store_id,original_stock,original_weight')->where($where)->find();

		// 店铺
		$where = [];
		$where['stock_type'] = 0; //每日更新
		$where['store_id'] = $now_goods['store_id']; 
		$store = M('Merchant_store_shop')->field('`store_id`')->where($where)->find();
		if (!$now_goods || !$store) {
			return true;
		}

		// 更新库存和销量
		$data = [];
		$data['stock_num'] = $now_goods['original_stock']; // 库存改为原始库存
		$data['stock_weight'] = $now_goods['original_weight']; // 库存改为原始库存
		$data['sell_day'] = $today;
		$data['today_sell_count'] = 0; // 今日销量0
		$data['today_sell_spec'] = ''; // 今日规格销量0
		$data['today_seckill_count'] = 0; // 今日限时优惠销量0
		M('Shop_goods')->where(['goods_id'=>$param['goods_id']])->save($data);
		return true;
	}
}
?>