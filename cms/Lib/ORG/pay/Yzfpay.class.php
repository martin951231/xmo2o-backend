<?php
include(dirname(__FILE__) . DIRECTORY_SEPARATOR."/Yzf/phpseclib1.0.1/Crypt/AES.php");
include(dirname(__FILE__) . DIRECTORY_SEPARATOR."/Yzf/phpseclib1.0.1/Crypt/RSA.php");

class Yzfpay{
	protected $order_info;
	protected $pay_money;
	protected $pay_type;
	protected $is_mobile;
	protected $pay_config;
	protected $user_info;
	protected $pay_url;
	protected $URL;
	protected $interface;
	protected $header = array(
			"Content-type: application/json;charset='utf-8'",
			'Content-Encoding: UTF-8',
	);
	private  $PUBLIC_KEY  ;
	private  $OFFLINE_PUBLIC_KEY  ;
	private  $PAY_OFFLINE2 =false  ;
	private  $PAY_OFFLINE_OWN =false  ;
	private  $store_id   ;

	public function __construct($order_info,$pay_money,$pay_type,$pay_config,$user_info,$is_mobile=0){
		$this->order_info = $order_info;
		$this->pay_money  = $pay_money;
		$this->pay_type   = $pay_type;
		$this->is_mobile   = $is_mobile;
		$this->pay_config = $pay_config;
		$this->user_info  = $user_info;
		if($pay_config['pay_yzf_mer_id']){
			$this->mer_id  = $pay_config['pay_yzf_mer_id'];
			$this->cert_key  = $pay_config['pay_yzf_cert_key'];
			$this->cert_path  = $pay_config['pay_yzf_p12'];
			$this->PUBLIC_KEY  = $pay_config['pay_yzf_public_key'];
		}else{
			$this->mer_id  = C('config.pay_yzf_mer_id');
			$this->cert_key  = C('config.pay_yzf_cert_key');
			$this->cert_path  = C('config.pay_yzf_p12');
			$this->PUBLIC_KEY  = C('config.pay_yzf_public_key');
			$this->pay_config['is_own'] = 0;
		}

		$this->store_id = $pay_config['pay_yzf2_store_id'];


		if($pay_config['pay_yzf_mer_id']!=''&& $pay_config['open']==1){
			$this->offline_mer_id  = $pay_config['pay_yzf_mer_id'];
			$this->offline_cert_key  = $pay_config['pay_yzf_cert_key'];
			$this->offline_cert_path  = $pay_config['pay_yzf_p12'];
			$this->OFFLINE_PUBLIC_KEY  = $pay_config['pay_yzf_public_key'];
		}else  if($pay_config['open_offline2']==1){
			$this->mer_id = $pay_config['pay_yzf2_mer_id'];
			$this->date_key = $pay_config['pay_yzf2_date_key'];
			$this->PAY_OFFLINE2 = true;
		}else if(C('config.open_yzfpay_offline')==1 && $pay_config['pay_yzf_offline_mer_id'] && $pay_config['open_offline']==1){
			$this->PAY_OFFLINE_OWN = true;
			$this->offline_mer_id  = $pay_config['pay_yzf_offline_mer_id'];
			$this->offline_cert_key  = $pay_config['pay_yzf_offline_cert_key'];
			$this->offline_cert_path  = $pay_config['pay_yzf_offline_p12'];
			$this->OFFLINE_PUBLIC_KEY  = $pay_config['pay_yzf_offline_public_key'];
		}


		$this->URL="https://capi.bestpay.com.cn";
		$this->interface = $this->URL."/common/interface";
		$this->pay_url = $this->URL."/gateway.pay";
		$this->pay_url2 = "https://webpaywg.bestpay.com.cn/barcode/placeOrder";
		$this->pay_web_url = "https://mapi.bestpay.com.cn/mapi/form/cashier/evoke";
		$this->create_order_url = "https://mapi.bestpay.com.cn/mapi/uniformReceipt/proCreateOrder";
		$this->query_order_url = "https://mapi.bestpay.com.cn/mapi/uniformReceipt/tradeQuery";
		$this->scan_pay_url = "https://mapi.bestpay.com.cn/mapi/uniformReceipt/barCodePay";
		$this->refund_url = "https://mapi.bestpay.com.cn/mapi/uniformReceipt/tradeRefund ";
		$this->notice_url = C('config.site_url').'/source/yzf_notice.php';
		$this->app_notice_url = C('config.site_url').'/source/yzf_app_notice.php';
		$this->order_info['order_trad_no'] =$this->order_info['order_type'].'_'.$this->order_info['order_id'].($this->pay_config['is_own'] ? '_1' : '');
		$this->back_url = C('config.site_url').'/index.php?c=LowFeePayNotice&a=yzf_order_back_notice&order_type='
				.$this->order_info['order_type'].'&order_id='.$this->order_info['order_id']
				.'&source='.$this->is_mobile.'&is_own='.($this->pay_config['is_own'] ? 1: 0).'&mer_id='.intval($this->order_info['mer_id']);

		$this->order_info['order_name'] = $this->order_info['order_name']?$this->order_info['order_name']:$this->order_info['order_id'];
	}

