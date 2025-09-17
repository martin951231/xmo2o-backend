<?php
/**
 * Login Controller
 */
class LoginAction extends BaseAction {
    public function config(){
        $agreement = $this->config['register_agreement'];
        $this->returnCode(0,array('agreement'=>$agreement));
    }
    //登录
    public function login() {
		session_start();
        session('user', null);
        $ticket = I('ticket', false);
        $client = I('client', 0);
        $registrationId = I('registrationId', '','trim');
		if(!$client && $this->is_app_browser){
			if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']),'android') !== false){
				$client = 2;
            }else{
                $client = 1;
            }
		}
        if ($ticket) {
            $info = ticket::get($ticket, $this->DEVICE_ID, true);
            if ($info) {
                $uid = $info['uid'];
				
				$login_result = D('User')->autologin('uid',$uid);
				if($login_result['error_code']){
					$this->returnCode(1001,array(),$login_result['msg']);
				}else{
					$user = $login_result['user'];
				}
				
                $doorList	=	$this->door_list($uid,1);
                $return = array(
                    'ticket'    =>	$ticket,
                    'v20_ticket' => D("User")->getV20Ticket($uid),
                    'user'      =>	$user,
                    'door'		=>	isset($doorList)?$doorList:array(),
                );
                //推送登录状态给到楼宇系统
                if ($_POST['building_login_key']) {
                    D('User')->pushUserLoginInfoToBuildingSystem($_POST['building_login_key'], $uid);
                }
                $this->addLog($user,$client,$registrationId);
                $this->upDevice($client,$uid);
                $this->returnCode(0, $return);
            }
        }
        $phone = I('phone', false);
        if (!$phone) {
            $this->returnCodeError('账号或密码错误');
        }
        try {
            $loginLog = [
                'logAccount' => $phone,
            ];
            D('User_login_log')->checkErr($loginLog);
        }catch (Exception $e) {
            $this->returnCodeError($e->getMessage());
        }
		
		$phone_country_type = I('phone_country_type','');
		$phone = phone_format($phone_country_type, $phone);
		
        $passwd = I('passwd', false);
        $smscode = I('code', false);
        if (!$passwd && !$smscode) {
            $this->returnCodeError('账号或密码错误');
        }
        if($smscode){
            $where = array();
            $where['phone'] = $phone;
            $where['extra'] = $smscode;
            // $where['type']   = 0;
            $where['status'] = 0;
            $where['expire_time'] = array('gt', time());
            $smsresult = D("App_sms_record")->where($where)->find();
            if (!$smsresult) {
                // 先检查
                $loginLog = [
                    'logAccount' => $phone,
                    'device_id' => $this->DEVICE_ID,
                    'logType' => 1,
                ];
                try {
                    D('User_login_log')->checkErr($loginLog);
                }catch (Exception $e) {
                    $this->returnCodeError($e->getMessage());
                }
                $loginLog['logContent'] = '错误验证码:'.$smscode;
                D('User_login_log')->addLoginErrLog($loginLog);
                $this->returnCode('20044009');
            }
        }
        $phone_country_type = remove_xss($phone_country_type);
        $phone = remove_xss($phone);
        $smscode = remove_xss($smscode);
		
        $result = D("User")->checkin($phone, $passwd,false,$smscode,$phone_country_type);

        if ($result['error_code']) {
            if($smscode){
                $date_user['phone_country_type'] = $phone_country_type;
                $date_user['phone'] = $phone;
                $date_user['client'] = remove_xss($_POST['client']);
                $date_user['source'] ='sms';
                $date_user['spread_code'] = strval($_POST['spread_code']);
				$date_user['nickname'] = substr_replace($phone, '****', 3, 4);
                $reg_user  = D("User")->autoreg($date_user,true);
				if($reg_user['error_code']){
					$this->returnCode(1001,array(),$reg_user['msg']);
				}
				$login_result = D('User')->autologin('uid',$reg_user['uid']);
				if($login_result['error_code']){
					$this->returnCode(1001,array(),$login_result['msg']);
				}
				$result['user'] = $login_result['user'];
				
                if(!empty($result['user']) && $this->config['open_score_fenrun'] && $_POST['spread_code']){
                    if($spread_user = M('User')->where(array('spread_code'=>$_POST['spread_code']))->find()){
                        $now_level = M(_view('User_level'))->where(array('level' => $spread_user['level']))->find();
                        // D('User_spread')->data(array('spread_openid'=>$spread_user['openid'],'spread_uid'=>$spread_user['uid'],'openid'=>'','uid'=>$result['user']['uid']))->add();
                        D('User_spread')->data(array('spread_uid' => $spread_user['uid'], 'uid' => $result['user']['uid'], 'add_time' => time()))->add();
                        if ($this->config['spread_user_give_type'] == 1 || $this->config['spread_user_give_type'] == 2) {
                            $spread_give_money = $now_level['spread_user_give_moeny']>0?$now_level['spread_user_give_moeny']:$this->config['spread_give_money'];
                            //D('User')->add_money($spread_user['uid'],  $spread_give_money, '推荐新用户注册平台赠送余额','','',$uid);
                            $Res = D('Fenrun')->add_recommend_award($spread_user['uid'],$result['user']['uid'],1,$spread_give_money,L_('推荐新用户注册平台奖励佣金'));

                        }

                    }
                }
            }else{
                $this->returnCodeError($result['msg']);
            }
        }
        unset($result['user']['pwd']);
        $doorList = $this->door_list($result['user']['uid'], 1);
        $ticket = ticket::create($result['user']['uid'], $this->DEVICE_ID, true);
        $return = array(
            'ticket'=>	$ticket['ticket'],
            'v20_ticket' => D("User")->getV20Ticket($result['user']['uid']),
            'user'	=>	$result['user'],
            'door'	=>	isset($doorList)?$doorList:array(),
        );
        D("User_spread")->updateUid($result['user']['openid'], $result['user']['uid']);
        $this->addLog($result['user'],$client,$registrationId);
        $this->upDevice($client,$result['user']['uid']);

