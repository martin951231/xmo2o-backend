<?php


namespace app\employee\model\service;

use app\employee\model\db\EmployeeCard;
use app\employee\model\db\EmployeeCardCoupon;
use app\merchant\model\service\MerchantStoreService;
use app\employee\model\db\EmployeeCardUser; 
use app\employee\model\db\EmployeeCardCouponSend;
use app\employee\model\db\EmployeeCardLable;
use app\employee\model\db\EmployeeCardLog;
use app\employee\model\db\EmployeeCardPayStore;
use app\employee\model\db\EmployeeCardStore;
use app\employee\model\db\EmployeeCardUserPayCode;
use app\employee\model\db\EmployeeCardUserPayLog;
use app\employee\model\db\User;
use think\facade\Db;
use app\merchant\model\service\storestaff\MerchantStoreStaffService;

require_once '../extend/phpqrcode/phpqrcode.php';

class EmployeePayCodeService
{
    public $employeeCardCouponSendModel = null;
    public $employeeCardCouponModel = null;
    public $employeeCardUserPayCodeModel = null;
    public $employeeCardUserModel = null;
    public $employeeCardUserPayLogModel = null;
    public $employeeCardLogModel = null; 
    public $employeeCardStoreModel = null; 
    public $employeeCardModel = null; 
    public $userModel = null; 

    public function __construct()
    {
        $this->employeeCardCouponSendModel = new EmployeeCardCouponSend();
        $this->employeeCardCouponModel = new EmployeeCardCoupon();
        $this->employeeCardUserPayCodeModel = new EmployeeCardUserPayCode();
        $this->employeeCardUserModel = new EmployeeCardUser();
        $this->employeeCardUserPayLogModel = new EmployeeCardUserPayLog();
        $this->employeeCardLogModel = new EmployeeCardLog(); 
        $this->employeeCardStoreModel = new EmployeeCardStore(); 
        $this->employeeCardModel = new EmployeeCard(); 
        $this->userModel = new User(); 
    }
    /**
     * 付款码详情
     */
    public function payCodeDetai($params)
    {
        if(empty($params['uid'])){
            throw new \think\Exception('请先登录');
        } 
        if(empty($params['card_id'])){
            throw new \think\Exception('card_id不能为空');
        } 

        $User = $this->employeeCardUserModel->getUser($params);
        if(!$User){
            throw new \think\Exception('用户不存在或被禁用');
        } 

        $time = time();

        $condition = [];
        $condition[] = ['is_use', '=', 0];
        $condition[] = ['card_id', '=', $User->card_id];
        $condition[] = ['user_id', '=', $User->user_id];
        $condition[] = ['uid', '=', $User->uid];
        $condition[] = ['mer_id', '=', $User->mer_id];
        $condition[] = ['type', '=', $params['type']];
        $condition[] = ['valid_time', '>', $time + 60];
        $PayCode = $this->employeeCardUserPayCodeModel->with('pay_log')->where($condition)->order('add_time DESC')->find();
        if(!$PayCode || !empty($PayCode->pay_log)){
            $PayCode = $this->employeeCardUserPayCodeModel;
            $PayCode->card_id = $User->card_id;
            $PayCode->user_id = $User->user_id;
            $PayCode->uid = $User->uid;
            $PayCode->mer_id = $User->mer_id;
            $PayCode->type = $params['type'];
            $PayCode->code = $this->getCode($params['type']);
            $PayCode->valid_time = $time + 60 * 5; 
            $PayCode->add_time = $time; 
            $PayCode->save(); 
        } 
        $money = '';

        $alert_status = 0;
        $alert_msg = '';
        //消费券支付
        if($params['type'] == 1){  
            $data = $this->employeeCardCouponSendModel->getCoupon($params); 
            $num = $this->employeeCardCouponSendModel->getCouponMunNew($params); 
            if(!$data){
                // throw new \think\Exception('无可用消费券，知否用余额支付');
                
                // $str = '无可用消费券';
                // if($num > 0){
                //     $str = '消费券不在使用时间';
                // }
                // //默认卡
                // $nowTime = date('H:i:s');
                // $condition = [];
                // $condition[] = ['mer_id', '=', $PayCode->mer_id];
                // $condition[] = ['card_id', '=', $PayCode->card_id];
                // $condition[] = ['status', '=', 1];
                // $condition[] = ['is_default', '=', 1];
                // $condition[] = ['start_time', '<', $nowTime];
                // $condition[] = ['end_time', '>', $nowTime];
                // $defaultCoupon = $this->employeeCardCouponModel->where($condition)->find();
                // if(!$defaultCoupon){
                //     // throw new \think\Exception($str);
                // }

               //弹出提示
                // if($PayCode->use_score_or_money == 0){
                //     if($User->card_score >= $defaultCoupon->coupon_price){
                //         $PayCode->pay_type = 1;
                //         $PayCode->num = $defaultCoupon->coupon_price;
                //         $PayCode->coupon_id = $defaultCoupon->pigcms_id;
                //         $PayCode->save(); 

                //         $alert_status = 1;
                //         $alert_msg = "{$str}，是否使用{$defaultCoupon->coupon_price}积分支付？(确定请刷卡支付)";

                //     }else if($User->card_money >= $defaultCoupon->coupon_price){
                //         $PayCode->pay_type = 2;
                //         $PayCode->num = $defaultCoupon->coupon_price;
                //         $PayCode->coupon_id = $defaultCoupon->pigcms_id;
                //         $PayCode->save(); 

                //         $alert_status = 1;
                //         $alert_msg = "{$str}，是否用余额支付{$defaultCoupon->coupon_price}元？(确定请刷卡支付)"; 
                //     }else{
                //         $alert_status = 2;
                //         $alert_msg = '余额不足请充值';  
                //     }
                // } 
            }
            $money = $data->money ?? ''; 
        }
        if($User->card_money < 20){
            $alert_msg = '余额已不足20元，点击充值';
        }
             
        $return = [];
        $return['pay_code_id'] = $PayCode->pigcms_id;
        $return['card_id'] = $PayCode->card_id;
        $return['type'] = $PayCode->type;
        $return['user_id'] = $PayCode->user_id;
        $return['bar_code_image'] =  $this->getBarCode($PayCode->code);
        $return['qr_code_image'] =  $this->getQrCode($PayCode->code);
        $return['money'] =  $money;
        $return['valid_time'] =  date('Y-m-d H:i:s', $PayCode->valid_time);
        $return['code'] =  substr($PayCode->code, 0, 4) . ' ' . substr($PayCode->code, 4, 4) . ' ' . substr($PayCode->code, 8, 4) . ' ' .substr($PayCode->code, 12, 6);
        $return['agree_user_agreement'] =  $User->agree_user_agreement;
        $return['user_money'] =  $User->card_money;
        $return['alert_status'] = '';// $alert_status;
        $return['alert_msg'] = $alert_msg;
        $return['now_coupon_name'] = $data->name ?? '无消费券，可用积分或余额消费';
        return $return;
            
        
    }