	public function createSign($data = '',$offline=false)
	{
		if($offline){

			$cer_key = file_get_contents($_SERVER['DOCUMENT_ROOT'].$this->offline_cert_path); //获取密钥内容
			openssl_pkcs12_read($cer_key, $certs,$this->offline_cert_key);//证书密码
		}else{

			$cer_key = file_get_contents($_SERVER['DOCUMENT_ROOT'].$this->cert_path); //获取密钥内容
			openssl_pkcs12_read($cer_key, $certs,$this->cert_key);//证书密码
		}

		openssl_sign($data, $signMsg, $certs['pkey'], OPENSSL_ALGO_SHA256); //注册生成加密信息
		return $signMsg ? base64_encode($signMsg) : "";
	}



	public function pay(){
		if(!$this->pay_config['pay_yzf_mer_id']){
			$this->mer_id  = C('config.pay_yzf_mer_id');
		}
		if($this->is_mobile == 2){
			return $this->app_pay();
		}elseif($this->is_mobile){
			return $this->mobile_pay();
		}else{
			return $this->web_pay();
		}
	}


	public function web_pay(){
		$pay_order = $this->get_pay_order();

		if(!$pay_order['success']){
			return array('error'=>true,'msg'=>$pay_order['errorMsg']);
		}
		$pay_order = $pay_order['result'];
		$time = date("Y-m-d H:i:s");
		$order = $this->order_info;
		$params = array(
			"institutionCode"=>$this->mer_id,
			"institutionType"=>"MERCHANT",
			"service"=>"mobile.securitypay.pay",
			"merchantNo"=>$this->mer_id,
			"subMerchantNo"=>"",
			"outTradeNo"=>$this->order_info['order_trad_no'],
			"tradeprodNo"=>$pay_order['tradeprodNo'],
			"tradeNo"=>$pay_order['tradeNo'],
			"tradeAmt"=>$this->pay_money*100,
			"ccy"=>"156",
			"subject"=>$order['order_name'],
			"goodsInfo"=>$order['order_name'],
			"mediumType"=>"WIRELESS",
			"tradeChannel"=>"WEB",
			"requestDate"=>$time,
			"frontNotifyUrl"=>$this->back_url,
			"bankCode"=>"",
			"signType"=>"CA",

		);

		ksort($params);
		$paramsJoined = array();
		foreach($params as $param => $value) {
			$paramsJoined[] = "$param=$value";
		}
		$paramData = implode('&', $paramsJoined);
		$sign = $this->createSign($paramData);
		$params['sign'] = $sign;

		$form = $this->create_pay_form($params);
		return array('error'=>0,'form'=>$form);
	}

