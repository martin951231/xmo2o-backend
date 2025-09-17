<?php
/**
 * 客户定制
 * 支持 支付宝/微信等支付方式
 * 文档  https://www.showdoc.cc/238445142000034
 * author by lumin  2019-05-20
 */
class Weikebaba{
	private 		$wxappid ;// 微信公众号 appid
	private 		$wxappid_mini ;// 微信小程序 appid
	private 		$key ;// 签名秘钥
	private 		$wxapp_notify_url ;//支付回调通知
	private 		$scan_notify_url ;//支付回调通知
	private         $request_url ;//微信小程序支付请求url
	public function __construct(){
		$this->user_id 			= C('config.weikebaba_system_user_id');
		$this->wxappid 			= C('config.weikebaba_system_wxappid');//微信公众号appid
		$this->wxappid_mini		= C('config.weikebaba_system_wxappid_mini');//微信小程序appid
		$this->key 				= C('config.weikebaba_system_key');//商户签名key
		$this->wxapp_notify_url = C('config.site_url').'/source/weikebaba_page_notice.php';
		$this->scan_notify_url  = C('config.site_url').'/source/weikebaba_scan_page_notice.php';
		$this->request_url      = 'https://wkopen.weikebaba.com/wips-service/unifiedPay';//接口地址
	}

	//调起支付
	public function pay($order){
		$order_id_arr = explode('_',$order['order_id']);
		M('Payment_money_order')->where(array('orderid'=>$order_id_arr[1],'order_type'=>$order_id_arr[0]))->data(array('pay_method'=>'weikebaba','orderNo'=>$order['order_id'],'is_own'=>intval($order_id_arr[2])))->save();
		import('ORG.Net.Http');
		$http = new Http();
		if($order['pay_type'] == 'weixin')//微信公众号支付
		{
			$pay_way = 'wips.wx.jspay';
			$wxappid = $this->wxappid;
		}
		elseif($order['pay_type'] == 'weixin_wxapp'){//微信小程序
			$pay_way = 'wips.wx.simpay';	
			$wxappid = $this->wxappid_mini;
		}
		$pay_post_data = [
			'pay_way'			=> $pay_way,//固定参数
			'user_id' 			=> $this->user_id,//由平台分配的用户标识ID
			'out_trade_no'  	=> $order['order_id'],
			'sub_openid'		=> $order['openid'],
			'body' 				=> $order['order_name'],
			'total_fee'			=> $order['order_total_money']*100,
			'mch_create_ip'		=> get_client_ip(),
			'notify_url'		=> $this->wxapp_notify_url,
			'pay_type'			=> 1,
			'sub_appid'			=> $wxappid,
		];
		if($order['pay_type'] == 'weixin_wxapp'){
			$pay_post_data['minipg'] = 1;
		}
		
		if($order['pay_type'] == 'alipay'){
			$pay_wap = 'wips.ali.jspay';
			$pay_post_data = [
				'pay_way'			=> $pay_way,//固定参数
				'user_id' 			=> $this->user_id,//由平台分配的用户标识ID
				'out_trade_no'  	=> $order['order_id'],
				'body' 				=> $order['order_name'],
				'total_fee'			=> $order['order_total_money']*100,
				'mch_create_ip'		=> get_client_ip(),
				'notify_url'		=> $this->wxapp_notify_url,
				'pay_type'			=> 2,
				'buyer_id'			=> $order['alipay_uid'],
			];
		}		
		
		$pay_post_data['sign'] = $this->getSign($pay_post_data);
		$data = $this->toJson($pay_post_data);
		$return = Http::curlPost($this->request_url, $data);
		if($return['status'] == '0'){
			if($return['result_code'] == '0'){
				if($order['pay_type'] == 'alipay'){
					$returnArr = array(
						'error' => 0,
						'alipay_param' => json_encode($return['pay_info']),
						'redirctUrl'   => C('config.site_url').'/index.php?c=LowFeePayNotice&a=order_back_notice&pay_type=weikebaba&order_type='.$order_id_arr[0].'&order_id='.$order_id_arr[1].'&source=1'.($order_id_arr[2] ? '&is_own=1' : ''),
					);	
				}
				else{
					$returnArr = array(
						'error' => 0,
						'weixin_param' => json_encode($return['pay_info']),
						'redirctUrl'   => C('config.site_url').'/index.php?c=LowFeePayNotice&a=order_back_notice&pay_type=weikebaba&order_type='.$order_id_arr[0].'&order_id='.$order_id_arr[1].'&source=1'.($order_id_arr[2] ? '&is_own=1' : ''),
					);	
				}
			}
			else{
				$returnArr = array(
					'error'=>1,
					'msg'=>'支付错误：'.$return['err_code'].' '.$return['err_msg'],
				);
			}
		}
		else{
			$returnArr = array(
				'error'=>1,
				'msg'=>'通信错误：'.$return['status'].'|'.$return['result_code'].$return['message'],
			);
		}
		
		return $returnArr;
	}

