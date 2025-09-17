<?php


namespace app\merchant\model\db;


use think\Model;

class MerchantPhysicalCard extends Model
{
    public function getList($where,$field='',$page=0,$pageSize=10){
        if(!$field){
            $field = 'c.*,u.nickname,u.phone';
        }
        $query = $this->alias('c')
            ->field($field)
            ->join('user u','c.uid = u.uid','left')
            ->where($where)
            ->order('c.id desc');
        if($page>0){
            $list = $query->paginate($pageSize)->toArray();
        }else{
            $list = $query->select()->toArray();
        }
        return $list;
    }
    
    public function getTotal($where,$field='*'){
        $total = $this->alias('c')
            ->field($field)
            ->join('merchant_physical_card_order co','c.id = co.card_id and c.mer_id = co.mer_id')
            ->where($where)
            ->findOrEmpty()->toArray();
        return $total;
    }
}