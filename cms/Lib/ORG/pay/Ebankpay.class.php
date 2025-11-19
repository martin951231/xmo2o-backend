<?php
/**
 * 
 * 日照银行支付
 * 文档地址：https://ebank.bankofrizhao.com.cn/uop/portal/#/productNavPage
 * 
 */

class Ebankpay {
    protected $order_info;
    protected $pay_money;
    protected $pay_type;
    protected $is_mobile;
    protected $pay_config;
    protected $user_info;
    protected $testUrl;
    protected $liveUrl;
    protected $pubkey;
    protected $prikey;
    protected $aesStr;
    protected $frontBackUrl;
    protected $notifyBackUrl;
    protected $timestamp;

	public function __construct($order_info, $pay_money, $pay_type, $pay_config, $user_info, $is_mobile=0) {
        $this->order_info = $order_info;
        $this->pay_money  = $pay_money;
        $this->pay_type   = $pay_type;
        $this->is_mobile  = $is_mobile;
        $this->pay_config = $pay_config;
        $this->user_info  = $user_info;
		$this->testUrl = C('config.pay_ebankpay_url') . 'uop/api/pay/paypro/'; //测试环境
        $this->liveUrl = C('config.pay_ebankpay_url') . 'uop/api/pay/paypro/'; //正式环境 https://ebank.bankofrizhao.com.cn/
        $this->pubkey  = C('config.site_url') . C('config.pay_ebankpay_pubkey');
        $this->prikey  = C('config.site_url') . C('config.pay_ebankpay_prikey');
        $this->aesStr  = createRandomStr(22) . '==';
        $this->frontBackUrl  = C('config.site_url') . '/source/wap_ebank_back.php?order_no=' . $order_info['order_type'] . '_' . $this->order_info['order_id'] . '_ebankpay'; //回跳地址,回跳商户地址
        $this->notifyBackUrl = C('config.site_url') . '/source/wap_ebank.php'; //通知地址,后台通知地址，需要异步通知时上送
        $this->timestamp     = date('YmdHis') . '000'; //时间戳 YYYYMMDDHHmmssSSS精确到毫秒,如20200109163545256
	}

	public function pay($env = 'pc') {
        if (empty($this->order_info['order_id']) || empty($this->pay_money)) {
            return array('error' => 1, 'msg' => '订单ID或支付金额必填！');
        }
        $res = $this->searchPay($this->order_info['order_id']);
        if ($res['error'] == 0) { //订单已支付
            $orderInfo = D('Payment_money_order')->where(['orderid' => $this->order_info['order_id'], 'pay_type' => 'ebankpay', 'paid_money' => 0])->find();
            $amount = floatval($res['pay_money']);
            D('Payment_money_order')->where(['orderid' => $orderInfo['orderid']])->save(['paid_money' => $amount]);
            $res = [
                'order_id'   => $orderInfo['orderid'],
                'pay_type'   => $orderInfo['pay_type'],
                'is_mobile'  => $this->is_mobile,
                'order_from' => 1,
                'order_type' => $orderInfo['order_type'] ?? 'shop',
                'is_own'     => $orderInfo['is_own'],
                'third_id'   => $data['paySeqNum'] ?? '',
                'pay_money'  => $amount
            ];
            $pay_info = D('Pay')->after_pay($res['order_type'], ['order_param' => $res], false);
            return array('error' => 1, 'msg' => '该订单已支付，请勿重复支付');
        }
        return $this->Ebankpay($this->order_info['order_id'], $this->pay_money, $env);
	}

