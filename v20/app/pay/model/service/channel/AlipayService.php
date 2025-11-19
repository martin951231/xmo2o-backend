<?php
/**
 * 微信官方通道
 * add by lumin
 * 2020-05-25
 */

namespace app\pay\model\service\channel;

use net\Http;
require_once '../extend/pay/alipay_sdk/AopClient.php';

class AlipayService{
	private $agent = '';
	private $config = [];//配置参数

	public function __construct($agent, $config = []){
		$this->agent = $agent;
		$this->config = $config;
	}

	public function pay($order_no, $money, $extra_cfg){
		if(empty($order_no) || empty($money)){
			throw new \think\Exception('订单ID或支付金额必填！');
		}
		switch ($this->agent) {
			case 'alipay':
				return $this->AlipayH5($order_no, $money, $extra_cfg);
				break;
			case 'h5':
				return $this->H5pay($order_no, $money, $extra_cfg);
				break;
			case 'iosapp':
			case 'androidapp':
				return $this->Apppay($order_no, $money, $extra_cfg);
				break;

			default:
				throw new \think\Exception('当前端口未对接，不能使用');
				break;
		}
	}

	/**
	 * 支付宝浏览器
	 */
	private function AlipayH5($order_no, $money, $extra_cfg){
		require_once '../extend/pay/alipay_sdk/request/AlipayTradeWapPayRequest.php';
		$aop = new \AopClientSdk ();
		$aop->gatewayUrl 			= 'https://openapi.alipay.com/gateway.do';
		$aop->appId 				= $this->config['pay_alipay_appid'];
		$aop->rsaPrivateKey 		= $this->config['pay_alipay_merchant_private_key'];
		$aop->alipayrsaPublicKey 	= $this->config['pay_alipay_public_key'];
		$aop->apiVersion 			= '1.0';
		$aop->signType 				= $this->config['pay_alipay_sign_type'];
		$aop->postCharset 			= 'utf-8';
		$aop->format 				= 'json';
		$request = new \AlipayTradeWapPayRequest ();
		$request->setBizContent("{" .
		"\"subject\":\"订单编号：".$extra_cfg['business_order_sn']."\"," .
		"\"out_trade_no\":\"".$order_no."\"," .
		"\"total_amount\":".$money."," .
		"\"quit_url\":\"https://hf.pigcms.com/wap.php\"," .//这里需要规划一下，支付过程中中断支付要跳回哪里？
		"\"product_code\":\"QUICK_WAP_WAY\"" .
		"  }");
		$request->setNotifyUrl((String) url('alipay_notify', [], false, true));//异步通知地址
		$request->setReturnUrl(cfg('site_url').'/packapp/plat/pages/pay/result?orderid='.$order_no);//支付完成后跳转地址
		
		$result = $aop->pageExecute ( $request); //现在使用了前后端分离的模式来调用，所以我们需要给前端返回一个url，让他们跳转这个url
		return [
			'type' => 'h5alipay',
			'info' => $result			
		];
	}

	/**
	 * 手机浏览器支付
	 */
	private function H5pay($order_no, $money, $extra_cfg){
		require_once '../extend/pay/alipay_sdk/request/AlipayTradeWapPayRequest.php';
		$aop = new \AopClientSdk ();
		$aop->gatewayUrl 			= 'https://openapi.alipay.com/gateway.do';
		$aop->appId 				= $this->config['pay_alipayh5_appid'];
		$aop->rsaPrivateKey 		= $this->config['pay_alipayh5_merchant_private_key'];
		$aop->alipayrsaPublicKey 	= $this->config['pay_alipayh5_public_key'];
		$aop->apiVersion 			= '1.0';
		$aop->signType 				= $this->config['pay_alipayh5_sign_type'];
		$aop->postCharset 			= 'utf-8';
		$aop->format 				= 'json';
		$request = new \AlipayTradeWapPayRequest ();
		$request->setBizContent("{" .
		"\"subject\":\"订单编号：".$extra_cfg['business_order_sn']."\"," .
		"\"out_trade_no\":\"".$order_no."\"," .
		"\"total_amount\":".$money."," .
		"\"quit_url\":\"http://www.taobao.com/product/113714.html\"," .//这里需要规划一下，支付过程中中断支付要跳回哪里？
		"\"product_code\":\"QUICK_WAP_WAY\"" .
		"  }");
		$request->setNotifyUrl((String) url('alipay_notify', [], false, true));//异步通知地址
		$request->setReturnUrl(cfg('site_url').'/packapp/plat/pages/pay/result?orderid='.$order_no);//支付完成后跳转地址
		$result = $aop->pageExecute ( $request); //现在使用了前后端分离的模式来调用，所以我们需要给前端返回一个url，让他们跳转这个url
		return [
			'type' => 'h5alipay',
			'info' => $result			
		];
	}

