<?php
// 前后端分离 交互
class PackappAction extends BaseAction{
	
	//获取到用户的微信openid
	public function bind(){
		if($_GET['referer']){
		    if ($_GET['is_return'] && 1==$_GET['is_return']) {
                $referer = trim($_GET['referer']);
                if (strstr($referer, '?') !== false) {
                    $url = $referer.'&openid='.$_SESSION['openid'];
                } else {
                    $url = $referer.'?openid='.$_SESSION['openid'];
                }
                redirect($url);
            } else {
                $referer = array_shift(explode('?',$_GET['referer']));
                redirect($referer.'?openid='.$_SESSION['openid']);
            }
		}else{
			redirect(U('Home/index'));
		}
	}
	
	//拼接URL网址
	public function build_url($url,$paramArr){
		if(strpos($url,'&') > 0){
			$returnUrl = $url . '&' . http_build_query($paramArr);
		}else{
			$returnUrl = $url . '?' . http_build_query($paramArr);
		}
		return $returnUrl;
	}
	
	//facebook登录
	public function facebook(){
		$_SESSION['from_referer'] = $_SERVER['original_get']['referer'];
		$_SESSION['third_login_state']   = md5(uniqid());
		$_SESSION['from_device_id']   = $_GET['device_id'];
		
		//判断已经登录
		if($this->user_session['uid']){
			redirect($this->login_ok_back_packapp($this->user_session['uid']));
		}
		
		$get_param = [
			'client_id' => $this->config['facebook_client_id'],
			'redirect_uri' => $this->config['site_url'].'/wap.php?c=Packapp&a=facebook_back',
			'state'	=> $_SESSION['third_login_state'],
			'response_type'	=> 'code',
		];
		$url = 'https://www.facebook.com/v3.3/dialog/oauth?' . http_build_query($get_param);
		redirect($url);
	}
	//微博登录 - 回跳
	public function facebook_back(){
		$referer = !empty($_SESSION['from_referer']) ? $_SESSION['from_referer'] : $this->config['site_url'].'/wap.php';

		if (isset($_GET['code'])){
			unset($_SESSION['third_login_state']);
			import('ORG.Net.Http');
			$http = new Http();
			
			$post_param = [
				'client_id' => $this->config['facebook_client_id'],
				'client_secret' => $this->config['facebook_client_secret'],
				'code' => $_GET['code'],
				'redirect_uri' => $this->config['site_url'].'/wap.php?c=Packapp&a=facebook_back',
			];

			$return = $http->curlGET('https://graph.facebook.com/v3.3/oauth/access_token?'. http_build_query($post_param));
			$jsonrt = json_decode($return,true);
			if($jsonrt['error_code']){
				redirect($this->login_error_back_packapp('授权发生错误：'.$jsonrt['error'].'('.$jsonrt['error_description'].')'));
			}
			
			//获取用户信息
			$get_param = [
				'access_token' => $jsonrt['access_token']
			];
			
			$return = $http->curlGet('https://api.weibo.com/2/users/show.json?' . http_build_query($get_param));
			$jsonrt = json_decode($return,true);
			
			if($jsonrt['error_code']){
				redirect($this->login_error_back_packapp('授权发生错误：'.$jsonrt['error'].'('.$jsonrt['error_description'].')'));
			}
			

			$now_user = D('User')->get_user($jsonrt['id'], 'facebook_token');
			if(!empty($now_user)){
				redirect($this->login_ok_back_packapp($now_user['uid']));
			}
			
			$third_login = [
				'from' => 'facebook',
				'facebook_token' => $jsonrt['id'],
				'nickname' => $jsonrt['name'],
				'avatar' => $jsonrt['profile_pic'] ? strval($jsonrt['profile_pic']) : strval($jsonrt['picture']),
				'third_auth_time' => time(),
			];
			if($third_id = M('User_tmp_third')->data($third_login)->add()){
				redirect($this->login_bind_back_packapp($third_id, 'weibo'));
			}else{
				redirect($this->login_error_back_packapp('访问异常！请重新登录。'));
			}
		}else{
			redirect($this->login_error_back_packapp('访问异常！请重新登录。'));
		}
	}
	
