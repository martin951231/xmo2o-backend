<?php
class ConfigAction  extends BaseAction{
    //返回小猪cms O2O系统所有配置
    public function index() {
		$appType = $_POST['app_type'] ?? '';
		if ($appType == '' && empty($_SERVER['HTTP_REFERER'])) {
			$this->returnCode('10000001');
		}
		if (strpos(C('config.site_url'),'www.group.com') === false && ($appType != 'wxapp' && $appType != 'dyapp' && $appType != 'street_wxapp') && $_SERVER['HTTP_REFERER'] && strpos($_SERVER['HTTP_REFERER'], C('config.site_url')) === false) {
			$this->returnCode('10000001');
		}

        if( ($this->config['site_close']==5  || $this->config['site_close'] == 3) && ($this->DEVICE_ID != 'wxapp' && $this->DEVICE_ID != 'street_wxapp' && $this->DEVICE_ID != 'wxapp_paotui')){
            $this->returnCode('60000002',array(),C('config.site_close_reason'));
        }
        $config    =   $this->config;
        $city_id   =   I('area_name');
        $app_type   =   I('app_type');
        $app_version    =   I('app_version');
    	if($city_id){
	        $city    =   $this->cityMatching($city_id);
        }else{
            $city    =   $this->nowCity($config['now_city']);
        }

        if($app_version){
			$arr['city']    =   $city;
        }else{
            if(strpos(strtolower($_SERVER['HTTP_USER_AGENT']),'android')){
                $arr['city']    =   $city;
            }else{
                $arr['city'][]    =   $city;
            }
        }
        if($config['many_city']){
			$arr['many_city']	=	$config['many_city'];
        }else{
			$arr['many_city']	=	0;
        }
		$arr['config']['mobile_head_color']  =  preg_match("/^rgb/", $this->config['mobile_head_color']) ? RGBToHex($this->config['mobile_head_color']) : $this->config['mobile_head_color'];
		if(!$arr['config']['mobile_head_color']){
			$arr['config']['mobile_head_color'] = '#06c1ae';
		}

        $arr['config']['mobile_head_affect'] = $this->config['mobile_head_affect'];
        $arr['config']['mobile_head_floor_color']  =  preg_match("/^rgb/", $this->config['mobile_head_floor_color']) ? RGBToHex($this->config['mobile_head_floor_color']) : $this->config['mobile_head_floor_color'];
        if(!$arr['config']['mobile_head_floor_color']){
            $arr['config']['mobile_head_floor_color'] = '#06c1ae';
        }
        $arr['is_new_block_nav']=0;
        $arr['config']['mobile_head_bg_img']='';
        $arr['config']['mobile_village_logo']='';
        $jsdhwy_new_wap_index=D('Config_customization')->getWapIndexJsdhwyUi();
        if($jsdhwy_new_wap_index>0) {
            $arr['is_new_block_nav'] = 1;
            $mobile_head_bg_img=$this->config['mobile_head_bg_img'] ? replace_file_domain($this->config['mobile_head_bg_img']):'';
            $arr['config']['mobile_head_bg_img']=$mobile_head_bg_img;
            $mobile_village_logo=$this->config['mobile_village_logo'] ? replace_file_domain($this->config['mobile_village_logo']):'';
            $arr['config']['mobile_village_logo']=$mobile_village_logo;
        }

        // 商家小程序
        if($_POST['wxapp_own_mer_id']){
        	// 设置导航栏主题色
        	$now_bind = M('Weixin_app_bind')->where(array('bind_type'=>0,'other_id'=>$_POST['wxapp_own_mer_id']))->find();
			$condition_page['page_id'] = $now_bind['mer_index_page'];
			$now_page = M('Merchant_store_diypage')->where($condition_page)->find();
			if($now_page['menu_bgcolor']){
				$arr['config']['mobile_head_color'] = $now_page['menu_bgcolor'];
			}
        }
        $arr['config']['zhibo_version'] = empty($this->config['zhibo_version'])?0:$this->config['zhibo_version']*1;//直播样式，1新版样式
		$arr['config']['close_estart_time']   =   $this->config['close_estart_time'];// 关闭直播预开始时间
		//模板类型
		$arr['config']['mobile_index_tpl'] = $this->config['mobile_index_tpl'];

		$arr['config']['have_plat']   =  true;
		$arr['config']['have_shop']   =  true;
		$arr['config']['have_merchant']   =  true;

		$arr['config']['is_demo_domain']   =   $this->config['is_demo_domain'] ? true : false;

		$arr['config']['open_voice_search'] = $this->config['open_voice_search'];	//是否开启语音
		$arr['config']['have_appoint'] 		= $this->config['appoint_page_row'] ? 1 : 0;
        $arr['config']['group_alias_name']  =   $this->config['group_alias_name'];
        $arr['config']['meal_alias_name']   =   $this->config['meal_alias_name'];
		$arr['config']['shop_alias_name']   =   $this->config['shop_alias_name'];
		$arr['config']['cash_alias_name']   =   $this->config['cash_alias_name'];
		$arr['config']['appoint_alias_name']   =   $this->config['appoint_alias_name'];
		$arr['config']['gift_alias_name']   =   $this->config['gift_alias_name'];
		$arr['config']['mall_alias_name']   =   $this->config['mall_alias_name'];
		$arr['config']['classify_name']   =   $this->config['classify_name'];
		$arr['config']['house_show_center']   =   $this->config['house_show_center'];
        $arr['config']['group_index_name']   =   $this->config['group_index_name'];
		$arr['config']['house_village_group_name']   =   $this->config['house_village_group_name']; // 社区团购别名

		$arr['config']['user_is_real_name']   =   $this->config['user_is_real_name'] ? true : false;	//是否开启实名认证和车主认证
		$arr['config']['user_auth_notneed_img']   =   $this->config['user_auth_notneed_img'] ?? '0';

		//是否开启多语言
		$arr['config']['open_multilingual']   =   $this->config['open_multilingual'] ? $this->config['open_multilingual'] : '0';
		$arr['config']['lang_config'] = [
			'lang_list' => [],
			'now_lang' => C('config.default_language') ? C('config.default_language') : 'chinese'
		];
		//赋个默认值，不然在没开启多语言的时候会出现一些问题
		if($arr['config']['open_multilingual']){
			if($_POST['Device-Id'] == 'wxapp' && (($_POST['isLaunch'] && $_POST['pagePath'] == 'pages/webview/tab_webview') || ($_POST['isLaunch'] && $_POST['pagePath'] == 'pages/shop_new/shopDetail/shopDetail'))){
				$arr['config']['lang_config']['now_lang'] = 'chinese';
				C('config.system_lang', 'chinese');
				$_POST['app_language'] = 'zh-cn';
			}

			if($_POST['app_language']){
				$_SERVER['HTTP_ACCEPT_LANGUAGE'] = $_POST['app_language'];
			}
			$arr['config']['lang_config'] = D('Lang')->lang_list();//语言配置

			C('config.system_lang',$arr['config']['lang_config']['now_lang']);

			if($_POST['special_lang']){
				$lang = new lang();
				$arr['config']['special_lang_list'] = $lang->get_special_lang($_POST['special_lang']);
			}
		}

		//是否支持国际手机
		$arr['config']['international_phone'] = $this->config['international_phone'] ? true : false;

		//列表页面是否展示配送费
		$arr['config']['shop_show_delivermoney'] = $this->config['shop_show_delivermoney'] ? true : false;

		//地图类型
		$arr['config']['map_config'] = $this->config['map_config'] ? $this->config['map_config'] : 'baidu';
		$arr['config']['baidu_map_ak'] = $this->config['baidu_map_ak'] ? $this->config['baidu_map_ak'] : '';
		$arr['config']['google_map_ak'] = $this->config['google_map_ak'] ? $this->config['google_map_ak'] : '';
		$arr['config']['google_map_position'] = $this->config['google_map_position'] ? true : false;	// true为谷歌地图不需要位置，而是需要坐标
		$arr['config']['country_code'] = $this->config['country_code'] ? $this->config['country_code'] : '';

		//货币符号，单位
		$arr['config']['Currency_txt'] = L_($this->config['Currency_txt']);
		$arr['config']['Currency_symbol'] = $this->config['Currency_symbol'];

		//平台配送别名
		$arr['config']['deliver_name'] = $this->config['deliver_name'] ? $this->config['deliver_name'] : L_('平台配送');

        $arr['config']['appoint_worker_name'] = $this->config['appoint_worker_name'] ?? L_('技师');
		//社区配置项
		$arr['config']['house_name'] = isset($this->config['house_name']) && $this->config['house_name'] ? $this->config['house_name'] : L_('小区');
		$arr['config']['house_market_name'] = isset($this->config['house_market_name']) && $this->config['house_market_name'] ? $this->config['house_market_name'] : L_('社区超市');

		$arr['config']['wxapp_paotui_appid'] = isset($this->config['pay_wxapp_paotui_appid']) && $this->config['pay_wxapp_paotui_appid'] ? $this->config['pay_wxapp_paotui_appid'] : '';
		//是否开启小区列表图片显示模式
		$arr['config']['open_house_logo_show'] = isset($this->config['open_house_logo_show']) && $this->config['open_house_logo_show'] ? $this->config['open_house_logo_show'] : 0;

        $arr['config']['worker_logo'] = $this->config['worker_logo'];
        $arr['config']['system_admin_logo'] = $this->config['system_admin_logo'];
        $arr['config']['site_logo']      =   $this->config['site_logo'];
        $arr['config']['site_phone']      =   $this->config['site_phone'];
		$arr['config']['wechat_share_img'] =   replace_file_domain($this->config['wechat_share_img']);

        $arr['config']['open_bbs'] = $this->config['village_group_index_bbs'];

        $arr['config']['open_bbs'] = $this->config['village_group_index_bbs'];

        if (1==$this->config['village_group_delivery_switch']) {
            // 社区拼团配送功能开关 开启了  默认 社区团购快递模式功能 关闭
            $this->config['village_group_express_open'] = 0;
        }
        $arr['config']['village_group_delivery_switch'] = $this->config['village_group_delivery_switch'];

        $arr['config']['village_group_start_buy_price'] = $this->config['village_group_start_price'];
        $arr['config']['village_group_express_open'] = $this->config['village_group_express_open'];

        //分润判断
        $arr['config']['open_score_fenrun']    =   isset($this->config['open_score_fenrun'])?$this->config['open_score_fenrun']:'';
        $arr['config']['fenrun_btn_location']    =   isset($this->config['fenrun_btn_location'])?$this->config['fenrun_btn_location']:'';
        $arr['config']['fenrun_award_url']    =   $this->config['site_url'].'/wap.php?c=Fenrun&a=user_free_award_list';
        $arr['config']['fenrun_money_url']    =   $this->config['site_url'].'/wap.php?c=Fenrun&a=fenrun_money_list';
        // 小程序管理
        $arr['config']['open_wxapp_manage']    =   $this->config['open_wxapp_manage'];
        $arr['config']['open_school'] = $this->config['open_school'] ? : 0;


		$arr['config']['open_address_pca']    =   intval($this->config['open_address_pca']);
        $pack_id = 0;
        $pack_info = [];
        if($_POST['Device-Id'] != 'wxapp') {
            // 单独处理APP包名信息
            if (isset($_POST['app_package_name']) && trim($_POST['app_package_name'])) {
                $app_package_name = trim($_POST['app_package_name']);
                $db_list = D('Appapi_pack_list');
                if (isset($_POST['app_type']) && $_POST['app_type'] && 1 == $_POST['app_type']) {
                    // ios
                    $where_pack_list = [
                        'package_name' => $app_package_name
                    ];
                } elseif (isset($_POST['app_type']) && $_POST['app_type'] && 2 == $_POST['app_type']) {
                    // 安卓
                    $where_pack_list = [
                        'package_name2' => $app_package_name
                    ];
                } else {
                    // 或查询
                    $where_pack_list = [
                        'package_name|package_name2' => $app_package_name
                    ];
                }
                $pack_info = $db_list->where($where_pack_list)->find();
                if ($pack_info) {
                    $pack_id = $pack_info['pack_id'];
                }
            }
        }

		if($_POST['Device-Id'] != 'wxapp'){
		    if ($pack_info && $pack_info['weixinapp_appid'] && $pack_info['weixinapp_appsecret']) {
                $pay_weixinapp_appid = $pack_info['weixinapp_appid'];
                $pay_weixinapp_appsecret = $pack_info['weixinapp_appsecret'];
            } else {
                $pay_weixinapp_appid = isset($this->config['pay_weixinapp_appid']) ? $this->config['pay_weixinapp_appid'] : '';
                $pay_weixinapp_appsecret = isset($this->config['pay_weixinapp_appsecret']) ? $this->config['pay_weixinapp_appsecret'] : '';
            }

			$arr['pay_share']['pay_weixinapp_key']    =    isset($this->config['pay_weixinapp_key']) ? $this->config['pay_weixinapp_key']: '';
			$arr['pay_share']['pay_weixinapp_appsecret']    =   $pay_weixinapp_appsecret;
			$arr['pay_share']['pay_weixinapp_appid']    =   $pay_weixinapp_appid;
			$arr['pay_share']['pay_weixinapp_mchid']    =   isset($this->config['pay_weixinapp_mchid'])?$this->config['pay_weixinapp_mchid']:'';
        }

		$arr['wap_around_show_type']  = intval($this->config['wap_around_show_type']);
        $arr['config']['house_position']    =   isset($this->config['house_position'])?$this->config['house_position']:1;

		if($_POST['Device-Id'] != 'wxapp'){
            if(isset($_POST['app_package_name']) && trim($_POST['app_package_name'])) {
                $appConfig = D('Appapi_app_config')->get('',$pack_id);
            } else {
                $appConfig  =   D('Appapi_app_config')->get('', 1);
            }
			foreach($appConfig as $k=>$v){
				if(in_array($k, ['platform_icp','ios_version', 'android_version', 'about', 'rules', 'ios_download_url', 'android_download_url', 'ios_version_code', 'android_version_code', 'android_version_desc', 'ios_version_desc', 'ios_must_upgrade', 'android_must_upgrade'])){
                    if($k == 'ios_version_desc'){
                        $arr['appConfig'][$k]   =   $v;
                    } else if (in_array($k, ['ios_download_url', 'android_download_url'])) {
                        $arr['appConfig'][$k] = $v ? replace_file_domain($v) : '';
                    } else{
                        $arr['appConfig'][$k]   =   nl2br($v);
                    }
				}
			}

			$ios_app_list =  M('App_other')->select();
			$arr['ios_app_list'] = $ios_app_list ? $ios_app_list : array();

			//app端底部显示ICP备案显示
			$arr['app_show_footer'] = [];
			if (isset($arr['appConfig']['platform_icp']) && $arr['appConfig']['platform_icp']) {
				$arr['app_show_footer'][] = [
					'title' => 'ICP备案号：' . $arr['appConfig']['platform_icp'],
					'icon' => '',
					'url' => 'https://beian.miit.gov.cn'
				];
			}

			// 隐私政策
			$arr['privacy_policy_md5'] = $appConfig['privacy_policy'] ? md5($appConfig['privacy_policy']) : '';
			$arr['privacy_policy_url'] = $appConfig['privacy_policy'] ? $this->config['site_url'].'/wap.php?c=Login&a=privacy_policy&pack_id='.$pack_id : '';

			// 注册协议
			if($_POST['app_type'] == 1 || $_POST['app_type'] == 2){
				//app端
				$arr['register_agreement_md5'] = md5($appConfig['user_agreement']??'');
				$arr['register_agreement_url'] = $this->config['site_url'].'/wap.php?c=Login&a=user_agreement&type=plat';
			}else{
				//非app端口
				$arr['register_agreement_md5'] = md5($this->config['register_agreement']);
				$arr['register_agreement_url'] = $this->config['site_url'].'/wap.php?c=Login&a=register_agreement';
			}
		}

        if(empty($arr)){
            $this->returnCode('20000002');
        }else if(empty($arr['appConfig'])){
            $arr['appConfig']   =   array();
        }else if(empty($arr['config'])){
            $arr['config']      =   array();
        }else if(empty($arr['city'])){
            $arr['city']    =   null;
        }
        if(empty($arr['appConfig'])){
            $arr['appConfig'] = (Object)array();
        }
        $arr['house']	=	array(
			'house_door'	=>	intval($this->config['house_door']),
			'house_open'	=>	$this->config['house_open'],
			'show_open_door_btn'=>	$this->config['show_open_door_btn'],
        );

		if($_POST['Device-Id'] != 'wxapp'){
			$arr['config']['pay_alipay_app_open'] = $this->config['pay_alipay_app_open'];
			$arr['config']['pay_alipay_app_pid'] =  isset($this->config['pay_alipay_app_pid']) ? $this->config['pay_alipay_app_pid']: '';
			$arr['config']['pay_alipay_app_appid'] =  isset($this->config['pay_alipay_app_appid']) ? $this->config['pay_alipay_app_appid']: '';
			$arr['config']['pay_alipay_app_count'] =  isset($this->config['pay_alipay_app_count']) ? $this->config['pay_alipay_app_count']: '';

			$arr['config']['new_pay_alipay_app_pid'] =  isset($this->config['new_pay_alipay_app_appid']) ? $this->config['new_pay_alipay_app_appid']: '';
			$arr['config']['new_pay_alipay_app_private_key'] =  isset($this->config['new_pay_alipay_app_private_key']) ? $this->config['new_pay_alipay_app_private_key']: '';
		}

        if($app_type==1){
            $arr['config']['pay_alipay_app_private_key'] =  isset($this->config['pay_alipay_app_private_key_ios']) ? $this->config['pay_alipay_app_private_key_ios']: '';
        }elseif($app_type==2){
            $arr['config']['pay_alipay_app_private_key'] =  isset($this->config['pay_alipay_app_private_key_android']) ? $this->config['pay_alipay_app_private_key_android']: '';
        }
        $arr['config']['pay_alipay_app_public_key'] =  isset($this->config['pay_alipay_app_public_key']) ? $this->config['pay_alipay_app_public_key']: '';


		//平台小程序参数
		$arr['config']['pay_wxapp_appid']    =   isset($this->config['pay_wxapp_appid'])?$this->config['pay_wxapp_appid'] : '';
		$arr['config']['shop_wxapp_share_img'] = isset($this->config['shop_wxapp_share_img'])?$this->config['shop_wxapp_share_img'] : '';
		$arr['config']['shop_wxapp_share_title'] = isset($this->config['shop_wxapp_share_title'])?$this->config['shop_wxapp_share_title'] : '';
		$arr['config']['wechat_name'] = $this->config['wechat_name'] ? : '';

		// 定制-发现功能
        $arr['config']['find_msg']  =   ($this->config['find_msg'] && $this->config['find_msg']>0) ? $this->config['find_msg'] : 0;

		$menu_category = M('Home_menu_category')->field('cat_id')->where(array('cat_key'=>'app_footer'))->find();
        if($menu_category){
            $footer_menu_list = M(_view('Home_menu'))->where(array('status'=>'1','cat_id'=>$menu_category['cat_id']))->order('`sort` DESC,`id` ASC')->limit(5)->select();
        }

		if($_POST['Device-Id'] != 'wxapp'){
			$arr['url_check_arr'] = array(
				array('key'=>'c=Foodshop&a=book_success')
				,array('key'=>'c=My')
				,array('key'=>'c=Shop&a=order_detail')
				,array('key'=>'c=Shop&a=status')
				,array('key'=>'c=Takeout&a=order_detail')
				,array('key'=>'c=Food&a=order_detail')
				,array('key'=>'c=Service&a=price_list')
				,array('key'=>'group_type=hotel')
				,array('key'=>'c=Group&a=detail')
				,array('key'=>'c=Groupnew&a=order_list')
				,array('key'=>'c=Group&a=shop')
				,array('key'=>'c=Shop&a=index')
				,array('key'=>'c=Shop&a=classic_good')
				,array('key'=>'c=Shop&a=classic_shop')
				,array('key'=>'c=Mall&a=detail')
				,array('key'=>'c=Diypage&a=page')
				,array('key'=>'c=Mall&a=store')
				,array('key'=>'c=Group&a=main_page')
				,array('key'=>'packapp/my/shop_order.html')
				,array('key'=>'Mall&a=group_order_detail')
				,array('key'=>'Mall&a=group_team_detail')
				,array('key'=>'c=Mall&a=status')
				,array('key'=>'c=Village_group&a=orderDetail')
			);

			//APP全屏广告
			$app_fullscreen_where['status'] = 1;
            $app_fullscreen_now_area = 0;
			if(C('customization.app_screen_opening_area')&&($_POST['now_area']||$_POST['now_city'])){
                $app_fullscreen_now_city = $_POST['now_city'];
                if(in_array($_POST['app_type'],['1','2'])){ //app进来
			        $area_info = D('Area')->where(['area_id'=>$_POST['now_city']])->find();
                    if($area_info&&$area_info['area_type']==3){
                        $app_fullscreen_now_city = $area_info['area_pid'];
                        $app_fullscreen_now_area = $_POST['now_city'];
                    }
                }else{ //H5
                    $app_fullscreen_now_area = $_POST['now_area'];
                }
            }else{
                $app_fullscreen_now_city = $this->config['now_city'];
            }
            $app_fullscreen_where['city_id'] = array('in', array(0, $app_fullscreen_now_city));
            if($app_fullscreen_now_area){
                $app_fullscreen_where['area_id'] = array('in', array(0, $app_fullscreen_now_area));
            }
			$today_zero_time = mktime(0, 0, 0, date('m', $_SERVER['REQUEST_TIME']), date('d', $_SERVER['REQUEST_TIME']), date('Y', $_SERVER['REQUEST_TIME']));
			$app_fullscreen_where['_string'] = 'end_time > ' . $_SERVER['REQUEST_TIME']. ' and begin_time < '. $_SERVER['REQUEST_TIME'];
			$app_fullscreen_adver_list = M('App_fullscreen_adver')->field('id,name,ios_pic_s,ios_pic_b,ios_pic_bb,android_pic,url,begin_time,end_time,play_time,android_pic_b')->where($app_fullscreen_where)->select();

			shuffle($app_fullscreen_adver_list);
			if (!empty($app_fullscreen_adver_list)) {
				foreach ($app_fullscreen_adver_list as &$v) {
					$v['ios_pic_s'] = replace_file_domain($v['ios_pic_s']);
					$v['ios_pic_b'] = replace_file_domain($v['ios_pic_b']);
					$v['ios_pic_bb'] = $v['ios_pic_bb'] ? replace_file_domain($v['ios_pic_bb']) : replace_file_domain($v['ios_pic_b']);
					$v['android_pic'] = replace_file_domain($v['android_pic']);
					$v['android_pic_b'] = replace_file_domain($v['android_pic_b']);
					$v['url'] = html_entity_decode($v['url']);
				}
			}

			$arr['app_fullscreen'] = $app_fullscreen_adver_list ? $app_fullscreen_adver_list[0] : (Object)array();
		}

		$arr['pay_url_check_arr'] = array(
			'c=Shop&a=status',
			'packapp/my/shop_order.html',
		);
        $appType = 0;
        if ($pack_info) {
            $appType = $pack_info['type'];
        }
        $village_id = I('village_id');
        if($this->wxapp_type == 'village' || $this->config['system_type'] == 'village' || ($appType && 2==$appType)){
			$house_village_footer_menu = M('House_village_footer_menu')->where(array('village_id'=>$village_id,'status'=>1))->limit(5)->order('sort desc')->select();
			$house_village_footer = [];
			if($house_village_footer_menu){
				foreach($house_village_footer_menu as $k=>$v){
                    $list['hover_pic_path'] = thumb_img('/upload/slider/'.$v['hover_pic_path'], 96, 96);
                    $list['name'] = $v['name'];
                    $list['pic_path'] = thumb_img('/upload/slider/'.$v['pic_path'], 96, 96);
                    $list['url'] = $v['url'];
                    $list['wxapp_id']='';
                    $list['wxapp_page']='';
                    if(isset($v['wxapp_id']) && !empty($v['wxapp_id'])){
                        $list['wxapp_id']=$v['wxapp_id'];
                        $list['wxapp_page']=$v['wxapp_page'];
                    }
					$house_village_footer[] = $list;
				}
				$arr['footer_menu_list'] =$house_village_footer;
			}else{
				$footer_menu_list = include('conf/village_footer_menu.php');
				if($this->config['house_show_center'] == 3){//不显示
					unset($footer_menu_list[2]);
				}elseif($this->config['house_show_center'] == 2){//社区团购
					$footer_menu_list[2] = array(
						'hover_pic_path' => $this->config['site_url']."/static/images/house/village_group2.png",
						'name' => empty($this->config['village_group_alias'])?'社区拼团':$this->config['village_group_alias'],
						'pic_path' => $this->config['site_url']."/static/images/house/village_group.png",
						'url' => $this->config['site_url']."/wap.php?c=House&a=go_villagegroup"
					);
				}elseif($this->config['house_show_center'] == 1){//社区超市
					$footer_menu_list[2] = array(
						'hover_pic_path' => $this->config['site_url']."/static/images/house/village_group2.png",
						'name' => '社区超市',
						'pic_path' => $this->config['site_url']."/static/images/house/village_group.png",
						'url' => $this->config['site_url']."/wap.php?c=House&a=go_housemarket"
					);
				}elseif($this->config['house_show_center'] == 0){//常用电话
					$base_url = D('House_village')->base_url;
					$footer_menu_list[2] = array(
						'hover_pic_path' => $this->config['site_url']."/static/images/house/phone2.png",
						'name' => '常用电话',
						'pic_path' => $this->config['site_url']."/static/images/house/phone.png",
						'url' => $this->config['site_url'] . $base_url . "pages/village/commonly_used/house_phone"
					);
				}
				$arr['footer_menu_list'] = array_values($footer_menu_list);
			}

			// if($_POST['app_type'] == '2'){
			// 	$arr['footer_menu_list'] = [$arr['footer_menu_list'][0]];
			// }
		}else{
			$footer_menu=array();
			foreach ($footer_menu_list as $key=>$v) {
				$tmp['type'] = $v['type'];
				$tmp['name'] = $v['name'];
				$tmp['url'] = $v['url'];
				$tmp['pic_path'] = thumb_img('/upload/slider/'.$v['pic_path'], 96, 96);
				$tmp['hover_pic_path'] = thumb_img('/upload/slider/'.$v['hover_pic_path'], 96, 96);

				// 小程序跳转到别的小程序
				if ($this->DEVICE_ID == 'wxapp' &&$v['wxapp_id']) {
					$tmp['url'] = 'wxapp://'.$v['wxapp_id'];
					if ($v['wxapp_page']) {
						// 小程序页面
						$tmp['url'] .= '://' . $v['wxapp_page'];
					}
					unset($v['wxapp_id'],$v['wxapp_page']);
				}

				$footer_menu[] = $tmp;
			}
			$footer_menu = replace_domain($footer_menu);

			// 默认导航
			if(empty($footer_menu)){
				$footer_menu = [
					[
		                "type"=>"0",
                        "name"=>"首页",
                        "url"=>C('config.site_url')."/wap.php?g=Wap&c=Home&a=index&no_house=1",
                        "pic_path"=>C('config.site_url')."/packapp/plat/static/images/footer_house.png",
                        "hover_pic_path"=>C('config.site_url')."/packapp/plat/static/images/footer_house_active.png"
                    ],
                    [
                        "type"=>"0",
                        "name"=>"外卖",
                        "url"=>C('config.site_url')."/packapp/plat/pages/shop_index/index",
                        "pic_path"=>C('config.site_url')."/packapp/plat/static/images/footer_home.png",
                        "hover_pic_path"=>C('config.site_url')."/packapp/plat/static/images/footer_home_active.png"
                    ],
                    [
                        "type"=>"0",
                        "name"=>"商家",
                        "url"=>C('config.site_url')."/packapp/plat/pages/plat_menu/store",
                        "pic_path"=>C('config.site_url')."/packapp/plat/static/images/footer_store.png",
                        "hover_pic_path"=>C('config.site_url')."/packapp/plat/static/images/footer_store_active.png"
                    ],
                    [
                        "type"=>"0",
                        "name"=>"我的",
                        "url"=>C('config.site_url')."/wap.php?g=Wap&c=My&a=index",
                        "pic_path"=>C('config.site_url')."/packapp/plat/static/images/footer_my.png",
                        "hover_pic_path"=>C('config.site_url')."/packapp/plat/static/images/footer_my_active.png"
                    ]
				];
			}

            if (isset($_POST['app_package_name']) && trim($_POST['app_package_name'])) {
                $app_package_name = trim($_POST['app_package_name']);
                $db_list = D('Appapi_pack_list');
                if (isset($_POST['app_type']) && 1 == $_POST['app_type']) {
                    // ios
                    $where_pack_list = ['package_name' => $app_package_name];
                } elseif (isset($_POST['app_type']) && 2 == $_POST['app_type']) {
                    // 安卓
                    $where_pack_list = ['package_name2' => $app_package_name];
                } else {
                    // 或查询
                    $where_pack_list = ['package_name|package_name2' => $app_package_name];
                }
                $appType = $db_list->where($where_pack_list)->getField('type');
                if ($appType == 3) {
                    $footer_menu = [
                        [
                            "type" => "0",
                            "name" => "跑腿",
                            "url" => C('config.site_url') . D('House_village')->base_url . 'pages/runErrands/index',
                            "pic_path" => C('config.site_url') . "/packapp/plat/static/images/footer_home.png",
                            "hover_pic_path" => C('config.site_url') . "/packapp/plat/static/images/footer_home_active.png"
                        ]
                    ];
                }
            }

			$arr['footer_menu_list']=$footer_menu;

			if($this->config['single_system_type'] == 'village_group'){
				$arr['footer_menu_list'] = array(
					array(
						'hover_pic_path' => $this->config['site_url']."/upload/slider/000/000/001/5c491becbb142608.png",
						'name' => $this->config['village_group_alias'],
						'pic_path' => $this->config['site_url']."/upload/slider/000/000/001/5c08eb0722e31708.png",
						'url' => $this->config['site_url']."/wap.php?g=Wap&c=Village_group&a=index"
					)
				);
			}
		}

        $hasVillage = $this->config['system_type'] == 'village' ? true : false;
        if (!$hasVillage) {
            $hasVillage = $_POST['wxapp_type'] == 'village' ? true : false;
        }
        if ($hasVillage && !empty($arr['footer_menu_list'])) {
            foreach ($arr['footer_menu_list'] as &$vItem) {
                //外卖首页
                $vItemUrl = $vItem['url'];
                if(stripos($vItemUrl, 'c=Shop&a=index') !== false || stripos($vItemUrl, 'pages/shop_index/index') !== false || stripos($vItemUrl, 'c=Shop&a=classic_index') !== false){
                    $vItem['url'] = $this->config['site_url'] . D('House_village')->base_url . 'pages/nofooter/shop/shop_index';
                }
            }
        }

		if($this->wxapp_type == 'village'){
			$village_id = I('village_id');
			$house_village = D('House_village')->where(array('village_id'=>$village_id))->find();
			$arr['footer_menu_bgcolor']  =  preg_match("/^rgb/", $house_village['app_footer_bgcolor']) ? RGBToHex($house_village['app_footer_bgcolor']) : $house_village['app_footer_bgcolor'];
			$arr['footer_menu_txtactivecolor']  =  preg_match("/^rgb/", $house_village['app_footer_txtactivecolor']) ? RGBToHex($house_village['app_footer_txtactivecolor']) : $house_village['app_footer_txtactivecolor'];
			$arr['footer_menu_txtcolor']  =  preg_match("/^rgb/", $house_village['app_footer_txtcolor']) ? RGBToHex($house_village['app_footer_txtcolor']) : $house_village['app_footer_txtcolor'];
		}else{
			$arr['footer_menu_bgcolor']  =  preg_match("/^rgb/", $this->config['app_footer_bgcolor']) ? RGBToHex($this->config['app_footer_bgcolor']) : $this->config['app_footer_bgcolor'];
			$arr['footer_menu_txtactivecolor']  =  preg_match("/^rgb/", $this->config['app_footer_txtactivecolor']) ? RGBToHex($this->config['app_footer_txtactivecolor']) : $this->config['app_footer_txtactivecolor'];
			$arr['footer_menu_txtcolor']  =  preg_match("/^rgb/", $this->config['app_footer_txtcolor']) ? RGBToHex($this->config['app_footer_txtcolor']) : $this->config['app_footer_txtcolor'];
		}
        $arr['config']['e_invoice_type']='';
		if($village_id>0){
            $e_config=M('House_e_config')->where(array('village_id'=>$village_id))->find();
            if($e_config['is_open']==1 || $e_config['is_tui']==1){
                $arr['config']['e_invoice_type']='szepiao_old';
            }
            $village_info = M('House_village_info')->where(array('village_id'=>$village_id))->find();
            $szepiao_cfg_info='';
            if($village_info && isset($village_info['szepiao_cfg_info']) && $village_info['szepiao_cfg_info']){
                $szepiao_cfg_info=$village_info['szepiao_cfg_info'] ? json_decode($village_info['szepiao_cfg_info'],true):'';
            }
            if($szepiao_cfg_info && isset($szepiao_cfg_info['szepiao_appid']) && $szepiao_cfg_info['szepiao_appid'] && $szepiao_cfg_info['szepiao_appsecret'] && $szepiao_cfg_info['szepiao_tax_no']){
                $arr['config']['e_invoice_type']='szepiao_new';
            }

        }
		$head_adver = D('Adver')->get_adver_by_key('app_index_top',1);
        $content_type = $this->config['guess_content_type'];
		$arr['is_app_adver'] = $head_adver ? true : false;
		$arr['home_like_type'] = $content_type;
		$arr['score_name'] = $this->config['score_name'];
		$arr['search_default_type'] =  $this->config['search_first_type']?$this->config['search_first_type']:'group';
        // 社群绑定手机开关 0 开  1关
		$arr['community_bind_phone_switch'] = ($this->config['community_bind_phone_switch'] && $this->config['community_bind_phone_switch']==1) ? 1:0;

		//小程序可打开的域名列表
		if ($_POST['Device-Id'] == 'wxapp') {
			$arr['wxapp_domain_list'] = S('wxapp_domain_list');
			if(!$arr['wxapp_domain_list']){
				$wxapp_domain_list = M('Wxapp_other_web')->field('domain')->select();
				foreach($wxapp_domain_list as $value){
					$arr['wxapp_domain_list'][] = $value['domain'];
				}
				S('wxapp_domain_list',$arr['wxapp_domain_list'],1800);
        	}
		}
        $arr['is_open_pay_for_others'] = $this->config['is_open_pay_for_others'] ? $this->config['is_open_pay_for_others']:0;

		//顶象验证码安全的appid
		$arr['dingxiang_captcha_appid'] = $this->config['dingxiang_captcha_appid'] ? $this->config['dingxiang_captcha_appid'] : '';
		$arr['qcloud_captcha_appid'] = $this->config['qcloud_captcha_appid'] ? $this->config['qcloud_captcha_appid'] : '';
		$arr['code_captcha_url'] = ($arr['dingxiang_captcha_appid'] || $arr['qcloud_captcha_appid']) ? $this->config['site_url'] . '/wap.php?c=Login&a=app_sms_safe' : '';

        //增加是否hark系统返回
        $arr['is_hark_system'] = $this->config['is_hark_system'] == 1 ? true : false;

		//是否支持手机号一键登录
		$arr['mobile_code_login'] = $this->config['mobile_code_login'] && $this->config['mobile_code_private_key'] ? true : false;


		if ($appType && 2==$appType) {
            $arr['config']['system_type'] = 'village';
        } else {
            $arr['config']['system_type'] = $this->config['system_type'] ? : 'plat';
        }


		//小程序是否只支持一键登录
		$arr['config']['program_mini_regtype'] = $this->config['program_mini_regtype'] ? : 1;
//		以上面判断为准 单独为APP做系统类型返回
//		$arr['config']['system_type'] = $this->config['system_type'] ? : 'plat';
		//是否使用了https
		$arr['config']['use_https'] = $this->config['use_https'] ? true : false;

		//外卖店铺列表展示商家注册入口
		$arr['config']['shop_show_merreg'] = $this->config['shop_show_merreg'] ? true : false;

		//是否展示店铺的销售数
		$arr['config']['merchant_not_show_sale_num'] = (!isset($this->config['merchant_show_sale_num']) || $this->config['merchant_show_sale_num']) ? false : true;

        //平台首页或者外卖首页最新订单状态轮询间隔
        $arr['order_status_interval'] = 30;
		//正则或匹配链接规则给到前端和APP，方便匹配原生页面
		$arr['url_rules'] = include('./conf/url_rules.php');

		// 注销按钮（删除账号）控制
        $arr['config']['login_cancel'] = $this->config['login_cancel'] == 1 ? true : false;
        $arr['config']['login_cancel_txt'] = $this->config['login_cancel_txt'] ? trim($this->config['login_cancel_txt']) : L_('注销账号');
		$arr['config']['life_tools']   =   C('customization.life_tools') ? true : false;	//城投生活通定制
        // 我的银行卡入口 控制 默认显示
        $arr['config']['my_bank_card_switch'] = (!isset($this->config['my_bank_card_switch']) || !$this->config['my_bank_card_switch']) ? true : false;
		//发布短视频是否强制绑定商品
		$arr['video_bind_goods'] = intval($this->config['video_bind_goods']);
        //APP端开启定位提示语
        $arr['open_location'] = is_null(C('config.open_location_on_homepage')) ? 1 : intval(C('config.open_location_on_homepage'));
        $arr['open_location_tip'] = C('config.open_location_on_homepage_tip') ? C('config.open_location_on_homepage_tip') : '授权定位可展示附近商家和服务';
		//是否单跑腿系统
		$arr['paotui_system'] = (C('config.system_type') == 'paotui');
		//商家中心店铺列表开启新版自定义页面
		$arr['config']['open_new_merchant_diypage'] = C('customization.open_new_merchant_diypage') ? 1 : 0;
		// 商城搜索商品样式
		$arr['config']['mall_search_goods_style'] = C('config.mall_search_goods_style') ?: 0;
		//外卖取单号的前缀
		$arr['config']['shop_fetch_prefix'] = '';// C('customization.shop_order_fetch_number_prefix'); 前端用不到了，不能返回
		$arr['config']['life_tools_scenic_alias_name'] = C('config.life_tools_scenic_alias_name') ?: 0;
		$arr['config']['life_tools_sports_alias_name'] = C('config.life_tools_sports_alias_name') ?: 0;

        $arr['config']['shop_goods_search_style'] = C('config.shop_goods_search_style') ?: 2;// 1 老版  2新版

        //定制-深圳市优你家新版商城用户下单地址与展示区域进行匹配，如非展示区域勾选区域，提示用户“超出可服务范围”，并无法下单。
        $arr['config']['open_mall_order_area'] = C('config.open_mall_order_area') ?: 0;

		//平台首页和外卖首页H5端口侧边栏导航
		$arr['config']['open_slide_bar'] = 1;

		//首页改版
		$arr['config']['unee_home_index_h5'] = C('customization.unee_home_index_h5') ?: 0;

        //人才系统弹窗配置
        $arr['popup'] = [
            'title' => $this->config['personnel_popup_title']?:'',
            'content' => $this->config['personnel_popup_content']?:'',
            'left_button_title' => $this->config['personnel_popup_left_title']?:'',
            'left_button_path' => $this->config['personnel_popup_left_path']?:'',
            'right_button_title' => $this->config['personnel_popup_right_title']?:'',
            'right_button_path' => $this->config['personnel_popup_right_path']?:'',
        ];

		$arr['runtime_env'] = $_POST['app_type'] == 'dyapp'?'douyin':'';
		$arr['shop_index_page_background_show'] =  $this->config['shop_index_page_background_show'] ?? 1;

		//用户推广二维码扫码强制登录  1：强制   0：不强制
		$arr['user_spread_force_login'] = intval(C('customization.user_spread_force_login'));

		//跑腿更多跳转
		$arr['config']['paotui_more_link'] = array(
			[
				'title' => L_('本地商城'),
				'desc'  => '',
				'icon'=>'icon-waimai1',
				'wx_appid'=>'',
				'path'  => $this->config['site_url'] . D('House_village')->base_url . 'pages/shopmall_third/shopmall_index'
			]
		);
		if(C('config.pay_wxapp_appid')){
			$wxapp = [
				'title' => L_('更多功能'),
				'desc'  => L_('本地团购外卖'),
				'icon'=>'icon-shezhi2',
				'wx_appid'=>C('config.pay_wxapp_appid'),
				'path'  => 'pages/index/index',
			];
			array_unshift($arr['config']['paotui_more_link'],$wxapp);
		}

        //是否公祭日主题
        $arr['is_public_memorial_day'] = intval(C('config.is_public_memorial_day'));
		//首页开启扫一扫
		$arr['mobile_index_scan'] = is_null(C('config.mobile_index_scan')) ? 1 : intval(C('config.mobile_index_scan'));
		//微信小程序隐私政策,目前只有小程序使用该字段
		if (C('config.privacy_policy') && C('config.register_agreement')) {
			$arr['privacy_policy'] = [
				'title' => '温馨提示',
				'content' => '欢迎使用“' . C('config.site_name') . '”，我们非常注重您的个人信息和隐私保护。在您使用产品服务前，请仔细阅读《服务使用协议》与《隐私与个人信息保护政策》，我们将按照您同意的条款使用您的个人信息，以便为您服务。如您同意此政策，请点击“同意”并开始使用我们的产品和服务，我们尽全力保护您的个人信息安全。',
				'policy_md5' => md5(C('config.privacy_policy') . C('config.register_agreement')),  //协议变更了，需要再次提醒
				'policy_items' => [
					[
						'name' => '《服务使用协议》',
						'url' => $this->config['site_url'] . '/wap.php?c=Login&a=register_agreement'
					],
					[
						'name' => '《隐私与个人信息保护政策》',
						'url' => $this->config['site_url'] . '/wap.php?c=Login&a=user_privacy_policy'
					]
				],
                'type' => 0, //0-默认样式，1-客户定制样式
                'type_name' => '',
			];
			if(C('customization.sqaeondelightjiangsu_customized')){
                $arr['privacy_policy']['type'] = 1;
                $arr['privacy_policy']['type_name'] = '永乐e生活-';
                $arr['privacy_policy']['content'] = '欢迎使用“' . C('config.site_name') . '”，我们非常注重您的个人信息和隐私保护。在您使用产品服务前，请仔细阅读《个人信息收集清单》《隐私政策》《用户注册协议》，我们将按照您同意的条款使用您的个人信息，以便为您服务。如您同意此政策，请点击“同意”并开始使用我们的产品和服务，我们尽全力保护您的个人信息安全。';
                $arr['privacy_policy']['policy_items'] = [];
                if(C('config.personal_information_collection')){
                    $arr['privacy_policy']['policy_items'][] = [
                        'name' => '《'.$arr['privacy_policy']['type_name'].'个人信息收集清单》',
                        'url'=>$this->config['site_url'] . '/wap.php?c=Login&a=personal_information_collection',
                        'short_name' => '《个人信息收集清单》',
                    ];
                }
                $arr['privacy_policy']['policy_items'][] = [
                    'name' => '《'.$arr['privacy_policy']['type_name'].'隐私政策》',
                    'url' => $this->config['site_url'] . '/wap.php?c=Login&a=user_privacy_policy',
                    'short_name' => '《隐私政策》',
                ];
                $arr['privacy_policy']['policy_items'][] = [
                    'name' => '《'.$arr['privacy_policy']['type_name'].'用户注册协议》',
                    'url' => $this->config['site_url'] . '/wap.php?c=Login&a=register_agreement',
                    'short_name' => '《用户注册协议》',
                ];
            }
		}

		//平台首页弹窗隐私政策、首页获取定位
		$arr['index_show_privacy_policy'] = is_null(C('config.index_show_privacy_policy')) ? 1 : intval(C('config.index_show_privacy_policy'));
		$arr['index_get_user_location'] = is_null(C('config.index_get_user_location')) ? 1 : intval(C('config.index_get_user_location'));

        $arr['config']['set_house_alias'] = C('customization.set_house_alias')==1 ? 1 : 0;
        $village_id = $village_id ?? 0;
        $arr['config']['house_name'] = D('House_village')->getHouseAlias('house_name',$village_id)?:L_('小区');
        $arr['config']['single_name'] = D('House_village')->getHouseAlias('single_name',$village_id)?:L_('楼栋');
        $arr['config']['floor_name'] = D('House_village')->getHouseAlias('floor_name',$village_id)?:L_('单元');
        $arr['config']['room_name'] = D('House_village')->getHouseAlias('room_name',$village_id)?:L_('房间');

        $plat_wxapp_id = C('config.pay_wxapp_appid');
        if ($plat_wxapp_id) {
            $arr['villageToPlatWxapp'] = [
                'plat_wxapp_id' => $plat_wxapp_id,
                'pay_wxapp_username' => C('config.pay_wxapp_username'),
                'village_page_rule' => [
                    'pages/houseManage', 'pages/houseMeter', 'pages/login',
                    'pages/newCharge', 'pages/parkingLot', 'pages/share',
                    'pages/street', 'pages/village', 'pages/village_menu',
                    'pages/village_my', 'pages/vote', 'wap.php?g=Wap&c=House',
                    'pages/my/myinfo', 'wap.php?g=Wap&c=Kefu&a=plat',
                ],
            ];
        }

        //商城为你推荐别名
        $arr['config']['mall_recommend_alias']    =   $this->config['mall_recommend_alias']?:'为你推荐';
        //下单页是否使用改版后的
        $arr['config']['order_revision'] = C('customization.order_revision') ?: 0;
        //团购活动推荐类型
        $arr['config']['group_activity_recommendations'] = C('config.group_activity_recommendations') ?: 0;
        $arr['config']['internal_membership_card'] = intval(C('customization.internal_membership_card'));//一卡通定制

        //预约导航位置
        $arr['config']['appoint_show_tab'] = $this->config['appoint_show_tab'] ?: 0;

        //直播间讲解商品样式参数
        $live_bottom_icon = $this->config['live_bottom_icon'];
        if($live_bottom_icon){
            $live_bottom_icon = replace_file_domain($live_bottom_icon);
            if(strpos($live_bottom_icon, 'https://') === false && strpos($live_bottom_icon, 'http://') === false){
                $live_bottom_icon = $this->config['site_url'].$live_bottom_icon;
            }
        }
        $arr['config']['live_product_style'] = [
            'live_banner_bottom_slogan' => $this->config['live_banner_bottom_slogan']?:'', //横幅底部宣传语
            'live_popular_name' => $this->config['live_popular_name']?:'', //抢手别称
            'live_buy_now_button_name' => $this->config['live_buy_now_button_name']?:'', //立刻抢购按钮别称
            'live_top_good_product_nickname' => $this->config['live_top_good_product_nickname']?:'', //商品顶部好货别称
            'live_bottom_icon' => $live_bottom_icon?:'', //上传抢购底部图标
            'live_banner_disappearance_time' => (int)$this->config['live_banner_disappearance_time'], //横幅弹出消失时间单位：s,0-一直显示
            'live_anchor_product_banners_on_screen' => (int)$this->config['live_anchor_product_banners_on_screen'], //主播上屏商品横幅展示
        ];

		//匹配第三方跳转h5地址，自动增加用户手机号和经纬度
		$arr['h5_url_matches'] = [
			'www.zhihuilantian.com/customh5/'
		];

		$arr['config']['tianditu_web_key'] = C('config.tianditu_web_key')?C('config.tianditu_web_key'):'';
        $arr['default_street_id']=0;
        $arr['default_street_name']=0;
        $streetWhere=array('area_id'=>array('gt',0),'is_open'=>1,'is_default'=>1);
        $streetInfo=M('Area_street')->where($streetWhere)->field('area_id,area_name')->find();
        if($streetInfo && $streetInfo['area_id']){
            $arr['default_street_id']= intval($streetInfo['area_id']);
            $arr['default_street_name']=$streetInfo['area_name'];
        }
		$arr['config']['shop_top_notice'] = C('config.shop_top_notice');

		//商场
		if (in_array(C('config.single_system_type'), ['shopping_mall', 'member'])) {
			$shoppingMall = M('Shopping_mall')->where(['status' => 1])->getField('id');
			$arr['config']['mall_id'] = $shoppingMall ?: 0;
		}
	    $arr['config']['social_comment_alias_name'] = C('config.social_comment_alias_name');
		$arr['config']['building_domain'] = C('config.building_domain');
        $village_footer_nav_show= C('config.village_footer_nav_show');
        $arr['config']['village_footer_nav_show'] =$village_footer_nav_show ? 1:0;

		//商城顶部是否跟着主题色变化
		$arr['config']['mall_follow_theme'] = C('config.mall_follow_theme') ? true : false;
		$arr['config']['mall_my_center_background'] = C('config.my_center_background') ? replace_file_domain(C('config.my_center_background')) : C('config.site_url') . '/static/wxapp/minebanner.png';
		$arr['config']['mall_order_detail_header_background'] = C('config.mall_order_detail_header_background') ? replace_file_domain(C('config.mall_order_detail_header_background')) : './static/orderstate.png';

		//网站缩略名
		$arr['config']['site_short_name'] = C('config.site_short_name') ? C('config.site_short_name') : C('config.site_name');
		$this->returnCode(0, $arr);
    }

