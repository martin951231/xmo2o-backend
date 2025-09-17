<?php
/*
 * nmgzhcs 操作
 * Created by vscode.
 * Author: hengtingmei
 * Date Time: 2020/6/17 14:56
 */
namespace hook;
class nmgzhcs{
	// param      print 打印机信息，数组      msg 打印信息，文本
	public function store_print_before($param){
		if($param['print']['mcode'] < 700000 || $param['print']['mcode'] > 799999){
			return false;
		}
		
		$appid = C('config.nmgzhcs_appid') ;
		$appsecret = C('config.nmgzhcs_appsecret');

		$url = C('config.nmgzhcs_base_url').'/api/businessshop/xzpush';
		
		$now_merchant = M('Merchant')->where(['mer_id'=>$param['print']['mer_id']])->find();
		
		if(empty($now_merchant['shop_openid'])){
			return false;
		}
		
		$params=array(
			'appid'=>$appid,
			'shop_openid'=>$now_merchant['shop_openid'],
			'content'=>json_encode([
				'printContent'=>$param['msg'], 
				'voiceTpic'=>'',
				'xzOrderLink'=>'',
				'xzOrderId'=> strval(time() . mt_rand(0,1000000)),
			]),
		);
		ksort($params);
		$paramsJoined = array();
		foreach($params as $param => $value) {
			$paramsJoined[] = "$param=$value";
		}
		$paramData = implode('&', $paramsJoined);

		$sign = strtoupper(md5($paramData.$appsecret));

		$params['sign'] = $sign;

		$res = httpRequest($url,'POST',$params);
		$res = json_decode($res[1],true);
		fdump($url,'store_print_before',true);
		fdump($params,'store_print_before',true);
		fdump($res,'store_print_before',true);
		
		return true;
	}
	
	// param 店员信息、推送信息
	public function msg_staff_push_before($param){
		$appid = C('config.nmgzhcs_appid') ;
		$appsecret = C('config.nmgzhcs_appsecret');

		$url = C('config.nmgzhcs_base_url').'/api/businessshop/xzpush';
		
		$now_merchant = M('Merchant')->where(['mer_id'=>$param['staff']['token']])->find();
		
		if(empty($now_merchant['shop_openid'])){
			return false;
		}
		
		$params=array(
			'appid'=>$appid,
			'shop_openid'=>$now_merchant['shop_openid'],
			'content'=>json_encode([
				'printContent'=>'', 
				'voiceTpic'=>$param['app_data']['message']['msg_content'],
				'xzOrderLink'=>$param['app_data']['message']['extras']['js_url'],
				'xzOrderId'=> strval(time() . mt_rand(0,1000000)),
			]),
		);
		ksort($params);
		$paramsJoined = array();
		foreach($params as $param => $value) {
			$paramsJoined[] = "$param=$value";
		}
		$paramData = implode('&', $paramsJoined);

		$sign = strtoupper(md5($paramData.$appsecret));

		$params['sign'] = $sign;

		$res = httpRequest($url,'POST',$params);
		$res = json_decode($res[1],true);
		fdump($url,'staff_push',true);
		fdump($params,'staff_push',true);
		fdump($res,'staff_push',true);
		
		return true;
	}
}
?>