<?php
date_default_timezone_set("Asia/Shanghai");

//--------------------------------------------1、基础参数配置------------------------------------------------

const API_HOST = 'https://cashier.sandpay.com.cn/cbs/api/pay/push';
const PUB_KEY_PATH = 'cert/sand.cer'; //杉德公钥文件
const PRI_KEY_PATH = 'cert/16938552.pfx'; //商户私钥文件
const CERT_PWD = 'password'; //私钥证书密码

//--------------------------------------------end基础参数配置------------------------------------------------
/**
 * 获取公钥
 * @param $path
 * @return mixed
 * @throws Exception
 */
function loadX509Cert($path)
{
    try {
        $file = file_get_contents($path);
        if (!$file) {
            throw new \Exception('loadx509Cert::file_get_contents ERROR');
        }

        $cert = chunk_split(base64_encode($file), 64, "\n");
        $cert = "-----BEGIN CERTIFICATE-----\n" . $cert . "-----END CERTIFICATE-----\n";

        $res = openssl_pkey_get_public($cert);
        $detail = openssl_pkey_get_details($res);
        openssl_free_key($res);
        if (!$detail) {
            throw new \Exception('loadX509Cert::openssl_pkey_get_details ERROR');
        }
        return $detail['key'];
    } catch (\Exception $e) {
        throw $e;
    }
}

/**
 * 获取私钥
 * @param $path
 * @param $pwd
 * @return mixed
 * @throws Exception
 */
function loadPk12Cert($path, $pwd)
{
    try {
        $file = file_get_contents($path);
        if (!$file) {
            throw new \Exception('loadPk12Cert::file
					_get_contents');
        }

        if (!openssl_pkcs12_read($file, $cert, $pwd)) {
            throw new \Exception('loadPk12Cert::openssl_pkcs12_read ERROR');
        }
        return $cert['pkey'];
    } catch (\Exception $e) {
        throw $e;
    }
}

/**
 * 私钥签名
 * @param $plainText
 * @param $path
 * @return string
 * @throws Exception
 */
function sign($plainText, $path)
{
    $plainText = json_encode($plainText);
    try {
        $resource = openssl_pkey_get_private($path);
        $result = openssl_sign($plainText, $sign, $resource);
        openssl_free_key($resource);

        if (!$result) {
            throw new \Exception('签名出错' . $plainText);
        }

        return base64_encode($sign);
    } catch (\Exception $e) {
        throw $e;
    }
}

/**
 * 公钥验签
 * @param $plainText
 * @param $sign
 * @param $path
 * @return int
 * @throws Exception
 */
function verify($plainText, $sign, $path)
{
    $resource = openssl_pkey_get_public($path);
    $result = openssl_verify($plainText, base64_decode($sign), $resource);
    openssl_free_key($resource);

    if (!$result) {
        throw new \Exception('签名验证未通过,plainText:' . $plainText . '。sign:' . $sign, '02002');
    }

    return $result;
}

/**
 * 发送请求
 * @param $url
 * @param $param
 * @return bool|mixed
 * @throws Exception
 */
function http_post_json($url, $param)
{
    if (empty($url) || empty($param)) {
        return false;
    }
    $param = http_build_query($param);
    try {

        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        //正式环境时解开注释
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);

        if (!$data) {
            throw new \Exception('请求出错');
        }

        return $data;
    } catch (\Exception $e) {
        throw $e;
    }
}

function parse_result($result)
{
    $arr = array();
    $response = urldecode($result);
    $arrStr = explode('&', $response);
    foreach ($arrStr as $str) {
        $p = strpos($str, "=");
        $key = substr($str, 0, $p);
        $value = substr($str, $p + 1);
        $arr[$key] = $value;
    }

    return $arr;
}