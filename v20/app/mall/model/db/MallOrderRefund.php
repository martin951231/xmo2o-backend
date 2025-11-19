<?php
namespace app\mall\model\db;

use think\Model;
use think\facade\Db;
class MallOrderRefund extends Model{
	use \app\common\model\db\db_trait\CommonFunc;

    public function getInfo($where, $field, $page = '', $pageSize = '')
    {
        $prefix = config('database.connections.mysql.prefix');
            if ($page && $pageSize) {
                $arr = $this->alias('r')
                    ->field($field)
                    ->join($prefix .'mall_order o', 'o.order_id=r.order_id')
                    ->join($prefix .'merchant mr', 'mr.mer_id=o.mer_id')
                    ->join($prefix .'merchant_store s', 's.store_id=o.store_id')
                    ->join($prefix .'merchant_store ms', 'ms.store_id=o.store_id')
                    ->where($where)
                    ->order('r.create_time desc')
                    ->page($page, $pageSize)
                    ->select();
            } else {
                $arr = $this->alias('r')
                    ->field($field)
                    ->join($prefix .'mall_order o', 'o.order_id=r.order_id')
                    ->join($prefix .'merchant mr', 'mr.mer_id=o.mer_id')
                    ->join($prefix .'merchant_store s', 's.store_id=o.store_id')
                    ->join($prefix .'merchant_store ms', 'ms.store_id=o.store_id')
                    ->where($where)
                    ->order('r.create_time desc')
                    ->select();
            }
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }

    /**
     * @param $where
     * @param $field
     * @param string $page
     * @param string $pageSize
     * @return array
     * 店铺信息
     */
    public function getInfoByStore($where, $field, $page = '', $pageSize = '')
    {
        $prefix = config('database.connections.mysql.prefix');
        if ($page && $pageSize) {
            $arr = $this->alias('r')
                ->field($field)
                ->join($prefix .'mall_order o', 'o.order_id=r.order_id')
                ->join($prefix .'merchant mr', 'mr.mer_id=o.mer_id')
                ->join($prefix .'merchant_store s', 's.store_id=o.store_id')
                ->where($where)
                ->order('r.create_time desc')
                ->page($page, $pageSize)
                ->select();
        } else {
            $arr = $this->alias('r')
                ->field($field)
                ->join($prefix .'mall_order o', 'o.order_id=r.order_id')
                ->join($prefix .'merchant mr', 'mr.mer_id=o.mer_id')
                ->join($prefix .'merchant_store s', 's.store_id=o.store_id')
                ->where($where)
                ->order('r.create_time desc')
                ->select();
        }
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }


    public function getInfoByOrderDetail($where, $field, $page = '', $pageSize = '')
    {
        $prefix = config('database.connections.mysql.prefix');
        if ($page && $pageSize) {
            $arr = $this->alias('r')
                ->field($field)
                ->join($prefix .'mall_order o', 'o.order_id=r.order_id')
                ->join($prefix . 'mall_order_detail md', 'md.order_id = o.order_id')
                ->join($prefix .'merchant mr', 'mr.mer_id=o.mer_id')
                ->join($prefix .'merchant_store ms', 'ms.store_id=o.store_id')
                ->where($where)
                ->order('r.create_time desc')
                ->page($page, $pageSize)
                ->select();
        } else {
            $arr = $this->alias('r')
                ->field($field)
                ->join($prefix .'mall_order o', 'o.order_id=r.order_id')
                ->join($prefix . 'mall_order_detail md', 'md.order_id = o.order_id')
                ->join($prefix .'merchant mr', 'mr.mer_id=o.mer_id')
                ->join($prefix .'merchant_store ms', 'ms.store_id=o.store_id')
                ->where($where)
                ->order('r.create_time desc')
                ->select();
        }
        if (!empty($arr)) {
            return $arr->toArray();
        } else {
            return [];
        }
    }
    
    public function getOrder($where,$field)
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr = $this->alias('a')
            ->join($prefix .'mall_order b', 'a.order_id=b.order_id')
            ->where($where)
            ->field($field)
            ->select()
            ->toArray();
        return $arr;
    }
    /**
     * 获取退款所有数量
     */
    public function getTodayRefundNum($where)
    {
        $prefix = config('database.connections.mysql.prefix');
        $num = $this->alias('f')
            ->join($prefix.'mall_order_refund_detail fd','fd.refund_id = f.refund_id')
            ->join($prefix.'mall_order_detail d','d.id = fd.order_detail_id')
            ->join($prefix.'mall_order o','o.order_id = f.order_id')
            ->where($where)
            ->value('SUM(fd.refund_nums)');
        return $num;
    }
}