	/**
	 * App端口支付
	 */
	private function Apppay($order_no, $money, $extra_cfg){
		require_once '../extend/pay/alipay_sdk/request/AlipayTradeAppPayRequest.php';
		$aop = new \AopClientSdk ();
		$aop->gatewayUrl 			= 'https://openapi.alipay.com/gateway.do';
		$aop->appId 				= $this->config['new_pay_alipay_app_appid'];
		$aop->rsaPrivateKey 		= $this->config['new_pay_alipay_app_private_key'];
		$aop->alipayrsaPublicKey	= $this->config['new_pay_alipay_app_public_key'];
		$aop->apiVersion 			= '1.0';
		$aop->signType 				= 'RSA2';
		$aop->postCharset			= 'utf-8';
		$aop->format 				= 'json';
		$request = new \AlipayTradeAppPayRequest ();
		$request->setBizContent("{" .
		"\"subject\":\"订单编号：".$extra_cfg['business_order_sn']."\"," .
		"\"out_trade_no\":\"".$order_no."\"," .
		"\"total_amount\":".$money."" .
		"  }");
		$request->setNotifyUrl((String) url('alipay_notify', [], false, true));

		$result = $aop->sdkExecute ( $request);

		return [
			'type' => 'sdk',
			'info' => $result
		];
	}

	//支付宝异步通知
	public function notice(){
		$arr = request()->param();
		$aop = new \AopClientSdk ();
		switch ($this->agent) {
			case 'alipay':
				$aop->alipayrsaPublicKey = $this->config['pay_alipay_public_key'];
				$result = $aop->rsaCheckV1($arr, $this->config['pay_alipay_public_key'], $this->config['pay_alipay_sign_type']);
				break;
			case 'h5':
				$aop->alipayrsaPublicKey = $this->config['pay_alipayh5_public_key'];
				$result = $aop->rsaCheckV1($arr, $this->config['pay_alipayh5_public_key'], $this->config['pay_alipayh5_sign_type']);
				break;
			case 'iosapp':
			case 'androidapp':
				$aop->alipayrsaPublicKey = $this->config['new_pay_alipay_app_public_key'];
				$result = $aop->rsaCheckV1($arr, $this->config['new_pay_alipay_app_public_key'], 'RSA2');
				break;

			default:
				throw new \think\Exception('当前端口未对接，不能使用');
				break;
		}
		if($result) {
			$trade_status = $arr['trade_status'];
			if($trade_status == 'TRADE_FINISHED' || $trade_status == 'TRADE_SUCCESS'){
				return [
					'paid_money' => bcmul($arr['total_amount']??$arr['buyer_pay_amount'],100,0),
					'paid_time' => strtotime($arr['gmt_payment']),
					'transaction_no' => $arr['trade_no']
				];
			}
		}
	}

	//查询订单支付状态
	public function query($order_no){
		$aop = new \AopClientSdk ();
		require_once '../extend/pay/alipay_sdk/request/AlipayTradeQueryRequest.php';
		switch ($this->agent) {
			case 'alipay':
				$aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
				$aop->appId = $this->config['pay_alipay_appid'];
				$aop->rsaPrivateKey = $this->config['pay_alipay_merchant_private_key'];
				$aop->alipayrsaPublicKey=$this->config['pay_alipay_public_key'];
				$aop->apiVersion = '1.0';
				$aop->signType = $this->config['pay_alipay_sign_type'];
				$aop->postCharset='utf-8';
				$aop->format='json';
				$request = new \AlipayTradeQueryRequest ();

				break;
			case 'h5':
				$aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
				$aop->appId = $this->config['pay_alipayh5_appid'];
				$aop->rsaPrivateKey = $this->config['pay_alipayh5_merchant_private_key'];
				$aop->alipayrsaPublicKey=$this->config['pay_alipayh5_public_key'];
				$aop->apiVersion = '1.0';
				$aop->signType = $this->config['pay_alipayh5_sign_type'];
				$aop->postCharset='utf-8';
				$aop->format='json';
				$request = new \AlipayTradeQueryRequest ();

				break;
			case 'iosapp':
			case 'androidapp':
				$aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
				$aop->appId = $this->config['new_pay_alipay_app_appid'];
				$aop->rsaPrivateKey = $this->config['new_pay_alipay_app_private_key'];
				$aop->alipayrsaPublicKey=$this->config['new_pay_alipay_app_public_key'];
				$aop->apiVersion = '1.0';
				$aop->signType = 'RSA2';
				$aop->postCharset='utf-8';
				$aop->format='json';
				$request = new \AlipayTradeQueryRequest ();
				break;

			default:
				throw new \think\Exception('当前端口未对接，不能使用');
				break;
		}
		$request->setBizContent("{" .
		"\"out_trade_no\":\"".$order_no."\"" .
		"  }");
		$result = $aop->execute ($request);
		$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
		$resultCode = $result->$responseNode->code;
		$tradeStatus = $result->$responseNode->trade_status;
		if(!empty($resultCode)&&$resultCode == 10000&&$tradeStatus!='WAIT_BUYER_PAY'){
			return [
				'status' => 1,
				'paid_money' => $result->$responseNode->total_amount*100,
				'paid_time' => strtotime($result->$responseNode->send_pay_date),
				'transaction_no' => $result->$responseNode->trade_no
			];
		} else {
			return [
				'status' => 2,
			];
		}
	}

