<?php
class Merchantwarrior{
	protected $order_info;
	protected $pay_money;
	protected $pay_type;
	protected $is_mobile;
	protected $pay_config;
	protected $user_info;
	
	public function __construct($order_info,$pay_money,$pay_type,$pay_config,$user_info,$is_mobile=0){
		$this->order_info = $order_info;
		$this->pay_money  = $pay_money;
		$this->pay_type   = $pay_type;
		$this->is_mobile   = $is_mobile;
		$this->pay_config = $pay_config;
		$this->user_info  = $user_info;
	}
	public function pay(){
		if(empty($this->pay_config['pay_merchantwarrior_payapi']) || empty($this->pay_config['pay_merchantwarrior_merchantUUID']) || empty($this->pay_config['pay_merchantwarrior_apiKey']) || empty($this->pay_config['pay_merchantwarrior_passphrase'])){
			return array('error'=>1,'msg'=>L_('支付方式的配置项缺失，无法调用'));
		}
		return $this->mobile_pay();
	}
	public function mobile_pay(){
		$from_device_id = $_SESSION['user']['from_device_id'];
		$from_ticket = $_SESSION['user']['from_ticket'];
		if(!$from_device_id || !$from_ticket){
			$from_device_id = 'packapp';
			$ticket_arr = ticket::create($this->user_info['uid'], $from_device_id, true);
			$from_ticket = $ticket_arr['ticket'];
		}
		
		// dump($this->order_info);die;
		
		$nowtime = date("ymdHis");
		$orderid = str_replace('51', '15', $nowtime.rand(10,99).sprintf("%08d",$this->user_info['uid']));
		
		// dump($this->order_info);die;
		
		$data_order = array(
			'uid' => $this->user_info['uid'],
			'tmp_id' => $orderid,
			'order_name' => $this->order_info['order_name'] ? $this->order_info['order_name'] : L_('在线订单支付'),
			'order_type' => $this->order_info['order_type'],
			'order_id' => $this->order_info['order_id'],
			'order_money' => $this->pay_money,
			'add_time' => time(),
		);
		
		//得到订单的支付来源
		$payment_money_order = M('Payment_money_order')->where(array('order_type'=>$this->order_info['order_type'],'orderid'=>$this->order_info['order_id']))->find();
		if($payment_money_order){
			fdump($payment_money_order,'payment_money_order');
			$data_order['is_mobile_pay'] = $payment_money_order['is_mobile_pay'];
		}
		
		if(!M('Hake_credit_order')->data($data_order)->add()){
			return array('error'=>1,'msg'=>L_('订单生成失败，请重试'));
		}
		fdump(M('Hake_credit_order')->getLastSql(),'Hake_credit_order');
		
		$param = array(
			'device_id' => $from_device_id,
			'ticket' => $from_ticket,
			'now_lang' => C('config.system_lang'),
			'from' => 'pay',
			'orderid' => $orderid,
		);
		
		redirect(C('config.site_url').'/packapp/hake_credit_pay/index.html?'.http_build_query($param));
	}

