<?php
/*uni 操作

 * */
class uni_user{
	public function select_score($uid){
		if(is_array($uid)){
			$uid = $uid['uid'];
		}
		$now_user = M('User')->where(['uid'=>$uid])->find();
		if(!$now_user['third_uid']){
			return array('error_code' => true, 'msg' => '用户不是对接而来的');
		}
		$post_param = [
			'ReqCont'	=> [
				'ID' => strval(time()),
				'SC' => 'http://web-bus/queryCreditRest',
				'SID' => 'SMALL_PIG',
				'DID' => 'O2O',
				'RT'  => date('Y-m-d H:i:s'),
			],
			'SvcCont' => [
				'SP' => 'queryCreditService',
				'SOO'=> [
					'CUST_PHONE' => $now_user['phone'],
				]
			]
		];
		$post_param['ReqCont']['SS'] = md5($post_param['ReqCont']['SC'] . $post_param['ReqCont']['RT'] . $post_param['ReqCont']['SID']);
		
		$post_url = C('config.uni_url') . 'web-bus/integralQuery/integral';
		import('ORG.Net.Http');
		$http = new Http();
		
		$return = $http->curlPostOwnWithHeader($post_url,json_encode($post_param),['Content-Type: application/json']);
		$returnArr = json_decode($return,true);
		fdump($post_url,'uni_score_select',true);
		fdump($post_param,'uni_score_select',true);
		fdump($returnArr,'uni_score_select',true);
		if($returnArr['SvcCont']['RC'] == '1000'){
			$condition_user['uid'] = $uid;
			$data_user = array(
				'score_count' 	=> $returnArr['SvcCont']['CREDIT_DATA']['CURRENT_CREDIT']
			);
			if(D('User')->where($condition_user)->data($data_user)->save() === false){
				return array('error_code' => true, 'msg' => '用户信息 查询保存失败！请联系管理员协助解决。');
			}
			
		}else{
			return array('error_code' => true, 'msg' => '查询发生错误：'.$return['respContent']);
		}
		return array('error_code' => false, 'msg' => 'ok');
	}
	public function add_score($uid,$score,$desc){
		$now_user = M('User')->where(['uid'=>$uid])->find();
		if(!$now_user['third_uid']){
			return array('error_code' => true, 'msg' => '用户不是对接而来的');
		}
		$post_param = [
			'ReqCont'	=> [
				'ID' => strval(time()),
				'SC' => 'http://web-bus/updateCreditRest',
				'SID' => 'SMALL_PIG',
				'DID' => 'O2O',
				'RT'  => date('Y-m-d H:i:s'),
			],
			'SvcCont' => [
				'SP' => 'updateCreditService',
				'SOO'=> [
					'CUST_PHONE' => $now_user['phone'],
					"CREDIT_USE_TIME"=> "",
					"CREDIT_EXPIRE_TIME"=> "2099-12-31",
					"ORDER_NUM"=> strval(time() . $uid),
					"CREDIT_TYPE"=> "1",
					"CREDIT_VALUE"=> $score,
					"NOTE"=> $desc
				]
			]
		];
		$post_param['ReqCont']['SS'] = md5($post_param['ReqCont']['SC'] . $post_param['ReqCont']['RT'] . $post_param['ReqCont']['SID']);
		
		$post_url = C('config.uni_url') . 'web-bus/updateCreditRest/updateCreditService';
		import('ORG.Net.Http');
		$http = new Http();

		$return = $http->curlPostOwnWithHeader($post_url,json_encode($post_param),['Content-Type: application/json']);
		$returnArr = json_decode($return,true);
		fdump($post_url,'uni_score_add',true);
		fdump($post_param,'uni_score_add',true);
		fdump($returnArr,'uni_score_add',true);
		if($returnArr['SvcCont']['RC'] != '1000'){
			return array('error_code' => true, 'msg' => '增加积分发生错误：'.$returnArr['SvcCont']['MSG']);
		}
		return array('error_code' => false, 'msg' => 'ok');
	}
	public function use_score($uid,$score,$desc){
		$now_user = M('User')->where(['uid'=>$uid])->find();
		if(!$now_user['third_uid']){
			return array('error_code' => true, 'msg' => '用户不是对接而来的');
		}
		$post_param = [
			'ReqCont'	=> [
				'ID' => strval(time()),
				'SC' => 'http://web-bus/updateCreditRest',
				'SID' => 'SMALL_PIG',
				'DID' => 'O2O',
				'RT'  => date('Y-m-d H:i:s'),
			],
			'SvcCont' => [
				'SP' => 'updateCreditService',
				'SOO'=> [
					'CUST_PHONE' => $now_user['phone'],
					'CREDIT_USE_TIME' => date('Y-m-d H:i:s'),
					'CREDIT_EXPIRE_TIME' => '',
					'CREDIT_TYPE' => '3',
					'CREDIT_VALUE' => $score,
					'NOTE' => $desc,
					'ORDER_NUM' => strval(time() . $uid),
				]
			]
		];
		$post_param['ReqCont']['SS'] = md5($post_param['ReqCont']['SC'] . $post_param['ReqCont']['RT'] . $post_param['ReqCont']['SID']);
		
		$post_url = C('config.uni_url') . 'web-bus/updateCreditRest/updateCreditService';

		import('ORG.Net.Http');
		$http = new Http();
		$return = $http->curlPostOwnWithHeader($post_url,json_encode($post_param),['Content-Type: application/json']);
		$returnArr = json_decode($return,true);
		fdump($post_url,'uni_score_use',true);
		fdump($post_param,'uni_score_use',true);
		fdump($returnArr,'uni_score_use',true);
		dump($returnArr['SvcCont']);
		if($returnArr['SvcCont']['RC'] != '1000'){
			return array('error_code' => true, 'msg' => '扣除积分发生错误：'.$returnArr['SvcCont']['MSG']);
		}
		return array('error_code' => false, 'msg' => 'ok');
	}
}
?>