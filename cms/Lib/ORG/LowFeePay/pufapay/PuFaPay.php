<?php

class PuFaPay{
	private $pufa_org_id;
	private $pufa_mer_id;
	private $order_info;
	private $pay_url;
	private $back_url;
	private $check_url;
	private $sign_key;
	private $rsaPrivateKeyFilePath;
	private $rsaPublicKeyFilePath;
	/*
     *  5091：交易查询
        1131：扫码支付
        2131：扫码撤销
        3131：扫码退货
        4131：扫码冲正（关闭当前订单）
        7131: 订单创建
        1191：公众号支付
        6131: 异步通知
    */
	private $pay_type =array('weixin'=>'0000007','alipay'=>'0000008');
	private $pay_type_other =array(1191=>'weixin',1131=>'weixin_scan',3131=>'weixin_refund',5091=>'weixin_query');
	public function __construct(){
		$this->pay_url =C('config.pufa_pay_url');
		$this->check_url =C('config.pufa_check_url');
		$this->back_url = C('config.site_url').'/source/pufapay_notice.php';
		$this->pufa_org_id =C('config.pufa_org_id');
		$this->pufa_mer_id = C('config.pufa_mer_id');
		$this->rsaPrivateKeyFilePath = C('config.pufa_private_key');
		$this->rsaPublicKeyFilePath = C('config.pufa_verify_public_key');

		$this->appId = C('config.wechat_appid');
		$this->wx_appId = C('config.pay_wxapp_appid');
	}


	public function __set($property, $value) {
		$this->$property = $value;
	}

	public function notice($sign_data){
		$sign_data=  json_decode($sign_data,true);
		$result = $this->verify($sign_data['msg_body'],$sign_data['signature']);

		fdump($result,'sign_data',1);

		$sign_data['msg_body'] = json_decode($sign_data['msg_body'] ,true);
		fdump($sign_data,'sign_data',1);
		if($result ){

			$msg_body = $sign_data['msg_body'];
			$arr['sign_result'] = true;
			$order_id = explode('_',$msg_body['order_id']);
			$arr['order_param'] = array(
					'third_id'=>$msg_body['sys_order_id'],
					'pay_money'=>floatval($msg_body['tran_amt']/100),
					'order_id'=>$order_id[1],
					'order_type'=>$order_id[0],
					'pay_type'=>$msg_body['biz_cd']=='0000007'?'weixin':'',
					'error'=>$sign_data['orderStatus']==1?0:1,
					'is_mobile'=>1,
					'is_own'=>$order_id[2]?1:0,
			);
		}else{
			$arr['sign_result'] = false;
			$arr['order_param'] = array();
		}

		return $arr;
	}


	public function refund($order){
		$request = $this->_encode_refund_request($order);
		$request = json_encode($request);
		fdump($request,'request');
		$res = httpRequest($this->pay_url,'POST',$request,$header);

		$res = json_decode($res[1],true);

		$msg_body = json_decode($res['msg_body'],true);
		$order_id = explode('_',$msg_body['order_id']);

		if($msg_body['resp_cd']=='00'){
			$go_refund_param['type']='ok';
			$go_refund_param['error'] = 0;
		}else{
			$go_refund_param['error'] = 1;
		}
		$go_refund_param['refund_param']  = serialize($msg_body);


		return $go_refund_param;
	}

	public function check($order){
		$request = $this->_encode_check_request($order);
		$request = json_encode($request);
		//$header = $this->set_header($request);
		$res = httpRequest($this->check_url,'POST',$request,$header);

		$res = json_decode($res[1],true);

		$msg_body = json_decode($res['msg_body'],true);
		$order_id = explode('_',$msg_body['order_id']);
		dump($msg_body);

		$go_pay_param = array(
				'third_id'=>$msg_body['sys_order_id'],
				'pay_money'=>$order['pay_money'],
				'order_id'=>$order_id[1],
				'order_type'=>$order_id[0],
				'pay_type'=>$this->pay_type[$order['pay_type']],
				'error'=>( $msg_body['resp_cd']=='00'&&$msg_body['resp_msg']=='交易成功' )?0:1,
				'is_mobile'=>0,
				'errorMsg'=>$msg_body['resp_msg'],
				'pay_time'=>$_SERVER['REQUEST_TIME'],
				'is_own'=>0,
		);

		return $go_pay_param;
	}


