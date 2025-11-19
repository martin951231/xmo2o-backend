<?php
/**
 * Created by PhpStorm.
 * User: Jaty
 * DateTime: 2021/4/27 15:35
 */
class House_qcloudAction extends BaseAction{
    /**
     * Notes: 腾讯云通知
     * User: Jaty
     * DateTime: 2021/4/27 15:43
     */
    public function market_notice() 
	{
		
		fdump_api([$_POST, $_GET], 'house_qcloud_notice', true);
		
		$sign_result = $this->checkSignature($_GET['signature'], 'K8gbrutMK0Y64TV36', $_GET['timestamp'], $_GET['eventId']);
		if(!$sign_result){
			exit(json_encode(['echoback' => 'sign error']));
		}
		
		if(!$_POST['openId']){
			$_POST['openId'] = md5($_POST['accountId']);
		}
		
		if($_POST['action'] == 'createInstance')	//实例创建通知接口
		{
			$this->createInstance();
		}else if($_POST['action'] == 'renewInstance')	//实例续费通知接口
		{
			$this->renewInstance();
		}else if($_POST['action'] == 'modifyInstance')	//实例配置变更通知接口
		{
			$this->modifyInstance();
		}else if($_POST['action'] == 'expireInstance')	//实例过期通知接口
		{
			$this->expireInstance();
		}else if($_POST['action'] == 'destroyInstance')	//实例销毁通知接口
		{
			$this->destroyInstance();
		}
		
		
		dump($_POST);
		
    }
	
	public function authLogin(){
		$database_property = D('House_property');
		$condition_village = ['qcloud_openId' => $_GET['openid']];
		$now_property = $database_property->where($condition_village)->find();
		if(!$now_property){
			$this->error('物业公司不存在');
		}
		
        $now_property['login_identify'] = 1;
        session('property', $now_property);

        // 给予物业管理员身份
		$database_property->where($condition_village)->data(array('last_time'=>time()))->save();
		
		$ticket = Token::createToken($now_property['id'], 8);
		setcookie('property_access_token', $ticket);
		
		
		//判断是跳引导页，还是跳首页
		$where_property_guide = [];
		$where_property_guide[] = ['property_id' => $now_property['id']];
		$db_house_property_guide = M('House_property_guide');
		$property_guide = $db_house_property_guide->where($where_property_guide)->find();
		if (empty($property_guide) || !isset($property_guide['complete_config']) || $property_guide['complete_config']!=1) {
			redirect($this->config['site_url'].'/v20/public/platform/#/property/property/Step');
		}
		
		
		redirect($this->config['site_url'].'/v20/public/platform/#/property/property.iframe/property_index');
	}
	