	public function Ebankpay($order_no, $money, $env) {
        $openid = $this->user_info['openid'];
        $cashierType = 'H5';
        $browser     = base64_encode($_SERVER['HTTP_USER_AGENT']);
        $accessType  = 'ACCESS_WEB';
        $envData     = [
            'browser' => $browser
        ];
        if ($env == 'wxMini') {
            $openid  = $this->user_info['wxapp_openid'];
        }
        $env == 'pc' && $cashierType = 'WEB';
        if ($env == 'app') {
            $this->frontBackUrl = '';
            $accessType = 'ACCESS_SDK';
            $envData = [
                'appPackName' => $this->pay_config['appPackName'] ?? '', //android必传
                'appPackSign' => $this->pay_config['appPackSign'] ?? '', //android必传
                'appBundleId' => $this->pay_config['appBundleId'] ?? '' //ios必传
            ];
        }
		$header = [
		    'X-Uop-Api-Code'  => 'OPC109000202000100000100', //API编码
            'X-Uop-App-Id'    => $this->pay_config['pay_ebankpay_appid'],
            'X-Uop-Unique-No' => $order_no . '_' . uniqid(),
            'X-Uop-Timestamp' => $this->timestamp,
        ];
		$arr = [
            'userId'          => strval($this->user_info['uid']), //用户id,用户编号,填平台用户唯一ID
            'mchntNo'         => $this->pay_config['pay_ebankpay_mchid'], //商户号,商户号,单笔订单时必送，合并支付时，填平台商户号
            'mchntOrderId'    => $order_no, //商户订单号
            'bizSceneType'    => '01',
            'orderCcy'        => '156', //交易币种,默认156-人民币
            'orderAmt'        => strval($money), //交易金额,订单总金额，单位为元，保留两位小数,填值为：订单明细orderDetailList中所有订单金额orderAmt金额之和
            'orderExpiryTime' => '600', //订单有效期,单位为秒
            'orderDesc'       => '订单号：' . $order_no, //订单描述,商户订单描述信息
            'frontBackUrl'    => str_replace('order_no', $order_no, $this->frontBackUrl), //回跳地址,回跳商户地址
            'notifyBackUrl'   => $this->notifyBackUrl, //通知地址,后台通知地址，需要异步通知时上送
            'extraParamLists' => [ //附加参数
                [
                    'paramKey'   => 'cashierType', //参数Key
                    'paramValue' => $cashierType, //参数Value
                    'sensitiveInfoFlag' => 'N' //敏感信息标识,Y-敏感信息，N-非敏感信息
                ],
                [
                    'paramKey'   => 'accessType', //参数Key
                    'paramValue' => $accessType, //参数Value
                    'sensitiveInfoFlag' => 'N' //敏感信息标识,Y-敏感信息，N-非敏感信息
                ],
                [
                    'paramKey'   => 'appId', //参数Key
                    'paramValue' => $this->pay_config['pay_ebankpay_appid'], //参数Value
                    'sensitiveInfoFlag' => 'N' //敏感信息标识,Y-敏感信息，N-非敏感信息
                ],
                [
                    'paramKey'   => 'envData', //参数Key
                    'paramValue' => json_encode($envData, true), //参数Value
                    'sensitiveInfoFlag' => 'N' //敏感信息标识,Y-敏感信息，N-非敏感信息
                ]
            ],
            'orderDetailLists' => [ //订单明细
                [
                    'orderCcy'        => '156', //交易币种
                    'orderAmt'        => strval($money), //交易金额
                    'orderSettleAmt'  => strval($money), //商户结算金额
                    'mchntNo'         => $this->pay_config['pay_ebankpay_smchid'], //子商户号
                    'mchntSubOrderId' => $order_no, //商户子订单号
                ]
            ]
        ];
        if ($accessType != 'ACCESS_SDK') {
            $arr['extraParamLists'][] = [
                'paramKey'   => 'openId', //参数Key
                'paramValue' => $openid, //参数Value
                'sensitiveInfoFlag' => 'N' //敏感信息标识,Y-敏感信息，N-非敏感信息
            ];
        }
        $header['Body'] = $this->getBody($arr);
        $header['X-Uop-Signature']     = $this->getSign($header);
        $header['X-Uop-Encrypted-Key'] = $this->getEncryptedKey();
        unset($header['Body']);
        $res = $this->doRequest('POST', $arr, $this->testUrl . 'payment/v1', $header); //testUrl liveUrl
        fdump(json_encode($arr), 'Ebankpay_pay', 1);
		$result = json_decode($res,true);
        fdump($env, 'Ebankpay_pay', 1);
        fdump($result, 'Ebankpay_pay', 1);
		if ($result['uop_code'] == 'UC000000' && !empty($result['uop_data']['onlineCasherUrl'])) {
		    if (in_array($env, ['wxMini', 'app'])) { //, 'wxH5'
                return array('error' => 0, 'redirectH5Pay' => $result['uop_data']['onlineCasherUrl']);
            } else {
                redirect($result['uop_data']['onlineCasherUrl']);
            }
		} else {
            return array('error' => 1, 'msg' => '支付失败：' . $result['uop_msg'] ?? '');
//			throw new Exception('支付失败：' . $result['uop_msg'] ?? '');
		}
	}

