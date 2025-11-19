<?php
/**
 * 四川省农村信用社支付接口对接
 * 【四川省农村信用社支付接口对接】https://www.tapd.cn/47145533/prong/stories/view/1147145533001008038
 */

class Scrcu_cash{
	protected $gateway;
	protected $order_info;
	protected $pay_money;
	protected $pay_type;
	protected $is_mobile;
	protected $pay_config;
	protected $user_info;
	
	public function __construct($order_info=array(),$pay_money=0,$pay_type='alipay',$pay_config=array(),$user_info=array(),$is_mobile=0){
		
		$this->gateway = C("config.scrcu_java_gateway");
		$this->order_info = $order_info;
		$this->pay_money  = $pay_money;
		$this->pay_type   = $pay_type;
		$this->is_mobile  = $is_mobile;
		$this->pay_config = $pay_config;
		$this->user_info  = $user_info;
	}

	//收银台支付
	public function pay(){
		import('ORG.Net.Http');
		$post = [
			'orderNumber' => $this->order_info['order_id'],
			'orderSendTime' => date('YmdHis'),
			'orderAmt' => getFormatNumber($this->pay_money*100),
			'subject' => $this->order_info['order_name'],
			'merId' => C("config.scrcu_mid"),
			'openId' => $this->is_mobile == 3 ? $this->user_info['wxapp_openid'] : $this->user_info['openid'],
			'frontEndUrl' => C('config.site_url').'/index.php?c=LowFeePayNotice&a=scrcu_back_notice&order_type='.$this->order_info['order_type'].'&order_id='.$this->order_info['order_id'],
			'subOrderAmt' => getFormatNumber($this->pay_money*100),
			'paymentWay' => '01',
			'mobileWay' => $this->is_mobile == 2 ? '01' : ($this->is_mobile == 3 ? '04' : '03'),
		];


		if($this->order_info['scrcu_mer_no']){
			$post['subMerId'] = $this->order_info['scrcu_mer_no'];
			if (in_array($this->order_info['order_type'], ['house_village_pay', 'house_village_express', 'house_village_pay_cashier'])) {
				$is_own = 5;
			}else{
				$is_own = 1;
			}
			$is_own = 1;
		}else{
			$post['subMerId'] = C("config.scrcu_mid");
			$is_own = 0;
		}

		if($this->is_mobile == 3){// 小程序

			$post['wxAppId'] = C('config.pay_wxapp_appid');
			$post['wxAppUrl'] = '/pages/pay/thirdWeixinPay';
		}
		$result = Http::curlPostOwn($this->gateway."/api/prePay", $post);
		$res = json_decode($result, true);
		if($res['error'] != '0'){
			fdump_sql([$post,$res], "Scrcu_cash_error");
			return [
				'error' => '1',
				'msg' => $res['msg'] ? $res['msg'] : L_('支付失败')
			];
		}
		else{
			$pay_config = [
				'scrcu_mid' => C("config.scrcu_mid"),
				'scrcu_mer_no' => $this->order_info['scrcu_mer_no'],
			];
			M('Payment_money_order')->where(array('orderid'=>$this->order_info['order_id'],'order_type'=>$this->order_info['order_type']))->data(array('pay_method'=>'scrcu', 'orderNo'=>$this->order_info['order_id'],'is_own'=>$is_own, 'pay_config' => json_encode($pay_config)))->save();//保存支付通道，这样可以后期做退款
            if ($this->order_info['order_type'] == 'shop') {
                D('Shop_order')->where(['orderid' => $this->order_info['order_id']])->save(['is_own' => $is_own]);
            }
            $record = [
				'order_number' => $this->order_info['order_id'],
				'order_type' => $this->order_info['order_type'],
				'limit' => 0,
				'over' => 0,
				'add_time' => time()
			];
			M("Scrcu_record")->data($record)->add();//支付记录，保险起见做主动查询支付结果的。
			return [
				'error' => '0',
				'url' => $res['data']
			];
		}
	}
}