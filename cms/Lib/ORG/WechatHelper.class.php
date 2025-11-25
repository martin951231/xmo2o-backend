<?php

/**
 * 工具类
 */
class Tools
{
    static private function _buildPost(&$data)
    {
        if (is_array($data)) {
            foreach ($data as &$value) {
                if (is_string($value) && $value[0] === '@' && class_exists('CURLFile', false)) {
                    $filename = realpath(trim($value, '@'));
                    file_exists($filename) && $value = new CURLFile($filename);
                }
            }
        }
        return $data;
    }

    static public function httpGet($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSLVERSION, 1);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        list($content, $status) = array(curl_exec($curl), curl_getinfo($curl), curl_close($curl));
        return (intval($status["http_code"]) === 200) ? $content : false;
    }

    static public function httpPost($url, $data)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, self::_buildPost($data));
        list($content, $status) = array(curl_exec($curl), curl_getinfo($curl), curl_close($curl));
        return (intval($status["http_code"]) === 200) ? $content : false;
    }

    static public function httpsPost($url, $data, $ssl_cer = null, $ssl_key = null, $second = 30)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, $second);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        if (!is_null($ssl_cer) && file_exists($ssl_cer) && is_file($ssl_cer)) {
            curl_setopt($curl, CURLOPT_SSLCERTTYPE, 'PEM');
            curl_setopt($curl, CURLOPT_SSLCERT, $ssl_cer);
        }
        if (!is_null($ssl_key) && file_exists($ssl_key) && is_file($ssl_key)) {
            curl_setopt($curl, CURLOPT_SSLKEYTYPE, 'PEM');
            curl_setopt($curl, CURLOPT_SSLKEY, $ssl_key);
        }
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, self::_buildPost($data));
        list($content, $status) = array(curl_exec($curl), curl_getinfo($curl), curl_close($curl));
        return (intval($status["http_code"]) === 200) ? $content : false;
    }
}

/**
 * 微信公众号基类
 */
class WechatHelper
{
    public $accesstoken = '';

    public $errCode = '';

    public $errMsg = '';

    public function __construct($config)
    {
        isset($config['access_token']) && $this->accesstoken = $config['access_token'];
    }
}

/**
 * 用户类
 */
class WechatUser extends WechatHelper
{
    public function getUserInfo($openid)
    {
        $result = Tools::httpGet('https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . $this->accesstoken . '&openid=' . $openid . '&lang=zh_CN');
        if ($result) {
            $json = json_decode($result, true);
            if (empty($json) || !empty($json['errcode'])) {
                $this->errCode = isset($json['errcode']) ? $json['errcode'] : '505';
                $this->errMsg = isset($json['errmsg']) ? $json['errmsg'] : '无法解析接口返回内容！';
                return false;
            }
            return $json;
        }
        return false;
    }
}

/**
 * 客服消息类
 */
class WechatCustom extends WechatHelper
{
    /**
     * 发送客服消息
     */
    public function send($openid, $msgType, $params)
    {
        $url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . $this->accesstoken;
        if ($msgType == 'text') {
            $data = [
                'touser' => $openid,
                'msgtype' => $msgType,
                'text' => [
                    'content' => $params['content']
                ]
            ];
        } else if ($msgType == 'image') {
            $data = [
                'touser' => $openid,
                'msgtype' => $msgType,
                'image' => [
                    'media_id' => $params['media_id']
                ]
            ];
        } else {
            return false;
        }
        $result = Tools::httpPost($url, json_encode($data, JSON_UNESCAPED_UNICODE));
        if ($result) {
            $json = json_decode($result, true);
            if (empty($json) || !empty($json['errcode'])) {
                $this->errCode = isset($json['errcode']) ? $json['errcode'] : '505';
                $this->errMsg = isset($json['errmsg']) ? $json['errmsg'] : '无法解析接口返回内容！';
                return false;
            }
            return $json;
        }
    }
}

/**
 * 媒体素材库
 */
class WechatMedia extends WechatHelper
{

    /**
     * 上传临时素材
     */
    public function uploadMedia($type, $data)
    {
        $result = Tools::httpPost('https://api.weixin.qq.com/cgi-bin/media/upload?access_token=' . $this->accesstoken . '&type=' . $type, $data);
        if ($result) {
            $json = json_decode($result, true);
            if (empty($json) || !empty($json['errcode'])) {
                $this->errCode = isset($json['errcode']) ? $json['errcode'] : '505';
                $this->errMsg = isset($json['errmsg']) ? $json['errmsg'] : '无法解析接口返回内容！';
            }
            return $json;
        }
        return false;
    }
}