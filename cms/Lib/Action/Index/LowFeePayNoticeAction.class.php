<?php
class LowFeePayNoticeAction extends BaseAction{
	public function order_back_notice(){

		$order_type = $_GET['order_type'];
		$order_id = $_GET['order_id'];
		$now_order = $this->get_now_order($order_id,$order_type);
	
		if($now_order['paid']==1){
			$redirctUrl = $this->back_url($order_type,$now_order,$_GET['source']);
			
			if($_GET['source']==2){
				header('Content-type: application/json');
				$pay_info =  array(
						'errorCode'=>0,
						'errorMsg'=>'success',
						'result'=>array(
								'url'=>$redirctUrl
						),
				);
				echo json_encode($pay_info);die;
			}
			
			redirect($redirctUrl);
		}else {
			import('@.ORG.LowFeePay');
			$order['orderNo'] = $order_type . '_' . $now_order['orderid'].($_GET['is_own']?'_1':'');
			$lowfeepay = new LowFeePay($_GET['pay_type']);
			if($now_order['mer_id'] &&$_GET['pay_type']=='juhe'){
				$mer_juhe = M('Merchant_juhe_config')->where(array('mer_id'=>$now_order['mer_id']))->find();
				if(!empty($mer_juhe)){
					$lowfeepay->userId =$mer_juhe['userid'];
				}
			}
			$order['sys_order_id'] = $_GET['sys_order_id'];
			$go_pay_param = $lowfeepay->check($order);

			$this->after_notice($go_pay_param);
		}
	}
	

	public function ysfmini_query(){

		$order_type = $_GET['order_type'];
		$order_id = $_GET['order_id'];
		$now_order = $this->get_now_order($order_id,$order_type);
	
		if($now_order['paid']==1){
			$redirctUrl = $this->back_url($order_type,$now_order,$_GET['source']);
			
			if($_GET['source']==2){
				header('Content-type: application/json');
				$pay_info =  array(
						'errorCode'=>0,
						'errorMsg'=>'success',
						'result'=>array(
								'url'=>$redirctUrl
						),
				);
				echo json_encode($pay_info);die;
			}
			
			redirect($redirctUrl);
		}else {
			import('@.ORG.pay.ChinaumsYsf');
			$order['orderNo'] = $order_type . '_' . $now_order['orderid'];
			$lowfeepay = new ChinaumsYsf();
			$go_pay_param = $lowfeepay->check($order);

			$this->after_notice($go_pay_param);
		}
	}

	// 四川农信同步通知
	public function scrcu_back_notice(){

		$order_type = $_GET['order_type'];
		$order_id = $_GET['order_id'];
		if(strpos($_GET['order_id'], '?') !== false){
			$temp = explode("?", $_GET['order_id']);
			$order_id = $temp[0];//防止四川农信银行那边会在url后面增加 ?xxx=xxx
		}
		$now_order = $this->get_now_order($order_id,$order_type);
		
		if($now_order['paid']==1){
			$redirctUrl = $this->back_url($order_type,$now_order,$_GET['source']);
			
			if($_GET['source']==2){
				header('Content-type: application/json');
				$pay_info =  array(
						'errorCode'=>0,
						'errorMsg'=>'success',
						'result'=>array(
								'url'=>$redirctUrl
						),
				);
				echo json_encode($pay_info);die;
			}
			
			redirect($redirctUrl);
		}else {
			import('@.ORG.pay.Scrcu');
			$order['orderNo'] = $order_type . '_' . $now_order['orderid'];
			$Scrcu = new Scrcu();
			$go_pay_param = $Scrcu->query($order);
			$this->after_notice($go_pay_param);
		}
	}

