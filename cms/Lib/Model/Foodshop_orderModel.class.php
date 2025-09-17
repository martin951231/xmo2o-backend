<?php
class Foodshop_orderModel extends Model
{
	public $status_list = array('-1' => '全部', '0' => '订单生成', 1 => '预定金已支付', 2 => '店员确认', 3 => '买单完成', 4 => '已评价', 5 => '已取消', 11 => '待店员操作');
	
	/**获取订单分类**/
	public function get_order_cate($order_id, $store_id = 0)
	{
		$store_id = $this->field('store_id')->where(array('order_id'=>$order_id))->find();
		$cat_id = M('Foodshop_category_relation')->field('cat_fid')->where(array('store_id'=>$store_id['store_id']))->find();
		$meal_cate = M('Foodshop_category')->field('cat_id,cat_name')->where(array('cat_id'=>$cat_id['cat_fid']))->find();
		return $meal_cate;
	}

	public function can_refund_status($now_order){
		if($now_order['status']!=1){
			return false;
		}else{
			return true;
		}
	}



	public function get_order_by_id($uid, $order_id, $orderid = 0)
	{
		$where = array();
		$order_id && $where['order_id'] = $order_id;
		$orderid && $where['orderid'] = $orderid;
		$where['uid'] = $uid;
		return $this->field(true)->where($where)->find();
	}

	public function get_order_by_orderid($order_id)
	{
		$where = array();


		$order_id && $where['order_id'] = $order_id;
		if (strlen($order_id) > 10){
			$where['real_orderid'] = $order_id;
			unset($where['order_id']);
		}
		return $this->get_order_detail($where, 3);
		return $this->field(true)->where($where)->find();
	}

	public function get_order($order_id){
		return $this->where(array('order_id'=>$order_id))->find();
	}

