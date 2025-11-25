<?php


namespace app\life_tools\model\db;


use think\Model;

class LifeToolsHouseVisitLog extends Model
{
    public function getList($where,$field="*",$page=0,$pageSize=10){
        $query = $this->alias('v')
            ->field($field)
            ->join('user u','v.uid = u.uid')
            ->join('life_tools_houses_list h','h.houses_id = v.house_id','left')
            ->where($where)
            ->order('v.last_time desc,v.id desc');
        if($page>0){
            $list = $query->paginate($pageSize);
        }else{
            $list = $query->select();
        }
        return $list->toArray();
    }
}