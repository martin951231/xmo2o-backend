<?php
/**
 * 澳洲 paylinx支付
 * 支持 支付宝/微信/小程序等支付方式
 * 文档  https://paylinx.cn/doc/index.html
 * author by lumin  2019-03-15 
 */
class Paylinx{
	private 		$mch_id ;// 商户ID
	private 		$store_id ;// 商户店铺ID
	private 		$wxappid ;// 微信小程序 appid
	private 		$appid ;// appid
	private 		$key ;// 签名秘钥
	private 		$wxapp_notify_url ;//支付回调通知
	private         $fee_type ;//交易币种  目前只支持AUD
	private         $request_wxapp_url ;//微信小程序支付请求url
	private         $request_app_wechat_url ;//微信原生支付请求url
	private 		$request_wechat_query_url ;//微信支付查询url
	private  		$request_wechat_refund_url;//微信退款url
	private 		$create_getway_url;
	public function __construct(){
		$this->mch_id 			= C('config.pay_paylinx_mchid');
		$this->wxappid 			= C('config.pay_paylinx_wxappid');
		$this->appid			= C('config.pay_paylinx_appid');
		$this->key 				= C('config.pay_paylinx_key');
		$this->store_id			= C('config.pay_paylinx_store_id');
		$this->fee_type 		= C('config.pay_paylinx_fee_type');
		$this->wxapp_notify_url = C('config.site_url').'/source/paylinx_page_notice.php';
		$this->request_wxapp_url= 'https://paylinx.cn/wxpay/gateway/mini_program/';//微信小程序支付
		$this->request_app_wechat_url = 'https://paylinx.cn/wxpay/gateway/app/';//app原生 微信支付
		$this->request_wechat_query_url = 'https://paylinx.cn/wxpay/gateway/orderquery/';//微信支付查询支付情况
		$this->request_wechat_refund_url = 'https://paylinx.cn/wxpay/gateway/refund/';//微信退款url
		$this->create_getway_url = 'https://paylinx.cn/wxpay/gateway/unifiedorder/';//创建交易接口
	}