	// 四川农信异步通知
	public function scrcu_notify(){

		$notice_data = file_get_contents("php://input");
		if($notice_data){
			$notice_data = json_decode($notice_data, true);
		}
		fdump('notice_data', 'scrcu_notify',1);
		fdump($notice_data, 'scrcu_notify',1);
		$order_id = $notice_data['orderNumber'];
		$pay_order = M("Payment_money_order")->where(['orderid'=>$order_id])->find();
		if(!empty($pay_order)){
			import('@.ORG.pay.Scrcu');
			$Scrcu = new Scrcu();
		
			$order_type = $pay_order['order_type'];
			$now_order = $this->get_now_order($order_id,$order_type);
			
			if($now_order['paid']==0){
				$order['orderNo'] = $order_type . '_' . $now_order['orderid'];
				$go_pay_param = $Scrcu->query($order);
				if($go_pay_param['error'] == 1){
					echo 'fail';
				}
				switch($order_type){
					case 'group':
						$res = D('Group_order')->after_pay($go_pay_param);
						break;
					case 'meal':
					case 'takeout':
					case 'food':
					case 'foodPad':
						D('Meal_order')->after_pay($go_pay_param, $order_type);
						break;
					case 'weidian':
						$pay_info = D('Weidian_order')->after_pay($go_pay_param);
						if(($pay_info['error']==0) && isset($pay_info['url'])){  /***异步通知***/
							$this->NoticeWDAsyn($now_order['orderid']);
						}
						break;
					case 'recharge':
						$res = D('User_recharge_order')->after_pay($go_pay_param);
						break;
					case 'waimai':
						D('Waimai_order')->after_pay($go_pay_param);
						break;
					case 'appoint':
						D('Appoint_order')->after_pay($go_pay_param);
						break;
					case 'balance-appoint':
						D('Appoint_order')->balance_after_pay($go_pay_param);
						break;
					case 'wxapp':
						D('Wxapp_order')->after_pay($go_pay_param);
						break;
					case 'store':
						D('Store_order')->after_pay($go_pay_param);
						break;
					case 'shop':
					case 'mall':
						D('Shop_order')->after_pay($go_pay_param);
						break;
					case 'plat':
						D('Plat_order')->after_pay($go_pay_param);
						break;
					case 'ticket':
						D('Scenic_order')->after_pay($go_pay_param);
						break;
					case 'guide':
						D('Scenic_aguide')->after_pay($go_pay_param);
						break;
		            case 'mcard':
		                D('Membership_card_order')->after_pay($go_pay_param);
		                break;
				}

				M("Scrcu_record")->where(['order_type' => $order_type, 'order_number' => $order_id])->data(['over'=>1])->save();
				echo 'ok';
			}else{
				M("Scrcu_record")->where(['order_type' => $order_type, 'order_number' => $order_id])->data(['over'=>1])->save();
				echo 'ok';
			}
			
		}else{

			//查询新版支付订单
			$pay_order = M("Pay_order_info")->where(['orderid'=>$order_id])->find();
			if($pay_order){
				$return = invoke_v20_service('\app\pay\model\service\channel\ScrcuService/scrcuNotify',['orderId' => $order_id]);

				fdump($return, 'scrcu_notify',1);
				echo $return['data']['retval'] ? $return['data']['retval'] : 'fail';
			}else{
				echo 'fail';
			}
		}
	}

	// 四川农信退款异步通知
	public function scrcu_refund(){
	}
	
	
	public function yzf_pay_back(){
		if($_GET['resultCode']!=-1){
			$this->error('支付失败');
		}
		$where['tradeprodNo']  =$_GET['orderSeq'];
		
		$res = M('Yzf_pay_info')->where($where)->find();

		$order_id = explode('_',$res['tradeNo']);
		
		$url = $this->config['site_url'].'/index.php?c=LowFeePayNotice&a=yzf_order_back_notice&order_type='
				.$res['order_type'].'&order_id='.$res['order_id']
				.'&source='.$res['is_mobile'].'&is_own='.($order_id[2] ? $order_id[2] : 0).'&mer_id='.intval($res['mer_id']).'&tradeDate='.$res['addtime'];
	
		redirect($url);
	}

