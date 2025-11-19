<?php
/**
 * 对接农业银行微信支付(小程序、公众号、app)
 */

class Nong{

	public function __construct(){

	}

	public function pay($order, $pay_info = ''){
		$order_id_arr = explode('_',$order['orderNo']);
		M('Payment_money_order')->where(array('orderid'=>$order_id_arr[1],'order_type'=>$order_id_arr[0]))->data(array('pay_method'=>'nong','orderNo'=>$order['orderNo'],'is_own'=>intval($order_id_arr[2])))->save();
		import("@.ORG.pay.ebusclient.UnifiedPaymentRequest", "", ".php");

		$tRequest = new UnifiedPaymentRequest();
		$tRequest->order["PayTypeID"] = 'JSAPI'; //设定交易类型
		$tRequest->order["OrderDate"] = date('Y/m/d'); //设定订单日期 （必要信息 - YYYY/MM/DD）
		$tRequest->order["OrderTime"] = date('H:i:s'); //设定订单时间 （必要信息 - HH:MM:SS）
		$tRequest->order["orderTimeoutDate"] = date('YmdHis',time()+3600); //设定订单有效期
		$tRequest->order["OrderNo"] = $order['orderNo']; //设定订单编号
		$tRequest->order["CurrencyCode"] = '156'; //设定交易币种
		$tRequest->order["OrderAmount"] = $order['payment_money']; //设定交易金额
		$tRequest->order["AccountNo"] = C("config.pay_weixin_appid"); //指定付款账户
		$tRequest->order["OrderDesc"] = '订单号：'.$order_id_arr[1]; //设定订单说明
		$tRequest->order["OpenID"] = $order['openid']; //指定付款账户
		$tRequest->order["ReceiverAddress"] = ''; //收货地址
		$tRequest->order["BuyIP"] = ''; //IP
		$tRequest->order["ExpiredDate"] = ''; //设定订单保存时间
		$tRequest->order["LimitPay"] = ''; //限制贷记卡

		//2、订单明细

		$orderitem = array ();
		$orderitem["SubMerName"] = ""; //设定二级商户名称
		$orderitem["SubMerId"] = ""; //设定二级商户代码
		$orderitem["SubMerMCC"] = ""; //设定二级商户MCC码 
		$orderitem["SubMerchantRemarks"] = ""; //二级商户备注项
		$orderitem["ProductID"] = ""; //商品代码，预留字段
		$orderitem["ProductName"] = "商品"; //商品名称
		$orderitem["UnitPrice"] = ""; //商品总价
		$orderitem["Qty"] = "1"; //商品数量
		$orderitem["ProductRemarks"] = ""; //商品备注项
		$orderitem["ProductType"] = ""; //商品类型
		$orderitem["ProductDiscount"] = "1"; //商品折扣
		$orderitem["ProductExpiredDate"] = ""; //商品有效期
		$tRequest->orderitems[0] = $orderitem;

		//3、生成支付请求对象
		$tRequest->request["PaymentType"] = '8'; //设定支付类型
		$tRequest->request["PaymentLinkType"] = '1'; //设定支付接入
		//$tRequest->request["ReceiveAccount"] = ($_POST['ReceiveAccount']); //与后台确认，不用
		//$tRequest->request["ReceiveAccName"] = ($_POST['ReceiveAccName']); //与后台确认，不用
		$tRequest->request["NotifyType"] = '1'; //设定通知方式
		$tRequest->request["ResultNotifyURL"] = C('config.site_url').'/source/wap_nong_weixin_notice.php'; //设定通知URL地址
		$tRequest->request["MerchantRemarks"] = ''; //设定附言
		$tRequest->request["IsBreakAccount"] = '0'; //设定交易是否分账
		//$tRequest->request["SplitAccTemplate"] = ($_POST['SplitAccTemplate']); //设定分账模板
		$tRequest->request["CommodityType"] = '0201'; //设定商品种类
		$tRequest->request["MerModelFlag"] = ''; //是否为大商户模式
		$tRequest->request["SubMerchantID"] = ''; //大商户模式的子商户号


		$tResponse = $tRequest->postRequest();

		if ($tResponse->isSuccess()) 
		{	
			// $JSAPI = $tResponse->GetValue("JSAPI");
			$weixin_param = [
				'appId' => C("config.pay_weixin_appid"),
				'nonceStr' => $tResponse->GetValue("nonceStr"),
				'package' => $tResponse->GetValue("package"),
				'paySign' => $tResponse->GetValue("paySign"),
				'signType' => $tResponse->GetValue("signType"),
				'timeStamp' => $tResponse->GetValue("timestamp")
			];
			return array('error'=>0,'weixin_param'=>$weixin_param);
		} 
		else 
		{
			return array('error'=>1,'msg'=>'返回错误：'.$tResponse->getReturnCode().$tResponse->getErrorMessage());
		}
	}

	public function refund($order){
		import("@.ORG.pay.ebusclient.RefundRequest", "", ".php");
		//1、生成退款请求对象
		$tRequest = new RefundRequest();
		$tRequest->request["OrderDate"] = date('Y/m/d'); //订单日期（必要信息）
		$tRequest->request["OrderTime"] = date('H:i:s'); //订单时间（必要信息）
		$tRequest->request["OrderNo"] = $order['orderNo']; //原交易编号（必要信息）
		$tRequest->request["NewOrderNo"] = substr($order['order_type'].'_'.$order['orderid'].'_'.strrev($_SERVER['REQUEST_TIME']),0,32); //交易编号（必要信息）
		$tRequest->request["CurrencyCode"] = '156'; //交易币种（必要信息）
		$tRequest->request["TrxAmount"] = $order['payment_refund_money'] ? $order['payment_refund_money'] : $order['payment_money']; //退货金额 （必要信息）

		//3、传送退款请求并取得退货结果
		$tResponse = $tRequest->postRequest();

		//4、支付请求提交成功，返回结果信息
		if ($tResponse->isSuccess()) {
			$returnArr = array(
				'error' => 0,
				'type' => 'ok',
				'refund_money'   => $tResponse->GetValue("TrxAmount"),
				'refund_param'   => serialize([$tResponse->getReturnCode(),$tResponse->getErrorMessage()]),
			);
		} else {
			$returnArr = array(
				'error'=>1,
				'msg'=>'退款返回：'.$tResponse->getReturnCode().$tResponse->getErrorMessage(),
			);
		}
		return $returnArr;
	}
}