    /**
     * 同意使用积分或余额支付
     */
    public function useScoreOrMoneyPay($params)
    {
        if(empty($params['pay_code_id'])){
            throw new \think\Exception('pay_code_id不能为空');
        }  
        $condition = [];
        $condition[] = ['pigcms_id', '=', $params['pay_code_id']];
        $PayCode = $this->employeeCardUserPayCodeModel->where($condition)->find();
        if(!$PayCode){
            throw new \think\Exception('付款码不存在');
        } 
        if($PayCode->uid != $params['uid']){
            throw new \think\Exception('无访问权限');
        } 

        $PayCode->use_score_or_money = 1;
        return $PayCode->save();
    }

    /**
     * 查询支付状态
     */
    public function payCodeStatus($params)
    {
        if(empty($params['pay_code_id'])){
            throw new \think\Exception('pay_code_id不能为空');
        }  
        $condition = [];
        $condition[] = ['pigcms_id', '=', $params['pay_code_id']];
        $PayCode = $this->employeeCardUserPayCodeModel->where($condition)->find();
        if(!$PayCode){
            throw new \think\Exception('付款码不存在');
        } 
        if($PayCode->uid != $params['uid']){
            throw new \think\Exception('无访问权限');
        } 
 
        $User = $this->employeeCardUserModel->where('user_id', $PayCode->user_id)->where('status', 1)->find();
        if(!$User){
            throw new \think\Exception('用户不存在或被禁用');
        } 
      
        $condition = [];
        $condition[] = ['code', '=', $PayCode->code];
        $condition[] = ['user_id', '=', $User->user_id];
        $condition[] = ['uid', '=', $params['uid']];
        $PayLog = $this->employeeCardUserPayLogModel->where($condition)->find();
        if(!$PayLog){
            return $this->payStatus(0, '正在支付中');
        }

        if($PayLog->status == 1){
            return $this->payStatus(1, '支付成功');
        }else {
            if($PayLog->msg == '余额不足'){
                return $this->payStatus(2, $PayLog->msg);
            }else if($PayLog->msg == '积分不足'){
                return $this->payStatus(2, $PayLog->msg);
            }else{
                return $this->payStatus(3, $PayLog->msg);
                // throw new \think\Exception($PayLog->msg);
            }
        }

    }


    /**
     * 扫码核销
     */
    public function deductions($params)
    {
        if(empty($params['code'])){
            throw new \think\Exception('code不能为空', 1002);
        }  

        if(empty($params['operate_type'])){
            throw new \think\Exception('operate_type不能为空');
        } 

        if(empty($params['mer_id']) || empty($params['staff']) || empty($params['staff_id'])){
            throw new \think\Exception('请先登录', 2002);
        }  

        //闸机
        if($params['operate_type'] == 'brake_machine'){

            if(empty($params['operate_client'])){
                throw new \think\Exception('设操作终端不能为空', 1002);
            }
            if($params['operate_client'] != 1){
                throw new \think\Exception('未知的操作终端', 1001);
            }
            
            if(empty($params['device_id'])){
                throw new \think\Exception('设备号不能为空', 1002);
            }

            if(empty($params['device_name'])){
                throw new \think\Exception('设备名不能为空', 1002);
            }

        }

       
        //店铺
        $store = (new MerchantStoreService())->getStoreInfo($params['staff']['store_id']);

        $params['store'] = $store;

        $return = [];
        $return['voice_url'] = $this->getVerifyVoiceUrl('核销成功');
        $return['title'] = '核销成功';
        $return['status'] = 1;

        try { 
            //实体卡
            if(strlen($params['code']) != 18){ 
                $this->cardNumberConsume($params);
    
            }else{//二维码 
                $this->qrcodeConsume($params); 
            }
        } catch (\Exception $e) {

            $return['voice_url'] = $this->getVerifyVoiceUrl($e->getMessage());
            $return['title'] = $e->getMessage();
            $return['status'] = 0;
        }
        return $return;
    }

