<?php
/**
 *
 * User:
 * Date: 2019/7/2
 * Time: 10:53
 */
class Mall_full_actModel extends Model
{
    public function get_full_list($goods_id,$store_id,$ing=true){
        $where=array();
        if(!$goods_id){

         return array();
        }
        if($ing){
            $where['stime'] = array('elt',time());
            $where['ftime']   = array('egt',time());
        }
        $where['is_del'] = 0;
        $where['store_id'] = $store_id;
        $where['status'] = 1;
        $where['_string'] = "FIND_IN_SET('$goods_id', goods_id)";
        $info = $this->where($where)->order('id desc')->find();
        return $info;


    }

}