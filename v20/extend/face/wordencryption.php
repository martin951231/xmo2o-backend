<?php
/**
 *======================================================
 * Created by : PhpStorm
 * User: zhanghan
 * Date: 2021/11/30
 * Time: 11:16
 *======================================================
 */

namespace  face;


class wordencryption
{
    // 加密秘钥
    public $word_key = 'wordencrypt202009020931';

    /**
     * @param string $string    要加密/解密的字符串
     * @param string $operation    类型，E 加密；D 解密
     * @param string $key 密钥
     * @return mixed|string
     */
    function encrypt($string, $operation, $key = 'encrypt')
    {
        $key = md5($key);
        $key_length = strlen($key);
        $string = $operation == 'D' ? base64_decode($string) : substr(md5($string . $key), 0, 8) . $string;
        $string_length = strlen($string);
        $rndkey = $box = array();
        $result = '';
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($key[$i % $key_length]);
            $box[$i] = $i;
        }
        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }
        if ($operation == 'D') {
            if (substr($result, 0, 8) == substr(md5(substr($result, 8) . $key), 0, 8)) {
                return substr($result, 8);
            } else {
                return '';
            }
        } else {
            return str_replace('=', '', base64_encode($result));
        }
    }

    /**
     * @param string $string    要加密的字符串
     * word_encryption constructor.
     * @param $string
     */
    public function text_encryption($string) {
        if (!$string) {
            return false;
        }
        $word_key = $this->word_key;
        $word = $this->encrypt($string,'E',$word_key);
        return $word;
    }

    /**
     * @param string $string    要解密的字符串
     * word_encryption constructor.
     * @param $string
     */
    public function text_decrypt($string) {
        if (!$string) {
            return false;
        }
        if (strstr($string, 'upload/') !== false) {
            // 存在路径upload 原数据返回
            return $string;
        }
        $word_key = $this->word_key;
        $word = $this->encrypt($string,'D',$word_key);
        return $word;
    }
}