	public function wft_pay_back(){
		//http://hf.group.com/index.php?c=LowFeePayNotice&a=wft_order_back_notice&order_type=group&order_id=1811221918414801358680&source=0&is_own=1&mer_id=1
		$order_type = $_GET['order_type'];
		$order_id = $_GET['order_id'];
		$source = $_GET['source'];
		$is_own = $_GET['is_own'];
		$mer_id = $_GET['mer_id'];
		$where['order_id'] = $order_id;
		$res = M('Wft_pay_info')->where($where)->find();
		$orderNo = $order_type.'_'.$res['short_orderid'].($is_own==1?'_'.$is_own:'');

		$now_order = $this->get_now_order($order_id,$order_type);
		if($now_order['paid']==1){
			$redirctUrl = $this->back_url($order_type,$now_order,$_GET['source']);

			if($_GET['source']==2){
				header('Content-type: application/json');
				$pay_info =  array(
						'errorCode'=>0,
						'errorMsg'=>'success',
						'result'=>array(
								'url'=>$redirctUrl
						),
				);
				echo json_encode($pay_info);die;
			}

			redirect($redirctUrl);
		}else {

			switch ($is_own) {
				case '0':
					$pay_method = D('Config')->get_pay_method($notOnline, $notOffline, true);
					break;
				case '1':
					$pay_method = D('Config')->get_pay_method($notOnline, $notOffline, true);
					$merchant_ownpay = D('Merchant_ownpay')->field('mer_id', true)->where(array('mer_id' => $mer_id))->find();
					foreach ($merchant_ownpay as $ownKey => $ownValue) {
						$ownValueArr = unserialize($ownValue);
						if ($ownValueArr['open']) {
							$ownValueArr['is_own'] = true;
							$pay_method[$ownKey] = array('name' => D('Pay')->getPayName($ownKey), 'config' => $ownValueArr);
						}
					}
					break;
				case '2':
					$pay_method = array();
					$merchant_ownpay = D('Merchant_ownpay')->field('mer_id', true)->where(array('mer_id' => $mer_id))->find();
					foreach ($merchant_ownpay as $ownKey => $ownValue) {
						$ownValueArr = unserialize($ownValue);
						if ($ownValueArr['open']) {
							$ownValueArr['is_own'] = true;
							$pay_method[$ownKey] = array('name' => D('Pay')->getPayName($ownKey), 'config' => $ownValueArr);
						}
					}

					if (empty($pay_method)) {
						$pay_method = D('Config')->get_pay_method($notOnline, $notOffline, true);
					}
					break;
			}


			$import_result = import('@.ORG.pay.Wftpay');
			$pay_class = new Wftpay('', '', 'wftpay', $pay_method['wftpay']['config'], '', 0);
			$go_pay_param = $pay_class->query_order($orderNo, $source,$res);

			$this->after_notice($go_pay_param);

		}
	}

	//翼支付跳转通知
	public function yzf_order_back_notice(){
		$order_type = $_GET['order_type'];
		$order_id = $_GET['order_id'];
		$source = $_GET['source'];
		$is_own = $_GET['is_own'];
		$mer_id = $_GET['mer_id'];
		$orderNo = $order_type.'_'.$order_id.($is_own==1?'_'.$is_own:'');
		$tradeDate = $_GET['tradeDate'];
		$now_order = $this->get_now_order($order_id,$order_type);

		if(empty($tradeDate)){
			$where['order_id']  =$_GET['order_id'];

			$res = M('Yzf_pay_info')->where($where)->find();

			$tradeDate = $res['addtime'];
		}


		if($now_order['paid']==1){
			$redirctUrl = $this->back_url($order_type,$now_order,$_GET['source']);

			if($_GET['source']==2){
				header('Content-type: application/json');
				$pay_info =  array(
						'errorCode'=>0,
						'errorMsg'=>'success',
						'result'=>array(
								'url'=>$redirctUrl
						),
				);
				echo json_encode($pay_info);die;
			}

			redirect($redirctUrl);
		}else {

			switch ($is_own) {
				case '0':
					$pay_method = D('Config')->get_pay_method($notOnline, $notOffline, true);
					break;
				case '1':
					$pay_method = D('Config')->get_pay_method($notOnline, $notOffline, true);
					$merchant_ownpay = D('Merchant_ownpay')->field('mer_id', true)->where(array('mer_id' => $mer_id))->find();
					foreach ($merchant_ownpay as $ownKey => $ownValue) {
						$ownValueArr = unserialize($ownValue);
						if ($ownValueArr['open']) {
							$ownValueArr['is_own'] = true;
							$pay_method[$ownKey] = array('name' => D('Pay')->getPayName($ownKey), 'config' => $ownValueArr);
						}
					}
					break;
				case '2':
					$pay_method = array();
					$merchant_ownpay = D('Merchant_ownpay')->field('mer_id', true)->where(array('mer_id' => $mer_id))->find();
					foreach ($merchant_ownpay as $ownKey => $ownValue) {
						$ownValueArr = unserialize($ownValue);
						if ($ownValueArr['open']) {
							$ownValueArr['is_own'] = true;
							$pay_method[$ownKey] = array('name' => D('Pay')->getPayName($ownKey), 'config' => $ownValueArr);
						}
					}

					if (empty($pay_method)) {
						$pay_method = D('Config')->get_pay_method($notOnline, $notOffline, true);
					}
					break;
			}


			$import_result = import('@.ORG.pay.Yzfpay');
			$pay_class = new Yzfpay('', '', 'yzfpay', $pay_method['yzfpay']['config'], '', 0);
			$go_pay_param = $pay_class->query_order($orderNo, $tradeDate, $source);

			$this->after_notice($go_pay_param);

		}
	}

