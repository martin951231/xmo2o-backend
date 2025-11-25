<?php


namespace app\flipping_event\model\db;


use think\Model;

class FlippingEventRulesLog extends Model
{
    public function getList($where,$field,$page=0,$pageSize=10){
        $query = $this->alias('l')
            ->field($field)
            ->join('user u','l.uid = u.uid')
            ->join('flipping_event_rules r','l.rules_id = r.id','left')
            ->where($where)
            ->order('l.id desc');
        if($page>0){
            $list = $query->paginate($pageSize);
        }else{
            $list = $query->select();
        }
        return $list->toArray();
    }
}