	public function mobile_pay(){
		$pay_order = $this->get_pay_order();
		if(!$pay_order['success']){
			return array('error_code'=>true,'msg'=>$pay_order['errorMsg']);
		}
		$pay_order = $pay_order['result'];
		$encrypt_public_key = $this->get_encrypt_public_key();
		if(!$encrypt_public_key['success']){
			return array('error_code'=>true,'msg'=>'获取加密公钥失败'	);
		}
		$webUrl = $this->_encode_payment_request(0,$pay_order,$encrypt_public_key);
		return array('error'=>0,'url'=>$webUrl);
	}

	public function app_pay(){
		$pay_order = $this->get_pay_order();
		if(!$pay_order['success']){
			return array('error_code'=>true,'msg'=>$pay_order['errorMsg']);
		}
		$tradeDate = $pay_order['tradeDate'];
		$pay_order = $pay_order['result'];
		$encrypt_public_key = $this->get_encrypt_public_key();
		if(!$encrypt_public_key['success']){
			return array('error_code'=>true,'msg'=>'获取加密公钥失败'	);
		}
		$app_param = $this->_encode_payment_request(1,$pay_order,$encrypt_public_key);
		return array('error'=>0,'app_param'=>$app_param,'back_url'=>$this->back_url.'&tradeDate='.$tradeDate);
	}

	public function scan_barcode_pay($barcode){
		
		if(C('config.open_yzfpay_offline')==1 && $this->pay_config['pay_yzf_offline_mer_id'] && $this->pay_config['open_offline']==1){
			$this->PAY_OFFLINE_OWN = true;
			$this->offline_mer_id  = $this->pay_config['pay_yzf_offline_mer_id'];
			$this->offline_cert_key  = $this->pay_config['pay_yzf_offline_cert_key'];
			$this->offline_cert_path  = $this->pay_config['pay_yzf_offline_p12'];
			$this->OFFLINE_PUBLIC_KEY  = $this->pay_config['pay_yzf_offline_public_key'];
		}
		
		$time = date("Y-m-d H:i:s");
		if($this->PAY_OFFLINE2==false){
			
			if(C('config.open_yzfpay_offline')==1 && $this->PAY_OFFLINE_OWN==false){
				$this->offline_mer_id  = C('config.pay_yzf_offline_mer_id');
				$this->offline_cert_key  =  C('config.pay_yzf_offline_cert_key');
				$this->offline_cert_path  =  C('config.pay_yzf_offline_p12');
				$this->OFFLINE_PUBLIC_KEY  =  C('config.pay_yzf_offline_public_key');
			}
			$goodsDetails = [];
			if(isset($this->order_info['order_content'])){
				foreach ($this->order_info['order_content'] as $det) {
					$goodsDetails[] = [
						"id" => $det['goods_id'],
						"name" => $det['name'],
						"quantity" => $det['num'],
						"unitPrice" => getFormatNumber(round($det['price']*100))
					];
				}
			}
			$request = array(
					"institutionType"=>'MERCHANT',
					"institutionCode"=>$this->offline_mer_id,
					"merchantNo"=>$this->offline_mer_id,
					"outTradeNo"=>$this->order_info['order_trade_no'],
					"tradeAmt"=>bcmul($this->pay_money,100),
					"subject"=>$this->order_info['order_name'],
					"authCode"=>$barcode,
					"ccy"=>"156",
					"requestDate"=>$time,
					"tradeChannel"=>$this->order_info['platform'],
					"accessCode"=>"EXTERNAL_PAYMENT",
					"operator"=>$this->order_info['staff_id'],
					"notifyUrl"=>$this->notice_url,
					"remark"=>$this->pay_config['is_own']?1:0,
			);
			if(!empty($goodsDetails)){
				$request['goodsDetails'] = json_encode($goodsDetails);
			}
			ksort($request);
			$paramsJoined = array();
			foreach($request as $param => $value) {
				$paramsJoined[] = "$param=$value";
			}
			$paramData = implode('&', $paramsJoined);

			$sign = $this->createSign($paramData,true);

			$request['sign'] =$sign;

			$request = json_encode($request);
			$res = httpRequest($this->scan_pay_url,'POST',$request,$this->header);

			$res = json_decode($res[1],true);
			
			
			$data['order_type'] = $this->order_info['order_type'];
			$data['order_id'] = $this->order_info['order_id'];
			$data['tradeNo'] = $res['result']['outTradeNo'];
			$data['tradeprodNo'] =  $res['result']['tradeNo'];
			$data['addtime'] =  $time;
			$data['is_mobile'] =  $this->is_mobile;
			$data['mer_id'] =   $this->order_info['mer_id'];
			$data['offline'] =   3;
			M('Yzf_pay_info')->add($data);
			return $res;
		}else{
			$date = date('YmdHis');
			$request = array(
				"merchantId"=>$this->mer_id,
				"barcode"=>$barcode,
				"orderNo"=>$this->order_info['order_trade_no'],
				"orderReqNo"=>$this->order_info['order_trade_no'],
				"channel"=>'05',
				"busiType"=>'0000001',
				"orderDate"=>$date,
				"orderAmt"=>bcmul($this->pay_money,100),
				"productAmt"=>bcmul($this->pay_money,100),
				"attachAmt"=>'0',
				"goodsName"=>$this->order_info['order_name'],
				"storeId"=>$this->store_id,
				"backUrl"=>$this->notice_url,
				"attach"=>$this->pay_config['is_own']?1:0,
				"mac"=>'',


			);


			$request['mac'] = md5("MERCHANTID={$request['merchantId']}&ORDERNO={$request['orderNo']}&ORDERREQNO={$request['orderNo']}&ORDERDATE={$request['orderDate']}&BARCODE={$request['barcode']}&ORDERAMT={$request['orderAmt']}&KEY={$this->date_key}");

			$request['mac'] = strtoupper($request['mac']);
			$res = httpRequest($this->pay_url2,'POST',$request,array('Content-Type: application/x-www-form-urlencoded'));


			$res = json_decode($res[1],true);
			$res['result']['tradeFinishedDate'] =date('Y-m-d H:i:s');
			if($res['result']['transStatus']=='B' ){
				$res['result']['tradeStatus']='SUCCESS';
				$res['result']['tradeNo']=$res['result']['ourTransNo'];
				$res['result']['payAmt']=$res['result']['transAmt'];
			}

			if($res['result']['transStatus']=='A' ){
				$res['result']['tradeStatus']='WAITFORPAY';
			}

			return $res;
		}
	}

