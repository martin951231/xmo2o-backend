<?php

namespace app\pay\model\service\channel;

class AllinpayService
{
    private $agent = '';
    private $config = [];

    public function __construct($agent = '', $config = [])
    {
        $this->agent = $agent;
        $this->config = $config;
    }

    public function pay($order_no, $money, $extra_cfg = [])
    {
        if (!in_array($this->agent, ['wechat_mini', 'h5', 'wechat_h5', 'alipay'], true)) {
            throw new \think\Exception('通联小程序收银台仅支持微信小程序端');
        }
        if (empty($order_no) || empty($money)) {
            throw new \think\Exception('订单号或支付金额不能为空');
        }

        $params = [
            'cusid' => $this->config['cusid'],
            'appid' => $this->config['appid'],
            'version' => $this->config['version'] ?? '12',
            'trxamt' => (string)round($money * 100),
            'reqsn' => $order_no,
            'notify_url' => $this->config['notify_url'],
            'body' => $extra_cfg['title'] ?? ($extra_cfg['goods_desc'] ?? '订单支付'),
            'remark' => $extra_cfg['business_order_sn'] ?? $order_no,
            'validtime' => $this->config['validtime'] ?? '5',
            'paytype' => 'W06',
            'randomstr' => $this->randomStr(),
            'signtype' => 'RSA',
        ];
        if (!empty($this->config['orgid'])) {
            $params['orgid'] = $this->config['orgid'];
        }
        if (!empty($this->config['limit_pay'])) {
            $params['limit_pay'] = $this->config['limit_pay'];
        }

        if ($this->agent != 'wechat_mini') {
            $siteUrl = rtrim(cfg('site_url'), '/');
            if (stripos($siteUrl, 'https://') !== 0) {
                throw new \think\Exception('通联 H5 支付要求站点地址配置为 HTTPS');
            }
            unset($params['paytype']);
            $params['charset'] = 'UTF-8';
            $params['returl'] = $siteUrl . '/source/allinpay_h5_return.php';
            $params['cancelurl'] = $siteUrl . '/source/allinpay_h5_cancel.php';
            $params['sign'] = $this->sign($params);
            return ['type' => 'allinpay_h5', 'info' => [
                'url' => 'https://syb.allinpay.com/apiweb/h5unionpay/unionorder',
                'params' => $params,
            ]];
        }
        $params['sign'] = $this->sign($params);

        return [
            'type' => 'allinpay_wxapp',
            'info' => $params,
        ];
    }

    public function notice()
    {
        $params = request()->param();
        $input = file_get_contents('php://input');
        if (empty($params) && $input) {
            parse_str($input, $params);
            if (empty($params)) {
                $params = json_decode($input, true) ?: [];
            }
        }
        fdump_sql([$params, $input], 'notify_allinpay');

        $orderNo = $params['cusorderid'] ?? ($params['reqsn'] ?? ($params['out_trade_no'] ?? ''));
        $status = (string)($params['trxstatus'] ?? '');
        if (!$orderNo || $status !== '0000') {
            return ['error' => 1, 'msg' => '通联支付通知状态异常'];
        }

        return [
            'paid_money' => $params['trxamt'] ?? 0,
            'paid_time' => !empty($params['paytime']) ? strtotime($params['paytime']) : time(),
            'transaction_no' => $params['trxid'] ?? ($params['chnltrxid'] ?? ''),
        ];
    }

    /**
     * 通联异步通知到达前，结果页会轮询支付状态。
     * 返回空结果交由上层按“尚未支付”处理，避免调用不存在的方法导致异常。
     */
    public function query($orderNo)
    {
        return [];
    }

