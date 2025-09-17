<?php

/**
 * 团购自定义配置店铺活动推荐店铺排序
 * Created by PhpStorm.
 * Author: 钱大双
 * Date Time: 2021-1-15 10:21:35
 */

namespace app\group\model\db;

use think\Model;

class GroupRenovationCustomStoreSort extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 获得列表
     * @param $where
     * @return object
     */
    public function getList($where = [], $field = true, $order = [], $page = 0, $limit = 0)
    {
        if (!$where) {
            return null;
        }

        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        if ($limit == 0) {
            $result = $this->alias('b')
                ->where($where)
                ->field($field)
                ->leftJoin($prefix . 'merchant_store s', 'b.store_id = s.store_id')
                ->leftJoin($prefix . 'merchant m', 'm.mer_id = s.mer_id')
                ->leftJoin($prefix . 'merchant_category c', 's.cat_id = c.cat_id')
                ->leftJoin($prefix . 'merchant_store_meal l', 's.store_id = l.store_id')
                ->order($order)
                ->group('b.store_id')
                ->select();
        } else {
            $result = $this->alias('b')
                ->where($where)
                ->field($field)
                ->leftJoin($prefix . 'merchant_store s', 'b.store_id = s.store_id')
                ->leftJoin($prefix . 'merchant m', 'm.mer_id = s.mer_id')
                ->leftJoin($prefix . 'merchant_category c', 's.cat_id = c.cat_id')
                ->leftJoin($prefix . 'merchant_store_meal l', 's.store_id = l.store_id')
                ->page($page, $limit)
                ->order($order)
                ->group('b.store_id')
                ->select();
        }
        return $result;
    }

    /**
     * 获得列表总数
     * @param $where
     * @return int
     */
    public function getListCount($where = [])
    {
        if (!$where) {
            return 0;
        }

        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('b')
            ->where($where)
            ->leftJoin($prefix . 'merchant_store s', 'b.store_id = s.store_id')
            ->leftJoin($prefix . 'merchant m', 'm.mer_id = s.mer_id')
            ->group('b.store_id')
            ->count();
        return $result;
    }

    public function del($where)
    {
        $result = $this->where($where)->delete();
        return $result;
    }
}