	public function get_pay_order(){
		$order = $this->order_info;
//		if($this->is_mobile==2){
//			$this->notice_url = $this->app_notice_url;
//		}
		$time = date("Y-m-d H:i:s");
		$merchantNo = $this->mer_id;
		$goodsDetails = [];
		if(isset($order['order_content'])){
			foreach ($order['order_content'] as $det) {
				$goodsDetails[] = [
					"id" => $det['goods_id'],
					"name" => $det['name'],
					"quantity" => $det['num'],
					"unitPrice" => getFormatNumber(round($det['price']*100))
				];
			}
		}
		//下单
		$params=array(
			"institutionType" => "MERCHANT",
			"institutionCode" => $merchantNo,
			'merchantNo' => $merchantNo."",
			'outTradeNo' => $order['order_trad_no'],
			'tradeAmt' => bcmul($this->pay_money,100),
			'ccy' => '156',
			'requestDate' => $time,
			"tradeChannel" => "H5",
			"accessCode" => "CASHIER",
			"mediumType" => "WIRELESS",
			"subject" => $order['order_name']."商品",
			"goodsInfo" =>  $order['order_name']."商品",
			"operator" => $merchantNo,
			"notifyUrl" => $this->notice_url,
			"storeCode" => $this->order_info['store_id'],
			"storeName" => $order['order_name'],
			"riskControlInfo" => json_encode(array(0 => array(
					"service_identify" => "10000001",
					"subject" =>  $order['order_name'],
					"product_type" => "3",
					'body'=> '',
					"goods_count" => "1"
			)))
		);
		if(!empty($goodsDetails)){
			$params['goodsDetails'] = json_encode($goodsDetails);
		}

		// if(!$this->store_id){
		// 	unset($params['storeCode']);
		// }

		ksort($params);
		$paramsJoined = array();
		foreach($params as $param => $value) {
			$paramsJoined[] = "$param=$value";
		}
		$paramData = implode('&', $paramsJoined);
		$sign = $this->createSign($paramData);
		$params['sign'] = $sign;
		$params = json_encode($params);
		$res = httpRequest($this->create_order_url,'POST',$params,$this->header);
		$res = json_decode($res[1],true);
		fdump($params,'res');
		fdump($res,'res',1);
		$res['tradeDate'] = $time;

		$data['order_type'] = $this->order_info['order_type'];
		$data['order_id'] = $this->order_info['order_id'];
		$data['tradeNo'] = $res['result']['outTradeNo'];
		$data['tradeprodNo'] =  $res['result']['tradeprodNo'];
		$data['addtime'] =  $time;
		$data['is_mobile'] =   $this->is_mobile;
		$data['mer_id'] =   $this->order_info['mer_id'];
		M('Yzf_pay_info')->add($data);
		return $res;
	}