    //查询订单支付状态
    public function searchPay($order_no) {
        fdump($order_no, 'Ebankpay_searchPay', 1);
        $header = [
            'X-Uop-Api-Code'  => 'OPC109000202000100000200', //API编码
            'X-Uop-App-Id'    => C('config.pay_ebankpay_appid'),
            'X-Uop-Unique-No' => $order_no . '_' . uniqid(),
            'X-Uop-Timestamp' => $this->timestamp,
        ];
        $arr = [
            'mchntNo'      => C('config.pay_ebankpay_mchid'), //商户号,商户号,单笔订单时必送，合并支付时，填平台商户号
            'mchntOrderId' => $order_no, //商户订单号
            'orderChanlId' => 'RZBKCTPT', //下单渠道标识
        ];
        $header['Body'] = $this->getBody($arr);
        $header['X-Uop-Signature'] = $this->getSign($header);
        $header['X-Uop-Encrypted-Key'] = $this->getEncryptedKey();
        $res    = $this->doRequest('POST', $arr, $this->testUrl . 'paymentstats/v1', $header);
        $result = json_decode($res,true);
        fdump($result, 'Ebankpay_searchPay', 1);
        if ($result['uop_code'] == 'UC000000' && !empty($result['uop_data']['orderInfo'])) {
            $orderInfo = $result['uop_data']['orderInfo'];
            if ($orderInfo['orderStatus'] == 3 || $orderInfo['orderStatus'] == '03') {
                return [
                    'error'     => 0,
                    'pay_money' => $orderInfo['orderAmt'],
                    'pay_type'  => 'ebankpay',
                    'trade_no'  => $orderInfo['sysSeqNum'] ?? ''
                ];
            }
        }
        return ['error' => 1, 'msg' => '查询失败：' . $result['uop_msg'] ?? ''];
//        throw new Exception('支付失败：' . $result['uop_msg'] ?? '');
    }


    //担保支付
    public function EbankGuarantee($order_no, $money) {
        $header = [
            'X-Uop-Api-Code'  => 'OPC109000202000100000100', //API编码
            'X-Uop-App-Id'    => $this->pay_config['pay_ebankpay_appid'],
            'X-Uop-Unique-No' => $order_no . '_' . uniqid(),
            'X-Uop-Timestamp' => $this->timestamp,
        ];
        $arr = [
            'userId'       => strval($this->user_info['uid']), //用户id,用户编号,填平台用户唯一ID
            'mchntNo'      => $this->pay_config['pay_ebankpay_smchid'], //商户号,商户号,单笔订单时必送，合并支付时，填平台商户号
            'mchntOrderId' => $order_no, //商户订单号
            'orderAmt'     => strval($money), //交易金额,订单总金额，单位为元，保留两位小数,填值为：订单明细orderDetailList中所有订单金额orderAmt金额之和
            'confirmOrderDetailLists' => [ //订单明细
                [
                    'orderCcy'        => '156', //交易币种
                    'orderAmt'        => strval($money), //交易金额
                    'orderSettleAmt'  => strval($money), //商户结算金额
                    'mchntNo'         => $this->pay_config['pay_ebankpay_smchid'], //子商户号
                    'mchntSubOrderId' => $order_no, //商户子订单号
                ]
            ]
        ];
        $header['Body'] = $this->getBody($arr);
        $header['X-Uop-Signature']     = $this->getSign($header);
        $header['X-Uop-Encrypted-Key'] = $this->getEncryptedKey();
        unset($header['Body']);
        $res = $this->doRequest('POST', $arr, $this->liveUrl . 'guarantee/v1', $header); //testUrl liveUrl
        fdump(json_encode($arr), 'Ebankpay_guarantee', 1);
        $result = json_decode($res,true);
        fdump($result, 'Ebankpay_guarantee', 1);
    }

