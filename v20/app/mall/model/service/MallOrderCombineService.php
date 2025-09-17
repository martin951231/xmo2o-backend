<?php

namespace app\mall\model\service;

use app\common\model\db\MerchantStore;
use app\common\model\db\StoreOrder;
use app\common\model\service\coupon\MerchantCouponService;
use app\common\model\service\coupon\SystemCouponService;
use app\common\model\service\live\LiveGoodService;
use app\common\model\service\merchant_card\MerchantCardService;
use app\common\model\service\MerchantPercentRateService;
use app\common\model\service\send_message\SmsService;
use app\common\model\service\UserBehaviorGoodsService;
use app\common\model\service\UserService;
use app\common\model\service\UserSpreadListService;
use app\common\model\service\weixin\TemplateNewsService;
use app\employee\model\db\EmployeeCardLog;
use app\employee\model\db\EmployeeCardUser;
use app\internal_card\model\db\InternalMembershipCard;
use app\internal_card\model\db\InternalMembershipCardLog;
use app\internal_card\model\service\InternalCardMoneyBillService;
use app\internal_card\model\service\InternalCardService;
use app\mall\model\db\MallOrder;
use app\mall\model\db\MallOrderCombine;
use app\mall\model\db\MallOrderDetail;
use app\mall\model\db\MallPrepareOrder;
use app\mall\model\db\Merchant;
use app\mall\model\service\activity\MallActivityService;
use app\mall\model\service\order_print\PrintHaddleService;
use app\mall\model\service\third_order_deliver\DeliverApiService;
use app\merchant\model\service\card\CardNewService as CardNewService;
use app\merchant\model\service\MerchantService;
use app\pay\model\service\PayService;
use Carbon\Carbon;
use think\facade\Cache;


class MallOrderCombineService
{
    const TIME_REMAINING = 15;//订单支付时间（单位：分）
	public function addData($uid, $success_orderids, $mer_id = 0, $store_id = 0, $city_id = 0, $is_prepare_end = 0,$business_order_sn=0){
		if(empty($uid) || empty($success_orderids)) return false;
		$data = [
			'uid' => $uid,
            'order_ids' => implode(',', $success_orderids),
            'addtime' => time(),
            'order_no' => (new PayService)->createOrderNo(),
            'paid' => 0,
            'time_remaining' => cfg('mall_order_obligations_time'),
            'mer_id' => $mer_id,
            'city_id' => $city_id,
            'store_id' => $store_id,
            'prepare_end' => $is_prepare_end,
            'business_order_sn' =>  $business_order_sn ?: ''
        ];
        return (new MallOrderCombine)->add($data);
	}

    public function getInfo($combine_id){
        $combine_info = (new MallOrderCombine)->getOne(['combine_id' => $combine_id]);
        if(empty($combine_info) || empty($combine_info['order_ids'])) throw new \think\Exception("没有找到订单");
        $combine_info = $combine_info->toArray();
        $combine_info['orderids'] = explode(',', $combine_info['order_ids']);
        $combine_info['timeout'] = ($combine_info['addtime'] + $combine_info['time_remaining']*60) > time() ? 0 : 1;
        $combine_info['time_surplus'] = $combine_info['timeout'] == 0 ? ($combine_info['addtime'] + $combine_info['time_remaining']*60) - time() : 0;
        return $combine_info;
    }

    /**
     * 获取商城业务的最大使用积分限制
     * @param  [type] $combine_id [description]
     * @return [type]             [description]
     */
    public function getScoreMaxUse($combine_id){
        $combine_info = (new MallOrderCombine)->getOne(['combine_id' => $combine_id])->toArray();
        if(empty($combine_info) || empty($combine_info['order_ids'])) throw new \think\Exception("没有找到订单");

        $MallOrderService = new MallOrderService;
        $orders = $MallOrderService->getOrders([['order_id', 'in', $combine_info['order_ids']]]);
        
        $max_score = 0;
        foreach ($orders as $order) {
            //获取商品的最大抵扣积分
            $detail = (new MallOrderDetailService)->getOrderDetailGoods($order['order_id'], 'g.score_max,d.num');
            $common_max_score = (new MerchantPercentRateService)->getMaxScoreUse($order['mer_id'], 'mall', $order['money_real']);
            $goods_max_score = 0;
            foreach ($detail as $good) {
                $goods_max_score += $good['score_max']*$good['num'];
            }
            //优先级  商品最大使用积分 > 商城设置最大 > 商家设置最大 > 平台商城业务设置最大 > 平台设置最大
            if($goods_max_score > 0){
                $max_score += $goods_max_score;
                continue;
            }
            elseif($common_max_score > 0){
                $max_score += $common_max_score;
                continue;
            }
        }

        return $max_score;
    }