    # 景区配置
    public function scenic_index(){
		$config    =   $this->config;
        if($config['many_city']){
			$arr['many_city']	=	$config['many_city'];
        }else{
			$arr['many_city']	=	0;
        }
        $arr['scenic_now_city'] = $config['scenic_now_city'];
    }

	/**
	 * 获取微信js配置信息
	 */
	public function wx_config(){
		$share = new WechatShare($this->config, '');
		$arr = $share->get_wx_config();

		if($_POST['work'] == 'storestaff'){
			switch($_POST['page']){
				case 'index':
					$arr['share'] = array(
						'title'=>L_('店员中心'),
						'content'=>'微信版店员中心-'.$this->config['site_name'],
						'url'=>$_POST['location_url']
					);
					break;
			}
		}else if($_POST['work'] == 'deliver'){
			switch($_POST['page']){
				case 'index':
				case 'tongji':
				case 'info':
					$arr['share'] = array(
						'title'=>L_('配送员中心'),
						'content'=>'微信版配送员中心-'.$this->config['site_name'],
						'url'=>$_POST['location_url']
					);
					break;
			}
		}else if($_POST['work'] == 'merchant'){
			switch($_POST['page']){
				case 'index':
				case 'tongji':
				case 'info':
					$arr['share'] = array(
						'title'=>L_('商家中心'),
						'content'=>'微信版商家中心-'.$this->config['site_name'],
						'url'=>$_POST['location_url']
					);
					break;
			}
        } else if ($_POST['work'] == 'share_coupon') {
            //分享抢券
            $orderId = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
            if ($_POST['share_coupon_show'] == 1) {
                $arr['share'] = [
                    'title' => L_($this->config['share_coupon_title']),
                    'content' => sprintf('%d张优惠券，快来抢啊!', intval($this->config['share_coupon_num'])),
                    'url' => C('config.site_url') . '/wap.php?c=Share_lottery&a=share_coupon&type=shop&order_id=' . $orderId
                ];
            } else {
                //新版订单详情
                if ($orderId > 0) {
                    $order = M('Shop_order')->alias('o')->join(C('DB_PREFIX') . 'merchant_store AS s ON o.store_id = s.store_id')->field('s.name,s.store_id,s.txt_info,s.logo')->where(['o.order_id' => $orderId])->find();
                    if ($order) {
                        $arr['share'] = [
                            'title' => L_($order['name']),
                            'content' => L_($order['txt_info']),
                            'url' => C('config.site_url') . '/wap.php?c=Shop&a=classic_shop&shop_id=' . $order['store_id'],
                            'image' => file_domain() . $order['logo'],
                        ];
                    }
                }
            }
        } else if ($_POST['work'] == 'my') {
            //屏蔽掉新版订单详情页面shop_order
            if ($_POST['page'] == 'shop_order') {
                $arr = [];
            }else if($_POST['page'] == 'index'){
				$url_arr = parse_url($_POST['location_url']);
				if($url_arr['query']){
					$urlQuery = convertUrlQuery($url_arr['query']);
					if($urlQuery['store_id']){
						$now_store = D('Merchant_store')->get_store_by_storeId($urlQuery['store_id']);
						if($now_store){
							$arr['share'] = [
								'title' => L_('推荐你一个店铺').'    「 ' . $now_store['name'] . ' 」',
								'content' => trim($now_store['txt_info']),
								'url' => C('config.site_url') . '/packapp/my/store/index.html?store_id=' . $now_store['store_id'],
								'image' => thumb_img($now_store['logo'] ? replace_file_domain($now_store['logo']) : replace_file_domain($now_store['all_pic'][0]), 200, 200, 'fill'),
							];
						}
					}
				}
				if(empty($arr['share'])){
                    $arr = [];
				}
			}
        }

		if($arr['share'] && empty($arr['share']['image'])){
			$arr['share']['image'] = $this->config['wechat_share_img'] ? $this->config['wechat_share_img'] : $this->config['site_url'].'/packapp/'.$_POST['work'].'/logo.png';
		}

		$this->returnCode(0, $arr);
	}

