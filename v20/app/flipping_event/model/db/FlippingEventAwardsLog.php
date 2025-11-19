<?php


namespace app\flipping_event\model\db;


use think\Model;

class FlippingEventAwardsLog extends Model
{
    public function getList($where,$field,$page=0,$pageSize=10,$group=''){
        $query = $this->alias('l')
            ->field($field)
            ->join('user u','l.uid = u.uid')
            ->join('flipping_event_awards a','l.awards_id = a.id')
            ->where($where)
            ->order('l.id desc');
        if($group){
            $query = $query->group($group);
        }
        if($page>0){
            $list = $query->paginate($pageSize);
        }else{
            $list = $query->select();
        }
        return $list->toArray();
    }
}