	public function get_public_key($pubKey){
		$p_key = "-----BEGIN PUBLIC KEY-----\n" .
				wordwrap($pubKey, 64, "\n", true) .
				"\n-----END PUBLIC KEY-----";
		return $p_key;
	}

	 private function getPublicKey(){

        $publicKey_path = $this->PUBLIC_KEY;

		 $certificateCAcerContent = file_get_contents($_SERVER['DOCUMENT_ROOT'].$publicKey_path);
		 $certificateCApemContent =  '-----BEGIN CERTIFICATE-----'.PHP_EOL
				 .chunk_split(base64_encode($certificateCAcerContent), 64, PHP_EOL)
				 .'-----END CERTIFICATE-----'.PHP_EOL;

		 $pubkeyid = openssl_get_publickey($certificateCApemContent);

        return $pubkeyid;
    }
	
    public function verifySign($data = '', $sign = ''){
        if (!is_string($sign) || !is_string($sign)) {
            return false;
        }
        return openssl_verify($data, base64_decode($sign), $this->getPublicKey(), OPENSSL_ALGO_SHA1);
    }
	

	
	public function get_encrypt_public_key(){
		$request = array(
			'keyIndex'=>'',
			'encryKey'=>'',
			'encryStr'=>'',
			'interCode'=>'INTER.SYSTEM.001',
			'version'=>'v1.3',
		);
		$request = json_encode($request);
		$res = httpRequest($this->interface,'POST',$request,$this->header);
		$res = json_decode($res[1],true);
		return $res;
	}

