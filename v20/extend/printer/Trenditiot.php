<?php

namespace printer;

/**
 * 大趋云打印机对接（https://doc.trenditiot.com/docs/%E5%BC%80%E5%8F%91%E6%8C%87%E5%BC%95）
 *
 * @author: zt
 * @date: 2025/03/18
 */
class Trenditiot
{
    //生成Http header
    private static function getHeader($appId, $appSecret, $data)
    {
        $uid = uniqid('Trenditiot');
        $time = time();
        $json = json_encode($data);
        $str = $uid . $appId . $time . $appSecret . $json;
        $md5 = md5($str);
        $header = array();
        $header[] = "Content-Type:application/json;charset=UTF-8";
        $header[] = "appid:" . $appId;
        $header[] = "uid:" . $uid;
        $header[] = "stime:" . $time;
        $header[] = "sign:" . $md5;

        return $header;
    }


    //post请求
    private static function httpPost($appId, $appSecret, $url, $formfields = null)
    {
        $header = self::getHeader($appId, $appSecret, $formfields);
        $body = json_encode($formfields);

        $ci = curl_init();
        curl_setopt($ci, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ci, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ci, CURLOPT_TIMEOUT, 30);
        curl_setopt($ci, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ci, CURLINFO_HEADER_OUT, true);
        curl_setopt($ci, CURLOPT_HTTPHEADER, $header); // 设置通用传参
        curl_setopt($ci, CURLOPT_POST, true);
        if (!empty($formfields)) {
            curl_setopt($ci, CURLOPT_POSTFIELDS, $body);
        }
        curl_setopt($ci, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        curl_setopt($ci, CURLOPT_SSL_VERIFYHOST, false); // 从证书中检查SSL加密算法是否存在
        curl_setopt($ci, CURLOPT_URL, $url);
        $response = curl_exec($ci);
        $http_code = curl_getinfo($ci, CURLINFO_HTTP_CODE);
        curl_close($ci);
        return json_decode($response, true);
    }

    //绑定打印机
    public static function addPrinter($appId, $appSecret, $data)
    {
        $url = "https://iot-device.trenditiot.com/openapi/addPrinter";
        $resp = self::httpPost($appId, $appSecret, $url, $data);
        return $resp;
    }

    public static function setImage($appId, $appSecret, $data)
    {
        $url = "https://iot-device.trenditiot.com/openapi/setImage";
        $resp = self::httpPost($appId, $appSecret, $url, $data);
        return $resp;
    }

    //打印小票
    public static function print($appId, $appSecret, $data)
    {
        $url = "https://iot-device.trenditiot.com/openapi/print";
        $resp = self::httpPost($appId, $appSecret, $url, $data);
        return $resp;
    }
}