	public function order_notice(){
		
		$notice_data = file_get_contents("php://input");
		// $notice_data= '{"md5Info":"F67ADD4427C2768B819BF23B164876A3","orderAmount":"0.01","orderNo":"group_1806141615137700000007","orderStatus":"1","orderTime":"20180614161541","payType":"21","serialNo":"20180614000004717432","thridOrderNo":"2018061421001004980588880312"}';

		fdump_sql([$notice_data, $_REQUEST], 'order_notice');

        //记录日志，重复的请求不处理.
        $xmlData = json_encode(simplexml_load_string($notice_data, 'SimpleXMLElement', LIBXML_NOCDATA));
        
        M('Fdump_sql')->data(['data' => $xmlData, 'name' => 'order_record_notice', 'time' => time()])->add();
        $recordCount = M('Fdump_sql')->where(['data' => $xmlData, 'name' => 'order_record_notice'])->count();
        
        if($recordCount > 1){
            header("HTTP/1.0 200 OK");
            return false;
        }
        
		import('@.ORG.LowFeePay');
		$lowfeepay = new LowFeePay($_GET['pay_type']);
		$verfiy_result =$lowfeepay->notice($notice_data);


		if($verfiy_result['sign_result']){
			$go_pay_param = $verfiy_result['order_param'];
			
			$this->after_notice($go_pay_param);
		}
		header("HTTP/1.0 200 OK");

	}

	public function nmgpay_order_back(){
		$out_trade_no = $_GET['out_trade_no'];

		import("@.ORG.pay.Nmgpay");
		$pay_model  = new nmgpay();

		$go_pay_param = $pay_model->query_order($out_trade_no);

		if(!$go_pay_param['error']){
			$this->after_notice($go_pay_param);
		}

	}


	public function nmgpay_order_notice(){
		$notice_data = file_get_contents("php://input");


//		$notice_data = '{"out_trade_no":"store_63_1551161067","order_code":"SMD2019022607532698","pay_status":"2","status_name":"\\u5df2\\u4ed8\\u6b3e","order_money":0.01,"pay_money":0.01,"full_money":0,"coupon_money":0,"no_discount":0,"revoke_money":0.01,"discount_money":0,"discount":10,"add_time":"2019-02-26 14:04:27","pay_time":"2019-02-26 14:06:31","pay_method":"3","pay_method_name":"\\u5fae\\u4fe1","balance":"","shop_openid":"bs6y2ji07elk3lszrghtcws5rbquqath"}';
		import("@.ORG.pay.Nmgpay");
		$pay_model  = new nmgpay();
		$order_notice = json_decode($notice_data,true);
		$go_pay_param = $pay_model->query_order( $order_notice['out_trade_no']);
		if(!$go_pay_param['error']){

			$this->after_notice($go_pay_param);
			echo json_encode(array('success'=>true,
					'result'=>array(
							'statusCode'=>200
					)));die;
		}
		echo json_encode(array('success'=>false,
				'result'=>array(
						'statusCode'=>0
				)));die;

	}

	public function yzf_order_notice(){
		$notice_data = file_get_contents("php://input");
		fdump($notice_data,'data',1);
		$notice_data = json_decode($notice_data,true);
		$where['tradeNo']  =$notice_data['outTradeNo'];
		$out_trade_no = explode('_',$notice_data['outTradeNo']);
		$is_own = $out_trade_no[2]?1:0;
		$res = M('Yzf_pay_info')->where($where)->find();

		$mer_id = $res['mer_id'];
		switch ($is_own) {
			case '0':
				$pay_method = D('Config')->get_pay_method($notOnline, $notOffline, true);
				break;
			case '1':
				$pay_method = D('Config')->get_pay_method($notOnline, $notOffline, true);
				$merchant_ownpay = D('Merchant_ownpay')->field('mer_id', true)->where(array('mer_id' => $mer_id))->find();
				foreach ($merchant_ownpay as $ownKey => $ownValue) {
					$ownValueArr = unserialize($ownValue);
					if ($ownValueArr['open']) {
						$ownValueArr['is_own'] = true;
						$pay_method[$ownKey] = array('name' => D('Pay')->getPayName($ownKey), 'config' => $ownValueArr);
					}
				}
				break;
			case '2':
				$pay_method = array();
				$merchant_ownpay = D('Merchant_ownpay')->field('mer_id', true)->where(array('mer_id' => $mer_id))->find();
				foreach ($merchant_ownpay as $ownKey => $ownValue) {
					$ownValueArr = unserialize($ownValue);
					if ($ownValueArr['open']) {
						$ownValueArr['is_own'] = true;
						$pay_method[$ownKey] = array('name' => D('Pay')->getPayName($ownKey), 'config' => $ownValueArr);
					}
				}

				if (empty($pay_method)) {
					$pay_method = D('Config')->get_pay_method($notOnline, $notOffline, true);
				}
				break;
		}

		// if($res['mer_id']){

		// $merchant_ownpay = D('Merchant_ownpay')->field('mer_id', true)->where(array('mer_id' => $res['mer_id']))->find();
		// foreach ($merchant_ownpay as $ownKey => $ownValue) {
		// $ownValueArr = unserialize($ownValue);
		// if ($ownValueArr['open']) {
		// $ownValueArr['is_own'] = true;
		// $pay_method[$ownKey] = array('name' => D('Pay')->getPayName($ownKey), 'config' => $ownValueArr);
		// }
		// }
		// }

		$import_result = import('@.ORG.pay.Yzfpay');
		$pay_class = new Yzfpay('', '', 'yzfpay',$pay_method['yzfpay']['config'], '', 0);
		$verfiy_result = $pay_class->notice($notice_data,$res['offline']);

		if($verfiy_result['sign_result']){
			$go_pay_param = $verfiy_result['order_param'];
			$go_pay_param['is_mobile'] = $res['is_mobile'];
			$this->after_notice($go_pay_param,true);
			echo json_encode(array('success'=>true,
					'result'=>array(
							'statusCode'=>200
					)));die;
		}
		echo json_encode(array('success'=>false,
				'result'=>array(
						'statusCode'=>0
				)));die;


	}

