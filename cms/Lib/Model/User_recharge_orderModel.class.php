<?php
class User_recharge_orderModel extends Model{
	public function get_pay_order($uid,$order_id,$is_web=false){
		$now_order = $this->get_order_by_id($uid,$order_id);
		if($now_order['money']<=0){
			return array('error'=>1,'msg'=>L_('充值订单金额异常！'));
		}
		if(empty($now_order)){
			return array('error'=>1,'msg'=>L_('当前订单不存在！'));
		}
		//用户充值check的时候不要弹窗了吗，防止用户支付成功弹窗两次
		if(!empty($now_order['paid']) && ACTION_NAME != 'check'){
			return array('error'=>1,'msg'=>L_('您已经充值！'),'url'=>'/wap.php?c=My&a=transaction');
		}

		if($is_web){
			$order_info = array(
					'order_id'			=>	$now_order['order_id'],
					'order_type'		=>	($_GET['type'] == 'waimai-recharge' || $_POST['order_type'] == 'waimai-recharge') ? 'waimai-recharge' : 'recharge',
					'order_total_money'	=>	floatval($now_order['money']),
					'order_name'		=>	L_('在线充值'),
					'order_num'			=>	1,
					'order_content'    =>  array(
							0=>array(
									'name'  		=> L_('在线充值'),
									'num'   		=> 1,
									'price' 		=> floatval($now_order['money']),
									'money' 	=> floatval($now_order['money']),
							)
					)
			);
		}else{
			$order_info = array(
					'order_id'			=>	$now_order['order_id'],
					'order_type'		=>	($_GET['type'] == 'waimai-recharge' || $_POST['order_type'] == 'waimai-recharge') ? 'waimai-recharge' : 'recharge',
					'order_name'		=>	L_('在线充值'),
					'order_num'			=>	1,
					'order_price'		=>	floatval($now_order['money']),
					'order_total_money'	=>	floatval($now_order['money']),
			);
		}
		return array('error'=>0,'order_info'=>$order_info);
	}
	public function get_order_by_id($uid,$order_id){
		$condition_user_recharge_order['uid'] = $uid;
		$condition_user_recharge_order['order_id'] = $order_id;
		return $this->field(true)->where($condition_user_recharge_order)->find();
	}
	//电脑站支付前订单处理
	public function web_befor_pay($order_info,$now_user){

		$data_user_recharge_order['last_time'] = $_SERVER['REQUEST_TIME'];
		$data_user_recharge_order['submit_order_time'] = $_SERVER['REQUEST_TIME'];
		$condition_user_recharge_order['order_id'] = $order_info['order_id'];
		if(!$this->where($condition_user_recharge_order)->data($data_user_recharge_order)->save()){
			return array('error_code'=>true,'msg'=>L_('保存订单失败！请重试或联系管理员。'));
		}
		return array('error_code'=>false,'pay_money'=>$order_info['order_total_money']);

	}
	//手机端支付前订单处理
	public function wap_befor_pay($order_info,$now_user){

		//去除微信优惠的金额
		$pay_money = $order_info['order_total_money'];

		//判断优惠券
//		if(!empty($now_coupon['price'])){
//			$data_weidian_order['card_id'] = $now_coupon['record_id'];
//			if($now_coupon['price'] >= $pay_money){
//				$order_result = $this->wap_pay_save_order($order_info,$data_weidian_order);
//				if($order_result['error_code']){
//					return $order_result;
//				}
//				return $this->wap_after_pay_before($order_info);
//			}
//			$pay_money -= $now_coupon['price'];
//		}

		//判断商家余额
//		if(!empty($merchant_balance)){
//			if($merchant_balance >= $pay_money){
//				$data_weidian_order['merchant_balance'] = $pay_money;
//				$order_result = $this->wap_pay_save_order($order_info,$data_weidian_order);
//				if($order_result['error_code']){
//					return $order_result;
//				}
//				return $this->wap_after_pay_before($order_info);
//			}else{
//				$data_weidian_order['merchant_balance'] = $merchant_balance;
//			}
//			$pay_money -= $merchant_balance;
//		}

		//判断帐户余额
//		if(!empty($now_user['now_money'])){
//			if($now_user['now_money'] >= $pay_money){
//				$data_weidian_order['balance_pay'] = $pay_money;
//				$order_result = $this->wap_pay_save_order($order_info,$data_weidian_order);
//				if($order_result['error_code']){
//					return $order_result;
//				}
//				return $this->wap_after_pay_before($order_info);
//			}else{
//				$data_weidian_order['balance_pay'] = $now_user['now_money'];
//			}
//			$pay_money -= $now_user['now_money'];
//		}
		//在线支付
		$order_result = $this->wap_pay_save_order($order_info);
		if($order_result['error_code']){
			return $order_result;
		}
		return array('error_code'=>false,'pay_money'=>$pay_money);
	}
	//手机端支付前保存各种支付信息
	public function wap_pay_save_order($order_info){
		$data_weidian_order['last_time'] = $_SERVER['REQUEST_TIME'];
		$condition_weidian_order['order_id'] = $order_info['order_id'];
		if($this->where($condition_weidian_order)->data($data_weidian_order)->save()){
			return array('error_code'=>false,'msg'=>L_('保存订单成功！'));
		}else{
			return array('error_code'=>true,'msg'=>L_('保存订单失败！请重试或联系管理员。'));
		}
	}
	//如果无需调用在线支付，使用此方法即可。
	public function wap_after_pay_before($order_info){
		$order_param = array(
				'order_id' => $order_info['order_id'],
				'pay_type' => '',
				'third_id' => '',
		);
		$result_after_pay = $this->after_pay($order_param);
		if($result_after_pay['error']){
			return array('error_code'=>true,'msg'=>$result_after_pay['msg']);
		}else{
			return array('error_code'=>false,'msg'=>L_('支付成功'),'url'=>$result_after_pay['url']);
		}
	}
	public function after_pay($order_param){
        fdump_api(['order_param' => $order_param], 'user_recharge_order/after_pay_log', 1);
		if($order_param['pay_type']!=''){
			$where['orderid'] = $order_param['order_id'];
		}else{
			$where['order_id'] = $order_param['order_id'];
		}
		$now_order = $this->field(true)->where($where)->find();
		if(floatval($order_param['pay_money'])!=floatval($now_order['money'])){
			return array('error'=>1,'msg'=>L_('充值订单有误'));
		}
        fdump_api(['now_order' => $now_order], 'user_recharge_order/after_pay_log', 1);
		if(empty($now_order)){
			return array('error'=>1,'msg'=>L_('当前订单不存在'));
		}else if($now_order['paid'] == 1){
			if($order_param['order_type'] == 'waimai-recharge'){
				if($order_param['is_mobile_pay']){
					return array('error'=>1,'msg'=>L_('该订单已付款！'),'url'=>U('Waimai/User/index'));
				}else{
					return array('error'=>1,'msg'=>L_('该订单已付款！'),'url'=>U('Waimai/Asset/balance'));
				}
			}else{
				return array('error'=>1,'msg'=>L_('该订单已付款！'),'url'=>$this->get_pay_after_url($now_order['label'],$now_order['is_mobile_pay'],$now_order));
			}
		}else{
            try {
                $uniqueKey = md5($now_order['order_id'].'userRechargeOrderAfterPay');
                $return_cache = S($uniqueKey);
                fdump_api(['uniqueKey' => $uniqueKey, 'return_cache' => $return_cache], 'user_recharge_order/after_pay_log', 1);
                if (!empty($return_cache)) {
                    return array('error'=>1,'msg'=>L_('重复执行了'));
                } else {
                    S($uniqueKey, 1, 120);
                }
//                $lockRs = setLock($uniqueKey, 10);
//                if ($lockRs['error_no']) {
//                    fdump_api(['msg' => $lockRs['error_msg'], 'order_param' => $order_param, 'lockRs' => $lockRs], 'user_recharge_order/after_pay_log', 1);
//                    return array('error' => 1, 'msg' => $lockRs['error_msg']);
//                }
            } catch (\Exception $e) {
                fdump_api(['执行错误' => $e->getMessage(), 'order_param' => $order_param, 'line' => $e->getLine(), 'file' => $e->getFile(), 'code' => $e->getCode(), 'func_get_args' => func_get_args()], 'user_recharge_order/after_pay_log');
            }

			//得到当前用户信息，不将session作为调用值，因为可能会失效或错误。
			$now_user = D('User')->get_user($now_order['uid']);
			if(empty($now_user)){
				return array('error'=>1,'msg'=>L_('没有查找到此订单归属的用户，请联系管理员！'));
			}

            $pay_time = isset($order_param['pay_time']) && intval($order_param['pay_time']) > 100 ? intval($order_param['pay_time']) : $_SERVER['REQUEST_TIME'];
            if (!$pay_time || intval($pay_time) < 100) {
                $pay_time = time();
            }
			$data_user_recharge_order = array();
			if(isset($order_param['is_mobile'])){
				$data_user_recharge_order['is_mobile_pay'] = $order_param['is_mobile'];
			}
			$data_user_recharge_order['pay_time'] = $pay_time;
			$data_user_recharge_order['payment_money'] = floatval($order_param['pay_money']);
			$data_user_recharge_order['pay_type'] = $order_param['pay_type'];
			$data_user_recharge_order['third_id'] = $order_param['third_id'];
			$data_user_recharge_order['paid'] = 1;

			$is_new_storage=false;
			$remarks='';
            $customized_meter_reading=C('config.customized_meter_reading');
            $customized_meter_reading=$customized_meter_reading ? intval($customized_meter_reading):0;
            $jz_wsxdsj_net_customized_open=D('Config_customization')->getWSXCustomizedOpen();
            //todo 【业主预存】 支付成功后同步写入明细
            $labelRechargeType='';
            $labelRechargeStr='';
            if(!empty($now_order['label']) && (strpos($now_order['label'], 'village_') !== false)){
                $label=explode('_',$now_order['label']);
                $labelRechargeStr=$now_order['label'];
                $labelRechargeType=$label[0].'_'.$label[1];
                $rechargeTypeArr=['village_water','village_electric','village_gas','village_property','village_hotwater','village_villagebalance','village_qunuanfei'];
                if(isset($label[0]) && isset($label[1]) && isset($label[3]) && in_array($labelRechargeType,$rechargeTypeArr)){
                    $is_new_storage=true;
                    $type_0127=[
                        'village_water'=>'水费',
                        'village_electric'=>'电费',
                        'village_gas'=>'燃气费',
                        'village_property'=>'物业费'
                    ];
                    if($customized_meter_reading){
                        $type_0127['village_water']='冷水费';
                        $type_0127['village_hotwater']='热水费';
                        $type_0127['village_villagebalance']='物业费';
                    }
                    if($jz_wsxdsj_net_customized_open){
                        $type_0127['village_qunuanfei']='取暖费';
                    }
                    $data_user_recharge_order['business_type'] = 2;
                    $data_user_recharge_order['business_id'] = $label[2];
                    $data_user_recharge_order['bind_id'] = $label[3];
                    $data_user_recharge_order['source'] = $label[0].'_'.$label[1];
                    $remarks='用户端'.$type_0127[$label[0].'_'.$label[1]].'预存操作';
                    fdump_api([$remarks,2222],'village_balance/pay',1);
                }
            }
            if($data_user_recharge_order['payment_money']>0){
                $save_paid_order_record=array();
                if($data_user_recharge_order['business_id']>0){
                    $save_paid_order_record['source_from']=1;
                    if($data_user_recharge_order['business_type']==2){
                        $save_paid_order_record['village_id']=$data_user_recharge_order['business_id'];
                    }elseif($data_user_recharge_order['business_type']==1){
                        $save_paid_order_record['property_id']=$data_user_recharge_order['business_id'];
                    }
                }
                $save_paid_order_record['house_type']='user_recharge_order';
                $save_paid_order_record['business_type']='user_recharge_order';
                $save_paid_order_record['business_name']='用户余额充值';
                $save_paid_order_record['uid']=$now_order['uid'];
                $save_paid_order_record['order_id']=$now_order['order_id'];
                $save_paid_order_record['order_no']=$now_order['orderid'] ? $now_order['orderid']:'';
                $save_paid_order_record['u_name']=$now_user['nickname'] ? $now_user['nickname']:'';
                $save_paid_order_record['u_phone']=$now_user['phone'] ? $now_user['phone']:'';
                $save_paid_order_record['table_name']='user_recharge_order';
                $save_paid_order_record['order_money']=$now_order['money'];
                $save_paid_order_record['pay_type']=$order_param['pay_type'];
                $save_paid_order_record['pay_order_no']=$now_order['orderid'] ? $now_order['orderid']:'';
                $save_paid_order_record['third_transaction_no']=isset($order_param['third_id']) ? $order_param['third_id']:'';
                $save_paid_order_record['is_online']=1;
                $save_paid_order_record['pay_time']=$pay_time;
                $save_paid_order_record['pay_money']=$data_user_recharge_order['payment_money'];
                $save_paid_order_record['bind_user_id']=$data_user_recharge_order['bind_id'];
                $save_paid_order_record['extra_data']=json_encode($order_param,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                D('Paid_order_record')->data($save_paid_order_record)->add();
            }
            
            fdump_api(['is_new_storage' => $is_new_storage], 'user_recharge_order/after_pay_log', 1);
            $can_use_village_balance=M('House_village_config')->where(['village_id'=>$data_user_recharge_order['business_id']])->getField('open_village_balance');
          
            fdump_api(['can_use_village_balance' => $can_use_village_balance, 'data_user_recharge_order' => $data_user_recharge_order], 'user_recharge_order/after_pay_log', 1);
            if($is_new_storage&&$can_use_village_balance==1){
                $this->where($where)->save($data_user_recharge_order);
                $user_money=D('Village_user_money_list')->where(['uid'=>$now_order['uid'],'business_id'=>$data_user_recharge_order['business_id'],'business_type'=>1])->field('*')->find();
           
                fdump_api(['user_money' => $user_money], 'user_recharge_order/after_pay_log', 1);
                $is_have_recode=true;
                $saveBalanceArr=array();
                if (empty($user_money)){
                    $is_have_recode=false;
                    $user_money=array();
                    $user_money['current_money']=0;
                    $user_money['cold_water_balance']=0;
                    $user_money['hot_water_balance']=0;
                    $user_money['electric_balance']=0;
                }
                $opt_money_type='';
                if($customized_meter_reading && $labelRechargeType && $labelRechargeStr){
                    if(strpos($labelRechargeStr,'village_water_')!==false){
                        $saveBalanceArr['cold_water_balance']=$user_money['cold_water_balance']+$now_order['money'];
                        $opt_money_type='cold_water_balance';
                    }else if( strpos($labelRechargeStr,'village_hotwater_')!==false){
                        $saveBalanceArr['hot_water_balance']=$user_money['hot_water_balance']+$now_order['money'];
                        $opt_money_type='hot_water_balance';
                    }else if(strpos($labelRechargeStr,'village_electric_')!==false){
                        $saveBalanceArr['electric_balance']=$user_money['electric_balance']+$now_order['money'];
                        $opt_money_type='electric_balance';
                    }else if( strpos($labelRechargeStr,'village_villagebalance_')!==false){
                        $saveBalanceArr['current_money']=$user_money['current_money']+$now_order['money'];
                    }
                }else{
                    $saveBalanceArr['current_money']=$user_money['current_money']+$now_order['money'];
                }
                if (!empty($saveBalanceArr)) {
                    if (!$is_have_recode) {
                        $arr_add = [
                            'uid' => $now_order['uid'],
                            'business_id' => $data_user_recharge_order['business_id'],
                            'add_time' => time(),
                            'update_time' => time(),
                            'remarks' => isset($remarks) ? $remarks : '',
                        ];
                        $arr_add =array_merge($arr_add,$saveBalanceArr);
                        $update_ret = D('Village_user_money_list')->add($arr_add);
                        
                        fdump_api(['update_ret' => $update_ret, 'arr_add' => $arr_add], 'user_recharge_order/after_pay_log', 1);
                    } else {
                        $update_ret = D('Village_user_money_list')->where(['uid' => $now_order['uid'], 'business_id' => $data_user_recharge_order['business_id'], 'business_type' => 1])->data($saveBalanceArr)->save();

                        fdump_api(['update_ret' => $update_ret, 'saveBalanceArr' => $saveBalanceArr], 'user_recharge_order/after_pay_log', 1);
                    }
                    if (!empty($update_ret)) {
                        $log_data = [
                            'uid' => $now_order['uid'],
                            'business_type' => 1,
                            'business_id' => $data_user_recharge_order['business_id'],
                            'type' => 1,
                            'current_money' => 0,
                            'money' => $now_order['money'],
                            'after_price' => 0,
                            'add_time' => time(),
                            'role_id' => 0,
                            'ip' => get_client_ip(),
                            'desc' => $remarks,
                            'order_id' => 0,
                            'order_type' => 'user_recharge',
                            'pay_type' => $order_param['pay_type'],
                        ];
                        $log_data['extra_data']=json_encode($order_param,JSON_UNESCAPED_UNICODE);
                        if($opt_money_type=='cold_water_balance'){
                            $log_data['after_price']=$saveBalanceArr['cold_water_balance'];
                            $log_data['current_money']=$user_money['cold_water_balance'];
                            $log_data['money_type']=3;
                        }elseif($opt_money_type=='hot_water_balance'){
                            $log_data['after_price']=$saveBalanceArr['hot_water_balance'];
                            $log_data['current_money']=$user_money['hot_water_balance'];
                            $log_data['money_type']=2;
                        }elseif($opt_money_type=='electric_balance'){
                            $log_data['after_price']=$saveBalanceArr['electric_balance'];
                            $log_data['current_money']=$user_money['electric_balance'];
                            $log_data['money_type']=1;
                        }else{
                            $log_data['after_price']=$saveBalanceArr['current_money'];
                            $log_data['current_money']=$user_money['current_money'];
                        }
                        D('Village_user_money_log')->add($log_data);
                        fdump_api(['log_data' => $log_data], 'user_recharge_order/after_pay_log', 1);
                    }
                    if($opt_money_type=='cold_water_balance'){
                        $rr= invoke_v20_service('\app\community\model\service\HouseNewCashierService/userUnpaidOrder', ['village_id'=>$data_user_recharge_order['business_id'],'pigcms_id'=>$data_user_recharge_order['bind_id'],'xtype'=>'cold_water_balance']);
                    }else if($opt_money_type=='hot_water_balance'){
                        $rr= invoke_v20_service('\app\community\model\service\HouseNewCashierService/userUnpaidOrder', ['village_id'=>$data_user_recharge_order['business_id'],'pigcms_id'=>$data_user_recharge_order['bind_id'],'xtype'=>'hot_water_balance']);
                    }else if($opt_money_type=='electric_balance'){
                        $rr= invoke_v20_service('\app\community\model\service\HouseNewCashierService/userUnpaidOrder', ['village_id'=>$data_user_recharge_order['business_id'],'pigcms_id'=>$data_user_recharge_order['bind_id'],'xtype'=>'electric_balance']);
                    }
                }
                $tmp_order = [];
                $tmp_order['plat_order_id'] = 0;
                $tmp_order['score_used_count'] = 0;
                $tmp_order['score_can_get'] =  0;
                $tmp_order['is_own'] = 0;
                $tmp_order['payment_money'] = 0;
                $tmp_order['score_deducte'] = 0;
                $tmp_order['desc'] = '社区住户余额充值';
                $tmp_order['money'] = $data_user_recharge_order['payment_money'];
                $tmp_order['order_type'] = 'village_new_pay';
                $tmp_order['order_type_special'] = 'village_balance_recharge';
                $order_info = array_merge($now_order,$tmp_order);
                $order_info['money'] = $data_user_recharge_order['payment_money'];
                $order_info['order_id']=$now_order['order_id'];
                $order_info['plat_order_id'] = 0;
                $order_info['village_id']=$data_user_recharge_order['business_id'];
                D('Village_money_list')->add_money($order_info,0,1);
                if(isset($data_user_recharge_order['source']) && $data_user_recharge_order['source'] == 'village_property'){
                    //物业费预存成功，调用v20方法 自动扣款未支付物业费
                    $rr= invoke_v20_service('\app\community\model\service\HouseNewCashierService/userUnpaidOrder', ['village_id'=>$data_user_recharge_order['business_id'],'pigcms_id'=>$data_user_recharge_order['bind_id']]);
                    
                    fdump_api(['msg' => '物业费预存成功，调用v20方法 自动扣款未支付物业费', 'data_user' => $data_user_recharge_order, 'rr' => $rr], 'user_recharge_order/after_pay_log', 1);
                }
                if(C('config.recharge_score_get')>0){
                    $score_get = ($now_order['payment_money']*C('config.recharge_score_get'));
                    $score_get= D('Percent_rate')->get_user_add_score_times($score_get,$now_order['uid']);
                    D('User')->add_score($now_order['uid'],$score_get,L_('用户充值获得').C('config.score_name'));
                }
                if($is_new_storage){
                    $now_order['bind_id']=$data_user_recharge_order['bind_id'];
                }
                return array('error'=>0,'msg'=>L_('充值成功！'),'url'=>$this->get_pay_after_url($now_order['label'],$now_order['is_mobile_pay'],$now_order));

            } 
            elseif($this->where($where)->save($data_user_recharge_order)){
                if($is_new_storage){
                    $time=time();
                    $get_now_money=D('User')->where(['uid'=>$now_order['uid']])->getField('now_money');
                    $data_0127=[
                        'order_no'=>$now_order['orderid'],
                        'village_id'=>$data_user_recharge_order['business_id'],
                        'money'=>$now_order['money'],
                        'uid'=>$now_order['uid'],
                        'time'=>$time,
                        'system_remarks'=>$remarks,
                        'ip'=>get_client_ip(0),
                        'desc'=>$remarks,
                        'ask'=>21,
                        'current_money'=>$now_user['now_money'],
                        'after_price'=>$get_now_money,
                        'type'=>1,
                        'withdraw_id'=>0,
                        'ask_id'=>0
                    ];
                    D('User_money_list')->data($data_0127)->add();
                   
                    if(isset($data_user_recharge_order['source']) && $data_user_recharge_order['source'] == 'village_property'){
                        //物业费预存成功，调用v20方法 自动扣款未支付物业费
                        $rr= invoke_v20_service('\app\community\model\service\HouseNewCashierService/userUnpaidOrder', ['village_id'=>$data_user_recharge_order['business_id'],'pigcms_id'=>$data_user_recharge_order['bind_id']]);
                        
                        fdump_api(['msg' => '物业费预存成功，调用v20方法 自动扣款未支付物业费', 'data_user' => $data_user_recharge_order, 'rr' => $rr], 'user_recharge_order/after_pay_log', 1);
                    }
                }
				$now_order = $this->field(true)->where($where)->find();
				D('User')->add_money($now_order['uid'],$order_param['pay_money'],L_('在线充值'));
				if(C('config.open_recharge_level')==1){
					$user_recharge_list = M('Recharge_level')->where(array('status'=>1))->order('level DESC')->select();
					foreach ($user_recharge_list as $vo) {
						if( $order_param['pay_money']>=$vo['money'] && $now_user['level']<=$vo['level']){
							$level_data['level']= $vo['level'];
							$level_data['level_time']= $_SERVER['REQUEST_TIME'];
							M('User')->where(array('uid'=>$now_order['uid']))->save($level_data);
							break;
						}
					}
				}
                fdump_api(['is_new_storage' => $is_new_storage], 'user_recharge_order/after_pay_log', 1);

				if(C('config.recharge_score_get')>0){
					$score_get = ($now_order['payment_money']*C('config.recharge_score_get'));
					$score_get= D('Percent_rate')->get_user_add_score_times($score_get,$now_order['uid']);
					D('User')->add_score($now_order['uid'],$score_get,L_('用户充值获得').C('config.score_name'));
				}

				// 充值优惠
				$give_money = $this->get_recharge_discount($data_user_recharge_order['payment_money']);
				if ($give_money['money']>0) {
					$desc = L_('在线充值').$data_user_recharge_order['payment_money']. L_('，赠送余额');
					$give_res = D('User')->add_money($now_order['uid'],$give_money['money'],L_('在线充值'));
					if (!$give_res['error_code']) {
						$discount_data = array(
							'order_id' => $now_order['order_id'],
							'discount_id' => $give_money['discount_id'],
							'uid' => $now_order['uid'],
							'pay_money' => $data_user_recharge_order['payment_money'],
							'money' => $give_money['money'],
							'add_time' => time(),
						);
						M('Recharge_discount_list')->add($discount_data);
					}
				}


				D('Scroll_msg')->add_msg('user_recharge',$now_user['uid'],L_("用户X1于X2充值成功！",array("X1" => str_replace_name($now_user['nickname']),"X2" => date('Y-m-d H:i',$_SERVER['REQUEST_TIME'])))); 
				if(empty($now_order['label'])){

					$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));

                    $href = C('config.site_url').'/wap.php?c=My&a=transaction';
					$model->sendTempMsg('OPENTM405462911',
						array(
							'href' => $href,
							'wecha_id' => $now_user['openid'],
							'first' => L_("您已成功在线充值X1，请及时查看",array("X1" => $order_param['pay_money'].C('config.Currency_txt'))), 
							'keyword1' => L_('在线充值成功'),
							'keyword2' =>L_('已发送'),
							'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']),
							'keyword4' => $now_user['nickname'],
							'remark' => '\n'.L_('请点击查看详细信息！')
						)
					);

					if (C('config.sms_place_order') == 1 || C('config.sms_place_order') == 3) {
						$sms_data['uid'] = $now_user['uid'];
						$sms_data['mobile'] = $now_user['phone'];
						$sms_data['sendto'] = 'user';
						$sms_data['content'] = L_("您于x1在线充值余额x2成功！请及时查看",array('x1'=>date('Y-m-d H:i:s'),'x2'=>$order_param['pay_money'].C('config.Currency_txt')));
						Sms::sendSms($sms_data);
					}
				}
				$spread_total_money = $now_order['payment_money'];