	//支付宝登录
	public function alipay(){
		$_SESSION['from_referer'] = $_SERVER['original_get']['referer'];
		$_SESSION['third_login_state']   = md5(uniqid());
		$_SESSION['from_device_id']   = $_GET['device_id'];
		
		//临时登录型的分享
		if(isset($_GET['thirdLogin'])){
			$_SESSION['thirdLogin']   = $_GET['thirdLogin'];
		}
		
		//判断已经登录
		if($this->user_session['uid']){
			redirect($this->login_ok_back_packapp($this->user_session['uid']));
		}
		
		$get_param = [
			'app_id'		=> $this->config['alipay_app_id'],
			'redirect_uri' => $this->config['site_url'].'/wap.php?c=Packapp&a=alipay_back',
			'scope' => 'auth_user'
		];
		
		$url = 'https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?' . http_build_query($get_param);
		redirect($url);
	}

	//支付宝登录 - 回跳
	public function alipay_back(){
		$referer = !empty($_SESSION['from_referer']) ? $_SESSION['from_referer'] : $this->config['site_url'].'/wap.php';
		
		$param = array();
		$param['app_id'] = $this->config['alipay_app_id'];
		$param['method'] = 'alipay.system.oauth.token';
		$param['charset'] = 'utf-8';
		$param['sign_type'] = $this->config['alipay_app_encrypt_type'] ? $this->config['alipay_app_encrypt_type'] : 'RSA';
		$param['timestamp'] = date('Y-m-d H:i:s');
		$param['version'] = '1.0';
		$param['grant_type'] = 'authorization_code';
		$param['code'] = $_GET['auth_code'];
		
		ksort($param);
		$stringToBeSigned = "";
		$i = 0;
		foreach ($param as $k => $v) {
			if (!empty($v) && "@" != substr($v, 0, 1)) {
				if ($i == 0) {
					$stringToBeSigned .= "$k" . "=" . "$v";
				} else {
					$stringToBeSigned .= "&" . "$k" . "=" . "$v";
				}
				$i++;
			}
		}

		$priKey = $this->config['alipay_app_prikey'];
		$res = "-----BEGIN RSA PRIVATE KEY-----\n".wordwrap($priKey, 64, "\n", true)."\n-----END RSA PRIVATE KEY-----";

		if(!function_exists('openssl_sign')){
			redirect($this->login_error_back_packapp('系统不支持 openssl_sign'));
		}
		
		if($param['sign_type'] == 'RSA2'){
			openssl_sign($stringToBeSigned, $sign, $res, OPENSSL_ALGO_SHA256);
		}else{
			openssl_sign($stringToBeSigned, $sign, $res);
		}
			
		if(empty($sign)){
			redirect($this->login_error_back_packapp('支付宝密钥错误'));
		}

		$sign = base64_encode($sign);

		$param['sign'] = $sign;
		$requestUrl = "https://openapi.alipay.com/gateway.do?";
		foreach ($param as $sysParamKey => $sysParamValue) {
			$requestUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
		}
		$requestUrl = substr($requestUrl, 0, -1);
		// echo $requestUrl;die;
		import('ORG.Net.Http');
		$http = new Http();

		$return = Http::curlGet($requestUrl);
		$returnArr = json_decode($return,true);
		
		if($returnArr['alipay_system_oauth_token_response']){
			//如果存在即 自动登录
			$now_user = D('User')->get_user($returnArr['alipay_system_oauth_token_response']['user_id'], 'alipay_uid');
			if(!empty($now_user)){
				redirect($this->login_ok_back_packapp($now_user['uid']));
			}

			$param = array();
			$param['app_id'] = $this->config['alipay_app_id'];
			$param['method'] = 'alipay.user.info.share';
			$param['charset'] = 'utf-8';
			$param['sign_type'] = $this->config['alipay_app_encrypt_type'] ? $this->config['alipay_app_encrypt_type'] : 'RSA';
			$param['timestamp'] = date('Y-m-d H:i:s');
			$param['version'] = '1.0';
			$param['auth_token'] = $returnArr['alipay_system_oauth_token_response']['access_token'];
			
			ksort($param);
			$stringToBeSigned = "";
			$i = 0;
			foreach ($param as $k => $v) {
				if (!empty($v) && "@" != substr($v, 0, 1)) {
					if ($i == 0) {
						$stringToBeSigned .= "$k" . "=" . "$v";
					} else {
						$stringToBeSigned .= "&" . "$k" . "=" . "$v";
					}
					$i++;
				}
			}

			$priKey = $this->config['alipay_app_prikey'];
			$res = "-----BEGIN RSA PRIVATE KEY-----\n".wordwrap($priKey, 64, "\n", true)."\n-----END RSA PRIVATE KEY-----";

			if(!function_exists('openssl_sign')){
				redirect($this->login_error_back_packapp('系统不支持 openssl_sign'));
			}

			if($param['sign_type'] == 'RSA2'){
				openssl_sign($stringToBeSigned, $sign, $res, OPENSSL_ALGO_SHA256);
			}else{
				openssl_sign($stringToBeSigned, $sign, $res);
			}
			
			if(empty($sign)){
				redirect($this->login_error_back_packapp('支付宝密钥错误'));
			}

			$sign = base64_encode($sign);

			$param['sign'] = $sign;
			$requestUrl = "https://openapi.alipay.com/gateway.do?";
			foreach ($param as $sysParamKey => $sysParamValue) {
				$requestUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
			}
			$requestUrl = substr($requestUrl, 0, -1);
			// echo $requestUrl;die;
			import('ORG.Net.Http');
			$http = new Http();

			$return = Http::curlGet($requestUrl);
			$returnArr = json_decode($return,true);
			// dump($param);
			// dump($returnArr);
			
			if($returnArr['error_response']){
				redirect($this->login_error_back_packapp('支付宝授权提示：'.$returnArr['error_response']['sub_msg']));
			}

			/*注册用户*/
			$jsonrt = $returnArr['alipay_user_info_share_response'];
			// dump($jsonrt);die;
            $alipay_user_open_id= isset($jsonrt['user_id']) ? $jsonrt['user_id'] : $jsonrt['open_id'];
            $alipay_user_open_id=$alipay_user_open_id ? $alipay_user_open_id:'';
			$third_login = array(
				'from' 		=> 'alipay',
				'alipay_uid'=> $alipay_user_open_id,
				'nickname' 	=> $jsonrt['nick_name'] ? $jsonrt['nick_name'] : '支付宝用户',
				'sex' 		=> $jsonrt['gender'] == 'm' ? 1 : 2,
				'province' 	=> rtrim($jsonrt['province'],'省'),
				'city' 		=> rtrim($jsonrt['city'],'市'),
				'avatar' 	=> $jsonrt['avatar'] ? D('User')->down_avatar($jsonrt['avatar'],$alipay_user_open_id) : '',
				'third_auth_time' => time(),
			);
			
			if($third_id = M('User_tmp_third')->data($third_login)->add()){
				redirect($this->login_bind_back_packapp($third_id, 'alipay'));
			}else{
				redirect($this->login_error_back_packapp('访问异常！请重新登录。'));
			}
		}else if($returnArr['error_response']){
			redirect($this->login_error_back_packapp('支付宝授权提示：'.$returnArr['error_response']['sub_msg']));
		}
	}
	
