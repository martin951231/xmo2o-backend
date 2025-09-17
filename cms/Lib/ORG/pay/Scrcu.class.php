<?php
/**
 * 四川省农村信用社支付接口对接
 * 【四川省农村信用社支付接口对接】https://www.tapd.cn/47145533/prong/stories/view/1147145533001008038
 * https://shimowendang.com/docs/VMAPV7GEePSvnXqg
 */

class Scrcu{
	protected $gateway;
	
	public function __construct(){
		$this->gateway = C("config.scrcu_java_gateway");
	}

	//微信公众号支付(微信支付)
	public function wechatPay($order_type, $order_no, $money, $order_desc, $openId, $scrcu_mer_no){
		import('ORG.Net.Http');
		$post = [
			'orderNumber' => $order_no,
			'orderSendTime' => date('YmdHis'),
			'orderAmt' => getFormatNumber($money*100),
			'subject' => $order_desc,
			'merId' => C("config.scrcu_mid"),
			'openId' => $openId,
			'frontEndUrl' => C('config.site_url').'/index.php?c=LowFeePayNotice&a=scrcu_back_notice&order_type='.$order_type.'&order_id='.$order_no,
			'subOrderAmt' => getFormatNumber($money*100),
			'paymentWay' => '03'
		];

		if($scrcu_mer_no){
			$post['subMerId'] = $scrcu_mer_no;
			if (in_array($order_type, ['house_village_pay', 'house_village_express', 'house_village_pay_cashier'])) {
				$is_own = 5;
			}else{
				$is_own = 1;
			}
		}else{
			$post['subMerId'] = C("config.scrcu_mid");
			$is_own = 0;
		}

		$result = Http::curlPostOwn($this->gateway."/api/prePay", $post);
		$res = json_decode($result, true);
		if($res['error'] != '0'){
			fdump_sql([$post,$res], "scrcu_wechatPay_error");
			return [
				'error' => '1',
				'msg' => $res['msg'] ? $res['msg'] : L_('支付失败')
			];
		}
		else{
			M('Payment_money_order')->where(array('orderid'=>$order_no,'order_type'=>$order_type))->data(array('pay_method'=>'scrcu','orderNo'=>$order_no,'is_own'=>$is_own))->save();//保存支付通道，这样可以后期做退款
			$record = [
				'order_number' => $order_no,
				'order_type' => $order_type,
				'limit' => 0,
				'over' => 0,
				'add_time' => time()
			];
			M("Scrcu_record")->data($record)->add();//支付记录，保险起见做主动查询支付结果的。
			return [
				'error' => '0',
				'redirectUrl' => $res['data']
			];
		}
	}

	//微信小程序支付(还没通哦,最好对照着上面的方法重写一下)
	public function wechatminiPay($order_type, $order_no, $money, $order_desc, $openId, $scrcu_mer_no){
		import('ORG.Net.Http');

		$post = [
			'orderNumber' => $order_no,
			'orderSendTime' => date('YmdHis'),
			'orderAmt' => getFormatNumber($money*100),
			'subOrderAmt' => getFormatNumber($money*100),
			'subject' => $order_desc,
			'miniPath' => "/pages/pay/thirdWeixinPay",//这里替换成小程序的路径地址
			'openId' => $openId,
			'merId' => C("config.scrcu_mid"),
			'wxAppId' => C("config.pay_wxapp_appid"),
			'frontEndUrl' => C('config.site_url').'/index.php?c=LowFeePayNotice&a=scrcu_back_notice&order_type='.$order_type.'&order_id='.$order_no,
		];


		if($scrcu_mer_no){
			$post['subMerId'] = $scrcu_mer_no;
			if (in_array($order_type, ['house_village_pay', 'house_village_express', 'house_village_pay_cashier'])) {
				$is_own = 5;
			}else{
				$is_own = 1;
			}
		}else{
			$post['subMerId'] = C("config.scrcu_mid");
			$is_own = 0;
		}

		$result = Http::curlPostOwn($this->gateway."/api/wechatMiniPay", $post);
		$res = json_decode($result, true);
		fdump([$post,$res], "scrcu_miniPay", 1);
		if($res['error'] != '0'){
			fdump_sql([$post,$res], "scrcu_miniPay_error");
			return [
				'error' => '1',
				'msg' => $res['msg'] ? $res['msg'] : L_('支付失败')
			];
		}
		else{
			M('Payment_money_order')->where(array('orderid'=>$order_no,'order_type'=>$order_type))->data(array('pay_method'=>'scrcu','orderNo'=>$order_no,'is_own'=>$is_own))->save();//保存支付通道，这样可以后期做退款
			$record = [
				'order_number' => $order_no,
				'order_type' => $order_type,
				'limit' => 0,
				'over' => 0,
				'add_time' => time()
			];
			M("Scrcu_record")->data($record)->add();//支付记录，保险起见做主动查询支付结果的。
			return [
				'error' => '0',
				'redirectUrl' => $res['data']
			];
		}
	}



