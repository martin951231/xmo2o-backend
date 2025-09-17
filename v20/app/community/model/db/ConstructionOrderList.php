<?php


namespace app\community\model\db;

use think\Model;

class ConstructionOrderList extends Model
{
    public function getOne($where,$field='*',$orderBy='pigcms_id desc')
    {
        $info = $this->where($where)->field($field)->order($orderBy)->find();
        return $info;
    }
    
    public function addOne($data)
    {
        $add = $this->insertGetId($data);
        return $add;
    }

    public function updateThis($where,$data)
    {
        $update = $this->where($where)->update($data);
        return $update;
    }
    
    public function getOneValue($where,$field)
    {
        $value = $this->where($where)->value($field);
        return $value;
    }
    
    public function getList($where,$field,$group='',$limit=0)
    {
        $list = $this->where($where)->field($field);
        if($group){
            $list = $list->group($group);
        }
        if($limit){
            $list = $list->limit($limit);
        }
        $list = $list->select();
        return $list;
    }
}