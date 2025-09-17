<?php
/*
 * 商户和微信通信接口
 */
class HouseweixinAction extends CommonAction
{
	public function index()
	{
		$wechat = new Chat(array('wx_appid' => $this->config['wx_house_appid'], 'wx_token' => $this->config['wx_house_token'], 'wx_encodingaeskey' => $this->config['wx_house_encodingaeskey']));
		$data = $wechat->request();
		list($content, $type) = $this->reply($data);
		if ($content) {
			$wechat->response($content, $type);
		} else {
			exit();
		}
	}
	
	
	public function get_ticket()
	{
        fdump_api(['get_ticket-'.__LINE__,$_POST,$_REQUEST],'/weixin/HouseweixinActionLog',true);
		//获取 component_verify_ticket 每十分钟微信服务本服务器请求一次
		$wechat = new Chat(array('wx_appid' => $this->config['wx_house_appid'], 'wx_token' => $this->config['wx_house_token'], 'wx_encodingaeskey' => $this->config['wx_house_encodingaeskey']));
		$data = $wechat->request();
        fdump_api(['get_ticket-'.__LINE__,$data],'/weixin/HouseweixinActionLog',true);
		if(isset($data['InfoType'])) {
			if ($data['InfoType'] == 'component_verify_ticket') {
				if (isset($data['ComponentVerifyTicket']) && $data['ComponentVerifyTicket']) {
					if ($config = D('Config')->where("`name`='wx_house_componentverifyticket'")->find()) {
						D('Config')->where("`name`='wx_house_componentverifyticket'")->data(array('value' => $data['ComponentVerifyTicket']))->save();
					} else {
						D('Config')->data(array('name' => 'wx_house_componentverifyticket', 'value' => $data['ComponentVerifyTicket'], 'type' => 'type=text', 'gid' => 0, 'tab_id' => 0))->add();
					}
					S(C('now_city') . 'config', null);
					exit('success');
				}
			} elseif ($data['InfoType'] == 'unauthorized') {
                fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/weixin_unauthorized_log',true);
                fdump($data,'api/log/'.date('Ymd').'/weixin_unauthorized_log',true);
                fdump(D('Weixin_bind')->where("`authorizer_appid`='{$data['AuthorizerAppid']}'")->select(),'api/log/'.date('Ymd').'/weixin_unauthorized_log',true);
                if (isset($data['AuthorizerAppid']) && $data['AuthorizerAppid']) {
//                    D('Weixin_bind')->where("`authorizer_appid`='{$data['AuthorizerAppid']}' AND type=1")->delete();
                    $wexinBindInfo = D('Weixin_bind')->where("`authorizer_appid`='{$data['AuthorizerAppid']}' AND type=2")->find();
                    D('Weixin_bind')->where("`authorizer_appid`='{$data['AuthorizerAppid']}' AND type=2")->delete();
                    // 解绑了物业自有公众号 清除原有小区推广二维码
                    if (isset($wexinBindInfo['type']) && $wexinBindInfo['type'] == 2 && $wexinBindInfo['mer_id']) {
                        $save_data = ['qrcode_id' => 0];
                        M('House_village')->where(['property_id' => $wexinBindInfo['mer_id']])->save($save_data);
                    }
                    exit('success');
                }
            }
		}
	}
	
	
	private function get_access_token($auth_code)
	{
		import('ORG.Net.Http');
		$url = 'https://api.weixin.qq.com/cgi-bin/component/api_component_token';
		$data = array('component_appid' => $this->config['wx_house_appid'], 'component_appsecret' => $this->config['wx_house_appsecret'], 'component_verify_ticket' => $this->config['wx_house_componentverifyticket']);
		$result = Http::curlPost($url, json_encode($data));
		if ($result['errcode']) {
			return false;
		}
		//获取 authorizer_appid
		$url = 'https://api.weixin.qq.com/cgi-bin/component/api_query_auth?component_access_token=' . $result['component_access_token'];//
		$data = array('component_appid' => $this->config['wx_house_appid'], 'authorization_code' => $auth_code);
		$result1 = Http::curlPost($url, json_encode($data));

		if ($result1['errcode']) {
			return false;
		}
		return $result1['authorization_info']['authorizer_access_token'];
	}
	
	
    private function reply($data)
    {
    	$keyword = isset($data['Content']) ? $data['Content'] : (isset($data['EventKey']) ? $data['EventKey'] : '');
    	if($data['ToUserName'] == 'gh_3c884a361561'){
    		if ($data['MsgType'] == 'event') {
    			return array($data['Event'] . 'from_callback', 'text');
    		}
    		
    		if ($keyword == 'TESTCOMPONENT_MSG_TYPE_TEXT') {
    			return array('TESTCOMPONENT_MSG_TYPE_TEXT_callback', 'text');
    		}
    		if (strstr($keyword, 'QUERY_AUTH_CODE:')) {
    			$t = explode(':', $keyword);
    			$query_auth_code = $t[1];
    			$access_token = $this->get_access_token($query_auth_code);
    			$url = 'https://api.weixin.qq.com/cgi-bin/message/custom/send?access_token=' . $access_token;
    			$str = '{"touser":"'. $data['FromUserName'] .'", "msgtype":"text", "text":{"content":"' . $query_auth_code . '_from_api"}}';
    			import('ORG.Net.Http');
    			Http::curlPost($url, $str);
    		}
    	}
        if (strpos($this->config['site_url'],'http://') !== false) {
            // 网址是http 重新查询定位下
            $site_url = M('Config')->where(['name' => 'site_url'])->getField('value');
            $site_url && $this->config['site_url'] = $site_url;
        }

        $mer_id = 0;
        $info_type = 0; // 这个值 0 对应小区  1 对应物业
		if ($bind = D('Weixin_bind')->where(array('user_name' => $data['ToUserName'], 'type' => 1))->find()) {
			$mer_id = $bind['mer_id'];
            $info_type = 0;
		}elseif ($bind = D('Weixin_bind')->where(array('user_name' => $data['ToUserName'], 'type' => 2))->find()) {
            $mer_id = $bind['mer_id'];
            $info_type = 1;
        }  else {
			return false;
		}
    	if ($data['MsgType'] == 'event') {
    		$id = $data['EventKey'];
    		switch (strtoupper($data['Event'])) {
    			case 'SCAN':
 		    		return $this->scan($id, $data['FromUserName'], $mer_id);
		    		break;
    			case 'CLICK':
		    		$return = $this->special_keyword($id, $data, $mer_id, $info_type);
		    		return $return;
    				break;
    			case 'SUBSCRIBE':
//					$this->route();
                    //获取用户信息
                    $access_token = D('Weixin_bind')->get_access_token($mer_id,2);
                    $access_token = $access_token['access_token'];
                    $getUserInfoUrl = "https://api.weixin.qq.com/cgi-bin/user/info?access_token=$access_token&openid=".$data['FromUserName']."&lang=zh_CN";
                    $return = json_decode($this->curlGet($getUserInfoUrl),true);
                    if(isset($return['openid'])){
                        $openId = isset($return['openid'])?$return['openid']:'';
                        $unionid = isset($return['unionid'])?$return['unionid']:'';
                        $nickname = isset($return['nickname'])?$return['nickname']:'';
                        $sex = isset($return['sex'])?$return['sex']:'';
                        $headimgurl = isset($return['headimgurl'])?$return['headimgurl']:'';

                        $data_weixin_bind_user = [
                            'property_id'=>$mer_id,
                            'openid'=>$openId,
                            'union_id'=>$unionid,
                            'nickName'=>$nickname,
                            'sex'=>$sex,
                            'avatar'=>$headimgurl,
                            'type'=>2,
                            'is_follow' => 1
                        ];
                        if(!empty($unionid)){
                            $where=['union_id'=>$unionid , 'openid'=>$openId, '_logic'=>'OR'];
                        }else{
                            $where=[
                                'openid'=>$openId
                            ];
                        }
                        $weixin_bind_user = M('Weixin_bind_user')->where($where)->find();
                        if($weixin_bind_user){
                            M('Weixin_bind_user')->where(['id'=>$weixin_bind_user['id']])->data($data_weixin_bind_user)->save();
                        }else{
                            M('Weixin_bind_user')->data($data_weixin_bind_user)->add();
                        }
                    }
    				if (isset($data['Ticket'])) {
    					$id = substr($data['EventKey'], 8);
    					return $this->scan($id, $data['FromUserName'], $mer_id, $info_type);
    				}
    				if ($mer_id) {
                        $other = D('House_reply_other')->where(array('type' => 0,'is_open' => 1, 'mer_id' => $mer_id, 'info_type'=> $info_type))->find();
    					if ($other) {
    						if ($other['reply_type'] == 0) {
    							return array($other['content'], 'text');
    						} else {
    							return $this->txt_img($other['from_id'], $mer_id, $info_type);
    						}
    					} else {
//    						return array("感谢您的关注，我们将竭诚为您服务！", 'text');
    					}
    				}
//    				return array("感谢您的关注，我们将竭诚为您服务！", 'text');
    				break;
    			case 'UNSUBSCRIBE':
    			    //$data['FromUserName']
//					$this->route();
                    M('Weixin_bind_user')->where(['openid'=>$data['FromUserName']])->data(['is_follow'=>0])->save();
    				return array("BYE-BYE", 'text');
    				break;
    			case 'LOCATION':
    				break;
				default:
					//return array("亲，此号暂停测试，请搜索【pigcms】进行关注测试", 'text');
    		}
    	} elseif ($data['MsgType'] == 'text') {
    		$content = $data['Content'];
    		if (strtolower($content) == 'go') {
    			$t_data = $this->route();
// 				header("Content-type: text/xml");
// 				exit($t_data);
    		}
    		$return = $this->special_keyword($content, $data, $mer_id, $info_type);
    		return $return;
    	} elseif ($data['MsgType'] == 'image' && $mer_id) {
			if ($other = D('House_reply_other')->where(array('type' => 2, 'mer_id' => $mer_id, 'info_type' => $info_type))->find()) {
				if ($other['reply_type'] == 0) {
					return array($other['content'], 'text');
				} else {
					return $this->txt_img($other['from_id'], $mer_id, $info_type);
				}
			} else {
				return false;
			}
    	}
    	return false;
    }