	/**
     * 小区个人中心用户登录物业公众号
     * @author lijie
     * @date_time 2020/11/2
     */
    public function property_weixin()
    {
        $_SESSION['property_id'] = $_GET['property_id'];//物业ID
        $_SESSION['from_referer'] = iconv('UTF-8','gb2312',$_GET['refer']);//来源地址
        $_SESSION['third_login_state']   = md5(uniqid());
        $authorizer_appid = M('Weixin_bind')->where(['mer_id'=>$_SESSION['property_id'],'type'=>2])->getField('authorizer_appid');
        if(empty($authorizer_appid)){
            redirect($_SESSION['from_referer']);
        }else{
            $_SESSION['authorizer_appid'] = $authorizer_appid;
        }
        //只有h5过来的要去授权登录
        $get_param = [
            'appid'		=> $_SESSION['authorizer_appid'],
            'redirect_uri' => $this->config['site_url'].'/wap.php?c=Packapp&a=property_weixin_back',
            'response_type=' => 'code',
            'scope' => 'snsapi_userinfo',
            'state' => $_SESSION['third_login_state'],
            'component_appid' => $this->config['wx_house_appid']
        ];
        $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?' . http_build_query($get_param) . '#wechat_redirect';
        redirect($url);
    }

    /**
     * 物业公众号授权code后跳转地址
     * @author lijie
     * @date_time 2020/11/2
     */
    public function property_weixin_back()
    {
        $referer = !empty($_SESSION['from_referer']) ? $_SESSION['from_referer'] : $this->config['site_url'].'/wap.php';
        if (isset($_GET['code'])){
            unset($_SESSION['third_login_state']);
            $get_component_access_token = D('Weixin_bind')->get_component_access_token(2);
            import('ORG.Net.Http');
            $http = new Http();
            $return = $http->curlGet('https://api.weixin.qq.com/sns/oauth2/component/access_token?appid='.$_SESSION['authorizer_appid'].'&code='.$_GET['code'].'&grant_type=authorization_code&component_appid='.$this->config['wx_house_appid'].'&component_access_token='.$get_component_access_token);
            $jsonrt = json_decode($return,true); //获取用户基本信息
            $is_follow = 0;
            $return = $http->curlGet('https://api.weixin.qq.com/sns/userinfo?access_token='.$jsonrt['access_token'].'&openid='.$jsonrt['openid'].'&lang=zh_CN');
            $jsonrt = json_decode($return, true);  //获取用户是否关注物业公众号
            $now_user = M('User')->where(['uid'=>$this->user_session['uid']])->find();
            $data['uid'] = $this->user_session['uid'];
            $data['property_id'] = $_SESSION['property_id'];
            $data['openid'] = $jsonrt['openid'];
            $data['union_id'] = isset($jsonrt['unionid'])?$jsonrt['unionid']:'';
            $data['nickName'] = isset($jsonrt['nickname'])?$jsonrt['nickname']:'';
            $data['sex'] = isset($jsonrt['sex'])?$jsonrt['sex']:'';
            $data['city'] = isset($jsonrt['city'])?$jsonrt['city']:'';
            $data['province'] = isset($jsonrt['province'])?$jsonrt['province']:'';
            $data['avatar'] = isset($jsonrt['headimgurl'])?$jsonrt['headimgurl']:'';
            $data['type'] = 2;
            $data['plat_openid'] = $now_user['openid'];
            if(M('Weixin_bind_user')->where(['openid'=>$jsonrt['openid'],'property_id'=>$_SESSION['property_id'],'type'=>2])->find()){
                M('Weixin_bind_user')->where(['openid'=>$jsonrt['openid'],'property_id'=>$_SESSION['property_id'],'type'=>2])->save($data);
            }else{
                if(M('Weixin_bind_user')->where(['uid'=>$this->user_session['uid'],'property_id'=>$_SESSION['property_id'],'type'=>2])->find()){
                    M('Weixin_bind_user')->where(['uid'=>$this->user_session['uid'],'property_id'=>$_SESSION['property_id'],'type'=>2])->save($data);
                } else{
                    $data['is_follow'] = 0;
                    M('Weixin_bind_user')->data($data)->add();
                }
            }
            redirect($referer);
        }
    }
	
