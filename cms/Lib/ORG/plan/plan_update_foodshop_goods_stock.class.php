<?php
/*
 * 更新餐饮商品库存 查找庫存類型為每日更新的店铺并写入子计划任务
 * date 2020-08-04
 * author hengtingmei
 */
class plan_update_foodshop_goods_stock extends plan_base{
	public function runTask(){
		$now_time=time();
		
		//库存类型每日更新
		$where = [];
		$store_list = M('Merchant_store_foodshop')->field('`store_id`')->where($where)->select();
		foreach ($store_list as $key => $value) {
			$arr= array();
			$arr['param'] = serialize(array('store_id'=>$value['store_id']));
			$arr['plan_time'] = 0;
			$arr['space_time'] = 0;
			$arr['add_time'] = $now_time;
			$arr['file'] = 'sub_update_foodshop_goods_stock';
			$arr['time_type'] = 1;
			$arr['unique_id'] = 'update_foodshop_goods_stock_'.$value['store_id'];
			$arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
			M('process_sub_plan')->data($arr)->add();
		}
		fdump($store_list,'plan_update_shop_goods_stock');;
		return true;
	}
}
?>