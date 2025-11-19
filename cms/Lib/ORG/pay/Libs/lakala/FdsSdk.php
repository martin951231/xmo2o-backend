<?php

class FdsSdk
{
	//应用ID
	public static $appId;
	//DSA私钥文件路径
	public static $DSAPrivateKeyFilePath;
	//DSA私钥密碼
	public static $DSAPrivateKeyFilePassword;
	//DSA公钥文件路径
	public static $DSAPublicKeyFilePath;
	//RSA私钥文件路径
	public static $RSAPrivateKeyFilePath;
	//RSA私钥密碼
	public static $RSAPrivateKeyFilePassword;
	//RSA公钥文件路径
	public static $RSAPublicKeyFilePath;
	//网关
	public static $gatewayUrl = "http://123.151.202.122:8080/fds/service/soa";
	//api版本
	public static $apiVersion = "2.0";
	// 表单提交字符集编码
	public static $postCharset = "UTF-8";
	// 文件字符集编码
	private static $fileCharset = "UTF-8";
	//签名类型
	public static $signType = "DSA";

	const STATUS_OK   = 'OK';
	const ERR_MESSAGE = 'message';
	const SIGNED_VALUE = 'result';

	private $bizContent;
	private $apiMethodName;
	static $bop;

	public static function getCashier()
	{
		if (empty(self::$bop)) {
			self::$bop = new FdsSdk();
		}
		return self::$bop;
	}

	public static function getService()
	{
		if (empty(self::$bop)) {
			self::$bop = new FdsSdk();
		}
		return self::$bop;
	}



	// 个人开户
	public function createPersonMember($out_member_no)
	{
		$param["out_member_no"] = $out_member_no;
		$this->bizContent = json_encode($param);
		$this->apiMethodName = 'member.person.create';
		$result = $this->execute();
		return $result;
	}



	public function getNotifyResult($request)
	{
		//获取签名
		$sign = $request['sign'];
		//组装签名原串
		$signStr = $request['app_id'] . $request['timestamp'] . $request['version'] . $request['event_type'] . $request['event'];
		//验签
		$seccess = $this->verify($signStr, $sign, self::$signType);
		if (!$seccess) {
			throw new Exception("返回值签名验证失败");
		}
		return $request;
	}

	public static function setAppId($appId)
	{
		return self::$appId = $appId;
	}

	public static function setServerUrl($gatewayUrl)
	{
		return self::$gatewayUrl = $gatewayUrl;
	}

	public static function getServerUrl()
	{
		return self::$gatewayUrl;
	}

	public static function setApiVersion($apiVersion)
	{
		return self::$apiVersion = $apiVersion;
	}

	public static function setDSAKey($DSAPublicKeyFilePath, $DSAPrivateKeyFilePath, $DSAPrivateKeyFilePassword)
	{
		self::$DSAPublicKeyFilePath = $DSAPublicKeyFilePath;
		self::$DSAPrivateKeyFilePath = $DSAPrivateKeyFilePath;
		self::$DSAPrivateKeyFilePassword = $DSAPrivateKeyFilePassword;
	}

	public static function setRSAKey($RSAPublicKeyFilePath, $RSAPrivateKeyFilePath, $RSAPrivateKeyFilePassword)
	{
		self::$RSAPublicKeyFilePath = $RSAPublicKeyFilePath;
		self::$RSAPrivateKeyFilePath = $RSAPrivateKeyFilePath;
		self::$RSAPrivateKeyFilePassword = $RSAPrivateKeyFilePassword;
	}

	public function execute($authToken = null, $appInfoAuthtoken = null)
	{
		//  如果两者编码不一致，会出现签名验签或者乱码
		if (strcasecmp(self::$fileCharset, self::$postCharset)) {

			// writeLog("本地文件字符集编码与表单提交编码不一致，请务必设置成一样，属性名分别为postCharset!");
			throw new Exception("文件编码：[" . self::$fileCharset . "] 与表单提交编码：[" . self::$postCharset . "]两者不一致!");
		}
		$iv = self::$apiVersion;

		//组装系统参数
		$sysParams["app_id"] = self::$appId;
		$sysParams["version"] = $iv;
		$sysParams["service"] = $this->apiMethodName;
		$sysParams["timestamp"] = time();

		//获取业务参数
		$sysParams["params"] = $this->bizContent;
		//签名
		$sysParams["sign"] = $this->generateSign($sysParams, self::$signType);
		$result = $this->request($sysParams);
		$result = $this->checkResult($result);
		return $result;
	}

