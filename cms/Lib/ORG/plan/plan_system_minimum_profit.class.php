<?php
/*平台最低利润

 * */
class plan_system_minimum_profit extends plan_base{
	
	public function runTask(){
		$now_time = time();
		$time_end = strtotime(date('Y-m-d',$now_time));
		$time_begin = $time_end-86400;
		$where['_string'] = "use_time>={$time_begin} AND use_time<{$time_end}";
		$all_system_take = M('Merchant_money_list')->where($where)->sum('system_take');
		if ($all_system_take>0) {
			$this->keepThread();
			$data['money'] = $all_system_take;
			$data['take_date'] = $time_begin;
			// 用户积分返现
			$data['score_pay_back']  = C('config.score_pay_back_percent') > 0 ? C('config.score_pay_back_percent') : 0;

			// 商户积分返现 
			$data['score_pay_back_mer']  = C('config.score_pay_back_percent_mer') > 0 ? C('config.score_pay_back_percent_mer') : 0;

			// 用户分佣
			$where['_string'] = "add_time>={$time_begin} AND add_time<{$time_end}";
			$all_user_spread = M('User_spread_list')->where($where)->sum('money');
			$data['user_spread'] = round($all_user_spread/$all_system_take*100,2);

			// 商户分佣
			$all_mer_spread = M('Merchant_spread_list')->where($where)->sum('money');
			$data['mer_spread'] = round($all_mer_spread/$all_system_take*100,2);

			// 商户备用金 
			$data['reserve_fund'] = C('config.reserve_fund_percent') > 0 ? C('config.reserve_fund_percent') : 0;

			// 平台最低利润
			$data['minimum_profit'] = 100 - $data['score_pay_back'] - $data['score_pay_back_mer'] - $data['user_spread'] - $data['mer_spread'] - $data['reserve_fund'];

			$data['add_time'] = time();

			D('Merchant_minimum_profit')->add($data);
		}
		return true;
	}

}
?>