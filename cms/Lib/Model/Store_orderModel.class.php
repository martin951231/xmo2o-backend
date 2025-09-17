<?php
class Store_orderModel extends Model
{
	
	public function get_order_by_id($uid, $order_id)
	{
		$condition = array();
		$condition['order_id'] = $order_id;
//		$uid && $condition['uid'] = $uid;
		return $this->field(true)->where($condition)->find();
	}

	public function get_pay_order($uid, $order_id, $is_web=false)
	{
		$now_order = $this->get_order_by_id($uid, $order_id);
		if(empty($now_order)){
			return array('error'=>1,'msg'=>L_('当前订单不存在！'));
		}
		if(!empty($now_order['paid'])){
			return array('error'=>1,'msg'=>L_('您已经支付过此订单！'),'url'=>str_replace('/source/','/',U('My/store_order_detail',array('order_id'=>$now_order['order_id']))));
		}

		$merchant_store = M("Merchant_store")->where(array('store_id' => $now_order['store_id'], 'mer_id' => $now_order['mer_id']))->find();
		$imgs =explode(';', $merchant_store['pic_info']);

		$order_info = array(
			'order_id'			  => $now_order['order_id'],
			'mer_id'			  => $now_order['mer_id'],
			'openid'			  => $now_order['openid'],
			'alipay_uid'		  => $now_order['alipay_uid'],
			'order_type'		  => 'store',
			'order_name'		  => $now_order['name'],
			'store_id'		      => $now_order['store_id'],
			'no_discount_money'   => $now_order['no_discount_money'],
			'order_num'			  => 0,
			'order_price'		  => floatval($now_order['price']),
			'order_total_money'   => floatval($now_order['price']),
			'extra_price'	      => floatval($now_order['extra_price']),
			'img'				  => file_domain().$imgs[0],
            'remark'		      => $now_order['remark'],
            'pay_merchant_coupon' => !$now_order['uid'] ? false : true, // 商家优惠券
            'pay_system_coupon'   => !$now_order['uid'] ? false : true, // 平台优惠券
		);
		return array('error' => 0,'order_info' => $order_info);
	}
	
	//手机端支付前订单处理
	public function wap_befor_pay($order_info, $now_coupon, $merchant_balance, $now_user,$ecard_info=null)
	{
		//去除微信优惠的金额
		$pay_money = $order_info['order_total_money'];

		if($merchant_balance['card_discount'] && $merchant_balance['card_discount'] != 0){
			$pay_money = sprintf("%.2f",($pay_money-$order_info['no_discount_money'])*$merchant_balance['card_discount']/10)+$order_info['no_discount_money'];
			//$pay_money = floor($pay_money*$merchant_balance['card_discount']*10)/100;
		}
		if(C('config.open_extra_price')==1){
			$user_score_use_percent=(float)C('config.user_score_use_percent');
			$order_info['order_extra_price'] = bcdiv($order_info['extra_price'],$user_score_use_percent,2);
			$pay_money +=$order_info['order_extra_price'];
		}
		$data_store_order['card_discount'] = $merchant_balance['card_discount'];

		if($pay_money<=0){
			$order_result = $this->wap_pay_save_order($order_info,$data_store_order);
			if ($order_result['error_code']) {
				return $order_result;
			}
			return $this->wap_after_pay_before($order_info);
		}

		//判断优惠券
		if ($now_coupon['card_price'] >0) {
			$data_store_order['card_id'] = $now_coupon['merc_id'];
			$data_store_order['card_price'] = round($now_coupon['card_price'] * 100)/100;
			if ($now_coupon['card_price'] >= $pay_money) {
				$order_result = $this->wap_pay_save_order($order_info, $data_store_order);
				if ($order_result['error_code']) {
					return $order_result;
				}
				return $this->wap_after_pay_before($order_info);
			}
			$pay_money -= $now_coupon['card_price'];
		}

		if($now_coupon['coupon_price']>0){
			$data_store_order['coupon_id'] = $now_coupon['sysc_id'];
			$data_store_order['coupon_price'] = $now_coupon['coupon_price'];
			if ($now_coupon['coupon_price'] >= $pay_money) {
				$order_result = $this->wap_pay_save_order($order_info, $data_store_order);
				if ($order_result['error_code']) {
					return $order_result;
				}
				return $this->wap_after_pay_before($order_info);
			}
			$pay_money -= $now_coupon['coupon_price'];
			$data_store_order['pay_money'] = $pay_money;
		}

		//使用平台E卡
		if(!empty($ecard_info) && $ecard_info['now_price']>0){
			$data_store_order['ecard_password'] = $ecard_info['card_password'];
			$data_store_order['use_ecard_price'] = $ecard_info['now_price'];
			if($ecard_info['now_price']>=$pay_money){
				$order_result = $this->wap_pay_save_order($order_info,$data_store_order);
				if($order_result['error_code']){
					return $order_result;
				}
				$order_info['pay_money'] = $pay_money;
				return $this->wap_after_pay_before($order_info);
			}
			$pay_money-=$ecard_info['now_price'];
			$data_store_order['pay_money'] = $pay_money;			
		}else{
			$data_store_order['ecard_password'] ='';
			$data_store_order['use_ecard_price'] =0;
		}

		if(!empty($order_info['score_deducte'])&&$order_info['use_score']){
			$data_store_order['score_used_count']  = $order_info['score_used_count'];
			$data_store_order['score_deducte']     = (float)$order_info['score_deducte'];
			if($order_info['score_deducte'] >= $pay_money){
				$data_store_order['score_deducte'] = $pay_money;
				//扣除积分
				$order_result = $this->wap_pay_save_order($order_info,$data_store_order);
				if($order_result['error_code']){
					return $order_result;
				}
				$order_info['pay_money'] = $pay_money;
				return $this->wap_after_pay_before($order_info);
			}


			$pay_money -= $order_info['score_deducte'];
			//$data_group_order['system_pay'] += $data_group_order['score_deducte'];
		}


		if(!empty($merchant_balance['card_money'])&&$order_info['use_merchant_balance']){
			if($merchant_balance['card_money'] >= $pay_money){
				$data_store_order['merchant_balance'] = $pay_money;
				$order_result = $this->wap_pay_save_order($order_info,$data_store_order);
				if($order_result['error_code']){
					return $order_result;
				}
				return $this->wap_after_pay_before($order_info);
			}else{
				$data_store_order['merchant_balance'] = $merchant_balance['card_money'];
			}
			$pay_money -= $merchant_balance['card_money'];
		}

		if(!empty($merchant_balance['card_give_money'])&&$order_info['use_merchant_balance']){
			if($merchant_balance['card_give_money'] >= $pay_money){
				$data_store_order['card_give_money'] = $pay_money;
				$order_result = $this->wap_pay_save_order($order_info,$data_store_order);
				if($order_result['error_code']){
					return $order_result;
				}
				return $this->wap_after_pay_before($order_info);
			}else{
				$data_store_order['card_give_money'] = $merchant_balance['card_give_money'];
			}
			$pay_money -= $merchant_balance['card_give_money'];
		}

        //使用企业预存款支付
        if ($order_info['qiye_balance'] == 1) {
            if (!D('Qiye')->checkTheSameQiye($now_user['uid'], $order_info['mer_id'])) {
                return array('error_code' => true, 'msg' => L_('同一企业商家和用户才能使用企业预存款'));
            }
            $staff = M('Qiye_staff')->where(['job_number' => $now_user['uid']])->find();
            if ($staff['money'] >= $pay_money) {
                $data_store_order['qiye_pay'] = $pay_money;
                $order_info['qiye_pay'] = $pay_money;
                $order_result = $this->wap_pay_save_order($order_info, $data_store_order);
                if ($order_result['error_code']) {
                    return $order_result;
                }
                $order_info['pay_money'] = $pay_money;
                return $this->wap_after_pay_before($order_info);
            } else {
                $data_store_order['qiye_pay'] = $staff['money'];
            }
            $pay_money -= $staff['money'];
            $pay_money = round($pay_money * 100) / 100;
            $pay_money = $pay_money > 0 ? $pay_money : 0;
        }


		//判断帐户余额
		if (!empty($now_user['now_money'])&&$order_info['use_balance']) {
			if ($now_user['now_money'] >= $pay_money) {
				$data_store_order['balance_pay'] = $pay_money;
				$order_result = $this->wap_pay_save_order($order_info, $data_store_order);
				if ($order_result['error_code']) {
					return $order_result;
				}
				return $this->wap_after_pay_before($order_info);
			} else {
				$data_store_order['balance_pay'] = $now_user['now_money'];
			}
			$pay_money -= $now_user['now_money'];
		}

        //================================一卡通支付金额================================
        if(!empty($_POST['internal_card_ids'])){
            if(is_string($_POST['internal_card_ids'])){//兼容下字符串
                $_POST['internal_card_ids'] = explode(',', $_POST['internal_card_ids']);
            }
            try {
                $cardPayInfo = D('InternalMembershipCard')->getInternalCardPay(
                    $_POST['internal_card_ids'],
                    $pay_money,
                    $order_info['store_id'],
                    $now_user['uid']
                );
                //校验卡余额
                $pay_money = $cardPayInfo['pay_money'];
                $save_data['internal_card_pay'] = $cardPayInfo['internal_card_pay'];
                M('store_order')->where(array('order_id' => $_POST['order_id']))->save($save_data);
            } catch (\Exception $e) {
                return array('error_code' => true, 'msg' => $e->getMessage());
            }
        }
        //================================一卡通支付金额结束================================

        if($pay_money < 0){
            $pay_money = 0;
        }

        if($pay_money == 0){
            $this->wap_pay_save_order($order_info, $data_store_order);
            return $this->wap_after_pay_before($order_info);
        }

		//在线支付
		$order_result = $this->wap_pay_save_order($order_info, $data_store_order);
		if ($order_result['error_code']) {
			return $order_result;
		}
		return array('error_code' => false, 'pay_money' => $pay_money);
	}
	
