<?php


namespace app\employee\model\db;


use think\Model;

class EmployeeCardClearScore extends Model
{
    use \app\common\model\db\db_trait\CommonFunc;

    public function user()
    {
        return $this->belongsTo(User::class, 'uid', 'uid');
    }
    
    /**
     * 计算下次清积分日期
     * @param array card 员工卡数组
     */
    public function calcNextClearScoreTime($card)
    {
        $time = time();
        $date_d = date('d');
        $date_m = date('m');
        $date_y = date('Y');
        $date_w = date('w');
        list($h, $i) = explode(':', $card['clear_time']);

        $return = [];
        if(!isset($card['clear_score'])){
            return -1;
        }

        switch($card['clear_score']){
            case 0: //不清零
                $next_clear_time = -1;
                break;
            case 1://月底清零
                
                //本月底清零时间
                $next_clear_time = mktime($h, $i, 0, $date_m+1 , 0, $date_y); 
                //下月
                if($next_clear_time <= $time){
                    $next_clear_time = mktime($h, $i, 0, $date_m+2 , 0, $date_y);
                }

                break;
            case 2://每月固定时间清零
                if(!isset($card['clear_date'])){
                    return -1;
                }
                //每月最后一天
                $monthEnd = date('d', mktime(0, 0, 0, $date_m+1 , 0, $date_y)); 
                if($monthEnd < $card['clear_date']){
                    $card['clear_date'] = $monthEnd;
                }
                $next_clear_time = mktime($h, $i, 0, $date_m, $card['clear_date'], $date_y);
                //下月
                if($next_clear_time <= $time){
                    $next_clear_time = mktime($h, $i, 0, $date_m + 1, $card['clear_date'], $date_y);
                }

                break;
            case 3://每周固定时间清零
                if(!isset($card['clear_week'])){
                    return -1;
                }
                $next_clear_time = mktime($h, $i, 0, $date_m, $date_d - $date_w + $card['clear_week'], $date_y);
                //下周
                if($next_clear_time <= $time){
                    $next_clear_time = mktime($h, $i, 0, $date_m, $date_d - $date_w + $card['clear_week'] + 7, $date_y);
                }

                break;
        }

        return $next_clear_time;
 
    }
}