    /**
     * 二维码消费
     */
    private function qrcodeConsume($params)
    {
        $params['consume_type'] = 1;

        $condition = [];
        $condition[] = ['code', '=', $params['code']];
        $PayCode = $this->employeeCardUserPayCodeModel->where($condition)->order('add_time desc')->find();
        if(!$PayCode){
            // $this->employeeCardUserPayLogModel->addLog($PayCode, 0, '付款码不存在，请刷新重试'); 
            throw new \think\Exception('付款码不存在', 3001);
        }
        if($PayCode->is_use == 1){
			$this->employeeCardUserPayLogModel->addLog($PayCode, 0, '付款码已使用'); 
            throw new \think\Exception('付款码已使用', 3001);
        }

        $payLog = $this->employeeCardUserPayLogModel->where($condition)->find();
        if($payLog && $payLog->status == 1){
            $this->employeeCardUserPayLogModel->addLog($PayCode, 0, '请勿重复核销'); 
            throw new \think\Exception('请勿重复核销');
        }
        $time = time();
        if($PayCode->valid_time <= $time){  
            $this->employeeCardUserPayLogModel->addLog($PayCode, 0, '付款码已过期'); 
            throw new \think\Exception('付款码已过期', 3001);
        }

        $params['uid'] = $PayCode->uid;
        $params['user_id'] = $PayCode->user_id;
        $params['card_id'] = $PayCode->card_id; 
        $UserCard = $this->employeeCardUserModel->where('user_id', $PayCode->user_id)->where('status', 1)->find();
        if(!$UserCard){
            $this->employeeCardUserPayLogModel->addLog($PayCode, 0, '账号不存在或被禁用'); 
            throw new \think\Exception('用户不存在或被禁用', 3001);
        } 

        if($UserCard->mer_id != $params['mer_id']){
            $this->employeeCardUserPayLogModel->addLog($PayCode, 0, '非此商家员工'); 
            throw new \think\Exception('非本商家员工', 3001);
        }

        $params['user_card'] = $UserCard;
    
        //用户同意用积分或余额支付 && $PayCode->use_score_or_money == 1 && $PayCode->pay_type != 0 && $PayCode->num != 0
        if($params['card_type'] == 'coupon'){

             //消费券
             $params['uid'] = $UserCard->uid;
             $params['user_id'] = $UserCard->user_id;
             $params['card_id'] = $UserCard->card_id;  
             $data = $this->employeeCardCouponSendModel->getCoupon($params); 
             $num = $this->employeeCardCouponSendModel->getCouponMunNew($params); 
             if(!$data){
                 $str = '消费券不在使用时间';
                 if($num > 0){
                     $str = '消费券不在使用时间';
                 }
                 //默认卡
                 $nowTime = date('H:i:s');
                 $condition = [];
                 $condition[] = ['mer_id', '=', $UserCard->mer_id];
                 $condition[] = ['card_id', '=', $UserCard->card_id];
                 $condition[] = ['status', '=', 1];
                 $condition[] = ['is_default', '=', 1];
                 $condition[] = ['start_time', '<', $nowTime];
                 $condition[] = ['end_time', '>', $nowTime];
                 $defaultCoupon = $this->employeeCardCouponModel->where($condition)->find();
                 if(!$defaultCoupon){
                     throw new \think\Exception($str);
                 }

                 $params['pay_type'] = 'unattended'; //无人值守

                 if($UserCard->card_score >= $defaultCoupon->coupon_price){
                     //积分支付
                     $params['card_type'] = 'score';
                     $params['score'] = $defaultCoupon->coupon_price; 
                     $params['remark'] = '无可用消费券使用积分支付';
                 }else if($UserCard->card_money >= $defaultCoupon->coupon_price){
                     //余额支付
                     $params['card_type'] = 'money';
                     $params['money'] = $defaultCoupon->coupon_price;
                     $params['remark'] = '无可用消费券使用余额支付';
                 }else{
                    $this->employeeCardUserPayLogModel->addLog($PayCode, 0, '余额不足'); 
                     throw new \think\Exception('余额不足');
                 }
             }

        }

        if($params['card_type'] == 'auto'){
            if(empty($params['money'])){
                throw new \think\Exception('请设置消费金额');
            }
            $payList = []; 
            //积分支付
            $payList[] = function($params, $UserCard){
                if($UserCard->card_score >= $params['money']){
                    $params['card_type'] = 'score';
                    $params['score'] = $params['money']; 
                    $params['remark'] = '积分支付';
                    return $params;
                }
                return false;
            };

            //余额支付
            $payList[] = function($params, $UserCard){
                if($UserCard->card_money >= $params['money']){ 
                    $params['card_type'] = 'money';
                    $params['money'] = $params['money'];
                    $params['remark'] = '余额支付';
                    return $params;
                }
                return false;
            };

            //支付顺序 0=先积分后余额，1=先余额后积分
            if($params['pay_sort'] == 1){
                $payList = array_reverse($payList);
            }

            foreach ($payList as $key => $payFun) {
                $return = $payFun($params, $UserCard);
                if($return){
                    break;
                }
            }
            if(!$return){
                $this->employeeCardUserPayLogModel->addLog($PayCode, 0, '余额不足'); 
                throw new \think\Exception('余额不足');
            }

            $params = $return;
            $params['pay_type'] = 'auto'; //自由支付
        }
        
        $params['pay_type'] = $params['pay_type'] ?? $params['card_type'];

        if($params['card_type'] == 'coupon'){
            $res = $this->couponVerify($params);
        }else if($params['card_type'] == 'score'){
            $res = $this->scoreConsume($params);
        }else if($params['card_type'] == 'money'){
            $res = $this->moneyConsume($params);
        }else{
            throw new \think\Exception('card_type不正确');
        }
        if(!$res['status']){
            $this->employeeCardUserPayLogModel->addLog($PayCode, 0, $res['msg']); 
            throw new \think\Exception($res['msg'], $res['code']);
        }

        //更新付款码
        $PayCode->is_use = 1;
        $PayCode->save();

        //记录日志
        $this->employeeCardUserPayLogModel->addLog($PayCode, 1); 
        return true; 
    }

