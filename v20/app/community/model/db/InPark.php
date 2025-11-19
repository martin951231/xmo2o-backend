<?php
/**
 * 车辆进入记录
 */

namespace app\community\model\db;

use think\Model;
class InPark extends Model
{
    /**
     * Notes: 获取数量
     * @param $where
     * @return int
     * @author: weili
     * @datetime: 2020/8/3 17:44
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }


    public function getParkCount($where)
    {
        $count = $this->alias('i')
            ->leftJoin('out_park o','i.order_id = o.order_id')
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
     * Notes: 获取数量
     * @param $where
     * @return mixed
     * @author: weili
     * @datetime: 2020/8/3 17:44
     */
    public function getOne1($where,$field='*',$order='id DESC')
    {
        $data = $this->where($where)->field($field)->order($order)->find();
        return $data;
    }

    /**
     * 获取小区进场车辆
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
     * Notes: 获取某个字段的值
     * @param $where
     * @param $field
     * @param $key
     * @author: weili
     * @datetime: 2020/8/4 10:57
     * @return array
     */
    public function getColumn($where,$column, $key = '')
    {
        $data = $this->where($where)->column($column,$key);
        return $data;
    }

    /**
     * 获取车辆进场列表
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

    public function getLists($where,$field,$page,$limit,$order='p.id DESC',$type=0)
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


    //todo 查询车辆数据
    public function getParkData($where,$times,$where2=''){
        $data = $this->alias('p')
            ->leftJoin('house_village_parking_car c','c.car_number = p.car_number')
            ->whereTime($times, 'today')
            ->where($where)->where($where2)->count();
        return $data;
    }

    /**
     * 获取车辆进场列表
     * @author zhubaodi
     * @date_time 2021/8/23
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
    public function getParkLists($where,$field,$page=0,$limit=10,$order='i.id DESC')
    {
        $data = $this->alias('i')
            ->leftJoin('out_park o','i.order_id=o.order_id')
            ->leftJoin('house_village_pay_order p','p.order_id=i.pay_order_id')
            ->leftJoin('house_village v','i.park_id=v.village_id')
            ->field($field)
            ->where($where);
        if($page){
            $data = $data
                ->page($page,$limit)
                ->order($order)
                ->select();
        }else{
            $data = $data
                ->order($order)
                ->select();
        }

        return $data;
    }

    /**
     * 修改信息
     * @author: wanziyang
     * @date_time: 2020/4/24 14:39
     * @param array $where 改写内容的条件
     * @param array $save 修改内容
     * @return bool
     */
    public function saveOne($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
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

    public function delDatas($where)
    {
        $res = $this->where($where)->delete();
        return $res;
    }
}