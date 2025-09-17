<?php
class_exists('TrxRequest') or require (dirname(__FILE__) . '/core/TrxRequest.php');
class_exists('Json') or require (dirname(__FILE__) . '/core/Json.php');
class_exists('IChannelType') or require (dirname(__FILE__) . '/core/IChannelType.php');
class_exists('IPaymentType') or require (dirname(__FILE__) . '/core/IPaymentType.php');
class_exists('INotifyType') or require (dirname(__FILE__) . '/core/INotifyType.php');
class_exists('DataVerifier') or require (dirname(__FILE__) . '/core/DataVerifier.php');
class_exists('ILength') or require (dirname(__FILE__) . '/core/ILength.php');
class_exists('IPayTypeID') or require (dirname(__FILE__) . '/core/IPayTypeID.php');
class_exists('ICommodityType') or require (dirname(__FILE__) . '/core/ICommodityType.php');
class_exists('IIsBreakAccountType') or require (dirname(__FILE__) . '/core/IIsBreakAccountType.php');
class UnifiedPaymentRequest extends TrxRequest {
	public $order = array (
		"PayTypeID" => "",
		"OrderDate" => "",
		"OrderTime" => "",
		"orderTimeoutDate" => "",   
		"OrderNo" => "",
		"CurrencyCode" => "",
		"OrderAmount" => "",
		"AccountNo" => "",
		"OrderDesc" => "",
		"OpenID" => "",
		"ReceiverAddress" => "",
	//"InstallmentMark" => "", //普通支付和java版,这个都是order的一个属性,后台是request属性，与后台确认该属性不用，直接去掉
  //"InstallmentCode" => "", //普通支付和java版,这个都是order的一个属性,后台是request属性，与后台确认该属性不用，直接去掉
  //"InstallmentNum" => "",  //普通支付和java版,这个都是order的一个属性,后台是request属性，与后台确认该属性不用，直接去掉		
		"BuyIP" => "",
		"ExpiredDate" => "",
		"LimitPay" => "",
		"SubMerNo" => "", //二级商户号 根据后台接口新增
	  "Fee" => ""       //后台接口没有此字段，为了兼容老接口，未去掉，java中也保留
	);
	public $orderitems = array ();
	public $splitaccinfos = array ();
	public $h5sceneinfo = array();
	public $request = array (
		"TrxType" => IFunctionID :: TRX_TYPE_PAY_REQ_UNIFIED,
		"PaymentType" => "",
		"PaymentLinkType" => "",
		"ReceiveAccount" => "",  //无用
		"ReceiveAccName" => "",  //无用
		"NotifyType" => "",
		"ResultNotifyURL" => "",
		"MerchantRemarks" => "",
		"IsBreakAccount" => "",
		"SplitAccTemplate" => "",
    "CommodityType" => "",    //普通支付和java版,这个都是order的一个属性,后台是request属性，按照后台来
		"MerModelFlag" => "",     //根据后台接口新增——是否为大商户模式
		"SubMerchantID" => ""     //根据后台接口新增——大商户模式的子商户号
	);
	function __construct() {
	}

	protected function getRequestMessage() {
		Json :: arrayRecursive($this->order, "urlencode", false);
		Json :: arrayRecursive($this->request, "urlencode", false);
		Json :: arrayRecursive($this->h5sceneinfo, "urlencode", false);
		
		$js = '"Order":' . (json_encode(($this->order)));
		$js = substr($js, 0, -1);		
		$js = $js . ',"OrderItems":[';
		$count = count($this->orderitems, COUNT_NORMAL);
		for ($i = 0; $i < $count; $i++) {
			Json :: arrayRecursive($this->orderitems[$i], "urlencode", false);
			$js = $js . json_encode($this->orderitems[$i]);
			if ($i < $count -1) {
				$js = $js . ',';
			}
		}
		$js = $js . ']';
		
	  $js = $js . ',"SplitAccInfoItems":[';
	  $count = count($this->splitaccinfos, COUNT_NORMAL);
		for ($i = 0; $i < $count; $i++) {
			Json :: arrayRecursive($this->splitaccinfos[$i], "urlencode", false);
			$js = $js . json_encode($this->splitaccinfos[$i]);
			if ($i < $count -1) {
				$js = $js . ',';
			}
		}
		$js = $js . ']';
		
	  $js = $js . ',"H5SceneInfo":' . (json_encode(($this->h5sceneinfo)));

		if (!(empty($this->h5sceneinfo)))
		{
			$js = substr($js, 0, -1);
			$js = $js. '}';
		}		  
		$js = $js. '}}';
		
		$tMessage = json_encode($this->request);
		$tMessage = substr($tMessage, 0, -1);
		$tMessage = $tMessage . ',' . $js;
		$tMessage = urldecode($tMessage);
		return $tMessage;
	}