	//app支付(微信支付)
	public function appPay($order_type, $order_no, $money, $order_desc, $scrcu_mer_no, $mobile_type){
		import('ORG.Net.Http');
		$post = [
			'orderNumber' => $order_no,
			'orderSendTime' => date('YmdHis'),
			'orderAmt' => getFormatNumber($money*100),
			'subject' => $order_desc,
			'merId' => C("config.scrcu_mid"),
			'subOrderAmt' => getFormatNumber($money*100),
			'mobileType' => $mobile_type,			
			'frontEndUrl' => C('config.site_url').'/index.php?c=LowFeePayNotice&a=scrcu_back_notice&order_type='.$order_type.'&order_id='.$order_no,
		];

		if($scrcu_mer_no){
			$post['subMerId'] = $scrcu_mer_no;
			if (in_array($order_type, ['house_village_pay', 'house_village_express', 'house_village_pay_cashier'])) {
				$is_own = 5;
			}else{
				$is_own = 1;
			}
		}else{
			$post['subMerId'] = C("config.scrcu_mid");
			$is_own = 0;
		}
		$result = Http::curlPostOwn($this->gateway."/api/appPay", $post);
		$res = json_decode($result, true);
		if($res['error'] != '0'){
			fdump([json_encode($post),json_encode($res)], "scrcu_appPay_error", 1);
			fdump_sql([$post,$res], "scrcu_appPay_error");
			return [
				'error' => '1',
				'msg' => $res['msg'] ? $res['msg'] : L_('支付失败')
			];
		}
		else{
			M('Payment_money_order')->where(array('orderid'=>$order_no,'order_type'=>$order_type))->data(array('pay_method'=>'scrcu','orderNo'=>$order_no,'is_own'=>$is_own))->save();//保存支付通道，这样可以后期做退款
			$record = [
				'order_number' => $order_no,
				'order_type' => $order_type,
				'limit' => 0,
				'over' => 0,
				'add_time' => time()
			];
			M("Scrcu_record")->data($record)->add();//支付记录，保险起见做主动查询支付结果的。
			return [
				'error' => '0',
				'redirectUrl' => $res['data']
			];
		}
	}


	/*
	  * 扫用户付款码
	  * @param string $orderType 订单类型
	  * @param string $order_no 订单号
	  * @param string $money 订单金额
	  * @param string $code 用户付款码
	  * @param string $scrcu_mer_no 下挂商户号
	  * @return array
	*/
	public function scanPay($order_type, $order_no, $money, $code, $scrcu_mer_no){
		import('ORG.Net.Http');
		$time = date('YmdHis');
		$post = [
			'orderNumber' => $order_no,
			'orderSendTime' => $time,
			'orderAmt' => getFormatNumber($money),
			'code' => $code,
		];

		if($scrcu_mer_no){
			$post['merId'] = $scrcu_mer_no;
		}else{
			$post['merId'] = C("config.scrcu_mid");
		}

        $key = 'scrcupay_'.$order_no;
        $time = S($key, $time);
        
		$result = Http::curlPostOwn($this->gateway."/api/scanCode", $post);
		$res = json_decode($result, true);
		fdump_sql([$post,$res], "scrcu_scanPay");
		if($res['error'] != '0'){
			return [
				'error' => '1',
				'msg' => $res['msg'] ? $res['msg'] : L_('支付失败')
			];
		}
		else{
			return [
				'error' => '0',
				'transaction_id' => $res['data']
			];
		}
	}

	/*
	  * 扫用户付款码订单查询
	  * @param string $order_no 订单号
	  * @param string $scrcu_mer_no 下挂商户号
	  * @return array
	*/

