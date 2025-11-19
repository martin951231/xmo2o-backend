<?php
class Gift_orderModel extends Model{
    public function save_post_form($now_gift , $uid , $order_id , $user_type=1){
        fdump("1", "lumin0336",1);
        if ($user_type==2) { //商家
            $now_merchant = D('Merchant')->get_info($uid);
            $now_user['now_money'] = $now_merchant['money'];
            $now_user['score_count'] = $now_merchant['system_score'];
            $now_user['mer_id'] = $now_merchant['mer_id'];
        }else{ // 平台用户
            $now_user = D('User')->get_user($uid);
        }
        if(empty($now_user)){
            return array('error'=>1,'msg'=>L_('未获取到您的帐号信息，请重试！'));
        }

        if($order_id){
            fdump("2", "lumin0336",1);
            $now_order = $this->get_pay_order($uid , $order_id);
            $now_order = $now_order['order_info'];

            if($now_order['exchange_type'] == 0){
                $use_score = $now_order['total_integral'];
            }elseif($now_order['exchange_type']== 1){
                $use_score = $now_order['total_integral'];
                $use_money = $now_order['order_total_money'];

                if($now_user['now_money'] < $use_money){
                    return array('error'=>1,'msg'=>L_("您的帐户余额为 X1 ，不足以兑换此奖品",array("X1" => $now_user['now_money'])),'order_id'=>$order_id,'flag'=>1,'num'=>$now_order['order_num']); 
                }

            }

            if($now_user['score_count'] < $use_score){
                return array('error'=>1,'msg'=>L_("您的帐户X1为 X2 ，不足以兑换此奖品",array("X1" => C('config.score_name'),"X2" => $now_user['score_count']))); 
            }

            $order_param = array(
                'order_id' => $now_order['order_id'],
                'pay_type' => '',
                'third_id' => '',
                'is_mobile' => 1,
            );
            return $this->after_pay($order_param);
        }else{

            $data_gift_order['gift_id'] = $_POST['gift_id'] + 0;
            $data_gift_order['num'] = $_POST['num'] + 0;
            $data_gift_order['gift_id'] = $_POST['gift_id'] + 0;
            $data_gift_order['order_name'] = $now_gift['gift_name'];
            $data_gift_order['exchange_type'] = $_POST['exchange_type'];
            $data_gift_order['order_time'] = time();
            $data_gift_order['is_source'] = $_POST['is_source'] ? $_POST['is_source'] + 0 : 0;
            $data_gift_order['pc_pic'] = $now_gift['pc_pic'];
            $data_gift_order['wap_pic'] = $now_gift['wap_pic'];
			
			$data_gift_order['delivery_fee'] =  $_POST['delivery_fee']?$_POST['delivery_fee']:'0';
			$data_gift_order['address_id']   =  $_POST['address_id']?$_POST['address_id']:'0';
            $expect_time = $_POST['oarrivalDate'].' '.$_POST['oarrivalTime'];
            $data_gift_order['expect_time'] = $expect_time?strtotime($expect_time):'0';
			
            if($data_gift_order['exchange_type'] == 0){
                $data_gift_order['payment_pure_integral'] = $now_gift['payment_pure_integral'];
                $data_gift_order['total_integral'] = $now_gift['payment_pure_integral'] * $data_gift_order['num'];
            }else if($data_gift_order['exchange_type'] == 1){
                $data_gift_order['payment_integral'] = $now_gift['payment_integral'];
                $data_gift_order['payment_money'] = $now_gift['payment_money'];
                $data_gift_order['price'] = $now_gift['payment_money'];
                $data_gift_order['total_price'] = $now_gift['payment_money'] * $data_gift_order['num'];
                $data_gift_order['total_integral'] = $now_gift['payment_integral'] * $data_gift_order['num'];
            }

            $data_gift_order['total_price'] += $data_gift_order['delivery_fee'];

            $data_gift_order['is_pick_in_store'] =  $_POST['is_pick_in_store']?intval($_POST['is_pick_in_store']):'0';
			
			if($data_gift_order['is_pick_in_store'] == 0 && $data_gift_order['address_id'] == 0 && empty($now_gift['coupon_id'])){
                return array('error'=>1,'msg'=>L_('请选择收货地址！'));
            }
			
            if($data_gift_order['is_pick_in_store']==2){
                //$data_gift_order['status'] = 1;
            }
            

            $data_gift_order['memo'] = $_POST['memo'];

            if(empty($data_gift_order['num'])){
                return array('error'=>1,'msg'=>L_('请输入正确的购买数量！'));
            }else if(!empty($now_gift['exchange_limit_num']) && ($data_gift_order['num'] > $now_gift['exchange_limit_num'])){
                return array('error'=>1,'msg'=>L_("您最多只能购买X1",array("X1" => $now_gift['exchange_limit_num']))); 
            }

            $check_result = $this->check_buy_num($uid , $now_gift , $data_gift_order['num']);
            if ($check_result['errcode']) {
                return array('error'=>1,'msg'=>$check_result['msg']);
            }

            if($data_gift_order['is_pick_in_store'] == 1 && empty($_POST['pick_id'])){
                return array('error'=>1,'msg'=>L_('请先选择门店自提地址！'));
            }

            if ($_POST['pick_id']) { // 门店自提
                $now_pick = D('Gift_pick_address')->get_one_pick($_POST['pick_id']);  
                if(empty($now_pick)){
                    return array('error'=>1,'msg'=>L_('请先添加门店自提地址！'));
                }
                $data_gift_order['pick_id'] = $now_pick['pick_id'];
                $data_gift_order['pick_code'] = date('Ymd').createRandomStr(7,true);
            }elseif($now_gift['mer_id']>0){ //商家积分商品
                $data_gift_order['receive_code'] = date('Ymd').createRandomStr(7,true);
            }elseif(!empty($_POST['delivery_type'])){
                if ($user_type==2) { //商家兑换  
                    if (!$_POST['name']) {
                        return array('error'=>1,'msg'=>L_('请填写收货人姓名！'));
                    }
                    if (!$_POST['phone']) {
                        return array('error'=>1,'msg'=>L_('请填写收货人手机号！'));
                    }
                    if (!$_POST['address']) {
                        return array('error'=>1,'msg'=>L_('请填写收货地址！'));
                    }
                    $data_gift_order['contact_name'] = $_POST['name'];
                    $data_gift_order['phone'] = $_POST['phone'];
                    $data_gift_order['adress'] = $_POST['address'];
                }else{ //用户兑换
                    $now_adress = D('User_adress')->get_one_adress($uid,$_POST['address_id']);
                    if(empty($now_adress)){
                        return array('error'=>1,'msg'=>L_('请先添加收货地址！'));
                    }
                    $data_gift_order['contact_name'] = $now_adress['name'];
                    $data_gift_order['phone'] = $now_adress['phone'];
                    $data_gift_order['zipcode'] = $now_adress['zipcode'];
                    $data_gift_order['adress'] = $now_adress['province_txt'].' '.$now_adress['city_txt'].' '.$now_adress['area_txt'].' '.$now_adress['adress'].' '.$now_adress['detail'];
                }
                
                $data_gift_order['delivery_type'] = $_POST['delivery_type'];
            }
            fdump("4", "lumin0336",1);
            // 平台优惠券
            $is_get_coupon = false;
            if ($_POST['coupon_id']&&$now_user['uid']) {
                $data_gift_order['coupon_id'] = $_POST['coupon_id'];
                $data_gift_order['status'] = 2;
                $gift_coupon['coupon_id'] = $_POST['coupon_id'];
                $gift_coupon['uid'] = $uid;
                $check_coupon = D('System_coupon')->gift_get_coupon($gift_coupon);
                if ($check_coupon['error']==1) {
                    return array('error'=>1,'msg'=>$check_coupon['msg']);
                }
                $is_get_coupon = true;
            }

            if(!empty($order_id)){

            }else{
                if ($user_type==2) {
                    $data_gift_order['mer_id'] = $uid;
                }else{
                    $data_gift_order['uid'] = $uid;
                }
            }

            if($now_user['score_count'] < $use_score){
                return array('error'=>1,'msg'=>L_("您的帐户X1为 X2 ，不足以兑换此奖品",array("X1" => C('config.score_name'),"X2" => $now_user['score_count']))); 
            }
            fdump("5", "lumin0336",1);

            if($_POST['exchange_type'] == 0){ // 只使用积分
                $use_score = $data_gift_order['payment_pure_integral'] * $data_gift_order['num'];

                // 使用积分
                if ($user_type==2) {
                    $save_result = D('Merchant')->use_score($now_user['mer_id'],$use_score, L_('参加兑换').C('config.score_name').L_('礼品 ').$now_gift['gift_name'].' * '.$data_gift_order['num'] ,$order_id,'gift');
                }else{
                    $save_result = D('User')->user_score($now_user['uid'],$use_score,L_('参加兑换').C('config.score_name').L_('礼品 ').$now_gift['gift_name'].' * '.$data_gift_order['num']);
                }
                if($save_result['error_code']){
                    exit(json_encode(array('status'=>0,'info'=>$save_result['error_code'])));
                }

                $data_gift_order['paid'] = 1;
                $data_gift_order['pay_time'] = $_SERVER['REQUEST_TIME'];
				//这里可能会产生运费需要余额支付
				if($data_gift_order['delivery_fee']>0){
					if($now_user['now_money'] < $data_gift_order['delivery_fee']){
						return array('error'=>1,'msg'=>L_("您的帐户余额为 X1 ，不足以兑换此奖品所产生的运费",array("X1" => $now_user['now_money']))); 
					}
					$save_result = D('User')->user_money($now_user['uid'],$data_gift_order['delivery_fee'],L_('支付兑换').C('config.score_name').L_('礼品 ').$now_gift['gift_name'].' * '.$data_gift_order['num'].'所产生的配送费用');
					if($save_result['error_code']){
						exit(json_encode(array('status'=>0,'info'=>$save_result['error_code'])));
					}
				}
                $order_id = $this->data($data_gift_order)->add();
				if(!$order_id){
					fdump_sql([$this->getLastSql(), $data_gift_order], 'gift_order_add_error');
				}
				
            }elseif($_POST['exchange_type']== 1){ // 积分和余额
                $use_score = $data_gift_order['payment_integral'] * $data_gift_order['num'];
                $use_money = $now_gift['payment_money'] * $data_gift_order['num']+$data_gift_order['delivery_fee'];
                if($now_user['now_money'] < $use_money){
                    if ($user_type==2) {
                        return array('error'=>1,'msg'=>L_("您的帐户余额为 X1 ，不足以兑换此奖品",array("X1" => $now_user['now_money']))); 
                    }else{

                        return array('error'=>1,'msg'=>L_("您的帐户余额为 X1 ，不足以兑换此奖品",array("X1" => $now_user['now_money'])),'order_id'=>$order_id,'flag'=>1); 
                    }
                    if(!empty($data_gift_order['is_source'])){
                        $order_id = $this->data($data_gift_order)->add();
						if(!$order_id){
							fdump_sql([$this->getLastSql(), $data_gift_order], 'gift_order_add_error');
						}
                    }
                }

                // 使用积分
                if ($user_type==2) {
                    $save_result = D('Merchant')->use_score($now_user['mer_id'],$use_score, L_('参加兑换').C('config.score_name').L_('礼品 ').$now_gift['gift_name'].' * '.$data_gift_order['num'] ,$order_id,'gift');
                }else{
                    $save_result = D('User')->user_score($now_user['uid'],$use_score,L_('参加兑换').C('config.score_name').L_('礼品 ').$now_gift['gift_name'].' * '.$data_gift_order['num']);
                }
                if($save_result['error_code']){
                    exit(json_encode(array('status'=>0,'info'=>$save_result['error_code'])));
                }

                $order_id = $this->data($data_gift_order)->add();
				if(!$order_id){
					fdump_sql([$this->getLastSql(), $data_gift_order], 'gift_order_add_error');
				}
                if ($user_type==2) {
                    $save_result = D('Merchant_money_list')->use_money($now_user['mer_id'],$use_money,'gift', L_('参加兑换').C('config.score_name').L_('礼品 ').$now_gift['gift_name'].' * '.$data_gift_order['num'] ,$order_id);
                }else{
                    $save_result = D('User')->user_money($uid,$use_money,L_('参加兑换').C('config.score_name').L_('礼品 ').$now_gift['gift_name'].' * '.$data_gift_order['num']);
                }
                if($save_result['error_code']){
                    exit(json_encode(array('status'=>0,'info'=>$save_result['error_code'])));
                }

                $data_gift_order_save['paid'] = 1;
                $data_gift_order_save['pay_time'] = $_SERVER['REQUEST_TIME'];
                $this->where(array('order_id'=>$order_id))->data($data_gift_order_save)->save();
            }

            fdump("6", "lumin0336",1);
            if(!empty($order_id)){
                fdump("7", "lumin0336",1);
                $database_gift = D('Gift');
                //下单成功减库存
                $sale_count = $data_gift_order['num'] + $now_gift['sale_count'];
                $update_gift_data = array('sale_count' => $sale_count);
                $database_gift->where(array('gift_id' => $now_gift['gift_id']))->save($update_gift_data);
                $first = C('config.score_name').L_('商城订单提醒');
                // 门店自提
                if ($data_gift_order['pick_id']) {
                    $pick_info = D('Gift_pick_address')->get_one_pick($data_gift_order['pick_id']);
                    $first =C('config.score_name').'商城订单提醒\n自提地址：'.$pick_info['area_info']['province'].$pick_info['area_info']['city'].$pick_info['area_info']['area'].$pick_info['name'].'\n提货码：'.$data_gift_order['pick_code'];
                    $data_gift_pick['pick_id'] = $data_gift_order['pick_id'];
                    $data_gift_pick['order_id'] = $order_id;
                    $data_gift_pick['type'] = 0;
                    $data_gift_pick['dateline'] = time();
                    M('Gift_pick_order')->data($data_gift_pick)->add();
                }

                // 平台优惠券 发放
                if ($gift_coupon['coupon_id']) {
                    $gift_coupon['order_id'] = $order_id;
                    D('System_coupon')->gift_get_coupon($gift_coupon,1);
                }

                // 发送模板消息
                if ($user_type==2) {
                    // $now_user = M('User')->where(array('uid' => $uid))->find();
                }else{
                    $now_user = M('User')->where(array('uid' => $uid))->find();
                     if($now_user['openid']){
                        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                        $href = C('config.site_url').'/wap.php?c=My&a=gift_order&order_id='.$order_id;
                        $model->sendTempMsg('TM00017', array('href' => $href,
                            'wecha_id' => $now_user['openid'],
                            'first' => $first,
                            'OrderSn' => $order_id,
                            'OrderStatus' =>L_("兑换成功"),
                            'remark' =>L_('点击查看详情')));
                    }
                }
                if($data_gift_order['is_pick_in_store']==2 && $data_gift_order['status'] == 1){
                    //获取平台配送设置
                    $plat_deliver = M('Gift_plat_deliver')->where(array('id'=>2))->find();
                    $result = D('Deliver_supply')->saveOrder($order_id, $plat_deliver,'','gift_order');
                }

                //系统订单汇总
                D("System_order")->add_order("gift", $data_gift_order, $order_id);

                if($is_get_coupon){
                    D('System_order')->save_order_status("gift", $order_id, 2);
                }
                

                return array('error'=>0,'msg'=>L_('订单产生成功！'),'order_id'=>$order_id);
            }else{
                return array('error'=>1,'msg'=>L_('订单产生失败！请重试'));
            }
        }

    }