	//微信登录
	public function weixin(){
		$_SESSION['from_referer'] = $_SERVER['original_get']['referer'];
		$_SESSION['third_login_state']   = md5(uniqid());
		$_SESSION['from_device_id']   = $_GET['device_id'];
		$_SESSION['login_type']   = $_GET['login_type'];
        $_SESSION['from_type']   = $_GET['from_type'];
        // $_SESSION['from_type']   = 'park_new';
		//临时登录型的分享
		if(isset($_GET['thirdLogin'])){
			$_SESSION['thirdLogin']   = $_GET['thirdLogin'];
		}
		
		//判断已经登录
		if($this->user_session['uid'] && $this->user_session['status'] != '1'){
			$this->user_session = [];
			setcookie('user_device_id', '', -1, '/');
			setcookie('user_ticket', '', -1, '/');
			setcookie('login_status', 'logout', 0, '/');
		}
		if($this->user_session['uid']){
			redirect($this->login_ok_back_packapp($this->user_session['uid']));
		}
		
		//部分系统不支持微信授权
		if($this->config['no_weixin']){
			$third_login = array(
				'from' 		=> 'weixin',
				'nickname' 	=> '微信用户',
				'openid' 	=> strval($_SESSION['openid']),
				'third_auth_time' => time(),
			);
			if($third_id = M('User_tmp_third')->data($third_login)->add()){
				redirect($this->login_bind_back_packapp($third_id, 'weixin'));
			}else{
				fdump_sql(M('User_tmp_third')->getLastSql(), 'packapp_weixin');
				redirect($this->login_error_back_packapp('访问异常！请重新登录。'));
			}
		}
		
		$get_param = [
			'appid'		=> $this->config['wechat_appid'],
			'redirect_uri' => $this->config['site_url'].'/wap.php?c=Packapp&a=weixin_back&from_referer='.$_SESSION['from_referer'].'&from_type='.$_SESSION['from_type'],
			'response_type=' => 'code',
			'scope' => 'snsapi_userinfo',
			'state' => $_SESSION['third_login_state'],
		];
		$url = 'https://open.weixin.qq.com/connect/oauth2/authorize?' . http_build_query($get_param) . '#wechat_redirect';
		redirect($url);
	}
	