	public function refund($order){
		
		$pay_post_data = array(
			'pay_way' => 'wips.refund',
			'user_id' => $this->user_id,
			'out_trade_no' => $order['orderNo'],
			'out_refund_no' => substr($order['order_type'].'_'.$order['orderid'].'_'.$_SERVER['REQUEST_TIME'],0,32),
			'total_fee' => strval($order['payment_money']*100),
			'refund_fee' => strval($order['payment_money']*100),
			'op_user_id' => $this->user_id,
		);
		
		$pay_post_data['sign'] = $this->getSign($pay_post_data);
		
		
		//请求
		import('ORG.Net.Http');
		$data = $this->toJson($pay_post_data);
		$return = Http::curlPost($this->request_url, $data);
		
		if($return['status'] == '0'){
			if($return['result_code'] == '0'){
				$returnArr = array(
					'error' => 0,
					'type' => 'ok',
					'refund_money'   => $return['refund_fee'],
					'refund_param'   => serialize($return),
				);
			}
			else{
				$returnArr = array(
					'error'=>1,
					'msg'=>'支付错误：'.$return['err_code'].' '.$return['err_msg'],
				);
			}
		}
		else{
			$returnArr = array(
				'error'=>1,
				'msg'=>'通信错误：'.$return['status'].'|'.$return['result_code'].$return['message'],
			);
		}

		return $returnArr;
	}

	public function scan_pay($order_info){
		$session_key = 'wft_order_userpaying_'.$order_info['auth_code'];
		
		if($_SESSION[$session_key] || $order_info['re_call']){
			$pay_post_data = array(
				'pay_way' => 'wips.query',
				'user_id' => $this->user_id,
				'out_trade_no' => $order_info['out_trade_no']
			);
		}else{
			$pay_post_data = array(
				'pay_way' => 'wips.micropay',
				'user_id' => $this->user_id,
				'out_trade_no' => $order_info['out_trade_no'],
				'body' => $order_info['order_name'],
				'total_fee' => $order_info['total_fee']*100,
				'mch_create_ip' => get_client_ip(),
				'auth_code' => $order_info['auth_code'],
				'notify_url' => $this->scan_notify_url,
				'pay_type' => $order_info['pay_type']
			);
		}

		$pay_post_data['sign'] = $this->getSign($pay_post_data);
		
		
		//请求
		import('ORG.Net.Http');
		$data = $this->toJson($pay_post_data);
		$return = Http::curlPost($this->request_url, $data);
		
		if($return['status'] == '0'){
			if($return['result_code'] == '0'){
				unset($_SESSION[$session_key]);
				$returnArr = array(
					'return_code'	=>	'TRUE',
					'result_code'	=>	'TRUE',
					'transaction_id'=>	$return['transaction_id'],
					'total_fee'		=>	$order_info['total_fee']*100,
					'openid'		=>	'',
					'alipay_uid'	=>	'',
				);
				if($return['trade_state']){
					$returnArr['trade_state'] = $return['trade_state'];
				}
				if($order_info['pay_type'] == '1'){
					$returnArr['pay_type'] = 'weixin';
				}else if($order_info['pay_type'] == '2'){
					$returnArr['pay_type'] = 'alipay';
				}
			}
			else{
				if($return['err_code'] == 'USERPAYING'){
					$returnArr = array(
						'result_code' => 'TRUE',
						'result_code'=>'FAIL',
						'result_msg'=>'用户支付中，需要输入密码！请询问用户输入完成后，再点击“确认支付”',
						'err_code'=>'USERPAYING',
					);
				}
				else{
					$returnArr = array(
						'result_code'=>'FAIL',
						'err_code_des'=>'支付错误：'.$return['err_code'].' '.$return['err_msg'],
					);
				}
			}
		}
		else{
			$returnArr = array(
				'return_code'=>'FAIL',
				'return_msg'=>'通信错误：'.$return['status'].'|'.$return['result_code'].$return['message'],
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
		if($payment_money_order['pay_type'] == 'weixin'){
			$pay_type = 'weixin_wxapp';
		}
		if(empty($pay_type)){
			$this->error_tips('支付方式不存在');
		}
		
		
		$pay_post_data = array(
			'pay_way' => 'wips.query',
			'user_id' => $this->user_id,
			'nonce_str' => createRandomStr(32),
			'out_trade_no' => $orderNo
		);
		
		$pay_post_data['sign'] = $this->getSign($pay_post_data);
		
		//转换成xml
		$json = $this->toJson($pay_post_data);
		
		//请求
		import('ORG.Net.Http');
		$http = new Http();
		$return = Http::curlPost($this->request_url, $json);
		
		if($return['status'] == '0'){
			if($return['result_code'] == '0'){
				M('Payment_money_order')->where(array('id'=>$payment_money_order['id']))->data(array('paid_money'=>$total_fee['total_fee']/100))->save();
			
				$go_pay_param = array(
					'third_id'		=>	$return['transaction_id'],
					'pay_money'		=>	$return['total_fee']/100,
					'order_id'		=>	$order_id_arr[1],
					'order_type'	=>	$order_id_arr[0],
					'pay_type'		=>	$payment_money_order['pay_type'],
					'error'			=>	0,
					'is_mobile'		=>	intval($payment_money_order['is_mobile_pay']),
					'errorMsg'		=>	'',
					'pay_time'		=>	strtotime($return['time_end']),
					'is_own'		=>	$order_id_arr[2] ? 1 : 0,
				);
				return $go_pay_param;
			}
			else{
				$returnArr = array(
					'error'=>1,
					'msg'=>'支付错误：'.$return['err_code'].' '.$return['err_msg'],
				);
			}
		}
		else{
			$returnArr = array(
				'error'=>1,
				'msg'=>'通信错误：'.$return['status'].'|'.$return['result_code'].$return['message'],
			);
		}
	}

	public function notice($sign_data){
		
		
		$array_data = json_decode($sign_data, true);
		
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
				'error'		=>	$array_data['pay_result'],
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

	private function toJson($arr){
		$json = json_encode($arr);
		
		return $json;
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