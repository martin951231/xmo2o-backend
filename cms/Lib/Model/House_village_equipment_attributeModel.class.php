<?php


class House_village_equipment_attributeModel extends Model
{
    /**
     * 设备绑定的属性列表
     * @author lijie
     * @date_time 2021/04/24
     * @param int $equipment_id
     * @return bool|mixed
     */
    public function get_equipment_attribute_list($equipment_id=0)
    {
        if(!$equipment_id){
            return false;
        }
        $field = 'b.attribute_name,b.id,c.standard_des,a.id';
        $left_join = "as a left join ".C('DB_PREFIX').'house_village_pile_attribute as b on `a`.`attribute_id` = `b`.`id` left join '.C('DB_PREFIX').'house_village_pile_attribute_set as c on `a`.`attribute_standard_id` = `c`.`id` ';

        $where['a.equipment_id'] = $equipment_id;
        $where['a.is_del'] = 1;
        $equipment_attribute_list = M('House_village_pile_equipment_attribute')->field($field)->join($left_join)->where($where)->select();
        return $equipment_attribute_list;
    }

    public function delOne($id)
    {
        $res = M('House_village_pile_equipment_attribute')->where(['id'=>$id])->delete();
        return $res;
    }
}