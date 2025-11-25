<?php
/**
 * Class plan_employee_card_coupon_outdate 将已过期的未核销的消费券转为积分->主计划任务
 */
class plan_employee_card_coupon_outdate extends plan_base{
    //主计划任务
    public function runTask(){

        $condition = [];
        $condition['status'] = 1; 
        $list = M('Employee_card_coupon')->where($condition)->group('overdue_time')->select();
	 
        if(empty($list)){
            return true;
        }
        $now_time = time();
        foreach($list as $key =>$val){
            $this->keepThread(); 
            $arr= array();
            $arr['param'] = serialize(array('overdue_time'=>$val['overdue_time']));
            $arr['plan_time'] = $now_time;
            $arr['space_time'] = 0;
            $arr['add_time'] = $now_time;
            $arr['file'] = 'sub_employee_card_coupon_outdate';
            $arr['time_type'] = 0;
            $arr['unique_id'] = 'sub_employee_card_coupon_outdate_'.$val['mer_id'];
			$arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
            M('process_sub_plan')->data($arr)->add();
        }
        return true;
    }
}