	public function execute2($authToken = null, $appInfoAuthtoken = null)
	{
		//  如果两者编码不一致，会出现签名验签或者乱码
		if (strcasecmp(self::$fileCharset, self::$postCharset)) {

			// writeLog("本地文件字符集编码与表单提交编码不一致，请务必设置成一样，属性名分别为postCharset!");
			throw new Exception("文件编码：[" . self::$fileCharset . "] 与表单提交编码：[" . self::$postCharset . "]两者不一致!");
		}
		$iv = self::$apiVersion;

		//组装系统参数
		$sysParams["app_id"] = self::$appId;
		$sysParams["version"] = $iv;
		$sysParams["service"] = $this->apiMethodName;
		$sysParams["timestamp"] = time();

		//获取业务参数
		foreach ($this->bizContent as $key => $value) {
			$sysParams[$key] = $value;
		}
		//签名
		$sysParams["sign"] = $this->generateSign($sysParams, self::$signType);

		$result = $this->request2($sysParams);
		$result = $this->checkResult($result);
		return $result;
	}

	public function execute3($authToken = null, $appInfoAuthtoken = null)
	{
		//  如果两者编码不一致，会出现签名验签或者乱码
		if (strcasecmp(self::$fileCharset, self::$postCharset)) {

			// writeLog("本地文件字符集编码与表单提交编码不一致，请务必设置成一样，属性名分别为postCharset!");
			throw new Exception("文件编码：[" . self::$fileCharset . "] 与表单提交编码：[" . self::$postCharset . "]两者不一致!");
		}
		$iv = self::$apiVersion;

		//组装系统参数
		$sysParams["app_id"] = self::$appId;
		$sysParams["version"] = $iv;
		$sysParams["service"] = $this->apiMethodName;
		$sysParams["timestamp"] = time();

		//获取业务参数
		$sysParams["params"] = $this->bizContent;
		//签名
		$sysParams["sign"] = $this->generateSign($sysParams, self::$signType);

		return $sysParams;
	}

