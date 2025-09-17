<?php
namespace express;

use think\facade\Db;

/**
 * 蜂鸟配送，文档：https://open.ele.me/documents  新API通用接口
 *
 * @author: zt
 * @date: 2025/06/12
 */
class EleDeliveryNew
{

    private $appId = '';

    private $secretKey = '';

    private $merchantId = '';

    private $version = '1.0';

    private $openApiUrl;

    private $bizUrl;

    const SANDBOX_SERVER = 'https://exam-anubis.ele.me/';

    const PRODUCTION_SERVER = 'https://open-anubis.ele.me/';

    protected $businessData = [];

    public function __construct()
    {
        $this->appId = cfg('ele_app_id');
        $this->secretKey = cfg('ele_secret_key');
        $this->merchantId = cfg('ele_merchant_id');

        if (cfg('ele_runtime_env') == 1) { //正式环境
            $this->openApiUrl = self::PRODUCTION_SERVER . 'anubis-webapi/openapi';
            $this->bizUrl = self::PRODUCTION_SERVER . 'anubis-webapi/v3/invoke';
        } else { //沙箱环境
            $this->openApiUrl = self::SANDBOX_SERVER . 'anubis-webapi/openapi';
            $this->bizUrl = self::SANDBOX_SERVER . 'anubis-webapi/v3/invoke';
        }
    }

    //设置业务参数
    public function setBusinessData($key, $val)
    {
        $this->businessData[$key] = $val;
    }

    //清空业务参数
    public function clearBusinessData()
    {
        $this->businessData = array();
    }

    //生成接口请求参数
    public function buildRequestParam($businessData)
    {
        $data = [
            'app_id' => $this->appId,
            'timestamp' => Helper::millisecond(),
            'access_token' => $this->getAccessToken(),
            'merchant_id' => $this->merchantId,
            'business_data' => json_encode($businessData, JSON_UNESCAPED_UNICODE),
            'version' => $this->version,
        ];
        $data['signature'] = Helper::sign($data, $this->secretKey);
        return $data;
    }

    /**
     * 获取token
     * @author: zt
     * @date: 2025/06/12
     */
    public function getAccessToken($code = '')
    {
        $record = Db::name('ele_delivery_access_token')->where('app_id', $this->appId)->where('secret_key', $this->secretKey)->find();
        if ($record && empty($code)) {
            if ($record['expire_time'] > time()) {
                return $record['access_token'];
            }
            //刷新token
            $postParam = [
                'grant_type' => 'refresh_token',
                'refresh_token' => $record['refresh_token'],
                'app_id' => $this->appId,
                'merchant_id' => $this->merchantId,
                'timestamp' => Helper::millisecond(),
            ];
            $url = $this->openApiUrl . '/refreshToken';
        } else {
            //生成token
            $postParam = [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'app_id' => $this->appId,
                'merchant_id' => $this->merchantId,
                'timestamp' => Helper::millisecond(),
            ];
            $url = $this->openApiUrl . '/token';
        }

        $postParam['signature'] = Helper::sign($postParam, $this->secretKey);
        $resp = HttpClient::doPost($url, json_encode($postParam));
        $result = Helper::getBusinessDataResult($resp);
        $data = [
            'app_id' => $this->appId,
            'secret_key' => $this->secretKey,
            'access_token' => $result['access_token'],
            'expire_time' => time()  + 7200,
            'refresh_token' => $result['refresh_token'],
            'merchant_id' => $this->merchantId,
        ];
        if ($record) {
            Db::name('ele_delivery_access_token')->where('id' , $record['id'])->update($data);
        } else {
            Db::name('ele_delivery_access_token')->insert($data);
        }
        return $result['access_token'];
    }

    /**
     * 创建订单
     * @author: zt
     * @date: 2025/06/12
     */
    public function createOrder()
    {
        /*$businessData = [
            'partner_order_code' => '25060905552712345681',
            'receiver_primary_phone' => '13205695127',
            'goods_count' => 1,
            'chain_store_id' => '323412140',
            'receiver_latitude' => '40.008944',
            'goods_total_amount_cent' => 100,
            'receiver_name' => '张三',
            'receiver_longitude' => '116.333585',
            'receiver_address' => '北京市海淀区双清路30号清华大学',
            'position_source' => 2, //坐标经纬度来源（1:腾讯地图,2:百度地图, 3:高德地图），蜂鸟建议使用高德地图
            'order_type' => 1,   //如果需要送达时间 – 推单时间 ＜ 60min，则蜂鸟配送开放平台自动将订单类型置为即时单，设置预约单无效
            'goods_weight' => 1,
            'goods_item_list' => [
                [
                    'item_actual_amount_cent' => 100,
                    'item_amount_cent' => 1,
                    'item_name' => '测试商品，请勿配送'
                ]
            ],
            'require_receive_time'=>'', 
            'goods_actual_amount_cent' => 100,
        ];*/

        $data = $this->buildRequestParam($this->businessData);
        $url = $this->bizUrl . '/createOrder';
        $resp = HttpClient::doPost($url, json_encode($data));
        $result = Helper::getBusinessDataResult($resp);
        return $result;
    }

