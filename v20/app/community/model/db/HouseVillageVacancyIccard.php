<?php


namespace app\community\model\db;

use think\Model;

class HouseVillageVacancyIccard extends Model
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
        $res = $this->insertGetId($data);
        return $res;
    }

    public function saveOne($where,$data)
    {
        $res  = $this->where($where)->save($data);
        return $res;
    }

    public function getOne($where,$field=true)
    {
        $data = $this->where($where)->field($field)->find();
        return $data;
    }

    public function getBindVacancyList($where,$field=true,$order='id desc',$page=0,$limit=20)
    {
        $sql = $this->where($where)->field($field)->order($order);
        if($page){
            $list = $sql->page($page,$limit)->select();
        }else{
            $list = $sql->select();
        }
        return $list;
    }

    public function getBindVacancyCount($where=[])
    {
        $sql = $this->where($where);
        $list = $sql->count();
        return $list;
    }

}