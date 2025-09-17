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
class InnerPaymentRequest extends TrxRequest {
	public $request = array (
		"TrxType" => IFunctionID :: TRX_TYPE_INNER_PAY,
		"InternalTransferNo" => "",
		"RemitterSubMerchantNo" => "",
		"RemitteeSubMerchantNo" => "",
		"Amount" => ""
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
		if (empty($this->request["InternalTransferNo"]))
        throw new TrxException(TrxException :: TRX_EXC_CODE_1101, TrxException :: TRX_EXC_MSG_1101, "未设定转账交易编号！");
		if (empty($this->request["RemitterSubMerchantNo"]))
        throw new TrxException(TrxException :: TRX_EXC_CODE_1101, TrxException :: TRX_EXC_MSG_1101, "未设定转出方子商户编号！");
		if (empty($this->request["RemitteeSubMerchantNo"]))
        throw new TrxException(TrxException :: TRX_EXC_CODE_1101, TrxException :: TRX_EXC_MSG_1101, "未设定转入方子商户编号！");
		if (empty($this->request["Amount"]))
        throw new TrxException(TrxException :: TRX_EXC_CODE_1101, TrxException :: TRX_EXC_MSG_1101, "未设定转账金额！");
    //合法性判断
    if (!DataVerifier :: isValidString($this->request["InternalTransferNo"], ILength :: ORDERID_LEN))
			throw new TrxException(TrxException :: TRX_EXC_CODE_1100, TrxException :: TRX_EXC_MSG_1100, "转账交易编号不合法！");
    if (!DataVerifier :: isValidAmount($this->request["Amount"], 2))
			throw new TrxException(TrxException :: TRX_EXC_CODE_1101, TrxException :: TRX_EXC_MSG_1101, "转账金额不合法！");
	  
	}
}
?>