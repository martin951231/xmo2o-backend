<?php


namespace app\community\model\db;

use think\Model;

class ParkSystem extends Model
{
    /**
     * 获取数量
     * @author lijie
     * @date_time 2021/03/01
     * @param $where
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    public function getCountByCondition($where)
    {
        $count = $this->alias('p')
            ->leftJoin('house_village b', 'p.park_id = b.village_id')
            ->leftJoin('house_village_park_config c', 'p.park_id = c.village_id')
            ->where($where)->count();

        return $count>0 ? $count:0;
    }
    /**
     * Notes: 获取一条
     * @param $where
     * @param $field
     * @return mixed
     * @author: zhubaodi
     * @datetime: 2021/11/3 13:37
     */
    public function getFind($where,$field=true)
    {
        $info = $this->where($where)->field($field)->find();
        return $info;
    }
}