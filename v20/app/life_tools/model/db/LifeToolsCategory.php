<?php
/**
 * 分类model
 */

namespace app\life_tools\model\db;

use \think\Model;

class LifeToolsCategory extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    /**
     * @param: string $where
     * @return :  int
     * @Desc:   获取分类总数
     */
    public function getSortCount($where)
    {
        $count = $goodsSort = $this->where($where)->count();
        return $count;
    }

    /**
     * 获取列表
     * @param $where
     * @return array
     */
    public function getListToolCategory($where, $field = 'r.*',$order='r.tools_id desc')
    {
        $prefix = config('database.connections.mysql.prefix');
        $arr    = $this->alias('r')
            ->field($field)
            ->join($prefix . 'life_tools l', 'l.cat_id = r.cat_id')
            ->where($where)
            ->group('r.cat_id')
            ->order($order);
        $out['total']=$arr->count();
        $out['list']=$arr->select()->toArray();
        return $out;
    }

    /**
     * @param: $where array
     * @param  $order array
     * @return :  array
     * @Desc:   获取分类列表(分组排序 分页)
     */
    public function getCategoryByCondition($where, $order)
    {
        $goodsSort = $this->where($where)->field(true)->order($order)->select()->toArray();
        if (!$goodsSort) {
            return [];
        }
        return $goodsSort;
    }
}