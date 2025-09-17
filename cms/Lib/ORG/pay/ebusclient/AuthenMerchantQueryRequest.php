<?php
class_exists('TrxRequest') or require (dirname(__FILE__) . '/core/TrxRequest.php');
class_exists('Json') or require (dirname(__FILE__) . '/core/Json.php');
class_exists('DataVerifier') or require (dirname(__FILE__) . '/core/DataVerifier.php');
class_exists('ILength') or require (dirname(__FILE__) . '/core/ILength.php');

class AuthenMerchantQueryRequest extends TrxRequest {
	public $request = array (
		"TrxType" => IFunctionID :: TRX_TYPE_QUERY_AUTHEN_MERCHANT,
		"TransferNo" => ""
	);

	function __construct() {
	}

	protected function getRequestMessage() {
	  Json :: arrayRecursive($this->request, "urlencode", false);
		$tMessage = json_encode($this->request);
		$tMessage = urldecode($tMessage);
		return $tMessage;
	}

	/// 支付请求信息是否合法
	protected function checkRequest() {
    //合法性判断
    if (empty($this->request["TransferNo"]))
			throw new TrxException(TrxException :: TRX_EXC_CODE_1100, TrxException :: TRX_EXC_MSG_1100, "未设定出金订单编号！");
    }
	  
}
?>