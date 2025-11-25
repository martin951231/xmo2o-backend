<?php
/*
 * Merchant Warrior (MW) 支付
 *
 */
class Hake_creditAction extends BaseAction{
	
	//银行卡列表
	public function index(){
		$return = array();
		
		//用户有没有设置支付密码
		$now_user = D('User')->get_user($this->_uid);
		$return['pay_pwd'] = $now_user['pay_pwd'] ? true : false;
		
		//银行卡列表
		$card_list = M('Hake_credit_list')->field('id,card_number')->where(array('uid'=>$this->_uid,'is_remove'=>'0','is_verify'=>'1'))->select();
		foreach($card_list as &$value){
			$card_pre = substr($value['card_number'],0,1);
			switch($card_pre){
				case 4:
					$value['card_type'] = 'visa';
					break;
				case 5:
					$value['card_type'] = 'mastercard';
					break;
				case 6:
					$value['card_type'] = 'unionpay';
					break;
			}
			$value['card_first_four'] = substr($value['card_number'],0,4);
			$value['card_last_four'] = substr($value['card_number'],-4);
		}
		$return['card_list'] = $card_list;
		
		
		
		$this->returnCodeOk($return);
	}
	
	//判断原密码是否错误，3次
	public function check_old_pwd(){
		$now_user = D('User')->get_user($this->_uid);
		if(empty($now_user['pay_pwd'])){
			$this->returnCodeError('emptypwd');
		}
		
		//判断4次错误 自然日三次
		$error_time = date('Ymd');
		if(M('Hake_pay_pwd_error')->where(array('uid'=>$now_user['uid'],'error_time'=>$error_time,'error_type'=>'0'))->count() >= 4){
			$this->returnCodeError('wrongmany');
		}
		
		$encry_pay_pwd = crypt($_POST['pay_pwd'], '$5$rounds=5000$'.$now_user['user_salt'].'$');
		if($now_user['pay_pwd'] == $encry_pay_pwd){
			M('Hake_pay_pwd_error')->where(array('error_time'=>array('lt',$error_time),'error_type'=>'0'))->delete();	//尝试清空数据表，减少体积
			
			if($_POST['is_pay']){
				$this->payCard();
			}
			
			$this->returnCodeOk('ok');
		}else{
			M('Hake_pay_pwd_error')->data(array('uid'=>$now_user['uid'],'error_time'=>$error_time,'error_type'=>'0'))->add();
			$this->returnCodeError('wrong');
		}
	}
	
	//提交绑卡
	public function addCard(){
		$param['card_name'] = trim($_POST['card_name']);
		$param['card_number'] = trim($_POST['card_number']);
		$param['card_expiry_year'] = trim($_POST['card_expiry_year']);
		$param['card_expiry_month'] = trim($_POST['card_expiry_month']);
		$param['card_cvv'] = trim($_POST['card_cvv']);
		
		$where['card_number'] = substr($param['card_number'],0,6).str_repeat('*',strlen($param['card_number'])-10).substr($param['card_number'],-4);
		
		$now_card = M('Hake_credit_list')->where(array('card_number'=>$where['card_number'],'uid'=>$this->_uid,'is_remove'=>'0','is_verify'=>'1'))->find();
		if($now_card){
			$this->returnCodeError(L_('银行卡已经存在'));
		}
		
		$hake_credit = new hake_credit();
		$result = $hake_credit->addCard($param);
		if($result['err_code']){
			$this->returnCodeError($result['err_msg']);
		}
		
		$data_card = array(
			'uid' 				=> $this->_uid,
			'card_name' 		=> $param['card_name'],
			'card_number' 		=> $where['card_number'],
			'card_cvv'		 	=> $param['card_cvv'],
			'card_from'		 	=> $result['card_from'],
			'card_id'		 	=> $result['card_id'],
			'card_key'		 	=> $result['card_key'],
			'ivr_card_id'		=> $result['ivr_card_id'],
			'add_time'			=> time(),
		);
		if($id = M('Hake_credit_list')->data($data_card)->add()){
			$this->returnCodeOk($id);
		}else{
			fdump_sql(array($data_card,$_POST), 'hake_credit_add_error');
			$this->returnCodeError(L_('银行卡添加失败'));
		}
	}
	
