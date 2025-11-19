<?php
/*
 * 更新餐饮商品库存-子计划任务（更新库存）
 * date 2020-08-14
 * author hengtingmei
 */
class plan_sub_update_foodshop_goods_stock_do extends plan_base{
	public function runTask($param){
		if (!$param['goods_id']) {
			return true;
		}

		$today = date('Ymd');

		// 查找商品
		$where = [];
		$where['goods_id'] = $param['goods_id'];
		$where['sell_day'] = ['neq',$today];
		$now_goods = M('Foodshop_goods_library')->field('goods_id,store_id,spec_original_stock')->where($where)->find();

        fdump($now_goods,'plan_sub_update_shop_goods_stock_do',1);;
		if (!$now_goods ){
			return true;
		}

		// 更新库存和销量
		$data = [];
		$data['spec_stock'] = $now_goods['spec_original_stock']; // 库存改为原始库存
		$data['sell_day'] = $today;
		M('Foodshop_goods_library')->where(['goods_id'=>$param['goods_id'],'store_id'=>$now_goods['store_id']])->save($data);
        fdump(M('Foodshop_goods_library')->_sql(),'plan_sub_update_shop_goods_stock_do',1);;
		return true;
	}
}
?>