	protected function createInstance(){
		$qcloud_openId = trim(strval($_POST['openId']));
		if($now_property = M('House_property')->where(['qcloud_openId' => $qcloud_openId])->find()){
			$order_arr = M('Package_order')->where(['property_id' => $now_property['id']])->find();
			$returnArr = [
				'signId' => $order_arr['order_no'],
				'appInfo' => [
					'website' => 'https://www.kuaijing.com.cn/',
					'authUrl' => $this->config['site_url'] . '/appapi.php?c=House_qcloud&a=authLogin&openid=' . $qcloud_openId,
				],
			];
			exit(json_encode($returnArr));
		}
		
		if($_POST['productId'] == '27048'){
			$data_house_property = [
				'qcloud_accountId' => $_POST['accountId'],
				'qcloud_openId' => $_POST['openId'],
				'qcloud_orderId' => $_POST['orderId'],
				'status' => '1',
				'create_time' => time(),
				'property_name' => '腾讯云注册',
			];
			if($_POST['productInfo']['isTrial']){
				$data_house_property['property_name'] = '体验物业'; 
			}
			
			$property_id = M('House_property')->data($data_house_property)->add();
			fdump(M('House_property')->getLastSql(), 'createInstance');
			
			if($_POST['productInfo']['isTrial']){
				$package = D('Privilege_package')->where(['package_title'=>'体验套餐'])->field('package_id,package_title,package_price,room_num,package_limit_num')->find();
			}else{
				$package = D('Privilege_package')->where(['package_title'=>'运营套餐'])->field('package_id,package_title,package_price,room_num,package_limit_num')->find();
			}
			$content_id = D('Privilege_package_content')->getField('content_id',true);
			
			$package_id = $package['package_id'];
            $package_title = $package['package_title'];
            $package_price = $package['package_price'];
            $room_num = $package['room_num'];
            $package_limit_num = $package['package_limit_num'];
			
			
			$time = strtotime(date('Y-m-d',time()));
			$order_content = implode(',',$content_id);
		
			$order_arr = [
				'order_no' => date('YmdHis') . rand(100, 999),
				'package_id' => $package_id,
				'property_id' => $property_id,
				'property_name' => '腾讯云注册',
				'property_tel' => '',
				'pay_money' => $package_price,
				'order_money' => $package_price,
				'pay_type' => 3,
				'create_time' => time(),
				'pay_time' => time(),
				'package_period' => 1,
				'package_end_time' => (1 * 366) * 86400 + $time + 86400,
				'num' => 1,
				'status' => 1,
				'package_title' => $package_title,
				'room_num' => $room_num,
				'package_price' => $package_price,
				'package_limit_num' => $package_limit_num,
				'content_id' => $order_content,
				'qcloud_product_json' => serialize($_POST),
			];
			
			if($_POST['productInfo']['isTrial']){
				$order_arr['property_name'] = '体验物业'; 
				$order_arr['order_money'] = 0; 
				$order_arr['pay_money'] = 0; 
				$order_arr['pay_time'] = 0; 
				$order_arr['package_period'] = $_POST['productInfo']['timeSpan'] . $_POST['productInfo']['timeUnit']; 
				$order_arr['package_end_time'] = (1 * 7) * 86400 + $time + 86400;
				$order_arr['package_limit_num'] = ''; 
				$order_arr['package_try_end_time'] = (1 * 7) * 86400 + $time + 86400;
			}
			
			$result =D('Package_order')->add($order_arr);
			
			
			$returnArr = [
				'signId' => $order_arr['order_no'],
				'appInfo' => [
					'website' => 'https://www.kuaijing.com.cn/',
					'authUrl' => $this->config['site_url'] . '/appapi.php?c=House_qcloud&a=authLogin&openid=' . $qcloud_openId,
				],
			];
			exit(json_encode($returnArr));
		}else{
			exit(json_encode(['echoback' => 'error productId']));
		}
	}
	
	private function renewInstance(){
		$qcloud_openId = trim(strval($_POST['openId']));
		if($_POST['productId'] == '27048'){
			$now_property = M('House_property')->where(['qcloud_openId' => $qcloud_openId])->find();
			// dump($now_property);
			$property_id = $now_property['id'];
			
			$package = D('Privilege_package')->where(['package_title'=>'运营套餐'])->field('package_id,package_title,package_price,room_num,package_limit_num')->find();
			$content_id = D('Privilege_package_content')->getField('content_id',true);
			
			$package_id = $package['package_id'];
            $package_title = $package['package_title'];
            $package_price = $package['package_price'];
            $room_num = $package['room_num'];
            $package_limit_num = $package['package_limit_num'];
			
			
			$time = strtotime(date('Y-m-d',time()));
			$order_content = implode(',',$content_id);
		
			$order_arr = [
				'order_no' => date('YmdHis') . rand(100, 999),
				'order_type' => 2,
				'package_id' => $package_id,
				'property_id' => $property_id,
				'property_name' => '',
				'property_tel' => '',
				'pay_money' => $package_price,
				'order_money' => $package_price,
				'pay_type' => 3,
				'create_time' => time(),
				'pay_time' => time(),
				'package_period' => 1,
				'package_end_time' => strtotime($_POST['instanceExpireTime']),
				'num' => 1,
				'status' => 1,
				'package_title' => $package_title,
				'room_num' => $room_num,
				'package_price' => $package_price,
				'package_limit_num' => $package_limit_num,
				'content_id' => $order_content,
				'qcloud_product_json' => serialize($_POST),
			];
			
			$result = D('Package_order')->add($order_arr);
			
			
			$returnArr = [
				'success' => 'true',
			];
			exit(json_encode($returnArr));
		}else{
			exit(json_encode(['echoback' => 'error productId']));
		}
	}
	
