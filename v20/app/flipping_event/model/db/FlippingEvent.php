<?php


namespace app\flipping_event\model\db;


use think\Model;

class FlippingEvent extends Model
{
    public function getList($where,$field="*",$page=0,$pageSize=10){
        $query = $this->field($field)
            ->where($where)
            ->order('id desc');
        if($page>0){
            $list = $query->paginate($pageSize)->toArray();
        }else{
            $list = $query->select()->toArray();
        }
        return $list;
    }
}