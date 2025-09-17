<?php

use app\community\model\service\StorageService;

class Plat_orderModel extends Model{
	/*根据 支付平台的英文 和 是否移动端支付 得到中文名称*/
	public function get_pay_name($pay_type,$is_mobile_pay, $paid = 1){
		switch($pay_type){
			case 'alipay':
				$pay_type_txt = L_('支付宝');
				break;
            case 'alipayh5':
                $pay_type_txt = L_('支付宝');
                break;
			case 'tenpay':
				$pay_type_txt = L_('财付通');
				break;
			case 'yeepay':
				$pay_type_txt = L_('易宝支付');
				break;
			case 'allinpay':
				$pay_type_txt = L_('通联支付');
				break;
			case 'chinabank':
				$pay_type_txt = L_('网银在线');
				break;
			case 'weixin':
				$pay_type_txt = L_('微信支付');
				break;
            case 'weixinh5':
                $pay_type_txt = L_('微信支付');
                break;
			case 'baidu':
				$pay_type_txt = L_('百度钱包');
				break;
			case 'unionpay':
				$pay_type_txt = L_('银联支付');
				break;
			case 'offline':
				$pay_type_txt = L_('货到付款');
				break;
            case 'hqpay':
                $pay_type_txt = L_('环球汇通支付');
                break;
            case 'hqpay_wx':
                $pay_type_txt = L_('环球汇通微信支付');
                break;
            case 'hqpay_al':
                $pay_type_txt = L_('环球汇通支付宝支付');
                break;
			default:
				if ($paid) {
					$pay_type_txt = L_('余额支付');
				} else {
					$pay_type_txt = L_('未支付');
					return L_('未支付');
				}
				
		}
		if($is_mobile_pay == 3){
			$pay_type_txt .= '['.L_('小程序').']';
		}else if($is_mobile_pay == 2){
			$pay_type_txt .= '['.L_('APP').']';
		}else if($is_mobile_pay){
			$pay_type_txt .= '['.L_('移动端').']';
		}
		return $pay_type_txt;
	}
	public function add_order($param){
		if(empty($param['business_type'])){
			return array('error_code' => true, 'error_msg' => L_('请携带业务类型'));
		}
		if(floatval($param['total_money']) < 0&&(!isset($param['nolicence'])||$param['nolicence']==false)){
			return array('error_code' => true, 'error_msg' => L_('请携带订单总价'));
		}
		if(empty($param['order_name'])){
			return array('error_code' => true, 'error_msg' => L_('请携带订单名称'));
		}
		$param['add_time'] = $_SERVER['REQUEST_TIME'];
        $order_id = $this->data($param)->add();
		fdump_api([$order_id,$param,$this->_sql()],'plat_order_0507',1);
		if($order_id ){
		    // 处理下服务快派订单
            if ($param['business_type'] && $param['business_type'] == 'service' && !$param['is_final_pay']){
                /*-----记录系统订单信息 订单创建 19/1/24--服务快派-*/
                PD("System_order")->add_order('service',$param, $order_id);

            }
            // 处理下话费充值订单
            if ($param['business_type'] && $param['business_type'] == 'mobile_recharge'){
                PD("System_order")->add_order('mobile_recharge',$param, $order_id);
            }

			return array('error_code' => false, 'order_id' => $order_id);
		}else{
			return array('error_code' => true, 'error_msg' => L_('订单创建失败，请重试'));
		}
	}
	public function get_pay_order($uid,$order_id,$is_web=false,$is_app = false){
		$now_order = $this->where(array('order_id'=>$order_id))->find();
		if(empty($now_order)){
			return array('error'=>1,'msg'=>L_('当前订单已失效或不存在！'));
		}
		if(!empty($now_order['paid'])){
            if(C('config.system_type') == 'village') {
                $url = get_base_url('pages/village_menu/my');
            } else {
                $url = '/wap.php?c=My&a=index';
            }
            return array('error'=>1,'msg'=>L_('您已经支付过此订单！'),'url'=>$url);
		}
		if ('house_village_pay_cashier'==$now_order['business_type']) {
            $order_param = D(ucfirst($now_order['business_type']).'_order')->get_pay_order($now_order['business_id'],$uid);
        } else {
            $order_param = D(ucfirst($now_order['business_type']).'_order')->get_pay_order($now_order['business_id']);
        }
        if ($now_order['business_type'] == 'village_group') {
            $village_group_order = D('Village_group_order')->field('uid,start_uid,stock_reduce_type,status')->where(array('order_id' => $now_order['business_id']))->find();
            $order_content = D('Village_group_order_detail')->field('goods_id,name, price, num')->where(array('order_id' => $now_order['business_id']))->select();
            if($village_group_order['status'] == 10){
                return array('error'=>1,'msg'=>'订单支付超时已取消，请重新下单');
            }
            if($village_group_order['stock_reduce_type'] == 2){
                //检查库存
                $goods_count = array_column($order_content, 'num', 'goods_id');
                $check = D("Village_group_goods")->check_goods($village_group_order['start_uid'], $goods_count, $village_group_order['uid'], true, $order_id);
                if(!$check['status']){
                    return array('error'=>1,'msg'=>$check['msg']);
                }
            }
            $order_param['order_info']['order_num'] = 0;
            foreach ($order_content as $val) {
                $order_param['order_info']['order_num'] += $val['num'];
            }
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
            if ($order_param) {
                $order_param['order_total_money'] = floatval($now_order['total_money']);
            }
        } else {
            $str = '';
        }

		$order_info = array(
			'order_id'			=>	$now_order['order_id'],
			'mer_id'			=>	$order_param['order_info']['mer_id'],
			'store_id'			=>	$order_param['order_info']['store_id'],
			'openid'			=>	$order_param['order_info']['openid'], 
			'order_type'		=>	'plat',
			'is_foodshop_new'	=>  $order_param['order_info']['is_foodshop_new'],
			'order_name'		=>	$now_order['order_name'],
			'order_num'			=>	$order_param['order_info']['order_num'],
			'order_total_money'	=>	floatval($now_order['total_money']),
			'business_type'    	=>$now_order['business_type'],
			'pay_offline' 			=> !isset($order_param['order_info']['pay_offline'])?false:$order_param['order_info']['pay_offline'],			//线下支付
			'pay_merchant_balance' 	=> !isset($order_param['order_info']['pay_merchant_balance'])?false:$order_param['order_info']['pay_merchant_balance'],		//商家余额
			'pay_merchant_coupon' 	=> !isset($order_param['order_info']['pay_merchant_coupon'])?false:$order_param['order_info']['pay_merchant_coupon'],		//商家优惠券
			'pay_merchant_ownpay' 	=>!isset($order_param['order_info']['pay_merchant_ownpay'])?false:$order_param['order_info']['pay_merchant_ownpay'],		//商家自有支付
			'pay_system_balance' 	=>!isset($order_param['order_info']['pay_system_balance'])?false:$order_param['order_info']['pay_system_balance'],		//平台余额
			'pay_system_coupon' 	=> !isset($order_param['order_info']['pay_system_coupon'])?false:$order_param['order_info']['pay_system_coupon'],		//平台优惠券
			'pay_system_score' 		=> !isset($order_param['order_info']['pay_system_score'])?false:$order_param['order_info']['pay_system_score'],		//平台积分抵现
			'discount_status' 		=> !isset($order_param['order_info']['discount_status'])?false:$order_param['order_info']['discount_status'],		//是否有折扣
			'is_own' 		=> $now_order['is_own'],		//是否有折扣
            'order_content'		=>  $order_content?$order_content:array(),
            'order_txt_type'	=>	$str,
		);
		if($order_param){
		    if (strtolower($now_order['business_type']) == 'foodshop') {
		        $order_param['order_info']['status'] = $order_param['order_info']['status'] > 2 ? 1 : 0;
		    }
			$order_info = array_merge($order_info,$order_param);
		}
		return array('error'=>0,'order_info'=>$order_info);
	}

	public  function get_order_by_business_id($param){
		$now_order = $this->where($param)->find();
		if ($now_order['pay_type']) {
			$now_order['pay_type_txt'] = PD('Pay')->get_pay_name($now_order['pay_type'], 0);
 		}
		return $now_order;
	}

