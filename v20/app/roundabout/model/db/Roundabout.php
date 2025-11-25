<?php
namespace app\roundabout\model\db;

use think\Model;

class Roundabout extends Model
{
    public function getList($where,$field='*',$page=0,$pageSize){
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