	/// 支付请求信息是否合法
	protected function checkRequest() {
		$tError = $this->isValid();
		if ($tError != null)
			throw new TrxException(TrxException :: TRX_EXC_CODE_1101, TrxException :: TRX_EXC_MSG_1101 . "订单信息不合法！[" . $tError . "]");
	}

	/// 支付请求信息是否合法
	private function isValid() {
		
		if (empty($this->request["PaymentLinkType"]))
		{
				return "未设置支付渠道！";
		}
		if (empty($this->request["PaymentType"]))
		{
			  return "未设置支付类型! ";
    }
		if (!($this->request["NotifyType"] === INotifyType :: NOTIFY_TYPE_SERVER))
		{
			  return "支付通知类型不合法！";
    }
		if (!(DataVerifier :: isValidURL($this->request["ResultNotifyURL"])))
		{
			  return "支付结果回传网址不合法！";
    }
		if (strlen($this->request["MerchantRemarks"]) > 100) 
		{
			  return "附言长度大于100";
		}
		//分账信息
		if (($this->request["IsBreakAccount"] !== IIsBreakAccountType :: IsBreak_TYPE_YES) && ($this->request["IsBreakAccount"] !== IIsBreakAccountType :: IsBreak_TYPE_NO)) {
			  return "交易是否分账设置异常，必须为：0或1";
		}
		if (($this->request["IsBreakAccount"] === IIsBreakAccountType :: IsBreak_TYPE_NO) && ( count($this->splitaccinfos, COUNT_NORMAL) > 1))
		{
			 return "分账标志为0时，不能设置分账信息";
		}
 		  
 
    //20160902 指定账户长度——java不再判断了，所以也去掉了 modified by chj
    //if ((!empty($this->order["AccountNo"])) && strlen($this->order["AccountNo"]) < 10)
    //	return "支付账户长度不能少于10位！";
    //交易类型验证
		$payTypeId = $this->order["PayTypeID"];
		if(!($payTypeId === IPayTypeID::PAY_TYPE_JSAPI) && 
		   !($payTypeId ===IPayTypeID::PAY_TYPE_NATIVE) &&
		   !($payTypeId === IPayTypeID::PAY_TYPE_APP) && 
		   !($payTypeId === IPayTypeID::PAY_TYPE_MICROPAY)  &&
		   !($payTypeId === IPayTypeID::PAY_TYPE_MWEB))
		{
			  return "设定交易类型错误！";
		}
	  //验证order信息 为空，是否为指定值
		if ($payTypeId === IPayTypeID :: PAY_TYPE_JSAPI || 
		    $payTypeId === IPayTypeID :: PAY_TYPE_APP   ||
		    $payTypeId === IPayTypeID :: PAY_TYPE_MICROPAY )
    {
    	if (empty($this->order["AccountNo"]))
     	{
     		return "指定支付账户为空";
      }
    }
    if ($payTypeId === IPayTypeID :: PAY_TYPE_JSAPI)
    {
    	if (empty($this->order["OpenID"]))
     	{
     		return "公众(号)小程序支付时,OpenID不能为空";
      }
    }
		if (!DataVerifier :: isValidString($this->order["OrderNo"], ILength :: ORDERID_LEN))
			return "交易编号不合法";
		if (!DataVerifier :: isValidDate($this->order["OrderDate"]))
			return "订单日期不合法";
		if (!DataVerifier :: isValidTime($this->order["OrderTime"]))
			return "订单时间不合法";
		if (!ICommodityType :: InArray($this->request["CommodityType"]))
			return "商品种类不合法";
		if (!DataVerifier :: isValidAmount($this->order["OrderAmount"], 2))
			return "订单金额不合法";
		if ($this->order["CurrencyCode"] !== "156")
			return "设定交易币种错误";

		#region 验证$orderitems信息（订单明细）
		if (count($this->orderitems, COUNT_NORMAL) < 1)
			return "商品明细为空";
		foreach ($this->orderitems as $orderitem) {
			if (!DataVerifier :: isValidString($orderitem["ProductName"], ILength :: PRODUCTNAME_LEN))
		  return "产品名称不合法";
		}
		return "";
	}
}
?>