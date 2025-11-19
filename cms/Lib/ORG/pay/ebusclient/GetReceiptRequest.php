<?php
class_exists('TrxRequest') or require (dirname(__FILE__) . '/core/TrxRequest.php');
class_exists('Json') or require (dirname(__FILE__) . '/core/Json.php');
class_exists('DataVerifier') or require (dirname(__FILE__) . '/core/DataVerifier.php');
class_exists('ILength') or require (dirname(__FILE__) . '/core/ILength.php');

class GetReceiptRequest extends TrxRequest {
	public $request = array (
		"TrxType" => IFunctionID :: TRX_TYPE_GET_RECEIPT,
		"SubMerchantNo" => "",
		"OrderNo" => ""
	);
	function __construct() {
	}

	protected function getRequestMessage() {
		Json :: arrayRecursive($this->request, "urlencode", false);
		$tMessage = json_encode($this->request);
		$tMessage = urldecode($tMessage);
		return $tMessage;
	}

	/// 请求信息是否合法
	protected function checkRequest() {
		if (!DataVerifier :: isValidString($this->request["SubMerchantNo"],ILength :: MERCHANTID_LEN))
			throw new TrxException(TrxException :: TRX_EXC_CODE_1101, TrxException :: TRX_EXC_MSG_1101, "二级商户不合法！");
		if (!DataVerifier :: isValidString($this->request["OrderNo"],ILength :: ORDERID_LEN))
			throw new TrxException(TrxException :: TRX_EXC_CODE_1101, TrxException :: TRX_EXC_MSG_1101, "交易编号不合法！");
	}
	
	public function decompressFromBase64String($str) {
		 if(strlen($str)===0)
		   return "";
		 if(!($sImagStr = base64_decode($str)))
		   return "";
	   if(!($sImagStr2 = gzdecode($sImagStr)))
	     return "";
		 else 
		   return $sImagStr2;
  }
}
?>