	public function log_report(){
		$filename = $_POST['file'] ? $_POST['file'] : 'file_log';
		fdump_api(['用户ID：'.$this->_uid, $_POST, $_SERVER['HTTP_REFERER']] , $filename, true);
		$this->returnCode(0, array());
	}

	public function wxapp_error_report(){
		fdump(date('Y-m-d H:i:s'),'api/log/'.date('Ymd').'/wxapp_error_log',true);
		fdump(intval($this->_uid),'api/log/'.date('Ymd').'/wxapp_error_log',true);
		fdump($_POST,'api/log/'.date('Ymd').'/wxapp_error_log',true);
		fdump($_SERVER['HTTP_REFERER'],'api/log/'.date('Ymd').'/wxapp_error_log',true);
		fdump('======================================','api/log/'.date('Ymd').'/wxapp_error_log',true);

		$this->returnCode(0, array());
	}

	//todo 蓝牙开门记录同步写入house_user_log表
	private function userLogAdds($param){
	    $door=M('house_village_door')->where(['door_device_id'=>$param['door_device_id']])->find();
	    if(empty($door)){
	        return false;
        }
        $door['floor_id']=intval($door['floor_id']);
        $address='--';
        if($door['floor_id'] < 1){
            $add_info = M('House_village_floor')
                ->join(' as f LEFT JOIN '.C('DB_PREFIX').'house_village_single as s on s.id=f.single_id')
                ->join(' as f LEFT JOIN '.C('DB_PREFIX').'house_village as v on v.village_id=f.village_id')
                ->where(array('f.floor_id' => $door['floor_id']))
                ->field('v.village_name,s.single_name,f.floor_name')->find();
            $address = $add_info['village_name'].$add_info['single_name'].(D('House_village')->getHouseAlias('single_name')?:'栋').$add_info['floor_name'].(D('House_village')->getHouseAlias('floor_name')?:'单元');
        }
	    $data=[
	        'uid'=>$param['uid'],
            'log_bind_id'=>0,
            'log_name'=>$address,
            'log_from'=>21,
            'log_business_id'=>$param['village_id'],
            'log_more_id'=>$door['floor_id'],
            'device_id'=>$door['door_id'],
            'log_detail'=>serialize($param),
            'log_time'=>$param['add_time'],
            'open_status'=>$param['open_status'],
            'phone_plat'=>$param['phone_plat'],
            'phone_version'=>$param['phone_version'],
            'add_time'=>time(),
        ];
        M('House_user_log')->data($data)->add();
    }

