<?php
/**
 * 调用v20service方法
 * @param $func string 方法名   例如:\app\shop\model\service\order\ShopOrderService/方法名;
 * @param $params array 方法参数 例：[
 * 参数名1 => 参数值,
 * 参数名2 => 参数值,
 * ...
 * ]
 * @param $curlTimeOutArr 设置curl 超时 [ connectionout => 60 ,timeout=>7]
 * @return array|mixed  ['error_no'=>0,'error_msg'=>'','retval'=>[]]
 * @author: 衡婷妹
 * @date: 2020/9/11
 *
 */
function invoke_v20_service($func, $params = [], $curlTimeOutArr = [])
{
    $siteUrl = C('config.site_url');
    $api     = $siteUrl . '/v20/public/index.php/common/common.invoke/index';
    $tm      = time();
    $token   = md5($siteUrl . $tm);
    $headers = ['Content-Type: application/json', 'Invoke-Auth-Token:' . $token];
    $result  = httpRequest($api, 'post', json_encode(['func' => $func, 'params' => $params, 'tm' => $tm]), $headers,false,$curlTimeOutArr);
    $result  = isset($result[1]) ? json_decode($result[1], true) : [];
    if (is_array($result) && $result['error_no'] == 1) {
        fdump_api([$params, $result], 'invoke_v20_service_error/error', 1);
        return false;
    }
    return $result;
}

/**
 * Notes: 对应获取链接
 * @return string
 * @author: wanzy
 * @date_time: 2021/4/6 16:56
 */
function get_base_url1($url = '')
{
    if (C('config.system_type') == 'village') {
        $base_url = '/packapp/village/';
    } else {
        $base_url = '/packapp/plat/';
    }
    $_url = C('config.site_url') . $base_url;
    if ($url) {
        $_url .= $url;
    }
    return $_url;
}


/*
 * 截取中文字符串
 */
function msubstr($str, $start = 0, $length, $suffix = true, $charset = "utf-8")
{
    if (function_exists("mb_substr")) {
        if ($suffix && mb_strlen($str, $charset) > $length)
            return mb_substr($str, $start, $length, $charset) . "...";
        else
            return mb_substr($str, $start, $length, $charset);
    } elseif (function_exists('iconv_substr')) {
        if ($suffix && strlen($str) > $length)
            return iconv_substr($str, $start, $length, $charset) . "...";
        else
            return iconv_substr($str, $start, $length, $charset);
    }
    $re['utf-8']  = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
    $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
    $re['gbk']    = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
    $re['big5']   = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
    preg_match_all($re[$charset], $str, $match);
    $slice = join("", array_slice($match[0], $start, $length));
    if ($suffix) return $slice . "…";
    return $slice;
}

function arr_htmlspecialchars(&$value, $key, $isget = false)
{
    if ($isget == true) {
        $value = str_replace(array('<', '>', '"', '\'', '%3c', '%3e', '%3C', '%3E'), '', $value);
    }
    $value = str_replace(['<script', '</script', '%3cscript', '%3c/script'], '', $value);
    $value = htmlspecialchars($value);
}

function arr_htmlspecialchars_decode(&$value, $key, $isget = false)
{
    if ($isget == true) {
        $value = str_replace(array('<', '>', '"', '\'', '%3c', '%3e', '%3C', '%3E'), '', $value);
    }
    $value = str_replace(['<script', '</script', '%3cscript', '%3c/script'], '', $value);
    $value = htmlspecialchars_decode($value);
}

function fulltext_filter($value)
{
    $value = htmlspecialchars_decode($value);
    $value = str_replace(['<script', '</script', '%3cscript', '%3c/script'], '', $value);
    return $value;
}

/**
 * 加密和解密函数
 *
 * <code>
 * // 加密用户ID和用户名
 * $auth = authcode("{$uid}\t{$username}", 'ENCODE');
 * // 解密用户ID和用户名
 * list($uid, $username) = explode("\t", authcode($auth, 'DECODE'));
 * </code>
 *
 * @access public
 * @param string $string 需要加密或解密的字符串
 * @param string $operation 默认是DECODE即解密 ENCODE是加密
 * @param string $key 加密或解密的密钥 参数为空的情况下取全局配置encryption_key
 * @param integer $expiry 加密的有效期(秒)0是永久有效 注意这个参数不需要传时间戳
 * @return string
 */
function Encryptioncode($string, $operation = 'DECODE', $key = '', $expiry = 0)
{
    $ckey_length = 4;
    $key         = md5($key != '' ? $key : 'lhs_simple_encryption_code_45120');
    $keya        = md5(substr($key, 0, 16));
    $keyb        = md5(substr($key, 16, 16));
    $keyc        = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';

    $cryptkey   = $keya . md5($keya . $keyc);
    $key_length = strlen($cryptkey);

    $string        = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
    $string_length = strlen($string);

    $result = '';
    $box    = range(0, 255);

    $rndkey = array();
    for ($i = 0; $i <= 255; $i++) {
        $rndkey[$i] = ord($cryptkey[$i % $key_length]);
    }

    for ($j = $i = 0; $i < 256; $i++) {
        $j       = ($j + $box[$i] + $rndkey[$i]) % 256;
        $tmp     = $box[$i];
        $box[$i] = $box[$j];
        $box[$j] = $tmp;
    }

    for ($a = $j = $i = 0; $i < $string_length; $i++) {
        $a       = ($a + 1) % 256;
        $j       = ($j + $box[$a]) % 256;
        $tmp     = $box[$a];
        $box[$a] = $box[$j];
        $box[$j] = $tmp;
        $result  .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
    }

    if ($operation == 'DECODE') {
        if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
            return substr($result, 26);
        } else {
            return '';
        }
    } else {
        return $keyc . str_replace('=', '', base64_encode($result));
    }
}

/*****
 **生成简单的随机数
 **$length 需要的长度
 **$onlynum 生成纯数字的
 **$nouppLetter  不需要大写的，数字和小写的混合
 **$capitalize  只需要大写和数字
 **/
function createRandomStr($length = 6, $onlynum = false, $nouppLetter = false, $capitalize = false)
{
    if (!($length > 0)) return false;
    $returnstr = '';
    if ($onlynum) {
        for ($i = 0; $i < $length; $i++) {
            $returnstr .= rand(0, 9);
        }
    } elseif ($capitalize) {
        $strarr = array_merge(range(0, 9), range('A', 'Z'));
        shuffle($strarr);
        shuffle($strarr);
        $returnstr = implode('', array_slice($strarr, 0, $length));
    } else if ($nouppLetter) {
        $strarr = array_merge(range(0, 9), range('a', 'z'));
        shuffle($strarr);
        shuffle($strarr);
        $returnstr = implode('', array_slice($strarr, 0, $length));
    } else {
        $strarr = array_merge(range(0, 9), range('a', 'z'), range('A', 'Z'));
        shuffle($strarr);
        shuffle($strarr);
        $returnstr = implode('', array_slice($strarr, 0, $length));
    }
    return $returnstr;
}

/**
 * *封装一个通用的
 * cURL封装**
 * *$postfields 参数
 * */
function httpRequest($url, $method = 'GET', $postfields = null, $headers = array(), $debug = false,$timeOptions = [])
{
    $connectionout = 60;
    $timeout = 120;
    if (!empty($timeOptions)){
        if (isset($timeOptions['connectionout'])){
            $connectionout = (int)$timeOptions['connectionout'];
        }
        if (isset($timeOptions['timeout'])){
            $timeout = (int)$timeOptions['timeout'];
        }
    }
    $method = strtoupper($method);
    $ci     = curl_init();
    /* Curl settings */
    //curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);
    curl_setopt($ci, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.2; WOW64; rv:34.0) Gecko/20100101 Firefox/34.0");
    curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, $connectionout); /* 在发起连接前等待的时间，如果设置为0，则无限等待 */
    curl_setopt($ci, CURLOPT_TIMEOUT, $timeout); /* 设置cURL允许执行的最长秒数 */
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
    if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
        curl_setopt($ci, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
    }
    switch ($method) {
        case "POST":
            curl_setopt($ci, CURLOPT_POST, true);
            if (!empty($postfields)) {
                $tmpdatastr = is_array($postfields) ? http_build_query($postfields) : $postfields;
                curl_setopt($ci, CURLOPT_POSTFIELDS, $tmpdatastr);
            }
            break;
        default:
            curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $method); /* //设置请求方式 */
            break;
    }
    $ssl = preg_match('/^https:\/\//i', $url) ? TRUE : FALSE;
    curl_setopt($ci, CURLOPT_URL, $url);
    if ($ssl) {
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, FALSE); // 不从证书中检查SSL加密算法是否存在
    }
    //curl_setopt($ci, CURLOPT_HEADER, true); /*启用时会将头文件的信息作为数据流输出*/
    //curl_setopt($ci, CURLOPT_FOLLOWLOCATION, 1);
    //curl_setopt($ci, CURLOPT_MAXREDIRS, 2); /* 指定最多的HTTP重定向的数量，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的 */
    curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ci, CURLINFO_HEADER_OUT, true);
    /* curl_setopt($ci, CURLOPT_COOKIE, $Cookiestr); * *COOKIE带过去** */
    $response    = curl_exec($ci);
    $requestinfo = curl_getinfo($ci);
    $http_code   = curl_getinfo($ci, CURLINFO_HTTP_CODE);
    if ($debug) {
        echo "=====post data======\r\n";
        var_dump($postfields);
        echo "=====info===== \r\n";
        print_r($requestinfo);

        echo "=====response=====\r\n";
        print_r($response);
    }
    curl_close($ci);
    return array($http_code, $response, $requestinfo);
}

/**
 * *封装一个通用的带cookie
 * cURL封装**
 * *$postfields 参数
 * */
function httpRequestWithCookie($url, $method = 'GET', $postfields = null, $headers = array(), $debug = false, $header_out = true)
{

    $Cookiestr = "";  //* cUrl COOKIE处理*
    if (!empty($_COOKIE)) {
        foreach ($_COOKIE as $vk => $vv) {
            if ($vk == 'PHPSESSID') {
                continue;
            }
            $tmp[] = $vk . "=" . $vv;
        }
        $Cookiestr = implode(";", $tmp);
    }
    $method = strtoupper($method);
    $ci     = curl_init();
    /* Curl settings */
    curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
    curl_setopt($ci, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.75 Safari/537.36");
    curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 60); /* 在发起连接前等待的时间，如果设置为0，则无限等待 */
    curl_setopt($ci, CURLOPT_TIMEOUT, 7); /* 设置cURL允许执行的最长秒数 */
    curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
    switch ($method) {
        case "POST":
            curl_setopt($ci, CURLOPT_POST, true);
            if (!empty($postfields)) {
                $tmpdatastr = is_array($postfields) ? http_build_query($postfields) : $postfields;
                curl_setopt($ci, CURLOPT_POSTFIELDS, $tmpdatastr);
            }
            break;
        default:
            curl_setopt($ci, CURLOPT_CUSTOMREQUEST, $method); /* //设置请求方式 */
            break;
    }
    $ssl = preg_match('/^https:\/\//i', $url) ? TRUE : FALSE;
    curl_setopt($ci, CURLOPT_URL, $url);
    if ($ssl) {
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, FALSE); // https请求 不验证证书和hosts
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, FALSE); // 不从证书中检查SSL加密算法是否存在
    }

    if ($header_out) {
        curl_setopt($ci, CURLOPT_HEADER, true); /*启用时会将头文件的信息作为数据流输出*/
    }
    curl_setopt($ci, CURLOPT_FOLLOWLOCATION, TRUE);
    curl_setopt($ci, CURLOPT_MAXREDIRS, 2); /* 指定最多的HTTP重定向的数量，这个选项是和CURLOPT_FOLLOWLOCATION一起使用的 */
    curl_setopt($ci, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ci, CURLINFO_HEADER_OUT, true);
    curl_setopt($ci, CURLOPT_COOKIE, $Cookiestr); /* *COOKIE带过去** */
    $response = curl_exec($ci);

    preg_match_all('/Set-Cookie:(.*);/iU', $response, $cookies); //正则匹配

    $response_list = explode(PHP_EOL . PHP_EOL, $response);

    $requestinfo = curl_getinfo($ci);
    $http_code   = curl_getinfo($ci, CURLINFO_HTTP_CODE);
    if ($debug) {
        echo "=====post data======\r\n";
        var_dump($postfields);
        echo "=====info===== \r\n";
        print_r($requestinfo);

        echo "=====response=====\r\n";
        print_r($response);
    }
    curl_close($ci);
    if ($header_out) {
        return array($http_code, $response_list[count($response_list) - 1], $requestinfo, $cookies[1]);
    } else {
        return array($http_code, $response, $requestinfo, $cookies[1]);
    }

}

/**
 * @desc 根据两点间的经纬度计算距离
 * @param float $lat 纬度值
 * @param float $lng 经度值
 */
