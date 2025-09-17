<?php
/*商家积分返现

 * */
class plan_score_pay_back_mer extends plan_base{
	public function runTask(){
		$now_time = time();
		$time_end = strtotime(date('Y-m-d',$now_time));
		$time_begin = $time_end-86400;
		$where['_string'] = "use_time>={$time_begin} AND use_time<{$time_end}";
		$all_system_take = M('Merchant_money_list')->where($where)->sum('system_take');
		$pay_back_money  = round($all_system_take*C('config.score_pay_back_percent_mer')/100,2);
		if($pay_back_money>0){
			$all_merchant = M('Merchant')->where(array('status'=>1))->select();
			$all_merchant_score = M('Merchant')->where(array('status'=>1))->sum('system_score');
			$score_name = C('config.score_name');
			if($all_merchant){
				foreach($all_merchant as $v){
					$this->keepThread();
					if($v['system_score']>0){
						$user_score_use_percent = $v['system_score']/$all_merchant_score;
						$score_deducte = bcmul($pay_back_money ,$user_score_use_percent,2); // 返现金额
						$res = D('Merchant')->add_money_by_score($v['mer_id'],floatval($score_deducte),$score_name.'返现 '.$score_deducte.C('config.Currency_txt').'到商户余额',3);
						if(!$res['error']){
							D('Merchant')->use_system_score($v['mer_id'],$score_deducte,''.$score_name.'返现，减扣'.$score_name.' '.$score_deducte.' 个');
						}
					}
				}
			}
		}
		return true;
	}

}
?>