	//微信登录 - 回跳
	public function weixin_back(){
		$referer = !empty($_SESSION['from_referer']) ? $_SESSION['from_referer'] : $this->config['site_url'].'/wap.php';
        $from_type = !empty($_SESSION['from_type']) ? $_SESSION['from_type'] : $_GET['from_type'];
        fdump_api([$referer,$_GET['code'],$_GET['from_type'],$_SESSION['from_type'],$from_type],'weixin_back_230105',1);
		if (isset($_GET['code'])){
			unset($_SESSION['third_login_state']);
			import('ORG.Net.Http');
			$http = new Http();
			$return = $http->curlGet('https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$this->config['wechat_appid'].'&secret='.$this->config['wechat_appsecret'].'&code='.$_GET['code'].'&grant_type=authorization_code');
			$jsonrt = json_decode($return,true);
			if($jsonrt['errcode']){
				$error_msg_class = new GetErrorMsg();
				redirect($this->login_error_back_packapp('授权发生错误：'.$error_msg_class->wx_error_msg($jsonrt['errcode'])));
			}

			$return = $http->curlGet('https://api.weixin.qq.com/sns/userinfo?access_token='.$jsonrt['access_token'].'&openid='.$jsonrt['openid'].'&lang=zh_CN');

			$jsonrt = json_decode($return,true);
			if ($jsonrt['errcode']) {
				$error_msg_class = new GetErrorMsg();
				redirect($this->login_error_back_packapp('授权发生错误：'.$error_msg_class->wx_error_msg($jsonrt['errcode'])));
			}
			
			$is_follow = 0;
			$access_token_array = D('Access_token_expires')->get_access_token();
			if (!$access_token_array['errcode']) {
				$return = $http->curlGet('https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token_array['access_token'].'&openid='.$jsonrt['openid'].'&lang=zh_CN');
				fdump($return, 'return_return');
				$userifo = json_decode($return, true);
				if (!$return['errcode']) {
					$is_follow = $userifo['subscribe'];
				}
			}

			/*优先使用 unionid 登录*/
			if (!empty($jsonrt['unionid'])) {
				$now_user = D('User')->get_user($jsonrt['unionid'], 'union_id');
				if ($now_user && $jsonrt['openid'] && $now_user['openid'] != $jsonrt['openid']) {
					D('User')->save_user($now_user['uid'], 'openid', $jsonrt['openid']);
				}
			}
			if (empty($now_user) && $jsonrt['openid']) {
				/*再次使用 openid 登录*/
				$now_user = D('User')->get_user($jsonrt['openid'], 'openid');
				if ($now_user && $jsonrt['unionid'] && $now_user['union_id'] != $jsonrt['unionid']) {
					D('User')->save_user($now_user['uid'], 'union_id', $jsonrt['unionid']);
				}
			}
			if(!empty($now_user)){
				D("User_spread")->updateUid($jsonrt['openid'], $now_user['uid']);
				redirect($this->login_ok_back_packapp($now_user['uid']));
			}

			/*注册用户*/
			$third_login = array(
				'from' 		=> 'weixin',
				'openid' 	=> $jsonrt['openid'],
				'union_id' 	=> ($jsonrt['unionid'] ? $jsonrt['unionid'] : ''),
				'nickname' 	=> $jsonrt['nickname'],
				'sex' 		=> $jsonrt['sex'],
				'province' 	=> $jsonrt['province'],
				'city' 		=> $jsonrt['city'],
				'avatar' 	=> D('User')->down_avatar($jsonrt['headimgurl'], $jsonrt['unionid'] ? $jsonrt['unionid'] : $jsonrt['openid']),
				'is_follow' => $is_follow,
				'third_auth_time' => time(),
			);
            fdump_api([$third_login,$from_type],'weixin_back_230105',1);
			if($third_id = M('User_tmp_third')->data($third_login)->add()){
                if (in_array($from_type, ['park_new','maiyatian'])){
					isset($jsonrt['unionid']) && $jsonrt['union_id'] = $jsonrt['unionid'];
                    if ($uid = D('User')->autoreg($jsonrt)){
                        fdump_api([$uid,$jsonrt],'weixin_back_230105',1);
                        $login_result = D('User')->autologin('openid',$jsonrt['openid']);
                        fdump_api([$uid,$jsonrt,$login_result],'weixin_back_230105',1);
                        if (!$login_result['error_code']){
                            $now_user = $login_result['user'];
                            session('user',$now_user);
                            fdump_api([$uid,$now_user],'weixin_back_230105',1);
                            redirect($this->login_ok_back_packapp($uid['uid']));
                        }else{
                            redirect($this->login_bind_back_packapp($third_id, 'weixin', $third_login));
                        }
                    }else{
                        redirect($this->login_bind_back_packapp($third_id, 'weixin', $third_login));
                    }
                }
                
               redirect($this->login_bind_back_packapp($third_id, 'weixin', $third_login));
			}else{
				fdump_sql(M('User_tmp_third')->getLastSql(), 'packapp_weixin_1');
				redirect($this->login_error_back_packapp('访问异常！请重新登录。'));
			}
		}else{
			fdump_sql(M('User_tmp_third')->getLastSql(), 'packapp_weixin_2');
			redirect($this->login_error_back_packapp('访问异常！请重新登录。'));
		}
	}
	
	//微博登录
	public function weibo(){
		$_SESSION['from_referer'] = $_SERVER['original_get']['referer'];
		$_SESSION['third_login_state']   = md5(uniqid());
		$_SESSION['from_device_id']   = $_GET['device_id'];
		
		//判断已经登录
		if($this->user_session['uid']){
			redirect($this->login_ok_back_packapp($this->user_session['uid']));
		}
		
		$get_param = [
			'client_id' => $this->config['weibo_app_key'],
			'redirect_uri' => $this->config['site_url'].'/source/weiboh5_login_back.php',
			'scope'	=> 'all',
			'state'	=> $_SESSION['third_login_state'],
			'display'	 => 'default',
			'forcelogin' => 'false',
			'language'   => empty($this->config['default_language']) || $this->config['default_language'] == 'chinese' ? '' : 'en',
		];
		$url = 'https://api.weibo.com/oauth2/authorize?' . http_build_query($get_param);
		redirect($url);
	}
	
	//微博登录 - 回跳
	public function weibo_back(){
		$referer = !empty($_SESSION['from_referer']) ? $_SESSION['from_referer'] : $this->config['site_url'].'/wap.php';

		if (isset($_GET['code'])){
			unset($_SESSION['third_login_state']);
			import('ORG.Net.Http');
			$http = new Http();
			
			$post_param = [
				'client_id' => $this->config['weibo_app_key'],
				'client_secret' => $this->config['weibo_app_secret'],
				'grant_type' => 'authorization_code',
				'code' => $_GET['code'],
				'redirect_uri' => $this->config['site_url'].'/source/weiboh5_login_back.php',
			];

			$return = $http->curlPostOwn('https://api.weibo.com/oauth2/access_token?'. http_build_query($post_param),[]);
			$jsonrt = json_decode($return,true);
			if($jsonrt['error_code']){
				redirect($this->login_error_back_packapp('授权发生错误：'.$jsonrt['error'].'('.$jsonrt['error_description'].')'));
			}
			
			//获取用户信息
			$get_param = [
				'access_token' => $jsonrt['access_token'],
				'uid' => $jsonrt['uid'],
			];
			
			$return = $http->curlGet('https://api.weibo.com/2/users/show.json?' . http_build_query($get_param));
			$jsonrt = json_decode($return,true);
			
			if($jsonrt['error_code']){
				redirect($this->login_error_back_packapp('授权发生错误：'.$jsonrt['error'].'('.$jsonrt['error_description'].')'));
			}
			

			$now_user = D('User')->get_user($jsonrt['idstr'], 'weibo_token');
			if(!empty($now_user)){
				redirect($this->login_ok_back_packapp($now_user['uid']));
			}
			
			$third_login = [
				'from' => 'weibo',
				'weibo_token' => $jsonrt['idstr'],
				'nickname' => $jsonrt['screen_name'],
				'avatar' => $jsonrt['avatar_large'],
				'third_auth_time' => time(),
			];
			if($third_id = M('User_tmp_third')->data($third_login)->add()){
				redirect($this->login_bind_back_packapp($third_id, 'weibo'));
			}else{
				redirect($this->login_error_back_packapp('访问异常！请重新登录。'));
			}
		}else{
			redirect($this->login_error_back_packapp('访问异常！请重新登录。'));
		}
	}
	
	//登录失败时跳回 packapp
	public function login_error_back_packapp($errmsg){
		$referer = !empty($_SESSION['from_referer']) ? $_SESSION['from_referer'] : $this->config['site_url'].'/wap.php';
		
		//回调路径
		$device_id = !empty($_SESSION['from_device_id']) ? $_SESSION['from_device_id'] : 'packapp';
		$redirect_param = [
			'device_id' => $device_id,
			'errmsg' 	=> $errmsg,
			'login_type' 	=> $_SESSION['login_type'],
		];
		return $this->build_url($referer,$redirect_param);
	}
	
	//需要绑定时跳回 packapp
	public function login_bind_back_packapp($third_id,$third_from,$third_info=[]){
//		$referer = !empty($_SESSION['from_referer']) ? $_SESSION['from_referer'] : $this->config['site_url'].'/wap.php';
		if (!empty($_SESSION['from_referer'])){
			$referer =  $_SESSION['from_referer'];
		}else if(isset($_GET['loginFail']) && !empty($_GET['loginFail'])) {
			$referer =  $_GET['loginFail'];
		}else if (isset($_GET['from_referer']) && !empty($_GET['from_referer'])){
			$referer =  $_GET['from_referer'];
		}else{
			$referer = $this->config['site_url'].'/wap.php';
		}
		
 
		//回调路径
		$device_id = !empty($_SESSION['from_device_id']) ? $_SESSION['from_device_id'] : 'packapp';
		$redirect_param = [
			'device_id' => $device_id,
			'third_id' 	=> $third_id,
			'third_from' 	=> $third_from,
			'login_type' 	=> $_SESSION['login_type'],
			'openid' 	=> $third_info['openid'],
			'nickname' 	=> $third_info['nickname'],
			'avatar' 		=> $third_info['avatar'],
		];
		//临时登录情况下
		if($_SESSION['thirdLogin']){
			$redirect_param['thirdLogin'] = 'true';
			unset($_SESSION['thirdLogin']);
		}
		return $this->build_url($referer,$redirect_param);
	}
	//登录成功时跳回 packapp
	public function login_ok_back_packapp($uid){
//		$referer = !empty($_SESSION['from_referer']) ? $_SESSION['from_referer'] : $this->config['site_url'].'/wap.php';
		if (!empty($_SESSION['from_referer'])){
			$referer =  $_SESSION['from_referer'];
		}else if(isset($_GET['loginFail']) && !empty($_GET['loginFail'])) {
			$referer =  $_GET['loginFail'];
		}else if (isset($_GET['from_referer']) && !empty($_GET['from_referer'])){
			$referer =  $_GET['from_referer'];
		}else{
			$referer = $this->config['site_url'].'/wap.php';
		}
        fdump_api([$uid,$_SESSION['from_referer'],$_GET['loginFail'],$_GET['from_referer'],$referer],'weixin_back_230105',1);
		//回调路径
		$device_id = !empty($_SESSION['from_device_id']) ? $_SESSION['from_device_id'] : 'packapp';
		$ticketArr = ticket::create($uid, $device_id, true);
		$redirect_param = [
			'device_id' => $device_id,
			'ticket' 	=> $ticketArr['ticket'],
		];
		//临时登录情况下
		if($_SESSION['thirdLogin']){
			$redirect_param['thirdLogin'] = 'true';
			unset($_SESSION['thirdLogin']);
		}
        fdump_api([$uid,$referer,$redirect_param],'weixin_back_230105',1);
		return $this->build_url($referer,$redirect_param);
	}

	public function merchant_authorizer(){
		$this->open_authorize_openid(array('appid' => $_GET['authorizer_appid'], 'mer_id' => $_GET['mer_id'], 'plat_openid' => $_GET['plat_openid']));
		if($_SESSION['open_authorize_openid'] == 'error'){
        	$weixin_bind_info = D('Weixin_bind')->where(array('mer_id'=>$_GET['mer_id']))->find();
          	if($weixin_bind_info['last_user_error']){
            	$last_user_error_info = unserialize($weixin_bind_info['last_user_error']);
              	if($last_user_error_info['wx_error']){
                	$this->error_tips('微信开放平台返回错误：' . $last_user_error_info['wx_error']['errmsg']);
                }
            }
        }
		redirect(html_entity_decode($_GET['refer']));
	}
}