	public function wap_befor_pay($order_info, $now_coupon, $merchant_balance, $now_user,$ecard_info=null){
		//return array('error_code' => false, 'pay_money' => $order_info['order_total_money']);
		//去除微信优惠的金额
		
		$pay_money = $order_info['order_total_money'];

		//保存加入联盟的商家id
		if(!empty($merchant_balance['union_mer_id'])){
			$data_plat_order['union_mer_id'] = $merchant_balance['union_mer_id'];
			$order_result = $this->wap_pay_save_order($order_info,$data_plat_order);
		}

		if($pay_money<=0){
			return $this->wap_after_pay_before($order_info);
		}

		//去掉折扣
		if($merchant_balance['card_discount']>0){
			$data_plat_order['card_discount'] = $merchant_balance['card_discount'];
			$tmp_pay_money = sprintf("%.2f",$pay_money*$merchant_balance['card_discount']/10);
			//$tmp_pay_money = floor($pay_money*$merchant_balance['card_discount']*10)/100;
			$data_plat_order['merchant_discount_money'] = sprintf("%.2f",$pay_money - $tmp_pay_money );
			$pay_money = $tmp_pay_money;
		}
		if(C('config.open_extra_price')==1){
			$user_score_use_percent=(float)C('config.user_score_use_percent');
			$order_info['order_extra_price'] = bcdiv($order_info['extra_price'],$user_score_use_percent,2);
			$pay_money +=$order_info['order_extra_price'];
		}

		//判断优惠券
		if($now_coupon['card_price']>0) {
			$data_plat_order['card_id'] = $now_coupon['merc_id'];
			$data_plat_order['card_price'] = $now_coupon['card_price'];
			if ($now_coupon['card_price'] >= $pay_money) {
				$order_result = $this->wap_pay_save_order($order_info, $data_plat_order);
				if ($order_result['error_code']) {
					return $order_result;
				}
				return $this->wap_after_pay_before($order_info);
			}
			$pay_money -= $now_coupon['card_price'];
			$data_plat_order['pay_money'] = $pay_money;
		}

		//系统优惠券
		if($now_coupon['coupon_price']>0){
			$data_plat_order['coupon_id'] = $now_coupon['sysc_id'];
			$data_plat_order['coupon_price'] = $now_coupon['coupon_price'];
			if ($now_coupon['coupon_price'] >= $pay_money) {
				$order_result = $this->wap_pay_save_order($order_info, $data_plat_order);
				if ($order_result['error_code']) {
					return $order_result;
				}
				return $this->wap_after_pay_before($order_info);
			}
			$pay_money -= $now_coupon['coupon_price'];
			$data_plat_order['pay_money'] = $pay_money;
		}

		//使用平台E卡
		if(!empty($ecard_info) && $ecard_info['now_price']>0){
			$data_plat_order['ecard_password'] = $ecard_info['card_password'];
			$data_plat_order['use_ecard_price'] = $ecard_info['now_price'];
			if($ecard_info['now_price']>=$pay_money){
				$order_result = $this->wap_pay_save_order($order_info,$data_plat_order);
				if($order_result['error_code']){
					return $order_result;
				}
				$order_info['pay_money'] = $pay_money;
				return $this->wap_after_pay_before($order_info);
			}
			$pay_money-=$ecard_info['now_price'];
			$data_plat_order['pay_money'] = $pay_money;
			
		}else{
			$data_plat_order['ecard_password'] ='';
			$data_plat_order['use_ecard_price'] =0;
		}

		// 使用积分
		if(!empty($order_info['score_deducte'])&&$order_info['use_score']){
			$data_plat_order['score_used_count']  = $order_info['score_used_count'];
			$data_plat_order['score_deducte']     = (float)$order_info['score_deducte'];
			if($order_info['score_deducte'] >= $pay_money){
				//扣除积分
				$order_result = $this->wap_pay_save_order($order_info,$data_plat_order);
				if($order_result['error_code']){
					return $order_result;
				}
				$order_info['pay_money'] = $pay_money;
				return $this->wap_after_pay_before($order_info);
			}


			$pay_money -= $order_info['score_deducte'];
			//$data_plat_order['system_pay'] += $data_plat_order['score_deducte'];
		}

		//判断商家余额
		if(!empty($merchant_balance['card_money'])&&$order_info['use_merchant_balance']){
			if($merchant_balance['card_money'] >= $pay_money){
				$data_plat_order['merchant_balance'] = $pay_money;
				$order_result = $this->wap_pay_save_order($order_info,$data_plat_order);
				if($order_result['error_code']){
					return $order_result;
				}
				return $this->wap_after_pay_before($order_info);
			}else{
				$data_plat_order['merchant_balance'] = $merchant_balance['card_money'];
			}
			$pay_money -= $merchant_balance['card_money'];
		}

		if(!empty($merchant_balance['card_give_money'])&&$order_info['use_merchant_balance']){
			if($merchant_balance['card_give_money'] >= $pay_money){
				$data_plat_order['card_give_money'] = $pay_money;
				$order_result = $this->wap_pay_save_order($order_info,$data_plat_order);
				if($order_result['error_code']){
					return $order_result;
				}
				return $this->wap_after_pay_before($order_info);
			}else{
				$data_plat_order['card_give_money'] = $merchant_balance['card_give_money'];
			}
			$pay_money -= $merchant_balance['card_give_money'];
		}

		//判断帐户余额
		if(!empty($now_user['now_money'])&&$order_info['use_balance']){
			if($now_user['now_money'] >= $pay_money){
				$data_plat_order['balance_pay'] = $pay_money;
				$order_result = $this->wap_pay_save_order($order_info,$data_plat_order);
				if($order_result['error_code']){
					return $order_result;
				}
				return $this->wap_after_pay_before($order_info);
			}else{
				$data_plat_order['balance_pay'] = $now_user['now_money'];
			}
			$pay_money -= $now_user['now_money'];
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
		
		//在线支付

		$order_result = $this->wap_pay_save_order($order_info,$data_plat_order);
		if($order_result['error_code']){
			return $order_result;
		}

		return array('error_code'=>false,'pay_money'=>$pay_money);
	}

	public function wap_pay_save_order($order_info,$data_plat_order){
		$condition_shop_order['order_id'] 		= $order_info['order_id'];
		$data_plat_order['system_coupon_id'] 			= !empty($data_plat_order['coupon_id']) ? $data_plat_order['coupon_id'] : 0;
		$data_plat_order['system_coupon_price'] 		= !empty($data_plat_order['coupon_price']) ? $data_plat_order['coupon_price'] : 0;
		$data_plat_order['merchant_coupon_id'] 			= !empty($data_plat_order['card_id']) ? $data_plat_order['card_id'] : 0;
		$data_plat_order['merchant_coupon_price'] 			= !empty($data_plat_order['card_price']) ? $data_plat_order['card_price'] : 0;
		$data_plat_order['merchant_balance_pay'] 	= !empty($data_plat_order['merchant_balance']) ? $data_plat_order['merchant_balance'] : 0;
		$data_plat_order['merchant_balance_give'] 	= !empty($data_plat_order['card_give_money']) ? $data_plat_order['card_give_money'] : 0;
		$data_plat_order['merchant_discount'] 	= !empty($data_plat_order['card_discount']) ? $data_plat_order['card_discount'] : 0;
		$data_plat_order['merchant_discount_money'] 	= !empty($data_plat_order['merchant_discount_money']) ? $data_plat_order['merchant_discount_money'] : 0;
		$data_plat_order['system_balance'] 		= !empty($data_plat_order['balance_pay']) ? $data_plat_order['balance_pay'] : 0;
		$data_plat_order['system_score']  	= !empty($data_plat_order['score_used_count'])?$data_plat_order['score_used_count']:0;
		$data_plat_order['system_score_money']     	= !empty($data_plat_order['score_deducte'])?(float)$data_plat_order['score_deducte']:0;
		$data_plat_order['score_discount_type']      = !empty($order_info['score_discount_type']) ? $order_info['score_discount_type'] : 0;
		$data_plat_order['union_mer_id']      = !empty($data_plat_order['union_mer_id']) ? $data_plat_order['union_mer_id'] : 0;
		$data_plat_order['last_time'] 			= $_SERVER['REQUEST_TIME'];
		$data_plat_order['submit_order_time'] 	= $_SERVER['REQUEST_TIME'];


		if ($this->where($condition_shop_order)->data($data_plat_order)->save() !== false) {
            // 处理下服务快派订单
            if ($order_info['business_type'] && $order_info['business_type'] == 'service'){
                /*---更新系统订单信息 19/1/24-----服务快派--*/
                D('System_order')->reg_group_pay_order($order_info['order_id'],$data_plat_order, 'service');
            }
			return array('error_code' => false, 'msg' => L_('保存订单成功！'));
		} else {
			return array('error_code' => true, 'msg' => L_('保存订单失败！请重试或联系管理员。'));
		}
	}

	public function wap_after_pay_before($order_info){
		$order_param = array(
				'order_id' => $order_info['order_id'],
				'mer_id' => $order_info['mer_id'],
				'pay_type' => '',
				'third_id' => '',
				'is_mobile' => $order_info['is_mobile'],
				'order_type' => $order_info['business_type']?$order_info['business_type']:'plat',
		);
		$result_after_pay = $this->after_pay($order_param);
		if($result_after_pay['error']){
			return array('error_code'=>true,'msg'=>$result_after_pay['msg']);
		}
		return array('error_code'=>false,'msg'=>L_('支付成功！'),'url'=>str_replace('/source/','/',$result_after_pay['url']));
	}



	public function after_pay($order_param){
        fdump_api($order_param,'order_param_30',true);
		if ($order_param['pay_type'] != '') {
			$where['orderid'] = $order_param['order_id'];
		} else {
			$where['order_id'] = $order_param['order_id'];
		}
		$now_order = $this->field(true)->where($where)->find();
        if (empty($now_order)) {
			return array('error' => 1, 'msg' => L_('当前订单不存在！'));
		}

        $uniqueKey = md5($now_order['business_type'] . '_' . $now_order['business_id'] . '_platOrderAfterPay');
        $lockRs = setLock($uniqueKey, 10);
        if ($lockRs['error_no']) {
            fdump_api([$now_order['business_id'], $lockRs], 'plat_order_after_pay', 1);
            return array('error' => 1, 'msg' => $lockRs['error_msg']);
        }

		$business_type = $now_order['business_type'];
		$business_order_table = D(ucfirst($business_type).'_order');
		if ($now_order['paid'] == 1){
			$url = $business_order_table->get_order_url($now_order['business_id'],$order_param['is_mobile']);
			return array('error' => 1, 'msg' => L_('该订单已付款！'), 'url' => $url);
		} else {
            // 得到当前用户信息，不将session作为调用值，因为可能会失效或错误。
            $tOrder = $business_order_table->where(array('order_id' => $now_order['business_id']))->find();
            if ($business_type == 'foodshop' && $tOrder && $tOrder['status'] > 2) {
                $url = $business_order_table->get_order_url($now_order['business_id'],$order_param['is_mobile']);
                return array('error' => 1, 'msg' => L_('该订单已付款！'), 'url' => $url);
            } elseif ($business_type == 'service' && !$tOrder) {
                $tOrder = D('Service_user_publish')->where(array('publish_id' => $now_order['business_id']))->find();
                $tOrder['real_orderid'] = $tOrder['order_sn'];
                if (!$now_order['order_name'] && $tOrder['catgory_type']==2) {
                    $now_order['order_name'] = '帮我买订单';
                } elseif (!$now_order['order_name'] && $tOrder['catgory_type']==3) {
                    $now_order['order_name'] = '帮我送订单';
                }
            }
            
			$order_param['mer_id'] = $tOrder['mer_id'];
			
			//社区非缴费类的特殊处理
			if($now_order['business_type'] == 'house_village_pay'){
				$pay_uid = M('House_village_pay_order')->where(['order_id'=>$now_order['business_id']])->getField('pay_uid');
				fdump_api($pay_uid,'444',true);
                if($pay_uid){
                    $now_order['uid'] = $pay_uid;
                }
			}
			
			$now_user = D('User')->get_user($now_order['uid']);
			if($now_order['uid']&&empty($now_user)){
				return array('error'=>1,'msg'=>L_('没有查找到此订单归属的用户，请联系管理员！'));
			}

			if($now_order['merchant_coupon_id']){
				$now_coupon = D('Card_new_coupon')->get_coupon_by_id($now_order['merchant_coupon_id']);
				if(empty($now_coupon)){
					return $this->wap_after_pay_error($now_order,$order_param,L_('您选择的优惠券不存在！'));
				}
			}

			//判断平台优惠券
			if($now_order['system_coupon_id']){
				$now_coupon = D('System_coupon')->get_coupon_by_id($now_order['system_coupon_id']);
				if(empty($now_coupon)){
					return $this->wap_after_pay_error($now_order,$order_param,L_('您选择的优惠券不存在！'));
				}
			}

			//判断会员卡余额
			$merchant_balance = floatval($now_order['merchant_balance_pay']);
			if($merchant_balance){
				if ($now_order['union_mer_id']) {
					$user_merchant_balance = D('Card_new')->get_card_by_uid_and_mer_id($now_order['uid'],$now_order['union_mer_id']);
				}else{
					$user_merchant_balance = D('Card_new')->get_card_by_uid_and_mer_id($now_order['uid'],$order_param['mer_id']);
				}
				if($user_merchant_balance['card_money'] < $merchant_balance){
					return $this->wap_after_pay_error($now_order,$order_param,L_('您的会员卡余额不够此次支付！'));
				}
			}

			$card_give_money = floatval($now_order['merchant_balance_give']);
			
			if($card_give_money){
				if ($now_order['union_mer_id']) {
					$user_merchant_balance = D('Card_new')->get_card_by_uid_and_mer_id($now_order['uid'],$now_order['union_mer_id']);
				}else{
					$user_merchant_balance = D('Card_new')->get_card_by_uid_and_mer_id($now_order['uid'],$order_param['mer_id']);
				}
				if($user_merchant_balance['card_money_give'] < $card_give_money){
					return $this->wap_after_pay_error($now_order,$order_param,L_('您的会员卡余额不够此次支付！'));
				}
			}
			//判断帐户余额
			$balance_pay = floatval($now_order['system_balance']);
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

			//如果使用了商家优惠券
//			if($now_order['card_id']){
//				$use_result = D('Member_card_coupon')->user_card($now_order['card_id'],$now_order['mer_id'],$now_order['uid']);
//				if($use_result['error_code']){
//					return array('error'=>1,'msg'=>$use_result['msg']);
//				}
//			}

			if($now_order['merchant_coupon_id']){
				$use_result = D('Card_new_coupon')->user_coupon($now_order['merchant_coupon_id'],$now_order['business_id'],$business_type,$order_param['mer_id'],$now_order['uid']);
				if($use_result['error_code']){
					return array('error'=>1,'msg'=>$use_result['msg']);
				}
			}
			//如果使用了平台优惠券
			if($now_order['system_coupon_id']){

				$use_result = D('System_coupon')->user_coupon($now_order['system_coupon_id'],$now_order['business_id'],$business_type,$order_param['mer_id'],$now_order['uid']);
				if($use_result['error_code']){
					return array('error'=>1,'msg'=>$use_result['msg']);
				}
			}
			//如果使用了平台E卡
			if(!empty($now_order['ecard_password'])){
				D('Ecard_coupon')->add_ecard_log($now_order,'plat');
			}


			//如果用户使用了积分抵扣，则扣除相应的积分
			//判断积分数量是否正确
			$score_used_count=$now_order['system_score'];
			if($now_user['score_count']<$score_used_count && $score_used_count > 0){
				return array('error'=>1,'msg'=>L_('保存订单失败！请重试或联系管理员。'));
			}
			if($score_used_count>0){
				$desc = L_("购买 X1商品 扣除X2",array("X1" => $now_order['order_name'],"X2" => C('config.score_name'))); 
				if ($tOrder['real_orderid']) {
					$desc .= L_('，订单编号') . $tOrder['real_orderid'];
				}elseif($tOrder['order_id']){
					$desc .= L_('，订单编号') . $tOrder['order_id'];
				}elseif($tOrder['cashier_id']){
					$desc .= L_('，订单编号') . $tOrder['cashier_id'];
				}
				$use_result = D('User')->user_score($now_order['uid'],$score_used_count,$desc);
				if($use_result['error_code']){
					return array('error'=>1,'msg'=>$use_result['msg']);
				}
			}

			//如果使用会员卡余额
			if($merchant_balance){
                $desc = L_("购买 X1商品 扣除会员卡余额",array("X1" => $now_order['order_name']));
                if ($tOrder['real_orderid']) {
                    $desc .= L_('，订单编号') . $tOrder['real_orderid'];
                }elseif($tOrder['order_id']){
                    $desc .= L_('，订单编号') . $tOrder['order_id'];
                }elseif($tOrder['cashier_id']){
                    $desc .= L_('，订单编号') . $tOrder['cashier_id'];
                }
                if ($now_order['union_mer_id']) {
			    	$use_result = D('Card_new')->use_money($now_order['uid'],$now_order['union_mer_id'],$merchant_balance,$desc);
                }else{
			    	$use_result = D('Card_new')->use_money($now_order['uid'],$order_param['mer_id'],$merchant_balance,$desc);
                }
				
				if($use_result['error_code']){
					return array('error'=>1,'msg'=>$use_result['msg']);
				}
			}

			if($card_give_money){
                $desc = L_("购买 X1商品 扣除会员卡赠送余额",array("X1" => $now_order['order_name']));
                if ($tOrder['real_orderid']) {
                    $desc .= L_('，订单编号') . $tOrder['real_orderid'];
                }elseif($tOrder['order_id']){
                    $desc .= L_('，订单编号') . $tOrder['order_id'];
                }elseif($tOrder['cashier_id']){
                    $desc .= L_('，订单编号') . $tOrder['cashier_id'];
                }
                if ($now_order['union_mer_id']) {
					$use_result = D('Card_new')->use_give_money($now_order['uid'],$now_order['union_mer_id'],$card_give_money,$desc);
                }else{
					$use_result = D('Card_new')->use_give_money($now_order['uid'],$order_param['mer_id'],$card_give_money,$desc);
                }
				if($use_result['error_code']){
					return array('error'=>1,'msg'=>$use_result['msg']);
				}
			}
			//如果用户使用了余额支付，则扣除相应的金额。
			if(!empty($balance_pay)){
				$desc = L_("购买 X1商品 扣除余额",array("X1" => $now_order['order_name'])); 
				if ($tOrder['real_orderid']) {
					$desc .= L_('，订单编号') . $tOrder['real_orderid'];
				}elseif($tOrder['order_id']){
					$desc .= L_('，订单编号') . $tOrder['order_id'];
				}elseif($tOrder['cashier_id']){
					$desc .= L_('，订单编号') . $tOrder['cashier_id'];
				}
				$use_result = D('User')->user_money($now_order['uid'],$balance_pay,$desc);
				if($use_result['error_code']){
					return array('error'=>1,'msg'=>$use_result['msg']);
				}
			}
			
			//扣除企业预存款
            if (!empty($qiye_balance)) {
                $qiye_result = D('User')->use_qiye_money($now_order['uid'], $qiye_balance, $now_order['order_id'], L_("购买X1商品，扣除企业预存款，订单编号X2", array("X1" => $alias_name, "X2" => $now_order['real_orderid'])),'shop');
                if ($qiye_result['error_code']) {
                    return array('error' => 1, 'msg' => $use_result['msg']);
                }
            }

			$data_order = array();

			$data_order['pay_time'] = isset($order_param['pay_time']) && $order_param['pay_time'] ? strtotime($order_param['pay_time']) : $_SERVER['REQUEST_TIME'];
			$data_order['pay_money'] = floatval($order_param['pay_money']);//在线支付的钱
			$data_order['pay_type'] = $order_param['pay_type'];
			$data_order['third_id'] = $order_param['third_id'];
			$data_order['is_mobile_pay'] = $order_param['is_mobile'];
			$data_order['is_own'] = $order_param['is_own'];
			$data_order['paid'] = 1;
			if($this->where($where)->data($data_order)->save()){
				$now_order = $this->field(true)->where($where)->find();
				if(isset($order_param['sub_mch_id']) && $order_param['sub_mch_id'] >0){
					$now_order['sub_mch_id'] = $order_param['sub_mch_id'];
				}
                // 处理下服务快派订单
                if ($business_type == 'service'){
                    /*----系统订单信息---服务快派订单-*/
	                D('System_order')->save_reg_order_info('service',$now_order['order_id'],$data_order);
                    
                }
                if($business_type == 'mobile_recharge'){
                	D('System_order')->save_reg_order_info('mobile_recharge', $order_param['order_id'], $data_order);
                }

				$business_order_table->after_pay($now_order['business_id'],$now_order);
				$url = $business_order_table->get_order_url($now_order['business_id'],$order_param['is_mobile']);
				return array('error' => 0, 'url' => $url);
			}else{
				return array('error' => 1, 'msg' => L_('修改订单状态失败，请联系系统管理员！'));
			}
		}
	}
	public function get_order_url($order_id,$is_mobile){
		$now_order = $this->where(array('order_id'=>$order_id,'orderid'=>$order_id,'_logic'=>'OR'))->find();

		$business_order_table = D(ucfirst($now_order['business_type']).'_order');

		return $business_order_table->get_order_url($now_order['business_id'],$is_mobile);
	}

	//支付时，金额不够，记录到帐号
	public function wap_after_pay_error($now_order,$order_param,$error_tips){

		//记录充值的金额，因为 Pay/return_url 处没有返回order的具体信息，故在此调用。
        $user_result['error_code'] = 0;
        if($order_param['pay_money'] > 0){
            $alreadyMoney = M("User_money_list")
                ->where(['money'=>$order_param['pay_money'],'uid'=>$now_order['uid'],'ask'=> 15, 'ask_id'=>$now_order['order_id']])//15 plat_order
                ->order('pigcms_id desc')
                ->find();
            if(empty($alreadyMoney)){
                //$user_result = D('User')->add_money($now_order['uid'],$order_param['pay_money'],L_("支付订单：X1发生错误！原因是：X2",array("X1" => $now_order['order_id'],"X2" => $error_tips)),15,$now_order['order_id']);
            }
        }
		$this->where(array('order_id'=>$now_order['order_id']))->setField('status',3);
		if($order_param['pay_type']=='weixin'&&ACTION_NAME=='return_url'){
			exit('<xml><return_code><![CDATA[SUCCESS]]></return_code></xml>');
		}
		if($user_result['error_code']){
			return array('error'=>1,'msg'=>$user_result['msg']);
		}else{
			if($order_param['is_mobile']){
				$return_url = str_replace('/source/','/',U('My/group_order',array('order_id'=>$now_order['order_id'])));
			}else{
				$return_url = U('User/Index/group_order_view',array('order_id'=>$now_order['order_id']));
			}
			return array('error'=>1,'msg'=>$error_tips.L_('，已将您充值的金额添加到您的余额内。'),'url'=>$return_url);
		}
	}

	//param array（business_id,business_type）
	public function order_refund($param, $operation_info = '',$refund_money=''){
		$now_order = $this->get_order_by_business_id($param);
		
		$business_order_table = D(ucfirst($now_order['business_type']).'_order');
		$tOrder = $business_order_table->where(array('order_id' => $now_order['business_id']))->find();
		//if(!$now_order['paid']){
		//	return array('error'=>1,'msg'=>'该订单未支付不能退款');
		//}
		$data_plat_order['is_refund'] = 1;
		$order_id = $now_order['order_id'];
		/*平台E卡退款*/
		if($now_order['use_ecard_price']>0){
			$desc=$operation_info . $now_order['order_name'] . '商品退款,增加'.$this->config['ecard_alias_name'].'转退余额,订单编号' . $tOrder['real_orderid'];
			$result = D('User')->add_money($now_order['uid'],$now_order['use_ecard_price'], $desc);
			$param = array('refund_time' => time());
			if($result['error_code']){
				$param['err_msg'] = $result['msg'];
			} else {
				$param['refund_id'] = $now_order['order_id'];
			}

			$data_plat_order['order_id'] = $now_order['order_id'];
			$data_plat_order['refund_detail'] = serialize($param);
			$result['error_code'] && $data_plat_order['is_refund'] = 4;
			$this->data($data_plat_order)->save();
			if ($result['error_code']) {
				return array('error'=>1,'msg'=>$result['msg']);
			}
			$refund_msg = $this->config['ecard_alias_name'].'转退余额退款成功 |';
		}

		if($now_order['system_balance']>0){
			if($refund_money !=''){
				$result = D('User')->add_money($now_order['uid'],$refund_money, $operation_info . $now_order['order_name'] . '商品退款,增加余额,订单编号' . $tOrder['order_id']);
			}else{
				$result = D('User')->add_money($now_order['uid'],$now_order['system_balance'], $operation_info . $now_order['order_name'] . '商品退款,增加余额,订单编号' . $tOrder['real_orderid']);
			}

			$param = array('refund_time' => time());
			if($result['error_code']){
				$param['err_msg'] = $result['msg'];
			} else {
				$param['refund_id'] = $now_order['order_id'];
			}

			$data_plat_order['order_id'] = $now_order['order_id'];
			$data_plat_order['refund_detail'] = serialize($param);
			$result['error_code'] && $data_plat_order['is_refund'] = 4;
			$this->data($data_plat_order)->save();
			if ($result['error_code']) {
				return array('error'=>1,'msg'=>$result['msg']);
			}
			$refund_msg = ' 平台余额退款成功 |';
		}
		if($now_order['system_score']>0 && $now_order['system_score_money']>0){
			$result = D('User')->add_score($now_order['uid'],$now_order['system_score'], L_("X1商品退款 X2回滚，订单编号X3",array("X1" => $operation_info . $now_order['order_name'],"X2" => C('config.score_name'),"X3" => $tOrder['real_orderid']))); 
			$param = array('refund_time' => time());
			if ($result['error_code']) {
				$param['err_msg'] = $result['msg'];
			} else {
				$param['refund_id'] = $now_order['order_id'];
			}
			$data_plat_order['order_id'] = $now_order['order_id'];
			$data_plat_order['refund_detail'] = serialize($param);
			$result['error_code'] && $data_plat_order['is_refund'] = 4;
			$this->data($data_plat_order)->save();
			if ($result['error_code']) {
				return array('error'=>1,'msg'=>$result['msg']);

			}
			$refund_msg .= ' '.$result['msg'].'|';
		}
		if ($now_order['merchant_balance_pay'] != '0.00'||$now_order['merchant_balance_give']!='0.00') {
			$result = D('Card_new')->add_user_money($now_order['mer_id'],$now_order['uid'],$now_order['merchant_balance_pay'],$now_order['merchant_balance_give'],0, $operation_info . $now_order['order_name'] . '商品退款,增加余额,订单编号' . $tOrder['real_orderid'], $operation_info . $now_order['order_name'] . '商品退款,增加赠送余额,订单编号' . $tOrder['real_orderid']);

			$param = array('refund_time' => time());
			if ($result['error_code']) {
				$param['err_msg'] = $result['msg'];
			} else {
				$param['refund_id'] = $now_order['order_id'];
			}

			$data_shop_order['order_id'] = $now_order['order_id'];
			$data_shop_order['refund_detail'] = serialize($param);
			$result['error_code'] && $data_plat_order['is_refund'] = 4;
			$this->data($data_shop_order)->save();
			if ($result['error_code']) {
				return array('error'=>1,'msg'=>$result['msg']);
			}
			$refund_msg .= $result['msg'] .'|';
		}

		if($now_order['pay_money']>0){
			$now_order['business_param'] = unserialize($now_order['business_param']);
			if ($now_order['is_own'] && $now_order['business_param']['mer_id'] ) {
				$pay_method = array();
				$merchant_ownpay = D('Merchant_ownpay')->field('mer_id', true)->where(array('mer_id' => $now_order['business_param']['mer_id']))->find();
				foreach($merchant_ownpay as $ownKey=>$ownValue){
					$ownValueArr = unserialize($ownValue);
					if($ownValueArr['open']){
						$ownValueArr['is_own'] = true;
						$pay_method[$ownKey] = array('name'=>$this->getPayName($ownKey),'config'=>$ownValueArr);
					}
				}
				$now_merchant = D('Merchant')->get_info($now_order['mer_id']);
				if ($now_merchant['sub_mch_refund'] && C('config.open_sub_mchid') && $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0) {
					$pay_method['weixin']['config']['pay_weixin_appid'] = C('config.pay_weixin_appid');
					$pay_method['weixin']['config']['pay_weixin_appsecret'] =  C('config.pay_weixin_appsecret');
					$pay_method['weixin']['config']['pay_weixin_mchid'] = C('config.pay_weixin_sp_mchid');
					$pay_method['weixin']['config']['pay_weixin_key'] =C('config.pay_weixin_sp_key') ;
					$pay_method['weixin']['config']['sub_mch_id'] = $now_merchant['sub_mch_id'];
					$pay_method['weixin']['config']['pay_weixin_client_cert'] = C('config.pay_weixin_sp_client_cert');
					$pay_method['weixin']['config']['pay_weixin_client_key'] =C('config.pay_weixin_sp_client_key');
					$pay_method['weixin']['config']['is_own'] = 2 ;
				}
			} else {

                if($now_order['is_mobile_pay']==2){
                    $pay_method = D('Config')->get_pay_method(0,0,1,1);
                } else {
                    $pay_method = D('Config')->get_pay_method(0,0,1);
                }
			}

			$pay_class_name = ucfirst($now_order['pay_type']);
			if ($pay_class_name == 'Alipay' && $now_order['is_mobile_pay'] == 2 && C('config.new_pay_alipay_app_public_key') != '' &&C('config.new_pay_alipay_app_appid')  != '' && C('config.new_pay_alipay_app_private_key')  != '') {
				$pay_class_name = 'AlipayApp';
				$pay_method['alipay']['config']['new_pay_alipay_app_appid'] = C('config.new_pay_alipay_app_appid') ;
				$pay_method['alipay']['config']['new_pay_alipay_app_private_key'] =  C('config.new_pay_alipay_app_private_key') ;
				$pay_method['alipay']['config']['new_pay_alipay_app_public_key'] =C('config.new_pay_alipay_app_public_key');
			}

			if (empty($pay_method)) {
				return array('error'=>1,'msg'=>L_('系统管理员没开启任一一种支付方式'));
			}


            if (in_array($now_order['pay_type'], ['hqpay', 'hqpay_wx', 'hqpay_al'])) {
                $pay_class_name = 'Hqpay';
                empty($pay_method[$now_order['pay_type']]) && $pay_method[$now_order['pay_type']] = 1;
            }
			if (empty($pay_method[$now_order['pay_type']])) {
				return array('error'=>1,'msg'=>L_('您选择的支付方式不存在，请更新支付方式！'));
			}

			// $pay_class_name = ucfirst($now_order['pay_type']);
			$import_result = import('@.ORG.pay.'.$pay_class_name);
			if(empty($import_result)){
				return array('error'=>1,'msg'=>L_('系统管理员暂未开启该支付方式，请更换其他的支付方式'));
			}
			$now_order['order_type'] = 'plat';
			$now_order['order_id'] = $now_order['orderid'];
			if($now_order['is_mobile_pay']==3){
				//判断是平台小程序，还是跑腿小程序独立支付
				if($now_order['business_type'] == 'service' && C('config.pay_wxapp_paotui_mchid')){
					$pay_method[$now_order['pay_type']]['config'] =array(
						'pay_weixin_appid'=>C('config.pay_wxapp_paotui_appid'),
						'pay_weixin_key'=>C('config.pay_wxapp_paotui_key'),
						'pay_weixin_mchid'=>C('config.pay_wxapp_paotui_mchid'),
						'pay_weixin_appsecret'=>C('config.pay_wxapp_paotui_appsecret'),
					);
					C('config.pay_weixin_client_cert',C('config.pay_wxapp_paotui_cert'));
					C('config.pay_weixin_client_key',C('config.pay_wxapp_paotui_cert_key'));
				}else if(C('config.pay_wxapp_appid')){
					$pay_method[$now_order['pay_type']]['config'] =array(
						'pay_weixin_appid'=>C('config.pay_wxapp_appid'),
						'pay_weixin_key'=>C('config.pay_wxapp_key'),
						'pay_weixin_mchid'=>C('config.pay_wxapp_mchid'),
						'pay_weixin_appsecret'=>C('config.pay_wxapp_appsecret'),
				     );
					C('config.pay_weixin_client_cert',C('config.pay_wxapp_cert'));
					C('config.pay_weixin_client_key',C('config.pay_wxapp_cert_key'));
				}
			}
			$now_order['payment_money'] = $now_order['pay_money'];
			$pay_class = new $pay_class_name($now_order, $now_order['pay_money'], $now_order['pay_type'], $pay_method[$now_order['pay_type']]['config'], $this->user_session, 1);
			$go_refund_param = $pay_class->refund();

			$now_order['order_id'] = $order_id;
			$data_plat_order['order_id'] = $order_id;
			$data_plat_order['refund_detail'] = serialize($go_refund_param['refund_param']);
			if (empty($go_refund_param['error']) && $go_refund_param['type'] == 'ok') {
				$data_plat_order['is_refund'] = 1;
			} else {
                $data_plat_order['is_refund'] = 4;
            }
			if ($now_order['is_refund']==1) {
				// 如果是退过款的重复性退款 只记录退款失败原因，依旧保持退款成功状态
				$data_plat_order['is_refund'] = 1;
			}
			$data_plat_order['last_time'] = time();
			$this->data($data_plat_order)->save();

			if($data_plat_order['is_refund'] != 1){
				return array('error'=>1,'msg'=>$go_refund_param['msg']);
			}else{
				$refund_msg .="在线支付退款成功| ";
			}

		}

		if($data_plat_order['is_refund'] != 4){
			return array('error'=>0,'msg'=>$refund_msg);
		}

	}

    /**
     * 充电桩退款
     * @author lijie
     * @date_time 2021/07/21
     * @param $param
     * @param string $operation_info
     * @param string $refund_money
     * @return array
     */
    public function pile_order_refund($param, $operation_info = '',$refund_money=''){
        if($param['pile_pay_order_id'] && $param['pile_pay_order_id']){
            $pilePayOrder = M('House_village_pile_pay_order')->where(['id'=>$param['pile_pay_order_id']])->find();
            if($pilePayOrder['pay_type']==5){
                //充电桩特有平台余额
                $whereTmpArr=array('uid'=>$pilePayOrder['uid'],'village_id'=>$pilePayOrder['village_id'],'pile_pay_order_id'=>$pilePayOrder['id'],'opt_type_flag'=>'order_refund_balance');
                $user_balance_log= M('House_village_pile_user_balance_log')->where($whereTmpArr)->find();
                if(!empty($user_balance_log)){
                    $refund_msg ="充电桩余额已退款成功！";
                    return array('error'=>0,'msg'=>$refund_msg);
                }
                $pile_user_balance=M('House_village_pile_user_balance')->where(array('uid'=>$pilePayOrder['uid']))->find();
                if($pile_user_balance){
                    $saveArr=array();
                    $saveArr['current_money']=$pile_user_balance['current_money']+$refund_money;
                    $saveArr['add_money']=$pile_user_balance['add_money']+$refund_money;
                    $saveArr['update_time']=time();
                    M('House_village_pile_user_balance')->where(array('uid'=>$pilePayOrder['uid']))->data($saveArr)->save();
                    //添加记录
                    $balanceLogArr=array('uid'=>$pilePayOrder['uid'],'village_id'=>$pilePayOrder['village_id']);
                    $balanceLogArr['before_money']=$pile_user_balance['current_money'];
                    $balanceLogArr['money']=$refund_money;
                    $balanceLogArr['after_money']=$saveArr['current_money'];
                    $balanceLogArr['opt_type']=1;
                    $balanceLogArr['opt_type_flag']='order_refund_balance';
                    $balanceLogArr['remarks']='充电结束退款';
                    $balanceLogArr['add_time']=time();
                    $balanceLogArr['equipment_id']=$pilePayOrder['equipment_id'];
                    $balanceLogArr['pay_order_no']=$pilePayOrder['order_no'];
                    $balanceLogArr['pile_pay_order_id']=$pilePayOrder['id'];
                    M('House_village_pile_user_balance_log')->data($balanceLogArr)->add();
                    $refund_msg ="充电桩余额退款成功！";
                    return array('error'=>0,'msg'=>$refund_msg);
                }
            }
        }
        $now_order = $this->get_order_by_business_id($param);
        fdump_api(['tip' => '充电桩退款','param' => $param,'operation_info' => $operation_info,'refund_money' => $refund_money,'now_order' => $now_order],'pile/pileOrderRefundLog',1);
        $business_order_table = D('House_village_pay_order');
        $tOrder = $business_order_table->where(array('order_id' => $now_order['business_id']))->find();
        $tOrder['order_type'] = 'pile_refund';
        $tOrder['desc'] = 'tcp充电桩充电退款';
        $tOrder['refund_money'] = $refund_money;
        if ($now_order && $now_order['orderid']) {
            $pay_info = M('Pay_order_info')->where(['orderid' => $now_order['orderid']])->find();
            if (in_array($pay_info['channel'], ['scrcu', 'scrcuCash', 'alipay', 'wechat', 'chinaums','lakala'])) {
                $system_balance_tmp = 0;
                if ($pay_info['current_system_balance'] > 0) {
                    $refund_money_tmp = $refund_money * 100;
                    $refund_money_tmp = round($refund_money_tmp);
                    if ($refund_money_tmp >= $pay_info['money']) {
                        $system_balance_tmp = $pay_info['current_system_balance'] / 100;
                        $refund_money = $pay_info['paid_money'] / 100;
                        $refund_money = round($refund_money, 2);
                    }
                }
                $refund = invoke_v20_service('\app\pay\model\service\PayService/refund', ['order_no' => $now_order['orderid'], 'refund_money' => $refund_money]);
                fdump_api(['tip' => '执行退款', 'pay_info' => $pay_info, 'url' => '\app\pay\model\service\PayService/refund', 'order_no' => $now_order['orderid'], 'refund_money' => $refund_money, 'refund' => $refund], 'pile/pileOrderRefundLog', 1);
                if (isset($refund['data']['retval']['refund_no']) && !empty($refund['data']['retval']['refund_no'])) {
                    if ($pay_info['channel'] != 'alipay' && $pay_info['is_own'] > 0) {
                        $tOrder['refund_money'] = 0;
                    }
                    $is_chinaums_divide = 0;
                    if ($tOrder['refund_money'] > 0 && $pay_info && isset($pay_info['pay_config']) && $pay_info['pay_config'] && $pay_info['channel'] == 'chinaums' && $pay_info['env'] == 'wechat_mini') {
                        $payOrderConfig = json_decode($pay_info['pay_config'], true);
                        if ($payOrderConfig && isset($payOrderConfig['without_asset']) && $payOrderConfig['without_asset'] == 1) {
                            $tOrder['refund_money'] = 0;
                            $is_chinaums_divide = 1;
                        }
                    }
                    if(isset($pay_info['channel'])&& $pay_info['channel'] == 'lakala' && $pay_info['pay_config'] && $pay_info['paid_extra'] && $pay_info['paid_money']>0){
                        $tOrder['refund_money']=0;
                    }
                    if ($is_chinaums_divide < 1 && $pay_info && isset($pay_info['channel']) && $pay_info['channel'] == 'chinaums' && $pay_info['chinaums_merchant_already_get'] > 0) {
                        $chinaums_divide_record = array('village_id' => $tOrder['village_id'], 'chinaums_zg_mid' => '');
                        $village_pay_config = M('House_village_pay_config')->where(array('village_id' => $tOrder['village_id']))->find();
                        $village_pile_pay_order = M('House_village_pile_pay_order')->where(['order_id' => $now_order['business_id']])->field('*')->find();
                        if ($now_order['business_type'] == 'pile' && $village_pay_config && isset($village_pay_config['chinaums_zg_mid']) && $village_pay_config['chinaums_zg_mid']) {
                            $villageChinaumsDivideRecordDb = M('Village_chinaums_divide_record');
                            $tOrder['refund_money'] = 0;
                            $divide_refund_money = $refund_money * 100;
                            $whereDivideRecord = array('pay_order_info_id' => $pay_info['id'], 'village_id' => $tOrder['village_id'], 'b_order_id' => $village_pile_pay_order['id'], 'order_table_type' => 'house_village_pile_pay_order');
                            $haveDivideRecord = $villageChinaumsDivideRecordDb->where($whereDivideRecord)->find();
                            if (!empty($haveDivideRecord) && $haveDivideRecord['dividend_status'] < 3) {
                                //还未成功分账 则直接将退的钱保存上
                                $divide_refund_money = $divide_refund_money + $haveDivideRecord['refund_money'];
                                $d_refundInfo = array('refund_money' => $divide_refund_money, 'refund_time' => time());
                                $villageChinaumsDivideRecordDb->where(array('id' => $haveDivideRecord['id']))->data($d_refundInfo)->save();
                            } elseif (!empty($haveDivideRecord) && $haveDivideRecord['dividend_status'] > 2) {
                                //之前有分账成功的 另加一条退款
                                $chinaums_divide_record['chinaums_zg_mid'] = $village_pay_config['chinaums_zg_mid'];
                                $chinaums_divide_record['village_money'] = 0;
                                $chinaums_divide_record['platform_money'] = 0;
                                $chinaums_divide_record['dividend_status'] = 1;
                                $chinaums_divide_record['pay_order_info_id'] = $pay_info['id'];
                                $chinaums_divide_record['b_order_id'] = $village_pile_pay_order['id'];
                                $chinaums_divide_record['order_table_type'] = 'house_village_pile_pay_order';
                                $chinaums_divide_record['add_time'] = time();
                                $chinaums_divide_record['refund_money'] = $divide_refund_money;
                                $chinaums_divide_record['refund_time'] = time();
                                $villageChinaumsDivideRecordDb->add($chinaums_divide_record);
                            }
                        }
                    }
                    D('SystemBill')->bill_method(5, $tOrder, 0);
                    $data_plat_order['order_id'] = $now_order['order_id'];
                    $data_plat_order['is_refund'] = 1;
                    $data_plat_order['refund_detail'] = serialize(['refund_time' => time(), 'refund_id' => $now_order['order_id']]);
                    $this->data($data_plat_order)->save();
                    $refund_msg = "在线支付退款成功| ";
                    if ($system_balance_tmp > 0) {
                        $result = D('User')->add_money($now_order['uid'], $system_balance_tmp, $operation_info . $now_order['order_name'] . '充电退款,增加余额,订单编号' . $tOrder['order_id']);
                        $refund_msg = ' 支付退款成功|';
                    }

            return array('error' => 0, 'msg' => $refund_msg);
        } else {
            if (isset($refund['data']['retval']['msg']) && !empty($refund['data']['retval']['msg'])) {
                $refund_msg = $refund['data']['retval']['msg'];
            } elseif (isset($refund['data']['error_msg']) && !empty($refund['data']['error_msg'])) {
                $refund_msg = $refund['data']['error_msg'];
            } else {
                $refund_msg = "在线支付退款失败| ";
            }
            return array('error' => 1, 'msg' => $refund_msg);
        }
    }
}
        if ($now_order['total_money'] && !$now_order['pay_money'] && !$now_order['system_balance'] && $now_order['business_type']=='pile') {
            $current_system_balance = M('House_village_pile_pay_order')->where(['order_id' => $now_order['business_id']])->getField('current_system_balance');
            if ($current_system_balance) {
                $now_order['system_balance'] = $current_system_balance;
            }
        }

        $data_plat_order['is_refund'] = 1;
        $order_id = $now_order['order_id'];
        /*平台E卡退款*/
        if($now_order['use_ecard_price']>0){
            $desc=$operation_info . $now_order['order_name'] . '充电退款,增加'.$this->config['ecard_alias_name'].'转退余额,订单编号' . $tOrder['order_id'];
            $result = D('User')->add_money($now_order['uid'],$now_order['use_ecard_price'], $desc);
            $param = array('refund_time' => time());
            if($result['error_code']){
                $param['err_msg'] = $result['msg'];
            } else {
                $param['refund_id'] = $now_order['order_id'];
            }

            $data_plat_order['order_id'] = $now_order['order_id'];
            $data_plat_order['refund_detail'] = serialize($param);
            $result['error_code'] && $data_plat_order['is_refund'] = 4;
            $this->data($data_plat_order)->save();
            if ($result['error_code']) {
                return array('error'=>1,'msg'=>$result['msg']);
            }
            $refund_msg = $this->config['ecard_alias_name'].'转退余额退款成功 |';
        }
        fdump_api(['tip' => '充电桩退款','tOrder' => $tOrder],'pile/pileOrderRefundLog',1);

        if($now_order['system_balance']>0){
            if($refund_money !=''){
                $result = D('User')->add_money($now_order['uid'],$refund_money, $operation_info . $now_order['order_name'] . '充电退款,增加余额,订单编号' . $tOrder['order_id']);
            }else{
                $result = D('User')->add_money($now_order['uid'],$now_order['system_balance'], $operation_info . $now_order['order_name'] . '充电退款,增加余额,订单编号' . $tOrder['order_id']);
            }

            $param = array('refund_time' => time());
            if($result['error_code']){
                $param['err_msg'] = $result['msg'];
            } else {
                $param['refund_id'] = $now_order['order_id'];
            }

            $data_plat_order['order_id'] = $now_order['order_id'];
            $data_plat_order['refund_detail'] = serialize($param);
            $result['error_code'] && $data_plat_order['is_refund'] = 4;
            $this->data($data_plat_order)->save();
            if ($result['error_code']) {
                return array('error'=>1,'msg'=>$result['msg']);
            }
            $refund_msg = ' 平台余额退款成功 |';
        }
        if($now_order['system_score']>0 && $now_order['system_score_money']>0){
            $result = D('User')->add_score($now_order['uid'],$now_order['system_score'], L_("X1商品退款 X2回滚，订单编号X3",array("X1" => $operation_info . $now_order['order_name'],"X2" => C('config.score_name'),"X3" => $tOrder['real_orderid'])));
            $param = array('refund_time' => time());
            if ($result['error_code']) {
                $param['err_msg'] = $result['msg'];
            } else {
                $param['refund_id'] = $now_order['order_id'];
            }
            $data_plat_order['order_id'] = $now_order['order_id'];
            $data_plat_order['refund_detail'] = serialize($param);
            $result['error_code'] && $data_plat_order['is_refund'] = 4;
            $this->data($data_plat_order)->save();
            if ($result['error_code']) {
                return array('error'=>1,'msg'=>$result['msg']);

            }
            $refund_msg .= ' '.$result['msg'].'|';
        }
        if($now_order['village_balance']>0){
            $village_money_data=[
                'uid'=>$tOrder['pay_uid'],
                'village_id'=>$tOrder['village_id'],
                'type'=>1,
                'current_village_balance'=>$now_order['village_balance'],
                'role_id'=>0,
                'desc'=>L_("充电桩自动退款，退还小区住户余额，订单编号X1", array("X1" => $tOrder['order_no'])),
                'order_id'=>$tOrder['order_id'],
                'order_type'=>1,
            ];
           // $villageUseResult = $village_user->addVillageUserMoney($village_money_data);
            $refund = invoke_v20_service('\app\community\model\service\StorageService/addVillageUserMoney', ['village_money_data'=>$village_money_data]);
            $param = array('refund_time' => time());
            if ($refund['data']['retval']['error_code']) {
                $param['err_msg'] = $refund['data']['retval']['msg'];
            }elseif(isset($refund['data']['error_msg']) && !empty($refund['data']['error_msg'])){
                $param['err_msg'] = $refund['data']['error_msg'];
            } else {
                $param['refund_id'] = $now_order['order_id'];
            }
            $data_plat_order['order_id'] = $now_order['order_id'];
            $data_plat_order['refund_detail'] = serialize($param);
            $refund['data']['retval']['error_code'] && $data_plat_order['is_refund'] = 4;
            $this->data($data_plat_order)->save();
            if ($refund['data']['retval']['error_code']) {
                return array('error'=>1,'msg'=>$refund['data']['retval']['msg']);
            }elseif(isset($refund['data']['error_msg']) && !empty($refund['data']['error_msg'])){
                return array('error'=>1,'msg'=>$refund['data']['error_msg']);
            }
            $tOrder['refund_money'] = $refund_money-$now_order['village_balance'];
            $tOrder['refund_money']=$tOrder['refund_money']>0 ? $tOrder['refund_money']:0;
            $refund_msg .= ' '.$refund['data']['retval']['msg'].'|';
        }
        if ($now_order['merchant_balance_pay'] != '0.00'||$now_order['merchant_balance_give']!='0.00') {
            $result = D('Card_new')->add_user_money($now_order['mer_id'],$now_order['uid'],$now_order['merchant_balance_pay'],$now_order['merchant_balance_give'],0, $operation_info . $now_order['order_name'] . '商品退款,增加余额,订单编号' . $tOrder['real_orderid'], $operation_info . $now_order['order_name'] . '商品退款,增加赠送余额,订单编号' . $tOrder['real_orderid']);

            $param = array('refund_time' => time());
            if ($result['error_code']) {
                $param['err_msg'] = $result['msg'];
            } else {
                $param['refund_id'] = $now_order['order_id'];

            }

            $data_shop_order['order_id'] = $now_order['order_id'];
            $data_shop_order['refund_detail'] = serialize($param);
            $result['error_code'] && $data_plat_order['is_refund'] = 4;
            $this->data($data_shop_order)->save();
            if ($result['error_code']) {
                return array('error'=>1,'msg'=>$result['msg']);
            }
            $refund_msg .= $result['msg'] .'|';
        }

        if($now_order['pay_money']>0){
            if ($tOrder)
            $now_order['business_param'] = unserialize($now_order['business_param']);
            if ($now_order['is_own'] && $now_order['business_param']['mer_id'] ) {
                $pay_method = array();
                $merchant_ownpay = D('Merchant_ownpay')->field('mer_id', true)->where(array('mer_id' => $now_order['business_param']['mer_id']))->find();
                foreach($merchant_ownpay as $ownKey=>$ownValue){
                    $ownValueArr = unserialize($ownValue);
                    if($ownValueArr['open']){
                        $ownValueArr['is_own'] = true;
                        $pay_method[$ownKey] = array('name'=>$this->getPayName($ownKey),'config'=>$ownValueArr);
                    }
                }
                $now_merchant = D('Merchant')->get_info($now_order['mer_id']);
                if ($now_merchant['sub_mch_refund'] && C('config.open_sub_mchid') && $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0) {
                    $pay_method['weixin']['config']['pay_weixin_appid'] = C('config.pay_weixin_appid');
                    $pay_method['weixin']['config']['pay_weixin_appsecret'] =  C('config.pay_weixin_appsecret');
                    $pay_method['weixin']['config']['pay_weixin_mchid'] = C('config.pay_weixin_sp_mchid');
                    $pay_method['weixin']['config']['pay_weixin_key'] =C('config.pay_weixin_sp_key') ;
                    $pay_method['weixin']['config']['sub_mch_id'] = $now_merchant['sub_mch_id'];
                    $pay_method['weixin']['config']['pay_weixin_client_cert'] = C('config.pay_weixin_sp_client_cert');
                    $pay_method['weixin']['config']['pay_weixin_client_key'] =C('config.pay_weixin_sp_client_key');
                    $pay_method['weixin']['config']['is_own'] = 2 ;
                }
            }
            else {

                if($now_order['is_mobile_pay']==2){
                    $pay_method = D('Config')->get_pay_method(0,0,1,1);
                } else {
                    $pay_method = D('Config')->get_pay_method(0,0,1);
                }
            }

            $pay_class_name = ucfirst($now_order['pay_type']);
            if ($pay_class_name == 'Alipay' && $now_order['is_mobile_pay'] == 2 && C('config.new_pay_alipay_app_public_key') != '' &&C('config.new_pay_alipay_app_appid')  != '' && C('config.new_pay_alipay_app_private_key')  != '') {
                $pay_class_name = 'AlipayApp';
                $pay_method['alipay']['config']['new_pay_alipay_app_appid'] = C('config.new_pay_alipay_app_appid') ;
                $pay_method['alipay']['config']['new_pay_alipay_app_private_key'] =  C('config.new_pay_alipay_app_private_key') ;
                $pay_method['alipay']['config']['new_pay_alipay_app_public_key'] =C('config.new_pay_alipay_app_public_key');
            }

            if (empty($pay_method)) {
                return array('error'=>1,'msg'=>L_('系统管理员没开启任一一种支付方式'));
            }


            if (in_array($now_order['pay_type'], ['hqpay', 'hqpay_wx', 'hqpay_al'])) {
                $pay_class_name = 'Hqpay';
                empty($pay_method[$now_order['pay_type']]) && $pay_method[$now_order['pay_type']] = 1;
            }
            if (empty($pay_method[$now_order['pay_type']])) {
                return array('error'=>1,'msg'=>L_('您选择的支付方式不存在，请更新支付方式！'));
            }

            // $pay_class_name = ucfirst($now_order['pay_type']);
            $import_result = import('@.ORG.pay.'.$pay_class_name);
            if(empty($import_result)){
                return array('error'=>1,'msg'=>L_('系统管理员暂未开启该支付方式，请更换其他的支付方式'));
            }
            $now_order['order_type'] = 'plat';
            $now_order['order_id'] = $now_order['orderid'];
            if($now_order['is_mobile_pay']==3){
                //判断是平台小程序，还是跑腿小程序独立支付
                if($now_order['business_type'] == 'service' && C('config.pay_wxapp_paotui_mchid')){
                    $pay_method[$now_order['pay_type']]['config'] =array(
                        'pay_weixin_appid'=>C('config.pay_wxapp_paotui_appid'),
                        'pay_weixin_key'=>C('config.pay_wxapp_paotui_key'),
                        'pay_weixin_mchid'=>C('config.pay_wxapp_paotui_mchid'),
                        'pay_weixin_appsecret'=>C('config.pay_wxapp_paotui_appsecret'),
                    );
                    C('config.pay_weixin_client_cert',C('config.pay_wxapp_paotui_cert'));
                    C('config.pay_weixin_client_key',C('config.pay_wxapp_paotui_cert_key'));
                }else if(C('config.pay_wxapp_appid')){
                    $pay_method[$now_order['pay_type']]['config'] =array(
                        'pay_weixin_appid'=>C('config.pay_wxapp_appid'),
                        'pay_weixin_key'=>C('config.pay_wxapp_key'),
                        'pay_weixin_mchid'=>C('config.pay_wxapp_mchid'),
                        'pay_weixin_appsecret'=>C('config.pay_wxapp_appsecret'),
                    );
                    C('config.pay_weixin_client_cert',C('config.pay_wxapp_cert'));
                    C('config.pay_weixin_client_key',C('config.pay_wxapp_cert_key'));
                }
            }
            $now_order['payment_money'] = $now_order['pay_money'];
            $pay_class = new $pay_class_name($now_order, $refund_money, $now_order['pay_type'], $pay_method[$now_order['pay_type']]['config'], $this->user_session, 1);
            $go_refund_param = $pay_class->refund();
            fdump_api(['tip' => '充电桩退款','now_order' => $now_order,'pay_type' => $now_order['pay_type'],'pay_method' => $pay_method[$now_order['pay_type']]['config'],'go_refund_param' => $go_refund_param],'pile/pileOrderRefundLog',1);

            $now_order['order_id'] = $order_id;
            $data_plat_order['order_id'] = $order_id;
            $data_plat_order['refund_detail'] = serialize($go_refund_param['refund_param']);
            if (empty($go_refund_param['error']) && $go_refund_param['type'] == 'ok') {
                $data_plat_order['is_refund'] = 1;
            } else {
                $data_plat_order['is_refund'] = 4;
            }
            if ($now_order['is_refund']==1) {
                // 如果是退过款的重复性退款 只记录退款失败原因，依旧保持退款成功状态
                $data_plat_order['is_refund'] = 1;
            }
            $data_plat_order['last_time'] = time();
            $this->data($data_plat_order)->save();

            if($data_plat_order['is_refund'] != 1){
                return array('error'=>1,'msg'=>$go_refund_param['msg']);
            }else{
                $refund_msg .="在线支付退款成功| ";
            }

        }
        if($now_order['business_type']=='pile'){
            $village_pile_pay_order= M('House_village_pile_pay_order')->where(['order_id' => $now_order['business_id']])->field('*')->find();
            $whereArr=array();
            $whereArr['village_id']=$village_pile_pay_order['village_id'];
            $whereArr['source_from']=1;
            $whereArr['order_id']=$village_pile_pay_order['id'];
            $whereArr['table_name']='house_village_pile_pay_order';
            $paid_order_record=D('Paid_order_record')->where($whereArr)->find();
            if(!empty($paid_order_record)){
                $record_refund_money=$refund_money+$paid_order_record['refund_money'];
                $record_refund_money=round($record_refund_money,2);
                $all_record__pay_money=$paid_order_record['balance_money']+$paid_order_record['pay_money'];
                $refund_status=1;
                if($record_refund_money>=$all_record__pay_money){
                    $refund_status=2;
                }
                $save_record_data=array('refund_status'=>$refund_status,'refund_money'=>$record_refund_money);
                $save_record_data['last_refund_time']=time();
                D('Paid_order_record')->where(array('id'=>$paid_order_record['id']))->data($save_record_data)->save();
            }
        }
        if($data_plat_order['is_refund'] != 4){
            $res = D('SystemBill')->bill_method(5,$tOrder,0);
            return array('error'=>0,'msg'=>$refund_msg);
        }

    }


    /**
     * 新版物业收费退款
     * @author:zhubaodi
     * @date_time: 2021/9/15 17:42
     * @param $param
     * @param string $operation_info
     * @param string $refund_money
     * @return array
     */
    public function new_village_order_refund($param, $operation_info = '',$refund_money='',$pay_type=0){

        $now_order = $this->get_order_by_business_id($param);

        fdump_api($param,'23433',true);
        fdump_api($now_order,'23433',true);
        $business_order_table = D('House_new_pay_order');
        $tOrder = $business_order_table->where(array('summary_id' => $now_order['business_id']))->find();
        if (!empty($pay_type)){
            $tOrder['order_type'] = 'new_village_refund';
            $tOrder['desc'] = $now_order['order_name'].'退款';
            $tOrder['refund_money'] = $refund_money;
            $res = D('Village_money_list')->use_money($tOrder['village_id'],$tOrder['refund_money'],$tOrder['order_type'],$tOrder['desc'],$now_order['order_id']);
        }
        if(!empty($tOrder)){
            $whereArr=array();
            $whereArr['village_id']=$tOrder['village_id'];
            $whereArr['source_from']=1;
            $whereArr['order_id']=$now_order['business_id'];
            $whereArr['table_name']='house_new_pay_order_summary';
            $paid_order_record=D('Paid_order_record')->where($whereArr)->find();
            if(!empty($paid_order_record)){
                $record_refund_money=$refund_money+$paid_order_record['refund_money'];
                $record_refund_money=round($record_refund_money,2);
                $all_record__pay_money=$paid_order_record['balance_money']+$paid_order_record['pay_money'];
                $refund_status=1;
                if($record_refund_money>=$all_record__pay_money){
                    $refund_status=2;
                }
                $save_record_data=array('refund_status'=>$refund_status,'refund_money'=>$record_refund_money);
                $save_record_data['last_refund_time']=time();
                D('Paid_order_record')->where(array('id'=>$paid_order_record['id']))->data($save_record_data)->save();
            }
        }
        $data_plat_order['is_refund'] = 1;
        $order_id = $now_order['order_id'];
        /*平台E卡退款*/
        if($now_order['use_ecard_price']>0){
            $desc=$operation_info . $now_order['order_name'] . '退款,增加'.$this->config['ecard_alias_name'].'转退余额,订单编号' . $tOrder['order_id'];
            $result = D('User')->add_money($now_order['uid'],$now_order['use_ecard_price'], $desc);
            $param = array('refund_time' => time());
            if($result['error_code']){
                $param['err_msg'] = $result['msg'];
            } else {
                $param['refund_id'] = $now_order['order_id'];
            }

            $data_plat_order['order_id'] = $now_order['order_id'];
            $data_plat_order['refund_detail'] = serialize($param);
            $result['error_code'] && $data_plat_order['is_refund'] = 4;
            $this->data($data_plat_order)->save();
            if ($result['error_code']) {
                return array('error'=>1,'msg'=>$result['msg']);
            }
            $refund_msg = $this->config['ecard_alias_name'].'转退余额退款成功 |';
        }

        if($now_order['system_balance']>0){
            if($refund_money !=''){
                $result = D('User')->add_money($now_order['uid'],$refund_money, $operation_info . $now_order['order_name'] . '退款,增加余额,订单编号' . $tOrder['order_id']);
            }else{
                $result = D('User')->add_money($now_order['uid'],$now_order['system_balance'], $operation_info . $now_order['order_name'] . '退款,增加余额,订单编号' . $tOrder['order_id']);
            }

            $param = array('refund_time' => time());
            if($result['error_code']){
                $param['err_msg'] = $result['msg'];
            } else {
                $param['refund_id'] = $now_order['order_id'];
            }

            $data_plat_order['order_id'] = $now_order['order_id'];
            $data_plat_order['refund_detail'] = serialize($param);
            $result['error_code'] && $data_plat_order['is_refund'] = 4;
            $this->data($data_plat_order)->save();
            if ($result['error_code']) {
                return array('error'=>1,'msg'=>$result['msg']);
            }
            $refund_msg = ' 平台余额退款成功 |';
        }
        /*if($now_order['system_score']>0 && $now_order['system_score_money']>0){
            $result = D('User')->add_score($now_order['uid'],$now_order['system_score'], L_("X1商品退款 X2回滚，订单编号X3",array("X1" => $operation_info . $now_order['order_name'],"X2" => C('config.score_name'),"X3" => $tOrder['real_orderid'])));
            $param = array('refund_time' => time());
            if ($result['error_code']) {
                $param['err_msg'] = $result['msg'];
            } else {
                $param['refund_id'] = $now_order['order_id'];
            }
            $data_plat_order['order_id'] = $now_order['order_id'];
            $data_plat_order['refund_detail'] = serialize($param);
            $result['error_code'] && $data_plat_order['is_refund'] = 4;
            $this->data($data_plat_order)->save();
            if ($result['error_code']) {
                return array('error'=>1,'msg'=>$result['msg']);

            }
            $refund_msg .= ' '.$result['msg'].'|';
        }*/
        /*if ($now_order['merchant_balance_pay'] != '0.00'||$now_order['merchant_balance_give']!='0.00') {
            $result = D('Card_new')->add_user_money($now_order['mer_id'],$now_order['uid'],$now_order['merchant_balance_pay'],$now_order['merchant_balance_give'],0, $operation_info . $now_order['order_name'] . '商品退款,增加余额,订单编号' . $tOrder['real_orderid'], $operation_info . $now_order['order_name'] . '商品退款,增加赠送余额,订单编号' . $tOrder['real_orderid']);

            $param = array('refund_time' => time());
            if ($result['error_code']) {
                $param['err_msg'] = $result['msg'];
            } else {
                $param['refund_id'] = $now_order['order_id'];

            }

            $data_shop_order['order_id'] = $now_order['order_id'];
            $data_shop_order['refund_detail'] = serialize($param);
            $result['error_code'] && $data_plat_order['is_refund'] = 4;
            $this->data($data_shop_order)->save();
            if ($result['error_code']) {
                return array('error'=>1,'msg'=>$result['msg']);
            }
            $refund_msg .= $result['msg'] .'|';
        }*/
       /* if($now_order['pay_money']>0){
            $now_order['business_param'] = unserialize($now_order['business_param']);
            if ($now_order['is_own'] && $now_order['business_param']['mer_id'] ) {
                $pay_method = array();
                $merchant_ownpay = D('Merchant_ownpay')->field('mer_id', true)->where(array('mer_id' => $now_order['business_param']['mer_id']))->find();
                foreach($merchant_ownpay as $ownKey=>$ownValue){
                    $ownValueArr = unserialize($ownValue);
                    if($ownValueArr['open']){
                        $ownValueArr['is_own'] = true;
                        $pay_method[$ownKey] = array('name'=>$this->getPayName($ownKey),'config'=>$ownValueArr);
                    }
                }
                $now_merchant = D('Merchant')->get_info($now_order['mer_id']);
                if ($now_merchant['sub_mch_refund'] && C('config.open_sub_mchid') && $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0) {
                    $pay_method['weixin']['config']['pay_weixin_appid'] = C('config.pay_weixin_appid');
                    $pay_method['weixin']['config']['pay_weixin_appsecret'] =  C('config.pay_weixin_appsecret');
                    $pay_method['weixin']['config']['pay_weixin_mchid'] = C('config.pay_weixin_sp_mchid');
                    $pay_method['weixin']['config']['pay_weixin_key'] =C('config.pay_weixin_sp_key') ;
                    $pay_method['weixin']['config']['sub_mch_id'] = $now_merchant['sub_mch_id'];
                    $pay_method['weixin']['config']['pay_weixin_client_cert'] = C('config.pay_weixin_sp_client_cert');
                    $pay_method['weixin']['config']['pay_weixin_client_key'] =C('config.pay_weixin_sp_client_key');
                    $pay_method['weixin']['config']['is_own'] = 2 ;
                }
            } else {

                if($now_order['is_mobile_pay']==2){
                    $pay_method = D('Config')->get_pay_method(0,0,1,1);
                } else {
                    $pay_method = D('Config')->get_pay_method(0,0,1);
                }
            }
            fdump_api(['第三方支付退款'.__LINE__,$pay_method],'new_village_order_refund',1);
            if ($now_order['pay_type']=='wechat'){
                $now_order['pay_type']='weixin';
            }
            $pay_class_name = ucfirst($now_order['pay_type']);
            fdump_api(['第三方支付退款'.__LINE__,$pay_class_name],'new_village_order_refund',1);
            if ($pay_class_name == 'Alipay' && $now_order['is_mobile_pay'] == 2 && C('config.new_pay_alipay_app_public_key') != '' &&C('config.new_pay_alipay_app_appid')  != '' && C('config.new_pay_alipay_app_private_key')  != '') {
                $pay_class_name = 'AlipayApp';
                $pay_method['alipay']['config']['new_pay_alipay_app_appid'] = C('config.new_pay_alipay_app_appid') ;
                $pay_method['alipay']['config']['new_pay_alipay_app_private_key'] =  C('config.new_pay_alipay_app_private_key') ;
                $pay_method['alipay']['config']['new_pay_alipay_app_public_key'] =C('config.new_pay_alipay_app_public_key');
            }


            if (empty($pay_method)) {
                return array('error'=>1,'msg'=>L_('系统管理员没开启任一一种支付方式'));
            }

            if (in_array($now_order['pay_type'], ['hqpay', 'hqpay_wx', 'hqpay_al'])) {
                $pay_class_name = 'Hqpay';
                empty($pay_method[$now_order['pay_type']]) && $pay_method[$now_order['pay_type']] = 1;
            }
            if (empty($pay_method[$now_order['pay_type']])) {
                return array('error'=>1,'msg'=>L_('您选择的支付方式不存在，请更新支付方式！'));
            }

            // $pay_class_name = ucfirst($now_order['pay_type']);
            $import_result = import('@.ORG.pay.'.$pay_class_name);
            if(empty($import_result)){
                return array('error'=>1,'msg'=>L_('系统管理员暂未开启该支付方式，请更换其他的支付方式'));
            }
            $now_order['order_type'] = 'plat';
            $now_order['order_id'] = $now_order['orderid'];
            if($now_order['is_mobile_pay']==3){
                //判断是平台小程序，还是跑腿小程序独立支付
                if($now_order['business_type'] == 'service' && C('config.pay_wxapp_paotui_mchid')){
                    $pay_method[$now_order['pay_type']]['config'] =array(
                        'pay_weixin_appid'=>C('config.pay_wxapp_paotui_appid'),
                        'pay_weixin_key'=>C('config.pay_wxapp_paotui_key'),
                        'pay_weixin_mchid'=>C('config.pay_wxapp_paotui_mchid'),
                        'pay_weixin_appsecret'=>C('config.pay_wxapp_paotui_appsecret'),
                    );
                    C('config.pay_weixin_client_cert',C('config.pay_wxapp_paotui_cert'));
                    C('config.pay_weixin_client_key',C('config.pay_wxapp_paotui_cert_key'));
                }else if(C('config.pay_wxapp_appid')){
                    $pay_method[$now_order['pay_type']]['config'] =array(
                        'pay_weixin_appid'=>C('config.pay_wxapp_appid'),
                        'pay_weixin_key'=>C('config.pay_wxapp_key'),
                        'pay_weixin_mchid'=>C('config.pay_wxapp_mchid'),
                        'pay_weixin_appsecret'=>C('config.pay_wxapp_appsecret'),
                    );
                    C('config.pay_weixin_client_cert',C('config.pay_wxapp_cert'));
                    C('config.pay_weixin_client_key',C('config.pay_wxapp_cert_key'));
                }
            }
            $now_order['payment_money'] = $now_order['pay_money'];
            fdump_api(['第三方支付退款'.__LINE__,$pay_class_name,$now_order, $refund_money, $now_order['pay_type'], $pay_method[$now_order['pay_type']]['config'], $this->user_session],'new_village_order_refund',1);
            $pay_class = new $pay_class_name($now_order, $refund_money, $now_order['pay_type'], $pay_method[$now_order['pay_type']]['config'], $this->user_session, 1);
            $go_refund_param = $pay_class->refund();
            fdump_api(['第三方支付退款'.__LINE__,$go_refund_param],'new_village_order_refund',1);
            $now_order['order_id'] = $order_id;
            $data_plat_order['order_id'] = $order_id;
            $data_plat_order['refund_detail'] = serialize($go_refund_param['refund_param']);
            if (empty($go_refund_param['error']) && $go_refund_param['type'] == 'ok') {
                $data_plat_order['is_refund'] = 1;
            } else {
                $data_plat_order['is_refund'] = 4;
            }
            if ($now_order['is_refund']==1) {
                // 如果是退过款的重复性退款 只记录退款失败原因，依旧保持退款成功状态
                $data_plat_order['is_refund'] = 1;
            }
            $data_plat_order['last_time'] = time();
            $this->data($data_plat_order)->save();

            if($data_plat_order['is_refund'] != 1){
                return array('error'=>1,'msg'=>$go_refund_param['msg']);
            }else{
                $refund_msg .="在线支付退款成功| ";
            }

        }*/

        if($data_plat_order['is_refund'] != 4){
            return array('error'=>0,'msg'=>$refund_msg);
        }

    }
    /**
     * 微信提现
     ***/
    public function withdrawWx($wxparam=array()){
        $param=$wxparam;
        $msgArr = [
            'error_code' => 1,
            'msg' => '',
            'third_id'=>'',
            'data'=>''
        ];
        fdump_api([__LINE__,'param'=>$param],'000withdrawWx',1);
        //开启了V3支付并且设置了V3秘钥
        if(C('config.withdraw_open_apiv3') == 1 && !empty(C('config.withdraw_weixin_apiv3'))){
            import('@.ORG.withdraw.WeixinPayV3');
            $weixinObj = new WeixinPayV3();
            $ret = $weixinObj->withdraw($param);
            fdump_api([__LINE__,'param'=>$param,'ret'=>$ret],'000withdrawWx',1);
            $result=$ret['result'];
            $transfer_batch=isset($result['transfer_batch']) && !empty($result['transfer_batch']) ? $result['transfer_batch']:'';
            if($result && is_array($result) && isset($result['batch_id']) && !empty($result['batch_id'])){
                $msgArr['error_code']=0;
                $msgArr['third_id'] = $result['batch_id'];
                $msgArr['data'] = $result;
            }else if($transfer_batch && is_array($transfer_batch) && isset($transfer_batch['batch_id']) && !empty($transfer_batch['batch_id'])){
                $msgArr['error_code']=0;
                $msgArr['third_id'] = $transfer_batch['batch_id'];
                $msgArr['data'] = $transfer_batch;
            }else {
                $msgArr['error_code']=1;
                $msg='提现失败！错误码：'.$result['code'];
                if(isset($result['message']) && !empty($result['message'])){
                    $msg=$result['message'];
                }else if(isset($result['err_code_des']) && !empty($result['err_code_des'])){
                    $msg=$result['err_code_des'];
                }
                $msgArr['msg']=$msg;
            }
        }else{
            import('@.ORG.withdraw.Weixin');
            $weixinObj = new Weixin();
            $ret = $weixinObj->withdraw($param);
            fdump_api([__LINE__,'param'=>$param,'ret'=>$ret],'000withdrawWx',1);
            $result=$ret['result'];
            if($result['return_code']=='SUCCESS' && $result['result_code']=='SUCCESS'){
                $msgArr['error_code']=0;
                $msgArr['third_id'] = $result['payment_no'];
            }else {
                $msgArr['error_code']=1;
                $msg='提现失败！错误码：'.$result['err_code'];
                if(isset($result['err_code_des']) && !empty($result['err_code_des'])){
                    $msg=$result['err_code_des'];
                }else if(isset($result['return_msg']) && !empty($result['return_msg'])){
                    $msg=$result['return_msg'];
                }
                $msgArr['msg']=$msg;
            }
        }
        return $msgArr;
    }
}

?>