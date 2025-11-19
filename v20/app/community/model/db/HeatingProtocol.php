<?php
namespace app\community\model\db;

use think\Model;
class HeatingProtocol extends Model{    
    public function addOne($data)
    {
        $add = $this->insert($data);
        return $add;
    }
    
    public function getOne($where,$field){
        $data = $this->where($where)->field($field)->find();
        $data = $data ? $data->toArray() : [];
        return $data;
    }
    
    public function updateThis($where,$date)
    {
        $update = $this->where($where)->update($date);
        return $update;
    }
}