<?php
/**
 * 消费券service
 * @date 2021-12-23
 */

namespace app\employee\model\service;

use app\employee\model\db\EmployeeCardCoupon;
use app\employee\model\db\EmployeeCardCouponSend;
use app\employee\model\db\EmployeeCardLog;
use app\employee\model\db\EmployeeCardUser;

class EmployeeCardCouponService
{

    public $employeeCardCouponSendModel = null;
    public $employeeCardCouponModel = null;
    public $employeeCardUserModel = null; 

    public function __construct()
    {
        $this->employeeCardCouponSendModel = new EmployeeCardCouponSend();
        $this->employeeCardCouponModel = new EmployeeCardCoupon();
        $this->employeeCardUserModel = new EmployeeCardUser(); 
    }

    /**
     * 发券
     * @param array params[]:card_id int
     * @param array params[]:mer_id int
     * @param array params[]:coupon_id int
     * @param array params[]:user_id int
     * @return boolean ture
     */
    public function sendCoupon($params)
    {
        if(empty($params['card_id'])){
            throw new \think\Exception('card_id不能为空！');
        }

        if(empty($params['coupon_id'])){
            throw new \think\Exception('coupon_id不能为空！');
        }

        if(empty($params['mer_id'])){
            throw new \think\Exception('mer_id不能为空！');
        }

        $condition = [];
        $condition[] = ['mer_id', '=', $params['mer_id']];
        $condition[] = ['card_id', '=', $params['card_id']];
        $condition[] = ['pigcms_id', '=', $params['coupon_id']];

        $coupon = $this->employeeCardCouponModel->where($condition)->find();
        if(!$coupon){
            throw new \think\Exception('消费券不存在！');
        } 
        $time = time();
        

        if(!empty($params['user_id'])){
            $condition = [];
            $condition[] = ['mer_id', '=', $params['mer_id']];
            $condition[] = ['card_id', '=', $params['card_id']];
            $condition[] = ['status', '=', 1];
            $condition[] = ['user_id', '=', $params['user_id']];
            $user = $this->employeeCardUserModel->where($condition)->find();
            $sendCoupon = $this->employeeCardCouponSendModel;
            $sendCoupon->card_id = $coupon->card_id;
            $sendCoupon->user_id = $user->user_id;
            $sendCoupon->uid =  $user->uid;
            $sendCoupon->mer_id =  $coupon->mer_id;
            $sendCoupon->coupon_id = $coupon->pigcms_id;
            $sendCoupon->send_num = 1;
            $sendCoupon->verify_num = 0;
            $sendCoupon->change_to_score_num = 0;
            $sendCoupon->add_time = $time;
            $sendCoupon->status = 0;
            $sendCoupon->save();
            return $sendCoupon->pigcms_id;
            
        }else if(empty($params['user_id']) || empty($user)){
            $condition = [];
            $condition[] = ['mer_id', '=', $params['mer_id']];
            $condition[] = ['card_id', '=', $params['card_id']];
            $condition[] = ['status', '=', 1];
            $userList = $this->employeeCardUserModel->where($condition)->select();
    
            $sendCoupon = $this->employeeCardCouponSendModel;
            $addData = [];
    
            foreach ($userList as $key => $user) {
                $addData[] = [
                    'card_id'               => $coupon->card_id,
                    'user_id'               => $user->user_id,
                    'uid'                   => $user->uid,
                    'mer_id'                => $coupon->mer_id,
                    'coupon_id'             => $coupon->pigcms_id,
                    'send_num'              => $coupon->send_num,
                    'verify_num'            => 0,
                    'change_to_score_num'   => 0,
                    'add_time'              => $time,
                    'status'                => 0
                ];
            } 
     
            $sendCoupon->saveAll($addData);
        }
 
       
        return true; 
    }

    /**
     * 未核销的消费券转为积分
     */
    public function couponChangeToScore($params)
    {
        if(empty($params['card_id'])){
            throw new \think\Exception('card_id不能为空！');
        }

        if(empty($params['coupon_id'])){
            throw new \think\Exception('coupon_id不能为空！');
        }

        if(empty($params['mer_id'])){
            throw new \think\Exception('mer_id不能为空！');
        }

        $condition = [];
        $condition[] = ['mer_id', '=', $params['mer_id']];
        $condition[] = ['pigcms_id', '=', $params['coupon_id']];
        $condition[] = ['card_id', '=', $params['card_id']];
        $condition[] = ['status', '=', 1];

        $coupon = $this->employeeCardCouponModel->where($condition)->find();
        if(!$coupon){
            throw new \think\Exception('消费券不存在！');
        }
        $todayStartTime = strtotime(date('Y-m-d') . ' 00:00:00');
        $todayEndTime = strtotime(date('Y-m-d') . ' 23:59:59');
 
        $condition = [];
        $condition[] = ['mer_id', '=', $params['mer_id']];
        $condition[] = ['card_id', '=', $params['card_id']];
        $condition[] = ['add_time', 'between', [$todayStartTime, $todayEndTime]];
        $condition[] = ['send_num', '>', 0];
        $condition[] = ['status', '=', 0];

        $couponSendList = $this->employeeCardCouponSendModel->where($condition)->select();
        
        $time = time();
        // return $couponSendList; 
        foreach ($couponSendList as $key => $send_coupon) {
            
            $user = $this->employeeCardUserModel->where('user_id', $send_coupon->user_id)->find(); 

            while($send_coupon->send_num){
                //余额不足
                if($coupon->deduct_money > $user->card_money){
                    $this->addCardLog($send_coupon, $user, '您的今日饭卡次数已失效，因余额不足，不能获得积分。', 6);
                    continue 2;
                }
                $user->card_score += $coupon->add_score_num;
                $user->card_money -= $coupon->deduct_money;
                $user->last_time = $time;
                $user->save();

                $send_coupon->send_num --;
                $send_coupon->change_to_score_num ++;
                $send_coupon->last_time = $time;
                $send_coupon->save();

                $msg = '您的今日的饭卡次数已失效，扣除余额'.$coupon->deduct_money.'元，为你兑换'.$coupon->add_score_num.'积分';
                $this->addCardLog($send_coupon, $user, $msg, 6);
            }
            
        }
        

    }


