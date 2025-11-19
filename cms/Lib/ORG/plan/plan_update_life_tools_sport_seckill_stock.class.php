<?php
/*
 * 更新体育健身秒杀商品库存 查找庫存類型為每日更新的店铺并写入子计划任务
 * date 2020-08-04
 * author hengtingmei
 */
class plan_update_life_tools_sport_seckill_stock extends plan_base{
	public function runTask(){
		$now_time=time();
		
		//库存类型每日更新
		$where = [
            'k.status' => ['lt', 2],
			'k.end_time' => ['gt', time()],
			'k.start_time' => ['lt', time()],
			'k.type' => 'limited',
			'k.is_del' => 0,
			'd.stock_type' => 2
		];
		$act_list = M('Life_tools_sports_seconds_kill')->join('as k join '.C('DB_PREFIX').'life_tools_sports_seconds_kill_detail as d on d.id = k.act_id')->field('d.id')->where($where)->select();
		foreach ($act_list as $key => $value) {
			$arr= array();
			$arr['param'] = serialize(array('act_id'=>$value['id']));
			$arr['plan_time'] = 0;
			$arr['space_time'] = 0;
			$arr['add_time'] = $now_time;
			$arr['file'] = 'sub_update_life_tools_sport_seckill_stock';
			$arr['time_type'] = 1;
			$arr['unique_id'] = 'sub_update_life_tools_sport_seckill_stock'.$value['id'];
			$arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
			M('process_sub_plan')->data($arr)->add();
		}
		fdump($act_list,'plan_update_life_tools_sport_seckill_stock');;
		return true;
	}
}
?>