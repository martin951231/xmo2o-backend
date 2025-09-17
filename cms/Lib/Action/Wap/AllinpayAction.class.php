<?php
class AllinpayAction extends BaseAction{
	protected $info; //商家信息
	public function __construct(){
		
		parent::__construct();
		if(IS_GET){
			if(empty($this->user_session)){
				redirect(U('Login/index'));
			}
		}
        $rand_id = $_REQUEST['order_id'];
        $mer_id = M('Allinpay_order')->field('mer_id')->where(array('allinpay_rand_id'=>$rand_id))->find();
        $this->info = M('Merchant_ownpay')->field('allinpay_mer')->where(array('mer_id'=>$mer_id['mer_id']))->find();
        $this->info = @unserialize($this->info['allinpay_mer']);
	}
    public function index(){
		
		if(empty($this->config['pay_allinpay_mer_open'])){
			$this->error_tips('平台未开启银行卡支付或未填写正确参数');
		}
		$sql = "SELECT * FROM " . C('DB_PREFIX') . "allinpay  WHERE uid={$this->user_session['uid']} AND mer_id={$_GET['mer_id']} AND is_bind='1' ORDER BY card_id DESC";
		
		$card_list = M()->query($sql);

		if($_GET['no_order']){
			$_SESSION['allinpay_order_id'] = '';
		}

        $discounts = D('Allinpay_discount')->getDiscounts($_GET['mer_id']);
        $discountss = array();
		foreach($discounts as $kk => $vv){
		    if($kk > 4) break;
		    if($vv['card_limit'] == 0){
		        $vv['bank_desc'] = '支持全部银行卡';
		        $vv['banks_len'] = '-1';
                $discountss[] = $vv;
            }else{
                $where['a.did'] = $vv['id'];
		        $list = M('Allinpay_discount_card')->join('as a LEFT JOIN '.C('DB_PREFIX').'banklist b ON a.cardbin = b.cardbin')->field('b.bankname,b.cardtype,b.cardbin')->where($where)->select();
		        if(empty($list)) continue;
		        $len = count($list);
		        foreach($list as $kkk => $l){
		            if($kkk >4) {
                        $vv['bank_desc'] .= '等'.$len.'个银行';
                        break;
                    }else{
                        $vv['bank_desc'] .= $l['bankname'].'('.$l['cardbin'].'),';
                    }
                }
                $vv['bank_desc'] = substr($vv['bank_desc'],0,-1);
                $vv['banks_len'] = $len;
                $discountss[] = $vv;
            }
        }
        if(empty($discountss)) $discountss = '';
		$this->assign('discountss',$discountss);

		foreach($discounts as $k => $discount){
		    if($discount['card_limit']==1){
                $card_arr = M('Allinpay_discount_card')->field('cardbin')->where(array('did'=>$discount['id']))->select();
                $cards = array();
                foreach($card_arr as $v){
                    $cards[] = $v['cardbin'];
                }
                foreach($card_list as &$card){
                    if(in_array($card['cardBin'],$cards)){
                        if($discount['single_num']==0 && $discount['total_num']==0){ //无限次
                            $discount['card_limit_num'] = -1;
                            $discount['card_can_use'] = -1;
                            $card['sort']++;
                        }elseif($discount['single_num']==0 && $discount['total_num']>0){ //单卡无限，总额有限
                            $count = M('Allinpay_order')->where(array('did'=>$discount['id'],'is_paid'=>1))->count();
                            if($count >= $discount['total_num']){
                                $discount['card_limit_num'] = -1;
                                $discount['card_can_use'] = 0;
                            }else{
                                $discount['card_limit_num'] = -1;
                                $discount['card_can_use'] = -1;
                                $card['sort']++;
                            }
                        }elseif($discount['single_num']>0 && $discount['total_num']==0){ //单卡有限，总额无限
                            //判断周期 0不限 1每月 2每周 3每日
                            $where = array('did'=>$discount['id'],'is_paid'=>1,'card_no'=>$card['cardNo']);
                            if($discount['cycle_type']>0){
                                $times = D('Allinpay_discount')->getCycleTime($discount['cycle_type']);
                                $where['pay_time'] = array('between',$times);
                            }
                            $count = M('Allinpay_order')->where($where)->count();
                            if($count >= $discount['single_num']){
                                $discount['card_limit_num'] = $discount['single_num'];
                                $discount['card_can_use'] = 0;
                            }else{
                                $discount['card_limit_num'] = $discount['single_num'];
                                $discount['card_can_use'] = $discount['single_num'] - $count;
                                $card['sort']++;
                            }
                        }elseif($discount['single_num']>0 && $discount['total_num']>0){//单卡有限，总额有限
                            //判断周期 0不限 1每月 2每周 3每日
                            $where = array('did'=>$discount['id'],'is_paid'=>1,'card_no'=>$card['cardNo']);
                            if($discount['cycle_type']>0){
                                $times = D('Allinpay_discount')->getCycleTime($discount['cycle_type']);
                                $where['pay_time'] = array('between',$times);
                            }
                            $single_count = M('Allinpay_order')->where($where)->count();
                            $total_count = M('Allinpay_order')->where(array('did'=>$discount['id'],'is_paid'=>1))->count();
                            if($single_count >= $discount['single_num'] || $total_count >= $discount['total_num']){
                                $discount['card_limit_num'] = $discount['single_num'];
                                $discount['card_can_use'] = 0;
                            }else{
                                $discount['card_limit_num'] = $discount['single_num'];
                                $discount['card_can_use'] = $discount['single_num'] - $single_count;
                                $card['sort']++;
                            }
                        }
                        $card['discounts'][] = $discount;
                    }
                    $card['last_no'] = substr($card['cardNo'],-4);
                    if(empty($card['sort'])) $card['sort'] = 0;
                }
            }else{
                foreach($card_list as &$card){
                    if($discount['single_num']==0 && $discount['total_num']==0){ //无限次
                        $discount['card_limit_num'] = -1;
                        $discount['card_can_use'] = -1;
                        $card['sort']++;
                    }elseif($discount['single_num']==0 && $discount['total_num']>0){ //单卡无限，总额有限
                        $count = M('Allinpay_order')->where(array('did'=>$discount['id'],'is_paid'=>1))->count();
                        if($count >= $discount['total_num']){
                            $discount['card_limit_num'] = -1;
                            $discount['card_can_use'] = 0;
                        }else{
                            $discount['card_limit_num'] = -1;
                            $discount['card_can_use'] = -1;
                            $card['sort']++;
                        }
                    }elseif($discount['single_num']>0 && $discount['total_num']==0){ //单卡有限，总额无限
                        //判断周期 0不限 1每月 2每周 3每日
                        $where = array('did'=>$discount['id'],'is_paid'=>1,'card_no'=>$card['cardNo']);
                        if($discount['cycle_type']>0){
                            $times = D('Allinpay_discount')->getCycleTime($discount['cycle_type']);
                            $where['pay_time'] = array('between',$times);
                        }
                        $count = M('Allinpay_order')->where($where)->count();

                        if($count >= $discount['single_num']){
                            $discount['card_limit_num'] = $discount['single_num'];
                            $discount['card_can_use'] = 0;
                        }else{
                            $discount['card_limit_num'] = $discount['single_num'];
                            $discount['card_can_use'] = $discount['single_num'] - $count;
                            $card['sort']++;
                        }
                    }elseif($discount['single_num']>0 && $discount['total_num']>0){//单卡有限，总额有限
                        //判断周期 0不限 1每月 2每周 3每日
                        $where = array('did'=>$discount['id'],'is_paid'=>1,'card_no'=>$card['cardNo']);
                        if($discount['cycle_type']>0){
                            $times = D('Allinpay_discount')->getCycleTime($discount['cycle_type']);
                            $where['pay_time'] = array('between',$times);
                        }
                        $single_count = M('Allinpay_order')->where($where)->count();
                        $total_count = M('Allinpay_order')->where(array('did'=>$discount['id'],'is_paid'=>1))->count();
                        if($single_count >= $discount['single_num'] || $total_count >= $discount['total_num']){
                            $discount['card_limit_num'] = $discount['single_num'];
                            $discount['card_can_use'] = 0;
                        }else{
                            $discount['card_limit_num'] = $discount['single_num'];
                            $discount['card_can_use'] = $discount['single_num'] - $single_count;
                            $card['sort']++;
                        }
                    }
                    if(empty($card['sort'])) $card['sort'] = 0;
                    $card['discounts'][] = $discount;
                    $card['last_no'] = substr($card['cardNo'],-4);
                }
            }
        }
        array_multisort(array_column($card_list,'sort'),SORT_DESC,$card_list);
        $this->assign('card_list',$card_list);

		if($_GET['order_id'] || $_SESSION['allinpay_order_id']){
			$condition_order['allinpay_rand_id'] = $_GET['order_id'] ? $_GET['order_id'] : $_SESSION['allinpay_order_id'];
			$now_order = M('Allinpay_order')->where($condition_order)->find();
			if(empty($now_order) || $now_order['uid'] != $this->user_session['uid']){
				$this->error('该订单不存在');
			}
			$_SESSION['allinpay_order_id'] = $now_order['allinpay_order_id'];
			$this->assign('now_order',$now_order);
		}

    	$this->display();
    }
	public function add(){	
		$now_year = date('Y');
		$card_over_year = $now_year+10;
		$card_over_year_array = array();
		for($i = $now_year;$i <= $card_over_year;$i++){
			$card_over_year_array[] = array(
				'four'=>$i,
				'two'=>substr($i,-2)
			);
		}
		$this->assign('card_over_year_array',$card_over_year_array);
		
		$card_over_month_array = array();
		for($i = 1;$i <= 12;$i++){
			if($i < 10){
				$card_over_month_array[] = '0'.$i;
			}else{
				$card_over_month_array[] = $i;
			}
		}
		$this->assign('card_over_month_array',$card_over_month_array);
	
		$this->display();
	}
	public function motify(){
		if(empty($this->user_session)){
			$this->error('请先登录');
		}

		if($this->info['open']==0) $this->error('商家关闭了该支付方式');

        $data['cusid'] = $this->info['pay_allinpay_mer_id'];
        $data['appid'] = $this->info['pay_allinpay_mer_appid'];
        $s_data['key'] = $this->info['pay_allinpay_mer_key'];

        $data['meruserid'] = $this->user_session['uid'];
        $data['accttype'] = $_POST['creditFlag'];
        $data['acctno'] = $_POST['cardNo'];
        $data['idno'] = $_POST['certificateNo'];
        $data['acctname'] =$_POST['userName'];
        $data['mobile'] = $_POST['phoneNo'];
        //信用卡
        if($_POST['creditFlag']=='02'){
            $data['validdate'] = $_POST['checkExpiry'];
            $data['cvv2'] = $_POST['checkNo'];
        }

        $url = $this->config['allinpay_mer_url'].'/qpay/agreeapply';
        $return = $this->sendAllinpay($url,$s_data,$data);
        //验签
        $check = $this->ValidSign($return,$s_data['key']);
        if(!$check){
            $this->error('验签失败，非法访问！');
        }
		if($return['trxstatus'] != '1999'){
			$this->error('绑卡失败提示：'.$return['errmsg']);
		}
		if(!empty($return['thpinfo'])){
            $_POST['thpinfo'] = $return['thpinfo'];
        }
		$_POST['uid'] = $this->user_session['uid'];
		$_POST['cardBin'] = substr($_POST['cardNo'],0,6);
		$_POST['mer_id'] =$_GET['mer_id'];
		//添加成功返回ID给前台
		if($card_id = M('Allinpay')->data($_POST)->add()){
			$this->success($card_id);
		}else{
			$this->error('绑卡保存失败，请重试');
		}
	}
	
