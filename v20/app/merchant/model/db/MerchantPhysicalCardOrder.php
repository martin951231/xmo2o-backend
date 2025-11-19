<?php


namespace app\merchant\model\db;


use think\Model;

class MerchantPhysicalCardOrder extends Model
{
    public function getList($where,$field='',$page=0,$pageSize=10){
        if(!$field){
            $field = 'co.*,s.name as store_name';
        }
        $query = $this->alias('co')->field($field)
            ->join('merchant_store s','co.store_id = s.store_id','left')
            ->where($where)
            ->order('co.add_time desc');
        if($page>0){
            $list = $query->paginate($pageSize)->toArray();
        }else{
            $list = $query->select()->toArray();
        }
        return $list;
    }
}