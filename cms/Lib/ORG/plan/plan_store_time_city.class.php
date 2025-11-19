<?php
/**
 * Created by PigCMS.
 * User: JJC.
 * Date: 2019/4/17.
 * Time: 16:33.
 */

/**
 * Class plan_store_time 店铺营业时间->城市分解计划任务
 */
class plan_store_time_city extends plan_base{
    //子计划任务 划分店铺
    public function runTask($param = array()){

        if(empty($param['city_id'])){
            return true;
        }

        $where = array();
        $where['status'] = 1;
        $where['city_id'] = $param['city_id'];
        $store_list = M('Merchant_store')->field('`store_id`')->where($where)->select();
        if(empty($store_list)){
            return true;
        }
        $now_time=time();
        foreach($store_list as $key => $val){
            $this->keepThread();
            $store_id = $val['store_id'];
            $arr= array();
            $arr['param'] = serialize(array('store_id'=>$store_id));
            $arr['plan_time'] = -120;
            $arr['space_time'] = 0;//子计划任务不需要每分钟执行，主计划任务会唤醒
            $arr['add_time'] = $now_time;
            $arr['file'] = 'store_time_store';
            $arr['time_type'] = 1;
            $arr['unique_id'] = 'store_time_store_'.$store_id;
			$arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
            M('process_sub_plan')->data($arr)->add();
        }
		
		return true;
    }
}