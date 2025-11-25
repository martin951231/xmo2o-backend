<?php
/*
 * 更新体育健身秒杀商品库存 子计划任务(更新库存)
 * date 2022-02-24
 * author hengtingmei
 */
class plan_sub_update_life_tools_sport_seckill_stock_do extends plan_base{
	public function runTask($param){
		if (!$param['id']) {
			return true;
		}

		$today = date('Ymd');

		// 查找商品
		$where = [];
		$where['id'] = $param['id'];
		$where['sale_day'] = ['neq',$today];
		$now_goods = M('Life_tools_sports_seconds_kill_ticket_sku')->field('id,day_stock_num,day_original_stock_num')->where($where)->find();

		if (!$now_goods ){
			return true;
		}

		// 更新库存
		$data = [];
		$data['day_stock_num'] = $now_goods['day_original_stock_num']; // 库存改为原始库存
		$data['sale_day'] = $today;
		M('Life_tools_sports_seconds_kill_ticket_sku')->where(['id'=>$param['id']])->save($data);
		return true;
	}
}
?>