	//手机端支付前保存各种支付信息
	public function wap_pay_save_order($order_info, $data_store_order)
	{
		$data_store_order['card_id'] 			= !empty($data_store_order['card_id']) ? $data_store_order['card_id'] : 0;
		$data_store_order['merchant_balance'] 	= !empty($data_store_order['merchant_balance']) ? $data_store_order['merchant_balance'] : 0;
		$data_store_order['card_give_money'] 	= !empty($data_store_order['card_give_money']) ? $data_store_order['card_give_money'] : 0;
		$data_store_order['card_discount'] 	= !empty($data_store_order['card_discount']) ? $data_store_order['card_discount'] : 0;
		$data_store_order['balance_pay']	 	= !empty($data_store_order['balance_pay']) ? $data_store_order['balance_pay'] : 0;
		$data_store_order['score_used_count']  	= !empty($data_store_order['score_used_count'])?$data_store_order['score_used_count']:0;
		$data_store_order['score_deducte']     	= !empty($data_store_order['score_deducte'])?(float)$data_store_order['score_deducte']:0;
		$data_store_order['score_discount_type']      = !empty($order_info['score_discount_type']) ? $order_info['score_discount_type'] : 0;
		$data_store_order['dateline'] = $_SERVER['REQUEST_TIME'];
		$data_store_order['card_price'] =  !empty($data_store_order['card_price']) ? $data_store_order['card_price'] : 0;
		$condition_store_order['order_id'] = $order_info['order_id'];
		$result = $this->where($condition_store_order)->data($data_store_order)->save();
		if ($result) {
			/*------更新系统订单信息 2019/1/14--------*/
			$data=$data_store_order;
			$data['from_plat']=$order_info['from_plat'];
			// 订单改变之后调用
			$this->after_store_change($order_info['order_id'],'store',2);

			return array('error_code' => false, 'msg' => L_('保存订单成功！'));
		} else {
			return array('error_code' => true, 'msg' => L_('保存订单失败！请重试或联系管理员。1'));
		}
	}
	//如果无需调用在线支付，使用此方法即可。
	public function wap_after_pay_before($order_info) 
	{
		$order_param = array(
				'order_id' => $order_info['order_id'],
				'pay_type' => '',
				'third_id' => '',
				'is_mobile' => $order_info['is_mobile'],
				'order_type' => 'store',
			);
			$result_after_pay = $this->after_pay($order_param);
			if ($result_after_pay['error']) {
				return array('error_code' => true,'msg'=>$result_after_pay['msg']);
			}
			if(C('config.pay_success_jump_to_homepage')){
				return array('error_code'=>false,'msg'=>L_('支付成功！'),'url'=>C('config.site_url') .  D('House_village')->base_url . 'pages/plat_menu/index');
			}else{
				return array('error_code'=>false,'msg'=>L_('支付成功！'),'url'=>str_replace('/source/','/',U('My/store_order_detail',array('order_id'=>$order_info['order_id']))));
			}
	}
	
