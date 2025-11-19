<?php
/**
 * [平台首页&&外卖首页]发现好菜自定义分类
 */
class Diy_categoryModel extends Model
{

    public function get_one($id){
    	return $this->where(['pigcms_id'=>$id])->find();
    }


    public function get_same_category_goods($type){
    	$where = ['is_del'=>0,'type'=>$type];
    	$list = $this->field('goods_ids')->where($where)->select();
    	$goods_arr = [];
    	foreach ($list as $key => $val) {
    		$temp_arr = explode(',', $val['goods_ids']);
    		foreach ($temp_arr as $k => $v) {
    			$goods_arr[] = $v;
    		}
    	}
    	return $goods_arr;
    }


    public function save_category_goods($category_id,$goods_id){
    	$where['pigcms_id'] = $category_id;
    	$goods_str = implode(',', $goods_id);
    	$data['goods_ids'] = $goods_str;
    	$result = $this->where($where)->save($data);
    	return $result;
    }

    public function get_normal_category($type,$city_id){
        $now_time = time();
        $where = [];
        $where['is_del']     =  0;
        $where['status']     =  1;
        $where['city_id']    =  $city_id;
        $where['type']       =  $type;
        $where['begin_time'] =  ['elt',$now_time];
        $where['end_time']   =  ['egt',$now_time];
        $field = 'pigcms_id as sort_id,name as sort_name';
        return $this->where($where)->field($field)->select();
    }


    public function get_normal_goods_id($type,$sort_id){
    	if(!$type) return [];
    	$where = [];
    	$where['is_del']     =  0;
        $where['status']     =  1;
        $where['type']       =  $type;
        if($sort_id){
        	$where['pigcms_id']  =  $sort_id;
        }
        $list = $this->where($where)->select();
        $goods_arr = [];
        if ($list) {
        	foreach ($list as $key => $value) {
        		$goods_ids = explode(',', $value['goods_ids']);
        		foreach ($goods_ids as $k => $v) {
        			if($v){
        				$goods_arr[] = $v;
        			}
        		}
        	}
        }
        return $goods_arr;
    }
}
?>