	public function _encode_payment_request($is_app=0,$pay_order,$encrypt_public_key){
		$encrypt_public_key = $encrypt_public_key['result'];
		$rand_data32 =generate_password(32);
		$pubKey = $this->get_public_key($encrypt_public_key['pubKey']);
		$pu_key = openssl_pkey_get_public($pubKey);
		$r = openssl_public_encrypt($rand_data32, $encrypted, $pu_key);
		$request = array(
				'keyIndex'=>$encrypt_public_key['keyIndex'],
				'encryKey'=>base64_encode($encrypted),
				'encryStr'=>'',
				'platform'=>'wap_3.0',
		);

		$ntime = date("YmdHis");
		$merchantNo = $this->mer_id;
		$order = $this->order_info;
		$encryStr_paramData = array(
				"INSTITUTIONCODE" => $merchantNo,
				"INSTITUTIONTYPE" => "MERCHANT",
				"SERVICE" => "mobile.securitypay.pay",
				"MERCHANTID" => $merchantNo,
				"SUBMERCHANTID" => "deprecated",
				"BEFOREMERCHANTURL" => C('config.site_url').'/source/yzf_pay_back.php',   //前端地址
				"BACKMERCHANTURL" => "deprecated",
				"SIGNTYPE" => "CA",
				"ORDERSEQ" => $pay_order['tradeprodNo'], //订单
				"ORDERREQTRANSEQ" => $pay_order['outTradeNo'],
				"TRADENO" => $pay_order['tradeNo'],
				"ORDERTIME" => $ntime,
				"ORDERVALIDITYTIME" => "",
				"ORDERAMOUNT" => $this->pay_money,
				"CURTYPE" => "156",
				"PRODUCTDESC" => $order['order_name'],
				"SUBJECT" =>  $order['order_name'],
				"PRODUCTAMOUNT" =>  $this->pay_money,
				"ATTACHAMOUNT" => "0.00",
//				"ACCOUNTID" => "",
				"CUSTOMERID" => "12345678901",
				"SWTICHACC" => "true",
				"BUSITYPE" => "04"
		);

		ksort($encryStr_paramData);

		$paramsJoined = array();
		foreach($encryStr_paramData as $param => $value) {
			$paramsJoined[] = "$param=$value";
		}
		$paramData = implode('&', $paramsJoined);

		//签名
		$sign_param = array(
				"INSTITUTIONCODE" => $merchantNo,
				"INSTITUTIONTYPE" => "MERCHANT",
				"SERVICE" => "mobile.securitypay.pay",
				"MERCHANTID" => $merchantNo,
				"SUBMERCHANTID" => "deprecated",
				"ORDERSEQ" => $pay_order['tradeprodNo'], //订单
				"ORDERREQTRANSEQ" => $pay_order['outTradeNo'],
				"TRADENO" => $pay_order['tradeNo'],
				"ORDERTIME" =>$ntime,
				"ORDERVALIDITYTIME" => "",
				"ORDERAMOUNT" => $this->pay_money,
				"CURTYPE" => "156",
				"PRODUCTDESC" => $order['order_name'],
				"SUBJECT" => $order['order_name'],
				"SWTICHACC" => "true",
		);
		ksort($sign_param);

		$paramsJoined = array();
		foreach($sign_param as $param => $value) {
			$paramsJoined[] = "$param=$value";
		}
		$sign_paramData = implode('&', $paramsJoined);
		$sign = $this->createSign($sign_paramData);

		$paramData.="&SIGN=".$sign;

		$cipher = new Crypt_AES();
		$cipher->setKey($rand_data32);
		$encryStr = base64_encode($cipher->encrypt($paramData));

		if($is_app){
			return array('paramData'=>$paramData,'entryStr'=>$encryStr);
		}else{
			$webUrl = "https://capi.bestpay.com.cn/gateway.pay?platform=wap_3.0&encryStr=".$encryStr."&keyIndex=".$request['keyIndex']."&encryKey=".$request['encryKey'];
			$webUrl = str_replace("+","%2B",$webUrl);
			return $webUrl;
		}

	}