    private function scan($id, $openid = '', $issubscribe = 0)
    {
        
        if($id > 5200000000){
            /**
             * $hzhou_grapefruit_order=D('Config_customization')->getHzhouGrapefruitOrderJudge();
             */
            $hzhou_grapefruit_order=1;
            // 新版物业收费收银台缴费
            $id -= 5200000000;
            $now_order = M('House_new_pay_order_summary')->where(array('summary_id'=>$id))->find();
            $now_village = M('House_village')->where(array('village_id'=>$now_order['village_id']))->find();
            $cashierUrl='/packapp/village/pages/houseMeter/NewCollectMoney/liveExpenses';
            if($hzhou_grapefruit_order){
                $cashierUrl='/packapp/village/pages/houseMeter/NewCollectMoney/newLiveExpenses';
            }
            if(0&&$this->config['pay_wxapp_important']){
                $url = $this->config['config_site_url'] . $cashierUrl.'?village_id='.$now_order['village_id'].'&resource_from=scan&summary_id='.$now_order['summary_id'].'&pigcms_id='.$now_order['pay_bind_id'];
                return array('<a href="'.$url.'" data-miniprogram-appid="'.$this->config['pay_wxapp_appid'].'" data-miniprogram-path="' . D('House_village')->home_url .  '?redirect=webview&webview_url='.urlencode($url).'">【'.$now_village['village_name'].'】收银台收费，点击去缴纳</a>', 'text');
            }else{
                return array('<a href="' . $this->config['config_site_url'] . $cashierUrl.'?village_id=' . $now_order['village_id'].'&resource_from=scan&summary_id='.$now_order['summary_id'].'&pigcms_id='.$now_order['pay_bind_id'].'">【'.$now_village['village_name'].'】收银台收费，点击去缴纳</a>', 'text');
            }
        } elseif($id > 4200000000){
            // 收银台缴费
            $id -= 4200000000;
            $now_order = M('House_village_pay_cashier_order')->where(array('cashier_id'=>$id))->find();
            $now_village = M('House_village')->where(array('village_id'=>$now_order['village_id']))->find();
            if($this->config['pay_wxapp_important']){
                $url = $this->config['config_site_url'] . '/wap.php?c=My&a=house_cashier_order_before&order_id=' . $id  . '&otherrm=1';
                return array('<a href="'.$url.'" data-miniprogram-appid="'.$this->config['pay_wxapp_appid'].'" data-miniprogram-path="' . D('House_village')->home_url .  '?redirect=webview&webview_url='.urlencode($url).'">【'.$now_village['village_name'].'】收银台收费，点击去缴纳</a>', 'text');
            }else{
                return array('<a href="' . $this->config['config_site_url'] . '/wap.php?c=My&a=house_cashier_order_before&order_id=' . $id  . '&otherrm=1">【'.$now_village['village_name'].'】收银台收费，点击去缴纳</a>', 'text');
            }
        }elseif ($id > 3950000000 && $openid) {
            $id -= 3950000000;
            $tmpVillage=D('House_village')->field(true)->where(array('openid' => $openid,'village_id' => $id))->find();
            if ($tmpVillage) {
                return array('该微信号已经绑定了，不能重复绑定', 'text');
            }
            if ($village = D('House_village')->field(true)->where(array('village_id' => $id))->find()) {
                $nickname='';
                $avatar='';
                $isfind=false;
                $user = D('User')->field('uid,openid,phone,nickname,avatar')->where(array('openid' => $openid))->order('uid desc')->find();
                if(!empty($user)){
                    $nickname=$user['nickname'];
                    $avatar=$user['avatar'];
                    $isfind=true;
                }
                if(empty($nickname) && empty($avatar)){
                    $whereStr=' openid="'.$openid.'" OR plat_openid="'.$openid.'" ';
                    $bind_user = D('Weixin_bind_user')->field('*')->where($whereStr)->order('id desc')->find();
                    if(!empty($bind_user)){
                        $nickname=$bind_user['nickName'];
                        $avatar=$bind_user['avatar'];
                        $isfind=true;
                    }
                }
                $data = array('openid' => $openid);
                $data['nickname'] = $nickname;
                $data['avatar'] = $avatar;
                if($isfind){
                    D('House_village')->where(array('village_id' => $id))->save($data);
                }else{
                    return array('您还不是小区的公众号用户，暂时无法成为小区的管理账号。<a href="' . $this->config['site_url'] . '/wap.php?c=My&a=index">点击去注册公众号用户</a>，注册完成后再重新扫码绑定。', 'text');
                }
                /*
                if ($village['openid'] != $openid) {
                    import('ORG.Net.Http');
                    $http = new Http();
                    $data = array('openid' => $openid);
                    $access_token_array = D('Access_token_expires')->get_access_token();
                    if (!$access_token_array['errcode']) {
                        $return = $http->curlGet('https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . $access_token_array['access_token'] . '&openid=' . $openid . '&lang=zh_CN');
                        $userifo = json_decode($return, true);
                        $data['nickname'] = isset($userifo['nickname']) ? $userifo['nickname'] : '';
                        $data['avatar'] = isset($userifo['headimgurl']) ? $userifo['headimgurl'] : '';
                    }
                    D('House_village')->where(array('village_id' => $id))->save($data);
                }
                */
                return array('绑定成功', 'text');
            } else {
                return array('没有相应的社区', 'text');
            }
        } elseif ($id > 3900000000 && $openid) {
            $id -= 3900000000;
            $where = [
                'openid' => $openid,
                'is_del' => 0
            ];
            /*
            $repeat_worker = D('House_worker')->field(true)->where($where)->find();
            if ($repeat_worker) {
                return array('该微信号已经绑定了，不能重复绑定', 'text');
            }
            */
            $worker = D('House_worker')->field('*')->where(array('wid' => $id,'is_del'=>0))->find();
            if ($worker) {
                $nickname='';
                $avatar='';
                $isfind=false;
                $user = D('User')->field('uid,openid,phone,nickname,avatar')->where(array('openid' => $openid))->order('uid desc')->find();
                if(!empty($user)){
                    $nickname=$user['nickname'];
                    $avatar=$user['avatar'];
                    $isfind=true;
                }
                $data = array('openid' => $openid);
                $data['nickname'] = $nickname;
                $data['avatar'] = $avatar;
                //$data['create_time'] = $worker['create_time'] ? $worker['create_time'] : time();
                $data['status'] = 1;
				if ($isfind) {
                    $whereArr=array('wid' => $id,'is_del'=>0,'status'=>array('lt',4));
					D('House_worker')->where($whereArr)->save($data);
                    if(isset($worker['hw_id']) && $worker['hw_id']>0){
                        $whereWxArr=array('hw_id'=>$worker['hw_id'],'village_id'=>array('gt',0),'is_del'=>0,'status'=>array('lt',4));
                        D('House_worker')->where($whereWxArr)->save($data);
                        $tmpUpData=array('openid'=>$openid,'nickname'=>$nickname,'avatar'=>$avatar);
                        $whereTmpWorker=array('hw_id'=>$worker['hw_id']);
                        D('House_property_village_worker')->where($whereTmpWorker)->save($tmpUpData);
                    }
				}else{
                    return array('您还不是小区的公众号用户，暂时无法绑定小区工作人员。<a href="' . $this->config['site_url'] . '/wap.php?c=My&a=index">点击去注册公众号用户</a>，注册完成后再重新扫码绑定。', 'text');
                }
                return array('绑定成功', 'text');
            } else {
                return array('先添加工作人员的基本信息', 'text');
            }
        } elseif ($id > 3890000000 && $openid) {
            $id -= 3890000000;
            if (D('Admin')->field(true)->where(array('openid' => $openid))->find()) {
                return array('该微信号已经绑定了，不能重复绑定', 'text');
            }
            if ($admin = D('Admin')->field(true)->where(array('id' => $id))->find()) {
                if ($admin['openid'] != $openid) {
                    import('ORG.Net.Http');
                    $http = new Http();
                    $data = array('openid' => $openid);
                    $access_token_array = D('Access_token_expires')->get_access_token();
                    if (!$access_token_array['errcode']) {
                        $return = $http->curlGet('https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . $access_token_array['access_token'] . '&openid=' . $openid . '&lang=zh_CN');
                        $userifo = json_decode($return, true);
                        $data['nickname'] = isset($userifo['nickname']) ? $userifo['nickname'] : '';
                        $data['avatar'] = isset($userifo['headimgurl']) ? $userifo['headimgurl'] : '';
                    }
                    D('Admin')->where(array('id' => $id))->save($data);
                }
                return array('绑定成功', 'text');
            } else {
                return array('没有改账号信息', 'text');
            }
        }elseif($id > 2700000000 && $openid) {  //申请入驻房屋
            $id -= 2700000000;
            $db_house_village = D('House_village');
            $app_version = intval($_POST['app_version']);
            $deviceId = trim($_POST['Device-Id']);
            $param = [
                'pageName' => 'joinHouse',
                'isAll' => true,
                'urlParam' => "pigcms_id=' . $id . '&opend_id='.$openid.'&otherrm=1",
            ];
            $joinHouseUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
            return array('<a href="' . $joinHouseUrl . '">申请入住房屋</a>', 'text');

        }elseif($id > 2600000000 && $openid) {  //生成二维码访客通行
            $id -= 2600000000;
            $db_house_village = D('House_village');
            $app_version = intval($_POST['app_version']);
            $deviceId = trim($_POST['Device-Id']);
            $param = [
                'pageName' => 'joinHouse',
                'isAll' => true,
                'urlParam' => "pigcms_id=' . $id . '&opend_id='.$openid.'&otherrm=1",
            ];
            $joinHouseUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);
            return array('<a href="' . $joinHouseUrl . '">访客通行处理</a>', 'text');

        }elseif ($id > 1600000000 && $openid) {
// 		    $id -= 1600000000;
            if ($admin = M('Admin')->field('id')->where(array('openid' => $openid,'status'=>'1'))->find()) {
                M('Admin_qrcode')->where(array('id' => $id))->save(array('aid' => $admin['id']));
                return array('登录成功', 'text');
            } else {
                return array('您的微信号没有绑定平台账号，不能登录', 'text');
            }

        }elseif ($id > 1300000000 && $openid) {
            $id -= 1300000000;
            $web_url = $this->config['site_url'] . "/packapp/community/";
            $return[] = array('物业管理','', $this->config['wechat_share_img'], $web_url);
            return array($return, 'news');
        }elseif ($id > 1200000000 && $openid) {
            if ($user = M('User')->field('uid')->where(array('openid' => $openid,'status'=>'1'))->find()) {
                M('Im_qrcode')->where(array('id' => $id))->save(array('uid' => $user['uid']));
                return array('登录成功', 'text');
            } else {
                M('Im_qrcode')->where(array('id' => $id))->save(array('uid' => -1));
                $return[] = array('点击授权登录', '', $this->config['site_logo'], $this->config['site_url'] . '/wap.php?c=Web_bind&a=ajax_web_login&qrcode_id=' . $id);
                return array($return, 'news');
            }
        }elseif ($id > 1100000000 && $openid) {
            $id -= 1100000000;
            // return array('<a href="' . $this->config['site_url'] . '/wap.php?g=Wap&c=Library&a=fetch_code&village_id=' . $id  . '">领快递</a>', 'text');
            $return[] = array('领取快递', '', $this->config['express_reply_icon'], $this->config['site_url'] . '/wap.php?g=Wap&c=Library&a=receive&village_id=' . $id);
            return array($return, 'news');
        }elseif ($id > 1000000000 && $openid) {
            $now_code = M('Login_qrcode')->where(array('id' => $id))->find();
            $user = M('User')->field('uid')->where(array('openid' => $openid, 'status' => '1'))->find();
            if (empty($now_code)) {
                return array('二维码不存在', 'text');
            } else if ($now_code['type'] == 'new_im_kefu') {
                //新版IM客服PC端扫码登录
                if (!$user || !M('Merchant_store_kefu')->where(['bind_uid' => $user['uid']])->find()) {
                    return array('登录失败，您还不是客服身份', 'text');
                } else {
                    M('Login_qrcode')->where(['id' => $id])->save(['uid' => $user['uid']]);
                    return array('登录成功', 'text');
                }
            }

            if ($user) {
                M('Login_qrcode')->where(array('id' => $id))->save(array('uid' => $user['uid']));
                if($now_code['type'] == 'merchant'){
                    $condition_merchant['uid'] = $user['uid'];
                    $now_merchant = M('Merchant')->field(true)->where($condition_merchant)->find();
                    if(empty($now_merchant)){
                        return array('微信号未绑定商家，请使用手机号登录商家后台绑定', 'text');
                    }
                }
                return array('登录成功', 'text');
            } else {
                M('Login_qrcode')->where(array('id' => $id))->save(array('uid' => -1));
                $return[] = array('点击授权登录', '', $this->config['site_logo'], $this->config['site_url'] . '/wap.php?c=Web_bind&a=ajax_web_login&qrcode_id=' . $id);
                return array($return, 'news');
            }
        }elseif($id > 900000000 && $openid){
            $id -= 900000000;
            $spread_info = M('User_spread_qrcode')->where(array('uid'=>$id))->find();
            $url = parse_url($spread_info['url']);
            $option = explode('&',$url['query']);
            foreach($option as $vo){
                $tmp = explode('=',$vo);
                $options[$tmp[0]]=$tmp[1];
            }
            if( !empty($options['openid']) && $openid && $options['openid'] != $openid && !M('Merchant_spread')->where(array('openid'=>$openid,'mer_id'=>array('gt',0)))->find() ){
                $spread_user = D('User')->get_user($options['openid'],'openid');
                if(!empty($spread_user)){
                    $now_user = D('User')->get_user($openid,'openid');
                    if(empty($now_user)||$this->config['user_spread_replace']){
                        $canSpread = true;
                        if($this->config['user_spread_replace']){
                            $user_spread_info = D('User_spread')->where(array('openid' => $openid))->find();
                            if($user_spread_info['spread_openid'] == $openid){
                                $canSpread = false;
                            }
                        }
                        if($canSpread){
                            D('User_spread')->where(array('openid'=>$openid))->delete();
                            D('User_spread')->data(array('spread_openid'=>$options['openid'],'spread_uid'=>$spread_user['uid'],'openid'=>$openid))->add();
                            D('User_spread')->send_spread_msg($openid,$options['openid']);
                        }
                    }
                }
            }

            if($res = M('Platform')->where(array('key'=>array('like','%用户推广二维码%')))->find()){
                if($res['type']==1){
                    return array(htmlspecialchars_decode($res['content']),'text');
                }else{
                    $return[] = array($res['title'], $res['content'], $this->config['site_url'].$res['pic'], $spread_info['url']);
                }
            }else{
                $return[] = array($this->config['user_spread_qrcode_keyword'], '', $this->config['wechat_share_img'], $spread_info['url']);
            }

            return array($return, 'news');

        }elseif($id > 800000000 && $openid){  //会员卡绑定
            $id -= 800000000;
            $user_card = M('Card_userlist')->where(array('id'=>$id))->find();
            $result = D('Card_new')->auto_reg_or_bind($user_card['mer_id'],$openid,$user_card['id'],1);
            return array($result['msg'], 'text');
        }elseif($id > 700000000){
            $id -= 700000000;
            $now_order = M('House_village_pay_order')->where(array('order_id'=>$id))->find();
            return array('<a href="' . $this->config['site_url'] . '/wap.php?c=My&a=house_order_before&order_id=' . $id  . '&otherrm=1">点击缴费</a>', 'text');
        }elseif($id > 600000000){
            $id -= 600000000;

            $group_start = M('Group_start')->where(array('id'=>$id))->find();

            $start_user = D('User')->get_user($group_start['uid']);
            $now_group = M('Group')->where(array('group_id'=>$group_start['group_id']))->find();
            $group_image_class = new group_image();
            $tmp_pic_arr = explode(';',$now_group['pic']);
            $image = $group_image_class->get_image_by_path($tmp_pic_arr[0], 's');
            $return[] = array('['.$this->config['group_alias_name'].']' . $now_group['s_name'], $now_group['name'], $image, $this->config['site_url'] . "/wap.php?g=Wap&c=Group&a=detail&wxscan=1&group_id={$now_group['group_id']}&gid={$id}");
            $return[] = array($start_user['nickname'].'邀请您参加他的拼团',$now_group['name'],$start_user['avatar'], $this->config['site_url'] . "/wap.php?g=Wap&c=Group&a=detail&wxscan=1&group_id={$now_group['group_id']}&gid={$id}");

            return array($return ,'news');
        } elseif($id > 500000000 && $openid){
            //share_type 为normal即推广给普通用户
            if ($id > 520000000) {
                $id -= 520000000;
                $share_type = 'unit_manager';
                $key_info = 'unit_manager_employee_extension';
            } elseif ($id > 510000000) {
                $id -= 510000000;
                $share_type = 'friend';
                $key_info = 'friend_employee_extension';
            } else {
                $id -= 500000000;
                $share_type = 'normal';
                $key_info = 'normal_employee_extension';
            }
            $spread_info = M('Employee_user_spread_qrcode')->where(array('uid'=>$id, 'share_type' => $share_type))->find();
            $url = parse_url($spread_info['url']);
            $option = explode('&',$url['query']);
            foreach($option as $vo){
                $tmp = explode('=',$vo);
                $options[$tmp[0]]=$tmp[1];
            }
            if( !empty($options['openid']) && $openid && $options['openid'] != $openid && !M('Merchant_spread')->where(array('openid'=>$openid,'mer_id'=>array('gt',0)))->find() ){
                $spread_user = D('User')->get_user($options['openid'],'openid');
                if(!empty($spread_user)){
                    $now_user = D('User')->get_user($openid,'openid');
                    if(empty($now_user)||$this->config['user_spread_replace']){
                        $canSpread = true;
                        if($this->config['user_spread_replace']){
                            $user_spread_info = D('User_spread')->where(array('openid' => $openid))->find();
                            if($user_spread_info['spread_openid'] == $openid){
                                $canSpread = false;
                            }
                        }
                        if($canSpread){
                            D('User_spread')->where(array('openid'=>$openid))->delete();
                            D('User_spread')->data(array('spread_openid'=>$options['openid'],'spread_uid'=>$spread_user['uid'],'openid'=>$openid))->add();
                            D('User_spread')->send_spread_msg($openid,$options['openid']);
                        }
                    }
                }
            }

            if($res = M('Employee_platform')->where(array('key'=>$key_info, 'share_type' => $share_type))->find()){
                $return[] = array($res['title'], $res['content'], $this->config['site_url'].$res['pic'], $spread_info['url']);
            }else{
                $return[] = array($this->config['user_spread_qrcode_keyword'], '', $this->config['site_logo'], $spread_info['url']);
            }

            return array($return, 'news');

        } elseif ($id > 400000000 && $openid) {
            $id -= 400000000;
            $m_merchant_workers = M('Merchant_workers');
            if ($m_merchant_workers->field(true)->where(array('openid' => $openid))->find()) {
                return array('该微信号已经绑定了，不能重复绑定', 'text');
            }
            if ($workers = $m_merchant_workers->field(true)->where(array('merchant_worker_id' => $id))->find()) {
                if ($workers['openid'] != $openid) {
                    import('ORG.Net.Http');
                    $http = new Http();
                    $data = array('openid' => $openid);
                    $access_token_array = D('Access_token_expires')->get_access_token();
                    if (!$access_token_array['errcode']) {
                        $return = $http->curlGet('https://api.weixin.qq.com/cgi-bin/user/info?access_token=' . $access_token_array['access_token'] . '&openid=' . $openid . '&lang=zh_CN');
                        $userifo = json_decode($return, true);
                        $data['nickname'] = isset($userifo['nickname']) ? $userifo['nickname'] : '';
                        $data['avatar'] = isset($userifo['headimgurl']) ? $userifo['headimgurl'] : '';
                    }
                    $m_merchant_workers->where(array('merchant_worker_id' => $id))->save($data);
                }
                return array('绑定成功', 'text');
            } else {
                return array('没有改账号信息', 'text');
            }
        }

        if ($recognition = M("Recognition")->field(true)->where(array('id' => $id))->find()) {
            switch ($recognition['third_type']) {
                case 'smart_city_car':
                    $now_car = D('Smart_city_car')->field(true)->where(array('car_id' => $recognition['third_id']))->find();

                    if(empty($now_car)){
                        $return[] = array('点击通知车主挪车', '该车辆没有录入,您也可以生成一张挪车二维码', $this->config['site_url'].'/static/fonts/car_notice.png', $this->config['site_url'].U('City_car/index'));
                    }else{
                        $spread_user = D('User')->get_user($now_car['uid']);
                        if( !empty($spread_user['openid']) && $openid && $spread_user['openid'] != $openid && !M('Merchant_spread')->where(array('openid'=>$openid,'mer_id'=>array('gt',0)))->find() ){
                            //$spread_user = D('User')->get_user($user['openid']);
                            $now_user = D('User')->get_user($openid,'openid');
                            if(empty($now_user)||$this->config['user_spread_replace']){
                                D('User_spread')->where(array('openid'=>$openid))->delete();
                                D('User_spread')->data(array('spread_openid'=>$spread_user['openid'],'spread_uid'=>$spread_user['uid'],'openid'=>$openid))->add();
                            }
                        }

                        $return[] = array('点击通知车主挪车', '点击查看车主联系方式,您也可以生成一张挪车二维码', $this->config['site_url'].'/static/fonts/car_notice.png', $this->config['site_url'].U('City_car/car_num',array('car_id'=>$recognition['third_id'])));
                    }
                    break;
                case 'group':
                    $now_group = D("Group")->field(true)->where(array('group_id' => $recognition['third_id']))->find();
                    $now_merchant = D('Merchant')->get_info($now_group['mer_id']);
                    $group_image_class = new group_image();
                    $tmp_pic_arr = explode(';',$now_group['pic']);
                    $image = $group_image_class->get_image_by_path($tmp_pic_arr[0], 's');
                    $return[] = array('['.$this->config['group_alias_name'].']' . $now_group['s_name'], $now_group['name'], $image, $this->config['site_url'] . "/wap.php?g=Wap&c=Group&a=detail&wxscan=1&group_id={$now_group['group_id']}");
                    $this->saverelation($openid, $now_group['mer_id'], 0);
                    $now_merchant['scan_group_show_other']==1 && $return = $this->other_message($return, $now_group['mer_id'], $now_group['group_id']);

                    //添加商家推广记录
                    D('Merchant_spread')->spread_add($now_group['mer_id'], $openid,'group');

                    break;
                case 'merchant':
                    $now_merchant = D("Merchant")->field(true)->where(array('mer_id' => $recognition['third_id']))->find();
                    //添加商家推广记录
                    D('Merchant_spread')->spread_add($recognition['third_id'], $openid,'merchant');
                    if($res = M('Merchant_qrcode_content')->where(array('mer_id'=>$recognition['third_id']))->find()){
                        $res = unserialize($res['content']);
                        foreach ($res['title'] as $key=>$v) {
                            $return[]=array(
                                $v, $res['info'][$key], $res['img'][$key], html_entity_decode($res['url'][$key])
                            );
                        }
                        $this->saverelation($openid, $now_merchant['mer_id'], 1, $issubscribe);
                        break;
                    }
                    $pic = '';
                    if ($now_merchant['pic_info']) {
                        $images = explode(";", $now_merchant['pic_info']);
                        $merchant_image_class = new merchant_image();
                        $images = explode(";", $images[0]);
                        $pic = $merchant_image_class->get_image_by_path($images[0]);
                    }
                    $mer_str = empty($this->config['merchant_alias_name']) ? '' : '[' . $this->config['merchant_alias_name'] . ']';
                    $return[] = array($mer_str . $now_merchant['name'], $now_merchant['txt_info'], $pic, $this->config['site_url'] . "/wap.php?g=Wap&c=Index&a=index&wxscan=1&token={$recognition['third_id']}");
                    $return = $this->other_message($return, $now_merchant['mer_id']);
                    $this->saverelation($openid, $now_merchant['mer_id'], 1, $issubscribe);
                    break;
                case 'merchantstore':
                    $store = D("Merchant_store")->field(true)->where(array('store_id' => $recognition['third_id']))->find();
                    //添加商家推广记录
                    D('Merchant_spread')->spread_add($store['mer_id'], $openid,'merchantstore',$store['store_id']);
                    $this->saverelation($openid, $store['mer_id'], 0);
                    $store_image_class = new store_image();
                    $images = $store_image_class->get_allImage_by_path($store['pic_info']);
                    $img = array_shift($images);
                    $return[] = array('['.$this->config['store_alias_name'].']' . $store['name'], $store['txt_info'], $img, $this->config['site_url'] . "/wap.php?c=Merchant&a=shop&wxscan=1&store_id={$store['store_id']}");
                    break;
                case 'shop':
                    $store = D("Merchant_store")->field(true)->where(array('store_id' => $recognition['third_id']))->find();
                    //添加商家推广记录
                    D('Merchant_spread')->spread_add($store['mer_id'], $openid,'shop');
                    $this->saverelation($openid, $store['mer_id'], 0);
                    $store_image_class = new store_image();
                    $images = $store_image_class->get_allImage_by_path($store['pic_info']);
                    $img = array_shift($images);
                    $return[] = array('['.$this->config['shop_alias_name'].']' . $store['name'], $store['txt_info'], $img, $this->config['site_url'] . "/wap.php?c=Shop&a=index&shop-id={$store['store_id']}&wxscan=1");
                    break;
                case 'mall':
                    $store = D("Merchant_store")->field(true)->where(array('store_id' => $recognition['third_id']))->find();
                    //添加商家推广记录
                    D('Merchant_spread')->spread_add($store['mer_id'], $openid,'mall');
                    $this->saverelation($openid, $store['mer_id'], 0);
                    $store_image_class = new store_image();
                    $images = $store_image_class->get_allImage_by_path($store['pic_info']);
                    $img = array_shift($images);
                    $return[] = array('['.$this->config['mall_alias_name'].']' . $store['name'], $store['txt_info'], $img, $this->config['site_url'] . "/wap.php?c=Mall&a=store&store_id={$store['store_id']}");
                    break;
                case 'meal':
                    $store = D("Merchant_store")->field(true)->where(array('store_id' => $recognition['third_id']))->find();
                    //添加商家推广记录
                    D('Merchant_spread')->spread_add($store['mer_id'], $openid,'meal');
                    $this->saverelation($openid, $store['mer_id'], 0);
                    $store_image_class = new store_image();
                    $images = $store_image_class->get_allImage_by_path($store['pic_info']);
                    $img = array_shift($images);
                    $return[] = array('['.$this->config['meal_alias_name'].']' . $store['name'], $store['txt_info'], $img, $this->config['site_url'] . "/wap.php?c=Foodshop&a=shop&wxscan=1&store_id={$store['store_id']}");
                    break;
                case 'lottery':
                    $lottery = D("Lottery")->field(true)->where(array('id' => $recognition['third_id']))->find();
                    switch ($lottery['type']){
                        case 1:
                            $return[] = array('[活动]' . $lottery['title'], $lottery['info'], $this->config['site_url'] . $lottery['starpicurl'], $this->config['site_url'] . "/wap.php?c=Lottery&a=index&wxscan=1&token={$lottery['token']}&id={$lottery['id']}");
                            break;
                        case 2:
                            $return[] = array('[活动]' . $lottery['title'], $lottery['info'], $this->config['site_url'] . $lottery['starpicurl'], $this->config['site_url'] . "/wap.php?c=Guajiang&a=index&wxscan=1&token={$lottery['token']}&id={$lottery['id']}");
                            break;
                        case 3:
                            $return[] = array('[活动]' . $lottery['title'], $lottery['info'], $this->config['site_url'] . $lottery['starpicurl'], $this->config['site_url'] . "/wap.php?c=Coupon&a=index&wxscan=1&token={$lottery['token']}&id={$lottery['id']}");
                            break;
                        case 4:
                            $return[] = array('[活动]' . $lottery['title'], $lottery['info'], $this->config['site_url'] . $lottery['starpicurl'], $this->config['site_url'] . "/wap.php?c=LuckyFruit&a=index&wxscan=1&token={$lottery['token']}&id={$lottery['id']}");
                            break;
                        case 5:
                            $return[] = array('[活动]' . $lottery['title'], $lottery['info'], $this->config['site_url'] . $lottery['starpicurl'], $this->config['site_url'] . "/wap.php?c=GoldenEgg&a=index&wxscan=1&token={$lottery['token']}&id={$lottery['id']}");
                            break;
                    }

                    //添加商家推广记录
                    D('Merchant_spread')->spread_add($lottery['mer_id'], $openid,'lettery');

                    break;
                case 'appoint':
                    $appoint_id = $recognition['third_id'];
                    $now_time = $_SERVER['REQUEST_TIME'];
                    $condition_table = array(C('DB_PREFIX').'appoint'=>'g',C('DB_PREFIX').'merchant'=>'m');
                    $condition_where = "`g`.`mer_id`=`m`.`mer_id` AND `g`.`check_status`='1' AND `g`.`appoint_status`='0' AND `m`.`status`='1' AND `g`.`start_time`<'$now_time' AND `g`.`end_time`>'$now_time' AND `g`.`appoint_id` = $appoint_id";
                    $appointInfo = D()->table($condition_table)->field(true)->where($condition_where)->find();
                    //添加商家推广记录
                    D('Merchant_spread')->spread_add($appointInfo['mer_id'], $openid,'appoint');

                    if(!empty($appointInfo['pic'])){
                        $appoint_image_class = new appoint_image();
                        $tmp_pic_arr = explode(';',$appointInfo['pic']);
                        $appointInfo['list_pic'] = $appoint_image_class->get_image_by_path($tmp_pic_arr[0],'s');
                    }
                    $appointInfo['url'] = $this->config['site_url'].U('Wap/Appoint/detail',array('appoint_id'=>$appoint_id, 'wxscan' => 1));


                    $return[] = array('[预约]' . $appointInfo['appoint_name'], $appointInfo['appoint_content'], $appointInfo['list_pic'],$appointInfo['url']);


                    break;
                case 'wifi':
                    $this->saverelation($openid, $recognition['third_id'], 1, $issubscribe);
                    $xml = new SimpleXMLElement(file_get_contents("php://input"));
                    foreach($xml as $key=>$value){
                        $old_data[$key] = strval($value);
                    }
                    $route_xml = new SimpleXMLElement('<xml><ToUserName><![CDATA[]]></ToUserName><FromUserName><![CDATA[]]></FromUserName><CreateTime></CreateTime><MsgType><![CDATA[text]]></MsgType><Content><![CDATA[go]]></Content><MsgId>6215367772828639901</MsgId></xml>');
                    foreach ($route_xml as $key => $value) {
                        $route_data[$key] = strval($value);
                    }
                    $route_data['ToUserName'] = $old_data['ToUserName'];
                    $route_data['FromUserName'] = $old_data['FromUserName'];
                    $route_data['CreateTime'] = $_SERVER['REQUEST_TIME'];
                    // file_put_contents('runtime/test_'.mt_rand(0,100).'.php', var_export($route_data,true));
                    $xmlObj = new SimpleXMLElement('<xml></xml>');
                    $this->data2xml($xmlObj, $route_data);
                    $t_data = $this->api_notice_increment('http://we-cdn.net',$xmlObj->asXML());
                    if(!empty($t_data)){
                        $routeXml = new SimpleXMLElement($t_data);
                        $return_data = $routeXml->Articles->item[0];
                        foreach ($return_data as $key => $value) {
                            $route_arr[$key] = strval($value);
                        }
                        $return[] = array($route_arr['Title'], $route_arr['Description'], $route_arr['PicUrl'],$route_arr['Url']);
                    }

                    $now_merchant = D("Merchant")->field(true)->where(array('mer_id' => $recognition['third_id']))->find();

                    //添加商家推广记录
                    D('Merchant_spread')->spread_add($now_merchant['mer_id'], $openid,'wifi');
                    $pic = '';
                    if ($now_merchant['pic_info']) {
                        $images = explode(";", $now_merchant['pic_info']);
                        $merchant_image_class = new merchant_image();
                        $images = explode(";", $images[0]);
                        $pic = $merchant_image_class->get_image_by_path($images[0]);
                    }
                    $mer_str = empty($this->config['merchant_alias_name']) ? '' : '[' . $this->config['merchant_alias_name'] . ']';
                    $return[] = array($mer_str . $now_merchant['name'], $now_merchant['txt_info'], $pic, $this->config['site_url'] . "/wap.php?g=Wap&c=Index&a=index&wxscan=1&token={$recognition['third_id']}");
                    $return = $this->other_message($return, $now_merchant['mer_id']);
                    $return = array_slice($return,0,6);

                    break;
                case 'chanel':
                    $chanel_id = $recognition['third_id'];
                    $chanelMsg = M('Chanel_msg_list')->field('`type_id`')->where(array('chanel_id'=>$chanel_id))->find();
                    if($chanelMsg['type_id']>0){
                        //添加商家推广记录
                        D('Merchant_spread')->spread_add($chanelMsg['type_id'], $openid,'chanel');
                    }
                    if($chanelMsg){
                        if($chanelMsg['type_id'] > 0){
                            $this->saverelation($openid, $chanelMsg['type_id'], 1, $issubscribe);
                        }
                        $chanelInfo = M('Chanel_msg_content')->field('title,img,des,url')->where(array('fid'=>$chanel_id))->order('`sort` DESC,`id` ASC')->select();
                        foreach($chanelInfo as $v){
                            $return[] = array($v['title'],$v['des'],replace_file_domain($v['img']),$v['url']);
                        }
                    }
                    break;
                case 'scan_get_score':
                    $chanel_id = $recognition['third_id'];
                    $chanelMsg = M('Scan_get_score_list')->field('`type_id`')->where(array('chanel_id'=>$chanel_id))->find();
                    $chanelInfo = M('Scan_get_score_content')->field('title,img,des,url')->where(array('fid'=>$chanel_id))->order('`sort` DESC,`id` ASC')->select();
                    foreach($chanelInfo as $v){
                        $return[] = array($v['title'],$v['des'],$v['img'],$v['url']);
                    }
                    break;
                case 'house':
                    $village_id = $recognition['third_id'];
                    $villageInfo = D('House_village')->field('wx_image,wx_desc,village_name')->where('village_id = '.$village_id)->find();
                    $wx_url = U('Wap/House/village',array('village_id'=>$village_id, 'wxscan' => 1));
                    if (strpos($villageInfo['wx_image'], 'upload/promote') !== false) {
                        $wx_img = replace_file_domain($villageInfo['wx_image']);
                    } else {
                        $wx_img = file_domain() . '/upload/promote/' . $villageInfo['wx_image'];
                    }
                    $wx_title = '['.$villageInfo["village_name"].']';
                    $return[] = array($wx_title,$villageInfo['wx_desc'],$wx_img,$this->config['site_url'].$wx_url);

                    break;
                case 'coupon':
                    $coupon_id = $recognition['third_id'];
                    $couponInfo = D('System_coupon')->field('name,img,des')->where(array('coupon_id'=>$coupon_id))->find();
                    $coupon_url = $this->config['site_url'].U('Wap/Systemcoupon/index');
                    $return[] = array($couponInfo['name'], $couponInfo['des'], replace_file_domain($couponInfo['img']), $coupon_url);
                    break;
                case 'employee_spread_normal':
                    $spread_id = $recognition['third_id'];
                    $share_type = 'normal';
                    $key_info = 'normal_employee_extension';
                    $spread_info = M('Employee_user_spread_qrcode')->where(array('qrcode_id'=>$spread_id, 'share_type' => $share_type))->find();
                    $url = parse_url($spread_info['url']);
                    $option = explode('&',$url['query']);
                    foreach($option as $vo){
                        $tmp = explode('=',$vo);
                        $options[$tmp[0]]=$tmp[1];
                    }

                    if($this->config['open_user_spread'] && !empty($options['openid']) && $openid && $options['openid'] != $openid && !M('Merchant_spread')->where(array('openid'=>$openid,'mer_id'=>array('gt',0)))->find() ){
                        $spread_user = D('User')->get_user($options['openid'],'openid');
                        if(!empty($spread_user)){
                            $now_user = D('User')->get_user($openid,'openid');
                            if(empty($now_user)||$this->config['user_spread_replace']){
                                $canSpread = true;
                                if($this->config['user_spread_replace']){
                                    $user_spread_info = D('User_spread')->where(array('openid' => $openid))->find();
                                    if($user_spread_info['spread_openid'] == $openid){
                                        $canSpread = false;
                                    }
                                }
                                if($canSpread){
                                    D('User_spread')->where(array('openid'=>$openid))->delete();
                                    $now_user['wxapp_openid'] && D('User_spread')->where(array('openid'=>$now_user['wxapp_openid']))->delete();
                                    D('User_spread')->data(array('spread_openid'=>$options['openid'],'spread_uid'=>$spread_user['uid'],'openid'=>$openid))->add();
                                    D('User_spread')->send_spread_msg($openid,$options['openid']);
                                }
                            }
                        }
                    }
                    if($res = M('Employee_platform')->where(array('key'=>$key_info, 'share_type' => $share_type))->find()){
                        $return[] = array($res['title'], $res['content'], file_domain().$res['pic'], $spread_info['url']);
                    }else{
                        $return[] = array($this->config['user_spread_qrcode_keyword'], '', $this->config['site_logo'], $spread_info['url']);
                    }

                    break;
                case 'employee_spread_friend':
                    $spread_id = $recognition['third_id'];
                    $share_type = 'friend';
                    $key_info = 'friend_employee_extension';
                    $spread_info = M('Employee_user_spread_qrcode')->where(array('qrcode_id'=>$spread_id, 'share_type' => $share_type))->find();
                    $url = parse_url($spread_info['url']);
                    $option = explode('&',$url['query']);
                    foreach($option as $vo){
                        $tmp = explode('=',$vo);
                        $options[$tmp[0]]=$tmp[1];
                    }

                    if($this->config['open_user_spread'] && !empty($options['openid']) && $openid && $options['openid'] != $openid && !M('Merchant_spread')->where(array('openid'=>$openid,'mer_id'=>array('gt',0)))->find() ){
                        $spread_user = D('User')->get_user($options['openid'],'openid');
                        if(!empty($spread_user)){
                            $now_user = D('User')->get_user($openid,'openid');
                            if(empty($now_user)||$this->config['user_spread_replace']){
                                $canSpread = true;
                                if($this->config['user_spread_replace']){
                                    $user_spread_info = D('User_spread')->where(array('openid' => $openid))->find();
                                    if($user_spread_info['spread_openid'] == $openid){
                                        $canSpread = false;
                                    }
                                }
                                if($canSpread){
                                    D('User_spread')->where(array('openid'=>$openid))->delete();
                                    $now_user['wxapp_openid'] && D('User_spread')->where(array('openid'=>$now_user['wxapp_openid']))->delete();
                                    D('User_spread')->data(array('spread_openid'=>$options['openid'],'spread_uid'=>$spread_user['uid'],'openid'=>$openid))->add();
                                    D('User_spread')->send_spread_msg($openid,$options['openid']);
                                }
                            }
                        }
                    }
                    if($res = M('Employee_platform')->where(array('key'=>$key_info, 'share_type' => $share_type))->find()){
                        $return[] = array($res['title'], $res['content'], file_domain().$res['pic'], $spread_info['url']);
                    }else{
                        $return[] = array($this->config['user_spread_qrcode_keyword'], '', $this->config['site_logo'], $spread_info['url']);
                    }

                    break;
                case 'employee_spread_unit_manager':
                    $spread_id = $recognition['third_id'];
                    $share_type = 'unit_manager';
                    $key_info = 'unit_manager_employee_extension';
                    $spread_info = M('Employee_user_spread_qrcode')->where(array('qrcode_id'=>$spread_id, 'share_type' => $share_type))->find();
                    $url = parse_url($spread_info['url']);
                    $option = explode('&',$url['query']);
                    foreach($option as $vo){
                        $tmp = explode('=',$vo);
                        $options[$tmp[0]]=$tmp[1];
                    }

                    if($this->config['open_user_spread'] && !empty($options['openid']) && $openid && $options['openid'] != $openid && !M('Merchant_spread')->where(array('openid'=>$openid,'mer_id'=>array('gt',0)))->find() ){
                        $spread_user = D('User')->get_user($options['openid'],'openid');
                        if(!empty($spread_user)){
                            $now_user = D('User')->get_user($openid,'openid');
                            if(empty($now_user)||$this->config['user_spread_replace']){
                                $canSpread = true;
                                if($this->config['user_spread_replace']){
                                    $user_spread_info = D('User_spread')->where(array('openid' => $openid))->find();
                                    if($user_spread_info['spread_openid'] == $openid){
                                        $canSpread = false;
                                    }
                                }
                                if($canSpread){
                                    D('User_spread')->where(array('openid'=>$openid))->delete();
                                    $now_user['wxapp_openid'] && D('User_spread')->where(array('openid'=>$now_user['wxapp_openid']))->delete();
                                    D('User_spread')->data(array('spread_openid'=>$options['openid'],'spread_uid'=>$spread_user['uid'],'openid'=>$openid))->add();
                                    D('User_spread')->send_spread_msg($openid,$options['openid']);
                                }
                            }
                        }
                    }
                    if($res = M('Employee_platform')->where(array('key'=>$key_info, 'share_type' => $share_type))->find()){
                        $return[] = array($res['title'], $res['content'], file_domain().$res['pic'], $spread_info['url']);
                    }else{
                        $return[] = array($this->config['user_spread_qrcode_keyword'], '', $this->config['site_logo'], $spread_info['url']);
                    }

                    break;
                case 'spread':
                    $spread_id = $recognition['third_id'];
                    $spread_info = D('User_spread_qrcode')->field(true)->where(array('qrcode_id'=>$spread_id))->find();
                    $url = parse_url($spread_info['url']);
                    $option = explode('&',$url['query']);
                    foreach($option as $vo){
                        $tmp = explode('=',$vo);
                        $options[$tmp[0]]=$tmp[1];
                    }

                    if($this->config['open_user_spread'] && !empty($options['openid']) && $openid && $options['openid'] != $openid && !M('Merchant_spread')->where(array('openid'=>$openid,'mer_id'=>array('gt',0)))->find() ){
                        $spread_user = D('User')->get_user($options['openid'],'openid');
                        if(!empty($spread_user)){
                            $now_user = D('User')->get_user($openid,'openid');
                            if(empty($now_user)||$this->config['user_spread_replace']){
                                $canSpread = true;
                                if($this->config['user_spread_replace']){
                                    $user_spread_info = D('User_spread')->where(array('openid' => $openid))->find();
                                    if($user_spread_info['spread_openid'] == $openid){
                                        $canSpread = false;
                                    }
                                }
                                if($canSpread){
                                    D('User_spread')->where(array('openid'=>$openid))->delete();
                                    $now_user['wxapp_openid'] && D('User_spread')->where(array('openid'=>$now_user['wxapp_openid']))->delete();
                                    D('User_spread')->data(array('spread_openid'=>$options['openid'],'spread_uid'=>$spread_user['uid'],'openid'=>$openid))->add();
                                    D('User_spread')->send_spread_msg($openid,$options['openid']);
                                }
                            }
                        }
                    }
                    if($res = M('Platform')->where(array('key'=>array('like','%用户推广二维码%')))->find()){
                        $return[] = array($res['title'], $res['content'], $this->config['site_url'].$res['pic'], $spread_info['url']);
                    }else{
                        $return[] = array($this->config['user_spread_qrcode_keyword'], '', $this->config['site_logo'], $spread_info['url']);
                    }

                    break;
                case 'card_coupon':
                    $coupon_id = $recognition['third_id'];
                    $couponInfo = D('Card_new_coupon')->field('name,img,des')->where(array('coupon_id'=>$coupon_id))->find();
                    $coupon_url = $this->config['site_url'].U('Wap/Cardcoupon/show',array('coupon_id'=>$coupon_id));
                    $return[] = array($couponInfo['name'],$couponInfo['des'],$couponInfo['img'],$coupon_url);
                    break;
                case 'gift':
                    $gift_id = $recognition['third_id'];
                    $gift_fields = 'gift_name,wap_pic,intro';
                    $giftInfo = D('Gift')->gift_detail(array('gift_id'=>$gift_id),$gift_fields);
                    $giftInfo = $giftInfo['detail'];
                    $gift_url = $this->config['site_url'].U('Wap/Gift/gift_detail',array('gift_id'=>$gift_id));
                    $giftInfo['img'] = $giftInfo['wap_pic_list'][0]['url'];
                    $return[] = array($giftInfo['gift_name'],$giftInfo['intro'],$giftInfo['img'],$gift_url);
                    break;
                case 'fixed_point_delive':
                    //外卖定点配送
                    $now_room = D("Shop_fixed_point_room")->field(true)->where(array('room_id' => $recognition['third_id']))->find();

                    $store = D("Merchant_store")->field(true)->where(array('store_id' => $now_room['store_id']))->find();
                    $store_image_class = new store_image();
                    $images = $store_image_class->get_allImage_by_path($store['pic_info']);
                    $img = array_shift($images);
                    $return[] = array('['.$this->config['shop_alias_name'].']' . $store['name'], $store['txt_info'], $img, $this->config['site_url'] . "/wap.php?c=Shop&a=classic_shop&shop_id={$store['store_id']}&room_id=".$recognition['third_id']);
                    break;
//				case 'shopgoods':
//					$goods_id = $recognition['third_id'];
//					$goodsInfo = D('Shop_goods')->where(array('goods_id'=>$goods_id))->find();
//					$goods_image_class = new goods_image();
//					$tmp_pic_arr = explode(';', $goodsInfo['image']);
//					$img_url = $goods_image_class->get_image_by_path($tmp_pic_arr[0]);
//					$goodsurl = $this->config['site_url'].U('Wap/Gift/gift_detail',array('gift_id'=>$gift_id));
//					$goodsInfo['img'] = $img_url;
//					$return[] = array($goodsInfo['name'],$goodsInfo['des'],$goodsInfo['img'],$goodsurl);
//					break;
            }
        }

        if ($return) {
            return array($return, 'news');
        }
        return false;
    }
    
