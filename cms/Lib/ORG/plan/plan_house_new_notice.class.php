<?php
/**
 * Created by PigCMS.
 * User: zhubaodi.
 * Date: 2021/07/5.
 * Time: 15:43.
 */

/**
 * 社区欠费自动发送微信通知提醒 一天一次
 */
class plan_house_new_notice extends plan_base
{
    //主计划任务 需要跑入的日期
    public function runTask()
    {
        $time = strtotime(date('Y-m-d 08:30:00'));
        $time2 = strtotime(date('Y-m-d 09:30:00'));
        $arr = array();
        $arr['param'] = '';
        $arr['plan_time'] = $time;
        $arr['space_time'] = 0;
        $arr['add_time'] = time();
        $arr['file'] = 'sub_house_new_notice_1';
        $arr['time_type'] = 1;
        $arr['unique_id'] = 'sub_house_new_notice_1';
        $arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
        $data[] = M('process_sub_plan')->data($arr)->add();

        $arr = array();
        $arr['param'] = '';
        $arr['plan_time'] = $time2;
        $arr['space_time'] = 0;
        $arr['add_time'] = time();
        $arr['file'] = 'sub_house_new_notice_2';
        $arr['time_type'] = 1;
        $arr['unique_id'] = 'sub_house_new_notice_2';
        $arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
        $data[] = M('process_sub_plan')->data($arr)->add();


    }

}