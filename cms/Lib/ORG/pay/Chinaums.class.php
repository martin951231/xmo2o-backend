<?php
/**
 * 银联分账支付需求对接
 * 包含支付、分账等方法
 * author lumin
 * 2021-01-16
 */

class Chinaums{
	public $gateway;
	public $config;
	public $merchant_get = 0;//给商家多少钱 单位：分
	public $platform_get = 0;//给平台多少钱 单位：分
	public $order_name = '';
    public $sysId = ''; //系统编号
	public function __construct(){
		$this->config['chinaums_appid'] = C("config.chinaums_appid");//银联分配的appid1
		$this->config['chinaums_appkey'] = C("config.chinaums_appkey");//银联分配的appkey1
		$this->config['chinaums_mid'] = C("config.chinaums_mid");//银联分配的支付商户号1
		/**
		 * 898140548160342  05056063   app   H5   公众号  
			898140548160340   05056061 小程序
		 */
		$this->config['chinaums_tid'] = C("config.chinaums_tid");//终端号1
		$this->config['chinaums_msgsrcid'] = C("config.chinaums_msgsrcid");//来源编号1
		$this->config['chinaums_md5key'] = C("config.chinaums_md5key");//md5密钥
		$this->gateway = 'https://api-mop.chinaums.com';//正式地址https://api-mop.chinaums.com

		//分账参数
		//$this->config['fenzhangURL'] = 'https://cloudpay.chinaums.com/channel/Business/UnifyMulti/';
        $this->config['fenzhangURL'] = 'https://im.chinaums.com/channel/Business/UnifyMulti/';
		$this->config['group_id'] = C("config.chinaums_group_id");
		$this->config['zg_private_key'] = C("config.zg_private_key");
		$this->config['zg_private_key_pwd'] = C("config.zg_private_key_pwd");
		$this->config['zg_plat_no'] = C("config.zg_plat_no");//平台企业用户号
		$this->config['zg_plat_card_no'] = C("config.zg_plat_card_no");//平台分账卡号
		$this->config['without_asset'] = C("config.open_chinaums_without_asset");  //小程序支付是否接入资管 1：不接入  0：接入
        $this->config['sysId'] = 'SH014';
	}

	//生成银联账单编号（因为他们有特别的要求，必须用他们的）
	public function createOrderNo($order_no, $ret = false){
		if(!$ret){
			if(strpos($order_no, '_')){
				$arr = explode('_', $order_no);
				$order_no = $arr[1];
			}
			$ori_order_no = $this->config['chinaums_msgsrcid'].$order_no;
		}
		else{
			$ori_order_no = str_replace($this->config['chinaums_msgsrcid'], '', $order_no);
		}
		return $ori_order_no;
	}

	public function createRefundNo(){
			
		$ori_order_no = $this->config['chinaums_msgsrcid'].date('YmdHis').rand(1000000,9999999);
		return $ori_order_no;
	}

	public function get_format_number($number = 0, $retain = 2){
		$number = number_format($number,$retain);
		if(strpos($number,'.') !== false){
			$number = rtrim($number,'0');
			$number = rtrim($number,'0');
			$number = rtrim($number,'.');
		}
		$number = str_replace(',','',$number);

		return $number;
	}