    private function txt_img($pigcms_id, $mer_id, $info_type=0)
    {
    	if ($data = D('House_source_material')->where(array('pigcms_id' => $pigcms_id, 'mer_id' => $mer_id, 'info_type' => $info_type))->find()) {
    		$it_ids = unserialize($data['it_ids']);
    		if ($data['type'] == 0) {
    			$id = isset($it_ids[0]) ? intval($it_ids[0]) : 0;
    			$image_text = D('House_image_text')->where(array('pigcms_id' => $id))->find();
    			$url = $this->config['site_url'] . '/wap.php?g=Wap&c=Article&a=hdetail&imid=' . $image_text['pigcms_id'];
    			$return[] = array($image_text['title'], $image_text['digest'], $this->config['site_url'] . $image_text['cover_pic'], $url);
    		} else {
    			$image_texts = D('House_image_text')->where(array('pigcms_id' => array('in', $it_ids)))->select();
    			foreach ($image_texts as $image) {
    				$url = $this->config['site_url'] . '/wap.php?g=Wap&c=Article&a=hdetail&imid=' . $image['pigcms_id'];
    				$return[] = array($image['title'], $image['digest'], $this->config['site_url'] . $image['cover_pic'], $url);
    			}
    		}
    		return array($return, 'news');
    	} else {
    		return array("没有找到相关的应答", 'text');
    	}
    }
    
    
    private function special_keyword($key, $data = array(), $mer_id = 0, $info_type=0)
    {
    	$return = array();
		//关键字回复
		if ($mer_id) {
			if ($keyobj = D('House_keyword')->where(array('mer_id' => $mer_id, 'content' => $key, 'type' => $info_type))->find()) {
				if ($keyobj['table'] == 'House_text') {
					$text = D('House_text')->where(array('pigcms_id' => $keyobj['from_id']))->find();
					return array($text['content'], 'text');
				} else {
					return $this->txt_img($keyobj['from_id'], $mer_id, $info_type);
				}
			} elseif ($other = D('House_reply_other')->where(array('type' => 1, 'mer_id' => $mer_id, 'is_open' => 1, 'info_type' => $info_type))->find()) {
				if ($other['reply_type'] == 0) {
					return array($other['content'], 'text');
				} else {
					return $this->txt_img($other['from_id'], $mer_id, $info_type);
				}
			}
		}
		return array('亲，暂时没有找到与“' . $key . '”相关的内容！请更换内容。', 'text');

    }
    
