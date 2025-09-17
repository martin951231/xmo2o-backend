<?php
/*
 * 润雅解码
 *
 */
class RunyaAction extends BaseAction{
	public function index(){
		if(empty($_SESSION['user'])){
			if($_SERVER['HTTP_REFERER']){
				$referer = $_SERVER['HTTP_REFERER'];
			}else if($_SERVER['PATH_INFO']){
				$referer = $this->config['site_url'].$_SERVER['PATH_INFO'];
			}else{
				$referer = $this->config['site_url'].'/runya/'.$_GET['code'];
			}
			redirect($this->config['site_url'].'/wap.php?c=Login&a=index&referer='.urlencode($referer));

		}
		$uid = $_SESSION['user']['uid'];
		$code = $_GET['code'];
		$code = str_replace(array('-','_',''),array('+','/','='),$code);
		$private = $this->config['runya_private_key'];
		$private_key = "-----BEGIN RSA PRIVATE KEY-----\n".wordwrap($private, 64, "\n", true) ."\n-----END RSA PRIVATE KEY-----";
		$pi_key =  openssl_pkey_get_private($private_key);
		openssl_private_decrypt(base64_decode($code),$message,$pi_key);

		$message = explode('&',$message);
		//订单号&商户标识&实际支付金额&用户标识&支付方式&订单时间戳

		$order_id = $message[0];
		$mer_code = $message[1];
		$payment_money = $message[2]/100;
		$user_code = $message[3];
		$pay_type = $message[4];
		$pay_time = $message[5];
		$where['uid'] = $uid;
		$where['user_no'] = $user_code;
		$where_mer['_string'] = "pos_store_no='{$mer_code}' OR yxlm_store_no='{$mer_code}' OR syt_store_no = '{$mer_code}'";
		$mer_store = M('Ao_merchant_store_ext')->where($where_mer)->find();

		if($user = M('Ao_user_from')->where($where)->find()){
			$where['user_from'] = $pay_type;
			if(M('Ao_user_from')->where($where)->find()){

			}else{
				$date['uid'] = $user['uid'];
				$date['user_no'] = $user_code;
				$date['user_from'] = $pay_type;
				$date['create_time'] = $pay_time;
				M('Ao_user_from')->add($date);
			}
		}else{
			$date['uid'] = $uid;
			$date['user_no'] = $user_code;
			$date['user_from'] = $pay_type;
			$date['create_time'] = $pay_time;
			M('Ao_user_from')->add($date);
		}

		$socre_rule = M('Ao_user_score_type')->where(array('user_score_type_id'=>$mer_store['user_score_type_id']))->find();
		if($socre_rule){
			$score = $payment_money*$socre_rule['point_ratio'];
		}else{
			if($this->config['open_score_get_percent']==1){
				$this->config['score_get'] = $this->config['score_get_percent']/100;
			}else{
				$this->config['score_get'] =  $this->config['user_score_get'];
			}
			$score = $payment_money*$this->config['score_get'] ;
		}

		$score = D('Percent_rate')->get_user_add_score_times($score,$uid);
		$score = sprintf("%.2f",floatval($score));
		$res = D('User')->add_score($uid,$score,'支付后扫码获得积分',0,array('mer_id'=>$mer_store['mer_id'],'store_id'=>$mer_store['store_id']));
		if($res['error_code']){
			redirect($this->config['site_url'].'/wap.php?g=Wap&c=My&a=index');
		}else{
			redirect($this->config['site_url'].'/wap.php?g=Wap&c=Gift&a=success_pointer&score='.$score);
		}
	}

	//商盈通端有消费通知我端，扣减用户余额
	public function consume(){
		$body = file_get_contents('php://input');
		$input = $this->simplest_xml_to_array($body);
		$user_model = D("User");
		$syt = D("UserShangyingtong");
		$sytdj = M("UserShangyingtongConsume");
		if($input['BODY']['CARD_ID']){
			$now_user = $syt->where("card_id = '".$input['BODY']['CARD_ID']."' and errcode = ''")->order('add_time asc')->field("uid")->find();			
		}
		if($now_user['uid']){
			if($input['BODY']['TRANS_TYPE'] == '100001'){
				//消费
				if($input['BODY']['TRANS_AMT'] > 0){
					$money = $input['BODY']['TRANS_AMT']/100;
					$user_model->user_money($now_user['uid'],$money, '商盈通pos消费',0,0,1);
				}
			}
		}
		if(($input['BODY']['TRANS_TYPE'] == '100002' || $input['BODY']['TRANS_TYPE'] == '100003') && $input['BODY']['TRANS_SN'] != ''){
			//消费撤销\冲正
			$last = $sytdj->where('REQ_SN="'.$input['BODY']['TRANS_SN'].'"')->field("TRANS_AMT,CARD_ID")->find();
			if($last){
				$now_user = $syt->where("card_id = '".$last['CARD_ID']."' and errcode = ''")->order('add_time asc')->field("uid")->find();
				if($now_user['uid']){
					//给用户加钱
					if($last['TRANS_AMT'] > 0){
						$money = $last['TRANS_AMT']/100;
						$user_model->add_money($now_user['uid'],$money, '商盈通pos消费'.($input['BODY']['TRANS_TYPE'] == '100002' ? "撤销" : "冲正"),0,0,0);
					}
				}
			}
		}
		$insert = array(
			'REQ_SN' => $input['INFO']['REQ_SN'],
			'TRANS_AMT' => $input['BODY']['TRANS_AMT'],
			'TRANS_DATE' => $input['BODY']['TRANS_DATE'],
			'SUBMIT_TIME' => $input['BODY']['SUBMIT_TIME'],
			'RESERVER1' => $input['BODY']['RESERVER1'],
			'RESERVER2' => $input['BODY']['RESERVER2'],
			'TRANS_TYPE' => $input['BODY']['TRANS_TYPE'],
			'CARD_ID' => $input['BODY']['CARD_ID'],
			'CARD_ISSUER' => $input['BODY']['CARD_ISSUER'],
			'ID_' => $input['BODY']['ID'],
			'TRANS_SN' => $input['BODY']['TRANS_SN'],
			'MERCHANT_ID' => $input['BODY']['MERCHANT_ID'],
			'TERMINAL_ID' => $input['BODY']['TERMINAL_ID'],
			'SYS_TRACE_NUM' => $input['BODY']['SYS_TRACE_NUM']
		);
		$sytdj->data($insert)->add();
		header("Content-type: application/xml");
		echo '<TIS>
			      <BODY>
			         <CARD_ID>'.$input['BODY']['CARD_ID'].'</CARD_ID>
			         <CARD_ISSUER>'.$input['BODY']['CARD_ISSUER'].'</CARD_ISSUER>
			         <ID>'.$input['BODY']['ID'].'</ID>
			         <TRANS_SN>'.$input['BODY']['TRANS_SN'].'</TRANS_SN>
			         <TRANS_TYPE>'.$input['BODY']['TRANS_TYPE'].'</TRANS_TYPE>
			         <SUBMIT_TIME>'.date('YmdHis').'</SUBMIT_TIME>
			      </BODY>
			     <INFO>
			         <REQ_SN>'.$input['INFO']['REQ_SN'].'</REQ_SN>
					 <RET_CODE>0000</RET_CODE>
					 <ERR_MSG>成功</ERR_MSG>
			      </INFO>
			</TIS>';
			exit;
	}

	public function simplest_xml_to_array($xmlstring) {
	    return json_decode(json_encode((array) simplexml_load_string($xmlstring)), true);
	}
};