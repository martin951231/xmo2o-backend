<?php
/**
 * 微信官方通道
 * add by lumin
 * 2020-05-25
 */

namespace app\pay\model\service\channel;

use app\pay\model\service\record\WeixinProfitSharingRecordService;

require_once '../extend/pay/wechat_sdk/lib/WxPay.Api.php';
require_once '../extend/pay/wechat_sdk/WxPay.Config.php';

class WechatService{
	private $agent = '';
	private $config = [];//配置参数

	public function __construct($agent, $config = []){
		$this->agent = $agent;
		$this->config = $config;
	}

	public function pay($order_no, $money, $extra_cfg){
		if(empty($order_no) || empty($money)){
            fdump_api(['param' => $_POST,'order_no' => $order_no,'money' => $money,'msg' => '订单ID或支付金额必填'],'errPay/WechatServicePayLog',1);
			throw new \think\Exception("订单ID或支付金额必填！");
		}
		switch ($this->agent) {
			case 'wechat_h5'://微信端
				return $this->Wechatpay($order_no, $money, $extra_cfg);
			case 'iosapp':
			case 'androidapp':
				return $this->Apppay($order_no, $money, $extra_cfg);
			case 'wechat_mini':
				return $this->Minipay($order_no, $money, $extra_cfg);
			case 'h5':
				return $this->H5pay($order_no, $money, $extra_cfg);
			default:
                fdump_api(['param' => $_POST,'agent' => $this->agent,'order_no' => $order_no,'money' => $money,'msg' => '当前端口未对接，不能使用'],'errPay/WechatServicePayLog',1);
				throw new \think\Exception("当前端口未对接，不能使用");
		}
	}

	/**
	 * 微信端微信支付
	 */
	private function Wechatpay($order_no, $money, $extra_cfg){
		if(empty($this->config['openid'])){
            fdump_api(['param' => $_POST,'config' => $this->config,'order_no' => $order_no,'money' => $money,'msg' => '未获取到openid，无法调起支付'],'errPay/WechatServiceWechatpayLog',1);
			throw new \think\Exception("未获取到openid，无法调起支付");
		}
		
		//②、统一下单
		$input = new \WxPayUnifiedOrder();
		$bodyStr="订单编号：" . $extra_cfg['business_order_sn'];
		if(isset($extra_cfg['goods_desc']) && !empty($extra_cfg['goods_desc'])){
            $bodyStr=$extra_cfg['goods_desc'].'-'. $extra_cfg['business_order_sn'];
        }
		$input->SetBody($bodyStr);
		$input->SetOut_trade_no($order_no);
		$input->SetTotal_fee($money * 100);
		$input->SetNotify_url((String) url('wechat_notify', [], false, true));
		$input->SetTrade_type("JSAPI");
		$input->SetOpenid($this->config['openid']);
		$config = new \WxPayConfig();
		$config->app_id = $this->config['pay_weixin_appid'];
		$config->merchant_id = $this->config['pay_weixin_mchid'];
		$config->key = $this->config['pay_weixin_key'];
		$config->secret = $this->config['pay_weixin_appsecret'];

		//服务商子商户支付
		if(isset($this->config['sub_mch_id']) && $this->config['sub_mch_id']){
			$input->SetSub_mch_id($this->config['sub_mch_id']);
		}

		// 分账	
		if(isset($this->config['open_weixin_profit_sharing']) && $this->config['open_weixin_profit_sharing']){
			$input->SetProfit_sharing('Y');
		}

		$res = \WxPayApi::unifiedOrder($config, $input);
		if($res['return_code'] == 'SUCCESS'){
			if($res['result_code'] == 'SUCCESS'){
				$jsApiParameters = $this->GetJsApiParameters($res);
				return [
					'type' => 'jssdk',
					'info' => $jsApiParameters
				];
			}
			else{
                fdump_api(['param' => $_POST,'res' => $res,'config' => $config,'input' => $input,'order_no' => $order_no,'money' => $money,'msg' => '调取微信支付失败,原因：'.$res['err_code'].' '.$res['err_code_des']],'errPay/WechatServiceWechatpayLog',1);
				throw new \think\Exception('调取微信支付失败,原因：'.$res['err_code'].' '.$res['err_code_des']);
			}
		}
		else{
            fdump_api(['param' => $_POST,'res' => $res,'config' => $config,'input' => $input,'order_no' => $order_no,'money' => $money,'msg' => '调取微信支付失败,原因：'.$res['return_msg']],'errPay/WechatServiceWechatpayLog',1);
            throw new \think\Exception('调取微信支付失败,原因：'.$res['return_msg']);
		}
	}

