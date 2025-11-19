<?php


namespace app\complaint\model\db;


use think\Model;

class ComplaintGroup extends Model
{
    public function getList($where,$page,$pageSize){
        $data = $this->field(true)
            ->where($where)
            ->order('sort desc, id asc')
            ->paginate($pageSize)->toArray();
        return $data;
    }
}