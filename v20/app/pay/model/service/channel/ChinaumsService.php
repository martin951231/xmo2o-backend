<?php
/**
 * 
 * 银联分账
 */

namespace app\pay\model\service\channel;
use app\merchant\model\service\MerchantService;
use app\community\model\service\PayService as communityPayService;
use app\pay\model\service\PayService;
use Exception;

class ChinaumsService{
	private $agent = '';
	private $config = [];//配置参数

	public function __construct($agent = '', $config = []){
		$this->agent = $agent;
		$this->config = $config;
	}

	public function pay($order_no, $money){
		if(empty($order_no) || empty($money)){
			throw new \think\Exception("订单ID或支付金额必填！");	
		}

		//不接资管平台只支持微信小程序支付
		if ($this->config['without_asset'] == 1 && $this->agent == 'wechat_mini') {
			return $this->WechatminiWithoutAsset($order_no, $money);
		}


		switch ($this->agent) {
			case 'wechat_h5'://微信端
			case 'h5':
				if($this->config['pay_type'] == 'wechat'){
					return $this->Wechatpay($order_no, $money);
				}
				break;
			case 'wechat_mini':
				return $this->Wechatmini($order_no, $money);
				break;
			case 'iosapp':
			case 'androidapp':
				if($this->config['pay_type'] == 'wechat'){
					return $this->Wechatapp($order_no, $money);
				}
				if($this->config['pay_type'] == 'alipay'){
					return $this->Alipayapp($order_no, $money);
				}
				break;
			default:
				throw new \think\Exception("当前端口未对接，不能使用");
				break;
		}
	}

	/**
	 * 获取当前通道支持的（已对接）支付方式
	 * @return [type] [description]
	 */
	public function getPayTypes($return = [], $pay_types = []){
		if(cfg('open_chinaums') != '1') return $return;

		$temp = [
			'wechat' => [
				'name' => $pay_types['wechat']['code'],
				'text' => $pay_types['wechat']['text'],
				'icon' => $pay_types['wechat']['icon'],
				'discount_tips' => '',
				'service_charge' => '',
				'rate' => ''
			],
			'alipay' => [
				'name' => $pay_types['alipay']['code'],
				'text' => $pay_types['alipay']['text'],
				'icon' => $pay_types['alipay']['icon'],
				'discount_tips' => '',
				'service_charge' => '',
				'rate' => ''
			]
		];
		if(request()->agent == 'wechat_mini' || request()->agent == 'wechat_h5'){//先屏蔽
			unset($temp['alipay']);
		}
		if(request()->agent == 'alipay' || request()->agent == 'alipay_mini'){//先屏蔽
			unset($temp['wechat']);
		}
		if(request()->agent == 'h5'){//先屏蔽
			unset($temp['wechat']);
		}
		return array_merge($return, $temp);
	}

	/**
	 * 获取当前通道的支付参数
	 */
	public function getParams($mer_id = 0,$village_id=0){
		//if(cfg('open_chinaums') != '1') return false;
        $sub_mer_no = '';
        $is_own = 0;
        if($mer_id){
            $now_merchant = (new MerchantService)->getMerchantByMerId($mer_id);
            $sub_mer_no = $now_merchant['chinaums_merno'] ?? '';
            if($sub_mer_no){
                $is_own = 1;
            }
        }elseif ($village_id){
            $community = (new communityPayService)->getConfig($village_id);
            $sub_mer_no = $community['mid'] ?? '';
            if($sub_mer_no){
                $is_own = 5;
            }
        }
		//如果商家没配置子商户则不走银联
		if(empty($sub_mer_no)){
			return [];
		}

		return [
			'channel' => 'chinaums',
			'is_own' => $is_own,
			'config' => [//目前先写死（然后对本脚本加密）
				'chinaums_mid' => cfg('chinaums_mid'),
				'chinaums_appid' => cfg('chinaums_appid'),
				'chinaums_appkey' => cfg('chinaums_appkey'),
				'chinaums_tid' => cfg('chinaums_tid'),
				'chinaums_msgsrcid' => cfg('chinaums_msgsrcid'),
				'chinaums_md5key' => cfg('chinaums_md5key'),
				'gateway' => 'https://api-mop.chinaums.com',
				'chinaums_mid_wx' => cfg('chinaums_mid_wx'),
				'chinaums_mid_wxapp' => cfg('chinaums_mid_wxapp'),
				'chinaums_mid_app' => cfg('chinaums_mid_app'),
				'sub_mer_no' => $sub_mer_no,
				'without_asset'=>cfg('open_chinaums_without_asset'),
			]
		];
	}

