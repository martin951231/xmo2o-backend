<?php

/**
 * 商家业务专用的通联微信小程序收银台。
 *
 * 与社区收费缴费的 v20 AllinpayService 完全独立。当前仅开放预约业务；
 * 后续商铺等商家业务只需扩展 BUSINESS_TYPES，并在对应旧支付完成回调中接入。
 */
class MerchantAllinpayWxapp
{
    // 前端支付通道标识；支付记录表 pay_type 只有 varchar(20)，不能直接入库。
    const PAY_TYPE = 'merchant_allinpay_wxapp';
    const STORAGE_PAY_TYPE = 'mer_allinpay_wxapp';
    // 已产生支付单曾被 MySQL 从 PAY_TYPE 截断成这个值，保留用于历史订单补单。
    const LEGACY_STORAGE_PAY_TYPE = 'merchant_allinpay_wx';

    private static $businessTypes = array('appoint', 'balance-appoint');

    private $config = array();

    public function __construct($config = array(), $storeId = 0)
    {
        $this->config = $config ?: self::getConfig($storeId);
    }

    public static function supportsBusiness($businessType)
    {
        return in_array($businessType, self::$businessTypes, true);
    }

    public static function getPaymentPayTypes()
    {
        return array(self::STORAGE_PAY_TYPE, self::LEGACY_STORAGE_PAY_TYPE, self::PAY_TYPE);
    }

    public static function isEnabled($businessType, $storeId)
    {
        $storeId = intval($storeId);
        if (!self::supportsBusiness($businessType) || !$storeId) {
            return false;
        }
        $config = self::getConfig($storeId);
        return self::isOpenStatus($config['status'])
            && trim((string)$config['appid']) !== ''
            && trim((string)$config['cusid']) !== ''
            && trim((string)$config['private_key']) !== '';
    }

    public static function getConfig($storeId)
    {
        $storeId = intval($storeId);
        $values = $storeId
            ? M('Merchant_store_allinpay_config')->where(array('store_id' => $storeId))->find()
            : array();
        return array(
            'store_id' => $storeId,
            'mer_id' => isset($values['mer_id']) ? intval($values['mer_id']) : 0,
            'status' => isset($values['allinpay_status']) ? $values['allinpay_status'] : 0,
            'appid' => isset($values['allinpay_appid']) ? $values['allinpay_appid'] : '',
            'cusid' => isset($values['allinpay_cusid']) ? $values['allinpay_cusid'] : '',
            'private_key' => isset($values['pay_allinpay_private_key']) ? $values['pay_allinpay_private_key'] : '',
        );
    }

    public static function getDiagnostic($businessType, $storeId)
    {
        $config = self::getConfig($storeId);
        return array(
            'code_version' => 'merchant_allinpay_20260907_1',
            'store_id' => intval($storeId),
            'business_supported' => self::supportsBusiness($businessType) ? 1 : 0,
            'status_value' => (string)$config['status'],
            'status_open' => self::isOpenStatus($config['status']) ? 1 : 0,
            'appid_configured' => trim((string)$config['appid']) !== '' ? 1 : 0,
            'cusid_configured' => trim((string)$config['cusid']) !== '' ? 1 : 0,
            'private_key_configured' => trim((string)$config['private_key']) !== '' ? 1 : 0,
            'enabled' => self::supportsBusiness($businessType)
                && self::isOpenStatus($config['status'])
                && trim((string)$config['appid']) !== ''
                && trim((string)$config['cusid']) !== ''
                && trim((string)$config['private_key']) !== '' ? 1 : 0,
        );
    }

    private static function isOpenStatus($status)
    {
        return in_array(strtolower(trim((string)$status)), array('1', 'on', 'true', 'open', 'enabled'), true);
    }

    public function pay($orderNo, $money, $title = '', $isH5 = false)
    {
        if (!self::isOpenStatus($this->config['status'])) {
            throw new Exception('商家通联支付未开启');
        }
        if (!$orderNo || $money <= 0) {
            throw new Exception('商家通联支付订单号或金额错误');
        }
        foreach (array('appid', 'cusid', 'private_key') as $field) {
            if (trim((string)$this->config[$field]) === '') {
                throw new Exception('商家通联支付配置缺失：' . $field);
            }
        }

        $params = array(
            'cusid' => $this->config['cusid'],
            'appid' => $this->config['appid'],
            'version' => '12',
            'trxamt' => (string)round($money * 100),
            'reqsn' => (string)$orderNo,
            // 支付通知使用商家通联独立入口，避免 appapi 聚合运行时缓存影响异步回调。
            'notify_url' => C('config.site_url') . '/source/merchant_allinpay_notify.php',
            'body' => $title ?: '预约服务订单支付',
            'remark' => (string)$orderNo,
            'validtime' => '5',
            'paytype' => 'W06',
            'randomstr' => $this->randomStr(),
            'signtype' => 'RSA',
        );
        if ($isH5) {
            $siteUrl = rtrim(C('config.site_url'), '/');
            if (stripos($siteUrl, 'https://') !== 0) {
                throw new Exception('通联 H5 支付要求站点地址配置为 HTTPS');
            }
            unset($params['paytype']);
            $params['charset'] = 'UTF-8';
            $params['returl'] = $siteUrl . '/source/allinpay_h5_return.php';
            $params['cancelurl'] = $siteUrl . '/source/allinpay_h5_cancel.php';
            $params['sign'] = $this->sign($params);
            return array('type' => 'allinpay_h5', 'info' => array(
                'url' => 'https://syb.allinpay.com/apiweb/h5unionpay/unionorder',
                'params' => $params,
            ));
        }
        $params['sign'] = $this->sign($params);

        return array(
            'type' => self::PAY_TYPE,
            'info' => $params,
        );
    }

