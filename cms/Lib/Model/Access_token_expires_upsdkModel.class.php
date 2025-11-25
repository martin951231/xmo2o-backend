<?php
class Access_token_expires_upsdkModel extends Model
{
	public function get_access_token()
	{
		$access_token = S('access_token_expires_upsdk');
		if($access_token){
			return array('errcode' => 0, 'access_token' => $access_token);
		}
		$params = [
			'appId' => C('config.upsdk_app_id'),
			'nonceStr' => createRandomStr(16),
			'timestamp' => time(),
			'secret' => C('config.upsdk_app_secret'),
		];
		
		ksort($params);
		$paramsJoined = array();
		foreach($params as $param => $value) {
			$paramsJoined[] = "$param=$value";
		}
		$paramData = implode('&', $paramsJoined);

		$params['signature']  = $this->sha256($paramData);
		
		$res = httpRequest('https://open.95516.com/open/access/1.0/backendToken', 'POST', json_encode($params), ["Content-Type: application/json;charset=utf8"]);
		$result = json_decode($res[1], true);
		if($result['resp'] == '00'){
			S('access_token_expires_upsdk', $result['params']['backendToken'], 3600);
			return array('errcode' => 0, 'access_token' => $result['params']['backendToken']);
		}
		
		return array('errcode' => 1, 'errmsg' => $result['msg']);
	}
	
	public function sha256($data, $rawOutput = false)
	{
        if (!is_scalar($data)) {
            return false;
        }
        $data = (string)$data;
        $rawOutput = !!$rawOutput;
        return hash('sha256', $data, $rawOutput);
	}

	function curlGet($url){
		$ch = curl_init();
		$header = "Accept-Charset: utf-8";
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
		//curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$temp = curl_exec($ch);
		return $temp;
	}
	
}
?>