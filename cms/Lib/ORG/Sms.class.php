<?php
final class Sms
{
	public $topdomain;
	
	public $key;
	
	public $smsapi_url;
	
	/**
	 * 
	 * 初始化接口类
	 * @param int $userid 用户id
	 * @param int $productid 产品id
	 * @param string $sms_key 密钥
	 */
	public function __construct()
	{
		
	}
	public function checkmobile($mobilephone)
	{
		$mobilephone = trim($mobilephone);
// 		if (preg_match("/^13[0-9]{1}[0-9]{8}$|15[01236789]{1}[0-9]{8}$|18[01236789]{1}[0-9]{8}$/", $mobilephone)) {
		if (preg_match("/^1[0-9]{10}$/", $mobilephone)) {
			return  $mobilephone;
		} else {
			return false;
		}
	}
	/**
	 * 
	 * 批量发送短信
	 * @param array $mobile 手机号码
	 * @param string $content 短信内容
	 * @param datetime $send_time 发送时间
	 * @param string $charset 短信字符类型 gbk / utf-8
	 * @param string $id_code 唯一值 、可用于验证码
	 * $data = array(mer_id, store_id, content, mobile, uid, type);
	 */
	public function sendSms($data = array(), $send_time = '', $charset = 'utf-8', $id_code = '')
	{
		fdump_api([file_get_contents('php://input'), $_POST, $_GET, $data], 'sendSms_' . date('H'), true);
		
		if ($data) {
			$type = isset($data['type']) ? $data['type'] : 'meal';
			if($type == 'market' && !C('config.market_sms_enable')){	//批发市场短信控制
				return false;
			}
			$sendto = isset($data['sendto']) ? $data['sendto'] : 'user';
			$mer_id = isset($data['mer_id']) ? intval($data['mer_id']) : 0;
			$store_id = isset($data['store_id']) ? intval($data['store_id']) : 0;
			$village_id = isset($data['village_id']) ? intval($data['village_id']) : 0;
			$uid = isset($data['uid']) ? intval($data['uid']) : 0;
			//if (empty($mer_id)) return 'mer_id is null';
			$content = isset($data['content']) ? Sms::_safe_replace($data['content']) : '';
			if (empty($content)) return 'send content is null';
			$mobile = isset($data['mobile']) ? $data['mobile'] : '';
			$nationCode = isset($data['nationCode']) ? $data['nationCode'] : '';
			if (empty($mobile)) return 'phone is null';
            $village_send_id = isset($data['village_send_id']) ? intval($data['village_send_id']) : 0;
			
			//语音通知
			$is_voice = isset($data['is_voice']) ? intval($data['is_voice']) : 0;
			
			//O2O多个号码以空格分开，取最后一个号码
			$mobileArr = array();
			$phone_array = explode(' ', $mobile);
			foreach ($phone_array as $phone) {
				if (Sms::checkmobile($phone)) {
					$mobileArr[] = $phone;
				}
			}
			if (count($mobileArr) > 1) {
				$mobile = array_pop($mobileArr);
			}
            $sms_sign=C('config.sms_sign');
            if (! $village_id && I('village_id')) {
                $village_id = I('village_id');
            }
            if (!empty($village_id)){
                $arr = D('House_village')->checkVillageSms($village_id, 1, true);
                if (! $arr) {
                    self::add_sms_record($data,-2);
                    return false;
                }
                if (!empty($arr['sms_sign'])) {
                    $sms_sign = $arr['sms_sign'];
                }
            }
            fdump_api([$village_id,$sms_sign], 'sendSms_' . date('H'), true);
			$data = array(
				'o2o_type' => $type,
				'o2o_sendto' => $sendto,
				'o2o_mer_id' => $mer_id,
				'o2o_store_id' => $store_id,
				'o2o_village_id' => $village_id,
                'o2o_uid' => $uid,
                'village_send_id' => $village_send_id,
				
				
				'topdomain' => C('config.sms_server_topdomain'),
				'key' => trim(C('config.sms_key')),
				'token' => $mer_id . 'o2opigcms',
				'content' => $content,
				'mobile' => trim($mobile),
				'sign' => trim($sms_sign),
				'is_voice' => $is_voice,
				'nationCode' => $nationCode,
			);
			
			if(empty($data['nationCode'])){
				if(C('config.open_ali_national_sms')){
					if($sendto=='user'){
						$user = M('User')->where(array('uid'=>$uid))->find();
						$data['nationCode'] = $user['phone_country_type']?$user['phone_country_type']:C('config.ali_national_sms_default_country');
					}else if($sendto=='merchant'){
						$mer = M('Merchant')->where(array('mer_id'=>$mer_id))->find();
						$data['nationCode'] = $mer['phone_country_type']?$mer['phone_country_type']:C('config.ali_national_sms_default_country');
					}
				}else if(C('config.open_twilio_sms')){
					if($sendto=='user'){
						$user = M('User')->where(array('uid'=>$uid))->find();
						$data['nationCode'] = $user['phone_country_type']?$user['phone_country_type']:C('config.twilio_sms_default_country');
					}else if($sendto=='merchant'){
						$mer = M('Merchant')->where(array('mer_id'=>$mer_id))->find();
						$data['nationCode'] = $mer['phone_country_type']?$mer['phone_country_type']:C('config.twilio_sms_default_country');
					}
				}else if(C('config.open_qcloud_sms')){
					if($sendto=='user'){
						$user = M('User')->where(array('uid'=>$uid))->find();
						$data['nationCode'] = $user['phone_country_type']?$user['phone_country_type']:C('config.qcloud_sms_default_country');
					}else if($sendto=='merchant'){
						$mer = M('Merchant')->where(array('mer_id'=>$mer_id))->find();
						$data['nationCode'] = $mer['phone_country_type']?$mer['phone_country_type']:C('config.qcloud_sms_default_country');
					}
				}
			}
			
			if(C('config.sms_send_type')){
				import('@.ORG.plan.plan_msg');
				$msg_class = new plan_msg();
				$param = array(
					'type' => '1',
					'content' => $data,
					'sort' => 9999,
				);
				$msg_class->addTask($param);
			}else{
				self::sendSmsData($data);
			}
			
		}
	}

