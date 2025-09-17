<?php
/*
 * 更新体育健身秒杀商品库存 子计划任务
 * date 2022-02-24
 * author hengtingmei
 */
class plan_sub_update_life_tools_sport_seckill_stock extends plan_base{
	public function runTask($param){
		if (!$param['act_id']) {
			return true;
		}

		$now_time = time();
		$today = date('Ymd');

		// 查找商品
		$where = [];
		$where['act_id'] = $param['act_id'];
		$where['sale_day'] = ['neq',$today];
		$goods_list = M('Life_tools_sports_seconds_kill_ticket_sku')->field('id')->where($where)->select();
		if (!$goods_list) {
			return true;
		}

		foreach ($goods_list as $key => $value) {
			$arr= array();
			$arr['param'] = serialize(array('id'=>$value['id']));
			$arr['plan_time'] = 0;
			$arr['space_time'] = 0;
			$arr['add_time'] = $now_time;
			$arr['file'] = 'sub_update_life_tools_sport_seckill_stock_do';
			$arr['time_type'] = 1;
			$arr['unique_id'] = 'sub_update_life_tools_sport_seckill_stock_do_'.$value['id'];
			$arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
			M('process_sub_plan')->data($arr)->add();
		}
        fdump($goods_list,'plan_sub_update_life_tools_sport_seckill_stock',1);;
		return true;
	}
}
?>