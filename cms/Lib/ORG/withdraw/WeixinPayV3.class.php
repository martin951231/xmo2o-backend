<?php


class WeixinPayV3
{
    protected $mchid;
    protected $appid;
    protected $appkey;
    protected $appkeyv3;
    protected $serialNumber;
    protected $base_url;
    protected $wechat_name;
    protected $privateKeyPath;
    protected $publicKeyPath;
    protected $auth;
    protected $serialNumberV3;
    protected $requestMethod;

    const ERROR_CODE_MSG = [
        'NO_AUTH'               => '商户信息不合法',
        'SYSTEM_ERROR'          => '系统错误',
        'NOT_ENOUGH'            => '资金不足',
        'PARAM_ERROR'           => '参数错误',
        'INVALID_REQUEST'       => '请求参数符合参数格式，但不符合业务规则',
        'ACCOUNTERROR'          => '商户账户付款受限',
        'QUOTA_EXCEED'          => '超出商户单日转账额度',
        'FREQUENCY_LIMITED'     => '频率超限',
        'NOT_FOUND'             => '记录不存在',
        'APPID_MCHID_NOT_MATCH' => '商户号和appid没有绑定关系',
    ];

    public function __construct()
    {
        $this->mchid          = C('config.withdraw_weixin_mchid'); // 商户号
        $this->appid          = C('config.withdraw_weixin_appid'); // 商户账号appid
        $this->appkey         = C('config.withdraw_weixin_appkey'); // 商户账号appkey
        $this->appkeyv3       = C('config.withdraw_weixin_apiv3'); // 商户账号v3秘钥
        $this->serialNumber   = C('config.withdraw_serial_no'); // 商户证书序列号
        $this->base_url       = 'https://api.mch.weixin.qq.com';
        $this->wechat_name    = C('config.wechat_name');//公众号名称
        $this->privateKeyPath = getcwd().C('config.withdraw_weixin_client_key');
        $this->publicKeyPath  = dirname(getcwd().C('config.withdraw_weixin_client_key')).'/certificates.pem';
    }

    /*
    * 提现到微信余额
    * */
    public function withdraw($data)
    {
        if(C('config.withdraw_weixin_apiv3_transfer_scene_id')) {
            return $this->withdrawalTransfer($data);
        }
        $originData = $data;
        static $withdrawNum = 1;

        $reqUrl = $this->base_url . '/v3/transfer/batches';

        $this->requestMethod = 'POST';
        if (
            empty($this->appid)
            || empty($this->mchid)
            || empty($this->appkey)
            || empty(C('config.withdraw_weixin_client_cert'))
            || empty(C('config.withdraw_weixin_client_key'))
            || empty(C('config.withdraw_weixin_apiv3'))
        ) {
            $result['result']['err_code_des'] = '暂未配置微信提现支付信息，请至系统设置--付款管理--微信提现tab页中配置';
            return $result;
        }
        try{
            $this->certificates();
            $postData = [
                "appid"                => $this->appid,//直连商户的appid
                "out_batch_no"         => $data['partner_trade_no'],//商家批次单号
                "batch_name"           => mb_substr($data['withdraw_name'],0,20) ?: '提现到微信',//批次名称
                "batch_remark"         => '微信提现' . ($data['money'] / 100) . '元（' . $this->wechat_name . '）',//批次备注
                "total_amount"         => intval($data['money']),//转账总金额
                "total_num"            => 1,//转账总笔数
                "transfer_detail_list" => [//转账明细列表
                    [
                        "out_detail_no"   => $data['partner_trade_no'],//商家明细单号
                        "transfer_amount" => intval($data['money']),//转账金额
                        "transfer_remark" => '微信提现' . ($data['money'] / 100) . '元（' . $this->wechat_name . '）',//转账备注
                        "openid"          => $data['openid'],//用户在直连商户应用下的用户标示
//                        "user_name"       => $this->getEncrypt(trim($data['name']))//收款用户姓名
                    ]
                ]
            ];
            if(bcdiv($data['money'],'100', 2) > 1){
                $postData['transfer_detail_list'][0]['user_name'] = $this->getEncrypt(trim($data['name']));//收款用户姓名 1元以下不实名校验
            }
            fdump_api(['微信余额请求1' => [$reqUrl,$postData,$data]], 'pay/WeixinPayV3',true);
            $this->getAuthStr($reqUrl, $postData);
            $response = $this->curlPost($reqUrl, $postData);
            $data     = json_decode($response, true);

            fdump_api(['微信余额请求2' => [
                $reqUrl,$postData,$data,$this->serialNumber,$this->publicKeyPath,@file_get_contents($this->publicKeyPath)
            ]], 'pay/WeixinPayV3',true);

            if (empty($data['batch_id'])) {

                if($withdrawNum < 2){//重试1次
                    $withdrawNum++;
                    sleep(1);
                    return $this->withdraw($originData);
                }
                return $this->dealCheckResult($data, $postData);
            }
            $data = $this->checkWithdraw($data['batch_id']);

            return $this->dealCheckResult($data, $postData);
        }catch (\Exception $e){
            fdump_api(['微信支付异常' => [$e->getMessage(),$e->getTraceAsString(),$e->getLine()]], 'pay/WeixinPayV3',true);
            $result['result']                 = $data;
            $result['post_data']              = $postData;
            $result['error']                  = true;
            $result['result_code']            = 'FAILURE';
            $result['result']['err_code_des'] = L_($e->getMessage());
            return $result;
        }
    }

