<?php
/*积分返现

 * */
class plan_score_pay_back extends plan_base{
	public function runTask(){
		$now_time = time();
		$time_end = strtotime(date('Y-m-d',$now_time));
		$time_begin = $time_end-86400;
		$where['_string'] = "use_time>={$time_begin} AND use_time<{$time_end}";
		$all_system_take = M('Merchant_money_list')->where($where)->sum('system_take');
		$pay_back_money  = round($all_system_take*C('config.score_pay_back_percent')/100,2);
		if($pay_back_money>0){
			$all_user = M('User')->where(array('status'=>1))->select();
			$all_user_score = M('User')->where(array('status'=>1))->sum('score_count');
			$score_name = C('config.score_name');
			if($all_user_score){
				foreach($all_user as $v){
					$this->keepThread();
					if($v['score_count']>0){
						$user_score_use_percent = $v['score_count']/$all_user_score;
						$score_deducte = bcmul($pay_back_money ,$user_score_use_percent,2);
						if($res = D('User')->add_money($v['uid'],floatval($score_deducte),$score_name.'返现 '.$score_deducte.C('config.Currency_txt').'到账户余额',3)){
							D('User')->use_score($v['uid'],$score_deducte,''.$score_name.'返现，减扣'.$score_name.' '.$score_deducte.' 个');
							D('User')->add_score_recharge_money($v['uid'],$score_deducte,'保存'.$score_name.'返现记录 '.$score_deducte.C('config.Currency_txt').'');
							D('Scroll_msg')->add_msg('score_recharge',$v['uid'],'用户'.str_replace_name($v['nickname']).'于'.date('Y-m-d H:i',time()).$score_name.'返现余额');
						}
					}
				}
			}
		}
		return true;
	}

}
?>