	public function notice($data,$is_app=0){
		$sign = $data['sign'];
		unset($data['sign']);
		ksort($data);
		$paramsJoined = array();
		foreach($data as $param => $value) {
			if ($value===null){
				$value="null";
			}
			$paramsJoined[] = "$param=$value";
		}
		$paramData = implode('&', $paramsJoined);
		
		if($is_app==3){
			$this->PUBLIC_KEY  =  C('config.pay_yzf_offline_public_key');
		}
		$sign_result = $this->verifySign($paramData,$sign);
		
	
		if($sign_result ){
			
			$arr['sign_result'] = $sign_result;
				$order_id = explode('_',$data['outTradeNo']);

				$arr['order_param'] = array(
					'third_id'=>$data['tradeNo'],
					'pay_money'=>$data['tradeAmt']/100,
					'order_id'=>$order_id[1],
					'order_type'=>$order_id[0],
					'pay_type'=>'yzfpay',
					'error'=>$data['tradeStatus']=='SUCCESS'?0:1,
					'is_mobile'=>0,
					'is_own'=>$order_id[2]==1?1:0,
				);
		}else{
			$arr['sign_result'] = false;
			$arr['order_param'] = array();
		}
		
	
		return $arr;
	
	} 
	/*查找微信订单信息*/
	public function query_order($orderNo,$tradeDate,$is_app=0){

		$request = array(
			"institutionType"=>'MERCHANT',
			"institutionCode"=>$this->mer_id,
			"merchantNo"=>$this->mer_id,
			"outTradeNo"=>$orderNo,
			"tradeDate"=>$tradeDate,
		);
		if($is_app==3){
			$this->offline_mer_id  = C('config.pay_yzf_offline_mer_id');
			$this->offline_cert_key  =  C('config.pay_yzf_offline_cert_key');
			$this->offline_cert_path  =  C('config.pay_yzf_offline_p12');
			$this->OFFLINE_PUBLIC_KEY  =  C('config.pay_yzf_offline_public_key');
			
			$request = array(
				"institutionType"=>'MERCHANT',
				"institutionCode"=>$this->offline_mer_id,
				"merchantNo"=>$this->offline_mer_id,
				"outTradeNo"=>$orderNo,
				"tradeDate"=>$tradeDate,
			);
		}
		ksort($request);
		$paramsJoined = array();
		foreach($request as $param => $value) {
			$paramsJoined[] = "$param=$value";
		}
		$paramData = implode('&', $paramsJoined);
		if($is_app==3){
			$sign = $this->createSign($paramData,1);
		}else{
			$sign = $this->createSign($paramData);
		}

		$request['sign'] =$sign;
		$request = json_encode($request);
		$res = httpRequest($this->query_order_url,'POST',$request,$this->header);
	
		$res = json_decode($res[1],true);
		if($res['errorCode']==NULL && $res['result']['tradeStatus']=='SUCCESS'){
			$result = $res['result'];
			$order_id = explode('_',$result['outTradeNo']);
			$query_pay_param = array(
					'third_id'=>$result['tradeNo'],
					'pay_money'=>$result['payAmt']/100,
					'order_id'=>$order_id[1],
					'order_type'=>$order_id[0],
					'pay_type'=>'yzfpay',
					'error'=>0,
					'is_mobile'=>$is_app,
					'errorMsg'=>'',
					'pay_time'=>$result['payFinishedDate'],
					'is_own'=>($order_id[2]?1:0),
			);
			return $query_pay_param;
		}else if($res['errorCode']){

			return array('error'=>1,'msg'=>'订单状态：'.$res['errorMsg']);
		}else{
			return array('error'=>1,'msg'=>'查询失败');
		}

	}

