<?php
/*
 * 店员中心 APP版
 */
class StorestaffAction extends BaseAction{
    protected $staff_session;
    protected $store;
    protected $now_merchant;
	protected $order_froms = array();
	protected $merchant_status = array();
    public function __construct(){
		$this->order_froms = array(L_('手机版'), L_('商城'), L_('安卓应用'), L_('苹果应用'), L_('小程序'), L_('电脑版X1',['X1'=>$this->config['shop_alias_name']]), L_('线下零售'), L_('饿了么'), L_('美团'),L_('扫码购'));
		$this->merchant_status = array(0=>L_('关闭'), 1=>L_('正常'), 2=>L_('待审核'),3=>L_('欠费'),4=>L_('删除'));
		
        parent::__construct();
        
        if (strpos(var_export($_POST, true),'shopNotice')) {//调用shop_notice_v20方法跳过 __construct
            $shopNotice = true;
        }
        if (empty($shopNotice)) {
        if (ACTION_NAME != 'config' && ACTION_NAME != 'login' && ACTION_NAME != 'reg' && ACTION_NAME != 'get_device_token' && ACTION_NAME != 'many_device_token' && ACTION_NAME != 'logout' && ACTION_NAME != 'get_privacy_policy') {
        	if ($this->_uid) {
	            $this->staff_session = M('Merchant_store_staff')->field(true)->where(array('id'=>$this->_uid))->find();
			}else{
				fdump_sql([$_POST, $_SERVER], 'store_staff_auto_loginout');
				$invoke_path = strtolower(MODULE_NAME.'/'.ACTION_NAME.'/'.GROUP_NAME);
                if (empty($this->DEVICE_ID) && $invoke_path != 'service/invoke/index') {
                    //设备ID都没有就不要去查了
                    $this->returnCode('20044013');
                }
                $this->staff_session = M('Merchant_store_staff')->field(true)->where(array('device_id'=>$this->DEVICE_ID))->find();
                if(empty($this->staff_session)){
                    $this->returnCode('20044013');
                }else{
					$this->_uid = $this->staff_session['id'];
				}
			}
			if(empty($this->staff_session)){
				$this->returnCodeError(L_('店员账号不存在').'，'.L_('错误信息').'：'.$this->_uid);
			}
			
            $condition_merchant_store['store_id'] = $this->staff_session['store_id'];
            $this->store = M('Merchant_store')->field(true)->where($condition_merchant_store)->find();
            if (empty($this->store) && ACTION_NAME != 'logout') {
				$this->returnCode('20130004');
            }
            //设置时区
            if(!empty($this->store['city_id']) || !empty($this->store['area_id'])){
                $area_db = M("Area");
                if(!empty($this->store['city_id'])){
                    $city_info = $area_db->where('area_id='.$this->store['city_id'])->field('timezone')->find();
                }
                if(!empty($this->store['area_id'])){
                    $area_info = $area_db->where('area_id='.$this->store['area_id'])->field('timezone')->find();
                }
                //area的时区优先于城市的时区
                if(!empty($area_info['timezone']) || !empty($city_info['timezone'])){
                    date_default_timezone_set($area_info['timezone'] ? $area_info['timezone'] : $city_info['timezone']);
					D('')->query("SET time_zone='+" . get_timeout_num_by_name() . "'");
                }
            }
        }
		
		$this->now_merchant = D('Merchant')->get_info($this->store['mer_id']);
		if($this->now_merchant['score_get_percent']>=0){
			$this->config['score_get'] = $this->now_merchant['score_get_percent']/100;
		}
		
        $this->order_froms = array(L_('手机版') . $this->config['shop_alias_name'], L_('商城'), L_('安卓应用'), L_('苹果应用'), L_('小程序'), L_('电脑版') . $this->config['shop_alias_name'], L_('线下零售'), L_('饿了么'), L_('美团'),L_('扫码购'));
        }
    }

    public function check_merchant(){
        if($this->now_merchant['status']!=1){
            if($this->now_merchant['merchant_end_time']>0 && $this->now_merchant['merchant_end_time']<time()){
                $this->returnCode(1,'',L_('商家已过期，暂不可使用该功能！'));
            }
            $this->returnCode(1,'',L_('商家已').$this->merchant_status[$this->now_merchant['status']].L_('，暂不可使用该功能！'));
        }else{
            // 开启店员权限功能 店小二
            if (C('config.open_staff_power')&&$this->staff_session['type'] == 0) {
                if ($_POST['type'] == 'shop') {
                    $this->returnCode(1,'',L_('您当前身份为【店小二】，暂无线下零售权限，请联系管理员！'));
                } elseif ($_POST['type'] == 'foodshop') {
                    $this->returnCode(1,'',L_('您当前身份为【店小二】，暂无结账权限，请联系管理员！'));
                }elseif ($_POST['type'] == 'store') {
                    $this->returnCode(1,'',L_('您当前身份为【店小二】，暂无收银权限，请联系管理员！'));
                }else{
                    $this->returnCode(0,array());
                }      
            }else{
                $this->returnCode(0,array());
            }
        }
    }

	//确认支付
    public function confirm_pay(){
		$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
		$result = M('Shop_order')->where(array('order_id'=>$order_id))->save(array('paid'=>1,'pay_time'=>time()));
		if($result){
			D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 1, 'name' => '', 'phone' => ''));
			$this->returnCode(0, L_("修改成功"));
		}else{
			$this->returnCode(1, null, L_('修改失败！'));
		}
	}
    public function get_device_token(){
        $where['device_id'] =  isset($_POST['device_id'])?$_POST['device_id']:$this->DEVICE_ID;
        $device_token =  $_POST['device_token'];
        M('Merchant_store_staff')->where($where)->setField('device_token',$device_token);
		$this->returnCode(0);
    }
	
	public function many_device_token(){
        $where['device_id'] =  isset($_POST['device_id']) ? $_POST['device_id'] : $this->DEVICE_ID;
        $data['device_token'] =  $_POST['upushToken'] ? $_POST['upushToken'] : '';
        $data['jpush_device_token'] =  $_POST['jpushToken'] ? $_POST['jpushToken'] : '';
		$data['phone_brand'] =  $_POST['phone_brand'] ? $_POST['phone_brand'] : '';
        M('Merchant_store_staff')->where($where)->data($data)->save();
		
		//检测极光设备号有没有成功绑定 tag
		if($data['jpush_device_token']){
			$jpushStaff = new Jpush(C('config.staff_jpush_appkey'), C('config.staff_jpush_secret'));
			$result = $jpushStaff->request('https://device.jpush.cn/v3/devices/' . $data['jpush_device_token'], [], 'GET');
			if(isset($result['tags']) && !in_array($where['device_id'], $result['tags'])){
				fdump([$_POST, $result], 'jpush_service_add_tags');
				$params = [
					'alias'  => $result['alias'],
					'mobile' => $result['mobile'],
					'tags'   => [
						'add'	=> [$where['device_id']]
					],
				];
				$jpushStaff->request('https://device.jpush.cn/v3/devices/' . $data['jpush_device_token'], $params, 'POST');
			}
		}
		
		$this->returnCode(0);
    }
	
	public function config(){
		$config['can_register'] = true;
		$config['is_packapp'] = true;
        
		$appConfig  =   D('Appapi_app_config')->get();

        if($_POST['phone_type'] == 2){
            $config['staff_android_v'] = strval($appConfig['pc_staff_android_v']);
			$config['staff_android_vcode'] = strval($appConfig['pc_staff_android_vcode']);
			$config['staff_android_url'] = strval($appConfig['pc_staff_android_url']);
            $config['staff_android_vdesc'] = strval($appConfig['pc_staff_android_vdesc']);
            $config['storestaff_android_package_name'] = $appConfig['pc_staff_android_package_name'] ? $appConfig['pc_staff_android_package_name'] : '';
		}else{
            $config['staff_android_v'] = strval($appConfig['staff_android_v']);
			$config['staff_android_vcode'] = strval($appConfig['staff_android_vcode']);
			$config['staff_android_url'] = strval($appConfig['staff_android_url']);
            $config['staff_android_vdesc'] = strval($appConfig['staff_android_vdesc']);
            $config['storestaff_android_package_name'] = $appConfig['storestaff_android_package_name'] ? $appConfig['storestaff_android_package_name'] : '';
		}
     
        $config['mer_android_package_name'] = $appConfig['mer_android_package_name'] ? $appConfig['mer_android_package_name'] : '';
        $config['mer_ios_package_name'] = $appConfig['mer_ios_package_name'] ? $appConfig['mer_ios_package_name'] : '';
        $config['storestaff_ios_package_name'] = $appConfig['storestaff_ios_package_name'] ? $appConfig['storestaff_ios_package_name'] : '';
		
		$config['storestaff_ios_must_upgrade'] = $appConfig['storestaff_ios_must_upgrade'] ? true : false;
		$config['storestaff_android_must_upgrade'] = $appConfig['storestaff_android_must_upgrade'] ? true : false;

        $config['mer_ios_download_url'] = $appConfig['mer_ios_download_url'] ? $appConfig['mer_ios_download_url'] : '';
        $config['mer_android_download_url'] = $appConfig['mer_android_url'] ? $appConfig['mer_android_url'] : '';
		
		
		$config['map_config'] = $this->config['map_config'] ? $this->config['map_config'] : 'baidu';
		$config['baidu_map_ak'] = $this->config['baidu_map_ak'] ? $this->config['baidu_map_ak'] : '';
		if($config['map_config'] == 'google'){
			$config['google_map_ak'] = $this->config['google_map_ak'] ? $this->config['google_map_ak'] : '';
		}
		
		$config['Currency_symbol'] = $this->config['Currency_symbol'] ? $this->config['Currency_symbol'] : '￥';
        $config['score_name'] = $this->config['score_name'] ? $this->config['score_name'] : L_('积分');
		
		$config['open_multilingual'] = $this->config['open_multilingual'] ? $this->config['open_multilingual'] : '0';
		$config['lang_config'] = D('Lang')->lang_list();//语言配置
		
		//vue 新版后台返回
		$config['site_logo']	 = 	strval($this->config['site_logo']);			//网站LOGO
		$config['site_name'] 	 = 	strval($this->config['site_name']);			//网站名称
		$config['site_phone'] 	 = 	strval($this->config['site_phone']);		//网站电话
		$config['site_qq'] 		 = 	strval($this->config['site_qq']);				//网站QQ
		$config['site_email'] 		 = 	strval($this->config['site_email']);				//网站site_email
		$config['wechat_qrcode'] =  strval($this->config['wechat_qrcode']);	//网站公众号
		
		$config['h5_qrcode_url'] =  $this->config['site_url'].'/index.php?c=Recognition&a=get_own_qrcode&qrCon='.urlencode($this->config['site_url'].'/packapp/storestaff/index.html');	//H5页面
		$config['app_qrcode_url'] =  $config['staff_android_url'] ? $this->config['site_url'].'/index.php?c=Recognition&a=get_own_qrcode&qrCon='.urlencode($this->config['site_url'].'/topic/app_wap_clerk.html') : '';	//APP页面
		$config['footer_copyright'] = 'Copyright &copy; '.date('Y').' '.$this->config['site_name'].$this->config['top_domain'].' '.L_('版权所有').' '.($this->config['site_icp'] ? '<a href="http://www.miibeian.gov.cn/" target="_blank">'.$this->config['site_icp'].'</a>' : '');

        $config['mall_deliver_to_expess'] = (bool)$this->config['mall_deliver_to_expess'];
        $config['open_company_advance_deposit'] = intval($this->config['open_company_advance_deposit']);
        $config['have_deposita']=empty(C('customization.deposita'))?0:C('customization.deposita');
        
        $config['internal_membership_card'] = intval(C('customization.internal_membership_card')); //一卡通定制
        $config['staff_uni_appid'] = C('config.staff_uni_appid') ? C('config.staff_uni_appid') : '__UNI__8799035';
        
		$this->returnCode('0',$config);
	}
	public function reg(){
		$this->returnCode('0');
	}

    // 隐私政策
    public function get_privacy_policy(){
        $app_config = D('Appapi_app_config')->get();
        $arr['privacy_policy'] = htmlspecialchars_decode(htmlspecialchars_decode($app_config['staff_privacy_policy']),ENT_QUOTES);
        $arr['privacy_policy_md5'] = md5($app_config['staff_privacy_policy'].$this->config['register_agreement']);
        // 注册协议
        $arr['register_agreement'] = htmlspecialchars_decode(htmlspecialchars_decode($this->config['register_agreement']),ENT_QUOTES);
        $this->returnCode('0',$arr);
    }

    # 蓝牙打印机

    public function blueteeth_hardware(){
        $where['store_id'] = $this->staff_session['store_id'];
        $where['print_type'] = 3;
        $_POST['device_type']&&$where['device_type'] =  $_POST['device_type'];
		if($_POST['app_type'] == 'ios'){
			$where['print_mobile_code'] = array('neq','');
		}
        $res = M('Orderprinter')->where($where)->select();
        if(empty($res)){
            $this->returnCode('0',array('print_list'=>array()));
        }else{
			$where['print_mobile_code'] = '';
		}
        $this->returnCode('0',array('print_list'=>$res));
    }

        // 登录
    public function login()
    {
        $ios = I('ios');
        $ticket = I('ticket', false);
        $client = I('client', 2);
        $registrationId = I('registrationId', '', 'trim');
		
		if($_POST['autologin_ios_device']){
			$ios = 1;
			$_POST['old_device'] = $_POST['autologin_ios_device'];
		}

        // 生成v20ticket
        import("@.ORG.Token");
        $token = new Token();
		
        if ($ios == 1) {
            $old_device = I('old_device');
            if (empty($old_device)) {
                $this->returnCode('20130029');
            }
            $ios_info = ticket::get($ticket, $old_device, true);
            if ($ios_info) {
                $tickets = ticket::create($ios_info['uid'], $this->DEVICE_ID, true);
                $ticket = $tickets['ticket'];
                $v20_ticket = Token::createToken($ios_info['uid']);
            } else {
                $this->returnCode('20130030');
            }
//             $save = true;
        }
        
        $database_store_staff = D('Merchant_store_staff');
        if ($ticket) {
            $info = ticket::get($ticket, $this->DEVICE_ID, true);
            if ($info) {
                $now_staff = $database_store_staff->field(true)->where(array('id' => $info['uid']))->find();
				if (empty($now_staff)) {
					$this->returnCode('20130001');
				}
				$condition_merchant_store['store_id'] = $now_staff['store_id'];
				$now_store = M('Merchant_store')->field(true)->where($condition_merchant_store)->find();
				if(empty($now_store) || $now_store['status'] != 1){
					$this->returnCodeError(L_('店铺状态不正常，无法登录'));
				}
                unset($now_staff['password']);
				$now_staff['store_name'] = $now_store['name'];
                $now_staff['openid'] = $now_staff['openid']?:null;

                $v20_ticket = Token::createToken($info['uid']);

                $return = array(
                    'ticket' => $ticket,
                    'v20_ticket' => $v20_ticket,
                    'user' => $now_staff
                );
            }
            $where['id'] = $now_staff['id'];
            $data_store_staff['last_time'] = $_SERVER['REQUEST_TIME'];
        } else {
            if (empty($_POST['account'])) {
                $this->returnCode('20130001');
            }
            $condition_store_staff['username'] = trim($_POST['account']);
            $now_staff = $database_store_staff->field(true)->where($condition_store_staff)->find();
            if (empty($now_staff)) {
                $this->returnCode('20130001');
            }
            $pwd = md5(trim($_POST['passwd']));
            if ($pwd != $now_staff['password']) {
                $this->returnCode('20130002');
            }
            unset($now_staff['password']);
            $ticket = ticket::create($now_staff['id'], $this->DEVICE_ID, true);
			$condition_merchant_store['store_id'] = $now_staff['store_id'];
			$now_store = M('Merchant_store')->field(true)->where($condition_merchant_store)->find();
			if(empty($now_store) || $now_store['status'] != 1){
				$this->returnCodeError(L_('店铺状态不正常，无法登录'));
			}
			unset($now_staff['password']);
			$now_staff['store_name'] = $now_store['name'];
            $now_staff['openid'] = $now_staff['openid']?:null;
            $v20_ticket = Token::createToken($now_staff['id']);
            $return = array(
                'ticket' => $ticket['ticket'],
                'v20_ticket' => $v20_ticket,
                'user' => $now_staff
            );
			
			if($this->DEVICE_ID != 'packapp' && $this->DEVICE_ID != '020000000000'){
                $database_store_staff->where(array('device_id'=>$this->DEVICE_ID,'client'=>array('neq','0')))->data(array('device_id'=>'','device_token'=>'','last_time'=>'0'))->save();
            }
			
            $where['id'] = $now_staff['id'];
            $data_store_staff['last_time'] = $_SERVER['REQUEST_TIME'];
        }
		
		//保存店员登录信息
		$data_store_staff['device_id'] = $this->DEVICE_ID;
        if(isset($_POST['client'])){
            $data_store_staff['client'] = $_POST['client'];
        }
        $data_store_staff['app_version'] = intval($this->app_version);
        $data_store_staff['app_version_name'] = strval($_POST['app_version_name']);
        $data_store_staff['phone_brand'] = strval($_POST['phone_brand']);
        $data_store_staff['web_session'] = hash('sha256',generate_password(32).'_'.$now_staff['id'],false);
        $return['user']['web_session'] = $data_store_staff['web_session'];
        $data_store_staff['jpush_registrationId'] = $registrationId;
        
        $save = $database_store_staff->where($where)->data($data_store_staff)->save();

        if ($save !== FALSE) {
            $condition_merchant_store['store_id'] = $return['user']['store_id'];
            $return['user']['mer_id'] = M('Merchant_store')->field(true)->where($condition_merchant_store)->getField('mer_id');
            $return['device_id']  	  =	$this->DEVICE_ID;
			
			/* 登录历史记录 */
			$data_store_staff_login_log = array(
				'uid' 		=> $now_staff['id'],
				'client' 	=> $client,
				'device_id' => $this->DEVICE_ID,
				'app_version' => intval($this->app_version),
				'app_version_name' => strval($_POST['app_version_name']),
				'add_time' 	=> $_SERVER['REQUEST_TIME'],
				'add_ip' 	=> get_client_ip(0),
				'type' 		=> '0',
            );
            if(!isset($_POST['client'])){
                $data_store_staff_login_log['client'] = $return['user']['client'];
            }
			M('Store_staff_login_log')->data($data_store_staff_login_log)->add();
			
			$this->checkNotice();
			
            $return['open_multilingual']      = $this->config['open_multilingual'];
			$this->returnCode(0, $return);
        } else {
            $this->returnCode('20130003');
        }
    }

    public function get_is_open_taika()
    {
        $store_id = I('store_id');
        $condition_merchant_store['store_id'] = $store_id;
        $return = M('Merchant_store')->field(true)->where($condition_merchant_store)->find();
        $this->returnCode(0,$return);
    }

    /**
     * 注销账号
     */
    public function cancelAccount()
    {
        if (empty($this->_uid)) {
            $this->returnCode('10044010');
        }
        $staffUser = M('Merchant_store_staff')
            ->where(['id' => $this->_uid])
            ->field('name, username, password, tel')
            ->find();

        $mark = '[已注销]'.time();

        M('Merchant_store_staff')
            ->where(['id' => $this->_uid])
            ->data([
                'name'     => $staffUser['name'] . $mark,
                'username' => $staffUser['username'] . $mark,
                'password' => $staffUser['password'] . $mark,
                'tel'      => $staffUser['tel'] . date('is',time()),
            ])
            ->save();
        
        return $this->logout();
    }
    
	public function logout(){
        if($this->_uid){
            M('Merchant_store_staff')->where(['id'=>$this->_uid])->data(['device_id'=>'','device_token'=>'','last_time'=>'0','last_print_time'=>'0'])->save();
			
			/* 登出历史记录 */
			$data_store_staff_login_log = array(
				'uid' 		=> $this->_uid,
				'client' 	=> '0',
				'device_id' => $this->DEVICE_ID,
				'app_version' => intval($this->app_version),
				'app_version_name' => strval($_POST['app_version_name']),
				'add_time' 	=> $_SERVER['REQUEST_TIME'],
				'add_ip' 	=> get_client_ip(0),
				'type' 		=> '1',
			);
			M('Store_staff_login_log')->data($data_store_staff_login_log)->add();
			
		}
		if($this->DEVICE_ID != 'packapp' && $this->DEVICE_ID != '020000000000'){
			M('Merchant_store_staff')->where(['device_id'=>$this->DEVICE_ID,'client'=>['neq','0']])->data(['device_id'=>'','device_token'=>'','last_time'=>'0','last_print_time'=>'0'])->save();
		}
		
		$this->checkNotice();
		
		$this->returnCode(0);
	}
	
    // 首页
    public function index()
    {
        $isSingleShopSystem = C('config.single_system_type') == 'shop';
        $arr['have_group'] = $isSingleShopSystem ? 0 : $this->store['have_group']; // 团购
        //判断商家是否开启了景区功能
        $now_merchant = D('Merchant')->get_info($this->store['mer_id']);
        if($now_merchant['is_open_scenic']==1){
            $arr['have_scenic'] = $isSingleShopSystem ? 0 : intval($this->staff_session['is_verify_ticket']);  //景区门票
        }else{
            $arr['have_scenic']=0;
        }
        
        $arr['have_meal'] = $this->store['have_meal']; // 餐饮
         // 查看有没有餐饮菜单
        $mealMune = M('New_merchant_menu')->where(['name'=>'订餐管理','module'=>'Foodshop'])->find();
        if(empty($mealMune)){
            $arr['have_meal'] = false;
        }
        $arr['have_meal2'] = $this->store['have_meal']; // 餐饮2.0
        $arr['open_sub_card'] =isset($this->config['open_sub_card']) ? 1 : 0; // 餐饮
        if (! M('Merchant_store_foodshop')->field('store_id')->where(array('store_id' => $this->store['store_id']))->find()) {
            $arr['have_meal'] = 0;
            $arr['have_meal2'] = 0;
        }
		
		//应用中心不存在老版餐饮，则隐藏老餐饮的入口
		if(!M('Plugin')->where(['plugin_id' => 5])->find()){
			$arr['have_meal'] = 0;
		}
		if(!M('Plugin')->where(['plugin_name' => '餐饮管理','plugin_label'=>'foodshop','status'=>1,'show'=>1])->find()){
            $arr['have_meal'] = 0;
        }
        $arr['have_shop'] = $this->store['have_shop']; // 快店
		$arr['have_mall'] = $isSingleShopSystem ? 0 : $this->store['have_mall']; // 商城
        $arr['have_mall2'] = $isSingleShopSystem ? 0 : $this->store['have_mall']; // 新版商城
        $shop_info = M('Merchant_store_shop')->field('store_id,store_theme')->where(array('store_id' => $this->store['store_id']))->find();
        if (!$shop_info) {
            $arr['have_shop'] = 0;
            $arr['have_mall'] = 0;
        }else{
            $arr['have_mall'] = M('Plugin')->where(['plugin_name' => '商城管理','plugin_label'=>'mall','status'=>1,'show'=>1])->find() ? $arr['have_mall'] : 0;
        }
        
		// 预约权限判断
        $arr['have_appoint'] = $isSingleShopSystem ? 0 : (isset($this->config['appoint_page_row']) ? 1 : 0);
		if($arr['have_appoint']){
			$now_merchant = D('Merchant')->get_info($this->store['mer_id']);
			$merchant_menus = $now_merchant['menus'];
			$merchant_menus_arr = explode(',',$merchant_menus);
			if ($merchant_menus_arr && $merchant_menus_arr[0]  && !in_array(60, $merchant_menus_arr)) {
				$arr['have_appoint'] = 0;
			}
		}
		
        $arr['have_store'] = isset($this->config['pay_in_store']) ? 1 : 0; // 到店
        $arr['have_group_name'] = isset($this->config['group_alias_name']) ? $this->config['group_alias_name'] : ''; // 团购
        $arr['have_scenic_name']=isset($this->config['scenic_alias_name']) ? $this->config['scenic_alias_name'] : ''; //景区
        $arr['have_meal_name'] = isset($this->config['meal_alias_name']) ? $this->config['meal_alias_name'] : ''; // 餐饮
        $arr['have_meal2_name'] = isset($this->config['meal_alias_name']) ? L_('新版').$this->config['meal_alias_name'] : ''; // 餐饮
		if($arr['have_meal'] == 0){
			$arr['have_meal2_name'] = $arr['have_meal_name'];
		}
		
        $arr['have_shop_name'] = isset($this->config['shop_alias_name']) ? $this->config['shop_alias_name'] : ''; // 快店
        $arr['have_mall_name'] = isset($this->config['mall_alias_name']) ? $this->config['mall_alias_name'] : ''; // 商城
        $arr['have_mall2_name'] = isset($this->config['mall_alias_name_new']) ?$this->config['mall_alias_name_new'] : ''; // 新版商城


        $arr['have_appoint_name'] = isset($this->config['appoint_alias_name']) ? $this->config['appoint_alias_name'] : ''; // 预约
        $arr['pay_in_store'] = $this->config['pay_in_store'];
        $arr['open_score_fenrun'] = isset($this->config['open_score_fenrun'])?$this->config['open_score_fenrun']:0;

        $arr['have_store_name'] = $this->config['cash_alias_name'];
        $arr['have_cash_name'] = L_('店员收银');
        $offline_pay_list = M('Store_pay')->where(array('store_id'=>$this->store['store_id']))->order('`id` ASC')->select();
		if(empty($offline_pay_list)){
			$offline_pay_list = array();
		}
        $arr['offlinepay'] = $offline_pay_list;
        $arr['mer_id'] =  $this->store['mer_id'];

        $config = M('Appapi_app_config')->select();
        foreach ($config as $v) {
            if ($v['var'] == 'staff_android_v') {
                $arr['android_version'] = $v['value'];
            } elseif ($v['var'] == 'staff_android_url') {
                $arr['android_downurl'] = $v['value'];
            } elseif ($v['var'] == 'staff_android_vcode') {
                $arr['android_version_code'] = $v['value'];
            } elseif ($v['var'] == 'staff_android_vdesc') {
                $arr['android_version_version_desc'] = $v['value'];
            }
        }
        $arr['time'] = time(); // 预约
        $arr['open_multilingual'] = $this->config['open_multilingual'];

        // 验证当前密码是否为123456，如果是则提示修改密码
        if ($this->config['pwd_verify']&&$this->staff_session['password']=='e10adc3949ba59abbe56e057f20f883e') {
            $arr['check_pwd'] = 1;
        }
        $arr['staff_info'] = $this->staff_session;
        $arr['have_deposita']=empty(C('customization.deposita'))?0:C('customization.deposita');

        //新版景区、员工卡、体育、商家活动
        $arr['show_new_scenic'] = C('config.life_tools_scenic_alias_name') ? 1 : 0;
        $arr['show_worker_card'] = 0;
        $arr['show_sport'] = 0;
        $arr['show_merchant_activity'] = 0;
        if (C('customization.life_tools')) {
            $arr['show_worker_card'] = 1;
            $arr['show_sport'] = 1;
            $arr['show_merchant_activity'] = 1;
        }
        $arr['has_point_exchange'] = C('customization.has_point_exchange') ?: 0;//是否展示积分兑换
        $arr['point_exchange_name'] = '积分兑换';
        
        $this->returnCode(0, $arr);
    }

    //修改密码
    public function changePwd(){
        if (isset($_POST['old_password'])) {
            if (md5($_POST['old_password'])!=$this->staff_session['password']) {
                $this->returnCode('20130056');
            }
        }

        if(md5($_POST['password']) == $this->staff_session['password']){
            $this->returnCode('10045010');
        }
        
        $condition_staff['id'] = $this->staff_session['id'];
        //校验密码
        $check_password = verifyPassword($_POST['password']);
        if($check_password['code']!=0){
            $this->returnCode(1001,array(),L_($check_password['msg']));
        }
        $data_staff['password'] = md5($_POST['password']);
        if(M('Merchant_store_staff')->where($condition_staff)->data($data_staff)->save()){
            $this->returnCode(0);
        }else{
            $this->returnCode('20140069');
        }
    }

    /**
     * 通过手机号获取会员信息
     * scan_from_merchang_card  到店付、线下零售识别用户身份是否开启商家会员卡识别方式
     */
    public function get_user_by_phone()
    {
        $phone = I('phone') ? trim(I('phone')) : '';
        $type = I('type') ? trim(I('type')) : 'foodshop';

        if ($this->config['scan_from_merchang_card']) {
            if (trim(I('auth_code'))) {
                // 扫商家会员卡二维码
                $key = trim(I('auth_code'));
                $condition_user['mer_id'] = $this->store['mer_id'];
                $condition_user['status'] = array('eq',1);
                $condition_user['_string'] = "id={$key}";
                $card = D('Card_userlist')->field(true)->where($condition_user)->find();
                if($card){
                    $user = D('User')->field(true)->where(array('uid' => $card['uid']))->find();
                }
            }else{
                if(strlen($phone) == 11) {
                    // 手机号搜索
                    $user = D('User')->field(true)->where(array('phone' => $phone))->find();
                }else{
                    // 输入实体卡号
                    $condition_user['mer_id'] = $this->store['mer_id'];
                    $condition_user['status'] = array('eq',1);
                    $condition_user['_string'] = "physical_id = {$phone}";
                    $card = D('Card_userlist')->field(true)->where($condition_user)->find();
                    if($card){
                        $user = D('User')->field(true)->where(array('uid' => $card['uid']))->find();
                    }
                }
            }
        }else{
            if (trim(I('auth_code'))) {
                $phone_info = D('User_payid')->get($_POST['auth_code']);
                if ($phone_info['error']) {
                    $this->returnCode(0,array());
                }else{
                    $phone = $phone_info['phone'];
                }
            }

            if (!$phone) {
                $this->returnCode(0,array());
            }

            //开启客户导入生成真实用户
            if(C('config.open_user_import_create_user') == 1){
                // 使用实体卡号搜索
                $user = D('User')->field(true)->where('phone ="'.$phone.'" OR cardid="'.$phone.'"')->find();
            }else{
                $user = D('User')->field(true)->where('phone ="'.$phone.'"')->find();
            }
        }

        if( $user ) {
            $return  = $this->get_card($user['uid'],$type);
            $this->returnCode(0, $return);
        }else{
            $this->returnCode(0, array());
        }
    }

    /**
     * 通过uid获取优惠信息
     */
    public function get_card($uid,$type)
    {
        $return = array();
        if( $user = D('User')->field(true)->where(array('uid' => $uid))->find()) {
            $return = array(
                'name' => $user['truename'] ? $user['truename'] : $user['nickname'], 
                'sex' => $user['sex'] == 1 ? L_('男') : L_('女'), 
                'phone' => $user['phone'],
                'uid' => $user['uid'],
            );

            $card = D('Card_userlist')->field(true)->where(array('uid' => $user['uid'], 'mer_id' => $this->store['mer_id'],'status'=>1))->find();
            $card_source = M(_view('Card_new'))->where(array('mer_id' => $this->store['mer_id'], 'status' => 1))->find();
            if ($card&& $card_source) {
                $return['card_id'] = $card['id'];
                $return['card_money'] = $card['card_money'] + $card['card_money_give'];
                $return['card_score'] = $card['card_score'];
                $return['physical_id'] = $card['physical_id'];
                $return['discount'] = $card_source['discount'];
            }
            

            // 会员等级优惠
            $level_off=false;
            if( !empty($user) && isset($user['level']) ){
                $leveloff=!empty($this->store['leveloff']) ? unserialize($this->store['leveloff']) :'';
                /****type:0无优惠 1百分比 2立减*******/
                if(!empty($leveloff) && isset($leveloff[$user['level']]) ){
                    $level_off=$leveloff[$user['level']];
                    if($level_off['type']==1){
                        $level_off['offstr']=L_('单价按原价X1%来结算',$level_off['vv']);
                    }elseif($level_off['type']==2){
                        $level_off['offstr']=L_('单价立减X1',C('config.Currency_symbol') . $level_off['vv']);
                    }

                }
            }
            $return['leveloff'] = $level_off;
            
            //商家优惠券
            // $mer_coupon = D('Card_new')->get_use_coupon_by_params($user['uid'], $this->store['mer_id'], $type);
            // if ($mer_coupon) {
            //     foreach ($mer_coupon as &$value) {
            //         $value['platform'] = unserialize($value['platform']);
            //         $value['end_time'] = date('Y-m-d H:i:s',$value['end_time']);
            //     }
            // }   
            // $return['card_new'] = $mer_coupon ? $mer_coupon : array();

            //平台优惠券
            // $now_order['total_money'] =  100;
            // $system_coupon = D('System_coupon')->get_noworder_coupon_list($now_order, $type, $user['phone'], $user['uid'], '','','',false);
            // $return['system_coupon'] = $system_coupon ? $system_coupon : array();
            
        }
        return $return;
    }

    // 获得积分使用信息
    public function get_score_discount(){
        $order_id  = I('order_id');
        $type_  = I('type');
        $total_money  = I('total_money');

        if ($type_ == 'shop') { // 快店
            $now_order = D("Shop_order")->get_order_by_orderid($order_id);
            $order_content = D('Shop_order_detail')->join('as o LEFT JOIN '.C('DB_PREFIX').'shop_goods g ON g.goods_id = o.goods_id')->field('o.*,g.score_max,score_percent')->where(array('order_id' => $order_id))->select();
            foreach ($order_content as &$trow) {
                $trow['name'] = $trow['spec'] ? $trow['name'] . ' (' . $trow['spec'] . ')' : $trow['name'];
                $trow['money'] = floatval($trow['price'] * $trow['num']);
            }
            $now_order['order_content'] = $order_content ? $order_content :array();
        }else{
            if ($order_id) {
                $now_order = M("Store_order")->where(array('order_id'=>$order_id))->find();
                switch($now_order['business_type']){
                    case 'foodshop': 
                        $foodshop_order = M('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->find();
                        $type_ = 'meal';
                        break;
                    default:// 到店付（优惠买单）
                        $type_ = 'store';
                }
            }else{
                $now_order = array();
                $type_ = 'store';
            }
            
        }
        $now_order['total_money'] =  $total_money;
        // var_dump($now_order);

        $now_user = D('User')->get_user($now_order['uid']);
        if (!$now_user) {
            $now_user = D('User')->get_user($_POST['uid']);
        }

 

        //使用积分
        $score_can_use_count=0;
        $score_deducte=0;
        $user_score_use_percent = (float)$this->config['user_score_use_percent'];

        
        $user_score_use_condition = $this->config['user_score_use_condition'];
        $user_score_max_use = D('Percent_rate')->get_max_core_use($order_info['mer_id'], $type_,$now_order['total_money'],$now_order);//不同业务不同积分

        $user_score_use_percent = (float)$this->config['user_score_use_percent'];
        $score_max_deducte = bcdiv($user_score_max_use, $user_score_use_percent, 2);

        if ($user_score_use_percent > 0 && $score_max_deducte > 0 && $user_score_use_condition > 0 ) {   //如果设置没有错误
            $total_ = $now_order['total_money'];

            if ($total_ >= $user_score_use_condition) {             //判断是否够使用积分的条件，即这个订单的总额是否大于规定的金额
                if ($total_ > $score_max_deducte) {                    //判断积分最大抵扣金额是否比这个订单的总额大
                    $score_can_use_count = (int)($now_user['score_count'] > $user_score_max_use ? $user_score_max_use : $now_user['score_count']);//如果用户积分大于每个订单的积分可使用数 则按可使用数算
                    $score_deducte = bcdiv($score_can_use_count, $user_score_use_percent, 2);
                    $score_deducte = $score_deducte > $total_ ? $total_ : $score_deducte;
                } else {
                    $score_can_use_count = ceil($total_ * $user_score_use_percent) > (int)$now_user['score_count'] ? (int)$now_user['score_count'] : ceil($total_ * $user_score_use_percent);
                    $score_deducte = bcdiv($score_can_use_count, $user_score_use_percent, 2);
                    $score_deducte = $score_deducte > $total_ ? $total_ : $score_deducte;
                }
            }
        }
        $this->returnCode(0,array('score_deducte'=>$score_deducte,'score_can_use_count'=>$score_can_use_count));
    }

    // 获得优惠券
    public function get_coupon(){
        $order_id  = I('order_id');
        $total_money  = I('total_money');

        $uid = I('uid') ? intval(I('uid')) : '';
        $type = I('business_type') == 'foodshop' ? 'meal' : 'store';
        
        if( $user = D('User')->field(true)->where(array('uid' => $uid))->find()) {

            $card = D('Card_userlist')->field(true)->where(array('uid' => $user['uid'], 'mer_id' => $this->store['mer_id'],'status'=>1))->find();

            $card_source = M(_view('Card_new'))->where(array('mer_id' => $this->store['mer_id'], 'status' => 1))->find();
            // var_dump($card);
            if ($card&& $card_source) {
                //商家优惠券
                $mer_coupon = D('Card_new')->get_use_coupon_by_params($user['uid'], $this->store['mer_id'], $type); 
            // var_dump($mer_coupon);
                if ($mer_coupon) {
                    foreach ($mer_coupon as &$value) {
                        $value['platform'] = unserialize($value['platform']);
                    }
                }   
            }

            $now_order['total_money'] =  $total_money;

            //平台优惠券
            $system_coupon = D('System_coupon')->get_noworder_coupon_list($now_order, $type, $user['phone'], $user['uid'], '','','',false);
            
        }
        $return['card_new'] = $mer_coupon ? $mer_coupon : array();
        $return['system_coupon'] = $system_coupon ? $system_coupon : array();
        
        $this->returnCode(0,$return);
    }

    //风景票券列表
    public function ticket_order_list(){
        $this->check_scenic();
        $mer_id=$this->store['mer_id'];
        $scenic_id=M('Scenic_list')->field('scenic_id')->where(array('company_id'=>$mer_id))->getField('scenic_id');

        $last_order_id=I('order_id','','intval');
        $find_type=I('find_type','','intval');
        $find_value=I('find_value');

        $where=array();
        $where['o.scenic_id']=$scenic_id;
        if($find_type==1){
            $order_id=D('Scenic_order_com')->where(array('code'=>array('like',"%".$find_value."%"),'type'=>1))->getField('order_id');
            $where['order_id']=$order_id;
        }elseif($find_type==2){
            $where['order_id']=$find_value;
        }elseif($find_type==3){
            $where['user_id']=$find_value;
        }elseif($find_type==4){
            $user_id=M('user')->where(array('nickname'=>array('like','%'.$find_value.'%')))->getField('uid');
            if($user_id){
                $where['user_id']=$user_id; 
            }else{
                $where['user_id']='';
            }
        }elseif($find_type==5){
            $now_user=D('User')->get_user($find_value,'phone');
            if(!empty($now_user)){
                $where['user_id']=$now_user['uid'];
            }else{
                $where['user_id']='';
            }
            
        }

        $db=M('scenic_order');
        //当前店铺购买票券的数量
        $count=$db->alias('o')->join('left join '.C('DB_PREFIX').'scenic_ticket t on t.ticket_id=o.ticket_id')->where($where)->count();
        if($last_order_id>0){
            $where['o.order_id']=array('lt',$last_order_id);
        }
        $order_list=$db->alias('o')->join('left join '.C('DB_PREFIX').'scenic_ticket t on t.ticket_id=o.ticket_id')->field('order_id,o.scenic_id,ticket_title,order_total,ticket_time,ticket_num,paid,order_status,is_group')->where($where)->limit(0,10)->order('order_id desc')->select();
		$order_list = $order_list ? $order_list : [];
        $where=array();
        $scenic_image_class = new scenic_image();
        //今日的日期
        $now_date=date("Y-n-j");
        foreach ($order_list as $k => $v) {
            //获取票券图片
            $where['scenic_id']=$v['scenic_id'];
            $scenic_pic=D('Scenic_list')->where($where)->getField('scenic_pic');
            $arr=explode(';',$scenic_pic);
            $v['pic']=$scenic_image_class->get_image_by_path($arr[0],$this->config['site_url'],'config','s');
            if($v['paid']==2 && $v['order_status']==1 && $v['ticket_time']==$now_date){
                $v['state']=1;
            }else{
                $v['state']=0;
            }
            if($v['ticket_num']==1 || $v['is_group']==2){
                $v['type']=0;
            }else{
                $v['type']=1;
            }
            $order_list[$k]=$v;
        }
        $result_arr=array();
        $result_arr['order_list']=(array)$order_list;
        $result_arr['page']=ceil($count/10);
        $result_arr['count']=$count;
        $result_arr['status']=1;
        $this->returnCode(0,$result_arr);
    }

    //景区门票多核销券
    public function ticket_pass_array(){
        $this->check_scenic();
        $order_id=I('order_id','','intval');
        $where=array();
        $where['order_id']=$order_id;
        $info=M('scenic_order')->where($where)->field('order_id,order_imm,paid,order_status')->find();
        $where['type']=1;
        $ticket_list=M('Scenic_order_com')->where($where)->field('com_id,order_id,type_id,code,status')->select();
        $flag=1;
        foreach ($ticket_list as $k => $v) {
            if($v['status']!=1){
                $flag=0;
                break;
            }
        }
        $data=array();
        $data['ticket_list']=$ticket_list;
        $data['info']=(array)$info;
        //是否展示全部核销的按钮
        $data['flag']=$flag;
        $this->returnCode(0,$data);
    }

    //景区详情
    public function ticket_order_details(){
        $this->check_scenic();        
        $order_id=I('order_id','','intval');
        if(empty($order_id)){
            $this->error();
        }
        $where=array();
        $where['order_id']=$order_id;
        $user_order=D('Scenic_order')->get_one_order($where);
        if(empty($user_order)){

        }
        $user_order['user']=D('User')->get_user($user_order['user_id']);
        $user_order['order_total']=floor($user_order['order_detail']);
        //订单商品
        $order_com=D('Scenic_order')->get_order_com($where);
        if(empty($order_com)){

        }
        $scenic_list=D('Scenic_list')->get_one_list(array('scenic_id'=>$user_order['scenic_id']));
        $ticket=array();
        $park=array();
        $guide=array();
        //二维码
        $ticket_code=array();
        $rc4_key = 'gm228689ty';
        foreach ($order_com as $k => $v) {
            if($v['type']==1){      //门票
                $ticket[]=$v;
                $tmp_ticket = '000000'.sprintf("%07s",$user_order['ticket']['scenic_id']).'001'.'1'.date('ymd',time()).sprintf("%016s",$v['code']);
                //$rc4_ticket =R('Wap/Scenic_user/getRc4Encode',array($rc4_key,$tmp_ticket));
                //$ticket_zj[] = bin2hex($rc4_ticket);
            }elseif($v['type']==2){ //停车场
                $park[]=$v;
            }elseif($v['type']==3){ //导游
                $guide[]=$v;
            }
        }
        //门票信息
        if(empty($ticket)){

        }
        $where2=array();
        $where2['ticket_id']=$user_order['ticket_id'];
        $scenic_ticket=D('Scenic_ticket')->get_scenic_one_ticket($where2);

        if(!empty($park)){
            $where3=array();
            $where3['parking_id']=$park['0']['type_id'];
            $scenic_prak=D('Scenic_park')->get_user_park($where3);
            $prak_count=count($park);
        }
        $scenic_image_class=new scenic_image();
        //向导信息
        if(!empty($guide)){
            $where4=array();
            $where4['guide_id']=$guide['0']['type_id'];
            $scenic_guide=D('Scenic_guide')->city_get_one_guide($where4);
            $scenic_guide['pic']=$scenic_image_class->get_image_by_path($scenic_guide['guide_pig'],$this->config['site_url'],'guide','s');
            $guide_number=1;
        }else{
            $guide_number=0;
        }
        
        //订单信息
        $now_order=array();
        //图片
        $pic_arr=explode(';',$scenic_list['scenic_pic']);
        $now_order['pic']=$scenic_image_class->get_image_by_path($pic_arr[0],$this->config['site_url'],'config','s');;
        $now_order['title']=$scenic_ticket['ticket_prefix_title'];
        $now_order['price']=floatval($ticket['0']['price']);
        $now_order['num']=count($ticket);
        $now_order['order_state']=$this->get_order_status($user_order['paid'],$user_order['order_status']);
        //订单总价
        $prak_count=count($park);
        $total_price=$park['0']['price']*$prak_count+$scenic_list['guide_price']*$guide_number+$ticket['0']['price']*$user_order['ticket_count'];
        //门票总价
        $ticket_price=$ticket['0']['price']*$user_order['ticket_count'];
        $now_order['total_price']=floatval($total_price);
        $now_order['ticket_price']=floatval($ticket_price);
        //导游信息
        $guide_arr=array();
        if($scenic_guide){
            $guide_arr=$scenic_guide;
            $guide_arr['guide_price']=$scenic_list['guide_price'];
        }
        //停车信息
        $park_arr=array();
        if($park){
            $park_arr['list']=$park;
            $park_arr['prak_price']=$park['0']['price'];
            $park_arr['prak_count']=$prak_count;
        }
        //出行消息
        $travel_arr=array();
        if(is_array($user_order['group_traveler_img']) && $user_order['is_group']==2){
            $travel_arr['img_list']=$user_order['group_traveler_img'];
        }elseif($user_order['is_group']==1){
            $family_id = explode(",",$user_order['family_id']);
            $card_type = array(
                'passport'=>L_('护照'),
                'Taiwan_card'=>L_('台胞证'),
                'HongKong_Macao'=>L_('港澳通行证'),
                'hometown_card'=>L_('回乡证'),
                'internation_seafarer_card'=>L_('国际海员证'),
            );
            foreach($family_id as $v){
                $arr=D('Scenic_family')->get_one(array('family_id'=>$v));
                foreach ($card_type as $k2 => $v2) {
                    if(!empty($v[$k2])){
                        $arr['card_info']=$v2.$v[$k2];
                    }
                }
                $family[]=$arr;
            }
            $travel_arr['family_list']=$family;
        }
        //确认码
        if($user_order['order_status']!=5 && $user_order['order_status']!=6){
            foreach ($ticket as $k => $v) {
                if($v['status']==1){
                    $v['state']=L_('未消费');
                }elseif($v['status']==2){
                    $v['state']=L_('已消费');
                }elseif($v['status']==3){
                    $v['state']=L_('已退款');
                }
                if($scenic_ticket['ticket_group']==2){
                    $v['num']=$user_order['ticket_num']-$order_com['0']['use_num'];
                    $v['stype']=1;
                }else{
                    $v['stype']=2;
                    if($scenic_ticket['use_limit']>0){
                        if($scenic_ticket['use_limit']>$v['use_num']){
                            $v['num']=intval($v['use_num']);
                        }else{
                            $v['num']=-1;
                        }
                    }
                }
                $ticket[$k]=$v;
            }
            $travel_arr['ticket_list']=$ticket;
        }
        //使用方式
        $ticket_arr=array();
        $ticket_arr['title']=$scenic_ticket['ticket_title'];
        $ticket_arr['sdate']=$user_order['ticket_time'];
        $ticket_arr['fdate']=date('Y-m-d',$user_order['endtime']);
        $ticket_arr['park_intr']=$scenic_ticket['park_intr'];
        $ticket_arr['ticket_cue']=$scenic_ticket['ticket_cue'];
        $ticket_arr['ticket_union_txt']=$scenic_ticket['ticket_union_txt'];
            
        $result_arr=array();
        $result_arr['now_order']=$now_order;
        $result_arr['guide_arr']=$guide_arr;
        $result_arr['park_arr']=$park_arr;
        $result_arr['travel_arr']=$travel_arr;
        $result_arr['ticket_arr']=$ticket_arr;
        $this->returnCode(0,$result_arr);
    }

    //景区门票核销
    public function ajax_verify_ticket(){
        $this->check_scenic();
        $order_id=I('order_id','','intval');
        $result_arr=array();
        $result_arr['errorCode']=1;
        if(empty($order_id)){
            $result_arr['errorMsg']=L_('缺少参数！');
            echo json_encode($result_arr);exit;
        }
        $where=array();
        $where['order_id']=$order_id;
        $info=D("Scenic_order")->get_one_order($where);
        if(empty($info)){
            $result_arr['errorMsg']=L_('订单不存在！');
            echo json_encode($result_arr);exit;
        }elseif($info['paid']!=2 || $info['order_status']!=1){
            $result_arr['errorMsg']=L_('订单状态错误！');
            echo json_encode($result_arr);exit;
        }
        //卡券id
        $com_id=I('com_id',0,'intval');
        $db=M('Scenic_order_com');
        //核销指定票券
        if($com_id>0){
            //该订单所有的票券
            $where2=array();
            $where2['order_id']=$order_id;
            $where2['type']=1;
            $card_info=$db->where($where2)->field("count(*) as count,type_id")->find();
            $count=$card_info['count'];


            //已经核销的票券数量
            $where2['status']=2;
            $done_num=$db->where($where2)->count(1);
            if($count<=$done_num){
                $result_arr['errorMsg']=L_('卡券已全部核销！');
                echo json_encode($result_arr);exit;
            }
            $where2['com_id']=$com_id;
            $where2['status']=1;    //未核销
            $ticket_info=$db->where($where2)->find();
            if(empty($ticket_info)){
                $result_arr['errorMsg']=L_('卡券不存在！');
                echo json_encode($result_arr);exit;
            }
            $time=time();
            //更新票券的状态和记录已使用次数
            $data=array();
            $data['status']=2;
            $data['last_time']=$time;
            $use_limit=M('Scenic_ticket')->where(array('ticket_id'=>$ticket_info['type_id']))->getField('use_limit');
            if($use_limit>0){
                $data['use_num']=$use_limit;
            }
            $res=$db->where($where2)->save($data);
            if($res){
                //更新景区收入
                $desc=L_('核销景区门票订单X1的消费码<br/>X2记入收入',array('X1'=>$info['order_imm'],'X2'=>$ticket_info['code']));
                $res=D('Scenic_money_list')->add_row($info['scenic_id'],1,$ticket_info['price'],$desc,$order_id);
                //全部完成处理
                if($count-$done_num==1){    
                    D('Scenic_order')->where(array('order_id'=>$order_id))->setField('order_status',2);
                    //增加票券的销售数量
                    M('Scenic_ticket')->where(array('ticket_id'=>$ticket_info['type_id']))->setInc('sale_count',$count);
                    //增加导游服务人数
                    $where3=array();
                    $where3['order_id']=$order_id;
                    $where3['type']=3;
                    $guide_id=$db->where($where3)->getField('type_id');
                    if($guide_id>0){
                        M('Scenic_guide')->where(array('guide_id'=>$guide_id))->setInc('guide_service_number',1);   
                    }
                    $where4=array();
                    $where4['order_id']=$order_id;
                    $where4['type']=array('in',array(2,3));
                    //更新导游和停车场
                    $data2=array();
                    $data2['status']=2;
                    $data2['last_time']=$time;
                    M('Scenic_order_com')->where($where4)->save($data2);
                    $total_price=M('Scenic_order_com')->where($where4)->sum('price');
                    if($total_price>0){
                        $desc=L_('核销景区门票订单X1的导游、停车场收费记入收入',$info['order_imm']);
                        $res=D('Scenic_money_list')->add_row($info['scenic_id'],1,$total_price,$desc,$order_id);
                    }
                }
                $result_arr['errorCode']=0;
                $result_arr['errorMsg']=L_('订单验证成功！');
                echo json_encode($result_arr);exit;
            }else{
                $result_arr['errorMsg']=L_('订单验证失败！');
                echo json_encode($result_arr);exit;
            }           
        }else{
            //更新停车场和导游的信息
            $data=array();
            $data['status']=2;
            $data['last_time']=time();
            $where['type']=array('in',array(2,3));
            $res1=$db->where($where)->save($data);
            //更新票券的信息
            if($info['is_group']==2){
                $num=$info['ticket_num'];
                $data['use_num']=$num;
            }else{
                $type_id=$db->where(array('order_id'=>$order_id,'type'=>1))->getField('type_id');
                $use_limit=M('scenic_ticket')->where(array('ticket_id'=>$type_id))->getField('use_limit');
                if($use_limit>0){
                    $data['use_num']=$use_limit;
                }
            }
            $where['type']=1;            
            $res2=$db->where($where)->save($data);
            if($res2){
                $ticket_info=$db->where($where)->field('count(*) as count,type_id')->find();
                D('Scenic_order')->where(array('order_id'=>$order_id))->setField('order_status',2);
                //增加票券的销售数量
                M('Scenic_ticket')->where(array('ticket_id'=>$ticket_info['type_id']))->setInc('sale_count',$ticket_info['count']);
                //增加导游服务人数
                $where3=array();
                $where3['order_id']=$order_id;
                $where3['type']=3;
                $guide_id=$db->where($where3)->getField('type_id');
                M('Scenic_guide')->where(array('guide_id'=>$guide_id))->setInc('guide_service_number',1);
                //更新景区收入
                $res=D('Scenic_money_list')->add_row($info['scenic_id'],1,$info['order_total'],L_('用户购买门票记入收入'),$order_id);
                $result_arr['errorCode']=0;
                $result_arr['errorMsg']=L_('订单验证成功！');
                echo json_encode($result_arr);exit;
            }else{
                $result_arr['errorMsg']=L_('订单验证失败！');
                echo json_encode($result_arr);exit;
            }
        }
    }

    //获取当前状态
    private function get_order_status($pay_status=null,$order_status=null){
        $order_state='';
        if($pay_status==1){
            if($order_status==0){
                $order_state=L_('未支付');
            }
        }elseif($pay_status==2){
            if($order_status==1){
                $order_state=L_('已支付 未入园');
            }elseif($order_status==2){
                $order_state=L_('已消费 未评论');    
            }elseif($order_status==3){
                $order_state=L_('已评论 已完成');
            }elseif($order_status==4){
                $order_state=L_('已退款');
            }elseif($order_status==5){
                $order_state=L_('已过期 未付款');
            }elseif($order_status==6){
                $order_state=L_('已过期 未入园');
            }
        }
        return $order_state;
    }

    //校验店员操作景区票券权限
    private function check_scenic(){
        //判断商家是否开启了景区功能
        $now_merchant = D('Merchant')->get_info($this->store['mer_id']);
        if($now_merchant['is_open_scenic']==1){
            $is_verify_ticket=intval($this->staff_session['is_verify_ticket']);  //景区门票
        }else{
            $is_verify_ticket=0;
        }
        if($is_verify_ticket==0){
            $this->returnCode('20130048');
        }
    }

    // 团购订单列表
    public function group_list()
    {
        $this->check_group();
        $store_id = $this->store['store_id'];
        $order_id = $_POST['order_id'];
        $condition_where = "`o`.`paid`=1 AND `o`.`uid`=`u`.`uid` AND `o`.`group_id`=`g`.`group_id` AND `o`.`store_id`='$store_id'";
        $condition_table = array(
            C('DB_PREFIX') . 'group' => 'g',
            C('DB_PREFIX') . 'group_order' => 'o',
            C('DB_PREFIX') . 'user' => 'u'
        );
        $count = D('')->where($condition_where)
            ->table($condition_table)
            ->count();
        if ($order_id) {
            $condition_where .= " AND `o`.`order_id` < '$order_id'";
        }
        $order_list = D('')->field('`o`.`phone` AS `group_phone`,`o`.*,`g`.`s_name`,`g`.`pic`,`u`.`uid`,`u`.`nickname`,`u`.`phone`,g.group_cate')
            ->where($condition_where)
            ->table($condition_table)
            ->page('1', '10')
            ->order('`o`.`add_time` DESC')
            ->select();
        if (empty($order_list)) {
            $arr['order_list'] = array();
        } else {
            // 团购优惠组合
            $combineIds = array_column($order_list, 'combine_id');
            $combineList = array_column(D('Group_combine_activity')->where(['combine_id'=>['in',$combineIds]])->select(),'title','combine_id');

            foreach ($order_list as &$item) {
                if($item['is_head'] || $item['pin_fid']>0){
                    $group_start = D('Group_start')->get_group_start_by_order_id($item['order_id']);
                    $item['group_start_status']  =$group_start['status'];
                }else{
                    $item['group_start_status']  = 1;
                }

                // 团购优惠组合
                if($combineList[$item['combine_id']]){
                    $item['s_name'] .= '【'.$combineList[$item['combine_id']].'】';
                }
            }
            $arr['order_list'] = $this->group_format($order_list);
        }
        $arr['page'] = ceil($count / 10);
        $arr['count'] = $count;
        $arr['status'] = 1;
        $this->returnCode(0, $arr);
    }
    //	团购新单统计数量
	public function group_count()
	{
		$this->check_group();
		$time = I('time', time());
		$where = array('mer_id' => $this->store['mer_id'], 'store_id' => $this->store['store_id'], 'pay_time' => array('gt', $time));
		$count = M('Group_order')->where($where)->count();
// 		$data_store_staff['device_id'] = $this->DEVICE_ID;
// 		$data_store_staff['last_time'] = $_SERVER['REQUEST_TIME'];
// 		$data_store_staff['client'] = I('client');
// 		if($data_store_staff['client'] ==2){
// 			$save=M('Merchant_store_staff')->where($where)->data($data_store_staff)->save();
// 		}

		if ($count < 0) {
			$this->returnCode('20130027');
		}
		$this->returnCode(0, array('count' => $count, 'time' => time()));
	}
    //	团购搜索
    public function group_find(){
    	$this->check_group();
    	$order_id	=	$_POST['order_id'];
        $mer_id = $this->store['mer_id'];
        $condition_where = "`o`.`uid`=`u`.`uid` AND `o`.`group_id`=`g`.`group_id` AND `o`.`mer_id`='$mer_id'";
        $find_value = $_POST['find_value'];
        $store_id = $this->store['store_id'];
        $res	=	'';
        if ($_POST['find_type'] == 1 && (strlen($find_value) == 14 || C('config.baodi_ticket_url'))) {
            $condition_where .= " AND (`o`.`store_id`='$store_id' OR `o`.`store_id` <= 0)";
            $res = D('Group_pass_relation')->get_orderid_by_pass($find_value);
            if(!empty($res)){
                $condition_where .= " AND `o`.`order_id`=".$res['order_id'];
            }else{
                $condition_where .= " AND `o`.`group_pass`='$find_value'";
            }
            //$condition_where .= " AND `o`.`group_pass`='$find_value'";
        } else {
            $condition_where .= " AND `o`.`store_id`='$store_id'";
            if ($_POST['find_type'] == 1) {
                $condition_where .= " AND `o`.`group_pass` like '%$find_value%'";
            } else if ($_POST['find_type'] == 2) {
                $condition_where .= " AND `o`.`express_id` like '%$find_value%'";
            } else if ($_POST['find_type'] == 3) {
                $condition_where .= " AND `o`.`real_orderid`='$find_value'";
            } else if ($_POST['find_type'] == 4) {
                $condition_where .= " AND `o`.`group_id`='$find_value'";
            } else if ($_POST['find_type'] == 5) {
                $condition_where .= " AND `o`.`uid`='$find_value'";
            } else if ($_POST['find_type'] == 6) {
                $condition_where .= " AND `u`.`nickname` like '%$find_value%'";
            } else if ($_POST['find_type'] == 7) {
                $condition_where .= " AND (`o`.`phone` like '%$find_value%' OR `u`.`phone` like '%$find_value%')";
            }
        }
        $condition_table = array(C('DB_PREFIX') . 'group' => 'g', C('DB_PREFIX') . 'group_order' => 'o', C('DB_PREFIX') . 'user' => 'u');
        $count = D('')->where($condition_where)->table($condition_table)->count();
        if($order_id){
        	if(empty($res)){
				$condition_where .= " AND `o`.`order_id` < '$order_id%'";
        	}
        }
        $order_list = D('')->field('`o`.`phone` AS `group_phone`,`o`.*,`g`.`s_name`,`g`.`pic`,`u`.`uid`,`u`.`nickname`,`u`.`phone`,g.group_cate')->where($condition_where)->table($condition_table)->page('1','10')->order('`o`.`add_time` DESC')->select();
        if ($order_list) {
        	$arr['order_list']	=	$this->group_format($order_list);
        }else{
			$arr['order_list']	=	array();
        }
        $arr['page'] = ceil($count/10);
        $arr['count'] = $count;
        $arr['status'] = 2;
        $this->returnCode(0,$arr);
    }
    /* 团购相关 */
    protected function check_group(){
        if (empty($this->store['have_group'])){
			$this->returnCode('20130005');
        }
    }

        // 团购状态格式化
    private function group_format($order_list)
    {
        if ($order_list) {
            $m_goup_specifications = M('Group_specifications');
            foreach ($order_list as $v) {
                $status_format = $this->status_format($v['status'], $v['paid'], $v['third_id'],$v['pay_type'], $v['tuan_type'],$v['express_id'],$v['is_pick_in_store']);
                $group_image_class = new group_image();
                $all_pic = $group_image_class->get_allImage_by_path($v['pic']);

                if($v['group_cate'] == 'cashing'){
                    $pic = thumb_img(C('config.site_url').'/static/images/group/cashing.png',200,150);
                }elseif($v['group_cate'] == 'booking_appoint'){
                    $pic = thumb_img(C('config.site_url').'/static/images/group/booking_appoint.png',200,150);
                }else{
                    $pic = $all_pic[0]['s_image'];
                }
                $tmp =array(
                    's_name' => $v['s_name'], // 名称
                    'num' => $v['num'], // 数量
                    'total_money' => $v['total_money'], // 总价
                    'status' => $status_format['status'], // 状态
                    'type' => $status_format['type'], // 团购券状态
                    'order_id' => $v['order_id'], // 团购ID
                    'pic' => $pic, // 图片
                    'pass_array' => $v['pass_array'], // 判断多个优惠券还是单个
                    'is_pick_in_store' => $v['is_pick_in_store'], // 取货确认
                    'tuan_type' => $v['tuan_type'], // 取货确认
                    'express' => $v['tuan_type'], // 取货确认
                    'third_id' => $v['third_id'], // 三方id
                    'pay_type' => $v['pay_type'], // 支付方式
                );

                if($v['is_head'] || $v['pin_fid']>0){
                    $group_start = D('Group_start')->get_group_start_by_order_id($v['order_id']);
                    $tmp['group_start_status']  =$group_start['status'];
                }else{
                    $tmp['group_start_status']  = 1;

                }
                // 如果存在 获取对应规格
                if ($v['specifications_id']) {
                    $specifications_info = $m_goup_specifications->field('specifications_name')->where(array('specifications_id' => $v['specifications_id']))->find();
                    if ($specifications_info) {
                        $tmp['s_name'] = $v['s_name'] . '('.$specifications_info['specifications_name'].')';
                    }
                }

                $arr[] =$tmp;
            }
            return $arr;
        }
        return array();
    }
    //	团购状态格式化
    private function status_format($order_status,$paid,$third_id,$pay_type,$tuan_type=1,$express_id=0,$is_pick_in_store=false){
    	$type	=	0;
		$status	=	0;
		if($order_status ==	9){
            $status	=	9;	//已过期平台冻结
        }else if($order_status	==	3){
			$status	=	1;	//已取消
		}else if($order_status==6){
            $status	=	1;	//部分退款
		}else if($paid){
			if($third_id==3 && $pay_type=='offline' && $order_status==0){
				$status	=	2;	//线下未付款
			}else if($order_status==0){
				$status	=	3;	//已付款
				if($tuan_type!=2){
					$type	=	1;	//未消费
				}else{
                    if($is_pick_in_store){
                        $type	=	7;   //未取货
                    }else{
                        if($express_id){
                            $type	=	4;	//未发货
                        }else{
                            $type	=	2;	//未发货
                        }
                    }
				}
			}else if($order_status==1){
				$status	=	4;	//待评价
                if($is_pick_in_store){
                    $type	=	8;   //已取货
                }else {
                    if ($tuan_type != 2) {
                        $type = 3;    //已消费
                    } else {
                        $type = 4;    //已发货
                    }
                }
			}else if($order_status==7){ // 退款失败
                $type	=	1;	//未消费
            }else{
				$status	=	5;	//已完成
			}
		}else{
			$status	=	6;	//未付款
		}
		$arr	=	array(
			'type'	=>	$type,
			'status'	=>	$status,
		);
		return $arr;
    }
    //	验证团购劵
    public function group_verify(){
        $this->check_group();
        $order_id = $_POST['order_id'];
        $param_arr = explode('_',$order_id);
        if($param_arr&&count($param_arr)==5){
            $order_id = $param_arr[0];
        }
        $database_group_order = D('Group_order');
        $now_order = $database_group_order->get_order_detail_by_id_and_merId($this->store['mer_id'], $order_id, false);
		if(empty($now_order['paid'])){
			$this->returnCode('20130019');
		}
        if($now_order['status']!=0 && $now_order['status']!=7){
			$this->returnCode('20130038');
		}
        if (empty($now_order)) {
            $this->returnCode('20130006');
        }

        // 场次预约结束
        if($combine = M('Group_booking_appoint_order')->where(['order_id'=>$now_order['order_id']])->find()){
            $now_order['deadline_time'] = $combine['book_end_time'];
        }

        $deadline_time = $now_order['effective_type'] != 1 ? $now_order['deadline_time'] : $now_order['pay_time'] + $now_order['deadline_time'] * 86400;
        if ($deadline_time <= time()) { //已过期
            $result_arr = array();
            $result_arr['errorCode'] = 1;
            $result_arr['errorMsg']  = L_('团购已过期！');
            echo json_encode($result_arr);exit;
        }

        if ($now_order['paid'] && ($now_order['status'] == 0 || $now_order['status']==7)) {

            if($now_order['is_head'] || $now_order['pin_fid']>0){
                $group_start = D('Group_start')->get_group_start_by_order_id($now_order['order_id']);
                $group_start_status  = $group_start['status'];
                if ($group_start && $group_start_status == 0) {
                    $this->returnCode('20130049');
                } elseif ($group_start && $group_start_status == 2) {
                    $this->returnCode('20130050');
                }
            }

            $condition_group_order['order_id'] = $now_order['order_id'];
            if (empty($now_order['third_id']) && $now_order['pay_type'] == 'offline') {
                $data_group_order['third_id'] = $now_order['order_id'];
            }
            $data_group_order['status'] = '1';
            $data_group_order['store_id'] = $this->store['store_id'];
            $data_group_order['use_time'] = $_SERVER['REQUEST_TIME'];
            $data_group_order['last_staff'] = $this->staff_session['name'];
			$data_group_order['staff_name'] = $this->staff_session['name'];
			$data_group_order['verify_time'] = $_SERVER['REQUEST_TIME'];
            $data_group_order['last_random'] = mt_rand(100000000,999999999);
            if ($database_group_order->where($condition_group_order)->data($data_group_order)->save()) {
                // 计算未验证的验证码数量
                $pass_num = $now_order['pass_num']>1?intval($now_order['pass_num']):1; // 一份团购 对应生成核销券 份数
                $consume_num = D('Group_pass_relation')->get_pass_num($now_order['order_id'],1);
                $unconsume_pass_num = $now_order['num']*$pass_num-$consume_num;
                $now_order['unconsume_pass_num'] = $unconsume_pass_num; // 未验证核销码数量

            	$this->group_notice($now_order,1);
                $staff_info = array(
                    'staff_name' => $this->staff_session['name'],
                    'verify_time' => $_SERVER['REQUEST_TIME'],
                    'verify_type' => 1
                );
                D('Group_pass_relation')->change_refund_status($now_order['order_id'], 1, $staff_info);

				//验证增加商家余额
				$now_order['order_type'] = 'group';
				$now_order['verify_all'] = 1;
				$now_order['store_id'] = $this->store['store_id'];
				//D('Merchant_money_list')->add_money($this->store['mer_id'],'用户购买'.$now_order['order_name'].'记入收入',$now_order);
                $now_order['desc']=L_('用户购买X1记入收入',$now_order['order_name']);
                D('SystemBill')->bill_method($now_order['is_own'],$now_order);
                /*-----记录系统订单信息 验证消费 改写为已完成 19/1/18----*/
                D("System_order")->save_reg_order_info('group',$now_order['order_id'],array(),2);
                //增加消息通知
                $url = '\app\common\model\service\user\UserNoticeService/addNotice';
                $params['type'] = 0;
                $params['business'] = 'group';
                $params['order_id'] = $now_order['order_id'];
                $params['mer_id'] = $now_order['mer_id'];
                $params['store_id'] = $now_order['store_id'];
                $params['uid'] = $now_order['uid'];
                $params['title'] = '核销成功，已经领取商品';
                $params['content'] = '您已经领取团购商品，请核对订单';
                invoke_v20_service($url, [$params]);

                $this->returnCode(0);
            } else {
                $this->returnCode('20130007');
            }
        } else {
            $this->returnCode('20130008');
        }
    }
    //	验证多个团购券(单个验证)
    public function group_array_verify(){
        $this->check_group();
        $order_id = $_POST['order_id'];
        $param_arr = explode('_',$order_id);
        if($param_arr&&count($param_arr)==5){
            $order_id = $param_arr[0];
        }
        $database_group_order = D('Group_order');
        $now_order = $database_group_order->get_order_detail_by_id_and_merId($this->store['mer_id'],$order_id,false);
		$verify_all = false;
		if(empty($now_order['paid'])){
			$this->returnCode('20130019');
		}
        if($now_order['status']!=0 && $now_order['status']!=7){
			$this->returnCode('20130038');
		}
        if(empty($now_order)){
            $this->returnCode('20130006');
        }else{
            if($now_order['is_head'] || $now_order['pin_fid']>0){
                $group_start = D('Group_start')->get_group_start_by_order_id($now_order['order_id']);
                $group_start_status  = $group_start['status'];
                if ($group_start && $group_start_status == 0) {
                    $this->returnCode('20130049');
                } elseif ($group_start && $group_start_status == 2) {
                    $this->returnCode('20130050');
                }
            }

            $where['order_id'] = $now_order['order_id'];
            $where['group_pass'] =$_POST['group_pass'];
            $group_pass_rela = D('Group_pass_relation');
            $res = $group_pass_rela->where($where)->find();
            if(!empty($res)){
                $date['status']=1;
				$date['staff_name'] = $_POST['staff_name'] ? $_POST['staff_name'] : $this->staff_session['name'];
				$date['verify_time'] = $_SERVER['REQUEST_TIME'];
				$date['verify_type'] = 1;
                if($group_pass_rela->where($where)->data($date)->save()){
                    $count = $group_pass_rela->get_pass_num($where['order_id']);
                    $count += $group_pass_rela->get_pass_num($where['order_id'],3);
                    if($count==0){
                        if (empty($now_order['third_id']) && $now_order['pay_type'] == 'offline') {
                            $data_group_order['third_id'] = $now_order['order_id'];
                        }
                        $data_group_order['status'] = '1';
						$verify_all = true;
                    }else{
                        $now_order['total_money'] = $now_order['price'];
                        $now_order['res'] = $res;
                    }
                    $data_group_order['store_id'] = $this->store['store_id'];
                    $condition_group_order['order_id'] = $where['order_id'];
                    $data_group_order['use_time'] = $_SERVER['REQUEST_TIME'];
                    $data_group_order['last_staff'] = $this->staff_session['name'];
                    $data_group_order['last_random'] = mt_rand(100000000,999999999);
                    if ($now_order['status'] == 7 && !$verify_all) {
                        $data_group_order['status'] = 0;
                    }
                    $save_msg = D('Group_order')->where($condition_group_order)->data($data_group_order)->save();
                    if($save_msg){
                        //验证增加商家余额
                        $now_order['order_type'] = 'group';
                        $now_order['verify_all'] = 0;
                        $now_order['store_id'] = $this->store['store_id'];
                        //D('Merchant_money_list')->add_money($this->store['mer_id'],'验证团购订单'.$now_order['real_orderid'].'的消费码</br>'.$_POST['group_pass'].'记入收入',$now_order);
                        $now_order['desc']=L_('验证团购订单X1的消费码</br>X2记入收入',array('X1'=>$now_order['real_orderid'],'X2'=>$_POST['group_pass']));
                        D('SystemBill')->bill_method($now_order['is_own'],$now_order);
                        $this->group_notice($now_order, $verify_all);
                        /*-----记录系统订单信息 验证消费 改写已完成 19/1/18----*/
						if($data_group_order['status']){
							D("System_order")->save_reg_order_info('group',$now_order['order_id'],array(),2);
						}
						$this->returnCode(0);
                    }else{
                        $date['status'] = 0;
                        $group_pass_rela->where($where)->data($date)->save();
                        $this->returnCode('20130007');
                    }
                }else{
                    $this->returnCode('20130022');
                }
            }else{
                $this->returnCode('20130021');
            }
        }
    }
    //	团购积分、短信、打印
    private function group_notice($order,$verify_all){
        $now_user = M('User')->where(array('uid' => $order['uid']))->find();
        if($verify_all) {
            //积分
            if($this->config['add_score_by_system_commission']==0 && $this->config['add_score_by_percent']==0 && ($this->config['open_score_discount']==0 || $order['score_discount_type']!=2) && ($this->config['score_get_times']==0 || !isset($this->config['score_get_times']))){
    			if($order['is_own'] && $this->config['user_own_pay_get_score']!=1 ){
    				$order['payment_money'] = 0;
    			}
                $this->config['score_get'] = D('Percent_rate')->scorePercent('group', $order['group_id'], $order['mer_id']);
                if( $this->config['score_get']>0) {
                    $this->config['score_get'] = D('Percent_rate')->get_user_add_score_times($this->config['score_get'], $order['uid']);

                    D('User')->add_score(
                        $order['uid'],
                        (($order['payment_money'] + $order['balance_pay']) * $this->config['score_get']),
                        L_('购买 X1 消费X2 获得X3', array('X1' => $order['order_name'], 'X2' => floatval($order['total_money']) . C('config.Currency_txt'), 'X3' => $this->config['score_name'])), 0,
                        [
                            'order_id' => $order['order_id'],
                            'order_type' => 'group',
                            'mer_id' => $order['mer_id'],
                        ]
                    );
                }
    			D('Scroll_msg')->add_msg(
                    'group',
                    $now_user['uid'],
                    L_('用户X1于X2购买X3成功并消费获得',array('X1'=>str_replace_name($now_user['nickname']),'X2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),'X3'=>$order['order_name'],'X4'=>$this->config['score_name']))
                );
            }
			D('Userinfo')->add_score(
                $order['uid'], 
                $order['mer_id'], 
                $order['total_money'], 
                L_('购买 X1 消费X2 获得X3',array('X1'=>$order['order_name'],'X2'=>floatval($order['total_money']) . C('config.Currency_txt'),'X3'=>$this->config['score_name']))
            );

			//商家推广分佣
			D('Merchant_spread')->add_spread_list($order, $now_user, 'group', L_('X1购买X2获得佣金',array('X1'=>$now_user['nickname'],'X2'=>C('config.group_alias_name'))));
		}

        if($now_user['openid']){

            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
            $href = C('config.site_url').'/wap.php?c=My&a=group_order&order_id='.$order['order_id'];
            $model->sendTempMsg('TM00017', array('href' => $href,
                'wecha_id' => $now_user['openid'],
                'first' => $order['order_name']?:$order['real_orderid'],
                'OrderSn' => L_("核销成功"),
                'OrderStatus' =>C('config.group_alias_name'),
                'remark' =>L_('点击查看详情')),
                $this->store['mer_id']);
        }

    	//短信
    	$sms_data = array('mer_id' => $order['mer_id'], 'store_id' => $this->store['store_id'], 'type' => 'group');
    	if ($this->config['sms_finish_order'] == 1 || $this->config['sms_finish_order'] == 3) {
    		$sms_data['uid'] = $order['uid'];
    		$sms_data['mobile'] = $order['phone'];
    		$sms_data['sendto'] = 'user';
            if(empty($order['res'])){
                $sms_data['content'] = L_('您购买 X1的订单(订单号：X2)已经完成了消费，如有任何疑意，请您及时联系本店，欢迎再次光临！',array('X1'=>$order['order_name'],'X2'=>$order['real_orderid']));
            }else{
                $sms_data['content'] = L_('您购买 X1的订单(消费码：X2)已经完成了消费，如有任何疑意，请您及时联系我们！',array('X1'=>$order['order_name'],'X2'=>$order['res']['group_pass']));
            }
    		Sms::sendSms($sms_data);
    	}
        D('Group_order')->group_app_notice($order['order_id'],7);

    	if ($this->config['sms_finish_order'] == 2 || $this->config['sms_finish_order'] == 3) {
    		$sms_data['uid'] = 0;
    		$sms_data['mobile'] = $this->store['phone'];
    		$sms_data['sendto'] = 'merchant';
    		$sms_data['content'] = L_('顾客购买的X1的订单(订单号：X2),已经完成了消费！',array('X1'=>$order['order_name'],'X2'=>$order['real_orderid']));
    		Sms::sendSms($sms_data);
    	}
    	//小票打印
    	$printHaddle = new PrintHaddle();
    	$printHaddle->printit($order['order_id'], 'group_order', 2);
    }
    // 团购详情
    public function group_edit()
    {
        $this->check_group();
        $order_id = $_POST['order_id'];
        $param_arr = explode('_',$order_id);
        if($param_arr&&count($param_arr)==5){
            $order_id = $param_arr[0];
        }
		if($_POST['from'] == 'scan'){
			$now_order = D('Group_order')->get_order_detail_by_longid_and_merId($this->store['mer_id'], $order_id, false);
		}else{
			$now_order = D('Group_order')->get_order_detail_by_id_and_merId($this->store['mer_id'], $order_id, false);
		}
        $store_id = $this->store['store_id'];

        //获取商品所属店铺
        $group_store = D('Group_store')->field('store_id')->where(['group_id'=>$now_order['group_id']])->select();
        $group_store_ids = array_column($group_store,'store_id');

        if ($now_order['store_id'] && !in_array($store_id,$group_store_ids)) {
            $result_arr=array();
            $result_arr['errorCode']=1;
            $result_arr['errorMsg']=L_('此订单不是当前店铺的订单,不能核销！');
            echo json_encode($result_arr);exit;
        }

        if (empty($now_order)) {
            $this->returnCode('20130006');
        }
        if (! empty($now_order['paid'])) {
            if ($now_order['is_pick_in_store']) {
                $pickType = [
                    0 => '快递配送',
                    1 => '到店自提',
                    2 => '商家配送'
                ];
                $now_order['paytypestr'] = L_($pickType[$now_order['is_pick_in_store']] ?? '未知');
            } else {
                $now_order['paytypestr'] = D('Pay')->get_pay_name($now_order['pay_type']);
            }
            if (($now_order['pay_type'] == 'offline') && ! empty($now_order['third_id']) && ($now_order['paid'] == 1)) {
                $paytypestr = L_('已支付');
            } elseif (($now_order['pay_type'] != 'offline') && ($now_order['paid'] == 1)) {
                $paytypestr = L_('已支付');
            } else {
                $paytypestr = L_('未支付');
            }
        } else {
            $now_order['paytypestr'] = L_('未支付');
        }
        if($now_order['is_group_combine'] == 1){
            $order_type = L_('优惠组合');
        }elseif ($now_order['tuan_type'] == 0) {
			$order_type = L_('X1券', $this->config['group_alias_name']);
        } elseif ($now_order['tuan_type'] == 1) {
            $order_type = L_('代金券');
        } else {
            $order_type = L_('实物');
        }
        $status_format = $this->status_format($now_order['status'], $now_order['paid'], $now_order['third_id'],$now_order['pay_type'], $now_order['tuan_type'],$now_order['express_id'],$now_order['is_pick_in_store']);
        if ($now_order['status'] > 0 && $now_order['status'] < 3) {
            if ($now_order['tuan_type'] != 2) {
                $operation = L_('消费');
            } else {
                $operation = L_('发货');
            }
        }
        $group_image_class = new group_image();
        $all_pic = $group_image_class->get_allImage_by_path($now_order['pic']);

        $pin_info = D('Group_start')->get_group_start_by_order_id($now_order['order_id']);
        if ($pin_info) {
            $group_start_status  = $pin_info['status'];
        }else{
            $group_start_status  = 1;
        }

        // 退款详情
        if($now_order['refund_detail'] && unserialize($now_order['refund_detail'])){
            $now_order['refund_detail'] = unserialize($now_order['refund_detail']);
            if ($now_order['refund_detail']['msg'] && $now_order['refund_detail']['error']) {
                $now_order['refund_detail']['msg'] = "错误码：".$now_order['refund_detail']['error'] . ', '. $now_order['refund_detail']['msg'];
            }
        }elseif($now_order['refund_detail'] && !$now_order['refund_detail']['refund_id']){
            $now_order['refund_detail']['msg'] = str_replace('certificate not match','微信支付证书不匹配',$now_order['refund_detail']);
        }elseif($now_order['refund_detail'] && $now_order['refund_detail']['msg']){
            $now_order['refund_detail']['msg'] = str_replace('certificate not match','微信支付证书不匹配',$now_order['refund_detail']['msg']);
        }elseif($now_order['refund_detail'] && $now_order['refund_detail']['err_msg']){
            $now_order['refund_detail']['err_msg'] = str_replace('certificate not match','微信支付证书不匹配',$now_order['refund_detail']['err_msg']);
        }

        $now_order['refund_detail']['refund_time'] = $now_order['refund_detail']['refund_time'] ? date('Y-m-d H:i:s',$now_order['refund_detail']['refund_time']) : '';
        if ($now_order['refund_detail']) {
            if(is_array($now_order['refund_detail'])){
                $refund_detail = $now_order['refund_detail'];
            }else{
                $refund_detail = ['err_msg' => $now_order['refund_detail']];
                $now_order['refund_detail'] = ['err_msg' => $now_order['refund_detail'], 'msg' => $now_order['refund_detail']];
            }
        } else {
            $refund_detail = array();
        }
        
        // 团购优惠组合
        $combineList = D('Group_combine_activity')->where(['combine_id'=> $now_order['combine_id'] ])->find();
        if($combineList){
            $now_order['s_name'] .= '【'.$combineList['title'].'】';
        }

        if($now_order['group_cate'] == 'cashing'){
            $pic = thumb_img(C('config.site_url').'/static/images/group/cashing.png',200,150);
        }elseif($now_order['group_cate'] == 'booking_appoint'){
            $pic = thumb_img(C('config.site_url').'/static/images/group/booking_appoint.png',200,150);
        }else{
            $pic = $all_pic[0]['s_image'];
        }
        $internalCardPayMoney = 0;
        $internalCardPay = @json_decode($now_order['internal_card_pay'], true);
        if(is_array($internalCardPay)){
            $internalCardPayMoney = array_sum($internalCardPay);
        }
        $arr['now_order'] = array(
            's_name' => $now_order['s_name'], // 团购名
            'pic' => $pic, // 团购名
            'order_id' => $now_order['order_id'], // 订单ID
            'real_orderid' => $now_order['real_orderid'], // 订单ID
            'group_id' => $now_order['group_id'], // 团购ID
            'status_s' => $now_order['status'], // 状态
            'is_pick_in_store' => $now_order['is_pick_in_store'], // 状态
            'order_type' => $order_type, // 订单类型
            'status' => $status_format['status'], // 订单状态
            'type' => $status_format['type'], // 订单状态
            'pass_array' => isset($now_order['pass_array']) ? $now_order['pass_array'] : '', // 操作
            'group_pass' => $now_order['group_pass'],
            'num' => (int) $now_order['num'], // 数量
            'price' => $now_order['price'], // 单价
            'add_time' => date('Y-m-d H:i', $now_order['add_time']), // 下单时间
            'pay_time' => date('Y-m-d H:i:s', $now_order['pay_time']), // 付款时间
            'operation' => isset($operation) ? $operation : '', // 消费 发货
            'use_time' => date('Y-m-d H:i:s', $now_order['use_time']), // 消费 发货 时间
            'last_staff' => $now_order['last_staff'], // 操作店员
            'paystatus' => isset($paytypestr) ? $paytypestr : '', // 已支付 未支付
            'paytypestr' => $now_order['paytypestr'], // 货到付款 未支付
            'delivery_comment' => $now_order['delivery_comment'], // 备注
            'total_money' => $now_order['total_money'], // 总金额
            'tuan_type' => $now_order['tuan_type'],
            'card_discount' => $now_order['card_discount'] == 10 ? 0 : $now_order['card_discount'],
            'card_price' => $now_order['card_price'],
            'coupon_price' => $now_order['coupon_price'],
            'score_deducte' => $now_order['score_deducte'],
            'use_ecard_price' => $now_order['use_ecard_price'], //平台E卡
            'group_start_status' => $group_start_status,
            'refund_detail' => $refund_detail,
            'verify_time' => $now_order['verify_time']?date('Y-m-d H:i:s',$now_order['verify_time']):'',
            'internal_card_pay_money' => $internalCardPayMoney,
        );

        $arr['pin_info']= $pin_info?$pin_info:'';
        if ($paytypestr == L_('未支付') || $now_order['paytypestr'] == L_('未支付')) {
            $arr['now_order']['pay_time'] = L_('未支付');
        }
        if ($now_order['third_id'] == 0 && $now_order['pay_type'] == 'offline') {
            $arr['now_order']['total_moneys'] = $now_order['total_money']; // 总金额
            $arr['now_order']['balance_pay'] = $now_order['balance_pay']; // 平台余额支付
            $arr['now_order']['merchant_balance'] = $now_order['merchant_balance']+$now_order['card_give_money']; // 商家会员卡余额支付
            if ($now_order['wx_cheap'] != '0.00') {
                $arr['now_order']['wx_cheap'] = $now_order['wx_cheap']; // 微信优惠
            } else {
                $arr['now_order']['wx_cheap'] = 0;
            }
            $arr['now_order']['payment_money'] = 0; // 在线支付金额
            $arr['now_order']['payment'] = $now_order['total_money'] - $now_order['wx_cheap'] - $now_order['merchant_balance'] - $now_order['balance_pay'] - $now_order['score_deducte'] - $now_order['coupon_price']; // 线下需向商家付金额 红色字体
        } else {
            $arr['now_order']['total_moneys'] = 0; // 总金额
            $arr['now_order']['balance_pay'] = $now_order['balance_pay']; // 平台余额支付
            $arr['now_order']['merchant_balance'] = $now_order['merchant_balance']+$now_order['card_give_money']; // 商家会员卡余额支付
            $arr['now_order']['wx_cheap'] = 0; // 微信优惠
            $arr['now_order']['payment_money'] = $now_order['payment_money']; // 在线支付金额
            $arr['now_order']['payment'] = 0;
        }
        if ($now_order['is_pick_in_store'] == 1) {
            $phone = $now_order['user_phone'];
        } else {
            $phone = $now_order['phone'];
        }


        $arr['user'] = array(
            'uid' => $now_order['uid'], // 用户ID
            'nickname' => $now_order['nickname'], // 用户名
            'phone' => $phone, // 订单手机号 自提就取用户手机号
            'user_phone' => $now_order['user_phone']
        );
        $arr['distribution'] = array(
            'contact_name' => $now_order['contact_name'], // 联系名
            'phone' => $now_order['phone'], // 联系电话
            'zipcode' => $now_order['zipcode'], // 邮编
            'adress' => $now_order['adress'], // 地址
            'express_id' => $now_order['express_id'], // 快递单号
            'express_type' => $now_order['express_type'], // 快递公司
            'merchant_remark' => $now_order['merchant_remark']
        );
        switch ($now_order['delivery_type']) {
            case 1:
                $arr['distribution']['delivery_type'] = L_('工作日、双休日与假日均可送货');
                break;
            case 2:
                $arr['distribution']['delivery_type'] = L_('只工作日送货');
                break;
            case 3:
                $arr['distribution']['delivery_type'] = L_('只双休日、假日送货');
                break;
            case 4:
                $arr['distribution']['delivery_type'] = L_('白天没人，其它时间送货');
                break;
        }
        $express_list = D('Express')->get_express_list();
        if ($express_list) {
            foreach ($express_list as &$v) {
                if ($v['id'] == $now_order['express_type']) {
                    $arr['distribution']['express_name'] = $v['name'];
                }
                $v['ids'] = $v['id'];
                unset($v['code'], $v['url'], $v['sort'], $v['add_time'], $v['status'], $v['id']);
            }
            if (empty($arr['distribution']['express_name'])) {
                $arr['distribution']['express_name'] = $express_list[0]['name'];
                $arr['distribution']['express_type'] = $express_list[0]['ids'];
            }
        } else {
            $express_list = array();
        }
        $arr['trade_hotel_info'] = '';
        if ($now_order['trade_info']) {
            $trade_info_arr = unserialize($now_order['trade_info']);
            if ($trade_info_arr['type'] == 'hotel') {
                $arr['trade_hotel_info'] = D('Trade_hotel_category')->format_order_trade_info($now_order['trade_info']);
                $refund_detail = is_array($now_order['refund_detail'])?$now_order['refund_detail']:unserialize($now_order['refund_detail']);
                $arr['trade_hotel_info'] ['order_id'] = $now_order['order_id'];
                $arr['trade_hotel_info'] ['paid'] = $now_order['paid'];
                $arr['trade_hotel_info'] ['use_time'] = $now_order['use_time'] == 0 ? 0 : date('Y-m-d H:i:s', $now_order['use_time']); // 消费 发货 时间;
                $arr['trade_hotel_info'] ['book_day'] = (strtotime( $arr['trade_hotel_info']['end_time']) - strtotime( $arr['trade_hotel_info']['dep_time']))/86400 ;
                if(!isset($refund_detail['score_count'])){
                    $refund_detail['score_count'] = 0;
                }
                if(!isset($refund_detail['merchant_balance'])){
                    $refund_detail['merchant_balance'] = 0;
                }
                if(!isset($refund_detail['card_give_money'])){
                    $refund_detail['card_give_money'] = 0;
                }
                $refund_detail['merchant_balance'] = $refund_detail['merchant_balance']+$refund_detail['card_give_money'];
                if(!isset($refund_detail['balance_pay'])){
                    $refund_detail['balance_pay'] = 0;
                }
                if(!isset($refund_detail['payment_money'])){
                    $refund_detail['payment_money'] = 0;
                }

                $arr['trade_hotel_info']['refund_detail'] = $refund_detail;

                foreach ($arr['trade_hotel_info']['price_list_txt'] as &$v) {
                    $v['day2'] = date('m/d',strtotime($v['day_key']));
                    $v['can_refund_day'] = $arr['trade_hotel_info']['num']-$refund_detail['detail'][$v['day_key']]['num'];
                }
            }
        }
        $arr['express_list'] = $express_list;

        // 获取填写的用户自定义字段信息

        $write_info = unserialize($now_order['write_field']);
        if ($write_info) {
            $write_list = array();
            foreach($write_info as $key=>$val){
                if(!empty($write_info[$key]['value'])){
                    $write_list[$key]['name'] = $val['name'];
                    $write_list[$key]['value'] = $val['value'];
                    $write_list[$key]['type'] = $val['type'];
                    if($write_info[$key]['type'] == 2){
                        $write_list[$key]['long'] = $val['long'];
                        $write_list[$key]['lat'] = $val['lat'];
                        $write_list[$key]['address'] = $val['address'];
                        $write_list[$key]['value'] = $val['value'].' '.$val['value-desc'];
                    }
                }
            }
            $arr['write_list'] = $write_list;
        }
        // 如果存在 获取对应规格
        if ($now_order['specifications_id']) {
            $specifications_info = M('Group_specifications')->where(array('specifications_id' => $now_order['specifications_id']))->find();
            if ($specifications_info) {
                $arr['now_order']['specifications_info'] = $specifications_info;
                $arr['now_order']['s_name'] = $now_order['s_name'] . '('.$specifications_info['specifications_name'].')';
            } else {
                $arr['now_order']['specifications_info'] = array();
            }
        }
        $arr['ecard_alias_name']=$this->config['ecard_alias_name'];
        
        //获取核销码
        $pass_array = D('Group_pass_relation')->get_pass_array($now_order['order_id']);
        foreach ($pass_array as &$v){
            $v['verify_time'] = !empty($v['verify_time']) ? date('Y-m-d H:i:s',$v['verify_time'] ) : 0;
        }
        $arr['now_order']['pass_array_arr'] = $pass_array?:[];
        $this->returnCode(0, $arr);
    }
    //	团购填写快递信息
    public function group_express()
    {
        $this->check_group();
        $now_order = D('Group_order')->get_order_detail_by_id_and_merId($this->store['mer_id'], $_POST['order_id'], false);
        if (empty($now_order)) {
            $this->returnCode('20130006');
        }
        if (empty($now_order['paid'])) {
            $this->returnCode('20130019');
        }
        // if($now_order['status']!=0){
        // $this->returnCode('20130038');
        // }
        if(!$_POST['express_id']){
            $this->returnCode('1003',[],'请输入快递号！');
        }
        
        $condition_group_order['order_id'] = $now_order['order_id'];
        $data_group_order['express_type'] = $_POST['express_type'];
        $data_group_order['express_id'] = $_POST['express_id'];
        $data_group_order['last_staff'] = $this->staff_session['name'];
        $data_group_order['last_time'] = time();
        $data_group_order['send_time'] = $_SERVER['REQUEST_TIME'];
        if ($now_order['paid'] == 1 && $now_order['status'] == 0) {
            $data_group_order['status'] = 0;
            $data_group_order['use_time'] = $_SERVER['REQUEST_TIME'];
            $data_group_order['store_id'] = $this->store['store_id'];
        }
        if (D('Group_order')->where($condition_group_order)->data($data_group_order)->save()) {
           // if ($now_order['status'] != 1) {
                //$this->group_notice($now_order, 1);
            //}
            $now_user = D('User')->get_user($now_order['uid'],'uid');
            $express_name = D('Express')->get_express($_POST['express_type']);
            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
            $href = C('config.site_url').'/wap.php?c=My&a=group_order&order_id='.$now_order['order_id'];
            $model->sendTempMsg('TM00017', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' =>L_('X1快递发货通知',$this->config['group_alias_name']), 'OrderSn' => $now_order['real_orderid'], 'OrderStatus' =>L_('X1已为您发货',$this->staff_session['name']), 'remark' =>L_('快递号：X1(X2),请尽快确认',array('X1'=>$_POST['express_id'],'X2'=>$express_name['name']))), $this->store['mer_id']);
            D('Group_order')->group_app_notice($now_order['order_id'],6);
            /*-----记录系统订单信息 店员已发过 改写状态已发货 19/1/18----*/
            D("System_order")->save_order_status('group',$now_order['order_id'],1);
            if(!$now_order['send_time']){
                //增加消息通知
                $url = '\app\common\model\service\user\UserNoticeService/addNotice';
                $params['type'] = 0;
                $params['business'] = 'group';
                $params['order_id'] = $now_order['order_id'];
                $params['mer_id'] = $now_order['mer_id'];
                $params['store_id'] = $now_order['store_id'];
                $params['uid'] = $now_order['uid'];
                $params['title'] = '商品已发货';
                $params['content'] = '您的商品已经发货';
                invoke_v20_service($url, [$params]);
            }
            $this->returnCode(0);
        } else {
            $this->returnCode('20130020');
        }
    }
    //	团购额外信息
    public function group_remark()
    {
        $this->check_group();
        $now_order = D('Group_order')->get_order_detail_by_id_and_merId($this->store['mer_id'], $_POST['order_id'], true, false);
        if (empty($now_order)) {
            $this->returnCode('20130006');
        }
        if (empty($now_order['paid'])) {
            $this->returnCode('20130019');
        }
        $condition_group_order['order_id'] = $now_order['order_id'];
        $data_group_order['merchant_remark'] = $_POST['merchant_remark'];
        $data_group_order['last_time'] = time();
        if (D('Group_order')->where($condition_group_order)->data($data_group_order)->save()) {
            $this->returnCode(0);
        } else {
            $this->returnCode('20130020');
        }
    }
    //	团购多消费券
    public function group_pass_array(){
        $this->check_group();
        $order_id = $_POST['order_id'];
        $param_arr = explode('_',$order_id);
        if($param_arr&&count($param_arr)==5){
            $order_id = $param_arr[0];
            $now_order = D('Group_order')->get_order_detail_by_longid_and_merId($this->store['mer_id'], $order_id, false);
        }else{
            $now_order = D('Group_order')->get_order_detail_by_id_and_merId($this->store['mer_id'], $order_id, false);
        }
        $now_order['coupon_price'] = D('Group_order')->get_coupon_info($now_order['order_id']);
        $un_pay =$now_order['total_money']-$now_order['wx_cheap']-$now_order['merchant_balance']-$now_order['balance_pay']-$now_order['score_deducte']-$now_order['coupon_price'];
        $has_pay = $now_order['total_money']-$un_pay;
        $pass_array = D('Group_pass_relation')->get_pass_array($now_order['order_id']);
        foreach($pass_array as &$v){
            $v['need_pay'] = 100;
//            $v['need_pay'] = $has_pay>$now_order['price']?0:$now_order['price']-$has_pay;
            $has_pay=($has_pay-$now_order['price'])>0?$has_pay-$now_order['price']:0;
            unset($v['id']);
        }
        $arr['pass_array']  =   isset($pass_array)?$pass_array:array();
        $un_consume_num  = D('Group_pass_relation')->get_pass_num($now_order['order_id'],0);
        if($now_order){
            $arr['now_order']   =   array(
                'status'    =>  $now_order['status'],
                'num'    =>  $now_order['num'],
                'un_consume_num'    =>  $un_consume_num,
            );
        }else{
			$arr['now_order']	=	array();
        }
        $this->returnCode(0,$arr);
    }
    //	客户自提
    public function group_pick(){
	    $this->check_group();
	    $now_order = D('Group_order')->get_order_detail_by_id_and_merId($this->store['mer_id'],$_POST['order_id'],false);
	    if(empty($now_order)){
	        $this->returnCode('20130006');
	    }
		if(empty($now_order['paid'])){
			$this->returnCode('20130019');
		}
		if($now_order['status']!=0){
			$this->returnCode('20130038');
		}
        if($now_order['is_head'] || $now_order['pin_fid']>0){
            $group_start = D('Group_start')->get_group_start_by_order_id($now_order['order_id']);
            $group_start_status  = $group_start['status'];
            if ($group_start && $group_start_status == 0) {
                $this->returnCode('20130049');
            } elseif ($group_start && $group_start_status == 2) {
                $this->returnCode('20130050');
            }
        }

	    $condition_group_order['order_id'] = $now_order['order_id'];
	    $date['status']=1;
	    $date['paid'] = 1;
	    $date['last_staff'] = $this->staff_session['name'];
	    $date['use_time'] = $_SERVER['REQUEST_TIME'];
	    if (empty($now_order['third_id']) && $now_order['pay_type'] == 'offline') {
	        $date['third_id'] = $now_order['order_id'];
	    }
	    if(D('Group_order')->where($condition_group_order)->data($date)->save()){
	        $this->group_notice($now_order,1);
            /*-----记录系统订单信息 自提完成 改写已完成 19/1/18----*/
            D("System_order")->save_reg_order_info('group',$now_order['order_id'],array(),2);
	        $this->returnCode(0);
	    }else{
	        $this->returnCode('20130020');
	    }
    }
    //	餐饮列表
    public function meal_list(){
        $this->check_meal();
        $store_id = intval($this->store['store_id']);
        $order_id	=	$_POST['order_id'];
        $where = array('mer_id' => $this->store['mer_id'], 'store_id' => $store_id);
		$stauts=isset($_POST['st']) ? intval(trim($_POST['st'])) :false;
        $ftype = isset($_POST['ft']) ? trim($_POST['ft']) : '';
        $fvalue = isset($_POST['fv']) ? trim(htmlspecialchars($_POST['fv'])) : '';
        $page	=	1;
		if(empty($ftype) && ($stauts==1)){
			$where['paid']=1; $where['status']=0;$ftype='st';
		}else{
	        switch ($ftype) {
	            case 'oid': //订单id
	                $fvalue && $where['order_id'] = array('like', "%$fvalue%");
	                break;
	            case 'xm':  //下单人姓名
	                $fvalue && $where['name'] = array('like', "%$fvalue%");
	                break;
	            case 'dh':  //下单人电话
	                $fvalue && $where['phone'] = array('like', "%$fvalue%");
	                break;
	            case 'mps': //消费码
	                $fvalue && $where['meal_pass'] = array('like', "%$fvalue%");
	                break;
	            default:
	                break;
	        }
        }
        $Meal_orderDb = D("Meal_order");
        $count = $Meal_orderDb->where($where)->count();
        if($order_id && $ftype != 'oid'){
			$where['order_id']	=	array('lt',$order_id);
        }else if($_POST['page']){
			$page	=	$_POST['page'];
        }
        import('@.ORG.wap_group_page');
        $notOffline = 1;
        $pay_offline_open = $this->config['pay_offline_open'];
        if ($pay_offline_open == 1) {
        	$now_merchant = D('Merchant')->get_info($this->store['mer_id']);
        	if ($now_merchant) {
        		$notOffline =($now_merchant['is_close_offline'] == 0 && $now_merchant['is_offline'] == 1) ? 0 : 1;
        	}
        }

        $list = $Meal_orderDb->where($where)->order("order_id DESC")->page($page,10)->select();
        foreach ($list as $k=>&$l) {
            if ($notOffline && $l['paid'] == 0) $l['is_confirm'] = 2;
            $order_list[$k]	=	array(
				'order_id'	=>	$l['order_id'],
				'name'	=>	$l['name'],
				'phone'	=>	$l['phone'],
				'price'	=>	$l['price'],
				'dateline'	=>	date('Y-m-d H:i:s',$l['dateline']),
				'address'	=>	$l['address'],
				'type'	=>	0,
				'clerk_status'	=>	0,
            );
            if($l['status'] == 3){
				$order_list[$k]['status']	=	1;	//已取消并退款	红色字体
            }else if(empty($l['third_id']) && ($l['pay_type'] == 'offline')){
				$order_list[$k]['status']	=	2;	//线下未支付	红色字体
            }else{
				if($l['paid'] == 0){
					$order_list[$k]['status']	=	3;	//未支付	红色字体
				}else{
					$order_list[$k]['status']	=	4;	//已支付	绿色字体
				}
				if($l['status'] == 0){
					$order_list[$k]['type']	=	1;	//未消费
				}else if($l['status'] == 1){
					$order_list[$k]['type']	=	2;	//已消费
				}else if($l['status'] == 2){
					$order_list[$k]['type']	=	3;	//已消费并且已评价
				}
            }
            if($l['status'] > 2){
				$order_list[$k]['clerk_status']	=	1;	//订单已取消	红色字体
            }else if($l['is_confirm'] == 1){
				$order_list[$k]['clerk_status']	=	2;	//已接单	绿色字体
            }else if($l['is_confirm'] == 0){
				$order_list[$k]['clerk_status']	=	3;	//接单	绿色字体
            }else{
				$order_list[$k]['clerk_status']	=	4;	//未支付，不能接单	红色字体
            }
        }
		$arr['order_list']	=	isset($order_list)?$order_list:array();
		$arr['page']		=	ceil($count/10);
		$arr['count']		=	$count;
		if(empty($stauts) && empty($ftype) && empty($fvalue)){
			$arr['status'] 		= 	1;
		}else{
			$arr['status'] 		= 	1;
		}
		$this->returnCode(0,$arr);
    }
    /* 检查是否开启餐饮 */
    protected function check_meal()
    {
        if (empty($this->store['have_meal'])) {
            $this->returnCode('20130009');
        }
        $store_id = intval($this->store['store_id']);
        $foodshop = M('Merchant_store_foodshop')->field('store_id')->where(array('store_id' => $store_id))->find();
        if (empty($foodshop)) {
            $this->returnCode('20130028');
        }
    }
    //	餐饮新单统计数量
	public function meal_count()
	{
		$this->check_meal();
		$time =	I('time', time());
		$where = array('mer_id' => $this->store['mer_id'], 'store_id' => $this->store['store_id'], 'pay_time' => array('gt', $time));
		$count = M('Foodshop_order')->where($where)->count();
// 		$data_store_staff['device_id'] = $this->DEVICE_ID;
// 		$data_store_staff['last_time'] = $_SERVER['REQUEST_TIME'];
// 		$data_store_staff['client'] = I('client');
// 		if($data_store_staff['client'] ==2){
// 			$save=M('Merchant_store_staff')->where($where)->data($data_store_staff)->save();
// 		}
		if ($count < 0) {
			$this->returnCode('20130027');
		}
		$this->returnCode(0, array('count' => $count, 'time' => time()));
	}

    // 通知上菜提醒
    public function foodshop_call_staff_count()
    {
        $time = I('time', time());
        $condition_where = "`o`.`table_id`=`t`.`id` AND `o`.`mer_id`=".$this->store['mer_id'].' AND `o`.`store_id`='. $this->store['store_id'].' AND `o`.`running_call_status` = 1 AND `o`.`running_time` >'. $time;
        $condition_table = array(C('DB_PREFIX').'foodshop_order'=>'o',C('DB_PREFIX').'foodshop_table'=>'t');
        $list = D('')->table($condition_table)->field('`t`.`name`,`o`.`order_id`')->where($condition_where)->select();

        $order_ids = array();
        foreach ($list as &$value) {
            $order_ids[] = $value['order_id'];
            $msg = L_('【X1】的客人上菜提醒，请及时确认菜品！',$value['name']);
            //$voice_return = json_decode($this->voice_baidu(), true);
            //$voice_access_token = $voice_return['access_token'];
            //$voice_mp3 = 'http://tsn.baidu.com/text2audio?vol=15&tex=' . urlencode($msg) . '&lan=zh&tok=' . $voice_access_token . '&ctp=1&cuid=9B9A62EE-3EE8-45E0-9C06-2E3245AE3FF5&.mp3';
            $voice_mp3 = text2audio($msg);
            $value['msg'] = $msg;
            $value['voice_mp3'] = $voice_mp3;
        }
        if ($order_ids) {
            D('Foodshop_order')->data(array('running_call_status'=>0))->where(array('order_id'=>array('in',$order_ids)))->save();

        }
        $this->returnCode(0,array('count' => count($list), 'time' => $time, 'list'=>$list));
    }

    //	餐饮详情
    public function meal_edit(){
    	$this->check_meal();
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $store_id = intval($this->store['store_id']);
        $order = D('Meal_order')->where(array('mer_id' => $this->store['mer_id'], 'order_id' => $order_id, 'store_id' => $store_id))->find();

		$order['info'] = unserialize($order['info']);
		if ($order['store_uid']) {
			$staff = M('Merchant_store_staff')->where(array('id' => $order['store_uid']))->find();
			$order['store_uname'] = $staff['name'];
		}
		if (empty($order['third_id']) && $order['pay_type'] == 'offline') {
			$order['paid'] = 0;
		}
		if(!empty($order['paid'])){
			$order['paytypestr'] = D('Pay')->get_pay_name($order['pay_type']);
			if(($order['pay_type']=='offline') && !empty($order['third_id']) && ($order['paid']==1)){
				$paytypestr	=	L_(L_('已支付'));
			}else if(($order['pay_type']!='offline') && ($order['paid']==1)){
				$paytypestr	=	'已支付';
			}else{
				$paytypestr	=	L_(L_('未支付'));
			}
		}else{
			$order['paytypestr'] = '未支付';
		}
		$arr['order_details']	=	array(
			'order_id'	=>	$order['order_id'],
			'name'	=>	$order['name'],
			'phone'	=>	$order['phone'],
			'price'	=>	$order['price'],
			'dateline'	=>	date('Y-m-d H:i:s',$order['dateline']),
			'address'	=>	$order['address'],
			'note'	=>	$order['note'],
			'paytypestr'	=>	$order['paytypestr'],
			'paystatus'	=>	isset($paytypestr)?$paytypestr:'',	//已支付	未支付
			'status'	=>	$order['status'],
			'clerk_status'	=>	0,
			'meal_type'	=>	$order['meal_type'],
		);
		if($order['status'] > 2){
			$arr['order_details']['clerk_status']	=	1;	//订单已取消	红色字体
		}else if($order['is_confirm'] == 1){
			$arr['order_details']['clerk_status']	=	2;	//已接单	绿色字体
		}else if($order['is_confirm'] == 0){
			$arr['order_details']['clerk_status']	=	3;	//接单	绿色字体
		}else{
			$arr['order_details']['clerk_status']	=	4;	//未支付，不能接单	红色字体
		}
		if($order['status'] == 3){
			$arr['order_details']['order_status']	=	L_('已取消并退款');
		}else if(empty($order['third_id']) && ($order['pay_type'] == 'offline')){
			$arr['order_details']['order_status']	=	L_('线下未支付');
		}else if($order['paid'] == 0){
			$arr['order_details']['order_status']	=	L_('未付款');
		}else{
			$arr['order_details']['order_status']	=	L_('已付款');
		}
		if($order['third_id'] == '0' && $order['pay_type'] == 'offline'){
			$arr['order_details']['total_price']	=	$order['total_price'];
			$arr['order_details']['balance_pay']	=	$order['balance_pay'];
			$arr['order_details']['merchant_balance']	=	$order['merchant_balance'];
			$arr['order_details']['payment']	=	$order['total_price']-$order['merchant_balance']-$order['balance_pay'];
			$arr['order_details']['payment_money']	=	0;
		}else{
			$arr['order_details']['total_price']	=	0;
			$arr['order_details']['balance_pay']	=	$order['balance_pay'];
			$arr['order_details']['merchant_balance']	=	$order['merchant_balance'];
			$arr['order_details']['payment']	=	0;
			$arr['order_details']['payment_money']	=	$order['payment_money'];
		}
		if(!empty($now_order['use_time'])){
			if($order['tuan_type'] != 2){
				$arr['order_details']['ptime']	=	L_('消费');
			}else{
				$arr['order_details']['ptime']	=	L_('发货');
			}
			$arr['order_details']['use_time']	=	date('Y-m-d H:i:s',$now_order['use_time']);
			$arr['order_details']['last_staff']	=	$now_order['last_staff'];
		}else{
			$arr['order_details']['ptime']	=	'';
			$arr['order_details']['use_time']	=	'';
			$arr['order_details']['last_staff']	=	'';
		}
		if($order['status'] == 0){
			$arr['order_details']['notes']	=	L_('注：改成已消费状态后同时如果是未付款状态则修改成线下支付已支付，状态修改后就不能修改了');
		}else{
			$arr['order_details']['notes']	=	'';
		}
		$arr['info']	=	$order['info'];

		foreach($arr['info'] as &$v){
			$v['total']	=	$v['price']*$v['num'];
		}

    	if(empty($arr['info'])){
			$arr['info'] = array();
    	}
        $this->returnCode(0,$arr);
    }
    //	餐饮确定消费
    public function meal_consumption(){
    	$this->check_meal();
    	$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $store_id = intval($this->store['store_id']);
        $status = intval($_POST['status']);
        if(empty($status)){
			$this->returnCode('20130010');
        }
        $order = D("Meal_order")->where(array('mer_id' => $this->store['mer_id'], 'order_id' => $order_id, 'store_id' => $store_id))->find();
        if ($order) {
			if ($order['status'] > 2) {
				$this->returnCode('20130011');
			}
            $data = array('store_uid' => $this->staff_session['id'], 'status' => $status,'use_time'=>time(),'last_staff'=>$this->staff_session['name']);
			if(empty($order['third_id']) && $order['pay_type'] == 'offline'){
				$order['paid'] = 0;
			}
	        if ($order['paid'] == 0) {
	            $notOffline = 1;
	            if ($this->config['pay_offline_open'] == 1) {
	                $now_merchant = D('Merchant')->get_info($order['mer_id']);
	                if ($now_merchant) {
	                	$notOffline =($now_merchant['is_close_offline'] == 0 && $now_merchant['is_offline'] == 1) ? 0 : 1;
	                }
	            }
	            if ($notOffline) {
					$this->returnCode('20130012');
	            }
	        }
            if ($order['paid'] != 1 || ($order['pay_type'] == 'offline' && empty($order['third_id']))) {//将未支付的订单，由店员改成已消费，其订单状态则修改成线下已支付！
                $data['third_id'] = $order['order_id'];
                $data['pay_type'] = 'offline';
                $data['paid'] = 1;
                $data['pay_time'] = $_SERVER['REQUEST_TIME'];
            }
			$data['use_time'] = $_SERVER['REQUEST_TIME'];
			if (D("Meal_order")->where(array('mer_id' => $this->store['mer_id'], 'order_id' => $order_id, 'store_id' => $store_id))->save($data)) {
				if ($status && $order['status'] == 0) {
					if ($supply = D('Deliver_supply')->field(true)->where(array('order_id' => $order_id, 'item' => 0))->find()) {
						if ($supply['status'] < 2) {
							D('Deliver_supply')->where(array('order_id' => $order_id, 'item' => 0))->delete();
						} else {
							D('Deliver_supply')->where(array('order_id' => $order_id, 'item' => 0))->save(array('status' => 5));
						}
					}
					$this->meal_notice($order);
				}
				$this->returnCode(0);
			} else {
				$this->returnCode('20130013');
			}
        } else {
			$this->returnCode('20130014');
        }
    }
    //	餐饮积分、短信、打印
    private function meal_notice($order){

		//验证增加商家余额
		$order['order_type']='meal';
		$info = unserialize($order['info']);
		$info_str = '';
		foreach($info as $v){
			$info_str.=$v['name'].':'.$v['price'].'*'.$v['num'].'</br>';
		}
		//D('Merchant_money_list')->add_money($this->store['mer_id'],'用户购买'.$info_str.'记入收入',$order);
        $order['desc']=L_('用户购买X1记入收入',$info_str);
        D('SystemBill')->bill_method($order['is_own'],$order);

        //商家推广分佣
        $now_user = M('User')->where(array('uid' => $order['uid']))->find();
        D('Merchant_spread')->add_spread_list($order, $now_user, 'meal', $now_user['nickname'] . L_('用户购买餐饮商品获得佣金'));

    	//积分
        if($this->config['add_score_by_system_commission']==0 && $this->config['add_score_by_percent']==0 && ($this->config['open_score_discount']==0 || $order['score_discount_type']!=2) && ($this->config['score_get_times']==0 || !isset($this->config['score_get_times']))){
    		if($order['is_own'] && $this->config['user_own_pay_get_score']!=1) {
    			$order['payment_money'] = 0;
    		}
            $this->config['score_get'] = D('Percent_rate')->get_user_add_score_times($this->config['score_get'],$order['uid']);
    		if ($this->config['score_get']>0 && ($order['payment_money'] + $order['balance_pay'])>0) {
                D('User')->add_score($order['uid'], (($order['payment_money'] + $order['balance_pay']) *$this->config['score_get']), L_('在 X1 中消费X2 获得X3',array('X1'=>$this->store['name'],'X2'=>floatval($order['price']) . C('config.Currency_txt'),'X3'=>$this->config['score_name'])));
                D('Scroll_msg')->add_msg('meal',$now_user['uid'],L_('用户X1于X2在X3 中消费获得X4',array('X1'=>str_replace_name($now_user['nickname']),'X2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),'X3'=>$this->store['name'],'X4'=>$this->config['score_name'])));
            }
        }
		D('Userinfo')->add_score($order['uid'], $order['mer_id'], $order['price'], L_('在 X1 中消费X2获得积分',array('X1'=>$this->store['name'],'X2'=> floatval($order['price']) . C('config.Currency_txt'))));
		//短信
		$sms_data = array('mer_id' => $this->store['mer_id'], 'store_id' => $this->store['store_id'], 'type' => 'food');
		if ($this->config['sms_finish_order'] == 1 || $this->config['sms_finish_order'] == 3) {
			if (empty($order['phone'])) {
				$user = D('User')->field(true)->where(array('uid' => $order['uid']))->find();
				$order['phone'] = $user['phone'];
			}
			$sms_data['uid'] = $order['uid'];
			$sms_data['mobile'] = $order['phone'];
			$sms_data['sendto'] = 'user';
			$sms_data['content'] = L_('您在 X1店中下的订单(订单号：X2),已经完成了消费，如有任何疑意，请您及时联系本店，欢迎再次光临！',array('X1'=>$this->store['name'],'X2'=>$order['order_id']));
			Sms::sendSms($sms_data);
		}
		if ($this->config['sms_finish_order'] == 2 || $this->config['sms_finish_order'] == 3) {
			$sms_data['uid'] = 0;
			$sms_data['mobile'] = $this->store['phone'];
			$sms_data['sendto'] = 'merchant';
			$sms_data['content'] = L_('顾客购买的X1的订单(订单号：X2),已经完成了消费！',array('X1'=>$order['name'],'X2'=>$order['order_id']));
			Sms::sendSms($sms_data);
		}
		//小票打印
		$printHaddle = new PrintHaddle();
		$printHaddle->printit($order['order_id'], 'meal_order', 2);
    }
    //	餐饮接单
    public function check_confirm(){
    	$this->check_meal();
		$database = D('Meal_order');
		$order_id = $condition['order_id'] = intval($_POST['order_id']);
		$condition['store_id'] = $this->store['store_id'];
		$order = $database->field(true)->where($condition)->find();
		if(empty($order)){
			$this->returnCode('20130006');
		}
		if ($order['paid'] == 0){
			$this->returnCode('20130016');
		}
		if ($order['status'] > 2){
			$this->returnCode('20130015');
		}
		$notOffline = 1;
		$pay_offline_open = $this->config['pay_offline_open'];
		if ($pay_offline_open == 1) {
			$now_merchant = D('Merchant')->get_info($this->store['mer_id']);
			if ($now_merchant) {
				$notOffline =($now_merchant['is_close_offline'] == 0 && $now_merchant['is_offline'] == 1) ? 0 : 1;
			}
		}
		if ($notOffline && $order['paid'] == 0) {
			$this->returnCode('20130016');
		}

		if ($order['meal_type'] == 1) {
			$deliverCondition['store_id'] = $this->store['store_id'];
			$deliverCondition['mer_id'] = $this->store['mer_id'];
// 			if ($deliver = D('Deliver_store')->where($deliverCondition)->find()) {
    			$old = D('Deliver_supply')->field(true)->where(array('order_id' => $order_id, 'item' => 0))->find();
    			if (empty($old)) {
					$deliverType = $deliver['type'];
					$address_id = $order['address_id'];
					$address_info = D('User_adress')->where(array('adress_id' => $address_id))->find();

					$supply['order_id'] = $order_id;
					$supply['paid'] = $order['paid'];
					$supply['pay_type'] = $order['pay_type'];
					$supply['money'] = $order['price'];
					$supply['deliver_cash'] = floatval($order['price'] - $order['card_price']-$order['merchant_balance']-$order['balance_pay']-$order['payment_money']-$order['score_deducte']-$order['coupon_price']);
					$supply['deliver_cash'] = max(0, $supply['deliver_cash']);
					$supply['store_id'] = $this->store['store_id'];
					$supply['store_name'] = $this->store['name'];
					$supply['mer_id'] = $this->store['mer_id'];
					$supply['from_site'] = $this->store['adress'];
					$supply['from_lnt'] = $this->store['long'];
					$supply['from_lat'] = $this->store['lat'];
					if ($address_info) {
						$supply['aim_site'] =  $address_info['adress'].' '.$address_info['detail'];
						$supply['aim_lnt'] = $address_info['longitude'];
						$supply['aim_lat'] = $address_info['latitude'];
						$supply['name']  = $address_info['name'];
						$supply['phone'] = $address_info['phone'];
					}
					$supply['status'] =  1;
					$supply['type'] = $deliverType;
					$supply['item'] = 0;
					$supply['create_time'] = $_SERVER['REQUEST_TIME'];
					$supply['start_time'] = $_SERVER['REQUEST_TIME'];
					$supply['appoint_time'] = $_SERVER['REQUEST_TIME'];
					if ($addResult = D('Deliver_supply')->add($supply)) {
					} else {
						$this->returnCode('20130017');
					}
    			}
// 			} else {
// 				$this->returnCode('20130018');
// 			}
		}

		$data['is_confirm'] = 1;
		$data['order_status'] = 3;
		$data['store_uid'] = $this->staff_session['id'];
		$data['last_staff'] = $this->staff_session['name'];
		if ($database->where($condition)->save($data)) {
			$this->returnCode(0);
		} else {
			$this->returnCode('20130019');
		}
	}
    //	快店列表
    public function shop_list(){
    	$this->check_shop();
        $store_id = intval($this->store['store_id']);
        $where = array('mer_id' => $this->store['mer_id'], 'store_id' => $store_id);
		$stauts = isset($_POST['st']) ? intval(trim($_POST['st'])) :false;
        $ftype = isset($_POST['ft']) ? trim($_POST['ft']) : '';
        $fvalue = isset($_POST['fv']) ? trim(htmlspecialchars($_POST['fv'])) : '';
        $order_id	=	$_POST['order_id'];
		if (empty($ftype) && ($stauts == 1)) {
			$where['paid'] = 1;
			$where['status'] = 0;
			$ftype = 'st';
		}else{
	        switch ($ftype) {
	            case 'oid': //订单id
	                $fvalue && $where['real_orderid'] = array('like', "%$fvalue%");
	                break;
	            case 'xm':  //下单人姓名
	                $fvalue && $where['username'] = array('like', "%$fvalue%");
	                break;
	            case 'dh':  //下单人电话
	                $fvalue && $where['phone'] = array('like', "%$fvalue%");
	                break;
	            case 'mps': //消费码
	                $fvalue && $where['orderid'] = $fvalue;
	                break;
	            default:
	                break;
	        }
        }
        if(empty($where['paid'])){
        	$where['paid'] = 1;
        }
        $count = M('Shop_order')->where($where)->count();
        if($order_id){
        	$where['order_id']	=	array('lt',$order_id);
        }
        $where['mer_id'] = $this->store['mer_id'];
        $where['store_id'] = $store_id;
        $shop_order = D('Shop_order')->get_order_list($where, 'pay_time DESC', 10, false);
        if($shop_order['order_list']){
			foreach($shop_order['order_list'] as $k=>$v){
				if($v['pay_type_str'] == L_('未支付')){
						$pay_status	=	'';
		        }else{
					if(empty($v['third_id']) && ($v['pay_type'] == 'offline')){
						$pay_status	=	L_('线下未支付');
			        }else if($v['paid'] == 0){
						$pay_status	=	L_('未支付');
			        }else{
						$pay_status	=	L_('已支付');
			        }
		        }
		        $arr['shop_order'][$k]	=	array(
					'order_id'	=>	$v['order_id'],
					'username'	=>	$v['username'],
					'userphone'	=>	$v['userphone'],
					'address'	=>	$v['address'],
					'create_time'	=>	date('Y-m-d H:i:s',$v['create_time']),
					'price'	=>	$v['price'],
					'pay_type_str'	=>	$v['pay_type_str'],
					'status'	=>	$v['status'],
					'paid'		=>	$v['paid'],
					'pay_status'	=>	$pay_status,
		        );
			}
        }else{
			$arr['shop_order']	=	array();
        }
        $arr['page']	=	ceil($count/10);;
        $arr['count']	=	$count;
        if(empty($stauts) && empty($ftype) && empty($fvalue)){
			$arr['status'] 		= 	1;
        }else{
			$arr['status'] 		= 	2;
        }
        $this->returnCode(0,$arr);
    }
    /* 检查是否开启餐饮 */
    protected function check_shop(){
        if (empty($this->store['have_shop'])) {
			$this->returnCode('20130025');
        }
        $store_id = intval($this->store['store_id']);
        $meal	=	M('Merchant_store_shop')->field('store_id')->where(array('store_id'=>$store_id))->find();
        if(empty($meal)){
			$this->returnCode('20130028');
        }
    }
    //	快店新单统计数量
	public function shop_count()
	{
		$this->check_shop();
		$time = I('time', time());

		$where = array('mer_id' => $this->store['mer_id'], 'store_id' => $this->store['store_id'], 'pay_time' => array('gt', $time));
		$count = M('Shop_order')->where($where)->count();
// 		$data_store_staff['device_id'] = $this->DEVICE_ID;
// 		$data_store_staff['last_time'] = $_SERVER['REQUEST_TIME'];
// 		$data_store_staff['client'] = I('client');
// 		if($data_store_staff['client'] ==2){
// 			$save=M('Merchant_store_staff')->where($where)->data($data_store_staff)->save();
// 		}
		if ($count < 0) {
			$this->returnCode('20130027');
		}
		$this->returnCode(0, array('count' => $count, 'time' => time()));
	}
    //	快店接单
    public function shop_order_confirm(){
    	$this->check_shop();
    	$database = D('Shop_order');
    	$order_id = $condition['order_id'] = intval($_POST['order_id']);
    	$condition['store_id'] = $this->store['store_id'];
    	$order = $database->field(true)->where($condition)->find();
    	if(empty($order)){
    		$this->returnCode('20130006');
    	}
    	if ($order['status'] > 0){
			$this->returnCode('20130015');
    	}
		if ($order['paid'] == 0){
			$this->returnCode('20130016');
		}
		if ($order['is_refund']) {
			$this->returnCode(1, null, '用户正在退款中~！');
			exit;
		}

		$data['status'] = 1;
		$data['order_status'] = 1;
		$data['last_staff'] = $this->staff_session['name'];
		$data['last_time'] = time();
		
		$condition['status'] = 0;
		
		if ($order['platform'] == 1) {//eleme
		    $eleme = D('Eleme_shop')->getElemeObj($order['store_id']);
		    if ($eleme != false) {
		        $result = $eleme->getDataByApi("eleme.order.confirmOrderLite", array('orderId' => $order['platform_id']));
		        if ($result['error']) {
		            $this->returnCode(1, null, L_('饿了么返回的错误') . $result['error']['code'] . $result['error']['message']);
		            exit;
		        } else {
		            $database->where($condition)->save($data);
		            if ($order['is_pick_in_store'] == 5) {
		                $result = D('Deliver_supply')->saveOrder($order_id, $this->store);
		                if ($result['error_code']) {
		                    D("Shop_order")->where(array('mer_id' => $this->store['mer_id'], 'order_id' => $order_id, 'store_id' => $this->store['store_id']))->save(array('status' => 0, 'last_time' => time()));
		                    $this->error($result['msg']);
		                    exit;
		                }
		                D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 2, 'name' => $this->staff_session['name'], 'phone' => $phones[0]));
		            }
		            $this->returnCode(0);
		            exit;
		        }
		    } else {
		        $this->returnCode(1, null, L_('店铺对接饿了么有问题，请联系商家处理！'));
		        exit;
		    }
		} elseif ($order['platform'] == 2) {//meituan
		    $store = M('Merchant_store')->field(true)->where(array('store_id' => $order['store_id']))->find();
		    if (empty($store) || empty($store['meituan_token'])) {
		        $this->returnCode(1, null, L_('店铺对接美团有问题，请联系商家处理！'));
		        exit;
		    }
		    import('@.ORG.Meituan');
		    $meituan = new Meituan($store['meituan_token']);
		    $result = $meituan->getDataByApi('waimai/order/confirm', array('orderId' => $order['platform_id']));
		    if ($result['error']) {
		        $this->returnCode(1, null, L_('美团返回的错误：') . $result['msg']);
		        exit;
		    } else {
		        $database->where($condition)->save($data);
		        if ($order['is_pick_in_store'] == 5) {
		            $result = D('Deliver_supply')->saveOrder($order_id, $this->store);
		            if ($result['error_code']) {
		                D("Shop_order")->where(array('mer_id' => $this->store['mer_id'], 'order_id' => $order_id, 'store_id' => $this->store['store_id']))->save(array('status' => 0, 'last_time' => time()));
		                $this->error($result['msg']);
		                exit;
		            }
		            D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 2, 'name' => $this->staff_session['name'], 'phone' => $phones[0]));
		        }
		        $this->returnCode(0);
		        exit;
		    }
		}
    	if ($database->where($condition)->data($data)->save()) {
			if ($order['is_pick_in_store'] != 2 && $order['is_pick_in_store'] != 3) {
			    $result = D('Deliver_supply')->saveOrder($order_id, $this->store);
			    if ($result['error_code']) {
			        D('Shop_order')->where(array('order_id' => $order_id))->save(array('status' => 0, 'order_status' => 0, 'last_time' => time()));
			        $this->returnCode(1, null, $result['msg']);
			    }
			}
			D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 2, 'name' => $this->staff_session['name'], 'phone' => $this->store['phone']));
    		$this->returnCode(0);
    	} else {
    		$this->returnCode('20130017');
    	}
    }
    //	快店详情
	public function shop_edit(){
		$this->check_shop();
    	$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    	$store_id = intval($this->store['store_id']);
    	$order = D("Shop_order")->get_order_detail(array('mer_id' => $this->store['mer_id'], 'order_id' => $order_id, 'store_id' => $store_id));
//     	$User_adress	=	M('User_adress')->field(array('longitude','latitude'))->where(array('adress_id'=>$order['address_id']))->find();
    	if($order){
    		if($order['pay_type'] == 'offline' && empty($order['third_id'])){
//				$payment	=	floatval($order['price']-$order['card_price']-$order['merchant_balance']-$order['balance_pay']-$order['payment_money']-$order['score_deducte']-floatval($order['coupon_price']));
				$payment	=	rtrim(rtrim(number_format($order['price']-$order['card_price']-$order['merchant_balance']-$order['card_give_money']-$order['balance_pay']-$order['payment_money']-$order['score_deducte']-floatval($order['coupon_price']),2,'.',''),'0'),'.');
    		}
    		$discount_price = floatval(round($order['discount_price'] + $order['freight_charge'] + $order['packing_charge'] - $order['merchant_reduce'] - $order['balance_reduce'], 2));
    		$order['card_discount'] = $order['card_discount'] == 0 ? 10 : $order['card_discount'];
			$arr['order_details']	=	array(
				'orderid'	=>	$order['orderid'],
				'order_id'	=>	$order['order_id'],
				'real_orderid'	=>	$order['real_orderid'],
				'username'	=>	$order['username'],
				'userphone'	=>	$order['userphone'],
				'create_time'	=>	date('Y-m-d H:i:s',$order['create_time']),
				'pay_time'	=>	date('Y-m-d H:i:s',$order['pay_time']),
				'expect_use_time'	=>	$order['expect_use_time']!=0 ? (date('Y-m-d H:i',$order['expect_use_time'])) : L_('尽快'),
				'is_pick_in_store'	=>	$order['is_pick_in_store'],
				'address'	=>	$order['address'],
				'deliver_str'	=>	$order['deliver_str'],
				'deliver_status_str'	=>	$order['deliver_status_str'],
				'note'	=>	isset($order['desc'])?$order['desc']:'',
				'invoice_head'	=>	$order['invoice_head'],
				'pay_status'	=>	$order['pay_status_print'],
				'pay_type_str'	=>	$order['pay_type_str'],
				'status_str'	=>	$order['status_str'],
				'score_used_count'	=>	$order['score_used_count'],//抵用的积分
				'score_deducte'	=>	strval(floatval($order['score_deducte'])),//积分兑现的金额
				'card_give_money'	=>	strval(floatval($order['card_give_money'])),//会员卡赠送余额
				'merchant_balance'	=>	strval(floatval($order['merchant_balance'])),//商家余额
				'balance_pay'	=>	strval(floatval($order['balance_pay'])),//平台余额
				'payment_money'	=>	strval(floatval($order['payment_money'])),//在线支付的金额
				'change_price'	=>	strval(floatval($order['change_price'])),//店员修改前的原始价格（如果是0表示没有修改过，可不显示）
				'change_price_reason'	=>	$order['change_price_reason'],//店员修改价格的理由
				'card_id'	=>	$order['card_id'],
				'card_price'	=>	strval(floatval($order['card_price'])),//商家优惠券的金额
				'coupon_price'	=>	strval(floatval($order['coupon_price'])),//平台优惠券的金额
				'payment'	=>	isset($payment)?$payment:0,
				'use_time'	=>	$order['use_time']!=0 ? (date('Y-m-d H:i:s',$order['use_time'])) : '0',
				'last_staff'	=>	$order['last_staff'],
				'status'	=>	$order['status'],
				'paid'	=>	$order['paid'],
				'register_phone'	=>	$order['register_phone'],//注册时的用户手机号
				'lat'	=>	$order['lat'],
				'lng'	=>	$order['lng'],
				'cue_field'	=> empty($order['cue_field']) ? array() : $order['cue_field'],//商家自定义字段值（如果没有的话是空 即：''）
				'card_discount'	=>	$order['card_discount'],//会员卡折扣
				'goods_price'	=>	strval(floatval($order['goods_price'])),//商品的总价
				'freight_charge'	=>	strval(floatval($order['freight_charge'])),//配送费
				'packing_charge'	=>	strval(floatval($order['packing_charge'])),//打包费
				'total_price'	=>	strval(floatval($order['total_price'])),//订单总价
				'merchant_reduce'	=>	strval(floatval($order['merchant_reduce'])),//商家优惠的金额
				'balance_reduce'	=>	strval(floatval($order['balance_reduce'])),//平台优惠的金额
				'price'	=>	strval(floatval($order['price'])),//实际支付金额
				'discount_price'	=>	strval($discount_price),//折扣与优惠后的总价
				'minus_price'	=>	strval(floatval(round($order['total_price'] - $discount_price, 2))),//折扣与优惠的优惠金额
				'minus_card_discount'	=>	strval($discount_price * (1 - $order['card_discount'] * 0.1)),//折扣与优惠的优惠金额
				'notes'	=>	L_('注：改成已消费状态后同时如果是未付款状态则修改成线下支付已支付，状态修改后就不能修改了'),
			);
			foreach($order['info'] as $k=>$v){
			    $discount_price = floatval($v['discount_price']) > 0 ? floatval($v['discount_price']) : floatval($v['price']);
				$arr['info'][]	=	array(
					'name'	=>	$v['name'],
					'price'	=>	strval(floatval($v['price'])),
					'discount_price'	=>	strval($discount_price),
					'spec'	=>	empty($v['spec']) ? '' : $v['spec'],
					'num'	=>	$v['num'],
					'total'	=>	strval(floatval($v['price'] * $v['num'])),
					'discount_total'	=>	strval(floatval($discount_price * $v['num'])),
				);
			}
			$arr['discount_detail'] = $order['discount_detail'] ?: '';
        } else {
            $arr['order_details'] = array();
        }
        if (empty($arr['info'])) {
            $arr['info'] = array();
        }
        $this->returnCode(0, $arr);
    }

    //	快店确定消费
    public function shop_consumption(){
    	$this->check_shop();
		$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    	$store_id = intval($this->store['store_id']);
    	$status = intval($_POST['status']);
    	if(empty($status)){
			$this->returnCode('20130010');
        }
    	if ($order = D("Shop_order")->where(array('mer_id' => $this->store['mer_id'], 'order_id' => $order_id, 'store_id' => $store_id))->find()) {
    		if ($order['status'] > 3) {
    			$this->returnCode('20130011');
    		}
    		$data = array('status' => $status, 'order_status' => 6, 'use_time' => time(), 'last_staff' => $this->staff_session['name']);
    		if(empty($order['third_id']) && $order['pay_type'] == 'offline'){
    			$order['paid'] = 0;
    		}
    		if ($order['paid'] == 0) {
    			$notOffline = 1;
    			if ($this->config['pay_offline_open'] == 1) {
    				$now_merchant = D('Merchant')->get_info($order['mer_id']);
    				if ($now_merchant) {
    					$notOffline =($now_merchant['is_close_offline'] == 0 && $now_merchant['is_offline'] == 1) ? 0 : 1;
    				}
    			}
    			if ($notOffline) {
    				$this->returnCode('20130012');
    			}
    		}
    		if ($order['paid'] != 1 || ($order['pay_type'] == 'offline' && empty($order['third_id']))) {//将未支付的订单，由店员改成已消费，其订单状态则修改成线下已支付！
    			$data['third_id'] = $order['order_id'];
    			$data['pay_type'] = 'offline';
    			$data['paid'] = 1;
    		}
    		$data['cancel_type'] = 2;////取消类型（0:pc店员，1:wap店员，2:andriod店员,3:ios店员，4：打包app店员，5：用户，6：配送员, 7:超时取消）
    		
    		$condition = array('mer_id' => $this->store['mer_id'], 'order_id' => $order_id, 'store_id' => $store_id);
    		$database = D('Shop_order');
    		if ($order['platform'] == 1) {//eleme
    		    $eleme= D('Eleme_shop')->getElemeObj($order['store_id']);
    		    if ($eleme != false) {
    		        $result = $eleme->getDataByApi("eleme.order.receivedOrderLite", array('orderId' => $order['platform_id']));
    		        if ($result['error']) {
    		            $this->returnCode(1, null, L_('饿了么返回的错误') . $result['error']['code'] . $result['error']['message']);
    		            exit;
    		        } else {
    		            $data['last_time'] = time();
    		            $database->where($condition)->save($data);
    		            $this->returnCode(0);
    		            exit();
    		        }
    		    } else {
    		        $this->returnCode(1, null, L_('店铺对接饿了么有问题，请联系商家处理！'));
    		        exit;
    		    }
    		} elseif ($order['platform'] == 2) {//meituan
    		    $this->returnCode(1, null, L_('美团不支持确认消费'));
    		    exit;
    		}
    		
    		
    		if (D("Shop_order")->where(array('mer_id' => $this->store['mer_id'], 'order_id' => $order_id, 'store_id' => $store_id))->save($data)) {
    			if ($status == 2 && $order['status'] < 2) {
    				D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 7, 'name' => $this->staff_session['name'], 'phone' => $this->store['phone']));
    				if ($supply = D('Deliver_supply')->field(true)->where(array('order_id' => $order_id, 'item' => 2))->find()) {
    					if ($supply['status'] < 2) {
    						D('Deliver_supply')->where(array('order_id' => $order_id, 'item' => 2))->delete();
    					} else {
    						D('Deliver_supply')->where(array('order_id' => $order_id, 'item' => 2))->save(array('status' => 5));
    					}
    				}
    				$this->shop_notice($order);
    			}
    			$this->returnCode(0);
    		} else {
    			$this->returnCode('20130013');
    		}
    	} else {
			$this->returnCode('20130014');
    	}
    }

    /**
     * V20调用消费完成订单金额计算
     * @author: 张涛
     * @date: 2020/11/13
     */
    public function shop_notice_v20($order, $is_staff = false)
    {
        fdump_sql([$order, $is_staff ],'v20_shop_notice');
        if ($order['store_id']) {
            $this->store = M('Merchant_store')->where(['store_id' => $order['store_id']])->find();
        }
        if(isset($this->config['store_score_get_percent'])){
            if($this->config['store_score_get_percent']==1){
                $this->config['score_get'] = $this->config['store_score_percent']/100;
            }else{
                $this->config['score_get'] =  $this->config['user_store_score_get'];
            }
        }
        if ($order['mer_id']) {
            $this->now_merchant = D('Merchant')->get_info($order['mer_id']);
            if ($this->now_merchant['score_get_percent'] >= 0) {
                $this->config['score_get'] = $this->now_merchant['score_get_percent'] / 100;
            }
        }
        $this->shop_notice($order, $is_staff);
    }

    //	快店积分、短信、打印
    private function shop_notice($order, $is_staff = false){
        $order['order_type']='shop';

        if ($is_staff) {
            if($order['card_id']){
                $use_result = D('Card_new_coupon')->user_coupon($order['card_id'], $order['order_id'], 'shop', $order['mer_id'], $order['uid']);
                if($use_result['error_code']){
                    return array('error' => 1, 'msg' => $use_result['msg']);
                }
            }

            if($order['coupon_id']){
                $use_result = D('System_coupon')->user_coupon($order['coupon_id'], $order['order_id'], 'shop', $order['mer_id'], $order['uid']);
                if($use_result['error_code']){
                    return array('error' => 1, 'msg' => $use_result['msg']);
                }
            }

            // 使用积分
            if ($order['score_used_count'] > 0) {
                $use_result = D('User')->user_score($order['uid'], $order['score_used_count'], L_('购买 X1 扣除X2' ,array('X1'=>$order['name'],'X2'=>C('config.score_name'))));
                if ($use_result['error_code']) {
                    return array('error' => 1, 'msg' => $use_result['msg']);
                }
            }

            if(floatval($order['merchant_balance']) > 0){
                $use_result = D('Card_new')->use_money($order['uid'], $order['mer_id'], $order['merchant_balance'], L_('购买 X1 扣除会员卡余额', $order['real_orderid']));
                if($use_result['error_code']){
                    return array('error'=>1,'msg'=>$use_result['msg']);
                }
            }

            if(floatval($order['card_give_money']) > 0){
                $use_result = D('Card_new')->use_give_money($order['uid'], $order['mer_id'], $order['card_give_money'], L_('购买 X1 扣除会员卡赠送余额',$order['real_orderid']));
                if ($use_result['error_code']) {
                    return array('error' => 1, 'msg' => $use_result['msg']);
                }
            }

            $goods = D('Shop_order_detail')->field(true)->where(array('order_id' => $order['order_id']))->select();
            $goods_obj = D("Shop_goods");
            foreach ($goods as $gr) {
                $goods_obj->update_stock($gr);//修改库存
            }

            $order['system_status'] = 2;
            D('System_order')->add_order('shop',$order,$order['order_id']);
            
            $order['order_type'] = 'shop_offline';
        }
        //验证增加商家余额
        if ($order['union_mer_id']) {
            $pre_desc = L_('商家会员卡联盟----');
        }
		if($order['order_from']=='1'){
			$alias_name = $this->config['mall_alias_name'];
		}else{
			$alias_name = $this->config['shop_alias_name'];
		}
        $order['desc']=$pre_desc.L_('用户购买X1订单记入收入',['X1'=>$alias_name]);
        D('SystemBill')->bill_method($order['is_own'],$order);
		//商家推广分佣
		$now_user = M('User')->where(array('uid' => $order['uid']))->find();
		D('Merchant_spread')->add_spread_list($order, $now_user, 'shop', $now_user['nickname'] . L_('用户购买X1商品获得佣金',['X1'=>$this->config['shop_alias_name']]));

    	//积分
        if($this->config['add_score_by_system_commission']==0 && $this->config['add_score_by_percent']==0 && ($this->config['open_score_discount']==0 || $order['score_discount_type']!=2)){
    		if($order['is_own'] && $this->config['user_own_pay_get_score']!=1){
    			$order['payment_money'] = 0;
    		}
            if(C('config.open_score_get_percent')==1){
                $score_percent = C('config.score_get_percent')/100;
            }else{
                $score_percent = C('config.user_score_get');
            }

            if($order['order_from']==1){
                $score_get = D('Percent_rate')->shop_get_score($order);
            } elseif ($this->config['shop_goods_score_edit'] == 1){
                $score_get = D('Percent_rate')->shop_get_score($order);
                $score_get = D('Percent_rate')->get_user_add_score_times($score_get,$order['uid']);
            }else{
                $now_merchant = D('Merchant')->get_info($order['mer_id']);
                if($now_merchant['score_get_percent']>=0){
                    $score_percent = $now_merchant['score_get_percent']/100;
                }
                $score_percent = D('Percent_rate')->get_user_add_score_times($score_percent,$order['uid']);
                $score_get  = round(($order['payment_money'] + $order['balance_pay']) *$score_percent, 2);
            }

            if ($score_get>0) {
                // 保存订单信息
                $param['order_type'] = 'shop';
                $param['order_id'] = $order['order_id'];
                D('User')->add_score($order['uid'], $score_get, L_('在 X1 中消费X2 获得X3', array('X1' => $this->store['name'], 'X2' => C('config.Currency_symbol') . floatval($order['price']), 'X3' => $this->config['score_name'])),0,$param);

                // D('User')->add_score($order['uid'], round(($order['payment_money'] + $order['balance_pay']) * $this->config['score_get']), '在 ' . $this->store['name'] . ' 中消费' . floatval($order['price']) . C('config.Currency_txt').' 获得'.$this->config['score_name']);
                D('Scroll_msg')->add_msg($order['order_type'], $now_user['uid'], L_('用户X1于X2在X3中消费获得X4', array('X1' => str_replace_name($now_user['nickname']), 'X2' => date('Y-m-d H:i', $_SERVER['REQUEST_TIME']), 'X3' => $this->store['name'], 'X4' => $this->config['score_name'])));
            }
        }
		D('Userinfo')->add_score($order['uid'], $order['mer_id'], $order['price'], L_('在 X1 中消费X2 获得积分',array('X1'=>$this->store['name'],'X2'=>floatval($order['price']) . C('config.Currency_txt'))));

		//短信
		$sms_data = array('mer_id' => $this->store['mer_id'], 'store_id' => $this->store['store_id'], 'type' => 'shop');
		if ($this->config['sms_finish_order'] == 1 || $this->config['sms_finish_order'] == 3) {
			if (empty($order['phone'])) {
				$user = D('User')->field(true)->where(array('uid' => $order['uid']))->find();
				$order['phone'] = $user['phone'];
			}
			$sms_data['uid'] = $order['uid'];
			$sms_data['mobile'] = $order['userphone'];
			$sms_data['sendto'] = 'user';
			$sms_data['content'] = L_('您在 X1店中下的订单(订单号：X2),已经完成了消费，如有任何疑意，请您及时联系本店，欢迎再次光临！',array('X1'=>$this->store['name'],'X2'=>$order['real_orderid']));
			Sms::sendSms($sms_data);
		}
		if ($this->config['sms_finish_order'] == 2 || $this->config['sms_finish_order'] == 3) {
			$sms_data['uid'] = 0;
			$sms_data['mobile'] = $this->store['phone'];
			$sms_data['sendto'] = 'merchant';
			$sms_data['content'] = L_('顾客购买的X1的订单(订单号：X2),已经完成了消费！',array('X1'=>$order['name'],'X2'=> $order['real_orderid']));
			Sms::sendSms($sms_data);
		}

        //微信小程序订阅消息提醒 
        $wxapp_message_send = false;
        if($now_user['wxapp_openid'] && $order['order_from']!=1){
            $param['page'] = '/pages/shop_new/orderDetail/orderDetail?order_id='.$order['order_id'];
            $param['wxapp_openid'] = $now_user['wxapp_openid'];
            $param['type'] = 'order_status_change';
            // 获得商品信息
            $goods_name = D('Shop_order_detail')->where(['order_id'=>$order['order_id']])->order('id ASC')->find()['name'];
            $goodsCount = D('Shop_order_detail')->field('sum(num) as total')->where(['order_id'=>$order['order_id']])->find()['total'];
            $order['goods_desc'] = $goodsCount > 1 ? L_('X1等X2个商品',['X1'=>$goods_name,'X2'=>$goodsCount]) : $goods_name;

            $order['status_str'] = L_('已送达');
            $order['note'] = L_('您的订单已送达，请及时关注~');
            $param['data_info'] = $order;
            $wxapp_message_send = D('Wxapp_template')->sendWxappMessage($param);
        }
        

		//小票打印
		$printHaddle = new PrintHaddle();
		$printType = $is_staff == true ? 1 : 2;
		$printHaddle->printit($order['order_id'], 'shop_order', $printType);
    }
    //	预约列表
	public function appoint_list() {
        $store_id = $this->store['store_id'];
		$database_order = D('Appoint_order');
    	$database_user = D('User');
    	$database_appoint = D('Appoint');
    	$database_store = D('Merchant_store');
    	$order_id	=	$_POST['order_id'];
    	$where['store_id'] = $store_id;
    	$count = $database_order->field(true)->where($where)->count();
    	if($order_id){
			$where['order_id']	=	array('lt',$order_id);
    	}
    	$order_info = $database_order->field(true)->where($where)->page(1,10)->order('`order_id` DESC')->select();
        $uidArr = array();
        foreach($order_info as $v){
        	array_push($uidArr,$v['uid']);
        }
        $uidArr = array_unique($uidArr);
    	$user_info = $database_user->field('`uid`, `phone`, `nickname`')->where(array('uid'=>array('in',$uidArr)))->select();
    	$appoint_info = $database_appoint->field('`appoint_id`, `appoint_name`, `appoint_type`, `appoint_price`')->select();
    	$store_info = $database_store->field('`store_id`, `name`, `adress`')->select();
    	$order_list = $this->formatOrderArray($order_info, $user_info, $appoint_info, $store_info);
    	if($order_list){
    		foreach($order_list as $v){
    			$truename	=	'';
				if($v['truename']){
					$truename	=	$v['truename'];
    			}else{
					$truename	=	$v['nickname'];
    			}
				$arr['order_list'][]	=	array(
					'order_id'	=>	$v['order_id'],
					'appoint_name'	=>	$v['appoint_name'],
					'truename'	=>	$truename,
					'phone'	=>	$v['phone'],
					'appoint_date'	=>	$v['appoint_date'].' '.$v['appoint_time'],
					'payment_money'	=>	floatval($v['payment_money']),
					'appoint_price'	=>	floatval($v['appoint_price']),
					'paid'			=>	$v['paid'],
					'service_status'=>	$v['service_status'],
				);
    		}
    	}else{
			$arr['order_list']	=	array();
    	}
    	$arr['count']	=	$count;
    	$arr['page'] = ceil($count/10);
    	$arr['status']	=	1;
    	$this->returnCode(0,$arr);
    }

    /*更新预约面议价格*/
    public function change_appoint_order_price(){
        $order_id = $_POST['order_id'];
        $price = $_POST['price'];
        $database_order = D('Appoint_order');
        $where['order_id'] = $order_id;
        $where['mer_id'] = $this->store['mer_id'];

        if($this->staff_session['is_change']==0){
            // 店员没有修改价格的权限
            $this->returnCode('20130055');
        }

        if($price<0){
            // 金额错误
            $this->returnCode('20130051');
        }

        $now_order = $database_order->where($where)->find();
        if(!$now_order){//订单不存在
            $this->returnCode('20130006');
        }
        if($now_order['service_status']!=0){
            // 订单状态不是未服务状态
            $this->returnCode('20130052');
        }
        if($now_order['is_initiative']>0){
            // 订单尾款已支付
            $this->returnCode('20130053');
        }

        if ($now_order['product_id']>0) {
            $update = $database_order->where($where)->setField('product_price',$price);
        }else{
            $update = $database_order->where($where)->setField('appoint_price',$price);
        }

        if($update){
            $this->returnCode('0');
        }else{
            $this->returnCode('20130054');
        }
    }

    /* 预约相关 */
    protected function check_appoint(){
        if (empty($this->config['appoint_page_row'])) {
			$this->returnCode('20130026');
        }
    }
    //	预约新单统计数量
	public function appoint_count()
	{
		$this->check_appoint();
		$time = I('time', time());
		$where = array('store_id' => $this->store['store_id']);
        $where['_string'] = 'pay_time>'.$time.' OR merchant_assign_time >'.$time;
		$count = M('Appoint_order')->where($where)->count();
// 		$data_store_staff['device_id'] = $this->DEVICE_ID;
// 		$data_store_staff['last_time'] = $_SERVER['REQUEST_TIME'];
// 		$data_store_staff['client'] = I('client');
// 		if($data_store_staff['client'] ==2){
// 			$save=M('Merchant_store_staff')->where($where)->data($data_store_staff)->save();
// 		}
		if ($count < 0) {
			$this->returnCode('20130027');
		}
		$this->returnCode(0, array('count' => $count, 'time' => time()));
	}
    /* 格式化订单数据  */
    protected function formatOrderArray($order_info, $user_info, $appoint_info, $store_info){
    	if(!empty($user_info)){
    		$user_array = array();
    		foreach($user_info as $val){
    			$user_array[$val['uid']]['phone'] = $val['phone'];
    			$user_array[$val['uid']]['nickname'] = $val['nickname'];
    		}
    	}
    	if(!empty($appoint_info)){
    		$appoint_array = array();
    		foreach($appoint_info as $val){
    			$appoint_array[$val['appoint_id']]['appoint_name'] = $val['appoint_name'];
    			$appoint_array[$val['appoint_id']]['appoint_type'] = $val['appoint_type'];
    			$appoint_array[$val['appoint_id']]['appoint_price'] = $val['appoint_price'];
    		}
    	}
    	if(!empty($store_info)){
    		$store_array = array();
    		foreach($store_info as $val){
    			$store_array[$val['store_id']]['store_name'] = $val['name'];
    			$store_array[$val['store_id']]['store_adress'] = $val['adress'];
    		}
    	}
    	if(!empty($order_info)){
    		foreach($order_info as &$val){
    			$val['phone'] = $user_array[$val['uid']]['phone'];
    			$val['nickname'] = $user_array[$val['uid']]['nickname'];
    			$val['appoint_name'] = $appoint_array[$val['appoint_id']]['appoint_name'];
    			$val['appoint_type'] = $appoint_array[$val['appoint_id']]['appoint_type'];
    			$val['appoint_price'] = $appoint_array[$val['appoint_id']]['appoint_price'];
    			$val['store_name'] = $store_array[$val['store_id']]['store_name'];
    			$val['store_adress'] = $store_array[$val['store_id']]['store_adress'];
    		}
    	}
    	return $order_info;
    }
	/*验证预约服务*/
	public function appoint_verify(){
		$database_order = D('Appoint_order');
		$where['store_id'] = $this->store['store_id'];
		$where['order_id'] = $_POST['order_id'];
		$now_order = $database_order->field(true)->where($where)->find();
		if(empty($now_order)){
			$this->returnCode('20130006');
		} elseif (!$now_order['payment_status'] && $now_order['paid']!=1){
            $this->returnCode('20130019');
        } else if ($now_order['paid']!=2 && $now_order['service_status'] == 0) {
            $condition_group['appoint_id'] = $now_order['appoint_id'];
            D('Appoint')->where($condition_group)->setInc('appoint_sum',1);
			$fields['store_id'] = $this->staff_session['store_id'];
			$fields['last_staff'] = $this->staff_session['name'];
			$fields['last_time'] = time();
            $fields['verify_time'] = time();
			$fields['service_status'] = 1;
			if($database_order->where($where)->data($fields)->save()){

				//验证增加商家余额
				$order_info['order_id'] = $now_order['order_id'];
                $order_info['mer_id'] = $now_order['mer_id'];
				$order_info['store_id'] = $now_order['store_id'];
				$order_info['order_type'] = 'appoint';
				$order_info['balance_pay'] = $now_order['balance_pay'];
				$order_info['score_deducte'] = $now_order['score_deducte'];
				$order_info['is_own'] = $now_order['is_own'];
				$order_info['merchant_balance'] = $now_order['merchant_balance'];
				$order_info['score_used_count'] = $now_order['score_used_count'];
//				$order_info['money'] = $order_info['user_pay_money'] + $order_info['balance_pay'] + $order_info['score_deducte'] + $order_info['pay_money'] + $order_info['merchant_balance'];
				$order_info['money'] = $order_info['balance_pay']
                    + $order_info['score_deducte']
                    + $order_info['payment_money']
                    + $order_info['merchant_balance']
                    + $order_info['product_score_deducte']
                    + $order_info['product_balance_pay']
                    + $order_info['product_coupon_price']
                    + $order_info['user_pay_money']
                    + $order_info['product_merchant_balance'];

				if($now_order['product_id'] > 0){
					$order_info['total_money'] = $now_order['product_price'];
				}else{
					$order_info['total_money'] = $now_order['appoint_price'];
				}
                if ($order_info['money']>$order_info['total_money']) {
                    $order_info['money'] = $order_info['total_money'];
                }

				$order_info['payment_money'] = $now_order['pay_money'] + $now_order['user_pay_money'];
				$order_info['balance_pay'] = $now_order['balance_pay'] + $now_order['product_balance_pay'];
				$order_info['merchant_balance'] = $now_order['merchant_balance'] + $now_order['product_merchant_balance'];
				$order_info['card_give_money'] = $now_order['card_give_money'] + $now_order['product_card_give_money'];
				$order_info['uid'] = $now_order['uid'];

				$appoint_name = M('Appoint')->field('appoint_name,score_percent')->where(array('appoint'=>$now_order['appoint_id']))->find();
				//D('Merchant_money_list')->add_money($this->store['mer_id'],'用户预约'.$appoint_name['appoint_name'].'记入收入',$order_info);
                $order_info['desc']=L_('用户预约X1记入收入',$appoint_name['appoint_name']);
                $order_info['score_discount_type']=$now_order['score_discount_type'];
                D('SystemBill')->bill_method($order_info['is_own'],$order_info);
                $now_user = D('User')->get_user($order_info['uid']);
                D('Merchant_spread')->add_spread_list($order_info,$now_user,'appoint',L_("X1用户购买预约获得佣金",$now_user['nickname']));
				//if($this->config['open_score_get_percent']==1){
				//	$score_get = $this->config['score_get_percent']/100;
				//}else{
				//	$score_get = $this->config['user_score_get'];
				//}
                
                if($this->config['add_score_by_system_commission']==0 && $this->config['add_score_by_percent']==0 && ($this->config['open_score_discount']==0 || $order_info['score_discount_type']!=2) && ($this->config['score_get_times']==0 || !isset($this->config['score_get_times']))){
                    if($order_info['is_own'] && $this->config['user_own_pay_get_score']!=1){
                        $order_info['payment_money'] = 0;
                    }
                    if($appoint_name['score_percent'] > 0){
                        $this->config['score_get'] = $order_info['total_money'] * $appoint_name['score_percent'];
                    }
                    else{
                        $this->config['score_get'] = D('Percent_rate')->get_user_add_score_times($this->config['score_get'],$order_info['uid']);
                    }
                    $params = [
                        'mer_id' => $order_info['mer_id'] ?? 0,
                        'store_id' => $order_info['store_id'] ?? 0,
                        'order_id' => $order_info['order_id'],
                        'order_type' => $order_info['order_type'] 
                    ];
				    D('User')->add_score($order_info['uid'], (($order_info['balance_pay']+$order_info['payment_money']) * $this->config['score_get']), L_('购买预约商品获得X1',$this->config['score_name']),0,$params);
                }
				$this->returnCode(0);
			} else {
				$this->returnCode('20130007');
			}
		}else{
			$this->returnCode('20130008');
		}
	}
	/*预约订单查找*/
	public function appoint_find(){
		$database_order = D('Appoint_order');
	    $database_user = D('User');
	    $database_appoint = D('Appoint');
	    $database_store = D('Merchant_store');
		$order_id	=	$_POST['order_id'];
		$appoint_where['mer_id'] = $this->store['mer_id'];
		if($_POST['find_type'] == 1 && strlen($_POST['find_value']) == 16){
			$appoint_where['appoint_pass'] = $_POST['find_value'];
		} else {
			if($_POST['find_type'] == 1){
				$appoint_where['appoint_pass'] = array('LIKE', '%'.$_POST['find_value'].'%');
			} else if($_POST['find_type'] == 2){
				$appoint_where['order_id'] = $_POST['find_value'];
			} else if($_POST['find_type'] == 3){
				$appoint_where['appoint_id'] = $_POST['find_value'];
			} else if($_POST['find_type'] == 4){
				$user_where['uid'] = $_POST['find_value'];
			} else if($_POST['find_type'] == 5){
				$user_where['nickname'] = array('LIKE', '%'.$_POST['find_value'].'%');
			} else if($_POST['find_type'] == 6){
				$user_where['phone'] = array('LIKE', '%'.$_POST['find_value'].'%');
			}
		}
		if($order_id && $_POST['find_type'] != 2){
			$appoint_where['order_id']	=	array('lt',$order_id);
    	}
    	$count = $database_order->where($appoint_where)->count();
	    $order_info = $database_order->field(true)->where($appoint_where)->order('`order_id` DESC')->select();
	    $user_info = $database_user->field('`uid`, `phone`, `nickname`')->where($user_where)->select();
	    $appoint_info = $database_appoint->field('`appoint_id`, `appoint_name`, `appoint_type`, `appoint_price`')->select();
	    $store_info = $database_store->field('`store_id`, `name`, `adress`')->select();
	    $order_list = $this->formatOrderArray($order_info, $user_info, $appoint_info, $store_info);

	    if($order_list){
	    	foreach($order_list as $k=>$v){
	    		if($_POST['find_type'] == 5){
                    if(!isset($v['nickname'])){
                        unset($order_list[$k]);
                        continue;
                    }
                }else if($_POST['find_type'] == 6){
                    if(!isset($v['phone'])){
                        unset($order_list[$k]);
                        continue;
                    }
                }
    			$truename	=	'';
				if($v['truename']){
					$truename	=	$v['truename'];
    			}else{
					$truename	=	$v['nickname'];
    			}
				$arr['order_list'][]	=	array(
					'order_id'	=>	$v['order_id'],
					'appoint_name'	=>	isset($v['appoint_name'])?$v['appoint_name']:'',
					'truename'	=>	$truename,
					'phone'	=>	$v['phone'],
					'appoint_date'	=>	$v['appoint_date'].' '.$v['appoint_time'],
					'payment_money'	=>	floatval($v['payment_money']),
					'appoint_price'	=>	floatval($v['appoint_price']),
					'paid'			=>	$v['paid'],
					'service_status'=>	$v['service_status'],
				);
    		}
	    }else{
			$arr['order_list']	=	array();
	    }

		if($arr['order_list']){
			$arr['count'] = $count;
			$arr['page'] = ceil($count/10);
		}else{
			$arr['count'] = 0;
			$arr['page'] = 0;
			$arr['order_list']	=	array();
		}
		$arr['status']	=	2;
		$this->returnCode(0,$arr);
	}

    /**
     * 店员更换技师
     * copy from cms/Lib/Action/Merchant/StoreAction.class.php ajax_worker_edit
     */
    public function changeWorker(){
        $merchant_worker_id = intval($_POST['merchant_worker_id']);
        $order_id = intval($_POST['order_id']);
        if(!$merchant_worker_id || !$order_id){
            exit(json_encode(array('status'=>0,'msg'=>L_('传递参数有误！'))));
        }

        $database_appoint_visit_order_info = D('Appoint_visit_order_info');
        $database_appoint_order = D('Appoint_order');
        $_data['merchant_worker_id'] = $merchant_worker_id;
        $where['order_id'] = $order_id;
        $worker = D('Merchant_workers')
            ->where([
                'merchant_worker_id' => $merchant_worker_id,
                'merchant_store_id' => $this->staff_session['store_id']
            ])
            ->field('merchant_worker_id, name')
            ->find();
        if(empty($worker)){
            exit(json_encode(array('errorCode'=>400,'errorMsg'=>L_('当前技师不存在'))));
        }
        $appointOrder = $database_appoint_order->where($where)->find();
        if($appointOrder['service_status']){
            exit(json_encode(array('errorCode'=>400,'errorMsg'=>L_('当前订单技师已服务'))));
        }
        $_data['change_worker_staff_uid'] = $this->_uid;
        $database_appoint_order->where($where)->data($_data)->save();
        
        $insert_id = $database_appoint_visit_order_info->where($where)->data($_data)->save();
        if($insert_id){
            $now_order = $database_appoint_order->where(array('order_id'=>$order_id))->find();
            $worker_where['appoint_order_id'] = $order_id;
            $now_worker = $database_appoint_visit_order_info->appoint_visit_order_detail($worker_where);
            $now_worker = $now_worker['detail'];
            if($now_order['appoint_date']&&$now_order['appoint_time']){
                $tmp_appoint_time = $now_order['appoint_date'] . ' ' . $now_order['appoint_time'];
                $sms_msg = L_('店员为您分配新的预约订单，预约服务时间：X1，订单号：X2，请注意查收！',array('X1'=>$tmp_appoint_time,'X2'=>$order_id));
            }else{
                $sms_msg = L_('店员为您分配新的预约订单，订单号：X1，请注意查收！',array('X1'=>$order_id));
            }
            if(C('config.store_worker_sms_order') && !empty($now_worker)){
                $database_appoint = D('Appoint');
                $appoint_info = $database_appoint->get_appoint_by_appointId($now_order['appoint_id']);

                $sms_data = array('mer_id' => $now_order['mer_id'], 'store_id' => $now_order['store_id'], 'type' => 'appoint');
                $sms_data['uid'] = 0;
                $sms_data['mobile'] = $now_worker['mobile'];
                $sms_data['sendto'] = 'user';
                $sms_data['content'] = L_('有份新的X1被预约，订单号：X2请您注意查看并处理!',array('X1'=>$appoint_info['appoint_name'],'X2'=>$now_order['order_id']));
                (new Sms)->sendSms($sms_data);
            }
            if (!empty($now_worker)&&$now_worker['openid']) {
                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                $href = C('config.site_url') . '/wap.php?g=Wap&c=Workerstaff&a=pick';
                $model->sendTempMsg('TM00017',
                    array(
                        'href' => $href,
                        'wecha_id' => $now_worker['openid'],
                        'first' => $sms_msg,
                        'OrderSn' => $now_order['order_id'],
                        'OrderStatus' => L_('分配新订单提醒'),
                        'remark' => '\n'.L_('请点击查看详细信息！')
                    )
                );
            }

            $database_appoint_supply = D('Appoint_supply');
            $now_supply_order = $database_appoint_supply->where(array('order_id'=>$now_order['order_id']))->find();
            $supply_data['appoint_id'] = $now_order['appoint_id'];
            $supply_data['mer_id'] = $now_order['mer_id'];
            $supply_data['store_id'] = $now_order['store_id'];
            $supply_data['create_time'] = time();
            $supply_data['status'] =  1;
            if($merchant_worker_id > 0){
                $supply_data['worker_id'] = $merchant_worker_id;
                $supply_data['get_type'] = 1;
                $supply_data['status'] =  2;
            }
            $supply_data['start_time'] = $_SERVER['REQUEST_TIME'];
            $supply_data['paid'] = $now_order['paid'];
            $supply_data['pay_type'] = $now_order['pay_type'];
            $supply_data['order_time'] = $now_order['order_time'];
            $supply_data['deliver_cash'] = floatval($now_order['product_price'] - $now_order['product_card_price'] - $now_order['product_merchant_balance'] - $now_order['product_balance_pay'] - $now_order['product_payment_money'] - $now_order['product_score_deducte'] - $now_order['product_coupon_price']);
            $supply_data['uid'] = $now_order['uid'];

            if(!$now_supply_order){
                $supply_data['order_id'] = $now_order['order_id'];
                $database_appoint_supply->data($supply_data)->add();
            }else{
                $supply_where['order_id'] = $now_order['order_id'];
                $database_appoint_supply->where($supply_where)->data($supply_data)->save();
            }
	        $recordContent['content'] = [
		        [
			        'title' => '服务人员名称',
			        'value' => $now_worker['name'],
			        'type'  => 'text'
		        ],
		        [
			        'title' => '服务人员手机号',
			        'value' => $now_worker['mobile'],
			        'type'  => 'text'
		        ],
	        ];
	        D('Appoint_order_records')->addOrderRecord($now_order['mer_id'], $now_order['order_id'], '分配服务人员', $recordContent);
            exit(json_encode(array('errorCode'=>0,'msg'=>L_('订单分配成功！'))));
        }else{
            exit(json_encode(array('errorCode'=>400,'errorMsg'=>L_('订单分配失败！'))));
        }
    }

    /**
     * 店铺技师列表
     */
    public function storeWorkerList()
    {
        $orderId = I('order_id');
        if(empty($orderId)){
            $this->returnCode(400, '参数缺失');
        }
        $worker = D('Appoint_supply')->where(array('order_id'=>$orderId))->field('worker_id')->find();
        $storeId = $this->staff_session['store_id'];
        $merchantWorker = D('Merchant_workers')
            ->where(['merchant_store_id' => $storeId])
            ->where(['merchant_worker_id' => ['neq',$worker['worker_id']]])
            ->field('merchant_worker_id, name,mobile')
            ->select();
        foreach ($merchantWorker as &$m) {
            $m['receive_total'] = D('Appoint_supply')->where(['status' => 2, 'store_id' => $storeId, 'paid' => 1, 'worker_id' => $m['merchant_worker_id']])->count();
        }
        $this->returnCode(0, $merchantWorker);
    }
	/*预约订单详情*/
	public function appoint_edit(){
		$where['order_id'] = $_POST['order_id'];
		$database_order = D('Appoint_order');
    	$database_user = D('User');
    	$database_appoint = D('Appoint');
    	$database_store = D('Merchant_store');
    	$order_info = $database_order->field(true)->where($where)->order('`order_id` DESC')->select();
        $uidArr = array();
        foreach($order_info as $v){
        	array_push($uidArr,$v['uid']);
        }
        $uidArr = array_unique($uidArr);
    	$user_info = $database_user->field('`uid`, `phone`, `nickname`')->where(array('uid'=>array('in',$uidArr)))->select();
    	$appoint_info = $database_appoint->field('`appoint_id`, `appoint_name`, `appoint_type`, `appoint_price`')->select();
    	$store_info = $database_store->field('`store_id`, `name`, `adress`')->select();
    	$order_list = $this->formatOrderArray($order_info, $user_info, $appoint_info, $store_info);
    	$now_order = $order_list[0];
    	$cue_info = unserialize($now_order['cue_field']);
    	$cue_list = array();
    	foreach($cue_info as $key=>$val){
    		$address	=	'';
    		if(!empty($val['value'])){
    			if($val['type'] == 2){
    				$address = $val['address'];
    			}
    			if($val['long'] && $val['lat']){
					$long	=	$val['long'];
					$lat	=	$val['lat'];
    			}
    			$cue_list[]	=	array(
					'name'	=>	$val['name'],
					'value'	=>	$val['value'],
					'type'	=>	$val['type'],
					'address'=>	isset($address)?$address:'',
    			);
    		}
    	}
    	if($now_order){
			$arr['now_order']	=	array(
				'appoint_id'	=>	$now_order['appoint_id'],
				'appoint_name'	=>	$now_order['appoint_name'],
				'order_id'	=>	$now_order['order_id'],
				'appoint_date'	=>	$now_order['appoint_date'].' '.$now_order['appoint_time'],
				'paid'	=>	$now_order['paid'],
				'service_status'	=>	$now_order['service_status'],
				'payment_money'	=>	floatval($now_order['payment_money']),
				'appoint_price'	=>	floatval($now_order['appoint_price']),
				'order_time'	=>	date('Y-m-d H:i',$now_order['order_time']),
				'pay_time'	=>	$now_order['pay_time']!=0 ? date('Y-m-d H:i',$now_order['pay_time']) :'',
				'paytypestr'	=>	isset($now_order['paytypestr'])?$now_order['paytypestr']:'',
				'balance_pay'	=>	$now_order['balance_pay'],
				'merchant_balance'	=>	$now_order['merchant_balance'],
				'pay_money'	=>	$now_order['pay_money'],
				'last_time'	=>	$now_order['last_time']!=0 ? date('Y-m-d H:i',$now_order['last_time']) :'',
				'last_staff'	=>	isset($now_order['last_staff'])?$now_order['last_staff']:'',
				'content'	=>	isset($now_order['content'])?$now_order['content']:'',
				'uid'	=>	$now_order['uid'],
				'nickname'	=>	$now_order['nickname'],
				'phone'	=>	$now_order['phone'],
				'longs'	=>	isset($long)?$long:0,
				'lats'	=>	isset($lat)?$lat:0,
			);
    	}else{
			$arr['now_order']	=	array();
    	}
    	$arr['cue_list']	=	$cue_list;
    	$this->returnCode(0,$arr);
	}

    /**
     * 团购优惠组合扫二维码验证
     * author 衡婷妹
     * date 20201120
     * */
    public function group_combine_find(){
        $param['group_pass'] = $_POST['group_pass'];
        $param['real_orderid']= $_POST['order_id'];
        $param['mer_id'] = $this->store['mer_id'];
        $param['store_id'] = $this->store['store_id'];
        if(strlen($param['group_pass']) != 16) {
            $this->returnCodeError(L_('消费码错误'));
        }

        // 团购优惠组合搜索
        $group_combine = D('Group_combine_activity')->getActivityOrderBySearch($param);
        if ($group_combine['error'] == 0) {
            $return['row_count'] = 1;
            $return['data'] = $group_combine['data'];
        } else {
            $return['row_count'] = 0;
        }
        $this->returnCode(0,$return);
    }

    /**
     * 团购优惠组合验证消费
     * author 衡婷妹
     * date 20201120
     */
    public function group_combine_verify(){
        $this->check_group();

        $confirmData = isset($_POST['confirm_data']) ? json_decode(htmlspecialchars_decode($_POST['confirm_data']), true) : [];

        $param['order_id'] = $_POST['order_id'];
        $param['mer_id'] = $this->store['mer_id'];
        $param['store_id'] = $this->store['store_id'];
        $param['last_staff'] = $this->staff_session['name'];
        $param['staff_name'] = $this->staff_session['name'];

        if ($confirmData) {
            $param['order_id'] = $confirmData['order_id'] ?? 0;
            $result = D('Group_combine_activity')->groupCombineVerify($param);
            if ($result['error'] > 0) {
                //异常
                $rs = [
                    'status' => 1003,
                    'msg' => $result['msg'],
                    'data' => new \stdClass()
                ];
            } else {
                //正常
                $rs = [
                    'status' => 1000,
                    'msg' => 'success',
                    'data' => [
                        'result' => '验证核销成功',
                        'url' => C('config.site_url') . '/packapp/storestaff/group_detail.html?order_id=' . $result['data']['order_id'] . '&from=scan'
                    ]
                ];
            }
            echo json_encode($rs,JSON_UNESCAPED_UNICODE);
            exit();
        }

        $result = D('Group_combine_activity')->groupCombineVerify($param);

        if($result['error']>0){
            $this->returnCodeError($result['msg']);
        }

        $this->returnCode(0,$result['data']);
    }

    /*     * *团购扫二维码验证*** */
    public function group_qrcode(){
        $group_pass = trim($_POST['group_pass']);
        $this->check_group();
        if(empty($group_pass)){
			$this->returnCode('20130023');
        }
        $database_group_order = D('Group_order');
        $now_order = $database_group_order->where(array('mer_id' => $this->store['mer_id'], 'group_pass' => $group_pass))->find();
        if (empty($now_order)) {
            $this->returnCode('20130006');
        } else if ($now_order['paid'] && $now_order['status'] == 0) {
            $condition_group_order['order_id'] = $now_order['order_id'];
            if (empty($now_order['third_id']) && $now_order['pay_type'] == 'offline') {
                $data_group_order['third_id'] = $now_order['order_id'];
            }
            $data_group_order['status'] = '1';
            $data_group_order['store_id'] = $this->store['store_id'];
            $data_group_order['use_time'] = $_SERVER['REQUEST_TIME'];
            $data_group_order['last_staff'] = $this->staff_session['name'];
            if ($database_group_order->where($condition_group_order)->data($data_group_order)->save()) {
            	$this->group_notice($now_order,1);
                D('Group_pass_relation')->change_refund_status($now_order['order_id'],1);
                /*-----记录系统订单信息 验证消费 改写已完成 19/1/18----*/
                D("System_order")->save_reg_order_info('group',$now_order['order_id'],array(),2);
                $this->returnCode(0);
            } else {
                $this->returnCode('20130007');
            }
        } else {
            $this->returnCode('20130008');
        }
    }
    /*     * *餐饮扫二维码验证*** */
    public function meal_qrcode(){
        $order_id = trim($_POST['order_id']);
        $this->check_meal();
        if(empty($order_id)){
			$this->returnCode('20130024');
        }
        $store_id = intval($this->store['store_id']);
        if (!empty($order_id)) {
            if ($order = D("Meal_order")->where(array('mer_id' => $this->store['mer_id'], 'order_id' => $order_id, 'store_id' => $store_id))->find()) {
				if ($order['status'] > 2) {
					$this->returnCode('20130011');
				}
                $data = array('store_uid' => $this->staff_session['id'], 'status' => 1,'use_time'=>time(),'last_staff'=>$this->staff_session['name']);

                if (empty($order['third_id']) && $order['pay_type'] == 'offline') {
                	$order['paid'] = 0;
                }
                if ($order['paid'] == 0) {
                	$notOffline = 1;
                	if ($this->config['pay_offline_open'] == 1) {
                		$now_merchant = D('Merchant')->get_info($order['mer_id']);
                		if ($now_merchant) {
                			$notOffline =($now_merchant['is_close_offline'] == 0 && $now_merchant['is_offline'] == 1) ? 0 : 1;
                		}
                	}
                	if ($notOffline) {
                		$this->returnCode('20130012');
                	}
                }
				if($order['paid'] != 1 || ($order['pay_type'] == 'offline' && empty($order['third_id']))){
					$data['third_id'] = $order['order_id'];
                    $data['pay_type'] = 'offline';
                    $data['paid'] = 1;
                    $data['pay_time'] = $_SERVER['REQUEST_TIME'];
				}
				$data['use_time'] = $_SERVER['REQUEST_TIME'];
                if (D("Meal_order")->where(array('mer_id' => $this->store['mer_id'], 'order_id' => $order_id, 'store_id' => $store_id))->save($data)) {
                	if ($order['status'] == 0) {
                		if ($supply = D('Deliver_supply')->field(true)->where(array('order_id' => $order_id, 'item' => 0))->find()) {
                			if ($supply['status'] < 2) {
                				D('Deliver_supply')->where(array('order_id' => $order_id, 'item' => 0))->delete();
                			} else {
                				D('Deliver_supply')->where(array('order_id' => $order_id, 'item' => 0))->save(array('status' => 5));
                			}
                		}
                		$this->meal_notice($order);
                	}
	                $this->returnCode(0);
                } else {
                	$this->returnCode('20130007');
                }
            } else {
                $this->returnCode('20130006');
            }
        } else {
            $this->returnCode('20130006');
        }
    }
	/*     * *快店扫二维码验证*** */
    public function shop_qrcode(){
        $order_id = trim($_POST['order_id']);
        $this->check_shop();
        if(empty($order_id)){
			$this->returnCode('20130024');
        }
        $store_id = intval($this->store['store_id']);
		$status = 2;
		if ($order = D("Shop_order")->where(array('mer_id' => $this->store['mer_id'], 'order_id' => $order_id, 'store_id' => $store_id))->find()) {
			if ($order['status'] > 3) {
				$this->returnCode('20130011');
			}
			$data = array('status' => $status, 'order_status' => 6, 'use_time' => time(), 'last_staff' => $this->staff_session['name']);
  			if(empty($order['third_id']) && $order['pay_type'] == 'offline'){
				$order['paid'] = 0;
  			}
			if ($order['paid'] == 0) {
				$notOffline = 1;
				if ($this->config['pay_offline_open'] == 1) {
					$now_merchant = D('Merchant')->get_info($order['mer_id']);
  					if ($now_merchant) {
						$notOffline =($now_merchant['is_close_offline'] == 0 && $now_merchant['is_offline'] == 1) ? 0 : 1;
					}
    			}
				if ($notOffline) {
					$this->returnCode('20130012');
				}
			}
			if ($order['paid'] != 1 || ($order['pay_type'] == 'offline' && empty($order['third_id']))) {//将未支付的订单，由店员改成已消费，其订单状态则修改成线下已支付！
				$data['third_id'] = $order['order_id'];
				$data['pay_type'] = 'offline';
				$data['paid'] = 1;
			}

			if (D("Shop_order")->where(array('mer_id' => $this->store['mer_id'], 'order_id' => $order_id, 'store_id' => $store_id))->save($data)) {
				if ($status == 2 && $order['status'] < 2) {
					D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 7, 'name' => $this->staff_session['name'], 'phone' => $this->store['phone']));
					if ($supply = D('Deliver_supply')->field(true)->where(array('order_id' => $order_id, 'item' => 2))->find()) {
						if ($supply['status'] < 2) {
 							D('Deliver_supply')->where(array('order_id' => $order_id, 'item' => 2))->delete();
						} else {
							D('Deliver_supply')->where(array('order_id' => $order_id, 'item' => 2))->save(array('status' => 5));
						}
					}
  					$this->shop_notice($order);
				}
				$this->returnCode(0);
			} else {
				$this->returnCode('20130013');
			}
		} else {
 			$this->returnCode('20130014');
		}
    }

	//到店消费

    /*
     *
     *
     *	到店支付  begin
     *
     *
     */
    //到店付订单列表
    public function store_arrival()
    {
        // 开启店员权限
        if (C('config.open_staff_power')&&$this->staff_session['type'] == 0) {           
            $this->returnCode(1,'',L_('您当前身份为【店小二】，暂无收银权限，请联系管理员！'));                
        }

		$store_order = D('Store_order');
		$res = $store_order->where(array('orderid'=>$_POST['order_id']))->find();

		$order_id = $res['order_id'];

		$condition = '';
		if($_POST['condition']&&$_POST['keyword']){
			if($_POST['condition']=='order_id'){
				$_POST['condition']='orderid';
			}
			$condition='AND '.$_POST['condition'].' like "%'.$_POST['keyword'].'%"';
		}

        if($_POST['stime']){
            $condition .= ' AND s.dateline >= '.strtotime($_POST['stime']);
        }
        if($_POST['etime']){
            $condition .= ' AND s.dateline < '.(strtotime($_POST['etime'])+86400);
        }
     
        $count_condition = $condition;
        $store_order = D('Store_order');

        if ($order_id) {
            $condition .= ' AND order_id < ' . $order_id;
        }

        $sql = "SELECT s.*, u.nickname, u.phone FROM " . C('DB_PREFIX') . "store_order AS s LEFT JOIN " . C('DB_PREFIX') . "user AS u ON s.uid=u.uid WHERE s.paid=1 AND s.store_id={$this->store['store_id']} AND (s.from_plat=2 OR s.from_plat=3 ){$condition} ORDER BY s.order_id DESC LIMIT 10";
        $order_list = $store_order->query($sql);
		$sql2 = "SELECT count(*) as count,sum(total_price) as toal_price_all,SUM(IF(s.refund=1,total_price,0)) AS total_refund_price_all FROM " . C('DB_PREFIX') . "store_order AS s LEFT JOIN " . C('DB_PREFIX') . "user AS u ON s.uid=u.uid WHERE s.paid=1 AND s.store_id={$this->store['store_id']} AND  (s.from_plat=2 OR s.from_plat=3 ) {$count_condition} ORDER BY s.order_id DESC";

		$count = $store_order->query($sql2);
		$pagenum = ceil($count[0]['count'] /10);
        $totalPriceAll = getFormatNumber($count[0]['toal_price_all']);
        $totalRefundPriceAll = getFormatNumber($count[0]['total_refund_price_all']);

        foreach ($order_list as &$l) {
			$arr[]=array(
                'short_order_id'=>$l['order_id'],
				'order_id'=>$l['orderid'],
				'phone'=>empty($l['phone'])?'':$l['phone'],
				'total_price'=>$l['total_price'],
				'pay_time'=>date('Y/m/d H:i:s',$l['pay_time']),
                'refund'=>$l['refund'],
			);
        }
		if($order_list){

			$this->returnCode(0,array('order_list' => $arr,'pagenum'=>$pagenum,'total_price'=>$totalPriceAll,'total_refund_price'=>$totalRefundPriceAll ));

		}else{
			$this->returnCode(0,array('order_list' => array(),'pagenum'=>$pagenum,'total_price'=>$totalPriceAll,'total_refund_price'=>$totalRefundPriceAll ));
		}
    }

    //创建到店付订单
    public function store_arrival_add(){
        // 开启店员权限功能 店小二没权限
        if (C('config.open_staff_power')&&$this->staff_session['type'] == 0) {
            $this->returnCode(1,null,L_('您当前身份为【店小二】，没有结账权限，请联系管理员！'));
        }

        if(IS_POST){

            $data = array('store_id' => $this->store['store_id']);
            $data['mer_id'] = $this->store['mer_id'];
            $data['orderid'] = date("YmdHis") . mt_rand(10000000, 99999999);
            $data['name'] = L_('顾客现场自助支付').'-'. $this->store['name'];
            $data['desc'] = $_POST['txt_info'];
            $data['total_price'] = $_POST['total_price'];
            $data['discount_price'] = $_POST['discount_money'];
            $pay_money = $data['price'] = $_POST['pay_money']; // 还应支付的金额 
            $data['staff_id'] = $this->staff_session['id'];
            $data['staff_name'] = $this->staff_session['name'];
            $data['no_discount_money'] = $_POST['no_discount_money'] ? $_POST['no_discount_money'] : 0;

			if($_POST['business_type']){
				$data['business_type'] = $_POST['business_type'];
				$data['business_id'] = $_POST['business_id'];
                // 开启店员权限功能 修改价格权限
                if (C('config.open_staff_power')&&$this->staff_session['is_change'] == 0) {
                    $order = D('Foodshop_order')->get_order_detail(array('order_id' => $data['business_id'], 'store_id' => $this->store['store_id']), 3);
                    $food_money = D('Foodshop_order')->count_price($order);
                    if ($food_money != $_POST['total_price']) {
                        $this->returnCode(1,null,L_('您没有修改价格权限，请联系管理员！'));
                    }
                }
			}
            $data['uid'] = intval($_POST['uid']);
            if($_POST['from_scan']){
                $data['payid'] = $_POST['payid'];
                //$this->success('SCAN_PAY_SUCCESS');
            }
            
            //保存优惠券
            $coupon_discount_id = isset($_POST['coupon_discount_id']) ? intval($_POST['coupon_discount_id']) : 0;
            $coupon_discount_money = isset($_POST['coupon_discount_money']) ? floatval($_POST['coupon_discount_money']) : 0;
            $is_use_discount = isset($_POST['is_use_discount']) ? intval($_POST['is_use_discount']) : 0;
            if ($is_use_discount && $coupon_discount_id) {
                M('Plat_order')->where(array('business_id' => $_POST['business_id'], 'business_type' => 'foodshop'))->save(array('system_coupon_id' => $coupon_discount_id, 'system_coupon_price' => $coupon_discount_money));
            }
            
            //新加的优惠
            if ($_POST['pay_price'] && $this->config['is_open_pay_bind_user']) {
                $data['price'] = $price = $_POST['pay_price'] ? $_POST['pay_price'] : 0;//折扣后金额
                $card_discount_money = $_POST['card_discount'] ? $_POST['card_discount'] : 0; // 会员折扣金额
                $data['vip_discount_money'] = $_POST['vip_discount_money'] ? $_POST['vip_discount_money'] : 0; // 平台会员等级优惠
                $data['score_deducte'] = $_POST['score_deducte'] ? $_POST['score_deducte'] : 0; 
                $data['score_used_count'] = $_POST['score_used_count'] ? $_POST['score_used_count'] : 0; 
                $card_id = $_POST['card_id'] ? $_POST['card_id'] : 0; // 商家优惠券id
                $data['card_price'] = $_POST['card_price'] ? $_POST['card_price'] : 0; 
                $coupon_id = $_POST['coupon_id'] ? $_POST['coupon_id'] : 0; // 平台优惠券id
                $data['coupon_price'] = $_POST['coupon_price'] ? $_POST['coupon_price'] : 0; 
                $card_money = $_POST['card_money'] ? $_POST['card_money'] : 0; // 会员卡使用金额

                $type = $_POST['business_type']=='foodshop'?'meal':'store';

                //检查会员卡的信息余额
                $data['merchant_balance'] = 0;//商家余额
                $data['card_give_money'] = 0;//会员卡赠送余额
                $data['coupon_id'] = 0;//平台优惠券金额
                $data['card_id'] = 0;//商家优惠券的金额
                $coupon_price = 0;
                $sys_coupon_price = 0;
                $score_deducte_price = 0;
                
                if ($data['uid']&&$card = D('Card_userlist')->field(true)->where(array('uid' => $data['uid'], 'mer_id' => $this->store['mer_id']))->find()) {
                    $card_source = M(_view('Card_new'))->where(array('mer_id' => $this->store['mer_id'], 'status' => 1))->find();
                    if ($card['status'] == 0 && $card_money) {
                        $this->returnCode(1,null,L_('会员卡已禁用'));
                    }

                    if ($card_discount_money) { // 使用会员卡折扣
                        $data['card_discount'] = $card_source['discount'];
                        $data['price'] += $card_discount_money;
                    }
                    // if (empty($card_source)) $this->returnCode(1,null,'会员卡不可用');

                    if ($card_money) {
                        $user_card_total_moeny = $card['card_money'] + $card['card_money_give'];
                        if ($user_card_total_moeny < $card_money) {
                            $this->returnCode(1,null,L_('会员卡余额不足'));
                        }
                        if ($card_money <= $card['card_money']) {
                            $data['merchant_balance'] = $card_money;
                            $data['card_give_money'] = 0;
                        } else {
                            $data['merchant_balance'] = $card['card_money'];
                            $data['card_give_money'] = $card_money - $card['card_money'];
                        } 
                    }

                    // 商家优惠券
                    if ($user_coupon = M('Card_new_coupon_hadpull')->field(true)->where(array('id' => $card_id, 'is_use' => 0))->find()) {
                        if ($new_coupon = M(_view('card_new_coupon'))->field(true)->where(array('coupon_id' => $user_coupon['coupon_id']))->find()) {
                            $now_time = time();
                            if (($new_coupon['cate_name'] == 'all' || $new_coupon['cate_name'] == $type) && $new_coupon['end_time'] > $now_time && $price >= $new_coupon['order_money']) {
                                $data['card_id'] = $card_id;
                                $coupon_price = $data['card_price'] = $new_coupon['discount'];
                            }
                        }
                    }
                } else {
                    $card_money = 0;
                }

                // 平台优惠券
                if ($user_coupon = D('System_coupon_hadpull')->field(true)->where(array('id' => $coupon_id, 'is_use' => 0))->find()) {
                    if ($new_coupon = M('System_coupon')->field(true)->where(array('coupon_id' => $user_coupon['coupon_id']))->find()) {
                        if (($new_coupon['cate_name'] == 'all' || $new_coupon['cate_name'] == $type) && $new_coupon['end_time'] > $now_time && ($price-$coupon_price) >= $new_coupon['order_money']) {
                             //查看使用期限是否到期
                            // 用户可使用的天数
                            if ($new_coupon['valid_time']) {
                                if ($user_coupon['receive_time']<$new_coupon['start_time']) {
                                    $end_time = $new_coupon['start_time'] + $new_coupon['valid_time']*86400;
                                }else{
                                    $end_time = $user_coupon['receive_time'] + $new_coupon['valid_time']*86400;
                                }
                                if ($end_time>time()) {
                                    $data['coupon_id'] = $coupon_id;
                                    $sys_coupon_price = $data['coupon_price'] = $new_coupon['discount'];
                                }
                            }else{
                                $data['coupon_id'] = $coupon_id;
                                $sys_coupon_price = $data['coupon_price'] = $new_coupon['discount'];
                            }
                        }
                    }
                }

                // 使用积分
                $score_can_use_count=0;
                $score_deducte=0;
                if ($_POST['is_use_score']) {
                    //使用积分
                    $user_score_use_percent = (float)$this->config['user_score_use_percent'];

                    $now_order['total_money'] = $price - $coupon_price - $sys_coupon_price;
                    $user_score_use_condition = $this->config['user_score_use_condition'];
                    $user_score_max_use = D('Percent_rate')->get_max_core_use($this->store['mer_id'], $type,$now_order['total_money'],$now_order);//不同业务不同积分
                    $uid = $data['uid'] ? $data['uid'] : 0;
                    $now_user = D('User')->get_user($uid);
                    $user_score_use_percent = (float)$this->config['user_score_use_percent'];
                    $score_max_deducte = bcdiv($user_score_max_use, $user_score_use_percent, 2);

                    if ($user_score_use_percent > 0 && $score_max_deducte > 0 && $user_score_use_condition > 0 ) {   //如果设置没有错误
                        $total_ = $now_order['total_money'];

                        if ($total_ >= $user_score_use_condition) {             //判断是否够使用积分的条件，即这个订单的总额是否大于规定的金额
                            if ($total_ > $score_max_deducte) {                    //判断积分最大抵扣金额是否比这个订单的总额大
                                $score_can_use_count = (int)($now_user['score_count'] > $user_score_max_use ? $user_score_max_use : $now_user['score_count']);//如果用户积分大于每个订单的积分可使用数 则按可使用数算
                                $score_deducte = bcdiv($score_can_use_count, $user_score_use_percent, 2);
                                $score_deducte = $score_deducte > $total_ ? $total_ : $score_deducte;
                            } else {
                                $score_can_use_count = ceil($total_ * $user_score_use_percent) > (int)$now_user['score_count'] ? (int)$now_user['score_count'] : ceil($total_ * $user_score_use_percent);
                                $score_deducte = bcdiv($score_can_use_count, $user_score_use_percent, 2);
                                $score_deducte = $score_deducte > $total_ ? $total_ : $score_deducte;
                            }
                        }
                    }
                    if ($score_deducte != $_POST['score_deducte']) {
                        $this->returnCode(1,null,L_('积分抵扣金额有误'));
                    }
                    $data['score_used_count'] = $score_can_use_count;
                    $data['score_deducte'] = $score_deducte;
                }
                // $data['price'] = floatval($price - $card_money - $coupon_price - $sys_coupon_price - $score_deducte);
                $pay_money  = floatval($price - $card_money - $coupon_price - $sys_coupon_price - $score_deducte);
            }else{
                $pay_money  = $_POST['pay_money'];
            }

            if($pay_money==0){
                $data['paid']=1;
                $data['pay_time']=time();
            }
            $data['dateline'] = time();
            $data['from_plat'] = 2;
            $order_id = M("Store_order")->add($data);

            // 订单改变之后调用
            D("Store_order")->after_store_change($order_id,'cash',1);
            
			if($pay_money==0){
                // 扣除优惠
                $this->store_notice($data,true);
                // 修改订单状态
                if ($_POST['business_type']=='foodshop') {
                    $save_data['status'] = 3;
                    $save_data['pay_time']=time();
                    D('Foodshop_order')->where(array('order_id' => $_POST['business_id']))->save($save_data);
                }
				$this->returnCode(0,array('orderid'=>$order_id,'order_id'=> $data['orderid']));
			}
            if($order_id){
                if($_POST['from_scan']){
                    $this->returnCode(0,array('code'=>'SCAN_PAY_SUCCESS','order_id'=>$order_id));
                }
				if($this->config['cash_pay_qrcode']){
					$pay_qrcode_url = $this->config['site_url'].'/index.php?c=Recognition&a=get_tmp_qrcode&qrcode_id='.floor($order_id+3600000000);
				}else{
					$pay_qrcode_url = $this->config['site_url'] . '/index.php?c=Recognition&a=get_own_qrcode&qrCon=' . urlencode($this->config['site_url'].'/wap.php?c=My&a=store_order_before&order_id='.$order_id);
				}
                $this->returnCode(0,array('orderid'=>$order_id,'order_id'=> $data['orderid'],'img'=>$pay_qrcode_url));
            }else{
                $this->returnCode('20130031');
            }
        }else{
            $this->returnCode('20130014');
        }
    }

    public function store_arrival_add_info(){
        if($this->now_merchant['status']!=1){
            if($this->now_merchant['merchant_end_time']>0 && $this->now_merchant['merchant_end_time']<time()){
                $this->returnCode(1,'',L_('商家已过期，暂不可使用该功能！'));
            }
            $this->returnCode(1,'',L_('商家已').$this->merchant_status[$this->now_merchant['status']].L_('，暂不可使用该功能！'));
        }
        $arr['discount_txt'] = unserialize($this->store['discount_txt']);
        $info['has_discount']=$arr['discount_txt'] ? true : false;
        $info['discount_type']= isset($arr['discount_txt']['discount_type']) ? $arr['discount_txt']['discount_type'] : 0;
        $info['discount_percent']= isset($arr['discount_txt']['discount_percent']) ? $arr['discount_txt']['discount_percent'] : 0;
        $info['condition_price']= isset($arr['discount_txt']['condition_price']) ? $arr['discount_txt']['condition_price'] : 0;
        $info['minus_price']= isset($arr['discount_txt']['minus_price']) ? $arr['discount_txt']['minus_price'] : 0;
        $info['vip_discount_type']= $this->store['vip_discount_type'];
        $info['is_open_pay_bind_user']= $this->config['is_open_pay_bind_user']; // 是否开启到店付，线下零售绑定用户
        $info['merchant_buy_points_switch']= $this->config['merchant_buy_points_switch']; // 是否开启商家积分
        $info['open_user_import_create_user']= $this->config['open_user_import_create_user']; // 是否开启导入用户后直接显示在用户列表
        $info['score_name']= $this->config['score_name']; 
        $info['cash_alias_name']= $this->config['cash_alias_name']; 
        $info['open_balance_pay_unverify']= $this->config['open_balance_pay_unverify']; //是否开启线下零售、到店付余额支付不使用短信验证码
        $info['platform_score']=  isset($this->now_merchant['platform_score']) ? floatval($this->now_merchant['platform_score']) : 0;
        $info['open_staff_power'] = $this->config['open_staff_power']?floatval($this->config['open_staff_power']):0;
        $info['staff_type'] = floatval($this->staff_session['type']);
        $info['is_change'] = floatval($this->staff_session['is_change']);
        $info['pay_yzfpay_open']= $this->config['pay_yzfpay_open'];// 是否开启翼支付
        $info['Currency_symbol'] = $this->config['Currency_symbol'];
        $this->returnCode(0,$info);

    }

    //修改桌台服务费
    public function edit_table_service(){
        $table_service_payment = floatval($_POST['table_service_payment']);
        $order_id = intval($_POST['order_id']);
        if(empty($order_id)){
            $this->returnCode(1,null,L_('订单id不能为空'));
        }
        $model = M('Foodshop_order');
        $where = array();
        $where['order_id'] = $order_id;
        $r = $model->where($where)->save(array('table_service_payment'=>$table_service_payment));
        if($r){
            $this->returnCode(1,null,L_('修改成功'));
        }else{
            $this->returnCode(1,null,L_('修改失败'));
        }
    }    

    //获得桌台号
    public function get_table_list(){
        $order_id = intval($_POST['order_id']);
        if(empty($order_id)){
            $this->returnCode(1,null,L_('订单id不能为空'));
        }

        $now_order = M('Foodshop_order')->where(array('order_id'=>$order_id))->find();
        if (!$now_order) {
            $this->returnCode(1,null,L_('订单不存在'));
        }

        $tables = M('Foodshop_table')->where(array('store_id' => $now_order['store_id'], 'tid' => $now_order['table_type']))->select();      
        $this->returnCode(0,$tables);
    }   

    //修改桌台号
    public function change_table(){
        $table_id = intval($_POST['table_id']);
        $order_id = intval($_POST['order_id']);
        if(empty($order_id)){
            $this->returnCode(1,null,L_('订单id不能为空'));
        }

        $now_order = M('Foodshop_order')->where(array('order_id'=>$order_id))->find();
        if (!$now_order) {
            $this->returnCode(1,null,L_('订单不存在'));
        }

        $table = M('Foodshop_table')->where(array('id'=>$table_id))->find();
        if(empty($table)){
            $this->returnCode(1,null,L_('桌台不存在'));
        }

        $table_type = M('Foodshop_table_type')->where(array('id'=>$table['tid']))->find();
        if ($table_id==$now_order['table_id']) {
            $this->returnCode(1,null,L_('您已是当前桌台，请选择其他桌台'));
        }

        $model = M('Foodshop_order');
        $where = array();
        $where['order_id'] = $order_id;
        $r = $model->where($where)->save(array('table_id'=>$table_id,'table_type'=>$table['tid']));
        if($r){
            $return = array(
                'table_type_name' => $table_type['name'],
                'table_name' => $table['name'],
                'min_max' => $table_type['min_people'].'-'.$table_type['max_people'].'人',
            );
            $this->returnCode(0,$return);
        }else{
            $this->returnCode(1,null,L_('修改失败'));
        }
    }

    //到店付详情
    public function store_arrival_order(){

        $order_id  = $_POST['order_id'];
        $now_order = M("Store_order")->where(array('orderid'=>$order_id))->find();
		if(empty($now_order['uid'])){
			$user['uid'] = '';
			$user['nickname'] = '';
			$user['phone'] = '';
		}else{
			$now_user=M('User')->where(array('uid'=>$now_order['uid']))->find();
			$user['uid'] = $now_user['uid'];
			$user['nickname'] = $now_user['nickname'];
			$user['phone'] = $now_user['phone'];
		}
		if($now_order){
			$arr['order_id'] = $now_order['orderid'];
			$arr['total_price'] = $now_order['total_price'];
			$arr['discount_price'] = $now_order['discount_price'];
			$arr['price'] = $now_order['price'];
			$arr['desc'] = $now_order['desc'];

            $arr['order']['pay_time'] = $now_order['pay_time']==0?'':date('Y/m/d H:i:s',$now_order['pay_time']);
			$arr['pay_type'] = D('Pay')->get_pay_name($now_order['pay_type'],1);

			$this->returnCode(0,array('order'=>$arr,'user'=>$user));
		}else{
			$this->returnCode(0,array('order'=>array(),'user'=>$user));
		}

    }
    //到店付打印小票
    public function store_arrival_print(){
        $now_order = M("Store_order")->where(array('orderid'=>$_POST['order_id']))->find();
        $printHaddle = new PrintHaddle();
        $printHaddle->printit($now_order['order_id'], 'store_order', -1);
		$this->returnCode(0);
    }

    // 到店付
    private function store_notice($order, $is_staff = false)
    {
        if (!$order['business_type']) {
            $order['business_type'] = 'store';
        }

        //验证消费
        if ($is_staff) {
            if($order['card_id']){
                $use_result = D('Card_new_coupon')->user_coupon($order['card_id'], $order['order_id'], $order['business_type'], $order['mer_id'], $order['uid']);
                if($use_result['error_code']){
                    $this->returnCode(1,null,L_('支付异常：商家优惠券扣除异常'));
                }
            }

            if($order['coupon_id']){
                $use_result = D('System_coupon')->user_coupon($order['coupon_id'], $order['order_id'], $order['business_type'], $order['mer_id'], $order['uid']);
                if($use_result['error_code']){
                    $this->returnCode(1,null,L_('支付异常：平台优惠券扣除异常'));
                }
            }

            // 使用积分
            if ($order['score_used_count'] > 0) {
                $use_result = D('User')->user_score($order['uid'], $order['score_used_count'], L_('购买 X1 扣除X2',array('X1'=>$order['name'],'X2'=>C('config.score_name'))));
                if ($use_result['error_code']) {
                    $this->returnCode(1,null,L_('支付异常：积分扣除异常'));
                }
            }

            if(floatval($order['merchant_balance']) > 0){
                $use_result = D('Card_new')->use_money($order['uid'], $order['mer_id'], $order['merchant_balance'], L_('购买 X1 扣除会员卡余额',$order['real_orderid']));
                if($use_result['error_code']){
                    $this->returnCode(1,null,L_('支付异常：扣除会员卡余额异常'));
                }
            }

            if(floatval($order['card_give_money']) > 0){
                $use_result = D('Card_new')->use_give_money($order['uid'], $order['mer_id'], $order['card_give_money'], L_('购买 X1 扣除会员卡赠送余额',$order['real_orderid']));
                if ($use_result['error_code']) {
                    $this->returnCode(1,null,L_('支付异常：扣除会员卡赠送余额异常'));
                }
            }
        }
    }

    //到店确认支付
    public function store_arrival_pay(){
        $order_id  = $_POST['order_id'];
        $cacheKey = 'store_arrival_pay_'.$order_id;
        if(S($cacheKey)){
            $this->returnCode(1,null,L_('请勿重复提交'));
        }
        S($cacheKey, 1, 3);
        $now_order = M("Store_order")->where(array('order_id'=>$order_id))->find();
		if($now_order['paid']){
			$this->returnCode('20130037');
		} 

        if ($this->config['is_open_pay_bind_user']) {
            if ($now_order['card_discount']) {  
                $now_order['card_discount_money'] = round($now_order['price']-($now_order['price']-$now_order['no_discount_money'])*$now_order['card_discount']/10-$now_order['no_discount_money'],2);
            }else{
                 $now_order['card_discount_money'] = 0;
            }
            $pay_money = round(floatval($now_order['price'] - $now_order['card_discount_money'] - $now_order['merchant_balance'] - $now_order['card_give_money'] - $now_order['card_price'] - $now_order['score_deducte'] - $now_order['coupon_price']),2);
            $now_order['price'] = $pay_money;

        }
        if($_POST['auth_type']=='balance'){
            $data['smscode'] = $_POST['auth_code'];
            $now_user = D('User')->get_user($_POST['phone'],'phone');
            $data['uid'] = $now_user['uid'];
            $data['name'] = $now_user['nickname'];
            $data['phone'] = $_POST['phone'];
            if ($now_user['now_money']<$now_order['price']) {
                $this->returnCode(1,'',L_('余额不足！'));
            }
            // 开启到店付，线下零售绑定用户，使用优惠
            $this->store_notice($now_order, true);

            $this->store_arrival_balance_pay($order_id,$now_order,'Store_order',$data);
            die;
        }
        if($_POST['auth_type']=='ecard'){
            // 平台优惠券，目前仅有一卡通支付支持平台优惠券
            $sys_hadpull_id = intval($_POST['system_coupon_hadpull_id']);
            if ($sys_hadpull_id && $user_coupon = D('System_coupon_hadpull')->field(true)->where(array('id' => $sys_hadpull_id, 'is_use' => 0))->find()) {
                if ($sys_coupon = M('System_coupon')->field(true)->where(array('coupon_id' => $user_coupon['coupon_id']))->find()) {
                    if (($sys_coupon['cate_name'] == 'all' || $sys_coupon['cate_name'] == 'cash') && $sys_coupon['end_time'] > time() && $now_order['price'] >= $sys_coupon['order_money']) {
                        if ($sys_coupon['is_discount'] == 1) {
                            //折扣券
                            $coupon_price = round($now_order['price'] * $sys_coupon['discount_value'] / 10, 2);
                        } else {
                            //满减券
                            $coupon_price = min($now_order['price'], $sys_coupon['discount']);
                        }
                        $now_order['price'] -= $coupon_price;
                        $data['coupon_id'] = $sys_hadpull_id;
                        $data['coupon_price'] = $coupon_price;
                    }
                }
            }

            $store_info = D('Merchant_store')->where(['store_id' => $now_order['store_id']])->find();
            try{
                $cardData = D('InternalMembershipCard')->getInternalCard($store_info, $_POST['phone'], '',['no_check_password' => 1]);
            }catch (\Exception $e){
                S($cacheKey, null);
                fdump_api($e->getMessage(), 'pay/store_arrival_pay_error', 1);
                $this->returnCodeError($e->getMessage());
            }
            $card_list = $cardData['data']['internal_card_ids'];
            $total_money = 0;
            foreach($card_list as $card){
                $total_money += $card['balance_money'];
            }
            $total_money = getFormatNumber($total_money);
            $now_user = D('User')->get_user($card_list[0]['phone'],'phone');
            $data['uid'] = $now_user['uid'];
            $data['name'] = $now_user['nickname'];
            $data['phone'] = $_POST['phone'];
            $internal_card_pay = array();
            try{
                if ($total_money<$now_order['price']) {
                    $this->returnCode(1,'',L_('余额不足！'));
                }else{
                    //计算一卡通扣钱
                    $money = $now_order['price'];
                    foreach($card_list as $card){
                        if ($card['balance_money'] >0){
                            if ($card['balance_money'] >= $money){
                                $internal_card_pay[$card['card_id']] = $money;
                                $money = 0;
                            }else{
                                $internal_card_pay[$card['card_id']] = $card['balance_money'];
                                $money -= $card['balance_money'];
                            }
                        }
                        if ($money == 0){
                            break;
                        }
                    }
                }
                // 开启到店付，线下零售绑定用户，使用优惠
                $data['internal_card_pay'] = $internal_card_pay;
                $this->store_arrival_ecard_pay($order_id,$now_order,'Store_order',$data);
            }catch (Exception $e) {
                S($cacheKey, null);
                fdump_api($e->getMessage(), 'pay/ecard_pay', 1);
                $this->returnCode(1, '', $e->getMessage());
            }
            S($cacheKey, null);
            die;
        }

        if ($_POST['auth_type'] == 'qiye') {
            $authCode = $_POST['auth_code'];
            $checkRs = D('Qiye')->checkPayAuthCode($authCode);
            if ($checkRs['error_no']) {
                $this->returnCode(1, '', $checkRs['error_msg']);
            }
            $payCode = $checkRs['retval'];
            $data['uid'] = $payCode['uid'];
            M('Qiye_pay_code_record')->where(['id' => $payCode['id']])->save(['status' => 1, 'order_id' => $order_id, 'type' => 'store']);
            $this->store_notice($now_order, true);
            $this->store_arrival_qiye_pay($order_id, $now_order, 'Store_order', $data);
            die;
        }

        if($_POST['offline_pay']){
            // 开启到店付，线下零售绑定用户，使用优惠
            $this->store_notice($now_order, true);
            $this->store_arrival_offline_pay($order_id,$now_order);
            die;
        }

        if($this->config['open_juhepay']==1){
            // 开启到店付，线下零售绑定用户，使用优惠
            $this->store_notice($now_order, true);
            import('@.ORG.LowFeePay');

            if($_POST['auth_code'] && (substr($_POST['auth_code'],0,1)==2 || substr($_POST['auth_code'],0,1)==3)){
                $_POST['auth_type'] = 'alipay';
            }else{
                $_POST['auth_type'] = 'scan_weixin';
            }
            $order_info['pay_type'] = $_POST['auth_type']=='alipay'?'alipay':'scan_weixin';
            $order_info['order_id'] =   'store_'.$order_id;

            $order_info['order_total_money'] =  $now_order['price'];
            $order_info['order_name'] =  $now_order['name'];
            $pay_info['auth_code'] =  $_POST['auth_code'];
            $lowfeepay = new LowFeePay('juhepay');


            $mer_juhe = M('Merchant_juhe_config')->where(array('mer_id'=>$this->store['mer_id']))->find();
            if(!empty($mer_juhe)){
                $lowfeepay->userId =$mer_juhe['userid'];
                $order_info['is_own'] =1 ;
                $order_info['order_id'] =  $order_info['order_id'].'_1';
            }
            
            $go_pay_param= $lowfeepay->pay($order_info,$pay_info);

            if($go_pay_param['error']==1){
                $this->error($go_pay_param['msg']);
            }else{

                $data['paid'] = '1';
                $data['pay_time'] = time();
                $data['pay_type'] = $_POST['auth_type']=='alipay'?'alipay':'weixin';

                $data['third_id'] = $go_pay_param['thirdid'];
                if(!empty($mer_juhe)) {
                    $data['is_own'] = 1;
                }
                $data['from_plat'] = 3; //扫用户支付码
                $data['payment_money'] = $now_order['price'];

                if(M("Store_order")->where(array('order_id'=>$order_id))->data($data)->save()){
                    $now_order['order_type']='cash';
                    $now_order['desc']=L_('用户到店扫码支付记入收入');
                    //商家余额增加

                    D('SystemBill')->bill_method($now_order['is_own'],$now_order);

                    D('Store_order')->store_score_get($now_order); // 增加积分

                    // 订单改变之后调用
                    D("Store_order")->after_store_change($order_id,'cash',2);

                    $this->returnCode(0);

                }
            }
            die;
        }
		
		if($_POST['auth_code'] && (substr($_POST['auth_code'],0,1)==5)){

            $this->store_notice($now_order, true);

            $this->store_arrival_yzfpay_pay($order_id,$now_order);die;
        }
		
		import('ORG.Net.Http');
		$http = new Http();
			
		$is_own = 0;
		
        $now_merchant = D('Merchant')->get_info($now_order['mer_id']);
		//开通威富通低费率支付
		if ($this->config['open_wftpay'] == 1) {
			$lowfeepay = new LowFeePay('wftpay');
			$order_info['re_call'] = $_POST['re_call'];
			$order_info['auth_code'] = $_POST['auth_code'];
			$order_info['is_own'] = 0;
			$order_info['mer_id'] = $now_order['mer_id'];
			$order_info['out_trade_no'] = 'store_' . $now_order['order_id'];
			$order_info['total_fee'] = $now_order['price'];
			$order_info['order_name'] = $now_order['name'] ? $now_order['name'] : $this->store['name'].' - 线下收款';
			$return = $lowfeepay->scan_pay($order_info);
			
			if ($return['return_code'] == 'FAIL') {
				$this->returnCodeError(L_('支付失败！支付返回：') . $return['return_msg']);
			}

			if ($return['err_code'] == 'USERPAYING') {
				$this->returnCode('20130035');
			}
			if ($return['result_code'] == 'FAIL') {
				$this->returnCodeError('支付失败！支付返回：' . $return['err_code_des']);
			}

			if ($return['trade_state'] == 'USERPAYING') {
				$this->returnCode('20130035');
			}

			$data['pay_type'] = $return['pay_type'] ? $return['pay_type'] : 'weixin';
			
			if($data['pay_type'] == 'weixin' && $return['openid']){
				$now_user = D('User')->get_user($return['openid'],'openid'); 
			}else if($data['pay_type'] == 'alipay' && $return['alipay_uid']){
				$now_user = D('User')->get_user($return['alipay_uid'],'alipay_uid'); 
			}
		}elseif($this->config['open_weikebaba'] == 1 && $this->config['weikebaba_system_user_id']){
            $lowfeepay = new LowFeePay('weikebaba');
            $order_info['re_call'] = $_POST['re_call'];
            $order_info['auth_code'] = $_POST['auth_code'];
            $order_info['is_own'] = 0;
            $order_info['mer_id'] = $now_order['mer_id'];
            $order_info['out_trade_no'] = 'store_' . $now_order['order_id'];
            $order_info['total_fee'] = $now_order['price'];
            $order_info['order_name'] = $now_order['name'] ? $now_order['name'] : $this->store['name'].' - 线下收款';
            $order_info['pay_type'] = $_POST['auth_type']=='alipay' ? 2 : 1;
            $return = $lowfeepay->scan_pay($order_info);
            
            if ($return['return_code'] == 'FAIL') {
                $this->returnCodeError(L_('支付失败！支付返回：') . $return['return_msg']);
            }

            if ($return['err_code'] == 'USERPAYING') {
                $this->returnCode('20130035');
            }
            if ($return['result_code'] == 'FAIL') {
                $this->returnCodeError('支付失败！支付返回：' . $return['err_code_des']);
            }

            if ($return['trade_state'] == 'USERPAYING') {
                $this->returnCode('20130035');
            }

            $data['pay_type'] = $return['pay_type'] ? $return['pay_type'] : 'weixin';
            
            if($data['pay_type'] == 'weixin' && $return['openid']){
                $now_user = D('User')->get_user($return['openid'],'openid'); 
            }else if($data['pay_type'] == 'alipay' && $return['alipay_uid']){
                $now_user = D('User')->get_user($return['alipay_uid'],'alipay_uid'); 
            }
        }elseif($this->config['pay_scrcu_cash_open'] == 1 && $this->config['scrcu_mid']){
        	// 四川农信银行支付 自有支付
            if($now_merchant['scrcu_mer_no']){
                $is_own = 1;
            }
        	import("@.ORG.pay.Scrcu");
            $pay_moodel = new Scrcu();
            if($_POST['re_call']){
	            $return = $pay_moodel->scanQuery($now_order['order_id'], $now_merchant['scrcu_mer_no']);
	            if ($return['error']) {// 正在输入密码
					$this->returnCode('20130035');
	            }
			}else{
				// 付款
	            $return = $pay_moodel->scanPay($_POST['order_type'], $now_order['order_id'], $now_order['price'], $_POST['auth_code'], $now_merchant['scrcu_mer_no'] );
	            if ($return['error']) {
	                $this->returnCodeError(L_('支付失败！支付返回：') . $return['msg']);
	            }

	            sleep(1);
	            // 查询
	            $return = $pay_moodel->scanQuery($now_order['order_id'], $now_merchant['scrcu_mer_no']); 
	            if ($return['error']) {// 正在输入密码
					$this->returnCode('20130035');
	            }

			}

            $data['pay_type'] = $return['pay_type'] ? $return['pay_type'] : 'weixin';

        }else{
			if($_POST['auth_code'] && (substr($_POST['auth_code'],0,1)==2 || substr($_POST['auth_code'],0,1)==3)){
				!$this->config['arrival_alipay_open'] && $this->returnCode('20130046');
				// 开启到店付，线下零售绑定用户，使用优惠
				$this->store_notice($now_order, true);

				$this->store_arrival_alipay_pay($order_id,$now_order);die;
			}

			$now_merchant = D('Merchant')->get_info($now_order['mer_id']);
			$sub_mch_pay = false;

			if($this->config['city_ownpay']){

				$merchant_ownpay = D('City_ownpay')->field('city_id',true)->where(array('city_id'=>$now_merchant['city_id']))->find();
				foreach($merchant_ownpay as $ownKey=>$ownValue){
					$ownValueArr = unserialize($ownValue);
					if($ownValueArr['open']){
						if(strpos($ownKey,'app')){
							continue;
						}
						if($ownKey=='weixin'){
							$ownValueArr['is_own'] = 6;
						}else{
							$ownValueArr['is_own'] = true;
						}
						$pay_method[$ownKey] = array('name'=>$this->getPayName($ownKey),'config'=>$ownValueArr);
					}
				}

				$this->config['pay_weixin_mchid'] = $this->config['pay_weixin_sp_mchid'];
				$this->config['pay_weixin_key'] = $this->config['pay_weixin_sp_key'];
				$sub_mch_pay = true;
				$sub_mch_id = $pay_method['weixin']['sub_mch_id'];
				$is_own = 6 ;
			}
			if ($this->config['open_sub_mchid'] && $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0) {
				$this->config['pay_weixin_mchid'] = $this->config['pay_weixin_sp_mchid'];
				$this->config['pay_weixin_key'] = $this->config['pay_weixin_sp_key'];
				$sub_mch_pay = true;
				$sub_mch_id = $now_merchant['sub_mch_id'];
				$is_own = 2 ;
			}

			$param = array();
			$param['appid'] = $this->config['pay_weixin_appid'];
			$param['mch_id'] = $this->config['pay_weixin_mchid'];
			$param['nonce_str'] = $this->createNoncestr();
			$param['auth_code'] = $_POST['auth_code'];
			$param['sign'] = $this->getWxSign($param);
			$return_openid_from_auth_code = Http::curlPostXml('https://api.mch.weixin.qq.com/tools/authcodetoopenid', $this->arrayToXml($param));
			if($return_openid_from_auth_code['return_code']=='SUCCESS' && $return_openid_from_auth_code['result_code'] =='SUCCESS'){
				$tmp_user_openid = $return_openid_from_auth_code['openid'];
			}
			$now_user = D('User')->get_user($tmp_user_openid,'openid');
			$now_order['order_type'] = 'store';
			if($now_user){
				if($this->config['san_pay_score_coupon']==1 && $now_merchant['san_pay_score_coupon']) {
					$check_result = D('Store_order')->check_coupon_score($now_user, $now_order);
					$now_order['card_id'] = $check_result['card_id'];
					$now_order['coupon_id'] = $check_result['coupon_id'];
					$now_order['score_used_count'] = $check_result['score_used_count'];
					$now_order['card_price'] = $check_result['card_price'];
					$now_order['coupon_price'] = $check_result['coupon_price'];
					$now_order['score_deducte'] = $check_result['score_deducte'];
					if ($now_order['card_id']) {
						$now_coupon = D('Card_new_coupon')->get_coupon_by_id($now_order['card_id']);
						if (empty($now_coupon)) {

							$this->error(L_('支付异常：卡券不存在'));
						}
					}

					//判断平台优惠券
					if ($now_order['coupon_id']) {
						$now_coupon = D('System_coupon')->get_coupon_by_id($now_order['coupon_id']);
						if (empty($now_coupon)) {
							$this->error(L_('支付异常：系统卡券错误'));
						}
					}

					$score_used_count = $now_order['score_used_count'];
					if ($now_user['score_count'] < $score_used_count) {
						$this->error(L_('支付异常：积分异常'));
					}


					$now_order['price'] = $check_result['total_money'];
				}
			}else{
				$access_token_array = D('Access_token_expires')->get_access_token();
				if (!$access_token_array['errcode']) {
					$result_user = $http->curlGet('https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token_array['access_token'].'&openid='.$tmp_user_openid.'&lang=zh_CN');
					$userinfo = json_decode($result_user,true);
					if(empty($userinfo['errcode'])) {
						//商家推广绑定关系
						D('Merchant_spread')->spread_add($now_order['mer_id'], $userinfo['openid'], 'storepay');
						$data_user = array(
							'openid' => $userinfo['openid'],
							'union_id' => ($userinfo['unionid'] ? $userinfo['unionid'] : ''),
							'nickname' => $userinfo['nickname'],
							'sex' => $userinfo['sex'],
							'province' => $userinfo['province'],
							'city' => $userinfo['city'],
							'avatar' => $userinfo['headimgurl'],
							'is_follow' => $userinfo['subscribe'],
							'source' => 'pc_staff_pay_auto2',
						);
						$reg_result = D('User')->autoreg($data_user);
						if ($reg_result['error_code']) {
							$now_user['uid'] = '0';
						} else {
							$now_user = D('User')->get_user($userinfo['openid'], 'openid');
						}
					}
				}else{
					$now_user['uid'] = '0';
				}
			}
			$success_pay_order = false;
			if($now_order['price']>0) {
				if($_POST['re_call']){
					$param = array();
					$param['appid'] = $this->config['pay_weixin_appid'];
					$param['mch_id'] = $this->config['pay_weixin_mchid'];
					$param['nonce_str'] = $this->createNoncestr();
					$param['out_trade_no'] = 'store_'.$now_order['orderid'];
					if($sub_mch_pay){
						$param['out_trade_no'] = 'store_'.$now_order['order_id'].'_1';
						$param['sub_mch_id'] = $sub_mch_id;
					}
					$param['sign'] = $this->getWxSign($param);
					$return = Http::curlPostXml('https://api.mch.weixin.qq.com/pay/orderquery', $this->arrayToXml($param));
				}else{


					$param = array();
					$param['appid'] = $this->config['pay_weixin_appid'];
					$param['mch_id'] = $this->config['pay_weixin_mchid'];
					$param['nonce_str'] = $this->createNoncestr();
					$param['body'] = $now_order['name'];
					$param['out_trade_no'] = 'store_'.$now_order['orderid'];
					$param['total_fee'] = floatval($now_order['price']*100);
					$param['spbill_create_ip'] = get_client_ip();
					$param['auth_code'] = $_POST['auth_code'];
					if($sub_mch_pay){
						$param['out_trade_no'] = 'store_'.$now_order['order_id'].'_1';
						$param['sub_mch_id'] = $sub_mch_id;
					}
					$param['sign'] = $this->getWxSign($param);
					$return = Http::curlPostXml('https://api.mch.weixin.qq.com/pay/micropay', $this->arrayToXml($param));
				}

				if($return['return_code'] == 'FAIL'){

					$this->returnCode('1',array(),$return['return_msg']);
				}
				if($return['trade_state'] == 'NOTPAY' ||$return['err_code']=='NOTPAY' ){
					$data['orderid'] = date("YmdHis") . mt_rand(10000000, 99999999);
					M('Store_order')->where(array('order_id'=>$order_id))->save($data);
					$this->returnCode('20130036');
				}else if($return['trade_state'] == 'USERPAYING'||$return['err_code']=='USERPAYING'){
					$this->returnCode('20130035');
				}else if($return['trade_state'] == 'PAYERROR'){
					$this->returnCode('20130040','',$return['trade_state_desc']);
				}else if($return['trade_state'] == 'NOTENOUGH'){
					$this->returnCode('20130041');
				}

				if($return['result_code'] == 'FAIL'){
					$this->returnCode('1',array(),$return['err_code_des']);
				}

				$data['pay_type'] = 'weixin';
				$success_pay_order = true;
			}else{
				$success_pay_order = true;
				$data['pay_type'] = '';
			}

			if($success_pay_order) {
				if ($now_order['card_id']) {
					$use_result = D('Card_new_coupon')->user_coupon($now_order['card_id'], $now_order['order_id'], 'store', $now_order['mer_id'], $now_user['uid']);
					if ($use_result['error_code']) {
						$this->error(L_('支付异常：卡券抵扣错误'));
					} else {
						$data['card_id'] = $check_result['card_id'];
						$data['card_price'] = $check_result['card_price'];
					}
				}

				//如果使用了平台优惠券
				if ($now_order['coupon_id']) {
					$use_result = D('System_coupon')->user_coupon($now_order['coupon_id'], $now_order['order_id'], 'store', $now_order['mer_id'], $now_user['uid']);
					if ($use_result['error_code']) {
						$this->error(L_('支付异常：系统卡券抵扣错误'));
					} else {
						$data['coupon_id'] = $check_result['coupon_id'];
						$data['coupon_price'] = $check_result['coupon_price'];
					}
				}

				if ($score_used_count > 0) {
					$use_result = D('User')->user_score($now_user['uid'], $score_used_count, L_('购买 X1 扣除X2'  ,array('X1'=>$now_order['name'],'X2'=>C('config.score_name'))));
					if ($use_result['error_code']) {
						$this->error(L_('支付异常：积分扣除异常'));
					} else {
						$data['score_used_count'] = $check_result['score_used_count'];
						$data['score_deducte'] = $check_result['score_deducte'];
					}
				}

				if(floatval($now_order['merchant_balance']) > 0){
					$use_result = D('Card_new')->use_money($now_order['uid'], $now_order['mer_id'], $now_order['merchant_balance'], L_('购买 X1 扣除会员卡余额',$now_order['real_orderid']));
					if($use_result['error_code']){
						$this->error(L_('支付异常：会员卡余额扣除异常'));
					}
				}

				if(floatval($now_order['card_give_money']) > 0){
					$use_result = D('Card_new')->use_give_money($now_order['uid'], $now_order['mer_id'], $now_order['card_give_money'], L_('购买 X1 扣除会员卡赠送余额',$now_order['real_orderid']));
					if ($use_result['error_code']) {
						$this->error(L_('支付异常：会员卡赠送余额扣除异常'));
					}
				}
			}
        }

        $data['uid'] = $now_user ? $now_user['uid'] : 0;
        $data['paid'] = '1';
        $data['pay_time'] = time();
        $data['payment_money'] = $return['total_fee']/100;
        $data['third_id'] = $return['transaction_id'];
		$data['is_own'] = $return['is_own'] ? $return['is_own'] : $is_own;
        $now_order['price'] = $data['payment_money'] + $data['score_deducte'] + $data['card_price'] + $data['coupon_price'];
        if(M("Store_order")->where(array('order_id'=>$order_id))->data($data)->save()){
			$order = M("Store_order")->where(array('order_id'=>$order_id))->find();
	        $now_order['uid'] = $order['uid'];
			if($order['business_type']){
				switch($order['business_type']){
					case 'foodshop':
						D('Foodshop_order')->store_order_after_call($order);
						break;
				}
			}

            if(C('config.merchant_buy_points_switch')!=1){ // 没有开启商家积分
                if($this->config['add_score_by_system_commission']==0 && $this->config['add_score_by_percent']==0 && ($this->config['score_get_times']==0 || !isset($this->config['score_get_times']))){
        			if($is_own && $this->config['user_own_pay_get_score']!=1){
        				$order['payment_money'] = 0;
        			}
                    if(isset($this->config['store_score_get_percent'])){
                        if($this->config['store_score_get_percent']==1){
                            $this->config['score_get'] = $this->config['store_score_percent']/100;
                        }else{
                            $this->config['score_get'] =  $this->config['user_store_score_get'];
                        }
                    }
                    $this->config['score_get'] = D('Percent_rate')->get_user_add_score_times($this->config['score_get'],$order['uid']);

                    if($this->config['score_round_two']==1){
                        M('Store_order')->where(array('order_id'=>$order_id))->setField('score_give',round(($order['payment_money'] + $order['balance_pay']) * $this->config['score_get'],2));
                    }else{
                        M('Store_order')->where(array('order_id'=>$order_id))->setField('score_give',round(($order['payment_money'] + $order['balance_pay']) * $this->config['score_get']));
                    }
					fdump_api([$now_order['uid'],'增加积分'], 'score/store_arrival_pay', 1);
                    D('User')->add_score(
                        $now_order['uid'], 
                        (($order['payment_money'] + $order['balance_pay'])  * $this->config['score_get']), 
                        L_('在X1 中使用到店消费支付了X2 获得X3',array('X1'=>$this->store['name'],'X2'=> C('config.Currency_symbol') . floatval($now_order['price']),'X3'=>$this->config['score_name']))
                    );

                    D('Scroll_msg')->add_msg(
                        'store',
                        $now_user['uid'],
                        L_('用户X1于X2在X3中使用到店消费获得X4',array('X1'=>str_replace_name($now_user['nickname']),'X2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),'X3'=>$this->store['name'],'X4'=>$this->config['score_name']))

                    );
                }
                D('Userinfo')->add_score(
                    $now_order['uid'],
                    $now_order['mer_id'], 
                    $now_order['price'], 
                    L_('在 X1 中使用到店消费支付了X2获得积分',array('X1'=>$this->store['name'],'X2'=>C('config.Currency_symbol') . floatval($now_order['price'])))
                );
            }

            D('Store_order')->store_score_get($now_order); // 增加积分

            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
            $href = C('config.site_url').'/wap.php?c=My&a=store_order_list';
            $model->sendTempMsg('OPENTM201752540', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' => $this->store['name'], 'keyword1' => L_('店内消费支付提醒'), 'keyword2' => $now_order['orderid'], 'keyword3' => $now_order['price'], 'keyword4' => date('Y-m-d H:i:s'), 'remark' => L_('付款成功，感谢您的使用')), $now_order['mer_id']);
			$now_order = M("Store_order")->where(array('order_id'=>$order_id))->find();
			$now_order['order_type']='cash';
			//商家余额增加
			//D('Merchant_money_list')->add_money($now_order['mer_id'],L_('用户到店支付记入收入'),$now_order);
            $now_order['desc']='用户到店支付记入收入';
            D('SystemBill')->bill_method($now_order['is_own'],$now_order);

			//商家推广关系
			D('Merchant_spread')->add_spread_list($now_order,$now_user,$now_order['order_type'],$now_user['nickname'].L_('用户到店支付获得佣金'));

            // 订单改变之后调用
            D("Store_order")->after_store_change($order_id,'cash',2);
            $this->returnCode(0);
        }else{
            $this->returnCode('20130034');
        }
    }

    public function store_arrival_qiye_pay($orderid, $nowOrder, $tableName, $data = array())
    {
        if ($nowOrder['price'] > 0) {
            if (!(D('Qiye')->checkTheSameQiye($data['uid'], $nowOrder['mer_id']))) {
                //如果用户余额足够支付则使用平台余额支付
                $now_user = D('User')->get_user($data['uid'], 'uid');
                $data['uid'] = $now_user['uid'];
                $data['name'] = $now_user['nickname'];
                $data['phone'] = $_POST['phone'];
                if ($now_user['now_money'] < $nowOrder['price']) {
                    $this->returnCode(1, '', '同一企业商家和用户才能使用企业预存款！');
                }
                // 开启到店付，线下零售绑定用户，使用优惠
                $this->store_notice($nowOrder, true);
                $this->store_arrival_balance_pay($orderid, $nowOrder, $tableName, $data);
                die;
            }

            $orderType = (new OrderModel())->getOrderTypeByTableName($tableName);
            $res = D('User')->use_qiye_money($data['uid'], $nowOrder['price'], $orderid, L_('买单支付使用企业预存款余额'),$orderType,true);
            if (!$res['error_code']) {
                $data['paid'] = 1;
                $data['pay_type'] = 'qiye';
                $data['pay_time'] = time();
                $data['use_time'] = time();
                $data['qiye_pay'] = $res['retval']['qiye_money'];
                $data['balance_pay'] = $res['retval']['balance_money'];
                $data['qiye_settlement_ratio'] = $res['retval']['qiye_settlement_ratio'];
                $data['qiye_settlement_money'] = $res['retval']['qiye_settlement_money'];
                if (M($tableName)->where(['order_id' => $orderid])->data($data)->save()) {
                    $now_order = M($tableName)->where(['order_id' => $orderid])->find();
                    $now_order['order_type'] = 'cash';
                    D('SystemBill')->bill_method($now_order['is_own'], $now_order);

                    if ($now_order['business_type']) {
                        switch ($now_order['business_type']) {
                            case 'foodshop':
                                $now_food_order = D('Foodshop_order')->where(array('order_id' => $now_order['business_id']))->find();
                                D('Foodshop_order')->after_pay($now_order['business_id'], null, 1);
                                M('Foodshop_order')->where(array('order_id' => $now_order['business_id']))->data(array('name' => $data['name'], 'phone' => $data['phone'], 'status' => 3, 'uid' => $data['uid'], 'price' => $now_order['price'] + $now_food_order['book_price'], 'pay_type' => '1', 'payment_method' => ''))->save();
                                break;
                        }
                    }
                    D('Store_order')->store_score_get($now_order); // 增加积分
                    // 订单改变之后调用
                    D("Store_order")->after_store_change($orderid, 'cash', 2);

                    $this->returnCode(0);
                }
            } else {
                $this->returnCode(1,'',$res['msg']);
            }
        }
    }

    public function store_arrival_balance_pay($order_id, $now_order, $table_name, $data = array())
    {
        if (!$this->config['open_balance_pay_unverify']) { // 是否开启线下零售、到店付余额支付不使用短信验证码
            $laste_sms = M('Merchant_sms_record')->where(array('mer_id' => $this->store['mer_id'],'extra'=>$data['smscode'],'type'=>5,'status'=>0))->setField('status',1);
            if (!$laste_sms) {
                $this->returnCode(1,'',L_('短信验证失败！'));
            }
        }
        if($now_order['price']>0){
            $res = D('User')->user_money($data['uid'],$now_order['price'],$this->config['cash_alias_name'].L_('使用平台余额'));
            if(!$res['error_code']){
                $data['paid'] = 1;
                $data['pay_time'] = time();
                $data['use_time'] = time();
                $data['balance_pay'] = $now_order['price'];

                if (M($table_name)->where(array('order_id' => $order_id))->data($data)->save()) {
                    M('System_order')->where(['type' => 'cash', 'order_id' => $order_id])->data(['uid' => $data['uid']])->save();
                    $now_order = M($table_name)->where(array('order_id' => $order_id))->find();
                    if($now_order['paid'] != 1){//数据库读写分离，主从复制速度没跟上，手动更新订单查询的信息
                        foreach ($data as $kData=>$vData){
                            $now_order[$kData]=$vData;
                        }
                    }
                    $now_order['order_type'] = 'cash';

                    D('SystemBill')->bill_method($now_order['is_own'],$now_order);

                    if($now_order['business_type']){
                        switch($now_order['business_type']){
                            case 'foodshop':
                                $now_food_order = D('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->find();
                                D('Foodshop_order')->after_pay($now_order['business_id'], null, 1);
                                M('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->data(array('name'=>$data['name'],'phone'=>$data['phone'],'status'=>3,'uid'=>$data['uid'],'price'=>$now_order['price']+$now_food_order['book_price'],'pay_type'=>'1','payment_method'=>''))->save();
                                break;
                        }
                    }

                    if($this->config['merchant_buy_points_switch']!=1){
                        if($this->config['add_score_by_system_commission']==0 && $this->config['add_score_by_percent']==0 && ($this->config['open_score_discount']==0 || $now_order['score_discount_type']!=2) && ($this->config['score_get_times']==0 || !isset($this->config['score_get_times']))){
                            $now_user  = D('User')->get_user($now_order['uid']);
                            if($this->config['store_score_get_percent']==1){
                                $score_get =$this->config['store_score_percent']/100;
                            }else{
                                $score_get = $this->config['user_store_score_get'];
                            }

                            if($now_order['is_own'] && $this->config['user_own_pay_get_score']!=1 ){
                                $now_order['payment_money'] = 0;
                            }
                            $score_get = D('Percent_rate')->get_user_add_score_times($score_get,$now_order['uid']);

                            D('User')->add_score(
                                $now_order['uid'], 
                                (($now_order['payment_money'] + $now_order['balance_pay'])* $score_get), 
                                L_('在X1使用X2消费X3获得X4',array('X1'=>$this->store['name'],'X2'=>$this->config['cash_alias_name'],'X3'=>floatval($now_order['price']) . C('config.Currency_txt'),'X4'=>$this->config['score_name']))
                            );
                            D('Scroll_msg')->add_msg(
                                'meal',
                                $now_user['uid'],
                                L_('用户X1于X2购买 X3成功并消费获得X4',array('X1'=>str_replace_name($now_user['nickname']),'X2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),'X3'=>$now_order['order_name'],'X4'=>$this->config['score_name']))
                            );
                        }
                    }

                    D('Store_order')->store_score_get($now_order); // 增加积分

                    // 订单改变之后调用
                    D("Store_order")->after_store_change($order_id,'cash',2);

                    $this->returnCode(0);
                }else{
                    fdump(M(),'mmm');
                }
            }else {
                $this->returnCode(1,'',$res['msg']);
            }

        }
    }
    
    public function store_arrival_ecard_pay($order_id, $now_order, $table_name, $data = array())
    {
        if($now_order['price']>0){
            $business = 'store';
            foreach($data['internal_card_pay'] as $card_id => $money){
                $res = D('Internal_card')->use_money($card_id, $money,$business,$now_order);
            }
            if(!$res['error_code']){
                $data['paid'] = 1;
                $data['pay_type'] = 'ecard';
                $data['pay_time'] = time();
                $data['use_time'] = time();
                $data['internal_card_pay'] = json_encode($data['internal_card_pay']);
                if (M($table_name)->where(array('order_id' => $order_id))->data($data)->save()) {
                    //平台优惠券使用
                    if(isset($data['coupon_id']) && $data['coupon_id'] > 0){
                        D('System_coupon')->user_coupon($data['coupon_id'],$order_id,'store',$now_order['mer_id'],$now_order['uid']);
                    }

                    M('System_order')->where(['type' => 'cash', 'order_id' => $order_id])->data(['uid' => $data['uid']])->save();
                    $now_order = M($table_name)->where(array('order_id' => $order_id))->find();
                    if($now_order['paid'] != 1){
                        foreach ($data as $kData=>$vData){
                            $now_order[$kData]=$vData;
                        }
                    }
                    $now_order['order_type'] = 'cash';
                    if($now_order['business_type']){
                        switch($now_order['business_type']){
                            case 'foodshop':
                                $now_food_order = D('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->find();
                                D('Foodshop_order')->after_pay($now_order['business_id'], null, 1);
                                M('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->data(array('name'=>$data['name'],'phone'=>$data['phone'],'status'=>3,'uid'=>$data['uid'],'price'=>$now_order['price']+$now_food_order['book_price'],'pay_type'=>'1','payment_method'=>''))->save();
                                break;
                        }
                    }

                    if($this->config['merchant_buy_points_switch']!=1){
                        if($this->config['add_score_by_system_commission']==0 && $this->config['add_score_by_percent']==0 && ($this->config['open_score_discount']==0 || $now_order['score_discount_type']!=2) && ($this->config['score_get_times']==0 || !isset($this->config['score_get_times']))){
                            $now_user  = D('User')->get_user($now_order['uid']);
                            if($this->config['store_score_get_percent']==1){
                                $score_get =$this->config['store_score_percent']/100;
                            }else{
                                $score_get = $this->config['user_store_score_get'];
                            }

                            if($now_order['is_own'] && $this->config['user_own_pay_get_score']!=1 ){
                                $now_order['payment_money'] = 0;
                            }
                            $score_get = D('Percent_rate')->get_user_add_score_times($score_get,$now_order['uid']);

                            D('User')->add_score(
                                $now_order['uid'],
                                (($now_order['payment_money'] + $now_order['balance_pay'])* $score_get),
                                L_('在X1使用X2消费X3获得X4',array('X1'=>$this->store['name'],'X2'=>$this->config['cash_alias_name'],'X3'=>floatval($now_order['price']) . C('config.Currency_txt'),'X4'=>$this->config['score_name']))
                            );
                            D('Scroll_msg')->add_msg(
                                'meal',
                                $now_user['uid'],
                                L_('用户X1于X2购买 X3成功并消费获得X4',array('X1'=>str_replace_name($now_user['nickname']),'X2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']),'X3'=>$now_order['order_name'],'X4'=>$this->config['score_name']))
                            );
                        }
                    }

                    //商家余额增加
                    $now_order['desc'] = L_('用户到店支付记入收入');
                    D('SystemBill')->bill_method($now_order['is_own'],$now_order);

                    D('Store_order')->store_score_get($now_order); // 增加积分

                    // 订单改变之后调用
                    D("Store_order")->after_store_change($order_id,'cash',2);

                    $printHaddle = new PrintHaddle();
                    $printHaddle->printit($now_order['order_id'], 'store_order', 1);

                    $this->returnCode(0);
                }else{
                    fdump(M(),'mmm');
                }
            }else {
                $this->returnCode(1,'',$res['msg']);
            }
        }
    }
    public function arrival_yzfpay_pay($order_id,$now_order,$table_name='',$data=array()){
        $mer_id =$this->store['mer_id'];
        $now_merchant = D('Merchant')->get_info($this->store['mer_id']);
        $this->config['merchant_ownpay'] = intval($this->config['merchant_ownpay']);
        switch($this->config['merchant_ownpay']){
            case '0':
                $pay_method = D('Config')->get_pay_method(0, 0, true);
                break;
            case '1':
                $pay_method = D('Config')->get_pay_method(0, 0, true);
                if($mer_id){
                    $merchant_ownpay = D('Merchant_ownpay')->field('mer_id',true)->where(array('mer_id'=>$mer_id))->find();
                    foreach($merchant_ownpay as $ownKey=>$ownValue){
                        $ownValueArr = unserialize($ownValue);
                        if($ownValueArr['open']){
                            $ownValueArr['is_own'] = true;
                            $pay_method[$ownKey] = array('name'=>D('Pay')->getPayName($ownKey),'config'=>$ownValueArr);
                        }
                    }
                }
                break;
            case '2':
                $pay_method = array();
                if($mer_id){
                    $merchant_ownpay = D('Merchant_ownpay')->field('mer_id',true)->where(array('mer_id'=>$mer_id))->find();
                    foreach($merchant_ownpay as $ownKey=>$ownValue){
                        $ownValueArr = unserialize($ownValue);
                        if($ownValueArr['open']){
                            $ownValueArr['is_own'] = true;
                            $pay_method[$ownKey] = array('name'=>D('Pay')->getPayName($ownKey),'config'=>$ownValueArr);
                        }
                    }
                }
                if(empty($pay_method)){
                    $pay_method = D('Config')->get_pay_method(0, 0, true);
                }
                break;
        }
        import('@.ORG.pay.Yzfpay');


        $now_order['order_name'] = $now_order['name']?$now_order['name']: $now_merchant['name'];
        $now_order['staff_id'] = $this->staff_session['id'];
        $now_order['platform'] ="APP";
        $now_order['order_trade_no'] ="shop_".$now_order['orderid'].($pay_method['yzfpay']['config']['is_own']?'_1':'');
        $pay_class = new Yzfpay($now_order,$now_order['price'], 'yzfpay', $pay_method['yzfpay']['config'],0,1);
        $go_query_param = $pay_class->scan_barcode_pay($_POST['auth_code']);
        fdump_sql($go_query_param,'yzf_scan');
        if($go_query_param['success']==true && $go_query_param['result']['tradeStatus']=='SUCCESS'){
            $data['paid'] = '1';
            $data['status'] = 2;
            $data['order_status'] = 5;
            $data['pay_time'] = strtotime($go_query_param['result']['tradeFinishedDate']);
            $data['pay_type'] = 'yzfpay_offline';
            $data['third_id'] = $go_query_param['result']['tradeNo'];
            $data['payment_money'] = $go_query_param['result']['payAmt']/100;
            $data['is_own'] =$pay_method['yzfpay']['config']['is_own']?1:0 ;

            if (M($table_name)->where(array('order_id'=>$order_id))->data($data)->save()) {
                $now_order = M($table_name)->where(array('order_id' => $order_id))->find();
                $this->shop_notice($now_order, true);
                // D('Shop_order')->shop_score_get($now_order);
                $this->returnCode(0);
            } else {
                $this->returnCode(222,'',L_(L_('支付失败！请联系管理员处理。')));

            }
        }else   if($go_query_param['success']==true && $go_query_param['result']['tradeStatus']=='WAITFORPAY'){
            sleep(2);
            $go_query_param = $pay_class->query_order($go_query_param['result']['outTradeNo'],  date("Y-m-d H:i:s",strtotime($go_query_param['result']['tradeFinishedDate'])),3);

            fdump($go_query_param,'ress');
            if($go_query_param['error']==0){
                $data['paid'] = '1';
                $data['status'] = 2;
                $data['order_status'] = 5;
                $data['pay_time'] = strtotime($go_query_param['pay_time']);
                $data['pay_type'] = 'yzfpay_offline';
                $data['third_id'] = $go_query_param['third_id'];
                $data['payment_money'] = $go_query_param['pay_money'];
                $data['is_own'] =$go_query_param['is_own'];


                if (M('Shop_order')->where(array('order_id'=>$go_query_param['order_id']))->data($data)->save()) {
                    $now_order = M('Shop_order')->where(array('order_id' => $order_id))->find();
                    $this->shop_notice($now_order, true);
                    // D('Shop_order')->shop_score_get($now_order);
                    $this->returnCode(0);
                } else {
                    fdump(M(),'ress',1);
                    $this->returnCode(222,'','支付失败！请联系管理员处理。');

                }
            }else{

                $this->returnCode(222,'',L_('需要用户确认支付，请在用户成功支付后再点击支付按钮。'));
            }
        }else{	//需要用户处理，下次查询订单
            $this->returnCode(222,'',$go_query_param['errorMsg']);
        }
    }

    public function store_arrival_yzfpay_pay($order_id,$now_order){
        $mer_id =$this->store['mer_id'];
        $now_merchant = D('Merchant')->get_info($this->store['mer_id']);
//		if(empty($this->config['arrival_alipay_open'])){
//			$this->error('平台未开启支付宝收银');
//		}
        $this->config['merchant_ownpay'] = intval($this->config['merchant_ownpay']);
        switch($this->config['merchant_ownpay']){
            case '0':
                $pay_method = D('Config')->get_pay_method(0, 0, true);
                break;
            case '1':
                $pay_method = D('Config')->get_pay_method(0, 0, true);
                if($mer_id){
                    $merchant_ownpay = D('Merchant_ownpay')->field('mer_id',true)->where(array('mer_id'=>$mer_id))->find();
                    foreach($merchant_ownpay as $ownKey=>$ownValue){
                        $ownValueArr = unserialize($ownValue);
                        if($ownValueArr['open']){
                            $ownValueArr['is_own'] = true;
                            $pay_method[$ownKey] = array('name'=>D('Pay')->getPayName($ownKey),'config'=>$ownValueArr);
                        }
                    }
                }
                break;
            case '2':
                $pay_method = array();
                if($mer_id){
                    $merchant_ownpay = D('Merchant_ownpay')->field('mer_id',true)->where(array('mer_id'=>$mer_id))->find();
                    foreach($merchant_ownpay as $ownKey=>$ownValue){
                        $ownValueArr = unserialize($ownValue);
                        if($ownValueArr['open']){
                            $ownValueArr['is_own'] = true;
                            $pay_method[$ownKey] = array('name'=>D('Pay')->getPayName($ownKey),'config'=>$ownValueArr);
                        }
                    }
                }
                if(empty($pay_method)){
                    $pay_method = D('Config')->get_pay_method(0, 0, true);
                }
                break;
        }
        import('@.ORG.pay.Yzfpay');


        $now_order['order_name'] = $now_order['name']?$now_order['name']: $now_merchant['name'];
        $now_order['staff_id'] = $this->staff_session['id'];
        $now_order['platform'] ="APP";
        $now_order['order_trade_no'] ="store_".$now_order['orderid'].($pay_method['yzfpay']['config']['is_own']?'_1':'');
        $pay_class = new Yzfpay($now_order,$now_order['price'], 'yzfpay', $pay_method['yzfpay']['config'],0,1);
        $go_query_param = $pay_class->scan_barcode_pay($_POST['auth_code']);
        fdump_sql($go_query_param,'yzf_scan');
        if($go_query_param['success']==true && $go_query_param['result']['tradeStatus']='SUCCESS'){
            $data['paid'] = '1';
            $data['pay_time'] = strtotime($go_query_param['result']['tradeFinishedDate']);
            $data['pay_type'] = 'yzfpay_offline';
            $data['third_id'] = $go_query_param['result']['tradeNo'];
            $data['payment_money'] =$go_query_param['result']['payAmt']/100;
            $data['is_own'] =$pay_method['yzfpay']['config']['is_own']?1:0 ;

            if(M("Store_order")->where(array('order_id'=>$order_id))->data($data)->save()){
                $now_order = M("Store_order")->where(array('order_id'=>$order_id))->find();
                $now_order['order_type']='cash';
                //商家余额增加
                //D('Merchant_money_list')->add_money($now_order['mer_id'],'用户到店支付宝支付记入收入',$now_order);
                $now_order['desc']=L_('用户到店翼支付记入收入');
                D('SystemBill')->bill_method($now_order['is_own'],$now_order);

                if($now_order['business_type']){
                    switch($now_order['business_type']){
                        case 'foodshop':
                            $now_food_order = D('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->find();
                            D('Foodshop_order')->after_pay($now_order['business_id'], null, 1);
                            M('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->data(array('price'=>$now_order['payment_money']+$now_food_order['book_price'],'pay_type'=>'1','payment_method'=>$data['pay_type']))->save();
                            break;
                    }
                }

                D('Store_order')->store_score_get($now_order); // 增加积分

                // 订单改变之后调用
                D("Store_order")->after_store_change($order_id,'cash',2);

                $this->returnCode(0);
            }else{
                $this->returnCode(1,'',L_('支付失败！请联系管理员处理。'));
            }
        } else if($go_query_param['success']==true && $go_query_param['result']['tradeStatus']='WAITFORPAY'){//需要用户处理，下次查询订单

            sleep(2);
            $go_query_param = $pay_class->query_order($go_query_param['result']['outTradeNo'],  date("Y-m-d H:i:s",strtotime($go_query_param['result']['tradeFinishedDate'])),3);

            fdump($go_query_param,'param');
            if($go_query_param['error']==0){
                $data['paid'] = '1';
                $data['pay_time'] = strtotime($go_query_param['pay_time']);
                $data['pay_type'] = 'yzfpay_offline';
                $data['third_id'] = $go_query_param['third_id'];
                $data['payment_money'] =$go_query_param['pay_money'];
                $data['is_own'] =$go_query_param['is_own'];

                if(M("Store_order")->where(array('order_id'=>$go_query_param['order_id']))->data($data)->save()){
                    $now_order = M("Store_order")->where(array('order_id'=>$go_query_param['order_id']))->find();
                    $now_order['order_type']='cash';
                    //商家余额增加
                    //D('Merchant_money_list')->add_money($now_order['mer_id'],'用户到店支付宝支付记入收入',$now_order);
                    $now_order['desc']=L_('用户到店翼支付记入收入');
                    D('SystemBill')->bill_method($now_order['is_own'],$now_order);
                    if($now_order['business_type']){
                        switch($now_order['business_type']){
                            case 'foodshop':
                                $now_food_order = D('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->find();
                                D('Foodshop_order')->after_pay($now_order['business_id'], null, 1);
                                M('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->data(array('price'=>$now_order['payment_money']+$now_food_order['book_price'],'pay_type'=>'1','payment_method'=>$data['pay_type']))->save();
                                break;
                        }
                    }
                    // D('Store_order')->store_score_get($now_order); // 增加积分
                    $this->returnCode(0);
                }else{
                    $this->returnCode(1,'',L_('支付失败！请联系管理员处理。'));
                }
            }else{
                $this->returnCode(222,'',L_('需要用户确认支付，请在用户成功支付后再点击支付按钮。'));
            }


        }else{	//需要用户处理，下次查询订单

            $this->returnCode(1,'',$go_query_param['errorMsg']);
        }
    }

    public function store_arrival_offline_pay($order_id,$now_order){
        $data['paid'] = '1';
        $data['pay_time'] = $_SERVER['REQUEST_TIME'];
        $data['pay_type'] = 'offline';
        $data['offline_pay'] = $_POST['offline_pay'];
        if(M("Store_order")->where(array('order_id'=>$order_id))->data($data)->save()){
            $now_order = M("Store_order")->where(array('order_id'=>$order_id))->find();
            if($now_order['business_type']){
                switch($now_order['business_type']){
                    case 'foodshop':
                        $now_food_order = D('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->find();
                        D('Foodshop_order')->after_pay($now_order['business_id'], null, 1);
                        M('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->data(array('price'=>$now_order['price']+$now_food_order['book_price'],'pay_type'=>'1','payment_method'=>$data['pay_type']))->save();
                        break;
                }
            }

            D('Store_order')->store_score_get($now_order); // 增加积分
            
            // 订单改变之后调用
            D("Store_order")->after_store_change($order_id,'cash',2);

            $this->returnCode(0);
        }else{
            $this->returnCode('20130034');
        }

    }

    //扫支付宝码支付
    public function store_arrival_alipay_pay($order_id,$now_order){
        $now_merchant = D('Merchant')->get_info($this->store['mer_id']);
        if($this->config['pay_alipay_sp_appid']!=''&& $this->config['pay_alipay_sp_prikey']!='' && $now_merchant['alipay_auth_code']!=''&& $now_merchant['open_sub_mchid']==1){
            $this->store_alipay_face_to_face($order_id,$now_order,$now_merchant['alipay_auth_code']);
            die;
        }
        if(empty($this->config['arrival_alipay_open'])){
            $this->returnCode('20130042');
        }
        $param['app_id'] = $this->config['arrival_alipay_app_id'];
        $param['method'] = 'alipay.trade.pay';
        $param['charset'] = 'utf-8';
		$param['sign_type'] = $this->config['alipay_arrival_encrypt_type'] ? $this->config['alipay_arrival_encrypt_type'] : 'RSA';
        $param['timestamp'] = date('Y-m-d H:i:s');
        $param['version'] = '1.0';
        $biz_content = array(
            'out_trade_no' => 'store_'.$now_order['order_id'],
            'scene' => 'bar_code',
            'auth_code' => $_POST['auth_code'],
            'total_amount' => $now_order['price'],
            'subject' => $now_order['name'],
        );
        $param['biz_content'] = json_encode($biz_content,JSON_UNESCAPED_UNICODE);

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

        $priKey = $this->config['arrival_alipay_app_prikey'];;
        $res = "-----BEGIN RSA PRIVATE KEY-----\n".wordwrap($priKey, 64, "\n", true)."\n-----END RSA PRIVATE KEY-----";

        if(!function_exists('openssl_sign')){
            $this->returnCode('20130043');
        }

        if($param['sign_type'] == 'RSA2'){
			openssl_sign($stringToBeSigned, $sign, $res, OPENSSL_ALGO_SHA256);
		}else{
			openssl_sign($stringToBeSigned, $sign, $res);
		}
		
        if(empty($sign)){
            $this->returnCode('20130044');
        }

        $sign = base64_encode($sign);

        $param['sign'] = $sign;
        $requestUrl = "https://openapi.alipay.com/gateway.do?";
        foreach ($param as $sysParamKey => $sysParamValue) {
            $requestUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
        }
        $requestUrl = substr($requestUrl, 0, -1);
        // echo $requestUrl;die;
        import('ORG.Net.Http');
        $http = new Http();

        $return = Http::curlGet($requestUrl);
        $returnArr = json_decode($return,true);
        if(!empty($returnArr['alipay_trade_pay_response']) && "10000" == $returnArr['alipay_trade_pay_response']['code']){
			$now_user = D('User')->get_user($returnArr['alipay_trade_pay_response']['buyer_user_id'],'alipay_uid'); 
			if($now_user){
				$data['uid'] = $now_user['uid'];
			}
            $data['paid'] = '1';
            $data['pay_time'] = strtotime($returnArr['alipay_trade_pay_response']['gmt_payment']);
            $data['pay_type'] = 'alipay';
            $data['third_id'] = $returnArr['alipay_trade_pay_response']['trade_no'];
            $data['payment_money'] = $returnArr['alipay_trade_pay_response']['receipt_amount'];
            if(M("Store_order")->where(array('order_id'=>$order_id))->data($data)->save()){
                $now_order = M("Store_order")->where(array('order_id'=>$order_id))->find();
                $now_order['order_type']='cash';
                //商家余额增加
                //D('Merchant_money_list')->add_money($now_order['mer_id'],L_('用户到店支付宝支付记入收入'),$now_order);
                $now_order['desc']='用户到店支付宝支付记入收入';
                D('SystemBill')->bill_method($now_order['is_own'],$now_order);

                if($now_order['business_type']){
                    switch($now_order['business_type']){
                        case 'foodshop':
                            $now_food_order = D('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->find();
                            D('Foodshop_order')->after_pay($now_order['business_id'], null, 1);
                            M('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->data(array('price'=>$now_order['payment_money']+$now_food_order['book_price'],'pay_type'=>'1','payment_method'=>$data['pay_type']))->save();
                            break;
                    }
                }

                D('Store_order')->store_score_get($now_order); // 增加积分
                
                // 订单改变之后调用
                D("Store_order")->after_store_change($order_id,'cash',2);

                $this->returnCode('20130033');
            }else{
                $this->returnCode('20130034');
            }
        }else if(!empty($returnArr['alipay_trade_pay_response']) && "10003" == $returnArr['alipay_trade_pay_response']['code']){	//需要用户处理，下次查询订单
            $this->returnCode('20130035');
        }else{
            if($returnArr['alipay_trade_pay_response']['sub_code'] == 'ACQ.TRADE_HAS_SUCCESS' && $now_order['paid'] != 1){
				$now_user = D('User')->get_user($returnArr['alipay_trade_pay_response']['buyer_user_id'],'alipay_uid'); 
				if($now_user){
					$data['uid'] = $now_user['uid'];
				}
                $data['paid'] = '1';
                $data['pay_time'] = strtotime($returnArr['alipay_trade_pay_response']['gmt_payment']);
                $data['pay_type'] = 'alipay';
                $data['third_id'] = $returnArr['alipay_trade_pay_response']['trade_no'];
                $data['payment_money'] = $returnArr['alipay_trade_pay_response']['receipt_amount'];
                if(M("Store_order")->where(array('order_id'=>$order_id))->data($data)->save()){
                    $now_order = M("Store_order")->where(array('order_id'=>$order_id))->find();
                    $now_order['order_type']='cash';
                    //商家余额增加
                    //D('Merchant_money_list')->add_money($now_order['mer_id'],L_('用户到店支付宝支付记入收入'),$now_order);
                    $now_order['desc']='用户到店支付宝支付记入收入';
                    D('SystemBill')->bill_method($now_order['is_own'],$now_order);

                    if($now_order['business_type']){
                        switch($now_order['business_type']){
                            case 'foodshop':
                                $now_food_order = D('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->find();
                                D('Foodshop_order')->after_pay($now_order['business_id'], null, 1);
                                M('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->data(array('price'=>$now_order['payment_money']+$now_food_order['book_price'],'payment_method'=>$data['pay_type']))->save();
                                break;
                        }
                    }

                    D('Store_order')->store_score_get($now_order); // 增加积分

                    // 订单改变之后调用
                    D("Store_order")->after_store_change($order_id,'cash',2);

                    $this->returnCode('20130033');
                }else{
                    $this->returnCode('20130034');
                }
            }else{
                $this->returnCode('20130034','',L_('支付失败！支付宝返回：').$returnArr['alipay_trade_pay_response']['sub_msg']);
            }
        }
    }
    //支付宝子商户
    public function store_alipay_face_to_face($order_id, $now_order,$auth_token){
        import('@.ORG.pay.Aop.AopClient');
        import('@.ORG.pay.Aop.SignData');
        import('@.ORG.pay.Aop.AlipayTradePayRequest');
        $aop = new AopClient ();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $this->config['pay_alipay_sp_appid'];
        $aop->rsaPrivateKey = $this->config['pay_alipay_sp_prikey'];
        $aop->alipayrsaPublicKey=$this->config['pay_alipay_sp_pubkey'];
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='UTF-8';
        $aop->format='json';
        $request = new AlipayTradePayRequest();
        $biz_content = array(
            'out_trade_no' => 'store_'.$now_order['order_id'],
            'scene' => 'bar_code',
            'auth_code' => $_POST['auth_code'],
            'product_code' => 'FACE_TO_FACE_PAYMENT',
            'total_amount' => $now_order['price'],
            'subject' => $now_order['name'] ? $now_order['name'] : L_('现场自助支付'),
        );

        $request->setBizContent(json_encode($biz_content,JSON_UNESCAPED_UNICODE));
        $result = $aop->execute ( $request,$_POST['auth_code'],$auth_token);

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if(!empty($resultCode)&&$resultCode == 10000){
            $data['paid'] = '1';
            $data['pay_time'] = strtotime($result->$responseNode->gmt_payment);
            $data['pay_type'] = 'alipay';
            $data['third_id'] = $result->$responseNode->trade_no;
            $data['payment_money'] = $result->$responseNode->receipt_amount;
            if(M("Store_order")->where(array('order_id'=>$order_id))->data($data)->save()){
                $now_order = M("Store_order")->where(array('order_id'=>$order_id))->find();
                $now_order['order_type']='cash';
                //商家余额增加
                //D('Merchant_money_list')->add_money($now_order['mer_id'],'用户到店支付宝支付记入收入',$now_order);
                $now_order['desc']='用户到店支付宝支付记入收入(支付宝服务商子商户)';
                $now_order['is_own']=2;
                if($auth_token){
                    $now_order['payment_money']=0;
                }
                D('SystemBill')->bill_method($now_order['is_own'],$now_order);

                if($now_order['business_type']){
                    switch($now_order['business_type']){
                        case 'foodshop':
                            $now_food_order = D('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->find();
                            D('Foodshop_order')->after_pay($now_order['business_id'], null, 1);
                            M('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->data(array('price'=>$now_order['payment_money']+$now_food_order['book_price'],'pay_type'=>'1','payment_method'=>$data['pay_type']))->save();
                            break;
                    }
                }

                // 订单改变之后调用
                D("Store_order")->after_store_change($order_id,'cash',2);

                $this->returnCode('20130033');
            }else{
                $this->returnCode('20130034','',L_('支付失败！请联系管理员处理。支付宝返回信息：').$result->$responseNode->sub_msg);
            }
        } elseif($resultCode == 10003) {
            $this->returnCode("20130045");
        }else{
            if($result->$responseNode->sub_code=='ACQ.TRADE_HAS_SUCCESS'){

                $data['paid'] = '1';
                $data['pay_time'] = strtotime($result->$responseNode->gmt_payment);
                $data['pay_type'] = 'alipay';
                $data['third_id'] = $result->$responseNode->trade_no;
                $data['payment_money'] = $result->$responseNode->receipt_amount;
                if(M("Store_order")->where(array('order_id'=>$order_id))->data($data)->save()){
                    $now_order = M("Store_order")->where(array('order_id'=>$order_id))->find();
                    $now_order['order_type']='cash';
                    //商家余额增加
                    //D('Merchant_money_list')->add_money($now_order['mer_id'],L_('用户到店支付宝支付记入收入'),$now_order);
                    $now_order['desc']='用户到店支付宝支付记入收入';
                    if($auth_token){
                        $now_order['payment_money']=0;
                    }
                    D('SystemBill')->bill_method($now_order['is_own'],$now_order);

                    if($now_order['business_type']){
                        switch($now_order['business_type']){
                            case 'foodshop':
                                $now_food_order = D('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->find();
                                D('Foodshop_order')->after_pay($now_order['business_id'], null, 1);
                                M('Foodshop_order')->where(array('order_id'=>$now_order['business_id']))->data(array('price'=>$now_order['payment_money']+$now_food_order['book_price'],'pay_type'=>'1','payment_method'=>$data['pay_type']))->save();
                                break;
                        }
                    }

                    // 订单改变之后调用
                    D("Store_order")->after_store_change($order_id,'cash',2);

                    $this->returnCode('20130033');
                }else{
                    $this->returnCode('20130034','',L_(L_('支付失败！请联系管理员处理。支付宝返回信息：')).$result->$responseNode->sub_msg);
                }
            }else{
                $this->returnCode('20130034','','支付失败！请联系管理员处理。支付宝返回信息：'.$result->$responseNode->sub_msg);
            }
        }
    }


	//优惠买单
	public function store_order()
	{
        // 开启店员权限
        if (C('config.open_staff_power')&&$this->staff_session['type'] == 0) {           
            $this->returnCode(1,'',L_('您当前身份为【店小二】，暂无权限，请联系管理员！'));                
        }

		$store_order = D('Store_order');
		$res = $store_order->where(array('orderid'=>$_POST['order_id']))->find();
		$order_id = $res['order_id'];
		$condition = '';
		$count_condition = '';
		if($_POST['condition']&&$_POST['keyword']){
			if($_POST['condition']=='order_id'){
				$_POST['condition']='orderid';
			}
			$condition='AND '.$_POST['condition'].' like "%'.$_POST['keyword'].'%"';
			$count_condition = $condition;
		}



		if($condition){
			$where = array('paid' => 1, 'store_id' => $this->store['store_id'], 'from_plat' => 1,'_string'=>substr($condition,4));
		}else{
			$where = array('paid' => 1, 'store_id' => $this->store['store_id'], 'from_plat' => 1);
		}
		if($order_id&&empty($condition)){
			$condition .= ' AND order_id < '.$order_id;
		}

        if($_POST['stime']){
            $condition .= ' AND pay_time >= '.strtotime($_POST['stime']);
            $count_condition .= ' AND pay_time >= '.strtotime($_POST['stime']);
        }
        if($_POST['etime']){
            $condition .= ' AND pay_time < '.(strtotime($_POST['etime'])+86400);
            $count_condition .= ' AND pay_time < '.(strtotime($_POST['etime'])+86400);
        }

		$sql = "SELECT s.*, u.nickname, u.phone FROM " . C('DB_PREFIX') . "store_order AS s LEFT JOIN " . C('DB_PREFIX') . "user AS u ON s.uid=u.uid WHERE s.paid=1 AND s.store_id={$this->store['store_id']} AND s.from_plat=1 {$condition} ORDER BY s.order_id DESC LIMIT 10";
		$sql2 = "SELECT count(*) as count,sum(total_price) AS toal_price_all,SUM(IF(s.refund=1,total_price,0)) AS total_refund_price_all FROM " . C('DB_PREFIX') . "store_order AS s LEFT JOIN " . C('DB_PREFIX') . "user AS u ON s.uid=u.uid WHERE s.paid=1 AND s.store_id={$this->store['store_id']} AND s.from_plat=1 {$count_condition} ORDER BY s.order_id DESC ";
        $order_list = $store_order->query($sql);
		$count = $store_order->query($sql2);
		$pagenum = ceil($count[0]['count'] /10);
        $totalPriceAll = getFormatNumber($count[0]['toal_price_all']);
        $totalRefundPriceAll = getFormatNumber($count[0]['total_refund_price_all']);

		$arr=array();
		foreach ($order_list as &$l) {
			$l['pay_type_show'] = D('Pay')->get_pay_name($l['pay_type'],1);
			$arr[]=array(
                'short_order_id'=>$l['order_id'],
				'order_id'=>$l['orderid'],
				'phone'=>empty($l['phone'])?'':$l['phone'],
				'total_price'=>$l['total_price'],
				'pay_time'=>date('Y/m/d H:i:s',$l['pay_time']),
                'refund'=>$l['refund'],
			);
		}
		if(empty($arr)){
			$this->returnCode(0,array('order_list' => array(),'pagenum'=>$pagenum,'total_price'=>$totalPriceAll,'total_refund_price'=>$totalRefundPriceAll));
		}else{
			$this->returnCode(0,array('order_list' => $arr,'pagenum'=>$pagenum,'total_price'=>$totalPriceAll,'total_refund_price'=>$totalRefundPriceAll));
		}
	}

	public function store_order_detail()
	{
		$where['orderid'] = $_POST['order_id'];
		$res = M('Store_order')->where($where)->find();
		$user = M('User')->where(array('uid'=>$res['uid']))->find();
		$arr['order']['order_id'] = $res['orderid'];
        $arr['order']['remark'] = $res['remark'];
		$arr['order']['total_price'] = $res['total_price'];
		$arr['order']['card_discount'] = $res['card_discount'];
		$arr['order']['no_discount_money'] = $res['no_discount_money'];
		$arr['order']['discount_price'] = $res['discount_price'];
		$arr['order']['card_price'] = $res['card_price'];
		$arr['order']['coupon_price'] = $res['coupon_price'];
		$arr['order']['score_deducte'] = $res['score_deducte'];
        if ($res['card_discount']) {  
            $arr['order']['card_discount_money'] = round($res['price']-($res['price']-$res['no_discount_money'])*$res['card_discount']/10-$res['no_discount_money'],2);
        }else{
             $arr['order']['card_discount_money'] = 0;
        }
        $internalCardPayMoney = 0;
        $internalCardPay = @json_decode($res['internal_card_pay'], true);
        if(is_array($internalCardPay)){
            $internalCardPayMoney = array_sum($internalCardPay);
        }
        $arr['order']['price'] = $res['price'] - $arr['order']['card_discount_money'];
        $arr['order']['payment_money'] = $res['payment_money'];
        $arr['order']['merchant_balance'] = $res['merchant_balance'];
        $arr['order']['balance_pay'] = $res['balance_pay'];
		$arr['order']['card_give_money'] = $res['card_give_money'];
        $arr['order']['pay_total'] = $res['payment_money']+$res['score_deducte']+$res['card_price']+$res['coupon_price']+$res['merchant_balance']+$res['card_give_money']+$res['balance_pay'];
        $arr['order']['vip_discount_money'] = $res['vip_discount_money'];
        $arr['order']['internal_card_pay_money'] = $internalCardPayMoney;
        $arr['order']['pay_total'] += $internalCardPayMoney;
        
        $offline_money = $res['price'] - $arr['order']['card_discount_money'] - ($res['payment_money']+$res['score_deducte']+$res['card_price']+$res['coupon_price']+$res['merchant_balance']+$res['card_give_money']+$res['balance_pay']);
        $offline_money -= $internalCardPayMoney;
        
		$arr['order']['offline_money'] = $offline_money ? round(floatval($offline_money),2) : 0;
        $arr['order']['pay_time'] = $res['pay_time']==0?'':date('Y/m/d H:i:s',$res['pay_time']);
		$arr['order']['pay_type'] = D('Pay')->get_pay_name($res['pay_type'],$res['is_mobile_pay'], $res['paid'], '',$res);
        
		$arr['order']['ticketNum'] = $res['ticketNum'];
		$arr['order']['ticketPrice'] = intval($res['ticketPrice']);
		$arr['order']['ticketInsure'] = intval($res['ticketInsure']);
		$arr['order']['desc'] = $res['desc'];
        $arr['order']['refund'] = intval($res['refund']);
        $arr['order']['refund_txt'] = $res['refund'] == 1?'退款成功':'未退款';
        $arr['order']['refund_time'] = $res['refund_time'] ? date('Y/m/d H:i:s', $res['refund_time']) : '';
        $arr['order']['refund_staff_name'] = $res['refund_staff_name'];
        $arr['order']['refund_staff_id'] = $res['refund_staff_id'];
        if($res['offline_pay']>0){
            $offline_pay_list = M('Store_pay')->where(array('store_id'=>$this->store['store_id']))->order('`id` ASC')->getField('id,name');
            $arr['order']['pay_type'] .=$offline_pay_list[$res['offline_pay']];
        }
        $arr['order']['pay_info'] = D('Pay')->payTypeMoneyDetail($res);
		if($user){
			$arr['user'] = array(
				'uid'=>$user['uid'],
				'nickname'=>$user['nickname'],
				'phone'=>$user['phone']
			);
		}

		$this->returnCode(0,$arr);
	}

	public function check_store_arrival_order(){
		$where['order_id'] = $_POST['order_id'];
        if($this->config['open_juhepay']==1){
            import('@.ORG.LowFeePay');
            $order['orderNo'] = 'store' . '_' . $_POST['order_id'];
            $lowfeepay = new LowFeePay('juhepay');
            $go_pay_param = $lowfeepay->check($order);

            if($go_pay_param['error']==0){
                $date['pay_type'] = $go_pay_param['pay_type'];
                $date['payment_money'] = $go_pay_param['pay_money'];
                $date['third_id'] = $go_pay_param['third_id'];
                $date['is_own'] = $go_pay_param['is_own'];
                $date['paid'] = 1;
                $date['pay_time'] = $go_pay_param['pay_time'];

                if(M("Store_order")->where(array('order_id'=>$_POST['order_id']))->data($date)->save()){
                    $now_order = M('Store_order')->where(array('order_id'=>$_POST['order_id']))->find();
                    $now_order['order_type']='cash';
                    $now_order['desc']=L_('用户到店扫码支付记入收入');
                    //商家余额增加

                    D('SystemBill')->bill_method($now_order['is_own'],$now_order);


                }

            }
        }
		$res = M('Store_order')->where($where)->find();
		if($res['paid']){
			$this->returnCode(0);
		}else{
			$this->returnCode('20130047');
		}
	}

	public function store_count()
	{

		$time = I('time', time());
		$where = array('store_id' => $this->store['store_id'], 'pay_time' => array('gt', $time),'from_plat'=>1);
		$count = M('Store_order')->where($where)->count();
// 		$data_store_staff['device_id'] = $this->DEVICE_ID;
// 		$data_store_staff['last_time'] = $_SERVER['REQUEST_TIME'];
// 		$data_store_staff['client'] = I('client');
// 		if($data_store_staff['client'] ==2){
// 			$save=M('Merchant_store_staff')->where($where)->data($data_store_staff)->save();
// 		}
		if ($count < 0) {
			$this->returnCode('20130027');
		}
		$this->returnCode(0, array('count' => $count, 'time' => time()));
	}

	public function cash_count()
	{

		$time = I('time', time());
		$where = array('store_id' => $this->store['store_id'], 'pay_time' => array('gt', $time),'from_plat'=>2);
		$count = M('Store_order')->where($where)->count();
// 		$data_store_staff['device_id'] = $this->DEVICE_ID;
// 		$data_store_staff['last_time'] = $_SERVER['REQUEST_TIME'];
// 		$data_store_staff['client'] = I('client');
// 		if($data_store_staff['client'] ==2){
// 			$save=M('Merchant_store_staff')->where($where)->data($data_store_staff)->save();
// 		}
		if ($count < 0) {
			$this->returnCode('20130027');
		}
		$this->returnCode(0, array('count' => $count, 'time' => time()));
	}

    //作用：产生随机字符串，不长于32位
    public function createNoncestr( $length = 32 )
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str ="";
        for ( $i = 0; $i < $length; $i++ )  {
            $str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);
        }
        return $str;
    }
    //微信支付密钥
    public function getWxSign($Obj)
    {
        foreach ($Obj as $k => $v)
        {
            $Parameters[$k] = $v;
        }
        //签名步骤一：按字典序排序参数
        ksort($Parameters);
        $String = $this->formatBizQueryParaMap($Parameters, false);
        $String = $String."&key=".$this->config['pay_weixin_key'];
        $String = md5($String);
        $result_ = strtoupper($String);
        return $result_;
    }
    //格式化参数，签名过程需要使用
    function formatBizQueryParaMap($paraMap, $urlencode){
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v)
        {
            if($urlencode)
            {
                $v = urlencode($v);
            }
            //$buff .= strtolower($k) . "=" . $v . "&";
            $buff .= $k . "=" . $v . "&";
        }
        $reqPar='';
        if (strlen($buff) > 0)
        {
            $reqPar = substr($buff, 0, strlen($buff)-1);
        }
        return $reqPar;
    }
    //数组转XML
    function arrayToXml($arr){
        $xml = "<xml>";
        foreach ($arr as $key=>$val){
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }
    /*
     *
     *
     *	到店支付  end
     *
     *
     */
    
    // 获得餐饮可预约列表详情
    public function get_appoint_time()
    {
        $foodshop = D('Merchant_store')->now_store($this->store['store_id']);
        if ($foodshop['err_code']) exit(json_encode($foodshop));
        $foodshop = $foodshop['data'];

        $book_time = isset($_POST['book_time']) ? htmlspecialchars($_POST['book_time']) : 0;
        if (empty($book_time)) {
            exit(json_encode(array('err_code' => true, 'msg' => L_('预订时间不能为空'))));
        }

        $book_time = strtotime($book_time);

        $table_type = intval($_POST['table_type']);
        $table_id = intval($_POST['table_id']);
        $return = D('Foodshop_order')->format_data($foodshop, $book_time, $table_type, $table_id);
        $this->returnCode(0, $return);
    }

    // 根据桌台获取当前桌台最近可预约时间
    public function ajax_get_now_appoint_time()
    {
        $foodshop = D('Merchant_store')->now_store($this->store['store_id']);
        if ($foodshop['err_code']) exit(json_encode($foodshop));
        $foodshop = $foodshop['data'];

        $table_type = intval($_POST['table_type']);
        $table_id = intval($_POST['table_id']);
        

        $book_time = strtotime($book_time);

        $table_type = intval($_POST['table_type']);
        $table_id = intval($_POST['table_id']);
        $book_time = D('Foodshop_order')->get_now_appoint_time($table_type,$foodshop,$table_id);
        if ($book_time) {
            $return = D('Foodshop_order')->format_data($foodshop, strtotime($book_time), $table_type,$table_id);
            if ( $return['err_code']) {
                $this->returnCode(1, null, $return['msg']);
            }else{
                $this->returnCode(0, $return);
            }
        }else{
            $this->returnCode(1,null, L_('当前桌台没有可预约时间'));
        }
    }

	public function foodshop()
	{
		$data = array();
		
		$foodshop = M('Merchant_store_foodshop')->where(array('store_id' => $this->store['store_id']))->find();
		if ($foodshop['queue_is_open']) {
		    $data['queue_count'] = M('Foodshop_queue')->where(array('store_id' => $this->store['store_id'], 'status' => 0))->count();
		} else {
		    $data['queue_count'] = 0;
		}
		$data['queue_is_open'] = $foodshop['queue_is_open'];
		
		$count_list = M('Foodshop_table')->field('count(1) AS cnt, status')->where(array('store_id' => $this->store['store_id']))->group('status')->select();
		$data['lock_count'] = 0;
		$data['open_count'] = 0;
		foreach ($count_list as $row) {
			if ($row['status'] == 1) {
				$data['lock_count'] = $row['cnt'];
			} else {
				$data['open_count'] = $row['cnt'];
			}
		}
		
        $where_order = array(
            'store_id' => $this->store['store_id'], 
            'status' => array('gt', 0),
            '_string'=>'(((account_type=1 OR order_from <> 1) OR (account_type=2 AND `status`>=3)) AND (unbind_table=0 OR status>=3))',// 必须绑定桌台或者不绑定桌台已支付才显示
        );
		$count_list = M('Foodshop_order')->field('count(1) AS cnt, status, running_state')->where($where_order)->group('status, running_state')->select();

		$data['book_count'] = 0;
		$data['confirm_count'] = 0;
		$data['eating_count'] = 0;
		$data['all_count'] = 0;
		$data['pay_count'] = 0;
		$data['cancel_count'] = 0;
		
		foreach ($count_list as $row) {
			if ($row['status'] == 1 && $row['running_state'] == 0) {
				$data['book_count'] += $row['cnt'];
			}
			if (($row['status'] == 2 || $row['status'] == 1) && $row['running_state'] == 1) {
				$data['confirm_count'] += $row['cnt'];
			}
			if ($row['status'] == 2 && $row['running_state'] == 0) {
				$data['eating_count'] += $row['cnt'];
			}
			if ($row['status'] > 2 && $row['status'] < 5) {
				$data['pay_count'] += $row['cnt'];
			}
			if ($row['status'] == 5) {
				$data['cancel_count'] += $row['cnt'];
			}
			$data['all_count'] += $row['cnt'];
		}
		
		//桌台列表
		$table_list = M('Foodshop_table')->where(array('store_id'=>$this->store['store_id']))->order('`id` ASC')->select();
		$data['table_lock'] = 0;	//锁定
        $data['table_unlock'] = 0;  //解锁
		$table_ids = array();
    	foreach($table_list as $value){
			if($value['status'] == 0){
				$data['table_unlock']++;
			}else{
				$data['table_lock']++;
			}
            $table_ids[] = $value['id'];
		} 

        // 开启桌台形式点餐
        if (C('config.store_open_table_type')==1) {
            if ($table_ids) {
                $where_order = array(
                    'table_id'=>array('in',$table_ids),
                    'status'=>array('in',array(1,2)),            
                    '_string'=>'(((account_type=1 OR order_from <> 1) OR (account_type=2 AND `status`>=3)) AND (unbind_table=0 OR status>=3))',// 必须绑定桌台或者不绑定桌台已支付才显示

                );
                $table_order = M('Foodshop_order')->field('table_id')->where($where_order)->order('`order_id` ASC')->select();
                $table_unlock = 0;
                $table_lock = 0;
                foreach($table_list as $_table){
                    $lock = 0;
                    foreach ($table_order as $_order) {
                        if ($_table['id']==$_order['table_id']) {
                            $lock=1;
                        }
                    }
                    if ($lock==1) {
                        $table_lock++;
                    }else{
                        $table_unlock++;
                    }
                }
                $data['lock_count'] = $data['table_lock'] = $table_lock;    //就餐中
                $data['open_count'] = $data['table_unlock'] = $table_unlock;  //空桌
            }
        }

        $data['store_open_table_type'] = C('config.store_open_table_type');
        $data['staff_type'] = $this->staff_session['type'];
        $data['account_type'] = $foodshop['account_type'];
		
		$this->returnCode(0, $data);
	}
	
	/**
	 * 创建与编辑订单
	 */
	public function foodshop_order_before()
	{
        if($this->now_merchant['status']!=1){
            if($this->now_merchant['merchant_end_time']>0 && $this->now_merchant['merchant_end_time']<time()){
                $this->returnCode(1,'',L_('商家已过期，暂不可使用该功能！'));
            }
            $this->returnCode(1,'',L_('商家已X1，暂不可使用该功能！',$this->merchant_status[$this->now_merchant['status']]));
        }
		$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
		$where = array('order_id' => $order_id, 'store_id' => $this->store['store_id']);
		$now_order = D('Foodshop_order')->field(true)->where($where)->find();
		
		$table_type = M('Foodshop_table_type')->where(array('store_id' => $this->store['store_id']))->order('`id` ASC')->select();
		if (empty($table_type)) {
			$this->returnCode(1, null, L_('该店铺没有设置桌台分类'));
		}

        if (C('config.store_open_table_bind_time') == 1) {
            // 是否开启线下锁定桌台绑定时间段
            $where = array('store_id' => $this->store['store_id']);
        }else{
            $where = array('store_id' => $this->store['store_id'], 'status' => '0');
        }

        // 桌台类型
        $now_table_type = intval($_POST['table_type']);
       
        $temp_list = M('Foodshop_table')->where($where)->order('`id` ASC')->select();
        
		$table_list = array();
		foreach ($temp_list as $row) {
			$table_list[$row['tid']][] = $row;
		}

		if ($now_order) {
			$row = M('Foodshop_table')->where(array('store_id' => $this->store['store_id'], 'id' => $now_order['table_id']))->find();
			$table_list[$row['tid']][] = $row;
		}else{
            if (C('config.store_open_table_type')==1&&$_POST['table_id']) { // 开启桌台形式
                $table_order = M('Foodshop_order')->field('table_id,order_id,status')->where(array('table_id'=>$_POST['table_id'],'status'=>array('in',array(1,2))))->order('`order_id` ASC')->find();
            }
        }
		$list = array();
		foreach ($table_type as $table) {
			if (isset($table_list[$table['id']])) {
				$table['list'] = $table_list[$table['id']];
				$list[] = $table;
			}
		}
		if (empty($list))$this->returnCode(1, null, L_('店铺没有可用的桌台'));

        $return = array(
            'store_open_table_bind_time' => C('config.store_open_table_bind_time') ? C('config.store_open_table_bind_time') : 0, 
            'table_type' => isset($now_order['table_type']) ? $now_order['table_type'] : 0, 
            'now_table_type' => $now_table_type, // 创建订单时桌台类型
            'now_table_id' => intval($_POST['table_id']), // 创建订单时桌台
            'table_id' => isset($now_order['table_id']) ? $now_order['table_id'] : 0, 
            'order_id' => $order_id, 
            'table_order_id' => $table_order['order_id']?$table_order['order_id']:0, 
            'status' => $table_order['status']?$table_order['status']:0, 
            'list' => $list
        );

        if (C('config.store_open_table_bind_time') == 1) {
            // 是否开启线下锁定桌台绑定时间段
            $foodshop = D('Merchant_store')->now_store($this->store['store_id']);
            if (!$foodshop['err_code']) {
                $foodshop = $foodshop['data'];

                $table_type_id = $table_type[0]['id'];
                $book_time = D('Foodshop_order')->get_now_appoint_time($table_type_id,$foodshop);

                $this->assign('book_time',$book_time);
                $time_info = D('Foodshop_order')->format_data($foodshop, strtotime($book_time), $table_type);
                unset($time_info['table_type']);
                unset($time_info['table_list']);
                $return = array_merge($return,$time_info);
            }
        }

		$this->returnCode(0, $return);
	}
	
	/**
	 * 创建与编辑订单的保存
	 */
	public function foodshop_add_order()
	{
		$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
		$book_num = isset($_POST['book_num']) ? intval($_POST['book_num']) : 0;
		$table_type = isset($_POST['table_type']) ? intval($_POST['table_type']) : 0;
		$table_id = isset($_POST['table_id']) ? intval($_POST['table_id']) : 0;
		$note = isset($_POST['note']) ? htmlspecialchars($_POST['note']) : '';
		if (empty($table_type) || empty($table_id)) $this->returnCode(1, null, L_('餐台必须选择一个'));

        if (C('config.store_open_table_bind_time') == 1 && isset($_POST['book_time'])) {
            // 是否开启线下锁定桌台绑定时间段
            $foodshop = D('Merchant_store')->now_store($this->store['store_id']);
            $foodshop = $foodshop['data'];

            $now_time = time() + $foodshop['advance_time'] * 60;
            $book_time = isset($_POST['book_time']) ? htmlspecialchars($_POST['book_time']) : 0;
            if (empty($book_time)) {
                $this->returnCode(1, null, L_('预订时间不能为空'));
            }
            $book_time = strtotime($book_time);
            if ($now_time > $book_time) {
                $this->returnCode(1, null, L_('至少提前X1分钟预定',$foodshop['advance_time']));
            }
            $data['book_time'] = $book_time;

            $order = M('Foodshop_order')->field(true)->where(array('store_id' => $this->store['store_id'], 'book_time' => $book_time, 'table_type' => $_POST['table_type'],'table_id' => $_POST['table_id'], 'status' => array('in', array(1, 2))))->find();
            if ($order) {
                $this->returnCode(1, null, L_('该时段该桌台已订满'));
            }
        }

		if ($now_order = M('Foodshop_order')->where(array('order_id' => $order_id, 'store_id' => $this->store['store_id']))->find()) {
			$condition_order['order_id'] = $order_id;
			$condition_order['store_id'] = $this->store['store_id'];
			$data['book_num'] = $book_num;
			$data['table_type'] = $table_type;
			$data['table_id'] = $table_id;
			if(M('Foodshop_order')->where($condition_order)->data($data)->save()){
                //计算桌台服务费
                $service_fee = D('Foodshop_order')->create_table_service_fee($order_id,1);
				M('Foodshop_table')->where(array('id' => $now_order['table_id']))->data(array('status'=>'0'))->save();
				M('Foodshop_table')->where(array('id' => $data['table_id']))->data(array('status'=>'1'))->save();
				$this->returnCode(0, L_('编辑成功'));
			} else {
				$this->returnCode(1, null, L_('编辑订单失败，请重试'));
			}
		} else {
			$data['real_orderid'] = date('ymdhis') . substr(microtime(), 2, 8 - strlen($this->store['store_id'])) . $this->store['store_id'];
			$data['mer_id'] = $this->store['mer_id'];
			$data['store_id'] = $this->store['store_id'];
			$data['book_num'] = $book_num;
			$data['table_type'] = $table_type;
			$data['table_id'] = $table_id;
			$data['note'] = $note;
			$data['order_from'] = 2;
			$data['create_time'] = $_SERVER['REQUEST_TIME'];
			$data['status'] = 1;
			if ($order_id = D('Foodshop_order')->save_order($data)){
                //计算桌台服务费
                $service_fee = D('Foodshop_order')->create_table_service_fee($order_id,1);
				M('Foodshop_table')->where(array('id' => $data['table_id']))->data(array('status' => '1'))->save();

                /* 增加系统订单记录 2019/01/29 */
                D('Foodshop_order')->add_system_order($order_id,1);
				$this->returnCode(0, $order_id);
			} else{
				$this->returnCode(1, null, L_('创建订单失败，请重试'));
			}
		}
	}

    
    /**
     * 验证桌台是否有预订或就餐中订单
     */
    public function foodshop_table_order()
    {
        if ($_POST['table_id']) { 
            $table_order = M('Foodshop_order')->field('table_id,order_id,status')->where(array('table_id'=>$_POST['table_id'],'status'=>array('in',array(1,2)),'account_type'=>1))->order('`order_id` ASC')->find();
        }
        $this->returnCode(0, array( 'table_order_id' => $table_order['order_id']?$table_order['order_id']:0, 'status' => $table_order['status']?$table_order['status']:0));
    }
	
	/**
	 * status = 1 预定待确认 is_order = 0 未点餐 1已点餐
	 * status = 2 店员已确认，在用餐中， is_order = 0 就餐中，1店员待确认菜品
	 * status = 3买单完成
	 */
	
	public function foodshop_order()
	{
        $status = isset($_POST['status']) ? intval($_POST['status']) : -1;
        $status = isset($_POST['status']) ? intval($_POST['status']) : -1;
		$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
		$keyword = isset($_POST['keyword']) ? htmlspecialchars($_POST['keyword']) : '';
		$searchType = isset($_POST['searchType']) ? htmlspecialchars($_POST['searchType']) : '';
        $where = array('store_id' => $this->store['store_id']);

        if ($order_id) {
            $where['order_id'] = $order_id;
        }
        $foodshop = M('Merchant_store_foodshop')->where(array('store_id'=>$this->store['store_id']))->field('account_type')->find();
        $account_type = $foodshop['account_type'];
		switch ($status) {
			case 1:
				$where['status'] = 1;//预定中
				$where['running_state'] = 0;
				break;
			case 2:
				$where['status'] = 2;//就餐中
				$where['running_state'] = 0;
				break;
			case 3:
				$where['status'] = array('in', array(3, 4));//已买单
				break;
			case 4:
				$where['running_state'] = 1;//待确认菜品
				$where['status'] = array('in', array(1, 2));//已买单
				break;
			case 5:
				$where['status'] = 5;//已取消
				break;
			default:
				$where['status'] = array('gt', 0);
		}
		if ($keyword) {
            switch($searchType){
                case 'dh':
                    $where['phone'] = $keyword;
                    break;
                case 'xm':
                    $where['name'] = $keyword;
                    break;
                case 'zt':
                    $tables = M('Foodshop_table')->where(array('name'=>array('like','%'.$keyword.'%'),'store_id'=>$this->store['store_id']))->select();
                    foreach ($tables as $t) {
                        $tmp_table_id[] = $t['id'];
                    }

                    $where['table_id'] = array('in',$tmp_table_id);
                    break;
            }

		}

		$data = D('Foodshop_order')->get_order_list($where, 'order_id DESC', 2);
        foreach ($data['order_list'] as &$value) {
            $store_order = D('Store_order')->where(array('business_id'=>$value['order_id'],'business_type'=>'foodshop','paid'=>1))->find();
            $plat_order = D('Plat_order')->where(array('business_id'=>$value['order_id'],'business_type'=>'foodshop','paid'=>1))->select();

            // 实付金额
            $discount_money = 0;
            if ($store_order) {  
                $discount_money += $store_order['card_price'] + $store_order['coupon_price']+ $store_order['score_deducte'];
            }

            if ($store_order['card_discount']) {  
                $discount_money += round($store_order['price']-($store_order['price']-$store_order['no_discount_money'])*$store_order['card_discount']/10-$store_order['no_discount_money'],2);
            }

            if ($plat_order) {
                foreach ($plat_order as $_plat) {
                    $discount_money +=  $_plat['system_coupon_price'] + $_plat['system_score_money'] + $_plat['merchant_coupon_price'] + $_plat['merchant_discount_money'];
                }
            }
            $value['price'] = $value['price'] - $discount_money;
            $value['cancel_date'] = date('Y-m-d H:i:s', $value['cancel_time']);
			
			if(!$value['min_max']){
				$value['min_max'] = '';
			}
        }
        $data['ecard_alias_name']=$this->config['ecard_alias_name'];
        $data['open_foodshop_print_type'] = C('config.open_foodshop_print_type');
		$this->returnCode(0, $data);
	}
	
	
	/**
	 * 订单的菜品详情
	 */
	public function foodshop_detail()
	{
		$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
		$where = array('order_id' => $order_id, 'store_id' => $this->store['store_id']);
		
		$now_order = D('Foodshop_order')->get_order_detail(array('order_id' => $order_id, 'store_id' => $this->store['store_id']), 2);
		
		if (empty($now_order)) $this->returnCode(1, null, L_('订单不存在'));
		
		$price = D('Foodshop_order')->count_price($now_order);
		
		$goods_detail_list = $now_order['info'];
// 		$price = $now_order['price'];
		$package_list = array();
		$must_list = array();
		$old_goods_list = array();
		$total_num = 0;
		$total_price = 0;
		foreach ($goods_detail_list as $new) {
			$new['price'] = floatval($new['price']);
			$new['num'] = floatval($new['num']);
			$total_num += $new['num'];
			if ($new['package_id']) {
			    if (empty($new['fid'])) {
			        if (isset($package_list[$new['id']])) {
			            $package_list[$new['id']]['name'] = $new['name'];
			            $package_list[$new['id']]['num'] = floatval($new['num']);
			            $package_list[$new['id']]['price'] = floatval($new['price']);
			        } else {
			            $package_list[$new['id']] = array('list' => array(), 'name' => $new['name'], 'num' => floatval($new['num']), 'price' => floatval($new['price']));
			        }
			        
			    } else {
			        if (isset($package_list[$new['fid']])) {
			            $package_list[$new['fid']]['list'][$new['goods_id']] = $new;
			        } else {
			            $package_list[$new['fid']] = array('list' => array($new['goods_id'] => $new));
			        }
			    }
			} else {
				$old_goods_list[] = $new;
			}
		}
		if ($now_order['package_ids']) {
			$package_ids = json_decode($now_order['package_ids'], true);
			$packages = D('Foodshop_goods_package')->field(true)->where(array('in' => array('id', $package_ids)))->select();
			foreach ($package_ids as $pid) {
				foreach ($packages as $p) {
					if ($pid == $p['id']) {
						$package_list[$pid]['num']++;
						$package_list[$pid]['price'] += $p['price'];
						$package_list[$pid]['name'] = $p['name'];
// 						$total_price += $p['price'];
					}
				}
			}
		}
		
        $total_price = floatval($price);
		// $total_price = floatval($price + $now_order['book_price']);
// 		$goods_temp_list = D('Foodshop_order_temp')->field(true)->where($where)->select();
		$goods_temp_list = $now_order['info_temp'];
		foreach ($goods_temp_list as &$temp) {
			$temp['price'] = floatval($temp['price']);
			$temp['num'] = floatval($temp['num']);
			$total_num += $temp['num'];
			if(!$temp['fid']){
				$total_price += $temp['num'] * $temp['price'];
			}
		}

		$this->returnCode(0, array('goods_list' => $old_goods_list, 'package_list' => $package_list, 'temp_list' => $goods_temp_list, 'total_price' => getFormatNumber($total_price), 'total_num' => $total_num));
	}

	/**
	 * 编辑的菜单页详情
	 */
	public function foodshop_goods()
	{
		$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $keyword = $_POST['keyword'];
		$where = array('order_id' => $order_id, 'store_id' => $this->store['store_id']);
		
		$now_order = D('Foodshop_order')->get_order_detail(array('order_id' => $order_id, 'store_id' => $this->store['store_id']), 2);
		if (empty($now_order)) $this->returnCode(1, null, L_('订单不存在'));
		
		$price = D('Foodshop_order')->count_price($now_order);
		//已确认的菜品
		$sql = 'SELECT `d`.*, `g`.image FROM ' . C('DB_PREFIX') . 'foodshop_order_detail AS d LEFT JOIN ' . C('DB_PREFIX') . 'foodshop_goods AS g ON `g`.`goods_id`=`d`.`goods_id` WHERE `d`.order_id=' . $order_id . ' AND `d`.`store_id`=' . $this->store['store_id'];
		$goods_detail_list = D()->query($sql);
		$goods_image_class = new foodshop_goods_image();
		$total_num = 0;
		$goodsList = array();
		$package_list = array();
		$uids = array();
		foreach ($goods_detail_list as $g) {
		    if (! in_array($g['uid'], $uids)) {
		        $uids[] = $g['uid'];
		    }

			$g['price'] = floatval($g['price']);
			$g['num'] = floatval($g['num']);
			if (empty($g['fid'])) {
                $total_num += $g['num'];
			}
// 			$total_price += $g['num'] * $g['price'];
			$tmp_pic_arr = explode(';', $g['image']);
			isset($tmp_pic_arr[0]) && $tmp_pic_arr[0] && $g['image'] = $goods_image_class->get_image_by_path($tmp_pic_arr[0], 's');
			if ($g['package_id']) {
			    if (empty($g['fid'])) {
			        if (isset($package_list[$g['uid']][$g['id']])) {
			            $package_list[$g['uid']][$g['id']]['name'] = $g['name'];
			            $package_list[$g['uid']][$g['id']]['num'] = floatval($g['num']);
			            $package_list[$g['uid']][$g['id']]['price'] = floatval($g['price']);
			        } else {
			            $package_list[$g['uid']][$g['id']] = array('list' => array(), 'name' => $g['name'], 'num' => floatval($g['num']), 'price' => floatval($g['price']));
			        }
			    } else {
			        if (isset($package_list[$g['uid']][$g['fid']])) {
			            $package_list[$g['uid']][$g['fid']]['list'][$g['goods_id']] = $g;
			        } else {
			            $package_list[$g['uid']][$g['fid']] = array('list' => array($g['goods_id'] => $g));
			        }
			    }
			    
			} else {
    			if (isset($goodsList[$g['uid']])) {
    			    $goodsList[$g['uid']]['foods'][] = $g;
    			} else {
    			    $goodsList[$g['uid']] = array('uid' => 0, 'nickname' => '', 'package' => array(), 'foods' => array($g));
    			}
			}
		}

		//全部的菜品分类和菜品信息
		$lists = D('Foodshop_goods')->get_list_by_storeid($this->store['store_id'],'1',$keyword);
		
		
		//用户待	确认菜品
		$temp_list = array();
		$goods_temp_list = $now_order['info_temp'];
		
		foreach ($goods_temp_list as $temp) {
		    if (! in_array($temp['uid'], $uids)) {
		        $uids[] = $temp['uid'];
		    }
		    $t_cookie = $temp;//array('goods_id' => $temp['goods_id'], 'num' => $temp['num'], 'name' => $temp['name'], 'price' => floatval($temp['price']));
			$params = array();
			if ($temp['spec_id']) {
				$params = D('Foodshop_goods')->format_spec_ids($temp, $params);
			}
			if ($go['spec']) {
				$params = D('Foodshop_goods')->format_properties_ids($temp, $params);
			}
			$t_cookie['params'] = $params;
			if (isset($temp_list[$temp['uid']])) {
			    $temp_list[$temp['uid']]['foods'][] = $t_cookie;
			} else {
			    $temp_list[$temp['uid']] = array('uid' => 0, 'nickname' => '', 'foods' => array($t_cookie));
			}
		}
		
		
		$userTemp = D('User')->field('nickname, uid')->where(array('uid' => array('in', $uids)))->select();
		$userList = array();
		$userTemp[] = array('nickname' => L_('店员'), 'uid' => 0);
		foreach ($userTemp as $user) {
		    if (isset($temp_list[$user['uid']])) {
		        $temp_list[$user['uid']]['nickname'] = $user['nickname'];
		        $temp_list[$user['uid']]['uid'] = $user['uid'];
		    }
		    
		    if (isset($goodsList[$user['uid']])) {
		        $goodsList[$user['uid']]['nickname'] = $user['nickname'];
		        $goodsList[$user['uid']]['uid'] = $user['uid'];
		        
		        if (isset($package_list[$user['uid']])) {
		            $goodsList[$user['uid']]['package'] = $package_list[$user['uid']];
		        } else {
		            $goodsList[$user['uid']]['package'] = array();
		        }
		    }
		    
		    if (isset($package_list[$user['uid']]) && !isset($goodsList[$user['uid']])) {
		        $goodsList[$user['uid']]['nickname'] = $user['nickname'];
		        $goodsList[$user['uid']]['uid'] = $user['uid'];
		        $goodsList[$user['uid']]['foods'] = array();
		        $goodsList[$user['uid']]['package'] = $package_list[$user['uid']];
		    }
		}
		
        $where = array('store_id' => $this->store['store_id'],'status' => 1);
        if ($keyword) {
            $where['name'] = array('like','%'.$keyword.'%');
        }
		$goods_package = D('Foodshop_goods_package')->where($where)->order('`id` ASC')->select();
		
		if($goods_package){
			foreach($goods_package as &$value){
				$value['price'] = floatval($value['price']);
				$value['goods_id'] = 0;
			}
			$lists[] = array('goods_list' => $goods_package, 'sort_name' => L_('团购套餐'), 'sort_id' => -1, 'store_id' => $this->store['store_id']);
		}else{
			$goods_package = array();
		}
		
		$this->returnCode(0, array('goods_list' => $goodsList, 'package_list' => $goods_package, 'temp_list' => $temp_list, 'lists' => $lists, 'total_price' => floatval($price + $now_order['book_price']), 'unpaid_price' => floatval($price), 'total_num' => $total_num, 'book_price' => floatval($now_order['book_price'])));
	}
	

	public function foodshop_getgroup_detail()
	{
		$group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
		$data = D('Foodshop_goods_package')->get_detail_by_id(array('store_id' => $this->store['store_id'], 'status' => 1, 'id' => $group_id), true);
		if ($data) {
		    if (empty($data['goods_detail'])) {
		        $this->returnCode(1, null, '套餐没有添加商品,暂时不能使用');
		    }
			$this->returnCode(0, $data);
		} else {
			$this->returnCode(1, null, L_('不存在的团购套餐'));
		}
	}
	
	
	public function foodshop_change_order()
	{
		$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
		$num = isset($_POST['num']) ? intval($_POST['num']) : 0;
		$id = isset($_POST['detail_id']) ? intval($_POST['detail_id']) : 0;
		
		$condition_where['store_id'] = $this->store['store_id'];
		$condition_where['order_id'] = $order_id;
		$condition_where['id'] = $id;
        $now_order_detail = M('Foodshop_order_detail')->where($condition_where)->find();
		if ($num > 0) {
			if (M('Foodshop_order_detail')->where($condition_where)->data(array('num' => $num))->save()) {
                // 修改库存
                if ($num!=$now_order_detail['num']) {
                    $diff_order_detail = $now_order_detail;
                    $diff_order_detail['num'] = $num-$now_order_detail['num'];
                    //更新库存
                    $goodsDB = D('Foodshop_goods');
                    $goodsDB->update_stock($diff_order_detail);
                    // 是否启用餐饮打印模式 新
                    if (C('config.open_foodshop_print_type')) {
                        if ($diff_order_detail['num']>0) { // 加菜
                            // 打印加菜
                            D('Foodshop_order')->order_notice($order_id, array($diff_order_detail), 0, 1, 0,$this->staff_session['name']);
                        }else{
                            // 打印退菜
                            $diff_order_detail['num'] = 0-$diff_order_detail['num'];
                            D('Foodshop_order')->order_notice($order_id, array($diff_order_detail), 0, 1, 1,$this->staff_session['name']);                 
                        }
                    }
                }
				$order = D('Foodshop_order')->get_order_detail(array('order_id' => $order_id, 'store_id' => $this->store['store_id']), 2);
				$price = D('Foodshop_order')->count_price($order);
				$extra_price = D('Foodshop_order')->count_extra_price($order);
				
                /* 增加系统订单记录 2019/01/29 */
                D('Foodshop_order')->add_system_order($order_id,2);

				$data = array('total_price' => floatval($price + $order['book_price']), 'book_price' => floatval($order['book_price']), 'unpaid_price' => floatval($price),'extra_price' => $extra_price);
				$this->returnCode(0, $data);
			} else {
				$this->returnCode(1, null, L_(L_('保存失败！')));
			}
		} else {
			if (M('Foodshop_order_detail')->where($condition_where)->delete()) {
                // 修改库存
                if ($now_order_detail['num']) {
                    //更新库存
                    $goodsDB = D('Foodshop_goods');
                    $goodsDB->update_stock($now_order_detail,1);
                    // 是否启用餐饮打印模式 新
                    if (C('config.open_foodshop_print_type')) {
                        // 打印退菜
                        D('Foodshop_order')->order_notice($order_id, array($now_order_detail), 0, 1, 1, $this->staff_session['name']);  
                    }  
                }

				$order = D('Foodshop_order')->get_order_detail(array('order_id' => $order_id, 'store_id' => $this->store['store_id']), 2);
				$price = D('Foodshop_order')->count_price($order);
				$extra_price = D('Foodshop_order')->count_extra_price($order);

                /* 增加系统订单记录 2019/01/29 */
                D('Foodshop_order')->add_system_order($order_id,2);
				
				$data = array('total_price' => floatval($price + $order['book_price']), 'book_price' => floatval($order['book_price']), 'unpaid_price' => floatval($price),'extra_price' => $extra_price);
				$this->returnCode(0, $data);
			} else {
				$this->returnCode(1, null, '保存失败！');
			}
		}
	}
	
	
	public function foodshop_save_order()
	{
	    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
	    $package_id = isset($_POST['package_id']) ? intval($_POST['package_id']) : 0;
	    $carts = isset($_POST['cart']) ? $_POST['cart'] : null;
	    if (empty($carts)) {
	        $this->returnCode(1, null, L_('没有点菜'));
	    }
// 	    fdump($carts, 'app', 1);
// 	    die;
	    if ($order = M('Foodshop_order')->field(true)->where(array('store_id' => $this->store['store_id'], 'order_id' => $order_id))->find()) {
            $goods_ids = array_column($carts, 'productId');
            $goods_list = M('Foodshop_goods')->where(array('goods_id'=>array('in',$goods_ids)))->select();
            $goods_list_fomart = array();
            foreach ($goods_list as $key => $value) {
                $goods_list_fomart[$value['goods_id']] = $value;
            }
	        $productCart = array();
	        foreach ($carts as $row) {
	            $params = array();
	            $tmp_id = 0;
	            if (isset($row['tmpOrderId']) && $row['tmpOrderId']) {
	                if ($goods_temp = D('Foodshop_order_temp')->field(true)->where(array('order_id' => $order_id, 'id' => $row['tmpOrderId']))->find()) {
	                    $t_cookie = array('goods_id' => $goods_temp['goods_id'], 'tempId' => $goods_temp['id'], 'uid' => $goods_temp['uid'], 'fid' => $goods_temp['fid'], 'package_id' => $goods_temp['package_id'], 'num' => $row['count'], 'note' => $row['note'], 'name' => $goods_temp['name'], 'price' => floatval($goods_temp['price']),'extra_price'=>$goods_temp['extra_price'],'is_must'=>intval($goods_list_fomart[$goods_temp['goods_id']]['is_must']),'operate_must_goods'=>intval($goods_list_fomart[$goods_temp['goods_id']]['operate_must_goods']));
	                    $temp_params = array();
	                    if ($goods_temp['spec_id']) {
	                        $temp_params = D('Foodshop_goods')->format_spec_ids($goods_temp, $temp_params);
	                    }
	                    if ($goods_temp['spec']) {
	                        $temp_params = D('Foodshop_goods')->format_properties_ids($goods_temp, $temp_params);
	                    }
	                    $t_cookie['params'] = $temp_params;
	                    $productCart[] = $t_cookie;
	                    continue;
	                }
	            } elseif (isset($row['productParam'])) {
	                foreach ($row['productParam'] as $r) {
	                    if ($r['type'] == 'spec') {
	                        $t_data = array('id' => $r['id'], 'name' => '', 'type' => $r['type'], 'data' => $r['data']);
	                    } else {
	                        $t_data = array('id' => $r['id'], 'name' => '', 'type' => $r['type'], 'data' => '');
	                        $td = array();
	                        foreach ($r['data'] as $i => $d) {
	                            $td[] = array('id' => $i, 'name' => $d['name']);
	                        }
	                        $t_data['data'] = $td;
	                    }
	                    $params[] = $t_data;
	                }
	            }
	            $productCart[] = array('name' => $row['productName'], 'tempId' => 0, 'uid' => 0, 'goods_id' => $row['productId'], 'package_id' => 0, 'note' => $row['note'], 'num' => $row['count'], 'params' => $params,'extra_price'=>$row['extra_price'],'staff_id'=>$this->staff_session['id'],'is_must'=>intval($goods_list_fomart[$row['productId']]['is_must']),'operate_must_goods'=>intval($goods_list_fomart[$row['productId']]['operate_must_goods']));
	        }
	        $cart_data = D('Foodshop_goods')->format_cart($productCart, $this->store['store_id'], $order_id, false);
            fdump($cart_data,'cart_data');
            $diff_order_temp = array(); // 店员新增或减少的菜品
            foreach($cart_data['data'] as $vd){
                unset($vd['tempId']);
                $where_tmp['order_id'] =$vd['order_id'] ;
                $where_tmp['store_id'] =$vd['store_id'] ;
                if(M('Foodshop_order_temp')->where($where_tmp)->find()){
                    if ($order_temp['num'] != $vd['num']) {
                        $_diff_order_temp = $vd;
                        $_diff_order_temp['num'] = $vd['num']-$order_temp['num'];
                        $diff_order_temp[] = $_diff_order_temp;
                    }
                    M('Foodshop_order_temp')->where($where_tmp)->save($vd);
                }else{
                    $diff_order_temp[] = $vd;
                    M('Foodshop_order_temp')->add($vd);
            fdump(M('Foodshop_order_temp')->_sql(),'cart_data',1);
                }
                $good_info = M('Foodshop_goods')->where(array('goods_id'=>$vd['goods_id']))->find();
                if($good_info['stock_num']>0||$good_info['stock_num']==-1){

                   // M('Foodshop_goods')->where(array('goods_id'=>$vd['goods_id']))->setInc('sell_count',$vd['num']);
                   // M('Foodshop_goods')->where(array('goods_id'=>$vd['goods_id']))->setDec('stock_num',$vd['num']);
                }else{
                    $this->returnCode(1, null, L_('菜品库存不足，请添加后重试！'));
                }
            }
	        if ($cart_data['err_code']) {
	            $this->error($cart_data['msg']);
	        }
	        $new_goods_list = $cart_data['data'];
	        
	        $total = $cart_data['total'];
	        $price = $cart_data['price'];
	        $now_time = time();
	        if ($package_data = M('Foodshop_goods_package')->field(true)->where(array('id' => $package_id, 'store_id' => $this->store['store_id']))->find()) {
	            $price = $package_data['price'];
	            
	            $hideData = array();
	            $hideData['order_id'] = $order_id;
	            $hideData['goods_id'] = $package_id;
	            $hideData['name'] = $package_data['name'];
	            $hideData['price'] = $package_data['price'];
	            $hideData['unit'] = L_('份');
	            $hideData['create_time'] = $now_time;
	            $hideData['package_id'] = $package_id;
	            $hideData['uid'] = 0;
                $hideData['staff_id'] = $this->staff_session['id'];
	            $hideData['ishide'] = 1;
	            $hideData['is_discount'] = 1;
	            $hideData['num'] = 1;
	            $hideData['store_id'] = $this->store['store_id'];
	            $hideData['extra_price'] = 0;
	            if ($fid = D('Foodshop_order_detail')->add($hideData)) {
	                foreach ($new_goods_list as $index => $new_row) {
	                    $new_row['order_id'] = $order_id;
	                    $new_row['create_time'] = $now_time;
	                    $new_row['package_id'] = $package_id;
	                    $new_row['uid'] = 0;
	                    $new_row['fid'] = $fid;
	                    $new_row['store_id'] = $this->store['store_id'];
	                    $new_row['extra_price'] = empty($new_row['extra_price'])?0:$new_row['extra_price'];
	                    D('Foodshop_order_detail')->add($new_row);
	                }
	            } else {
	                $this->returnCode(1, null, L_('套餐保存失败，稍后重试！'));
	            }
	        } else {
	            $detail_list = D('Foodshop_order_detail')->field(true)->where(array('order_id' => $order_id, 'store_id' => $this->store['store_id'], 'package_id' => 0))->select();
	            $detailList = array();
	            foreach ($detail_list as $_row) {
	                $_t_index = $_row['uid'] . '_' . $_row['goods_id'];
	                if (strlen($_row['spec']) > 0) {
	                    $_t_index .= '_' . md5($_row['spec']);
	                }
	                $detailList[$_t_index] = $_row;
	            }
	            
	            $tempTableList = D('Foodshop_order_temp')->field(true)->where(array('order_id' => $order_id, 'store_id' => $this->store['store_id'], 'package_id' => 0))->select();
	            $temp_table_list = array();
	            foreach ($tempTableList as $t_row) {
	                $t_t_index = $t_row['uid'] . '_' . $t_row['goods_id'];
	                if (strlen($t_row['spec']) > 0) {
	                    $t_t_index .= '_' . md5($t_row['spec']);
	                }
	                $temp_table_list[$t_t_index] = $t_row;
	            }
	            $newPackageIDs = array();
	            $detailDB = D('Foodshop_order_detail');
	            $foodshopGoodsDB = D('Foodshop_goods');
	            foreach ($new_goods_list as $index => $new_row) {
	                if ($new_row['package_id']) {//用户点的套餐
	                    if (isset($newPackageIDs[$new_row['package_id']]) && $newPackageIDs[$new_row['package_id']]) {
	                        $fid = $newPackageIDs[$new_row['package_id']];
	                    } else {
	                        if ($pack = D('Foodshop_goods_package')->field(true)->where(array('id' => $new_row['package_id']))->find()) {
	                            $tmp = array();
	                            $tmp['order_id'] = $order_id;
	                            $tmp['store_id'] = $this->store['store_id'];
	                            $tmp['package_id'] = $new_row['package_id'];
	                            $tmp['goods_id'] = $new_row['package_id'];
	                            $tmp['note'] = $new_row['note'];
	                            $tmp['ishide'] = 1;
	                            $tmp['is_discount'] = 1;
	                            $tmp['name'] = $pack['name'];
	                            $tmp['price'] = $pack['price'];
	                            $tmp['extra_price'] = 0;
	                            $tmp['uid'] = $new_row['uid'];
                                $tmp['staff_id'] = intval($new_row['staff_id']);
	                            $tmp['create_time'] = $now_time;
	                            $tmp['num'] = $new_row['num'];
	                            if ($fid = $detailDB->add($tmp)) {
	                                $newPackageIDs[$new_row['package_id']] = $fid;
	                            } else {
	                                $this->returnCode(1, null, L_('用户点的套餐保存失败，稍后重试！'));
	                            }
	                        } else {
	                            $this->returnCode(1, null, L_('套餐信息不存在！'));
	                        }
	                    }
	                    if ($new_row['fid']) {
	                        $new_row['fid'] = $fid;
	                        $new_row['create_time'] = $now_time;
	                        $new_row['order_id'] = $order_id;
	                        $new_row['store_id'] = $this->store['store_id'];
	                        $new_row['extra_price'] = empty($new_row['extra_price']) ? 0 : $new_row['extra_price'];
	                        $detailDB->add($new_row);
	                    }
	                } elseif (isset($detailList[$index])) {//这个用户已经点了这个菜品的时候 只是在已点菜品中增加数量
	                    $detailDB->where(array('id' => $detailList[$index]['id']))->save(array('num' => $new_row['num'] + $detailList[$index]['num']));
	                    unset($detailList[$new_row['uid']][$index]);
	                } else {//新增的菜品
	                    $new_row['fid'] = 0;
	                    $new_row['package_id'] = 0;
	                    $new_row['create_time'] = $now_time;
	                    $new_row['order_id'] = $order_id;
	                    $new_row['store_id'] = $this->store['store_id'];
	                    $new_row['extra_price'] = empty($new_row['extra_price']) ? 0 : $new_row['extra_price'];
	                    $detailDB->add($new_row);
	                }
	                
	                if (isset($temp_table_list[$index])) {
	                    $diffNum = $new_row['num'] - $temp_table_list[$index]['num'];
	                    if ($diffNum != 0) {
	                        $temp_table_list[$index]['num'] = $diffNum;
	                        // $foodshopGoodsDB->update_stock($temp_table_list[$index]);
	                    }
	                    unset($temp_table_list[$index]);
	                }
	            }
	            //将用户点的菜品，但是被店员给取消掉的回滚库存
	            foreach ($temp_table_list as $i => $rowList) {
	                // foreach ($rowList as $val) {
	                    $foodshopGoodsDB->update_stock($val, 1);
	                // }
	            }

                // 更新库存
                foreach ($diff_order_temp as $i => $rowList) {
                    $foodshopGoodsDB->update_stock($rowList);
                }

	            //清空用户点餐的零时表
	            D('Foodshop_order_temp')->where(array('order_id' => $order_id, 'store_id' => $this->store['store_id']))->delete();
	        }
	        if ($order['status'] < 2) {
	            $save_order_data['status'] = 2;
	        }
	        $save_order_data['running_state'] = 0;
	        $save_order_data['last_time'] = $_SERVER['REQUEST_TIME'];
	        $save_order_data['running_time'] = $_SERVER['REQUEST_TIME'];
	        
	        if (M('Foodshop_order')->where(array('store_id' => $this->store['store_id'], 'order_id' => $order_id))->save($save_order_data)) {
	            //配置打印
                // 是否启用餐饮打印模式 新
                if (C('config.open_foodshop_print_type')) {
                    D('Foodshop_order')->order_notice($order_id, $new_goods_list, 6, 1, 0,$this->staff_session['name'],$order['status']);
                }else{
                    D('Foodshop_order')->order_notice($order_id, $new_goods_list);
                }

                /* 增加系统订单记录 2019/01/29 */
                D('Foodshop_order')->add_system_order($order_id,2);

                // 增加流水号
                D('Foodshop_order')->get_fetch_number($order);

	            $this->returnCode(0, L_('订单保存成功'));
	        } else {
	            $this->returnCode(1, null, L_('订单保存失败，稍后重试！'));
	        }
	    } else {
	        $this->returnCode(1, null, L_('订单信息不存在！'));
	    }
	}
	
    public function foodShopClearTempData()
    {
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $order_temp_list = D('Foodshop_order_temp')->where(array('store_id' => $this->store['store_id'], 'order_id' => $order_id))->select();
        //回滚库存
        foreach ($order_temp_list as $value) {
            D('Foodshop_goods')->update_stock($value, 1);
        }

        D('Foodshop_order_temp')->where(array('store_id' => $this->store['store_id'], 'order_id' => $order_id))->delete();

        $this->returnCode(0, 'ok');
    }
	//只打印订单里面的商品，而且只用主打印机打印，一般用于用户结算前的打印。
	public function foodshop_print_order()
	{
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
	    $is_print_menu = isset($_POST['is_print_menu']) ? intval($_POST['is_print_menu']) : 0;
        $order = M('Foodshop_order')->field(true)->where(array('store_id' => $this->store['store_id'], 'order_id' => $order_id))->find();
        
	    $printHaddle = new PrintHaddle();
         // 是否启用餐饮打印模式 新
        if (C('config.open_foodshop_print_type')) {
            if ($is_print_menu) { // 后厨补打
                $printHaddle->printit($order_id, 'foodshop_order', -1, 0, $this->staff_session['name']);
            }else{   // 打印预结单，结账单             
                $printHaddle->printit($order_id, 'foodshop_order', -1, 1, $this->staff_session['name']);
            }
        }else{
            $printHaddle->printit($order_id, 'foodshop_order', -1, 1);
        }
	    $this->returnCode(0, L_('发送打印请求成功'));
	}
	
	//快店重打小票
    public function shop_re_print_order(){

        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;

        $now_order = D('Shop_order')->get_order_detail(array('order_id' => $order_id, 'store_id' => $this->store['store_id']));
        if (empty($now_order)) {
            $this->returnCode(1,array(),L_('订单信息不存在'));
        }
        $printHaddle = new PrintHaddle();
        $printHaddle->printit($now_order['order_id'], 'shop_order', -1, 0, '',1);
        $this->returnCode(0, L_('发送打印请求成功'));

    }
	
	public function foodshop_table_list(){
		$table_type = M('Foodshop_table_type')->where(array('store_id'=>$this->store['store_id']))->order('`id` ASC')->select();
		
		if(empty($table_type)){
			$this->returnCode(1,array(),L_('该店铺没有设置桌台分类'));
		}
		
		$table_list = M('Foodshop_table')->where(array('store_id'=>$this->store['store_id']))->order('`id` ASC')->select();
		
		foreach($table_type as $value){
			$value['table_list'] = array();
			$table_type_arr[$value['id']] = $value;
		}
        $table_ids = array();
		foreach($table_list as $value){
            $table_ids[] = $value['id'];
		}

        // 开启桌台形式点餐
        if (C('config.store_open_table_type')==1) {
            if ($table_ids) {
                $table_order = M('Foodshop_order')->field('table_id,order_id,status')->where(array('table_id'=>array('in',$table_ids),'status'=>array('in',array(1,2)),'is_foodshop_new'=>0,'account_type'=>1))->order('`order_id` ASC')->select();

                foreach($table_list as &$_table){
                    foreach ($table_order as $_order) {
                        if ($_table['id']==$_order['table_id']) {
                            $_table['order_id'] = $_order['order_id'];
                            $_table['o_status'] = $_order['status'];
                            continue;
                        }
                    }
                    $_table['store_open_table_type'] = C('config.store_open_table_type');
                }
            }
        }

        foreach($table_list as $value){
            $table_type_arr[$value['tid']]['table_list'][] = $value;
            $table_ids[] = array();
        }
		
		foreach($table_type_arr as $key=>$value){
			if(empty($value['table_list'])){
				unset($table_type_arr[$key]);
			}
		}


		$table_list = array_values($table_type_arr);
        $return['list'] = $table_list;
        $return['store_open_table_type'] = C('config.store_open_table_type');

		$this->returnCode(0,$return);
	}
	public function tmp_table_lock(){
		if(M('Foodshop_table')->where(array('store_id'=>$this->store['store_id'],'id'=>$_POST['id']))->setField('status',$_POST['lock'])){
			$this->returnCode(0,array());
		}else{
			$this->returnCode(1,array(),L_('操作失败'));
		}
	}
	public function book_list(){
		$table_id = isset($_POST['table_id']) && $_POST['table_id'] ? intval($_POST['table_id']) : 0;
		$where = array('store_id' => $this->store['store_id'], 'table_id' => $table_id, 'status' => array('lt', 2), 'book_time' => array('gt', time()));
		// $where = array('store_id' => $this->store['store_id'], 'table_id' => $table_id);
		$return = D('Foodshop_order')->field(true)->where($where)->order('book_time ASC')->select();
		
		if(empty($return)){
			$return = array();
		}else{
			foreach($return as &$value){
				$value['book_price_txt'] = floatval($value['book_price']);
				$value['book_time_txt'] = date('Y-m-d H:i',$value['book_time']);
			}
		}
		
		$this->returnCode(0,$return);
	}

    // 餐饮日结账单统计
    public function foodshop_report()
    {   
        if ($this->staff_session['type'] != 2) {
            $this->returnCode(1,null,L_('对不起，您没有权限'));
        }

        $stime = strtotime(date('Y-m-d 00:00:00'));
        $etime = strtotime(date('Y-m-d 23:59:59'));
        $where = array('paid' => 1, 'store_id' => $this->store['store_id']);
        if ($stime && $etime) {
            $where['pay_time'] = array(array('gt', $stime), array('lt', $etime));
        }

        $return = D('Foodshop_order')->foodshop_report($where);
        $return['stime'] = date('Y-m-d 00:00:00');
        $return['etime'] = date('Y-m-d 23:59:59');
        $this->returnCode(0,$return);
    }

    public function foodshop_report_print()
    {
        $stime = strtotime(date('Y-m-d 00:00:00'));
        $etime = strtotime(date('Y-m-d 23:59:59'));
        $where = array('paid' => 1, 'store_id' => $this->store['store_id']);
        if ($stime && $etime) {
            $where['pay_time'] = array(array('gt', $stime), array('lt', $etime));
        }

        $return = D('Foodshop_order')->foodshop_report($where);

        $printHaddle = new PrintHaddle();
        $printHaddle->printit($return, 'foodshop_order_report', $this->staff_session['name']);// 日结单

        $this->returnCode(0,L_('打印成功'));
    }
	

	/**
	 * 排号例表
	 */
	public function queue_list()
	{
		$where = array('store_id' => $this->store['store_id']);
		$foodshop = M('Merchant_store_foodshop')->field(true)->where($where)->find();
		if ($foodshop['is_queue'] == 0) {
			$this->returnCode(1, null, L_('店铺没有排号功能'));
		}
		$foodshop['queue_open_time'] = date('Y-m-d H:i:s', $foodshop['queue_open_time']);
		//排队例表
		$queue_list = M('Foodshop_queue')->field(true)->where(array('store_id' => $this->store['store_id'], 'status' => 0))->order('id ASC')->select();
		$now_number_ids = null;
		$next_number_ids = null;
		$wait_number_list = array();
		$now_data = null;
		$next_data = null;
		foreach ($queue_list as $row) {
			if (!isset($now_number_ids[$row['table_type']])) {
				$now_number_ids[$row['table_type']] = $row['number'];
				$now_data[$row['table_type']] = $row;
			} elseif (!isset($next_number_ids[$row['table_type']])) {
				$next_number_ids[$row['table_type']] = $row['number'];
				$next_data[$row['table_type']] = $row;
			}
			if (isset($wait_number_list[$row['table_type']])) {
				$wait_number_list[$row['table_type']] ++;
			} else {
				$wait_number_list[$row['table_type']] = 1;
			}
		}
		$table_total = M('Foodshop_table')->field('count(tid) AS cnt, tid')->where(array('store_id' => $this->store['store_id'], 'status' => 0))->group('tid')->select();
	
		$temp = array();
		foreach ($table_total as $v) {
			$temp[$v['tid']] = $v['cnt'];
		}
	
		$table_type_list = M('Foodshop_table_type')->field(true)->where($where)->select();
		if(empty($table_type_list)){
			$table_type_list = array();
		}
		foreach ($table_type_list as &$t_row) {
			$t_row['now_number'] = '';
			$t_row['next_number'] = '';
			$t_row['wait'] = 0;
			$t_row['free'] = 0;
			if (isset($now_number_ids[$t_row['id']])) {
				$t_row['now_number'] = $now_number_ids[$t_row['id']];
				$t_row['now_use_num'] = $now_data[$t_row['id']]['num'];
				$t_row['now_queue_from'] = $now_data[$t_row['id']]['queue_from'];
			}
			if (isset($next_number_ids[$t_row['id']])) {
				$t_row['next_number'] = $next_number_ids[$t_row['id']];
				$t_row['next_use_num'] = $next_data[$t_row['id']]['num'];
				$t_row['next_queue_from'] = $next_data[$t_row['id']]['queue_from'];
			}
			if (isset($wait_number_list[$t_row['id']])) {
				$t_row['wait'] = $wait_number_list[$t_row['id']];
			}
			if (isset($temp[$t_row['id']])) {
				$t_row['free'] = $temp[$t_row['id']];
			}
		}
		$this->returnCode(0, array('list' => $table_type_list, 'foodshop' => $foodshop));
	}

	//开启与关闭排号
	public function change_queue()
	{
		$where = array('store_id' => $this->store['store_id']);
		$foodshop = M('Merchant_store_foodshop')->field(true)->where($where)->find();
		if (empty($foodshop)) {
			$this->returnCode(1, null, L_('店铺信息有问题'));
		}
		if (M('Merchant_store_foodshop')->where($where)->save(array('queue_is_open' => 1 - intval($foodshop['queue_is_open']), 'queue_open_time' => time()))) {
			if ($foodshop['queue_is_open'] == 0) {
				//开启排号的时候清空以前的排号记录
				M('Foodshop_queue')->where($where)->delete();
				
				$this->queue_list();
			} else {
				$this->returnCode(0, L_('状态修改成功'));
			}
			
		} else {
			$this->returnCode(1, null, L_('状态修改失败'));
		}
	}

	//叫号
	public function queue_call()
	{
		$tid = isset($_POST['tid']) ? intval($_POST['tid']) : 0;
		$where = array('store_id' => $this->store['store_id'], 'table_type' => $tid, 'status' => 0);
		if ($queue = M('Foodshop_queue')->where($where)->order('id ASC')->limit(1)->find()) {
			//TODO 发送模板消息
			if ($user = D('User')->where(array('uid' => $queue['uid']))->find()) {
				$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
				$href = C('config.site_url').'/wap.php?c=Foodshop&a=queue&store_id=' . $this->store['store_id'];
				$model->sendTempMsg('OPENTM205984119', array('href' => $href, 'wecha_id' => $user['openid'], 'first' => L_('尊敬的用户您好，已经排到您的号了'), 'keyword1' => $queue['number'], 'keyword2' => date('Y.m.d H:i', $queue['create_time']), 'keyword3' => 1, 'remark' => L_('【X1】通知您进店用餐！', $this->store['name'])));
			}
			$this->returnCode(0, L_('已叫号'));
		} else {
			$this->returnCode(0, L_('没有排号了'));
		}
	}
	
	//跳号
	public function queue_cancel()
	{
		$tid = isset($_POST['tid']) ? intval($_POST['tid']) : 0;
		$where = array('store_id' => $this->store['store_id'], 'status' => 0, 'table_type' => $tid);
		if ($queue = M('Foodshop_queue')->where($where)->order('id ASC')->limit(1)->find()) {
			$where['id'] = $queue['id'];
			if (M('Foodshop_queue')->where($where)->save(array('status' => 1))) {
				$this->queue_list();
			} else {
				$this->returnCode(1, null, L_('跳号失败，稍后重试'));
			}
		} else {
			$this->returnCode(1, null, L_('不存在的信息'));
		}
	}
	
	public function queue_create()
	{
		$table_type = M('Foodshop_table_type')->where(array('store_id' => $this->store['store_id']))->order('`id` ASC')->select();
		$this->returnCode(0, $table_type);
	}
	//现场取号
	public function queue_save()
	{
		$store_id = $this->store['store_id'];
		$table_type = isset($_POST['table_type']) ? intval($_POST['table_type']) : 0;

		
		$table_type_data = M('Foodshop_table_type')->field(true)->where(array('store_id' => $store_id, 'id' => $table_type))->find();
		if (empty($table_type_data)) {
			$this->returnCode(1, null, L_('不存在的桌台类型'));
		}
		
		$foodshop_queue_db = M('Foodshop_queue');
// 		if ($queue = $foodshop_queue_db->field(true)->where(array('store_id' => $store_id, 'uid' => $this->user_session['uid'], 'status' => 0))->find()) {
// 			exit(json_encode(array('err_code' => true, 'msg' => '您已经取过号了，不要重新取号，如果重新取号，请先取消已经取的号')));
// 		}
		
		$fp = fopen('./runtime/' . md5(C('config.site_url') . $table_type) . '_lock.txt', "w+");
		flock($fp, LOCK_EX);
		if ($new_queue = $foodshop_queue_db->field(true)->where(array('store_id' => $store_id, 'table_type' => $table_type))->order('id DESC')->find()) {
			$number = str_replace($table_type_data['number_prefix'], '', $new_queue['number']);
		} else {
			$number = 0;
		}
		$number = intval($number) + 1;
		$new_number = $table_type_data['number_prefix'] . $number;
		$now_time = time();
		
		$num = isset($_POST['num']) ? intval($_POST['num']) : 1;
		$num = max($num, 1);
		
		$count = $foodshop_queue_db->where(array('store_id' => $store_id, 'table_type' => $table_type, 'status' => 0))->count();
		
		$use_time =  $now_time + ceil(($count + 1) / $table_type_data['num']) * $table_type_data['use_time'] * 60;
		
		$data = array('store_id' => $store_id, 'uid' => 0, 'table_type' => $table_type, 'number' => $new_number, 'create_time' => $now_time, 'use_time' => $use_time, 'num' => $num, 'status' => 0, 'queue_from' => 1);
		$queue_id = $foodshop_queue_db->add($data);
		
		flock($fp, LOCK_UN);
		fclose($fp);
		
		if ($queue_id) {
			$this->returnCode(0, $new_number);
			$this->queue_list();
// 			$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
// 			$href = C('config.site_url').'/wap.php?c=Foodshop&a=queue&store_id=' . $store_id;
// 			$model->sendTempMsg('OPENTM205984119', array('href' => $href, 'wecha_id' => $this->user_session['openid'], 'first' => '尊敬的用户您好，您的排号信息如下', 'keyword1' => $new_number, 'keyword2' => date('Y.m.d H:i'), 'keyword3' => $count + 1, 'remark' => '感谢您的支持！'));
// 			exit(json_encode(array('err_code' => false, 'number' => $new_number, 'time' => $table_type_data['use_time'])));
		} else {
			$this->returnCode(1, null, L_('稍后重试'));
		}
	}

    //取消订单（不是预定的订单，是支付过后的订单）
    public function cancel_foodshop_order(){
		if($this->staff_session['type'] <> 2){
			exit(json_encode(array('err_code' => true, 'msg' => L_('您暂无权限取消订单，请联系店长处理'))));
		}
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $now_order = D('Foodshop_order')->field(true)->where(array('order_id' => $order_id, 'store_id' => $this->store['store_id']))->find();
        if ($now_order['status'] < 3||$now_order['status']==5) {
            $this->returnCode(1, null, L_('该订单尚未支付或已经取消，无法取消'));
        }
        if (D('Foodshop_order')->where(array('order_id' => $order_id))->save(array('cancel_time' => time(), 'status' => 5, 'cancel_reason' => L_('店员手动取消！')))) {
            // 更新库存
            $temp_goods_list = D('Foodshop_order_detail')->field(true)->where(array('order_id' => $order_id, 'store_id' => $this->store['store_id'],'ishide'=>0))->order('id ASC')->select();
            $goodsDB = D('Foodshop_goods');
            foreach ($temp_goods_list as $index => $new_row) {
                //更新库存
                $goodsDB->update_stock($new_row,1);

                // 打印退菜
                D('Foodshop_order')->order_notice($order_id, array($new_row), 0, 1, 1,$this->staff_session['name']);  
            }

            /* 增加系统订单记录 2019/01/29 */
            D('Foodshop_order')->add_system_order($order_id,2);

            $this->refundMoney($order_id,$now_order['order_from']);
        } else {
            exit(json_encode(array('err_code' => true, 'msg' => L_('取消时出现错误稍后重试！'))));
        }
    }

    public function cancel_book()
    {
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        
        $now_order = D('Foodshop_order')->field(true)->where(array('order_id' => $order_id, 'store_id' => $this->store['store_id']))->find();
        if ($now_order['status'] > 1) {
            $this->returnCode(1, null, L_('您不能取消订单了'));
        }
        if (D('Foodshop_order')->where(array('order_id' => $order_id))->save(array('cancel_time' => time(), 'status' => 5, 'cancel_reason' => L_('店员手动取消！')))) {
            // 更新库存
            $temp_goods_list = D('Foodshop_order_temp')->field(true)->where(array('order_id' => $order_id, 'store_id' => $this->store['store_id'],'ishide'=>0))->order('id ASC')->select();
            $goodsDB = D('Foodshop_goods');
            foreach ($temp_goods_list as $index => $new_row) {
                //更新库存
                $goodsDB->update_stock($new_row,1);
            }

            // 修改桌台状态
            if ($now_order['table_id']) {
                $order = M('Foodshop_order')->where(array('table_id'=>$now_order['table_id'],'status'=>array('in',array(1,2))))->order('`order_id` ASC')->find();
                if (!$order) {
                    M('Foodshop_table')->where(array('id'=>$now_order['table_id']))->data(array('status'=>0))->save();
                }
            }

            /* 增加系统订单记录 2019/01/29 */
            D('Foodshop_order')->add_system_order($order_id,2);

            $this->refundMoney($order_id,$now_order['order_from']);

        } else {
            $this->returnCode(1, null, L_('取消时出现错误稍后重试！'));
        }
    }
    
    private function refundMoney($orderid,$order_from=0)
    {
        $now_order = M("Plat_order")->where(array('business_id' => $orderid,'business_type'=>'foodshop'))->find();

        if(empty($now_order)){
            if ($order_from==2) {
                $this->returnCode(0, L_('取消成功'));
            }else{
                $this->returnCode(0, L_('取消成功'));
                // $this->returnCode(1, null, L_('当前订单不存在'));
            }
        }
        $now_order['order_type'] = 'plat';
        $now_order['payment_money'] = $now_order['pay_money'];
        $table = ucfirst($now_order['business_type']).'_order';
        $model =D($table);
        $business_order = $model->get_order_by_orderid($now_order['business_id']);
        
//        $can_refund = $model->can_refund_status($business_order);
//        if ($now_order['paid'] != 1 || !$can_refund) {
////            return array('error' => 1, 'msg' => '当前订单店员正在处理中，不能退款或取消');
//            $this->returnCode(1, null, '当前订单店员正在处理中，不能退款或取消');
//        }
        if($now_order['total_money']==0){
            $this->returnCode(0, '当前订单未支付定金,订单已取消');
        }
        
        if(empty($now_order['paid'])){
            $this->returnCode(1, null, L_('当前订单还未付款！'));
        }
        $refund_status = false;
        $refund_num = 0; // 退款种类数量
        if ($now_order['pay_money'] != '0.00') {
            if($now_order['is_own']){
                $pay_method = array();
                $merchant_ownpay = D('Merchant_ownpay')->field('mer_id',true)->where(array('mer_id'=>$business_order['mer_id']))->find();
                foreach($merchant_ownpay as $ownKey=>$ownValue){
                    $ownValueArr = unserialize($ownValue);
                    if($ownValueArr['open']){
                        $ownValueArr['is_own'] = true;
                        $pay_method[$ownKey] = array('name'=>$this->getPayName($ownKey),'config'=>$ownValueArr);
                    }
                }
                $now_merchant = D('Merchant')->get_info($now_order['mer_id']);
                if ($now_merchant['sub_mch_refund'] && $this->config['open_sub_mchid'] && $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0) {
                    $pay_method['weixin']['config']['pay_weixin_appid'] = $this->config['pay_weixin_appid'];
                    $pay_method['weixin']['config']['pay_weixin_appsecret'] = $this->config['pay_weixin_appsecret'];
                    $pay_method['weixin']['config']['pay_weixin_mchid'] = $this->config['pay_weixin_sp_mchid'];
                    $pay_method['weixin']['config']['pay_weixin_key'] = $this->config['pay_weixin_sp_key'];
                    $pay_method['weixin']['config']['sub_mch_id'] = $now_merchant['sub_mch_id'];
                    $pay_method['weixin']['config']['pay_weixin_client_cert'] = $this->config['pay_weixin_sp_client_cert'];
                    $pay_method['weixin']['config']['pay_weixin_client_key'] = $this->config['pay_weixin_sp_client_key'];
                    $pay_method['weixin']['config']['is_own'] = 2 ;
                }
            } else {
                $pay_method = D('Config')->get_pay_method(0,0,1);
            }
            
            if(empty($pay_method)){
                $this->returnCode(1, null, L_('系统管理员没开启任一一种支付方式！'));
            }
            
            $pay_class_name = ucfirst($now_order['pay_type']);
            if($pay_class_name=='Alipay' && $now_order['is_mobile_pay'] == 2 && $this->config['new_pay_alipay_app_public_key']!=''&& $this->config['new_pay_alipay_app_appid']!='' && $this->config['new_pay_alipay_app_private_key']!=''){
                $pay_class_name = 'AlipayApp';
                $pay_method['alipay']['config']['new_pay_alipay_app_appid'] = $this->config['new_pay_alipay_app_appid'];
                $pay_method['alipay']['config']['new_pay_alipay_app_private_key'] = $this->config['new_pay_alipay_app_private_key'];
                $pay_method['alipay']['config']['new_pay_alipay_app_public_key'] = $this->config['new_pay_alipay_app_public_key'];
            }
            if (in_array($now_order['pay_type'], ['hqpay', 'hqpay_wx', 'hqpay_al'])) {
                $pay_class_name = 'Hqpay';
                empty($pay_method[$now_order['pay_type']]) && $pay_method[$now_order['pay_type']] = 1;
            }
            if(empty($pay_method[$now_order['pay_type']])){
                $this->returnCode(1,null,L_('您选择的支付方式不存在，请更新支付方式！'));
            }
            $import_result = import('@.ORG.pay.'.$pay_class_name);
            if(empty($import_result)){
                $this->returnCode(1,null,L_('系统管理员暂未开启该支付方式，请更换其他的支付方式'));
            }
            
            $order_id = $now_order['order_id'];
            $now_order['order_id'] = $now_order['orderid'];
            if($now_order['is_mobile_pay']==3){
                $pay_method[$now_order['pay_type']]['config'] =array(
                    'pay_weixin_appid'=>$this->config['pay_wxapp_appid'],
                    'pay_weixin_key'=>$this->config['pay_wxapp_key'],
                    'pay_weixin_mchid'=>$this->config['pay_wxapp_mchid'],
                    'pay_weixin_appsecret'=>$this->config['pay_wxapp_appsecret'],
                );
                $is_mobile = 3;
            }else{
                $is_mobile = 1;
            }
            $now_order['payment_money'] = $now_order['pay_money'];
            $pay_class = new $pay_class_name($now_order,$now_order['pay_money'],$now_order['pay_type'],$pay_method[$now_order['pay_type']]['config'],$this->user_session,$is_mobile);
            $go_refund_param = $pay_class->refund();
            
            $now_order['order_id'] = $orderid;
            $data_plat_order['order_id'] = $orderid;
            $data_plat_order['refund_detail'] = serialize($go_refund_param['refund_param']);
            if(empty($go_refund_param['error']) && $go_refund_param['type'] == 'ok'){
                $refund_status = true;
            }else{
                $this->returnCode(1,null,$go_refund_param['msg']);
            }
            D('Plat_order')->data($data_plat_order)->save();
            $refund_num++;

        }elseif($now_order['pay_type'] == 'offline') {
            $data_plat_order['order_id'] = $now_order['order_id'];
            $data_plat_order['refund_detail'] = serialize(array('refund_time'=>time()));
            D('Plat_order')->data($data_plat_order)->save();
        }
        
        //如果使用了积分 2016-1-15
        if ($now_order['system_score']>0) {
            
            $order_name=$now_order['order_name'];
            $result = D('User')->add_score($now_order['uid'],$now_order['system_score'],L_('退款 X1回滚',array('X1'=>$order_name.$this->config['score_name'])));
            $param = array('refund_time' => time());
            if($result['error_code']){
                $param['err_msg'] = $result['msg'];
            } else {
                $param['refund_id'] = $now_order['order_id'];
            }
            $data_plat_order['order_id'] = $now_order['order_id'];
            $data_plat_order['refund_detail'] = serialize($param);
            D('Plat_order')->data($data_plat_order)->save();
            if ($result['error_code']) {
                $this->returnCode(1,null,$result['msg']);
            }else{
                $refund_status = true;
            }
            $go_refund_param['msg'] = $result['msg'];
            $refund_num++;
        }
        
        //平台余额退款
        if($now_order['system_balance'] != '0.00'){
            $result = D('User')->add_money($now_order['uid'],$now_order['system_balance'],L_('退款 X1 增加余额',$now_order['order_name']));
            $param = array('refund_time' => time());
            if($result['error_code']){
                $param['err_msg'] = $result['msg'];
            } else {
                $param['refund_id'] = $now_order['order_id'];
            }
            $data_plat_order['order_id'] = $now_order['order_id'];
            $data_plat_order['refund_detail'] = serialize($param);
            D('Plat_order')->data($data_plat_order)->save();
            if ($result['error_code']) {
                $this->returnCode(1,null,$result['msg']);
            }else{
                $refund_status = true;
            }
            $go_refund_param['msg'] = L_(L_('退款成功'));
            $refund_num++;
        }
        
        //商家会员卡余额退款
        if($now_order['merchant_balance_pay'] != '0.00'||$now_order['merchant_balance_give']!='0.00'){
            $card_mer_id = $business_order['mer_id'];

            // 商家联盟会员卡
            if ($now_order['union_mer_id']) {
                $card_mer_id = $now_order['union_mer_id'];
            }
            
            $result = D('Card_new')->add_user_money(
                $card_mer_id,
                $now_order['uid'],
                $now_order['merchant_balance_pay'],
                $now_order['merchant_balance_give'],
                0,
                L_('退款 X1 增加余额','退款 X2 增加赠送余额',array('X1'=>$now_order['order_name'],'X2'=>$now_order['order_name']))
            );
            $param = array('refund_time' => time());
            if($result['error_code']){
                $param['err_msg'] = $result['msg'];
            } else {
                $param['refund_id'] = $now_order['order_id'];
            }
            
            $data_plat_order['order_id'] = $now_order['order_id'];
            $data_plat_order['refund_detail'] = serialize($param);
            D('Plat_order')->data($data_plat_order)->save();
            if ($result['error_code']) {
                $this->returnCode(1,null,$result['msg']);
            }else{
                $refund_status = true;
            }
            $go_refund_param['msg'] = L_('商家会员卡余额退款成功');
            $refund_num++;
        }
        
        if ($refund_num>1) {
            $go_refund_param['msg'] = L_('退款成功');
        }
        
        //if($refund_status){
        //调用 打印 发短信 发模板消息 回滚库存 回调跳转地址
        $refund_result = $model->afert_refund($business_order);
        if(!$refund_status['error_code']){
            // 给用户推送模板消息
            $now_user = D('User')->field(true)->where(array('uid' => $now_order['uid']))->find();
            if ($now_user['openid']) {
                $href = C('config.site_url') . '/wap.php?g=Wap&c=My&a=foodshop_order_list';
                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                $model->sendTempMsg('OPENTM405627933', array(
                    'href' => $href,
                    'wecha_id' => $now_user['openid'],
                    'first' => L_('您的X1订单已取消，系统已自动退款！',C('config.meal_alias_name')),
                    'keyword1' => $business_order['real_orderid'],
                    'keyword2' => $business_order['price'].C('config.Currency_txt'),
                    'keyword3'=>date('Y年m月d日 H:i'),
                    'remark' => L_('点击查看详情！')
                ));
            }
            if($now_order['pay_type']=='offline'){
                $this->returnCode(0,L_('此订单是线下支付！订单状态已修改为已退款。'));
                $this->success_tips(L_('您使用的是线下支付！订单状态已修改为已退款。'),$refund_result['url']);
            }else{
                $this->returnCode(0,$go_refund_param['msg']);
                $this->success_tips($go_refund_param['msg'],$refund_result['url']);
            }
        }else{
            $this->returnCode(1,null,$result['msg']);
        }
        //}
        
    }
    
    /**
     * 修改快店的未支付订单的价格
     */
    public function shopChangePrice()
    {
        if (empty($this->staff_session['is_change'])) {
            $this->returnCode(1, null, L_('您没有修改价格的权限!'));
        }
        $type = isset($_POST['type']) ? htmlspecialchars($_POST['type']) : 'c';
        $change_price_reason = isset($_POST['change_price_reason']) ? htmlspecialchars($_POST['change_price_reason']) : '';
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $where = array('order_id' => $order_id, 'store_id' => $this->store['store_id']);
        $order = D('Shop_order')->get_order_detail($where);
        if (empty($order)) {
            $this->returnCode(1, null, L_('不存在的订单信息!'));
        }
        if ($order['paid'] != 0) {
            $this->returnCode(1, null, L_('该订单已经不能修改支付价格了!'));
        }

        $data = array('last_staff' => $this->staff_session['name']);
        $data['last_time'] = $_SERVER['REQUEST_TIME'];
        $change_price = 0;
        if ($type == 'r') {
            if ($order['change_price'] > 0) {
                $change_price = $order['change_price'];
                $data['price'] = $order['change_price'];
            } else {
                $change_price = $order['price'];
            }
            $data['change_price'] = 0;
            $data['change_price_reason'] = '';
        } else {
            $change_price = isset($_POST['change_price']) ? floatval($_POST['change_price']) : $order['price'];
            if ($change_price == $order['price']) {
                $this->returnCode(1, null, L_('您没有修改价格!'));
            }
            if ($change_price <= 0) {
                $this->returnCode(1, null, L_('您不能把价格改成小于等于0的数'));
            }
            $data['price'] = $change_price;
            $data['last_staff'] = $this->staff_session['name'];
            $data['last_time'] = $_SERVER['REQUEST_TIME'];
            if (floatval($order['change_price']) == 0) {
                $data['change_price'] = $order['price'];
            }
            $data['change_price_reason'] = $change_price_reason;
        }
        if (D('Shop_order')->where($where)->save($data)) {
            $phones = explode(' ', $this->store['phone']);
            D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 30, 'name' => $this->staff_session['name'], 'phone' => $phones[0], 'note' => $change_price));
            $this->returnCode(0, floatval($change_price));
        } else {
            echo D('Shop_order')->_sql();
            $this->returnCode(1, null, L_('修改出错，稍后重试！'));
        }
    }

    //周期购商品，申请延期或取消延期的接口
    public function periodic_delay(){
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $type = isset($_POST['type']) ? $_POST['type'] :'';//reply or cancle
        $model = D('Mall_periodic_deliver');
        $result = $model->delay($id,$type,1);
		$result = $this->deal_result($result);
        echo json_encode($result);
    }
	
	protected function deal_result($result){
		$return = array();
		if($result['status']==0){
			$return['errorCode']=1;
		}else{
			$return['errorCode']=0;
		}
		$return['errorMsg'] = $result['info'];
		$return['result'] = $result['info'];
		return $return;
	}

    /**
     * 快店的订单详情（打包app使用）
     */
    public function shopDetail()
    {
        $this->check_shop();
        $order_id = isset($_POST['order_id']) ? $_POST['order_id'] : 0;
        $store_id = intval($this->store['store_id']);
        if ($store_shop = M(_view('Merchant_store_shop'))->where(array('store_id' => $store_id))->find()) {
            $arr['deliver_type'] = $store_shop['deliver_type'];
            $arr['is_open_pick'] = $store_shop['is_open_pick'];
            $arr['is_change'] = $this->staff_session['is_change'];
            $arr['is_change'] = $this->staff_session['is_change'];
            $arr['freight_alias'] = $store_shop['freight_alias'] ? $store_shop['freight_alias'] : L_('配送费');
            $arr['pack_alias'] = $store_shop['pack_alias'] ? $store_shop['pack_alias'] : L_('打包费');
            $arr['ecard_alias_name'] = $this->config['ecard_alias_name'];
        } else {
            $this->returnCode(1, null, L_('店铺信息不存在'));
        }

        $config_data = D('Config_data')->get_config_data();
        $deliver_model_switch = ($config_data && $config_data['deliver_model_switch']) ?  true : false;
        $arr['deliver_model_switch'] = $deliver_model_switch;

        $detail_arr = array('mer_id' => $this->store['mer_id']);
        if (strlen($order_id) == 20 || strlen($order_id) == 21) {
            $detail_arr['real_orderid'] = $order_id;
        } else {
            $detail_arr['order_id'] = $order_id;
        }
        $order = D("Shop_order")->get_order_detail($detail_arr);
        //验证是否有提交权限
        if($order['is_pick_in_store']==2&&!$this->checkPickRule($order['pick_id'])){
            $this->returnCode(1,'','没有分配当前自提点权限!');
        }

        if ($order) {
            if ($order['pay_type'] == 'offline' && empty($order['third_id'])) {
                $payment = rtrim(rtrim(number_format($order['price'] - $order['card_price'] - $order['merchant_balance'] - $order['card_give_money'] - $order['balance_pay'] - $order['payment_money'] - $order['score_deducte'] - floatval($order['coupon_price']), 2, '.', ''), '0'), '.');
            }
            $sure = true; //可以确认消费和取消
            if ($order['is_pick_in_store'] == 0) {
                $supply = D('Deliver_supply')->field('uid')->where(array('order_id' => $order['order_id'], 'item' => 2))->find();
                if (isset($supply['uid']) && $supply['uid']) {
                    $sure = false;
                }
            }

            $discount_price = floatval(round($order['discount_price'] + $order['freight_charge'] + $order['packing_charge'] + $order['other_money'], 2));
            $order['card_discount'] = $order['card_discount'] == 0 ? 10 : $order['card_discount'];
            $orderStatus = D('Shop_order_log')->where(array('order_id' => $order['order_id']))->order('id DESC')->find();
            $orderStatus['dateline'] = date('Y-m-d H:i', $orderStatus['dateline']);

            if ($order['expect_use_time']) {
                if($order['is_pick_in_store'] == 2){
                    $order['expect_use_time'] = ($order['is_right_now'] ? '<span style="color:#e93e14;">'.L_('立即送达').' </span>'.date('H:i',$order['expect_use_time']) : date('Y-m-d H:i', $order['expect_use_time'])) . ' - ' . date('H:i', $order['expect_use_time'] + 1200);
                }else{
                    $order['expect_use_time'] = ($order['is_right_now'] ? '<span style="color:#e93e14;">'.L_('立即送达').' </span>'.date('H:i',$order['expect_use_time']) : date('Y-m-d H:i', $order['expect_use_time'])) . ' - ' . date('H:i', $order['expect_use_time'] + 900);
                }
            } else {
                $order['expect_use_time'] = L_('尽快');
            }

            //支付手续费
            $payment_money_order_db = M("payment_money_order");
            $payment_money_order = $payment_money_order_db->where("order_id=".$order_id)->find();
            $order['service_charge'] = '';
            if($payment_money_order && $payment_money_order['service_charge'] != '0.00'){
                $order['service_charge'] = $payment_money_order['service_charge'];
            }
            //获取周期购订单配送列表
            $periodic_list = array();
            if($order['periodic_type']){
                $periodic_list = D('Mall_periodic_deliver')->get_order_deliver($order['order_id'],0);
				foreach($periodic_list as $key => $val){
					if($val['status']==4){
						$periodic_list[$key]['deliver_num'] = '';
					}
					$periodic_list[$key]['deliver_time'] = date('Y-m-d',$val['deliver_time']);
					$periodic_list[$key]['accept_time'] = date('Y-m-d H:i:s',$val['accept_time']);
                    $periodic_list[$key]['arrive_time'] = date('Y-m-d H:i:s',$val['arrive_time']);
				}
                $last_periodic_id = D('Mall_periodic_deliver')->where(array('order_id'=>$order['order_id'],'status'=>0))->order('deliver_num ASC,id ASC')->find();//最新的待配送的订单；
				$periodic_total = D('Mall_periodic_deliver')->where(array('order_id'=>$order['order_id'],'status'=>array('neq',4)))->count();
				$periodic_done = D('Mall_periodic_deliver')->where(array('order_id'=>$order['order_id'],'status'=>2))->count();
            }
			if($order['is_pick_in_store']==2){
				$botton_name = L_('确认消费');
			}else{
				$botton_name = L_('发货');
			}
			
			if($last_periodic_id){
				if($order['is_pick_in_store']==2){
					$botton_name = '第'.$last_periodic_id['deliver_num'].'期自提取货';
				}else{
					$botton_name = '第'.$last_periodic_id['deliver_num'].'期发货';
				}
			}
            $arr['order_details'] = array(
                'sure' => $sure,
                'orderid' => $order['orderid'],
                'order_id' => $order['order_id'],
                'real_orderid' => $order['real_orderid'],
                'username' => $order['username'],
                'userphone' => $order['userphone'],
                'create_time' => date('Y-m-d H:i:s', $order['create_time']),
                'pay_time' => date('Y-m-d H:i:s', $order['pay_time']),
                'expect_use_time' => $order['expect_use_time'],
                'is_pick_in_store' => $order['is_pick_in_store'],//配送方式 0：平台配送，1：商家配送，2：自提，3:快递配送
				'is_booking' => $order['is_booking'] ? true :false,
                'expect_pick_time' => $order['expect_pick_time'] ? date('Y-m-d H:i:s', $order['expect_pick_time']) : '',
                'address' => $order['address'],
                'deliver_str' => $order['deliver_str'],
                'deliver_status_str' => $order['deliver_status_str'],
                'note' => isset($order['desc']) && $order['desc'] ? $order['desc'] : L_('无'),
                'invoice_head' => $order['invoice_head'],//发票抬头
                'pay_status' => $order['pay_status_print'],
                'order_status' => $order['order_status'],
                'pay_type_str' => $order['pay_type_str'],
                'status_str' => $order['status_str'],
                'score_used_count' => $order['score_used_count'],//抵用的积分
                'score_deducte' => strval(floatval($order['score_deducte'])),//积分兑现的金额
                'card_give_money' => strval(floatval($order['card_give_money'])),//会员卡赠送余额
                'merchant_balance' => strval(floatval($order['merchant_balance'])),//商家余额
                'balance_pay' => strval(floatval($order['balance_pay'])),//平台余额
                'qiye_pay' => strval(floatval($order['qiye_pay'])),//企业预存款余额
                'payment_money' => strval(floatval($order['payment_money'])),//在线支付的金额
                'service_charge' => $order['service_charge'],//在线支付手续费
                'change_price' => strval(floatval($order['change_price'])),//店员修改前的原始价格（如果是0表示没有修改过，可不显示）
                'change_price_reason' => $order['change_price_reason'],//店员修改价格的理由
                'card_id' => $order['card_id'],
                'card_price' => strval(floatval($order['card_price'])),//商家优惠券的金额
                'coupon_price' => strval(floatval($order['coupon_price'])),//平台优惠券的金额
                'payment' => isset($payment) ? $payment : 0,
                'use_time' => $order['use_time'] != 0 ? date('Y-m-d H:i:s', $order['use_time']) : '0',
                'last_staff' => $order['last_staff'],
                'status' => $order['status'],
                'paid' => $order['paid'],
                'register_phone' => $order['register_phone'],//注册时的用户手机号
                'lat' => $order['lat'],
                'lng' => $order['lng'],
                'cue_field' => $order['cue_field'],//商家自定义字段值（如果没有的话是空 即：''）
                'card_discount' => $order['card_discount'],//会员卡折扣
                'goods_price' => strval(floatval($order['goods_price'])),//商品的总价
                'freight_charge' => strval(floatval($order['freight_charge'])),//配送费
                'packing_charge' => strval(floatval($order['packing_charge'])),//打包费
                'total_price' => strval(floatval($order['total_price'])),//订单总价
                'merchant_reduce' => strval(floatval($order['merchant_reduce'])),//商家优惠的金额
                'balance_reduce' => strval(floatval($order['balance_reduce'])),//平台优惠的金额
                'price' => strval(floatval($order['price'])),//实际支付金额
                'distance' => round($order['distance'] / 1000, 2),//距离
                'discount_price' => strval($discount_price),//折扣后的总价  = floatval(round($order['discount_price'] + $order['freight_charge'] + $order['packing_charge'], 2));
                'minus_price' => strval(floatval(round($order['merchant_reduce'] + $order['balance_reduce'], 2))),//平台和商家的优惠金额
                'go_pay_price' => strval(floatval(round($discount_price - $order['merchant_reduce'] - $order['balance_reduce'], 2))),//应付的金额
                'minus_card_discount' => strval(floatval(round(($discount_price - $order['merchant_reduce'] - $order['balance_reduce'] - $order['freight_charge']) * (1 - $order['card_discount'] * 0.1), 2))),//折扣与优惠的优惠金额
                'notes' => L_('注：改成已消费状态后同时如果是未付款状态则修改成线下支付已支付，状态修改后就不能修改了'),
                'order_from_txt' => $this->order_froms[$order['order_from']],
                'order_from' => $order['order_from'],
                'deliver_log_list' => $order['deliver_log_list'],
                'orderStatus' => $orderStatus,
                'express_name' => isset($order['express_name']) ? $order['express_name'] : '',
                'express_number' => $order['express_number'],
                'change_price_reason' => $order['change_price_reason'],
                'change_price' => floatval($order['change_price']),
                'cancel_type' => $order['cancel_type'],
                'other_money' => floatval($order['other_money']),
                'fetch_number' => $order['fetch_number'],
                'order_from_name' => $this->order_froms[$order['order_from']],
                'deliverName' => !empty($this->config['deliver_name']) ? $this->config['deliver_name'] : L_('平台配送'),
                'use_ecard_price' => $order['use_ecard_price'],
                'periodic_list' => $periodic_list,
                'periodic_deliver_id' => $last_periodic_id?$last_periodic_id['id']:'',
				'periodic_total' =>$periodic_total,
				'periodic_done' =>$periodic_done,
                'third_id' =>$order['third_id'],
				'botton_name' => $botton_name,
				'cancel_time' => $order['cancel_time'] ? date('Y-m-d H:i:s', $order['cancel_time']) : '',
                'is_weight' => $order['is_weight'],
                'last_status' => D('Shop_order_log')->get_last_log($order['order_id'],'to_wap_usr'),
                'expect_take_time' => ''
            );
            if ($order['is_pick_in_store'] != 2 && $order['is_pick_in_store'] != 3 && $order['order_status'] >= 1 && in_array($order['status'], [0, 1]) && $order['expect_take_time'] > 0) {
                $supply = D('Deliver_supply')->where(['order_id' => $order['order_id'], 'item' => 2])->find();
                if (empty($supply)) {
                    $arr['order_details']['expect_take_time'] = date('Y-m-d H:i', $order['expect_take_time']);
                }
            }

            if ($order['order_from'] == 1) {
                $arr['order_details']['vip_level_reduce_money'] = $order['vip_level_reduce_money'] > 0 ? $order['vip_level_reduce_money'] : 0;
                //定制--实名认证 显示字段
                $is_open_check = 0; //是否开启实名认证  0关闭   1开启
                $img_check = 0; //实名认证方式 0 姓名+身份证号   1 姓名+身份证+身份证正反面照片

                $check_type = M('Shop_order')->field('is_open_check,img_check')->where(['order_id' => $order['order_id']])->find();
                if ($check_type['is_open_check'] == 1) {
                    $is_open_check = 1;
                    if ($check_type['img_check'] == 1) {
                        $img_check = 1;
                    }
                }

                if($is_open_check == 1) { //商品需要实名认证
                    //查询该用户是否已实名
                    $check_id_card_info = M('User_authentication')->field('user_truename,user_id_number,authentication_img,authentication_back_img,authentication_status')->where(['uid'=>$order['uid'],'authentication_status'=>1])->find();
                    if($img_check == 1) { //姓名 + 身份证号 + 照片
                        $arr['order_details']['user_truename'] = !empty($check_id_card_info['user_truename'])?$check_id_card_info['user_truename']:'-';
                        $arr['order_details']['user_id_number'] = !empty($check_id_card_info['user_id_number'])?$check_id_card_info['user_id_number']:'-';
                        $arr['order_details']['authentication_img'] = !empty($check_id_card_info['authentication_img'])?$check_id_card_info['authentication_img']:'';
                        $arr['order_details']['authentication_status'] = !empty($check_id_card_info['authentication_status'])?$check_id_card_info['authentication_status']:'';
                    } else {
                        $arr['order_details']['user_truename'] = !empty($check_id_card_info['user_truename'])?$check_id_card_info['user_truename']:'-';
                        $arr['order_details']['user_id_number'] = !empty($check_id_card_info['user_id_number'])?$check_id_card_info['user_id_number']:'-';
                    }
                }

                $arr['order_details']['is_open_check'] = $is_open_check;
                $arr['order_details']['img_check'] = $img_check;

                $arr['seckill_alias_name'] = C('config.seckill_alias_name') ? C('config.seckill_alias_name') : L_('限时优惠');
            }

            // 计算
            $sub_total = floatval($arr['order_details']['discount_price']);
            if ($arr['order_details'] && $arr['order_details']['freight_charge'] >0) {
                $sub_total -= floatval($arr['order_details']['freight_charge']);
            }
            if ($arr['order_details'] && $arr['order_details']['other_money'] >0) {
                $sub_total -= floatval($arr['order_details']['other_money']);
            }
            $arr['sub_total'] = round_number($sub_total, 2);
            if ($arr['order_details'] && $arr['order_details']['minus_price'] >0) {
                $minus_total_price = $sub_total - floatval($arr['order_details']['minus_price']);
            } else {
                $minus_total_price = $sub_total;
            }
            $arr['minus_total_price'] = round_number($minus_total_price, 2);


            $goods_image_class = new goods_image();
            //商品图片列表
            $img_list = array();

            $tempList = array();
            foreach ($order['info'] as $v) {
                if($v['give_goods_id']){
                    $shop_goods_name = D('Shop_goods')->field('name,price')->where(array('goods_id'=>$v['give_goods_id']))->find();
                }
                if($v['give_coupon_id']){
                    $coupon = D('Card_new_coupon')->field('name')->where(array('coupon_id'=>$v['give_coupon_id']))->find();
                }
                /*--获取商品图片 2018/12/21 */
                $goods_id = $v['goods_id'];
                /*获取商品图片*/
                $goods_image = D("Shop_order")->get_order_goods_img($v, $goods_image_class);

                $discount_price = floatval($v['discount_price']) > 0 ? floatval($v['discount_price']) : floatval($v['price']);
                $tGoods = array(
                    'name' => $v['name'],
                    'discount_type' => $v['discount_type'],
                    'price' => strval(floatval($v['price'])),
                    'discount_price' => strval($discount_price),
                    'spec' => empty($v['spec']) ? '' : $v['spec'],
                    'num' => $v['num'],
                    'number' => $v['number'],
                    'total' => strval(floatval($v['price'] * $v['num'])),
                    'discount_total' => strval(floatval($discount_price * $v['num'])),
                    'is_seckill' => $v['is_seckill'],
                    'image' => $goods_image,
                    'goods_id' => $v['goods_id'],
                    'host_goods_id' => $v['host_goods_id'],
                    'uniqueness_number' => $v['uniqueness_number'],
                    'give_goods_id'=>$shop_goods_name['name'],
                    'give_goods_price'=>$shop_goods_name['price'],
                    'give_coupon_id'=>$coupon['name'],
                    'detail_id' => $v['id'],
                    'sell_type' => $v['sell_type'],
                    'goods_weight' => $v['goods_weight'],
                    'goods_total_weight' => getFormatNumber($v['goods_weight'] * $v['num']),
                    'real_weight' => $v['real_weight'],
                    'is_give'=>$v['is_give']
                );

                $index = isset($v['packname']) && $v['packname'] ? $v['packname'] : 0;
                if (isset($tempList[$index])) {
                    $tempList[$index]['list'][] = $tGoods;
                } else {
                    $tempList[$index] = array('name' => $v['packname'], 'list' => array($tGoods));
                }
                if ($order['order_from'] == 1) {
                    $discount_arr = explode(',', $v['discount_rate']);
                    $arr['store_discount'] = bcdiv($discount_arr['0'], 10);
                }
            }
            if (count($tempList) == 1) {
                $tempList[$index]['name'] = '';
            }

            // 组合附属商品
            foreach ($tempList as $key => $value) {
                $tempList[$key]['list'] = D('Shop_goods')->combination_data($value['list'], 'spec');
            }

            $arr['info'] = $tempList;
            /*只修改商城来源的订单*/
            if ($order['order_from'] == 1) {
                if ($order['vip_level_reduce_money'] > 0) {
                    $order['discount_detail']['vip_level'] = array('discount_type' => 6, 'vipLevelReduceMoney' => $order['vip_level_reduce_money'], 'vip_discount' => $order['vip_level_discount'] * 0.1);
                }
                $order['discount_detail'] = $this->discount_sort($order['discount_detail'], $order['vip_level_after']);
                //获取店铺折扣价格
                $arr['store_discount_price'] = getFormatNumber($order['goods_price'] - $order['discount_price'] - $order['vip_level_reduce_money']);
            }
            $arr['discount_detail'] = $order['discount_detail'] ?: '';

            // 隐私号处理
            $arr['order_details']['virtual_phone_1'] = $order['virtual_phone_arr'][0] ? $order['virtual_phone_arr'][0]: [];
            if ($order['virtual_phone_arr'][1]) {
                $arr['order_details']['virtual_phone_2'] = $order['virtual_phone_arr'][1];
            }else{
                $arr['order_details']['virtual_phone_2'] = $arr['order_details']['virtual_phone_1'];
            }
            if ($order['virtual_phone_arr']) {
                $arr['order_details']['userphone'] = phone_show($arr['order_details']['userphone']);
                $arr['order_details']['register_phone'] = phone_show($arr['order_details']['register_phone']);
            }
            // 隐私号码是否过期
            if ($order['virtual_phone_overtime']>time()) {
                $arr['order_details']['virtual_phone_overtime'] = 0;
            }else{
                $arr['order_details']['virtual_phone_overtime'] = 1;
            }
        } else {
	    if ($_POST['from'] == 'qrcode') {
                $this->returnCode(1, null, L_('您扫码的二维码所属店铺与当前登录店员店铺不一致，请确认后再操作。'));
            } else {
                $this->returnCode(1, null, L_('订单信息错误！'));
            }
         
        }
        if (empty($arr['info'])) {
            $arr['info'] = array();
        }
        //退货信息
        $refundList = D('Shop_order_refund')->field(true)->where(array('order_id' => $order_id, 'status' => 0))->order('id DESC')->select();

        $sql = "SELECT g.image, d.goods_id, d.name, d.price, d.num, d.unit, d.spec, d.id, d.refund_id FROM " . C('DB_PREFIX') . "shop_goods AS g INNER JOIN " . C('DB_PREFIX') . "shop_refund_detail AS d ON g.goods_id=d.goods_id WHERE d.order_id={$order_id}";
        $list = D()->query($sql);
        $data = array();
        $goods_image_class = new goods_image();
        foreach ($list as $row) {
            $row['price'] = $row['price'] < 0 ? 0 : $row['price'];
            $image = '';
            if (!empty($row['image'])) {
                $tmp_pic_arr = explode(';', $row['image']);
                foreach ($tmp_pic_arr as $key => $value) {
                    if (empty($image)) {
                        $image = $goods_image_class->get_image_by_path($value, 's');
                        break;
                    }
                }
            }
            $row['image'] = $image;
            $data[$row['refund_id']][] = $row;
        }
        //状态(0：用户申请，1：商家同意退货，2：商家拒绝退货，3：用户重新申请，4：取消退货申请)
        $status = array(L_('商家审核中'), L_('已完成退货'), L_('商家拒绝退货'), L_('商家审核中'), L_('取消退货申请'));
        foreach ($refundList as &$refund) {
            $refund['image'] = explode(',', $refund['image']);
            $refund['goodsList'] = isset($data[$refund['id']]) ? $data[$refund['id']] : array();
            $refund['showStatus'] = $status[$refund['status']];
            //一个订单的退款有些，直接使用后循环查询
            $refund['logList'] = D('Shop_refund_log')->field(true)->where(array('refund_id' => $refund['id']))->order('id ASC')->select();
        }
        if ($refund['image']) {
            foreach ($refund['image'] as &$r_img) {
                $r_img = $r_img?file_domain() . $r_img:'';
            }
        }
        $rlist = D('Shop_order_refund')->field(true)->where(array('order_id' => $order_id, 'status' => 1))->order('id DESC')->select();
        $rids = array();
        foreach ($rlist as $rl) {
            $rids[] = $rl['id'];
        }
        $finishRefunds = array();
        if ($rids) {
            $finishRefunds = D('Shop_refund_detail')->field(true)->where(array('refund_id' => array('in', $rids)))->select();

            // 组合附属商品
            //$finishRefunds = D('Shop_goods')->combination_data($finishRefunds, 'spec');
        }
        $arr['finishRefunds'] = $finishRefunds;
        if ($refund) {
            for ($i = 0; $i < count($refund['logList']); $i++) {
                $refund['logList'][$i]['dateline'] = $refund['logList'][$i]['dateline'] != 0 ? date('Y-m-d H:i:s', $refund['logList'][$i]['dateline']) : '0';
            }
        }
        $arr['refund'] = $refund;

        //周期购商品配送信息
        
       //实名认证字段
        $authentication = array();
        $is_authentication = D('Merchant_store')->where(array('store_id' => $order['store_id']))->getField('is_authentication');
        if($order['order_from']==1&&$is_authentication){
            $authenticationInfo = D('Mall_authentication')->where(array('uid'=>$order['uid']))->find();
            $authenticationInfo['authentication_field'] = unserialize($authenticationInfo['authentication_field']);
            $authentication = $authenticationInfo['authentication_field'];
        }
        $arr['authentication'] = $authentication;
        
        $this->returnCode(0, $arr);
    }

    //排序优惠信息
    public function discount_sort($discount_list=null,$vip_level_discount_sequence=''){
        if(empty($discount_list)){
            return array();
        }
        $arr=array();
        $arr[]='newuser';
        $arr[]='minus';
        $arr[]='system_newuser';
        $arr[]='system_minus';
        $vip_level_discount_sequence=is_numeric($vip_level_discount_sequence)?$vip_level_discount_sequence:C('config.vip_level_discount_sequence');
        if($vip_level_discount_sequence==0){
            array_unshift($arr,'vip_level');
        }else{
            $arr[]='vip_level';
        }
        $arr[]='delivery';
        $list=array();
        foreach ($discount_list as $k => $v) {
            $i=array_search($k,$arr);
            if(is_numeric($i)){
                $list[$i]=$v;
            }
        }
        ksort($list);
        $list2=array();
        foreach ($list as $k => $v) {
            $key=$arr[$k];
            $list2[$key]=$v;
        }
        return $list2;
    }

    /**
     * 快店的退款
     */
    public function replyRefund()
    {
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $refund_id = isset($_POST['refund_id']) ? intval($_POST['refund_id']) : 0;
        $type = isset($_POST['type']) ? htmlspecialchars(trim($_POST['type'])) : 'agree';
        $reply_content = isset($_POST['reply_content']) ? htmlspecialchars(trim($_POST['reply_content'])) : '';

        $order = D('Shop_order')->field(true)->where(array('order_id' => $order_id, 'store_id' => $this->store['store_id']))->find();
        if (empty($order)) {
            $this->returnCode(1, null, L_('订单信息错误'));
        }
        if (!in_array($type, array('agree', 'disagree'))) {
            $this->returnCode(1, null, L_('请选择正确的回复形式'));
        }

        if($order['status']==4){
            $goods_total_all = D('Shop_order_detail')->where(['order_id'=>$order_id])->sum('num');
            $refund_goods_total_all = D('Shop_order_refund')->join(' as r inner join '.C("DB_PREFIX").'shop_refund_detail as rd on rd.refund_id = r.id')
                ->where(['r.order_id'=>$order_id,'r.status'=>1])->sum('rd.num');
            if(!$refund_goods_total_all||$refund_goods_total_all >= $goods_total_all){
                $this->returnCode(1, null, L_('该订单已退款，请不要重复操作'));
            }
        }

        $refund = D('Shop_order_refund')->field(true)->where(array('order_id' => $order_id, 'id' => $refund_id,'status' => array('neq',4)))->find();
        if (empty($refund)) {
            $this->returnCode(1, null, L_('订单的退货信息错误'));
        }
        if ($refund['status'] != 0) {
            $this->returnCode(1, null, L_('当前的退货信息无需修改'));

        }

        if($order['order_from']==1){
            $order_type='mall';
        }else{
            $order_type='shop';
        }
        //状态(0：用户申请，1：商家同意退货，2：商家拒绝退货，3：用户重新申请，4：取消退货申请)
        if ($type == 'agree') {
            //先减余额支付，在进行线上支付的退款
            //已退款的金额的查询
//            $refundMoney = D('Shop_order_refund')->field('sum(balance_pay) as balance_pay, sum(merchant_balance) as merchant_balance, sum(card_give_money) as card_give_money, sum(payment_money) as payment_money, sum(score_used_count) as score_used_count, sum(score_deducte) as score_deducte')->where(array('order_id' => $order_id, 'status' => 1))->find();
            //积分->商家余额->平台余额->在线支付的金额
            $refundMoney = $refund;
            $nowPayMoney = floatval($refund['price']);//当前要退款的金额

            // 查看要退换的积分 积分不够则从退款金额抵扣 20191219
            $returnScoreBack = D('User')->refundScoreBack($order['uid'],$order_id,$order_type,$nowPayMoney);

            if($this->config['open_allinyun']==1){
                $nowPayMoney = $returnScoreBack['refundMoney'];

                import('@.ORG.AccountDeposit.AccountDeposit');
                $deposit = new AccountDeposit('Allinyun');
                $allyun = $deposit->getDeposit();

                $allinyun_user = D('Deposit')->allinyun_user($this->user_session['uid']);

                if($allinyun_user['bizUserId']!=''){
                    $allyun->setUser($allinyun_user);
                    $params['bizOrderNo'] = 'shop_'.$order['orderid'];
                    $params['oriBizOrderNo'] = $order['third_id'];
                    $params['amount'] = intval($nowPayMoney*100);
                    $res = $allyun->refundApply($params);

                    if($res['status']=='OK'){
                        $data_shop_order['order_id'] = $order['order_id'];
                        $data_shop_order['status'] = 4;
                        $data_shop_order['last_time'] = time();
                        $data_shop_order['refund_detail'] = serialize($res);
                        $return = $this->shop_refund_detail($order, $order['store_id']);
                        if ($return['error_code']) {
                            exit(json_encode(array('errcode' => 1, 'msg' =>$return['msg'])));
							// $this->error_tips($return['msg']);
                        }
                        D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 9));
                        D('Shop_order')->data($data_shop_order)->save();
	                    D('Shop_order_refund')->recoverLackWeightMoney($order_id, $refund_id);
                        // 退还获得积分 20191219
                        $returnScoreBack = D('User')->refundScoreBack($order['uid'],$order_id,$order_type,floatval($refund['price']),1);
                        //增加消息通知
                        $url = '\app\common\model\service\user\UserNoticeService/addNotice';
                        $params['type'] = 0;
                        $params['business'] = 'shop';
                        $params['order_id'] = $order['order_id'];
                        $params['mer_id'] = $order['mer_id'];
                        $params['store_id'] = $order['store_id'];
                        $params['uid'] = $order['uid'];
                        $params['title'] = '商家已经同意退款';
                        $params['content'] = '您申请的退款商家已经同意，请查看订单详情';
                        invoke_v20_service($url, [$params]);
                        $this->returnCode(0, L_('退款成功'));
                    }else{
                        $this->returnCode(1, null, $res['message']);
                    }
                }
            }

            // 计算退款时每种支付方式需要退款的金额
            $returnRefundMoney = D('Shop_order')->getRrfundDetail($refundMoney,$returnScoreBack);
            // 积分
            $score_used_count=$returnRefundMoney['score_used_count'];
            // 积分抵扣金额
            $score_deducte=$returnRefundMoney['score_deducte'];
            // 商家余额
            $merchant_balance=$returnRefundMoney['merchant_balance'];
            // 商家赠送余额
            $card_give_money=$returnRefundMoney['card_give_money'];
            // 平台余额
            $balance_pay=$returnRefundMoney['balance_pay'];
            // ecard
            $ecard_pay=$returnRefundMoney['ecard_pay'];
            // 剩余退还金额
            $backMoney=$returnRefundMoney['backMoney'];

            $payment_money = 0;
            $third_refund_id = 0;
            if ($refundMoney['payment_money'] > 0) {
                $payment_money = $refundMoney['payment_money'];
                $payment_money_order = M('Payment_money_order')->where(array('order_id'=>$order['order_id'],'order_type'=>'shop','pay_type'=>$order['pay_type'],'paid_money'=>array('gt',0)))->find();
                if($payment_money == $payment_money_order['payment_money']){
                    //如果是全退，那就把用户实际支付的钱一起退了吧。包括支付手续费
                    $payment_money = $payment_money_order['paid_money'];
                }

                if($payment_money_order['pay_rate'] > 0 && $order['pay_type'] != 'weixin'){
                    $payment_money = getFormatNumber($payment_money*$payment_money_order['pay_rate']);
                }

                // 退回积分不够，需要扣除相应金额
                if ($backMoney) {
                    $payment_money = getFormatNumber($payment_money - $backMoney);
                    if ($payment_money<0) {
                        // 不够抵扣
                        $backMoney -= $payment_money;
                        $payment_money = 0;
                    }else{
                        // 足够抵扣
                        $backMoney=0;
                    }
                }
            }

            if ($payment_money > 0) {
                if($payment_money_order['pay_method'] == 'mallbook'){//mallbook通道支付退款
                    import('@.ORG.pay.Mallbookpay.MallbookPayService');
                    $payService = new MallbookPayService();
                    $go_refund_param = $payService->refund(
                        $payment_money_order['orderid'],
                        $payment_money,
                        $payment_money_order['mallbook_order_no']
                    );
                }else
				if($payment_money_order && ($payment_money_order['pay_method'] == 'wft' || $payment_money_order['pay_method'] == 'paylinx' || $payment_money_order['pay_method'] == 'wepayez' || $payment_money_order['pay_method'] == 'weikebaba' || $payment_money_order['pay_method'] == 'xiaoma' || $payment_money_order['pay_method'] == 'icbcpay')){

					if($payment_money_order['pay_method'] == 'wft'){
						$pay_method = 'wftpay';
					}
					if($payment_money_order['pay_method'] == 'paylinx'){
						$pay_method = 'paylinx';
					}
                    if($payment_money_order['pay_method'] == 'wepayez'){
                        $pay_method = 'wepayez';
                    }
                    if($payment_money_order['pay_method'] == 'weikebaba'){
                        $pay_method = 'weikebaba';
                    }
                    if($payment_money_order['pay_method'] == 'xiaoma'){
                        $pay_method = 'xiaoma';
                    }
                    if($payment_money_order['pay_method'] == 'icbcpay'){
                        $pay_method = 'icbcpay';
                    }
					import('@.ORG.LowFeePay');
					$lowfeepay = new LowFeePay($pay_method);
					$order['payment_refund_money'] = $payment_money;
					$order['order_type'] = 'shop';
					$order['orderNo'] = $payment_money_order['orderNo'];
					if($order['mer_id'] && $payment_money_order['pay_method'] == 'wft'){
	                    $mer_juhe = M('Merchant_juhe_config')->where(array('mer_id'=>$order['mer_id']))->find();
						if(!empty($mer_juhe)){ 
							$lowfeepay->userId =$mer_juhe['userid'];
							$order['is_own'] =1 ;
						    $order['orderNo'] =  $order['orderNo'].'_1';
						}
						
	                }elseif($order['mer_id'] && $payment_money_order['pay_method'] == 'weikebaba'){
                        $mer_juhe = M('Weikebaba')->where(array('mer_id'=>$order['mer_id']))->find();
                        if(!empty($mer_juhe)){ 
                            $lowfeepay->user_id = $mer_juhe['user_id'];
                            $lowfeepay->key = $mer_juhe['key'];
                            $order['is_own'] =1 ;
                            $order['orderNo'] =  $order['orderNo'].'_1';
                        }
                        
                    }
					$go_refund_param= $lowfeepay->refund($order);
				}else if($payment_money_order && $payment_money_order['pay_method'] == 'lichu'){//利楚支付
                    $config = json_decode($payment_money_order['third_plat_data'],true);
                    $config['out_trade_no'] = $payment_money_order['orderNo'];
                    require_once $_SERVER['DOCUMENT_ROOT'] . "/v20/extend/pay/LichuPay.php";
                    $pay = new LichuPay();
                    $go_refund_param= $pay->refund([
                        'real_orderid'=>$order['real_orderid'],
                        'refund_fee'=>$payment_money_order['paid_money'] * 100
                    ],$config);
                }else if($payment_money_order && strpos($payment_money_order['pay_type'],'xiantao') !== false){// 智慧仙桃支付退款
                    if($payment_money_order['paid_money'] == $payment_money){// 不支持部分退款
                        import('@.ORG.pay.XianTaoCard');
                        $pay = new XianTaoCard();

                        $now_user = D('User')->get_user($order['uid']);
                        try{
                            // 退款
                            $go_refund_param = $pay->refund($payment_money_order['orderNo'], $now_user['phone']);
                        } catch(Exception $e){
							$this->returnCode(1, null, $e->getMessage());
                        }
                    }else{
						$this->returnCode(1, null, '仙桃支付方式不支持部分退款');
                    }
                }else if($payment_money_order && $payment_money_order['pay_method'] == 'chinaums'){
                    import("@.ORG.pay.Chinaums");
                    $pay = new Chinaums();
                    if($payment_money_order['is_mobile_pay'] == 3){
                        $mid = C("config.chinaums_mid_wxapp");
                    }
                    if($payment_money_order['is_mobile_pay'] == 2){
                        $mid = C("config.chinaums_mid_app");
                    }
                    if($payment_money_order['is_mobile_pay'] == 1){
                        $mid = C("config.chinaums_mid_wx");
                    }
                    $chinaums_merno = '';
                    if($order['mer_id']){
                        $now_merchant = M("Merchant")->where(['mer_id'=>$order['mer_id']])->find();
                        if(!empty($now_merchant['chinaums_merno'])){
                            $chinaums_merno = $now_merchant['chinaums_merno'];
                        }
                    }
                    $order['order_type'] = $order['order_from'] == '1' ? 'mall' : 'shop';
                    $go_refund_param= $pay->refund($order['order_type'].'_'.$payment_money_order['orderid'], $payment_money_order['paid_money'], $payment_money, $payment_money_order['third_plat_data'], $chinaums_merno, $mid);
                }else if($payment_money_order && $payment_money_order['pay_method'] == 'lakala'){
                	import('@.ORG.pay.Lakala');
					$pay_config = json_decode($payment_money_order['pay_config'],true);
                	$pay = new Lakala([],$pay_config);
                	
                	$go_refund_param= $pay->refund('shop_'.$payment_money_order['orderid'], $payment_money_order['paid_money'], $payment_money, $payment_money_order['third_plat_data']);
                }else if($payment_money_order && $payment_money_order['pay_method'] == 'scrcu'){
                    import("@.ORG.pay.Scrcu");
                    $pay = new scrcu();
                    
                    $scrcu_mer_no = '';
                    if($order['mer_id']){
                        $now_merchant = M("Merchant")->where(['mer_id'=>$order['mer_id']])->find();
                        if(!empty($now_merchant['scrcu_mer_no'])){
                            $scrcu_mer_no = $now_merchant['scrcu_mer_no'];
                        }
                    }

                    $go_refund_param= $pay->refund($order['orderid'], $payment_money_order['paid_money'], $scrcu_mer_no);
                }else{
					if ($order['is_own'] && $order['pay_type'] != 'nmgpay') {
						$pay_method = array();
						$merchant_ownpay = D('Merchant_ownpay')->field('mer_id', true)->where(array('mer_id' => $order['mer_id']))->find();
						foreach ($merchant_ownpay as $ownKey => $ownValue) {
							$ownValueArr = unserialize($ownValue);
							if ($ownValueArr['open']) {
								$ownValueArr['is_own'] = true;
								$pay_method[$ownKey] = array('name' => '', 'config' => $ownValueArr);
							}
						}

                        $now_merchant = D('Merchant')->get_info($order['mer_id']);
                        $open_sub_mchid = C("config.open_sub_mchid");
                        if ($now_merchant['sub_mch_refund'] && $open_sub_mchid && $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0) {
                            $pay_method['weixin']['config']['pay_weixin_appid'] = C("config.pay_weixin_appid");
                            $pay_method['weixin']['config']['pay_weixin_appsecret'] = C("config.pay_weixin_appsecret");
                            $pay_method['weixin']['config']['pay_weixin_mchid'] = C("config.pay_weixin_sp_mchid");
                            $pay_method['weixin']['config']['pay_weixin_key'] = C("config.pay_weixin_sp_key");
                            $pay_method['weixin']['config']['sub_mch_id'] = $now_merchant['sub_mch_id'];
                            $pay_method['weixin']['config']['pay_weixin_client_cert'] = C("config.pay_weixin_sp_client_cert");
                            $pay_method['weixin']['config']['pay_weixin_client_key'] = C("config.pay_weixin_sp_client_key");
                            $pay_method['weixin']['config']['is_own'] = 2 ;
                        }
                        if ($order['order_from'] == 4 && $order['is_own'] == 1) {
                            $wxapp_own_pay = M('Weixin_app_bind')->where(array('other_id' => $_POST['own_pay_mer_id'], 'bind_type' => 0))->find();
                            $pay_method['weixin']['config'] = array(
                                'pay_weixin_appid' => $wxapp_own_pay['appid'],
                                'pay_weixin_key' => $wxapp_own_pay['wxpay_key'],
                                'pay_weixin_mchid' => $wxapp_own_pay['wxpay_merid'],
                                'pay_weixin_appsecret' => $wxapp_own_pay['appsecret'],
                                'is_own' => 1
                            );
                        }
                        
                        if ($order['is_own'] == 2 && $now_merchant['sub_mch_refund'] && C('config.open_sub_mchid') && $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0) {
                            if($order['is_mobile_pay'] == '3'){
                                $pay_method['weixin']['config']['pay_weixin_appid'] = C('config.pay_wxapp_appid');
                                $pay_method['weixin']['config']['pay_weixin_appsecret'] = C('config.pay_wxapp_appsecret');
                            }
                            else{
                                $pay_method['weixin']['config']['pay_weixin_appid'] = C('config.pay_weixin_appid');
                                $pay_method['weixin']['config']['pay_weixin_appsecret'] = C('config.pay_weixin_appsecret');
                            }
                            $pay_method['weixin']['config']['pay_weixin_mchid'] = C('config.pay_weixin_sp_mchid');
                            $pay_method['weixin']['config']['pay_weixin_key'] = C('config.pay_weixin_sp_key');
                            $pay_method['weixin']['config']['sub_mch_id'] = $now_merchant['sub_mch_id'];
                            $pay_method['weixin']['config']['pay_weixin_client_cert'] = C('config.pay_weixin_sp_client_cert');
                            $pay_method['weixin']['config']['pay_weixin_client_key'] = C('config.pay_weixin_sp_client_key');
                            $pay_method['weixin']['config']['is_own'] = 2;
                        }

					} else {
						$is_wap = $order['pay_type'] == 'alipayh5' ? 1 : 0;
						$pay_method = D('Config')->get_pay_method(0, 0, $is_wap, 0,false,true);
					}

					if (empty($pay_method)) {
						$this->returnCode(1, null, L_('支付方式不存在或已关闭'));
						return false;
					}

					$pay_class_name = ucfirst($order['pay_type']);
                    if (in_array($order['pay_type'], ['hqpay', 'hqpay_wx', 'hqpay_al'])) {
                        $pay_class_name = 'Hqpay';
                        empty($pay_method[$order['pay_type']]) && $pay_method[$order['pay_type']] = 1;
                    }
					if (empty($pay_method[$order['pay_type']])) {
						$this->returnCode(1, null, L_('支付方式不存在或已关闭'));
					}
					$import_result = import('@.ORG.pay.' . $pay_class_name);
					if (empty($import_result)) {
						$this->returnCode(1, null, L_('支付方式不存在或已关闭'));
					}

					$order['order_type'] = 'shop';
					$order['order_id'] = $order['orderid'];
					if ($order['order_from'] == 4 && $order['is_own'] != 2) {
						$pay_method['weixin']['config'] = array(
							'pay_weixin_appid' => C('config.pay_wxapp_appid'),
							'pay_weixin_key' => C('config.pay_wxapp_key'),
							'pay_weixin_mchid' => C('config.pay_wxapp_mchid'),
							'pay_weixin_appsecret' => C('config.pay_wxapp_appsecret')
						);
						if ($order['is_own'] == 1) {
							$wxapp_own_pay = M('Weixin_app_bind')->where(array('other_id' => $_POST['own_pay_mer_id'], 'bind_type' => 0))->find();
							$pay_method['weixin']['config'] = array(
								'pay_weixin_appid' => $wxapp_own_pay['appid'],
								'pay_weixin_key' => $wxapp_own_pay['wxpay_key'],
								'pay_weixin_mchid' => $wxapp_own_pay['wxpay_merid'],
								'pay_weixin_appsecret' => $wxapp_own_pay['appsecret'],
								'is_own' => 1
							);
						}
					}
					$now_user = D('User')->get_user($order['uid']);
					if ($pay_class_name == 'Alipay' && $order['is_mobile_pay'] == 2 && C('config.new_pay_alipay_app_public_key') != '' && C('config.new_pay_alipay_app_appid')!= '' && C('config.new_pay_alipay_app_private_key')!= '') {
						$pay_class_name = 'AlipayApp';
						$pay_method['alipay']['config']['new_pay_alipay_app_appid'] = C('config.new_pay_alipay_app_appid');
						$pay_method['alipay']['config']['new_pay_alipay_app_private_key'] = C('config.new_pay_alipay_app_private_key');
						$pay_method['alipay']['config']['new_pay_alipay_app_public_key'] = C('config.new_pay_alipay_app_public_key') ;
					}
					$import_result = import('@.ORG.pay.' . $pay_class_name);
					$pay_class = new $pay_class_name($order, $payment_money, $order['pay_type'], $pay_method[$order['pay_type']]['config'], $now_user, 1);
					$go_refund_param = $pay_class->refund();
				}
				
                if (!(empty($go_refund_param['error']) && $go_refund_param['type'] == 'ok')) {
                    $this->returnCode(1, null, $go_refund_param['msg']);
                } else {
                    $third_refund_id = $go_refund_param['refund_param']['refund_id'];
                }
            }
            /*修改日志别名*/
            if($order['order_from']==1){
                $alias_name=$this->config['mall_alias_name'];
                $alias_name=$alias_name?$alias_name:L_('商城');
                $order_type='mall';
            }else{
                $alias_name=$this->config['shop_alias_name'];
                $order_type='shop';
            }
            $mer_store = D('Merchant_store')->where(array('mer_id' => $order['mer_id'], 'store_id' => $order['store_id']))->find();
            if ($score_used_count != 0) {
                $result = D('User')->add_score($order['uid'], $score_used_count, $alias_name . '部分商品退货, ' . C('config.score_name').'回滚,订单编号' . $order['real_orderid']);
                $param = array('refund_time' => time());
                if ($result['error_code']) {
                    $score_used_count = 0;
                }
            }

            //平台余额退款
            if ($balance_pay > 0) {
                $result = D('User')->add_money($order['uid'], $balance_pay, $alias_name . '部分商品退货,增加余额,订单编号' . $order['real_orderid']);
                if($result['error_code']){
                    $balance_pay = 0;
                }
            }
            //平台E卡退款返回余额
            if($ecard_pay>0){
                $result=D('User')->add_money($order['uid'],$ecard_pay,$alias_name.'部分商品退货，'.$this->config['ecard_alias_name'].'退还增加余额'.$order['real_orderid']);
                if($result['error_code']){
                    $ecard_pay=0;
                }
            }
            //商家会员卡余额退款
            if ($merchant_balance > 0 || $card_give_money > 0) {                
                $card_mer_id = $order['mer_id'];

                // 商家联盟会员卡
                if ($order['union_mer_id']) {
                    $card_mer_id = $order['union_mer_id'];
                }
                $result = D('Card_new')->add_user_money($card_mer_id, $order['uid'],  $merchant_balance, $card_give_money, 0, $alias_name . '部分商品退货,增加余额,订单编号' . $order['real_orderid'], $alias_name . '部分商品退货,增加赠送余额,订单编号' . $order['real_orderid']);
                if ($result['error_code']) {
                    $merchant_balance = 0;
                    $card_give_money = 0;
                }
            }
            $data = array();
            $data['reply_content'] = $reply_content;
            $data['reply_time'] = time();
            $data['status'] = 1;
            $data['score_deducte'] = $score_deducte;
            $data['score_used_count'] = $score_used_count;
            $data['balance_pay'] = $balance_pay;
            $data['ecard_pay']=$ecard_pay;
            $data['merchant_balance'] = $merchant_balance;
            $data['card_give_money'] = $card_give_money;
            $data['payment_money'] = $payment_money;
            $data['third_refund_id'] = $third_refund_id;
            $data['refund_price'] = getFormatNumber($score_deducte+$balance_pay+$ecard_pay+$merchant_balance+$card_give_money+$payment_money);

            D('Shop_refund_log')->add(array('refund_id' => $refund_id, 'status' => 1, 'dateline' => time(), 'note' => $reply_content));
            D('Shop_order_refund')->where(array('order_id' => $order_id, 'id' => $refund_id))->save($data);
            D('Shop_order')->refundTwOrder($order_id,$refund_id);
            D('System_order')->save_order_status($order_type, $order_id, 4);

            $count = D('Shop_order_detail')->where(array('order_id' => $order_id, '_string' => 'refundNum < num'))->count();
            $rcount = D('Shop_order_refund')->where(array('order_id' => $order_id, 'status' => 0))->count();
            $saveData = array('is_apply_refund' => 0);

            if($rcount){// 还有其他未同意的退款申请
                $saveData['is_apply_refund'] = 1;
            }
            
            if (empty($count) && empty($rcount)) {
                $saveData['status'] = 4;
                $saveData['last_time'] = time();
                D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 9));
            }

            // 退还获得积分 20191219
            $returnScoreBack = D('User')->refundScoreBack($order['uid'],$order_id,$order_type,$nowPayMoney,$refund_id);

            /******************** 商家余额减少 start *******************/
            if (!$refund['merchant_money'] && $refund['price']>0) {
                $refund['merchant_money'] = $refund['price'];
            }
            //自有支付和子商户支付商家余额不减少
            if($order['is_own'] > 0 ){
                $refund['merchant_money'] = 0;
            }


            $totalMoney = floatval(round($refund['merchant_money'], 2));
            if($order['is_pick_in_store'] == 3 && $order['freight_charge'] > 0){
                //如果是商家快递配送，那么扣余额的时候要把运费去掉
                //【www.flsdj.com   商城订单用户退款，但是退还的金额和支付的进入不一致】https://www.tapd.cn/47145533/bugtrace/bugs/view?bug_id=1147145533001018943
                $totalMoney = $totalMoney - $order['freight_charge'];
            }
            if ($totalMoney<0) {
                $totalMoney=0;
            }

            // 商家会员卡联盟 商家余额退款减少联盟商家的余额
            if ($data['merchant_balance']&&$order['union_mer_id']) {
                //使用了联盟商家会员卡
                // 联盟商家
                if ($mdata = D('Merchant_money_list')->field('percent,type,money,system_take')->where(array('_string' => "type='shop' or type='mall'", 'income'=>1,'order_id' => $order['real_orderid'],'union_mer_id'=>$order['union_mer_id']))->find()) {
                    // 根据商家会员卡余额比例计算应退金额
                    $union_mer_money = $totalMoney * ($data['merchant_balance']/$refundMoney['price']);
                    $totalMoney = $totalMoney - $union_mer_money;
                    $returnSystemTake = $returnTakePercent= 0;
                    if($mdata['system_take'] > 0){
                        $returnSystemTake = bcmul(bcdiv($union_mer_money,$mdata['money'],2), $mdata['system_take'],2);
                        $returnTakePercent = $mdata['percent'];
                    }
                    D('Merchant_money_list')->use_money($order['union_mer_id'], $union_mer_money, $mdata['type'], $alias_name . '部分商品退货,减少余额,订单编号' . $order['real_orderid'], $order['real_orderid'], $returnTakePercent,$returnSystemTake);
                }

                // 此商家
                if ($mdata = D('Merchant_money_list')->field('percent,type,money, system_take')->where(array('_string' => "type='shop' or type='mall'",'income' => 1, 'order_id' => $order['real_orderid']))->find()) {
                    $returnSystemTake = $returnTakePercent = 0;
                    if($mdata['system_take'] > 0){
                        $returnSystemTake = bcmul(bcdiv($totalMoney,$mdata['money'],2), $mdata['system_take'],2);
                        $returnTakePercent = $mdata['percent'];
                    }
                    D('Merchant_money_list')->use_money(
                        $order['mer_id'], 
                        $totalMoney, 
                        $mdata['type'], 
                        $alias_name . '部分商品退货,减少余额,订单编号' . $order['real_orderid'], 
                        $order['real_orderid'], 
                        $returnTakePercent,
                        $returnSystemTake,
                        [],
                        0,
                        $go_refund_param['refund_param']??[]
                    );
                }
            }else{
                if ($mdata = D('Merchant_money_list')->field('percent,type,money, system_take')->where(array('_string' => "type='shop' or type='mall'",'income'=>1, 'order_id' => $order['real_orderid']))->find()) {
                    $returnSystemTake = $returnTakePercent = 0;
                    if($mdata['system_take'] > 0){
                        $returnSystemTake = bcmul(bcdiv($totalMoney,$mdata['money'],2), $mdata['system_take'],2);
                        $returnTakePercent = $mdata['percent'];
                    }
                    D('Merchant_money_list')->use_money(
                        $order['mer_id'], 
                        $totalMoney, 
                        $mdata['type'], 
                        $alias_name . '部分商品退货,减少余额,订单编号' . $order['real_orderid'], 
                        $order['real_orderid'], 
                        $returnTakePercent, 
                        $returnSystemTake,
                        [],
                        0,
                        $go_refund_param['refund_param']??[]
                    );
                }
            }
            /******************** 商家余额减少 end *******************/

            D('Shop_order')->where(array('order_id' => $order_id))->save($saveData);
            //打印
            $printHaddle = new PrintHaddle();
            $printHaddle->printit($order_id, 'shop_order', 3);
            //给用户发送微信模板消息
            $now_user = D('User')->get_user($order['uid']);
            $href = C('config.site_url').'/wap.php?g=Wap&c=Shop&a=status&order_id='.$order_id;
            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
            $model->sendTempMsg('OPENTM405627933', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' => L_('退款成功！') ,  'keyword1' => $order['real_orderid'], 'keyword2' => floatval($refund['price']).C('config.Currency_txt'),'keyword3'=>date('Y年m月d日 H:i'), 'remark' => L_(L_(L_('点击查看详情！')))));

            if($refund['is_user_apply']){
                D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 62,'note'=>'商家同意用户退款申请'));
            }
            //增加消息通知
            $url = '\app\common\model\service\user\UserNoticeService/addNotice';
            $params['type'] = 0;
            $params['business'] = 'shop';
            $params['order_id'] = $order['order_id'];
            $params['mer_id'] = $order['mer_id'];
            $params['store_id'] = $order['store_id'];
            $params['uid'] = $order['uid'];
            $params['title'] = '商家已经同意退款';
            $params['content'] = '您申请的退款商家已经同意，请查看订单详情';
            invoke_v20_service($url, [$params]);

            $this->returnCode(0, 'ok');
        } else {
            D('Shop_refund_log')->add(array('refund_id' => $refund_id, 'status' => 2, 'dateline' => time(), 'note' => $reply_content));
            $refundDetailList = D('Shop_refund_detail')->field(true)->where(array('refund_id' => $refund_id))->select();
            $orderDetailDB = D('Shop_order_detail');
            foreach ($refundDetailList as $row) {
                $orderDetailDB->where(array('id' => $row['detail_id']))->save(array('refundNum' => 0));
            }
            D('Shop_order_refund')->where(array('order_id' => $order_id, 'id' => $refund_id))->save(array('reply_time' => time(), 'reply_content' => $reply_content, 'status' => 2));

            $update = ['is_apply_refund' => 0];
            if ($order['order_from'] == 1 && $order['status'] == 11) {
                $beforeStatus = D('System_order')->where(['type' => 'mall', 'order_id' => $order_id])->getField('status');
                !is_null($beforeStatus) && $update['status'] = $beforeStatus;
            }
            D('Shop_order')->where(array('order_id' => $order_id))->save($update);
            //给用户发送微信模板消息
            $now_user = D('User')->get_user($order['uid']);
            $href = C('config.site_url').'/wap.php?g=Wap&c=Shop&a=status&order_id='.$order_id;
            $first = L_('退款失败！');
            if($reply_content){
                $first.=L_('失败原因：').$reply_content;
            }
            $totalMoney = floatval($refund['price']);//当前要退款的金额
            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
            $model->sendTempMsg('OPENTM405627933', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' => $first ,  'keyword1' => $order['real_orderid'], 'keyword2' => $totalMoney.C('config.Currency_txt'),'keyword3'=>date('Y年m月d日 H:i'), 'remark' => '点击查看详情！'));

            if ($refund['is_user_apply']) {
                D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 63, 'note' => '商家拒绝用户退款申请'));
            }
            $this->returnCode(0, 'ok');
        }
    }
    /**
     * 快店订单的操作记录
     */
    public function shopOrderLogs()
    {
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        if ($order = D('Shop_order')->get_order_detail(array('order_id' => $order_id, 'store_id' => $this->store['store_id']))) {
            $storeName = D("Merchant_store")->field('`name`, `phone`')->where(array('store_id' => $this->store['store_id']))->find();
            $status = D('Shop_order_log')->field(true)->where(array('order_id' => $order_id))->order('id DESC')->select();
            $express = D('Express')->get_express_list(1);
            foreach ($status as &$row) {
                $row['dateline'] = date('Y-m-d H:i', $row['dateline']);
                $row['real_orderid'] = $order['real_orderid'];
                $row['is_pick_in_store'] = $order['is_pick_in_store'];
                $express_info = explode('/', $row['ext']);
                $row['express_name'] = $express_info[0]?$express[$express_info[0]]:$order['express_name'];
                $row['express_number'] = $express_info[1]?$express_info[1]:$order['express_number'];
            }
            $isShowMap = 1;
            if ($order['order_status'] > 1 && $order['order_status'] < 5) {
                $supply = D("Deliver_supply")->where(array('order_id' => $order_id))->find();
                if (empty($supply)) {
                    $isShowMap = 0;
                }
            }
            
            $arr = array();
            $arr['logList'] = $status;
            $arr['statusCount'] = count($status);
            $arr['isShowMap'] = $isShowMap;
            $arr['order'] = $order;
            $arr['storeName'] = $storeName['name'];
            $arr['storePhone'] = $storeName['phone'];
            $this->returnCode(0, $arr);
        } else {
            $this->returnCode(1, null, L_(L_('错误的订单信息！')));
        }
    }
    /**
     * 配送的配送路线
     */
    public function line()
    {
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        if ($order = M('Shop_order')->field(true)->where(array('order_id' => $order_id, 'store_id' => $this->store['store_id']))->find()) {
            if ($order['order_status'] > 1 && $order['order_status'] < 6) {
                $supply = D("Deliver_supply")->where(array('order_id' => $order_id))->find();
                if (empty($supply)) {
                    $this->returnCode(0, array('err_code' => true));
                }
                $start_time = $supply['start_time'];
                $where = array();
                $where['uid'] = $supply['uid'];
                $where['create_time'] = array('gt', $start_time);
                $lines = D("Deliver_user_location_log")->where($where)->order("`create_time` ASC")->select();
                $points = array();
                $points['from_site'] = array('lng' => $supply['from_lnt'], 'lat' => $supply['from_lat']);
                $points['aim_site'] = array('lng' => $supply['aim_lnt'], 'lat' => $supply['aim_lat']);
                
                if ($lines) {
                    $center = array_pop($lines);
                } else {
                    $center = array('lng' => $supply['from_lnt'], 'lat' => $supply['from_lat']);
                }
                
                $this->returnCode(0, array('err_code' => false, 'lines' => $lines, 'status' => $supply['status'], 'points' => $points, 'center' => $center));
            } else {
                $this->returnCode(0, array('err_code' => true));
            }
        }
        $this->returnCode(1, null, '错误的订单信息！');
    }
    
    /**
     * 快店的商城订单修改配送方式的订单详情
     */
    public function mallOrderDetail()
    {
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $order = D('Shop_order')->get_order_detail(array('order_id' => $order_id, 'store_id' => $this->store['store_id']));
        if (empty($order)) $this->returnCode(1, null, L_('订单信息错误！'));
        
        $store_shop = D('Merchant_store_shop')->field(true)->where(array('store_id' => $order['store_id']))->find();
        
        
        //计算配送距离
        $distance = 0;
        if (C('config.is_riding_distance')) {
            import('@.ORG.longlat');
            $longlat_class = new longlat();
            $distance = $longlat_class->getRidingDistance($this->store['lat'], $this->store['long'], $order['lat'], $order['lng']);
        }
        $distance || $distance = getDistance($this->store['lat'], $this->store['long'], $order['lat'], $order['lng']);
        
        //获取配送的优惠金额信息
        $delivery_fee_reduce = 0;
        $discounts = D('Shop_discount')->getDiscounts($this->store['mer_id'], $this->store['store_id']);
        
        if ($d_tmp = D('Shop_goods')->getReduce($discounts, 2, $order['price'])) {
            $delivery_fee_reduce = $d_tmp['reduce_money'];
        }
        $order['distance'] = $distance;
        
        //平台配送的相关设置信息的处理
        $distance = $distance / 1000;
        $deliverReturn = D('Deliver_set')->getDeliverInfo($store_shop, $order['price']);
        
        
        $pass_distance = $distance > $deliverReturn['basic_distance'] ? floatval($distance - $deliverReturn['basic_distance']) : 0;
        $deliverReturn['delivery_fee'] += round($pass_distance * $deliverReturn['per_km_price'], 2);
        $deliverReturn['delivery_fee'] = $deliverReturn['delivery_fee'] - $delivery_fee_reduce;
        $deliverReturn['delivery_fee'] = $deliverReturn['delivery_fee'] > 0 ? $deliverReturn['delivery_fee'] : 0;
        $delivery_fee = $deliverReturn['delivery_fee'];
        $data = array();
        $data['time_select_1'] = substr($deliverReturn['delivertime_start'], 0, -3) . '-' . substr($deliverReturn['delivertime_stop'], 0, -3);
        $delivery_fee2 = 0;
        $delivery_fee3 = 0;
        if (!($deliverReturn['delivertime_start2'] == $deliverReturn['delivertime_stop2'] && $deliverReturn['delivertime_start2'] == '00:00:00')) {
            $pass_distance = $distance > $deliverReturn['basic_distance2'] ? floatval($distance - $deliverReturn['basic_distance2']) : 0;
            $deliverReturn['delivery_fee2'] += round($pass_distance * $deliverReturn['per_km_price2'], 2);
            $deliverReturn['delivery_fee2'] = $deliverReturn['delivery_fee2'] - $delivery_fee_reduce;
            $deliverReturn['delivery_fee2'] = $deliverReturn['delivery_fee2'] > 0 ? $deliverReturn['delivery_fee2'] : 0;
            $delivery_fee2 = $deliverReturn['delivery_fee2'];
            $data['time_select_2'] = substr($deliverReturn['delivertime_start2'], 0, -3) . '-' . substr($deliverReturn['delivertime_stop2'], 0, -3);
        }
        
        if (!($deliverReturn['delivertime_start3'] == $deliverReturn['delivertime_stop3'] && $deliverReturn['delivertime_start3'] == '00:00:00')) {
            $pass_distance = $distance > $deliverReturn['basic_distance3'] ? floatval($distance - $deliverReturn['basic_distance3']) : 0;
            $deliverReturn['delivery_fee3'] += round($pass_distance * $deliverReturn['per_km_price3'], 2);
            $deliverReturn['delivery_fee3'] = $deliverReturn['delivery_fee3'] - $delivery_fee_reduce;
            $deliverReturn['delivery_fee3'] = $deliverReturn['delivery_fee3'] > 0 ? $deliverReturn['delivery_fee3'] : 0;
            $delivery_fee3 = $deliverReturn['delivery_fee3'];
            $data['time_select_3'] = substr($deliverReturn['delivertime_start3'], 0, -3) . '-' . substr($deliverReturn['delivertime_stop3'], 0, -3);
        }
        
        switch (intval(C('config.count_freight_charge_method'))) {
            case 0:
                break;
            case 1:
                $delivery_fee = ceil($delivery_fee * 10) / 10;
                $delivery_fee2 = ceil($delivery_fee2 * 10) / 10;
                $delivery_fee3 = ceil($delivery_fee3 * 10) / 10;
                break;
            case 2:
                $delivery_fee = floor($delivery_fee * 10) / 10;
                $delivery_fee2 = floor($delivery_fee2 * 10) / 10;
                $delivery_fee3 = floor($delivery_fee3 * 10) / 10;
                break;
            case 3:
                $delivery_fee = round($delivery_fee * 10) / 10;
                $delivery_fee2 = round($delivery_fee2 * 10) / 10;
                $delivery_fee3 = round($delivery_fee3 * 10) / 10;
                break;
            case 4:
                $delivery_fee = ceil($delivery_fee);
                $delivery_fee2 = ceil($delivery_fee2);
                $delivery_fee3 = ceil($delivery_fee3);
                break;
            case 5:
                $delivery_fee = floor($delivery_fee);
                $delivery_fee2 = floor($delivery_fee2);
                $delivery_fee3 = floor($delivery_fee3);
                break;
            case 6:
                $delivery_fee = round($delivery_fee);
                $delivery_fee2 = round($delivery_fee2);
                $delivery_fee3 = round($delivery_fee3);
                break;
        }

        
        $diffTime = 60;
        if ($store_shop['send_time_type'] == 1) {
            $diffTime = 3600;
        } elseif ($store_shop['send_time_type'] == 2) {
            $diffTime = 86400;
        } elseif ($store_shop['send_time_type'] == 3) {
            $diffTime = 86400 * 7;
        } elseif ($store_shop['send_time_type'] == 4) {
            $diffTime = 86400 * 30;
        }
        $time = time() + $store_shop['work_time'] * $diffTime;
        
        
        $data['delivery_fee'] = getFormatNumber($delivery_fee);
        $data['delivery_fee2'] = getFormatNumber($delivery_fee2);
        $data['delivery_fee3'] = getFormatNumber($delivery_fee3);
        $data['freight_charge'] = getFormatNumber($order['freight_charge']);
        $data['deliver_type'] = $store_shop['deliver_type'];
        $data['deliverName'] = $this->config['deliver_name'];
        $data['distance'] = getFormatNumber($distance);
        $data['address'] = $order['address'];
        
        $data['arrive_datetime'] = date('Y-m-d H:i', $time);
        $data['order_id'] = $order_id;
        $this->returnCode(0, $data);
    }
    
    
    /**
     * 商城订单更改配送方式  将快递 更改成 其他配送方式
     */
    public function checkDeliver()
    {
        $database = D('Shop_order');
        $order_id = $condition['order_id'] = intval($_POST['order_id']);
        $expect_use_time = isset($_POST['expect_use_time']) ? strtotime(htmlspecialchars($_POST['expect_use_time'])) : 0;
        $condition['store_id'] = $this->store['store_id'];
        $order = $database->field(true)->where($condition)->find();
        if(empty($order)){
            $this->returnCode(1, null, L_('订单不存在！'));
        }
    
        if ($order['is_refund']) {
            $this->returnCode(1, null, L_('用户正在退款中~！'));
        }
        if ($order['paid'] == 0) {
            $this->returnCode(1, null, L_('订单未支付，不能接单！'));
        }
        if ($order['status'] > 0) {
            $this->returnCode(1, null, L_('该订单已处理，不能更改！'));
        }
    
        if ($order['is_pick_in_store'] != 3) {
            $this->returnCode(1, null, L_('不是快递配送，不能修改配送方式！'));
        }
        $d_shop = D('Merchant_store_shop')->field(true)->where(array('store_id' => $this->store['store_id']))->find();
    
        if (in_array($d_shop['deliver_type'], array(2, 5))) {
            $this->returnCode(1, null, L_('店铺不支持快递以外的配送，不能修改配送方式！'));
        }
        $is_pick_in_store = $d_shop['deliver_type'] == 0 || $d_shop['deliver_type'] == 3 ? 0 : 1;
    
    
        $deliverReturn = D('Deliver_set')->getDeliverInfo($d_shop, $order['goods_price']);
        
        
        $start_time = $deliverReturn['delivertime_start'];
        $stop_time = $deliverReturn['delivertime_stop'];
        
        $start_time2 = $deliverReturn['delivertime_start2'];
        $stop_time2 = $deliverReturn['delivertime_stop2'];
        
        $start_time3 = $deliverReturn['delivertime_start3'];
        $stop_time3 = $deliverReturn['delivertime_stop3'];
        
        $distance = getDistance($order['lat'], $order['lng'], $this->store['lat'], $this->store['long']);
        $distance = $distance / 1000;
        
        
        $delivery_fee_reduce = 0;
        $discounts = D('Shop_discount')->getDiscounts($this->store['mer_id'], $this->store['store_id']);
        if ($d_tmp = D('Shop_goods')->getReduce($discounts, 2, $order['goods_price'])) {
            $delivery_fee_reduce = $d_tmp['reduce_money'];
        }
        
        $expect_use_time_temp = $expect_use_time ? $expect_use_time : (time() + $d_shop['send_time'] * 60);//默认的期望送达时间
        
        if (D('Deliver_set')->getDeliverTime($expect_use_time_temp, $start_time, $stop_time, 1)) {
            //时间段一
            $pass_distance = $distance > $deliverReturn['basic_distance'] ? floatval($distance - $deliverReturn['basic_distance']) : 0;
            $deliverReturn['delivery_fee'] += round($pass_distance * $deliverReturn['per_km_price'], 2);
            $deliverReturn['delivery_fee'] = $deliverReturn['delivery_fee'] - $delivery_fee_reduce;
            $deliverReturn['delivery_fee'] = $deliverReturn['delivery_fee'] > 0 ? $deliverReturn['delivery_fee'] : 0;
            $delivery_fee = $order_data['freight_charge'] = $deliverReturn['delivery_fee'];//运费
        } elseif (D('Deliver_set')->getDeliverTime($expect_use_time_temp, $start_time2, $stop_time2, 2)) {
            //时间段二
            $pass_distance = $distance > $deliverReturn['basic_distance2'] ? floatval($distance - $deliverReturn['basic_distance2']) : 0;
            $deliverReturn['delivery_fee2'] += round($pass_distance * $deliverReturn['per_km_price2'], 2);
            $deliverReturn['delivery_fee2'] = $deliverReturn['delivery_fee2'] - $delivery_fee_reduce;
            $deliverReturn['delivery_fee2'] = $deliverReturn['delivery_fee2'] > 0 ? $deliverReturn['delivery_fee2'] : 0;
            $delivery_fee = $order_data['freight_charge'] = $deliverReturn['delivery_fee2'];//运费
        } elseif (D('Deliver_set')->getDeliverTime($expect_use_time_temp, $start_time3, $stop_time3, 3)) {
            //时间段三
            $pass_distance = $distance > $deliverReturn['basic_distance3'] ? floatval($distance - $deliverReturn['basic_distance3']) : 0;
            $deliverReturn['delivery_fee3'] += round($pass_distance * $deliverReturn['per_km_price3'], 2);
            $deliverReturn['delivery_fee3'] = $deliverReturn['delivery_fee3'] - $delivery_fee_reduce;
            $deliverReturn['delivery_fee3'] = $deliverReturn['delivery_fee3'] > 0 ? $deliverReturn['delivery_fee3'] : 0;
            $delivery_fee = $order_data['freight_charge'] = $deliverReturn['delivery_fee3'];//运费
        } else {
            $this->returnCode(1, null, L_('您选择的时间不在配送时间段内！'));
        }

        if ($expect_use_time_temp >= 0 && $expect_use_time_temp < time()) {
            fdump($_POST, 'Storestaff_checkDeliver', true);
            $this->returnCode(1, null, L_('预计送达时间不能小于当前'));
        }
        $data['status'] = 1;
        $condition['status'] = 0;
        $data['order_status'] = 1;
        $data['is_pick_in_store'] = $is_pick_in_store;
        $data['expect_use_time'] = $expect_use_time_temp;
        $data['last_staff'] = $this->staff_session['name'];
        if ($d_shop['deliver_type'] == 0 || $d_shop['deliver_type'] == 3) {
            $data['no_bill_money'] = $delivery_fee;
        }
        $data['distance']=$distance*1000;
        if ($database->where($condition)->save($data)) {
            $result = D('Deliver_supply')->saveOrder($order_id, $this->store);
            if ($result['error_code']) {
                D('Shop_order')->where(array('order_id' => $order_id))->save(array('status' => 0, 'order_status' => 0, 'last_time' => time()));
                $this->returnCode(1, null, $result['msg']);
                exit;
            }
            $phones = explode(' ', $this->store['phone']);
            D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 2, 'name' => $this->staff_session['name'], 'phone' => $phones[0]));
            $this->returnCode(0, L_('已接单'));
        } else {
            $this->returnCode(1, null, L_('接单失败'));
        }
    }
    
    /**
     * 快店的快递配送时填写快递单号
     */
    public function getExpress()
    {
        $order_id = intval($_POST['order_id']);
        $order = M('Shop_order')->field(true)->where(array('store_id' => $this->store['store_id'], 'order_id' => $order_id))->find();
        if (empty($order)) $this->returnCode(1, null, L_('订单信息错误'));
        //称重商品必须录入重量后才能接单
        if ($order['is_weight'] == 1) {
            $real_weight_not_filled_goods = M('Shop_order_detail')->where(['sell_type' => 2, 'real_weight' => 0, 'order_id' => $order['order_id']])->field('id')->find();
            if ($real_weight_not_filled_goods) {
                $this->returnCode(500005, null, L_('该订单含称重商品，请录入重量后接单！'));
            }
        }

        $express_list = D('Express')->get_express_list();
        $data = array('express_id' => $order['express_id'], 'express_number' => empty($order['express_number']) ? '' : $order['express_number']);
        $data['express_list'] = $express_list;
        $distance = getDistance($order['lat'], $order['lng'], $this->store['lat'], $this->store['long']);
        $data['distance'] = floatval(round($distance / 1000, 2));
        $data['address'] = $order['address'];
        $data['order_id'] = $order['order_id'];
        $this->returnCode(0, $data);
    }
    
    
    /**
     * 打包APP修改快店订单状态的接口
     */
    public function shopOrderEdit()
    {
        if (IS_POST) {
            $nowTime = time();
            $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
            $store_id = $this->store['store_id'];
            $status = intval($_POST['status']);
            $phones = explode(' ', $this->store['phone']);

            // 开启店员权限功能 店小二没权限 有接单权限
            if (C('config.open_staff_power')&&$this->staff_session['type'] == 0 && $status != 1) {
                $this->returnCode(1,null,L_('您当前身份为【店小二】，没有权限，请联系管理员！'));
            }

            if ($order = D("Shop_order")->where(array('mer_id' => $this->store['mer_id'], 'order_id' => $order_id))->find()) {
                //验证是否有提交权限
                if($order['is_pick_in_store']==2&&!$this->checkPickRule($order['pick_id'])){
                    $this->returnCode(1,'','没有分配当前自提点权限!');
                }
                if($order['is_pick_in_store']==3 && $status == 1 && $order['status'] > 0){

                }else{
                    if ($status == 1 && $order['status'] > 0) {
                        $this->returnCode(1, null, L_('该单已接，不要重复接单'));
                    }
                }
                if ($status == 5 && $order['status'] == 2) {
                    $this->returnCode(1, null, L_('该单已确认消费，不能退款'));
                }
                if ($order['status'] == 4 || $order['status'] == 5) {
                    $this->returnCode(1, null, L_('订单已取消，不能再做其他操作。'));
                }
                if ($order['is_refund']) {
                    $this->returnCode(1, null, L_('用户正在退款中~！'));
                }
                if ($order['paid'] == 0 && $status != 5) {
                    $this->returnCode(1, null, L_('未付款的订单只能进行取消操作。'));
                }
                //0未确认，1已确认，2已消费，3已评价，4已退款，5已取消，
                if ($status == 3) {
                    $this->returnCode(1, null, L_('您不能将该订单改成已评价状态。'));
                }
                //称重商品必须录入重量后才能接单
                if ($order['is_weight'] == 1) {
                    $real_weight_not_filled_goods = M('Shop_order_detail')->where(['sell_type' => 2, 'real_weight' => 0, 'order_id' => $order['order_id']])->field('id')->find();
                    if ($real_weight_not_filled_goods) {
                        $this->returnCode(500005, null, L_('该订单含称重商品，请录入重量后接单！'));
                    }
                }

                //赠送满减送优惠卷
                if($status == 2){
                    $order_detail = M('Shop_order_detail')->where(array('order_id'=>$order_id))->select();
                    if($order_detail){
                        foreach($order_detail as $k=>$v){
                            if($v['give_coupon_id']){
                                $data['coupon_id'] = $v['give_coupon_id'];
                                $data['uid'] = $order['uid'];
                                $data['num'] = 1;
                                $data['receive_time'] = time();
                                $data['is_use'] = 0;
                                M('Card_new_coupon_hadpull')->data($data)->add();
                            }
                        }
                    }
                }
                if (empty($order['third_id']) && $order['pay_type'] == 'offline') {
                    $order['paid'] = 0;
                }
                if ($order['paid'] == 0) {
                    $notOffline = 1;
                    if ($this->config['pay_offline_open'] == 1) {
                        $now_merchant = D('Merchant')->get_info($order['mer_id']);
                        if ($now_merchant) {
                            $notOffline =($now_merchant['is_close_offline'] == 0 && $now_merchant['is_offline'] == 1) ? 0 : 1;
                        }
                    }
                    if ($notOffline && $status != 5) {
                        //$this->returnCode(1, null, '店铺不支持线下支付。');
                    }
                }
                $data = array('store_uid' => $this->staff_session['id']);
                $data['status'] = $status;
                $data['cancel_type'] = 4;//取消类型（0:pc店员，1:wap店员，2:andriod店员,3:ios店员，4：打包app店员，5：用户，6：配送员, 7:超时取消）
                $data['last_staff'] = $this->staff_session['name'];
				if($status==1){
					$data['is_taking'] = 1;//店员已接单
				}
                $condition = array('mer_id' => $order['mer_id'], 'order_id' => $order_id, 'store_id' => $store_id);
                $database = D('Shop_order');
                $database->startTrans();
                if ($order['platform'] == 1) {//eleme
                    $eleme = D('Eleme_shop')->getElemeObj($order['store_id']);
                    if ($eleme != false) {
                        if ($status == 1) {
                            $result = $eleme->getDataByApi("eleme.order.confirmOrderLite", array('orderId' => $order['platform_id']));
                        } elseif ($status == 2 || $status == 3) {
                            $result = $eleme->getDataByApi("eleme.order.receivedOrderLite", array('orderId' => $order['platform_id']));
                        } elseif ($status == 4) {
                            $result = $eleme->getDataByApi("eleme.order.agreeRefundLite", array('orderId' => $order['platform_id']));
                        } elseif ($status == 5) {
                            $result = $eleme->getDataByApi("eleme.order.cancelOrderLite", array('orderId' => $order['platform_id'], 'type' => 'fakeOrder', 'remark' => L_('无法取得联系')));
                        }
						if ($result['error']){
							$result['ownStore'] = $this->store;
							fdump_sql($result,'elem_storestaff_getorder_result');
                        }
                        if ($result['error'] && $result['error']['message'] != L_('操作失败，订单已确认')) {
                            $this->returnCode(1, null, L_('饿了么返回的错误') . $result['error']['code'] . $result['error']['message']);
                            exit;
                        } else {
                            $data['last_time'] = time();
                            $database->where($condition)->save($data);
                            
                            if ($status == 1 && $order['is_pick_in_store'] == 5) {
                                $result = D('Deliver_supply')->saveOrder($order_id, $this->store);
                                if ($result['error_code']) {
                                    D("Shop_order")->where(array('mer_id' => $this->store['mer_id'], 'order_id' => $order_id, 'store_id' => $order['store_id']))->save(array('status' => 0, 'last_time' => time()));
                                    $this->error($result['msg']);
                                    exit;
                                }
                                D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 2, 'name' => $this->staff_session['name'], 'phone' => $phones[0]));
                            } elseif ($status == 0 || $status == 5 || $status == 4) {
                                D('Deliver_supply')->where(array('order_id' => $order_id, 'item' => 2))->save(array('status' => 0));
                            }
                            $database->commit();
                            $this->returnCode(0, L_("更新成功"));
                            exit;
                        }
                    } else {
                        $database->rollback();
                        $this->returnCode(1, null, L_('店铺对接饿了么有问题，请联系商家处理！'));
                        exit;
                    }
                } elseif ($order['platform'] == 2) {//meituan
                    $store = M('Merchant_store')->field(true)->where(array('store_id' => $order['store_id']))->find();
                    if (empty($store) || empty($store['meituan_token'])) {
                        $this->returnCode(1, null, L_('店铺对接美团有问题，请联系商家处理！'));
                        exit;
                    }
                    import('@.ORG.Meituan');
                    $meituan = new Meituan($store['meituan_token']);
                    if ($status == 1) {
                        $result = $meituan->getDataByApi('waimai/order/confirm', array('orderId' => $order['platform_id']));
                    } elseif ($status == 2 || $status == 3) {
                        $this->returnCode(1, null, L_('美团不支持确认消费'));
                        exit;
                    } elseif ($status == 4) {
                        $result = $meituan->getDataByApi('waimai/order/agreeRefund', array('orderId' => $order['platform_id'], 'reason' => L_('同意退款')));
                    } elseif ($status == 5) {
                        $result = $meituan->getDataByApi('waimai/order/cancel', array('orderId' => $order['platform_id'], 'reasonCode' => 2001, 'reason' => L_('地址无法配送')));
                    }
                   

                    if ($result['error']) {
                        $database->rollback();
                        $this->returnCode(1, null, L_('美团返回的错误：') . $result['msg']);
                        exit;
                    } else {
                        $data['last_time'] = time();
                        $database->where($condition)->save($data);
                        
                        if ($status == 1 && $order['is_pick_in_store'] == 5) {
                            $result = D('Deliver_supply')->saveOrder($order_id, $this->store);
                            if ($result['error_code']) {
                                D("Shop_order")->where(array('mer_id' => $this->store['mer_id'], 'order_id' => $order_id, 'store_id' => $order['store_id']))->save(array('status' => 0, 'last_time' => time()));
                                $this->error($result['msg']);
                                exit;
                            }
                            $log_data= [
                                'order_id' => $order_id,
                                'status' => 2,
                                'name' => $this->staff_session['name'],
                                'phone' => $phones[0]
                            ];
                            if(M('Shop_order_log')->where(['order_id' => $order_id, 'status' => 2])->count()>1){//如果之前已存在发货记录后面的状态均为修改
                                $log_data['note'] = 'update';//修改操作
                            }
                            D('Shop_order_log')->add_log($log_data);
                        } elseif ($status == 0 || $status == 5 || $status == 4) {
                            D('Deliver_supply')->where(array('order_id' => $order_id, 'item' => 2))->save(array('status' => 0));
                        }
                        $database->commit();
                        $this->returnCode(0, L_("更新成功"));
                        exit;
                    }
                }
                // is_pick_in_store  0：平台配送，1：商家配送，2：自提 3：快递配送（商城）
                if ($order['is_pick_in_store'] == 3) {
                    $data['status'] = $status;
                    $express_id = isset($_POST['express_id']) ? intval($_POST['express_id']) : 0;
                    $express_number = isset($_POST['express_number']) ? htmlspecialchars($_POST['express_number']) : 0;

                    //没有填写快递单号，则是接单操作
                    $isTake = false;
                    if ($status == 1 && (empty($express_id) || empty($express_number))) {
                        $isTake = true;
                        $data['order_status'] = 1;
                        unset($data['status']);
                    }


                    if ($status == 2 && (empty($express_id) || empty($express_number))) $this->returnCode(1, null, L_('快递公司和快递单号都不能为空。'));
                    $data['last_staff'] = $this->staff_session['name'];
                    $data['express_id'] = $express_id;
                    $data['express_number'] = $express_number;
                    $data['use_time'] = $nowTime;
                    $data['last_time'] = $nowTime;

                    if($order['periodic_type']){//周期购
                        $p_data = array();
                        $p_data['express_id']     = $express_id;
                        $p_data['express_number'] = $express_number;
                        $p_data['status'] = 1;
                        $p_data['update_time'] = $_SERVER['REQUEST_TIME'];
                        $p_data['accept_time'] = $_SERVER['REQUEST_TIME'];
                        $p_data['deliver_uid'] = $this->staff_session['id'];

                        $p_where = array();
                        $p_where['id'] = intval($_POST['periodic_deliver_id']);
                        if(M('Mall_periodic_deliver')->where($p_where)->data($p_data)->save()){
                            unset($data['status']);
                        }
                    }

                    if (D("Shop_order")->where(array('mer_id' => $this->store['mer_id'], 'order_id' => $order_id, 'store_id' => $store_id))->save($data)) {
                        if ($status == 1) {

                            $log_data = [
                                'order_id' => $order_id,
                                'status' => 2,
                                'name' => $this->staff_session['name'],
                                'phone' => $phones[0],
                                'ext'=> $express_id.'/'.$express_number,
                            ];
                            if($isTake){
                                $log_data['note'] = L_('订单已确认，商家备货中');
                            }
                            //dump($log_data);exit;
                            if(M('Shop_order_log')->where(['order_id' => $order_id, 'status' => 2])->count()>1){//如果之前已存在发货记录后面的状态均为修改
                                $log_data['note'] = 'update';//修改操作
                            }
                            D('Shop_order_log')->add_log($log_data,$order['is_pick_in_store']);
                            D("System_order")->save_order_status($order['order_from'] == '1' ? 'mall' : 'shop',$order_id,1);

                            //接单后打印标签
                            $orderDetail = D('Shop_order')->get_order_detail(array('order_id' => $order_id), true);
                            D('Label_printer')->shopOrderLabel($orderDetail);

                            //-----------------------京东确认订单start 2018-09-03--------------------------------------
                            if($order['jd_order_id']>0 && $order['jd_paid']==0){
                                $url_confirm = "https://bizapi.jd.com/api/order/confirmOrder";
                                $confirm_data['token'] = C('config.jd_access_token');
                                $confirm_data['jdOrderId'] = $order['jd_order_id'];
                                import('ORG.Net.Http');
                                $confirm_return = Http::curlPost($url_confirm,$confirm_data);
                                if($confirm_return['success']==false){
                                    fdump_sql(json_encode($confirm_return),'jd_confirm');
                                    //失败后再发起一次请求
                                    $confirm_return2 = Http::curlPost($url_confirm,$confirm_data);
                                    if($confirm_return2['success']==false){
                                        fdump_sql(json_encode($confirm_return2),'jd_confirm2');
                                        $this->returnCode(1, null, L_("京东支付失败，请联系京东运营！订单已确认"));
                                    }else{
                                        D("Shop_order")->where(array('mer_id' => $this->store['mer_id'], 'order_id' => $order_id, 'store_id' => $store_id))->save(array('jd_paid'=>1));
                                    }
                                }else{
                                    D("Shop_order")->where(array('mer_id' => $this->store['mer_id'], 'order_id' => $order_id, 'store_id' => $store_id))->save(array('jd_paid'=>1));
                                }
                            }
                            //-----------------------京东确认订单end 2018-09-03--------------------------------------
                        } elseif ($status == 2 && $order['status'] != 2 && $order['status'] != 3) {
                            $this->shop_notice($order);
                            D('Shop_order_log')->add_log(array('log_from' => 'storestaff', 'order_id' => $order_id, 'status' => 7, 'name' => $this->staff_session['name'], 'phone' => $phones[0]));
                        } elseif ($status == 4) {
                            D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 9, 'name' => $this->staff_session['name'], 'phone' => $phones[0]));
                            D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 9, 'name' => $this->staff_session['name'], 'phone' => $phones[0]));
                            D('Shop_order')->check_refund($order);


                        } elseif ($status == 5) {
                            D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 10, 'name' => $this->staff_session['name'], 'phone' => $phones[0],'note' => L_('店员把此单修改成已取消')));
                            D('System_order')->save_order_status($order['order_from'] == '1' ? 'mall' : 'shop', $order_id, 5);
                            D('Shop_order')->check_refund($order,$this->staff_session);
                        }
                        $database->commit();
                        $this->returnCode(0, L_("更新成功"));
                    } else {
                        $database->rollback();
                        $this->returnCode(1, null, L_("更新失败，稍后重试"));
                    }
                } else {
                    $supply = D('Deliver_supply')->field(true)->where(array('order_id' => $order_id, 'item' => 2))->find();
    
                    if ($order['is_pick_in_store'] == 0 && $status == 2 && $supply && $supply['uid']) {//平台配送，当配送员接单后店员就不能把订单修改成已消费状态
                        $this->returnCode(1, null, L_('您不能将该订单改成已消费状态。'));
                    }
                    if ($status == 0 && $order['status'] == 1 && $order['is_pick_in_store'] < 2) {
                        if ($supply && $supply['status'] > 1) {
                            $this->returnCode(1, null, L_('当前订单已进入了配送状态，不能修改成未确认状态。'));
                        }
                    }
                    
                    if ($status == 2 && $order['paid'] == 0) {//将未支付的订单，由店员改成已消费，其订单状态则修改成线下已支付！
                        $data['third_id'] = $order['order_id'];
                        $order['pay_type'] = $data['pay_type'] = 'offline';
                        $data['paid'] = 1;
                        $order['pay_time'] = $nowTime;
                    }

                    if ($status == 2) {
                        $data['order_status'] = 6;//配送完成
                        $supply = D('Deliver_supply')->field(true)->where(array('order_id' => $order_id, 'item' => 2))->find();
                        if ($supply) {
                            if ($supply['status'] < 2) {
                                D('Deliver_supply')->where(array('order_id' => $order_id, 'item' => 2))->delete();
                            } else {
                                D('Deliver_supply')->where(array('order_id' => $order_id, 'item' => 2))->save(array('status' => 5));
                            }
                        }
                        $data['use_time'] = $nowTime;
                        //检查含有称重商品的订单，是否需要部分退款
                        if($this->config['is_open_shop_weigh']==1 && $order['is_weight']==1){
                            D('Shop_order')->checkWeightOrder($order);
                        }

                        /*$order_detail1 = M('Shop_order_detail')->where(array('order_id'=>$order_id))->select();
                        if($order_detail1) {
                            foreach ($order_detail1 as $k => $v) {
                                $goods = M('Shop_goods')->where(array('goods_id'=>$v['goods_id']))->find();
                                if($goods['score_percent_type']==1 && $goods['score_percent']>0 && $order['order_from']==1){
                                    D('User')->add_score($order['uid'], floatval($order['price']*$goods['score_percent']),L_("店员验证消费, 顾客消费X2 获得X3",array("X2" => C('config.Currency_symbol') . floatval($order['price']), "X3" => floatval($order['price']*$goods['score_percent']))),0);
                                }
                            }
                        }*/

                    } elseif ($status == 1) {
                        $data['order_status'] = 1;
                    }
                    $data['last_time'] = $nowTime;
					
					if($order['periodic_type']){//周期购
                        $p_data = array();
                        $p_data['status'] = 2;
                        $p_data['update_time'] = $_SERVER['REQUEST_TIME'];
                        $p_data['accept_time'] = $_SERVER['REQUEST_TIME'];
                        $p_data['arrive_time'] = $_SERVER['REQUEST_TIME'];
                        $p_data['deliver_uid'] = $this->staff_session['id'];

                        $p_where = array();
                        $p_where['id'] = intval($_POST['periodic_deliver_id']);
                        if(M('Mall_periodic_deliver')->where($p_where)->data($p_data)->save()){
                            unset($data['status']);
                        }
                    }
					
                    if (D("Shop_order")->where(array('mer_id' => $this->store['mer_id'], 'order_id' => $order_id, 'store_id' => $store_id))->save($data)) {
                        if (($status == 0 || $status == 5 || $status == 4) && $order['is_pick_in_store'] < 2) {//is_pick_in_store : 配送方式 0：平台配送，1：商家配送，2：自提，3:快递配送
                            D('Deliver_supply')->where(array('order_id' => $order_id, 'item' => 2))->save(array('status' => 0,'deliver_status'=>0));
                        }
                        if ($status == 5 || $status == 4) {
                            if ($this->store['dada_shop_id']) {
                                $dada = new Dada();
                                $dadaData = array();
                                $dadaData['order_id'] = $order['real_orderid'];//
                                $dadaData['cancel_reason_id'] = 4;
                                $dadaData['cancel_reason'] = L_(L_('顾客取消订单'));
                                $result = $dada->formalCancel($dadaData);
                            }
                            if($order['keloop_trade_no']!=''){
                                $keloop = new Keloop();
                                $Data = array();
                                $Data['trade_no'] = $order['keloop_trade_no'];
                                $Data['reason'] = '顾客取消订单';
                                $result = $keloop->cancelOrder($Data);
                                if($result['code']!=200){
                                    $this->returnCode(1, null, $result['message']);
                                }
                            }
			                //顺丰配送取消订单
                            if ($order['sf_order_id'] != '') {
                                $merchantStore = D('Merchant_store')->field('shunfeng_is_open,shunfeng_dev_id,shunfeng_dev_key,shunfeng_shop_id')->where(['store_id' => $order['store_id'], 'shunfeng_is_open' => 1])->find();
                                $sfData = [
                                    'order_id' => $order['sf_order_id'],
                                    'order_type' => 1,
                                    'push_time' => time()
                                ];
                                $result = (new Shunfeng($merchantStore))->cancelorder($sfData);
                                if ($result['error_code'] != 0) {
                                    $this->returnCode(1, null, $result['message']);
                                }
                            }

                            //饿了么蜂鸟配送取消订单
                            if ($order['is_ele_delivery'] == 1) {
                                $merchantStore = D('Merchant_store')->field('ele_is_open,ele_app_id,ele_secret_key,ele_transport_name,ele_chain_store_code')->where(['store_id' => $order['store_id'], 'ele_is_open' => 1])->find();
                                $ele = new EleDeliveryNew();
                                try {
                                    $ele->setBusinessData('partner_order_code', $order['real_orderid']);
                                    //获取可取消原因
                                    $reasonList = $ele->getCancelReasonList();
                                    //预取消订单
                                    $ele->setBusinessData('order_cancel_code', $reasonList[0]['order_cancel_code'] ?? 0);
                                    $ele->preCancelOrder();
                                    //正式取消
                                    $ele->setBusinessData('order_cancel_role', 1);
                                    $ele->setBusinessData('order_cancel_other_reason', '店员取消订单');
                                    $ele->cancelOrder();
                                } catch (\Exception $e) {
                                    $this->returnCode(1, null, $e->getMessage());
                                }
                            }
                            if($order['dingding_trade_no']!=''){
                                //处理叮叮取消订单======>郭先森
                                $merchantStore = D('Merchant_store')->where(array('store_id'=>$order['store_id'],'is_open_dingding'=>1))->find();
                                import('@.ORG.Dingding');
                                $getShopInfo = new Dingding();
                                $postData = array(
                                    'headquarters_id'=>1,
                                    'username'=>$merchantStore['dingding_user_name'],
                                    'per_userid'=>$merchantStore['dingding_per_userid'],
                                );
                                $res = $getShopInfo->getShopInfo($postData);
                                $res = json_decode($res,true);
                                fdump("=====================================================","dingdingorderCancel",true);
                                fdump($res,"dingdingorderCancel",true);
                                if($res['code'] == 0){
                                    return array('error_code' => 1, 'msg' => L_('店鋪信息不存在,请联系开发者！'));
                                }
                                $dingDing = new Dingding(C('config.dingding_fromplatform'),C('config.dingding_form_app_key'),C('config.dingding_appid'),$res['result']['code'],$merchantStore['dingding_per_userid'],$merchantStore['dingding_user_name'],$merchantStore['dingding_pass'],$res['result']['phone']);
                                $postData = array(
                                    'origin_id'=>$order['real_orderid'],
                                    'cancel_id'=>10000,
                                    'cancelReason'=>'其他',
                                    'timestamp'     => time(),
                                );

                                $res = $dingDing->orderCancel($postData);

                                $res = json_decode($res,true);
                                fdump($res,"dingdingorderCancel",true);
                            }
                            D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 10, 'name' => $this->staff_session['name'], 'phone' => $phones[0],'note' => L_('店员把此单修改成已取消')));
                            D('Shop_order')->check_refund($order,$this->staff_session);
                            D('System_order')->save_order_status($order['order_from'] == '1' ? 'mall' : 'shop', $order_id, 5);
                            // 配送员推送模板消息
                            $deliver_user = D('Deliver_user')->field(true)->where(array('uid' => $supply['uid'],'status'=>array('neq',4)))->find();
                            if($deliver_user['openid']){
                                $href = '';
                                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                                $model->sendTempMsg('TM00017', array(
                                    'href' => $href,
                                    'wecha_id' => $deliver_user['openid'],
                                    'first' => L_('用户取消X1订单！',C('config.shop_alias_name')),
                                    'OrderSn' => $order['real_orderid'],
                                    'OrderStatus' => L_('已取消'),
                                    'remark' => date('Y-m-d H:i:s')
                                ));
                            }
                            //销量回滚reduce_stock_type
                            if (($order['paid'] == 1 || $order['reduce_stock_type'] == 1) && $order['is_rollback'] == 0) {
                                $goods_obj = D('Shop_goods');
                                $details = D('Shop_order_detail')->field(true)->where(array('order_id' => $order_id))->select();
                                foreach ($details as $menu) {
                                    $goods_obj->update_stock($menu, 1);//修改库存
                                }
                                D('Shop_order')->where(array('order_id' => $order_id))->save(array('is_rollback' => 1));
                            }
                        }
                        if ($status == 2 && $order['status'] != 2 && $order['status'] != 3) {//当订单由未消费修改成已消费时做的通知
                            D('Pick_order')->where(array('store_id' => $order['store_id'], 'order_id' => $order['order_id']))->save(array('status' => 4));
                            D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 7, 'name' => $this->staff_session['name'], 'phone' => $phones[0]));
                            $this->shop_notice($order);
                        }
                        if ($status == 1 && $order['status'] == 0) {//接单
							if($order['is_pick_in_store'] != 2){
								$result = D('Deliver_supply')->saveOrder($order_id, $this->store);
								if ($result['error_code']) {
									D('Shop_order')->where(array('order_id' => $order_id))->save(array('status' => 0, 'order_status' => 0, 'last_time' => time()));
									$this->returnCode(1, null, $result['msg']);
									exit;
								}
                            }
                            D('System_order')->save_order_status($order['order_from'] == '1' ? 'mall' : 'shop', $order_id, 1);
                            D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 2, 'name' => $this->staff_session['name'], 'phone' => $phones[0]));

                            //接单后打印标签
                            $orderDetail = D('Shop_order')->get_order_detail(array('order_id' => $order_id), true);
                            D('Label_printer')->shopOrderLabel($orderDetail);
                        }
                        if ($status == 8) {
                            D('Pick_order')->where(array('store_id' => $order['store_id'], 'order_id' => $order['order_id']))->save(array('status' => 1));
                            D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 12, 'name' => $this->staff_session['name'], 'phone' => $phones[0]));
                        }
                        $database->commit();
                        $this->returnCode(0, L_("更新成功"));
                    } else {
                        $database->rollback();
                        $this->returnCode(1, null, L_("更新失败，稍后重试"));
                    }
                }
            } else {
                $this->returnCode(1, null, L_(L_('不合法的请求')));
            }
        }
    }

    /**
     * 检查快店是否有库存报警
     */
    public function checkShopGoodsStock()
    {
        if ($now_shop = D('Merchant_store_shop')->field(true)->where(array('store_id' => $this->store['store_id']))->find()) {
            $shop_type=I('shop_type',1,'intval');
            if ($result = D('Shop_goods')->check_stock_list($this->store['store_id'], $now_shop['stock_type'],10,$shop_type)) {
                $this->returnCode(0, 1);
            } else {
                $this->returnCode(0, 0);
            }
        } else {
            //$this->returnCode(1, null, '不合法的请求');
        }
    }
    /**
     * 快店商品库存报警列表
     */
    public function shopGoodsStock()
    {
        if ($now_shop = D('Merchant_store_shop')->field(true)->where(array('store_id' => $this->store['store_id']))->find()) {
            $shop_type=I('shop_type',1,'intval');
            $goods_list = D('Shop_goods')->check_stock_list($this->store['store_id'], $now_shop['stock_type'],10,$shop_type);
            $data = array('count' => count($goods_list), 'goods_list' => $goods_list);
            $this->returnCode(0, $data);
        } else {
            $this->returnCode(1, null, '不合法的请求');
        }
    }
    

    /**
     * 自提点列表
     */
    public function getPickAddress()
    {
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $order = D('Shop_order')->get_order_detail(array('order_id' => $order_id, 'store_id' => $this->store['store_id']));
        if(empty($order)){
            $this->returnCode(1, null, L_(L_('订单不存在！')));
        }
        $pick_addr = D('Pick_address')->get_pick_addr_by_merid($this->store['mer_id'], true);
        foreach ($pick_addr as &$v) {
            $v['range'] = getRange(getDistance($v['lat'], $v['long'], $order['lat'], $order['lng']));
        }
        $this->returnCode(0, array('order_id' => $order_id, 'pick_list' => $pick_addr));
    }
    
    /**
     * 保存分配到自提点
     */
    public function pick()
    {
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $order = D('Shop_order')->get_order_detail(array('order_id' => $order_id, 'store_id' => $this->store['store_id']));
        if(empty($order)){
            $this->returnCode(1, null, '订单不存在！');
        }
        $pick_order = D('Pick_order')->where(array('store_id' => $order['store_id'], 'order_id' => $order['order_id']))->find();
        
        if ($order['status'] == 4 || $order['status'] == 5) {
            $this->returnCode(1, null, L_('订单已取消，不能接单！'));
            exit;
        }
        if ($order['is_refund']) {
            $this->returnCode(1, null, L_('用户正在退款中~！'));
            exit;
        }
        if ($order['paid'] == 0) {
            $this->returnCode(1, null, L_('订单未支付，不能接单！'));
            exit;
        }
        $pick_id = isset($_POST['pick_id']) ? htmlspecialchars($_POST['pick_id']) : '';
        $pick_address = null;
        if ($pick_id) {
            $type = substr($pick_id, 0, 1);
            $type = $type == 's' ? 1 : 0;
            $pick_id = substr($pick_id, 1);
            $pick_address = D('Pick_address')->field(true)->where(array('id' => $pick_id, 'mer_id' => $this->store['mer_id']))->find();

        }
        if (empty($pick_address)) {
            $this->returnCode(1, null, L_('请选择自提点'));
        }
        
        if (empty($pick_order)) {
            D('Pick_order')->add(array('store_id' => $order['store_id'], 'order_id' => $order['order_id'], 'type' => $type, 'pick_id' => $pick_id, 'status' => 0, 'dateline' => time()));
            D('Shop_order')->where(array('order_id' => $order_id))->save(array('status' => 7, 'order_status' => 1, 'pick_id' => $pick_id));
            $phones = explode(' ', $this->store['phone']);
            D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 11, 'name' => $this->staff_session['name'], 'phone' => $phones[0]));//分配到自提点
            $this->returnCode(0, 'SUCCESS');
        } else {
            $this->returnCode(1, null, L_('不要重复分配'));
        }
    }
    //	快店列表
    public function shopList()
    {
    	$this->check_shop();
        // $pay_types = array('balance' => L_('余额支付'), 'alipay' => L_('支付宝'), 'tenpay' => L_('财付通'), 'yeepay' => L_('易宝支付'), 'allinpay' => L_('通联支付'), 'chinabank' => L_('网银在线'), 'weixin' => L_('微信支付'), 'baidu' => L_('百度钱包'), 'unionpay' => L_('银联支付'), 'offline' => L_('货到付款'));

        // 支付方式
        $pay_method = D('Config')->get_pay_method('','',1);
        foreach ($pay_method as $key => $value) {
           $pay_types[$key] = $value['name'];
        }
        $pay_types['offline'] && $pay_types['offline'] = L_('货到付款');

        $store_id = intval($this->store['store_id']);
        $where = array('mer_id' => $this->store['mer_id']);
        $where['store_id'] = $store_id;
        if($this->staff_session['pick_addr_ids']){
            $where['_string'] = "((`is_pick_in_store` <> 2) OR (`is_pick_in_store` = 2 AND FIND_IN_SET(`pick_id`,'".$this->staff_session['pick_addr_ids']."')))";
        }
        $status = isset($_POST['st']) ? intval(trim($_POST['st'])) : -1;//订单状态
        $ftype = isset($_POST['ft']) ? trim($_POST['ft']) : '';//搜索关键词类型
        $fvalue = isset($_POST['fv']) ? trim(htmlspecialchars($_POST['fv'])) : '';//搜索关键词
        $order_id = $_POST['order_id'];
        
        $pay_type = isset($_POST['pay_type']) ? htmlspecialchars(trim($_POST['pay_type'])) : -2;//支付类型
        $order_from = isset($_POST['order_from']) ? intval(trim($_POST['order_from'])) : -2;//订单来源
        
        $stime = isset($_POST['stime']) ? trim(htmlspecialchars($_POST['stime'])) : '';//搜索开始时间
        $etime = isset($_POST['etime']) ? trim(htmlspecialchars($_POST['etime'])) : '';//搜索结束时间

        $shop_type = isset($_POST['shop_type']) ? intval(trim($_POST['shop_type'])) : 1;//店铺类别 1-快店；2-商城
        if ($stime && $etime ) {
            if($status=='101'){//已付款未取餐状态下特殊处理
                /* $where['expect_pick_time'] = array(array('gt', strtotime($stime . ' 00:00:01')), array('lt', strtotime($etime . ' 23:59:59'))); */
            }else{
                //未支付、全部状态筛选使用下单时间
                if(in_array($status,[6,-1])){
                    $where['create_time'] = array(array('gt', strtotime($stime . ' 00:00:01')), array('lt', strtotime($etime . ' 23:59:59')));
                }else{
                    $where['pay_time'] = array(array('gt', strtotime($stime . ' 00:00:01')), array('lt', strtotime($etime . ' 23:59:59')));
                }
            }
        }
        
        if ($status != -1 && $status != -2) {
            if ($status == 2) {
                $where['status'] = array('in', '2, 3');
            } elseif ($status == 6) {
                $where['paid'] = 0;
            } elseif($status == 11){
                $where['status'] = array('in',array(2,3));
                $where['is_apply_refund'] = 1;
            } elseif($status==12){
                $where['status'] = array('in','0,1,9');
                $where['paid']=1;
            }elseif($status=='101'){//已付款未取餐状态下特殊处理
                //获取未取餐的订单id
                $where['order_status']=array('lt',3);
				$where['status']=array('lt',2);
                $where['paid']=1;
            }else{
                $where['status'] = $status;
            }
        }else if(!$this->staff_session['is_change'] && $status == -1){
			$where['paid']=1;
		}
        
        if ($pay_type != -2) {
            if ($pay_type == 'balance') {
                $where['paid'] = 1;
                $where['pay_type'] = '';
            } else {
                $where['pay_type'] = $pay_type;
            }
        }
        if($shop_type==2){
            $where['order_from']=1;

            //活动类型 act_type 0:所有订单；1：限时购订单；2：拼团订单；3：砍价订单
            $act_type=I('act_type',0,'intval');
            if($act_type==2){
                $where_str='group_id>0';
                $group_order_arr=D('Mall_group_act')->get_success_team($where['mer_id'],$where['store_id']);
                $group_order_str='';
                foreach ($group_order_arr as $k => $v) {
                    if($k==0){
                        $group_order_str=$v['order_id'];
                    }else{
                        $group_order_str.=','.$v['order_id'];   
                    }                   
                }
                $where_str.=' and order_id in('.$group_order_str.')';
            }elseif($act_type==3){
                $where_str='bar_id>0';
            }else{
                $where_str="group_id=0";
                $group_order_arr=D('Mall_group_act')->get_success_team($where['mer_id'],$where['store_id']);
                if($group_order_arr){
                    $group_order_str='';
                    foreach ($group_order_arr as $k => $v) {
                        if($k==0){
                            $group_order_str=$v['order_id'];
                        }else{
                            $group_order_str.=','.$v['order_id'];   
                        }
                    }
                    $where_str.=' or (group_id>0 and order_id in('.$group_order_str.'))';
                }
            }
            $where['_string'] = $where['_string']?($where['_string'].' and '.$where_str):$where_str;
        }else{
            if ($order_from != -2) {
                $where['order_from'] = $order_from;
            }else{
                $where['order_from']=array('neq',1);
            }   
        }
        
        switch ($ftype) {
            case 'oid': //订单id
                $fvalue && $where['real_orderid'] = array('like', "%$fvalue%");
                break;
            case 'xm':  //下单人姓名
                $fvalue && $where['username'] = array('like', "%$fvalue%");
                break;
            case 'dh':  //下单人电话
                $fvalue && $where['userphone'] = array('like', "%$fvalue%");
                break;
            case 'mps': //消费码
                $fvalue && $where['orderid'] = $fvalue;
                break;
            default:
                break;
        }
        $_GET['page'] = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $sort = 'pay_time DESC,create_time DESC';
        if($status=='101'){////已付款未取餐状态下特殊处理
            $sort = 'is_taking ASC,expect_pick_time ASC,pay_time DESC,create_time DESC';
        }
        $shop_order = D('Shop_order')->get_order_list($where, $sort, 12, false);
		if ($shop_order['order_list']) {
            foreach ($shop_order['order_list'] as $k => $v) {
                if ($v['pay_type_str'] == L_('未支付')) {
                    $pay_status = L_('未支付');
                } else {
                    if (empty($v['third_id']) && ($v['pay_type'] == 'offline')) {
                        $pay_status = L_('线下未支付');
                    } elseif($v['paid'] == 0) {
                        $pay_status = L_('未支付');
                    } else {
                        $pay_status = L_('已支付');
                    }
                }

                $arr['shop_order'][$k] = array(
                    'order_id' => $v['order_id'],
                    'username' => $v['username'],
                    'userphone' => $v['userphone'],
                    'address' => $v['address'],
                    'create_time' => date('Y-m-d H:i:s', $v['create_time']),
					'is_booking' => $v['is_booking'] ? true :false,
                    'expect_pick_time' => $v['expect_pick_time'] ? date('Y-m-d H:i:s', $v['expect_pick_time']) : '',
                    'pay_type_str' => $v['pay_type_str'],
                    'status' => $v['status'],
                    'order_status' => $v['order_status'],
                    'paid' => $v['paid'],
                    'is_pick_in_store' => $v['is_pick_in_store'],
                    'deliverName' => $this->config['deliver_name'],
                    'pay_status' => $pay_status,
                    'total_price' => floatval($v['total_price']),
                    'change_price' => floatval($v['change_price']),
                    'price' => floatval($v['price']),
                    'change_price_reason' => $v['change_price_reason'],
                    'order_from_name' => $this->order_froms[$v['order_from']],
                    'cancel_type' => $v['cancel_type'],
                    'order_from' => $v['order_from'],
                    'fetch_number' => $v['fetch_number'],
                    'virtual_phone_arr' => $v['virtual_phone_arr'] ? $v['virtual_phone_arr'] : '',
                    'expect_take_time' => '',
                    'is_weight' => $v['is_weight'],
                );

                if ($v['is_pick_in_store'] != 2 && $v['is_pick_in_store'] != 3 && $v['order_status'] >= 1 && in_array($v['status'], [0, 1]) && $v['expect_take_time'] > 0) {
                    $supply = D('Deliver_supply')->where(['order_id' => $v['order_id'], 'item' => 2])->find();
                    if (empty($supply)) {
                        $arr['shop_order'][$k]['expect_take_time'] = date('Y-m-d H:i', $v['expect_take_time']);
                    }
                }

                // 虚拟号码是否过期
                if ($v['virtual_phone_overtime']>time()) {
                    $arr['shop_order'][$k]['virtual_phone_overtime'] = 0;
                }else{
                    $arr['shop_order'][$k]['virtual_phone_overtime'] = 1;
                }
                // 存在虚拟号码 则隐藏真实号码
                if ($v['virtual_phone_arr']) {
                    $arr['shop_order'][$k]['userphone'] = phone_show($v['userphone']);
                }

                if ($v['expect_use_time']) {
                    if($v['is_pick_in_store'] == 2){
                        $arr['shop_order'][$k]['expect_use_time'] = ($v['is_right_now'] ? '<span style="color:#e93e14;">'.L_('立即送达').' </span>'.date('H:i',$v['expect_use_time']) : date('Y-m-d H:i', $v['expect_use_time'])) . ' - ' . date('H:i', $v['expect_use_time'] + 1200);
                    }else{
                        $arr['shop_order'][$k]['expect_use_time'] = ($v['is_right_now'] ? '<span style="color:#e93e14;">'.L_('立即送达').' </span>'.date('H:i',$v['expect_use_time']) : date('Y-m-d H:i', $v['expect_use_time'])) . ' - ' . date('H:i', $v['expect_use_time'] + 900);
                    }
                } else {
                    $arr['shop_order'][$k]['expect_use_time'] = L_('尽快');
                }

            }
        } else {
            $arr['shop_order'] = array();
        }
        $arr['page'] = $shop_order['page'];
        $arr['count'] = $shop_order['count'];
        if ($_GET['page'] == 1) {
           $shop = M('Merchant_store_shop')->field(true)->where(array('store_id' => $this->store['store_id']))->find();
           $arr['deliver_type'] = $shop['deliver_type'];
           $arr['is_open_pick'] = $shop['is_open_pick'];
           $arr['is_change'] = $this->staff_session['is_change'];
        }
        $arr['pay_type'] = $pay_types;
        $k=1;
        if($shop_type==2){
            $arr['order_from'] =array($k=>$this->order_froms[$k]);
        }else{
            unset($this->order_froms[$k]);
            $arr['order_from'] = $this->order_froms;
            // 开启多语言
            if (C('config.open_multilingual')) {
                $arr['order_from'] = array(0=>L_('微信公众号和WAP版'), 2=>L_('安卓应用'), 3=>L_('苹果应用'),4=>L_('微信小程序'), 5=>L_('电脑版X1',['X1'=>$this->config['shop_alias_name']]));
            }
        }
        D('Shop_order')->status_list[11] = L_('申请退货');
        D('Shop_order')->status_list[12] = L_('已付款未消费');
        if($shop_type==1){//只有快店才有此状态
            D('Shop_order')->status_list[101] = L_('已付款/未取餐');
        }
        $arr['status_list'] = D('Shop_order')->status_list;
        // 开启多语言
        if (C('config.open_multilingual')) {
            unset($arr['status_list'][7],$arr['status_list'][8],$arr['status_list'][9],$arr['status_list'][10]);
        }
		if(!$this->staff_session['is_change']){
			unset($arr['status_list'][6]);
		}
        
        $this->returnCode(0, $arr);
    }

    /**
     * 体育核销列表
     */
    public function toolsVerifyList()
    {
        $where  = array(
            'd.staff_id' => $this->staff_session['id'],
            'd.status'   => 2,
            't.type'     => ['neq', 'scenic']
        );
        $fvalue = isset($_POST['fv']) ? trim(htmlspecialchars($_POST['fv'])) : '';//搜索关键词
        $ftype  = isset($_POST['ft']) ? trim($_POST['ft']) : '';//搜索关键词类型
        $stime  = isset($_POST['stime']) ? trim(htmlspecialchars($_POST['stime'])) : '';//搜索开始时间
        $etime  = isset($_POST['etime']) ? trim(htmlspecialchars($_POST['etime'])) : '';//搜索结束时间
        if ($stime && $etime ) {
            $where['d.last_time'] = array(array('gt', strtotime($stime . ' 00:00:01')), array('lt', strtotime($etime . ' 23:59:59')));
        }
        switch ($ftype) {
            case 'oid': //订单id
                $fvalue && $where['o.real_orderid'] = array('like', '%' . $fvalue . '%');
                break;
            case 'xm':  //下单人姓名
                $fvalue && $where['o.nickname'] = array('like', '%' . $fvalue . '%');
                break;
            case 'dh':  //下单人电话
                $fvalue && $where['o.phone'] = array('like', '%' . $fvalue . '%');
                break;
            default:
                $fvalue && $where['o.real_orderid'] = array('like', '%' . $fvalue . '%');
                break;
        }
        $_GET['page'] = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $sort = 'd.last_time DESC';
        $tools_order = D('Life_tools_order_detail')->get_verify_list($where, $sort);
        $this->returnCode(0, $tools_order);
    }

    /**
     * 体育核销列表
     */
    public function scenicVerifyList()
    {
        $where  = array(
            'd.staff_id' => $this->staff_session['id'],
            'd.status'   => 2,
            't.type'     => ['eq', 'scenic']
        );
        $fvalue = isset($_POST['fv']) ? trim(htmlspecialchars($_POST['fv'])) : '';//搜索关键词
        $ftype  = isset($_POST['ft']) ? trim($_POST['ft']) : '';//搜索关键词类型
        $stime  = isset($_POST['stime']) ? trim(htmlspecialchars($_POST['stime'])) : '';//搜索开始时间
        $etime  = isset($_POST['etime']) ? trim(htmlspecialchars($_POST['etime'])) : '';//搜索结束时间
        if ($stime && $etime ) {
            $where['d.last_time'] = array(array('gt', strtotime($stime . ' 00:00:01')), array('lt', strtotime($etime . ' 23:59:59')));
        }
        switch ($ftype) {
            case 'oid': //订单id
                $fvalue && $where['o.real_orderid'] = array('like', '%' . $fvalue . '%');
                break;
            case 'xm':  //门票名称
                $fvalue && $where['o.ticket_title'] = array('like', '%' . $fvalue . '%');
                break;
            case 'dh':  //景区名称
                $fvalue && $where['t.title'] = array('like', '%' . $fvalue . '%');
                break;
            default:
                $fvalue && $where['o.real_orderid'] = array('like', '%' . $fvalue . '%');
                break;
        }
        // dump($fvalue);die;
        $_GET['page'] = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $sort = 'd.last_time DESC';
        $tools_order = D('Life_tools_order_detail')->get_verify_list($where, $sort);
        $this->returnCode(0, $tools_order);
    }

    /**
     * 次卡核销列表
     */
    public function cardVerifyList()
    {
        $mer_id = M('Merchant_store')->where(['store_id'=>$this->staff_session['store_id']])->getField('mer_id');
        $where  = [
            'r.mer_id' => $mer_id
        ];
        $fvalue = isset($_POST['fv']) ? trim(htmlspecialchars($_POST['fv'])) : '';//搜索关键词
        $ftype  = isset($_POST['ft']) ? trim($_POST['ft']) : '';//搜索关键词类型
        $stime  = isset($_POST['stime']) ? trim(htmlspecialchars($_POST['stime'])) : '';//搜索开始时间
        $etime  = isset($_POST['etime']) ? trim(htmlspecialchars($_POST['etime'])) : '';//搜索结束时间
        $type  = isset($_POST['type']) ? trim(htmlspecialchars($_POST['type'])) : '';//搜索类型
        if ($stime && $etime ) {
            $where['r.add_time'] = array(array('gt', strtotime($stime . ' 00:00:01')), array('lt', strtotime($etime . ' 23:59:59')));
        }
        if($this->staff_session['type'] != 2){
            $where['r.staff_id'] = $this->staff_session['id'];
        }
        switch ($ftype) {
            case 'oid': //订单id
                $fvalue && $where['o.orderid'] = array('like', '%' . $fvalue . '%');
                break;
            case 'ck':  //次卡名称
                $fvalue && $where['o.title'] = array('like', '%' . $fvalue . '%');
                break;
            case 'nk':  //昵称
                $fvalue && $where['o.nickname'] = array('like', '%' . $fvalue . '%');
                break;
            case 'ph':  //手机号
                $fvalue && $where['o.phone'] = array('like', '%' . $fvalue . '%');
                break;
        }
        $where2 = '';
        if($type){
            if($type == 'sports'){
                $where2 = 'r.type = "sports" OR r.type = "stadium" OR r.type = "course"';
            }else{
                $where2 = 'r.type = "scenic"';
            }
            switch ($type) {
                case 'sports': //体育
                    $where['r.type'] = array('like', '%' . $fvalue . '%');
                    break;
                case 'scenic':  //景区
                    
                    break;
            }
        }
        $count = M('Life_tools_card_order_record')
                ->join(' AS r LEFT JOIN ' . C('DB_PREFIX') . 'life_tools_card_order AS o ON r.order_id = o.order_id')
                ->where($where)
                ->where($where2)
                ->count();

        $page  = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $pagesize = isset($_POST['page_size']) ? intval($_POST['page_size']) : 10;
        $star = $pagesize * ($page - 1);

        $data = M('Life_tools_card_order_record')
        ->join(' AS r LEFT JOIN ' . C('DB_PREFIX') . 'life_tools_card_order AS o ON r.order_id = o.order_id')
        ->field('o.*,r.add_time AS verify_time,r.staff_name')
        ->where($where)
        ->where($where2)
        ->limit($star . ',' . $pagesize)
        ->select(); 

        $typeMap = [
            'scenic'    =>  '景区',
            'stadium'   =>  '体育馆',
            'course'    =>  '课程',
            'sports'    =>  '体育'
        ];
        foreach($data as $key => $val){
            $data[$key]['verify_time_txt'] = date('Y-m-d H:i:s', $val['verify_time']);
            $data[$key]['order_source'] = ($typeMap[$val['type']] ?? '') . '次卡';
        }

        $result = [];
        $result['count'] = $count;//ceil($count / 10);
        $result['data'] = $data;
        $result['page'] = isset($_POST['page']) ? intval($_POST['page']) : 1;

        $this->returnCode(0, $result);
    }

    public function appointVerifyList()
    {
        $data = [];
        $fvalue = isset($_POST['fv']) ? trim(htmlspecialchars($_POST['fv'])) : '';//搜索关键词
        $ftype  = isset($_POST['ft']) ? trim($_POST['ft']) : '';//搜索关键词类型
        $stime  = isset($_POST['stime']) ? trim(htmlspecialchars($_POST['stime'])) : '';//搜索开始时间
        $etime  = isset($_POST['etime']) ? trim(htmlspecialchars($_POST['etime'])) : '';//搜索结束时间
        $page = $_POST['page'] ?? 1;
        $data['params'] = [
            'staff_id'  =>  $this->staff_session['id'],
            'mer_id' => $this->store['mer_id'],
            'page_size' => 10,
            'page' => $page,
            'from' => 'v10'
        ];
        if(!empty($fvalue)){
            $data['params']['keywords'] = $fvalue;
        }
        if(!empty($ftype)){
            $data['params']['search_by'] = $ftype == 'oid' ? 1 : 2;
        }
        if(!empty($stime)){
            $data['params']['start_date'] = $stime;
        }
        if(!empty($etime)){
            $data['params']['end_date'] = $etime;
        }
        $result = invoke_v20_service('app\life_tools\model\service\LifeToolsAppointService/verifyList', $data);
        $this->returnCode(0, $result['data']['retval'] ?? []);
    }
    

    /**
     * 快店商品的销量统计
     */
    public function statistics()
    {
        $begin_time = isset($_POST['begin_time']) ? strtotime($_POST['begin_time'] . ' 00:00:01') : 0;
        $end_time = isset($_POST['end_time']) ? strtotime($_POST['end_time'] . ' 23:59:59') : 0;
        $arr = array();
        if ($end_time && $begin_time) {
            $data = array('store_id' => $this->store['store_id']);
            $data['begin_time'] = $begin_time;
            $data['end_time'] = $end_time;
            $shop_type=I('shop_type',1,'intval');
            $data['shop_type']=$shop_type;
            $arr = D('Shop_order')->goods_sale_count($data);
        }
        $this->returnCode(0, $arr);
    }

	/**
     * 获取会员卡信息
     */
    public function ajax_card_new()
    {
        $key = isset($_POST['key']) ? htmlspecialchars($_POST['key']) : '';
        $type = isset($_POST['type']) ? htmlspecialchars($_POST['type']) : '';

        if (!$this->staff_session) {
            $this->returnCodeError(L_('店员未登录'));
        }
        //获取店员绑定店铺信息
        $store_info = M('merchant_store')
            ->field('store_id,area_id,is_internal_card_pay')
            ->where(['store_id' => $this->staff_session['store_id']])
            ->find();
        if (!$store_info) {
            $this->returnCodeError(L_('未找到店员店铺信息'));
        }

        // 一卡通查询
        if ($type == 'ecard'){
            try{
                $cardData = D('InternalMembershipCard')->getInternalCard($store_info, $key, $_POST['password']);
            }catch (\Exception $e){
                $this->returnCodeError($e->getMessage());
            }
            $this->returnCode(0,$cardData['data']);
        }
        // scan_from_merchang_card 到店付、线下零售识别用户身份是否开启商家会员卡识别方式
        if (!$this->config['scan_from_merchang_card'] && ($this->config['is_open_pay_bind_user']==1||C('config.open_user_import_create_user') == 1)) {
            if (trim($_POST['auth_code'])) {
                $phone_info = D('User_payid')->get($_POST['auth_code']);
                if ($phone_info['error']) {
                    exit(json_encode(array('err_code' => true, 'data' => $phone_info['msg'])));
                }else{
                    $key = $phone_info['phone'];
                }
            }

            //开启客户导入生成真实用户
            if(C('config.open_user_import_create_user') == 1){
                // 使用实体卡号搜索
                $user = D('User')->field(true)->where('phone ="'.$key.'" OR cardid="'.$key.'"')->find();
            }else{
                $user = D('User')->field(true)->where('phone ="'.$key.'"')->find();
            }

            if($user) {
                $card = D('Card_userlist')->field(true)->where(array('uid' => $user['uid'], 'mer_id' => $this->store['mer_id'],'status'=>1))->find();
                $card_data = $this->format_ajax_card($card);
                if ($card_data['err_code']) {
                    $card_data['err_code'] = false;
                    $card_data['data'] = array(
                        'name' => $user['truename'] ? $user['truename'] : $user['nickname'], 
                        'sex' => $user['sex'] == 1 ? L_('男') : L_('女'), 
                        'phone' => $user['phone']
                    );
                    $return['uid'] = $user['uid'];
                }

                $card_data['data']['card_new'] = D('Card_new')->get_use_coupon_by_params($user['uid'], $this->store['mer_id'], 'shop');
                $card_data['data']['system_card'] = D('System_coupon')->get_use_coupon_by_params($user['uid'], 'shop');;

                // 会员等级优惠
                $level_off=false;
                if( !empty($user) && isset($user['level']) ){
                    $leveloff=!empty($this->store['leveloff']) ? unserialize($this->store['leveloff']) :'';
                    /****type:0无优惠 1百分比 2立减*******/
                    if(!empty($leveloff) && isset($leveloff[$user['level']]) ){
                        $level_off=$leveloff[$user['level']];
                        if($level_off['type']==1){
                            $level_off['offstr']=L_('单价按原价X1%来结算',$level_off['vv']);
                        }elseif($level_off['type']==2){
                            $level_off['offstr']=L_('单价立减X1',$level_off['vv'].C('config.Currency_txt'));
                        }
                    }
                }
                $card_data['data']['leveloff'] = $level_off;
                $this->returnCode(0,$card_data['data']);
            }else{
                $this->returnCode(1,array(),L_('没有用户信息'));
            }
        }else{
            if(strlen($key) == 11 && $user = D('User')->field(true)->where(array('phone' => $key))->find()) {
                $card = D('Card_userlist')->field(true)->where(array('uid' => $user['uid'], 'mer_id' => $this->store['mer_id'], 'status'=>array('eq',1)))->find();
                $card_data = $this->format_ajax_card($card);
            }else{
                $condition_user['mer_id'] = $this->store['mer_id'];
                $condition_user['status'] = array('eq',1);
                $condition_user['_string'] = "id={$key} OR wx_card_code={$key} OR physical_id = {$key}";
                $card = D('Card_userlist')->field(true)->where($condition_user)->find();
                if(empty($card) &&($user = D('User')->field(true)->where(array('truename' =>  $key))->find() || $user = D('User')->field(true)->where(array('nickname' =>  array('like','%'.$key.'%')))->find()) ){
                    $card = D('Card_userlist')->field(true)->where(array('uid' => $user['uid'], 'mer_id' => $this->store['mer_id']))->find();
                }
                $card_data = $this->format_ajax_card($card);
            }

            $this->returnCode(0,$card_data['data']);
        }
    }

	/**
	 * 获取会员卡信息
	 */
    public function ajax_card()
    {
        $key = isset($_POST['key']) ? htmlspecialchars($_POST['key']) : '';
        if(strlen($key) == 11 && $user = D('User')->field(true)->where(array('phone' => $key))->find()) {
            $card = D('Card_userlist')->field(true)->where(array('uid' => $user['uid'], 'mer_id' => $this->store['mer_id']))->find();
           $card_data = $this->format_ajax_card($card);
        }else{

            $condition_user['mer_id'] = $this->store['mer_id'];
            $condition_user['_string'] = "id={$key} OR wx_card_code={$key} OR physical_id = {$key}";
            $card = D('Card_userlist')->field(true)->where($condition_user)->find();
            if(empty($card) &&($user = D('User')->field(true)->where(array('truename' =>  $key))->find() || $user = D('User')->field(true)->where(array('nickname' =>  array('like','%'.$key.'%')))->find()) ){
                $card = D('Card_userlist')->field(true)->where(array('uid' => $user['uid'], 'mer_id' => $this->store['mer_id']))->find();
            }
            $card_data = $this->format_ajax_card($card);
        }
        if ($card_data['err_code']) {
            $this->returnCode(1000,array(),$card_data['data']);
        }else{
            $this->returnCode(0,$card_data['data']);
        }
    }

    public function format_ajax_card($card){
        if(empty($card)){
            return array('err_code' => true, 'data' =>L_('没有会员卡信息'));
        }
        $card_source = M(_view('Card_new'))->where(array('mer_id' => $this->store['mer_id'], 'status' => 1))->find();
        if (empty($card_source)) {
            return array('err_code' => true, 'data' =>  L_('没有会员卡信息'));
        }
        if ($user = D('User')->field(true)->where(array('uid' => $card['uid']))->find()) {
            $return = array('name' => $user['truename'] ? $user['truename'] : $user['nickname'], 'sex' => $user['sex'] == 1 ? L_('男') : L_('女'), 'card_id' => $card['id'], 'phone' => $user['phone']);
            $return['card_money'] = $card['card_money'] + $card['card_money_give'];
            $return['card_score'] = $card['card_score'];
            $return['physical_id'] = $card['physical_id'];
            $return['uid'] = $user['uid'];
            $return['discount'] = empty($card_source['discount']) ? 10 : $card_source['discount'];

            $return['card_new'] = D('Card_new')->get_use_coupon_by_params($user['uid'], $this->store['mer_id'], 'shop',$this->staff_session['store_id']);

            return array('err_code' => false, 'data' => $return);
        } else {
            return array('err_code' => true, 'data' => L_('此卡找不到相应的用户信息'));
        }
    }

    public function ajax_shop_goods()
	{
		$is_refresh = isset($_POST['refresh']) ? intval($_POST['refresh']) : 1;
		$product_list = D('Shop_goods')->get_list($_POST['store_id'], $is_refresh,['status'=>1],['sort_status'=>1]);
		if ($product_list) {
			$this->returnCode(0,$product_list);
		} else {
			$this->returnCode(1000,array(),L_('暂无商品'));
		}
	}

    //预约
    public function pay_appoint_order(){
		
        $appoint_order = M('Appoint_order')->where(array('order_id'=>$_POST['order_id']))->find();
        if(empty($appoint_order)){
			$this->returnCodeError(L_('订单不存在'));
        }

        $now_pay_money = $appoint_order['product_price'] - $appoint_order['product_payment_price'];

        if(!$now_pay_money){
            $now_pay_money = $appoint_order['appoint_price'] - $appoint_order['payment_money'];
        }

        if($now_pay_money <= 0){
			$this->returnCodeError(L_('数据处理有误，总价需要大于定金'));
        }
		
        if($this->config['cash_pay_qrcode']){
            $pay_qrcode_url = $this->config['site_url'] . '/index.php?c=Recognition&a=get_tmp_qrcode&qrcode_id=' . (3100000000 + $_POST['order_id']);
        }else{
            $pay_qrcode_url = $this->config['site_url'] . '/index.php?c=Recognition&a=get_own_qrcode&qrCon=' . urlencode($this->config['site_url'].'/wap.php?c=My&a=appoint_order_before&order_id='.$_POST['order_id']);
        }

        $now_pay_money = getFormatNumber($now_pay_money);


        $data = array('real_orderid' => $appoint_order['real_orderid'], 'order_id' => $_POST['order_id'], 'price' => $now_pay_money, 'pay_qrcode_url' => $pay_qrcode_url);
        $this->returnCode(0, $data);
    }

	public function shop_order_save()
	{
        if($this->now_merchant['status']!=1){
            if($this->now_merchant['merchant_end_time']>0 && $this->now_merchant['merchant_end_time']<time()){
                $this->returnCode(1,'',L_('商家已过期，暂不可使用该功能！'));
            }
            $this->returnCode(1,'',L_('商家已').$this->merchant_status[$this->now_merchant['status']].L_('，暂不可使用该功能！'));
        }

        // 开启店员权限功能 店小二无线下零售权限
        if (C('config.open_staff_power')&&$this->staff_session['type'] == 0) {
            $this->returnCode(1,'',L_('您当前身份为【店小二】，暂无线下零售权限，请联系管理员！'));
        }

		$data = isset($_POST['data']) ? ($_POST['data']) : '';
		$uid = isset($data['card_data']['uid']) && $data['card_data']['uid'] ? intval($data['card_data']['uid']) : 0;
		$store_id = $this->staff_session['store_id'];
		$return = D('Shop_goods')->checkCart($store_id, $uid, $data['goods_data'], 2, 0, true);
		if ($return['error_code']) $this->returnCode(1000,array(),$return['msg']);
		if (IS_POST) {
			
			if (!($user = D('User')->field(true)->where(array('uid' => $uid))->find())) {
				$uid = 0;
			}
			
			D('Shop_order')->where(array('staff_id' => $this->staff_session['id'], 'order_from' => 6, 'paid' => 0, 'is_del' => 0))->save(array('is_del' => 1));
	
			$now_time = time();
			$order_data = array();
			$order_data['mer_id'] = $return['mer_id'];
			$order_data['store_id'] = $return['store_id'];
			$order_data['uid'] = $uid;//TODO
			$order_data['staff_id'] = $this->staff_session['id'];
			$order_data['last_staff'] = $this->staff_session['name'];

			$order_data['desc'] = '';
			$order_data['create_time'] = $now_time;
			$order_data['last_time'] = $now_time;
			$order_data['invoice_head'] = '';
			$order_data['village_id'] = 0;

			$order_data['num'] = $return['total'];
			$order_data['packing_charge'] = $return['packing_charge'];//打包费
			$order_data['merchant_reduce'] = $return['sto_first_reduce'] + $return['sto_full_reduce'];//店铺优惠
			$order_data['balance_reduce'] = $return['sys_first_reduce'] + $return['sys_full_reduce'];//平台优惠
			$orderid  = date('ymdhis').substr(microtime(), 2, 8 - strlen($uid)). $uid;
			$order_data['real_orderid'] = $orderid;
			$order_data['no_bill_money'] = 0;//无需跟平台对账的金额
			
			$order_data['is_pick_in_store'] = 2;//配送方式 0：平台配送，1：商家配送，2：自提，3:快递配送
			$delivery_fee = $order_data['freight_charge'] = 0;//运费
			$order_data['username'] = isset($user['nickname']) && $user['nickname'] ? $user['nickname'] : '';
			$order_data['userphone'] = isset($user['phone']) && $user['phone'] ? $user['phone'] : '';
			$order_data['address'] = '';
			$order_data['address_id'] = 0;
			$order_data['pick_id'] = 0;
			$order_data['status'] = 0;
			$order_data['order_from'] = 6;
			$order_data['expect_use_time'] = 0;//客户期望使用时间
            $order_data['can_discount_money'] =  $return['can_discount_money'];//可用商家优惠券的总价

            $hasReduceMoney = 0;
            if(C('config.vip_level_discount_sequence')==1) { //满减后计算
                if ($return['vip_discount'] < 100 && $return['vip_discount'] > 0) {
                    if ($return['sorts_discount_type'] == 0) { //折上折
                        $tempMoney = round($return['vip_discount_money'] - round(($order_data['merchant_reduce'] + $order_data['balance_reduce']), 2), 2);
                        $hasReduceMoney = $tempMoney - round($tempMoney * ($return['vip_discount'] * 0.01), 2);
                        foreach ($return['goods'] as &$goods) {
                            $rate = sprintf('%.2f', ($goods['price'] * $goods['num']) / $return['price']);
                            $rate = $rate ? $rate : 1;
                            $goods['discount_price'] = floatval(round($goods['discount_price'] - (($hasReduceMoney * $rate) / $goods['num']), 2));
                        }
                    } else { //折扣最优
                        if ($return['optimalDiscountMoney'] > 0) {
                            $totalOptimalMoney = $return['optimalDiscountMoney'];
                            $return['vip_discount_money'] = $totalOptimalMoney;
                            $tempMoney = round($totalOptimalMoney - round(($order_data['merchant_reduce'] + $order_data['balance_reduce']), 2), 2);
                            $hasReduceMoney = $tempMoney - round($tempMoney * ($return['vip_discount'] * 0.01), 2);
                            foreach ($return['goods'] as &$goods) {
                                $rate = sprintf('%.2f', ($goods['price'] * $goods['num']) / $return['price']);
                                $rate = $rate ? $rate : 1;
                                $goods['discount_price'] = floatval(round($goods['price'] - (($hasReduceMoney * $rate) / $goods['num']), 2));
                            }
                        }
                    }
                }
                $return['vip_discount_money']  = round($return['vip_discount_money'] - $hasReduceMoney,2);
                $return['discount_price']  = $return['vip_discount_money'];
                $return['can_discount_money'] = round($return['can_discount_money'] - $hasReduceMoney,2);
                $order_data['vip_level_after'] = 1;//会员等级优惠满减后计算
            }else{ //正常计算
               $hasReduceMoney = $return['vip_level_reduce_money'] ? $return['vip_level_reduce_money'] : 0;
            }

            $order_data['vip_level_reduce_money'] = $hasReduceMoney;//会员等级折扣优惠金额
            $order_data['vip_level_discount'] = $return['vip_discount'] ? $return['vip_discount'] : 0;//会员等级折扣
            

			$order_data['goods_price'] = $return['price'];//商品的价格
			$order_data['extra_price'] = $return['extra_price'];//另外要支付的金额
			$order_data['discount_price'] = $return['vip_discount_money'];//商品折扣后的总价
			$order_data['total_price'] = $return['price'] + $delivery_fee + $return['packing_charge'];//订单总价  商品价格+打包费+配送费
			$order_data['price'] = $order_data['discount_price'] + $delivery_fee + $return['packing_charge'] - $order_data['merchant_reduce'] - $order_data['balance_reduce'];//实际要支付的价格
			$order_data['discount_detail'] = '';//优惠详情
			if ($return['price'] - $return['store_discount_money'] > 0) {
				$order_data['discount_detail'] = L_('店铺折扣优惠：X1' , floatval($return['price'] - $return['store_discount_money']));
			}
			if ($return['store_discount_money'] - $return['vip_discount_money'] > 0) {
				$order_data['discount_detail'] = $order_data['discount_detail'] ? $order_data['discount_detail'] . ';VIP优惠：' . floatval($return['store_discount_money'] - $return['vip_discount_money']) : L_('VIP优惠：X1' ,floatval($return['store_discount_money'] - $return['vip_discount_money']));
			}
			if ($return['sys_first_reduce']> 0) {
				$order_data['discount_detail'] = $order_data['discount_detail'] ? $order_data['discount_detail'] . ';平台首单减：' . $return['sys_first_reduce'] : L_('平台首单减：X1', $return['sys_first_reduce']);
			}
			if ($return['sys_full_reduce'] > 0) {
				$order_data['discount_detail'] = $order_data['discount_detail'] ? $order_data['discount_detail'] . ';平台满减：' . $return['sys_full_reduce'] : L_('平台满减：X1' , $return['sys_full_reduce']);
			}
			if ($return['sto_first_reduce']> 0) {
				$order_data['discount_detail'] = $order_data['discount_detail'] ? $order_data['discount_detail'] . ';店铺首单减：' . $return['sto_first_reduce'] : L_('店铺首单减：X1' , $return['sto_first_reduce']);
			}
			if ($return['sto_full_reduce'] > 0) {
				$order_data['discount_detail'] = $order_data['discount_detail'] ? $order_data['discount_detail'] . ';店铺满减：' . $return['sto_full_reduce'] : L_('店铺满减：X1' , $return['sto_full_reduce']);
			}
            if ($return['vip_level_reduce_money'] > 0) {
                $order_data['discount_detail'] = $order_data['discount_detail'] ? $order_data['discount_detail'] . ';平台会员等级优惠：' . $return['vip_level_reduce_money'] : L_('平台会员等级优惠：X1' , $return['vip_level_reduce_money']);
            }
			$order_data['reduce_stock_type'] = $return['store']['reduce_stock_type'];//'减库存类型（0：支付后，1：下单后）'
			
			if ($order_id = D('Shop_order')->add($order_data)) {
				D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 0));
				$detail_obj = D('Shop_order_detail');
				$goods_obj = D("Shop_goods");
				foreach ($return['goods'] as $grow) {
					$detail_data = array('store_id' => $return['store_id'], 'order_id' => $order_id, 'number' => isset($grow['number']) && $grow['number'] ? $grow['number'] : '', 'cost_price' => $grow['cost_price'], 'unit' => $grow['unit'], 'goods_id' => $grow['goods_id'], 'name' => $grow['name'], 'price' => $grow['price'], 'num' => $grow['num'], 'spec' => $grow['str'], 'spec_id' => $grow['spec_id'], 'create_time' => time(),'extra_price'=>$grow['extra_price'] ? $grow['extra_price'] : 0);
					$detail_data['is_seckill'] = intval($grow['is_seckill_price']);
                    $detail_data['discount_type'] = intval($grow['discount_type']);
                    $detail_data['discount_rate'] = $grow['discount_rate'];
                    $detail_data['sort_id'] = $grow['sort_id'];
                    $detail_data['old_price'] = floatval($grow['old_price']);
                    $detail_data['discount_price'] = floatval($grow['discount_price']);
					D('Shop_order_detail')->add($detail_data);
					$order_data['reduce_stock_type'] && $goods_obj->update_stock($grow);//修改库存
				}
				if ($user['openid']) {
					$keyword2 = '';
					$pre = '';
					foreach ($return['goods'] as $menu) {
						$keyword2 .= $pre . $menu['name'] . ':' . $menu['price'] . '*' . $menu['num'];
						$pre = '\n\t\t\t';
					}
					$href = C('config.site_url').'/wap.php?c=Shop&a=status&order_id='. $order_id . '&mer_id=' . $order_data['mer_id'] . '&store_id=' . $order_data['store_id'];
					$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
					$model->sendTempMsg('OPENTM201682460', array('href' => $href, 'wecha_id' => $user['openid'], 'first' => L_('您好，您的订单已生成'), 'keyword3' => $orderid, 'keyword1' => date('Y-m-d H:i:s'), 'keyword2' => $keyword2, 'remark' => L_('您的该次X1下单成功，感谢您的使用！',$this->config['shop_alias_name'])), $order_data['mer_id']);
				}

				
				$printHaddle = new PrintHaddle();
				$printHaddle->printit($order_id, 'shop_order', 0);
	
				$sms_data = array('mer_id' => $return['mer_id'], 'store_id' => $return['store_id'], 'type' => 'shop');
				if ($this->config['sms_shop_place_order'] == 1 || $this->config['sms_shop_place_order'] == 3) {
					$sms_data['uid'] = $user['uid'];
					$sms_data['mobile'] = $order_data['userphone'];
					$sms_data['sendto'] = 'user';
					$sms_data['content'] = L_('您X1在【X2】中下了一个订单，订单号：X3' ,array('X1'=>date(L_("H时i分")),'X2'=>$return['store']['name'],'X3'=>$orderid));
					Sms::sendSms($sms_data);
				}
				if ($this->config['sms_shop_place_order'] == 2 || $this->config['sms_shop_place_order'] == 3) {
					$sms_data['uid'] = 0;
					$sms_data['mobile'] = $return['store']['phone'];
					$sms_data['sendto'] = 'merchant';
					$sms_data['content'] = L_('顾客【X1】在X2时下了一个订单，订单号：X3 请您注意查看并处理!',array('X1'=>$order_data['username'],'X2'=> date("Y-m-d H:i:s"),'X3'=>$orderid));
					Sms::sendSms($sms_data);
				}
				if($this->config['cash_pay_qrcode']){
					$pay_qrcode_url = $this->config['site_url'] . '/index.php?c=Recognition&a=get_tmp_qrcode&qrcode_id=' . (3700000000 + $order_id);
				}else{
					$pay_qrcode_url = $this->config['site_url'] . '/index.php?c=Recognition&a=get_own_qrcode&qrCon=' . urlencode($this->config['site_url'].'/wap.php?c=My&a=shop_order_before&order_id='.$order_id);
				}
				$data = array('real_orderid' => $order_data['real_orderid'], 'order_id' => $order_id, 'price' => $order_data['price'], 'pay_qrcode_url' => $pay_qrcode_url);
				$this->returnCode(0, $data);
			} else {
				$this->returnCode(1000,array(),L_('订单保存失败'));
			}
		} else {
			$this->returnCode(1000,array(),L_('不合法的提交'));
		}
	}
    public function print_prev_order(){
        $where = [];
        $where['store_id'] = $this->store['store_id'];
        $where['order_from'] = 6;
        $where['paid'] = 1;
        $order = M('Shop_order')->where($where)->order('order_id DESC')->find();
        $printHaddle = new PrintHaddle();
        $res = $printHaddle->printit($order['order_id'], 'shop_order', 0);
        $this->returnCode(0,L_('打印上单成功！'));
    }

	public function get_pay_method(){
		$return['offline_pay_list'] = M('Store_pay')->where(array('store_id' => $this->staff_session['store_id']))->order('`id` ASC')->select();
		$return['open_alipay'] = $this->config['arrival_alipay_open'] ? $this->config['arrival_alipay_open'] : 0;
		$return['cash_pay_qrcode'] = $this->config['cash_pay_qrcode'];
		$this->returnCode(0,$return);
	}
	public function shop_change_price()
	{
		if(empty($this->staff_session['is_change'])) {
			$this->returnCode(1000,array(),L_('您没有修改价格的权限!'));
		}
		$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
		$where = array('order_id' => $order_id, 'store_id' => $this->staff_session['store_id']);
		$order = D('Shop_order')->get_order_detail($where);
		
		if (empty($order)) {
			$this->returnCode(1000,array(),L_('不存在的订单信息!'));
		}
		if (!($order['paid'] == 0 && $order['status'] == 0)) {
			$this->returnCode(1000,array(),L_('该订单已经不能修改支付价格了!'));
		}
		$change_price = isset($_POST['change_price']) ? floatval($_POST['change_price']) : $order['price'];
		if ($change_price == $order['price']) {
			$this->returnCode(1000,array(),L_('您没有修改价格!'));
		}
		if ($change_price <= 0) {
			$this->returnCode(1000,array(),L_('您不能把价格改成小于等于0的数!'));
		}
		$data = array('price' => $change_price);
		$data['last_staff'] = $this->staff_session['name'];
		$data['last_time'] = time();
		if (floatval($order['change_price']) == 0) {
			$data['change_price'] = $order['price'];
		}
		$data['change_price_reason'] = $_POST['change_price_reason'];
		if (D('Shop_order')->where($where)->save($data)) {
			$phones = explode(' ', $this->staff_session['phone']);
			D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 30, 'name' => $this->staff_session['name'], 'phone' => $phones[0], 'note' => $change_price));
			$this->returnCode(0,L_('修改成功'));
		} else {
			$this->returnCode(1000,array(),L_('修改出错，稍后重试！'));
		}
	}
	public function shop_arrival_check()
	{
		$now_order = M('Shop_order')->where(array('order_id'=>$_POST['order_id']))->find();
		if ($now_order['paid']) {
			$this->returnCode(0,L_('支付成功'));
		} else {
			$this->returnCode(1000,L_('支付失败'));
		}
	}
	public function arrival_pay()
	{
        // 开启店员权限功能 店小二无线下零售权限
        if (C('config.open_staff_power')&&$this->staff_session['type'] == 0) {
            $this->returnCode(1,'',L_('您当前身份为【店小二】，暂无线下零售权限，请联系管理员！'));
        }

		$table = isset($_POST['table']) ? htmlspecialchars($_POST['table']) : 'shop';
		$change_price_reason = isset($_POST['change_reason']) ? htmlspecialchars($_POST['change_reason']) : 'shop';
		$coupon = isset($_POST['coupon']) ? intval($_POST['coupon']) : 0;
		$uid = isset($_POST['uid']) ? intval($_POST['uid']) : 0;
		$card_id = isset($_POST['card_id']) ? intval($_POST['card_id']) : 0;
		$card_money = isset($_POST['card_money']) ? floatval($_POST['card_money']) : 0;
		$price = isset($_POST['price']) ? floatval($_POST['price']) : 0;
		$table = strtolower($table);
		$table_name = ucfirst($table) . '_order'; 
		$order_id  = intval($_POST['order_id']);
        $lumin_fdump_detail['order_id'] = $order_id;
        $internal_card_pay_money = $_POST['internal_card_pay'];
        fdump_sql($lumin_fdump_detail,"lumin20190417");

        $sys_coupon = isset($_POST['sys_coupon']) ? floatval($_POST['sys_coupon']) : 0; // 平台优惠券id
        $is_use_score = isset($_POST['is_use_score']) ? floatval($_POST['is_use_score']) : 0; // 是否使用积分
			
		$orderid = date("ymdHis").rand(10,99).sprintf("%08d",$this->staff_session['id']);
        $lumin_fdump_detail['orderid'] = $orderid;
        fdump_sql($lumin_fdump_detail,"lumin20190417");
		M($table_name)->where(array('order_id' => $order_id))->data(array('orderid'=>$orderid))->save();	
		
		$now_order = M($table_name)->where(array('order_id' => $order_id))->find();
        $lumin_fdump_detail['now_order'] = $now_order;
        fdump_sql($lumin_fdump_detail,"lumin20190417");
        $now_order['order_name'] = L_('X1线下零售X2',array('X1'=>$this->store['name'],'X2'=>$now_order['name']));
		if (empty($now_order)) $this->returnCode(1000,array(),L_('订单信息错误'));
		if($now_order['paid']==1) $this->returnCode(1009,array(),L_('订单已支付'));

        // 商家优惠(到店付买单优惠)
        $discount_money = isset($_POST['discount_money']) ? floatval($_POST['discount_money']) : 0;
        $no_discount_money = isset($_POST['no_discount_money']) ? floatval($_POST['no_discount_money']) : 0;
        $data['no_discount_money'] = $no_discount_money;//不可优惠金额
        if ($discount_money) {
            $data['merchant_discount_price'] = $discount_money;
            $discount_price = $now_order['price'] - $discount_money;
            $data['price'] = $discount_price;
        }else{
            $discount_price = $now_order['price'];
            $data['price'] = $discount_price;
        }

        fdump_api($_POST['phone'],'store_staff_arrival_pay',true);

        //使用一卡通
        $internal_card_pay = array();
        if ($internal_card_pay_money){

            $store_info = D('Merchant_store')->where(['store_id' => $now_order['store_id']])->find();
            try{
                $cardData = D('InternalMembershipCard')->getInternalCard($store_info, $_POST['phone'], '',['no_check_password' => 1]);
            }catch (\Exception $e){
                $this->returnCodeError($e->getMessage());
            }
            $card_list = $cardData['data']['internal_card_ids'];
            if(empty($card_list)){
                $this->returnCode(1,'',L_('没有一卡通信息！'));
            }
            $total_money = 0;
            foreach($card_list as $card){
                $total_money += $card['balance_money'];
            }
            if ($total_money <$internal_card_pay_money) {
                $this->returnCode(1,'',L_('余额不足！'));
            }else{
                //计算一卡通扣钱
                $money = $internal_card_pay_money;
                foreach($card_list as $card){
                    if ($card['balance_money'] >= $money){
                        $internal_card_pay[$card['card_id']] = $money;
                        $money = 0;
                    }else{
                        $internal_card_pay[$card['card_id']] = $card['balance_money'];
                        $money -= $card['balance_money'];
                    }

                    if ($money == 0){
                        break;
                    }
                }
            }
        }
        $data['internal_card_pay'] = json_encode($internal_card_pay);

		//检查会员卡的信息以及余额
		$discount = 10;
		$data['merchant_balance'] = 0;//商家余额
		$data['card_give_money'] = 0;//会员卡赠送余额
		$data['card_id'] = 0;//优惠券ID
		$data['card_price'] = 0;//优惠券的金额
		$coupon_price = 0;
        $sys_coupon_price = 0;
        $score_deducte_price = 0;
		if ($card = D('Card_userlist')->field(true)->where(array('id' => $card_id, 'uid' => $uid, 'mer_id' => $this->store['mer_id']))->find()) {
            $lumin_fdump_detail['card'] = $card;
            fdump_sql($lumin_fdump_detail,"lumin20190417");
			$card_source = M(_view('Card_new'))->where(array('mer_id' => $this->store['mer_id'], 'status' => 1))->find();
			$discount = floatval($card_source['discount']);
			//0代表不打折
            $discount = $discount > 0 ? $discount : 10;
			$user_card_total_moeny = $card['card_money'] + $card['card_money_give'];
			if ($user_card_total_moeny < $card_money) {
				$this->returnCode(1000,array(),L_('会员卡余额不足'));
			}
			if ($card_money <= $card['card_money']) {
				$data['merchant_balance'] = $card_money;
				$data['card_give_money'] = 0;
			} else {
				$data['merchant_balance'] = $card['card_money'];
				$data['card_give_money'] = $card_money - $card['card_money'];
			}
			
            $discount_price = floatval(round(($discount_price-$no_discount_money) * $discount * 0.1+$no_discount_money, 2));// 折扣后的金额
			$data['price'] = $discount_price;
			if ($user_coupon = M('Card_new_coupon_hadpull')->field(true)->where(array('id' => $coupon, 'is_use' => 0))->find()) {
				if ($new_coupon = M(_view('card_new_coupon'))->field(true)->where(array('coupon_id' => $user_coupon['coupon_id'], 'use_with_card' => 1))->find()) {
					$now_time = time();
					if (($new_coupon['cate_name'] == 'all' || $new_coupon['cate_name'] == 'shop') && $new_coupon['end_time'] > $now_time && $discount_price >= $new_coupon['order_money']) {
						$data['card_id'] = $coupon;
						$coupon_price = $data['card_price'] = $new_coupon['discount'];
					}
				}
			}
			$discount = floatval($card_source['discount']);
		} else {
			$card_money = 0;
            $lumin_fdump_detail['card_money'] = $card_money;
            fdump_sql($lumin_fdump_detail,"lumin20190417");
		}

        // 平台优惠券
        if ($user_coupon = D('System_coupon_hadpull')->field(true)->where(array('id' => $sys_coupon, 'is_use' => 0))->find()) {
            if ($new_coupon = M('System_coupon')->field(true)->where(array('coupon_id' => $user_coupon['coupon_id']))->find()) {
                if (($new_coupon['cate_name'] == 'all' || $new_coupon['cate_name'] == 'shop') && $new_coupon['end_time'] > $now_time && ($discount_price-$coupon_price) >= $new_coupon['order_money']) {
                        //查看使用期限是否到期
                        // 用户可使用的天数
                        if ($new_coupon['valid_time']) {
                            if ($user_coupon['receive_time']<$new_coupon['start_time']) {
                                $end_time = $new_coupon['start_time'] + $new_coupon['valid_time']*86400;
                            }else{
                                $end_time = $user_coupon['receive_time'] + $new_coupon['valid_time']*86400;
                            }
                            if ($end_time>time()) {
                                $data['coupon_id'] = $sys_coupon;
                                $sys_coupon_price = $data['coupon_price'] = $new_coupon['discount'];
                            }
                        }else{
                            $data['coupon_id'] = $sys_coupon;
                            $sys_coupon_price = $data['coupon_price'] = $new_coupon['discount'];
                        }
                }
            }
        }
        $lumin_fdump_detail['user_coupon'] = $user_coupon;
        fdump_sql($lumin_fdump_detail,"lumin20190417");

        // 使用积分
        $score_can_use_count=0;
        $score_deducte=0;
        if ($_POST['is_use_score']) {
            $now_user = D('User')->get_user($now_order['uid']);
             //使用积分
            $user_score_use_percent = (float)$this->config['user_score_use_percent'];

            $now_order['total_money'] = $discount_price - $coupon_price - $sys_coupon_price;
            $user_score_use_condition = $this->config['user_score_use_condition'];
            $order_content = D('Shop_order_detail')->join('as o LEFT JOIN '.C('DB_PREFIX').'shop_goods g ON g.goods_id = o.goods_id')->field('o.*,g.score_max,score_percent')->where(array('order_id' => $order_id))->select();
            $now_order['order_content'] = $order_content ? $order_content :array();

            $user_score_max_use = D('Percent_rate')->get_max_core_use($now_order['mer_id'], 'shop',$now_order['total_money'],$now_order);//不同业务不同积分

            $score_max_deducte = bcdiv($user_score_max_use, $user_score_use_percent, 2);

            if ($user_score_use_percent > 0 && $score_max_deducte > 0 && $user_score_use_condition > 0 ) {   //如果设置没有错误
                $total_ = $now_order['total_money'];
                if ($total_ >= $user_score_use_condition) {             //判断是否够使用积分的条件，即这个订单的总额是否大于规定的金额
                    if ($total_ > $score_max_deducte) {                    //判断积分最大抵扣金额是否比这个订单的总额大

                        $score_can_use_count = (int)($now_user['score_count'] > $user_score_max_use ? $user_score_max_use : $now_user['score_count']);//如果用户积分大于每个订单的积分可使用数 则按可使用数算
                        $score_deducte = bcdiv($score_can_use_count, $user_score_use_percent, 2);
                        $score_deducte = $score_deducte > $total_ ? $total_ : $score_deducte;
                    } else {
                        $score_can_use_count = ceil($total_ * $user_score_use_percent) > (int)$now_user['score_count'] ? (int)$now_user['score_count'] : ceil($total_ * $user_score_use_percent);
                        $score_deducte = bcdiv($score_can_use_count, $user_score_use_percent, 2);
                        $score_deducte = $score_deducte > $total_ ? $total_ : $score_deducte;
                    }
                }
            }
            $data['score_used_count'] = $score_can_use_count;
            $data['score_deducte'] = $score_deducte;
        }
        
        if ($this->staff_session['is_change']) {
            $true_price = floatval(round($discount_price - $coupon_price - $sys_coupon_price, 2));//订单会员卡折扣和优惠券后的钱
            if ($true_price != $price) {
                $data['change_price'] = $discount_price;
                $data['price'] = $price;
                $data['change_price_reason'] = $change_price_reason;//修改价格的理由
            } else {
                $price = $true_price;
            }
            $now_order['price'] = floatval(round($price  - $coupon_price - $sys_coupon_price - $card_money - $score_deducte - $internal_card_pay_money, 2));
        } else {
            $now_order['price'] = floatval(round($discount_price - $card_money - $coupon_price - $sys_coupon_price - $score_deducte - $internal_card_pay_money, 2));
        }

        fdump_api('now_order_price','store_staff_arrival_pay',true);
        fdump_api($now_order['price'],'store_staff_arrival_pay',true);

        fdump_api(($now_order['price'] > 0),'store_staff_arrival_pay',true);

		$data['card_discount'] = $discount;
		$data['last_staff'] = $this->staff_session['name'];
		$lumin_fdump_detail['now_order_second'] = $now_order;
        fdump_sql($lumin_fdump_detail,"lumin20190417");
		if ($now_order['price'] > 0) {
            if($_POST['auth_type'] == 'balance'){
                $data['smscode'] = $_POST['auth_code'];
                $now_user = D('User')->get_user($_POST['phone'],'phone');
                $data['uid'] = $now_user['uid'];
                if ($now_user['now_money']<$now_order['price']) {
                    $this->returnCode(1,'',L_('余额不足！'));
                }
                $this->arrival_balance_pay($order_id, $now_order, $table_name, $data);
                die;
            }

			$offline_pay = isset($_POST['offline_pay']) ? intval($_POST['offline_pay']) : -1;
			if($offline_pay >= 0){
				$this->arrival_offline_pay($order_id, $now_order, $table_name, $data);
				die;
			}

            if($_POST['auth_type'] == 'yzfpay'){
                $this->arrival_yzfpay_pay($order_id, $now_order, $table_name, $data);
                die;
            }
			
			
			import('ORG.Net.Http');
			$http = new Http();
			
			$is_own = 0;
			
            $now_merchant = D('Merchant')->get_info($now_order['mer_id']);

			//开通威富通低费率支付
			if ($this->config['open_wftpay'] == 1) {
				$lowfeepay = new LowFeePay('wftpay');
				$order_info['re_call'] = $_POST['re_call'];
				$order_info['auth_code'] = $_POST['auth_code'];
				$order_info['is_own'] = 0;
				$order_info['mer_id'] = $now_order['mer_id'];
				$order_info['out_trade_no'] = $table . '_' . $now_order['order_id'];
				$order_info['total_fee'] = $now_order['price'];
				$order_info['order_name'] = $this->store['name'].' - 线下零售';
				$return = $lowfeepay->scan_pay($order_info);
                if ($return['return_code'] == 'FAIL') {
                    $this->returnCode(1000,array(),L_('支付失败！微信返回：'). $return['return_msg']);
                }else if ($return['result_code'] == 'FAIL') {
                    if ($return['err_code'] == 'USERPAYING') {
                        $_SESSION[$session_key] = '1';
                        $this->returnCode(1000,array(),L_('用户支付中，需要输入密码！请询问用户输入完成后，再点击“确认支付”'));
                    }
                    if($return['err_code']=='ORDERPAID'){
                        $param = array();
                        $param['appid'] = $this->config['pay_weixin_appid'];
                        $param['mch_id'] = $this->config['pay_weixin_mchid'];
                        $param['nonce_str'] = $this->createNoncestr();
                        $param['out_trade_no'] = $table . '_' . $now_order['order_id'];
                        if($sub_mch_pay){
                            $param['out_trade_no'] = 'store_'.$now_order['order_id'].'_1';
                            $param['sub_mch_id'] = $sub_mch_id;
                        }
                        $param['sign'] = $this->getWxSign($param);
                        $return = Http::curlPostXml('https://api.mch.weixin.qq.com/pay/orderquery', $this->arrayToXml($param));
                        $lumin_fdump_detail['return_second'] = $return;
                        fdump_sql($lumin_fdump_detail,"lumin20190417");
                    }else{
                        $this->returnCode(1000,array(),L_('支付失败！微信返回：').$return['err_code_des']);
                    }
                }else if (isset($return['trade_state']) && $return['trade_state'] == 'USERPAYING') {
                    $session_key && $_SESSION[$session_key] = '1';
                    $this->returnCode(20130035,array(),L_('用户支付中，需要输入密码！请询问用户输入完成后，再点击“确认支付”'));
                }else if (isset($return['trade_state']) && $return['trade_state'] != 'SUCCESS') {
                    fdump_sql(array('return'=>$return,'now_order'=>$now_order),'storestaff_arrival_pay_error');
                    
                    $this->error(L_('支付时发生错误，请联系技术处理。'));
                }
                
                if($session_key){
                    unset($_SESSION[$session_key]);
                }
                
                $data['third_id'] = $return['transaction_id'];
                $data['payment_money'] = $return['total_fee']/100;
                $data['pay_type'] = $return['pay_type'] ? $return['pay_type'] : 'weixin';
    		}elseif($this->config['open_weikebaba'] == 1){
                $lowfeepay = new LowFeePay('weikebaba');
                $order_info['re_call'] = $_POST['re_call'];
                $order_info['auth_code'] = $_POST['auth_code'];
                $order_info['is_own'] = 0;
                $order_info['mer_id'] = $now_order['mer_id'];
                $order_info['out_trade_no'] = $table . '_' . $now_order['order_id'];
                $order_info['total_fee'] = $now_order['price'];
                $order_info['order_name'] = $this->store['name'].' - 线下零售';
                $order_info['pay_type'] = $_POST['auth_type']=='alipay' ? 2 : 1;
                $return = $lowfeepay->scan_pay($order_info);
                if ($return['return_code'] == 'FAIL') {
                    $this->returnCode(1000,array(),L_('支付失败！微信返回：'). $return['return_msg']);
                }else if ($return['result_code'] == 'FAIL') {
                    if ($return['err_code'] == 'USERPAYING') {
                        $_SESSION[$session_key] = '1';
                        $this->returnCode(1000,array(),L_('用户支付中，需要输入密码！请询问用户输入完成后，再点击“确认支付”'));
                    }
                }else if (isset($return['trade_state']) && $return['trade_state'] == 'USERPAYING') {
                    $session_key && $_SESSION[$session_key] = '1';
                    $this->returnCode(20130035,array(),L_('用户支付中，需要输入密码！请询问用户输入完成后，再点击“确认支付”'));
                }else if (isset($return['trade_state']) && $return['trade_state'] != 'SUCCESS') {
                    fdump_sql(array('return'=>$return,'now_order'=>$now_order),'storestaff_arrival_pay_error');
                    
                    $this->error(L_('支付时发生错误，请联系技术处理。'));
                }
                
                if($session_key){
                    unset($_SESSION[$session_key]);
                }
                
                $data['third_id'] = $return['transaction_id'];
                $data['payment_money'] = $return['total_fee']/100;
                $data['pay_type'] = $return['pay_type'] ? $return['pay_type'] : 'weixin';
            }elseif($this->config['pay_scrcu_cash_open'] == 1 && $this->config['scrcu_mid']){
	        	// 四川农信银行支付
                if($now_merchant['scrcu_mer_no']){
                    $is_own = 1;
                }
	        	import("@.ORG.pay.Scrcu");
	            $pay_moodel = new Scrcu();
	            if($_POST['re_call']){
		            $return = $pay_moodel->scanQuery($now_order['order_id'], $now_merchant['scrcu_mer_no']);
		            if ($return['error']) {// 正在输入密码
						$this->returnCode('20130035');
		            }
				}else{
					// 付款
		            $return = $pay_moodel->scanPay($_POST['order_type'], $now_order['order_id'], $now_order['price'], $_POST['auth_code'], $now_merchant['scrcu_mer_no'] );
		            if ($return['error']) {
		                $this->returnCodeError(L_('支付失败！支付返回：') . $return['msg']);
		            }

		            sleep(1);
		            // 查询
		            $return = $pay_moodel->scanQuery($now_order['order_id'], $now_merchant['scrcu_mer_no']); 
		            if ($return['error']) {// 正在输入密码
						$this->returnCode('20130035');
		            }

				}
	            $data['pay_type'] = $return['pay_type'] ? $return['pay_type'] : 'weixin';
	        }else{
				if($_POST['auth_type'] == 'alipay'){
					$this->arrival_alipay_pay($order_id, $now_order, $table_name, $data);
					die;
				}

                $lumin_fdump_detail['now_merchant'] = $now_merchant;
                fdump_sql($lumin_fdump_detail,"lumin20190417");
				$sub_mch_pay = false;

				if($this->config['city_ownpay']){

					$merchant_ownpay = D('City_ownpay')->field('city_id',true)->where(array('city_id'=>$now_merchant['city_id']))->find();
					foreach($merchant_ownpay as $ownKey=>$ownValue){
						$ownValueArr = unserialize($ownValue);
						if($ownValueArr['open']){
							if(strpos($ownKey,'app')){
								continue;
							}
							if($ownKey=='weixin'){
								$ownValueArr['is_own'] = 6;
							}else{
								$ownValueArr['is_own'] = true;
							}
							$pay_method[$ownKey] = array('name'=>$this->getPayName($ownKey),'config'=>$ownValueArr);
						}
					}

					$this->config['pay_weixin_mchid'] = $this->config['pay_weixin_sp_mchid'];
					$this->config['pay_weixin_key'] = $this->config['pay_weixin_sp_key'];
					$sub_mch_pay = true;
					$sub_mch_id = $pay_method['weixin']['sub_mch_id'];
					$is_own = 6 ;
				}
				if ($this->config['open_sub_mchid'] && $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0) {

					$this->config['pay_weixin_mchid'] = $this->config['pay_weixin_sp_mchid'];
					$this->config['pay_weixin_key'] = $this->config['pay_weixin_sp_key'];
					$sub_mch_pay = true;
					$sub_mch_id = $now_merchant['sub_mch_id'];
					$is_own = 2 ;
				}

				$param = array();
                $param['appid'] = $this->config['pay_weixin_appid'];
                $param['mch_id'] = $this->config['pay_weixin_mchid'];
                $param['nonce_str'] = $this->createNoncestr();
                $param['auth_code'] = $_POST['auth_code'];
                $param['sign'] = $this->getWxSign($param);
                $return_openid_from_auth_code = Http::curlPostXml('https://api.mch.weixin.qq.com/tools/authcodetoopenid', $this->arrayToXml($param));
                if($return_openid_from_auth_code['return_code']=='SUCCESS' && $return_openid_from_auth_code['result_code'] =='SUCCESS'){
                    $tmp_user_openid = $return_openid_from_auth_code['openid'];
                }
                $now_user = D('User')->get_user($tmp_user_openid,'openid');
                $now_order['order_type'] = 'shop';
        
                if(empty($now_user)){
                    $access_token_array = D('Access_token_expires')->get_access_token();
                    if (!$access_token_array['errcode']) {
                        $return_user = $http->curlGet('https://api.weixin.qq.com/cgi-bin/user/info?access_token='.$access_token_array['access_token'].'&openid='.$return['openid'].'&lang=zh_CN');
                        $userifo = json_decode($return_user,true);
            
                        //商家推广绑定关系
                        D('Merchant_spread')->spread_add($now_order['mer_id'],  $userifo['openid'],'storepay');
                        $data_user = array(
                            'openid'    => $userifo['openid'],
                            'union_id'  => ($userifo['unionid'] ? $userifo['unionid'] : ''),
                            'nickname'  => $userifo['nickname'],
                            'sex'       => $userifo['sex'],
                            'province'  => $userifo['province'],
                            'city'      => $userifo['city'],
                            'avatar'    => $userifo['headimgurl'],
                            'is_follow' => $userifo['subscribe'],
                            'source'    => 'app_staff_pay_auto2',
                        );
                        $reg_result = D('User')->autoreg($data_user);
                        if($reg_result['error_code']){
                            $now_user['uid'] = '0';
                        }else{
                            $now_user = D('User')->get_user($userifo['openid'],'openid');
                            $uid = $now_user['uid'];
                        }
                    }
                } else {
                    $uid = $now_user['uid'];
                    if($this->config['san_pay_score_coupon']==1 && $now_merchant['san_pay_score_coupon']) {
                        $check_result = D('Shop_order')->check_coupon_score($now_user, $now_order);
                        $now_order['score_used_count'] = $check_result['score_used_count'];
                        $now_order['score_deducte'] = $check_result['score_deducte'];
                        $now_order['coupon_id'] = $check_result['coupon_id'];
                        $now_order['coupon_price'] = $check_result['coupon_price'];

                        //判断平台优惠券
                        if ($now_order['coupon_id']) {
                            $now_coupon = D('System_coupon')->get_coupon_by_id($now_order['coupon_id']);
                            if (empty($now_coupon)) {
                                $this->error(L_('支付异常：系统卡券错误'));
                            }
                        }

                        $score_used_count = $now_order['score_used_count'];
                        if ($now_user['score_count'] < $score_used_count) {
                            $this->error(L_('支付异常：积分异常'));
                        }
                        $now_order['price'] = $check_result['total_money'];

                        $data['coupon_id'] = $check_result['coupon_id'];
                        $data['coupon_price'] = $check_result['coupon_price'];
                        $data['score_used_count'] = $check_result['score_used_count'];
                        $data['score_deducte'] = $check_result['score_deducte'];
                    }
                }

                if($now_order['price']>0) {
					
                    $session_key = $table . '_order_userpaying_'.$order_id;
                    if($_POST['re_call'] || $_SESSION[$session_key]){
                        $param = array();
                        $param['appid'] = $this->config['pay_weixin_appid'];
                        $param['mch_id'] = $this->config['pay_weixin_mchid'];
                        $param['nonce_str'] = $this->createNoncestr();
                        $param['out_trade_no'] = $table . '_' . $now_order['order_id'];
                        if($sub_mch_pay){
                            $param['out_trade_no'] = 'store_'.$now_order['order_id'].'_1';
                            $param['sub_mch_id'] = $sub_mch_id;
                        }
                        $param['sign'] = $this->getWxSign($param);
                        $return = Http::curlPostXml('https://api.mch.weixin.qq.com/pay/orderquery', $this->arrayToXml($param));
                    } else {
                        $param = array();
                        $param['appid'] = $this->config['pay_weixin_appid'];
                        $param['mch_id'] = $this->config['pay_weixin_mchid'];
                        $param['nonce_str'] = $this->createNoncestr();
                        $param['body'] = $now_order['real_orderid'];
                        $param['out_trade_no'] = $table . '_' . $now_order['order_id'];
                        $param['total_fee'] = floatval($now_order['price']*100);
                        $param['spbill_create_ip'] = get_client_ip();
                        $param['auth_code'] = $_POST['auth_code'];
                        if($sub_mch_pay){
                            $param['out_trade_no'] = 'store_'.$now_order['order_id'].'_1';
                            $param['sub_mch_id'] = $sub_mch_id;
                        }
                        $param['sign'] = $this->getWxSign($param);
                
                        $return = Http::curlPostXml('https://api.mch.weixin.qq.com/pay/micropay', $this->arrayToXml($param));
                    }
                    if ($return['return_code'] == 'FAIL') {
                        $this->returnCode(1000,array(),L_('支付失败！微信返回：'). $return['return_msg']);
                    }else if ($return['result_code'] == 'FAIL') {
                        if ($return['err_code'] == 'USERPAYING') {
                            $_SESSION[$session_key] = '1';
                            $this->returnCode(20130035,array(),L_('用户支付中，需要输入密码！请询问用户输入完成后，再点击“确认支付”'));
                        }
                        if($return['err_code']=='ORDERPAID'){
                            $param = array();
                            $param['appid'] = $this->config['pay_weixin_appid'];
                            $param['mch_id'] = $this->config['pay_weixin_mchid'];
                            $param['nonce_str'] = $this->createNoncestr();
                            $param['out_trade_no'] = $table . '_' . $now_order['order_id'];
                            if($sub_mch_pay){
                                $param['out_trade_no'] = 'store_'.$now_order['order_id'].'_1';
                                $param['sub_mch_id'] = $sub_mch_id;
                            }
                            $param['sign'] = $this->getWxSign($param);
                            $return = Http::curlPostXml('https://api.mch.weixin.qq.com/pay/orderquery', $this->arrayToXml($param));
                        }else{
                            $this->returnCode(1000,array(),L_('支付失败！微信返回：').$return['err_code_des']);
                        }
                    }else if (isset($return['trade_state']) && $return['trade_state'] == 'USERPAYING') {
                        $session_key && $_SESSION[$session_key] = '1';
                        $this->returnCode(20130035,array(),L_('用户支付中，需要输入密码！请询问用户输入完成后，再点击“确认支付”'));
                    }else if (isset($return['trade_state']) && $return['trade_state'] != 'SUCCESS') {
                        fdump_sql(array('return'=>$return,'now_order'=>$now_order),'storestaff_arrival_pay_error');
                        
                        $this->error(L_('支付时发生错误，请联系技术处理。'));
                    }
                    
                    if($session_key){
                        unset($_SESSION[$session_key]);
                    }
                    
                    $data['third_id'] = $return['transaction_id'];
                    $data['payment_money'] = $return['total_fee']/100;
                    $data['pay_type'] = $return['pay_type'] ? $return['pay_type'] : 'weixin';
                }
			}
			
		}
		
		
		$data['uid'] = $uid;
		$data['paid'] = 1;
		$data['status'] = 2;
		$data['is_own'] = isset($return['is_own']) ? $return['is_own'] : $is_own;
		$data['order_status'] = 5;
		$data['pay_time'] = time();
		$data['use_time'] = time();

		if(M($table_name)->where(array('order_id' => $order_id))->data($data)->save()){

            fdump_api("保存数据成功",'store_staff_arrival_pay',true);

			$now_order = M($table_name)->where(array('order_id'=>$order_id))->find();

            fdump_api($now_order,'store_staff_arrival_pay',true);

			$this->shop_notice($now_order, true);
            D('Shop_order')->shop_score_get($now_order); // 获得积分
			$this->returnCode(0,L_('支付成功'));
		}else{
			$this->returnCode(1000,array(),L_('支付失败！请联系管理员处理。'));
		}
	}
	public function arrival_offline_pay($order_id, $now_order, $table_name, $data = array())
	{
		$data['paid'] = 1;
		$data['status'] = 2;
		$data['order_status'] = 5;
		$data['pay_time'] = $_SERVER['REQUEST_TIME'];
		$data['use_time'] = time();
		$data['pay_type'] = 'offline';
		$data['offline_pay'] = $_POST['offline_pay'];
		$data['third_id'] = $order_id;
		if(M($table_name)->where(array('order_id' => $order_id))->data($data)->save()){
			$now_order = M($table_name)->where(array('order_id' => $order_id))->find();
			$this->shop_notice($now_order, true);
            D('Shop_order')->shop_score_get($now_order); // 获得积分
			$this->returnCode(0,L_('支付成功'));
		}else{
			$this->returnCode(1000,array(),L_('支付失败！请联系管理员处理。'));
		}
	}

    public function arrival_balance_pay($order_id, $now_order, $table_name, $data = array())
    {
        if (!$this->config['open_balance_pay_unverify']) { // 是否开启线下零售、到店付余额支付不使用短信验证码
            $laste_sms = M('Merchant_sms_record')->where(array('mer_id' => $this->store['mer_id'],'extra'=>$data['smscode'],'type'=>5,'status'=>0))->setField('status',1);
            if (!$laste_sms) {
                $this->returnCode(1,'',L_('短信验证失败！'));
            }
        }
        if($now_order['price']>0){
            $res = D('User')->user_money($data['uid'],$now_order['price'],L_('线下零售使用平台余额'));
            if(!$res['error_code']){
                $data['paid'] = 1;
                $data['status'] = 2;
                $data['order_status'] = 5;
                $data['pay_time'] = time();
                $data['use_time'] = time();
                $data['balance_pay'] = $now_order['price'];
                if (M($table_name)->where(array('order_id' => $order_id))->data($data)->save()) {
                    $now_order = M($table_name)->where(array('order_id' => $order_id))->find();
                    $this->shop_notice($now_order, true);
                    D('Shop_order')->shop_score_get($now_order); // 获得积分
                    $this->returnCode(0);
                }
            }else {
                $this->returnCode(1,'',$res['msg']);
            }
        }
    }

    public function store_arrival_pufa_pay()
    {
        $ticket = I('ticket', false);
        if ($ticket) {
            $info = ticket::get($ticket, $_POST['device_id'], true);
            if ($info) {
                $this->staff_session = M('Merchant_store_staff')->field(true)->where(array('id'=>$info['uid']))->find();
            }
        }else{
            $this->returnCode('20044013');
        }

        $now_order = M("Store_order")->where(array('orderid'=>$_POST['order_id']))->find();
        if($_POST['order_status']=='00'){
            $data['paid'] = 1;

            $data['pay_time'] = strtotime($_POST['pay_time']);
            $data['pay_type'] = 'offline';

            $data['third_id']  = $_POST['third_id'];
            $data['payment_money']  = floatval($_POST['pay_money'])/100;
            if(M('Store_order')->where(array('order_id' => $now_order['order_id']))->data($data)->save()){

                $this->returnCode(0,L_('支付成功'));
            }else{
                $this->returnCode(1,array(),L_('支付失败！请联系管理员处理。'));
            }
        }else{
            $this->returnCode(1,array(),L_('支付失败！请联系管理员处理。'));
        }

    }

    public function shop_arrival_pufa_pay()
    {
        $ticket = I('ticket', false);
        if ($ticket) {
            $info = ticket::get($ticket, $_POST['device_id'], true);
            if ($info) {
                $this->staff_session = M('Merchant_store_staff')->field(true)->where(array('id'=>$info['uid']))->find();
            }
        }else{
            $this->returnCode('20044013');
        }

        $now_order = M("Store_order")->where(array('orderid'=>$_POST['order_id']))->find();
        if($_POST['order_status']=='00'){
            $data['paid'] = 1;

            $data['pay_time'] = strtotime($_POST['pay_time']);
            $data['pay_type'] = 'pufapay';

            $data['third_id']  = $_POST['third_id'];
            $data['payment_money']  = floatval($_POST['pay_money'])/100;
            if(M('Store_order')->where(array('order_id' => $now_order['order_id']))->data($data)->save()){

                $this->returnCode(0,L_('支付成功'));
            }else{
                $this->returnCode(1,array(),L_('支付失败！请联系管理员处理。'));
            }
        }else{
            $this->returnCode(1,array(),L_('支付失败！请联系管理员处理。'));
        }

    }



    public function arrival_alipay_face_to_face($order_id, $now_order,$auth_token){
        import('@.ORG.pay.Aop.AopClient');
        import('@.ORG.pay.Aop.SignData');
        import('@.ORG.pay.Aop.AlipayTradePayRequest');
        $aop = new AopClient ();
        $aop->gatewayUrl = 'https://openapi.alipay.com/gateway.do';
        $aop->appId = $this->config['pay_alipay_sp_appid'];
        $aop->rsaPrivateKey = $this->config['pay_alipay_sp_prikey'];
        $aop->alipayrsaPublicKey=$this->config['pay_alipay_sp_pubkey'];
        $aop->apiVersion = '1.0';
        $aop->signType = 'RSA2';
        $aop->postCharset='UTF-8';
        $aop->format='json';
        $request = new AlipayTradePayRequest();
        $biz_content = array(
            'out_trade_no' => 'shop_'.$now_order['real_orderid'],
            'scene' => 'bar_code',
            'auth_code' => $_POST['auth_code'],
            'product_code' => 'FACE_TO_FACE_PAYMENT',
            'total_amount' => $now_order['price'],
            'subject' => $now_order['name'] ? $now_order['name'] : L_('现场自助支付'),
            'extend_params' => array(
                'sys_service_provider_id'=>$this->config['pay_alipay_pid'],
            ),
        );

        $now_merchant = D('Merchant')->get_info($this->store['mer_id']);
        $request->setBizContent(json_encode($biz_content,JSON_UNESCAPED_UNICODE));
        $result = $aop->execute ( $request,$_POST['auth_code'],$now_merchant['alipay_auth_code']);

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if(!empty($resultCode)&&$resultCode == 10000){
            $data['paid'] = 1;
            $data['status'] = 2;
            $data['order_status'] = 5;
            $data['pay_time'] = strtotime($result->$responseNode->gmt_payment);
            $data['use_time'] = time();
            $data['pay_type'] = 'alipay';
            $data['third_id'] =  $result->$responseNode->trade_no;
            $data['payment_money'] =$result->$responseNode->receipt_amount;
            if (M('Shop_order')->where(array('order_id' => $order_id))->data($data)->save()) {
                $now_order = M('Shop_order')->where(array('order_id' => $order_id))->find();
                if($auth_token){
                    $now_order['payment_money']=0;
                }
                $this->shop_notice($now_order, true);


                $this->returnCode(0,L_('支付成功'));
            } else {
                $this->returnCode(1000,array(),L_('支付失败！请联系管理员处理。'));
            }
        } elseif($resultCode == 10003) {
            $this->returnCode("20130045");
        }else{
            if($result->$responseNode->sub_code=='ACQ.TRADE_HAS_SUCCESS'){

                $data['paid'] = 1;
                $data['status'] = 2;
                $data['order_status'] = 5;
                $data['pay_time'] = strtotime($result->$responseNode->gmt_payment);
                $data['use_time'] = time();
                $data['pay_type'] = 'alipay';
                $data['third_id'] =  $result->$responseNode->trade_no;
                $data['payment_money'] = $result->$responseNode->receipt_amount;
                if (M('Shop_order')->where(array('order_id' => $order_id))->data($data)->save()) {
                    $now_order = M('Shop_order')->where(array('order_id' => $order_id))->find();
                    if($auth_token){
                        $now_order['payment_money']=0;
                    }
                    $this->shop_notice($now_order, true);
                    $this->returnCode(0,'支付成功');
                } else {
                    $this->returnCode(1000,array(),'支付失败！请联系管理员处理。');
                }
            }else{
                $this->returnCode(1000,array(),L_('支付失败！支付宝返回：').$result->$responseNode->sub_msg);
            }
        }
    }
	
	
	public function arrival_alipay_pay($order_id, $now_order, $table_name, $data = array())
	{
        $now_merchant = D('Merchant')->get_info($this->store['mer_id']);
        if($now_merchant['open_sub_mchid']==1 && $this->config['pay_alipay_sp_appid']!=''&& $this->config['pay_alipay_sp_prikey']!='' && $now_merchant['alipay_auth_code']!=''){
            $this->arrival_alipay_face_to_face($order_id,$now_order,$now_merchant['alipay_auth_code']);
            die;
        }
		if (empty($this->config['arrival_alipay_open'])) {
			$this->returnCode(1000,array(),L_('平台未开启支付宝收银'));
		}
		$param['app_id'] = $this->config['arrival_alipay_app_id'];
		$param['method'] = 'alipay.trade.pay';
		$param['charset'] = 'utf-8';
		$param['sign_type'] = $this->config['alipay_arrival_encrypt_type'] ? $this->config['alipay_arrival_encrypt_type'] : 'RSA';
		$param['timestamp'] = date('Y-m-d H:i:s');
		$param['version'] = '1.0';
		$biz_content = array(
                'out_trade_no' => 'shop_'.$now_order['real_orderid'],
				'scene' => 'bar_code',
				'auth_code' => $_POST['auth_code'],
				'total_amount' => $now_order['price'],
				'subject' => $now_order['real_orderid'],
		);
		$param['biz_content'] = json_encode($biz_content,JSON_UNESCAPED_UNICODE);
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
	
		$priKey = $this->config['arrival_alipay_app_prikey'];;
		$res = "-----BEGIN RSA PRIVATE KEY-----\n".wordwrap($priKey, 64, "\n", true)."\n-----END RSA PRIVATE KEY-----";
		
		if($param['sign_type'] == 'RSA2'){
			openssl_sign($stringToBeSigned, $sign, $res, OPENSSL_ALGO_SHA256);
		}else{
			openssl_sign($stringToBeSigned, $sign, $res);
		}
		
		if (empty($sign)) {
			$this->returnCode(1000,array(),L_('支付宝收银商户密钥错误，请联系管理员解决。'));
		}
	
		$sign = base64_encode($sign);
	
		$param['sign'] = $sign;
		$requestUrl = "https://openapi.alipay.com/gateway.do?";
		foreach ($param as $sysParamKey => $sysParamValue) {
			$requestUrl .= "$sysParamKey=" . urlencode($sysParamValue) . "&";
		}
		$requestUrl = substr($requestUrl, 0, -1);
		// echo $requestUrl;die;
		import('ORG.Net.Http');
		$http = new Http();
	
		$return = Http::curlGet($requestUrl);
		$returnArr = json_decode($return,true);
		if (!empty($returnArr['alipay_trade_pay_response']) && "10000" == $returnArr['alipay_trade_pay_response']['code']) {
			$now_user = D('User')->get_user($returnArr['alipay_trade_pay_response']['buyer_user_id'],'alipay_uid'); 
			if($now_user){
				$data['uid'] = $now_user['uid'];
			}
			$data['paid'] = 1;
			$data['status'] = 2;
			$data['order_status'] = 5;
			$data['pay_time'] = strtotime($returnArr['alipay_trade_pay_response']['gmt_payment']);
			$data['use_time'] = time();
			$data['pay_type'] = 'alipay';
			$data['third_id'] = $returnArr['alipay_trade_pay_response']['trade_no'];
			$data['payment_money'] = $returnArr['alipay_trade_pay_response']['receipt_amount'];
			if (M($table_name)->where(array('order_id'=>$order_id))->data($data)->save()) {
				$now_order = M($table_name)->where(array('order_id' => $order_id))->find();
				$this->shop_notice($now_order, true);
                D('Shop_order')->shop_score_get($now_order); // 获得积分
				$this->returnCode(0,L_(L_('支付成功')));
			} else {
				$this->returnCode(1000,array(),L_(L_('支付失败！请联系管理员处理。')));
			}
		} elseif (!empty($returnArr['alipay_trade_pay_response']) && "10003" == $returnArr['alipay_trade_pay_response']['code']) {	//需要用户处理，下次查询订单
	
		} else {
			if ($returnArr['alipay_trade_pay_response']['sub_code'] == 'ACQ.TRADE_HAS_SUCCESS' && $now_order['paid'] != 1) {
				$now_user = D('User')->get_user($returnArr['alipay_trade_pay_response']['buyer_user_id'],'alipay_uid'); 
				if($now_user){
					$data['uid'] = $now_user['uid'];
				}
				$data['paid'] = 1;
				$data['status'] = 2;
				$data['order_status'] = 5;
				$data['pay_time'] = strtotime($returnArr['alipay_trade_pay_response']['gmt_payment']);
				$data['use_time'] = time();
				$data['pay_type'] = 'alipay';
				$data['third_id'] = $returnArr['alipay_trade_pay_response']['trade_no'];
				$data['payment_money'] = $returnArr['alipay_trade_pay_response']['receipt_amount'];
				if (M($table_name)->where(array('order_id' => $order_id))->data($data)->save()) {
					$now_order = M($table_name)->where(array('order_id' => $order_id))->find();
					$this->shop_notice($now_order);
                    D('Shop_order')->shop_score_get($now_order); // 获得积分
					$this->returnCode(0,'支付成功');
				} else {
					$this->returnCode(1000,array(),'支付失败！请联系管理员处理。');
				}
			} else {
				$this->returnCode(1000,array(),L_('支付失败！支付宝返回：').$returnArr['alipay_trade_pay_response']['sub_msg']);
			}
		}
	}
	
    
    public function appointList()
    {
        $key = isset($_POST['key']) ? htmlspecialchars($_POST['key']) : '';
        $searchType = isset($_POST['searchtype']) ? htmlspecialchars($_POST['searchtype']) : '';
        $stime = isset($_POST['stime']) ? htmlspecialchars($_POST['stime']) : '';
        $etime = isset($_POST['etime']) ? htmlspecialchars($_POST['etime']) : '';
        $payType = isset($_POST['pay_type']) && $_POST['pay_type'] ? htmlspecialchars($_POST['pay_type']) : '';
        
        $where = " `o`.`store_id`={$this->store['store_id']}";
        if ($key) {
            switch ($searchType) {
                case 'order_id':
                    $where .= " AND `o`.`order_id`={$key}";
                    break;
                case 'orderid':
                    $tmp_result = M('Tmp_orderid')->where(array('orderid'=>$where['orderid']))->find();
                    $where .= " AND `o`.`order_id`={$tmp_result['order_id']}";
                    break;
                case 'name':
                    $where .= " AND `u`.`nickname` LIKE '%{$key}%'";
                    break;
                case 'phone':
                    $where .= " AND `u`.`phone` LIKE '%{$key}%'";
                    break;
                case 'third_id':
                    $where .= " AND `o`.`third_id`='{$key}'";
                    break;
                case 'real_orderid':
                    $where .= " AND `o`.`real_orderid`='{$key}'";
                    break;
            }
        }

        if ($payType) {
            if ($payType == 'balance') {
                $where .= " AND (`o`.`balance_pay`<>0 OR `o`.`merchant_balance` <> 0 OR `o`.`product_merchant_balance` <> 0 OR `o`.`product_balance_pay` <> 0 )";
            } else {
                $where .= " AND `o`.`pay_type`='{$payType}'";
            }
        }

        if (isset($_POST['service_status']) && $_POST['service_status'] !=-1) {           
            $where .= " AND `o`.`service_status`='{$_POST['service_status']}'";            
        }


        // 支付状态
        $paid = isset($_POST['paid']) && $_POST['paid'] ? $_POST['paid'] : -1;
        if ($paid == -10) {
            $where .= " AND (`o`.`paid`=1 AND `o`.`service_status`<=0)";
        } elseif ($paid == 1) {
            $where .= " AND ((`o`.`paid`=1 AND `o`.`payment_status`!=1) OR (`o`.`paid`=1 AND `o`.`service_status`>0))";
        } elseif ($paid==3)  {
            $where .= " AND ((`o`.`paid`=3) OR (`o`.`is_del`>0))";
        } elseif ($paid==5)  {
            $where .= " AND ((`o`.`paid`=1) AND (`o`.`is_apply_refund` = 1))";
        } elseif ($paid==-2)  {
            $where .= " AND (`o`.`paid`=0)";
        } elseif ($paid>=0)  {
            $where .= " AND (`o`.`paid`='{$paid}')";
        }
        
        if ($stime && $etime) {
            $where .= ' AND `o`.order_time >=' . strtotime($stime . ' 00:00:00') . ' AND `o`.order_time <=' . strtotime($etime . ' 23:59:59');
        }
        
        $sqlCount = " SELECT count(1) as cnt FROM " . C('DB_PREFIX') . "appoint AS a RIGHT JOIN " . C('DB_PREFIX') . "appoint_order AS o ON o.appoint_id=a.appoint_id LEFT JOIN " . C('DB_PREFIX') . "user AS u ON o.uid=u.uid WHERE {$where}";
        
        $res = D()->query($sqlCount);
       
        $count = isset($res[0]['cnt']) ? intval($res[0]['cnt']) : 0;
        
        import('@.ORG.merchant_page');
        $_GET['page'] = $_POST['page'];
        $page = new Page($count, 10);
        
        $sql = " SELECT `o`.*, `a`.`appoint_name`, `o`.`mer_id`, `u`.`uid`, `u`.`nickname`, `u`.`phone`  FROM " . C('DB_PREFIX') . "appoint AS a RIGHT JOIN " . C('DB_PREFIX') . "appoint_order AS o ON o.appoint_id=a.appoint_id LEFT JOIN " . C('DB_PREFIX') . "user AS u ON o.uid=u.uid WHERE {$where} ORDER BY `o`.`order_id` DESC LIMIT {$page->firstRow}, {$page->listRows}";
        
        $orderList = D()->query($sql);
        foreach ($orderList as &$val) {
            $val['store_name'] = $this->store['name'];
            $val['store_adress'] = $this->store['adress'];
            $val['order_time'] = date('Y-m-d H:i:s', $val['order_time']);
            $val['appoint_worker_name'] = $this->config['appoint_worker_name']?$this->config['appoint_worker_name']:L_('技师');

            /**
             * 订单状态 appoint_order_status
             * paid 支付状态(0,未支付；1,已支付；2,已退款  3,用户取消
             * is_apply_refund 是否申请退货(0:未申请，1:申请)
             * confirm_status 店员确定状态：0=待确定,1=已确定,2已拒单,3=已退款
             * appoint_order_status  0 未支付  1已支付  2 已退款 3 用户取消 4 用户申请退款 5 已拒单
             */
            $val['appoint_order_status'] = intval($val['paid']);
            if($val['is_apply_refund'] == 1){
                $val['appoint_order_status'] = 4;
            }
            if($val['confirm_status'] == 2){
                $val['appoint_order_status'] = 5;
            }
        }
        $payList = D('Config')->get_pay_method('', '', 0);
        $payList['balance'] = array('name' => L_('余额支付'));
        $arr = array('order_list' => $orderList, 'pay_list' => $payList);
        $arr['page'] = ceil($count / 10);;
        $arr['count'] = $count;
        $this->returnCode(0, $arr);
    }
    


    /*验证预约服务*/
    public function appointVerify()
    {
        $database_order = D('Appoint_order');
        $database_appoint_visit_order_info = D('Appoint_visit_order_info');
        $database_merchant_workers = D('Merchant_workers');
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        
        $where = array('store_id' => $this->store['store_id']);
        $where['order_id'] = $order_id;
        $now_order = $database_order->field(true)->where($where)->find();

        if(empty($now_order)){
            $this->returnCode(1, null, L_('当前订单不存在！'));
        }elseif (!$now_order['payment_status'] && $now_order['paid']!=1){
            $this->returnCode('20130019');
        }  elseif ((($now_order['paid'] == 1) && ($now_order['service_status'] == 0)) || ($now_order['payment_status'] == 0&& $now_order['service_status'] == 0)) {
            $fields['store_id'] = $this->staff_session['store_id'];
            $fields['last_staff'] = $this->staff_session['name'];
            $fields['last_time'] = time();
            $fields['service_status'] = 1;
            $fields['paid'] = 1;
            if($database_order->where($where)->data($fields)->save()){
                $Map['appoint_order_id'] =  $order_id;
                $appoint_visit_order_info = $database_appoint_visit_order_info->where($Map)->find();
    
                $worker_where['merchant_worker_id'] = $appoint_visit_order_info['merchant_worker_id'];
                $pay_money_count = $database_appoint_visit_order_info->order_appoint_price_sum($worker_where);
                $database_merchant_workers->where($worker_where)->where($worker_where)->setField('appoint_price',$pay_money_count);
                $database_merchant_workers->where($worker_where)->setInc('order_num');
    
                $sms_data = array('mer_id' => $this->store['mer_id'], 'store_id' => $this->store['store_id'], 'type' => 'appoint');
                if ($this->config['sms_finish_order'] == 1 || $this->config['sms_finish_order'] == 3) {
                    if (empty($now_order['phone'])) {
                        $user = D('User')->field(true)->where(array('uid' => $now_order['uid']))->find();
                    }
                    $sms_data['uid'] = $now_order['uid'];
                    $sms_data['mobile'] = $user['phone'];
                    $sms_data['sendto'] = 'user';
                    $sms_data['content'] = L_('您在 X1店中下的订单(订单号：X2),已经完成了消费，如有任何疑意，请您及时联系本店，欢迎再次光临！',array('X1'=>$this->store['name'],'X2'=> $now_order['order_id']));
                    Sms::sendSms($sms_data);
                }
    
                //验证增加商家余额
                $order_info['order_type']='appoint';
                $order_info['order_id'] = $now_order['order_id'];
                $order_info['store_id'] = $now_order['store_id'];
                $order_info['mer_id'] = $now_order['mer_id'];
                $order_info['balance_pay'] = $now_order['balance_pay'];
                $order_info['score_deducte'] = $now_order['score_deducte'];
                $order_info['is_own'] = $now_order['is_own'];
                $order_info['merchant_balance'] = $now_order['merchant_balance'];
                $order_info['score_used_count'] = $now_order['score_used_count'];
                $order_info['money'] = $order_info['balance_pay'] + $order_info['score_deducte'] + $order_info['pay_money'] + $order_info['merchant_balance'] + $now_order['user_pay_money'] + $now_order['product_balance_pay'] + $now_order['product_merchant_balance'];
    
                if($now_order['product_id'] > 0){
                    $order_info['total_money'] = $now_order['product_price'];
                }else{
                    $order_info['total_money'] = $now_order['appoint_price'];
                }
                $order_info['payment_money'] = $now_order['pay_money'] + $now_order['user_pay_money'];
                $order_info['balance_pay'] = $now_order['balance_pay'] + $now_order['product_balance_pay'];
                $order_info['merchant_balance'] = $now_order['merchant_balance'] + $now_order['product_merchant_balance'];
                $order_info['card_give_money'] = $now_order['card_give_money'] + $now_order['product_card_give_money'];
                $order_info['uid'] = $now_order['uid'];
    
                $appoint_name = M('Appoint')->field('appoint_name')->where(array('appoint_id'=>$now_order['appoint_id']))->find();
                $order_info['desc']='用户预约'.$appoint_name['appoint_name'].'记入收入';
                 $order_info['score_discount_type']=$now_order['score_discount_type'];
                D('SystemBill')->bill_method($order_info['is_own'],$order_info);

                $where['status'] = array('neq',3);
                $database_appoint_supply = D('Appoint_supply');
                $now_supply = $database_appoint_supply->where($where)->find();
                if($now_supply){
                    $supply_data['status'] = 3;
                    $supply_data['end_time'] = time();
                    $supply_data['check_source'] = 2;
                    $supply_data['check_time'] = time();
                    $database_appoint_supply->where($where)->data($supply_data)->save();
                }
                if(C('config.open_extra_price')==1){
                    $score = D('Percent_rate')->get_extra_money($order_info);
                    if($score>0){
                        D('User')->add_score($order_info['uid'], floor($score), L_('用户预约X1 获得X2',array('X1'=>$appoint_name['appoint_name'],'X2'=>C('config.extra_price_alias_name'))));
                    }
    
                }else if($this->config['add_score_by_system_commission']==0 && $this->config['add_score_by_percent']==0 && ($this->config['open_score_discount']==0 || $now_order['score_discount_type']!=2) && ($this->config['score_get_times']==0 || !isset($this->config['score_get_times']))){
                    if($order_info['is_own'] && $this->config['user_own_pay_get_score']!=1){
						$order_info['payment_money'] = 0;
					}
                    $this->config['score_get'] = D('Percent_rate')->get_user_add_score_times($this->config['score_get'],$order_info['uid']);
    
                    D('User')->add_score($now_order['uid'], (($order_info['balance_pay']+$order_info['payment_money']) * $this->config['score_get']), L_('购买预约商品获得X1',$this->config['score_name']));
                }

                //增加消息通知
                $url = '\app\common\model\service\user\UserNoticeService/addNotice';
                $params['type'] = 0;
                $params['business'] = 'appoint';
                $params['order_id'] = $now_order['order_id'];
                $params['mer_id'] = $now_order['mer_id'];
                $params['store_id'] = $now_order['store_id'];
                $params['uid'] = $now_order['uid'];
                $params['title'] = '核销成功,已完成服务';
                $params['content'] = '您预约的服务已经完成，请查看订单';
                invoke_v20_service($url, [$params]);
                $this->returnCode(0);
            } else {
                $this->returnCode(1, null, L_('验证失败！请重试。'));
            }
        }else{
            if($now_order['service_status'] == 1){
                $this->returnCode(1, null, L_('订单已验证，不可重复验证'));
            }
            $this->returnCode(1, null, L_('修改订单失败，请联系系统管理员！'));
        }
    }


    /*订单详情*/
    public function appointDetail()
    {
        $where = array('store_id' => $this->store['store_id']);
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $where['order_id'] = $order_id;
    
    
        $orderInfo = D('Appoint_order')->field(true)->where($where)->find();
        if (empty($orderInfo)) {
            $this->returnCode(1, null, L_('订单信息不存在'));
        }
    
        $orderInfo['order_time'] = $orderInfo['order_time'] ? date('Y-m-d H:i:s', $orderInfo['order_time']) : 0;
        $orderInfo['user_pay_time'] = $orderInfo['user_pay_time'] ? date('Y-m-d H:i:s', $orderInfo['user_pay_time']) : 0;
        $orderInfo['pay_time'] = $orderInfo['pay_time'] ? date('Y-m-d H:i:s', $orderInfo['pay_time']) : 0;
        
        $orderInfo['user_name'] = '';
        $orderInfo['user_phone'] = '';
        if ($userInfo = D('User')->field('`uid`, `phone`, `nickname`')->where(array('uid' => $orderInfo['uid']))->find()) {
            $orderInfo['user_name'] = $userInfo['nickname'];
            $orderInfo['user_phone'] = $userInfo['phone'];
        }
    
       
        $orderInfo['appoint_name'] = '';
        if ($appointInfo = D('Appoint')->field('`appoint_id`, `appoint_name`, `appoint_type`, `appoint_price`')->where(array('appoint_id' => $orderInfo['appoint_id']))->find()) {
            $orderInfo['appoint_name'] = $appointInfo['appoint_name'];
        }

        $cue_info = unserialize($orderInfo['cue_field']);
        $cue_list = array();
        foreach ($cue_info as $key => $val) {
            if (!empty($cue_info[$key]['value'])) {
                $cue_list[$key]['name'] = $val['name'];
                $cue_list[$key]['value'] = $val['value'];
                $cue_list[$key]['type'] = $val['type'];
                if($cue_info[$key]['type'] == 2){
                    $cue_list[$key]['long'] = $val['long'];
                    $cue_list[$key]['lat'] = $val['lat'];
                    $cue_list[$key]['address'] = $val['address'];
                }
            }
        }
        $orderInfo['product_detail'] = '';
        $product_detail = D('Appoint_product')->field(true)->where(array('id' => $orderInfo['product_id']))->find();;
        if ($product_detail) {
            $orderInfo['product_detail'] = $product_detail;
        }

        $service_address_info = array();
        $orderInfo['worker_name'] = '';
        $orderInfo['worker_phone'] = '';
        $orderInfo['worker_time'] = 0;
        if ($orderInfo['appoint_type'] == 1) {
            if ($appoint_visit_order_info = D('Appoint_visit_order_info')->where(array('appoint_order_id' => $orderInfo['order_id'], 'uid' => $orderInfo['uid']))->find()) {
                $service_address = unserialize($appoint_visit_order_info['service_address']);
                foreach ($service_address as $key => $val) {
                    if (!empty($service_address[$key]['value'])) {
                        $service_address_info[$key]['name'] = $val['name'];
                        $service_address_info[$key]['value'] = $val['value'];
                        $service_address_info[$key]['type'] = $val['type'];
                        $service_address_info[$key]['phone'] = $val['phone'];
                        $service_address_info[$key]['showName'] = $val['showName'];
                        if($appoint_visit_order_info['type'] == 2){
                            $service_address_info[$key]['long'] = $val['long'];
                            $service_address_info[$key]['lat'] = $val['lat'];
                            $service_address_info[$key]['address'] = $val['address'];
                            $service_address_info[$key]['phone'] = $val['phone'];
                            $service_address_info[$key]['showName'] = $val['showName'];
                        }
                    }
                }
                if ($merchant_workers_info = M('Merchant_workers')->field(array('merchant_worker_id', 'name', 'mobile'))->where(array('merchant_worker_id' => $appoint_visit_order_info['merchant_worker_id'])) ->find()) {
                    $orderInfo['worker_name'] = $merchant_workers_info['name'];
                    $orderInfo['worker_phone'] = $merchant_workers_info['mobile'];
                    $orderInfo['worker_time'] = date('Y-m-d H:i:s', $appoint_visit_order_info['add_time']);
                }
            }
        }
    
        if($service_address_info){
            $cue_list = $service_address_info;
        }
        /**
         * 订单状态 appoint_order_status
         * paid 支付状态(0,未支付；1,已支付；2,已退款  3,用户取消
         * is_apply_refund 是否申请退货(0:未申请，1:申请)
         * confirm_status 店员确定状态：0=待确定,1=已确定,2已拒单,3=已退款
         * appoint_order_status  0 未支付  1已支付  2 已退款 3 用户取消 4 用户申请退款 5 已拒单
         */
        $orderInfo['appoint_order_status'] = intval($orderInfo['paid']);
        if($orderInfo['is_apply_refund'] == 1){
            $orderInfo['appoint_order_status'] = 4;
        }
        if($orderInfo['confirm_status'] == 2){
            $orderInfo['appoint_order_status'] = 5;
        }

        $orderInfo['refund_info'] = M('Appoint_order_refund')
            ->where([
                'appoint_id' => $orderInfo['appoint_id'],
                'order_id'   => $orderInfo['order_id']
            ])
            ->order('id desc')
            ->field('id refund_id, uid, image, reason, status, applytime')
            ->find();
        if($orderInfo['refund_info'] && $orderInfo['refund_info']['image'] ){
            $images = explode(',', $orderInfo['refund_info']['image']);
            foreach ($images as &$v){
                $v = file_domain().$v;
            }
            $orderInfo['refund_info']['image'] = $images;
        }
        $orderInfo['cue_list'] = $cue_list ?: '';
        $orderInfo['ecard_alias_name']=$this->config['ecard_alias_name'];
        $orderInfo['appoint_worker_name'] = $this->config['appoint_worker_name']?$this->config['appoint_worker_name']:L_('技师');

        //退款信息
        $refund = D('Appoint_order_refund')->where(['order_id' => $order_id])->order('id DESC')->find();
        if ($refund) {
            $status = array(L_('正在审核中'), L_('商家审核通过'), L_('商家拒绝退款'), L_('您重新申请退款'), L_('您取消申请退款'));
            $refundImages =  explode(',', $refund['image']);
            $refund['images'] = array_map(function ($r) {
                return replace_file_domain($r);
            }, $refundImages);
            $refund['apply_date'] = date('Y-m-d H:i:s',$refund['applytime']);
            $refund['reply_date'] = date('Y-m-d H:i:s',$refund['reply_time']);
            $refund['status_desc'] = $status[$refund['status']]??'--';
        }
        $orderInfo['refund'] = $refund;

        $this->returnCode(0, $orderInfo);
        
    }

    public function appointOrderRefund()
    {
        return D('Appoint_order')->appoint_reply_refund($this->store); 
    }
    public function store_arrival_get_info()
    {
        $business_type = isset($_POST['business_type']) ? trim(htmlspecialchars($_POST['business_type'])) : '';
        $order_id = isset($_POST['business_id']) ? intval($_POST['business_id']) : 0;
        if ($business_type != 'foodshop')$this->returnCode(1, null, L_('参数信息错误'));
        if ($order = D('Foodshop_order')->get_order_detail(array('order_id' => $order_id, 'store_id' => $this->store['store_id']))) {
            $card_data = $this->get_card($order['uid'],'foodshop');
            $order['card_data'] = $card_data;

            $now_user = D('User')->get_user($order['uid']);
            $order['phone'] = $now_user['phone'] ? $now_user['phone'] : '';
            $order['coupon'] = null;
            if ($order['status'] == 0) {
                $price = $order['price'];
            } else {
                $price = D('Foodshop_order')->count_price($order);
                //定制桌次折扣优惠券
                $tablesDiscount = D('Merchant_store_foodshop')->getTableDiscount($this->store['mer_id'],$this->store['store_id'],$order_id,$order);
                if($tablesDiscount!=false && $tablesDiscount['mer_discount']!=0){
                    $order['mer_discount'] = $tablesDiscount['mer_discount'];
                    $order['mer_scale'] = $tablesDiscount['mer_scale'];
                    $order['can_discount_table_money'] = $tablesDiscount['can_discount_table_money'];
                    $order['tables'] = $tablesDiscount['tables'];
                }
                //注释原商家折扣功能---------start
                //$can_discount_money = D('Foodshop_order')->count_price($order, 1);
                //可以打折的金额
                //$order['total_money'] = $can_discount_money;
                //注释原商家折扣功能---------end
                $now_coupon = D('System_coupon')->get_noworder_coupon_list($order, 'plat', '', $order['uid'], 'wap', 'foodshop');
                $order['coupon'] = $now_coupon[0];
                $order['system_coupon'] = $now_coupon;
            }
//             $order['coupon'] = array();
            $order['go_pay_price'] = round($price,2);
            $order['table_service_alias'] = D('Merchant_store_foodshop')->get_table_service_alias($order['store_id']);
            $this->returnCode(0, $order);
        } else {
            $this->returnCode(1, null, L_('不存在的订单信息'));
        }
    }
	public function store_qrcode(){
		$qrcode_url = $this->config['site_url'].'/wap.php?c=My&a=pay&store_id='.$this->store['store_id'];
		redirect($this->config['site_url'].'/index.php?c=Recognition&a=get_own_qrcode&size=20&qrCon='.urlencode($qrcode_url));
	}

    public function scan_payid_check(){
        $payid = $_POST['payid'];
        $res = M('Tmp_payid')->where(array('payid'=>$payid))->order('add_time DESC')->find();

        if(($res['add_time']+60)<time()){
            $this->returnCode('20130039');
        }else{
            $this->returnCode(0,array('uid'=>$res['uid'],'payid'=>$payid));
        }
    }
	public function save_openid(){
		if(empty($this->_uid)){
			$this->returnCode('20030101');
		}
		D('Merchant_store_staff')->data(array('openid'=>$_POST['openid']))->where(array('id' => $this->staff_session['id']))->save();
		$this->returnCode('0');
	}
	public function save_notice(){
		if(empty($this->_uid)){
			$this->returnCode('20030101');
		}
		D('Merchant_store_staff')->data(array('is_notice'=>$_POST['is_notice']))->where(array('id' => $this->staff_session['id']))->save();
		
		fdump([$_POST, $this->staff_session],'api/log/' . date('Ymd') . '/storestaff_save_notice' ,true);
		
		$this->checkNotice();
		
		$this->returnCode('0');
	}
	
	public function get_print_has(){
		$condition['mkey'] 	= $_POST['mkey'];
		$condition['store_id']	= $this->staff_session['store_id'];
		if($print = M('Orderprinter')->where($condition)->find()){
            // 更新版本号
            if ($print['version'] != $this->app_version) {
                M('Orderprinter')->where($condition)->save(array('version'=>$this->app_version));
            }
			$this->returnCode('0');
		}else{
			if($_POST['mcode'] && $_POST['mkey']){
				M('Orderprinter')->where(['mcode' => $_POST['mcode'], 'mkey' => $_POST['mkey']])->delete();
				$data_print['mer_id'] = $this->staff_session['token'];
				$data_print['store_id'] = $this->staff_session['store_id'];
                $data_print['version'] = $this->app_version;
				$data_print['mcode'] = $_POST['mcode'];
				$data_print['mkey'] = $_POST['mkey'];
				$data_print['count'] = 1;
				$data_print['paid'] = 5;
				$data_print['name'] = L_('手持设备');
				$data_print['is_main'] = 1;
				$data_print['print_type'] = 6;
				$data_print['print_way'] = 2;
				$data_print['add_time'] = time();
				if(M('Orderprinter')->data($data_print)->add()){
					fdump($data_print,'api/log/printer_auto_add_log',true);
					$this->returnCode('0');
				}else{
					fdump_sql(M('Orderprinter'),'add_printer_error');
				}
			}
			
			$this->returnCode('20001',array(),L_('打印机不存在'));
		}
	}
	
	public function own_print_work(){
		$mkey = $_POST['mkey'];
		$mkey_last_time = S('own_print_work' . $mkey);
		if($mkey_last_time > time() - 2){
			$return['info'] = '';
			$this->returnCode('0',$return);
		}
		S('own_print_work'.$mkey,time());
		
		if($this->_uid){
			$save_data = ['last_print_time'=>time()];
			if($_POST['phone_brand']){
				$save_data['phone_brand'] = $_POST['phone_brand'];
			}
			if($this->app_version){
				$save_data['app_version'] = $_POST['app_version'];
			}
			if($_POST['app_version_name']){
				$save_data['app_version_name'] = strval($_POST['app_version_name']);
			}
			
            M('Merchant_store_staff')->where(['id'=>$this->_uid])->data($save_data)->save();
		}
		
		if(empty($mkey)){
			$this->returnCode('20001', array(), L_('请携带密钥值'));
		}
		
		fdump($_POST['app_version_name'], 'api/log/' . date('Ymd') . '/own_print_work/' . $mkey, true);
		
		$op = new orderPrint(C('config.print_server_key'), C('config.print_server_topdomain'));
		$return['info'] = $op->get_own_printer($mkey, $this->app_version);
		$this->returnCode('0',$return);
	}
	
	//调整店铺有没有接单的店员
	protected function checkNotice(){
		$where = [
			'store_id' => $this->staff_session['store_id'],
			'is_notice' => '0',
			'last_time' => ['gt','0'],
		];
		$staff_count = M('Merchant_store_staff')->where($where)->count();
		M('Merchant_store')->where(['store_id'=>$this->staff_session['store_id']])->data(['staff_notice_count'=>$staff_count])->save();
	}
	
	//心跳包
	public function checkLogin(){
		//$this->returnCode('20044017', [], json_encode(['title'=>'你好', 'content'=>'逗你玩', 'btn'=>'关掉', 'noClose'=>true]));
		
		if($this->DEVICE_ID && $this->staff_session){
			if($this->staff_session['device_id'] != $this->DEVICE_ID){
				$this->returnCode('20044014');
			}
            M('Merchant_store_staff')->where(['id'=>$this->_uid])->data(['last_time'=>time(), 'use_lang'=>$this->config['system_lang']])->save();
            M('Merchant_store')->where(['store_id'=>$this->staff_session['store_id']])->data(['staff_last_time'=>time()])->save();
		}
		
		fdump([$_SERVER['HTTP_USER_AGENT']], 'api/log/' . date('Ymd') . '/staff_check_login/' . $this->DEVICE_ID, true);
		
		$this->checkNotice();
		
		if($this->app_version && $this->DEVICE_ID != 'packapp'){
			$appConfig  =   D('Appapi_app_config')->get();
			$appConfig['staff_android_vcode'] = 1500; 
			if($appConfig['storestaff_android_must_upgrade'] && $appConfig['staff_android_vcode'] > $this->app_version){
				$this->returnCode('20044015');
			}
		}
		
		$this->returnCodeOk([]);
	}

    //免单
    public function sub_card(){
        if(!empty($_POST['keyword'])){
            if ($_POST['find_type'] == 'pass') {
                $where['pass'] = htmlspecialchars($_POST['keyword']);
            }else if ($_POST['find_type'] == 'nickname') {
                $where['nickname'] = array('like','%'.htmlspecialchars($_POST['keyword']).'%');
            }else if ($_POST['find_type'] == 'phone') {
                $where['phone'] = htmlspecialchars($_POST['keyword']);
            }
        }

        $res = M('Sub_card_user_pass')->where(array('id'=>$_POST['id']))->find();
        $id = $res['id'];
        if(empty($_POST['keyword'])){
            $where['status']=1;
        }else{
            $where['status']=0;
        }
        $where['store_id']=$this->store['store_id'];


        $count = M('Sub_card_user_pass')->where($where)->count();

        if($id && empty($condition)){
            $where['s.id']= array('gt',$id);
        }
        $where['s.status']=$where['status'];
        unset($where['status']);
        $list = M('Sub_card_user_pass')->field('s.* ,sc.name,sc.desc,sc.price,sc.free_total_num,u.nickname,u.phone')->join('AS s LEFT JOIN '.C('DB_PREFIX').'sub_card sc ON sc.id = s.sub_card_id LEFT JOIN '.C('DB_PREFIX').'user u ON s.uid  =u.uid  ')->where($where)->order('s.use_time DESC')->limit(10)->select();

        $pagenum = ceil($count /10);
        $arr=array();
        foreach ($list as &$l) {
            $sub_card = D('Sub_card')->get_sub_card($l['sub_card_id']);
            $arr[]=array(
                'id'=>$l['id'],
                'pass'=>$l['pass'],
                'phone'=>empty($l['phone'])?'':$l['phone'],
                'nickname'=>empty($l['nickname'])?'':$l['nickname'],
                'money'=>$sub_card['price']/$sub_card['free_total_num'],
                'name'=>$sub_card['name'],
                'status'=>$l['status'],
                'use_time'=>date('Y/m/d H:i:s',$l['use_time'])
            );
        }
        if(empty($arr)){
            $this->returnCode(0,array('order_list' => array(),'pagenum'=>$pagenum));
        }else{
            $this->returnCode(0,array('order_list' => $arr,'pagenum'=>$pagenum));
        }

    }

    //搜索免单
    public function sub_card_find(){
        if(IS_POST) {
            $find_value = $_POST['pass'];
            if (strlen($find_value) == 14) {
                $res = D('Sub_card_order')->get_orderid_by_pass($find_value,$this->store['store_id']);
                if (!empty($res)) {
                    $return['row_count'] = 1;
                    $return['money'] = $res['sub_card']['price']/$res['sub_card']['free_total_num'];
                    $return['name'] = $res['sub_card']['name'];
                    $return['pass'] = $find_value;
                    $return['use_time'] = $res['pass_info']['use_time']?date('Y-m-d H:i:s',$res['pass_info']['use_time']):0;
                    $return['add_time'] = $res['pass_info']['add_time'];
                    $return['staff'] = $res['pass_info']['last_staff'];
                    $return['phone'] = $res['pass_info']['phone'];
                    $return['uid'] = $res['pass_info']['uid'];
                    $return['nickname'] = $res['pass_info']['nickname'];
                    $return['effective_days'] = $res['pass_info']['effective_days'];
                    if($return['effective_days']<0 && $return['use_time']==0){
                        $return['status'] = L_('已过期，未消费');
                    }else if($return['use_time']>0){
                        $return['status'] = L_('已消费');
                    }else{
                        $return['status'] = L_('未消费');
                    }
                    $this->returnCode('0',$return);
                } else {
                    $return['row_count'] = 0;
                    $this->returnCode('0',$return);
                }
            }else{
                $this->returnCode('26000001',array(),L_(L_('消费码不正确')));
            }
        }else{
            $this->display();
        }
    }

    //免单套餐验证消费
    public function sub_card_verify(){
        $find_value = $_POST['pass'];
        $res = D('Sub_card_order')->get_orderid_by_pass($find_value,$this->store['store_id']);
        if(empty($res)){
            $this->returnCode('26000001',array(),'消费码不正确');
        }else if($res['pass_info']['status']==1){
            $this->returnCode('26000002',array(),L_('此消费码已消费，不能再验证消费！'));
        } else if($res['pass_info']['effective_days']<0){
            $this->returnCode('26000003',array(),L_('此消费码已过期'));
        } else {
            $condition['id'] = $res['pass_info']['id'];
            $date['status'] = '1';
            $date['use_time'] = $_SERVER['REQUEST_TIME'];
            $date['last_staff'] = $this->staff_session['name'];
            if(M('Sub_card_user_pass')->where($condition)->data($date)->save()){
                //验证增加商家余额
                $now_order['order_type'] = 'sub_card';
                $now_order['order_id'] = $res['pass_info']['id'];
                $now_order['mer_id'] =$this->store['mer_id'];
                $now_order['store_id'] =$this->store['store_id'];
                $now_order['percent'] =$res['sub_card']['percent'];
                $now_order['desc']=L_('免单验证消费记入收入');
                $now_order['money'] = $res['sub_card']['price']/$res['sub_card']['free_total_num'];
                $now_order['total_money'] =$now_order['money'];
                D('SystemBill')->bill_method(0,$now_order);

                $this->returnCode('0');
            }else{
                $this->returnCode('26000002',array(),L_('验证失败'));
            }
        }
    }
    
    public function foodshop_change_order_note()
    {
        $condition_where['store_id'] = $this->store['store_id'];
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $id = isset($_POST['detail_id']) ? intval($_POST['detail_id']) : 0;
        $note = isset($_POST['note']) ? htmlspecialchars(trim($_POST['note'])) : '';
        $condition_where['order_id'] = $order_id;
        $condition_where['id'] = $id;
        if ($detail = M('Foodshop_order_detail')->where($condition_where)->find()) {
            if ($detail['note'] != $note) {
                M('Foodshop_order_detail')->where($condition_where)->save(array('note' => $note));
            }
            $this->returnCode('0');
        } else {
            $this->returnCode(1, null, L_('保存失败！'));
        }
    }
    /*
     * 优惠券列表
     * */
    public function coupon_list(){
        $store_id = $this->store['store_id'];
        $condition_where = "`ear`.`uid`=`u`.`uid` AND `ear`.`activity_list_id`=`eal`.`pigcms_id` AND `ecr`.`record_id`=`ear`.`pigcms_id` AND `ecr`.`store_id`='$store_id'";
        $id = $_POST['id'];
        $condition_table = array(C('DB_PREFIX').'extension_activity_list'=>'eal',C('DB_PREFIX').'extension_activity_record'=>'ear',C('DB_PREFIX').'extension_coupon_record'=>'ecr',C('DB_PREFIX').'user'=>'u');

        $count = D('')->where($condition_where)->table($condition_table)->count();

        if($id){
            $condition_where .= ' AND `ecr`.`pigcms_id` < '.$id;
        }

        $order_list = D('')->field('`eal`.`name`,`eal`.`pic`,`ecr`.*,`ear`.`time`,`u`.`uid`,`u`.`nickname`,`u`.`phone`')->where($condition_where)->table($condition_table)->order('`ecr`.`pigcms_id` DESC')->limit(10)->select();

        $pagenum = ceil($count /10);
        $extension_image_class = new extension_image();
        foreach ($order_list as &$l) {
            $l['time']  = date('Y-m-d H:i:s',$l['time']);
            $l['pic'] = $extension_image_class->get_image_by_path(array_shift(explode(';',$l['pic'])),'s');
        }
        if(empty($order_list)){
            $this->returnCode(0,array('order_list' => array(),'pagenum'=>$pagenum));
        }else{
            $this->returnCode(0,array('order_list' => $order_list,'pagenum'=>$pagenum));
        }
    }

    public function coupon_find(){

            $mer_id = $this->store['mer_id'];
            $condition_where = "`ear`.`uid`=`u`.`uid` AND `ear`.`activity_list_id`=`eal`.`pigcms_id` AND `ecr`.`record_id`=`ear`.`pigcms_id` AND `eal`.`mer_id`='$mer_id'";
            $find_value = $_POST['keyword'];
            $store_id = $this->store['store_id'];
            if($_POST['find_type'] == 1 && strlen($find_value) == 16){
                $condition_where .= " AND `ecr`.`number`='$find_value'";
            }else{
                $condition_where .= " AND `ecr`.`store_id`='$store_id'";
                if($_POST['find_type'] == 1){
                    $condition_where .= " AND `ecr`.`number` like '$find_value%'";
                }else if($_POST['find_type'] == 2){
                    $condition_where .= " AND `eal`.`pigcms_id` like '$find_value%'";
                }else if($_POST['find_type'] == 3){
                    $condition_where .= " AND `u`.`uid`='$find_value'";
                }else if($_POST['find_type'] == 4){
                    $condition_where .= " AND `u`.`nickname`='$find_value'";
                }else if($_POST['find_type'] == 5){
                    $condition_where .= " AND `u`.`phone` like '$find_value%'";
                }
            }
			if($_POST['id']){
				$last_id = $_POST['id'];
				$condition_where .= "  AND `ecr`.`pigcms_pd` < $last_id";
			}
            $extension_image_class = new extension_image();
            $condition_table = array(C('DB_PREFIX').'extension_activity_list'=>'eal',C('DB_PREFIX').'extension_activity_record'=>'ear',C('DB_PREFIX').'extension_coupon_record'=>'ecr',C('DB_PREFIX').'user'=>'u');
            $order_list = D('')->field('`eal`.`name`,`ecr`.*,`ear`.`time`,`eal`.`pic`,`u`.`uid`,`u`.`nickname`,`u`.`phone`,`ecr`.`check_time`')->where($condition_where)->table($condition_table)->order('`ecr`.`pigcms_id` DESC')->select();
      
            if($order_list){
                foreach($order_list as &$l){
                    $l['time_txt'] = date('Y-m-d H:i:s',$l['time']);
                    $l['check_time_txt'] = date('Y-m-d H:i:s',$l['check_time']);
                    $l['time']  = date('Y-m-d H:i:s',$l['time']);
                    $l['pic'] = $extension_image_class->get_image_by_path(array_shift(explode(';',$l['pic'])),'s');
                }
            }
            $return['order_list'] = $order_list?$order_list:array();
            $return['row_count'] = count($order_list);
        $this->returnCode(0,$return);

    }
    public function coupon_verify(){
        $mer_id = $this->store['mer_id'];
        $condition_table = array(C('DB_PREFIX').'extension_activity_list'=>'eal',C('DB_PREFIX').'extension_activity_record'=>'ear',C('DB_PREFIX').'extension_coupon_record'=>'ecr');
        $condition_where = "`ear`.`activity_list_id`=`eal`.`pigcms_id` AND `ecr`.`record_id`=`ear`.`pigcms_id` AND `eal`.`mer_id`='$mer_id' AND `ecr`.`number`='{$_POST['pass']}'";
        $now_order = D('')->field('`ecr`.`pigcms_id`,`eal`.`pigcms_id` as id,`eal`.`money`,`eal`.`name`')->where($condition_where)->table($condition_table)->find();
        if(!empty($now_order)){
            if(D('Extension_coupon_record')->where(array('pigcms_id'=>$now_order['pigcms_id']))->data(array('check_time'=>time(),'store_id'=>$this->store['store_id'],'last_staff'=>$this->staff_session['name']))->save()){
                //验证增加商家余额
                if($now_order['money']>0){
                    $now_order['order_type'] ='coupon';
                    $now_order['order_id'] =$now_order['pigcms_id'];
                    $now_order['mer_id']  = $mer_id;
                    $now_order['desc']=L_('用户购买X1记入收入',$now_order['name']);
                    D('SystemBill')->bill_method(0,$now_order);
                    //商家推广分佣
                    $now_user = M('User')->where(array('uid'=>$now_order['uid']))->find();
                    D('Merchant_spread')->add_spread_list($now_order,$now_user,$now_order['order_type'],$now_user['nickname'].L_('用户购买平台活动商品获得佣金'));
                }
                $this->returnCode(0);
            }else{
                $this->returnCode('20130007');
            }
        }else{
            $this->returnCode('20130008');
        }
    }

    public function hotel_refund(){
        $this->check_group();
        $now_order = D('Group_order')->get_order_detail_by_id_and_merId($this->store['mer_id'],$_POST['order_id'],false);
        $temp_order = $now_order;
        $now_order['refund_detail'] = unserialize($now_order['refund_detail']);
        if(empty($now_order)){
            exit(L_('此订单不存在！'));
        }

        $trade_hotel_info = D('Trade_hotel_category')->format_order_trade_info($now_order['trade_info']);
        $refund = $_POST['refund_num'];

        $price_list = $trade_hotel_info['price_list'];
        $price_list_all = array_sum($price_list);
        $hotel_num_all = $trade_hotel_info['num'];    // 房间数量
        $all_hotel_num = count($price_list)*$hotel_num_all;  //整个酒店订单数量 房间数*天数
        $old_refund_num = $now_order['order_detail']['num'];   //之前退款数量
        $refund_per_money = ($now_order['score_deducte']+$now_order['merchant_balance']+$now_order['card_give_money']+$now_order['balance_pay']+$now_order['payment_money'])/$hotel_num_all;
        $refund_money = 0;
        $refund_num = 0;
        foreach ($refund as $key=>$v) {
            $refund_money_per = $price_list[$key]/$price_list_all*$refund_per_money;
            $refund_money_tmp = $v*$refund_money_per;
            $refund_money += $refund_money_tmp;
            if($now_order['refund_detail'] ['detail'][$key]['num']>0 && $now_order['refund_detail'] ['detail'][$key]['num']+$v>$hotel_num_all){
                $this->returnCode(1,'',L_('退款房间超出订购房间数'));
            }
            $tmp_detail[$key] = array('num'=>$v,'money'=>$refund_money_tmp);
            $refund_num+=$v;
        }
        $refund_status = 3;
        if($old_refund_num+$refund_num<$all_hotel_num){
            $refund_status = 6;
        }

        if($now_order['refund_detail']){
            $old_refund = $now_order['refund_detail'];

            if($old_refund['score']>0){
                $now_order['score_used_count']-=$old_refund['score'];
                $now_order['score_used_count'] =intval($now_order['score_used_count']);
            }

            if($old_refund['card_give_money']>0){
                $now_order['card_give_money']-=$old_refund['card_give_money'];
                $now_order['card_give_money'] = sprintf("%.2f",$now_order['card_give_money']);
            }

            if($old_refund['merchant_balance']>0){
                $now_order['merchant_balance']-=$old_refund['merchant_balance'];
                $now_order['merchant_balance'] = sprintf("%.2f",$now_order['merchant_balance']);
            }

            if($old_refund['balance_pay']>0){
                $now_order['balance_pay']-=$old_refund['balance_pay'];
                $now_order['balance_pay'] = sprintf("%.2f",$now_order['balance_pay']);
            }

            if($old_refund['payment_money']>0){
                $now_order['payment_money']-=$old_refund['payment_money'];
                $now_order['payment_money'] = sprintf("%.2f",$now_order['payment_money']);
            }
        }

        if($now_order['refund_detail']){
            $old_refund = $now_order['refund_detail'];
        }
        $refund_detail['detail'] = $tmp_detail;

        $go_refund_param['msg'] = '';
        //退积分
        if ($refund_money>0 && $now_order['score_used_count']>0 ) {
            if($refund_money>=$now_order['score_deducte']){
                $tmp_refund_money = $now_order['score_deducte'];
                $refund_score = $now_order['score_used_count'];
            }else{
                $tmp_refund_money = $refund_money;
                $per_score_deducte = $temp_order['score_deducte']/$temp_order['score_used_count'];
                $refund_score  = round($refund_money/$per_score_deducte,2);

                if($refund_score>$now_order['score_used_count']){
                    $refund_score = $now_order['score_used_count'];
                    $tmp_refund_money  = $refund_score*$per_score_deducte;
                }
            }

            $refund_detail['score'] = $refund_score;

            $result = D('User')->add_score($now_order['uid'],$refund_score,L_('退款 X1 X2回滚',array('X1'=>$now_order['order_name'],'X2'=>C('config.score_name'))));
            $refund_detail['refund_time'] =time();
            if($result['error_code']){
                $param['err_msg'] = $result['msg'];
            } else {
                $param['refund_id'] = $now_order['order_id'];
            }
            $data_group_order['order_id'] = $now_order['order_id'];
            $data_group_order['refund_detail'] = serialize($refund_detail);
            $result['error_code'] || $data_group_order['status'] = $refund_status;
            D('Group_order')->data($data_group_order)->save();
            if ($result['error_code']) {
                $data_group_order['order_id'] = $now_order['order_id'];

                $refund_detail['msg']  =  L_('退款失败,退回X1失败，原因：X2',array('X1'=>C('config.score_name'),'X2'=>$result['msg']));
                $data_group_order['refund_detail'] = serialize($refund_detail);
                D('Group_order')->data($data_group_order)->save();
                $this->returnCode(1,'',$data_group_order['refund_detail']);
            }
            $go_refund_param['msg'] .= $result['msg'];
            $refund_money = $refund_money-$tmp_refund_money;

        }

        //商家会员卡余额退款
        if($refund_money>0 && $now_order['card_give_money'] >0 ){
            if($refund_money>=$now_order['card_give_money']){
                $tmp_refund_money = $now_order['card_give_money'];
            }else{
                $tmp_refund_money = $refund_money;
            }

            $tmp_refund_money = round($tmp_refund_money,2);
            $refund_detail['card_give_money'] = $tmp_refund_money;
            $result = D('Card_new')->add_user_money(
                $now_order['mer_id'],
                $now_order['uid'],
                0,
                $tmp_refund_money,
                0,
                L_('退款 X1 增加余额',$now_order['order_name']),
                L_('退款 X1 增加赠送余额',$now_order['order_name'])
            );
            $refund_detail['refund_time'] = array('refund_time' => time());
            if($result['error_code']){
                $param['err_msg'] = $result['msg'];
            } else {
                $param['refund_id'] = $now_order['order_id'];
            }
            $data_group_order['order_id'] = $now_order['order_id'];
            $refund_detail['refund_time'] =time();
            $data_group_order['refund_detail'] = serialize($refund_detail);
            $result['error_code'] || $data_group_order['status'] = $refund_status;
            D('Group_order')->data($data_group_order)->save();
            if ($result['error_code']) {
                $data_group_order['order_id'] = $now_order['order_id'];
//				$data_group_order['status'] = 7;

                $refund_detail['msg']  =  L_('退款失败，退回商家会员卡赠送余额失败，原因：x1',$result['msg']);
                $data_group_order['refund_detail'] = serialize($refund_detail);
                D('Group_order')->data($data_group_order)->save();
                $this->returnCode(1,'',$data_group_order['refund_detail']);
            }
            $go_refund_param['msg'] = $result['msg'];
            $refund_money = $refund_money-$tmp_refund_money;
        }

        if($refund_money>0 &&$now_order['merchant_balance']>0){

            if($refund_money>=$now_order['merchant_balance']){
                $tmp_refund_money = $now_order['merchant_balance'];
            }else{
                $tmp_refund_money = $refund_money;
            }
            $tmp_refund_money = round($tmp_refund_money,2);
            $refund_detail['merchant_balance'] = $tmp_refund_money;
            $result = D('Card_new')->add_user_money(
                $now_order['mer_id'],
                $now_order['uid'],
                $tmp_refund_money,
                0,
                0,
                L_('退款 X1 增加余额',$now_order['order_name']),
                L_('退款 X1 增加赠送余额',$now_order['order_name'])
            );
            $refund_detail['refund_time'] =time();
            if($result['error_code']){
                $param['err_msg'] = $result['msg'];
            } else {
                $param['refund_id'] = $now_order['order_id'];
            }
            $data_group_order['order_id'] = $now_order['order_id'];
            $data_group_order['refund_detail'] = serialize($refund_detail);
            $result['error_code'] || $data_group_order['status'] = $refund_status;
            D('Group_order')->data($data_group_order)->save();
            if ($result['error_code']) {
                $data_group_order['order_id'] = $now_order['order_id'];
//				$data_group_order['status'] = 7;
                $refund_detail['msg']  =  L_('退款失败，退回商家会员卡余额失败，原因：X1',$result['msg']);
                $data_group_order['refund_detail'] = serialize($refund_detail);
                D('Group_order')->data($data_group_order)->save();
                $this->returnCode(1,'',$data_group_order['refund_detail']);
            }
            $go_refund_param['msg'] = $result['msg'];
            $refund_money = $refund_money-$tmp_refund_money;
        }

        //平台余额退款
        if($refund_money>0 && $now_order['balance_pay']>0 ){

            if($refund_money>=$now_order['balance_pay']){
                $tmp_refund_money = $now_order['balance_pay'];
            }else{
                $tmp_refund_money = $refund_money;
            }
            $tmp_refund_money = round($tmp_refund_money,2);
            $refund_detail['balance_pay'] = $tmp_refund_money;

            $add_result = D('User')->add_money($now_order['uid'],$tmp_refund_money,L_('退款 X1 增加余额',$now_order['order_name']));
            $refund_detail['refund_time'] = array('refund_time' => time());
            if($add_result['error_code']){
                $param['err_msg'] = $add_result['msg'];
            } else {
                $param['refund_id'] = $now_order['order_id'];
            }

            $data_group_order['order_id'] = $now_order['order_id'];
            $refund_detail['refund_time'] =time();
            $data_group_order['refund_detail'] = serialize($refund_detail);

            $add_result['error_code'] || $data_group_order['status'] = $refund_status;
            D('Group_order')->data($data_group_order)->save();
            if ($add_result['error_code']) {
                $data_group_order['order_id'] = $now_order['order_id'];
//				$data_group_order['status'] = 7;
                $refund_detail['msg']  =  L_('退款失败，退回余额失败，原因：X1',$add_result['msg']);
                $data_group_order['refund_detail'] = serialize($refund_detail);

                D('Group_order')->data($data_group_order)->save();
                $this->returnCode(1,'',$data_group_order['refund_detail']);
            }
            $go_refund_param['msg'] = L_('平台余额退款成功');
            $refund_money = $refund_money-$tmp_refund_money;
        }

        if($refund_money>0 && $now_order['payment_money'] >0){
            $refund_money = round($refund_money,2);
            if($refund_money>=$now_order['payment_money']){
                $tmp_refund_money = $now_order['payment_money'];
            }else{
                $tmp_refund_money = $refund_money;
            }

            $refund_detail['payment_money'] = $tmp_refund_money;

            if($now_order['is_own']){
                $pay_method = array();
                $merchant_ownpay = D('Merchant_ownpay')->field('mer_id',true)->where(array('mer_id'=>$now_order['mer_id']))->find();
                foreach($merchant_ownpay as $ownKey=>$ownValue){
                    $ownValueArr = unserialize($ownValue);
                    if($ownValueArr['open']){
                        $ownValueArr['is_own'] = true;
                        $pay_method[$ownKey] = array('name'=>$this->getPayName($ownKey),'config'=>$ownValueArr);
                    }
                }
                $now_merchant = D('Merchant')->get_info($now_order['mer_id']);
                if ($now_merchant['sub_mch_refund'] && C('config.open_sub_mchid') && $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0) {
                    $pay_method['weixin']['config']['pay_weixin_appid'] = C('config.pay_weixin_appid');
                    $pay_method['weixin']['config']['pay_weixin_appsecret'] = C('config.pay_weixin_appsecret');
                    $pay_method['weixin']['config']['pay_weixin_mchid'] =C('config.pay_weixin_sp_mchid');
                    $pay_method['weixin']['config']['pay_weixin_key'] = C('config.pay_weixin_sp_key');
                    $pay_method['weixin']['config']['sub_mch_id'] = $now_merchant['sub_mch_id'];
                    $pay_method['weixin']['config']['pay_weixin_client_cert'] = C('config.pay_weixin_sp_client_cert');
                    $pay_method['weixin']['config']['pay_weixin_client_key'] = C('config.pay_weixin_sp_client_key');
                    $pay_method['weixin']['config']['is_own'] = 2 ;
                }
            }else{
                $pay_method = D('Config')->get_pay_method(0, 0, $now_order['is_mobile_pay'] == 1 ? true : false, $now_order['is_mobile_pay'] == 2 ? true : false);
            }

            if(empty($pay_method)){
                $data_group_order['order_id'] = $now_order['order_id'];
//				$data_group_order['status'] = 7;

                $refund_detail['msg']  = L_('退款失败，原因：未找到支付方式');
                $data_group_order['refund_detail'] = serialize($refund_detail);
                D('Group_order')->data($data_group_order)->save();
                $this->returnCode(1,'',  $refund_detail['msg'] );

            }
            $pay_class_name = ucfirst($now_order['pay_type']);
            if (in_array($now_order['pay_type'], ['hqpay', 'hqpay_wx', 'hqpay_al'])) {
                $pay_class_name = 'Hqpay';
                empty($pay_method[$now_order['pay_type']]) && $pay_method[$now_order['pay_type']] = 1;
            }
            if(empty($pay_method[$now_order['pay_type']])){
                $data_group_order['order_id'] = $now_order['order_id'];
//				$data_group_order['status'] = 7;

                $refund_detail['msg']  = L_('退款失败，原因：支付方式不存在');
                $data_group_order['refund_detail'] = serialize($refund_detail);
                D('Group_order')->data($data_group_order)->save();
                $this->returnCode(1,'',  $refund_detail['msg'] );

            }

            $import_result = import('@.ORG.pay.'.$pay_class_name);
            if(empty($import_result)){
                $data_group_order['order_id'] = $now_order['order_id'];
//				$data_group_order['status'] = 7;
                $refund_detail['msg']  =L_('退款失败，原因：支付方式不存在');
                $data_group_order['refund_detail'] = serialize($refund_detail);
                D('Group_order')->data($data_group_order)->save();
                $this->returnCode(1,'',  $refund_detail['msg'] );

            }
            $now_order['order_type'] = 'group';

            $tmp_order_id = $now_order['order_id'];
            if(!empty($now_order['orderid'])){
                $now_order['order_id']=$now_order['orderid'];
            }
            $now_order['payment_money'] = $temp_order['payment_money'];
            $pay_class = new $pay_class_name($now_order,$tmp_refund_money,$now_order['pay_type'],$pay_method[$now_order['pay_type']]['config'],'',1);
            $go_refund_param = $pay_class->refund();
            $refund_detail['refund_time'] =time();
            $refund_detail['payment_refund'] = $go_refund_param['refund_param'];
            $now_order['order_id'] = $tmp_order_id;
            $data_group_order['order_id'] = $now_order['order_id'];
            $data_group_order['refund_detail'] = serialize($go_refund_param['refund_param']);
            if(empty($go_refund_param['error']) && $go_refund_param['type'] == 'ok'){
                $data_group_order['status'] = $refund_status;
            }
            D('Group_order')->data($data_group_order)->save();
        }

        $trade_info = unserialize($now_order['trade_info']);
        switch($trade_info['type']){
            case 'hotel':
                $where['mer_id'] = $now_order['mer_id'];
                $where['cat_id'] = $trade_info['cat_id'];

                foreach ($refund_detail['detail'] as $k=>$num) {
                    $where['stock_day']=$k;
                    M('Trade_hotel_stock')->where($where)->setInc('stock',$num['num']);
                }
                break;
        }


        foreach ($price_list as $key=>$v) {
            if($old_refund['detail'][$key]['num']>0){
                if($refund_detail['detail'][$key]){
                    $refund_detail['detail'][$key]['num'] +=$old_refund['detail'][$key]['num'];
                    $refund_detail['detail'][$key]['money'] +=$old_refund['detail'][$key]['money'];
                }else{
                    $refund_detail['detail'][$key] = $old_refund['detail'][$key];
                }
            }
        }

        if($old_refund['score']>0){
            $refund_detail['score']+=$old_refund['score'];
        }

        if($old_refund['card_give_money']>0){
            $refund_detail['card_give_money']+=$old_refund['card_give_money'];
        }

        if($old_refund['merchant_balance']>0){
            $refund_detail['merchant_balance']+=$old_refund['merchant_balance'];
        }

        if($old_refund['balance_pay']>0){
            $refund_detail['balance_pay']+=$old_refund['balance_pay'];
        }

        if($old_refund['payment_money']>0){
            $refund_detail['payment_money']+=$old_refund['payment_money'];
        }

        D('Group_order')->where(array('order_id'=>$now_order['order_id']))->save(array('refund_detail'=>serialize($refund_detail)));

        if(!empty($now_order['trade_info'])){
            $trade_info = unserialize($now_order['trade_info']);

            switch($trade_info['type']){
                case 'hotel':
                    $where['mer_id']=$now_order['mer_id'];
                    $where['cat_id']=$trade_info['cat_id'];

                    foreach ($refund_detail['detail'] as $k=>$num) {
                        $where['stock_day']=$k;
                        M('Trade_hotel_stock')->where($where)->setInc('stock',$num);
                    }
                    break;
            }
        }

        if(in_array($now_order['status'],array(1,2,3)) && $merchant_money =  M('Merchant_money_list')->where(array('income'=>1,'type'=>'group','order_id'=>$now_order['real_orderid']))->find()){

            $num = $trade_hotel_info['num']*count($price_list);
            $refund_money_mer = $merchant_money['money']/$num*$refund_num;
            $desc  = L_("店员手动退房扣除商家余额");
            $returnSystemTake = $returnTakePercent = 0;
            if($merchant_money['system_take'] > 0){
                $returnSystemTake = bcmul(bcdiv($refund_money_mer,$merchant_money['money'],2), $merchant_money['system_take'],2);
                $returnTakePercent = $merchant_money['percent'];
            }
            D('Merchant_money_list')->use_money(
                $now_order['mer_id'],
                $refund_money_mer,
                'group',
                $desc,
                $now_order['real_orderid'], 
                $returnTakePercent, 
                $returnSystemTake
                [],
                0,
                $go_refund_param['refund_param']??[]
            );
        }

        $refund_status == 3 &&  D("System_order")->save_order_status('group', $_POST['order_id'], 4, 3);

        $this->returnCode(0);

    }


    public function balance_verify_phone(){
        $phone = $_POST['phone'];
        if(empty($phone)){

            $this->returnCode(1,'',L_('手机号码不能为空'));
        }
        if($res=D('User')->get_user($phone,'phone')){
            $this->returnCode(0,array('money'=>$res['now_money']));
        }else{
            $this->returnCode(1,'',L_('检测到改手机号暂未注册为平台用户'));
        }
    }

    public function sendCode(){

        $code = mt_rand(1000, 9999);
        $text = L_('您的验证码是：X1。此验证码20分钟内有效，请不要把验证码泄露给其他人。如非本人操作，可不用理会！',$code);

        if($_POST['phone']){
            $phone = $_POST['phone'];
        }
		$phone = phone_format($_POST['phone_country_type'], $phone);

        $columns = array();
        $columns['phone'] = $phone;
        $columns['mer_id'] = $this->store['mer_id'];
        $columns['extra'] = $code;
        $columns['type'] = 5;
        $columns['status'] = 0;
        $columns['send_time'] = time();
        $columns['expire_time'] = $columns['send_time'] + 1200;
        $result = M("Merchant_sms_record")->add($columns);
        if (!$result){
            $this->returnCode(1,'',L_('短信记录保存失败'));
        }

        $sms_data = array('mer_id' => $this->store['mer_id'], 'store_id' => $this->store['store_id'], 'content' => $text, 'mobile' => $phone, 'uid' => 0, 'type' => 'pay_verify');
        $_POST['phone_country_type'] && $sms_data['nationCode']  = $_POST['phone_country_type'];
        $return = Sms::sendSms($sms_data);

        if ($return != 0) {
            $this->returnCode(1,'',self::ConverSmsCode($return));
        }
        $this->returnCode(0, $return);
    }

    static private function ConverSmsCode($smscode) {
        $errCode = array(
            '2'    => '20060001',
            '400'  => '20060002',
            '401'  => '20060003',
            '402'  => '20060004',
            '403'  => '20060005',
            '4030' => '20060006',
            '404'  => '20060007',
            '405'  => '20060008',
            '4050' => '20060009',
            '4051' => '20060010',
            '4052' => '20060011',
            '406'  => '20060012',
            '407'  => '20060013',
            '4070' => '20060014',
            '4071' => '20060015',
            '4072' => '20060016',
            '4073' => '20060017',
            '408'  => '20060018',
            '4085' => '20060019',
            '4084' => '20060020',
        );
        return $errCode[$smscode];
    }
    
    // 语音获取
    private function voice_baidu()
    {
        static $return;

        if (empty($return)) {
            $voice_baidu = "https://openapi.baidu.com/oauth/2.0/token?grant_type=client_credentials&client_id=XU7nTWQzS9vn32bckmrhHbTu&client_secret=1375720f23eed9b7b787e728de5dd1c2";
            import('ORG.Net.Http');
            $return = Http::curlGet($voice_baidu);
        }
        return $return;
    }

    /**
     * 获取店员客服跳转
     * @throws Exception
     */
    public function getImUrl()
    {
        $ticket = I('ticket', false);
        $info = ticket::get($ticket, $this->DEVICE_ID, true);
        $staffid = isset($info['uid']) ? $info['uid'] : -1;
        if ($staffid < 0) {
            $this->returnCode('20130030');
        }
        $staff = M('Merchant_store_staff')->where(['id' => $staffid])->field('store_id,openid')->find();
        if (empty($staff)) {
            $this->returnCode('20140021');
        }
        if (empty($staff['openid'])) {
            $this->returnCode('20130057');
        }
        $kf = M('Merchant_store_kefu')->alias('kf')
            ->join(C('DB_PREFIX') . 'user AS u ON kf.bind_uid = u.uid')
            ->where(['kf.store_id' => $staff['store_id'], 'u.openid' => $staff['openid']])
            ->field('kf.store_id,kf.username')
            ->find();
        if (empty($kf)) {
            $this->returnCode(1001, '', L_('未绑定客服'));
        }
        $im = build_store_im_conversation_url($kf['username'], 'store2user');
        $this->returnCodeOk(['url' => $im]);
    }


    /**
     * 录入商品重量
     */
    public function write_weight()
    {
        $detail_ids = isset($_POST['detail_id']) && is_array($_POST['detail_id']) ? $_POST['detail_id'] : [];
        $real_weights = isset($_POST['real_weight']) && is_array($_POST['real_weight']) ? $_POST['real_weight'] : [];
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $ticket = isset($_POST['ticket']) ? trim($_POST['ticket']) : '';
        $info = ticket::get($ticket, $this->DEVICE_ID, true);
        $staffid = isset($info['uid']) ? $info['uid'] : -1;
        if ($staffid < 0) {
            $this->returnCode('20130030');
        }
        $staff = M('Merchant_store_staff')->where(['id' => $staffid])->field('store_id,openid')->find();
        if (empty($staff)) {
            $this->returnCode('20140021');
        }
        if (empty($detail_ids) || empty($real_weights) || empty($order_id)) {
            $this->returnCode(1, '', L_('参数有误'));
        }
        $store_id = $staff['store_id'];
        $detail_mod = D('Shop_order_detail');
        foreach ($detail_ids as $k => $v) {
            if ($v === '' || !isset($real_weights[$k]) || $real_weights[$k] === '') {
                continue;
            }
            $detail_mod->where(['order_id' => $order_id, 'store_id' => $store_id, 'sell_type' => 2, 'id' => $v])->save(['real_weight' => $real_weights[$k]]);
        }
        $this->returnCode(0, []);
    }

    public function checkPickRule($pick_id){
        $data = true;
        if($this->staff_session['pick_addr_ids']){
            $pick_addr_ids = explode(',',$this->staff_session['pick_addr_ids']);
            if(!in_array($pick_id,$pick_addr_ids)){
                $data = false;
            }
        }
        return $data;
    }

    public function take_order(){
        $id = intval($_POST['order_id']);
        $db = M('Shop_order');
        $info = $db->where(['order_id'=>$id])->find();
        if(empty($info)){
            $this->returnCode(1,'','订单不存在');
        }
        if($info['order_status']<>0 || ($info['status']<>0 && $info['status']<>7)|| $info['paid']<>1){
            $this->returnCode(1,'','订单状态不正确');
        }
        //验证是否有提交权限
        if($info['is_pick_in_store']==2&&!$this->checkPickRule($info['pick_id'])){
            $this->returnCode(1,'','没有分配当前自提点权限!');
        }
        $r = $db->where(array('order_id' => $info['order_id']))->save(array('order_status' => 1, 'last_time' => time()));
        if($r){
            $log_date = [
                'order_id' =>  $info['order_id'],
                'status'   =>  2,
                'name'     =>  $this->staff_session['username'],
                'phone'    =>  $this->staff_session['tel'],
                'note'     =>  L_('订单已确认，商家备货中')
            ];
            D('Shop_order_log')->add_log($log_date);
            if($info['is_pick_in_store']==2){//自提的订单
                $log_date = [
                    'order_id' =>  $info['order_id'],
                    'status'   =>  11,
                    'name'     =>  $this->staff_session['username'],
                    'phone'    =>  $this->staff_session['tel'],
                    'note'     =>  '',
                ];
                D('Shop_order_log')->add_log($log_date);
            }

            if($info['is_pick_in_store']==0) {//平台配送的订单,分配订单到配送表
                D('Deliver_supply')->auto_set_by_orderid($info['order_id']);
            }
            $this->returnCode(0,'','接单成功');
        }else{
            $this->returnCode(1,'','接单失败');
        }
    }

    //同意用户取消订单
    public function agree_cancle_apply()	{
        $order_id = intval($_POST['id']);
        $now_order = D("Shop_order")->get_order_detail(array('order_id' => $order_id));
        $refund_rate = D("Shop_order")->get_period_refund_rate($now_order['order_id']);//适配周期购期数退款

        if(empty($now_order)){
            $this->returnCode(1,'','订单不存在');
        }
        if($this->config['open_allinyun']==1){
            import('@.ORG.AccountDeposit.AccountDeposit');
            $deposit = new AccountDeposit('Allinyun');
            $allyun = $deposit->getDeposit();

            $allinyun_user = D('Deposit')->allinyun_user($now_order['uid']);

            if($allinyun_user['bizUserId']!=''){
                $allyun->setUser($allinyun_user);
                $params['bizOrderNo'] = 'shop_'.$now_order['orderid'];
                $params['oriBizOrderNo'] = $now_order['third_id'];
                $params['amount'] = intval($now_order['payment_money']*100);
                $res = $allyun->refundApply($params);

                if($res['status']=='OK'){
                    $data_shop_order['order_id'] = $now_order['order_id'];
                    $data_shop_order['status'] = 4;
                    $data_shop_order['last_time'] = time();
                    $data_shop_order['refund_detail'] = serialize($res);
                    $return = $this->shop_refund_detail($now_order, $now_order['store_id']);
                    if ($return['error_code']) {
                        $this->error_tips($return['msg']);
                    }
                    D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 10));
                    D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 9));

                    $printHaddle = new PrintHaddle();
                    $printHaddle->printit($order_id, 'shop_order', 3);
                    D('Shop_order')->data($data_shop_order)->save();
                    //$this->success_tips(L_('退款成功'), 'javascript:history.go(-2);');
                    $this->returnCode(0,'','退款成功');
                }else{
                    //$this->error_tips(L_('退款失败') . '，' . $res['message']);
                    $this->returnCode(1,'',$res['message']);
                }
            }
        }

        $now_merchant = D('Merchant')->get_info($now_order['mer_id']);

        $store_id = $now_order['store_id'];
        $this->mer_id = $now_order['mer_id'];
        if (!($now_order['paid'] == 1 && ($now_order['status'] == 11))) {
            //$this->error_tips(L_('当前订单店员正在处理中，不能退款或取消'));
            //exit(json_encode(['status'=>0,'info'=>'当前订单店员正在处理中，不能退款或取消']));
            $this->returnCode(1,'','当前订单店员正在处理中，不能退款或取消');
        }
        if (empty($now_order['paid'])) {
            $this->returnCode(1,'','订单未付款');
        }

        $mer_store = D('Merchant_store')->where(array('mer_id' => $now_order['mer_id'], 'store_id' => $now_order['store_id']))->find();
        $my_user = D('User')->field(true)->where(array('uid' => $now_order['uid']))->find();

        //线下支付退款
        $data_shop_order['cancel_type'] = 5;//取消类型（0:pc店员，1:wap店员，2:andriod店员,3:ios店员，4：打包app店员，5：用户（），6：配送员, 7:超时取消）
        if ($now_order['pay_type'] == 'offline') {
            $data_shop_order['order_id'] = $now_order['order_id'];
            $data_shop_order['refund_detail'] = serialize(array('refund_time' => time()));
            $data_shop_order['status'] = 4;
            if (D('Shop_order')->data($data_shop_order)->save()) {
                $return = $this->shop_refund_detail($now_order, $store_id);
                if ($return['error_code']) {
                    $this->error_tips($return['msg']);
                } else {
                    //用户取消订单  //对接叮叮配送===>郭先森
                    if($now_order['dingding_trade_no']!=''){
                        //处理叮叮取消订单======>郭先森
                        $merchantStore = D('Merchant_store')->where(array('store_id'=>$now_order['store_id'],'is_open_dingding'=>1))->find();
                        import('@.ORG.Dingding');
                        $getShopInfo = new Dingding();
                        $postData = array(
                            'headquarters_id'=>1,
                            'username'=>$merchantStore['dingding_user_name'],
                            'per_userid'=>$merchantStore['dingding_per_userid'],
                        );
                        $res = $getShopInfo->getShopInfo($postData);
                        $res = json_decode($res,true);
                        if($res['code'] == 0){
                            return array('error_code' => 1, 'msg' => L_('店鋪信息不存在,请联系开发者！'));
                        }
                        $dingDing = new Dingding(C('config.dingding_fromplatform'),C('config.dingding_form_app_key'),C('config.dingding_appid'),$res['result']['code'],$merchantStore['dingding_per_userid'],$merchantStore['dingding_user_name'],$merchantStore['dingding_pass'],$res['result']['phone']);
                        $postData = array(
                            'origin_id'=>$now_order['real_orderid'],
                            'cancel_id'=>4,
                            'cancelReason'=>'顾客取消订单',
                            'timestamp'     => time(),
                        );
                        $res = $dingDing->orderCancel($postData);

                        $res = json_decode($res,true);
                        fdump($res,"dingding---yonghu---orderCancel",true);
                    }
                    D('System_order')->save_order_status($now_order['order_from']=='1' ? 'mall' : 'shop' , $now_order['order_id'], 5);
                    //$this->success_tips(L_('您使用的是线下支付！订单状态已修改为已退款。'),U('Shop/status',array('order_id' => $now_order['order_id'], 'store_id' => $store_id, 'mer_id' => $this->mer_id)));
                    //exit(json_encode(['status'=>1,'info'=>'您使用的是线下支付！订单状态已修改为已退款。']));
                    $this->returnCode(1,'','您使用的是线下支付！订单状态已修改为已退款。');
                }
            } else {
                //$this->error_tips(L_('取消订单失败！请重试。'));
                //exit(json_encode(['status'=>0,'info'=>'取消订单失败！请重试。']));
                $this->returnCode(1,'','取消订单失败！请重试。');
            }
        } else {
            if ($now_order['payment_money'] != '0.00') {

                $payment_money_order = M('Payment_money_order')->where(array('order_id'=>$now_order['order_id'],'order_type'=>['IN',['shop','mall']],'pay_type'=>$now_order['pay_type'],'paid_money'=>array('gt',0)))->find();
                if($now_order['payment_money'] == $payment_money_order['payment_money']){
                    //如果是全退，那就把用户实际支付的钱一起退了吧。包括支付手续费
                    $now_order['payment_money'] = $payment_money_order['paid_money'];
                }

                if(C("config.is_refund_balance") == 1 && $payment_money_order){
                    //2019年6月17日，新增需求， 退款到余额 add by lumin
                    $alias_name=C('config.shop_alias_name');
                    if($now_order['order_from']==1){
                        $alias_name=C('config.mall_alias_name');
                    }
                    $result = D('User')->add_money($now_order['uid'],$payment_money_order['paid_money']*$refund_rate, L_("X1商品退款，增加余额，订单编号X2",array("X1" => $alias_name,"X2" => $now_order['real_orderid'])));
                    $go_refund_param = array('refund_time' => time());
                    if($result['error_code']){
                        $go_refund_param['err_msg'] = $result['msg'];
                    } else {
                        $go_refund_param['refund_id'] = $now_order['order_id'];
                        $go_refund_param['type'] = 'ok';
                    }
                }
                else{
                    if($payment_money_order && ($payment_money_order['pay_method'] == 'wft' || $payment_money_order['pay_method'] == 'paylinx' || $payment_money_order['pay_method'] == 'wepayez' || $payment_money_order['pay_method'] == 'weikebaba' || $payment_money_order['pay_method'] == 'xiaoma')){
                        if($payment_money_order['pay_method'] == 'wft'){
                            $pay_method = 'wftpay';
                        }
                        if($payment_money_order['pay_method'] == 'paylinx'){
                            $pay_method = 'paylinx';
                        }
                        if($payment_money_order['pay_method'] == 'wepayez'){
                            $pay_method = 'wepayez';
                        }
                        if($payment_money_order['pay_method'] == 'weikebaba'){
                            $pay_method = 'weikebaba';
                        }
                        if($payment_money_order['pay_method'] == 'xiaoma'){
                            $pay_method = 'xiaoma';
                        }
                        import('@.ORG.LowFeePay');
                        $lowfeepay = new LowFeePay($pay_method);
                        $now_order['order_type'] = 'shop';
                        $now_order['orderNo'] = $payment_money_order['orderNo'];
                        if($now_order['mer_id'] && $payment_money_order['pay_method'] == 'wft'){
                            $mer_juhe = M('Merchant_juhe_config')->where(array('mer_id'=>$now_order['mer_id']))->find();
                            if(!empty($mer_juhe)){
                                $lowfeepay->userId =$mer_juhe['userid'];
                                $now_order['is_own'] =1 ;
                                $now_order['orderNo'] =  $now_order['orderNo'].'_1';
                            }

                        }
                        if($now_order['mer_id'] && $payment_money_order['pay_method'] == 'weikebaba'){
                            $mer_juhe = M('Weikebaba')->where(array('mer_id'=>$now_order['mer_id']))->find();
                            if(!empty($mer_juhe)){
                                $lowfeepay->user_id = $mer_juhe['user_id'];
                                $lowfeepay->key = $mer_juhe['key'];
                                $now_order['is_own'] =1 ;
                                $now_order['orderNo'] =  $now_order['orderNo'];
                            }

                        }
                        $go_refund_param= $lowfeepay->refund($now_order);
                    }else if($this->config['open_juhepay']==1 &&( $now_order['pay_type']=='weixin' || $now_order['pay_type']=='alipay')){
                        import('@.ORG.LowFeePay');
                        $lowfeepay = new LowFeePay('juhepay');
                        $now_order['orderNo'] = 'shop_'.$now_order['orderid'];
                        if($now_order['mer_id']){
                            $mer_juhe = M('Merchant_juhe_config')->where(array('mer_id'=>$now_order['mer_id']))->find();
                            if(!empty($mer_juhe)){
                                $lowfeepay->userId =$mer_juhe['userid'];
                                $now_order['is_own'] =1 ;
                                $now_order['orderNo'] =  $now_order['orderNo'].'_1';
                            }

                        }
                        $go_refund_param= $lowfeepay->refund($now_order);

                    }else if($this->config['open_pufa_pay']==1 &&( $now_order['pay_type']=='weixin')){
                        import('@.ORG.LowFeePay');
                        $lowfeepay = new LowFeePay('pufapay');
                        $now_order['orderNo'] = 'shop_'.$now_order['orderid'];
                        $go_refund_param= $lowfeepay->refund($now_order);
                    }else if($payment_money_order && $payment_money_order['pay_method'] == 'scrcu'){
                        import("@.ORG.pay.Scrcu");
                        $pay = new scrcu();
                        
                        $scrcu_mer_no = '';
                        if($now_order['mer_id']){
                            $now_merchant = M("Merchant")->where(['mer_id'=>$now_order['mer_id']])->find();
                            if(!empty($now_merchant['scrcu_mer_no'])){
                                $scrcu_mer_no = $now_merchant['scrcu_mer_no'];
                            }
                        }
        
                        $go_refund_param= $pay->refund($now_order['orderid'], $payment_money_order['paid_money'], $scrcu_mer_no);
                    } else if ($payment_money_order['pay_method'] == 'chinaums') {
                        import("@.ORG.pay.Chinaums");
                        $pay = new Chinaums();
                        if ($payment_money_order['is_mobile_pay'] == 3) {
                            $mid = C("config.chinaums_mid_wxapp");
                        }
                        if ($payment_money_order['is_mobile_pay'] == 2) {
                            $mid = C("config.chinaums_mid_app");
                        }
                        if ($payment_money_order['is_mobile_pay'] == 1) {
                            $mid = C("config.chinaums_mid_wx");
                        }
                        $chinaums_merno = '';
                        if ($now_order['mer_id']) {
                            $now_merchant = M("Merchant")->where(['mer_id' => $now_order['mer_id']])->find();
                            if (!empty($now_merchant['chinaums_merno'])) {
                                $chinaums_merno = $now_merchant['chinaums_merno'];
                            }
                        }
                        $go_refund_param = $pay->refund($payment_money_order['order_type'] . '_' . $payment_money_order['orderid'], $payment_money_order['paid_money'], $payment_money_order['paid_money'], $payment_money_order['third_plat_data'], $chinaums_merno, $mid);
                    }else {
                        if ($now_order['is_own'] && $now_order['pay_type'] != 'nmgpay') {
                            $pay_method = array();
                            $merchant_ownpay = D('Merchant_ownpay')->field('mer_id', true)->where(array('mer_id' => $now_order['mer_id']))->find();
                            foreach ($merchant_ownpay as $ownKey => $ownValue) {
                                $ownValueArr = unserialize($ownValue);
                                if ($ownValueArr['open']) {
                                    $ownValueArr['is_own'] = true;
                                    $pay_method[$ownKey] = array('name' => $this->getPayName($ownKey), 'config' => $ownValueArr);
                                }
                            }
                            $now_merchant = D('Merchant')->get_info($now_order['mer_id']);
                            if ($now_merchant['sub_mch_refund'] && $this->config['open_sub_mchid'] && $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0) {
                                $pay_method['weixin']['config']['pay_weixin_appid'] = $this->config['pay_weixin_appid'];
                                $pay_method['weixin']['config']['pay_weixin_appsecret'] = $this->config['pay_weixin_appsecret'];
                                $pay_method['weixin']['config']['pay_weixin_mchid'] = $this->config['pay_weixin_sp_mchid'];
                                $pay_method['weixin']['config']['pay_weixin_key'] = $this->config['pay_weixin_sp_key'];
                                $pay_method['weixin']['config']['sub_mch_id'] = $now_merchant['sub_mch_id'];
                                $pay_method['weixin']['config']['pay_weixin_client_cert'] = $this->config['pay_weixin_sp_client_cert'];
                                $pay_method['weixin']['config']['pay_weixin_client_key'] = $this->config['pay_weixin_sp_client_key'];
                                $pay_method['weixin']['config']['is_own'] = 2;
                            }
                        } else {
                            $is_app = false;
                            if ((stripos($_SERVER['HTTP_USER_AGENT'], 'Android') || stripos($_SERVER['HTTP_USER_AGENT'], 'IOS')) && $now_order['pay_type'] == 'alipay') $is_app = true;
                            $pay_method = D('Config')->get_pay_method(0, 0, 1, $is_app);
                            if ($now_order['is_mobile_pay'] == 2) {
                                $pay_method['weixin'] = $pay_method['weixinapp'];
                            }
                        }

                        if (empty($pay_method)) {
                            //$this->error_tips(L_('系统管理员没开启任一一种支付方式！'));
                            $this->returnCode(1,'','系统管理员没开启任一一种支付方式！');
                        }

                        $pay_class_name = ucfirst($now_order['pay_type']);
                        if ($pay_class_name == 'Alipay' && $now_order['is_mobile_pay'] == 2 && $this->config['new_pay_alipay_app_public_key'] != '' && $this->config['new_pay_alipay_app_appid'] != '' && $this->config['new_pay_alipay_app_private_key'] != '') {
                            $pay_class_name = 'AlipayApp';
                            $pay_method['alipay']['config']['new_pay_alipay_app_appid'] = $this->config['new_pay_alipay_app_appid'];
                            $pay_method['alipay']['config']['new_pay_alipay_app_private_key'] = $this->config['new_pay_alipay_app_private_key'];
                            $pay_method['alipay']['config']['new_pay_alipay_app_public_key'] = $this->config['new_pay_alipay_app_public_key'];
                        }
                        if (in_array($now_order['pay_type'], ['hqpay', 'hqpay_wx', 'hqpay_al'])) {
                            $pay_class_name = 'Hqpay';
                            empty($pay_method[$now_order['pay_type']]) && $pay_method[$now_order['pay_type']] = 1;
                        }
                        if (empty($pay_method[$now_order['pay_type']])) {
                            //$this->error_tips(L_('您选择的支付方式不存在，请更新支付方式！'));
                            $this->returnCode(1,'','您选择的支付方式不存在，请更新支付方式！');
                        }

                        $import_result = import('@.ORG.pay.' . $pay_class_name);
                        if (empty($import_result)) {
                            //$this->error_tips(L_('系统管理员暂未开启该支付方式，请更换其他的支付方式'));
                            $this->returnCode(1,'','系统管理员暂未开启该支付方式，请更换其他的支付方式');
                        }
                        D('Shop_order')->where(array('order_id' => $now_order['order_id']))->save(array('is_refund' => 1));
                        $now_order['order_type'] = 'shop';
                        if($now_order['order_from'] ==1){
                            //商城
                            $now_order['order_type'] = 'mall';
                        }
                        $now_order['order_id'] = $now_order['orderid'];
                        if ($now_order['is_mobile_pay'] == 3) {
                            $pay_method[$now_order['pay_type']]['config'] = array(
                                'pay_weixin_appid' => $this->config['pay_wxapp_appid'],
                                'pay_weixin_key' => $this->config['pay_wxapp_key'],
                                'pay_weixin_mchid' => $this->config['pay_wxapp_mchid'],
                                'pay_weixin_appsecret' => $this->config['pay_wxapp_appsecret'],
                            );
                            C('config.pay_weixin_client_cert', $this->config['pay_wxapp_cert']);
                            C('config.pay_weixin_client_key', $this->config['pay_wxapp_cert_key']);
                            /*商家小程序自有支付*/
                            if($now_order['is_own']){
                                $now_merchant_wxapp_ownpay = M('Weixin_app_bind')->where(array('other_id'=>$now_order['mer_id']))->find();
                                $pay_method[$now_order['pay_type']]['config'] = array(
                                    'pay_weixin_appid' => $now_merchant_wxapp_ownpay['appid'],
                                    'pay_weixin_key' => $now_merchant_wxapp_ownpay['wxpay_key'],
                                    'pay_weixin_mchid' => $now_merchant_wxapp_ownpay['wxpay_merid'],
                                    'pay_weixin_appsecret' =>$now_merchant_wxapp_ownpay['appsecret'],
                                );
                                C('config.pay_weixin_client_cert', $now_merchant_wxapp_ownpay['public_key']);
                                C('config.pay_weixin_client_key', $now_merchant_wxapp_ownpay['private_key']);
                            }
                        }

                        $pay_class = new $pay_class_name($now_order, $now_order['payment_money']*$refund_rate, $now_order['pay_type'], $pay_method[$now_order['pay_type']]['config'], $this->user_session, 1);
                        $go_refund_param = $pay_class->refund();
                    }
                }
                $now_order['order_id'] = $order_id;
                $data_shop_order['order_id'] = $order_id;
                $data_shop_order['refund_detail'] = serialize($go_refund_param['refund_param']);
                if (empty($go_refund_param['error']) && $go_refund_param['type'] == 'ok') {
                    $data_shop_order['status'] = 4;
                }
                $data_shop_order['last_time'] = time();
                D('Shop_order')->data($data_shop_order)->save();
                if($data_shop_order['status'] != 4){
                    $this->returnCode(1,'',$go_refund_param);
                    //$this->returnCode(1,'',$go_refund_param['msg']);
                }else{
                    //增加erp生成退货单、负数收款单 2019/02/19
                    if(C('Config.open_yongyou_erp')==1){
                        D('Shop_order')->erp_after_pay($now_order,3);
                    }
                    $merchant_money=M('Merchant_money_list')->where(array('order_id' => $now_order['real_orderid'], 'income' => 1))->find();//钱进
                    $merchant_money1=M('Merchant_money_list')->where(array('order_id' => $now_order['real_orderid'], 'income' => 2))->find();//钱出
                    if ($now_order['paid']==1 && $now_order['order_from']==1 && !empty($merchant_money) && empty($merchant_money1) && $now_order['pay_type']!='offline') {
                        $desc="店员取消订单";
                        $returnSystemTake = $returnTakePercent = 0;
                        if($merchant_money['system_take'] > 0){
                            $returnSystemTake = bcmul(bcdiv($now_order['price'],$merchant_money['money'],2), $merchant_money['system_take'],2);
                            $returnTakePercent = $merchant_money['percent'];
                        }
                        D('Merchant_money_list')->use_money(
                            $now_order['mer_id'],
                            $now_order['price'],
                            "mall",
                            $desc,
                            $now_order['real_orderid'], 
                            $returnSystemTake, 
                            $returnTakePercent,
                            [],
                            0,
                            $go_refund_param['refund_param']??[]
                        );
                    }
                    $go_refund_param['msg'] = L_("在线支付退款成功") . ' ';
                }
            }


            $return = $this->shop_refund_detail($now_order, $store_id,$refund_rate);
            if ($return['error_code']) {
                //$this->error_tips($return['msg']);
                $this->returnCode(0,'',$return['msg']);
            } else {
                $go_refund_param['msg'] .= $return['msg'];
            }

            if (empty($now_order['pay_type'])) {
                $data_shop_order['order_id'] = $now_order['order_id'];
                $data_shop_order['status'] = 4;
                $data_shop_order['last_time'] = time();
                D('Shop_order')->data($data_shop_order)->save();
                //用户取消订单  //对接叮叮配送===>郭先森
                if($now_order['dingding_trade_no']!=''){
                    //处理叮叮取消订单======>郭先森
                    $merchantStore = D('Merchant_store')->where(array('store_id'=>$now_order['store_id'],'is_open_dingding'=>1))->find();
                    import('@.ORG.Dingding');
                    $getShopInfo = new Dingding();
                    $postData = array(
                        'headquarters_id'=>1,
                        'username'=>$merchantStore['dingding_user_name'],
                        'per_userid'=>$merchantStore['dingding_per_userid'],
                    );
                    $res = $getShopInfo->getShopInfo($postData);
                    $res = json_decode($res,true);
                    if($res['code'] == 0){
                        return array('error_code' => 1, 'msg' => L_('店鋪信息不存在,请联系开发者！'));
                    }
                    $dingDing = new Dingding(C('config.dingding_fromplatform'),C('config.dingding_form_app_key'),C('config.dingding_appid'),$res['result']['code'],$merchantStore['dingding_per_userid'],$merchantStore['dingding_user_name'],$merchantStore['dingding_pass'],$res['result']['phone']);
                    $postData = array(
                        'origin_id'=>$now_order['real_orderid'],
                        'cancel_id'=>4,
                        'cancelReason'=>'店员同意顾客取消订单',
                        'timestamp'     => time(),
                    );
                    $res = $dingDing->orderCancel($postData);

                    $res = json_decode($res,true);
                    fdump($res,"dingding---yonghu---orderCancel",true);
                }
                $go_refund_param['msg'] .= ' ' . L_('取消订单成功');
            }
            if(empty($go_refund_param['msg'])){
                $go_refund_param['msg'] .= ' ' . L_('取消订单成功');
            }
            D('System_order')->save_order_status($now_order['order_from']=='1' ? 'mall' : 'shop' , $now_order['order_id'], 5);
            D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 10,'note'=>'店员同意用户取消订单'));
            D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 9,'note'=>'店员同意用户取消订单'));
            $this->returnCode(0,'',$go_refund_param['msg']);
        }

    }

    private function shop_refund_detail($now_order, $store_id,$refund_rate=1)
    {
        $order_id  = $now_order['order_id'];

        $mer_store = D('Merchant_store')->where(array('mer_id' => $this->mer_id, 'store_id' => $store_id))->find();
        //业务别称
        $alias_name=C('config.shop_alias_name');
        $order_type = 'shop';
        if($now_order['order_from']==1){
            $alias_name=C('config.mall_alias_name');
            $order_type = 'mall';
        }

        $zbw_return = false;
        if($this->config['zbw_key']){

            $zbw_return = true;
            $now_user = D('User')->get_user($this->user_session['uid']);
            $result = D('ZbwErp')->VipRetSheet($now_user['zbw_cardid'],$now_order['balance_pay']*$refund_rate,$now_order['score_used_count']*$refund_rate,$now_order['real_orderid'], $alias_name. '商品退款，增加余额，订单编号' . $now_order['real_orderid']);
            if($result['result']){
                $go_refund_param['msg'] .= L_('退款成功！');
            }else{
                //$this->error_tips($result['err']);
                $this->returnCode(1,'',$result['err']);
            }
        }

        //如果使用了积分 2016-1-15
        if (!$zbw_return && $now_order['score_used_count'] != 0) {
            $result = D('User')->add_score($now_order['uid'],$now_order['score_used_count']*$refund_rate, $alias_name. '商品退款，' . C('config.score_name') . '回滚，订单编号' . $now_order['real_orderid']);
            $param = array('refund_time' => time());
            if ($result['error_code']) {
                $param['err_msg'] = $result['msg'];
            } else {
                $param['refund_id'] = $now_order['order_id'];
            }
            $data_shop_order['order_id'] = $now_order['order_id'];
            $data_shop_order['refund_detail'] = serialize($param);
            $result['error_code'] || $data_shop_order['status'] = 4;
            D('Shop_order')->data($data_shop_order)->save();
            if ($result['error_code']) {
                return $result;
                //$this->error_tips($result['msg']);
                $this->returnCode(1,'',$result['msg']);
            }
            $go_refund_param['msg'] .= ' '.$result['msg'];
        }
        //平台E卡退款
        if (!$zbw_return && $now_order['use_ecard_price'] != '0.00') {
            $desc=$alias_name. '商品退款，增加'.$this->config['ecard_alias_name'].'转退余额，订单编号' . $now_order['real_orderid'];
            $add_result = D('User')->add_money($now_order['uid'],$now_order['use_ecard_price']*$refund_rate,$desc);

            $param = array('refund_time' => time());
            if($result['error_code']){
                $param['err_msg'] = $result['msg'];
            } else {
                $param['refund_id'] = $now_order['order_id'];
            }

            $data_shop_order['order_id'] = $now_order['order_id'];
            $data_shop_order['refund_detail'] = serialize($param);
            $result['error_code'] || $data_shop_order['status'] = 4;
            D('Shop_order')->data($data_shop_order)->save();
            if ($result['error_code']) {
                return $result;
                $this->returnCode(1,'',$result['msg']);
            }
            $go_refund_param['msg'] .= $this->config['ecard_alias_name'].'退款成功';
        }
        //平台余额退款
        if (!$zbw_return && $now_order['balance_pay'] != '0.00') {

            $add_result = D('User')->add_money($now_order['uid'],$now_order['balance_pay']*$refund_rate, $alias_name . '商品退款，增加余额，订单编号' . $now_order['real_orderid']);

            $param = array('refund_time' => time());
            if($add_result['error_code']){
                $param['err_msg'] = $add_result['msg'];
            } else {
                $param['refund_id'] = $now_order['order_id'];
            }

            $data_shop_order['order_id'] = $now_order['order_id'];
            $data_shop_order['refund_detail'] = serialize($param);
            $result['error_code'] || $data_shop_order['status'] = 4;
            D('Shop_order')->data($data_shop_order)->save();
            if ($result['error_code']) {
                return $result;
                $this->returnCode(1,'',$result['msg']);
            }
            $go_refund_param['msg'] .= ' 平台余额退款成功';
        }
        //商家会员卡余额退款
        if ($now_order['merchant_balance'] != '0.00'||$now_order['card_give_money']!='0.00') {
            //$result = D('Member_card')->add_card($now_order['uid'],$now_order['mer_id'],$now_order['merchant_balance'],'退款 ' . $mer_store['name'] . '(' . $order_id . ')  增加余额');

            // 商家联盟会员卡
            $card_mer_id = $now_order['mer_id'];
            if ($now_order['union_mer_id']) {
                $card_mer_id = $now_order['union_mer_id'];
            }

            $result = D('Card_new')->add_user_money($card_mer_id,$now_order['uid'],$now_order['merchant_balance']*$refund_rate,$now_order['card_give_money']*$refund_rate,0,$alias_name . '商品退款，增加余额，订单编号' . $now_order['real_orderid'], $alias_name . '商品退款，增加赠送余额，订单编号' . $now_order['real_orderid']);


            $param = array('refund_time' => time());
            if ($result['error_code']) {
                $param['err_msg'] = $result['msg'];
            } else {
                $param['refund_id'] = $now_order['order_id'];
            }

            $data_shop_order['order_id'] = $now_order['order_id'];
            $data_shop_order['refund_detail'] = serialize($param);
            $result['error_code'] || $data_shop_order['status'] = 4;
            D('Shop_order')->data($data_shop_order)->save();
            if ($result['error_code']) {
                return $result;
                $this->returnCode(1,'',$result['msg']);
            }
            $go_refund_param['msg'] .= $result['msg'];
        }

        //退款打印
        $printHaddle = new PrintHaddle();
        $printHaddle->printit($now_order['order_id'], 'shop_order', 3);

// 		$msg = ArrayToStr::array_to_str($now_order['order_id'], 'shop_order');
// 		$op = new orderPrint($this->config['print_server_key'], $this->config['print_server_topdomain']);
// 		$op->printit($this->mer_id, $store_id, $msg, 3);

// 		$str_format = ArrayToStr::print_format($now_order['order_id'], 'shop_order');
// 		foreach ($str_format as $print_id => $print_msg) {
// 			$print_id && $op->printit($this->mer_id, $store_id, $print_msg, 3, $print_id);
// 		}

        $sms_data = array('mer_id' => $mer_store['mer_id'], 'store_id' => $mer_store['store_id'], 'type' => 'shop');
        if ($this->config['sms_shop_cancel_order'] == 1 || $this->config['sms_shop_cancel_order'] == 3) {
            $sms_data['uid'] = $now_order['uid'];
            $sms_data['mobile'] = $now_order['userphone'] ? $now_order['userphone'] : $my_user['phone'];
            $sms_data['sendto'] = 'user';
            $sms_data['content'] = '您在 ' . $mer_store['name'] . '店中下的订单(订单号：' . $now_order['real_orderid'] . '),在' . date('Y-m-d H:i:s') . '时已被您取消并退款，欢迎再次光临！';
            Sms::sendSms($sms_data);
        }
        if ($this->config['sms_shop_cancel_order'] == 2 || $this->config['sms_shop_cancel_order'] == 3) {
            $sms_data['uid'] = 0;
            $sms_data['mobile'] = $mer_store['phone'];
            $sms_data['sendto'] = 'merchant';
            $sms_data['content'] = '顾客' . $now_order['username'] . '的预定订单(订单号：' . $now_order['real_orderid'] . '),在' . date('Y-m-d H:i:s') . '时已被客户取消并退款！';
            Sms::sendSms($sms_data);
        }

        //退款时销量回滚
        if (($now_order['paid'] == 1 || $now_order['reduce_stock_type'] == 1) && $now_order['is_rollback'] == 0) {
            $goods_obj = D("Shop_goods");
            foreach ($now_order['info'] as $menu) {
                $goods_obj->update_stock($menu, 1);//修改库存
            }
            D('Shop_order')->where(array('order_id' => $now_order['order_id']))->save(array('is_rollback' => 1));
        }
        D("Merchant_store_shop")->where(array('store_id' => $now_order['store_id'], 'sale_count' => array('gt', 0)))->setDec('sale_count', 1);
        //退款时销量回滚

        $go_refund_param['error_code'] = false;

        // 系统订单
        D('System_order')->save_order_status($order_type, $order_id, 4);
        return $go_refund_param;
    }
	
	/* 停车场优惠券 */
	public function house_park(){
		if($_POST['store_id']){
			$store_id = intval($_POST['store_id']);
		}else{
			$store_id = $this->staff_session['store_id'];
		}
		
		$store_list = M('Park_coupons_shop')->alias('pcs')->join('left join '.C('DB_PREFIX').'house_village as `hv` on `hv`.`village_id`=`pcs`.`village_id`')->field('pcs.*,hv.village_name')->where(['pcs.bind_m_id' => $store_id, 'pcs.status' => '0'])->order('`m_id` ASC')->select();
		
		$this->returnCodeOk(['store_list' => $store_list]);
	}

    public function error_tips($tips,$time=2,$href=''){
        if($href == ''){
            $tips = '<font color=\"red\">'.$tips.'</font>';
            $href = 'javascript:history.back(-1);';
            $tips .= '<br/><br/>系统正在跳转到上一个页面。';
        }
        if($time != 2){
            $tips .= $time.'秒后会提示将自动关闭，可手动关闭！';
        }
        exit('<html><head><script>window.top.msg(0,"'.$tips.'",true,'.$time.');location.href="'.$href.'";</script></head></html>');
    }

    public function addUserNotice($order_id,$mer_id,$store_id,$uid,$title='',$content='',$business='shop')
    {
        //新增消息通知
        $url = '\app\common\model\service\user\UserNoticeService/addNotice';
        $param['type'] = 0;
        $param['business'] = $business;
        $param['order_id'] = $order_id;
        $param['mer_id'] = $mer_id;
        $param['store_id'] = $store_id;
        $param['title'] = $title ?: '商家手动取消订单';
        $param['content'] = $content ?: '商家手动取消订单，钱已退回，查看订单';
        $param['uid'] = $uid;
        invoke_v20_service($url, [$param]);
    }

    /**
     * 预约退款
     */
    public function appoint_reply_refund(){
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $refund_id = isset($_POST['refund_id']) ? intval($_POST['refund_id']) : 0;
        $type = isset($_POST['type']) ? $_POST['type'] : '';
        $reply_content = isset($_POST['reply_content']) ?trim($_POST['reply_content']) : '';

		$order = D('Appoint_order')->field(true)->where(array('order_id' => $order_id, 'store_id' => $this->staff_session['store_id']))->find();
		if (empty($order)) {
            $this->returnCode(1,'',L_('订单信息不存在'));
		}
		if (!in_array($type, array('agree', 'disagree'))) {
            $this->returnCode(1,'',L_('非法审核操作'));
		}

		$refund = D('Appoint_order_refund')->field(true)->where(array('order_id' => $order_id, 'id' => $refund_id,'status' => 0))->find();
		if (empty($refund)) {
            $this->returnCode(1,'',L_('退款申请不存在或已处理'));
		}

		if($type == 'agree'){//同意退款
			$refundMoney = $refund;
			$score_used_count=0;
			$score_deducte=0;

			if ($refundMoney['score_deducte']>0 ) {
				$score_used_count = $refundMoney['score_used_count'];
				$score_deducte = $refundMoney['score_deducte'];
			}

			$merchant_balance = 0;
			$card_give_money = 0;

			if ($refundMoney['card_give_money']>0 ) {
				$card_give_money = $refundMoney['card_give_money'];
			}
			if ($refundMoney['merchant_balance']>0 ) {
				$merchant_balance = $refundMoney['merchant_balance'];
			}

			$balance_pay = 0;
			if ($refundMoney['balance_pay'] > 0) {
				$balance_pay = $refundMoney['balance_pay'];
			}

			$balance_payment_money = 0; //余额支付定金
			if ($refundMoney['balance_payment_money'] > 0) {
				$balance_payment_money = $refundMoney['balance_payment_money'];
			}

			/*退还的平台E卡的金额*/
			if($refundMoney['ecard_pay']>0){
				$ecard_pay=$refundMoney['ecard_pay'];
			}
			$payment_money = 0;
			$third_refund_id = 0;


			if ($refundMoney['payment_money'] > 0) {

				$payment_money = $refundMoney['payment_money'];
                $pay_type = $order['product_pay_type'] ? $order['product_pay_type']: $order['pay_type'];
                $payment_money_order = M('Payment_money_order')->where(array('order_id'=>$order['orderid'],'order_type'=>'balance-appoint','pay_type'=>$pay_type,'paid_money'=>array('gt',0)))->find();
                if ($order['is_own']) {
					$pay_method = array();
					$merchant_ownpay = D('Merchant_ownpay')->field('mer_id', true)->where(array('mer_id' => $order['mer_id']))->find();
					foreach ($merchant_ownpay as $ownKey => $ownValue) {
						$ownValueArr = unserialize($ownValue);
						if ($ownValueArr['open']) {
							$ownValueArr['is_own'] = true;
							$pay_method[$ownKey] = array('name' => '', 'config' => $ownValueArr);
						}
					}
				} else {
                    if ($order['product_pay_type'] && $order['product_pay_type']=='alipayh5') {
                        $is_wap = 1;
                    } else {
                        $is_wap = $order['pay_type'] == 'alipayh5' ? 1 : 0;
                    }
					$pay_method = D('Config')->get_pay_method(0, 0, $is_wap, 0);
				}

                if (empty($pay_method)) {
                    $this->returnCode(1, '', L_('支付方式不存在或已关闭'));
                }
                if (empty($order['pay_type'])) {
                    if ($order['product_pay_type'] && empty($pay_method[$order['product_pay_type']])) {
                        $this->returnCode(1, '', L_('支付方式不存在或已关闭'));
                    }
                } else {
                    if ($order['pay_type'] && empty($pay_method[$order['pay_type']])) {
                        exit(json_encode(array('errcode' => 1, 'msg' => L_('支付方式不存在或已关闭'))));
                    }
                }

				if($payment_money_order && ($payment_money_order['pay_method'] == 'wft' || $payment_money_order['pay_method'] == 'paylinx' || $payment_money_order['pay_method'] == 'weikebaba')){
					if($payment_money_order['pay_method'] == 'wft'){
						$pay_method = 'wftpay';
					}
					if($payment_money_order['pay_method'] == 'paylinx'){
						$pay_method = 'paylinx';
					}
					if($payment_money_order['pay_method'] == 'weikebaba'){
						$pay_method = 'weikebaba';
					}
					import('@.ORG.LowFeePay');
					$lowfeepay = new LowFeePay($pay_method);
					$order['order_type'] = 'appoint';
					$order['orderNo'] = $payment_money_order['orderNo'];
					if($order['mer_id'] && $payment_money_order['pay_method'] == 'weikebaba'){
						$mer_juhe = M('Weikebaba')->where(array('mer_id'=>$order['mer_id']))->find();
						if(!empty($mer_juhe)){
							$lowfeepay->user_id = $mer_juhe['user_id'];
							$lowfeepay->key = $mer_juhe['key'];
							$order['is_own'] =1 ;
                        }
					}
					$go_refund_param= $lowfeepay->refund($order);
					fdump($go_refund_param,'lumin_refund',true);
                } else if ($order['product_pay_type'] || $order['pay_type']) {
					if($order['pay_type'] == ''){
						$pay_class_name = ucfirst($order['product_pay_type']);
                        $order['is_mobile_pay'] = $order['product_is_mobile_pay'];
					}else{
						$pay_class_name = ucfirst($order['pay_type']);
					}
					$import_result = import('@.ORG.pay.' . $pay_class_name);
					if (empty($import_result)) {
                        $this->returnCode(1, '', L_('支付方式不存在或已关闭'));
					}

					$order['order_type'] = 'appoint';
					$order['order_id'] = $order['orderid'];
					if ($order['order_from'] == 4) {
						$pay_method['weixin']['config'] = array(
							'pay_weixin_appid' => C('config.pay_wxapp_appid'),
							'pay_weixin_key' => C('config.pay_wxapp_key'),
							'pay_weixin_mchid' => C('config.pay_wxapp_mchid'),
							'pay_weixin_appsecret' => C('config.pay_wxapp_appsecret')
						);
						if ($order['is_own'] == 1) {
							$wxapp_own_pay = M('Weixin_app_bind')->where(array('other_id' => $_POST['own_pay_mer_id'], 'bind_type' => 0))->find();
							$pay_method['weixin']['config'] = array(
								'pay_weixin_appid' => $wxapp_own_pay['appid'],
								'pay_weixin_key' => $wxapp_own_pay['wxpay_key'],
								'pay_weixin_mchid' => $wxapp_own_pay['wxpay_merid'],
								'pay_weixin_appsecret' => $wxapp_own_pay['appsecret'],
								'is_own' => 1
							);
						}
					}

					$now_user = D('User')->get_user($order['uid']);
					if ($pay_class_name == 'Alipay' && $order['is_mobile_pay'] == 2 && C('config.new_pay_alipay_app_public_key') != '' && C('config.new_pay_alipay_app_appid')!= '' && C('config.new_pay_alipay_app_private_key')!= '') {
						$pay_class_name = 'AlipayApp';
						$pay_method['alipay']['config']['new_pay_alipay_app_appid'] = C('config.new_pay_alipay_app_appid');
						$pay_method['alipay']['config']['new_pay_alipay_app_private_key'] = C('config.new_pay_alipay_app_private_key');
						$pay_method['alipay']['config']['new_pay_alipay_app_public_key'] = C('config.new_pay_alipay_app_public_key') ;
					}
					$order['order_type'] = 'balance-appoint';
					$order['order_id'] = $order['orderid'];
					$order['third_id'] = '';
					$order['payment_money'] = $payment_money;

					$import_result = import('@.ORG.pay.' . $pay_class_name);
					if($order['pay_type'] == ''){
						$pay_class = new $pay_class_name($order, $payment_money, $order['product_pay_type'], $pay_method[$order['product_pay_type']]['config'], $now_user, 1);
					}else{
						$pay_class = new $pay_class_name($order, $payment_money, $order['pay_type'], $pay_method[$order['pay_type']]['config'], $now_user, 1);
					}
					fdump($pay_class,'weixin',1);
					$go_refund_param = $pay_class->refund();
					fdump($go_refund_param,'weixin',1);
				}

                if ($go_refund_param && !(empty($go_refund_param['error']) && $go_refund_param['type'] == 'ok')) {
                    $this->returnCode(1, '', $go_refund_param['msg']);
				} else {
					$third_refund_id = $go_refund_param['refund_param']['refund_id'];
				}
			}
			$mer_store = D('Merchant_store')->where(array('mer_id' => $order['mer_id'], 'store_id' => $order['store_id']))->find();

			$order_type='appoint';
			if ($score_used_count != 0) {
				$result = D('User')->add_score($order['uid'], $score_used_count,'预约退款, ' . C('config.score_name').'回滚,订单编号' . $order['real_orderid']);
				$param = array('refund_time' => time());
				if ($result['error_code']) {
					$score_used_count = 0;
				}
			}


			//定金
			if ($refundMoney['online_payment_money'] > 0) {

				$payment_money = $refundMoney['online_payment_money'];
				$payment_money_order = M('Payment_money_order')->where(array('order_id'=>$order['order_id'],'order_type'=>'appoint','pay_type'=>$order['pay_type'],'paid_money'=>array('gt',0)))->find();
				if ($order['is_own']) {
					$pay_method = array();
					$merchant_ownpay = D('Merchant_ownpay')->field('mer_id', true)->where(array('mer_id' => $order['mer_id']))->find();
					foreach ($merchant_ownpay as $ownKey => $ownValue) {
						$ownValueArr = unserialize($ownValue);
						if ($ownValueArr['open']) {
							$ownValueArr['is_own'] = true;
							$pay_method[$ownKey] = array('name' => '', 'config' => $ownValueArr);
						}
					}
				} else {
					$is_wap = $order['pay_type'] == 'alipayh5' ? 1 : 0;
					$pay_method = D('Config')->get_pay_method(0, 0, $is_wap, 0);
				}

				if (empty($pay_method)) {
                    $this->returnCode(1, '', L_('支付方式不存在或已关闭'));
				}
				if (empty($pay_method[$order['pay_type']])) {
                    $this->returnCode(1, '', L_('支付方式不存在或已关闭'));
				}

				if($payment_money_order && ($payment_money_order['pay_method'] == 'wft' || $payment_money_order['pay_method'] == 'paylinx' || $payment_money_order['pay_method'] == 'weikebaba')){
					if($payment_money_order['pay_method'] == 'wft'){
						$pay_method = 'wftpay';
					}
					if($payment_money_order['pay_method'] == 'paylinx'){
						$pay_method = 'paylinx';
					}
					if($payment_money_order['pay_method'] == 'weikebaba'){
						$pay_method = 'weikebaba';
					}
					import('@.ORG.LowFeePay');
					$lowfeepay = new LowFeePay($pay_method);
					$order['order_type'] = 'appoint';
					$order['orderNo'] = $payment_money_order['orderNo'];
					if($order['mer_id'] && $payment_money_order['pay_method'] == 'weikebaba'){
						$mer_juhe = M('Weikebaba')->where(array('mer_id'=>$order['mer_id']))->find();
						if(!empty($mer_juhe)){
							$lowfeepay->user_id = $mer_juhe['user_id'];
							$lowfeepay->key = $mer_juhe['key'];
							$order['is_own'] =1 ;
                        }
					}
					$go_refund_param= $lowfeepay->refund($order);
					fdump($go_refund_param,'lumin_refund',true);
				} else {
					fdump($order['pay_type'],'weixin',1);
					$pay_class_name = ucfirst($order['pay_type']);
					$import_result = import('@.ORG.pay.' . $pay_class_name);
					if (empty($import_result)) {
                        $this->returnCode(1, '', L_('支付方式不存在或已关闭'));
					}

					$order['order_type'] = 'appoint';
					$order['order_id'] = $order['orderid'];
					if ($order['order_from'] == 4) {
						$pay_method['weixin']['config'] = array(
							'pay_weixin_appid' => C('config.pay_wxapp_appid'),
							'pay_weixin_key' => C('config.pay_wxapp_key'),
							'pay_weixin_mchid' => C('config.pay_wxapp_mchid'),
							'pay_weixin_appsecret' => C('config.pay_wxapp_appsecret')
						);
						if ($order['is_own'] == 1) {
							$wxapp_own_pay = M('Weixin_app_bind')->where(array('other_id' => $_POST['own_pay_mer_id'], 'bind_type' => 0))->find();
							$pay_method['weixin']['config'] = array(
								'pay_weixin_appid' => $wxapp_own_pay['appid'],
								'pay_weixin_key' => $wxapp_own_pay['wxpay_key'],
								'pay_weixin_mchid' => $wxapp_own_pay['wxpay_merid'],
								'pay_weixin_appsecret' => $wxapp_own_pay['appsecret'],
								'is_own' => 1
							);
						}
					}

					$now_user = D('User')->get_user($order['uid']);
					if ($pay_class_name == 'Alipay' && $order['is_mobile_pay'] == 2 && C('config.new_pay_alipay_app_public_key') != '' && C('config.new_pay_alipay_app_appid')!= '' && C('config.new_pay_alipay_app_private_key')!= '') {
						$pay_class_name = 'AlipayApp';
						$pay_method['alipay']['config']['new_pay_alipay_app_appid'] = C('config.new_pay_alipay_app_appid');
						$pay_method['alipay']['config']['new_pay_alipay_app_private_key'] = C('config.new_pay_alipay_app_private_key');
						$pay_method['alipay']['config']['new_pay_alipay_app_public_key'] = C('config.new_pay_alipay_app_public_key') ;
					}
					$order['order_type'] = 'appoint';
					if($order['pay_type'] == 'alipayh5'){
						$order['order_id'] = $order['orderid'];
					}else{
						if(empty($order['orderids'])){
							$payment_money_order = M('Payment_money_order')->where(array('order_id'=>$order_id,'order_type'=>'appoint'))->find();
							$order['order_id'] = $payment_money_order['orderid'];
							$order['orderid'] = $payment_money_order['orderid'];
						}else{
							$order['order_id'] = $order['orderids'];
							$order['orderid'] = $order['orderids'];
						}
					}
					$order['third_id'] = '';
					$order['payment_money'] = $payment_money;

					$import_result = import('@.ORG.pay.' . $pay_class_name);
					$pay_class = new $pay_class_name($order, $payment_money, $order['pay_type'], $pay_method[$order['pay_type']]['config'], $now_user, 1);
					fdump($pay_class,'weixins',1);
					$go_refund_param = $pay_class->refund();
					fdump($go_refund_param,'weixins',1);
				}

				if (!(empty($go_refund_param['error']) && $go_refund_param['type'] == 'ok')) {
                    $this->returnCode(1, '', $go_refund_param['msg']);
				} else {
					$third_refund_id = $go_refund_param['refund_param']['refund_id'];
				}
			}
			$mer_store = D('Merchant_store')->where(array('mer_id' => $order['mer_id'], 'store_id' => $order['store_id']))->find();

			$order_type='appoint';
			if ($score_used_count != 0) {
				$result = D('User')->add_score($order['uid'], $score_used_count,'预约退款, ' . C('config.score_name').'回滚,订单编号' . $order['real_orderid']);
				$param = array('refund_time' => time());
				if ($result['error_code']) {
					$score_used_count = 0;
				}
			}

			//平台余额退款
			if ($balance_pay > 0) {
				$result = D('User')->add_money($order['uid'], $balance_pay, '预约退款,增加余额,订单编号' . $order['real_orderid']);
				fdump("22222",'test1',1);
				if($result['error_code']){
					$balance_pay = 0;
				}
			}

			if ($balance_payment_money > 0) {
				$result = D('User')->add_money($order['uid'], $balance_payment_money, '预约定金退款,增加余额,订单编号' . $order['real_orderid']);
				if($result['error_code']){
					$balance_pay = 0;
				}
			}
			//平台E卡退款返回余额
			if($ecard_pay>0){
				$result=D('User')->add_money($order['uid'],$ecard_pay,'预约退款，'.$this->config['ecard_alias_name'].'退还增加余额'.$order['real_orderid']);
				if($result['error_code']){
					$ecard_pay=0;
				}
			}

			//商家会员卡余额退款
			if ($merchant_balance > 0 || $card_give_money > 0) {
				$result = D('Card_new')->add_user_money($order['mer_id'], $order['uid'],  $merchant_balance, $card_give_money, 0,  '预约退款,增加会员卡赠送余额,订单编号' . $order['real_orderid'],'预约退款,增加会员卡赠送余额,订单编号' . $order['real_orderid']);
				if ($result['error_code']) {
					$merchant_balance = 0;
					$card_give_money = 0;
				}
			}
			$data = array();
			$data['reply_content'] = $reply_content;
			$data['reply_time'] = time();
			$data['status'] = 1;
			$data['score_deducte'] = $score_deducte;
			$data['score_used_count'] = $score_used_count;
			$data['balance_pay'] = $balance_pay;
			$data['ecard_pay']=$ecard_pay;
			$data['merchant_balance'] = $merchant_balance;
			$data['card_give_money'] = $card_give_money;
			$data['payment_money'] = $payment_money;
			//$data['third_refund_id'] = $third_refund_id;

			D('Appoint_refund_log')->add(array('refund_id' => $refund_id, 'status' => 1, 'dateline' => time(), 'note' => $reply_content));
			D('Appoint_order_refund')->where(array('order_id' => $order_id, 'id' => $refund_id))->save($data);

			$recordContent['content'] = [
				[
					'title' => '退款理由',
					'value' => $refund['reason'],
					'type'  => 'text'
				],[
					'title' => '退款时间',
					'value' => date('Y-m-d H:i:s'),
					'type'  => 'text'
				],
			];
			D('Appoint_order_records')->addOrderRecord($order['mer_id'], $order['order_id'], '订单退款', $recordContent);
			$saveData = array('status' => 4);
			$mdata = D('Merchant_money_list')->field('money,total_money,percent,type,system_take')->where(array('_string' => "type='appoint'",'income'=>1, 'order_id' => $order_id))->find();
			if ($mdata) {
				$totalMoney = floatval(round($mdata['money'], 2));
				if ($totalMoney<0) {
					$totalMoney=0;
				}
                $returnSystemTake = $returnTakePercent = 0;
                if($mdata['system_take'] > 0){
                    $returnSystemTake = bcmul(bcdiv($totalMoney,$mdata['money'],2), $mdata['system_take'],2);
                    $returnTakePercent = $mdata['percent'];
                }
				D('Merchant_money_list')->use_money(
                    $order['mer_id'], 
                    $totalMoney, 
                    $mdata['type'],
                    '预约退款,减少余额,订单编号' . $order['real_orderid'], 
                    $order_id, 
                    $returnTakePercent, 
                    $returnSystemTake,
                    [],
                    $order['store_id'],
                    $go_refund_param['refund_param']??[]
                );
			}
			D('Appoint_order')->where(array('order_id' => $order_id))->save(array('is_apply_refund'=>0,'paid'=>2));
			/*更新平台订单状态*/
            $saveData['system_status']=4;
			D('System_order')->save_order_info($order_type,$order_id,$saveData);

            $appointSupply = D('Appoint_supply')->where(['order_id' => $order_id])->find();
            if($appointSupply && $appointSupply['status'] == 2) {
                D('Appoint_supply')->where(['order_id' => $order_id])->save(['status' => 5]);// 未服务的退款标记一下 5 已退款
            }
			
			//增加消息通知
            $this->addUserNotice($order_id,$order['mer_id'],$order['store_id'],$order['uid'],'商家已经同意退款','您申请的退款商家已经同意，请查看订单详情','appoint');
            $this->returnCode(0, []);

		}else{
			//不同意
			M('Appoint_refund_log')->add(array('refund_id'=>$refund_id,'status'=>2,'dateline' => time(),'note'=>$reply_content));
			M('Appoint_order_refund')->where(array('order_id'=>$order_id,'id'=>$refund_id))->save(array('reply_time'=>time(),'reply_content'=>$reply_content,'status'=>2));
			//D('Appoint_order')->where(array('order_id' => $order_id, 'id' => $refund_id))->save(array('reply_time' => time(), 'reply_content' => $reply_content, 'status' => 2));
            $this->returnCode(0, []);
		}
    }

    public function get_ecard(){
        $phone = $_POST['phone'];
        if(empty($phone)){
            $this->returnCode(1,'',L_('手机号码不能为空'));
        }
        $card_list = M('internal_membership_card')->where(array('phone' => $phone, 'status' => 1))->select();
        if(empty($card_list)){
            $this->returnCode(1,'',L_('没有一卡通信息'));
        }
        if(!$_POST['password']){
            $card_data['data'] = [];
            $card_data['data']['need_password'] = true;
            $this->returnCode(0,$card_data['data']);
        }else{
            $password = $_POST['password'];
            if($card_list[0]['password'] != md5($password)){
                $this->returnCode(1,array(),L_('一卡通密码错误'));
            }
        }
        $total_money = 0;
        foreach($card_list as $card){
            $total_money += $card['balance_money'];
        }
        $this->returnCode(0, getFormatNumber($total_money));
    }

    public function send_post($url, $post_data) {
        $postData = http_build_query($post_data);
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type:application/x-www-form-urlencoded',
                'content' => $postData,
                'timeout' => 15 * 60 // 超时时间（单位:s）
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        return json_decode($result, true);
    }


    //店员收银重打小票
    public function reprint_store_order()
    {
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        if ($order_id < 1) {
            $this->returnCode(1, '', L_('参数有误'));
        }
        $printHaddle = new PrintHaddle();
        $printHaddle->printit($order_id, 'store_order', 1);
        $this->returnCode(0, []);
    }

    //店员退款
    public function refund_store_order()
    {
        $order_id = isset($_POST['order_id']) ? trim($_POST['order_id']) : 0;
        if (empty($order_id)) {
            $this->returnCode(1, '', L_('参数有误'));
        }

        $order = D('Store_order')->where(['orderid' => $order_id])->find();
        if (date('Ymd') != date('Ymd', $order['pay_time'])) {
            $this->returnCode(1, '', L_('订单已经隔天，店员不允许退款。如需退款请联系商家'));
        }

        try {
            D('Store_order')->refund_store_order($order_id, true, $this->staff_session['id'], '店员:' . $this->staff_session['name']);
            $this->returnCode(0, []);
        } catch (Exception $e) {
            $this->returnCode(1, '', $e->getMessage());
        }
    }

    //获取店内收银可用优惠券
    public function get_cash_coupons()
    {
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $uid = isset($_POST['uid']) ? intval($_POST['uid']) : 0;
        $store_order = D('Store_order')->where(['order_id' => $order_id])->find();
        if (empty($store_order)) {
            $this->returnCode(1, '', L_('订单信息获取失败'));
        }
        if ($uid) {
            $amount = $store_order['price'];
            $coupon_list = D('System_coupon')->get_user_coupon_list($uid, '', 1, 'cash');
            $tmp_order = [
                'total_money' => $amount,
                'order_money' => $amount,
                'uid' => $uid,
                'store_id' => $store_order['store_id'],
            ];
            $result = D('System_coupon')->getCanUseCoupon($tmp_order, $coupon_list, 'cash', 'app', 'system', []);
            $can_use_coupon_list = $result['can_use_coupon_list'] ?? [];
            //优惠券按照过期时间排序
            if (!empty($can_use_coupon_list)) {
                usort($can_use_coupon_list, function ($a, $b) {
                    return $a['end_time'] > $b['end_time'];
                });
            }
            //同样的优惠券合并数量
            $coupon_list = [];
            foreach ($can_use_coupon_list as $key => $value) {
                if (isset($coupon_list[$value['coupon_id']])) {
                    $coupon_list[$value['coupon_id']]['count'] += 1;
                } else {
                    if ($value['is_discount'] == 1) {
                        $value['show_discount'] = $value['name'] . '(' . $value['discount_value'] . '折)';
                    } else {
                        $value['show_discount'] = $value['name'] . '(减' . $value['discount'] . '元)';
                    }
                    $coupon_list[$value['coupon_id']] = $value;
                    $coupon_list[$value['coupon_id']]['count'] = 1;
                }
            }
            $coupon_list = array_values($coupon_list);
        } else {
            $coupon_list = array();
        }
        $this->returnCode(0, $coupon_list);
    }

    public function order_trace()
    {
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        if (empty($order_id)) {
            $this->returnCode(1, '', L_('参数有误'));
        }
        $order_trace = D('Appoint_order_records')->where(array('order_id' => $order_id))->order('id DESC')->select();
        foreach ($order_trace as &$v) {
            $v['content'] = json_decode($v['content'], true);
        }
        $this->returnCode(0, $order_trace);
    }
}
?>