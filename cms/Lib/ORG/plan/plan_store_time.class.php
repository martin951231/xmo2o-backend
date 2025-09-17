<?php
/**
 * Created by PigCMS.
 * User: JJC.
 * Date: 2019/4/17.
 * Time: 15:38.
 */

/**
 * Class plan_store_time 店铺营业时间->主计划任务
 */
class plan_store_time extends plan_base{
    //主计划任务划分城市
    public function runTask(){
		
		//删除无效数据
		$delete_where = array();
		$delete_where['time_sort'] = array('neq',1);
		$delete_where['open_time'] = '00:00:00';
		$delete_where['close_time'] = '00:00:00';
		D('Merchant_store_open_time')->where($delete_where)->delete();
		
        //查找店铺列表
        $where = array();
        $where['status'] = 1;
        $store_list = M('Merchant_store')->field('`store_id`,`city_id`')->group('`city_id`')->where($where)->select();
        //$last = M()->getLastSql();
        //fdump($last,'plan');
        if(empty($store_list)){
            return true;
        }
        $now_time = time();
        foreach($store_list as $key =>$val){
            $this->keepThread();
            $city_id = $val['city_id'];
            $arr= array();
            $arr['param'] = serialize(array('city_id'=>$city_id));
            $arr['plan_time'] = -120;
            $arr['space_time'] = 0;
            $arr['add_time'] = $now_time;
            $arr['file'] = 'store_time_city';
            $arr['time_type'] = 1;
            $arr['unique_id'] = 'store_time_city_'.$city_id;
			$arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
            M('process_sub_plan')->data($arr)->add();
            //$last = M()->getLastSql();
            //fdump($last,'plan');
        }
        return true;
    }
}