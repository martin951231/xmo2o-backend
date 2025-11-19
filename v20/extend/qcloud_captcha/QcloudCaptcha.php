<?php
require 'QcloudCaptchaUtil.php';
class QcloudCaptcha
{
    private $url;
    private $appid;
    private $appkey;
    private $util;

    /**
     * 构造函数
     *
     * @param string $appid  sdkappid
     * @param string $appkey sdkappid对应的appkey
     */
    public function __construct()
    {
        $this->url = "https://captcha.tencentcloudapi.com/";
        $this->util = new SmsSenderUtil();
    }
	
	
    /**
     * 普通单发
     *
     */
    public function send($ticket, $randstr)
    {
		$urlParamArr = [
			'Action' => 'DescribeCaptchaResult',
			'Version' => '2019-07-22',
			'CaptchaType' => 9,
			'Ticket' => $ticket,
			'UserIp' => get_client_ip(),
			'Randstr' => $randstr,
			'CaptchaAppId' => cfg('qcloud_captcha_appid'),
			'AppSecretKey' => cfg('qcloud_captcha_appkey'),
			'Timestamp' => time(),
			'Nonce' => $this->util->getRandom(),
			'SecretId' => cfg('qcloud_captcha_SecretId'),
		];
		
		ksort($urlParamArr);

		$signStr = "GETcaptcha.tencentcloudapi.com/?";
		foreach ( $urlParamArr as $key => $value ) {
			$signStr = $signStr . $key . "=" . $value . "&";
		}
		$signStr = substr($signStr, 0, -1);
		$urlParamArr['Signature'] = base64_encode(hash_hmac("sha1", $signStr, cfg('qcloud_captcha_SecretKey'), true));
		
        $wholeUrl = $this->url . "?" . http_build_query($urlParamArr);
        $result = json_decode($this->util->sendCurlGet($wholeUrl), true);
		return $result;
    }
}
?>