    /**
     * 退款
     * @param  [type] $order_no       本系统支付单号
     * @param  [type] $total_fee 	  总支付金额（单位：分）
     * @param  [type] $transaction_no 第三方交易流水号（防止某些平台需要）
     * @param  [type] $refund_money   退款金额（单位：分）
     * @return [type]                 [description]
     */
    public function refund(){
        fdump($this->order_info['order_id'], 'Ebankpay_refund', 1);
        fdump($this->pay_money, 'Ebankpay_refund', 1);
        $header = [
            'X-Uop-Api-Code'  => 'OPC109000202000100000400', //API编码
            'X-Uop-App-Id'    => C('config.pay_ebankpay_appid'),
            'X-Uop-Unique-No' => $this->order_info['order_id'] . '_' . uniqid(),
            'X-Uop-Timestamp' => $this->timestamp,
        ];
        $refund_no = $this->order_info['order_id'] . '_' . uniqid();
        $arr = [
            'mchntNo'       => C('config.pay_ebankpay_mchid'), //商户号,商户号,单笔订单时必送，合并支付时，填平台商户号
            'mchntOrderId'  => $this->order_info['order_id'], //商户订单号
            'orderAmt'      => strval($this->pay_money), //交易金额,退款订单总金额，单位为元，保留两位小数 取值为：退款订单明细refundOrderDetailList中所有订单金额orderAmt之和
            'mchntRefundId' => $refund_no, //商户退款订单号,每次发起交易请使用新的订单号
            'refundOrderDetailLists' => [
                [
                    'mchntNo' => C('config.pay_ebankpay_smchid'), //pay_ebankpay_smchid
                    'mchntSubOrderId' => $this->order_info['order_id'],
                    'orderCcy' => '156',
                    'orderAmt' => strval($this->pay_money),
                    'orderSettleAmt' => strval($this->pay_money)
                ]
            ]
        ];
        $header['Body'] = $this->getBody($arr);
        $header['X-Uop-Signature'] = $this->getSign($header);
        $res    = $this->doRequest('POST', $arr, $this->testUrl . 'payrefund/v1', $header);
        $result = json_decode($res,true);
        fdump($result, 'Ebankpay_refund', 1);
        if ($result['uop_code'] == 'UC000000') {
            M('Payment_money_order')->where(['orderid' => $this->order_info['order_id']])->setField('orderNo', $refund_no);
            return ['error' => 0];
        } else {
            return ['error' => 1, 'msg' => '退款失败：' . $result['uop_msg'] ?? ''];
//            throw new Exception('退款失败：' . $result['uop_msg'] ?? '');
        }
    }

    /**
     * 异步通知
     * @return [type] [description]
     */
    public function notice_url() {
//        $data = file_get_contents("php://input");
//        $data = json_decode($data, true);
//        fdump($data, 'ebankpay_notice', 1);
//        if (isset($data['payStatus']) && $data['payStatus'] == '1') {
        $orderList = D('Payment_money_order')->where(['addtime' => ['EGT',  time() - 600], 'pay_type' => 'ebankpay', 'paid_money' => 0])->limit(20)->select();
        if ($orderList) {
            foreach ($orderList as $k => $orderInfo) {
                $res = $this->searchPay($orderInfo['orderid']);
                if ($res && $res['error'] == 0) {
                    $amount = floatval($res['pay_money']);
//                    $this->EbankGuarantee($orderInfo['orderid'], $amount); //担保支付
                    D('Payment_money_order')->where(['orderid' => $orderInfo['orderid']])->save(['paid_money' => $amount]);
                    $res = [
                        'order_id'   => $orderInfo['orderid'],
                        'pay_type'   => $orderInfo['pay_type'],
                        'is_mobile'  => $this->is_mobile,
                        'order_from' => 1,
                        'order_type' => $orderInfo['order_type'] ?? 'shop',
                        'is_own'     => $orderInfo['is_own'],
                        'third_id'   => $data['paySeqNum'] ?? '',
                        'pay_money'  => $amount
                    ];
                    $pay_info = D('Pay')->after_pay($res['order_type'], ['order_param' => $res], false);
                }
            }
        }
            fdump($orderList, 'ebankpay_notice', 1);
        echo json_encode(['uop_code' => "UC000000", 'code' => "success", 'uop_msg' => '成功']);die;
//        }
    }