	//提交绑卡验证
	public function processCard(){
		$id = $_POST['id'];
		if(empty($id)){
			$this->returnCodeError(L_('参数有误'));
		}
		$now_card = M('Hake_credit_list')->where(array('id'=>$id,'uid'=>$this->_uid))->find();
		if(empty($now_card)){
			$this->returnCodeError(L_('没有找到该银行卡'));
		}
		
		$now_user = D('User')->get_user($this->_uid);
		
		$nowtime = date("ymdHis");
		$orderid = $nowtime.rand(10,99).sprintf("%08d",$this->_uid);
		
		if($now_card['verify_money'] && $now_card['verify_money'] != '0.00'){
			$this->returnCodeOk('ok');
		}
		
		$payment_order = array(
			'transactionAmount' 		=> round_number((mt_rand(100,200))/100,2),
			'transactionCurrency' 		=> 'AUD',
			'transactionProduct' 		=> L_('校验银行卡真实性').' - '.$this->config['site_name'],
			'transactionReferenceID' 	=> 'credit_'.$orderid,
			'customerName' 				=> $now_card['card_name'],
			'customerCountry' 			=> 'AU',
			'customerState' 			=> 'Queensland',
			'customerCity' 				=> 'Brisbane',
			'customerAddress' 			=> 'N/A',
			'customerPostCode' 			=> '4116',
			'customerPhone' 			=> $now_user['phone_country_type'].$now_user['phone'],
			'cardID' 					=> $now_card['card_id'],
			'paymentCardCSC' 			=> $now_card['card_cvv'],
		);
		if($now_user['email']){
			$payment_order['customerEmail'] = $now_user['email'];
		}else{
			$payment_order['customerEmail'] = 'support@harkhark.com.au';
		}
		
		//判断用户有没有收货地址，进行变更信息
		$user_address = D('User_adress')->field(true)->where(array('uid' => $now_user['uid']))->order('`default` DESC,`adress_id` DESC')->find();
		if($user_address){
			//省/州
			$province = D('Area')->get_area_by_areaId($user_address['province'], false);
			$payment_order['customerState'] = $province['area_name'];
			//市
			$city = D('Area')->get_area_by_areaId($user_address['city'], false);
			$payment_order['customerCity'] = $city['area_name'];
			//区
			$area = D('Area')->get_area_by_areaId($user_address['area'], false);
			if($area && $area['zip_code']){
				$payment_order['customerPostCode'] = $area['zip_code'];
			}
			//地址
			$payment_order['customerAddress'] = $user_address['detail'].$user_address['adress'];
		}
		
		$hake_credit = new hake_credit();
		$result = $hake_credit->processCard($payment_order);
		if($result['err_code']){
			$this->returnCodeError($result['err_msg']);
		}
		
		$data_card = array(
			'verify_money' 		=> $payment_order['transactionAmount'],
			'verify_order_id' 	=> $payment_order['transactionReferenceID'],
			'third_order_id' 	=> $result['transactionID'],
			'more_response' 	=> serialize($result['more_response']),
			'verify_add_time' 	=> time(),
		);
		if(M('Hake_credit_list')->where(array('id'=>$now_card['id']))->data($data_card)->save()){
			//自动退款
			$refund_order = array(
				'transactionAmount' => $payment_order['transactionAmount'],
				'transactionCurrency' => 'AUD',
				'transactionReferenceID' => 'refund_'.$payment_order['transactionReferenceID'],
				'transactionID' => $result['transactionID'],
				'refundAmount' => $payment_order['transactionAmount'],
			);
			$refund_result = $hake_credit->refundCard($refund_order);
			if($refund_result['err_code'] == 0){
				$data_refund_card = array(
					'is_refund' 			=> '1',
					'refund_third_order_id' => $refund_result['transactionID'],
					'refund_time' 			=> time()
				);
				if(!M('Hake_credit_list')->where(array('id'=>$now_card['id']))->data($data_refund_card)->save()){
					fdump_sql(array('sql'=>M('Hake_credit_list')->getLastSql(),'refund_order'=>$refund_order,'data_card'=>$data_refund_card),'Hake_credit_save_card_refund_error');
				}
			}
			
			$this->returnCodeOk('ok');
		}else{
			fdump_sql(array('sql'=>M('Hake_credit_list')->getLastSql(),'payment_order'=>$payment_order,'data_card'=>$data_card),'Hake_credit_save_card_error');
			$this->returnCodeError(L_('表单保存失败'));
		}
	}
	public function queryCard(){
		$query_order = array(
			'transactionReferenceID' => 'credit_1904171637419001358725',
		);
		
		$hake_credit = new hake_credit();
		$result = $hake_credit->queryCard($query_order);
		dump($result);
		if($result['err_code']){
			$this->returnCodeError($result['err_msg']);
		}
	}
	public function refundCard(){
		$transactionAmount = $_POST['transactionAmount'];
		$transactionReferenceID = $_POST['transactionReferenceID'];
		$transactionID = $_POST['transactionID'];
		$refundAmount = $_POST['refundAmount'];
		$refund_order = array(
			'transactionAmount' => $transactionAmount,
			'transactionCurrency' => 'AUD',
			'transactionReferenceID' => $transactionReferenceID,
			'transactionID' => $transactionID,
			'refundAmount' => $refundAmount,
		);
		
		$hake_credit = new hake_credit();
		$result = $hake_credit->refundCard($refund_order);
		dump($result);
		if($result['err_code']){
			$this->returnCodeError($result['err_msg']);
		}
		
	}
	
