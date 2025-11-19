<?php


namespace app\appoint\model\db;


use think\Model;

class MerchantWorkersMoney extends Model
{
    protected $pk = 'pigcms_id';
    
    public function getTimeAttr($value)
    {
        if($value){
            $value = date('Y-m-d H:i:s', $value);
        }
        return $value;
    }
    
    public function getList($where,$field='*',$page=0,$pageSize=10){
        $query = $this->field($field)
            ->where($where)
            ->order('pigcms_id desc');
        if($page>0){
            $list = $query->paginate($pageSize);
        }else{
            $list = $query->select();
        }
        return $list->toArray();
    }
}