<?php
/**
 * 功能套餐db
 * @author weili
 * @datetime 2020/8/13
 */

namespace app\community\model\db;

use app\community\model\service\HouseUserLogService;
use think\Model;
class PackageOrder extends Model
{
    /**
     * Notes: 获取一条
     * @param array $where
     * @param bool $field
     * @param string $order
     * @return array|Model|null
     * @author: weili
     * @datetime: 2020/8/13 14:19
     */
    public function getFind($where=[],$field=true,$order='order_id desc')
    {
        $info = $this->where($where)->field($field)->order($order)->find();
        return $info;
    }

    /**
     * Notes: 获取所有
     * @param array $where
     * @param bool $field
     * @param int $page
     * @param int $limit
     * @param string $order
     * @return \think\Collection
     * @author: weili
     * @datetime: 2020/8/13 14:27
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
        $list = $sql->select();
        return $list;
    }

    /**
     * Notes: 获取数量
     * @param $where
     * @return int
     * @author: weili
     * @datetime: 2020/8/13 14:30
     */
    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    /**
     * Notes: 添加数据
     * @param $data
     * @return int|string
     * @author: weili
     * @datetime: 2020/8/15 18:22
     */
    public function insertOrder($data)
    {
        $order_id = $this->insertGetId($data);
        if (isset($data['property_id']) && intval($data['property_id']) > 0) {
            (new HouseUserLogService())->clearPackageOrder(intval($data['property_id']));
        }
        return $order_id;
    }

    /**
     * Notes: 修改订单
     * @param $where
     * @param $data
     * @return PackageOrder
     * @author: weili
     * @datetime: 2020/8/18 19:29
     */
    public function edit($where,$data)
    {
        $order_id = $this->where($where)->update($data);
        $orderInfo = $this->where($where)->field('property_id')->find();
        if (isset($orderInfo['property_id']) && intval($orderInfo['property_id']) > 0) {
            (new HouseUserLogService())->clearPackageOrder(intval($orderInfo['property_id']));
        }
        return $order_id;
    }
    /**
     * Notes: 对应字段求和
     * @param $where
     * @param $field
     * @return mixed
     * @author: weili
     * @datetime: 2020/8/19 17:40
     */
    public function getSum($where,$field){
        $sum = $this->where($where)->sum($field);
        return $sum;
    }
}