	public function pay($order,$payinfo=array()){
		$this->order_info  = $order;
		$appid = $this->appId;

		$request = $this->_encode_payment_request($order['order_id'],$order['order_total_money'],$order['openid'],$appid,$payinfo['auth_code'],$order['pay_type']);

		$request = json_encode($request);

//		$header = $this->set_header($request);
		$res = httpRequest($this->pay_url ,'POST',$request);

		$pay_key  = array_search($this->order_info['pay_type'],$this->pay_type_other);
		$res = json_decode($res[1],true);
		$msg_body = json_decode($res['msg_body'],true);

		$msg_body['qr_code_info']['wx_jsapi'] =$wx_jsapi= json_decode($msg_body['qr_code_info']['wx_jsapi'],true);
		$res['msg_body'] = $msg_body;
		fdump($request,'res');
		fdump($res,'res',1);
		switch($pay_key){
			//主扫微信
			case 10:
				if(!$res){
					$returnArr = array(
							'error'=>1,
							'msg'=>'用户支付中，需要输入密码！请询问用户输入完成后，再点击“确认支付”',
					);
				}else if($res['head']['resCode']=='900002'){
					$returnArr = array(
							'error'=>1,

							'msg'=>$res['head']['resMessage'],
					);
				}else if($res['head']['resCode']=='000000'){
					$returnArr = array(
							'error'=>0,
							'msg'=>'交易成功',
							'thirdid'=>$res['orderNo'],
					);
				}

				break;
			case 11:
				$returnArr = array(
						'status'=>1,
						'info'=>$res['qrCodeStr'],
						'orderid'=>$order['order_id'],
				);
				break;
			//微信公众号
			case 1191:
			case 1131:
				$jsApiObj["appId"] = $wx_jsapi['appId'];
				$jsApiObj["timeStamp"] = $wx_jsapi['timeStamp'];
				$jsApiObj["nonceStr"] = $wx_jsapi['nonceStr'];
				$jsApiObj["package"] = $wx_jsapi['package'];
				$jsApiObj["signType"] = $wx_jsapi['signType'];
				$jsApiObj["paySign"] = $wx_jsapi['paySign'];
				$returnArr['weixin_param'] = json_encode($jsApiObj);
				$returnArr['error'] = 0;
				$order_id = explode('_',$order['order_id']);
				fdump($returnArr,'returnArr');
				$returnArr['redirctUrl'] = C('config.site_url').'/index.php?c=LowFeePayNotice&a=order_back_notice&pay_type=pufapay&pay_money='.$order['order_total_money'].'&order_type='.$order_id[0].'&order_id='.$order_id[1].'&sys_order_id='.$res['msg_body']['sys_order_id'].'&source=1'.($order_id[2]?'&is_own=1':'');
				break;
			//主扫支付宝
			case 20:
				if(!$res){
					$returnArr = array(
							'error'=>1,
							'msg'=>'用户支付中，需要输入密码！请询问用户输入完成后，再点击“确认支付”',
					);
				}else if($res['head']['resCode']=='900002'){
					$returnArr = array(
							'error'=>1,
							'msg'=>$res['head']['resMessage'],
					);
				}else if($res['head']['resCode']=='000000'){
					$returnArr = array(
							'error'=>0,
							'msg'=>'交易成功',
							'thirdid'=>$res['orderNo'],
					);
				}
				break;
			case 21:
				$returnArr = array(
						'status'=>1,
						'info'=>$res['qrCodeStr'],
						'orderid'=>$order['order_id'],
				);

				break;
		}

		return $returnArr;
	}

	private function _sign_request($request) {
		if($this->checkEmpty($this->rsaPrivateKeyFilePath)){
			$priKey=$this->rsaPrivateKey;
			$res = "-----BEGIN RSA PRIVATE KEY-----\n" .
					wordwrap($priKey, 64, "\n", true) .
					"\n-----END RSA PRIVATE KEY-----";
		}else {
			$priKey = file_get_contents($_SERVER['DOCUMENT_ROOT'].$this->rsaPrivateKeyFilePath);

			$res = openssl_get_privatekey($priKey);
		}
		($res) or die('您使用的私钥格式错误，请检查RSA私钥配置');
		$r = openssl_sign($request, $sign, $res);
		return base64_encode($sign);
	}

