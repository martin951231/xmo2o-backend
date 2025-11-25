<?php


namespace app\group\model\db;


use think\Model;

class GroupRenovationGoodsCustom extends Model
{
    public function getLsit($where,$field='c.*',$page=0,$pageSize=10){
        $query = $this->alias('c')
            ->field($field)
            ->join('group_category gc','c.type = gc.cat_id','left')
            ->where($where)
            ->order('sort desc,custom_id asc');
        if($page>0){
            $list = $query->paginate($pageSize);
        }else{
            $list = $query->select();
        }
        return $list->toArray();
    }
}