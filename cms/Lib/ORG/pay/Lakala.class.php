<?php


/**
 * 钱账通拉卡拉 http://47.110.246.50:6524/docs/qzt/qzt-1ekbceebjccgf
 *
 * @author: zt
 * @date: 2025/03/20
 */

require_once LIB_PATH . 'ORG/pay/Libs/lakala/FdsSdk.php';

class Lakala
{
	private $config;

	private $orderInfo;

	public function __construct($orderInfo = [], $config = [])
	{
		$this->orderInfo = $orderInfo;
		$this->config = $config;
		$this->init();
	}

	public function setConfig($field, $value)
	{
		$this->config[$field] = $value;
	}

	private function init()
	{
		FdsSdk::setAppId(C('config.lakala_appid'));
		FdsSdk::setServerUrl(C('config.lakala_soa_url'));
		FdsSdk::setApiVersion("3.0");

		//DSAPublicKeyPath ：钱帐通的DSA公钥文件所在目录，需修改
		//DSAPrivateKeyPath ：应用自己的DSA私钥文件所在目录，需修改
		//DSAPrivateKeyPass ：应用自己的DSA私钥密码，需修改
		$DSAPublicKeyPath = APP_PATH . '..'  . C('config.lakala_cloud_dsa_public');
		$DSAPrivateKeyPath = APP_PATH . '..'  . C('config.lakala_dsa_private_key');
		$DSAPrivateKeyPass = C('config.lakala_dsa_pass');

		FdsSdk::setDSAKey($DSAPublicKeyPath, $DSAPrivateKeyPath, $DSAPrivateKeyPass);

		//RSAPublicKeyPath ：钱帐通的RSA公钥文件所在目录，需修改
		//RSAPrivateKeyPath ：应用自己的RSA私钥文件所在目录，需修改
		//RSAPrivateKeyPass ：应用自己的RSA私钥密码，需修改
		$RSAPublicKeyPath = APP_PATH . '..'  . C('config.lakala_cloud_rsa_public');
		$RSAPrivateKeyPath = APP_PATH . '..'  . C('config.lakala_rsa_private_key');
		$RSAPrivateKeyPass =  C('config.lakala_rsa_pass');
		FdsSdk::setRSAKey($RSAPublicKeyPath, $RSAPrivateKeyPath, $RSAPrivateKeyPass);
	}

	//全局参数
	public static function getParams($merId = 0, $villageId = 0)
	{
		$subMemberNo = $subMerchantNo = '';
		if ($merId) {
			$nowMerchant = M('Lakala_qianzhangtong_archives')->where(['item_type' => 'merchant', 'item_id' => $merId])->find();
			$subMemberNo = $nowMerchant['member_no'] ?? '';
			$subMerchantNo = $nowMerchant['merchant_no'] ?? '';
		}
		if (empty($subMemberNo)) {
			return [];
		}

		return [
			'lakala_pay_switch' => C('config.lakala_pay_switch'),
			'lakala_soa_url' => C('config.lakala_soa_url'),
			'lakala_appid' => C('config.lakala_appid'),
			'lakala_member_no' => C('config.lakala_member_no'),  //平台
			'lakala_merchant_no' => C('config.lakala_merchant_no'), //平台
			'lakala_cloud_dsa_public' => C('config.lakala_cloud_dsa_public'),
			'lakala_cloud_rsa_public' => C('config.lakala_cloud_rsa_public'),
			'lakala_dsa_private_key' => C('config.lakala_dsa_private_key'),
			'lakala_rsa_private_key' => C('config.lakala_rsa_private_key'),
			'lakala_dsa_pass' => C('config.lakala_dsa_pass'),
			'lakala_rsa_pass' => C('config.lakala_rsa_pass'),
			'lakala_sub_member_no' => $subMemberNo,
			'lakala_sub_merchant_no' => $subMerchantNo,
		];
	}

	 //通知
	 public function notice()
	 {
		//验签
		FdsSdk::getService()->getNotifyResult($_POST);
		$eventData = $_POST['event'] ? json_decode($_POST['event'], true) : [];
		return [
			'paid_money' => $eventData["amount"]/100,
			'paid_time' => strtotime($eventData["pay_time"]),
			'transaction_no' => $eventData['order_no']
		];
	 }


	//微信小程序支付
	public function WechatMiniPay($money)
	{
		$bodyStr = $this->orderInfo['order_name'];
		$orderNo = $this->orderInfo['order_type'].'_'.$this->orderInfo['order_id'];
		$param = [
			'seller_member_no' => $this->config['lakala_sub_member_no'],
			'out_order_no' => $orderNo,
			'amount' => bcmul($money , 100),
			'pay_amount' => bcmul($money , 100),
			'terminal_ip' => get_client_ip(),
			'order_name' => $bodyStr,
			'split_mode' => 1,
			'back_url' => C('config.site_url').'/source/lakala_notify.php',
			'pay_method' => [
				'JSAPI' => [
					'amount' => bcmul($money , 100),
					'mch_appid' => C('config.pay_wxapp_appid'),
					'openid' => $this->config['wxapp_openid'],
					'mode' => 'wxpay',
					'subject' => $bodyStr,
					'merchant_no' => $this->config['lakala_sub_merchant_no'],
					'member_no' => $this->config['lakala_sub_member_no'],
				],
			]
		];
		$result = FdsSdk::getService()->orderConsume($param);
		$payInfo = $result['result']['pay_info'];
		return [
			'error' => '0',
			'weixin_param' => [
				'appId' => $payInfo['appId'],
				'timeStamp' => $payInfo['timestamp'],
				'nonceStr' => $payInfo['nonce_str'],
				'package' => $payInfo['package'],
				'signType' => $payInfo['sign_type'],
				'paySign' => $payInfo['pay_sign'],
			]
		];
	}

