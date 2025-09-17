<?php
/**
 * 团购商品过期自动处理 1:平台冻结|2:自动退款|3:自动核销
 */
class plan_group_order_out_auto_operate extends plan_base{

	public function runTask() {
        $tm = time();
        //非场次预约
        $order_list = D('Group_order')->get_out_order_List("`g`.`group_cate` <> 'booking_appoint' AND `o`.`auto_operate_time` < ".($tm - 2*3600));
        //场次预约
        $booking_order_list = D('Group_order')->alias('o')
        ->field('o.*,u.nickname,u.phone AS user_phone')
        ->join('INNER JOIN ' . C('DB_PREFIX') . 'group g ON o.group_id=g.group_id')
        ->join('INNER JOIN ' . C('DB_PREFIX') . 'group_booking_appoint_order ob ON ob.order_id = o.order_id')
        ->join('INNER JOIN ' . C('DB_PREFIX') . 'user u ON u.uid=o.uid')
        ->where(['o.paid' => 1, 'o.status' => 0, 'g.group_cate' => 'booking_appoint', 'ob.book_end_time' => ['LT', $tm]])
        ->select();
        $order_list = array_merge($order_list, $booking_order_list );

        if(S('group_order_out_auto_operate')){
            return true;
        }
        S('group_order_out_auto_operate', 1, 60);
		if (!empty($order_list)) {
            $type = [1=>'autoFrozen',2=>'autoRefund',3=>'autoVerify']; //2: //自动退款,3: //自动核销,1: //平台冻结
            foreach ($order_list as $v) {
                $this->dealGroupOutAutoOperate($v,($type[C('config.group_out_auto_operate')]??'autoFrozen'));
                sleep(1);
            }
		}
        S('group_order_out_auto_operate', null);
		return true;
	}
	
	public function dealGroupOutAutoOperate($data,$method){
        D()->startTrans();
        try {
            $this->$method($data);
            D()->commit();
        }catch (\Exception $e){
            D()->rollback();
            M('Group_order')->where(['order_id'=>$data['order_id']])->save(['auto_operate_time'=>time(),'auto_operate_error'=>$e->getMessage()]);
        }
    }

