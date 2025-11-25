<?php
/**
 * 【定制--【4.12号交付】养老金模块与杉德支付对接】 https://www.tapd.cn/47145533/prong/stories/view/1147145533001007312
 */
class Sande{
	private $sdk;
	public function __construct(){
		import('@.ORG.pay.sand.SandePay');
		$this->sdk = new SandePay();
		$this->sdk->sellerMid = C("config.sande_merno");
		$this->sdk->platformFz = C("config.sande_platform_fz");
		$this->sdk->oldAgeFz = C("config.sande_old_age_fz");
		$this->sdk->publicKeyPath = $_SERVER['DOCUMENT_ROOT'].C("config.sande_cer");
		$this->sdk->privateKeyPath = $_SERVER['DOCUMENT_ROOT'].C("config.sande_pfx");
		$this->sdk->privateKeyPwd = C("config.sande_pfx_pwd");
	}

	public function pay($order){
		$appid = "";
		if($order['pay_type'] == 'wxapp'){
			$this->sdk->productId = '00002021';
			$appid = C("config.pay_wxapp_appid");
		}
        if($order['pay_type'] == 'alipay'){
            $this->sdk->productId = '00002022';
        }
		if($order['pay_type'] == 'wechat'){
			$this->sdk->productId = '00002020';	
			$appid = C("config.pay_weixin_appid");
		}

		$order_id_arr = explode('_',$order['orderNo']);
		
		M('Payment_money_order')->where(array('orderid'=>$order_id_arr[1],'order_type'=>$order_id_arr[0]))->data(array('pay_method'=>'sande','orderNo'=>$order['orderNo']))->save();

		// 参数
        $this->sdk->body = array(
            'orderCode'   => $order['orderNo'],
            'totalAmount' => $this->createPrice($order['payment_money']),
            'subject'     => $order['order_name'],
            'body'        => $order['order_name'],
            'payMode'     => 'sand_wx',
            'payExtra'    => array(
                'subAppid' => $appid,
                'userId'   => $order['openid'],
            ),
            'clientIp'    => get_client_ip(),
            'notifyUrl'   => C('config.site_url').'/source/sande_notice.php',
            // 'frontUrl'    => 'http://192.168.22.171/sandpay-qr-phpdemo.bak',
            'accsplitInfo' => array(
            	'accsplitMode' => '3'
            ),
        );
        if($order['pay_type'] == 'alipay'){
            $this->sdk->body['payMode'] = "sand_alipay";
            $this->sdk->body['payExtra'] = [
                "userId" => $order['alipay_uid']
            ];
        }
        $ret = $this->sdk->request('orderPay');
        if(isset($ret['data'])){
        	$result = json_decode($ret['data'], true);
        	if($result['head']['respCode'] == '000000' && isset($result['body']['credential'])){
        		$credential = json_decode($result['body']['credential'], true);
        		$params = json_decode($credential['params'], true);
        		
                if($order['pay_type'] == 'alipay'){
                    $returnArr = array(
                        'error' => 0,
                        'tradeNO' => substr($params['payInfo']['trade_no'], 2, 30),
                    );
                }
                else{
                    $returnArr = array(
                        'error' => 0,
                        'weixin_param' => $params,
                    );
                }
        	}
        	else{
        		$returnArr = array(
					'error'=>1,
					'msg'=>'第三方返回：'.$result['head']['respMsg'],
				);
        	}
        }
        else{
        	$returnArr = array(
				'error'=>1,
				'msg'=>'调用第三方接口出错',
			);
        }
        return $returnArr;
	}

	private function createPrice($price){
		$price = getFormatNumber($price*100);
		$len = strlen($price);
		$zero = "";
		for($i = 0;$i<12-$len;$i++){
			$zero .= "0";
		}
		return $zero.$price;//这个支付对接放有点变态，非要传递一个12位的字符串，如果价格不够12位，则以零补全
	}