    /**
     * 实体卡消费
     */
    private function cardNumberConsume($params)
    {
        //员工卡
        $condition = [];
        $condition[] = ['card_number', '=', $params['code']];
        $condition[] = ['status', '=', 1];
        if (isset($params['mer_id']) && $params['mer_id'] > 0) {
            $condition[] = ['mer_id', '=', $params['mer_id']];
        }

        $UserCard = $this->employeeCardUserModel->where($condition)->find();
        if(!$UserCard){
            throw new \think\Exception('员工卡不存在或已被禁用');
        }
        if($UserCard->mer_id != $params['mer_id']){
            throw new \think\Exception('非本商家员工', 3001);
        }
         
        $params['user_card'] = $UserCard;

        $params['consume_type'] = 2;

        if($params['card_type'] == 'coupon'){
            
            //消费券
            $params['uid'] = $UserCard->uid;
            $params['user_id'] = $UserCard->user_id;
            $params['card_id'] = $UserCard->card_id;  
            $data = $this->employeeCardCouponSendModel->getCoupon($params); 
            $num = $this->employeeCardCouponSendModel->getCouponMunNew($params); 
            if(!$data){
                $str = '请配置余额支付消费券';
                if($num > 0){
                    $str = '消费券不在使用时间';
                }
                //默认卡
                $nowTime = date('H:i:s');
                $condition = [];
                $condition[] = ['mer_id', '=', $UserCard->mer_id];
                $condition[] = ['card_id', '=', $UserCard->card_id];
                $condition[] = ['status', '=', 1];
                $condition[] = ['is_default', '=', 1];
                $condition[] = ['start_time', '<', $nowTime];
                $condition[] = ['end_time', '>', $nowTime];
                $defaultCoupon = $this->employeeCardCouponModel->where($condition)->find();
                if(!$defaultCoupon){
                    throw new \think\Exception($str);
                }
                $params['pay_type'] = 'unattended'; //无人值守
                if($UserCard->card_score >= $defaultCoupon->coupon_price){
                    //积分支付
                    $params['card_type'] = 'score';
                    $params['score'] = $defaultCoupon->coupon_price; 
                    $params['remark'] = '无可用消费券使用积分支付';
                }else if($UserCard->card_money >= $defaultCoupon->coupon_price){
                    //余额支付
                    $params['card_type'] = 'money';
                    $params['money'] = $defaultCoupon->coupon_price;
                    $params['remark'] = '无可用消费券使用余额支付';
                }else{
                    throw new \think\Exception('余额不足');
                }
            }
  
        }

        if($params['card_type'] == 'auto'){
            if(empty($params['money'])){
                throw new \think\Exception('请设置消费金额');
            }
            $payList = []; 
            //积分支付
            $payList[] = function($params, $UserCard){
                if($UserCard->card_score >= $params['money']){
                    $params['card_type'] = 'score';
                    $params['score'] = $params['money']; 
                    $params['remark'] = '积分支付';
                    return $params;
                }
                return false;
            };

            //余额支付
            $payList[] = function($params, $UserCard){
                if($UserCard->card_money >= $params['money']){ 
                    $params['card_type'] = 'money';
                    $params['money'] = $params['money'];
                    $params['remark'] = '余额支付';
                    return $params;
                }
                return false;
            };

            //支付顺序 0=先积分后余额，1=先余额后积分
            if($params['pay_sort'] == 1){
                $payList = array_reverse($payList);
            }

            foreach ($payList as $key => $payFun) {
                $return = $payFun($params, $UserCard);
                if($return){
                    break;
                }
            }
            if(!$return){
                throw new \think\Exception('余额不足');
            }
            $params = $return;
            $params['pay_type'] = 'auto'; //自由支付
        }

        $params['pay_type'] = $params['pay_type'] ?? $params['card_type'];

        if($params['card_type'] == 'coupon'){
            $res = $this->couponVerify($params);
        }else if($params['card_type'] == 'score'){
            $res = $this->scoreConsume($params);
        }else if($params['card_type'] == 'money'){
            $res = $this->moneyConsume($params);
        }else{
            throw new \think\Exception('card_type不正确');
        }

        if(!$res['status']){
            throw new \think\Exception($res['msg'], $res['code']);
        }
        return true; 
    }