				if(!empty($now_user['openid'])&&C('config.open_user_spread') && (!C('config.open_spread_appoint_user') || ($now_user['can_spread']==1 && !C('config.open_spread_only_once'))) && (C('config.spread_money_limit')==0 || C('config.spread_money_limit')<=$spread_total_money) && empty(C('config.open_spread_rand'))){
					//上级分享佣金
					$spread_rate = D('Percent_rate')->get_user_spread_rate($now_order['mer_id'],'recharge',0,$now_order);
					$spread_type = 'recharge';

					$spread_users[]=$now_user['uid'];
					$spread_where['_string'] = "openid = '{$now_user['uid']}'";
					$now_user_spread = D('User_spread')->where($spread_where)->find();

					$href = C('config.site_url') . '/wap.php?g=Wap&c=My&a=spread_list&status=-1';
					if(!empty($now_user_spread)){
						$spread_user = D('User')->get_user($now_user_spread['spread_uid']);

						$user_spread_rate = $spread_rate['first_rate'];
						if($spread_user && $user_spread_rate && !in_array($spread_user['uid'],$spread_users)){
							$spread_money = round(($spread_total_money) * $user_spread_rate / 100, 2);
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
							$buy_user = D('User')->get_user($now_user_spread['openid'],'openid');
							if($spread_money>0){
								$money_name = L_('佣金');
								$model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $spread_user['openid'], 'first' => L_("X1通过您的分享在平台充值，您将获得X2。",array("X1" => $buy_user['nickname'],"X2" => $money_name)) , 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), 0);
								 D('User')->add_money($spread_user['uid'],$spread_money,L_("推广用户X1充值获得的佣金",array("X1" => $buy_user['nickname']))); 
							}
							$spread_users[]=$spread_user['uid'];
						}

