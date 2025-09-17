<?php


namespace app\community\model\db;

use think\Model;

class HousePropertyDigit extends Model
{
    public function getOne($where=[],$field=true)
    {
        $data = $this->where($where)->field($field)->find();
        return $data;
    }
}