    //连接路由操作
    private function route()
    {
		$nonce = isset($_REQUEST['nonce']) ? $_REQUEST['nonce'] : '';
		$sTimeStamp = isset($_REQUEST['timestamp']) ? $_REQUEST['timestamp'] : time();
		$msg_signature = isset($_REQUEST['msg_signature']) ? $_REQUEST['msg_signature'] : '';
		$xml = file_get_contents("php://input");
		
		
		import("@.ORG.aes.WXBizMsgCrypt");
		$pc = new WXBizMsgCrypt($this->config['wx_house_token'], $this->config['wx_house_encodingaeskey'], $this->config['wx_house_appid']);
		$sMsg = "";
		$pc->decryptMsg($msg_signature, $sTimeStamp, $nonce, $xml, $sMsg);
		
		$data = $this->api_notice_increment('http://we-cdn.net', $sMsg);
		$data = str_replace('<?xml version="1.0"?>', '', $data);
		$encryptMsg = "";
		$pc->encryptMsg($data, $sTimeStamp, $nonce, $encryptMsg);
		return $encryptMsg;
    }
    
    
    private function api_notice_increment($url, $data)
    {
    	$ch = curl_init();
		$headers = array(
				"User-Agent: Mozilla/5.0 (Windows NT 5.1; rv:14.0) Gecko/20100101 Firefox/14.0.1",
				"Accept-Language: en-us,en;q=0.5",
				"Referer:http://mp.weixin.qq.com/",
 				'Content-type: text/xml'
		);
    	curl_setopt($ch, CURLOPT_URL, $url);
    	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
    	curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
    	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
    	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    	curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
    	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	$tmpInfo = curl_exec($ch);
    	curl_close($ch);
    	if (curl_errno($ch)) {
    		return false;
    	} else {
    		return $tmpInfo;
    	}
    }

    public function curlGet($url){
        $ch = curl_init();
        $header = "Accept-Charset: utf-8";
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $temp = curl_exec($ch);
        return $temp;
    }
}