    public function get_pay_order($uid,$order_id,$is_web=false,$user_type=1){
        $database_gift = D('Gift');
        $now_order = $this->get_order_by_id($uid,$order_id,$user_type);
        if(empty($now_order)){
            return array('error'=>1,'msg'=>L_('当前订单已失效或不存在！'));
        }
        if(!empty($now_order['paid'])){
            unset($_SESSION['gift_order']);
            if($is_web){
                return array('error'=>1,'msg'=>L_('您已经支付过此订单！'),'url'=>U('Index/Gift/gift_order_view',array('order_id'=>$now_order['order_id'])));
            }else{
                return array('error'=>1,'msg'=>L_('您已经支付过此订单！'),'url'=>str_replace('/source/','/',U('My/gift_order',array('order_id'=>$now_order['order_id']))));
            }
        }

        $gift_where['is_del'] = 0;
        $gift_where['status'] = 1;
        $gift_where['gift_id'] = $now_order['gift_id'];

        $now_gift = $database_gift->gift_detail($gift_where);
        $now_gift = $now_gift['detail'];

        $check_result = $this->check_buy_num($uid, $now_gift, $now_order['num']);
        if ($check_result['errcode']) {
            return array('error' => 1,'msg'=> $check_result['msg']);
        }

        if(empty($now_gift)){
            return array('error'=>1,'msg'=>L_("当前X1不存在或已过期！",array("X1" => C('config.gift_alias_name')))); 
        }

        if($is_web){
            if($now_order['exchange_type'] == 1){
                $total_money = $now_order['num'] * $now_order['payment_money'];
            }else{
                $total_money = 0;
            }

            $order_info = array(
                'order_id'			=>	$now_order['order_id'],
                'gift_id'			=>	$now_order['gift_id'],
                'order_type'		=>	'gift',
                'order_name'		=>	$now_order['order_name'],
                'order_num'			=>	$now_order['num'],
                'order_total_money'	=>	floatval($now_order['total_price']+$now_order['delivery_fee']),
                'total_integral' => floatval($now_order['total_integral']),
                'exchange_type' =>$now_order['exchange_type'],
                'order_content'    =>  array(
                    0=>array(
                        'name'  		=> $now_gift['gift_name'],
                        'num'   		=> $now_order['num'],
                        'price' 		=> floatval($now_order['payment_money']),
                        'money' 	=> floatval($total_money),
                    )
                )
            );


        }else{
            if($now_order['exchange_type'] == 1){
                $total_money = $now_order['num'] * $now_order['payment_money'];
            }else{
                $total_money = 0;
            }

            $order_info = array(
                'order_id'			=>	$now_order['order_id'],
                'gift_id'			=>	$now_order['gift_id'],
                'order_type'		=>	'gift',
                'order_name'		=>	$now_order['order_name'],
                'order_num'			=>	$now_order['num'],
                'order_price'		=>	floatval($now_order['price']+$now_order['delivery_fee']),
                'order_total_money'	=>	floatval($total_money+$now_order['delivery_fee']),
                'img'               => $now_gift['wap_pic_list'][0]['url'],
                'exchange_type'     => $now_order['exchange_type'],
                'total_integral' => floatval($now_order['total_integral']),
                'order_total_money'	=>	floatval($now_order['total_price']),
            );
        }
        //实物
        // if($now_order['tuan_type'] == 2){
        $order_info['adress'] = $now_order['contact_name'].'，'.$now_order['adress'].'，'.$now_order['zipcode'].'，'.$now_order['phone'];
        switch($now_order['delivery_type']){
            case '1':
                $order_info['delivery_type'] = L_('工作日、双休日与假日均可送货');
                break;
            case '2':
                $order_info['delivery_type'] = L_('只工作日送货');
                break;
            case '3':
                $order_info['delivery_type'] = L_('双休日、假日送货：周六至周日');
                break;
            default:
                $order_info['delivery_type'] = L_('白天没人，其它时间送货');
                break;
        }
        $order_info['delivery_comment'] = $now_order['delivery_comment'];
        //  }

        return array('error'=>0,'order_info'=>$order_info);
    }