	private function request($args)
	{
		global $log;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, self::$gatewayUrl);
		$sb = '';
		$reqbody = array();
		foreach ($args as $entry_key => $entry_value) {
			$sb .= $entry_key . '=' . urlencode($entry_value) . '&';
		}
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $sb);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_AUTOREFERER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-length', count($reqbody)));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		$result = curl_exec($ch);
		curl_close($ch);
		fdump_api([self::$gatewayUrl,$args,$result],'pay/LakalaFdsSdkLog',true);
		return $result;
	}

	private function request2($args)
	{
		global $log;
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, self::$gatewayUrl);
		$sb2 = array();
		$reqbody = array();
		foreach ($args as $entry_key => $entry_value) {
			$sb2[$entry_key] = $entry_value;
		}
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $sb2);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_AUTOREFERER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-length', count($reqbody)));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		$result = curl_exec($ch);
		curl_close($ch);
		fdump_api([self::$gatewayUrl,$args,$result],'pay/LakalaFdsSdkLog',true);
		return $result;
	}

	/**
	 * 检查服务调用结果是否正确，如果服务调用失败，则抛出失败异常
	 * @param result 服务调用的返回结果对象
	 * @throws Exception
	 */
	private function checkResult($result)
	{
		global $log;
		$arr = json_decode($result, true);
		if (is_string($arr['response'])) {
			$response = json_decode($arr['response'], true);
		} else {
			$response = $arr['response'];
		}
		$sign = $arr['sign'];
		if ($sign != null) {
			$seccess = $this->verify($arr['response'], $sign, self::$signType);
			if (!$seccess)
				throw new Exception("返回值签名验证失败");
		}
		return $response;
	}

	/**
	 * 校验$value是否非空
	 *  if not set ,return true;
	 *    if is null , return true;
	 **/
	protected function checkEmpty($value)
	{
		if (!isset($value))
			return true;
		if ($value === null)
			return true;
		if (trim($value) === "")
			return true;

		return false;
	}

	//验签
	function verify($data, $sign, $signType = 'RSA')
	{
		//读取公钥文件
		$pubKey = file_get_contents(self::$DSAPublicKeyFilePath);
		//转换为openssl格式密钥
		$res = openssl_get_publickey($pubKey);

		($res) or die('公钥错误。请检查公钥文件格式是否正确');

		//调用openssl内置方法验签，返回bool值
		if (!is_string($data)) {
			$data = json_encode($data);
		}
		if ("RSA2" == $signType) {
			$result = openssl_verify($data, base64_decode($sign), $res, OPENSSL_ALGO_SHA256);
		} else if ("DSA" == $signType) {
			$result = openssl_verify($data, base64_decode($sign), $res, OPENSSL_ALGO_DSS1);
		} else {
			$result = openssl_verify($data, base64_decode($sign), $res);
		}
		if ($result == 1) {
			$result = true;
		} else {
			$result = false;
		}
		if (!$this->checkEmpty(self::$DSAPublicKeyFilePath)) {
			openssl_free_key($res);
		}
		return $result;
	}

	public function generateSign($params, $signType = "DSA")
	{
		return $this->sign($this->getSignContent($params), $signType);
	}

	protected function getSignContent($params)
	{
		$stringToBeSigned = '';
		if (array_key_exists("app_id", $params)) {
			$stringToBeSigned .= $params['app_id'];
		}
		if (array_key_exists("timestamp", $params)) {
			$stringToBeSigned .= $params['timestamp'];
		}
		if (array_key_exists("version", $params)) {
			$stringToBeSigned .= $params['version'];
		}
		if (array_key_exists("service", $params)) {
			$stringToBeSigned .= $params['service'];
		}
		if (array_key_exists("params", $params)) {
			$stringToBeSigned .= $params['params'];
		}
		if (array_key_exists("image_type", $params)) {
			$stringToBeSigned .= $params['image_type'];
		}
		if (array_key_exists("image_name", $params)) {
			$stringToBeSigned .= $params['image_name'];
		}
		//$stringToBeSigned = $params['app_id'] . $params['timestamp'] . $params['version'] . $params['service'] . $params['params'];
		$stringToBeSigned = str_replace("\r\n", "", $stringToBeSigned);
		return $stringToBeSigned;
	}

	protected function sign($data, $signType = "DSA")
	{
		if ("DSA" == $signType) {
			$file = file_get_contents(self::$DSAPrivateKeyFilePath);
			openssl_pkcs12_read($file, $res, self::$DSAPrivateKeyFilePassword);
		} else {
			$priKey = file_get_contents(self::$RSAPrivateKeyFilePath);
			$res = openssl_get_privatekey($priKey, self::$RSAPrivateKeyFilePassword);
		}

		($res) or die('您使用的私钥格式错误，请检查私钥配置');
		if ("RSA2" == $signType) {
			openssl_sign($data, $sign, $res, OPENSSL_ALGO_SHA256);
		} else if ("DSA" == $signType) {
			openssl_sign($data, $sign, $res['pkey'], OPENSSL_ALGO_DSS1);
		} else {
			openssl_sign($data, $sign, $res);
		}
		if ("DSA" != $signType && !$this->checkEmpty(self::$DSAPrivateKeyFilePath)) {
			openssl_free_key($res);
		}
		$sign = base64_encode($sign);
		return $sign;
	}

	public function rsaEncrypt($data)
	{
		//读取公钥文件
		$pubKey = file_get_contents(self::$RSAPublicKeyFilePath);

		//转换为openssl格式密钥
		$res = openssl_get_publickey($pubKey);
		if (!openssl_public_encrypt($data, $chrtext, $res, OPENSSL_PKCS1_PADDING)) {
			echo "<br/>" . openssl_error_string() . "<br/>";
		}
		return base64_encode($chrtext);
	}

	public function rsaDecrypt($data)
	{
		//读取私钥文件
		$priKey = file_get_contents(self::$RSAPrivateKeyFilePath);
		//转换为openssl格式密钥
		openssl_pkcs12_read($priKey, $res, self::$RSAPrivateKeyFilePassword);
		$res1 = openssl_pkey_get_private($res['pkey']);
		if (!openssl_private_decrypt(base64_decode($data), $dcyCont, $res1, OPENSSL_PKCS1_PADDING)) {
			echo "<br/>---" . openssl_error_string() . "<br/>";
		}
		return $dcyCont;
	}


	// 消费
	public function orderConsume($param)
	{
		$this->bizContent = json_encode($param);
		$this->apiMethodName = 'order.consume.request';
		$result = $this->execute();
		if ($result['status'] != self::STATUS_OK) {
			throw new \Exception($result[self::ERR_MESSAGE]);
		}
		if ($result[self::SIGNED_VALUE] == null) {
			throw new \Exception("请求异常");
		}
		if (!empty($result[self::SIGNED_VALUE]['error_message'])) {
			throw new \Exception($result[self::SIGNED_VALUE]['error_message']);
		}
		return $result;
	}

	//查询订单详细信息
	public function orderDetailInfoGet($outOrderNo)
	{
		$param = [
			'out_order_no' => $outOrderNo
		];
		$this->bizContent = json_encode($param);
		$this->apiMethodName = 'order.detail.info.get';
		$result = $this->execute();
		if ($result['status'] != self::STATUS_OK) {
			throw new \Exception($result[self::ERR_MESSAGE]);
		}
		if ($result[self::SIGNED_VALUE] == null) {
			throw new \Exception("请求异常");
		}
		return $result;
	}

	//退款
	public function orderConsumeRefund($param)
	{
		$this->bizContent = json_encode($param);
		$this->apiMethodName = 'order.consume.refund';
		$result = $this->execute();
		if ($result['status'] != self::STATUS_OK) {
			throw new \Exception($result[self::ERR_MESSAGE]);
		}
		if ($result[self::SIGNED_VALUE] == null) {
			throw new \Exception("请求异常");
		}
		return $result;
	}

	//2.1、新版注册开户（H5-URL）
	public function memberOpenPartPageUrlGet($param)
	{
		$this->bizContent = json_encode($param);
		$this->apiMethodName = 'member.open.part.page.url.get';
		$result = $this->execute();
		if ($result['status'] != self::STATUS_OK) {
			throw new \Exception($result[self::ERR_MESSAGE]);
		}
		if ($result[self::SIGNED_VALUE] == null) {
			throw new \Exception("请求异常");
		}
		return $result;
	}

	//商户绑定
	public function memberMerchantBind($param)
	{
		$this->bizContent = json_encode($param);
		$this->apiMethodName = 'member.merchant.bind';
		$result = $this->execute();
		if ($result['status'] != self::STATUS_OK) {
			throw new \Exception($result[self::ERR_MESSAGE]);
		}
		if ($result[self::SIGNED_VALUE] == null) {
			throw new \Exception("请求异常");
		}
		return $result;
	}

	//商户解绑
	public function memberMerchantUnbind($param)
	{
		$this->bizContent = json_encode($param);
		$this->apiMethodName = 'member.merchant.unbind';
		$result = $this->execute();
		if ($result['status'] != self::STATUS_OK) {
			throw new \Exception($result[self::ERR_MESSAGE]);
		}
		if ($result[self::SIGNED_VALUE] == null) {
			throw new \Exception("请求异常");
		}
		return $result;
	}

	//订单完成（确权分账）
	public function orderComplete($param)
	{
		$this->bizContent = json_encode($param);
		$this->apiMethodName = 'order.complete';
		$result = $this->execute();
		if ($result['status'] != self::STATUS_OK) {
			throw new \Exception($result[self::ERR_MESSAGE]);
		}
		if ($result[self::SIGNED_VALUE] == null) {
			throw new \Exception("请求异常");
		}
		return $result;
	}

	//下载对账单
	public function orderCheckDownload($param)
	{
		$this->bizContent = json_encode($param);
		$this->apiMethodName = 'order.check.download';
		$result = $this->execute3();
		return self::$gatewayUrl . '?' . http_build_query($result);
	}

	//会员绑卡相关页面（H5-URL）
	public function memberBankCardPageUrlGet($param)
	{
		$this->bizContent = json_encode($param);
		$this->apiMethodName = 'member.bank.card.page.url.get';
		$result = $this->execute();
		if ($result['status'] != self::STATUS_OK) {
			throw new \Exception($result[self::ERR_MESSAGE]);
		}
		if ($result[self::SIGNED_VALUE] == null) {
			throw new \Exception("请求异常");
		}
		return $result;
	}
}
