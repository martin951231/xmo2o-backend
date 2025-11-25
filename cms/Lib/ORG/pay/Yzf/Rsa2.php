<?php
	
/**
 * User: zhouxirui
 * Date: 2017/9/8
 */
class Rsa2
{
    private static $PRIVATE_KEY ='-----BEGIN RSA PRIVATE KEY-----
MIICXwIBAAKBgQC6B9PcMOq2P9tNCIA25rs36OnKzh6KXX848Vj7EnjmGkJGOJmA
iG1oA1LT9r/xMB2BUAqNm91E61njjDwiuFlJ3JCxjfTmnE96CGKPz3t46kq27hSw
riy205/DVcE2+4gWnkVwjxRW7VecWcdzsHFhN0ZAuWAMXadX6JYXv0a2ZwIDAQAB
AoGBAI5stcAL13bGtuBoVLkztzxb3rp9ShaG9IMlDAnVH6iTjA/0/1jPmmuat8w+
Sr+eJci9/gZiwWolgcFbuSKf3wMGZxse+8jevWz1GKb8klVv4HrKzGoE1aaVGHN0
FJ4cbMNxWQfnNpzj65UIkI94AO57LwSRLyDnMpzBKGGCF+DJAkEA2/9j2dXbJxzm
zywPzz73byKz1hzEBZ2q64bLRFjznPJJ01lyaEwxAvS2YVDAXtVS53xNh1aVGdOJ
CH1c+v0p/QJBANh5avA8NZf+Hxs0PpMzscBaG0qq2jfQNX2KA67QKDiDe+R8e/Lc
G27cbS6DQwM9F6SVfGVpePJuJe3m/Y/DjTMCQQDB0+CYk1fTvR4KF8sSEGsWip+h
nJSEUia9/nPmkuyIMdwjjuJCzCY+cXaihPAIXbD+bMEt5XXWXE6WoUUjiZvNAkEA
sPuL17+SHaMRMX+kTVstT1McDw7ifFFI+kt1OV5ME/BDwYmZGPSlpRyoUyfUCPjQ
tKnW217BGIu/jOKWmgxKCwJBAJN+UoZyvKJCsrS9hNY00DI1Wfo6qtEog25HfWyj
1Pk9ZsD7hFmP+lPD6NXjYSlQ5Bla7INcVnSXun3KyTXqbAQ=
-----END RSA PRIVATE KEY-----';
    private static $PUBLIC_KEY  ='-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC6B9PcMOq2P9tNCIA25rs36OnK
zh6KXX848Vj7EnjmGkJGOJmAiG1oA1LT9r/xMB2BUAqNm91E61njjDwiuFlJ3JCx
jfTmnE96CGKPz3t46kq27hSwriy205/DVcE2+4gWnkVwjxRW7VecWcdzsHFhN0ZA
uWAMXadX6JYXv0a2ZwIDAQAB
-----END PUBLIC KEY-----';
    /**
     * 获取私钥
     * @return bool|resource
     */
    private static function getPrivateKey()
    {
        $privKey = self::$PRIVATE_KEY;
        return openssl_pkey_get_private($privKey);
    }
    /**
     * 获取公钥
     * @return bool|resource
     */
    private static function getPublicKey()
    {

        $publicKey = self::$PUBLIC_KEY;
        return openssl_pkey_get_public($publicKey);
    }
    /**
     * 创建签名
     * @param string $data 数据
     * @return null|string
     */
    public function createSign($data = '')
    {
      //  var_dump(self::getPrivateKey());die;
        if (!is_string($data)) {
            return null;
        }
        return openssl_sign($data, $sign, self::getPrivateKey(),OPENSSL_ALGO_SHA256 ) ? base64_encode($sign) : null;
    }

    /**
     * 验证签名
     * @param string $data 数据
     * @param string $sign 签名
     * @return bool
     */
    public function verifySign($data = '', $sign = '')
    {
        if (!is_string($sign) || !is_string($sign)) {
            return false;
        }
        return (bool)openssl_verify(
            $data,
            base64_decode($sign),
            self::getPublicKey(),
            OPENSSL_ALGO_SHA256
        );
    }
}

// $data ='authCode=510950212800578446&ccy=156&institutionCode=3178000003978343&merchantNo=3178000003990790&notifyUrl=http://cha.498.net/bestpay/return.php&operator=3178000003990790&outTradeNo=815047745251956175&remark=1,2,3,4,5,6&requestDate=2017-09-07 16:55:25&subject=test&tradeAmt=1&tradeChannel=API';
$data='buyerLoginNo=18192089766&ccy=156&institutionCode=3178000003978343&ledgerAccount=ledgerAccount&merchantNo=317800000397844&notifyUrl=www.bestpay.com.cn&operator=operator&outTradeNo=2017080801100777000001111851554&productCode=68800020109&requestDate=2017-06-09 13:59:15&subject=subject&tradeAmt=123';

$priKey = '-----BEGIN RSA PRIVATE KEY-----
MIICXwIBAAKBgQC6B9PcMOq2P9tNCIA25rs36OnKzh6KXX848Vj7EnjmGkJGOJmA
iG1oA1LT9r/xMB2BUAqNm91E61njjDwiuFlJ3JCxjfTmnE96CGKPz3t46kq27hSw
riy205/DVcE2+4gWnkVwjxRW7VecWcdzsHFhN0ZAuWAMXadX6JYXv0a2ZwIDAQAB
AoGBAI5stcAL13bGtuBoVLkztzxb3rp9ShaG9IMlDAnVH6iTjA/0/1jPmmuat8w+
Sr+eJci9/gZiwWolgcFbuSKf3wMGZxse+8jevWz1GKb8klVv4HrKzGoE1aaVGHN0
FJ4cbMNxWQfnNpzj65UIkI94AO57LwSRLyDnMpzBKGGCF+DJAkEA2/9j2dXbJxzm
zywPzz73byKz1hzEBZ2q64bLRFjznPJJ01lyaEwxAvS2YVDAXtVS53xNh1aVGdOJ
CH1c+v0p/QJBANh5avA8NZf+Hxs0PpMzscBaG0qq2jfQNX2KA67QKDiDe+R8e/Lc
G27cbS6DQwM9F6SVfGVpePJuJe3m/Y/DjTMCQQDB0+CYk1fTvR4KF8sSEGsWip+h
nJSEUia9/nPmkuyIMdwjjuJCzCY+cXaihPAIXbD+bMEt5XXWXE6WoUUjiZvNAkEA
sPuL17+SHaMRMX+kTVstT1McDw7ifFFI+kt1OV5ME/BDwYmZGPSlpRyoUyfUCPjQ
tKnW217BGIu/jOKWmgxKCwJBAJN+UoZyvKJCsrS9hNY00DI1Wfo6qtEog25HfWyj
1Pk9ZsD7hFmP+lPD6NXjYSlQ5Bla7INcVnSXun3KyTXqbAQ=
-----END RSA PRIVATE KEY-----';

$rsa2 = new Rsa2();
$strSign = $rsa2->createSign($data);      //生成签名
echo $strSign;
echo "\n";
$is_ok = $rsa2->verifySign($data, $strSign); //验证签名
echo $is_ok;
echo "\n";


date_default_timezone_set("Etc/GMT+6");
echo date('Y-m-d H:i:s', strtotime('Mon Sep 11 09:59:57 CST 2017'));
echo strtotime('Mon Sep 11 09:59:57 CST 2017');
?>

