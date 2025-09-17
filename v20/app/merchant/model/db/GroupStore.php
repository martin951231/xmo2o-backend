<?php


namespace app\merchant\model\db;


use think\Model;

class GroupStore extends Model
{
    public function getStoreList($where,$field="b.*"){
        $list = $this->field($field)->alias('a')
            ->join('merchant_store b','a.store_id = b.store_id')
            ->where($where)
            ->order('b.store_id asc')
            ->select();
        return $list;
    }
}