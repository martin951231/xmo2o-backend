<?php


namespace app\community\model\db;

use think\Model;
class HouseCameraTip extends Model
{

    public function getOne($where=[],$field=true)
    {
        return $this->where($where)->field($field)->find();
    }


    public function addOne($data=[])
    {
        return $this->insertGetId($data);
    }


    public function saveOne($where=[],$data=[])
    {
        return $this->where($where)->save($data);
    }


    public function getList($where=[],$field=true,$page=1,$limit=15,$order='id DESC')
    {
        return $this->where($where)->field($field)->page($page,$limit)->order($order)->select();
    }

    public function getCount($where=[])
    {
        return $this->where($where)->count();
    }
}