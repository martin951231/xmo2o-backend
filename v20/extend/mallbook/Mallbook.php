<?php

namespace mallbook;

use app\pay\model\db\MallbookRequestLog;
use think\facade\Db;

class Mallbook
{
    // 应用公共文件
    public static function curlPost($url = '', $postData = '', $options = array())
    {
        $serverCode = $postData['serverCode'];
        fdump_api($postData, "mallBook/{$serverCode}", true);
        $requestLog = Db::transaction(function ()use($serverCode, $postData){
            $requestLog = new MallbookRequestLog();
            $requestLog->save([
                'type' => $serverCode,
                'merOrderId' => $postData['merOrderId'],
                'request_params' => $postData,
            ]);
            return $requestLog;
        });
        if (is_array($postData)) {
            $postData = http_build_query($postData);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30); //设置cURL允许执行的最长秒数
        if (!empty($options)) {
            curl_setopt_array($ch, $options);
        }
        
        //https请求 不验证证书和host
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($ch);
        curl_close($ch);
        //记录请求日志
        fdump_api([
            'request_url' => $url,
            'post_data' => $postData,
            'result' => $data
        ], "mallBook/{$serverCode}", true);
        Db::transaction(function ()use($requestLog, $data){
            $requestLog->request_response = $data;
            $requestLog->save();
        });        
        return $data;
    }

    public static function getSign($data)
    {
        if (empty($data)) {
            return "";
        }

        if (empty($data['merOrderId']) 
            || empty($data['merchantNo']) 
            || empty($data['channelType']) 
            || empty($data['date']) 
            || empty($data['params'])
        ) {
            return "";
        }

        $signString = $data['merOrderId'] 
                        . $data['merchantNo']
                        . $data['channelType'] 
                        . $data['date'] 
                        . $data['params'];

        $content = cfg('mallbook_my_private_rsa_key');
        $sign     = static::sign($signString, $content);
        return $sign;
    }

    public static function verify(array $data, $sign, $rsaPem = '')
    {
        if (empty($data) || empty($sign)) {
            return "";
        }
        //code+msg+date+result,
        $code   = $data['code'] ?? '';
        $msg    = $data['msg'] ?? '';
        $date   = $data['date'] ?? '';
        $result = $data['result'] ?? '';

        $signString = $code . $msg . $date . $result;
//        $mallbook_key_file = Config::get('mallbook.mallbook_key_file');
        $pubKey = $rsaPem ?: cfg('mallbook_my_rsa_public');
        // mallbook平台公钥
        //$pubKey = Config::get('mallbook.mall_book_public_key');
        return static::rsaVerify($signString, $pubKey, $sign);
    }

    public static function rsaVerify($data, $pubKey, $sign)
    {
        $res = openssl_get_publickey($pubKey);
        $result = (bool) openssl_verify($data, base64_decode($sign), $res);
        openssl_free_key($res);
        return $result;
    }

    public static function sign($data, $priKey)
    {
        $sign = '';
        $res = openssl_get_privatekey($priKey);
        openssl_sign($data, $sign, $res);
        openssl_free_key($res);
        $sign = base64_encode($sign);
        return $sign;
    }

    public static function getMillisecond()
    {
        list($s1, $s2) = explode(' ', microtime());
        return (float) sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }
}