    private function dealCheckResult($data, $postData)
    {
        $result['result']    = $data;
        $result['post_data'] = $postData;

        $result['error'] = false;
        if (!empty($data['code']) || (!empty($data['state'] && !in_array($data['state'], ['ACCEPTED', 'PROCESSING', 'WAIT_USER_CONFIRM', 'TRANSFERING'])))) {
            $result['error']                  = true;
            $result['result_code']            = 'FAILURE';
            $result['result']['err_code_des'] = !empty($data['message']) ? $data['message'] :(L_(self::ERROR_CODE_MSG[strtoupper($data['code'] ?? $data['state'])] ?? '请求异常！'));
        } else {
            $result['result_code'] = 'SUCCESS';
        }

        return $result;
    }
	public function getScenePostData($sceneId, $data): array
	{
		switch ($sceneId) {
			case '1005'://佣金报酬
				$postData = [//转账场景报备信息
					[
						"info_type" => '岗位类型',//信息类型
						"info_content" => $data['job_type'] ?? '商家',//信息内容
					],
					[
						"info_type" => '报酬说明',//信息类型
						"info_content" => $data['remark'] ?? '申请发放' . ($data['money'] / 100) . '元（' . $this->wechat_name . '）',//信息内容
					]
				];
				break;
			case '1010'://二手回收
				$postData =  [//转账场景报备信息
					[
						"info_type" => '回收商品名称',//信息类型
						"info_content" => '商品回收打款',//信息内容
					],
				];
				break;
			default:
				$postData = [];
				break;
		}
		return $postData;
				
	}
	