	/**
	 * 
	 * 获取jsapi支付的参数
	 * @param array $UnifiedOrderResult 统一支付接口返回的数据
	 * @throws WxPayException
	 * 
	 * @return json数据，可直接填入js函数作为参数
	 */
	private function GetJsApiParameters($UnifiedOrderResult)
	{
		if(!array_key_exists("appid", $UnifiedOrderResult)
		|| !array_key_exists("prepay_id", $UnifiedOrderResult)
		|| $UnifiedOrderResult['prepay_id'] == "")
		{
			throw new \think\Exception("微信异常：参数错误");
		}

		$jsapi = new \WxPayJsApiPay();
		$jsapi->SetAppid($UnifiedOrderResult["appid"]);
		$timeStamp = time();
		$jsapi->SetTimeStamp("$timeStamp");
		$jsapi->SetNonceStr(\WxPayApi::getNonceStr());
		$jsapi->SetPackage("prepay_id=" . $UnifiedOrderResult['prepay_id']);

		$config = new \WxPayConfig();
		$config->key = $this->config['pay_weixin_key'];
		$jsapi->SetPaySign($jsapi->MakeSign($config));
		$parameters = json_encode($jsapi->GetValues());
		return $parameters;
	}

	private function GetMiniApiParameters($UnifiedOrderResult)
	{
		if(!array_key_exists("appid", $UnifiedOrderResult)
		|| !array_key_exists("prepay_id", $UnifiedOrderResult)
		|| $UnifiedOrderResult['prepay_id'] == "")
		{
			throw new \think\Exception("微信异常：参数错误");
		}

		$jsapi = new \WxPayJsApiPay();
		$jsapi->SetAppid($UnifiedOrderResult["appid"]);
		$timeStamp = time();
		$jsapi->SetTimeStamp("$timeStamp");
		$jsapi->SetNonceStr(\WxPayApi::getNonceStr());
		$jsapi->SetPackage("prepay_id=" . $UnifiedOrderResult['prepay_id']);

		$config = new \WxPayConfig();
		$config->key = $this->config['pay_wxapp_key'];
		$jsapi->SetPaySign($jsapi->MakeSign($config));
		$parameters = json_encode($jsapi->GetValues());
		return $parameters;
	}

	/**
	 * app中的微信支付
	 */
	private function Apppay($order_no, $money, $extra_cfg){
		//②、统一下单
		$input = new \WxPayUnifiedOrder();
        $bodyStr="订单编号：" . $extra_cfg['business_order_sn'];
        if(isset($extra_cfg['goods_desc']) && !empty($extra_cfg['goods_desc'])){
            $bodyStr=$extra_cfg['goods_desc'].'-'. $extra_cfg['business_order_sn'];
        }
        $input->SetBody($bodyStr);
		$input->SetOut_trade_no($order_no);
		$input->SetTotal_fee($money * 100);
		$input->SetNotify_url((String) url('wechat_notify', [], false, true));
		$input->SetTrade_type("APP");

		//服务商子商户支付
		if(isset($this->config['sub_mch_id']) && $this->config['sub_mch_id']){
			$input->SetSub_mch_id($this->config['sub_mch_id']);
		}
		
		// 分账	
		if(isset($this->config['open_weixin_profit_sharing']) && $this->config['open_weixin_profit_sharing']){
			$input->SetProfit_sharing('Y');
		}

		$config = new \WxPayConfig();
		$config->app_id = $this->config['pay_weixinapp_appid'];
		$config->merchant_id = $this->config['pay_weixinapp_mchid'];
		$config->key = $this->config['pay_weixinapp_key'];
		$config->secret = $this->config['pay_weixinapp_appsecret'];
		$res = \WxPayApi::unifiedOrder($config, $input);
		if($res['return_code'] == 'SUCCESS'){
			if($res['result_code'] == 'SUCCESS'){
				$timeStamp = time();
				$nonce_str = \WxPayApi::getNonceStr();
				$package = "Sign=WXPay";
				$app_param = [
					'appid' => $res['appid'],
					'partnerid' => $res['mch_id'],
					'prepayid' => $res['prepay_id'],
					'noncestr' => $nonce_str,
					'timestamp' => $timeStamp,
					'package' => $package
				];
				$app_param['sign'] = $this->GetAppApiParameters($app_param);

				return [
					'type' => 'sdk',
					'info' => $app_param
				];
			}
			else{
                fdump_api(['param' => $_POST,'res' => $res,'config' => $config,'input' => $input,'order_no' => $order_no,'money' => $money,'msg' => '调取微信支付失败,原因：'.$res['err_code'].' '.$res['err_code_des']],'errPay/WechatServiceApppayLog',1);
                throw new \think\Exception('调取微信支付失败,原因：'.$res['err_code'].' '.$res['err_code_des']);
			}
		}
		else{
            fdump_api(['param' => $_POST,'res' => $res,'config' => $config,'input' => $input,'order_no' => $order_no,'money' => $money,'msg' => '调取微信支付失败,原因：'.$res['return_msg']],'errPay/WechatServiceApppayLog',1);
            throw new \think\Exception('调取微信支付失败,原因：'.$res['return_msg']);
		}
	}

	private function GetAppApiParameters($app_param){
		ksort($app_param);
		$string = $this->ToUrlParams($app_param);
		$string = $string . "&key=".$this->config['pay_weixinapp_key'];
		$config = new \WxPayConfig();
		if($config->GetSignType() == "MD5"){
			$string = md5($string);
		} else if($config->GetSignType() == "HMAC-SHA256") {
			$string = hash_hmac("sha256",$string ,$this->config['pay_weixinapp_key']);
		} else {
			throw new \think\Exception("签名类型不支持！");
		}
		return strtoupper($string);
	}

