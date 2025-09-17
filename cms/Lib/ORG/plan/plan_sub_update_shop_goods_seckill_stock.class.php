<?php
/*
 * 更新商品库存
 * date 2020-04-13
 * author hengtingmei
 */
class plan_sub_update_shop_goods_seckill_stock extends plan_base{
	public function runTask(){
		if (!$param['id']) {
			return true;
		}

		$today = date('Y-m-d');
		
		$where = [];
		//库存类型每日更新
		$where['stock_type'] = 0;
		// 未更新过
		$where['update_stock_date'] = ['neq' ,$today]; 
		$record = M('Time_limited_discount_goods')->field('`id`,`goods_id`,`stock_type`,`update_stock_date`,`is_spec`')->where($where)->find();
		if (!$record) {
			return true;
		}
		D('Time_limited_discount_goods')->update_stock_everyday($record);
		
		return true;
	}
}
?>