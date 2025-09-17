<?php 
/**
 * Class plan_employee_card_clear_score 清积分->子计划任务
 */
class plan_sub_employee_card_coupon_outdate extends plan_base{
    //子计划任务
    public function runTask($param = array()){

        if(empty($param['card_id'])){
            return true;
        }
 
        D('Employee_card')->clearScore($param['card_id']);
    
        return true;
 
    }
}