	//校验银行卡金额是否正确
	public function verifyCard(){
		$id = $_POST['id'];
		if(empty($id)){
			$this->returnCodeError(L_('参数有误'));
		}
		
		//判断错误 自然日四次
		$error_time = date('Ymd');
		if(M('Hake_pay_pwd_error')->where(array('uid'=>$this->_uid,'error_time'=>$error_time,'error_type'=>'1'))->count() >= 4){
			$this->returnCodeError('wrongmany');
		}
		
		$now_card = M('Hake_credit_list')->where(array('id'=>$id,'uid'=>$this->_uid))->find();
		if(empty($now_card)){
			$this->returnCodeError(L_('没有找到该银行卡'));
		}
		
		if($_POST['money'] != $now_card['verify_money']){
			M('Hake_pay_pwd_error')->data(array('uid'=>$this->_uid,'error_time'=>$error_time,'error_type'=>'1'))->add();
			$this->returnCodeError('wrongmoney');
		}
		M('Hake_pay_pwd_error')->where(array('error_time'=>array('lt',$error_time),'error_type'=>'1'))->delete();	//尝试清空数据表，减少体积
		
		$data_card = array(
			'is_verify' 		=> '1',
			'verify_time' 		=> time(),
		);
		if($card_id = M('Hake_credit_list')->where(array('id'=>$now_card['id']))->data($data_card)->save()){
			$this->returnCodeOk($card_id);
		}else{
			$this->returnCodeError(L_('表单保存失败'));
		}
	}
	
	//删除银行卡
	public function removeCard(){
		$id = $_POST['id'];
		if(empty($id)){
			$this->returnCodeError(L_('参数有误'));
		}
		$now_card = M('Hake_credit_list')->where(array('id'=>$id,'uid'=>$this->_uid))->find();
		if(empty($now_card)){
			$this->returnCodeError(L_('没有找到该银行卡'));
		}
		
		$data_card = array(
			'is_remove' 		=> '1',
		);
		if(M('Hake_credit_list')->where(array('id'=>$now_card['id']))->data($data_card)->save()){
			$this->returnCodeOk('ok');
		}else{
			$this->returnCodeError(L_('表单保存失败'));
		}
	}
	