function getDistance($lat1, $lng1, $lat2, $lng2)
{
    $earthRadius = 6367000;
    $lat1        = ($lat1 * pi()) / 180;
    $lng1        = ($lng1 * pi()) / 180;

    $lat2 = ($lat2 * pi()) / 180;
    $lng2 = ($lng2 * pi()) / 180;

    $calcLongitude      = $lng2 - $lng1;
    $calcLatitude       = $lat2 - $lat1;
    $stepOne            = pow(sin($calcLatitude / 2), 2) + cos($lat1) * cos($lat2) * pow(sin($calcLongitude / 2), 2);
    $stepTwo            = 2 * asin(min(1, sqrt($stepOne)));
    $calculatedDistance = $earthRadius * $stepTwo;
    return round($calculatedDistance);
}

function getRange($range, $space = true, $is_ch = false)
{
    if ($range < 1000) {
        return $range . ($space ? ' ' : '') . ($is_ch ? L_('米') : 'm');
    } else {
        return floatval(round($range / 1000, 2)) . ($space ? ' ' : '') . ($is_ch ? L_('公里') : 'km');
    }
}

/**
 * 指点的经纬是否在多边形的地图内
 * @param float $lng
 * @param float $lat
 * @param array $latLngData
 * @return boolean
 */
function isPtInPoly($lng, $lat, $latLngData)
{
    foreach ($latLngData as $latLng) {
        $iCount = count($latLng);
        if ($iCount < 3) continue;
        $iSum = 0;
        for ($i = 0; $i < $iCount; $i++) {
            if ($i == $iCount - 1) {
                $dLon1 = $latLng[$i]['lng'];
                $dLat1 = $latLng[$i]['lat'];
                $dLon2 = $latLng[0]['lng'];
                $dLat2 = $latLng[0]['lat'];
            } else {
                $dLon1 = $latLng[$i]['lng'];
                $dLat1 = $latLng[$i]['lat'];
                $dLon2 = $latLng[$i + 1]['lng'];
                $dLat2 = $latLng[$i + 1]['lat'];
            }
            //以下语句判断A点是否在边的两端点的水平平行线之间，在则可能有交点，开始判断交点是否在左射线上
            if ((($lat >= $dLat1) && ($lat < $dLat2)) || (($lat >= $dLat2) && ($lat < $dLat1))) {
                if (abs($dLat1 - $dLat2) > 0) {
                    //得到 A点向左射线与边的交点的x坐标：
                    $dLon = $dLon1 - (($dLon1 - $dLon2) * ($dLat1 - $lat)) / ($dLat1 - $dLat2);
                    if ($dLon < $lng) $iSum++;
                }
            }
        }
        if ($iSum % 2 != 0) return true;
    }
    return false;
}


//得到带URL的链接
//支持最多5个参数
function UU()
{
    switch (func_num_args()) {
        case 0:
            return C('config.config_site_url');
        case 1:
            return C('config.config_site_url') . U(func_get_arg(0));
        case 2:
            return C('config.config_site_url') . U(func_get_arg(0), func_get_arg(1));
        case 3:
            return C('config.config_site_url') . U(func_get_arg(0), func_get_arg(1), func_get_arg(2));
        case 4:
            return C('config.config_site_url') . U(func_get_arg(0), func_get_arg(1), func_get_arg(2), func_get_arg(3));
        case 5:
            return C('config.config_site_url') . U(func_get_arg(0), func_get_arg(1), func_get_arg(2), func_get_arg(3), func_get_arg(4));
    }
}

//通用的加密数组串
function get_butt_encrypt_key($butt_array, $butt_key, $only_key = false)
{
    $new_arr = array();
    if (empty($butt_array['encrypt_time'])) {
        $butt_array['encrypt_time'] = $_SERVER['REQUEST_TIME'];        //为了多页面能调到统一的时候，采用SERVER中的REQUEST_TIME。
    }
    ksort($butt_array);
    foreach ($butt_array as $key => $value) {
        $new_arr[] = $key . '=' . $value;
    }
    $new_arr[] = 'butt_key=' . $butt_key;

    $string = implode('&', $new_arr);
    if ($only_key) {
        return md5($string);
    } else {
        $butt_array['encrypt_key'] = md5($string);
        return $butt_array;
    }
}

//转换Wap下的LBS链接
function wapLbsTranform($url, $param = array(), $returnLbs = false)
{
    if (stripos($url, 'LBS://') !== FALSE) {
        $url           = parse_url($url);
        $long_lat      = explode(',', $url['host']);
        $param['long'] = $long_lat[0];
        $param['lat']  = $long_lat[1];

        if (defined('IS_INDEP_HOUSE')) {
            $url = C('config.site_url') . '/wap_house.php?c=Lbs&a=show&' . http_build_query($param);
        } else {
            $url = C('config.site_url') . '/wap.php?c=Lbs&a=show&' . http_build_query($param);
        }


        if ($returnLbs) {
            $return['url']  = $url;
            $return['long'] = $param['long'];
            $return['lat']  = $param['lat'];
            return $return;
        }
    }
    return $url;
}

//查询数据整理递归函数（无限制级别）
function arrayPidProcess($data, $res = array(), $pid = '0', $endlevel = '0')
{
    foreach ($data as $k => $value) {
        /**********控制商家的菜单显示************/
        if ($value['fid'] == $pid) {
            $select_module = explode(',', $value['select_module']);
            $select_action = explode(',', $value['select_action']);
            if (in_array(MODULE_NAME, $select_module) && (empty($value['select_action']) || in_array(ACTION_NAME, $select_action))) {
                $value['is_active'] = true;
            }
            if ($value['module']) {
                $value['url'] = U($value['module'] . '/' . $value['action']);
            }
            $res[$value['id']] = $value;
            unset($data[$k]);
            if ($endlevel != '0') {
                if ($value['level'] != $endlevel) {
                    $child = arrayPidProcess($data, array(), $value['id'], $endlevel);
                }
                $res[$value['id']]['menu_list'] = $child;
            } else {
                $child = arrayPidProcess($data, array(), $value['id']);
                if (!($child == '' || $child == null)) {
                    $res[$value['id']]['menu_list'] = $child;
                }
            }
        }
    }

    return $res;
}

//查询数据整理递归函数（无限制级别）
function arrayPidHouseProcess($data, $res = array(), $pid = '0', $endlevel = '0')
{
    foreach ($data as $k => $value) {
        /**********控制商家的菜单显示************/
        if ($value['fid'] == $pid) {
            $select_module = explode(',', $value['select_module']);
            $select_action = explode(',', $value['select_action']);
            $choose_page   = explode(',', $value['choose_page']);

            if ($choose_page && in_array(MODULE_NAME . '#' . ACTION_NAME, $choose_page)) {
                $value['is_active'] = true;
            } elseif (in_array(MODULE_NAME, $select_module) && in_array(ACTION_NAME, $select_action)) {
                $value['is_active'] = true;
            } elseif (empty($choose_page) && in_array(MODULE_NAME, $select_module) && (empty($value['select_action']) || in_array(ACTION_NAME, $select_action))) {
                $value['is_active'] = true;
            }
            $res[$value['id']] = $value;
            unset($data[$k]);
            if ($endlevel != '0') {
                if ($value['level'] != $endlevel) {
                    $child = arrayPidHouseProcess($data, array(), $value['id'], $endlevel);
                }
                $res[$value['id']]['menu_list'] = $child;
            } else {
                $child = arrayPidHouseProcess($data, array(), $value['id']);
                if (!($child == '' || $child == null)) {
                    $res[$value['id']]['menu_list'] = $child;
                }
            }
        }
    }

    return $res;
}


function uniqid_rand()
{
    return uniqid() . mt_rand(100, 999);
}

function sortArrayAsc($preData, $sortType = 'price')
{
    $sortData = array();
    foreach ($preData as $key_i => $value_i) {
        $price_i              = $value_i[$sortType];
        $value_i['array_key'] = $key_i;
        $min_key              = '';
        $sort_total           = count($sortData);
        foreach ($sortData as $key_j => $value_j) {
            if ($price_i < $value_j[$sortType]) {
                $min_key = $key_j + 1;
                break;
            }
        }
        if (empty($min_key)) {
            array_push($sortData, $value_i);
        } else {
            $sortData1 = array_slice($sortData, 0, $min_key - 1);
            array_push($sortData1, $value_i);
            if (($min_key - 1) < $sort_total) {
                $sortData2 = array_slice($sortData, $min_key - 1);
                foreach ($sortData2 as $value) {
                    array_push($sortData1, $value);
                }
            }
            $sortData = $sortData1;
        }
    }
    return $sortData;
}

//二维数组排序
function sortArray($arrays, $sort_key, $sort_order = SORT_ASC, $sort_type = SORT_NUMERIC)
{
    if (is_array($arrays)) {
        foreach ($arrays as $array) {
            if (is_array($array)) {
                $key_arrays[] = $array[$sort_key];
            } else {
                return $arrays;
            }
        }
    } else {
        return $arrays;
    }
    array_multisort($key_arrays, $sort_order, $sort_type, $arrays);
//    var_dump($arrays);
    return $arrays;
}

/**
 * 调试数据的本地保存
 *
 * <code>
 * // O2O缓存目录在网站根目录下的runtime文件夹
 * // 简单的调试
 * fdump($arr); 会在缓存目录下保存一个  test_fdump.php 的文件
 * // 自定义文件名的调试
 * fump($arr,'custom'); 会在缓存目录下替换保存一个  custom_fdump.php 的文件
 * // 追加到文件中的调试
 * fump($arr,'custom',true); 会在缓存目录下保存一个  custom_fdump.php 的文件 在文件末尾追加内容
 * </code>
 *
 * @access public
 * @param string|array $data 进行调试的数据
 * @param string $filename 调试文件的文件名，后面会自动追加 _fdump.php，方便文件存储的分类辨别
 * @param string $append 是否采用追加的模式，默认不采用、覆盖文件
 * @return string
 */