	public function ToUrlParams($app_param)
	{
		$buff = "";
		foreach ($app_param as $k => $v)
		{
			if($k != "sign" && $v != "" && !is_array($v)){
				$buff .= $k . "=" . $v . "&";
			}
		}
		
		$buff = trim($buff, "&");
		return $buff;
	}


	private function Minipay($order_no, $money, $extra_cfg){
		if(empty($this->config['wxapp_openid'])){
			throw new \think\Exception("未获取到openid，无法调起支付");
		}
		
		//②、统一下单
		$input = new \WxPayUnifiedOrder();
        $bodyStr="订单编号：" . $extra_cfg['business_order_sn'];
        if(isset($extra_cfg['goods_desc']) && !empty($extra_cfg['goods_desc'])){
            $bodyStr=$extra_cfg['goods_desc'].'-'. $extra_cfg['business_order_sn'];
        }
        $input->SetBody($bodyStr);
		$input->SetOut_trade_no($order_no);
		$input->SetTotal_fee($money * 100);
		$input->SetNotify_url((String) url('wechat_notify', [], false, true));
		$input->SetTrade_type("JSAPI");
		$input->SetOpenid($this->config['wxapp_openid']);

		//服务商子商户支付
		if(isset($this->config['sub_mch_id']) && $this->config['sub_mch_id']){
			$input->SetSub_mch_id($this->config['sub_mch_id']);
		}
		
		// 分账	
		if(isset($this->config['open_weixin_profit_sharing']) && $this->config['open_weixin_profit_sharing']){
			$input->SetProfit_sharing('Y');
		}

		$config = new \WxPayConfig();
		$config->app_id = $this->config['pay_wxapp_appid'];
		$config->merchant_id = $this->config['pay_wxapp_mchid'];
		$config->key = $this->config['pay_wxapp_key'];
		$config->secret = $this->config['pay_wxapp_appsecret'];
		$res = \WxPayApi::unifiedOrder($config, $input);
		if($res['return_code'] == 'SUCCESS'){
			if($res['result_code'] == 'SUCCESS'){
				$jsApiParameters = $this->GetMiniApiParameters($res);
				return [
					'type' => 'jssdk',
					'info' => $jsApiParameters
				];
			}
			else{
                fdump_api(['param' => $_POST,'res' => $res,'config' => $config,'input' => $input,'order_no' => $order_no,'money' => $money,'msg' => '调取微信支付失败,原因：'.$res['err_code'].' '.$res['err_code_des']],'errPay/WechatServiceMinipayLog',1);
                throw new \think\Exception('调取微信支付失败,原因：'.$res['err_code'].' '.$res['err_code_des']);
			}
		}
		else{
            fdump_api(['param' => $_POST,'res' => $res,'config' => $config,'input' => $input,'order_no' => $order_no,'money' => $money,'msg' => '调取微信支付失败,原因：'.$res['return_msg']],'errPay/WechatServiceMinipayLog',1);
            throw new \think\Exception('调取微信支付失败,原因：'.$res['return_msg']);
		}
	}


	/**
	 * 手机浏览器中的微信支付
	 */
	private function H5pay($order_no, $money, $extra_cfg){
		//②、统一下单
		$input = new \WxPayUnifiedOrder();
        $bodyStr="订单编号：" . $extra_cfg['business_order_sn'];
        if(isset($extra_cfg['goods_desc']) && !empty($extra_cfg['goods_desc'])){
            $bodyStr=$extra_cfg['goods_desc'].'-'. $extra_cfg['business_order_sn'];
        }
        $input->SetBody($bodyStr);
		$input->SetOut_trade_no($order_no);
		$input->SetTotal_fee($money * 100);
		$input->SetNotify_url((String) url('wechat_notify', [], false, true));
		$input->SetTrade_type("MWEB");

		//服务商子商户支付
		if(isset($this->config['sub_mch_id']) && $this->config['sub_mch_id']){
			$input->SetSub_mch_id($this->config['sub_mch_id']);
		}
		
		// 分账	
		if(isset($this->config['open_weixin_profit_sharing']) && $this->config['open_weixin_profit_sharing']){
			$input->SetProfit_sharing('Y');
		}

		// $input->SetOpenid($this->config['openid']);
		$config = new \WxPayConfig();
		$config->app_id = $this->config['pay_weixinh5_appid'];
		$config->merchant_id = $this->config['pay_weixinh5_mchid'];
		$config->key = $this->config['pay_weixinh5_key'];
		$config->secret = $this->config['pay_weixinh5_appsecret'];
		$res = \WxPayApi::unifiedOrder($config, $input);
		
		if($res['return_code'] == 'SUCCESS'){
			if($res['result_code'] == 'SUCCESS'){
				return [
					'type' => 'redirect',
					'info' => $res['mweb_url']
				];
			}
			else{
                fdump_api(['param' => $_POST,'res' => $res,'config' => $config,'input' => $input,'order_no' => $order_no,'money' => $money,'msg' => '调取微信支付失败,原因：'.$res['err_code'].' '.$res['err_code_des']],'errPay/WechatServiceH5payLog',1);
                throw new \think\Exception('调取微信支付失败,原因：'.$res['err_code'].' '.$res['err_code_des']);
			}
		}
		else{
            fdump_api(['param' => $_POST,'res' => $res,'config' => $config,'input' => $input,'order_no' => $order_no,'money' => $money,'msg' => '调取微信支付失败,原因：'.$res['return_msg']],'errPay/WechatServiceH5payLog',1);
            throw new \think\Exception('调取微信支付失败,原因：'.$res['return_msg']);
		}
	}

