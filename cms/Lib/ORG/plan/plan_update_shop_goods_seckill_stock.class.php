<?php
/*
 * 更新外卖商品限时优惠库存
 * date 2020-04-13
 * author hengtingmei
 */
class plan_update_shop_goods_seckill_stock extends plan_base{
	public function runTask(){
		$now_time=time();

		$where = [];
		$where['stock_type'] = 0; //每日更新库存
		$where['is_del'] = 0;
		$where['end_date'] = ['gt' ,date('Y-m-d')]; //活动未结束的
		// 未更新过
		$where['update_stock_date'] = ['neq' ,date('Y-m-d')];
		$goods_list = M('Time_limited_discount_goods')->where($where)->field('`id`')->select();
			fdump(M('Time_limited_discount_goods')->_sql(),'plan_update_shop_goods_seckill_stock',1);
		
		foreach ($goods_list as $key => $value) {
			$arr= array();
			$arr['param'] = serialize(array('id'=>$value['id']));
			$arr['plan_time'] = 0;
			$arr['space_time'] = 0;
			$arr['add_time'] = $now_time;
			$arr['file'] = 'sub_update_shop_goods_seckill_stock';
			$arr['time_type'] = 1;
			$arr['unique_id'] = 'update_shop_goods_seckill_stock_'.$value['id'];
			$arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
			M('process_sub_plan')->data($arr)->add();
			fdump(M('process_sub_plan')->_sql(),'plan_update_shop_goods_seckill_stock',1);
		}
		return true;
	}
}
?>