    /**
     * 饭卡核销
     * @param params[]:user_card 员工卡对象
     * @param params[]:store 店铺数组
     * @param params[]:consume_type 消费类型：1=二维码，2=实体卡
     * @param params[]:operate_type 请求类型
     * @param params[]:staff 店员数组
     * @param params[]:staff_id 店员ID
     */
    private function couponVerify($params)
    {
        $return = [];
        $return['status'] = true;
        $return['msg'] = '';
        $return['code'] = 1000;

        $UserCard = $params['user_card'];
        $store = $params['store'];
        //消费券
        $params['uid'] = $UserCard->uid;
        $params['user_id'] = $UserCard->user_id;
        $params['card_id'] = $UserCard->card_id;  
        $coupon = $this->employeeCardCouponSendModel->getCoupon($params);
        if(!$coupon){ 
            $return['status'] = false;
            $return['msg'] = '当前用户没有消费券';
            $return['code'] = 3001; 
            return $return;
        }
        if($coupon->money > $UserCard->card_money){ 
            $return['status'] = false;
            $return['msg'] = '余额不足';
            $return['code'] = 3001; 
            return $return;
        } 

        $condition = [];
        $condition[] = ['pigcms_id', '=', $coupon->coupon_send_id];
        $CouponSend = $this->employeeCardCouponSendModel->where($condition)->find();
        if(!$CouponSend){ 
            $return['status'] = false;
            $return['msg'] = '当前用户没有消费券';
            $return['code'] = 3001; 
            return $return;
        }

        //判断用户是否可以在此店铺核销
        $store_id = $params['staff']['store_id'];
        $cardPayStoreCondition = [];
        $cardPayStoreCondition[] = ['mer_id', '=', $CouponSend->mer_id];
        $cardPayStoreCondition[] = ['store_id', '=', $store_id];
        $cardPayStoreCondition[] = ['card_id', '=', $CouponSend->card_id];
        $isCardBindStore = (new EmployeeCardPayStore())->where($cardPayStoreCondition)->count();
        $lableCondition = [];
        $lableCondition[] = ['mer_id', '=', $CouponSend->mer_id];
        $lableCondition[] = ['is_del', '=', 0];
        $bind_store_id = (new EmployeeCardLable())->where($lableCondition)->value('bind_store_id');
        if(!$isCardBindStore && (!$bind_store_id || !is_array($bind_store_id) || !in_array($store_id, $bind_store_id)) ){
            $return['status'] = false;
            $return['msg'] = '核销失败，此卡未绑定当前店铺';
            $return['code'] = 3001; 
            return $return;
        }
        $time = time();
        Db::startTrans();
        try {
            //修改消费券数量
            $CouponSend->send_num --;
            $CouponSend->verify_num ++;
            if($CouponSend->send_num == 0){
                $CouponSend->status = 1;
            }
            $CouponSend->last_time = $time;
            $CouponSend->save();
            
    
            //添加消费记录
            $cardLog = $this->employeeCardLogModel;
            $cardLog->card_id = $UserCard->card_id;
            $cardLog->user_id = $UserCard->user_id;
            $cardLog->uid = $UserCard->uid;
            $cardLog->mer_id = $UserCard->mer_id;
            $cardLog->store_id = $store['store_id'] ?? 0;

            $description = '';
            $user_msg = '';  
            $cardLog->type = 'coupon';
            $description = $coupon['name'] ?: '消费券核销';
            if($params['operate_type'] == 'brake_machine'){
                $user_msg = '您在'.date('Y-m-d H:i:s', $time) . ' ' . $params['device_name'] .'刷了饭卡，扣除余额'.$coupon->money.'元';
            }else{
                $user_msg = '您在'.date('Y-m-d H:i:s', $time) . ' ' . $store['name'] .'刷了饭卡，扣除余额'.$coupon->money.'元';
            }
            $UserCard->card_money -= $coupon->money;
            $cardLog->coupon_id = $CouponSend->pigcms_id;
            $cardLog->num = $coupon->money;
            $cardLog->money = $coupon->money;
            $cardLog->coupon_price = $coupon->coupon_price;
            $cardLog->grant_price = $coupon->coupon_price - $coupon->money;
            
            
            $cardLog->change_type = 'success';
            $cardLog->description = $description;
            $cardLog->user_desc = $user_msg;
            
            $cardLog->operate_type = 'staff';
            $cardLog->operate_id = $params['staff_id'];
            $cardLog->operate_name = $params['staff']['name'];
            $cardLog->add_time = $time;
 
            if($params['operate_type'] == 'brake_machine'){
                $cardLog->operate_type = 'brake_machine';
                $cardLog->brake_machine_id = $params['device_id'];
                $cardLog->brake_machine_name = $params['device_name'];
            } 
            
            $cardLog->remark = $params['remark'] ?? '';
            $cardLog->consume_type = $params['consume_type'] ?? 0;
            //记录员工信息
            $cardLog->user_name = $UserCard->name ?? '';
            $cardLog->card_number = $UserCard->card_number ?? '';
            $cardLog->department = $UserCard->department ?? '';
            $cardLog->identity = $UserCard->identity ?? '';

            $cardLog->pay_type = $params['pay_type'] ?? '';
            $cardLog->log_type = 1;
            $cardLog->save();  


            //更新用户信息
            $UserCard->save(); 
            // 提交事务
            Db::commit();
         } catch (\Exception $e) {
             throw new \think\Exception($e->getMessage(), 3001);
             // 回滚事务
             Db::rollback();
         }
 
        return $return;
    }
 

