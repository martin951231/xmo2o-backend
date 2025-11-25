<?php


namespace app\storestaff\model\db;


use think\Model;

class CouponVerifyRecord extends Model
{
    public function getList($where,$page=0,$pageSize=10){
        $query = $this->field('*')
            ->where($where)
            ->order('id desc');
        if($page>0){
           $list = $query->paginate($pageSize); 
        }else{
            $list = $query->select();
        }
        return $list->toArray();
    }
}