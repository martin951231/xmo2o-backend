<?php

/*
  * 客户定制每日释放商家积分
  * @date 2021-09-09
*/
class plan_add_merchant_score extends plan_base{
		public function runTask(){
				// 获得180天内的积分记录
				$startTime = date('Ymd',strtotime("-181 day"));
				$endTime = date('Ymd',strtotime("-1 day"));
				$where['get_date'] = ['between',[$startTime, $endTime]];
				$merlist = M('Merchant_get_score')->field('mer_id')->where($where)->group('mer_id')->select();
		    foreach($merlist as $key => $val){
		        $this->keepThread();
		        $mer_id = $val['mer_id'];
		        $arr= array();
		        $arr['param'] = serialize(array('mer_id'=>$mer_id));
		        $arr['plan_time'] = 0;
		        $arr['space_time'] = 0;//子计划任务不需要每分钟执行，主计划任务会唤醒
		        $arr['add_time'] = time();
		        $arr['file'] = 'add_merchant_score_save';
		        $arr['time_type'] = 1;
		        $arr['unique_id'] = 'add_merchant_score_save_'.$mer_id;
						$arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
		        M('process_sub_plan')->data($arr)->add();
		    }

				return true;
		}	
}
?>