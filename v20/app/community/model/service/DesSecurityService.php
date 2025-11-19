<?php
namespace app\community\model\service;

class DesSecurityService
{
    private $key;
    private $iv;


    public function __construct(string $key = 'LmMGStGtOpF4xNyvYt54EQ=='){
        $srcKey = base64_decode($key);
        $this->key = substr($srcKey, 0, 8);
        $this->iv = substr($srcKey,8);
    }



    /**
     * 加密
     *
     * @param array $data
     * @return string
     */
    public function encrypt(array $data):string{

        $content = json_encode($data,JSON_UNESCAPED_UNICODE);
        $srcBuf = md5($content,true) . $content;
        // $result = base64_encode(
        //     openssl_encrypt($srcBuf,'DES-CBC',$this->key,0,$this->iv)
        // );
        $result = openssl_encrypt($srcBuf,'DES-CBC',$this->key,0,$this->iv);
        return $result;
    }
}
