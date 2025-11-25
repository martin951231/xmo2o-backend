<?php
/**
 * 在线报修/投诉建议分类
 * @author weili
 * @datetime 2020/7/18
 */

namespace app\community\model\db;

use think\Model;
class HouseVillageRepairCate extends Model
{
    /**
     * Notes:获取一条记录
     * @param:
     * @param $where
     * @param bool $field
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: weili
     * @datetime: 2020/7/18 11:25
     */
    public function getFind($where,$field=true)
    {
        $info = $this->where($where)->field($field)->find();
        return $info;
    }

    /**
     * Notes: 获取一级二级分类名称
     * @param:
     * @param $where
     * @param bool $field
     * @return mixed
     * @author: weili
     * @datetime: 2020/7/18 12:00
     */
    public function getRelevance($where,$field=true)
    {
        $data = $this->alias('a')
            ->leftJoin('house_village_repair_cate b','a.parent_id=b.id')
            ->where($where)
            ->field($field)
            ->find();
        return $data;
    }
}