	public function notice_url(){
		exit('success');
	}
	public function return_url(){
		if(empty($this->pay_config['pay_merchantwarrior_payapi']) || empty($this->pay_config['pay_merchantwarrior_merchantUUID']) || empty($this->pay_config['pay_merchantwarrior_apiKey']) || empty($this->pay_config['pay_merchantwarrior_passphrase'])){
			return array('error'=>1,'msg'=>L_('支付方式的配置项缺失，无法调用'));
		}
		
		$now_order = M('Hake_credit_order')->where(array('tmp_id'=>$_GET['orderid']))->find();
		if(empty($now_order) && $_GET['transactionReferenceID']){
			$tmp_order_arr = explode('_', $_GET['transactionReferenceID']);
			$now_order = M('Hake_credit_order')->where(array('order_type'=>$tmp_order_arr[0],'order_id'=>$tmp_order_arr[1]))->order('`id` DESC')->find();
		}
		
		if(empty($now_order)){
			return array('error'=>1,'msg'=>L_('没有找到该订单'));
		}
		
		$payment_money_order = M('Payment_money_order')->where(array('order_type'=>$now_order['order_type'],'orderid'=>$now_order['order_id']))->find();
		
		fdump($_POST,'mw_return_url_post',true);
		
		//跳转支付处理
		if(isset($_GET['status']) && isset($_GET['message'])){
			fdump($_GET,'mw_return_url_get',true);
			//出错了
			if($_GET['status'] == 'approved'){
				$query_order = array(
					'transactionReferenceID' => $now_order['order_type'].'_'.$now_order['order_id'],
				);
				
				$hake_credit = new hake_credit();
				$result = $hake_credit->queryCard($query_order);
				
				if(isset($result['more_response']['responseCode']) && $result['more_response']['responseCode'] == 0){
					$data_order = array(
						'third_order_id' => $result['more_response']['transactionID'],
						'more_response'	 => serialize($result['more_response']),
						'pay_time' 		 => time(),
					);
					if(M('Hake_credit_order')->where(array('id'=>$now_order['id']))->data($data_order)->save() !== false){
						//将卡信息绑到卡上。
						if($now_order['credit_id'] && $_GET['cardID'] && $_GET['cardKey'] && $_GET['ivrCardID']){
							$card_save = array(
								'card_from'		=> 'warrior',
								'card_id'		=> $_GET['cardID'],
								'card_key'		=> $_GET['cardKey'],
								'ivr_card_id'	=> $_GET['ivrCardID'],
							);
							M('Hake_credit_list')->where(array('id'=>$now_order['credit_id']))->data($card_save)->save();
						}
						
						$now_order = array_merge($now_order,$data_order);
					}else{
						fdump_sql(array('sql'=>M('Hake_credit_order')->getLastSql(),'_GET'=>$_GET,'data_order'=>$data_order),'Hake_order_pay_return_error');
						$_GET['status']  = 'error';
						$_GET['code']	 = '-999';
						$_GET['message'] = L_('订单保存失败');
					}
				}
			}
			
			if($_GET['status'] != 'approved'){
				$from_device_id = $_SESSION['user']['from_device_id'];
				$from_ticket = $_SESSION['user']['from_ticket'];
				if(!$from_device_id || !$from_ticket){
					$from_device_id = 'packapp';
					$ticket_arr = ticket::create($now_order['uid'], $from_device_id, true);
					$from_ticket = $ticket_arr['ticket'];
				}
				$param = array(
					'device_id' => $from_device_id,
					'ticket' 	=> $from_ticket,
					'now_lang' 	=> C('config.system_lang'),
					'from' 		=> 'pay',
					'orderid' 	=> $orderid,
					'code' 	=> isset($_GET['code']) ? $_GET['code'] : '-1',
					'message' 	=> $_GET['message'],
				);
				redirect(C('config.site_url').'/packapp/hake_credit_pay/paymentError.html?'.http_build_query($param));
			}
		}
		
		if(!$now_order['pay_time']){
			return array('error'=>1,'msg'=>L_('该订单还未支付'));
		}
		
		$order_param['pay_type'] = 'merchantwarrior';
		$order_param['is_mobile'] = $payment_money_order ? $payment_money_order['is_mobile_pay'] : $this->is_mobile;
		$order_param['order_type'] = $now_order['order_type'];
		$order_param['order_id'] = $now_order['order_id'];
		$order_param['is_own'] = 0;
		$order_param['third_id'] = $now_order['third_order_id'];
		$order_param['pay_money'] = $now_order['order_money'];
		return array('error'=>0,'order_param'=>$order_param);
	}
	/*查找银行卡订单信息*/
	public function query_order(){
		
	}

    /* 退款 */
    public function refund(){
		$now_order = M('Hake_credit_order')->where(array('order_type'=>$this->order_info['order_type'],'order_id'=>$this->order_info['orderid']))->find();
        //修复哈客退款pigcms_shop_order存储的orderid和pigcms_payment_money_order表orderid不一致问题，导致信用卡退款找不到订单，退款失败
        if (empty($now_order) && in_array($this->order_info['order_type'], ['shop', 'mall'])) {
            $payment_money_order = M('Payment_money_order')->where(['order_id' => $this->order_info['order_id'], 'order_type' => 'shop', 'pay_type' => $this->order_info['pay_type'], 'paid_money' => ['gt', 0]])->find();
            if ($payment_money_order) {
                $now_order = M('Hake_credit_order')->where(['order_type' => $this->order_info['order_type'], 'order_id' => $payment_money_order['orderid']])->find();
            }
        }

		
		$transactionAmount = $this->order_info['payment_money'];
		$transactionReferenceID = 'refund_'.$now_order['order_type'].'_'.$now_order['order_id'].'_'.mt_rand(10,99);
		$transactionID = $now_order['third_order_id'];
		$refundAmount = $this->pay_money;
		$refund_order = array(
			'transactionAmount' => $transactionAmount,
			'transactionCurrency' => 'AUD',
			'transactionReferenceID' => $transactionReferenceID,
			'transactionID' => $transactionID,
			'refundAmount' => number_format($refundAmount, 2, ".", ""),
		);
		
		$hake_credit = new hake_credit();
		$result = $hake_credit->refundCard($refund_order);
		
		if($result['err_code']){
			return array('error'=>1,'type'=>'fail','msg'=>L_('退款申请失败！如果重试多次还是失败请联系系统管理员。').($result['err_msg']?'<br>原因：'.$result['err_msg']:''));
		}
		
		$refund_param['refund_id'] = $result['transactionID'];
		$refund_param['refund_time'] = time();
		$refund_param['refund_more'] = $result['more_response'];
		return array('error'=>0,'type'=>'ok','msg'=>L_('退款申请成功！请注意查收“微信支付”给您发的退款通知。'),'refund_param'=>$refund_param);
	}
}
?>