    //电脑站支付前订单处理
    public function web_befor_pay($order_info,$now_user){
        //判断是否需要在线支付
        if(!$order_info['use_balance']){
            $now_user['now_money']=0;
        }
        /* if(($now_user['now_money']+$order_info['score_deducte'] )< $order_info['order_total_money']){
             $online_pay = true;
         }else{
             $online_pay = false;
         }*/

        //$database_user = D('User');
        //不使用在线支付，直接使用用户余额。
        if(empty($online_pay)){
            /*if($order_info['exchange_type'] == 1){
                 $money_pay_result = $database_user->user_money($now_user['uid'],$order_info['order_total_money'],L_('购买 ').$order_info['order_name'].'*'.$order_info['order_num']);
                 if($money_pay_result['error_code']){
                     return array('error'=>1,'msg'=>$money_pay_result['msg']);
                 }
            }

            $use_result = $database_user->user_score($now_user['uid'] , $order_info['total_integral'] , L_('兑换 ').$order_info['order_name'].'*'.$order_info['order_num']);
            if($use_result['error_code']){
                return array('error'=>1,'msg'=>$use_result['msg']);
            }*/
        }else{
            if(!empty($now_user['now_money'])){
                $order_pay['balance_pay'] = $now_user['now_money'];
            }
        }

        //$money_pay_result = D('User')->user_money($now_user['uid'],$order_info['total_money'],'兑换 '.$order_info['order_name'].'*'.$order_info['num']);


        /*D('User_score_list')->add_row($now_user['uid'], 2, floatval($order_info['total_integral']) , L_('兑换 '). $order_info['gift_name'] .' 消费积分', false);
        if(!empty($now_order['exchange_type'])){
            D('User_money_list')->add_row($now_user['uid'],2,$order_info['total_integral'],'兑换 '.$order_info["order_name"].' 扣除余额');
        }*/




        //将已支付用户余额等信息记录到订单信息里
        /*if(!empty($order_pay['balance_pay'])){
            $data_gift_order['balance_pay'] = $order_pay['balance_pay'];
        }
        //扣除积分并保存订单
        if(!empty($order_info['score_deducte'])){
            $data_gift_order['score_used_count']	= (int)$order_info['score_used_count'];
            $data_gift_order['score_deducte']      = (float)$order_info['score_deducte'];
        }*/

        /* if(!empty($data_gift_order)){
             $data_gift_order['last_time'] = $_SERVER['REQUEST_TIME'];
             $condition_gift_order['order_id'] = $order_info['order_id'];
             if(!$this->where($condition_gift_order)->data($data_gift_order)->save()){
                 return array('error_code'=>true,'msg'=>L_('保存订单失败！请重试或联系管理员。'));
             }
         }*/
        /*if($online_pay){
            return array('error_code'=>false,'pay_money'=>$order_info['order_total_money'] - $now_user['now_money']-(float)$order_info['score_deducte']);
        }else{*/
        $order_param = array(
            'order_id' => $order_info['order_id'],
            'pay_type' => '',
            'third_id' => '',
            'is_mobile' => 0,
        );
        $result_after_pay = $this->after_pay($order_param);

        if($result_after_pay['error']){
            return array('error_code'=>true,'msg'=>$result_after_pay['msg']);
        }
        return array('error_code'=>false,'msg'=>L_('支付成功！'),'url'=>U('Index/Gift/gift_order_view',array('order_id'=>$order_info['order_id'])));
        //}
    }