    //生成Body
    public function getBody($data) {
        $data = json_encode($data);
        $Body = base64_encode($this->hexToStr($this->encrypt_sha256($data)));
        return $Body;
    }

    //生成签名
    public function getSign($data) {
	    ksort($data);
	    $str = urldecode(http_build_query($data));
        $rsaPriCertPath = $this->prikey;// 私钥证书路径
        $pwd = $this->pay_config['pay_ebankpay_prikey_pass'];// 私钥证书密码
        openssl_pkcs12_read(file_get_contents($rsaPriCertPath), $cert, $pwd);// 读取证书内容
        $RSA_PRIVATE = $cert['pkey'];//私钥
        openssl_sign($str, $sign, $RSA_PRIVATE, OPENSSL_ALGO_SHA256);
        $sign = base64_encode($sign);
        return $sign;
    }

    public function getEncryptedKey() {
        $aesKey = base64_decode($this->aesStr);//aes密钥这个要随机生成，每次请求都要变化。
        $encryptPubCertKey = file_get_contents($this->pubkey);// 读取证书内容
        $RSA_PUBLIC = openssl_pkey_get_public($encryptPubCertKey);// 公钥
        //公钥加密
        $public_key = openssl_pkey_get_public($RSA_PUBLIC);
        if (!$public_key) {
            throw new Exception('公钥不可用');
        }
        //第一个参数是待加密的数据只能是string，第二个参数是加密后的数据,第三个参数是openssl_pkey_get_public返回的资源类型,第四个参数是填充方式
        $return_en = openssl_public_encrypt($aesKey, $crypted, $public_key);
        if (!$return_en) {
            throw new Exception('加密失败,请检查RSA秘钥');
        }
        $eb64_cry = base64_encode($crypted);
        return $eb64_cry;
    }

    public function hexToStr($hex) {
        $str = "";
        for ($i = 0;$i < strlen($hex) - 1; $i += 2)
            $str .= chr(hexdec($hex[$i] . $hex[$i + 1]));
        return $str;
    }

    public function encrypt_sha256($str = '')
    {
        return hash("sha256", $str);
    }

    /**
     * 加密方法
     * @param string $str
     * @return string
     */
    public function encrypt($str, $screct_key)
    {
        //AES, 128 模式加密数据 CBC
        $screct_key = base64_decode($screct_key);
        $str = trim($str);
        $str = $this->addPKCS7Padding($str);
        //设置全0的IV
        $iv = str_repeat("\0", 16);
        $encrypt_str = openssl_encrypt($str, 'aes-128-cbc', $screct_key, OPENSSL_NO_PADDING, $iv);
        return base64_encode($encrypt_str);
    }

    /**
     * 解密方法
     * @param string $str
     * @return string
     */
    public function decrypt($str, $screct_key)
    {
        //AES, 128 模式加密数据 CBC
        $str = base64_decode($str);
        $screct_key = base64_decode($screct_key);

        //设置全0的IV
        $iv = str_repeat("\0", 16);
        $decrypt_str = openssl_decrypt($str, 'aes-128-cbc', $screct_key, OPENSSL_NO_PADDING, $iv);
        $decrypt_str = $this->stripPKSC7Padding($decrypt_str);
        return $decrypt_str;
    }

