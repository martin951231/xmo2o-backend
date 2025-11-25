<?php
final class Ali_sms
{
	public function request($accessKeyId, $accessKeySecret, $domain, $params, $security=false, $method='POST') {
		$apiParams = array_merge(array (
				"SignatureMethod" => "HMAC-SHA1",
				"SignatureNonce" => uniqid(mt_rand(0,0xffff), true),
				"SignatureVersion" => "1.0",
				"AccessKeyId" => $accessKeyId,
				"Timestamp" => gmdate("Y-m-d\TH:i:s\Z"),
				"Format" => "JSON",
		), $params);
		ksort($apiParams);

		$sortedQueryStringTmp = "";
		foreach ($apiParams as $key => $value) {
			$sortedQueryStringTmp .= "&" . Ali_sms::encode($key) . "=" . Ali_sms::encode($value);
		}

		$stringToSign = "${method}&%2F&" . Ali_sms::encode(substr($sortedQueryStringTmp, 1));

		$sign = base64_encode(hash_hmac("sha1", $stringToSign, $accessKeySecret . "&",true));

		$signature = Ali_sms::encode($sign);

		$url = ($security ? 'https' : 'http')."://{$domain}/";

		try {
			$content = Ali_sms::fetchContent($url, $method, "Signature={$signature}{$sortedQueryStringTmp}");
			return json_decode($content);
		} catch( \Exception $e) {
			return false;
		}
	}

	private function encode($str)
	{
		$res = urlencode($str);
		$res = preg_replace("/\+/", "%20", $res);
		$res = preg_replace("/\*/", "%2A", $res);
		$res = preg_replace("/%7E/", "~", $res);
		return $res;
	}

	private function fetchContent($url, $method, $body) {
		$ch = curl_init();

		if($method == 'POST') {
			curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
			curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
		} else {
			$url .= '?'.$body;
		}

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_TIMEOUT, 5);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				"x-sdk-client" => "php/2.0.0"
		));
		if (defined('CURLOPT_IPRESOLVE') && defined('CURL_IPRESOLVE_V4')) {
			curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
		}

		if(substr($url, 0,5) == 'https') {
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		}

		$rtn = curl_exec($ch);

		if($rtn === false) {
			// 大多由设置等原因引起，一般无法保障后续逻辑正常执行，
			// 所以这里触发的是E_USER_ERROR，会终止脚本执行，无法被try...catch捕获，需要用户排查环境、网络等故障
			trigger_error("[CURL_" . curl_errno($ch) . "]: " . curl_error($ch), E_USER_ERROR);
		}
		curl_close($ch);

		return $rtn;
	}

	public function fetch_template($content){
		$template_list = M('Ali_sms_template')->where(array('status'=>1, 'templateCode'=>['neq', '']))->select();
		foreach ($template_list as $item) {
			preg_match_all('/\${\w+}/',$item['content'],$match);
			$param =  preg_replace("/(\\$|{|})/",'',$match[0]);
			$sms_str = preg_replace('/\${\w+}/','||',$item['content']);
			$sms_arr = explode('||',$sms_str);
			
			//如果变量是最后一个，则删除
			if(empty(end($sms_arr))){
				unset($sms_arr[count($sms_arr) - 1]);
			}
			
			$save_sms_arr = $sms_arr;

			foreach ($sms_arr as &$it) {
				$it = "/".trim($it)."/";
				$it = str_replace('(', '\\(', $it);
				$it = str_replace(')', '\\)', $it);
			}

			$replace_str = preg_replace($sms_arr, '|', $content,-1,$count);
			$replace_str = trim($replace_str,'|');

			$replace_arr = explode('|',$replace_str);

			foreach ($replace_arr as $key=>$str) {
				$item['param'][$param[$key]] = trim($str);
			}
			
			//检测模板中的文字和短信文字是否包含
			$is_template = true;
			foreach($save_sms_arr as $value){
				if(strpos($content, $value) === false){
					$is_template = false;
					break;
				}
			}

			if($count==count($sms_arr) && $is_template){
				return $item;
			}
		}
		return false;

	}


	function sendSms($data) {
        $sms_sign=C('config.sms_sign');
        if (!empty($data['o2o_village_id'])){
            $arr = D('House_village')->checkVillageSms($data['o2o_village_id'], 1, true);
            if (! $arr) {
                Ali_sms::add_sms_record($data,-2);
                return false;
            }
            if (!empty($arr['sms_sign'])) {
                $sms_sign = $arr['sms_sign'];
            }
        }
		$params = array ();
		// fixme 必填：是否启用https
		$security = false;
		$accessKeyId = C('config.ali_sms_key');
		$accessKeySecret = C('config.ali_sms_key_secret');
		$params["PhoneNumbers"] = $data['mobile'];
		$params["SignName"] =trim($sms_sign);
		$template = Ali_sms::fetch_template($data['content']);

		if(!$template){

			fdump_sql('没有匹配的模板，内容：'.$data['content'].'|手机：'.$data['mobile'],'ali_sms_error_log');
			return false;
		}
		$params["TemplateCode"] = $template['templateCode'];
		foreach ($template['param'] as $key=>$item) {
			$params['TemplateParam'][$key] = $item;
		}

		// *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
		if(!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
			$params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
		}

		// 此处可能会抛出异常，注意catch
		$content = Ali_sms::request(
			$accessKeyId,
			$accessKeySecret,
			"dysmsapi.aliyuncs.com",
			array_merge($params, array(
					"RegionId" => "cn-hangzhou",
					"Action" => "SendSms",
					"Version" => "2017-05-25",
			)),
			$security
		);
		$sms_status = -3;
	
		if($content->Code=='OK'){
			$sms_status = 0;
		}
        Ali_sms::add_sms_record($data, $sms_status);
		if($content->Code != 'OK'){
			fdump_sql($content,'ali_sms_error_log');
		}
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
            fdump_sql(['Ali_sms', $row],'sms_error_log');
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
        return true;
    }
}
?>