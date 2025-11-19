<?php
/**
 * Created by PhpStorm.
 * User: wanzy
 * DateTime: 2020/11/12 16:10
 */

class rsa{

    public $encrypted_key;

    /**
     * rsa constructor.
     * @param string $encrypted_key 加密公钥串
     */
    public function __construct($encrypted_key) {
        $this->encrypted_key = $encrypted_key;
    }

    /**
     * Notes: rsa加密
     * @param string $rand_data16 加密字段
     * @return string
     * @author: wanzy
     * @date_time: 2020/11/12 16:14
     */
    public function encrypt($rand_data16) {
        if (!$rand_data16) {
            $rand_data16 = generate_password(16);
        }
        $pubKey = $this->get_public_key($this->encrypted_key);
        $pu_key = openssl_pkey_get_public($pubKey);
        openssl_public_encrypt($rand_data16, $data, $pu_key);
        $data = base64_encode($data);
        return $data;
    }

    public function get_public_key($pubKey){
        $p_key = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($pubKey, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";
        return $p_key;
    }
}