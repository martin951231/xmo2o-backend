<?php
/**
 * 用户领取商家优惠券记录
 * User: lumin
 */

namespace app\common\model\db;

use think\Model;

class CardNewCouponHadpull extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    //正常
    const CAN_USE = 0;

    //已使用
    const HAS_USED = 1;

    //不能使用
    const FORBIDDEN_USE = 2;

    //赠送中
    const GIVING_AWAY = 3;


    public function getUserCoupon($where, $field = 'c.*,h.*'){
    	$data = $this->alias('h')
    			->leftJoin('card_new_coupon c', 'h.coupon_id=c.coupon_id')
    			->field($field)
    			->where($where)
    			->order('h.is_use ASC,c.discount DESC ,c.add_time DESC')
    			->select();

    	return $data;
    }

    public function getSum($where,$field){
        $data = $this->where($where)->sum($field);
        return $data;
    }
    /**
     * 获取一条用户优惠券信息
     * @param $where
     * @param string $field
     * @return mixed
     * @author: 张涛
     * @date: 2020/8/22
     */
    public function getOneUserCoupon($where, $field = 'c.*,h.*')
    {
        $data = $this->alias('h')
            ->leftJoin('card_new_coupon c', 'h.coupon_id=c.coupon_id')
            ->field($field)
            ->where($where)
            ->order('h.is_use ASC,c.discount DESC ,c.add_time DESC')
            ->find();
        return $data;
    }

    /**
     * @param $where
     * @param string $field
     */
    public function getAllCouponHadpullList($where = [], $limit = 0, $search_store = 0, $field = 'h.*,h.id as hadpull_id,c.name as coupon_name,u.nickname,u.phone,cu.name as card_name', $order = 'h.id desc', $mer_id = 0)
    {
        $prefix = config('database.connections.mysql.prefix');
        if (is_array($limit)) {
            $arr = $this->alias('h')
                ->join($prefix . 'card_new_coupon c', 'h.coupon_id = c.coupon_id', 'left')
                ->join($prefix . 'user u', 'h.uid = u.uid', 'left')
                ->join($prefix . 'employee_card_user cu', 'h.uid = cu.uid and cu.mer_id = ' . $mer_id, 'left');
            if ($search_store == 1) {
                $arr = $arr->join($prefix . 'card_new_coupon_use_list a', 'a.hadpull_id = h.id', 'left')
                    ->join($prefix . 'system_order o', 'o.type = a.order_type and o.order_id = a.order_id', 'left')
                    ->join($prefix . 'merchant_store s', 's.store_id = o.store_id', 'left');
            }
            $arr = $arr->field($field)
                ->where($where)
                ->order($order)
                ->paginate($limit)
                ->toArray();
        } else if ($limit > 0) {
            $arr1 = $this->alias('h')
                ->join($prefix . 'card_new_coupon c', 'h.coupon_id = c.coupon_id', 'left')
                ->join($prefix . 'user u', 'h.uid = u.uid', 'left')
                ->join($prefix . 'employee_card_user cu', 'h.uid = cu.uid and cu.mer_id = ' . $mer_id, 'left');
            if ($search_store == 1) {
                $arr1 = $arr1->join($prefix . 'card_new_coupon_use_list a', 'a.hadpull_id = h.id', 'left')
                    ->join($prefix . 'system_order o', 'o.type = a.order_type and o.order_id = a.order_id', 'left')
                    ->join($prefix . 'merchant_store s', 's.store_id = o.store_id', 'left');
            }
            $arr1 = $arr1->field($field)
                ->where($where)
                ->order($order)
                ->limit($limit)
                ->select();
            if (!empty($arr1)) {
                $arr['data'] = $arr1->toArray();
            } else {
                $arr = [
                    'data' => []
                ];
            }
        } else {
            $arr1 = $this->alias('h')
                ->join($prefix . 'card_new_coupon c', 'h.coupon_id = c.coupon_id', 'left')
                ->join($prefix . 'user u', 'h.uid = u.uid', 'left')
                ->join($prefix . 'employee_card_user cu', 'h.uid = cu.uid and cu.mer_id = ' . $mer_id, 'left');
            if ($search_store == 1) {
                $arr1 = $arr1->join($prefix . 'card_new_coupon_use_list a', 'a.hadpull_id = h.id', 'left')
                    ->join($prefix . 'system_order o', 'o.type = a.order_type and o.order_id = a.order_id', 'left')
                    ->join($prefix . 'merchant_store s', 's.store_id = o.store_id', 'left');
            }
            $arr1 = $arr1->field($field)
                ->where($where)
                ->order($order)
                ->select();
            if (!empty($arr1)) {
                $arr['data'] = $arr1->toArray();
            } else {
                $arr = [
                    'data' => []
                ];
            }
        }
        return $arr;
    }


}