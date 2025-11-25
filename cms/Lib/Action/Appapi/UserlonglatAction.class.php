<?php
//保存用户的地理位置
class UserlonglatAction extends BaseAction{
	public function report(){
		if($this->_uid){
			if($_POST['type'] == 'gcj02'){
				$_POST['type'] = '3';
			}else if($_POST['type'] == 'wgs84'){
				$_POST['type'] = '1';
			}else if($_POST['type'] == 'gps'){
				$_POST['type'] = '1';
			}else if($_POST['type'] == 'baidu'){
				$_POST['type'] = '5';
			}
			
			isset($_POST['lng']) && $_POST['lng'] = floatval($_POST['lng']);
			isset($_POST['lat']) && $_POST['lat'] = floatval($_POST['lat']);

			$now_user = D('User')->get_user($this->_uid);
			if(($now_user['openid'] || $now_user['wxapp_openid']) && $_POST['lng'] && $_POST['lat'] && $_POST['type']){
				//小程序openid和公众号openid都保存
				if($now_user['openid']){
					$openid = $now_user['openid'];
				}else{
					$openid = $now_user['wxapp_openid'];
				}
				D('User_long_lat')->saveLocation($openid, $_POST['lng'], $_POST['lat'], $_POST['type']);
			}
		}
		$this->returnCodeOk('ok');
	}
}
?>