<?php
/**
 * Class plan_employee_card_to_send_coupon 员工卡消费券发券->计划任务
 */
class plan_employee_card_to_send_coupon extends plan_base{
    //消费券发券 更新店铺营业时间
    public function runTask($param = array()){

        if(empty($param['pigcms_id'])){
            return true;
        }
        $pigcms_id = $param['pigcms_id'];
		D('Employee_card_coupon')->sendCoupon($pigcms_id);

        return true;
    }
}