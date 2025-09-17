<?php

class House_village_user_unbindModel extends Model
{


    /*得到小区下所有的解绑列表*/
    public function get_limit_list_page($village_id, $pageSize = 20, $condition_where = array())
    {
        if (!$village_id) {
            return null;
        }
        $return = array();
        $condition_where['village_id'] = $village_id;
    
       
        $count = $this->where($condition_where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count, $pageSize, 'page');
        $list = $this->field(true)->where($condition_where)->order('status ASC,`itemid` DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        // var_dump($this->_sql());
        $sing_arr = M('House_village_single')->where(array('village_id' => $village_id))->getField('id,single_name');
        foreach($list as &$v){
            $floor_info = D('House_village_floor')->where(array('floor_id'=>$v['floor_id']))->find();
            $room_info = D('House_village_user_vacancy')->where(array('pigcms_id'=>$v['room_id']))->find();
//            $v['address'] = $floor_info['floor_layer'].$floor_info['floor_name'].$room_info['layer'].$room_info['room'];
            $word_data = array();
            $sing_name = $sing_arr[$floor_info['single_id']] ? $sing_arr[$floor_info['single_id']] : $floor_info['floor_layer'];
            $word_data['floor_name'] = $floor_info['floor_name'];
            $word_data['layer'] = $room_info['layer'];
            $word_data['room'] = $room_info['room'];
            $word_data['single_name'] = $sing_name;
            $room_diy_name = D('House_village')->word_replce_msg($word_data,$village_id);
            $v['address'] = $room_diy_name;

        }


        if ($list) {
            $return['totalPage'] = ceil($count / $pageSize);
            $return['user_count'] = count($list);
            $return['pagebar'] = $p->show();
            $return['list'] = $list;
        }

        return $return;
    }

    /*得到小区下所有的解绑列表*/
    public function get_limit_list_page_new($village_id, $pageSize = 20, $condition_where = array())
    {
        if (!$village_id) {
            return null;
        }
        $return = array();

        $condition = 'u.village_id ='.$village_id;
        $condition_table  = array(C('DB_PREFIX').'house_village_user_unbind'=>'u');
        $left_join = ' LEFT JOIN '.C('DB_PREFIX').'house_village_user_bind b on b.pigcms_id = u.bind_id LEFT JOIN '.C('DB_PREFIX').'house_village_user_bind pb on b.parent_id = pb.pigcms_id'; 

        if($condition_where['status']){//业主列表
            $condition .= 'u.status ='.$condition_where['status'];
        }

        if ($condition_where['_string']) {
            $condition .= ' AND '.$condition_where['_string'];
        }

        $field = 'u.*,pb.name as parent_name,pb.phone as parent_phone';

        $count = D('')->table($condition_table)->where($condition)->count();
        $p = new Page($count,$pageSize,'page');
        $list = D('')->field($field)->table($condition_table)->join($left_join)->where($condition)->order('u.itemid desc')->limit($p->firstRow.','.$p->listRows)->select();

        foreach($list as &$v){ 
            $floor_info = D('House_village_floor')->where(array('floor_id'=>$v['floor_id']))->find();
            $room_info = D('House_village_user_vacancy')->where(array('pigcms_id'=>$v['room_id']))->find();
            $v['address'] = $floor_info['floor_layer'].$floor_info['floor_name'].$room_info['layer'].$room_info['room'];
        }

        if ($list) {
            $return['totalPage'] = ceil($count / $pageSize);
            $return['user_count'] = count($list);
            $return['pagebar'] = $p->show();
            $return['list'] = $list;
        }
        return $return;
    }

}
