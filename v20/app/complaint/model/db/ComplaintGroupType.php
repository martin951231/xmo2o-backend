<?php


namespace app\complaint\model\db;


use think\Model;

class ComplaintGroupType extends Model
{
    public function getList($where,$page,$pageSize){
        $data = $this->field(true)
            ->where($where)
            ->order('sort desc, id asc')
            ->paginate($pageSize)->toArray();
        return $data;
    }
}