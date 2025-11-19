<?php
/**
 * Class plan_employee_card_coupon_outdate_handle 将已过期的未核销的消费券转为积分->计划任务
 */
class plan_employee_card_coupon_outdate_handle extends plan_base{
    //将已过期的未核销的消费券转为积分
    public function runTask($param = array()){

        if(empty($param['pigcms_id'])){
            return true;
        }
        $pigcms_id = $param['pigcms_id'];
		D('Employee_card_coupon')->couponOutdateHandle($pigcms_id);

        return true;
    }
}