<?php


class House_village_pay_orderModel extends Model{
	/*得到小区的新闻列表*/
	public function get_limit_list_page($column,$pageSize=20,$isSystem=false,$order='',$page=0){
		if(!$column['village_id']){
			return null;
		}

    	$condition_table  = array(C('DB_PREFIX').'house_village_pay_order'=>'o',C('DB_PREFIX').'house_village_user_bind'=>'b');

		$condition_where = " `o`.`village_id` = `b`.`village_id`  AND `o`.`bind_id` = `b`.`pigcms_id`  AND `o`.`village_id` =".$column['village_id'];

    	if(isset($column['paid'])){
    		$condition_where .= " AND `o`.`paid`= ".intval($column['paid']);
    	}

        if(isset($column['order_id'])){
            $condition_where .= " AND `o`.`order_id`= ".intval($column['order_id']);
        }
        if(isset($column['pay_bind_id'])){
            $condition_where .= " AND `o`.`pay_bind_id`= ".intval($column['pay_bind_id']);
        }
        if($column['bind_id']){
    		$condition_where .= " AND `o`.`bind_id`=".intval($column['bind_id']);
    	}
    	if($column['phone']){
			$condition_where .= " AND `b`.`phone` like '%".$column['phone']."%'";
		}
    	if($column['name']){
			$condition_where .= " AND REPLACE(`b`.`name`,' ','') like '%".str_replace(' ','',$column['name'])."%'";
		}
    	if($column['usernum']){
			$condition_where .= " AND `b`.`usernum` like '%".$column['usernum']."%'";
		}
		if($column['order_name']){
			$condition_where .= " AND `o`.`order_name` like '%".$column['order_name']."%'";
		}
		if($column['order_type']){
			$condition_where .= " AND `o`.`order_type` in (".$column['order_type'].")";
		}
		if(isset($column['pay_type'])){
			$condition_where .= " AND `o`.`pay_type` =".$column['pay_type'];
		}
		if(isset($column['cashier_id'])){
			$condition_where .= " AND `o`.`cashier_id` in (".$column['cashier_id'].")";
		}


		if($column['is_pay_bill']==1){
			$condition_where .= " AND (`o`.`is_pay_bill`= 1 OR `o`.`is_pay_bill`= 2)" ;
		}else if($column['is_pay_bill']==2){
			$condition_where .= " AND `o`.`is_pay_bill`= 0" ;
		}

		if($column['pay_time_str']){
			$condition_where .= ' AND '.$column['pay_time_str'];
		}
    	$condition_field = '`b`.`name` AS `username` ,b.*,o.*';
    	if (!$order) {
        	$order = ' `o`.`pay_time` DESC,`o`.`order_id` DESC';
    	}
    	
    	if($isSystem){
    		import('@.ORG.system_page');
    	}else{
    		import('@.ORG.merchant_page');
    	}
    	$count_order = D('')->table($condition_table)->where($condition_where)->count();
    	$p = new Page($count_order,$pageSize,'page');
    	if($pageSize > 0){
            $order_list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
        }else{
            $order_list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->order($order)->limit($page,1000)->select();
        }
		$database_house_village_property_paylist = D('House_village_property_paylist');
		$pay_list = $database_house_village_property_paylist->where(array('village_id'=>$column['village_id']))->select();
        // 自定义缴费周期时间
		$custom_paylist = D('House_village_payment_paylist')->where(array('village_id'=>$column['village_id']))->select();

		if ($order_list) {
			$sing_arr = M('House_village_single')->where(array('village_id' => $column['village_id']))->getField('id,single_name');
			foreach($order_list as $key=>$value){
			    $pay_name = M('House_village_pay_cashier_order')
                    ->join('AS o left join ' . C('DB_PREFIX') . 'house_village_pay_type p on p.id =o.pay_type')
                    ->where(['o.cashier_id'=>$value['cashier_id']])
                    ->getField('p.name');
			    $order_list[$key]['pay_name'] = $pay_name;
				if ($custom_paylist) {
					foreach($custom_paylist as $pay_info){
						if($value['order_id'] ==  $pay_info['order_id']){
							$order_list[$key]['custom_time_str'] = date('Y-m-d',$pay_info['start_time']) . '至' . date('Y-m-d',$pay_info['end_time']);
                            $order_list[$key]['service_start_time'] = date('Y-m-d',$pay_info['start_time']);
                            $order_list[$key]['service_end_time'] = date('Y-m-d',$pay_info['end_time']-1);
						}
					}
				}

				if($value['order_type'] == 'property'){
				    //$num = $value['property_month_num'] + $value['presented_property_month_num'];
				    $user_bind_info = M('House_village_user_bind')->where(['pigcms_id'=>$value['bind_id']])->find();
				    $start_time = $user_bind_info['property_starttime']?strtotime(date('Y-m-d 00:00:00',$user_bind_info['property_starttime'])):strtotime(date('Y-m-d 00:00:00',time()));
				    $end_time = $user_bind_info['property_endtime']?strtotime(date('Y-m-d 23:59:59',$user_bind_info['property_endtime'])):strtotime(date('Y-m-d 23:59:59',time()));
                    $order_list[$key]['property_time_str'] = date('Y-m-d',$start_time) . '至' . date('Y-m-d',$end_time);
                    $order_list[$key]['service_start_time'] = date('Y-m-d',$start_time);
                    $order_list[$key]['service_end_time'] = date('Y-m-d',$end_time);
                }

				if($value['order_type'] == 'park'){
				    if(!$value['month_end_time']){
                        $order_list[$key]['park_time_str'] = '暂无';
                    }else{
				        $time = strtotime('-'.($value['property_month_num']+$value['presented_property_month_num']).' month',strtotime(date('Y-m-d 23:59:59',$value['month_end_time'])));
                        $order_list[$key]['park_time_str'] = date('Y-m-d',time()) . '至' . date('Y-m-d',$time);
                    }
                }

                if($value['order_type'] == 'custom_payment'){
                    if(strpos($value['order_name'],'(') !== false){
                        $value['order_name'] = substr($value['order_name'],0,strrpos($value['order_name'] ,"("));
                    }
                    $time = M('House_village_payment_standard_bind')
                        ->join('AS b left join ' . C('DB_PREFIX') . 'house_village_payment p on p.payment_id =b.payment_id')
                        ->field('b.start_time,b.end_time')
                        ->where(['b.pigcms_id'=>$value['bind_id'],'b.bind_id'=>$value['payment_bind_id']])
                        ->order('b.bind_id DESC')
                        ->limit(1)
                        ->find();
                    $order_list[$key]['custom_time_str'] = date('Y-m-d',$time['start_time']) . '至' . date('Y-m-d',$time['end_time']);
                }

                if($value['order_type'] == 'park' && $value['car_type'] == 'month_type'){
                    $order_list[$key]['order_name'] = $value['order_name'].'(月租卡充值)';
                }elseif($value['order_type'] == 'park' && $value['car_type'] == 'stored_type'){
                    $order_list[$key]['order_name'] = $value['order_name'].'(停车卡储值)';
                }

				/*if(!empty($pay_list)){
					foreach($pay_list as $pay_info){
						if($value['order_id'] ==  $pay_info['order_id']){
							$order_list[$key]['property_time_str'] = date('Y-m-d',$pay_info['start_time']) . '至' . date('Y-m-d',$pay_info['end_time']);
                            $order_list[$key]['service_start_time'] = date('Y-m-d',$pay_info['start_time']);
                            $order_list[$key]['service_end_time'] = date('Y-m-d',$pay_info['end_time']);
						}
					}
				}*/
				$plat_order_where = array();
				if ($value['cashier_id']) {
					$plat_order_where = array('business_type' =>'house_village_pay_cashier' ,'business_id'=>$value['cashier_id'],'paid'=>1 );
				}else{
					$plat_order_where = array('business_type' =>'house_village_pay' ,'business_id'=>$value['order_id'],'paid'=>1 );
				}
				$plat_order = D('Plat_order')->where($plat_order_where)->find();
				$order_list[$key]['is_own'] = $plat_order ? $plat_order['is_own'] : 0;


				if($value['floor_id']){
					$floor_info = D('House_village_floor')->field('floor_name,floor_layer')->where(array('floor_id'=>$value['floor_id']))->find();
				}
				$word_data = array();
				$single_id = $value['single_id'] ? $value['single_id'] : ($floor_info['single_id']?$floor_info['single_id']:0);
				if ($sing_arr[$single_id] && $single_id>0) {
					$sing_name = $sing_arr[$single_id];
				} else {
					$sing_name = '';
				}
				$word_data['layer'] = $value['layer_num'];
				$word_data['room'] = $value['room_addrss'];
				if (!empty($floor_info)) {
					$word_data['floor_name'] = $floor_info['floor_name'];
					if (!$sing_name) {
						$sing_name = $floor_info['floor_layer'];
					}
				}
				$word_data['single_name'] = $sing_name;
				$room_diy_name = D('House_village')->word_replce_msg($word_data,$column['village_id']);
				$order_list[$key]['address'] = $room_diy_name;
			}
		} else {
			$order_list = array();
		}


        $total = D('')->field(' SUM(`o`.`money` ) AS totalMoney ')->table($condition_table)->where($condition_where." AND `o`.`order_status`=1 ")->find();
       // print_r(D('')->_sql());exit;
		$refund_total = D('')->field(' SUM(`o`.`money` ) AS totalMoney ')->table($condition_table)->where($condition_where." AND `o`.`order_status`=2 ")->find();
    	$already = D('')->field(' SUM(`o`.`money` ) AS readyMoney ')->table($condition_table)->where($condition_where." AND `o`.`is_pay_bill`=1 ")->find();
    	
    	$return['pagebar'] = $p->show();
    	$return['order_list'] = $order_list;
        $return['totalMoney'] = $total;
        $return['refund_total'] = $refund_total;
    	$return['readyMoney'] = $already;
    	
    	return $return;
	}

    public function get_limit_pay_order($column,$pageSize=20,$isSystem=false,$order=''){
        if(!$column['village_id']){
            return null;
        }

        $village_id = '';
        foreach ($column['village_id'] as $k=>$v){
            $village_id .= $v.',';
        }
        $village_id = rtrim($village_id,',');
        $village_id = '('.$village_id.')';
        $condition_table  = array(C('DB_PREFIX').'house_village_pay_order'=>'o',C('DB_PREFIX').'house_village_user_bind'=>'b');

        $condition_where = " `o`.`village_id` = `b`.`village_id`  AND `o`.`bind_id` = `b`.`pigcms_id` AND `o`.`village_id` in $village_id";

        if(isset($column['paid'])){
            $condition_where .= " AND `o`.`paid`= ".intval($column['paid']);
        }

        if($column['bind_id']){
            $condition_where .= " AND `o`.`bind_id`=".intval($column['bind_id']);
        }
        if($column['phone']){
            $condition_where .= " AND `b`.`phone` like '%".$column['phone']."%'";
        }
        if($column['name']){
            $condition_where .= " AND REPLACE(`b`.`name`,' ','') like '%".str_replace(' ','',$column['name'])."%'";
        }
        if($column['usernum']){
            $condition_where .= " AND `b`.`usernum` like '%".$column['usernum']."%'";
        }
        if($column['order_name']){
            $condition_where .= " AND `o`.`order_name` like '%".$column['order_name']."%'";
        }
        if($column['order_type']){
            $condition_where .= " AND `o`.`order_type` in (".$column['order_type'].")";
        }
        if(isset($column['pay_type'])){
            $condition_where .= " AND `o`.`pay_type` =".$column['pay_type'];
        }
        if(isset($column['cashier_id'])){
            $condition_where .= " AND `o`.`cashier_id` in (".$column['cashier_id'].")";
        }


        if($column['is_pay_bill']==1){
            $condition_where .= " AND (`o`.`is_pay_bill`= 1 OR `o`.`is_pay_bill`= 2)" ;
        }else if($column['is_pay_bill']==2){
            $condition_where .= " AND `o`.`is_pay_bill`= 0" ;
        }

        if($column['pay_time_str']){
            $condition_where .= ' AND '.$column['pay_time_str'];
        }
        $condition_field = '`b`.`name` AS `username` ,b.*,o.*';
        if (!$order) {
            $order = ' `o`.`pay_time` DESC,`o`.`order_id` DESC, `o`.`paid` ASC';
        }

        if($isSystem){
            import('@.ORG.system_page');
        }else{
            import('@.ORG.merchant_page');
        }
        $count_order = D('')->table($condition_table)->where($condition_where)->count();
        $p = new Page($count_order,$pageSize,'page');
        $order_list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
        $database_house_village_property_paylist = D('House_village_property_paylist');
        $pay_list = $database_house_village_property_paylist->where(['village_id','in',$column['village_id']])->select();
        // 自定义缴费周期时间
        $custom_paylist = D('House_village_payment_paylist')->where(['village_id','in',$column['village_id']])->select();

        if ($order_list) {
            $sing_arr = M('House_village_single')->where(['village_id'=>['in',$column['village_id']]])->getField('id,single_name');
            $village_arr = M('House_village')->where(['village_id'=>['in',$column['village_id']]])->getField('village_id,village_name');
            foreach($order_list as $key=>$value){
                if ($custom_paylist) {
                    foreach($custom_paylist as $pay_info){
                        if($value['order_id'] ==  $pay_info['order_id']){
                            $order_list[$key]['custom_time_str'] = date('Y-m-d',$pay_info['start_time']) . '至' . date('Y-m-d',$pay_info['end_time']);
                        }
                    }
                }

                if(!empty($pay_list)){
                    foreach($pay_list as $pay_info){
                        if($value['order_id'] ==  $pay_info['order_id']){
                            $order_list[$key]['property_time_str'] = date('Y-m-d',$pay_info['start_time']) . '至' . date('Y-m-d',$pay_info['end_time']);
                        }
                    }
                }
                $plat_order_where = array();
                if ($value['cashier_id']) {
                    $plat_order_where = array('business_type' =>'house_village_pay_cashier' ,'business_id'=>$value['cashier_id'],'paid'=>1 );
                }else{
                    $plat_order_where = array('business_type' =>'house_village_pay' ,'business_id'=>$value['order_id'],'paid'=>1 );
                }
                $plat_order = D('Plat_order')->where($plat_order_where)->find();
                $order_list[$key]['is_own'] = $plat_order ? $plat_order['is_own'] : 0;


                if($value['floor_id']){
                    $floor_info = D('House_village_floor')->field('floor_name,floor_layer')->where(array('floor_id'=>$value['floor_id']))->find();
                }
                $word_data = array();
                $single_id = $value['single_id'] ? $value['single_id'] : ($floor_info['single_id']?$floor_info['single_id']:0);
                if ($sing_arr[$single_id] && $single_id>0) {
                    $sing_name = $sing_arr[$single_id];
                } else {
                    $sing_name = '';
                }
                $word_data['layer'] = $value['layer_num'];
                $word_data['room'] = $value['room_addrss'];
                if (!empty($floor_info)) {
                    $word_data['floor_name'] = $floor_info['floor_name'];
                    if (!$sing_name) {
                        $sing_name = $floor_info['floor_layer'];
                    }
                }
                $word_data['single_name'] = $sing_name;
                $room_diy_name = D('House_village')->word_replce_msg($word_data,$value['village_id']);
                if (isset($village_arr[$value['village_id']]) && $village_arr[$value['village_id']]) {
                    $room_diy_name = $village_arr[$value['village_id']] . ' ' . $room_diy_name;
                }
                $order_list[$key]['address'] = $room_diy_name;
            }
        } else {
            $order_list = array();
        }


        $total = D('')->field(' SUM(`o`.`money` ) AS totalMoney ')->table($condition_table)->where($condition_where)->find();
        $already = D('')->field(' SUM(`o`.`money` ) AS readyMoney ')->table($condition_table)->where($condition_where." AND `o`.`is_pay_bill`=1 ")->find();

        $return['pagebar'] = $p->show();
        $return['order_list'] = $order_list;
        $return['totalMoney'] = $total;
        $return['readyMoney'] = $already;

        return $return;
    }