function fdump($data, $filename = 'test', $append = false)
{
    empty($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT'] = dirname($_SERVER['SCRIPT_FILENAME']);
    if (strpos($filename, '/') > 0) {
        $fileName = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . $filename . '_fdump.php';
        $dirName  = dirname($fileName);
        if (!file_exists($dirName)) {
            @mkdir($dirName, 0777, true);
            @chown($dirName,'www');
            @chgrp($dirName,'www');
        }
    } else {
        $fileName = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/runtime/' . $filename . '_fdump.php';
    }
    $debug_trace = debug_backtrace();
    $file        = __FILE__;
    $line        = "unknown";
    if (isset($debug_trace[0]) && isset($debug_trace[0]['file'])) {
        $file = $debug_trace[0]['file'];
        $line = $debug_trace[0]['line'];
    }
    $f_l = '[' . $file . ' : ' . $line . ']';

    if(!file_exists($fileName)){
        @file_put_contents($fileName,'<?php');
    }
    @chmod($fileName,0777);
    @chown($dirName,'www');
    @chgrp($dirName,'www');
    
    if ($append) {
        @file_put_contents($fileName, PHP_EOL . $f_l . PHP_EOL . date('Y-m-d H:i:s') . ' ' . $_SERVER['REQUEST_URI'] . PHP_EOL . var_export($data, true) . PHP_EOL, FILE_APPEND);
    } else {
        @file_put_contents($fileName, '<?php' . PHP_EOL . date('Y-m-d H:i:s') . ' ' . $f_l . PHP_EOL . $_SERVER['REQUEST_URI'] . PHP_EOL . var_export($data, true));
    }
}

function fdump_html($data, $filename = 'test', $append = false)
{
    if (strpos($filename, '/') > 0) {
        $fileName = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/' . $filename . '_fdump.html';
        $dirName  = dirname($fileName);
        if (!file_exists($dirName)) {
            @mkdir($dirName, 0777, true);
            @chown($dirName,'www');
            @chgrp($dirName,'www');
        }
    } else {
        $fileName = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/runtime/' . $filename . '_fdump.html';
    }
    if(!file_exists($fileName)){
        @file_put_contents($fileName, '<meta charset="utf-8"/>');
    }
    @chmod($fileName,0777);
    @chown($fileName,'www');
    @chgrp($fileName,'www');
    if ($append) {
        @file_put_contents($fileName, '<pre>日志生成时间：' . date('Y-m-d H:i:s') . PHP_EOL . PHP_EOL . var_export($data, true) . '</pre><br/><br/>', FILE_APPEND);
    } else {
        @file_put_contents($fileName, '<meta charset="utf-8"/><pre>日志生成时间：' . date('Y-m-d H:i:s') . PHP_EOL . PHP_EOL . var_export($data, true) . '</pre>');
    }
}

/**
 * 调试数据的API目录长时间保存
 *
 * <code>
 * // 简单的调试
 * fdump($arr); 会在缓存目录下保存一个  test_fdump.php 的文件
 * // 自定义文件名的调试
 * fump($arr,'custom'); 会在缓存目录下替换保存一个  custom_fdump.php 的文件
 * // 追加到文件中的调试
 * fump($arr,'custom',true); 会在缓存目录下保存一个  custom_fdump.php 的文件 在文件末尾追加内容
 * // 创建目录的调试
 * fump($arr, 'test/custom'); 会在缓存目录下创建一个test目录，且保存一个  custom_fdump.php 的文件
 * </code>
 *
 * @access public
 * @param string|array $data 进行调试的数据
 * @param string $filename 调试文件的文件名，后面会自动追加 _fdump.php，方便文件存储的分类辨别
 * @param string $append 是否采用追加的模式，默认不采用、覆盖文件
 * @return string
 */
function fdump_api($data, $filename = 'test', $append = false)
{
    $fileName = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/api/log/' . date('Ymd') . '/' . $filename . '_fdump.php';
    $dirName  = dirname($fileName);
    if (!file_exists($dirName)) {
        @mkdir($dirName, 0777, true);
        @chown($dirName,'www');
        @chgrp($dirName,'www');
    }
    $debug_trace = debug_backtrace();
    $file        = __FILE__;
    $line        = "unknown";
    if (isset($debug_trace[0]) && isset($debug_trace[0]['file'])) {
        $file = $debug_trace[0]['file'];
        $line = $debug_trace[0]['line'];
    }
    $f_l = '[' . $file . ' : ' . $line . ']';

    if(!file_exists($fileName)){
        @file_put_contents($fileName,'<?php');
    }
    @chmod($fileName,0777);
    @chown($fileName,'www');
    @chgrp($fileName,'www');
    
    if ($append) {
        @file_put_contents($fileName, PHP_EOL . $f_l . PHP_EOL . date('Y-m-d H:i:s') . ' ' . $_SERVER['REQUEST_URI'] . PHP_EOL . var_export($data, true) . PHP_EOL, FILE_APPEND);
    } else {
        @file_put_contents($fileName, '<?php' . PHP_EOL . date('Y-m-d H:i:s') . ' ' . $f_l . PHP_EOL . $_SERVER['REQUEST_URI'] . PHP_EOL . var_export($data, true));
    }
}

/**
 * 调试数据的数据库保存
 *
 *
 * @access public
 * @param string $data 调试数据
 * @param string $filename 调试名称
 * @return string
 */
function fdump_sql($data, $name)
{
    $data       = var_export($data, true);
    $data_fdump = array(
        'data' => $data,
        'name' => $name,
        'time' => time(),
    );
    M('Fdump_sql')->data($data_fdump)->add();
}


/**
 * 计算给定时间戳与当前时间相差的时间，并以一种比较友好的方式输出
 * @param  [int] $timestamp [给定的时间戳]
 * @param  [int] $current_time [要与之相减的时间戳，默认为当前时间]
 * @return [string]            [相差天数]
 */
function tmspan($timestamp, $current_time = 0)
{
    if (!$current_time) $current_time = time();
    $span = $current_time - $timestamp;
    if ($span < 60) {
        return "刚刚";
    } else if ($span < 3600) {
        return intval($span / 60) . "分钟前";
    } else if ($span < 24 * 3600) {
        return intval($span / 3600) . "小时前";
    } else if ($span < (7 * 24 * 3600)) {
        return intval($span / (24 * 3600)) . "天前";
    } else {
        return date('Y-m-d', $timestamp);
    }
}

function getAttachmentUrl($fileUrl, $is_remote = true)
{

    if (empty($fileUrl)) {
        return '';
    } else {
        // 如果已经是完整url地址，则不做处理
        if (strstr($fileUrl, 'http://') !== false) {
            return replace_file_domain($fileUrl);
        }
        if (strstr($fileUrl, 'https://') !== false) {
            return replace_file_domain($fileUrl);
        }

        $attachment_upload_type = C('config.attachment_upload_type');
        $url                    = file_domain() . '/upload/';

        // 如果当前路径中已有upload，将不增加此路径
        if (strstr($fileUrl, 'upload/') !== false) {
            $url = file_domain() . '/';
        }

        if ($attachment_upload_type == '1' && $is_remote) {
            $url = 'http://' . C('config.attachment_up_domainname') . '/';
        }

        return $url . $fileUrl;
    }
}

function getFormatNumber($number, $num = 2, $allowNegative = true)
{
    if(empty($allowNegative) && $number < 0){
        return 0;
    }
    $number = number_format($number, $num);
    if (strpos($number, '.') !== false) {
        $number = rtrim($number, '0');
        $number = rtrim($number, '0');
        $number = rtrim($number, '.');
    }
    $number = str_replace(',', '', $number);

    if ($number === '') {
        $number = '0';
    }

    return $number;
}

/* param，多个参数使用英文逗号分隔 */
function removeUrlParam($url, $param)
{
    if (substr($url, 0, 7) != 'http://' && substr($url, 0, 8) != 'https://') {
        $url = 'http://' . $url;
    }

    $parts = parse_url($url);

    if ($parts['query']) {
        parse_str($parts['query'], $queryArr);
    } else {
        return $url;
    }

    $paramArr = explode(',', $param);

    $newQueryArr = array();
    foreach ($queryArr as $queryKey => $queryValue) {
        if (!in_array($queryKey, $paramArr)) {
            $newQueryArr[$queryKey] = $queryValue;
        }
    }

    $url = $parts['scheme'] . '://' . $parts['host'] . ($parts['port'] ? ':' . $parts['port'] : '') . $parts['path'] . '?' . http_build_query($newQueryArr) . '#' . $parts['fragment'];

    return $url;
}

function long2short_url($token, $url)
{
    $short_date['action']       = 'long2short';
    $short_date['long_url']     = $url;
    $short_date['access_token'] = $token;

    $json   = json_encode($short_date, JSON_UNESCAPED_UNICODE);
    $return = httpRequest('https://api.weixin.qq.com/cgi-bin/shorturl?access_token=' . $token, 'post', $json);

    $short_url = json_decode($return[1], true);
    return $short_url['short_url'];
}

/**
 * 计算给定时间戳加一定时间加减月份
 * @param  [int] $timestamp [给定的时间戳]
 * @param  [int] $plusMonth [要与之相加减的月份]
 * @param  [int] $plus [true 为加 false 为减]
 * @return [string]            [获取的时间戳]
 */

function plus_time($timestamp = '', $plusMonth = 1, $plus = true)
{
    if (!$timestamp) {
        $timestamp = time();
    }
    // 获取需要计算的年月份
    $time = date("Y-m", $timestamp);
    // 获取需要计算的时间的日期
    $day    = date("d", $timestamp);
    $endDay = date("d", strtotime(" +1 month -1 hour", $time));
    // 计算月份
    if ($plus) {
        $plusTime = date("Y-m", strtotime($time . " +" . $plusMonth . " month"));
    } else {
        $plusTime = date("Y-m", strtotime($time . " -" . $plusMonth . " month"));
    }
    $plusday = date("d", strtotime($plusTime . " +1 month -1 hour"));

    if (intval($day) == intval($endDay)) {
        $date = $plusTime . '-' . $plusday;
    } elseif ((int)$day > (int)$plusday) {
        $date = $plusTime . '-' . $plusday;
    } else {
        $date = $plusTime . '-' . $day;
    }
    $date = strtotime($date);
    return $date;
}


/**
 * 计算给定时间戳的显示信息
 * @param  [int] $timestamp [给定的时间戳]
 * @param  [string] $str [显示的动作，可以为空]
 * @param  [bool] $showHour [是否显示小时分钟，默认为不显示]
 * @return [string]            [获取的时间显示]
 */
// 时间显示处理
function time_info($timestamp, $str = '', $showHour = false)
{
    if (!$timestamp) {
        $timestamp = time();
    }
    // 取今天的时间范围
    $now_start_time = strtotime(date('Y-m-d', time()) . " 00:00:00");
    $now_end_time   = strtotime(date('Y-m-d', time()) . " 23:59:59");
    if (intval($timestamp) >= intval($now_start_time) && intval($timestamp) <= intval($now_start_time)) {
        $time_info = date('H:i', $timestamp) . $str;
        return $time_info;
    }
    // 取昨天的时间范围
    $yesterday_start_time = strtotime("-1 day", $now_start_time);
    $yesterday_end_time   = strtotime("-1 day", $now_end_time);
    if (intval($timestamp) >= intval($yesterday_start_time) && intval($timestamp) <= intval($yesterday_end_time)) {
        $time_info = $showHour ? '昨天 ' . date('H:i', $timestamp) . $str : '昨天' . $str;
        return $time_info;
    }
    // 取前天的时间范围
    $week_start_time = strtotime("-2 day", $now_start_time);
    $week_end_time   = strtotime("-2 day", $now_end_time);
    if (intval($timestamp) >= intval($week_start_time) && intval($timestamp) <= intval($week_end_time)) {
        $weekarray = array("日", "一", "二", "三", "四", "五", "六");
        $w         = date("w", $timestamp);
        $time_info = $showHour ? '周' . $weekarray[$w] . date('H:i', $timestamp) . $str : '周' . $weekarray[$w] . $str;
        return $time_info;
    }
    // 获取年份
    $now_year = date('Y');
    $year     = date('Y', $timestamp);
    if (intval($now_year) == intval($year)) {
        $time_info = $showHour ? date('m月d日 H:i', $timestamp) . $str : date('m月d日', $timestamp) . $str;
    } else {
        $time_info = $showHour ? date('Y年m月d日 H:i', $timestamp) . $str : date('Y年m月d日', $timestamp) . $str;
    }
    return $time_info;
}

//获取微信头像到本地
function put_file_from_url_content($url, $save_dir = '', $filename = '')
{
    //根据url获取远程文件
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 500);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);

    curl_setopt($curl, CURLOPT_URL, $url);

    $res = curl_exec($curl);
    curl_close($curl);
    //把图片保存到指定目录下的指定文件
    $result = file_put_contents($save_dir . $filename, $res);
    if ($result) {
        return array(
            'file_name' => $filename,
            'save_path' => $save_dir . $filename,
            'error'     => 0
        );
    } else {
        return false;
    }
}

/**
 * 人民币小写转大写
 *
 * @param string $number 数值
 * @param string $int_unit 币种单位，默认"元"，有的需求可能为"圆"
 * @param bool $is_round 是否对小数进行四舍五入
 * @param bool $is_extra_zero 是否对整数部分以0结尾，小数存在的数字附加0,比如1960.30，
 *             有的系统要求输出"壹仟玖佰陆拾元零叁角"，实际上"壹仟玖佰陆拾元叁角"也是对的
 * @return string
 */
function cny($number = 0, $int_unit = '圆', $is_round = TRUE, $is_extra_zero = FALSE)
{
    // 将数字切分成两段
    $parts = explode('.', $number, 2);
    $int   = isset($parts[0]) ? strval($parts[0]) : '0';
    $dec   = isset($parts[1]) ? strval($parts[1]) : '';

    // 如果小数点后多于2位，不四舍五入就直接截，否则就处理
    $dec_len = strlen($dec);
    if (isset($parts[1]) && $dec_len > 2) {
        $dec = $is_round
            ? substr(strrchr(strval(round(floatval("0." . $dec), 2)), '.'), 1)
            : substr($parts[1], 0, 2);
    }

    // 当number为0.001时，小数点后的金额为0元
    if (empty($int) && empty($dec)) {
        return '零';
    }

    // 定义
    $chs     = array('0', '壹', '贰', '叁', '肆', '伍', '陆', '柒', '捌', '玖');
    $uni     = array('', '拾', '佰', '仟');
    $dec_uni = array('角', '分');
    $exp     = array('', '万');
    $res     = '';

    // 整数部分从右向左找
    for ($i = strlen($int) - 1, $k = 0; $i >= 0; $k++) {
        $str = '';
        // 按照中文读写习惯，每4个字为一段进行转化，i一直在减
        for ($j = 0; $j < 4 && $i >= 0; $j++, $i--) {
            $u   = $int{$i} > 0 ? $uni[$j] : ''; // 非0的数字后面添加单位
            $str = $chs[$int{$i}] . $u . $str;
        }
        //echo $str."|".($k - 2)."<br>";
        $str = rtrim($str, '0');// 去掉末尾的0
        $str = preg_replace("/0+/", "零", $str); // 替换多个连续的0
        if (!isset($exp[$k])) {
            $exp[$k] = $exp[$k - 2] . '亿'; // 构建单位
        }
        $u2  = $str != '' ? $exp[$k] : '';
        $res = $str . $u2 . $res;
    }

    // 如果小数部分处理完之后是00，需要处理下
    $dec = rtrim($dec, '0');

    // 小数部分从左向右找
    if (!empty($dec)) {
        if ($res) {
            $res .= $int_unit;
        }
        // 是否要在整数部分以0结尾的数字后附加0，有的系统有这要求
        if ($is_extra_zero) {
            if (substr($int, -1) === '0') {
                $res .= '零';
            }
        }

        for ($i = 0, $cnt = strlen($dec); $i < $cnt; $i++) {
            if ($dec{$i} > 0) {
                $u   = $dec{$i} > 0 ? $dec_uni[$i] : ''; // 非0的数字后面添加单位
                $res .= $chs[$dec{$i}] . $u;
            }
        }
        $res = rtrim($res, '0');// 去掉末尾的0
        $res = preg_replace("/0+/", "零", $res); // 替换多个连续的0
    } else {
        if ($res) {
            $res .= $int_unit . '整';
        } else {

            $res = '零' . $int_unit . '整';
        }
    }
    return $res;
}

/**
 * RGB转 十六进制
 * @param $rgb RGB颜色的字符串 如：rgb(255,255,255);
 * @return string 十六进制颜色值 如：#FFFFFF
 */
