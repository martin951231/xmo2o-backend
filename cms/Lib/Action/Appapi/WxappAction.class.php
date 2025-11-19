<?php
class WxappAction extends BaseAction{
	public function code_login(){
		$appid = $this->config['pay_wxapp_appid'];
		$appsecret = $this->config['pay_wxapp_appsecret'];
		
		import('ORG.Net.Http');
		$http = new Http();

		$return = Http::curlPost('https://api.weixin.qq.com/sns/jscode2session?appid='.$appid.'&secret='.$appsecret.'&js_code='.$_POST['code'].'&grant_type=authorization_code', array());

		if($return['errcode']){
			$this->returnCodeError($return['errmsg']);
		}

		/*优先使用 unionid 登录*/
		if(!empty($return['unionid'])){
			$now_user = $this->autologin('union_id',$return['unionid'],$return['openid']);
		}
		if(empty($now_user) && !empty($return['openid'])){
			/*再次使用 openid 登录*/
			$now_user = $this->autologin('wxapp_openid',$return['openid']);
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
				'v20_ticket' => D("User")->getV20Ticket($now_user['uid']),
				'user'	=>	$now_user,
				'emptyUser'	=>	false,
			);

			//推送登录状态给到楼宇系统
			if ($_POST['building_login_key']) {
				D('User')->pushUserLoginInfoToBuildingSystem($_POST['building_login_key'], $now_user['uid']);
			}
		}else{
			$return = array(
				'emptyUser' => true,
				'wx_openid' => $return['openid'],//做餐饮2.0用户登录的时候，需要拿到openid，因为他们是做的免登录
			);
		}
		D("User_spread")->updateUid($return['openid'], $now_user['uid']);
		$this->returnCode(0,$return);
	}
	
	public function login(){
        if($_POST['ticket']){

			if(!empty($_POST['Device-Id']) && $_POST['Device-Id'] == 'dyapp'){

				$info = ticket::get($_POST['ticket'],'dyapp', true);
				
				if ($info && $info['uid']){
					$now_user = $this->autologin('uid',$info['uid']);
					if(!empty($now_user)){
						D("User_spread")->updateUid($now_user['openid'], $now_user['uid']);
						$return = array(
							'ticket'    =>	$_POST['ticket'],
							'v20_ticket' => D("User")->getV20Ticket($info['uid']),
							'user'      =>	$now_user,
						);
						//推送登录状态给到楼宇系统
						if ($_POST['building_login_key']) {
							D('User')->pushUserLoginInfoToBuildingSystem($_POST['building_login_key'], $info['uid']);
						}
						$this->returnCode(0, $return);
					}else{
						$this->returnCode(0,array('emptyUser'=>true));
					}
				}else{
					$this->returnCode(0,array('emptyUser'=>true));
				}


			}else{

				$info = ticket::get($_POST['ticket'],$this->DEVICE_ID ? $this->DEVICE_ID : 'wxapp', true);
				if ($info && $info['uid']){
					$now_user = $this->autologin('uid',$info['uid']);
					if(!empty($now_user)){
						D("User_spread")->updateUid($now_user['openid'], $now_user['uid']);
						$return = array(
							'ticket'    =>	$_POST['ticket'],
							'v20_ticket' => D("User")->getV20Ticket($info['uid']),
							'user'      =>	$now_user,
						);
						//推送登录状态给到楼宇系统
						if ($_POST['building_login_key']) {
							D('User')->pushUserLoginInfoToBuildingSystem($_POST['building_login_key'], $info['uid']);
						}
						$this->returnCode(0, $return);
					}else{
						$this->returnCode(0,array('emptyUser'=>true));
					}
				}else{
					$this->returnCode(0,array('emptyUser'=>true));
				}

			}

            
        }
		
		$appid = $this->config['pay_wxapp_appid'];
		$appsecret = $this->config['pay_wxapp_appsecret'];
		
		import('ORG.Net.Http');
		$http = new Http();

		$return = Http::curlPost('https://api.weixin.qq.com/sns/jscode2session?appid='.$appid.'&secret='.$appsecret.'&js_code='.$_POST['code'].'&grant_type=authorization_code', array());
		
		if($return['errcode']){
			$this->returnCodeError($return['errmsg']);
		}
		
		import('@.ORG.aeswxapp.wxBizDataCrypt');
		
		$session_key = $return['session_key'];
		$pc = new WXBizDataCrypt($appid, $session_key);
		$errCode = $pc->decryptData($_POST['encryptedData'],$_POST['iv'],$data);
		$jsonrt = json_decode($data,true);
		/*优先使用 unionId 登录*/
		if(!empty($jsonrt['unionId'])){
			$now_user = $this->autologin('union_id',$jsonrt['unionId'],$jsonrt['openId']);
		}
		if(empty($now_user) && !empty($jsonrt['openId'])){
			/*再次使用 openId 登录*/
			$now_user = $this->autologin('wxapp_openid',$jsonrt['openId']);
		}
		if(empty($now_user)){
			$data_user = array(
				'wxapp_openid' 	=> $jsonrt['openId'],
				'union_id' 	=> ($jsonrt['unionId'] ? $jsonrt['unionId'] : ''),
				'nickname' 	=> $jsonrt['nickName'],
				'sex' 		=> $jsonrt['gender'],
				'province' 	=> $jsonrt['province'],
				'city' 		=> $jsonrt['city'],
				'avatar' 	=> D('User')->down_avatar($jsonrt['avatarUrl'], $jsonrt['unionId'] ? $jsonrt['unionId'] : $jsonrt['openId']),
				'last_weixin_time' => time(),
				'source' 	=> 'wxapp',
				'wxapp_session_key' => $session_key,
			);
			$reg_result = D('User')->autoreg($data_user);
			if(!$reg_result['error_code']){
				$now_user = $this->autologin('wxapp_openid',$jsonrt['openId']);
			}
		}else{
            //if ($jsonrt['nickName'] != '微信用户') {
                $data_user = array(
                    //'nickname' 	=> $jsonrt['nickName'],
                    //'sex' 		=> $jsonrt['gender'],
                    //'province' 	=> $jsonrt['province'],
                    //'city' 		=> $jsonrt['city'],
                    'avatar' 	=> D('User')->down_avatar($jsonrt['avatarUrl'], $jsonrt['unionId'] ? $jsonrt['unionId'] : $jsonrt['openId']),
                    'last_weixin_time' => time(),
                    'wxapp_session_key' => $session_key,
                );
                $jsonrt['openId'] && $data_user['wxapp_openid'] = $jsonrt['openId'];
				$jsonrt['unionId'] && $data_user['union_id'] = $jsonrt['unionId'];
                D('User')->where(array('uid'=>$now_user['uid']))->data($data_user)->save();
            //}
		}
		
		if(!empty($now_user)){
			D("User_spread")->updateUid($jsonrt['openid'], $now_user['uid']);
			$now_user['wxapp_session_key'] = $session_key;
			unset($now_user['wxapp_session_key']);
			$ticket = ticket::create($now_user['uid'],'wxapp', true);
			$return = array(
				'ticket'=>	$ticket['ticket'],
				'v20_ticket' => D("User")->getV20Ticket($now_user['uid']),
				'user'	=>	$now_user,
				'emptyUser'	=>	false,
			);
			$this->returnCode(0,$return);
		}else{
			$this->returnCode(0,array('emptyUser'=>true));
		}
    }
	protected function autologin($field,$value,$openid = ''){
		$result = D('User')->autologin($field,$value);
		if(empty($result['error_code'])){
			if($field == 'union_id' && empty($result['user']['wxapp_openid'])){
				$condition_user['union_id'] = $value;
				D('User')->where($condition_user)->data(array('wxapp_openid'=>$openid))->save();
				$result['user']['wxapp_openid'] = $openid;
			}
			$now_user = $result['user'];
		}elseif($result['error_code'] === true){
			$this->returnCodeError($result['msg']);
		}elseif(isset($_POST['source_type']) && $_POST['source_type'] == 'personnel' && isset($_POST['uid']) && $_POST['uid']){
            //根据uid查询
            $now_user = D('User')->get_user($_POST['uid'], 'uid');
            if($now_user){
                //更新用户信息
                $updateData = [
                    $field=>$value
                ];
                if($field != 'wxapp_openid' && $openid){
                    $updateData['wxapp_openid'] = $openid;
                }
//                D('User')->where(['uid'=>$_POST['uid']])->data($updateData)->save();
                $result = D('User')->autologin('uid',$_POST['uid']);
                $now_user = $result['user'];
            }
        }
		if(isset($now_user['wxapp_session_key'])){
			unset($now_user['wxapp_session_key']);
		}
		return $now_user;
	}
	//解密得到用户手机号
	public function getUserWxPhone(){
		if(!$this->_uid){
			$this->returnCodeError('用户未登录');
		}
		$now_user = D('User')->get_user($this->_uid);
		if(!$now_user){
			$this->returnCodeError('用户不存在');
		}
		if(!$now_user['wxapp_session_key']){
			$this->returnCodeError('小程序会话状态读取失败，请在个人中心重新登录后重试。');
		}
		
		import('@.ORG.aeswxapp.wxBizDataCrypt');
		
		$session_key = $now_user['wxapp_session_key'];
		$pay_wxapp_appid = $this->config['pay_wxapp_appid'];
		if($_POST['wxapp_own_mer_id']){	//商家小程序
			$now_bind = M('Weixin_app_bind')->where(array('bind_type'=>0,'other_id'=>$_POST['wxapp_own_mer_id']))->find();
			$pay_wxapp_appid = $now_bind['appid'];
		}
		if($_POST['code']){
			$access_token_array = D('Access_token_wxapp_expires')->get_access_token();
			$url = 'https://api.weixin.qq.com/wxa/business/getuserphonenumber?access_token=' . $access_token_array['access_token'];
			import('ORG.Net.Http');
			$return = Http::curlPost($url, json_encode(['code' => $_POST['code']]), 15, 'json');
			if ($return['errcode'] != 0) {
				$this->returnCodeError('小程序提交参数错误，错误码：' . $return['errcode']);
			}
			$phoneDataArr = $return['phone_info'];
		}else{
			$pc = new WXBizDataCrypt($pay_wxapp_appid, $session_key);
			$errCode = $pc->decryptData($_POST['encryptedData'],$_POST['iv'],$phoneData);
			if($errCode){
				$this->returnCodeError('小程序提交参数错误，错误码：'.$errCode.'。请在个人中心重新登录后重试。');
			}
			$phoneDataArr = json_decode($phoneData,true);
		}
		
		if($_POST['type'] == 'change_user_phone'){
			$other_user = array();
			//判断新手机号，数据库是否有用户。排查用户没改，
			if($now_user['phone'] != $phoneDataArr['purePhoneNumber']){
				$condition_user_find['phone'] = $phoneDataArr['purePhoneNumber'];
				$other_user = D('User')->where($condition_user_find)->find();
				if($other_user && !empty($now_user['phone'])){
					$this->returnCodeError('新手机号已经存在,无法修改');
				}
			}else if($now_user['phone_country_type'] == $phoneDataArr['countryCode'] || $now_user['phone_country_type'] == ''){
				$this->returnCodeError("修改失败！\n新手机号和原手机号一致。");
			}
			if($other_user && empty($now_user['phone'])){
				$condition_user['uid'] = $other_user['uid'];
				$data_user = array(
					'openid' => $now_user['openid'],
					'wxapp_openid' => $now_user['wxapp_openid'],
					'union_id' => $now_user['union_id'],
					'paotui_openid' => $now_user['paotui_openid'],
					'group_openid' => $now_user['group_openid'],
					'nickname' => $now_user['nickname'],
					'last_time' => time(),
				);
				if($now_user['avatar']){
					$data_user['avatar'] = $now_user['avatar'];
				}
				if($now_user['pay_pwd']){
					$data_user['pay_pwd'] = $now_user['pay_pwd'];
				}
				if(D('User')->where($condition_user)->data($data_user)->save()){
					$condition_user_remove['uid'] = $now_user['uid'];
					$data_user_remove = array(
						'openid' => $now_user['openid'].'~no_use',
						'wxapp_openid' => $now_user['wxapp_openid'].'~no_use',
						'union_id' => $now_user['union_id'].'~no_use',
						'paotui_openid' => $now_user['paotui_openid'].'~no_use',
						'group_openid' => $now_user['group_openid'].'~no_use',
						'last_time' => time(),
						'phone'		=> '',
						'status'  => 4
					);
					D('User')->where($condition_user_remove)->data($data_user_remove)->save();
					setcookie('user_device_id', '', -1, '/');
                    setcookie('user_ticket', '', -1, '/');
                    setcookie('login_status', '', -1, '/');
					$tmp_user = D('User')->get_user($other_user['uid']);
                    $ticket = ticket::create($other_user['uid'],'wxapp', true);
                    $tmp_user['ticket'] = $ticket['ticket'];
					$this->returnCodeOk($tmp_user);
				}else{
					$this->returnCodeError('修改失败，请重试');
				}
			}else{
				$condition_user['uid'] = $this->_uid;
				$data_user = array(
					'phone' => $phoneDataArr['purePhoneNumber'],
					'phone_country_type' => $phoneDataArr['countryCode'],
					'last_time' => time(),
				);
				if(D('User')->where($condition_user)->data($data_user)->save()){
					$tmp_user = D('User')->get_user($this->_uid);
					$ticket = ticket::create($this->_uid,'wxapp', true);
                    $tmp_user['ticket'] = $ticket['ticket'];
					$this->returnCodeOk($tmp_user);
				}else{
					$this->returnCodeError('修改失败，请重试');
				}
			}
		}else if($_POST['type'] == 'get_phone'){
			$returnArr = array(
				'phone' => $phoneDataArr['purePhoneNumber'],
				'phone_country_type' => $phoneDataArr['countryCode'],
			);
			$this->returnCodeOk($returnArr);
		}
		$this->returnCodeError('type传值不正确');
	}
	//保存 formid
    public function post_formid()
    {
		//由于小程序 formid 已经没有了用途，直接先返回成功，不操作数据库
		$this->returnCode(0, '添加成功');
		
        if(!$this->_uid){
			$this->returnCode(1001, '用户未登录');
		}
        $data['uid'] = $this->_uid;
        $data['formid'] = $_POST['formid'];
        $data['add_time'] = time();

        $add_result = M('Wxapp_formid')->data($data)->add();
        if (!$add_result['error']) {
            $this->returnCode(0, '添加成功');
        } else {
            $this->returnCode(1001, '添加失败');
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
	// 小程序请求获取webview分享参数、顶部背景
	public function webview_share(){
		$webview_url = trim($_POST['webview_url']);
		
		$webview_url_arr = parse_url($webview_url);
		$urlQuery = $this->convertUrlQuery($webview_url_arr['query']);
		$urlQuery['now_city'] = intval($_POST['now_city']);
		$urlQuery['now_area'] = intval($_POST['now_area']);
		if($this->_uid){
			$urlQuery['openid'] = M('User')->where(array('uid'=>$this->_uid))->getField('openid');
		}
		unset($urlQuery['ticket'],$urlQuery['device_id']);
        $webview_url = '';
		if (isset($webview_url_arr['scheme']) && $webview_url_arr['scheme'] && isset($webview_url_arr['host']) && $webview_url_arr['host'] && isset($webview_url_arr['path']) && $webview_url_arr['path']) {
            $webview_url = $webview_url_arr['scheme'].'://'.$webview_url_arr['host'].$webview_url_arr['path'].'?'.http_build_query($urlQuery);
        }
		if($urlQuery['c'] == 'Village_group' && $urlQuery['a']=='imgBoxShare'){
            $leader_id = intval($urlQuery['leader_id']);
			$webview_url = $this->site_url.'/wap.php?c=Village_group&a=index&leader_id='.$leader_id;
		}
		$return = array(
			'backgroundColor' => $this->config['mobile_head_color'],	//顶部背景颜色
			'frontColor' => '#ffffff',									//顶部字体颜色
			'share_title' => '',										//分享标题
			'share_img' => '',											//分享图片
			'share_url' => 'pages/webview/tab_webview?url='.urlencode($webview_url),	//分享链接
			'shareMenu' => true,
			'navigate_to_mini' => false,
			'navigate_to_url' => ''
		);

		$parseUrlData = parse_url($webview_url);
		//正则匹配short_(\d+) 先适配短链
		if (strpos($webview_url, C('config.site_url')) == 0 && preg_match('/short_(\d+)/', $parseUrlData['path'], $matches)) {
			$link_url = M('Short_link')->where(['link_id' => $matches[1]])->getField('link_url');
			//判断link_url含有packapp和pages
			if ($link_url && strpos($link_url, 'packapp') !== false && strpos($link_url, 'pages/') !== false) {
				$return['navigate_to_mini'] = true;
				$return['navigate_to_url'] = $link_url;
				if ($parseUrlData['query']) {
					//path  拼接上query参数
					$return['navigate_to_url'] = $link_url . (strpos($link_url, '?') !== false ? '&' : '?') . $parseUrlData['query'];
				}
			}
		}

		// 商家小程序设置导航栏主题色
        $wxapp_own_mer_id = intval($_POST['wxapp_own_mer_id']);
		if($wxapp_own_mer_id){
        	$now_bind = M('Weixin_app_bind')->where(array('bind_type'=>0,'other_id'=>$wxapp_own_mer_id))->find();
			$condition_page['page_id'] = $now_bind['mer_index_page'];
			$now_page = M('Merchant_store_diypage')->where($condition_page)->find();
			if($now_page['menu_bgcolor']){
				$return['backgroundColor'] = $now_page['menu_bgcolor'];
			}
		}
		//社区团购
		 if($urlQuery['c'] == 'Village_group'){
		 	if($urlQuery['a']=='goods'){
		 	    $leader_id = intval($urlQuery['leader_id']);
		 		$header = D('Village_group')->get_header_info(0, $leader_id);
		 		$header = $header['msg'];
		 		$goods_id = intval($urlQuery['goods_id']);
		 		$goods = M('Village_group_goods')->where(array('goods_id'=>$goods_id))->find();
		 		$return['share_title'] =$header['pick_address']['name'].'-'. $goods['name'];
		 		$return['share_img'] = $this->config['site_url'].'/village_group/goodswx_'.$leader_id.'_'.$goods_id;
		 	}else{
		 		if($this->config['village_group_wxapp_share_title'] && $this->config['village_group_wxapp_share_img']){
		 			$return['share_title'] = $this->config['village_group_wxapp_share_title'];
		 			$return['share_img'] = $this->config['village_group_wxapp_share_img'];
		 		}
		 	}
		 }
		//商城商品
		if($urlQuery['c'] == 'Mall' && $urlQuery['a'] == 'detail'){
			fdump($urlQuery,'urlQuery');
            $goods_id = intval($urlQuery['goods_id']);
			$goods_info = M('Shop_goods')->where(array('goods_id'=>$goods_id))->find();
			
			$img_rand_path = sprintf("%09d", $goods_id);
			$rand_num = substr($img_rand_path, 0, 3) . '/' . substr($img_rand_path, 3, 3) . '/' . substr($img_rand_path, 6, 3);
//			$share_img = C('config.site_url').'/upload/wxapp_mall/'.$rand_num.'/friend_'.$goods_id;
            $share_img = replace_file_domain('/upload/wxapp_mall/'.$rand_num.'/friend_'.$goods_id);

            $bar_id = intval($urlQuery['bar_id']);
			if($goods_id>0){		//拼团活动
//				$share_img.='_'.$goods_id;
				$return['share_title'] = '邀您参团'.$goods_info['name'];
			}elseif($bar_id>0){		//砍价活动
				$share_img.='_'.$bar_id;
				$return['share_title'] = '邀您砍价'.$goods_info['name'];
			}else{
				$return['share_title'] = '我推荐'.$goods_info['name'];
			}
            $team_id = intval($urlQuery['team_id']);
			if($team_id>0){
				$share_img.='_'.$team_id;
			}
			$return['share_img'] = $share_img.'.png';
			
			
			if($this->config['is_hark_system']){
				$now_store = M('Merchant_store')->where(array('store_id'=>$goods_info['store_id']))->find();
				
				$return['share_title'] = $now_store['wxapp_share_title'] ? : '';
				$return['share_img'] = $now_store['wxapp_share_pic'] ? thumb($now_store['wxapp_share_pic'], 500, 400 , 'fill') : '';
			}
		}
		
		//配送员注册页面用
		if(stripos($webview_url, 'deliver_web/register.html')){
			$return['backgroundColor'] = '#27A3F7';
		}
		
		//分类信息
		if($urlQuery['c'] == 'Classifynew'){
			$return['backgroundColor'] = '#ff4d00';
		}
		
		//APP门禁访客
		if($urlQuery['c'] == 'Library' && $urlQuery['a'] == 'visitor_list_open'){
			$return['shareMenu'] = false;
		}
		
		//社区拼团 - 禁止分享
		if($urlQuery['c'] == 'Village_group' && $urlQuery['a'] == 'imgBoxShare'){
			$return['shareMenu'] = false;
		}

		//社区团购首页分享
		$isShequVillageGroup = ($urlQuery['c'] == 'House' && $urlQuery['a'] == 'go_villagegroup');
		$isO2OVillageGroup = ($urlQuery['c'] == 'Village_group' && $urlQuery['a'] == 'index');
		$isVillageGroup = strpos(trim($_POST['webview_url']), "packapp/community_group/#/index") !== false;
		if ($isShequVillageGroup || $isO2OVillageGroup || $isVillageGroup) {
			$return['share_title'] = C('config.village_group_wxapp_share_title');
			$return['share_img'] = replace_file_domain(C('config.village_group_wxapp_share_img'));
		}

		//自定义页面，需要改颜色
		if($urlQuery['c'] == 'Diypage' && $urlQuery['a'] == 'page'){
		    $page_id = intval($urlQuery['page_id']);
			$diy_page = M('Merchant_store_diypage')->where(['page_id' => $page_id])->find();

			if($diy_page['menu_bgcolor']){
				$menu_bgcolor = $diy_page['menu_bgcolor'];
				if($menu_bgcolor != '#ffffff' && $menu_bgcolor != '#fff'){
					$return['backgroundColor'] = $menu_bgcolor;
				}
		    }else{
				$bgcolor = $diy_page['bgcolor'];
				if($bgcolor && $bgcolor != '#ffffff' && $bgcolor != '#fff'){
					$return['backgroundColor'] = $bgcolor;
				}
		    }
			
			if($this->config['is_hark_system']){
				$now_store = M('Merchant_store')->where(array('store_id'=>$diy_page['store_id']))->find();
				
				$return['share_title'] = $now_store['wxapp_share_title'] ? : '';
				$return['share_img'] = $now_store['wxapp_share_pic'] ? thumb($now_store['wxapp_share_pic'], 500, 400 , 'fill') : '';
			}
		}
		
		$this->returnCodeOk($return);
	}
	
	public function wxapp_login(){
		$appid = $this->config['pay_wxapp_appid'];
		$appsecret = $this->config['pay_wxapp_appsecret'];
		
		if(!function_exists('mcrypt_module_open')){
		    $this->returnCodeError('mcrypt_module_open PHP模块没有打开，请先打开。');
		}
		
		import('ORG.Net.Http');
		$http = new Http();

		$return = Http::curlPost('https://api.weixin.qq.com/sns/jscode2session?appid='.$appid.'&secret='.$appsecret.'&js_code='.$_POST['code'].'&grant_type=authorization_code', array());
		
		if($return['errcode']){
			$this->returnCodeError($return['errmsg']);
		}
		
		import('@.ORG.aeswxapp.wxBizDataCrypt');
		
		$session_key = $return['session_key'];
		$pc = new WXBizDataCrypt($appid, $session_key);
		$errCode = $pc->decryptData($_POST['encryptedData'],$_POST['iv'],$data);
		$jsonrt = json_decode($data,true);

		//解密失败，code获取的有openid和unionid
		if (empty($jsonrt) && ($return['openid'] || $return['unionid'])) {
			$jsonrt = [
				'openId' => $return['openid'],
				'unionId' => $return['unionid'],
				'nickName' => '微信用户',
				'gender' => 0,
				'language' => '',
				'city' => '',
				'province' => '',
				'country' => '',
				'avatarUrl' => ''
			];
		}
		if (empty($jsonrt)) {
			$this->returnCodeError('获取用户授权信息失败，请重新授权');
		}

		/*优先使用 unionId 登录*/
		if(!empty($jsonrt['unionId'])){
			$now_user = $this->autologin('union_id',$jsonrt['unionId'],$jsonrt['openId']);
		}
		if (empty($now_user) && !empty($jsonrt['openId'])) {
			/*再次使用 openId 登录*/
			$now_user = $this->autologin('wxapp_openid', $jsonrt['openId']);
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
				'wxapp_session_key' => $session_key,
			);
			if($third_id = M('User_tmp_third')->data($third_login)->add()){
				$now_third = M('User_tmp_third')->where(['third_id' => $third_id])->find();
				unset($now_third['wxapp_session_key']);
				$ticket = ticket::create($now_third['third_id'],$this->DEVICE_ID, true);
				$return = [
					'emptyUser'		=> false, 
					'third_id' 		=> $third_id, 
					'third_from'	=> $third_login['from'],
					'ticket'		=> $ticket['ticket'],
					'v20_ticket' 	=> D("User")->getV20Ticket($now_third['third_id']),
					'user'  		=> $now_third,		
				];
				$this->returnCodeOk($return);
			}
		}else{
			$data_user = [];
            if ($jsonrt['nickName'] != '微信用户') {
                $data_user = array(
                    'nickname' => $jsonrt['nickName'],
                    'sex' => $jsonrt['gender'],
                    'province' => $jsonrt['province'],
                    'city' => $jsonrt['city'],
                    'avatar' => D('User')->down_avatar($jsonrt['avatarUrl'], $jsonrt['unionId'] ? $jsonrt['unionId'] : $jsonrt['openId']),
                    'last_weixin_time' => time(),
                    'wxapp_session_key' => $session_key,
                );
            }
			$jsonrt['openId'] && $data_user['wxapp_openid'] = $jsonrt['openId'];
			$jsonrt['unionId'] && $data_user['union_id'] = $jsonrt['unionId'];
			if ($data_user) {
				D('User')->where(array('uid' => $now_user['uid']))->data($data_user)->save();
				$after_user = D('User')->where(array('uid' => $now_user['uid']))->find();
				$now_user = array_merge($now_user, $after_user);
			}
		}
		
		if(!empty($now_user)){
			D("User_spread")->updateUid($now_user['openid'], $now_user['uid']);
			$now_user['wxapp_session_key'] = $session_key;
			unset($now_user['wxapp_session_key']);  //安全考虑接口不要暴露session_key
			$ticket = ticket::create($now_user['uid'], $this->DEVICE_ID, true);

			session('user',$now_user);
			setcookie('user_device_id',$this->DEVICE_ID,0,'/');
			setcookie('user_ticket', $ticket['ticket'],0,'/');
			setcookie('login_status','login',0,'/');

			//推送登录状态给到楼宇系统
			if ($_POST['building_login_key']) {
				D('User')->pushUserLoginInfoToBuildingSystem($_POST['building_login_key'], $now_user['uid']);
			}
			$this->returnCodeOk(['emptyUser'=>false, 'third_id' => 0, 'ticket' => $ticket['ticket'], 'user' => $now_user, 'v20_ticket' => D("User")->getV20Ticket($now_user['uid'])]);
		}else{
			$this->returnCodeOk(['emptyUser'=>true]);
		}
    }
	
	//支付宝小程序授权登录
    public function alipayapp_login() {
        $auth_code = $_POST['auth_code'];
        $param = array();
        $param['app_id'] = $this->config['alipayapp_app_id'];
        $param['method'] = 'alipay.system.oauth.token';
        $param['charset'] = 'utf-8';
        $param['sign_type'] = $this->config['alipayapp_app_encrypt_type'] ? $this->config['alipayapp_app_encrypt_type'] : 'RSA';
        $param['timestamp'] = date('Y-m-d H:i:s');
        $param['version'] = '1.0';
        $param['grant_type'] = 'authorization_code';
        $param['code'] = $auth_code;
        ksort($param);
        $stringToBeSigned = "";
        $i = 0;
        foreach ($param as $k => $v) {
            if (!empty($v) && "@" != substr($v, 0, 1)) {
                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }

        $priKey = $this->config['alipayapp_app_prikey'];
        $res = "-----BEGIN RSA PRIVATE KEY-----\n".wordwrap($priKey, 64, "\n", true)."\n-----END RSA PRIVATE KEY-----";

        if(!function_exists('openssl_sign')){
            $this->returnCodeError('系统不支持 openssl_sign');
        }
        
        if($param['sign_type'] == 'RSA2'){
            openssl_sign($stringToBeSigned, $sign, $res, OPENSSL_ALGO_SHA256);
        }else{
            openssl_sign($stringToBeSigned, $sign, $res);
        }
            
        if(empty($sign)){
            $this->returnCodeError('支付宝密钥错误');
        }

        $sign = base64_encode($sign);

        $param['sign'] = $sign;
        $requestUrl = "https://openapi.alipay.com/gateway.do?";
        foreach ($param as $sysParamKey => $sysParamValue) {
            $requestUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
        }
        $requestUrl = substr($requestUrl, 0, -1);

        import('ORG.Net.Http');
        $http = new Http();

        $return = Http::curlGet($requestUrl);
        $returnArr = json_decode($return,true);
        
        if($returnArr['alipay_system_oauth_token_response']){
            //如果存在即 自动登录
            $now_user = D('User')->get_user($returnArr['alipay_system_oauth_token_response']['user_id'], 'alipay_uid');
            if(!empty($now_user)){
                //回调路径
                $ticket = ticket::create($now_user['uid'], $this->DEVICE_ID, true);
				
                //统一数据结构
                $return = array(
                    'ticket' => $ticket["ticket"],
                    'v20_ticket' => D("User")->getV20Ticket($now_user['uid']),
                    'user' => $now_user,
                    'access_token' => $returnArr['alipay_system_oauth_token_response']['access_token'],
					'emptyUser' => false,
					'third_id' => 0,
                );
                D("User_spread")->updateUid($now_user['openid'], $now_user['uid']);
				$this->returnCodeOk($return);
            }
          
            /*注册用户*/
            $jsonrt = $returnArr['alipay_system_oauth_token_response'];
            $third_login = array(
                'from'      => 'alipayapp',
                'alipay_uid'=> $jsonrt['user_id'],
                'nickname'  => $_POST['nickName'] ? $_POST['nickName'] : '支付宝用户',
                'sex'       => $_POST['gender'] == 'm' ? 1 : 2,
                'province'  => rtrim($_POST['province'],'省'),
                'city'      => rtrim($_POST['city'],'市'),
                'avatar'    => $_POST['avatar'] ? D('User')->down_avatar($_POST['avatar'], $jsonrt['user_id']) : '',
                'third_auth_time' => time(),
            );
            
            if($third_id = M('User_tmp_third')->data($third_login)->add()){
                $this->returnCodeOk(['emptyUser' => false, 'third_id' => $third_id, 'third_from' => $third_login['from']]);
            }else{
                $this->returnCodeError('访问异常！请重新登录。');
            }
        }else if($returnArr['error_response']){
            fdump($param,'alipayapp_login',1);
            fdump($returnArr['error_response'],'alipayapp_login',1);
            $this->returnCodeError('支付宝授权提示：'.$returnArr['error_response']['sub_msg']);
        }
    }
	
	//云闪付小程序
	public function upsdk_login(){
		$access_token_arr = D('Access_token_expires_upsdk')->get_access_token();
		if($access_token_arr['errcode']){
			$this->returnCodeError($access_token_arr['errmsg']);
		}
		
		
		$postArr = [
			'appId' 		=> C('config.upsdk_app_id'),
			'backendToken' 	=> $access_token_arr['access_token'],
			'code' 			=> $_POST['code'],
			'grantType' 	=> 'authorization_code',
			'third_auth_time' => time(),
		];
		
		$res = httpRequest('https://open.95516.com/open/access/1.0/token', 'POST', json_encode($postArr), ["Content-Type: application/json;charset=utf8"]);
		$result = json_decode($res[1], true);
		
		if($result['resp'] != '00'){
			$this->returnCodeError('云闪付返回：' . $result['msg']);
		}
		
		$now_user = $this->autologin('upsdk_openId', $result['params']['openId']);
		
		$jsonrt = [
			'openId' 	=> $result['params']['openId'],
			'nickName' 	=> '云闪付用户',
			'gender' 	=> '0',
			'province' 	=> '',
			'city' 	=> '',
			
		];
		
		if(empty($now_user)){
			/*临时注册用户*/
			$third_login = array(
				'from' 		=> 'upsdk',
				'upsdk_openId' 	=> $jsonrt['openId'],
				'nickname' 	=> $jsonrt['nickName'],
				'sex' 		=> $jsonrt['gender'],
				'province' 	=> $jsonrt['province'],
				'city' 		=> $jsonrt['city']
			);
			if($third_id = M('User_tmp_third')->data($third_login)->add()){
				$now_third = M('User_tmp_third')->where(['third_id' => $third_id])->find();
				$ticket = ticket::create($now_third['third_id'],$this->DEVICE_ID, true);
				$return = [
					'emptyUser'		=> false, 
					'third_id' 		=> $third_id, 
					'third_from'	=> $third_login['from'],
					'ticket'		=> $ticket['ticket'],
					'v20_ticket' 	=> D("User")->getV20Ticket($now_third['third_id']),
					'user'  		=> $now_third,		
				];
				$this->returnCodeOk($return);
			}
		}
		
		
		
		$data_user = array(
			'nickname' 	=> $jsonrt['nickName'],
			'sex' 		=> $jsonrt['gender'],
			'province' 	=> $jsonrt['province'],
			'city' 		=> $jsonrt['city']
		);
		D('User')->where(array('uid'=>$now_user['uid']))->data($data_user)->save();

		$ticket = ticket::create($now_user['uid'], $this->DEVICE_ID, true);
		$this->returnCodeOk(['emptyUser'=>false, 'third_id' => 0, 'ticket' => $ticket['ticket'], 'user' => $now_user, 'v20_ticket' => D("User")->getV20Ticket($now_user['uid'])]);
	}



	public function dy_code_login()
	{
		$appid = $this->config['dy_app_id'];
		$appsecret = $this->config['dy_app_secret'];
		$post = I('post.');

		if(empty($post['code'])){
			$this->returnCodeError('缺少必要参数！');
		}

		$params['appid'] = $appid;
		$params['secret'] = $appsecret;
		$params['code'] = $post['code'];
		  
		import('ORG.Net.Http'); 
		$url = 'https://developer.toutiao.com/api/apps/v2/jscode2session';
		// $params = array();
		$json_str = json_encode($params, JSON_UNESCAPED_SLASHES);
		
		$result = Http::curlPostJson($url, $json_str);
		
		if($result['err_no'] != 0){
			$this->returnCodeError($result['err_tips']);
		}   
 

		$dy_unionid = '';
		/*优先使用 unionId 登录*/
		if(!empty($result['data']['unionid'])){
			$dy_unionid = $result['data']['unionid'];
			$now_user = $this->autologin('dy_unionid', $result['data']['unionid'], $result['data']['openid']);
		}
		if(empty($now_user) && !empty($result['data']['openid'])){
			/*再次使用 openId 登录*/
			$now_user = $this->autologin('dy_openid',$result['data']['openid']);
		} 
		if(!empty($now_user)){

			$ticket = ticket::create($now_user['uid'], $this->DEVICE_ID, true);
			$this->returnCodeOk(['emptyUser'=>false, 'third_id' => 0, 'ticket' => $ticket['ticket'], 'user' => $now_user, 'v20_ticket' => D("User")->getV20Ticket($now_user['uid'])]);
		}else{
			$this->returnCodeOk(['emptyUser'=>true]);
		}
	}


	/**
	 * 抖音小程序登录
	 */
	public function dyapp_login()
	{
		if($_POST['ticket']){
			if(!empty($_POST['Device-Id']) && $_POST['Device-Id'] == 'dyapp'){

				$info = ticket::get($_POST['ticket'],'dyapp', true);
				
				if ($info && $info['uid']){
					$now_user = $this->autologin('uid',$info['uid']);
					if(!empty($now_user)){
						D("User_spread")->updateUid($now_user['openid'], $now_user['uid']);
						$return = array(
							'ticket'    =>	$_POST['ticket'],
							'v20_ticket' => D("User")->getV20Ticket($info['uid']),
							'user'      =>	$now_user,
						);
						$this->returnCode(0, $return);
					}else{
						$this->returnCode(0,array('emptyUser'=>true));
					}
				}else{
					$this->returnCode(0,array('emptyUser'=>true));
				}


			}
        }


		$appid = $this->config['dy_app_id'];
		$appsecret = $this->config['dy_app_secret'];
		$post = I('post.');

		if(empty($post['code']) || empty($post['encryptedData']) || empty($post['iv'])){
			$this->returnCodeError('缺少必要参数！');
		}

		$params['appid'] = $appid;
		$params['secret'] = $appsecret;
		$params['code'] = $post['code'];
		  
		import('ORG.Net.Http'); 
		$url = 'https://developer.toutiao.com/api/apps/v2/jscode2session';
		// $params = array();
		$json_str = json_encode($params, JSON_UNESCAPED_SLASHES);
		
		$result = Http::curlPostJson($url, $json_str);

		if($result['err_no'] != 0){
			$this->returnCodeError($result['err_tips']);
		}  
		$dy_unionid = '';
		/*优先使用 unionId 登录*/
		if(!empty($result['data']['unionid'])){
			$dy_unionid = $result['data']['unionid'];
			$now_user = $this->autologin('dy_unionid', $result['data']['unionid'], $result['data']['openid']);
		}
		if(empty($now_user) && !empty($result['data']['openid'])){
			/*再次使用 openId 登录*/
			$now_user = $this->autologin('dy_openid',$result['data']['openid']);
		} 
		 
		//解密用户信息
		$session_key = $result['data']['session_key'];
		$encryptedData = $post['encryptedData'];
		$iv = $post['iv'];
		try {
			$userInfo = openssl_decrypt(base64_decode($encryptedData, true), 'AES-128-CBC', base64_decode($session_key), OPENSSL_RAW_DATA, base64_decode($iv));
		} catch (\Exception $e) {
			$this->returnCodeError($e->getMessage());
		} 
		$userInfo = json_decode($userInfo, true);

		$data['from'] = 'dyapp'; 
		$data['dy_openid'] = $userInfo['openId']?:$result['data']['openid']; 
		$data['dy_unionid'] = $dy_unionid?:$result['data']['unionid']; 
		$data['nickname'] = $userInfo['nickName']; 
		$data['sex'] = $userInfo['gender']; 
		$data['province'] = $userInfo['province']; 
		$data['city'] = $userInfo['city']; 
		$data['avatar'] = D('User')->down_avatar($userInfo['avatarUrl'], $data['dy_unionid'] ? $data['dy_unionid'] : $data['dy_openid']); 
		$data['is_follow'] = 0; 
		$data['third_auth_time'] = time(); 
		
		//注册
		if(empty($now_user)){ 
	
			if($third_id = M('User_tmp_third')->data($data)->add()){

				$now_third = M('User_tmp_third')->where(['third_id' => $third_id])->find();
				$ticket = ticket::create($now_third['third_id'],$this->DEVICE_ID, true);
				$return = [
					'emptyUser'		=> false, 
					'third_id' 		=> $third_id, 
					'third_from'	=> $data['from'],
					'ticket'		=> $ticket['ticket'],
					'v20_ticket' 	=> D("User")->getV20Ticket($now_third['third_id']),
					'user'  		=> $now_third,
				];
				$this->returnCodeOk($return);
			}

		}else{

			$data_user = array(
				'nickname' 	=> $data['nickname'],
				'sex' 		=> $data['sex'],
				'province' 	=> $data['province'],
				'city' 		=> $data['city'],
				'avatar' 	=> $data['avatar']
			);
			if($data['dy_openid']){
				$data_user['dy_openid'] = $data['dy_openid'];
			}
			if($data['dy_unionid']){
				$data_user['dy_unionid'] = $data['dy_unionid'];
			}
			D('User')->where(array('uid'=>$now_user['uid']))->data($data_user)->save();

		}
		
		if(!empty($now_user)){
			D("User_spread")->updateUid($now_user['dy_openid'], $now_user['uid']);
			$now_user['wxapp_session_key'] = $session_key;
			$ticket = ticket::create($now_user['uid'], $this->DEVICE_ID, true);

			$now_user['nickname'] = $data['nickname'];
			$now_user['sex'] = $data['sex'];
			$now_user['province'] = $data['province'];
			$now_user['city'] = $data['city'];
			$now_user['avatar'] = $data['avatar'];


			$this->returnCodeOk(['emptyUser'=>false, 'third_id' => 0, 'ticket' => $ticket['ticket'], 'user' => $now_user, 'v20_ticket' => D("User")->getV20Ticket($now_user['uid'])]);
		}else{
			$this->returnCodeOk(['emptyUser'=>true]);
		}
	}
	
}
?>