    public function autoRefund($v) {
        $now_order = D('Group_order')->get_order_detail_by_id($v['uid'], intval($v['order_id']),true);
        if (empty($now_order)) {
            throw new Exception('该订单不存在');
        }
        $now_group = M('Group')->where(array('group_id' => $now_order['group_id']))->find();
        if ($now_group['no_refund'] == 1) { //不支持退款,自动核销
            $this->autoVerify($v);
        }
        // 如果成团后退款手续费收取大于等于100%，返回错误
        if ($now_group['pin_num']>0 && $now_group['group_refund_fee']>0 && $now_group['group_refund_fee']>=100) {
            $this->autoVerify($v);
        }
        // 拼团做判断
        if( $now_group['pin_num']>0 && !$now_order['single_buy']) {
            // 如果使用了积分且需退的金额大于0，计算退多少积分 优先退积分
            $group_refund_fee = $now_group['group_refund_fee']>0?$now_group['group_refund_fee']/100:0; // 退款所需手续费
        } else {
            $group_refund_fee = 0;
        }

        if(C('config.open_allinyun')==1){
            import('@.ORG.AccountDeposit.AccountDeposit');
            $deposit = new AccountDeposit('Allinyun');
            $allyun = $deposit->getDeposit();

            $allinyun_user = D('Deposit')->allinyun_user($now_order['uid']);

            if($allinyun_user['bizUserId']!=''){
                $allyun->setUser($allinyun_user);
                $params['bizOrderNo'] = 'group_'.$now_order['orderid'];
                $params['oriBizOrderNo'] = $now_order['third_id'];
                $params['amount'] = intval($now_order['payment_money']*100);
                $res = $allyun->refundApply($params);
                if($res['status']=='OK'){

                    $data_group_order['refund_detail'] = serialize($res);
                    $data_group_order['status'] = 3;
                    D('Group_order')->where(array('order_id' => $now_order['order_id']))->save($data_group_order);

                    /*-----记录系统订单信息 订单取消退款成功 改写状态已退款 19/1/18---团购状态-已退款3-*/
                    D("System_order")->save_order_status('group',$now_order['order_id'],4, 3);
                    return true;
                } else {
                    throw new Exception($res['message']);
                }
            }
        }

        // 首先 计算出 已经使用的核销码数量和未使用的核销码数量  同步计算出需要抵扣的金额 线上支付+平台余额+商户余额+商户赠送余额+积分抵扣金额
        $pass_num = $now_order['pass_num']>1?intval($now_order['pass_num']):1; // 一份团购 对应生成核销券 份数
        $total_pass_num = $now_order['num']*$pass_num; // 改订单产生的总 核销券 份数
        $express_fee = $now_order['express_fee'] > 0 ? intval($now_order['express_fee']):0; // 运费
        $total_pay_money = $now_order['price'] * intval($now_order['num']) + $express_fee; // // 改订单实际支付的总金额 线上支付+平台余额+商户余额+商户赠送余额+积分抵扣金额+运费  直接单价*数量
        $total_pay_money = sprintf("%.2f",$total_pay_money);
        if ($now_order['card_discount']>0 && $now_order['card_discount']<10) {
            $card_discount_money = $total_pay_money - round($total_pay_money * $now_order['card_discount'] * 0.1, 2); // 优先会员卡折扣优惠金额
        } else {
            $card_discount_money = 0;
        }
        $total_pay_money = $total_pay_money - $now_order['wx_cheap']; // 去除微信端/app优惠
        $actual_total_pay_money = $total_pay_money - $now_order['card_price'] - $now_order['coupon_price'] - $card_discount_money; // 实际支付总金额   总金额-各种优惠金额（商家优惠券金额+平台优惠券金额+	微信优惠+会员卡折扣优惠金额）[用户等级优惠在单价上已经计算过了，不予考虑]
        $actual_total_pay_money = sprintf("%.2f",$actual_total_pay_money);
        //计算下订单记录的金额
        $total_order_pay = $now_order['merchant_balance'] + $now_order['balance_pay'] + $now_order['payment_money'] + $now_order['score_deducte'];
        if ($total_order_pay>$actual_total_pay_money) {
            // 兼容原来旧的 计算优惠顺序不同的金额
            $actual_total_pay_money = $total_order_pay;
        }
        // 计算没分核销码的单价
        $pass_price = sprintf("%.4f",$actual_total_pay_money/$total_pass_num);
        if($now_order['pass_array']){
            $consume_num = D('Group_pass_relation')->get_pass_num($now_order['order_id'],1);
            $unconsume_pass_num = $total_pass_num-$consume_num;
        }elseif($now_order['tuan_type']==2){
            $unconsume_pass_num = $total_pass_num;
        }else{
            $unconsume_pass_num = 1;
        }
        $need_refund_money = $unconsume_pass_num * $pass_price; // 未使用的核销券 需要对应退款的总金额
        $need_refund_money = sprintf("%.2f",$need_refund_money);
        $refund_money_total = $need_refund_money; // 总需要退款金额
        // 如果使用了积分且需退的金额大于0，计算退多少积分 优先退积分
        // 团购退款手续总费用
        $refund_fee_total = $need_refund_money*$group_refund_fee; // 团购退款手续总费用 = 总需要退款金额*手续费比率
        $refund_fee_total = sprintf("%.2f",$refund_fee_total);
        $refund_score = 0; // 默认需要退款 积分 为 0
        $refund_score_fee = 0; // 积分退款手续费-积分数
        if ($now_order['score_used_count']>0 && $need_refund_money>0) {
            // 首先判断积分是否有剩余需要退的
            if ($need_refund_money >= $now_order['score_deducte']) {
                $need_refund_money = $need_refund_money - $now_order['score_deducte']; // 计算还需要退款金额 = 需退款金额 - 积分抵扣金额
                $refund_score = $now_order['score_deducte'] * (1 - $group_refund_fee) * ($now_order['score_used_count']/$now_order['score_deducte']); // 需要退的积分数量 = 积分抵扣金额 * (1-退款手续费) * （使用积分数/对应抵扣金额数）
                $refund_score = sprintf("%.2f",$refund_score); // 保留两位小数
                if ($group_refund_fee>0) {
                    $refund_score_fee = $now_order['score_used_count'] - $refund_score; // 积分退款手续费-积分数 = 总的使用积分数-需要退的积分数
                    $refund_score_fee = sprintf("%.2f",$refund_score_fee); // 保留两位小数
                }
            } else {
                $refund_score = $need_refund_money * (1 - $group_refund_fee) * ($now_order['score_used_count']/$now_order['score_deducte']); // 计算需要退的积分 = 需退金额 * (1-退款手续费) * （使用积分数/对应抵扣金额数）
                $refund_score = sprintf("%.2f",$refund_score);
                if ($group_refund_fee>0) {
                    $refund_score_fee = sprintf("%.2f",$need_refund_money * ($now_order['score_used_count']/$now_order['score_deducte'])) - $refund_score; // 积分退款手续费-积分数 = 总的需要退的积分（未计算手续费的）-需要退的积分数
                    $refund_score_fee = sprintf("%.2f",$refund_score_fee); // 保留两位小数
                }
                $need_refund_money = 0; // 积分就可以退完 需退金额， 后续就不需要在退了
            }
        }
        // 如果使用了商户会员卡赠送金额且还需退的金额大于0 接着退 商户会员卡赠送金额
        $refund_card_give_money = 0; // 默认需要退款 商户会员卡赠送金额 为 0
        $refund_card_give_fee = 0; // 商户会员卡赠送金额 退款手续费
        if($now_order['card_give_money'] > 0 && $need_refund_money>0){
            $need_refund_money = sprintf("%.2f",$need_refund_money);
            if ($need_refund_money >= $now_order['card_give_money']) {
                $need_refund_money = $need_refund_money - $now_order['card_give_money']; // 计算还需要退款金额 = 还需要退款的金额 - 商户会员卡赠送余额
                $refund_card_give_money = $now_order['card_give_money'] * (1 - $group_refund_fee); // 需要退的商户会员卡赠送余额 = 商户会员卡赠送余额 * (1-退款手续费)
                $refund_card_give_money = sprintf("%.2f",$refund_card_give_money);// 保留两位小数
                if ($group_refund_fee>0) {
                    $refund_card_give_fee = $now_order['card_give_money'] - $refund_card_give_money; // 商户会员卡赠送金额手续费 = 总的商户会员卡赠送金额-需要退的商户会员卡赠送金额
                    $refund_card_give_fee = sprintf("%.2f",$refund_card_give_fee); // 保留两位小数
                }
            } else {
                $refund_card_give_money = $need_refund_money * (1 - $group_refund_fee); // 计算需要退的商户会员卡赠送余额 = 需退金额* (1-退款手续费)
                $refund_card_give_money = sprintf("%.2f",$refund_card_give_money); // 保留两位小数
                if ($group_refund_fee>0) {
                    $refund_card_give_fee = $need_refund_money - $refund_card_give_money; // 商户会员卡赠送金额手续费 = 需要退的金额-需要退的商户会员卡赠送金额
                    $refund_card_give_fee = sprintf("%.2f",$refund_card_give_fee); // 保留两位小数
                }
                $need_refund_money = 0; // 商户会员卡赠送余额 就可以退完 需退金额， 后续就不需要在退了
            }
        }
        // 如果使用了商户会员卡金额且还需退的金额大于0 接着退 商户会员卡金额
        $refund_merchant_balance = 0; // 默认需要退款 商户会员卡金额 为 0
        $refund_merchant_balance_fee = 0; // 商户会员卡赠送金额 退款手续费
        if($now_order['merchant_balance'] > 0 && $need_refund_money>0){
            $need_refund_money = sprintf("%.2f",$need_refund_money);
            if ($need_refund_money >= $now_order['merchant_balance']) {
                $need_refund_money = $need_refund_money - $now_order['merchant_balance']; // 计算还需要退款金额 = 还需要退款的金额 - 商户会员卡金额
                $refund_merchant_balance = $now_order['merchant_balance'] * (1 - $group_refund_fee); // 需要退的商户会员卡金额= 需要退的商户会员卡金额 * (1-退款手续费)
                $refund_merchant_balance = sprintf("%.2f",$refund_merchant_balance); // 保留两位小数
                if ($group_refund_fee>0) {
                    $refund_merchant_balance_fee = $now_order['merchant_balance'] - $refund_merchant_balance; // 商户会员卡金额手续费 = 需要退的金额-需要退的商户会员卡金额
                    $refund_merchant_balance_fee = sprintf("%.2f",$refund_merchant_balance_fee); // 保留两位小数
                }
            } else {
                $refund_merchant_balance = $need_refund_money * (1 - $group_refund_fee); // 计算需要退的商户会员卡金额 = 需退金额 * (1-退款手续费)
                $refund_merchant_balance = sprintf("%.2f",$refund_merchant_balance); // 保留两位小数
                if ($group_refund_fee>0) {
                    $refund_merchant_balance_fee = $need_refund_money - $refund_merchant_balance; // 商户会员卡赠送金额手续费 = 需要退的金额-需要退的商户会员卡赠送金额
                    $refund_merchant_balance_fee = sprintf("%.2f",$refund_merchant_balance_fee); // 保留两位小数
                }
                $need_refund_money = 0; // 商户会员卡金额 就可以退完 需退金额， 后续就不需要在退了
            }
        }
        // 如果使用了平台余额 且还需退的金额大于0 接着退 平台余额
        $refund_balance_pay = 0; // 默认需要退款 平台余额 为 0
        $refund_balance_pay_fee = 0; // 平台余额 退款手续费
        if($now_order['balance_pay'] > 0 && $need_refund_money>0){
            $need_refund_money = sprintf("%.2f",$need_refund_money);
            if ($need_refund_money >= $now_order['balance_pay']) {
                $need_refund_money = $need_refund_money - $now_order['balance_pay']; // 计算还需要退款金额 = 还需要退款的金额 - 平台余额
                $refund_balance_pay = $now_order['balance_pay'] * (1 - $group_refund_fee); // 需要退的平台余额 = 需要退的平台余额 * (1-退款手续费)
                $refund_balance_pay = sprintf("%.2f",$refund_balance_pay); // 保留两位小数
                if ($group_refund_fee>0) {
                    $refund_balance_pay_fee = $now_order['balance_pay'] - $refund_balance_pay; // 平台余额 = 需要退的金额-需要退的平台余额
                    $refund_balance_pay_fee = sprintf("%.2f",$refund_balance_pay_fee); // 保留两位小数
                }
            } else {
                $refund_balance_pay = $need_refund_money * (1 - $group_refund_fee); // 计算需要退的平台余额 = 需退金额 * (1-退款手续费)
                $refund_balance_pay = sprintf("%.2f",$refund_balance_pay); // 保留两位小数
                if ($group_refund_fee>0) {
                    $refund_balance_pay_fee = $need_refund_money - $refund_balance_pay; // 平台余额手续费 = 需要退的金额-需要退的平台余额
                    $refund_balance_pay_fee = sprintf("%.2f",$refund_balance_pay_fee); // 保留两位小数
                }
                $need_refund_money = 0; // 平台余额 就可以退完 需退金额， 后续就不需要在退了
            }
        }
        // 如果使用了平台E卡支付 且还需退的金额大于0 接着退 平台E卡金额
        $refund_ecard_pay = 0; // 默认需要退款 平台E卡金额 为 0
        $refund_ecard_pay_fee = 0; // 平台E卡 退款手续费
        if($now_order['use_ecard_price'] > 0 && $need_refund_money>0){
            $need_refund_money = sprintf("%.2f",$need_refund_money);
            if ($need_refund_money >= $now_order['use_ecard_price']) {
                $need_refund_money = $need_refund_money - $now_order['use_ecard_price']; // 计算还需要退款金额 = 还需要退款的金额 - 平台E卡余额
                $refund_ecard_pay = $now_order['use_ecard_price'] * (1 - $group_refund_fee); // 需要退的平台E卡余额 = 需要退的平台E卡余额 * (1-退款手续费)
                $refund_ecard_pay = sprintf("%.2f",$refund_ecard_pay); // 保留两位小数
                if ($group_refund_fee>0) {
                    $refund_ecard_pay_fee = $now_order['use_ecard_price'] - $refund_ecard_pay; // 平台E卡余额 = 需要退的金额-需要退的平台余额
                    $refund_ecard_pay_fee = sprintf("%.2f",$refund_ecard_pay_fee); // 保留两位小数
                }
            } else {
                $refund_ecard_pay = $need_refund_money * (1 - $group_refund_fee); // 计算需要退的平台余额 = 需退金额 * (1-退款手续费)
                $refund_ecard_pay = sprintf("%.2f",$refund_ecard_pay); // 保留两位小数
                if ($group_refund_fee>0) {
                    $refund_ecard_pay_fee = $need_refund_money - $refund_ecard_pay; // 平台余额手续费 = 需要退的金额-需要退的平台余额
                    $refund_ecard_pay_fee = sprintf("%.2f",$refund_ecard_pay_fee); // 保留两位小数
                }
                $need_refund_money = 0; // 平台余额 就可以退完 需退金额， 后续就不需要在退了
            }
        }

        //  如果使用了线上支付 且还需退的金额大于0 接着退 线上支付
        $refund_payment_money = 0; // 默认需要退款 线上支付 为 0
        $refund_payment_money_fee = 0; // 线上支付 退款手续费
        if($now_order['payment_money'] > 0 && $need_refund_money>0){
            $need_refund_money = sprintf("%.2f",$need_refund_money);
            if ($need_refund_money >= $now_order['payment_money']) {
                $need_refund_money = $need_refund_money - $now_order['payment_money']; // 计算还需要退款金额 = 还需要退款的金额 - 线上支付金额
                $refund_payment_money = $now_order['payment_money'] * (1 - $group_refund_fee); // 需要退的线上支付 = 线上支付金额 * (1-退款手续费)
                $refund_payment_money = sprintf("%.2f",$refund_payment_money); // 保留两位小数
                if ($group_refund_fee>0) {
                    $refund_payment_money_fee = $now_order['payment_money'] - $refund_payment_money; // 线上支付金额 = 需要退的金额-需要退的线上支付金额
                    $refund_payment_money_fee = sprintf("%.2f",$refund_payment_money_fee); // 保留两位小数
                }
            } else {
                $refund_payment_money = $need_refund_money * (1 - $group_refund_fee); // 计算需要退的线上支付金额 = 需退金额 * (1-退款手续费)
                $refund_payment_money = sprintf("%.2f",$refund_payment_money); // 保留两位小数
                if ($group_refund_fee>0) {
                    $refund_payment_money_fee = $need_refund_money - $refund_payment_money; // 线上支付金额手续费 = 需要退的金额-需要退的线上支付金额
                    $refund_payment_money_fee = sprintf("%.2f",$refund_payment_money_fee); // 保留两位小数
                }
                $need_refund_money = 0; // 平台余额 就可以退完 需退金额， 后续就不需要在退了
            }
        }
        // 如果使用了线下退款 且还需退的金额大于0  接着计算 线下退款金额
        $refund_offline_money = 0; // 默认需要退款 线下支付 为 0
        $refund_offline_money_fee = 0; // 线下支付 退款手续费
        if($now_order['pay_type'] == 'offline' && $need_refund_money>0){
            $offline_money = $total_pay_money-$now_order['merchant_balance']-$now_order['balance_pay']-$now_order['score_deducte']-$now_order['card_price']-$now_order['coupon_price']-$card_discount_money; // 计算线下支付金额

            if ($need_refund_money >= $offline_money) {
                $need_refund_money = $need_refund_money - $offline_money; // 计算还需要退款金额 = 还需要退款的金额 - 线下支付金额
                $refund_offline_money = $offline_money * (1 - $group_refund_fee); // 需要退的线下支付金额 = 线下支付金额 * (1-退款手续费)
                $refund_offline_money = sprintf("%.2f",$refund_offline_money); // 保留两位小数
                if ($group_refund_fee>0) {
                    $refund_offline_money_fee = $offline_money - $refund_offline_money; // 线下支付金额 = 需要退的金额-需要退的线下支付金额
                    $refund_offline_money_fee = sprintf("%.2f",$refund_offline_money_fee); // 保留两位小数
                }
            } else {
                $need_refund_money = sprintf("%.2f",$need_refund_money);
                $refund_offline_money = $need_refund_money * (1 - $group_refund_fee); // 计算需要退的线下支付 = 需退金额 * (1-退款手续费)
                $refund_offline_money = sprintf("%.2f",$refund_offline_money); // 保留两位小数
                if ($group_refund_fee>0) {
                    $refund_offline_money_fee = $need_refund_money - $refund_offline_money; // 线下支付金额手续费 = 需要退的金额-需要退的线下支付金额
                    $refund_offline_money_fee = sprintf("%.2f",$refund_offline_money_fee); // 保留两位小数
                }
                $need_refund_money = 0; // 所有的钱均在线下支付退完
            }


        }

        // 退款详情
        $refund_detail = array(
            'refund_score' => $refund_score,
            'refund_score_fee' => $refund_score_fee,
            'refund_card_give_money' => $refund_card_give_money,
            'refund_card_give_fee' => $refund_card_give_fee,
            'refund_merchant_balance' => $refund_merchant_balance,
            'refund_merchant_balance_fee' => $refund_merchant_balance_fee,
            'refund_balance_pay' => $refund_balance_pay,
            'refund_balance_pay_fee' => $refund_balance_pay_fee,
            'refund_ecard_pay' => $refund_ecard_pay,
            'refund_ecard_pay_fee' => $refund_ecard_pay_fee,
            'refund_payment_money' => $refund_payment_money,
            'refund_payment_money_fee' => $refund_payment_money_fee,
            'refund_offline_money' => $refund_offline_money,
            'refund_offline_money_fee' => $refund_offline_money_fee,
            'refund_time' => time()
        );
        $order_id = $now_order['order_id'];
        $data_group_order['order_id'] = $order_id;  // 对应团购id
        $data_group_order['refund_money'] = $refund_money_total; // 以实际总金额计算出来的需要退款总金额
        $data_group_order['refund_fee'] = $refund_fee_total; // 退款总手续费  以实际支付总金额计算

        //线下付款退款-如果支付方式线下付款， 对应退上面所做计算得出的退款金额
        if($now_order['pay_type'] == 'offline'){
            $data_group_order['order_id'] = $order_id;
            $data_group_order['refund_detail'] = serialize(array('refund_time'=>time()));
            $data_group_order['status'] = 3;
            if(D('Group_order')->data($data_group_order)->save()){
                //2015-12-24     线下退款时销量回滚
                $update_group = D('Group')->where(array('group_id' => $now_order['group_id']))->find();
                if($now_group['group_cate'] == 'booking_appoint') {// 场次预约
                    $ruleInfo = M('Group_booking_appoint_order')->where(['order_id'=>$now_order['order_id']])->find();
                }
                if ($update_group['type'] == 3) {
                    // 判断是否为团购规格退款
                    $update_group_data = array();

                    if ($now_order['specifications_id']) {
                        $m_group_specifications = M('Group_specifications');
                        $specifications_info = $m_group_specifications->where(array('specifications_id' => $now_order['specifications_id']))->field('count_num, sale_count')->find();
                        $sale_count = $specifications_info['sale_count'] - $now_order['num'];
                        $sale_count = $sale_count > 0 ? $sale_count : 0;
                        $update_data = array('sale_count' => $sale_count);
                        // 货物总数为零 即不限制
                        if (($specifications_info['count_num'] > 0 && $sale_count < $specifications_info['count_num']) || $specifications_info['count_num'] == 0) {
                            $update_group_data['type'] = 1;
                        }
                        $m_group_specifications->where(array('specifications_id' => $now_order['specifications_id']))->save($update_data);
                    } elseif($ruleInfo) {
                        $v20_param = [
                            'ruleId' => $ruleInfo['rule_id'],
                            'combineId' => $ruleInfo['combine_id'],
                            'num' => $now_order['num'],
                            'type' => 2
                        ];
                        invoke_v20_service('\app\group\model\service\GroupBookingAppointService/updateStock', $v20_param);
                    } else {
                        $sale_count = $update_group['sale_count'] - $now_order['num'];
                        $sale_count = $sale_count > 0 ? $sale_count : 0;
                        $update_group_data = array('sale_count' => $sale_count);
                        if ($update_group['count_num'] > 0 && $sale_count < $update_group['count_num']) {
                            $update_group_data['type'] = 1;
                        }
                    }
                    D('Group')->where(array('group_id' => $now_order['group_id']))->save($update_group_data);
                } else {
                    //退款时销量回滚
                    if ($now_order['specifications_id']) {
                        $m_group_specifications = M('Group_specifications');
                        $m_group_specifications->where(array('specifications_id' => $now_order['specifications_id']))->setDec('sale_count', $now_order['num']);
                    } elseif($ruleInfo) {
                        $v20_param = [
                            'ruleId' => $ruleInfo['rule_id'],
                            'combineId' => $ruleInfo['combine_id'],
                            'num' => $now_order['num'],
                            'type' => 2
                        ];
                        $res = invoke_v20_service('\app\group\model\service\GroupBookingAppointService/updateStock', $v20_param);
                    } else {
                        D('Group')->where(array('group_id' => $now_order['group_id']))->setDec('sale_count', $now_order['num']);
                    }
                }

                // 取消订单，回滚redis库存
                $v20_param = [
                    'groupId' => $now_order['group_id'],
                    'specificationsId' => $now_order['specifications_id'],
                    'ruleId' => $ruleInfo['rule_id'],
                    'combineId' => $ruleInfo['combine_id'],
                    'num' => $now_order['num']
                ];
                invoke_v20_service('\app\group\model\service\order\GroupOrderService/addRedisStock', $v20_param);

                //平台E卡退款
                if($now_order['use_ecard_price'] > 0 && $refund_ecard_pay>0){
                    if ($refund_ecard_pay_fee > 0) {
                        $msg = '过期自动退款 '.$now_order['order_name'].' 增加'.C('config.ecard_alias_name').'转退余额。'.'手续费：'.$refund_ecard_pay_fee;
                    } else {
                        $msg = '过期自动退款 '.$now_order['order_name'].' 增加'.C('config.ecard_alias_name').'转退余额';
                    }
                    $result = D('User')->add_money($now_order['uid'],$refund_ecard_pay,$msg);
                    if($result['error_code']){
                        $refund_detail['err_msg'] = $result['msg'];
                        $refund_detail['refund_id'] = $order_id;
                        $data_group_order['refund_detail'] = serialize($refund_detail);
                        D('Group_order')->data($data_group_order)->save();
                        throw new Exception($result['msg']);
                    } else {
                        $refund_detail['refund_id'] = $order_id;
                        $refund_detail['refund_ecard_pay'] = $refund_ecard_pay;
                        $refund_detail['refund_ecard_pay_fee'] = $refund_ecard_pay_fee;
                    }
                    $go_refund_param['msg'] = '平台'.C('config.ecard_alias_name').'转退余额过期自动退款成功';
                }
                //用户积分退款是回滚
                if ($now_order['score_used_count']>0 && $refund_score>0) {
                    if ($refund_score_fee > 0) {
                        $msg = '过期自动退款 '.$now_order['order_name'].C('config.score_name').'回滚。'.'手续费： ' .$refund_score_fee. C('config.score_name');
                    } else {
                        $msg = '过期自动退款 '.$now_order['order_name'].C('config.score_name').'回滚。';
                    }
                    $result = D('User')->add_score($now_order['uid'],$refund_score,$msg);
                    if($result['error_code']){
                        $refund_detail['err_msg'] = $result['msg'];
                        $refund_detail['refund_id'] = $order_id;
                        $data_group_order['refund_detail'] = serialize($refund_detail);
                        D('Group_order')->data($data_group_order)->save();
                        throw new Exception($result['msg']);
                    } else {
                        $refund_detail['refund_id'] = $order_id;
                        $refund_detail['refund_score'] = $refund_score;
                        $refund_detail['refund_score_fee'] = $refund_score_fee;
                    }
                    $go_refund_param['msg'] = $result['msg'];
                }
                //商家会员卡赠送余额需退款金额大于0 或者 商家会员卡余额需退款金额大于0
                if($refund_card_give_money>0 || $refund_merchant_balance>0){
                    if ($refund_card_give_fee > 0) {
                        $give_msg = '过期自动退款 '.$now_order['order_name'].' 增加赠送余额。'.'手续费： '.$refund_card_give_fee;
                    } else {
                        $give_msg = '过期自动退款 '.$now_order['order_name'].' 增加赠送余额';
                    }
                    if ($refund_merchant_balance_fee > 0) {
                        $msg = '过期自动退款 '.$now_order['order_name'].' 增加余额。'.'手续费：'.$refund_merchant_balance_fee;
                    } else {
                        $msg = '过期自动退款 '.$now_order['order_name'].' 增加余额';
                    }
                    $result = D('Card_new')->add_user_money($now_order['mer_id'],$now_order['uid'],$refund_merchant_balance,$refund_card_give_money,0,$msg,$give_msg);

                    if($result['error_code']){
                        $refund_detail['err_msg'] = $result['msg'];
                        $refund_detail['refund_id'] = $order_id;
                        $data_group_order['refund_detail'] = serialize($refund_detail);
                        D('Group_order')->data($data_group_order)->save();
                        throw new Exception($result['msg']);
                    } else {
                        $refund_detail['refund_id'] = $order_id;
                        $refund_detail['refund_card_give_money'] = $refund_card_give_money;
                        $refund_detail['refund_card_give_fee'] = $refund_card_give_fee;
                        $refund_detail['refund_merchant_balance'] = $refund_merchant_balance;
                        $refund_detail['refund_merchant_balance_fee'] = $refund_merchant_balance_fee;
                    }
                    $go_refund_param['msg'] = $result['msg'];
                }

                //平台余额退款
                if($now_order['balance_pay'] > 0 && $refund_balance_pay>0){
                    if ($refund_balance_pay_fee > 0) {
                        $msg = '过期自动退款 '.$now_order['order_name'].' 增加余额。'.'手续费：'.$refund_balance_pay_fee;
                    } else {
                        $msg = '过期自动退款 '.$now_order['order_name'].' 增加余额';
                    }
                    $result = D('User')->add_money($now_order['uid'],$refund_balance_pay,$msg);

                    if($result['error_code']){
                        $refund_detail['err_msg'] = $result['msg'];
                        $refund_detail['refund_id'] = $order_id;
                        $data_group_order['refund_detail'] = serialize($refund_detail);
                        D('Group_order')->data($data_group_order)->save();
                        throw new Exception($result['msg']);
                    } else {
                        $refund_detail['refund_id'] = $order_id;
                        $refund_detail['refund_balance_pay'] = $refund_balance_pay;
                        $refund_detail['refund_balance_pay_fee'] = $refund_balance_pay_fee;
                    }
                    $go_refund_param['msg'] = '平台余额过期自动退款成功';
                }
                // 整体退款流程走完后 修改团购订单状态为 已退款
                // 记录线下支付需退金额
                $refund_detail['refund_offline_money'] = $refund_offline_money;
                $refund_detail['refund_offline_money_fee'] = $refund_offline_money_fee;
                $data_group_order['order_id'] = $order_id;
                $data_group_order['refund_detail'] = serialize($refund_detail);
                $data_group_order['status'] = 3;
                $data_group_order['refund_money'] = $refund_money_total; // 以实际总金额计算出来的需要退款总金额
                if ($refund_fee_total > 0) {
                    $data_group_order['refund_fee'] = $refund_fee_total; // 退款总手续费  以实际支付总金额计算
                } else {
                    $data_group_order['refund_fee'] = 0;
                }

                D('Group_order')->data($data_group_order)->save();
                /*-----记录系统订单信息 订单取消退款成功 改写状态已退款 19/1/18---团购状态-已退款3-*/
                D("System_order")->save_order_status('group',$order_id,4,3);
                return true;
            } else {
                throw new Exception('过期自动取消订单失败！请重试。');
            }
        }

        //平台E卡退款
        if($now_order['use_ecard_price'] > 0 && $refund_ecard_pay>0){
            if ($refund_ecard_pay_fee > 0) {
                $msg = '过期自动退款 '.$now_order['order_name'].' 增加'.C('config.ecard_alias_name').'转退余额。'.'手续费：'.$refund_ecard_pay_fee;
            } else {
                $msg = '过期自动退款 '.$now_order['order_name'].' 增加'.C('config.ecard_alias_name').'转退余额';
            }
            $result = D('User')->add_money($now_order['uid'],$refund_ecard_pay,$msg);
            if($result['error_code']){
                $refund_detail['err_msg'] = $result['msg'];
                $refund_detail['refund_id'] = $order_id;
                $data_group_order['refund_detail'] = serialize($refund_detail);
                D('Group_order')->data($data_group_order)->save();
                throw new Exception($result['msg']);
            } else {
                $refund_detail['refund_id'] = $order_id;
                $refund_detail['refund_ecard_pay'] = $refund_ecard_pay;
                $refund_detail['refund_ecard_pay_fee'] = $refund_ecard_pay_fee;
            }
            $go_refund_param['msg'] = '平台'.C('config.ecard_alias_name').'转退余额过期自动退款成功';
        }
        //用户积分退款是回滚
        if ($now_order['score_used_count']>0 && $refund_score>0) {
            if ($refund_score_fee > 0) {
                $msg = '过期自动退款 '.$now_order['order_name'].C('config.score_name').'回滚。'.'手续费： ' .$refund_score_fee. C('config.score_name');
            } else {
                $msg = '过期自动退款 '.$now_order['order_name'].C('config.score_name').'回滚。';
            }
            $result = D('User')->add_score($now_order['uid'],$refund_score,$msg);
            if($result['error_code']){
                $refund_detail['err_msg'] = $result['msg'];
                $refund_detail['refund_id'] = $order_id;
                $data_group_order['refund_detail'] = serialize($refund_detail);
                D('Group_order')->data($data_group_order)->save();
                throw new Exception($result['msg']);
            } else {
                $refund_detail['refund_id'] = $order_id;
                $refund_detail['refund_score'] = $refund_score;
                $refund_detail['refund_score_fee'] = $refund_score_fee;
            }
            $go_refund_param['msg'] = $result['msg'];
        }

        //商家会员卡赠送余额需退款金额大于0 或者 商家会员卡余额需退款金额大于0
        if($refund_card_give_money>0 || $refund_merchant_balance>0){
            if ($refund_card_give_fee > 0) {
                $give_msg = '过期自动退款 '.$now_order['order_name'].' 增加赠送余额。'.'手续费： '.$refund_card_give_fee;
            } else {
                $give_msg = '过期自动退款 '.$now_order['order_name'].' 增加赠送余额';
            }
            if ($refund_merchant_balance_fee > 0) {
                $msg = '过期自动退款 '.$now_order['order_name'].' 增加余额。'.'手续费：'.$refund_merchant_balance_fee;
            } else {
                $msg = '过期自动退款 '.$now_order['order_name'].' 增加余额';
            }
            $result = D('Card_new')->add_user_money($now_order['mer_id'],$now_order['uid'],$refund_merchant_balance,$refund_card_give_money,0,$msg,$give_msg);

            if($result['error_code']){
                $refund_detail['err_msg'] = $result['msg'];
                $refund_detail['refund_id'] = $order_id;
                $data_group_order['refund_detail'] = serialize($refund_detail);
                D('Group_order')->data($data_group_order)->save();
                throw new Exception($result['msg']);
            } else {
                $refund_detail['refund_id'] = $order_id;
                $refund_detail['refund_card_give_money'] = $refund_card_give_money;
                $refund_detail['refund_card_give_fee'] = $refund_card_give_fee;
                $refund_detail['refund_merchant_balance'] = $refund_merchant_balance;
                $refund_detail['refund_merchant_balance_fee'] = $refund_merchant_balance_fee;
            }
            $go_refund_param['msg'] = $result['msg'];
        }

        //平台余额退款
        if($now_order['balance_pay'] > 0 && $refund_balance_pay>0){
            if ($refund_balance_pay_fee > 0) {
                $msg = '过期自动退款 '.$now_order['order_name'].' 增加余额。'.'手续费：'.$refund_balance_pay_fee;
            } else {
                $msg = '过期自动退款 '.$now_order['order_name'].' 增加余额';
            }
            $result = D('User')->add_money($now_order['uid'],$refund_balance_pay,$msg);

            if($result['error_code']){
                $refund_detail['err_msg'] = $result['msg'];
                $refund_detail['refund_id'] = $order_id;
                $data_group_order['refund_detail'] = serialize($refund_detail);
                D('Group_order')->data($data_group_order)->save();
                throw new Exception($result['msg']);
            } else {
                $refund_detail['refund_id'] = $order_id;
                $refund_detail['refund_balance_pay'] = $refund_balance_pay;
                $refund_detail['refund_balance_pay_fee'] = $refund_balance_pay_fee;
            }
            if ($result['error_code']) {
                throw new Exception($result['msg']);
            }
            $go_refund_param['msg'] = '平台余额过期自动退款成功';
        }

        //线上支付
        $refund_detail['refund_id'] = $order_id;
        if($now_order['payment_money'] > 0 && $refund_payment_money>0){

            $payment_money_order = M('Payment_money_order')->where(array('order_id'=>$now_order['order_id'],'order_type'=>'group','pay_type'=>$now_order['pay_type'],'paid_money'=>array('gt',0)))->find();

            if(empty($payment_money_order)){// 获取新版支付信息
                $pay_order_info =  M('Pay_order_info')->where(array('business_order_id'=>$now_order['order_id'],'business'=>'group','pay_type'=>$now_order['pay_type'],'paid_money'=>array('gt',0)))->find();
            }

            if($pay_order_info){// 新版支付退款

                $refundParam = [
                    'order_no' => $pay_order_info['orderid'],
                    'refund_money' => $refund_payment_money,
                ];
                $result = invoke_v20_service('\app\pay\model\service\PayService/refund',$refundParam);

                if(!$result || $result['data']['error_no']){
                    $refund_detail['err_msg'] = $result['data']['error_msg'];
                    $data_group_order['refund_detail'] = serialize($refund_detail);
                    D('Group_order')->data($data_group_order)->save();
                    throw new Exception($result['data']['error_msg']);
                }

                $refund_detail['refund_payment_money'] = $result['data']['retval']['refund_money'];
                $refund_detail['refund_id'] = $result['data']['retval']['refund_no'];

            }else{
                if($now_order['payment_money'] == $payment_money_order['payment_money']){
                    //如果是全退，那就把用户实际支付的钱一起退了吧。包括支付手续费
                    $now_order['payment_money'] = $payment_money_order['paid_money'];
                    if($payment_money_order['pay_rate'] > 0){
                        $now_order['payment_money'] = $now_order['payment_money']*$payment_money_order['pay_rate'];
                    }
                }

                if($payment_money_order && ($payment_money_order['pay_method'] == 'wft' || $payment_money_order['pay_method'] == 'paylinx' || $payment_money_order['pay_method'] == 'wepayez' || $payment_money_order['pay_method'] == 'weikebaba' || $payment_money_order['pay_method'] == 'xiaoma' || $payment_money_order['pay_method'] == 'nong')){
                    if($payment_money_order['pay_method'] == 'wft'){
                        $pay_method = 'wftpay';
                    }
                    if($payment_money_order['pay_method'] == 'paylinx'){
                        $pay_method = 'paylinx';
                    }
                    if($payment_money_order['pay_method'] == 'wepayez'){
                        $pay_method = 'wepayez';
                    }
                    if($payment_money_order['pay_method'] == 'weikebaba'){
                        $pay_method = 'weikebaba';
                    }
                    if($payment_money_order['pay_method'] == 'xiaoma'){
                        $pay_method = 'xiaoma';
                    }
                    if($payment_money_order['pay_method'] == 'nong'){
                        $pay_method = 'nong';
                    }
                    import('@.ORG.LowFeePay');
                    $lowfeepay = new LowFeePay($pay_method);
                    $now_order['order_type'] = 'group';
                    $now_order['orderNo'] = $payment_money_order['orderNo'];
                    if($now_order['mer_id'] && $payment_money_order['pay_method'] == 'wft'){
                        $mer_juhe = M('Merchant_juhe_config')->where(array('mer_id'=>$now_order['mer_id']))->find();
                        if(!empty($mer_juhe)){
                            $lowfeepay->userId =$mer_juhe['userid'];
                            $now_order['is_own'] =1 ;
                            $now_order['orderNo'] =  $now_order['orderNo'].'_1';
                        }

                    }elseif($now_order['mer_id'] && $payment_money_order['pay_method'] == 'weikebaba'){
                        $mer_juhe = M('Weikebaba')->where(array('mer_id'=>$now_order['mer_id']))->find();
                        if(!empty($mer_juhe)){
                            $lowfeepay->user_id = $mer_juhe['user_id'];
                            $lowfeepay->key = $mer_juhe['key'];
                            $now_order['is_own'] =1 ;
                            $now_order['orderNo'] =  $now_order['orderNo'];
                        }

                    }
                    $now_order['payment_refund_money'] = $refund_payment_money;
                    $go_refund_param= $lowfeepay->refund($now_order);
                }else if($payment_money_order['pay_method'] == 'chinaums'){
                    import("@.ORG.pay.Chinaums");
                    $pay = new Chinaums();
                    if($payment_money_order['is_mobile_pay'] == 3){
                        $mid = C("config.chinaums_mid_wxapp");
                    }
                    if($payment_money_order['is_mobile_pay'] == 2){
                        $mid = C("config.chinaums_mid_app");
                    }
                    if($payment_money_order['is_mobile_pay'] == 1){
                        $mid = C("config.chinaums_mid_wx");
                    }
                    $chinaums_merno = '';
                    if($now_order['mer_id']){
                        $now_merchant = M("Merchant")->where(['mer_id'=>$now_order['mer_id']])->find();
                        if(!empty($now_merchant['chinaums_merno'])){
                            $chinaums_merno = $now_merchant['chinaums_merno'];
                        }
                    }
                    $now_order['order_type'] = 'group';
                    $go_refund_param= $pay->refund($now_order['order_type'].'_'.$payment_money_order['orderid'], $payment_money_order['paid_money'], $payment_money_order['paid_money'], $payment_money_order['third_plat_data'], $chinaums_merno, $mid, $payment_money_order);
                }else if(C('config.open_juhepay')==1 &&( $now_order['pay_type']=='weixin' || $now_order['pay_type']=='alipay')){
                    // 这里线上支付退款 直接对应订单退款，退的是整体金额 （如果不退整个金额，这里的金额就会退还过多）
                    import('@.ORG.LowFeePay');
                    $lowfeepay = new LowFeePay('juhepay');
                    $now_order['orderNo'] = 'group_'.$now_order['orderid'];
                    if($now_order['mer_id']){
                        $mer_juhe = M('Merchant_juhe_config')->where(array('mer_id'=>$now_order['mer_id']))->find();
                        if(!empty($mer_juhe)){
                            $lowfeepay->userId =$mer_juhe['userid'];
                            $now_order['is_own'] =1 ;
                            $now_order['orderNo'] =  $now_order['orderNo'].'_1';
                        }

                    }
                    $now_order['payment_refund_money'] = $refund_payment_money;
                    $go_refund_param= $lowfeepay->refund($now_order);

                }else if(C('config.open_pufa_pay')==1 &&( $now_order['pay_type']=='weixin')){
                    // 这里线上支付退款 直接对应订单退款，退的是整体金额 （如果不退整个金额，这里的金额就会退还过多）
                    import('@.ORG.LowFeePay');
                    $lowfeepay = new LowFeePay('pufapay');
                    $now_order['orderNo'] = 'group_'.$now_order['orderid'];
                    $now_order['payment_refund_money'] = $refund_payment_money;
                    $go_refund_param= $lowfeepay->refund($now_order);
                }
                else if($payment_money_order['pay_method'] == 'chinaums'){
                    import('@.ORG.pay.Chinaums');
                    $pay = new Chinaums();

                    if ($payment_money_order['is_mobile_pay'] == 3) {
                        $mid = C("config.chinaums_mid_wxapp");
                    }
                    if ($payment_money_order['is_mobile_pay'] == 2) {
                        $mid = C("config.chinaums_mid_app");
                    }
                    if ($payment_money_order['is_mobile_pay'] == 1) {
                        $mid = C("config.chinaums_mid_wx");
                    }
                    $chinaums_merno = '';
                    if ($now_order['mer_id']) {
                        $now_merchant = M("Merchant")->where(['mer_id' => $now_order['mer_id']])->find();
                        if (!empty($now_merchant['chinaums_merno'])) {
                            $chinaums_merno = $now_merchant['chinaums_merno'];
                        }
                    }
                    $go_refund_param = $pay->refund($payment_money_order['order_type'] . '_' . $payment_money_order['orderid'], $refund_payment_money, $refund_payment_money, $payment_money_order['third_plat_data'], $chinaums_merno, $mid);

                }else if($payment_money_order && $payment_money_order['pay_method'] == 'scrcu'){
                    import("@.ORG.pay.Scrcu");
                    $pay = new scrcu();

                    $scrcu_mer_no = '';
                    if($now_order['mer_id']){
                        $now_merchant = M("Merchant")->where(['mer_id'=>$now_order['mer_id']])->find();
                        if(!empty($now_merchant['scrcu_mer_no'])){
                            $scrcu_mer_no = $now_merchant['scrcu_mer_no'];
                        }
                    }

                    $go_refund_param= $pay->refund($now_order['orderid'], $payment_money_order['paid_money'], $scrcu_mer_no);
                }else if($payment_money_order && strpos($payment_money_order['pay_type'],'xiantao') !== false){// 智慧仙桃支付退款
                    import('@.ORG.pay.XianTaoCard');
                    $pay = new XianTaoCard();

                    $now_user = D('User')->get_user($now_order['uid']);
                    try{
                        // 退款
                        $go_refund_param = $pay->refund($payment_money_order['orderNo'], $now_user['phone']);
                    } catch(Exception $e) {
                        throw new Exception($e->getMessage());
                    }
                }
                else {
                    if ($now_order['is_own'] && $now_order['pay_type'] != 'nmgpay') {
                        $pay_method = array();
                        $merchant_ownpay = D('Merchant_ownpay')->field('mer_id', true)->where(array('mer_id' => $now_order['mer_id']))->find();
                        foreach ($merchant_ownpay as $ownKey => $ownValue) {
                            $ownValueArr = unserialize($ownValue);
                            if ($ownValueArr['open']) {
                                $ownValueArr['is_own'] = true;
                                $pay_method[$ownKey] = array('name' => $this->getPayName($ownKey), 'config' => $ownValueArr);
                            }
                        }
                        $now_merchant = D('Merchant')->get_info($now_order['mer_id']);
                        if ($now_merchant['sub_mch_refund'] && C('config.open_sub_mchid') && $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0) {
                            $pay_method['weixin']['config']['pay_weixin_appid'] = C('config.pay_weixin_appid');
                            $pay_method['weixin']['config']['pay_weixin_appsecret'] = C('config.pay_weixin_appsecret');
                            $pay_method['weixin']['config']['pay_weixin_mchid'] = C('config.pay_weixin_sp_mchid');
                            $pay_method['weixin']['config']['pay_weixin_key'] = C('config.pay_weixin_sp_key');
                            $pay_method['weixin']['config']['sub_mch_id'] = $now_merchant['sub_mch_id'];
                            $pay_method['weixin']['config']['pay_weixin_client_cert'] = C('config.pay_weixin_sp_client_cert');
                            $pay_method['weixin']['config']['pay_weixin_client_key'] = C('config.pay_weixin_sp_client_key');
                            $pay_method['weixin']['config']['is_own'] = 2;
                        }
                    } else {
                        $pay_method = D('Config')->get_pay_method(0, 0, 1);
                    }

                    //小程序支付特殊处理
                    if ($now_order['is_mobile_pay'] == 3) {
                        $pay_method[$now_order['pay_type']]['config'] = array(
                            'pay_weixin_appid' => C('config.pay_wxapp_appid'),
                            'pay_weixin_key' => C('config.pay_wxapp_key'),
                            'pay_weixin_mchid' => C('config.pay_wxapp_mchid'),
                            'pay_weixin_appsecret' => C('config.pay_wxapp_appsecret'),
                        );

                        //自有支付特殊处理
                        if($now_order['is_own']){
                            $now_merchant_wxapp_ownpay = M('Weixin_app_bind')->where(array('other_id'=>$now_order['mer_id']))->find();
                            $pay_method[$now_order['pay_type']]['config'] = array(
                                'pay_weixin_appid' => $now_merchant_wxapp_ownpay['appid'],
                                'pay_weixin_key' => $now_merchant_wxapp_ownpay['wxpay_key'],
                                'pay_weixin_mchid' => $now_merchant_wxapp_ownpay['wxpay_merid'],
                                'pay_weixin_appsecret' => $now_merchant_wxapp_ownpay['appsecret'],
                                'is_own' => 1,
                            );
                            C('config.pay_weixin_client_cert', $now_merchant_wxapp_ownpay['public_key']);
                            C('config.pay_weixin_client_key', $now_merchant_wxapp_ownpay['private_key']);

                            $now_merchant = D('Merchant')->get_info($now_order['mer_id']);
                            if ($now_merchant['sub_mch_refund'] && C('config.open_sub_mchid') && $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0) {
                                $pay_method['weixin']['config']['pay_weixin_appid'] = C('config.pay_wxapp_appid');
                                $pay_method['weixin']['config']['pay_weixin_appsecret'] = C('config.pay_wxapp_appsecret');
                                $pay_method['weixin']['config']['pay_weixin_mchid'] = C('config.pay_weixin_sp_mchid');
                                $pay_method['weixin']['config']['pay_weixin_key'] = C('config.pay_weixin_sp_key');
                                $pay_method['weixin']['config']['sub_mch_id'] = $now_merchant['sub_mch_id'];
                                $pay_method['weixin']['config']['pay_weixin_client_cert'] = C('config.pay_weixin_sp_client_cert');
                                $pay_method['weixin']['config']['pay_weixin_client_key'] = C('config.pay_weixin_sp_client_key');
                                $pay_method['weixin']['config']['is_own'] = 2;
                            }
                        }
                    }

                    if (empty($pay_method)) {
                        throw new Exception('系统管理员没开启任一一种支付方式！');
                    }

                    $pay_class_name = ucfirst($now_order['pay_type']);
                    if ($pay_class_name == 'Alipay' && $now_order['is_mobile_pay'] == 2 && C('config.new_pay_alipay_app_public_key') != '' && C('config.new_pay_alipay_app_appid') != '' && C('config.new_pay_alipay_app_private_key') != '') {
                        $pay_class_name = 'AlipayApp';
                        $pay_method['alipay']['config']['new_pay_alipay_app_appid'] = C('config.new_pay_alipay_app_appid');
                        $pay_method['alipay']['config']['new_pay_alipay_app_private_key'] = C('config.new_pay_alipay_app_private_key');
                        $pay_method['alipay']['config']['new_pay_alipay_app_public_key'] = C('config.new_pay_alipay_app_public_key');
                    }
                    if (empty($pay_method[$now_order['pay_type']])) {
                        throw new Exception('您选择的支付方式不存在，请更新支付方式！');
                    }
                    $import_result = import('@.ORG.pay.' . $pay_class_name);
                    if (empty($import_result)) {
                        throw new Exception('系统管理员暂未开启该支付方式，请更换其他的支付方式');
                    }
                    $now_order['order_type'] = 'group';
                    if (!empty($now_order['orderid'])) {
                        $now_order['old_order_id'] = $now_order['order_id'];
                        $now_order['order_id'] = $now_order['orderid'];
                    }

                    $pay_class = new $pay_class_name($now_order, $refund_payment_money, $now_order['pay_type'], $pay_method[$now_order['pay_type']]['config'], $this->user_session, $now_order['is_mobile_pay']);
                    $go_refund_param = $pay_class->refund();
                }

                if ($now_order['pay_type']=='nmgpay' && ($go_refund_param['status'] == 'y' || $go_refund_param['info']=='成功')) {
                    $data_group_order['type'] = 'ok';
                } elseif ($now_order['pay_type']=='nmgpay'){
                    $go_refund_param['msg']=$go_refund_param['info'];
                }

                if(empty($go_refund_param['error']) && $go_refund_param['type'] == 'ok'){
                    $refund_detail['refund_payment_money'] = $refund_payment_money;
                    $refund_detail['refund_payment_money_fee'] = $refund_payment_money_fee;
                }else{
                    $refund_detail['err_msg']=$go_refund_param['msg'];
                    $data_group_order['refund_detail'] = serialize($refund_detail);
                    D('Group_order')->data($data_group_order)->save();
                    throw new Exception($go_refund_param['msg']);
                }
            }
        }

        // 整体退款流程走完后 修改团购订单状态为 已退款
        // 记录线下支付需退金额
        $data_group_order['refund_detail'] = serialize($refund_detail);
        $data_group_order['status'] = 3;


        if ($refund_fee_total > 0) {
            $now_order['order_type'] = 'group';
            $now_order['refund'] = true;
            $now_order['refund_money'] = $refund_fee_total;
            $now_order['desc']='团购过期自动退款手续费';
            D('SystemBill')->bill_method($now_order['is_own'],$now_order);
        } else {
            $data_group_order['refund_fee'] = 0;
        }

        if($total_pass_num>$unconsume_pass_num) {
            //退款 原来没有成团的拼团组人数减1
            if($now_group['pin_num']!=0){
                $now_group_start = D('Group_start')->get_group_start_by_order_id($order_id);
                if($now_group_start['status']==0){
                    D('Group_start')->buyer_refund_dec_by_orderid($order_id,$now_group_start['id']);
                }
                if($total_pass_num==$unconsume_pass_num){
                    $data_group_order['status']=3;
                }
            }else{
                $data_group_order['status'] = 6;
            }
        }else{
            $data_group_order['status']=3;
        }
        $go_refund_param['msg'] = "过期自动退款成功！";
        D('Group_order')->data($data_group_order)->save();

        $update_group = D('Group')->where(array('group_id' => $now_order['group_id']))->find();

        if($now_group['group_cate'] == 'booking_appoint') {// 场次预约
            $ruleInfo = M('Group_booking_appoint_order')->where(['order_id'=>$now_order['order_id']])->find();
        }
        if ($update_group['type'] == 3) {
            // 判断是否为团购规格退款
            $update_group_data = array();
            if ($now_order['specifications_id']) {
                $m_group_specifications = M('Group_specifications');
                $specifications_info = $m_group_specifications->where(array('specifications_id' => $now_order['specifications_id']))->field('count_num, sale_count')->find();
                $sale_count = $specifications_info['sale_count'] - $now_order['num'];
                $sale_count = $sale_count > 0 ? $sale_count : 0;
                $update_data = array('sale_count' => $sale_count);
                // 货物总数为零 即不限制
                if (($specifications_info['count_num'] > 0 && $sale_count < $specifications_info['count_num']) || $specifications_info['count_num'] == 0) {
                    $update_group_data['type'] = 1;
                }
                $m_group_specifications->where(array('specifications_id' => $now_order['specifications_id']))->save($update_data);
            } elseif($ruleInfo) {
                $v20_param = [
                    'ruleId' => $ruleInfo['rule_id'],
                    'combineId' => $ruleInfo['combine_id'],
                    'num' => $now_order['num'],
                    'type' => 2
                ];
                $res = invoke_v20_service('\app\group\model\service\GroupBookingAppointService/updateStock', $v20_param);
            } else {
                $sale_count = $update_group['sale_count'] - $now_order['num'];
                $sale_count = $sale_count > 0 ? $sale_count : 0;
                $update_group_data = array('sale_count' => $sale_count);
                if ($update_group['count_num'] > 0 && $sale_count < $update_group['count_num']) {
                    $update_group_data['type'] = 1;
                }
            }
            D('Group')->where(array('group_id' => $now_order['group_id']))->save($update_group_data);
        } else {
            //退款时销量回滚
            if ($now_order['specifications_id']) {
                $m_group_specifications = M('Group_specifications');
                $m_group_specifications->where(array('specifications_id' => $now_order['specifications_id'], 'sale_count' => array('egt', $now_order['num'])))->setDec('sale_count', $now_order['num']);
            } elseif($ruleInfo) {
                $v20_param = [
                    'ruleId' => $ruleInfo['rule_id'],
                    'combineId' => $ruleInfo['combine_id'],
                    'num' => $now_order['num'],
                    'type' => 2
                ];
                $res = invoke_v20_service('\app\group\model\service\GroupBookingAppointService/updateStock', $v20_param);
            } else {
                D('Group')->where(array('group_id' => $now_order['group_id'], 'sale_count' => array('egt', $now_order['num'])))->setDec('sale_count', $now_order['num']);
            }
        }

        // 取消订单，回滚redis库存
        $v20_param = [
            'groupId' => $now_order['group_id'],
            'specificationsId' => $now_order['specifications_id'],
            'ruleId' => $ruleInfo['rule_id'],
            'combineId' => $ruleInfo['combine_id'],
            'num' => $now_order['num']
        ];
        invoke_v20_service('\app\group\model\service\order\GroupOrderService/addRedisStock', $v20_param);

        //酒店等其他业务增加库存
        if(!empty($now_order['trade_info'])){
            $trade_info = unserialize($now_order['trade_info']);

            switch($trade_info['type']){
                case 'hotel':
                    $where['mer_id']=$now_group['mer_id'];
                    $where['cat_id']=$trade_info['cat_id'];
                    $where['_string']="stock_day >=".$trade_info['dep_time'] .' AND stock_day <'.$trade_info['end_time'];
                    M('Trade_hotel_stock')->where($where)->setInc('stock',$trade_info['num']);

                    break;
            }
        }

        //短信提醒
        $sms_data = array('mer_id' => $now_order['mer_id'], 'store_id' => 0, 'type' => 'group');
        if (C('config.sms_group_cancel_order') == 1 || C('config.sms_group_cancel_order') == 3) {
            $sms_data['uid'] = $now_order['uid'];
            $sms_data['mobile'] = $now_order['phone'];
            $sms_data['sendto'] = 'user';
            $sms_data['content'] = '您购买 '.$now_order['order_name'].'的订单(订单号：' . $now_order['order_id'] . '),在' . date('Y-m-d H:i:s') . '时已过期自动退款，欢迎再次光临！';
            Sms::sendSms($sms_data);
        }
        if (C('config.sms_group_cancel_order') == 2 || C('config.sms_group_cancel_order') == 3) {
            $merchant = D('Merchant')->where(array('mer_id' => $now_order['mer_id']))->find();
            $sms_data['uid'] = 0;
            $sms_data['mobile'] = $merchant['phone'];
            $sms_data['sendto'] = 'merchant';
            $sms_data['content'] = '顾客购买的' . $now_order['order_name'] . '的订单(订单号：' . $now_order['order_id'] . '),在' . date('Y-m-d H:i:s') . '时已过期自动退款！';
            Sms::sendSms($sms_data);
        }
        D('Group_order')->group_app_notice($order_id,5);
        D('Group_pass_relation')->change_refund_status($order_id);
        /*-----记录系统订单信息 订单取消退款成功 改写状态已退款 19/1/18--团购状态-已退款3--*/
        D("System_order")->save_order_status('group',$order_id,4,3);
        return true;
    }

