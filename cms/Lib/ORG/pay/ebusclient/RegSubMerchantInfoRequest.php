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
class_exists('IIsBreakAccountType') or require (dirname(__FILE__) . '/core/IIsBreakAccountType.php');

class RegSubMerchantInfoRequest extends TrxRequest {
	
	public $details = array ();
	/*与后台接口保持一致 modified by chj @20190305*/
	public $request = array (
		"TrxType" => IFunctionID :: TRX_TYPE_REG_MERCHANTINFO_REQ,
		//"MerchantID" => "",
		"SubMerId" => "",
		"SubMerName" => "",
		"SubMerType" => "",
		"SubMerSort" => "1",
		"Status" => "",
		//"MCC" => "",
		"CertificateType" => "",
    "CertificateNo" => "",
    "ContactName" => "",
    "CompanyName" => "",
    "CompanyCertType" => "",
    "CompanyCertNo" => "",
    "AccountName" => "",
    "Account" => "",
    "BankName" => "",
    "MobilePhone" => "",
    "Address" => "",
    "AccountType" => "",
		"Remark" => ""
	);
	
	function __construct() {
	}

  /*后台一次只支持一个账户提交 modified by chj @20190305*/
	protected function getRequestMessage() {		
	  Json :: arrayRecursive($this->request, "urlencode", false);
		$tMessage = json_encode($this->request);
		$tMessage = urldecode($tMessage);
		return $tMessage;
		
	}

	/// 支付请求信息是否合法
			/*modified  by chj@20190304 修改点如下：
		  1、去掉this->details、SubMerSort、MobileNo、AccStatus、IsABCAccount的判断
		  2、增加BankName非空判断
		  3、detail改为this->request----与后台核对，现在只提交一条，之前一次可提交1-5条
		  4、增加了SubMerId和MobilePhone的合法性验证
		  */		  
	protected function checkRequest() {
		/*if (count($this->details, COUNT_NORMAL) > ILength :: MAXSUMCOUNT) {
			throw new TrxException(TrxException :: TRX_EXC_CODE_1101, TrxException :: TRX_EXC_MSG_1101, "批次的总笔数(" . count($this->details, COUNT_NORMAL) . ")超过最大限制(" . ILength :: MAXSUMCOUNT . ")");
		}	*/
		if (empty($this->request["SubMerId"]))
        throw new TrxException(TrxException :: TRX_EXC_CODE_1101, TrxException :: TRX_EXC_MSG_1101, "二级商户号未设定！");
    if (empty($this->request["SubMerName"]))
        throw new TrxException(TrxException :: TRX_EXC_CODE_1101, TrxException :: TRX_EXC_MSG_1101, "二级商户名称未设定！");
    if (empty($this->request["SubMerType"]))
        throw new TrxException(TrxException :: TRX_EXC_CODE_1101, TrxException :: TRX_EXC_MSG_1101, "二级商户类型未设定！");
    /*if (empty($this->request["SubMerSort"]))
        throw new TrxException(TrxException :: TRX_EXC_CODE_1101, TrxException :: TRX_EXC_MSG_1101, "二级商户种类未设定！");  */        
    if (!DataVerifier :: isValidCertificate($this->request["CertificateType"], $this->request["CertificateNo"])) {
	      throw new TrxException(TrxException :: TRX_EXC_CODE_1101, TrxException :: TRX_EXC_MSG_1101, "证件类型、证件号码不合法");
		}
		
    if (empty($this->request["ContactName"]))
        throw new TrxException(TrxException :: TRX_EXC_CODE_1101, TrxException :: TRX_EXC_MSG_1101, "联系人名称未设定！");
    if (empty($this->request["Status"]))
        throw new TrxException(TrxException :: TRX_EXC_CODE_1101, TrxException :: TRX_EXC_MSG_1101, "二级商户状态未设定！");
    /*if (!DataVerifier :: isValid($this->request["MobileNo"]))
			throw new TrxException(TrxException :: TRX_EXC_CODE_1100, TrxException :: TRX_EXC_MSG_1101, "手机号不合法");*/
    
    if (empty($this->request["Account"]))
        throw new TrxException(TrxException :: TRX_EXC_CODE_1100, TrxException :: TRX_EXC_MSG_1101, "二级商户账户不能为空！");
    if (empty($this->request["AccountName"]))
        throw new TrxException(TrxException :: TRX_EXC_CODE_1100, TrxException :: TRX_EXC_MSG_1101, "二级商户账户名不能为空！");
    
    if (empty($this->request["BankName"]))
        throw new TrxException(TrxException :: TRX_EXC_CODE_1100, TrxException :: TRX_EXC_MSG_1101, "收款银行为空！");
           
    if (empty($this->request["AccountType"]))
        throw new TrxException(TrxException :: TRX_EXC_CODE_1100, TrxException :: TRX_EXC_MSG_1101, "二级商户账户类型不能为空！");
           
    if (!DataVerifier :: isValid($this->request["AccountType"]) || strlen($this->request["AccountType"]) !== 3)
        throw new TrxException(TrxException :: TRX_EXC_CODE_1100, TrxException :: TRX_EXC_MSG_1100, "二级商户账户类型不合法！");
    
    if (empty($this->request["MobilePhone"]))
        throw new TrxException(TrxException :: TRX_EXC_CODE_1100, TrxException :: TRX_EXC_MSG_1101, "手机号不能为空！");
       /*if (empty($detail["AccStatus"]))
           throw new TrxException(TrxException :: TRX_EXC_CODE_1100, TrxException :: TRX_EXC_MSG_1101, "二级商户账户状态为空！");*/
       /*if (empty($detail["IsABCAccount"]))
           throw new TrxException(TrxException :: TRX_EXC_CODE_1100, TrxException :: TRX_EXC_MSG_1101, "是否是农行账户不能为空！");*/
       
    if(strlen($this->request["SubMerId"]) !== ILength::MERCHANTID_LEN)
        throw new TrxException(TrxException :: TRX_EXC_CODE_1100, TrxException :: TRX_EXC_MSG_1100, "二级商户号不合法！");      
    if (!DataVerifier :: isValid($this->request["MobilePhone"]))
        throw new TrxException(TrxException :: TRX_EXC_CODE_1100, TrxException :: TRX_EXC_MSG_1100, "手机号不合法！");    
   }
}
?>