    //手机端支付前订单处理
    public function wap_befor_pay($order_info , $now_user){
        $pay_money = $order_info['order_total_money'];


        //扣除积分
        $data_group_order['score_used_count']  = (int)$order_info['total_integral'];
        $order_result = $this->wap_pay_save_order($order_info,$data_group_order);
        if($order_result['error_code']){
            return $order_result;
        }
        $order_info['pay_money'] = $pay_money;
        return $this->wap_after_pay_before($order_info);


        $pay_money -= $order_info['score_deducte'];

        //判断帐户余额
        if(!empty($now_user['now_money'])&&$order_info['use_balance']){
            if($now_user['now_money'] >= $pay_money){
                $data_gift_order['balance_pay'] = $pay_money;
                /*$order_result = $this->wap_pay_save_order($order_info,$data_gift_order);
                if($order_result['error_code']){
                    return $order_result;
                }*/
                return $this->wap_after_pay_before($order_info);
            }else{
                $data_gift_order['balance_pay'] = $now_user['now_money'];
            }
            $pay_money -= $now_user['now_money'];
        }
        //在线支付
        $order_result = $this->wap_pay_save_order($order_info,$data_gift_order);
        if($order_result['error_code']){
            return $order_result;
        }
        return array('error_code'=>false,'pay_money'=>$pay_money);
    }

