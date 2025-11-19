<?php

/**
 * 团购自定义配置超值组合装修商品数据
 * Created by PhpStorm.
 * Author: 钱大双
 * Date Time: 2021-1-13 15:13:53
 */

namespace app\group\model\db;

use think\Model;

class GroupRenovationCombineGoods extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 获得商品列表
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
                ->leftJoin($prefix . 'group_combine_activity g', 'b.combine_id = g.combine_id')
                ->order($order)
                ->select();
        } else {
            $result = $this->alias('b')
                ->where($where)
                ->field($field)
                ->leftJoin($prefix . 'group_combine_activity g', 'b.combine_id = g.combine_id')
                ->page($page, $limit)
                ->order($order)
                ->select();
        }
        return $result;
    }


    /**
     * 获得超值组合列表总数
     * @param $where
     * @return array
     */
    public function getCombineCount($where = [], $field = true)
    {
        if (!$where) {
            return 0;
        }
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        $result = $this->alias('b')
            ->where($where)
            ->field($field)
            ->leftJoin($prefix . 'group_combine_activity g', 'b.combine_id = g.combine_id')
            ->count();
        return $result;
    }
}