	/**************************************以下为对接支付中心的几个方法*********************************/
    /*
     * 获取订单信息
     * combine_id  合单表的主键ID
     */
    public function getOrderPayInfo($combine_id){
    	$combine_info = $this->getInfo($combine_id);
        $return = [
            'paid' => $combine_info['paid'],
            'is_cancel' => $combine_info['timeout'] == 1 ? 1 : 0,
            'time_remaining' => $combine_info['time_surplus'],
            'mer_id' => $combine_info['mer_id'],
            'city_id' => $combine_info['city_id'],
            'store_id' => $combine_info['store_id'],
            'order_money' => 0, //这个金额需要后续计算出来
            'uid' => $combine_info['uid'],
            'order_no' => $combine_info['order_no'],
            'title' => '商城订单',
            'merchant_balance_open' => false,//因为新版商城将商家会员卡余额功能前置，所以支付中心要关闭这个功能
            'business_order_sn' => $combine_info['business_order_sn'] ?: '',
            'goods_status' => 1,//（1：上架，0：下架）商品上下架状态，只要有一个商品处于下架状态，订单不能支付
        ];
        $goodsAry = (new MallOrderDetail())->getRealOrderGoods([['order_id', 'in', $combine_info['order_ids']]],'g.status')->toArray();
        if($goodsAry && in_array(0,array_column($goodsAry, 'status'))){
            $return['goods_status'] = 0;
        }
        $MallOrderService = new MallOrderService;
        $orders = $MallOrderService->getOrders([['order_id', 'in', $combine_info['order_ids']]]);        
        $MerchantCardService = new MerchantCardService;
        foreach ($orders as $key => $order) {
            if($order['goods_activity_type'] == 'prepare'){
                $prepare_pay_info = (new MallPrepareOrder)->getOne([['order_id', '=', $order['order_id']]]);
                if($prepare_pay_info['pay_status'] == '1'){
                    $orders[$key]['money_real'] = $prepare_pay_info['rest_price'];
                }
            }
            if(in_array($order['status'],[50,51,52])){//订单取消
                $return['is_cancel'] = 1;
            }
        }            //检查商家会员卡余额
        if($order['money_merchant_balance'] > 0 && $combine_info['prepare_end'] == '0'){
            $current_card = $MerchantCardService->getUserCard($order['uid'], $order['mer_id']);
            if($current_card && $current_card['card_money'] < $order['money_merchant_balance']){
                throw new \think\Exception("商家会员卡余额不足！");
            }
        }

        //扣除商家会员卡赠送余额(由于商城业务将商家会员卡余额功能前置到了提交订单页，所以就不走支付中心了)
        if($order['money_merchant_give_balance'] > 0 && $combine_info['prepare_end'] == '0'){
            $current_card = $MerchantCardService->getUserCard($order['uid'], $order['mer_id']);
            if($current_card && $current_card['card_money_give'] < $order['money_merchant_give_balance']){
                throw new \think\Exception("商家会员卡赠送余额不足！");
            }
        }

        //平台优惠券
        if($order['system_coupon_id'] && $combine_info['prepare_end'] == '0' && $key == 0){
            (new SystemCouponService())->checkCouponCanUse($order['system_coupon_id'],$order['mer_id'],$order['uid']);
        }

        //商家优惠券
        if($order['merchant_coupon_id'] && $combine_info['prepare_end'] == '0'){
            (new MerchantCouponService())->checkCouponCanUse($order['merchant_coupon_id'],$order['mer_id'],$order['uid']);
        }

        if(empty($orders)) throw new \think\Exception("没有找到订单");
        $return['order_money'] = array_sum(array_column($orders, 'money_real')) - array_sum(array_column($orders, 'money_merchant_balance')) - array_sum(array_column($orders, 'money_merchant_give_balance')) - array_sum(array_column($orders, 'money_qiye_balance'));
        $return['order_money'] = get_format_number($return['order_money']);
        // if($combine_info['timeout'] == 1){//取消子订单
        //     foreach($orders as $o){
        //         if(!$MallOrderService->changeOrderStatus($o['order_id'], 51, '超时取消订单')){
        //             throw new \think\Exception("修改订单状态失败！");            
        //         }
        //     }
        //     (new MallOrderCombine)->updateThis(['combine_id'=>$combine_id], ['is_timeout_cancel' => 1]);
        // }这里先去掉， 走计划任务吧
        return $return;
    }

    /**
     * 获取支付结果页地址
     * @param  [type]  $combine_id  [description]
     * @param  integer $is_cancel 1=已取消  0=未取消
     * @return array             返回跳转链接
     */

