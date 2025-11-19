<?php


namespace app\shop\model\db;


use think\Model;

class ShopRebate extends Model
{
    public function getList($where,$page=0,$pageSize=0,$field="a.*",$order='a.id desc'){
        $query = $this->alias('a')
            ->field($field)
            ->where($where)
            ->order($order);
        if($page>0){
            $list = $query->paginate($pageSize)->toArray();
        }else{
            $list = $query->select();
        }
        return $list;
    }
}