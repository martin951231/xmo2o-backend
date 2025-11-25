<?php

class village_group_goods_bind_areaModel extends Model
{
    public function get_area_goods_list( $where = array(),$pageSize = 20)
    {
        $condition = '`g`.`goods_id` = `a`.`goods_id`';
        $condition_table  = array(C('DB_PREFIX').'village_group_goods_bind_area'=>'a',C('DB_PREFIX').'village_group_goods'=>'g');
        $return = array();
        // if ($where['name']) {
        //     $condition .= ' AND `a`.`area_name` like "%'.$where['name'].'%"';
        // }

        if ($where['city_id']) {
            $condition .= ' AND `a`.`city_id` ='.$where['city_id'];
        }
        if ($where['pigcms_id']) {
            $condition .= ' AND `a`.`pigcms_id` ='.$where['pigcms_id'];
        }

        $field = 'a.pigcms_id,a.city_id,a.price as area_price,a.min_price,a.max_price,a.address_list,a.buy_user_type,a.is_add_price,a.time as bind_time,g.*';
        $order = 'a.pigcms_id DESC';

        $count = D('')->table($condition_table)->where($condition)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count, $pageSize, 'page');
        $list = D('')->field($field)->table($condition_table)->where($condition)->order($order)->limit($p->firstRow . ',' . $p->listRows)->select();
        if ($list) {
            $return['totalPage'] = ceil($count / $pageSize);
            $return['count'] = count($list);
            $return['pagebar'] = $p->show();
            $return['list'] = $list;
        }

        return $return;
    }    

    public function get_area_goods_bind_info( $goods_id,$province_id = 0,$city_id = 0,$area_id = 0)
    {
        $left_join = 'as b JOIN '.C('DB_PREFIX').'village_group_area ga ON ga.pigcms_id = b.city_id';
        $where = "b.goods_id = {$goods_id} AND (ga.area_id={$province_id}  OR ga.area_id= {$city_id} OR ga.area_id={$area_id})" ;
        $field = 'b.*,ga.*';
        $goods_bind_area = $this->field($field)->join($left_join)->where($where)->select();
        $return = array();
        foreach ($goods_bind_area as $key => $value) {
            if ($value['area_id'] == $city_id) {
                $return =  $value;
                break;
            }elseif ($value['area_id'] == $province_id) {
                $return =  $value;
            }elseif($value['area_id'] == $area_id){
                $return =  $value;
            }
        }
        return $return;
    }
}