	public function sendSmsData($data){
        if (isset($data['o2o_village_id']) && $data['o2o_village_id']>0) {
            $arr = D('House_village')->checkVillageSms($data['o2o_village_id'], 1, true);
            if (! $arr) {
                self::add_sms_record($data,-2);
                return false;
            }
        }
		if(C('config.open_ali_national_sms') && empty($data['is_voice'])){
			import('@.ORG.AliNationSms');
			$sms = new AliNationSms();
			$sms->send($data);
		}elseif(C('config.open_twilio_sms') && empty($data['is_voice'])){
			import('@.ORG.TwilioSms');
			$sms = new TwilioSms();
			$sms->send($data);
		}else if(C('config.open_aliyun_sms') && empty($data['is_voice'])){
			Ali_sms::sendSms($data);
		}else if(C('config.open_huaweiyun_sms') && empty($data['is_voice'])){
			//dump($data);die;
			Huawei_sms::sendSms($data);
		}else if(C('config.open_qcloud_sms') && ($data['nationCode'] != '86' || !C('config.sms_key')) && empty($data['is_voice'])){
			include_once 'qcloud_sms/Sms.class.php';
            $sms_sign=C('config.sms_sign');
            if(isset($data['sign']) && $data['sign']){
                $sms_sign=$data['sign'];
            }
            $sms_sign=trim($sms_sign);
			$Content = '【'.$sms_sign.'】'.$data['content'];
			$sms = new QcloudSms();
			$res = $sms->send(0, ($data['nationCode'] ? $data['nationCode'] : '86'), $data['mobile'], $Content);
			$send_time = time();
			
			$res = json_decode($res,true);
            self::add_sms_record($data,$res['result']);
			//echo $res;
		}else if(C('config.sms_guodu_operid') && empty($data['is_voice'])){
			$OperID = C('config.sms_guodu_operid');
			$OperPass = C('config.sms_guodu_operpass');
			$Content = $data['content'];
			$DesMobile = $data['mobile'];
			Sms::postGuoduSendMsg($data,$OperID, $OperPass, $SendTime, $ValidTime, $AppendID, $Content_Type, $Content, $DesMobile);
		}else{
			
			//nxcloud 通道 语音通知
			if(!empty($data['is_voice']) && C('config.nxcloud_appkey')){
				$postData = [
					'appkey' => C('config.nxcloud_appkey'),
					'secretkey' => C('config.nxcloud_secretkey'),
					'phone' => $data['mobile'],
					'country_code' => $data['nationCode'],
					'show_phone' => $data['nationCode'] . $data['mobile'],
					'content' => $data['content'],
					'lang' => 'zh',
				];
				import('ORG.Net.Http');
				$http = new Http();
				$headersArr = ['Content-Type: application/x-www-form-urlencoded'];
				$return = Http::curlPostOwn(C('config.nxcloud_url'), http_build_query($postData), $headersArr);
				$returnArr = json_decode($return, true);
				fdump([$postData, $returnArr], 'api/log/' . date('Ymd') . '/nxcloud_sms', true);
				
				//增加到本地数据库
                self::add_sms_record($data, $returnArr['code']);
				
				return true;
			}
			
			
			$post = '';
			foreach ($data as $k => $v) {
				$post .= $k . '=' . $v .'&';
			}

			$smsapi_senturl = 'http://up.pigcms.cn/oa/admin.php?m=sms&c=sms&a=send&productid=3';
			$return = Sms::_post($smsapi_senturl, 0, $post);
			$arr = explode('#', $return);
            
            fdump_api([$return,$arr,$post,$smsapi_senturl], 'sendSms_0412_' . date('H'), true);
			//增加到本地数据库
            self::add_sms_record($data, $arr[0]);
            return true;
		}
	}
	