        /* 退款 */
	public function refund()
	{
		if ($this->is_mobile == 2) {
			$chanel = 'APP';
		} else if ($this->is_mobile == 1) {
			$chanel = 'H5';
		} else {
			$chanel = 'API';
		}
		$where['order_id'] =  $this->order_info['orderid'];
		$where['order_type'] =  $this->order_info['order_type'];

		// if($this->order_info['order_from']==1&&$this->order_info['order_type']=='shop'){
		// 	$this->order_info['order_type']='mall';
		// }
		$payment_money_order = M('Payment_money_order')->where(array('orderid'=>$this->order_info['orderid']))->find();
		$this->order_info['order_type']=$payment_money_order['order_type'];

		$request = array(
			"outRequestNo"=>$this->order_info['order_type'].'_'.substr($this->order_info['order_id'],0,10).'_'.$_SERVER['REQUEST_TIME'],
			"merchantNo"=>$this->mer_id,
			//"outTradeNo"=>$this->order_info['order_trad_no'],
			"outTradeNo"=>$this->order_info['order_type'].'_'.$this->order_info['orderid'],
			"refundAmt"=>bcmul($this->order_info['payment_money'],100),
			"requestDate"=>date("Y-m-d H:i:s"),
			"operator"=>$this->mer_id,
			"tradeChannel"=>$chanel,
			"accessCode"=>"CASHIER",
			"originalTradeDate"=>date('Y-m-d 00:00:00',$this->order_info['pay_time']),
			"ccy"=>"156",
		);

		ksort($request);
		$paramsJoined = array();
		foreach($request as $param => $value) {
			$paramsJoined[] = "$param=$value";
		}
		$paramData = implode('&', $paramsJoined);

		$sign = $this->createSign($paramData);
		$request['sign'] =$sign;
		$request = json_encode($request);
		$res = httpRequest($this->refund_url,'POST',$request,$this->header);

		$res = json_decode($res[1],true);

        if($res['success'] == false && $res['errorMsg'] == '订单信息不存在'){
            $request = array(
                "outRequestNo"=>'shop_'.substr($this->order_info['order_id'],0,10).'_'.$_SERVER['REQUEST_TIME'],
                "merchantNo"=>$this->mer_id,
                //"outTradeNo"=>$this->order_info['order_trad_no'],
                "outTradeNo"=>'shop_'.$this->order_info['orderid'],
                "refundAmt"=>bcmul($this->order_info['payment_money'],100),
                "requestDate"=>date("Y-m-d H:i:s"),
                "operator"=>$this->mer_id,
                "tradeChannel"=>$chanel,
                "accessCode"=>"CASHIER",
                "originalTradeDate"=>date('Y-m-d 00:00:00',$this->order_info['pay_time']),
                "ccy"=>"156",
            );
            ksort($request);
            $paramsJoined = array();
            foreach($request as $param => $value) {
                $paramsJoined[] = "$param=$value";
            }
            $paramData = implode('&', $paramsJoined);

            $sign = $this->createSign($paramData);
            $request['sign'] =$sign;
            $request = json_encode($request);
            $res = httpRequest($this->refund_url,'POST',$request,$this->header);

            $res = json_decode($res[1],true);
        }

		if($res['success'] == false){

			return array('error'=>1,'type'=>'fail','msg'=>'退款申请失败！如果重试多次还是失败请联系系统管理员。'.$res['errorMsg'],'refund_param'=>array());
		}else{

			return array('error'=>0,'type'=>'ok','msg'=>'退款申请成功！','refund_param'=>$res['result']);
		}
	}


	public function create_pay_form($param){
		$str =<<<Eof
				<div style="display:none;">
					<form method='post' id="SendOrderForm" name='SendOrderForm' action="$this->pay_web_url">
						<input type='hidden' name='institutionCode' value="{$param['institutionCode']}"/>
						<input type='hidden' name='institutionType' value="{$param['institutionType']}"/>
						<input type='hidden' name='service' value="{$param['service']}"/>
						<input type='hidden' name='merchantNo' value="{$param['merchantNo']}"/>
						<input type='hidden' name='subMerchantNo' value="{$param['subMerchantNo']}"/>
						<input type='hidden' name='outTradeNo' value="{$param['outTradeNo']}"/>
						<input type='hidden' name='tradeprodNo' value="{$param['tradeprodNo']}"/>
						<input type='hidden' name='tradeNo' value="{$param['tradeNo']}"/>
						<input type='hidden' name='tradeAmt' value="{$param['tradeAmt']}"/>
						<input type='hidden' name='ccy' value="{$param['ccy']}"/>
						<input type='hidden' name='subject' value="{$param['subject']}"/>
						<input type='hidden' name='goodsInfo' value="{$param['goodsInfo']}"/>
						<input type='hidden' name='mediumType' value="{$param['mediumType']}"/>
						<input type='hidden' name='tradeChannel' value="{$param['tradeChannel']}"/>
						<input type='hidden' name='requestDate' value="{$param['requestDate']}"/>
						<input type='hidden' name='frontNotifyUrl' value="{$param['frontNotifyUrl']}"/>
						<input type='hidden' name='bankCode' value="{$param['bankCode']}"/>
						<input type='hidden' name='signType' value="{$param['signType']}"/>
						<input type='hidden' name='sign' value="{$param['sign']}"/>
						<input type="submit" value="进行支付">
						<script>document.forms['SendOrderForm'].submit();</script>
					</form>
				</div>
Eof;

		return $str;
	}


}
?>