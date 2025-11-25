<?php
/**
 * Created by PigCMS.
 * User: zhubaodi.
 * Date: 2021/04/21.
 * Time: 10:43.
 */

use app\community\model\db\HouseMeterElectricGroup;

/**
 * Class plan_mer_revenue_statistics 商家营业统计->主计划任务
 */
class plan_house_meter_reading extends plan_base
{
    //主计划任务 需要跑入的日期
    public function runTask()
    {

        $group_list = M('house_meter_electric_group')->where(['status' => 1])->select();
        if (empty($group_list)){
            return true;
        }
/*        foreach ($group_list as $value){
            $arr = array();
            $arr['param'] = serialize(array('id' => $value['id'],'tcp_address'=>$value['tcp_address']));
            $arr['plan_time'] = 1;
            $arr['space_time'] = 0;
            $arr['add_time'] = time();
            $arr['file'] = 'sub_house_meter_reading';
            $arr['time_type'] = 1;
            $arr['unique_id'] = 'sub_house_meter_reading' .$value['id'] ;
            $arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
           $data[]= M('process_sub_plan')->data($arr)->add();

        }*/


        $time=[0=>1,1=>strtotime(date('Y-m-d 01:30:00')),2=>strtotime(date('Y-m-d 02:00:00')),3=>strtotime(date('Y-m-d 02:30:00')),4=>strtotime(date('Y-m-d 03:00:00'))];
        foreach ($group_list as $value){
            for($i=0;$i<5;$i++){
                $arr = array();
                $arr['param'] = serialize(array('id' => $value['id'],'tcp_address'=>$value['tcp_address']));
                $arr['plan_time'] = $time[$i];
                $arr['space_time'] = 0;
                $arr['add_time'] = time();
                $arr['file'] = 'sub_house_meter_reading';
                $arr['time_type'] = 1;
                $arr['unique_id'] = 'sub_house_meter_reading' .$i.$value['id'] ;
                $arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
                $data[]= M('process_sub_plan')->data($arr)->add();
            }
        }
        return true;
    }
}