        //推送登录状态给到楼宇系统
        if ($_POST['building_login_key']) {
            D('User')->pushUserLoginInfoToBuildingSystem($_POST['building_login_key'], $result['user']['uid']);
        }
        $this->returnCode(null, $return);
    }
    //注册
    public function register() {
        $registrationId = I('registrationId', '','trim');
        $code = I('code', 0);
        if (! $code) {
            $this->returnCode('20044008');
        }
        $phone = I("phone", false);
        if (! $phone) {
            $this->returnCode('20042001');
        }
        $passwd = I("passwd", false);
        if (! $passwd) {
            $this->returnCode('20042002');
        }
        $client = I('client', 0);
        //判断验证码
        //if ($code != '1234') {
        //      $this->returnCode('20044009');
        // }
        $where = array();
        $where['phone'] = $phone;
        $where['extra'] = $code;
        // $where['type']   = 1;
        $where['status'] = 0;
        $where['expire_time'] = array('gt', time());
        $result = D("App_sms_record")->where($where)->find();
        if (! $result) {
           $this->returnCode('20044009');
        }
        //注册账号
        $result = D('User')->where(array('phone'=>$phone))->find();
        if ($result) {
            $this->returnCode('20044005');
        }
		
		$phone_country_type = strval($_POST['phone_country_type']);
		$phone = phone_format($phone_country_type, $phone);
		
        $result = D('User')->checkreg($phone, $passwd, $phone_country_type);
        if ($result['error_code']) {
            $this->returnCode('20044003');
        }

        if(!empty($result['user']) && $this->config['open_score_fenrun'] && $_POST['spread_code']){
            if($spread_user = M('User')->where(array('spread_code'=>$_POST['spread_code']))->find()){
                $now_level = M(_view('User_level'))->where(array('level' => $spread_user['level']))->find();
                // D('User_spread')->data(array('spread_openid'=>$spread_user['openid'],'spread_uid'=>$spread_user['uid'],'openid'=>'','uid'=>$result['user']['uid']))->add();
                D('User_spread')->data(array('spread_uid' => $spread_user['uid'], 'uid' => $result['user']['uid'], 'add_time' => time()))->add();
                if ($this->config['spread_user_give_type'] == 1 || $this->config['spread_user_give_type'] == 2) {
                    $spread_give_money = $now_level['spread_user_give_moeny']>0?$now_level['spread_user_give_moeny']:$this->config['spread_give_money'];
                    //D('User')->add_money($spread_user['uid'],  $spread_give_money, '推荐新用户注册平台赠送余额','','',$uid);
                    $Res = D('Fenrun')->add_recommend_award($spread_user['uid'],$result['user']['uid'],1,$spread_give_money,L_('推荐新用户注册平台奖励佣金'));

                }

            }
        }

        $database_house_village_user_bind = D('House_village_user_bind');
        $database_house_village_user_vacancy = D('House_village_user_vacancy');
        $find_village = D('House_village_user_bind')->where(array('phone'=>trim($phone)))->select();
        if($find_village){
            foreach($find_village as $k=>$v){
                $data_vacancy = array();
                $dataInfo['uid'] = $result['user']['uid'];
                $database_house_village_user_bind->where(array('pigcms_id'=>$v['pigcms_id']))->data($dataInfo)->save();
                if($v['type']==0 && $v['vacancy_id']>0){
                    $database_house_village_user_vacancy->where(array('pigcms_id'=>$v['vacancy_id']))->data($dataInfo)->save();
                }
            }
        }

        unset($result['user']['pwd']);
        $uid = $result['user']['uid'];
        $nickname = $result['user']['nickname'];
        // D('User')->register_give_money($uid,1);	//checkreg 已经给过积分
        //商盈通对接
        $shangyingtong = D("UserShangyingtong");
        $shangyingtong->reg($phone,$uid);

        $ticket = ticket::create($uid, $this->DEVICE_ID, true);
        //统一数据结构
        $result['user']['coupon_count'] = 0;
        $return = array(
            'ticket' => $ticket["ticket"],
            'v20_ticket' => D("User")->getV20Ticket($uid),
            'user' => $result['user'],
        );
        $this->addLog($result['user'],$client,$registrationId);
        $this->upDevice($client,$result['user']['uid']);
        $this->returnCode(null, $return);
    }



    //支付宝小程序授权登录
    public function alipayapp_login() {
        $registrationId = I('registrationId', '','trim');
        $client = I('client', 0);
        $auth_code = $_POST['auth_code'];
        $param = array();
        $param['app_id'] = $this->config['alipayapp_app_id'];
        $param['method'] = 'alipay.system.oauth.token';
        $param['charset'] = 'utf-8';
        $param['sign_type'] = $this->config['alipayapp_app_encrypt_type'] ? $this->config['alipayapp_app_encrypt_type'] : 'RSA';
        $param['timestamp'] = date('Y-m-d H:i:s');
        $param['version'] = '1.0';
        $param['grant_type'] = 'authorization_code';
        $param['code'] = $auth_code;
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

        $priKey = $this->config['alipayapp_app_prikey'];
        $res = "-----BEGIN RSA PRIVATE KEY-----\n".wordwrap($priKey, 64, "\n", true)."\n-----END RSA PRIVATE KEY-----";

        if(!function_exists('openssl_sign')){
            $this->returnCode(1,null,'系统不支持 openssl_sign');
        }
        
        if($param['sign_type'] == 'RSA2'){
            openssl_sign($stringToBeSigned, $sign, $res, OPENSSL_ALGO_SHA256);
        }else{
            openssl_sign($stringToBeSigned, $sign, $res);
        }
            
        if(empty($sign)){
            $this->returnCode(1,null,'支付宝密钥错误');
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

        fdump($requestUrl,'alipayapp_login');
        $return = Http::curlGet($requestUrl);
        $returnArr = json_decode($return,true);
        
        if($returnArr['alipay_system_oauth_token_response']){
            fdump($returnArr,'alipayapp_login',1);
            //如果存在即 自动登录
            $now_user = D('User')->get_user($returnArr['alipay_system_oauth_token_response']['user_id'], 'alipay_uid');
            if(!empty($now_user)){
                //回调路径
                $ticket = ticket::create($now_user['uid'], $this->DEVICE_ID, true);
                //统一数据结构
                $return = array(
                    'ticket' => $ticket["ticket"],
                    'v20_ticket' => D("User")->getV20Ticket($uid),
                    'user' => $now_user,
                    'access_token' => $returnArr['alipay_system_oauth_token_response']['access_token'],
                );
                fdump($return,'login',1);
                $this->addLog($result['user'],$client,$registrationId);
                $this->upDevice($client,$result['user']['uid']);
                $this->returnCode(null, $return);
            }
          
            /*注册用户*/
            $jsonrt = $returnArr['alipay_system_oauth_token_response'];
            $third_login = array(
                'from'      => 'alipayapp',
                'alipay_uid'=> $jsonrt['user_id'],
                'nickname'  => $_POST['nickName'] ? $_POST['nickName'] : '支付宝用户',
                'sex'       => $_POST['gender'] == 'm' ? 1 : 2,
                'province'  => rtrim($_POST['province'],'省'),
                'city'      => rtrim($_POST['city'],'市'),
                'avatar'    => $_POST['avatar'] ? D('User')->down_avatar($_POST['avatar'], $jsonrt['user_id']) : '',
                'third_auth_time' => time(),
            );
            
            if($third_id = M('User_tmp_third')->data($third_login)->add()){
                $data_user = $third_login;
                unset($data_user['third_id'], $data_user['from'], $data_user['third_auth_time']);
                $data_user['source'] = 'alipayapp';

                $database_user = D('User');
                $reg_result = D('User')->autoreg($data_user);
                fdump($reg_result,'login',1);
                if($reg_result['error_code']){
                    $this->returnCodeError($reg_result['msg']);
                }
                
                $login_result = D('User')->autologin('uid',$reg_result['uid']);
                if($login_result['error_code']){
                    $this->returnCodeError($login_result['msg']);
                }else{
                    $now_user = $login_result['user'];
                    $ticket = ticket::create($now_user['uid'],$this->DEVICE_ID, true);
                    $return = array(
                        'ticket'=>  $ticket['ticket'],
                        'v20_ticket' => D("User")->getV20Ticket($uid),
                        'user'  =>  $now_user,
                        'access_token'  =>  $jsonrt['access_token'],
                    );
                    $this->addLog($result['user'],$client,$registrationId);
                    $this->returnCodeOk($return);
                }
            }else{
                $this->returnCode(1,null,'访问异常！请重新登录。');
            }
        }else if($returnArr['error_response']){
            fdump($param,'alipayapp_login',1);
            fdump($returnArr['error_response'],'alipayapp_login',1);
            $this->returnCode(1,null,'支付宝授权提示：'.$returnArr['error_response']['sub_msg']);
        }
    }

    //支付宝解密手机号，存入数据库
    public function alipayapp_deciphering() {
        if (!$this->_uid) {
            $this->returnCode(20046009);
        }
        $client = I('client', 0);  
        $response = $_POST['response'];
        $response = json_decode($response,true);
        $response = $response['response'];
        // $response = "jtowIfjhWSeoGz4lPlatvQK3VBiG2V16SG+Cc9FOtWqocNq1RTYL1TeBHLGCF/5/9P00ZoqSf+JsWUPreo+Y2w==";

        $key = $this->config['alipayapp_app_aeskey'];
        if (!$key) {
            $this->returnCode(1,null,'系统提示：AES密钥未设置');
        }

        $aesKey=base64_decode($key);
        $iv = 0;
        $aesIV=base64_decode($iv);
        $aesCipher=base64_decode($response);
        $result=openssl_decrypt( $aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);
	    $result = json_decode($result,true);
        if($result['code']=='10000'){
            //如果存在即 自动登录 更新手机号
            $now_user = D('User')->get_user($this->_uid);
            if(!empty($now_user)){
                D('User')->where(array('uid'=>$this->_uid))->setField('phone',$result['mobile']);
                $return['phone'] = $result['mobile'];
                $this->returnCodeOk($return);
            }else{
                $this->returnCode(10052001);
            }
        }else if($result['code']){
            fdump($param,'alipayapp_deciphering',1);
            fdump($returnArr['error_response'],'alipayapp_deciphering',1);
            $this->returnCode(1,null,'支付宝提示：'.$result['sub_msg']);
        }
    }

	//发送验证码给用户
	public function sendCodeUser(){
		$now_user = D('User')->get_user($this->_uid);
		$_POST['phone_country_type'] = $now_user['phone_country_type'];
		$_POST['phone'] = $now_user['phone'];
		$_POST['type'] = '2';
		$this->sendCode();
	}
	
	//验证验证码给用户
	public function verifyCodeUser(){
		$now_user = D('User')->get_user($this->_uid);
		$_POST['phone_country_type'] = $now_user['phone_country_type'];
		$_POST['phone'] = $now_user['phone'];
		$_POST['type'] = '2';
		$this->verifyCode();
	}
	
    //发送验证码
    public function sendCode() {
		//type值  1注册帐号，2忘记密码，3修改手机，4绑定手机号 6修改一卡通密码
        $phone = I('phone', 0);
		$type = I('type', 1);
		if(strpos($phone,'+') !== false){
			$now_user = D('User')->get_user($this->_uid);
			$phone = $now_user['phone'];
		}
		$phone_country_type = I('phone_country_type', '');
		$phone = phone_format($phone_country_type, $phone);
        if($phone_country_type == '855' && !in_array(substr($phone,0,3), ['010','015','016','069','070','081','086','087','093','098','096','088','097','071','060','066','067','068','090','031','091','011','012','014','017','061','076','077','078','085','089','092','095','099','013','080','083','084','018','038'])){
            $this->returnCode('20042001',[],L_('当前号码不符合格式，请重新输入'));
        }

        if (!$phone) {
            $this->returnCode('20042001');
        }
        if(!preg_match('/^[0-9]{11}$/',$phone)){
            $this->returnCode('20042003');
        }
        if((strlen($this->DEVICE_ID)>255 || empty($this->DEVICE_ID)) && !$this->is_weixin_browser){
            $this->returnCode('20045014');
        }
		
		if(!in_array($_POST['app_type'], ['1', '2', 'packapp', 'wxapp', 'android', 'ios','dyapp','pc','street_wxapp'])){
			$this->returnCodeError('请求非法，请联系客服处理。');
		}

        if($this->DEVICE_ID != 'packapp' && $this->DEVICE_ID != 'wxapp' && $this->DEVICE_ID != 'dyapp'){
			if($this->app_version >= 30100 && ($this->config['dingxiang_captcha_appid'] || $this->config['qcloud_captcha_appid'])){
				if(!$_POST['CaptchaToken']){
					$this->returnCodeError('图形校验码未监控通过，请刷新页面后再试。');
				}
				if($this->config['qcloud_captcha_appid']){
					
					$CaptchaTokenArr = explode('___', $_POST['CaptchaToken']);
					$_POST['CaptchaToken'] = $CaptchaTokenArr[0];
					$_POST['CaptchaRandstr'] = $CaptchaTokenArr[1];
					
					import('@.ORG.qcloud_captcha.QcloudCaptcha');
					$QcloudCaptcha = new QcloudCaptcha();
					$response = $QcloudCaptcha->send($_POST['CaptchaToken'], $_POST['CaptchaRandstr']);
					
					fdump([$response, $_POST], 'CaptchaCheck', true);
					
					if($response['Response']['CaptchaCode'] != '1'){
						/**token验证失败**/
						$this->returnCodeError('图形校验码出现错误，请刷新页面后再试。' . $response['Response']['Error']['Message']);
					}
				}else{
					include('./cms/Lib/ORG/dingxianginc/CaptchaClient.php');
					$appId = $this->config['dingxiang_captcha_appid'];
					$appSecret = $this->config['dingxiang_captcha_appsecret'];
					$client = new CaptchaClient($appId, $appSecret);
					$client->setTimeOut(5);      //设置超时时间，默认2秒
					$response = $client->verifyToken($_POST['CaptchaToken']);  //token指的是前端传递的值，即验证码验证成功颁发的token
					
					fdump([$response, $_POST], 'CaptchaCheck', true);
					
					//确保验证状态是SERVER_SUCCESS，SDK中有容错机制，在网络出现异常的情况会返回通过
					if($response->serverStatus != 'SERVER_SUCCESS' || !$response->result){
						/**token验证失败**/
						$this->returnCodeError('图形校验码校验出现错误，请重试。');
					}
				}
			}
			
            //防止1分钟内多次发送短信
			if($this->DEVICE_ID != '020000000000'){
				$laste_sms = M('App_sms_record')->where(array('device_id'=>$this->DEVICE_ID,'type'=>$type))->order('pigcms_id DESC')->find();
				if($this->check_sms($this->DEVICE_ID, $phone) > 10){
					$this->returnCode('20046037');
				}
            }
            if(time()-$laste_sms['send_time']<60 && $type!=3){
                $this->returnCode('20046036');
            }
        }else if($this->DEVICE_ID == 'packapp' && strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'cloudpay') === false && ($this->config['dingxiang_captcha_appid'] || $this->config['qcloud_captcha_appid']) && !$this->_uid){	//H5模式会有图文验证码
			if(!$_POST['CaptchaToken']){
				$this->returnCodeError('图形校验码未监控通过，请刷新页面后再试。');
			}
			if($this->config['qcloud_captcha_appid']){
				import('@.ORG.qcloud_captcha.QcloudCaptcha');
				$QcloudCaptcha = new QcloudCaptcha();
				$response = $QcloudCaptcha->send($_POST['CaptchaToken'], $_POST['CaptchaRandstr']);
				
				fdump([$response, $_POST], 'CaptchaCheck', true);
				
				if($response['Response']['CaptchaCode'] != '1'){
					/**token验证失败**/
					$this->returnCodeError('图形校验码出现错误，请刷新页面后再试。' . $response['Response']['Error']['Message']);
				}
			}else{
				include('./cms/Lib/ORG/dingxianginc/CaptchaClient.php');
				$appId = $this->config['dingxiang_captcha_appid'];
				$appSecret = $this->config['dingxiang_captcha_appsecret'];
				$client = new CaptchaClient($appId, $appSecret);
				$client->setTimeOut(5);      //设置超时时间，默认2秒
				$response = $client->verifyToken($_POST['CaptchaToken']);  //token指的是前端传递的值，即验证码验证成功颁发的token
				
				fdump([$response, $_POST], 'CaptchaCheck', true);
				
				//确保验证状态是SERVER_SUCCESS，SDK中有容错机制，在网络出现异常的情况会返回通过
				if($response->serverStatus != 'SERVER_SUCCESS' || !$response->result){
					/**token验证失败**/
					$this->returnCodeError('图形校验码出现错误，请刷新页面后再试。');
				}
			}
		}
		
		//目前小程序发送短信，只有用户绑定手机号或登录，加强校验
		if($this->DEVICE_ID == 'wxapp'){
			if($_POST['third_id']){
				
				if($this->config['program_mini_regtype'] == 2){
					$this->returnCodeError('请使用页面下方一键手机号功能。');
				}
			
				$now_third = M('User_tmp_third')->where(['third_id' => $_POST['third_id']])->find();
				if(!$now_third){
					$this->returnCodeError('当前绑定信息不存在，请退出本页面重试。');
				}
				
				if($now_third['third_auth_time'] < time() - 300){
					$this->returnCodeError('授权信息超过5分钟有效期，请退出本页面重试。');
				}
				
				$now_user = D('User')->get_user($now_third['wxapp_openid'], 'wxapp_openid');
				if($now_user){
					$this->returnCodeError('用户已存在，无法重复发送短信。');
				}
			}else if(!$this->_uid){
				$this->returnCodeError('当前小程序版本较低，请使用页面下方一键手机号功能。');
			}
		}

        
        $code = mt_rand(1000, 9999);
        $text = L_('您的验证码是：X1。此验证码20分钟内有效，请不要把验证码泄露给其他人。如非本人操作，可不用理会！',$code);
        if($type == 1){        //注册帐号
            //查看用户是否存在
            $where = array();
            $where['phone'] = $phone;
            $result = D("User")->field('`uid`')->where($where)->find();
            if($result){
                $this->returnCode('20044005');
            }
        }else if ($type == 2) {  //忘记密码
            //查看用户是否存在
            $where = array();
            $where['phone'] = $phone;
            $result = D("User")->field('`uid`')->where($where)->find();
            if(!$result){
                $this->returnCode('20044011');
            }
        }

        $send_time = time();
        // 当前手机号 先前发送的验证码 重置为失效
        $where_repeat = array();
        $where_repeat['phone'] = $phone;
        $where_repeat['device_id'] = $this->DEVICE_ID;
        $where_repeat['type'] = $type;
        $where_repeat['status'] = 0;
        $where_repeat['expire_time'] = array('gt', $send_time);
        $save_set = [
            'status' => 1,
            'expire_time' => $send_time,
        ];
        D("App_sms_record")->where($where_repeat)->save($save_set);

        $columns = array();
        $columns['phone'] = $phone;
        $columns['text'] = $text;
        $columns['extra'] = $code;
        $columns['device_id'] = $this->DEVICE_ID;
        $columns['type'] = $type;
        $columns['status'] = 0;
        $columns['send_time'] = $send_time;
        $columns['send_ip'] = get_client_ip();
        $columns['expire_time'] = $columns['send_time'] + 1200;
        $result = D("App_sms_record")->add($columns);
        if (! $result) {
            $this->returnCode('20044007');
        }
		
		$sms_data = array('mer_id' => 0, 'store_id' => 0, 'content' => $text, 'mobile' => $phone, 'uid' => 0, 'type' => 'app_register');
        if($type == 70){
            $sms_data['sendto'] = 'merchant';
        }
		$phone_country_type && $sms_data['nationCode']  = $phone_country_type;
        $return = Sms::sendSms($sms_data);
		
        if ($result != 0) {
            $this->returnCode(self::ConverSmsCode($return));
        }
        $this->returnCode(null, $return);
    }

    //检查短信一天条数
    public function check_sms($device_id,$phone){
        $today_zero = strtotime(date('Ymd',time()));
		if($device_id == 'wxapp'){
			$where['_string'] = "phone ='".$phone."' AND send_time >".$today_zero.' AND send_time<'.($today_zero+86399);
		}else{
			$where['_string'] = "(device_id = '".$device_id."' OR phone ='".$phone."') AND send_time >".$today_zero.' AND send_time<'.($today_zero+86399);
		}
        return M('App_sms_record')->where($where)->count();
    }

    //  发送更改密码验证码
    public function verifyCode() {
		if(strpos($_POST['phone'],'+') !== false){
			$now_user = D('User')->get_user($this->_uid);
			$_POST['phone'] = $now_user['phone'];
		}
		
		$phone = phone_format($_POST['phone_country_type'], $_POST['phone']);
        if (! $phone) {
            $this->returnCode('20042001');
        }
		
        $code = I('code', 0);
        if (! $code) {
            $this->returnCode('20044008');
        }

        $type = I('type', 2);
        $where = array();
        $where['phone'] = $phone;
        $where['extra'] = $code;
        // $where['type'] = $type;
        $where['status'] = 0;
        $where['expire_time'] = array('gt', time());
        $result = D("App_sms_record")->where($where)->find();
        if (! $result) {
            $this->returnCode('20044009');
        }
        $result = D("App_sms_record")->where($where)->data(array('status'=>1))->save();
        $this->returnCode();
    }
    //  忘记密码-更改密码
    public function forget() {
        $code = I('code', 0);
        if (! $code) {
            $this->returnCode('20044008');
        }
        $phone = phone_format($_POST['phone_country_type'], $_POST['phone']);
        if (! $phone) {
            $this->returnCode('20042001');
        }
        $passwd = I("passwd", false);
        if (! $passwd) {
            $this->returnCode('20042002');
        }

        //查看用户是否存在
        $where = array();
        $where['phone'] = $phone;
        $result = D("User")->field('`uid`')->where($where)->find();
        if (! $result) {
            $this->returnCode('20044011');
        }

        $result = D('User')->save_user($result['uid'],'pwd',md5($passwd));
        if ($result['error_code']) {
            $this->returnCode('20044012');
        }

        $this->returnCode();
    }
    //微信登录
    public function weixin_login() {
        $registrationId = I('registrationId', '','trim');
        $client = I('client', 1);
        $weixin_open_id = I('weixin_open_id', 0, "trim");
        if (! $weixin_open_id) {
            $this->returnCode('20045001');
        }
        $weixin_union_id = I('weixin_union_id', 0, "trim");
        if (! $weixin_union_id) {
            $this->returnCode('20045002');
        }
        $nickname = I('nickname', "weixin_user", "trim");
        $avatar = I('avatar');
        $sex = I('sex', 1);
        //如果已经绑定账号，直接登录，返回ticket
        $user = D("User")->get_user($weixin_open_id, "app_openid");
        if(strpos($user['openid'],'no_use')){
            $this->returnCode('20120005');
        }
        if($user&&$user['status'] !=1 ){
            $this->returnCode('20120008');
        }

        if ($user) {
            $columns = array();
            $columns['last_time'] = $_SERVER['REQUEST_TIME'];
            $columns['last_ip'] = get_client_ip(0);
            D('User')->where(array('uid' => $user['uid']))->save($columns);
            unset($user['pwd']);
            $ticket = ticket::create($user['uid'], $this->DEVICE_ID, true);
            $return = array(
                'ticket' => $ticket['ticket'],
                'v20_ticket' => D("User")->getV20Ticket($uid),
                'user'=>$user
            );
            $this->addLog($user,$client,$registrationId);
            $this->upDevice($client,$user['uid']);
            $this->returnCode(null, $return);
        }
        //如果已经绑定账号，直接登录，返回ticket
        $user = D("User")->get_user($weixin_union_id, "union_id");
        if ($user) {
            $columns = array();
            $columns['app_openid'] = $weixin_open_id;
            $columns['last_time'] = $_SERVER['REQUEST_TIME'];
            $columns['last_ip'] = get_client_ip(0);
            D('User')->where(array('uid' => $user['uid']))->save($columns);
            unset($user['pwd']);
            $ticket = ticket::create($user['uid'], $this->DEVICE_ID, true);
            $return = array(
                'ticket' => $ticket['ticket'],
                'v20_ticket' => D("User")->getV20Ticket($uid),
                'user'=>$user
            );
            $this->addLog($user,$client,$registrationId);
            $this->upDevice($client,$user['uid']);
            $this->returnCode(null, $return);
        }
        //未绑定，注册新账号，并登录，返回ticket
        $columns = array();
        $columns['app_openid'] = $weixin_open_id;
        $columns['union_id'] = $weixin_union_id;
        $columns['sex'] = $sex;
        $columns['avatar'] = $avatar;
        $columns['add_time'] = $_SERVER['REQUEST_TIME'];
        $columns['last_time'] = $_SERVER['REQUEST_TIME'];
        $columns['source']    = ($client == '1' ? 'iosapp' : 'androidapp');
        $columns['add_ip'] = get_client_ip(0);
        $columns['last_ip'] = get_client_ip(0);
		if($nickname == $this->config['site_name']){
       		$columns['nickname'] = L_('昵称');
		}else{
        	$columns['nickname'] = $nickname;
		}
        $userId = D("User")->data($columns)->add();
        if (! $userId) {
            $this->returnCode('20045004');
        }
        D('User')->register_give_money($user['uid'], true);

        $user = D("User")->get_user($userId, "uid");
        $this->addLog($user,$client,$registrationId);
        $this->upDevice($client,$user['uid']);
        unset($user['pwd']);
        $ticket = ticket::create($user['uid'], $this->DEVICE_ID, true);
        $return = array(
            'ticket' => $ticket['ticket'],
            'v20_ticket' => D("User")->getV20Ticket($uid),
            'user'=>$user
        );

        $this->returnCode(null, $return);
    }

    //第三方登录  微信、微博、facebook...
    public function platform_login() {
        $registrationId = I('registrationId', '','trim');
        $client             = I('client', 0);
        $platform           = I('platform');
        $token              = I("token") ? : I("mobile_code");
        $nickname           = I('nickname');
        $avatar             = I('avatar');
        $sex                = I('sex', 1);
        $code               = I('code', 0);
        $phone              = I('phone');
        $passwd             = I('passwd');
        $phone_country_type = I('phone_country_type');
		
		$phone = phone_format($phone_country_type, $phone);
		
        if(empty($platform)) $this->returnCode('20020011');
        //判断验证码
        if($phone){
            if(empty($code)){
                $this->returnCode('20044009');
            }

            $where = array();
            $where['phone'] = $phone;
            $where['extra'] = $code;
//          $where['type']   = 1;
            $where['status'] = 0;
            $where['expire_time'] = array('gt', time());
            $result = D("App_sms_record")->where($where)->find();
            if (! $result) {
               $this->returnCode('20044009');
            }
        }
        if($platform == 'wechat'){
            $weixin_open_id = I('weixin_open_id', 0, "trim");
            if (! $weixin_open_id) {
                $this->returnCode('20045001');
            }
            $weixin_union_id = I('weixin_union_id', 0, "trim");
            if (! $weixin_union_id) {
                $this->returnCode('20045002');
            }
            //如果已经绑定账号，直接登录，返回ticket
            $user = D("User")->get_user($weixin_open_id, "app_openid");
            // 如果查询不到 查询其他表格
            if (empty($user) && isset($_POST['app_package_name']) && trim($_POST['app_package_name'])) {
                $app_package_name = trim($_POST['app_package_name']);
                $where_user_login = [
                    'business_type' => 'appweixin',
                    'business_otherid' => $weixin_open_id,
                    'business_pack' => $app_package_name,
                ];
                $user_login_uid = M('User_login')->where($where_user_login)->getField('uid');
                if ($user_login_uid) {
                    $user = D("User")->get_user($user_login_uid, "uid");
                }
            }
			if($user){
				if(empty($user['phone']) && empty($phone)){
					$this->returnCodeError(L_('您还没有绑定手机，请先绑定手机!'));
				}
				if(strpos($user['openid'],'no_use')){
					$this->returnCode('20120005');
				}
				if($user['status'] !=1 ){
					$this->returnCode('20120008');
				}

                $columns = array();
                $columns['last_time'] = $_SERVER['REQUEST_TIME'];
                $columns['last_ip'] = get_client_ip(0);
                if($phone){
					if($phone_country_type){
						$columns['phone_country_type'] = $phone_country_type;
					}
                    $columns['phone'] = $phone;
                    $other_user = D("User")->get_user($phone,'phone');
                    if($other_user['uid'] == $user['uid']){//确保不是自己
                        unset($other_user);
                    }
                }
				if($passwd){
					$columns['pwd'] = md5($passwd);
				}
				
				//如果有头像，则保存头像
				if($avatar){
					$columns['avatar'] = D('User')->down_avatar($avatar, $weixin_union_id);
				}
				if(!empty($other_user)){
                    $data_use['openid'] = $user['openid'].'~no_use';
                    $data_use['union_id'] = $user['union_id'].'~no_use';
                    $data_use['wxapp_openid'] = $user['wxapp_openid'].'~no_use';
                    $data_use['app_openid'] = $user['app_openid'].'~no_use';
                    $data_use['paotui_openid'] = $user['paotui_openid'].'~no_use';
                    $data_use['group_openid'] = $user['group_openid'].'~no_use';
                    $data_use['phone'] = '';
                    $data_use['status'] = 4;
                    D('User')->where(array('uid'=>$user['uid']))->save($data_use);
                    setcookie('user_device_id', '', -1, '/');
                    setcookie('user_ticket', '', -1, '/');
                    setcookie('login_status', '', -1, '/');

                    D('User')->where(array('uid' => $other_user['uid']))->save($columns);
                    $user = $other_user;
                }
                else{
                    D('User')->where(array('uid' => $user['uid']))->save($columns);
                }
                unset($user['pwd']);
                $ticket = ticket::create($user['uid'], $this->DEVICE_ID, true);
                $return = array(
                    'ticket' => $ticket['ticket'],
                    'v20_ticket' => D("User")->getV20Ticket($uid),
                    'user'=>$user
                );
                $this->addLog($user,$client,$registrationId);
                $this->upDevice($client,$user['uid']);
                $this->returnCode(null, $return);
            }
            //如果已经绑定账号，直接登录，返回ticket
            $user = D("User")->get_user($weixin_union_id, "union_id");
            if (empty($user) && isset($_POST['app_package_name']) && trim($_POST['app_package_name'])) {
                $app_package_name = trim($_POST['app_package_name']);
                $where_user_login = [
                    'business_type' => 'appweixin',
                    'business_otherid' => $weixin_open_id,
                    'business_pack' => $app_package_name,
                ];
                $user_login_uid = M('User_login')->where($where_user_login)->getField('uid');
                if ($user_login_uid) {
                    $user = D("User")->get_user($user_login_uid, "uid");
                }
            }
            
            if ($user) {
				if(empty($user['phone']) && empty($phone)){
					$this->returnCodeError(L_('您还没有绑定手机，请先绑定手机!'));
				}
			
                $columns = array();
                $columns['app_openid'] = $weixin_open_id;
                $columns['last_time'] = $_SERVER['REQUEST_TIME'];
                $columns['last_ip'] = get_client_ip(0);
                if($phone){
					if($phone_country_type){
						$columns['phone_country_type'] = $phone_country_type;
					}
                    $columns['phone'] = $phone;
                    $other_user = D("User")->get_user($phone,'phone');
                    if($other_user['uid'] == $user['uid']){//确保不是自己
                        unset($other_user);
                    }
                }
				if($passwd){
					$columns['pwd'] = md5($passwd);
				}
				
				//如果有头像，则保存头像
				if($avatar){
					$columns['avatar'] = D('User')->down_avatar($avatar, $weixin_union_id);
				}
				
                if(!empty($other_user)){
                    $data_use['openid'] = $user['openid'].'~no_use';
                    $data_use['union_id'] = $user['union_id'].'~no_use';
                    $data_use['wxapp_openid'] = $user['wxapp_openid'].'~no_use';
                    $data_use['app_openid'] = $user['app_openid'].'~no_use';
                    $data_use['paotui_openid'] = $user['paotui_openid'].'~no_use';
                    $data_use['group_openid'] = $user['group_openid'].'~no_use';
                    $data_use['phone'] = '';
                    $data_use['status'] = 4;
                    D('User')->where(array('uid'=>$user['uid']))->save($data_use);
                    setcookie('user_device_id', '', -1, '/');
                    setcookie('user_ticket', '', -1, '/');
                    setcookie('login_status', '', -1, '/');

                    D('User')->where(array('uid' => $other_user['uid']))->save($columns);
                    $user = $other_user;
                }
                else{
                    D('User')->where(array('uid' => $user['uid']))->save($columns);
                }
                unset($user['pwd']);
                $ticket = ticket::create($user['uid'], $this->DEVICE_ID, true);
                $return = array(
                    'ticket' => $ticket['ticket'],
                    'v20_ticket' => D("User")->getV20Ticket($uid),
                    'user'=>$user
                );
                $this->addLog($user,$client,$registrationId);
                $this->upDevice($client,$user['uid']);
                $this->returnCode(null, $return);
            }
        } else {
            if (! $token) {
                $this->returnCode('20045002');
            }
            //如果已经绑定账号，直接登录，返回ticket
            if($platform == 'facebook'){
                $user = D("User")->get_user($token, "facebook_token");
                
                if ($user) {
					if(empty($user['phone']) && empty($phone)){
						$this->returnCodeError(L_('您还没有绑定手机，请先绑定手机!'));
					}
				
                    $columns = array();
                    $columns['last_time'] = $_SERVER['REQUEST_TIME'];
                    $columns['last_ip'] = get_client_ip(0);
                    if($phone){
						if($phone_country_type){
							$columns['phone_country_type'] = $phone_country_type;
						}
                        $columns['phone'] = $phone;
                        $other_user = D("User")->get_user($phone,'phone');
                        if($other_user['uid'] == $user['uid']){//确保不是自己
                            unset($other_user);
                        }
                    }
					if($passwd){
						$columns['pwd'] = md5($passwd);
					}
				
					//如果有头像，则保存头像
					if($avatar){
						$columns['avatar'] = D('User')->down_avatar($avatar, $token);
					}
				
                    if(!empty($other_user)){
                        $data_use['openid'] = $user['openid'].'~no_use';
                        $data_use['union_id'] = $user['union_id'].'~no_use';
                        $data_use['wxapp_openid'] = $user['wxapp_openid'].'~no_use';
                        $data_use['app_openid'] = $user['app_openid'].'~no_use';
                        $data_use['paotui_openid'] = $user['paotui_openid'].'~no_use';
                        $data_use['group_openid'] = $user['group_openid'].'~no_use';
                        $data_use['phone'] = '';
                        $data_use['status'] = 4;
                        D('User')->where(array('uid'=>$user['uid']))->save($data_use);
                        setcookie('user_device_id', '', -1, '/');
                        setcookie('user_ticket', '', -1, '/');
                        setcookie('login_status', '', -1, '/');

                        D('User')->where(array('uid' => $other_user['uid']))->save($columns);
                        $user = $other_user;
                    }
                    else{
                        D('User')->where(array('uid' => $user['uid']))->save($columns);
                    }
                    unset($user['pwd']);
                    $ticket = ticket::create($user['uid'], $this->DEVICE_ID, true);
                    $return = array(
                        'ticket' => $ticket['ticket'],
                        'v20_ticket' => D("User")->getV20Ticket($uid),
                        'user'=>$user
                    );
                    $this->addLog($user,$client,$registrationId);
                    $this->upDevice($client,$user['uid']);
                    $this->returnCode(null, $return);
                }
            }
            if($platform == 'weibo'){
                $user = D("User")->get_user($token, "weibo_token");
                
                if ($user) {
					if(empty($user['phone']) && empty($phone)){
						$this->returnCodeError(L_('您还没有绑定手机，请先绑定手机!'));
					}
				
                    $columns = array();
                    $columns['last_time'] = $_SERVER['REQUEST_TIME'];
                    $columns['last_ip'] = get_client_ip(0);
                    if($phone){
						if($phone_country_type){
							$columns['phone_country_type'] = $phone_country_type;
						}
                        $columns['phone'] = $phone;
                        $other_user = D("User")->get_user($phone,'phone');
                        if($other_user['uid'] == $user['uid']){//确保不是自己
                            unset($other_user);
                        }
                    }
					if($passwd){
						$columns['pwd'] = md5($passwd);
					}
					
					//如果有头像，则保存头像
					if($avatar){
						$columns['avatar'] = D('User')->down_avatar($avatar, $token);
					}
					
                    if(!empty($other_user)){
                        $data_use['openid'] = $user['openid'].'~no_use';
                        $data_use['union_id'] = $user['union_id'].'~no_use';
                        $data_use['wxapp_openid'] = $user['wxapp_openid'].'~no_use';
                        $data_use['app_openid'] = $user['app_openid'].'~no_use';
                        $data_use['paotui_openid'] = $user['paotui_openid'].'~no_use';
                        $data_use['group_openid'] = $user['group_openid'].'~no_use';
                        $data_use['phone'] = '';
                        $data_use['status'] = 4;
                        D('User')->where(array('uid'=>$user['uid']))->save($data_use);
                        setcookie('user_device_id', '', -1, '/');
                        setcookie('user_ticket', '', -1, '/');
                        setcookie('login_status', '', -1, '/');

                        D('User')->where(array('uid' => $other_user['uid']))->save($columns);
                        $user = $other_user;
                    }
                    else{
                        D('User')->where(array('uid' => $user['uid']))->save($columns);
                    }
                    unset($user['pwd']);
                    $ticket = ticket::create($user['uid'], $this->DEVICE_ID, true);
                    $return = array(
                        'ticket' => $ticket['ticket'],
                        'v20_ticket' => D("User")->getV20Ticket($uid),
                        'user'=>$user
                    );
                    $this->addLog($user,$client,$registrationId);
                    $this->upDevice($client,$user['uid']);
                    $this->returnCode(null, $return);
                }
            }
			
			//手机号一键登录，如果已经绑定账号，直接登录，返回ticket
            if($platform == 'mobile_code'){
				
				import('@.ORG.Jpush');
				$jpush = new Jpush($this->config['weixin_push_jpush_appkey'], $this->config['weixin_push_jpush_secret']);
				
				$this->jpush_api = 'https://api.verification.jpush.cn/v1/web/loginTokenVerify';
				
				$columns = [
					'loginToken' => $token,
				];
				
				$result = $jpush->request($this->jpush_api, $columns, "POST");
				if($result['phone']){
					$prefix = '-----BEGIN PRIVATE KEY-----';
					$suffix = '-----END PRIVATE KEY-----';
					
					$encrypted = $result['phone'];
					$prikey = wordwrap($this->config['mobile_code_private_key'], 64, "\n", true);

					$key = $prefix . "\n" . $prikey . "\n" . $suffix;
					$r = openssl_private_decrypt(base64_decode($encrypted), $phone, openssl_pkey_get_private($key));
					
					if(!$phone){
						$this->returnCodeError(L_('未解密获得您的手机号，应该是管理员填错密钥了。请稍后再试'));
					}
				}else{
					$this->returnCodeError(L_('手机号一键登录解密报错') . '：' . $result['content']);
				}
				
                $user = D("User")->get_user($phone, "phone");
                if ($user) {
                    $columns = array();
                    $columns['last_time'] = $_SERVER['REQUEST_TIME'];
                    $columns['last_ip'] = get_client_ip(0);
                    D('User')->where(array('uid' => $user['uid']))->save($columns);
                    unset($user['pwd']);
                    $ticket = ticket::create($user['uid'], $this->DEVICE_ID, true);
                    $return = array(
                        'ticket' => $ticket['ticket'],
                        'v20_ticket' => D("User")->getV20Ticket($uid),
                        'user' => $user
                    );
                    $this->addLog($user, $client,$registrationId);
                    $this->upDevice($client, $user['uid']);
                    $this->returnCodeOk($return);
                }
				
				$phone_country_type = 86; //手机号一键登录，只有国内有
            }
        }
        if(empty($phone)) $this->returnCodeError(L_('您还没有绑定手机，请先绑定手机!'));
        
		$other_user = D("User")->get_user($phone,'phone');

        //未绑定，注册新账号，并登录，返回ticket
        $columns = array();
		if($weixin_open_id){
			$columns['app_openid'] = $weixin_open_id;
			if($avatar){
				$avatar = D('User')->down_avatar($avatar, $weixin_union_id);
			}
		}
		if($weixin_union_id){
			$columns['union_id'] = $weixin_union_id;
			if($avatar){
				$avatar = D('User')->down_avatar($avatar, $weixin_union_id);
			}
		}
        if($platform == 'weibo'){
            $columns['weibo_token'] = $token;
			if($avatar){
				$avatar = D('User')->down_avatar($avatar, $token);
			}
        }
        if($platform == 'facebook'){
            $columns['facebook_token'] = $token;
			if($avatar){
				$avatar = D('User')->down_avatar($avatar, $token);
			}
        }
        $columns['phone'] = $phone;
		if($passwd){
			$columns['pwd'] = md5($passwd);
		}
        $columns['sex'] = $sex;
        $columns['avatar'] = $avatar;
        $columns['last_time'] = $_SERVER['REQUEST_TIME'];
        $columns['last_ip'] = get_client_ip(0);
        $columns['phone_country_type'] = trim($phone_country_type);
        if($nickname == $this->config['site_name']){
            $columns['nickname'] = L_('昵称');
        }else{
            $columns['nickname'] = $nickname;
        }
        if(empty($other_user)){
            $columns['add_time'] = $_SERVER['REQUEST_TIME'];
            $columns['source']    = ($client == '1' ? 'iosapp' : 'androidapp');
            $columns['add_ip'] = get_client_ip(0);
            $userId = D("User")->data($columns)->add();
            D('User')->register_give_money($userId);
            //商盈通对接
            $shangyingtong = D("UserShangyingtong");
            $shangyingtong->reg($phone,$userId);
            // 同步记录到设备
            if($platform == 'wechat' && empty($user) && isset($_POST['app_package_name']) && trim($_POST['app_package_name'])) {
                $app_package_name = trim($_POST['app_package_name']);
                $db_list = D('Appapi_pack_list');
                if (isset($_POST['app_type']) && $_POST['app_type'] && 1==$_POST['app_type']) {
                    // ios
                    $where_pack_list = [
                        'package_name' => $app_package_name
                    ];
                } elseif (isset($_POST['app_type']) && $_POST['app_type'] && 2==$_POST['app_type']) {
                    // 安卓
                    $where_pack_list = [
                        'package_name2' => $app_package_name
                    ];
                } else {
                    // 或查询
                    $where_pack_list = [
                        'package_name|package_name2' => $app_package_name
                    ];
                }
                $info = $db_list->where($where_pack_list)->find();
                $data = [
                    'uid' => $userId,
                    'business_type' => 'appweixin',
                    'business_otherid' => $weixin_open_id?$weixin_open_id:'',
                    'business_id' => $weixin_union_id?$weixin_union_id:'',
                    'business_pack' => $app_package_name,
                    'other_id' => $info['weixinapp_appid']?$info['weixinapp_appid']:'',
                    'other_key' => $info['jpush_appkey']?$info['jpush_appkey']:'',
                    'other_secret' => $info['jpush_secret']?$info['jpush_secret']:'',
                    'add_time' => time(),
                ];
                M('User_login')->add($data);
            }
        } else{
            $userId = D("User")->data($columns)->where(array('uid'=>$other_user['uid']))->save();
            $userId = $other_user['uid'];
            // 同步记录到设备
            if($platform == 'wechat' && empty($user) && isset($_POST['app_package_name']) && trim($_POST['app_package_name'])) {
                $app_package_name = trim($_POST['app_package_name']);
                $db_list = D('Appapi_pack_list');
                if (isset($_POST['app_type']) && $_POST['app_type'] && 1==$_POST['app_type']) {
                    // ios
                    $where_pack_list = [
                        'package_name' => $app_package_name
                    ];
                } elseif (isset($_POST['app_type']) && $_POST['app_type'] && 2==$_POST['app_type']) {
                    // 安卓
                    $where_pack_list = [
                        'package_name2' => $app_package_name
                    ];
                } else {
                    // 或查询
                    $where_pack_list = [
                        'package_name|package_name2' => $app_package_name
                    ];
                }
                $info = $db_list->where($where_pack_list)->find();
                $data = [
                    'uid' => $userId,
                    'business_type' => 'appweixin',
                    'business_otherid' => $weixin_open_id?$weixin_open_id:'',
                    'business_id' => $weixin_union_id?$weixin_union_id:'',
                    'business_pack' => $app_package_name,
                    'other_id' => $info['weixinapp_appid']?$info['weixinapp_appid']:'',
                    'other_key' => $info['jpush_appkey']?$info['jpush_appkey']:'',
                    'other_secret' => $info['jpush_secret']?$info['jpush_secret']:'',
                ];

                $app_package_name = trim($_POST['app_package_name']);
                $where_user_login = [
                    'business_type' => 'appweixin',
                    'uid' => $userId,
                    'business_pack' => $app_package_name,
                ];
                $pack_id = M('User_login')->where($where_user_login)->getField('pack_id');
                if ($pack_id) {
                    $data['update_time'] = time();
                    M('User_login')->where($where_user_login)->save($data);
                } else {
                    $data['add_time'] = time();
                    M('User_login')->add($data);
                }
            }
        }
        if (!$userId) {
            $this->returnCode('20045004');
        }

        $user = D("User")->get_user($userId);
        $this->addLog($user,$client,$registrationId);
        $this->upDevice($client,$user['uid']);
        unset($user['pwd']);
        $ticket = ticket::create($user['uid'], $this->DEVICE_ID, true);
        $return = array(
            'ticket' => $ticket['ticket'],
            'v20_ticket' => D("User")->getV20Ticket($uid),
            'user'=>$user
        );

        $this->returnCode(null, $return);
    }
	
	
	//通过third_id和third_from获取临时表里的用户信息
	public function third_id_tmp_login(){
		$info = ticket::get($_POST['tmp_ticket'], $this->DEVICE_ID, true);
		$now_third = M('User_tmp_third')->where(['third_id' => $info['uid']])->find();
		if($now_third){
			$ticket = ticket::create($now_third['third_id'],$this->DEVICE_ID, true);
            $return = array(
                'ticket'=>  $ticket['ticket'],
                'v20_ticket' => D("User")->getV20Ticket($now_third['third_id']),
                'user'  =>  $now_third,
            );
			
			$this->returnCodeOk($return);
		}else{
			$this->returnCodeError(L_('获取用户失败'));
		}
	}
	
	//通过third_id和third_from获取临时表里的用户信息
	public function third_id_info(){
		$platform = $_POST['third_from'];
        $third_id = $_POST['third_id'];
		$condition_third = [
			'from'		=> $platform,
			'third_id' 	=> $third_id,
		];
		if(empty($platform)) $this->returnCode('20020011');
		$now_third = M('User_tmp_third')->where($condition_third)->find();
		if($now_third){
			$ticket = ticket::create($now_third['third_id'],$this->DEVICE_ID, true);
            $return = array(
                'ticket'=>  $ticket['ticket'],
                'v20_ticket' => D("User")->getV20Ticket($now_third['third_id']),
                'user'  =>  $now_third,
            );
			
			$this->returnCodeOk($return);
		}else{
			$this->returnCodeError(L_('获取用户失败'));
		}
	}
	
	//使用第三方的third_id进行登录，暂只用于 packapp
	public function third_id_login(){
		$platform = $_POST['third_from'];
        $third_id = $_POST['third_id'];
		$condition_third = [
			'from'		=> $platform,
			'third_id' 	=> $third_id,
		];
		if(empty($platform)) $this->returnCode('20020011');
		$now_third = M('User_tmp_third')->where($condition_third)->find();
		if(empty($now_third)) $this->returnCodeError(L_('第三方登录信息不存在'));
		
		$data_user = $now_third;
		
		//统一注销掉其他第三方信息
		unset($data_user['third_id'], $data_user['from'], $data_user['third_auth_time'], $data_user['weibo_token'], $data_user['facebook_token'], $data_user['alipay_uid'], $data_user['union_id'], $data_user['openid'], $data_user['wxapp_openid'], $data_user['wxapp_session_key'],$data_user['paotui_openid']);
		
		//微信小程序
		if($_POST['phone_from'] == 'wxapp'){
			//平台小程序
			if(empty($_POST['wxapp_own_mer_id']) && empty($_POST['wxapp_own_village_id'])){

                //设置独有参数
                if ($now_third['union_id']) {
                    $data_user['union_id'] = $now_third['union_id'];
                }
                if ($now_third['wxapp_openid']) {
                    $data_user['wxapp_openid'] = $now_third['wxapp_openid'];
                }
                if ($now_third['paotui_openid']) {
                    $data_user['paotui_openid'] = $now_third['paotui_openid'];
                }

				import('@.ORG.aeswxapp.wxBizDataCrypt');
			
				$session_key = $now_third['wxapp_session_key'];

                $pay_wxapp_appid = $this->config['pay_wxapp_appid'];
                if ($_POST['Device-Id'] == 'wxapp_paotui') {
                    $pay_wxapp_appid = $this->config['pay_wxapp_paotui_appid'];
                }
				$pc = new WXBizDataCrypt($pay_wxapp_appid, $session_key);
				$errCode = $pc->decryptData($_POST['encryptedData'],$_POST['iv'],$phoneData);
				if($errCode){
					$this->returnCodeError('小程序提交参数错误，错误码：'.$errCode.'。请在个人中心重新登录后重试。');
				}
				$phoneDataArr = json_decode($phoneData,true);
				$phone = $phoneDataArr['purePhoneNumber'];
				$phone_country_type = $phoneDataArr['countryCode'];
				
				$data_user['source'] = 'wxapp';
				
			}else if($_POST['wxapp_own_mer_id']){	//商家小程序
				
				$now_bind = M('Weixin_app_bind')->where(array('bind_type'=>0,'other_id'=>$_POST['wxapp_own_mer_id']))->find();
				
				import('@.ORG.aeswxapp.wxBizDataCrypt');
			
				$session_key = $now_third['wxapp_session_key'];
				$pc = new WXBizDataCrypt($now_bind['appid'], $session_key);
				$errCode = $pc->decryptData($_POST['encryptedData'], $_POST['iv'], $phoneData);
				if($errCode){
					$this->returnCodeError('小程序提交参数错误，错误码：'.$errCode.'。请在个人中心重新登录后重试。');
				}
				$phoneDataArr = json_decode($phoneData,true);
				$phone = $phoneDataArr['purePhoneNumber'];
				$phone_country_type = $phoneDataArr['countryCode'];
				
				$data_user['source'] = 'merchant_wxapp';
				
			}else{	//物业小程序
				
				$now_bind = M('Weixin_app_bind')->where(array('bind_type'=>1,'other_id'=>$_POST['wxapp_own_village_id']))->find();
				
				import('@.ORG.aeswxapp.wxBizDataCrypt');
			
				$session_key = $now_third['wxapp_session_key'];
				$pc = new WXBizDataCrypt($now_bind['appid'], $session_key);
				$errCode = $pc->decryptData($_POST['encryptedData'], $_POST['iv'], $phoneData);
				if($errCode){
					$this->returnCodeError('小程序提交参数错误，错误码：'.$errCode.'。请在个人中心重新登录后重试。');
				}
				$phoneDataArr = json_decode($phoneData,true);
				$phone = $phoneDataArr['purePhoneNumber'];
				$phone_country_type = $phoneDataArr['countryCode'];
				
				$data_user['source'] = 'village_wxapp';
			}
		}else if($_POST['phone_from'] == 'alipayapp'){

			//设置独有参数
			$data_user['alipay_uid'] = $now_third['alipay_uid'];
			
            $response = $_POST['response'];
            $response = json_decode($response,true);
            $response = $response['response'];
            $key = $this->config['alipayapp_app_aeskey'];
            if (!$key) {
                $this->returnCode(1,null,'系统提示：AES密钥未设置');
            }
            $aesKey=base64_decode($key);
            $iv = 0;
            $aesIV=base64_decode($iv);
            $aesCipher=base64_decode($response);
            $result=openssl_decrypt( $aesCipher, "AES-128-CBC", $aesKey, 1, $aesIV);
            $result = json_decode($result,true);
            if($result['code'] != '10000'){
                $this->returnCodeError('支付宝提示：'.$result['sub_msg']);
            }

			$phone = $result['mobile'];
			$phone_country_type = '';
			
			$data_user['source'] = 'alipayapp';
		}else if($_POST['phone_from'] == 'upsdk'){	//云闪付小程序

			//设置独有参数
			$data_user['upsdk_openId'] = $now_third['upsdk_openId'];
			
			//获取基础服务令牌
			$access_token_arr = D('Access_token_expires_upsdk')->get_access_token();
			if($access_token_arr['errcode']){
				$this->returnCodeError($access_token_arr['errmsg']);
			}
			
			//获取授权访问令牌
			$postArr = [
				'appId' 		=> C('config.upsdk_app_id'),
				'backendToken' 	=> $access_token_arr['access_token'],
				'code' 			=> $_POST['upsdkCode'],
				'grantType' 	=> 'authorization_code'
			];
			
			$res = httpRequest('https://open.95516.com/open/access/1.0/token', 'POST', json_encode($postArr), ["Content-Type: application/json;charset=utf8"]);
			$result = json_decode($res[1], true);
			if($result['resp'] != '00'){
                $this->returnCodeError('云闪付返回：' . $result['msg']);
            }
            
            //1. 获取用户手机号
			$postArr = [
				'appId' 		=> C('config.upsdk_app_id'),
				'backendToken' 	=> $access_token_arr['access_token'],
				'accessToken' 	=> $result['params']['accessToken'],
				'openId' 		=> $result['params']['openId'],
			];
			
			$res = httpRequest('https://open.95516.com/open/access/1.0/user.mobile', 'POST', json_encode($postArr), ["Content-Type: application/json;charset=utf8"]);
			$result = json_decode($res[1], true);
			if($result['resp'] != '00'){
                $this->returnCodeError('云闪付返回：' . $result['msg']);
            }
            import('@.ORG.aes.STD3Des');
            $STD3Des = new STD3Des();
            $desc_res = $STD3Des->decrypt3DES($result['params']['mobile'], C('config.upsdk_app_des_secret'));
            
            if(!$desc_res){
                $this->returnCodeError('云闪付返回：手机号解密失败');
            }

			$phone = $desc_res;
			$phone_country_type = '';
			
			$data_user['source'] = 'upsdk';

 

		}else{	//验证码
			$phone = strval($_POST['phone']);
			$phone_country_type = strval($_POST['phone_country_type']);
			$code = $_POST['code'];
			$phone = phone_format($phone_country_type, $phone);
		
			//判断验证码
			if($phone){
				if(empty($code)){
					$this->returnCode('20044009');
				}
				$where = array();
				$where['phone'] = $phone;
				$where['extra'] = $code;
				//$where['type']   = 1;
				$where['status'] = 0;
				$where['expire_time'] = array('gt', time());
				$result = D("App_sms_record")->where($where)->find();
				if (!$result){
				   $this->returnCode('20044009');
				}
			}
			
			if($_POST['app_type'] == 'wxapp'){	//微信小程序
				//设置独有参数
                if ($now_third['union_id']) {
                    $data_user['union_id'] = $now_third['union_id'];
                }
                if ($now_third['wxapp_openid'] && (empty($_POST['wxapp_own_mer_id']) && empty($_POST['wxapp_own_village_id']))) {
                    $data_user['wxapp_openid'] = $now_third['wxapp_openid'];
                }
                if ($now_third['paotui_openid']) {
                    $data_user['paotui_openid'] = $now_third['paotui_openid'];
                }
				
				//注册来源
				$data_user['source'] = 'wxapp';
			}else if(strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false){	//微信网页
				//设置独有参数
				$data_user['union_id'] = $now_third['union_id'];
				$data_user['openid'] = $now_third['openid'];
				
				//注册来源
				$data_user['source'] = 'weixin';
			}else if(strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false){	//支付宝网页
				//设置独有参数
				$data_user['alipay_uid'] = $now_third['alipay_uid'];
				
				//注册来源
				$data_user['source'] = 'alipay';
			}else if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'cloudpay') !== false){	//云闪付小程序
				//设置独有参数
				$data_user['upsdk_openId'] = $now_third['upsdk_openId'];
				
				//注册来源
				$data_user['source'] = 'upsdk';

            }else if($_POST['app_type'] == 'dyapp'){ //抖音小程序
                 
                //设置独有参数
                $data_user['dy_openid'] = $now_third['dy_openid'];

                if ($now_third['dy_unionid']) {
                    $data_user['dy_unionid'] = $now_third['dy_unionid'];
                }
                if ($now_third['dy_openid']) {
                    $data_user['dy_openid'] = $now_third['dy_openid'];
                } 
				
				//注册来源
				$data_user['source'] = 'dyapp'; 

			}else{
				//注册来源
				$data_user['source'] = 'wap';
			}
		}
		
		//处理用户的头像，下载至本地
		if($data_user['avatar']){
			$token = $now_third['from'] . $now_third['third_id'];
			if($now_third['from'] == 'weibo'){
				$token = $now_third['weibo_token'];
			}else if($now_third['from'] == 'facebook'){
				$token = $now_third['facebook_token'];
			}else if($now_third['from'] == 'alipay'){
				$token = $now_third['alipay_uid'];
			}else if($now_third['from'] == 'weixin'){
				$token = $now_third['union_id'] ? $now_third['union_id'] : $now_third['openid'];
			}else if($now_third['from'] == 'wxapp'){
				$token = $now_third['union_id'] ? $now_third['union_id'] : $now_third['wxapp_openid'];
			}
			$data_user['avatar'] = D('User')->down_avatar($data_user['avatar'], $token);
		}
		$data_user['phone_country_type'] = $phone_country_type;
		$data_user['phone'] = $phone;
		
		
        $database_user = D('User');
        $condition_user['phone'] = $phone;
        $res = $database_user->field('`uid`,`nickname`')->where($condition_user)->find();
        if(empty($res) && isset($data_user['openid']) && !empty($data_user['openid'])){
            $res = $database_user->where(array('openid'=>$data_user['openid']))->find();
        }
        if($res){
			//如果用户原有昵称，且是支付宝状态下，则不更新用户名。因为支付宝可能会返回昵称为“支付宝用户”
			if($res['nickname'] && $data_user['source'] == 'alipayapp'){
				unset($data_user['nickname']);
			}
			
			//如果用户原有昵称，且是云闪付状态下，则不更新用户名。因为云闪付可能会返回昵称为“云闪付用户”
			if($res['nickname'] && $data_user['source'] == 'upsdk'){
				unset($data_user['nickname']);
			}
			
			unset($data_user['source']);
            //更新用户信息，防止被覆盖,屏蔽掉字段内容为空的
            foreach ($data_user as $k => $v) {
                if (empty($v)) {
                    unset($data_user[$k]);
                }
            }
			
            $database_user->where(['uid'=> $res['uid']])->data($data_user)->save();
            $reg_result['uid'] = $res['uid'];
        }else{
    		$reg_result = D('User')->autoreg($data_user);
    		if($reg_result['error_code']){
    			$this->returnCodeError($reg_result['msg']);
    		}

            // 绑定手机号获得积分
            $score = D('User_score_list')->get_one_score(array('score_type'=>1,'uid'=>$reg_result['uid']));
            if (!$score&&$this->config['score_bind_phone']>0) { // 绑定手机只能获一次积分
                D('User')->add_score($reg_result['uid'], $this->config['score_bind_phone'],L_('绑定手机号获得X1',C('config.score_name')),0,array('score_type'=>1));
            }

        }
        D("User_spread")->updateUid($now_third['openid'], $reg_result['uid']);
		
		//商家小程序需要生成对应的数据到weixin_bind_user方便后续进行直接登录
		if($_POST['wxapp_own_mer_id']){
			$data_bind_user = array(
				'uid'			=> $reg_result['uid'],
				'mer_id'		=> $_POST['wxapp_own_mer_id'],
				'wxapp_openid' 	=> $now_third['wxapp_openid'],
				'union_id' 	    => $now_third['union_id'],
				'nickname' 	    => $now_third['nickname'],
				'sex' 			=> $now_third['sex'],
				'province' 		=> $now_third['province'],
				'city' 			=> $now_third['city'],
				'avatar' 		=> $data_user['avatar'],
				'source' 		=> 'merchant_wxapp'
			);
			M('Weixin_bind_user')->data($data_bind_user)->add();
		}
		//商家小程序完成
		
		//物业小程序需要生成对应的数据到weixin_bind_user方便后续进行直接登录
		if($_POST['wxapp_own_village_id']){
			$data_bind_user = array(
				'uid'			=> $reg_result['uid'],
				'property_id'	=> $_POST['wxapp_own_village_id'],
				'wxapp_openid' 	=> $now_third['wxapp_openid'],
				'union_id' 	    => $now_third['union_id'],
				'nickname' 	    => $now_third['nickname'],
				'sex' 			=> $now_third['sex'],
				'province' 		=> $now_third['province'],
				'city' 			=> $now_third['city'],
				'avatar' 		=> $data_user['avatar'],
				'source' 		=> 'village_wxapp'
			);
			M('Weixin_bind_user')->data($data_bind_user)->add();
		}
		//物业小程序完成
		
        // 绑定用户以往订单
        if ($data_user['openid']) {
            $type = 'openid';
            $openid = $data_user['openid'];
        }elseif ($data_user['alipay_uid']) {
            $type = 'alipay_uid';
            $openid = $data_user['alipay_uid'];
        }
        $data_user['uid'] = $reg_result['uid'];
        $bind_result = D('User')->bind_order($data_user['uid'],$type,$openid);
        fdump($bind_result,'bind_result');

        $login_result = D('User')->autologin('uid',$reg_result['uid']);
        if($login_result['error_code']){
            $this->returnCodeError($login_result['msg']);
        }else{
            $now_user = $login_result['user'];
            $ticket = ticket::create($now_user['uid'],$this->DEVICE_ID, true);
            $return = array(
                'ticket'=>  $ticket['ticket'],
                'v20_ticket' => D("User")->getV20Ticket($now_user['uid']),
                'user'  =>  $now_user,
            );
            $this->returnCodeOk($return);
        }
	}

    // 手机号绑定
    public function bind_user(){
        $registrationId = I('registrationId', '','trim');
    	if($_POST['type'] != 3){
			$code 		= I('code', 0);
	        $phone      =   I('phone');
	        $passwd     =   I('passwd');
	        $ticket     =   I('ticket', false);
	        $client     =   I('client');
	        if (!$code) {
	            $this->returnCode('20044008');
	        }
	        $where = array();
	        $where['phone'] = $phone;
	        $where['extra'] = $code;
//	        $where['type']   = 1;
	        $where['status'] = 0;
	        $where['expire_time'] = array('gt', time());
	        $result = D("App_sms_record")->where($where)->find();
	        if (! $result) {
	           $this->returnCode('20044009');
	        }
	        if ($ticket) {
	            $info = ticket::get($ticket, $this->DEVICE_ID, true);
	            if ($info) {
	                $uid = $info['uid'];
	            }
	            if(empty($phone)){
	                $this->returnCode('20042001');
	            }
	            if(empty($passwd)){
	                $this->returnCode('20042002');
	            }
	            $database_user = D('User');
	            $condition_user['phone'] = $phone;
                if($res = $database_user->field('`uid`')->where($condition_user)->find() && $res['openid']!=''){
                    $this->returnCode('20044014');
                }
	            $condition_save_user['uid'] = $uid;
	            $data_save_user['phone'] = $phone;
	            $data_save_user['pwd'] = md5($passwd);
	            if($database_user->where($condition_save_user)->data($data_save_user)->save()){
                    if($res){
                        $data_use['openid'] = $res['openid'].'~no_use';
                        $data_use['union_id'] = $res['union_id'].'~no_use';
                        $data_use['wxapp_openid'] = $res['wxapp_openid'].'~no_use';
                        $data_use['app_openid'] = $res['app_openid'].'~no_use';
                        $data_use['paotui_openid'] = $res['paotui_openid'].'~no_use';
                        $data_use['group_openid'] = $res['group_openid'].'~no_use';
                        $data_use['phone'] = '';
                        $data_use['status'] = 4;
                        $database_user->where(array('uid'=>$res['uid']))->save($data_use);
                        setcookie('user_device_id', '', -1, '/');
                        setcookie('user_ticket', '', -1, '/');
                        setcookie('login_status', '', -1, '/');
                    }
	                $user = array(
	                    'uid'    =>  $uid,
	                    'phone'    =>  $phone,
	                );
	                $this->addLog($user,$client,$registrationId);
                    //商盈通对接
                    $shangyingtong = D("UserShangyingtong");
                    $shangyingtong->reg($phone,$uid);
	                $_SESSION['user']['phone'] = $phone;
	                $arr    =   $database_user->where($condition_save_user)->find();
	                $this->returnCode(0,$arr);
	            }else{
	                $this->returnCode('20045006');
	            }
	        }else{
	            $this->returnCode('20044013');
	        }
    	}else{
			$this->weixin_bind_user();
    	}

    }
    //错误代码
    static private function ConverSmsCode($smscode) {
        $errCode = array(
            '2'    => '20060001',
            '400'  => '20060002',
            '401'  => '20060003',
            '402'  => '20060004',
            '403'  => '20060005',
            '4030' => '20060006',
            '404'  => '20060007',
            '405'  => '20060008',
            '4050' => '20060009',
            '4051' => '20060010',
            '4052' => '20060011',
            '406'  => '20060012',
            '407'  => '20060013',
            '4070' => '20060014',
            '4071' => '20060015',
            '4072' => '20060016',
            '4073' => '20060017',
            '408'  => '20060018',
            '4085' => '20060019',
            '4084' => '20060020',
        );
        return $errCode[$smscode];
    }
    //  记录手机登录，Appapi_app_login_log
    public function addLog($user = array(),$client,$registrationId=''){
        if ($client!=0) {
            $log = array(
                'client' => $client,
                'device_id' => $this->DEVICE_ID,
                'uid' => $user['uid'],
                'phone' => $user['phone'],
                'create_time' => time(),
            );
            if(empty($registrationId)){
                $registerid = D("Appapi_app_login_log")->where([
                    'client' => $client,
                    'device_id' => $this->DEVICE_ID,
                    'uid' => $user['uid'],
                    'phone' => $user['phone']
                ])->where('jpush_registrationId is not null and jpush_registrationId <> ""')->field('jpush_registrationId')->order('pigcms_id desc')->find();
                if(!empty($registerid)){
                    $registrationId=$registerid['jpush_registrationId'];
                }
            }
            $log['jpush_registrationId']=$registrationId;

            M("Appapi_app_login_log")->add($log);
            fdump_api([$_POST, $log], 'app_add_log', true);
        }
    }
    //  更新user表里的Device-Id
    public function upDevice($client,$uid){
        $userUpdata =   array(
            'device_id' =>  $this->DEVICE_ID,
            'client'    =>  $client
        );
        M('User')->where(array('uid' => $uid))->save($userUpdata);
        if (isset($_POST['app_package_name']) && trim($_POST['app_package_name'])) {
            // 上传了包名信息 单独处理记录
            $app_package_name = trim($_POST['app_package_name']);
            $db_user_deviceid = M('User_deviceid');
            $where_repeat = [
                'uid' => $uid,
                'package_name' => $app_package_name,
                'client' => $client
            ];
            $repeat_id = $db_user_deviceid->where($where_repeat)->getField('id');
            if ($repeat_id) {
                $userDeviceUpdate = [
                    'device_id' =>  $this->DEVICE_ID,
                    'client'    =>  $client,
                    'update_time' =>time()
                ];
                $db_user_deviceid->where($where_repeat)->save($userDeviceUpdate);
            } else {
                $userDeviceData = [
                    'device_id' =>  $this->DEVICE_ID,
                    'client'    =>  $client,
                    'uid'    =>  $uid,
                    'package_name'    =>  $app_package_name,
                    'add_time' =>time()
                ];
                $db_user_deviceid->add($userDeviceData);
            }
        }
    }
    //	获取用户的门禁列表
    public function door_list($uid='',$type=2){
        if($this->app_version>=200){
            if($type==1){
                return $this->door_list_($uid,$type);
            }else{
                $this->door_list_($uid,$type);die;
            }
        }
    	if($type == 1){
			if(empty($uid)){
				return	array();
			}
    	}else{
			$uid = $this->_uid;
			if(empty($uid)){
				$this->returnCode('20090009');
			}
    	}
		$where['uid']	=	$uid;
		$where['status'] =  '1';
		$aUserSelect	=	M('House_village_user_bind')->distinct(true)->field(array('village_id','floor_id','property_price','property_endtime','phone','type','open_door_day'))->where($where)->select();
        $now_user = D('User')->get_user($uid);

        $worker_info = M('House_worker')->where(array('phone'=>$now_user['phone'],'is_del'=>0,'status'=>array('neq',4)))->select();
        $aDoor = array();
        foreach($worker_info as $k=>$v){
            $condition_door['door_status']	=	1;
            $condition_door['village_id'] = $v['village_id'];
            $condition_door['floor_id'] = array(array('eq',-1),array('eq',$v['floor_id']),'or');
            $aDoorList	=	M('House_village_door')->distinct(true)->field(true)->where($condition_door)->select();

            $ban_open_status = !$v['open_door'];

            foreach($aDoorList as $kk=>$vv){
                $userWhere	=	array(
                    'user_id'	=>	$uid,
                    'door_fid'	=>	$vv['door_id'],
                );
                $aDoorFind	=	M('House_village_door_user')->field(true)->where($userWhere)->find();
                if($ban_open_status){
                    $aDoorList[$kk]['open_status']	=	3;//欠物业费
                    $aDoorList[$kk]['open_status_txt']	=	L_('您的权限已到期，请联系物业管理人员');
                }elseif(empty($aDoorFind)){
                    $aDoorList[$kk]['open_status']	=	1;
                    $aDoorList[$kk]['open_status_txt']	=	L_('可以打开');
                }else if($aDoorFind['status'] == 1){
                    if($aDoorFind == 0 || time()>$aDoorFind['end_time']){
                        $aDoorList[$kk]['open_status']	=	1;	//允许使用
                        $aDoorList[$kk]['open_status_txt']	=	L_('可以打开');
                    }else{
                        $aDoorList[$kk]['open_status']	=	2;	//时间过期
                        $aDoorList[$kk]['open_status_txt']	=	L_('已过期，请联系物业');
                    }
                }else{
                    $aDoorList[$kk]['open_status']	=	0;		//禁止使用
                    $aDoorList[$kk]['open_status_txt']	=	L_('您没有权限开门，请联系物业');		//禁止使用
                }
            }
            //  $aDoorList[$kk]['open_status']	=	1;
            $aSelect[]	=	$aDoorList;

        }

        if($aSelect){
            foreach($aSelect as $k=>$v){
                if(empty($v)){
                    continue;
                }
                foreach($v as $kk=> $vv){
                    if($vv['floor_id'] != "-1"){
                        $aFloor	=	M('House_village_floor')->field(array('floor_name','floor_layer'))->where(array('floor_id'=>$vv['floor_id']))->find();
                        $vv['floor_name']	=	strval($aFloor['floor_name']);
                        $vv['floor_layer']	=	strval($aFloor['floor_layer']);
                    }else{
                        $vv['floor_name']	=	$this->config['house_name'] ? $this->config['house_name'] : L_('小区');
                        $vv['floor_layer']	=	L_('大门');
                    }
                    $aDoor[]	=	isset($vv)?$vv:array();
                }
            }
        }
        if($type == 1){
            return	$aDoor;
        }else{
            if(empty($aDoor)){
                $aDoor	=	array();
            }else  if(!empty($aDoor)){
                $aDoor = $this->wxapp_door($aDoor);
                $this->returnCode(0,$aDoor);
            }
        }

		if($type	==	1){
			if(empty($aUserSelect)){
				return	array();
			}
		}else{
			if(empty($aUserSelect)){
				$this->returnCode(0,array());
			}
		}
		foreach($aUserSelect as $k=>$v){
			$condition_door['door_status']	=	1;
			$condition_door['village_id'] = $v['village_id'];
			$condition_door['floor_id'] = array(array('eq',-1),array('eq',$v['floor_id']),'or');
			$aDoorList	=	M('House_village_door')->distinct(true)->field(true)->where($condition_door)->select();
            if($v['type']==4){
                //工作人员 不跟状态有关，production
                $worker_info = M('House_worker')->where(array('village_id'=>$v['village_id'],'is_del'=>0,'phone'=>$v['phone']))->find();
                if($worker_info){
                    $ban_open_status = $worker_info['open_door'];
                }else{
                    $ban_open_status = true;
                }
            }else{
                $now_house = M('House_village')->where(array('village_id'=>$v['village_id']))->find();
                if(isset($v['open_door_day']) && $v['open_door_day']>0){
                    $now_house['owe_property_open_door_day']=$v['open_door_day'];
                }
                if(!$v['property_endtime']|| $now_house['owe_property_open_door']){
                    $ban_open_status = false;
                }else{

                    $owe_property_days = (strtotime(date('y-m-d',time()))-strtotime(date('y-m-d',$v['property_endtime'])))/86400;
                   //$ban_open_status = (!$now_house['owe_property_open_door'] &&(time()>$v['property_endtime'])  || ($now_house['owe_property_open_door_day']!=0 && $owe_property_days>0 && $owe_property_days > $now_house['owe_property_open_door_day'] ));
                    // $ban_open_status = (!$now_house['owe_property_open_door'] &&(time()>$v['property_endtime'])  || ($now_house['owe_property_open_door_day']!=0 && $owe_property_days>0 && $owe_property_days > $now_house['owe_property_open_door_day'] ));
                    $ban_open_status = true;
                    if(!$now_house['owe_property_open_door'] &&(time()>$v['property_endtime'])){
                        $ban_open_status = false;
                    }else if($now_house['owe_property_open_door_day']!=0 && $owe_property_days>0 && $owe_property_days > $now_house['owe_property_open_door_day']){
                        $ban_open_status = false;
                    }
                }
            }

			foreach($aDoorList as $kk=>$vv){
				$userWhere	=	array(
					'user_id'	=>	$uid,
					'door_fid'	=>	$vv['door_id'],
				);
				$aDoorFind	=	M('House_village_door_user')->field(true)->where($userWhere)->find();
                if($ban_open_status){
                    $aDoorList[$kk]['open_status']	=	3;//欠物业费
                    $aDoorList[$kk]['open_status_txt']	=	L_('您的权限已到期，请联系物业管理人员');
                }elseif(empty($aDoorFind)){
					$aDoorList[$kk]['open_status']	=	1;
                    $aDoorList[$kk]['open_status_txt']	=	L_('可以打开');
				}else if($aDoorFind['status'] == 1){
					if($aDoorFind == 0 || time()>$aDoorFind['end_time']){
						$aDoorList[$kk]['open_status']	=	1;	//允许使用
                        $aDoorList[$kk]['open_status_txt']	=	L_('可以打开');
                    }else{
						$aDoorList[$kk]['open_status']	=	2;	//时间过期
                        $aDoorList[$kk]['open_status_txt']	=	L_('已过期，请联系物业');
					}
				}else{
					$aDoorList[$kk]['open_status']	=	0;		//禁止使用
					$aDoorList[$kk]['open_status_txt']	=	L_('您没有权限开门，请联系物业');		//禁止使用
				}
			}
          //  $aDoorList[$kk]['open_status']	=	1;
			$aSelect[]	=	$aDoorList;
		}

        $worker_info = M('House_worker')->where(array('village_id'=>$v['village_id'],'is_del'=>0,'phone'=>$v['phone']))->find();
        foreach($worker_info as $k=>$v){
            $condition_door['door_status']	=	1;
            $condition_door['village_id'] = $v['village_id'];
            $condition_door['floor_id'] = array(array('eq',-1),array('eq',$v['floor_id']),'or');
            $aDoorList	=	M('House_village_door')->distinct(true)->field(true)->where($condition_door)->select();

            $ban_open_status = $worker_info['open_door'];

            foreach($aDoorList as $kk=>$vv){
                $userWhere	=	array(
                    'user_id'	=>	$uid,
                    'door_fid'	=>	$vv['door_id'],
                );
                $aDoorFind	=	M('House_village_door_user')->field(true)->where($userWhere)->find();
                if($ban_open_status){
                    $aDoorList[$kk]['open_status']	=	3;//欠物业费
                    $aDoorList[$kk]['open_status_txt']	=	L_('您的权限已到期，请联系物业管理人员');
                }elseif(empty($aDoorFind)){
                    $aDoorList[$kk]['open_status']	=	1;
                    $aDoorList[$kk]['open_status_txt']	=	L_('可以打开');
                }else if($aDoorFind['status'] == 1){
                    if($aDoorFind == 0 || time()>$aDoorFind['end_time']){
                        $aDoorList[$kk]['open_status']	=	1;	//允许使用
                        $aDoorList[$kk]['open_status_txt']	=	L_('可以打开');
                    }else{
                        $aDoorList[$kk]['open_status']	=	2;	//时间过期
                        $aDoorList[$kk]['open_status_txt']	=	L_('已过期，请联系物业');
                    }
                }else{
                    $aDoorList[$kk]['open_status']	=	0;		//禁止使用
                    $aDoorList[$kk]['open_status_txt']	=	L_('您没有权限开门，请联系物业');		//禁止使用
                }
            }
            //  $aDoorList[$kk]['open_status']	=	1;
            $aSelect[]	=	$aDoorList;

        }
        $door_device_arr = array();
		if($aSelect){
			foreach($aSelect as $k=>$v){
				if(empty($v)){
					continue;
				}
				foreach($v as $kk=> $vv){
                    if(in_array($vv['door_device_id'],$door_device_arr)){
                        continue;
                    }else{
                        $door_device_arr[] = $vv['door_device_id'];
                    }
					if($vv['floor_id'] != "-1"){
						$aFloor	=	M('House_village_floor')->field(array('floor_name','floor_layer'))->where(array('floor_id'=>$vv['floor_id']))->find();
						$vv['floor_name']	=	strval($aFloor['floor_name']);
						$vv['floor_layer']	=	strval($aFloor['floor_layer']);
					}else{
						$vv['floor_name']	=	$this->config['house_name'] ? $this->config['house_name'] : L_('小区');
						$vv['floor_layer']	=	L_('大门');
					}

					$aDoor[]	=	isset($vv)?$vv:array();
				}
			}
		}
		if($type == 1){
			return	$aDoor;
		}else{
			if(empty($aDoor)){
				$aDoor	=	array();
			}
            $aDoor = $this->wxapp_door($aDoor);

			$this->returnCode(0,$aDoor);
		}
    }


    public function door_list_($uid='',$type=2){
        if($type == 1){
            if(empty($uid)){
                return	array();
            }
        }else{
            $uid = $this->_uid;
            if(empty($uid)){
                $this->returnCode('20090009');
            }
        }

        //自动绑定一次
        $now_user = D('User')->get_user($uid);
        if($now_user['phone']){
            D('House_village_user_bind')->bind($now_user['uid'],$now_user['phone']);
        }


        $m_house_worker = M('House_worker');
        $m_house_village_door_user = M('House_village_door_user');
        $m_house_village_floor = M('House_village_floor');
        $m_house_village_door = M('House_village_door');
        $m_house_village_user_bind = M('House_village_user_bind');


        $where['a.uid'] = $uid;
        $where['a.status'] = 1;
        $where['b.status'] = 1;

        $aUserSelect = $m_house_village_user_bind->alias('a')
            ->join('LEFT JOIN pigcms_house_village as b on b.village_id=a.village_id')
            ->distinct(true)
            ->field(array('a.pigcms_id','a.parent_id','a.type','a.name','a.village_id', 'a.floor_id', 'a.property_price', 'a.property_endtime', 'a.phone', 'a.open_door_day', 'b.village_name', 'b.village_address', 'b.owe_property_open_door', 'b.owe_property_open_door_day'))
            ->where($where)
            ->order('a.type DESC')
            ->select();
        fdump($uid,'door_list_');
        fdump($aUserSelect,'door_list_',1);

        // 处理数据-层次为  小区-门禁-具体信息
        $open_list_info = array();
        // 查询出来的门禁信息存储
        $door_list_info = array();
        // 查询出来的单元信息存储
        $floor_list_info = array();
        // 查询出来的可以开门的信息存储
        $aDoor_is_open = array();
        // 查询出来开门门禁信息
        $aDoor_info = array();
        // 存储信息
        $bind_info = array();

        $aDoor = array();
        if (!empty($aUserSelect)) {
            foreach ($aUserSelect as $k => $v) {
                if(isset($v['open_door_day']) && $v['open_door_day']>0){
                    $v['owe_property_open_door_day']=$v['open_door_day'];
                }
                if ($v['type'] == 1 || $v['type'] == 2) {
                    // 家属和租客 物业费信息以业主为准
                    if ($bind_info[$v['parent_id']]) {
                        $v['property_price'] = $bind_info[$v['parent_id']]['property_price'];
                        $v['property_endtime'] = $bind_info[$v['parent_id']]['property_endtime'];
                    } else {
                        $info = $m_house_village_user_bind->where(array('pigcms_id' => $v['parent_id']))->field('property_price, property_endtime')->find();
                        $v['property_price'] = $info['property_price'];
                        $v['property_endtime'] = $info['property_endtime'];
                    }
                }
                $bind_info[$v['pigcms_id']] = $v;
                if (!$open_list_info[$v['village_id']]) {
                    // 处理小区层信息
                    $open_list_info[$v['village_id']] = array(
                        'village_name' => $v['village_name'],
                        'village_address' => $v['village_address']
                    );
                    // 放置门禁信息数组
                    $open_list_info[$v['village_id']]['door_list'] = array();
                }
                // 避免重复查询门禁信息
                if ($door_list_info && $door_list_info[$v['village_id']]) {
                    $aDoorList = $door_list_info[$v['village_id']];
                } else {
                    $condition_door['door_status'] = 1;
                    $condition_door['village_id'] = $v['village_id'];
                    $aDoorList = $m_house_village_door->group('village_id,door_device_id,floor_id')->field(true)->where($condition_door)->select();
                    $door_list_info[$v['village_id']] = $aDoorList;
                }
                // 默认存在单元门禁
                if (empty($aDoorList)) {
                    unset($open_list_info[$v['village_id']]);
                    continue;
                } else {
                    $open_list_info[$v['village_id']]['have_village_door'] = true;
                    $open_list_info[$v['village_id']]['tip_info'] = '';
                }

                if ($v['type'] == 4) {
                    // 获取工作人员信息
                    $worker_info = $m_house_worker->where(array('phone'=>$now_user['phone'],'is_del'=>0,'status'=>array('neq',4), 'village_id' => $v['village_id']))->field('village_id,wid,phone,status,open_door')->find();

                    if (!empty($worker_info)) {
                        if ($worker_info['open_door'] == 1) {
                            $work_open_status = 1;
                            $work_open_info = L_('可以打开');
                        } else {
                            $work_open_status = 0;
                            $work_open_info = L_('您没有权限开门，请联系物业');
                        }
                        foreach ($aDoorList as $kk => &$vv) {
                            if (!empty($aDoor_info) && !empty($aDoor_info[$vv['door_id']])) {
                                // 门禁信息已经查询过，直接赋予新的状态
                                $door = $aDoor_info[$vv['door_id']];
                                $door['door_end_time'] = 0;
                                $door['door_user_type'] = 3; // 工作人员
                                $aDoor_info[$vv['door_id']]['door_end_time'] = 0; // 工作人员开门权限无时间限制
                                $aDoor_info[$vv['door_id']]['door_user_type'] = 3;
                                $open_list_info[$vv['village_id']]['door_list'][$vv['door_id']] = $door;
                                continue;
                            }
                            if ($vv['floor_id'] != "-1") {
                                // 避免重复查询单元信息
                                if ($floor_list_info && $floor_list_info[$vv['floor_id']]) {
                                    $aFloor = $floor_list_info[$vv['floor_id']];
                                } else {
                                    $aFloor = $m_house_village_floor->field(array('floor_name', 'floor_layer'))->where(array('floor_id' => $vv['floor_id']))->find();
                                    $floor_list_info[$vv['floor_id']] = $aFloor;
                                }
                                $vv['floor_name'] = strval($aFloor['floor_name']);
                                $vv['floor_layer'] = strval($aFloor['floor_layer']);
                                $vv['door_type'] = 1; // 小区单元门
                            } else {
                                $vv['floor_name'] = $this->config['house_name'] ? $this->config['house_name'] : L_('小区');
                                $vv['floor_layer'] = L_('大门');
                                $vv['door_type'] = 2; // 小区大门
                            }
                            $vv['door_user_type'] = 3; // 工作人员
                            $vv['door_end_time'] = 0;
                            $vv['open_status'] = $work_open_status;
                            $vv['open_status_txt'] = $work_open_info;
                            $aDoor[] = $vv;
                            $aDoor_info[$vv['door_id']] = $vv;
                            if ($worker_info['open_door'] == 1) {
                                $aDoor_is_open[$vv['door_id']] = $vv;
                            }
                            $open_list_info[$vv['village_id']]['door_list'][$vv['door_id']] = $vv;
                            $open_list_info[$vv['village_id']]['have_floor_door'] = true;
                        }
                    }
                } else {
                    // 绑定的单元-即拥有该小区的大门和改单元门禁权限
                    $village_floor_id = $v['floor_id'];
                    // 开门到期时间
                    $property_endtime = 0;

                    // 允许开门情况下继续判断
                    if (!$v['property_endtime']) {
                        $ban_open_status = false;   // 是否禁止开门  false 可以开门  true 禁止开门
                    } else {
                        $ban_open_status = false; // 默认可以开门
                        $owe_property_days = 0;
                        if ( $v['property_endtime'] > 0) {
                            // 计算 当前时间到物业截止时间的天数
                            $owe_property_days = (strtotime(date('y-m-d', time())) - strtotime(date('y-m-d', $v['property_endtime']))) / 86400;
                        }
                        if (!$v['owe_property_open_door'] && $v['property_endtime']>0 && (time() > $v['property_endtime'])) {
                            // 判断  不能欠款延期开门 且 当前时间大于 物业截止时间  开门禁止
                            $ban_open_status = true;
                        } else if ($v['owe_property_open_door_day'] > 0 && $owe_property_days >= 0 && $owe_property_days > $v['owe_property_open_door_day']) {
                            // 判断  可以欠款延期开门 且 延期天数大于0 且  当前时间到物业截止时间的天数存在 且大于 延期天数 开门禁止
                            $ban_open_status = true;
                        }
                        // 处理下开门到期时间
                        if ($v['property_endtime'] > 0) {
                            $property_endtime = $v['property_endtime'];
                            if ($v['owe_property_open_door'] && $v['owe_property_open_door_day'] > 0) {
                                $property_endtime +=  $v['owe_property_open_door_day'] * 86400;
                            }else if($v['owe_property_open_door']){
                                $property_endtime = 0;
                            }
                        }
                    }
                    foreach ($aDoorList as $kk => &$vv) {
                        $userWhere = array(
                            'user_id' => $uid,
                            'door_fid' => $vv['door_id'],
                        );
                        if (!empty($aDoor_is_open) && !empty($aDoor_is_open[$vv['door_id']])) {

                            // 判断下哪一个有效期更长
                            if ($aDoor_is_open[$vv['door_id']]['door_end_time'] == 0 || ($property_endtime > 0 && $aDoor_is_open[$vv['door_id']]['door_end_time'] && $aDoor_is_open[$vv['door_id']]['door_end_time'] > $property_endtime)) {
                                // 如果已经存在的门禁权限更久 将跳过下面的步骤
                                continue;
                            }
                            // 门禁信息已经查询过，直接赋予新的状态
                            $vv = $aDoor_is_open[$vv['door_id']];
                        } elseif (!empty($aDoor_info) && !empty($aDoor_info[$vv['door_id']])) {
                            // 门禁信息已经查询过，直接赋予新的状态
                            $vv = $aDoor_info[$vv['door_id']];
                        } elseif ($vv['floor_id'] != "-1") {
                            // 避免重复查询单元信息
                            if ($floor_list_info && $floor_list_info[$vv['floor_id']]) {
                                $aFloor = $floor_list_info[$vv['floor_id']];
                            } else {
                                $aFloor = $m_house_village_floor->field(array('floor_name', 'floor_layer'))->where(array('floor_id' => $vv['floor_id']))->find();
                                $floor_list_info[$vv['floor_id']] = $aFloor;
                            }
                            $vv['floor_name'] = strval($aFloor['floor_name']);
                            $vv['floor_layer'] = strval($aFloor['floor_layer']);
                            $vv['door_type'] = 1; // 小区单元门
                        } else {
                            $vv['floor_name'] = $this->config['house_name'] ? $this->config['house_name'] : L_('小区');
                            $vv['floor_layer'] = L_('大门');
                            $vv['door_type'] = 2; // 小区大门
                        }
                        if ($v['type'] == 1 || $v['type'] == 2) {
                            $vv['door_user_type'] = 4; // 家属
                        } else {
                            $vv['door_user_type'] = 1; // 业主
                        }

                        $vv['door_end_time'] = $property_endtime>0?date('Y-m-d H:i', $property_endtime):0;
                        $aDoorFind = $m_house_village_door_user->field(true)->where($userWhere)->find();
                        if (!$ban_open_status && empty($aDoorFind) && $vv['floor_id'] == -1) {
                            if (empty($aDoor_is_open) || empty($aDoor_is_open[$vv['door_id']])) {
                                $vv['open_status'] = 1;
                                $vv['open_status_txt'] = L_('可以打开');
                                $aDoor[] = $vv;
                                $aDoor_info[$vv['door_id']] = $aDoor_is_open[$vv['door_id']] = $vv;
                            } else {
                                $vv['open_status'] = 1;
                                $vv['open_status_txt'] = L_('可以打开');
                                $aDoor[] = $vv;
                            }
                            $aDoor_info[$vv['door_id']]['door_user_type'] = $aDoor_is_open[$vv['door_id']]['door_user_type'] = 1;
                            $open_list_info[$vv['village_id']]['door_list'][$vv['door_id']] = $vv;
                        } elseif (!$ban_open_status && empty($aDoorFind) && ($village_floor_id==0 || $vv['floor_id'] == $village_floor_id)) {
                            if (empty($aDoor_is_open) || empty($aDoor_is_open[$vv['door_id']])) {
                                $vv['open_status'] = 1;
                                $vv['open_status_txt'] = L_('可以打开');
                                $aDoor[] = $vv;
                                $aDoor_info[$vv['door_id']] = $aDoor_is_open[$vv['door_id']] = $vv;
                            } else {
                                $vv['open_status'] = 1;
                                $vv['open_status_txt'] = L_('可以打开');
                                $aDoor[] = $vv;
                            }
                            $open_list_info[$vv['village_id']]['door_list'][$vv['door_id']] = $vv;
                        } elseif (!$ban_open_status && $aDoorFind['status'] == 1 && ($village_floor_id==0 || $vv['floor_id'] == $village_floor_id)) {
                            if ($aDoorFind == 0 || time() > $aDoorFind['end_time']) {
                                if (empty($aDoor_is_open) || empty($aDoor_is_open[$vv['door_id']])) {
                                    $vv['open_status'] = 1;
                                    $vv['open_status_txt'] = L_('可以打开');
                                    $aDoor[] = $vv;
                                    $aDoor_info[$vv['door_id']] = $aDoor_is_open[$vv['door_id']] = $vv;
                                } else {
                                    $vv['open_status'] = 1;
                                    $vv['open_status_txt'] = L_('可以打开');
                                    $aDoor[] = $vv;
                                }
                                $open_list_info[$vv['village_id']]['door_list'][$vv['door_id']] = $vv;
                            } else {
                                $vv['open_status']	=	2;	//时间过期
                                $vv['open_status_txt']	=	L_('已过期，请联系物业');
                                $aDoor[] = $vv;
                                $aDoor_info[$vv['door_id']] = $vv;
                            }
                            $open_list_info[$vv['village_id']]['door_list'][$vv['door_id']] = $vv;
                        } elseif (!$ban_open_status && $aDoorFind['status'] == 0 && ($village_floor_id==0 || $vv['floor_id'] == $village_floor_id)) {
                            $vv['open_status']	=	0;		//禁止使用
                            $vv['open_status_txt']	=	L_('您没有权限开门，请联系物业');		//禁止使用
                            $aDoor[] = $vv;
                            $aDoor_info[$vv['door_id']] = $vv;
                            $open_list_info[$vv['village_id']]['door_list'][$vv['door_id']] = $vv;
                        } elseif ($ban_open_status && ($village_floor_id==0 || $vv['floor_id'] == $village_floor_id)) {
                            $vv['open_status']	=	3;//欠物业费
                            $vv['open_status_txt']	=	L_('您的权限已到期，请联系物业管理人员');
                            $aDoor[] = $vv;
                            $aDoor_info[$vv['door_id']] = $vv;
                            $open_list_info[$vv['village_id']]['door_list'][$vv['door_id']] = $vv;
                        } elseif ($ban_open_status && $vv['floor_id'] == -1) {
                            $vv['open_status']	=	3;//欠物业费
                            $vv['open_status_txt']	=	L_('您的权限已到期，请联系物业管理人员');
                            $aDoor[] = $vv;
                            $aDoor_info[$vv['door_id']] = $vv;
                            $open_list_info[$vv['village_id']]['door_list'][$vv['door_id']] = $vv;
                        } else {
                            $vv['open_status']	=	0;		//禁止使用
                            $vv['open_status_txt']	=	L_('您没有权限开门，请联系物业');		//禁止使用
                            $aDoor[] = $vv;
                            $aDoor_info[$vv['door_id']] = $vv;
                            $open_list_info[$vv['village_id']]['door_list'][$vv['door_id']] = $vv;
                        }
                    }
                    if (empty($open_list_info[$v['village_id']]['door_list']) && $open_list_info[$v['village_id']]['have_village_door']) {
                        $open_list_info[$v['village_id']]['have_floor_door'] = false;
                        $open_list_info[$v['village_id']]['tip_info'] = L_('您入住的单元暂未添加门禁');
                    } else {
                        $open_list_info[$v['village_id']]['have_floor_door'] = true;
                    }
                }
            }
        }

        if($type == 1){;
            return	$aDoor;
        }else{
            if(empty($aDoor)){
                $aDoor	=	array();
            }else{
                $aDoor = $this->wxapp_door($aDoor);
                $aDoor = array_values($aDoor);
            }
            $this->returnCode(0,$aDoor);
        }
    }


    public function door_list_old($uid='',$type=2){
        if($type == 1){
            if(empty($uid)){
                return	array();
            }
        }else{
            $uid = $this->_uid;
            if(empty($uid)){
                $this->returnCode('20090009');
            }
        }

		//自动绑定一次
		$now_user = D('User')->get_user($uid);
		if($now_user['phone']){
			D('House_village_user_bind')->bind($now_user['uid'],$now_user['phone']);
		}

        $where['uid']	=	$uid;
		$where['status'] =  '1';
        $aUserSelect	=	M('House_village_user_bind')->distinct(true)->where($where)->field('floor_id,village_id,property_price,property_endtime,phone,type,status,open_door_day')->select();

        $aUserSelect_tmp  = array();
        foreach($aUserSelect as $key=>$value){
            $aUserSelect_tmp[$value['village_id']][] = $value;
        }

        $worker_info = M('House_worker')->where(array('phone'=>$now_user['phone'],'is_del'=>0,'status'=>array('neq',4)))->getField('village_id,wid,phone,status,open_door');

        $aDoor = array();


        //user_type 1 用户 2 工作人员 3 （1，2）合并
        // foreach ($aUserSelect as $kv=>&$item) {
        // if($worker_info[$kv]){
        // $item = array_merge($item,$worker_info[$kv]);
        // $item['user_type']=3;
        // }else{
        // $item['user_type']=1;
        // }
        // }

        foreach ($aUserSelect_tmp as $kvv=>&$items) {
            foreach($items as $kit=>&$it){

                if($worker_info[$kvv]){
                    // $aUserSelect_tmp[$kvv][$kit] = array_merge($aUserSelect_tmp[$kvv][$kit],$worker_info[$kvv]);
                    $it = array_merge($it,$worker_info[$kvv]);
                    $it['user_type']=3;
                    $items['user_type']=3;
                }else{
                    $it['user_type']=1;
                }
            }

        }


        // foreach ($worker_info as &$v) {
        // if(!$aUserSelect[$v['village_id']]){
        // $v['user_type'] = 2;
        // $aUserSelect[$v['village_id']] = $v;
        // }
        // }

        foreach ($worker_info as &$v) {
            if(!$aUserSelect_tmp[$v['village_id']]){
                $v['user_type'] = 2;
                $aUserSelect_tmp[$v['village_id']] = $v;
            }
        }

        if($aUserSelect){

            foreach ($aUserSelect as $w) {
                $village_arr[] = $w['village_id'];
                $ban_open_status = $w['open_door']?$w['open_door']:true;


                if($w['user_type']==2  ){
                    $ban_open_status = !$w['open_door'];
                } else{
                    $now_house = M('House_village')->where(array('village_id'=>$w['village_id']))->find();

                    if(isset($w['open_door_day']) && $w['open_door_day']>0){
                        $now_house['owe_property_open_door_day']=$w['open_door_day'];
                    }
                    if(!$w['property_endtime']){
                        $ban_open_status = false;
                    }else{
                        $ban_open_status = false; // 默认可以开门
                        $owe_property_days = 0;
                        if ( $w['property_endtime'] > 0) {
                            // 计算 当前时间到物业截止时间的天数
                            $owe_property_days = (strtotime(date('y-m-d', time())) - strtotime(date('y-m-d', $w['property_endtime']))) / 86400;
                        }
                        if (!$now_house['owe_property_open_door'] && $w['property_endtime']>0 && (time() > $w['property_endtime'])) {
                            // 判断  不能欠款延期开门 且 当前时间大于 物业截止时间  开门禁止
                            $ban_open_status = true;
                        } else if ($now_house['owe_property_open_door'] && $now_house['owe_property_open_door_day'] > 0 && $owe_property_days >= 0 && $owe_property_days > $now_house['owe_property_open_door_day']) {
                            // 判断  可以欠款延期开门 且 延期天数大于0 且  当前时间到物业截止时间的天数存在 且大于 延期天数 开门禁止
                            $ban_open_status = true;
                        }
                    }
                }
                $ban_open_status_arr[$w['village_id']] = $ban_open_status;
                $user_door[] = $w['floor_id'];
            }

            foreach ($aUserSelect_tmp as $w) {
                foreach ($w as $wv){

                    $wv['village_id'] && $village_arrs[] = $wv['village_id'];
                    $ban_open_status = $wv['open_door']?$wv['open_door']:true;

                    if($wv['user_type']==2  ){
                        $ban_open_status = !$wv['open_door'];
                    } else{
                        $now_house = M('House_village')->where(array('village_id'=>$wv['village_id']))->find();
                        if(isset($wv['open_door_day']) && $wv['open_door_day']>0){
                            $now_house['owe_property_open_door_day']=$w['open_door_day'];
                        }
                        if(!$wv['property_endtime']){
                            $ban_open_status = false;
                        }else{
                            $ban_open_status = false; // 默认可以开门
                            $owe_property_days = 0;
                            if ( $wv['property_endtime'] > 0) {
                                // 计算 当前时间到物业截止时间的天数
                                $owe_property_days = (strtotime(date('y-m-d', time())) - strtotime(date('y-m-d', $wv['property_endtime']))) / 86400;
                            }
                            if (!$now_house['owe_property_open_door'] && $wv['property_endtime']>0 && (time() > $wv['property_endtime'])) {
                                // 判断  不能欠款延期开门 且 当前时间大于 物业截止时间  开门禁止
                                $ban_open_status = true;
                            } else if ($now_house['owe_property_open_door'] && $now_house['owe_property_open_door_day'] > 0 && $owe_property_days >= 0 && $owe_property_days > $now_house['owe_property_open_door_day']) {
                                // 判断  可以欠款延期开门 且 延期天数大于0 且  当前时间到物业截止时间的天数存在 且大于 延期天数 开门禁止
                                $ban_open_status = true;
                            }
                        }
                    }
                    $ban_open_status_arr[$wv['village_id']] = $ban_open_status;
                    $user_door[] = $wv['floor_id'];
                }


            }

            $condition_door['door_status']	=	1;
            $condition_door['village_id']	=	array('in',$village_arr);

            $aFloor	=	M('House_village_floor')->where(array('village_id'=>$condition_door['village_id']))->getField('floor_id,floor_name,floor_layer,village_id');
            $aDoorList	=	M('House_village_door')->distinct(true)->field(true)->where($condition_door)->select();

            foreach($aDoorList as $kk=>&$vv){
                // $userWhere	=	array(
                // 'user_id'	=>	$uid,
                // 'door_fid'	=>	$vv['door_id'],
                // );
                // $aDoorFind	=	M('House_village_door_user')->field(true)->where($userWhere)->find();
                if($ban_open_status_arr[$vv['village_id']]){
                    $vv['open_status']	=	3;//欠物业费
                    $vv['open_status_txt']	=	L_('您的权限已到期，请联系物业管理人员');
                }elseif(in_array($vv['floor_id'],$user_door) || $vv['floor_id']==-1 || $aUserSelect_tmp[$vv['village_id']]['user_type']>1){
                    $vv['open_status']	=	1;
                    $vv['open_status_txt']	=	L_('可以打开');
                }else if($aDoorFind['status'] == 1){
                    if($aDoorFind == 0 || time()>$aDoorFind['end_time']){
                        $vv['open_status']	=	1;	//允许使用
                        $vv['open_status_txt']	=	L_('可以打开');
                    }else{
                        $vv['open_status']	=	2;	//时间过期
                        $vv['open_status_txt']	=	L_('已过期，请联系物业');
                    }
                }else{
                    $vv['open_status']	=	0;		//禁止使用
                    $vv['open_status_txt']	=	L_('您没有权限开门，请联系物业');		//禁止使用
                }
//                $tmp[$vv['village_id']]	=	$vv;
                if($vv['floor_id'] != "-1"){

                    $vv['floor_name']	=	strval($aFloor[$vv['floor_id']]['floor_name']);
                    $vv['floor_layer']	=	strval($aFloor[$vv['floor_id']]['floor_layer']);
                }else{
                    $vv['floor_name']	=	$this->config['house_name'] ? $this->config['house_name'] : L_('小区');
                    $vv['floor_layer']	=	L_('大门');
                }
                $aDoor[]	=	isset($vv)?$vv:array();
            }

        }



        if($type == 1){
            return	$aDoor;
        }else{
            if(empty($aDoor)){
                $aDoor	=	array();
            }else{
                $aDoor = $this->wxapp_door($aDoor);
				$aDoor = array_values($aDoor);
            }
            $this->returnCode(0,$aDoor);
        }


    }

    private function wxapp_door($aDoor){
		foreach ($aDoor as $key=>$item) {
            if($this->DEVICE_ID=='wxapp' && $item['open_status'] != 1){
                unset($aDoor[$key]);
                continue;
            }
            if(strlen($item['door_device_id']) == 17 && substr($item['door_device_id'],-5) == 'BLOCK'){
                $pwdArr = str_split($item['door_psword']);
                $new16Pwd = '';
                foreach($pwdArr as $pwdValue){
                    $new16Pwd .= '0'.$pwdValue;
                }
                $item['lockData']  = 'EEA2'.substr($item['door_device_id'],0,12).$new16Pwd.date('ymd').'ED';
            }else{
                $lockDate = S($item['door_psword'].$item['door_device_id']);
                if(empty($lockDate)) {
                    $return_msg = file_get_contents("http://o2o-service-qcloud.pigcms.com:8080/dhpkgcomm/app/wxlockData?DEVICEPSW={$item['door_psword']}&DEVICE_ID={$item['door_device_id']}");
                    $return_msg = json_decode($return_msg,true);
                    fdump($return_msg,'return_msg',true);
                    $item['lockData'] = $return_msg['lockData'];
                    S($item['door_psword'].$item['door_device_id'],$item['lockData'],86400);
                }else{
                    $item['lockData']  = $lockDate;
                }
            }
		}
		return $aDoor;
    }

    public function house_adver_click(){
        $adver_id = $_POST['adver_id'];
       // $type = $_POST['type'];
        $num_name = 'click_num';
        M('House_open_door_adver')->where(array('id'=>$adver_id))->setInc($num_name,1);
        // 处理对日期记录
        $year = date('Y');
        $month = date('m');
        $day = date('d');
        $recording = $year.'-'.$month.'-'.$day;
        $where_record = array(
            'adver_id' => $adver_id,
            'year' => $year,
            'month' => $month,
            'day' => $day,
            'recording' => $recording,
        );
        $record = M('House_open_door_adver_statistics')->where($where_record)->find();
        if ($record) {
            $data = array(
                'click_num' => intval($record['click_num']) + 1,
                'last_time' => time()
            );
            M('House_open_door_adver_statistics')->where($where_record)->data($data)->save();
        } else {
            // 第一次点击没有数据 默认点击数 1  曝光数也为 1
            $data = array(
                'click_num' => 1,
                'exposure_number' => 1,
                'adver_id' => $adver_id,
                'year' => $year,
                'month' => $month,
                'day' => $day,
                'recording' => $recording,
                'add_time' => time()
            );
            M('House_open_door_adver_statistics')->where($where_record)->data($data)->add();
        }
        $this->returnCode(0);
    }

    //开门app通知
    public function open_door_notice(){
        $uid = $this->_uid;
        if(empty($uid)){
            $this->returnCode('20000009');
        }
        $host = C('REDIS_HOST');
        $port = C('REDIS_PORT');
        $pass = C('REDIS_AUTH');
        $lock_key = $key = 'open_door_'.$uid.'_lock';
        if ($host && $port && $pass) {
            $redis = new Redis();
            $redis->pconnect($host, $port);
            $redis->auth($pass); //密码验证
            $is_lock = $redis->setnx($lock_key, 1); // 加锁
            if($is_lock == true){ // 获取锁权限
               // 第一次触发
            }else{
                fdump(['连续重复点击了开门'.__LINE__,$_POST,$_SESSION[$key]],'laya/open_door_notice_err_log',true);
                // 防止死锁
                if($redis->ttl($lock_key) == -1){
                    $redis->expire($lock_key, 1);
                }
                $this->returnCodeOk(array());
            }
        }
        if ($_SESSION[$key]) {
            fdump(['连续重复点击了开门'.__LINE__,$_POST,$_SESSION[$key]],'laya/open_door_notice_err_log',true);
            // 防止死锁
            if($redis && $redis->ttl($lock_key) == -1){
                $redis->expire($lock_key, 1);
            }
            $arr = array (
                'id' => '0',
                'name' => '',
                'ios_pic_s' => '',
                'ios_pic_b' => '',
                'android_pic' => '',
                'url' => '',
                'site_name' => $this->config['site_name'],
                'open_lottery_txt' => '',
                'voice_mp3' => '',
            );
            $this->returnCodeOk($arr);
        }
        $_SESSION[$key] = microtime(true);
        $oneday_get_num = $this->config['oneday_opendoor_lottery_num'];
        $condition_door['door_id'] = $_POST['door_id'];
        if(!$door_info = M('House_village_door')->where($condition_door)->find()){
			fdump(['对应门禁不存在'.__LINE__,$_POST],'laya/open_door_notice_err_log',true);
            // 防止死锁
            if($redis && $redis->ttl($lock_key) == -1){
                $redis->expire($lock_key, 1);
            }
            $arr = array (
                'id' => '0',
                'name' => '',
                'ios_pic_s' => '',
                'ios_pic_b' => '',
                'android_pic' => '',
                'url' => '',
                'site_name' => $this->config['site_name'],
                'open_lottery_txt' => '',
                'voice_mp3' => '',
            );
            $this->returnCodeOk($arr);
        }

        $where['uid'] = $uid;
        $where['desc'] = array('like', '%门禁开门成功随机赠送%');
        $today_zero = strtotime(date('y-m-d',$_SERVER['REQUEST_TIME']));
        $today_end = $today_zero+86399;
        $where['_string'] = "time >{$today_zero} AND time<{$today_end}";
        $oneday_open_num = M('User_score_list')->where($where)->count();
        if ($oneday_open_num <= $oneday_get_num) {
            $coupon_where['uid'] = $uid;
            $coupon_where['_string'] = "add_time >{$today_zero} AND add_time<{$today_end}";
            $oneday_open_num_coupon = M('Coupon_rand_send_hadpull')->where($where)->count();
            $oneday_open_num = max($oneday_open_num, $oneday_open_num_coupon);
        }
        fdump_api(['点击开门'.__LINE__,$uid,$oneday_get_num,$oneday_open_num,get_client_ip(0),get_client_ip(),$_SERVER,$_SESSION[$key],$key,$host,$port,$pass],'laya/open_door_notice_log',true);

        $get_score = 0;
        $success_coupon_num = 0;
        // 每日开门最多可获得积分优惠券的次数大于当前次数
        if($oneday_get_num > $oneday_open_num) {


            $min_score = $this->config['open_door_min_score'];
            $max_score = $this->config['open_door_max_score'];
            $get_score = rand($min_score, $max_score);

            if ($get_score > 0) {
                $result =  D('User')->add_score($uid, $get_score, L_('门禁开门成功随机赠送您X1个积分',$get_score));
                if (!$result['error']) {
                    $date['uid']       = $uid;
                    $date['add_time']  = $_SERVER['REQUEST_TIME'];
                    $date['num']       = 1;
                    M('Coupon_rand_send_hadpull')->add($date);
                }
            }

            $get_coupon_num = $this->config['open_door_coupon_num'];
            if ($get_coupon_num>0) {
                $coupon_list = D('System_coupon')->get_coupon_list();
                $count_coupon = M('Coupon_rand_send_hadpull')->where(array('uid' => $uid, 'coupon_id' => array('gt', 0)))->count();
                if ($get_coupon_num>$count_coupon) {
                    shuffle($coupon_list);
                    foreach ($coupon_list as $v) {
                        if ($v['rand_send'] != 1 || $v['rand_send_num'] <= 0) {
                            continue;
                        }
                        //限制随机派送仅一次
                        if (M('Coupon_rand_send_hadpull')->where(array('uid' => $uid, 'coupon_id' => $v['coupon_id']))->find()) {
                            continue;
                        }
                        $result = D('System_coupon')->send_coupon_by_id($v['coupon_id'], $uid, 1);
                        if (!$result['error']) {
                            $date['coupon_id'] = $v['coupon_id'];
                            $date['uid']       = $uid;
                            $date['add_time']  = $_SERVER['REQUEST_TIME'];
                            $date['num']       = 1;
                            M('Coupon_rand_send_hadpull')->add($date);
                            $success_coupon[] = $v['coupon_id'];
                        }

                        if (count($success_coupon) >= $get_coupon_num) {
                            break;
                        }
                    }
                    $success_coupon_num = count($success_coupon);
                }
            }
        }
        $adver_list = M('House_open_door_adver')->where(array('status'=>1,'intelligent_hardware_type'=>0))->select();
        $adver_village_list = M('Adver_village')->where(array('status'=>1,'village_id'=>$door_info['village_id']))->getField('adver_id,village_id');
        $tmp_advers = [];
        foreach ($adver_list as $ad) {
            $tmp_advers[$ad['id']] = $ad;
            if($ad['village_id']==$door_info['village_id'] ){
                $tmp_adver[] = $ad;
            }

        }

        foreach ($adver_village_list as $key=>$adv) {
            if($adv['village_id']==$door_info['village_id'] && $tmp_advers[$key] ){
                $tmp_adver[] = $tmp_advers[$key];
            }
        }

        if(!empty($tmp_adver)){
            $adver_list = $tmp_adver;
        }
        // 随机获取一张广告图片
        $adver_key = 0;
        if (!empty($adver_list)) {
            $adver_key = rand(0, count($adver_list) - 1);;
        }

        $return['id'] = $adver_list[$adver_key]['id'];
        $return['name'] = $adver_list[$adver_key]['name'];
        $return['ios_pic_s'] = replace_file_domain($adver_list[$adver_key]['android_pic']);
        $return['ios_pic_b'] = replace_file_domain($adver_list[$adver_key]['android_pic']);
        $return['android_pic'] = replace_file_domain($adver_list[$adver_key]['android_pic']);
        $return['url'] = htmlspecialchars_decode($adver_list[$adver_key]['url']);
        $return['site_name'] = $this->config['site_name'];

        if ($adver_list && $adver_list[$adver_key]) {
            $adver_id = $adver_list[$adver_key]['id'];
            // 处理对日期记录
            $year = date('Y');
            $month = date('m');
            $day = date('d');
            $recording = $year.'-'.$month.'-'.$day;
            $where_record = array(
                'adver_id' => $adver_id,
                'year' => $year,
                'month' => $month,
                'day' => $day,
                'recording' => $recording,
            );
            $record = M('House_open_door_adver_statistics')->where($where_record)->find();
            if ($record) {
                $data = array(
                    'exposure_number' => intval($record['exposure_number']) + 1,
                    'last_time' => time()
                );
                M('House_open_door_adver_statistics')->where($where_record)->data($data)->save();
            } else {
                // 第一次获取没有数据 默认点击数 0  曝光数为 1
                $data = array(
                    'click_num' => 0,
                    'exposure_number' => 1,
                    'adver_id' => $adver_id,
                    'year' => $year,
                    'month' => $month,
                    'day' => $day,
                    'recording' => $recording,
                    'add_time' => time()
                );
                M('House_open_door_adver_statistics')->where($where_record)->data($data)->add();
            }
            // 总广告添加曝光数
            M('House_open_door_adver')->where(array('id'=>$adver_id))->setInc('exposure_number',1);
        }

        $txt='';
        if($get_score>0){
            $txt = L_('送您X1个平台积分',$get_score);
        }
        if($success_coupon_num>0 && $txt != ''){
            $txt .= ','.$success_coupon_num.'张平台优惠券';
        } elseif ($success_coupon_num>0) {
            $txt = '送您'. $success_coupon_num.'张平台优惠券';
        }
        $return['open_lottery_txt'] = $txt;
        $voice_mp3 = '';
        if ($this->config['door_open_voice']) {
            $voice_mp3 = file_domain() . $this->config['door_open_voice'];
        } elseif ($this->config['door_open_voice_words']) {
            $msg = $this->config['door_open_voice_words'];
            //$voice_return = json_decode($this->voice_baidu(), true);
            //$voice_access_token = $voice_return['access_token'];
            //$voice_mp3 = 'http://tsn.baidu.com/text2audio?vol=15&tex=' . urlencode($msg) . '&lan=zh&tok=' . $voice_access_token . '&ctp=1&cuid=9B9A62EE-3EE8-45E0-9C06-2E3245AE3FF5&.mp3';
            $voice_mp3 = text2audio($msg);
        }
        $return['voice_mp3'] = $voice_mp3;
        $_SESSION[$key] = null;
        if($is_lock == true){ // 获取锁权限
            $redis->del($lock_key);
        }
        fdump_api(['点击开门结束'.__LINE__,$oneday_get_num,$oneday_open_num,$return],'laya/open_door_notice_log',true);
        $this->returnCode(0,$return);
    }

    public function modify_phone(){
        $ticket     =   I('ticket', false);
        $code		=	I('code',false);
		$phone		=	$_POST['phone'];
		$phone_country_type = $_POST['phone_country_type'];
		
		$phone = phone_format($phone_country_type, $phone);
		
		$where = array();
        $where['phone'] = $phone;
        $where['extra'] = $code;
        // $where['type']   = 3;
        $where['status'] = 0;
        $where['expire_time'] = array('gt', time());
        $result = D("App_sms_record")->where($where)->find();

        if(!$result){
            $this->returnCode('20044009');
        }
		
		if($_POST['other_id'] && $_POST['bind_id']){
			$now_user = D('User')->get_user($phone,'phone');
			$now_bind_user = D('Weixin_bind_user')->get_info($_POST['other_id'],'id',$_POST['bind_id']);
			if($now_user){
				$data_bind_user['uid'] = $now_user['uid'];
				$condition_bind_user['id'] = $_POST['bind_id'];
				if(!$now_user['avatar'] && $now_bind_user['avatar']){
					$data_bind_user['avatar'] 	= $now_bind_user['avatar'];
					$data_bind_user['city'] 	= $now_bind_user['city'];
					$data_bind_user['province'] = $now_bind_user['province'];
					$data_bind_user['sex'] 		= $now_bind_user['sex'];
				}
				if(D('Weixin_bind_user')->where($condition_bind_user)->data($data_bind_user)->save()){
					$ticket = ticket::create($now_user['uid'],'wxapp', true);
					$return = array(
						'ticket'=>	$ticket['ticket'],
                        'v20_ticket' => D("User")->getV20Ticket($now_user['uid']),
						'user'	=>	$now_user,
					);
					$this->returnCode(0,$return);
				}else{
					$this->returnCode(1001,array(),L_('绑定老用户失败'));
				}
			}else{
				$data_user = array(
					'phone' 	=> $phone,
					'phone_country_type' 	=> $phone_country_type ? $phone_country_type : '',
					'sex' 		=> $now_bind_user['sex'],
					'province' 	=> $now_bind_user['province'],
					'city' 		=> $now_bind_user['city'],
					'avatar' 	=> $now_bind_user['avatar'],
					'source' 	=> $now_bind_user['source'],
				);
                $reg_result = D('User')->autoreg($data_user,true);
				if($reg_result['error_code']){
					$this->returnCode(1001,array(),$reg_result['msg']);
				}else{
					$login_result = D('User')->autologin('uid',$reg_result['uid']);
					if($login_result['error_code']){
						$this->returnCode(1001,array(),$login_result['msg']);
					}else{
						$now_user = $login_result['user'];
						$data_bind_user['uid'] = $now_user['uid'];
						$condition_bind_user['id'] = $_POST['bind_id'];
						D('Weixin_bind_user')->where($condition_bind_user)->data($data_bind_user)->save();
						
						$ticket = ticket::create($now_user['uid'],'wxapp', true);
						$return = array(
							'ticket'=>	$ticket['ticket'],
                            'v20_ticket' => D("User")->getV20Ticket($now_user['uid']),
							'user'	=>	$now_user,
						);
						$this->returnCode(0,$return);
					}
				}
			}
		}
		
		if(!$this->_uid){
			$this->returnCode('20044013');
		}else{
			$uid = $this->_uid;
		}

        if(empty($phone)){
            $this->returnCode('20042001');
        }
        $database_user = D('User');
		$now_user = $database_user->get_user($uid);
		//判断用户是否存在手机，不存在则需要考虑合并账号
        if(!empty($now_user['phone'])){
			if($phone == $now_user['phone']){
				//$this->returnCodeError(L_('手机不能与当前一样'));
                $ticket = ticket::create($now_user['uid'], $this->DEVICE_ID, true);
				$return = array(
						'ticket'=>	$ticket['ticket'],
                        'v20_ticket' => D("User")->getV20Ticket($now_user['uid']),
						'user'	=>	$now_user,
				);
				$this->returnCode(0,$return);
			}
            $condition_user['phone'] = $phone;
            if($database_user->field(true)->where($condition_user)->find()){
                $this->returnCodeError(L_('手机号已存在'));
            }
        }else{
			$other_user = $database_user->get_user($phone,'phone');
            //如果用户手机号注册，没有微信身份，则账号不予合并
			if($other_user['status']==1){
                // if (empty($other_user['openid']) && empty($other_user['wxapp_openid']) && empty($other_user['union_id'])) {
                //     $this->returnCodeError(L_('手机号已存在'));
                // }

				$condition_user_save['uid'] = $other_user['uid'];
				$data_user_save = array(
                    'openid' => $now_user['openid'] ?: $other_user['openid'],
                    'wxapp_openid' => $now_user['wxapp_openid'] ?: $other_user['wxapp_openid'],
                    'union_id' => $now_user['union_id'] ?: $other_user['union_id'],
                    'paotui_openid' => $now_user['paotui_openid'] ?: $other_user['paotui_openid'],
                    'group_openid' => $now_user['group_openid'] ?: $other_user['group_openid'],
                    'alipay_uid' => $now_user['alipay_uid'] ?: $other_user['alipay_uid'],
                    'nickname' => $now_user['nickname'] ?: $other_user['nickname'],
					'last_time' => time(),
				);
				if($now_user['avatar']){
					$data_user_save['avatar'] = $now_user['avatar'];
				}
				if($now_user['pay_pwd']){
					$data_user_save['pay_pwd'] = $now_user['pay_pwd'];
				}
                fdump_api([$now_user, $other_user, $data_user_save], 'merge_user', true);
				if(D('User')->where($condition_user_save)->data($data_user_save)->save()){
					$condition_user_remove['uid'] = $now_user['uid'];
					$data_user_remove = array(
						'openid' => $now_user['openid'].'~no_use',
						'wxapp_openid' => $now_user['wxapp_openid'].'~no_use',
						'union_id' => $now_user['union_id'].'~no_use',
						'paotui_openid' => $now_user['paotui_openid'].'~no_use',
						'group_openid' => $now_user['group_openid'].'~no_use',
                        'alipay_uid' => $now_user['alipay_uid'].'~no_use',
						'phone'		=> '',
						'last_time' => time(),
						'status'  => 4
					);
					D('User')->where($condition_user_remove)->data($data_user_remove)->save();
                    setcookie('user_device_id', '', -1, '/');
                    setcookie('user_ticket', '', -1, '/');
                    setcookie('login_status', '', -1, '/');
					
					$now_user = D('User')->get_user($other_user['uid']);
					$ticket = ticket::create($now_user['uid'], $this->DEVICE_ID, true);
					$return = array(
						'ticket'=>	$ticket['ticket'],
                        'v20_ticket' => D("User")->getV20Ticket($now_user['uid']),
						'user'	=>	$now_user,
					);
					$this->returnCode(0,$return);
				}else{
					$this->returnCodeError(L_('修改失败，请重试'));
				}
			}
		}
		
        $condition_save_user['uid'] = $uid;
        $data_save_user['phone'] = $phone;
        $data_save_user['phone_country_type'] = $phone_country_type ? $phone_country_type : '';
        $userWorker=D('House_village_user_bind')->where(array('uid' => $uid,'type'=>4))->field('pigcms_id,village_id,phone,status')->select();
        if($database_user->where($condition_save_user)->data($data_save_user)->save()){
            D('House_village_user_bind')->where(array('uid' => $uid))->data(array('phone' => $phone))->save();
            D('House_village_user_vacancy')->where(array('uid' => $uid))->data(array('phone' => $phone))->save();
            if($userWorker){
                $house_workerDb=D('House_worker');
                foreach ($userWorker as $wvv){
                    if($wvv['village_id']>0 && !empty($wvv['phone'])){
                        $house_workerDb->where(array('village_id' => $wvv['village_id'],'phone'=>$wvv['phone']))->data(array('phone' => $phone))->save();
                    }
                }
            }
			$now_user = D('User')->get_user($uid);
			$ticket = ticket::create($uid, $this->DEVICE_ID, true);
			$return = array(
				'ticket'=>	$ticket['ticket'],
                'v20_ticket' => D("User")->getV20Ticket($uid),
				'user'	=>	$now_user,
			);
            $this->returnCode(0,$return);
        }else{
            $this->returnCode('20120006');
        }
    }
    //	微信绑定手机
    public function weixin_bind_user(){
    	$ticket     =   I('ticket', false);
    	$code		=	I('code',false);
		if(empty($ticket)){
			$this->returnCode('20044013');
		}else{
			$info = ticket::get($ticket, $this->DEVICE_ID, true);
			if ($info['uid']) {
                $uid = $info['uid'];
            }else{
				$this->returnCode('20000009');
            }
		}

		$phone		=	$_POST['phone'];
		$password	=	$_POST['passwd'];
		if(empty($phone)){
			$this->returnCode('20042001');
		}
        $database_user = D('User');
        $now_user = $database_user->where(array('uid'=>$uid))->find();
        if(!empty($now_user['phone'])){
            $condition_user['phone'] = $phone;
            if($database_user->field(true)->where($condition_user)->find()){
                $this->returnCode('10044005');
            }
        }

		$where = array();
        $where['phone'] = $phone;
        $where['extra'] = $code;
//        $where['type']   = 3;
        $where['status'] = 0;
        $where['expire_time'] = array('gt', time());
        $result = D("App_sms_record")->where($where)->find();

        if (!$result) {
           $this->returnCode('20044009');
        }
        $openidPost	=	$_POST['openid'];
        $unionidPost	=	$_POST['union_id'];
        if(!$openidPost){
			$this->returnCode('20120010');
        }
		//$database_user = D('User');
		$condition_user['phone'] = $phone;
		if(($res = $database_user->field('`uid`,`pwd`')->where($condition_user)->find())&&empty($now_user['phone'])){
			$openid = $database_user->field('`app_openid`')->where('uid='.$res['uid'])->find();
			if(!empty($openid['app_openid'])){
				$this->returnCode('20120005');
			}
            
            if($res['pwd']==''){
                $login_result = D('User')->checkin($phone,$password,'app',1);
            }else{
                $login_result = D('User')->checkin($phone,$password,'app');
            }

            if($login_result['error_code']){
				$this->returnCode($login_result['msg']);
			}else{
				if($database_user->where('`uid`='.$res['uid'])->setField('app_openid',$openidPost)){
					$database_user->where('`uid`='.$info['uid'])->setField('union_id',$unionidPost);
					$database_user->where('`uid`='.$info['uid'])->setField('app_openid',$openidPost.'~no_use');
					session_destroy();
					unset($_SESSION);
                    setcookie('user_device_id', '', -1, '/');
                    setcookie('user_ticket', '', -1, '/');
                    setcookie('login_status', '', -1, '/');
					$this->returnCode(0);
				}else{
					$this->returnCode('20120006');
				}
			}
		}
		$condition_save_user['uid'] = $uid;
		$data_save_user['phone'] = $phone;
        if(!empty($password)){
            $data_save_user['pwd'] = md5($password);
        }
		if($database_user->where($condition_save_user)->data($data_save_user)->save()){

            // 绑定手机号获得积分
            $score = D('User_score_list')->get_one_score(array('score_type'=>1,'uid'=>$uid));
            if (!$score&&$this->config['score_bind_phone']>0) { // 绑定手机只能获一次积分
                D('User')->add_score($uid, $this->config['score_bind_phone'],L_('绑定手机号获得X1',C('config.score_name')),0,array('score_type'=>1));
            }
            //商盈通对接
            $shangyingtong = D("UserShangyingtong");
            $shangyingtong->reg($phone,$uid);
			$_SESSION['user']['phone'] = $phone;
			$this->returnCode(0);
		}else{
			$this->returnCode('20120006');
		}
	}

    // 处理分享信息
    public function door_share_info() {
        $share_info = array();
        $share_info['is_owner'] = false;
        if($this->_uid){
            $uid = $this->_uid;
            // 绑定分享id
            $door_share_id = I('door_share_id', 0, 'intval');
            $share_uid = M('House_village_door_share')->where(array('door_share_id'=>$door_share_id))->getField('share_uid');
            if ($share_uid == $uid) {
                $share_info['is_owner'] = true;
            } elseif ($door_share_id > 0) {
                $this->bind_share_user($uid, $door_share_id);
            }
            // 获取绑定的信息
            $bind_share_msg = $this->bind_share_msg($uid);

            if ($bind_share_msg){
                $share_info['share_count'] = count($bind_share_msg);
                $share_info['bind_share_msg'] = $bind_share_msg;
                $now_time = time();
                $is_over = true; // 如果均过期返回true， 如果存在未过期，则返回false
                foreach ($bind_share_msg as &$val) {
                    if ($now_time < $val['share_end_time']) {
                        $is_over = false;
                    }
                }
                $share_info['is_over'] = $is_over;
            }
        } else {
            $this->returnCode('20090009');
        }
        $this->returnCode(0, $share_info);
    }

    // 解除绑定
    public function relieve_bind_share_user() {
        $door_share_id = I('door_share_id', false);
        if($this->_uid){
            $uid = $this->_uid;
            M('House_village_bind_share')->where(array('uid' => $uid, 'door_share_id'=>$door_share_id))->delete();
        }
        $this->returnCode(0);
    }

    // 解除所有过期分享绑定
    public function relieve_bind_all_share_user() {
        if($this->_uid){
            $uid = $this->_uid;
            $now_time = time();
            $share_where = array(
                'a.uid' =>$uid,
                'b.share_end_time' => array('lt', $now_time)
            );
            $share_info = M('House_village_bind_share')->alias('a')
                ->field('a.id')
                ->join('LEFT JOIN pigcms_house_village_door_share as b on b.door_share_id=a.door_share_id')
                ->where($share_where)
                ->select();
            if ($share_info) {
                $m_house_village_bind_share = M('House_village_bind_share');
                foreach ($share_info as $val) {
                    $m_house_village_bind_share->where(array('id' => $val['id']))->delete();
                }
            }
        }
        $this->returnCode(0);
    }



    // 绑定 分享门禁与用户
    private function bind_share_user($uid, $door_share_id) {
        $door_share = M('House_village_bind_share')->where(array('door_share_id'=>$door_share_id))->find();
        if ($door_share && $door_share['uid'] != $uid) {
            $this->returnCodeError('该链接已被其他人使用，请联系业主重新生成。');
        } elseif (!$door_share) {
            M('House_village_bind_share')->data(array('uid' => $uid, 'door_share_id'=>$door_share_id, 'add_time' => time()))->add();
        }
    }
    // 获取 用户的关联分享相关信息
    private function bind_share_msg($uid){
        $share_where = array(
            'a.uid' =>$uid
        );
        $share_info = M('House_village_bind_share')->alias('a')
            ->field('a.uid, b.*')
            ->join('LEFT JOIN pigcms_house_village_door_share as b on b.door_share_id=a.door_share_id')
            ->where($share_where)
            ->select();
        if (!$share_info) $share_info = array();
        return $share_info;
    }


    // 切换是否允许自动开门
    public function switch_auto_open_door() {
        $auto_door_open = false;// 默认不自动开门
        if($this->_uid){
            // 登录 做相关处理
            $uid = $this->_uid;
            $now_user=M('User')->where(array('uid'=>$uid))->field('auto_door_open')->find();
            $change = array(
                'auto_door_open' =>  ($now_user['auto_door_open']==1)?0:1
            );
            $save = M('User')->where(array('uid'=>$uid))->save($change);
            if ($save) {
                $auto_door_open = ($now_user['auto_door_open']==1)?false:true;
            } else {
                $this->returnCode(1001,array(),L_('切换失败，请重试~'));
            }
        }
        $this->returnCode(0, array('auto_door_open' => $auto_door_open));
    }


    // 获取是否允许自动开门
    public function auto_open_door_info() {
        $auto_door_open = false;// 默认不自动开门
        if($this->_uid){
            // 登录 获取相关信息
            $uid = $this->_uid;
            $now_user=M('User')->where(array('uid'=>$uid))->field('auto_door_open')->find();
            $auto_door_open = ($now_user['auto_door_open']==1)?true:false;
        }
        $this->returnCode(0, array('auto_door_open' => $auto_door_open));
    }
    // 开门语音获取
    private function voice_baidu()
    {
        static $return;

        if (empty($return)) {
            $voice_baidu = "https://openapi.baidu.com/oauth/2.0/token?grant_type=client_credentials&client_id=XU7nTWQzS9vn32bckmrhHbTu&client_secret=1375720f23eed9b7b787e728de5dd1c2";
            import('ORG.Net.Http');
            $return = Http::curlGet($voice_baidu);
        }
        return $return;
    }

    //用户注销(没有激活流程)
    public function cancel(){
        $ticket = I('ticket', false);
        if ($ticket) {
            $info = ticket::get($ticket, $this->DEVICE_ID, true);
            if ($info) {
                $uid = $info['uid'];
                $user = D("User")->get_user($uid);
                if($user['now_money'] > 0){
                    $this->returnCode('10099999', [], '当前账户有账户余额，注销失败！');
                }
                $cards = M("Card_userlist")->where(['uid'=>$uid, 'card_money'=>['gt',0]])->find();
                if($cards){
                    $this->returnCode('10099999', [], '当前账户有商家会员卡余额，注销失败！');
                }
                $data_use['status'] = 4;
                D('User')->where(array('uid'=>$uid))->save($data_use);
                setcookie('user_device_id', '', -1, '/');
                setcookie('user_ticket', '', -1, '/');
                setcookie('login_status', '', -1, '/');
                
                $this->returnCode(0);
            }
            else{
                $this->returnCode('10052001');
            }
        }
        else{
            $this->returnCode('10052001');
        }
    }
}
?>