    public function getPayResultUrl($combine_id,$is_cancel=0){
        $combine_info = $this->getInfo($combine_id);
        $redirect_url = '';
        $MallOrderService = new MallOrderService;
        $orders = $MallOrderService->getOrders([['order_id', 'in', $combine_info['order_ids']]]);
        //如果改合单表包含多个子订单，则跳转到用户订单列表页；如只包含一个子订单，则跳转到订单详情页
        if(count($orders) == 1){
            if($orders[0]['goods_activity_type'] == 'group'){
                if($is_cancel == '1'){
                    $detail = (new MallOrderDetailService)->getOrderDetailGoods($orders[0]['order_id'], 'g.goods_id');
                    return ['redirect_url' => get_base_url('pages/shopmall_third/commodity_details?goods_id='.$detail[0]['goods_id']), 'direct' => 1];//跳转商品详情
                }
                return ['redirect_url' => get_base_url('pages/shopmall_third/inviteFriends?orderid='.$orders[0]['order_id']), 'direct' => 1];
            }
            else{
                return ['redirect_url' => get_base_url('pages/shopmall_third/orderDetails?order_id='.$orders[0]['order_id']), 'direct' => 0];
            }
        }
        else{
            return ['redirect_url' => get_base_url('pages/my/my_order?state=0&type=mall'), 'direct' => 0];
        }
    }

