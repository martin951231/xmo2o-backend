<?php
/*
 * 小程序授权
 *
 * @  Writers    wanziyang
 * @  BuildTime  2019/10/31 13:46
 * 
 */
class NewWeixinAppAction extends BaseAction{
    protected $property_id;
    protected $property_session;
	protected $wxapp_domain;

    public function _initialize(){
        $this->actionLoginWhite = ['NewWeixinApp#auth_back'];
        parent::_initialize();
        $this->property_session = session('property');
        $this->property_id = $this->property_session['id'];

		$this->wxapp_domain = C('config.house_wxapp_domain') ? 'http://' . C('config.house_wxapp_domain') : 'https://o2o-service.pigcms.com';
    }

	protected function get_bind(){
		$database_app_bind = D('Weixin_app_bind');
		$condition_app_bind['bind_type'] = '1';
		$condition_app_bind['other_id'] = $this->property_id;
		$now_bind = $database_app_bind->where($condition_app_bind)->find();
		return $now_bind;
	}

	public function authorize_redirect(){
        $url = $this->wxapp_domain . '/house_wxapp/authorize.php?'.http_build_query($_SERVER['original_get']);
        redirect($url);
	}

	public function authorize(){
        $auth_back = $this->config['site_url'].'/shequ.php?g=House&c=NewWeixinApp&a=auth_back&property_id='.$this->property_id;
        $go_url = $this->wxapp_domain . '/house_wxapp/authorize.php?domain='.$_SERVER['SERVER_NAME'].'&label='.$this->property_id.'&type=0&auth_back='.urlencode($auth_back);

        import('ORG.Net.Http');
        $authorize_url = Http::curlGet($go_url);

        echo '<script>location.href="'.$authorize_url.'"</script>';
	}

