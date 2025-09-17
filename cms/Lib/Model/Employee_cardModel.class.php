<?php
class Employee_cardModel extends Model
{
    /**
     * 清积分
     */
    public function clearScore($card_id)
    {
        $card = $this->where(['card_id'=>$card_id])->find();
        if(!$card){
            return false;
        }
        
        $cardUserModel = M('Employee_card_user');
        $clearScoreModel = M('Employee_card_clear_score');
        $cardLogModel = M('Employee_card_log');
        //所有员工
        $condition = [];
        $condition['card_id'] = $card_id;
        $userList = $cardUserModel->where($condition)->select();

        $saveLogData = [];
        $cardLogData = [];
        $time = time();
        foreach ($userList as $key => $user) {
            if($user['card_score'] <= 0){
                continue;
            }
            $tmp = [];
            $tmp['user_id'] = $user['user_id'];
            $tmp['card_id'] = $user['card_id'];
            $tmp['mer_id'] = $user['mer_id'];
            $tmp['uid'] = $user['uid'];
            $tmp['user_name'] = $user['name'];
            $tmp['clear_score'] = $user['card_score'];
            $tmp['now_score'] = 0;
            $tmp['add_time'] = $time;
            $saveLogData[] = $tmp;

            $log_tmp = [];
            $log_tmp['card_id'] = $user['card_id'];
            $log_tmp['user_id'] = $user['user_id'];
            $log_tmp['mer_id'] = $user['mer_id'];
            $log_tmp['uid'] = $user['uid'];
            $log_tmp['num'] = $user['card_score'];
            $log_tmp['type'] = 'score';
            $log_tmp['change_type'] = 'decrease';
            $log_tmp['description'] = "积分到期已被清零";
            $log_tmp['user_desc'] = "您的{$user['card_score']}积分到期已被清零";
            $log_tmp['operate_type'] = "merchant";
            $log_tmp['add_time'] = $time;
            $cardLogData[] = $log_tmp;
        }
        $next_clear_time = $this->calcNextClearScoreTime($card);

        $cardUserModel->startTrans();

        try {
            //积分清空
            $cardUserModel->where($condition)->save(['card_score'=>0]);
            //记录日志
            $cardLogModel->addAll($cardLogData);
            //添加清空日志
            $clearScoreModel->addAll($saveLogData);
            //修改下次清零时间
            $this->where(['card_id'=>$card['card_id']])->save(['next_clear_time'=>$next_clear_time]);

            $cardUserModel->commit();
        } catch (\Exception $e) {
            $cardUserModel->rollback();
            fdump_sql(['errMsg'=>$e->getMessage(),'cardLogData'=>$cardLogData, 'clearScoreData'=>$saveLogData], 'employee_clear_score_error');
        }
       
        return true;
        
    }


    /**
     * 计算下次清积分日期
     * @param array card 员工卡数组
     */
    public function calcNextClearScoreTime($card)
    {
        if($card['next_clear_time'] == -1){
            return -1;
        }
        $time = time();
        $date_d = date('d');
        $date_m = date('m');
        $date_y = date('Y');
        $date_w = date('w');
        list($h, $i) = explode(':', $card['clear_time']);

        $return = [];

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