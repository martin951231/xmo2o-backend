<?php

class SandePay
{
    // 商户号
    public $sellerMid;
    // 公钥文件
    public $publicKeyPath = '';
    // 私钥文件
    public $privateKeyPath = '';
    // 私钥证书密码
    public $privateKeyPwd = '';
    // 接口地址
    public $apiUrl = 'https://cashier.sandpay.com.cn';
    // 产品id https://open.sandpay.com.cn/product/detail/43984//
    public $productId;
    // 接入类型  1-普通商户接入 2-平台商户接入 3-核心企业商户接入
    public $accessType = '1';
    // 渠道类型  07-互联网  08-移动端
    public $channelType = '07';
    // 平台ID accessType为2时必填，在担保支付模式下填写核心商户号
    public $plMid = '';
    // 请求报文体
    public $body;

    /*
    |--------------------------------------------------------------------------
    | step1.组装参数
    |--------------------------------------------------------------------------
    */
    // 参数
    protected function postData($method)
    {
        $data = array(
            'head' => array(
                'version'     => '1.0',
                'method'      => $method,
                'productId'   => $this->productId,
                'accessType'  => $this->accessType,
                'mid'         => $this->sellerMid,
                'plMid'       => $this->plMid,
                'channelType' => $this->channelType,
                'reqTime'     => date('YmdHis', time()),
            ),
            'body' => $this->body,
        );

        $postData = array(
            'charset'  => 'utf-8',
            'signType' => '01',
            'data'     => json_encode($data),
            'sign'     => $this->sign($data),
        );

        return $postData;
    }

    /*
    |--------------------------------------------------------------------------
    | step2. 请求
    |--------------------------------------------------------------------------
    */
    // curl请求接口
    public function request($apiName)
    {
        try {
            $apiMap = $this->apiMap();
            if (!isset($apiMap[$apiName])) {
                throw new \Exception('接口名错误');
            }
            $postData = $this->postData($apiMap[$apiName]['method']);
            $url      = $this->apiUrl . $apiMap[$apiName]['url'];

            $ret    = $this->httpPost($url, $postData);
            $retAry = $this->parseResult($ret); // 格式解析
            $verify = $this->verify($retAry['data'], $retAry['sign']); // 验签
            if (!$verify) {
                throw new \Exception('返回数据验签失败');
            }
            return $retAry;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    // curl. 发送请求
    protected function httpPost($url, $params)
    {
        if (empty($url) || empty($params)) {
            throw new \Exception('请求参数错误');
        }
        $params = http_build_query($params);
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $data  = curl_exec($ch);
            $err   = curl_error($ch);
            $errno = curl_errno($ch);
            if ($errno) {
                $msg = 'curl errInfo: ' . $err . ' curl errNo: ' . $errno;
                throw new \Exception($msg);
            }
            curl_close($ch);
            return $data;
        } catch (\Exception $e) {
            if ($ch) curl_close($ch);
            throw $e;
        }
    }

    // curl.解析返回数据
    protected function parseResult($result)
    {
        $arr      = array();
        $response = urldecode($result);
        $arrStr   = explode('&', $response);
        foreach ($arrStr as $str) {
            $p         = strpos($str, "=");
            $key       = substr($str, 0, $p);
            $value     = substr($str, $p + 1);
            $arr[$key] = $value;
        }

        return $arr;
    }

    // 表单请求接口
    public function form($apiName)
    {
        $apiMap = $this->apiMap();
        if (!isset($apiMap[$apiName])) {
            throw new \Exception('接口名错误');
        }
        $postData = $this->postData($apiMap[$apiName]['method']);
        $url      = $this->apiUrl . $apiMap[$apiName]['url'];

        $form = '<form action="' . $url . '" method="post">';
        foreach ($postData as $k => $v) {
            $form .= "{$k} <p><input type='text' name='{$k}' value='{$v}'></p>";
        }
        $form .= '<input type="submit" value="提交"></form>';

        return $form;
    }

    /*
    |--------------------------------------------------------------------------
    | step3.签名 + 验签
    |--------------------------------------------------------------------------
    */

    // 公钥
    private function publicKey()
    {
        try {
            $file = file_get_contents($this->publicKeyPath);
            if (!$file) {
                throw new \Exception('getPublicKey::file_get_contents ERROR');
            }
            $cert   = chunk_split(base64_encode($file), 64, "\n");
            $cert   = "-----BEGIN CERTIFICATE-----\n" . $cert . "-----END CERTIFICATE-----\n";
            $res    = openssl_pkey_get_public($cert);
            $detail = openssl_pkey_get_details($res);
            openssl_free_key($res);
            if (!$detail) {
                throw new \Exception('getPublicKey::openssl_pkey_get_details ERROR');
            }
            return $detail['key'];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    // 私钥
    private function privateKey()
    {
        try {
            $file = file_get_contents($this->privateKeyPath);
            if (!$file) {
                throw new \Exception('getPrivateKey::file_get_contents');
            }
            if (!openssl_pkcs12_read($file, $cert, $this->privateKeyPwd)) {
                throw new \Exception('getPrivateKey::openssl_pkcs12_read ERROR');
            }
            return $cert['pkey'];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    // 私钥加签
    protected function sign($plainText)
    {
        $plainText = json_encode($plainText);
        try {
            $resource = openssl_pkey_get_private($this->privateKey());
            $result   = openssl_sign($plainText, $sign, $resource);
            openssl_free_key($resource);
            if (!$result) throw new \Exception('sign error');
            return base64_encode($sign);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    // 公钥验签
    public function verify($plainText, $sign)
    {
        $resource = openssl_pkey_get_public($this->publicKey());
        $result   = openssl_verify($plainText, base64_decode($sign), $resource);
        openssl_free_key($resource);

        if (!$result) {
            throw new \Exception('签名验证未通过,plainText:' . $plainText . '。sign:' . $sign);
        }

        return $result;
    }

    // 参数映射
    public function apiMap()
    {
        return array(
            'orderPay'          => array(
                'method' => 'sandpay.trade.pay',
                'url'    => '/gateway/api/order/pay',
            ),
            'orderQuery'        => array(
                'method' => 'sandpay.trade.query',
                'url'    => '/gateway/api/order/query',
            ),
            'orderRefund'       => array(
                'method' => 'sandpay.trade.refund',
                'url'    => '/gateway/api/order/refund',
            ),
            'clearfileDownload' => array(
                'method' => 'sandpay.trade.download',
                'url'    => '/gateway/api/clearfile/download',
            ),
            'multipartCreate' => array(
                'method' => 'sandpay.multipart.create',
                'url'    => '/multipart/create',
            ),
        );
    }
}