	public function get_one($order_id){
		if(!$order_id){
			return false;
		}

		return $this->where(array('order_id'=>$order_id))->find();
	}

	public function get_pay_order($order_id){
		$now_order = $this->get_one($order_id);
		$order_info = array(
				'pay_offline' 			=> false,		  //线下支付
				'pay_merchant_balance' 	=> false,		  //商家余额
				'pay_merchant_coupon' 	=> false,		  //商家优惠券
				'pay_merchant_ownpay' 	=> false,		  //商家自有支付
				'pay_system_balance' 	=> true,		  //平台余额
				'pay_system_coupon' 	=> false,		  //平台优惠券
				'pay_system_score' 		=> true,		  //平台积分抵现
				'order_info' 			=> $now_order,	  //平台积分抵现
		);
		if($now_order['order_type']=='property'){
			$order_info['pay_system_score']=true;
		}
		$now_village = D('House_village')->get_one($now_order['village_id']);
		if(C('config.open_sub_mchid') && $now_village['open_sub_mchid'] && $now_village['sub_mch_id']>0 ){
			$order_info['pay_merchant_ownpay'] = true;
		}

		if(C('config.open_sub_mchid') && $now_village['open_sub_mchid'] && $now_village['sub_mch_id']>0 && $now_village['sub_mch_discount']==0 ){
			$order_info['pay_system_score'] = false;
		}

		if(C('config.open_sub_mchid') && $now_village['open_sub_mchid'] && $now_village['sub_mch_id']>0 && $now_village['sub_mch_sys_pay']==0 ){
			$order_info['pay_system_balance'] = false;
		}
		return $order_info;
	}