    public function afterPay($combine_id, $pay_info = []){
        fdump_api([$combine_id,$pay_info],'pay/mall_after_pay',1);
        $check_click_key = 'mall3_'.$combine_id;
        $check_click_time = Cache::get($check_click_key);
        if($check_click_time){
            fdump_api([$combine_id,$check_click_time],'pay/mall_after_pay',1);
            throw new \think\Exception(L_('请求过于频繁，请稍后操作！'));
        }else{
            Cache::set($check_click_key,time(),10);
        }
        $combine_info = $this->getInfo($combine_id);

        $MallOrderService = new MallOrderService;
        $orders = $MallOrderService->getOrders([['order_id', 'in', $combine_info['order_ids']]]);

        //算出每个订单在总合单表中的占比(按照money_real字段算)
        $money_real_total = array_sum(array_column($orders, 'money_real'));

        if(empty($orders)) throw new \think\Exception("没有找到订单");

        $order_record = [
            'current_score_use' => [],
            'current_score_deducte' => [],
            'current_system_balance' => [],
            'current_merchant_balance' => [],
            'current_merchant_give_balance' => [],
            'current_qiye_balance' => [],
            'current_internal_card_balance' => [],
            'money_online_pay' => []
        ];
        $UserService = new UserService();
        if($combine_info['uid']){
            $now_user = $UserService->getUser($combine_info['uid']);
        }
        $MerchantCardService = new MerchantCardService;
        $userBehaviorGoodsData = [];
        foreach ($orders as $key => $order) {
            $level1_money = 0;
            $level2_money = 0;
            $level3_money = 0;
            $order_percent = -1;//每个订单在总支付单中的支付占比  当它为-1时，则代表为最后一个取整的订单
            if($key != (count($orders) - 1)){//最后一个订单是相减
                $order_percent = get_format_number((($order['money_real']-$order['money_merchant_balance']-$order['money_merchant_give_balance'])/$money_real_total-$order['money_merchant_balance']-$order['money_merchant_give_balance']), 6);
            }

            //扣除商家会员卡余额(由于商城业务将商家会员卡余额功能前置到了提交订单页，所以就不走支付中心了)
            if($order['money_merchant_balance'] > 0 && $combine_info['prepare_end'] == '0'){
                $current_card = $MerchantCardService->getUserCard($order['uid'], $order['mer_id']);
                if($current_card && $current_card['card_money'] < $order['money_merchant_balance']){
                    throw new \think\Exception("商家会员卡余额不足！");
                }
                $desc = "购买 ".cfg('mall_alias_name')."商品 扣除会员卡余额，订单编号".$order['order_no'];
                (new CardNewService())->useMoney($order['mer_id'],$order['uid'],$order['money_merchant_balance'],$desc);
            }

            //扣除商家会员卡赠送余额(由于商城业务将商家会员卡余额功能前置到了提交订单页，所以就不走支付中心了)
            if($order['money_merchant_give_balance'] > 0 && $combine_info['prepare_end'] == '0'){
                $current_card = $MerchantCardService->getUserCard($order['uid'], $order['mer_id']);
                if($current_card && $current_card['card_money_give'] < $order['money_merchant_give_balance']){
                    throw new \think\Exception("商家会员卡赠送余额不足！");
                }
                $desc = "购买 ".cfg('mall_alias_name')."商品 扣除会员卡赠送余额，订单编号".$order['order_no'];
                (new CardNewService())->useGiveMoney($order['mer_id'],$order['uid'],$order['money_merchant_give_balance'],$desc);
            }

            //平台优惠券
            if($order['system_coupon_id'] && $combine_info['prepare_end'] == '0' && $key == 0){
                $result = (new SystemCouponService())->useCoupon($order['system_coupon_id'],$order['order_id'],'mall',$order['mer_id'],$order['uid']);
            }

            //商家优惠券
            if($order['merchant_coupon_id'] && $combine_info['prepare_end'] == '0'){
                $result = (new MerchantCouponService())->useCoupon($order['merchant_coupon_id'],$order['order_id'],'mall',$order['mer_id'],$order['uid']);
            }

            //扣除使用积分
            $current_score_use = 0;
            $current_score_deducte = 0;
            if($pay_info['current_score_use'] > 0){
                if(empty($order_record['current_score_use'])){//第一次， 则扣除用户积分
                    if($now_user['score_count'] < $pay_info['current_score_use']){
                        throw new \think\Exception("用户积分不足！");
                    }
                    $desc = "购买".cfg('mall_alias_name')."商品 ，扣除".cfg('score_name')." ，第三方支付编号".$pay_info['paid_orderid'];
//                    $desc = "购买".cfg('mall_alias_name')."商品 ，扣除".cfg('score_name')." ，订单编号".$combine_info['order_no'];
                    $use_result = $UserService->userScore($now_user['uid'],$pay_info['current_score_use'],$desc);
                    if($use_result['error_code']){
                        throw new \think\Exception($use_result['error_code']);
                    }
                }
                if($order_percent == -1){//最后一条订单取整
                    $current_score_use = $pay_info['current_score_use'] - array_sum($order_record['current_score_use']);
                    $current_score_deducte = $pay_info['current_score_deducte'] - array_sum($order_record['current_score_deducte']);
                }
                else{
                    $current_score_use = round($order_percent*$pay_info['current_score_use']);
                    $current_score_deducte = round($order_percent*$pay_info['current_score_deducte']*100)/100;
                }
                $order_record['current_score_use'][] = $current_score_use;
                $order_record['current_score_deducte'][] = $current_score_deducte;
            }

            $internal_card_pay = array();
            //扣除使用内部会员卡余额
            if ($pay_info['current_internal_card_balance'] > 0){
                if($order_percent == -1){//最后一条订单取整
                    $current_internal_card_balance = $pay_info['current_internal_card_balance'] - array_sum($order_record['current_internal_card_balance']);
                }
                else{
                    $current_internal_card_balance = round($order_percent*$pay_info['current_internal_card_balance']*100)/100;
                }

                $order_record['current_internal_card_balance'][] = $current_internal_card_balance;
                //记录并扣除每个订单一卡通
                foreach (json_decode($pay_info['current_internal_card_ids'], true) as $card_id){
                    $card = InternalMembershipCard::where('card_id', $card_id)->find();
                    $card_balance = round($card['balance_money'] * 100) / 100;
                    if ($current_internal_card_balance > 0 and $card_balance > 0){
                        if ($card_balance > $current_internal_card_balance){
                            $internal_card_pay[$card_id] = $current_internal_card_balance;
                            $card_balance -= $current_internal_card_balance;
                            $current_internal_card_balance = 0;
                        }else{
                            $internal_card_pay[$card_id] = round($card_balance * 100) / 100;
                            $current_internal_card_balance -= $card_balance;
                            $card_balance = 0;
                        }
                        $save_card['balance_money'] = $card_balance;
                        $save_card['last_time'] = time();
                        InternalMembershipCard::where('card_id', $card_id)->update($save_card);
                        MallOrder::where(['order_id' => $order['order_id']])->save([
                            'internal_card_pay' => json_encode($internal_card_pay)
                        ]);
                        //日志
                        $condition_card_log['card_id'] = $card_id;
                        $condition_card_log['operation_type'] = 2; //交易
                        $condition_card_log['before_balance'] = $card['balance_money'];
                        $condition_card_log['after_balance'] = $card_balance;
                        $condition_card_log['money'] = $card['balance_money'] - $card_balance;
                        $condition_card_log['business_type'] = 'mall';
                        $condition_card_log['order_id'] = $order['order_no'];
                        $condition_card_log['uid'] = $order['uid'];
                        $condition_card_log['describe'] = '购买商城商品, 扣除余额'. round($condition_card_log['money'] * 100) / 100  .'元, 订单编号'.$order['order_no'];
                        $condition_card_log['mer_id'] = $order['mer_id'];
                        $condition_card_log['store_id'] = $order['store_id'];
                        $condition_card_log['create_time'] = time();
                        (new InternalMembershipCardLog())->insert($condition_card_log);

                        // 发送模板消息
                        (new InternalCardService())->sendWxMsg($card_id, '-'.getFormatNumber($condition_card_log['money']), '购买商城商品');
                    }
                }
            }
            //扣除使用平台余额
            $current_system_balance = 0;
            if($pay_info['current_system_balance'] > 0){
                if(empty($order_record['current_system_balance'])){//第一次， 则扣除用户积分
                    if($now_user['now_money'] < $pay_info['current_system_balance']){
                        throw new \think\Exception("用户余额不足！");
                    }
                    $order_no_arr = array_column($orders, 'order_no');
                    $order_no_str = implode(',', $order_no_arr);
                    $desc = "购买".cfg('mall_alias_name')."商品 ，扣除余额 ，订单编号".$order_no_str;
                    $use_result = $UserService->userMoney($now_user['uid'],$pay_info['current_system_balance'],$desc);
                    fdump_api([$now_user['uid'],$pay_info['current_system_balance'],$desc,$use_result],'pay/mall_after_pay',1);
                    if($use_result['error_code']){
                        throw new \think\Exception($use_result['error_code']);
                    }
                }
                if($order_percent == -1){//最后一条订单取整
                    $current_system_balance = $pay_info['current_system_balance'] - array_sum($order_record['current_system_balance']);
                }
                else{
                    $current_system_balance = round($order_percent*$pay_info['current_system_balance']*100)/100;
                }
                $order_record['current_system_balance'][] = $current_system_balance;
            }

            //在线支付金额
            $money_online_pay = 0;
            if($pay_info['paid_money'] > 0){
                if($order_percent == -1){//最后一条订单取整
                    $money_online_pay = $pay_info['paid_money'] - array_sum($order_record['money_online_pay']);
                }
                else{
                    $money_online_pay = round($order_percent*$pay_info['paid_money']*100)/100;
                }
                $order_record['money_online_pay'][] = $money_online_pay;
            }

            $employee_score_pay = 0;
            $employee_balance_pay = 0;
            $employee_card_user_id = 0;
            if(customization('life_tools') && $pay_info['employee_card_user_id']){
                $employeeCardUserModel = new EmployeeCardUser();
                $employeeCardLogModel = new EmployeeCardLog();
                $cardUser = $employeeCardUserModel->where('user_id', $pay_info['employee_card_user_id'])->find();
                if($cardUser){
                    $mall_alias_name = cfg('mall_alias_name');
                    $time = time();
                    //使用员工卡余额支付
                    if($pay_info['current_employee_balance'] > 0){
                        if($cardUser->card_money < $pay_info['current_employee_balance']){
                            throw new \think\Exception("员工卡余额不足！");
                        }
                        $employeeCardUserModel->where('user_id', $pay_info['employee_card_user_id'])->update([
                            'card_money'    =>  $cardUser->card_money - $pay_info['current_employee_balance'],
                            'last_time'     =>  time()
                        ]);
                        $employeeCardLogModel->insert([
                            'card_id'		=>	$cardUser->card_id,
                            'user_id'		=>	$cardUser->user_id,
                            'mer_id'		=>	$cardUser->mer_id,
                            'uid'			=>	$cardUser->uid,
                            'num'			=>	$pay_info['current_employee_balance'],
                            'type'			=>	'money',
                            'change_type'	=>	'decrease',
                            'description'	=>	 '购买' . $mall_alias_name . '商品，消费余额'. $pay_info['current_employee_balance'] . '元',
                            'user_desc'		=>	 '购买' . $mall_alias_name . '商品，消费余额'. $pay_info['current_employee_balance'] . '元',
                            'operate_type'	=>	'user',
                            'operate_id'	=>	$cardUser->uid,
                            'add_time'		=>	$time,
                            'log_type'		=>	11,
                            'pay_type'		=>	'money'
                        ]);
                        $employee_balance_pay = $pay_info['current_employee_balance'];
                    }
                    //使用员工卡积分支付
                    if($pay_info['current_employee_score_deducte'] > 0){
                        if($cardUser->card_score < $pay_info['current_employee_score_deducte']){
                            throw new \think\Exception("员工卡积分不足！");
                        }
                        $employeeCardUserModel->where('user_id', $pay_info['employee_card_user_id'])->update([
                            'card_score'    =>  $cardUser->card_score - $pay_info['current_employee_score_deducte'],
                            'last_time'     =>  time()
                        ]);
                        $employeeCardLogModel->insert([
                            'card_id'		=>	$cardUser->card_id,
                            'user_id'		=>	$cardUser->user_id,
                            'mer_id'		=>	$cardUser->mer_id,
                            'uid'			=>	$cardUser->uid,
                            'num'			=>	$pay_info['current_employee_score_deducte'],
                            'type'			=>	'score',
                            'change_type'	=>	'decrease',
                            'description'	=>	'购买' . $mall_alias_name . '商品，抵扣积分'. $pay_info['current_employee_score_deducte'],
                            'user_desc'		=>	'购买' . $mall_alias_name . '商品，抵扣积分'. $pay_info['current_employee_score_deducte'],
                            'operate_type'	=>	'user',
                            'operate_id'	=>	$cardUser->uid,
                            'add_time'		=>	$time,
                            'log_type'		=>	11,
                            'pay_type'		=>	'score'
                        ]);
                        $employee_score_pay = $pay_info['current_employee_score_deducte'];
                    }
                    if($employee_score_pay || $employee_balance_pay){
                        $employee_card_user_id = $pay_info['employee_card_user_id'];
                    }
                }else{
                    throw new \think\Exception('员工卡不存在！');
                }
               
            }

            //获取订单明细
            $details = (new MallOrderDetailService)->getByOrderId(true, $order['order_id']);
            $is_prepare = 0;
            $is_prepare_end = 0;//是否是预售尾款（因为牵扯到二次支付，所以预售比较特殊）
            $change_status = true;
            $order_detail_goods_name = '';
            foreach ($details as $detail) {
                switch ($detail['activity_type']) {
                    case 'group'://修改拼团状态
                        (new MallActivityService)->afterPayUpdateGroupTeamUser($order['order_id']);
                        $change_status = false;//修改订单状态由邓那边去判断是否是拼主还是拼团成员，所以不由我们来修改订单状态
                        break;
                    case 'bargain'://修改砍价状态
                        (new MallActivityService)->afterPayUpdateBargainTeamStatus($order['order_id']);
                        break;
                    case 'periodic':
                        (new MallActivityService)->afterPayUpdatePeriodic($order['order_id']);
                        break;
                    case 'prepare'://修改预售支付状态
                        (new MallActivityService)->afterPayUpdatePrepareMsg($order['order_id']);
                        $is_prepare = 1;
                        if($combine_info['prepare_end'] == '1'){
                            $is_prepare_end = 1;
                        }
                        break;
                }
                $spread = (new MallGoodsService)->getSpreadRate($detail['goods_id']);
                $level1_money += $detail['money_total']*$spread['first_rate']/100;
                $level2_money += $detail['money_total']*$spread['second_rate']/100;
                $level3_money += $detail['money_total']*$spread['third_rate']/100;
                $userBehaviorGoodsData[] = [
                    'business_type'=>'mall',
                    'goods_id'=>$detail['goods_id'],
                    'uid'=>$order['uid'],
                    'name'=>$detail['name'],
                    'num'=>$detail['num'],
                    'from_type'=>$this->getFromType($order['source']),
                    'order_id'=>$order['order_id'],
                ];
                $order_detail_goods_name .= ($order_detail_goods_name?'、':'').$detail['name'].'x'.$detail['num'];
            }

            $status = 10;
            $status_note = '订单支付成功';
            if($is_prepare_end == 1){//预售尾款支付，逻辑不一样
                $save_data = [
                    'money_goods' => $order['money_goods'] + $current_score_deducte + $current_system_balance + $money_online_pay,
                    'score_use' => $order['score_use'] + $current_score_use,
                    'money_score' => $order['money_score'] + $current_score_deducte,
                    'money_system_balance' => $order['money_system_balance'] + $current_system_balance,
                    'money_online_pay' => $order['money_online_pay'] + $money_online_pay, 
                    'money_real' => $order['money_real'] + $current_score_deducte + $current_system_balance + $money_online_pay,
                    'online_pay_type' => $pay_info['paid_type'],
                    'pay_orderno' => $pay_info['paid_orderid'],
                    'pay_time' => time(),
                    'last_uptime' => time()
                ];
            }
            else{
                $save_data = [
                    'score_use' => $current_score_use,
                    'money_score' => $current_score_deducte,
                    'money_system_balance' => $current_system_balance,
                    'money_online_pay' => $money_online_pay, 
                    'employee_score_pay' => $employee_score_pay,
                    'employee_balance_pay' => $employee_balance_pay,
                    'employee_card_user_id' => $employee_card_user_id,
                    'pay_orderno' => $pay_info['paid_orderid'],
                    'online_pay_type' => $pay_info['paid_type'],
                    'pay_time' => time(),
                    'last_uptime' => time()
                ];
                if($is_prepare == 1){
                    $save_data['pre_pay_orderno'] = $pay_info['paid_orderid'];
                    $save_data['preorder_online_pay_type'] = $pay_info['paid_type'];
                    unset($save_data['pay_orderno']);
                    unset($save_data['online_pay_type']);
                    $status = 1;
                    $status_note = '订单定金支付成功';
                }
            }
            $MallOrderService->updateMallOrder(['order_id' => $order['order_id']], $save_data);

            if($change_status){
                $MallOrderService->changeOrderStatus($order['order_id'], $status, $status_note,[],0,'','normal',[],($pay_info['paid_money']>0?0:1));
            }
            if(customization('internal_membership_card')){
                try {
                    $result = $MallOrderService->uploadMiaomaiOrder($order['order_id']);
                    fdump_api(['order'=>$order,'result' => $result],'miaomai/uploadMiaomaiOrder',1);
                }catch (\Exception $e){
                    fdump_api($e->getMessage(),'miaomai/uploadMiaomaiOrderError',1);
                }
            }
            //增加佣金
            (new UserSpreadListService)->userToSpread($order['uid'], 'mall', $order['order_id'], $order['order_no'], $level1_money, $level2_money, $level3_money);
            //直播/短视频增加销量
            if((($order['is_liveshow'] > 0 && $order['liveshow_id'] > 0) || ($order['is_livevideo'] > 0 && $order['livevideo_id'] > 0)) && $status == 10){
                foreach ($details as $detail) {
                    if($detail['liveshow_id'] > 0 || $detail['livevideo_id'] > 0){
                        (new LiveGoodService)->addSaleNums($detail['goods_id'], $detail['liveshow_id'], $detail['livevideo_id'], $detail['num'], 'mall3');
                    }
                }
            }
            
            // 支付成功后打印小票
            $param = [
                'order_id' => $order['order_id'],
                'print_type' => 'bill_account'
            ];
            (new PrintHaddleService)->printOrder($param, 2);

            //短信通知
            $merchant = (new MerchantService())->getMerchantByMerId($order['mer_id']);
            
            //同步到第三方配送
            if($merchant['open_third_deliver']&&$order['is_third_deliver']&&!empty($pay_info['paid_type'])&&($is_prepare==0||($is_prepare==1&&$is_prepare_end==1))){
                //在线支付可以直接推送第三方，余额支付最外卖有事务推送后会出现pay_time还未提交保存报错未支付的情况
                try {
                    (new DeliverApiService())->assigned($order,$details);
                }catch (\Exception $e){
                    fdump_api([$e->getMessage().$e->getFile().$e->getLine(),$order['order_no']],'mall_third_deliver/changeStatus',1);
                }
            }
            
            if(in_array(cfg('sms_success_order'),[2,3])&&$merchant['phone']){
                $sms_data['uid'] = 0;
                $sms_data['mobile'] = $merchant['phone'];
                $sms_data['sendto'] = 'merchant';
                $sms_data['content'] = L_('顾客购买的x1的订单(订单号：x2),在x3时已经完成了支付！', array('x1' => cfg('mall_alias_name_new'), 'x2' => $order['order_no'], 'x3' => date('Y-m-d H:i:s')));
                $merchant_sms_res = (new SmsService())->sendSms($sms_data);
            }
            if(in_array(cfg('sms_success_order'),[1,3])&&$now_user['phone']){
                $sms_data['uid'] = $now_user['uid'];
                $sms_data['mobile'] = $now_user['phone'];
                $sms_data['sendto'] = 'user';
                $sms_data['content'] = L_('您购买 x1的订单(订单号：x2)已经完成支付!', array('x1' => cfg('mall_alias_name_new'), 'x2' => $order['order_no']));
                $user_sms_res = (new SmsService())->sendSms($sms_data);
            }
            //微信通知用户
            if(!empty($now_user)&&$now_user['openid']){
                $model = new TemplateNewsService();
                $href = get_base_url('pages/shopmall_third/orderDetails?order_id=' . $order['order_id']);
                $model->sendTempMsg('OPENTM201752540', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' => cfg('mall_alias_name_new') . L_('提醒'), 'keyword1' => $order_detail_goods_name, 'keyword2' => $order['order_no'], 'keyword3' => getFormatNumber($order['money_real']), 'keyword4' => date('Y-m-d H:i:s'), 'remark' => L_("X1订单支付成功，您的订单号：X2", array("X1" => cfg('mall_alias_name_new'), "X2" => $order['order_no']))));
            }
            
        }
        (new MallOrderCombine)->updateThis(['combine_id'=>$combine_id], ['paid' => 1]);
        //用户行为记录
        if($userBehaviorGoodsData){
            (new UserBehaviorGoodsService())->addUserBehaviorGoodsAll($userBehaviorGoodsData);
        }
        //滚动消息
        if ($now_user) {
            (new \app\common\model\service\ScrollMsgService())->addMsg('mall', $now_user['uid'], L_('用户x1于x2购买x3成功', array('x1' => str_replace_name($now_user['nickname']), 'x2' => date('Y-m-d H:i', time()), 'x3' => cfg('mall_alias_name_new'))));
        }
        fdump_api('支付成功结束','pay/mall_after_pay',1);
    }
    /**************************************以上为对接支付中心的几个方法*********************************/
    public function getFromType($type)
    {
        $source = [
            'androidapp'=>1,//安卓
            'iosapp'=>2,//ios
            'wechat_mini'=>3,//小程序
            'wechat_h5'=>7,//公众号
            'h5'=>6,//移动端
            'pc'=>4,//pc端
        ]; 
        return $source[$type] ?? 5;
    }

    public function merchantStoreStatistic($storeId, $date)
    {
        $time = Carbon::parse($date);
        $startTime = $time->startOfDay()->timestamp;
        $endTime = $time->endOfDay()->timestamp;
        //总支付金额，平台余额支付金额， 支付订单数量
        $payMoney = MallOrder::where([
            ['store_id', '=', $storeId],
            ['pay_time', '>=', $startTime],
            ['pay_time', '<=', $endTime],
        ])
            ->field(
                'SUM(money_real) pay_money,
                SUM(money_system_balance) balance_pay_money,
                SUM(money_refund) refund_money,
                count(*) cnt'
            )
            ->find();
        //支付宝支付金额
        $aliPayMoney = MallOrder::alias('o')
            ->leftJoin('pay_order_info p', "o.order_id = p.business_order_id AND p.business = 'mall'")
            ->where([
                ['o.store_id', '=', $storeId],
                ['o.status', '>', 1],
                ['o.pay_time', '>=', $startTime],
                ['o.pay_time', '<=', $endTime],
                ['p.pay_type', 'IN', 'alipayh5, alipay'],
            ])
            ->field('SUM(p.paid_money) pay_money')
            ->find();
        //微信支付金额
        $weixinPayMoney = MallOrder::alias('o')
            ->leftJoin('pay_order_info p', "o.order_id = p.business_order_id AND p.business = 'mall'")
            ->where([
                ['o.store_id', '=', $storeId],
                ['o.status', '>', 1],
                ['o.pay_time', '>=', $startTime],
                ['o.pay_time', '<=', $endTime],
                ['p.pay_type', 'IN', 'weixinh5, weixin'],
            ])
            ->field('SUM(p.paid_money) pay_money')
            ->find();
        //一卡通支付金额
        $cardMoney = InternalMembershipCardLog::where([
            ['store_id', '=', $storeId],
            ['operation_type', '=', 2],//支付
            ['business_type', 'IN', 'mall'],
            ['create_time', '>=', $startTime],
            ['create_time', '<=', $endTime],
        ])
            ->field('SUM(money) pay_money')
            ->find();

        $allPayMoney = bcsub(
            $cardMoney['pay_money'] + $payMoney['balance_pay_money'] + $aliPayMoney['pay_money'] + $weixinPayMoney['pay_money'], 
            $payMoney['refund_money'], 
            2);
        
        $data = [
            'mer_id' => MerchantStore::where(['store_id' => $storeId, 'status' => 1])->value('mer_id'),
            'store_id' => $storeId,
            'order_type' => 'mall',
            'bill_date' => $date,
            'pay_money' => $allPayMoney,
            'pay_order_count' => $payMoney['cnt'],
            'internal_card_pay_money' => $cardMoney['pay_money'] ?: 0,
            'balance_pay_money' => $payMoney['balance_pay_money'] ?: 0,
            'alipay_pay_money' => $aliPayMoney['pay_money'] ?: 0,
            'wechat_pay_money' => $weixinPayMoney['pay_money'] ?: 0,
            'refund_money' => $payMoney['refund_money'] ?: 0,
        ];

        return (new InternalCardMoneyBillService())->saveMerchantStoreDateBill($data);
    }

    /**
     * 余额支付后推送第三方配送
     * @param $combine_order_id
     * @throws \think\Exception
     */
    public function pushThirdDeliver($combine_order_id){
        $combine_info = $this->getInfo($combine_order_id);
        $MallOrderService = new MallOrderService;
        $orders = $MallOrderService->getOrders([['order_id', 'in', $combine_info['order_ids']],['is_third_deliver','=',1],['pay_time','>',0]]);
        foreach ($orders as $order){
            $open_third_deliver = (new Merchant())->where(['mer_id'=>$order['mer_id']])->value('open_third_deliver');
            //同步到第三方配送
            if($open_third_deliver){
                //获取订单明细
                $details = (new MallOrderDetailService)->getByOrderId(true, $order['order_id']);
                $is_prepare = 0;
                $is_prepare_end = 0;//是否是预售尾款（因为牵扯到二次支付，所以预售比较特殊）
                foreach ($details as $detail) {
                    if ($detail['activity_type']=='prepare') { //预售订单支付尾款后才推送订单
                        (new MallActivityService)->afterPayUpdatePrepareMsg($order['order_id']);
                        $is_prepare = 1;
                        if($combine_info['prepare_end'] == '1'){
                            $is_prepare_end = 1;
                        }
                    }
                }
                if($is_prepare==0||($is_prepare==1&&$is_prepare_end==1)) {
                    //在线支付可以直接推送第三方，余额支付最外卖有事务推送后会出现pay_time还未提交保存报错未支付的情况
                    try {
                        (new DeliverApiService())->assigned($order, $details);
                    } catch (\Exception $e) {
                        fdump_api([$e->getMessage() . $e->getFile() . $e->getLine(), $order['order_no']], 'mall_third_deliver/changeStatus', 1);
                    }
                }
            }
        }
    }
}