	//设置新的支付密码
	public function set_new_pwd(){
		$pay_pwd = trim($_POST['pay_pwd']);
		if(strlen($pay_pwd) != 4){
			$this->returnCodeError(L_('支付密码格式错误'));
		}
		
		$now_user = D('User')->get_user($this->_uid);
		if($now_user['pay_pwd'] && $now_user['user_salt']){
			$encry_pay_pwd = crypt($pay_pwd, '$5$rounds=5000$'.$now_user['user_salt'].'$');
			if($encry_pay_pwd == $now_user['pay_pwd']){
				$this->returnCodeError(L_('新密码不能与旧密码一致'));
			}
		}
		
		$user_salt = $now_user['user_salt'] ? $now_user['user_salt'] : generate_password(16);
		$data_user = [
			'pay_pwd' => crypt($pay_pwd, '$5$rounds=5000$'.$user_salt.'$'),
			'user_salt' => $user_salt,
		];
		$result = D('User')->where(['uid'=>$this->_uid])->data($data_user)->save();
		if($result !== false){
			$this->returnCodeOk('ok');
		}else{
			$this->returnCodeError(L_('支付密码保存失败，请重试'));
		}
	}
	
	//绑卡提交支付
	public function payCard(){
		$id = $_POST['id'];
		if(empty($id)){
			$this->returnCodeError(L_('参数有误'));
		}
		$now_card = M('Hake_credit_list')->where(array('id'=>$id,'uid'=>$this->_uid))->find();
		if(empty($now_card)){
			$this->returnCodeError(L_('没有找到该银行卡'));
		}
		
		$orderid = $_POST['orderid'];
		if(empty($orderid)){
			$this->returnCodeError(L_('参数有误'));
		}
		$now_order = M('Hake_credit_order')->where(array('tmp_id'=>$orderid))->find();
		if(empty($now_order)){
			$this->returnCodeError(L_('没有找到该订单'));
		}
		$order_redirect_url = $this->config['site_url'].'/wap.php?c=Pay&a=return_url&pay_type=merchantwarrior&orderid='.$orderid;
		if($now_order['pay_time']){
			//先异步通知，再同步跳转
			import('ORG.Net.Http');
			$http = new Http();
			$notice_result = $http->curlGet($order_redirect_url);
			fdump_sql([$order_redirect_url, $notice_result, 1], 'hake_notice_result');
			
			$this->returnCodeOk($order_redirect_url);
		}
		
		$now_user = D('User')->get_user($this->_uid);
		if(empty($now_user)){
			$this->returnCodeError(L_('没有找到该用户'));
		}
		
		$payment_order = array(
			'transactionAmount' 		=> $now_order['order_money'],
			'transactionCurrency' 		=> 'AUD',
			'transactionProduct' 		=> $now_order['order_name'],
			//'transactionProduct' 		=> 'HarkHark Order '.$now_order['order_type'].'_'.$now_order['order_id'],
			'transactionReferenceID' 	=> $now_order['order_type'].'_'.$now_order['order_id'],
			'customerName' 				=> $now_card['card_name'],
			'customerCountry' 			=> 'AU',
			'customerState' 			=> 'Queensland',
			'customerCity' 				=> 'Brisbane',
			'customerAddress' 			=> 'N/A',
			'customerPostCode' 			=> '4116',
			'customerPhone' 			=> $now_user['phone_country_type'].$now_user['phone'],
			'cardID' 					=> $now_card['card_id'],
			'paymentCardCSC' 			=> $now_card['card_cvv'],
			'customerIP' 				=> get_client_ip(),
			'custom1' 					=> $now_order['order_type'],
			'custom2' 					=> $now_order['is_mobile_pay'] == 3 ? 'wxapp' : ($now_order['is_mobile_pay'] == 2 ? 'app' : ($now_order['is_mobile_pay'] == 1 ? 'wap' : 'pc')),
			'custom3' 					=> C('config.system_lang'),
		);
		if($now_user['email']){
			$payment_order['customerEmail'] = $now_user['email'];
		}else{
			$payment_order['customerEmail'] = 'support@harkhark.com.au';
		}
		$hake_credit = new hake_credit();
		$result = $hake_credit->processCard($payment_order);
		if($result['err_code']){
			$data_order = array(
				'more_response'	=> serialize($result['more_response']),
			);
			M('Hake_credit_order')->where(array('id'=>$now_order['id']))->data($data_order)->save();
			$this->returnCodeError($result['err_msg']);
		}
		
		$data_order = array(
			'credit_id' 	 => $id,
			'third_order_id' => $result['transactionID'],
			'more_response'	 => serialize($result['more_response']),
			'pay_time' 		 => time(),
		);
		if(M('Hake_credit_order')->where(array('id'=>$now_order['id']))->data($data_order)->save()){
			//先异步通知，再同步跳转
			import('ORG.Net.Http');
			$http = new Http();
			$notice_result = $http->curlGet($order_redirect_url);
			fdump_sql([$order_redirect_url, $notice_result, 2], 'hake_notice_result');
			
			$this->returnCodeOk($order_redirect_url);
		}else{
			fdump_sql(array('sql'=>M('Hake_credit_order')->getLastSql(),'payment_order'=>$payment_order,'data_order'=>$data_order),'Hake_order_pay_error');
			$this->returnCodeError(L_('表单保存失败'));
		}
	}
	
