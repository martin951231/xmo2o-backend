<?php
/*
 * 【o2o.zhuzhousmartcity.com株洲国投智慧城市的统一登录接口对接】
https://www.tapd.cn/47145533/prong/stories/view/1147145533001002851
 *
 */
class ZhuzhousmartcityAction extends BaseAction {

	public function index(){
		$session_id = trim(I("session_id"));
		if(empty($session_id)){
			die("Not find session_id");
		}
		//获取诸事通当前登录用户
		$http_api = "http://my.zhuzhou.gov.cn/wst-app/other/getUser";
		$md5salt = "abc123";

		$_checksum_ = $this->create_checksum($session_id, $md5salt);
		$param = [
			'session_id' => $session_id,
			'_checksum_' => $_checksum_
		];
		import('ORG.Net.Http');
		$http = new Http();
		$return = Http::curlPost($http_api, $param);
		if($return['errcode'] == '0' && $return['code'] == '1000' && $return['data'] && $return['data']['id']){
			$third_uid = $return['data']['id'];
			$now_user = D('User')->get_user($third_uid,'third_uid');
			$data_user = [];
			$data_user['nickname'] 		= $return['data']['realname'] ? : '';
			$data_user['phone'] 		= $return['data']['phone'] ? : '';
			if($now_user){
				$condition_user['uid'] 		= $now_user['uid'];
				D('User')->where($condition_user)->data($data_user)->save();
			}
			else{
				$data_user['now_money'] 	= 0;
				$data_user['score_count'] 	= 0;
				$data_user['pwd'] 			= '';
				$data_user['avatar'] 		= '';
				$data_user['third_uid'] 	= $third_uid;

				$reg_result = D('User')->autoreg($data_user);
				if($reg_result['error_code']){
					die($reg_result['msg']);
				}
				$now_user = D('User')->get_user($reg_result['uid'],'uid');
			}
			$login_result = D('User')->autologin('uid',$now_user['uid']);
			if($login_result['error_code']){
				die($login_result['msg']);
			}else{
				$now_user = $login_result['user'];
				session('user',$now_user);
				$this->user_session = $now_user;
				$device_id = 'packapp';
				$ticket = ticket::create($now_user['uid'], $device_id, true);
				setcookie('user_device_id', $device_id, time() + 7*86400, '/');
                setcookie('user_ticket', $ticket,  time() + 7*86400, '/');
                setcookie('login_status', 'login',  time() + 7*86400, '/');
                redirect($this->config['site_url']);
			}
		}
		else{
			die("Login error...");
		}
	}

	/**
	 * 获取校验格式的字符串
	 * @param  [type] $session_id [description]
	 * @param  [type] $md5salt    [description]
	 * @return [type]             [description]
	 */
	private function create_checksum($session_id, $md5salt){
		return md5('session_id='.$session_id.date('Ymd').','.$md5salt);
	}
}