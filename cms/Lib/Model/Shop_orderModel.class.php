<?php
class Shop_orderModel extends Model
{
    public $status_list = [];
	public $status_list_admin = [];

	public $order_from_list = [];

    public function _initialize()
    {
        $this->status_list = [
            '-1' => L_('全部'),
            '0' => L_('未确认'),
            1 => L_('已确认'),
            2 => L_('已消费'),
            3 => L_('已评价'),
            4 => L_('已退款'),
            5 => L_('已取消'),
            6 => L_('未支付'),
            7 => L_('分配到自提点'),
            8 => L_('发货到自提点'),
            9 => L_('自提点接货'),
            10 => L_('自提点发货'),
            12 => L_('已付款未消费'),
        ];
        $this->status_list_admin = [
            '-1' => L_('全部'),
            '100' => L_('未支付'),
            '0' => L_('已支付'),
            1 => L_('已确认'),
            2 => L_('已消费'),
            3 => L_('已评价'),
            4 => L_('已退款'),
            5 => L_('已取消'),
            7 => L_('分配到自提点'),
            8 => L_('发货到自提点'),
            9 => L_('自提点接货'),
            10 => L_('自提点发货'),
            11 => L_('申请退货'),
            12 => L_('已付款未消费'),
        ];
        $this->order_from_list = [
            '0' => 'wap快店',
            '1' => 'wap商城',
            '2' => 'Android',
            '3' => 'ios',
            '4' => '小程序',
            '5' => 'pc快店',
            '6' => '线下零售',
            '7' => '饿了么',
            '8' => '美团',
            '9' => '扫码购'
        ];
    }

	/**获取订单分类**/
	public function get_order_cate($order_id){
		$store_id = $this->field('store_id,order_from')->where(array('order_id'=>$order_id))->find();
		$cat_id = M('Shop_category_relation')->field('cat_fid')->where(array('store_id'=>$store_id['store_id']))->find();
		$meal_cate = M((_view('Shop_category')))->field('cat_id,cat_name')->where(array('cat_id'=>$cat_id['cat_fid']))->find();
		return $meal_cate;
	}

    public function get_order_cate_more($order_id, $is_storeid = false)
    {
        if ($is_storeid) {
            $store_id = $order_id;
        } else {
            $store_id = $this->field('store_id')->where(array('order_id' => $order_id))->find();
        }
		$cat_id = M('Shop_category_relation')->field('cat_fid')->where(array('store_id'=>$store_id['store_id']))->select();
		foreach($cat_id as $v){
			$catfid_arr[] = $v['cat_fid'];
		}
		$meal_cate = M((_view('Shop_category')))->field('cat_id,cat_name')->where(array('cat_id'=>array('in',$catfid_arr)))->select();
		return $meal_cate;
	}

	public function get_order_by_id($uid, $order_id, $orderid = 0){
		$where = array();
		$order_id && $where['order_id'] = $order_id;
		$orderid && $where['orderid'] = $orderid;
		// $where['uid'] = $uid;
		return $this->field(true)->where($where)->find();
	}

	public function get_order_by_orderid($order_id){
		$where = array();
		$order_id && $where['order_id'] = $order_id;
		return $this->field(true)->where($where)->find();
	}

	public function get_pay_order($uid, $order_id, $is_web = false,$is_app = false,$order_info = [])
	{
	    if($order_info){
	        $now_order = $order_info;
        }else{
            $now_order = $this->get_order_by_id($uid, $order_id);
            if(empty($now_order)){
                return array('error'=>1,'msg'=>L_('当前订单不存在！'));
            }
        }
		if($now_order['paid'] == 1&&$now_order['book_id']==0){
			if (!$is_web) {
				if ($now_order['order_from'] == 1) {
					return array('error' => 1, 'msg' => L_('您已经支付过此订单！'), 'url' => U('Wap/Mall/status', array('order_id' => $now_order['order_id'], 'mer_id' => $now_order['mer_id'], 'store_id' => $now_order['store_id'])));
				} else {
                    if (C('config.map_config') == 'google') {
                        $url = packapp_url('my/shop_order.html?order_id=' . $now_order['order_id'], D('User')->get_user($order_info['uid']));
                    } else {
                        $url = C('config.site_url') . D('House_village')->base_url . "pages/shop_new/orderDetail/orderDetail?order_id=" . $now_order['order_id'];
                    }
                    return array('error' => 1, 'msg' => L_('您已经支付过此订单！'), 'url' => $url);
				}
			} else {
				return array('error' => 1, 'msg' => L_('您已经支付过此订单！'), 'url' => U('User/Index/shop_order_view', array('order_id' => $now_order['order_id'])));
			}
		}


		$price = $now_order['price'];
        if($now_order['book_id']>0){
        	if($now_order['book_status']==0){
        		$price = $now_order['bargin_price'];
        	}elseif($now_order['book_status']==1){
        		$price = $now_order['rest_price'];
        	}else{
        		return array('error' => 1, 'msg' => L_('此预售订单已支付完成！'), 'url' => U('Wap/Mall/status', array('order_id' => $now_order['order_id'], 'mer_id' => $now_order['mer_id'], 'store_id' => $now_order['store_id'])));
        	}
        }

		if($now_order['status'] == 4 || $now_order['status'] == 5){
			if (!$is_web) {
				if ($now_order['order_from'] == 1) {
					return array('error' => 1, 'msg' => L_('您的订单已取消，不能付款了！'), 'url' => U('Wap/Mall/status', array('order_id' => $now_order['order_id'], 'mer_id' => $now_order['mer_id'], 'store_id' => $now_order['store_id'])));
				} else {
					return array('error' => 1, 'msg' => L_('您的订单已取消，不能付款了！'), 'url' => U('Wap/Shop/status', array('order_id' => $now_order['order_id'], 'mer_id' => $now_order['mer_id'], 'store_id' => $now_order['store_id'])));
				}
			} else {
				return array('error' => 1, 'msg' => L_('您的订单已取消，不能付款了！'), 'url' => U('User/Index/shop_order_view', array('order_id' => $now_order['order_id'])));
			}
		}
		if ($now_order['is_pick_in_store'] < 2 && $now_order['expect_use_time'] && $now_order['expect_use_time'] < time()) {
		    $this->where(array('order_id' => $order_id))->save(array('status' => 5));
		    D("System_order")->save_order_status('shop', $order_id, 5);
		    D('Shop_order_log')->add_log(array('status' => 10, 'order_id' => $order_id, 'name' => L_('自动取消'), 'note' => L_('超时未付款，自动取消订单')));

			if (!$is_web) {
				$redirectUrl = U('Wap/Shop/status', array('order_id' => $now_order['order_id'], 'mer_id' => $now_order['mer_id'], 'store_id' => $now_order['store_id']));
			}else{
				$redirectUrl = U('User/Index/shop_list');
			}
		    return array('error'=>1,'msg'=>L_('期望送达时间比当前时间早，无法进行支付，此订单已自动取消，请重新下单！'), 'url' => $redirectUrl);
		}

        $logo = M("Merchant")->where(['mer_id'=>$now_order['mer_id']])->getField('logo');
        $merchant_store = M("Merchant_store")->where(array('store_id' => $now_order['store_id'], 'mer_id' => $now_order['mer_id']))->find();
        if (empty($logo)) {
            $imgs = explode(';', $merchant_store['pic_info']);
        }

		$order_content = D('Shop_order_detail')->join('as o LEFT JOIN '.C('DB_PREFIX').'shop_goods g ON g.goods_id = o.goods_id')->field('o.*,g.score_max,score_percent')->where(array('order_id' => $order_id))->select();
		foreach ($order_content as &$trow) {
			$trow['name'] = $trow['spec'] ? $trow['name'] . ' (' . $trow['spec'] . ')' : $trow['name'];
			$trow['money'] = floatval($trow['price'] * $trow['num']);
		}

		//$str='';
		foreach ($order_content as $val) {
			if(count($order_content)>1) {
				if($is_app){
					$str = $val['name'] . ' '.C('config.Currency_symbol') . $val['price'] . ' *' . $val['num'] . ' 等';
				}else{
					$str = $val['name'] . ' '.C('config.Currency_symbol') . $val['price'] . ' *' . $val['num'] . '  <b style="color:#BEBFBF;">等</b>';
				}
			}else{
				$str = $val['name'] . ' '.C('config.Currency_symbol') . $val['price'] . ' *' . $val['num'] ;
			}
			break;
		}
        if (empty($order_content) && $order_info['goods']) {
            $order_content = $order_info['goods'];
        }
        $score = 0;
		foreach ($order_content as &$goods) {
			//积分展示
			if($scorePrice = floatval($goods['price'] * $goods['num'])){
				$scorePercent = D('Percent_rate')->scorePercent('shop', $goods['goods_id'], $now_order['mer_id']);
				$goods['score'] = bcmul($scorePrice, $scorePercent,2);
				if(C('config.score_round_two')){
					$goods['score'] = sprintf('%.2f',floatval($goods['score']));
				}else{
					$goods['score'] = round($goods['score']);
				}
				$score += floatval($goods['score']);
			}
		}
		$order_type = 'shop';
		if ($is_web) {
			$order_info = array(
					'order_id'			=>	$now_order['order_id'],
					'real_orderid'		=>	$now_order['real_orderid'],
					'orderid'			=>	$now_order['orderid'],
					'mer_id'			=>	$now_order['mer_id'],
					'store_id'			=>	$now_order['store_id'],
					'paid'				=>	$now_order['paid'],
					'goods_price'		=>  $now_order['goods_price'],
					'uid'				=>	$now_order['uid'],
					'balance_pay'		=>	$now_order['balance_pay'],//平台余额支付的金额
					'merchant_balance'	=>	$now_order['merchant_balance'],//商家余额支付的金额
					'payment_money'		=>	$now_order['payment_money'],//在线支付的金额
					'pay_money'			=>	$now_order['pay_money'],//订单已经完成支付的金额
					'order_name'		=>	$merchant_store['name'],
					'order_num'			=>	$now_order['num'],
					'order_from'		=>	$now_order['order_from'],
					'freight_charge'	=>	$now_order['freight_charge'],
					'freight_charge_original' => $now_order['freight_charge_original'],
					'order_content'		=>  $order_content?$order_content:array(),
					'order_total_money'	=>	$price,//$now_order['price'],//当前需要支付的金额
					'order_type'		=>	$order_type,
					'extra_price'	=>	$now_order['extra_price'],
					'can_discount_money'	=>	$now_order['can_discount_money'],
					'group_id'=>$now_order['group_id'],
					'bar_id'=>$now_order['bar_id'],
					'is_pick_in_store'	=>	$now_order['is_pick_in_store'],
            		'can_use_mer_union_card' => C('config.mer_card_union') == 1 ? true : false, // 是否使用商家联盟会员卡
                    'discount_detail'=>$now_order['discount_detail'],
                    'upgrade_level'=>$now_order['upgrade_level'], //升级会员等级
                    'upgrade_level_price'=>$now_order['upgrade_level_price'], //升级会员等级价格
                    'current_user_level'=>$now_order['current_user_level'], //升级会员时会员等级
                    'book_id'=>$now_order['book_id'],//预售的id号
                    'is_open_membership_card'=>$now_order['is_open_membership_card'],//是否开通平台会员卡
                    'membership_card_price'=>$now_order['membership_card_price'],//平台会员卡价格
			);
		} else {
			$order_info = array(
				'order_id'			=>	$now_order['order_id'],
				'real_orderid'		=>	$now_order['real_orderid'],
				'orderid'			=>	$now_order['orderid'],
				'mer_id'			=>	$now_order['mer_id'],
				'store_id'			=>	$now_order['store_id'],
				'paid'				=>	$now_order['paid'],
				'goods_price'		=>  $now_order['goods_price'],
				'uid'				=>	$now_order['uid'],
				'balance_pay'		=>	$now_order['balance_pay'],//平台余额支付的金额
				'merchant_balance'	=>	$now_order['merchant_balance'],//商家余额支付的金额
				'payment_money'		=>	$now_order['payment_money'],//在线支付的金额
				'pay_money'			=>	$now_order['pay_money'],//订单已经完成支付的金额
				'order_name'		=>	$merchant_store['name'],
				'order_num'			=>	$now_order['num'],
				'order_from'		=>	$now_order['order_from'],
				'order_content'		=>  $order_content?$order_content:array(),
				'order_total_money'	=>	$price,//$now_order['price'],
				'freight_charge'	=>	$now_order['freight_charge'],
				'freight_charge_original' => $now_order['freight_charge_original'],
				'order_type'		=>	$order_type,
				'img'				=> $logo?replace_file_domain($logo):file_domain().$imgs[0],
				'order_txt_type'	=>	$str,
				'extra_price'	=>	$now_order['extra_price'],
				'can_discount_money'	=>	$now_order['can_discount_money'],
				'group_id'=>$now_order['group_id'],
				'bar_id'=>$now_order['bar_id'],
				'is_pick_in_store'	=>	$now_order['is_pick_in_store'],
            	'can_use_mer_union_card' => C('config.mer_card_union') == 1 ? true : false, // 是否使用商家联盟会员卡
                'discount_detail'=>$now_order['discount_detail'],
                'upgrade_level'=>$now_order['upgrade_level'], //升级会员等级
                'upgrade_level_price'=>$now_order['upgrade_level_price'], //升级会员等级价格
                'current_user_level'=>$now_order['current_user_level'], //升级会员时会员等级
                'book_id'=>$now_order['book_id'],//预售的id号
                'is_open_membership_card'=>$now_order['is_open_membership_card'],//是否开通平台会员卡
                'membership_card_price'=>$now_order['membership_card_price'],//平台会员卡价格
			);
		}
		C('customization.goods_show_score') && $order_info['score_text'] = $score > 0 ? C('config.score_name').'+'.floatval($score) : '';
		return array('error' => 0, 'order_info' => $order_info);
	}


	//电脑站支付前订单处理
	public function web_befor_pay($order_info,$now_user)
	{
		//判断是否需要在线支付
		if(!$order_info['use_balance']){
			$now_user['now_money']=0;
		}
		if(($now_user['now_money']+$order_info['score_deducte'] )< $order_info['order_total_money']){
			$online_pay = true;
		} else {
			$online_pay = false;
		}
		//不使用在线支付，直接使用会员卡和用户余额。
		if(empty($online_pay)){
			$order_pay['balance_pay'] =  $order_info['order_total_money']-$order_info['score_deducte'];
		}else{
			if(!empty($now_user['now_money'])){
				$order_pay['balance_pay'] = $now_user['now_money'];
			}
		}

		//将已支付用户余额等信息记录到订单信息里
		if(!empty($order_pay['balance_pay'])){
			$data_shop_order['balance_pay'] = $order_pay['balance_pay'] >= 0 ? $order_pay['balance_pay'] : 0;
		}
		//扣除积分并保存订单
		if(!empty($order_info['score_deducte'])){
			$data_shop_order['score_used_count']	= $order_info['score_used_count'];
			$data_shop_order['score_deducte']      	= (float)$order_info['score_deducte'];
		}

		if(!empty($data_shop_order)){
			$data_shop_order['score_used_count'] = (int)$order_info['score_used_count'];	//扣除积分
			$data_shop_order['score_deducte'] = (float)$order_info['score_deducte'];
			$data_shop_order['last_time'] = $_SERVER['REQUEST_TIME'];
			$condition_shop_order['order_id'] = $order_info['order_id'];
			if(!$this->where($condition_shop_order)->data($data_shop_order)->save()){
				return array('error_code'=>true,'msg'=>L_('保存订单失败！请重试或联系管理员。'));
			}
		}

		if($online_pay){
			return array('error_code' => false, 'pay_money' => $order_info['order_total_money'] - $now_user['now_money']-(float)$order_info['score_deducte']);
		}else{
			$order_param = array(
					'order_id' => $order_info['order_id'],
					'orderid' => $order_info['orderid'],
					'pay_type' => '',
					'third_id' => '',
					'is_mobile' => 0,
					'pay_money' => 0,
			);
			$result_after_pay = $this->after_pay($order_param);
			if($result_after_pay['error']){
				return array('error_code'=>true,'msg'=>$result_after_pay['msg']);
			}
			return array('error_code'=>false,'msg'=>L_('支付成功！'),'url'=>U('User/Index/shop_order_view',array('order_id'=>$order_info['order_id'])));
		}
	}

	//手机端支付前订单处理
	public function wap_befor_pay($order_info, $now_coupon, $merchant_balance, $now_user,$ecard_info=null)
	{
        $pay_money = round($order_info['order_total_money'] * 100) / 100; //本次支付的总金额
        if($order_info['book_id']>0) {
            $merchant_balance['card_discount']=0;
        }
        if($merchant_balance['card_discount']>0){
            $pay_money = round(round(($pay_money-$order_info['freight_charge'])*$merchant_balance['card_discount']/10,3),2)+$order_info['freight_charge'];
        }else{
            $pay_money = round($order_info['order_total_money'] * 100) / 100; //本次支付的总金额
        }
        if ($order_info['upgrade_level_price'] > 0) {
            $pay_money += $order_info['upgrade_level_price'];
        }
        if ($order_info['membership_card_price'] > 0) {
            $pay_money += $order_info['membership_card_price'];
        }
		if($merchant_balance['card_discount']>0){
		    //和上面判断重复了!!
			//$pay_money = round(round(($pay_money-$order_info['freight_charge'])*$merchant_balance['card_discount']/10,3),2)+$order_info['freight_charge'];
			//$pay_money = floor(($pay_money-$order_info['freight_charge'])*$merchant_balance['card_discount']*10)/100+$order_info['freight_charge'];
		}
		if($pay_money < 0){
			$pay_money = 0;
		}
		$merchant_balance['card_money'] = round($merchant_balance['card_money'] * 100) / 100;			//用户在商家所拥有的金额
		$merchant_balance['card_give_money'] = round($merchant_balance['card_give_money'] * 100) / 100;			//用户在商家所拥有的金额
		$data_shop_order['card_discount'] = $merchant_balance['card_discount'];
		//判断优惠券
		if(C('config.open_extra_price')==1){
            if($order_info['book_id']==0 || empty($order_info['book_id'])) {
                $user_score_use_percent=(float)C('config.user_score_use_percent');
                $order_info['order_extra_price'] = bcdiv($order_info['extra_price'],$user_score_use_percent,2);
                $pay_money +=$order_info['order_extra_price'];
            }
		}
		if($pay_money < 0){
			$pay_money = 0;
		}

		//保存加入联盟的商家id
		if(!empty($merchant_balance['union_mer_id'])){
			$data_shop_order['union_mer_id'] = $merchant_balance['union_mer_id'];
			$order_result = $this->wap_pay_save_order($order_info,$data_shop_order);
		}

		if($pay_money==0){
			$order_result = $this->wap_pay_save_order($order_info, $data_shop_order);
			if ($order_result['error_code']) {
				return $order_result;
			}
			return $this->wap_after_pay_before($order_info);
		}
		if($pay_money < 0){
			$pay_money = 0;
		}
		$data_shop_order['card_discount'] = $merchant_balance['card_discount'];
		if ($now_coupon['card_price'] >0) {
            if($order_info['book_id']==0 || empty($order_info['book_id'])) {
                $data_shop_order['card_id'] = $now_coupon['merc_id'];
                $data_shop_order['card_price'] = round($now_coupon['card_price'] * 100)/100;
                if ($now_coupon['card_price'] >= $pay_money) {
                    $order_result = $this->wap_pay_save_order($order_info, $data_shop_order);
                    if ($order_result['error_code']) {
                        return $order_result;
                    }
                    return $this->wap_after_pay_before($order_info);
                }
                $pay_money -= round($now_coupon['card_price'] * 100)/100;
                $pay_money = round($pay_money * 100)/100;
            }
		}
		if($pay_money < 0){
			$pay_money = 0;
		}

		if ($now_coupon['coupon_price'] >0) {
            if($order_info['book_id']==0 || empty($order_info['book_id'])) {
                $data_shop_order['coupon_id'] = $now_coupon['sysc_id'];
                $data_shop_order['coupon_price'] = round($now_coupon['coupon_price'] * 100)/100;
                $data_shop_order['sys_discount_type'] = $now_coupon['discount_type']?$now_coupon['discount_type']:0;//平台优惠券，优惠类型
                if ($now_coupon['coupon_price'] >= $pay_money && $now_coupon['discount_type']==0) {
                    $order_result = $this->wap_pay_save_order($order_info, $data_shop_order);
                    if ($order_result['error_code']) {
                        return $order_result;
                    }
                    return $this->wap_after_pay_before($order_info);
                }
                $pay_money -= round($now_coupon['coupon_price'] * 100)/100;
                $pay_money = round($pay_money * 100)/100;
            }
		}
		if($pay_money < 0){
			$pay_money = 0;
		}

		//使用平台E卡
		if(!empty($ecard_info) && $ecard_info['now_price']>0){
			$data_shop_order['ecard_password'] = $ecard_info['card_password'];
			$data_shop_order['use_ecard_price'] = $ecard_info['now_price'];
			if($ecard_info['now_price']>=$pay_money){
				$order_result = $this->wap_pay_save_order($order_info,$data_shop_order);
				if($order_result['error_code']){
					return $order_result;
				}
				$order_info['pay_money'] = $pay_money;
				return $this->wap_after_pay_before($order_info);
			}
			$pay_money-=$ecard_info['now_price'];
			$pay_money=round($pay_money*100)/100;
		}else{
			$data_shop_order['ecard_password'] ='';
			$data_shop_order['use_ecard_price'] =0;
		}
		if($pay_money < 0){
			$pay_money = 0;
		}

		// 使用积分
        if(!empty($order_info['score_deducte'])&&$order_info['use_score']){
            if($order_info['book_id']==0 || empty($order_info['book_id'])) {
                $data_shop_order['score_used_count']  = $order_info['score_used_count'];
                $data_shop_order['score_deducte']     = (float)$order_info['score_deducte'];
                if($order_info['score_deducte'] >= round($pay_money,2)){
                    $data_shop_order['score_deducte'] = $pay_money;
                    $order_result = $this->wap_pay_save_order($order_info,$data_shop_order);
                    if($order_result['error_code']){
                        return $order_result;
                    }
                    $order_info['pay_money'] = $pay_money;
                    return $this->wap_after_pay_before($order_info);
                }
                $pay_money -= $order_info['score_deducte'];
                $pay_money = round($pay_money * 100)/100;
            }
		}
		if($pay_money < 0){
			$pay_money = 0;
		}

		//判断商家余额
		if(!empty($merchant_balance['card_money'])&&$order_info['use_merchant_balance']){
			if ($merchant_balance['card_money'] >= $pay_money) {
				$data_shop_order['merchant_balance'] = $pay_money;
				$order_info['merchant_balance'] = $pay_money;
				$order_result = $this->wap_pay_save_order($order_info, $data_shop_order);
				if ($order_result['error_code']) {
					return $order_result;
				}
// 				$order_info['pay_money'] = $pay_money;
				return $this->wap_after_pay_before($order_info);
			}else{
				$data_shop_order['merchant_balance'] = $merchant_balance['card_money'];
			}
			$pay_money -= $merchant_balance['card_money'];
			$pay_money = round($pay_money * 100)/100;
		}
		if($pay_money < 0){
			$pay_money = 0;
		}

		if(!empty($merchant_balance['card_give_money'])&&$order_info['use_merchant_balance']){
			if($merchant_balance['card_give_money'] >= $pay_money){
				$data_shop_order['card_give_money'] = $pay_money;
				$order_result = $this->wap_pay_save_order($order_info,$data_shop_order);
				if($order_result['error_code']){
					return $order_result;
				}
				return $this->wap_after_pay_before($order_info);
			}else{
				$data_shop_order['card_give_money'] = $merchant_balance['card_give_money'];
			}
			$pay_money -= $merchant_balance['card_give_money'];
		}
		if($pay_money < 0){
			$pay_money = 0;
		}

        //使用企业预存款支付
        if ($order_info['qiye_balance'] == 1) {
            if (!D('Qiye')->checkTheSameQiye($now_user['uid'], $order_info['mer_id'])) {
                return array('error_code' => true, 'msg' => L_('同一企业商家和用户才能使用企业预存款'));
            }
            $staff = M('Qiye_staff')->where(['job_number' => $now_user['uid']])->find();
            if ($staff['money'] >= $pay_money) {
                $data_shop_order['qiye_pay'] = $pay_money;
                $order_info['qiye_pay'] = $pay_money;
                $order_result = $this->wap_pay_save_order($order_info, $data_shop_order);
                if ($order_result['error_code']) {
                    return $order_result;
                }
                $order_info['pay_money'] = $pay_money;
                return $this->wap_after_pay_before($order_info);
            } else {
                $data_shop_order['qiye_pay'] = $staff['money'];
            }
            $pay_money -= $staff['money'];
            $pay_money = round($pay_money * 100) / 100;
            $pay_money = $pay_money > 0 ? $pay_money : 0;
        }
 
		if(C('customization.life_tools') && $order_info['mer_id'] > 0){
			$condition = [];
			$condition['mer_id'] = $order_info['mer_id'];
			$condition['status'] = 1;
			$employeeCard = M('Employee_card')->where($condition)->find();

			if($employeeCard){
				$condition = [];
				$condition['uid'] = $now_user['uid'];
				$condition['card_id'] = $employeeCard['card_id'];
				$condition['status'] = 1;
				$employeeCardUser = M('Employee_card_user')->where($condition)->find();
				if($employeeCardUser){

					//员工卡积分支付
					if($order_info['employee_score'] == 1){
						if($employeeCardUser['card_score'] >= $pay_money){

							$data_shop_order['employee_score_pay'] = $pay_money;
							$order_info['employee_score_pay'] = $pay_money;
						}else{
							$data_shop_order['employee_score_pay'] = $employeeCardUser['card_score'];
							$order_info['employee_balance_pay'] = $employeeCardUser['card_score'];
						}

						$pay_money -= $employeeCardUser['card_score'];
						$pay_money = round($pay_money * 100) / 100;
						$pay_money = $pay_money > 0 ? $pay_money : 0;
					}
					
			
					//员工卡余额支付
					if($pay_money > 0 && $order_info['employee_balance'] == 1){
						if($employeeCardUser['card_money'] >= $pay_money){

							$data_shop_order['employee_balance_pay'] = $pay_money;
							$order_info['employee_balance_pay'] = $pay_money;

						}else{
							$data_shop_order['employee_balance_pay'] = $employeeCardUser['card_money'];
							$order_info['employee_balance_pay'] =  $employeeCardUser['card_money'];
						}

						$pay_money -= $employeeCardUser['card_money'];
						$pay_money = round($pay_money * 100) / 100;
						$pay_money = $pay_money > 0 ? $pay_money : 0;
					}
					

					if($data_shop_order['employee_score_pay'] > 0 || $data_shop_order['employee_balance_pay'] > 0){
						$data_shop_order['employee_card_user_id'] = $employeeCardUser['user_id'];
					}

					if($pay_money == 0){
						$order_result = $this->wap_pay_save_order($order_info, $data_shop_order);
						if ($order_result['error_code']) {
							return $order_result;
						}
						$order_info['pay_money'] = $pay_money;
						return $this->wap_after_pay_before($order_info);
					}
				}

			}
			
		}
		fdump_api([$order_info, $now_user, $pay_money], 'shop_order/wap_before_pay', 1);
		//判断帐户余额
		if(!empty($now_user['now_money']) && $order_info['use_balance']){
			$now_user['now_money'] = round($now_user['now_money'] * 100)/100;
			if($now_user['now_money'] >= $pay_money){
				$data_shop_order['balance_pay'] = $pay_money;
				$order_info['balance_pay'] = $pay_money;
				$order_result = $this->wap_pay_save_order($order_info,$data_shop_order);
				if($order_result['error_code']){
					return $order_result;
				}
				$order_info['pay_money'] = $pay_money;
				return $this->wap_after_pay_before($order_info);
			}else{
				$data_shop_order['balance_pay'] = $now_user['now_money'];
			}
			$pay_money -= $now_user['now_money'];
			$pay_money = round($pay_money * 100)/100;
		}
		//================================一卡通支付金额================================
		if(!empty($_POST['internal_card_ids'])){
			if(is_string($_POST['internal_card_ids'])){//兼容下字符串
				$_POST['internal_card_ids'] = explode(',', $_POST['internal_card_ids']);
			}
			try{
				$cardPayInfo = D('InternalMembershipCard')->getInternalCardPay(
					$_POST['internal_card_ids'],
					$pay_money,
					$order_info['store_id'],
					$now_user['uid']
				);
				$data_shop_order['internal_card_pay_money'] = $pay_money - $cardPayInfo['pay_money'];
				//校验卡余额
				$pay_money = $cardPayInfo['pay_money'];
				$save_data['internal_card_pay'] = $cardPayInfo['internal_card_pay'];
				M('Shop_order')->where(array('order_id' => $_POST['order_id']))->save($save_data);
			}catch (\Exception $e){
				return array('error_code' => true, 'msg' => $e->getMessage());
			}
		}
		if($pay_money > 0){
			$this->wap_pay_save_order($order_info,$data_shop_order);
		}
		//================================一卡通支付金额结束================================
		if($pay_money < 0){
			$pay_money = 0;
		}

		if($pay_money == 0){
			return $this->wap_after_pay_before($order_info);
		}

		//在线支付
		$order_result = $this->wap_pay_save_order($order_info, $data_shop_order);
		if($order_result['error_code']){
			return $order_result;
		}
		return array('error_code' => false, 'pay_money' => $pay_money);
	}

	/**
	 * @desc 处理本次使用的余额付款，将余额保存到订单
	 * @param $order_info
	 * @param $data_shop_order
	 * @return array
	 */
	public function wap_pay_save_order($order_info, $data_shop_order)
	{
        $where['order_id']=$condition_shop_order['order_id'] 		= $order_info['order_id'];
        /*重新查询订单，预售的单子需要计算平台余额支付的钱*/
        $res=$this->where($where)->find();
		$data_shop_order['coupon_id'] 			= $data_shop_order['coupon_id'] > 0 ? intval($data_shop_order['coupon_id']) : 0;
		$data_shop_order['card_id'] 			= $data_shop_order['card_id'] > 0 ? intval($data_shop_order['card_id']) : 0;
		$data_shop_order['card_price'] 			= $data_shop_order['card_price'] > 0 ? floatval($data_shop_order['card_price']) : 0;
		$data_shop_order['coupon_price'] 		= $data_shop_order['coupon_price'] > 0 ? floatval($data_shop_order['coupon_price']) : 0;
		$data_shop_order['merchant_balance'] 	= $data_shop_order['merchant_balance'] > 0 ? floatval($data_shop_order['merchant_balance']) : 0;
		$data_shop_order['card_give_money'] 	= $data_shop_order['card_give_money'] > 0 ? floatval($data_shop_order['card_give_money']) : 0;
		$data_shop_order['card_discount'] 		= $data_shop_order['card_discount'] > 0 ? floatval($data_shop_order['card_discount']) : 0;
		$data_shop_order['balance_pay'] 		= $data_shop_order['balance_pay'] > 0 ? floatval($data_shop_order['balance_pay']) : 0;
		$data_shop_order['internal_card_pay_money'] = floatval($data_shop_order['internal_card_pay_money']);
        if(!empty($res['book_id'] && $res['book_status']==1) && !empty($res['book_time'])){
            $data_shop_order['balance_pay'] 		= $data_shop_order['balance_pay']+ $res['balance_pay']> 0 ? floatval($data_shop_order['balance_pay']+ $res['balance_pay']) : 0;
        }
		$data_shop_order['score_used_count']  	= $data_shop_order['score_used_count']  > 0 ? floatval($data_shop_order['score_used_count']) : 0;
		$data_shop_order['score_deducte']     	= $data_shop_order['score_deducte']  > 0 ? floatval($data_shop_order['score_deducte']) : 0;
		$data_shop_order['score_discount_type'] = $order_info['score_discount_type']  > 0 ? floatval($order_info['score_discount_type']) : 0;
		$data_shop_order['last_time'] 			= $_SERVER['REQUEST_TIME'];
		$data_shop_order['submit_order_time'] 	= $_SERVER['REQUEST_TIME'];
		switch($data_shop_order['sys_discount_type'])
		{
			case '1'://1减免运费
				$freight_charge = $order_info['freight_charge'] - $data_shop_order['coupon_price'];
				$data_shop_order['coupon_price'] = $freight_charge > 0 ? $data_shop_order['coupon_price'] : $order_info['freight_charge'];
				break;
			case '2'://2减免餐费
				$goods_price = $order_info['goods_price'] - $data_shop_order['coupon_price'];
				$data_shop_order['coupon_price'] = $goods_price > 0 ? $data_shop_order['coupon_price'] : $order_info['goods_price'];
				break;
		}
		if ($this->where($condition_shop_order)->data($data_shop_order)->save() !== false) {
			return array('error_code' => false, 'msg' => L_('保存订单成功！'));
		} else {
			return array('error_code' => true, 'msg' => L_('保存订单失败！请重试或联系管理员。'));
		}
	}

	/*
	 * 判断用户积分优惠券，并保存
	 * */
	public function check_coupon_score($now_user,$now_order)
	{
		$order_info = array(
				'order_id'			=>	$now_order['order_id'],
				'mer_id'			=>	$now_order['mer_id'],
				'order_type'		=>	'shop',
				'order_name'		=>	$now_order['name'],
				'store_id'		=>	$now_order['store_id'],
				'no_discount_money'		=>	$now_order['no_discount_money'],
				'order_num'			=>	0,
				'order_price'		=>	floatval($now_order['price']),
				'order_total_money'	=>	floatval($now_order['price']),
				'uid'	=>	$now_user['uid'],
		);
		$order_type  = $order_info['order_type'];

		$now_user['now_money'] = 0;
		$platform = 'weixin';

		$order_info['total_money'] = $order_info['order_total_money'];

		$tmp_order = $order_info;
		$tmp_order['total_money'] -= $tmp_order['no_discount_money'];

		//平台优惠券
		if ($tmp_order['total_money'] > $mer_coupon['discount']) {
			$now_coupon = D('System_coupon')->get_noworder_coupon_list($tmp_order, $order_type, '', $now_user['uid'], $platform);
			$tmp_coupon = reset($now_coupon);
			if($tmp_coupon){
				$system_coupon['had_id'] = $tmp_coupon['id'];
				$system_coupon['order_money'] = $tmp_coupon['order_money'];
				$system_coupon['discount'] = $tmp_coupon['discount'];
			}else{
				$system_coupon =array();
			}
		}else{
			$system_coupon = array();
		}

		$tmp_order['total_money'] -=$system_coupon['discount'];
		if($tmp_order['total_money']<0){
			$tmp_order['total_money']= 0;
		}

		$tmp_order['total_money']+=$tmp_order['no_discount_money'];

		// 计算积分可抵扣
		$score_can_use_count=0;
		$score_deducte=0;

		$type_ =$order_type;
		$user_score_use_condition=C('config.user_score_use_condition');
		$user_score_max_use=D('Percent_rate')->get_max_core_use($order_info['mer_id'],$type_,$tmp_order['total_money']);

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

		//子商家设置不允许使用平台积分抵扣
		if($now_user['forzen_score'] == 1){
			$score_can_use_count = 0;
			$score_deducte = 0;
		}
		$tmp_order['total_money']=$tmp_order['total_money']*100-$score_deducte*100;

		$arr['coupon_id'] = intval($system_coupon['had_id']);
		$arr['coupon_price'] = floatval($system_coupon['discount']);
		$arr['score_used_count'] = $score_can_use_count;
		$arr['score_deducte'] = $score_deducte;
		$arr['total_money'] = $tmp_order['total_money']/100;
		return $arr;
	}

	//如果无需调用在线支付，使用此方法即可。
	public function wap_after_pay_before($order_info)
	{
		$order_param = array(
				'order_id' => $order_info['order_id'],
				'orderid' => $order_info['orderid'],
				'pay_type' => '',
				'third_id' => '',
				'is_mobile' => $order_info['is_mobile'],
				'pay_money' => 0,
				'order_total_money' => $order_info['order_total_money'],
				'balance_pay' => $order_info['balance_pay'],
				'merchant_balance' => $order_info['merchant_balance'],
				'order_type' => 'shop',
			);
			$result_after_pay = $this->after_pay($order_param);
			if($result_after_pay['error']){
				return array('error_code' => true,'msg'=>$result_after_pay['msg']);
			}
			if ($order_info['order_from'] == 1) {
				if($order_info['group_id']>0){
					$back_url=C('config.site_url').U('Wap/Mall/group_order_detail',array('order_id'=>$order_info['order_id'],'mer_id'=>$order_info['mer_id'],'store_id'=>$order_info['store_id']));
				}else{
					$back_url=C('config.site_url').U('Wap/Mall/status',array('order_id'=>$order_info['order_id'],'mer_id'=>$order_info['mer_id'],'store_id'=>$order_info['store_id']));
				}
				$back_url=str_replace('/source/','/',$back_url);
                $back_url = str_replace('appapi.php', 'wap.php', $back_url);
				return array('error_code'=>false,'msg'=>L_('支付成功'),'url'=>$back_url);

			} else {
                if (C('config.map_config') == 'google') {
                    $url = packapp_url('my/shop_order.html?order_id=' . $order_info['order_id'], D('User')->get_user($order_info['uid']));
                } else {
                    $url = C('config.site_url') . D('House_village')->base_url . "pages/shop_new/orderDetail/orderDetail?order_id=" . $order_info['order_id'];
                }
				return array('error_code'=>false,'msg'=>L_('支付成功！'),'url' => $url);
			}
	}

	//生成周期购商品配送数据
	public function update_periodic_deliver($info){
		if(!$info) return false;
		$deliver_goods = M('shop_order_detail')->where(array('order_id'=>$info['order_id']))->field('goods_id')->select();
		if(!empty($deliver_goods)){
			foreach ($deliver_goods as $key => $val) {
				D('Mall_periodic_purchase')->create_deliver($info['order_id'],$val['goods_id'],$info['periodic_appoint_day']);
			}
		}
	}

	public function after_pay($order_param)
	{
	    fdump_sql($order_param,'ID1016266');
		if ($order_param['pay_type'] != '') {
			$where['orderid'] = $order_param['order_id'];
		} else {
			$where['order_id'] = $order_param['order_id'];
		}

		$now_order = $this->field(true)->where($where)->find();
		if (empty($now_order)) {
			return array('error' => 1, 'msg' => L_('当前订单不存在！'));
		}
		$uniqueKey = md5($now_order['order_id'] . 'shopOrderAfterPay');
		$lockRs = setLock($uniqueKey, 10);
		if ($lockRs['error_no']) {
			return array('error' => 1, 'msg' => $lockRs['error_msg']);
		}
		if ($now_order['paid'] == 1) {
			if ($order_param['is_mobile']) {
				if ($order_param['order_from'] == 1) {
					return array('error' => 1, 'msg' => L_('该订单已付款！'), 'url' => U('Wap/Mall/status', array('order_id' => $now_order['order_id'], 'mer_id' => $now_order['mer_id'], 'store_id' => $now_order['store_id'])));
				} else {
					return array('error' => 1, 'msg' => L_('该订单已付款！'), 'url' => U('Wap/Shop/status', array('order_id' => $now_order['order_id'], 'mer_id' => $now_order['mer_id'], 'store_id' => $now_order['store_id'])));
				}
			} else {
				return array('error' => 1, 'msg' => L_('该订单已付款！'), 'url' => U('User/Index/shop_order_view', array('order_id' => $now_order['order_id'])));
			}
		} else {
			//得到当前用户信息，不将session作为调用值，因为可能会失效或错误。
			$now_user = D('User')->get_user($now_order['uid']);
			if(empty($now_user)){
				return array('error'=>1,'msg'=>L_('没有查找到此订单归属的用户，请联系管理员！'));
			}
			$card_price = 0;
			if($now_order['card_id']){
				$now_coupon = D('Card_new_coupon')->get_coupon_by_id($now_order['card_id']);
				$card_price = $now_coupon['price'];
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

			//判断会员卡余额
			$merchant_balance = floatval($now_order['merchant_balance']);
			if($merchant_balance){
				if ($now_order['union_mer_id']) {
					// 商家联盟会员卡
					$user_merchant_balance = D('Card_new')->get_card_by_uid_and_mer_id($now_order['uid'],$now_order['union_mer_id']);
				}else{
					$user_merchant_balance = D('Card_new')->get_card_by_uid_and_mer_id($now_order['uid'],$now_order['mer_id']);
				}
				if($user_merchant_balance['card_money'] < $merchant_balance){
					return $this->wap_after_pay_error($now_order,$order_param,L_('您的会员卡余额不够此次支付！'));
				}
			}

			$card_give_money = floatval($now_order['card_give_money']);
			if($card_give_money){
				if ($now_order['union_mer_id']) {
					// 商家联盟会员卡
					$user_merchant_balance = D('Card_new')->get_card_by_uid_and_mer_id($now_order['uid'],$now_order['union_mer_id']);
				}else{
					$user_merchant_balance = D('Card_new')->get_card_by_uid_and_mer_id($now_order['uid'],$now_order['mer_id']);
				}
				if($user_merchant_balance['card_money_give'] < $card_give_money){
					return $this->wap_after_pay_error($now_order,$order_param,L_('您的会员卡余额不够此次支付！'));
				}
			}
			//判断帐户余额
			$balance_pay = floatval($now_order['balance_pay']);
			if($balance_pay){
				if($now_user['now_money'] < $balance_pay){
					return $this->wap_after_pay_error($now_order,$order_param,L_('您的帐户余额不够此次支付！'));
				}
			}

			//企业预存款
            $qiye_balance = floatval($now_order['qiye_pay']);
            if ($qiye_balance) {
                $staff = M('Qiye_staff')->where(['job_number' => $now_user['uid']])->find();
                if (!$staff || $staff['money'] < $qiye_balance) {
                    return $this->wap_after_pay_error($now_order, $order_param, L_('您的企业预付款余额不够此次支付！'));
                }
            }

			//员工卡支付
			if(C('customization.life_tools') && $now_order['employee_card_user_id'] > 0){
				$condition = [];
				$condition['uid'] = $now_user['uid'];
				$condition['user_id'] = $now_order['employee_card_user_id'];
				$condition['status'] = 1;
				$employeeCardUserModel = M('Employee_card_user');
				$employeeCardUser = $employeeCardUserModel->where($condition)->find();
				if(!$employeeCardUser){
					return $this->wap_after_pay_error($now_order, $order_param, L_('支付的员工卡账户不存在！'));
				}
				//员工卡积分
				$employee_score = floatval($now_order['employee_score_pay']);
				if ($employee_score && $employee_score > $employeeCardUser['card_score']) {
					return $this->wap_after_pay_error($now_order, $order_param, L_('您的员工卡积分不够此次支付！'));
				}
				//员工卡余额
				$employee_balance = floatval($now_order['employee_balance_pay']);
				if ($employee_balance && $employee_balance > $employeeCardUser['card_money']) {
					return $this->wap_after_pay_error($now_order, $order_param, L_('您的员工卡余额不够此次支付！'));
				}
			}
			if($now_order['card_id']){
				$use_result = D('Card_new_coupon')->user_coupon($now_order['card_id'],$now_order['order_id'],$order_param['order_type'],$now_order['mer_id'],$now_order['uid']);
				if($use_result['error_code']){
					return array('error'=>1,'msg'=>$use_result['msg']);
				}
			}
			//如果使用了平台优惠券
            if ($now_order['coupon_id'] && $now_order['coupon_id'] < Membership_cardModel::HADPULL_BEGIN_ID) {
				$use_result = D('System_coupon')->user_coupon($now_order['coupon_id'],$now_order['order_id'],$order_param['order_type'],$now_order['mer_id'],$now_order['uid']);
				if($use_result['error_code']){
					return array('error'=>1,'msg'=>$use_result['msg']);
				}
			}
			if ($now_order['coupon_id'] && $now_order['coupon_id'] >= Membership_cardModel::HADPULL_BEGIN_ID && $now_order['is_open_membership_card'] != 1) {
				return array('error' => 1, 'msg' => '平台会员卡优惠券使用出错');
			}
			//如果使用了平台E卡
			if(!empty($now_order['ecard_password'])){
				D('Ecard_coupon')->add_ecard_log($now_order,'shop');
			}

			//判断是否是商城订单
			$alias_name=C('config.shop_alias_name');
			$from_type='shop';
			if($now_order['order_from']==1){
				$from_type='mall';
				$alias_name=C('config.mall_alias_name');
				$alias_name=$alias_name?$alias_name:L_('商城');
			}
			//如果用户使用了积分抵扣，则扣除相应的积分
			//判断积分数量是否正确
			$score_used_count = $now_order['score_used_count'];
			if($score_used_count && $score_used_count*1 && $now_user['score_count'] < $score_used_count){
				return array('error' => 1, 'msg' => L_('保存订单失败！请重试或联系管理员。'));
			}

			if ($score_used_count>0) {
			    $use_result = D('User')->user_score($now_order['uid'], $score_used_count, L_("购买X1商品 ，扣除X2 ，订单编号X3",array("X1" => $alias_name,"X2" => C('config.score_name'),"X3" => $now_order['real_orderid'])));
				if ($use_result['error_code']) {
					return array('error' => 1, 'msg' => $use_result['msg']);
				}
			}
			if($merchant_balance){
				if ($now_order['union_mer_id']) {
			    	$use_result = D('Card_new')->use_money($now_order['uid'],$now_order['union_mer_id'],$merchant_balance,L_("购买X1商品，扣除会员卡余额 ，订单编号X2",array("X1" => $alias_name,"X2" => $now_order['real_orderid'])));
                }else{
			    	$use_result = D('Card_new')->use_money($now_order['uid'],$now_order['mer_id'],$merchant_balance,L_("购买X1商品，扣除会员卡余额 ，订单编号X2",array("X1" => $alias_name,"X2" => $now_order['real_orderid'])));
                }
				if($use_result['error_code']){
					return array('error'=>1,'msg'=>$use_result['msg']);
				}
			}

			if($card_give_money){
				if ($now_order['union_mer_id']) {
			    	$use_result = D('Card_new')->use_give_money($now_order['uid'],$now_order['union_mer_id'],$card_give_money,L_("购买X1商品，扣除会员卡赠送余额 ，订单编号X2",array("X1" => $alias_name,"X2" => $now_order['real_orderid'])));
                }else{
			    	$use_result = D('Card_new')->use_give_money($now_order['uid'],$now_order['mer_id'],$card_give_money,L_("购买X1商品，扣除会员卡赠送余额 ，订单编号X2",array("X1" => $alias_name,"X2" => $now_order['real_orderid'])));
                }
				fdump_sql($use_result, 'shop_order_after_pay/card_give_money_pay');
				if($use_result['error_code']){
					return array('error'=>1,'msg'=>$use_result['msg']);
				}
			}


			//如果用户使用了余额支付，则扣除相应的金额。
           /*如果是预售，则扣款的记录变化一下*/
            if($now_order['book_id']>0 && $now_order['book_status']==0){
                $balance_pay=$now_order['bargin_price'];
            }elseif ($now_order['book_id']>0 && $now_order['book_status']==1){
                $balance_pay=$now_order['rest_price'];
            }
			if (!empty($balance_pay)) {
				$use_result = D('User')->user_money($now_order['uid'], $balance_pay, L_("购买X1商品，扣除余额，订单编号X2",array("X1" => $alias_name,"X2" => $now_order['real_orderid'])));
                $fdumpData = [
                    'uid' => $now_order['uid'],
                    'balance_pay' => $balance_pay,
                    'desc' => L_("购买X1商品，扣除余额，订单编号X2", array("X1" => $alias_name, "X2" => $now_order['real_orderid'])),
                    'result' => $use_result
                ];
                fdump_sql($fdumpData, 'shop_order_after_pay/balance_pay');
				if ($use_result['error_code']) {
					return array('error' => 1, 'msg' => $use_result['msg']);
				}
			}

            //扣除企业预存款
            if (!empty($qiye_balance)) {
                $qiye_result = D('User')->use_qiye_money($now_order['uid'], $qiye_balance, $now_order['order_id'], L_("购买X1商品，扣除余额，订单编号X2", array("X1" => $alias_name, "X2" => $now_order['real_orderid'])),'shop',false);
                if ($qiye_result['error_code']) {
                    return array('error' => 1, 'msg' => $use_result['msg']);
                }
                $qiyeSettlement['qiye_settlement_ratio'] = $qiye_result['retval']['qiye_settlement_ratio'];
                $qiyeSettlement['qiye_settlement_money'] = $qiye_result['retval']['qiye_settlement_money'];
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
						return array('error'=>1,'msg'=>L_('您的会员卡'. $card_id .'余额不足'));
					}
					$save_data['balance_money'] = $card_info['balance_money'] - $card_pay_money;
					$save_data['last_time'] = $time;
					if(!$card->where(array('card_id' => $card_id))->save($save_data)){
						return array('error'=>1,'msg'=>L_('会员卡'. $card_id .'支付失败!'));
					}
					$condition_card_log['card_id'] = $card_id;
					$condition_card_log['operation_type'] = 2; //交易
					$condition_card_log['before_balance'] = $card_info['balance_money'];
					$condition_card_log['after_balance'] = $save_data['balance_money'];
					$condition_card_log['money'] = $card_pay_money;
					$condition_card_log['business_type'] = 'shop';
					$condition_card_log['order_id'] = $now_order['real_orderid'];
					$condition_card_log['uid'] = $now_order['uid'];
					$condition_card_log['describe'] = '快店买单, 扣除余额'. $card_pay_money .'元, 订单编号'.$now_order['real_orderid'];
					$condition_card_log['mer_id'] = $now_order['mer_id'];
					$condition_card_log['store_id'] = $now_order['store_id'];
					$condition_card_log['create_time'] = $time;
					$internal_card_log->add($condition_card_log);
					$internal_card_pay += $card_pay_money;
					D('Internal_card')->send_wx_msg($card_id, '-'.getFormatNumber($card_pay_money), '快店买单');
				}
			}
			//扣除员工卡
			if(C('customization.life_tools')){
				$time = time();
				$employeeCondition = [
					'user_id'	=>	$now_order['employee_card_user_id']
				];
				$shop_alias_name = c('config.shop_alias_name');
				$employeeCardLogModel = M('Employee_card_log');
				if($employee_score){
					$employeeSaveData = [
						'card_score'	=>	$employeeCardUser['card_score'] - $employee_score,
						'last_time'		=>	$time
					];
					$employeeCardUserModel->where($employeeCondition)->data($employeeSaveData)->save();
					$employeeCardLogModel->add([
						'card_id'		=>	$employeeCardUser['card_id'],
						'user_id'		=>	$employeeCardUser['user_id'],
						'mer_id'		=>	$employeeCardUser['mer_id'],
						'uid'			=>	$employeeCardUser['uid'],
						'num'			=>	$employee_score,
						'type'			=>	'score',
						'change_type'	=>	'decrease',
						'description'	=>	 '购买' . $shop_alias_name . '商品，抵扣积分'. $employee_score . '，订单号：' . $now_order['real_orderid'],
						'user_desc'		=>	 '购买' . $shop_alias_name . '商品，抵扣积分'. $employee_score,
						'operate_type'	=>	'user',
						'operate_id'	=>	$now_order['uid'],
						'add_time'		=>	$time,
						'log_type'		=>	10,
						'pay_type'		=>	'score'
					]);
				}
				if($employee_balance){
					$employeeSaveData = [
						'card_money'	=>	$employeeCardUser['card_money'] - $employee_balance,
						'last_time'		=>	$time
					];
					$employeeCardUserModel->where($employeeCondition)->data($employeeSaveData)->save();
					$employeeCardLogModel->add([
						'card_id'		=>	$employeeCardUser['card_id'],
						'user_id'		=>	$employeeCardUser['user_id'],
						'mer_id'		=>	$employeeCardUser['mer_id'],
						'uid'			=>	$employeeCardUser['uid'],
						'num'			=>	$employee_balance,
						'type'			=>	'money',
						'change_type'	=>	'decrease',
						'description'	=>	'购买' . $shop_alias_name . '商品，消费余额'. $employee_balance . '元，订单号：' . $now_order['real_orderid'],
						'user_desc'		=>	 '购买' . $shop_alias_name . '商品，消费余额'. $employee_balance . '元',
						'operate_type'	=>	'user',
						'operate_id'	=>	$employeeCardUser['uid'],
						'add_time'		=>	$time,
						'log_type'		=>	10,
						'pay_type'		=>	'money'
					]);
				}

			}

			if($now_order['order_from']==1){
				if($now_order['group_id']>0){
					/*拼团商品处理事件*/
					D('Mall_group_team')->check_group_order($now_order);
				}elseif($now_order['bar_id']>0){
					//砍价商品处理事件
					D('Mall_bargain_team')->check_bargain_order($now_order);
				}
			}

            $data_shop_order = array();
            if (isset($qiyeSettlement['qiye_settlement_ratio'])) {
                $data_shop_order['qiye_settlement_ratio'] = $qiyeSettlement['qiye_settlement_ratio'];
            }
            if (isset($qiyeSettlement['qiye_settlement_money'])) {
                $data_shop_order['qiye_settlement_money'] = $qiyeSettlement['qiye_settlement_money'];
            }
            //支付时间直接取请求时间，防止每种支付方式返回的支付成功时间不同时区导致计算时间戳错误
            $data_shop_order['pay_time'] = $_SERVER['REQUEST_TIME'];

			$data_shop_order['payment_money'] = floatval($order_param['pay_money']);//在线支付的钱
			$data_shop_order['pay_type'] = $order_param['pay_type'];
			$data_shop_order['third_id'] = $order_param['third_id'];
			if (($now_order['order_from'] == 2 || $now_order['order_from'] == 3) && empty($order_param['pay_type'])) {
			    $data_shop_order['is_mobile_pay'] = 2;
			} else {
			    $data_shop_order['is_mobile_pay'] = $order_param['is_mobile'];
			}
			$data_shop_order['is_own'] = $order_param['is_own']??(isset($order_param['sub_mch_id'])?2:$order_param['is_own']);

			if($now_order['book_id']>0){//商城预售订单

				if($now_order['book_status']==0){
					$time = time();
					$data_shop_order['book_status'] = 1;
					$data_shop_order['book_time'] = $time;
					$book_act = D('Mall_book_goods')->get_info($now_order['book_id']);
					if($book_act['rest_pay_type']==2){//尾款支付时间是多少天之后
						$data_shop_order['expect_rest_time_start'] = $time+$book_act['rest_day']*60*60*24;
						$data_shop_order['expect_rest_time_end'] = $time+$book_act['rest_day']*60*60*24;
					}

				}elseif($now_order['book_status']==1){

					$data_shop_order['book_status'] = 2;
					$data_shop_order['rest_time'] = time();

					$data_shop_order['paid'] = 1;
				}
			}else{
				$data_shop_order['paid'] = 1;
			}

			$data_shop_order['price'] = $now_order['price'];
			if ($now_order['card_discount']) {
				$data_shop_order['price'] = sprintf("%.2f", ($now_order['price']-$now_order['freight_charge']) * $now_order['card_discount']/10)+$now_order['freight_charge'];//floor($now_order['price'] * $now_order['card_discount'] * 10) / 100;
			}

			//期望送达时间的修改
            $data_shop_order['expect_use_time'] = $now_order['expect_use_time'] + strtotime(date("Y-m-d H:i",$data_shop_order['pay_time'] - $now_order['create_time']));
            if(true){
                $store = M('Merchant_store_shop')->alias('shop')
                    ->join(C('DB_PREFIX') . 'merchant_store AS s ON s.store_id = shop.store_id')
                    ->where(array('s.store_id' => $now_order['store_id']))
                    ->find();

			    if($now_order['is_pick_in_store'] == 0){
                    $send_time = $store['s_send_time'] * 60;

					//如果设置了配送时速，则重新计算
					$deliverReturn = D('Deliver_set')->getDeliverInfo($store);
					$order_distance_km = $now_order['distance'] / 1000;
					if (getFormatNumber($deliverReturn['deliver_speed_hour']) && isset($order_distance_km)) {    //按时速计算
						$send_time =  $order_distance_km / $deliverReturn['deliver_speed_hour'] * 3600 + ($deliverReturn['deliver_delay_time'] * 60);
					}
					fdump('order_distance_km: ' . $order_distance_km, 'expect_time_auto', true);
					fdump('send_time: ' . $send_time, 'expect_time_auto', true);
					fdump('deliverReturn', 'expect_time_auto', true);
					fdump($deliverReturn, 'expect_time_auto', true);
					$data_shop_order['expect_pick_time'] = $now_order['expect_use_time'] - $send_time;

                }elseif($now_order['is_pick_in_store']==1){
					$send_time = $store['send_time'] * 60;
                    //如果设置了配送时速，则重新计算
                    $deliverReturn = D('Deliver_set')->getDeliverInfo($store);
                    $order_distance_km = $now_order['distance'] / 1000;
                    if (getFormatNumber($deliverReturn['deliver_speed_hour']) && isset($order_distance_km)) {    //按时速计算
                        $send_time =  $order_distance_km / $deliverReturn['deliver_speed_hour'] * 3600 + ($deliverReturn['deliver_delay_time'] * 60);
                    }
					$data_shop_order['expect_pick_time'] = $now_order['expect_use_time'] - $send_time;
                }elseif($now_order['is_pick_in_store']==2){
					$data_shop_order['expect_pick_time'] = $now_order['expect_use_time'];
				}

                $diffTime = 60;
                if ($store['send_time_type'] == 1) {
                    $diffTime = 3600;
                } elseif ($store['send_time_type'] == 2) {
                    $diffTime = 86400;
                } elseif ($store['send_time_type'] == 3) {
                    $diffTime = 86400 * 7;
                } elseif ($store['send_time_type'] == 4) {
                    $diffTime = 86400 * 30;
                }
                $work_time = $store['work_time'] * $diffTime;
                if(C('config.expect_time_auto')==1 && ($data_shop_order['expect_use_time'] - time()) < ($send_time + $work_time)){
                    $data_shop_order['expect_use_time'] = $data_shop_order['pay_time'] + $send_time + $work_time;
                    if ($now_order['is_pick_in_store'] == 0) {
                        $data_shop_order['expect_pick_time'] = $data_shop_order['expect_use_time'] - $send_time;
                    } else if ($now_order['is_pick_in_store'] == 2) {
                        $data_shop_order['expect_pick_time'] = $data_shop_order['expect_use_time'];
                    }
                }

				if(C('config.shop_order_booking_time') && $data_shop_order['expect_pick_time'] && $data_shop_order['expect_pick_time'] > time() + $work_time + 3600 * C('config.shop_order_booking_time')){
					fdump('expect_pick_time: ' . $data_shop_order['expect_pick_time'], 'expect_time_auto', true);
					fdump('work_time: ' . $work_time, 'expect_time_auto', true);
					fdump('shop_order_booking_time: ' . 3600 * C('config.shop_order_booking_time'), 'expect_time_auto', true);

					$data_shop_order['is_booking'] = '1';
                    $data_shop_order['is_handle'] = 0;
				}
            }

			//预计出单时间
            $data_shop_order['expect_take_time'] = $data_shop_order['expect_use_time'] - $work_time - $send_time;


            //会员卡折的金额
			$discountPrice = $now_order['coupon_price'] + $now_order['card_price'];
// 			$discountPrice = $now_order['price'] - $data_shop_order['price'] + $now_order['coupon_price'] + $now_order['card_price'];

            fdump_sql(['req_order_data' => $order_param, 'update_order_data' => $data_shop_order], 'ID1005617');

            if($now_order['order_from']==1){
                if($now_order['is_pick_in_store']==0){
                    $data_shop_order['status']=1;
                    $data_shop_order['order_status']=1;

                    //接单后打印标签
                    $orderDetail = D('Shop_order')->get_order_detail(array('order_id' => $now_order['order_id']), true);
                    D('Label_printer')->shopOrderLabel($orderDetail);
                }
            }

            if($this->where($where)->save($data_shop_order)){
                if ($now_order['upgrade_level'] > 0) {
                    D('User')->level_update($now_order['uid'], $now_order['upgrade_level'], $now_order['upgrade_level_price'], L_('下单升级会员，订单号：' . $now_order['real_orderid']));
				}

				//开通平台会员卡
				if ($now_order['is_open_membership_card']) {
					$sendRs = (new Membership_cardModel())->sendCard($now_order['uid'], $now_order['order_id'], 'shop');
					if ($sendRs) {
						//更新用户过期时间
						D('User')->where(['uid' => $now_order['uid']])->save(['membership_card_time' => $sendRs['expire_time']]);
					}
				}
                if ($now_order['reduce_id'] > 0) {
                    (new Membership_cardModel())->useOrderDeliverItem($now_order['reduce_id'], $now_order['order_id']);
                }

                //订单支付成功，清空购物车
                M('Cart')->where(['store_id' => $now_order['store_id'], 'uid' => $now_order['uid'], 'is_choose' => 1])->delete();

                if ($now_order['plat_discount'] > 0) {
                    D('Merchant_discount_log')->saveLog($now_order, 0);
                }
                $this->update_periodic_deliver($now_order);
                if(!($now_order['order_from']==1&&$order_param['pay_type']=='offline')){//商城线下支付不生成支付记录
			    	D('Shop_order_log')->add_log(array('order_id' => $now_order['order_id'], 'status' => 1));
				}

			    D("Merchant_store_shop")->where(array('store_id' => $now_order['store_id']))->setInc('sale_count', 1);
			    /*更新平台订单信息*/
			    D('System_order')->save_order_info($from_type,$now_order['order_id'],$data_shop_order);

			    M()->startTrans();
				try{
					/*****************20170518增加取单号**********************/
					$orderFetchNumber = $this->where($where)->field('fetch_number')->find();//已经有单号就不再生成了
					if ($now_order['order_from'] != 6 && $now_order['is_pick_in_store'] <= 2 && intval($orderFetchNumber['fetch_number']) <= 0) {
						$nowDay = date('Ymd', ($now_order['expect_use_time'] ? : time()));
						//修复跨天订单造成的取餐号紊乱
						//$nowDay = date('Ymd');
						if ($shop = D('Merchant_store_shop')
							->lock(true)
							->field('fetch_day, fetch_number')
							->where(array('store_id' => $now_order['store_id']))
							->find()) {
							if ($shop['fetch_day'] == $nowDay) {
								$fetch_number = $shop['fetch_number'] + 1;
								D('Merchant_store_shop')->where(array('store_id' => $now_order['store_id']))->setInc('fetch_number');
							} else {
								//改写新产生到新的表
								$fetch_number_where = ['store_id' => $now_order['store_id'], 'business' => 'shop', 'fetch_day' => $nowDay];
								$fetch_number = M('Merchant_store_fetch_number')->where($fetch_number_where)->getField('fetch_number');
								if($fetch_number){
									$fetch_number = $fetch_number + 1;
									M('Merchant_store_fetch_number')->where($fetch_number_where)->setInc('fetch_number');
								}else{
									$fetch_number = 1;
									$fetch_number_where['fetch_number'] = $fetch_number;
									M('Merchant_store_fetch_number')->data($fetch_number_where)->add();
								}
							}
						} else {
							$fetch_number = 0;
						}
						$this->where($where)->save(array('fetch_number' => $fetch_number));
					}
					fdump_api([
						'order' => $now_order,
						'fetch_number' => $fetch_number,
					],'shop_order/fetch_number',1);
					M()->commit();
				}catch (\Exception $e){
					fdump_api([
						'order' => $now_order,
						'fetch_number_msg' => $e->getMessage(),
						'fetch_number_error' => $e->getTraceAsString(),
					],'shop_order/fetch_number',1);

					M()->rollback();
				}
			    /*****************20170518增加取单号**********************/
			    //-----------------------add auto order 2017-03-06--------------------------------------
			    if (/*$now_order['is_pick_in_store'] != 2 && $now_order['is_pick_in_store'] != 3&&*/$now_order['order_from']<>1) {

			        $storeShop = D('Merchant_store_shop')->field(true)->where(array('store_id' => $now_order['store_id']))->find();
			        if ($storeShop && $storeShop['is_auto_order'] && $now_order['group_id']==0 && $now_order['is_weight']==0) {
			        	$store = D('Merchant_store')->field(true)->where(array('store_id' => $now_order['store_id']))->find();

                        //称重订单录入商品重量之后才推送给配送员
                        if ($now_order['is_weight'] == 0 && $now_order['is_pick_in_store'] != 3 && $data_shop_order['is_booking'] != 1) {
							fdump_api([
								'自动接单',
                                'order' => $data_shop_order
                            ],'shop_order/deliver',1);
                            $result = D('Deliver_supply')->saveOrder($now_order['order_id'], $store);
                            if ($result['error_code']) {
                                $this->where(array('order_id' => $now_order['order_id']))->save(array('status' => 0, 'order_status' => 0, 'last_time' => time()));
                                return array('error' => 1, 'msg' => $result['msg']);
                            }
                        }

			            $this->where(array('order_id' => $now_order['order_id']))->save(array('status' => 1, 'order_status' => 1, 'last_time' => time()));

                        D("System_order")->save_order_status($now_order['order_from'] == '1' ? 'mall' : 'shop',$now_order['order_id'],1);
						fdump_api(['after_pay1' => $now_order], 'shop_order/deliver',1);
			            D('Shop_order_log')->add_log(array('is_auto_accept_order' => true,'order_id' => $now_order['order_id'], 'status' => 2, 'name' => L_('自动接单'), 'phone' => '', 'note' => L_('店铺设置下单后自动接单')));

                        //接单后打印标签
                        $orderDetail = D('Shop_order')->get_order_detail(array('order_id' => $now_order['order_id']), true);
                        D('Label_printer')->shopOrderLabel($orderDetail);
			        }
			    }
			    //-----------------------add auto order 2017-03-06--------------------------------------
                if ($now_order['order_from']==1&&$order_param['pay_type']=='offline' && $now_order['is_pick_in_store']==0){//商城线下支付转平台配送生成配送员配送接单记录
                    $this->auto_set($now_order['order_id']);
                }
			    if ($now_order['cartid'] && ($orderTemp = D('Shop_order_temp')->field(true)->where(array('cartid' => $now_order['cartid']))->find())) {
			        $tempPrice = $data_shop_order['price'] - $now_order['card_price'] - $now_order['coupon_price'];
			        D('Shop_order_temp')->where(array('cartid' => $now_order['cartid']))->save(array('price' => floatval($tempPrice)));
			    }
				D('Scroll_msg')->add_msg($from_type,$now_user['uid'],L_('用户x1于x2购买x3成功',array('x1'=>str_replace_name($now_user['nickname']),'x2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),'x3'=>$alias_name)));
				if(C('config.open_score_pay_back') && C('config.system_take_spread_percent')>0 ){
					$spread_total_money = ($data_shop_order['balance_pay']+$data_shop_order['payment_money']+$data_shop_order['score_deducte']+$data_shop_order['coupon_price']+$data_shop_order['balance_reduce']-$now_order['no_bill_money']+$data_shop_order['merchant_balance']);
                    if ($now_order['order_from'] == 1) {
                        $percent_type = 'mall';
                    } else {
                        $percent_type = 'shop';
                    }
					$percent = D('Percent_rate')->get_percent($now_order['mer_id'],$percent_type,$spread_total_money, '',$now_order['store_id'], true);
					$spread_total_money = $spread_total_money*$percent/100;
				}else{
					$spread_total_money = $balance_pay + $data_shop_order['payment_money']-$now_order['freight_charge'];
				}

                //线下支付的重新计算推广金额
                if ($data_shop_order['pay_type'] == 'offline') {
                    $spread_total_money = $now_order['price'] - $now_order['freight_charge'];
                }

				if(C('config.add_spread_by_system_commission')){// 根据平台抽成获得佣金
					// 获取平台抽成金额
                    $update_order = array_merge($now_order, $data_shop_order);
					$update_order['order_type'] = 'shop';
					$update_order['get_system_take_money'] = true;
                    $spread_total_money =  D('SystemBill')->bill_method($update_order['is_own'],$update_order);
				}

				$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));


				$userMod = new UserModel();
				if(empty(C('config.open_single_spread')) && (!C('config.open_spread_appoint_user') || ($now_user['can_spread']==1 && !C('config.open_spread_only_once'))) &&$now_order['pay_type']!='offline' && C('config.open_user_spread')&& (C('config.spread_money_limit')==0 || C('config.spread_money_limit')<=$spread_total_money)&&empty(C('config.open_spread_rand'))){

					$now_store_shop = M('Merchant_store_shop')->join('as store_shop left join '.C('DB_PREFIX').'merchant_store store ON store_shop.store_id = store.store_id')->where(array('store_shop.store_id'=>$now_order['store_id']))->find();
					if($data_shop_order['is_own'] && C('config.own_pay_spread')==0){
						$data_shop_order['payment_money']=0;
					}
					$open_extra_price = C('config.open_extra_price');
					//上级分享佣金
					$spread_users[]  = $now_user['uid'];

					$spread_where['_string'] = "uid = '{$now_user['uid']}'";


					$now_user_spread = D('User_spread')->where($spread_where)->find();

                    //判断推广用户是否可以获得佣金
                    if ($now_user_spread && !($userMod->canSpread($now_user_spread['spread_uid']))) {
                        $now_user_spread = [];
                    }


					$spread_rate     = D('Percent_rate')->get_user_spread_rate($now_order['mer_id'], $from_type ,$now_order['order_id'],$now_order);
					$href            = C('config.site_url') . '/wap.php?g=Wap&c=My&a=spread_list&status=-1';
					if(!empty($now_user_spread)){
						$spread_user = D('User')->get_user($now_user_spread['spread_uid']);
						// $user_spread_rate = $now_store_shop['spread_rate'] > 0 ? $now_store_shop['spread_rate'] : C('config.user_spread_rate');
						$user_spread_rate = $spread_rate['first_rate'];
						if($now_order['is_pick_in_store']!=0){
							$now_order['freight_charge']=0;
						}
						if($spread_user && $user_spread_rate&&!in_array($spread_user['uid'],$spread_users)){
							if (C('config.shop_goods_spread_edit')==1 && ($spread_rate['type']=='shop' || $spread_rate['type']=='mall')) { // 开启每个商品单独佣金
								$spread_money = $user_spread_rate;
							}else{
								$spread_money = round($spread_total_money * $user_spread_rate / 100, 2);
							}

							$spread_data = array('uid'=>$spread_user['uid'],'spread_uid'=>0,'get_uid'=>$now_user['uid'],'money'=>$spread_money,'order_type'=>$from_type,'order_id'=>$now_order['order_id'],'third_id'=>$now_order['store_id'],'add_time'=>$_SERVER['REQUEST_TIME'],'level'=>1,'province_id'=>$now_store_shop['province_id'],'city_id'=>$now_store_shop['city_id'],'area_id'=>$now_store_shop['area_id']);
							if($spread_user['spread_change_uid']!=0){
								$spread_data['change_uid'] = $spread_user['spread_change_uid'];
							}

							$buy_user = D('User')->get_user($now_user_spread['uid']);
							if($spread_money>0){
                                // 处理长订单id
							    if ($now_order['real_orderid']) {
                                    $spread_data['real_orderid'] = $now_order['real_orderid'];
                                }
								D('User_spread_list')->data($spread_data)->add();
								// $model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $spread_user['openid'], 'first' => $buy_user['nickname'] . '通过您的分享购买了【' . $now_store_shop['name'] . '】的'.$alias_name.'商品，验证消费后您将获得佣金。', 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $now_order['mer_id']);
								$model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $spread_user['openid'], 'first' => L_("X1通过您的分享购买商品，验证消费后您将获得佣金。",array("X1" => $buy_user['nickname'])) , 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $now_order['mer_id']);
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
							// $sub_user_spread_rate = $now_store_shop['sub_spread_rate'] > 0 ? $now_store_shop['sub_spread_rate'] : C('config.user_first_spread_rate');
							$sub_user_spread_rate = $spread_rate['second_rate'];
							if ($second_user && $sub_user_spread_rate&&!in_array($second_user['uid'],$spread_users)) {
								if (C('config.shop_goods_spread_edit')==1 && ($spread_rate['type']=='shop' || $spread_rate['type']=='mall')) { // 开启每个商品单独佣金
									$spread_money = $sub_user_spread_rate;
								}else{
									$spread_money = round($spread_total_money * $sub_user_spread_rate / 100, 2);
								}
								$spread_data = array('uid' => $second_user['uid'], 'spread_uid' => $spread_user['uid'], 'get_uid' => $now_user['uid'], 'money' => $spread_money, 'order_type' => $from_type, 'order_id' => $now_order['order_id'], 'third_id' => $now_order['store_id'], 'add_time' => $_SERVER['REQUEST_TIME'],'level'=>2,'province_id'=>$now_store_shop['province_id'],'city_id'=>$now_store_shop['city_id'],'area_id'=>$now_store_shop['area_id']);
								if($second_user['spread_change_uid']!=0){
									$spread_data['change_uid'] = 	$second_user['spread_change_uid'];
								}
                                $sec_where['_string'] = "uid = '{$second_user_spread['uid']}'";
                                $sec_user = M('User')->where($sec_where)->find();

								if($spread_money>0) {
                                    // 处理长订单id
                                    if ($now_order['real_orderid']) {
                                        $spread_data['real_orderid'] = $now_order['real_orderid'];
                                    }
									D('User_spread_list')->data($spread_data)->add();
									if($open_extra_price){
										$money_name = C('config.extra_price_alias_name');
									}else{
										$money_name = L_('佣金');
									}
									// $model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $second_user['openid'], 'first' => $sec_user['nickname'] .'的子用户'.$buy_user['nickname']. '通过分享购买了【' . $now_store_shop['name']. '】的'.$alias_name.'商品，验证消费后您将获得'.$money_name.L_('。'), 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $now_order['mer_id']);
									$model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $second_user['openid'], 'first' => L_("X1的子用户X2通过分享购买商品，验证消费后您将获得X3。",array("X1" => $sec_user['nickname'],"X2" => $buy_user['nickname'],"X3" => $money_name)) , 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $now_order['mer_id']);
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
								// $sub_user_spread_rate = $now_store_shop['third_spread_rate'] > 0 ? $now_store_shop['third_spread_rate'] : C('config.user_second_spread_rate');
								$sub_user_spread_rate = $spread_rate['third_rate'];
								if ($first_spread_user && $sub_user_spread_rate&&!in_array($first_spread_user['uid'],$spread_users)) {
									if (C('config.shop_goods_spread_edit')==1 && ($spread_rate['type']=='shop' || $spread_rate['type']=='mall')) { // 开启每个商品单独佣金
										$spread_money = $sub_user_spread_rate;
									}else{
										$spread_money = round($spread_total_money * $sub_user_spread_rate / 100, 2);
									}
										$spread_data = array('uid' => $first_spread_user['uid'], 'spread_uid' => $second_user['uid'], 'get_uid' => $now_user['uid'], 'money' => $spread_money, 'order_type' => $from_type, 'order_id' => $now_order['order_id'], 'third_id' => $now_order['store_id'], 'add_time' => $_SERVER['REQUEST_TIME'],'level'=>3,'province_id'=>$now_store_shop['province_id'],'city_id'=>$now_store_shop['city_id'],'area_id'=>$now_store_shop['area_id']);
									if($first_spread_user['spread_change_uid']!=0){
										$spread_data['change_uid'] = 	$first_spread_user['spread_change_uid'];
									}
                                    $fir_where['_string'] = "uid = '{$first_user_spread['uid']}'";
                                    $fir_user = M('User')->where($fir_where)->find();

									// $fir_user = D('User')->get_user($first_user_spread['openid'], 'openid');
									if($spread_money>0) {
                                        // 处理长订单id
                                        if ($now_order['real_orderid']) {
                                            $spread_data['real_orderid'] = $now_order['real_orderid'];
                                        }
										D('User_spread_list')->data($spread_data)->add();
										// $model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $first_spread_user['openid'], 'first' => $fir_user['nickname'].'的子用户的子用户'.$buy_user['nickname'] . '通过您的分享购买了【' . $now_store_shop['name'] . '】的'.$alias_name.'商品，验证消费后您将获得佣金。', 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $now_order['mer_id']);
										$model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $first_spread_user['openid'], 'first' => L_("X1的子用户的子用户X2通过您的分享购买商品，验证消费后您将获得佣金。",array("X1" => $fir_user['nickname'],"X2" => $buy_user['nickname'])) , 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => '点击查看详情！'), $now_order['mer_id']);
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
							    D('Percent_rate')->add_spread_jifen_log($param,$from_type,$now_order['store_id']);
							}
						}
					}
				}elseif(!empty($now_user['openid'])&&C('config.open_spread_rand')){
		            /* 开启三级推广随机一级推广奖励功能，唯一次奖励 */
					//一级推广用户
					$spread_res = D('User_spread')->spread_rand_only_once($from_type,$now_user,$now_order);
		        }elseif(!empty($now_user['openid'])&&C('config.open_spread_appoint_user') && $now_user['can_spread']==1 && C('config.open_spread_only_once')&&(C('config.spread_money_limit')==0 || C('config.spread_money_limit')<=$spread_total_money)){
		            /* 开启指定用户享受三级推广功能且开启唯一次奖励 */
					//一级推广用户
					$spread_res = D('User_spread')->spread_user_only_once($from_type,$spread_total_money,$now_user,$now_order);
		        }

				//增加粉丝 5 group 6 shop 7 meal 8 appoint 9 store
				D('Merchant')->saverelation($now_user['openid'], $now_order['mer_id'], 6);
				$goodsDetailDB = D('Shop_order_detail');
				$goods = $goodsDetailDB->field(true)->where(array('order_id' => $now_order['order_id']))->select();
				$keyword2 = '';
				$pre = '';
				$goods_obj = D("Shop_goods");
				$i = 1;
				foreach ($goods as $gr) {
					if (empty($now_order['reduce_stock_type']) || $now_order['order_from'] == 6) {
						$goods_obj->update_stock($gr);//修改库存
					}
					if ($i < 5) {
    					$keyword2 .= $pre . $gr['name'] . ':' . $gr['price'] . '*' . $gr['num'];
    					$pre = '\n\t\t\t';
					} elseif ($i == 5) {
					    $keyword2 .= $pre . '...';
					}
					$i++;
					if ($discountPrice > 0 || ($now_order['card_discount'] != 10 && $now_order['card_discount'] != 0)) {

				        $goodsDetailDB->where(array('id' => $gr['id']))->save(array('pay_price' => floatval(round($gr['pay_price'] * ($now_order['card_discount']/10) - ($discountPrice*$gr['price']/($now_order['total_price'])), 2))));

					}
				}
				if(C('customization.internal_membership_card')){
					invoke_v20_service('\app\shop\model\service\order\ShopOrderService/uploadMiaomaiOrder',[$now_order['order_id']]);
				}
				/* 计算用户分销获得佣金 通过分享链接直接购买才有分佣一般只有一个商品*/
				if (C('config.open_single_spread')==1&&$now_order['distributor_uid']) {
					$now_goods = $goods_obj->where(array('goods_id'=>$goods[0]['goods_id']))->find();
					if ($now_goods['distributor_percent']) {
						$distributor_spread_data = array();
						$spread_total_money = $balance_pay + $data_shop_order['payment_money']-$now_order['freight_charge'];
						$distributor_spread_money = round($spread_total_money*$now_goods['distributor_percent']/100,2);
						if ($distributor_spread_money) {
							$distributor_spread_data =array(
								'spread_uid' => $now_order['distributor_uid'],
								'order_uid' => $now_order['uid'],
								'money' => $distributor_spread_money,
								'order_type' => 'mall',
								'order_id' => $now_order['order_id'],
								'real_orderid' => $now_order['real_orderid'],
								'third_id' => $now_goods['goods_id'],
								'add_time' => $_SERVER['REQUEST_TIME']
							);
							M('Distributor_spread_list')->data($distributor_spread_data)->add();
						}
					}
				}

                //微信小程序订阅消息提醒
                $wxapp_message_send = false;
                if($now_user['wxapp_openid'] && $now_order['order_from']!=1){
                    $param['page'] = '/pages/shop_new/orderDetail/orderDetail?order_id='.$now_order['order_id'];
                    $param['wxapp_openid'] = $now_user['wxapp_openid'];
                    $param['type'] = 'pay_success';
                    // 获得商品信息
                    $goods_name = D('Shop_order_detail')->where(['order_id'=>$now_order['order_id']])->order('id ASC')->find()['name'];
                    $goodsCount = D('Shop_order_detail')->field('sum(num) as total')->where(['order_id'=>$now_order['order_id']])->find()['total'];
                    $now_order['goods_desc'] = $goodsCount > 1 ? L_('X1等X2个商品',['X1'=>$goods_name,'X2'=>$goodsCount]) : $goods_name;
                    $now_order['pay_time'] = $data_shop_order['pay_time'];
                    $now_order['note'] = L_('X1订单已完成支付。',['X1'=>C('config.shop_alias_name')]);
                    $param['data_info'] = $now_order;

                    $wxapp_message_send = D('Wxapp_template')->sendWxappMessage($param);
                }

				//微信模板消息提醒
				if (($now_user['openid'] || $now_user['wxapp_openid']) && !$wxapp_message_send) {
					/*商城订单跳转到商城订单详情页*/
					if($now_order['order_from']==1){
						$href = C('config.site_url').'/wap.php?c=Mall&a=status&order_id='. $now_order['order_id'] . '&mer_id=' . $now_order['mer_id'] . '&store_id=' . $now_order['store_id'];
					}else{
						$href = C('config.site_url').'/wap.php?c=Shop&a=status&order_id='. $now_order['order_id'] . '&mer_id=' . $now_order['mer_id'] . '&store_id=' . $now_order['store_id'];
					}
					$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
					if ($order_param['pay_type'] == 'offline' && empty($order_param['third_id'])) {
						$model->sendTempMsg('OPENTM201752540', array('href' => $href, 'wecha_id' => $now_user['openid'], 'wxapp_openid' => $now_user['wxapp_openid'], 'first' => $alias_name.L_('提醒'), 'keyword2' => $now_order['real_orderid'], 'keyword1' => $keyword2, 'keyword3' => $now_order['price'], 'keyword4' => date('Y-m-d H:i:s'), 'remark' => L_('购买成功（线下未支付）！')), $now_order['mer_id']);
					} else {
						$model->sendTempMsg('OPENTM201752540', array('href' => $href, 'wecha_id' => $now_user['openid'], 'wxapp_openid' => $now_user['wxapp_openid'], 'first' => $alias_name.L_('提醒'), 'keyword2' => $now_order['real_orderid'], 'keyword1' => $keyword2, 'keyword3' => $now_order['price'], 'keyword4' => date('Y-m-d H:i:s'), 'remark' => L_('购买成功!')), $now_order['mer_id']);
					}
				}

				$offline = '';
				if ($order_param['pay_type'] == 'offline' && empty($order_param['third_id'])) {
					$offline = '('.L_('线下未支付').')';
				}

				//微信派发优惠券 支付到平台 微信支付
				if($data_shop_order['is_own']==0 &&  $data_shop_order['pay_type']=='weixin' && $data_shop_order['payment_money']>=C('config.weixin_send_money')){
					D('System_coupon')->weixin_send( $data_shop_order['payment_money'],$now_order['uid']);
				}

				//【直播+短视频平台】 https://www.tapd.cn/47145533/prong/stories/view/1147145533001002522
				$goodsDetailDB = D('Shop_order_detail');
				$goods = $goodsDetailDB->field(true)->where(array('order_id' => $now_order['order_id'], 'is_liveshow' => 1))->select();
				if($now_order['is_liveshow'] == '1' && $now_order['liveshow_id'] > 0 && !empty($goods)){
					//添加销量
					foreach ($goods as $gvalue) {
						D("Live_show_goods")->add_sale_nums($gvalue['goods_id'], $now_order['liveshow_id'], $gvalue['num'], $now_order['order_from'] == 1 ? 'mall' : 'shop');
					}
				}
				if($now_order['is_livevideo'] == '1' && $now_order['livevideo_id'] > 0 && !empty($goods)){
					//添加销量
					foreach ($goods as $gvalue) {
						D("Live_video_goods")->add_sale_nums($gvalue['goods_id'], $now_order['livevideo_id'], $gvalue['num'], $now_order['order_from'] == 1 ? 'mall' : 'shop');
					}
				}

				//小票打印
// 				$msg = ArrayToStr::array_to_str($now_order['order_id'], 'shop_order');
// 				$op = new orderPrint(C('config.print_server_key'), C('config.print_server_topdomain'));
// 				$op->printit($now_order['mer_id'], $now_order['store_id'], $msg, 1);

//                 $str_format = ArrayToStr::print_format($now_order['order_id'], 'shop_order');
// 				foreach ($str_format as $print_id => $print_msg) {
// 					$print_id && $op->printit($now_order['mer_id'], $now_order['store_id'], $print_msg, 1, $print_id);
// 				}
// 				$str_format = ArrayToStr::print_format($now_order['order_id'], 'shop_order', 1);
// 				foreach ($str_format as $print_id => $print_msg) {
// 					$print_id && $op->printit($now_order['mer_id'], $now_order['store_id'], $print_msg, 1, $print_id);
// 				}

// 				$msg = ArrayToStr::printOnlyOne($now_order['order_id'], 'shop_order');
// 				$op->printit($now_order['mer_id'], $now_order['store_id'], $msg, 1);

				//短信提醒
				$sms_data = array('mer_id' => $now_order['mer_id'], 'store_id' => $now_order['mer_id'], 'type' => 'shop');
				$store = D('Merchant_store')->field(true)->where(array('store_id' => $now_order['store_id']))->find();
				if (!($now_user['openid'] && $now_user['is_follow']) && (C('config.sms_shop_success_order') == 1 || C('config.sms_shop_success_order') == 3)){
					if (empty($now_order['phone'])) {
						$user = D('User')->field(true)->where(array('uid' => $now_order['uid']))->find();
						$now_order['phone'] = $user['phone'];
					}
					$sms_data['uid'] = $now_order['uid'];
					$sms_data['mobile'] = $now_order['userphone'];
					$sms_data['sendto'] = 'user';
					if ($data_shop_order['shop_pass']) {
						$sms_data['content'] = L_("您在X1中的订单号：X2，已经完成了支付X3！",array("X1" => $store['name'],"X2" => $now_order['real_orderid'],"X3" => $offline));
					} else {
						$sms_data['content'] = L_("您在X1中的订单号：X2，已经完成了支付X3！",array("X1" => $store['name'],"X2" => $now_order['real_orderid'],"X3" => $offline));
					}
					Sms::sendSms($sms_data);
				}
				if (C('config.sms_shop_success_order') == 2 || C('config.sms_shop_success_order') == 3) {
					$store_phpne = explode(' ',$store['phone']);
					if(count($store_phpne)>0){
						foreach($store_phpne as $phone){
							if(preg_match('/^[0-9]{11}$/',$phone)){
								$store['phone'] = $phone;
								$sms_data['uid'] = 0;
								$sms_data['mobile'] = $store['phone'];
								$sms_data['sendto'] = 'merchant';
								$sms_data['content'] = L_("顾客X1在X2时，将订单号：X3支付成功X4！",array("X1" => $now_order['username'] ? : ($now_order['pick_username'] ? : L_('未知')),"X2" => date("Y-m-d H:i:s"),"X3" => msubstr($now_order['real_orderid'], 0, 15),"X4" => $offline));
								Sms::sendSms($sms_data);
							}
						}
					}
					
				}

				if (($now_order['order_from'] == 6 && $now_order['is_pick_in_store'] == 2 && $now_order['staff_id'] > 0) || $now_order['order_from'] == 9) {
					$finish_data = array('order_status' => 5, 'status' => 2, 'use_time' => time());
					if ($this->where($where)->save($finish_data)) {
					    $now_order = array_merge($now_order, $data_shop_order);
						$this->shop_notice($now_order,true);
						D('Shop_order_log')->add_log(array('order_id' => $now_order['order_id'], 'status' => 15, 'name' => '', 'phone' => ''));
						D('Shop_order_log')->add_log(array('order_id' => $now_order['order_id'], 'status' => 6, 'name' => '', 'phone' => ''));
					}
				} else {
				    $printHaddle = new PrintHaddle();
				    $printHaddle->printit($now_order['order_id'], 'shop_order', 1);
				}

				/*对接用友erp系统 2019/02/20 */
				if(C('Config.open_yongyou_erp')==1){
					$this->erp_after_pay($now_order,1);
				}

                //快店订单写入预计商家收入
                if ($now_order['order_from'] != 1) {
                    (new Merchant_money_listModel())->compute_merchant_settlement_money($now_order['order_id']);
                }

				//-----------------------add auto order 2017-03-06--------------------------------------
//                 if ($now_order['is_pick_in_store'] != 2 && $now_order['is_pick_in_store'] != 3) {
//                     $storeShop = D('Merchant_store_shop')->field(true)->where(array('store_id' => $now_order['store_id']))->find();
//                     if ($storeShop && $storeShop['is_auto_order']) {
//                         $store = D('Merchant_store')->field(true)->where(array('store_id' => $now_order['store_id']))->find();
//                         $result = D('Deliver_supply')->saveOrder($now_order['order_id'], $store);
//                         if ($result['error_code']) {
//                             D('Shop_order')->where(array('order_id' => $now_order['order_id']))->save(array('status' => 0, 'order_status' => 0, 'last_time' => time()));
//                             return array('error' => 1, 'msg' => $result['msg']);
//                         }
//                         $this->where(array('order_id' => $now_order['order_id']))->save(array('status' => 1, 'order_status' => 1, 'last_time' => time()));
//                         D('Shop_order_log')->add_log(array('order_id' => $now_order['order_id'], 'status' => 2, 'name' => '自动接单', 'phone' => '', 'note' => '店铺设置下单后自动接单'));
//                     }
//                 }

                /*-----------------------------商城平台配送自动接单--------2020823-------------------*/
                //fdump($now_order,"dengyuanhui0099",1);
                  $my_status=0;
                if($now_order['order_from']==1 && $order_param['pay_type']!='offline' && $now_order['is_pick_in_store']==0){
                    if($now_order['group_id']>0 && $now_order['team_id']>0){
                        $where1['id']=$now_order['team_id'];
                        $where1['group_id']=$now_order['group_id'];
                        $status=M('Mall_group_team')->where($where1)->find();
                        $my_status=$status['status'];
                    }
                    $store = D('Merchant_store')->field(true)->where(array('store_id' => $now_order['store_id']))->find();
                    if($now_order['group_id']==0){
                        $result = D('Deliver_supply')->saveOrder($now_order['order_id'], $store);
                        if ($result['error_code']) {
                            $this->where(array('order_id' => $now_order['order_id']))->save(array('status' => 0, 'order_status' => 0, 'last_time' => time()));
                            return array('error' => 1, 'msg' => $result['msg']);
                        }
						fdump_api(['after_pay' => $now_order], 'shop_order/deliver',1);
                        D('Shop_order_log')->add_log(array('order_id' => $now_order['order_id'], 'status' => 2, 'name' => L_('自动接单'), 'phone' => '', 'note' => L_('店铺设置下单后自动接单')));
                    }elseif ($now_order['group_id']>0 && $now_order['team_id']>0 && $my_status==1){
                        $where1['team_id']=$now_order['team_id'];
                        $where1['group_id']=$now_order['group_id'];
                        $where1['paid']=1;
                        $group_list=M('Shop_order')->where($where1)->select();
                        if(!empty($group_list)){
                            foreach ($group_list as $key=>$value){
                                $result = D('Deliver_supply')->saveOrder($value['order_id'], $store);
                                if ($result['error_code']) {
                                    $this->where(array('order_id' => $value['order_id']))->save(array('status' => 0, 'order_status' => 0, 'last_time' => time()));
                                    return array('error' => 1, 'msg' => $result['msg']);
                                }
                                D('Shop_order_log')->add_log(array('order_id' => $value['order_id'], 'status' => 2, 'name' => L_('自动接单'), 'phone' => '', 'note' => L_('店铺设置下单后自动接单')));
                            }
                        }
                    }
                }
                //-----------------------add auto order 2017-03-06--------------------------------------
				/* 粉丝行为分析 */
// 				D('Merchant_request')->add_request($now_order['mer_id'], array('meal_buy_count' => $now_order['num'], 'meal_buy_money' => $now_order['price']));
				//记录下用户行为
				$order_detail = M('Shop_order_detail')->field('goods_id,name,num')->where(array('order_id'=>$now_order['order_id']))->select();
				if($order_detail){
					foreach($order_detail as $val){
						$business_type = $now_order['order_from'] == 1?0:1;
						$from_type=$this->change_from_type($now_order['order_from']);//来源：0wap端，1ios,2安卓，3小程序，4pc端，5移动端（即安卓，ios,小程序）
						D('User_behavior_goods')->add_record($val['goods_id'],$val['name'],$now_order['uid'],$business_type,$from_type,$now_order['order_id'],$val['num']);
					}
				}

				// 组合套餐购买人数纪录
				if ($now_order['com_id']) {
					M('Shop_combination')->where(['com_id'=>$now_order['com_id']])->setInc('sell_count',1);
				}

                //写入店铺新订单数量，暂时只写快店订单
                $order = $this->where(array('order_id' => $now_order['order_id']))->find();
                if ($order['order_from'] != 1 && $order['status'] == 0 && $order['order_status'] == 0) {
                    (new Merchant_store_shopModel())->shopNewOrderCountInc($order['store_id'], 1);
                }
                if($now_order['order_from'] != 1){
                	//新增消息通知
					$url = '\app\common\model\service\user\UserNoticeService/addNotice';
					$param['type'] = 0;
					$param['business'] = 'shop';
					$param['order_id'] = $now_order['order_id'];
					$param['mer_id'] = $now_order['mer_id'];
					$param['store_id'] = $now_order['store_id'];
					$param['title'] = '外卖下单成功';
					$param['content'] = '下单成功，商品将尽快为你送达';
					$param['uid'] = $now_order['uid'];
                	invoke_v20_service($url, [$param]);
				}

				//同步图维
				$this->saveTwOrder($now_order['order_id']);

				if ($order_param['is_mobile']) {
					if ($now_order['order_from'] == 1) {
						return array('error' => 0, 'url' => U('Wap/Mall/status', array('mer_id' => $now_order['mer_id'], 'store_id' => $now_order['store_id'], 'order_id' => $now_order['order_id'])));
					} else {
						return array('error' => 0, 'url' => U('Wap/Shop/status', array('mer_id' => $now_order['mer_id'], 'store_id' => $now_order['store_id'], 'order_id' => $now_order['order_id'])));
					}
				} else {
					return array('error' => 0, 'url' => U('User/Index/shop_order_view', array('order_id'=>$now_order['order_id'])));
				}
			} else {
				return array('error' => 1, 'msg' => L_('修改订单状态失败，请联系系统管理员！'));
			}
		}
	}

	/**
	 * @desc shop_order表中的order_from字段转换成User_behavior_goods表中的from_type字段
	 * @param $order_from:订单来源（0：wap快店，1：wap商城，2：Android，3：ios,4:小程序,5：pc快店,6:线下零售,7.饿了么，8.美团，9.扫码购）
	 * @return int：来源（0wap端，1安卓,2ios，3小程序，4pc端，5未知,6移动端）
	 */
	private function change_from_type($order_from){
		if($order_from==0||$order_from==1){
			return 0;
		}elseif($order_from==2){
			return 1;
		}elseif($order_from==3){
			return 2;
		}elseif($order_from==4){
			return 3;
		}elseif($order_from==5){
			return 4;
		}else{
			return 5;
		}
	}

	//修改订单状态
	public function change_status($order_id, $status,$type='shop')
	{
		$where['order_id'] = $order_id;
		$data['status'] = $status;
		/*更新平台订单状态*/
		D('System_order')->save_order_info($type,$order_id,$data);

		if ($this->where($where)->data($data)->save()) {
			return true;
		} else {
			return false;
		}
	}

    //检查称重订单是否需要退款
    public function checkWeightOrder($order){
	    if($order['is_weight']!=1){
	        return false;
        }
        $order_details = M('Shop_order_detail')->where(array('order_id'=>$order['order_id']))->select();
	    $lack_weight_money_all = 0;
	    foreach($order_details as $order_detail){
	        if($order_detail['sell_type']==2){
	            $order_weight = $order_detail['goods_weight'] * $order_detail['num'];
	            if($order_detail['real_weight']>=$order_weight){
	                //多出部分算商家补贴
                }else{ //不足部分给用户退款到平台余额
                    $lack_weight = $order_weight - $order_detail['real_weight'];
                    $total_pay = round($order_detail['pay_price'] * $order_detail['num'],2);
                    $lack_weight_money = round($total_pay * (round($lack_weight/$order_weight,2)),2);
                    if($lack_weight_money >= $total_pay) $lack_weight_money = $total_pay;
                    if($lack_weight_money > 0){
                        $pay_price = round($total_pay - $lack_weight_money,2);
                        $single_pay_price = round($pay_price / $order_detail['num'],2);
                        M('Shop_order_detail')->where(array('id'=>$order_detail['id']))->save(array('lack_weight_refund'=>$lack_weight_money,'pay_price'=>$single_pay_price));
                        $lack_weight_money_all += $lack_weight_money;
                    }
                }
            }
        }
        if($lack_weight_money_all > 0){
            $add_result = D('User')->add_money($order['uid'],$lack_weight_money_all,'订单称重商品重量不足部分退款，增加用户余额.订单编号：'.$order['real_orderid'], 18, $order['order_id']);//18 shop_order
            if($add_result['error_code']==1){
                fdump_sql($add_result,'lack_weight_refund');
            }
        }
    }

	//支付时，金额不够，记录到帐号
	public function wap_after_pay_error($now_order,$order_param,$error_tips)
	{
		fdump_sql(['now_order' => $now_order, 'order_param' => $order_param, 'error_tips' => $error_tips], 'shop_wap_after_pay_error');
		//记录充值的金额，因为 Pay/return_url 处没有返回order的具体信息，故在此调用。
		$user_result['error_code'] = 0;
		if($order_param['pay_money'] > 0){
			$alreadyMoney = M("User_money_list")
				->where(['money'=>$order_param['pay_money'],'uid'=>$now_order['uid'],'ask'=> 18, 'ask_id'=>$now_order['order_id']])//18 shop_order
				->order('pigcms_id desc')
				->find();
			if(empty($alreadyMoney)){
				//$user_result = D('User')->add_money($now_order['uid'],$order_param['pay_money'],L_("支付订单：X1发生错误！原因是：X2",array("X1" => $now_order['order_id'],"X2" => $error_tips)),18,$now_order['order_id']);
			}
		}
		if($order_param['pay_type']=='weixin'&&ACTION_NAME=='return_url'){
			exit('<xml><return_code><![CDATA[SUCCESS]]></return_code></xml>');
		}
		if ($user_result['error_code']) {
			return array('error'=>1,'msg'=>$user_result['msg']);
		} else {
			if ($order_param['is_mobile']) {
				$return_url = str_replace('/source/', '/', U('My/shop_order',array('order_id' => $now_order['order_id'])));
			} else {
				$return_url = U('User/Index/shop_order_view', array('order_id' => $now_order['order_id']));
			}
			return array('error' => 1, 'msg' => $error_tips . L_('已将您充值的金额添加到您的余额内。'), 'url' => $return_url);
		}
	}

	public function get_order_list($where = array(), $order = 'pay_time DESC,order_id DESC', $is_wap = false, $is_user = true, $is_show_error=0)
	{
		if (isset($where['status']) && $where['status'] === 0) $where['paid'] = 1;
		if (is_array($where) && $is_user) $where['is_del'] = 0;
		if ($where['status']==6) $where['status'] = 0;

		if($where['pay_type'] == 'weixinapp'){
		    $where['pay_type'] = 'weixin';
            $where['is_mobile_pay'] = 2;
        }

        if($where['pay_type'] == 'alipayh5'){
            $where['pay_type'] = ['IN',['alipayh5', 'alipay']];
        }

		if($is_wap != 10){
			$count = $this->where($where)->count();
		}
		if ($is_wap == 4) {
	        import('@.ORG.wap_group_page');
	        $p = new Page($count, 20, 'p');
		} elseif ($is_wap == 3) {
			import('@.ORG.system_page');
			$p = new Page($count, 20);
		} elseif ($is_wap == 2) {
			import('@.ORG.merchant_page');
			$p = new Page($count, 20);
		} elseif($is_wap == 10){
			$list = $this->where($where)->order($order)->page(1,10)->select();
		}  elseif($is_wap == 11){
	        import('@.ORG.wap_group_page');
	        $p = new Page($count, 20, 'p');
			$list = $this->where($where)->order($order)->select();
		} elseif($is_wap == 12){
	       	$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
			$pagesize = 10;
			$totalPage = ceil($count / $pagesize);
			$star = $pagesize * ($page - 1);
			$list = $this->where($where)->order($order)->limit($star . ',' . $pagesize)->select();
		} elseif ($is_wap) {
			import('@.ORG.wap_group_page');
			$p = new Page($count, 20, 'p');
		} else {
			import('@.ORG.user_page');
			$p = new Page($count, 10);
		}
		if($is_wap != 11 && $is_wap != 10 && $is_wap != 12){
			if($is_show_error){//允许展示且重试未生成配送订单的订单
				$newWhere = [];
				$newWhereOr = [];
				foreach ($where as $vK => $vW){
					$newWhere['a.'.$vK] = $vW;
					if($vK!='status'){
						$newWhereOr['a.'.$vK] = $vW;
					}
				}
				$newWhereOr['a.status'] = ['eq',1];
				$newWhereOr['a.platform'] = ['eq',0];
				$newWhereOr['a.is_pick_in_store'] = ['eq',0];
				$newWhereOr['a.order_status'] = ['eq',1];
				$newWhereOr[] = ('b.supply_id is null');
				$condition['_complex'] = array(
					'_logic' => 'or',
					$newWhere,
					$newWhereOr,
				);
				$orderAry = explode(',',$order);
				$newOrderAry = [];
				foreach ($orderAry as $vOrder){
					if($vOrder){
						$newOrderAry[] = 'a.'.$vOrder;
					}
				}
				$count = $this->join(' as a left join '.C('DB_PREFIX').'deliver_supply as b ON a.real_orderid=b.real_orderid')->where($condition)->count();
				if ($is_wap == 4) {
					import('@.ORG.wap_group_page');
					$p = new Page($count, 20, 'p');
				} elseif ($is_wap == 3) {
					import('@.ORG.system_page');
					$p = new Page($count, 20);
				} elseif ($is_wap == 2) {
					import('@.ORG.merchant_page');
					$p = new Page($count, 20);
				} elseif ($is_wap) {
					import('@.ORG.wap_group_page');
					$p = new Page($count, 20, 'p');
				} else {
					import('@.ORG.user_page');
					$p = new Page($count, 10);
				}
				$newOrder = implode(',',$newOrderAry);
				$list = $this->join(' as a left join '.C('DB_PREFIX').'deliver_supply as b ON a.real_orderid=b.real_orderid')->where($condition)->field('a.*,b.supply_id as supply')->order($newOrder)->limit($p->firstRow . ',' . $p->listRows)->select();
			}else{
				$list = $this->where($where)->order($order)->limit($p->firstRow . ',' . $p->listRows)->select();
			}
		}
		$notOffline = 1;
		$pay_offline_open = C('config.pay_offline_open');
		if ($pay_offline_open == 1) {
            $mer_id = !empty($mer_id) ? $mer_id : 0;
            $now_merchant = D('Merchant')->get_info($mer_id);
			if ($now_merchant) {
				$notOffline =($now_merchant['is_close_offline'] == 0 && $now_merchant['is_offline'] == 1) ? 0 : 1;
			}
		}
		foreach ($list as &$order) {
			$order['supply'] = $order['supply']??0;
			$order['offline_price'] = round($order['price'] +$order['extra_price'] - round($order['card_price'] + $order['merchant_balance'] + $order['card_give_money'] +$order['balance_pay'] + $order['payment_money'] + $order['score_deducte'] + $order['coupon_price'] + $order['qiye_pay'], 2), 2);
			$order['offline_price'] = $order['offline_price'] < 0 ? 0 : $order['offline_price'];
			$order['deliver_info'] = $order['deliver_info'] ? unserialize($order['deliver_info']) : '';
            
			if(!empty($order['robot_delivery_order_id'])){//机器人配送
				$order['robot_order'] = D('Third_robot_delivery_order')->where(['id' => $order['robot_delivery_order_id']])->find();
			}
            /*预售支付价格*/
            $order['book_all_pay']=$order['bargin_price']+$order['rest_price'];
			switch ($order['status']) {
				case 0:
					$order['status_str'] = '<b style="color:red">未确认</b>';
					break;
				case 1:
					$order['status_str'] = '<b style="color:green">已确认</b>';
					break;
				case 2:
					$order['status_str'] = '<b style="color:green">已消费</b>';
					break;
				case 3:
					$order['status_str'] = '<b style="color:green">已评价</b>';
					break;
				case 4:
					$status_txt = $this->checkOrderRefund($order['order_id']);
					$order['status_str'] = '<del style="color:gray">'.$status_txt.'</del>';
					break;
				case 5:
					$order['status_str'] = '<del style="color:gray">已取消</del>';
					break;
				case 7:
					$order['status_str'] = '<b style="color:green">分配到自提点</b>';
					break;
				case 8:
					$order['status_str'] = '<b style="color:green">发货到自提点</b>';
					break;
				case 9:
					$order['status_str'] = '<b style="color:green">自提点接货</b>';
					break;
				case 10:
					$order['status_str'] = '<del style="color:green">自提点发货</del>';
					break;
				case 11:
						$order['status_str'] = '<del style="color:red">申请取消订单</del>';
					break;
			}
			if ($order['paid'] == 0) {
			    if($order['book_status']==1){
			        //加上预售订单状态标志
                    $order['pay_status'] = L_('定金已支付');
                }else{
                    $order['pay_status'] = L_('未支付');
                }
			} elseif ($order['paid'] == 1) {
				if ($order['pay_type'] == 'offline') {
					if ($order['third_id'] || $order['order_from'] == 6) {
						$order['pay_status'] = '<b style="color:green">已支付</b>';
					} else {
						$order['pay_status'] = '<b style="color:red">未支付</b>';
					}
				} else {
					$order['pay_status'] = '<b style="color:green">已支付</b>';
				}
				if($order['book_status'] == 2){
                    //加上预售订单状态标志
                    $order['pay_status'] = '<b style="color:green">尾款已支付</b>';
                }
			}
			if ($order['is_pick_in_store'] == 0) {
			    $order['deliver_str'] = C('config.deliver_name');
			    $order['deliverinfo'] = C('config.deliver_name');
				// if ($order['deliver_info']) {
					// $order['deliverinfo'] .= '<br/>配送员姓名：' . $order['deliver_info']['name'] . '<br/>配送员电话：' . $order['deliver_info']['phone'];
				// }
			} elseif ($order['is_pick_in_store'] == 1) {
				$order['deliver_str'] = L_('商家配送');
				$order['deliverinfo'] = L_('商家配送');
				// if ($order['deliver_info']) {
					// $order['deliverinfo'] .= '<br/>配送员姓名：' . $order['deliver_info']['name'] . '<br/>配送员电话：' . $order['deliver_info']['phone'];
				// }
			} elseif ($order['is_pick_in_store'] == 2) {
				$order['deliver_str'] = L_('自提');
				$order['deliverinfo'] = L_('自提');
			} elseif ($order['is_pick_in_store'] == 3) {
				$order['deliver_str'] = L_('快递配送');
				$order['deliverinfo'] = L_('快递配送');
			}
// 			if ($order['is_pick_in_store'] == 2) {
// 				$order['deliver_str'] = '自提';
// 				$order['deliverinfo'] = '自提';
// 			} else {
// 				$order['deliver_str'] = '配送';
// 				$order['deliverinfo'] = '配送';
// 				if ($order['deliver_info']) {
// 					$order['deliverinfo'] .= '<br/>配送员：' . $order['deliver_info']['name'] . '<br/>配送员电话：' . $order['deliver_info']['phone'];
// 				}
// 			}

			//配送状态（0：订单生产，1:店员接单，2：配送员接单，3：配送员取货，4：送达，5：确认收货，6，配送结束）
			switch ($order['order_status']) {
				case 0:
					if ($order['is_pick_in_store'] == 2) {
						$order['deliver_status_str'] = L_('待提货');
					} elseif ($order['is_pick_in_store'] == 3) {
					    if ($order['status'] < 1) {
					        $order['deliver_status_str'] = L_('待发货');
					    } elseif ($order['status'] == 4) {
					        $order['deliver_status_str'] = L_('已退款');
					    } elseif ($order['status'] == 5) {
					        $order['deliver_status_str'] = L_('已取消');
					    }else {
					        $order['deliver_status_str'] = L_('已发货');
					    }
					} else {
						$order['deliver_status_str'] = L_('待发货');
					}
					break;
				case 1:
					$order['deliver_status_str'] = L_('店铺已接单');
					break;
				case 2:
					$order['deliver_status_str'] = L_('配送员接单');
					break;
				case 3:
					$order['deliver_status_str'] = L_('配送员取货');
					break;
				case 4:
					$order['deliver_status_str'] = L_('配送中');
					break;
				case 5:
					$order['deliver_status_str'] = L_('确认收货');
					break;
				case 6:
					if ($order['is_pick_in_store'] == 2) {
						$order['deliver_status_str'] = L_('已提货');
					} else {
						$order['deliver_status_str'] = L_('配送完成');
					}
					break;

			}
			$order['pay_type_str'] = D('Pay')->get_pay_name($order['pay_type'], $order['is_mobile_pay'], $order['paid']);

			if($order['from_plat']==3){
				$order['pay_type_str'].=' 扫用户码 ';
			}

			// 虚拟手机号
			if ($order['virtual_phone']) {
				$virtual_phone = explode(';', $order['virtual_phone']);
				$virtual_phone_arr = array();
				foreach ($virtual_phone as $_vPhone) {
					$virtual_phone_arr[] = explode(',', $_vPhone);
				}
				$order['virtual_phone_arr'] = $virtual_phone_arr;
			}

			//定制--显示用户实名认证信息
            $check_user = M('User_authentication')->field('user_truename, user_id_number')->where(['authentication_status'=>1, 'uid'=>$order['uid']])->find();
			$order['user_truename'] = empty($check_user['user_truename'])?'-':$check_user['user_truename'];
            $order['user_id_number'] = empty($check_user['user_id_number'])?'-':$check_user['user_id_number'];

		}
		if($is_wap != 10 && $is_wap != 12){
			return array('order_list' => $list, 'pagebar' => $p->show(), 'page' => ceil($count/10),'count' => $count);
		}else{
			return array('order_list' => $list, 'page' => ceil($count/10),'count' => $count);
		}
	}

	public function get_order_detail($where, $need_muitilingual = false)
	{
		$order = $this->field(true)->where($where)->find();
		if (empty($order)) return false;

		if ($need_muitilingual && C('config.open_multilingual')) {
			$merchant_lang = M('Merchant')->where(['mer_id' => $order['mer_id']])->getField('merchant_default_lang');
			if($merchant_lang != C('config.system_lang')){
				C('config.tmp_system_lang',$merchant_lang);
			}
		}
		$w = array();
		$w['order_id'] = $order['order_id'];
        $order['info'] = D('Shop_order_detail')->field(true)->where($w)->select();

        //替换商品名称 (多语言开启时)
        if (C('config.open_multilingual')) {
            $order['info'] = D('Shop_goods')->replace_goods_name($order['info']);
        }
		$order['cue_field'] = isset($order['cue_field']) && $order['cue_field'] ? unserialize($order['cue_field']) : '';
		if(empty($order['cue_field'])){
			$order['cue_field'] = array();
		}
		if(empty($order['cue_field'])){
			$order['cue_field'] = '';
		}
		if ($order['discount_detail'] && @unserialize($order['discount_detail'])) {
		    $order['discount_detail'] = unserialize($order['discount_detail']);
		} else {
		    $order['discount_detail'] = '';
		}


		// 虚拟手机号
		if ($order['virtual_phone']) {
			$virtual_phone = explode(';', $order['virtual_phone']);
			$virtual_phone_arr = array();
			foreach ($virtual_phone as $_vPhone) {
				$virtual_phone_arr[] = explode(',', $_vPhone);
			}
			$order['virtual_phone_arr'] = $virtual_phone_arr;
		}
		$order['date'] = date('Y-m-d H:i:s', $order['create_time']);
		$order['offline_price'] = round($order['price'] +$order['extra_price'] - round($order['card_price'] + $order['card_give_money'] +$order['merchant_balance'] + $order['balance_pay'] + $order['payment_money'] + $order['score_deducte'] + $order['coupon_price'] + $order['qiye_pay'], 2), 2);
		$order['offline_price'] = $order['offline_price'] < 0 ? 0 : $order['offline_price'];
		$order['refund_detail'] = $order['refund_detail'] ? unserialize($order['refund_detail']) : [];
		switch ($order['status']) {
			case 0:
				$order['css'] = 'inhand';
				$order['show_status'] = L_('处理中');
				$order['status_str'] = L_('未确认');
				break;
			case 1:
				$order['css'] = 'confirm';
				$order['show_status'] = L_('已确认');
				$order['status_str'] = L_('已确认');
				break;
			case 2:
				$order['css'] = 'confirm';
				$order['show_status'] = L_('已消费');
				$order['status_str'] = L_('已消费');
				break;
			case 3:
				$order['css'] = 'complete';
				$order['show_status'] = L_('已评价');
				$order['status_str'] = L_('已评价');
				break;
			case 4:
				$order['css'] = 'cancle';
				$order['show_status'] = L_('已退款');
				$status_txt = $this->checkOrderRefund($order['order_id']);
				$order['status_str'] = L_($status_txt);
				$order['last_time'] = isset($order['refund_detail']['refund_time']) ? $order['refund_detail']['refund_time'] : $order['last_time'];
				break;
			case 5:
				$order['css'] = 'cancle';
				$order['show_status'] = L_('已取消');
				$order['status_str'] = L_('已取消');
				break;
			case 7:
				$order['css'] = 'confirm';
				$order['show_status'] = L_('分配到自提点');
				$order['status_str'] = L_('分配到自提点');
				break;
			case 8:
				$order['css'] = 'confirm';
				$order['show_status'] = L_('发货到自提点');
				$order['status_str'] = L_('发货到自提点');
				break;
			case 9:
				$order['css'] = 'confirm';
				$order['show_status'] = L_('自提点接货');
				$order['status_str'] = L_('自提点接货');
				break;
			case 10:
				$order['css'] = 'confirm';
				$order['show_status'] = L_('自提点发货');
				$order['status_str'] = L_('自提点发货');
				break;
            case 11:
                $order['css'] = 'cancle';
                $order['show_status'] = L_('申请取消订单');
                $order['status_str'] = L_('申请取消订单');
				break;
		}

		if ($order['paid'] == 0) {
			$order['pay_status'] = '<b style="color:red">'.L_('未支付').'</b>';
			$order['pay_status_print'] = L_('未支付');
		} elseif ($order['paid'] == 1) {
			if ($order['pay_type'] == 'offline') {
                if ($order['third_id'] || $order['order_from'] == 6) {
					$order['pay_status'] = '<b style="color:green">'.L_('已支付').'</b>';
					$order['pay_status_print'] = L_('已支付');
				} else {
					$order['pay_status'] = '<b style="color:red">'.L_('未支付').'</b>';
					$order['pay_status_print'] = L_('未支付');
				}
			} else {
				$order['pay_status'] = '<b style="color:green">'.L_('已支付').'</b>';
				$order['pay_status_print'] = L_('已支付');
			}
		}
		if ($order['is_pick_in_store'] == 0 || $order['is_pick_in_store'] == 5) {
		    $order['deliver_str'] = C('config.deliver_name');
		} elseif ($order['is_pick_in_store'] == 1) {
			$order['deliver_str'] = L_('商家配送');
		} elseif ($order['is_pick_in_store'] == 2) {
			$order['deliver_str'] = L_('自提');
		} elseif ($order['is_pick_in_store'] == 3) {
			$order['deliver_str'] = L_('快递配送');
		}

		$order['deliver_log_list'] = '';
		if ($order['is_pick_in_store'] < 2) {
			$deliver_log_list_where = [
				'order_id' => $order['order_id'],
				'_string'  => "((`status`>'1' AND `status`<'7') OR `status`='35')"
			];
		    $order['deliver_log_list'] = D('Shop_order_log')->field(true)->where($deliver_log_list_where)->order('status ASC')->select();
		    foreach ($order['deliver_log_list'] as &$tv) {
		        $tv['dateline'] = date('Y-m-d H:i:s', $tv['dateline']);
		    }
		}
		$order['deliver_log_list'] = $order['deliver_log_list'] ?:'';
		//配送状态（0：订单生产，1:店员接单，2：配送员接单，3：配送员取货，4：送达，5：确认收货，6，配送结束）
		switch ($order['order_status']) {
			case 0:
				if ($order['is_pick_in_store'] == 2) {
					$order['deliver_status_str'] = L_('待提货');
				} elseif ($order['is_pick_in_store'] == 3) {
                    if ($order['status'] > 0 && $order['express_number']) {
                        $order['deliver_status_str'] = L_('已发货');
                    } else {
                        $order['deliver_status_str'] = L_('待发货');
                    }
				} else {
					$order['deliver_status_str'] = L_('待发货');
				}
				break;
			case 1:
				$order['deliver_status_str'] = L_('店铺已接单');
				break;
			case 2:
				$order['deliver_status_str'] = L_('配送员接单');
				break;
			case 3:
				$order['deliver_status_str'] = L_('配送员取货');
				break;
			case 4:
				$order['deliver_status_str'] = L_('配送中');
				break;
			case 5:
				$order['deliver_status_str'] = L_('确认收货');
				break;
			case 6:
				if ($order['is_pick_in_store'] == 2) {
					$order['deliver_status_str'] = L_('已提货');
				} else {
					$order['deliver_status_str'] = L_('配送完成');
				}
				break;

		}
		if ($order['order_from'] == 6) {
		    $order['deliver_str'] = '';
		    $order['deliver_status_str'] = '';
		}
		if ($order['is_pick_in_store'] == 3) {
			$express = D('Express')->get_express($order['express_id']);
			$order['express_name'] = isset($express['name']) ? $express['name'] : '';
			$order['express_code'] = $express['code'];
		}
        $order['pay_type_str'] = D('Pay')->get_pay_name($order['pay_type'], $order['is_mobile_pay'], $order['paid'], $order['orderid'], $order);
		if($order['pay_type_str']!='' && $order['is_own']>0){
			$now_merchant = D('Merchant')->get_info($order['mer_id']);
			if($order['is_own']==2){
				$order['pay_type_str'] .= '(平台子商家：'.$now_merchant['name'].')';
			}else{
				$order['pay_type_str'] .= '(商家：'.$now_merchant['name'].')';
			}
		}

		if ($order['offline_pay']) {
		    $storePay = M('Store_pay')->field(true)->where(array('id' => $order['offline_pay'], 'store_id' => $order['store_id']))->find();
		    $order['pay_type_str'] = isset($storePay['name']) ? $storePay['name'] : $order['pay_type_str'];
		}
		$sql = "SELECT u.name, u.phone FROM " . C('DB_PREFIX') . "deliver_supply AS s INNER JOIN " . C('DB_PREFIX') . "deliver_user AS u ON u.uid=s.uid WHERE s.order_id={$order['order_id']} AND s.item=2";
		$res = $this->query($sql);
		$res = isset($res[0]) && $res[0] ? $res[0] : '';
		$order['deliver_user_info'] = $res;
		$order['register_phone'] = '';
		if ($user = D('User')->field(true)->where(array('uid' => $order['uid']))->find()) {
			$order['register_phone'] = $user['phone'];
		}

		//获取订单的 店员接单时间
        $order['cashier_order_time'] = M('Shop_order_log')->where(['order_id' => $order['order_id'], 'status' => ['in', '2']])->getField('dateline') ? : '';
		//获取订单的取消时间
		$order['cancel_time'] = M('Shop_order_log')->where(['order_id' => $order['order_id'], 'status' => 10])->getField('dateline') ? : '';
		C('config.tmp_system_lang',null);
		
		if($order['is_pick_in_store'] == 2 && $order['fetch_number'] && C('customization.shop_order_fetch_number_prefix')){
			$order['fetch_number'] = C('customization.shop_order_fetch_number_prefix').$order['fetch_number'];
		}
		return $order;
	}

	public function get_rate_order_list($uid,$is_rate=false,$is_wap=false)
	{
		$condition_where = "`o`.`uid`='$uid' AND `o`.`store_id`=`s`.`store_id`";
		if($is_rate){
			$condition_where .= " AND `o`.`paid`='1'";
			$condition_where .= " AND `o`.`status`='3'";
			$condition_where .= " AND `r`.`order_type`='3' AND `r`.`order_id`=`o`.`order_id`";
			$condition_table = array(C('DB_PREFIX').'merchant_store' => 's', C('DB_PREFIX').'shop_order' => 'o', C('DB_PREFIX').'reply' => 'r');
			$condition_field = '`o`.*,`s`.`name`,`s`.`pic_info`,`r`.*';
			$condition_order = '`r`.`pigcms_id` DESC';
		}else{
			$condition_where .= " AND `o`.`paid`='1'";
			$condition_where .= " AND `o`.`status`<3";
			$condition_table = array(C('DB_PREFIX').'merchant_store'=>'s',C('DB_PREFIX').'shop_order'=>'o');
			$condition_field = '`o`.*,`s`.`name`,`s`.`pic_info`';
			$condition_order = '`o`.`create_time` DESC';
		}

		$count = $this->field($condition_field)->where($condition_where)->table($condition_table)->order($condition_order)->count();

		import('@.ORG.user_page');
		$p = new Page($count, 10);

		$order_list = $this->field($condition_field)->where($condition_where)->table($condition_table)->order($condition_order)->limit($p->firstRow . ',' . $p->listRows)->select();
		$store_image_class = new store_image();
		foreach ($order_list as &$v) {
			$v['info'] = D("Shop_order_detail")->field(true)->where(array('order_id' => $v['order_id']))->select();
			$images = $store_image_class->get_allImage_by_path($v['pic_info']);
			$v['image'] = $images ? array_shift($images) : array();
			$v['url'] = C('config.site_url') . '/shop/' . $v['store_id'] . '.html';
			$v['comment'] = stripslashes($v['comment']);
			if($v['pic']){
				$tmp_array = explode(',',$v['pic']);
				$v['pic_count'] = count($tmp_array);
			}
		}
		// 'pagebar' => $p->show(), 'page' => ceil($count/10),'count' => $count
		return array('order_list' => $order_list, 'pagebar' => $p->show(), 'page' => ceil($count/10),'count' => $count);
		// return $order_list;
	}

	public function order_count($data = array())
	{
	    $where = '1';
	    if (isset($data['store_id']) && $data['store_id']) {
	        $where .= " AND o.store_id={$data['store_id']}";
	    }
	    if (isset($data['begin_time']) && $data['begin_time'] && isset($data['end_time']) && $data['end_time']) {
	        $where .= " AND o.pay_time>'{$data['begin_time']}' AND o.pay_time<'{$data['end_time']}'";
	    }
	    if(isset($data['order_from'])){
	    	if($data['order_from']['0']=='neq'){
	    		$where.=' and o.order_from!='.$data['order_from']['1'];
	    	}else{
	    		$where.=' and o.order_from='.$data['order_from'];
	    	}
	    }
	    if(isset($data['is_check'])){
            $where.=' and '.$data['is_check'];
        }
	    $sql = "SELECT d.*, sum(d.num) as total FROM " . C('DB_PREFIX') . "shop_order AS o INNER JOIN " . C('DB_PREFIX') . "shop_order_detail AS d ON d.order_id=o.order_id WHERE {$where} AND o.paid=1 AND (o.status<2 OR o.status>5) GROUP BY d.goods_id, d.spec_id";
	    $res = $this->query($sql);
	    $list = array();
	    foreach ($res as $r) {
	        if (isset($list[$r['goods_id']])) {
	            $list[$r['goods_id']]['count'] += $r['total'];
	            $list[$r['goods_id']]['row'] += 1;
	            $list[$r['goods_id']]['list'][] = $r;
	        } else {
	            $list[$r['goods_id']] = array('count' => $r['total'], 'name' => $r['name'], 'row' => 1, 'list' => array($r));
	        }
	    }
	    return $list;
	}

    public function goods_sale_count($data = array())
    {
        $where = ' o.paid=1 AND (o.status=2 OR o.status=3) ';
        if (isset($data['store_id']) && $data['store_id']) {
            $where .= " AND o.store_id={$data['store_id']}";
        }
        if (isset($data['begin_time']) && $data['begin_time'] && isset($data['end_time']) && $data['end_time']) {
            $where .= " AND o.pay_time>'{$data['begin_time']}' AND o.pay_time<'{$data['end_time']}'";
        }
        if (isset($data['sort_id']) && $data['sort_id']) {
            $where .= ' AND d.sort_id IN (' . implode(',', $data['sort_id']) . ')';
        }
        if (isset($data['order_from']) && $data['order_from']) {
            if ($data['order_from'] == 1) {
                $where .= ' AND o.order_from=1';
            } elseif ($data['order_from'] == 6) {
                $where .= ' AND o.order_from=6';
            } elseif ($data['order_from'] == 2){
                $where .= ' AND o.order_from<>1 AND o.order_from<>6';
            }
        }elseif(isset($data['shop_type'])){
        	if($data['shop_type']==2){
        		$where.='and o.order_from=1';
        	}else{
        		$where.='and o.order_from!=1';
        	}
        }
        $sql = "SELECT d.*, sum(d.num) as total FROM " . C('DB_PREFIX') . "shop_order AS o INNER JOIN " . C('DB_PREFIX') . "shop_order_detail AS d ON d.order_id=o.order_id WHERE {$where} GROUP BY d.goods_id, d.spec_id";
        $res = $this->query($sql);
        $list = array();
        foreach ($res as $r) {
            if (isset($list[$r['goods_id']])) {
                $list[$r['goods_id']]['count'] += $r['total'];
                $list[$r['goods_id']]['row'] += 1;
                $list[$r['goods_id']]['list'][] = $r;
            } else {
                $list[$r['goods_id']] = array(
                    'count' => $r['total'],
                    'name' => $r['name'],
                    'row' => 1,
                    'list' => array(
                        $r
                    )
                );
            }
        }
        return $list;
    }

	public function cancel()
	{
		$cancel_time = 60 * C('config.shop_order_cancel_time');
		$time = time();
		$sql = "SELECT o.* FROM " . C('DB_PREFIX') . "shop_order AS o INNER JOIN " . C('DB_PREFIX') . "merchant_store_shop AS s ON s.store_id=o.store_id WHERE o.paid=1 AND o.status=0 AND ((o.cancel_time>0 AND {$time}-o.create_time>o.cancel_time*60) OR (o.cancel_time=0 AND {$time}-o.create_time>{$cancel_time}))";
		$res = $this->query($sql);
		foreach ($res as $row) {
			$this->where(array('order_id' => $row['order_id']))->save(array('status' => 5));
			D('Shop_order_log')->add_log(array('order_id' => $row['order_id'], 'status' => 10, 'name' => L_('店员接单超时系统自动取消'), 'phone' => ''));
		}
	}

	public function shop_notice($order, $is_staff = false)
	{
		$order['order_type']='shop';
		if ($is_staff) {
			$order['order_type'] = 'shop_offline';
		}
		$alias_name=C('config.shop_alias_name');
		$from_type='shop';
		if($order['order_from']==1){
			$from_type='mall';
			$alias_name=C('config.mall_alias_name');
			$alias_name=$alias_name?$alias_name:L_('商城');
		}

		$store = D('Merchant_store')->field(true)->where(array('store_id' => $order['store_id']))->find();

		//验证增加商家余额
		$desc = L_("用户在 X1 中消费X2记入收入",array("X1" => $store['name'],"X2" => floatval($order['price']) . C('config.Currency_txt')));
		if ($order['union_mer_id']) {
			$desc = L_('商家会员联盟----').$desc;
		}

		$order['desc']=$desc;
		D('SystemBill')->bill_method($order['is_own'],$order);
		//商家推广分佣
		$now_user = M('User')->where(array('uid'=>$order['uid']))->find();
		D('Merchant_spread')->add_spread_list($order,$now_user,$from_type,L_("X1用户购买X2商品获得佣金",array("X1" => $now_user['nickname'],"X2" => $alias_name)));

//		满减处理
		$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
       if($order['order_from'] == 1){
		   $href = C('config.site_url').'/wap.php?c=Mall&a=status&order_id='. $order['order_id'] . '&mer_id=' . $order['mer_id'] . '&store_id=' . $order['store_id'];
		   $orderDetailList = D('Shop_order_detail')->where(array('order_id'=>$order['order_id']))->select();
		   foreach($orderDetailList as $item){
			   if(!empty($item['give_coupon_id'])){
				   $data['coupon_id'] = $item['give_coupon_id'];
				   $data['uid'] = $order['uid'];
				   $data['num'] = 1;
				   $data['use_time'] = 0;
				   $data['receive_time'] = time();
				   $data['is_use'] = 0;
				   $coupon = D('Card_new_coupon')->create($data);
                   if($coupon){
					   $model->sendTempMsg('OPENTM201752540', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' => $alias_name.L_('提醒'), 'keyword2' => $order['real_orderid'], 'keyword1' => '', 'keyword3' => $order['price'], 'keyword4' => date('Y-m-d H:i:s'), 'remark' => L_('获取优惠券!')), $order['mer_id']);
				   }
			   }
		   }
	   }
		//积分
		// 保存订单信息
		$param['order_type'] = 'shop';
		$param['order_id'] = $order['order_id'];
		if(C('config.open_extra_price')==1){
			$score = D('Percent_rate')->get_extra_money($order);
			if($score>0){
				D('User')->add_score($order['uid'], floor($score),L_("在 X1 中消费X2 获得X3",array("X1" => $store['name'],"X2" => C('config.Currency_symbol') . floatval($order['price']), "X3" => C('config.extra_price_alias_name'))),0,$param);
				D('Scroll_msg')->add_msg($from_type,$now_user['uid'],L_("用户X1于X2购买X3成功获得X4",array("X1" => str_replace_name($now_user['nickname']),"X2" => date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),"X3" => $alias_name,"X4" => C('config.score_name'))));
			}
		}else {
			if(C('config.add_score_by_system_commission')==0 && C('config.add_score_by_percent')==0 && (C('config.open_score_discount')==0 || $order['score_discount_type']!=2)){
				if(C('config.open_score_get_percent')==1){
					$score_percent = C('config.score_get_percent')/100;
				}else{
					$score_percent = C('config.user_score_get');
				}
				//商城订单不受快店的配置限制
				if($order['order_from']==1){
					$score_get = D('Percent_rate')->shop_get_score($order);
				}elseif(C('config.shop_goods_score_edit') == 1){
					$score_get = D('Percent_rate')->shop_get_score($order);
				}else{
					$now_merchant = D('Merchant')->get_info($order['mer_id']);
					if($now_merchant['score_get_percent']>=0){
						$score_percent = $now_merchant['score_get_percent']/100;
					}
                    $score_percent = D('Percent_rate')->get_user_add_score_times($score_percent,$order['uid']);
					if($order['order_type'] = 'shop_offline'){
						$score_get  = round(($order['price']) *$score_percent, 2);
					}else{
						$score_get  = round(($order['payment_money'] + $order['balance_pay']) *$score_percent, 2);
					}
				}
				fdump_api(['no_extra_money' => [$order, $score_percent, $score_get]], 'shop_order/no_extra_money', 1);
				D('User')->add_score($order['uid'], $score_get, L_("在 X1 中消费X2 获得X3",array("X1" => $store['name'],"X2" => C('config.Currency_symbol') . floatval($order['price']),"X3" => C('config.score_name'))),0,$param);
			}
			D('Userinfo')->add_score($order['uid'], $order['mer_id'], $order['price'], L_("在 X1 中消费X2 获得X3", array("X1" => $store['name'], "X2" => C('config.Currency_symbol') . floatval($order['price']), "X3" => C('config.score_name'))));
		}
		//短信
		$sms_data = array('mer_id' => $store['mer_id'], 'store_id' => $store['store_id'], 'type' => 'shop');
		if (C('config.sms_shop_finish_order') == 1 || C('config.sms_shop_finish_order') == 3) {
			if (empty($order['phone'])) {
				$user = D('User')->field(true)->where(array('uid' => $order['uid']))->find();
				$order['phone'] = $user['phone'];
			}
			$sms_data['uid'] = $order['uid'];
			$sms_data['mobile'] = $order['userphone'];
			$sms_data['sendto'] = 'user';
			$sms_data['content'] = L_("您在 X1店中下的订单(订单号：X2),已经完成了消费，如有任何疑意，请您及时联系本店，欢迎再次光临！",array("X1" => $store['name'],"X2" => $order['real_orderid']));
			Sms::sendSms($sms_data);
		}
		if (C('config.sms_shop_finish_order') == 2 || C('config.sms_shop_finish_order') == 3) {
			$sms_data['uid'] = 0;
			$sms_data['mobile'] = $store['phone'];
			$sms_data['sendto'] = 'merchant';
			$sms_data['content'] = L_("顾客购买的X1的订单（订单号：X2），已经完成了消费！",array("X1" => $order['name'],"X2" => $order['real_orderid']));
			Sms::sendSms($sms_data);
		}


        //微信小程序订阅消息提醒
        $wxapp_message_send = false;
        if($now_user['wxapp_openid'] && $order['order_from']!=1){
            $param['page'] = '/pages/shop_new/orderDetail/orderDetail?order_id='.$order['order_id'];
            $param['wxapp_openid'] = $now_user['wxapp_openid'];
            $param['type'] = 'order_status_change';
            // 获得商品信息
            $goods_name = D('Shop_order_detail')->where(['order_id'=>$order['order_id']])->order('id ASC')->find()['name'];
            $goodsCount = D('Shop_order_detail')->field('sum(num) as total')->where(['order_id'=>$order['order_id']])->find()['total'];
            $order['goods_desc'] = $goodsCount > 1 ? L_('X1等X2个商品',['X1'=>$goods_name,'X2'=>$goodsCount]) : $goods_name;

            $order['status_str'] = L_('已送达');
            $order['note'] = L_('您的订单已送达，请及时关注~');
            $param['data_info'] = $order;
            $wxapp_message_send = D('Wxapp_template')->sendWxappMessage($param);
        }

		//微信模板消息提醒
		if ($now_user['openid'] && $order['order_from'] != 6 && !$wxapp_message_send) {
			/*商城订单跳转到商城订单详情页*/
			if($order['order_from']==1){
				$href = C('config.site_url').'/wap.php?c=Mall&a=status&order_id='. $order['order_id'] . '&mer_id=' . $order['mer_id'] . '&store_id=' . $order['store_id'];
			}else{
				$href = C('config.site_url').'/wap.php?c=Shop&a=status&order_id='. $order['order_id'] . '&mer_id=' . $order['mer_id'] . '&store_id=' . $order['store_id'];
			}
			$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
			$goods = D('Shop_order_detail')->field(true)->where(array('order_id' => $order['order_id']))->select();
			$keyword2 = '';
			$pre = '';
			$i = 1;
			foreach ($goods as $gr) {
			    if ($i < 5) {
			        $keyword2 .= $pre . $gr['name'] . ':' . $gr['price'] . '*' . $gr['num'];
			        $pre = '\n\t\t\t';
			    } elseif ($i == 5) {
			        $keyword2 .= $pre . '...';
			    }
			}
			$model->sendTempMsg('OPENTM201752540', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' => $alias_name.L_('提醒'), 'keyword2' => $order['real_orderid'], 'keyword1' => $keyword2, 'keyword3' => $order['price'], 'keyword4' => date('Y-m-d H:i:s'), 'remark' => L_('消费成功!')), $order['mer_id']);
		}
		/*更新记录的平台订单信息 2019/01/04 */
        $data=array();
        $data['status']=2;
        $data['last_time']=$_SERVER['REQUEST_TIME'];
        D('System_order')->save_order_info($from_type,$order['order_id'],$data);
        /*--------------*/

		//打印
		$printHaddle = new PrintHaddle();
		$printHaddle->printit($order['order_id'], 'shop_order', 2);

        //配送完成给配送员增加余额
        $supply = D('Deliver_supply')->where(['item' => 2, 'order_id' => $order['order_id'], 'is_settlement' => 0])->find();
        if ($supply && $supply['uid'] > 0) {
            $model = '\app\deliver\model\service\DeliverUserService/addMoney';
            $params = [
                'uid' => $supply['uid'],
                'businessType' => 'shop',
                'businessId' => $supply['order_id'],
                'money' => $supply['deliver_user_fee'] > 0 ? $supply['deliver_user_fee'] : 0,
                'desc' => '配送收入',
                'tipMoney' => $supply['tip_price'] > 0 ? $supply['tip_price'] : 0,
                'tipDesc' => '小费收入',
            ];
            invoke_v20_service($model, $params);
            D('Deliver_supply')->where(['supply_id' => $supply['supply_id']])->save(['is_settlement' => 1]);
        }
	}

	//线下零售获得积分 消耗商家积分
	public function shop_score_get($order_param)
	{
		fdump_api(['shop_score_get' => $order_param], 'shop_order/shop_score_get', 1);
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
			$param['order_type'] = 'shop';
			$param['order_id'] = $now_order['order_id'];
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
			}else{
				if((C('config.open_score_discount')==0 || $now_order['score_discount_type']!=2)){
					if(C('config.open_score_get_percent')==1){
						$score_percent = C('config.score_get_percent')/100;
					}else{
						$score_percent = C('config.user_score_get');
					}
					if(C('config.shop_goods_score_edit') == 1){
						$score_get = D('Percent_rate')->shop_get_score($now_order);
					}else{
						$now_merchant = D('Merchant')->get_info($now_order['mer_id']);
						if($now_merchant['score_get_percent']>=0){
							$score_percent = $now_merchant['score_get_percent']/100;
						}
						if ($now_order['pay_type'] == 'offline') {
						$score_get  = round(($now_order['price']-$now_order['card_price']-$now_order['coupon_price']-$now_order['merchant_balance']-$now_order['score_deducte']-$now_order['card_give_money']) *$score_percent);
						}else{
							$score_get  = round(($now_order['payment_money'] + $now_order['balance_pay']) *$score_percent);
						}
					}
					fdump_api(['shop_score_get2' => [
						$score_percent, $now_order['pay_type'], $now_order['price'],  $score_get
					]], 'shop_order/shop_score_get', 1);
					
					$score_get = ($score_get > $now_merchant['platform_score'])? $now_merchant['platform_score'] : $score_get;

					// 商家减去积分
					$res1 = D('Merchant')->use_score($now_order['mer_id'], $score_get, L_("在X1 中使用X2支付了X3 获得X4",array("X1" => $store['name'],"X2" => C('config.cash_alias_name'),"X3" => floatval($now_order['price']) . C('config.Currency_txt'),"X4" => C('config.score_name'))),$now_order['order_id'],'store',$now_order['uid']);

					// 用户增加积分
					D('User')->add_score($now_order['uid'], $score_get, L_("在 X1 中消费X2 获得X3",array("X1" => $store['name'],"X2" => floatval($now_order['price']) . C('config.Currency_txt'),"X3" => C('config.score_name'))),0,$param);
				}
				D('Userinfo')->add_score($now_order['uid'], $now_order['mer_id'], $now_order['price'], L_("在 X1 中消费X2 获得X3",array("X1" => $store['name'],"X2" => floatval($now_order['price']) . C('config.Currency_txt'),"X3" => C('config.score_name'))));
			}
			return array('error'=>0,'data'=>'success');
		}
	}

    public function shop_status($is_pick_in_store = 0)
    {
        $pick_status = L_('店员验证消费');
        if ($is_pick_in_store == 3) {
            //$pick_status = L_('店员已发货');
        }
        return array(
            0 => array('txt' => L_("订单生成成功"), 'img' => 3),
            1 => array('txt' => L_("订单支付成功"), 'img' => 3),
            2 => array('txt' => L_("店员接单"), 'img' => 1),
            3 => array('txt' => L_("配送员接单"), 'img' => 4),
            4 => array('txt' => L_("配送员已到店"), 'img' => 5),
            5 => array('txt' => L_("配送员配送中"), 'img' => 5),
            6 => array('txt' => L_("配送结束"), 'img' => 5),
            7 => array('txt' => $pick_status, 'img' => 4),
            8 => array('txt' => L_("完成评论"), 'img' => 3),
            9 => array('txt' => L_("已完成退款"), 'img' => 3),
            10 => array('txt' => L_("已取消订单"), 'img' => 3),
            11 => array('txt' => L_("商家分配自提点"), 'img' => 5),
            12 => array('txt' => L_("商家发货到自提点"), 'img' => 5),
            13 => array('txt' => L_("自提点已接货"), 'img' => 5),
            14 => array('txt' => L_("自提点已发货"), 'img' => 5),
            15 => array('txt' => L_("您在自提点取货"), 'img' => 3),
            30 => array('txt' => L_("店员为您修改了价格"), 'img' => 3),
            31 => array('txt' => L_("配送员放弃配送"), 'img' => 4),
            34 => array('txt' => L_("系统配送失败"), 'img' => 4),
            35 => array('txt' => L_("配送员接单"), 'img' => 4),
			36 => array('txt' => L_("更换配送员"), 'img' => 4),

            61 => array('txt' => L_("申请取消订单"), 'img' => 4),
            62 => array('txt' => L_("商家同意取消订单"), 'img' => 4),
            63 => array('txt' => L_("商家拒绝取消订单"), 'img' => 4),
        );
    }

	//获取周期购退款比例2019-10-31
	public function get_period_refund_rate($order_id){
		$all_array = array(
					'order_id' => $order_id,
					'status' => array('in',array(0,1,2)),
					);
		$all_count = M('Mall_periodic_deliver')->where($all_array)->count();//总期数
		if(!$all_count) return 1;
		$undeliver_where = array(
					'order_id' => $order_id,
					'status' => 0,
					);
		$undeliver_count = M('Mall_periodic_deliver')->where($undeliver_where)->count();//未配送期数
		return round($undeliver_count/$all_count,2);
	}

    // 取消订单
    public function check_refund($now_order,$staff=array())
    {
        $nowTime = time() + 5;
        $order_id = intval($now_order['order_id']);
        if (empty($now_order['paid'])) {
            $data_shop_order['order_id'] = $order_id;
            $data_shop_order['status'] = 5;
            $data_shop_order['last_time'] = $nowTime;
            $this->data($data_shop_order)->save();
            return false;
        }
		$refund_rate = 1;//适配周期购退款新增参数（周期购退款，退款金额按未发货期数占的比例计算）2019-10-31
        if($now_order['periodic_type']){
			$refund_rate = $this->get_period_refund_rate($now_order['order_id']);
		}
        $mer_store = D('Merchant_store')->where(array('mer_id' => $now_order['mer_id'], 'store_id' => $now_order['store_id']))->find();

        $my_user = D('User')->field(true)->where(array('uid' => $now_order['uid']))->find();

		//商城订单 配送订单在未发货以前 自提订单在确认消费以前 都退还商户或平台优惠券 tapd_story_1001389/2019-11-08
		if($now_order['order_status']<2 && $now_order['status']<2 && $now_order['order_from']==1){
			//退还商家优惠券
			if($now_order['card_id']){
				$use_result = D('Card_new_coupon')->return_coupon($now_order['card_id'],$now_order['order_id'],'shop',$now_order['mer_id'],$now_order['uid']);
				if($use_result['error_code']){
					$this->error_tips($use_result['msg']);
				}
			}
			//退还平台优惠券
			if($now_order['coupon_id']){
				$use_result = D('System_coupon')->return_coupon($now_order['coupon_id'],$now_order['order_id'],'shop',$now_order['mer_id'],$now_order['uid']);
				if($use_result['error_code']){
					return array('error'=>1,'msg'=>$use_result['msg']);
				}
			}
		}

        // 线下支付退款
        if ($now_order['pay_type'] == 'offline') {
            $data_shop_order['order_id'] = $now_order['order_id'];
            $data_shop_order['refund_detail'] = serialize(array('refund_time' => time()));
            $data_shop_order['status'] = 4;
            $data_shop_order['last_time'] = $nowTime + 1;
            if ($this->data($data_shop_order)->save()) {
                $return = $this->shop_refund_detail($now_order, $now_order['store_id'], $staff,$refund_rate);
                if ($return['error_code']) {
                    $data_shop_order['order_id'] = $order_id;
                    $data_shop_order['status'] = 5;
                    $data_shop_order['last_time'] = $nowTime + 1;
                    $this->data($data_shop_order)->save();
                    return false;
                } else {
                    return true;
                }
            }
        } else {
            //开通平台会员卡金额不能扣除，优先级：在线支付 > 平台余额
            if ($now_order['is_open_membership_card'] == 1 && $now_order['membership_card_price'] > 0) {
                $left = $now_order['membership_card_price'];
                $fields = ['payment_money', 'balance_pay', 'merchant_balance', 'card_give_money'];
                foreach ($fields as $f) {
                    if ($now_order[$f] >= $left) {
                        $now_order[$f] = $now_order[$f] - $left;
                        $left = 0;
                        break;
                    } else {
                        $left = $left - $now_order[$f];
                        $now_order[$f] = 0;
                    }
                }
            }
            if ($now_order['payment_money'] != '0.00') {
            	$payment_money_order = M('Payment_money_order')->where(array('order_id'=>$now_order['order_id'],'order_type'=>'shop','pay_type'=>$now_order['pay_type'],'paid_money'=>array('gt',0)))->find();
            	if($now_order['payment_money'] == $payment_money_order['payment_money']){
                    //如果是全退，那就把用户实际支付的钱一起退了吧。包括支付手续费
                    $now_order['payment_money'] = $payment_money_order['paid_money'];
                    if($payment_money_order['pay_rate'] > 0){
                    	$now_order['payment_money'] = $now_order['payment_money']*$payment_money_order['pay_rate'];
                    }
                }
                if ($now_order['is_own'] && $now_order['pay_type'] != 'nmgpay'  && $payment_money_order['pay_method'] != 'weikebaba' && $payment_money_order['pay_method'] != 'lichu') {
                    $pay_method = array();
                    $merchant_ownpay = D('Merchant_ownpay')->field('mer_id', true)->where(array('mer_id' => $now_order['mer_id']))->find();
                    foreach ($merchant_ownpay as $ownKey => $ownValue) {
                        $ownValueArr = unserialize($ownValue);
                        if ($ownValueArr['open']) {
                            $ownValueArr['is_own'] = true;
                            $pay_method[$ownKey] = array('name' => '', 'config' => $ownValueArr);
                        }
                    }
                    $now_merchant = D('Merchant')->get_info($now_order['mer_id']);
                    $open_sub_mchid = C("config.open_sub_mchid");
	                if ($now_merchant['sub_mch_refund'] && $open_sub_mchid && $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0) {
	                    $pay_method['weixin']['config']['pay_weixin_appid'] = C("config.pay_weixin_appid");
	                    $pay_method['weixin']['config']['pay_weixin_appsecret'] = C("config.pay_weixin_appsecret");
	                    $pay_method['weixin']['config']['pay_weixin_mchid'] = C("config.pay_weixin_sp_mchid");
	                    $pay_method['weixin']['config']['pay_weixin_key'] = C("config.pay_weixin_sp_key");
	                    $pay_method['weixin']['config']['sub_mch_id'] = $now_merchant['sub_mch_id'];
	                    $pay_method['weixin']['config']['pay_weixin_client_cert'] = C("config.pay_weixin_sp_client_cert");
	                    $pay_method['weixin']['config']['pay_weixin_client_key'] = C("config.pay_weixin_sp_client_key");
	                    $pay_method['weixin']['config']['is_own'] = 2 ;
	                }
                    if ($now_order['order_from'] == 4 && $now_order['is_own'] == 1) {
                        $wxapp_own_pay = M('Weixin_app_bind')->where(array('other_id' => $_POST['own_pay_mer_id'], 'bind_type' => 0))->find();
                        $pay_method['weixin']['config'] = array(
                            'pay_weixin_appid' => $wxapp_own_pay['appid'],
                            'pay_weixin_key' => $wxapp_own_pay['wxpay_key'],
                            'pay_weixin_mchid' => $wxapp_own_pay['wxpay_merid'],
                            'pay_weixin_appsecret' => $wxapp_own_pay['appsecret'],
                            'is_own' => 1
                        );
                    }

					$now_merchant = D('Merchant')->get_info($now_order['mer_id']);
					if ($now_order['is_own'] == 2 && $now_merchant['sub_mch_refund'] && C('config.open_sub_mchid') && $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0) {
						if($now_order['is_mobile_pay'] == '3'){
							$pay_method['weixin']['config']['pay_weixin_appid'] = C('config.pay_wxapp_appid');
							$pay_method['weixin']['config']['pay_weixin_appsecret'] = C('config.pay_wxapp_appsecret');
						}
						else{
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
                    $is_wap = $now_order['pay_type'] == 'alipayh5' ? 1 : 0;
                    $pay_method = D('Config')->get_pay_method(0, 0, $is_wap, 0,false,true);
                }

                if (empty($pay_method)) {
                    $data_shop_order['order_id'] = $order_id;
                    $data_shop_order['status'] = 5;
                    $data_shop_order['last_time'] = $nowTime + 1;
                    $this->data($data_shop_order)->save();
                    return false;
                }
                if (in_array($now_order['pay_type'], ['hqpay', 'hqpay_wx', 'hqpay_al'])) {
                    $pay_class_name = 'Hqpay';
                    empty($pay_method[$now_order['pay_type']]) && $pay_method[$now_order['pay_type']] = 1;
                }
                if (empty($pay_method[$now_order['pay_type']])) {
                    $data_shop_order['order_id'] = $order_id;
                    $data_shop_order['status'] = 5;
                    $data_shop_order['last_time'] = $nowTime + 1;
                    $this->data($data_shop_order)->save();
                    return false;
                }

				if($payment_money_order['pay_method'] == 'mallbook'){//mallbook通道支付退款
					import('@.ORG.pay.Mallbookpay.MallbookPayService');
					$payService = new MallbookPayService();
					$go_refund_param = $payService->refund(
						$payment_money_order['orderid'],
						$payment_money_order['paid_money'],
						$payment_money_order['mallbook_order_no']
					);
				}else if($payment_money_order && ($payment_money_order['pay_method'] == 'wft' || $payment_money_order['pay_method'] == 'paylinx' || $payment_money_order['pay_method'] == 'weikebaba' || $payment_money_order['pay_method'] == 'wepayez' || $payment_money_order['pay_method'] == 'xiaoma' || $payment_money_order['pay_method'] == 'nong')){
					if($payment_money_order['pay_method'] == 'wft'){
						$pay_method = 'wftpay';
					}
					if($payment_money_order['pay_method'] == 'paylinx'){
						$pay_method = 'paylinx';
					}
					if($payment_money_order['pay_method'] == 'weikebaba'){
						$pay_method = 'weikebaba';
					}
					if($payment_money_order['pay_method'] == 'wepayez'){
						$pay_method = 'wepayez';
					}
					if($payment_money_order['pay_method'] == 'xiaoma'){
						$pay_method = 'xiaoma';
					}
					if($payment_money_order['pay_method'] == 'nong'){
						$pay_method = 'nong';
					}
                	import('@.ORG.LowFeePay');
                	$lowfeepay = new LowFeePay($pay_method);
                	$now_order['order_type'] = 'shop';
                	$now_order['orderNo'] = $payment_money_order['orderNo'];
                	if($now_order['mer_id'] && $payment_money_order['pay_method'] == 'weikebaba'){
                        $mer_juhe = M('Weikebaba')->where(array('mer_id'=>$now_order['mer_id']))->find();
                        if(!empty($mer_juhe)){
                            $lowfeepay->user_id = $mer_juhe['user_id'];
                            $lowfeepay->key = $mer_juhe['key'];
                            $now_order['is_own'] =1 ;
                        }
                    }
                	$go_refund_param= $lowfeepay->refund($now_order);
                }else if($payment_money_order && $payment_money_order['pay_method'] == 'lichu'){//利楚支付
					$config = json_decode($payment_money_order['third_plat_data'],true);
					$config['out_trade_no'] = $payment_money_order['orderNo'];
					require_once $_SERVER['DOCUMENT_ROOT'] . "/v20/extend/pay/LichuPay.php";
					$pay = new LichuPay();
					$go_refund_param= $pay->refund([
						'real_orderid'=>$now_order['real_orderid'],
						'refund_fee'=>$payment_money_order['paid_money'] * 100
					],$config);
				}else if($payment_money_order && $payment_money_order['pay_method'] == 'chinaums'){
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

                	$go_refund_param= $pay->refund('shop_'.$payment_money_order['orderid'], $payment_money_order['paid_money'], $payment_money_order['paid_money'], $payment_money_order['third_plat_data'], $chinaums_merno, $mid);
                }else if($payment_money_order && $payment_money_order['pay_method'] == 'lakala'){
                	import('@.ORG.pay.Lakala');
					$pay_config = json_decode($payment_money_order['pay_config'],true);
                	$pay = new Lakala([],$pay_config);
                	
                	$go_refund_param= $pay->refund('shop_'.$payment_money_order['orderid'], $payment_money_order['paid_money'], $payment_money_order['paid_money'], $payment_money_order['third_plat_data']);
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
                    if($payment_money_order['paid_money'] == $now_order['payment_money']){// 不支持部分退款
                        import('@.ORG.pay.XianTaoCard');
                        $pay = new XianTaoCard();

                        $now_user = D('User')->get_user($now_order['uid']);
                        try{
                            // 退款
                            $go_refund_param = $pay->refund($payment_money_order['orderNo'], $now_user['phone']);
                        } catch(Exception $e){
                           return array('errcode' => 1, 'msg' => $e->getMessage());
                        }
                    }else{
                        return array('errcode' => 1, 'msg' => '仙桃支付方式不支持部分退款');
                    }
                }else {
	                $pay_class_name != 'Hqpay' && $pay_class_name = ucfirst($now_order['pay_type']);
	                $import_result = import('@.ORG.pay.' . $pay_class_name);
	                if (empty($import_result)) {
	                    $data_shop_order['order_id'] = $order_id;
	                    $data_shop_order['status'] = 5;
	                    $data_shop_order['last_time'] = $nowTime + 1;
	                    $this->data($data_shop_order)->save();
	                    return false;
	                }

	                $this->where(array('order_id' => $now_order['order_id']))->save(array('is_refund' => 1));
	                $now_order['order_type'] = 'shop';
	                $now_order['order_id'] = $now_order['orderid'];
	                if ($now_order['order_from'] == 4 && $now_order['is_own'] != 2 && $now_order['is_own'] != 3) {
	                    $pay_method['weixin']['config'] = array(
	                        'pay_weixin_appid' => C('config.pay_wxapp_appid'),
	                        'pay_weixin_key' => C('config.pay_wxapp_key'),
	                        'pay_weixin_mchid' => C('config.pay_wxapp_mchid'),
	                        'pay_weixin_appsecret' => C('config.pay_wxapp_appsecret')
	                    );
	                    if ($now_order['is_own'] == 1) {
	                        $wxapp_own_pay = M('Weixin_app_bind')->where(array('other_id' => $_POST['own_pay_mer_id'], 'bind_type' => 0))->find();
	                        $pay_method['weixin']['config'] = array(
	                            'pay_weixin_appid' => $wxapp_own_pay['appid'],
	                            'pay_weixin_key' => $wxapp_own_pay['wxpay_key'],
	                            'pay_weixin_mchid' => $wxapp_own_pay['wxpay_merid'],
	                            'pay_weixin_appsecret' => $wxapp_own_pay['appsecret'],
	                            'is_own' => 1
	                        );
	                    }
	                }

					if ($pay_class_name == 'Alipay' && $now_order['is_mobile_pay'] == 2 && C('config.new_pay_alipay_app_public_key') != '' && C('config.new_pay_alipay_app_appid')!= '' && C('config.new_pay_alipay_app_private_key')!= '') {
						$pay_class_name = 'AlipayApp';
						$pay_method['alipay']['config']['new_pay_alipay_app_appid'] = C('config.new_pay_alipay_app_appid');
						$pay_method['alipay']['config']['new_pay_alipay_app_private_key'] = C('config.new_pay_alipay_app_private_key');
						$pay_method['alipay']['config']['new_pay_alipay_app_public_key'] = C('config.new_pay_alipay_app_public_key') ;
					}
					$import_result = import('@.ORG.pay.' . $pay_class_name);
					$pay_class = new $pay_class_name($now_order, getFormatNumber($now_order['payment_money']*$refund_rate), $now_order['pay_type'], $pay_method[$now_order['pay_type']]['config'], $my_user, 1);
					$go_refund_param = $pay_class->refund();
	            }

	            $now_order['order_id'] = $order_id;
                $data_shop_order['order_id'] = $order_id;
                $data_shop_order['refund_detail'] = serialize($go_refund_param);
                if (! (empty($go_refund_param['error']) && $go_refund_param['type'] == 'ok')) {
                    $data_shop_order['status'] = 5;
                    $data_shop_order['is_refund'] = 0;
                    $data_shop_order['last_time'] = $nowTime;
                } else {
                    $data_shop_order['status'] = 4;
                    $data_shop_order['last_time'] = $nowTime;
                }
            }else{
                $data_shop_order['order_id'] = $order_id;
                $data_shop_order['status'] = 4;
                $data_shop_order['last_time'] = $nowTime;
            }
            if (empty($now_order['pay_type'])) {
                $data_shop_order['order_id'] = $order_id;
                $data_shop_order['status'] = 4;
                $data_shop_order['last_time'] = $nowTime;
            }

			if ($mdata = D('Merchant_money_list')->field('percent,type,system_take')->where(array('_string' => "type='shop' or type='mall'",'income' => 1, 'order_id' => $now_order['real_orderid']))->find()) {
					if($now_order['order_from']==1){
						$alias_name=$this->config['mall_alias_name'];
						$alias_name=$alias_name?$alias_name:'商城';
					}else{
						$alias_name=$this->config['shop_alias_name'];

					}
					fdump($mdata,'asd',1);
					fdump($alias_name,'asd',1);
					
				$returnSystemTake = $returnTakePercent = 0;
				if($mdata['system_take'] > 0){
					$returnSystemTake = bcmul(bcdiv(($now_order['payment_money']+$now_order['balance_pay'])*$refund_rate,$mdata['money'],2), $mdata['system_take'],2);
					$returnTakePercent = $mdata['percent'];
				}

				D('Merchant_money_list')->use_money(
					$now_order['mer_id'], 
					($now_order['payment_money']+$now_order['balance_pay'])*$refund_rate, 
					$mdata['type'], 
					$alias_name . '部分商品退货,减少余额,订单编号' . $now_order['real_orderid'], 
					$now_order['real_orderid'],
					$returnTakePercent,
					$returnSystemTake,
					[],
					0,
					$go_refund_param['refund_param']??[]
				);
				}

            if ($this->data($data_shop_order)->save()) {
            	D("System_order")->save_order_status('shop', $order_id, 5);
				$this->cancelTwOrder($order_id);
                if ($data_shop_order['status'] == 4) {
                    $return = $this->shop_refund_detail($now_order, $now_order['store_id'], $staff,$refund_rate);
                    if (empty($return['error_code'])) {
                        D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 9));
                    }
                }
            }
            //佣金退回
            //D('User_spread_list')->cancelUserSpreadCommission('shop', $order_id);
        }
    }

	private function shop_refund_detail($now_order, $store_id, $staff=array(),$refund_rate=1)
	{
		$order_id  = $now_order['order_id'];
		$mer_store = D('Merchant_store')->where(array('mer_id' => $now_order['mer_id'], 'store_id' => $store_id))->find();
		$alias_name=C('config.shop_alias_name');
		$from_type='shop';
		if($now_order['order_from']==1){
			$from_type='mall';
			$alias_name=C('config.mall_alias_name');
			$alias_name=$alias_name?$alias_name:L_('商城');
		}

		//取消订单，返回E卡支付的金额
		if($now_order['use_ecard_price']>0){
			$result=D('Ecard_coupon')->refund_ecard($now_order,$from_type,$alias_name);
			$param=array('refund_time'=>time());
			if($result['error_code']){
				$param['err_msg']=$result['msg'];
			}else{
				$param['refund_id']=$now_order['order_id'];
			}
			$data_shop_order['order_id']=$now_order['order_id'];
			$data_shop_order['refund_detail']=serialize($param);
			$result['error_code'] || $data_shop_order['status']=4;
			$this->data($data_shop_order)->save();
			if($result['error_code']){
				return $result;
			}
			$go_refund_param['msg']=$result['msg'];
		}

		//如果使用了积分 2016-1-15
		if ($now_order['score_used_count'] != 0) {
		    $result = D('User')->add_score($now_order['uid'], $now_order['score_used_count']*$refund_rate, L_("X1商品退款，X2回滚，订单编号X3",array("X1" => $alias_name,"X2" => C('config.score_name'),"X3" => $now_order['real_orderid'])));
			$param = array('refund_time' => time());
			if ($result['error_code']) {
				$param['err_msg'] = $result['msg'];
			} else {
				$param['refund_id'] = $now_order['order_id'];
			}
			$data_shop_order['order_id'] = $now_order['order_id'];
			$data_shop_order['refund_detail'] = serialize($param);
			$result['error_code'] || $data_shop_order['status'] = 4;
			$this->data($data_shop_order)->save();
			if ($result['error_code']) {
				return $result;
			}
			$go_refund_param['msg'] = $result['msg'];
		}

		//平台余额退款
		if ($now_order['balance_pay'] != '0.00') {
		    $result = D('User')->add_money($now_order['uid'],$now_order['balance_pay']*$refund_rate, L_("X1商品退款，增加余额，订单编号X2",array("X1" => $alias_name,"X2" => $now_order['real_orderid'])));
			$param = array('refund_time' => time());
			if($result['error_code']){
				$param['err_msg'] = $result['msg'];
			} else {
				$param['refund_id'] = $now_order['order_id'];
			}

			$data_shop_order['order_id'] = $now_order['order_id'];
			$data_shop_order['refund_detail'] = serialize($param);
			$result['error_code'] || $data_shop_order['status'] = 4;
			$this->data($data_shop_order)->save();
			if ($result['error_code']) {
				return $result;
			}
			$go_refund_param['msg'] = L_('平台余额退款成功');
		}

		//企业预存款退回
        if ($now_order['qiye_pay'] > 0) {
            $staff = M('Qiye_staff')->where(['job_number' => $now_order['uid']])->find();
            if (M('Qiye_staff')->where(['staff_id' => $staff['staff_id']])->setInc('money', $now_order['qiye_pay'])) {
                $log = [
                    'staff_id' => $staff['staff_id'],
                    'uid' => $now_order['uid'],
                    'type' => 1,
                    'money' => $now_order['qiye_pay'],
                    'time' => time(),
                    'ip' => get_client_ip(),
                    'desc' => L_("X1商品退款，增加余额，订单编号X2", array("X1" => $alias_name, "X2" => $now_order['real_orderid'])),
                    'order_id' => $now_order['order_id'],
                    'real_orderid'=>$now_order['real_orderid'],
                    'store_id'=>$store_id,
                    'order_type' => $from_type,
                    'now_money' => $staff['money'] + $now_order['qiye_pay']
                ];
                M('Qiye_staff_money_list')->add($log);
                $result = ['error_code' => 0, 'msg' => 'OK'];
            } else {
                $result = ['error_code' => 1, 'msg' => L_('企业预存款余额增加失败！请联系管理员协助解决。')];
            }
            $param = array('refund_time' => time());
            if ($result['error_code']) {
                $param['err_msg'] = $result['msg'];
            } else {
                $param['refund_id'] = $now_order['order_id'];
            }

            $data_shop_order['order_id'] = $now_order['order_id'];
            $data_shop_order['refund_detail'] = serialize($param);
            $result['error_code'] || $data_shop_order['status'] = 4;
            $this->data($data_shop_order)->save();
            if ($result['error_code']) {
                return $result;
            }
            $go_refund_param['msg'] = L_('企业预存款退款成功');
        }

		//商家会员卡余额退款
		if ($now_order['merchant_balance'] != '0.00' || $now_order['card_give_money'] != '0.00') {
			$card_mer_id = $now_order['mer_id'];

            // 商家联盟会员卡
            if ($now_order['union_mer_id']) {
                $card_mer_id = $now_order['union_mer_id'];
            }
		    $result = D('Card_new')->add_user_money($card_mer_id, $now_order['uid'], $now_order['merchant_balance']*$refund_rate,$now_order['card_give_money']*$refund_rate, 0, L_("X1商品退款，增加余额，订单编号X2",array("X1" => $alias_name,"X2" => $now_order['real_orderid'])),L_("X1商品退款，增加余额，订单编号X2",array("X1" => $alias_name,"X2" => $now_order['real_orderid'])));
			$param = array('refund_time' => time());
			if ($result['error_code']) {
				$param['err_msg'] = $result['msg'];
			} else {
				$param['refund_id'] = $now_order['order_id'];
			}

			$data_shop_order['order_id'] = $now_order['order_id'];
			$data_shop_order['refund_detail'] = serialize($param);
			$result['error_code'] || $data_shop_order['status'] = 4;
			$this->data($data_shop_order)->save();
			if ($result['error_code']) {
				return $result;
			}
			$go_refund_param['msg'] = $result['msg'];
		}
		//退款打印
		$printHaddle = new PrintHaddle();
		$printHaddle->printit($now_order['order_id'], 'shop_order', 3);

		$sms_data = array('mer_id' => $mer_store['mer_id'], 'store_id' => $mer_store['store_id'], 'type' => 'shop');
		if (C('config.sms_shop_cancel_order') == 1 || C('config.sms_shop_cancel_order') == 3) {
			$sms_data['uid'] = $now_order['uid'];
			$sms_data['mobile'] = $now_order['userphone'] ? $now_order['userphone'] : $my_user['phone'];
			$sms_data['sendto'] = 'user';
            $sms_data['content'] = L_("您在 X1店中下的订单（订单号：X2），在X3时已被取消并退款，欢迎再次光临！",array("X1" => $mer_store['name'],"X2" => $now_order['real_orderid'],"X3" => date('Y-m-d H:i:s')));
			Sms::sendSms($sms_data);
		}
		if (C('config.sms_shop_cancel_order') == 2 || C('config.sms_shop_cancel_order') == 3) {
			$sms_data['uid'] = 0;
			$sms_data['mobile'] = $mer_store['phone'];
			$sms_data['sendto'] = 'merchant';
			if (!empty($staff)) {
                $sms_data['content'] = L_("顾客X1的预定订单（订单号：X2），在X3时已被店员取消并退款！",array("X1" => $now_order['username'],"X2" => $now_order['real_orderid'],"X3" => date('Y-m-d H:i:s')));
            } else {
                $sms_data['content'] = L_("顾客X1的预定订单（订单号：X2），在X3时已被客户取消并退款！",array("X1" => $now_order['username'],"X2" => $now_order['real_orderid'],"X3" => date('Y-m-d H:i:s')));
            }
			Sms::sendSms($sms_data);
		}
		//退款时销量回滚
		if (($now_order['paid'] == 1 || $now_order['reduce_stock_type'] == 1) && $now_order['is_rollback'] == 0) {
			$goods_obj = D("Shop_goods");
			foreach ($now_order['info'] as $menu) {
				$goods_obj->update_stock($menu, 1);//修改库存
			}
			$this->where(array('order_id' => $now_order['order_id']))->save(array('is_rollback' => 1));
		}
		D("Merchant_store_shop")->where(array('store_id' => $now_order['store_id'], 'sale_count' => array('gt', 0)))->setDec('sale_count', 1);
		//退款时销量回滚
		$go_refund_param['error_code'] = false;
		return $go_refund_param;
	}

    public function saveOrder($order_data, $return, $user,$is_add = true)
    {
        $detail_obj = D('Shop_order_detail');
        $goods_obj = D("Shop_goods");
        $oldOrder = null;
        $orderTemp = null;
        $order_data['platform_merchant'] = $return['platform_merchant'];//平台优惠中商家补贴的总和统计
        $order_data['platform_plat'] = $return['platform_plat'];//平台优惠中平台补贴的总和统计

		/*if(cookie('visit_village_id')!='' && cookie('visit_village_id')>0) {
			$count_store = D('Plat_recommend')->where(array('store_id',$order_data['store_id']))->count();
			if ($count_store>0) {
				$order_data['village_id'] = cookie('visit_village_id');
			}
		}*/


        if (!C('config.house_buy_rebate_open')) {
            if(cookie('visit_village_id')!='' && cookie('visit_village_id')>0){
                $count_store = D('Plat_recommend')->where(array('store_id',$order_data['store_id']))->count();
                if ($count_store>0) {
                    $order_data['village_id'] = cookie('visit_village_id');
                }
                if (!$order_data['village_id']) {
                    $order_data['village_id'] = cookie('visit_village_id');
                }
            }
        } else {
            //开关开启情况下-获取用户最新选择的小区
            $select_log = M('House_village_select_log')->where(['uid'=>$order_data['uid']])->field('village_id')->order('id DESC')->find();
            $order_data['village_id'] = isset($select_log['village_id'])?$select_log['village_id']:0;
        }

        //获取用户最新选择的小区
        $select_log = M('House_village_select_log')->where(['uid'=>$order_data['uid']])->field('village_id')->order('id DESC')->limit(1)->find();
        $order_data['village_id'] = isset($select_log['village_id'])?$select_log['village_id']:0;

        /** 20180507 平台给商家限定订单数的折扣，下单时就增加折扣订单数目，没有做此单没有支付或者其他原因回滚折扣订单数 */
        $order_data['plat_discount'] = 0;
        $order_data['plat_discount_price'] = 0;
        $plat_discount = D('Merchant')->getDiscount($order_data['mer_id']);
        $plat_discount = floatval($plat_discount);
        if ($plat_discount) {
            $dprice = round($order_data['price'] * $plat_discount * 0.01, 2);
            $order_data['can_discount_money'] = round($order_data['can_discount_money'] * $plat_discount * 0.01, 2);
            $order_data['balance_reduce'] += ($order_data['price'] - $dprice);
            $order_data['plat_discount_price'] = $order_data['price'] - $dprice;
            $order_data['price'] = $dprice;
            $order_data['plat_discount'] = $plat_discount;
        } else {
            $plat_discount = 100;
        }

        if ($order_data['cartid']) {	//购物车
            $oldOrder = $this->field(true)->where(array('cartid' => $order_data['cartid']))->find();
            if ($oldOrder['status'] != 0 || $oldOrder['paid'] != 0) return 0;
            $orderTemp = D('Shop_order_temp')->field(true)->where(array('cartid' => $order_data['cartid']))->find();
        }else if($order_data['tmp_order_id']){	//临时订单
			$oldOrder = $this->field(true)->where(array('order_id' => $order_data['tmp_order_id']))->find();
			if ($oldOrder['paid'] != 0) return 0;
			//fdump_sql(['oldOrder' => $oldOrder], 'ID1011054_oldOrder');
		}
        if (!$is_add) {
            $order_data['goods'] = $return['goods'];
            return $order_data;
        }

        // 虚拟手机号处理 快递配送不处理
        $now_user = D('User')->get_user($order_data['uid']);
        if ($order_data['is_pick_in_store'] !=3 && ($order_data['userphone'] || $now_user['phone']) && C('config.protect_phone_number')) {
        	// 期望送达时间或自提时间
        	$start_time = time();
        	if ($order_data['expect_use_time']) {
        		$start_time = $order_data['expect_use_time'];
        	}

        	// 订单手机号
        	$order_data['virtual_phone'] = '';
            $order_data['virtual_phone_overtime'] = '';
        	if ($order_data['userphone']) {
        		$virtual_phone = D('User')->get_virtual_phone($order_data['userphone'],$start_time);
        		if (!$virtual_phone['error_code']) {
        			$order_data['virtual_phone_overtime'] = $virtual_phone['data']['expiration_time'];
            		$order_data['virtual_phone'] .= $virtual_phone['data']['phone'];
            	}
        	}

        	// 用户注册手机号 如果不一样获取两个虚拟手机号
        	if($now_user['phone'] && $now_user['phone'] != $order_data['userphone']){
        		$virtual_phone = D('User')->get_virtual_phone($now_user['phone'],$start_time);
        		if (!$virtual_phone['error_code']) {
        			$order_data['virtual_phone_overtime'] = $virtual_phone['data']['expiration_time'];
            		$order_data['virtual_phone'] .= ';'.$virtual_phone['data']['phone'];
        		}
        	}
        }

        $order_id = 0;
        if ($oldOrder && ($orderTemp || $order_data['tmp_order_id'])) {
            if ($oldOrder['real_orderid']) {
                $order_data['real_orderid'] = $oldOrder['real_orderid'];
            }
            if ($this->where(array('order_id' => $oldOrder['order_id']))->save($order_data)) {
                $details = $detail_obj->where(array('order_id' => $oldOrder['order_id']))->select();
                foreach ($details as $detail) {
                    $order_data['reduce_stock_type'] && $goods_obj->update_stock($detail, 1);//修改库存
                }
                $detail_obj->where(array('order_id' => $oldOrder['order_id']))->delete();
                $order_id = $oldOrder['order_id'];
            }
        } else {
            if ($order_id = $this->add($order_data)) {
                D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 0));
                if ($order_data['is_pick_in_store'] == 2 && $order_data['status'] == 7) {
                    D('Pick_order')->add(array('store_id' => $order_data['store_id'], 'order_id' => $order_id, 'pick_id' => $order_data['pick_id'], 'status' => 0, 'dateline' => time()));
                    //D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 11, 'name' => $this->staff_session['name'], 'phone' => $this->store['phone']));//分配到自提点
                }
            }
        }
        if ($order_id) {
            if (isset($orderTemp['cartid']) && $orderTemp['cartid']) {
                D('Shop_order_temp')->where(array('cartid' => $orderTemp['cartid']))->save(array('price' => $order_data['price'], 'packing_charge' => $order_data['packing_charge'], 'freight_charge' => $order_data['freight_charge']));
            }

            $discountPrice = $order_data['merchant_reduce'] + $order_data['balance_reduce'];
            $totalDiscountPrice = $order_data['discount_price'];

            $flag = false;//判断订单是否含有称重商品
            $detailGoodsList= [];
            foreach ($return['goods'] as $grow) {
                if (($order_data['order_from'] == 2 || $order_data['order_from'] == 3) && !empty($grow['str'])) {
                    $temp = explode(',', $grow['str']);
                    $temp = array_reverse($temp);
                    $grow['str'] = implode(',', $temp);
                }

				$detail_data = array(
					'store_id'            => $return['store_id'],
					'order_id'            => $order_id,
					'number'              => isset($grow['number']) && $grow['number'] ? $grow['number'] : '',
					'cost_price'          => $grow['cost_price'],
					'unit'                => $grow['unit'],
					'goods_id'            => $grow['goods_id'],
					'name'                => $grow['name'],
					'goods_image'         => $grow['image'],
					'price'               => $grow['price'],
					'num'                 => $grow['num'],
					'spec'                => $grow['str'],
					'spec_id'             => $grow['spec_id'],
					'create_time'         => time(),
					'extra_price'         => isset($grow['extra_price']) ? floatval($grow['extra_price']) : 0,
					'packname'            => isset($grow['packname']) ? trim($grow['packname']) : '',
					'packing_charge'      => isset($grow['packing_charge']) ? floatval($grow['packing_charge']) : 0,
					'host_goods_id'       => isset($grow['host_goods_id']) ? intval($grow['host_goods_id']) : 0,//附属商品关联的主商品ID 主商品为0
					'uniqueness_number'   => isset($grow['uniqueness_number']) ? $grow['uniqueness_number'] : 0, //附属商品与主商品的相同唯一ID
					'is_liveshow'         => isset($grow['is_liveshow']) ? $grow['is_liveshow'] : 0,
					'liveshow_id'         => isset($grow['liveshow_id']) ? $grow['liveshow_id'] : 0,
					'is_livevideo'        => isset($grow['is_livevideo']) ? $grow['is_livevideo'] : 0,
					'livevideo_id'        => isset($grow['livevideo_id']) ? $grow['livevideo_id'] : 0,
					'liveshow_percent'    => isset($grow['liveshow_percent']) ? $grow['liveshow_percent'] : 0,
					'rep_id'              => $grow['rep_id'] > 0 ? $grow['rep_id'] : 0,//超值换购活动id
					'in_the_preferential' => isset($grow['in_the_preferential']) ? $grow['in_the_preferential'] : 0
				);
                $detail_data['is_seckill'] = intval($grow['is_seckill_price']);
                $detail_data['is_use_coupon'] = intval($grow['is_use_coupon']);
                $detail_data['discount_type'] = intval($grow['discount_type']);
                $detail_data['discount_rate'] = $grow['discount_rate'];
                $detail_data['sort_id'] = $grow['sort_id'];
                $detail_data['old_price'] = floatval($grow['old_price']);
                $detail_data['discount_price'] = floatval($grow['discount_price']);
                $detail_data['sell_type'] = $grow['sell_type'] ? $grow['sell_type'] : 0;

                //记录限时优惠id
                if ($detail_data['is_seckill']) {
                    $seckill_record = D('Time_limited_discount_goods')->get_in_progress_record($detail_data['goods_id']);
                    if (empty($detail_data['spec_id'])) {
                        $detail_data['limit_id'] = $seckill_record['id'];
                    } else {
                        $spec_lists = isset($seckill_record['spec_list']) ? $seckill_record['spec_list'] : [];
                        $detail_data['limit_id'] = isset($spec_lists[$detail_data['spec_id']]) ? $spec_lists[$detail_data['spec_id']]['id'] : 0;
                    }
                }else{
                    $detail_data['limit_id'] = 0;
                }

                if($grow['sell_type']==2) $flag = true;//判断订单是否含有称重商品
                $detail_data['goods_weight'] = $grow['goods_weight'] ? $grow['goods_weight'] : 0;
                if ($grow['is_rep']) {
                	// 超值换购不参与其他任何折扣
                	$detail_data['pay_price'] = $detail_data['discount_price'];
                }else{
	                if($totalDiscountPrice > 0){
						$detail_data['pay_price'] = floatval(round($grow['discount_price'] * (1 - $discountPrice / $totalDiscountPrice), 2) * $plat_discount * 0.01);
					}else{
						$detail_data['pay_price'] = floatval(round($grow['discount_price'], 2) * $plat_discount * 0.01);
					}
                }
                $order_data['reduce_stock_type'] && $goods_obj->update_stock(array_merge($grow,$detail_data));//修改库存
                $detailGoodsList[] = $detail_data;
            }

            if ($detailGoodsList) {
                //fdump_sql(['post' => $_POST, 'detail' => $detailGoodsList, 'req_uri' => $_SERVER['REQUEST_URI']], 'ID1011360');
                $detailGoodsList = array_unique($detailGoodsList, SORT_REGULAR);
                $detail_data_save = $detail_obj->addAll($detailGoodsList);
                //异常时记录至数据库日志里，方便后续查看原因。
                if (!$detail_data_save) {
                    $fdumpArr = array(
                        'order_id' => $order_id,
                        'detail_data' => $detailGoodsList,
                    );
                    fdump_sql($fdumpArr, 'shop_order_good_fail');
                }
            }
            //记录赠送商品
            $fullGift = D('Store_full_gift_activity')->getFullGiveGood($order_data['store_id'], $order_data['goods_price'] + $order_data['packing_charge'],$order_data['uid']);
            if ($fullGift) {
                $this->where(['order_id' => $order_id])->save(['is_give' => 1, 'full_gift_aid' => $fullGift['aid']]);
                $fullGiftDetail = array(
                    'store_id' => $return['store_id'],
                    'order_id' => $order_id,
                    'number' => $fullGift['number'],
                    'cost_price' => 0,
                    'unit' => $fullGift['unit'],
                    'goods_id' => $fullGift['goods_id'],
                    'name' => $fullGift['name'],
                    'goods_image' => $fullGift['image'],
                    'price' => 0,
                    'num' => 1,
                    'spec' => '',
                    'spec_id' => '',
                    'create_time' => time(),
                    'extra_price' => 0,
                    'packname' => '',
                    'packing_charge' => 0,
                    'host_goods_id' => 0,
                    'uniqueness_number' => 0,
                    'rep_id' => 0,
                    'is_seckill' => 0,
                    'is_use_coupon' => 0,
                    'discount_type' => 0,
                    'discount_rate' => '',
                    'sort_id' => $fullGift['sort_id'],
                    'old_price' => 0,
                    'discount_price' => 0,
                    'sell_type' => $fullGift['sell_type'],
                    'goods_weight' => $fullGift['weight'],
                    'pay_price' => 0,
                    'is_give' => 1,
                    'full_gift_setting_id' => $fullGift['setting_id']
                );
                $order_data['reduce_stock_type'] && $goods_obj->update_stock($fullGiftDetail);//修改库存
                $detail_obj->add($fullGiftDetail);
            }


            if($flag) $this->where(array('order_id'=>$order_id))->save(array('is_weight'=>1));//判断订单是否含有称重商品

			//通知体系   临时订单不通知
			if(!$order_data['is_tmp_order']){
				if ($user['openid'] || $user['wxapp_openid']) {
					$keyword2 = '';
					$pre = '';
					$i = 1;
					foreach ($return['goods'] as $menu) {
						if ($i < 5) {
							$keyword2 .= $pre . $menu['name'] . ':' . $menu['price'] . '*' . $menu['num'];
							$pre = '\n\t\t\t';
						} elseif ($i == 5) {
							$keyword2 .= $pre . '...';
						}
						$i++;
					}
					$alias_name=C('config.shop_alias_name');
					$href = C('config.site_url') . '/wap.php?c=Shop&a=status&order_id='. $order_id;
					if($order_data['order_from']==1){
						$alias_name=C('config.mall_alias_name');
						$alias_name=$alias_name?$alias_name:L_('商城');
						$href = C('config.site_url') . '/wap.php?c=Mall&a=status&order_id='. $order_id;
					}
					$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
					$model->sendTempMsg('OPENTM201682460', array('href' => $href, 'wecha_id' => $user['openid'], 'wxapp_openid' => $user['wxapp_openid'], 'first' => L_('您好，您的订单已生成'), 'keyword3' => $order_data['real_orderid'], 'keyword1' => date('Y-m-d H:i:s'), 'keyword2' => $keyword2, 'remark' => $alias_name . L_('订单已生成，等待您完成支付并使用')), $order_data['mer_id']);
				}

				$printHaddle = new PrintHaddle();
				$printHaddle->printit($order_id, 'shop_order', 0);

				$sms_data = array('mer_id' => $return['mer_id'], 'store_id' => $return['store_id'], 'type' => 'shop');
				if (C('config.sms_shop_place_order') == 1 || C('config.sms_shop_place_order') == 3) {
					$sms_data['uid'] = $this->user_session['uid'];
					$sms_data['mobile'] = $order_data['userphone'];
					$sms_data['sendto'] = 'user';
					$sms_data['content'] = L_("您X1在【X2】中下了一个订单，订单号：X3",array("X1" => date("H时i分") ,"X2" => $return['store']['name'],"X3" => $order_data['real_orderid']));
					Sms::sendSms($sms_data);
				}
				if (C('config.sms_shop_place_order') == 2 || C('config.sms_shop_place_order') == 3) {
					$store_phones = explode(' ', $return['store']['phone']);
					foreach ($store_phones as $p) {
						if(preg_match('/^[0-9]{11}$/',$p)){
							$sms_data['uid'] = 0;
							$sms_data['mobile'] = $p;
							$sms_data['sendto'] = 'merchant';
							$sms_data['content'] = L_("顾客【X1】在X2时下了一个订单，订单号：X3请您注意查看并处理!",array("X1" => $order_data['username'],"X2" => date("Y-m-d H:i:s"),"X3" => $order_data['real_orderid']));
							Sms::sendSms($sms_data);
						}
					}
					
				}
            }

            /*增加记录系统订单 2019/01/04 */
            //增加搜索关键词  店铺名称 和 商品名称
            $goods_name = [];
            foreach ($return['goods'] as $v) {
                $goods_name[] = $v['name'];
            }

            $goods_name_str = rtrim(implode(',', $goods_name),',');
            $order_data['keywords'] = $return['store']['name'].','.$goods_name_str;

            D('System_order')->add_order('shop',$order_data,$order_id);

            return $order_id;
        } else {
            return 0;
        }
    }

    private function checkTime($businessTime, $time)
    {
        if (empty($businessTime)) return true;
        $time = strtotime(date('Y-m-d') . ' ' . date('H:i:s', $time));
        foreach ($businessTime as $row) {
            $s = strtotime(date('Y-m-d ' . $row['s']));
            $e = strtotime(date('Y-m-d ' . $row['e']));
            if ($s <= $time && $time <= $e) {
                return true;
            }
        }
        return false;
    }

    public function formatTime($data, &$date_list, $businessTime)
    {
        $time_select = $data['time_select'];
        $after_time = $data['after_time'];
        $start_time = $data['start_time'];
        $stop_time = $data['stop_time'];
        $time = $data['time'];
        $day = $data['day'];
        $delivery_fee = $data['delivery_fee'];

        $stime = strtotime(date('Y-m-d', strtotime('+' . $day . ' day')) . ' ' . $start_time) + $after_time;
        $etime = strtotime(date('Y-m-d', strtotime('+' . $day . ' day')) . ' ' . $stop_time);

        $next_stime = 0;
        $next_etime = 0;
        if ($etime < $stime) {
            $etime = strtotime(date('Y-m-d', strtotime('+' . $day . ' day')) . ' 23:59:59');
            $next_stime = strtotime(date('Y-m-d', strtotime('+' . $day . ' day')));
            $next_etime = strtotime(date('Y-m-d', strtotime('+' . $day . ' day')) . ' ' . $stop_time);
        }

        if ($stime < $time) $stime = $time;
        if ($etime > $stime) {
            $bigen_time = $stime;
            $_m_15 = strtotime(date('Y-m-d H:15', $stime));
            $_m_30 = strtotime(date('Y-m-d H:30', $stime));
            $_m_45 = strtotime(date('Y-m-d H:45', $stime));
            if ($_m_15 >= $stime) {
                $stime = $_m_15;
            } elseif ($_m_30 >= $stime) {
                $stime = $_m_30;
            } elseif ($_m_45 >= $stime) {
                $stime = $_m_45;
            } else {
                $stime = strtotime(date('Y-m-d H:00', $stime + 3600));
            }
            if ($this->checkTime($businessTime, $bigen_time)) {
                $date_list[date('Y-m-d', $bigen_time)][date('H:i', $bigen_time)] = array('hour_minute' => date('H:i', $bigen_time), 'time_select' => $time_select, 'delivery_fee' => $delivery_fee);
            }
            for ($now_date = $stime; $now_date <= $etime;) {
                if ($this->checkTime($businessTime, $now_date)) {
                    $date_list[date('Y-m-d', $now_date)][date('H:i', $now_date)] = array('hour_minute' => date('H:i', $now_date), 'time_select' => $time_select, 'delivery_fee' => $delivery_fee);
                }
                $now_date += 900;
            }
        }

        if ($next_stime != $next_etime) {
            $stime = $next_stime;
            $etime = $next_etime;
            if ($stime < $time) $stime = $time;
            if ($etime > $stime) {
                $bigen_time = $stime;
                $_m_15 = strtotime(date('Y-m-d H:15', $stime));
                $_m_30 = strtotime(date('Y-m-d H:30', $stime));
                $_m_45 = strtotime(date('Y-m-d H:45', $stime));
                if ($_m_15 >= $stime) {
                    $stime = $_m_15;
                } elseif ($_m_30 >= $stime) {
                    $stime = $_m_30;
                } elseif ($_m_45 >= $stime) {
                    $stime = $_m_45;
                } else {
                    $stime = strtotime(date('Y-m-d H:00', $stime + 3600));
                }
                if ($this->checkTime($businessTime, $bigen_time)) {
                    $date_list[date('Y-m-d', $bigen_time)][date('H:i', $bigen_time)] = array('hour_minute' => date('H:i', $bigen_time), 'time_select' => $time_select, 'delivery_fee' => $delivery_fee);
                }
                for ($now_date = $stime; $now_date <= $etime;) {
                    if ($this->checkTime($businessTime, $now_date)) {
                        $date_list[date('Y-m-d', $now_date)][date('H:i', $now_date)] = array('hour_minute' => date('H:i', $now_date), 'time_select' => $time_select, 'delivery_fee' => $delivery_fee);
                    }
                    $now_date += 900;
                }
            }

            $stime = $next_stime + 86400;
            $etime = $next_etime + 86400;
            if ($stime < $time) $stime = $time;
            if ($etime > $stime) {
                $bigen_time = $stime;
                $_m_15 = strtotime(date('Y-m-d H:15', $stime));
                $_m_30 = strtotime(date('Y-m-d H:30', $stime));
                $_m_45 = strtotime(date('Y-m-d H:45', $stime));
                if ($_m_15 >= $stime) {
                    $stime = $_m_15;
                } elseif ($_m_30 >= $stime) {
                    $stime = $_m_30;
                } elseif ($_m_45 >= $stime) {
                    $stime = $_m_45;
                } else {
                    $stime = strtotime(date('Y-m-d H:00', $stime + 3600));
                }
                if ($this->checkTime($businessTime, $bigen_time)) {
                    $date_list[date('Y-m-d', $bigen_time)][date('H:i', $bigen_time)] = array('hour_minute' => date('H:i', $bigen_time), 'time_select' => $time_select, 'delivery_fee' => $delivery_fee);
                }
                for ($now_date = $stime; $now_date <= $etime;) {
                    if ($this->checkTime($businessTime, $now_date)) {
                        $date_list[date('Y-m-d', $now_date)][date('H:i', $now_date)] = array('hour_minute' => date('H:i', $now_date), 'time_select' => $time_select, 'delivery_fee' => $delivery_fee);
                    }
                    $now_date += 900;
                }
            }
        }
    }

    /*获取最新购买的用户*/
    public function get_buy_user($goods_id=null,$limit=10,$get_count=0){
    	$where=array();
    	if($goods_id>0){
    		$where['de.goods_id']=$goods_id;
            $where['ord.paid']=1;
    	}
    	$default_avatar=C('config.site_url').'/tpl/Wap/pure/static/images/new_my/pic-default.png';
    	if($get_count==1){
    		$data=array();
    		//获取购买总数
    		$count_arr=$this->alias('ord')->join('inner join '.C("DB_PREFIX").'shop_order_detail de on ord.order_id=de.order_id')->join('inner join '.C('DB_PREFIX').'user us on ord.uid=us.uid')->where($where)->group('ord.uid')->getfield("count(1)",true);
    		$data['count']=count($count_arr);
    		//最新购买的用户
    		$list=$this->alias('ord')->join('inner join '.C("DB_PREFIX").'shop_order_detail de on ord.order_id=de.order_id')->join('inner join '.C('DB_PREFIX').'user us on ord.uid=us.uid')->where($where)->group('ord.uid')->order('ord.order_id desc')->field('us.nickname,us.avatar')->limit($limit)->select();
    		foreach ($list as $k => $v) {
				$v['nickname']=str_replace_name($v['nickname']);
				$v['avatar']=$v['avatar'] ? replace_file_domain($v['avatar']) : $default_avatar;
				$list[$k]=$v;
			}
    		$data['head_list']=$list;
    		return $data;
    	}else{
    		$list=$this->alias('ord')->join('inner join '.C("DB_PREFIX").'shop_order_detail de on ord.order_id=de.order_id')->join('inner join '.C('DB_PREFIX').'user us on ord.uid=us.uid')->where($where)->group('de.order_id')->order('ord.order_id desc')->field('ord.order_id,us.nickname,us.avatar')->limit($limit)->select();
    		foreach ($list as $k => $v) {
				$v['nickname'] = str_replace_name($v['nickname']);
				$v['avatar']=$v['avatar'] ? replace_file_domain($v['avatar']) : $default_avatar;
				$list[$k]=$v;
			}
    		return $list;
    	}
    }

    /*获取订单的商品详情*/
    public function get_good_details($order_id=null){
    	if(empty($order_id)){
    		return array();
    	}
    	$where=array();
    	$where['a.order_id']=$order_id;
    	//$list=M('Shop_order_detail')->alias("a")->join("left join ".C("DB_PREFIX")."shop_goods as b on a.goods_id=b.goods_id")->where($where)->field('a.id,a.goods_id,a.goods_image,b.name,a.num,a.spec_id,b.image as goods_image,a.price,a.discount_price')->select();
        $list=M('Shop_order_detail')->alias("a")->join("left join ".C("DB_PREFIX")."shop_goods as b on a.goods_id=b.goods_id"." LEFT JOIN ".C("DB_PREFIX")."shop_order AS c ON a.order_id = c.order_id")->where($where)->field('a.id,a.goods_id,a.goods_image,a.uniqueness_number,b.name,a.num,a.spec_id,b.image,a.price,a.discount_price,c.book_id,c.bargin_price,c.rest_price,c.book_status,c.book_discount_price,a.spec')->select();
    	$goods_list=array();
    	$arr=array();
    	$image_obj=new goods_image();
    	foreach ($list as $k => $v) {
    		$goods_id=$v['goods_id'];
			$uniqe = $goods_id.'_'.$v['uniqueness_number'];
    		if(in_array($uniqe, $arr)){
    			continue;
    		}else{
    			$arr[]=$uniqe;
				if(empty($v['goods_image'])){
					$v['goods_image'] = $v['image'];
				}
    			$v['goods_image']=$this->get_order_goods_img($v,$image_obj);
    			$v['price'] = $v['discount_price'];
				$v['num'] = $v['num'] ?: 1;
				$v['name'] = $v['name'] ?: '商品已下架';
				$v['sku_info'] = $v['spec']?:'';
	    		$goods_list[]=$v;
    		}
    	}
    	return (array)$goods_list;
    }

    //获取订单详情商品图片
    public function get_order_goods_img($info,$img_obj){
    	$image = explode(';',$info['goods_image']);
		$info['goods_image'] = $image[0];
    	if(!empty($info['goods_image']) && $info['goods_image'] != 'h'){
    		if(stripos($info['goods_image'],'?')){
    			$info['goods_image'] = $info['goods_image'];
    		}
    		if(stripos($info['goods_image'],',')===false){
    			return thumb($info['goods_image'], 200, 200);
    		}else{
    			$img_str=$info['goods_image'];
    		}
    	}else{
    		$img_str=D('Shop_goods')->where(array('goods_id'=>$info['goods_id']))->getfield('image');

			$data=array();
			$data['id']=$info['id'];
			$data['goods_image']=$img_str;
			M('shop_order_detail')->data($data)->save();
    	}
    	if(empty($img_obj)){
    		return '';
    	}
    	$img_arr=D('Shop_goods')->reg_good_img($img_str,$img_obj);
		//$goods_image=$img_arr['0']['url']['s_image'];
		$goods_image=$img_arr['0']['url'];

		return $goods_image;
    }

    /*-- 用友erp系统对接 start --*/
    //付款生成销货单、收款单；退货生成退货单、负数收款单
    //@param int $type 1-付款成功，2-发货，3-取消订单，4-操作申请的退款
    public function erp_after_pay($now_order,$type=1){
    	$SaleOrder=array();
    	$SaleOrder['System']=C('config.site_name');
    	$SaleOrder['WarehouseCode']='';
    	$SaleOrder['ErpOrder']=$now_order['real_orderid'];
    	if(empty($now_order['post_time'])){
    		$now_order['post_time']=time();
    	}
    	$ShipmentId  = date('ymdhis',$now_order['post_time']).substr(microtime(),2,8-strlen($now_order['uid'])).$now_order['uid'];
    	$SaleOrder['ShipmentId']=$ShipmentId;
    	$SaleOrder['ActualShipDateTime']=$now_order['post_time'];
    	if($type==1 || $type==2){
    		$SaleOrder['ErpOrderType']=L_('销售出库');
    	}else{
    		$SaleOrder['ErpOrderType']=L_('销售退货');
    	}
    	$SaleOrder['TotalAmount']=$now_order['price'];
    	$store_name=M('Merchant_store')->where(array('store_id'=>$now_order['store_id']))->getfield('name');
    	$SaleOrder['Department']=$store_name;
    	$SaleOrders['SaleOrder']=$SaleOrder;

    	//支付方式
    	$Settlements=array();
    	if($order['card_discount']!=0 && $order['card_discount']!=10){
    		$List=array();
    		$List['SettleStyle']=L_('商家会员卡折扣');
    		$discount_price = floatval(round($order['discount_price'] + $order['freight_charge'] + $order['packing_charge'], 2));
    		$minus_card_discount=strval(floatval(round(($discount_price - $order['merchant_reduce'] - $order['balance_reduce'] - $order['freight_charge']) * (1 - $order['card_discount'] * 0.1), 2)));

    		$List['OrigAmount']=$minus_card_discount;
    		$Settlements[]['List']=$List;
    	}
    	if($now_order['coupon_price']>0){
    		$List=array();
    		$List['SettleStyle']=L_('平台优惠券');
    		$List['OrigAmount']=$now_order['coupon_price'];
    		$Settlements[]['List']=$List;
    	}
    	if($now_order['card_price']>0){
    		$List=array();
    		$List['SettleStyle']=L_('商家优惠券');
    		$List['OrigAmount']=$now_order['card_price'];
    		$Settlements[]['List']=$List;
    	}
    	if($now_order['score_deducte']>0){
    		$List=array();
    		$List['SettleStyle']=C('config.score_name').L_('抵扣');
    		$List['OrigAmount']=$now_order['score_deducte'];
    		$Settlements[]['List']=$List;
    	}
    	if($now_order['merchant_balance']>0){
    		$List=array();
    		$List['SettleStyle']=L_('商家余额支付');
    		$List['OrigAmount']=$now_order['merchant_balance'];
    		$Settlements[]['List']=$List;
    	}
    	if($now_order['balance_pay']>0){
    		$List=array();
    		$List['SettleStyle']=L_('平台余额支付');
    		$List['OrigAmount']=$now_order['balance_pay'];
    		$Settlements[]['List']=$List;
    	}
    	if($now_order['payment_money']>0){
    		$pay_type=D('Pay')->get_pay_name($now_order['pay_type'], $now_order['is_mobile_pay'], $now_order['paid']);

    		$List=array();
    		$List['SettleStyle']=$pay_type;
    		$List['OrigAmount']=$now_order['payment_money'];
    		$Settlements[]['List']=$List;
    	}
    	$SaleOrders['Settlements']=$Settlements;
    	/*商品信息*/
    	$list=M('Shop_order_detail')->where(array('order_id'=>$now_order['order_id']))->field('goods_id,number,num,refundNum,price')->select();
    	$Items=array();
    	foreach ($list as $k => $v) {
    		$Item=array();
    		$Item['ErpOrderLineNum']='';
    		if(!empty($v['number'])){
    			$Item['SkuNo']=$v['number'];
    		}else{
    			$SkuNo=M('Shop_goods')->where(array('goods_id'=>$v['goods_id']))->getfield('SkuNo');
    			$Item['SkuNo']=$SkuNo;
    		}
    		if($type==3){
    			$Item['ActualQty']=$v['num']*-1;
    		}elseif($type==4 && $v['refundNum']>0){
    			$Item['ActualQty']=$v['refundNum']*-1;
    		}else{
    			$Item['ActualQty']=$v['num'];
    		}
    		$Item['ItemPrice']=$v['price'];
    		$Item['ItemDiscount']=$v['num']*$v['price'];
    		$Items[]['Item']=$Item;
    	}
    	$SaleOrders['Items']=$Items;

    	$RequestArr=array();
    	$RequestArr['SaleOrderOutboundDetailRequest']['CompanyCode']='';
    	$RequestArr['SaleOrderOutboundDetailRequest']['SaleOrders']=$SaleOrders;

    	$url='http://218.202.103.74:6030/tinterface/Sale2.aspx';
    	$res=$this->erp_request($url,$RequestArr,'SALE_ORDER_OUTBOUND_DETAIL_PUSH_SERVICE');
    	$info=$res['1'];
    	fdump($info,'erp',1);
    }

    //生成出库单
    public function create_delivery_form($now_order){
    	$SaleOut=array();
    	$SaleOut['WarehouseCode']=$now_order['store_id'];
    	$SaleOut['ErpOrder']=$now_order['real_orderid'];

    	$ShipmentId  = date('ymdhis',$now_order['post_time']).substr(microtime(),2,8-strlen($now_order['uid'])).$now_order['uid'];
    	$SaleOut['ShipmentId']=$ShipmentId;
    	$SaleOut['ActualShipDateTime']=$now_order['post_time'];
    	$SaleOut['ErpOrderType']=L_('销售出库');
    	$SaleOut['TotalAmount']=$now_order['price'];
    	$store_name=M('Merchant_store')->where(array('store_id'=>$now_order['store_id']))->getfield('name');
    	$SaleOut['Department']=$store_name;

    	$RequestArr=array();
    	$RequestArr['SaleOutRequest']['CompanyCode']='';
    	$RequestArr['SaleOutRequest']['SaleOuts']['SaleOut']=$SaleOut;

    	$url='http://218.202.103.74:6030/tinterface/Out1.aspx';
    	$res=$this->erp_request($url,$RequestArr,'SALE_ORDER_OUTBOUND_PUSH_SERVICE');
    	$info=$res['1'];
    	fdump($info,'erp',1);

    }

    public function erp_request($url,$body,$service){
    	if(empty($url)){
    		return false;
    	}
    	$xml='<Request service="'.$service.'" lang="zh-CN">';
    	$xml.='<Body>';
    	$xml.=$this->arrayToXml($body);
    	$xml.='</Body>';
    	$xml.='</Request>';
    	/*本地调试*/
    	//$url='http://www.group.com/appapi.php?c=Erpapi&a=save_goods_num&mer_id=1&store_id=2';
    	$data=array();
    	$data['logistics_interface']=$xml;
    	$key=C('config.yongyou_erp_key');
    	$data['data_digest']=base64_encode(md5($xml.$key));
    	return httpRequest($url,'post',$data);
    }

    /*数组格式化成xml数据*/
    public function arrayToXml($data){
    	$xml='';
    	foreach ($data as $k => $v) {
    		if(is_array($v)){
    			if(is_numeric($k)){
    				$xml.=$this->arrayToXml($v);
    			}else{
    				$xml.='<'.$k.'>'.$this->arrayToXml($v).'</'.$k.'>';
    			}
    		}else{
    			$xml.='<'.$k.'>'.$v.'</'.$k.'>';
    		}
    	}
    	return $xml;
    }
    /*-- 用友erp系统对接 end --*/

    /**
     * 获取订单来源
     */
    public function getOrderFromTag($orderFrom)
    {
        $tag = $this->order_from_list;
        return isset($tag[$orderFrom]) ? $tag[$orderFrom] : $orderFrom;
    }

    // 营业分析获取数据
    public function getHistoryStatistics($condition,$m_condition,$discount_info=0,$shop_type=0){
        // 已支付
        $condition['paid'] = 1;
        $m_condition['o.paid'] = 1;

        if($shop_type==1){//商城
        	$condition['order_from'] = 1;
	        $m_condition['o.order_from'] = 1;
        }else{//默认：快店
        	$condition['order_from'] = ['neq',1];
	        $m_condition['o.order_from'] = ['neq',1];
        }

        $condition['order_status'] = array('gt',0);  // 已接单
        $condition['status'] = array('in','0,1,2,3');  // 未取消 未退款

        /* 营业额 0点到现在(已支付订单) */
        $total_price_today = $this->field('sum(price) as total_price')->where($condition)->find();
        $returnArr['total_money'] = getFormatNumber($total_price_today['total_price']);

        /* 订单收入 已对账金额包括自由支付 扣除：1. 平台抽成   2. 平台配送费   3. 平台满减补贴   4. 商家满减补贴   5. 申请售后退款金额 */
        // 获得的金额
        $m_condition['m.type'] = 'shop';
        $m_condition['m.income'] = 1;
        $income_money = D('merchant_money_list')->field('sum(money) as money')->join(' as m join '.C('DB_PREFIX').'shop_order as o on o.real_orderid=m.order_id')->where($m_condition)->find();

        // 支出的金额
        $m_condition['m.income'] = 2;
        $pay_money = D('merchant_money_list')->field('sum(money) as money')->join(' as m join '.C('DB_PREFIX').'shop_order as o on o.real_orderid=m.order_id')->where($m_condition)->find();
        $returnArr['mer_income'] = getFormatNumber($income_money['money']) - getFormatNumber($pay_money['money']);

        // 自有支付
        // 自有支付方式支付的金额
        $condition['is_own'] = ['gt',0];
        $condition['status'] = ['in',[2,3,4,5]]; // 2：已使用，3：已评论，:4：退款，5：取消
        $own_money = M('Shop_order')->field('sum(payment_money) as payment_money')->where($condition)->find();
        $own_money = getFormatNumber($own_money['payment_money']);

        // 自有支付方式退款的金额
        $m_condition['o.is_own'] = ['gt',0];
        $m_condition['o.status'] = ['in',[2,3,4,5]]; // 2：已使用，3：已评论，:4：退款，5：取消
        $refund_money1 = D('Shop_order_refund')->field('sum(payment_money) as payment_money')->join(' as m join '.C('DB_PREFIX').'shop_order as o on o.order_id=m.order_id')->where($m_condition)->find();
        $refund_money1 = getFormatNumber($refund_money1['payment_money']);

        $refund_money2 = D('Shop_order_system_refund')->field('sum(payment_money) as payment_money')->join(' as m join '.C('DB_PREFIX').'shop_order as o on o.order_id=m.order_id')->where($m_condition)->find();
        $refund_money2 = getFormatNumber($refund_money2['payment_money']);
        $returnArr['mer_income'] += getFormatNumber($own_money-$refund_money1-$refund_money2);

        /* 有效订单：已接单，且未被取消、未申请售后全部商品退款的订单数；取消包括平台、店员、用户操作的取消 */
        unset($condition['is_own']);
        $condition['order_status'] = array('gt',0);  // 已接单
        $condition['status'] = array('in','0,1,2,3');  // 未取消 未退款
        $returnArr['effective_count'] = M('Shop_order')->where($condition)->count();

        /* 活动补贴：：平台/商家满减优惠与平台/商家优惠券补贴总额，包含：1. 平台满减优惠平台补贴金额部分，含满减优惠的所有类型  2. 平台满减优惠商家补贴金额部分，含满减优惠的所有类型  3. 商家自有满减优惠商家补贴金额部分   4. 平台优惠券平台补贴金额部分   5. 商家优惠券商家补贴金额部分   6. 平台积分抵扣平台补贴部分 */
        $condition['order_status'] = array('gt',0);  // 已接单
        $condition['status'] = array('in','0,1,2,3');  // 未取消 未退款
        $discount_money = M('Shop_order')->field('sum(platform_plat) as platform_plat,sum(merchant_reduce) as merchant_reduce,sum(card_price) as card_price,sum(coupon_price) as coupon_price,sum(score_deducte) as score_deducte,sum(packing_charge) as packing_charge')->where($condition)->find();
        $returnArr['discount_money'] = getFormatNumber($discount_money['platform_plat']+$discount_money['merchant_reduce']+$discount_money['card_price']+$discount_money['coupon_price']+$discount_money['score_deducte']);

		// 计算平台优惠券平台补贴和商家补贴部分
        $system_coupon_list = M('Shop_order')->field('coupon_price,coupon_id,uid')->where($condition)->select();
        $coupon_plat_money = 0;
        $coupon_merchant_money = 0;
        foreach($system_coupon_list as $coupon){
        	$d_money = D('System_coupon')->getCouponDiscountMoney($coupon);
        	$coupon_plat_money += $d_money['plat_money'];
        	$coupon_merchant_money += $d_money['merchant_money'];
        }
        $discount_money['coupon_price'] = $returnArr['coupon_price'] = getFormatNumber($coupon_plat_money);
        $discount_money['card_price']  += $coupon_merchant_money;

        // 返回优惠详情
        if ($discount_info) {
        	// 平台满减补贴金额
        	$returnArr['discount_info']['platform_plat'] = getFormatNumber($discount_money['platform_plat']);
        	// 商家满减补贴金额
        	$returnArr['discount_info']['merchant_reduce'] = getFormatNumber($discount_money['merchant_reduce']);
        	// 平台优惠券补贴金额
        	$returnArr['discount_info']['coupon_price'] = getFormatNumber($discount_money['coupon_price']);
        	// 商家优惠券补贴金额
        	$returnArr['discount_info']['card_price'] = getFormatNumber($discount_money['card_price']);
        	// 平台积分补贴金额
        	$returnArr['discount_info']['score_deducte'] = getFormatNumber($discount_money['score_deducte']);


        	// 平台补贴总额
        	$returnArr['discount_info']['plat_total'] = getFormatNumber($discount_money['platform_plat']+$discount_money['coupon_price']+$discount_money['score_deducte']);
        	// 打包费
        	$returnArr['discount_info']['merchant_total'] = getFormatNumber($discount_money['merchant_reduce']+$discount_money['card_price']);

        	// 获取平台配送费
        	$condition['order_status'] = array('gt',0);  // 已接单
	        $condition['status'] = array('in','0,1,2,3');  // 未取消 未退款
	        $condition['is_pick_in_store'] = 0;  // 未取消 未退款
	        $freight_charge = M('Shop_order')->field('sum(freight_charge) as freight_charge')->where($condition)->find();
	        $returnArr['discount_info']['plat_freight_charge'] = getFormatNumber($freight_charge['freight_charge']);

        	// 获取商家配送费
        	$condition['order_status'] = array('gt',0);  // 已接单
	        $condition['status'] = array('in','0,1,2,3');  // 未取消 未退款
	        $condition['is_pick_in_store'] = 1;  // 未取消 未退款
	        $freight_charge = $this->field('sum(freight_charge) as freight_charge')->where($condition)->find();
	        $returnArr['discount_info']['merchant_freight_charge'] = getFormatNumber($freight_charge['freight_charge']);
	        unset($condition['is_pick_in_store']);
        }

        /* 客单价：有效订单的平均单价。 */
        if ($returnArr['effective_count']) {
            $returnArr['unit_price'] = getFormatNumber($returnArr['mer_income']/$returnArr['effective_count']);
        }else{
            $returnArr['unit_price'] = '0.00';
        }

        /* 下单用户数 */
        unset($condition['status']);
        $user_count_today = $this->field('count(distinct(uid)) as user_count')->where($condition)->find();
        $returnArr['user_count'] = $user_count_today['user_count'];


        /* 无效订单：包括用户主动及平台、店员主动取消与申请售后全部商品退款的订单。 */
        unset($condition['order_status']);
        $condition['status'] = array('in','4,5'); // 未取消 未退款
        $returnArr['invalid_count'] = $this->where($condition)->count();

        /* 损失：包括用户主动及平台、店员主动取消与申请售后全部商品退款的退款金额。 */
        unset($condition['order_status']);
        $condition['status'] = array('in','4,5'); // 未取消 未退款
        $price_today = $this->field('sum(price) as price')->where($condition)->find();
        $returnArr['loss_money'] = getFormatNumber($price_today['price']);

        return $returnArr;
    }

    /**营业分析 对比数据*/
    public function statisticsFormat($data,$param='',$diff='',$unit='',$compare_text=''){
        if(!$compare_text){
           $compare_text = L_('比昨日同时段');
        }
        $returnArr = [];
        $data = explode('.', $data);
        $returnArr[$param.'_0'] = $data[0] ? $data[0] : '0';
        $returnArr[$param.'_1'] = $data[1] ? '.'.$data[1] : '.00';

        if ($diff>0) { // 上升
            $returnArr[$param.'_text'] = $compare_text.'<embed src="./tpl/Merchant/default/static/images/up.svg" class="logo"><span style="color: red">'.$diff.$unit.'</span></embed>';
        }elseif ($returnArr['total_price_diff']==0) { // 持平
            $returnArr[$param.'_text'] = $compare_text.L_('持平');
        }else{ // 下降
            $returnArr[$param.'_text'] = $compare_text.'<embed src="./tpl/Merchant/default/static/images/down.svg" class="logo"><span style="color: red">'.$diff.$unit.'</span></embed>';
        }
        return $returnArr;
    }

     /**
     * author htm
     * description 计算退款时每种支付方式需要退款的金额
     * data 20191219 09:44
     * param uid 用户ID
     * param order_id 订单ID
     * param order_type 订单类型 shop-外卖 mall-商城
     * param refundMoney 本次退款金额
     * return
    */
    public function getRrfundDetail($refundMoney,$returnScoreBack){
		$backMoney = $returnScoreBack['backMoney'];
        $score_used_count=0;
        $score_deducte=0;
        if ($refundMoney['score_deducte']>0 ) {
            if ($backMoney) {
                // 减去需要扣除的积分
                $score_used_count =  getFormatNumber($refundMoney['score_used_count'] - $returnScoreBack['backScore']);
                if ($score_used_count<0) {
                    // 积分不够抵扣 则计算还需抵扣的金额 按比例算
                    $backMoney = getFormatNumber($refundMoney['score_used_count']/$returnScoreBack['backScore']*$backMoney);
                    $score_used_count = 0;
                    $score_deducte =  0;
                }else{
                    $score_deducte = getFormatNumber($refundMoney['score_deducte']/$refundMoney['score_used_count']*$score_used_count);

                    // 积分足够抵扣
                    $backMoney = 0;
                }
            }else{
                $score_used_count =  $refundMoney['score_used_count'];
                $score_deducte =  $refundMoney['score_deducte'];
            }
        }

        /*退还的平台E卡的金额*/
        if($refundMoney['ecard_pay']>0){
            if ($backMoney) {
            	$ecard_pay = $refundMoney['ecard_pay'] - $backMoney;
            	if ($ecard_pay<0) {
                    // 不够抵扣
            		$backMoney -= $ecard_pay;
            		$ecard_pay = 0;
            	}else{
                    // 足够抵扣
            		$backMoney=0;
            	}
            }else{
            	$ecard_pay = $refundMoney['ecard_pay'];
            }
        }

        $balance_pay = 0;
        if ($refundMoney['balance_pay'] > 0) {
            if ($backMoney) {
            	$balance_pay = $refundMoney['balance_pay'] - $backMoney;
            	if ($balance_pay<0) {
                    // 不够抵扣
            		$backMoney -= $balance_pay;
            		$balance_pay = 0;
            	}else{
                    // 足够抵扣
            		$backMoney=0;
            	}
            }else{
            	$balance_pay = $refundMoney['balance_pay'];
            }
        }

        $merchant_balance = 0;
        $card_give_money = 0;

        if ($refundMoney['card_give_money']>0 ) {
            if ($backMoney) {
            	$card_give_money = $refundMoney['card_give_money'] - $backMoney;
            	if ($card_give_money<0) {
                    // 不够抵扣
            		$backMoney -= $card_give_money;
            		$card_give_money = 0;
            	}else{
                    // 足够抵扣
            		$backMoney=0;
            	}
            }else{
            	$card_give_money = $refundMoney['card_give_money'];
            }
        }
        if ($refundMoney['merchant_balance']>0 ) {
            if ($backMoney) {
            	$merchant_balance = $refundMoney['merchant_balance'] - $backMoney;
            	if ($merchant_balance<0) {
                    // 不够抵扣
            		$backMoney -= $merchant_balance;
            		$merchant_balance = 0;
            	}else{
                    // 足够抵扣
            		$backMoney=0;
            	}
            }else{
            	$merchant_balance = $refundMoney['merchant_balance'];
            }
        }

		$employee_score_pay = 0;
		if($refundMoney['employee_score_pay']){
			if ($backMoney) {
            	$employee_score_pay = $refundMoney['employee_score_pay'] - $backMoney;
            	if ($employee_score_pay<0) {
                    // 不够抵扣
            		$backMoney -= $employee_score_pay;
            		$employee_score_pay = 0;
            	}else{
                    // 足够抵扣
            		$backMoney=0;
            	}
            }else{
            	$employee_score_pay = $refundMoney['employee_score_pay'];
            }
		}
		$employee_balance_pay = 0;
		if($refundMoney['employee_balance_pay']){
			if ($backMoney) {
            	$employee_balance_pay = $refundMoney['employee_balance_pay'] - $backMoney;
            	if ($employee_score_pay<0) {
                    // 不够抵扣
            		$backMoney -= $employee_balance_pay;
            		$employee_balance_pay = 0;
            	}else{
                    // 足够抵扣
            		$backMoney=0;
            	}
            }else{
            	$employee_balance_pay = $refundMoney['employee_balance_pay'];
            }
		}

		$returnArr = array(
			'score_used_count' => $score_used_count,
			'score_deducte'=>$score_deducte,
			'card_give_money'=>$card_give_money,
			'merchant_balance'=>$merchant_balance,
			'employee_score_pay'=>$employee_score_pay,
			'employee_balance_pay'=>$employee_balance_pay,
			'balance_pay'=>$balance_pay,
			'ecard_pay'=>$ecard_pay,
			'payment_money'=>$payment_money,
			'backMoney'=>$backMoney,
		);
        return $returnArr;
    }

    // 分发用户三级分佣获得的金额
    public function spreadSend($now_order){

    	// 类型
        if ($now_order['order_from'] == 1) {
            $from_type = 'mall';
        } else {
            $from_type = 'shop';
        }

        // 分佣列表
        $spread_condition['order_type'] = $from_type;
        $spread_condition['order_id'] = $now_order['order_id'];
        $spread_condition['status'] = 0;//未到账
        $now_spread = D('User_spread_list')->where($spread_condition)->select();

        fdump(D('User_spread_list')->_sql(),'plan_shop_order_spread',1);
        fdump($now_spread,'plan_shop_order_spread',1);
        // 无分佣记录 直接返回
        if(!$now_spread){
        	// 将订单状态改为已分佣
			M('Shop_order')->where(['order_id'=>$now_order['order_id']])->setField('is_spread',1);
        	return true;
        }

        // 退款金额
    	$refund_money = $this->getOrderRefundMoney($now_order['order_id']);

        fdump('refund_money','plan_shop_order_spread',1);
        fdump($refund_money,'plan_shop_order_spread',1);
		// 在线支付金额
		$payment_money_order = M('Payment_money_order')->where(array('order_id'=>$now_order['order_id'],'order_type'=>'shop','pay_type'=>$now_order['pay_type'],'paid_money'=>array('gt',0)))->find();
		if($now_order['payment_money'] == $payment_money_order['payment_money']){
			//可退金额包括用户实际支付的支付手续费
			$now_order['payment_money'] = $payment_money_order['paid_money'];
		}

		// 参与分佣的金额
		$spread_total_money_old = 0;
		$spread_total_money = 0;//减去退款金额

		// 用户实际支付金额
		$total_money =  $now_order['payment_money']+$now_order['balance_pay']+$now_order['merchant_balance']+$now_order['card_give_money']+$now_order['score_deducte'];

    	if(C('config.open_score_pay_back') && C('config.system_take_spread_percent')>0 ){
    		// 退款金额
    		$refundMoney = $refund_money['balance_pay']+$refund_money['payment_money']+$refund_money['score_deducte']+$refund_money['merchant_balance'];

    		// 分佣金额
			$spread_total_money_old = getFormatNumber($now_order['balance_pay']+$now_order['payment_money']+$now_order['score_deducte']+$now_order['coupon_price']+$now_order['merchant_balance']+$now_order['balance_reduce']-$now_order['no_bill_money']);

    		// 分佣金额-退款金额
			$spread_total_money = getFormatNumber($spread_total_money_old-$refundMoney);

			$percent = D('Percent_rate')->get_percent($now_order['mer_id'],$from_type,$spread_total_money, '',$now_order['store_id'], true);
			$spread_total_money = getFormatNumber($spread_total_money*$percent/100);
		}else{
    		// 退款金额
    		$refundMoney = $refund_money['balance_pay']+$refund_money['payment_money'];

    		// 分佣金额
			$spread_total_money_old = $now_order['balance_pay'] + $now_order['payment_money']-$now_order['freight_charge'];

    		// 分佣金额-退款金额
			$spread_total_money = getFormatNumber($spread_total_money_old-$refundMoney);
		}

		if(C('config.add_spread_by_system_commission')){// 根据平台抽成获得佣金
			// 获取平台抽成金额
			$where = [];
			$where['type'] = $from_type;
			$where['order_id'] = $now_order['real_orderid'];
			$where['income'] = 1;
			$spread_total_money =  D('Merchant_money_list')->where($where)->getField('system_take');
		}

		// 三级分佣比例
        $spread_rate = D('Percent_rate')->get_user_spread_rate($now_order['mer_id'], $from_type ,$now_order['order_id'],$now_order);

        // 查询是否参与分账 如果参与更新分账信息
        $where_share = [
        	'order_type' => $from_type,
        	'order_id' => $now_order['order_id'],
        	'is_withdraw' => 0,
        ];
        $profit_sharing = M('Weixin_profit_sharing_record')->where($where_share)->find();
        if($profit_sharing){
        	$not_add_money = true;
        	$receivers = json_decode($profit_sharing['receivers'], true);
        	$system_take = 0;
        	$share_spread_total = 0;
        	foreach($receivers as $_receivers){
        		if($_receivers['type'] == 'MERCHANT_ID'){
        			$system_take = $_receivers['amount']/100;
        		}else{
        			$share_spread_total += $_receivers['amount']/100;
        		}
        	}
        }

		// 重新计算分佣金额 然后给用户分发佣金
        foreach($now_spread as $v){
        	switch ($v['level']) {
        		case '1':
        			$user_spread_rate = $spread_rate['first_rate'];
        			break;
        		case '2':
        			$user_spread_rate = $spread_rate['second_rate'];
        			break;
        		case '3':
        			$user_spread_rate = $spread_rate['third_rate'];
        			break;
        	}

            if ($spread_rate['goods_percent']==1) { // 开启每个商品单独佣金
				$spread_money = $user_spread_rate;
			}else{
				$spread_money = round($spread_total_money * $user_spread_rate / 100, 2);
			}

			if($spread_money > 0 && $v['spread_type'] == 0){// 保存分账信息 用户获得的分账
				$share_spread_total_new += $spread_money;
                $spread_user = D('User')->get_user($v['uid']);
                if($spread_user && $spread_user['openid']){
                    $receivers_new[] = [
                        'type' => 'PERSONAL_OPENID',
                        'account' => $spread_user['openid'],
                        'amount' => $spread_money * 100,
                        'description' => '商家分账',
                    ];
                }
            }

			// 保存现在应得的金额
			$save_date = ['money'=>$spread_money,'old_money'=>$v['money']];
	        fdump('save_date','plan_shop_order_spread',1);
	        fdump($save_date,'plan_shop_order_spread',1);
			if(D('User_spread_list')->where(['pigcms_id'=>$v['pigcms_id']])->save($save_date)){
				// 分发佣金
            	D('Merchant_money_list')->spread_check($v['uid'],$v['pigcms_id'], $not_add_money);
			}
        }

        if (!C('config.open_spread_by_merchant')) {// 是否开启推广佣由金商家支付
            // 平台所得抽成
            $system_take = $system_take + $share_spread_total - $share_spread_total_new; // 扣除用户分佣的金额
        }

        if($system_take > 0){// 平台获得的分账
            $receivers_new[] = [
                'type' => 'MERCHANT_ID',
                'account' => $pay_config['pay_weixin_mchid'],
                'amount' => $system_take * 100,
                'description' => '商家分账',
            ];
        }

        // 更新分账信息
        $saveShareData = [
        	'receivers' => json_encode($receivers_new),
            'is_withdraw' =>  1,// 佣金需要不可售后才能结算也要结算后才能分账
        ];
        M('Weixin_profit_sharing_record')->where($where_share)->data($saveShareData)->save();



        // 将订单状态改为已分佣
		D('Shop_order')->where(['order_id'=>$now_order['order_id']])->setField('is_spread',1);
		return true;
    }

    // 获得订单退款金额
    public function getOrderRefundMoney($order_id){
    	// 已退总金额
		// 用户申请退款
		$refund_money = M('Shop_order_refund')->field('sum(price) as r_price,sum(balance_pay) as r_balance_pay,sum(merchant_balance) as r_merchant_balance,sum(card_give_money) as r_card_give_money,sum(payment_money) as r_payment_money,sum(score_deducte) as r_score_deducte,sum(score_used_count) as r_score_used_count')->where(array('order_id'=>$order_id,'status'=>array('in','0,1,3')))->find();

		// 平台手动退款
		$refund_money_system = M('Shop_order_system_refund')->field('sum(price) as r_price,sum(balance_pay) as r_balance_pay,sum(merchant_balance) as r_merchant_balance,sum(card_give_money) as r_card_give_money,sum(payment_money) as r_payment_money,sum(score_deducte) as r_score_deducte,sum(score_used_count) as r_score_used_count')->where(array('order_id'=>$order_id,'status'=>1))->find();

		$refund_money['price'] = round(floatval($refund_money['r_price']+$refund_money_system['r_price']),2);
		$refund_money['balance_pay'] = round(floatval($refund_money['r_balance_pay']+$refund_money_system['r_balance_pay']),2);
		$refund_money['merchant_balance'] = round(floatval($refund_money['r_merchant_balance']+$refund_money_system['r_merchant_balance']),2);
		$refund_money['card_give_money'] = round(floatval($refund_money['r_card_give_money']+$refund_money_system['r_card_give_money']),2);
		$refund_money['payment_money'] = round(floatval($refund_money['r_payment_money']+$refund_money_system['r_payment_money']),2);
		$refund_money['score_deducte'] = round(floatval($refund_money['r_score_deducte']+$refund_money_system['r_score_deducte']),2);
		$refund_money['score_used_count'] = round(floatval($refund_money['r_score_used_count']+$refund_money_system['r_score_used_count']),2);
		return $refund_money;
    }

    // 系统后台营业分析获取数据
    public function getRevenueStatistics($mer_id,$date,$param=array(),$shop_type=0){
    	// 开始时间
        $start_time = $date;
        // 结束时间
        if($param['end_time']){
        	$end_time = $param['end_time'];
        }else{
        	$end_time = $start_time+86400-1;
        }

        // 已支付
        $condition['paid'] = 1;
        $m_condition['o.paid'] = 1;

        if($shop_type==1){//商城
        	$condition['order_from'] = 1;
        	$m_condition['o.order_from'] = 1;
        }else{//默认快店
    		$condition['order_from'] = ['neq',1];
        	$m_condition['o.order_from'] = ['neq',1];
        }

        // 支付时间
        $condition['pay_time'] = array('between',[$start_time,$end_time]);
        $m_condition['o.pay_time'] = array('between',[$start_time,$end_time]);
        if (!$mer_id) {
        	if ($param['area_id'] && $param['area_id'] != 'undefined') {
	            $where_mer['area_id'] = $param['area_id'];
	        } else if ($param['city_idss'] && $param['city_idss'] != 'undefined') {
	            $where_mer['city_id'] = $param['city_idss'];
	        } else if ($param['province_idss'] && $param['province_idss'] != 'undefined') {
	            $where_mer['province_id'] = $param['province_idss'];
	        }

	        if(C('customization.life_tools') && $param['system_session']['level'] != 2){// 城投生活通定制 商家绑定管理员
	        	$adminIdArr = D('Admin')->getAdminByFid($param['system_session']['id']);
	            $where_mer['admin_id'] = ['in', $adminIdArr];	
	        }

	        if ($where_mer) {
	            $mer_ids = M('Merchant')->where($where_mer)->select();

	            foreach ($mer_ids as $st) {
	                $mer_id_arr[] = $st['mer_id'];
	            }   	
	            if (!$mer_id_arr) {
	                return array();
	            }
		        $condition['mer_id'] = array('in',$mer_id_arr);
		        $m_condition['o.mer_id'] = array('in',$mer_id_arr);
	        }
        }else{
	        // 商家id
	        $condition['mer_id'] = $mer_id;
	        $m_condition['o.mer_id'] = $mer_id;
        }

        // 店铺id
        if ($param['store_list']&&$param['store_list']!=-1) {
	        $condition['store_id'] = array('in',$param['store_list']);
	        $m_condition['o.store_id'] = array('in',$param['store_list']);
        }

        // 已支付
        $condition['paid'] = 1;
        $m_condition['o.paid'] = 1;

        // 有效订单
        $condition['order_status'] = array('gt',0);  // 已接单
        $condition['status'] = array('in','0,1,2,3,7,8,9,10');  // 未取消 未退款

        /* 营业额 0点到现在(已支付订单) */
        $total_price_today = $this->field('sum(total_price) as total_price')->where($condition)->find();
        // var_dump($this->_sql());die;
        $returnArr['total_money'] = getFormatNumber($total_price_today['total_price']);
        // var_dump($param);
        // var_dump(M('Shop_order')->_sql());

        /* 订单收入 已对账金额包括自由支付 扣除：1. 平台抽成   2. 平台配送费   3. 平台满减补贴   4. 商家满减补贴   5. 申请售后退款金额 */
        // 获得的金额
        $m_condition['m.type'] = 'shop';
        $m_condition['m.income'] = 1;
        $income_money = M('Merchant_money_list')->field('sum(money) as money, sum(system_take) as system_take')->join(' as m join '.C('DB_PREFIX').'shop_order as o on o.real_orderid=m.order_id')->where($m_condition)->find();

        // 支出的金额
        $m_condition['m.income'] = 2;
        $pay_money = M('Merchant_money_list')->field('sum(money) as money, sum(system_take) as system_take')->join(' as m join '.C('DB_PREFIX').'shop_order as o on o.real_orderid=m.order_id')->where($m_condition)->find();
        $returnArr['mer_income'] = getFormatNumber($income_money['money']) - getFormatNumber($pay_money['money']);
        // var_dump(D('merchant_money_list')->_sql());
        // 自有支付
        // 自有支付方式支付的金额
        $condition['is_own'] = ['gt',0];
        $condition['status'] = ['in',[2,3,4,5,7,8,9,10]]; // 2：已使用，3：已评论，:4：退款，5：取消
        $own_money = $this->field('sum(payment_money) as payment_money')->where($condition)->find();
        $own_money = getFormatNumber($own_money['payment_money']);

        // 自有支付方式退款的金额
		unset($m_condition['m.type'], $m_condition['m.income']);
        $m_condition['o.is_own'] = ['gt',0];
        $m_condition['o.status'] = ['in',[2,3,4,5,7,8,9,10]]; // 2：已使用，3：已评论，:4：退款，5：取消
        $refund_money1 = D('Shop_order_refund')->field('sum(m.payment_money) as payment_money')->join(' as m join '.C('DB_PREFIX').'shop_order as o on o.order_id=m.order_id')->where($m_condition)->find();
        $refund_money1 = getFormatNumber($refund_money1['payment_money']);

        $refund_money2 = D('Shop_order_system_refund')->field('sum(m.payment_money) as payment_money')->join(' as m join '.C('DB_PREFIX').'shop_order as o on o.order_id=m.order_id')->where($m_condition)->find();
        $refund_money2 = getFormatNumber($refund_money2['payment_money']);
        $returnArr['mer_income'] += getFormatNumber($own_money-$refund_money1-$refund_money2);

        /* 平台抽成 */
        $returnArr['plat_get_commission'] = getFormatNumber($income_money['system_take']-$pay_money['system_take']);


        /* 有效订单：已接单，且未被取消、未申请售后全部商品退款的订单数；取消包括平台、店员、用户操作的取消 */
        unset($condition['is_own']);
        $condition['order_status'] = array('gt',0);  // 已接单
        $condition['status'] = array('in','0,1,2,3,7,8,9,10');  // 未取消 未退款
        $returnArr['effective_count'] = M('Shop_order')->where($condition)->count();

        /* 活动补贴：：平台/商家满减优惠与平台/商家优惠券补贴总额，包含：1. 平台满减优惠平台补贴金额部分，含满减优惠的所有类型  2. 平台满减优惠商家补贴金额部分，含满减优惠的所有类型  3. 商家自有满减优惠商家补贴金额部分   4. 平台优惠券平台补贴金额部分   5. 商家优惠券商家补贴金额部分   6. 平台积分抵扣平台补贴部分 */
        $condition['order_status'] = array('gt',0);  // 已接单
        $condition['status'] = array('in','0,1,2,3');  // 未取消 未退款
        $discount_money = M('Shop_order')->field('sum(platform_plat) as platform_plat,sum(merchant_reduce) as merchant_reduce,sum(card_price) as card_price,sum(coupon_price) as coupon_price,sum(score_deducte) as score_deducte,sum(packing_charge) as packing_charge,sum(vip_level_reduce_money) as vip_level_reduce_money')->where($condition)->find();
        $returnArr['discount_money'] = getFormatNumber($discount_money['platform_plat']+$discount_money['merchant_reduce']+$discount_money['card_price']+$discount_money['coupon_price']+$discount_money['score_deducte']);

    	// 平台满减补贴金额
    	$returnArr['platform_plat'] = getFormatNumber($discount_money['platform_plat']);

    	// 平台优惠券补贴金额
    	$returnArr['coupon_price'] = getFormatNumber($discount_money['coupon_price']);

    	// 平台积分补贴金额
    	$returnArr['score_deducte'] = getFormatNumber($discount_money['score_deducte']);

    	// 平台会员等级优惠补贴总额
    	$returnArr['vip_level_reduce_money'] = getFormatNumber($discount_money['vip_level_reduce_money']);

    	// 计算平台优惠券平台补贴和商家补贴部分
        $system_coupon_list = M('Shop_order')->field('coupon_price,coupon_id,uid')->where($condition)->select();
        $coupon_plat_money = 0;
        $coupon_merchant_money = 0;
        foreach($system_coupon_list as $coupon){
        	$d_money = D('System_coupon')->getCouponDiscountMoney($coupon);
        	$coupon_plat_money += $d_money['plat_money'];
        	$coupon_merchant_money += $d_money['merchant_money'];
        }
        $discount_money['coupon_price'] = $returnArr['coupon_price'] = getFormatNumber($coupon_plat_money);
        $discount_money['card_price']  += $coupon_merchant_money;

    	/* 平台送出积分*/
    	// 有效订单列表
        $order_ids = array_column($this->field('order_id')->where($condition)->select(), 'order_id') ;

        // 获得总积分
    	$score_send_info = M('User_score_list')->field('sum(score) as score')->where(['order_id'=>['in',$order_ids],'type'=>1])->find();
    	$score_send = $score_send_info['score'];

    	// 退回积分
    	$score_send_info = M('User_score_list')->field('sum(score) as score')->where(['order_id'=>['in',$order_ids],'type'=>2])->find();
    	$score_back = $score_send_info['score'];
    	$returnArr['score_deducte'] = getFormatNumber($discount_money['score_deducte']);

    	$score_send_info = M('Shop_order_system_refund')->field('sum(score_back_count) as score')->where(['order_id'=>['in',$order_ids]])->find();
    	$score_back += $score_send_info['score'];

    	$score_send_info = M('Shop_order_refund')->field('sum(score_back_count) as score')->where(['order_id'=>['in',$order_ids,'status'=>1]])->find();
    	$score_back += $score_send_info['score'];

    	$returnArr['score_send'] = getFormatNumber($score_send - $score_back);

    	if (C('config.user_score_recharge_percent')) {
            $returnArr['scoreSendMoney'] = getFormatNumber($returnArr['score_send']/C('config.user_score_recharge_percent'));
        }else{
            $returnArr['scoreSendMoney'] = 0;
        }
    	/* 平台送出积分*/

    	// 平台送出佣金
    	$spread_info = D('User_spread_list')->field('sum(money) as money')->where(['order_id'=>['in',$order_ids],'status'=>1])->find();
    	$returnArr['send_commission'] = getFormatNumber($spread_info['money']);

    	// 平台补贴总额
    	$returnArr['plat_total_money'] = 
			getFormatNumber($discount_money['platform_plat']//平台优惠中平台补贴的总和
			+$discount_money['coupon_price'] //优惠券金额
			+$discount_money['score_deducte']//积分兑现的金额
			+$returnArr['vip_level_reduce_money']//会员等级优惠金额
			+$returnArr['scoreSendMoney'] //（平台发的积分-退回的积分）兑换的金额
			+$returnArr['send_commission']);//平台送出佣金

    	// 商家优惠券补贴金额
    	$returnArr['card_price'] = getFormatNumber($discount_money['card_price']);

    	// 商家满减补贴金额
    	$returnArr['merchant_reduce'] = getFormatNumber($discount_money['merchant_reduce']);

    	// 商家补贴总额
    	$returnArr['mer_total_money'] = getFormatNumber($discount_money['merchant_reduce']+$discount_money['card_price']);

    	// 获取平台配送费（计算配送员比例）
    	 // 支付时间
        $d_condition['o.pay_time'] = array('between',[$start_time,$end_time]);

        // 商家id
        if($m_condition['o.mer_id']){
	        // 商家id
	        $d_condition['o.mer_id'] = $m_condition['o.mer_id'];
        }
        // 已支付
        $d_condition['o.paid'] = 1;

    	$d_condition['o.order_status'] = array('gt',0);  // 已接单
        $d_condition['o.status'] = array('in','0,1,2,3,7,8,9,10');  // 未取消 未退款
        $d_condition['o.is_pick_in_store'] = 0;  // 未取消 未退款
        $freight_charge = M('Shop_order')->field('sum(o.freight_charge) as freight_charge,sum(deliver_user_fee) as deliver_user_fee,sum(platform_merchant) as platform_merchant')->join(' as o left join '.C('DB_PREFIX').'deliver_supply as m on o.order_id=m.order_id AND o.mer_id = m.mer_id AND o.real_orderid = m.real_orderid')->where($d_condition)->find();
//        $returnArr['plat_freight_charge'] = getFormatNumber($freight_charge['freight_charge']-$freight_charge['deliver_user_fee']);
        $returnArr['plat_freight_charge'] = getFormatNumber($freight_charge['freight_charge']-$freight_charge['deliver_user_fee']);

    	// 平台盈利
		$returnArr['plat_income'] = getFormatNumber($returnArr['plat_get_commission']-$returnArr['plat_total_money']+$returnArr['plat_freight_charge']);
		// 补贴总额
		$returnArr['discount_money'] = getFormatNumber($returnArr['mer_total_money']+$returnArr['plat_total_money']);

        /* 客单价：有效订单的平均单价。 */
        if ($returnArr['effective_count']) {
            $returnArr['unit_price'] = getFormatNumber($returnArr['mer_income']/$returnArr['effective_count']);
        }else{
            $returnArr['unit_price'] = '0.00';
        }

        /* 下单用户数 */
        unset($condition['status']);
        $user_count_today = $this->field('count(distinct(uid)) as user_count')->where($condition)->find();
        $returnArr['user_count'] = $user_count_today['user_count'];


        /* 无效订单：包括用户主动及平台、店员主动取消与申请售后全部商品退款的订单。 */
        unset($condition['order_status']);
        $condition['status'] = array('in','4,5'); // 未取消 未退款
        $returnArr['invalid_count'] = $this->where($condition)->count();

        /* 损失：包括用户主动及平台、店员主动取消与申请售后全部商品退款的退款金额。 */
        unset($condition['order_status']);
        $condition['status'] = array('in','4,5'); // 未取消 未退款
        $price_today = $this->field('sum(price) as price')->where($condition)->find();
        $returnArr['loss_money'] = getFormatNumber($price_today['price']);

        return $returnArr;
    }


    /**
     * 系统后台营业分析获取营销数据（优惠券、满减）
     */
    public function getRevenueStatisticsMarket($mer_id,$date,$param=array()){
    	if ($param['start_time']&&$param['end_time']) {
	    	// 开始时间
    		$start_time = $param['start_time'];
	        // 结束时间
    		$end_time = $param['end_time'];
    	}else{
	    	// 开始时间
	        $start_time = $date;
	        // 结束时间
	        $end_time = $start_time+86400-1;
    	}

        // 已支付
        $condition['paid'] = 1;
        $m_condition['o.paid'] = 1;

    	// 去掉商城订单
    	$condition['order_from'] = ['neq',1];
        $m_condition['o.order_from'] = ['neq',1];;

        // 支付时间
        $condition['pay_time'] = array('between',[$start_time,$end_time]);
        $m_condition['o.pay_time'] = array('between',[$start_time,$end_time]);
        if (!$mer_id) {
        	// 系统后台区域筛选
        	if ($param['area_id'] && $param['area_id'] != 'undefined') {
	            $where_mer['area_id'] = $param['area_id'];
	        } else if ($param['city_idss'] && $param['city_idss'] != 'undefined') {
	            $where_mer['city_id'] = $param['city_idss'];
	        } else if ($param['province_idss'] && $param['province_idss'] != 'undefined') {
	            $where_mer['province_id'] = $param['province_idss'];
	        }

	        if(C('customization.life_tools') && $param['system_session']['level'] != 2){// 城投生活通定制 商家绑定管理员
	        	$adminIdArr = D('Admin')->getAdminByFid($param['system_session']['id']);
	            $where_mer['admin_id'] = ['in', $adminIdArr];	        	
	        }

	        if ($where_mer) {
	            $mer_ids = M('Merchant')->where($where_mer)->select();

	            foreach ($mer_ids as $st) {
	                $mer_id_arr[] = $st['mer_id'];
	            }
	            if (!$mer_id_arr) {
	                return array();
	            }
		        $condition['mer_id'] = array('in',$mer_id_arr);
		        $m_condition['o.mer_id'] = array('in',$mer_id_arr);
	        }
        }else{
	        // 商家id
	        $condition['mer_id'] = $mer_id;
	        $m_condition['o.mer_id'] = $mer_id;
        }

        // 店铺id
        if ($param['store_id']) {
	        $condition['store_id'] = array('in',$param['store_id']);
	        $m_condition['o.store_id'] = array('in',$param['store_id']);
        }

        // 有效订单
        $condition['order_status'] = array('gt',0);  // 已接单
        $condition['status'] = array('in','0,1,2,3,7,8,9,10');  // 未取消 未退款
        $m_condition['o.order_status'] = array('gt',0);
        $m_condition['o.status'] = array('in','0,1,2,3,7,8,9,10');

        $returnArr['order_type'] = 'shop';
        /* 营业额 0点到现在(已支付订单) */
        $total_price_today = $this->field('sum(total_price) as total_price')->where($condition)->find();
        $returnArr['total_money'] = getFormatNumber($total_price_today['total_price']);

        /* 有效订单数 */
        $returnArr['effective_count'] = $this->where($condition)->count();


        /* 查询有效订单并返回统计数据 */
        $orderInfoArr = $this->getMarketOrderStatistics($condition);
       	$order_id_arr = $orderInfoArr['order_id_arr']; // 参与活动的订单id
       	$real_orderid_arr = $orderInfoArr['real_orderid_arr']; // 参与活动的订单id
       	$uid_arr = $orderInfoArr['uid_arr']; // 参与活动的用户id
       	unset($orderInfoArr['order_id_arr']);
       	unset($orderInfoArr['real_orderid_arr']);
       	unset($orderInfoArr['uid_arr']);

       	$returnArr = array_merge($returnArr,$orderInfoArr);

        /* 订单收入 已对账金额包括自由支付 扣除：1. 平台抽成   2. 平台配送费   3. 平台满减补贴   4. 商家满减补贴   5. 申请售后退款金额 */
        // 获得的金额
        $m_condition['m.type'] = 'shop';
        $m_condition['m.income'] = 1;
        $m_condition['m.order_id'] = ['in',$real_orderid_arr];
        $income_money = M('Merchant_money_list')->field('sum(money) as money, sum(system_take) as system_take')->join(' as m join '.C('DB_PREFIX').'shop_order as o on o.real_orderid=m.order_id')->where($m_condition)->find();

        // 支出的金额
        $m_condition['m.income'] = 2;
        $pay_money = M('Merchant_money_list')->field('sum(money) as money, sum(system_take) as system_take')->join(' as m join '.C('DB_PREFIX').'shop_order as o on o.real_orderid=m.order_id')->where($m_condition)->find();
        $returnArr['mer_income'] = getFormatNumber($income_money['money']) - getFormatNumber($pay_money['money']);
        // var_dump(D('merchant_money_list')->_sql());
        // 自有支付
        // 自有支付方式支付的金额
        $condition['is_own'] = ['gt',0];
        $condition['status'] = ['in',[2,3,4,5,7,8,9,10]]; // 2：已使用，3：已评论，:4：退款，5：取消
        $own_money = $this->field('sum(payment_money) as payment_money')->where($condition)->find();
        $own_money = getFormatNumber($own_money['payment_money']);

        // 自有支付方式退款的金额
        $m_condition['o.is_own'] = ['gt',0];
        $m_condition['o.status'] = ['in',[2,3,4,5,7,8,9,10]]; // 2：已使用，3：已评论，:4：退款，5：取消
        $refund_money1 = D('Shop_order_refund')->field('sum(payment_money) as payment_money')->join(' as m join '.C('DB_PREFIX').'shop_order as o on o.order_id=m.order_id')->where($m_condition)->find();
        $refund_money1 = getFormatNumber($refund_money1['payment_money']);

        $refund_money2 = D('Shop_order_system_refund')->field('sum(payment_money) as payment_money')->join(' as m join '.C('DB_PREFIX').'shop_order as o on o.order_id=m.order_id')->where($m_condition)->find();
        $refund_money2 = getFormatNumber($refund_money2['payment_money']);
        $returnArr['mer_income'] += getFormatNumber($own_money-$refund_money1-$refund_money2);

        /* 平台抽成 */
        $returnArr['plat_get_commission'] = getFormatNumber($income_money['system_take']-$pay_money['system_take']);

        /* 平台送出积分兑换金额 */
    	if (C('config.user_score_recharge_percent')) {
            $returnArr['scoreSendMoney'] = getFormatNumber($returnArr['send_score']/C('config.user_score_recharge_percent'));
        }else{
            $returnArr['scoreSendMoney'] = 0;
        }

    	// 平台补贴总额
    	$returnArr['plat_total_money'] = getFormatNumber($returnArr['platform_plat']+$returnArr['coupon_price']+$returnArr['score_deducte']+$returnArr['vip_level_reduce_money']+$returnArr['scoreSendMoney']+$returnArr['send_commission']+$returnArr['send_commission_mer']);

    	// 获取平台配送费（计算配送员比例）
        $d_condition['o.is_pick_in_store'] = 0;  // 平台配送
        $m_condition['o.order_id'] = ['in',$order_id_arr];  // 订单id
        $freight_charge = M('Shop_order')->field('sum(freight_charge) as freight_charge,sum(deliver_user_fee) as deliver_user_fee')->join(' as o left join '.C('DB_PREFIX').'deliver_supply as m on o.order_id=m.order_id')->where($d_condition)->find();
        $returnArr['plat_freight_charge'] = getFormatNumber($freight_charge['freight_charge']-$freight_charge['deliver_user_fee']);

    	//统计时间内所有有效订单中的所有活动订单平台佣金总额减去所有平台活动补贴总额加上平台配送费，可能会出现负数
		$returnArr['plat_income'] = getFormatNumber($returnArr['plat_get_commission']-$returnArr['plat_total_money']+$returnArr['plat_freight_charge']);

        /* 客单价：统计时间内所有有效订单中活动订单的平均单价，计算公式为：统计时间内所有有效订单中所有活动订单的商家净收入除以统计时间内所有有效订单中的所有活动订单总数 */
        if ($returnArr['market_count']) {
            $returnArr['unit_price'] = getFormatNumber($returnArr['mer_income']/$returnArr['market_count']);
        }else{
            $returnArr['unit_price'] = '0.00';
        }

       	/* 领券数量：统计时间内用户领取平台优惠券的总数；包含：1. 用户主动领券  2. 分享抢券  3. 系统派发券  4. 微信支付奖励券    5.签到领取     6. 随机领券   7. 客服发券*/
       	$coupon_condition['receive_time'] = array('between',[$start_time,$end_time]);
       	$returnArr['system_coupon_hadpull'] = M('System_coupon_hadpull')->where($coupon_condition)->count();

       	/* 核销量：统计时间内用户领取平台优惠券的总数；包含：1. 用户主动领券  2. 分享抢券  3. 系统派发券  4. 微信支付奖励券    5.签到领取     6. 随机领券   7. 客服发券*/
       	$coupon_condition['is_user'] = 1;
       	$returnArr['system_coupon_hadpull_used'] = M('System_coupon_hadpull')->where($coupon_condition)->count();

		/*核销率：统计时间内带动订单数除以领取数量*/
        $returnArr['coupon_use_rate'] = $returnArr['system_coupon_hadpull'] ? getFormatNumber($returnArr['system_coupon_hadpull_used']/$returnArr['system_coupon_hadpull']*100) : 0;

        /* 投入产出比 */
        $returnArr['input_output_rate'] = $returnArr['plat_total_money'] ? getFormatNumber($returnArr['plat_income']/$returnArr['plat_total_money']*100) : 0;

        /* 占总营业额百分比，计算方式为：活动总营业额除以总营业额 */
        $returnArr['market_total_money_rate'] = $returnArr['total_money'] ? getFormatNumber($returnArr['market_total_money']/$returnArr['total_money']*100) : 0;

        /* 占总订单百分比，计算方式为：活动总订单数除以总订单数 */
        $returnArr['market_count_rate'] = $returnArr['effective_count'] ? getFormatNumber($returnArr['market_count']/$returnArr['effective_count']*100) : 0;
        return $returnArr;
    }


    /**
     * 系统后台营业分析获取营销数据,获取活动订单统计数据
     */
    public function getMarketOrderStatistics($condition){
        /* 查询有效订单 */
       	$order_list = $this->field('order_id,real_orderid,total_price,price,coupon_id,coupon_price,score_used_count,score_deducte,balance_reduce,balance_reduce_total,platform_plat,vip_level_reduce_money,uid')->where($condition)->select();

       	/*统计时间内所有有效订单中的所有活动订单总额；活动类型包含：1. 平台满减优惠活动   2. 平台优惠券活动    3. 平台积分抵扣活动  4. 平台会员等级优惠活动    5. 平台送出积分活动     6. 平台送出三级推广佣金活动   7. 平台送出商家推广佣金活动；
       	注意：1. 旁边也增加一个占总营业额百分比，计算方式为：活动总营业额除以总营业额，样式见下图。2. 旁边加个向下的箭头，点击后展开各个活动类型的统计明细。*/
       	$returnArr['market_total_money'] = 0; // 参与活动的营业额
       	// 明细
       	$returnArr['platform_plat_order_money'] = 0;  // 平台满减补贴的订单营业总额
       	$returnArr['coupon_price_order_money'] = 0;  // 平台优惠券补贴的订单营业总额
       	$returnArr['vip_level_reduce_money_order_money'] = 0;  // 平台会员等级优惠补贴的订单营业总额
       	$returnArr['send_score_order_money'] = 0;  // 平台送出积分的订单营业总额
       	$returnArr['send_commission_order_money'] = 0;  // 平台送出平台佣金的订单营业总额
       	$returnArr['score_deducte_order_money'] = 0;  // 平台积分抵扣补贴的订单营业总额
       	$returnArr['send_commission_mer_order_money'] = 0;  // 平台送出商家推广佣金的订单营业总额

       	/*带动订单：统计时间内所有有效订单中的所有活动订单总数；注意：1. 旁边也增加一个占总订单百分比，计算方式为：活动总订单数除以总订单数。 2. 旁边加个向下的箭头，点击后展开各个活动类型的统计明细。*/
       	$returnArr['market_count'] = 0;  // 参与活动的订单数
       	// 明细
       	$returnArr['platform_plat_count'] = 0;  // 平台满减补贴的订单总数
       	$returnArr['coupon_price_count'] = 0;  // 平台优惠券补贴的订单总数
       	$returnArr['vip_level_reduce_money_count'] = 0;  // 平台会员等级优惠补贴的订单总数
       	$returnArr['send_score_count'] = 0;  // 平台送出积分的订单总数
       	$returnArr['send_commission_count'] = 0;  // 平台送出平台佣金的订单总数
       	$returnArr['score_deducte_count'] = 0;  // 平台积分抵扣补贴的订单总数
       	$returnArr['send_commission_mer_count'] = 0;  // 平台送出商家推广佣金的订单总数

       	// 各种优惠总额
       	$returnArr['platform_plat'] = 0;  // 平台满减补贴的总额
       	$returnArr['coupon_price'] = 0;  // 平台优惠券补贴的总额
       	$returnArr['vip_level_reduce_money'] = 0;  // 平台会员等级优惠补贴的总额
       	$returnArr['send_score'] = 0;  // 平台送出积分的总额
       	$returnArr['send_commission'] = 0;  // 平台送出平台佣金的总额
       	$returnArr['score_deducte'] = 0;  // 平台积分抵扣补贴的总额
       	$returnArr['send_commission_mer'] = 0;  // 平台送出商家推广佣金的总额

       	// 新老客户
       	$returnArr['new_customer'] = 0;  // 新客户数量
       	$returnArr['regular_customer'] = 0;  // 老客户数量

       	$order_id_arr = []; // 参与活动的订单id
       	$real_orderid_arr = []; // 参与活动的订单id
       	$uid_arr = []; // 参与活动的用户id
       	foreach ($order_list as $key => $value) {
       		// 平台满减补贴的总额
	       	$returnArr['platform_plat'] = getFormatNumber($returnArr['platform_plat']+$value['platform_plat']);

	       	// 平台优惠券补贴的订单数+1
	       	if ($value['platform_plat']>0) {
	       		$returnArr['platform_plat_count']++;
	       		$returnArr['platform_plat_order_money'] += $value['total_price'];
	       	}

	       	// 平台优惠券补贴的总额
	       	$returnArr['coupon_price'] = getFormatNumber($returnArr['coupon_price']+$value['coupon_price']);

	       	// 平台优惠券补贴的订单数+1
	       	if ($value['coupon_price']>0) {
	       		$returnArr['coupon_price_count']++;
	       		$returnArr['coupon_price_order_money'] += $value['total_price'];
	       	}

	       	// 平台会员等级优惠补贴的总额
	       	$returnArr['vip_level_reduce_money'] = getFormatNumber($returnArr['vip_level_reduce_money']+$value['vip_level_reduce_money']);

	       	// 平台会员等级优惠补贴的订单数+1
	       	if ($value['vip_level_reduce_money']>0) {
	       		$returnArr['vip_level_reduce_money_count']++;
	       		$returnArr['vip_level_reduce_money_order_money'] += $value['total_price'];
	       	}

	       	// 计算退款的积分抵扣（积分分抵扣需减去退款的）
	       	$score_deducte = $value['score_deducte'];
	       	$refundMoney = $this->getOrderRefundMoney($value['order_id']);
	       	$score_deducte -= $refundMoney['score_deducte'];
	       	// 平台积分抵扣补贴的总额
	       	$returnArr['score_deducte'] = getFormatNumber($returnArr['score_deducte']+$score_deducte);

	       	// 平台积分抵扣补贴的订单数+1
	       	if ($score_deducte>0) {
	       		$returnArr['score_deducte_count']++;
	       		$returnArr['send_score_order_money'] += $value['total_price'];
	       	}

	       	// 是否获得佣金
	       	$user_spread_count = M('User_spread_list')->where(['order_id'=>$value['order_id'],'status'=>1,'order_type'=>'shop'])->count();
	       	if ($user_spread_count>0) {
	       		// 平台送出平台佣金的总额
	       		$user_spread_money = M('User_spread_list')->field('sum(money) as money')->where(['order_id'=>$value['order_id'],'status'=>1])->find();
	       		$returnArr['send_commission'] = getFormatNumber($returnArr['send_commission']+$user_spread_money['money']);

	       		// 平台送出平台佣金的订单数+1
	       		$returnArr['send_commission_count']++;
	       		$returnArr['send_commission_order_money'] += $value['total_price'];
	       	}

	       	// 是否获得积分
	       	$user_score_count = M('User_score_list')->where(['order_id'=>$value['order_id'],'order_type'=>'shop'])->count();
	       	if ($user_score_count>0) {
	       		// 平台送出积分的总额
	       		$user_score_money = M('User_score_list')->field('sum(score) as score')->where(['order_id'=>$value['order_id'],'order_type'=>'shop'])->find();
	       		$returnArr['send_score'] = getFormatNumber($returnArr['send_score']+$user_score_money['score']);

	       		// 平台送出积分的订单数+1
	       		$returnArr['send_score_count']++;
	       		$returnArr['score_deducte_order_money'] += $value['total_price'];
	       	}

	       	// 是否获得商家推广佣金
	       	$merchant_spread_count = M('Merchant_spread_list')->where(['order_id'=>$value['order_id'],'order_type'=>'shop','money'=>['gt',0]])->count();
	       	if ($merchant_spread_count>0) {
	       		// 平台送出商家推广佣金的总额
	       		$merchant_spread_money = M('Merchant_spread_list')->field('sum(money) as money')->where(['order_id'=>$value['order_id'],'order_type'=>'shop'])->find();
	       		$returnArr['send_commission_mer'] = getFormatNumber($returnArr['send_commission_mer']+$merchant_spread_money['money']);

	       		// 平台送出商家推广佣金的订单数+1
	       		$returnArr['send_commission_mer_count']++;
	       		$returnArr['send_commission_mer_order_money'] += $value['total_price'];
	       	}

	       	// 活动订单数+1
       		if ($value['platform_plat']>0 || $value['coupon_price']>0 || $value['vip_level_reduce_money']>0 || $score_deducte>0 || $user_spread_count || $user_score_count) {
       			$returnArr['market_count'] ++;
       			$returnArr['market_total_money'] = getFormatNumber($returnArr['market_total_money']+$value['total_price']);
       			$order_id_arr[] = $value['order_id'];
       			$real_orderid_arr[] = $value['real_orderid'];

       			$user_order_count = $this->where(['uid'=>$value['uid'],'pay_time'=>['lt',$condition['pay_time'][1][1]],'status' => array('not in', '4,5'),'paid'=>1])->count();
       			if ($user_order_count>1) {
       				/*活动老客数：统计时间内所有有效订单中活动订单的老用户数*/
       				if (!in_array($value['uid'], $uid_arr)) {
       					$uid_arr[] = $value['uid'];
			       		$returnArr['regular_customer']++;  // 老客户数量
       				}
       			}else{
					/*活动新客数：统计时间内所有有效订单中活动订单的新用户数*/
			       	$returnArr['new_customer']++;  // 新客户数量
       			}

       		}
       	}
       	$returnArr['order_id_arr'] = $order_id_arr;
       	$returnArr['real_orderid_arr'] = $real_orderid_arr;
       	$returnArr['uid_arr'] = $uid_arr;
        return $returnArr;
    }

      /**
     * 商家后台营业分析获取营销数据（优惠券、满减）
     */
    public function getRevenueStatisticsMarketMerchant($mer_id,$date,$param=array()){
    	if ($param['start_time']&&$param['end_time']) {
	    	// 开始时间
    		$start_time = $param['start_time'];
	        // 结束时间
    		$end_time = $param['end_time'];
    	}else{
	    	// 开始时间
	        $start_time = $date;
	        // 结束时间
	        $end_time = $start_time+86400-1;
    	}

        // 已支付
        $condition['paid'] = 1;
        $m_condition['o.paid'] = 1;

    	// 去掉商城订单
    	$condition['order_from'] = ['neq',1];
        $m_condition['o.order_from'] = ['neq',1];;

        // 支付时间
        $condition['pay_time'] = array('between',[$start_time,$end_time]);
        $m_condition['o.pay_time'] = array('between',[$start_time,$end_time]);

        // 商家id
        $condition['mer_id'] = $mer_id;
        $m_condition['o.mer_id'] = $mer_id;

        // 店铺id
        if ($param['store_list']&&$param['store_list']!=-1) {
	        $condition['store_id'] = array('in',$param['store_list']);
	        $m_condition['o.store_id'] = array('in',$param['store_list']);
        }

        // 有效订单
        $condition['order_status'] = array('gt',0);  // 已接单
        $condition['status'] = array('in','0,1,2,3');  // 未取消 未退款
        $m_condition['o.order_status'] = array('gt',0);
        $m_condition['o.status'] = array('in','0,1,2,3');

        $returnArr['order_type'] = 'shop';
        /* 营业额 0点到现在(已支付订单) */
        $total_price_today = $this->field('sum(total_price) as total_price')->where($condition)->find();
        $returnArr['total_money'] = getFormatNumber($total_price_today['total_price']);

        /* 有效订单数 */
        $returnArr['effective_count'] = $this->where($condition)->count();


        /* 查询有效订单并返回统计数据 */
        $orderInfoArr = $this->getMarketOrderStatisticsMerchant($condition);
       	$order_id_arr = $orderInfoArr['order_id_arr']; // 参与活动的订单id
       	$real_orderid_arr = $orderInfoArr['real_orderid_arr']; // 参与活动的订单id
       	$uid_arr = $orderInfoArr['uid_arr']; // 参与活动的用户id
       	unset($orderInfoArr['order_id_arr']);
       	unset($orderInfoArr['real_orderid_arr']);
       	unset($orderInfoArr['uid_arr']);
       	$returnArr = array_merge($returnArr,$orderInfoArr);

        /* 订单收入 已对账金额包括自由支付 扣除：1. 平台抽成   2. 平台配送费   3. 平台满减补贴   4. 商家满减补贴   5. 申请售后退款金额 */
        // 获得的金额
        $m_condition['m.type'] = 'shop';
        $m_condition['m.income'] = 1;
        $m_condition['m.order_id'] = ['in',$real_orderid_arr];
        $income_money = M('Merchant_money_list')->field('sum(money) as money, sum(system_take) as system_take')->join(' as m join '.C('DB_PREFIX').'shop_order as o on o.real_orderid=m.order_id')->where($m_condition)->find();

        // 支出的金额
        $m_condition['m.income'] = 2;
        $pay_money = M('Merchant_money_list')->field('sum(money) as money, sum(system_take) as system_take')->join(' as m join '.C('DB_PREFIX').'shop_order as o on o.real_orderid=m.order_id')->where($m_condition)->find();
        $returnArr['mer_income'] = getFormatNumber($income_money['money']) - getFormatNumber($pay_money['money']);
        // var_dump(D('merchant_money_list')->_sql());
        // 自有支付
        // 自有支付方式支付的金额
        $condition['is_own'] = ['gt',0];
        $condition['status'] = ['in',[2,3,4,5]]; // 2：已使用，3：已评论，:4：退款，5：取消
        $own_money = $this->field('sum(payment_money) as payment_money')->where($condition)->find();
        $own_money = getFormatNumber($own_money['payment_money']);

        // 自有支付方式退款的金额
        $m_condition['o.is_own'] = ['gt',0];
        $m_condition['o.status'] = ['in',[2,3,4,5]]; // 2：已使用，3：已评论，:4：退款，5：取消
        $refund_money1 = D('Shop_order_refund')->field('sum(payment_money) as payment_money')->join(' as m join '.C('DB_PREFIX').'shop_order as o on o.order_id=m.order_id')->where($m_condition)->find();
        $refund_money1 = getFormatNumber($refund_money1['payment_money']);

        $refund_money2 = D('Shop_order_system_refund')->field('sum(payment_money) as payment_money')->join(' as m join '.C('DB_PREFIX').'shop_order as o on o.order_id=m.order_id')->where($m_condition)->find();
        $refund_money2 = getFormatNumber($refund_money2['payment_money']);
        $returnArr['mer_income'] += getFormatNumber($own_money-$refund_money1-$refund_money2);

        /* 客单价：统计时间内所有有效订单中活动订单的平均单价，计算公式为：统计时间内所有有效订单中所有活动订单的商家净收入除以统计时间内所有有效订单中的所有活动订单总数 */
        if ($returnArr['market_count']) {
            $returnArr['unit_price'] = getFormatNumber($returnArr['mer_income']/$returnArr['market_count']);
        }else{
            $returnArr['unit_price'] = '0.00';
        }

       	/* 领券数量：统计时间内用户领取平台优惠券的总数；包含：1. 用户主动领券  2. 分享抢券  3. 系统派发券  4. 微信支付奖励券    5.签到领取     6. 随机领券   7. 客服发券*/
       	$coupon_condition['receive_time'] = array('between',[$start_time,$end_time]);
       	$returnArr['card_new_coupon_hadpull'] = M('Card_new_coupon_hadpull')->where($coupon_condition)->count();

       	/* 核销量：统计时间内用户领取平台优惠券的总数；包含：1. 用户主动领券  2. 分享抢券  3. 系统派发券  4. 微信支付奖励券    5.签到领取     6. 随机领券   7. 客服发券*/
       	$coupon_condition['is_user'] = 1;
       	$returnArr['card_new_coupon_hadpull_used'] = M('Card_new_coupon_hadpull')->where($coupon_condition)->count();

		/*核销率：统计时间内带动订单数除以领取数量*/
        $returnArr['coupon_use_rate'] = $returnArr['card_new_coupon_hadpull'] ? getFormatNumber($returnArr['card_new_coupon_hadpull_used']/$returnArr['card_new_coupon_hadpull']*100) : 0;

        /* 投入产出比 */
        $returnArr['input_output_rate'] = $returnArr['mer_total_money'] ? getFormatNumber($returnArr['mer_income']/$returnArr['mer_total_money']*100) : 0;

        /* 占总营业额百分比，计算方式为：活动总营业额除以总营业额 */
        $returnArr['market_total_money_rate'] = $returnArr['total_money'] ? getFormatNumber($returnArr['market_total_money']/$returnArr['total_money']*100) : 0;

        /* 占总订单百分比，计算方式为：活动总订单数除以总订单数 */
        $returnArr['market_count_rate'] = $returnArr['effective_count'] ? getFormatNumber($returnArr['market_count']/$returnArr['effective_count']*100) : 0;
        return $returnArr;
    }

    /**
     * 商家后台营业分析获取营销数据,获取活动订单统计数据
     */
    public function getMarketOrderStatisticsMerchant($condition){
        /* 查询有效订单 */
        $condition['_string'] = '(merchant_reduce>0 OR card_price>0)';
       	$order_list = $this->field('order_id,real_orderid,total_price,price,merchant_reduce,card_price,uid')->where($condition)->select();

       	/*活动总营业额：统计时间内所有有效订单中的所有活动订单总额；活动类型包含：1. 商家满减优惠活动   2. 商家优惠券活动。 注意：1. 旁边也增加一个占总营业额百分比，计算方式为：活动总营业额除以总营业额*/
       	$returnArr['market_total_money'] = 0; // 参与活动的营业额
       	// 明细
       	$returnArr['merchant_reduce_order_money'] = 0;  // 商家满减补贴的订单营业总额
       	$returnArr['card_price_order_money'] = 0;  // 商家优惠券补贴的订单营业总额

       	/*带动订单：统计时间内所有有效订单中的所有活动订单总数；注意：1. 旁边也增加一个占总订单百分比，计算方式为：活动总订单数除以总订单数。 2. 旁边加个向下的箭头，点击后展开各个活动类型的统计明细。*/
       	$returnArr['market_count'] = 0;  // 参与活动的订单数
       	// 明细
       	$returnArr['merchant_reduce_count'] = 0;  // 商家满减补贴的订单总数
       	$returnArr['card_price_count'] = 0;  // 商家优惠券补贴的订单总

       	// 各种优惠总额
       	$returnArr['mer_total_money'] = 0;  // 商家补贴的总额
       	$returnArr['merchant_reduce'] = 0;  // 商家满减补贴的总额
       	$returnArr['card_price'] = 0;  // 商家优惠券补贴的总额

       	// 新老客户
       	$returnArr['new_customer'] = 0;  // 新客户数量
       	$returnArr['regular_customer'] = 0;  // 老客户数量


       	$order_id_arr = []; // 参与活动的订单id
       	$real_orderid_arr = []; // 参与活动的订单id
       	$uid_arr = []; // 参与活动的用户id
       	foreach ($order_list as $key => $value) {
       		// 商家满减补贴的总额
	       	$returnArr['merchant_reduce'] = getFormatNumber($returnArr['merchant_reduce']+$value['merchant_reduce']);

	       	// 商家优惠券补贴的订单数+1
	       	if ($value['merchant_reduce']>0) {
	       		$returnArr['merchant_reduce_count']++;
       			$returnArr['merchant_reduce_order_money'] += $value['total_price'];  // 商家满减补贴的订单营业总额
	       	}

	       	// 商家优惠券补贴的总额
	       	$returnArr['card_price'] = getFormatNumber($returnArr['card_price']+$value['card_price']);

	       	// 商家优惠券补贴的订单数+1
	       	if ($value['coupon_price']>0) {
	       		$returnArr['coupon_price_count']++;
       			$returnArr['card_price_order_money'] += $value['total_price'];  // 商家优惠券补贴的订单营业总额
	       	}

	       	// 活动订单数+1
   			$returnArr['market_count'] ++;

	       	// 活动总营业额
   			$returnArr['market_total_money'] = getFormatNumber($returnArr['market_total_money']+$value['total_price']);

   			$order_id_arr[] = $value['order_id'];
   			$real_orderid_arr[] = $value['real_orderid'];

   			$user_order_count = $this->where(['uid'=>$value['uid'],'pay_time'=>['lt',$condition['pay_time'][1][1]],'status' => array('not in', '4,5'),'paid'=>1])->count();
   			if ($user_order_count>1) {
   				/*活动老客数：统计时间内所有有效订单中活动订单的老用户数*/
   				if (!in_array($value['uid'], $uid_arr)) {
   					$uid_arr[] = $value['uid'];
		       		$returnArr['regular_customer']++;  // 老客户数量
   				}
   			}else{
				/*活动新客数：统计时间内所有有效订单中活动订单的新用户数*/
		       	$returnArr['new_customer']++;  // 新客户数量
   			}


       	}
       	$returnArr['mer_total_money'] = getFormatNumber($returnArr['card_price']+$returnArr['merchant_reduce']);
       	$returnArr['order_id_arr'] = $order_id_arr;
       	$returnArr['real_orderid_arr'] = $real_orderid_arr;
       	$returnArr['uid_arr'] = $uid_arr;
        return $returnArr;
    }


    /**
     * 满减神器，根据用户历史支付成功订单获取组合
     */
    public function getFullReduction($uid, $storeId, $fullMoney = 0, $reduceMoney = 0, $goodsNum = 3, $returnCount = 20, $days = 14)
    {
        if ($storeId < 1) {
            return [];
        }

        $orderWhere = [
            'store_id' => $storeId,
            'paid' => 1,
            'create_time' => ['gt', strtotime("-$days day")]
        ];
        $uid > 0 && $orderWhere['uid'] = $uid;
        $orders = $this->where($orderWhere)->select();
        if (empty($orders)) {
            return [];
        }

        $shopDetailMod = D('Shop_order_detail');
        $goodsMod = D('Shop_goods');
        $fullReduce = [];
        $storeInfo = M('Merchant_store_shop')->where(['store_id'=>$storeId])->field('pack_type,pack_fee')->find();
        foreach ($orders as $o) {
            if (count($fullReduce) >= $returnCount) {
                break;
            }
            $detail = $shopDetailMod->alias('d')
                ->join(C('DB_PREFIX') . 'shop_goods AS g ON d.goods_id = g.goods_id')
                ->where(['d.order_id' => $o['order_id']])
                ->order('g.sell_count DESC')
                ->field('d.*,g.is_subsidiary_goods')
                ->select();
            $combineGoods = [];
            $totalPrice = 0;
            $packingCharge = 0;
            foreach ($detail as $g) {
                if (count($combineGoods) >= $goodsNum) {
                    break;
                }
                if ($g['is_subsidiary_goods']) {
                    continue;
                }
                if ($goodsMod->isShow($g['goods_id'])) {
                    $info = $goodsMod->get_goods_by_id($g['goods_id']);
                    if (!$info) {
                        continue;
                    }
                    //有规格选取第一个
                    if ($info['spec_list']) {
                        $specFirst = [];
                        foreach ($info['spec_list'] as $s) {
                            $first = array_shift($s['list']);
                            $specFirst[] = $first['id'];
                        }
                        $specIndex = implode('_', $specFirst);
                        $specInfo = isset($info['list'][$specIndex]) ? $info['list'][$specIndex] : [];
                        if (!$specInfo) {
                            continue;
                        }
                        $good = [
                            'goods_id' => $info['goods_id'],
                            'name' => $info['name'],
                            'image' => $info['image'],
                            'price' => $info['is_seckill_price'] ? getFormatNumber($specInfo['seckill_price']) : getFormatNumber($specInfo['price'])
                        ];
                    } else {
                        $good = [
                            'goods_id' => $info['goods_id'],
                            'name' => $info['name'],
                            'image' => $info['image'],
                            'price' => getFormatNumber($info['price']),
                        ];
                    }
                    $totalPrice += $good['price'];
                    $packingCharge += $info['packing_charge'];
                    $combineGoods[] = $good;
                    if($totalPrice > $fullMoney){
                        break;
                    }
                }
            }
            if ($storeInfo['pack_type'] == 2) {
                $packingCharge = $storeInfo['pack_fee'];
            }
            $totalPrice += $packingCharge;
            if ($totalPrice >= $fullMoney) {
                $combineGoodsId = array_column($combineGoods,'goods_id');
                sort($combineGoods);
                $combineIndex = implode('_',$combineGoodsId);
                if(isset($fullReduce[$combineIndex])){
                    continue;
                }
                $fullReduce[$combineIndex] = [
                    'goods_list' => $combineGoods,
                    'total_price' => getFormatNumber($totalPrice),
                    'real_pay_price' => getFormatNumber($totalPrice - $reduceMoney),
                    'packing_fee' => getFormatNumber($packingCharge),
                    'full_reduce_goods_id' => $combineIndex
                ];
            } else {
                continue;
            }
        }
        return $fullReduce;
    }

    /**
     * 是否平台满减订单
     * 平台满额打折（6） 平台满减（2） 平台首单（1） 平台配送费满减（5）
     */
    public function is_system_full_discount_order($discount_list = [])
    {
        $is_system_full_discount_order = false;
        foreach ($discount_list as $v) {
            if (in_array($v['discount_type'], [1, 2, 5, 6])) {
                $is_system_full_discount_order = true;
                break;
            }
        }
        return $is_system_full_discount_order;
    }

    // 导出订单列表
    public function shopExportCsv($param)
    {
        $title = C('config.shop_alias_name') . L_('账单');
        $where_store = null;
        if (!empty($param['keyword']) && $param['searchtype'] == 's_name') {
            $where_store['name'] = array('like', '%' . $param['keyword'] . '%');
        }

        if ($param['system_session']['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $param['system_session']['area_id']))->find();
            if ($now_area['area_type'] == 3) {
                $area_index = 'area_id';
            } elseif ($now_area['area_type'] == 2) {
                $area_index = 'city_id';
            } elseif ($now_area['area_type'] == 1) {
                $area_index = 'province_id';
            }
            $where_store[$area_index] = $param['system_session']['area_id'];
        }

        if ($param['area_id'] && $param['area_id'] != 'undefined') {
            $where_store['area_id'] = $param['area_id'];
        } else if ($param['city_idss'] && $param['city_idss'] != 'undefined') {
            $where_store['city_id'] = $param['city_idss'];
        } else if ($param['province_idss'] && $param['province_idss'] != 'undefined') {
            $where_store['province_id'] = $param['province_idss'];
        }

        $store_ids = array();
        $where = array('platform' => 0);
        $condition_where = 'WHERE `o`.`platform`=0';
        $condition_where .= ' AND o.is_del=0';
        if ($where_store) {
            $stores = D('Merchant_store')->field('store_id')->where($where_store)->select();
            foreach ($stores as $row) {
                $store_ids[] = $row['store_id'];
            }
            if ($store_ids) {
                $where['store_id'] = array('in', $store_ids);
                $condition_where .= ' AND o.store_id IN (' . implode(',', $store_ids) . ')';
            }
        }
        //区分线下零售
        $order_from = $param['order_from'];
        if ($order_from == 1) {
            $where['order_from'] = array('neq', 6);
            $condition_where .= ' AND o.order_from!=6';
        } elseif ($order_from == 6) {
            $where['order_from'] = 6;
            $condition_where .= ' AND o.order_from=6';
        }


        if (!empty($param['keyword'])) {
            if ($param['searchtype'] == 'real_orderid') {
                $where['real_orderid'] = htmlspecialchars($param['keyword']);
                $condition_where .= ' AND o.real_orderid = "' . htmlspecialchars($param['keyword']) . '"';
            } elseif ($param['searchtype'] == 'orderid') {
                $where['orderid'] = htmlspecialchars($param['keyword']);
                $tmp_result = M('Tmp_orderid')->where(array('orderid' => $where['orderid']))->find();
                unset($where['orderid']);
                $where['order_id'] = $tmp_result['order_id'];
                $condition_where .= ' AND o.order_id = ' . $tmp_result['order_id'];
            } elseif ($param['searchtype'] == 'name') {
                $where['username'] = htmlspecialchars($param['keyword']);
                $condition_where .= ' AND o.username = "' . htmlspecialchars($param['keyword']) . '"';
            } elseif ($param['searchtype'] == 'phone') {
                $where['userphone'] = htmlspecialchars($param['keyword']);
                $condition_where .= ' AND o.userphone = "' . htmlspecialchars($param['keyword']) . '"';
            } elseif ($param['searchtype'] == 'third_id') {
                $where['third_id'] = $param['keyword'];
                $condition_where .= ' AND o.third_id = "' . $param['keyword'] . '"';
            }

        }
        if ($param['merchant_session']) {
            $where['mer_id'] = $param['merchant_session']['mer_id'];
            $condition_where .= ' AND o.mer_id = ' . $param['store_session']['mer_id'];
        }
        if ($param['store_session']) {
            $where['store_id'] = $param['store_session']['store_id'];
            $condition_where .= ' AND o.store_id = ' . $param['store_session']['store_id'];
        }


        $status = isset($param['status']) ? intval($param['status']) : -1;

        $type = isset($param['type']) && $param['type'] ? $param['type'] : '';
        $sort = isset($param['sort']) && $param['sort'] ? $param['sort'] : '';
        $from_type = isset($param['from_type']) && $param['from_type'] ? $param['from_type'] : '';
        $shop_type = isset($param['shop_type']) && $param['shop_type'] ? $param['shop_type'] : '';
        $pay_type = isset($param['pay_type']) && $param['pay_type'] ? $param['pay_type'] : '';
        if ($sort != 'DESC' && $sort != 'ASC') $sort = '';
        if ($type != 'price' && $type != 'pay_time') $type = '';

        if ($status == 11) {
            $where['status'] = array('in',array(2,3));
            $where['is_apply_refund'] = 1;
            $condition_where .= ' AND o.status in(2,3) AND o.is_apply_refund=1';
        } else if($status == 100) {
            $where['paid'] = 0;
            $condition_where .= " AND `o`.`paid`=0";
        } else if($status == 12){
			$where['paid'] = 1;
			$where['status'] = array('in',array(0,1,9));
			$condition_where .= " AND `o`.`status` IN (0,1,9) AND paid=1";
		} else if($status == 6){
			$where['paid'] = 0;
			$condition_where .= " AND paid=0";
		} else if($status == 2){
            $where['status'] = array('in', array(2, 3));
            $condition_where .= " AND `o`.`status` IN (2, 3)";
		} else if($status === 0){
            $where['paid'] = 1;
			$where['order_status'] = 0;
            if($from_type==2||$shop_type==2) {//商城订单
                $where['status'] = array('in', array(0, 9));
                $condition_where .= ' AND o.paid = 1 AND o.order_status=0 AND `o`.`status` IN (0, 9) ';
            }else{
				$where['status'] = array('in', array(0, 7));
                $condition_where .= ' AND o.paid = 1 AND o.order_status=0 AND `o`.`status` IN (0, 7) ';
            }
		}else if ($status != - 1) {
            $where['status'] = $status;
            $condition_where .= " AND `o`.`status`=" . $status;
        }

        if ($pay_type && $pay_type != 'balance') {
			if($pay_type == 'employee_balance_pay'){
				$where['employee_balance_pay'] = ['gt', 0];
				$condition_where .= ' AND o.employee_balance_pay > 0';
			}else if($pay_type == 'employee_score_pay'){
				$where['employee_score_pay'] = ['gt', 0];
				$condition_where .= ' AND o.employee_score_pay > 0';
			}elseif($pay_type == 'weixinapp'){
				$where['pay_type'] = 'weixin';
				$where['is_mobile_pay'] = 2;
				$condition_where .= ' AND o.pay_type = "weixin" AND o.is_mobile_pay = 2';
			}elseif($pay_type == 'alipayh5'){
				$where['pay_type'] = ['IN',['alipayh5', 'alipay']];
				$condition_where .= ' AND o.pay_type IN ("alipayh5","alipay")';
			}else{
				$where['pay_type'] = $pay_type;
				$condition_where .= ' AND o.pay_type="' . $pay_type . '"';
			}
        } else if ($pay_type == 'balance') {
            $where['_string'] = "(`balance_pay`<>0 OR `merchant_balance` <> 0 OR pay_type='')";
            $condition_where .= ' AND (`o`.`balance_pay`<>0 OR `o`.`merchant_balance` <> 0 OR o.pay_type="")';
        }

        if (!empty($param['begin_time']) && !empty($param['end_time'])) {
            if ($param['begin_time'] > $param['end_time']) {
                return false;
            }
            $searchTimeType = $param['searchTimeType'] ? $param['searchTimeType'] : 'create_time';
            $period = array(strtotime($param['begin_time'] . " 00:00:00"), strtotime($param['end_time'] . " 23:59:59"));
            $where['_string'] .= ($where['_string'] ? ' AND ' : '') . " (o.".$searchTimeType." BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            $condition_where .= " AND (o.".$searchTimeType." BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
        }
        //判断是否属于商城订单
        if($from_type==2||$shop_type==2){//商城订单
            $where['order_from']=1;
            $condition_where.=' and o.order_from=1';
        }else{
            $where['order_from']=array('neq',1);
            $condition_where.=' and o.order_from!=1';
        }
		//筛选员工部门/身份
		if(!empty($param['identity'])){
			$condition_where .= " AND u.identity LIKE '%{$param['identity']}%'";
		}
		if(!empty($param['department'])){
			$condition_where .= " AND u.department LIKE '%{$param['department']}%'";
		}

		if(!empty($param['pick_id'])){
			$condition_where .= " AND o.pick_id = " . $param['pick_id'];
		}

        $sql_count = "SELECT count(*) as count FROM " . C('DB_PREFIX') . "shop_order AS o INNER JOIN " . C('DB_PREFIX') . "merchant_store AS s ON s.store_id=o.store_id INNER JOIN " . C('DB_PREFIX') . "merchant AS m ON `s`.`mer_id`=`m`.`mer_id` INNER JOIN " . C('DB_PREFIX') . "shop_order_detail AS d ON `d`.`order_id`=`o`.`order_id` " . $condition_where;
        $count = M()->query($sql_count);
        $count = $count[0]['count'];

        $csv_head = array(
            L_('订单编号'),
            L_('订单流水号'),
            L_('商品名称'),
            L_('商品进价'),
            L_('单价'),
            L_('单位'),
            L_('规格/属性'),
            L_('数量'),
            L_('商家名称'),
            L_('店铺名称'),
            L_('客户姓名'),
            L_('客户电话'),
            L_('会员分组'),
            L_('会员姓名'),
            L_('客户地址'),
            /*L_('客户身份'),
            L_('客户部门'),
			L_('员工名称'),//16
            L_('员工卡号'),
            L_('员工标签'),*/
            L_('注册姓名'),
            L_('注册电话'),
            L_('订单总价'),
            L_('平台优惠'),
            L_('商家优惠'),
            L_('应付总价'),
            L_('在线支付金额'),
            L_('商家余额'),
            L_('平台余额'),
            L_('平台会员卡价格'),
            L_('积分抵扣金额'),
            L_('原平台配送费'),
            L_('惠后平台配送费'),
            L_('商家配送费'),
            L_('支付时间'),
            L_('送达时间'),
            L_('订单状态'),
            L_('支付情况'),
            L_('第三方流水号'),
            L_('区域商圈'),
            L_('平台抽佣金额'),
            L_('骑士应交账金额（货到付款订单）'),
            L_('平台满减金额（平台出补贴）'),
            L_('平台满减金额（商家出补贴）'),
            L_('平台券金额'),
            L_('商家券金额'),
            L_('平台承担配送费补贴金额'),
            L_('商家承担配送费补贴金额'),
            L_('店铺折扣金额'),
            L_('分类折扣金额'),
            L_('限时优惠金额'),
            L_('平台会员等级优惠金额'),
            L_('店铺满减金额'),
            L_('店铺首单满减金额'),
            L_('会员卡折扣'),
            L_('会员卡折扣金额'),
            L_('平台给商家结款金额'),
            L_('期望送达时间'),
            L_('取单号'),
            L_('取餐方式'),
            L_('注册姓名'),
            L_('注册电话'),
            L_('备注'),
            L_('打包费'),
        );


        $csv_data = [];


         $sql = "SELECT  o.*,uu.nickname AS register_name,uu.phone AS register_phone, (SELECT m.name FROM " . C('DB_PREFIX') . "merchant AS m WHERE o.mer_id=m.mer_id) AS merchant_name,d.name as good_name,d.price as good_price ,d.spec,d.unit,d.cost_price, d.num as good_num,(SELECT s.name FROM " . C('DB_PREFIX') . "merchant_store AS s WHERE s.store_id=o.store_id) AS store_name , (SELECT mm.money FROM " . C('DB_PREFIX') . "merchant_money_list AS mm WHERE mm.order_id = o.real_orderid AND mm.type = 'shop' LIMIT 1) AS income_money,u.identity,u.department,u.name user_card_name,u.card_number,u.lable_ids  FROM " . C('DB_PREFIX') . "shop_order AS o INNER JOIN " . C('DB_PREFIX') . "shop_order_detail AS d ON `d`.`order_id`=`o`.`order_id` 
		LEFT JOIN " . C('DB_PREFIX') . "employee_card_user AS u ON o.uid = u.uid AND o.mer_id = u.mer_id LEFT JOIN ".C('DB_PREFIX')."user uu ON uu.uid=o.uid
		 " . $condition_where . " ORDER BY o.order_id DESC";

        $result_list = D()->query($sql);

        //获取用户信息
        $uids = array_unique(array_column($result_list, 'uid'));
        if ($uids) {
            $users = D('User')->where(['uid' => ['IN', $uids]])->getField('uid,nickname,phone');
        } else {
            $users = [];
        }

        $m_area = M('Area');
        $m_shop_order_detail = M('Shop_order_detail');
        $m_merchant_store_shop = M("Merchant_store_shop");
        $m_merchant_store = M("Merchant_store");
        $m_shop_goods = M('shop_goods');
        $d_shop_discount = D('Shop_discount');
        $m_merchant_money_list = M('Merchant_money_list');

        // 分类表单填写项
        $category_name = array();
        $category_list = M(_view('Shop_category'))->field(true)->where(array('status'=>1))->select();
        foreach ($category_list as $key => $value) {
            $value['cue_field'] = unserialize($value['cue_field']);
            foreach ($value['cue_field'] as $_cue_field) {
                $category_name[] = $_cue_field['name'];
            }
        }
        if ($category_name) {
            $category_name_format = array_unique($category_name);
            $csv_head = array_merge($csv_head,$category_name_format);
            foreach ($category_name_format as $key => $value) {
                $category_name_format[$key] = array('20',$value);
            }

        }
		//员工卡标签
		$cardCondition = [];
		if ($param['merchant_session']) {
			$cardCondition['mer_id'] = $param['store_session']['mer_id'];
		}
		$cardLable = M('Employee_card_lable')->where($cardCondition)->getField('id,name', true);
        if (!empty($result_list)) {
            $tmp_id = 0;
            foreach ($result_list as $orderKey => $value) {
				//处理员工卡标签
				$lableStr = '';
				if($cardLable && count($cardLable)){
					$tempLable = explode(',', $value['lable_ids']);
					foreach($tempLable as $vv){
						$lableStr .= isset($cardLable[$vv]) ? ($cardLable[$vv] . ' ') : ''; 
					}
					$lableStr = rtrim($lableStr, ' ');
				}
				$goodsStr = '';//同一订单商品描述
				$good_num = 0;//称重商品计算数量
				$isGoods = 0; //是否是商品 0不是 1是
                if ($tmp_id == $value['real_orderid']) {
					$good_num = $value['good_num'];
					//称重商品计算数量
					if($value['sell_type'] == 2 && $value['code_weight']){
						$good_num = $value['code_weight'] / 500;
						if(!$good_num){
							$good_num = 1;
						}
					}
					$goodsStr = "此为订单组合商品";
					$isGoods = 1;//是否是商品 0不是 1是
                } 
                
				$circle_info = '';
				$circle_id = $m_merchant_store->where(array('store_id' => $value['store_id']))->getField('circle_id');
				if ($circle_id) {
					// 获取对应商圈信息
					$condition_area = array(
						'area_id' => $circle_id
					);
					$circle_arr = $m_area->where($condition_area)->field('area_pid, area_name, area_ip_desc')->find();
					if (!$circle_arr['area_ip_desc']) {
						if ($circle_arr['area_pid']) {
							$condition_pid_area = array(
								'area_id' => $circle_arr['area_pid']
							);
							$circle_msg = $m_area->where($condition_pid_area)->getField('area_name');
							$circle_info = $circle_msg . $circle_arr['area_name'];
						} else {
							$circle_info = $circle_arr['area_name'];
						}
					} else {
						$circle_info = $circle_arr['area_ip_desc'];
					}
				}


				$m_where = [
					'type' => ['IN', ['mall', 'shop', 'shop_offline']],
					'_string' => sprintf("(order_id='%s' OR order_id='%s')", $value['real_orderid'], $value['order_id'])
				];
				$mer_settle_record  = $m_merchant_money_list->where(array_merge($m_where,['income'=>1]))->field('system_take,money')->order('id ASC')->find();
				$mer_refund_settle_record = $m_merchant_money_list->where(array_merge($m_where,['income'=>2]))->field('system_take,money')->order('id ASC')->find();
				$system_take = $value['income_money'] = 0;
				if ($mer_settle_record) {
					if (!empty($mer_refund_settle_record)) {
						$system_take = getFormatNumber($mer_settle_record['system_take'] - $mer_refund_settle_record['system_take']);
						$value['income_money'] = getFormatNumber($mer_settle_record['money'] - $mer_refund_settle_record['money']);
					} else {
						$system_take = getFormatNumber($mer_settle_record['system_take']);
						$value['income_money'] = getFormatNumber($mer_settle_record['money']);
					}
				}

				// 骑士应交账金额 $value['price']   货到付款  即  $value['pay_type'] == 'offline' 时候

				// 满减优惠 （平台出补贴） 和 满减优惠 （商家补贴）
				$shop_detail = $m_shop_order_detail->where(array('order_id' => $value['order_id']))->field(true)->select();
				// 满减优惠 （平台出补贴）
				$plat_full_money = 0;
				// 满减优惠 （商家补贴）
				$merchant_full_money = 0;
				// 商家承担配送费补贴金额
				$merchant_express_money = 0;
				// 平台承担配送费补贴金额
				$plat_express_money = 0;
				// 店铺满减金额
				$merchant_reduce_money = 0;
				//店铺首单满减金额
				$merchant_reduce_money_first = 0;

				if ($value['discount_detail'] && @unserialize($value['discount_detail'])) {
					$details = unserialize($value['discount_detail']);
					foreach ($details as $_discount) {
						if ($_discount['discount_type'] == 1 || $_discount['discount_type'] == 2) { // 平台首单满减,平台满减
							$plat_full_money += $_discount['plat_money'];
							$merchant_full_money += $_discount['merchant_money'];
						}
						if ($_discount['discount_type'] == 5) { // 平台配送订单满减
							$plat_express_money += $_discount['plat_money'];
							$merchant_express_money += $_discount['merchant_money'];
						}
						if ($_discount['discount_type'] == 3) { // 商家首单满减
							$merchant_reduce_money_first += $_discount['minus'];
						}
						if ($_discount['discount_type'] == 4) { // 商家满减
							$merchant_reduce_money += $_discount['minus'];
						}
					}
				}

				// 平台券金额（平台出补贴） $value['coupon_price']
				// 商家券金额（商家出补贴） $value['card_price']

				// 限时优惠金额
				$limit_money = 0;
				$leveloff_money = 0;
				$store_discount_money = 0; // 店铺折扣金额
				$cat_discount_money = 0; // 分类折扣金额
				// 计算优惠 等级优惠
				if ($shop_detail) {
					foreach ($shop_detail as $k => $v) {
						if ($v && $v['price'] <= $v['old_price']) {
							$limit_money += round((intval($v['num']) * floatval(($v['old_price'] - $v['price']))), 2);
						}
						$total_price = floatval($v['price'] * $v['num']);
						if ($v['discount_type'] == 1) {
							$store_discount_money += round(($total_price - floatval($v['discount_price']*$v['num'])), 2);
						} elseif ($v['discount_type'] == 2) {
							$cat_discount_money += round(($total_price - floatval($v['discount_price']*$v['num'])), 2);
						} elseif ($v['discount_type'] == 3) {
							$leveloff_money += round(($total_price - floatval($v['discount_price']*$v['num'])), 2);
						} elseif ($v['discount_type'] == 4) {
							// 先计算店铺折扣
							$discount_arr = explode(',', $v['discount_rate']);
							$first_discount_money = $total_price - ($total_price * $discount_arr[0] * 0.01);
							if ($first_discount_money > 0) {
								$store_discount_money += round($first_discount_money, 2);
							}
							// 再计算vip折扣
							$second_price = $total_price * $discount_arr[0] * 0.01; // 第二次打折的总价钱
							$second_discount_money = $second_price - ($second_price * $discount_arr[1] * 0.01);
							if ($second_discount_money > 0) {
								$leveloff_money += round($second_discount_money, 2);
							}
						} elseif ($v['discount_type'] == 5) {
							// 先计算分类折扣
							$discount_arr = explode(',', $v['discount_rate']);
							$first_discount_money = $total_price - floatval($total_price * $discount_arr[0] * 0.01);
							if ($first_discount_money > 0) {
								$cat_discount_money += round($first_discount_money, 2);
							}
							// 再计算vip折扣
							$second_price = $total_price * $discount_arr[1] * 0.01; // 第二次打折的总价钱
							$second_discount_money = $second_price - floatval($second_price * $discount_arr[1] * 0.01);
							if ($second_discount_money > 0) {
								$leveloff_money += round($second_discount_money, 2);
							}
						}
					}
				}
				// 实付总价
				$real_total_price = $value['price'] - $value['coupon_price'] - $value['card_price'];
				$real_total_price = sprintf("%.2f", $real_total_price);
				// 原平台配送费
				$deliver_fee = floatval($value['freight_charge']) + $merchant_express_money + $plat_express_money;
				$deliver_fee = sprintf("%.2f", $deliver_fee);

				$total_online_temp = $value['card_price'] + $value['merchant_balance'] + $value['balance_pay'] + $value['payment_money'] + $value['score_deducte'] + $value['coupon_price'] + $value['card_give_money'];
				$total_online_temp = $total_online_temp > $value['price'] ? $value['price'] : $total_online_temp;


				$cardUserData = M('Card_userlist')->field(true)->where(['uid' => $value['uid']])->find() ?? [];//会员卡数据

				// 会员卡折扣金额
				if($value['card_discount']){
					$card_discount_money = getFormatNumber(($value['price']-$value['freight_charge']) * (10-$value['card_discount'])/10);
				}

				$address_info = D('User_adress')->where(array('adress_id' => $value['address_id']))->find();
				if(!empty($address_info)){
					$pro_name=$this->getAreaName($address_info['province']);
					$city_name=$this->getAreaName($address_info['city']);
					$area_name=$this->getAreaName($address_info['area']);
					$value['address']=$pro_name.$city_name.$area_name.' '.$value['address'];
				}

				$csv_data[$orderKey] = [
					$value['real_orderid']."\n".$goodsStr,
					$value['orderid'] ? 'shop_' . $value['orderid']."\n" : ' ',// 流水号
					$value['good_name'],
					$value['cost_price'],
					$value['good_price'],
					$value['unit'],
					$value['spec'],
					$good_num ?: $value['good_num'],
					$value['merchant_name'],
					$value['store_name'],
					$value['username'],
					$value['userphone'],
					!empty($cardUserData['gid']) ? M('Card_group')->where(['id' => $cardUserData['gid']])->getField('name') : '无',
					!empty($cardUserData['name']) ? $cardUserData['name'] : '无',
					$value['address'],
					/*$value['identity'],
					$value['department'],
					$value['user_card_name'], //姓名
					$value['card_number'],
					$lableStr,*/
					$value['register_name'],
					$value['register_phone'],
					$isGoods ? 0 : getFormatNumber($value['total_price']),
					$isGoods ? 0 : getFormatNumber($value['balance_reduce']),
					$isGoods ? 0 : getFormatNumber($value['merchant_reduce']),
					$isGoods ? 0 : getFormatNumber($value['price']),
					$isGoods ? 0 : getFormatNumber($value['payment_money']),
					$isGoods ? 0 : getFormatNumber($value['merchant_balance']),
					$isGoods ? 0 : getFormatNumber($value['balance_pay']),
					$isGoods ? 0 : getFormatNumber($value['membership_card_price']),
					$isGoods ? 0 : getFormatNumber($value['score_deducte']),
				];

				if ($value['is_pick_in_store'] == 0) {
					$csv_data[$orderKey][] = $isGoods ? 0 : $deliver_fee;
					$csv_data[$orderKey][] = $isGoods ? 0 : getFormatNumber($value['freight_charge']);
				} elseif ($value['is_pick_in_store'] == 1) {
					$csv_data[$orderKey][] = 0;
					$csv_data[$orderKey][] = $isGoods ? 0 : getFormatNumber($value['freight_charge']);
				} else {
					$csv_data[$orderKey][] = 0;
					$csv_data[$orderKey][] = 0;
				}
				$csv_data[$orderKey][] = $isGoods ? 0 : getFormatNumber($merchant_express_money);

				$csv_data[$orderKey][] = $value['pay_time'] ? date('Y-m-d H:i:s', $value['pay_time']) : '';
				$csv_data[$orderKey][] = $value['use_time'] ? date('Y-m-d H:i:s', $value['use_time']) : '';
				$csv_data[$orderKey][] = D('Shop_order')->status_list[$value['status']];
				$payTxt = D('Pay')->get_pay_name($value['pay_type'], $value['is_mobile_pay'], $value['paid'],$value['order_id'],[],'shop');
				if($value['from_plat']==3){
					$payTxt.='(扫用户码)';
				}
				$csv_data[$orderKey][] = $payTxt;
				$csv_data[$orderKey][] = $value['third_id']."\t";
				$csv_data[$orderKey][] = $circle_info;
				$csv_data[$orderKey][] = $isGoods ? 0 : $system_take;


				if ($value['pay_type'] == 'offline') {
					$csv_data[$orderKey][] = $isGoods ? 0 : $value['price'];
				} else {
					$csv_data[$orderKey][] = '';
				}

				$csv_data[$orderKey][] = $isGoods ? 0 : $plat_full_money;
				$csv_data[$orderKey][] = $isGoods ? 0 : $merchant_full_money;
				$csv_data[$orderKey][] = $isGoods ? 0 : $value['coupon_price'];
				$csv_data[$orderKey][] = $isGoods ? 0 : $value['card_price'];
				$csv_data[$orderKey][] = $isGoods ? 0 : $plat_express_money;
				$csv_data[$orderKey][] = $isGoods ? 0 : $merchant_express_money;
				$csv_data[$orderKey][] = $isGoods ? 0 : $store_discount_money;
				$csv_data[$orderKey][] = $isGoods ? 0 : $cat_discount_money;
				$csv_data[$orderKey][] = $isGoods ? 0 : $limit_money;
				$csv_data[$orderKey][] = $isGoods ? 0 : $leveloff_money;
				$csv_data[$orderKey][] = $isGoods ? 0 : $merchant_reduce_money;
				$csv_data[$orderKey][] = $isGoods ? 0 : $merchant_reduce_money_first;
				$csv_data[$orderKey][] = $isGoods ? 0 : $value['card_discount'];
				$csv_data[$orderKey][] = $isGoods ? 0 : $card_discount_money;
				$csv_data[$orderKey][] = $isGoods ? 0 : $value['income_money'];
				$csv_data[$orderKey][] = $value['expect_use_time'] && $value['order_from']!=6 ? date('Y-m-d H:i:s', $value['expect_use_time']) : L_('无');
				$csv_data[$orderKey][] = $value['fetch_number'] ? $value['fetch_number'] : '';
				// 取餐方式
				if ($value['is_pick_in_store'] == 2) {
					$csv_data[$orderKey][] = L_('自提');
				} else {
					$csv_data[$orderKey][] = L_('配送');
				}

				//注册姓名
				$csv_data[$orderKey][] = $users[$value['uid']]['nickname'] ?? '';
				//注册电话
				$csv_data[$orderKey][] = $users[$value['uid']]['phone'] ?? '';

				// 备注
				$csv_data[$orderKey][] = $value['desc'];
				$csv_data[$orderKey][] = $value['packing_charge'];//打包费
				// 自定义填写项
				$cue_field_arr =  $value['cue_field'] ? unserialize($value['cue_field']) : array();
				$cue_field_format = array();
				$cue_field_title = array();
				foreach ($cue_field_arr as $_cue_field) {
					$cue_field_format[$_cue_field['title']] = $_cue_field['txt'];
					$cue_field_title[] = $_cue_field['title'];
				}
				foreach ($category_name_format as $_cat_name) {
					if (in_array($_cat_name[1],$cue_field_title)) {
						/*店员打印出现身份证不全解决办法*/
						$csv_data[$orderKey][] = "\t".$cue_field_format[$_cat_name[1]];
					}else{
						$csv_data[$orderKey][] = '';
					}
				}
                if(C('config.site_url')<> C('config.export_orderid_except_site')){//该客户不需要合并同一订单显示
                    $tmp_id = $value['real_orderid'];
                }
            }
        }

        $filename = date("Y-m-d", time()) . '-' . $param['rand_number'] . '.csv';
        $csvFileName = './runtime/' . $filename;
        D('Order')->putCsv($csvFileName, $csv_data, $csv_head);
    }


    /**
     * 商品信息导出
     */
    public function exportGoods(&$objProps,&$objExcel,$param)
    {
        $title = L_('商品信息导出');
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        // 设置当前的sheet
        $objExcel->setActiveSheetIndex(0);

        $sell_type = isset($param['sell_type']) ? $param['sell_type'] : 1;
        $is_shop = isset($param['is_shop']) ? intval($param['is_shop']) : 0;
        $where = ['store_id' => $param['store_id'], 'sell_type' => $sell_type];
        $is_shop && $where['goods_type'] = 0;
        $fields = '*';
        $goods = M('Shop_goods')->field($fields)->where($where)->select();
        $length = count($goods) / 1000;
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);
            $objExcel->getActiveSheet()->setTitle(L_('第X1个一千条商品信息', ($i + 1)));
            $objActSheet = $objExcel->getActiveSheet();
            //计重商品导出模板
            if ($sell_type == 2) {
                $title_arr = array(
                    array(40, L_('商品名称')),
                    array(40, L_('商品副标题')),
                    array(20, L_('商品分类名称')),
                    array(20, L_('商品单位')),
                    array(20, L_('商品单价/元/一斤（500g）')),
                    array(20, L_('商品重量（单位：g或者kg）')),
                    array(20, L_('商品皮重（单位：g）')),
                    array(20, L_('当前库存（单位：g）')),
                    array(20, L_('原始库存（单位：g）')),
                    array(20, L_('条形码')),
                );

                $lastColumn = D('Order')->setPHPExcelHeader($objExcel, $title_arr, 1, 15);
                $tmp_id = 0;

                $goodsMod = D('Shop_goods');
                $goodsSortMod = M('Shop_goods_sort');
                if (!empty($goods)) {
                    $index = 2;
                    foreach ($goods as $value) {
                        //计算分类名称
                        $sort1 = $goodsSortMod->where(['sort_id' => $value['sort_id']])->find();
                        $sort2 = $sort1['fid'] == 0 ? [] : $goodsSortMod->where(['sort_id' => $sort1['fid']])->find();
                        $sort3 = $sort2['fid'] == 0 ? [] : $goodsSortMod->where(['sort_id' => $sort2['fid']])->find();
                        $sort = [$sort3 ? $sort3['sort_name'] : '', $sort2 ? $sort2['sort_name'] : '', $sort1 ? $sort1['sort_name'] : ''];
                        $sort = array_filter($sort);
                        $sort = implode($sort, '#');

                        $column = 0;
                        $objExcel->getActiveSheet()->setCellValueExplicit('A' . $index, $value['name']);
                        $objExcel->getActiveSheet()->setCellValueExplicit('B' . $index, $value['describe']);
                        $objExcel->getActiveSheet()->setCellValueExplicit('C' . $index, $sort);
                        $objExcel->getActiveSheet()->setCellValueExplicit('D' . $index, $value['unit']);
                        $objExcel->getActiveSheet()->setCellValueExplicit('E' . $index, $value['unit_price']);
                        $objExcel->getActiveSheet()->setCellValueExplicit('F' . $index, $value['weight'] . ($value['weight_unit'] == 1 ? 'g' : 'kg'));
                        $objExcel->getActiveSheet()->setCellValueExplicit('G' . $index, $value['tare_weight']);
                        $objExcel->getActiveSheet()->setCellValueExplicit('I' . $index, $value['stock_weight']);
                        $objExcel->getActiveSheet()->setCellValueExplicit('J' . $index, $value['original_weight']);
                        $objExcel->getActiveSheet()->setCellValueExplicit('K' . $index, $value['number']);
                        $index++;
                    }
                }
            }else{
                //下面是计件商品的逻辑
                $title_arr = array(
                    array(40, L_('商品名称')),
                    array(40, L_('商品副标题')),
                    array(20, L_('商品分类名称')),
                    array(20, L_('商品单位')),
                    array(20, L_('商品现价')),
                    array(20, L_('规格名称')),
                    array(20, L_('属性名称')),
                    array(20, L_('规格价/元')),
                    array(20, L_('当前库存')),
                    array(20, L_('原始库存')),
                    array(20, L_('条形码')),
					array(20, L_('商品进价/元')),
                    array(20, L_('规格进价/元')),
                );

                $lastColumn = D('Order')->setPHPExcelHeader($objExcel, $title_arr, 1, 15);
                $tmp_id = 0;
                $propertiesMod = M('Shop_goods_properties');
                $specMod = M('Shop_goods_spec');
                $specValMod = M('Shop_goods_spec_value');
                $goodsMod = D('Shop_goods');
                $goodsSortMod = M('Shop_goods_sort');
                if (!empty($goods)) {
                    $index = 2;
                    foreach ($goods as $value) {
                        //计算分类名称
                        $sort1 = $goodsSortMod->where(['sort_id' => $value['sort_id']])->find();
                        $sort2 = $sort1['fid'] == 0 ? [] : $goodsSortMod->where(['sort_id' => $sort1['fid']])->find();
                        $sort3 = $sort2['fid'] == 0 ? [] : $goodsSortMod->where(['sort_id' => $sort2['fid']])->find();
                        $sort = [$sort3 ? $sort3['sort_name'] : '', $sort2 ? $sort2['sort_name'] : '', $sort1 ? $sort1['sort_name'] : ''];
                        $sort = array_filter($sort);
                        $sort = implode($sort, '#');

                        //计算属性
                        $propertieLists = $propertiesMod->where(['goods_id' => $value['goods_id']])->select();
                        $propertieStr = '';
                        foreach ($propertieLists as $p) {
                            $vals = array_map(function ($v) {
                                return substr($v, 0, -2);
                            }, explode(',', $p['val']));

                            if ($propertieStr) {
                                $propertieStr .= '+' . $p['name'] . '#' . implode($vals, '&');
                            } else {
                                $propertieStr .= $p['name'] . '#' . implode($vals, '&');
                            }
                        }

                        //计算规格
                        $return = $goodsMod->format_spec_value($value['spec_value'], $value['goods_id']);
                        $specIndex = array_combine(array_column($return['spec_list'], 'id'), array_column($return['spec_list'], 'name'));
                        $specValue = $return['list'];
                        $specArr = [];
                        foreach ($specValue as $spec) {
                            $str = '';
                            foreach ($spec['spec'] as $sv) {
                                if ($str) {
                                    $str .= '+';
                                }
                                $str .= isset($specIndex[$sv['spec_val_sid']]) ? $specIndex[$sv['spec_val_sid']] : '';
                                $str .= '#' . $sv['spec_val_name'];
                            }
                            $specArr[] = [
                                'spec' => $str,
                                'sku' => $spec['number'],
                                'goods_weight' => $spec['goods_weight'],
                                'price' => $spec['price'],
								'cost_price' => $spec['cost_price'],
                            ];
                        }
                        $column = 0;
                        $objExcel->getActiveSheet()->setCellValueExplicit('A' . $index, $value['name']);
                        $objExcel->getActiveSheet()->setCellValueExplicit('B' . $index, $value['describe']);
                        $objExcel->getActiveSheet()->setCellValueExplicit('C' . $index, removeEmojiChar($sort));
                        $objExcel->getActiveSheet()->setCellValueExplicit('D' . $index, $value['unit']);
                        $objExcel->getActiveSheet()->setCellValueExplicit('E' . $index, $value['price']);
                        $objExcel->getActiveSheet()->setCellValueExplicit('G' . $index, $propertieStr);
                        $objExcel->getActiveSheet()->setCellValueExplicit('I' . $index, $value['stock_num']);
                        $objExcel->getActiveSheet()->setCellValueExplicit('J' . $index, $value['original_stock']);
                        $objExcel->getActiveSheet()->setCellValueExplicit('K' . $index, $value['number']);
						$objExcel->getActiveSheet()->setCellValueExplicit('L' . $index, $value['cost_price']);
                        if ($specArr) {
                            foreach ($specArr as $sk => $v) {
                                $objExcel->getActiveSheet()->setCellValueExplicit('F' . $index, $v['spec']);
                                $objExcel->getActiveSheet()->setCellValueExplicit('H' . $index, $v['price']);
								$objExcel->getActiveSheet()->setCellValueExplicit('M' . $index, $v['cost_price']);
                                $index++;
                            }
                        } else {
                            $index++;
                        }
                    }
                }
            }
            sleep(2);
        }
        unset($goods);
	}


    /**
     * 发送模板消息
     * @return void
     * @author: 张涛
     * @date: 2020/11/10
     */
    public function sendTempMsg($tpl, $data)
    {
        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
        $rs = $model->sendTempMsg($tpl, $data);
        return $rs;
    }


    /**
     * 打印小票
     * @author: 张涛
     * @date: 2020/11/10
     */
    public function printTicket($orderId, $status, $table = 'shop_order', $staffName = '', $rePrint = 0)
    {
        $printHaddle = new PrintHaddle();
        $printHaddle->printit($orderId, $table, $status, 0, $staffName, $rePrint);
        return true;
    }

    /**
     * 消费完成处理
     * @param $order
     * @param bool $is_staff
     * @author: 张涛
     * @date: 2020/11/13
     */
    public function shopNotice($order, $is_staff = false)
    {
        R('Appapi/Storestaff/shop_notice_v20', ['order' => $order, 'is_staff' => $is_staff]);
    }

    public function auto_set($order_id){
        $order =  D("Shop_order")->where(array('order_id' => $order_id))->find();
        $order_from=0;
        if($order['order_from']==7){
            $order_from=1;
        }elseif ($order['order_from']==8){
            $order_from=2;
        }
        $supply = array();
        //if (empty($order['third_id']) && $order['pay_type'] == 'offline') $order['paid'] = 0;
        $supply['order_from'] = $order_from;//订单来源(0:快店，1:饿了么，2:美团，3：帮送，4：帮买(指定地址)),5：帮买（未指定地址）
        $supply['order_id'] = $order_id;
        $supply['paid'] = $order['paid'];
        $supply['pay_time'] = intval($order['pay_time']);
        $supply['real_orderid'] = isset($order['real_orderid']) ? $order['real_orderid'] : '';
        $supply['pay_type'] = $order['pay_type'];
        $supply['money'] = $order['price'];
        $supply['deliver_cash'] = round($order['price']+$order['extra_price'] - round($order['card_price'] + $order['merchant_balance'] + $order['card_give_money'] +$order['balance_pay'] + $order['payment_money'] + $order['score_deducte'] + $order['coupon_price'], 2), 2);
        $supply['deliver_cash'] = max(0, $supply['deliver_cash']);
        $supply['store_id'] = $order['store_id'];
        $store =  D("Merchant_store")->where(array('store_id' => $order['store_id']))->find();
        $supply['store_name'] = $store['name'];
        $supply['mer_id'] = $store['mer_id'];
        $supply['from_site'] = $store['adress'];
        $supply['from_lnt'] = $store['long'];
        $supply['from_lat'] = $store['lat'];

        $supply['province_id'] = $store['province_id'];
        $supply['city_id'] = $store['city_id'];
        $supply['area_id'] = $store['area_id'];

        //目的地
        $supply['aim_site'] =  $order['address'];
        $supply['aim_lnt'] = $order['lng'];
        $supply['aim_lat'] = $order['lat'];
        $supply['name']  = $order['username'];
        $supply['phone'] = str_replace(' ', '', $order['userphone']);
        $supply['fetch_number'] = isset($order['fetch_number']) ? $order['fetch_number'] : '';

        $supply['status'] =  1;
        $supply['type'] = $order['is_pick_in_store'] != 5 ? $order['is_pick_in_store'] : 0;
        $supply['item'] = 2;//0:老快店的外卖，1：外送系统，2：新快店, 3:对接的
        $supply['create_time'] = $_SERVER['REQUEST_TIME'];
        $shop = M('Merchant_store_shop')->field(true)->where(array('store_id' => $order['store_id']))->find();
        $supply['order_out_time'] = $_SERVER['REQUEST_TIME'] + $shop['work_time'] * 60;//预计出单时间
        //$supply['start_time'] = $_SERVER['REQUEST_TIME'];
        $supply['appoint_time'] = $order['expect_use_time'];
        $supply['note'] = $order['desc'];
        $supply['is_right_now'] = intval($order['is_right_now']);//是否立即送达时间

        $supply['order_time'] = $order['pay_time'];//订单支付时间
        $delivery_fee=0;
        $delivery_fee = $delivery_fee ? $delivery_fee : ($order['freight_charge_original']?$order['freight_charge_original']:$order['freight_charge']);//原始配送费

        $supply['freight_charge'] = $delivery_fee;//配送费

        if ($order['distance']) {
            $supply['distance'] = round($order['distance']/1000, 2);//配送距离
        } else {
            $supply['distance'] = round(getDistance($order['lat'], $order['lng'], $store['lat'], $store['long'])/1000, 2);//配送距离
        }

        // 号码隐私处理
        $supply['virtual_phone'] = $order['virtual_phone']??'';//虚拟号码可以多个用;分割，主机号与分机号用,号分割
        $supply['virtual_phone_overtime'] = $order['virtual_phone_overtime']??0;//虚拟号码过期时间
        $arr=M('Deliver_supply')->data($supply)->add();
        if($arr){
          return true;
        }else{
            return false;
        }
    }


    /**
     * 判断称重订单是否全部录入了重量
     * @param $orderId
     * @return bool
     * @author: 张涛
     * @date: 2020/11/27
     */
    public function isAllWriteRealWeight($orderId)
    {
        $rs = M('Shop_order_detail')->where(['order_id' => $orderId, 'sell_type' => 2, 'real_weight' => 0])->find();
        return $rs ? false : true;
    }

    /**
     * 标签打印
     * @date: 2021/06/13
     */
    public function labelPrint($orderId)
    {
        $orderDetail = D('Shop_order')->get_order_detail(array('order_id' => $orderId), true);
        D('Label_printer')->shopOrderLabel($orderDetail);
    }

    /**
     * @param $areaId
     * @return mixed
     * 获取地方名称
     */
    public function getAreaName($areaId){
        return M('Area')->where(['area_id'=>$areaId])->getField('area_name');
    }

	/**
	 * 根据订单id计算商家活动支出
	 * 注意：改动此方法记得同步调整\app\shop\model\service\order\ShopOrderService/computeMerchantExpendByOrderId
	 */
	public function computeMerchantExpendByOrderId($orderId)
	{
		$order = $this->where(['order_id' => $orderId])->find();
		$discount = [];

		$discountDetail = unserialize($order['discount_detail']);
		if ($discountDetail && isset($discountDetail['minus']) && $discountDetail['minus']) {
			$merMinus = $discountDetail['minus'];
			if ($merMinus['money'] > 0) {
				$title = sprintf('店铺优惠满%s减%s', getFormatNumber($merMinus['money']), getFormatNumber($merMinus['minus']));
			} else {
				$title = sprintf('店铺优惠减%s', getFormatNumber($merMinus['minus']));
			}
			$discount['merchant_reduce'] = [
				'title' => $title,
				'symbol' => '-',
				'amount' => getFormatNumber($merMinus['minus'])
			];
		}
		if ($discountDetail && isset($discountDetail['newuser']) && $discountDetail['newuser']) {
			$merNewuser = $discountDetail['newuser'];
			if ($merNewuser['money'] > 0) {
				$title = sprintf('店铺首单满%s减%s', getFormatNumber($merNewuser['money']), getFormatNumber($merNewuser['minus']));
			} else {
				$title = sprintf('店铺首单减%s', getFormatNumber($merNewuser['minus']));
			}
			$discount['merchant_first_order_reduce'] = [
				'title' => $title,
				'symbol' => '-',
				'amount' => getFormatNumber($merNewuser['minus'])
			];
		}
		if ($order['card_discount'] > 0 && $order['card_discount'] < 10) {
			$cardDdiscountMoney = (($order['discount_price'] + $order['packing_charge'] + $order['other_money']) - ($order['merchant_reduce'] + $order['balance_reduce'])) * (1 - $order['card_discount'] * 0.1);
			$discount['merchant_card'] = [
				'title' => sprintf('商家会员卡%s折优惠', $order['card_discount']),
				'symbol' => '-',
				'amount' => $cardDdiscountMoney > 0 ? getFormatNumber($cardDdiscountMoney) : 0
			];
		}
		if ($order['card_id'] > 0 && $order['card_price'] > 0) {
			$discount['merchant_coupon'] = [
				'title' => L_('商家优惠券'),
				'symbol' => '-',
				'amount' => getFormatNumber($order['card_price'])
			];
		}
		if ($discountDetail && isset($discountDetail['delivery']) && $discountDetail['delivery']) {
			$sysDeliver = $discountDetail['delivery'];
			$sysDeliver['merchant_money'] = $sysDeliver['merchant_money'];
			if ($sysDeliver['minus'] > $order['freight_charge_original']) {
				$sum = $sysDeliver['merchant_money'] + $sysDeliver['plat_money'];
				if ($sum > 0) {
					$sysDeliver['merchant_money'] = $sysDeliver['merchant_money'] * $order['freight_charge_original'] / $sum;
				}
			}

			if ($sysDeliver['money'] > 0) {
				$title = sprintf('平台配送费优惠满%s减%s，商家补贴部分', getFormatNumber($sysDeliver['money']), getFormatNumber($sysDeliver['minus']));
			} else {
				$title = sprintf('平台配送费优惠减%s，商家补贴部分', getFormatNumber($sysDeliver['minus']));
			}
			$discount['system_deliver'] = [
				'title' => $title,
				'symbol' => '-',
				'amount' => getFormatNumber($sysDeliver['merchant_money'])
			];
		}
		if ($discountDetail && isset($discountDetail['system_minus']) && $discountDetail['system_minus']) {
			$sysMinus = $discountDetail['system_minus'];
			$balanceReduce = $order['discount_price'] + $order['freight_charge'];
			$merchantSubsidy = round($sysMinus['merchant_money'], 2);
			if ($sysMinus['minus'] >= $balanceReduce) {
				$sum = $sysMinus['merchant_money'] + $sysMinus['plat_money'];
				if ($sum > 0) {
					$merchantSubsidy = round($sysMinus['merchant_money'] * $balanceReduce / $sum, 2);
				}
			}

			if ($sysMinus['money'] > 0) {
				$title = sprintf('平台优惠满%s减%s，商家补贴部分', getFormatNumber($sysMinus['money']), getFormatNumber($sysMinus['minus']));
			} else {
				$title = sprintf('平台优惠减%s，商家补贴部分', getFormatNumber($sysMinus['minus']));
			}
			$discount['system_reduce'] = [
				'title' => $title,
				'symbol' => '-',
				'amount' => getFormatNumber($merchantSubsidy)
			];
		}

		if ($discountDetail && isset($discountDetail['system_newuser']) && $discountDetail['system_newuser']) {
			$systemNewuser = $discountDetail['system_newuser'];
			$systemNewuserMerchantSubsidy = round($systemNewuser['merchant_money'], 2);
			if ($systemNewuser['money'] > 0) {
				$title = sprintf('平台首单优惠满%s减%s', getFormatNumber($systemNewuser['money']), getFormatNumber($systemNewuser['minus']));
			} else {
				$title = sprintf('平台首单优惠减%s', getFormatNumber($systemNewuser['minus']));
			}
			$discount['system_newuser_reduce'] = [
				'title' => $title,
				'symbol' => '-',
				'amount' => getFormatNumber($systemNewuserMerchantSubsidy)
			];
		}

		if ($discountDetail && isset($discountDetail['plat_discount']) && $discountDetail['plat_discount']) {
			$platDiscount = $discountDetail['plat_discount'];
			if ($platDiscount['percentage_status'] == 1) {
				//折扣
				$platDiscountMerchantSubsidy = round($platDiscount['minus2'] * $platDiscount['merchant_money'], 2);
				if ($platDiscount['money'] > 0) {
					$title = sprintf('平台优惠满%s享%s折', getFormatNumber($platDiscount['money']), getFormatNumber($platDiscount['minus'] * 10));
				} else {
					$title = sprintf('平台优惠享%s折', getFormatNumber($platDiscount['minus'] * 10));
				}
			} else {
				//满减
				$platDiscountMerchantSubsidy = round($platDiscount['merchant_money'], 2);
				if ($platDiscount['money'] > 0) {
					$title = sprintf('平台优惠满%s减%s', getFormatNumber($platDiscount['money']), getFormatNumber($platDiscount['minus']));
				} else {
					$title = sprintf('平台优惠减%s', getFormatNumber($platDiscount['minus']));
				}
			}

			$discount['plat_discount_reduce'] = [
				'title' => $title,
				'symbol' => '-',
				'amount' => getFormatNumber($platDiscountMerchantSubsidy)
			];
		}

		if ($order['vip_level_discount'] > 0 && $order['vip_level_discount'] < 100) {
			$discount['system_card'] = [
				'title' => sprintf('平台会员等级%s折优惠', getFormatNumber($order['vip_level_discount'] / 10)),
				'symbol' => '-',
				'amount' => getFormatNumber($order['vip_level_reduce_money'])
			];
		}

		if ($order['coupon_id'] > 0 && $order['coupon_price'] > 0) {
			$sysCoupon = D('System_coupon_hadpull')->alias('p')
				->join('INNER JOIN '.C('DB_PREFIX').'system_coupon c ON c.coupon_id=p.coupon_id')
				->field('c.*')
				->where(['p.id' => $order['coupon_id']])
				->find();
			if ($sysCoupon) {
				if ($sysCoupon['is_discount'] == 0) {
					if ($sysCoupon['discount'] > $order['coupon_price']) {
						$sum = $sysCoupon['merchant_money'] + $sysCoupon['plat_money'];
						if ($sum > 0) {
							$sysCoupon['merchant_money'] = round($sysCoupon['merchant_money'] * $order['coupon_price'] / $sum, 2);
							$sysCoupon['plat_money'] = $order['coupon_price'] - $sysCoupon['merchant_money'];
						}
					}

					$discount['system_coupon'] = [
						'title' => L_('平台优惠券，商家补贴部分'),
						'symbol' => '-',
						'amount' => getFormatNumber($sysCoupon['merchant_money'])
					];
				} else {
					$discount['system_coupon'] = [
						'title' => L_('平台优惠券，商家补贴部分'),
						'symbol' => '-',
						'amount' => getFormatNumber($sysCoupon['merchant_money'] * $order['coupon_price'])
					];
				}
			}
		}

		if ($order['card_give_money'] > 0) {
			$discount['card_give_money'] = [
				'title' => L_('商家会员卡余额支付的线下充值部分'),
				'symbol' => '-',
				'amount' => getFormatNumber($order['card_give_money'])
			];
		}
		return $discount;
	}

	public function changeAddressDistance($orderId, $lat, $lng)
	{
		$shopOrder     = M('Shop_order')->where(['order_id' => $orderId])->field('mer_id, store_id, order_id, lat, lng')->find();
		$orderDistance = getDistance($lat, $lng, $shopOrder['lat'], $shopOrder['lng']);

		$hasChangeAddress = D('Merchant_store_shop')->hasChangeAddress($shopOrder['mer_id'], $shopOrder['store_id'], $shopOrder['order_id']);

		if (isset($hasChangeAddress['distance'])) {
			$exceedDistance = round(($orderDistance / 1000) - $hasChangeAddress['distance'], 3);
			if ($exceedDistance > 0) {
				return [
					'has_select'      => false,
					'exceed_distance' => $exceedDistance
				];
			}
			return [
				'has_select'      => true,
				'exceed_distance' => 0
			];
		}
		return [
			'has_select'      => false,
			'exceed_distance' => 0
		];
	}


	public function saveTwOrder($orderId)
	{
		$nowOrder = $this->where(['order_id' => $orderId])->find();
		if (empty($nowOrder)) {
			return false;
		}

		$store = D('Merchant_store')->where(['store_id' => $nowOrder['store_id']])->find();
		$user = D('User')->where(['uid' => $nowOrder['uid']])->find();

		if (empty($store) || empty($user) || empty($store['tw_shop_id'])) {
			return false;
		}

		$goods = D('Shop_order_detail')->alias('od')
			->join(C('DB_PREFIX') . 'shop_goods g ON od.goods_id=g.goods_id')
			->field('od.*,g.tw_item_no')
			->where(['od.order_id' => $orderId])
			->select();
		$twGoods = [];
		foreach ($goods as $g) {
			$twGoods[] = [
				'item_no' => $g['tw_item_no'],
				'item_name' => $g['name'],
				'sale_qty' => $g['num'],
				'sale_price' => $g['discount_price'],
				'item_row_amount' => $g['pay_price']
			];
		}

		$params = [
			'shop_id' => $store['tw_shop_id'],
			'cust_phone' => $user['phone'],
			'cust_name' => $user['nickname'],
			'oper_date' => date('Y-m-d H:i:s', $nowOrder['create_time']),
			'cust_province' => D('Area')->where(['area_id' => $store['province_id']])->getField('area_name'),
			'cust_city' => D('Area')->where(['area_id' => $store['city_id']])->getField('area_name'),
			'cust_area' => D('Area')->where(['area_id' => $store['area_id']])->getField('area_name'),
			'cust_add' => $nowOrder['address'],
			'sheet_amount' => $nowOrder['price'],
			'sheet_no' => $nowOrder['real_orderid'],
			'freight' => $nowOrder['freight_charge'],
			'memo' => $nowOrder['note'] ?: '',
			'products' => $twGoods
		];
		import('@.ORG.Tw');
		$result = (new Tw())->saveOrder($params);
		if ($result) {
			$this->where(['order_id' => $orderId])->save(['tw_order_no' => $result['tw_order_no']]);
		}
	}

	//整单取消
	public function cancelTwOrder($orderId){
		$nowOrder = $this->where(['order_id' => $orderId])->find();
		$merchant = D('Merchant')->where(['mer_id' => $nowOrder['mer_id']])->find();
		if(C('config.internal_membership_card') && $merchant['miaomaiyun_switch']){//淼迈取消订单
			import("@.ORG.Erp.MiaomaiErpService");
			$config = MiaomaiErpService::getErpConfigByMerchantId($nowOrder['mer_id']);
			fdump_api(['order_id' => $orderId,'msg' => '快店取消订单'], 'miaomai/mall_cancel_order', 1);
			
			$result = (MiaomaiErpService::getInstance())
				->setConfig($config['miaomaiyun_erp_appid'], $config['miaomaiyun_erp_appsecret'], $config['miaomaiyun_url'])
				->setParams([
					'order_code' => $nowOrder['miaomai_order_code']
				])
				->run(Miaomaiyun::CANCEL_ORDER);
			fdump_api(['order_id' => $orderId,'result' => $result], 'miaomai/mall_cancel_order', 1);
		}
		if (empty($nowOrder) || empty($nowOrder['tw_order_no'])) {
			return false;
		}

		$store = D('Merchant_store')->where(['store_id' => $nowOrder['store_id']])->find();
		$user = D('User')->where(['uid' => $nowOrder['uid']])->find();

		if (empty($store) || empty($user) || empty($store['tw_shop_id'])) {
			return false;
		}

		$goods = D('Shop_order_detail')->alias('od')
			->join(C('DB_PREFIX') . 'shop_goods g ON od.goods_id=g.goods_id')
			->field('od.*,g.tw_item_no')
			->where(['od.order_id' => $orderId])
			->select();
		$twGoods = [];
		foreach ($goods as $g) {
			$twGoods[] = [
				'item_no' => $g['tw_item_no'],
				'item_name' => $g['name'],
				'sale_qty' => $g['num'],
				'sale_price' => $g['discount_price'],
				'item_row_amount' => $g['pay_price']
			];
		}

		$params = [
			'shop_id' => $store['tw_shop_id'],
			'cust_phone' => $user['phone'],
			'tw_order_no' => $nowOrder['tw_order_no'],
			'refund_amount' => $nowOrder['price'],
			'refund_sheet_no' => 'R'.$nowOrder['real_orderid'],
			'memo' => '',
			'products' => $twGoods
		];
		import('@.ORG.Tw');
		(new Tw())->refundOrder($params);

		D('Card_new')->sync_tw_card($nowOrder['uid'],$nowOrder['store_id']);
	}

	//店员同意售后退款
	public function refundTwOrder($orderId, $refundId)
	{
		$nowOrder = $this->where(['order_id' => $orderId])->find();
		if (empty($nowOrder) || empty($nowOrder['tw_order_no'])) {
			return false;
		}

		$store = D('Merchant_store')->where(['store_id' => $nowOrder['store_id']])->find();
		$user = D('User')->where(['uid' => $nowOrder['uid']])->find();

		if (empty($store) || empty($user) || empty($store['tw_shop_id'])) {
			return false;
		}

		$refund = D('Shop_order_refund')->where(['id' => $refundId, 'order_id' => $orderId])->find();
		if (empty($refund)) {
			return false;
		}

		$goods = D('Shop_refund_detail')->alias('rd')
			->join(C('DB_PREFIX') . 'shop_goods g ON rd.goods_id=g.goods_id')
			->field('rd.*,g.tw_item_no')
			->where(['rd.refund_id' => $refundId])
			->select();
		$twGoods = [];
		foreach ($goods as $g) {
			$twGoods[] = [
				'item_no' => $g['tw_item_no'],
				'item_name' => $g['name'],
				'sale_qty' => $g['num'],
				'sale_price' => $g['price'],
				'item_row_amount' => $g['price']
			];
		}

		$params = [
				'shop_id' => $store['tw_shop_id'],
				'cust_phone' => $user['phone'],
				'tw_order_no' => $nowOrder['tw_order_no'],
				'refund_amount' => $refund['price'],
				'refund_sheet_no' => 'R' . $nowOrder['real_orderid'] . '_' . $refundId,
				'memo' => $refund['reason'] ?: '',
				'products' => $twGoods
			];
		import('@.ORG.Tw');
		(new Tw())->refundOrder($params);

		D('Card_new')->sync_tw_card($nowOrder['uid'],$nowOrder['store_id']);
	}


	//完成订单
	public function finishTwOrder($orderId){
		$nowOrder = $this->where(['order_id' => $orderId])->find();
		if (empty($nowOrder)) {
			return false;
		}

		$store = D('Merchant_store')->where(['store_id' => $nowOrder['store_id']])->find();
		$user = D('User')->where(['uid' => $nowOrder['uid']])->find();

		if (empty($store) || empty($user) || empty($store['tw_shop_id'])) {
			return false;
		}
		$params = [
			'shop_id' => $store['tw_shop_id'],
			'cust_phone' => $user['phone'],
			'tw_order_no' => $nowOrder['tw_order_no']
		];
		import('@.ORG.Tw');
		(new Tw())->finishOrder($params);

		D('Card_new')->sync_tw_card($nowOrder['uid'],$nowOrder['store_id']);
	}
	

	
	/**
	 * 系统自动添加评论
	 * copy from cms/Lib/Action/Appapi/MyAction.class.php addComment
	 * @param $order_id
	 */
	public function shopOrderAutoComment($order_id)
	{
		$goods_ids = [];
		$score =  5;
		$comment = '默认好评！';
		$dscore = 5;
		$dcomment = '默认好评！';
		
		$now_order = D('Shop_order')->get_order_detail(array('order_id' => $order_id));
		$goodsids = array();
		$goods = '';
		$pre = '';
		if (isset($now_order['info'])) {
			foreach ($now_order['info'] as $row) {
				if (!in_array($row['goods_id'], $goodsids)) {
					$goodsids[] = $row['goods_id'];
					if (in_array($row['goods_id'], $goods_ids)) {
						$goods .= $pre . $row['name'];
						$pre = '#@#';
					}
				}
			}
		}
		$database_reply = D('Reply');
		$data_reply = [
			'parent_id' => $now_order['store_id'],
			'store_id' => $now_order['store_id'],
			'mer_id' => $now_order['mer_id'],
			'score' => $score,
			'order_type' => 3,
			'order_id' => $order_id,
			'anonymous' => 1,
			'check' => 2,
			'comment' => $comment,
			'uid' => $now_order['uid'],
			'add_time' => time(),
			'add_ip' => get_client_ip(0),
			'goods' => $goods,
			'deliver_score' => $dscore,
			'reply_pic' => '',
		];
		$login_user = M('User')->where(['uid' => $now_order['uid']])->find();
		$res = $database_reply->data($data_reply)->add();
		if($res === false){
			fdump_sql([
				'msg' => '系统自动添加评论',
				'sql' => $database_reply->getLastSql()
			], 'shopOrderAutoComment');
			
			return false;
		}
	
		$from_type = 6;//系统自动评价
		//记录下用户评论行为分析$reply_id,$uid,$content,$business_type,$comment_time,$from_type,$add_ip
		D('User_behavior_reply')->add_record($res, $now_order['uid'], $comment, 1, time(), $from_type, get_client_ip(0));

		D('Merchant_store')->setInc_shop_reply($now_order['store_id'], $score, $dscore);
		D('Shop_order')->change_status($now_order['order_id'], 3, 'shop');
		D('Shop_order_log')->add_log(array('order_id' => $now_order['order_id'], 'status' => 8));
		foreach ($goods_ids as $goods_id) {
			if (in_array($goods_id, $goodsids)) {
				D('Shop_goods')->where(array('goods_id' => $goods_id))->setInc('reply_count', 1);
				D('Shop_order_detail')->where(array('goods_id' => $goods_id, 'order_id' => $order_id))->save(array('is_goods' => 1));
			}
		}

		if ($supply = D('Deliver_supply')->field(true)->where(array('order_id' => $now_order['order_id'], 'item' => 2))->find()) {
			D('Deliver_supply')->where(array('order_id' => $now_order['order_id'], 'item' => 2))->save(array('score' => $dscore, 'status' => 6, 'comment_time' => time(), 'comment' => $dcomment, 'userId' => $now_order['uid']));

			if ($user = D('Deliver_user')->field(true)->where(array('uid' => $supply['uid'], 'status' => array('neq', 4)))->find()) {
				$userData = array();
				$userData['reply_count'] = $user['reply_count'] + 1;
				$userData['total_score'] = $user['total_score'] + $dscore;
				$userData['average_score'] = round($userData['total_score'] / $userData['reply_count'], 2);
				D('Deliver_user')->where(array('uid' => $supply['uid'], 'status' => array('neq', 4)))->save($userData);

				// 只对系统配送员有效
				if (C('config.open_deliver_level') && $user['group'] == 1 && $dscore >= 4) {
					$score = floatval(C('config.deliver_level_good_reply_get_score'));
					$desc = '用户好评获得' . $score . '积分';
					D('Deliver_user')->add_score($supply['uid'], $score, $desc, 3, $supply);
				}
			}
		}

		if ($this->config['feedback_score_add'] > 0) {
			D('User')->add_extra_score($now_order['uid'], $this->config['feedback_score_add'], $this->config['shop_alias_name'] . '评论获得' . $this->config['feedback_score_add'] . '个' . $this->config['score_name'],[
				'mer_id'=>$now_order['mer_id'],
				'store_id'=>$now_order['store_id'],
				'order_id'=>$now_order['order_id'],
				'order_type'=>'shop',
			]);
			D('Scroll_msg')->add_msg('feedback', $now_order['uid'], '用户' . str_replace_name($login_user['nickname']) . '于' . date('Y-m-d H:i', $_SERVER['REQUEST_TIME']) . '对' . $this->config['shop_alias_name'] . '评论获得' . $this->config['feedback_score_add'] . '个' . $this->config['score_name']);
		}
		return true;
	}
	
	public function checkOrderRefund($order_id,$total_num=0){
		$status_txt = '已退款';
		if($total_num>0){
			$shop_total_num = $total_num;
		}else{
			$shop_total_num = D('Shop_order_detail')->where(['order_id'=>$order_id])->sum('num');
		}
		$shop_refund_num = D('Shop_order_refund')->join(' as r inner join '.C("DB_PREFIX").'shop_refund_detail as rd on rd.refund_id = r.id')
			->where(['r.order_id'=>$order_id,'r.status'=>1])->sum('rd.num');
		if($shop_refund_num && $shop_total_num > $shop_refund_num){
			$status_txt = '部分退款';
		}
		return $status_txt;
	}
}
?>
