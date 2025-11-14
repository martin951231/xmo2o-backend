<?php
/*
 * 渠道二维码
 *
 */
class Recognition_wxappAction extends BaseAction{
	public function create_page_qrcode(){
		$origin_page = $qr_path = htmlspecialchars_decode($_GET['page']);
		$access_token_array = D('Access_token_wxapp_expires')->get_access_token();
		if ($access_token_array['errcode']) {
			exit('获取access_token发生错误：错误代码' . $access_token_array['errcode'] .',微信返回错误信息：' . $access_token_array['errmsg']);
		}
        if(strlen($qr_path) > 128){
            //小程序码路径不能大于128，如果大于转成成短链处理
            $link_id = M('Short_link')->data(['link_url'=>ltrim(D('House_village')->base_url,'/').$qr_path])->add();
            if(!$link_id){
                $this->error('小程序码生成失败');
            }
            //小程序路径
            $qr_path = D('House_village')->home_url . '?redirect=webview&webview_url='.urlencode('/short_'.$link_id);
        }
		$qrcode_url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token='.$access_token_array['access_token'];
        $post_data = array(
			'path'=>$qr_path,
			'width'=>425
		);
		$img_content = $this->curlPost($qrcode_url,json_encode($post_data));
		header('Content-type: image/jpeg');

		if (is_null(json_decode($img_content))) {
			echo $img_content;
			die;
		}

		//如果生成二维码失败，则尝试生成无数量限制的
		$webview_url = get_base_url($origin_page);
		$parse_url = parse_url($webview_url);
		//获取$webview_url携带的参数，如果是webview跳转，重新获取地址
		$urlQuery = $this->convertUrlQuery($parse_url['query']);
		if ($urlQuery['redirect'] == 'webview' && $urlQuery['webview_url']) {
			$webview_url = urldecode($urlQuery['webview_url']);
		}
		$link_id = M('Short_link')->data(['link_url' => $webview_url])->add();
		if (!$link_id) {
			$this->error('小程序码生成失败'); 
		}
		$webview_url = '/short_' . $link_id;
		$_GET['page'] = 'pages/webview/webview';
		$_GET['scene'] = urldecode($webview_url);
		$_GET['width'] = 425;
		$this->create_unlimit_qrcode();
	}
	public function create_unlimit_qrcode(){
		$page = htmlspecialchars_decode($_GET['page']);
		$scene = htmlspecialchars_decode($_GET['scene']);
		if($_GET['mer_id']){
			$access_token_array = D('Access_token_wxapp_merchant_expires')->get_access_token($_GET['mer_id']);
		}else{
			$access_token_array = D('Access_token_wxapp_expires')->get_access_token();
		}
		if ($access_token_array['errcode']) {
			exit('获取access_token发生错误：错误代码' . $access_token_array['errcode'] .',微信返回错误信息：' . $access_token_array['errmsg']);
		}
		
		$qrcode_url = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token='.$access_token_array['access_token'];
		$post_data = array(
			'scene'	=>	$scene,
			'page'	=>	$page,
			'width'	=>	$_GET['width'] ?? 280
		);
		$img_content = $this->curlPost($qrcode_url,json_encode($post_data));
		header('Content-type: image/jpeg');
		echo $img_content;
	}
	
