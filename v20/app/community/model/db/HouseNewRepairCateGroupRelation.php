<?php
/**
 * @author : liukezhu
 * @date : 2022/4/7
 */
namespace app\community\model\db;
use think\Model;

class HouseNewRepairCateGroupRelation extends Model{

    public function addAll($data)
    {
        $res = $this->insertAll($data);
        return $res;
    }

    public function delOne($where) {
        $del = $this->where($where)->delete();
        return $del;
    }


    public function getColumn($where,$column)
    {
        $data = $this->where($where)->column($column);
        return $data;
    }

    public function getOne($where=[],$field=true)
    {
        $data = $this->where($where)->field($field)->find();
        return $data;
    }

}