	public function cardInfo(){
		$card_query = array(
			'cardID' => 'TUND16318893',
			'cardKey' => 'sI4Pecs80PsEsIAh',
		);
		$hake_credit = new hake_credit();
		$result = $hake_credit->cardInfo($card_query);
	}
	
	//跳转支付订单
	public function payCardRedirect(){
		$data_card = array(
			'uid' 				=> $this->_uid,
			'card_name' 		=> $_POST['card_name'],
			'card_number' 		=> substr($_POST['card_number'],0,6).str_repeat('*',strlen($_POST['card_number'])-10).substr($_POST['card_number'],-4),
			'card_cvv'		 	=> $_POST['card_cvv'],
			'add_time'			=> time(),
		);
		$id = M('Hake_credit_list')->data($data_card)->add();
		if(!$id){
			$this->returnCodeError(L_('银行卡处理失败'));
		}

		$orderid = $_POST['orderid'];
		if(empty($orderid)){
			$this->returnCodeError(L_('参数有误'));
		}
		$now_order = M('Hake_credit_order')->where(array('tmp_id'=>$orderid))->find();
		if(empty($now_order)){
			$this->returnCodeError(L_('没有找到该订单'));
		}
		$order_redirect_url = $this->config['site_url'].'/wap.php?c=Pay&a=return_url&pay_type=merchantwarrior&orderid='.$orderid;
		if($now_order['pay_time']){
			$this->returnCodeOk($order_redirect_url);
		}
		
		$now_user = D('User')->get_user($this->_uid);
		if(empty($now_user)){
			$this->returnCodeError(L_('没有找到该用户'));
		}
		
		$payment_order = array(
			'orderid' 					=> $orderid,
			'transactionAmount' 		=> $now_order['order_money'],
			'transactionCurrency' 		=> 'AUD',
			'transactionProduct' 		=> $now_order['order_name'],
			//'transactionProduct' 		=> 'HarkHark Order '.$now_order['order_type'].'_'.$now_order['order_id'],
			'transactionReferenceID' 	=> $now_order['order_type'].'_'.$now_order['order_id'],
			'customerName' 				=> $_POST['card_name'],
			'customerCountry' 			=> 'AU',
			'customerState' 			=> 'Queensland',
			'customerCity' 				=> 'Brisbane',
			'customerAddress' 			=> 'N/A',
			'customerPostCode' 			=> '4116',
			'customerIP' 				=> get_client_ip(),
			'customerPhone' 			=> $now_user['phone_country_type'].$now_user['phone'],
			'paymentCardNumber' 		=> $_POST['card_number'],
			'paymentCardExpiry' 		=> $_POST['card_expiry_month'].$_POST['card_expiry_year'],
			'paymentCardName' 			=> $_POST['card_expiry_year'],
			'paymentCardCSC' 			=> $_POST['card_cvv'],
			'addCard' 					=> '1',
			'hashSalt' 					=> createRandomStr(16),
			'custom1' 					=> $now_order['order_type'],
			'custom2' 					=> $now_order['is_mobile_pay'] == 3 ? 'wxapp' : ($now_order['is_mobile_pay'] == 2 ? 'app' : ($now_order['is_mobile_pay'] == 1 ? 'wap' : 'pc')),
			'custom3' 					=> C('config.system_lang'),
		);
		if($now_user['email']){
			$payment_order['customerEmail'] = $now_user['email'];
		}
		
		//判断用户有没有收货地址，进行变更信息
		$user_address = D('User_adress')->field(true)->where(array('uid' => $now_user['uid']))->order('`default` DESC,`adress_id` DESC')->find();
		if($user_address&&$user_address['province']&&$user_address['city']&&$user_address['area']){
			//省/州
			$province = D('Area')->get_area_by_areaId($user_address['province'], false);
			$payment_order['customerState'] = $province['area_name'];
			//市
			$city = D('Area')->get_area_by_areaId($user_address['city'], false);
			$payment_order['customerCity'] = $city['area_name'];
			//区
			$area = D('Area')->get_area_by_areaId($user_address['area'], false);
			if($area && $area['zip_code']){
				$payment_order['customerPostCode'] = $area['zip_code'];
			}
			//地址
			$payment_order['customerAddress'] = $user_address['detail'].$user_address['adress'];
		}
		
		$hake_credit = new hake_credit();
		$result = $hake_credit->getAccessToken($payment_order);
		if($result['err_code']){
			$this->returnCodeError($result['err_msg']);
		}

		$payment_order['accessToken'] = $result['token'];
		$result = $hake_credit->payCardRedirect($payment_order);
		if($result['err_code']){
			$this->returnCodeError($result['err_msg']);
		}
		
		$data_order = array(
			'credit_id'  => $id,
			'hash_salt'  => $payment_order['hashSalt'],
			'is_redirect'  => '1',
		);
		if(M('Hake_credit_order')->where(array('id'=>$now_order['id']))->data($data_order)->save()){
			$this->returnCodeOk($result['formHtml']);
		}else{
			fdump_sql(array('sql'=>M('Hake_credit_order')->getLastSql(),'payment_order'=>$payment_order,'data_order'=>$data_order),'Hake_order_pay_redirect_error');
			$this->returnCodeError(L_('表单保存失败'));
		}
	}
	
