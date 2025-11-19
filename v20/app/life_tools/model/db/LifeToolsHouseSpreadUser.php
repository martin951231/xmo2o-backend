<?php


namespace app\life_tools\model\db;


use think\Model;

class LifeToolsHouseSpreadUser extends Model
{
    public function getList($where,$field='*',$page=0,$pageSize=10){
        $query = $this->alias('s')
            ->field($field)
            ->join('user u','s.uid = u.uid')
            ->where($where)
            ->order('s.id desc');
        if($page>0){
            $list = $query->paginate($pageSize);
        }else{
            $list = $query->select();
        }
        return $list->toArray();
    }
}