	//网付通异步
	public function wftpay_order_notice(){
		$notice_data = file_get_contents("php://input");

		$data = explode('&',$notice_data);
		$params = array();
		foreach($data as $k=>$v){
			$tmp = explode('=',$v);
			$params[$tmp[0]] = $tmp[1];
		}

		$where['tradeNo'] = $params['OrderNo'];
		$res = M('Wft_pay_info')->where($where)->find();

		if($res['mer_id']){

			$merchant_ownpay = D('Merchant_ownpay')->field('mer_id', true)->where(array('mer_id' => $res['mer_id']))->find();
			foreach ($merchant_ownpay as $ownKey => $ownValue) {
				$ownValueArr = unserialize($ownValue);
				if ($ownValueArr['open']) {
					$ownValueArr['is_own'] = true;
					$pay_method[$ownKey] = array('name' => D('Pay')->getPayName($ownKey), 'config' => $ownValueArr);
				}
			}
		}
		import('@.ORG.Wftpay');

		$pay_class = new Yzfpay('', '', 'yzfpay',$pay_method['wftpay']['config'], '', 0);
		$verfiy_result =$pay_class->notice($params);
		if($verfiy_result['sign_result']){
			$go_pay_param = $verfiy_result['order_param'];
			$this->after_notice($go_pay_param);
		}

		header("HTTP/1.0 200 OK");

	}