	public function wxapp_door_report(){
		$data = array();
		//公共参数
			$data['add_time'] = time();

		//手机信息
			$data['phone_plat'] = $_POST['systemInfo']['platform'] == 'android' ? 2 : 1;
			$data['phone_brand'] = strtolower($_POST['systemInfo']['brand']);

			//手机号版本只取前两位
			$phone_version = trim(str_replace(array('ios','android'),'',strtolower($_POST['systemInfo']['system'])));
			$phone_version_arr = explode('.',$phone_version);
			$data['phone_version'] = $phone_version_arr[0] . '.' . ($phone_version_arr[1] ? $phone_version_arr[0] : '0');

		//未搜索到门禁，不知道用户情况
		if($_POST['file'] == 'wxapp_notfounddoor_error_log'){
			//如果用户属于一个小区，则归为该小区
			$user_bind = M('House_village_user_bind')->field('`village_id`')->where(array('uid'=>$this->_uid))->group('village_id')->select();

			if(count($user_bind) == 1){
				$data['village_id'] = $user_bind[0]['village_id'];
			}
			$data['uid'] = $this->_uid;
			$data['add_time'] = time();
			$data['open_status'] = '1';

			//蓝牙搜索到的个数，部分安卓手机需要开定位才能使用蓝牙，作为部分依据查看蓝牙是否真的可用
			foreach($_POST['msg']['devicesArr'] as $key=>$value){
				if(!empty($value)){
					$pre = substr($value,2);
					if($pre == '05' || $pre == '0C'){
						$_POST['msg']['devicesArr'][$key] = substr($value,2,8);
					}
				}
			}
			$data['searched_bluetooth'] = count($_POST['msg']['devicesArr']);
			$data['param'] = $data['searched_bluetooth'] ? serialize($_POST['msg']['devicesArr']) : '';
			$data['location'] = $_POST['msg']['userLocation'] ? serialize($_POST['msg']['userLocation']) : '';
			//可能出问题的门
			$fail_door = D('House_village_door')->check_fail_door($this->_uid);
			if($fail_door){
				$data['door_device_id'] = $fail_door['door_device_id'];
			}
			$_POST['log_id'] = M('House_village_open_door')->data($data)->add();
			self::userLogAdds($data);
		}else if($_POST['file'] == 'wxapp_connetion_error_log' || $_POST['file'] == 'wxapp_startDiscoveryFail_error_log' || $_POST['file'] == 'wxapp_notifyBLE_log' || $_POST['file'] == 'wxapp_getBLEDeviceCharacteristics_error_log' || $_POST['file'] == 'wxapp_getServices_error_log'){  //连接不上蓝牙，
			//通过门禁ID进行查询
			$door_id = intval($_POST['msg']['door_id']);

			$now_door = M('House_village_door')->where(array('door_id'=>$door_id))->find();
			$data['village_id'] = $now_door['village_id'];
			$data['door_device_id'] = $now_door['door_device_id'];
			$data['floor_id'] = $now_door['floor_id'];
			$data['uid'] = $this->_uid;
			$data['open_status'] = $_POST['msg']['has_reconnect'] ? '3' : '2';
			$data['param'] = $_POST['msg']['errorRes'] ? serialize($_POST['msg']['errorRes']) : '';
			$data['location'] = $_POST['msg']['userLocation'] ? serialize($_POST['msg']['userLocation']) : '';
			M('House_village_open_door')->data($data)->add();
            self::userLogAdds($data);
		}else if($_POST['file'] == 'wxapp_door_log' || $_POST['file'] == 'wxapp_openSuccess_log'){  //正常连接蓝牙
			//通过门禁ID进行查询
			$door_id = intval($_POST['msg']['door_id']);

			$now_door = M('House_village_door')->where(array('door_id'=>$door_id))->find();

			$data['village_id'] = $now_door['village_id'];
			$data['door_device_id'] = $now_door['door_device_id'];
			$data['floor_id'] = $now_door['floor_id'];
			$data['uid'] = $this->_uid;
			$data['open_status'] = '0';
            $data['searched_bluetooth'] = count($_POST['msg']['devicesArr']);
            $data['param'] = $data['searched_bluetooth'] ? serialize($_POST['msg']['devicesArr']) : '';

			//去重
			$condition['add_time'] 		 = $data['add_time'];
			$condition['village_id']	 = $data['village_id'];
			$condition['door_device_id'] = $data['door_device_id'];
			$condition['uid'] 			 = $this->_uid;
			$condition['open_status'] 	 = '0';
			if(!M('House_village_open_door')->where($condition)->find()){
				$data['location'] = $_POST['msg']['userLocation'] ? serialize($_POST['msg']['userLocation']) : '';
				M('House_village_open_door')->data($data)->add();
                self::userLogAdds($data);

				//修改门禁最后开门时间
				M('House_village_door')->where(array('door_device_id'=>$data['door_device_id']))->data(array('last_open_time'=>$data['add_time']))->save();
			}
		}

		//未连接上门禁，
		$fdumpname = $_POST['file'] ? $_POST['file'] : 'wxapp_door_log';
		$fdumpfile = 'api/door_log/'.date('Ymd').'/'.$fdumpname;

		fdump(['蓝牙开门信息'.__LINE__,intval($this->_uid),$_POST,$_SERVER['HTTP_REFERER']],$fdumpfile,1);

		$this->returnCode(0, array());
	}