    public function index(){
		$now_bind = $this->get_bind();

        if(C('config.house_wxapp_domain')){
            $go_url = $this->config['site_url'].'/shequ.php?g=House&c=NewWeixinApp&a=authorize';
        }else{
            $v2o_ticket = $_COOKIE['property_access_token'] ? $_COOKIE['property_access_token'] : '';
            if ($v2o_ticket) {
                $ticket = Token::checkToken($v2o_ticket);
                $login_role = isset($ticket['extends'])?$ticket['extends']:3;
            } else {
                $login_role = 3;
            }
            $auth_back = $this->config['site_url'].'/shequ.php?g=House&c=NewWeixinApp&a=auth_back&property_id='.$this->property_id.'&login_role='.$login_role;
            $go_url = $this->wxapp_domain . '/house_wxapp/authorize.php?domain='.$_SERVER['SERVER_NAME'].'&label='.$this->property_id.'&type=0&auth_back='.urlencode($auth_back);
        }
		$this->assign('go_url',$go_url);

		if($now_bind['authorizer_appid'] && $now_bind['status'] == 1){
			import('ORG.Net.Http');
			$url = $this->wxapp_domain . '/house_wxapp/wxapp.json';
			$next_bind_json = Http::curlGet($url);

			$new_bind = json_decode($next_bind_json,true);

			$tester_list = D('Weixin_app_tester')->where(array('bind_id'=>$now_bind['bind_id']))->order('`tester_id` ASC')->select();

			//如果提交版本大于当前版本，请求微信校验。
			if($now_bind['update_version_id'] > $now_bind['now_version_id']){
                $audit_result = $this->get_audit_result();
				if($audit_result['status'] == 0){
					$data_bind['now_version'] = $now_bind['update_version'];
					$data_bind['now_version_id'] = $now_bind['update_version_id'];
					$data_bind['update_version'] = '';
					$data_bind['update_version_id'] = '0';
					$data_bind['update_error_tip'] = '';
					M('Weixin_app_bind')->where(array('bind_id'=>$now_bind['bind_id']))->data($data_bind)->save();
					$now_bind = $this->get_bind();
				}else if($audit_result['status'] == 1){
					$data_bind['update_version'] = '';
					$data_bind['update_version_id'] = '0';
					$data_bind['update_error_tip'] = $audit_result['reason'];
					M('Weixin_app_bind')->where(array('bind_id'=>$now_bind['bind_id']))->data($data_bind)->save();
					$now_bind = $this->get_bind();
				}
				$this->assign('audit_result',$audit_result);
			}

            if ($now_bind['update_error_tip']) {
                $now_bind['update_error_tip'] = str_replace('"',"‘",$now_bind['update_error_tip']);
                $now_bind['update_error_tip'] = str_replace(array("\r\n", "\r", "\n"), "", $now_bind['update_error_tip']);
            }
			$this->assign('now_bind',$now_bind);
			$this->assign('new_bind',$new_bind);
			$this->assign('tester_list',$tester_list);

            $result2 = $this->authorizer_access_token($now_bind);
            if (isset($result2['authorizer_access_token'])&&$result2['authorizer_access_token']) {
                $resultPrivacy = $this->get_privacy_interface($result2['authorizer_access_token']);
                if (isset($resultPrivacy['interface_list'])&&$resultPrivacy['interface_list']) {
                    $this->assign('interface_list',$resultPrivacy['interface_list']);
                }
            }

			$this->display('app_info');
		}else{
			$this->display();
		}
    }
	public function auth_back(){
        $property_id = I('property_id');
        if (!$property_id) {
            $property_id = $this->property_id;
        }
        $login_role = I('login_role');
        if (!$login_role) {
            $login_role = 3;
        }
        $database_app_bind = D('Weixin_app_bind');
        $condition_app_bind['bind_type'] = '1';
        $condition_app_bind['other_id'] = $property_id;
        $data_app_bind['authorizer_appid'] = $_GET['authorizer_appid'];
        $data_app_bind['appid'] = $_GET['authorizer_appid'];
        $data_app_bind['authorizer_access_token'] = $_GET['authorizer_access_token'];
        $data_app_bind['authorizer_refresh_token'] = $_GET['authorizer_refresh_token'];
        $data_app_bind['status'] = '1';
        if($database_app_bind->where($condition_app_bind)->find()){
            $database_app_bind->where($condition_app_bind)->data($data_app_bind)->save();
        }else{
            $data_app_bind['bind_type'] = '1';
            $data_app_bind['other_id'] = $this->property_id;
            $database_app_bind->data($data_app_bind)->add();
        }
        $v2o_ticket = $_COOKIE['property_access_token'] ? $_COOKIE['property_access_token'] : '';
        $redirectUrl = $this->config['site_url']."/shequ.php?g=House&c=NewWeixinApp&a=index&iframe=true";
        if (!$v2o_ticket) {
            $ticket = Token::createToken($property_id,$login_role);
            $v2o_ticket = $ticket;
        }
        $redirectUrl .= '&propertyToken='.$v2o_ticket;
        fdump_api(['$_GET'=> $_GET,'$_POST'=>$_POST,'$property_id' => $property_id,'$redirectUrl' => $redirectUrl],'NewWeixinApp/authBackLog',1);
        redirect($redirectUrl);
	}
	protected function authorizer_access_token($now_bind){
		import('ORG.Net.Http');
		$url = $this->wxapp_domain . '/house_wxapp/api_authorizer_token.php';
		$data = array('authorizer_appid'=>$now_bind['authorizer_appid'],'authorizer_refresh_token'=>$now_bind['authorizer_refresh_token']);
		$result2 = Http::curlPost($url, $data);

		if($result2['authorizer_access_token']){
			$condition_app_bind['bind_id'] = $now_bind['bind_id'];
			$data_app_bind['authorizer_access_token'] = $result2['authorizer_access_token'];
			$data_app_bind['authorizer_refresh_token'] = $result2['authorizer_refresh_token'];
			D('Weixin_app_bind')->where($condition_app_bind)->data($data_app_bind)->save();
		}

		return $result2;
	}
	public function add_tester(){
		import('ORG.Net.Http');
		$now_bind = $this->get_bind();
		$result2 = $this->authorizer_access_token($now_bind);
		if($result2['authorizer_access_token']){
			$url = $this->wxapp_domain . '/house_wxapp/bind_tester.php';
			$data = array('wechatid'=>$_POST['wechatid'],'authorizer_access_token'=>$result2['authorizer_access_token']);
			$result3 = Http::curlPost($url, $data);
			if($result3['errcode'] == '85001'){
				$this->error(L_('微信号不存在或微信号设置为不可搜索'));
			}else if($result3['errcode'] == '85002'){
				$this->error(L_('小程序绑定的体验者数量达到上限'));
			}else if($result3['errcode'] == '85003'){
				$this->error(L_('微信号绑定的小程序体验者达到上限'));
			}

			$data_weixin_app_tester['bind_id'] = $now_bind['bind_id'];
			$data_weixin_app_tester['wechatid'] = $_POST['wechatid'];
			$data_weixin_app_tester['wechatname'] = $_POST['wechatname'];
			$data_weixin_app_tester['add_time'] = time();
			if(M('Weixin_app_tester')->data($data_weixin_app_tester)->add()){
				$this->success(L_('绑定成功'));
			}else{
				$this->error(L_('绑定失败，请重试'));
			}
		}else{
			$this->error(L_('绑定失败，请重试'));
		}
	}
	public function unbind_tester(){
		import('ORG.Net.Http');
		$now_bind = $this->get_bind();

		$now_tester = M('Weixin_app_tester')->where(array('bind_id'=>$now_bind['bind_id'],'tester_id'=>$_GET['id']))->find();


		$result2 = $this->authorizer_access_token($now_bind);
		if($result2['authorizer_access_token']){
			$url = $this->wxapp_domain . '/house_wxapp/unbind_tester.php';
			$data = array('wechatid'=>$_POST['wechatid'],'authorizer_access_token'=>$result2['authorizer_access_token']);
			$result3 = Http::curlPost($url, $data);
			if($result3 && $result3['errcode'] == '0'){
				$condition_weixin_app_tester['tester_id'] = $now_tester['tester_id'];
				if(M('Weixin_app_tester')->where($condition_weixin_app_tester)->delete()){
					$this->success(L_('解绑成功'));
				}else{
					$this->error(L_('解绑失败，请重试'));
				}
			}else if($result3['errcode'] == '-1'){
				$this->error($result3['errmsg'].L_('，管理员无法解除绑定'));
			}else{
				$this->error(L_('解绑失败，请重试'));
			}
		}else{
			$this->error(L_('解绑失败，请重试'));
		}
	}
    protected $requiredPrivateInfosArr = ['chooseAddress','getLocation','chooseLocation'];
	public function commit_version(){
		import('ORG.Net.Http');
		$now_bind = $this->get_bind();
        $url = $this->wxapp_domain . '/house_wxapp/wxapp.json';
        $next_bind_json = Http::curlGet($url);
        $new_bind = json_decode($next_bind_json,true);
        $now_version_id = isset($new_bind['now_version_id']) ? intval($new_bind['now_version_id']) : 0;
        $commit_version_id = isset($now_bind['commit_version_id']) ? intval($now_bind['commit_version_id']) : 0;
        if ($now_version_id > 0 && $now_version_id && $commit_version_id >= $now_version_id) {
            $this->error(L_('已提交代码最新版本，当前不支持提交'));
        }
        // 优先设置下域名 然后提交
        if (!isset($now_bind['modify_domain']) || !$now_bind['modify_domain']) {
            $result_access_token = $this->authorizer_access_token($now_bind);
            $url = $this->wxapp_domain . '/house_wxapp/modify_domain.php';
            $modify_domain_param = [];
            $modify_domain_param['domain'] = $_SERVER['HTTP_HOST'];
            $modify_domain_param['authorizer_access_token'] = $result_access_token['authorizer_access_token'];
            $modify_domain_result = Http::curlPost($url, $modify_domain_param);
            if ($modify_domain_result['errcode'] == 0) {
                $data_bind = [];
                $data_bind['modify_domain'] = $modify_domain_param['domain'];
                $data_bind['modify_domain_time'] = time();
                M('Weixin_app_bind')->where(array('bind_id'=>$now_bind['bind_id']))->data($data_bind)->save();
            }
        }
        
        $result2 = $this->authorizer_access_token($now_bind);
		$ext_json_arr = array(
			'extEnable' => true,
			'extAppid' => $now_bind['authorizer_appid'],
			'ext'  => array(
				'property_id' => $this->property_id,
				'mer_name' => $this->property_session['property_name'],
				'http_domain' => str_replace('http://','https://',$this->config['site_url']).'/',
			),
            'requiredPrivateInfos' => $this->requiredPrivateInfosArr
		);
		$url = $this->wxapp_domain . '/house_wxapp/commit.php';
        $post_param = [];
		$post_param['ext_json'] = json_encode($ext_json_arr);
		$post_param['authorizer_access_token'] = $result2['authorizer_access_token'];
		$result3 = Http::curlPost($url, $post_param);

		if($result3['errcode'] == 0){
            $data_bind = [];
            if (isset($result3['now_version']) && $result3['now_version'] && isset($result3['now_version_id']) && $result3['now_version_id']) {
                $data_bind['commit_version'] = $result3['now_version'];
                $data_bind['commit_version_id'] = $result3['now_version_id'];
            }
            if (isset($new_bind['now_version']) && $new_bind['now_version'] && isset($new_bind['now_version_id']) && $new_bind['now_version_id']) {
                $data_bind['commit_version'] = $new_bind['now_version'];
                $data_bind['commit_version_id'] = $new_bind['now_version_id'];
            }
			$data_bind['commit_time'] = time();
			if(M('Weixin_app_bind')->where(array('bind_id'=>$now_bind['bind_id']))->data($data_bind)->save()){
				$this->success(L_('提交成功'));
			}else{
				$this->error(L_('提交保存失败，请重试'));
			}
		}else{
			$this->error(L_('提交失败，错误原因：').$result3['errmsg']);
		}
	}
	public function get_qrcode(){
		import('ORG.Net.Http');
		$now_bind = $this->get_bind();
		$result2 = $this->authorizer_access_token($now_bind);

		$url = $this->wxapp_domain . '/house_wxapp/get_qrcode.php';
		$post_param['authorizer_access_token'] = $result2['authorizer_access_token'];
		$result3 = Http::curlPost($url, $post_param);
        if (strstr($result3['qrcode_url'], $this->wxapp_domain) == false) {
            $arrUrl = explode('house_wxapp/',$result3['qrcode_url']);
            if (isset($arrUrl[1])) {
                $result3['qrcode_url'] =  $this->wxapp_domain . '/house_wxapp/' . $arrUrl[1];
            }
        }
		$this->success($result3['qrcode_url']);
	}
	public function get_release_qrcode(){
		import('ORG.Net.Http');
		$now_bind = $this->get_bind();
		$result2 = $this->authorizer_access_token($now_bind);

		$url = $this->wxapp_domain . '/house_wxapp/get_release_qrcode.php';
		$post_param['authorizer_access_token'] = $result2['authorizer_access_token'];
		$result3 = Http::curlPost($url, $post_param);
        if (strstr($result3['qrcode_url'], $this->wxapp_domain) == false) {
            $arrUrl = explode('house_wxapp/',$result3['qrcode_url']);
            if (isset($arrUrl[1])) {
                $result3['qrcode_url'] =  $this->wxapp_domain . '/house_wxapp/' . $arrUrl[1];
            }
        }
		$this->success($result3['qrcode_url']);
	}
	public function get_category(){
		import('ORG.Net.Http');
		$now_bind = $this->get_bind();
		$result2 = $this->authorizer_access_token($now_bind);

		$url = $this->wxapp_domain . '/house_wxapp/get_category.php';
		$post_param['authorizer_access_token'] = $result2['authorizer_access_token'];
		$result3 = Http::curlPost($url, $post_param);

		$this->success($result3['category_list']);
	}
	public function submit_audit(){
		import('ORG.Net.Http');
		$now_bind = $this->get_bind();
		$result2 = $this->authorizer_access_token($now_bind);

		$category_arr = explode('||',$_POST['category']);

		$post_param = array(
			'authorizer_access_token' => $result2['authorizer_access_token'],
			'item_list' => array(
				array(
					'address' => 'pages/merchant/index',
					'tag' => trim($_POST['tag']),
					'first_class' => $category_arr[3],
					'second_class' => $category_arr[4],
					'third_class' => $category_arr[5] != 'undefined' ? $category_arr[5] : '',
					'first_id' => $category_arr[0],
					'second_id' => $category_arr[1],
					'third_id' => $category_arr[2] != 'undefined' ? $category_arr[2] : '',
					'title'		=> L_('首页')
				),
			),
		);
		$post_param['item_list'] = json_encode($post_param['item_list']);
		$url = $this->wxapp_domain . '/house_wxapp/submit_audit.php';
		$result3 = Http::curlPost($url, $post_param);

		if($result3['errcode'] == 0 || $result3['errcode'] == 85009){
			$data_bind['update_version'] = $_POST['new_version'];
			$data_bind['update_version_id'] = $_POST['new_version_id'];
			$data_bind['update_time'] = time();
			M('Weixin_app_bind')->where(array('bind_id'=>$now_bind['bind_id']))->data($data_bind)->save();
			$this->success(L_('提交审核成功，请耐心等待微信官方审核'));
		}else{
            $this->error(L_('提交出现错误，微信返回内容：').$result3['errmsg'] . ' ' . $result3['real_errmsg']);
		}
	}
	public function get_setting(){
        import('ORG.Net.Http');
        $now_bind = $this->get_bind();
        $result2 = $this->authorizer_access_token($now_bind);
        $post_param = array(
            'authorizer_access_token' => $result2['authorizer_access_token'],
            'privacy_ver'=>2,
        );
        $url = $this->wxapp_domain . '/house_wxapp/getprivacysetting.php';
        $result3 = Http::curlQyWxPost($url, $post_param);
        fdump(['获取配置信息：'.__LINE__,$result3],'get_setting');
        if ($result3['errcode']==0){
            $return['contact_phone'] = $result3['owner_setting']['contact_phone'];
            $return['contact_email'] = $result3['owner_setting']['contact_email'];
            $return['contact_qq'] = $result3['owner_setting']['contact_qq'];
            $return['contact_weixin'] = $result3['owner_setting']['contact_weixin'];
        }
//		$where = array();
//		$where['mer_id']    = $this->property_id;
//		$where['is_remove'] = 0;
//		$where['page_goods_source'] = 1;//必须是快店的自定义页面
//		$diypage_list = M('Merchant_store_diypage')->field('`page_id`,`page_name`')->where($where)->select();
//
//        $return['diypage_list'] = $diypage_list;
        $return['diypage_list'] = array();
		$return['appid'] = $now_bind['appid'];
		$return['appsecret'] = $now_bind['appsecret'];
		$return['wxpay_merid'] = $now_bind['wxpay_merid'];
		$return['wxpay_key'] = $now_bind['wxpay_key'];
		$return['mer_index_page'] = $now_bind['mer_index_page'];
		$return['public_key'] = $now_bind['public_key'];
		$return['private_key'] = $now_bind['private_key'];
		$this->success($return);
	}
	public function set_setting(){
        import('ORG.Net.Http');
        $now_bind = $this->get_bind();
        $result2 = $this->authorizer_access_token($now_bind);
        $this->apply_privacy_interface($result2['authorizer_access_token']);
        $post_param = array(
            'authorizer_access_token' => $result2['authorizer_access_token'],
            'privacy_ver'=>2,
            'owner_setting' => array(
                    'contact_email' => trim($_POST['contact_email']),
                    'contact_phone' => trim($_POST['contact_phone']),
                    'contact_qq' => trim($_POST['contact_qq']),
                    'contact_weixin' => trim($_POST['contact_weixin']),
                'notice_method'=>'弹窗'
            ),
            'setting_list'=>array(
                array(
                    'privacy_key' => 'UserInfo',
                    'privacy_text' => '记录用户信息（微信昵称、头像），注册、登录小程序',
                ),
                array(
                    'privacy_key' => 'Location',
                    'privacy_text' => '计算距离，收集位置信息',
                ),
                array(
                    'privacy_key' => 'Address',
                    'privacy_text' => '发货默认地址，收集用户地址',
                ),
                array(
                    'privacy_key' => 'Record',
                    'privacy_text' => '发布短视频，访问麦克风',
                ),
                array(
                    'privacy_key' => 'Album',
                    'privacy_text' => '发布短视频，选中的照片或视频信息',
                ),array(
                    'privacy_key' => 'Camera',
                    'privacy_text' => '拍摄照片，访问摄像头',
                ),
                array(
                    'privacy_key' => 'PhoneNumber',
                    'privacy_text' => '用户绑定手机号，收集手机号',
                ),array(
                    'privacy_key' => 'Contact',
                    'privacy_text' => '通讯录（仅写入）权限',
                ),
                array(
                    'privacy_key' => 'DeviceInfo',
                    'privacy_text' => '登录记录，收集设备信息',
                ),array(
                    'privacy_key' => 'EXUserFollowAcct',
                    'privacy_text' => '所关注账号',
                ),
                array(
                    'privacy_key' => 'AlbumWriteOnly',
                    'privacy_text' => '生成海报，相册（仅写入）权限',
                ),
                array(
                    'privacy_key' => 'BlueTooth',
                    'privacy_text' => '门禁开门，使用蓝牙',
                ),
                array(
                    'privacy_key' => 'Email',
                    'privacy_text' => '邮箱',
                ),
                array(
                    'privacy_key' => 'MessageFile',
                    'privacy_text' => '选中的文件',
                ),
                array(
                    'privacy_key' => 'Clipboard',
                    'privacy_text' => '读取你的剪切板',
                ),
            )
        );
        $post_param['owner_setting']= json_encode($post_param['owner_setting'],JSON_UNESCAPED_UNICODE);
        $post_param['setting_list']= json_encode($post_param['setting_list'],JSON_UNESCAPED_UNICODE);
        $url = $this->wxapp_domain . '/house_wxapp/setprivacysetting.php';
        $result3 = Http::curlPost($url, $post_param);
        if (isset($result3['errcode']) && intval($result3['errcode']) > 0 && isset($result3['errmsg']) && trim($result3['errmsg']) != 'ok') {
            $this->error($result3['errmsg']);
        }
        fdump_api(['小程序提交'.__LINE__,$result3,$url,$post_param],'setSettingLog',1);
//        $data_bind['mer_index_page'] = trim($_POST['mer_index_page']);
        $data_bind['mer_index_page'] = 0;
		$data_bind['appsecret'] = trim($_POST['appsecret']);
		$data_bind['wxpay_merid'] = trim($_POST['wxpay_merid']);
		$data_bind['wxpay_key'] = trim($_POST['wxpay_key']);
		$data_bind['public_key'] = trim($_POST['wxapp_public_key']);
		$data_bind['private_key'] = trim($_POST['wxapp_private_key']);
		if(M('Weixin_app_bind')->where(array('bind_id'=>$now_bind['bind_id']))->data($data_bind)->save()){
			$this->success(L_('编辑成功'));
		}else{
            $this->success(L_('编辑成功'));
		}
	}