	public function scanPay($auth_code, $order_no, $money, $pay_type){
		$param = [];
		$param['app_id'] = $this->config['pay_alipayh5_appid'];
		$param['method'] = 'alipay.trade.pay';
		$param['charset'] = 'utf-8';
		$param['sign_type'] = $this->config['pay_alipayh5_sign_type'] ? $this->config['pay_alipayh5_sign_type'] : 'RSA';
		$param['timestamp'] = date('Y-m-d H:i:s');
		$param['version'] = '1.0';
		$biz_content = array(
				'out_trade_no' => $order_no,
				'scene' => 'bar_code',
				'auth_code' => $auth_code,
				'total_amount' => $money,
				'subject' => $order_no,
		);
		$param['biz_content'] = json_encode($biz_content,JSON_UNESCAPED_UNICODE);
		ksort($param);
		$stringToBeSigned = "";
		$i = 0;
		foreach ($param as $k => $v) {
			if (!empty($v) && "@" != substr($v, 0, 1)) {
				if ($i == 0) {
					$stringToBeSigned .= "$k" . "=" . "$v";
				} else {
					$stringToBeSigned .= "&" . "$k" . "=" . "$v";
				}
				$i++;
			}
		}

		$priKey = $this->config['pay_alipayh5_merchant_private_key'];
		$res = "-----BEGIN RSA PRIVATE KEY-----\n".wordwrap($priKey, 64, "\n", true)."\n-----END RSA PRIVATE KEY-----";

		if($param['sign_type'] == 'RSA2'){
			openssl_sign($stringToBeSigned, $sign, $res, OPENSSL_ALGO_SHA256);
		}else{
			openssl_sign($stringToBeSigned, $sign, $res);
		}

		if (empty($sign)) {
			throw new \think\Exception('支付宝收银商户密钥错误，请联系管理员解决。');
		}

		$sign = base64_encode($sign);

		$param['sign'] = $sign;
		$requestUrl = "https://openapi.alipay.com/gateway.do?";
		foreach ($param as $sysParamKey => $sysParamValue) {
			$requestUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
		}
		$requestUrl = substr($requestUrl, 0, -1);
		$return = Http::curlGet($requestUrl);
		$returnArr = json_decode($return,true);

        if (!empty($returnArr['alipay_trade_pay_response']) && "10000" == $returnArr['alipay_trade_pay_response']['code']) {
        	return ['status'=>1, 'transaction_no' => $returnArr['alipay_trade_pay_response']['trade_no']];
        }
        elseif (!empty($returnArr['alipay_trade_pay_response']) && "10003" == $returnArr['alipay_trade_pay_response']['code']){
        	return ['status'=>2];
        }
        throw new \think\Exception($returnArr['alipay_trade_pay_response']['sub_msg']);
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
		require_once '../extend/pay/alipay_sdk/request/AlipayTradeRefundRequest.php';
		$aop = new \AopClientSdk ();
		$aop->gatewayUrl 			= 'https://openapi.alipay.com/gateway.do';
		
		$aop->apiVersion 			= '1.0';
		$aop->postCharset 			= 'utf-8';
		$aop->format 				= 'json';

		switch ($this->agent) {
			case 'alipay':
				$aop->appId 				= $this->config['pay_alipay_appid'];
				$aop->rsaPrivateKey 		= $this->config['pay_alipay_merchant_private_key'];
				$aop->alipayrsaPublicKey 	= $this->config['pay_alipay_public_key'];
				$aop->signType 				= $this->config['pay_alipay_sign_type'];
				break;
			case 'h5':
				$aop->appId 				= $this->config['pay_alipayh5_appid'];
				$aop->rsaPrivateKey 		= $this->config['pay_alipayh5_merchant_private_key'];
				$aop->alipayrsaPublicKey 	= $this->config['pay_alipayh5_public_key'];
				$aop->signType 				= $this->config['pay_alipayh5_sign_type'];
				break;
			case 'iosapp':
			case 'androidapp':
				$aop->appId 				= $this->config['new_pay_alipay_app_appid'];
				$aop->rsaPrivateKey 		= $this->config['new_pay_alipay_app_private_key'];
				$aop->alipayrsaPublicKey 	= $this->config['new_pay_alipay_app_public_key'];
				$aop->signType 				= 'RSA2';
				break;

			default:
				throw new \think\Exception('退款：当前端口未对接，不能使用');
				break;
		}

		$refund_no = "sdkphp".date("YmdHis").rand(1000,9999);
		$request = new \AlipayTradeRefundRequest ();
		$request->setBizContent("{" .
		"\"out_trade_no\":\"".$order_no."\"," .
		"\"out_request_no\":\"".$refund_no."\"," .
		"\"refund_amount\":".get_format_number($refund_money/100,2) .
		"  }");
        fdump(['order_no'=>$order_no,'refund_no'=>$refund_no,'refund_money'=>$refund_money], "lumin_alipay_refund", 1);
        try {
            $result = $aop->execute($request);
            fdump(['result' => $result, 'refund_no' => $refund_no], "lumin_alipay_refund", 1);
            $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
            $resultCode = $result->$responseNode->code;
        }catch (\Exception $e) {
            fdump(['e'=> $e->getMessage(),'file'=>$e->getFile(),'lile'=>$e->getLine(),'code'=>$e->getCode()], "lumin_alipay_refund", 1);
        }
		if($result && !empty($resultCode)&&$resultCode == 10000){
			return [
				'refund_no' => $refund_no
			];
		} else {
            fdump(['responseNode'=>$responseNode,'responseNodeRet'=>$result->$responseNode], "lumin_alipay_refund", 1);
			throw new \think\Exception($result->$responseNode->code.':'.$result->$responseNode->msg.';'.$result->$responseNode->sub_code.':'.$result->$responseNode->sub_msg);
		}
	}
	