	public function order_sms(){
		//校验GET参数 bind_id 以及bind_id是否对应当前用户
		$condition['card_id'] = intval($_GET['bind_id']);
		$condition['uid'] = $this->user_session['uid'];
		$now_card = M('Allinpay')->where($condition)->find();

		if(empty($now_card)){
			$this->error('绑卡不存在');
		}
		
		$this->assign('now_card',$now_card);

		$condition_order['allinpay_rand_id'] = $_GET['order_id'];
		$now_order = M('Allinpay_order')->where($condition_order)->find();
		if(empty($now_order) || $now_order['uid'] != $this->user_session['uid']){
			$this->error('该订单不存在');
		}
		$this->assign('now_order',$now_order);
			
		$this->display();
	}
	public function get_order_sms()
    {
        if (empty($this->user_session)) {
            $this->error('请先登录');
        }

        $condition['card_id'] = intval($_POST['bind_id']);
        $condition['uid'] = $this->user_session['uid'];
        $now_card = M('Allinpay')->where($condition)->find();
        if (empty($now_card)) {
            $this->error('绑卡不存在');
        }
        $now_order = M('Allinpay_order')->where(array('allinpay_rand_id' => $_POST['order_id']))->find();
        $is_mobile = 1;
        if(strpos($_SERVER['HTTP_USER_AGENT'],'wxapp')!==false){
            $is_mobile = 3;
        }else if($this->is_app_browser){
		    $is_mobile = 2;
        }

        $pay_money = $now_order['order_price'] - $now_order['discount_money'];
        M('Payment_money_order')->where(array('order_type'=>$now_order['order_type'],'orderid'=>$now_order['order_id']))->setField(array('payment_money'=>$pay_money));
        if($pay_money<=0){
            //支付金额为0或小于0的情况
            $data['pay_time'] = time();
            $data['paid_money'] = 0;
            $data['is_paid'] = 1;
            $where['order_id'] = $now_order['order_id'];
            if(M('Allinpay_order')->where($where)->data($data)->save()){
                $now_order = M('Allinpay_order')->where(array('order_id'=>$now_order['order_id']))->find();
                if(empty($now_order)){
                    $this->error('该订单不存在');
                }
                $is_mobile = 1;
                if(strpos($_SERVER['HTTP_USER_AGENT'],'wxapp')!==false){
                    $is_mobile = 3;
                }else if($this->is_app_browser){
                    $is_mobile = 2;
                }
                $order_param['pay_type'] = 'allinpay_mer';
                $order_param['is_mobile'] = $is_mobile;
                $order_param['order_type'] = $now_order['order_type'];
                $order_param['order_id'] = $now_order['order_id'];
                $order_param['third_id'] = $now_order['trxid'];
                $order_param['pay_money'] = $now_order['paid_money'];
                $order_param['is_own'] = 1;
            }else{
                fdump_sql($data,'allinpay_save_exception');
                $this->error('支付存储失败，请联系管理员');
            }
            $go_query_param['order_param'] = $order_param;
            if($payment_money_order = M('Payment_money_order')->where(array('orderid'=>$go_query_param['order_param']['order_id'],'order_type'=>$go_query_param['order_param']['order_type']))->find()){
                M('Payment_money_order')->where(array('orderid'=>$go_query_param['order_param']['order_id'],'order_type'=>$go_query_param['order_param']['order_type']))->setField('paid_money',$go_query_param['order_param']['pay_money']);
                if($payment_money_order['payment_money'] > $go_query_param['order_param']['pay_money'] + 0.1){
                    fdump_sql($payment_money_order,'allinpay_check_pay_exception');
                    fdump_sql($go_query_param,'allinpay_check_pay_exception');
                    $this->error_tips('支付金额异常');
                }
            }else{
                fdump_sql($go_query_param,'allinpay_payment_order_exception');
                $this->error_tips('支付异常');
            }
            switch($go_query_param['order_param']['order_type']){
                case 'group':
                    D('Group_order')->after_pay($go_query_param['order_param']);
                    break;
                case 'meal':
                case 'takeout':
                case 'food':
                case 'foodPad':
                    D('Meal_order')->after_pay($go_query_param['order_param'], $go_query_param['order_param']['order_type']);
                    break;
                case 'weidian':
                    $pay_info = D('Weidian_order')->after_pay($go_query_param['order_param']);
                    if(($pay_info['error']==0) && isset($pay_info['url'])){  /***异步通知***/
                        $this->NoticeWDAsyn($now_order['orderid']);
                    }
                    break;
                case 'recharge':
                    D('User_recharge_order')->after_pay($go_query_param['order_param']);
                    break;
                case 'waimai':
                    D('Waimai_order')->after_pay($go_query_param['order_param']);
                    break;
                case 'appoint':

                    D('Appoint_order')->after_pay($go_query_param['order_param']);

                    break;
                case 'balance-appoint':

                    D('Appoint_order')->balance_after_pay($go_query_param['order_param']);

                    break;
                case 'wxapp':
                    D('Wxapp_order')->after_pay($go_query_param['order_param']);
                    break;
                case 'store':
                    D('Store_order')->after_pay($go_query_param['order_param']);
                    break;
                case 'shop':
                case 'mall':
                    D('Shop_order')->after_pay($go_query_param['order_param']);
                    break;
                case 'plat':
                    D('Plat_order')->after_pay($go_query_param['order_param']);
                    break;
                case 'spread':
                    D('Merchant_spread_order')->after_pay($go_query_param['order_param']);
                    break;
                case 'mcard':
                    D('Membership_card_order')->after_pay($go_query_param['order_param']);
                    break;
            }
            $url = D('Pay')->back_url($now_order['order_type'],$now_order);
            exit(json_encode(array('status'=>1,'url'=>$url,'discount_money'=>$now_order['discount_money'],'paid_money'=>$now_order['paid_money'])));
        }

		$post = array(
            'cusid'   => $this->info['pay_allinpay_mer_id'],
            'appid' => $this->info['pay_allinpay_mer_appid'],
			'orderid' => $now_order['order_type'].'_'.$now_order['order_id'].($now_order['is_own'] ? '_1' : ''),
			'agreeid' => $now_card['agreeid'],
			'amount' => $pay_money * 100,
			'currency' => 'CNY',
			'trxreserve' => $is_mobile,
			'subject' => $now_order['order_name'],
			'notifyurl' 	=> $this->config['site_url'].'/source/allinpay_notice.php',
		);
        $url = $this->config['allinpay_mer_url'].'/qpay/payapplyagree';
        $s_data['key'] = $this->info['pay_allinpay_mer_key'];
        $return = $this->sendAllinpay($url,$s_data,$post);
        //验签
        $check = $this->ValidSign($return,$s_data['key']);
        if(!$check){
            $this->error('验签失败，非法访问！');
        }
        if($return['trxstatus'] != '1999'){
            $this->error('失败提示：'.$return['errmsg']);
        }
        if(!empty($return['thpinfo'])){
            M('Allinpay_order')->where(array('allinpay_rand_id'=>$_POST['order_id']))->save(array('thpinfo'=>$return['thpinfo']));
        }
		$this->success('发送短信成功');
	}
	
