<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/11/19 15:49
 */
namespace app\community\model\db;

use think\Model;
class HouseVillageParkingTemp extends Model
{
    /**
     * Notes: 获取一条
     * @param $where
     * @param $field
     * @return mixed
     * @author: weili
     * @datetime: 2020/8/27 13:37
     */
    public function getFind($where,$field=true,$order='id DESC')
    {
        $info = $this->where($where)->field($field)->order($order)->find();
        return $info;
    }


    public function getOne($where = [], $field = true, $order = 'id DESC') {
        $result = $this->field($field)->where($where)->order($order)->find();
        return $result;
    }

    /**
     * 添加车库
     * @author zhubaodi
     * @date_time 2021/11/19 14:39
     * @param $data
     * @return int|string
     */
    public function addOne($data)
    {
        $res = $this->insertGetId($data);
        return $res;
    }


    /**
     * 修改订单
     * @param array|object $where
     * @param array|object $data
     * @return bool
     * @author zhubaodi
     * @date_time 2022/05/21
     */
    public function saveOne($where = [], $data = [])
    {
        $res = $this->where($where)->save($data);
        return $res;
    }

    public function delOne($where) {
        $del = $this->where($where)->delete();
        return $del;
    }
}