    /**
     * 积分消费
     * @param params[]:user_card 员工卡对象
     * @param params[]:store 店铺数组
     * 
     * @param params[]:consume_type 消费类型：1=二维码，2=实体卡
     * @param params[]:operate_type 请求类型
     * @param params[]:staff 店员数组
     * @param params[]:staff_id 店员ID
     * 
     * @param params[]:score 积分数
     */
    private function scoreConsume($params)
    {
        $return = [];
        $return['status'] = true;
        $return['msg'] = '';
        $return['code'] = 1000;

        $UserCard = $params['user_card'];
        $store = $params['store'];
        //消费券
        $params['uid'] = $UserCard->uid;
        $params['user_id'] = $UserCard->user_id;
        $params['card_id'] = $UserCard->card_id;

        $condition = [];
        $condition[] = ['store_id', '=', $store['store_id']];
        $condition[] = ['card_id', '=', $UserCard->card_id];
        $condition[] = ['mer_id', '=', $UserCard->mer_id];
        $cardStore = $this->employeeCardStoreModel->where($condition)->find();
        if(!$cardStore){
            $return['status'] = false;
            $return['msg'] = '此店铺不支持积分抵扣';
            $return['code'] = 3001; 
            return $return;
        }
        if(empty($params['score'])){
            $return['status'] = false;
            $return['msg'] = '请输入消费积分数';
            $return['code'] = 3001;
            return $return;
        }

        if($UserCard->card_score < $params['score']){
            $return['status'] = false;
            $return['msg'] = '积分不足';
            $return['code'] = 3001;
            return $return;
        }

        //判断用户是否可以在此店铺核销
        $store_id = $params['staff']['store_id'];
        $lableCondition = [];
        $lableCondition[] = ['mer_id', '=', $UserCard->mer_id];
        $lableCondition[] = ['is_del', '=', 0];
        $bind_store_id = (new EmployeeCardLable())->where($lableCondition)->value('bind_store_id');
        $bind_store_id = json_decode($bind_store_id, true);
        if(!$bind_store_id || !is_array($bind_store_id) || !in_array($store_id, $bind_store_id) ){
            $return['status'] = false;
            $return['msg'] = '核销失败，此卡未绑定当前店铺';
            $return['code'] = 3001; 
            return $return;
        }

        $time = time();
        Db::startTrans();
        try {
 
            //添加消费记录
            $cardLog = $this->employeeCardLogModel;
            $cardLog->card_id = $UserCard->card_id;
            $cardLog->user_id = $UserCard->user_id;
            $cardLog->uid = $UserCard->uid;
            $cardLog->mer_id = $UserCard->mer_id;

            $cardLog->store_id = $store['store_id'] ?? 0;
   
            if($params['operate_type'] == 'brake_machine'){
                $user_msg = '您在'.date('Y-m-d H:i:s', $time) . ' ' . $params['device_name'] .'消费'.$params['score'].'积分';
                
            }else{
                $user_msg = '您在'.date('Y-m-d H:i:s', $time) . ' ' . $store['name'] .'消费'.$params['score'].'积分';
            }
  
            $cardLog->type = 'score';
            $cardLog->description = '积分消费';
            $cardLog->user_desc = $user_msg;
           
            $cardLog->coupon_id = 0;
            $cardLog->num = $params['score'];
        
            $cardLog->change_type = 'success';
            
            $cardLog->operate_type = 'staff';
            $cardLog->operate_id = $params['staff_id'];
            $cardLog->operate_name = $params['staff']['name'];
            $cardLog->add_time = $time;

            if($params['operate_type'] == 'brake_machine'){
                $cardLog->operate_type = 'brake_machine';
                $cardLog->brake_machine_id = $params['device_id'];
                $cardLog->brake_machine_name = $params['device_name'];
            } 
            $cardLog->remark = $params['remark'] ?? '';
            $cardLog->consume_type = $params['consume_type'] ?? '';
            //记录员工信息
            $cardLog->user_name = $UserCard->name ?? '';
            $cardLog->card_number = $UserCard->card_number ?? '';
            $cardLog->department = $UserCard->department ?? '';
            $cardLog->identity = $UserCard->identity ?? '';
            $cardLog->pay_type = $params['pay_type'] ?? '';
            $cardLog->log_type = 3;
            $cardLog->save();  
 
            $UserCard->card_score -= $params['score'];
            //更新用户信息
            $UserCard->save(); 

            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage(), 3001);
            // 回滚事务
            Db::rollback();
        }

