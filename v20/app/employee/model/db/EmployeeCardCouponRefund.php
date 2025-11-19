<?php


namespace app\employee\model\db;


use think\Model;

class EmployeeCardCouponRefund extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    protected $pk = 'pigcms_id';

    /**
     * 添加一条退款记录
     * @param cardlog_id pigcms_employee_card_log表pigcms_id
     * @param mer_id
     * @param user_id
     * @param uid
     * @param money 更改余额
     * @param score 更改积分，正数为增加，负数为减少
     * @param coupon 添加的消费券id
     * @param remark 备注
     */
    public function addOne($cardlog_id, $mer_id, $user_id, $uid, $money = 0, $score = 0, $coupon_id = 0, $remark = '', $staff_id = 0)
    {
        $this->cardlog_id = $cardlog_id;
        $this->mer_id = $mer_id;
        $this->user_id = $user_id;
        $this->uid = $uid;
        $this->money = $money;
        $this->score = $score;
        $this->coupon_id = $coupon_id;
        $this->remark = $remark;
        $this->add_time = time();
        $this->staff_id = $staff_id;
        return $this->save();
    }
}