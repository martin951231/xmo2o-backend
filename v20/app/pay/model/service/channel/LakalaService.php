<?php

namespace app\pay\model\service\channel;

use app\pay\model\service\libs\lakala\FdsSdk;
use think\facade\Db;

/**
 * 钱账通拉卡拉 http://47.110.246.50:6524/docs/qzt/qzt-1ekbceebjccgf
 *
 * @author: zt
 * @date: 2025/03/20
 */
class LakalaService
{
    private $agent = '';
    private $config = []; //配置参数

    public function __construct($agent = '', $config = [])
    {
        $this->agent = $agent;
        $this->config = $config;
        $this->init();
    }

    private function init()
    {
        FdsSdk::setAppId(cfg('lakala_appid'));
        FdsSdk::setServerUrl(cfg('lakala_soa_url'));
        FdsSdk::setApiVersion("3.0");

        //DSAPublicKeyPath ：钱帐通的DSA公钥文件所在目录，需修改
        //DSAPrivateKeyPath ：应用自己的DSA私钥文件所在目录，需修改
        //DSAPrivateKeyPass ：应用自己的DSA私钥密码，需修改
        $DSAPublicKeyPath = root_path() . '..' . cfg('lakala_cloud_dsa_public');
        $DSAPrivateKeyPath = root_path() . '..' . cfg('lakala_dsa_private_key');
        $DSAPrivateKeyPass = cfg('lakala_dsa_pass');

        FdsSdk::setDSAKey($DSAPublicKeyPath, $DSAPrivateKeyPath, $DSAPrivateKeyPass);

        //RSAPublicKeyPath ：钱帐通的RSA公钥文件所在目录，需修改
        //RSAPrivateKeyPath ：应用自己的RSA私钥文件所在目录，需修改
        //RSAPrivateKeyPass ：应用自己的RSA私钥密码，需修改
        $RSAPublicKeyPath = root_path() . '..' . cfg('lakala_cloud_rsa_public');
        $RSAPrivateKeyPath = root_path() . '..' . cfg('lakala_rsa_private_key');
        $RSAPrivateKeyPass = cfg('lakala_rsa_pass');
        FdsSdk::setRSAKey($RSAPublicKeyPath, $RSAPrivateKeyPath, $RSAPrivateKeyPass);
    }

    //去支付
    public function pay($orderNo, $money, $extraCfg)
    {
        if (empty($orderNo) || empty($money)) {
            throw new \think\Exception("订单ID或支付金额必填！");
        }

        //公众号和小程序是走JSAPI是吧，H5端口走GATEWAY
        switch ($this->agent) {
            case 'wechat_h5': //微信端
                return $this->WechatH5Pay($orderNo, $money, $extraCfg);
            case 'wechat_mini':
                return $this->WechatMiniPay($orderNo, $money, $extraCfg);
            case 'iosapp':
            case 'androidapp':
            case 'h5':
                //return $this->GatewayPay($orderNo, $money, $extraCfg);
                throw new \think\Exception("当前端口未对接，不能使用");
            default:
                throw new \think\Exception("当前端口未对接，不能使用");
        }
    }