	//生成银联账单编号（因为他们有特别的要求，必须用他们的）
	public function createOrderNo($order_no, $ret = false){
		if(!$ret){
			$ori_order_no = $this->config['chinaums_msgsrcid'].$order_no;
		}
		else{
			$ori_order_no = str_replace($this->config['chinaums_msgsrcid'], '', $order_no);
		}
		return $ori_order_no;
	}

	//公众号微信支付
	public function Wechatpay($order_no, $money){
		if(empty($order_no) || empty($money)){
			throw new \think\Exception("订单ID或支付金额必填！");	
		}
		if(empty($this->config['openid'])){
			throw new \think\Exception("未获取到openid，无法调起支付");
		}

		$url = $this->config['gateway'].'/v1/netpay/wx/unified-order';
		$merOrderId = $this->createOrderNo($order_no);
		$post = [
			'requestTimestamp' => date('Y-m-d H:i:s'),
			'merOrderId' => $merOrderId,
			'mid' => $this->config['chinaums_mid_wx'] ? $this->config['chinaums_mid_wx']:$this->config['chinaums_mid'] ,
			'tid' => $this->config['chinaums_tid'],
			'instMid' => 'YUEDANDEFAULT',
			'totalAmount' => get_format_number($money * 100),
			'notifyUrl' => (String) url('chinaums_notify', [], false, true),
			'subOpenId' => $this->config['openid'],
			'tradeType' => 'JSAPI',			
		];
		if(!empty($this->config['sub_mer_no'])){
			if(cfg('chinaums_platform_sub_mchid')){
				$post['divisionFlag'] = true;
				$post['platformAmount'] = 0;
				$post['subOrders'] = [
					[
						'mid' => $this->config['sub_mer_no'],
						'totalAmount' => $this->config['merchant_get'],
					],
					[
						'mid' => cfg('chinaums_platform_sub_mchid'),
						'totalAmount' => $this->config['platform_get'],
					],
				];
			}else{
				$post['divisionFlag'] = true;
				$post['platformAmount'] = cfg('is_1238888_system') ? $this->config['platform_get'] : 0;
				$post['subOrders'] = [
					[
						'mid' => $this->config['sub_mer_no'],
						'totalAmount' => cfg('is_1238888_system') ? $this->config['merchant_get'] : get_format_number($money * 100),
					]
				];
			}
		}
		$result = $this->postReq($url, $post);
		if($result['errCode'] != 'SUCCESS'){
			throw new \think\Exception($result['errMsg']);	
		}
		return [
				'type' => 'jssdk',
				'merchant_get' => $this->config['merchant_get'],
				'platform_get' => $this->config['platform_get'],
				'info' => $result['jsPayRequest']
			];
	}