    protected function autoVerify($now_order) {
        $database_group_order = D('Group_order');
        if ($now_order['is_head'] || $now_order['pin_fid'] > 0) {
            $group_start = D('Group_start')->get_group_start_by_order_id($now_order['order_id']);
            $group_start_status = $group_start['status'];
            if ($group_start && $group_start_status == 0) {
                return true;
            } elseif ($group_start && $group_start_status == 2) {
                return true;
            }
        }
        $condition_group_order['order_id'] = $now_order['order_id'];
        if (empty($now_order['third_id']) && $now_order['pay_type'] == 'offline') {
            $data_group_order['third_id'] = $now_order['order_id'];
        }
        $data_group_order['status']      = '1';
        $data_group_order['store_id']    = $now_order['store_id'];
        $data_group_order['use_time']    = $_SERVER['REQUEST_TIME'];
        $data_group_order['last_staff']  = '过期自动核销';
        $data_group_order['staff_name']  = '过期自动核销';
        $data_group_order['verify_time'] = $_SERVER['REQUEST_TIME'];
        $data_group_order['last_random'] = mt_rand(100000000,999999999);
        if ($database_group_order->where($condition_group_order)->data($data_group_order)->save()) {
            // 计算未验证的验证码数量
            $pass_num = $now_order['pass_num'] > 1 ? intval($now_order['pass_num']) : 1; // 一份团购 对应生成核销券 份数
            $consume_num = D('Group_pass_relation')->get_pass_num($now_order['order_id'], 1);
            $unconsume_pass_num = $now_order['num'] * $pass_num - $consume_num;
            $now_order['unconsume_pass_num'] = $unconsume_pass_num; // 未验证核销码数量

            $this->group_notice($now_order, 1);
            $staff_info = array(
                'staff_name'  => '过期自动核销',
                'verify_time' => $_SERVER['REQUEST_TIME'],
            );
            D('Group_pass_relation')->change_refund_status($now_order['order_id'], 1, $staff_info);

            //验证增加商家余额
            $now_order['order_type'] = 'group';
            $now_order['verify_all'] = 1;
            $now_order['store_id']   = 0;
            $now_order['desc'] = L_('用户购买X1记入收入', $now_order['order_name']);
            D('SystemBill')->bill_method($now_order['is_own'], $now_order);
            /*-----记录系统订单信息 验证消费 改写为已完成 19/1/18----*/
            D("System_order")->save_reg_order_info('group', $now_order['order_id'], array(), 2);
        }
        return true;
    }

