<?php
/**
 * 定时更新外卖商品限时优惠状态
 * User: 衡婷妹.
 * Date: 2021/02/02.
 * Time: 16:15.
 */

/**
 * 主计划任务
 */
class plan_shop_goods_time_limit extends plan_base{
    //主计划任务
    public function runTask(){
		
        //查找店铺列表
        $where = array();
        $where['s.status'] = 1;
        $where['g.is_del'] = 0;
        $join = ' as s join '.C('DB_PREFIX').'time_limited_discount_goods as g on g.store_id=s.store_id';
        $store_list = M('Merchant_store')->field('s.`store_id`')->join($join)->where($where)->select();
        if(empty($store_list)){
            return true;
        }
        $now_time = time();
        foreach($store_list as $key =>$val){
            $this->keepThread();
            $store_id = $val['store_id'];
            $arr= array();
            $arr['param'] = serialize(array('store_id'=>$val['store_id']));
            $arr['plan_time'] = -120;
            $arr['space_time'] = 0;
            $arr['add_time'] = $now_time;
            $arr['file'] = 'sub_shop_goods_time_limit';
            $arr['time_type'] = 1;
            $arr['unique_id'] = 'shop_goods_time_limit'.$store_id;
            M('process_sub_plan')->data($arr)->add();
        }
        return true;
    }
}