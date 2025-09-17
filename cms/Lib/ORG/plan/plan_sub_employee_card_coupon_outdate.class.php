<?php 
/**
 * Class plan_sub_employee_card_coupon_outdate 将已过期的未核销的消费券转为积分->子计划任务
 */
class plan_sub_employee_card_coupon_outdate extends plan_base{
    //子计划任务
    public function runTask($param = array()){

        if(empty($param['overdue_time'])){
            return true;
        }

        $where = array();
        $where['overdue_time'] = $param['overdue_time']; 
        $list = M('Employee_card_coupon')->where($where)->select();
        if(empty($list)){
            return true;
        }
        $now_time=time();
        foreach($list as $key => $val){
            //转积分数量为0时，不执行转积分
            if($val['add_score_num'] == 0){
                continue;
            }
            $this->keepThread(); 
            $arr= array();
            $arr['param'] = serialize(array('pigcms_id'=>$val['pigcms_id']));
            $arr['plan_time'] = strtotime(date('Y-m-d'.' '.$param['overdue_time']));
            $arr['space_time'] = 0;//子计划任务不需要每分钟执行，主计划任务会唤醒
            $arr['add_time'] = $now_time;
            $arr['file'] = 'employee_card_coupon_outdate_handle';
            $arr['time_type'] = 0;
            $arr['unique_id'] = 'employee_card_coupon_outdate_handle_'.$val['pigcms_id'];
			$arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
            M('process_sub_plan')->data($arr)->add();
        }
		
		return true;
    }
}