	public $applyContent = [
        'wx.getFuzzyLocation' => "计算距离，获取当前的模糊地理位置",
        'wx.getLocation' => "为了计算小区和用户之间距离，收集地理位置信息，当用户离开小程序后，此接口无法调用。",
        'wx.onLocationChange' => "监听实时地理位置变化事件，提供实时配送信息",
        'wx.chooseAddress' => "调起用户编辑收货地址原生界面，并在编辑完成后返回用户选择的地址",
        'wx.choosePoi' => "选择位置，快速定位配送地址",
        'wx.chooseLocation' => "打开地图选择位置",
        'wx.startLocationUpdate' => "开启小程序进入前台时接收位置消息",
        'wx.startLocationUpdateBackground' => "开启小程序进入前后台时均接收位置消息",
    ];
    protected $applyArr = ['wx.getFuzzyLocation','wx.getLocation','wx.chooseAddress','wx.chooseLocation'];

	public function apply_privacy_interface($authorizer_access_token) {
        $resultPrivacy = $this->get_privacy_interface($authorizer_access_token, true);
        if ($resultPrivacy && isset($resultPrivacy['interface_list'])) {
            $apply_arr = [];
            foreach ($resultPrivacy['interface_list'] as $item) {
                if (isset($item['api_name'])&&$item['api_name']) {
                    $apply_arr[] = [
                        "authorizer_access_token" => $authorizer_access_token,
                        "api_name"=> $item['api_name'],
                        "content"=> isset($this->applyContent[$item['api_name']])?$this->applyContent[$item['api_name']]:$item['api_ch_name'],
                    ];
                }
            }
        } else {
            $apply_arr = [
                [
                    "authorizer_access_token" => $authorizer_access_token,
                    "api_name"=> "wx.chooseAddress",
                    "content"=> "调起用户编辑收货地址原生界面，并在编辑完成后返回用户选择的地址。",
                ],
                [
                    "authorizer_access_token" => $authorizer_access_token,
                    "api_name"=> "wx.getLocation",
                    "content"=> "计算距离，收集位置信息",
                ],
            ];
        }
        $apply_privacy_interface_url = $this->wxapp_domain . '/house_wxapp/apply_privacy_interface.php';
        foreach ($apply_arr as $item) {
            $apply_post_param = $item;
            $resultApply = Http::curlPost($apply_privacy_interface_url, $apply_post_param);
            fdump_api(['接口文档-申请隐私接口-申请接口'.__LINE__,$resultApply,$apply_privacy_interface_url,$apply_post_param],'NewWeixinApp/applyPrivacyInterfaceLog',1);
        }
        return true;
    }