	public function after_notice($go_pay_param){
		$order_type = $go_pay_param['order_type'];
		$now_order = $this->get_now_order($go_pay_param['order_id'],$order_type);

		$redirctUrl = $this->back_url($order_type,$now_order,$go_pay_param['is_mobile']);
		if($go_pay_param['error'] == 1) {
			$error_msg =  '校验时发生错误！' . $go_pay_param['msg'];
			if ($go_pay_param['is_mobile'] == 2) {
				header('Content-type: application/json');
				$pay_info = array(
						'errorCode' => 0,
						'errorMsg' => $error_msg,
						'result' => array(
						'url' => $redirctUrl
						),
				);
				echo json_encode($pay_info);
				die;
			} else if($go_pay_param['is_mobile']==1) {
				$this->error_tips($error_msg, $redirctUrl);
			} else {
				$this->error($error_msg, $redirctUrl);
			}
		}
	
		if(empty($now_order)){
			$this->error_tips('该订单不存在');
		}
		
		$now_order['order_type'] = $order_type;
		if($now_order['paid']==1 && $order_type!='balance-appoint'){
			$error_msg ="订单已支付";
			$redirctUrl = $this->back_url($order_type,$now_order,1);
			if($go_pay_param['is_mobile']==2){
				header('Content-type: application/json');
				$pay_info =  array(
					'errorCode'=>0,
					'errorMsg'=>'success',
					'result'=>array(
						'url'=>$redirctUrl
					),
				);
				echo json_encode($pay_info);die;
			}else if($go_pay_param['is_mobile']==0){
				$redirctUrl = $this->back_url($order_type,$now_order,0);
				redirect($redirctUrl);
			}else{
				redirect($redirctUrl);
			}
		}

		$now_order['is_mobile_pay'] = $go_pay_param ['is_mobile'];
		switch($order_type){
			case 'group':
				$res = D('Group_order')->after_pay($go_pay_param);
				break;
			case 'meal':
			case 'takeout':
			case 'food':
			case 'foodPad':
				D('Meal_order')->after_pay($go_pay_param, $order_type);
				break;
			case 'weidian':
				$pay_info = D('Weidian_order')->after_pay($go_pay_param);
				if(($pay_info['error']==0) && isset($pay_info['url'])){  /***异步通知***/
					$this->NoticeWDAsyn($now_order['orderid']);
				}
				break;
			case 'recharge':
				$res = D('User_recharge_order')->after_pay($go_pay_param);
				break;
			case 'waimai':
				D('Waimai_order')->after_pay($go_pay_param);
				break;
			case 'appoint':
				D('Appoint_order')->after_pay($go_pay_param);
				break;
			case 'balance-appoint':
				D('Appoint_order')->balance_after_pay($go_pay_param);
				break;
			case 'wxapp':
				D('Wxapp_order')->after_pay($go_pay_param);
				break;
			case 'store':
				D('Store_order')->after_pay($go_pay_param);
				break;
			case 'shop':
			case 'mall':
				D('Shop_order')->after_pay($go_pay_param);
				break;
			case 'plat':
				D('Plat_order')->after_pay($go_pay_param);
				break;
			case 'ticket':
				D('Scenic_order')->after_pay($go_pay_param);
				break;
			case 'guide':
				D('Scenic_aguide')->after_pay($go_pay_param);
				break;
            case 'spread':
                D('Merchant_spread_order')->after_pay($go_pay_param);
                break;
            case 'mcard':
                D('Membership_card_order')->after_pay($go_pay_param);
                break;
		}

		if($go_pay_param['is_mobile']==2){
			if($_GET['pay_type'] == 'paylinx'){
				redirect($redirctUrl);
				exit;
			}
			header('Content-type: application/json');
			$pay_info =  array(
				'errorCode'=>0,
				'errorMsg'=>'success',
				'result'=>array(
					'url'=>$redirctUrl
				),
			);
			echo json_encode($pay_info);die;
		}else if($go_pay_param['is_mobile']==1){
			redirect($this->config['site_url'].'/wap.php?c=Pay&a=success_order&referer='.urlencode($redirctUrl));
		}else{
			redirect($redirctUrl);
		}

	}

	public function  get_now_order($order_id,$order_type){
		switch($order_type){
			case 'group':
				$now_order =$this->get_orderid('Group_order',$order_id);
				break;
			case 'meal':
				$now_order =$this->get_orderid('Meal_order',$order_id);
				break;
			case 'takeout':
			case 'food':
			case 'foodPad':
				$now_order =$this->get_orderid('Meal_order',$order_id);
				break;
			case 'weidian':
				$now_order = D('Weidian_order')->where(array('orderid'=>$order_id))->find();
				$now_order =$this->get_orderid('Weidian_order',$order_id);
				break;
			case 'recharge':
				$now_order =$this->get_orderid('User_recharge_order',$order_id);
				break;
			case 'appoint':
				$now_order =$this->get_orderid('Appoint_order',$order_id);
				break;
			case 'waimai':
				$now_order =$this->get_orderid('Waimai_order',$order_id);
				break;
			case 'wxapp':
				$now_order =$this->get_orderid('Wxapp_order',$order_id);
				break;
			case 'store':
				$now_order =$this->get_orderid('Store_order',$order_id);
				fdump($now_order,'ss');
				break;
			case 'shop':
			case 'mall':
				$now_order = $this->get_orderid('Shop_order', $order_id);
				break;
			case 'plat':
				$now_order = $this->get_orderid('Plat_order', $order_id);
				break;
			case 'balance-appoint':
				$now_order = $this->get_orderid('Appoint_order', $order_id);
				break;
			case 'ticket':
				$now_order =$this->get_orderid('Scenic_order',$order_id);
				break;
			case 'guide':
				$now_order =$this->get_orderid('Scenic_aguide_order',$order_id);
				break;
			case 'mcard':
				$now_order =$this->get_orderid('Membership_card_order',$order_id);
				break;
			default:
				$now_order =array();
		}
		return $now_order;

	}

	public function get_orderid($table,$orderid,$offline=0){
		$order =  D($table);
		$tmp_orderid = D('Tmp_orderid');
		if($offline){
			$now_order = $order->where(array('orderid'=>$orderid))->find();
		}else{
			$now_order = $order->where(array('orderid'=>$orderid))->find();
			
			if(empty($now_order)){
				$res = $tmp_orderid->where(array('orderid'=>$orderid))->find();
				$now_order = $order->where(array('order_id'=>$res['order_id']))->find();
				$order->where(array('order_id'=>$res['order_id']))->setField('orderid',$orderid);
				$now_order['orderid']=$orderid;
			}
		}
		
		if(empty($now_order)){
			$this->error_tips('该订单不存在');
		}else{
			//            $tmp_orderid->where(array('order_id'=>$now_order['order_id']))->delete();
		}

		return $now_order;
	}



