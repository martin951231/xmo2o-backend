<?php


namespace app\group\model\db;


use think\Model;

class GroupRenovationGoodsCustomTop extends Model
{
    public function getList($where,$field='*',$page=0,$pageSize=10,$order='gt.id asc',$group=''){
        $query = $this->alias('gt')
            ->field($field)
            ->join('group g','gt.group_id = g.group_id')
            ->join('group_store gs','gs.group_id = g.group_id')
            ->join('merchant_store s','gs.store_id = s.store_id')
            ->join('merchant m','s.mer_id = m.mer_id')
            ->where($where)
            ->order($order);
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