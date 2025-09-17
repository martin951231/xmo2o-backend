<?php


class Wftpay{
	protected $order_info;
	protected $pay_money;
	protected $pay_type;
	protected $is_mobile;
	protected $pay_config;
	protected $user_info;
	protected $pay_url;
	protected $URL;
	protected $wft_pay_card_cert;
	protected $wft_pay_card_url;
	protected $wft_pay_card_pkey;

	protected $header = array(
			"Content-type: application/json;charset='utf-8'",
			'Content-Encoding: UTF-8',
	);

	public function __construct($order_info,$pay_money,$pay_type,$pay_config,$user_info,$is_mobile=0){
		$this->order_info = $order_info;
		$this->pay_money  = $pay_money;
		$this->pay_type   = $pay_type;
		$this->real_pay_type   = $pay_config['real_pay_type'];
		$this->is_mobile   = $is_mobile;
		$this->pay_config = $pay_config;
		$this->user_info  = $user_info;
		$this->mer_id  = $pay_config['pay_wft_mer_id'];
		$this->mer_pwd  = $pay_config['pay_wft_mer_pwd'];
		$this->private_key  = $pay_config['pay_wft_key'];


		$this->wft_mer_id  = C('config.wft_pay_card_mer_id');
		$this->wft_pay_card_cert  =  C('config.wft_pay_card_cert');
		$this->wft_pay_card_pkey  =  C('config.wft_pay_card_pkey');

		$this->pay_url = "http://test.gnetpg.com:8089/GneteMerchantAPI/api/PayV36";
		$this->query_order_url = "http://test.gnetpg.com:8089/GneteMerchantAPI/Trans.action";
		$this->wft_pay_card_url = "https://59.41.103.101:9070/CardWebServiceProvider";

		$this->order_info['order_trade_no'] =$this->order_info['order_type'].'_'.$this->order_info['order_id'].($this->pay_config['is_own'] ? '_1' : '');
		$this->back_url = C('config.site_url').'/index.php?c=LowFeePayNotice&a=wft_pay_back&order_type='
				.$this->order_info['order_type'].'&order_id='.$this->order_info['order_id']
				.'&source='.$this->is_mobile.'&is_own='.($this->pay_config['is_own'] ? 1: 0).'&mer_id='.intval($this->order_info['mer_id']);

		$this->order_info['order_name'] = $this->order_info['order_name']?$this->order_info['order_name']:$this->order_info['order_id'];
	}

	private function getPublicKey(){

		$publicKey_path = $this->wft_pay_card_cert;

		$certificateCAcerContent = file_get_contents($_SERVER['DOCUMENT_ROOT'].$publicKey_path);
		$certificateCApemContent =  '-----BEGIN CERTIFICATE-----'.PHP_EOL
				.chunk_split(base64_encode($certificateCAcerContent), 64, PHP_EOL)
				.'-----END CERTIFICATE-----'.PHP_EOL;

		$pubkeyid = openssl_pkey_get_public($certificateCApemContent);
//		dump($pubkeyid);
		$id  = openssl_x509_parse($certificateCApemContent);
		dump($id);
		return array('public_key'=>$pubkeyid,'certNo'=>$id['serialNumber']);
	}

	public function pay(){
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
		return $pay_order;
	}