	public function refund($order){

		$refund_money = $order['payment_refund_money'] ? strval($order['payment_refund_money']) : strval($order['payment_money']);
		$this->sdk->body = array(
            'orderCode'          => substr($order['order_type'].'_'.$order['orderid'].'_'.$_SERVER['REQUEST_TIME'],0,32), //新的订单号
            'oriOrderCode'       => $order['orderNo'], //原订单号
            'refundAmount'       => $this->createPrice($refund_money), //退款金额
            'refundMarketAmount' => $this->createPrice($refund_money), //退营销金额
            'notifyUrl'          => C('config.site_url').'/source/sande_notice_refund.php',
            'refundReason'       => 'userrefund',
            'extend'             => ''
        );
        $ret = $this->sdk->request('orderRefund');
        if(isset($ret['data'])){
        	$result = json_decode($ret['data'], true);
        	if($result['head']['respCode'] == '000000'){
        		$returnArr = array(
					'error' => 0,
					'type' => 'ok',
					'refund_money'   => $refund_money,
					'refund_param'   => serialize($ret['data']),
				);
        	}
        	else{
        		$returnArr = array(
					'error'=>1,
					'msg'=>'第三方返回：'.$result['head']['respMsg'],
				);
        	}
        }
        else{
        	$returnArr = array(
				'error'=>1,
				'msg'=>'调用第三方接口出错',
			);
        }
        return $returnArr;
	}

	/**
	 * 下达分账指令
	 * @param  [type] $order_no              分账订单
	 * @param  [type] $platform_per          平台应得抽成比例
	 * @param  [type] $merchant              入驻商户的分账方编号
	 * @param  [type] $merchant_per          入驻商户应得的分账比例
	 * @param  [type] $old_age_statement_per 公证处应得抽成比例
	 * @return [type]                        [description]
	 */
	public function executeAccount($order_no, $platform_per, $merchant, $merchant_per, $old_age_statement_per, $uid, $payment_data){
        $order_id_arr = explode('_', $order_no);
        $wait_money = floor($payment_data['paid_money']*100*0.997);
        $platform_money = floor($wait_money*$platform_per/100);
        $user_money = floor($wait_money*$old_age_statement_per/100);
        $merchant_money = $wait_money - $platform_money - $user_money;
        fdump("fenzhang...start", "lumin04272", 1);
        // 参数
        $this->sdk->body = array(
            'orderCode'   => $order_id_arr[1].rand(10000,99999),
            'oriOrderCode' => $order_no,
            'accsplitInfo' => [
                'accsplitMode' => '0',
                'accsplitRules' => [
                    'accsplitRule' => '0',
                    'accsplitTotalAmt' => $this->createPrice($wait_money/100),
                    'accsplitRuleMerchantList' => [
                        [
                            'accsplitMerNo' => $this->sdk->platformFz,
                            'fixedAmt' => '000000000000',
                            'accsplitAmt' => $this->createPrice($platform_money/100),
                        ],
                        [
                            'accsplitMerNo' => $merchant,
                            'fixedAmt' => '000000000000',
                            'accsplitAmt' => $this->createPrice($merchant_money/100),
                        ],
                        [
                            'accsplitMerNo' => $this->sdk->oldAgeFz,
                            'fixedAmt' => '000000000000',
                            'accsplitAmt' => $this->createPrice($user_money/100),
                        ],
                    ]
                ]
            ]
        );
        $ret = $this->sdk->request('multipartCreate');
        fdump_sql([$this->sdk->body, $ret], 'Sande_executeAccount');
        if(isset($ret['data'])){
        	$result = json_decode($ret['data'], true);
        	if($result['head']['respCode'] == '000000'){
        		//todo  对接杉德机构同步分账信息
                $money = getFormatNumber($user_money);//得到单位：分 的金额
                $record = [
                    'uid' => $uid,
                    'time' => time(),
                    'note' => '在店铺消费'.C("config.Currency_symbol").$payment_data['paid_money'].'，获得养老金',
                    'money' => $money,
                    'transaction_number' => '',
                    'deposit_no' => '',
                    'status' => 0
                ];
                M("Old_age_pension_records")->data($record)->add();
                //这里把养老金记录到临时表，然后由计划任务统一执行给公证处
                //但目前公证处那边是人工审核，他们不能够一条订单一条订单的审核
                //而客户那边又不同意合并提交（原因：合并提交会延迟养老金的到账）
                //客户跟养老金商量未果，等待他们通知，我们这边再来开发计划任务代码

        		return true;
        	}
        	else{
        		return false;
        	}
        }
        else{
        	return false;
        }
        return false;
	}


}