function RGBToHex($rgb)
{
    $regexp = "/^rgba\(([0-9]{0,3})\,\s*([0-9]{0,3})\,\s*([0-9]{0,3})\,\s*(.[0-9]{0,3})\)/";
    $re = preg_match($regexp, $rgb, $matchs);
    if($matchs){//rgba
        $match = $matchs;
    }else{
        $regexp   = "/^rgb\(([0-9]{0,3})\,\s*([0-9]{0,3})\,\s*([0-9]{0,3})\)/";
        preg_match($regexp, $rgb, $match);
    }
    $re       = array_shift($match);
    $hexColor = "#";
    $hex      = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F');
    for ($i = 0; $i < 3; $i++) {
        $r     = null;
        $c     = $match[$i];
        $hexAr = array();
        while ($c > 16) {
            $r = $c % 16;
            $c = ($c / 16) >> 0;
            array_push($hexAr, $hex[$r]);
        }
        array_push($hexAr, $hex[$c]);
        $ret      = array_reverse($hexAr);
        $item     = implode('', $ret);
        $item     = str_pad($item, 2, '0', STR_PAD_LEFT);
        $hexColor .= $item;
    }
    return $hexColor;
}

function safeValue($value)
{
    $value = htmlspecialchars($value);

    $filterArr = array(
        'if(',
        'sleep(',
        '=',
        ' and ',
        ' or ',
        'select ',
        'delete ',
        'update ',
        'insert ',
    );

    $value = str_ireplace($filterArr, '', $value);
    return $value;
}


//单位M
function file_max_size($size)
{
    $max_size = ini_get('post_max_size');
    $max_size = str_replace('M', '', $max_size);
    return $max_size > $size ? $size : $max_size;
}

// 替换字符串中文字
function str_replace_name($name)
{
    $length = utf8_strlen($name);
    if ($length == 2) {
        $name = preg_replace("/(.).{1,1}(.*)/iu", "$1*$2", $name);
    } elseif ($length == 3) {
        $name = preg_replace("/(.).{1,1}(.*)/iu", "$1*$2", $name);
    } elseif ($length == 4) {
        $name = preg_replace("/(.).{1,2}(.*)/iu", "$1**$2", $name);
    } elseif ($length == 5) {
        $name = preg_replace("/(.).{1,4}(.*)/iu", "$1***$2", $name);
    } elseif ($length >= 6) {
        $name = preg_replace("/(.).{1,4}(.*)/iu", "$1****$2", $name);
    }
    return $name;
}

// 计算中文字符串长度
function utf8_strlen($string = null)
{
    // 将字符串分解为单元
    preg_match_all("/./us", $string, $match);
    // 返回单元个数
    return count($match[0]);
}

function generate_password($length = 8)
{
// 密码字符集，可任意添加你需要的字符
    $chars    = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[mt_rand(0, strlen($chars) - 1)];
    }
    return $password;
}

//获得yyyyMMddHHmmssSSS 格式时间
function get_millisecond($is_z = false)
{

    list($usec, $sec) = explode(" ", microtime());

    $msec    = round($usec * 1000);
    $cn_time = date('YmdHis', time()) . $msec;

    $jhm = $cn_time;

    $is_z && $jhm . '+0800';
    return $jhm;

}

function createRandNumber($length = 32)
{
    $chars = "0123456789";
    $str   = "";
    for ($i = 0; $i < $length; $i++) {
        $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    }
    return $str;
}

//上传文件目录{pigcms{:file_domain()}
function file_domain()
{
    static $static_file_domain;
    if (empty($static_file_domain)) {
        if (C('config.static_oss_switch') && C('config.static_oss_access_domain_names')) { //阿里云
            $static_file_domain = $_SERVER['REQUEST_SCHEME'] . '://' . C('config.static_oss_access_domain_names');
        } else if (C('config.static_obs_switch') && C('config.static_obs_access_domain_names')) {  //华为云
            $static_file_domain = $_SERVER['REQUEST_SCHEME'] . '://' . C('config.static_obs_access_domain_names');
        } else if (C('config.static_cos_switch') && C('config.static_cos_region')) {  //腾讯云
            $static_file_domain = $_SERVER['REQUEST_SCHEME'] . '://' . C('config.static_cos_access_domain_names');
        } else {
            $static_file_domain = C('config.site_url');
            if (is_ssl()) {
                $static_file_domain = str_replace('http://','https://',$static_file_domain);
            }
        }
    }
    return $static_file_domain;
}

/**
 * 获取完整url地址,默认缩放裁剪显示
 * @param $url
 */
function get_full_image_url($url, $thumb = true, $w = 240, $h = 240, $resize = true)
{
    $url = $url && substr($url, 0, 4) == 'http' ? $url : ($url ? file_domain() . $url : '');
    if ($thumb && $url) {
        $thumbUrl = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/index.php?g=Index&c=Image&a=thumb';
        $thumbUrl .= '&url=' . urlencode($url);
        $thumbUrl .= '&width=' . $w;
        $thumbUrl .= '&height=' . $h;
        if ($resize) {
            $thumbUrl .= '&resize=fill';
        }
        return $thumbUrl;
    } else {
        return $url;
    }
}

//替换上传文件目录
function replace_file_domain($url)
{
    if (empty($url)) {
        return '';
    }
    if (C('config.static_oss_access_domain_names') && strpos($url, C('config.static_oss_access_domain_names') . '/upload/') !== false) {
        if (C('config.use_https') && stripos($url, 'http://') !== false) {
            $url = 'https://' . substr($url, 7);
        }
        return $url;
    }
    if (C('config.static_obs_access_domain_names') && strpos($url, C('config.static_obs_access_domain_names') . '/upload/') !== false) {
        if (C('config.use_https') && stripos($url, 'http://') !== false) {
            $url = 'https://' . substr($url, 7);
        }
        return $url;
    }
    $stripos_result = stripos($url, './upload/');
    if ($stripos_result === 0) {
        $url = C('config.site_url') . ltrim($url, '.');
    }
    $stripos_result = stripos($url, '/upload/');
    if ($stripos_result === 0) {
        $url = C('config.site_url') . $url;
    }
    $stripos_result = stripos($url, 'upload/');
    if ($stripos_result === 0) {
        $url = C('config.site_url') . '/' . $url;
    }
    //阿里云
    if ($stripos_result !== false && C('config.static_oss_switch') && C('config.static_oss_access_domain_names')) {
        $url = str_replace(C('config.site_url'), $_SERVER['REQUEST_SCHEME'] . '://' . C('config.static_oss_access_domain_names'), $url);
        $url = str_replace(C('config.upload_site_url'), $_SERVER['REQUEST_SCHEME'] . '://' . C('config.static_oss_access_domain_names'), $url);
        if (C('config.use_https') && stripos($url, 'http://') !== false) {
            $url = str_replace(str_replace('https://', 'http://', C('config.site_url')), $_SERVER['REQUEST_SCHEME'] . '://' . C('config.static_oss_access_domain_names'), $url);
            $url = str_replace(str_replace('https://', 'http://', C('config.upload_site_url')), $_SERVER['REQUEST_SCHEME'] . '://' . C('config.static_oss_access_domain_names'), $url);
        }
    }
    //腾讯云
    if ($stripos_result !== false && C('config.static_cos_switch') && C('config.static_cos_region')) {
        $url = str_replace(C('config.site_url'), $_SERVER['REQUEST_SCHEME'] . '://' . C('config.static_cos_access_domain_names'), $url);
        $url = str_replace(C('config.upload_site_url'), $_SERVER['REQUEST_SCHEME'] . '://' . C('config.static_cos_access_domain_names'), $url);
        if (C('config.use_https') && stripos($url, 'http://') !== false) {
            $url = str_replace(str_replace('https://', 'http://', C('config.site_url')), $_SERVER['REQUEST_SCHEME'] . '://' . C('config.static_cos_access_domain_names'), $url);
            $url = str_replace(str_replace('https://', 'http://', C('config.upload_site_url')), $_SERVER['REQUEST_SCHEME'] . '://' . C('config.static_cos_access_domain_names'), $url);
        }
    }
    //华为云
    if ($stripos_result !== false && C('config.static_obs_switch') && C('config.static_obs_access_domain_names')) {
        $url = str_replace(C('config.site_url'), $_SERVER['REQUEST_SCHEME'] . '://' . C('config.static_obs_access_domain_names'), $url);
        $url = str_replace(C('config.upload_site_url'), $_SERVER['REQUEST_SCHEME'] . '://' . C('config.static_obs_access_domain_names'), $url);
        if (C('config.use_https') && stripos($url, 'http://') !== false) {
            $url = str_replace(str_replace('https://', 'http://', C('config.site_url')), $_SERVER['REQUEST_SCHEME'] . '://' . C('config.static_obs_access_domain_names'), $url);
            $url = str_replace(str_replace('https://', 'http://', C('config.upload_site_url')), $_SERVER['REQUEST_SCHEME'] . '://' . C('config.static_obs_access_domain_names'), $url);
        }
    }

    return $url;
}


//兼容本地和云存储的展现缩略图
function thumb($img, $width, $height = 0, $resize = '')
{
    return thumb_img($img, $width, $height, $resize);
}

/**
 * 裁剪图片
 * @param $img     string    图片原路径
 * @param $width     int     裁剪的期望宽度
 * @param $height    int     裁剪的期望高度
 * @param $resize     string        fill固定宽高居中裁剪，其他值强制宽高压缩
 * @param $local_thumb_file   boolean    是返回图片路径，还是压缩图片的网址，返回图片路径可用于使用缩略图。仅对本地图片生效。
 * @param $maxfilesize   int    强制图片文件最大的大小，会再次自动循环每次减少30像素再次压缩图片尺寸，直至图片文件达到符合要求。例如海康人脸设备要求200kb以下的图片，像素又要尽量保存640。仅对本地图片生效。
 * @return array
 * @author: 彭家庭
 * @date: 2021/1/30
 */
function thumb_img($img, $width, $height = 0, $resize = '', $local_thumb_file = false, $maxfilesize = 0)
{
    if (!$img) {
        return '';
    }

    $img = replace_file_domain($img);
    //高度可以不传，自动使用宽度，识别为正方形
    if (!$height) {
        $height = $width;
    }
    if (stripos($img, '.gif') !== false) {
        return $img;
    }

    //检测图片是否已经包含压缩，若压缩则去除
    if (stripos($img, 'c=Image&a=thumb') !== false) {
        $url_arr = parse_url($img);
        if ($url_arr['query']) {
            $urlQuery = convertUrlQuery($url_arr['query']);
            if ($urlQuery['url']) {
                $img = urldecode($urlQuery['url']);
            }
        }
    }

    //阿里云
    if (C('config.static_oss_switch') && strpos($img, C('config.static_oss_access_domain_names') . '/upload/') !== false) {
        //检测图片是否已经包含压缩，若压缩则去除
        $tmp_img_arr = explode('?x-oss-process=', $img);
        if (count($tmp_img_arr) >= 2) {
            $img = $tmp_img_arr[0];
        }

        //如果是 fill
        if ($resize == 'fill') {
            return $img . '?x-oss-process=image/resize,m_fill,w_' . $width . ',h_' . $height;
        }
        return $img . '?x-oss-process=image/resize,w_' . $width . ',h_' . $height;
    }

    //腾讯云
    if (C('config.static_cos_switch') && strpos($img, C('config.static_cos_access_domain_names') . '/upload/') !== false) {
        //检测图片是否已经包含压缩，若压缩则去除
        $tmp_img_arr = explode('?imageView2', $img);
        if (count($tmp_img_arr) >= 2) {
            $img = $tmp_img_arr[0];
        }

        //如果是 fill
        if ($resize == 'fill') {
            return $img . '?imageView2/1/w/' . $width . '/h/' . $height;

        }
        return $img . '?imageView2/2/w/' . $width . '/h/' . $height;
    }

    //华为云
    if (C('config.static_obs_switch') && strpos($img, C('config.static_obs_access_domain_names') . '/upload/') !== false) {
        //检测图片是否已经包含压缩，若压缩则去除
        $tmp_img_arr = explode('?x-image-process=', $img);
        if (count($tmp_img_arr) >= 2) {
            $img = $tmp_img_arr[0];
        }

        //如果是 fill
        if ($resize == 'fill') {
            return $img . '?x-image-process=image/resize,m_fill,w_' . $width . ',h_' . $height;
        }
        return $img . '?x-image-process=image/resize,w_' . $width . ',h_' . $height;
    }

    if (strpos($img, '/index.php?c=Image&a=thumb') !== false) {
        return $img;
    }

    if ($local_thumb_file) {
        return D('Image')->thumb($img, $width, $height, $resize);
    }
    return C('config.site_url') . '/index.php?c=Image&a=thumb&url=' . urlencode($img) . '&width=' . $width . '&height=' . $height . '&resize=' . $resize . '&maxfilesize=' . $maxfilesize;
}