    //手机端支付前保存各种支付信息
    public function wap_pay_save_order($order_info,$data_gift_order){
        $data_gift_order['score_used_count'] = $data_gift_order['score_used_count'];
        $data_gift_order['last_time'] = $_SERVER['REQUEST_TIME'];
        $condition_gift_order['order_id'] = $order_info['order_id'];
        if($this->where($condition_gift_order)->data($data_gift_order)->save()){
            return array('error_code'=>false,'msg'=>L_('保存订单成功！'));
        }else{
            return array('error_code'=>true,'msg'=>L_('保存订单失败！请重试或联系管理员。'));
        }
    }

    //如果无需调用在线支付，使用此方法即可。
    public function wap_after_pay_before($order_info,$now_user){
        $order_param = array(
            'order_id' => $order_info['order_id'],
            'pay_type' => '',
            'third_id' => '',
            'is_source' => 1,
        );
        $result_after_pay = $this->after_pay($order_param);
        if($result_after_pay['error']){
            return array('error_code'=>true,'msg'=>$result_after_pay['msg']);
        }


        /*$database_user = D('User');
        if($order_info['exchange_type'] == 1){
            $money_pay_result = $database_user->user_money($now_user['uid'],$order_info['order_total_money'],L_('购买 ').$order_info['order_name'].'*'.$order_info['order_num']);
            if($money_pay_result['error_code']){
                return array('error'=>1,'msg'=>$money_pay_result['msg']);
            }
        }

        $use_result = $database_user->user_score($now_user['uid'] , $order_info['total_integral'] , L_('兑换 ').$order_info['order_name'].'*'.$order_info['order_num']);
        if($use_result['error_code']){
            return array('error'=>1,'msg'=>$use_result['msg']);
        }*/


        return array('error_code'=>false,'msg'=>L_('支付成功！'),'url'=>str_replace('/source/','/',U('Gift/success_order',array('order_id'=>$order_info['order_id']))));
    }