	//付款码支付
	public function scanPay($auth_code, $order_no, $money, $pay_type){
		$input = new \WxPayMicroPay();
		$input->SetAuth_code($auth_code);
		$input->SetBody($order_no);
		$input->SetTotal_fee($money * 100);
		$input->SetOut_trade_no($order_no);

		//服务商子商户支付
		if(isset($this->config['sub_mch_id']) && $this->config['sub_mch_id']){
			$input->SetSub_mch_id($this->config['sub_mch_id']);
		}
		
		// 分账	
		if(isset($this->config['open_weixin_profit_sharing']) && $this->config['open_weixin_profit_sharing']){
			$input->SetProfit_sharing('Y');
		}

		$config = new \WxPayConfig();
		$config->app_id = $this->config['pay_weixin_appid'];
		$config->merchant_id = $this->config['pay_weixin_mchid'];
		$config->key = $this->config['pay_weixin_key'];
		$config->secret = $this->config['pay_weixin_appsecret'];

		$result = \WxPayApi::micropay($config, $input, 5);
		if(!array_key_exists("return_code", $result)
			|| !array_key_exists("result_code", $result))
		{
            fdump_api(['param' => $_POST,'result' => $result,'config' => $config,'input' => $input,'auth_code' => $auth_code,'order_no' => $order_no,'money' => $money,'msg' => "接口调用失败,请确认是否输入是否有误！"],'errPay/WechatServiceScanPayLog',1);
            throw new \WxPayException($result['return_msg']??"接口调用失败,请确认是否输入是否有误！");
		}
		$result["err_code"] = $result["err_code"] ?? '';
		$result["err_code_des"] = $result["err_code_des"] ?? '';
		$result["return_code"] = $result["return_code"] ?? '';
		$result["result_code"] = $result["result_code"] ?? '';
		$out_trade_no = $input->GetOut_trade_no();
		if($result["return_code"] == "SUCCESS" &&
		   $result["result_code"] == "FAIL" && 
		   $result["err_code"] != "USERPAYING" && 
		   $result["err_code"] != "SYSTEMERROR")
		{
            fdump_api(['param' => $_POST,'result' => $result,'config' => $config,'input' => $input,'auth_code' => $auth_code,'order_no' => $order_no,'money' => $money,'msg' => $result['err_code_des']],'errPay/WechatServiceScanPayLog',1);
            throw new \WxPayException($result['err_code_des']);
		}

		//③、确认支付是否成功
		$queryTimes = 10;
		while($queryTimes > 0)
		{
			$succResult = 0;
			$queryResult = $this->scanQuery($out_trade_no, $succResult);
			//如果需要等待1s后继续
			if($succResult == 2){
				sleep(2);
				$queryTimes--;
				continue;
			} else if($succResult == 1){//查询成功
				return ['status'=>1, 'transaction_no' => $queryResult['transaction_id']];
			} else {//订单交易失败
				break;
			}
		}

		//④、次确认失败，则撤销订单
		if(!$this->scanCancel($out_trade_no))
		{
			throw new \WxpayException("撤销单失败！");
		}
		else{
			throw new \WxpayException("用户超时未支付，请重新扫码！");
		}
		throw new \WxpayException("支付失败！");
	}

	public function scanQuery($out_trade_no, &$succCode)
	{
		$queryOrderInput = new \WxPayOrderQuery();
		$queryOrderInput->SetOut_trade_no($out_trade_no);
		
		//服务商子商户支付
		if(isset($this->config['sub_mch_id']) && $this->config['sub_mch_id']){
			$queryOrderInput->SetSub_mch_id($this->config['sub_mch_id']);
		}

		$config = new \WxPayConfig();
		$config->app_id = $this->config['pay_weixin_appid'];
		$config->merchant_id = $this->config['pay_weixin_mchid'];
		$config->key = $this->config['pay_weixin_key'];
		$config->secret = $this->config['pay_weixin_appsecret'];

		$result = \WxPayApi::orderQuery($config, $queryOrderInput);
		$result["err_code"] = $result["err_code"] ?? '';
		$result["err_code_des"] = $result["err_code_des"] ?? '';
		$result["return_code"] = $result["return_code"] ?? '';
		$result["result_code"] = $result["result_code"] ?? '';
		if($result["return_code"] == "SUCCESS" 
			&& $result["result_code"] == "SUCCESS")
		{
			//支付成功
			if($result["trade_state"] == "SUCCESS"){
				$succCode = 1;
			   	return $result;
			}
			//用户支付中
			else if($result["trade_state"] == "USERPAYING"){
				$succCode = 2;
				return false;
			}
		}
		
		//如果返回错误码为“此交易订单号不存在”则直接认定失败
		if(isset($result["err_code"]) && $result["err_code"] == "ORDERNOTEXIST")
		{
			$succCode = 0;
		} else{
			//如果是系统错误，则后续继续
			$succCode = 2;
		}
		return false;
	}