//替换富文本中的 OSS 图片
function replace_file_domain_content($content)
{
    //if(C('config.static_oss_switch') && C('config.static_oss_access_domain_names')){
    $src     = 'src="' . file_domain() . '/upload/';
    $content = str_replace('src="' . C('config.site_url') . '/upload/', $src, $content);
    $content = str_replace(str_replace('https', 'http', 'src="' . C('config.site_url') . '/upload/'), $src, $content);
    $content = str_replace('src="' . C('config.config_site_url') . '/upload/', $src, $content);
    $content = str_replace('src="/upload/', $src, $content);
    $content = str_replace('src=&quot;/upload/', $src, $content);
    $content = str_replace('src=&quot;' . C('config.config_site_url') . '/upload/', $src, $content);
    if (C('config.use_https') && stripos($content, 'http://') !== false) {
        $content = str_replace('src="' . str_replace('https://', 'http://', C('config.site_url')) . '/upload/', $src, $content);
        $content = str_replace('src="' . str_replace('https://', 'http://', C('config.config_site_url')) . '/upload/', $src, $content);
    }
    //embed标签视频无法播放，特殊处理type=video/x-ms-asf-plugin转换成video标签
    preg_match_all('/<embed(.*?)\/>/i', $content, $m);
    if ($m) {
        $search  = $m[0];
        $replace = $m[1];
        foreach ($search as $k => $v) {
            if (strpos($v, 'video/x-ms-asf-plugin') !== false) {
                $content = str_replace($v, '<video ' . $replace[$k] . ' controls="controls">您的浏览器不支持 video 标签' . '</video>', $content);
            }
        }
    }

    //}
    return $content;
}

//替换富文本里的图片为正常的，去除样式
function replace_file_domain_content_img($content)
{
    $content = str_replace("font-family:Lato, \"", '', $content);
    $content = str_replace("\" font-size:", 'font-size:', $content);
    $content = str_replace("=\"\"", '', $content);
    $content = preg_replace("/<img.*?src=(\"|\')(.*?)(\"|\').*?\/\>/is", '<img style="max-width:100%;vertical-align:top;" src="$2" />', $content);
    $content = preg_replace("/<video.*?src=(\"|\')(.*?)(\"|\').*?\>/is", '<video style="width:100%;height:calc(100vw / 16 * 9);vertical-align:top;" src="$2" controls preload="none" />', $content);
    $content = preg_replace("/<iframe.*?src=(\"|\')(.*?)(\"|\').*iframe?\>/is", '', $content);
    return replace_file_domain_content($content);
}

// 避免溢出的4舍5入共有方法 $number 具体数字 $precision 精确度即小数点后保留位数
function round_number($number, $precision = 2)
{
    $val = round($number, $precision); // 首先进行四舍五入
    $val = sprintf("%." . $precision . "f", $val); // 防止溢出
//    $val = floatval($val);  // 将字符串转换成数字
    return $val;
}

//多域名支持下替换域名为当前访问域名	字符串或数组都可以
function replace_domain($array)
{
    if (!$array) {
        return $array;
    }
    if (C('config.more_domain_visit') && C('config.config_site_url') != C('config.now_site_url')) {
        if (is_array($array)) {
            foreach ($array as $key => $value) {
                if (is_array($array[$key])) {
                    $array[$key] = replace_domain($value);
                } else {
                    $array[$key] = str_replace(C('config.config_site_url'), C('config.now_site_url'), $value);
                }
            }
        } else {
            $array = str_replace(C('config.config_site_url'), C('config.now_site_url'), $array);
        }
    }
    return $array;
}


/**
 * 获取语言展示
 * $key string 语言键名
 * $param array 支持多参数
 * return string ;
 */
function L_($key = '', $param = '', $js = false, $from = 0, $replace = 1, $filter = '')
{
    if (empty($key)) {
        return $key;
    }
    $open_multilingual = C('config.open_multilingual');//是否开启多语言
    $default_language  = C('config.default_language');//默认语言
    if (!$open_multilingual) {
        $result = $key;
    } else {
        $result         = '';
        $current_module = array();
        if (GROUP_NAME) {
            $current_module[] = GROUP_NAME;
        }
        if (MODULE_NAME) {
            $current_module[] = MODULE_NAME;
        }
        if (ACTION_NAME) {
            $current_module[] = ACTION_NAME;
        }
        $current_module_str = implode('|', $current_module);
        $key                = trim($key);

        $fields = C('config.tmp_system_lang') ? C('config.tmp_system_lang') : (C('config.system_lang') ? C('config.system_lang') : $default_language);//默认中文
        if (empty($fields)) return $key;


        $s_key = 'lang/php_get' . $key . $current_module_str . $fields;

        $result = S($s_key);
        if (!$result) {

            //引入繁体类
            import("@.ORG.ZhConvert");

            //这里将来可以做缓存
            $lang_db       = M("Lang");
            $where         = array();
            $where['key']  = $key;
            $where['from'] = $from;
            $lang          = $lang_db->where($where)->field('id,`key`,group_name,module_name,action_name,' . $fields)->select();
            //没找到当前数据，则插入一条数据
            if (empty($lang)) {
                if (!$js) {
                    $insert = array(
                        'key'         => $key,
                        'chinese'     => $key,
                        'traditional' => ZhConvert::zh($key),
                        'add_time'    => time(),
                        'from'        => $from,
                        'group_name'  => GROUP_NAME,
                        'module_name' => MODULE_NAME,
                        'action_name' => ACTION_NAME,
                    );
                } else {//如果是js代码上报的词语，不存它的GROUPNAME MODULENAME
                    $insert = array(
                        'key'         => $key,
                        'chinese'     => $key,
                        'from'        => $from,
                        'traditional' => ZhConvert::zh($key),
                        'add_time'    => time()
                    );
                }
                $lang_db->data($insert)->add();
                $result = $key;//方便后续替换参数
                S($s_key, $result);
            } else {
                $zero = $first = $second = $third = array();
                foreach ($lang as $k => $value) {
                    if ($value['action_name']) {
                        $third[$value['key'] . $value['group_name'] . '|' . $value['module_name'] . '|' . $value['action_name']] = $value[$fields];
                    }
                    if ($value['module_name']) {
                        $second[$value['key'] . $value['group_name'] . '|' . $value['module_name']] = $value[$fields];
                    }
                    if ($value['group_name']) {
                        $first[$value['key'] . $value['group_name']] = $value[$fields];
                    }
                    $zero[$value['key']] = $value[$fields];
                }

                $module3 = GROUP_NAME . '|' . MODULE_NAME . '|' . ACTION_NAME;
                $module2 = GROUP_NAME . '|' . MODULE_NAME;
                $module1 = GROUP_NAME;

                if (isset($third[$key . $module3])) {
                    $result = $third[$key . $module3];
                } elseif (isset($second[$key . $module2])) {
                    $result = $second[$key . $module2];
                } elseif (isset($first[$key . $module1])) {
                    $result = $first[$key . $module1];
                } else {
                    $result = $zero[$key];
                }

                S($s_key, $result);
            }
        }
        if (empty($result)) {
            $result = $key;
        }
    }
    if ($replace) {
        if (is_array($param)) {
            foreach ($param as $k => $v) {
                if (empty($v) && $v != '0') $v = '';
                $result = str_replace($k, $v, $result);//替换语言中的变量
            }
        } else {
            if (empty($param) && $param != '0') $param = '';
            $pattern = '/x1|X1/';
            $result  = preg_replace($pattern, $param, $result);//替换语言中的变量
        }
    }

    if ($filter && function_exists($filter)) {
        return $filter($result);
    }
    return $result;
}

/**
 * @param $key 多语言转换
 * @param $filter 过滤方法
 */
function LF_($key, $filter = 'addslashes')
{
    return L_($key, '', false, 0, 1, $filter);
}

function _view($model, $language = '')
{
    $open_multilingual = C('config.open_multilingual');//是否开启多语言
    $default_language  = C('config.default_language');//默认语言

    if (!$open_multilingual) {
        return $model;
    }

    if ($language) {
        $config_lang = $language;
    } else {
        // 用户当前语言
        $config_lang = C('config.system_lang');
    }

    $lang_db = PD('Lang');
    if (!in_array(strtolower($model), $lang_db->service_lang_table)) {
        die("LangModel.class.php中需要添加该表{$model}的映射.");
    }

    if (!C('config.tmp_system_lang') && $default_language == $config_lang) {
        return $model;
    }
    $tmp_system_lang = C('config.tmp_system_lang');
    $system_lang     = $tmp_system_lang ? '_' . $tmp_system_lang : ($config_lang ? '_' . $config_lang : '_' . $default_language);
    return $model . $system_lang . '_view';
}

//中文版的 trim
function mb_trim($string, $trim_chars = '\s')
{
    return preg_replace('/^[' . $trim_chars . ']*(?U)(.*)[' . $trim_chars . ']*$/u', '\\1', $string);
}

//格式化手机号隐藏展示
function phone_show($phone)
{
    $phone_show = strlen($phone) == 11 ? substr($phone, 0, 3) . '****' . substr($phone, 7, 4) : $phone;
    return $phone_show;
}

//格式化手机号存储
function phone_format($country, $phone)
{
    if ($country == '61') {
        // $phone = ltrim($phone,'+61');   // +6104 +614都可以去除 +61
        if (substr($phone, 0, 3) == '+61') {
            $phone = substr($phone, 3);
        }
        if (strlen($phone) > 10) {      // 6104是12位大于10，614是11位大于10
            // $phone = ltrim($phone,'61');
            if (substr($phone, 0, 2) == '61') {
                $phone = substr($phone, 2);
            }
        }
        $phone = '0' . ltrim($phone, '0');   // 如果前面没有0，加个0
    } else if ($country == '855') {
        // $phone = ltrim($phone,'+855');    // +85504 +8554都可以去除 +855
        if (substr($phone, 0, 4) == '+855') {
            $phone = substr($phone, 4);
        }
        if (strlen($phone) > 10) {      // 85504是12位大于10，8554是11位大于10
            // $phone = ltrim($phone,'855');
            if (substr($phone, 0, 3) == '855') {
                $phone = substr($phone, 3);
            }
        }
        $phone = '0' . ltrim($phone, '0');   // 如果前面没有0，加个0
    }

    return $phone;
}

function phone_demo_show($phone)
{
    if (C('config.not_o2o_demo')) {
        return phone_show($phone);
    } else {
        return $phone;
    }
}

//计算一周总共有几天营业
function get_week_count($data = array())
{
    $s   = count($data) - 1;
    $num = 0;
    for ($i = 0; $i <= $s; $i++) {
        if ($data[$i]['week_status'] == 1) {
            $num++;
        }
    }
    return $num;
}

//插件的公共Model类
function plugin_model($name)
{
    include_once WEB_PATH . 'plugin/plugin_public/model/' . $name . '.class.php';
    $plugin_class_name = $name;
    $class             = new $plugin_class_name();
    return $class;
}

//插件的ORG应用类库
//class  支持  xx.xx，代表目录
function plugin_import($plugin_name, $class, $ext = '.class.php')
{
    $class_arr     = explode('.', $class);
    $class_name    = array_pop($class_arr);
    $importBaseUrl = WEB_PATH . 'plugin/' . $plugin_name . '/Lib/ORG/' . ($class_arr ? implode('/', $class_arr) . '/' : '');
    import($class_name, $importBaseUrl, $ext);
}

//插件读取系统类
function PD($name)
{
    return D('plat://' . $name);
}

function convertUrlQuery($query)
{
    $queryParts = explode('&', $query);
    $params     = array();
    foreach ($queryParts as $param) {
        $item             = explode('=', $param);
        $params[$item[0]] = $item[1];
    }
    return $params;
}

function packapp_url($url, $now_user = array())
{
    $url = C('config.site_url') . '/packapp/' . $url;

    $url_arr = parse_url($url);

    if ($url_arr['query']) {
        $urlQuery = convertUrlQuery($url_arr['query']);
    } else {
        $urlQuery = array();
    }

    $urlQuery['now_city'] = C('config.now_city');

    if (C('config.system_lang')) {
        $urlQuery['now_lang'] = C('config.system_lang');
    }

    if ($now_user) {
        $from_device_id = $now_user['device_id'] ? $now_user['device_id'] : 'packapp';
        $ticket_arr     = ticket::create($now_user['uid'], $from_device_id, true);
        $from_ticket    = $ticket_arr['ticket'];

        $urlQuery['device_id'] = $from_device_id;
        $urlQuery['ticket']    = $from_ticket;
    }

    $url = $url_arr['scheme'] . '://' . $url_arr['host'] . $url_arr['path'] . '?' . http_build_query($urlQuery);

    return $url;
}

/**
 * 图片url转成base64编码图片内容
 * @param $image
 * @return string
 */
function base64_encode_image($image)
{
    $base64Image = '';
    $imageInfo   = getimagesize($image);
    $imageData   = file_get_contents($image);
    $base64Image = 'data:' . $imageInfo['mime'] . ';base64,' . chunk_split(base64_encode($imageData));
    return $base64Image;
}

/**
 * 将多语言字段转换(一次只支持一个字段)
 * @param string $field 字段名
 * @return string 转换后的字段名
 */
function _field($field = '')
{
    if (empty($field)) return $field;
    $open_multilingual = C('config.open_multilingual');//是否开启多语言
    $default_language  = C('config.default_language');//默认语言
    if (empty($open_multilingual)) {
        return $field;
    }
    $lang_db     = PD('Lang');
    $config_lang = C('config.system_lang');
    if ($default_language == $config_lang) {
        return $field;
    }
    return $field . '_' . $config_lang;
}

/**
 * 生成会话列表客服页面地址
 * @param $fromUser
 * @param array $params
 */
