<?php
/*
 * Baodi_card 支付
 *
 */
class Baodi_cardAction extends BaseAction{
	
	//会员卡列表
	public function index(){
		$return = array();
		
		$nowUser = D('User')->get_user($this->_uid);

		import('@.ORG.third_custom.baodi.baodiCard');
		$baodiCard = new baodiCard();
		$return = $baodiCard->getCardList($nowUser['phone']);
		if($return['MessageNo'] == '-1'){
			$this->returnCodeError($return['Message']);
		}
		
		$card_list = [];
		if($return['Rows']['Row'][0]){
			foreach($return['Rows']['Row'] as $value){
				$value['Balance'] = getFormatNumber($value['Balance']);
				$card_list[] = $value;
			}
		}else{
			$return['Rows']['Row']['Balance'] = getFormatNumber($return['Rows']['Row']['Balance']);
			$card_list[] = $return['Rows']['Row'];
		}

		$this->returnCodeOk($card_list);
	}
	
	//绑卡提交支付
	public function payCard(){
		$ProfileId = $_POST['ProfileId'];
		$orderid = $_POST['orderid'];
		if(empty($ProfileId)){
			$this->returnCodeError(L_('参数有误'));
		}
		
		//获取会员卡信息
		import('@.ORG.third_custom.baodi.baodiCard');
		$baodiCard = new baodiCard();
		$return = $baodiCard->getCardById($ProfileId);
		if($return['MessageNo'] == '-1'){
			$this->returnCodeError($return['Message']);
		}
		
		$nowOrder = M('Baodi_credit_order')->where(['orderid' => $orderid])->find();
		$order_redirect_url = $this->config['site_url'].'/wap.php?c=Pay&a=return_url&pay_type=baodi_card&orderid='.$orderid;
		if($nowOrder['pay_time']){
			//先异步通知，再同步跳转
			import('ORG.Net.Http');
			$http = new Http();
			$notice_result = $http->curlGet($order_redirect_url);
			fdump_sql([$order_redirect_url, $notice_result, 1], 'baodi_notice_result');
			$this->returnCodeOk($order_redirect_url);
		}
		
		
		if($return['Rows']['Row']['Balance'] < $nowOrder['order_money']){
			fdump_api([$return ,$nowOrder], 'baodi_payCard_error');
			$this->returnCodeError(L_('余额不够支付订单'));
		}
		
		$return = $baodiCard->transCardBalance($ProfileId, $nowOrder['order_money'] * -1, $nowOrder['order_name']);
		if($return['MessageNo'] == '-1'){
			$this->returnCodeError($return['Message']);
		}else if($return['MessageNo'] == '1'){
			$data_order = array(
				'more_response'	 => serialize($return),
				'pay_time' 		 => time(),
				'profile_id' 	 => $ProfileId,
			);
			if(M('Baodi_credit_order')->where(['orderid' => $orderid])->data($data_order)->save()){
				//先异步通知，再同步跳转
				import('ORG.Net.Http');
				$http = new Http();
				$notice_result = $http->curlGet($order_redirect_url);
				fdump_sql([$order_redirect_url, $notice_result, 2], 'baodi_notice_result');
				$this->returnCodeOk($order_redirect_url);
			}
		}else{
			$this->returnCodeError('未知错误，请重试。');
		}
		
		
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
}
