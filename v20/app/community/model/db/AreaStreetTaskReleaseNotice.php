<?php
/**
 * @author : liukezhu
 * @date : 2022/5/10
 */
namespace app\community\model\db;

use think\Model;
class AreaStreetTaskReleaseNotice extends Model{

    public function getOne($where,$field =true){
        $info = $this->where($where)->field($field)->find();
        return $info;
    }

    public function addFind($data)
    {
        $id =$this->insertGetId($data);
        return $id;
    }

    public function getColumn($where,$field)
    {
        $column = $this->where($where)->column($field);
        return $column;
    }


}