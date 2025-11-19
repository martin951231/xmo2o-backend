<?php
/**
 * Created by PigCMS.
 * User: htm.
 * Date: 2019/12/21.
 * Time: 10:43.
 */

/**
 * Class plan_sub_mer_revenue_statistics 商家营业统计->子计划任务
 */
class plan_sub_mer_revenue_statistics_date extends plan_base{
    //子计划任务 划分商家
   public function runTask($param = array()){
        // 统计数据日期时间戳
        if(empty($param['date'])||empty($param['type'])){
            return true;
        }

        //查找商家列表
        $mer_list = M('Merchant')->field('`mer_id`')->select();
        $mer_list = array_column($mer_list, 'mer_id');
        switch ($param['type']) {
            case 'mall': //商城统计
            case 'shop': // 外卖统计
            case 'market': // 营销统计
                if(empty($mer_list)){
                    return true;
                }
                break;
            case 'deliver': // 配送统计
                // 加上跑腿、社区团购（无商家id）
                $mer_list = array_merge($mer_list,[-1,-2]);
                break;
        }
        $now_time = time();
        foreach($mer_list as $key =>$val){
            $this->keepThread();
            $mer_id = $val;
            $arr= array();
            $arr['param'] = serialize(array('mer_id'=>$mer_id,'date'=>$param['date'],'type'=>$param['type']));
            $arr['plan_time'] = 1;
            $arr['space_time'] = 0;
            $arr['add_time'] = $now_time;
            $arr['file'] = 'sub_mer_revenue_statistics';
            $arr['time_type'] = 1;
            $arr['unique_id'] = 'sub_mer_revenue_statistics'.$mer_id.$param['date'].$param['type'];
			$arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
            M('process_sub_plan')->data($arr)->add();
        }
        return true;
    }
}