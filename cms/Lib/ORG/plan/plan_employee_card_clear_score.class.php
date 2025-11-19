<?php
/**
 * Class plan_employee_card_clear_score 清积分->主计划任务
 */
class plan_employee_card_clear_score extends plan_base{
    //主计划任务
    public function runTask(){

        $now_time = time();
        $condition = [];
        $condition['clear_score'] = ['<>', 0];
        $condition['next_clear_time'] = ['<=', $now_time];
        $list = M('Employee_card')->where($condition)->select();
        foreach($list as $key =>$val){
            $this->keepThread(); 
            $arr= array();
            $arr['param'] = serialize(array('card_id'=>$val['card_id']));
            $arr['plan_time'] = $now_time;
            $arr['space_time'] = 0;
            $arr['add_time'] = $now_time;
            $arr['file'] = 'sub_employee_card_clear_score';
            $arr['time_type'] = 0;
            $arr['unique_id'] = 'sub_employee_card_clear_score_'.$val['card_id'];
			$arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
            M('process_sub_plan')->data($arr)->add();
        }
        return true;
    }
}