    public function get_privacy_interface($authorizer_access_token,$getApply=false) {
        $get_privacy_interface_url = $this->wxapp_domain . '/house_wxapp/get_privacy_interface.php';
        $privacy_interface_post_param = [
            "authorizer_access_token" => $authorizer_access_token
        ];
        $resultPrivacy = Http::curlPost($get_privacy_interface_url, $privacy_interface_post_param);
        $statusArr = [1=>'待申请开通',2=>'无权限',3=>'申请中',4=>'申请失败',5=>'已开通'];
        $applyStatus = [1,2,4];
        foreach ($resultPrivacy['interface_list'] as $key=>&$item) {
            if (!in_array($item['api_name'],$this->applyArr)) {
                unset($resultPrivacy['interface_list'][$key]);
            }
            if ($getApply && !in_array($item['status'], $applyStatus)) {
                unset($resultPrivacy['interface_list'][$key]);
            }
            $item['statusTxt'] = isset($statusArr[$item['status']])?$statusArr[$item['status']]:'';
        }
        if ($resultPrivacy['interface_list']) {
            $resultPrivacy['interface_list'] = array_values($resultPrivacy['interface_list']);
        }
        fdump_api(['接口文档-申请隐私接口-获取接口列表'.__LINE__,$resultPrivacy,$get_privacy_interface_url,$privacy_interface_post_param],'NewWeixinApp/getPrivacyInterFaceLog',1);
        return $resultPrivacy;
    }

