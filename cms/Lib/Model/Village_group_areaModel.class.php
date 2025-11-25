<?php

class village_group_areaModel extends Model
{
   
    public function get_area( $where = array())
    {
        $condition = '`g`.`area_id` = `a`.`area_id`';
        $condition_table  = array(C('DB_PREFIX').'village_group_area'=>'g',C('DB_PREFIX').'area'=>'a');

        if ($where['name']) {
            $condition .= ' AND `a`.`area_name` like "%'.$where['name'].'%"';
        }

        if ($where['area_pid']) {
            $condition .= ' AND `a`.`area_pid` ='.$where['area_pid'];
        }

        if ($where['area_id']) {
            $condition .= ' AND `g`.`area_id` ='.$where['area_id'];
        }
        if ($where['area_type']) {
            $condition .= ' AND `a`.`area_type` ='.$where['area_type'];
        }
        if ($where['pigcms_id']) {
            $condition .= ' AND `g`.`pigcms_id` ='.$where['pigcms_id'];
        }
        if ($where['is_open']) {
            $condition .= ' AND `g`.`is_open` ='.$where['is_open'];
        }

        $field = 'a.area_name,g.*';
        $order = 'g.area_sort DESC,`g`.`pigcms_id` DESC';

        $list = D('')->field($field)->table($condition_table)->where($condition)->order($order)->select();
//var_dump(D('')->_sql());
        return $list;
    }
}