    /**
     * 主动查询通联交易结果。
     * 用于收银台返回但异步通知尚未到达或通知丢失的兜底场景。
     */
    public function query($orderNo)
    {
        if (!$orderNo) {
            throw new Exception('商家通联支付查询订单号不能为空');
        }
        foreach (array('appid', 'cusid', 'private_key') as $field) {
            if (trim((string)$this->config[$field]) === '') {
                throw new Exception('商家通联支付配置缺失：' . $field);
            }
        }

        $params = array(
            'cusid' => trim((string)$this->config['cusid']),
            'appid' => trim((string)$this->config['appid']),
            'version' => '12',
            'reqsn' => (string)$orderNo,
            'randomstr' => $this->randomStr(),
            'signtype' => 'RSA',
        );
        $params['sign'] = $this->sign($params);

        $queryUrl = C('config.merchant_allinpay_query_url');
        if (!$queryUrl) {
            $queryUrl = 'https://vsp.allinpay.com/apiweb/tranx/query';
        }
        $response = $this->postForm($queryUrl, $params);
        $result = json_decode($response, true);
        fdump_sql(array(
            'order_no' => $orderNo,
            'response' => $result ?: $response,
        ), 'merchant_allinpay_query');
        if (!is_array($result)) {
            throw new Exception('商家通联支付查询响应异常');
        }
        return $result;
    }

    /**
     * 旧版 Http::curlPostOwn 强制 TLSv1 且数组会按 multipart 提交，
     * 通联查询接口改用标准 application/x-www-form-urlencoded，并让 cURL 自动协商 TLS。
     */
    private function postForm($url, $params)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($params, '', '&'),
            CURLOPT_HTTPHEADER => array('Content-Type: application/x-www-form-urlencoded; charset=UTF-8'),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ));
        if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
            curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        }
        $response = curl_exec($curl);
        $error = curl_error($curl);
        $errno = curl_errno($curl);
        curl_close($curl);
        if ($response === false) {
            throw new Exception('商家通联支付查询请求失败(' . $errno . ')：' . $error);
        }
        return $response;
    }

    private function sign($params)
    {
        unset($params['sign']);
        $params = array_filter($params, function ($value) {
            return $value !== '' && $value !== null;
        });
        ksort($params);
        $signText = urldecode(http_build_query($params));
        $privateKey = $this->loadPrivateKey($this->config['private_key']);
        if (!@openssl_sign($signText, $signature, $privateKey, OPENSSL_ALGO_SHA1)) {
            throw new Exception('商家通联支付签名失败：' . $this->getOpenSslError());
        }
        return base64_encode($signature);
    }

    private function loadPrivateKey($key)
    {
        $key = trim((string)$key);
        if (strlen($key) > 1 && $key[0] === '"' && substr($key, -1) === '"') {
            $decoded = json_decode($key, true);
            if (is_string($decoded) && $decoded !== '') {
                $key = $decoded;
            }
        }
        $key = str_replace(array('\\r\\n', '\\n', '\\r'), "\n", $key);
        $key = str_replace(array("\r\n", "\r"), "\n", $key);
        $key = preg_replace('/^\xEF\xBB\xBF/', '', $key);

        $candidates = array();
        if (strpos($key, '-----BEGIN') !== false) {
            $candidates[] = $key;
        } else {
            $body = preg_replace('/\s+/', '', $key);
            if ($body === '' || base64_decode($body, true) === false) {
                throw new Exception('商家通联支付私钥格式错误');
            }
            $candidates[] = "-----BEGIN PRIVATE KEY-----\n" . chunk_split($body, 64, "\n") . "-----END PRIVATE KEY-----";
            $candidates[] = "-----BEGIN RSA PRIVATE KEY-----\n" . chunk_split($body, 64, "\n") . "-----END RSA PRIVATE KEY-----";
        }

        foreach ($candidates as $candidate) {
            while (openssl_error_string() !== false) {
            }
            $privateKey = @openssl_pkey_get_private($candidate);
            if ($privateKey !== false) {
                return $privateKey;
            }
        }
        throw new Exception('商家通联支付私钥无法解析：' . $this->getOpenSslError());
    }

    private function getOpenSslError()
    {
        $errors = array();
        while (($error = openssl_error_string()) !== false) {
            $errors[] = $error;
        }
        return $errors ? end($errors) : '未知错误';
    }

    private function randomStr($length = 16)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $result;
    }
}