	public function scanQuery($order_no, $scrcu_mer_no){
		import('ORG.Net.Http');
        $key = 'scrcupay_'.$order_no;
        $time = S($key);
		$post = [
			'orderNumber' => date("YmdHis") . mt_rand(10000000, 99999999),
			'oriOrderNumber' => $order_no,
			'orderSendTime' => date('YmdHis'),
			'oriOrderSendTime' => $time,
		];

		if($scrcu_mer_no){
			$post['merId'] = $scrcu_mer_no;
		}else{
			$post['merId'] = C("config.scrcu_mid");
		}
		$result = Http::curlPostOwn($this->gateway."/api/scanQuery", $post);

		fdump([$post,$result],'scanQuery',1);
		$res = json_decode($result, true);
	
		if($res['error'] == '0'){
			$order_param['pay_type'] = 'weixin';
			$order_param['is_mobile'] = 1;
			$order_param['order_id'] = $order_no;
			$order_param['is_own'] = 1;
			$order_param['third_id'] = "";
			$order_param['sub_mch_id'] = '';
			$order_param['error'] = 0;
			return $order_param;			
		}
		else{
			return array('error'=>1, 'msg' => $res['msg']);
		}
	}

	public function query($order){
		$orderNo = explode('_', $order['orderNo']);
		$payment_where = ['orderid'=>$orderNo[1]];
		$payment_money_order = M('Payment_money_order')->where($payment_where)->find();
		import('ORG.Net.Http');
		$post = [
			'orderNumber' => $orderNo[1],
			'merId' => C("config.scrcu_mid")
		];
		$result = Http::curlPostOwn($this->gateway."/api/query", $post);
		fdump_sql(['order' => $order, 'url' => $this->gateway."/api/query", 'post' => $post, 'result' => $result],'orgPyaScrcuQuery');

		$res = json_decode($result, true);
		$save = [
			'paid_money' => $payment_money_order['payment_money'],
		];
		M('Payment_money_order')->where(['id'=>$payment_money_order['id']])->data($save)->save();
		if($res['error'] == '0'){
			$order_param['pay_type'] = $payment_money_order['pay_type'];
			$order_param['is_mobile'] = $payment_money_order['is_mobile_pay'];
			$order_param['order_type'] = $payment_money_order['order_type'];
			$order_param['order_id'] = $orderNo[1];
			$order_param['is_own'] = $payment_money_order['is_own'];
			$order_param['third_id'] = "";
			$order_param['pay_money'] = $payment_money_order['payment_money'];
			$order_param['sub_mch_id'] = '';
			$order_param['error'] = 0;
			return $order_param;			
		}
		else{
			return array('error'=>1, 'msg' => $res['msg']);
		}
	}

	public function refund($order_no, $refund_money, $scrcu_mer_no){
		// $payment_where = ['orderid'=>$order_no];
		// $payment_money_order = M('Payment_money_order')->where($payment_where)->find();
		import('ORG.Net.Http');
        $refund_no = date("YmdHis").rand(1000,9999);
		$post = [
			'orderNumber' => "refund".$refund_no,
			'oriOrderNumber' => $order_no,
			'orderSendTime' => date('YmdHis'),
			'orderAmt' => getFormatNumber($refund_money*100),
			'merId' => C("config.scrcu_mid"),
			'oriSubOrderNumber' => $order_no.'02'
		];

		if($scrcu_mer_no){
			$post['merNo'] = $scrcu_mer_no;
		}else{
			$post['merNo'] = C("config.scrcu_mid");
		}
		fdump($post,'scrcu_refund',1);
		$result = Http::curlPostOwn($this->gateway."/api/refund", $post);
		// $result = Http::curlPostOwn($this->gateway."/api/scanRefund", $post);
		fdump($result,'scrcu_refund',1);

		$res = json_decode($result, true);
		if($res['error'] == '0'){
			$refund_param['refund_id'] = "refund".$order_no;
			$refund_param['refund_time'] = time();
			return array('error'=>0,'type'=>'ok','msg'=>'退款申请成功！请注意查收退款通知。','refund_param'=>$refund_param);		
		}
		else{
			return array('error'=>1, 'msg' => $res['msg']);
		}
	}
}