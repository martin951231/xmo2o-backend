<?php
/*拼团自动退款

 * */
class plan_group_order_cancel extends plan_base{
	public function runTask(){
		$order_list  = D('Group_start')->group_refund();

		if(!empty($order_list)){
			foreach ($order_list as $v){
				$this->keepThread();
				$now_order = $v;
				
				$tmp_order_id = $now_order['order_id'];

				$data_group_order = array();
                $data_group_order['order_id'] = $now_order['order_id'];
				if($now_order['payment_money'] >0){

					$payment_money_order = M('Payment_money_order')->where(array('order_id'=>$now_order['order_id'],'order_type'=>'group','pay_type'=>$now_order['pay_type'],'paid_money'=>array('gt',0)))->find();

                    $pay_order_info = [];
                    if(empty($payment_money_order)){// 获取新版支付信息
                        $pay_order_info =  M('Pay_order_info')->where(array('business_order_id'=>$now_order['order_id'],'business'=>'group','pay_type'=>$now_order['pay_type'],'paid_money'=>array('gt',0)))->find();
                    }
					if($pay_order_info){
                        $refundParam = [
                            'order_no' => $pay_order_info['orderid'],
                            'refund_money' => false,
                        ];
                        $result = invoke_v20_service('\app\pay\model\service\PayService/refund',$refundParam);
                        $go_refund_param = [
                            'refund_param' => [],
                            'error' => 0,
                            'type' => 'ok',
                        ];
                        if(!$result || $result['data']['error_no']){
                            $go_refund_param['error'] = 1;
                            $go_refund_param['refund_param'] = [
                                'err_msg' => $result['data']['error_msg']
                            ];
                        }else{
                            $go_refund_param['refund_param'] = [
                                'refund_payment_money' => $result['data']['retval']['refund_money'],
                                'refund_id' => $result['data']['retval']['refund_no']
                            ];
                        }
                    }else {
                        if ($payment_money_order && ($payment_money_order['pay_method'] == 'wft' || $payment_money_order['pay_method'] == 'paylinx' || $payment_money_order['pay_method'] == 'weikebaba' || $payment_money_order['pay_method'] == 'xiaoma')) {
                            if ($payment_money_order['pay_method'] == 'wft') {
                                $pay_method = 'wftpay';
                            }
                            if ($payment_money_order['pay_method'] == 'paylinx') {
                                $pay_method = 'paylinx';
                            }
                            if ($payment_money_order['pay_method'] == 'weikebaba') {
                                $pay_method = 'weikebaba';
                            }
                            if ($payment_money_order['pay_method'] == 'xiaoma') {
                                $pay_method = 'xiaoma';
                            }
                            import('@.ORG.LowFeePay');
                            $lowfeepay = new LowFeePay($pay_method);
                            $now_order['order_type'] = 'group';
                            $now_order['orderNo'] = $payment_money_order['orderNo'];
                            if ($now_order['mer_id']) {
                                $mer_juhe = M('Merchant_juhe_config')->where(array('mer_id' => $now_order['mer_id']))->find();
                                if (!empty($mer_juhe)) {
                                    $lowfeepay->userId = $mer_juhe['userid'];
                                    $now_order['is_own'] = 1;
                                    $now_order['orderNo'] = $now_order['orderNo'] . '_1';
                                }

                            }
                            if ($now_order['mer_id'] && $payment_money_order['pay_method'] == 'weikebaba') {
                                $mer_juhe = M('Weikebaba')->where(array('mer_id' => $now_order['mer_id']))->find();
                                if (!empty($mer_juhe)) {
                                    $lowfeepay->user_id = $mer_juhe['user_id'];
                                    $lowfeepay->key = $mer_juhe['key'];
                                    $now_order['is_own'] = 1;
                                    $now_order['orderNo'] = $now_order['orderNo'] . '_1';
                                }

                            }
                            $go_refund_param = $lowfeepay->refund($now_order);
                        } else if (C('config.open_juhepay') == 1 && ($now_order['pay_type'] == 'weixin' || $now_order['pay_type'] == 'alipay')) {
                            import('@.ORG.LowFeePay');
                            $lowfeepay = new LowFeePay('juhepay');
                            $now_order['orderNo'] = 'group_' . $now_order['orderid'];
                            if ($now_order['mer_id']) {
                                $mer_juhe = M('Merchant_juhe_config')->where(array('mer_id' => $now_order['mer_id']))->find();
                                if (!empty($mer_juhe)) {
                                    $lowfeepay->userId = $mer_juhe['userid'];
                                    $now_order['is_own'] = 1;
                                    $now_order['orderNo'] = $now_order['orderNo'] . '_1';
                                }

                            }
                            $go_refund_param = $lowfeepay->refund($now_order);

                        } else if (C('config.open_pufa_pay') == 1 && ($now_order['pay_type'] == 'weixin')) {
                            import('@.ORG.LowFeePay');
                            $lowfeepay = new LowFeePay('pufapay');
                            $now_order['orderNo'] = 'group_' . $now_order['orderid'];
                            $go_refund_param = $lowfeepay->refund($now_order);
                        } else if ($payment_money_order && $payment_money_order['pay_method'] == 'chinaums') {
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
                            if ($now_order['mer_id']) {
                                $now_merchant = M("Merchant")->where(['mer_id' => $now_order['mer_id']])->find();
                                if (!empty($now_merchant['chinaums_merno'])) {
                                    $chinaums_merno = $now_merchant['chinaums_merno'];
                                }
                            }
                            $go_refund_param = $pay->refund('group_' . $payment_money_order['orderid'], $payment_money_order['paid_money'], $payment_money_order['paid_money'], $payment_money_order['third_plat_data'], $chinaums_merno, $mid);
                        } else if ($payment_money_order && $payment_money_order['pay_method'] == 'scrcu') {
                            import("@.ORG.pay.Scrcu");
                            $pay = new scrcu();

                            $scrcu_mer_no = '';
                            if ($now_order['mer_id']) {
                                $now_merchant = M("Merchant")->where(['mer_id' => $now_order['mer_id']])->find();
                                if (!empty($now_merchant['scrcu_mer_no'])) {
                                    $scrcu_mer_no = $now_merchant['scrcu_mer_no'];
                                }
                            }

                            $go_refund_param = $pay->refund($now_order['orderid'], $payment_money_order['paid_money'], $scrcu_mer_no);
                        } else {
                            if ($now_order['is_own']) {
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
                                $pay_method = D('Config')->get_pay_method(0, 0, $now_order['is_mobile_pay'] == 1 ? true : false, $now_order['is_mobile_pay'] == 2 ? true : false);
                            }

                            if (empty($pay_method)) {
                                $data_group_order['status'] = 7;
                                $data_group_order['refund_detail'] = '自动退款失败，原因：未找到支付方式';
                                D('Group_order')->data($data_group_order)->save();
                                continue;
                            }
                            $pay_class_name = ucfirst($now_order['pay_type']);
                            if (in_array($now_order['pay_type'], ['hqpay', 'hqpay_wx', 'hqpay_al'])) {
                                $pay_class_name = 'Hqpay';
                                empty($pay_method[$now_order['pay_type']]) && $pay_method[$now_order['pay_type']] = 1;
                            }
                            if (empty($pay_method[$now_order['pay_type']])) {
                                $data_group_order['status'] = 7;
                                $data_group_order['refund_detail'] = '自动退款失败，原因：支付方式不存在';
                                D('Group_order')->data($data_group_order)->save();
                                continue;
                            }

                            $import_result = import('@.ORG.pay.' . $pay_class_name);
                            if (empty($import_result)) {
                                $data_group_order['status'] = 7;
                                $data_group_order['refund_detail'] = '自动退款失败，原因：支付方式不存在';
                                D('Group_order')->data($data_group_order)->save();
                                continue;
                            }
                            $now_order['order_type'] = 'group';

                            if (!empty($now_order['orderid'])) {
                                $now_order['order_id'] = $now_order['orderid'];
                            }
                            $pay_class = new $pay_class_name($now_order, $now_order['payment_money'], $now_order['pay_type'], $pay_method[$now_order['pay_type']]['config'], '', 1);
                            $go_refund_param = $pay_class->refund();
                        }
                    }
					
					$now_order['order_id'] = $tmp_order_id;
					$data_group_order['refund_detail'] = serialize($go_refund_param['refund_param']);
					if (empty($go_refund_param['error']) && $go_refund_param['type'] == 'ok') {
						$data_group_order['status'] = 3;
					}
					D('Group_order')->data($data_group_order)->save();

					if ($data_group_order['status'] != 3 || empty($data_group_order['status'])) {
						$data_group_order['status'] = 7;
						if ($go_refund_param['order_param']) {

							$data_group_order['refund_detail'] = '自动退款失败，原因：' . $go_refund_param['order_param']['error_msg'];
						} elseif ($go_refund_param['refund_param']) {
							$data_group_order['refund_detail'] = '自动退款失败，原因：' . $go_refund_param['refund_param']['err_msg'];
						} else {
							$data_group_order['refund_detail'] = serialize($go_refund_param);

						}

						D('Group_order')->data($data_group_order)->save();
						continue;
					}
				}

				//退积分
				if ($now_order['score_used_count']>0) {
					$order_info=unserialize($now_order['info']);
					$order_name=$order_info[0]['name']."*".$order_info[0]['num'];
					$result = D('User')->add_score($now_order['uid'],$now_order['score_used_count'],'退款 '.$order_name.' '.C('config.score_name').'回滚');
					$param = array('refund_time' => time());
					if($result['error_code']){
						$param['err_msg'] = $result['msg'];
					} else {
						$param['refund_id'] = $now_order['order_id'];
					}
					$data_meal_order['order_id'] = $now_order['order_id'];
					$data_meal_order['refund_detail'] = serialize($param);
					$result['error_code'] || $data_meal_order['status'] = 3;
					D('Group_order')->data($data_meal_order)->save();
					if ($result['error_code']) {
						$data_group_order['status'] = 7;
						$data_group_order['refund_detail'] = '自动退款，退回'.C('config.score_name').'失败，原因：'.$result['msg'];
						D('Group_order')->data($data_group_order)->save();
					}
					$go_refund_param['msg'] .= $result['msg'];
				}
				//平台E卡退款
				if($now_order['use_ecard_price']>0){
					$add_result = D('User')->add_money($now_order['uid'],$now_order['use_ecard_price'],'退款 '.$now_order['order_name'].' 增加'.C('config.ecard_alias_name').'转退余额');
					$param = array('refund_time' => time());
					if($add_result['error_code']){
						$param['err_msg'] = $add_result['msg'];
					} else {
						$param['refund_id'] = $now_order['order_id'];
					}

					$data_group_order['refund_detail'] = serialize($param);
					$add_result['error_code'] || $data_group_order['status'] = 3;
					D('Group_order')->data($data_group_order)->save();
					if ($add_result['error_code']) {
						$data_group_order['status'] = 7;
						$data_group_order['refund_detail'] = '自动退款，退回'.C('config.ecard_alias_name').'转退余额失败，原因：'.$add_result['msg'];
						D('Group_order')->data($data_group_order)->save();
					}
					$go_refund_param['msg'] = C('config.ecard_alias_name').'转退退款成功';
				}
				//平台余额退款
				if($now_order['balance_pay']>0){
					$add_result = D('User')->add_money($now_order['uid'],$now_order['balance_pay'],'退款 '.$now_order['order_name'].' 增加余额');
					$param = array('refund_time' => time());
					if($add_result['error_code']){
						$param['err_msg'] = $add_result['msg'];
					} else {
						$param['refund_id'] = $now_order['order_id'];
					}

					$data_group_order['refund_detail'] = serialize($param);
					$add_result['error_code'] || $data_group_order['status'] = 3;
					D('Group_order')->data($data_group_order)->save();
					if ($add_result['error_code']) {
						$data_group_order['status'] = 7;
						$data_group_order['refund_detail'] = '自动退款，退回余额失败，原因：'.$add_result['msg'];
						D('Group_order')->data($data_group_order)->save();
					}
					$go_refund_param['msg'] = '平台余额退款成功';
				}
				//商家会员卡余额退款

				if($now_order['card_give_money'] >0){
					$result = D('Card_new')->add_user_money($now_order['mer_id'],$now_order['uid'],0,$now_order['card_give_money'],0,'退款 '.$now_order['order_name'].' 增加余额','退款 '.$now_order['order_name'].' 增加赠送余额');
					$param = array('refund_time' => time());
					if($result['error_code']){
						$param['err_msg'] = $result['msg'];
					} else {
						$param['refund_id'] = $now_order['order_id'];
					}
					$data_group_order['refund_detail'] = serialize($param);
					$result['error_code'] || $data_group_order['status'] = 3;
					D('Group_order')->data($data_group_order)->save();
					if ($result['error_code']) {
						$data_group_order['status'] = 7;
						$data_group_order['refund_detail'] = '自动退款，退回商家会员卡赠送余额失败，原因：'.$result['msg'];
						D('Group_order')->data($data_group_order)->save();
					}
					$go_refund_param['msg'] = $result['msg'];
				}

				if($now_order['merchant_balance']>0){
					$result = D('Card_new')->add_user_money($now_order['mer_id'],$now_order['uid'],$now_order['merchant_balance'],0,0,'退款 '.$now_order['order_name'].' 增加余额','退款 '.$now_order['order_name'].' 增加赠送余额');
					$param = array('refund_time' => time());
					if($result['error_code']){
						$param['err_msg'] = $result['msg'];
					} else {
						$param['refund_id'] = $now_order['order_id'];
					}
					$data_group_order['refund_detail'] = serialize($param);
					$result['error_code'] || $data_group_order['status'] = 3;
					D('Group_order')->data($data_group_order)->save();
					if ($result['error_code']) {
						$data_group_order['status'] = 7;
						$data_group_order['refund_detail'] = '自动退款，退回商家会员卡余额失败，原因：'.$result['msg'];
						D('Group_order')->data($data_group_order)->save();
					}
					$go_refund_param['msg'] = $result['msg'];
				}

//				if($now_order['total_money']==0||$now_order['card_discount']==0){
				if(floatval($now_order['total_money'])==0){
					$param = array('refund_time' => time());

					$param['refund_id'] = $now_order['order_id'];

					$data_group_order['refund_detail'] = serialize($param);
					$data_group_order['status'] = 3;
					D('Group_order')->data($data_group_order)->save();
				}

                //2015-12-9     退款时销量回滚 确切退款成功了 进行操作
				if (intval($data_group_order['status'])==3) {
                    $update_group = D('Group')->where(array('group_id' => $now_order['group_id']))->find();
                    if ($update_group['type'] == 3) {
                        $sale_count = $update_group['sale_count'] - $now_order['num'];
                        $sale_count = $sale_count > 0 ? $sale_count : 0;
                        $update_group_data = array('sale_count' => $sale_count);
                        if ($update_group['count_num'] > 0 && $sale_count < $update_group['count_num']) {
                            $update_group_data['type'] = 1;
                        }
                        D('Group')->where(array('group_id' => $now_order['group_id']))->save($update_group_data);
                    } else {
                        //退款时销量回滚
						$sale_count = $update_group['sale_count'] - $now_order['num'];
						if ($sale_count>0) {
							D('Group')->where(array('group_id' => $now_order['group_id']))->setDec('sale_count', $now_order['num']);
						} else {
							$sale_count = M('Group_order')->where(array('group_id' => $now_order['group_id'],'paid'=>1,'status'=>array('neq',3)))->count();
							if (!$sale_count) {
								$sale_count = 0;
							}
							D('Group')->where(array('group_id' => $now_order['group_id']))->setField('sale_count', $sale_count);
						}
                    }
                } elseif (!$now_order['pay_type']) {
                    // 出现该种情况应该是线下支付 更改支付方式为 线下支付
					D('Group_order')->where(array('order_id' => $tmp_order_id))->setField('sale_count', 'offline');
                }
                /*-----记录系统订单信息 订单取消退款成功 改写状态已退款 19/1/18--团购状态-已退款3--*/
                D("System_order")->save_order_status('group',$now_order['order_id'],4,3);

			}
		}
		return true;
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