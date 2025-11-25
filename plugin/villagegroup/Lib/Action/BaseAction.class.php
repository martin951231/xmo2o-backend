<?php
class BaseAction extends Action{
	public $leader_id = null;
	public $_uid = null;
 	public $is_weixin_browser = false;
 	public $is_wxapp_browser = false;
	public $is_alipay_browser = false;
	public $is_alipayapp_browser = false;
	public $is_app_browser =   false;
	protected function _initialize(){
		//基类
		$plugin_base = include(PLUGIN_PATH.'../plugin_public/plugin_base.php');
		//基类公共参数
		foreach($plugin_base['action'] as $key => $value){
			$this->$key = $value;
		}
		foreach($plugin_base['tpl'] as $key => $value){
			$this->assign($key,$value);
		}
		if(!in_array(ACTION_NAME, ['current_leader', 'near_leader_list', 'consumer_code'])){
			$leader_id = I("leader_id");
			if(empty($leader_id)){
				returnCode(1, [], 'leader_id 参数错误！');
			}
			$this->leader_id = $leader_id;
		}

		//微信H5
		if(strpos($_SERVER['HTTP_USER_AGENT'],'MicroMessenger') !== false){
			$this->is_weixin_browser = true;
		}
		//微信小程序H5
		if($this->is_weixin_browser && strpos($_SERVER['HTTP_USER_AGENT'],'miniProgram') !== false){
			$this->is_wxapp_browser = true;
		}
		//支付宝H5
		if(strpos($_SERVER['HTTP_USER_AGENT'],'AlipayClient') !== false){
			$this->is_alipay_browser = true;
		}
		//支付宝小程序H5
		if($this->is_alipay_browser && (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'miniprogram') !== false || strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'alipayide') !== false)){
			$this->is_alipayapp_browser = true;
		}
		if(strpos($_SERVER['HTTP_USER_AGENT'],'pigcmso2oreallifeapp') !== false){
            $this->is_app_browser =   true;
        }
		
		$ticket = I("ticket");	
		if(in_array(ACTION_NAME, ['save_cart', 'select_cart', 'cart_list', 'my', 'save_order', 'confirm_order', 'confirm_go_pay', 'goods_add_price', 'get_difference']) || $ticket){
			$device_id = I("Device-Id");
			if(empty($device_id)){
				returnCode(1, [], 'Device-Id 参数错误！');
			}
			if(empty($ticket)){
				returnCode(1, [], 'ticket 参数错误！');
			}
			$info = ticket::get($ticket, $device_id, true);
	        $this->_uid = $info['uid'];
	        if(empty($this->_uid)){
	        	returnCode(1, [], 'ticket 参数错误！');	
	        }
	    }
	    if(!empty($this->_uid)){
	    	$user_info = PD("User")->get_user($this->_uid);
	    	if(strpos($user_info['openid'], 'no_use') !== false){
	    		returnCode(1, [], '当前用户账号已解绑，请重新登录！');	
	    	}
	    }
	}
}