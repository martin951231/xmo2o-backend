<?php
/**
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2020/11/12 16:16
 */
class aes{
    private $_secret = '';
    private $hex_iv = '00000000000000000000000000000000';

    /**
     * rsa constructor.
     * @param string $encrypted_key 加密公钥串
     */
    public function __construct($secret) {
        $this->_secret =$secret;
    }

    public function decodeECB128($secretData){
        $data = openssl_decrypt($secretData,'aes-128-ecb',$this->_secret,OPENSSL_RAW_DATA);
        return $data;
    }
    public function encodeECB128($data){
         //这里需要配置options参数OPENSSL_RAW_DATA裸数据，否则默认是base64_encode格式化过得数据
        $data = openssl_encrypt($data,'aes-128-ecb',$this->_secret,OPENSSL_RAW_DATA);
        return $data;
    }

    /*
     * aes 给PHP文件加密
     * 写入设置文件
     */
    public function filecrypt($filename, $file_new)
    {
        if (is_file($filename) && is_writable($filename)) {
//            $contents = file_get_contents($filename);
            $contents = php_strip_whitespace($filename);
            $data = openssl_encrypt($contents, 'AES-128-ECB', $this->_secret, OPENSSL_RAW_DATA, $this->hexToStr($this->hex_iv));
            $data = base64_encode($data);
            return file_put_contents($file_new, $data);
        }
        return false;
    }

    function hexToStr($hex)
    {
        $string='';
        for ($i=0; $i < strlen($hex)-1; $i+=2)
        {
            $string .= chr(hexdec($hex[$i].$hex[$i+1]));
        }
        return $string;
    }
}