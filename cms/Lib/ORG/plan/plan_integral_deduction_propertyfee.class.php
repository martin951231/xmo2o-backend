<?php

//积分抵扣物业费
class plan_integral_deduction_propertyfee extends plan_base
{
    public function runTask()
    {
        $where['status'] = ['in', [0, 1]];
        $where['points_deduction_day_set'] = ['neq', ''];
        $day = (int)date('d', time());
        $village_data = D('House_village')->where($where)->field('village_id,points_deduction_day_set,points_deduction_time_set,sms_notice_set')->select();
        if ($village_data && count($village_data) > 0) {
            foreach ($village_data as $v) {
                if (!$v['points_deduction_day_set'] || $v['points_deduction_day_set'] == '') {
                    continue;
                }
                if ($v['points_deduction_day_set'] && $v['points_deduction_day_set'] == $day) {
                    $points_deduction_day_set = $v['points_deduction_day_set'];
                    $points_deduction_time_set = $v['points_deduction_time_set'];
                    // if(strtotime(date('Y-m-d 00:00:00',time())) == strtotime(date("Y-m-$points_deduction_day_set 00:00:00",time()))) {
                    $time = time();
                    $starttime = $time - 10;
                    $endtime = $time + 3000;
                    $date = strtotime(date("Y-m-$points_deduction_day_set $points_deduction_time_set:00:00"));
                    //  if ($time== strtotime(date("Y-m-$points_deduction_day_set $points_deduction_time_set:00:00"))) {
                    if ($date >=$starttime&&$date<=$endtime) {
                        D('House_village')->deduction_points($v['village_id']);
                    }
                }
            }
        }
        return true;
    }
}
