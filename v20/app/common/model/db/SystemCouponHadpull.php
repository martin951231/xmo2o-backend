<?php
/**
 * 优惠券发放
 * Created by PhpStorm.
 * User: chenxiang
 * Date: 2020/6/1 19:45
 */

namespace app\common\model\db;

use think\Model;

class SystemCouponHadpull extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;
    //正常
    const CAN_USE = 0;

    //已使用
    const HAS_USED = 1;

    public function getUserCoupon($where, $field = 'c.*,h.*'){
        $data = $this->alias('h')
                ->leftJoin('system_coupon c', 'h.coupon_id=c.coupon_id')
                ->field($field)
                ->where($where)
                ->select();
        return $data;
    }
    /**
     * 优惠券发放计数
     * User: chenxiang
     * Date: 2020/6/1 19:47
     * @param array $where
     * @return int
     */
    public function countNum($where = []) {
        $result = $this->where($where)->count();
        return $result;
    }

    /**
     * 新增
     * User: chenxiang
     * Date: 2020/6/1 20:05
     * @param array $data
     * @return int
     */
    public function addAll($data = []) {
        $result = $this->insertAll($data);
        return $result;
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
            ->leftJoin('system_coupon c', 'h.coupon_id=c.coupon_id')
            ->field($field)
            ->where($where)
            ->find();
        return $data;
    }

    /**
     * @param $where
     * @param string $field
     */
    public function getAllCouponHadpullList($where = [], $limit = 0, $search_store = 0, $field = 'h.*,h.id as hadpull_id,c.name as coupon_name,u.nickname,u.phone', $order = 'h.id desc')
    {
        $prefix = config('database.connections.mysql.prefix');
        if (is_array($limit)) {
            $arr = $this->alias('h')
                ->join($prefix . 'system_coupon c', 'h.coupon_id = c.coupon_id', 'left')
                ->join($prefix . 'user u', 'h.uid = u.uid', 'left');
            if ($search_store == 1) {
                $arr = $arr->join($prefix . 'system_coupon_use_list a', 'a.hadpull_id = h.id', 'left')
                    ->join($prefix . 'system_order o', 'o.type = IF(a.order_type = "mall", "mall3", a.order_type) and o.order_id = a.order_id', 'left')
                    ->join($prefix . 'merchant_store s', 's.store_id = o.store_id', 'left');
            }
            $arr = $arr->field($field)
                ->where($where)
                ->order($order)
                ->paginate($limit)
                ->toArray();
        } else if ($limit > 0) {
            $arr1 = $this->alias('h')
                ->join($prefix . 'system_coupon c', 'h.coupon_id = c.coupon_id', 'left')
                ->join($prefix . 'user u', 'h.uid = u.uid', 'left');
            if ($search_store == 1) {
                $arr1 = $arr1->join($prefix . 'system_coupon_use_list a', 'a.hadpull_id = h.id', 'left')
                    ->join($prefix . 'system_order o', 'o.type = IF(a.order_type = "mall", "mall3", a.order_type) and o.order_id = a.order_id', 'left')
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
                ->join($prefix . 'system_coupon c', 'h.coupon_id = c.coupon_id', 'left')
                ->join($prefix . 'user u', 'h.uid = u.uid', 'left');
            if ($search_store == 1) {
                $arr1 = $arr1->join($prefix . 'system_coupon_use_list a', 'a.hadpull_id = h.id', 'left')
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