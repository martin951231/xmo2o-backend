<?php
class Gift_pick_orderModel extends Model{

    /*得到订单列表*/
    public function get_limit_list_page($condition , $page = 20){
        $condition_where = 'po.order_id=o.order_id AND o.gift_id=g.gift_id';
        if($condition['order_id']){
            $condition_where .= " AND `o`.`order_id` = '{$condition['order_id']}' ";
        }
        if($condition['nickname']){
            $condition_where .= " AND `u`.`nickname` = '{$condition['nickname']}' ";
        }
        if($condition['phone']){
            $condition_where .= " AND `u`.`phone` = '{$condition['phone']}' ";
        }
        if($condition['order_name']){
            $condition_where .= " AND `o`.`order_name` = '{$condition['order_name']}' ";
        }
        if($condition['pick_id']){
            $condition_where .= " AND `po`.`pick_id` = '{$condition['pick_id']}' ";
        }
        if($condition['type']){
            $condition_where .= " AND `po`.`type` = '{$condition['type']}' ";
        }
        if($condition['begin_time']&&!$condition['end_time']){
            $condition_where .= " AND `o`.`pay_time` >= '{$condition['begin_time']}' ";
        }
        if(!$condition['begin_time']&&$condition['end_time']){
            $condition_where .= " AND `o`.`pay_time` <= '{$condition['end_time']}' ";
        }
        if($condition['begin_time']&&$condition['end_time']){
            $condition_where .= " AND `o`.`pay_time` BETWEEN '{$condition['begin_time']}' AND '{$condition['end_time']}' ";
        }

        $left_join = 'LEFT JOIN '.C('DB_PREFIX').'user as u on `o`.`uid`=`u`.`uid` LEFT JOIN '.C('DB_PREFIX').'merchant as m on `o`.`mer_id`=`m`.`mer_id`';
        $condition_table = array(C('DB_PREFIX').'gift_pick_order'=>'po',C('DB_PREFIX').'gift'=>'g', C('DB_PREFIX').'gift_order'=>'o');
        $condition_field = '`po`.`status` AS `pstatus`,`po`.`order_id` AS `p_order_id`,`o`.`phone` AS `gift_phone`,`o`.*,`g`.`gift_name`,`u`.`uid`,`u`.`nickname`,`u`.`phone`,`g`.`gift_id`,`m`.`name`,`m`.`phone` as m_phone';

        import('@.ORG.merchant_page');
        $count = D()->table($condition_table)->join($left_join)->where($condition_where)->count();
        $p = new Page($count,$page);
        $order_list = D()->table($condition_table)->field($condition_field)->join($left_join)->where($condition_where)->order('`o`.`order_time` DESC')->limit($p->firstRow.','.$p->listRows)->select();

        $return['pagebar'] = $p->show();
        $return['list'] = $order_list;

        return $return;
    }
}
?>