	public function release_version(){
		import('ORG.Net.Http');
		$now_bind = $this->get_bind();
		$result2 = $this->authorizer_access_token($now_bind);
        
		$url = $this->wxapp_domain . '/house_wxapp/release.php';
		$post_param['authorizer_access_token'] = $result2['authorizer_access_token'];
		$result3 = Http::curlPost($url, $post_param);

		if($result3['errcode'] == 0){
			$data_bind['commit_version'] = $result3['now_version'];
			$data_bind['commit_version_id'] = $result3['now_version_id'];
			$data_bind['commit_time'] = time();
			if(M('Weixin_app_bind')->where(array('bind_id'=>$now_bind['bind_id']))->data($data_bind)->save()){
				$this->success(L_('提交成功'),U('index'));
			}else{
				$this->error(L_('提交保存失败，请重试'));
			}
		}else{
			$this->error(L_('提交失败，错误原因：').$result3['errmsg']);
		}
	}
	public function modify_domain(){
        $now_bind = $this->get_bind();
        // 优先设置下域名 然后提交
        if (!isset($now_bind['modify_domain']) || !$now_bind['modify_domain']) {
            import('ORG.Net.Http');
            $result2 = $this->authorizer_access_token($now_bind);
            $url = $this->wxapp_domain . '/house_wxapp/modify_domain.php';
            $post_param['domain'] = $_SERVER['HTTP_HOST'];
            $post_param['authorizer_access_token'] = $result2['authorizer_access_token'];
            $modify_domain_result = Http::curlPost($url, $post_param);
            if ($modify_domain_result['errcode'] == 0) {
                $data_bind = [];
                $data_bind['modify_domain'] = $post_param['domain'];
                $data_bind['modify_domain_time'] = time();
                M('Weixin_app_bind')->where(array('bind_id'=>$now_bind['bind_id']))->data($data_bind)->save();
            }
            $modify_domain_result['curl_request_url']=$url;
            echo json_encode($modify_domain_result,JSON_UNESCAPED_UNICODE);
        }
	}
	public function delete_wxapp(){
		$now_bind = $this->get_bind();
		if(empty($now_bind)){
			$this->error(L_('应用不存在，请重试'));
		}
		M('Weixin_app_bind')->where(array('bind_id'=>$now_bind['bind_id']))->data(array('status'=>4))->save();
		M('Weixin_app_tester')->where(array('bind_id'=>$now_bind['bind_id']))->delete();
        exit('<html><head><script>window.top.msg(1,"解绑成功",true,3);window.top.GenerateRoutes();</script></head></html>');
	}

