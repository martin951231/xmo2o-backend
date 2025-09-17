<?php

/**
 * 团购发现页团购分类商品管理排序
 * Created by PhpStorm.
 * Author: 钱大双
 * Date Time: 2021年2月1日09:53:52
 */

namespace app\group\model\db;

use think\Model;

class GroupRenovationCustomGroupSort extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 获得列表
     * @param $where
     * @return array
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
                ->leftJoin($prefix . 'group g', 'b.group_id = g.group_id')
                ->leftJoin($prefix . 'merchant m', 'g.mer_id = m.mer_id')
                ->order($order)
                ->select();
        } else {
            $result = $this->alias('b')
                ->where($where)
                ->field($field)
                ->leftJoin($prefix . 'group g', 'b.group_id = g.group_id')
                ->leftJoin($prefix . 'merchant m', 'g.mer_id = m.mer_id')
                ->page($page, $limit)
                ->order($order)
                ->select();
        }
        if (!empty($result)) {
            return  $result->toArray();
        } else {
            return [];
        }
    }

    /**
     * 获得列表总数
     * @param $where
     * @return array
     */
    public function getCountBy($where = [], $field = true)
    {
        if (!$where) {
            return null;
        }

        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $count = $this->alias('b')
            ->where($where)
            ->field($field)
            ->leftJoin($prefix . 'group g', 'b.group_id = g.group_id')
            ->leftJoin($prefix . 'merchant m', 'g.mer_id = m.mer_id')
            ->count();
        return $count;
    }

    public function del($where)
    {
        $result = $this->where($where)->delete();
        return $result;
    }
}