    /**
     * 通联支付退款仅用于系统内部登记，不调用通联线上退款接口。
     *
     * @param string $orderNo      本系统支付单号
     * @param int    $totalFee     原支付金额（分）
     * @param int    $refundMoney  退款金额（分）
     * @param mixed  $extra        支付扩展信息
     * @return array
     */
    public function refund($orderNo, $totalFee, $refundMoney, $extra = [])
    {
        if (empty($orderNo)) {
            throw new \think\Exception('支付单号不能为空');
        }
        if ($refundMoney <= 0) {
            throw new \think\Exception('退款金额必须大于0');
        }

        $refundNo = date('YmdHis') . random_int(1000, 9999);
        fdump_sql([
            'order_no' => $orderNo,
            'total_fee' => $totalFee,
            'refund_money' => $refundMoney,
            'extra' => $extra,
            'refund_no' => $refundNo,
            'record_only' => 1,
        ], 'allinpay_refund_record');

        return [
            'refund_no' => $refundNo,
            'record_only' => 1,
        ];
    }

    private function sign($params)
    {
        unset($params['sign']);
        $params = array_filter($params, function ($value) {
            return $value !== '' && $value !== null;
        });
        ksort($params);
        $signStr = urldecode(http_build_query($params));
        $privateKey = $this->loadPrivateKey($this->config['private_key'] ?? '');
        $result = @openssl_sign($signStr, $sign, $privateKey, OPENSSL_ALGO_SHA1);
        if (!$result) {
            throw new \think\Exception('通联支付签名失败：' . $this->getOpenSslError());
        }
        return base64_encode($sign);
    }

    /**
     * 兼容 PKCS#8、PKCS#1、无 PEM 头以及从表单保存的转义换行格式。
     */
    private function loadPrivateKey($key)
    {
        $key = trim((string)$key);
        if ($key === '') {
            throw new \think\Exception('通联支付私钥不能为空');
        }

        // 兼容误将 JSON 字符串（带首尾双引号）直接保存的情况。
        if (strlen($key) > 1 && $key[0] === '"' && substr($key, -1) === '"') {
            $decoded = json_decode($key, true);
            if (is_string($decoded) && $decoded !== '') {
                $key = $decoded;
            }
        }

        // 兼容数据库中保存为 "\\n" 的文本，以及 Windows 换行。
        $key = str_replace(['\\r\\n', '\\n', '\\r'], "\n", $key);
        $key = str_replace(["\r\n", "\r"], "\n", $key);
        $key = preg_replace('/^\xEF\xBB\xBF/', '', $key);

        $candidates = [];
        if (strpos($key, '-----BEGIN') !== false) {
            $candidates[] = $key;
        } else {
            $body = preg_replace('/\s+/', '', $key);
            if ($body === '' || base64_decode($body, true) === false) {
                throw new \think\Exception('通联支付私钥格式错误：私钥正文不是有效的 Base64 内容');
            }
            // 通联新密钥通常为 PKCS#8，同时兼容传统 PKCS#1 RSA 私钥。
            $candidates[] = "-----BEGIN PRIVATE KEY-----\n" . chunk_split($body, 64, "\n") . "-----END PRIVATE KEY-----";
            $candidates[] = "-----BEGIN RSA PRIVATE KEY-----\n" . chunk_split($body, 64, "\n") . "-----END RSA PRIVATE KEY-----";
        }

        $lastError = '';
        foreach ($candidates as $candidate) {
            $this->clearOpenSslErrors();
            $privateKey = @openssl_pkey_get_private($candidate);
            if ($privateKey !== false) {
                return $privateKey;
            }
            $lastError = $this->getOpenSslError();
        }

        throw new \think\Exception(
            '通联支付私钥无法解析，请确认填写的是商户 RSA 私钥而不是公钥，并检查 PEM 头尾及换行。OpenSSL：' . $lastError
        );
    }

    private function clearOpenSslErrors()
    {
        while (openssl_error_string() !== false) {
        }
    }

    private function getOpenSslError()
    {
        $errors = [];
        while (($error = openssl_error_string()) !== false) {
            $errors[] = $error;
        }
        return $errors ? end($errors) : '未知错误';
    }

    private function randomStr($length = 16)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $str;
    }
}
