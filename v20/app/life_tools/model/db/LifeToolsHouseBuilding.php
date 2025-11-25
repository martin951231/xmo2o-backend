<?php


namespace app\life_tools\model\db;


use think\Model;

class LifeToolsHouseBuilding extends Model
{
    public function getInfo($where,$field,$order){
        $list = $this->alias('b')
            ->field($field)
            ->join('life_tools_house_unit u','b.house_id = u.house_id and b.serial_number = u.p_number')
            ->join('life_tools_house_floor f','b.house_id = f.house_id and u.serial_number = f.p_number and f.b_number = b.serial_number')
            ->join('life_tools_house_room r','b.house_id = r.house_id and f.serial_number = r.p_number and r.b_number = b.serial_number and r.u_number = u.serial_number')
            ->where($where)
            ->order($order)
            ->select()->toArray();
        return $list;
    }
}