<?php

/**
 * 顺丰配送  开发文档:https://commit-openic.sf-express.com/#/apidoc
 * User: 张涛
 * Date: 2020/8/6
 */
class Shunfeng
{
    const SERVER_HOST = 'https://commit-openic.sf-express.com';

    //创建订单
    const CREATE_ORDER = '/open/api/external/createorder';

    //取消订单
    const CANCEL_ORDER = '/open/api/external/cancelorder';

    //获取配送员实时坐标接口
    const RIDER_LATEST_POSITION = '/open/api/external/riderlatestposition';

    const VERSION = 17;

    /**
     * 开发者账号
     * @var
     */
    private $devId;

    /**
     * 开发者密钥
     * @var
     */
    private $devKey;


    /**
     * 顺丰店铺ID
     */
    private $shopId;

    public function __construct($config)
    {
        $this->devId = $config['shunfeng_dev_id'] ?? '';
        $this->devKey = $config['shunfeng_dev_key'] ?? '';
        $this->shopId = $config['shunfeng_shop_id'] ?? '';
    }

    public function getSign($data)
    {
        $postData = '';
        if ($data) {
            $postData = is_array($data) ? json_encode($data) : $data;
        }
        $signChar = $postData . '&' . $this->devId . '&' . $this->devKey;
        $sign = base64_encode(MD5($signChar));
        return $sign;
    }

    public function createorder($data)
    {
        $data['dev_id'] = $this->devId;
        $data['version'] = self::VERSION;
        $data['shop_id'] = $this->shopId;
        $data['shop_type'] = 1;
        if (isset($data['order_source'])) {
            $data['order_source'] = '平台';
        }
        $sign = $this->getSign($data);
        $url = self::SERVER_HOST . self::CREATE_ORDER . '?sign=' . $sign;
        $rs = HTTPHelper::post($url, $data);
        return $rs;
    }

    public function cancelorder($data)
    {
        $data['dev_id'] = $this->devId;
        if (isset($data['shop_id'])) {
            $data['shop_id'] = $this->shopId;
            $data['shop_type'] = 1;
        }
        if(isset($data['order_source'])){
            $data['order_source'] = '平台';
        }

        $sign = $this->getSign($data);
        $url = self::SERVER_HOST . self::CANCEL_ORDER . '?sign=' . $sign;
        $rs = HTTPHelper::post($url, $data);
        return $rs;
    }

    public function riderlatestposition($data)
    {
        $data['dev_id'] = $this->devId;
        $data['shop_id'] = $this->shopId;
        $data['shop_type'] = 1;
        if(isset($data['order_source'])){
            $data['order_source'] = '平台';
        }

        $sign = $this->getSign($data);
        $url = self::SERVER_HOST . self::RIDER_LATEST_POSITION . '?sign=' . $sign;
        $rs = HTTPHelper::post($url, $data);
        if ($rs['error_code'] == 0) {
            return $rs['result'];
        } else {
            return [];
        }
    }
}


class HTTPHelper
{
    public static function post($url, $param)
    {
        if (empty($url) or empty($param)) {
            throw new \Exception('Params is not of the expected type');
        }

        if (!empty($param) && is_array($param)) {
            $param = json_encode($param);
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
        curl_setopt($ch, CURLOPT_POST, true);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: Application/json'));
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result, true);
        fdump_api([$url, $param, $result], 'Shunfeng/post',true);
        return $result ?: [];
    }
}