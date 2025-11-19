<?php
/**
 * hark superpay 支付
 * 支持 支付宝
 * author by lumin  2019-09-11 
 */
class Superpay{
	private 		$getway_url;
	private 		$query_url;
	private 		$refund_url;
	private 		$mini_getway_url;
	public function __construct(){
		$this->getway_url = 'https://api.superpayglobal.com/payment/bridge/merchant_request';//网关地址
		$this->query_url = 'https://api.superpayglobal.com/payment/bridge/notification_verification';//网关地址
		$this->refund_url = 'https://api.superpayglobal.com/payment/bridge/refund';//网关地址
		$this->mini_getway_url = 'https://gate.supaytechnology.com/api/gateway/merchant/order';//支付宝小程序网关
	}

	//调起支付
	public function pay($order, $pay_info){
		if(empty($pay_info)){
			return ['error'=>1, 'msg' => '支付信息传递有误！'];
		}
		$order_id_arr = explode('_',$order['order_id']);
		import('ORG.Net.Http');
		$http = new Http();
		if($order['pay_type'] == 'alipay_mini'){//支付宝小程序支付
			M('Payment_money_order')->where(array('orderid'=>$order_id_arr[1],'order_type'=>$order_id_arr[0]))->data(array('pay_method'=>'superpaymini','orderNo'=>$order['order_id'],'is_own'=>intval($order_id_arr[2])))->save();
			$pay_post_data = [
				'service' => 'create_instant_trade',
				'mch_id' => $pay_info['superpay_mchid'],
				'charset' => 'UTF-8',
				'sign_type' => 'MD5',
				'version' => '1.0',
				'call_back_url' => C('config.site_url').'/source/superpay_page_notice.php',
				'store_name' => $pay_info['superpay_storename'],
				'recevice_id' => $pay_info['superpay_receive_id'],
				'mch_order_no' => $order['order_id'],
				'pay_way' => 'JSAPI',
				'pay_channel' => 'ALIPAY',
				'pay_amount' => (string)$order['order_total_money'],
				'goods_name' => $order['order_name'],
				'goods_price' => (string)$order['order_total_money'],
				'quantity' => '1',
				'goods_desc' => $order['order_name'],
				'timeout_express' => '60m',
				'buyer_id' => $order['alipay_uid']
			];	

			$pay_post_data['sign'] = $this->getMiniSign($pay_post_data, $pay_info['superpay_key']);
			$json = json_encode($pay_post_data);
			$return = Http::curlPost($this->mini_getway_url, $json, 15, 'json');
			if($return['status'] == 'T'){
				if($return['data']['result'] == 'true' || $return['data']['result'] === true){
					$returnArr = array(
						'error' => 0,
						'param' => json_decode($return['data']['pay_info'],true),
						'redirctUrl'   => C('config.site_url').'/index.php?c=LowFeePayNotice&a=order_back_notice&pay_type=superpay&order_type='.$order_id_arr[0].'&order_id='.$order_id_arr[1].'&source=1'.($order_id_arr[2] ? '&is_own=1' : ''),
					);
				}
				else{
					$returnArr = [
						'error' => 1,
						'msg' => json_encode($return)
					];
				}
			}
			else{
				$returnArr = [
					'error' => 1,
					'msg' => '调取superpay接口返回结果失败！请检查配置参数是否正确！'
				];
			}
		}
		else{//H5支付

			M('Payment_money_order')->where(array('orderid'=>$order_id_arr[1],'order_type'=>$order_id_arr[0]))->data(array('pay_method'=>'superpayh5','orderNo'=>$order['order_id'],'is_own'=>intval($order_id_arr[2])))->save();
			$pay_post_data = [
				'merchant_id'		=> $pay_info['superpay_mchid'],
				'authentication_code'=> $pay_info['superpay_key'],
				'product_title'		=> $order['order_name'],
				'merchant_trade_no'	=> $order['order_id'],
				'currency'			=> 'AUD',
				'total_amount'		=> (string)$order['order_total_money'],
				'create_time'		=> date('Y-m-d H:i:s'),
				'notification_url' 	=> C('config.site_url').'/source/superpay_page_notice.php',
				'return_url' 		=> C('config.site_url').'/packapp/foodshop/#/PaySucess?order_id='.$order_id_arr[1],
				'mobile_flag'		=> 'T'
			];	
			$pay_post_data['token'] = $this->getSign($pay_post_data);
			// dump($pay_post_data);exit;
			$query = http_build_query($pay_post_data);
			$redirctUrl = $this->getway_url."?".$query;
			$returnArr = [
				'error' => 0,
				'redirctUrl' => $redirctUrl
			];
			
		}
		return $returnArr;
	}

