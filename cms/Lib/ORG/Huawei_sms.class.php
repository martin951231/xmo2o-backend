<?php
final class Huawei_sms
{
	public function request($accessKeyId, $accessKeySecret, $domain, $params, $header, $security=false, $method='POST') {
		
		$url = ($security ? 'https' : 'http')."://{$domain}/sms/batchSendSms/v1";

		try {
			$content = Huawei_sms::fetchContent($url, $method, $params, $header);
			return json_decode($content);
		} catch( \Exception $e) {
			return false;
		}
		
	}

	private function fetchContent($url, $method, $body, $headers = array()) {

		$ch = curl_init();
		//dump($body);
		if($method == 'POST') {
			curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($body));
		} else {
			$url .= '?'.$body;
		}

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		if($headers){
			//dump($headers);
			$post_header = [];
			foreach($headers as $key=>$value){
				$post_header[] = $key.': '.$value;
			}
			//dump($post_header);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $post_header);
		}


		if(substr($url, 0,5) == 'https') {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		}

		$rtn = curl_exec($ch);
		
		//dump(curl_getinfo($ch));
		
		//dump($rtn);die;

		if($rtn === false) {
			// 大多由设置等原因引起，一般无法保障后续逻辑正常执行，
			// 所以这里触发的是E_USER_ERROR，会终止脚本执行，无法被try...catch捕获，需要用户排查环境、网络等故障
			trigger_error("[CURL_" . curl_errno($ch) . "]: " . curl_error($ch), E_USER_ERROR);
		}
		curl_close($ch);

		return $rtn;
	}

	public function fetch_template($content){
		$template_list = M('Ali_sms_template')->field('`id`,`huawei_content`,`huawei_templateCode`')->where(['status'=>1,'huawei_templateCode'=>['neq',''],'huawei_content'=>['neq','']])->select();
		//dump($template_list);
		foreach ($template_list as $item) {
			preg_match_all('/\${\w+}/',$item['huawei_content'],$match);
			$param =  preg_replace("/(\\$|{|})/",'',$match[0]);
			$sms_str = preg_replace('/\${\w+}/','||',$item['huawei_content']);
			$sms_arr = explode('||',$sms_str);
			$sms_arr = array_filter($sms_arr);
			
			$replace_str = str_replace($sms_arr, '|', $content,$count);
			$replace_str = trim($replace_str,'|');
			$replace_arr = explode('|',$replace_str);

			foreach ($replace_arr as $key=>$str) {
				$item['param'][] = $str;
			}

			if($count==count($sms_arr)){
				$match = true;
				foreach($sms_arr as $v){
					if(strpos($content,$v) === FALSE){
						$match = false;
						break;
					}
				}
				if($match){
					return $item;
				}
			}
		}
		return false;

	}
	
	function buildWsseHeader($appKey,$appSecret){
		$now = date('Y-m-d\TH:i:s\Z'); //Created
		$nonce = uniqid(); //Nonce
		$base64 = base64_encode(hash('sha256', ($nonce . $now . $appSecret))); //PasswordDigest
		return sprintf("UsernameToken Username=\"%s\",PasswordDigest=\"%s\",Nonce=\"%s\",Created=\"%s\"",
			$appKey, $base64, $nonce, $now);
	}

	function sendSms($data) {
        $sms_sign=C('config.sms_sign');
        if (!empty($data['o2o_village_id'])){
            $arr = D('House_village')->checkVillageSms($data['o2o_village_id'], 1, true);
            if (! $arr) {
                Huawei_sms::add_sms_record($data,-2);
                return false;
            }
            if (!empty($arr['sms_sign'])) {
                $sms_sign = $arr['sms_sign'];
            }
        }
		$params = array ();
		// fixme 必填：是否启用https
		$security = true;
		$accessKeyId = C('config.huaweiyun_sms_key');
		$accessKeySecret = C('config.huaweiyun_sms_key_secret');
		$params["signature"] = $sms_sign;
		if(strpos($data['content'],'验证码') !== false){
			$params["from"] = C('config.huaweiyun_sms_from');
		}else{
			$params["from"] = C('config.huaweiyun_sms_notice_from');
		}
		$params["to"] = $data['mobile'];
		
		$template = Huawei_sms::fetch_template($data['content']);

		if(!$template){
			fdump_sql('没有匹配的模板，内容：'.$data['content'].'|手机：'.$data['mobile'],'ali_sms_error_log');
			return false;
		}
		$params["templateId"] = $template['huawei_templateCode'];
		foreach ($template['param'] as $key=>$item) {
			$params['templateParas'][$key] = $item;
		}

		// *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
		if(!empty($params["templateParas"]) && is_array($params["templateParas"])) {
			$params["templateParas"] = json_encode($params["templateParas"], JSON_UNESCAPED_UNICODE);
		}
		
		$header = array(
			'Content-Type' => 'application/x-www-form-urlencoded',
			'Authorization' => 'WSSE realm="SDP",profile="UsernameToken",type="Appkey"',
			'X-WSSE' => self::buildWsseHeader($accessKeyId, $accessKeySecret)
		);

		// 此处可能会抛出异常，注意catch
		$content = Huawei_sms::request(
			$accessKeyId,
			$accessKeySecret,
			"smsapi.cn-north-4.myhuaweicloud.com:443",
			$params,
			$header,
			$security
		);
		$sms_status = 0;
	
		if($content->code=='000000'){
			$sms_status = 1;
		}else if($content->code=='000000'){
			$sms_status = 1;
		}
        Huawei_sms::add_sms_record($data, $sms_status);

		fdump_sql($content,'ali_sms_error_log');
		return $content;
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
            fdump_sql(['Huawei_sms', $row],'sms_error_log');
        }
        // 如果是社区群发处理社区群发
        if (0 == $status && intval($row['village_send_id']) > 0 && $data['o2o_type'] == 'house_group_send') {
            // 正常发送添加成功发送数量
            M('House_sms_group_send')->where(array('send_id' => $data['village_send_id']))->setInc('success_send_num', 1);
        }
        $village_id = $row['o2o_village_id'];
        if (intval($village_id) > 0) {
            // 发送不论是否成功更正下社区拥有短信数量
            D('House_village')->correctVillageSmsNumber($data['village_send_id']);
        }
    }
}
?>