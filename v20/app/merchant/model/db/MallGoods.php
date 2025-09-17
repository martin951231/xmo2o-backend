<?php


namespace app\merchant\model\db;


use think\Model;

class MallGoods extends Model
{
    public function getList($where, $page=0, $pageSize=10, $field='a.*,b.name as store_name'){
        $query = $this->field($field)->alias('a')
            ->join('merchant_store b','a.store_id = b.store_id and b.status = 1')
            ->where($where)
            ->order('a.goods_id desc');
        if($page>0){
            $list = $query->paginate($pageSize);
        }else{
            $list = $query->select();
        }
        return $list;
    }
}