	public function confirmSms(){
		//校验GET参数 bind_id 以及bind_id是否对应当前用户
		$condition['card_id'] = intval($_GET['bind_id']);
		$condition['uid'] = $this->user_session['uid'];
		$now_card = M('Allinpay')->where($condition)->find();

		if(empty($now_card)){
			$this->error_tips('绑卡不存在');
		}
		
		$this->assign('now_card',$now_card);
		
		$this->display();
	}
	public function delete(){
		$condition['card_id'] = intval($_POST['bind_id']);
		$condition['uid'] = $this->user_session['uid'];
		$now_card = M('Allinpay')->where($condition)->find();
		if(empty($now_card)){
			$this->error_tips('绑卡不存在');
		}

        $post = array(
            'cusid'   => $this->info['pay_allinpay_mer_id'],
            'appid' => $this->info['pay_allinpay_mer_appid'],
            'agreeid' => $now_card['agreeid'],
        );
        $url = $this->config['allinpay_mer_url'].'/qpay/unbind';
        $s_data['key'] = $this->info['pay_allinpay_mer_key'];
        $return = $this->sendAllinpay($url,$s_data,$post);
        //验签
        $check = $this->ValidSign($return,$s_data['key']);
        if(!$check){
            $this->error('验签失败，非法访问！');
        }
        if($return['retcode']=="SUCCESS"){
            if(M('Allinpay')->where($condition)->delete()){
                $this->success('解绑成功');
            }else{
                $this->error('解绑失败，请重试');
            }
        }else{
            $this->error('解绑失败，请重试'.$return['retmsg']);
        }
		

	}
	public function card_save(){
		if(empty($this->user_session)){
			$this->error('请先登录');
		}
		
		$condition['card_id'] = intval($_POST['bind_id']);
		$condition['uid'] = $this->user_session['uid'];
		$now_card = M('Allinpay')->where($condition)->find();
		if(empty($now_card)){
			$this->error('绑卡不存在');
		}
		$s_data['key'] = $this->info['pay_allinpay_mer_key'];
		$post = array(
            'cusid' => $this->info['pay_allinpay_mer_id'],
            'appid' => $this->info['pay_allinpay_mer_appid'],
			'meruserid' 	=> $this->user_session['uid'],
			'accttype' 	=> $now_card['creditFlag'],
			'acctno' 	=> $now_card['cardNo'],
			'idno' 	=> $now_card['certificateNo'],
			'acctname' 	=> $now_card['userName'],
			'mobile'	=> $now_card['phoneNo'],
			'smscode'	=> $_POST['smsCode'],
		);
		if($now_card['creditFlag']=='02'){
            $post['validdate'] = $now_card['checkExpiry'];
            $post['cvv2'] = $now_card['checkNo'];
        }
        if($now_card['thpinfo']){
            $post['thpinfo'] = $now_card['thpinfo'];
        }
        $url = $this->config['allinpay_mer_url'].'/qpay/agreeconfirm';
        $return = $this->sendAllinpay($url,$s_data,$post);
        //验签
        $check = $this->ValidSign($return,$s_data['key']);
        if(!$check){
            $this->error('验签失败，非法访问！');
        }
		if($return['trxstatus'] != '0000'){
			$this->error('绑卡失败提示：'.$return['errmsg']);
		}
		
		$data['agreeid'] = strval($return['agreeid']);
		$data['bankcode'] = $return['bankcode'];
		$data['bankname'] = $return['bankname'];
		$data['is_bind'] = '1';
		$data['bind_time'] = time();
		if(M('Allinpay')->where($condition)->data($data)->save()){
			$this->success('绑卡成功');
		}else{
			$this->error('绑卡失败，请重试');
		}
	}
	public function card_pay(){
		if(empty($this->user_session)){
			$this->error('请先登录');
		}
		$condition['card_id'] = intval($_POST['bind_id']);
		$condition['uid'] = $this->user_session['uid'];
		$now_card = M('Allinpay')->where($condition)->find();
		if(empty($now_card)){
			$this->error('绑卡不存在');
		}
		
		$condition_order['allinpay_rand_id'] = $_POST['order_id'];
		$now_order = M('Allinpay_order')->where($condition_order)->find();
		if(empty($now_order) || $now_order['uid'] != $this->user_session['uid']){
			$this->error('该订单不存在');
		}

        $post = array(
            'cusid'   => $this->info['pay_allinpay_mer_id'],
            'appid' => $this->info['pay_allinpay_mer_appid'],
            'orderid' => $now_order['order_type'].'_'.$now_order['order_id'].($now_order['is_own'] ? '_1' : ''),
            'agreeid' => $now_card['agreeid'],
            'smscode' => $_POST['smsCode']
        );
		if(!empty($now_order['thpinfo'])){
		    $post['thpinfo'] = $now_order['thpinfo'];
        }
        $url = $this->config['allinpay_mer_url'].'/qpay/payagreeconfirm';
        $s_data['key'] = $this->info['pay_allinpay_mer_key'];
        $return = $this->sendAllinpay($url,$s_data,$post);
        if(!$return || empty($return)){
            $this->error('支付方返回数据异常，请重试！');
        }
        //验签
        $check = $this->ValidSign($return,$s_data['key']);
        if(!$check){
            $this->error('验签失败，非法访问！');
        }
        if($return['trxstatus'] == '0000'){
            //交易查询
            $query_url = $this->config['allinpay_mer_url'].'/qpay/query';
            $this->info = M('Merchant_ownpay')->field('allinpay_mer')->where(array('mer_id'=>$now_order['mer_id']))->find();
            $this->info = @unserialize($this->info['allinpay_mer']);
            $s_data['key'] = $this->info['pay_allinpay_mer_key'];
            $post = array(
                'cusid'   => $this->info['pay_allinpay_mer_id'],
                'appid' => $this->info['pay_allinpay_mer_appid'],
                'orderid' => $now_order['order_type'].'_'.$now_order['order_id'].($now_order['is_own'] ? '_1' : ''),
                'trxid' => $return['trxid'],
            );
            $returnq = $this->sendAllinpay($query_url,$s_data,$post);
            //验签
            $check = $this->ValidSign($returnq,$s_data['key']);
            if(!$check){
                $this->error('验签失败，非法访问！');
            }
            if($returnq['trxstatus']!='0000'){
                fdump_sql($returnq,'allinpay_query_exception');
                $this->error('支付查询异常，请联系管理员！');
            }
            $result_orderid = explode('_',$return['orderid']);
            $data['fee'] = $return['fee'];
            $data['pay_time'] = time();
            $data['paid_time'] = $return['paytime'];
            $data['trxid'] = $return['trxid']; //收银宝平台流水
            $data['paid_money'] = $returnq['trxamt'] / 100;
            $data['is_paid'] = 1;
            $where['order_id'] = $result_orderid[1];
            if(M('Allinpay_order')->where($where)->data($data)->save()){
                $now_order = M('Allinpay_order')->where(array('order_id'=>$result_orderid[1]))->find();
                if(empty($now_order)){
                    $this->error('该订单不存在');
                }
                $is_mobile = 1;
                if(strpos($_SERVER['HTTP_USER_AGENT'],'wxapp')!==false){
                    $is_mobile = 3;
                }else if($this->is_app_browser){
                    $is_mobile = 2;
                }
                $order_param['pay_type'] = 'allinpay_mer';
                $order_param['is_mobile'] = $is_mobile;
                $order_param['order_type'] = $now_order['order_type'];
                $order_param['order_id'] = $now_order['order_id'];
                $order_param['third_id'] = $now_order['trxid'];
                $order_param['pay_money'] = $now_order['paid_money'];
                $order_param['is_own'] = intval($result_orderid[2]);
            }else{
                fdump_sql($data,'allinpay_save_exception');
                $this->error('支付存储失败，请联系管理员');
            }
            $go_query_param['order_param'] = $order_param;
            if($payment_money_order = M('Payment_money_order')->where(array('orderid'=>$go_query_param['order_param']['order_id'],'order_type'=>$go_query_param['order_param']['order_type']))->find()){
                M('Payment_money_order')->where(array('orderid'=>$go_query_param['order_param']['order_id'],'order_type'=>$go_query_param['order_param']['order_type']))->setField('paid_money',$go_query_param['order_param']['pay_money']);
                if($payment_money_order['payment_money'] > $go_query_param['order_param']['pay_money'] + 0.1){
                    fdump_sql($payment_money_order,'allinpay_check_pay_exception');
                    fdump_sql($go_query_param,'allinpay_check_pay_exception');
                    $this->error_tips('支付金额异常');
                }
            }else{
                fdump_sql($go_query_param,'allinpay_payment_order_exception');
                $this->error_tips('支付异常');
            }
            switch($go_query_param['order_param']['order_type']){
                case 'group':
                    D('Group_order')->after_pay($go_query_param['order_param']);
                    break;
                case 'meal':
                case 'takeout':
                case 'food':
                case 'foodPad':
                    D('Meal_order')->after_pay($go_query_param['order_param'], $go_query_param['order_param']['order_type']);
                    break;
                case 'weidian':
                    $pay_info = D('Weidian_order')->after_pay($go_query_param['order_param']);
                    if(($pay_info['error']==0) && isset($pay_info['url'])){  /***异步通知***/
                        $this->NoticeWDAsyn($now_order['orderid']);
                    }
                    break;
                case 'recharge':
                    D('User_recharge_order')->after_pay($go_query_param['order_param']);
                    break;
                case 'waimai':
                    D('Waimai_order')->after_pay($go_query_param['order_param']);
                    break;
                case 'appoint':

                    D('Appoint_order')->after_pay($go_query_param['order_param']);

                    break;
                case 'balance-appoint':

                    D('Appoint_order')->balance_after_pay($go_query_param['order_param']);

                    break;
                case 'wxapp':
                    D('Wxapp_order')->after_pay($go_query_param['order_param']);
                    break;
                case 'store':
                    D('Store_order')->after_pay($go_query_param['order_param']);
                    break;
                case 'shop':
                case 'mall':
                    D('Shop_order')->after_pay($go_query_param['order_param']);
                    break;
                case 'plat':
                    D('Plat_order')->after_pay($go_query_param['order_param']);
                    break;
                case 'spread':
                    D('Merchant_spread_order')->after_pay($go_query_param['order_param']);
                    break;
                case 'mcard':
                    D('Membership_card_order')->after_pay($go_query_param['order_param']);
                    break;
            }
            $url = D('Pay')->back_url($now_order['order_type'],$now_order);
            exit(json_encode(array('status'=>1,'url'=>$url,'discount_money'=>$now_order['discount_money'],'paid_money'=>$now_order['paid_money'])));
        }elseif($return['trxstatus'] == '1999'){
            exit(json_encode(array('info'=>'','status'=>'1999','url'=>'')));
        }elseif($return['trxstatus'] == '2000' || $return['trxstatus']=='2008'){
           exit(json_encode(array('info'=>'','status'=>'2000','url'=>'')));
        }else{
            fdump_sql($return,'allinpay_trxstatus_exception');
            $this->error('提示：'.$return['errmsg']);
        }
	}
	private function sendAllinpay($url,$s_data,$data){

	    $key = $s_data['key'];
        $nowtime = date("ymdHis");
        $randomstr = $nowtime.rand(10,99).sprintf("%08d",$this->user_session['uid']);
        $postData = $data;
        $postData['version'] = 11;
        $postData['randomstr'] = $randomstr;
        $postData['reqtime'] = date("YmdHis");
        $postData['reqip'] = get_client_ip(0);
		$sign = $this->SignArray($postData,$key);
		$postData['sign'] = $sign;
		import('ORG.Net.Http');
		$http = new Http();
		$return = $http->curlPostOwn($url, $postData);
		return json_decode($return,true);
	}
    /**
     * 将参数数组签名
     */
    public function SignArray(array $array,$appkey){
        $array['key'] = $appkey;// 将key放到数组中一起进行排序和组装
        ksort($array);
        $blankStr = $this->ToUrlParams($array);
        $sign = strtoupper(md5($blankStr));
        return $sign;
    }

