<?php


namespace app\life_tools\model\db;


use think\Model;

class LifeToolsHouseCardUser extends Model
{
    public function getList($where,$field,$order){
        $list = $this->alias('uc')
            ->field($field)
            ->join('life_tools_house_building b','b.house_id = uc.house_id and b.serial_number = uc.b_number')
            ->join('life_tools_house_unit u','b.house_id = u.house_id and b.serial_number = u.p_number and u.serial_number = uc.u_number')
            ->join('life_tools_house_floor f','u.house_id = f.house_id and u.serial_number = f.p_number and f.b_number = u.p_number and f.serial_number = uc.f_number')
            ->join('life_tools_house_room r','f.house_id = r.house_id and f.serial_number = r.p_number and r.b_number = f.b_number and r.u_number = f.p_number and r.serial_number = uc.r_number')
            ->where($where)
            ->order($order)
            ->select()->toArray();
        return $list;
    }
    
    public function getCardUserList($where,$field='',$page=0,$pageSize=10){
        $query = $this->alias('uc')
            ->field($field)
            ->join('life_tools_houses_list h','uc.house_id = h.houses_id and h.is_del = 0')
            ->join('life_tools_house_building b','b.house_id = uc.house_id and b.serial_number = uc.b_number')
            ->join('life_tools_house_unit u','b.house_id = u.house_id and b.serial_number = u.p_number and u.serial_number = uc.u_number')
            ->join('life_tools_house_floor f','u.house_id = f.house_id and u.serial_number = f.p_number and f.b_number = u.p_number and f.serial_number = uc.f_number')
            ->join('life_tools_house_room r','f.house_id = r.house_id and f.serial_number = r.p_number and r.b_number = f.b_number and r.u_number = f.p_number and r.serial_number = uc.r_number')
            ->where($where)
            ->order('uc.id desc');
        if($page>0){
            $list = $query->paginate($pageSize);
        }else{
            $list = $query->select();
        }
        return $list->toArray();
    }
}