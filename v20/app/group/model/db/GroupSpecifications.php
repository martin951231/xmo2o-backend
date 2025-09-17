<?php

/**
 * 团购商品规格
 * Author: 衡婷妹
 * Date Time: 2020/11/27 13:53
 */
namespace app\group\model\db;

use think\Model;
use think\facade\Db;
class GroupSpecifications extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 根据条件返回订单列表
     * @param $where array 条件
     * @param $order array 排序
     * @return array|bool|Model|null
     */
    public function getGroupGoodsListByJoin($where, $field, $order, $page, $pageSize) {

        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        if($pageSize==0){
            $result = $this ->alias('g')
                ->where($where)
                ->field($field)
                ->leftJoin($prefix.'merchant m','m.mer_id = g.mer_id')
                ->leftJoin($prefix.'group_specifications s','s.group_id = g.group_id')
                ->order($order)
                ->group('g.group_id')
                ->select();

        }else{
            $result = $this ->alias('g')
                ->where($where)
                ->field($field)
                ->leftJoin($prefix.'merchant m','m.mer_id = g.mer_id')
                ->leftJoin($prefix.'group_specifications s','s.group_id = g.group_id')
                ->order($order)
                ->group('g.group_id')
                ->page($page,$pageSize)
                ->select();

        }
        return $result;
    }


    /**
     * 根据条件返回总数
     * @param $where array 条件
     * @return array|bool|Model|null
     */
    public function getGroupGoodsCountByJoin($where) {
        // 表前缀
        $prefix = config('database.connections.mysql.prefix');

        $result = $this ->alias('g')
                ->where($where)
                ->leftJoin($prefix.'merchant m','m.mer_id = g.mer_id')
                ->leftJoin($prefix.'group_specifications s','s.group_id = g.group_id')
                ->count();
        return $result;
    }
}