    public function ToUrlParams(array $array)
    {
        $buff = "";
        foreach ($array as $k => $v)
        {
            if($v != "" && !is_array($v)){
                $buff .= $k . "=" . $v . "&";
            }
        }
        $buff = trim($buff, "&");
        return $buff;
    }
    //验签
    public function ValidSign(array $array,$appkey){
        $sign = $array['sign'];
        unset($array['sign']);
        $array['key'] = $appkey;
        $mySign = $this->SignArray($array, $appkey);
        return strtolower($sign) == strtolower($mySign);
    }
	
    //查询订单
    public function ajax_query(){
        $order_id = $_POST['order_id'];
        $now_order = M('Allinpay_order')->where(array('allinpay_rand_id'=>$order_id))->find();
        if($now_order){
            if($now_order['is_paid']==1){
                $url = D('Pay')->back_url($now_order['order_type'],$now_order);
                exit(json_encode(array('is_paid'=>$now_order['is_paid'],'url'=>$url,'discount_money'=>$now_order['discount_money'],'paid_money'=>$now_order['paid_money'])));
            }
            exit(json_encode(array('is_paid'=>$now_order['is_paid'])));
        }
    }
    /***异步通知*微店**/
    public function NoticeWDAsyn($order_id){
        $now_order = M('Weidian_order')->field(true)->where(array('orderid'=>trim($order_id)))->find();
        if(!empty($now_order) && ($now_order['pay_type']=='weixin' || $now_order['pay_type']=='alipay')){
            $wdAsynarr=array('order_no'=>$now_order['weidian_order_id'],'pay_money'=>$now_order['money'],'third_id'=>$now_order['third_id'],'payment_method'=>$now_order['pay_type']);
            $wdAsynarr['salt'] = C('config.weidian_sign');
            ksort($wdAsynarr);
            $wdAsynarr['sign_key'] = sha1(http_build_query($wdAsynarr));
            $wdAsynarr['request_time'] = time();
            $returnarr=httpRequest($this->config['weidian_url'].'/api/pay_notify.php','POST',$wdAsynarr);
            if(empty($returnarr['1'])){
                $returnarr=httpRequest($this->config['weidian_url'].'/api/pay_notify.php','POST',$wdAsynarr);
            }
        }
    }
    //检查优惠信息
    public function check_discount(){
        $data = $_POST;
        $now_order = M('Allinpay_order')->where(array('allinpay_rand_id'=>$data['order_id']))->find();
        $now_card = M('Allinpay')->where(array('card_id'=>$data['bind_id'],'uid'=>$this->user_session['uid']))->find();
        if($data['discount_id']==0){
            $data_discount['card_no'] = $now_card['cardNo'];
            $data_discount['cardbin'] = $now_card['cardBin'];
            $data_discount['did'] = 0;
            $data_discount['discount_money'] = 0;
            $data_discount['discount_info'] = '';
            $data_discount['discount_type'] = 0; //默认值，没有did时无效
            if(M('Allinpay_order')->where(array('allinpay_rand_id'=>$now_order['allinpay_rand_id']))->save($data_discount)){
                $this->success();
            }else{
                $this->error('出错了，请重试');
            }
        }
        $now_discount = M('Allinpay_discount')->where(array('id'=>$data['discount_id']))->find();

        if(!$now_order){
            $this->error('订单信息有误');
        }
        if(!$now_card){
            $this->error('银行卡信息有误');
        }
        if(!$now_discount){
            $this->error('优惠信息有误');
        }
        $mer_discounts = D('Allinpay_discount')->getDiscounts($now_order['mer_id']);
        if(!in_array($now_discount['id'],array_column($mer_discounts,'id'))){
            $this->error('该优惠不可享用');
        }
        if($now_discount['card_limit']==1){
            $cards_use = M('Allinpay_discount_card')->where(array('did'=>$now_discount['id']))->select();
            if(!in_array($now_card['cardBin'],array_column($cards_use,'cardbin'))){
                $this->error('该卡不可享用此优惠');
            }
        }
        if($now_discount['single_num']==0 && $now_discount['total_num']==0){ //无限次
            //next step
        }elseif($now_discount['single_num']==0 && $now_discount['total_num']>0){ //单卡无限，总额有限
            $count = M('Allinpay_order')->where(array('did'=>$now_discount['id'],'is_paid'=>1))->count();
            if($count >= $now_discount['total_num']){
                $now_discount['card_limit_num'] = -1;
                $this->error('总优惠次数已用完');
            }
        }elseif($now_discount['single_num']>0 && $now_discount['total_num']==0){ //单卡有限，总额无限
            //判断周期 0不限 1每月 2每周 3每日
            $where = array('did'=>$now_discount['id'],'is_paid'=>1,'card_no'=>$now_card['cardNo']);
            if($now_discount['cycle_type']>0){
                $times = D('Allinpay_discount')->getCycleTime($now_discount['cycle_type']);
                $where['pay_time'] = array('between',$times);
            }
            $count = M('Allinpay_order')->where($where)->count();
            if($count >= $now_discount['single_num']){
                $this->error('优惠次数已用完');
            }
        }elseif($now_discount['single_num']>0 && $now_discount['total_num']>0){ //单卡有限，总额有限
            //判断周期 0不限 1每月 2每周 3每日
            $where = array('did'=>$now_discount['id'],'is_paid'=>1,'card_no'=>$now_card['cardNo']);
            if($now_discount['cycle_type']>0){
                $times = D('Allinpay_discount')->getCycleTime($now_discount['cycle_type']);
                $where['pay_time'] = array('between',$times);
            }
            $single_count = M('Allinpay_order')->where($where)->count();
            $total_count = M('Allinpay_order')->where(array('did'=>$now_discount['id'],'is_paid'=>1))->count();
            if($total_count >= $now_discount['total_num']) {
                $this->error('总优惠次数已用完');
            }elseif($single_count >= $now_discount['single_num']){
                $this->error('优惠次数已用完');
            }
        }
        //计算优惠
        if($now_discount['reach_money'] > $now_order['order_price']){
            $this->error('未达到最低消费，不可使用该优惠');
        }
        $total_money = 0;
        if($now_discount['max_money']>0){
            $where['did'] = $now_discount['id'];
            $where['is_paid'] = 1;
            $total_money = M('Allinpay_order')->where(array($where))->sum('discount_money');
            if($total_money >= $now_discount['max_money']){
                $this->error('活动优惠已用完');
            }
        }
        $discount_money = 0;
        if($now_discount['type']==0){ //满减
            $discount_money = $now_discount['reduce_money'];
        }elseif($now_discount['type']==1){ //折扣
            $discount_price = round($now_order['order_price'] * ($now_discount['discount'] / 100),2);
            $discount_money = $now_order['order_price'] - $discount_price;
            if($now_discount['discount_max_money'] > 0 && $discount_money > $now_discount['discount_max_money']){
                $discount_money = $now_discount['discount_max_money'];
            }
        }elseif($now_discount['type']==2){ //随机立减
            $money_arr = explode(PHP_EOL,$now_discount['random_reduce']);
            $discount_money_k = array_rand($money_arr,1);
            $discount_money = round(floatval($money_arr[$discount_money_k]),2);
        }
        if($discount_money > 0 && $now_discount['max_money']>0){
            if($discount_money > ($now_discount['max_money'] - $total_money)){
                $this->error('活动优惠已用完');
            }
        }
        $data_discount['card_no'] = $now_card['cardNo'];
        $data_discount['cardbin'] = $now_card['cardBin'];
        $data_discount['did'] = $now_discount['id'];
        $data_discount['discount_money'] = $discount_money;
        $data_discount['discount_type'] = $now_discount['type'];

        $discount_info['reach_money'] = $now_discount['reach_money'];
        $discount_info['discount_money'] = strval($discount_money);
        if($now_discount['type']==0){
            $discount_info['reduce_money'] = $now_discount['reduce_money'];
        }elseif($now_discount['type']==1){
            $discount_info['discount'] = $now_discount['discount'];
            $discount_info['discount_max_money'] = $now_discount['discount_max_money'];
        }
        $data_discount['discount_info'] = json_encode($discount_info);
        if(M('Allinpay_order')->where(array('allinpay_rand_id'=>$now_order['allinpay_rand_id']))->save($data_discount)){
            $this->success();
        }else{
            $this->error('出错了，请重试');
        }
    }
    //查询优惠信息
    public function query_discount(){
        $data = $_POST;
        $now_order = M('Allinpay_order')->where(array('allinpay_rand_id'=>$data['order_id']))->find();
        $now_card = M('Allinpay')->where(array('card_id'=>$data['bind_id'],'uid'=>$this->user_session['uid']))->find();
        if($data['discount_id']==0){
            $this->error('出错了，请重试');
        }
        $now_discount = M('Allinpay_discount')->where(array('id'=>$data['discount_id']))->find();

        if(!$now_order){
            $this->error('订单信息有误');
        }
        if(!$now_card){
            $this->error('银行卡信息有误');
        }
        if(!$now_discount){
            $this->error('优惠信息有误');
        }
        $mer_discounts = D('Allinpay_discount')->getDiscounts($now_order['mer_id']);
        if(!in_array($now_discount['id'],array_column($mer_discounts,'id'))){
            $this->error('该优惠不可享用');
        }
        if($now_discount['card_limit']==1){
            $cards_use = M('Allinpay_discount_card')->where(array('did'=>$now_discount['id']))->select();
            if(!in_array($now_card['cardBin'],array_column($cards_use,'cardbin'))){
                $this->error('该卡不可享用此优惠');
            }
        }
        if($now_discount['single_num']==0 && $now_discount['total_num']==0){ //无限次
            //next step
        }elseif($now_discount['single_num']==0 && $now_discount['total_num']>0){ //单卡无限，总额有限
            $count = M('Allinpay_order')->where(array('did'=>$now_discount['id'],'is_paid'=>1))->count();
            if($count >= $now_discount['total_num']){
                $now_discount['card_limit_num'] = -1;
                $this->error('总优惠次数已用完');
            }
        }elseif($now_discount['single_num']>0 && $now_discount['total_num']==0){ //单卡有限，总额无限
            //判断周期 0不限 1每月 2每周 3每日
            $where = array('did'=>$now_discount['id'],'is_paid'=>1,'card_no'=>$now_card['cardNo']);
            if($now_discount['cycle_type']>0){
                $times = D('Allinpay_discount')->getCycleTime($now_discount['cycle_type']);
                $where['pay_time'] = array('between',$times);
            }
            $count = M('Allinpay_order')->where($where)->count();
            if($count >= $now_discount['single_num']){
                $this->error('优惠次数已用完');
            }
        }elseif($now_discount['single_num']>0 && $now_discount['total_num']>0){ //单卡有限，总额有限
            //判断周期 0不限 1每月 2每周 3每日
            $where = array('did'=>$now_discount['id'],'is_paid'=>1,'card_no'=>$now_card['cardNo']);
            if($now_discount['cycle_type']>0){
                $times = D('Allinpay_discount')->getCycleTime($now_discount['cycle_type']);
                $where['pay_time'] = array('between',$times);
            }
            $single_count = M('Allinpay_order')->where($where)->count();
            $total_count = M('Allinpay_order')->where(array('did'=>$now_discount['id'],'is_paid'=>1))->count();
            if($total_count >= $now_discount['total_num']) {
                $this->error('总优惠次数已用完');
            }elseif($single_count >= $now_discount['single_num']){
                $this->error('优惠次数已用完');
            }
        }
        //计算优惠
        if($now_discount['reach_money'] > $now_order['order_price']){
            $this->error('未达到最低消费，不可使用该优惠');
        }
        $total_money = 0;
        if($now_discount['max_money']>0){
            $where['did'] = $now_discount['id'];
            $where['is_paid'] = 1;
            $total_money = M('Allinpay_order')->where(array($where))->sum('discount_money');
            if($total_money >= $now_discount['max_money']){
                $this->error('活动优惠已用完');
            }
        }
        $discount_money = 0;
        if($now_discount['type']==0){ //满减
            $discount_money = $now_discount['reduce_money'];
        }elseif($now_discount['type']==1){ //折扣
            $discount_price = round($now_order['order_price'] * ($now_discount['discount'] / 100),2);
            $discount_money = $now_order['order_price'] - $discount_price;
            $discount_status = 'normal';
            if($now_discount['discount_max_money'] > 0 && $discount_money > $now_discount['discount_max_money']){
                $discount_money = $now_discount['discount_max_money'];
                $discount_status = 'max';
            }
        }elseif($now_discount['type']==2){ //随机立减
            $money_arr = explode(PHP_EOL,$now_discount['random_reduce']);
            $max_money = max($money_arr);
            $min_money = min($money_arr);
        }
        if($discount_money > 0 && $now_discount['max_money']>0){
            if($discount_money > ($now_discount['max_money'] - $total_money)){
                $this->error('活动优惠已用完');
            }
        }
        $paid_money = $now_order['order_price'] - $discount_money;
        if($paid_money < 0) $paid_money = 0;
        if($now_discount['type']==0){
            exit(json_encode(array('status'=>1,'discount_type'=>$now_discount['type'],'discount_money'=>strval($discount_money),'paid_money'=>strval($paid_money))));
        }elseif($now_discount['type']==1){
            exit(json_encode(array('status'=>1,'discount_type'=>$now_discount['type'],'discount_money'=>strval($discount_money),'paid_money'=>strval($paid_money),'discount_status'=>$discount_status)));
        }elseif($now_discount['type']==2){
            exit(json_encode(array('status'=>1,'discount_type'=>$now_discount['type'],'paid_money'=>strval($paid_money),'max_money'=>strval($max_money),'min_money'=>strval($min_money))));
        }else{
            $this->error('出错了');
        }
    }
}
?>