    //支付之后
    public function after_pay($order_param){
        unset($_SESSION['gift_order']);

        if($order_param['pay_type']!=''){
            $condition_gift_order['orderid'] = $order_param['order_id'];
        }else{
            $condition_gift_order['order_id'] = $order_param['order_id'];
        }

        $now_order = $this->field(true)->where($condition_gift_order)->find();

        if(empty($now_order)){
            return array('error'=>1,'msg'=>L_('当前订单不存在！'));
        }else if($now_order['paid'] == 1){
            if($order_param['is_mobile']){
                return array('error'=>1,'msg'=>L_('该订单已付款！'),'url'=>U('My/gift_order',array('order_id'=>$now_order['order_id'])));
            }else{
                return array('error'=>1,'msg'=>L_('该订单已付款！'),'url'=>U('Index/Gift/gift_order_view',array('order_id'=>$now_order['order_id'])));
            }
        }else{
            $database_user = D('User');

            //得到当前用户信息，不将session作为调用值，因为可能会失效或错误。
            $now_user = $database_user->get_user($now_order['uid']);
            if(empty($now_user)){
                return array('error'=>1,'msg'=>L_('没有查找到此订单归属的用户，请联系管理员！'));
            }
            $gift_where['is_del'] = 0;
            $gift_where['status'] = 1;
            $gift_where['gift_id'] = $now_order['gift_id'];

            $database_gift = D('Gift');
            $now_gift = $database_gift->gift_detail($gift_where);
            $now_gift = $now_gift['detail'];

            $check_result = $this->check_buy_num($now_order['uid'], $now_gift, $now_order['num']);
            if ($check_result['errcode']) {
                return $this->wap_after_pay_error($now_order, $order_param, $check_result['msg']);
            }

            if($now_order['exchange_type'] == 0){
                $use_score = $now_order['payment_pure_integral'] * $now_order['num'];
                D('User')->user_score($now_user['uid'],$use_score,L_('参加兑换').C('config.score_name').L_('礼品 ').$now_order['order_name'].' * '.$now_order['num']);
            }elseif($now_order['exchange_type'] == 1){
                $use_score = $now_order['payment_integral'] * $now_order['num'];
                D('User')->user_score($now_user['uid'],$use_score,L_('参加兑换').C('config.score_name').L_('礼品 ').$now_order['order_name'].' * '.$now_order['num']);
                $use_money = $now_order['total_price'];
                D('User')->user_money($now_user['uid'],$use_money,L_('参加兑换').C('config.score_name').L_('礼品 ').$now_order['order_name'].' * '.$now_order['num']);
            }

            $data_gift_order['payment_money'] = floatval($order_param['pay_money']);
            $data_gift_order['pay_type'] = $order_param['pay_type'];
            $data_gift_order['paid'] = 1;
            $data_gift_order['status'] = 0;
            $data_gift_order['pay_time'] = time();
            $data_gift_order['num'] = $order_param['num'];
            $data_gift_order['is_source'] = $order_param['is_source'] ? $order_param['is_source'] : 0;
            if($this->where($condition_gift_order)->data($data_gift_order)->save()){
                $condition_gift['gift_id'] = $now_order['gift_id'];

                //2015-11-19     更改结束状态
                $update_gift = $database_gift->where($condition_gift)->find();
                //$sale_count = $now_order['num'] + $update_gift['sale_count'];
                $sale_count = $order_param['num'] + $update_gift['sale_count'];

                $update_gift_data = array('sale_count' => $sale_count);
                /* if ($update_gift['count_num'] > 0 && $sale_count >= $update_gift['count_num']) {//更改结束状态
                     $update_gift_data['type'] = 3;
                 }*/
                D('Gift')->where($condition_gift)->save($update_gift_data);
                if($order_param['is_mobile']){
                    return array('error'=>0,'url'=>str_replace('/source/','/',U('gift/success_order',array('order_id'=>$now_order['order_id']))));
                }else{
                    return array('error'=>0,'url'=>U('Index/Gift/gift_order_view',array('order_id'=>$now_order['order_id'])));
                }
            }else{
                return array('error'=>1,'msg'=>L_('修改订单状态失败，请联系系统管理员！'));
            }
        }
    }

