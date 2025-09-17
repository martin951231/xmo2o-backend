<?php


namespace app\common\model\db;


use think\Model;

class HomeSearchRanking extends Model
{
    public function getList($where,$field="*",$page=0,$pageSize=10,$sort='id desc'){
        $query = $this->field($field)
            ->where($where)
            ->order($sort);
        if($page>0){
            $list = $query->paginate($pageSize);
        }else{
            $list = $query->limit($pageSize)->select();
        }
        return $list->toArray();
    }
}