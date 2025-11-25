<?php
class_exists('TrxRequest') or require (dirname(__FILE__) . '/core/TrxRequest.php');
class_exists('Json') or require (dirname(__FILE__) . '/core/Json.php');
class_exists('IChannelType') or require (dirname(__FILE__) . '/core/IChannelType.php');
class_exists('IPaymentType') or require (dirname(__FILE__) . '/core/IPaymentType.php');
class_exists('INotifyType') or require (dirname(__FILE__) . '/core/INotifyType.php');
class_exists('DataVerifier') or require (dirname(__FILE__) . '/core/DataVerifier.php');
class_exists('ILength') or require (dirname(__FILE__) . '/core/ILength.php');
class_exists('IPayTypeID') or require (dirname(__FILE__) . '/core/IPayTypeID.php');
class_exists('IInstallmentmark') or require (dirname(__FILE__) . '/core/IInstallmentmark.php');
class_exists('ICommodityType') or require (dirname(__FILE__) . '/core/ICommodityType.php');
class OutPaymentRequest extends TrxRequest {
	public $request = array (
		"TrxType" => IFunctionID :: TRX_TYPE_PAY_FOR_ANOTHER_REQ,
		"SubMerId" => "",      //二级商户编号        
		"Account" => "",       //收款账号          
		"AccountName" => "",   //收款账名          
		"TrxAmount" => "",     //出金金额          
	  "OrderNo" => "",       //交易编号          
		"DrawingFlag" => "",   //交易编号          
		"Remark" => "",        //附言            
		"RecBankNo" => ""      //他行行号          
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
		//空值判断
		if (empty($this->request["SubMerId"]))
        throw new TrxException(TrxException :: TRX_EXC_CODE_1101, TrxException :: TRX_EXC_MSG_1101, "未设定二级商户编号！");
		if (empty($this->request["Account"]))
        throw new TrxException(TrxException :: TRX_EXC_CODE_1101, TrxException :: TRX_EXC_MSG_1101, "未设定二级商户收款账户！");
		if (empty($this->request["AccountName"]))
        throw new TrxException(TrxException :: TRX_EXC_CODE_1101, TrxException :: TRX_EXC_MSG_1101, "未设定二级商户收款账户名称！");
	}
}
?>