	//微信公众号支付
	public function WechatH5Pay($money)
	{
		$bodyStr = $this->orderInfo['order_name'];
		$orderNo = $this->orderInfo['order_type'].'_'.$this->orderInfo['order_id'];
		$param = [
			'seller_member_no' => $this->config['lakala_sub_member_no'],
			'out_order_no' => $orderNo,
			'amount' => bcmul($money , 100),
			'pay_amount' => bcmul($money , 100),
			'terminal_ip' => get_client_ip(),
			'order_name' => $bodyStr,
			'split_mode' => 1,
			'back_url' => C('config.site_url').'/source/lakala_notify.php',
			'pay_method' => [
				'JSAPI' => [
					'amount' => bcmul($money , 100),
					'mch_appid' => C('config.wechat_appid'),
					'openid' => $this->config['openid'],
					'mode' => 'wxpay',
					'subject' => $bodyStr,
					'merchant_no' => $this->config['lakala_sub_merchant_no'],
					'member_no' => $this->config['lakala_sub_member_no'],
				],
			]
		];
		$result = FdsSdk::getService()->orderConsume($param);
		$payInfo = $result['result']['pay_info'];
		return [
			'error' => '0',
			'weixin_param' => [
				'appId' => $payInfo['appId'],
				'timeStamp' => $payInfo['timestamp'],
				'nonceStr' => $payInfo['nonce_str'],
				'package' => $payInfo['package'],
				'signType' => $payInfo['sign_type'],
				'paySign' => $payInfo['pay_sign'],
			]
		];
	}

	//退款
	public function refund($orderNo, $totalFee, $refundMoney, $transactionNo = '')
	{
		$res =  FdsSdk::getService()->orderDetailInfoGet($orderNo);
		$payList = isset($res['result']['pay_list']) ? $res['result']['pay_list'][0] : [];
		$refundNo = 'R' . $orderNo;
		$param = [
			'order_no' => $transactionNo,
			'out_request_no' => $refundNo,
			'refund_amount' => intval($refundMoney * 100),
			'pay_seq_no' =>  $payList['pay_seq_no'] ?? '',
		];

		//如果订单待分账金额足够退款，则不需要上送退款规则，直接从待分账金额退款
        $payAmount = $res['result']['pay_amount'];
        $splitAmount = $res['result']['split_amount'];
        $refundAmount = $res['result']['refund_amount'];
        $leftSplitAmount = bcsub(bcsub($payAmount, $splitAmount, 2), $refundAmount, 2);

        if (bccomp($leftSplitAmount, $param['refund_amount'], 2) == -1) {
            //如果订单已分账，则需要上送退款规则
            $fzRs =  D('Lakala_qianzhangtong_pay')->where(['order_no' => $transactionNo, 'type' => 1])->select();
            $totalSplitAmount = 0;
            $totalMemberSplitLists = [];
            foreach ($fzRs as $f) {
                $totalSplitAmount += $f['split_amount'];
                $fzRules = json_decode($f['split_rule_data'], true)['split_list'];
                foreach ($fzRules as $fr) {
                    if (isset($totalMemberSplitLists[$fr['member_no']])) {
                        $totalMemberSplitLists[$fr['member_no']]['amount'] += $fr['amount'];
                    } else {
                        $totalMemberSplitLists[$fr['member_no']] = [
                            'member_no' => $fr['member_no'],
                            'amount' => $fr['amount']
                        ];
                    }
                }
            }
            $totalMemberSplitLists = array_values($totalMemberSplitLists);

            $refundRules = [];
            $leftRefundMoney = $param['refund_amount'];
            $len = count($totalMemberSplitLists);
            foreach ($totalMemberSplitLists as $k => $v) {
                if ($k == $len - 1) {
                    //最后一个给剩余全部
                    $refundRules[] = [
                        'member_no' => $v['member_no'],
                        'amount' => intval($leftRefundMoney)
                    ];
                } else {
                    $amount = min($v['amount'], ceil(bcmul($param['refund_amount'], $v['amount'] / $totalSplitAmount, 2)));
                    $refundRules[] = [
                        'member_no' => $v['member_no'],
                        'amount' => intval($amount)
                    ];
                    $leftRefundMoney = bcsub($leftRefundMoney, $amount, 0);
                }
                $param['refund_rule'] = $refundRules;
            }
			$archive = M('Lakala_qianzhangtong_archives')->where(['member_no' => $totalMemberSplitLists[0]['member_no']])->find();
			if (empty($archive)) {
				throw new \Exception('未找到商户注册档案');
			}
			$merGet = isset($totalMemberSplitLists[0]['amount']) ? bcdiv((string)$totalMemberSplitLists[0]['amount'], '100', 2) : 0;
            $platGet = isset($totalMemberSplitLists[1]['amount']) ? bcdiv((string)$totalMemberSplitLists[1]['amount'], '100', 2) : 0;
        }
		$result = FdsSdk::getService()->orderConsumeRefund($param);

		//记录退款记录
		if (!empty($archive)) {
			$param['item_type'] = $archive['item_type'];
			$param['item_id'] = $archive['item_id'];
		}
		$param['type'] = 2;
		$param['refund_rule'] = json_encode($param['refund_rule'], JSON_UNESCAPED_UNICODE);
		$param['resp'] = json_encode($result, JSON_UNESCAPED_UNICODE);
		D('Lakala_qianzhangtong_pay')->add($param);

		return [
			'error' => 0, 
			'type' => 'ok',
			'msg' => '退款申请成功！请注意查收退款通知。',
			'refund_param'=>[
				'refund_time' => time(),
				'refund_no' => $refundNo,
				'merchant_get' => $merGet ?? 0,
            	'platform_get' => $platGet ?? 0
			]
		];
	}



