<?php


namespace app\mall\model\db;

use think\Model;
class ShopGoodsBatchFailLog extends Model
{
    /** 添加数据 获取插入的数据id
     * Date: 2020-10-16 15:42:29
     * @param $data
     * @return int|string
     */
    public function addOne($data) {
        return $this->insertGetId($data);

    }

    /**
     * 更新数据
     * @param $where
     * @param $data
     * @return MallGoods
     */
    public function updateOne($data,$where)
    {
        $result = $this->where($where)->update($data);
        return $result;
    }

    /**
     * @param $where
     * @param $order
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     *获取列表
     */
    public function getList($where, $order)
    {
        $result = $this->where($where)->order($order)->select()->toArray();

        return $result;
    }
}