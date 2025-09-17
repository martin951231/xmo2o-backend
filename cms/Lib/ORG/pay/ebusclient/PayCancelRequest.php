<?php
class_exists('TrxRequest') or require (dirname(__FILE__) . '/core/TrxRequest.php');
class_exists('Json') or require (dirname(__FILE__) . '/core/Json.php');
class_exists('DataVerifier') or require (dirname(__FILE__) . '/core/DataVerifier.php');
class_exists('ILength') or require (dirname(__FILE__) . '/core/ILength.php');

class PayCancelRequest extends TrxRequest {
	public $request = array (
		"TrxType" => IFunctionID :: TRX_TYPE_PAY_CANCEL,
		"OrderNo" => "",
		"QueryDetail" => "false",
		"SubMchNO" => "",
		"ModelFlag" => "",
		"MerchantFlag" => ""
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
    if (!DataVerifier :: isValidString($this->request["OrderNo"], ILength :: ORDERID_LEN))
			throw new TrxException(TrxException :: TRX_EXC_CODE_1100, TrxException :: TRX_EXC_MSG_1100, "订单编号不合法！");
    if (($this->request["ModelFlag"] !== "0") && ($this->request["ModelFlag"] !== "1")) 
      throw new TrxException(TrxException :: TRX_EXC_CODE_1100, TrxException :: TRX_EXC_MSG_1100, "商户模式设置不合法！");
		if (($this->request["MerchantFlag"] !== "W") && ($this->request["MerchantFlag"] !== "Z"))
      throw new TrxException(TrxException :: TRX_EXC_CODE_1100, TrxException :: TRX_EXC_MSG_1100, "支付渠道设置不合法！");
		}
	  
}
?>