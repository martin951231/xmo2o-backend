<?php
/**
 * Class plan_employee_card_send_coupon 员工卡消费券发券->主计划任务
 */
class plan_employee_card_send_coupon extends plan_base{
    //主计划任务
    public function runTask(){

        $list = M('Employee_card_coupon')->where(['status'=>1])->group('mer_id')->select();
	 
        if(empty($list)){
            return true;
        }
        $now_time = time();
        foreach($list as $key =>$val){
            $this->keepThread(); 
            $arr= array();
            $arr['param'] = serialize(array('mer_id'=>$val['mer_id']));
            $arr['plan_time'] = $now_time;
            $arr['space_time'] = 0;
            $arr['add_time'] = $now_time;
            $arr['file'] = 'sub_employee_card_send_coupon';
            $arr['time_type'] = 0;
            $arr['unique_id'] = 'sub_employee_card_send_coupon_'.$val['mer_id'];
			$arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
            M('process_sub_plan')->data($arr)->add();
        }
        return true;
    }
}