    private function WechatH5Pay($orderNo, $money, $extraCfg)
    {
        $bodyStr = "订单编号：" . $extraCfg['business_order_sn'];
        $param = [
            'seller_member_no' => $this->config['lakala_sub_member_no'],
            'out_order_no' => $orderNo,
            'amount' => bcmul($money , 100),
            'pay_amount' => bcmul($money , 100),
            'terminal_ip' => get_client_ip(),
            'order_name' => $bodyStr,
            'split_mode' => 1,
            'back_url' => (string) url('lakala_notify', [], false, true),
            'pay_method' => [
                'JSAPI' => [
                    'amount' => bcmul($money , 100),
                    'mch_appid' => cfg('wechat_appid'),
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
            'type' => 'jssdk',
            'info' => [
                'appId' => $payInfo['appId'],
                'timeStamp' => $payInfo['timestamp'],
                'nonceStr' => $payInfo['nonce_str'],
                'package' => $payInfo['package'],
                'signType' => $payInfo['sign_type'],
                'paySign' => $payInfo['pay_sign'],
            ]
        ];
    }

    private function WechatMiniPay($orderNo, $money, $extraCfg)
    {
        $bodyStr = "订单编号：" . $extraCfg['business_order_sn'];
        $param = [
            'seller_member_no' => $this->config['lakala_sub_member_no'],
            'out_order_no' => $orderNo,
            'amount' => bcmul($money , 100),
            'pay_amount' => bcmul($money , 100),
            'terminal_ip' => get_client_ip(),
            'order_name' => $bodyStr,
            'split_mode' => 1,
            'back_url' => (string) url('lakala_notify', [], false, true),
            'pay_method' => [
                'JSAPI' => [
                    'amount' => bcmul($money , 100),
                    'mch_appid' => cfg("pay_wxapp_appid"),
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
            'type' => 'jssdk',
            'info' => [
                'appId' => $payInfo['appId'],
                'timeStamp' => $payInfo['timestamp'],
                'nonceStr' => $payInfo['nonce_str'],
                'package' => $payInfo['package'],
                'signType' => $payInfo['sign_type'],
                'paySign' => $payInfo['pay_sign'],
            ]
        ];
    }
    private function GatewayPay($orderNo, $money, $extraCfg)
    {
        $bodyStr = "订单编号：" . $extraCfg['business_order_sn'];
        $param = [
            'seller_member_no' => $this->config['lakala_member_no'],
            'out_order_no' => $orderNo,
            'amount' => intval($money * 100),
            'pay_amount' => intval($money * 100),
            'terminal_ip' => get_client_ip(),
            'order_name' => $bodyStr,
            'split_mode' => 1,
            'back_url' => (string) url('lakala_notify', [], false, true),
            'pay_method' => [
                'GATEWAY' => [
                    'amount' => intval($money * 100),
                    'merchant_no' => $this->config['lakala_merchant_no'],
                    'member_no' => $this->config['lakala_member_no'],
                    'product_name' => $bodyStr
                ],
            ]
        ];
        $result = FdsSdk::getService()->orderConsume($param);
        return [
            'error' => '0',
            'type'  => 'redirect',
            'info'  => $result['result']['pay_info']['counter_url']
        ];
    }

    //订单查询
    public function query($orderNo)
    {
        $res =  FdsSdk::getService()->orderDetailInfoGet($orderNo);
        $payList = isset($res['result']['pay_list']) ? $res['result']['pay_list'][0] : [];

        //查询支付信息
        if ($payList['pay_status'] == 1004) {
            return [
                'status' => 1,
                'paid_money' => $res['result']['order_amount'],
                'paid_time' => strtotime($payList['pay_time']),
                'transaction_no' => $res['result']['order_no']
            ];
        } else {
            throw new \think\Exception("订单未支付");
        }
    }


    //通知
    public function notice()
    {
        //验签
        FdsSdk::getService()->getNotifyResult($_POST);
        $eventData = $_POST['event'] ? json_decode($_POST['event'], true) : [];
        return [
            'paid_money' => $eventData["amount"],
            'paid_time' => strtotime($eventData["pay_time"]),
            'transaction_no' => $eventData['order_no']
        ];
    }

    //退款
    public function refund($orderNo, $totalFee, $refundMoney, $transactionNo = '')
    {
        $res =  FdsSdk::getService()->orderDetailInfoGet($orderNo);
        $payList = isset($res['result']['pay_list']) ? $res['result']['pay_list'][0] : [];
        $refundNo = 'R' . $orderNo.date('is');
        $param = [
            'order_no' => $transactionNo,
            'out_request_no' => $refundNo,
            'refund_amount' => intval($refundMoney),
            'pay_seq_no' =>  $payList['pay_seq_no'] ?? '',
        ];

        //如果订单待分账金额足够退款，则不需要上送退款规则，直接从待分账金额退款
        $payAmount = $res['result']['pay_amount'];
        $splitAmount = $res['result']['split_amount'];
        $refundAmount = $res['result']['refund_amount'];
        $leftSplitAmount = bcsub(bcsub($payAmount, $splitAmount, 2), $refundAmount, 2);

        if (bccomp($leftSplitAmount, $refundMoney, 2) == -1) {
            //如果订单已分账，则需要上送退款规则
            $fzRs = Db::name('lakala_qianzhangtong_pay')->where('order_no', $transactionNo)->where('type', 1)->select()->toArray();
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
            $leftRefundMoney = $refundMoney;
            $len = count($totalMemberSplitLists);
            foreach ($totalMemberSplitLists as $k => $v) {
                if ($k == $len - 1) {
                    //最后一个给剩余全部
                    $refundRules[] = [
                        'member_no' => $v['member_no'],
                        'amount' => intval($leftRefundMoney)
                    ];
                } else {
                    $amount = min($v['amount'], ceil(bcmul($refundMoney, $v['amount'] / $totalSplitAmount, 2)));
                    $refundRules[] = [
                        'member_no' => $v['member_no'],
                        'amount' => intval($amount)
                    ];
                    $leftRefundMoney = bcsub($leftRefundMoney, $amount, 0);
                }
                $param['refund_rule'] = $refundRules;
            }

            //获取子商户档案
            $archive = Db::name('lakala_qianzhangtong_archives')->where('member_no', $totalMemberSplitLists[0]['member_no'])->find();
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
		Db::name('lakala_qianzhangtong_pay')->insert($param);

        return [
            'refund_no' => $refundNo,
            'merchant_get' => $merGet ?? 0,
            'platform_get' => $platGet ?? 0
        ];
    }

    //分账
    public function fenzhang($orderNo, $merchantGet, $platGet)
    {
        $archive = Db::name('lakala_qianzhangtong_archives')->where('member_no', $this->config['lakala_sub_member_no'])->find();
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
        Db::name('lakala_qianzhangtong_pay')->insert($param);

        return [
            'split_rule_result' => $result['result']['split_rule_result'] ?? '',
            'order_status' => $result['result']['order_status'] ?? '',
            'split_seq_no' => $result['result']['split_seq_no'] ?? '',
        ];
    }

    //全局参数
    public static function getParams($merId = 0, $villageId = 0)
    {
        $subMemberNo = $subMerchantNo = '';
        if ($merId) {
            $nowMerchant = Db::name('lakala_qianzhangtong_archives')->where('item_type', 'merchant')->where('item_id', $merId)->find();
            $subMemberNo = $nowMerchant['member_no'] ?? '';
            $subMerchantNo = $nowMerchant['merchant_no'] ?? '';
        }else if($villageId){
            $nowMerchant = Db::name('lakala_qianzhangtong_archives')->where('item_type', 'village')->where('item_id', $villageId)->find();
            $subMemberNo = $nowMerchant['member_no'] ?? '';
            $subMerchantNo = $nowMerchant['merchant_no'] ?? '';
        }
        if (empty($subMemberNo)) {
            return [];
        }

        return [
            'channel' => 'lakala',
            'is_own' => 0,
            'config' => [
                'lakala_pay_switch' => cfg('lakala_pay_switch'),
                'lakala_soa_url' => cfg('lakala_soa_url'),
                'lakala_appid' => cfg('lakala_appid'),
                'lakala_member_no' => cfg('lakala_member_no'),  //平台
                'lakala_merchant_no' => cfg('lakala_merchant_no'), //平台
                'lakala_cloud_dsa_public' => cfg('lakala_cloud_dsa_public'),
                'lakala_cloud_rsa_public' => cfg('lakala_cloud_rsa_public'),
                'lakala_dsa_private_key' => cfg('lakala_dsa_private_key'),
                'lakala_rsa_private_key' => cfg('lakala_rsa_private_key'),
                'lakala_dsa_pass' => cfg('lakala_dsa_pass'),
                'lakala_rsa_pass' => cfg('lakala_rsa_pass'),
                'lakala_sub_member_no' => $subMemberNo,
                'lakala_sub_merchant_no' => $subMerchantNo,
            ]
        ];
    }

    //获取开户进阶页面（H5-URL）
    public function register($outMemberNo)
	{
		$params = [
			'out_member_no' => $outMemberNo,
			'open_part' => 1,
			'back_url' => cfg('site_url'). '/wap.php?c=Qianzhangtong&a=notify',
			'member_category' => 0,
			'member_role' => 'R001',
			'member_type' => '4'
		];
		$res = FdsSdk::getService()->memberOpenPartPageUrlGet($params);
		return $res['result'] ?? [];
	}
}