    protected function autoFrozen($now_order) {
        $where_group_order['order_id'] = $now_order['order_id'];
        $data_group_order['status']    = 9;
        $data_group_order['last_time'] = time();
        D('Group_order')->where($where_group_order)->data($data_group_order)->save();
        D("System_order")->save_order_status('group',$now_order['order_id'], 5, 9);
        return true;
    }

    //	团购积分、短信、打印
    protected function group_notice($order, $verify_all) {
        $now_user = M('User')->where(array('uid' => $order['uid']))->find();
        $store = M('Merchant_store')->field(true)->where(['store_id' => $order['store_id']])->find();
        if($verify_all) {
            //积分
            if(C('config.add_score_by_percent')==0 && (C('config.open_score_discount')==0 || $order['score_discount_type']!=2) && empty(C('config.score_get_times'))){
                if($order['is_own'] && C('config.user_own_pay_get_score')!=1 ){
                    $order['payment_money'] = 0;
                }
                $score_get = D('Percent_rate')->get_user_add_score_times(C('config.score_get'),$order['uid']);

                D('User')->add_score(
                    $order['uid'],
                    (($order['payment_money'] + $order['balance_pay'])* $score_get),
                    L_('购买 X1 消费X2 获得X3',array('X1'=>$order['order_name'],'X2'=>floatval($order['total_money']) . C('config.Currency_txt'),'X3'=>C('config.score_name')))
                );
                D('Scroll_msg')->add_msg(
                    'group',
                    $now_user['uid'],
                    L_('用户X1于X2购买X3成功并过期自动消费获得',array('X1'=>str_replace_name($now_user['nickname']),'X2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),'X3'=>$order['order_name'],'X4'=>C('config.score_name')))
                );
            }
            D('Userinfo')->add_score(
                $order['uid'],
                $order['mer_id'],
                $order['total_money'],
                L_('购买 X1 消费X2 获得X3',array('X1'=>$order['order_name'],'X2'=>floatval($order['total_money']) . C('config.Currency_txt'),'X3'=>C('config.score_name')))
            );

            //商家推广分佣
            D('Merchant_spread')->add_spread_list($order, $now_user, 'group', L_('X1购买X2获得佣金',array('X1'=>$now_user['nickname'],'X2'=>C('config.group_alias_name'))));
        }

        if($now_user['openid']){

            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
            $href = C('config.site_url').'/wap.php?c=My&a=group_order&order_id='.$order['order_id'];
            $model->sendTempMsg('TM00017', array('href' => $href,
                'wecha_id' => $now_user['openid'],
                'first' => L_('X1团购提醒',C('config.group_alias_name')),
                'OrderSn' => $order['real_orderid'],
                'OrderStatus' =>L_("您购买的团购已于X1过期自动核销成功",date('Y-m-d H:i:s',time())),
                'remark' =>L_('点击查看详情')),
                $store['mer_id']);
        }

        //短信
        $sms_data = array('mer_id' => $order['mer_id'], 'store_id' => $store['store_id'], 'type' => 'group');
        if (C('config.sms_finish_order') == 1 || C('config.sms_finish_order') == 3) {
            $sms_data['uid'] = $order['uid'];
            $sms_data['mobile'] = $order['phone'];
            $sms_data['sendto'] = 'user';
            if(empty($order['res'])){
                $sms_data['content'] = L_('您购买 X1的订单(订单号：X2)已经过期自动完成了消费，如有任何疑意，请您及时联系本店，欢迎再次光临！',array('X1'=>$order['order_name'],'X2'=>$order['real_orderid']));
            }else{
                $sms_data['content'] = L_('您购买 X1的订单(消费码：X2)已经过期自动完成了消费，如有任何疑意，请您及时联系我们！',array('X1'=>$order['order_name'],'X2'=>$order['res']['group_pass']));
            }
            Sms::sendSms($sms_data);
        }
        D('Group_order')->group_app_notice($order['order_id'],7);

        if (C('config.sms_finish_order') == 2 || C('config.sms_finish_order') == 3) {
            $sms_data['uid'] = 0;
            $sms_data['mobile'] = $store['phone'];
            $sms_data['sendto'] = 'merchant';
            $sms_data['content'] = L_('顾客购买的X1的订单(订单号：X2),已经过期自动完成了消费！',array('X1'=>$order['order_name'],'X2'=>$order['real_orderid']));
            Sms::sendSms($sms_data);
        }
        //小票打印
        $printHaddle = new PrintHaddle();
        $printHaddle->printit($order['order_id'], 'group_order', 2);
    }

	protected function getPayName($label){
		$payName = array(
				'weixin' => '微信支付',
				'tenpay' => '财付通支付',
				'yeepay' => '银行卡支付(易宝支付)',
				'allinpay' => '银行卡支付(通联支付)',
				'chinabank' => '银行卡支付(网银在线)',
		);
		return $payName[$label];
	}
}
?>