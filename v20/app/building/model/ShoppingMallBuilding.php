<?php


namespace app\building\model;


use think\Model;

class ShoppingMallBuilding extends Model
{
    public function getList($where, $field)
    {
        $list = $this->where($where)->field($field)->order('sort desc,id asc')->select()->toArray();
        return $list;
    }
}