	//调起支付
	public function pay($order){
		$order_id_arr = explode('_',$order['order_id']);
		M('Payment_money_order')->where(array('orderid'=>$order_id_arr[1],'order_type'=>$order_id_arr[0]))->data(array('pay_method'=>'paylinx','orderNo'=>$order['order_id'],'is_own'=>intval($order_id_arr[2])))->save();
		import('ORG.Net.Http');
		$http = new Http();
		if($order['pay_type'] == 'weixin_wxapp')//小程序微信支付
		{
			$pay_post_data = [
				'mch_id' 			=> $this->mch_id,
				'store_id'			=> $this->store_id,//可选参数
				'appid'				=> $this->wxappid,
				'openid'			=> $order['openid'],
				'nonce_str'			=> createRandomStr(32),
				'notify_url'		=> $this->wxapp_notify_url,
				'out_trade_no'  	=> $order['order_id'],
				'body' 				=> $order['order_name'],
				'spbill_create_ip'	=> get_client_ip(),
				'total_fee'			=> $order['order_total_money']*100,
				'fee_type'			=> $this->fee_type,
				'trade_type'		=> 'JSAPI'
			];		
			$pay_post_data['sign'] = $this->getSign($pay_post_data);
			$xml = $this->toXml($pay_post_data);
			$return = Http::curlPostXml($this->request_wxapp_url, $xml);
			fdump_api($return,'return_30',true);
			if($return['return_code'] == 'FAILD'){
				$returnArr = array(
					'error'=>1,
					'msg'=>'支付返回：'.$return['return_msg'],
				);
			}
			elseif($return['result_code'] == 'FAILD'){
				$returnArr = array(
					'error'=>1,
					'msg'=>'支付返回：'.$return['err_code_des'],
				);
			}
			else{
				$returnArr = array(
					'error' => 0,
					'weixin_param' => $return['requestParams'],
					'redirctUrl'   => C('config.site_url').'/index.php?c=LowFeePayNotice&a=order_back_notice&pay_type=paylinx&order_type='.$order_id_arr[0].'&order_id='.$order_id_arr[1].'&source=1'.($order_id_arr[2] ? '&is_own=1' : ''),
				);
			}
		}
		elseif($order['pay_type'] == 'app_weixin_wechat'){//app原生支付 微信支付 这一块ios、安卓还没搞好。3月18日
			$pay_post_data = [
				'mch_id' 			=> $this->mch_id,
				'store_id'			=> $this->store_id,//可选参数
				'appid'				=> $this->appid,
				'nonce_str'			=> createRandomStr(32),
				'notify_url'		=> $this->wxapp_notify_url,
				'out_trade_no'  	=> $order['order_id'],
				'body' 				=> $order['order_name'],
				'spbill_create_ip'	=> get_client_ip(),
				'total_fee'			=> $order['order_total_money']*100,
				'fee_type'			=> $this->fee_type,
				'trade_type'		=> 'APP'
			];	
			$pay_post_data['sign'] = $this->getSign($pay_post_data);
			$xml = $this->toXml($pay_post_data);
			$return = Http::curlPostXml($this->request_app_wechat_url, $xml);

			if($return['return_code'] == 'FAILD'){
				$returnArr = array(
					'error'=>1,
					'msg'=>'支付返回：'.$return['return_msg'],
				);
			}
			elseif($return['result_code'] == 'FAILD'){
				$returnArr = array(
					'error'=>1,
					'msg'=>'支付返回：'.$return['err_code_des'],
				);
			}
			else{
				$returnArr = array(
					'error' => 0,
					'weixin_param' => $return['requestParams'],
					'redirctUrl'   => C('config.site_url').'/index.php?c=LowFeePayNotice&a=order_back_notice&pay_type=paylinx&order_type='.$order_id_arr[0].'&order_id='.$order_id_arr[1].'&source=1'.($order_id_arr[2] ? '&is_own=1' : ''),
				);
			}
		}
		elseif($order['pay_type'] == 'weixin_wechat'){//公众号微信支付
			$pay_post_data = [
				'mch_id' 			=> $this->mch_id,
				'store_id'			=> $this->store_id,//可选参数
				'nonce_str'			=> createRandomStr(32),
				'notify_url'		=> $this->wxapp_notify_url,
				'return_url'		=> C('config.site_url').'/index.php?c=LowFeePayNotice&a=order_back_notice&pay_type=paylinx&order_type='.$order_id_arr[0].'&order_id='.$order_id_arr[1].'&source=1'.($order_id_arr[2] ? '&is_own=1' : ''),
				'out_trade_no'  	=> $order['order_id'],
				'body' 				=> $order['order_name'],
				'spbill_create_ip'	=> get_client_ip(),
				'total_fee'			=> $order['order_total_money']*100,
				'fee_type'			=> $this->fee_type,
				'trade_type'		=> 'JSAPI'
			];	
			$pay_post_data['sign'] = $this->getSign($pay_post_data);
			$xml = $this->toXml($pay_post_data);
			$return = Http::curlPostXml($this->create_getway_url, $xml);

			if($return['return_code'] == 'FAILD'){
				$returnArr = array(
					'error'=>1,
					'msg'=>'支付返回：'.$return['return_msg'],
				);
			}
			elseif($return['result_code'] == 'FAILD'){
				$returnArr = array(
					'error'=>1,
					'msg'=>'支付返回：'.$return['err_code_des'],
				);
			}
			else{
				$returnArr = array(
					'error' => 0,
					'weixin_param' => ['prepay_id' => $return['prepay_id'], 'code_url' => $return['code_url']],
					'redirctUrl'   => 'https://paylinx.cn/wxpay/h5/?mch_id='.$this->mch_id.'&out_trade_no='.$order['order_id'],
				);
			}
		}
		
		return $returnArr;
	}

