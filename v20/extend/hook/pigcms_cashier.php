<?php
/*
 *
 * pigcms_cashier 操作
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/6/17 14:56
 *
 *INSERT INTO `pigcms_hook` (`hook_id`, `hook_exec`, `is_plugin`, `plugin_name`, `file`, `function`, `desc`) VALUES
(10001,	'user.get_user_before',	0,	'',	'hook.pigcms_cashier',	'get_user_before',	'智慧店铺对接余额积分接口'),
(10002,	'user.add_user_money_after',	0,	'',	'hook.pigcms_cashier',	'add_user_money_after',	'智慧店铺对接余额积分接口'),
(10003,	'user.use_user_money_after',	0,	'',	'hook.pigcms_cashier',	'use_user_money_after',	'智慧店铺对接余额积分接口'),
(10004,	'user.add_user_score_after',	0,	'',	'hook.pigcms_cashier',	'add_user_score_after',	'智慧店铺对接余额积分接口'),
(10005,	'user.use_user_score_after',	0,	'',	'hook.pigcms_cashier',	'use_user_score_after',	'智慧店铺对接余额积分接口');
*INSERT INTO `pigcms_config` (`name`, `type`, `value`, `info`, `desc`, `tab_id`, `tab_name`, `gid`, `sort`, `status`, `is_lang`, `value_english`, `value_traditional`, `value_korean`) VALUES
('butt_pigcms_cashier_token',	'',	'',	'',	'',	'0',	'',	0,	0,	1,	0,	'',	'',	''),
('butt_pigcms_cashier_url',	'',	'https://k.pigcms.com.cn/',	'',	'',	'0',	'',	0,	0,	1,	0,	'',	'',	'');
 *
 */
namespace hook;
use net\Http;
use app\common\model\service\UserService as UserService;
class pigcms_cashier{
	public function __construct(){
		if(!cfg('butt_pigcms_cashier_token') || !cfg('butt_pigcms_cashier_url')){
			return array('error_code' => true, 'msg' => '初始化错误');
		}
	}
	public function get_user_before($param){
		$post_param = [
			'token'	=> cfg('butt_pigcms_cashier_token'),
			'phone'	=> $param['user']['phone'],
		];
		
		$post_url = cfg('butt_pigcms_cashier_url') . 'cashier/merchants.php?m=Api&c=cashierApi&a=getUserByTel';
		$http = new Http();
		$return = $http->curlPostOwnWithHeader($post_url,json_encode($post_param),['Content-Type: application/json']);
		$returnArr = json_decode($return,true);
		
		if($returnArr['error'] == 0 && $returnArr['msg'] == 'SUCCESS'){
			$condition_user['uid'] = $param['uid'];
			$data_user = array(
				'score_count' 	=> $returnArr['data']['points'],
				'now_money' 	=> $returnArr['data']['money'],
			);
			
			if(!(new UserService())->UpdateUser($condition_user,$data_user)){
				return array('error_code' => true, 'msg' => '用户信息 查询保存失败！请联系管理员协助解决。');
			}
			
		}else{
			return array('error_code' => true, 'msg' => '查询发生错误：'.$return['msg']);
		}
		return array('error_code' => false, 'msg' => 'ok');
	}
	
	public function add_user_money_after($param){
		$post_param = [
			'token'	=> cfg('butt_pigcms_cashier_token'),
			'phone'	=> $param['user']['phone'],
			'money'	=> $param['money'],
			'isfrom'=> '1',
			'oldMoney'=> $param['user']['now_money'],
			'desc'=> $param['desc'],
		];
		
		$post_url = cfg('butt_pigcms_cashier_url') . 'cashier/merchants.php?m=Api&c=cashierApi&a=uptUserMoney';
		$http = new Http();
		$return = $http->curlPostOwnWithHeader($post_url,json_encode($post_param),['Content-Type: application/json']);
		$returnArr = json_decode($return,true);
		
		if($returnArr['error'] == 0 && $returnArr['msg'] == 'SUCCESS'){
			return array('error_code' => false, 'msg' => 'ok');
		}else{
			return array('error_code' => true, 'msg' => '增加用户余额发生错误：'.$return['msg']);
		}
		
	}
	
	public function use_user_money_after($param){
		$post_param = [
			'token'	=> cfg('butt_pigcms_cashier_token'),
			'phone'	=> $param['user']['phone'],
			'money'	=> $param['money'],
			'isfrom'=> '2',
			'oldMoney'=> $param['user']['now_money'],
			'desc'=> $param['desc'],
		];
		
		$post_url = cfg('butt_pigcms_cashier_url') . 'cashier/merchants.php?m=Api&c=cashierApi&a=uptUserMoney';
		$http = new Http();
		$return = $http->curlPostOwnWithHeader($post_url,json_encode($post_param),['Content-Type: application/json']);
		$returnArr = json_decode($return,true);
		
		if($returnArr['error'] == 0 && $returnArr['msg'] == 'SUCCESS'){
			return array('error_code' => false, 'msg' => 'ok');
		}else{
			return array('error_code' => true, 'msg' => '减少用户余额发生错误：'.$return['msg']);
		}
	}
	
	public function add_user_score_after($param){
		$post_param = [
			'token'	=> cfg('butt_pigcms_cashier_token'),
			'phone'	=> $param['user']['phone'],
			'points'	=> $param['score'],
			'isfrom'=> '1',
			'oldPoints'=> $param['user']['score_count'],
			'desc'=> $param['desc'],
		];
		
		$post_url = cfg('butt_pigcms_cashier_url') . 'cashier/merchants.php?m=Api&c=cashierApi&a=uptUserPoint';
		$http = new Http();
		$return = $http->curlPostOwnWithHeader($post_url,json_encode($post_param),['Content-Type: application/json']);
		$returnArr = json_decode($return,true);
		
		if($returnArr['error'] == 0 && $returnArr['msg'] == 'SUCCESS'){
			return array('error_code' => false, 'msg' => 'ok');
		}else{
			return array('error_code' => true, 'msg' => '增加用户积分发生错误：'.$return['msg']);
		}
	}
	
	public function use_user_score_after($param){
		$post_param = [
			'token'	=> cfg('butt_pigcms_cashier_token'),
			'phone'	=> $param['user']['phone'],
			'points'=> $param['score'],
			'isfrom'=> '2',
			'oldPoints'=> $param['user']['score_count'],
			'desc'=> $param['desc'],
		];
		
		$post_url = cfg('butt_pigcms_cashier_url') . 'cashier/merchants.php?m=Api&c=cashierApi&a=uptUserPoint';
		$http = new Http();
		$return = $http->curlPostOwnWithHeader($post_url,json_encode($post_param),['Content-Type: application/json']);
		$returnArr = json_decode($return,true);
		
		if($returnArr['error'] == 0 && $returnArr['msg'] == 'SUCCESS'){
			return array('error_code' => false, 'msg' => 'ok');
		}else{
			return array('error_code' => true, 'msg' => '减少用户积分发生错误：'.$return['msg']);
		}
	}
}
?>