function build_store_im_conversation_url($fromUser, $relation='', $params = [], $isPc = false)
{
    if (empty($fromUser)) {
        throw new \Exception('参数有误');
    }
    if($isPc){
        $url = C('config.site_url') . '/packapp/customer_service_pc/chat?username=' . $fromUser . '&relation=' . $relation;
    }else{
        $url = C('config.site_url') . '/packapp/customer_service_h5/?from_user=' . $fromUser . '&relation=' . $relation;
    }

    $params['t'] = time();
    $params['s'] = md5($params['t'] . $fromUser . C('config.site_url'));
    
    foreach ($params as $k => $v) {
        $url .= '&' . $k . '=' . $v;
    }
    return $url;
}

function build_im_chat_url($fromUser, $toUser, $relation, $params = [])
{
    if (empty($fromUser) || empty($relation)) {
        throw new \Exception('参数有误');
    }
    $url = C('config.site_url') . '/packapp/customer_service_h5/pages/chat/conversations?from_user=' . $fromUser . '&to_user=' . $toUser . '&relation=' . $relation;
    //$url = C('config.site_url') . '/packapp/im/index.html#/chatInterface?from_user=' . $fromUser . '&to_user=' . $toUser . '&relation=' . $relation;
    
    $params['t'] = time();
    $params['s'] = md5($params['t'] . $fromUser . C('config.site_url'));
    
    foreach ($params as $k => $v) {
        $url .= '&' . $k . '=' . $v;
    }
    if (C('config.open_multilingual')) {
        $url .= '&now_lang=' . C('config.system_lang');
    }
    return $url;
}

/**
 * 生成订单号
 * @param $uid
 * @return string
 */
function build_real_orderid($uid)
{
    return date('ymdhis') . substr(microtime(), 2, 9 - strlen($uid)) . $uid;
}

/**
 * 获取字符串首字母拼音
 * @return string
 */
function get_first_charter($str)
{

    if (empty($str)) {
        return '';
    }

    $fchar = ord($str{0});


    if ($fchar >= ord('A') && $fchar <= ord('z')) return strtoupper($str{0});
    if ($fchar >= ord('0') && $fchar <= ord('9')) return strtoupper($str{0});

    $s1 = mb_convert_encoding($str, 'gb2312', 'UTF-8');

    $s2 = mb_convert_encoding($s1, 'UTF-8', 'gb2312');

    $s = $s2 == $str ? $s1 : $str;

    $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;

    if ($asc >= -20319 && $asc <= -20284) return 'A';

    if ($asc >= -20283 && $asc <= -19776) return 'B';

    if ($asc >= -19775 && $asc <= -19219) return 'C';

    if ($asc >= -19218 && $asc <= -18711) return 'D';

    if ($asc >= -18710 && $asc <= -18527) return 'E';

    if ($asc >= -18526 && $asc <= -18240) return 'F';

    if ($asc >= -18239 && $asc <= -17923) return 'G';

    if ($asc >= -17922 && $asc <= -17418) return 'H';

    if ($asc >= -17417 && $asc <= -16475) return 'J';

    if ($asc >= -16474 && $asc <= -16213) return 'K';

    if ($asc >= -16212 && $asc <= -15641) return 'L';

    if ($asc >= -15640 && $asc <= -15166) return 'M';

    if ($asc >= -15165 && $asc <= -14923) return 'N';

    if ($asc >= -14922 && $asc <= -14915) return 'O';

    if ($asc >= -14914 && $asc <= -14631) return 'P';

    if ($asc >= -14630 && $asc <= -14150) return 'Q';

    if ($asc >= -14149 && $asc <= -14091) return 'R';

    if ($asc >= -14090 && $asc <= -13319) return 'S';

    if ($asc >= -13318 && $asc <= -12839) return 'T';

    if ($asc >= -12838 && $asc <= -12557) return 'W';

    if ($asc >= -12556 && $asc <= -11848) return 'X';

    if ($asc >= -11847 && $asc <= -11056) return 'Y';

    if ($asc >= -11055 && $asc <= -10247) return 'Z';

    return '';

}

/**
 * 多边形坐标转换格式
 */
function polygon_2_type($pos, $type = 'json', $coordinateTrans = "")
{
    $pos        = substr($pos, 9, strlen($pos) - 11);
    $lngLatData = explode(',', $pos);
    array_pop($lngLatData);
    $lngLats = [];
    foreach ($lngLatData as $v) {
        $lngLat    = explode(' ', $v);
        //坐标转换，暂时只适配百度转天地图、天地图转百度。后面按需拓展
        switch ($coordinateTrans) {
            case 'wgs84ToBd09':
                list($lngLat[0], $lngLat[1]) = Coordinate::wgs84ToBd09($lngLat[0], $lngLat[1]);
                break;
            case 'bd09ToWgs84':
                list($lngLat[0], $lngLat[1]) = Coordinate::bd09ToWgs84($lngLat[0], $lngLat[1]);
                break;
            default:
                break;
        }
        $lngLats[] = array('lng' => $lngLat[0], 'lat' => $lngLat[1]);
    }
    if ($type == 'json') {
        return json_encode([$lngLats]);
    } else if ($type == 'array') {
        return $lngLats;
    }
}

/**
 * @desc 根据后台配置(配送费计算方式)取舍数字
 * @param $number
 * @return return
 */
function format_number_by_config($number)
{
    if (empty($number)) return 0;
    switch (C('config.count_freight_charge_method')) {
        case '0':
            $return = round($number, 2);
            break;//精确到分（四舍五入到分）
        case '1':
            $return = ceil($number * 10) / 10;
            break;//进一到角收取
        case '2':
            $return = floor($number * 10) / 10;
            break;//减一到角收取
        case '3':
            $return = round($number, 1);
            break;//四舍五入到角收取
        case '4':
            $return = ceil($number);
            break;//进一到元收取
        case '5':
            $return = floor($number);
            break;//减一到元收取
        case '6':
            $return = round($number);
            break;//四舍五入到元收取
        default:
            $return = round($number, 2);//四舍五入到分
    }
    return $return;
}

/**
 * @desc 数字每隔3位加个逗号
 * @param $num
 * @return rvalue
 */
function format_number_by_comma($num)
{
    if (!is_numeric($num)) {
        return false;
    }

    $fuhao = 1;
    if ($num >= 0) {
        $fuhao = 1;
    } elseif ($num < 0) {
        $fuhao = -1;
    }

    $fuAry = explode('-', $num);//把负号和数据分开
    $num = count($fuAry) > 1 ? $fuAry[1] : $fuAry[0];
    $num = explode('.', $num);//把整数和小数分开
    $rl  = $num[1];//小数部分的值
    $j   = strlen($num[0]) % 3;//整数有多少位
    $sl  = substr($num[0], 0, $j);//前面不满三位的数取出来
    $sr  = substr($num[0], $j);//后面的满三位的数取出来
    $i   = 0;
    while ($i <= strlen($sr)) {
        $rvalue = $rvalue . ',' . substr($sr, $i, 3);//三位三位取出再合并，按逗号隔开
        $i      = $i + 3;
    }
    $rvalue = $sl . $rvalue;
    $rvalue = substr($rvalue, 0, strlen($rvalue) - 1);//去掉最后一个逗号
    $rvalue = explode(',', $rvalue);//分解成数组
    if ($rvalue[0] == 0) {
        array_shift($rvalue);//如果第一个元素为0，删除第一个元素
    }

    $rvalue[0] = $rvalue[0] * $fuhao;

    $rv = $rvalue[0];//前面不满三位的数
    for ($i = 1; $i < count($rvalue); $i++) {
        $rv = $rv . ',' . $rvalue[$i];
    }
    if (!empty($rl)) {
        $rvalue = $rv . '.' . $rl;//小数不为空，整数和小数合并
    } else {
        $rvalue = $rv;//小数为空，只有整数
    }
    return $rvalue;
}

/**
 * 读取excel表格内容到数据
 * @param string $filePath
 * @param int $sheet
 * @return array
 */
function format_excel2array($filePath = '', $sheet = 0, $searchChars = [], $replace = '')
{
    if (empty($filePath) || !file_exists($filePath)) {
        throw new \Exception('File not exists');
    }
    require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
    $PHPReader = new PHPExcel_Reader_Excel2007();
    if (!$PHPReader->canRead($filePath)) {
        $PHPReader = new PHPExcel_Reader_Excel5();
        if (!$PHPReader->canRead($filePath)) {
            throw new \Exception('Could not open this file for reading');
        }
    }
    $PHPExcel     = $PHPReader->load($filePath);
    $currentSheet = $PHPExcel->getSheet($sheet);
    $allColumn    = $currentSheet->getHighestColumn();
    $allRow       = $currentSheet->getHighestRow();
    $data         = array();
    for ($rowIndex = 1; $rowIndex <= $allRow; $rowIndex++) {
        for ($colIndex = 'A'; $colIndex <= 'Z'; $colIndex++) {
            $addr = $colIndex . $rowIndex;
            $cell = $currentSheet->getCell($addr)->getValue();
            if ($cell instanceof PHPExcel_RichText) {
                $cell = $cell->__toString();
            }
            //特殊字符替换
            if ($searchChars) {
                $cell = str_replace($searchChars, $replace, $cell);
            }
            $data[$rowIndex][$colIndex] = trim($cell);
            if ($colIndex === $allColumn) {
                break;
            }
        }
        //删除空行,遇到空行直接终止
        $uniq = array_unique($data[$rowIndex]);
        if (count($uniq) == 1 && $uniq[0] == '') {
            unset($data[$rowIndex]);
            break;
        }
    }
    return $data ? array_values($data) : [];
}


/**
 * @desc 返回文本中去除所有换行的文本，特别用于微信H5分享
 * @param $text
 * @return return
 */
function remove_nl($text, $replace = '')
{
    $text = str_replace(array("\r\n", "\r", "\t", "\n", PHP_EOL), $replace, $text);
    return $text;
}

// 生成带小数的随机数
function randomFloat($min = 0, $max = 1)
{
    $num = $min + mt_rand() / mt_getrandmax() * ($max - $min);
    return sprintf("%.2f", $num);  //控制小数后几位
}


// S方法的SQL版，为了避免 runtime 目录被清空导致缓存数据不准
// expire_time等于0时，代表永久缓存
function cache_sql($name, $value = '', $expire_time = 0)
{
    // null时代表声明删除
    if ($value === null) {
        M('Cache_sql')->where(['name' => $name])->delete();
    } else if ($value === '') {    //读取值
        $cache_sql = M('Cache_sql')->where(['name' => $name])->find();
        if (!$cache_sql) {
            return false;
        }
        if ($cache_sql['expire'] == 0 || $cache_sql['expire'] > time()) {
            return json_decode($cache_sql['value'], true);
        }
        return false;
    } else {    //设置值
        M('Cache_sql')->where(['name' => $name])->delete();
        $data_sql = [
            'name'   => $name,
            'value'  => json_encode($value),
            'expire' => $expire_time == 0 ? 0 : (time() + $expire_time),
        ];
        M('Cache_sql')->data($data_sql)->add();
    }
}

// 获取数字的时区
function get_timeout_num_by_name($name = '')
{
    if ($name == '') {
        $name = date_default_timezone_get();
    }
    $tz           = timezone_open($name);
    $dateTimeOslo = date_create("now", timezone_open("Europe/Oslo"));
    $v            = timezone_offset_get($tz, $dateTimeOslo);

    $h = floor($v / 3600);
    $t = $v % 3600;
    $m = ceil($t / 60);
    if ($m < 10) {
        $m = '0' . $m;
    }
    return $h . ':' . $m;
}

//身份证号码校验
function is_idcard($id_card)
{
    $id_card = strtoupper($id_card);
    // 校验白名单
    $white_list = [
        '410881198401197124', '320304196206140411', '110109196507244011', '520202197106113035'
    ];
    if (in_array($id_card, $white_list)) {
        return TRUE;
    }
    $patternDalu     =  "#(^\d{15}$)|(^\d{17}(\d|X)$)#"; //大陆
    $patternHongkong = "/^((\s?[A-Za-z])|([A-Za-z]{2}))\d{6}(\([0−9aA]\)|[0-9aA])$/"; //香港
    $patternTaiwan   = "/^[a-zA-Z][0-9]{9}$/";  //台湾
    $patternMacao    = "/^[1|5|7][0-9]{6}\([0-9Aa]\)/";  //澳门

    if (  preg_match($patternDalu, $id_card) 
        || preg_match($patternHongkong, $id_card) 
        || preg_match($patternTaiwan, $id_card) 
        || preg_match($patternMacao, $id_card)
    ) {
        return  true;
    }else{
        return false;
    }
     
}

/**
 * Notes:通过身份证获取年龄
 * @param $idcard
 * @return bool|false|string
 * @author: wanzy
 * @date_time: 2021/1/16 14:52
 */
function getAgeByIdcard($idcard)
{
    $year     = substr($idcard, 6, 4);
    $monthDay = substr($idcard, 10, 4);

    $age = date('Y') - $year;
    if ($monthDay > date('md')) {
        $age--;
    }
    return $age;
}

/**
 * Notes: 对应获取链接
 * @return string
 * @author: wanzy
 * @date_time: 2021/4/6 16:56
 */
