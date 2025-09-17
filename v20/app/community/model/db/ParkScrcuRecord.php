<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/12/7 15:22
 */
namespace app\community\model\db;

use think\Model;
class ParkScrcuRecord extends Model
{
    /**
     * 添加数据
     * @author:zhubaodi
     * @date_time: 2022/7/9 15:13
     */
    public function add($data)
    {
        $id = $this->insertGetId($data);
        return $id;
    }
    /**
     * 获取访客和主人信息
     * @author:zhubaodi
     * @date_time: 2022/7/9 15:13
     * @param $where
     * @param string $field
     * @param string $order
     * @return mixed
     */
    public function get_one($where,$field=true,$order='id DESC')
    {
        $data = $this->field($field)->where($where)->order($order)->find();
        return $data;
    }

    /**
     * 删除信息
     * @author:zhubaodi
     * @date_time: 2022/7/9 15:13
     * @param array $where 改写内容的条件
     * @return bool
     */
    public function delOne($where) {
        $del = $this->where($where)->delete();
        return $del;
    }

    /**
     * 修改订单
     * @param array $where
     * @param array $data
     * @return bool
     * @author:zhubaodi
     * @date_time: 2022/7/9 15:13
     */
    public function saveOne($where = [], $data = [])
    {
        $res = $this->where($where)->save($data);
        return $res;
    }
}