    public function ajax_upload_file(){
        $allowExts =array (
            0 => 'pem',
        );
        if($_FILES['imgFile']['error'] != 4){
            $img_admin_id = sprintf("%09d", $this->property_id);
            $rand_num = substr($img_admin_id,0,3) . '/' . substr($img_admin_id,3,3) . '/' . substr($img_admin_id,6,3);
            $upload_dir = "./upload/files/{$rand_num}/";
            if(!is_dir($upload_dir)){
                mkdir($upload_dir,0777,true);
            }
            import('ORG.Net.UploadFile');
            $upload = new UploadFile();
            $upload->maxSize = 10*1024*1024;
            $upload->allowExts = $allowExts;
            $upload->savePath = $upload_dir;
            $upload->saveRule = 'uniqid';
            if($upload->upload()){
                $uploadList = $upload->getUploadFileInfo();
                $title = $rand_num.','.$uploadList[0]['savename'];
                exit(json_encode(array('error' => 0,'url' =>'./upload/files/'.$rand_num.'/'.$uploadList[0]['savename'],'title'=>$title)));
            }else{
                exit(json_encode(array('error' => 1,'message' =>$upload->getErrorMsg())));
            }
        }else{
            exit(json_encode(array('error' => 1,'message' =>L_('没有选择文件'))));
        }
    }