    //文档：https://pay.weixin.qq.com/doc/v3/merchant/4012716434
    public function withdrawalTransfer($data): array
    {
        $originData = $data;
        static $withdrawNum = 1;

        $reqUrl = $this->base_url . '/v3/fund-app/mch-transfer/transfer-bills';
        $postData = [];
        $this->requestMethod = 'POST';
	    try {
		    if (
			    empty($this->appid)
			    || empty($this->mchid)
			    || empty($this->appkey)
			    || empty(C('config.withdraw_weixin_client_cert'))
			    || empty(C('config.withdraw_weixin_client_key'))
			    || empty(C('config.withdraw_weixin_apiv3'))
		    ) {
			    throw new \Exception('暂未配置微信提现支付信息，请至系统设置--付款管理--微信提现tab页中配置');
		    }
		    $sceneId = C('config.withdraw_weixin_apiv3_transfer_scene_id');
		    if (empty($sceneId)) {
			    throw new \Exception('暂未配置微信提现场景ID，请至系统设置中配置');
		    }
		    $scenePostData = $this->getScenePostData($sceneId, $data);
		    if (empty($scenePostData)) {
			    throw new \Exception('提现场景ID暂未适配');
		    }
	    } catch (\Exception $e) {
		    $result['error'] = true;
		    $result['result_code'] = 'FAILURE';
		    $result['result']['err_code_des'] = $e->getMessage();
		    return $result;
	    }
        try {
            $this->certificates();
            $postData = [
                "appid" => $this->appid,//直连商户的appid
                "out_bill_no" => $data['partner_trade_no'],//商户单号
                "transfer_scene_id" => $sceneId,//转账场景ID
                "openid" => $data['openid'],//收款用户OpenID
                "transfer_amount" => intval($data['money']),//转账金额
                "transfer_remark" => '微信提现' . ($data['money'] / 100) . '元（' . $this->wechat_name . '）',//转账备注
                'notify_url' => C('config.site_url') . '/source/weixin_withdraw_notify.php',//回调地址
                "transfer_scene_report_infos" => $scenePostData,//转账场景报备信息
            ];
            if (bcdiv($data['money'], '100', 2) > 1) {
                $postData['user_name'] = $this->getEncrypt(trim($data['name']));//收款用户姓名 1元以下不实名校验
            }
            fdump_api(['withdrawalTransfer微信余额请求1' => [$reqUrl, $postData, $data]], 'pay/WeixinPayV3', true);
            $this->getAuthStr($reqUrl, $postData);
            $response = $this->curlPost($reqUrl, $postData);
            $data = json_decode($response, true);
            fdump_api(['withdrawalTransfer微信余额请求2' => [
                $reqUrl, $postData, $data, $this->serialNumber, $this->publicKeyPath, @file_get_contents($this->publicKeyPath)
            ]], 'pay/WeixinPayV3', true);
            
            $this->saveRequestInfo($postData, $data, $originData);

            if (empty($data['transfer_bill_no'])) {
                if ($withdrawNum < 2) {//重试1次
                    $withdrawNum++;
                    sleep(1);
                    return $this->withdrawalTransfer($originData);
                }
                return $this->dealCheckResult($data, $postData);
            }
            if (in_array($data['state'], ['ACCEPTED', 'PROCESSING', 'WAIT_USER_CONFIRM', 'TRANSFERING'])) {
                $this->sendWithdrawTemplate($postData['openid'], [
                    'out_bill_no' => $postData['out_bill_no'],
                    'withdraw_name' => $postData['transfer_remark'],
                    'withdraw_money' => $postData['transfer_amount'],
                ]);
            }
            return $this->dealCheckResult($data, $postData);
        } catch (\Exception $e) {
            fdump_api(['withdrawalTransfer微信支付异常' => [$e->getMessage(), $e->getTraceAsString(), $e->getLine()]], 'pay/WeixinPayV3', true);
            $result['result'] = $data;
            $result['post_data'] = $postData;
            $result['error'] = true;
            $result['result_code'] = 'FAILURE';
            $result['result']['err_code_des'] = L_($e->getMessage());
            return $result;
        }
    }
    private function saveRequestInfo($postData, $data, $originData)
    {
        if(empty($postData['out_bill_no'])) {
            return false;
        }
        $withdraw = M('Withdraw_request_info')->where(['out_bill_no' => $postData['out_bill_no']])->find();
        $info = [
            'withdraw_id' => $originData['withdraw_id'] ?? 0,
            'withdraw_type' => $originData['withdraw_type'] ?? '',
            'weixin_mchid' => $this->mchid,
            'weixin_appid' => $this->appid,
            'openid' => $postData['openid'],
            'withdraw_money' => $postData['transfer_amount']/100,
            'package_info' => $data['package_info'] ?? '',
            'request_data' => json_encode($postData, JSON_UNESCAPED_UNICODE),
            'response_data' => json_encode($data, JSON_UNESCAPED_UNICODE),
        ];
        if ($withdraw) {
            M('Withdraw_request_info')->where(['out_bill_no' => $postData['out_bill_no']])->save($info);
        } else {
            $info['out_bill_no'] = $postData['out_bill_no'];
            M('Withdraw_request_info')->add($info);
        }
    }
    public function sendWithdrawTemplate($openid, $withdraw)
    {
        $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
        $href = get_base_url().'pages/my/weChatTransferCash?out_bill_no='.$withdraw['out_bill_no'];
        $templateParams = [
            'href' => $href,
            'wecha_id' => $openid,
            'first' => L_('有一笔商家提现待领取，请尽快领取！'),
            'keyword1' => $withdraw['withdraw_name'],
            'keyword2' => date('Y-m-d H:i',time()),
            'keyword3' => C('config.Currency_symbol').bcdiv($withdraw['withdraw_money'],100,2),
            'remark' =>L_('请点击去领取'),
            'new_info' => [//新版本发送需要的信息
                'tempKey'=>'44716',//新模板号
                'thing12'=>L_('有一笔商家提现待领取，请尽快领取！'),//服务项目
                'phrase2'=>'商家提现',//订单类型
                'time13'=>date('Y-m-d H:i',time()),//服务时间
                'thing11'=>'无',//服务地址
            ],
        ];
        fdump_api(['sendWithdrawTemplate' => [$openid, $withdraw, $templateParams]], 'pay/WeixinPayV3', true);
        $model->sendTempMsg('OPENTM412319702', $templateParams);
    }
    public function checkWithdraw(string $batchId)
    {
        static $num = 1;
        //"need_query_detail" 是否查询转账明细单
        $reqUrl = $this->base_url . "/v3/transfer/batches/batch-id/{$batchId}?need_query_detail=false";

        $this->requestMethod = 'GET';
        $this->getAuthStr($reqUrl);
        $data = $this->curlGet($reqUrl);
        $data = json_decode($data, true);
        fdump_api(['微信余额请求3' => [$reqUrl,$batchId,$data]], 'pay/WeixinPayV3',true);
        if(!empty($data['code']) && $num <= 5){
            $num++;
            sleep(1);
            $this->checkWithdraw($batchId);
        }
        return $data;
    }
    public function curlGet($url, $timeout = 30)
    {
        if(isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['HTTP_USER_AGENT']){
            $UserAgent = $_SERVER['HTTP_USER_AGENT'];
        }else{
            $UserAgent = 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/63.0.3239.132 Safari/537.36';
        }
        $authorization =  [
            'Authorization:' . $this->auth,
            'Content-Type:application/json',
            'Accept:application/json',
            'User-Agent:' . $UserAgent,
            'Wechatpay-Serial:' . $this->serialNumber
        ];
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $authorization);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        //获得内容
        $data = curl_exec($ch);
        //关闭curl
        curl_close($ch);
        return $data;
    }

    public function curlPost($url = '', $postData = array(), $options = array())
    {
        if (is_array($postData)) {
            $postData = json_encode($postData);
        }
        $authorization =  [
            'Authorization:' . $this->auth,
            'Content-Type:application/json',
            'Accept:application/json',
            'User-Agent:' . $_SERVER['HTTP_USER_AGENT'],
            'Wechatpay-Serial:' . $this->serialNumberV3
        ];
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $authorization);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //设置cURL允许执行的最长秒数
        if (!empty($options)) {
            curl_setopt_array($ch, $options);
        }
        //https请求 不验证证书和host
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($ch);
        curl_close($ch);
        fdump_sql([$postData,$url,$data],'weixin_withdraw_v3');
        return $data;
    }

    private function getSchema()
    {
        return 'WECHATPAY2-SHA256-RSA2048';
    }

    public function getAuthStr($requestUrl, $reqParams = array())
    {
        $schema     = $this->getSchema();
        $token      = $this->getToken($requestUrl, $reqParams);
        $this->auth = $schema . ' ' . $token;
        return $this->auth;
    }

    private function getNonce()
    {
        static $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString     = '';
        for ($i = 0; $i < 32; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public function getToken($requestUrl, $reqParams = array())
    {
        $body      = $reqParams ? json_encode($reqParams) : '';
        $nonce     = $this->getNonce();
        $timestamp = time();
        $message   = $this->buildMessage($nonce, $timestamp, $requestUrl, $body);
        $sign      = $this->sign($message);
        $serialNo  = $this->serialNumber;
        return sprintf('mchid="%s",nonce_str="%s",timestamp="%d",serial_no="%s",signature="%s"',
            $this->mchid, $nonce, $timestamp, $serialNo, $sign
        );
    }

    private function buildMessage($nonce, $timestamp, $requestUrl, $body = '')
    {
        $method       = $this->requestMethod;
        $urlParts     = parse_url($requestUrl);
        $canonicalUrl = ($urlParts['path'] . (!empty($urlParts['query']) ? "?{$urlParts['query']}" : ""));
        return strtoupper($method) . "\n" .
            $canonicalUrl . "\n" .
            $timestamp . "\n" .
            $nonce . "\n" .
            $body . "\n";
    }

    private function sign($message)
    {
        if (!in_array('sha256WithRSAEncryption', openssl_get_md_methods(true))) {
            throw new \RuntimeException("当前PHP环境不支持SHA256withRSA");
        }
        $res = file_get_contents($this->privateKeyPath);
        fdump_api(['签名私钥' => [$this->privateKeyPath,$res]], 'pay/WeixinPayV3',true);
        if (!openssl_sign($message, $sign, $res, 'sha256WithRSAEncryption')) {
            throw new \UnexpectedValueException("签名验证过程发生了错误");
        }
        return base64_encode($sign);
    }

    private function getEncrypt($str)
    {
        //$str是待加密字符串
        $public_key = file_get_contents($this->publicKeyPath);
        $encrypted  = '';
        if (openssl_public_encrypt($str, $encrypted, $public_key, OPENSSL_PKCS1_OAEP_PADDING)) {
            //base64编码
            $sign = base64_encode($encrypted);
        } else {
            throw new Exception('encrypt failed');
        }
        return $sign;
    }

    /**
     * [certificates 保存获取的平台证书，且返回证书的序列号serial_no] 缓存一天时间
     * @return string serial_no证书的序列号
     */
    public function certificates()
    {
        //存在微信公钥证书则直接读取 (平台证书切换为微信支付公钥https://pay.weixin.qq.com/doc/v3/merchant/4012154180)
        if (C('config.withdraw_weixin_public_key')) {
            $this->publicKeyPath  = getcwd() . C('config.withdraw_weixin_public_key');
            $this->serialNumberV3 = C('config.withdraw_weixin_public_key_id');
            return true;
        }

        $cacheV3PayKey = 'wx_pay_v3_'.$this->appid;
        $serial_no = S($cacheV3PayKey);
        if(!empty($serial_no)){
            $this->serialNumberV3 = $serial_no;
            return true;
        }

        $reqUrl = $this->base_url . "/v3/certificates";
        $this->requestMethod = 'GET';
        $this->getAuthStr($reqUrl);
        $data = $this->curlGet($reqUrl);
        $data = json_decode($data, true);

        fdump_api(['微信平台证书请求' => [$reqUrl,$data]], 'pay/WeixinPayV3',true);
        isset($data['code']) && throw_exception($data['message']);

        $r = $data['data'];
        if (is_array($r)) {
            $certFileName = dirname(getcwd().C('config.withdraw_weixin_client_key')).'/certificates.pem';
            $associatedData = $r[0]['encrypt_certificate']['associated_data'];
            $nonceStr = $r[0]['encrypt_certificate']['nonce'];
            $ciphertext = $r[0]['encrypt_certificate']['ciphertext'];

            $data1 = self::decryptToString($associatedData, $nonceStr, $ciphertext);
            $serial_no = $r[0]['serial_no'];
            file_put_contents($certFileName, $data1);
            S($cacheV3PayKey, $serial_no, 10 * 3600);//缓存10小时
            $this->publicKeyPath  = $certFileName;
        }else{
            throw_exception('微信返回格式错误');
        }
        $this->serialNumberV3 = $serial_no;
        return true;
    }


    /**
     * [decryptToString V3 证书和回调报文解密]
     * @param string $aesKey V3签名
     * @param string $associatedData 附加数据包
     * @param string $nonceStr 加密使用的随机串初始化向量）
     * @param string $ciphertext Base64编码后的密文
     *
     * @return string|bool      Decrypted string on success or FALSE on failure
     */
    public function decryptToString($associatedData, $nonceStr, $ciphertext)
    {
        $aesKey = $this->appkeyv3;
        if (strlen($aesKey) != 32) {
            throw new Exception('无效的ApiV3Key，长度应为32个字节');
        }

        $ciphertext = \base64_decode($ciphertext);
        if (strlen($ciphertext) <= 16) {
            throw new Exception('无效的数据密文，长度应为16个字节以上');
        }

        if (
            function_exists('\sodium_crypto_aead_aes256gcm_is_available') &&
            \sodium_crypto_aead_aes256gcm_is_available()
        ) {
            return \sodium_crypto_aead_aes256gcm_decrypt($ciphertext, $associatedData, $nonceStr, $aesKey);
        }

        // ext-libsodium (need install libsodium-php 1.x via pecl)
        if (
            function_exists('\Sodium\crypto_aead_aes256gcm_is_available') &&
            \Sodium\crypto_aead_aes256gcm_is_available()
        ) {
            return \Sodium\crypto_aead_aes256gcm_decrypt($ciphertext, $associatedData, $nonceStr, $aesKey);
        }

        // openssl (PHP >= 7.1 support AEAD)
        if (PHP_VERSION_ID >= 70100 && in_array('aes-256-gcm', \openssl_get_cipher_methods())) {
            $ctext = substr($ciphertext, 0, -16);
            $authTag = substr($ciphertext, -16);

            return \openssl_decrypt(
                $ctext,
                'aes-256-gcm',
                $aesKey,
                \OPENSSL_RAW_DATA,
                $nonceStr,
                $authTag,
                $associatedData
            );
        }

        throw new Exception('AEAD_AES_256_GCM需要PHP 7.1以上或者安装libsodium-php');
    }

    /**
     * 获取转账信息，先查询转账状态，如果是失败，在查询转账明细查找失败原因
     */
    public function checkWithdrawApplyInfo($batchId)
    {
        $withdraw_info = $this->checkWithdrawAll($batchId);
        if(isset($withdraw_info['code']) && $withdraw_info['message']){
            return ['error'=>1,'msg'=>$withdraw_info['message']];
        }
        if(isset($withdraw_info['transfer_detail_list'][0]['detail_id'])){
            $fail_reason = '';//失败原因
            if($withdraw_info['transfer_detail_list'][0]['detail_status']=='SUCCESS'){
                $apply_status = 1;//提现成功
            }elseif($withdraw_info['transfer_detail_list'][0]['detail_status']=='FAIL'){
                $apply_status = 20;//提现失败
            }else{
                $apply_status = 10;//处理中
            }
            if($apply_status == 20){//提现失败，需要查询失败原因
                $withdraw_detail = $this->checkWithdrawDetail($batchId,$withdraw_info['transfer_detail_list'][0]['detail_id']);
                if(isset($withdraw_detail['code']) && $withdraw_detail['message']){
                    return ['error'=>1,'msg'=>$withdraw_info['message']];
                }
                $fail_reason = $withdraw_detail['fail_reason'] ?? '原因未知，请于微信商户后台查看具体失败原因';
            }
            return ['error'=>0,'apply_status'=>$apply_status,'fail_reason'=>$fail_reason,'result'=>['post_data'=>'','result'=>$withdraw_info]];
        }else{
            return ['error'=>1,'msg'=>'未查询到转账明细'];
        }
    }

    /**
     * @author Nd
     * @date 2023/2/2
     * 查询微信转账状态
     * @param $batchId
     * @param $detailId
     * @return mixed
     */
    public function checkWithdrawAll($batchId)
    {
        try {
            $reqUrl = $this->base_url . "/v3/transfer/batches/batch-id/{$batchId}?need_query_detail=true&offset=0&limit=20&detail_status=ALL";
            $this->requestMethod = 'GET';
            $this->getAuthStr($reqUrl);
            $data = $this->curlGet($reqUrl);
            $data = json_decode($data, true);
            fdump_api(['查询微信转账状态' => [$reqUrl,$batchId,$data]], 'pay/WeixinPayV3Detail',true);
        }catch (\Exception $e){
            return ['code'=>1,'message'=>$e->getMessage()];
        }
        return $data;
    }
    
    /**
     * @author Nd
     * @date 2023/2/2
     * 查询微信转账明细
     * @param $batchId
     * @param $detailId
     * @return mixed
     */
    public function checkWithdrawDetail($batchId,$detailId)
    {
        try {
            $reqUrl = $this->base_url . "/v3/transfer/batches/batch-id/{$batchId}/details/detail-id/{$detailId}";
            $this->requestMethod = 'GET';
            $this->getAuthStr($reqUrl);
            $data = $this->curlGet($reqUrl);
            $data = json_decode($data, true);
            fdump_api(['查询微信转账明细' => [$reqUrl,$batchId,$detailId,$data]], 'pay/WeixinPayV3Detail',true);
        }catch (\Exception $e){
            return ['code'=>1,'message'=>$e->getMessage()];
        }
        return $data;
    }
}