	public function back_url($order_type,$now_order,$is_wap=1){
		if($is_wap){
			switch($order_type){
				case 'group':
					if (isset($now_order['is_head']) && $now_order['is_head'] > 0) {
						$redirctUrl = C('config.site_url').'/wap.php?c=Groupnew&a=invitePin&order_id='.$now_order['order_id'];
					} else {
						$redirctUrl = C('config.site_url').'/wap.php?c=My&a=group_order&order_id='.$now_order['order_id'];
					}
					break;
				case 'meal':
					$redirctUrl = C('config.site_url').'/wap.php?c=Meal&a=detail&orderid='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
					break;
				case 'takeout':
					$redirctUrl = C('config.site_url').'/wap.php?c=Takeout&a=order_detail&order_id='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
					break;
				case 'food':
				case 'foodPad':
					$redirctUrl = C('config.site_url').'/wap.php?c=Food&a=order_detail&order_id='.$now_order['order_id'].'&mer_id='.$now_order['mer_id'].'&store_id='.$now_order['store_id'];
					break;
				case 'weidian':
					$redirctUrl = D('Weidian_order')->get_weidian_url(array('wecha_id'=>$now_order['uid'],'order_no'=>$now_order['weidian_order_id'],'pay_money'=>$now_order['money'],'third_id'=>$now_order['third_id'],'payment_method'=>$now_order['pay_type']));
					$this->NoticeWDAsyn($now_order['orderid']);
					break;
				case 'appoint':
					$redirctUrl = C('config.site_url').'/wap.php?c=My&a=appoint_order&order_id='.$now_order['order_id'];
					break;
				case 'waimai':
					$redirctUrl = C('config.site_url').'/index.php?g=WaimaiWap&c=Order&a=detail&order_id='.$now_order['order_id'];
					break;
				case 'recharge':
					$redirctUrl = C('config.site_url').D('User_recharge_order')->get_pay_after_url($now_order['label'],$now_order['is_mobile_pay']);
					break;
				case 'wxapp':
					$redirctUrl = C('config.site_url').'/wap.php?c=Wxapp&a=pay_back&order_id='.$now_order['order_id'];
					break;
				case 'store':
					$redirctUrl = C('config.site_url').'/wap.php?c=My&a=store_order_detail&order_id='.$now_order['order_id'];
					break;
				case 'shop':
					if($now_order['team_id']&&$now_order['group_id']){
						$redirctUrl = C('config.site_url').'/wap.php?c=Mall&a=group_team_detail&group_id='.$now_order['group_id'].'&team_id='.$now_order['team_id'].'&order_id=' . $now_order['order_id'];
					}else{
						$redirctUrl = C('config.site_url').'/wap.php?c=Shop&a=status&order_id=' . $now_order['order_id'];
					}
					break;
				case 'mall':
					if($now_order['team_id']&&$now_order['group_id']){
						$redirctUrl = C('config.site_url').'/wap.php?c=Mall&a=group_team_detail&group_id='.$now_order['group_id'].'&team_id='.$now_order['team_id'].'&order_id=' . $now_order['order_id'];
					}else{
						$redirctUrl = C('config.site_url').'/wap.php?c=Mall&a=status&order_id=' . $now_order['order_id'];
					}
					break;
				case 'plat':
					$redirctUrl = D('Plat_order')->get_order_url($now_order['order_id'],true);
					break;
				case 'balance-appoint':
					$redirctUrl = C('config.site_url').'/wap.php?c=My&a=appoint_order&order_id='.$now_order['order_id'];
					break;
				case 'ticket':
					$redirctUrl = C('config.site_url').'/wap.php?c=Scenic_user&a=ticket_order_details&order_id='.$now_order['order_id'];
					break;
				case 'guide':
					$redirctUrl = C('config.site_url').'/wap.php?c=Scenic_user&a=aguide_order_list';
					break;
                case 'spread':
                    $redirctUrl = C('config.site_url') . '/wap.php?c=My&a=my_spread';
                    break;
                case 'mcard':
                    $redirctUrl = C('config.site_url') . D('House_village')->base_url . 'pages/membership/platMemberList';
                    break;
			}

		}else{
			switch($order_type){
				case 'group':
					$redirctUrl = C('config.site_url').'/index.php?g=User&c=Index&a=group_order_view&order_id='.$now_order['order_id'];
					break;
				case 'meal':
				case 'takeout':
				case 'food':
					$redirctUrl = C('config.site_url').'/index.php?g=User&c=Index&a=meal_order_view&order_id='.$now_order['order_id'];
					break;
				case 'recharge':
					$redirctUrl = C('config.site_url').'/index.php?g=User&c=Credit&a=index';
					break;
				case 'appoint':
					$redirctUrl = C('config.site_url').'/index.php?g=User&c=Index&a=appoint_order_view&order_id='.$now_order['order_id'];
					break;
				case 'waimai':
					$redirctUrl = C('config.site_url').'/index.php?g=Waimai&c=Order&a=detail&order_id='.$now_order['order_id'];
					break;
				case 'mall':
				case 'shop':
					$redirctUrl = C('config.site_url').'/index.php?g=User&c=Index&a=shop_order_view&order_id='.$now_order['order_id'];
					break;
				case 'mcard':
					$redirctUrl = C('config.site_url') . D('House_village')->base_url . 'pages/membership/platMemberList';
					break;
			}

		}
		return  $redirctUrl;
	}
	