    /**
     * 获取审核状态
     * User: zhanghan
     * Date: 2022/2/23
     * Time: 14:57
     */
    public function look_update_status(){
        $audit_result = $this->get_audit_result();
        $this->success($audit_result);
    }

    /**
     * 获取最新一次提交的审核状态
     * User: zhanghan
     * Date: 2022/2/23
     * Time: 15:19
     * @return array|mixed
     */
    public function get_audit_result(){
        $now_bind = $this->get_bind();
        import('ORG.Net.Http');
        $result2 = $this->authorizer_access_token($now_bind);
        $url = $this->wxapp_domain . '/house_wxapp/get_latest_auditstatus.php';
        $post_param['authorizer_access_token'] = $result2['authorizer_access_token'];
        $audit_result = Http::curlPost($url, $post_param);
        fdump_api(['获取最新一次提交的审核状态'.__LINE__,$url,$post_param,$audit_result],'NewWeixinApp/get_audit_resultLog',1);

        if($audit_result['status'] == 0){
            $audit_result['status_txt'] = '审核成功';
        }else if($audit_result['status'] == 1){
            $audit_result['status_txt'] = '审核失败';
        }else if($audit_result['status'] == 2){
            $audit_result['status_txt'] = '审核中';
        }else if($audit_result['status'] == 3){
            $audit_result['status_txt'] = '审核已撤回';
        }
        if (isset($audit_result['submit_audit_time'])&&$audit_result['submit_audit_time']) {
            $timeTxt = date('Y-m-d H:i:s', $audit_result['submit_audit_time']);
        }
        if (!$timeTxt) {
            $timeTxt = '无';
        }
        $audit_result['timeTxt'] =  $timeTxt;
        return $audit_result;
    }


    public function auth_back1(){
        $this->display('index');
    }
}
