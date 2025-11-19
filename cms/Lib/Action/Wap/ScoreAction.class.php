<?php

class ScoreAction extends BaseAction{
	// 首页
	public function scan_get_score(){
		$id = intval($_GET['id']);
		if (!$id) {
            $this->error_tips('非法请求！', U('index'));
		}

		$scan_get_score_content = D('Scan_get_score_content')->where('fid='.$id)->find();
		if (!$scan_get_score_content) {
            $this->error_tips('未获取到信息！', $this->config['site_url'].'/wap.php');
		}

		if (!$scan_get_score_content['status']) {
            $this->error_tips('已关闭！', $this->config['site_url'].'/wap.php');
		}
		$this->assign('scan_content',$scan_get_score_content);

		// 验证是否关注公众号
		import('ORG.Net.Http');
		$http = new Http();
		$is_follow = 0;
		$access_token_array = D('Access_token_expires')->get_access_token();
		if (!$access_token_array['errcode']) {
			$return = $http->curlGet('https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token_array['access_token'].'&openid='.$_SESSION['openid'].'&lang=zh_CN');
			$userifo = json_decode($return,true);
			$is_follow = $userifo['subscribe'];
		}
		if (!$is_follow) { // 弹出渠道二维码
			redirect($this->config['site_url'].'/index.php?g=Index&c=Recognition&a=see_qrcode&id='.$id.'&type=scan_get_score&img=1');
		}

		if(empty($this->user_session)){
            $this->error_tips('请先进行登录！', U('Login/index'));
        }

        //是否需要绑定手机号
        $now_user = D('User')->get_user($this->user_session['uid']);
        if (C('config.weixin_login_bind')) {
			if(empty($now_user['phone'])){
				$location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
	            $this->error_tips('请先绑定手机号！', U('My/bind_user',$location_param));
	        }
        }

		if ($scan_get_score_content['score']) {
			// 验证是否已经领取过
			$is_get = M('Scan_get_score_user_list')->where(array('uid'=>$this->user_session['uid'],'chanel_id'=>$id))->find();
			if ($is_get) {
	            // $this->error_tips('您已领取过！', $scan_get_score_content['go_url']);
				redirect($scan_get_score_content['go_url']);
			}else{
				$data['uid'] = $this->user_session['uid'];
				$data['chanel_id'] = $id;
				$data['time'] = time();
				M('Scan_get_score_user_list')->add($data);
				$result = D('User')->add_score($this->user_session['uid'], $scan_get_score_content['score'],'扫码获得'.C('config.score_name'));
				if ($result['error_code']) {
	            	$this->error_tips('获取失败，请重新扫码！', $scan_get_score_content['go_url']);
				}else{
					// $this->success_tips('获取积分成功！', $scan_get_score_content['go_url']);
					$this->display();
				}
			}
		}else{
			$this->error_tips('信息有误，获取失败，请重新扫码！', $scan_get_score_content['go_url']);
		}
	}

}