	//通过城市名或经纬度获取城市信息
	public function getLocationCity(){

		$lat = isset($_POST['lat'])?floatval($_POST['lat']):0;
        $lng = isset($_POST['lng'])?floatval($_POST['lng']):0;
		$arr = $this->geocoder($lat,$lng);
		if ($arr) {
			if ($this->config['location_mode']) { // 商城
				$city = $this->cityMatchingAll($arr['city'],$arr['district'],$lat,$lng);

			}else{ // 城市、区县
				$city = $this->cityMatchingAll($arr['city'],$arr['district']);
			}
		}else{
			$city = $this->cityMatchingAll('');
		}

		if (!$city) {
			$this->returnCodeError(L_('转换位置信息失败'));
		}else{
			$this->returnCodeOk(array('area_id'=>$city['now_area_id'] ? $city['now_area_id'] : $city['area_id'],'area_name'=>$city['now_area_name'] ? $city['now_area_name'] : $city['area_name'],'top_area_id'=>$city['area_id'],'top_area_name'=>$city['area_name']));
		}

	}

	public function geocoder($lat, $lng){
		if ($lat&&$lng) {
			$map_config = C('config.map_config');
			if(!$map_config || $map_config == 'baidu'){
				$url = 'https://api.map.baidu.com/reverse_geocoding/v3/?location='.$lat.','.$lng.'&output=json&pois=1&ak=' . $this->config['baidu_map_ak'];
				import('ORG.Net.Http');
				$http = new Http();
				$result = $http->curlGet($url);
				if($result){
					$result = json_decode($result,true);
					fdump($result, 'result_baidu');
					if(empty($result['result']) && C('config.tianditu_key')){
                        //百度地图没解析到，如果配置了天地图，那么按照天地图解析
                        list($wgs84_lng, $wgs84_lat) = Coordinate::bd09ToWgs84($lng, $lat);
                        $result = Tianditu::reverseGeocoder($wgs84_lng, $wgs84_lat, C('config.tianditu_key'));
						$arr = array(
							'city' => $result['result']['addressComponent']['city']?:$result['result']['addressComponent']['province'],
							'province' => $result['result']['addressComponent']['province'],
							'district' => $result['result']['addressComponent']['county']?:$result['result']['addressComponent']['town'],
						);
                    }else{
						$arr = array(
							'city' => $result['result']['addressComponent']['city'],
							'province' => $result['result']['addressComponent']['province'],
							'district' => $result['result']['addressComponent']['district'],
						);
					}

					return $arr;
				}else{
					return array();
				}
			}else{
				$now_address = D('Area')->cityMatching($lat, $lng);
				$arr = array(
					'city' => $now_address['area_info']['city_name'],
					'province' => $now_address['area_info']['province_name'],
					'district' => $now_address['area_info']['area_name'],
				);
				return $arr;
			}
		}else{
			return array();
		}
	}

