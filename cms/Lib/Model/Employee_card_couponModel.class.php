<?php
class Employee_card_couponModel extends Model
{
    /**
     * 发券
     */
    public function sendCoupon($pigcms_id)
    {
        if(empty($pigcms_id)){
            return false;
        }
        $condition = []; 
        $condition['pigcms_id'] = $pigcms_id;


        $coupon = $this->where($condition)->find();
        if(!$coupon){
            return false;
        } 

        $where = "mer_id = {$coupon['mer_id']} AND card_id = {$coupon['card_id']} AND status = 1";

        //身份标签判断--------
        
        if($coupon['label_ids'] == ''){
            return false;
        } 
        $labelIds = explode(',', $coupon['label_ids']);
        $where .= " AND (";
        foreach($labelIds as $val){
            $where .= " FIND_IN_SET({$val}, lable_ids) OR";
        }
        $where = rtrim($where, 'OR');
        $where .= ')'; 

        //身份标签判断--------

        //发券日期判断--------
        $datetime = strtotime(date('Ymd'));
        $otherData = $coupon['other_date'] == '' ? [] : json_decode($coupon['other_date'], true);
        

        if(isset($otherData[$datetime])){
            //不发券
            if($otherData[$datetime] == 0){
                return false;
            }
        }else{
            $sendRule = $coupon['send_rule'] == '' ? [] : explode(',', $coupon['send_rule']);
            switch ($coupon['send_by']) {
                case 1:
                    if(!in_array(date('w', $datetime), $sendRule)){
                        return false;
                    }
                    break;
                case 2:
                    if(strtotime($datetime < $sendRule[0]) || $datetime > strtotime($sendRule[1])){
                        return false;
                    }
                    break;
            }
        } 

        //发券日期判断-------- 

        $userList = M('Employee_card_user')->where($where)->select();
    
        $time = time(); 
        $sendCoupon = M('Employee_card_coupon_send');

        $addData = [];
        foreach ($userList as $key => $user) {
            $addData[] = [
                'card_id'               => $coupon['card_id'],
                'user_id'               => $user['user_id'],
                'uid'                   => $user['uid'],
                'mer_id'                => $coupon['mer_id'],
                'coupon_id'             => $coupon['pigcms_id'],
                'send_num'              => $coupon['send_num'],
                'verify_num'            => 0,
                'change_to_score_num'   => 0,
                'add_time'              => $time,
                'status'                => 0
            ];
        } 
 
        $sendCoupon->addAll($addData);
        return true; 
         
    }

    /**
     * 过期处理
     */
    public function couponOutdateHandle($pigcms_id)
    {
        if(empty($pigcms_id)){
            return false;
        } 

        $condition = []; 
        $condition['pigcms_id'] = $pigcms_id; 
        $condition['status'] = 1;

        $coupon = $this->where($condition)->find();
        if(!$coupon){
            return false;
        }
        $todayStartTime = strtotime(date('Y-m-d') . ' 00:00:00');
        $todayEndTime = strtotime(date('Y-m-d') . ' 23:59:59');
 
        $condition = [];
        $condition['coupon_id'] = $pigcms_id; 
        $condition['add_time'] = ['between', [$todayStartTime, $todayEndTime]];
        $condition['send_num'] =['GT', 0];
        $condition['status'] = 0; 
        $sendCouponModel = M('Employee_card_coupon_send');
        $couponSendList = $sendCouponModel->where($condition)->select();
 
        $time = time();
         
        $userModel = M('Employee_card_user');
        foreach ($couponSendList as $key => $send_coupon) {
            
            $user = $userModel->where(['user_id' => $send_coupon['user_id']])->find();
            if($user['status'] == 0){
                // 您的今日饭卡次数已失效，因账号被禁用，不能获得积分。
                continue;
            }
            
            if($user['agree_user_agreement'] != 1){
                //未同意用户协议，不做处理
                $this->addCardLog($coupon, $send_coupon, $coupon['add_score_num'],'您的今日饭卡次数已失效，因您未同意用户协议，不能获得积分。', 'error', '未同意用户协议', $user);
                continue;
            } 
            
            $saveUserData = [
                'user_id'    => $user['user_id'],
                'card_score' => $user['card_score'],
                'card_money' => $user['card_money'],
                'last_time'  => $time
            ];

            $saveSendCouponData = [
                'pigcms_id' =>  $send_coupon['pigcms_id'],
                'send_num'  =>  $send_coupon['send_num'],
                'change_to_score_num'  => $send_coupon['change_to_score_num'],
                'last_time' => $time
            ];

            while($saveSendCouponData['send_num']){

                if($coupon['deduct_money'] > $saveUserData['card_money']){
                    $this->addCardLog($coupon, $send_coupon, $coupon['add_score_num'],'您的今日饭卡次数已失效，因余额不足，不能获得积分。', 'error', '余额不足', $user);
                    continue 2;
                }  

                $saveUserData['card_score'] += $coupon['add_score_num'];
                $saveUserData['card_money'] -= $coupon['deduct_money']; 
                $userModel->save($saveUserData);

                $saveSendCouponData['send_num'] --;
                $saveSendCouponData['change_to_score_num'] ++; 
                $sendCouponModel->save($saveSendCouponData);

                $msg = '您的今日的饭卡次数已失效，扣除余额'.$coupon['deduct_money'].'元，为你兑换'.$coupon['add_score_num'].'积分';
                $this->addCardLog($coupon, $send_coupon, $coupon['add_score_num'], $msg, 'success', '消费券过期', $user);
            }
 
            
        }
    }

    /**
     * 消费记录
     */
    private function addCardLog($coupon, $send_coupon, $add_score_num, $msg = '', $change_type='success', $desc = '消费券过期', $user)
    {
        $arr = [];
        $arr['card_id'] = $send_coupon['card_id'];
        $arr['user_id'] = $send_coupon['user_id'];
        $arr['coupon_id'] = $send_coupon['pigcms_id'];
        $arr['mer_id'] = $send_coupon['mer_id'];
        $arr['uid'] = $send_coupon['uid'];
        $arr['num'] = $add_score_num;
        $arr['coupon_price'] = $coupon['coupon_price'];
        $arr['deduct_money'] = $coupon['deduct_money'];
        $arr['add_score_num'] = $coupon['add_score_num'];
        $arr['type'] = 'overdue';
        $arr['change_type'] = $change_type;
        $arr['description'] = $desc;
        $arr['user_desc'] = $msg;
        $arr['operate_type'] = 'merchant';
        $arr['add_time'] = time();  
        $arr['user_name'] = $user['name'] ?? '';  
        $arr['card_number'] = $user['card_number'] ?? '';  
        $arr['department'] = $user['department'] ?? '';  
        $arr['identity'] = $user['identity'] ?? '';  
        $arr['log_type'] = 6;  
        return M('Employee_card_log')->data($arr)->add();
    }
}
?>