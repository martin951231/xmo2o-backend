<?php
namespace express;
class Keloop
{
    const BASE_URL = 'https://open.keloop.cn';
    const VERSION = 1;

    private $_team_token = '';  // 团队 Token
    private $_dev_key = '';     // 开发者 key
    private $_dev_secret = '';  // 开发者 密钥

    /**
     * KeloopSdk constructor.
     * @throws Exception
     */
    public function __construct()
    {
        $this->_dev_key = cfg('keloop_dev_key');
        $this->_dev_secret = cfg('keloop_dev_secret');
        $this->_team_token = cfg('keloop_team_token');

        if (empty($this->_dev_key) || empty($this->_dev_secret)) {
            throw new Exception('dev_key 或 dev_secret 异常');
        }
        if (empty($this->_team_token)) {
            throw new Exception('team_token 异常');
        }
    }

    /**
     * generate ticket
     * @return string
     */
    private function _genTicket()
    {
        if (function_exists('com_create_guid')) {
            $uuid = trim(com_create_guid(), '{}');
        } else {
            mt_srand((double)microtime() * 10000);
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45);
            $uuid = substr($charid, 0, 8) . $hyphen
                . substr($charid, 8, 4) . $hyphen
                . substr($charid, 12, 4) . $hyphen
                . substr($charid, 16, 4) . $hyphen
                . substr($charid, 20, 12);
        }
        return strtoupper($uuid);
    }

    /**
     * 封装参数
     * @param array $paramm
     * @return array
     */
    private function _genParam($paramm = [])
    {
        $data = [
            'version' => self::VERSION,
            'timestamp' => time(),
            'ticket' => $this->_genTicket(),
            'team_token' => $this->_team_token,
            'dev_key' => $this->_dev_key,
            'body' => json_encode($paramm)
        ];
        $sign = Md5Sign::getSign($data, $this->_dev_secret);
        $data['sign'] = $sign;
        return $data;
    }

    /**
     * @param $path
     * @param array $param
     * @return mixed|null
     */
    public function getUrl($path, $param = [])
    {
        $data = $this->_genParam($param);
        $url = self::BASE_URL . $path;
        $result = HTTPRequests::getUrl($url, $data);
        if (!empty($result)) {
            return json_decode($result, true);
        } else {
            return null;
        }
    }

    /**
     * @param $path
     * @param array $param
     * @return mixed|null
     */
    public function postUrl($path, $param = [])
    {
        $data = $this->_genParam($param);
        $url = self::BASE_URL . $path;
        $result = HTTPRequests::postUrl($url, $data);
        if (!empty($result)) {
            $res = json_decode($result, true);
            if($res['code'] != '200'){
                fdump_sql(json_encode($data),'keloop_exception');
                fdump_sql(json_encode($res),'keloop_exception');
            }
            return $res;
        } else {
            fdump_sql(json_encode($data),'keloop_exception');
            return null;
        }
    }

    /**
     * 检测 sign 是否正确
     * @param $paramm
     * @return bool
     */
    public function checkSign($paramm)
    {
        if (!isset($paramm['sign'])) {
            return false;
        }
        return Md5Sign::isSignCorrect($paramm, $this->_dev_secret, $paramm['sign']);
    }

    // ********************** 订单相关接口 ********************** //

    /**
     * 向绑定的配送站发送订单
     * @param $param
     * @return mixed|null
     */
    public function createOrder($param)
    {
        $path = '/open/order/createOrder';
        return $this->postUrl($path, $param);
    }

    /**
     * 获取订单信息
     * @param $param
     * @return mixed|null
     */
    public function getOrderInfo($param)
    {
        $path = '/open/order/getOrderInfo';
        return $this->getUrl($path, $param);
    }

    /**
     * 获取订单进程
     * @param $param
     * @return mixed|null
     */
    public function getOrderLog($param)
    {
        $path = '/open/order/getOrderLog';
        return $this->getUrl($path, $param);
    }

    /**
     * 计算快跑者商户配送费
     * @param $param
     * @return mixed|null
     */
    public function getFee($param)
    {
        $path = '/open/order/getFee';
        return $this->getUrl($path, $param);
    }

    /**
     * 获取配送员最新坐标
     * @param $param
     * @return mixed|null
     */
    public function getCourierTag($param)
    {
        $path = '/open/order/getCourierTag';
        return $this->getUrl($path, $param);
    }

    /**
     * 取消订单
     * @param $param
     * @return mixed|null
     */
    public function cancelOrder($param)
    {
        $path = '/open/order/cancelOrder';
        return $this->postUrl($path, $param);
    }

    /**
     * 评论订单
     * @param $param
     * @return mixed|null
     */
    public function commentOrder($param)
    {
        $path = '/open/order/commentOrder';
        return $this->postUrl($path, $param);
    }

    /**
     * 获取商户计价明细
     * @param $param
     * @return mixed|null
     */
    public function getMerchantCalc($param)
    {
        $path = '/open/order/getFee';
        return $this->postUrl($path, $param);
    }

    // ********************** 团队相关接口 ********************** //

    /**
     * 获取团队信息
     * @return mixed|null
     */
    public function getTeamInfo()
    {
        $path = '/open/team/getTeamInfo';
        return $this->getUrl($path);
    }

    // ********************** 商户相关接口 ********************** //

    /**
     * 获取团队所有合作商户信息
     * @return mixed|null
     */
    public function getMerchants()
    {
        $path = '/open/merchant/getMerchants';
        return $this->getUrl($path);
    }

    // ********************** 配送员相关接口 ********************** //

    /**
     * 获取团队合作的所有配送员信息
     * @return mixed|null
     */
    public function getCouriers()
    {
        $path = '/open/courier/getCouriers';
        return $this->getUrl($path);
    }

}