    //支付时，金额不够，记录到帐号
    public function wap_after_pay_error($now_order,$order_param,$error_tips){
        //记录充值的金额，因为 Pay/return_url 处没有返回order的具体信息，故在此调用。
        $user_result['error_code'] = 0;
        if($order_param['pay_money'] > 0){
            $alreadyMoney = M("User_money_list")
                ->where(['money'=>$order_param['pay_money'],'uid'=>$now_order['uid'],'ask'=> 11, 'ask_id'=>$now_order['order_id']])//11 gift_order
                ->order('pigcms_id desc')
                ->find();
            if(empty($alreadyMoney)){
                //$user_result = D('User')->add_money($now_order['uid'],$order_param['pay_money'],L_("支付订单：X1发生错误！原因是：X2",array("X1" => $now_order['order_id'],"X2" => $error_tips)),11,$now_order['order_id']);
            }
        }
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

    private function get_order_by_id($uid,$order_id,$user_type=1){
        $condition_gift_order['order_id'] = $order_id;
        if ($user_type==2) {
            $condition_gift_order['mer_id'] = $uid;
        }else{
            $condition_gift_order['uid'] = $uid;
        }
        
        $condition_gift_order['status'] = 0;
        return $this->field(true)->where($condition_gift_order)->find();
    }

    /**
     * @param int $uid
     * @param int $gift_id
     * @param int $num
     * @return array
     */
    private function check_buy_num($uid, $now_gift, $num)
    {
        $count = $this->where("gift_id='{$now_gift['gift_id']}' AND uid='{$uid}' AND paid=1")->sum('num');
        //最大购买数量
        $max_num = $now_gift['exchange_limit_num'];//一次购买最多的数量
        $min_num = 1;//一次最少数量

        if($now_gift['sku'] > 0){
            $k_num = $now_gift['sku'] - $now_gift['sale_count'] ; //实际库存

            if ($k_num < $min_num) {//库存等于销售量的时候不能买
                return array('errcode' => 1, 'msg' => L_('该商品已售空'));
            } else {
                if ($max_num > 0) {
                    $my_num = $max_num - $count;//我的剩余得到份数
                    $my_num = $my_num > $k_num ? $k_num : $my_num;
                    $my_num = $my_num > $max_num ? $max_num : $my_num;

                    if ($my_num < $num) {
                        if ($my_num > 0) {
                            return array('errcode' => 1, 'msg' => L_("您最多能购买X1份",array("X1" => $my_num))); 
                        } else {
                            return array('errcode' => 1, 'msg' => L_("该商品已限制单人只能购买X1份，您不能再购买了",array("X1" => $my_num))); 
                        }
                    } elseif ($num < $min_num) {
                        return array('errcode' => 1, 'msg' => L_("您一次最少要购买X1份",array("X1" => $min_num))); 
                    }
                } else {
                    if ($num > $k_num) {
                        return array('errcode' => 1, 'msg' => L_("您最多能购买X1份",array("X1" => $k_num))); 
                    }
                }
            }
        }else{
            if ($max_num > 0) {
                $my_num = $max_num - $count;//我的剩余得到份数
                $my_num = $my_num > $max_num ? $max_num : $my_num;
                if ($my_num < $num) {
                    if ($my_num > 0) {
                        return array('errcode' => 1, 'msg' => L_("您最多能购买X1个",array("X1" => $my_num))); 
                    } else {
                        return array('errcode' => 1, 'msg' => L_("此商品每个用户只能购买X1个",array("X1" => $max_num))); 
                    }
                } elseif ($num < $min_num) {
                    return array('errcode' => 1, 'msg' => L_("您一次最少要购买X1个",array("X1" => $min_num))); 
                }
            }
        }
    }

    public function get_order_detail_by_id($uid,$order_id,$is_wap=false,$check_user=true,$user_type=1){
        $condition_table = array(C('DB_PREFIX').'gift'=>'g',C('DB_PREFIX').'gift_order'=>'o');
        if($check_user){
            if ($user_type==2) {
                $condition_where = "`o`.`mer_id`='$uid' AND ";
            }else{
                $condition_where = "`o`.`uid`='$uid' AND ";
            }            
        }else{
            $condition_where = '';
        }
        $condition_where .= "`o`.`order_id`='$order_id' AND `o`.`gift_id`=`g`.`gift_id`";
        $now_order = $this->field('`g`.*,`o`.*')->where($condition_where)->table($condition_table)->find();
        if(!empty($now_order)){
            $gift_image_class = new gift_image();
            if($is_wap){
                $tmp_pic_arr = explode(';',$now_order['wap_pic']);
            }else{
                $tmp_pic_arr = explode(';',$now_order['pc_pic']);
            }

            $now_order['list_pic'] = $gift_image_class->get_image_by_path($tmp_pic_arr[0]);
            $now_order['url'] = D('Gift')->get_gift_url($now_order['gift_id'],$is_wap);
            $now_order['order_url'] = $this->get_order_url($now_order['gift_id'],$is_wap);

            $now_order['price'] = floatval($now_order['price']);
            $now_order['total_money'] = floatval($now_order['total_money']);
            $now_order['pay_type_txt'] = D('Pay')->get_pay_name($now_order['pay_type'],$now_order['is_mobile_pay']);

            if(!empty($now_order['exchange_limit_num'])&& ($now_order['exchange_limit_num'] > 0)){
                $order_count = $this->where(array('gift_id'=>$now_order['gift_id'],'uid'=>$uid,'paid'=>1))->sum('num');
                $now_order['remain_num'] = $now_order['exchange_limit_num'] - $order_count;
            }
            
            // 门店自取信息
            if ($now_order['pick_id']) {
                $pick_address = D('Gift_pick_address')->get_one_pick($now_order['pick_id']);
                $now_order['pick_address'] = $pick_address;
            }
        }
        return $now_order;
    }


    public function get_order_detail_by_id_and_merId($order_id,$is_wap=false){
        $condition_table = array(C('DB_PREFIX').'gift'=>'g',C('DB_PREFIX').'gift_order'=>'o');
        $condition_where = "`o`.`order_id`='$order_id' AND `o`.`gift_id`=`g`.`gift_id`";

        $left_join = 'LEFT JOIN '.C('DB_PREFIX').'user as u on `o`.`uid`=`u`.`uid` LEFT JOIN '.C('DB_PREFIX').'merchant as m on `o`.`mer_id`=`m`.`mer_id`';

        $now_order = $this->field('`o`.*,`g`.`gift_name`,`u`.`nickname`,`u`.`phone` `user_phone`,`m`.`name`,`m`.`phone` as m_phone')->join($left_join)->where($condition_where)->table($condition_table)->find();


        if(!empty($now_order)){
            $gift_image_class = new gift_image();
            $tmp_pic_arr = explode(';',$now_order['pic']);
            $now_order['list_pic'] = $gift_image_class->get_image_by_path($tmp_pic_arr[0],'s');

            $now_order['price'] = floatval($now_order['price']);
            $now_order['total_money'] = floatval($now_order['total_money']);
            $now_order['pay_type_txt'] = D('Pay')->get_pay_name($now_order['pay_type'],$now_order['is_mobile_pay']);
            if($now_order['gift_pass']){
                $now_order['gift_pass_txt'] = preg_replace('#(\d{4})#','$1 ',$now_order['gift_pass']);
            }

            // 门店自取信息
            if ($now_order['pick_id']) {
                $pick_address = D('Gift_pick_address')->get_one_pick($now_order['pick_id']);
                $now_order['pick_address'] = $pick_address;
            }
        }
        return $now_order;
    }


    public function get_order_url($order_id,$is_wap=false){
        if($is_wap){
            return U('Index/Gift/gift_detail',array('gift_id'=>$order_id));
        }else{
            return U('Index/Gift/gift_order_view',array('order_id'=>$order_id));
        }
    }


    /*得到订单列表*/
    public function get_order_list($uid , $status , $is_wap=false , $page = 20 , $user_type=1){
        if ($user_type==2) {
            $condition_where = "`o`.`mer_id`='$uid' AND `o`.`gift_id`=`g`.`gift_id` AND `o`.`is_del`=0";
            import('@.ORG.merchant_page');
        }else{
            $condition_where = "`o`.`uid`='$uid' AND `o`.`gift_id`=`g`.`gift_id` AND `o`.`is_del`=0";
            import('@.ORG.user_page');
        }

        if($status == -1){
            $condition_where .= " AND `o`.`paid` = 0 ";
        }else if($status == 1){
            $condition_where .= " AND `o`.`paid` = 1 AND `o`.`status`=1";
        }else if($status == 2){
            $condition_where .= " AND `o`.`paid` = 1 AND `o`.`status`=2";
        }

        $condition_table = array(C('DB_PREFIX').'gift'=>'g',C('DB_PREFIX').'gift_order'=>'o');

        $count = $this->where($condition_where)->table($condition_table)->count();
        $p = new Page($count,$page);
        $order_list = $this->field('`o`.*,`g`.`exchange_type` `gift_exchange_type`,g.`pc_pic` as pc_pic_ ,g.`wap_pic` as wap_pic_')->where($condition_where)->table($condition_table)->order('`o`.`order_time` DESC')->limit($p->firstRow.','.$p->listRows)->select();

        if(!empty($order_list)){
            $gift_image_class = new gift_image();
            foreach($order_list as $k=>$v){
                $tmp_pic_arr = explode(';',$v['pic']);
                //56 $order_list[$k]['list_pic'] = $gift_image_class->get_image_by_path($tmp_pic_arr[0]);
                $order_list[$k]['url'] = $this->get_order_url($v['gift_id'],$is_wap);
                $order_list[$k]['price'] = floatval($v['price']);
                $order_list[$k]['total_money'] = floatval($v['total_money']);


                if(!empty($v['pc_pic_'])){
                    $gift_image_class = new gift_image();
                    $pc_pic_arr = explode(';',$v['pc_pic_']);
                    $pc_pic_list = array();
                    foreach($pc_pic_arr as $key=>$pic){
                        $pc_pic_list[$key]['title'] = $pic;
                        $tmp_pic = $gift_image_class->get_image_by_path($pic);
                        $pc_pic_list[$key]['image_url'] = $tmp_pic['image'];
                    }
                    $order_list[$k]['pc_pic_list'] = $pc_pic_list;
                    $order_list[$k]['image_url'] = $pc_pic_list[0]['image_url'];
                }


                if(!empty($v['wap_pic_'])){
                    $gift_image_class = new gift_image();
                    $wap_pic_arr = explode(';',$v['wap_pic_']);
                    $wap_pic_list = array();
                    foreach($wap_pic_arr as $keys=>$pic){
                        $wap_pic_list[$keys]['title'] = $pic;
                        $tmp_pic = $gift_image_class->get_image_by_path($pic);
                        $wap_pic_list[$keys]['image_url'] = $tmp_pic['image'];
                    }
                    $order_list[$k]['wap_pic_list'] = $wap_pic_list;
                    $order_list[$k]['image_url'] = $wap_pic_list[0]['image_url'];
                }
            }
        }

        $return['pagebar'] = $p->show();
        $return['order_list'] = $order_list ? $order_list : array();
        $return['total_count'] = $count;
        $return['totalPage'] = ceil($count/$page);

        return $return;
    }

    //退款
    public function refund($now_order){
        $return = [];
        if($now_order['paid']==1&&in_array($now_order['status'],[0,1])){
            $data = [];
            $data['status'] =5;
            $data['refund_time'] =time();
            $result = $this->where(array('order_id'=>$now_order['order_id']))->data($data)->save();
            if($result){
                /**
                $sql  = 'update `'.C("DB_PREFIX").'gift` set `sale_count`=`sale_count`-'.$now_order['num'];//减销量
                $sql .=',`sku`=`sku`+'.$now_order['num'];//加库存
                $sql .= ' where `gift_id`='.$now_order['gift_id'];
                M('')->query($sql);
                fdump($sql,'gift_order_refund_err_log',1);
                fdump(M()->getLastSql(),'gift_order_refund_err_log',1);//记录下日志
                **/
                $gift_where = array();
                $gift_where['gift_id'] = $now_order['gift_id'];
                $gift = M('gift')->where($gift_where)->find();
                $gift_data = [];
                $gift_data['sale_count'] = $gift['sale_count']-$now_order['num'];//减销量
                $gift_data['sku'] = $gift['sku']+$now_order['num'];//加库存
                M('gift')->where($gift_where)->save($gift_data);

                if($now_order['exchange_type']==1){//积分+金额
                    D('User')->add_money($now_order['uid'], $now_order['payment_money'],L_("积分商城取消订单，退还余额，订单号X1",array("X1" => $now_order['order_id'])));
                    D('User')->add_score($now_order['uid'], $now_order['payment_integral']*$now_order['num'],L_('积分商城取消订单，退还积分，订单号：X1',array("X1" => $now_order['order_id'])));
                }else{//仅积分
                    D('User')->add_score($now_order['uid'], $now_order['payment_pure_integral']*$now_order['num'],L_('积分商城取消订单，退还积分，订单号：X1',array("X1" => $now_order['order_id'])));
                }
                D("System_order")->save_order_status('gift', $now_order['order_id'], 4);
                $return['error_code'] = 0;
                $return['msg'] = '退款成功！';
            }else{
                $return['error_code'] = 1;
                $return['msg'] = '退款失败！';
            }
        }else{
            $return['error_code'] = 1;
            $return['msg'] = '订单状态不符合退款标准';
        }
        return $return;

    }


    /**
     * 积分商城自提订单发货后超时自动确认收货
     *
     * @return void
     * @author: zt
     * @date: 2023/07/06
     */
    public function giftOrderAutoConfirm()
    {
        $expireDays = C('config.gift_accept');
        if ($expireDays < 1) {
            return true;
        }

        $tm = time();
        $where = [
            'o.status' => 1,
            'o.is_pick_in_store' => 1,
            'po.dateline' => ['LT', $tm - $expireDays * 86400],
            'o.pay_time' => ['GT', $tm - 15 * 86400]
        ];

        $giftOrderMod = M('Gift_order');
        $systemOrderMod =  D("System_order");
        $orders = $giftOrderMod->alias('o')
            ->join('INNER JOIN ' . C('DB_PREFIX') . 'gift_pick_order po ON o.order_id=po.order_id')
            ->order('o.order_id DESC')
            ->where($where)
            ->select();

        foreach ($orders as $o) {
            $updateRs = $giftOrderMod->where(['order_id' => $o['order_id']])->save(['status' => 2]);

            if ($updateRs !== false) {
                $systemOrderMod->save_order_status('gift', $o['order_id'], 6, 2);
            }
        }
        return true;
    }
}
?>