	public function scanCancel($out_trade_no, $depth = 0)
	{
		if($depth > 10){
			return false;
		}
		
		$clostOrder = new \WxPayReverse();
		$clostOrder->SetOut_trade_no($out_trade_no);

		$config = new \WxPayConfig();
		$config->app_id = $this->config['pay_weixin_appid'];
		$config->merchant_id = $this->config['pay_weixin_mchid'];
		$config->key = $this->config['pay_weixin_key'];
		$config->secret = $this->config['pay_weixin_appsecret'];
		$root_path = str_replace('/v20/public', '', getcwd());
		$config->sslCertPath = $root_path.$this->config['pay_weixin_client_cert'];
		$config->sslKeyPath = $root_path.$this->config['pay_weixin_client_key'];
			

		$result = \WxPayApi::reverse($config, $clostOrder);
		$result["err_code"] = $result["err_code"] ?? '';
		$result["err_code_des"] = $result["err_code_des"] ?? '';
		$result["return_code"] = $result["return_code"] ?? '';
		$result["result_code"] = $result["result_code"] ?? '';
		
		//接口调用失败
		if($result["return_code"] != "SUCCESS"){
			return false;
		}
		
		//如果结果为success且不需要重新调用撤销，则表示撤销成功
		if($result["result_code"] == "SUCCESS" 
			&& $result["recall"] == "N"){
			return true;
		} else if($result["recall"] == "Y") {
			return $this->scanCancel($out_trade_no, ++$depth);
		}
		return false;
	}

	/**
	 * 异步通知
	 */
	public function notice(){
		require_once '../extend/pay/wechat_sdk/lib/WxPay.Notify.php';
		$config = new \WxPayConfig();

		switch ($this->agent) {
			case 'wechat_h5'://微信端
				$config->app_id = $this->config['pay_weixin_appid'];
				$config->merchant_id = $this->config['pay_weixin_mchid'];
				$config->key = $this->config['pay_weixin_key'];
				$config->secret = $this->config['pay_weixin_appsecret'];
				break;
			case 'iosapp':
			case 'androidapp':
				$config->app_id = $this->config['pay_weixinapp_appid'];
				$config->merchant_id = $this->config['pay_weixinapp_mchid'];
				$config->key = $this->config['pay_weixinapp_key'];
				$config->secret = $this->config['pay_weixinapp_appsecret'];
				break;
			case 'wechat_mini':
				$config->app_id = $this->config['pay_wxapp_appid'];
				$config->merchant_id = $this->config['pay_wxapp_mchid'];
				$config->key = $this->config['pay_wxapp_key'];
				$config->secret = $this->config['pay_wxapp_appsecret'];
				break;
			case 'h5':
				$config->app_id = $this->config['pay_weixinh5_appid'];
				$config->merchant_id = $this->config['pay_weixinh5_mchid'];
				$config->key = $this->config['pay_weixinh5_key'];
				$config->secret = $this->config['pay_weixinh5_appsecret'];
				break;
			
			default:
				throw new \think\Exception("当前端口未对接，不能使用");
				break;
		}	

		$msg = "";
		$res = \WxPayApi::notify($config, array($this, 'NotifyCallBack'), $msg);
		if($msg){
			throw new \think\Exception($msg);
		}
		return $res;
	}

	//异步通知回调函数
	public function NotifyCallBack($objData){
		$data = $objData->GetValues();
		if($data['return_code'] == 'SUCCESS'){
			if($data['result_code'] == 'SUCCESS'){
				return [
					'paid_money' => $data['total_fee'],
					'paid_time' => strtotime($data['time_end']),
					'transaction_no' => $data['transaction_id']
				];
			}
			else{
				throw new \think\Exception($data['err_code'].' '.$data['err_code_des']);
			}
		}
		else{
			throw new \think\Exception($data['return_msg']);
		}
	}

