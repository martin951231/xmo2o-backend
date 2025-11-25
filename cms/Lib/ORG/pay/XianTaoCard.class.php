<?php
/**
 * 智慧仙桃电子卡支付需求对接
 * author hengtingmei
 * 【【定制-4月29日交付】智慧仙桃对接支付】https://www.tapd.cn/47145533/prong/stories/view/1147145533001007470
 * 2021-07-26
 */

class XianTaoCard{
	public $gateway;
	public $config;
	public $payTypeArr;
	public $statusArr;
	public function __construct(){
		$this->gateway = C('config.pay_xiantao_card_domain');
		$this->payTypeArr = [
			'xiantao_card' => 1,// 仙桃电子卡
			'xiantao_debit_card' => 8,// 智慧仙桃储值卡
			'xiantao_abc_card' => 0,// 农行支付
			'xiantao_union_card' => 5,// 银联支付
		];

		$this->statusArr = [
			0 => '未支付',
			1 => '已支付',
			2 => '已取消',
			3 => '已过期',
			4 => '退款中',
			5 => '已退款',
			6 => '退款失败',
			7 => '订单关闭',
		];
	}

	public function getUniqueKey($phone){
		return md5($phone);
	}

	/*
	  * 账户检查
	*/
	public function checkUser($phone){
		// $phone = 13995594671;
		if(empty($phone)){
			throw new Exception("未绑定手机号");	
		}
		if(empty($this->gateway)){
			throw new Exception("请设置接口域名");	
		}

		$url = $this->gateway.'/rrb-open-app-zhxt/user/checkOtherAccount?phone='.$phone.'&uniqueKey='.$this->getUniqueKey($phone);
		
		fdump($url,'1XianTaoCard_checkUser',1);
		$result = $this->httpGet($url);
		fdump($result,'1XianTaoCard_checkUser',1);
	
		if($result['errcode'] != 0){
			throw new Exception("未开通智慧仙桃储值卡，请自行前往App开通", $result['errcode']);
		}
		return  $result['data'];
		
	}

	/*
	  * 账户授权
	*/
	public function toAuthorization($phone, $password){
		// $phone = 13995594671;
		// $password = 123456;
		if(empty($phone) || empty($password)){
			throw new Exception("手机号或者密码未输入");	
		}

		if(empty($this->gateway)){
			throw new Exception("请设置接口域名");	
		}
		// 去授权
		$url = $this->gateway.'/rrb-open-app-zhxt/user/otherAccountAuth';

		// 请求参数
		$post = [
			'regPhone' => $phone,
			'password' => $password,
			'uniqueKey' => $this->getUniqueKey($phone)
			
		];
		
		$result = $this->postReq($url, $post);

		if($result['errcode'] != 0){
			throw new Exception($result['errtext'],  $result['errcode']);
		}

		return  $result['data'];
	}

	/*
	  * 支付检查
	*/
	public function checkPay($money, $payType, $param = []){
		if(empty($money)){
			throw new Exception("支付金额必填！");	
		}
		if(empty($this->gateway)){
			throw new Exception("请设置接口域名");	
		}

		$url = $this->gateway.'/rrb-open-app-zhxt/pay/wallet/payPreCheck';
		// 请求头
		$header = ['AccessToken:'.$param['accessToken']];

		// 请求参数
		$post = [
			'orderAmount' => $money,
			'payType' => $this->payTypeArr[$payType],//（0-电子卡（农行），1-电子钱包，2-工行卡，3-电子卡（支付宝），4-电子卡（微信），5-银联，6-微信小程序，7-储值卡（短信），8-储值卡（密码）
			
		];
		
		$result = $this->postReq($url, $post, $header);


		if( $result['errcode']== 2217){
			$result['errtext'] = '未设置智慧仙桃储值卡密码，请自行前往App设置';
		}elseif( $result['errcode']== 2203){
			$result['errtext'] = '智慧仙桃App电子钱包未开户，扣款失败，请点击下载智慧仙桃App进行电子钱包开户！';
		}elseif( $result['errcode']== 2233){
			$result['errtext'] = '智慧仙桃App储值卡余额不足，扣款失败，请点击下载智慧仙桃App进行储值卡充值！';
		}

		if($result['errcode'] != 0){
			fdump_sql([$header,$param,$post,$result],'XianTaoCard_checkPay');
			throw new Exception($result['errtext'],  $result['errcode']);
		}

		$return['error'] = 0;
		if($result['data']['largePayThreshold'] < $money){
			throw new Exception('支付金额大于支付门槛');
		}

		return $return;
	}
	