						//第二级分享佣金
                        $spread_where['_string'] = "uid = '{$spread_user['uid']}' ";
						$second_user_spread = D('User_spread')->where($spread_where )->find();

						if(!empty($second_user_spread) ) {
							$second_user = D('User')->get_user($second_user_spread['spread_uid']);
							$sub_user_spread_rate = $spread_rate['second_rate'];
							if ($second_user && $sub_user_spread_rate&&!in_array($second_user['uid'],$spread_users)) {
								$spread_money = round(($spread_total_money) * $sub_user_spread_rate / 100, 2);
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
									$model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $second_user['openid'], 'first' => L_("X1的子用户X2通过您的分享在平台充值，您将获得佣金。",array("X1" => $sec_user['nickname'],"X2" => $buy_user['nickname'])) , 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')),  0);
									 D('User')->add_money($second_user['uid'],$spread_money,L_("子用户推广用户X1充值获得的佣金",array("X1" => $second_user['nickname']))); 
								}
								$spread_users[]=$second_user['uid'];
							}

							//顶级分享佣金
                            $spread_where['_string'] = "uid = '{$second_user['uid']}' ";
							$first_user_spread = D('User_spread')->where(	$spread_where)->find();

							if (!empty($first_user_spread) && C('config.user_third_level_spread')) {
								$first_spread_user = D('User')->get_user($first_user_spread['spread_uid']);
								$sub_user_spread_rate = $spread_rate['third_rate'];
								if ($first_spread_user && $sub_user_spread_rate&&!in_array($first_spread_user['uid'],$spread_users)) {
									$spread_money = round(($spread_total_money) * $sub_user_spread_rate / 100, 2);
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
										$model->sendTempMsg('OPENTM201812627', array('href' => $href, 'wecha_id' => $first_spread_user['openid'], 'first' =>L_("X1的子用户的子用户X2通过您的分享在平台充值，您将获得佣金。",array("X1" => $fir_user['nickname'],"X2" => $buy_user['nickname'])) , 'keyword1' => $spread_money, 'keyword2' => date("Y年m月d日 H:i"), 'remark' => L_('点击查看详情！')), 0);
										D('User')->add_money($first_spread_user['uid'],$spread_money,L_("子用户的子用户推广用户X1充值获得的佣金",array("X1" => $first_spread_user['nickname']))); 
									}
									// D('User')->add_money($first_spread_user['uid'],$spread_money,'子用户推广用户 '.$now_user['nickname'].' 购买ID为 '.$now_order['group_id'].' 的商品 获得的佣金');
								}
							}
						}

					}
				}elseif(!empty($now_user['openid'])&&C('config.open_spread_rand')){
		            /* 开启三级推广随机一级推广奖励功能，唯一次奖励 */
					//一级推广用户
					$spread_res = D('User_spread')->spread_rand_only_once('recharge',$now_user,$now_order);
		        }elseif(!empty($now_user['openid'])&&C('config.open_spread_appoint_user') && $now_user['can_spread']==1 && C('config.open_spread_only_once')&&(C('config.spread_money_limit')==0 || C('config.spread_money_limit')<=$spread_total_money)){
                    /* 开启指定用户享受三级推广功能且开启唯一次奖励 */
                    //一级推广用户
					$spread_res = D('User_spread')->spread_user_only_once('recharge',$spread_total_money,$now_user,$now_order);
		        }
				if($order_param['order_type'] == 'waimai-recharge'){
					return array('error'=>0,'msg'=>L_('充值成功！'),'url'=>U('Waimai/User/index'));
				} else {
                    if($is_new_storage){
                        $now_order['bind_id']=$data_user_recharge_order['bind_id'];
                    }
					return array('error'=>0,'msg'=>L_('充值成功！'),'url'=>$this->get_pay_after_url($now_order['label'],$now_order['is_mobile_pay'],$now_order));
				}


			}else{
				return array('error'=>1,'msg'=>L_('修改订单状态失败，请联系系统管理员！'));
			}
		}
	}

	// 获得当前可赠送金额
	public function get_recharge_discount($money){
		$give_money = array(
			'money' => 0,
			'discount_id' => 0,
		);
        $recharge_discount_list = M('Recharge_discount')->field(true)->where(array('status'=>1))->select();
        foreach ($recharge_discount_list as $key => $value) {
        	if ($value['full_money']<=$money && $give_money['money']<=$value['money']) {
        		$give_money['money'] = $value['money'];
        		$give_money['discount_id'] = $value['discount_id'];
        	}
        }
        return $give_money;
	}
	
	public function get_pay_after_url($label,$is_mobile = false,$now_order=array()){
		if($label){
			$labelArr = explode('_',$label);
            //todo 【业主预存】 支付成功跳转路径
			if(in_array($labelArr[0].'_'.$labelArr[1],['village_water','village_electric','village_gas','village_property','village_qunuanfei'])){
                $base_url = PD('House_village')->base_url;
                if(isset($now_order['bind_id']) && !empty($now_order['bind_id'])){
                    $pigcms_id=$now_order['bind_id'];
                } else{
                    $pigcms_id=$labelArr[3];
                }
                if($labelArr[0].'_'.$labelArr[1] == 'village_property'){ //物业费跳转路径
                    $url=$base_url . 'pages/houseMeter/NewCollectMoney/newLiveExpenses?village_id='.$labelArr[2].'&pigcms_id='.$pigcms_id;
                }
                else{
                    $url=$base_url . 'pages/houseMeter/preStorage/preStorageIndex?pigcms_id='.$pigcms_id.'&redirecturl=storage';
                }
                return $url;
            }

			if($labelArr[0] == 'wap'){
				switch($labelArr[1]){
					case 'village':
						//直接验证订单
						$order_id = $labelArr[2];
						$now_order = D('House_village_pay_order')->field(true)->where(array('order_id'=>$order_id))->find();
						if($now_order['paid']){
							return U('House/pay_order',array('order_id'=>$order_id));
						}
						$use_result = D('User')->user_money($now_order['uid'],$now_order['money'],$now_order['order_name'].' 扣除余额');
						if(empty($use_result['error_code'])){
							$data_order['order_id'] = $order_id;
							$data_order['pay_time'] = $_SERVER['REQUEST_TIME'];
							$data_order['paid'] = 1;
							// $data_order['pay_type'] = $now_order['pay_type'];
							// $data_order['payment_money'] = $now_order['payment_money'];
							D('House_village_pay_order')->data($data_order)->save();
							$now_user_info = D('House_village_user_bind')->get_one($now_order['village_id'],$now_order['bind_id'],'pigcms_id');
							if($now_order['order_type'] != 'custom'){
								switch($now_order['order_type']){
									case 'property':
										$database_house_village_property_paylist = D('House_village_property_paylist');
										$paylist_data['bind_id'] = $now_user_info['pigcms_id'];
										$paylist_data['uid'] = $now_user_info['uid'];
										$paylist_data['village_id'] = $now_user_info['village_id'];
										$paylist_data['property_month_num'] = $now_order['property_month_num'];
										$paylist_data['presented_property_month_num'] = $now_order['presented_property_month_num'];
										$paylist_data['house_size'] = $now_order['house_size'];
										$paylist_data['property_fee'] = $now_order['property_fee'];
										$paylist_data['floor_type_name'] = $now_order['floor_type_name'];

										$now_pay_info = $database_house_village_property_paylist->where(array('bind_id'=>$now_user_info['pigcms_id']))->order('add_time desc')->find();
										if(!empty($now_pay_info)){
											$paylist_data['start_time'] = $now_pay_info['end_time'] ;
											$paylist_data['end_time'] = strtotime("+" .($paylist_data['property_month_num'] + $paylist_data['presented_property_month_num']). " months", $now_pay_info['end_time']);
										}else{
											if($now_user_info['add_time'] > 0){
												$paylist_data['start_time'] = $now_user_info['add_time'];
												$paylist_data['end_time'] = strtotime("+" .($paylist_data['property_month_num'] + $paylist_data['presented_property_month_num']). " months", $now_user_info['add_time']);
											}else{
												$paylist_data['start_time'] = time();
												$paylist_data['end_time'] = strtotime("+" .($paylist_data['property_month_num'] + $paylist_data['presented_property_month_num']). " months", time());
											}

										}
										$paylist_data['add_time'] = time();
										$paylist_data['order_id'] = $order_id;
										$database_house_village_property_paylist->data($paylist_data)->add();


										$bind_field = 'property_price';
										$data_bind['property_month_num'] =  $now_user_info['property_month_num'] + $now_order['property_month_num'];
										$data_bind['presented_property_month_num'] = $now_user_info['presented_property_month_num'] + $now_order['presented_property_month_num'];

										break;
									case 'water':
										$bind_field = 'water_price';
										break;
									case 'electric':
										$bind_field = 'electric_price';
										break;
									case 'gas':
										$bind_field = 'gas_price';
										break;
									case 'park':
										$bind_field = 'park_price';
										break;
									default:
										$bind_field = '';
								}

								if(!empty($bind_field)){
									$data_bind['pigcms_id'] = $now_user_info['pigcms_id'];
									if($now_user_info[$bind_field] - $now_order['money'] >= 0){
										$data_bind[$bind_field] = $now_user_info[$bind_field] - $now_order['money'];
									}else{
										$data_bind[$bind_field] = 0;
									}
									$data_bind[$bind_field] = $now_user_info[$bind_field] - $now_order['money'] >= 0 ? $now_user_info[$bind_field] - $now_order['money'] : 0;
									D('House_village_user_bind')->data($data_bind)->save();
								}
							}else{
								$data_order['pay_money']  = $now_order['payment_money'];
								$data_order['system_score']  =0;
								$data_order['system_score_money']  =0;
								$data_order['system_balance']  = 0;
								$data_order['is_own']  = 0;
								D('House_village_pay_order')->after_pay($order_id,$data_order);
							}
							$database_user = D('User');
							$now_user = $database_user->get_user($now_order['uid']);
							if(!empty($now_user['openid'])){
								$href = C('config.site_url').'/wap.php?g=Wap&c=House&a=village_my_paylists&village_id='.$now_order['village_id'];
								$model = new templateNews(C('config.wechat_appid'),C('config.wechat_appsecret'));
								$model->sendTempMsg('TM01008', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' =>  ' 缴费成功提醒', 'keynote1' =>$now_order['order_name'], 'keynote2' =>L_('物业号 '). $now_user_info['usernum'], 'remark' => L_('缴费时间：').date('Y年n月j日 H:i',$now_order['time']).'\n'.L_('缴费金额：').C('config.Currency_symbol').$now_order['money']));
							}
						}


						return U('House/pay_order',array('order_id'=>$order_id));
					case 'activity':
						$activity_id = $labelArr[2];
						$quantity = $labelArr[3];
						return "/index.php?c=Activity&a=submit&r=1&id=".$activity_id."&q=".$quantity;
					case 'gift':
						$gift_id = $labelArr[2];
						$quantity = $labelArr[3];
						return "/wap.php?c=Gift&a=submit&r=1&order_id=".$gift_id."&q=".$quantity;
					case 'classify':
						$order_id = $labelArr[2];
						$quantity = $labelArr[3];
						return "/wap.php?c=Classify&a=submit&r=1&order_id=".$order_id."&q=".$quantity;
					case 'appoint':
						$order_id = $labelArr[2];
						return "/wap.php?c=My&a=submit&r=1&order_id=" . $order_id . '&is_initiative=1&user_recharge_order_id='.$now_order['order_id'];
					case 'crowdsourcing':
						$wap = $labelArr[2];
						$order_id = $labelArr[3];
						$status = $labelArr[4];
						if($wap == 1){
							return "/wap_house.php?c=Crowdsourcing&a=grab_single&package_id=".$order_id."&status=" . $status;
						}else{
							return "/wap.php?c=Crowdsourcing&a=grab_single&package_id=".$order_id."&status=" . $status;
						}
					case 'ride':
						$wap = $labelArr[2];
						$order_id = $labelArr[3];
						$status = $labelArr[4];
						if($wap == 1){
							return "/wap_house.php?c=Ride&a=ride_place_order&ride_id=".$order_id."&status=" . $status;
						}else{
							return "/wap.php?c=Ride&a=ride_place_order&ride_id=".$order_id."&status=" . $status;
						}
					case 'servicebuy':
						$cid = $labelArr[2];
						return "/wap.php?c=Service&a=publish_detail&cid=".$cid;
					case 'servicegive':
						$cid = $labelArr[2];
						return "/wap.php?c=Service&a=publish_detail&cid=".$cid;
					case 'service':
						$publish_id = $labelArr[2];
						return "/wap.php?c=Service&a=price_list&publish_id=".$publish_id;
					case 'nextorder':
						$rid = $labelArr[2];
						return "/wap.php?c=Yuedan&a=next_order&rid=".$rid;
					case 'level':
						return "/wap.php?c=My&a=levelUpdate";
					case 'express':
						$village_express_order_id = $labelArr[2];
						$data_village_express_order['paid']=1;
						$data_village_express_order['pay_time']=$_SERVER['REQUEST_TIME'];
						return "/wap.php?g=Wap&c=Library&a=express_submit&order_id=".$village_express_order_id;

				}
			}
			if($labelArr[0] == 'weixin'){
				switch($labelArr[1]){
					case 'activity': // 群活动
						//报名id
						$join_id = $labelArr[2];

						// 查询报名信息
						$join_info = D('Community_activity_join')->field(true)->where(array('join_id'=>$join_id))->find();
						if (!$join_info) {
                            $order_id = M('Community_order')->where(array('orderid' => $now_order['orderid']))->getField('order_id');
							return '/Appapi.php?g=Appapi&c=Comm_Apply&a=join_order_detail&order_id='.$order_id;
						}
						if($join_info['status'] == 1){
							// 已付款
							return '/Appapi.php?g=Appapi&c=Comm_Apply&a=join_order_detail&order_id='.$join_info['order_id'];
						}
                        //获取用户信息
                        $user_info = D('User')->get_user($join_info['uid']);
                        if ($user_info['now_money']  < $join_info['money']) {
                        	// 余额不足
							return '/Appapi.php?g=Appapi&c=Comm_Apply&a=join_order_detail&order_id='.$join_info['order_id'];
                        }
						 // 查询群活动
		                $activity_info = D('Community_activity')->field(true)->where(array('activity_id'=>$join_info['activity_id']))->find();

						$use_result = D('User')->user_money($join_info['uid'],$join_info['money'],L_("参加群活动【X1】报名费用，减少余额",array("X1" => $activity_info['title']))); 
						if(empty($use_result['error_code'])){
							//生成订单
							$order_data = array(
								'community_id' => $join_info['community_id'],
								'activity_id' => $join_info['activity_id'],
								'uid' => $join_info['uid'],
								'money' => $join_info['money'],
								'orderid' => $now_order['orderid'],
								'status' => 1, // 已付款
								'pay_type' => 1,//付款方式1-微信，2-支付宝，3-余额
								'type' => 1, // 订单类型:1-活动，2-加群
								'add_time' => time(),
							);
							$order_id = D('Community_order')->data($order_data)->add();
							if ($order_id) {
								$res = D('Community_order')->after_pay($order_id,$labelArr[1],$labelArr[2]);
							}
						}
						return '/Appapi.php?g=Appapi&c=Comm_Apply&a=join_order_detail&order_id='.$order_id;
					case 'joingroup': // 加群
						// 加群id
						$add_id = $labelArr[2];
						// 查询报名信息
						$join_info = D('Community_join')->field(true)->where(array('add_id'=>$add_id))->find();
						if (!$join_info) {
							return '/Appapi.php?g=Appapi&c=Comm_Group&a=group_info&community_id='.$join_info['community_id'];
						}
						if($join_info['add_status'] == 3){
							// 已付款
							return '/Appapi.php?g=Appapi&c=Comm_Group&a=group_info&community_id='.$join_info['community_id'];
						}
                        //获取用户信息
                        $user_info = D('User')->get_user($join_info['add_uid']);
                        if ($user_info['now_money']  < $join_info['charge_money']) {
                        	// 余额不足
							return '/Appapi.php?g=Appapi&c=Comm_Group&a=group_info&community_id='.$join_info['community_id'];
                        }
						
		                // 查询群信息
		                $community_info = D('Community_info')->field(true)->where(array('community_id'=>$join_info['community_id']))->find();

						$use_result = D('User')->user_money($join_info['add_uid'],$join_info['charge_money'],L_("加入群【X1】费用，减少余额",array("X1" => $community_info['community_name']))); 
						if(empty($use_result['error_code'])){
							//生成订单
							$order_data = array(
								'community_id' => $join_info['community_id'],
								'uid' => $join_info['add_uid'],
								'money' => $join_info['charge_money'],
								'orderid' => $now_order['orderid'],
								'status' => 1, // 已付款
								'pay_type' => 1,//付款方式1-微信，2-支付宝，3-余额
								'type' => 2, // 订单类型:1-活动，2-加群
								'add_time' => time(),
							);
							if ($order_id = D('Community_order')->data($order_data)->add()) {				
								$res = D('Community_order')->after_pay($order_id,$labelArr[1],$labelArr[2]);
							}
						}
						return '/Appapi.php?g=Appapi&c=Comm_Group&a=group_info&community_id='.$join_info['community_id'];
				}
			}
		}else{
			if($is_mobile){

				return '/wap.php?c=My&a=transaction';
			}else{

				return '/index.php?g=User&c=Credit&a=index';
			}
		}
	}

	//支付时，金额不够，记录到帐号
	public function wap_after_pay_error($now_order,$order_param,$error_tips){
		//记录充值的金额，因为 Pay/return_url 处没有返回order的具体信息，故在此调用。

        $user_result['error_code'] = 0;
        if($order_param['pay_money'] > 0){
            $alreadyMoney = M("User_money_list")
                ->where(['money'=>$order_param['pay_money'],'uid'=>$now_order['uid'],'ask'=> 19, 'ask_id'=>$now_order['order_id']])//19 user_recharge_order
                ->order('pigcms_id desc')
                ->find();
            if(empty($alreadyMoney)){
                //$user_result = D('User')->add_money($now_order['uid'],$order_param['pay_money'],L_("支付订单：X1发生错误！原因是：X2",array("X1" => $now_order['order_id'],"X2" => $error_tips)),19,$now_order['order_id']);
            }
        }
		if($order_param['pay_type']=='weixin'&&ACTION_NAME=='return_url'){
			exit('<xml><return_code><![CDATA[SUCCESS]]></return_code></xml>');
		}
		if($user_result['error_code']){
			return array('error'=>1,'msg'=>$user_result['msg']);
		}else{
			return array('error'=>1,'msg'=>$error_tips.L_('已将您充值的金额添加到您的余额内。'));
		}
	}

	// 导出商家对账订单信息
    public function exportRechargeOrder(&$objProps, &$objExcel, $param){
        set_time_limit(0);
        $param['type'] = 'recharge_order';
        $title = L_('充值订单');
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        // 设置当前的sheet
        $objExcel->setActiveSheetIndex(0);


        // 查询数据
		$condition_where = "`o`.`uid`=`u`.`uid` ";
        if (!empty($param['keyword'])) {
            if ($param['searchtype'] == 'orderid') {
                $where['orderid'] = htmlspecialchars($param['keyword']);
                $tmp_result = M('Tmp_orderid')->where(array('orderid' => $param['keyword']))->find();
                $condition_where .= " AND `o`.`order_id`='" . htmlspecialchars($tmp_result['order_id']) . "'";
            } elseif ($param['searchtype'] == 'name') {
                $condition_where .= " AND `u`.`nickname` like '%" . htmlspecialchars($param['keyword']) . "%'";
            } elseif ($param['searchtype'] == 'phone') {
                $condition_where .= " AND `u`.`phone` like '%" . htmlspecialchars($param['keyword']) . "%'";
            } elseif ($param['searchtype'] == 'order_id') {
                $condition_where .= " AND `o`.`order_id`='" . htmlspecialchars($param['keyword']) . "'";
            } elseif ($param['searchtype'] == 'third_id') {
                $condition_where .= " AND `o`.`third_id`='" . htmlspecialchars($param['keyword']) . "'";
            }

        }

        if (!empty($param['begin_time']) && !empty($param['end_time'])) {            
            $period = array(strtotime($param['begin_time'] . " 00:00:00"), strtotime($param['end_time'] . " 23:59:59"));
            $condition_where .= " AND  (o.pay_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
        }

        if ($param['pay_type']) {
            $condition_where .= " AND `o`.`pay_type`='" . $param['pay_type'] . "'";
        }

        $type = isset($param['type']) && $param['type'] ? $param['type'] : '';
        $sort = isset($param['sort']) && $param['sort'] ? $param['sort'] : '';
        if ($sort != 'DESC' && $sort != 'ASC') $sort = '';
        if ($type != 'price' && $type != 'pay_time') $type = '';
        $order_sort = '';
        if ($type && $sort) {
            $order_sort .= 'o.' . $type . ' ' . $sort . ',';
            $order_sort .= 'o.order_id DESC';
        } else {
            $order_sort .= 'o.order_id DESC';
        }

        $condition_where .= " AND `o`.`paid`=1";

        $condition_table = array(C('DB_PREFIX') . 'user_recharge_order' => 'o', C('DB_PREFIX') . 'user' => 'u');
        $order_count = D('')->where($condition_where)->table($condition_table)->count();


        $length = ceil($order_count / 1000);
        for ($i = 0; $i <= $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);
            $objExcel->getActiveSheet()->setTitle(L_('第X1个一千条订单信息', ($i + 1)));
            $objActSheet = $objExcel->getActiveSheet();

            // 设置表头
            $title_arr = array(
                array(40, L_('订单编号')),
                array(20, L_('充值金额')),
                array(20, L_('订单用户')),
                array(20, L_('手机号')),
                array(20, L_('下单时间')),
                array(20, L_('支付时间')),
            );

            $lastColumn = D('Order')->setPHPExcelHeader($objExcel, $title_arr, 1, 15);

            $index = 1;
            if($i != 0){
                $start = ($i-1)*1000;
                $order_list = D('')->field('`o`.*,`u`.`uid`,`u`.`nickname`,`u`.`phone`')->where($condition_where)->table($condition_table)->order($order_sort)->limit($start. ',1000')->select();
            }

            foreach ($order_list as $key  => $value) {   
                $index++;
            	$column = 0;
            	$objExcel->getActiveSheet()->setCellValueExplicit(D('order')->getLetterColumn($column++) . $index, $value['order_id']);
                $objExcel->getActiveSheet()->setCellValueExplicit(D('order')->getLetterColumn($column++) . $index, $value['money']);
                $objExcel->getActiveSheet()->setCellValueExplicit(D('order')->getLetterColumn($column++) . $index, $value['nickname']);
                $objExcel->getActiveSheet()->setCellValueExplicit(D('order')->getLetterColumn($column++) . $index, $value['phone']);
                $objExcel->getActiveSheet()->setCellValueExplicit(D('order')->getLetterColumn($column++) . $index, date('Y-m-d H:i:s', $value['add_time']));
                $objExcel->getActiveSheet()->setCellValueExplicit(D('order')->getLetterColumn($column++) . $index, $value['pay_time'] ? date('Y-m-d H:i:s', $value['pay_time']) : '');
            }
        }
        sleep(2);
    }
}
?>