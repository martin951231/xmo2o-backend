<?php

/**
 * 团购优惠组合绑定商品
 * Author: 衡婷妹
 * Date Time: 2020/11/16 16:29
 */
namespace app\group\model\db;

use think\Model;
class GroupCombineActivityGoods extends Model
{

    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * 获得商品列表
     * @param $where
     * @return array
     */
    public function getBindList($where = [], $field=true, $order=[], $page = 0, $limit=0 )
    {
        if(!$where){
            return null;
        }

        // 表前缀
        $prefix = config('database.connections.mysql.prefix');
        if($limit==0){
            $result = $this ->alias('b')
                ->where($where)
                ->field($field)
                ->leftJoin($prefix.'group g','g.group_id = b.group_id')
                ->leftJoin($prefix.'merchant m','m.mer_id = g.mer_id')
                ->order($order)
                ->select();
        }else{
            $result = $this ->alias('b')
                ->where($where)
                ->field($field)
                ->leftJoin($prefix.'group g','g.group_id = b.group_id')
                ->leftJoin($prefix.'merchant m','m.mer_id = g.mer_id')
                ->page($page,$limit)
                ->order($order)
                ->select();
        }
        return $result;
    }

    /**获取优惠组合商品数量
     * @param array $where
     * @param bool $field
     * @return array
     */
    public function getCombineCount($where = [], $field=true)
    {
        $result = $this->where($where)
            ->field($field)
            ->group('combine_id')
            ->select();
        if (!empty($result)) {
            return $result->toArray();
        } else {
            return [];
        }
    }

}