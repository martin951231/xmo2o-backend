<?php


namespace app\employee\model\db;


use think\Model;

class EmployeeCardCoupon extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 优惠券编辑
     */
    public function editCoupon($where = [], $field = true, $order = []) {
        $result = $this->field($field)->where($where)->order($order)->find();
        if(empty($result)){
            $result=[];
        }else{
            $result=$result->toArray();
        }
        return $result;
    }

    /**
     * 优惠券删除
     */
    public function delCard($where)
    {
        $ret=$this->where($where)->delete();
        return $ret;
    }

    public function coupon()
    {
        return $this->belongsTo(EmployeeCardCoupon::class, 'coupon_id', 'pigcms_id');
    }

    /**
     * 判断是否有重叠时间记录
     */
    public function isOverlap($coupon)
    {
        $where = "mer_id = {$coupon->mer_id} AND is_default = 1 AND pigcms_id <> {$coupon->pigcms_id}  
                    AND ( 
                        (start_time < '{$coupon->start_time}' AND end_time > '{$coupon->end_time}' )
                        OR (start_time > '{$coupon->start_time}' AND end_time < '{$coupon->end_time}') 
                        OR(
                            (start_time < '{$coupon->end_time}' AND end_time > '{$coupon->end_time}') 
                            OR (end_time > '{$coupon->start_time}' AND start_time < '{$coupon->start_time}') 
                        ) 
                    )";
        $count = $this->where($where)->count('pigcms_id');
        if($count > 0){
            return true;
        }else{
            return false;
        }
    }
}