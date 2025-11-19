<?php


namespace app\merchant\model\db;


use think\Model;

class Group extends Model
{
    public function getList($where, $page=0, $pageSize=10, $field='*'){
        $query = $this->field($field)
            ->where($where)
            ->order('group_id desc');
        if($page>0){
            $list = $query->paginate($pageSize);
        }else{
            $list = $query->select();
        }
        return $list;
    }
}