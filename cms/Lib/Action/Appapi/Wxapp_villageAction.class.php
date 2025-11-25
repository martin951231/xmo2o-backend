<?php
/*
 * 商家小程序
 *
 */
class Wxapp_villageAction extends BaseAction {
	public function code_login(){
		$now_bind = M('Weixin_app_bind')->where(array('bind_type'=>1,'other_id'=>$_POST['wxapp_own_village_id']))->find();
		
		$appid = $now_bind['appid'];
		$appsecret = $now_bind['appsecret'];
		
		if(!$appid || !$appsecret){
			$this->returnCodeError('小程序沒有配置appid或appsecret，請在物业后台配置');
		}
		
		import('ORG.Net.Http');
		$http = new Http();

		$return = Http::curlPost('https://api.weixin.qq.com/sns/jscode2session?appid='.$appid.'&secret='.$appsecret.'&js_code='.$_POST['code'].'&grant_type=authorization_code', array());
		
		if($return['errcode']){
			$this->returnCodeError($return['errmsg']);
		}
		
		/*优先使用 unionid 登录*/
		if(!empty($return['unionid'])){
			$now_bind_user = D('Weixin_bind_user')->get_village_info($now_bind['other_id'], 'union_id', $return['unionid']);
			if($now_bind_user && $now_bind_user['uid']){
				$now_user = D('User')->get_user($now_bind_user['uid']);
			}
		}else{
			/*再次使用 openid 登录*/
			$now_bind_user = D('Weixin_bind_user')->get_village_info($now_bind['other_id'], 'wxapp_openid', $return['openid']);
			if($now_bind_user && $now_bind_user['uid']){
				$now_user = D('User')->get_user($now_bind_user['uid']);
			}
		}
		if(!empty($now_user)){
			$session_key = $return['session_key'];
			$data_user = array(
				'last_weixin_time' => time(),
				'wxapp_session_key' => $session_key,
			);
			D('User')->where(array('uid'=>$now_user['uid']))->data($data_user)->save();

			$ticket = ticket::create($now_user['uid'],'wxapp', true);
			$return = array(
				'ticket'=>	$ticket['ticket'],
				'user'	=>	$now_user,
				'emptyUser'	=>	false,
			);
		}else{
			$return = array(
				'emptyUser' => true
			);
		}
		$this->returnCode(0,$return);
	}
	
	public function wxapp_login(){
		
		if(!function_exists('mcrypt_module_open')){
		    $this->returnCodeError('mcrypt_module_open PHP模块没有打开，请先打开。');
		}
		
		$now_bind = M('Weixin_app_bind')->where(array('bind_type'=>1,'other_id'=>$_POST['wxapp_own_village_id']))->find();
		
		$appid = $now_bind['appid'];
		$appsecret = $now_bind['appsecret'];
		
		if(!$appid || !$appsecret){
			$this->returnCodeError('小程序沒有配置appid或appsecret，請在物业后台配置');
		}
		
		import('ORG.Net.Http');
		$http = new Http();

		$return = Http::curlPost('https://api.weixin.qq.com/sns/jscode2session?appid='.$appid.'&secret='.$appsecret.'&js_code='.$_POST['code'].'&grant_type=authorization_code', array());
		
		if($return['errcode'] == 40125){
			$this->returnCode($return['errcode'],array(),$return['errmsg']);
		}
		
		
		import('@.ORG.aeswxapp.wxBizDataCrypt');
		
		$pc = new WXBizDataCrypt($appid, $return['session_key']);
		$errCode = $pc->decryptData($_POST['encryptedData'], $_POST['iv'], $data);
		$jsonrt = json_decode($data,true);
		if(!empty($jsonrt['unionId'])){
			$now_bind_user = D('Weixin_bind_user')->get_village_info($now_bind['other_id'],'union_id',$jsonrt['unionId']);
			if($now_bind_user && $now_bind_user['uid']){
				$now_user = D('User')->get_user($now_bind_user['uid']);
			}
		}else{
			$now_bind_user = D('Weixin_bind_user')->get_village_info($now_bind['other_id'],'wxapp_openid',$jsonrt['openId']);
			if($now_bind_user && $now_bind_user['uid']){
				$now_user = D('User')->get_user($now_bind_user['uid']);
			}
		}
		
		//用户信息
		if($_POST['userInfo']){
			$jsonrt['nickName'] = $_POST['userInfo']['nickName'];
			$jsonrt['gender'] = $_POST['userInfo']['gender'];
			$jsonrt['province'] = $_POST['userInfo']['province'];
			$jsonrt['city'] = $_POST['userInfo']['city'];
			$jsonrt['avatarUrl'] = $_POST['userInfo']['avatarUrl'];
		}
		
		if(empty($now_user)){
			/*临时注册用户*/
			$third_login = array(
				'from' 		=> 'wxapp',
				'wxapp_openid' 	=> $jsonrt['openId'],
				'union_id' 	=> ($jsonrt['unionId'] ? $jsonrt['unionId'] : ''),
				'nickname' 	=> $jsonrt['nickName'],
				'sex' 		=> $jsonrt['gender'],
				'province' 	=> $jsonrt['province'],
				'city' 		=> $jsonrt['city'],
				'avatar' 	=> D('User')->down_avatar($jsonrt['avatarUrl'], $jsonrt['unionId'] ? $jsonrt['unionId'] : $jsonrt['openId']),
				'is_follow' => 0,
				'third_auth_time' => time(),
				'wxapp_session_key' => $return['session_key'],
			);
			if($third_id = M('User_tmp_third')->data($third_login)->add()){
				$this->returnCodeOk(['emptyUser'=>false, 'third_id' => $third_id, 'third_from' => $third_login['from']]);
			}else{
				$this->returnCodeError(L_('登录失败，请重试'));
			}
		}else{
			$ticket = ticket::create($now_user['uid'], 'wxapp', true);
			$return = array(
				'ticket'    =>	$ticket['ticket'],
				'user'	    =>	$now_user,
				'emptyUser' =>  false
			);
			D('User')->autologin('uid', $now_user['uid']);
			$this->returnCode(0,$return);
		}
		
	}
}
?>