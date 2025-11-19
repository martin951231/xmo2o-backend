<?php
/*
 * 第三方登录直连
 *
 * 
 */
class Third_loginAction extends BaseAction{
	//第三方登录直连
	public function index(){
		$login_type = $_REQUEST['login_type'];
		if(empty($login_type)){
			$this->error('第三方类别为空');
		}
		$key_key = $login_type.'_login_key';
		if(!isset($this->config[$key_key]) || $_REQUEST[$key_key] != $this->config[$key_key]){
			$this->error('密钥错误');
		}
		$referer 		= strval($_REQUEST['referer']);
		$nickname 		= strval($_REQUEST['nickname']);
		$avatar   		= strval($_REQUEST['avatar']);
		$phone    		= strval($_REQUEST['phone']);
		$pwd 	  		= strval($_REQUEST['pwd']);
		$now_money 	  	= strval($_REQUEST['money']);
		$score_count 	= strval($_REQUEST['score']);
		$third_uid  = strval($_REQUEST['third_uid']);
		$is_login		= strval($_REQUEST['is_login']);
		if($is_login == '1'){
			if(empty($nickname)){
				$this->error('昵称不能为空（nickname）');
			}
			if(empty($third_uid)){
				$this->error('第三方用户标识不能为空（third_uid）');
			}
			
			
			$now_user = D('User')->get_user($third_uid,'third_uid');
			
			$data_user['nickname'] 		= $nickname;
			$data_user['avatar'] 		= $avatar;
			$data_user['phone'] 		= $phone;
			$data_user['pwd'] 			= $pwd;
			$data_user['now_money'] 	= $now_money;
			$data_user['score_count'] 	= $score_count;
		
			if($now_user){
				$condition_user['uid'] 		= $now_user['uid'];
				D('User')->where($condition_user)->data($data_user)->save();
			}else{
				$data_user['third_uid'] 	= $third_uid;
				
				$tmp_user = D('User')->get_user($phone,'phone');
				if($tmp_user){
					$condition_user['uid'] 	= $tmp_user['uid'];
					D('User')->where($condition_user)->data($data_user)->save();
					$now_user = D('User')->get_user($tmp_user['uid'],'uid');
				}else{
					$reg_result = D('User')->autoreg($data_user);
					if($reg_result['error_code']){
						$this->error($reg_result['msg']);
					}
					if($data_user['score_count'] > 0){
						D('User')->add_score($reg_result['uid'], $data_user['score_count'], '同步积分');
					}
					$now_user = D('User')->get_user($reg_result['uid'],'uid');
				}
			}
			
			
			$login_result = D('User')->autologin('uid',$now_user['uid']);
			if($login_result['error_code']){
				$this->error($login_result['msg']);
			}else{
				$now_user = $login_result['user'];
				session('user',$now_user);
				$this->user_session = $now_user;
			}
		}
		else{
			session(null);
			session("unijia_login", '1');
		}
		
		$referer = $referer ? $referer : $this->config['site_url'];
		redirect($referer);
	}
	
	//商家直连
	public function merchant_by_shop_openid(){
		$appid = C('config.nmgzhcs_appid');
		$appsecret = C('config.nmgzhcs_appsecret');
		$shop_openid = $_POST['shop_openid'];
		$shop_sign = $_POST['sign'];
		$device_id = $_POST['device_id'];
		$params=array(
			'appid'=>$appid,
			'shop_openid'=>$shop_openid,
		);
		ksort($params);
		$paramsJoined = array();
		foreach($params as $param => $value) {
			$paramsJoined[] = "$param=$value";
		}
		$paramData = implode('&', $paramsJoined);
		
		$sign = strtoupper(md5($paramData.$appsecret));

		if($shop_sign != $sign){
			exit(json_encode(array('status'=>'error','err_msg'=>'密钥错误')));
		}
		
		$now_merchant = M('Merchant')->where(array('shop_openid'=>$shop_openid))->find();
		if(empty($now_merchant)){
			exit(json_encode(array('status'=>'error','err_msg'=>'商户不存在')));
		}
		
		
		$aTicket = ticket::create($now_merchant['mer_id'], $device_id, true);
		$ticket = $aTicket['ticket'];
		
		exit(json_encode(array('status'=>'success','device_id'=>$device_id,'ticket'=>$ticket)));
	}
	