        return $return;
    }

    /**
     * 余额消费 
     * @param params[]:user_card 员工卡对象
     * @param params[]:store 店铺数组
     * 
     * @param params[]:consume_type 消费类型：1=二维码，2=实体卡
     * @param params[]:operate_type 请求类型
     * @param params[]:staff 店员数组
     * @param params[]:staff_id 店员ID
     * @param params[]:money 扣除余额数
     * 
     */
    private function moneyConsume($params)
    {
        $return = [];
        $return['status'] = true;
        $return['msg'] = '';
        $return['code'] = 1000;

        if(empty($params['money'])){ 
            $return['status'] = false;
            $return['msg'] = '请输入money';
            $return['code'] = 3001; 
            return $return;
        } 
        $UserCard = $params['user_card'];
        $store = $params['store'];
        //消费券
        $params['uid'] = $UserCard->uid;
        $params['user_id'] = $UserCard->user_id;
        $params['card_id'] = $UserCard->card_id;
 
        if($params['money'] > $UserCard->card_money){ 
            $return['status'] = false;
            $return['msg'] = '余额不足';
            $return['code'] = 3001; 
            return $return;
        } 

        //判断用户是否可以在此店铺核销
        $store_id = $params['staff']['store_id'];
        $cardPayStoreCondition = [];
        $cardPayStoreCondition[] = ['mer_id', '=', $UserCard->mer_id];
        $cardPayStoreCondition[] = ['store_id', '=', $store_id];
        $cardPayStoreCondition[] = ['card_id', '=', $UserCard->card_id];
        $isCardBindStore = (new EmployeeCardPayStore())->where($cardPayStoreCondition)->count();
        $lableCondition = [];
        $lableCondition[] = ['mer_id', '=', $UserCard->mer_id];
        $lableCondition[] = ['is_del', '=', 0];
        $bind_store_id = (new EmployeeCardLable())->where($lableCondition)->value('bind_store_id');
        if(!$isCardBindStore && (!$bind_store_id || !is_array($bind_store_id) || !in_array($store_id, $bind_store_id)) ){
            $return['status'] = false;
            $return['msg'] = '核销失败，此卡未绑定当前店铺';
            $return['code'] = 3001; 
            return $return;
        }

        Db::startTrans();
        try {
            $time = time();
            //添加消费记录
            $cardLog = $this->employeeCardLogModel;
            $cardLog->card_id = $UserCard->card_id;
            $cardLog->user_id = $UserCard->user_id;
            $cardLog->uid = $UserCard->uid;
            $cardLog->mer_id = $UserCard->mer_id;
            $cardLog->store_id = $store['store_id'] ?? 0;
            $cardLog->type = 'money';
            $cardLog->coupon_id = 0;
            $cardLog->num = $params['money'];
            $cardLog->change_type = 'success';
            $cardLog->description = '余额消费';
            if($params['operate_type'] == 'brake_machine'){
                $user_msg = '您在'.date('Y-m-d H:i:s', $time) . ' ' . $params['device_name'] .'消费'.$params['money'].'元';
            }else{
                $user_msg = '您在'.date('Y-m-d H:i:s', $time) . ' ' . $store['name'] .'消费'.$params['money'].'元';
            }
            $cardLog->user_desc = $user_msg;
            $cardLog->operate_type = 'staff';
            $cardLog->operate_id = $params['staff_id'];
            $cardLog->operate_name = $params['staff']['name'];
            $cardLog->add_time = $time;
            if($params['operate_type'] == 'brake_machine'){
                $cardLog->operate_type = 'brake_machine';
                $cardLog->brake_machine_id = $params['device_id'];
                $cardLog->brake_machine_name = $params['device_name'];
            } 
            $cardLog->remark = $params['remark'] ?? '';
            $cardLog->consume_type = $params['consume_type'] ?? '';
            //记录员工信息
            $cardLog->user_name = $UserCard->name ?? '';
            $cardLog->card_number = $UserCard->card_number ?? '';
            $cardLog->department = $UserCard->department ?? '';
            $cardLog->identity = $UserCard->identity ?? '';

            $cardLog->pay_type = $params['pay_type'] ?? '';

            $cardLog->log_type = 2;
            $cardLog->save();  
 
            
            //更新用户信息
            $UserCard->card_money -= $params['money'];
            $UserCard->save(); 
  
            Db::commit();
        } catch (\Exception $e) {
            throw new \think\Exception($e->getMessage(), 3001);
            Db::rollback();
        }
        
        return $return;
    }
 
 

    private function payStatus($status, $msg)
    {
        $data = [];
        $data['status'] = $status;
        $data['msg'] = $msg;
        return $data;
    }


    /**
     * 生成付款码
     */
    private function getCode($type)
    { 
        $time = time();
        $code = $type;
        $code .= mt_rand(10000, 99999);
        $code .= substr($time, 4, 6);
        list($msec, $sec) = explode(' ', microtime());
        $micro = floatval($msec) * 1000000;
        $code .=  str_pad($micro, 6, "0", STR_PAD_LEFT);
        return $code;
    }
 
    /**
     * 获取条形码
     */
    private function getBarCode($code)
    {
        $generator = new \Picqer\Barcode\BarcodeGeneratorPNG();
        return 'data:image/png;base64,' . base64_encode($generator->getBarcode($code, $generator::TYPE_CODE_128, 1, 1));
    }

    /**
     * 获取二维码
     */
    private function getQrCode($code)
    {
        $date = date('Y-m-d');
        $time = date('Hi');
        $qrcode = new \QRcode();
        $errorLevel = "L";
        $size = "9";
        $dir = '../../runtime/qrcode/employee/'.$date. '/' .$time;
        if(!is_dir($dir)){
            mkdir($dir, 0777, true);
        }
        $filename_url = '../../runtime/qrcode/employee/'.$date.'/'.$time . '/' . $code.'.png';
        $qrcode->png($code, $filename_url, $errorLevel, $size);
        $QR = 'runtime/qrcode/employee/'.$date.'/'.$time . '/' . $code.'.png';      //已经生成的原始二维码图片文件
        $http_type = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        return $http_type.$_SERVER["HTTP_HOST"].'/'.$QR;
    }

    /**
     * 核销记录(对外接口)
     */
    public function consumeRecords($params)
    {
        $condition = [];
        $condition[] = ['operate_id', '=', $params['staff_id']];
        if(!in_array($params['type'], ['all', 'coupon', 'score'])){
            throw new \think\Exception("type类型有误");
        }
        if($params['type'] != 'all'){
            $condition[] = ['type', '=', $params['type']];
            $condition[] = ['change_type', '=', 'success'];
        }
        if(!empty($params['start_date'])){
            $condition[] = ['add_time', '>=', strtotime($params['start_date'] . ' 00:00:00')];
        }
        if(!empty($params['end_date'])){
            $condition[] = ['add_time', '<=', strtotime($params['end_date'] . ' 23:59:59')];
        }

        //员工卡名称
        if(!empty($params['kw_card_name'])){
            $conditionCard = [];
            $conditionCard[] = ['name', 'like', '%'. $params['kw_card_name'] . '%'];
            $card_ids = $this->employeeCardModel->where($conditionCard)->column('card_id');
            $condition[] = ['card_id', 'in', $card_ids];
            unset($conditionCard);
        }

        //员工姓名
        if(!empty($params['kw_user_name'])){
            $conditionCardUser = [];
            $conditionCardUser[] = ['name', 'like', '%'. $params['kw_user_name'] . '%'];
            $user_ids = $this->employeeCardUserModel->where($conditionCardUser)->column('user_id');
            $condition[] = ['user_id', 'in', $user_ids];
            unset($conditionCardUser);
        }

        //员工电话
        if(!empty($params['kw_user_phone'])){
            $conditionUser = [];
            $conditionUser[] = ['phone', 'like', '%'. $params['kw_user_phone'] . '%'];
            $uids = $this->userModel->where($conditionUser)->column('uid');
            $condition[] = ['uid', 'in', $uids];
            unset($conditionUser);
        }
        
        //消费券名称
        if(!empty($params['kw_coupon_name'])){
            $conditionCoupon = [];
            $conditionCoupon[] = ['name', 'like', '%'. $params['kw_coupon_name'] . '%'];
            $coupon_ids = $this->employeeCardCouponModel->where($conditionCoupon)->column('pigcms_id');
            $conditionCouponSend = [];
            $conditionCouponSend[] = ['coupon_id', 'in', $coupon_ids];
            $conditionCouponSend[] = ['mer_id', '=', $params['mer_id']];
            $coupon_send_ids = $this->employeeCardCouponSendModel->where($conditionCouponSend)->column('pigcms_id');
            $condition[] = ['coupon_id', 'in', $coupon_send_ids];
            unset($conditionCoupon, $conditionCouponSend, $coupon_send_ids, $coupon_ids);
        }

        $with = [];
        $with['card'] = function($query){
            $query->field(['card_id', 'name'])->bind(['card_name'=>'name']);
        };
        $with['user'] = function($query){
            $query->field(['uid', 'nickname', 'phone'])->bind(['user_phone'=>'phone']);
        };

        // $with['card_user'] = function($query){
        //     $query->field(['user_id', 'card_number', 'name'])->bind(['user_name'=>'name', 'card_number']);
        // };

        $with['coupon_send'] = function($query){
            $query->field(['pigcms_id', 'coupon_id'])
            ->with(['coupon' => function($query){
                $query->field(['pigcms_id', 'name'])->bind(['coupon_name'=>'name']);
            }])->bind(['coupon_name']); 
        };

        $cardLog = $this->employeeCardLogModel
                    ->with($with)
                    ->where($condition)
                    ->order('add_time desc')
                    ->paginate($params['page_size'])
                    ->toArray();
        $data = []; 
        // $data['total'] = $cardLog['total'];
        if($cardLog['data']){
            foreach($cardLog['data'] as $key => $val){
                $temp = [];
                $temp['log_id'] = $val['pigcms_id'] ?? '';
                $temp['card_name'] = $val['card_name'] ?? '';
                $temp['card_number'] = $val['card_number'] ?? '';
                $temp['user_name'] = $val['user_name'] ?? '';
                $temp['user_phone'] = $val['user_phone'] ?? '';
                $temp['coupon_name'] = $val['coupon_name'] ?? '';
                $temp['remark'] = $val['user_desc'];
                $temp['operate_client'] = $val['operate_type'] == 'brake_machine' ? 1 : 0;
                $temp['device_name'] = $val['operate_type'] == 'brake_machine' ? $val['brake_machine_name'] : '';
                $temp['log_time'] = date('Y-m-d H:i:s', $val['add_time']); 
                $temp['money'] = $val['num'];
                $temp['is_refund'] = $val['is_refund'];	
                $temp['refund_remark'] = $val['refund_remark'];
                $data[] = $temp;
            }
        }
         
        return $data;
    }

    /**
     * 仪表盘
     */
    public function dashboard($staffId = 0)
    {
        $where = [
            ['operate_id', '=', $staffId],
            ['type', '=', 'coupon'],
            ['change_type', '=', 'success'],
        ];
        $data = [
            'today_money' => $this->employeeCardLogModel->where($where)->whereTime('add_time', 'today')->sum('num') ?? 0, //当天核销金额
            'week_money'  => $this->employeeCardLogModel->where($where)->whereTime('add_time', 'week')->sum('num') ?? 0, //本周核销金额
            'month_money' => $this->employeeCardLogModel->where($where)->whereTime('add_time', 'month')->sum('num') ?? 0, //本月核销金额
            'total_money' => $this->employeeCardLogModel->where($where)->sum('num') ?? 0, //累计核销金额
        ];
        $where = [
            ['operate_id', '=', $staffId],
            ['type', '=', 'score'],
            ['change_type', '=', 'success'],
            ['is_refund', '=', 0]
        ];
        $data['today_score'] = $this->employeeCardLogModel->where($where)->whereTime('add_time', 'today')->sum('num') ?? 0; //当天消费积分
        $data['month_score'] = $this->employeeCardLogModel->where($where)->whereTime('add_time', 'month')->sum('num') ?? 0; //本月消费积分
        $data['total_score'] = $this->employeeCardLogModel->where($where)->sum('num') ?? 0; //累计消费积分
        return $data;
    }


    /**
     * 保存Mp3文件  
     */
    private function getVerifyVoiceUrl($str)
    {
        $filePath = '../../runtime/employee/voice/';
        if(!is_dir($filePath)){
            mkdir($filePath, 0700, true);
        }
        
        $fileUrl = cfg('site_url') . '/runtime/employee/voice/'.$str . '.mp3';
        if(is_file($filePath . $str . '.mp3')){
            return $fileUrl;
        }

        $filename_url = $filePath . $str . '.mp3';
        $url = (new MerchantStoreStaffService())->getstaffNewOrderVoice($str, 15);
        $data = file_get_contents($url); 
        $re = file_put_contents($filename_url, $data);
        if(!$re){
            return false;
        }
        return $fileUrl;
    }

}