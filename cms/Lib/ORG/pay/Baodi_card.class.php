<?php
class Baodi_card{
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
		if(empty($this->pay_config['pay_baodi_card_open'])){
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
		
		$orderid = str_replace('51', '15', $nowtime.rand(10,99).sprintf("%08d",$this->user_info['uid']));
		
		$data_order = array(
			'uid' => $this->user_info['uid'],
			'order_name' => $this->order_info['order_name'] ? $this->order_info['order_name'] : L_('在线订单支付'),
			'order_type' => $this->order_info['order_type'],
			'order_id' => $this->order_info['order_id'],
			'orderid' => $orderid,
			'order_money' => $this->pay_money,
			'add_time' => time(),
		);
		
		//得到订单的支付来源
		$payment_money_order = M('Payment_money_order')->where(array('order_type'=>$this->order_info['order_type'],'orderid'=>$this->order_info['order_id']))->find();
		if($payment_money_order){
			fdump($payment_money_order,'payment_money_order');
			$data_order['is_mobile_pay'] = $payment_money_order['is_mobile_pay'];
		}
		
		if(!M('Baodi_credit_order')->data($data_order)->add()){
			return array('error'=>1,'msg'=>L_('订单生成失败，请重试'));
		}
		fdump(M('Baodi_credit_order')->getLastSql(),'Baodi_credit_order');
		
		$param = array(
			'device_id' => $from_device_id,
			'ticket' => $from_ticket,
			'now_lang' => C('config.system_lang'),
			'from' => 'pay',
			'orderid' => $orderid,
		);
		
		redirect(C('config.site_url').'/packapp/baodi_card_pay/index.html?'.http_build_query($param));
	}

	public function notice_url(){
		exit('success');
	}
	public function return_url(){
		if(empty($this->pay_config['pay_baodi_card_open'])){
			return array('error'=>1,'msg'=>L_('支付方式的配置项缺失，无法调用'));
		}
		
		$now_order = M('Baodi_credit_order')->where(array('orderid'=>$_GET['orderid']))->find();
		
		if(empty($now_order)){
			return array('error'=>1,'msg'=>L_('没有找到该订单'));
		}
		
		$payment_money_order = M('Payment_money_order')->where(array('order_type'=>$now_order['order_type'],'orderid'=>$now_order['order_id']))->find();
		
		fdump($_POST,'baodi_return_url_post',true);

		if(!$now_order['pay_time']){
			return array('error'=>1,'msg'=>L_('该订单还未支付'));
		}
		
		$order_param['pay_type'] = 'baodi_card';
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
		$nowOrder = M('Baodi_credit_order')->where(array('order_type'=>$this->order_info['order_type'],'order_id'=>$this->order_info['orderid']))->find();
        //修复退款pigcms_shop_order存储的orderid和pigcms_payment_money_order表orderid不一致问题，导致信用卡退款找不到订单，退款失败
        if (empty($nowOrder) && in_array($this->order_info['order_type'], ['shop', 'mall'])) {
            $payment_money_order = M('Payment_money_order')->where(['order_id' => $this->order_info['order_id'], 'order_type' => 'shop', 'pay_type' => $this->order_info['pay_type'], 'paid_money' => ['gt', 0]])->find();
            if ($payment_money_order) {
                $nowOrder = M('Baodi_credit_order')->where(['order_type' => $this->order_info['order_type'], 'order_id' => $payment_money_order['orderid']])->find();
            }
        }
		
		$refund_param['refund_id'] = $nowOrder['orderid'];
		$refund_param['refund_time'] = time();
		$refund_param['refund_more'] = $nowOrder['more_response'];
		
		//获取会员卡信息
		import('@.ORG.third_custom.baodi.baodiCard');
		$baodiCard = new baodiCard();
		
		$return = $baodiCard->transCardBalance($nowOrder['profile_id'], $nowOrder['order_money'], $nowOrder['order_name']);
		if($return['MessageNo'] == '-1'){
			return array('error'=>1,'type'=>'fail','msg' => $return['Message']);
		}else if($return['MessageNo'] == '1'){
			return array('error'=>0,'type'=>'ok','msg'=>L_('退款申请成功！已经增加您的会员卡余额。'),'refund_param'=>$refund_param);
		}else{
			return array('error'=>1,'type'=>'fail','msg' => '退款申请失败！如果重试多次还是失败请联系系统管理员。');
		}
	}
}
?>