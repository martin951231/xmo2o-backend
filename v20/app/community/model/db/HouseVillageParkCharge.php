<?php
/**
 * Created by PhpStorm.
 * Author: zhubaodi
 * Date Time: 2021/11/3 18:02
 */
namespace app\community\model\db;

use think\Model;
class HouseVillageParkCharge extends Model
{
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

    /**
     * Notes: 添加数据
     * @param $data
     * @return int|string
     */
    public function addFind($data)
    {
        $id =$this->insertGetId($data);
        return $id;
    }

    /**
     *编辑数据
     * @author: liukezhu
     * @date : 2022/1/12
     * @param $where
     * @param $save
     * @return mixed
     */
    public function save_one($where, $save) {
        $set_info = $this->where($where)->save($save);
        return $set_info;
    }
}