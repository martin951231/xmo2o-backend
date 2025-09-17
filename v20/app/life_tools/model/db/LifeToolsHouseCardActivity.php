<?php


namespace app\life_tools\model\db;


use think\Model;

class LifeToolsHouseCardActivity extends Model
{
    public function getHouseList($where,$field='*',$page,$pageSize){
        $list = $this->alias('a')
            ->field($field)
            ->join('life_tools_houses_list h',"find_in_set(h.houses_id,a.house_ids)")
            ->where($where)
            ->order('a.end_time desc,h.houses_id desc')
            ->paginate($pageSize)
            ->toArray();
        return $list;
    }
}