	//查询订单支付状态
	public function query($order_no){

		$config = new \WxPayConfig();
		switch ($this->agent) {
			case 'wechat_h5'://微信端
				$config->app_id = $this->config['pay_weixin_appid'];
				$config->merchant_id = $this->config['pay_weixin_mchid'];
				$config->key = $this->config['pay_weixin_key'];
				$config->secret = $this->config['pay_weixin_appsecret'];
				break;
			case 'iosapp':
			case 'androidapp':
				$config->app_id = $this->config['pay_weixinapp_appid'];
				$config->merchant_id = $this->config['pay_weixinapp_mchid'];
				$config->key = $this->config['pay_weixinapp_key'];
				$config->secret = $this->config['pay_weixinapp_appsecret'];
				break;
			case 'wechat_mini':
				$config->app_id = $this->config['pay_wxapp_appid'];
				$config->merchant_id = $this->config['pay_wxapp_mchid'];
				$config->key = $this->config['pay_wxapp_key'];
				$config->secret = $this->config['pay_wxapp_appsecret'];
				break;
			case 'h5':
				$config->app_id = $this->config['pay_weixinh5_appid'];
				$config->merchant_id = $this->config['pay_weixinh5_mchid'];
				$config->key = $this->config['pay_weixinh5_key'];
				$config->secret = $this->config['pay_weixinh5_appsecret'];
				break;
			
			default:
				throw new \think\Exception("当前端口未对接，不能使用");
				break;
		}

		$input = new \WxPayOrderQuery();
		$input->SetOut_trade_no($order_no);
		$res = \WxPayApi::orderQuery($config, $input);
		if($res['return_code'] == 'SUCCESS'){
			if($res['result_code'] == 'SUCCESS'){
				if($res['trade_state'] == 'SUCCESS'){
					return [
						'status' => 1,
						'paid_money' => $res['total_fee'],
						'paid_time' => strtotime($res['time_end']),
						'transaction_no' => $res['transaction_id']
					];
				}
				else{
					throw new \think\Exception($res['trade_state_desc']);
				}
			}
			else{
				throw new \think\Exception($res['err_code'].' '.$res['err_code_des']);
			}
		}
		else{
			throw new \think\Exception($res['return_msg']);
		}
	}

	/**
	 * 退款
	 * @param  [type] $order_no       本系统支付单号
	 * @param  [type] $total_fee 	  总支付金额（单位：分）
	 * @param  [type] $transaction_no 第三方交易流水号（防止某些平台需要）
	 * @param  [type] $refund_money   退款金额（单位：分）
	 * @return [type]                 [description]
	 */
	public function refund($order_no, $total_fee, $refund_money, $transaction_no = ''){
		$out_trade_no = $order_no;
		$refund_fee = $refund_money;
		$input = new \WxPayRefund();
		$input->SetOut_trade_no($out_trade_no);
		$input->SetTotal_fee($total_fee);
		$input->SetRefund_fee($refund_fee);

		$config = new \WxPayConfig();
		$root_path = str_replace('/v20/public', '', getcwd());
		$root_path = str_replace('\v20\public', '', $root_path);//防止windows客户
		
		switch ($this->agent) {
			case 'wechat_h5'://微信端
				$config->app_id = $this->config['pay_weixin_appid'];
				$config->merchant_id = $this->config['pay_weixin_mchid'];
				$config->key = $this->config['pay_weixin_key'];
				$config->secret = $this->config['pay_weixin_appsecret'];
				$config->sslCertPath = $root_path.$this->config['pay_weixin_client_cert'];
				$config->sslKeyPath = $root_path.$this->config['pay_weixin_client_key'];
                if($this->config['pay_weixin_mchid'] == cfg('pay_weixin_mchid') && $this->config['pay_weixin_key'] == cfg('pay_weixin_key')){
                    $config->sslCertPath = $_SERVER['DOCUMENT_ROOT'].cfg('pay_weixin_client_cert');
                    $config->sslKeyPath = $_SERVER['DOCUMENT_ROOT'].cfg('pay_weixin_client_key');
                }
				break;
			case 'iosapp':
			case 'androidapp':
				$config->app_id = $this->config['pay_weixinapp_appid'];
				$config->merchant_id = $this->config['pay_weixinapp_mchid'];
				$config->key = $this->config['pay_weixinapp_key'];
				$config->secret = $this->config['pay_weixinapp_appsecret'];
				$config->sslCertPath = $root_path.$this->config['pay_weixinapp_cert'];
				$config->sslKeyPath = $root_path.$this->config['pay_weixinapp_cert_key'];
                if($this->config['pay_weixinapp_mchid'] == cfg('pay_weixinapp_mchid') && $this->config['pay_weixinapp_key'] == cfg('pay_weixinapp_key')){
                    $config->sslCertPath = $_SERVER['DOCUMENT_ROOT'].cfg('pay_weixinapp_cert');
                    $config->sslKeyPath = $_SERVER['DOCUMENT_ROOT'].cfg('pay_weixinapp_cert_key');
                }
				break;
			case 'wechat_mini':
				$config->app_id = $this->config['pay_wxapp_appid'];
				$config->merchant_id = $this->config['pay_wxapp_mchid'];
				$config->key = $this->config['pay_wxapp_key'];
				$config->secret = $this->config['pay_wxapp_appsecret'];
				$config->sslCertPath = $root_path.($this->config['pay_weixin_client_cert']??$this->config['pay_wxapp_cert']);
				$config->sslKeyPath = $root_path.$this->config['pay_wxapp_cert_key'];
                if($this->config['pay_wxapp_mchid'] == cfg('pay_wxapp_mchid') && $this->config['pay_wxapp_key'] == cfg('pay_wxapp_key')){
                    $config->sslCertPath = $_SERVER['DOCUMENT_ROOT'].cfg('pay_wxapp_cert');
                    $config->sslKeyPath = $_SERVER['DOCUMENT_ROOT'].cfg('pay_wxapp_cert_key');
                }
				break;
			case 'h5':
				$config->app_id = $this->config['pay_weixinh5_appid'];
				$config->merchant_id = $this->config['pay_weixinh5_mchid'];
				$config->key = $this->config['pay_weixinh5_key'];
				$config->secret = $this->config['pay_weixinh5_appsecret'];
				$config->sslCertPath = $root_path.$this->config['pay_weixinh5_client_cert'];
				$config->sslKeyPath = $root_path.$this->config['pay_weixinh5_client_key'];
                if($this->config['pay_weixinh5_mchid'] == cfg('pay_weixinh5_mchid') && $this->config['pay_weixinh5_key'] == cfg('pay_weixinh5_key') && cfg('pay_weixinh5_client_cert')){
                    $config->sslCertPath = $_SERVER['DOCUMENT_ROOT'].cfg('pay_weixinh5_client_cert');
                    $config->sslKeyPath = $_SERVER['DOCUMENT_ROOT'].cfg('pay_weixinh5_client_key');
                }
				break;
			
			default:
				throw new \think\Exception("当前端口未对接，不能使用");
				break;
		}

		//服务商子商户支付
		if(isset($this->config['sub_mch_id']) && $this->config['sub_mch_id']){
			$input->SetSub_mch_id($this->config['sub_mch_id']);
			$config->sslCertPath = $root_path.cfg('pay_weixin_sp_client_cert');
			$config->sslKeyPath = $root_path.cfg('pay_weixin_sp_client_key');
			
		}

		//$refund_no = "sdkphp".date("YmdHis").rand(1000,9999);
        $refund_no = 'R'.$order_no.date("His");
	    $input->SetOut_refund_no($refund_no);
	    $input->SetOp_user_id($config->GetMerchantId());
		$res = \WxPayApi::refund($config, $input);

		fdump_sql([$config, $input, $res], 'wechat_refund');

		if($res['return_code'] == 'SUCCESS'){
			if($res['result_code'] == 'SUCCESS'){
				return [
					'refund_no' => $refund_no
				];
			}
			else{
				throw new \think\Exception($res['err_code'].' '.$res['err_code_des']);
			}
		}
		else{
			throw new \think\Exception($res['return_msg']);
		}
	}