	private function modifyInstance(){
		$qcloud_openId = trim(strval($_POST['openId']));
		if($_POST['productId'] == '27048'){
			$now_property = M('House_property')->where(['qcloud_openId' => $qcloud_openId])->find();
			// dump($now_property);
			$property_id = $now_property['id'];
			
			$package = D('Privilege_package')->where(['package_title'=>'运营套餐'])->field('package_id,package_title,package_price,room_num,package_limit_num')->find();
			$content_id = D('Privilege_package_content')->getField('content_id',true);
			
			$package_id = $package['package_id'];
            $package_title = $package['package_title'];
            $package_price = $package['package_price'];
            $room_num = $package['room_num'];
            $package_limit_num = $package['package_limit_num'];
			
			
			$time = strtotime(date('Y-m-d',time()));
			$order_content = implode(',',$content_id);
		
			$order_arr = [
				'order_no' => date('YmdHis') . rand(100, 999),
				'order_type' => 2,
				'package_id' => $package_id,
				'property_id' => $property_id,
				'property_name' => '',
				'property_tel' => '',
				'pay_money' => $package_price,
				'order_money' => $package_price,
				'pay_type' => 3,
				'create_time' => time(),
				'pay_time' => time(),
				'package_period' => 1,
				'package_end_time' => strtotime($_POST['instanceExpireTime']),
				'num' => 1,
				'status' => 1,
				'package_title' => $package_title,
				'room_num' => $room_num,
				'package_price' => $package_price,
				'package_limit_num' => $package_limit_num,
				'content_id' => $order_content,
				'qcloud_product_json' => serialize($_POST),
			];
			
			$result = D('Package_order')->add($order_arr);
			
			
			$returnArr = [
				'success' => 'true',
			];
			exit(json_encode($returnArr));
		}else{
			exit(json_encode(['echoback' => 'error productId']));
		}
	}
	
	private function expireInstance(){
		$qcloud_openId = trim(strval($_POST['openId']));
		if($_POST['productId'] == '27048'){
			$now_property = M('House_property')->where(['qcloud_openId' => $qcloud_openId])->find();
			// dump($now_property);
			$property_id = $now_property['id'];
			
			$package = D('Privilege_package')->where(['package_title'=>'运营套餐'])->field('package_id,package_title,package_price,room_num,package_limit_num')->find();
			$content_id = D('Privilege_package_content')->getField('content_id',true);
			
			$package_id = $package['package_id'];
            $package_title = $package['package_title'];
            $package_price = $package['package_price'];
            $room_num = $package['room_num'];
            $package_limit_num = $package['package_limit_num'];
			
			
			$time = strtotime(date('Y-m-d',time()));
			$order_content = implode(',',$content_id);
		
			$order_arr = [
				'order_no' => date('YmdHis') . rand(100, 999),
				'order_type' => 3,
				'package_id' => $package_id,
				'property_id' => $property_id,
				'property_name' => '',
				'property_tel' => '',
				'pay_money' => $package_price,
				'order_money' => $package_price,
				'pay_type' => 3,
				'create_time' => time(),
				'pay_time' => time(),
				'package_period' => 1,
				'package_end_time' => time(),
				'num' => 1,
				'status' => 1,
				'package_title' => $package_title,
				'room_num' => $room_num,
				'package_price' => $package_price,
				'package_limit_num' => $package_limit_num,
				'content_id' => $order_content,
				'qcloud_product_json' => serialize($_POST),
			];
			
			$result = D('Package_order')->add($order_arr);
			
			
			$returnArr = [
				'success' => 'true',
			];
			exit(json_encode($returnArr));
		}else{
			exit(json_encode(['echoback' => 'error productId']));
		}
	}
	
	//实际不销毁，未来随时可以续费使用
	private function destroyInstance(){
		$qcloud_openId = trim(strval($_POST['openId']));
		if($_POST['productId'] == '27048'){
			$returnArr = [
				'success' => 'true',
			];
			exit(json_encode($returnArr));
		}else{
			exit(json_encode(['echoback' => 'error productId']));
		}
	}
	
	private function checkSignature($signature, $token, $timestamp, $eventId)
	{
		$currentTimestamp = time();
		if ($currentTimestamp - $timestamp > 30000) {
			return false;
		}
		$timestamp = (string)$timestamp;
		$eventId = (string)$eventId;
		$params = array($token, $timestamp, $eventId);
		sort($params, SORT_STRING);
		$str = implode('', $params);
		$requestSignature = hash('sha256', $str);
		return $signature === $requestSignature;
	}
}
