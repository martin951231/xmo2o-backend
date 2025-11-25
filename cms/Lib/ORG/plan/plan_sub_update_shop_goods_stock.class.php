<?php
/*
 * 更新商品库存子计划任务
 * date 2020-04-13
 * author hengtingmei
 */
class plan_sub_update_shop_goods_stock extends plan_base{
	public function runTask($param){
		if (!$param['store_id']) {
			return true;
		}

		$now_time = time();
		$today = date('Ymd');

		// 查找商品
		$where = [];
		$where['store_id'] = $param['store_id'];
		$where['sell_day'] = ['neq',$today];
		$goods_list = M('Shop_goods')->field('goods_id')->where($where)->select();
		if (!$goods_list) {
			return true;
		}

		foreach ($goods_list as $key => $value) {
			$arr= array();
			$arr['param'] = serialize(array('goods_id'=>$value['goods_id']));
			$arr['plan_time'] = 0;
			$arr['space_time'] = 0;
			$arr['add_time'] = $now_time;
			$arr['file'] = 'sub_update_shop_goods_stock_do';
			$arr['time_type'] = 1;
			$arr['unique_id'] = 'update_shop_goods_stock_do_'.$value['goods_id'];
			$arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
			M('process_sub_plan')->data($arr)->add();
		}
		return true;
	}
}
?>