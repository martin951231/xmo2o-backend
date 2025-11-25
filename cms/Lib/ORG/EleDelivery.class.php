<?php

/**
 * 蜂鸟配送，文档：https://open.ele.me/documents  旧版蜂鸟接口，官方已废弃
 * @author 张涛
 * @date 2020/06/30
 */
class EleDelivery
{
    private $appId = '';

    private $secretKey = '';

    private $apiUrl;
    
    public function __construct($config)
    {
        $this->appId = $config['ele_app_id'] ?? '';
        $this->secretKey = $config['ele_secret_key'] ?? '';
        
        $runtimeEnv = C('config.ele_runtime_env') ?: 1;
        if($runtimeEnv == 1){//正式环境
            $this->apiUrl = 'https://open-anubis.ele.me/anubis-webapi';
        }else{//沙箱环境
            $this->apiUrl = 'https://exam-anubis.ele.me/anubis-webapi';
        }
    }

    /**
     * 获取token
     * @author 张涛
     * @date 2020/06/30
     */
    public function getAccessToken()
    {
        $db = M('Ele_delivery_access_token');
        $record = $db->where(['app_id' => $this->appId, 'secret_key' => $this->secretKey])->find();
        if ($record && $record['expire_time'] > time()) {
            return $record['access_token'];
        }

        $salt = mt_rand(1000, 9999);
        $sig = Helper::generateSign($this->appId, $salt, $this->secretKey);
        $url = $this->apiUrl . '/get_access_token';
        $resp = HttpClient::doGet($url, array('app_id' => $this->appId, 'salt' => $salt, 'signature' => $sig));
        if (empty($resp) || $resp['code'] != 200) {
            throw new \Exception('蜂鸟配送获取access_token失败');
        }
        $data = [
            'app_id' => $this->appId,
            'secret_key' => $this->secretKey,
            'access_token' => $resp['data']['access_token'],
            'expire_time' => intval($resp['data']['expire_time'] / 1000 - 5 * 3600)
        ];
        if ($record) {
            $db->where(['id' => $record['id']])->save($data);
        } else {
            $db->add($data);
        }
        return $resp['data']['access_token'];
    }

    /**
     * 创建订单
     * @author 张涛
     * @date 2020/06/30
     */
    public function createOrder($data = [])
    {
        $salt = mt_rand(1000, 9999);
        $dataJson = json_encode($data, JSON_UNESCAPED_UNICODE);
        $urlencodeData = urlencode($dataJson);
        $sig = Helper::generateBusinessSign($this->appId, $this->getAccessToken(), $urlencodeData, $salt);   //生成签名
        $requestJson = json_encode(array(
            'app_id' => $this->appId,
            'salt' => $salt,
            'data' => $urlencodeData,
            'signature' => $sig
        ));
        $url = $this->apiUrl . '/v2/order';
        return HttpClient::doPost($url, $requestJson);
    }

    /**
     * 取消订单
     * @author 张涛
     * @date 2020/06/30
     */
    public function cancelOrder($data = [])
    {
        $dataJson = json_encode($data);
        $salt = mt_rand(1000, 9999);
        $urlencodeData = urlencode($dataJson);
        $sig = Helper::generateBusinessSign($this->appId, $this->getAccessToken(), $urlencodeData, $salt);
        $requestJson = json_encode(array(
            'app_id' => $this->appId,
            'salt' => $salt,
            'data' => $urlencodeData,
            'signature' => $sig
        ));
        $url = $this->apiUrl . "/v2/order/cancel";
        return HttpClient::doPost($url, $requestJson);
    }

    /**
     * 查询骑手位置
     * @author 张涛
     * @date 2020/07/01
     */
    public function getDeliverPosition($data = [])
    {
        $dataJson = json_encode($data);
        $salt = mt_rand(1000, 9999);
        $urlencodeData = urlencode($dataJson);
        $sig = Helper::generateBusinessSign($this->appId, $this->getAccessToken(), $urlencodeData, $salt);
        $requestJson = json_encode(array(
            'app_id' => $this->appId,
            'salt' => $salt,
            'data' => $urlencodeData,
            'signature' => $sig
        ));
        $url = $this->apiUrl . "/v2/order/carrier";
        return HttpClient::doPost($url, $requestJson);
    }
}

/**
 * 帮助类，生成签名
 * @author 张涛
 * @date 2020/06/30
 */
class Helper
{
    public static function generateSign($appId, $salt, $secretKey)
    {
        $seed = 'app_id=' . $appId . '&salt=' . $salt . '&secret_key=' . $secretKey;
        return md5(urlencode($seed));
    }

    public static function generateBusinessSign($appId, $token, $urlencodeData, $salt)
    {
        $seed = 'app_id=' . $appId . '&access_token=' . $token
            . '&data=' . $urlencodeData . '&salt=' . $salt;
        return md5($seed);
    }
}

class HttpClient
{
    /**
     * 发送GET请求
     * @param string $url
     * @param array $param
     * @return bool|mixed
     */
    public static function doGet($url, $param = null)
    {
        if (empty($url) or (!empty($param) and !is_array($param))) {
            throw new InvalidArgumentException('Params is not of the expected type');
        }

        if (!empty($param)) {
            $url = trim($url, '?') . '?' . http_build_query($param);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);     //  不进行ssl 认证
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $result = curl_exec($ch);
        curl_close($ch);
        fdump_sql([$url,$param,$result],'eleDelivery_post');
        $result = json_decode($result, true);
        return $result ?: [];
    }

    /**
     * POST请求
     * @param $url
     * @param $param
     * @return boolean|mixed
     */
    public static function doPost($url, $param, $method = "POST")
    {
        if (empty($url) or empty($param)) {
            throw new InvalidArgumentException('Params is not of the expected type');
        }

        if (!empty($param) and is_array($param)) {
            $param = urldecode(json_encode($param));
        } else {
            $param = strval($param);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        // curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);     //  不进行ssl 认证

        if (strcmp($method, "POST") == 0) {  // POST 操作
            curl_setopt($ch, CURLOPT_POST, true);
        } else if (strcmp($method, "DELETE") == 0) { // DELETE操作
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        } else {
            throw new InvalidArgumentException('Please input correct http method, such as POST or DELETE');
        }

        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: Application/json'));
        $result = curl_exec($ch);
        curl_close($ch);
        fdump_sql([$url,$param,$result],'eleDelivery_post');
        $result = json_decode($result, true);
        return $result ?: [];
    }
}
