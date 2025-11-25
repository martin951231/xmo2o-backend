<?php
/*
 *
 * 人人店api对接 （积分同步） 点点客系统
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/6/17 14:56
 *
 *INSERT INTO `pigcms_hook` (`hook_id`, `hook_exec`, `is_plugin`, `plugin_name`, `file`, `function`, `desc`) VALUES
(20003,	'user.get_user_before',	0,	'',	'hook.renrendian',	'get_user_before',	'人人店积分查询'),
(20001,	'user.add_user_score_after',	0,	'',	'hook.renrendian',	'add_user_score_after',	'人人店积分同步'),
(20002,	'user.use_user_score_after',	0,	'',	'hook.renrendian',	'use_user_score_after',	'人人店积分同步');
 *
 *
 */
namespace hook;
use net\Http;
use app\common\model\service\UserService as UserService;
class renrendian{
	protected $gateway_url = 'http://apis.wxrrd.com/router/rest';
	protected $token_url = 'http://apis.wxrrd.com/token';
	protected $redirect_uri = 'https://o2o.xiaoaibuluo.com/index.php?c=Ibmall&a=index';
	protected $code = '6f06f9f5416bcf54';//这个需要登录点点客的后台来获取code
	public function __construct(){
		if(!cfg('diandianke_appid') || !cfg('diandianke_secret')){
			return array('error_code' => true, 'msg' => '初始化错误');
		}
	}

	//查询积分
	public function get_user_before($param){
		//先获取memberid
		$member_id = $this->getMemberid($param);
		if(!$member_id){ 
			return array('error_code' => true, 'msg' => '没有获取到member_id');
		}
		$access_token = $this->getAccessToken();
		if(empty($access_token)){
			fdump("yyyy", "lumin_addscore",1);
			return array('error_code' => true, 'msg' => '没有获取到access_token');
		}
		$post_param = [
			'appid' => cfg('diandianke_appid'),
			'secret' => cfg('diandianke_secret'),
			'method' => 'weiba.wxrrd.user.get',
			'timestamp' => date('Y-m-d H:i:s'),
			'access_token' => $access_token,
			'member_id' => $member_id
		];
		$post_param['sign'] = $this->getSign($post_param);
		$http = new Http();
		$return = $http->curlGet($this->gateway_url."?".$this->get_param($post_param));
		$returnArr = json_decode($return, true);
		if($returnArr['errCode'] == '0'){
			$condition_user['uid'] = $param['uid'];
			$data_user = array(
				'score_count' 	=> $returnArr['data']['credit'],
			);
			if(!(new UserService())->UpdateUser($condition_user,$data_user)){
				return array('error_code' => true, 'msg' => '用户信息 查询保存失败！请联系管理员协助解决。');
			}
			return array('error_code' => false, 'msg' => 'ok');
		}
		else{
			return array('error_code' => true, 'msg' => '获取用户积分发生错误，错误码：'.$returnArr['errCode']);
		}
	}

	//获取accesstoken
	private function getAccessToken(){
		$token = M("Diandianke_token")->where(['expiresd' => ['gt', time()+20]])->order("id desc")->find();
		if($token){
			return $token['token'];
		}
		else{
			$last_token = M("Diandianke_token")->order("id desc")->find();
			if($ref_token){
				$ref_token = $last_token['ref_token'];
			}
		}
		$post_param = [
			'appid'	=> cfg('diandianke_appid'),
			'secret'	=> cfg('diandianke_secret'),
			'grant_type'	=> $ref_token ? 'refresh_token' : 'authorization_code',
			'code'=> $this->code,
			'redirect_uri'=> $this->redirect_uri
		];
		if($ref_token){
			$post_param['refresh_token'] = $ref_token;
		}
		$http = new Http();
		$return = $http->curlPostOwn($this->token_url,$post_param);
		$returnArr = json_decode($return, true);
		if($returnArr['errCode'] || !$returnArr['access_token']){
			return "";
		}
		M("Diandianke_token")->data(['token'=>$returnArr['access_token'], 'ref_token'=> $returnArr['refresh_token'], 'expiresd'=> time()+$returnArr['expiresd']])->add();
		return $returnArr['access_token'];
	}