	public function mobile_pay(){
		$pay_order = $this->get_pay_order();
		return $pay_order;
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

	public function get_pay_order(){
		$order = $this->order_info;

		$data['order_type'] = $this->order_info['order_type'];
		$data['order_id'] = $this->order_info['order_id'];
		$data['short_orderid'] = substr($this->order_info['order_id'],10,20);
		$data['tradeNo'] = $this->order_info['order_trade_no'];
		$data['addtime'] = time();
		$data['is_mobile'] = $this->is_mobile;
		$data['mer_id'] = $this->order_info['mer_id'];
		M('Wft_pay_info')->add($data);

		//下单
		$params=array(
			"MerId"=>$this->mer_id,
			"OrderNo"=>$this->order_info['order_type'].'_'.$data['short_orderid'].($this->pay_config['is_own'] ? '_1' : ''),
			"OrderAmount"=>$this->pay_money,
			"CurrCode"=>"CNY",
			"OrderType"=>"B2C",
			"CallBackUrl"=>$this->back_url,
			"BankCode"=>"00010000",
			"LangType"=>"UTF-8",
			"BuzType"=>"01",
			"Reserved01"=>"123",
			"Reserved02"=>"456",
		);
		$Goods = array(
				"goodsCategory"=>$this->order_info['order_type'],
				"goodsId"=>$this->order_info['order_id'],
				"goodsName"=>$this->order_info['order_name'],
				"body"=>$this->order_info['order_trade_no'],
				"price"=>$this->pay_money,
				"quantity"=>1
		);

		if($this->real_pay_type=='wft_weixin'){

			$params['BankCode'] = "88988888";

			$params['Reserved01'] ='Goods='.json_encode($Goods,JSON_UNESCAPED_UNICODE).'|PayType=WXPay.jsPay|SubOpenId='.$this->user_info['openid'];
		}

		if($this->real_pay_type=='wft_alipay'){

			$params['BankCode'] = "88988888";
			$params['Reserved01'] ='Goods='.json_encode($Goods,JSON_UNESCAPED_UNICODE).'|PayType=trade.jsPay';
		}

		$SignMsg=md5($params['MerId'].$params['OrderNo'].$params['OrderAmount'].$params['CurrCode']
				.$params['OrderType'].$params['CallBackUrl'].$params['BankCode'].$params['LangType']
				.$params['BuzType'].$params['Reserved01'].$params['Reserved02'].md5($this->private_key));
//		dump($params['MerId'].$params['OrderNo'].$params['OrderAmount'].$params['CurrCode']
//				.$params['OrderType'].$params['CallBackUrl'].$params['BankCode'].$params['LangType']
//				.$params['BuzType'].$params['Reserved01'].$params['Reserved02']);die;
		$params['SignMsg'] = $SignMsg;

		$form = $this->create_pay_form($params);
		return array('error'=>0,'form'=>$form);
	}

	public function notice($params){
		$SignMsg=md5($params['OrderNo'].$params['PayNo'].$params['PayAmount'].$params['CurrCode']
				.$params['SystemSSN'].$params['RespCode'].$params['SettDate'].$params['Reserved01']
				.$params['Reserved02'].md5($this->private_key));

		if($SignMsg==$params['SignMsg'] && $params['RespCode']=='00'){
			$arr['sign_result'] = true;
			$order_id = explode('_',$params['OrderNo']);
			$arr['order_param'] = array(
				'third_id'=>$params['PayNo'],
				'pay_money'=>$params['PayAmount'],
				'order_id'=>$order_id[1],
				'order_type'=>$order_id[0],
				'pay_type'=>'wftpay',
				'error'=>$params['RespCode']=='00'?0:1,
				'is_mobile'=>$this->is_mobile,
				'is_own'=>$order_id[2]==1?1:0,
			);
		}else{
			$arr['sign_result'] = false;
			$arr['order_param'] = array();
		}

		return $arr;
	
	} 
	/*查找微信订单信息*/
	public function query_order($orderNo,$is_app=0,$order){
		$request = array(
			"TranType"=>"100",
			"JavaCharset"=>"UTF-8",
			"Version"=>"V60",
			"UserId"=>$this->mer_id,
			"Pwd"=>md5($this->mer_pwd),
			"MerId"=>$this->mer_id,
			"PayStatus"=>"2",
			"ShoppingTime"=>date('Y-m-d 00:00:00',$order['addtime']),
			"BeginTime"=>"",
			"EndTime"=>"",
			"OrderNo"=>$orderNo,
		);

		$res = httpRequest($this->query_order_url.'?'.http_build_query($request,'&'),'GET');
		$res =explode(' ',trim(str_replace('\n',' ',$res[1])));
		if( $res[5]=='00'){
			$order_id = explode('_',$order['tradeNo']);
			$query_pay_param = array(
				'third_id'=>$res[4],
				'pay_money'=>$res[1],
				'order_id'=>$order_id[1],
				'order_type'=>$order_id[0],
				'pay_type'=>'wftpay',
				'error'=>0,
				'is_mobile'=>$is_app,
				'errorMsg'=>'',
				'pay_time'=>$res['addtime'],
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

		$wft_pay_info= M('Wft_pay_info')->where(array('order_id'=>$this->order_info['orderid']))->find();
		$request = array(
				"TranType"=>"31",
				"JavaCharset"=>"UTF-8",
				"Version"=>"V36",
				"RefundNo"=>$this->order_info['order_type'].'_'.time(),
				"MerId"=>$this->mer_id,
				"OrderNo"=>$this->order_info['order_type'].'_'.$wft_pay_info['short_orderid'].($wft_pay_info['mer_id']>0?'_1':''),
				"ShoppingDate"=>date('Ymd',$this->order_info['pay_time']),
				"PayAmount"=>$this->order_info['payment_money'],
				"RefundAmount"=>$this->pay_money,
				"Reserved"=>"",

		);

		ksort($request);
		$paramsJoined = array();
		foreach($request as $param => $value) {
			$paramsJoined[] = "$param=$value";
		}
		$paramData = implode('&', $paramsJoined);


		$signMsg =md5($paramData.'&'.md5($this->private_key));
		$request['SignMsg'] = $signMsg;
		$ch = curl_init ();

		curl_setopt ( $ch, CURLOPT_URL, $this->query_order_url );
		curl_setopt ( $ch, CURLOPT_POST, 1 );
		curl_setopt ( $ch, CURLOPT_HEADER, 0 );
		curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
		curl_setopt ( $ch, CURLOPT_POSTFIELDS, $request );
		$return = curl_exec ( $ch );
		curl_close ( $ch );

		//SignMsg=7541239a094670dd086352cdad560a9f&Code=0000&Message=退款成功!

		$queryParts = explode('&',$return);
		foreach ($queryParts as $param) {
			$item = explode('=', $param);
			$res[$item[0]] = $item[1];
		}




		if($res['Code'] == '0000' && $res['Message']=='退款成功!'){
			return array('error'=>0,'type'=>'ok','msg'=>'退款申请成功！','refund_param'=>$res['result']);
		}else{
			return array('error'=>1,'type'=>'fail','msg'=>'退款申请失败！如果重试多次还是失败请联系系统管理员。'.$res['Message'],'refund_param'=>array());

		}
	}

	public function create_refund_form($param){
		$strs='';
		foreach ($param as $key=>$v) {
			$strs.="<input type='hidden' name='{$key}' value=\"{$v}\"/>";
		}
		dump($strs);
		$str =<<<Eof
				<div style="display:none;">
					<form method='post' id="SendOrderForm" name='SendOrderForm' action="$this->query_order_url">
						{$strs}
						<input type="submit" value="进行支付">
						<script>document.forms['SendOrderForm'].submit();</script>
					</form>
				</div>
Eof;

		return $str;
	}

	public function create_pay_form($param){
		$str =<<<Eof
				<div style="display:none;">
					<form method='post' id="SendOrderForm" name='SendOrderForm' action="$this->pay_url">
						<input type='hidden' name='MerId' value="{$param['MerId']}"/>
						<input type='hidden' name='OrderNo' value="{$param['OrderNo']}"/>
						<input type='hidden' name='OrderAmount' value="{$param['OrderAmount']}"/>
						<input type='hidden' name='CurrCode' value="{$param['CurrCode']}"/>
						<input type='hidden' name='CallBackUrl' value="{$param['CallBackUrl']}"/>
						<input type='hidden' name='OrderType' value="{$param['OrderType']}"/>
						<input type='hidden' name='BankCode' value="{$param['BankCode']}"/>
						<input type='hidden' name='BuzType' value="{$param['BuzType']}"/>
						<input type='hidden' name='LangType' value="{$param['LangType']}"/>
						<input type='hidden' name='Reserved01' value="{$param['Reserved01']}"/>
						<input type='hidden' name='Reserved02' value="{$param['Reserved02']}"/>
						<input type='hidden' name='SignMsg' value="{$param['SignMsg']}"/>
						<input type="submit" value="进行支付">
						<script>document.forms['SendOrderForm'].submit();</script>
					</form>
				</div>
Eof;

		return $str;
	}


	// 预支付卡功能
	public function xml_header(){
		return array(
				'Ver'=>'1.0',
				'App'=>'CARD',
				'SndCode'=>'14095810',
				'RecCode'=>'00000000',
				'MsgType'=>'4001',
				'MsgId'=>'14095810'.createRandNumber(20),
				'TimeStamp'=>get_millisecond(),
		);
	}

	public function xml_body(){
		return array(
				'Ver'=>'1.0',
				'App'=>'CARD',
				'SndCode'=>'CARD',
				'RecCode'=>'00000000',
				'MsgType'=>'00000000',
				'MsgId'=>'00000000',
				'TimeStamp'=>get_millisecond(),
		);
	}

	public function xml_sign($signDate){
//		$cert = $this->getPublicKey();
		$private_key = file_get_contents($_SERVER['DOCUMENT_ROOT'].$this->wft_pay_card_pkey); //获取密钥文件内容

		//私钥加密
		openssl_pkcs12_read($private_key, $certs, '12345678'); //读取公钥、私钥
		$prikeyid = $certs['pkey'];

		$cert_info  = openssl_x509_parse($certs['cert']);
		$s = openssl_sign($signDate,$signature,$prikeyid,OPENSSL_ALGO_MD5 );
		return array(
			'CertNo'=>$cert_info['serialNumber'],
			'OrigMsg'=>$signDate,
			'Sign'=>base64_encode($signature),
		);
	}

	//源地址+报文流水+时间撮+用户登录名+用户登录密码+登录密码类型
	public function login_card(){
		$header = $this->xml_header();

		$body = array(
				'UserId'=>'EWO11DTG',
				'SysId'=>'JFKII',
				'LoginPwd'=>'30765553',
				'PwdType'=>'0',
		);
		$signDate = $this->wft_pay_card_url.$header['MsgId'].$header['TimeStamp'].$body['UserId'].$body['LoginPwd'].$body['PwdType'];

		$arr['CMS'] = array(
			"HEAD"=>$header
		,
			"BODY"=>$body
		,
			"TAIL"=>$this->xml_sign($signDate)
		);

//		$xmlObj = new SimpleXMLElement('<CMS></CMS>');
//		$this->data2xml($xmlObj, $arr);
//		$data = $this->postXmlCurl($xmlObj->asXML(),$this->wft_pay_card_url);

		import("@.ORG.nusoap");
		$soapClient = new nusoap_client('./CardWebServiceProvider.xml',TRUE); //TRUE参数表示使用wsdl
		$err = $soapClient->getError();
		dump($arr);
		$soapClient->xml_encoding = 'UTF-8'; //指定字符编码
		$soapClient->soap_defencoding = 'UTF-8';
		dump($soapClient->wsdlFile);
//		$soapClient->set
		$outString = $soapClient->call("reqForCardServiceResponse", base64_encode(gzencode($arr)));


		dump($outString);
	}
	public function create_card(){
		$arr = array(
			"HEAD"=>$this->xml_header()
			,
			"BODY"=>
				$this->xml_body()
			,
			"TAIL"=>$this->xml_sign()

		);
		dump($arr);
		$xmlObj = new SimpleXMLElement('<CMS></CMS>');
		$this->data2xml($xmlObj, $arr);

		dump($xmlObj->asXML());



	}

	private function data2xml($xml, $data, $item = 'item') {
		foreach ($data as $key => $value) {
			/* 指定默认的数字key */
			is_numeric($key) && $key = $item;

			/* 添加子元素 */
			if(is_array($value) || is_object($value)){
				$child = $xml->addChild($key);
				$this->data2xml($child, $value, $item);
			} else {
				if(is_numeric($value)){
					$child = $xml->addChild($key, $value);
				} else {
					$child = $xml->addChild($key);
					$node  = dom_import_simplexml($child);
					$node->appendChild($node->ownerDocument->createCDATASection($value));
				}
			}
		}
	}

	public function postXmlCurl($xml,$url,$second=30)
	{
		dump($url);
		//初始化curl
		$ch = curl_init();
		//设置超时
		curl_setopt($ch, CURLOP_TIMEOUT, $second);
		//这里设置代理，如果有的话
		//curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
		//curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
		//设置header
		curl_setopt($ch, CURLOPT_HEADER, array('Content-Type: text/xml'));
		//要求结果为字符串且输出到屏幕上
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		//post提交方式
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		//运行curl
		$data = curl_exec($ch);
		curl_close($ch);
		//返回结果
		if($data)
		{
			curl_close($ch);
			return $data;
		}
		else
		{
			$error = curl_errno($ch);
			dump($error);
			echo "curl出错，错误码:$error"."<br>";
			echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
			curl_close($ch);
			return false;
		}
	}

}
?>