    public function getQueryDetail($order_no){
        $aop = new \AopClientSdk ();
        require_once '../extend/pay/alipay_sdk/request/AlipayTradeQueryRequest.php';
        switch ($this->agent) {
            case 'alipay':
                $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
                $aop->appId = $this->config['pay_alipay_appid'];
                $aop->rsaPrivateKey = $this->config['pay_alipay_merchant_private_key'];
                $aop->alipayrsaPublicKey=$this->config['pay_alipay_public_key'];
                $aop->apiVersion = '1.0';
                $aop->signType = $this->config['pay_alipay_sign_type'];
                $aop->postCharset='utf-8';
                $aop->format='json';
                $request = new \AlipayTradeQueryRequest ();

                break;
            case 'h5':
                $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
                $aop->appId = $this->config['pay_alipayh5_appid'];
                $aop->rsaPrivateKey = $this->config['pay_alipayh5_merchant_private_key'];
                $aop->alipayrsaPublicKey=$this->config['pay_alipayh5_public_key'];
                $aop->apiVersion = '1.0';
                $aop->signType = $this->config['pay_alipayh5_sign_type'];
                $aop->postCharset='utf-8';
                $aop->format='json';
                $request = new \AlipayTradeQueryRequest ();

                break;
            case 'iosapp':
            case 'androidapp':
                $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
                $aop->appId = $this->config['new_pay_alipay_app_appid'];
                $aop->rsaPrivateKey = $this->config['new_pay_alipay_app_private_key'];
                $aop->alipayrsaPublicKey=$this->config['new_pay_alipay_app_public_key'];
                $aop->apiVersion = '1.0';
                $aop->signType = 'RSA2';
                $aop->postCharset='utf-8';
                $aop->format='json';
                $request = new \AlipayTradeQueryRequest ();
                break;

            default:
                throw new \think\Exception('当前端口未对接，不能使用');
                break;
        }
        $data = [
            'out_trade_no'  => $order_no,
            'query_options' => [
                'fund_bill_list',
                'voucher_detail_list',
                'discount_goods_detail',
                'mdiscount_amount',
                'trade_settle_info',
            ],
        ];
        $request->setBizContent(json_encode($data));
        $result = $aop->execute ($request);
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        $tradeStatus = $result->$responseNode->trade_status;
        return [
            'resultCode'     => $resultCode,
            'tradeStatus'    => $tradeStatus,
            'paid_money'     => $result->$responseNode->total_amount*100,
            'paid_time'      => strtotime($result->$responseNode->send_pay_date),
            'transaction_no' => $result->$responseNode->trade_no,
            'result'         => $result->$responseNode
        ];
    }
}