	//店铺直连
	public function merchant_by_staff_phone(){
		$appid = C('config.nmgzhcs_appid');
		$appsecret = C('config.nmgzhcs_appsecret');
		$phone = $_POST['phone'];
		$shop_sign = $_POST['sign'];
		$device_id = $_POST['device_id'];
		$params=array(
			'appid'=>$appid,
			'phone'=>$phone,
		);
		ksort($params);
		$paramsJoined = array();
		foreach($params as $param => $value) {
			$paramsJoined[] = "$param=$value";
		}
		$paramData = implode('&', $paramsJoined);

		$sign = strtoupper(md5($paramData.$appsecret));

		if($shop_sign != $sign){
			exit(json_encode(array('status'=>'error','err_msg'=>'密钥错误')));
		}
		
		$now_staff = M('Merchant_store_staff')->where(array('tel'=>$phone))->find();
		if(empty($now_staff)){
			exit(json_encode(array('status'=>'error','err_msg'=>'店员不存在')));
		}
		
		if($_POST['client'] && $_POST['device_id']){
			$data_save = [
				'client' => trim($_POST['client']),
				'device_id' => trim($_POST['device_id']),
				'last_time' => time(),
			];
			M('Merchant_store_staff')->where(array('tel'=>$phone))->data($data_save)->save();
		}else{
			exit(json_encode(['status'=>'error','err_msg'=>'client 或 device_id 错误']));
		}
		
		$aTicket = ticket::create($now_staff['id'], $device_id, true);
		$ticket = $aTicket['ticket'];
		
		exit(json_encode(array('status'=>'success','device_id'=>$device_id,'ticket'=>$ticket)));
	}
	
	
	public function query_user_balance(){
		$unionid 		= strval($_REQUEST['unionid']);
		$login_type = $_REQUEST['login_type'];
		if(empty($login_type)){
			$return_arr = [
				'unionid' 	=> $unionid,
				'balance' 	=> 0,
				'error_code'=> 2,
				'error_info'=> '第三方类别为空',
			];
			echo json_encode($return_arr);
			die;
		}
		$key_key = $login_type.'_login_key';
		if(!isset($this->config[$key_key]) || $_REQUEST[$key_key] != $this->config[$key_key]){
			$return_arr = [
				'unionid' 	=> $unionid,
				'balance' 	=> 0,
				'error_code'=> 2,
				'error_info'=> '密钥错误',
			];
			echo json_encode($return_arr);
			die;
		}
		if($unionid){
			$now_user = D('User')->get_user($unionid,'union_id');
			
			$data_user['nickname'] 		= $nickname;
			$data_user['avatar'] 		= $avatar;
			$data_user['phone'] 		= $phone;
			$data_user['pwd'] 			= $pwd;
			$data_user['now_money'] 	= $now_money;
			$data_user['score_count'] 	= $score_count;
		
			if($now_user){
				$return_arr = [
					'unionid' 	=> $unionid,
					'balance' 	=> $now_user['now_money'],
					'error_code'=> 0,
					'error_info'=> '',
				];
			}else{
				$return_arr = [
					'unionid' 	=> $unionid,
					'balance' 	=> 0,
					'error_code'=> 1,
					'error_info'=> '用户不存在',
				];
			}
		}else{
			$return_arr = [
				'unionid' 	=> $unionid,
				'balance' 	=> 0,
				'error_code'=> 2,
				'error_info'=> '入参错误，请检查',
			];
		}
		echo json_encode($return_arr);
		die;
	}
	
