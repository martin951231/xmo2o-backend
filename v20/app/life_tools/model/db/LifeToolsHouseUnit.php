<?php


namespace app\life_tools\model\db;


use think\Model;

class LifeToolsHouseUnit extends Model
{
    public function getChildren($where,$field){
        $list = $this->alias('u')
            ->field($field)
            ->join('life_tools_house_floor f','u.house_id = f.house_id and u.serial_number = f.p_number and f.b_number = u.p_number')
            ->join('life_tools_house_room r','f.house_id = r.house_id and f.serial_number = r.p_number and r.b_number = f.b_number and r.u_number = f.p_number')
            ->where($where)
            ->select()->toArray();
        return $list;
    }
}