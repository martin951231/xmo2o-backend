<?php


namespace app\roundabout\model\db;


use think\Model;

class RoundaboutUser extends Model
{
    public function getList($where,$field='*',$page=0,$pageSize=10){
        $query = $this->alias('ru')
            ->field($field)
            ->join('user u','ru.uid = u.uid')
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