    public function after_pay($order_id,$plat_order_info,$is_fenzhang=0){
        $order_info = $this->get_one($order_id);
        if($order_info['village_id']){
            $e_config = M('House_e_config')->where(array('village_id'=>$order_info['village_id']))->find();
            if(empty($e_config))
                $auto = false;
            elseif ($e_config['is_open'] == 1 && $e_config['is_tui'] == 1)
                $auto = true;
            else
                $auto = false;
        }else{
            $auto = false;
        }
        $villageBalancePay=0;
        if (isset($plat_order_info['current_village_balance']) && $plat_order_info['current_village_balance']>0){
            //判断小区住户帐户余额,扣除余额
            $villageBalancePay = $plat_order_info['current_village_balance'];
        }
        $pay_order_count = M('House_village_pay_order')->where(array('fid'=>$order_id))->count();
        fdump_api(['支付回调'.__LINE__,$order_id,$plat_order_info,$is_fenzhang,$order_info,$pay_order_count,$auto],'after_pay/house_village_pay_order',1);
        if($pay_order_count>0 && $pay_order_count){//账单一起支付
            $dataList = M('House_village_pay_order')->where(array('fid'=>$order_id))->select();
            if($dataList){
                $db_house_village_property_paylist = D('House_village_property_paylist');
                foreach($dataList as $k=>$v){
                    $now_order = $this->get_one($v['order_id']);//获取子订单支付订单号===>郭先森
                    $date_order['pay_time'] = $plat_order_info['pay_time'];
                    $date_order['paid'] = 1;
                    $date_order['score_used_count'] = $plat_order_info['system_score'];
                    $date_order['score_deducte'] = $plat_order_info['system_score_money'];
                    $user = D('User')->where(array('uid'=>$plat_order_info['uid']))->find();
                    if (intval($plat_order_info['system_score']) > 0) { // 使用了积分
                        $score_can_get = $now_order['score_can_get'];
                        D('User')->add_score($plat_order_info['uid'],$score_can_get,'缴纳'.$now_order['order_name'].'获得积分');
                    }else{ // 未使用积分
                        $scroe = $this->get_score($now_order,$user,false);
                        $score_can_get = $scroe['generate_integral'];
                        $date_order['score_can_get'] = $score_can_get;
                        D('User')->add_score($plat_order_info['uid'],$score_can_get,'缴纳'.$now_order['order_name'].'获得积分');
                    }
                    $tmp_order = $now_order;
                    $tmp_order['money']=$tmp_order['money']-$villageBalancePay;
                    $tmp_order['money']=$tmp_order['money']>0 ? round($tmp_order['money'],2):0;
                    $tmp_order['plat_order_id'] = $plat_order_info['order_id'];
                    $tmp_order['is_own'] = $plat_order_info['is_own'];
                    $tmp_order['score_used_count'] = $date_order['score_used_count'] ? $date_order['score_used_count'] : 0;
                    $tmp_order['score_can_get'] =$score_can_get ? $score_can_get : 0;
                    $tmp_order['village_balance']=$villageBalancePay;  //减掉小区住户余额
                    $tmp_order['payment_money'] = $plat_order_info['pay_money']-$villageBalancePay;
                    $tmp_order['payment_money']=$tmp_order['payment_money']>0 ? round($tmp_order['payment_money'],2):0;
                    $tmp_order['score_deducte'] = $plat_order_info['system_score_money'] ? $plat_order_info['system_score_money'] : 0;
                    $tmp_order['balance_pay'] = $plat_order_info['system_balance'];
                    if($now_order['order_type'] == 'property'){
                        $rate = 0.06;
                    }else{
                        $rate = 0.05;
                    }
                    $now_user_info = D('House_village_user_bind')->get_one_by_bindId($now_order['bind_id']);
                    switch($now_order['order_type']){
                        case 'property':
                            // $now_village = D('House_village')->get_one($now_order['village_id']);
                            // //增加积分
                            // if($now_order['score_can_get']>0){
                            // 	D('User')->add_score($plat_order_info['uid'],$now_order['score_can_get'],'交物业费获得积分');
                            // }

                            $bind_field = 'property_price';
                            $paylist_data = [];
                            $paylist_data['bind_id'] = $now_order['bind_id'];
                            $paylist_data['uid'] = $now_order['uid'];
                            $paylist_data['village_id'] = $now_order['village_id'];
                            $paylist_data['property_month_num'] = $now_order['property_month_num'] ? intval($now_order['property_month_num']) : 0;
                            $paylist_data['presented_property_month_num'] = $now_order['presented_property_month_num'] ? $now_order['presented_property_month_num'] : 0;
                            $paylist_data['house_size'] = floatval($now_order['house_size']);
                            $paylist_data['property_fee'] = floatval($now_order['property_fee']);
                            $paylist_data['floor_type_name'] = $now_order['floor_type_name'];
                            if($now_user_info['property_endtime']){
                                $paylist_data['start_time'] = $now_user_info['property_endtime']+1;
                                $paylist_data['end_time'] = strtotime("+" .($paylist_data['property_month_num'] + $paylist_data['presented_property_month_num']). " months", $now_user_info['property_endtime'])-1;
                            }else{
                                if($now_user_info['add_time'] > 0){
                                    $paylist_data['start_time'] = strtotime(date('Y-m-d 00:00:00',$now_user_info['add_time']));
                                    $paylist_data['end_time'] = strtotime("+" .($paylist_data['property_month_num'] + $paylist_data['presented_property_month_num']). " months", strtotime(date('Y-m-d 00:00:00',$now_user_info['add_time'])))-1;
                                }else{
                                    $paylist_data['start_time'] = strtotime(date('Y-m-d 00:00:00',time()));
                                    $paylist_data['end_time'] = strtotime("+" .($paylist_data['property_month_num'] + $paylist_data['presented_property_month_num']). " months", strtotime(date('Y-m-d 00:00:00',time())))-1;
                                }

                            }

                            $date_order['month_start_time'] = $paylist_data['start_time'];
                            $date_order['month_end_time'] = $paylist_data['end_time'];


                            $paylist_data['add_time'] = time();
                            $paylist_data['order_id'] = $v['order_id'];

//							$BeginDate=date('Y-m-01', strtotime(date("Y-m-d")));
//							$paylist_data['end_time'] = strtotime(date('Y-m-d', strtotime("$BeginDate +1 month -1 day")).' 23:59:59');
                            $paylist_data['end_time'] = strtotime(date('Y-m-d 23:59:59',$paylist_data['end_time']));
                            // $where['uid'] = $now_order['uid'];
                            $where['village_id'] = $now_order['village_id'];
                            $where['pigcms_id'] = $now_order['bind_id'];
                            M('House_village_user_bind')->where($where)->save(array('property_endtime'=>$paylist_data['end_time']));
                            fdump_api(['支付回调'.__LINE__,$paylist_data],'after_pay/house_village_pay_order',1);
                            $pay_id = $db_house_village_property_paylist->data($paylist_data)->add();
                            $tmp_order['desc'] = '用户交物业费';
                            $property['property_price'] = array('neq', 0);
                            $property['order_id'] = array('eq', 0);
                            M('House_village_user_paylist')->where(array('village_id'=>$now_order['village_id'],'uid'=>$now_order['uid'],'bind_id'=>$now_order['bind_id']))->where($property)->save(array('order_id'=>$v['order_id']));

                            fdump_api(['支付回调[添加]'.__LINE__,$pay_id,$db_house_village_property_paylist->getDbError()],'after_pay/house_village_pay_order',1);
                            // 存在物业时间和家属同步下业主家属信息
                            D('House_face_img')->house_user_face_device_info($now_order['uid'],$now_order['bind_id']);
                            if($now_user_info['property_endtime']!=$paylist_data['end_time']){
                                $database_house_village_user_bind = D('House_village_user_bind');
                                $childrenList = $database_house_village_user_bind->where(array('parent_id'=>$now_order['bind_id'],'status'=>1))->field('pigcms_id,uid')->select();
                                if (!empty($childrenList)) {
                                    foreach ($childrenList as $items) {
                                        D('House_face_img')->house_user_face_device_info($items['uid'],$items['pigcms_id']);
                                    }
                                }
                            }
                            break;
                        case 'water':
                            $bind_field = 'water_price';
                            $tmp_order['desc'] = '用户交水费';

                            $water['water_price'] = array('neq', 0);
                            $water['order_id'] = array('eq', 0);
                            M('House_village_user_paylist')->where(array('village_id'=>$now_order['village_id'],'uid'=>$now_order['uid'],'bind_id'=>$now_order['bind_id']))->where($water)->save(array('order_id'=>$v['order_id']));
                            fdump_api(['支付回调'.__LINE__,M('House_village_user_paylist')->_sql()],'after_pay/house_village_pay_order',1);
                            break;
                        case 'electric':
                            $bind_field = 'electric_price';
                            $tmp_order['desc'] = '用户交电费';
                            $electric['electric_price'] = array('neq', 0);
                            $electric['order_id'] = array('eq', 0);
                            M('House_village_user_paylist')->where(array('village_id'=>$now_order['village_id'],'uid'=>$now_order['uid'],'bind_id'=>$now_order['bind_id']))->where($electric)->save(array('order_id'=>$v['order_id']));
                            fdump_api(['支付回调'.__LINE__,M('House_village_user_paylist')->_sql()],'after_pay/house_village_pay_order',1);
                            break;
                        case 'gas':
                            $bind_field = 'gas_price';
                            $tmp_order['desc'] = '用户交燃气费';
                            $gas['gas_price'] = array('neq', 0);
                            $gas['order_id'] = array('eq', 0);
                            M('House_village_user_paylist')->where(array('village_id'=>$now_order['village_id'],'uid'=>$now_order['uid'],'bind_id'=>$now_order['bind_id']))->where($gas)->save(array('order_id'=>$v['order_id']));
                            fdump_api(['支付回调'.__LINE__,M('House_village_user_paylist')->_sql()],'after_pay/house_village_pay_order',1);
                            break;
                        case 'park':
                            $bind_field = 'park_price';
                            $tmp_order['desc'] = '用户交车位费';
                            $park['park_price'] = array('neq', 0);
                            $park['order_id'] = array('eq', 0);
                            M('House_village_user_paylist')->where(array('village_id'=>$now_order['village_id'],'uid'=>$now_order['uid'],'bind_id'=>$now_order['bind_id']))->where($park)->save(array('order_id'=>$v['order_id']));
                            $eTime = D('House_village_parking_car')->where(['car_id'=>$now_order['car_id']])->getField('end_time');
                            if(!$eTime){
                                $num = $now_order['property_month_num']+$now_order['presented_property_month_num'];
                                $eTime = strtotime('+'.$num.' month',strtotime(date('Y-m-d 00:00:00',time())))-1;
                                D('House_village_parking_car')->where(['car_id'=>$now_order['car_id']])->save(['start_time'=>strtotime(date('Y-m-d 00:00:00',time())),'end_time'=>$eTime]);
                            }else{
                                $num = $now_order['property_month_num']+$now_order['presented_property_month_num'];
                                $eTime = strtotime('+'.$num.' month',strtotime(date('Y-m-d 00:00:00',$eTime)))-1;
                                D('House_village_parking_car')->where(['car_id'=>$now_order['car_id']])->save(['end_time'=>$eTime]);
                            }
                            break;
                        case 'custom_payment':
                            $bind_field = '';
                            $tmp_order['desc']= $plat_order_info['order_name'];
                            // 计算缴费时间
                            $bind_payment = M('House_village_payment_standard_bind')->where(array('bind_id'=>$now_order['payment_bind_id']))->find();
                            $payment_standard = M('House_village_payment_standard')->where(array('standard_id'=>$bind_payment['standard_id']))->find();
                            M('House_village_payment_standard_unpaid')->where(array('village_id'=>$now_order['village_id'],'bind_id'=>$now_order['bind_id'],'payment_bind_id'=>$now_order['payment_bind_id'],'uid'=>$now_order['uid']))->save(array('order_id'=>$v['order_id']));
                            $paylist_data = array();
                            $paylist_data['bind_id'] = $now_order['bind_id'];
                            $paylist_data['uid'] = $now_order['uid'];
                            $paylist_data['village_id'] = $now_order['village_id'];
                            $paylist_data['paid_cycle'] = $now_order['payment_paid_cycle'];
                            switch ($payment_standard['cycle_type']) {
                                case 'Y': // 年
                                    // 计算到期时间 = 开始时间 + 已缴时间（收费周期*已缴周期）
                                    $start_time = strtotime("+" .($payment_standard['pay_cycle'] * $bind_payment['paid_cycle']  ). " years", strtotime(date('Y-m-d 00:00:00',$bind_payment['start_time'])));
                                    $end_time = strtotime("+" .($payment_standard['pay_cycle'] * $paylist_data['paid_cycle'] ). " years", strtotime(date('Y-m-d 00:00:00',$start_time)))-1;
                                    break;
                                case 'M': //月
                                    $start_time = strtotime("+" .($payment_standard['pay_cycle'] * $bind_payment['paid_cycle']  ). " months", strtotime(date('Y-m-d 00:00:00',$bind_payment['start_time'])));
                                    $end_time = strtotime("+" .($payment_standard['pay_cycle'] * $paylist_data['paid_cycle'] ). " months", strtotime(date('Y-m-d 00:00:00',$start_time)))-1;;
                                    break;
                                case 'D': // 日
                                    $start_time = strtotime("+" .($payment_standard['pay_cycle'] * $bind_payment['paid_cycle']  ). " days", strtotime(date('Y-m-d 00:00:00',$bind_payment['start_time'])));
                                    $end_time = strtotime("+" .($payment_standard['pay_cycle'] * $paylist_data['paid_cycle'] ). " days", strtotime(date('Y-m-d 00:00:00',$start_time)))-1;;
                                    break;
                            }
                            $paylist_data['start_time'] = $start_time;
                            $paylist_data['end_time'] = $end_time;
                            $paylist_data['add_time'] = time();
                            $paylist_data['order_id'] = $v['order_id'];
                            D('House_village_payment_paylist')->data($paylist_data)->add();

                            //修改绑定缴费项目已缴周期
                            M('House_village_payment_standard_bind')->where(array('bind_id'=>$now_order['payment_bind_id']))->setInc('paid_cycle',$now_order['payment_paid_cycle']);
                        default:
                            $bind_field = '';
                            $tmp_order['desc']= $plat_order_info['order_name'];
                    }

                    $this->where(array('order_id'=>$v['order_id']))->save($date_order);
                    if(!empty($bind_field)){
//                        $now_user_info = D('House_village_user_bind')->get_one($now_order['village_id'],$now_order['bind_id'],'pigcms_id');
                        $data_bind['pigcms_id'] = $now_user_info['pigcms_id'];
                        if($now_user_info[$bind_field] - $now_order['money'] >= 0){
                            $data_bind[$bind_field] = $now_user_info[$bind_field] - $now_order['money'];
                        }else{
                            $data_bind[$bind_field] = 0;
                        }
                        $data_bind[$bind_field] = $now_user_info[$bind_field] - $now_order['money'] >= 0 ? $now_user_info[$bind_field] - $now_order['money'] : 0;
                        D('House_village_user_bind')->data($data_bind)->save();

                    }
                    $tmp_order['order_type'] = 'village_pay';
                    if($plat_order_info['is_own']==0 || $plat_order_info['is_own']==1)
                        $plat_order_info['is_own']+=4;
                    //社区对账
                    if(isset($plat_order_info['is_chinaums_divide']) && $plat_order_info['is_chinaums_divide']==1){
                        $tmp_order['is_chinaums_divide']=1;
                        $tmp_order['chinaums_divide_money']=$plat_order_info['chinaums_divide_money'] ? $plat_order_info['chinaums_divide_money']:0;
                        $tmp_order['chinaums_system_take']=$plat_order_info['chinaums_system_take'] ? $plat_order_info['chinaums_system_take']:0;
                    }
                    //社区分账
                    if(isset($plat_order_info['is_pay_need_divide']) && $plat_order_info['is_pay_need_divide']==1){
                        $tmp_order['is_pay_need_divide']=1;
                        $tmp_order['pay_divide_type']=isset($plat_order_info['pay_divide_type']) ? $plat_order_info['pay_divide_type']:"" ;
                        $tmp_order['divide_village_money']=isset($plat_order_info['divide_village_money']) ? $plat_order_info['divide_village_money']:0;
                        $tmp_order['divide_system_take']=isset($plat_order_info['divide_system_take']) ? $plat_order_info['divide_system_take']:0;
                        $tmp_order['divide_desc']=isset($plat_order_info['divide_desc']) ? $plat_order_info['divide_desc']:'';
                    }
                    $res = D('SystemBill')->bill_method($plat_order_info['is_own'],$tmp_order,$is_fenzhang);

					if(!$res['error_code']){
						$this->where(array('order_id'=>$order_id))->setField('is_pay_bill',2);
					}
					$now_user = D('User')->get_user($now_order['uid']);
                    $village_info = D('House_village')->where(array('village_id'=>$now_order['village_id']))->find();
					if(!empty($now_user['openid'])){
						$href = C('config.site_url').'/wap.php?g=Wap&c=House&a=village_my_paylists&village_id='.$now_order['village_id'].'&pigcms_id='.$now_order['bind_id'];
						$model = new templateNews(C('config.wechat_appid'),C('config.wechat_appsecret'));
						$model->sendTempMsg('TM01008', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' =>  ' 缴费成功提醒', 'keynote1' =>$village_info['village_name'], 'keynote2' =>'物业号 '. $now_user_info['usernum'], 'remark' => '缴费时间：'.date('Y年n月j日 H:i',$now_order['time']).'\n'.'缴费金额：'.C('config.Currency_symbol').$now_order['money']),0,$village_info['property_id'],2);
					}
                    $village_bind = D('House_village_user_bind')->where(array('pigcms_id'=>$now_order['bind_id']))->find();
                    if ($village_bind['single_id'] && $village_bind['floor_id'] && $village_bind['layer_id'] && $village_bind['vacancy_id'] && $village_bind['village_id']) {
                        $address = D('House_village')->get_single_floor_room($village_bind['single_id'],$village_bind['floor_id'],$village_bind['layer_id'],$village_bind['vacancy_id'],$village_bind['village_id']);
                        if ($address) {
                            $village_bind['address'] = $address;
                        }
                    }
                    if($village_info['openid']){
                        $href = "";
                        $model = new templateNews(C('config.wechat_appid'),C('config.wechat_appsecret'));
                        $model->sendTempMsg('TM01008', array('href' => $href, 'wecha_id' => $village_info['openid'], 'first' =>  ' 业主 '.$village_bind['name'].'( '.$village_bind['address'].' )缴费成功提醒', 'keynote1' =>$village_info['property_name'], 'keynote2' =>$village_bind['name']."( ".$village_bind['phone']." )", 'remark' => '缴费周期： '.($now_order['property_month_num'] ? $now_order['property_month_num'] : ($now_order['payment_paid_cycle'] ? $now_order['payment_paid_cycle'] : 0)).' 个月\n缴费时间：'.date('Y年n月j日 H:i',$now_order['time']).'\n'.'缴费金额：'.C('config.Currency_symbol').$now_order['money']),0,$village_info['property_id'],2);
                    }
                    //判断是否自动开具电子发票
                    if($auto){
                        $order_no = date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
                        $name = !empty($now_user_info['name'])?$now_user_info['name']:$now_order['order_name'];
                        $res = $this->addInvoice($name,$user['phone'],$order_no,$e_config['duty_paragraph'],$now_order['order_name'],$now_order['money'],$rate,$village_info['property_id']);
                        if($res['status'] == '0000'){
                            $this->addERecord($e_config['duty_paragraph'],$name,$now_order['money'],$user['phone'],$res['fpqqlsh'],$order_no,$now_order['order_name'],$v['order_id'],$now_order['bind_id']);
                        }
                    }
                }
            }
        }else{//单个支付
            $now_order = $this->get_one($order_id);
            $date_order['pay_time'] = $plat_order_info['pay_time'];
            $date_order['paid'] = 1;
            $date_order['score_used_count'] = $plat_order_info['system_score'];
            $date_order['score_deducte'] = $plat_order_info['system_score_money'];
            $user = D('User')->where(array('uid'=>$plat_order_info['uid']))->find();
            if (intval($plat_order_info['system_score']) > 0) { // 使用了积分
                $score_can_get = $now_order['score_can_get'];
                D('User')->add_score($plat_order_info['uid'],$score_can_get,'缴纳'.$now_order['order_name'].'获得积分');
            }else{ // 未使用积分
                $scroe = $this->get_score($now_order,$user,false);
                $score_can_get = $scroe['generate_integral'];
                $date_order['score_can_get'] = $score_can_get;
                D('User')->add_score($plat_order_info['uid'],$score_can_get,'缴纳'.$now_order['order_name'].'获得积分');
            }



            $db_house_village_property_paylist = D('House_village_property_paylist');
            $tmp_order = $now_order;
            $tmp_order['money']=$tmp_order['money']-$villageBalancePay;
            $tmp_order['money']=$tmp_order['money']>0 ? round($tmp_order['money'],2):0;
            $tmp_order['plat_order_id'] = $plat_order_info['order_id'];
            $tmp_order['is_own'] = $plat_order_info['is_own'];
            $tmp_order['score_used_count'] = $date_order['score_used_count'] ? $date_order['score_used_count'] : 0;
            $tmp_order['score_can_get'] =$score_can_get ? $score_can_get : 0;
            $tmp_order['payment_money'] = $plat_order_info['pay_money']-$villageBalancePay;
            $tmp_order['payment_money']=$tmp_order['payment_money']>0 ? round($tmp_order['payment_money'],2):0;
            $tmp_order['score_deducte'] = $plat_order_info['system_score_money'];
            $tmp_order['balance_pay'] = $plat_order_info['system_balance'];
            if($now_order['order_type'] == 'property'){
                $rate = 0.06;
            }else{
                $rate = 0.05;
            }
            $now_user_info = D('House_village_user_bind')->get_one_by_bindId($now_order['bind_id']);
            switch($now_order['order_type']){
                case 'property':
                    // $now_village = D('House_village')->get_one($now_order['village_id']);
                    // //增加积分
                    // if($now_order['score_can_get']>0){
                    // 	D('User')->add_score($plat_order_info['uid'],$now_order['score_can_get'],'交物业费获得积分');
                    // }

                    $bind_field = 'property_price';
                    $paylist_data['bind_id'] = $now_order['bind_id'];
                    $paylist_data['uid'] = $now_order['uid'];
                    $paylist_data['village_id'] = $now_order['village_id'];
                    $paylist_data['property_month_num'] = $now_order['property_month_num'] ? intval($now_order['property_month_num']) : 0;
                    $paylist_data['presented_property_month_num'] = $now_order['presented_property_month_num'] ? intval($now_order['presented_property_month_num']) : 0;
                    $paylist_data['house_size'] = floatval($now_order['house_size']);
                    $paylist_data['property_fee'] = floatval($now_order['property_fee']);
                    $paylist_data['floor_type_name'] = $now_order['floor_type_name'];
                    if($now_user_info['property_endtime']){
                        $paylist_data['start_time'] = strtotime(date('Y-m-d 23:59:59',$now_user_info['property_endtime']))+1;
                        $paylist_data['end_time'] = strtotime("+" .($paylist_data['property_month_num'] + $paylist_data['presented_property_month_num']). " months", strtotime(date('Y-m-d 00:00:00',$now_user_info['property_endtime'])))-1;
                    }else{
                        if($now_user_info['add_time'] > 0){
                            $paylist_data['start_time'] = strtotime(date('Y-m-d 00:00:00',$now_user_info['add_time']));
                            $paylist_data['end_time'] = strtotime("+" .($paylist_data['property_month_num'] + $paylist_data['presented_property_month_num']). " months", strtotime(date('Y-m-d 00:00:00',$now_user_info['add_time'])))-1;
                        }else{
                            $paylist_data['start_time'] = strtotime(date('Y-m-d 00:00:00',time()));
                            $paylist_data['end_time'] = strtotime("+" .($paylist_data['property_month_num'] + $paylist_data['presented_property_month_num']). " months", strtotime(date('Y-m-d 00:00:00',time())))-1;
                        }
                    }

                    $date_order['month_start_time'] = $paylist_data['start_time'];
                    $date_order['month_end_time'] = $paylist_data['end_time'];

                    $paylist_data['add_time'] = time();
                    $paylist_data['order_id'] = $order_id;

//					$BeginDate=date('Y-m-01', strtotime(date("Y-m-d")));
//					$paylist_data['end_time'] = strtotime(date('Y-m-d', strtotime("$BeginDate +1 month -1 day")).' 23:59:59');

                    // $where['uid'] = $now_order['uid'];
                    $where['village_id'] = $now_order['village_id'];
                    $where['pigcms_id'] = $now_order['bind_id'];
                    M('House_village_user_bind')->where($where)->save(array('property_endtime'=>$paylist_data['end_time']));
                    fdump_api(['支付回调'.__LINE__,$paylist_data],'after_pay/house_village_pay_order',1);
                    $db_house_village_property_paylist->data($paylist_data)->add();
                    $tmp_order['desc'] = '用户交物业费';
                    $where['property_price'] = array('neq', 0);
                    $where['order_id'] = array('eq', 0);
                    M('House_village_user_paylist')->where(array('village_id'=>$now_order['village_id'],'uid'=>$now_order['uid'],'bind_id'=>$now_order['bind_id']))->where($where)->save(array('order_id'=>$order_id));

                    // 存在物业时间和家属同步下业主家属信息
                    D('House_face_img')->house_user_face_device_info($now_order['uid'],$now_order['bind_id']);
                    if($now_user_info['property_endtime']!=$paylist_data['end_time']){
                        $database_house_village_user_bind = D('House_village_user_bind');
                        $childrenList = $database_house_village_user_bind->where(array('parent_id'=>$now_order['bind_id'],'status'=>1))->field('pigcms_id,uid')->select();
                        if (!empty($childrenList)) {
                            foreach ($childrenList as $items) {
                                D('House_face_img')->house_user_face_device_info($items['uid'],$items['pigcms_id']);
                            }
                        }
                    }
                    break;
                case 'water':
                    $bind_field = 'water_price';
                    $tmp_order['desc'] = '用户交水费';
                    $where['water_price'] = array('neq', 0);
                    $where['order_id'] = array('eq', 0);
                    M('House_village_user_paylist')->where(array('village_id'=>$now_order['village_id'],'uid'=>$now_order['uid'],'bind_id'=>$now_order['bind_id']))->where($where)->save(array('order_id'=>$order_id));
                    break;
                case 'electric':
                    $bind_field = 'electric_price';
                    $tmp_order['desc'] = '用户交电费';
                    $where['electric_price'] = array('neq', 0);
                    $where['order_id'] = array('eq', 0);
                    M('House_village_user_paylist')->where(array('village_id'=>$now_order['village_id'],'uid'=>$now_order['uid'],'bind_id'=>$now_order['bind_id']))->where($where)->save(array('order_id'=>$order_id));
                    break;
                case 'gas':
                    $bind_field = 'gas_price';
                    $tmp_order['desc'] = '用户交燃气费';
                    $where['gas_price'] = array('neq', 0);
                    $where['order_id'] = array('eq', 0);
                    M('House_village_user_paylist')->where(array('village_id'=>$now_order['village_id'],'uid'=>$now_order['uid'],'bind_id'=>$now_order['bind_id']))->where($where)->save(array('order_id'=>$order_id));
                    break;
                case 'park':
                    $tmp_order['desc'] = '停车缴费';
                    $park_config = M('House_village_park_config')->where(array('village_id'=>$now_order['village_id']))->find();
                    if(!$park_config || $park_config['park_versions'] == 1){
                        $bind_field = 'park_price';
                        $where['park_price'] = array('neq', 0);
                        $where['order_id'] = array('eq', 0);

                        M('House_village_user_paylist')->where(array('village_id'=>$now_order['village_id'],'uid'=>$now_order['uid'],'bind_id'=>$now_order['bind_id']))->where($where)->save(array('order_id'=>$order_id));
                    }elseif($park_config['park_versions'] == 2){//新版

                        $pay_order = $this->where(array('order_id'=>$order_id))->find();
                        if (!$pay_order) {
                            $pay_order = $now_order;
                        }
                        fdump_api(['park-'.__LINE__,$pay_order['car_type']],'after_pay/house_village_pay_order',true);
                        if($pay_order['car_type'] == 'month_type'){//月租
                            $parking_car_single = D('House_village_parking_car')->where(array('car_id'=>$pay_order['car_id']))->find();
                            $parking_position = D('House_village_parking_position')->where(array('position_id'=>$parking_car_single['car_position_id']))->find();
                            $parking_car = D('House_village_parking_car')->where(array('car_position_id'=>$parking_position['position_id']))->select();
                            $house_village = D('House_village')->where(array('village_id'=>$pay_order['village_id']))->find();
                            $channel_number_arr = M('Park_passage')->where(array('village_id'=>$pay_order['village_id']))->getField('channel_number',true);
                            $channel_number_str = implode(',', $channel_number_arr);
                            if (!$channel_number_str) {
                                $channel_number_str = '';
                            }
                            $park_system = M('Park_system')->where(array('park_id'=>$pay_order['village_id']))->find();
                            $nowtime = date("mdHis");
                            $trade_no = $nowtime . sprintf("%04d", $order_id);
                            // 月租车暂时不支持逗号分隔方式上传 只能一个一个上传
                            if (2!=$parking_position['position_pattern'] && 0) {
                                // 固定停车位
                                $park_position_set = [];
                                $park_data['begin_time'] = $parking_position['start_time'] ?   strtotime(date('Y-m-d 00:00:00',$parking_position['start_time'])) : strtotime(date('Y-m-d 00:00:00',time()));
                                $park_data['end_time'] = strtotime(date('Y-m-d 00:00:00',$pay_order['month_end_time']))-1;
                                $car_number_arr = [];
                                foreach($parking_car as $vl){
                                    $car_number_arr[] = $vl['province'].$vl['car_number'];
                                }
                                $car_number_str = implode(',', $car_number_arr);
                                if ($car_number_str) {
                                    // 处理下开始时间-开始时间大于当前时间的以当前时间为准
                                    if (intval($park_data['begin_time']) > intval(time())) {
                                        $park_data['begin_time'] = time();
                                    }
                                    $park_position_set['start_time'] = $park_data['begin_time'];
                                    $park_position_set['end_time'] = $park_data['end_time'];

                                    $park_data['park_id'] = $parking_position['village_id'];
                                    $park_data['remark'] = '';
                                    $park_data['car_number'] = $car_number_str;
                                    $park_data['tel'] = '';
                                    $park_data['pid'] = '';

                                    if ($parking_position['card_id']) {
                                        $park_data['operate_type'] = '2';//更新操作
                                        $park_data['update_time'] = time();
                                        $card_id = $parking_position['card_id'];
                                    } else {
                                        $card_id = '1'.sprintf("%09d",$parking_position['position_id']);//月卡会员编号（收费系统唯一编号  是
                                        $park_position_set['card_id'] = $card_id;
                                        $park_data['operate_type'] = '1';//添加操作
                                        $park_data['create_time'] = time();
                                    }
                                    $park_data['card_id'] = $card_id;
                                    $park_data['p_lot'] = strval($parking_position['position_num']);
                                    $park_data['price'] = $pay_order['money'];//实收金额
                                    $park_data['name'] = '车位号：'.$parking_position['position_num'];
                                    $park_data['car_type_id'] = '月租车A';
                                    $park_data['amount_receivable'] = $pay_order['money'];//应收金额
                                    $park_data['channel_id'] = '';//月卡允许通行的通道编号
                                    $park_data['tel'] = $parking_car['car_user_phone'] ? $parking_car['car_user_phone'] : '';
                                    $park_data['address'] = $house_village['village_name'];
                                    $json_data['service_name'] = 'month_member_sync';
                                    $json_data['msg_id'] = createRandomStr(8,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(12,true,true);//是
                                    $json_data['data'] = $park_data;

                                    $post_data['unserialize_desc'] = serialize($json_data);
                                    $post_data['card_id'] = $card_id;
                                    $post_data['status'] = 2;
                                    $post_data['msg_id'] = $json_data['msg_id'];
                                    $post_data['village_id'] = $parking_position['village_id'];
                                    $post_data['park_id'] = $park_system['park_id'];
                                    $post_data['token'] = $park_system['token'];
                                    $post_data['service_name'] = 'month_member_sync';
                                    $post_data['create_time'] = time();
                                    $res = M('Park_total_record')->data($post_data)->add();//月卡会员下发
                                    if($res){
                                        D('House_village_parking_position')->where(array('position_id'=>$parking_position['position_id']))->data($park_position_set)->save();
                                        D('House_village_parking_car')->where(array('car_position_id'=>$parking_position['position_id']))->data($park_position_set)->save();
                                    }
                                } else {
                                    // 打印记录下
                                    fdump($pay_order,'api/log/'.date('Ymd').'/park/'.$park_system['park_id'].'/month_type_add_log',true);
                                    fdump($parking_car_single,'api/log/'.date('Ymd').'/park/'.$park_system['park_id'].'/month_type_add_log',true);
                                    fdump($parking_position,'api/log/'.date('Ymd').'/park/'.$park_system['park_id'].'/month_type_add_log',true);
                                    fdump($parking_car,'api/log/'.date('Ymd').'/park/'.$park_system['park_id'].'/month_type_add_log',true);
                                }
                            }
                            else {
                                if ($park_config['park_sys_type'] && 'D1'==$park_config['park_sys_type']) {
                                    $park_sys_type = 'D1';
                                } else {
                                    $park_sys_type = 'A1';
                                }
                                if (2!=$parking_position['position_pattern']) {
                                    // 固定车位-共同使用一个月卡会员编号
                                    if ($parking_position['card_id']) {
                                        $now_car_id = $parking_position['card_id'];
                                    } else {
                                        $now_car_id = '1'.sprintf("%09d",$parking_position['position_id']);//月卡会员编号（收费系统唯一编号  是
                                        $park_position_set['card_id'] = $now_car_id;
                                    }
                                    $address = $house_village['village_name'] .'车位：'.$parking_position['position_num'];
                                } else {
                                    $address = $house_village['village_name'];
                                }
                                // 如果是虚拟停车位 对应每一个车生成唯一收费编号
                                $parking_car_type_arr = D('House_village_parking_car')->parking_car_type_arr;
                                foreach($parking_car as $k=>$v){
                                    $park_set = [];
                                    $park_data['begin_time'] = $v['start_time'] ?  strtotime(date('Y-m-d 00:00:00',$v['start_time'])) : strtotime(date('Y-m-d 00:00:00',time())) ;
//                                    $park_data['end_time'] = strtotime(date('Y-m-d 00:00:00',$pay_order['month_end_time']))-1;
                                    $park_data['end_time'] = strtotime(date('Y-m-d 23:59:59',$pay_order['month_end_time']));
                                    if (intval($park_data['end_time']) > intval(time())) {
                                        $park_data['begin_time'] = time();
                                    }
                                    $park_set['start_time'] = $park_data['begin_time'];
                                    $park_set['end_time'] = $park_data['end_time'];
                                    if ($park_sys_type!='D1') {
                                        $park_data['park_id'] = $v['village_id'];
                                    }
                                    $park_data['car_number'] = $v['province'].$v['car_number'];
                                    if ($now_car_id) {
                                        $park_position_set['start_time'] = $park_data['begin_time'];
                                        $park_position_set['end_time'] = $park_data['end_time'];
                                    }
                                    fdump_api(['$parking_car结果集--'.__LINE__,$v,$park_config],'after_pay/parking_car',true);
                                    if($park_config['park_sys_type'] == 'D5'){
                                        //todo D5智慧停车 触发下发到设备
                                        invoke_v20_service('\app\community\model\service\Park\D5Service/D5AddCar',[
                                            'village_id'=>$v['village_id'],
                                            'car_id'=>$v['car_id'],
                                            'start_time'=>$park_set['start_time'],
                                            'end_time'=>$park_set['end_time']
                                        ]);
                                    }
                                    if(false && $v['card_id']){	//暂停使用续费操作，全都以编辑车辆下发
                                        // 3.16月卡续费记录下发(停车云)
                                        $service_name = 'month_pay_sync';// 续费操作
                                        if ($now_car_id) {
                                            $card_id = $now_car_id;
                                        } else {
                                            $card_id = $v['card_id'];
                                        }
                                        $park_data['trade_no'] = $trade_no;
                                        $park_data['pay_time']  = $pay_order['pay_time'] ? $pay_order['pay_time'] : time();
                                        $park_data['amount_receivable'] = $pay_order['money'];
                                        $park_data['amount_pay'] = $pay_order['money'];
                                        $park_data['pay_type'] = $pay_order['pay_type']==0?'线上支付':'现金';
                                    }else{
                                        // 3.7月卡会员同步（停车云）
                                        $park_data['remark'] = '';
                                        $park_data['pid'] = '';
                                        $service_name = 'month_member_sync';// 添加会员卡操作
                                        if ($now_car_id) {
                                            $card_id = $now_car_id;
                                        } else {
                                            $card_id = '2'.sprintf("%09d",$v['car_id']);//月卡会员编号（收费系统唯一编号  是
                                        }
                                        // 同步储值卡
                                        if ($parking_car_type_arr && $v['parking_car_type'] && $parking_car_type_arr[$v['parking_car_type']]) {
                                            $car_type_id = $parking_car_type_arr[$v['parking_car_type']];
                                        } else if($v['temporary_car_type']) {
                                            $car_type_id = $v['store_value_car_type'];
                                        } else {
                                            $car_type_id = '储值车A';
                                        }
                                        $park_set['card_id'] = $card_id;
                                        if ($park_sys_type=='D1' && $v['card_id']) {
                                            $park_data['is_edit'] = 1;
                                        } elseif ($park_sys_type!='D1') {
                                            $park_data['operate_type'] = $v['card_id'] ? '2' : '1';	//1 添加，2 编辑
                                            if ($park_data['operate_type']==1) {
                                                $park_data['create_time'] = time();
                                            } else {
                                                $park_data['update_time'] = time();
                                            }
                                            $park_data['price'] = $pay_order['money'];//实收金额
                                            $park_data['channel_id'] = $channel_number_str;//月卡允许通行的通道编号
                                            $park_data['amount_receivable'] = $pay_order['money'];//应收金额
                                        }
                                        $park_data['name'] = $v['car_user_name'];
                                        $park_data['car_type_id'] = $car_type_id;
                                        $park_data['tel'] = $v['car_user_phone'] ? $v['car_user_phone'] : '';
                                        $park_data['address'] = $address;
                                        $park_data['p_lot'] = strval($parking_position['position_num']);
                                    }
                                    $park_data['card_id'] = $card_id;
                                    //2021/2/1 多车多位  修改 start
                                    $infos = D('House_village_parking_car')->bind_parking_position($card_id);
                                    if($infos){
                                        $park_data['car_group'] = intval($infos['car_group']);
                                        $park_data['p_lot_number'] = intval($infos['p_lot_number']);
                                    }
                                    //2021/2/1 多车多位  修改 end
                                    $json_data['msg_id'] = createRandomStr(8,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(12,true,true);//是
                                    $json_data['data'] = $park_data;
                                    $json_data['service_name'] = $service_name;

                                    $post_data['unserialize_desc'] = serialize($json_data);
                                    $post_data['status'] = 2;
                                    $post_data['card_id'] = $card_id;
                                    $post_data['msg_id'] = $json_data['msg_id'];
                                    $post_data['village_id'] = $v['village_id'];
                                    $post_data['park_id'] = $park_system['park_id'];
                                    $post_data['token'] = $park_system['token'];
                                    $post_data['car_number'] = $park_data['car_number']?$park_data['car_number']:'';
                                    $post_data['service_name'] = $service_name;
                                    $post_data['create_time'] = time();
                                    $res = M('Park_total_record')->data($post_data)->add();//月卡会员下发
									
									// 没有配置停车场，或者数据添加成功都保存车辆信息。
                                    if(!($park_system['park_id'] && $park_system['token']) || $res){
                                        D('House_village_parking_car')->where(array('car_id'=>$v['car_id']))->data($park_set)->save();
                                        if ($park_position_set) {
                                            D('House_village_parking_position')->where(array('position_id'=>$parking_position['position_id']))->data($park_position_set)->save();
                                        }
                                    } else {
                                        //2021/2/1 多车多位  修改 start
                                        $infos = D('House_village_parking_car')->bind_parking_position($card_id);
                                        if($infos){
                                            $park_data['car_group'] = intval($infos['car_group']);
                                            $park_data['p_lot_number'] = intval($infos['p_lot_number']);
                                        }
                                        //2021/2/1 多车多位  修改 end
                                        $json_data['msg_id'] = createRandomStr(8,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(12,true,true);//是
                                        $json_data['data'] = $park_data;
                                        $json_data['service_name'] = $service_name;

                                        $post_data['unserialize_desc'] = serialize($json_data);
                                        $post_data['status'] = 2;
                                        $post_data['card_id'] = $card_id;
                                        $post_data['msg_id'] = $json_data['msg_id'];
                                        $post_data['village_id'] = $v['village_id'];
                                        $post_data['park_id'] = $park_system['park_id'];
                                        $post_data['token'] = $park_system['token'];
                                        $post_data['car_number'] = $park_data['car_number']?$park_data['car_number']:'';
                                        $post_data['service_name'] = $service_name;
                                        $post_data['create_time'] = time();
                                        $res = M('Park_total_record')->data($post_data)->add();//月卡会员下发
                                        if($res){
                                            D('House_village_parking_car')->where(array('car_id'=>$v['car_id']))->data($park_set)->save();
                                            if ($park_position_set) {
                                                D('House_village_parking_position')->where(array('position_id'=>$parking_position['position_id']))->data($park_position_set)->save();
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        elseif($pay_order['car_type'] == 'stored_type'){//储值
                            $house_village = D('House_village')->where(array('village_id'=>$pay_order['village_id']))->find();
                            $address = $house_village['village_name'];

                            $parking_car = D('House_village_parking_car')->where(array('car_id'=>$pay_order['car_id']))->find();
                            $park_system = M('Park_system')->where(array('park_id'=>$pay_order['village_id']))->find();

                            $nowtime = date("mdHis");
                            $trade_no = $nowtime . sprintf("%04d", $order_id);
                            if ($parking_car['stored_card']) {
                                // 储值卡续充
                                $service_name = 'prepay_card_trade_sync';
                                $park_data['trade_no'] = $trade_no;
                                $park_data['card_id'] = $parking_car['stored_card'];
                                $park_data['pay_time'] = $pay_order['pay_time'] ? $pay_order['pay_time'] : time();
                                $park_data['pay_type'] = $pay_order['pay_type']==0?'线上支付':'现金';
                                $park_data['total_price'] = $pay_order['money'];
                                $park_data['car_number'] = $parking_car['province'].$parking_car['car_number'];
                                $park_data['mobile'] = $parking_car['car_user_phone'];
                                $park_data['park_id'] = $park_system['park_id'];
                                $park_data['name'] = $parking_car['car_user_name'];
                                $json_data['service_name'] = $service_name;
                                $json_data['msg_id'] = createRandomStr(8,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(12,true,true);//是
                                $json_data['data'] = $park_data;
                                $post_data['unserialize_desc'] = serialize($json_data);
                                $post_data['park_id'] = $park_system['park_id'];
                                $post_data['token'] = $park_system['token'];
                                $post_data['service_name'] = $service_name;
                                $post_data['car_number'] = $park_data['car_number'];
                                $post_data['village_id'] = $pay_order['village_id'];
                                $post_data['msg_id'] = $json_data['msg_id'];
                                $post_data['stored_card'] = $park_data['card_id'];
                                $post_data['order_id'] = $order_id;
                                $post_data['car_id'] = isset($pay_order['car_id'])?intval($pay_order['car_id']):0;
                                $post_data['create_time'] = time();
                                M('Park_total_record')->data($post_data)->add();//储值卡续充同步
                            } else {
                                $parking_car_type_arr = D('House_village_parking_car')->parking_car_type_arr;
                                // 同步储值卡
                                if ($parking_car_type_arr && $parking_car['parking_car_type'] && $parking_car_type_arr[$parking_car['parking_car_type']]) {
                                    $car_type_id = $parking_car_type_arr[$parking_car['parking_car_type']];
                                } else if($parking_car['temporary_car_type']) {
                                    $car_type_id = $parking_car['store_value_car_type'];
                                } else {
                                    $car_type_id = '储值车A';
                                }
                                // 同步储值卡
                                $service_name = 'prepay_card_sync';
                                $park_data['operate_type'] = 1;
                                $park_data['create_time'] = time();
                                $park_data['total_price'] = $pay_order['money'];
                                $park_data['car_number'] = $parking_car['province'].$parking_car['car_number'];
                                $park_data['mobile'] = $parking_car['car_user_phone'];
                                $park_data['name'] = $parking_car['car_user_name'];
                                $park_data['park_id'] = $park_system['park_id'];
                                $park_data['remark'] = '储值卡数据下发';
                                $park_data['card_id'] = '3_'.$park_system['park_id'].$parking_car['car_id'];//储值卡编号（收费系统唯一编号）
                                $park_data['car_type_id'] = $car_type_id;
                                $park_data['address'] = $address;
                                $json_data['service_name'] = $service_name;
                                $json_data['msg_id'] = createRandomStr(8,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(12,true,true);//是
                                $json_data['data'] = $park_data;
                                $post_data['unserialize_desc'] = serialize($json_data);
                                $post_data['park_id'] = $park_system['park_id'];
                                $post_data['car_number'] = $park_data['car_number'];
                                $post_data['token'] = $park_system['token'];
                                $post_data['service_name'] = $service_name;
                                $post_data['village_id'] = $pay_order['village_id'];
                                $post_data['msg_id'] = $json_data['msg_id'];
                                $post_data['stored_card'] = $park_data['card_id'];
                                $post_data['car_id'] = isset($pay_order['car_id'])?intval($pay_order['car_id']):0;
                                $post_data['create_time'] = time();
                                $id = M('Park_total_record')->data($post_data)->add();//储值卡同步

                                $data_set = [
                                    'stored_card' => $park_data['card_id']
                                ];
                                if ($id) {
                                    D('House_village_parking_car')->where(array('car_id'=>$parking_car['car_id']))->data($data_set)->save();
                                }
                            }
                        }
                        elseif($pay_order['car_type'] == 'temporary_type'){ //临时
                            if ($park_config['park_sys_type'] && 'D1'==$park_config['park_sys_type']) {
                                $park_sys_type = 'D1';
                            }elseif ($park_config['park_sys_type'] && 'D3'==$park_config['park_sys_type']) {
                                $park_sys_type = 'D3';
                            } else {
                                $park_sys_type = 'A1';
                            }
                            fdump_api(['临时车park-'.__LINE__,$park_sys_type],'after_pay/house_village_pay_order',true);
                            if ($park_sys_type=='D1') {
                                // d1上报缴费结果
                                $where_temp = [
                                    'car_number' => $pay_order['car_number'],
                                    'order_id' => $pay_order['third_id'],
                                    'is_paid' => 0,
                                ];
                                $house_village_parking_temp = M('House_village_parking_temp')->where($where_temp)->order('id desc')->find();
                                $car_number = $pay_order['car_number'] ? $pay_order['car_number'] : $house_village_parking_temp['car_number'];
                                $param = [];
                                $param['car_number'] = $car_number;
                                $param['money'] = $pay_order['money'];
                                $param['pay_time'] = $pay_order['pay_time'] ? $pay_order['pay_time'] : time();
                                $param['order_id'] = $pay_order['third_id'];
                                $toPark = D('ParkService')->payNoticeToPark($pay_order['village_id'], $param);
                            }
                            elseif ($park_config['park_sys_type'] && 'D3'==$park_config['park_sys_type']){
                                $house_village_parking_temp = M('House_village_parking_temp')->where(array('car_number'=>$pay_order['car_number']))->order('id desc')->find();
                                fdump_api(['停车支付回调'.__LINE__,$house_village_parking_temp],'after_pay/park_after_pay',1);
                                if($house_village_parking_temp){
                                    $car_number = $pay_order['car_number'] ? $pay_order['car_number'] : $house_village_parking_temp['car_number'];
                                    fdump_api(['停车支付回调'.__LINE__,$pay_order,$car_number,is_null($house_village_parking_temp['is_pay_scene'])],'after_pay/park_after_pay',1);
                                    $out_channel_id = $house_village_parking_temp['out_channel_id'];
                                    if(!$out_channel_id){
                                        $out_channel_id = '1';
                                    }
                                    if($house_village_parking_temp['is_pay_scene'] == 1 || is_null($house_village_parking_temp['is_pay_scene'])){//直付
                                        $where_log = [];
                                        $where_log['car_number']=$car_number;
                                        $where_log['park_type']=2;
                                        $time_condition = " add_time >= " . (time() - 300) . ' AND  add_time <=' . (time() + 150) ;
                                        $where_log['_string']=$time_condition;
                                        $log_info = D('Park_plateresult_log')->where($where_log)->find();
                                        fdump_api([$log_info,$where_log,D('Park_plateresult_log')->_sql()],'park_after_pay_0507',1);
                                        if (!empty($log_info)) {
                                            $park_log_data = [];
                                            $park_log_data['car_number'] = $log_info['car_number'];
                                            $park_log_data['channel_id'] = $log_info['channel_id'];
                                            $park_log_data['park_type'] = $log_info['park_type'];
                                            $park_log_data['add_time'] = time();
                                            $log_id= D('Park_open_log')->add($park_log_data);
                                            fdump_api([$log_id,$park_log_data],'park_after_pay_0507',1);
                                        }else{
                                            $where_log = [];
                                            $where_log['channel_id']=$out_channel_id;
                                            $where_log['park_type']=2;
                                            $log_info = D('Park_plateresult_log')->where($where_log)->order('id DESC')->find();
                                            fdump_api([$log_info,$car_number],'park_after_pay_0507',1);
                                            if (!empty($log_info)&&$log_info['car_number']==$car_number){
                                                $park_log_data = [];
                                                $park_log_data['car_number'] = $log_info['car_number'];
                                                $park_log_data['channel_id'] = $log_info['channel_id'];
                                                $park_log_data['park_type'] = $log_info['park_type'];
                                                $park_log_data['add_time'] = time();
                                                $log_id= D('Park_open_log')->add($park_log_data);
                                                fdump_api([$log_id,$park_log_data],'park_after_pay_0507',1);
                                            }
                                        }

                                        $nowtime = date("mdHis");
                                        $orderid = $nowtime . sprintf("%04d", $pay_order['order_id']);
                                        M('House_village_parking_temp')->where(array('id'=>$house_village_parking_temp['id']))->data(['is_paid'=>1,'pay_time'=> time()])->save();
                                    }
                                    elseif($house_village_parking_temp['is_pay_scene'] == 2){//无牌车直付
                                        M('House_village_parking_temp')->where(array('id'=>$house_village_parking_temp['id']))->data(['is_paid'=>1,'pay_time'=> time()])->save();
                                    }elseif($house_village_parking_temp['is_pay_scene'] == 0){//预付
                                        $where_log = [];
                                        $where_log['car_number']=$car_number;
                                        $where_log['park_type']=2;
                                        $log_info11 = D('Park_plateresult_log')->where($where_log)->order('id DESC')->find();
                                      fdump_api([$log_info11,$car_number],'park_after_pay_0507',1);
                                       if (!empty($log_info11)){
                                           $where_log = [];
                                           $where_log['channel_id']=$log_info11['channel_id'];
                                           $where_log['park_type']=2;
                                           $log_info = D('Park_plateresult_log')->where($where_log)->order('id DESC')->find();
                                           fdump_api([$log_info,$log_info11['channel_id']],'park_after_pay_0507',1);
                                           if (!empty($log_info)&&$log_info['car_number']==$car_number){
                                               $park_log_data = [];
                                               $park_log_data['car_number'] = $log_info['car_number'];
                                               $park_log_data['channel_id'] = $log_info['channel_id'];
                                               $park_log_data['park_type'] = $log_info['park_type'];
                                               $park_log_data['add_time'] = time();
                                               $log_id=D('Park_open_log')->add($park_log_data);
                                               fdump_api([$log_id,$park_log_data],'park_after_pay_0507',1);
                                           }
                                       }
                                        $nowtime = date("mdHis");
                                        $orderid = $nowtime . sprintf("%04d", $pay_order['order_id']);

                                        M('House_village_parking_temp')->where(array('id'=>$house_village_parking_temp['id']))->data(['is_paid'=>1,'pay_time'=> time()])->save();
                                    }
                                }
                            }
                            elseif ($park_config['park_sys_type'] && 'D6'==$park_config['park_sys_type']){
                                $house_village_parking_temp = M('House_village_parking_temp')->where(array(
                                    'car_number'=>$pay_order['car_number'],
                                    'service_name'=>'d6_charging',
                                    'village_id'=>$now_order['village_id'],
                                    'state'=>1
                                ))->order('id desc')->find();
                                $paid_type=trim($plat_order_info['paid_type']);
                                if(in_array($paid_type,['wechat','weixin'])){
                                    $pay_typess=1;
                                }elseif (in_array($paid_type,['alipay'])){
                                    $pay_typess=2;
                                }else{
                                    $pay_typess=5;
                                }
                                //todo D6智慧停车 支付成功
                                invoke_v20_service('\app\community\model\service\Park\D6Service/d6ChargingResult',[
                                    'temp_param'=>$house_village_parking_temp,
                                    'pay_price'=>$pay_order['money'],
                                    'pay_type'=>$pay_typess,
                                    'pay_time'=>time()
                                ]);
                            }
                            else {
                                //临时车处理
                                $park_system = M('Park_system')->where(array('park_id'=>$pay_order['village_id']))->find();
                                if($park_system){
                                    $house_village_parking_temp = M('House_village_parking_temp')->where(array('car_number'=>$pay_order['car_number'],'token'=>$park_system['token']))->order('id desc')->find();
                                    if($house_village_parking_temp){

                                        $car_number = $pay_order['car_number'] ? $pay_order['car_number'] : $house_village_parking_temp['car_number'];
                                        $out_channel_id = $house_village_parking_temp['out_channel_id'];
                                        if(!$out_channel_id){
                                            $out_channel_id = '1';
                                        }

                                        if($house_village_parking_temp['is_pay_scene'] == 1){//直付
                                            $nowtime = date("mdHis");
                                            $orderid = $nowtime . sprintf("%04d", $pay_order['order_id']);
                                            $json_data['state'] = 1;
                                            $json_data['order_id'] = $house_village_parking_temp['order_id'];
                                            $json_data['service_name'] = 'outpark';
                                            $json_data['trade_no'] = $orderid;
                                            $json_data['pay_type'] = 'scancode';
                                            $json_data['out_channel_id'] = $out_channel_id;//通道号暂时写1
                                            $json_data['total'] = floatval($house_village_parking_temp['price']);
                                            $json_data['errmsg'] = '支付成功';
                                            $json_data['pay_time'] = $pay_order['pay_time'] ? $pay_order['pay_time'] : time();//
                                            $json_data['car_number'] = $car_number;
                                            $json_data['arrive_money'] = $house_village_parking_temp['price'];
                                            $json_data['fee'] = 0.00;
                                            $json_data['account_type'] = 1;
                                            $json_data['remark'] = '收到停车费-'.$json_data['car_number'];

                                            $post_data['unserialize_desc'] = serialize($json_data);
                                            $post_data['park_id'] = $park_system['park_id'];
                                            $post_data['token'] = $park_system['token'];
                                            $post_data['service_name'] = 'outpark';
                                            $post_data['order_id'] = $house_village_parking_temp['order_id'] ? $house_village_parking_temp['order_id'] : '';
                                            $post_data['create_time'] = time();
                                            $post_data['car_number'] = $car_number;
                                            $post_data['village_id'] = $pay_order['village_id'];
                                            $res = M('Park_total_record')->data($post_data)->add();
                                        }elseif($house_village_parking_temp['is_pay_scene'] == 2){//无牌车直付
                                            $nowtime = date("mdHis");
                                            $orderid = $nowtime . sprintf("%04d", $pay_order['order_id']);
                                            $json_data['state'] = 1;
                                            $json_data['order_id'] = $house_village_parking_temp['order_id'];
                                            $json_data['service_name'] = 'outpark';
                                            $json_data['trade_no'] = $orderid;
                                            $json_data['pay_type'] = 'scancode';
                                            $json_data['out_channel_id'] = $out_channel_id;//通道号暂时写1
                                            $json_data['total'] = floatval($house_village_parking_temp['price']);
                                            $json_data['errmsg'] = '支付成功';
                                            $json_data['pay_time'] = $pay_order['pay_time'] ? $pay_order['pay_time'] : time();//
                                            $json_data['car_number'] = $car_number;
                                            $json_data['arrive_money'] = $house_village_parking_temp['price'];
                                            $json_data['fee'] = 0.00;
                                            $json_data['account_type'] = 1;
                                            $json_data['remark'] = '收到无牌车停车费-'.$json_data['car_number'];

                                            $post_data['unserialize_desc'] = serialize($json_data);
                                            $post_data['park_id'] = $park_system['park_id'];
                                            $post_data['token'] = $park_system['token'];
                                            $post_data['service_name'] = 'outpark';
                                            $post_data['order_id'] = $house_village_parking_temp['order_id'] ? $house_village_parking_temp['order_id'] : '';
                                            $post_data['create_time'] = time();
                                            $post_data['car_number'] = $car_number;
                                            $post_data['village_id'] = $pay_order['village_id'];
                                            $res = M('Park_total_record')->data($post_data)->add();

                                        }elseif($house_village_parking_temp['is_pay_scene'] == 0){//预付
                                            $nowtime = date("mdHis");
                                            $orderid = $nowtime . sprintf("%04d", $pay_order['order_id']);

                                            $json_data['car_number'] = $car_number;
                                            $json_data['service_name'] = 'prepay_order';
                                            $json_data['prepay'] = '';//金额 float
                                            $json_data['prepay_type'] = '';
                                            $json_data['pay_channel'] = '0微信';
                                            $json_data['order_id'] = $house_village_parking_temp['order_id'];//车场订单id //跟入场一起
                                            $json_data['query_order_no'] = '';//查询价格编号，查询时收费系统返回的查询价格编号 否
                                            $json_data['park_id'] = $park_system['park_id'];//
                                            $json_data['trade_no'] = $orderid;//
                                            $json_data['query_time'] = $house_village_parking_temp['add_time'];//
                                            $json_data['pay_time'] = $pay_order['pay_time'] ? $pay_order['pay_time'] : time();//
                                            $json_data['arrive_money'] = $house_village_parking_temp['price'];//到账金额
                                            $json_data['fee'] = 0.00;//手续费
                                            $json_data['account_type'] = 1;//
                                            $json_data['remark'] = '收到停车费-'.$json_data['car_number'];

                                            $post_data['unserialize_desc'] = serialize($json_data);
                                            $post_data['park_id'] = $park_system['park_id'];
                                            $post_data['token'] = $park_system['token'];
                                            $post_data['service_name'] = 'prepay_order';
                                            $post_data['order_id'] = $house_village_parking_temp['order_id'] ? $house_village_parking_temp['order_id'] : '';
                                            $post_data['create_time'] = time();
                                            $post_data['car_number'] = $car_number;
                                            $post_data['village_id'] = $pay_order['village_id'];
                                            $res = M('Park_total_record')->data($post_data)->add();

                                        }
                                    }

                                }
                            }
                        }
                    }
                    break;
                case 'custom_payment':
                    $bind_field = '';
                    $tmp_order['desc']= $plat_order_info['order_name'];
                    // 计算缴费时间
                    $bind_payment = M('House_village_payment_standard_bind')->where(array('bind_id'=>$now_order['payment_bind_id']))->find();
                    $payment_standard = M('House_village_payment_standard')->where(array('standard_id'=>$bind_payment['standard_id']))->find();

                    M('House_village_payment_standard_unpaid')->where(array('village_id'=>$now_order['village_id'],'bind_id'=>$now_order['bind_id'],'payment_bind_id'=>$now_order['payment_bind_id'],'uid'=>$now_order['uid']))->save(array('order_id'=>$order_id));
                    $paylist_data = array();
                    $paylist_data['bind_id'] = $now_order['bind_id'];
                    $paylist_data['uid'] = $now_order['uid'];
                    $paylist_data['village_id'] = $now_order['village_id'];
                    $paylist_data['paid_cycle'] = $now_order['payment_paid_cycle'];

                    switch ($payment_standard['cycle_type']) {
                        case 'Y': // 年
                            // 计算到期时间 = 开始时间 + 已缴时间（收费周期*已缴周期）
                            $start_time = strtotime("+" .($payment_standard['pay_cycle'] * $bind_payment['paid_cycle']  ). " years", strtotime(date('Y-m-d 00:00:00',$bind_payment['start_time'])));
                            $end_time = strtotime("+" .($payment_standard['pay_cycle'] * $paylist_data['paid_cycle'] ). " years", strtotime(date('Y-m-d 00:00:00',$start_time)))-1;
                            break;
                        case 'M': //月
                            $start_time = strtotime("+" .($payment_standard['pay_cycle'] * $bind_payment['paid_cycle']  ). " months",strtotime(date('Y-m-d 00:00:00',$bind_payment['start_time'])));
                            $end_time = strtotime("+" .($payment_standard['pay_cycle'] * $paylist_data['paid_cycle'] ). " months", strtotime(date('Y-m-d 00:00:00',$start_time)))-1;
                            break;
                        case 'D': // 日
                            $start_time = strtotime("+" .($payment_standard['pay_cycle'] * $bind_payment['paid_cycle']  ). " days",strtotime(date('Y-m-d 00:00:00',$bind_payment['start_time'])));
                            $end_time = strtotime("+" .($payment_standard['pay_cycle'] * $paylist_data['paid_cycle'] ). " days", strtotime(date('Y-m-d 00:00:00',$start_time)))-1;
                            break;
                    }
                    $paylist_data['start_time'] = $start_time;
                    $paylist_data['end_time'] = $end_time;
                    $paylist_data['add_time'] = time();
                    $paylist_data['order_id'] = $now_order['order_id'];
                    D('House_village_payment_paylist')->data($paylist_data)->add();

                    //修改绑定缴费项目已缴周期
                    M('House_village_payment_standard_bind')->where(array('bind_id'=>$now_order['payment_bind_id']))->setInc('paid_cycle',$now_order['payment_paid_cycle']);
                default:
                    $bind_field = '';
                    $tmp_order['desc']= $plat_order_info['order_name'];
            }

            $this->where(array('order_id'=>$order_id))->save($date_order);
            if(!empty($bind_field)){
//                $now_user_info = D('House_village_user_bind')->get_one($now_order['village_id'],$now_order['bind_id'],'pigcms_id');
                $data_bind['pigcms_id'] = $now_user_info['pigcms_id'];
                if($now_user_info[$bind_field] - $now_order['money'] >= 0){
                    $data_bind[$bind_field] = $now_user_info[$bind_field] - $now_order['money'];
                }else{
                    $data_bind[$bind_field] = 0;
                }
                $data_bind[$bind_field] = $now_user_info[$bind_field] - $now_order['money'] >= 0 ? $now_user_info[$bind_field] - $now_order['money'] : 0;
                D('House_village_user_bind')->data($data_bind)->save();
            }
            $tmp_order['order_type'] = 'village_pay';
            if ($now_order['order_type']=='pile'){
                $tmp_order['order_type'] = 'village_pile_pay';
            }
            if($plat_order_info['is_own']==0 || $plat_order_info['is_own']==1)
                $plat_order_info['is_own']+=4;
            //社区对账
            if(isset($plat_order_info['is_chinaums_divide']) && $plat_order_info['is_chinaums_divide']==1){
                $tmp_order['is_chinaums_divide']=1;
                $tmp_order['chinaums_divide_money']=$plat_order_info['chinaums_divide_money'] ? $plat_order_info['chinaums_divide_money']:0;
                $tmp_order['chinaums_system_take']=$plat_order_info['chinaums_system_take'] ? $plat_order_info['chinaums_system_take']:0;
            }
            //社区分账
            if(isset($plat_order_info['is_pay_need_divide']) && $plat_order_info['is_pay_need_divide']==1){
                $tmp_order['is_pay_need_divide']=1;
                $tmp_order['pay_divide_type']=isset($plat_order_info['pay_divide_type']) ? $plat_order_info['pay_divide_type']:"" ;
                $tmp_order['divide_village_money']=isset($plat_order_info['divide_village_money']) ? $plat_order_info['divide_village_money']:0;
                $tmp_order['divide_system_take']=isset($plat_order_info['divide_system_take']) ? $plat_order_info['divide_system_take']:0;
                $tmp_order['divide_desc']=isset($plat_order_info['divide_desc']) ? $plat_order_info['divide_desc']:'';
            }
            $res = D('SystemBill')->bill_method($plat_order_info['is_own'],$tmp_order,$is_fenzhang);

            if(!$res['error_code']){
                $this->where(array('order_id'=>$order_id))->setField('is_pay_bill',2);
            }
            $now_user = D('User')->get_user($now_order['uid']);
            $village_info = D('House_village')->where(array('village_id'=>$now_order['village_id']))->find();
            if(!empty($now_user['openid'])){
                if ($order_info['order_type']=='pile'){
                    $href=get_base_url('pages/village/smartCharge/historyOrderDetails');
                }else{
                    $href = C('config.site_url').'/wap.php?g=Wap&c=House&a=village_my_paylists&village_id='.$now_order['village_id'];
                }
                $model = new templateNews(C('config.wechat_appid'),C('config.wechat_appsecret'));
                $model->sendTempMsg('TM01008', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' =>  ' 缴费成功提醒', 'keynote1' =>$village_info['village_name'], 'keynote2' =>'物业号 '. $now_user_info['usernum'], 'remark' => '缴费时间：'.date('Y年n月j日 H:i',$now_order['time']).'\n'.'缴费金额：'.C('config.Currency_symbol').$now_order['money']),0,$village_info['property_id'],2);
            }
            $village_bind = D('House_village_user_bind')->where(array('pigcms_id'=>$now_order['bind_id']))->find();
            if ($village_bind['single_id'] && $village_bind['floor_id'] && $village_bind['layer_id'] && $village_bind['vacancy_id'] && $village_bind['village_id']) {
                $address = D('House_village')->get_single_floor_room($village_bind['single_id'],$village_bind['floor_id'],$village_bind['layer_id'],$village_bind['vacancy_id'],$village_bind['village_id']);
                if ($address) {
                    $village_bind['address'] = $address;
                }
            }
            if($village_info['openid']){
                $href = "";
                $model = new templateNews(C('config.wechat_appid'),C('config.wechat_appsecret'));
                $model->sendTempMsg('TM01008', array('href' => $href, 'wecha_id' => $village_info['openid'], 'first' =>  ' 业主 '.$village_bind['name'].'( '.$village_bind['address'].' )缴费成功提醒', 'keynote1' =>$village_info['village_name'], 'keynote2' =>$village_bind['name']."( ".$village_bind['phone']." )", 'remark' => '缴费周期： '.($now_order['property_month_num'] ? $now_order['property_month_num'] : ($now_order['payment_paid_cycle'] ? $now_order['payment_paid_cycle'] : 0)).' 个月\n缴费时间：'.date('Y年n月j日 H:i',$now_order['time']).'\n'.'缴费金额：'.C('config.Currency_symbol').$now_order['money']),0,$village_info['property_id'],2);
            }
            //判断是否自动开具电子发票
            if($auto){
                $order_no = date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
                $name = !empty($village_bind['name'])?$village_bind['name']:$now_order['order_name'];
                $res = $this->addInvoice($name,$user['phone'],$order_no,$e_config['duty_paragraph'],$now_order['order_name'],$now_order['money'],$rate,$village_bind['property_id']);
                fdump($res,1,true);
                if($res['status'] == '0000'){
                    $this->addERecord($e_config['duty_paragraph'],$name,$now_order['money'],$user['phone'],$res['fpqqlsh'],$order_no,$now_order['order_name'],$order_id,$now_order['bind_id']);
                }
            }
        }

    }

	public function get_order_url($order_id,$is_mobile){
		$now_order = $this->get_one($order_id);
		M('House_village')->where(array('village_id'=>$now_order['village_id']))->setInc('payment_reminder');
		
		// 小票打印start
        $printHaddle = new PrintVillage();
        $printHaddle->printit($order_id);
        // 小票打印end
        $base_url = D('House_village')->base_url;
        $site_url = C('config.site_url');
        $db_house_village = D('House_village');
        $app_version = intval($_POST['app_version']);
        $deviceId = trim($_POST['Device-Id']);
        if($now_order['order_type'] == 'park'){
           if($now_order['car_type'] == 'temporary_type'){
			   $park_system = M('Park_system')->where(array('park_id'=>$now_order['village_id']))->find();

			   $parking_temp = M('House_village_parking_temp')->where(array('status'=>0,'token'=>$park_system['token']))->order('id desc')->find();

			   return $site_url . $base_url . 'pages/village/smartPark/paymentSuccessful?village_id='.$now_order['village_id'].'&free_out_time='.$parking_temp['free_out_time'];
		   }else{
               $param = [
                   'pageName' => 'payMentlist',
                   'isAll' => true,
                   'urlParam' => 'village_id='.$now_order['village_id'].'&no_head=true&current=1',
               ];
               $payMentlistUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
               if ($now_order['bind_id']>0) {
                   $payMentlistUrl .= '&pigcms_id='.$now_order['bind_id'];
               }
			   return $payMentlistUrl;
		   }
		}else{
            $param = [
                'pageName' => 'payMentlist',
                'isAll' => true,
                'urlParam' => 'village_id='.$now_order['village_id'].'&no_head=true&current=1',
            ];
            $payMentlistUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
            if ($now_order['bind_id']>0) {
                $payMentlistUrl .= '&pigcms_id='.$now_order['bind_id'];
            }
            return $payMentlistUrl;
		}

	}

	public function get_user_village_info($bind_id){
		$database_house_village_user_bind = D('House_village_user_bind');
		$now_user_info = $database_house_village_user_bind->get_one_by_bindId($bind_id);
		if(empty($now_user_info)){
			$this->check_ajax_error_tips('您不是该'.C('config.house_name').'业主',U('bind_village',array('village_id'=>$_GET['village_id'])));
		}

		$where['parent_id|pigcms_id'] = $bind_id;
		$where['uid'] = $this->user_session['uid'];
		$where['village_id'] = $_GET['village_id'] + 0;
		$house_village_user_bind_count = $database_house_village_user_bind->where($where)->count();
		if(!$house_village_user_bind_count){
			redirect(U('bind_village',array('village_id'=>$_GET['village_id'])));
		}

		$this->assign('now_user_info',$now_user_info);
		return $now_user_info;
	}

	/**
	 * [get_pay_order_sum 获取总和]
	 * @return [type] [description]
	 */
	public function get_pay_order_sum($field,$where=array()){
        $pay_count = D('House_village_pay_order')->where($where)->sum($field);
        $pay_count = $pay_count ? $pay_count : 0;
        return $pay_count;
	}

    #计算可使用积分，获得积分
	public function get_score($order_info,$now_user,$user_score=true){
        // $order_info = $this->get_one($order_id);
        // 其他缴费也可获得积分
        $percent = 0;            //计算物业费和抵扣金额
        //判断是否允许使用积分
        $village_config = D('House_village_config')->where(array('village_id'=>$order_info['village_id']))->find();
        //首先得到 积分比例 和 用户总积分
        $system_config = D('Config')->get_config();
        //得到多少积分抵扣一元
        $moneytointegral = $system_config['user_score_use_percent'];
        //得到用户总积分
        $user_score_count = $now_user['score_count'];
        //得到总金额
        $total_price = $order_info['money'];

        if ($order_info['order_type']=='property') { // 物业费
        	if($village_config['village_pay_use_integral']==1){ //允许使用积分 针对小区开启
				$percent = $village_config['use_max_integral_percentage'];
				if($village_config['village_pay_owe_use_integral']==1){ //欠缴物业费允许使用积分
					$property_info = $this->get_use_score($total_price ,$village_config['use_max_integral_percentage'] , $village_config['use_max_integral_num'] , $moneytointegral , $user_score_count);
				}else{ // 欠缴物业费不允许使用积分情况
					//获得欠缴月份数
					$owe_mouth = D('House_village_user_bind')->owe_property_mouth($order_info['bind_id']);
					// 计算可使用积分
					if ($owe_mouth) { // 欠费 去掉欠费部分
						if($owe_mouth >= $order_info['property_month_num']){
							$property_info['use_total_score_count'] = 0;
							$property_info['use_total_money'] = 0;		
						}else{
							$now_total_integral_money = $total_price - $order_info['property_fee'] * $order_info['house_size'] * $owe_mouth;
							$property_info = $this->get_use_score($now_total_integral_money ,$village_config['use_max_integral_percentage'] , $village_config['use_max_integral_num'] , $moneytointegral , $user_score_count);
						}
					}else{ // 不欠费
						$property_info = $this->get_use_score($total_price ,$village_config['use_max_integral_percentage'] , $village_config['use_max_integral_num'] , $moneytointegral , $user_score_count);
					}
				}
			}elseif($village_config['village_pay_use_integral']==0){ // 继承系统设置
				if($system_config['village_pay_use_integral']==1){
					$percent = $system_config['use_max_integral_percentage'];
					if($system_config['village_owe_pay_use_integral']==1){ //欠缴物业费允许使用积分
						$property_info = $this->get_use_score($total_price ,$system_config['use_max_integral_percentage'] , $system_config['use_max_integral_num'] , $moneytointegral , $user_score_count);
					}else{ // 欠缴物业费不允许使用积分情况
						//获得欠缴月份数
						$owe_mouth = D('House_village_user_bind')->owe_property_mouth($order_info['bind_id']);
						// 计算可使用积分
						if ($owe_mouth) { // 欠费 去掉欠费部分
							if($owe_mouth >= $order_info['property_month_num']){
								$property_info['use_total_score_count'] = 0;
								$property_info['use_total_money'] = 0;		
							}else{
								$now_total_integral_money = $total_price - $order_info['property_fee'] * $order_info['house_size'] * $owe_mouth;
								$property_info = $this->get_use_score($now_total_integral_money ,$system_config['use_max_integral_percentage'] , $system_config['use_max_integral_num'] , $moneytointegral , $user_score_count);
							}
						}else{ // 不欠费
							$property_info = $this->get_use_score($total_price ,$village_config['use_max_integral_percentage'] , $village_config['use_max_integral_num'] , $moneytointegral , $user_score_count);
						}
					}
				}else{
					$property_info['use_total_score_count'] = 0;
					$property_info['use_total_money'] = 0;	
				}
			}else{
				$property_info['use_total_score_count'] = 0;
				$property_info['use_total_money'] = 0;
			}
		
			//计算可生成积分多少
			if($village_config['village_pay_integral']==1){ //社区 允许生成积分
				// 积分比例
				if($village_config['open_score_get_percent']==1){
	                // 开启了积分百分比， 取当前小区的积分百分比计算
	                $score_get = $village_config['score_get_percent']/100;
	            }else{
	                // 关闭了积分百分比， 取当前小区的积分计算
	                $score_get = $village_config['user_score_get'];
	            }

	            // 计算获得积分
				if($village_config['village_owe_pay_integral']==1){ //欠缴物业费 允许生成积分
					if ($user_score) { // 使用了积分
						$property_info['generate_integral'] = ($total_price - $property_info['use_total_money']) * $score_get; // 得到生成积分
					}else{
						$property_info['generate_integral'] = $total_price * $score_get; // 得到生成积分
					}
				}else{ //欠缴物业费不允许生成积分
					//计算欠缴月份数
					$owe_mouth = D('House_village_user_bind')->owe_property_mouth($order_info['bind_id']);
					// 计算可获得积分
					if ($owe_mouth) { // 欠费 去掉欠费部分
						if($owe_mouth < $order_info['property_month_num']){
							$now_total_integral_money = $total_price - $order_info['property_fee'] * $order_info['house_size'] * $owe_mouth;
							if ($user_score) { // 使用了积分
								$property_info['generate_integral'] = ($now_total_integral_money - $property_info['use_total_money']) * $score_get; // 得到生成积分
							}else{
								$property_info['generate_integral'] = $now_total_integral_money * $score_get; // 得到生成积分
							}
						}else{
							$property_info['generate_integral'] = 0; // 得到生成积分
						}
					}else{ // 不欠费
						if ($user_score) { // 使用了积分
							$property_info['generate_integral'] = ($total_price - $property_info['use_total_money']) * $score_get; // 得到生成积分
						}else{
							$property_info['generate_integral'] = $total_price * $score_get; // 得到生成积分
						}
					}
				}
				
			}elseif($village_config['village_pay_integral']==0){ //继承平台设置 是否允许生成积分
				if($system_config['village_pay_integral']==1){ // 平台允许生成积分
					// 积分比例
	                if($system_config['open_score_get_percent']==1){
	                    // 开启了积分百分比， 取平台小区的积分百分比计算
	                    $score_get = $system_config['score_get_percent']/100;
	                }else{
	                    // 关闭了积分百分比， 取平台小区的积分计算
	                    $score_get = $system_config['user_score_get'];
	                }

	           		// 计算积分
					if($system_config['village_owe_pay_integral']==1){ //欠缴物业费允许使用积分
						if ($user_score) { // 使用了积分
							$property_info['generate_integral'] = ($total_price - $property_info['use_total_money']) * $score_get; // 得到生成积分
						}else{
							$property_info['generate_integral'] = $total_price * $score_get; // 得到生成积分
						}
					}else{ // 欠缴物业费不允许使用积分情况
						//计算欠缴月份数
						$owe_mouth = D('House_village_user_bind')->owe_property_mouth($order_info['bind_id']);
						// 计算可获得积分
						if ($owe_mouth) { // 欠费 去掉欠费部分
							if($owe_mouth < $order_info['property_month_num']){
								$now_total_integral_money = $total_price - $order_info['property_fee'] * $order_info['house_size'] * $owe_mouth;
								if ($user_score) { // 使用了积分
									$property_info['generate_integral'] = ($now_total_integral_money - $property_info['use_total_money']) * $score_get; // 得到生成积分
								}else{
									$property_info['generate_integral'] = $now_total_integral_money * $score_get; // 得到生成积分
								}
							}else{
								$property_info['generate_integral'] = 0; // 得到生成积分
							}
						}else{ // 不欠费
							if ($user_score) { // 使用了积分
								$property_info['generate_integral'] = ($total_price - $property_info['use_total_money']) * $score_get; // 得到生成积分
							}else{
								$property_info['generate_integral'] = $total_price * $score_get; // 得到生成积分
							}
						}
					}
				}else{
					$property_info['generate_integral'] = 0; // 得到生成积分
				}
			}elseif($village_config['village_pay_integral']==-1){ //不允许生成积分 
				$property_info['generate_integral'] = 0;	
			}
        }else{
        	//第一步 判断是否允许使用积分 (注意 这里是针对设置了小区本身的缴费配置)
	        if($village_config['village_pay_use_integral']==1){ //允许使用积分 针对小区开启
	            $percent = $village_config['use_max_integral_percentage'];
	            $property_info = $this->get_use_score($total_price ,$village_config['use_max_integral_percentage'] , $village_config['use_max_integral_num'] , $moneytointegral , $user_score_count);
	        }elseif($village_config['village_pay_use_integral']==0){ // 继承系统设置
	            if($system_config['village_pay_use_integral']==1){
	                $percent = $system_config['use_max_integral_percentage'];
	                $property_info = $this->get_use_score($total_price ,$system_config['use_max_integral_percentage'] , $system_config['use_max_integral_num'] , $moneytointegral , $user_score_count);
	             }else{
	                $property_info['use_total_score_count'] = 0;
	                $property_info['use_total_money'] = 0;  
	            }
	        }else{
	            $property_info['use_total_score_count'] = 0;
	            $property_info['use_total_money'] = 0;
	        }
	        
	        //计算可生成积分多少
	        if($village_config['village_pay_integral']==1){ //允许生成积分
                if($village_config['open_score_get_percent']==1){
                    // 开启了积分百分比， 取当前小区的积分百分比计算
                    $score_get = $village_config['score_get_percent']/100;
                }else{
                    // 关闭了积分百分比， 取当前小区的积分计算
                    $score_get = $village_config['user_score_get'];
                }
	            if ($user_score) { // 使用了积分
	                $property_info['generate_integral'] = ($total_price - $property_info['use_total_money']) * $score_get; // 得到生成积分
	            }else{ // 未使用积分
	                $property_info['generate_integral'] = $total_price * $score_get; // 得到生成积分
	            }
	        }elseif($village_config['village_pay_integral']==0){ //继承平台设置 是否允许生成积分
	            if($system_config['village_pay_integral']==1){ // 平台允许生成积分
                    if($system_config['open_score_get_percent']==1){
                        // 开启了积分百分比， 取平台小区的积分百分比计算
                        $score_get = $system_config['score_get_percent']/100;
                    }else{
                        // 关闭了积分百分比， 取平台小区的积分计算
                        $score_get = $system_config['user_score_get'];
                    }
	                if ($user_score) { // 使用了积分
	                    $property_info['generate_integral'] = ($total_price - $property_info['use_total_money']) * $score_get; // 得到生成积分
	                }else{ // 未使用积分
	                    $property_info['generate_integral'] = $total_price * $score_get; // 得到生成积分
	                }
	            }else{
	                $property_info['generate_integral'] = 0; // 得到生成积分
	            }
	        }elseif($village_config['village_pay_integral']==-1){ //不允许生成积分 
	            $property_info['generate_integral'] = 0;    
	        }
        }
        
	    $property_info['score_percent'] = $percent;    
        return $property_info;
    }

    // 计算可使用积分
    /****************************************************************
        $total_price 总价格    
        $use_max_integral_percentage 使用最大积分占总额百分比
        $use_max_integral_num 允许使用的最大积分
        $moneytointegral 多少积分抵扣一元
        $user_score_count 用户总共多少积分
    ****************************************************************/
    public function get_use_score($total_price ,$use_max_integral_percentage , $use_max_integral_num , $moneytointegral , $user_score_count){
        if ($user_score_count<=0 || $moneytointegral<=0) {
            $property_info['use_total_score_count'] = 0;
            $property_info['use_total_money'] = 0;          
            return $property_info;
        }
        if($use_max_integral_percentage > 0){ //使用最大百分比
                        
            //得到最大抵扣金额
            $max_use_price = sprintf("%.2f",$total_price * $use_max_integral_percentage / 100);
            
			//计算可以使用多少积分
			if(C('config.score_round_two')==1){
				$use_total_score_count = getFormatNumber($max_use_price * $moneytointegral);
			}else{
				$use_total_score_count = floor($max_use_price * $moneytointegral);
			}
            
            //这里计算用户应该支出的积分  和 应该抵扣的钱  和 要支付的钱
            if($user_score_count < $use_total_score_count){
                
                $property_info['use_total_money'] =sprintf("%.2f",$user_score_count/$moneytointegral);
                $property_info['use_total_score_count'] = $property_info['use_total_money'] * $moneytointegral;
                
            }else{
                $property_info['use_total_money'] = $max_use_price;
                $property_info['use_total_score_count'] = $use_total_score_count;
            }
        }elseif($use_max_integral_num > 0){ //使用最大积分
            if($use_max_integral_num < $user_score_count){
                $property_info['use_total_money'] = sprintf("%.2f",$use_max_integral_num / $moneytointegral);
                $property_info['use_total_score_count'] = $property_info['use_total_money'] * $moneytointegral;
            }else{
                $property_info['use_total_money'] = sprintf("%.2f",$user_score_count/$moneytointegral);
                $property_info['use_total_score_count'] = $property_info['use_total_money'] * $moneytointegral;
            }

            if ($property_info['use_total_money']>$total_price) { // 可抵扣金额大于支付金额
                $property_info['use_total_money'] = $total_price;
                $property_info['use_total_score_count'] = $total_price * $moneytointegral;
            }
        }else{ // 否则不计算
            $property_info['use_total_score_count'] = 0;
            $property_info['use_total_money'] = 0; 
        }
        return $property_info;
    }

    /*得到打印详情*/
    public function get_print_detail($column){
        if(!$column['village_id']||!$column['order_id']){
            return null;
        }

        //小区信息
        $village_info = D('House_village')->where(array('village_id'=>$column['village_id']))->find();
        $print_detail['village_name'] = $village_info['village_name'];

        //收款人 物业公司
        $print_detail['payee'] = $village_info['property_name'];
		$print_detail['print_time'] = date('Y-m-d H:i:s' ,time());
        $cashier_info['pay_type_name'] = '';

        //查询详细订单
        $condition_table  = array(C('DB_PREFIX').'house_village_pay_order'=>'o',C('DB_PREFIX').'house_village_user_bind'=>'b');
        $condition_where = " `o`.`village_id` = `b`.`village_id`  AND `o`.`bind_id` = `b`.`pigcms_id`  AND `o`.`village_id` =".$column['village_id'];

        $condition_where .= " AND `o`.`order_id` in (".$column['order_id'].')';
        
        
        $condition_field = '`b`.`name` AS `username` ,b.*,o.*';
        $order = ' `o`.`order_id` DESC';
       
        $order_list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->order($order)->select();
        $totalMoney = 0;
        if ($order_list) {
            foreach ($order_list as &$value) {
                //收款时间
                $value['pay_time'] = date('Y-m-d H:i:s' ,$value['pay_time']);
                //优惠
                if ($value["presented_property_month_num"]&&$value["diy_type"] == 0) {
                    $value['discount'] = $value["presented_property_month_num"].'个月';
                } elseif($value["diy_type"] == 1) {
                    $value['discount'] = $value["diy_content"];
                }else{
                    $value['discount'] = '';
                }
                //服务时间
                if ($value["order_type"] == 'custom_payment') {
                    $value['property_time_str'] = $value["property_time_str"];
                }
                //缴费周期
                $value['service_cycle'] = '';
                if ($value["property_month_num"]) { //物业服务周期
                    $value['service_cycle'] = $value["property_month_num"].'个月';
                }elseif($value["order_type"] == 'custom_payment'){ //自定义缴费周期
                    $value['service_cycle'] = $value["payment_paid_cycle"].'/周期';
                }

                $value['real_money'] = $value['money'];
                $totalMoney  += $value['money'];
                //楼层信息
                $floor_where['floor_id'] = $value['floor_id'];
                $floor_where['status'] = 1;

                $database_house_village_floor = D('House_village_floor');
                $floor_info = $database_house_village_floor->house_village_floor_detail($floor_where);
                $floor_info = $floor_info['detail'];


                //房号
                //$value['room_num'] = $floor_info['floor_name']. $floor_info['floor_layer'] . $value['layer_num'].$value['room_addrss'];
                $single_name = M('House_village_single')->where(array('village_id' => $value['village_id'],'id' => $value['single_id']))->getField('single_name');
                $word_data = [];
                $word_data['layer'] = $value['layer_num'];
                $word_data['room'] = $value['room_addrss'];
                $word_data['floor_name'] = $floor_info['floor_name'];
                $word_data['single_name'] = !empty($single_name) ? $single_name : $floor_info['floor_layer'];
                $word_data['room'] = D("House_village_user_vacancy")->where(['pigcms_id' => $value['vacancy_id']])->getField('room');
                $room_diy_name = D('House_village')->word_replce_msg($word_data,$column['village_id']);
                $value['room_num'] = $room_diy_name;
                // $value['room_num'] = $value['address'];

                // 房屋面积  $user_info['vacancy_id']
                $housesize = D('house_village_user_vacancy')->where(array('pigcms_id' => $value['vacancy_id']))->field('housesize')->find();
                $print_detail['housesize'] = $housesize['housesize'].' ㎡';

                //收费标准
                switch ($value["order_type"]) {
                    case 'property':
                        //$value["price"] = $value['property_fee'];
                        if($value['vacancy_id']){
                            $date_month = M('House_village_property_standard_bind')
                                ->join('AS sb LEFT JOIN '.C('DB_PREFIX').'house_village_property_standard AS s ON s.id = sb.standard_id')
                                ->where(['sb.pigcms_id'=>$value['vacancy_id'],'is_del'=>1])
                                ->getField('s.date_month');
                            if(!empty($date_month)){
                                $date_month = unserialize($date_month);
                                $property_fee = '';
                                foreach ($date_month as $k1=>$v1){
                                    $property_fee = $v1['price'].'元/平方米/月';
                                }
                            }else{
                                $property_fee = '';
                            }
                        }else{
                            $property_fee  = $floor_info['property_fee'];
                        }
                        $value["price"] = $property_fee;
                        // 开始时间，结束时间
                        $paylist = D('House_village_property_paylist')->where(array('order_id'=>$value['order_id']))->find();
                        if ($paylist['start_time']) {
                            $value["start_time"] = date('Y-m-d' ,$paylist['start_time']);
                        } else {
                            $value["start_time"] = '';
                        }
                        if ($paylist['end_time']) {
                            $value["end_time"] = date('Y-m-d' ,$paylist['end_time']);
                        } else {
                            $value["end_time"] = '';
                        }
                        break;
                    case 'water':
                        if(($floor_info['water_fee'] == '0.00') || (!isset($floor_info['water_fee']))){
                           // $value["price"] = $village_info['water_price'].C('config.Currency_txt').'/立方米';
                           $value["price"] = $village_info['water_price'];
                        }else{
                            $value["price"] = $floor_info['water_fee'];
                        }
                        $value["number"] = round($value["money"]/$value["price"],2);
                        break;
                    case 'electric':
                        if(($floor_info['electric_fee'] == '0.00') || (!isset($floor_info['electric_fee']))){
                           // $value["price"] = $village_info['electric_price'].C('config.Currency_txt').'/千瓦时(度)';
                           $value["price"] = $village_info['electric_price'];
                        }else{
                            $value["price"] = $floor_info['electric_fee'];
                        }
                        $value["number"] = round($value["money"]/$value["price"],2);
                        break;
                    case 'gas':
                        if(($floor_info['gas_fee'] == '0.00') || (!isset($floor_info['gas_fee']))){
                           // $value["price"] = $village_info['gas_price'].C('config.Currency_txt').'/立方米';
                           $value["price"] = $village_info['gas_price'];
                        }else{
                            $value["price"] = $floor_info['gas_fee'];
                        }
                        $value["number"] = round($value["money"]/$value["price"],2);
                        break;
                    case 'park':
                        if(($floor_info['park_fee'] == '0.00') || (!isset($floor_info['park_fee']))){
                           // $value["price"] = $village_info['park_price'].C('config.Currency_txt').'/月';
                           $value["price"] = $village_info['park_price'];
                        }else{
                            $value["price"] = $floor_info['park_fee'];
                        }
                        break;  
                    case 'custom_payment': // 自定义缴费 
                        $payment_standard_bind = D('House_village_payment_standard_bind')->where(array('bind_id'=>$value['payment_bind_id']))->find();
                        $payment_standard = D('House_village_payment_standard')->where(array('standard_id'=>$payment_standard_bind['standard_id']))->find();
                        // 周期类型
                        $cycle_type = '';
                        switch ($payment_standard['cycle_type']) {
                            case 'M':
                                $cycle_type = $payment_standard['pay_cycle'].'月/周期';
                                break;
                            case 'Y':
                                $cycle_type = $payment_standard['pay_cycle'].'年/周期';
                                break;
                            case 'D':
                                $cycle_type = $payment_standard['pay_cycle'].'天/周期';
                                break;
                        }
                        $value["service_cycle"] = $value['payment_paid_cycle'].'（'.$cycle_type.'）'; //支付周期
                        $value["price"] = $payment_standard['pay_money'];
                        $value["number"] = $payment_standard_bind['metering_mode_val']; //自定义数量
                        // 开始时间，结束时间
                        $paylist = D('House_village_payment_paylist')->where(array('order_id'=>$value['order_id']))->find();
                        $value["start_time"] = $paylist['start_time'] ? date('Y-m-d' ,$paylist['start_time']) : '';
                        $value["end_time"] =  $paylist['start_time'] ? date('Y-m-d' ,$paylist['end_time']) : '';
                        break;    
                    case 'custom': // 自定义缴费 
                        $value["price"] = $value["money"];
                        $value["number"] = 1;

                        $pattern = '#【(.*?)】#i';
                        preg_match_all($pattern, $value["order_name"], $matches); 
                        $value["order_name"] = $matches[1][0] ? $matches[1][0] : $value["order_name"];
                        break;
                }

                $print_detail['username'] = $value['username'];
		        $print_detail['payer'] = $value['username'];
		        $print_detail['phone'] = $value['phone'];
		        $print_detail['usernum'] = $value['usernum'];
		        //$print_detail['room_num'] = $floor_info['floor_name'].$floor_info['floor_layer']  .$value['layer_num'].$value['room_addrss'];;
		        $print_detail['room_num'] = $value['room_num'];
		        $print_detail['time'] = $value['time'] ? date('Y-m-d H:i:s' ,$value['time']) : '';
		        $print_detail['pay_time'] = $value['pay_time'];
            }
        }
        // $total = D('')->field(' SUM(`o`.`money` ) AS totalMoney ')->table($condition_table)->where($condition_where)->find();
        $print_detail['totalMoney'] = C('config.Currency_symbol').$totalMoney.'（人民币大写：'.cny($totalMoney).'）';
        $print_detail['case'] = cny($totalMoney);
        $print_detail['order_list'] = $order_list;

        return $print_detail;
    }

    /**
     * 提交发票申请
     * @author lijie
     * @date_time 2020/07/08
     * @param $name
     * @param $phone
     * @param $order_no
     * @param $duty_paragraph
     * @param $order_name
     * @param $money
     * @param $rate
     * @param $property_id
     * @return mixed
     */
    public function addInvoice($name,$phone,$order_no,$duty_paragraph,$order_name,$money,$rate,$property_id=0)
    {
        if(!$property_id)
            return false;
        $e_config = M('House_property')->where(['id'=>$property_id])->field('identity,house_e_invoice_url,house_e_pdf_url')->find();
        $arr['identity'] = $e_config['identity'];
        $order['buyername'] = $name;
        //$order['taxnum'] = '339901999999142';
        $order['phone'] = $phone;
        $order['order_no'] = $order_no;
        $order['invoicedate'] = date('Y-m-d H:i:s',time());
        $order['clerk'] = 'name';
        $order['saletaxnum'] = $duty_paragraph;
        $order['kptype'] = 1;

        $order['detail'][0]['goodsname'] = $order_name;
        $order['detail'][0]['hsbz'] = 1;
        $order['detail'][0]['taxrate'] = $rate;
        $order['detail'][0]['spbm'] = 304080101;
        $order['detail'][0]['fphxz'] = 0;
        $order['detail'][0]['taxamt'] = round($money,2);
        $order['detail'][0]['tax'] = round($money*$rate/(1+$rate),2);
        $order['detail'][0]['taxfreeamt'] = round($order['detail'][0]['taxamt'] - $order['detail'][0]['tax'],2);

        $arr['order'] = $order;
        fdump($arr,1,true);
        $res = $this->sendInvoice($arr,$e_config['house_e_invoice_url']);
        return $res;
    }

    /**
     * 调开具电子发票接口
     * @author lijie
     * @date_time 2020/07/08
     * @param $order
     * @param $url
     * @return mixed
     */
    public function sendInvoice($order,$url)
    {
        $order = $this->encrypt($order);
        $response = httpRequest($url,'POST',['order'=>$order]);
        return json_decode($response[1],true);
    }

    /**
     * 加密
     * @param $data
     * @return string
     */
    public function encrypt($data)
    {
        $srcKey = base64_decode('LmMGStGtOpF4xNyvYt54EQ==');
        $content = json_encode($data,JSON_UNESCAPED_UNICODE);
        $srcBuf = md5($content,true) . $content;
        // $result = base64_encode(
        //     openssl_encrypt($srcBuf,'DES-CBC',$this->key,0,$this->iv)
        // );
        $result = openssl_encrypt($srcBuf,'DES-CBC',substr($srcKey, 0, 8),0, substr($srcKey,8));
        return $result;
    }

    /**
     * 添加开发票记录
     * @author lijie
     * @date_time 2020/07/08
     * @param $duty_paragraph
     * @param $name
     * @param $money
     * @param $phone
     * @param $fpqqlsh
     * @param $order_no
     * @param $order_name
     * @param $order_id
     * @param $pigcms_id
     */
    public function addERecord($duty_paragraph,$name,$money,$phone,$fpqqlsh,$order_no,$order_name,$order_id,$pigcms_id)
    {
        $post['duty_paragraph'] = $duty_paragraph;
        $post['create_time'] = time();
        $post['head_up_type'] = 1;
        $post['money'] = $money;
        $post['pigcms_id'] = $pigcms_id;
        $post['receive_tel'] = $phone;
        $post['name'] = $name;
        $post['fpqqlsh'] = $fpqqlsh;
        $post['order_no'] = $order_no;
        $post['property_type'] = '*'.$order_name;
        $id = D('House_village_e_record')->insertData($post);
        $insertAll['order_id'] = $order_id;
        $insertAll['property_type'] = $order_name;
        $insertAll['price'] = $money;
        $insertAll['e_record_id'] = $id;
        $insertAll['create_time'] = time();
        D('House_village_detail_record')->insertData($insertAll);
    }

}