	//$pay_info  店铺支付资料
	public function refund($order){
		import('ORG.Net.Http');
		$http = new Http();
		if($order['pay_method'] == 'superpaymini'){
			$pay_post_data = [
				'service' => 'create_refund',
				'mch_id' => $order['superpay_mchid'],
				'charset' => 'UTF-8',
				'sign_type' => 'MD5',
				'version' => '1.0',
				'call_back_url' => C('config.site_url').'/source/superpay_page_notice_refund.php',
				'mch_order_no' => substr($order['order_type'].'_'.$order['orderid'].'_'.strrev($_SERVER['REQUEST_TIME']),0,32),
				'orig_mch_order_no' => $order['orderNo'],
				'refund_amount' => $order['payment_refund_money'] ? $order['payment_refund_money'] : $order['payment_money']
			];	

			$pay_post_data['sign'] = $this->getMiniSign($pay_post_data, $order['superpay_key']);
			$json = json_encode($pay_post_data);
			$return = Http::curlPost($this->mini_getway_url, $json, 15, 'json');
			if($return['status'] == 'T'){
				if($return['data']['result'] == 'true' || $return['data']['result'] === true){
					$returnArr = array(
						'error' => 0,
						'type' => 'ok',
						'refund_money'   => $pay_post_data['total_amount'],
						'refund_param'   => serialize($return),
					);
				}
				else{
					$returnArr = array(
						'error'=>1,
						'msg'=>'退款返回：'.$return['message'],
					);
				}
			}
			else{
				$returnArr = [
					'error' => 1,
					'msg' => '调取superpay接口返回结果失败！请检查配置参数是否正确！'
				];
			}
		}
		else{
			//查询
			$query_post_data = [
				'merchant_id'		=> $order['superpay_mchid'],
				'authentication_code'=> $order['superpay_key'],
				'merchant_trade_no'	=> $order['orderNo']
			];
			$signPars = "";
			$signPars .= "merchant_id=".$query_post_data['merchant_id'];
			$signPars .= "&authentication_code=".$query_post_data['authentication_code'];
			$signPars .= "&merchant_trade_no=".$query_post_data['merchant_trade_no'];
			$query_post_data['token'] = md5($signPars);
			$query = http_build_query($query_post_data);
			$queryurl = 'https://api.superpayglobal.com/payment/bridge/get_payment_detail?'.$query;
			$query_res = json_decode($http->curlGet($queryurl),true);
			if($query_res['query_success'] == 'T' && $query_res['total_amount'] > 0){
				$order['payment_refund_money'] = $query_res['total_amount'];//避免因为superpay那边有手续费，所以要先查一次
			}
			else{
				$returnArr = array(
					'error'=>1,
					'msg'=>'退款返回：查询出错',
				);
				return $returnArr;
			}

			$pay_post_data = [
				'merchant_id'		=> $order['superpay_mchid'],
				'authentication_code'=> $order['superpay_key'],
				'merchant_trade_no'		=> substr($order['order_type'].'_'.$order['orderid'].'_'.strrev($_SERVER['REQUEST_TIME']),0,32),
				'org_merchant_trade_no'	=> $order['orderNo'],
				'total_amount'			=> $order['payment_refund_money'] ? $order['payment_refund_money'] : $order['payment_money']
			];
			$signPars = "";
			$signPars .= "merchant_id=".$pay_post_data['merchant_id'];
			$signPars .= "&authentication_code=".$pay_post_data['authentication_code'];
			$signPars .= "&merchant_trade_no=".$pay_post_data['merchant_trade_no'];
			$signPars .= "&org_merchant_trade_no=".$pay_post_data['org_merchant_trade_no'];
			$signPars .= "&total_amount=" . $pay_post_data['total_amount'];
		
			$pay_post_data['token'] = md5($signPars);

			// dump($pay_post_data);exit;
			$query = http_build_query($pay_post_data);
			$queryurl = $this->refund_url."?".$query;
			$refundQueryResult = json_decode($http->curlGet($queryurl),true);
			fdump($refundQueryResult,"lumin_superpay",1);
			
			
			if($refundQueryResult['query_success'] == 'F'){
				$returnArr = array(
					'error'=>1,
					'msg'=>'退款返回：'.$refundQueryResult['error_message'],
				);
			}else if($refundQueryResult['query_success'] == 'T'){
				$returnArr = array(
					'error' => 0,
					'type' => 'ok',
					'refund_money'   => $pay_post_data['total_amount'],
					'refund_param'   => serialize($refundQueryResult),
				);
			}else{
				
				$returnArr = array(
					'error'=>1,
					'msg'=>'退款返回：异常',
				);
			}
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
		$plat_order = M("Plat_order")->where(['orderid'=> $order_id_arr[1]])->field("store_id")->find();
		if(empty($plat_order)){
			return array('error'=>1,'msg'=>'没有找到订单所属门店！');
		}
		$merchant_store_pay_rate = M("Merchant_store_pay_rate")->where(['store_id'=>$plat_order['store_id']])->find();
		if($merchant_store_pay_rate['is_open'] == '1' && !empty($merchant_store_pay_rate['superpay_mchid']) && !empty($merchant_store_pay_rate['superpay_receive_id']) && !empty($merchant_store_pay_rate['superpay_storename']) && !empty($merchant_store_pay_rate['superpay_key'])){

		}
		else{
			return array('error'=>1,'msg'=>'门店superpay参数配置不全或者未开启');	
		}
		
		
		$pay_post_data = array(
			'service' => 'query_trade',
			'mch_id' => $merchant_store_pay_rate['superpay_mchid'],
			'charset'			=> 'UTF-8',
			'sign_type'			=> 'MD5',
			'version'			=> '1.0',
			'mch_order_no' => $orderNo,
			'trade_type' => 'INSTANT'
		);
		
		$pay_post_data['sign'] = $this->getMiniSign($pay_post_data);
		
		$json = json_encode($pay_post_data);
		
		//请求
		import('ORG.Net.Http');
		$http = new Http();
		$orderQueryResult = Http::curlPost($this->mini_getway_url, $json, 15, 'json');
		fdump($orderQueryResult, 'orderQueryResult', true);
		if($return['status'] == 'T'){
			if($return['data']['result'] == 'true' || $return['data']['result'] === true){
				M('Payment_money_order')->where(array('id'=>$payment_money_order['id']))->data(array('paid_money'=>$orderQueryResult['pay_info']['tradeAmount']))->save();
				$go_pay_param = array(
					'third_id'		=>	$orderQueryResult['pay_info']['innerTradeNo'],
					'pay_money'		=>	$orderQueryResult['pay_info']['tradeAmount'],
					'order_id'		=>	$order_id_arr[1],
					'order_type'	=>	$order_id_arr[0],
					'pay_type'		=>	$payment_money_order['pay_type'],
					'error'			=>	0,
					'is_mobile'		=>	intval($payment_money_order['is_mobile_pay']),
					'errorMsg'		=>	'',
					'pay_time'		=>	strtotime($orderQueryResult['pay_info']['tradeDate']),
					'is_own'		=>	$order_id_arr[2] ? 1 : 0,
				);
				return $go_pay_param;
			}
			else{
				$returnArr = [
					'error' => 1,
					'msg' => $return['pay_info']
				];
			}
		}
		else{
			return array('error'=>1,'msg'=>'调取superpay接口返回结果失败！请检查配置参数是否正确！');	
		}
	}

	public function notice($sign_data){
		
		
		// $array_data = json_decode(json_encode(simplexml_load_string($sign_data, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		
		// $tmpData = $array_data;
		// unset($tmpData['sign']);
		// $sign = $this->getSign($tmpData);//本地签名
		fdump($_REQUEST,"lumin_superpay",1);
		$notice_id = I("notice_id") ? : I("notify_id");
		$merchant_trade_no = I("merchant_trade_no") ? : I("outer_trade_no");
		$token = I("token");
		$order_id_arr = explode('_',$merchant_trade_no);
		$payment_money_order = M('Payment_money_order')->where(array('orderid'=>$order_id_arr[1],'order_type'=>$order_id_arr[0]))->find();
		$plat_order = M('Plat_order')->where(array('orderid'=>$order_id_arr[1]))->find();
		if(empty($plat_order)){
			$arr['sign_result'] = false;
			$arr['order_param'] = array();
			return $arr;
		}
		$store_id = 0;
		if($order_id_arr[0] == 'plat' && $plat_order['business_type'] == 'foodshop'){//目前只支持餐饮
			$order_info = M("Foodshop_order")->where(['order_id'=>$plat_order['business_id']])->find();
			$store_id = $order_info['store_id'];
		}
		else{
			$arr['sign_result'] = false;
			$arr['order_param'] = array();
			return $arr;
		}

		if(empty($store_id)){
			$arr['sign_result'] = false;
			$arr['order_param'] = array();
			return $arr;	
		}
		$merchant_store_pay_rate = M("Merchant_store_pay_rate")->where(['store_id'=>$store_id])->find();
		if($payment_money_order['pay_method'] == 'superpayh5'){//h5支付
			if($merchant_store_pay_rate['is_open'] == '1' && !empty($merchant_store_pay_rate['superpay_mchid']) && !empty($merchant_store_pay_rate['superpay_key'])){

				//校验token
				$signPars = "";
				$signPars .= "notice_id=".$notice_id;
				$signPars .= "&merchant_trade_no=".$merchant_trade_no;
				$signPars .= "&authentication_code=".$merchant_store_pay_rate['superpay_key'];
				if(md5($signPars) != $token){
					$arr['sign_result'] = false;
					$arr['order_param'] = array();
					return $arr;
				}

				//查询支付结果
				$queryurl = $this->query_url."?merchant_trade_no=".$merchant_trade_no."&notice_id=".$notice_id;
				import('ORG.Net.Http');
				$http = new Http();
				$result = $http->curlGet($queryurl);
				fdump($result,"lumin_superpay",1);
				if($result == 'SUCCESS'){

					fdump('success----',"lumin_superpay",1);
				
					M('Payment_money_order')->where(array('id'=>$payment_money_order['id']))->data(array('paid_money'=>$payment_money_order['payment_money']))->save();
					$arr['sign_result'] = true;
					$arr['order_param'] = array(
						'third_id'	=>	$notice_id,
						'pay_money'	=>	$payment_money_order['payment_money'],
						'order_id'	=>	$order_id_arr[1],
						'order_type'=>	$order_id_arr[0],
						'pay_type'	=>	$payment_money_order['pay_type'],
						'error'		=>	'0',
						'is_mobile'	=>	intval($payment_money_order['is_mobile_pay']),
						'is_own'	=>	$order_id_arr[2] ? 1 : 0,
					);
				}
			}
			else{
				$arr['sign_result'] = false;
				$arr['order_param'] = array();
				return $arr;
			}
		}
		else{
			if($merchant_store_pay_rate['is_open'] == '1' && !empty($merchant_store_pay_rate['superpay_mchid']) && !empty($merchant_store_pay_rate['superpay_key']) && !empty($merchant_store_pay_rate['superpay_receive_id']) && !empty($merchant_store_pay_rate['superpay_storename'])){
				M('Payment_money_order')->where(array('id'=>$payment_money_order['id']))->data(array('paid_money'=>$payment_money_order['payment_money']))->save();
				$arr['sign_result'] = true;
				$arr['order_param'] = array(
					'third_id'	=>	$notice_id,
					'pay_money'	=>	$payment_money_order['payment_money'],
					'order_id'	=>	$order_id_arr[1],
					'order_type'=>	$order_id_arr[0],
					'pay_type'	=>	$payment_money_order['pay_type'],
					'error'		=>	'0',
					'is_mobile'	=>	intval($payment_money_order['is_mobile_pay']),
					'is_own'	=>	$order_id_arr[2] ? 1 : 0,
				);
			}
			else{
				$arr['sign_result'] = false;
				$arr['order_param'] = array();
				return $arr;
			}
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
		$signPars .= "merchant_id=".$arr['merchant_id'];
		$signPars .= "&authentication_code=".$arr['authentication_code'];
		$signPars .= "&merchant_trade_no=".$arr['merchant_trade_no'];
		$signPars .= "&total_amount=" . $arr['total_amount'];
	
		return md5($signPars);
	}

	private function getMiniSign($arr, $key){
		unset($arr['sign_type']);
		$signPars = "";
		ksort($arr);
		foreach($arr as $k => $v) {
			if("" != $v && "sign" != $k) {
				$signPars .= $k . "=" . $v . "&";
			}
		}
		$signPars .= "key=" . $key;
	
		return md5($signPars);
	}
}