	//小程序微信支付
	public function Wechatmini($order_no, $money){
		if(empty($order_no) || empty($money)){
			throw new \think\Exception("订单ID或支付金额必填！");	
		}
		if(empty($this->config['wxapp_openid'])){
			throw new \think\Exception("未获取到openid，无法调起支付");
		}
		$url = $this->config['gateway'].'/v1/netpay/wx/unified-order';
		$merOrderId = $this->createOrderNo($order_no);
		$post = [
			'requestTimestamp' => date('Y-m-d H:i:s'),
			'merOrderId' => $merOrderId,
			'mid' => $this->config['chinaums_mid_wxapp'] ? $this->config['chinaums_mid_wxapp']:$this->config['chinaums_mid'] ,
			'tid' => $this->config['chinaums_tid'],
			'instMid' => 'MINIDEFAULT',
			'subAppId' => cfg("pay_wxapp_appid"),
			'totalAmount' => get_format_number($money * 100),
			'notifyUrl' => (String) url('chinaums_notify', [], false, true),
			'subOpenId' => $this->config['wxapp_openid'],
			'tradeType' => 'MINI',			
		];

		if(!empty($this->config['sub_mer_no'])){
			if(cfg('chinaums_platform_sub_mchid')){
				$post['divisionFlag'] = true;
				$post['platformAmount'] = 0;
				$post['subOrders'] = [
					[
						'mid' => $this->config['sub_mer_no'],
						'totalAmount' => $this->config['merchant_get'],
					],
					[
						'mid' => cfg('chinaums_platform_sub_mchid'),
						'totalAmount' => $this->config['platform_get'],
					],
				];
			}else{
				$post['divisionFlag'] = true;
				$post['platformAmount'] = cfg('is_1238888_system') ? $this->config['platform_get'] : 0;
				$post['subOrders'] = [
					[
						'mid' => $this->config['sub_mer_no'],
						'totalAmount' => cfg('is_1238888_system') ? $this->config['merchant_get'] : get_format_number($money * 100),
					]
				];
			}
		}
		
		$result = $this->postReq($url, $post);
		if($result['errCode'] != 'SUCCESS'){
			throw new \think\Exception($result['errMsg']);	
		}
		return [
			'type' => 'jssdk',
            'merchant_get' => $this->config['merchant_get'],
			'platform_get' => $this->config['platform_get'],
			'info' => json_encode($result['miniPayRequest']),
		];
	}

	//APP微信支付
	public function Wechatapp($order_no, $money){
		if(empty($order_no) || empty($money)){
			throw new \think\Exception("订单ID或支付金额必填！");	
		}
		$url = $this->config['gateway'].'/v1/netpay/wx/app-pre-order';
		$merOrderId = $this->createOrderNo($order_no);
		$post = [
			'requestTimestamp' => date('Y-m-d H:i:s'),
			'merOrderId' => $merOrderId,
			'mid' => $this->config['chinaums_mid_app'] ? $this->config['chinaums_mid_app']:$this->config['chinaums_mid'],
			'tid' => $this->config['chinaums_tid'],
			'subAppId' => cfg("pay_weixinapp_appid"),
			'instMid' => 'APPDEFAULT',
			'totalAmount' => get_format_number($money * 100),
			'notifyUrl' => (String) url('chinaums_notify', [], false, true),
			'tradeType' => 'APP',//微信必传APP			
		];
		if(!empty($this->config['sub_mer_no'])){
			if(cfg('chinaums_platform_sub_mchid')){
				$post['divisionFlag'] = true;
				$post['platformAmount'] = 0;
				$post['subOrders'] = [
					[
						'mid' => $this->config['sub_mer_no'],
						'totalAmount' => $this->config['merchant_get'],
					],
					[
						'mid' => cfg('chinaums_platform_sub_mchid'),
						'totalAmount' => $this->config['platform_get'],
					],
				];
			}else{
				$post['divisionFlag'] = true;
				$post['platformAmount'] = cfg('is_1238888_system') ? $this->config['platform_get'] : 0;
				$post['subOrders'] = [
					[
						'mid' => $this->config['sub_mer_no'],
						'totalAmount' => cfg('is_1238888_system') ? $this->config['merchant_get'] : get_format_number($money * 100),
					]
				];
			}
		}
		$result = $this->postReq($url, $post);
		if($result['errCode'] != 'SUCCESS'){
			throw new \think\Exception($result['errMsg']);	
		}
		return [
			'type' => 'sdk',
            'merchant_get' => $this->config['merchant_get'],
			'platform_get' => $this->config['platform_get'],
			'info' => $result['appPayRequest']
		];
	}

