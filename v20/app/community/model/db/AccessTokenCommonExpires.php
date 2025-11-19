<?php


namespace app\community\model\db;

use think\Model;

class AccessTokenCommonExpires extends Model
{
    public function getOne($where,$field=true, $order = [])
    {
        $data = $this->where($where)->field($field)->order($order)->find();
        return $data;
    }

    public function addOne($data)
    {
        $res = $this->insertGetId($data);
        return $res;
    }

    public function saveOne($where,$data)
    {
        $res = $this->where($where)->save($data);
        return $res;
    }
}