	public function postGuoduSendMsg($data,$OperID, $OperPass, $SendTime, $ValidTime, $AppendID, $Content_Type, $Content, $DesMobile){
		$Content = '【'.trim(C('config.sms_sign')).'】'.$Content;
		$DesMobileSend = $DesMobile;
		$CommString = "OperID=" . $OperID . "&OperPass=" . $OperPass . "&SendTime=" . $SendTime . "&ValidTime=" . $ValidTime . "&AppendID=" . $AppendID . "&DesMobile=" . trim($DesMobileSend) . "&Content=" . urlencode(iconv("UTF-8", "GB2312//IGNORE",$Content)) . "&ContentType=" . $Content_Type;

		$TestUrl = "http://221.179.180.158:9007/QxtSms/QxtFirewall";
		// $TestUrl = "http://221.179.180.158:8000/HttpQuickProcess_utf-8/submitMessageAll";

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $TestUrl);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $CommString);
		$response = curl_exec($ch);
		
		$xml = simplexml_load_string($response);//转换post数据为simplexml对象
		foreach($xml->children() as $child){
			if($child->getName() == 'code'){
				$sms_status = $child;
			}
		}
		$sms_status = strval($sms_status);
		if($sms_status == '03' || $sms_status == '00'){
			$sms_status = '0';
		}

        self::add_sms_record($data, $sms_status);
        return true;
	}
		
	
	
	/**
	 *  post数据
	 *  @param string $url		post的url
	 *  @param int $limit		返回的数据的长度
	 *  @param string $post		post数据，字符串形式username='dalarge'&password='123456'
	 *  @param string $cookie	模拟 cookie，字符串形式username='dalarge'&password='123456'
	 *  @param string $ip		ip地址
	 *  @param int $timeout		连接超时时间
	 *  @param bool $block		是否为阻塞模式
	 *  @return string			返回字符串
	 */
	
	private function _post($url, $limit = 0, $post = '', $cookie = '', $ip = '', $timeout = 15, $block = true)
	{
		$return = '';
		$url = str_replace('&amp;', '&', $url);
		$matches = parse_url($url);
		$host = $matches['host'];
		$path = $matches['path'] ? $matches['path'] . ($matches['query'] ? '?' . $matches['query'] : '') : '/';
		$port = !empty($matches['port']) ? $matches['port'] : 80;
		$siteurl = Sms::_get_url();

		//服务器配置了代理出网
		$proxy = $_SERVER['https_proxy'] ?: ($_SERVER['http_proxy'] ?: '');
		if ($proxy) {
			$pm = parse_url($proxy);
			$proxyHost = $pm['host'] ?? '';
			$proxyPort = $pm['port'] ?? '';
		} else {
			$proxyHost = $proxyPort = '';
		}

		if ($proxyHost && $proxyPort) {
			$out = ($port ? "POST $url HTTP/1.1\r\n" : "GET $url HTTP/1.1\r\n");
			$out .= "Proxy-Connection: Keep-Alive\r\n";
		} else {
			$out = $post ? "POST $path HTTP/1.1\r\n" : "GET $path HTTP/1.1\r\n";
		}

		if ($post) {
			//$out = "POST $path HTTP/1.1\r\n";
			$out .= "Accept: */*\r\n";
			$out .= "Referer: ".$siteurl."\r\n";
			$out .= "Accept-Language: zh-cn\r\n";
			$out .= "Content-Type: application/x-www-form-urlencoded\r\n";
			$out .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
			$out .= "Host: $host\r\n" ;
			$out .= 'Content-Length: '.strlen($post)."\r\n" ;
			$out .= "Connection: Close\r\n" ;
			$out .= "Cache-Control: no-cache\r\n" ;
			$out .= "Cookie: $cookie\r\n\r\n" ;
			$out .= $post ;
		} else {
			//$out = "GET $path HTTP/1.1\r\n";
			$out .= "Accept: */*\r\n";
			$out .= "Referer: ".$siteurl."\r\n";
			$out .= "Accept-Language: zh-cn\r\n";
			$out .= "User-Agent: $_SERVER[HTTP_USER_AGENT]\r\n";
			$out .= "Host: $host\r\n";
			$out .= "Connection: Close\r\n";
			$out .= "Cookie: $cookie\r\n\r\n";
		}
		if ($proxyHost && $proxyPort) {
			$fp = @fsockopen($proxyHost, $proxyPort, $errno, $errstr, $timeout);
		} else {
			$fp = @fsockopen(($ip ? $ip : $host), $port, $errno, $errstr, $timeout);
		}

		if(!$fp) return '';

		stream_set_blocking($fp, $block);
		stream_set_timeout($fp, $timeout);
		@fwrite($fp, $out);
		$status = stream_get_meta_data($fp);
		
		if($status['timed_out']) return '';	
		while (!feof($fp)) {
			if(($header = @fgets($fp)) && ($header == "\r\n" ||  $header == "\n"))  break;				
		}
		
		$stop = false;
		while(!feof($fp) && !$stop) {
			$data = fread($fp, ($limit == 0 || $limit > 8192 ? 8192 : $limit));
			$return .= $data;
			if($limit) {
				$limit -= strlen($data);
				$stop = $limit <= 0;
			}
		}
		@fclose($fp);

		//部分虚拟主机返回数值有误，暂不确定原因，过滤返回数据格式
		$return_arr = explode("\n", $return);
		if(isset($return_arr[1])) {
			$return = trim($return_arr[1]);
		}
		unset($return_arr);
		
		return $return;
	}

	/**
	 * 获取当前页面完整URL地址
	 */
	private function _get_url() {
		$sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
		$php_self = $_SERVER['PHP_SELF'] ? Sms::_safe_replace($_SERVER['PHP_SELF']) : Sms::_safe_replace($_SERVER['SCRIPT_NAME']);
		$path_info = isset($_SERVER['PATH_INFO']) ? Sms::_safe_replace($_SERVER['PATH_INFO']) : '';
		$relate_url = isset($_SERVER['REQUEST_URI']) ? Sms::_safe_replace($_SERVER['REQUEST_URI']) : $php_self.(isset($_SERVER['QUERY_STRING']) ? '?'.Sms::_safe_replace($_SERVER['QUERY_STRING']) : $path_info);
		return $sys_protocal.(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '').$relate_url;
	}
	
	/**
	 * 安全过滤函数
	 *
	 * @param $string
	 * @return string
	 */
	private function _safe_replace($string) {
		$string = str_replace('%20','',$string);
		$string = str_replace('%27','',$string);
		$string = str_replace('%2527','',$string);
		$string = str_replace('*','',$string);
		$string = str_replace('"','&quot;',$string);
		$string = str_replace("'",'',$string);
		$string = str_replace('"','',$string);
		$string = str_replace(';','',$string);
		$string = str_replace('<','&lt;',$string);
		$string = str_replace('>','&gt;',$string);
		$string = str_replace("{",'',$string);
		$string = str_replace('}','',$string);
		$string = str_replace('\\','',$string);
		return $string;
	}


    //增加到本地数据库记录
    public function add_sms_record($data,$status){
        $row = array(
            'mer_id'     =>  isset($data['o2o_mer_id']) ? intval($data['o2o_mer_id']) : 0,
            'uid'        =>  isset($data['o2o_uid']) ? intval($data['o2o_uid']) : 0,
            'store_id'   =>  isset($data['o2o_store_id']) ? intval($data['o2o_store_id']) : 0,
            'time'       =>  time(),
            'phone'      =>  isset($data['mobile']) ? $data['mobile'] : '',
            'text'       =>  isset($data['content']) ? $data['content'] : '',
            'status'     =>  $status,
            'type'       =>  isset($data['o2o_type']) ? $data['o2o_type'] : '',
            'sendto'     =>  isset($data['o2o_sendto']) ? $data['o2o_sendto'] : '',
            'village_id' =>  isset($data['o2o_village_id']) ? $data['o2o_village_id'] : 0,
            'village_send_id' => isset($data['village_send_id']) ? intval($data['village_send_id']) : 0
        );
        $add = D('Sms_record')->add($row);
        if (! $add) {
            fdump_sql($row,'sms_error_log');
        }
        // 如果是社区群发处理社区群发
        if (0 == $status && intval($row['village_send_id']) > 0 && $data['o2o_type'] == 'house_group_send') {
            // 正常发送添加成功发送数量
            $template_num = isset($data['template_num']) && intval($data['template_num']) > 0 ? intval($data['template_num']) : 1;
            M('House_sms_group_send')->where(array('send_id' => $data['village_send_id']))->setInc('success_send_num', $template_num);
        }
        $village_id = $row['o2o_village_id'];
        if (intval($village_id) > 0) {
            // 发送不论是否成功更正下社区拥有短信数量
            D('House_village')->correctVillageSmsNumber($data['village_send_id']);
        }
    }
}
?>