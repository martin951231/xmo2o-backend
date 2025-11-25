<?php
/**
 * Created by PhpStorm.
 * Title：PHP版DES加解密类
 *        可与java的DES(DESede/CBC/PKCS5Padding)加密方式兼容
 * Author: zhubaodi
 * Date Time: 2022/1/10 11:33
 */

namespace tools;


class Ecb
{
    private static $_instance = NULL;
    var $key;//秘钥向量
    var $iv;//混淆向量 ->偏移量

    function __construct()
    {
        $this->key = env('DES_KEY');
        $this->iv = env('DES_IV');
    }

    public static function encrypt($string, $key)
    {
        if (substr(PHP_VERSION, 0, 1) == '7') {
            return self::opensslEncrypt($string, $key);
        } else {
            return self::mcryptEncrypt($string, $key);
        }

    }

    public static function decrypt($string, $key)
    {
        if (substr(PHP_VERSION, 0, 1) == '7') {
            return self::opensslDecrypt($string, $key);
        } else {
            return self::mcryptDecrypt($string, $key);
        }

    }

    /**
     * [encrypt description]
     * 使用mcrypt库进行加密
     * @param  [type] $string
     * @param  [type] $key
     * @return [type]
     */
    public static function mcryptEncrypt($string, $key)
    {
        $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
        $string = self::pkcs7_pad($string, $size);
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
        $iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);//MCRYPT_DEV_URANDOM
        mcrypt_generic_init($td, $key, $iv);
        $data = mcrypt_generic($td, $string);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        return base64_encode($data);
    }


    /**
     * [decrypt description]
     * 使用mcrypt库进行解密
     * @param  [type] $sStr
     * @param  [type] $sKey
     * @return [type]
     */
    public static function mcryptDecrypt($sStr, $sKey)
    {
        $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB), MCRYPT_RAND);//MCRYPT_DEV_URANDOM
        $decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $sKey, base64_decode($sStr), MCRYPT_MODE_ECB, $iv);
        return self::pkcs7_unpad($decrypted);
    }

    /**
     * [opensslDecrypt description]
     * 使用openssl库进行加密
     * @param  [type] $sStr
     * @param  [type] $sKey
     * @return [type]
     */
    public static function opensslEncrypt($sStr, $sKey, $method = 'AES-128-ECB')
    {
        $str = openssl_encrypt($sStr, $method, $sKey);
        return $str;
    }

    /**
     * [opensslDecrypt description]
     * 使用openssl库进行解密
     * @param  [type] $sStr
     * @param  [type] $sKey
     * @return [type]
     */
    public static function opensslDecrypt($sStr, $sKey, $method = 'AES-128-ECB')
    {
        $str = openssl_decrypt($sStr, $method, $sKey);
        return $str;
    }


    private static function pkcs7_pad($text, $blocksize)
    {
        $pad = $blocksize - (strlen($text) % $blocksize);

        return $text . str_repeat(chr($pad), $pad);
    }

    private static function pkcs7_unpad($text)
    {
        $pad = ord($text[strlen($text) - 1]);

        if ($pad > strlen($text)) return false;

        if (strspn($text, chr($pad), strlen($text) - $pad) != $pad) return false;

        return substr($text, 0, -1 * $pad);
    }
}