	public function create_url_qrcode(){
		//去除网址里无用参数
		$webview_url = htmlspecialchars_decode($_GET['webview_url']);
		$webview_url_arr = parse_url($webview_url);
		$urlQuery = $this->convertUrlQuery($webview_url_arr['query']);
		unset($urlQuery['code']);
		unset($urlQuery['state']);
		unset($urlQuery['isappinstalled']);
		unset($urlQuery['openid']);
		if($urlQuery['from'] == 'singlemessage'){
			unset($urlQuery['from']);
		}
		$webview_url = $webview_url_arr['path'].'?'.http_build_query($urlQuery);
		
		if(strlen($webview_url) >= 45){
			//换短链
			$link_id = M('Short_link')->data(['link_url'=>$webview_url])->add();
			if(!$link_id){
				$this->error('小程序码生成失败');
			}
			$webview_url = '/short_'.$link_id;
		}
		
		if(strpos($webview_url, 'c=Village_group&a=index') !== false || strpos($webview_url, '/short_') !== false){
			$qr_path = '/pages/webview/tab_webview?url='.urlencode($webview_url);
		}else{
			$qr_path = D('House_village')->home_url . '?redirect=webview&webview_url='.urlencode($webview_url);
		}
		
		if($_GET['spread_uid']){
			$qr_path.= '&spread_uid=' . $_GET['spread_uid'];
		}
		$shortTitle = '&webview_title=';
		$title = '&webview_title='.urlencode($_GET['webview_title']);
		if(strlen($qr_path) + strlen($title) < 128){
			$qr_path.= $title;
		}else if(strlen($qr_path) + strlen($shortTitle) < 128){
			$qr_path.= $shortTitle;
		}
		$access_token_array = D('Access_token_wxapp_expires')->get_access_token();
		if ($access_token_array['errcode']) {
			exit('获取access_token发生错误：错误代码' . $access_token_array['errcode'] .',微信返回错误信息：' . $access_token_array['errmsg']);
		}
		
		$qrcode_url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token='.$access_token_array['access_token'];
		$post_data = array(
			'path'=>$qr_path,
			'width'=>425
		);
		$img_content = $this->curlPost($qrcode_url,json_encode($post_data));
		
		if(is_null(json_decode($img_content))){
			header('Content-type: image/jpeg');
			echo $img_content;
			die;
		}
		
		//若返回报错，则尝试生成无数量限制的
		if(strpos($webview_url, '/short_') !== 0){
			//换短链
			$link_id = M('Short_link')->data(['link_url'=>$webview_url])->add();
			if(!$link_id){
				$this->error('小程序码生成失败');
			}
			$webview_url = '/short_'.$link_id;
		}
		
		$page = 'pages/webview/webview';
		$scene = urldecode($webview_url);
		$url = 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token='.$access_token_array['access_token'];
		$postData = array(
			'scene'=> $scene,
			'page'=> $page,
			'width'=> 425
		);
		$img_content = $this->curlPost($url, json_encode($postData));
		
		header('Content-type: image/jpeg');
		echo $img_content;
	}
	
	public function see_qrcode($type,$id){
		//判断ID是否正确，如果正确且以前生成过二维码则得到ID
		$data['third_type'] = $type;
		$data['third_id'] = $id;
		
		
		$now_qrcode = M('Recognition_wxapp')->where($data)->find();
		
		if(!empty($now_qrcode)){
			if($_GET['img']){
				echo '<html><head><style>*{margin:0;padding:0;}</style></head><body><img src="'.$now_qrcode['ticket'].'"/></body></html>';
			}else{
				redirect($now_qrcode['ticket']);
			}
			die;
		}

        if($type == 'merchantstore'){
			$qr_path = 'pages/store/homePage?store_id='.$id.'&scanQrcode=1';
            // $qr_path = D('House_village')->home_url . '?redirect=webview&webview_url='.urlencode('/packapp/my/store/index.html?store_id='.$id);
        }elseif($type == 'doorshare'){
            // 访客分享开门
            $qr_path = D('House_village')->home_url . '?redirect=house_door&door_share_id='.$id;
        }elseif($type == 'cashier'){
            // 收款码
            $db_house_village = D('House_village');
            $app_version = intval($_POST['app_version']);
            $deviceId = trim($_POST['Device-Id']);
            $param = [
                'pageName' => 'payMentlist',
                'isAll' => false,
                'urlParam' => '',
            ];
            $payMentlistUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
            $qr_path = $payMentlistUrl.'?village_id='.$id;
        }elseif($type == 'community'){
            // 物业管理
            $qr_path = "/pages/login/login";
        }else{
			exit('您查看的内容非法！无法查看二维码！');
		}
		if($type == 'cashier'){
            $village_info = M('House_village')->where(['village_id'=>$id])->find();
            $access_obj = D('Weixin_app_bind')->where(['bind_type'=>1,'other_id'=>$village_info['property_id'],'status'=>1])->find();
            if(empty($access_obj)){
                $access_token_array = D('Access_token_wxapp_expires')->get_access_token();
            }else{
                $access_token_array = D('Weixin_app_bind')->get_access_token($village_info['property_id']);
            }
        } elseif($type == 'community'){
            $access_token_array = D('Access_token_house_manage_wxapp_expires')->get_access_token();
        }else{
            $access_token_array = D('Access_token_wxapp_expires')->get_access_token();
        }
		if ($access_token_array['errcode']) {
			exit('获取access_token发生错误：错误代码' . $access_token_array['errcode'] .',微信返回错误信息：' . $access_token_array['errmsg']);
		}
		
		$qrcode_url = 'https://api.weixin.qq.com/wxa/getwxacode?access_token='.$access_token_array['access_token'];
		$post_data = array(
			'path'=>$qr_path,
			'width'=>425
		);
		$img_content = $this->curlPost($qrcode_url,json_encode($post_data));
		header('Content-type: image/jpeg');
		echo $img_content;
	}
	static public function curlPost($url,$data,$timeout=15){
		$ch = curl_init();
		$headers[] = "Accept-Charset: utf-8";
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch ,CURLOPT_TIMEOUT ,$timeout);
		$result = curl_exec($ch);
		
		//关闭curl
		curl_close($ch);
		return $result;	
	}
}