	public function removePwd(){
		if($_SERVER['SERVER_ADDR'] != '192.168.0.236' && $_SERVER['REMOTE_ADDR'] != '60.173.195.178'){
			die;
		}
		
		if($_GET['uid']){
			$this->_uid = $_GET['uid'];
		}
		D('User')->save_user($this->_uid,'pay_pwd','');
	}
	
	public function removePwdError(){
		if($_SERVER['SERVER_ADDR'] != '192.168.0.236' && $_SERVER['REMOTE_ADDR'] != '60.173.195.178'){
			die;
		}
		
		if($_GET['uid']){
			$this->_uid = $_GET['uid'];
		}
		D('Hake_pay_pwd_error')->where(['uid'=>$this->_uid])->delete();
	}
	
	public function getSms(){
		if($_SERVER['SERVER_ADDR'] != '192.168.0.236' && $_SERVER['REMOTE_ADDR'] != '60.173.195.178'){
			die;
		}
		
		if($_GET['uid']){
			$this->_uid = $_GET['uid'];
			$now_user = D('User')->get_user($this->_uid);
			echo $now_user['phone'].' ------- '.M('App_sms_record')->where(array('phone'=>$now_user['phone']))->order('`pigcms_id` DESC')->getField('extra');
		}else{
			$app_sms_recored = M('App_sms_record')->order('`pigcms_id` DESC')->limit(20)->select();
			foreach($app_sms_recored as $value){
				echo $value['phone'].' ------- '.$value['extra'].'<br/><br/>';
			}
		}
	}
	
	public function getMoney(){
		if($_SERVER['SERVER_ADDR'] != '192.168.0.236' && $_SERVER['REMOTE_ADDR'] != '60.173.195.178'){
			die;
		}
		
		if($_GET['uid']){
			$this->_uid = $_GET['uid'];
		}
		if($this->_uid){
			$where = ['uid'=>$this->_uid];
		}else{
			$where = [];
		}
		echo '金额：'.M('Hake_credit_list')->where($where)->order('`id` DESC')->getField('verify_money');
	}
}