	// 请求单次分账
	public function profitSharing($orderInfo){

		$input = new \WxPayProfitSharing();

		$config = new \WxPayConfig();
		
		// 设置支付公共参数
		$this->setConfig($config);

		// 自定义订单号
		$input->SetOut_order_no($orderInfo['order_id'].'_share');

		//服务商子商户号
		if(isset($this->config['sub_mch_id']) && $this->config['sub_mch_id']){
			$input->SetSub_mch_id($this->config['sub_mch_id']);
		}

		// 分账接收方列表
	    $input->SetReceivers($orderInfo['receivers']);

		// 微信支付订单号
	    $input->SetTransaction_id($orderInfo['third_id']);

		$res = \WxPayApi::ProfitSharing($config, $input);
		(new WeixinProfitSharingRecordService())->updateThis(['third_id'=>$orderInfo['third_id']], ['result' => json_encode([$input,$res])]);
		if($res['return_code'] == 'SUCCESS'){
			if($res['result_code'] == 'SUCCESS'){
				return [
					'out_order_no' => $res['out_order_no'] ?? '',
					'order_id' => $res['order_id'] ?? '',
				];
			}
			else{
				if($res['err_code'] != 'ORDER_NOT_READY'){
					(new WeixinProfitSharingRecordService())->setCountInc(['third_id'=>$orderInfo['third_id']]);
				}
				throw new \think\Exception($res['err_code'].' '.$res['err_code_des']);
			}
		}
		else{
			throw new \think\Exception($res['err_code'].' '.$res['err_code_des']);
		}
	}

