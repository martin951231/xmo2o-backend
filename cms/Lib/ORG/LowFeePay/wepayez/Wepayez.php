<?php
/**
 * 澳洲 wepayez支付
 * 支持 支付宝等支付方式
 * 文档  哈客产品发送的pdf文档
 * author by lumin  2019-05-07 
 */
class Wepayez{
	private 		$mch_id ;// 商户ID
	private 		$key ;// 签名秘钥
	private 		$alipay_notify_url ;//支付回调通知
	private         $request_alipay_url ;//url
	public function __construct(){
		$this->mch_id 			= C('config.pay_wepayez_mchid');
		$this->key 				= C('config.pay_wepayez_key');
		$this->alipay_notify_url = C('config.site_url').'/source/wepayez_page_notice.php';
		$this->request_alipay_url= 'https://gateway.wepayez.com/pay/gateway';//url
	}

	//调起支付
	public function pay($order){
		$order_id_arr = explode('_',$order['order_id']);
		M('Payment_money_order')->where(array('orderid'=>$order_id_arr[1],'order_type'=>$order_id_arr[0]))->data(array('pay_method'=>'wepayez','orderNo'=>$order['order_id'],'is_own'=>intval($order_id_arr[2])))->save();
		import('ORG.Net.Http');
		$http = new Http();
		if($order['pay_type'] == 'alipay')//app支付宝支付
		{
			$pay_post_data = [
				'service'			=> 'pay.alipay.app.intl',
				'mch_id' 			=> $this->mch_id,
				'nonce_str'			=> createRandomStr(32),
				'notify_url'		=> $this->alipay_notify_url,
				'out_trade_no'  	=> $order['order_id'],
				'body' 				=> $order['order_name'],
				'mch_create_ip'		=> get_client_ip(),
				'total_fee'			=> $order['order_total_money']*100
			];		
			$pay_post_data['sign'] = $this->getSign($pay_post_data);
			$xml = $this->toXml($pay_post_data);
			$return = Http::curlPostXml($this->request_alipay_url, $xml);
			if($return['status'] == '0'){
				if($return['result_code'] == '0'){
					$returnArr = array(
						'error' => 0,
						'param' => $return['pay_info'],
						'redirctUrl'   => C('config.site_url').'/index.php?c=LowFeePayNotice&a=order_back_notice&pay_type=wepayez&order_type='.$order_id_arr[0].'&order_id='.$order_id_arr[1].'&source=1'.($order_id_arr[2] ? '&is_own=1' : ''),
					);
				}
				else{
					$returnArr = array(
						'error'=>1,
						'msg'=>'支付返回：'.$return['err_code'].$return['err_msg'],
					);
				}
			}
			else{
				$returnArr = array(
					'error'=>1,
					'msg'=>'支付返回：'.$return['message'],
				);	
			}
		}
		
		return $returnArr;
	}

	public function refund($order){
		
		$pay_post_data = array(
			'service' => 'unified.trade.refund',
			'mch_id' => $this->mch_id,
			'nonce_str' => createRandomStr(32),
			'out_trade_no' => $order['orderNo'],
			'out_refund_no' => substr($order['order_type'].'_'.$order['orderid'].'_'.$_SERVER['REQUEST_TIME'],0,32),
			'total_fee' => strval($order['payment_money']*100),
			'refund_fee' => strval(($order['payment_refund_money'] ? $order['payment_refund_money'] : $order['payment_money'] )*100),
			'op_user_id' => $this->mch_id
		);
		
		$pay_post_data['sign'] = $this->getSign($pay_post_data);
		
		//转换成xml
		$xml = $this->toXml($pay_post_data);
		
		//请求
		import('ORG.Net.Http');
		$http = new Http();
		$refundQueryResult = Http::curlPostXml($this->request_alipay_url, $xml);
		
		if($refundQueryResult['status'] == '0'){
			if($refundQueryResult['result_code'] == '0'){
				$returnArr = array(
					'error' => 0,
					'type' => 'ok',
					'refund_money'   => $refundQueryResult['refund_fee'],
					'refund_param'   => serialize($refundQueryResult),
				);
			}
			else{
				$returnArr = array(
					'error'=>1,
					'msg'=>'支付返回：'.$refundQueryResult['err_code'].$refundQueryResult['err_msg'],
				);
			}
		}
		else{
			$returnArr = array(
				'error'=>1,
				'msg'=>'支付返回：'.$refundQueryResult['message'],
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
		
		$pay_type = 'alipay';		
		
		$pay_post_data = array(
			'service' => 'unified.trade.query',
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
		$orderQueryResult = Http::curlPostXml($this->request_alipay_url, $xml);
		
		if($orderQueryResult['status'] == '0'){
			if($orderQueryResult['result_code'] == '0'){
				if($orderQueryResult['trade_state'] == 'SUCCESS'){
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
				else{
					$trade_state = array(
						'REFUND' => '转入退款',
						'NOTPAY' => '未支付',
						'CLOSED' => '已关闭',
						'REVERSE' => '已冲正',
						'REVOKE' => '已撤销',
					);
					return array('error'=>1,'msg'=>$trade_state[$orderQueryResult['trade_state']], 'status' => $orderQueryResult['trade_state']);	
				}
			}
			else{
				return array('error'=>1,'msg'=>'通信出错：'.$orderQueryResult['err_code'].$orderQueryResult['err_msg']);	
			}
		}
		else{
			return array('error'=>1,'msg'=>'通信出错：'.$orderQueryResult['message']);
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