	public function set_user_balance(){
		$unionid 		= strval($_REQUEST['unionid']);
		$login_type = $_REQUEST['login_type'];
		if(empty($login_type)){
			$return_arr = [
				'unionid' 	=> $unionid,
				'balance' 	=> 0,
				'error_code'=> 2,
				'error_info'=> '第三方类别为空',
			];
			echo json_encode($return_arr);
			die;
		}
		$key_key = $login_type.'_login_key';
		if(!isset($this->config[$key_key]) || $_REQUEST[$key_key] != $this->config[$key_key]){
			$return_arr = [
				'unionid' 	=> $unionid,
				'balance' 	=> 0,
				'error_code'=> 2,
				'error_info'=> '密钥错误',
			];
			echo json_encode($return_arr);
			die;
		}
		
		$unionid 	= strval($_REQUEST['unionid']);
		$type 		= intval($_REQUEST['type']);
		$balance 	= floatval($_REQUEST['balance']);
		$desc 	= strval($_REQUEST['desc']);
		if($unionid && $type && $balance && $desc){
			$now_user = D('User')->get_user($unionid,'union_id');
			if(!$now_user){
				$return_arr = [
					'unionid' 	=> $unionid,
					'balance' 	=> 0,
					'error_code'=> 1,
					'error_info'=> '',
				];
				echo json_encode($return_arr);
				die;
			}
			
			
			if($type == '1'){
				$save_result = D('User')->add_money($now_user['uid'], $balance, $desc);
			}else{
				$save_result = D('User')->user_money($now_user['uid'], $balance, $desc);
			}
			
			//更新有问题
			if($save_result['error_code']){
				$return_arr = [
					'unionid' 	=> $unionid,
					'balance' 	=> 0,
					'error_code'=> 2,
					'error_info'=> $save_result['msg'],
				];
			}else{
				$now_user = D('User')->get_user($unionid,'union_id');
				
				$return_arr = [
					'unionid' 	=> $unionid,
					'balance' 	=> $now_user['now_money'],
					'error_code'=> 0,
					'error_info'=> '设置余额成功',
				];
			}
		}else{
			$return_arr = [
				'unionid' 	=> $unionid,
				'balance' 	=> 0,
				'error_code'=> 2,
				'error_info'=> '入参错误，请检查',
			];
		}
		echo json_encode($return_arr);
		die;
	}
	public function get_wechat_accesstoken(){
		$login_type = $_REQUEST['login_type'];
		if(empty($login_type)){
			$return_arr = [
				'error_code'=> 2,
				'error_info'=> '第三方类别为空',
			];
			echo json_encode($return_arr);
			die;
		}
		$key_key = $login_type.'_login_key';
		if(!isset($this->config[$key_key]) || $_REQUEST[$key_key] != $this->config[$key_key]){
			$return_arr = [
				'error_code'=> 2,
				'error_info'=> '密钥错误',
			];
			echo json_encode($return_arr);
			die;
		}
		
		//微信授权获得access_token
		$access_token_array = D('Access_token_expires')->get_access_token();
		if ($access_token_array['errcode']) {
			$return_arr = [
				'error_code'=> 3,
				'error_info'=> '获取access_token发生错误：错误代码' . $access_token_array['errcode'] .',微信返回错误信息：' . $access_token_array['errmsg'],
			];
			echo json_encode($return_arr);
			die;
		}
		$return_arr = [
			'error_code'=> 0,
			'access_token'=> $access_token_array['access_token'],
			'expires_in'=> $access_token_array['expires_in'] - 1800,	//对外暴露减少30分钟的缓存时效
		];
		echo json_encode($return_arr);
		die;
	}
	
	public function verifyGroupOrder(){
		fdump_sql([
			file_get_contents('php://input'),
		], 'verifyGroupOrder');
		
		import('@.ORG.third_custom.baodi.baodiTicket');
		$baodiTicket = new baodiTicket();
		$result = $baodiTicket->verifyTicket();
	}

	//客户定制商家中心免登陆跳转到用户端下跑腿订单
	public function login_from_merchant()
	{
		$phone = strval($_REQUEST['phone']);
		$type = strval($_REQUEST['type']);
		$source = 'merchant_paotui';

		if (empty($phone)) {
			$this->error('手机号不能为空');
		}
		//判断手机号有对应的商家
		$merchant = D('Merchant')->where(['status' => 1, 'phone' => $phone])->find();
		if (empty($merchant)) {
			$this->error('系统错误');
		}

		$now_user = D('User')->get_user($phone, 'phone');
		$data_user['nickname'] 		= phone_show($phone);
		$data_user['avatar'] 		= '';
		$data_user['phone'] 		= $phone;
		$data_user['source'] 		= $source;

		if (empty($now_user)) {
			//查不到手机号那就注册
			$reg_result = D('User')->autoreg($data_user);
			if ($reg_result['error_code']) {
				$this->error($reg_result['msg']);
			}
			$now_user = D('User')->get_user($reg_result['uid'], 'uid');
		}

		$login_result = D('User')->autologin('uid', $now_user['uid']);
		if ($login_result['error_code']) {
			$this->error($login_result['msg']);
		} else {
			$now_user = $login_result['user'];
			session('user', $now_user);
			$this->user_session = $now_user;
			$device_id = 'packapp';
			$ticket = ticket::create($this->user_session['uid'], $device_id, true);
			setcookie('user_device_id', $device_id, 0, '/');
			setcookie('user_ticket', $ticket['ticket'], 0, '/');
			setcookie('login_status', 'login', 0, '/');
			setcookie('now_user', json_encode($now_user, JSON_UNESCAPED_UNICODE), 0, '/');
			$v20Ticket = Token::createToken($this->user_session['uid']);
			setcookie('v20_ticket', $v20Ticket, 0, '/');
		}

		if($type == 'paotui'){
			$referer = get_base_url("pages/runErrands/confirm?from=merchant&mer_id=" . $merchant['mer_id']."&tm=".uniqid());
		}else if($type == 'paotui_orders'){
			$referer = get_base_url("pages/my/my_order?type=service");
		}else{
			$referer = get_base_url();
		}
		redirect($referer);
	}
}
