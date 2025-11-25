<?php
class RedirectAction extends BaseAction{
    public function index(){
    	$url = htmlspecialchars_decode($_GET['url']);
		$param = 'openid='.$_SESSION['openid'].'&uid='.$_SESSION['user']['uid'];
		if(strpos($url,'?') !== false){
			redirect($url.'&'.$param);
		}else{
			redirect($url.'?'.$param);
		}
    }
	public function need_login(){
		$param['referer'] = urlencode($_GET['referer']);
		if($this->is_app_browser){
			$this->error_tips('请先进行登录！',U('Login/index',$param));
		}else{
			redirect(U('Login/index',$param));
		}
	}
	
	public function convertUrlQuery($query){
		$queryParts = explode('&', $query);
		$params = array();
		foreach ($queryParts as $param)
		{
			$item = explode('=', $param);
			$params[$item[0]] = $item[1];
		}
		return $params;
	}
	
	public function zendesk_chat(){
		$url = 'my/zendesk_chat.html?account_key='.$this->config['open_kefu_zendesk'].'&zendesk_tag='.urlencode($_GET['zendesk_tag']);
		$_GET['url'] = htmlspecialchars($url);
		$this->packapp();
	}
	
	public function packapp(){
		$url = $this->config['site_url'].'/packapp/'. htmlspecialchars_decode($_GET['url']);
		
		$url_arr = parse_url($url);
		
		if($url_arr['query']){
			$urlQuery = $this->convertUrlQuery($url_arr['query']);
		}else{
			$urlQuery = array();
		}
		if($_COOKIE['now_city']){
			$urlQuery['now_city'] = $_COOKIE['now_city'];
		}
		if($_COOKIE['system_lang']){
			$urlQuery['now_lang'] = $_COOKIE['system_lang'];
		}
		
		if($this->user_session){
			$from_device_id = $this->user_session['from_device_id'];
			$from_ticket = $this->user_session['from_ticket'];
			if(!$from_device_id || !$from_ticket){
				$from_device_id = 'packapp';
				$ticket_arr = ticket::create($this->user_session['uid'], $from_device_id, true);
				$from_ticket = $ticket_arr['ticket'];
			}
			$urlQuery['device_id'] = $from_device_id;
			$urlQuery['ticket'] = $from_ticket;
		}
		
		$url = $url_arr['scheme'].'://'.$url_arr['host'].$url_arr['path'].'?'.http_build_query($urlQuery);
		
		redirect($url);
	}

    /**
     *重定向跳转
     */
    public function redirectUrl()
    {
        if($this->user_session){
            $now_user = D('User')->get_user($this->user_session['uid']);
            //获取sign
//        $now_user['avatar'] = 'https://www.baidu.com/img/flexible/logo/pc/result.png';
//        $now_user['nickname'] = '李四?？';
//        $now_user['phone'] = '13095455555';
            $source = 1;//1:公众号，2：小程序，3：app
            $openid = $now_user['openid'];
            $openidStr = '';
            if($this->is_app_browser){
                $source = 3;
            }else
                if($this->is_wxapp_browser){
                    $source = 2;
                    $openid = $now_user['wxapp_openid'];
                    $openidStr = '&openid='.$openid;
                }else{
                    $openidStr = '&openid='.$openid;
                }
            //获取sign
            $param = 'avatarUrl='.$now_user['avatar'].'&nickname='.$now_user['nickname'].$openidStr.'&phone='.$now_user['phone'].'&source='.$source;
            $sign = strtoupper(md5($param.'&key=b897afbec6f584415ba515aa171ac72f'));

            $now_user['nickname'] = urlencode($now_user['nickname']);
            $param = 'avatarUrl='.$now_user['avatar'].'&nickname='.$now_user['nickname'].$openidStr.'&phone='.$now_user['phone'].'&source='.$source;
            $url = ($_GET['url'] ?: 'https://hsq.rzghjt.com/').'?'.$param.'&sign='.$sign;
            header('location:'.$url);exit;
        }else{
            $this->error_tips('请先登录！',U('Index/Login/index'));
        }
    }
}
?>