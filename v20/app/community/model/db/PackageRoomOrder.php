<?php
/**
 * 房间套餐订单
 * @author weili
 * @datetime 2020/8/13
 */

namespace app\community\model\db;

use think\Model;
class PackageRoomOrder extends Model
{
    /**
     * Notes: 获取一条
     * @param array $where
     * @param bool $field
     * @param string $order
     * @return array|Model|null
     * @author: weili
     * @datetime: 2020/8/13 15:40
     */
    public function getFind($where=[],$field=true,$order='order_id desc')
    {
        $info = $this->where($where)->field($field)->order($order)->find();
        return $info;
    }

    /**
     * Notes: 获取多条
     * @param array $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @author: weili
     * @datetime: 2020/8/13 15:41
     */
    public function getSelect($where=[],$field=true,$page=0,$limit=0,$order='')
    {
//        $list = $this->where($where)->field($field)->order($order)->limit($page,$limit)->select();
//        return $list;
        $sql = $this->field($field)->where($where)->order($order);
        if($limit)
        {
            $sql->limit($page,$limit);
        }
        $result = $sql->select();
        return $result;
    }

    /**
     * Notes: 获取数量
     * @param $where
     * @return int
     * @author: weili
     * @datetime: 2020/8/13 15:41
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * Notes: 计算和
     * @param $where
     * @param $field
     * @return float
     * @author: weili
     * @datetime: 2020/8/18 9:33
     */
    public function getSum($where,$field)
    {
        $sum = $this->where($where)->sum($field);
        return $sum;
    }

    /**
     * Notes: 一次插入多个
     * @param $data
     * @return int
     * @author: weili
     * @datetime: 2020/8/18 16:23
     */
    public function addAll($data)
    {
        $res = $this->insertAll($data);
        return $res;
    }

    /**
     * Notes: 修改
     * @param $where
     * @param $data
     * @return PackageRoomOrder
     * @author: weili
     * @datetime: 2020/8/18 18:55
     */
    public function edit($where,$data)
    {
        $res = $this->where($where)->update($data);
        return $res;
    }
}