    //获取可用订单取消原因接口
    public function getCancelReasonList()
    {
        // $businessData = [
        //     'partner_order_code' => '25060905552712345681',
        // ];
        $data = $this->buildRequestParam($this->businessData);
        $url = $this->bizUrl . '/getCancelReasonList';
        $resp = HttpClient::doPost($url, json_encode($data));
        $result = Helper::getBusinessDataResult($resp);
        return $result;
    }

    /**
     * 订单预取消接口
     * @author: zt
     * @date: 2025/06/12
     */
    public function preCancelOrder()
    {
        // $businessData = [
        //     'partner_order_code' => '25060905552712345681',
        //     'order_cancel_code' => 32,
        // ];
        $data = $this->buildRequestParam($this->businessData);
        $url = $this->bizUrl . '/preCancelOrder';
        $resp = HttpClient::doPost($url, json_encode($data));
        $result = Helper::getBusinessDataResult($resp);
        return $result;
    }

    /**
     * 订单取消接口
     * @author: zt
     * @date: 2025/06/12
     */
    public function cancelOrder()
    {
        // $businessData = [
        //     'partner_order_code' => '25060905552712345681',
        //     'order_cancel_role' => 2, // 1商户取消, 2 用户取消
        //     'order_cancel_code' => 32,
        //     'order_cancel_other_reason' => '无条件取消'
        // ];
        $data = $this->buildRequestParam($this->businessData);
        $url = $this->bizUrl . '/cancelOrder';
        $resp = HttpClient::doPost($url, json_encode($data));
        $result = Helper::getBusinessDataResult($resp);
        return $result;
    }

    //查看门店配送范围接口
    public function chainstoreRange()
    {
        $data = $this->buildRequestParam($this->businessData);
        $url = $this->bizUrl . '/chainstoreRange';
        $resp = HttpClient::doPost($url, json_encode($data));
        $result = Helper::getBusinessDataResult($resp);
        return $result;
    }

    //查询订单详情接口
    public function getOrderDetail()
    {
        $data = $this->buildRequestParam($this->businessData);
        $url = $this->bizUrl . '/getOrderDetail';
        $resp = HttpClient::doPost($url, json_encode($data));
        $result = Helper::getBusinessDataResult($resp);
        return $result;
    }

    //查询骑手信息接口
    public function getKnightInfo()
    {
        $data = $this->buildRequestParam($this->businessData);
        $url = $this->bizUrl . '/getKnightInfo';
        $resp = HttpClient::doPost($url, json_encode($data));
        $result = Helper::getBusinessDataResult($resp);
        return $result;
    }
}

/**
 * 帮助类，生成签名
 * @author: zt
 * @date: 2025/06/12
 */
class Helper
{

    //获取毫秒
    public static function millisecond()
    {
        list($msec, $sec) = explode(' ', microtime());
        return (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
    }
    //参数拼接
    public static function toUrlParams($values)
    {
        $buff = '';
        foreach ($values as $k => $v) {
            if ($k != 'sign' && $v != '' && !is_array($v)) {
                $buff .= $k . '=' . $v . '&';
            }
        }
        return trim($buff, '&');
    }

    //SHA-256加密
    public static function sha256($data, $rawOutput = false)
    {
        if (!is_scalar($data)) {
            return false;
        }
        $data = (string)$data;
        return hash('sha256', $data, $rawOutput);
    }

    //签名
    public static function sign($postParam, $appSecret)
    {
        ksort($postParam);
        $waitSign = self::toUrlParams($postParam);
        $sign = Helper::sha256($appSecret . $waitSign);
        return strtolower($sign);
    }

    public static function getBusinessDataResult($resp)
    {
        if (empty($resp) || $resp['apiCode'] != '00000') {
            throw new \Exception('蜂鸟配送返回：' . $resp['apiMsg']);
        }
        return json_decode($resp['business_data'], true);
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
        fdump_sql([$url, $param, $result], 'eleDelivery_post');
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
            throw new \InvalidArgumentException('Params is not of the expected type');
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
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);     //  不进行ssl 认证

        if (strcmp($method, "POST") == 0) {  // POST 操作
            curl_setopt($ch, CURLOPT_POST, true);
        } else if (strcmp($method, "DELETE") == 0) { // DELETE操作
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        } else {
            throw new \InvalidArgumentException('Please input correct http method, such as POST or DELETE');
        }

        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: Application/json'));
        $result = curl_exec($ch);
        curl_close($ch);
        fdump_api([$url, $param, $result], 'EleDelivery/post',true);
        $result = json_decode($result, true);
        return $result ?: [];
    }
}