    /**
     * 填充算法
     * @param string $source
     * @return string
     */
    public function addPKCS7Padding($source)
    {
        $source = trim($source);
        $block = 16;

        $pad = $block - (strlen($source) % $block);
        if ($pad <= $block) {
            $char = chr($pad);
            $source .= str_repeat($char, $pad);
        }
        return $source;
    }

    /**
     * 移去填充算法
     * @param string $source
     * @return string
     */
    public function stripPKSC7Padding($source)
    {
        $char = substr($source, -1);
        $num = ord($char);
        if ($num == 62) return $source;
        $source = substr($source, 0, -$num);
        return $source;
    }

    /**
     * $HTTPVerb：       请求方式：GET / POST
     * $params：         POST请求的参数
     */
    public function doRequest($HTTPVerb, $params, $url, $header = [], $contentType = 'application/json', $deSign = true) {
        $curl    = curl_init($url);
        $payload = "";
        if($HTTPVerb == "POST"){
            if ($contentType == 'application/x-www-form-urlencoded') {
                $payload = http_build_query($params);
            } else {
                $payload = json_encode($params, true);
            }
        }
        $payload  = $this->encrypt($payload, $this->aesStr);//加密报文体
        $headers  = array("Content-Type: " . $contentType);
        if ($header) {
            foreach ($header as $k => $v) {
                $headers[] = $k . ': ' . $v;
            }
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        if($HTTPVerb == "POST") {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
        }
        curl_setopt($curl, CURLOPT_POST, 1);
        if ($deSign) { //是否需要解密
            curl_setopt($curl, CURLINFO_HEADER_OUT, TRUE); //CURLINFO_HEADER_OUT选项可以拿到请求头信息
            curl_setopt($curl, CURLOPT_HEADER, true);
        }
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        $curl_return = curl_exec($curl);
        if (curl_errno($curl)) {
            echo 'Error'.curl_error($curl);//捕抓异常
        }
        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        curl_close($curl);
        if ($deSign) {
            return $this->deSign($curl_return, $headerSize);
        } else {
            return $curl_return;
        }
    }

    //解密
    public function deSign($curl_return, $headerSize) {
        $rspHead = substr($curl_return, 0, $headerSize);
        $rspbody = substr($curl_return, $headerSize);
        if (strpos($rspbody, "uop_code") !== false) {
            $rspbody = json_decode($rspbody, true);
            if (isset($rspbody['uop_code']) && $rspbody['uop_code'] != 'UC000000') {
                throw new Exception($rspbody['uop_msg']);
            }
        }
        $rspHeads = explode("\r\n", $rspHead);
        $X_Uop_Unique_No = '';
        $X_Uop_Signature = '';
        $X_Uop_Encrypted_Key = '';
        foreach ($rspHeads as $loop) {
            if (strpos($loop, "X-Uop-Unique-No") !== false) {
                $X_Uop_Unique_No = trim(substr($loop, strlen("X-Uop-Unique-No")+2));
            }
            if (strpos($loop, "X-Uop-Signature") !== false) {
                $X_Uop_Signature = trim(substr($loop, strlen("X-Uop-Signature")+2));
            }
            if (strpos($loop, "X-Uop-Encrypted-Key") !== false) {
                $X_Uop_Encrypted_Key = trim(substr($loop, strlen("X-Uop-Encrypted-Key")+2));
            }
        }
        //私钥解密
        $rsaPriCertPath = $this->prikey;// 私钥证书路径
        $pwd = $this->pay_config['pay_ebankpay_prikey_pass'];// 私钥证书密码
        openssl_pkcs12_read(file_get_contents($rsaPriCertPath), $cert, $pwd);// 读取证书内容
        $RSA_PRIVATE = $cert['pkey'];//私钥
        $private_key = openssl_pkey_get_private($RSA_PRIVATE);
        if(!$private_key){
            throw new Exception('私钥不可用');
        }
        $return_de = openssl_private_decrypt(base64_decode($X_Uop_Encrypted_Key), $decrypted, $private_key);
        if(!$return_de){
            throw new Exception('解密失败,请检查RSA秘钥');
        }
        $body = $this->decrypt($rspbody, base64_encode($decrypted));
        return $body;
    }
}