	//定时查询订单支付状态
	public function scrcu_record(){
	    $whereScrcu = [
	        'order_type' => ['neq', 'pay_order_info'],
            'add_time'=>['gt', time()-480],
            'over'=>0,
            'limit'=>['lt', 127],
        ];
		$data = M("Scrcu_record")->order("id asc")->where($whereScrcu)->limit(20)->select();
        if (count($data)<20) {
            // 首先查询再时间范围内的一次没有查询过的订单不足20条， 查询没有查询过的订单补足20条
            $limit1 = 20 - count($data);
            $sql = "SELECT * FROM `pigcms_scrcu_record` WHERE ( `limit` < 127 ) AND ( `over` = 0 ) AND ( `order_type` <> 'pay_order_info' ) ORDER BY `limit` asc,`id` asc LIMIT {$limit1} ";
            $data1 = D('')->query($sql);
            if ($data1) {
                $data = array_merge($data, $data1);
            }
        }
		if(!empty($data)){
			import('@.ORG.pay.Scrcu');
			$Scrcu = new Scrcu();
			foreach ($data as $key => $value) {
				$order_type = $value['order_type'];
				$order_id = $value['order_number'];
				$now_order = $this->get_now_order($order_id,$order_type);
				
				if($now_order['paid']==1){
					M("Scrcu_record")->where(['id'=>$value['id']])->data(['over'=>1,'over_time' => time()])->save();
				}else {
					$order['orderNo'] = $order_type . '_' . $now_order['orderid'];
					$go_pay_param = $Scrcu->query($order);
					if($go_pay_param['error'] == 1){
						M("Scrcu_record")->where(['id'=>$value['id']])->data(['limit'=>$value['limit']+1])->save();
						continue;
					}
					switch($order_type){
						case 'group':
							$res = D('Group_order')->after_pay($go_pay_param);
							break;
						case 'meal':
						case 'takeout':
						case 'food':
						case 'foodPad':
							D('Meal_order')->after_pay($go_pay_param, $order_type);
							break;
						case 'weidian':
							$pay_info = D('Weidian_order')->after_pay($go_pay_param);
							if(($pay_info['error']==0) && isset($pay_info['url'])){  /***异步通知***/
								$this->NoticeWDAsyn($now_order['orderid']);
							}
							break;
						case 'recharge':
							$res = D('User_recharge_order')->after_pay($go_pay_param);
							break;
						case 'waimai':
							D('Waimai_order')->after_pay($go_pay_param);
							break;
						case 'appoint':
							D('Appoint_order')->after_pay($go_pay_param);
							break;
						case 'balance-appoint':
							D('Appoint_order')->balance_after_pay($go_pay_param);
							break;
						case 'wxapp':
							D('Wxapp_order')->after_pay($go_pay_param);
							break;
						case 'store':
							D('Store_order')->after_pay($go_pay_param);
							break;
						case 'shop':
						case 'mall':
							D('Shop_order')->after_pay($go_pay_param);
							break;
						case 'plat':
							D('Plat_order')->after_pay($go_pay_param);
							break;
						case 'ticket':
							D('Scenic_order')->after_pay($go_pay_param);
							break;
						case 'guide':
							D('Scenic_aguide')->after_pay($go_pay_param);
							break;
			            case 'mcard':
			                D('Membership_card_order')->after_pay($go_pay_param);
			                break;
					}
					M("Scrcu_record")->where(['id'=>$value['id']])->delete();
				}
			}
		}
	}

}
	
	