	//post请求
	public function postReq($url, $post_data){
		if(empty($url) || empty($post_data)){
			throw new Exception("调用异常!");	
		}
		$json_post_data = json_encode($post_data);

		$timestamp = date("YmdHis",time());
		$nonce = md5(uniqid(microtime(true),true));
		$str = bin2hex(hash('sha256', $json_post_data, true));
		$appid = $this->config['chinaums_appid'];
		$appkey = $this->config['chinaums_appkey'];
		$signature = base64_encode(hash_hmac('sha256', "$appid$timestamp$nonce$str", $appkey, true));
		$authorization = "OPEN-BODY-SIG AppId=\"".$appid."\", Timestamp=\"".$timestamp."\", Nonce=\"".$nonce."\", Signature=\"".$signature."\"";


		$curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,1); 
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); 
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
        $header = ['Content-type: application/json', 'Authorization:'.$authorization];
        if(!empty($header)){
            curl_setopt ( $curl, CURLOPT_HTTPHEADER, $header );
        }
        //请求时间
        $timeout = 30;
        curl_setopt ($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
        if(is_array($post_data)){
            $params = json_encode($post_data);
        }
       
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS,$params);
        $data = curl_exec($curl);
        curl_close($curl); //关闭cURL会话

		fdump_api([$url, $header, $post_data, $data], 'chinaums_req', true);
        if(empty($data)){
        	throw new Exception("调用银联接口发生异常!");
        }
    	$return = json_decode($data,true);
        if(isset($return['respCode']) && $return['respCode'] == 'RISK_CHECK_ERROR'){
            throw_exception($return['respDesc']);
        }
		if (isset($return['errCode'])) {
			if ($return['errCode'] == 'SUCCESS') {
				return $return;
			} else {
				throw new Exception(($return['errMsg'] ?? '') . ($return['errInfo'] ?? ''));
			}
		} else {
			return $return;
		}
	}

	//公众号微信支付
	public function Wechatpay($order_no, $money, $sub_mer_no = '',$village_id = 0){
		if(empty($order_no) || empty($money)){
			throw new Exception("订单ID或支付金额必填！");	
		}
		if(empty($this->config['openid'])){
			throw new Exception("未获取到openid，无法调起支付");
		}
		if (!is_null(C('config.chinaums_platform_sub_mchid')) && empty(C('config.chinaums_platform_sub_mchid'))) {
			throw new Exception("未配置平台商户号");
		}
		$order_id_arr = explode('_', $order_no);
        if($village_id && !empty($sub_mer_no)){
            $is_own = 5;
        }else{
            $is_own = 0;
        }
		$payment = M("Payment_money_order")->where(['orderid'=>$order_id_arr[1]])->data(['chinaums_merchant_already_get'=>$this->merchant_get, 'chinaums_platform_already_get' => $this->platform_get,'is_own'=>$is_own])->save();
        $url = $this->gateway.'/v1/netpay/wx/unified-order';
		$merOrderId = $this->createOrderNo($order_no);
		$post = [
			'requestTimestamp' => date('Y-m-d H:i:s'),
			'merOrderId' => $merOrderId,
			'mid' => empty(C("config.chinaums_mid_wx")) ? $this->config['chinaums_mid'] : C("config.chinaums_mid_wx"),
			'tid' => $this->config['chinaums_tid'],
			'instMid' => 'YUEDANDEFAULT',
			'totalAmount' => $this->get_format_number($money * 100),
			'notifyUrl' => C('config.site_url').'/source/chinaums_page_notice.php',//*********************************修改这里
			'subOpenId' => $this->config['openid'],
			'tradeType' => 'JSAPI',		
			'orderDesc' => $this->order_name	
		];
		if($sub_mer_no){
			if (C('config.chinaums_platform_sub_mchid')) {
				$post['divisionFlag'] = true;
				$post['platformAmount'] = 0;
				$post['subOrders'] = [
					[
						'mid' => $sub_mer_no,
						'totalAmount' => $this->get_format_number($this->merchant_get),
					],
					[
						'mid' => C('config.chinaums_platform_sub_mchid'),
						'totalAmount' => $this->get_format_number($this->platform_get),
					]
				];
			} else {
				$post['divisionFlag'] = true;
				$post['platformAmount'] = C('config.is_1238888_system') ? $this->get_format_number($this->platform_get) : 0;
				$post['subOrders'] = [
					[
						'mid' => $sub_mer_no,
						'totalAmount' => C('config.is_1238888_system') ? $this->get_format_number($this->merchant_get) : $this->get_format_number($money * 100),
					]
				];
			}
		}
		$result = $this->postReq($url, $post);
		return [
			'error' => 0,
			'weixin_param' => json_encode($result['jsPayRequest'])
		];
	}

	//小程序微信支付
	public function Wechatmini($order_no, $money, $sub_mer_no = '',$village_id=0){
		if(empty($order_no) || empty($money)){
			throw new Exception("订单ID或支付金额必填！");	
		}
		if(empty($this->config['openid'])){
			throw new Exception("未获取到openid，无法调起支付");
		}
		if (!is_null(C('config.chinaums_platform_sub_mchid')) && empty(C('config.chinaums_platform_sub_mchid'))) {
			throw new Exception("未配置平台商户号");
		}

		//不接资管平台只支持微信小程序支付
		if (C('config.open_chinaums_without_asset') == 1) {
			return $this->WechatminiWithoutAsset($order_no, $money, $sub_mer_no, $village_id);
		}

		$order_id_arr = explode('_', $order_no);
        if($village_id && !empty($sub_mer_no)){
            $is_own = 5;
        }else{
            $is_own = 0;
        }
		$payment = M("Payment_money_order")->where(['orderid'=>$order_id_arr[1]])->data(['chinaums_merchant_already_get'=>$this->merchant_get, 'chinaums_platform_already_get' => $this->platform_get, 'is_own'=>$is_own])->save();
		$url = $this->gateway.'/v1/netpay/wx/unified-order';
		$merOrderId = $this->createOrderNo($order_no);
		$post = [
			'requestTimestamp' => date('Y-m-d H:i:s'),
			'merOrderId' => $merOrderId,
			'mid' => empty(C("config.chinaums_mid_wxapp")) ? $this->config['chinaums_mid'] : C("config.chinaums_mid_wxapp"),
			'tid' => $this->config['chinaums_tid'],
			'instMid' => 'MINIDEFAULT',
			// 'subAppId' => C('config.pay_wxapp_appid'),
			'totalAmount' => $this->get_format_number($money * 100),
			'notifyUrl' => C('config.site_url').'/source/chinaums_page_notice.php',//*********************************修改这里
			'subOpenId' => $this->config['openid'],
			'tradeType' => 'MINI',	
			'orderDesc' => $this->order_name		
		];
		if($sub_mer_no){
			if (C('config.chinaums_platform_sub_mchid')) {
				$post['divisionFlag'] = true;
				$post['platformAmount'] = 0;
				$post['subOrders'] = [
					[
						'mid' => $sub_mer_no,
						'totalAmount' => $this->get_format_number($this->merchant_get),
					],
					[
						'mid' => C('config.chinaums_platform_sub_mchid'),
						'totalAmount' => $this->get_format_number($this->platform_get),
					]
				];
			} else {
				$post['divisionFlag'] = true;
				$post['platformAmount'] = C('config.is_1238888_system') ? $this->get_format_number($this->platform_get) : 0;
				$post['subOrders'] = [
					[
						'mid' => $sub_mer_no,
						'totalAmount' => C('config.is_1238888_system') ? $this->get_format_number($this->merchant_get) : $this->get_format_number($money * 100),
					]
				];
			}
		}
		$result = $this->postReq($url, $post);
		return [
			'error' => 0,
			'weixin_param' => json_encode($result['miniPayRequest'])
		];
	}

	//云闪付app
	public function UnionpayApp($order_no, $money, $sub_mer_no = ''){
		if(empty($order_no) || empty($money)){
			throw new Exception("订单ID或支付金额必填！");	
		}
		$order_id_arr = explode('_', $order_no);
		$payment = M("Payment_money_order")->where(['orderid'=>$order_id_arr[1]])->data(['chinaums_merchant_already_get'=>$this->merchant_get])->save();
		$url = $this->gateway.'/v1/netpay/uac/app-order';
		$merOrderId = $this->createOrderNo($order_no);
		$post = [
			'requestTimestamp' => date('Y-m-d H:i:s'),
			'merOrderId' => $merOrderId,
			'mid' => $this->config['chinaums_mid'],
			'tid' => $this->config['chinaums_tid'],
			'instMid' => 'APPDEFAULT',
			'totalAmount' => $this->get_format_number($money * 100),
			'notifyUrl' => C('config.site_url').'/source/chinaums_page_notice.php',//*********************************修改这里	
		];
		if($sub_mer_no){
			$post['divisionFlag'] = true;
			$post['platformAmount'] = $this->platform_get;
			$post['subOrders'] = [
				[
					'mid' => $sub_mer_no,
					'totalAmount' => $this->merchant_get,
				]
			];
		}
		$result = $this->postReq($url, $post);
		return [
			'error' => 0,
			'unionpay_param' => json_encode($result['appPayRequest'])
		];
	}

	//APP微信支付
	public function Wechatapp($order_no, $money, $sub_mer_no = '',$village_id=0){
		if(empty($order_no) || empty($money)){
			throw new Exception("订单ID或支付金额必填！");	
		}
		$order_id_arr = explode('_', $order_no);
		if($village_id && !empty($sub_mer_no)){
		    $is_own = 5;
        }else{
            $is_own = 0;
        }
		$payment = M("Payment_money_order")->where(['orderid'=>$order_id_arr[1]])->data(['chinaums_merchant_already_get'=>$this->merchant_get, 'chinaums_platform_already_get' => $this->platform_get, 'is_own'=>$is_own])->save();
		$url = $this->gateway.'/v1/netpay/wx/app-pre-order';
		$merOrderId = $this->createOrderNo($order_no);
		$post = [
			'requestTimestamp' => date('Y-m-d H:i:s'),
			'merOrderId' => $merOrderId,
			'mid' => empty(C("config.chinaums_mid_app")) ? $this->config['chinaums_mid'] : C("config.chinaums_mid_app"),
			'tid' => $this->config['chinaums_tid'],
			'subAppId' => C('config.pay_weixinapp_appid'),
			'instMid' => 'APPDEFAULT',
			'totalAmount' => $this->get_format_number($money * 100),
			'notifyUrl' => C('config.site_url').'/source/chinaums_page_notice.php',//*********************************修改这里
			'tradeType' => 'APP',//微信必传APP			
		];
		if($sub_mer_no){
			if (C('config.chinaums_platform_sub_mchid')) {
				$post['divisionFlag'] = true;
				$post['platformAmount'] = 0;
				$post['subOrders'] = [
					[
						'mid' => $sub_mer_no,
						'totalAmount' => $this->get_format_number($this->merchant_get),
					],
					[
						'mid' => C('config.chinaums_platform_sub_mchid'),
						'totalAmount' => $this->get_format_number($this->platform_get),
					]
				];
			} else {
				$post['divisionFlag'] = true;
				$post['platformAmount'] = C('config.is_1238888_system') ? $this->get_format_number($this->platform_get) : 0;
				$post['subOrders'] = [
					[
						'mid' => $sub_mer_no,
						'totalAmount' => C('config.is_1238888_system') ? $this->get_format_number($this->merchant_get) : $this->get_format_number($money * 100),
					]
				];
			}
		}
		$result = $this->postReq($url, $post);
		
		return [
			'error' => 0,
			'weixin_param' => $result['appPayRequest']
		];
	}

	//APP支付宝支付
	public function Alipayapp($order_no, $money, $sub_mer_no = '',$village_id=0){
		if(empty($order_no) || empty($money)){
			throw new Exception("订单ID或支付金额必填！");	
		}
		$order_id_arr = explode('_', $order_no);
        if($village_id && !empty($sub_mer_no)){
            $is_own = 5;
        }else{
            $is_own = 0;
        }
		$payment = M("Payment_money_order")->where(['orderid'=>$order_id_arr[1]])->data(['chinaums_merchant_already_get'=>$this->merchant_get, 'chinaums_platform_already_get' => $this->platform_get, 'is_own'=>$is_own])->save();
		$url = $this->gateway.'/v1/netpay/trade/app-pre-order';
		$merOrderId = $this->createOrderNo($order_no);
		$post = [
			'requestTimestamp' => date('Y-m-d H:i:s'),
			'merOrderId' => $merOrderId,
			'mid' => empty(C("config.chinaums_mid_app")) ? $this->config['chinaums_mid'] : C("config.chinaums_mid_app"),
			'tid' => $this->config['chinaums_tid'],
			// 'subAppId' => C('config.pay_weixinapp_appid'),
			'instMid' => 'APPDEFAULT',
			'totalAmount' => $this->get_format_number($money * 100),
			'notifyUrl' => C('config.site_url').'/source/chinaums_page_notice.php',//*********************************修改这里
			// 'tradeType' => 'APP',//微信必传APP			
		];
		if($sub_mer_no){
			if (C('config.chinaums_platform_sub_mchid')) {
				$post['divisionFlag'] = true;
				$post['platformAmount'] = 0;
				$post['subOrders'] = [
					[
						'mid' => $sub_mer_no,
						'totalAmount' => $this->get_format_number($this->merchant_get),
					],
					[
						'mid' => C('config.chinaums_platform_sub_mchid'),
						'totalAmount' => $this->get_format_number($this->platform_get),
					]
				];
			} else {
				$post['divisionFlag'] = true;
				$post['platformAmount'] = C('config.is_1238888_system') ? $this->get_format_number($this->platform_get) : 0;;
				$post['subOrders'] = [
					[
						'mid' => $sub_mer_no,
						'totalAmount' => C('config.is_1238888_system') ? $this->get_format_number($this->merchant_get) : $this->get_format_number($money * 100),
					]
				];
			}
		}
		$result = $this->postReq($url, $post);
		
		return [
			'error' => 0,
			'ali_param' => $result['appPayRequest']
		];
	}

	//普通H5支付宝(可能目前分配的商户号还不支持)
	public function Alipay($order_no, $money, $sub_mer_no = ''){
		if(empty($order_no) || empty($money)){
			throw new Exception("订单ID或支付金额必填！");	
		}
		if(empty($this->config['openid'])){
			throw new Exception("未获取到openid，无法调起支付");
		}
		$url = $this->gateway.'/v1/netpay/trade/create';
		$merOrderId = $this->createOrderNo($order_no);
		$post = [
			'requestTimestamp' => date('Y-m-d H:i:s'),
			'merOrderId' => $merOrderId,
			'mid' => $this->config['chinaums_mid'],
			'tid' => $this->config['chinaums_tid'],
			'instMid' => 'YUEDANDEFAULT',
			'totalAmount' => $this->get_format_number($money * 100),
			'notifyUrl' => C('config.site_url').'/source/chinaums_page_notice.php',//*********************************修改这里
			'userId' => $this->config['alipay_uid'],
			'tradeType' => 'JSAPI',			
		];
		if($sub_mer_no){
			$post['divisionFlag'] = true;
			$post['platformAmount'] = C('config.is_1238888_system') ? $this->get_format_number($this->platform_get) : 0;
			$post['subOrders'] = [
				[
					'mid' => $sub_mer_no,
					'totalAmount' => C('config.is_1238888_system') ? $this->get_format_number($this->merchant_get) : $this->get_format_number($money * 100),
				]
			];
		}
		$result = $this->postReq($url, $post);
		return [
			'error' => 0,
			'weixin_param' => json_encode($result['jsPayRequest'])
		];
	}

	//查询
	public function query($order_no){
		if(empty($order_no)){
			throw new Exception("订单ID或支付金额必填！");	
		}
		$payment_where = ['orderid'=>$order_no];
		$payment_money_order = M('Payment_money_order')->where($payment_where)->find();
		if(empty($payment_money_order)){
			throw new Exception("订单查询失败！没有找到支付信息！");	
		}
		$url = $this->gateway.'/v1/netpay/query';
		$merOrderId = $this->createOrderNo($order_no);
		$post = [
			'requestTimestamp' => date('Y-m-d H:i:s'),
			'merOrderId' => $merOrderId,
			'mid' => $this->config['chinaums_mid'],
			'tid' => $this->config['chinaums_tid'],
			'instMid' => 'YUEDANDEFAULT',
		];
		$result = $this->postReq($url, $post);
		$pay_types = [
            'Alipay 1.0' => 'alipay',
            'Alipay 2.0' => 'alipay',
            'WXPay' => 'weixin',
        ];
		$order_param['pay_type'] = $pay_types[$result['targetSys']];
		$order_param['is_mobile'] = 1;
		$order_param['order_type'] = $payment_money_order['order_type'];
		$order_param['order_id'] = $order_no;
		$order_param['is_own'] = 0;
		$order_param['third_id'] = $result['targetOrderId'];
		$order_param['pay_money'] = getFormatNumber($result['totalAmount']/100);
		$order_param['sub_mch_id'] = '';
		return array('error'=>0,'order_param'=>$order_param);
	}

	//退款
	public function refund($order_no, $total_fee, $refund_money, $transaction_no, $sub_mer_no = '', $mid = '', $payment_money_order = []){
		$url = $this->gateway.'/v1/netpay/refund';
		$merOrderId = $this->createOrderNo($order_no);
		$refundOrderId = $this->createRefundNo();
		// $merAmount = $this->get_format_number($refund_money*100);
		// if(isset($payment_money_order['chinaums_merchant_already_get'])){
		// 	$platformAmount = $this->get_format_number($refund_money*100) - $payment_money_order['chinaums_merchant_already_get'];
		// 	$merAmount = $payment_money_order['chinaums_merchant_already_get'];
		// }
		//按照先商家后平台退款
		if (empty($payment_money_order)) {
			$payOrderid = $this->createOrderNo($merOrderId, true);
			$payment_money_order = D('Payment_money_order')->where(['orderid' => $payOrderid])->find();
		}
		if (empty($payment_money_order)) {
			throw new Exception("订单查询失败！没有找到支付信息！");
		}

		if (C('config.open_chinaums_without_asset') == 1 && $payment_money_order['env'] == 'wxMini') {
			return $this->refundWithoutAsset($order_no, $total_fee, $refund_money, $transaction_no, $sub_mer_no, $mid, $payment_money_order);
		}

		$refundTotal = intval($refund_money * 100);
		if ($payment_money_order['chinaums_merchant_already_get'] >= $refundTotal) {
			$merRefundAmount = $refundTotal;
			$platRefundAmount = 0;
		} else {
			$merRefundAmount = $payment_money_order['chinaums_merchant_already_get'];
			$platRefundAmount = $refundTotal - $merRefundAmount;
		}

		//$merRefundAmount = $this->get_format_number(100*$refund_money*($payment_money_order['chinaums_merchant_already_get']/($payment_money_order['chinaums_merchant_already_get']+$payment_money_order['chinaums_platform_already_get'])));
		//$platRefundAmount=$this->get_format_number($refund_money*100-$merRefundAmount);

		$post = [
			'requestTimestamp' => date('Y-m-d H:i:s'),
			'merOrderId' => $merOrderId,
			'mid' => empty($mid) ? $this->config['chinaums_mid'] : $mid,
			'tid' => $this->config['chinaums_tid'],
			'instMid' => 'YUEDANDEFAULT',
			'targetOrderId' => $transaction_no,
			'refundAmount' => $this->get_format_number($refundTotal),
			'refundOrderId' => $refundOrderId,
			'platformAmount' => 0,
		];
		if (!empty($sub_mer_no)) {
			$post['subOrders'] = [
				[
					'totalAmount' => $this->get_format_number($refundTotal),
					'mid' => $sub_mer_no
				]
			];
		}

		if (C('config.chinaums_platform_sub_mchid')) {
			unset($post['platformAmount']);
			$post['subOrders'] = [
				[
					'totalAmount' => $this->get_format_number($merRefundAmount),
					'mid' => $sub_mer_no
				],
				[
					'mid' => C('config.chinaums_platform_sub_mchid'),
					'totalAmount' => $this->get_format_number($platRefundAmount),
				]
			];
		}
		try {
		    fdump(['url'=>$url,'postData'=>$post],"zzzznnn111111111111111111tttttttttt12222");
			$result = $this->postReq($url, $post);
			if ($result['status'] == 'REFUND' || $result['status'] == 'TRADE_SUCCESS') {
				$refund_param['refund_id'] = $result['refundTargetOrderId'];
				$refund_param['refund_time'] = strtotime($result['responseTimestamp']);

				if (C('config.chinaums_platform_sub_mchid') && $sub_mer_no) {
					//有结算记录，当退款的时候才记录退款记录。
					//if (M("Chinaums_fenzhang_record")->where(['pay_orderid' => $payment_money_order['orderid'], 'type' => 'settlement'])->find()) {
						$chinaums_data = [
							'mer_id' => M('Merchant')->where(['chinaums_merno' => $sub_mer_no])->getField('mer_id'),
							'create_time' => time(),
							'merchant_money' => $this->get_format_number($merRefundAmount) * -1,
							'platform_money' => $this->get_format_number($platRefundAmount) * -1,
							'is_withdraw' => 0,
							'plat_is_withdraw' => 0,
							'pay_orderid' => $payment_money_order['orderid'],
							'type' => 'refund'
						];
						M("Chinaums_fenzhang_record")->data($chinaums_data)->add();
					//}
				}
				return array('error' => 0, 'type' => 'ok', 'msg' => '退款申请成功！请注意查收退款通知。', 'refund_param' => $refund_param);
			} else {
				return array('error' => 1, 'type' => 'fail', 'msg' => '状态:' . $result['refundStatus']);
			}
		} catch (\Exception $e) {
			return array('error' => 1, 'type' => 'fail', 'msg' => '状态:' . $e->getMessage());
		}
		
	}

	/**
	 * 分账
	 * @param  [type] $order_no  交易订单号
	 * @param  [type] $merConfig     商家的企业用户号及划付金额,格式如下：
	 *                               [
	 *                               	'企业用户号1' => '划付金额1',
	 *                               	'企业用户号2' => '划付金额2',
	 *                               ]
	 * @return bool            成功或者失败
	 */
	public function launchLedger($order_no, $merConfig){
		if(empty($order_no) || empty($merConfig)){
			throw new Exception("分账指令参数不全!");	
		}
		$order_no = $this->createOrderNo($order_no);
		foreach ($merConfig as $mer_no => $mer_money) {
			$mer_money = getFormatNumber($mer_money * 100);
			//先查询
			$header = $this->getHeader('202007');
			$data = [
	            'merNo' => $mer_no,
	            'transDate' => date("Ymd"),
	            'queryItem' => 'A',
	            'queryValue' => $order_no
	        ];
			$post_data = array_merge($header,$data);
	        $string = $this->getParamsString($post_data);
	        $signature = $this->sign($string);
	        $post_data['signature'] = $signature;        
	        $result = $this->httpRequest($post_data);
	        if($result['settleStatus'] == 'Special' && $result['canPayAmt'] >= $mer_money){
	        	//先划付给商户，再分账给平台
				$header = $this->getHeader('202001');
				$data = [
		            'merNo' => $mer_no,
		            'merOrderNo' => $order_no,
		            'payAmt' => $mer_money
		        ];
				$post_data = array_merge($header,$data);
		        $string = $this->getParamsString($post_data);
		        $signature = $this->sign($string);
		        $post_data['signature'] = $signature;        
		        $result1 = $this->httpRequest($post_data);
		        if($result1['respCode'] == '99999999'){
		        	$plat_money = $result['canPayAmt'] - $mer_money;
		        	if($plat_money > 0){
		        		//再分账给平台
		        		$header = $this->getHeader('202003');
						$data = [
				            'merNo' => $this->config['zg_plat_no'],
				            'merOrderNo' => $order_no,
				            'payAmt' => $plat_money,
				            'cardNo' => hash("sha256", $this->config['zg_plat_card_no']),
				            'ps' => base64_encode('分账收入')
				        ];
						$post_data = array_merge($header,$data);
				        $string = $this->getParamsString($post_data);
				        $signature = $this->sign($string);
				        $post_data['signature'] = $signature;        
				        $result2 = $this->httpRequest($post_data);
				        if($result2['respCode'] == '99999999'){
				        	return true;
				        }
				        else{
				        	throw new Exception("分账时出错!错误码：".$result1['respCode']);	
				        }
		        	}
		        }
		        else{
		        	throw new Exception("划付时出错!错误码：".$result1['respCode']);	
		        }
	        }
	        else{
	        	throw new Exception("不可分账（原因可能是分账金额超出清算金额）!");	
	        }

			
		}
		
	}

	/**
	 * 按金额划付和分账
	 * @param  [type] $merchant_money             给商家的钱 单位：分
	 * @param  [type] $platform_money             给平台的钱 单位：分
	 * @param  [type] $merchant_chinaums_zg_merno 商家资管系统企业用户号
	 * @return [type]                             [description]
	 */
	public function launchLedgerMoney($merchant_money, $platform_money, $merchant_chinaums_zg_merno){
		$header = $this->getHeader('202002');
		$data = [
            'merNo' => $merchant_chinaums_zg_merno,
            'payAmt' => (String)$merchant_money
        ]; 
		$post_data = array_merge($header,$data);
        $string = $this->getParamsString($post_data);
        $signature = $this->sign($string);
        $post_data['signature'] = $signature;        
        $result = $this->httpRequest($post_data);
        fdump_sql([func_get_args(), $post_data, $result], 'launchLedgerMoney');
        if($result['respCode'] == '99999999'){
        	if($platform_money > 0){
	        	$header = $this->getHeader('202004');
				$data = [
		            'merNo' => $merchant_chinaums_zg_merno,
		            'payType' => '0',
		            'cardNo' => hash("sha256", $this->config['zg_plat_card_no']),
					'ps' => base64_encode('分账收入'),
		            'payAmt' => (String)$platform_money
		        ];
				$post_data = array_merge($header,$data);
		        $string = $this->getParamsString($post_data);
		        $signature = $this->sign($string);
		        $post_data['signature'] = $signature;        
		        $result = $this->httpRequest($post_data);
                fdump_sql([$post_data, $result], 'launchLedgerMoney');
		        if($result['respCode'] == '99999999'){
		        	return 2;//分账和划付都做成功了
		        }
		        else{
		        	return 1;//只做了商家的划付，分账没成功
		        }
		    }
		    return 1;//只做了商家的划付
        }
        else{
            fdump_sql([$post_data, $result], 'launchLedgerMoney_error');
        	throw new Exception("划付时出错!错误码：".$result['respCode'].";错误原因：".$result['respMsg']);	
        }

	}

	/**
	 * 对子商户执行划付指令
	 *
	 * @param string $merchant_money  金额，分
	 * @param string $merchant_chinaums_zg_merno  子商户号
	 * @return void
	 * @date: 2023/07/18
	 */
	public function do202002($merchant_money,  $merchant_chinaums_zg_merno)
	{
		$header = $this->getHeader('202002');
		$data = [
			'merNo' => $merchant_chinaums_zg_merno,
			'payAmt' => (string)$merchant_money
		];
		$post_data = array_merge($header, $data);
		$string = $this->getParamsString($post_data);
		$signature = $this->sign($string);
		$post_data['signature'] = $signature;
		$result = $this->httpRequest($post_data);
		fdump_sql([$post_data, $result], 'launchLedgerMoney');
		if ($result['respCode'] == '99999999') {
			return true;
		} else {
			return false;
		}
	}

	protected function getHeader($transCode)
    {
        $header = [
            'transCode' => $transCode,
            'verNo' => '100',
            'srcReqDate' => date("Ymd"),
            'srcReqTime' => date("His"),
            'srcReqId' => $this->createRefundNo(),
            'channelId' => '043',
            'groupId' => $this->config['group_id'],
        ];

        return $header;
    }

    function getParamsString($params)
    {
        ksort($params);
        $paramsToBeSigned = [];
        foreach ($params as $k=>$v) {
            if(is_array($params[$k])){
                $v = json_encode($v,JSON_UNESCAPED_UNICODE);
            }else if(trim($v) == ""){
                continue;
            }
            if(is_bool($v)){
                $paramsToBeSigned[] = $v ? "$k=true" : "$k=false";
            }else {
                $paramsToBeSigned[] = $k . '=' . $v;
            }
        }
        unset ($k, $v);

        $stringToBeSigned = (implode('&', $paramsToBeSigned));

        return $stringToBeSigned;
    }

    function sign($data)
    {
        $filePath = getcwd().$this->config['zg_private_key'];
        if(!file_exists($filePath)) {
            throw new Exception("资管系统私钥不存在!");	
        }

        $password = $this->config['zg_private_key_pwd'];

        $pkcs12 = file_get_contents($filePath);
        openssl_pkcs12_read($pkcs12, $certs, $password);
        if(!$certs){
            return '';
        }

        $privateKey = $certs['pkey']; 
        if (openssl_sign(utf8_encode($data), $binarySignature, $privateKey, OPENSSL_ALGO_SHA256)) {
            return bin2hex($binarySignature);
        } else {
            return '';
        }

    }

    /**
     * 接口请求返回结果
     *
     * @param $data
     * @return mixed
     */
    public function httpRequest($data)
    {
        $url = $this->config['fenzhangURL'].$data['transCode'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        //有post参数-设置
        if (is_array($data) && 0 < count($data)) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data,JSON_UNESCAPED_UNICODE));
        }


        $header[] = "Content-type: application/json";
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);

        $result = curl_exec($ch);

		fdump_sql([$url,$header,$data,$result],'chinaums_fenzhang');


        return json_decode($result,true);
    }

	/**
	 * 新购物车小程序支付
	 *
	 * @param string $order_no
	 * @param float $money  单位：元
	 * @return void
	 * @author: zt
	 * @date: 2024/01/20
	 */
	public function WechatminiWithoutAsset($order_no, $money, $sub_mer_no = '', $village_id = 0)
	{
		$order_id_arr = explode('_', $order_no);
		if ($village_id && !empty($sub_mer_no)) {
			$is_own = 5;
		} else {
			$is_own = 0;
		}
		M("Payment_money_order")->where(['orderid' => $order_id_arr[1]])->data(['chinaums_merchant_already_get' =>$this->merchant_get, 'chinaums_platform_already_get' => $this->platform_get, 'is_own' => $is_own, 'env' => 'wxMini'])->save();

		$url = $this->gateway . '/v1/inip/upsp/shop/mini/pay';
		$merOrderId = $this->createOrderNo($order_no);
		$post = [
			'mchntOrderId' => $merOrderId,
			'vaMchntNo' => empty(C("config.chinaums_mid_wxapp")) ? $this->config['chinaums_mid'] : C("config.chinaums_mid_wxapp"),
			'vaTermNo' => $this->config['chinaums_tid'],
			'msgType' => 'wx.unifiedOrder',
			'sysSource' => 'OPT',
			'tradeType' => 'MINI',
			'totalAmount' => $this->get_format_number($money * 100),
			'instMid' => 'MINIDEFAULT',
			'notifyUrl' => C('config.site_url') . '/source/chinaums_page_notice.php',
			//'subAppId' => 'wxd6cd1b1a2c1a57b9',
			'subAppId' => C('config.pay_wxapp_appid'),
			'subOpenId' => $this->config['openid'],
			'vaFld' => [
				'newShopFlag' => 'GWCXS',
				'isSubMchntPay' => '01',
				'platAmt' => $this->get_format_number($this->platform_get),
				'satType' => '02',
				'satInfo' => [
					[
						'merId' => $sub_mer_no,
						'satAmt' => $this->get_format_number($this->merchant_get),
					]
				]
			]
		];

		$result = $this->postReq($url, $post);
		if ($result['respCode'] !== '0000') {
			throw new Exception($result['respDesc']);
		}
		return [
			'error' => 0,
			'weixin_param' => $result['miniPayRequest']
		];
	}

	/**
	 * 新购物车退款
	 *
	 * @date: 2024/02/01
	 */
	public function refundWithoutAsset($order_no, $total_fee, $refund_money, $transaction_no, $sub_mer_no = '', $mid = '', $payment_money_order = [])
	{
		$url = $this->gateway . '/v1/inip/upsp/shop/mini/refund';
		$merOrderId = $this->createOrderNo($order_no);
		$refundOrderId = $this->createRefundNo();

		if (empty($payment_money_order)) {
			throw new Exception("订单查询失败！没有找到支付信息！");
		}
		$refundTotal = intval($refund_money * 100);
		if ($payment_money_order['chinaums_merchant_already_get'] >= $refundTotal) {
			$merRefundAmount = $refundTotal;
			$platRefundAmount = 0;
		} else {
			$merRefundAmount = $payment_money_order['chinaums_merchant_already_get'];
			$platRefundAmount = $refundTotal - $merRefundAmount;
		}

		$post = [
			'mchntOrderId' => $merOrderId,  //如果订单确认了传递确认订单号，没确认传递原订单号
			'vaMchntNo' => C('config.chinaums_mid_wxapp')?C('config.chinaums_mid_wxapp'):C('config.chinaums_mid'),
			'vaTermNo' => C('config.chinaums_tid'),
			'refundAmount' => $this->get_format_number($refundTotal),
			'refundOrderId' => $refundOrderId,
			'sysSource' => 'OPT',
			'vaFld' => [
				'newShopFlag' => 'GWCXS',
				'isSubMchntPay' => '01',
				'platAmt' => $this->get_format_number($platRefundAmount),
				'satType' => '02',
				'satInfo' => [
					[
						'merId' => $sub_mer_no,
						'refundAmt' => $this->get_format_number($merRefundAmount),
						//'oriMerOrderId' => '20240205154136100024554547'  //订单确认了需要传递子商户分账订单号
					]
				],
				'oriMchntOrderId' => $merOrderId,
			],
		];

		$asyncConfirmRs = $payment_money_order['async_confirm_result'] ? json_decode($payment_money_order['async_confirm_result'], true) : [];
		if ($asyncConfirmRs) {
			$post['mchntOrderId'] = 'FZ' . $post['mchntOrderId'];
			$satDetail =  array_column($asyncConfirmRs['vaFld']['satDetail'], 'merOrderId', 'merId');
			foreach ($post['vaFld']['satInfo'] as &$s) {
				$s['oriMerOrderId'] = $satDetail[$s['merId']] ?? '';
			}
		}

		try {
			$result = $this->postReq($url, $post);
			$refund_param['refund_id'] = $result['refundTargetOrderId'];
			$refund_param['refund_time'] = strtotime($result['responseTimestamp']);
			return array('error' => 0, 'type' => 'ok', 'msg' => '退款申请成功！请注意查收退款通知。', 'refund_param' => $refund_param);
		} catch (\Exception $e) {
			return array('error' => 1, 'type' => 'fail', 'msg' => '状态:' . $e->getMessage());
		}
	}

	/**
	 * 异步分账确认
	 * @param string $order_no  支付发起订单号
	 * @param int $money  支付发起金额，单位：分
	 * @param array $subOrders  异步分账确认子订单信息
	 * @return void
	 * @author: zt
	 * @date: 2024/01/05
	 */
	public function fenzhangConfirm($order_no, $money, $subOrders = [])
	{
		if (empty($order_no)) {
			throw new Exception("订单ID必填！");
		}
		$url = $this->gateway . '/v1/inip/upsp/shop/async-confirm';
		$merOrderId = $this->createOrderNo($order_no);

		$subOrderAmt = array_sum(array_column($subOrders, 'satAmt'));
		$post = [
			'mchntOrderId' => 'FZ' . $merOrderId,
			'vaMchntNo' => C('config.chinaums_mid_wxapp')?C('config.chinaums_mid_wxapp'):C('config.chinaums_mid'),
			'vaTermNo' =>  C("config.chinaums_tid"),
			'sysSource' => 'OPT',
			'transAmt' => $this->get_format_number($money),
			'vaFld' => [
				'isSubMchntPay' => '01',
				'platAmt' => $this->get_format_number($money - $subOrderAmt),
				'oriMchntOrderId' => $merOrderId,
				'satInfo' => $subOrders
			]
		];
		$result = $this->postReq($url, $post);
		if ($result['respCode'] !== '0000') {
			throw new Exception($result['respDesc']);
		}
		return $result;
	}

    /**
     * 查询银联商户可提现金额
     * @throws Exception
     */
    public function queryWithdrawBalance($chinaumsMerNo) 
    {
        $url = $this->gateway.'/v1/tn/cooperator/query-withdraw-balance';
        //银联云闪付通道
        $post = [
            'sysId' => $this->config['sysId'],
            'mchntNo' => $chinaumsMerNo,
            'reqTs' => date("YmdHis"),
            'appType' => '74',
        ];
        $result = $this->postReq($url, $post);
        $money = 0;
        if($result['respCode']=='000000'){
            $money = $result['tzWithdrawAmt']; //TZ可提现金额
            $money = $money<0?0:$money;
        }else{
            throw_exception($result['respDesc']);
        }
        
        return $money;
    }

    /**
     * 提现
     * @return mixed
     * @throws Exception
     */
    public function withdraw($params)
    {
        $url = $this->gateway.'/v1/tn/cooperator/process-withdraw';
        $post = [
            'sysId' => $this->config['sysId'],//系统编号
            'mchntNo' => $params['chinaumsMerNo'],//子商户代付编号
            'sysOrderId' => $params['sysOrderId'],//系统订单号
            'notifyUrl' => '',//回调地址（走计划任务定时查询更新）
            'reqTs' => date("YmdHis"),//请求时间戳
            'withdrawType' => $params['withdrawType']??'1',//提现类型 //0 - D0,1 - D1（对公对私是TN内部维护）
            'withdrawAmt' => (string)($params['money']*100),//提现金额(单位：分)
            'appType' => '74',//提现类型
            'feeRatio' => $params['feeRatio']??'0.001',//提现手续费扣率
            'remark' => $params['remark'],//提现备注
            'subsFlag' => '0',//补贴标记
            'minFee' => '0',//保底手续费
            'd0SubsFlag' => '0', //垫资手续费补贴标记(0:不补贴 1:补贴（传1时，如果有垫支手续费则由机构承担，否则由商户承担）)
        ];
        $result = $this->postReq($url, $post);
        if($result['respCode']!='000000'){
            throw_exception($result['respDesc']);
        }
        return $result;
    }

    /**
     * 查询提现状态
     * @return mixed
     * @throws Exception
     */
    public function queryWithdrawStatus($params)
    {
        $url = $this->gateway.'/v1/tn/cooperator/query-order-status';
        $post = [
            'sysId' => $this->config['sysId'],//系统编号
            'mchntNo' => $params['chinaumsMerNo'],//商户编号
            'transDate' => $params['transDate'],//交易日期（格式yyyyMMDD）
            'sysOrderId' => $params['real_orderid'],//系统订单号
            'reqTs' => date("YmdHis"),//请求时间戳
        ];
        $result = $this->postReq($url, $post);
        return $result;
    }
}
?>