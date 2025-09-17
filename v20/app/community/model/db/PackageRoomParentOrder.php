<?php
/**
 * 房间套餐总表
 * @author weili
 * @date 2020/8/20
 */

namespace app\community\model\db;

use think\Model;
class PackageRoomParentOrder extends Model
{
    /**
     * Notes: 添加数据
     * @param $data
     * @return int|string
     * @author: weili
     * @datetime: 2020/8/20 17:45
     */
    public function addFind($data)
    {
        $id = $this->insertGetId($data);
        return $id;
    }

    /**
     * Notes:修改数据
     * @param $where
     * @param $data
     * @return PackageRoomParentOrder
     * @author: weili
     * @datetime: 2020/8/20 17:48
     */
    public function edit($where,$data)
    {
        $res = $this->where($where)->update($data);
        return $res;
    }

    /**
     * Notes: 获取详情
     * @param $where
     * @param bool $field
     * @param string $order
     * @return array|Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     * @author: weili
     * @datetime: 2020/8/20 17:51
     */
    public function getInfo($where,$field=true,$order='order_id desc')
    {
        $info = $this->where($where)->field($field)->order($order)->find();
        return $info;
    }

    /**
     * Notes: 求和
     * @param $where
     * @param $filed
     * @return float
     * @author: weili
     * @datetime: 2020/8/31 14:16
     */
    public function getFieldSum($where,$filed)
    {
        $data = $this->where($where)->sum($filed);
        return $data;
    }
}