	/*
	  * 支付
	*/
	public function pay($order_no, $money, $password, $payType, $param = []){
		if(empty($order_no) || empty($money)){
			throw new Exception("订单ID或支付金额必填！");	
		}
		if(empty($this->gateway)){
			throw new Exception("请设置接口域名");	
		}

		$url = $this->gateway.'/rrb-open-app-zhxt/pay/payment/recharge';
		$post = [
			'usageType' => 1,
			'payType' => $this->payTypeArr[$payType],
			'payItem' => 21,
			'orderAmount' => $money,
			'extraInfo' => json_encode(['order_no'=>$order_no]),			
		];

		if($password){
			$post['payPassword'] = $password;
		}
		
		// 请求头
		$header = ['AccessToken:'.$param['accessToken']];

		$result = $this->postReq($url, $post, $header);

        if( $result['errcode']== 2233){
            $result['errtext'] = '智慧仙桃App储值卡余额不足，扣款失败，请点击下载智慧仙桃App进行储值卡充值！';
        }
        
		if($result['errcode'] != 0){
			fdump_sql([$param,$post,$result],'XianTaoCard_pay_error');
			throw new Exception($result['errtext'],  $result['errcode']);
		}

		return [
			'error' => 0,
			'data' => $result['data']

		];
	}

	
	/*
	  * 支付详情
	*/
	public function payInfo($order_no, $phone){
		if(empty($order_no)){
			throw new Exception("订单ID必填！");	
		}

		if(empty($phone)){
			throw new Exception("手机号必填！");	
		}
		if(empty($this->gateway)){
			throw new Exception("请设置接口域名");	
		}

		// 用户检查
		$param = $this->checkUser($phone);

		// 详情接口
		$url = $this->gateway.'/rrb-open-app-zhxt/order/getOrderInfo?billId='.$order_no;
		
		// 请求头
		$header = ['AccessToken:'.$param['accessToken']];

		// 结果
		$result = $this->httpGet($url, $header);

		if($result['errcode'] != 0){
			fdump_sql([$url,$result],'XianTaoCard_payInfo');
			throw new Exception($result['errtext'],  $result['errcode']);
		}

		if($result['data']['status'] != 1){
			throw new Exception('订单'.$this->statusArr[$result['data']['status']]);
		}

		return [
			'error' => 0,
			'order_param' => [
				'pay_money' => $result['data']['actualAmount'],
				'is_mobile' => 1,
				'third_id' => $result['data']['id']
			]
		];
	}
	
	/*
	  * 退款
	*/
	public function refund($order_no, $phone){
		if(empty($this->gateway)){
			throw new Exception("请设置接口域名");	
		}
		if(empty($order_no)){
			throw new Exception("订单ID必填！");	
		}

		if(empty($phone)){
			throw new Exception("手机号必填！");	
		}

		// 用户检查
		$param = $this->checkUser($phone);

		// 详情接口
		$url = $this->gateway.'/rrb-open-app-zhxt/refund/pyt/orderRefund';
		
		// 请求头
		$header = ['AccessToken:'.$param['accessToken']];

		// 请求参数
		$post = [
			'orderNo' => $order_no,// 订单id
			'isNotify' => 0,	// 是否已通知用户（传0）	
			'accessory' => '',	// 退款图片附件URL
			'areaId' => '429004',// 区域id（固定传429004）
		];

		// 结果
		$result = $this->postReq($url, $post, $header);
		if($result['errcode'] != 0){
			fdump_sql([$post,$result],'XianTaoCard_refund');
			throw new Exception($result['errtext'],  $result['errcode']);
		}

		return [
			'error' => 0,
			'type' => 'ok'
		];
	}

    /**
     * get请求
     *
     * @param $url
     * @param $header
     * @return array
     */
    public function httpGet($url, $header = [])
    {
        if(empty($url)){
			throw new Exception("调用异常!");	
		}
		

		$curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,1); 
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); 
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
       
        if($header){
        	curl_setopt ($curl, CURLOPT_HTTPHEADER, $header);
        }
       
        //请求时间
        $timeout = 30;
        curl_setopt ($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
       
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");

        $data = curl_exec($curl);
        curl_close($curl);//关闭cURL会话
        if(empty($data)){
        	throw new Exception("调用接口发生异常!");
        }
    	$return = json_decode($data,true);
    	return $return;
    }


	//post请求
	public function postReq($url, $post_data, $headerArr=[]){
		if(empty($url) || empty($post_data)){
			throw new Exception("调用异常!");	
		}
		$json_post_data = json_encode($post_data);


		$curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER,1); 
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE); 
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);

        // 请求头
        $header = ['Content-type: application/json'];
        if($headerArr){
        	$header = array_merge($header, $headerArr);
        }

        curl_setopt ( $curl, CURLOPT_HTTPHEADER, $header );
        
        //请求时间
        $timeout = 30;
        curl_setopt ($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
        if(is_array($post_data)){
            $params = json_encode($post_data);
        }
       
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS,$params);
        $data = curl_exec($curl);
        curl_close($curl);//关闭cURL会话
        if(empty($data)){
        	throw new Exception("调用银联接口发生异常!");
        }
    	$return = json_decode($data,true);
    	return $return;
	}
}
?>