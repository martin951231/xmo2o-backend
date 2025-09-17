<?php
/*
 * 微信access_token
 *
 */
class WxAccessTokenAction extends BaseAction{
    public function index(){
		header("Content-type: application/json");
		if($_POST['token'] == $this->config['site_token']){
			$res = D('Access_token_expires')->get_access_token();
			if(!$res['errcode']){
				exit(json_encode(array('code' => 200, 'msg' => '获取成功', 'data' => $res['access_token'])));
			}else{
				exit(json_encode(array('code' => 201, 'msg' => $res['errmsg'], 'data' => '')));
			}
		}else{
			exit(json_encode(array('code' => 201, 'msg' => '获取失败，密钥错误', 'data' => '')));
		}
    }
}