	//APP支付宝支付
	public function Alipayapp($order_no, $money){
		if(empty($order_no) || empty($money)){
			throw new \think\Exception("订单ID或支付金额必填！");	
		}
		$url = $this->config['gateway'].'/v1/netpay/trade/app-pre-order';
		$merOrderId = $this->createOrderNo($order_no);
		$post = [
			'requestTimestamp' => date('Y-m-d H:i:s'),
			'merOrderId' => $merOrderId,
			'mid' => $this->config['chinaums_mid_app'] ? $this->config['chinaums_mid_app']:$this->config['chinaums_mid'] ,
			'tid' => $this->config['chinaums_tid'],
			'instMid' => 'APPDEFAULT',
			'totalAmount' => get_format_number($money * 100),	
			'notifyUrl' => (String) url('chinaums_notify', [], false, true),	
		];
		if(!empty($this->config['sub_mer_no'])){
			if(cfg('chinaums_platform_sub_mchid')){
				$post['divisionFlag'] = true;
				$post['platformAmount'] = 0;
				$post['subOrders'] = [
					[
						'mid' => $this->config['sub_mer_no'],
						'totalAmount' => $this->config['merchant_get'],
					],
					[
						'mid' => cfg('chinaums_platform_sub_mchid'),
						'totalAmount' => $this->config['platform_get'],
					],
				];
			}else{
				$post['divisionFlag'] = true;
				$post['platformAmount'] = cfg('is_1238888_system') ? $this->config['platform_get'] : 0;
				$post['subOrders'] = [
					[
						'mid' => $this->config['sub_mer_no'],
						'totalAmount' => cfg('is_1238888_system') ? $this->config['merchant_get'] : get_format_number($money * 100),
					]
				];
			}
		}
		$result = $this->postReq($url, $post);
		if($result['errCode'] != 'SUCCESS'){
			throw new \think\Exception($result['errMsg']);	
		}
		return [
			'type' => 'sdk',
            'merchant_get' => $this->config['merchant_get'],
			'platform_get' => $this->config['platform_get'],
			'info' => $result['appPayRequest']
		];
	}

	//post请求
	public function postReq($url, $post_data){
		if(empty($url) || empty($post_data)){
			throw new \think\Exception("调用异常!");	
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
        // exit;
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
        	throw new \think\Exception("调用银联接口发生异常!");
        }
    	$return = json_decode($data,true);
        if(isset($return['respCode']) && $return['respCode'] == 'RISK_CHECK_ERROR'){
            throw_exception($return['respDesc']);
        }
		if(isset($return['errCode'])){
			if($return['errCode'] == 'SUCCESS'){
				return $return;
			}
			else{
				throw new \think\Exception(($return['errMsg'] ?? '').($return['errInfo'] ?? ''));
			}
		}else{
			return $return;
		}
    	
	}

	/**
	 * 异步通知
	 * @return [type] [description]
	 */
	public function notice(){
		return [
			'paid_money' => $_POST["totalAmount"],
			'paid_time' => time(),
			'transaction_no' => $_POST['targetOrderId']
		];
	}

	//查询
	public function query($order_no){
		if ($this->config['without_asset'] == 1 && $this->agent == 'wechat_mini') {
			return $this->queryWithoutAsset($order_no);
		}

		if(empty($order_no)){
			throw new \think\Exception("订单ID或支付金额必填！");	
		}
		$url = $this->config['gateway'].'/v1/netpay/query';
		$merOrderId = $this->createOrderNo($order_no);
		$mid = '';
		switch ($this->agent) {
			case 'wechat_h5'://微信端
			case 'h5':
				if($this->config['pay_type'] == 'wechat'){
					$mid = $this->config['chinaums_mid_wx'] ? $this->config['chinaums_mid_wx']:$this->config['chinaums_mid'];
				}
				break;
			case 'wechat_mini':
				$mid = $this->config['chinaums_mid_wxapp'] ? $this->config['chinaums_mid_wxapp']:$this->config['chinaums_mid'] ;
				
				break;
			case 'iosapp':
			case 'androidapp':
				$mid = $this->config['chinaums_mid_app'] ? $this->config['chinaums_mid_app']:$this->config['chinaums_mid'] ;
				break;
		}
		$post = [
			'requestTimestamp' => date('Y-m-d H:i:s'),
			'merOrderId' => $merOrderId,
			'mid' => $mid,
			'tid' => $this->config['chinaums_tid'],
			'instMid' => 'YUEDANDEFAULT',
		];
		$result = $this->postReq($url, $post);
		if($result['errCode'] != 'SUCCESS'){
			throw new \think\Exception($result['errMsg']);	
		}
		if($result['status'] != 'TRADE_SUCCESS'){
			throw new \think\Exception($result['errMsg']);	
			// return [
			// 	'paid_money' => $result['totalAmount'],
			// 	'paid_time' => time(),
			// 	'transaction_no' => $result['targetOrderId'],
			// 	'status' => 2,
			// ];
		}
		return [
			'paid_money' => $result['totalAmount'],
			'paid_time' => time(),
			'transaction_no' => $result['targetOrderId'],
			'status' => 1,
		];
	}