	// 添加分账接收方
	public function profitsharingaddreceiver($orderInfo){
		$config = new \WxPayConfig();
		
		// 设置支付公共参数
		$this->setConfig($config);

		$receivers = $orderInfo['receivers'] ? json_decode($orderInfo['receivers'], true) : [];
		foreach($receivers as $_receiver){
			$_receiver['relation_type'] = $_receiver['type'] == 'MERCHANT_ID' ? 'SERVICE_PROVIDER' : 'USER';
			if($_receiver['type'] == 'MERCHANT_ID'){
				$name = '';
				switch ($this->agent) {
					case 'wechat_h5'://微信端
						$name = $this->config['pay_weixin_mch_name'];
						break;
					case 'wechat_mini':
						$name = $this->config['pay_wxapp_mch_name'];
						break;
					case 'iosapp':
					case 'androidapp':
						$name = $this->config['pay_weixinapp_mch_name'];
						break;
					case 'h5':
						$name = $this->config['pay_weixinh5_mch_name'];
						break;
				}
				$_receiver['name'] = $name;
			}
			unset($_receiver['amount'],$_receiver['description']);

			
			$input = new \WxPayProfitSharing();

			//服务商子商户号
			if(isset($this->config['sub_mch_id']) && $this->config['sub_mch_id']){
				$input->SetSub_mch_id($this->config['sub_mch_id']);
			}

			// 分账接收方列表
			$input->SetReceiver(json_encode($_receiver,JSON_UNESCAPED_UNICODE));

			$res = \WxPayApi::Profitsharingaddreceiver($config, $input);
			(new WeixinProfitSharingRecordService())->updateThis(['third_id'=>$orderInfo['third_id']], ['result' => json_encode([$input,$res])]);
			if($res['return_code'] == 'SUCCESS'){
				if($res['result_code'] == 'SUCCESS'){
					return [
						'out_order_no' => $res['out_order_no'] ?? '',
						'order_id' => $res['order_id'] ?? '',
					];
				}
				else{
					throw new \think\Exception($res['err_code'].' '.$res['err_code_des']);
				}
			}
			else{
				throw new \think\Exception($res['err_code'].' '.$res['err_code_des']);
			}
		}
	}

	// 完成分账
	public function profitsharingfinish($orderInfo){

		$input = new \WxPayProfitSharing();

		$config = new \WxPayConfig();

		// 设置支付公共参数
		$this->setConfig($config);

		// 自定义订单号
		$input->SetOut_order_no($orderInfo['order_id'].'_finish');

		//服务商子商户号
		if(isset($this->config['sub_mch_id']) && $this->config['sub_mch_id']){
			$input->SetSub_mch_id($this->config['sub_mch_id']);
		}

		// 订单完成描述
		$input->SetDescription('完成分账');

		// 微信支付订单号
		$input->SetTransaction_id($orderInfo['third_id']);

		$res = \WxPayApi::Profitsharingfinish($config, $input);
		(new WeixinProfitSharingRecordService())->updateThis(['third_id'=>$orderInfo['third_id']], ['result' => json_encode([$input,$res])]);
		if($res['return_code'] == 'SUCCESS'){
			if($res['result_code'] == 'SUCCESS'){
				return [
					'out_order_no' => $res['out_order_no'] ?? '',
					'order_id' => $res['order_id'] ?? '',
				];
			}
			else{
				if($res['err_code'] != 'ORDER_NOT_READY'){
					(new WeixinProfitSharingRecordService())->setCountInc(['third_id'=>$orderInfo['third_id']]);
				}
				throw new \think\Exception($res['err_code'].' '.$res['err_code_des']);
			}
		}
		else{
			throw new \think\Exception($res['return_msg']);
		}
	}

	// 设置支付公共参数
	public function setConfig($config){
		$root_path = str_replace('/v20/public', '', getcwd());
		$root_path = str_replace('\v20\public', '', $root_path);//防止windows客户
		switch ($this->agent) {
			case 'wechat_h5'://微信端
				$config->app_id = $this->config['pay_weixin_appid'];
				$config->merchant_id = $this->config['pay_weixin_mchid'];
				$config->key = $this->config['pay_weixin_key'];
				$config->secret = $this->config['pay_weixin_appsecret'];
				$config->sslCertPath = $root_path.$this->config['pay_weixin_client_cert'];
				$config->sslKeyPath = $root_path.$this->config['pay_weixin_client_key'];

				break;
			case 'wechat_mini':
				$config->app_id = $this->config['pay_wxapp_appid'];
				$config->merchant_id = $this->config['pay_wxapp_mchid'];
				$config->key = $this->config['pay_wxapp_key'];
				$config->secret = $this->config['pay_wxapp_appsecret'];
				$config->sslCertPath = $root_path.$this->config['pay_wxapp_cert'];
				$config->sslKeyPath = $root_path.$this->config['pay_wxapp_cert_key'];
				break;
			case 'iosapp':
			case 'androidapp':
				$config->app_id = $this->config['pay_weixinapp_appid'];
				$config->merchant_id = $this->config['pay_weixinapp_mchid'];
				$config->key = $this->config['pay_weixinapp_key'];
				$config->secret = $this->config['pay_weixinapp_appsecret'];
				$config->sslCertPath = $root_path.$this->config['pay_weixinapp_cert'];
				$config->sslKeyPath = $root_path.$this->config['pay_weixinapp_cert_key'];
				break;
			case 'h5':
				$config->app_id = $this->config['pay_weixinh5_appid'];
				$config->merchant_id = $this->config['pay_weixinh5_mchid'];
				$config->key = $this->config['pay_weixinh5_key'];
				$config->secret = $this->config['pay_weixinh5_appsecret'];
				$config->sslCertPath = $root_path.$this->config['pay_weixinh5_cert'];
				$config->sslKeyPath = $root_path.$this->config['pay_weixinh5_cert_key'];
				break;
			default:
				throw new \think\Exception("当前端口未对接，不能使用");
				break;
		}
	}
}