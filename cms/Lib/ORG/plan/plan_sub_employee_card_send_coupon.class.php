<?php 
/**
 * Class plan_sub_employee_card_send_coupon 员工卡消费券发券->子计划任务
 */
class plan_sub_employee_card_send_coupon extends plan_base{
    //子计划任务
    public function runTask($param = array()){

        if(empty($param['mer_id'])){
            return true;
        }

        $where = array();
        $where['status'] = 1;
        $where['mer_id'] = $param['mer_id'];
        $list = M('Employee_card_coupon')->where($where)->select();
        if(empty($list)){
            return true;
        }
        $now_time=time();
        foreach($list as $key => $val){
            $this->keepThread(); 
            $arr= array();
            $arr['param'] = serialize(array('pigcms_id'=>$val['pigcms_id']));
            $arr['plan_time'] = $now_time;
            $arr['space_time'] = 0;//子计划任务不需要每分钟执行，主计划任务会唤醒
            $arr['add_time'] = $now_time;
            $arr['file'] = 'employee_card_to_send_coupon';
            $arr['time_type'] = 0;
            $arr['unique_id'] = 'employee_card_to_send_coupon_'.$val['pigcms_id'];
			$arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
            M('process_sub_plan')->data($arr)->add();
        }
		
		return true;
    }
}