	//支付之后
	public function after_pay($order_param)
	{
        fdump_api($order_param,'pay/store_order_after_pay', 1);
		if($order_param['pay_type']!=''){
			$condition_order['orderid'] = $order_param['order_id'];
		}else{
			$condition_order['order_id'] = $order_param['order_id'];
		}
        $lockRs = setLock('pay_store_order_'.$order_param['order_id'], 10);
        if ($lockRs['error_no']) {
            fdump_api([$order_param['order_id'], $lockRs], 'pay/store_order_after_pay', 1);
            return array('error' => 1, 'msg' => '支付正在处理中！');
        }
		$now_order = $this->field(true)->where($condition_order)->find();
		if (empty($now_order)) {
			return array('error' => 1, 'msg' => L_('当前订单不存在！'));
		} elseif($now_order['paid'] == 1) {
			return array('error' => 1, 'msg' => L_('该订单已付款！'), 'url' => U('My/store_order_detail',array('order_id'=>$now_order['order_id'])));
		} else {
            if ($now_order['business_type'] == 'foodshop') {
                $now_food_order = D('Foodshop_order')->field('order_id, status, book_price')->where(array('order_id' => $now_order['business_id']))->find();
                if ($now_food_order && $now_food_order['status'] > 2) {
                    return array(
                        'error' => 1,
                        'msg' => L_('该订单已付款！'),
                        'url' => U('My/store_order_detail', array('order_id' => $now_order['order_id']))
                    );
                }
            }
		    
			//得到当前用户信息，不将session作为调用值，因为可能会失效或错误。
			$now_user = D('User')->get_user($now_order['uid']);
			if ($now_order['uid']&&empty($now_user)) {
				return array('error' => 1, 'msg' => L_('没有查找到此订单归属的用户，请联系管理员！'));
			}
			if($now_order['card_id']){
				$now_coupon = D('Card_new_coupon')->get_coupon_by_id($now_order['card_id']);
				if(empty($now_coupon)){
					return $this->wap_after_pay_error($now_order,$order_param,L_('您选择的优惠券不存在！'));
				}
			}

			//判断平台优惠券
			if($now_order['coupon_id']){
				$now_coupon = D('System_coupon')->get_coupon_by_id($now_order['coupon_id']);
				if(empty($now_coupon)){
					return $this->wap_after_pay_error($now_order,$order_param,L_('您选择的优惠券不存在！'));
				}
			}

			//如果使用了平台E卡
			if(!empty($now_order['ecard_password'])){
				D('Ecard_coupon')->add_ecard_log($now_order,'store');
			}

			$score_used_count= getFormatNumber($now_order['score_used_count']);
			if($now_user && $score_used_count>0 && $now_user['score_count']<$score_used_count){
				return array('error'=>1,'msg'=>L_('保存订单失败！请重试或联系管理员。'));
			}
			if($score_used_count>0){
				$use_result = D('User')->user_score($now_order['uid'],$score_used_count,L_("购买 X1 扣除X2",array("X1" => $now_order['order_name'],"X2" => C('config.score_name'))));
				if($use_result['error_code']){
					return array('error'=>1,'msg'=>$use_result['msg']);
				}
			}
			
			//判断会员卡余额
			$merchant_balance = floatval($now_order['merchant_balance']);
			if($merchant_balance){
				$user_merchant_balance = D('Card_new')->get_card_by_uid_and_mer_id($now_order['uid'],$now_order['mer_id']);
				if($user_merchant_balance['card_money'] < $merchant_balance){
					return $this->wap_after_pay_error($now_order,$order_param,L_('您的会员卡余额不够此次支付！'));
				}
			}

			$card_give_money = floatval($now_order['card_give_money']);
			if($card_give_money){
				$user_merchant_balance = D('Card_new')->get_card_by_uid_and_mer_id($now_order['uid'],$now_order['mer_id']);
				if($user_merchant_balance['card_money_give'] < $card_give_money){
					return $this->wap_after_pay_error($now_order,$order_param,L_('您的会员卡余额不够此次支付！'));
				}
			}
			//判断帐户余额
			$balance_pay = floatval($now_order['balance_pay']);
			if($balance_pay){
				if($now_user['now_money'] < $balance_pay){
					return $this->wap_after_pay_error($now_order, $order_param, L_('您的帐户余额不够此次支付！'));
				}
			}

            //判断一卡通余额, 扣除余额
            $card_pay_info = json_decode($now_order['internal_card_pay'], true);

            $card = M('internal_membership_card');
            $internal_card_log = M('internal_membership_card_log');
            $time = time();
            $internal_card_pay = 0;
            if (!empty($card_pay_info)){
                foreach($card_pay_info as $card_id => $card_pay_money){
                    $card_info = $card->where(array('card_id' => $card_id))->find();
                    $card_info['balance_money'] = round($card_info['balance_money'] * 100) / 100;
                    if($card_info['balance_money'] < $card_pay_money){
                        return $this->wap_after_pay_error($now_order,$order_param,L_('您的会员卡'. $card_id .'余额不足'));
                    }
                    $save_data['balance_money'] = $card_info['balance_money'] - $card_pay_money;
                    $save_data['last_time'] = $time;
                    if(!$card->where(array('card_id' => $card_id))->save($save_data)){
                        return $this->wap_after_pay_error($now_order,$order_param,L_('会员卡'. $card_id .'支付失败!'));
                    }
                    $condition_card_log['card_id'] = $card_id;
                    $condition_card_log['operation_type'] = 2; //交易
                    $condition_card_log['before_balance'] = $card_info['balance_money'];
                    $condition_card_log['after_balance'] = $save_data['balance_money'];
                    $condition_card_log['money'] = $card_pay_money;
                    $condition_card_log['business_type'] = 'store';
                    if($now_order['from_plat'] == 2){
                        $condition_card_log['business_type'] = 'cash';
                    }
                    $businessType = D('InternalMembershipCard')->getBusinessTypeText($condition_card_log['business_type']);
                    $condition_card_log['uid'] = $card_info['uid'];
                    $condition_card_log['order_id'] = $now_order['orderid'];
                    $condition_card_log['describe'] = $businessType.', 扣除余额'. $card_pay_money .'元, 订单编号'.$now_order['orderid'];
                    $condition_card_log['mer_id'] = $now_order['mer_id'];
                    $condition_card_log['store_id'] = $now_order['store_id'];
                    $condition_card_log['create_time'] = $time;
                    $internal_card_log->add($condition_card_log);
                    $internal_card_pay += $card_pay_money;
                    D('Internal_card')->send_wx_msg($card_id, '-'.getFormatNumber($card_pay_money), $businessType);
                }
            }

            //如果是充值订单, 充值一卡通
            if ($now_order['from_plat'] == 4){
                $money = $now_order['total_price'];
                $card_id = $now_order['remark'];
                $order_id = $now_order['orderid'];
                $res = D('Internal_card')->recharge($card_id, $money, $order_id);
                if ($res['error_code']){
                    return array('error'=>1,'msg'=>$res['msg']);
                }
            }

			//如果使用了商家优惠券
			if($now_order['card_id']){
				$use_result = D('Card_new_coupon')->user_coupon($now_order['card_id'],$now_order['order_id'],$order_param['order_type'],$now_order['mer_id'],$now_order['uid']);
				if($use_result['error_code']){
					return array('error'=>1,'msg'=>$use_result['msg']);
				}
			}

			//如果使用了平台优惠券
			if($now_order['coupon_id']){
				$use_result = D('System_coupon')->user_coupon($now_order['coupon_id'],$now_order['order_id'],$order_param['order_type'],$now_order['mer_id'],$now_order['uid']);
				if($use_result['error_code']){
					return array('error'=>1,'msg'=>$use_result['msg']);
				}
			}
			if($merchant_balance){
				$use_result = D('Card_new')->use_money($now_order['uid'],$now_order['mer_id'],$merchant_balance,L_("购买 X1 扣除会员卡余额",array("X1" => $now_order['order_name'])));
				if($use_result['error_code']){
					return array('error'=>1,'msg'=>$use_result['msg']);
				}
			}

			if($card_give_money){
				$use_result = D('Card_new')->use_give_money($now_order['uid'],$now_order['mer_id'],$card_give_money,L_("购买 X1 扣除会员卡赠送余额",array("X1" => $now_order['order_name'])));
				if($use_result['error_code']){
					return array('error'=>1,'msg'=>$use_result['msg']);
				}
			}

			//如果用户使用了余额支付，则扣除相应的金额。
			if(!empty($balance_pay)){
				$use_result = D('User')->user_money($now_order['uid'],$balance_pay,L_("购买 X1 扣除余额",array("X1" => $now_order['order_name'])));
				if($use_result['error_code']){
					return array('error'=>1,'msg'=>$use_result['msg']);
				}
			}

            //扣除企业预存款
            $qiye_balance = floatval($now_order['qiye_pay']);
            if (!empty($qiye_balance)) {
                $qiye_result = D('User')->use_qiye_money($now_order['uid'], $qiye_balance, $now_order['order_id'], L_("购买X1商品，扣除余额，订单编号X2", array("X1" => $now_order['order_name'], "X2" => $now_order['real_orderid'])), 'store', false);
                if ($qiye_result['error_code']) {
                    return array('error' => 1, 'msg' => $use_result['msg']);
                }
                $data_shop_order['qiye_settlement_ratio'] = $qiye_result['retval']['qiye_settlement_ratio'];
                $data_shop_order['qiye_settlement_money'] = $qiye_result['retval']['qiye_settlement_money'];
            }

			//$condition_store_order['order_id'] = $order_param['order_id'];
			
			$data_store_order['pay_time'] = isset($order_param['pay_time']) && $order_param['pay_time'] ? strtotime($order_param['pay_time']) : $_SERVER['REQUEST_TIME'];
			$data_store_order['payment_money'] = floatval($order_param['pay_money']);
			$data_store_order['pay_type'] = $order_param['pay_type'];
			$data_store_order['third_id'] = $order_param['third_id'];
			$data_store_order['is_own'] = !empty($order_param['sub_mch_id'])?2:floatval($order_param['is_own']);
			$data_store_order['paid'] = 1;
			if($this->where($condition_order)->data($data_store_order)->save()){
                   //商家设置了消费一元百分比获得积分，优惠买单支付成功以后，用户增加积分
                // 防止会有取不到最新数据的情况发生
				$update_order = array_merge($now_order, $data_store_order);

				$store = D('Merchant_store')->field(true)->where(array('store_id' => $now_order['store_id']))->find();

				D('Scroll_msg')->add_msg('store',$now_user['uid'],L_("用户X1于X2使用了X3",array("X1" => str_replace_name($now_user['nickname']),"X2" => date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),"X3" => C('config.cash_alias_name'))));
				$get_score_num = 0;
                $score_params = [
                    'mer_id' => $now_order['mer_id'],
                    'store_id' => $now_order['store_id'],
                    'score_type' => 0,
                    'order_id' => $now_order['order_id'],
                    'order_type' => 'store',
                ];
				if(C('config.open_extra_price')==1){
					$order = $now_order;
					$order['order_type'] ='store';
					$score = D('Percent_rate')->get_extra_money($order);
					if($score>0){
						$addScoreRs = D('User')->add_score($order['uid'], floor($score),L_("在 X1 中使用X2支付了X3 +X4 获得X5",array("X1" => $store['name'],"X2" => C('config.cash_alias_name'),"X3" => floatval($now_order['balance_pay']+$now_order['merchant_balance']+$order_param['pay_money']) . C('config.Currency_txt'),"X4" => $now_order['score_used_count'].C('config.extra_price_alias_name'),"X5" => C('config.extra_price_alias_name'))), 0, $score_params);
						D('Scroll_msg')->add_msg('store',$now_user['uid'],L_("用户X1于X2使用了X3成功获得X4",array("X1" => str_replace_name($now_user['nickname']),"X2" => date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),"X3" => C('config.cash_alias_name'),"X4" => C('config.score_name'))));
						$get_score_num = floor($score);
					}
				}else{
					$score_get_times = C('config.score_get_times');
					//获得积分按抽成算
					if(C('config.add_score_by_system_commission')==0 && C('config.add_score_by_percent')==0 && (C('config.open_score_discount')==0 || $update_order['score_discount_type']!=2) && ( $score_get_times==0 || empty($score_get_times))){
                        //商家设置了消费一元百分比获得积分，优惠买单支付成功以后，用户增加积分
                        $database_merchant = D('Merchant');
						$scoreDesc = L_("在X1中使用X2支付成功获得X3",['X1'=>$store['name'],'X2'=>C('config.cash_alias_name'),'X3'=>C('config.score_name')]);
                        $old = $database_merchant->where(['mer_id'=>$now_order['mer_id']])->find();
                        $pay_money=floatval($now_order['balance_pay']+$now_order['merchant_balance']+$order_param['pay_money']);
                        if(C('config.pay_in_store')==1 && C('config.store_score_get_percent')==1 && $pay_money>=1 && C('config.store_score_percent')>0){//百分比
                            $addScoreRs = D('User')->add_score($now_order['uid'], $pay_money*(C('config.store_score_percent')/100),$scoreDesc, 0, $score_params);
                        }elseif (C('config.pay_in_store')==1 && C('config.store_score_get_percent')==0 && $pay_money>=1 && C('config.user_store_score_get')>0){//积分
                            $addScoreRs = D('User')->add_score($now_order['uid'], $pay_money*C('config.user_store_score_get'),$scoreDesc, 0, $score_params);
                        }elseif($old['score_get_percent']>0 && $pay_money>=1){
                            $addScoreRs = D('User')->add_score($now_order['uid'], $pay_money*($old['score_get_percent']/100),$scoreDesc, 0, $score_params);
                        }else{
							if(C('config.store_score_get_percent')==1){
								$score_get = C('config.store_score_percent')/100;
							}else{
								$score_get = C('config.user_store_score_get');
							}
							if($data_store_order['is_own']&& C('config.user_own_pay_get_score')!=1){
								$data_store_order['payment_money']= 0;
							}
								$now_merchant = D('Merchant')->get_info($now_order['mer_id']);
								if($now_merchant['score_get_percent']>=0){
									$score_get = $now_merchant['score_get_percent']/100;
								}
							$score_get = D('Percent_rate')->get_user_add_score_times($score_get,$now_order['uid']);
							$money = $now_order['balance_pay']+$now_order['merchant_balance']+$order_param['pay_money'];
							$addScoreRs = D('User')->add_score($now_order['uid'], (($data_store_order['payment_money']+$update_order['balance_pay'] )* $score_get), L_("在X1 中使用X2支付了X3 获得X4",array("X1" => $store['name'],"X2" => C('config.cash_alias_name'),"X3" => floatval($money) . C('config.Currency_txt'),"X4" => C('config.score_name'))), 0, $score_params);
							$this->where(array('order_id'=>$now_order['order_id']))->setField('score_give',round(($data_store_order['payment_money']+$update_order['balance_pay'] )* $score_get));
							$get_score_num = round(($data_store_order['payment_money']+$update_order['balance_pay'] )* $score_get);
						}
					}else{
                        //商家设置了消费一元百分比获得积分，优惠买单支付成功以后，用户增加积分
                        $database_merchant = D('Merchant');
						$scoreDesc = L_("在X1中使用X2支付成功获得X3",['X1'=>$store['name'],'X2'=>C('config.cash_alias_name'),'X3'=>C('config.score_name')]);
                        $old = $database_merchant->where(['mer_id'=>$now_order['mer_id']])->find();
                        $pay_money=floatval($now_order['balance_pay']+$now_order['merchant_balance']+$order_param['pay_money']);
                        if(C('config.pay_in_store')==1 && C('config.store_score_get_percent')==1 && $pay_money>=1 && C('config.store_score_percent')>0){//百分比
                            $addScoreRs = D('User')->add_score($now_order['uid'], $pay_money*(C('config.store_score_percent')/100),$scoreDesc, 0, $score_params);
                        }elseif (C('config.pay_in_store')==1 && C('config.store_score_get_percent')==0 && $pay_money>=1 && C('config.user_store_score_get')>0){//积分
                            $addScoreRs = D('User')->add_score($now_order['uid'], $pay_money*C('config.user_store_score_get'),$scoreDesc, 0, $score_params);
                        }elseif($old['score_get_percent']>0 && $pay_money>=1){
                            $addScoreRs = D('User')->add_score($now_order['uid'], $pay_money*($old['score_get_percent']/100),$scoreDesc, 0, $score_params);
                        }
                        $get_score_num = $pay_money*($old['score_get_percent']/100);
                    }					
					D('Userinfo')->add_score($now_order['uid'], $now_order['mer_id'], $now_order['price'], L_("在 X1 中使用X2支付了X3 获得积分",array("X1" => $store['name'],"X2" => C('config.cash_alias_name'),"X3" => floatval($now_order['price']) . C('config.Currency_txt'))));
				}
				if(C('config.open_score_pay_back') && C('config.system_take_spread_percent')>0 ) {
					$spread_total_money = $update_order['balance_pay']+$update_order['payment_money']+$update_order['merchant_balance']+$update_order['coupon_price']+$update_order['score_deducte'];
					$percent = D('Percent_rate')->get_percent($now_order['mer_id'],'store',$spread_total_money);
					$spread_total_money = $spread_total_money*$percent/100;
				}else{
					$spread_total_money = $balance_pay + $data_store_order['payment_money'];

				}
				
				if(C('config.add_spread_by_system_commission')){// 根据平台抽成获得佣金	
					// 获取平台抽成金额								
					$update_order['get_system_take_money'] = true;
					$update_order['order_type'] = 'store';
                    $spread_total_money =  D('SystemBill')->bill_method($update_order['is_own'],$update_order);	
					$update_order['get_system_take_money'] = false;
				}
				fdump($spread_total_money, 'spread_total_money');
				
				$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
				//分佣
                $userMod = new UserModel();
				if(!empty($now_user['openid'])&&C('config.open_user_spread') && (!C('config.open_spread_appoint_user') || ($now_user['can_spread']==1 && !C('config.open_spread_only_once'))) && (C('config.spread_money_limit')==0 || C('config.spread_money_limit')<=$spread_total_money)&&empty(C('config.open_spread_rand'))){
					//上级分享佣金
					if($now_order['from_plat']==1){
						$spread_rate = D('Percent_rate')->get_user_spread_rate($now_order['mer_id'],'store',0,$now_order);
						$spread_type = 'store';
					}else{
						$spread_rate = D('Percent_rate')->get_user_spread_rate($now_order['mer_id'],'cash',0,$now_order);
						$spread_type = 'cash';
					}
					fdump($spread_rate, 'spread_total_money',1);

					$open_extra_price = C('config.open_extra_price');
					$spread_users[]=$now_user['uid'];
					$spread_where['_string'] = "uid = '{$now_user['uid']}'";
					$now_user_spread = D('User_spread')->where($spread_where)->find();

                    //判断推广用户是否可以获得佣金
                    if ($now_user_spread && !($userMod->canSpread($now_user_spread['spread_uid']))) {
                        $now_user_spread = [];
                    }

					if($data_store_order['is_own']  && C('config.own_pay_spread')==0){
						$data_store_order['payment_money']=0;
					}
					$href = C('config.site_url') . '/wap.php?g=Wap&c=My&a=spread_list&status=-1';
					if(!empty($now_user_spread)){
						$spread_user = D('User')->get_user($now_user_spread['spread_uid']);
						//$user_spread_rate = $update_group['spread_rate'] > 0 ? $update_group['spread_rate'] : C('config.user_spread_rate');
						$user_spread_rate = $spread_rate['first_rate'];
						if($spread_user && $user_spread_rate&&!in_array($spread_user['uid'],$spread_users)){
							$spread_money = round($spread_total_money * $user_spread_rate / 100, 2);
							$spread_data = array(
									'uid'=>$spread_user['uid'],
									'spread_uid'=>0,
									'get_uid'=>$now_user['uid'],
									'money'=>$spread_money,
									'order_type'=>$spread_type,
									'order_id'=>$now_order['order_id'],
									'third_id'=>$now_order['store_id'],
									'add_time'=>$_SERVER['REQUEST_TIME']
							);
							if($spread_user['spread_change_uid']!=0){
								$spread_data['change_uid'] = 	$spread_user['spread_change_uid'];
							}
                            // 处理长订单id
                            if ($now_order['orderid']) {
                                $spread_data['real_orderid'] = $now_order['orderid'];
                            }
							D('User_spread_list')->data($spread_data)->add();
							if($now_user_spread['uid']){
								$buy_user = D('User')->get_user($now_user_spread['uid']);
							}else{
								$buy_user = D('User')->get_user($now_user_spread['openid'],'openid');
							}
							if($spread_money>0){
								if($open_extra_price){
									$money_name = C('config.extra_price_alias_name');
								}else{
									$money_name = L_('佣金');
								}
								$model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $spread_user['openid'], 'first' => L_("X1通过您的分享购买了X2，验证消费后您将获得X3。",array("X1" => $buy_user['nickname'],"X2" => C('config.cash_alias_name'),"X3" => $money_name)) , 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $now_order['mer_id']);
							}
							$spread_users[]=$spread_user['uid'];
							// D('User')->add_money($spread_user['uid'],$spread_money,'推广用户 '.$now_user['nickname'].' 购买 ID为 '.$now_order['group_id'].' 的商品 获得的佣金');
						}

						//第二级分享佣金
                        $spread_where['_string'] = "uid = '{$spread_user['uid']}' ";
						$second_user_spread = D('User_spread')->where($spread_where )->find();

                        //判断推广用户是否可以获得佣金
                        if ($second_user_spread && !($userMod->canSpread($second_user_spread['spread_uid']))) {
                            $second_user_spread = [];
                        }

						if(!empty($second_user_spread)&&!$open_extra_price) {
							$second_user = D('User')->get_user($second_user_spread['spread_uid']);

							$sub_user_spread_rate = $spread_rate['second_rate'];
							if ($second_user && $sub_user_spread_rate&&!in_array($second_user['uid'],$spread_users)) {
								$spread_money = round($spread_total_money * $sub_user_spread_rate / 100, 2);
								$spread_sec_data =array('uid' => $second_user['uid'], 'spread_uid' => $spread_user['uid'], 'get_uid' => $now_user['uid'], 'money' => $spread_money, 'order_type' => $spread_type, 'order_id' => $now_order['order_id'], 'third_id' => $now_order['store_id'], 'add_time' => $_SERVER['REQUEST_TIME']);
								if($second_user['spread_change_uid']!=0){
									$spread_sec_data['change_uid'] = 	$second_user['spread_change_uid'];
								}
                                // 处理长订单id
                                if ($now_order['orderid']) {
                                    $spread_sec_data['real_orderid'] = $now_order['orderid'];
                                }
								D('User_spread_list')->data($spread_sec_data)->add();
                                if($second_user_spread['openid']!=''){
                                    $sec_where['_string'] = "openid = '{$second_user_spread['openid']}' OR wxapp_openid = '{$second_user_spread['openid']}' ";
                                    $sec_user = M('User')->where($sec_where)->find();
                                }
								if($spread_money>0) {
									$model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $second_user['openid'], 'first' => L_("X1的子用户X2通过您的分享购买了X3，验证消费后您将获得佣金。",array("X1" => $sec_user['nickname'],"X2" => $buy_user['nickname'],"X3" => C('config.cash_alias_name'))) , 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')),  $now_order['mer_id']);
								}
								$spread_users[]=$second_user['uid'];
								// D('User')->add_money($first_spread_user['uid'],$spread_money,'子用户推广用户 '.$now_user['nickname'].' 购买ID为 '.$now_order['group_id'].' 的商品 获得的佣金');
							}

							//顶级分享佣金
                            
                            $spread_where['_string'] = "uid = '{$second_user['uid']}' ";
                            
							$first_user_spread = D('User_spread')->where(	$spread_where)->find();

                            //判断推广用户是否可以获得佣金
                            if ($first_user_spread && !($userMod->canSpread($first_user_spread['spread_uid']))) {
                                $first_user_spread = [];
                            }

							if (!empty($first_user_spread) && C('config.user_third_level_spread')&&!$open_extra_price) {
								$first_spread_user = D('User')->get_user($first_user_spread['spread_uid']);
								$sub_user_spread_rate = $spread_rate['third_rate'];
								if ($first_spread_user && $sub_user_spread_rate&&!in_array($first_spread_user['uid'],$spread_users)) {
									$spread_money = round($spread_total_money * $sub_user_spread_rate / 100, 2);
									$spread_thd_data=array('uid' => $first_spread_user['uid'], 'spread_uid' => $second_user['uid'], 'get_uid' => $now_user['uid'], 'money' => $spread_money, 'order_type' => $spread_type, 'order_id' => $now_order['order_id'], 'third_id' => $now_order['store_id'], 'add_time' => $_SERVER['REQUEST_TIME']);
									if($first_spread_user['spread_change_uid']!=0){
										$spread_thd_data['change_uid'] = 	$first_spread_user['spread_change_uid'];
									}
                                    // 处理长订单id
                                    if ($now_order['orderid']) {
                                        $spread_thd_data['real_orderid'] = $now_order['orderid'];
                                    }
									D('User_spread_list')->data($spread_thd_data)->add();

                                    if($first_user_spread['openid']!='') {
                                        $fir_where['_string'] = "openid = '{$first_user_spread['openid']}' OR wxapp_openid = '{$first_user_spread['openid']}' ";
                                        $fir_user = M('User')->where($fir_where)->find();
                                    }
									if($spread_money>0) {
										$model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $first_spread_user['openid'], 'first' =>L_("X1的子用户的子用户X2通过您的分享购买了X3，验证消费后您将获得佣金。",array("X1" => $fir_user['nickname'],"X2" => $buy_user['nickname'],"X3" => C('config.cash_alias_name'))) , 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $now_order['mer_id']);
									}
									// D('User')->add_money($first_spread_user['uid'],$spread_money,'子用户推广用户 '.$now_user['nickname'].' 购买ID为 '.$now_order['group_id'].' 的商品 获得的佣金');
								}
							}

						}
						/*平台三级积分分佣 2018/12/13 客户定制*/
						if($spread_total_money>0 && $spread_user['uid']>0){
							if(C('config.first_jifen_percent')>0 || C('config.second_jifen_percent')>0 || C('config.third_jifen_percent')>0){
								$param=array();
							    $param['get_uid']=$now_user['uid'];
							    $param['first_uid']=$spread_user['uid'];
							    $param['second_uid']=$second_user['uid'];
							    $param['third_uid']=$first_spread_user['uid'];
							    $param['spread_total_money']=$spread_total_money;
							    $param['order_id']=$now_order['order_id'];
							    D('Percent_rate')->add_spread_jifen_log($param,$spread_type,$now_order['store_id']);
							}
						}
					}
				}elseif(!empty($now_user['openid'])&&C('config.open_spread_rand')){
		            /* 开启三级推广随机一级推广奖励功能，唯一次奖励 */
					//一级推广用户
					if($now_order['from_plat']==1){
						$spread_res = D('User_spread')->spread_rand_only_once('store',$now_user,$now_order); 
					}else{
						$spread_res = D('User_spread')->spread_rand_only_once('cash',$now_user,$now_order); 
					}
		        }elseif(!empty($now_user['openid'])&&C('config.open_spread_appoint_user') && $now_user['can_spread']==1 && C('config.open_spread_only_once')&&(C('config.spread_money_limit')==0 || C('config.spread_money_limit')<=$spread_total_money)){
		            /* 开启指定用户享受三级推广功能且开启唯一次奖励 */
					//一级推广用户
		                if($now_order['from_plat']==1){
							$spread_res = D('User_spread')->spread_user_only_once('store',$spread_total_money,$now_user,$now_order);
						}else{
							$spread_res = D('User_spread')->spread_user_only_once('cash',$spread_total_money,$now_user,$now_order);
						}
			        
			    }

				$now_order = $update_order;
				$price_str =  floatval($now_order['balance_pay']+$now_order['payment_money']+$now_order['merchant_balance']+$now_order['card_give_money']+$now_order['qiye_pay']+$internal_card_pay);

				//增加粉丝 5 group 6 shop 7 meal 8 appoint 9 store
				D('Merchant')->saverelation($now_user['openid'], $now_order['mer_id'], 9);


				if($now_order['from_plat'] && $now_user['openid']){
					if($now_order['from_plat'] == 2){
						$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
						$href = C('config.site_url').'/wap.php?c=My&a=store_order_detail&order_id='.$now_order['order_id'];
						$model->sendTempMsg('OPENTM201752540', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' => L_('到店买单提醒'), 'keyword1' => $now_order['name'], 'keyword2' => $now_order['order_id'], 'keyword3' => $price_str, 'keyword4' => date('Y-m-d H:i:s'), 'remark' => L_('付款成功，感谢您的使用')), $now_order['mer_id']);
					}else{
						$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
						$href = C('config.site_url').'/wap.php?c=My&a=store_order_detail&order_id='.$now_order['order_id'];
						$model->sendTempMsg('OPENTM201752540', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' => C('config.cash_alias_name').L_('提醒'), 'keyword1' => C('config.cash_alias_name'), 'keyword2' => $now_order['order_id'], 'keyword3' => $price_str, 'keyword4' => date('Y-m-d H:i:s'), 'remark' => L_('付款成功，感谢您的使用')), $now_order['mer_id']);
					}
				}
				//优惠买单现金返还
				$now_order['back_rate'] = 0;
				$now_order['back_price'] = 0;
				$now_order['back_original_price'] = 0;
				if(C('customization.cash_back')&&$now_order['uid']>0){
					$now_store = M('Merchant_store')->field(true)->where(['store_id' => $now_order['store_id']])->find();
					$cardCondition = [];
					$cardCondition['mer_id'] = $now_store['mer_id'];
					$cardCondition['uid'] = $now_order['uid'];
					$cardCondition['status'] = 1;
					$card = M('Card_userlist')->where($cardCondition)->find();
					if($now_store && $now_store['cash_back_rate'] > 0 && $card){
						$backData = [];
						$backData['pay_money'] = $now_order['balance_pay'] + $now_order['merchant_balance'] + $now_order['payment_money'];
						if($backData['pay_money'] > 0){
							$backData['mer_id'] = $now_store['mer_id'];
							$backData['store_id'] = $now_order['store_id'];
							$backData['uid'] = $now_order['uid'];
							$backData['card_id'] = $card['id'];
							$backData['order_id'] = $now_order['order_id'];
							$backData['store_name'] = $now_store['name'];
							$backData['cash_back_rate'] = $now_store['cash_back_rate'];
							$backData['cash_back_price'] = $backData['pay_money'] * $now_store['cash_back_rate'] / 100;
							$backData['status'] = 1;
							$backData['add_time'] = time();
							M('Merchant_store_cash_back_log')->data($backData)->add();
	
							if (M('Card_userlist')->where(['id' => $card['id']])->setInc('card_money_give', $backData['cash_back_price'])) {
								$data_money['card_id'] = $card['id'];
								$data_money['type'] = 1;
								$data_money['money_add'] = $backData['cash_back_price'];
								$data_money['desc'] = L_('优惠买单支付返现');
								D('Card_new')->add_row($data_money);
							}
							$now_order['back_rate'] = $backData['cash_back_rate'];
							$now_order['back_price'] = $backData['cash_back_price'];
							$now_order['back_original_price'] = $backData['pay_money'];
						}
					}
				}
				
				//优惠买单送优惠券
                $store_info = D('Merchant_store')->where(['store_id'=>$now_order['store_id']])->find();
				if($store_info&&$store_info['open_give_coupon']==1){
				    $merchant_store_give_coupon_set = D('Merchant_store_give_coupon_set')->where(['store_id'=>$now_order['store_id']])->find();
				    if($merchant_store_give_coupon_set){
				        $coupon_start_time = strtotime(date('Y-m-d',strtotime("+ ".$merchant_store_give_coupon_set['use_day'].'day')));
                        $coupon_end_time = strtotime(date('Y-m-d',strtotime("+ ".($merchant_store_give_coupon_set['use_day']+$merchant_store_give_coupon_set['expire_day']).'day')));
				        $coupon_discount = $merchant_store_give_coupon_set['is_discount']?$now_order['price']*$merchant_store_give_coupon_set['discount']/100:$merchant_store_give_coupon_set['discount'];
                        
				        //创建优惠券
                        $coupon_save_data = [
                            'mer_id' => $now_order['mer_id'],
                            'card_id' => 0,
                            'is_partition' => 1,
                            'name' => $merchant_store_give_coupon_set['coupon_name'],
                            'cate_name' => 'all',
                            'platform' => 'a:4:{i:0;s:3:"wap";i:1;s:3:"app";i:2;s:6:"weixin";i:3;s:5:"wxapp";}',
                            'cat_id' => 0,
                            'num' => $merchant_store_give_coupon_set['coupon_num'],
                            'limit' => $merchant_store_give_coupon_set['coupon_num'],
                            'order_money' => $merchant_store_give_coupon_set['order_money'],
                            'start_time' => $coupon_start_time,
                            'end_time' => $coupon_end_time,
                            'add_time' => time(),
                            'status' => 1,
                            'allow_new' => 0,
                            'store_id' => $now_order['store_id'],
                            'discount' => $coupon_discount,
                            'is_public_collection' => 0,
                            'had_pull' => $merchant_store_give_coupon_set['coupon_num'],
                            'img' => $merchant_store_give_coupon_set['coupon_img']?:'',
                            'use_with_card' => $merchant_store_give_coupon_set['use_with_card']?:0
                        ];
                        $coupon_add_id = D('Card_new_coupon')->add($coupon_save_data);
                        if($coupon_add_id){
                            $store_give_log = [
                                'coupon_id' => $coupon_add_id,
                                'mer_id' => $now_order['mer_id'],
                                'uid' => $now_user['uid'],
                                'num' => $merchant_store_give_coupon_set['coupon_num'],
                                'add_time' => time(),
                                'store_id' => $now_order['store_id'],
                                'store_money' => $now_order['price'],
                                'order_money' => $merchant_store_give_coupon_set['order_money'],
                                'coupon_name' => $merchant_store_give_coupon_set['coupon_name'],
                                'user_name' => $now_user['nickname']?:$now_user['truename'],
                                'user_phone' => $now_user['phone'],
                                'start_time' => $coupon_start_time,
                                'end_time' => $coupon_end_time,
                            ];
                            $store_give_log_id = D('Merchant_store_give_coupon_log')->add($store_give_log);
                            if(!$store_give_log_id){
                                fdump_api([$store_give_log,$store_give_log_id,D('Merchant_store_give_coupon_log')->getLastSql()],'merchant_store_give_coupon/add_coupon_error');
                            }
                            //用户领取
                            $user_coupon_data = [];
                            for ($i=1;$i<=$merchant_store_give_coupon_set['coupon_num'];$i++){
                                $user_coupon_data[] = [
                                    'coupon_id' => $coupon_add_id,
                                    'uid' => $now_user['uid'],
                                    'num' => 1,
                                    'receive_time' => $coupon_start_time,
                                ];
                            }
                            
                            $user_coupon_get_id = D('Card_new_coupon_hadpull')->addAll($user_coupon_data);
                            if(!$user_coupon_get_id){
                                fdump_api([$user_coupon_data,$user_coupon_get_id,D('Card_new_coupon_hadpull')->getLastSql()],'merchant_store_give_coupon/add_coupon_error');
                            }
                        }else{
                            fdump_api([$coupon_save_data,$coupon_add_id,D('Card_new_coupon')->getLastSql()],'merchant_store_give_coupon/add_coupon_error');
                        }
                        
                    }
                }
				
				//支付成功增加商家余额

				$now_user = M('User')->where(array('uid'=>$now_order['uid']))->find();
				if($now_order['from_plat']==1){
					$now_order['order_type']='store';
					$now_order['desc']=L_("用户X1支付记入收入",array("X1" => C('config.cash_alias_name')));
					//D('Merchant_money_list')->add_money($now_order['mer_id'],'用户优惠买单支付记入收入',$now_order);
					$res = D('SystemBill')->bill_method($now_order['is_own'],$now_order);
					fdump_sql([$now_order,$res],'store_order_after');
					//商家推广分佣
					D('Merchant_spread')->add_spread_list($now_order,$now_user,$now_order['order_type'],L_("X1用户X2获得佣金",array("X1" => $now_user['nickname'],"X2" => C('config.cash_alias_name'))));
				}else{
					$now_order['order_type']='cash';
					$now_order['desc']=L_('用户到店支付记入收入');
					//D('Merchant_money_list')->add_money($now_order['mer_id'],'用户到店支付记入收入',$now_order);
					D('SystemBill')->bill_method($now_order['is_own'],$now_order);
					//商家推广分佣
					D('Merchant_spread')->add_spread_list($now_order,$now_user,$now_order['order_type'],$now_user['nickname'].L_('用户到店支付获得佣金'));
				}

				/*------优惠买单扫码支付，直接成为商家推广用户 2019/8/6--------*/
				if ($now_user['openid']&&$now_order['is_scan']) {
					D('Merchant_spread')->spread_add($now_order['mer_id'], $now_user['openid'],'storeorder');
				}

				//微信派发优惠券 支付到平台 微信支付
				if($data_store_order['is_own']==0 && $data_store_order['pay_type']=='weixin' && $data_store_order['payment_money']>=C('config.weixin_send_money')){
					D('System_coupon')->weixin_send($data_store_order['payment_money'],$now_order['uid']);
				}


				//小票打印
// 				$msg = ArrayToStr::array_to_str($now_order['order_id'], 'shop_order');
// 				$op = new orderPrint(C('config.print_server_key'), C('config.print_server_topdomain'));
// 				$op->printit($now_order['mer_id'], $now_order['store_id'], $msg, 1);

// 				$str_format = ArrayToStr::print_format($now_order['order_id'], 'shop_order');
// 				foreach ($str_format as $print_id => $print_msg) {
// 					$print_id && $op->printit($now_order['mer_id'], $now_order['store_id'], $print_msg, 1, $print_id);
// 				}

				//短信提醒
				$sms_data = array('mer_id' => $now_order['mer_id'], 'store_id' => $now_order['mer_id'], 'type' => 'store');
				$store = D('Merchant_store')->field(true)->where(array('store_id' => $now_order['store_id']))->find();
// 				if (C('config.sms_shop_success_order') == 1 || C('config.sms_shop_success_order') == 3) {

// 				$sms_data['uid'] = $now_order['uid'];
// 				$sms_data['mobile'] = $now_user['phone'];
// 				$sms_data['sendto'] = 'user';
// 				$sms_data['content'] = '您在' . date("Y-m-d H:i:s") . '时，通过优惠买单,给【' . $store['name'] . '】店,支付了' . floatval($now_order['price']) . C('config.Currency_txt');
// 				Sms::sendSms($sms_data);

// 				}
 				if (C('config.sms_store_success_order') == 2 && $now_order['from_plat'] != 4) {
					$sms_data['uid'] = 0;
					$store['phone'] = explode(' ',$store['phone']);
					$sms_data['mobile'] = $store['phone'][0];
					$sms_data['sendto'] = 'merchant';
					$sms_data['content'] = L_("顾客X1在X2时，通过X3，支付了：X4，单号：X5。",array("X1" => $now_user['username'],"X2" => date("Y-m-d H:i:s"),"X3" => C('config.cash_alias_name'),"X4" => $price_str. C('config.Currency_txt'),"X5" => ($now_order['real_orderid']?$now_order['real_orderid']:$now_order['order_id'])));
					Sms::sendSms($sms_data);
 				}

				
				if($now_order['business_type']){
					switch($now_order['business_type']){
						case 'foodshop':
// 							$now_food_order = D('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->find();
							D('Foodshop_order')->after_pay($now_order['business_id']);
							M('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->data(array('price'=>$now_order['price']+$now_food_order['book_price'],'pay_type'=>'1'))->save();
							break;
					}
				}
				D('Merchant_store_staff')->sendMsgStoreOrder($now_order, $addScoreRs['score'] ?? 0);

				$printHaddle = new PrintHaddle();
				$printHaddle->printit($now_order['order_id'], 'store_order', 1);

				/*------更新系统订单信息 2019/1/14--------*/
				if($now_order['from_plat']==1 || $now_order['from_plat']==4){
					// 订单改变之后调用
					$this->after_store_change($now_order['order_id'],'store',2);
				}else{
					$this->after_store_change($now_order['order_id'],'cash',2);
				}
				$this->where(array('order_id'=>$now_order['order_id']))->setField('pay_get_score','0_'.$get_score_num);
				if($order_param['is_mobile']){
					return array('error'=>0,'url'=>str_replace('/source/','/',U('My/store_order_detail',array('order_id'=>$now_order['order_id']))), 'get_score_num'=>$get_score_num);
				}else{
					return array('error'=>0,'url'=>U('User/Index/store_order_list',array('order_id'=>$now_order['order_id'])));
				}
			}else{
				return array('error'=>1,'msg'=>L_('修改订单状态失败，请联系系统管理员！'));
			}
		}
	}

	//优惠买单获得积分 消耗商家积分
	public function store_score_get($order_param)
	{		
		if(C('config.merchant_buy_points_switch')!=1){
			return array('error' => 1, 'msg' => L_('商家积分功能未开启！'));
		}
		
		$condition_order['order_id'] = $order_param['order_id'];
		
		

		$now_order = $this->field(true)->where($condition_order)->find();
		if (empty($now_order)) {
			return array('error' => 1, 'msg' => L_('当前订单不存在！'));
		}elseif ($now_order['paid'] != 1 ) {
			return array('error' => 1, 'msg' => L_('订单未支付'));
		} else {
			// 商家信息
			$now_merchant = D('Merchant')->get_info($now_order['mer_id']);
			if($now_merchant['platform_score']<=0){
				return array('error' => 1, 'msg' => L_('当前商家没有积分！'));
			}

			if ($now_merchant['mer_id'] != '118') {
				return array('error' => 1, 'msg' => L_('当前商家没有开通此功能！'));
			}

			//得到当前用户信息，不将session作为调用值，因为可能会失效或错误。
			$now_user = D('User')->get_user($now_order['uid']);
			if (empty($now_user)) {
				return array('error' => 1, 'msg' => L_('没有查找到此订单归属的用户，请联系管理员！'));
			}


			//积分
			$store = D('Merchant_store')->field(true)->where(array('store_id' => $now_order['store_id']))->find();

			// D('Scroll_msg')->add_msg('store',$now_user['uid'],'用户'.$now_user['nickname'].'于'.date('Y-m-d H:i',$_SERVER['REQUEST_TIME']).'购买'.C('config.cash_alias_name').'成功');

			if(C('config.open_extra_price')==1){
				// $order = $now_order;
				// $order['order_type'] ='store';
				// $score = D('Percent_rate')->get_extra_money($order);
				// if($score>0){
				// 	D('User')->add_score($order['uid'], floor($score),'在 ' . $store['name'] . ' 中使用'.C('config.cash_alias_name').'支付了' . floatval($now_order['balance_pay']+$now_order['merchant_balance']+$order_param['pay_money']) . C('config.Currency_txt').' +'.$now_order['score_used_count'].C('config.extra_price_alias_name').' 获得'.C('config.extra_price_alias_name'));
				// 	D('Scroll_msg')->add_msg('store',$now_user['uid'],'用户'.$now_user['nickname'].'于'.date('Y-m-d H:i',$_SERVER['REQUEST_TIME']).'购买'.C('config.cash_alias_name').'成功获得'.C('config.score_name'));
					
				// }
			}else{
				$score_get_times = C('config.score_get_times');
				if((C('config.open_score_discount')==0 || $now_order['score_discount_type']!=2) && ( $score_get_times==0 || empty($score_get_times))){
					if(C('config.store_score_get_percent')==1){
						$score_get = C('config.store_score_percent')/100;
					}else{
						$score_get = C('config.user_store_score_get');
					}

					$now_merchant = D('Merchant')->get_info($now_order['mer_id']);
					if($now_merchant['score_get_percent']>=0){
						$score_get = $now_merchant['score_get_percent']/100;
					}

					
					//$score_get = D('Percent_rate')->get_user_add_score_times($score_get,$now_order['uid']);

					if($now_order['is_own'] && C('config.user_own_pay_get_score')!=1 ){
						$now_order['payment_money'] = 0;
					}

					if ($now_order['pay_type'] == 'offline') {
						$score_get  = round(($now_order['price']-$now_order['card_price']-$now_order['coupon_price']-$now_order['merchant_balance']-$now_order['score_deducte']-$now_order['card_give_money']) *$score_get);
					}else{
						$score_get  = round(($now_order['payment_money'] + $now_order['balance_pay']) *$score_get);
					}

					$score_can_get = ($score_get > $now_merchant['platform_score'])? $now_merchant['platform_score'] : ($score_get);
					// 商家减去积分
					$res1 = D('Merchant')->use_score($now_order['mer_id'], $score_can_get, L_("在X1 中使用X2支付了X3 获得X4",array("X1" => $store['name'],"X2" => C('config.cash_alias_name'),"X3" => floatval($now_order['price']) . C('config.Currency_txt'),"X4" => C('config.score_name'))),$now_order['order_id'],'shop',$now_order['uid']);

					// 用户增加积分
                    $money = $now_order['balance_pay']+$now_order['merchant_balance']+$order_param['pay_money'];
					$res2 = D('User')->add_score($now_order['uid'], $score_can_get, L_("在X1 中使用X2支付了X3 获得X4",array("X1" => $store['name'],"X2" => C('config.cash_alias_name'),"X3" => floatval($money) . C('config.Currency_txt'),"X4" => C('config.score_name'))));

					$this->where(array('order_id'=>$now_order['order_id']))->setField('score_give',$score_can_get);
				}

				D('Userinfo')->add_score($now_order['uid'], $now_order['mer_id'], $now_order['price'], L_("在 X1 中使用X2支付了X3 获得X4",array("X1" => $store['name'],"X2" => C('config.cash_alias_name'),"X3" => floatval($now_order['price']) . C('config.Currency_txt'),"X4" => C('config.score_name'))));
			}
			return array('error'=>0,'data'=>'success');
		}
	}
	private function voic_baidu(){
        static $return;

        if (empty($return)) {
            $voic_baidu = "https://openapi.baidu.com/oauth/2.0/token?grant_type=client_credentials&client_id=XU7nTWQzS9vn32bckmrhHbTu&client_secret=1375720f23eed9b7b787e728de5dd1c2";
            import('ORG.Net.Http');
            $return = Http::curlGet($voic_baidu);
        }
        return $return;
    }
	//支付时，金额不够，记录到帐号
	public function wap_after_pay_error($now_order,$order_param,$error_tips)
	{
		//记录充值的金额，因为 Pay/return_url 处没有返回order的具体信息，故在此调用。
        $user_result['error_code'] = 0;
        if($order_param['pay_money'] > 0){
            $alreadyMoney = M("User_money_list")
                ->where(['money'=>$order_param['pay_money'],'uid'=>$now_order['uid'],'ask'=> 8, 'ask_id'=>$now_order['order_id']])//8 store_order
                ->order('pigcms_id desc')
                ->find();
            if(empty($alreadyMoney)){
                //$user_result = D('User')->add_money($now_order['uid'],$order_param['pay_money'],L_("支付订单：X1发生错误！原因是：X2",array("X1" => $now_order['order_id'],"X2" => $error_tips),8,$now_order['order_id']));
            }
        }		
		if($order_param['pay_type']=='weixin'&&ACTION_NAME=='return_url'){
			exit('<xml><return_code><![CDATA[SUCCESS]]></return_code></xml>');
		}
		if($user_result['error_code']){
			return array('error'=>1,'msg'=>$user_result['msg']);
		}else{
			if($order_param['is_mobile']){
				$return_url = str_replace('/source/','/',U('My/store_order_detail',array('order_id'=>$now_order['order_id'])));
			}else{
				$return_url = U('User/Index/store_order_list',array('order_id'=>$now_order['order_id']));
			}
			return array('error'=>1,'msg'=>$error_tips.L_('已将您充值的金额添加到您的余额内。'),'url'=>$return_url);
		}
	}

	/**
	 * 店铺当天订单数量
	 */

	public function get_storeorder_count_today($order_id){
		$today = strtotime(date('Ymd',time()));
		$where['store_id'] = $order_id;
		$where['pay_time'] = array('gt',$today);
		return $this->where($where)->count();
	}

	/**
	 * 扫码订单查询
	 */
	public function get_order_by_payid($uid,$payid){
		return $this->where(array('uid'=>$uid,'payid'=>$payid))->find();
	}

	/*
	 * 判断用户积分优惠券，并保存
	 * */
	public function check_coupon_score($now_user,$now_order)
	{
		$order_info = array(
				'order_id'			=>	$now_order['order_id'],
				'mer_id'			=>	$now_order['mer_id'],
				'order_type'		=>	'store',
				'order_name'		=>	$now_order['name'],
				'store_id'		=>	$now_order['store_id'],
				'no_discount_money'		=>	$now_order['no_discount_money'],
				'order_num'			=>	0,
				'order_price'		=>	floatval($now_order['price']),
				'order_total_money'	=>	floatval($now_order['price']),
				'uid'	=>	$now_user['uid'],
		);
		$order_type  = $order_info['order_type'];

		if ($order_info['mer_id']) {
			$now_merchant = D('Merchant')->get_info($order_info['mer_id']);
		}

		$now_user['now_money'] = 0;
		$platform = 'weixin';

		$order_info['total_money'] = $order_info['order_total_money'];
		$tmp_order = $order_info;
		if ($order_info['order_type'] == 'store') {
			$tmp_order['total_money'] -= $tmp_order['no_discount_money'];
		}

		$merchant_balance = 0 ;
		$card_info = D('Card_new')->get_card_by_uid_and_mer_id($now_user['uid'], $order_info['mer_id']);

		if(C('config.cash_nobind_phone_use_balance') || !empty($now_user['phone'])){


			if($card_info['status']) {
				if ((!isset($order_info['discount_status']) || $order_info['discount_status'])) {

						if (!empty($order_info['business_type'])) {
							$card_coupon_list = D('Card_new_coupon')->get_noworder_coupon_list($tmp_order, $order_type, $platform, $order_info['business_type']);
						} else {
							$card_coupon_list = D('Card_new_coupon')->get_noworder_coupon_list($tmp_order, $order_type, $platform);
						}

						$tmp_coupon = reset($card_coupon_list);

					if(!empty($tmp_coupon)){
						$mer_coupon['had_id'] = $tmp_coupon['id'];
						//$mer_coupon['coupon_id'] = $tmp_coupon['coupon_id'];
						$mer_coupon['order_money'] = $tmp_coupon['order_money'];
						$mer_coupon['discount'] = $tmp_coupon['discount'];
					}else{
						$mer_coupon =array();
					}
				}else{
					$mer_coupon = array();
				}
			}else{
				$mer_coupon = array();
			}

			$tmp_order['total_money'] -= empty($mer_coupon['discount']) ? 0 : $mer_coupon['discount'];

			//平台优惠券
			if ($tmp_order['total_money'] > $mer_coupon['discount']) {


					if (!empty($order_info['business_type'])) {
						$now_coupon = D('System_coupon')->get_noworder_coupon_list($tmp_order, $order_type, '', $now_user['uid'], $platform, $order_info['business_type']);
					} else {
						$now_coupon = D('System_coupon')->get_noworder_coupon_list($tmp_order, $order_type, '', $now_user['uid'], $platform);

					}
				$tmp_coupon = reset($now_coupon);

				if($tmp_coupon){
					$system_coupon['had_id'] = $tmp_coupon['id'];
					//	$system_coupon['coupon_id'] = $tmp_coupon['coupon_id'];
					$system_coupon['order_money'] = $tmp_coupon['order_money'];
					$system_coupon['discount'] = $tmp_coupon['discount'];
				}else{
					$system_coupon =array();
				}
			}else{
				$system_coupon = array();
			}

			if (isset($order_info['discount_status']) && !$order_info['discount_status']) {
				$card_info['discount'] = 10;
			}
			$tmp_order['total_money'] -=$system_coupon['discount'];

			if($tmp_order['total_money']<0){
				$tmp_order['total_money']= 0;
			}
		}
		if($order_info['order_type']=='store'){
			// $pay_infact+=$tmp_order['no_discount_money'];
			$tmp_order['total_money']+=$tmp_order['no_discount_money'];
		}

		$score_can_use_count=0;
		$score_deducte=0;

		$type_ =$order_type;
		$user_score_use_condition=C('config.user_score_use_condition');
		$user_score_max_use=D('Percent_rate')->get_max_core_use($order_info['mer_id'],$type_,$tmp_order['total_money']);

		//$user_score_max_use=$score_config['user_score_max_use'];
		if($order_type=='group'){
			$group_info = D('Group')->where(array('group_id'=>$order_info['group_id']))->find();
			if($group_info['score_use']){
				if($group_info['group_max_score_use']!=0){
					$user_score_max_use = $group_info['group_max_score_use'];
				}
			}else{
				$user_score_max_use = 0;
			}
		}
		$user_score_use_percent=C('config.user_score_use_percent');
		$score_max_deducte=bcdiv($user_score_max_use,$user_score_use_percent,2);

		if($user_score_use_percent>0&&$score_max_deducte>0&&$user_score_use_condition>0&&$now_user['score_count']>0){   //如果设置没有错误
			$total_ = $tmp_order['total_money'];
			if ($total_>=$user_score_use_condition) {             //判断是否够使用积分的条件，即这个订单的总额是否大于规定的金额
				if($total_>$score_max_deducte){                    //判断积分最大抵扣金额是否比这个订单的总额大
					$score_can_use_count = (int)($now_user['score_count']>$user_score_max_use?$user_score_max_use:$now_user['score_count']);//如果用户积分大于每个订单的积分可使用数 则按可使用数算
					$score_deducte =bcdiv($score_can_use_count,$user_score_use_percent,2);
					$score_deducte = $score_deducte>$total_?$total_:$score_deducte;
				}else{                                                                      //最大可抵扣的金额比总单金额大 只扣掉总单范围内的积分 扣除积分=总单*积分抵扣比例
					$score_can_use_count = ceil($total_*$user_score_use_percent)>(int)$now_user['score_count']?(int)$now_user['score_count']:ceil($total_*$user_score_use_percent);
					$score_deducte = bcdiv($score_can_use_count,$user_score_use_percent,2);
					$score_deducte = $score_deducte>$total_?$total_:$score_deducte;
				}
			}
		}

		//子商家设置不允许使用平台优惠抵扣
		if($now_user['forzen_score'] == 1 || (C('config.cash_nobind_phone_use_balance')==0 && empty($now_user['phone']))){
			$score_can_use_count = 0;
			$score_deducte = 0;
		}
		$tmp_order['total_money']=$tmp_order['total_money']*100-$score_deducte*100;

		$arr['card_id'] = intval($mer_coupon['had_id']);
		$arr['card_price'] = floatval($mer_coupon['discount']);
		$arr['coupon_id'] = intval($system_coupon['had_id']);
		$arr['coupon_price'] = floatval($system_coupon['discount']);
		$arr['score_used_count'] = $score_can_use_count;
		$arr['score_deducte'] = $score_deducte;
		$arr['total_money'] = $tmp_order['total_money']/100;
		return $arr;
	}

	// 订单状态改变之后调用
	public function after_store_change($order_id = 0,$order_type = 0,$step = 1,$order_data=[]){		
    	if(!$order_id) return false;
        $now_order = $order_data;
    	if(!$order_data)$now_order = $this->get_order_by_id('',$order_id);
        fdump('after_store_change','store',true);
        fdump($now_order,'store',true);

    	if ($now_order['business_type'] == 'foodshop') {
    	}else{
            /* 增加系统订单记录 2019/01/29 */
	    	$res = D('System_order')->add_order($order_type,$now_order,$order_id,$step);
    	}
	}


	/**
	 * 退款
	 *
	 * @param string|int $order_id   订单号
	 * @param boolean $is_long  是否长订单号，默认否
	 * @date: 2024/07/22
	 */
	public function refund_store_order($order_id, $is_long = false, $staff_id = 0, $staff_name = '')
	{
        $cacheKey = $order_id;
        if(S($cacheKey)){
            throw_exception('请勿重复操作!');
        }
        S($cacheKey, 1, 5);
		$where = $is_long ? ['orderid' => $order_id] : ['order_id' => $order_id];
		$order = $this->where($where)->find();
		if (empty($order)) {
			throw new Exception('订单不存在');
		}
		if ($order['refund'] == 1) {
			throw new Exception('订单已退款,请勿重复退款');
		}

		$alias_name = $order['from_plat'] == 1 ? '优惠买单' : '店内收银';
		$my_user = D('User')->field(true)->where(array('uid' => $order['uid']))->find();

		//在线支付金额 payment_money
		if ($order['payment_money'] != '0.00') {
			$payment_money_order = M('Payment_money_order')->where(array('orderid' => $order['orderid'], 'order_type' => 'store', 'pay_type' => $order['pay_type'], 'paid_money' => array('gt', 0)))->find();

			if ($order['is_own'] && $order['pay_type'] != 'nmgpay'  && $payment_money_order['pay_method'] != 'weikebaba' && $payment_money_order['pay_method'] != 'lichu') {

				$now_merchant = D('Merchant')->get_info($order['mer_id']);
				$open_sub_mchid = C("config.open_sub_mchid");
				if ($now_merchant['sub_mch_refund'] && $open_sub_mchid && $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0) {
					$pay_method['weixin']['config']['pay_weixin_appid'] = C("config.pay_weixin_appid");
					$pay_method['weixin']['config']['pay_weixin_appsecret'] = C("config.pay_weixin_appsecret");
					$pay_method['weixin']['config']['pay_weixin_mchid'] = C("config.pay_weixin_sp_mchid");
					$pay_method['weixin']['config']['pay_weixin_key'] = C("config.pay_weixin_sp_key");
					$pay_method['weixin']['config']['sub_mch_id'] = $now_merchant['sub_mch_id'];
					$pay_method['weixin']['config']['pay_weixin_client_cert'] = C("config.pay_weixin_sp_client_cert");
					$pay_method['weixin']['config']['pay_weixin_client_key'] = C("config.pay_weixin_sp_client_key");
					$pay_method['weixin']['config']['is_own'] = 2;
				}


				if ($order['is_own'] == 2 && $now_merchant['sub_mch_refund'] && C('config.open_sub_mchid') && $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0) {
					if ($order['is_mobile_pay'] == '3') {
						$pay_method['weixin']['config']['pay_weixin_appid'] = C('config.pay_wxapp_appid');
						$pay_method['weixin']['config']['pay_weixin_appsecret'] = C('config.pay_wxapp_appsecret');
					} else {
						$pay_method['weixin']['config']['pay_weixin_appid'] = C('config.pay_weixin_appid');
						$pay_method['weixin']['config']['pay_weixin_appsecret'] = C('config.pay_weixin_appsecret');
					}
					$pay_method['weixin']['config']['pay_weixin_mchid'] = C('config.pay_weixin_sp_mchid');
					$pay_method['weixin']['config']['pay_weixin_key'] = C('config.pay_weixin_sp_key');
					$pay_method['weixin']['config']['sub_mch_id'] = $now_merchant['sub_mch_id'];
					$pay_method['weixin']['config']['pay_weixin_client_cert'] = C('config.pay_weixin_sp_client_cert');
					$pay_method['weixin']['config']['pay_weixin_client_key'] = C('config.pay_weixin_sp_client_key');
					$pay_method['weixin']['config']['is_own'] = 2;
				}
			} else {
				$pay_method = D('Config')->get_pay_method(0, 0, 0, 0, false, true);
			}

			if ($payment_money_order && $payment_money_order['pay_method'] == 'chinaums') {
				import("@.ORG.pay.Chinaums");
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
				if ($order['mer_id']) {
					$now_merchant = M("Merchant")->where(['mer_id' => $order['mer_id']])->find();
					if (!empty($now_merchant['chinaums_merno'])) {
						$chinaums_merno = $now_merchant['chinaums_merno'];
					}
				}
				$go_refund_param = $pay->refund('store_' . $payment_money_order['orderid'], $payment_money_order['paid_money'], $payment_money_order['paid_money'], $payment_money_order['third_plat_data'], $chinaums_merno, $mid);
			}else if($payment_money_order && $payment_money_order['pay_method'] == 'lakala'){
				import('@.ORG.pay.Lakala');
				$pay_config = json_decode($payment_money_order['pay_config'],true);
				$pay = new Lakala([],$pay_config);
				
				$go_refund_param= $pay->refund('store_'.$payment_money_order['orderid'], $payment_money_order['paid_money'], $payment_money_order['paid_money'], $payment_money_order['third_plat_data']);
			} else {
				$pay_class_name = ucfirst($order['pay_type']);
				$import_result = import('@.ORG.pay.' . $pay_class_name);
				if (!empty($import_result)) {
					$order['order_type'] = 'store';
					$order['order_id'] = $order['orderid'];

					if ($pay_class_name == 'Alipay' && $order['is_mobile_pay'] == 2 && C('config.new_pay_alipay_app_public_key') != '' && C('config.new_pay_alipay_app_appid') != '' && C('config.new_pay_alipay_app_private_key') != '') {
						$pay_class_name = 'AlipayApp';
						$pay_method['alipay']['config']['new_pay_alipay_app_appid'] = C('config.new_pay_alipay_app_appid');
						$pay_method['alipay']['config']['new_pay_alipay_app_private_key'] = C('config.new_pay_alipay_app_private_key');
						$pay_method['alipay']['config']['new_pay_alipay_app_public_key'] = C('config.new_pay_alipay_app_public_key');
					}
					$import_result = import('@.ORG.pay.' . $pay_class_name);
					$pay_class = new $pay_class_name($order, getFormatNumber($order['payment_money']), $order['pay_type'], $pay_method[$order['pay_type']]['config'], $my_user, 1);
					$go_refund_param = $pay_class->refund();
				}
			}
		}

		//平台余额 balance_pay
		if ($order['balance_pay'] > 0) {
			D('User')->add_money($order['uid'], $order['balance_pay'], L_("X1商品退款，增加余额，订单编号X2", array("X1" => $alias_name, "X2" => $order['orderid'])));
		}

		//商家余额 merchant_balance
		//商家会员卡赠送余额 card_give_money
		if ($order['merchant_balance'] != '0.00' || $order['card_give_money'] != '0.00') {
			D('Card_new')->add_user_money($order['mer_id'], $order['uid'], $order['merchant_balance'], $order['card_give_money'], 0, L_("X1商品退款，增加余额，订单编号X2", array("X1" => $alias_name, "X2" => $order['orderid'])), L_("X1商品退款，增加余额，订单编号X2", array("X1" => $alias_name, "X2" => $order['orderid'])));
		}

		//积分抵扣数量 score_used_count
		if ($order['score_used_count'] > 0) {
			D('User')->add_score($order['uid'], $order['score_used_count'], L_("X1商品退款，X2回滚，订单编号X3", array("X1" => $alias_name, "X2" => C('config.score_name'), "X3" => $order['orderid'])));
		}

		//会员卡优惠券 card_id
		//平台优惠券 coupon_id
		if ($order['card_id']) {
			D('Card_new_coupon')->return_coupon($order['card_id'], $order['order_id'], 'store', $order['mer_id'], $order['uid']);
		}
		if ($order['coupon_id']) {
			D('System_coupon')->return_coupon($order['coupon_id'], $order['order_id'], 'store', $order['mer_id'], $order['uid']);
		}

		//一卡通支付金额 internal_card_pay
		$card_pay_info = json_decode($order['internal_card_pay'], true);
		$time = time();
		if (!empty($card_pay_info)) {
			$card_DB = M('internal_membership_card');
			$card_log_DB = M('internal_membership_card_log');
			foreach ($card_pay_info as $card_id => $card_pay_money) {
				$card = $card_DB->where(array('card_id' => $card_id))->find();
				$card_balance = round($card['balance_money'] * 100) / 100;

				$save_data['balance_money'] = round(($card_balance + $card_pay_money) * 100) / 100;
				$save_data['last_time'] = $time;
				$card_DB->where(array('card_id' => $card_id))->save($save_data);
				$condition_card_log['card_id'] = $card_id;
				$condition_card_log['operation_type'] = 3; //交易退款
				$condition_card_log['before_balance'] = $card_balance;
				$condition_card_log['after_balance'] = $save_data['balance_money'];
				$condition_card_log['money'] = $card_pay_money;
				$condition_card_log['business_type'] = $order['from_plat'] == 1?'store':'cash';
				$condition_card_log['order_id'] = $order['orderid'];
				$condition_card_log['uid'] = $order['uid'];
				$condition_card_log['describe'] = $alias_name . '商品退款, 退回余额' . $card_pay_money . '元, 订单编号' . $order['orderid'];
				$condition_card_log['mer_id'] = $order['mer_id'];
				$condition_card_log['store_id'] = $order['store_id'];
				$condition_card_log['create_time'] = $time;
				$card_log_DB->add($condition_card_log);
				D('Internal_card')->send_wx_msg($card_id, '+' . getFormatNumber($card_pay_money), $alias_name . '商品退款');
			}
		}

		//如果是充值订单, 充值一卡通
		if ($order['from_plat'] == 4) {
			$rechargeLog = M('Internal_membership_card_log')->where(['operation_type' => 1, 'order_id' => $order['orderid']])->find();
			if ($rechargeLog) {
				D('Internal_card')->recharge($rechargeLog['card_id'], $rechargeLog['money'] * -1, $order['orderid'],true);
			}
		}

		$refund = [
			'refund' => 1,
			'refund_time' => time(),
			'refund_txt' => $go_refund_param ? serialize($go_refund_param) : '订单退款',
			'refund_staff_id' => $staff_id,
			'refund_staff_name' => $staff_name
		];
		$this->where($where)->save($refund);

		//退回商家结算余额
		if ($mdata = D('Merchant_money_list')->field('percent,type,system_take,money,income,mer_id,store_id,order_id')->where(array('_string' => "type='cash' or type='store'", 'order_id' => $order['order_id']))->select()) {
			foreach ($mdata as $m_item){
			    if($m_item['income'] == 1){
                    $m_add_list_res = D('Merchant_money_list')->use_money(
						$m_item['mer_id'], 
						$m_item['money'], 
						$m_item['type'],  
						$alias_name. '退款,减少余额', 
						$m_item['order_id'], 
						$m_item['percent'], 
						$m_item['system_take'],
						[],
						0,
						$go_refund_param['refund_param']??[]
					);
                    fdump_api($m_add_list_res,'store_order_refund',1);
			    }elseif($m_item['income'] == 2){
			        $m_add_data = [
                        'mer_id' => $m_item['mer_id'],
                        'income' => 1,
                        'order_id'  => $m_item['order_id'],
                        'percent' => 0,
                        'system_take' => 0,
                        'type' => $m_item['type'],
                        'money' => $m_item['money'],
                        'use_time' => time(),
                        'desc' => $alias_name. '退款,增加余额',
                        'store_id' => $m_item['store_id'],
                    ];
                    $m_add_res = M('Merchant')->where(array('mer_id'=>$m_item['mer_id']))->setInc('money', $m_add_data['money']);
                    fdump_api($m_add_res,'store_order_refund',1);
                    if($m_add_res){
                        $now_mer_money = M('Merchant')->field('money,mch_owe_money')->where(array('mer_id'=> $m_item['mer_id']))->find();
                        $m_add_data['now_mer_money'] = $now_mer_money['money'];
                        $m_add_list_res = D('Merchant_money_list')->add($m_add_data);
                        fdump_api([$m_add_data,$m_add_list_res],'store_order_refund',1);
                    }
                }
            }
		}
		D("System_order")->save_order_status($order['from_plat'] == 1 ? 'store' : 'cash', $order['order_id'], 5);
        //退订单积分
        $check_score = D('User_score_list')->where(['order_type'=>'store','order_id'=>$order['order_id'],'score'=>['gt',0],'type'=>1])->getField('score',true);
        foreach ($check_score as $item){
            D('User')->use_score($order['uid'],
                $item,
                L_('快速买单订单退款扣除X1', ['X1'=>C('config.score_name')]),
                ['mer_id'=>$order['mer_id'],'store_id'=>$order['store_id'],'score_type'=>0,'order_id'=>$order['order_id'],'order_type'=>'store']
            );
        }
	}
}
?>