	//获取开户进阶页面（H5-URL）
	public function register($outMemberNo)
	{
		$params = [
			'out_member_no' => $outMemberNo,
			'open_part' => 1,
			'back_url' => C('config.site_url') . '/wap.php?c=Qianzhangtong&a=notify',
			'member_category' => 0,
			'member_role' => 'R001',
			'member_type' => '4'
		];
		$res = FdsSdk::getService()->memberOpenPartPageUrlGet($params);
		return $res['result'] ?? [];
	}

	//商户绑定
	public function memberMerchantBind($memberNo, $channelMerchantNo, $channelTerminalNo)
	{
		$params = [
			'member_no' => $memberNo,
			'channel_merchant_no' => $channelMerchantNo,
			'channel_terminal_no' => $channelTerminalNo,
			'merchant_type' => 'BMCP',
			'back_url' => C('config.site_url') . '/wap.php?c=Qianzhangtong&a=notify',
		];
		$res = FdsSdk::getService()->memberMerchantBind($params);
		return $res['result'] ?? [];
	}

	//商户解绑
	public function memberMerchantUnbind($memberNo, $channelMerchantNo, $channelTerminalNo)
	{
		$params = [
			'member_no' => $memberNo,
			'channel_merchant_no' => $channelMerchantNo,
			'channel_terminal_no' => $channelTerminalNo,
			'merchant_type' => 'BMCP',
		];
		$res = FdsSdk::getService()->memberMerchantUnbind($params);
		return $res['result'] ?? [];
	}


	//商户对账文件
	public function orderCheckDownload($billDate){
		$params = [
			'bill_date' => $billDate
		];
		$url = FdsSdk::getService()->orderCheckDownload($params);
		return $url;
	}

	//会员绑卡相关页面（H5-URL）
	public function memberBankCardPageUrlGet($memberNo)
	{
		$params = [
			'member_no' => $memberNo,
			'back_url' =>  C('config.site_url') . '/wap.php?c=Qianzhangtong&a=notify',
		];
		$res = FdsSdk::getService()->memberBankCardPageUrlGet($params);
		return $res['result'] ?? [];
	}

	//分账
    public function fenzhang($orderNo, $merchantGet, $platGet)
    {
		$archive = M('Lakala_qianzhangtong_archives')->where(['member_no' => $this->config['lakala_sub_member_no']])->find();
		if (empty($archive)) {
			throw new \Exception('未找到商户注册档案');
		}

        $param = [
            'member_no' => $this->config['lakala_sub_member_no'],
            'order_no' => $orderNo,
            'out_request_no' => 'fz' . date('YmdHis') . rand(1000, 9999),
            'split_amount' => intval(bcadd($merchantGet , $platGet)),
            'split_rule_data' =>  json_encode([
                'split_list' => [
                    [
                        'member_no' => $this->config['lakala_sub_member_no'],
                        'amount' => intval(bcmul($merchantGet,1)),
                    ],
                    [
                        'member_no' => $this->config['lakala_member_no'],
                        'amount' => intval(bcmul($platGet,1)),
                    ],
                ]
            ]),
        ];
        $result = FdsSdk::getService()->orderComplete($param);

		//记录分账记录
		$param['item_type'] = $archive['item_type'];
        $param['item_id'] = $archive['item_id'];
		$param['type'] = 1;
		$param['resp'] = json_encode($result, JSON_UNESCAPED_UNICODE);
		D('Lakala_qianzhangtong_pay')->add($param);

        return [
            'split_rule_result' => $result['result']['split_rule_result'] ?? '',
            'order_status' => $result['result']['order_status'] ?? '',
            'split_seq_no' => $result['result']['split_seq_no'] ?? '',
        ];
    }
}