	private function getSign($arr){
		$signPars = "";
		ksort($arr);
		foreach($arr as $k => $v) {
			$signPars .= $k . "=" . $v . "&";
		}
		$signPars = substr($signPars,0,-1);
	
		return strtoupper(md5($signPars));
	}

	private function get_param($data){
		$temp = [];
		foreach($data as $k => $v) {
			$temp[] = $k . "=" . $v;
		}
		return implode('&', $temp);
	}

	//获取用户memberid
	private function getMemberid($param){
		if(empty($param['user']['phone'])) return false;
		//获取accesstoken
		$access_token = $this->getAccessToken();
		if(empty($access_token)) return false;
		$post_param = [
			'appid' => cfg('diandianke_appid'),
			'secret' => cfg('diandianke_secret'),
			'method' => 'weiba.wxrrd.credit.lists',
			'timestamp' => date('Y-m-d H:i:s'),
			'access_token' => $access_token,
			'mobile' => $param['user']['phone'],
			'startDate' => date('Y-m-d H:i:s', strtotime('-3 years')),//就查三年以内的， 三年以内都没有记录，那估计就没有了
			'endDate' => date('Y-m-d H:i:s')
		];
		$post_param['sign'] = $this->getSign($post_param);
		$http = new Http();
		$return = $http->curlGet($this->gateway_url."?".$this->get_param($post_param));
		$returnArr = json_decode($return, true);
		if($returnArr['errCode'] == '0' && $returnArr['data']['data'][0]['member_id'] > 0){
			return $returnArr['data']['data'][0]['member_id'];
		}
		return false;
	}

	//增加用户积分
	public function add_user_score_after($param){
		fdump($param, "lumin_addscore",1);
		//先获取memberid
		$member_id = $this->getMemberid($param);
		if(!$member_id){ 
			return array('error_code' => true, 'msg' => '没有获取到member_id');
		}
		$access_token = $this->getAccessToken();
		if(empty($access_token)){
			fdump("yyyy", "lumin_addscore",1);
			return array('error_code' => true, 'msg' => '没有获取到access_token');
		}
		$post_param = [
			'appid' => cfg('diandianke_appid'),
			'secret' => cfg('diandianke_secret'),
			'method' => 'weiba.wxrrd.credit.edits',
			'timestamp' => date('Y-m-d H:i:s'),
			'access_token' => $access_token,
			'member_id' => $member_id,
			'credit_num' => $param['score'],
			'credit_op_type' => 0//0=加积分 1=减积分
		];
		$post_param['sign'] = $this->getSign($post_param);
		$http = new Http();
		$return = $http->curlGet($this->gateway_url."?".$this->get_param($post_param));
		$returnArr = json_decode($return, true);
		if($returnArr['errCode'] == '0'){
			return array('error_code' => false, 'msg' => 'ok');
		}
		else{
			return array('error_code' => true, 'msg' => '增加用户积分发生错误，错误码：'.$returnArr['errCode']);
		}
	}

	//扣减用户积分
	public function use_user_score_after($param){
		//先获取memberid
		$member_id = $this->getMemberid($param);
		if(!$member_id) return array('error_code' => true, 'msg' => '没有获取到member_id');
		$access_token = $this->getAccessToken();
		if(empty($access_token)) return array('error_code' => true, 'msg' => '没有获取到access_token');
		$post_param = [
			'appid' => cfg('diandianke_appid'),
			'secret' => cfg('diandianke_secret'),
			'method' => 'weiba.wxrrd.credit.edits',
			'timestamp' => date('Y-m-d H:i:s'),
			'access_token' => $access_token,
			'member_id' => $member_id,
			'credit_num' => $param['score'],
			'credit_op_type' => 1//0=加积分 1=减积分
		];
		$post_param['sign'] = $this->getSign($post_param);
		$http = new Http();
		$return = $http->curlGet($this->gateway_url."?".$this->get_param($post_param));
		$returnArr = json_decode($return, true);
		if($returnArr['errCode'] == '0'){
			return array('error_code' => false, 'msg' => 'ok');
		}
		else{
			return array('error_code' => true, 'msg' => '扣减用户积分发生错误，错误码：'.$returnArr['errCode']);
		}
	}
}
?>