    public function cityMatchingAll($city_id,$area_name='',$lat=0,$lng=0){
		$full_city_name = trim($city_id);
        $long   =   strlen($city_id);
        if($long >= 7){
            $city_id    =   str_replace(L_('市'),'',$city_id);
			$city_id    =   str_replace(L_('地区'),'',$city_id);
            $city_id    =   str_replace(L_('特别行政区'),'',$city_id);
            $city_id    =   str_replace(L_('蒙古自治州'),'',$city_id);
            $city_id    =   str_replace(L_('回族自治州'),'',$city_id);
            $city_id    =   str_replace(L_('柯尔克孜自治州'),'',$city_id);
            $city_id    =   str_replace(L_('哈萨克自治州'),'',$city_id);
            $city_id    =   str_replace(L_('土家族苗族自治州'),'',$city_id);
            $city_id    =   str_replace(L_('藏族羌族自治州'),'',$city_id);
			$city_id    =   str_replace(L_('傣族自治州'),'',$city_id);
			$city_id    =   str_replace(L_('布依族苗族自治州'),'',$city_id);
			$city_id    =   str_replace(L_('苗族侗族自治州'),'',$city_id);
			$city_id    =   str_replace(L_('壮族苗族自治州'),'',$city_id);
			$city_id    =   str_replace(L_('澳门'),L_('澳門'),$city_id);
			$city_id    =   str_replace(L_('朝鲜族自治州'),'',$city_id);
			$city_id    =   str_replace(L_('哈尼族彝族自治州'),'',$city_id);
			$city_id    =   str_replace(L_('傣族景颇族自治州'),'',$city_id);
			$city_id    =   str_replace(L_('藏族自治州'),'',$city_id);
			$city_id    =   str_replace(L_('彝族自治州'),'',$city_id);
			$city_id    =   str_replace(L_('白族自治州'),'',$city_id);
			$city_id    =   str_replace(L_('傈僳族自治州'),'',$city_id);
        }
        $database_area = D('Area');
        $database_field = '`area_id`,`area_name`,`area_url`';
        $condition_all_city['area_name'] = [['eq', $full_city_name],['eq',  $city_id], 'OR'];
        $condition_all_city['area_type'] = 2;
        $condition_all_city['is_open'] = 1;
        $oCity = $database_area->field($database_field)->where($condition_all_city)->find();
        if($city_id&&$oCity){
            // 获取当前区县
            if($area_name){
                $maybe_areaname = str_replace(array(L_('区'), L_('县')), '', $area_name);
                $where_area['area_pid'] = $oCity['area_id'];
				$where_area['area_name'] = [['eq', $area_name],['eq',  $maybe_areaname], 'OR'];
                $now_area = D('Area')->where($where_area)->find();

                if(empty($now_area)){
                    import('@.ORG.words');
                    $words_class = new words();
                    $data_area['area_pid'] = $oCity['area_id'];
                    $data_area['area_name'] = $area_name;
                    $data_area['area_url'] = $words_class->stringgetFirstCharter($area_name);
                    $data_area['area_type'] = '3';
                    if($data_area['area_id'] = D('Area')->data($data_area)->add()){
                        $now_area = $data_area;
                    }
                }

                if($now_area['is_show_change']){
					$oCity['now_area_id'] = $now_area['area_id'];
					$oCity['now_area_name'] = $now_area['area_name'];
				}else{
					$oCity['now_area_id'] = $oCity['area_id'];
					$oCity['now_area_name'] = $oCity['area_name'];
				}

            }

            // 通过经纬度获取该区县下距离最近的商场信息
            if($lat && $lng ){
                $oCity['now_area_id'] = $oCity['area_id'];
                $oCity['now_area_name'] = $oCity['area_name'];
                if ($now_area) {
                    $now_tranding = D('Area')->get_tranding_by_countyPid($now_area['area_id'],true);
                    $min_distance_tranding = array();
                    $distance_old = 0;
                    if ($now_tranding) {
                        foreach ($now_tranding as $key => $value) {
                            $distance = getDistance($lat, $lng, $value['lat'], $value['lng']); // 距离
                            if($min_distance_tranding){
                                if ($distance < $distance_old) { // 取较小的距离
                                    $min_distance_tranding = $value;
                                }
                            }else{
                                $min_distance_tranding = $value;
                                $distance_old = $distance;
                            }
                        }
                        if ($min_distance_tranding) {
                            $oCity['now_area_id'] = $min_distance_tranding['market_id'];
                            $oCity['now_area_name'] = $min_distance_tranding['market_name'];
                        }
                    }
                }
            }
            return  $oCity;
        }else{
	        $condition_all_city = array();
			$condition_all_city['area_id'] = $this->config['now_city'];
	        $condition_all_city['area_type'] = 2;
	        $condition_all_city['is_open'] = 1;
	        $oCity = $database_area->field($database_field)->where($condition_all_city)->find();
        	if ($oCity) {
	            $oCity['now_area_id'] = $oCity['area_id'];
	            $oCity['now_area_name'] = $oCity['area_name'];
            	return  $oCity;
        	}else{
        		return array();
        	}
        }
    }

    //获取国家区号接口
    public function getNationalPhone(){
		$national_list = S('app_national_list');
		// if(empty($national_list)){
		if(true){//这里不要用缓存了，用缓存的话多语言会出问题
			$db = M(_view("national_phone"));
			$where = array(
				'status' => 1
			);
			if ($lang) {
				$data = $db->where($where)->order('sort desc,id asc')->select(array(),$lang);
			}else{
				$data = $db->where($where)->order('sort desc,id asc')->select();
			}

			$national_list = array();
			foreach($data as $v){
				$national_list[] = array(
					'code'		=> $v['code'],
					'national'	=> $v['national'],
					'show'		=> $v['national'].' +'.$v['code'],
					'length'	=> $v['length'],
					'match'		=> $v['match'],
				);
			}
			S('app_national_list',$national_list);
		}
		$this->returnCodeOk($national_list);
    }

    public function get_reg(){
    	$this->returnCodeOk(['reg' => $this->config['open_deliver_register']]);
    }
}
?>