	public function refund($order){
		
		$pay_post_data = array(
			'mch_id' => $this->mch_id,
			'nonce_str' => createRandomStr(32),
			'out_trade_no' => $order['orderNo'],
			'out_refund_no' => substr($order['order_type'].'_'.$order['orderid'].'_'.strrev($_SERVER['REQUEST_TIME']),0,32),
			'total_fee' => strval($order['payment_money']*100),
			'refund_fee' => $order['payment_refund_money'] ? strval($order['payment_refund_money']*100) : strval($order['payment_money']*100),
		);
		
		$pay_post_data['sign'] = $this->getSign($pay_post_data);
		
		//转换成xml
		$xml = $this->toXml($pay_post_data);
		
		//请求
		import('ORG.Net.Http');
		$http = new Http();
		$refundQueryResult = Http::curlPostXml($this->request_wechat_refund_url, $xml);
		
		
		if($refundQueryResult['return_code'] == 'FAILD'){
			$returnArr = array(
				'error'=>1,
				'msg'=>'支付返回：'.$refundQueryResult['return_msg'],
			);
		}else if($refundQueryResult['result_code'] == 'FAILD'){
			$returnArr = array(
				'error'=>1,
				'msg'=>'支付返回：'.$refundQueryResult['err_code_des'],
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
			return array('error'=>1,'msg'=>'支付查询发生错误，请联系平台客服');
		}
		
		$pay_type = '';
		if($payment_money_order['pay_type'] == 'weixin' && $payment_money_order['is_mobile_pay'] == '3'){
			$pay_type = 'weixin_wxapp';
		}
		if($payment_money_order['pay_type'] == 'weixin' && $payment_money_order['is_mobile_pay'] == '2'){
			$pay_type = 'app_weixin_wechat';
		}
		if($payment_money_order['pay_type'] == 'weixin' && $payment_money_order['is_mobile_pay'] == '1'){
			$pay_type = 'weixin_wechat';
		}
		if(empty($pay_type)){
			return array('error'=>1,'msg'=>'支付方式不存在');
		}
		
		
		$pay_post_data = array(
			'mch_id' => $this->mch_id,
			'nonce_str' => createRandomStr(32),
			'out_trade_no' => $orderNo
		);
		
		$pay_post_data['sign'] = $this->getSign($pay_post_data);
		
		//转换成xml
		$xml = $this->toXml($pay_post_data);
		
		//请求
		import('ORG.Net.Http');
		$http = new Http();
		$orderQueryResult = Http::curlPostXml($this->request_wechat_query_url, $xml);
		fdump($orderQueryResult, 'orderQueryResult', true);
		if($orderQueryResult['return_code'] == 'FAILD') {
			return array('error'=>1,'msg'=>'通信出错：'.$orderQueryResult['return_msg'],'order_id'=>$order_id_arr[1],'order_type'=>$order_id_arr[0],'is_mobile'=>1);
		}else if($orderQueryResult['result_code'] == 'FAILD') {
			return array('error'=>1,'msg'=>'错误代码描述：'.$orderQueryResult['err_code_des'],'order_id'=>$order_id_arr[1],'order_type'=>$order_id_arr[0],'is_mobile'=>1);
		}else if($orderQueryResult['trade_status'] == 'FAILD' || empty($orderQueryResult['transaction_id'])) {
			return array('error'=>1,'msg'=>'错误代码描述：'.$orderQueryResult['trade_state_desc'],'trade_status'=>$orderQueryResult['trade_status'],'order_id'=>$order_id_arr[1],'order_type'=>$order_id_arr[0],'is_mobile'=>1);
		}else{
			M('Payment_money_order')->where(array('id'=>$payment_money_order['id']))->data(array('paid_money'=>$orderQueryResult['total_fee']/100))->save();
			
			$go_pay_param = array(
				'third_id'		=>	$orderQueryResult['transaction_id'],
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

	public function notice($sign_data){
		
		
		$array_data = json_decode(json_encode(simplexml_load_string($sign_data, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		
		$tmpData = $array_data;
		unset($tmpData['sign']);
		$sign = $this->getSign($tmpData);//本地签名
		
		if ($array_data['sign'] == $sign) {
			$arr['sign_result'] = true;
			$order_id_arr = explode('_',$array_data['out_trade_no']);
			
			$payment_money_order = M('Payment_money_order')->where(array('orderid'=>$order_id_arr[1],'order_type'=>$order_id_arr[0]))->find();
			
			M('Payment_money_order')->where(array('id'=>$payment_money_order['id']))->data(array('paid_money'=>$array_data['total_fee']/100))->save();
			
			$arr['order_param'] = array(
				'third_id'	=>	$array_data['transaction_id'],
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

	public function __set($property, $value) {
		$this->$property = $value;
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
		$signPars .= "key=" . $this->key;
	
		return strtoupper(md5($signPars));
	}
}
