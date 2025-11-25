<?php
class Deliver_user_clock_timeModel extends Model
{

    /**
     * 查询总条数
     */
    public function getOnlineTimeCount($where)
    {
        $data = $this->field('dt.uid')
                ->join(' as dt LEFT JOIN ' . C('DB_PREFIX') . 'deliver_user as d ON dt.uid = d.uid')
                ->group('uid')
                ->where($where)
                ->select();
        return count($data);
    }
 
    /**
     * 获取在线时间段列表
     */
    public function getOnlineTimeList($field, $where, $limit)
    { 
        $data = $this->field($field)
                ->join(' as dt LEFT JOIN ' . C('DB_PREFIX') . 'deliver_user as d ON dt.uid = d.uid')
                ->group('uid')
                ->where($where)
                ->order('dt.id DESC')
                ->limit($limit)
                ->select();
        return $data;
    }

    /**
     * 获取用户在线时间段列表
     * 
     */
    public function getUserOnlineTimeList($uid,$limit=30)
    {
        $field ='id,FROM_UNIXTIME(start_time, "%Y-%m-%d %H:%i:%s") as start,
                FROM_UNIXTIME(end_time, "%Y-%m-%d %H:%i:%s") as end,
                FROM_UNIXTIME(end_time, "%Y-%m-%d") as date,working_time'; 

        $where = array(
            'uid'   =>  $uid
        );
        
        if($limit > 0){
            return $this->field($field)->where($where)->limit($limit)->order('id desc')->select();
        }else{
            return $this->field($field)->where($where)->order('id desc')->select();
        }
    }


    //获取配送员作息时间
    public function getDeliverUserSchedule($uid, $startTime, $endTime)
    {
        $where = [
            'uid' => $uid,
            'start_time' => ['EGT', $startTime],
            'end_time' => ['ELT', $endTime],
        ];
        $field = '*,FROM_UNIXTIME(start_time,"%Y-%m-%d %H:%i") AS start_hour,FROM_UNIXTIME(end_time,"%Y-%m-%d %H:%i") AS end_hour';
        $logs = $this->where($where)->field($field)->order('id ASC')->select();
        $rs = [
            'total_working_time' => 0,  //总工作时间，单位秒
            'average_working_time' => 0, //平均工作时间，单位秒
            'total_working_days' => 0,  //出勤天数
            'work_hours' => '',  //上班时间
            'off_duty_hours' => '' //下班时间
        ];
        if (empty($logs)) {
            return $rs;
        }

        $rs['total_working_time'] = array_sum(array_column($logs, 'working_time'));
        $i = 0;

        while ($startTime + $i * 86400 <= $endTime) {
            $thisTime = $startTime + $i * 86400;
            foreach ($logs as $l) {
                $min = strtotime(date('Y-m-d', $l['start_time']));
                $max = strtotime(date('Y-m-d', $l['end_time'])) + 86400;
                if ($thisTime >= $min && $thisTime < $max) {
                    $rs['total_working_days'] += 1;
                    break;
                }
            }
            $i++;
        }
        $rs['average_working_time'] = $rs['total_working_days'] > 0 ? ceil($rs['total_working_time'] / $rs['total_working_days']) : 0;

        //获取每天上下班时间
        $getHour = function ($r) {
            return substr($r, -5);
        };

        $startArr = [];
        $endArr = [];
        foreach ($logs as $v) {
            list($startDate, $startHour) = explode(' ', $v['start_hour']);
            list($endDate, $endHour) = explode(' ', $v['end_hour']);
            if (!isset($startArr[$startDate]) || strtotime($startArr[$startDate]) > strtotime($startHour)) {
                $startArr[$startDate] = strtotime($startHour);
            }
            if (!isset($endArr[$endDate]) || strtotime($endArr[$endDate]) > strtotime($endHour)) {
                $endArr[$endDate] = strtotime($endHour);
            }
        }

        asort($startArr);
        asort($endArr);
        $workHours = array_count_values($startArr);
        $offDutyHours = array_count_values($endArr);
        arsort($workHours);
        arsort($offDutyHours);
        $workHoursKeys = array_keys($workHours);
        $offDutyHoursKeys = array_keys($offDutyHours);
        $rs['work_hours'] = date('H:i', reset($workHoursKeys));
        $rs['off_duty_hours'] = date('H:i', reset($offDutyHoursKeys));
        return $rs;
    }
}