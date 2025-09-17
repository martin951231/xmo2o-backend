<?php
/*
 * 东信号码隐私保护对接
 * */
final class DongXinPhone
{
    public $appkey;
    public $appsecret;
    public $base_url;

    public function __construct()
    {
        $this->appkey = C('config.protect_dongxin_appkey');
        $this->appsecret = C('config.protect_dongxin_appsecret');//密钥
        $this->base_url = 'http://spn.caihcom.com:9101';
    }

    /*
     * 签名
     * 签名规则 msgdgt 生成规则如下：
        1）把消息头（appkey 和 ts）、消息体按 key 的字母顺序排序
        2）排序后将密钥、消息头（appkey 和 ts）和消息体的所有 key、value 串起来以 UTF-8 编码进行 MD5 加密，如： MD5(secretkey1value1key2value2..)
        3）生成 32 位大写的摘要字符串，如：BA9854BED1A2986B061E2713F403C752
    */
    private  function getMsgdgt($ts,$data){
        $msgArr['appkey'] = $this->appkey;
        $msgArr['ts'] = $ts;
        $msgArr = array_merge($msgArr,$data);
        ksort($msgArr);

        $msg = $this->appsecret;//密钥
        foreach ($msgArr as $key => $value){
            $msg .= $key.$value;
        }

        $msg = strtoupper(md5($msg));
        return $msg;
    }

    public function curlPost($url,$data,$timeout=15, $type = null){
        $url = $this->base_url.$url;
//        var_dump($url);

        $ch = curl_init();

        // 请求体

        // 请求头
        $headers[] = "Accept: application/json;charset=utf-8";
        $headers[] = "Content-type: application/json;charset=utf-8";
        $ts = date('YmdHis');
        $t = microtime(true);
        $ts = $ts.floor(sprintf('%06d',($t-floor($t))*pow(10,6) )/1000);

        $headers[] = "appkey: ".$this->appkey;
        $headers[] = "ts: ".$ts;
        $headers[] = "msgdgt: ".$this->getMsgdgt($ts,$data);
//        var_dump(json_encode($data));

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/75.0.3770.80 Safari/537.36');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch ,CURLOPT_TIMEOUT ,$timeout);
        $result = curl_exec($ch);
        $meta = curl_getinfo($ch);
//        var_dump($result);

        //关闭curl
        curl_close($ch);
        // echo $result;exit;
        $result = json_decode($result, true);
        return $result;
    }

    /*
    * 绑定隐私号码
    * */
    public function axxBind($data){
        $param = $data;
        $url = '/spn/ext/axx/bind';

        // 请求 ID。
        $data['requestId'] = md5($data['phone'].time().rand(1000,9999));

        // 过期时间（秒），当过期时间到达时自 动解绑；0 表示不限制时间，不会自动解 绑。
        $time = C('config.protect_time');
        $time = $time ? $time : 2;
        $data['expiration'] = $time*86400;
        $data['prvNumSelectMode'] = 'AUTO';
//        $data['subts'] = date('YmdHis');
//        $data['areacode'] = C('config.protect_dongxin_areacode') ? C('config.protect_dongxin_areacode') : '10';
//        $data['anucode'] = '1,2,3';

        $result = $this->curlPost($url,$data,'','json');
        return $result;
    }

}
?>