<?php
class deliver_score_log {
    public function get_limit_list_page($condition = array(),$pageSize = 20)
    {
       
        $return = array();
        $condition_where = '`l`.`uid` = `u`.`uid`';
        $condition_table  = array(C('DB_PREFIX').'deliver_score_log'=>'l',C('DB_PREFIX').'deliver_user'=>'u');

        if($condition['uid']){
            $condition_where .= ' AND l.uid in (' . $condition['uid'] . ')';
        }

        if ($condition['other']) {
            $condition_where .= ' AND '.$condition['other'];
        }

        $field = 'l.*,u.name,u.phone';
        $order = ' `l`.`id` DESC';

        $count = M('')->table($condition_table)->where($condition_where)->count();
        import('@.ORG.system_page');
        $p = new Page($count,$pageSize,'page');
        $list = M('')->field($field)->table($condition_table)->where($condition_where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();

        // var_dump(D('')->_sql());

        $return['totalPage'] = ceil($count / $pageSize);
        $return['count'] = count($list);
        $return['pagebar'] = $p->show();
        $return['list'] = $list ? $list : array();
        

        return $return;
    }

    /*增加记录行数
    *  param from  1-管理员操作，2-完成订单，3-好评，4-准时达，5差评，6-超时，7-降级设置，8-其他
    */
    public function add_row($uid,$type,$from,$score,$msg,$time = 0,$param=array()){
        if(!$time){
            $time=  time();
        }
        $data_user_score_list['uid']   = $uid;
        $data_user_score_list['type']  = $type;
        $data_user_score_list['score'] = $score;
        $data_user_score_list['desc']  = $msg;
        $data_user_score_list['from']  = $from;
        $data_user_score_list['time']  = $time;

        $param['supply_id']  &&  $data_user_score_list['supply_id']  = $param['supply_id'];
        $param['store_id']  &&  $data_user_score_list['store_id']  = $param['store_id'];
        $param['order_id']  &&  $data_user_score_list['order_id']  = $param['order_id'];
        $param['item']  &&  $data_user_score_list['item']  = $param['item'];
       
        if(M('Deliver_score_log')->data($data_user_score_list)->add()){
            return true;
        }else{
            return false;
        }
    }
}