function get_base_url($url = '', $isNew = 0)
{
    if (C('config.system_type') == 'village') {
        if ($isNew) {
            $base_url = '/packapp/platn/';
        } else {
            $base_url = '/packapp/village/';
        }
    } else {
        if ($isNew) {
            $base_url = '/packapp/platn/';
        } else {
            $base_url = '/packapp/plat/';
        }
    }
    $site_url=C('config.site_url');
    if(C('config.config_site_url')){
        $site_url=C('config.config_site_url');
    }
    $_url=$site_url. $base_url;
    if ($url) {
        $_url .= $url;
    }
    return $_url;
}

/**
 * 判断两个时间段是否存在交集
 * @param int $beginTime1
 * @param int $endTime1
 * @param int $beginTime2
 * @param int $endTime2
 * @return bool
 * @author 张涛
 * @date 2020/08/04
 */
function isTimeCross($beginTime1 = 0, $endTime1 = 0, $beginTime2 = 0, $endTime2 = 0)
{
    $status = $beginTime2 - $beginTime1;
    if ($status > 0) {
        $status2 = $beginTime2 - $endTime1;
        if ($status2 > 0) {
            return false; // 无交集
        } else {
            return true; // 有交集
        }
    } else {
        $status2 = $endTime2 - $beginTime1;
        if ($status2 > 0) {
            return true;
        } else {
            return false;
        }
    }
}

/**
 * 数据库版本是否8.0+
 * @return bool
 * @author 刘若飞
 * @date 2021/08/05/
 */
function isMysqlVer8()
{
    $ver = S('mysql_version');
    if (!$ver) {
        $ver = M()->query("select VERSION() as version");
        $ver = $ver[0]['version'];
        $ver = $ver ? intval(explode('.', $ver)[0]) : 0;
        S('mysql_version', $ver, 86400 * 2);//两天缓存
    }
    if ($ver >= 8) {
        return true;
    } else {
        return false;
    }
}

/**
 * 获取13位的unix时间戳
 * @return bool
 * @author 刘若飞
 * @date 2021/08/05/
 */
