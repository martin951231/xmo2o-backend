<?php
namespace app\social_activity\model\service;

class SocialActivityRefundGateway
{
    private $config;
    private $publicKey;

    public function __construct(array $originalConfig)
    {
        $this->config = $originalConfig;
        $public = $originalConfig['platform_public_key'] ?? ($originalConfig['public_key'] ?? '');
        if (!$public && function_exists('cfg')) {
            $public = cfg('allinpay_public_key') ?: cfg('pay_allinpay_public_key');
        }
        if (!$public && function_exists('config')) { $public = config('social_activity_refund.public_key'); }
        $this->publicKey = $this->pem($public, 'PUBLIC KEY');
    }

    private function pem($key, $type)
    {
        $key = str_replace(['\\r\\n', '\\n', '\\r'], "\n", trim((string)$key));
        if (strpos($key, '-----BEGIN') === false && $key !== '') {
            $key = '-----BEGIN ' . $type . "-----\n" . chunk_split(preg_replace('/\s+/', '', $key), 64, "\n") . '-----END ' . $type . '-----';
        }
        return $key;
    }

    public function assertReady()
    {
        foreach (['cusid', 'appid', 'private_key'] as $field) {
            if (empty($this->config[$field])) {
                throw new \RuntimeException('原支付单缺少通联商户配置，请联系管理员');
            }
        }
        if ($this->publicKey !== '' && !openssl_pkey_get_public($this->publicKey)) {
            throw new \RuntimeException('已有通联平台验签公钥格式无效，请检查现有配置');
        }
        if (!openssl_pkey_get_private($this->pem($this->config['private_key'], 'PRIVATE KEY'))) {
            throw new \RuntimeException('原支付单通联私钥无效，请联系管理员');
        }
    }

    public static function canonical(array $params)
    {
        unset($params['sign']);
        $params = array_filter($params, function ($v) { return $v !== '' && $v !== null; });
        ksort($params, SORT_STRING);
        $parts = [];
        foreach ($params as $k => $v) {
            if (!is_scalar($v)) { throw new \RuntimeException('通联报文格式异常'); }
            $parts[] = $k . '=' . $v;
        }
        return implode('&', $parts);
    }

    public function verify(array $data)
    {
        $sign = base64_decode($data['sign'] ?? '', true);
        if (!$sign || openssl_verify(self::canonical($data), $sign, $this->publicKey, OPENSSL_ALGO_SHA1) !== 1) {
            throw new \RuntimeException('通联退款报文验签失败');
        }
        $this->verifyMerchant($data);
    }

    private function verifyMerchant(array $data)
    {
        if ((string)($data['cusid'] ?? '') !== (string)$this->config['cusid'] ||
            (string)($data['appid'] ?? '') !== (string)$this->config['appid']) {
            throw new \RuntimeException('通联退款商户不匹配');
        }
    }

    public function verifyNotification(array $data)
    {
        // 无论是否有验签公钥，通知均不作为入账依据，仅触发服务端HTTPS查单。
        if ($this->publicKey !== '') { $this->verify($data); }
        else { $this->verifyMerchant($data); }
    }

    public function submit(array $refund)
    {
        $params = ['reqsn' => $refund['refund_no'], 'trxamt' => (string)$refund['online_amount'],
            'oldreqsn' => $refund['pay_order_no'], 'reason' => '用户申请活动整单退款'];
        if ($refund['original_trxid']) { $params['oldtrxid'] = $refund['original_trxid']; }
        $url = (string)config('social_activity_refund.notify_url');
        if ($url !== '') {
            $parts = parse_url($url);
            if (!$parts || ($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) || isset($parts['query']) || isset($parts['fragment']) || (isset($parts['port']) && $parts['port'] != 443)) {
                throw new \RuntimeException('退款通知地址必须是无参数的HTTPS地址，端口443');
            }
            $params['notify_url'] = $url;
        }
        return $this->request('refund', $params);
    }

    public function query(array $refund)
    {
        // 始终按本次退款单号查询，绝不能查询原支付单并把支付成功误判为退款成功。
        return $this->request('query', ['reqsn' => $refund['refund_no']]);
    }

    protected function request($action, array $params)
    {
        $this->assertReady();
        $params += ['cusid' => $this->config['cusid'], 'appid' => $this->config['appid'],
            'version' => $this->config['version'] ?? '11', 'randomstr' => bin2hex(random_bytes(16)), 'signtype' => 'RSA'];
        if (!empty($this->config['orgid'])) { $params['orgid'] = $this->config['orgid']; }
        if (!openssl_sign(self::canonical($params), $signature, $this->pem($this->config['private_key'], 'PRIVATE KEY'), OPENSSL_ALGO_SHA1)) {
            throw new \RuntimeException('通联退款签名失败');
        }
        $params['sign'] = base64_encode($signature);
        $base = config('social_activity_refund.test') ? 'https://syb-test.allinpay.com' : 'https://vsp.allinpay.com';
        $curl = curl_init($base . '/apiweb/tranx/' . $action);
        curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => http_build_query($params),
            CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded']]);
        $body = curl_exec($curl);
        $http = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return $this->parseHttpsResponse($body, $http, $params['reqsn']);
    }

    // 仅用于上面的固定通联地址、证书和主机名校验均开启的HTTPS响应。
    // 不得传入用户请求或回调报文。
    protected function parseHttpsResponse($body, $http, $requestNo)
    {
        if ($body === false || $http !== 200) { throw new \RuntimeException('通联退款通信异常，等待查询确认'); }
        $data = json_decode($body, true);
        if (!is_array($data)) { throw new \RuntimeException('通联退款响应格式异常，等待查询确认'); }
        if (($data['retcode'] ?? '') !== 'SUCCESS') { throw new \RuntimeException('通联通信未成功：' . ($data['retmsg'] ?? '等待查询确认')); }
        if ($this->publicKey !== '') { $this->verify($data); }
        else { $this->verifyMerchant($data); }
        if ((string)($data['reqsn'] ?? '') !== (string)$requestNo) { throw new \RuntimeException('通联退款单号不匹配'); }
        return $data;
    }
}
