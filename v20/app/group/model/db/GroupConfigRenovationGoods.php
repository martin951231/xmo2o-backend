<?php

/**
 * 团购自定义配置装修商品数据
 * Created by PhpStorm.
 * Author: 钱大双
 * Date Time: 2021-1-13 15:13:53
 */

namespace app\group\model\db;

use think\Model;

class GroupConfigRenovationGoods extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 获得优选商品商品列表
     * @param $where
     * @return array
     */
    public function getBindList($where = [], $field = true, $order = [], $page = 0, $limit = 0)
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
                ->leftJoin($prefix . 'group g', 'g.group_id = b.group_id')
                ->leftJoin($prefix . 'merchant m', 'm.mer_id = g.mer_id')
                ->order($order)
                ->select();
        } else {
            $result = $this->alias('b')
                ->where($where)
                ->field($field)
                ->leftJoin($prefix . 'group g', 'g.group_id = b.group_id')
                ->leftJoin($prefix . 'merchant m', 'm.mer_id = g.mer_id')
                ->page($page, $limit)
                ->order($order)
                ->select();
        }
        return $result;
    }


    /**
     * 获得优选商品列表总数
     * @param $where
     * @return array
     */
    public function getSelectCount($where = [], $field = true)
    {
        if (!$where) {
            return 0;
        }
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('b')
            ->where($where)
            ->field($field)
            ->leftJoin($prefix . 'group g', 'g.group_id = b.group_id')
            ->leftJoin($prefix . 'merchant m', 'm.mer_id = g.mer_id')
            ->count();
        return $result;
    }
}