function getUnixTimestamp()
{
    list($s1, $s2) = explode(' ', microtime());
    $msectime = (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    return substr($msectime, 0, 13);
}


function remove_xss($val) 
{
    $val    = preg_replace('/([\x00-\x08,\x0b-\x0c,\x0e-\x19])/', '', $val);
    $search = 'abcdefghijklmnopqrstuvwxyz';
    $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $search .= '1234567890!@#$%^&*()';
    $search .= '~`";:?+/={}[]-_|\'\\';
    for ($i = 0; $i < strlen($search); $i++) {
        $val = preg_replace('/(&#[xX]0{0,8}' . dechex(ord($search[$i])) . ';?)/i', $search[$i], $val); // with a ;
        $val = preg_replace('/(&#0{0,8}' . ord($search[$i]) . ';?)/', $search[$i], $val); // with a ;
    }

    $ra1 = array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'style', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base');
    $ra2 = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');
    $ra  = array_merge($ra1, $ra2);

    $found = true;
    while ($found == true) {
        $val_before = $val;
        for ($i = 0; $i < sizeof($ra); $i++) {
            $pattern = '/';
            for ($j = 0; $j < strlen($ra[$i]); $j++) {
                if ($j > 0) {
                    $pattern .= '(';
                    $pattern .= '(&#[xX]0{0,8}([9ab]);)';
                    $pattern .= '|';
                    $pattern .= '|(&#0{0,8}([9|10|13]);)';
                    $pattern .= ')*';
                }
                $pattern .= $ra[$i][$j];
            }
            $pattern     .= '/i';
            $replacement = substr($ra[$i], 0, 2) . '<x>' . substr($ra[$i], 2); // add in <> to nerf the tag
            $val         = preg_replace($pattern, $replacement, $val); // filter out the hex tags
            if ($val_before == $val) {
                $found = false;
            }
        }
    }
    return $val;
}

/**
 * 获取当前运行域名的顶级域名
 */
function getRunTopDomain($getHost = false)
{
    $host = isset($_SERVER['HTTP_X_FORWARDED_HOST']) ? $_SERVER['HTTP_X_FORWARDED_HOST'] : (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME'] . ($_SERVER['SERVER_PORT'] == '80' ? '' : ':' . $_SERVER['SERVER_PORT']));
    $host = strtolower($host);
    if (strpos($host, '/') !== false) {
        $parse = @parse_url($host);
        $host  = $parse['host'];
    }
    if ($getHost) {
        return $host;
    }
    $topleveldomaindb = array('com', 'cn', 'cc', 'cd', 'beer', 'center', 'vip', 'ceo', 'cern', 'cf', 'ch', 'channel', 'chat', 'cheap', 'christmas', 'chrome', 'church', 'ci', 'city', 'com.au', 'com.br', 'com.cn', 'com.co', 'com.de', 'com.ec', 'com.fr', 'com.gr', 'com.gt', 'com.hk', 'com.mm', 'com.mx', 'com.my', 'com.ph', 'com.pl', 'com.pt', 'com.ro', 'com.ru', 'com.sg', 'com.tr', 'com.tw', 'com.ua', 'com.ve', 'community', 'company', 'computer', 'condos', 'construction', 'consulting', 'contractors', 'cooking', 'cool', 'coop', 'country', 'courses', 'cq.cn', 'credit', 'creditcard', 'cricket', 'cruises', 'cuisinella', 'cx', 'cymru', 'cz', 'dad', 'dance', 'dating', 'datsun', 'day', 'dclk', 'de', 'deals', 'degree', 'delivery', 'democrat', 'dental', 'dentist', 'desi', 'design', 'dev', 'diamonds', 'diet', 'digital', 'direct', 'directory', 'discount', 'dk', 'dm', 'dn.ua', 'docs', 'domains', 'doosan', 'durban', 'dvag', 'dz', 'eat', 'ec', 'ecn.br', 'edu', 'edu.au', 'edu.cn', 'edu.gr', 'edu.gt', 'edu.hk', 'edu.mm', 'edu.mx', 'edu.my', 'edu.pl', 'edu.pt', 'edu.rs', 'edu.sg', 'edu.tr', 'edu.za', 'education', 'ee', 'email', 'emerck', 'energy', 'eng.br', 'engineer', 'engineering', 'enterprises', 'epson', 'equipment', 'ernet.in', 'es', 'esp.br', 'esq', 'estate', 'etc.br', 'eti.br', 'eu', 'eu.com', 'eu.lv', 'eurovision', 'eus', 'events', 'exchange', 'expert', 'exposed', 'fail', 'fans', 'farm', 'fashion', 'feedback', 'fi', 'fin.ec', 'finance', 'financial', 'firm.ro', 'firmdale', 'fish', 'fishing', 'fit', 'fitness', 'flights', 'florist', 'flowers', 'flsmidth', 'fly', 'fm', 'fm.br', 'fo', 'foo', 'football', 'forsale', 'fot.br', 'foundation', 'fr', 'frl', 'frogans', 'fst.br', 'fund', 'furniture', 'futbol', 'g12.br', 'gal', 'gallery', 'garden', 'gb.com', 'gb.net', 'gbiz', 'gd', 'gd.cn', 'gdn', 'geek.nz', 'gen.nz', 'gent', 'gf', 'gg', 'ggee', 'gi', 'gift', 'gifts', 'gives', 'gl', 'glass', 'gle', 'global', 'globo', 'gmail', 'gmina.pl', 'gmx', 'go.id', 'go.jp', 'go.kr', 'go.th', 'gob.gt', 'gob.mx', 'goldpoint', 'goo', 'goog', 'google', 'gop', 'gov', 'gov.br', 'gov.cn', 'gov.ec', 'gov.gr', 'gov.il', 'gov.in', 'gov.mm', 'gov.mx', 'gov.my', 'gov.sg', 'gov.tr', 'gov.za', 'gq', 'gr', 'graphics', 'gratis', 'green', 'gripe', 'gs', 'gs.cn', 'gsm.pl', 'guide', 'guitars', 'guru', 'gv.ac', 'gv.at', 'gx.cn', 'gy', 'gz.cn', 'hamburg', 'hangout', 'haus', 'hb.cn', 'he.cn', 'healthcare', 'help', 'here', 'hi.cn', 'hiphop', 'hiv', 'hk', 'hk.cn', 'hl.cn', 'hn', 'hn.cn', 'holdings', 'holiday', 'horse', 'host', 'hosting', 'house', 'how', 'hr', 'ht', 'hu', 'hu.com', 'ibm', 'id', 'id.au', 'ie', 'ifm', 'il', 'im', 'immo', 'immobilien', 'in', 'in.rs', 'in.th', 'ind.br', 'ind.gt', 'industries', 'inf.br', 'infiniti', 'info', 'info.pl', 'info.ro', 'info.ve', 'ing', 'ink', 'institute', 'insure', 'int', 'international', 'investments', 'io', 'iq', 'ir', 'is', 'it', 'iwi.nz', 'java', 'jcb', 'je', 'jl.cn', 'jobs', 'joburg', 'jor.br', 'jp', 'js.cn', 'juegos', 'k12.il', 'k12.tr', 'kaufen', 'kddi', 'ke', 'kg', 'kh.ua', 'ki', 'kiev.ua', 'kim', 'kitchen', 'kiwi', 'kiwi.nz', 'koeln', 'kr', 'krd', 'ky', 'kyoto', 'kz', 'la', 'lacaixa', 'land', 'lat', 'latrobe', 'lawyer', 'lc', 'lease', 'leclerc', 'legal', 'lel.br', 'lg.ua', 'lgbt', 'li', 'life', 'lighting', 'limited', 'limo', 'link', 'ln.cn', 'loans', 'london', 'lotte', 'lt', 'ltd.uk', 'ltda', 'lu', 'luxe', 'luxury', 'lv', 'lviv.ua', 'ly', 'ma', 'madrid', 'mail.pl', 'maison', 'management', 'mango', 'maori.nz', 'market', 'marketing', 'md', 'me', 'me.uk', 'med.br', 'med.ec', 'media', 'media.pl', 'melbourne', 'meme', 'memorial', 'menu', 'mg', 'mi.th', 'miami', 'miasta.pl', 'mil', 'mil.br', 'mil.ec', 'mil.gt', 'mil.id', 'mil.pl', 'mil.tr', 'mil.za', 'mini', 'mk', 'ml', 'mn', 'mo', 'mo.cn', 'mobi', 'moda', 'monash', 'money', 'mortgage', 'moscow', 'mov', 'ms', 'msk.ru', 'mtpc', 'mu', 'muni.il', 'museum', 'mx', 'my', 'mz', 'na', 'name', 'navy', 'nc', 'ne.jp', 'ne.kr', 'net', 'net.au', 'net.br', 'net.cn', 'net.co', 'net.ec', 'net.gr', 'net.gt', 'net.hk', 'net.il', 'net.in', 'net.mm', 'net.mx', 'net.my', 'net.nz', 'net.ph', 'net.pl', 'net.ru', 'net.sg', 'net.th', 'net.tr', 'net.tw', 'net.ua', 'net.uk', 'net.ve', 'net.za', 'network', 'new', 'nexus', 'nf', 'ng', 'ngo', 'ngo.ph', 'ngo.za', 'nico', 'ninja', 'nissan', 'nl', 'nm.cn', 'nm.kr', 'no', 'no.com', 'nom.br', 'nom.co', 'nom.pl', 'nom.ro', 'nom.za', 'nra', 'nrw', 'nt.ro', 'ntr.br', 'nu', 'nx.cn', 'nz', 'odo.br', 'om', 'one', 'ong', 'onl', 'ooo', 'or.ac', 'or.at', 'or.jp', 'or.kr', 'or.th', 'org', 'org.au', 'org.br', 'org.cn', 'org.ec', 'org.gr', 'org.gt', 'org.hk', 'org.il', 'org.in', 'org.mm', 'org.mx', 'org.my', 'org.nz', 'org.ph', 'org.pl', 'org.ro', 'org.rs', 'org.ru', 'org.sg', 'org.tr', 'org.tw', 'org.ua', 'org.uk', 'org.ve', 'org.za', 'organic', 'ovh', 'paris', 'partners', 'parts', 'pc.pl', 'pe', 'pf', 'ph', 'photo', 'photography', 'photos', 'physio', 'pics', 'pictures', 'pink', 'pizza', 'pl', 'place', 'plc.uk', 'plumbing', 'pm', 'pohl', 'poker', 'porn', 'post', 'pp.ru', 'ppg.br', 'pr', 'press', 'presse.fr', 'priv.pl', 'pro', 'pro.br', 'prod', 'productions', 'prof', 'properties', 'property', 'psc.br', 'psi.br', 'pt', 'pub', 'pw', 'qa', 'qc.com', 'qh.cn', 'quebec', 're', 're.kr', 'realestate.pl', 'rec.br', 'rec.ro', 'recipes', 'red', 'rehab', 'reise', 'reisen', 'reit', 'rel.pl', 'rentals', 'repair', 'report', 'republican', 'res.in', 'rest', 'restaurant', 'reviews', 'rich', 'rio', 'rip', 'ro', 'rocks', 'rodeo', 'rs', 'rsvp', 'ru', 'ruhr', 'sa', 'sa.com', 'saarland', 'sale', 'samsung', 'sarl', 'saxo', 'sb', 'sc', 'sc.cn', 'sca', 'scb', 'schmidt', 'school', 'school.nz', 'school.za', 'schule', 'scot', 'se', 'se.com', 'se.net', 'services', 'sexy', 'sg', 'sh', 'sh.cn', 'shiksha', 'shoes', 'shop.pl', 'si', 'singles', 'sk', 'sklep.pl', 'sky', 'slg.br', 'sm', 'sn', 'sn.cn', 'so', 'sochi.su', 'social', 'software', 'solar', 'solutions', 'sos.pl', 'soy', 'space', 'spb.ru', 'spiegel', 'st', 'store.ro', 'study', 'style', 'su', 'sucks', 'supplies', 'supply', 'support', 'surf', 'surgery', 'sx', 'sy', 'sydney', 'systems', 'taipei', 'targi.pl', 'tatar', 'tattoo', 'tax', 'tc', 'technology', 'tel', 'tennis', 'tf', 'th', 'tienda', 'tips', 'tires', 'tirol', 'tj', 'tj.cn', 'tk', 'tl', 'tm', 'tm.fr', 'tm.mc', 'tm.pl', 'tm.ro', 'tm.za', 'tmp.br', 'tn', 'to', 'today', 'tools', 'top', 'toshiba', 'tourism.pl', 'town', 'toys', 'training', 'travel', 'travel.pl', 'trust', 'tt', 'tui', 'tur.br', 'turystyka.pl', 'tv', 'tv.br', 'tw', 'tw.cn', 'tz', 'ua', 'ug', 'uk', 'uk.co', 'uk.com', 'uk.net', 'university', 'uno', 'uol', 'us', 'us.com', 'uy', 'uy.com', 'uz', 'vacations', 'vc', 've', 'vegas', 'ventures', 'versicherung', 'vet', 'vet.br', 'vg', 'viajes', 'video', 'villas', 'vision', 'vlaanderen', 'vodka', 'vote', 'voting', 'voto', 'voyage', 'vu', 'wales', 'wang', 'watch', 'web.ve', 'web.za', 'website', 'wed', 'wedding', 'wf', 'whoswho', 'wien', 'wiki', 'wme', 'work', 'works', 'world', 'ws', 'wtc', 'wtf', 'www.ro', 'xj.cn', 'xxx', 'xyz', 'xz.cn', 'yn.cn', 'yodobashi', 'yoga', 'youtube', 'yt', 'za.com', 'za.net', 'za.org', 'zip', 'zj.cn', 'zlg.br', 'zm', 'zone', 'zuerich', 'abogado', 'ac', 'ac.ac', 'ac.at', 'ac.be', 'ac.cn', 'ac.il', 'ac.in', 'ac.jp', 'ac.ke', 'ac.kr', 'ac.nz', 'ac.th', 'ac.uk', 'ac.za', 'academy', 'accountants', 'actor', 'ad', 'adm.br', 'adult', 'adv.br', 'ae', 'aero', 'af', 'ag', 'agency', 'agro.pl', 'ah.cn', 'aid.pl', 'airforce', 'allfinanz', 'alsace', 'alt.za', 'am', 'am.br', 'android', 'apartments', 'aquarelle', 'archi', 'army', 'arq.br', 'art.br', 'arts.ro', 'as', 'asia', 'asn.au', 'asso.fr', 'asso.mc', 'associates', 'at', 'atm.pl', 'attorney', 'au', 'auction', 'audio', 'auto.pl', 'aw', 'ax', 'band', 'bank', 'bar', 'barclaycard', 'barclays', 'bargains', 'bayern', 'bbs.tr', 'be', 'berlin', 'best', 'bg', 'bi', 'bike', 'bingo', 'bio', 'bio.br', 'biz', 'biz.pl', 'bj', 'bj.cn', 'black', 'blackfriday', 'blue', 'bmw', 'bnpparibas', 'boo', 'boutique', 'br', 'br.com', 'brussels', 'budapest', 'build', 'builders', 'business', 'bw', 'by', 'bz', 'bzh', 'ca', 'cab', 'cal', 'camera', 'camp', 'cancerresearch', 'canon', 'capetown', 'capital', 'cards', 'care', 'career', 'careers', 'casa', 'cash', 'casino', 'cat', 'catering', 'cl', 'claims', 'cleaning', 'click', 'clinic', 'clothing', 'club', 'cn.com', 'cng.br', 'cnt.br', 'co', 'co.ac', 'co.at', 'co.il', 'co.in', 'co.jp', 'co.ke', 'co.kr', 'co.nz', 'co.rs', 'co.th', 'co.uk', 'co.ve', 'co.za', 'coach', 'codes', 'coffee', 'college', 'cologne', 'test', 'shop', 'store');
    $str              = '';

    foreach ($topleveldomaindb as $v) {
        $str .= ($str ? '|' : '') . $v;
    }

    $matchstr = "[^\.]+\.(?:(" . $str . ")|\w{2}|((" . $str . ")\.\w{2}))$";
    if (preg_match("/" . $matchstr . "/", $host, $matchs)) {
        $domain = $matchs['0'];
    } else {
        $domain = $host;
    }

    return $domain;
}

/**
 * 运行通过的白名单
 * @param $domin 可以是字符串也可以是域名数组
 */
function MergeWhitDomain($domin = '')
{
    $whitDomain = [
        "group.com",
        "pigcms.com"
    ];
    if ($domin && is_string($domin)) {
        $whitDomain[] = trim($domin);
    } else if (is_array($domin)) {
        $whitDomain = array_merge($whitDomain, $domin);
    }
    return $whitDomain;
}

/**
 * 判断是否是新安装客户
 * @return bool
 */
function isSoftwareMew()
{
    $software_new_version = C('config.software_new_version');
    if ($software_new_version) {
        return true;
    } else {
        return false;
    }
}

// 解析身份证
function idCardAnalysis($id_card)
{
    if (!preg_match('#(^\d{15}$)|(^\d{17}(\d|X)$)#', $id_card)) {
        return false;
    }
    $date = substr($id_card, 6, 8);
    if (strlen($date) != 8) {
        return false;
    }
    $year  = intval(substr($date, 0, 4));
    $month = intval(substr($date, 4, 2));
    $day   = intval(substr($date, 6, 2));
    // 日期基本格式校验
    if (!checkdate($month, $day, $year)) {
        return false;
    }
    // 日期大于今天
    if ($date > date('Ymd')) {
        return false;
    }
    $res = [
        'birthday' => substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2),
        'age'      => date('Y') - $year,
        'sex'      => substr($id_card, (strlen($id_card) == 18 ? -2 : -1), 1) % 2 ? '男' : '女',
    ];
    return $res;
}

/**
 * 去除字符串中的emoji
 * @author Nd
 * @date 2022/8/3
 * @param $str
 * @return string
 */
function removeEmojiChar($str){
        $mbLen = mb_strlen($str);

        $strArr = [];
        for ($i = 0; $i < $mbLen; $i++) {
            $mbSubstr = mb_substr($str, $i, 1, 'utf-8');
            if (strlen($mbSubstr) >= 4) {
                continue;
            }
            $strArr[] = $mbSubstr;
        }

        return implode('', $strArr);
}
/**
 * 文本转语音
 * @param $message  文本内容  例如：您有新订单请及时抢单
 * @param $businessType 业务逻辑  例如：new_deliver_order
 * @return string
 * @date: 2022/06/30
 */
function text2audio($message, $businessType = '')
{
    $apiKey = C('config.text2audio_baidu_api_key');
    $secretKey = C('config.text2audio_baidu_secret_key');
    if (!$apiKey || !$secretKey) {
        return $message;
    }

    $type = C('config.text2audio_baidu_type');
    static $return;
    import('ORG.Net.Http');
    if (empty($return)) {
        $tokenUrl = "https://openapi.baidu.com/oauth/2.0/token?grant_type=client_credentials&client_id=" . $apiKey . "&client_secret=" . $secretKey;
        $tokenResult = Http::curlGet($tokenUrl);
        fdump_api([$message, $tokenUrl, $tokenResult], 'text2audio', 1);
        $return = json_decode($tokenResult, true);
    }

    if ($return['access_token']) {
        $voiceMp3 = 'http://tsn.baidu.com/text2audio?tex=' . urlencode($message) . '&lan=zh&tok=' . $return['access_token'] . '&ctp=1&cuid=9B9A62EE-3EE8-45E0-9C06-2E3245AE3FF5';
        $type == 1 && $voiceMp3 .= '&per=5118';
    }

    return $voiceMp3 ?? '';
}
//手机号脱敏隐藏展示
function phone_desensitization($phone=''){
    $house_desensitization_tel = C('config.house_desensitization_tel');
    $house_desensitization_tel=!empty($house_desensitization_tel) ? intval($house_desensitization_tel):0;
    if(!empty($phone) && (strpos($phone,'无')!==false || strpos($phone,'--')!==false)){
        return $phone;
    }
    if($house_desensitization_tel==1 && !empty($phone)){
        $len=strlen($phone);
        if($len == 11){
            $phone =substr($phone,0,3).'****'.substr($phone,7,4);
        }else if($len>=6){
            if($len<11){
                $start=($len-3)/2;
                $start=ceil($start);
                $phone = substr_replace($phone,'***',$start,3);
            }else{
                $start=3;
                $length=4;
                $phone = substr_replace($phone,'****',$start,$length);
            }

        }
    }
    return $phone;
}

//身份证号脱敏隐藏展示
function idnum_desensitization($idnum=''){
    $house_desensitization_idnum = C('config.house_desensitization_idnum');
    $house_desensitization_idnum=!empty($house_desensitization_idnum) ? intval($house_desensitization_idnum):0;
    if(!empty($idnum) && (strpos($idnum,'无')!==false || strpos($idnum,'--')!==false)){
        return $idnum;
    }
    if($house_desensitization_idnum==1 && !empty($idnum)){
        $len=strlen($idnum);
        if($len == 18){
            $idnum =substr($idnum,0,8).'********'.substr($idnum,16,2);
        }elseif($len == 15){
            //130503 670401 001
            $idnum =substr($idnum,0,6).'*******'.substr($idnum,13,2);
        }else if($len>15 && $len<=25){
            $idnum = substr_replace($idnum,'********',7,7);
        }
    }
    return $idnum;
}


if (!function_exists('dd')) {
    
    function dd()
    {
        $data = func_get_args();
        echo "<pre>";
        foreach ($data as $v) {
           var_dump($v);
        }
        echo "</pre>";
        
        exit(1);
    }
}

/**
 * 加锁
 * @param $key
 * @param int $expire
 * @return array
 * @date: 2023/02/09
 */
function setLock($key, $expire = 30)
{
    $redisHost = C('REDIS_HOST');
    $redisPort = C('REDIS_PORT');
    $redisAuth = C('REDIS_AUTH');
    $rs = ['error_no' => 0, 'error_msg' => ''];
    try {
        if ($redisHost && $redisPort) {
            $redis = new Redis($redisHost, $redisPort);
            $connected = $redis->connect($redisHost, $redisPort);
            if (!$connected) {
                throw new Exception('REDIS 连接失败！请检查主机和端口。');
            }
            if ($redisAuth) {
                $authed = $redis->auth($redisAuth);
                if (!$authed) {
                    throw new Exception('REDIS 验证密码时发生错误！请检查验证密码。');
                }
            }
            if (!$redis->setnx($key, 1)) {
                throw new Exception('系统繁忙，请稍后再试');
            };
            $redis->expire($key, $expire);
        } else {
            if (S($key)) {
                throw new Exception('系统繁忙，请稍后再试');
            }
            S($key, 1, $expire);
        }
    } catch (Exception $e) {
        $rs['error_no'] = 1;
        $rs['error_msg'] = $e->getMessage();
    }
    return $rs;
}

/**
 * 解锁
 * @param $key
 * @param int $expire
 * @return array
 * @date: 2023/02/09
 */
function unLock($key)
{
    $redisHost = C('REDIS_HOST');
    $redisPort = C('REDIS_PORT');
    $redisAuth = C('REDIS_AUTH');
    $rs = ['error_no' => 0, 'error_msg' => ''];
    try {
        if ($redisHost && $redisPort) {
            $redis = new Redis($redisHost, $redisPort);
            $connected = $redis->connect($redisHost, $redisPort);
            if (!$connected) {
                throw new Exception('REDIS 连接失败！请检查主机和端口。');
            }
            if ($redisAuth) {
                $authed = $redis->auth($redisAuth);
                if (!$authed) {
                    throw new Exception('REDIS 验证密码时发生错误！请检查验证密码。');
                }
            }

            $redis->del($key);
        } else {
            S($key, null);
        }
    } catch (Exception $e) {
        $rs['error_no'] = 1;
        $rs['error_msg'] = $e->getMessage();
    }
    return $rs;
}

/**
 * 验证密码是否为8~16位且包含字母、数字和特殊符号
 * @param $str
 */
function verifyPassword($str){
    $code = 0;
    $msg = '';
    $regex = '/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[!@#$%^&*?()_+])[A-Za-z\d@$!#%*?&]{8,16}$/';
    if (!preg_match($regex, $str)) {
        $msg = L_('密码必须是8~16位且包含字母、数字和特殊符号(@、$、!、#、%、*、?、&)');
        $code = 1;
    }
    return [
        'code' => $code,
        'msg' => $msg
    ];
}
?>
