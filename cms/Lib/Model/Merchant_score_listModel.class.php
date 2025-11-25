<?php
// 商家平台积分
class Merchant_score_listModel extends Model{
     /*得到商家积分列表*/
    public function get_limit_list_page( $column = array(), $pageSize = 20)
    {
        $condition_table  = array(C('DB_PREFIX').'merchant_score_list'=>'s',C('DB_PREFIX').'merchant'=>'m');
        $condition_where = " `s`.`mer_id` = `m`.`mer_id`";

        if($column['mer_id']){
            $condition_where .= " AND `s`.`mer_id`=".intval($column['mer_id']);
        }
        if($column['name']){
            $condition_where .= " AND `m`.`name` like '%".$column['name']."%'";
        }
        if($column['time']){
            if ($column['time'][0] == 'gt') {
                $condition_where .= " AND `s`.`time`>=".$column['time'][1];
            }elseif($column['time'][0] == 'lt'){

                $condition_where .= " AND `s`.`time`<=".$column['time'][1];
            }else{
                if (is_array($column['time'])) {
                    foreach ($column['time'] as $key => $value) {
                        if ($value[0] == 'gt') {
                            $condition_where .= " AND `s`.`time`>=".$value[1];
                        }elseif($value[0] == 'lt'){

                            $condition_where .= " AND `s`.`time`<=".$value[1];
                        }
                    }
                }
            }
        }
        if($column['type']){
            $condition_where .= " AND `s`.`type`=".intval($column['type']);
        }
        
        $condition_field = '`s`.*,m.name';
        $order = ' `s`.`id` DESC';
        $return = array();
        
        $count = D()->table($condition_table)->where($condition_where)->count();
        import('@.ORG.system_page');
        $p = new Page($count, $pageSize, 'page');
        $score_list = D()->table($condition_table)->field($condition_field)->where($condition_where)->order($order)->limit($p->firstRow . ',' . $p->listRows)->select();
        $return['totalPage'] = ceil($count / $pageSize);
        $return['count'] = count($score_list);
        $return['pagebar'] = $p->show();
        $return['list'] = $score_list ?  $score_list : array();
        return $return;
    }
}