	public function createRefundNo(){
		$ori_order_no = $this->config['chinaums_msgsrcid'].date('YmdHis').rand(1000000,9999999);
		return $ori_order_no;
	}


    public function refund($order_no, $total_fee, $refund_money, $transaction_no = '')
    {
		if ($this->config['without_asset'] == 1 && $this->agent == 'wechat_mini') {
			return $this->refundWithoutAsset($order_no, $total_fee, $refund_money, $transaction_no);
		}

        $url = $this->config['gateway'] . '/v1/netpay/refund';
        $sub_mer_no = $this->config['sub_mer_no'];
        $merOrderId = $this->createOrderNo($order_no);
        $refundOrderId = $this->createRefundNo();
        $merAmount = get_format_number($refund_money);

		$payService = new PayService();
		$payOrderInfo = $payService->getPayOrderInfo($order_no);
		if (empty($payOrderInfo)) {
			throw new \think\Exception("订单查询失败！没有找到支付信息！");
		}
		$refundTotal = intval($refund_money);
		if (cfg('chinaums_platform_sub_mchid')) {
			if ($payOrderInfo['chinaums_merchant_already_get'] >= $refundTotal) {
				$merRefundAmount = $refundTotal;
				$platRefundAmount = 0;
			} else {
				$merRefundAmount = $payOrderInfo['chinaums_merchant_already_get'];
				$platRefundAmount = $refundTotal - $merRefundAmount;
			}
		} else {
			$merRefundAmount =  $merAmount;
			$platRefundAmount = 0;
		}
		

        $mid = '';
        switch ($this->agent) {
            case 'wechat_h5'://微信端
            case 'h5':
                if($this->config['pay_type'] == 'wechat'){
                    $mid = $this->config['chinaums_mid_wx'] ? $this->config['chinaums_mid_wx']:$this->config['chinaums_mid'];
                }
                break;
            case 'wechat_mini':

                $mid = $this->config['chinaums_mid_wxapp'] ? $this->config['chinaums_mid_wxapp']:$this->config['chinaums_mid'] ;

                break;
            case 'iosapp':
            case 'androidapp':
                $mid = $this->config['chinaums_mid_app'] ? $this->config['chinaums_mid_app']:$this->config['chinaums_mid'] ;
                break;
        }

        $post = [
            'requestTimestamp' => date('Y-m-d H:i:s'),
            'merOrderId' => $merOrderId,
           // 'mid' => $this->config['chinaums_mid'],
            'mid' => $mid,
            'tid' => $this->config['chinaums_tid'],
            'instMid' => 'YUEDANDEFAULT',
            'targetOrderId' => $transaction_no,
            'refundAmount' => get_format_number($refund_money),
            'refundOrderId' => $refundOrderId,
            'platformAmount' => get_format_number(0),
        ];
       
        if (!empty($sub_mer_no)) {
			$post['subOrders'] = [
				[
					'totalAmount' => get_format_number($refund_money),
					'mid' => $sub_mer_no
				],
			];
			if (cfg('chinaums_platform_sub_mchid')) {
				$post['subOrders'] = [
					[
						'totalAmount' => get_format_number($merRefundAmount),
						'mid' => $sub_mer_no
					],
					[
						'mid' => cfg('chinaums_platform_sub_mchid'),
						'totalAmount' => get_format_number($platRefundAmount),
					]
				];
			}
			
        }
        try {
            $result = $this->postReq($url, $post);
			fdump_api([$this->config, $post, $order_no, $result], 'refund_0124', 1);
            if ($result['status'] == 'REFUND' || $result['status'] == 'TRADE_SUCCESS') {


				if (cfg('chinaums_platform_sub_mchid') && $sub_mer_no) {
					//有结算记录，当退款的时候才记录退款记录。
					$fenzhangMod = \think\facade\Db::name('chinaums_fenzhang_record');
					// if ($fenzhangMod->where(['pay_orderid' => $order_no, 'type' => 'settlement'])->find()) {
						$merId = (new \app\common\model\db\Merchant())->where('chinaums_merno', $sub_mer_no)->value('mer_id');
						$chinaums_data = [
							'mer_id' => $merId,
							'create_time' => time(),
							'merchant_money' => get_format_number($merRefundAmount) * -1,
							'platform_money' => get_format_number($platRefundAmount) * -1,
							'is_withdraw' => 0,
							'plat_is_withdraw' => 0,
							'pay_orderid' => $payOrderInfo['orderid'],
							'type' => 'refund'
						];
						$fenzhangMod->insert($chinaums_data);
					// }
				}

                $refund_param['refund_id'] = $result['refundTargetOrderId'];
                $refund_param['refund_time'] = strtotime($result['responseTimestamp']);
                return ['refund_no' => $refundOrderId, 'refund_param' => $refund_param];
            } else {
                throw new \think\Exception('状态:' . $result['refundStatus']);
            }
        } catch (\Exception $e) {
            throw new \think\Exception('状态:' . $e->getMessage());
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
			throw new \think\Exception("订单ID必填！");
		}
		$url = $this->config['gateway'] . '/v1/inip/upsp/shop/async-confirm';
		$merOrderId = $this->createOrderNo($order_no);

		$subOrderAmt = array_sum(array_column($subOrders, 'satAmt'));
		$post = [
			'mchntOrderId' => 'FZ' . $merOrderId,
			'vaMchntNo' => $this->config['chinaums_mid_wxapp'] ? $this->config['chinaums_mid_wxapp'] : $this->config['chinaums_mid'],
			'vaTermNo' => $this->config['chinaums_tid'],
			'sysSource' => 'OPT',
			'transAmt' => get_format_number($money),
			'vaFld' => [
				'isSubMchntPay' => '01',
				'platAmt' => get_format_number($money - $subOrderAmt),
				'oriMchntOrderId' => $merOrderId,
				'satInfo' => $subOrders
			]
		];
		$result = $this->postReq($url, $post);
        fdump_api(['url'=>$url, 'post'=>$post, 'result'=>$result], '00chinaums_fenzhangConfirm', 1);
		if ($result['respCode'] !== '0000') {
			throw new \think\Exception($result['respDesc']);
		}
		return $result;
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
	public function WechatminiWithoutAsset($order_no, $money){
		if(empty($order_no) || empty($money)){
			throw new \think\Exception("订单ID或支付金额必填！");	
		}
		if(empty($this->config['wxapp_openid'])){
			throw new \think\Exception("未获取到openid，无法调起支付");
		}
		$url = $this->config['gateway'].'/v1/inip/upsp/shop/mini/pay';
		$merOrderId = $this->createOrderNo($order_no);
		$post = [
			'mchntOrderId' => $merOrderId,
			'vaMchntNo' => $this->config['chinaums_mid_wxapp'] ? $this->config['chinaums_mid_wxapp']:$this->config['chinaums_mid'] ,
			'vaTermNo' => $this->config['chinaums_tid'],
			'msgType'=>'wx.unifiedOrder',
			'sysSource'=>'OPT',
			'tradeType'=>'MINI',
			'totalAmount' => get_format_number($money * 100),	
			'instMid' => 'MINIDEFAULT',
			'notifyUrl' => (String) url('chinaums_notify', [], false, true),
			//'subAppId' => 'wxd6cd1b1a2c1a57b9',
			'subAppId' => cfg("pay_wxapp_appid"),
			'subOpenId' => $this->config['wxapp_openid'],
			'vaFld'=>[
				'newShopFlag'=>'GWCXS',
				'isSubMchntPay'=>'01',
				'platAmt'=>$this->config['platform_get'],
				'satType'=>'02',
				'satInfo'=>[
					[
						'merId'=>$this->config['sub_mer_no'],
						'satAmt'=>$this->config['merchant_get'],
					]
				]
			]
		];
		$result = $this->postReq($url, $post);
		if($result['respCode'] !== '0000'){
			throw new \think\Exception($result['respDesc']);	
		}
		return [
			'type' => 'jssdk',
            'merchant_get' => $this->config['merchant_get'],
			'platform_get' => $this->config['platform_get'],
			'info' => json_decode($result['miniPayRequest'],true),
		];
	}

	/**
	 * 新购物车退款
	 *
	 * @param string $order_no
	 * @param float $total_fee 单位：分
	 * @param float $refund_money  单位：分
	 * @param string $transaction_no
	 * @return void
	 * @date: 2024/02/01
	 */
	public function refundWithoutAsset($order_no, $total_fee = '', $refund_money, $transaction_no = '')
	{
		$url = $this->config['gateway'] . '/v1/inip/upsp/shop/mini/refund';
		$merOrderId = $this->createOrderNo($order_no);
		$refundOrderId = $this->createRefundNo();

		$payService = new PayService();
		$payOrderInfo = $payService->getPayOrderInfo($order_no);
		if (empty($payOrderInfo)) {
		 	throw new \think\Exception("订单查询失败！没有找到支付信息！");
		}
		$refundTotal = intval($refund_money);
		if ($payOrderInfo['chinaums_merchant_already_get'] >= $refundTotal) {
			$merRefundAmount = $refundTotal;
			$platRefundAmount = 0;
		} else {
			$merRefundAmount = $payOrderInfo['chinaums_merchant_already_get'];
			$platRefundAmount = $refundTotal - $merRefundAmount;
		}

		$mid = $this->config['chinaums_mid_wxapp'] ? $this->config['chinaums_mid_wxapp'] : $this->config['chinaums_mid'];
		$post = [
			'mchntOrderId' => $merOrderId,  //如果订单确认了传递确认订单号，没确认传递原订单号
			'vaMchntNo' => $mid,
			'vaTermNo' => $this->config['chinaums_tid'],
			'refundAmount' => get_format_number($refund_money),
			'refundOrderId' => $refundOrderId,
			'sysSource' => 'OPT',
			'vaFld' => [
				'newShopFlag' => 'GWCXS',
				'isSubMchntPay' => '01',
				'platAmt' => get_format_number($platRefundAmount),
				'satType' => '02',
				'satInfo' => [
					[
						'merId' => $this->config['sub_mer_no'],
						'refundAmt' => get_format_number($merRefundAmount),
						//'oriMerOrderId' => '20240205154136100024554547'  //订单确认了需要传递子商户分账订单号
					]
				],
				'oriMchntOrderId' => $merOrderId,
			],
		];

		$asyncConfirmRs = $payOrderInfo['async_confirm_result'] ? json_decode($payOrderInfo['async_confirm_result'], true) : [];
		if ($asyncConfirmRs) {
			$post['mchntOrderId'] = 'FZ' . $post['mchntOrderId'];
			$satDetail =  array_column($asyncConfirmRs['vaFld']['satDetail'], 'merOrderId', 'merId');
			foreach ($post['vaFld']['satInfo'] as &$s) {
				$s['oriMerOrderId'] = $satDetail[$s['merId']] ?? '';
			}
		}

		$result = $this->postReq($url, $post);
		if ($result['respCode'] !== '0000') {
			throw new \think\Exception($result['respDesc']);
		}
		$refund_param['refund_id'] = $result['refundTargetOrderId'];
		$refund_param['refund_time'] = strtotime($result['responseTimestamp']);
		return ['refund_no' => $refundOrderId, 'refund_param' => $refund_param];
	}


	public function queryWithoutAsset($order_no){
		$url = $this->config['gateway'] . '/v1/inip/upsp/shop/mini/pay-query';
		$mid = $this->config['chinaums_mid_wxapp'] ? $this->config['chinaums_mid_wxapp'] : $this->config['chinaums_mid'];
		$post = [
			'mchntOrderId' => $order_no,
			'vaMchntNo' => $mid,
			'vaTermNo' => $this->config['chinaums_tid'],
			'sysSource' => 'OPT',
			'vaFld' => [
				'newShopFlag' => 'GWCXS'
			]
		];

		$result = $this->postReq($url, $post);
		if ($result['respCode'] !== '0000') {
			throw new \think\Exception($result['respDesc']);
		} else if ($result['status'] != "TRADE_SUCCESS") {
			throw new \think\Exception("支付状态异常：" . $result['status']);
		} else {
			return [
				'paid_money' => $result['totalAmount'],
				'paid_time' => strtotime($result['payTime']),
				'transaction_no' => $result['targetOrderId'],
				'status' => 1,
			];
		}
	}
}