	function verify($data, $sign) {

		if($this->checkEmpty($this->rsaPublicKeyFilePath)){

			$pubKey= $this->rsaPublicKeyFilePath;
			$res = "-----BEGIN PUBLIC KEY-----\n" .
					wordwrap($pubKey, 64, "\n", true) .
					"\n-----END PUBLIC KEY-----";
		}else {
			//读取公钥文件
			$pubKey = file_get_contents($_SERVER['DOCUMENT_ROOT'].$this->rsaPublicKeyFilePath);
			//转换为openssl格式密钥
			$res = openssl_get_publickey($pubKey);
		}

		($res) or die('RSA公钥错误。请检查公钥文件格式是否正确');

		//调用openssl内置方法验签，返回bool值
		$result = (bool)openssl_verify($data, base64_decode($sign), $res);

		if(!$this->checkEmpty($this->rsaPublicKeyFilePath)) {
			//释放资源
			openssl_free_key($res);
		}

		return $result;
	}

	function _encode_payment_request($order_id, $amount, $openid='',$appId='',$authCode='',$pay_type='') {
		$payment_request = array(
				'version' => '1.0.0',
				'encoding'=>'UTF-8',
				'sign_method'=>'01',

		);


		$msg_body = json_encode(array(
				"ins_id_cd" => $this->pufa_org_id,
				"chn_ins_id_cd" => $this->pufa_org_id,
				"prod_cd" => '1151',
				"biz_cd" => $this->pay_type[$pay_type],
				"acc_mod" => '01',
				"mcht_cd" => $this->pufa_mer_id,
				"tran_dt_tm" => strval(date('YmdHis',time())),
				"order_id" => $order_id,
				"tran_amt" => strval($amount*100),
				"qr_code_info" => array(
						"noti_url"=>$this->back_url,
						"sub_user_id"=>$openid,
						"sub_app_id"=>$appId,
				),
				"tran_cd" => strval(array_search($this->order_info['pay_type'],$this->pay_type_other)),
		));
		$sign = $this->_sign_request($msg_body);
		$payment_request['signature'] = $sign;
		$payment_request['msg_body'] = $msg_body;
		return $payment_request;
	}

	function _encode_refund_request($order) {
		$refund_request = array(
				'version' => '1.0.0',
				'encoding'=>'UTF-8',
				'sign_method'=>'01',

		);

		$msg_body = json_encode(array(
				"ins_id_cd" => $this->pufa_org_id,
				"chn_ins_id_cd" => $this->pufa_org_id,
				"prod_cd" => '1151',
				"biz_cd" => $this->pay_type[$order['pay_type']],
				"acc_mod" => '01',
				"mcht_cd" => $this->pufa_mer_id,
				"tran_dt_tm" => strval(date('YmdHis',$order['pay_time'])),
				"order_id" =>  $order['orderNo'],
				"tran_amt" => strval($order['payment_money']*100),

				"orig_sys_order_id" => $order['third_id'],
				"orig_tran_dt" => strval(date('Ymd',$order['pay_time'])),
				"tran_cd" => '3131',
		));
		$sign = $this->_sign_request($msg_body);
		$refund_request['signature'] = $sign;
		$refund_request['msg_body'] = $msg_body;

		return $refund_request;
	}

	function _encode_check_request($order) {
		$check_request = array(
				'version' => '1.0.0',
				'encoding'=>'UTF-8',
				'sign_method'=>'01',

		);


		$msg_body = json_encode(array(
				"ins_id_cd" => $this->pufa_org_id,
				"chn_ins_id_cd" => $this->pufa_org_id,
				"prod_cd" => '1151',
				"biz_cd" =>  $this->pay_type[$order['pay_type']],
				"acc_mod" => '01',
				"mcht_cd" => $this->pufa_mer_id,
				"order_id" => $order['orderNo'],
				"orig_sys_order_id" => $order['sys_order_id'],
				"tran_cd" => '5091',
		));
		$sign = $this->_sign_request($msg_body);
		$check_request['signature'] = $sign;
		$check_request['msg_body'] = $msg_body;
		return $check_request;
	}
	function set_header($request) {
		$header = array(
				"Content-type: application/json;charset='utf-8'",
				'Content-Encoding: UTF-8',
				'mercId: '.$this->mercId,
				'sign: '.$this->_sign_request($request),
		);
		return $header;
	}

	protected function checkEmpty($value) {
		if (!isset($value))
			return true;
		if ($value === null)
			return true;
		if (trim($value) === "")
			return true;

		return false;
	}



}


