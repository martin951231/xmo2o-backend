<?php
class hake_credit
{
	protected $MW_API_ENDPOINT_TOKEN;
	protected $MW_API_ENDPOINT_POST;
	protected $MW_API_ENDPOINT_Transfer;
	protected $merchantUUID;
	protected $apiKey;
	protected $passphrase;
	protected $returnURL;
	protected $notifyURL;
	
	public function __construct(){
		$this->MW_API_ENDPOINT_TOKEN = C('config.pay_merchantwarrior_payapi').'token/';
		$this->MW_API_ENDPOINT_POST = C('config.pay_merchantwarrior_payapi').'post/';
		$this->MW_API_ENDPOINT_Transfer = C('config.pay_merchantwarrior_payapi').'transfer/';
		$this->merchantUUID = C('config.pay_merchantwarrior_merchantUUID');
		$this->apiKey  = C('config.pay_merchantwarrior_apiKey');
		$this->passphrase  = C('config.pay_merchantwarrior_passphrase');	
		$this->returnURL  = C('config.site_url').'/wap.php?c=Pay&a=return_url&pay_type=merchantwarrior';
		$this->notifyURL  = C('config.site_url').'/source/wap_merchantwarrior_notice.php';	
	}
	
	public function addCard($param){
		$postData = array (
			'method' => 'addCard',
			'merchantUUID' => $this->merchantUUID,
			'apiKey' => $this->apiKey,
			'cardName' => $param['card_name'],
			'cardNumber' => $param['card_number'],
			'cardExpiryMonth' => $param['card_expiry_month'],
			'cardExpiryYear' => $param['card_expiry_year'],
		);
		
		import('ORG.Net.Http');
		$http = new Http();

		$return = Http::curlPostOwn($this->MW_API_ENDPOINT_TOKEN, http_build_query($postData, '', '&'));
		
		
		$return_data = json_decode(json_encode(simplexml_load_string($return, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		
		if($return_data['responseCode'] != 0){
			return array('err_code'=>$return_data['responseCode'],'err_msg'=>$return_data['responseMessage']);
		}
		return array(
			'err_code' => 0,
			'card_id'  => $return_data['cardID'],
			'card_key'  => $return_data['cardKey'],
			'ivr_card_id'  => $return_data['ivrCardID'],
			'card_number'  => $return_data['cardNumber'],
			'card_from'  => 'warrior',
		);
	}
	
	public function getAccessToken($payment_order){
		$postData['method'] = 'getAccessToken';
		$postData['merchantUUID'] = $this->merchantUUID;
		$postData['apiKey'] = $this->apiKey;
		
		$postData['hash'] = md5(strtolower(md5($this->passphrase).$this->merchantUUID.$payment_order['transactionAmount'].$payment_order['transactionCurrency']));
		$postData['urlHash'] = md5(strtolower(md5($this->passphrase).$this->merchantUUID.($this->returnURL.'&orderid='.$payment_order['orderid']).$this->notifyURL));
		
		import('ORG.Net.Http');
		$http = new Http();

		$return = Http::curlPostOwn($this->MW_API_ENDPOINT_Transfer, http_build_query($postData, '', '&'));
		$return_data = json_decode(json_encode(simplexml_load_string($return, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		if($return_data['responseCode'] != 0){
			fdump($postData,'postData');
			return array('err_code'=>$return_data['responseCode'],'err_msg'=>$return_data['responseMessage'],'more_response'=>$return_data);
		}
		return array(
			'err_code' => 0,
			'token'  => $return_data['token'],
		);
	}
	
	public function payCardRedirect($payment_order){
		$postData = $payment_order;
		$postData['method'] = 'processCard';
		$postData['merchantUUID'] = $this->merchantUUID;
		$postData['apiKey'] = $this->apiKey;
		
		$postData['returnURL'] = $this->returnURL.'&orderid='.$payment_order['orderid'];
		$postData['notifyURL'] = $this->notifyURL;
		
		$postData['hash'] = md5(strtolower(md5($this->passphrase).$this->merchantUUID.$payment_order['transactionAmount'].$payment_order['transactionCurrency']));
		$postData['urlHash'] = md5(strtolower(md5($this->passphrase).$this->merchantUUID.($this->returnURL.'&orderid='.$payment_order['orderid']).$this->notifyURL));
		
		fdump($postData,'mw_payCardRedirect',true);
		
		$formHtml  = '<div style="display:none;">';
		$formHtml .= '<form id="merchantwarrior_form" name="merchantwarrior_form" action="'.$this->MW_API_ENDPOINT_Transfer.'" method="post">';
		foreach($postData as $key=>$value){
			$formHtml .= '<input type="hidden" name="'.$key.'" value="'.$value.'"/>';
		}
		$formHtml .= '<input type="submit" value="进行支付"/>';
		$formHtml .= '</form>';
		$formHtml .= '</div>';
		return array(
			'err_code' => 0,
			'formHtml' => $formHtml,
		);
	}
	
	public function processCard($payment_order){
		$postData = $payment_order;
		$postData['method'] = 'processCard';
		$postData['merchantUUID'] = $this->merchantUUID;
		$postData['apiKey'] = $this->apiKey;
		
		$postData['hash'] = md5(strtolower(md5($this->passphrase).$this->merchantUUID.$postData['transactionAmount'].$postData['transactionCurrency']));
		
		fdump($postData,'mw_payCard',true);
		
		import('ORG.Net.Http');
		$http = new Http();

		$return = Http::curlPostOwn($this->MW_API_ENDPOINT_TOKEN, http_build_query($postData, '', '&'));
		$return_data = json_decode(json_encode(simplexml_load_string($return, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		if($return_data['responseCode'] != 0){
			fdump($postData,'postData');
			return array('err_code'=>$return_data['responseCode'],'err_msg'=>$return_data['responseMessage'],'more_response'=>$return_data);
		}
		return array(
			'err_code' => 0,
			'transactionID'  => $return_data['transactionID'],
			'more_response'  => $return_data,
		);
	}
	
	public function cardInfo($card_query){
		$postData = $card_query;
		$postData['method'] = 'cardInfo';
		$postData['merchantUUID'] = $this->merchantUUID;
		$postData['apiKey'] = $this->apiKey;
		
		dump($postData);
		
		import('ORG.Net.Http');
		$http = new Http();

		$return = Http::curlPostOwn($this->MW_API_ENDPOINT_TOKEN, http_build_query($postData, '', '&'));

		$return_data = json_decode(json_encode(simplexml_load_string($return, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		
		dump($return_data);
	}
	
	public function queryCard($query_order){
		$postData = $query_order;
		$postData['method'] = 'queryCard';
		$postData['merchantUUID'] = $this->merchantUUID;
		$postData['apiKey'] = $this->apiKey;
		
		$postData['hash'] = md5(strtolower(md5($this->passphrase).$this->merchantUUID.$postData['transactionReferenceID']));
		
		import('ORG.Net.Http');
		$http = new Http();

		$return = Http::curlPostOwn($this->MW_API_ENDPOINT_POST, http_build_query($postData, '', '&'));

		$return_data = json_decode(json_encode(simplexml_load_string($return, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		if($return_data['responseCode'] != 0){
			return array('err_code'=>$return_data['responseCode'],'err_msg'=>$return_data['responseMessage']);
		}
		return array(
			'err_code' => 0,
			'more_response'  => $return_data,
		);
	}
	
	public function refundCard($refund_order){
		$postData = $refund_order;
		$postData['method'] = 'refundCard';
		$postData['merchantUUID'] = $this->merchantUUID;
		$postData['apiKey'] = $this->apiKey;
		
		$postData['hash'] = md5(strtolower(md5($this->passphrase).$this->merchantUUID.$postData['transactionAmount'].$postData['transactionCurrency']));
		
		import('ORG.Net.Http');
		$http = new Http();

		$return = Http::curlPostOwn($this->MW_API_ENDPOINT_POST, http_build_query($postData, '', '&'));
		$return_data = json_decode(json_encode(simplexml_load_string($return, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		if($return_data['responseCode'] != 0){
			return array('err_code'=>$return_data['responseCode'],'err_msg'=>$return_data['responseMessage']);
		}
		
		fdump($postData,'hake_refundCard',true);
		fdump($return_data,'hake_refundCard',true);
		
		return array(
			'err_code' => 0,
			'transactionID'  => $return_data['transactionID'],
			'more_response'  => $return_data,
		);
	}
}
?>