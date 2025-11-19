<?php

class Wftpay{
	private $wft_store_id;
	private $wft_pay_key;
	private $wft_pay_prikey;
	private $wft_pay_pubkey;
	private $weixin_page_back_url;
	private $alipay_page_back_url;
	private $order_info;
	public function __construct(){
		$this->wft_store_id = C('config.wft_store_id');
		$this->wft_pay_key = C('config.wft_pay_key');
		$this->wft_pay_prikey = C('config.wft_pay_prikey');
		$this->wft_pay_pubkey = C('config.wft_pay_pubkey');
		$this->page_back_url = C('config.site_url').'/source/wftpay_page_notice.php';
	}


	public function __set($property, $value) {
		$this->$property = $value;
	}

	public function notice($sign_data){
		
		// fdump($sign_data,'sign_data');
		// fdump($_GET,'sign_data',true);
		
		$array_data = json_decode(json_encode(simplexml_load_string($sign_data, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		
		$tmpData = $array_data;
		unset($tmpData['sign']);
		$sign = $this->getSign($tmpData);//本地签名
		
		if ($array_data['sign'] == $sign) {
			$arr['sign_result'] = true;
			$order_id_arr = explode('_',$array_data['out_trade_no']);
			
			$payment_money_order = M('Payment_money_order')->where(array('orderid'=>$order_id_arr[1],'order_type'=>$order_id_arr[0]))->find();

			if($payment_money_order['pay_rate']>0){
				$array_data['total_fee'] = getFormatNumber($array_data['total_fee']/$payment_money_order['pay_rate']);
			}
			
			M('Payment_money_order')->where(array('id'=>$payment_money_order['id']))->data(array('paid_money'=>$array_data['total_fee']/100))->save();
			
			$arr['order_param'] = array(
				'third_id'	=>	$array_data['out_transaction_id'],
				'pay_money'	=>	$array_data['total_fee']/100,
				'order_id'	=>	$order_id_arr[1],
				'order_type'=>	$order_id_arr[0],
				'pay_type'	=>	$payment_money_order['pay_type'],
				'error'		=>	$array_data['result_code'],
				'is_mobile'	=>	intval($payment_money_order['is_mobile_pay']),
				'is_own'	=>	$order_id_arr[2] ? 1 : 0,
			);
			// fdump($arr['order_param'],'sign_data',true);
		}else{
			$arr['sign_result'] = false;
			$arr['order_param'] = array();
		}

		return $arr;
	}


	public function refund($order){
		$pay_post_data = array(
			'service' => 'unified.trade.refund',
			'version' => '2.0',
			'charset' => 'UTF-8',
			'sign_type' => 'MD5',
			'mch_id' => $this->wft_store_id,
			'out_trade_no' => $order['orderNo'],
			'out_refund_no' => substr($order['order_type'].'_'.$order['orderid'].'_'.$_SERVER['REQUEST_TIME'],0,32),
			'total_fee' => strval(floor($order['payment_money']*100)),
			'refund_fee' => $order['payment_refund_money'] ? strval(floor($order['payment_refund_money']*100)) : strval(floor($order['payment_money']*100)),
			'op_user_id' => $this->wft_store_id,
			'refund_channel' => 'ORIGINAL',
			'nonce_str' => createRandomStr(32),
		);
		
		$pay_post_data['sign'] = $this->getSign($pay_post_data);
		
		//转换成xml
		$xml = $this->toXml($pay_post_data);
		
		//请求
		$gateUrl = 'https://pay.hstypay.com/v2/pay/gateway';
		import('ORG.Net.Http');
		$http = new Http();
		$refundQueryResult = Http::curlPostXml($gateUrl, $xml);
		fdump_sql([$gateUrl, $xml, $refundQueryResult], 'wft_refund');
		if($refundQueryResult['status'] !== '0'){
			$returnArr = array(
				'error'=>1,
				'msg'=>'支付返回：'.$refundQueryResult['message'],
			);
		}else if($refundQueryResult['result_code'] !== '0'){
			$returnArr = array(
				'error'=>1,
				'msg'=>'支付返回：'.($refundQueryResult['err_msg'] ? $refundQueryResult['err_msg'] : $refundQueryResult['error_msg']),
			);
		}else{
			$returnArr = array(
				'error' => 0,
				'type' => 'ok',
				'refund_money'   => $refundQueryResult['refund_fee'],
				'refund_param'   => serialize($refundQueryResult),
			);
		}

		return $returnArr;
	}

	public function check($order){
		
		$orderNo = $order['orderNo'];
		
		$order_id_arr = explode('_',$orderNo);
		$payment_money_order = M('Payment_money_order')->where(array('orderid'=>$order_id_arr[1],'order_type'=>$order_id_arr[0]))->find();
		
		if(empty($payment_money_order)){
			$this->error_tips('支付查询发生错误，请联系平台客服');
		}
		
		$pay_type = '';
		if($payment_money_order['pay_type'] == 'weixin' && $payment_money_order['is_mobile_pay'] == '1'){
			$pay_type = 'weixin_wechat';
		}
		if(($payment_money_order['pay_type'] == 'alipayh5' || $payment_money_order['pay_type'] == 'alipay') && $payment_money_order['is_mobile_pay'] == '1'){
			$pay_type = 'alipayh5';
		}
		if(empty($pay_type)){
			$this->error_tips('支付方式不存在');
		}
		
		
		$pay_post_data = array(
			'service' => 'unified.trade.query',
			'version' => '2.0',
			'charset' => 'UTF-8',
			'sign_type' => 'MD5',
			'mch_id' => $this->wft_store_id,
			'out_trade_no' => $orderNo,
			'nonce_str' => createRandomStr(32),
			'limit_credit_pay' => $this->order_info['order_type'] == 'recharge' ? '1' : '0',
		);
		
		$pay_post_data['sign'] = $this->getSign($pay_post_data);
		
		//转换成xml
		$xml = $this->toXml($pay_post_data);
		
		//请求
		$gateUrl = 'https://pay.hstypay.com/v2/pay/gateway';
		import('ORG.Net.Http');
		$http = new Http();
		$orderQueryResult = Http::curlPostXml($gateUrl, $xml);
		
		if($orderQueryResult['result_code'] !== '0') {
			return array('error'=>1,'msg'=>'通信出错：'.$orderQueryResult['message']);
		}else if($orderQueryResult['status'] !== '0') {
			return array('error'=>1,'msg'=>'错误代码描述：'.$orderQueryResult['err_msg']);
		}else if($orderQueryResult['trade_state'] != 'SUCCESS') {
			switch($orderQueryResult['trade_state_desc']){
				case 'REFUND':
					$orderQueryResult['trade_state_desc'] = '转入退款';
					break;
				case 'NOTPAY':
					$orderQueryResult['trade_state_desc'] = '未支付';
					break;
				case 'CLOSED':
					$orderQueryResult['trade_state_desc'] = '已关闭';
					break;
				case 'REVERSE':
					$orderQueryResult['trade_state_desc'] = '已冲正';
					break;
				case 'REVOK':
					$orderQueryResult['trade_state_desc'] = '已撤销';
					break;
			}
			return array('error'=>1,'msg'=>'交易状态：'.$orderQueryResult['trade_state_desc']);
		}else{
			M('Payment_money_order')->where(array('id'=>$payment_money_order['id']))->data(array('paid_money'=>$orderQueryResult['total_fee']/100))->save();
			
			$go_pay_param = array(
				'third_id'		=>	$orderQueryResult['out_transaction_id'],
				'pay_money'		=>	$orderQueryResult['total_fee']/100,
				'order_id'		=>	$order_id_arr[1],
				'order_type'	=>	$order_id_arr[0],
				'pay_type'		=>	$payment_money_order['pay_type'],
				'error'			=>	0,
				'is_mobile'		=>	intval($payment_money_order['is_mobile_pay']),
				'errorMsg'		=>	'',
				'pay_time'		=>	strtotime($orderQueryResult['time_end']),
				'is_own'		=>	$order_id_arr[2] ? 1 : 0,
			);
			return $go_pay_param;
		}
	}
	
	public function scan_pay($order_info){
		
		$session_key = 'wft_order_userpaying_'.$order_info['auth_code'];
		
		if($_SESSION[$session_key] || $order_info['re_call']){
			$pay_post_data = array(
				'service' => 'unified.trade.query',
				'version' => '2.0',
				'charset' => 'UTF-8',
				'sign_type' => 'MD5',
				'mch_id' => $this->wft_store_id,
				'out_trade_no' => $order_info['out_trade_no'],
				'nonce_str' => createRandomStr(32),
			);
		}else{
			$pay_post_data = array(
				'service' => 'unified.trade.micropay',
				'version' => '2.0',
				'charset' => 'UTF-8',
				'sign_type' => 'MD5',
				'mch_id' => $this->wft_store_id,
				'out_trade_no' => $order_info['out_trade_no'],
				'body' => $order_info['order_name'],
				'sub_appid' =>  C('config.pay_weixin_appid'),
				'total_fee' => $order_info['total_fee']*100,
				'auth_code' => $order_info['auth_code'],
				'nonce_str' => createRandomStr(32),
				'mch_create_ip' => get_client_ip(),
			);
		}
		
		$pay_post_data['sign'] = $this->getSign($pay_post_data);
		
		//转换成xml
		$xml = $this->toXml($pay_post_data);
		
		//请求
		$gateUrl = 'https://pay.hstypay.com/v2/pay/gateway';
		import('ORG.Net.Http');
		$http = new Http();
		$return = Http::curlPostXml($gateUrl, $xml);
		fdump_sql([$gateUrl, $xml,$return],'wft_scan_pay');

		//如果订单需要查询，则丢到记录表里，计划任务主动去查订单状态同步，避免客户支付了订单状态未更新
		//result_code 系统错误状态码
		if ($return['need_query'] == 'Y' || $return['result_code'] == '50201') {
			$queryOrder = [
				'out_trade_no' => $order_info['out_trade_no'],
				'create_time' => time(),
				'query_count' => 0,
				'status' => 0
			];
			M('Wft_query_order')->add($queryOrder);
		}

		if($return['status'] !== '0'){
			$returnArr = array(
				'return_code'=>'FAIL',
				'return_msg'=>$return['message'],
			);
		}else if($return['result_code'] !== '0'){
			if ($return['err_code'] == 'USERPAYING') {
				$_SESSION[$session_key] = '1';
				$returnArr = array(
					'return_code'=>'TRUE',
					'result_code'=>'FAIL',
					'result_msg'=>'用户支付中，需要输入密码！请询问用户输入完成后，再点击“确认支付”',
					'err_code'=>'USERPAYING',
				);
			}else{
				$returnArr = array(
					'return_code'=>'TRUE',
					'result_code'=>'FAIL',
					'err_code_des'=>$return['err_msg'] ? $return['err_msg'] : ($return['error_msg'] ? $return['error_msg'] : $return['message']),
				);
			}
		}else{
			if($returnArr['trade_state'] == 'SUCCESS'){
				unset($_SESSION[$session_key]);
			}
			$returnArr = array(
				'return_code'	=>	'TRUE',
				'result_code'	=>	'TRUE',
				'transaction_id'=>	$return['transaction_id'],
				'total_fee'		=>	$return['total_fee'],
				'openid'		=>	$return['sub_openid'] ? $return['sub_openid'] : '',
				'alipay_uid'	=>	$return['buyer_user_id'] ? $return['buyer_user_id'] : '',
			);
			if($return['trade_state']){
				$returnArr['trade_state'] = $return['trade_state'];
			}
			if($return['trade_type'] == 'pay.wechat.micropay' || $return['trade_type'] == '	pay.weixin.micropay'){
				$returnArr['pay_type'] = 'weixin';
			}else if($return['trade_type'] == 'pay.alipay.micropay'){
				$returnArr['pay_type'] = 'alipay';
			}
			if($_SESSION[$session_key] && $return['trade_state'] == 'NOTPAY'){
				$returnArr['trade_state'] = 'USERPAYING';
			}
		}
		if($return['trade_state_desc']){
			$returnArr['trade_state_desc'] = $return['trade_state_desc'];
		}
		$returnArr['raw_result'] = $return;
		return $returnArr;
	}

	public function pay($order,$payinfo=array()){
		$this->order_info  = $order;
		$order_id_arr = explode('_',$this->order_info['order_id']);
		
		M('Payment_money_order')->where(array('orderid'=>$order_id_arr[1],'order_type'=>$order_id_arr[0]))->data(array('pay_method'=>'wft','orderNo'=>$this->order_info['order_id'],'is_own'=>intval($order_id_arr[2])))->save();
		
		if($this->order_info['pay_type'] == 'weixin_wechat' || $this->order_info['pay_type'] == 'weixin_wxapp'){
			$pay_post_data = array(
				'service' => 'pay.weixin.jspay',
				'version' => '2.0',
				'charset' => 'UTF-8',
				'sign_type' => 'MD5',
				'mch_id' => $this->wft_store_id,
				'is_raw' => '1',
				'is_minipg' => $this->order_info['pay_type'] == 'weixin_wechat' ? '0' : '1',
				'out_trade_no' => $this->order_info['order_id'],
				'body' => $this->order_info['order_name'],
				'sub_openid' => $this->order_info['openid'],
				'sub_appid' => in_array($this->order_info['pay_type'], ['weixin_wechat', 'weixin_h5']) ? C('config.pay_weixin_appid') : C('config.pay_wxapp_appid'),
				'total_fee' => $this->order_info['order_total_money']*100,
				'mch_create_ip' => get_client_ip(),
				'notify_url' => $this->page_back_url,
				'nonce_str' => createRandomStr(32),
				'limit_credit_pay' => $this->order_info['order_type'] == 'recharge' ? '1' : '0',
			);
			
			$pay_post_data['sign'] = $this->getSign($pay_post_data);
			
			//转换成xml
			$xml = $this->toXml($pay_post_data);
			
			//请求
			$gateUrl = 'https://pay.hstypay.com/v2/pay/gateway';
			import('ORG.Net.Http');
			$http = new Http();
			$return = Http::curlPostXml($gateUrl, $xml);
			
			if($return['status'] !== '0'){
				$returnArr = array(
					'error'=>1,
					'msg'=>'支付返回：'.$return['message'],
				);
			}else if($return['result_code'] !== '0'){
				$returnArr = array(
					'error'=>1,
					'msg'=>'支付返回：'.$return['err_msg'],
				);
			}else{
				$returnArr = array(
					'error' => 0,
					'weixin_param' => $return['pay_info'],
					'redirctUrl'   => C('config.site_url').'/index.php?c=LowFeePayNotice&a=order_back_notice&pay_type=wftpay&order_type='.$order_id_arr[0].'&order_id='.$order_id_arr[1].'&source=1'.($order_id_arr[2] ? '&is_own=1' : ''),
				);
			}
			return $returnArr;
		}else if($this->order_info['pay_type'] == 'weixin_h5'){
			$pay_post_data = array(
				'service' => 'pay.weixin.wappay',
				'version' => '2.0',
				'charset' => 'UTF-8',
				'sign_type' => 'RSA_1_256',
				'mch_id' => $this->wft_store_id,
				'is_raw' => '1',
				'is_minipg' => $this->order_info['pay_type'] == 'weixin_wechat' ? '0' : '1',
				'out_trade_no' => $this->order_info['order_id'],
				'body' => $this->order_info['order_name'],
				'sub_openid' => $this->order_info['openid'],
				'sub_appid' => $this->order_info['pay_type'] == 'weixin_wechat' ? C('config.pay_weixin_appid') : C('config.pay_wxapp_appid'),
				'total_fee' => $this->order_info['order_total_money']*100,
				'mch_create_ip' => get_client_ip(),
				'notify_url' => $this->page_back_url,
				'nonce_str' => createRandomStr(32),
				'limit_credit_pay' => $this->order_info['order_type'] == 'recharge' ? '1' : '0',
			);
			
			$pay_post_data['sign'] = $this->createRSASign($pay_post_data);
			fdump($pay_post_data, "lumin012017",1);
			//转换成xml
			$xml = $this->toXml($pay_post_data);
			
			//请求
			$gateUrl = 'https://pay.swiftpass.cn/pay/gateway';
			import('ORG.Net.Http');
			$http = new Http();
			$return = Http::curlPostXml($gateUrl, $xml);
			fdump($return, "lumin012017",1);
			
			if($return['status'] !== '0'){
				$returnArr = array(
					'error'=>1,
					'msg'=>'支付返回：'.$return['message'],
				);
			}else if($return['result_code'] !== '0'){
				$returnArr = array(
					'error'=>1,
					'msg'=>'支付返回：'.$return['err_msg'],
				);
			}else{
				$returnArr = array(
					'error' => 0,
					'url' => $return['pay_info']
				);
			}
			return $returnArr;
		}else if($this->order_info['pay_type'] == 'alipayh5'){
			$pay_post_data = array(
				'service' => 'pay.alipay.jspay',
				'version' => '2.0',
				'charset' => 'UTF-8',
				'sign_type' => 'MD5',
				'mch_id' => $this->wft_store_id,
				'out_trade_no' => $this->order_info['order_id'],
				'body' => $this->order_info['order_name'],
				'total_fee' => $this->order_info['order_total_money']*100,
				'mch_create_ip' => get_client_ip(),
				'notify_url' => $this->page_back_url,
				'nonce_str' => createRandomStr(32),
				'limit_credit_pay' => $this->order_info['order_type'] == 'recharge' ? '1' : '0',
				'buyer_id' => $this->order_info['alipay_uid'],
			);
			
			$pay_post_data['sign'] = $this->getSign($pay_post_data);
			fdump($pay_post_data,'pay_post_data');
			//转换成xml
			$xml = $this->toXml($pay_post_data);
			
			//请求
			$gateUrl = 'https://pay.hstypay.com/v2/pay/gateway';
			import('ORG.Net.Http');
			$http = new Http();
			$return = Http::curlPostXml($gateUrl, $xml);
			if($return['status'] !== '0'){
				$returnArr = array(
					'error'=>1,
					'msg'=>'支付返回：'.$return['message'],
				);
			}else if($return['result_code'] !== '0'){
				$returnArr = array(
					'error'=>1,
					'msg'=>'支付返回：'.$return['err_msg'],
				);
			}else{
				$returnArr = array(
					'error' => 0,
					'alipay_param' => $return['pay_info'],
					'redirctUrl'   => C('config.site_url').'/index.php?c=LowFeePayNotice&a=order_back_notice&pay_type=wftpay&order_type='.$order_id_arr[0].'&order_id='.$order_id_arr[1].'&source=1'.($order_id_arr[2] ? '&is_own=1' : ''),
				);
			}
			
			return $returnArr;
		}
		

		return $returnArr;
	}
	
	private function toXml($arr){
		$xml = '<xml>';
		forEach($arr as $k=>$v){
			$xml.='<'.$k.'><![CDATA['.$v.']]></'.$k.'>';
		}
		$xml.='</xml>';
		
		return $xml;
	}
	
	private function getSign($arr){
		$signPars = "";
		ksort($arr);
		foreach($arr as $k => $v) {
			if("" != $v && "sign" != $k) {
				$signPars .= $k . "=" . $v . "&";
			}
		}
		$signPars .= "key=" . $this->wft_pay_key;
	
		return strtoupper(md5($signPars));
	}

	function createRSASign($arr) {
		$signPars = "";
		ksort($arr);
		foreach($arr as $k => $v) {
			if("" != $v && "sign" != $k) {
				$signPars .= $k . "=" . $v . "&";
			}
		}

		$signPars = substr($signPars, 0, strlen($signPars) - 1);
		fdump($this->wft_pay_prikey, "lumin012017",1);
		$priKey=$this->wft_pay_prikey;
		$priKey = "-----BEGIN RSA PRIVATE KEY-----\n" .
				wordwrap($priKey, 64, "\n", true) .
				"\n-----END RSA PRIVATE KEY-----";
		fdump($priKey, "lumin012017",1);

		$res = openssl_get_privatekey($priKey);
		fdump($res, "lumin012017",1);
		openssl_sign($signPars, $sign, $res, OPENSSL_ALGO_SHA256);
		openssl_free_key($res);
		$sign = base64_encode($sign);
		return $sign;
	}
}