    public function get_pay_order($order_id)
    {
        $now_order = $this->get_order($order_id);
		// 获取商品数量
		$now_order_tmp = $this->get_order_detail(array('order_id'=>$order_id));
		$now_order['order_num'] = $now_order_tmp['goods_num'];
        return array(
            'pay_offline' => false,//D('Percent_rate')->pay_offline($now_order['mer_id'],'meal'), // 线下支付 D('Percent_rate')->pay_offline($now_order['mer_id'],'meal')
            'pay_merchant_balance' => true, // 商家余额
            'pay_merchant_coupon' => ($now_order['is_book_pay'] == 3 || !$now_order['uid'])? false : true, // 商家优惠券
            'pay_merchant_ownpay' => true, // 商家自有支付
            'pay_system_balance' => true, // 平台余额
            'pay_system_coupon' =>($now_order['is_book_pay'] == 3 || !$now_order['uid']) ? false : true, // 平台优惠券
            'pay_system_score' => true, // 平台积分抵现
            'discount_status' => $now_order['is_book_pay'] == 3 ? false : true, // 平台积分抵现
            'can_use_mer_union_card' => C('config.mer_card_union') == 1 ? true : false, // 是否使用商家联盟会员卡
            'order_info' => $now_order,
            'status' => $now_order['status']
        );
    }
	public function get_order_url($order_id, $is_mobile)
	{
		$now_order = $this->get_order_detail(array('order_id' => $order_id));
		if ($now_order) {
			if ($now_order['status'] == 1) {
				return C('config.site_url').'/wap.php?c=Foodshop&a=book_success&order_id=' . $order_id;
			} else {
				return C('config.site_url').'/wap.php?c=Foodshop&a=order_detail&order_id=' . $order_id;
			}
		}
		
	}
	public function after_pay($order_id, $plat_order_info = array(), $pay_type = 0)
	{
		$now_order = $this->get_order_detail(array('order_id' => $order_id), 3);
		if ($now_order['status'] > 2) {
		    return ;
		}
		$now_user = D('User')->get_user($now_order['uid']);
		$save_data = '';

		if ($now_order['status'] == 0) {
			$flag = true;
		    $now_order['status'] = 1;
		    D('Merchant_store_staff')->sendMsgFoodShop($now_order);
			$save_data = array('book_pay_time' => time(), 'price' => $now_order['book_price'], 'status' => 1, 'is_book_pay' => 1);

			// 插入必点菜
           	$this->count_price($now_order,0,true);
		
			// 20190110增加流水号
			$this->get_fetch_number($now_order);

		} else {
			$price = $this->count_price($now_order);

			// 20191111增加流水号
			$this->get_fetch_number($now_order);
			
			$payMoney = 0 ;
			$book_order = M('Plat_order')->where(array('business_id' => $order_id, 'business_type' => 'foodshop', 'paid' => 1))->select();
			
			$order_info['balance_pay'] = 0;
			$order_info['payment_money'] = 0;
			$order_info['score_deducte'] = 0;
			$order_info['score_used_count'] = 0;
			$order_info['coupon_price'] = 0;
			$order_info['merchant_balance'] = 0;
			$order_info['card_give_money'] = 0;

			foreach ($book_order as $book) {
			    $payMoney += floatval($book['total_money']);

				if($book['is_own']>0){
					$book['pay_money'] = 0;
				}

			    $order_info['balance_pay'] += $book['system_balance'];
			    $order_info['payment_money'] += $book['pay_money'];
			    $order_info['score_deducte'] += $book['system_score_money'];
			    $order_info['score_used_count'] += $book['system_score'];
			    $order_info['coupon_price'] += $book['system_coupon_price'];
			    $order_info['merchant_balance'] += $book['merchant_balance_pay'];
			    $order_info['card_give_money'] += $book['merchant_balance_give'];
			}
			$store_order = M('Store_order')->field('price')->where(array('business_id' => $order_id, 'business_type' => 'foodshop', 'paid' => 1))->select();
			$mer_table_discount = 0;
			foreach ($store_order as $storder) {
			    $payMoney += floatval($storder['price']);
			    $mer_table_discount += $storder['mer_table_discount'];
			}

			//商家桌次折扣
            if(C('config.is_open_merchant_foodshop_discount')==1&&!$mer_table_discount){
				if($now_order['table_or_vipcard_discount']==0){
                    $where = array('real_orderid'=>$now_order['real_orderid']);
                    $order = D('Foodshop_order')->where($where)->find();
                    $discount = $order['mer_table_discount'];
                    $can_discount_table_money = $order['can_discount_table_money'];
                    if($discount!=0 && $discount!=100){
                        $reduceMoney = round($can_discount_table_money - $can_discount_table_money * ($discount / 100),2);
                        $payMoney -= $reduceMoney;
                    }
				}
            }
			$save_data = array('price' => $payMoney, 'status' => 3, 'pay_type' => $pay_type);
			$save_data['total_price'] = $price + $now_order['price'];
			if (empty($now_order['phone']) && $now_order['uid']) {
			    $user = D('User')->field('phone')->where(array('uid' => $now_order['uid']))->find();
			    if ($user) {
			        $save_data['phone'] = $user['phone'];
			    }
			}
			$save_data['pay_time'] = $_SERVER['REQUEST_TIME'];
			$order_info['order_id'] = $now_order['real_orderid'];
			$order_info['orderid'] = $plat_order_info['orderid'];
			$order_info['store_id'] = $now_order['store_id'];
			$order_info['order_type'] = 'meal';
			$order_info['total_price'] = $payMoney;//$save_data['total_price'];
			$order_info['total'] = 1 ;
			$order_info['order_from'] = $now_order['order_from'];
			$order_info['is_own'] = $plat_order_info['is_own'];
			$order_info['mer_id'] = $now_order['mer_id'];
			$order_info['uid'] = $now_order['uid'];
			$order_info['desc']=$plat_order_info['union_mer_id'] ? L_('商家会员卡联盟----购买餐饮商品') : L_('购买餐饮商品');
			$order_info['score_discount_type']=$now_order['score_discount_type'];
			$order_info['union_mer_id'] = $plat_order_info['union_mer_id']; // 联盟商家id

			//【直播+短视频平台】 https://www.tapd.cn/47145533/prong/stories/view/1147145533001002522
			$order_info['is_liveshow'] = $now_order['is_liveshow'];
			$order_info['liveshow_id'] = $now_order['liveshow_id'];



			//微信派发优惠券 支付到平台 微信支付
			if($plat_order_info['is_own']==0 && $plat_order_info['pay_type']=='weixin' && $order_info['payment_money']>=C('config.weixin_send_money')){
				D('System_coupon')->weixin_send($order_info['payment_money'],$order_info['uid']);
			}

			// 加积分

			if(C('config.open_extra_price')==1){
				$order_info['extra_price'] = $now_order['extra_price'] ;
				$score = D('Percent_rate')->get_extra_money($order_info);
					
				if($score>0){
					D('User')->add_score($now_order['uid'], $score,L_("购买X1商品获得X2",array("X1" => C('config.meal_alias_name'),"X2" => C('config.score_name')))); 

				}
			}else if($this->config['add_score_by_system_commission']==0 && C('config.add_score_by_percent')==0 && (C('config.open_score_discount')==0 || $plat_order_info['score_discount_type']!=2)){
				if(C('config.open_score_get_percent')==1){
					$score_get = C('config.score_get_percent')/100;
				}else{
					
					$score_get = C('config.user_score_get');
				}
				// if(C('config.open_score_fenrun')){
					if($order_info['is_own'] && C('config.user_own_pay_get_score')!=1){
						$order_info['payment_money'] = 0;
					}
					$now_merchant = D('Merchant')->get_info($now_order['mer_id']);
					if($now_merchant['score_get_percent']>=0){
						$score_get = $now_merchant['score_get_percent']/100;
					}
				// }
				// 
				$score_get = D('Percent_rate')->get_user_add_score_times($score_get,$order_info['uid']);

				D('User')->add_score($now_order['uid'], (($order_info['balance_pay']+$order_info['payment_money']) * $score_get), L_("购买餐饮商品获得X1",array("X1" => C('config.score_name')))); 
				D('Scroll_msg')->add_msg('foodshop',$now_user['uid'],L_('用户x1于x2购买x3成功获得x4',array('x1'=>str_replace_name($now_user['nickname']),'x2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),'x3'=>C('config.meal_alias_name'),'x4'=>C('config.score_name'))));
			}
			// 加积分
		}
        if ($now_order['plat_discount'] > 0) {
            $now_order['plat_discount'] = 100;
            $oldprice = $this->count_price($now_order);
            $diffPrice = floatval($oldprice - $price);
            $save_data['plat_discount_price'] = $diffPrice;
        }
			$save_data['payment_method'] = $plat_order_info['pay_type'] ? $plat_order_info['pay_type'] :'';
        if ($this->where(array('order_id' => $order_id))->save($save_data)) {
            //运行桌台折扣计算
            if(C('config.is_open_merchant_foodshop_discount')==1){
            	if(isset($flag)&&$flag){
            		D('Merchant_store_foodshop')->getTableDiscount($now_order['mer_id'],$now_order['store_id'],$now_order['order_id']);
                    $this->notice_table_discount($now_order['mer_id'],$now_order['store_id'],$now_order['book_time']);
				}
            }
            if ($now_order['plat_discount'] > 0) {
                $logData = $now_order;
                $logData['plat_discount_price'] = $diffPrice;
                D('Merchant_discount_log')->saveLog($logData, 1);
            }

            // 定制 不绑定桌台 支付后清空购物车 修改商品库存
            if ($now_order['unbind_table']==1) {
            	// 清空购物车
	            D('Foodshop_cart')->where(['uid'=>$now_order['uid'],'store_id'=>$now_order['store_id']])->delete();

 				// 修改商品库存
 				fdump($now_order['info'],'after_pay',1);
	            foreach ($now_order['info'] as $key => $value) {
	            	D('Foodshop_goods')->update_stock($value);
	            }
            }

            /* 增加系统订单记录 2019/01/29 */
			$this->add_system_order($order_id,2);

// 		    $goods = D('Foodshop_order_detail')->field(true)->where(array('order_id' => $order_id))->select();
// 		    $foodShopObj = D("Foodshop_goods");
// 		    foreach ($goods as $gr) {
// 		        $foodShopObj->update_stock($gr['goods_id'], $gr['num'], $gr['spec_id']);
// 		    }
			D('Scroll_msg')->add_msg('foodshop',$now_user['uid'],L_('用户x1于x2购买x3成功',array('x1'=>str_replace_name($now_user['nickname']),'x2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),'x3'=>C('config.meal_alias_name'))));

			if(C('config.open_extra_price')==1&&$now_order['status'] != 0){
				$total_money_tmp=($order_info['balance_pay']+$order_info['merchant_balance']+$order_info['pay_money']).C('config.Currency_txt').'+'.$order_info['score_used_count'].C('config.score_name');

			}else{
				$total_money_tmp =floatval($save_data['price']) . C('config.Currency_txt');
			}
			if ($now_user = D('User')->field(true)->where(array('uid' => $now_order['uid']))->find()) {
				if ($now_user['openid']) {
					$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
					if ($now_order['status'] == 0) {
						$href = C('config.site_url').'/wap.php?c=Foodshop&a=book_success&order_id='. $order_id;
						$model->sendTempMsg('OPENTM201752540', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' => C('config.meal_alias_name').L_('提醒'), 'keyword2' => $now_order['real_orderid'], 'keyword1' => $now_order['table_name'], 'keyword3' => $total_money_tmp, 'keyword4' => date('Y.m.d H:i'), 'remark' => '预订成功,期待您的到来!'), $now_order['mer_id']);
					} else {
						$href = C('config.site_url').'/wap.php?c=Foodshop&a=order_detail&order_id='. $order_id;
						$model->sendTempMsg('OPENTM201752540', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' => C('config.meal_alias_name').L_('提醒'), 'keyword2' => $now_order['real_orderid'], 'keyword1' => $now_order['info'][0]['name'] . L_('...等菜品'), 'keyword3' => $total_money_tmp, 'keyword4' => date('Y.m.d H:i'), 'remark' => '买单成功,欢迎下次光临!'), $now_order['mer_id']);
					}
				}
				if(C('config.open_score_pay_back') && C('config.system_take_spread_percent')>0 ){
					$spread_total_money = $plat_order_info['system_balance'] +$plat_order_info['pay_money']+$plat_order_info['system_score_money']+$plat_order_info['system_coupon_price']+$plat_order_info['merchant_balance_pay'];
					$percent = $percent = D('Percent_rate')->get_percent($now_order['mer_id'],$now_order['order_type'],$spread_total_money,'',$now_order['store_id'], true);
					$spread_total_money = $spread_total_money*$percent/100;
				}else{
					$spread_total_money = $plat_order_info['system_balance'] +$plat_order_info['pay_money'];

				}
				
				if(C('config.add_spread_by_system_commission')){// 根据平台抽成获得佣金	
					// 获取平台抽成金额								
					$order_info['get_system_take_money'] = true;
                    $spread_total_money =  D('SystemBill')->bill_method($order_info['is_own'],$order_info);
				}

                $userMod = new UserModel();
				if(!empty($now_user['openid'])&&C('config.open_user_spread') && (!C('config.open_spread_appoint_user') || ($now_user['can_spread']==1 && !C('config.open_spread_only_once'))) && (C('config.spread_money_limit')==0 || C('config.spread_money_limit')<=$spread_total_money) && !C('config.open_spread_rand')){
					//上级分享佣金
					$spread_rate = D('Percent_rate')->get_user_spread_rate($now_order['mer_id'],'meal',0,$now_order);
					$spread_users[]=$now_user['uid'];
						$spread_where['_string'] = "uid = '{$now_user['uid']}'";
					$now_user_spread = D('User_spread')->where($spread_where)->find();

                    //判断推广用户是否可以获得佣金
                    if ($now_user_spread && !($userMod->canSpread($now_user_spread['spread_uid']))) {
                        $now_user_spread = [];
                    }

					$href = C('config.site_url') . '/wap.php?g=Wap&c=My&a=spread_list&status=-1';
					$open_extra_price = C('config.open_extra_price');
					if($plat_order_info['is_own']  && C('config.own_pay_spread')==0){
						$data_group_order['payment_money']=0;
					}
					if(!empty($now_user_spread)){
						$spread_user = D('User')->get_user($now_user_spread['spread_uid']);
						//$user_spread_rate = $update_group['spread_rate'] > 0 ? $update_group['spread_rate'] : C('config.user_spread_rate');
						$user_spread_rate = $spread_rate['first_rate'];
						if($spread_user && $user_spread_rate&&!in_array($spread_user['uid'],$spread_users)){
							$spread_money = round($spread_total_money * $user_spread_rate / 100, 2);
							$spread_data = array('uid'=>$spread_user['uid'],'spread_uid'=>0,'get_uid'=>$now_user['uid'],'money'=>$spread_money,'order_type'=>'meal','order_id'=>$now_order['order_id'],'third_id'=>$now_order['store_id'],'add_time'=>$_SERVER['REQUEST_TIME']);
							if($spread_user['spread_change_uid']!=0){
								$spread_data['change_uid'] = 	$spread_user['spread_change_uid'];
							}
                            // 处理长订单id
                            if ($now_order['real_orderid']) {
                                $spread_data['real_orderid'] = $now_order['real_orderid'];
                            }
							D('User_spread_list')->data($spread_data)->add();

							$buy_user = D('User')->get_user($now_user_spread['uid'],'uid');
							if($spread_money>0){
								if($open_extra_price){
									$money_name = C('config.extra_price_alias_name');
								}else{
									$money_name = L_('佣金');
								}
								$model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $spread_user['openid'], 'first' => L_('x1通过您的分享购买商品，验证消费后您将获得x2。',array('x1'=>$buy_user['nickname'],'x2'=>$money_name)), 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $now_order['mer_id']);
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

								$spread_data=array('uid' => $second_user['uid'], 'spread_uid' => $spread_user['uid'], 'get_uid' => $now_user['uid'], 'money' => $spread_money, 'order_type' => 'meal', 'order_id' => $now_order['order_id'], 'third_id' => $now_order['store_id'], 'add_time' => $_SERVER['REQUEST_TIME']);
								if($spread_user['spread_change_uid']!=0){
									$spread_data['change_uid'] = 	$second_user['spread_change_uid'];
								}
                                // 处理长订单id
                                if ($now_order['real_orderid']) {
                                    $spread_data['real_orderid'] = $now_order['real_orderid'];
                                }
								D('User_spread_list')->data($spread_data)->add();
                                    $sec_where['_string'] = "uid = '{$second_user_spread['uid']}'";
                                    $sec_user = M('User')->where($sec_where)->find();
                                
								if($spread_money>0) {
									$model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $second_user['openid'], 'first' => L_('x1的子用户x2通过您的分享购买商品，验证消费后您将获得佣金。',array('x1'=>$sec_user['nickname'],'x2'=>$buy_user['nickname'])), 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $now_order['mer_id']);
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
									$spread_data = array('uid' => $first_spread_user['uid'], 'spread_uid' => $second_user['uid'], 'get_uid' => $now_user['uid'], 'money' => $spread_money, 'order_type' => 'meal', 'order_id' => $now_order['order_id'], 'third_id' => $now_order['store_id'], 'add_time' => $_SERVER['REQUEST_TIME']);
									if($spread_user['spread_change_uid']!=0){
										$spread_data['change_uid'] = 	$first_spread_user['spread_change_uid'];
									}
                                    // 处理长订单id
                                    if ($now_order['real_orderid']) {
                                        $spread_data['real_orderid'] = $now_order['real_orderid'];
                                    }
									D('User_spread_list')->data($spread_data)->add();

                                        $fir_where['_string'] = "uid = '{$first_user_spread['uid']}'";
                                        $fir_user = M('User')->where($fir_where)->find();
                                    
									if($spread_money>0) {
										$model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $first_spread_user['openid'], 'first' =>L_('x1的子用户的子用户x2通过您的分享消购买商品，验证消费后您将获得佣金。',array('x1'=>$fir_user['nickname'],'x2'=>$buy_user['nickname'])), 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), $now_order['mer_id']);
									}
									// D('User')->add_money($first_spread_user['uid'],$spread_money,'子用户推广用户 '.$now_user['nickname'].' 购买ID为 '.$now_order['group_id'].' 的商品 获得的佣金');
								}
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
						    D('Percent_rate')->add_spread_jifen_log($param,'meal',$now_order['store_id']);
						}
					}
				}elseif(!empty($now_user['openid'])&&C('config.open_spread_rand')){
		            /* 开启三级推广随机一级推广奖励功能，唯一次奖励 */
					//一级推广用户
					$spread_res = D('User_spread')->spread_rand_only_once('meal',$now_user,$now_order);
		        }elseif(!empty($now_user['openid'])&&C('config.open_spread_appoint_user') && $now_user['can_spread']==1 && C('config.open_spread_only_once')&&(C('config.spread_money_limit')==0 || C('config.spread_money_limit')<=$spread_total_money)){
		            /* 开启指定用户享受三级推广功能且开启唯一次奖励 */
					//一级推广用户
					$spread_res = D('User_spread')->spread_user_only_once('meal',$spread_total_money,$now_user,$now_order);
		        }
			}
			fdump($order_info,'1111');
			// 对账	
			$order_info['get_system_take_money'] = false;
			$res = D('SystemBill')->bill_method($order_info['is_own'],$order_info);

			//商家推广分佣
			D('Merchant_spread')->add_spread_list($now_order,$now_user,'meal',L_("X1用户购买X2商品获得佣金",array("X1" => $now_user['nickname'],"X2" => C('config.meal_alias_name'))));
			
			//增加粉丝 5 group 6 shop 7 meal 8 appoint 9 store
			D('Merchant')->saverelation($now_user['openid'], $now_order['mer_id'], 7);
				
			//小票打印
			$printHaddle = new PrintHaddle();
			if ($save_data['status'] == 3) {
				// 是否启用餐饮打印模式 新
				if (C('config.open_foodshop_print_type')) {
				    $printHaddle->printit($now_order['order_id'], 'foodshop_order', 7, 1);// 结账单
				}else{
				    $printHaddle->printit($now_order['order_id'], 'foodshop_order', 1);// 结账单
				}
			} else {
				// 是否启用餐饮打印模式 新
				if (C('config.open_foodshop_print_type')) {
				    $printHaddle->printit($now_order['order_id'], 'foodshop_order', 6, 1);// 预结单
				}else{
				    $printHaddle->printit($now_order['order_id'], 'foodshop_order', 0); // 预结单
				}
			}
			fdump('account_type='.$now_order['account_type'],'sendMsgFoodShop',1);
			//先结算后下单模式下 给店员发消息
            if($now_order['account_type']==2){
                D('Merchant_store_staff')->sendMsgFoodShop($now_order);
				$menus=D('Foodshop_order_detail')->where(array('order_id'=>$now_order['order_id']))->select();
				$printHaddle->foodshop_menu_new($now_order['order_id'],$menus,1);
            }
			
//     		$store = D('Merchant_store')->field(true)->where(array('store_id' => $now_order['store_id']))->find();
//     		$msg = ArrayToStr::array_to_str($now_order['order_id'], 'foodshop_order');
//     		$op = new orderPrint(C('config.print_server_key'), C('config.print_server_topdomain'));
//     		$op->printit($store['mer_id'], $store['store_id'], $msg, 1);
    		
//     		$str_format = ArrayToStr::print_format($now_order['order_id'], 'foodshop_order');
//             foreach ($str_format as $print_id => $print_msg) {
//                 if ($print_id) {
//                     foreach ($print_msg as $msg) {
//                         $op->printit($store['mer_id'], $store['store_id'], $msg, 1, $print_id);
//                     }
//                 }
//             }
			if ($save_data['status'] == 3) {//通知店员
			    $now_order['status'] = 3;
                D('Merchant_store_staff')->sendMsgFoodShop($now_order);
                // 桌台解锁
                M('Foodshop_table')->where(array('store_id'=>$now_order['store_id'],'id'=>$now_order['table_id']))->setField('status',0);

			}
			//短信提醒
			$sms_data = array('mer_id' => $now_order['mer_id'], 'store_id' => $now_order['mer_id'], 'type' => 'food');
			if (C('config.sms_success_order') == 1 || C('config.sms_success_order') == 3) {
				if (empty($now_order['phone'])) {
					$now_order['phone'] = isset($now_user['phone']) && $now_user['phone'] ? $now_user['phone'] : '';
				}
				$sms_data['uid'] = $now_order['uid'];
				$sms_data['mobile'] = $now_order['phone'];
				$sms_data['sendto'] = 'user';
				
				if ($now_order['status'] == 1) {
					$sms_data['content'] = L_('您在x1时，预订了x2的x3人，已成功生成订单，订单号：x4',array('x1'=>date('Y-m-d H:i:s'),'x2'=>$store['name'],'x3'=>$now_order['table_type_name'],'x4'=>$now_order['real_orderid']));
				} else {
					// $sms_data['content'] = L_('您预订的x1人的订单(订单号：x2)已经完成支付。欢迎下次光临！',array('x1'=>$store['name'] . $now_order['table_type_name'] . $now_order['book_num'],'x2'=>$now_order['real_orderid']));

					$sms_data['content'] = L_('您预订的x1订单(订单号：x2)已经完成支付。欢迎下次光临！',array('x1'=>C('config.meal_alias_name'),'x2'=>$now_order['real_orderid']));
				}
				$sms_data['mobile'] && Sms::sendSms($sms_data);
			}
			
			if (C('config.sms_success_order') == 2 || C('config.sms_success_order') == 3) {
				$sms_data['uid'] = 0;
				$sms_data['mobile'] = $store['phone'];
				$sms_data['sendto'] = 'merchant';

				if ($now_order['status'] == 1) {
					$sms_data['content'] = L_('顾客在x1时，预订了x2的x3人，已成功生成订单，订单号：x4',array('x1'=>date('Y-m-d H:i:s'),'x2'=>$store['name'],'x3'=>$now_order['table_type_name'] . $now_order['book_num'],'x4'=>$now_order['real_orderid']));
				} else {
					$sms_data['content'] = L_('顾客预订的x1的x2人的订单(订单号：x3),在x4时已经完成了支付！',array('x1'=>$store['name'],'x2'=>$now_order['table_type_name'] . $now_order['book_num'],'x3'=>$now_order['real_orderid'],'x4'=>date('Y-m-d H:i:s')));
				}
				$sms_data['mobile'] && Sms::sendSms($sms_data);
			}
		}
		//D('Card_new')->add_user_money($now_order['mer_id'],$now_order['uid'],$now_order['money'],$now_order['give_money'],$now_order['give_score'],'在线充值'.intval($now_order['money']).C('config.Currency_txt'),'在线充值'.intval($now_order['money']).C('config.Currency_txt').'赠送');
	}

	//退款后发送 短信、模板消息、减库存 等等
	public function afert_refund($now_order){
		//发模板消息.
		if($this->where(array('order_id'=>$now_order['order_id']))->setField('status',5)) {
            //运行桌台折扣计算
            if(C('config.is_open_merchant_foodshop_discount')==1){
                $this->notice_table_discount($now_order['mer_id'],$now_order['store_id'],$now_order['book_time']);
            }
			$order_id = $now_order['order_id'];
			$href = C('config.site_url') . '/wap.php?c=Foodshop&a=book_success&order_id=' . $order_id;
			if ($now_user = D('User')->field(true)->where(array('uid' => $now_order['uid']))->find()) {
				if ($now_user['openid']) {
					$now_store = M('Merchant_store')->where(array('store_id'=>$now_order['store_id']))->find();
					$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));

					$model->sendTempMsg('TM00017', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' => $now_store['name'].C('config.meal_alias_name') . L_('订金取消提醒'), 'OrderSn' => $now_order['real_orderid'], 'OrderStatus' =>L_('订单已于 X1 取消成功',date('Y.m.d H:i:s')), 'remark' => L_('感谢您的使用欢迎您继续光临本店')), $now_order['mer_id']);

				}
				//小票打印
				$printHaddle = new PrintHaddle();
				$printHaddle->printit($now_order['order_id'], 'foodshop_order', 3);
				
// 				$store = D('Merchant_store')->field(true)->where(array('store_id' => $now_order['store_id']))->find();
// 				$msg = ArrayToStr::array_to_str($now_order['order_id'], 'foodshop_order');
// 				$op = new orderPrint(C('config.print_server_key'), C('config.print_server_topdomain'));
// 				$op->printit($store['mer_id'], $store['store_id'], $msg, 1);
				
// 				$str_format = ArrayToStr::print_format($now_order['order_id'], 'foodshop_order');
//                 foreach ($str_format as $print_id => $print_msg) {
//                     if ($print_id) {
//                         foreach ($print_msg as $msg) {
//                             $op->printit($store['mer_id'], $store['store_id'], $msg, 1, $print_id);
//                         }
//                     }
//                 }
				
				//短信提醒
				$sms_data = array('mer_id' => $now_order['mer_id'], 'store_id' => $now_order['mer_id'], 'type' => 'food');
				if (C('config.sms_cancel_order') == 1 || C('config.sms_cancel_order') == 3) {
					if (empty($now_order['phone'])) {
						$now_order['phone'] = isset($now_user['phone']) && $now_user['phone'] ? $now_user['phone'] : '';
					}
					$sms_data['uid'] = $now_order['uid'];
					$sms_data['mobile'] = $now_order['phone'];
					$sms_data['sendto'] = 'user';
					
					$sms_data['content'] = L_('您预订的x1人的订单(订单号：x2),在x3时取消成功！',array('x1'=>$store['name'] . $now_order['table_type_name'] . $now_order['book_num'],'x2'=>$now_order['real_orderid'],'x3'=>date('Y-m-d H:i:s')));
					Sms::sendSms($sms_data);
				}
				
				if (C('config.sms_cancel_order') == 2 || C('config.sms_cancel_order') == 3) {
					$sms_data['uid'] = 0;
					$sms_data['mobile'] = $store['phone'];
					$sms_data['sendto'] = 'merchant';
					$sms_data['content'] = L_('顾客预订的x1的x2人的订单(订单号：x3),在x4时取消订单！',array('x1'=>$store['name'],'x2'=>$now_order['table_type_name'] . $now_order['book_num'],'x3'=>$now_order['real_orderid'],'x4'=>date('Y-m-d H:i:s')));
					Sms::sendSms($sms_data);
				}
			}
			return array('error_code'=>true,'msg'=>L_('订单取消成功！'),'url'=>$href);
		}else{
			return array('error_code'=>true,'msg'=>L_('订单状态更新失败！'));
		}
	}

	public function count_price($order, $returnType = 0, $save_must = false)
	{
		$price = 0;
		$totalDiscount = 0;
		$mer_id = $order['mer_id'];
		$plat_discount = $order['plat_discount'];
		$mer_table_discount = $order['mer_table_discount'];
		if ($plat_discount <= 0 || $plat_discount >= 100) {
		    $plat_discount = 100;
		}
		//套餐价格
		if ($order['package_ids']) {
			$package_ids = json_decode($order['package_ids'], true);
			$packages = D('Foodshop_goods_package')->field(true)->where(array('in' => array('id', $package_ids)))->select();
			foreach ($package_ids as $pid) {
				foreach ($packages as $p) {
					if ($pid == $p['id']) {
					    $price += $p['price'] * $plat_discount * 0.01;
					    $totalDiscount += $p['price'] * $plat_discount * 0.01;
					}
				}
			}
		}
		//菜品总价
		$must_goods = array();
		foreach ($order['info'] as $info) {
		    if (empty($info['package_id']) && empty($info['is_must'])) {
//			    if ($info['is_discount']) {
//                    $price += $info['price'] * $info['num'] * $plat_discount * 0.01;
//                    $totalDiscount += $info['price'] * $info['num'] * $plat_discount * 0.01;
//                }else
				if($info['is_table_discount']){
                    $price += $info['price'] * $info['num'];
                    $totalDiscount += $info['price'] * $info['num'];
				}else{
			        $price += $info['price'] * $info['num'];
			    }
			} elseif ($info['ishide']) {//计算修改后的套餐存储方式
//			    if ($info['is_discount']) {
//			        $price += $info['price'] * $info['num'] * $plat_discount * 0.01;
//			        $totalDiscount += $info['price'] * $info['num'] * $plat_discount * 0.01;
//			    }else
				if($info['is_table_discount']){
                    $price += $info['price'] * $info['num'];
                    $totalDiscount += $info['price'] * $info['num'];
                } else {
			        $price += $info['price'] * $info['num'];
			    }
			}
			if ($info['is_must']) {
				$must_goods[$info['goods_id']] = array('id' => $info['id'], 'num' => $info['num']);
			}
		}

		//必点菜的总价统计
		
		$goods_list = D('Foodshop_goods')->field(true)->where(array('store_id' => $order['store_id'], 'status' => 1, 'is_must' => 1))->select();
		$now_time = time();
		$save_data = array();
		foreach ($goods_list as $goods) {			
			if (isset($must_goods[$goods['goods_id']]['id']) && $must_goods[$goods['goods_id']]['id']) {
				if ($goods['operate_must_goods']) { //开启必点菜店员是可操作时
					// 计算价格 
					if($goods['is_table_discount']){
		                $price += $goods['price'] * $must_goods[$goods['goods_id']]['num'];
		                $totalDiscount += $goods['price'] * $must_goods[$goods['goods_id']]['num'];
		            } else {
				        $price += $goods['price'] * $must_goods[$goods['goods_id']]['num'];
				    }
				}else{
					if ($order['book_num'] > 0) {
						if ($must_goods[$goods['goods_id']]['num'] != $order['book_num'] && !$goods['operate_must_goods']) {
							D('Foodshop_order_detail')->where(array('id' => $must_goods[$goods['goods_id']]['id']))->save(array('num' => $order['book_num']));
						}
						// 计算价格
						if($goods['is_table_discount']){
			                $price += $goods['price'] * $order['book_num'];
			                $totalDiscount += $goods['price'] * $order['book_num'];
			            } else {
					        $price += $goods['price'] * $order['book_num'];
					    }
					}
				}
				unset($must_goods[$goods['goods_id']]);
			} else {
				if (!$goods['operate_must_goods'] || $save_must) { //开启必点菜店员是可操作时
					if ($goods['operate_must_goods']) {
						// 必点菜可操作，数量默认为1
						$save_data[] =$tmp_must_goods= array('goods_id' => $goods['goods_id'],
							'num' => 1, 
							'price' => $goods['price'], 
							'order_id' => $order['order_id'], 
							'store_id' => $order['store_id'], 
							'name' => $goods['name'],
							'unit' => $goods['unit'],
							'number' => $goods['number'],
							'operate_must_goods' => intval($goods['operate_must_goods']),
							'is_must' => 1,
							'create_time' => $now_time,
							'sort_id'=>$goods['sort_id']
						);

					}else{	
						// 必点菜不可操作，数量默认为预订人数数量
						if ($order['book_num'] > 0) {
							// 计算价格
							if($goods['is_table_discount']){
				                $price += $goods['price'] * $order['book_num'];
				                $totalDiscount += $goods['price'] * $order['book_num'];
				            } else {
						        $price += $goods['price'] * $order['book_num'];
						    }
						    $save_data[] =$tmp_must_goods= array('goods_id' => $goods['goods_id'],
								'num' => $order['book_num'], 
								'price' => $goods['price'], 
								'order_id' => $order['order_id'], 
								'store_id' => $order['store_id'], 
								'name' => $goods['name'],
								'unit' => $goods['unit'],
								'number' => $goods['number'],
								'operate_must_goods' => intval($goods['operate_must_goods']),
								'is_must' => 1,
								'create_time' => $now_time,
								'sort_id'=>$goods['sort_id']
							);
						}
					}	
					
				}
				
				if(!$returnType){
					D('Foodshop_goods')->update_stock($tmp_must_goods);
				}

			}
		}

		if ($must_goods) {
			$ids = array();
			foreach ($must_goods as $id => $v) {
				$ids[] = $v['id'];
			}

			if ($ids) {
				D('Foodshop_order_detail')->where(array('id' => array('in', $ids)))->delete();
			}
		}
		if(!$returnType){
			if ($save_data) { //将必点菜插入订单详情中去				
            	D('Foodshop_order_detail')->addAll($save_data);
			}
		}
		//减去已支付的预订金
		if ($order['status']) {
			$price -= $order['book_price'];
		}
		$price += $order['table_service_payment'];
		$price = max(0, $price);
		if ($returnType) {
		    return $totalDiscount;
		} else {
		    return $price;
		}
	}


	public function count_extra_price($order)
	{
		$price = 0;
		$order_detail = M('Foodshop_order_detail')->where(array('order_id'=>$order['order_id']))->select();
		foreach ($order_detail as $v) {
			if($v['extra_price']>0){
				$price+=$v['extra_price']*$v['num'];
			}
		}
		return $price;
	}
	
	/**
	 * @param array $where
	 * @param string $order
	 * @param int $from 0:用户的个人中心订单列表，1:商家中心的订单列表 2:打包app店员中心
	 * @return array
	 */
	public function get_order_list($where = array(), $order = 'order_id DESC', $from = 0)
	{

		// 必须绑定桌台或者不绑定桌台已支付才显示 (table_id>0 OR status=3)
		$where['_string'] = '((account_type=1 OR order_from <> 1) OR (account_type=2 AND `status`>=3)) AND (unbind_table=0 OR status>=3)';
		$count = $this->where($where)->count();
		if ($from == 0) {
// 			import('@.ORG.wap_group_page');
// 			$p = new Page($count, 20, 'p');
// 			$list = $this->where($where)->order($order)->limit($p->firstRow . ',' . $p->listRows)->select();
			$list = $this->where($where)->order($order)->select();
			return array('order_list' => $list);
		} elseif ($from == 1) {
			import('@.ORG.merchant_page');
			$p = new Page($count, 20);
			$list = $this->where($where)->order($order)->limit($p->firstRow . ',' . $p->listRows)->select();
		} elseif ($from == 2) {
			import('@.ORG.merchant_page');
			$_GET['page'] = isset($_POST['page']) ? intval($_POST['page']) : 1;
			$p = new Page($count, 10);
			$list = $this->where($where)->order($order)->limit($p->firstRow . ',' . $p->listRows)->select();
			$order_list = array();
			foreach ($list as $r) {
				$thisorder = $this->get_order_detail(null, 2, $r);
				$price = $this->count_price($thisorder);
				$new_order = $this->get_order_detail(null, 3, $r);
				// $new_order['totalPrice'] = floatval($price + $thisorder['book_price']);
				$new_order['totalPrice'] = round($price,2);
//				$new_order['totalPrice'] = round($price + $thisorder['book_price'],2);
				$new_order['balance_pay'] = round($new_order['balance_pay'],2);
				$order_list[] = $new_order;
				
			}
			return array('order_list' => $order_list, 'count' => $count, 'totalPage' => $p->totalPage);
		}
		$table_types = array();
		$tids = array();
		foreach ($list as $row) {
			if (!in_array($row['table_id'], $tids)) {
				$tids[] = $row['table_id'];
			}
			if (!in_array($row['table_type'], $table_types)) {
				$table_types[] = $row['table_type'];
			}
		}
		$type_list = array();
		if ($table_types) {
			$temp_type_list = M('Foodshop_table_type')->field(true)->where(array('id' => array('in', $table_types)))->select();
			foreach ($temp_type_list as $tmp) {
				$type_list[$tmp['id']] = $tmp;
			}
		}
		$table_list = array();
		if ($tids) {
			$temp_table_list = M('Foodshop_table')->field(true)->where(array('id' => array('in', $tids)))->select();
			foreach ($temp_table_list as $temp) {
				$table_list[$temp['id']] = $temp;
			}
		}
		foreach ($list as &$val) {
			$val['table_type_name'] = isset($type_list[$val['table_type']]) ? $type_list[$val['table_type']]['name'] . '(' . $type_list[$val['table_type']]['min_people'] . '-' . $type_list[$val['table_type']]['max_people'] . '人)' : '';
			$val['table_name'] = isset($table_list[$val['table_id']]) ? $table_list[$val['table_id']]['name'] : '';
			$val['show_status'] = $this->status_list[$val['status']];
		}
		return array('order_list' => $list, 'pagebar' => $p->show());
	}
	
	
	

	/**
	 * @param array $where
	 * @param int $show_type 0:只查找订单，1：查找包括桌台信息，2：包括菜品信息，3：包括支付信息
	 * @return boolean|mixed
	 */
	public function get_order_detail($where, $show_type = 2, $order = array())
	{
		if (!empty($where)) {
			$order = $this->field(true)->where($where)->find();
		}
		if (empty($order)) return false;
		
		$order['date'] = date('Y-m-d H:i:s', $order['create_time']);
		$order['show_status'] = $this->status_list[$order['status']];
		$order['status_str'] = $this->status_list[$order['status']];
		$order['note'] = $order['note'] ? $order['note'] : '';
		if ($show_type == 0) return $order;
		
		$order['table_name'] = '';
		$order['table_type_name'] = '';
		if ($order['table_id']) {
			$table = M('Foodshop_table')->field(true)->where(array('id' => $order['table_id']))->find();
			$order['table_name'] = $table ? $table['name'] : '';
		}
		if ($table) {
			if ($table['tid']!=$order['table_type']) {
				$this->where(array('order_id'=>$order['order_id']))->save(array('table_type'=>$table['tid']));
			}
			$type = M('Foodshop_table_type')->field(true)->where(array('id' => $table['tid']))->find();
			$order['table_type_name'] = $type ? $type['name'] : '';
			$order['min_max'] = $type ? '(' . $type['min_people'] . '-' . $type['max_people'] . '人)' : '';
		}
		$order['book_time_show'] = $order['book_time'] ? date('m月d日 H:i', $order['book_time']) : '--';
		$order['book_time_packapp'] = $order['book_time'] ? date('Y-m-d H:i', $order['book_time']) : '--';
		if ($show_type == 1) return $order;
		$goods_num = 0; // 已点菜品总数
		$order['info'] = D('Foodshop_order_detail')->field(true)->where(array('order_id' => $order['order_id']))->select();
		foreach ($order['info'] as &$r) {
			$r['num'] = floatval($r['num']);
			$r['price'] = floatval($r['price']);
			$goods_num += $r['num'];
		}
		$order['goods_num'] = $goods_num; // 已点菜品总数
		$order['info_temp'] = D('Foodshop_order_temp')->field(true)->where(array('order_id' => $order['order_id']))->select();
		foreach ($order['info_temp'] as &$r) {
			$r['num'] = floatval($r['num']);
			$r['price'] = floatval($r['price']);
		}
		if ($show_type == 2) return $order;

		$result = D('Plat_order')->field(true)->where(array('business_id' => $order['order_id'], 'business_type' => 'foodshop', 'paid' => 1))->order('order_id ASC')->select();
		
		
		$order['merchant_reduce'] = 0;//店铺优惠
		$order['balance_reduce'] = 0;//平台优惠
		$order['use_ecard_price'] = 0;//使用平台E卡抵扣金额
		$order['score_used_count'] = 0;//使用积分数
		$order['score_deducte'] = 0;//积分抵用的金额
		$order['merchant_balance'] = 0;//商家余额
		$order['balance_pay'] = 0;//平台余额
		$order['card_id'] = 0;//会员卡
		$order['card_price'] = 0;//店铺优惠券金额
		$order['coupon_id'] = 0;//平台优惠券
		$order['coupon_price'] = 0;//平台优惠券金额
		$order['merchant_balance_give'] = 0;//商家赠送余额
		$order['pay_money'] = 0;//在线支付的金额

		foreach ($result as $vo) {
		    $order['merchant_reduce'] += $vo['merchant_discount_money'];//店铺优惠
		    $order['balance_reduce'] = 0;//平台优惠		    
		    $order['use_ecard_price'] += $vo['use_ecard_price'];//使用平台E卡抵扣金额
		    $order['score_used_count'] += $vo['system_score'];//使用积分数
		    $order['score_deducte'] += $vo['system_score_money'];//积分抵用的金额
		    $order['merchant_balance'] += $vo['merchant_balance_pay'];//商家余额
		    $order['merchant_balance_give'] += $vo['merchant_balance_give'];//商家余额
		    $order['balance_pay'] += $vo['system_balance'];//平台余额
		    $order['card_id'] || $order['card_id'] = $vo['merchant_coupon_id'];//会员卡
		    $order['card_price'] += $vo['merchant_coupon_price'];//店铺优惠券金额
		    $order['coupon_id'] || $order['coupon_id'] = $vo['system_coupon_id'];//平台优惠券
		    $order['coupon_price'] += $vo['system_coupon_price'];//平台优惠券金额
		    $order['pay_money'] += $vo['pay_money'];
		}
		if (count($result) == 1) {
			if ($order['status'] < 3) {
				$order['book_pay_type'] = D('Pay')->get_pay_name($result[0]['pay_type'], $result[0]['is_mobile_pay'], $result[0]['paid']);
				$order['book_pay_time'] = $result[0]['pay_time'];
				$order['online_pay'] = $result[0]['pay_type'];
			} elseif ($order['pay_type'] == 1) {//店员买单
				if ($store_order = D('Store_order')->field(true)->where(array('business_type' => 'foodshop', 'business_id' => $order['order_id'], 'paid' => 1))->find()) {
					if ($store_order['pay_type'] == 'offline') { // 线下支付
						if ($store_order['offline_pay'] && ($store_pay = D('Store_pay')->field('name')->where(array('id' => $store_order['offline_pay']))->find())) {
							$order['pay_type'] = L_("线下支付【X1】",array("X1" => $store_pay['name'])); 
						} else {
							$order['pay_type'] = L_('线下支付');
						}
						$order['offline_pay'] = $store_order['offline_pay'];
					} else {
						$order['pay_type'] = D('Pay')->get_pay_name($store_order['pay_type'], 0, 1);
						$order['online_pay'] = $store_order['pay_type'];
					}
					$order['pay_time'] = $store_order['pay_time'];
					$order['book_pay_type'] = D('Pay')->get_pay_name($result[0]['pay_type'], $result[0]['is_mobile_pay'], $result[0]['paid']);
					$order['book_pay_time'] = $result[0]['pay_time'];
				} else {
					$order['pay_type'] = D('Pay')->get_pay_name($result[0]['pay_type'], $result[0]['is_mobile_pay'], $result[0]['paid']);
					$order['pay_time'] = $result[0]['pay_time'];
					$order['online_pay'] = $result[0]['pay_type'];
				}
			}else {
				$order['pay_type'] = D('Pay')->get_pay_name($result[0]['pay_type'], $result[0]['is_mobile_pay'], $result[0]['paid']);
				$order['pay_time'] = $result[0]['pay_time'];
				$order['online_pay'] = $result[0]['pay_type'];
			}
		} elseif (count($result) > 1) {
			$order['book_pay_type'] = D('Pay')->get_pay_name($result[0]['pay_type'], $result[0]['is_mobile_pay'], $result[0]['paid']);
			$order['book_pay_time'] = $result[0]['pay_time'];
			$order['pay_type'] = D('Pay')->get_pay_name($result[1]['pay_type'], $result[1]['is_mobile_pay'], $result[1]['paid']);
			$order['pay_time'] = $result[1]['pay_time'];
			$order['online_pay'] = $result[1]['pay_type'];
			
// 			$order['balance_pay'] = $result[1]['system_balance'];
// 			$order['coupon_price'] = $result[1]['system_coupon_price'];
			
// 			$order['score_used_count'] = $result[1]['system_score'];
// 			$order['score_deducte'] = $result[1]['system_score_money'];
			
// 			$order['merchant_balance'] = $result[1]['merchant_balance_pay'] + $result[1]['merchant_balance_give'];
// 			$order['card_price'] = $result[1]['merchant_coupon_price'];
// 			$order['merchant_reduce'] = $result[1]['merchant_discount_money'];
// 			$order['pay_money'] = $result[1]['pay_money'];
		} elseif ($order['pay_type'] == 1) {//店员买单
			if ($store_order = D('Store_order')->field(true)->where(array('business_type' => 'foodshop', 'business_id' => $order['order_id'], 'paid' => 1))->find()) {
				if ($store_order['pay_type'] == 'offline') {
					if ($store_order['offline_pay'] && ($store_pay = D('Store_pay')->field('name')->where(array('id' => $store_order['offline_pay']))->find())) {
						$order['pay_type'] = L_("线下支付【X1】",array("X1" => $store_pay['name'])); 
					} else {
						$order['pay_type'] = L_('线下支付');
					}
					$order['offline_pay'] = $store_order['offline_pay'];
				} else {
					$order['pay_type'] = D('Pay')->get_pay_name($store_order['pay_type'], 0, 1);
					$order['online_pay'] = $store_order['pay_type'];
				}
				$order['pay_time'] = $store_order['pay_time'];
			}
		}
		$order['pay_time_str'] = date('Y-m-d H:i:s',$order['pay_time']);
		$order['register_phone'] = '';
		if ($user = D('User')->field(true)->where(array('uid' => $order['uid']))->find()) {
            $order['register_phone'] = $user['phone'];
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

		$order_list = $this->field($condition_field)->where($condition_where)->table($condition_table)->order($condition_order)->select();
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
		return $order_list;
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
	
// 	public function cancel()
// 	{
// 		$cancel_time = 60 * C('config.shop_order_cancel_time');
// 		$time = time();
// 		$sql = "SELECT o.* FROM " . C('DB_PREFIX') . "shop_order AS o INNER JOIN " . C('DB_PREFIX') . "merchant_store_shop AS s ON s.store_id=o.store_id WHERE o.paid=1 AND o.status=0 AND ((o.cancel_time>0 AND {$time}-o.create_time>o.cancel_time*60) OR (o.cancel_time=0 AND {$time}-o.create_time>{$cancel_time}))";
// 		$res = $this->query($sql);
// 		foreach ($res as $row) {
// 			$this->where(array('order_id' => $row['order_id']))->save(array('status' => 5));
// 			D('Shop_order_log')->add_log(array('order_id' => $row['order_id'], 'status' => 10, 'name' => '店员接单超时系统自动取消', 'phone' => ''));
// 		}
// 	}
	
	public function save_order($data,$is_must = 1)
	{
		if($_SESSION['now_village_bind']['village_id']>0&&M('House_village_meal')->where(array('village_id'=>$_SESSION['now_village_bind']['village_id'],'store_id'=>$data['store_id']))->find()){
			$data['village_id'] = $_SESSION['now_village_bind']['village_id'];
		}
		/** 20180507 平台给商家限定订单数的折扣，下单时就增加折扣订单数目，没有做此单没有支付或者其他原因回滚折扣订单数 */
		$data['plat_discount'] = 0;
		$plat_discount = D('Merchant')->getDiscount($data['mer_id']);
		$plat_discount = floatval($plat_discount);
		if ($plat_discount) {
		    $data['plat_discount'] = $plat_discount;
		}

        $order_id = $this->add($data);
        if ($order_id&&$is_must) {
        	$now_order = $this->get_order_detail(array('order_id'=>$order_id));
			// 插入必点菜
	       	$this->count_price($now_order,0,true);
        }
		return $order_id;
	}

    /*****************20191111增加流水号**********************/
	public function get_fetch_number($now_order){
		if (!$now_order) {
			return false;
		}
        $data = array();
		if (!$now_order['fetch_number']) {
	        $nowDay = date('Ymd');
	        if ($foodshop = $this->field('fetch_day, fetch_number')->where(array('store_id' => $now_order['store_id']))->order('fetch_day DESC,fetch_number DESC')->find()) {
	            if ($foodshop['fetch_day'] == $nowDay) {
	                $fetch_number = $foodshop['fetch_number'] + 1;
	            } else {
	                $fetch_number = 1;
	            }
	        } else {
	            $fetch_number = 1;
	        }
	        $data['fetch_number'] = $fetch_number;
	        $data['fetch_day'] = $nowDay;
			$this->where(array('order_id' => $now_order['order_id']))->save($data);
	    }
        return true;
	}


	//修改订单状态
	public function change_status($order_id, $status)
	{
		$where['order_id'] = $order_id;
		$data['status'] = $status;
		if ($this->where($where)->data($data)->save()) {
			return true;
		} else {
			return false;
		}
	}
	/*
		status 打印方式  默认6  打印预结单
	*/
	public function order_notice($order_id, $menu = array(),$status = 6,$printType = 1, $is_back = 0, $staff_name='', $old_status = 0)
	{
	    $printHaddle = new PrintHaddle();
	    if ($menu) {
			// 是否启用餐饮打印模式 新
			if (C('config.open_foodshop_print_type')) {
		        $printHaddle->foodshop_menu_new($order_id, $menu, $status, $printType, $is_back, $staff_name, $old_status);
		    }else{
		    	$printHaddle->foodshop_menu($order_id, $menu,0);
		    }
	    } else {
	    	// 是否启用餐饮打印模式 新
			if (C('config.open_foodshop_print_type')) {
	        	$printHaddle->printit($order_id, 'foodshop_order', 6, $staff_name);
			}else{
				$printHaddle->printit($order_id, 'foodshop_order', 0);
			}
	    }
	}
	
	//到店付回调餐饮订单状态
	public function store_order_after_call($store_order){
		$now_food_order = $this->where(array('order_id'=>$store_order['business_id']))->find();
		$this->after_pay($store_order['business_id'], null, 1);
		$this->where(array('order_id'=>$store_order['business_id']))->data(array('price'=>$store_order['payment_money']+$now_food_order['book_price'],'pay_type'=>'1'))->save();
	}

	//定制计算店铺当日最小折扣
	public function notice_table_discount($mer_id,$store_id,$book_time)
    {
        $thatday = date('Y-m-d', $book_time);
        $time_start = strtotime($thatday);
        $time_end = strtotime(date($thatday . ' 23:59:59'));
        $where['book_time'] = array('between', array($time_start, $time_end));
        $where['status'] = array('in', array(1, 2, 3, 4));
        $where['order_from'] = 0;
        $merchant = M('Merchant')->where(array('mer_id' => $mer_id))->find();
        $merchant_temp = $merchant;
        $sql = 'SELECT f.* FROM  ' . C('DB_PREFIX') . 'merchant_store as m LEFT JOIN ' . C('DB_PREFIX') . 'merchant_store_foodshop as f ON m.store_id = f.store_id WHERE m.mer_id = ' . $mer_id . ' AND m.status=1 AND m.have_meal=1';
        $foodshops = M()->query($sql);
        foreach ($foodshops as $foodshop) {
        	$merchant = $merchant_temp;
            unset($where['mer_id']);
            $where['store_id'] = $foodshop['store_id'];
            $order_index = D('Foodshop_order')->where($where)->count();

            if ($foodshop['foodshop_tables_discount'] != '' || ($foodshop['other_discount'] > 0 && $foodshop['other_discount'] < 100)) {
                $merchant['foodshop_tables_discount'] = $foodshop['foodshop_tables_discount'];
                $merchant['other_discount'] = $foodshop['other_discount'];
            } else {
                unset($where['store_id']);
                $where['mer_id'] = $mer_id;
                $order_index = D('Foodshop_order')->where($where)->count();
            }
            if ($merchant['foodshop_tables_discount'] != '') {
                $merchant['foodshop_tables_discount'] = substr($merchant['foodshop_tables_discount'], 0, -1);
                $discountArr = explode('|', $merchant['foodshop_tables_discount']);
                $tables = [];
                foreach ($discountArr as $k => $v) {
                    $v = explode(':', $v);
                    $tables[$k] = $v;
                }
                if (intval($order_index) >= count($tables)) {
                    $mer_discount = $merchant['other_discount'];
                } else {
                    $mer_discount = $tables[$order_index][1];
                }
            } else {
                $mer_discount = $merchant['other_discount'];
            }
            $mer_discount = $mer_discount / 10;
            $foodshop_temp = M('Foodshop_date_discount')->where(array('store_id' => $foodshop['store_id'], 'book_date' => $thatday))->find();
            if ($foodshop_temp) {
                if ($foodshop_temp['table_discount'] != $mer_discount) {
                    M('Foodshop_date_discount')->where(array('store_id' =>  $foodshop['store_id'], 'book_date' => $thatday))->save(array('table_discount' => $mer_discount));
                }
            }
        }
    }

    // 写入平台总订单
    public function add_system_order($order_id = 0,$step = 1){
    	if(!$order_id) return false;

		$now_order = $this->get_order_detail(array('order_id' => $order_id), 3);
		$now_order['goods_price'] = $this->count_price($now_order);

		// 店员下单
		$result = D('store_order')->field(true)->where(array('business_id' => $now_order['order_id'], 'business_type' => 'foodshop', 'paid' => 1))->order('order_id ASC')->select();

		foreach ($result as $vo) {
		    $now_order['merchant_reduce'] += $vo['discount_price'];//店铺优惠
		    $now_order['score_used_count'] += $vo['score_used_count'];//使用积分数
		    $now_order['score_deducte'] += $vo['score_deducte'];//积分抵用的金额
		    $now_order['merchant_balance'] += $vo['merchant_balance'];//商家余额支付
		    $now_order['merchant_balance_give'] += $vo['card_give_money'];//商家赠送余额支付
		    $now_order['balance_pay'] += $vo['balance_pay'];//平台余额
		    $now_order['card_price'] += $vo['card_price'];//店铺优惠券金额
		    $now_order['coupon_price'] += $vo['coupon_price'];//平台优惠券金额
		    $now_order['pay_money'] += $vo['payment_money'];
		    $now_order['vip_discount_money'] += $vo['vip_discount_money'];
		    $now_order['mer_table_discount'] += $vo['mer_table_discount'];
		    $now_order['card_discount'] += $vo['card_discount'];
		    $now_order['total_price'] = $vo['total_price'];
		    $now_order['price'] = $vo['price'];
		    $now_order['no_discount_money'] = $vo['no_discount_money'];
		    $vo['uid'] && $now_order['uid'] = $vo['uid'];
		}

		/* 增加订单搜索条件 */

		$goods_name = [];
		foreach ($now_order['info'] as $v) {
			$goods_name[] = $v['name'];
		}

		//店铺名称
		$store = M('Merchant_store')->field('name')->where(['mer_id' => $now_order['mer_id'],
			'store_id' => $now_order['store_id']])->find();

		$goods_name_str = rtrim(implode(',', $goods_name),',');
		$now_order['keywords'] = $store['name'].','.$goods_name_str;

	    $res = D('System_order')->add_order('meal',$now_order,$order_id,$step);
	    return $res;
    }

    // 获取当前桌台最早预约时间
    public function get_now_appoint_time($table_type,$foodshop,$table_id = 0)
    {
        $now_time = time() + $foodshop['advance_time'] * 60;
        $where = array('table_type' => $table_type, 'book_time' => array('egt', $now_time), 'status' => array('in', array(0, 1, 2)));
        $table_id && $where['table_id'] = $table_id;
        $order_list = $this->field(true)->where($where)->select();
        $order_table_list = array();
        foreach ($order_list as $order) {
            $order_table_list[date('YmdHi', $order['book_time'])][] = $order;
        }

        $order_date_count = array();
        if ($order_table_list) {
            foreach ($order_table_list as $index => $row) {
                if ($table_id) {
                    if ($row) {
                        $order_date_count[$index] = 1;
                    } else {
                        $order_date_count[$index] = 0;
                    }
                }else{
                    if ($table_type_data['num'] <= count($row)) {
                        $order_date_count[$index] = 1;
                    } else {
                        $order_date_count[$index] = 0;
                    }
                }
                
            }
        }
        
        if(!$foodshop['book_time']){
            $foodshop['book_time'] = 60;
        }
        
        $now_time = time() + $foodshop['advance_time'] * 60;
        $loop_time = $foodshop['book_time'] * 60;
        $start_time = $foodshop['book_start'];
        $stop_time = $foodshop['book_stop'];
        if ($start_time == '00:00:00' && $stop_time == '00:00:00') {
            $stop_time = '23:59:59';
        }
        $book_time = 0;
        for ($d = 0; $d <= $foodshop['book_day']; $d++) {
            $this_start_time = strtotime(date('Y-m-d ', strtotime("+{$d} day")) . $start_time);
            $this_stop_time = strtotime(date('Y-m-d ', strtotime("+{$d} day")) . $stop_time);
            if ($this_start_time < $this_stop_time) {
                for ($t = $this_start_time; $t <= $this_stop_time; $t += $loop_time) {
                    if ($t < $now_time) {
                    } else {
                        if (isset($order_date_count[date('YmdHi', $t)]) && $order_date_count[date('YmdHi', $t)]) {
                        } else {
                            $book_time = date('Y-m-d H:i', $t);
                            break;
                        }
                    }
                }
                if ($book_time) break;
            } else {
                $stop_time_t = strtotime(date('Y-m-d ', strtotime("+{$d} day")) . '23:59:59');
                for ($t = $this_start_time; $t <= $stop_time_t; $t += $loop_time) {
                    if ($t < $now_time) {
                    } else {
                        if (isset($order_date_count[date('YmdHi', $t)]) && $order_date_count[date('YmdHi', $t)]) {
                        } else {
                            $book_time = date('Y-m-d H:i', $t);
                            break;
                        }
                    }
                }
                if ($book_time) break;
                $d_t = $d + 1;
                if ($d_t < $foodshop['book_day']) {
                    $start_time_t = strtotime(date('Y-m-d ', strtotime("+{$d_t} day")) . '00:00:00');
                    $stop_time_t = strtotime(date('Y-m-d ', strtotime("+{$d_t} day")) . $stop_time);
                    for ($t = $start_time_t; $t <= $stop_time_t; $t += $loop_time) {
                        if ($t < $now_time) {
                        } else {
                            if (isset($order_date_count[date('YmdHi', $t)]) && $order_date_count[date('YmdHi', $t)]) {
                            } else {
                                $book_time = date('Y-m-d H:i', $t);
                                break;
                            }
                        }
                    }
                    if ($book_time) break;
                }
                if ($book_time) break;
            }
        }
        return $book_time;
        
    }




    /**
     *获取可预约列表
     * @param array $foodshop   店铺的详情
     * @param int $book_time    预定时间，时间戳格式
     * @param int $book_num     预订人数
     * @param int $table_type   桌台类型ID
     * @return array
     */
    public function format_data($foodshop, $book_time, $table_type, $table_id=0)
    {
        $store_id = $foodshop['store_id'];
        //根据预订人数查找对应的桌台
        $table_type_data = M('Foodshop_table_type')->field(true)->where(array('store_id' => $store_id,'num'=>array('gt',0)))->select();
        $type_list = array();
        $type_ids = array();
        foreach ($table_type_data as $type) {
            $type_list[$type['id']] = $type;
            $type_ids[] = $type['id'];
        }

        if ($type_ids) {
            if (!in_array($table_type, $type_ids)) {
                $table_type = $type_ids[0];
            }
        } else {
            return array('err_code' => 1, 'msg' => L_('没有可供选择的桌台'));
        }


        //检验已选的桌台类型的各个时间点的预订情况
        $where = array('table_type' => $table_type, 'book_time' => array('gt', time()), 'status' => array('in', array(1, 2)));;
        $table_id && $where['table_id'] = $table_id;
        $order_list = M('Foodshop_order')->field(true)->where($where)->select();
        
        $order_table_list = array();
        foreach ($order_list as $order) {
            $order_table_list[date('YmdHi', $order['book_time'])][] = $order;
        }
        $order_date_count = array();
        if ($order_table_list) {
            foreach ($order_table_list as $index => $row) {
                if ($table_id) {
                    if ($row) {
                        $order_date_count[$index] = 1;
                    } else {
                        $order_date_count[$index] = 0;
                    }
                }else{
                    if ($type_list[$table_type]['num'] <= count($row)) {
                        $order_date_count[$index] = 1;
                    } else {
                        $order_date_count[$index] = 0;
                    }
                }
                
            }
        }

        fdump($order_date_count,'foodshop',true);
        $weeks = array(L_('周日'), L_('周一'), L_('周二'), L_('周三'), L_('周四'), L_('周五'), L_('周六'));
        $time_list = array();
        $day_list = array();
        $now_time = time() + $foodshop['advance_time'] * 60;//开始预约时间
        $foodshop['book_time'] = $foodshop['book_time'] > 0 ? $foodshop['book_time'] : 60;
        $loop_time = $foodshop['book_time'] * 60;//预订时间间隔

        $start_time = $foodshop['book_start'];
        $stop_time = $foodshop['book_stop'];
        if ($start_time == '00:00:00' && $stop_time == '00:00:00') {
            $stop_time = '23:59:59';
        }

        $select_date_flag = false;
        for ($d = 0; $d <= $foodshop['book_day']; $d++) {
            $index = date('Ymd', strtotime("+{$d} day"));
            //日期的列表
            $day_list[$index] = array('date' => date('Y-m-d', strtotime("+{$d} day")), 'title' => $weeks[date('w', strtotime("+{$d} day"))], 'day' => '<i class="m">' . date('m', strtotime("+{$d} day")) . '</i>-<i class="d">' . date('d', strtotime("+{$d} day")) . '</i>');

            //每日可供预约的时间点
            $this_start_time = strtotime(date('YmdHi', strtotime(date('Y-m-d ', strtotime("+{$d} day")) . $start_time)));
            $this_stop_time = strtotime(date('YmdHi', strtotime(date('Y-m-d ', strtotime("+{$d} day")) . $stop_time)));
            $temp = null;
            if ($this_start_time < $this_stop_time) {
                $temp['date'] = date('Y-m-d', $this_start_time);
                for ($t = $this_start_time; $t <= $this_stop_time; $t += $loop_time) {
                    $class = '';
                    if ($t < $now_time) {
                        $class = 'End';
                        $t_a = array('class' => 'End', 'time' => date('H:i', $t));
                    } else {
                        if (isset($order_date_count[date('YmdHi', $t)]) && $order_date_count[date('YmdHi', $t)]) {
                            $class = 'End';
                            $t_a = array('class' => 'End', 'time' => date('H:i', $t));
                        } else {
                            $t_a = array('class' => '', 'time' => date('H:i', $t));
                        }
                    }
                    if ($book_time == $t) {
                        if ($class == 'End') {
                            return array('err_code' => 2, 'msg' => L_('您选择餐桌在该时间不能再预订了！'));
                            $book_time += $loop_time;
                        } else {
                            $select_date_flag = true;
                            $t_a = array('class' => 'on', 'time' => date('H:i', $t));
                        }
                    }
                    $temp['time_list'][] = $t_a;
                }
                $time_list[$index] = $temp;
            } else {
                $stop_time_t = strtotime(date('Y-m-d ', strtotime("+{$d} day")) . '23:59:59');
                $temp['date'] = date('Y-m-d', $this_start_time);
                for ($t = $this_start_time; $t <= $stop_time_t; $t += $loop_time) {
                    $class = '';
                    if ($t < $now_time) {
                        $class = 'End';
                        $t_a = array('class' => 'End', 'time' => date('H:i', $t));
                    } else {
                        if (isset($order_date_count[date('YmdHi', $t)]) && $order_date_count[date('YmdHi', $t)]) {
                            $class = 'End';
                            $t_a = array('class' => 'End', 'time' => date('H:i', $t));
                        } else {
                            $t_a = array('class' => '', 'time' => date('H:i', $t));
                        }
                    }
                    if ($book_time == $t) {
                        if ($class == 'End') {
                            return array('err_code' => 2, 'msg' => L_('您选择餐桌在该时间不能再预订了！'));
                            $book_time += $loop_time;
                        } else {
                            $select_date_flag = true;
                            $t_a = array('class' => 'on', 'time' => date('H:i', $t));
                        }
                    }
                    $temp['time_list'][] = $t_a;
                }
                $time_list[$index] = $temp;
                $d_t = $d + 1;
                if ($d_t < $foodshop['book_day']) {
                    $start_time_t = strtotime(date('Y-m-d ', strtotime("+{$d_t} day")) . '00:00:00');
                    $stop_time_t = strtotime(date('Y-m-d ', strtotime("+{$d_t} day")) . $stop_time);
                    $temp['date'] = date('Y-m-d', $start_time_t);
                    for ($t = $start_time_t; $t <= $stop_time_t; $t += $loop_time) {
                        $class = '';
                        if ($t < $now_time) {
                            $class = 'End';
                            $t_a = array('class' => 'End', 'time' => date('H:i', $t));
                        } else {
                            if (isset($order_date_count[date('YmdHi', $t)]) && $order_date_count[date('YmdHi', $t)]) {
                                $class = 'End';
                                $t_a = array('class' => 'End', 'time' => date('H:i', $t));
                            } else {
                                $t_a = array('class' => '', 'time' => date('H:i', $t));
                            }
                        }
                        if ($book_time == $t) {
                            if ($class == 'End') {
                                return array('err_code' => 2, 'msg' => L_('您选择餐桌在该时间不能再预订了！'));
                                $book_time += $loop_time;
                            } else {
                                $select_date_flag = true;
                                $t_a = array('class' => 'on', 'time' => date('H:i', $t));
                            }
                        }
                        $temp['time_list'][] = $t_a;
                    }
                    $time_list[date('Ymd', strtotime("+{$d_t} day"))] = $temp;
                }
            }
        }


        if ($select_date_flag) {
            $data = array('m' => date('m', $book_time), 'd' => date('d', $book_time), 'w' => $weeks[date('w', $book_time)], 'o' => date('H:i', $book_time), 'selectdate' => date('Y-m-d', $book_time));
            $day_list = array_values($day_list);
            $time_list = array_values($time_list);
            $data['err_code'] = false;
            $data['table_list'] = $type_list;
            $data['table_type'] = $table_type;
            $data['day_list'] = $day_list;
            $data['time_list'] = $time_list;
            $data['book_time'] = date('Y-m-d H:i', $book_time);
            $data['book_num'] = $book_num;
            $data['book_price'] = floatval($type_list[$table_type]['deposit']);
            return $data;
        } else {
            return array('err_code' => 3, 'msg' => L_('没有可供预约的时间'));
        }

    }

    // 餐饮账单统计
	public function foodshop_report($where)
	{
		$return = array();
		$return['pay_type_report'] = array();
		$return['store_id'] = $where['store_id'];

		// 店员线下支付方式
		$store_pays = D('Store_pay')->field(true)->where(array('store_id' => $where['store_id']))->select();
		$offline_pay_type = array(0 => array('name' => L_('现金支付'), 'id' => 0, 'store_id' => $where['store_id']));
		foreach ($store_pays as $pay) {
			$offline_pay_type[$pay['id']] = $pay;
		}

		//用户结账 余额支付
		$field = 'sum(total_money - wx_cheap) AS total_price, count(DISTINCT order_id) as num, sum(system_balance) as money,is_mobile_pay';
		$where['business_type'] = 'foodshop';
		$where['system_balance'] = array('gt',0);
		$blance_order = D('Plat_order')->field($field)->where($where)->find();
		unset($where['system_balance']);
		$blance_order['num'] && $blance_order['pay_type_name'] =  L_('平台').D('Pay')->get_pay_name($blance_order['pay_type'],$blance_order['is_mobile_pay'],1);
		$blance_order['num']  && $return['pay_type_report'] = array($blance_order);

		//用户结账  在线支付，根据支付方式统计
		$field = 'pay_type, count(DISTINCT order_id) as num, sum(total_money - wx_cheap) AS total_price, sum(pay_money) as money ,is_mobile_pay';
		$where['business_type'] = 'foodshop';
		$where['pay_money'] = array('gt',0);
		$online_pay_order = D('Plat_order')->field($field)->where($where)->group('pay_type')->select();
		unset($where['pay_money']);
		foreach ($online_pay_order as &$online) {
			$online['pay_type_name'] = D('Pay')->get_pay_name($online['pay_type'],$online['is_mobile_pay'],1);
			$online['money'] = getFormatNumber($online['money']);
			$online['total_price'] = getFormatNumber($online['total_price']);
		}
		$online_pay_order && $return['pay_type_report'] = array_merge($return['pay_type_report'],$online_pay_order);

		// 店员结算 会员卡折扣抵扣金额和线下支付方式汇总
		$field = 'card_discount,price,no_discount_money,coupon_price, score_deducte, merchant_balance, card_give_money, card_price,vip_discount_money, discount_price,total_price,offline_pay';
		$where['business_type'] = 'foodshop';
		$card_discount_list = D('Store_order')->field($field)->where($where)->select();

		//会员卡折扣抵扣金额
		$card_discount_money_total = 0;
		//线下支付
		$offline_order_list = array();
		foreach ($card_discount_list as $key => $value) {
			$card_discount_money = 0;
			if ($value['card_discount']) {
				$card_discount_money = ($value['price'] - $value['no_discount_money'])*(10-$value['card_discount'])/10;
				$card_discount_money += $card_discount_money;
			}
			if ($value['offline_pay']) {
				$offline_pay = $value['price'] - $card_discount_money - $value['coupon_price'] - $value['score_deducte'] - $value['merchant_balance'] - $value['card_give_money'] - $value['card_price'];
				if ($offline_order_list[$value['offline_pay']]) {
					$offline_order_list[$value['offline_pay']]['money'] += $offline_pay;
					$offline_order_list[$value['offline_pay']]['total_price'] += $value['total_price'];
					$offline_order_list[$value['offline_pay']]['num'] += 1;
				}else{
					$offline_order_list[$value['offline_pay']] = array(
						'offline_pay' => $value['offline_pay'],
						'total_price' => $value['total_price'],
						'money' => $offline_pay,
						'num' => 1
					);
				}
			}
		}
		$card_discount_money_total = round($card_discount_money_total,2);
		if ($offline_order_list) {
			foreach ($offline_order_list as &$offline) {
				$offline['pay_type_name'] = '线下支付('.$offline_pay_type[$offline['offline_pay']]['name'].')';
				$offline['money'] = getFormatNumber($offline['money']);
				$offline['total_price'] = getFormatNumber($offline['total_price']);
			}
			$offline_order_list = array_values($offline_order_list);
			$return['pay_type_report'] = array_merge($return['pay_type_report'],$offline_order_list);
		}

		// 店员结算 在线支付
		$field = 'pay_type,count(DISTINCT business_id) as num,sum(total_price) AS total_price, sum(payment_money) AS money,is_mobile_pay';
		$where['business_type'] = 'foodshop';
		$where['pay_type'] = array('gt',0);
		$order_list = D('Store_order')->field($field)->where($where)->group('offline_pay')->select();
		unset($where['pay_type']);
		if ($order_list){
			foreach ($order_list as &$_temp) {
				$_temp['pay_type_name'] =  D('Pay')->get_pay_name($_temp['pay_type'],$_temp['is_mobile_pay'],1);
				$_temp['money'] = getFormatNumber($_temp['money']);
				$_temp['total_price'] = getFormatNumber($_temp['total_price']);
			}
			$order_list && $return['pay_type_report'] = array_merge($return['pay_type_report'],$order_list);
		}

		// 店员结算 余额支付
		$field = 'count(DISTINCT business_id) as num,sum(total_price) AS total_price, sum(balance_pay) AS money';
		$where['business_type'] = 'foodshop';
		$where['balance_pay'] = array('gt',0);
		$order_list = D('Store_order')->field($field)->where($where)->find();
		unset($where['balance_pay']);
		$order_list['num'] && $order_list['pay_type_name'] =  L_('平台余额支付');
		$order_list['num'] && $return['pay_type_report'] = array_merge($return['pay_type_report'],array($order_list));

		//用户结账 优惠
		$field = 'sum(system_coupon_price) as system_coupon_price, sum(system_score_money) as system_score_money, sum(merchant_balance_pay) AS merchant_balance_pay, sum(merchant_balance_give) as merchant_balance_give, sum(merchant_coupon_price) as merchant_coupon_price, sum(merchant_discount_money) as merchant_discount_money';
		$where['business_type'] = 'foodshop';
		$discount_price = D('Plat_order')->field($field)->where($where)->find();

		// 店员结算 优惠
		$field = 'sum(coupon_price) AS system_coupon_price, sum(score_deducte) as system_score_money, sum(merchant_balance) as merchant_balance_pay, sum(card_give_money) AS merchant_balance_give, sum(card_price) AS merchant_coupon_price, sum(vip_discount_money) as vip_discount_money, sum(discount_price) as discount_price';
		$where['business_type'] = 'foodshop';
		$store_discount_price = D('Store_order')->field($field)->where($where)->find();

		// 桌台折扣
		$field = 'sum(table_discount_money) AS table_discount_money, count(*) as num, sum(book_num) as people_num, sum(total_price) as total_price';
		unset($where['business_type']);
		$foodshop_order = D('Foodshop_order')->field($field)->where($where)->find();

		// 平台优惠券
		$return['discount']['system_coupon_price']  = array('name'=>L_('平台优惠券'),'money'=>round($discount_price['system_coupon_price']+$store_discount_price['system_coupon_price'],2));
		// 积分抵扣
		$return['discount']['system_score_money']  = array('name'=>L_('积分抵扣'),'money'=>round($discount_price['system_score_money']+$store_discount_price['system_score_money'],2));
		// 商家会员卡余额
		$return['discount']['merchant_balance_pay']  = array('name'=>L_('商家会员卡余额'),'money'=>round($discount_price['merchant_balance_pay']+$store_discount_price['merchant_balance_pay'],2));
		// 商家会员卡赠送余额
		$return['discount']['merchant_balance_give']  = array('name'=>L_('商家会员卡赠送余额'),'money'=>round($discount_price['merchant_balance_give']+$store_discount_price['merchant_balance_give'],2));
		// 商家优惠券
		$return['discount']['merchant_coupon_price']  = array('name'=>L_('商家优惠券'),'money'=>round($discount_price['merchant_coupon_price']+$store_discount_price['merchant_coupon_price'],2));
		// 商家会员卡折扣
		$return['discount']['merchant_discount_money']  = array('name'=>L_('商家会员卡折扣'),'money'=>round($discount_price['merchant_discount_money']+$card_discount_money_total,2));
		//会员等级优惠
		$return['discount']['vip_discount_money']  = array('name'=>L_('会员等级优惠'),'money'=>round($discount_price['vip_discount_money']+$store_discount_price['vip_discount_money'],2));
		// 店铺优惠
		$return['discount']['discount_price']  = array('name'=>L_('店铺优惠'),'money'=>round($discount_price['discount_price']+$store_discount_price['discount_price'],2));
		//桌台折扣
		$return['discount']['table_discount_money']  = array('name'=>L_('桌台折扣'),'money'=>round(floatval($foodshop_order['table_discount_money']),2));
		$return['num'] = $foodshop_order['num'];
		$return['people_num'] = $foodshop_order['people_num'];
		$return['per_capita_consumption'] = $foodshop_order['people_num'] ? round($foodshop_order['total_price']/$foodshop_order['people_num'],2) : 0;
		$return['total_price'] = $foodshop_order['total_price'];
		return $return;
	}    
	protected function getPayName(){
		$payName = array(
				'weixin' => L_('微信支付'),
				'tenpay' => L_('财付通支付'),
				'yeepay' => L_('银行卡支付(易宝支付)'),
				'allinpay' => L_('银行卡支付(通联支付)'),
				'chinabank' => L_('银行卡支付(网银在线)'),
		);
		return $payName;
	}

	/**
     * @desc 生成桌台服务费
     * @param $orderid
     * @return bool
     */
    public function create_table_service_fee($orderid,$id=0){
        if(!$orderid) return false;
        $where = array();
		if($id){
			$where['o.order_id'] = $orderid;
		}else{
			$where['o.real_orderid'] = $orderid;
		}
        $field = 'd.price,d.num,g.join_table_service,t.service_fee';//根据商品原价计算
        $info = M('Foodshop_order')->alias('o')
                ->JOIN('LEFT JOIN pigcms_foodshop_order_detail  as d on d.order_id=o.order_id')
                ->JOIN('LEFT JOIN pigcms_foodshop_goods as g on g.goods_id = d.goods_id')
                ->JOIN('LEFT JOIN pigcms_foodshop_table as t on t.id=o.table_id')
                ->WHERE($where)
                ->FIELD($field)
                ->select();
				//echo M()->getLastSql();exit;
        if(!empty($info)&&!empty($info[0]['service_fee'])){
            $service_fee_total = 0;
            foreach($info as $key => $val){
                if($val['join_table_service']){
					$number = $val['price']*$val['num']*$val['service_fee']/100;
                    $service_fee_total += round($number,2);
                }
            }
            $data = array();
            $data['table_service_payable'] = $service_fee_total;
            $data['table_service_payment'] = $service_fee_total;
			if($id){
				$w['order_id'] = $orderid;
			}else{
				$w['real_orderid'] = $orderid;
			}
            $r = $this->where($w)->data($data)->save();
            if($r===FALSE){
				fdump(M()->getLastSql(),'TABLE');
                return false;  
            }else{
				return $service_fee_total;
            }
        }
        return true;
    }

    // 预订桌台
    public function foodshopBookTimePlan($book_info,$check_id=0){
        $check_db = M('foodshop_book_time_check');
        $check_data = [];
        $check_data['book_info'] = json_encode($book_info);
        $check_data['create_time'] = time();
        if ($check_id) {
            $check_db->where(['id'=>$check_id])->save($check_data);
        }else{
            $check_id = $check_db->add($check_data);
        }
        
        $arr= array();
        $arr['param'] = serialize(array());
        $arr['plan_time'] = -1000;
        $arr['space_time'] = 0;
        $arr['add_time'] = time();
        $arr['file'] = 'foodshop_book_time_check';
        $arr['time_type'] = 1;
        $arr['unique_id'] = 'foodshop_book_time_check'.$check_id;
		$arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
        M('process_sub_plan')->data($arr)->add();

        $process = true;
        $i=0;
        while($process){
            $check_info = $check_db->where(array('id'=>$check_id))->find();
            if($check_info['status']==1){//成功
                $process = false;
                $check_return = true;
                return ['error_code'=>0,'data'=>['table_id'=>$check_info['table_id']]];
            }elseif($check_info['status']==2){//失败
                $process = false;
                $check_goods = json_decode($check_info['msg']);
                $check_goods_str = implode(",", $check_goods);
                $check_tips = '抱歉，该时段该桌台已订满！';
                return ['error_code'=>1,'msg'=>$check_tips];
            }
            if($i>=50){
                $process = false;
                return ['error_code'=>1,'msg'=>'系统繁忙，请稍后重试'];
            }
            $i++;
            usleep(300000);
        }
    }

    //桌台是否可预订【计划任务调用此方法】
    public function foodshopBookTimeCheck($id){
        
        $db = M('foodshop_book_time_check');
        $where = ['id'=>$id];
        
        $info = $db->where($where)->find();
        $book_info = json_decode($info['book_info'],true);
        
        $can_book = 2; //是否可预订，默认不可预订

        if ($book_info['table_id']) {
        	$where_check = ['book_info'=>$info['book_info'],'status'=>1,'create_time'=>['gt',time()-10]];
        	$check = $db->where($where_check)->find();
        	if ($check) {
                $can_book = 2;
            }else{ 
	            // 已定订单
	            $orders = M('Foodshop_order')->field(true)->where($book_info)->select();
	            if (!$orders) {
	                $table_id = $book_info['table_id'];
	                //可以预定
	                $can_book = 1;
	            }else{
	                // 不可预订
	                $can_book = 2;
	            }
            }
           
        }else{
            // 桌台类型
            $table_type_data = M('Foodshop_table_type')->where(array('store_id' => $book_info['store_id'], 'id' => $book_info['table_type']))->find();

            // 已定订单
            $orders = M('Foodshop_order')->field(true)->where($book_info)->select();
            if (count($orders) >= $table_type_data['num']) {
                // 不可预订
                $can_book = 2;
            }else{
                $tids = array();
                foreach ($orders as $order) {
                    $tids[] = $order['table_id'];
                }
                $tables = M('Foodshop_table')->field(true)->where(array('store_id' => $book_info['store_id'], 'tid' => $book_info['table_type']))->select();
                $table_id = 0;
                foreach ($tables as $table) {
                    if (!in_array($table['id'], $tids)) {
                        $book_info['table_id'] = $table['id'];
                        $table_id = $table['id'];
                        $can_book = 1;
                        // $this->foodshopBookTimePlan($book_info);
                        break;
                    }
                }
            }
        }
        
        $save = [];
        $save['status'] = $can_book;
        $save['table_id'] = $table_id;
        return $db->where($where)->data($save)->save();
    }
}
?>
