<?php
/**
 * 车辆出场
 * @author weili
 * @datetime 2020/8/3
 */

namespace app\community\model\db;

use think\Model;
class OutPark extends Model
{
    public function getCount($where)
    {
        $count = $this->alias('o')->leftJoin('in_park i','o.order_id=i.order_id')
                       ->where($where)
                       ->count();
        return $count;
    }

    /**
     * Notes: 获取数量
     * @param $where
     * @return mixed
     * @author: weili
     * @datetime: 2020/8/3 17:44
     */
    public function getOne($where,$field=true)
    {
        $data = $this->where($where)->field($field)->find();
        return $data;
    }
    /**
     * 获取小区出场车辆
     * @author lijie
     * @date_time 2020/01/15
     * @param $where
     * @return mixed
     */
    public function getCountByVillageId($where)
    {
        $count = $this->alias('p')
            ->leftJoin('house_village_park v','p.park_id = v.id')
            ->where($where)
            ->count();
        return $count;
    }

    /**
     * 获取车辆出场列表
     * @author lijie
     * @date_time 2020/01/12
     * @param $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getList($where,$field=true,$page=1,$limit=10,$order='id DESC')
    {
        $data = $this->where($where)->field($field)->page($page,$limit)->order($order)->select();
        return $data;
    }

    public function getLists($where,$field=true,$page,$limit,$order='p.id DESC',$type=0)
    {
        $data = $this->alias('p')
            ->leftJoin('house_village_park v','p.park_id = v.id')
            ->field($field)
            ->where($where);
        if($type){
            $data = $data
                ->order($order)
                ->select();
        }else{
            $data = $data
                ->page($page,$limit)
                ->order($order)
                ->select();
        }
        return $data;
    }

    /**
     * 添加数据
     * @author weili
     * @datetime 2020/7/7 10:43
     * @param array $data
     * @return integer
     **/
    public function insertOne($data)
    {
        $res = $this->insertGetId($data);
        return $res;
    }

    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }


    public function getColumn($where,$column, $key = '')
    {
        $data = $this->where($where)->column($column,$key);
        return $data;
    }


    public function getInfoCount($where)
    {
        return $this->where($where)->count();
    }
}