    /**
     * 消费记录
     */
    private function addCardLog($send_coupon, $user, $msg = '',$operate = 'merchant', $log_type = 0)
    {
        $type = 'score';
        if($log_type == 6){
            $type = 'overdue';
        }else if($log_type == 7){
            $type = 'to_score';
        }
        $cardLog = new EmployeeCardLog();
        $cardLog->card_id = $send_coupon->card_id;
        $cardLog->user_id = $send_coupon->user_id;
        $cardLog->coupon_id = $send_coupon->pigcms_id;
        $cardLog->mer_id = $send_coupon->mer_id;
        $cardLog->uid = $send_coupon->uid;
        $cardLog->type = $type;
        $cardLog->change_type = 'success';
        $cardLog->description = '消费券转积分';
        $cardLog->user_desc = $msg;
        $cardLog->operate_type = $operate;
        $cardLog->log_type = $log_type;
        $cardLog->add_score_num = $send_coupon->coupon->add_score_num ?? 0; 
        $cardLog->deduct_money = $send_coupon->coupon->deduct_money ?? 0; 
        $cardLog->user_name = $user->name ?: ''; 
        $cardLog->card_number = $user->card_number ?: ''; 
        $cardLog->department = $user->department ?: ''; 
        $cardLog->identity = $user->identity ?: ''; 
        $cardLog->add_time = time();
        return $cardLog->save();
    }


    /**
     * 获取消费券列表
     */
    public function getCouponList($params)
    {
        if(empty($params['uid'])){
            throw new \think\Exception('请登录！');
        }
        if(empty($params['card_id'])){
            throw new \think\Exception('card_id不能为空');
        }
        $condition = [];
        $condition[] = ['card_id', '=', $params['card_id']];
        $condition[] = ['uid', '=', $params['uid']];
        $condition[] = ['status', '=', 0];
        $condition[] = ['send_num', '>', 0];
        // if($params['is_pay'] == 2){ //筛选当天
            $condition[] = ['add_time', 'between', [strtotime(date('Ymd')), strtotime(date('Y-m-d').' 23:59:59')]];
        // }
        $with = [];
        $with['coupon'] = function($query) use($params){
            $time = date('H:i:s');
            $condition = [];
            // if($params['is_pay'] == 2){
                // $condition[] = ['end_time', '>', $time]; //筛选过期
            // }
            $condition[] = ['status', '=', 1];
            $query->field(['pigcms_id', 'name', 'start_time', 'end_time', 'send_num', 'money', 'add_score_num', 'deduct_money', 'coupon_price'])->where($condition);
        };
        $with[] = 'user';
        $data = $this->employeeCardCouponSendModel
            ->with($with)
            ->where($condition)
            ->order('add_time DESC')
            ->select()
            ->toArray();

        foreach($data as $key => $val){
            if(empty($val['coupon']) || empty($val['user'])){
                unset($data[$key]);
                continue;
            }
            if($params['is_pay'] == 1 && $val['coupon']['add_score_num'] == 0){
                unset($data[$key]);
            }
        }
        $data = array_values($data);
        return $data;
    }

    /**
     * 消费券转积分(手动转积分)
     */
    public function couponTurnScore($params)
    {
        if(empty($params['uid'])){
            throw new \think\Exception('请登录！');
        }
        if(empty($params['pigcms_id'])){
            throw new \think\Exception('pigcms_id不能为空');
        }

        $condition = [];
        $condition[] = ['pigcms_id', '=', $params['pigcms_id']];
        $condition[] = ['uid', '=', $params['uid']];
        $data = $this->employeeCardCouponSendModel->with(['coupon'])->where($condition)->find();
        if(empty($data) || empty($data->coupon)){
            throw new \think\Exception('消费券不存在，或已被删除！');
        }
        if($data->coupon->status == 0){
            throw new \think\Exception('消费券已被禁用，不能转积分！');
        }
        if($data->send_num <= 0){
            throw new \think\Exception('可转积分的消费券数量不足！');
        }

        $condition = [];
        $condition[] = ['mer_id', '=', $data->mer_id];
        $condition[] = ['card_id', '=', $data->card_id];
        $condition[] = ['status', '=', 1];
        $condition[] = ['user_id', '=', $data->user_id];
        $user = $this->employeeCardUserModel->where($condition)->find();
        if(!$user){
            throw new \think\Exception('用户不存在！');
        }
        if($user->agree_user_agreement != 1){
            //未同意用户协议，不做处理
            // throw new \think\Exception('请先阅读并同意用户协议！');
        }
        $time = time();
        while($data->send_num){
            //余额不足
            if($data->coupon->deduct_money > $user->card_money){
                throw new \think\Exception('余额不足！');
            }
            $user->card_score += $data->coupon->add_score_num;
            $user->card_money -= $data->coupon->deduct_money;
            $user->last_time = $time;
            $user->save();

            $data->send_num --;
            $data->change_to_score_num ++;
            $data->last_time = $time;
            $data->save();

            $msg = '手动转积分，扣除余额'.$data->coupon->deduct_money.'元，为你兑换'.$data->coupon->add_score_num.'积分';
            $this->addCardLog($data, $user, $msg, 'self', 7);
        }

        return true;

    }

   
   

}