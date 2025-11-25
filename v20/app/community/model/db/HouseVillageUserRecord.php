<?php


namespace app\community\model\db;

use think\Model;

class HouseVillageUserRecord extends Model
{

    public function getCount($where)
    {
        $count = $this->where($where)->count();
        return $count;
    }

    public function getList($where,$field=true,$page,$limit,$order='id DESC')
    {
        $data = $this->where($where)->field($field)->page($page,$limit)->order($order)->select();
        return $data;
    }

    public function addOne($data)
    {
        $res = $this->insert($data);
        return $res;
    }

    public function saveOne($where,$data)
    {
        $res  = $this->where($where)->save($data);
        return $res;
    }

    public function getOne($where,$field)
    {
        $data = $this->where($where)->field($field)->find();
        return $data;
    }
}