/**
 * 签名类
 * Class Md5Sign
 */
class Md5Sign
{

    /**
     * 获取签名
     * @param array $param 密的参数数组
     * @param string $accessSec 加密的key
     * @return bool|string 生产的签名
     */
    public static function getSign($param, $accessSec)
    {
        if (empty($param) || empty($accessSec)) {
            return false;
        }

        // 除去待签名参数数组中的空值和签名参数
        $param = self::paraFilter($param);
        $param = self::argSort($param);
        $str = self::createLinkstring($param);
        $sign = self::md5Verify($str, $accessSec);
        return $sign;
    }

    /**
     * 判断签名是否正确
     * @param $paramm
     * @param $encKey
     * @param $sign
     * @return bool
     */
    public static function isSignCorrect($paramm, $encKey, $sign)
    {
        if (empty($sign)) {
            return false;
        } else {
            $prestr = self::getSign($paramm, $encKey);
            return $prestr === $sign ? true : false;
        }
    }

    /**
     * 除去数组中的空值和签名参数
     * @param array $param 签名参数组
     * @return array 获取去掉空值与签名参数后的新签名参数组
     */
    private static function paraFilter($param)
    {
        $param_filter = array();
        while (list ($key, $val) = each($param)) {
            //去掉 '',null,保留数字0
            if ($key == 'sign' || $key == 'sign_type' || $key == 'key' || (empty($val) && !is_numeric($val))) {
                continue;
            } else {
                $param_filter[$key] = $param[$key];
            }
        }
        return $param_filter;
    }

    /**
     * 对数组排序
     * @param array $param 排序前的数组
     * @return mixed 排序后的数组
     */
    private static function argSort($param)
    {
        ksort($param);
        reset($param);
        return $param;
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param array $param 需要拼接的数组
     * @return string 拼接完成以后的字符串
     */
    private static function createLinkstring($param)
    {
        $arg = '';
        while (list ($key, $val) = each($param)) {
            $arg .= $key . '=' . $val . '&';
        }
        //去掉最后一个&字符
        $arg = trim($arg, '&');
        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }
        return $arg;
    }

    /**
     * 生成签名
     * @param string $prestr 需要签名的字符串
     * @param string $sec 身份认证密钥(access_sec)
     * @return string 签名结果
     */
    private static function md5Verify($prestr, $sec)
    {
        return md5($prestr . $sec);
    }

}

/**
 * HTTP 请求类
 * Class HTTPRequests
 */
class HTTPRequests
{

    /**
     * Http post request
     * @param $url
     * @param array $paramms
     * @param int $timeout
     * @return bool|mixed
     */
    public static function postUrl($url, $paramms = array(), $timeout = 30)
    {
        //编码特殊字符
        $p = http_build_query($paramms);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false); // 检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_URL, $url);
        // 设置header
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $p);
        // 设置cURL 参数，要求结果保存到字符串中还是输出到屏幕上。
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        // 运行cURL，请求网页
        $data = curl_exec($curl);
        fdump_sql([curl_error($curl), $url, $paramms], 'keloop_http_post_result');
        curl_close($curl);
        if ($data === false) {
            return false;
        } else {
            return $data;
        }
    }

    /**
     * Http get request
     * @param $url
     * @param array $paramm
     * @return mixed
     */
    public static function getUrl($url, $paramm = array())
    {
        $url = self::buildUrl($url, $paramm);
        return self::get($url);
    }

    /**
     * Http get request
     * @param $url
     * @param int $timeout
     * @return mixed
     */
    public static function get($url, $timeout = 3)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $resposne = curl_exec($ch);
        return $resposne;
    }

    /**
     * Build request url
     * @param $url
     * @param $paramm
     * @return string
     */
    private static function buildUrl($url, $paramm)
    {
        $url = rtrim(trim($url), '?');
        $url = $url . '?';
        $query = '';
        if (!empty($paramm)) {
            $query = http_build_query($paramm);
        }
        return $url . $query;
    }

}