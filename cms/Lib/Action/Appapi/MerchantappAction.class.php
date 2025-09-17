<?php
class MerchantappAction extends BaseAction{
    protected $merid;
    protected $merchant;
    protected $is_app = false;
    protected $is_weixin = false;
    protected $is_wxapp = false;
    protected $score_name = '';
    public $time_type = array('year' => '年', 'month' => '月', 'day' => '日');

    protected function _initialize(){
        parent::_initialize();
        $ticket = I('ticket', false);
        $from = I('from', '');
        if($ticket){
            if($from == 'storestaff'){
                //店员app跳转商家app
                $info = ticket::get($_POST['store_ticket'], $_POST['store_device'], true);
            }else{
                $info = ticket::get($ticket, $this->DEVICE_ID, true);
            }
            if($info){
                $condition_merchant['mer_id'] = $info['uid'];
            }else{
                $this->returnCode('20140001');
            }
            $database_merchant = D('Merchant');
            $this->merchant = $database_merchant->field(true)->where($condition_merchant)->find();
            $this->mer_id = $this->merchant['mer_id'];
            //设置时区
            if(!empty($this->merchant['city_id']) || !empty($this->merchant['area_id'])){
                $area_db = M("Area");
                if(!empty($this->merchant['city_id'])){
                    $city_info = $area_db->where('area_id='.$this->merchant['city_id'])->field('timezone')->find();
                }
                if(!empty($this->merchant['area_id'])){
                    $area_info = $area_db->where('area_id='.$this->merchant['area_id'])->field('timezone')->find();
                }
                //area的时区优先于城市的时区
                if(!empty($area_info['timezone']) || !empty($city_info['timezone'])){
                    date_default_timezone_set($area_info['timezone'] ? $area_info['timezone'] : $city_info['timezone']);
					D('')->query("SET time_zone='+" . get_timeout_num_by_name() . "'");
                }
            }
        }
        $this->config['have_group_name'] = isset($this->config['group_alias_name']) ? $this->config['group_alias_name'] : '';// 团购
        $this->config['have_meal_name'] = isset($this->config['meal_alias_name']) ? $this->config['meal_alias_name'] : ''; // 餐饮
        $this->config['have_shop_name'] = isset($this->config['shop_alias_name']) ? $this->config['shop_alias_name'] : ''; // 快店
        $this->config['have_mall_name'] = isset($this->config['mall_alias_name']) ? $this->config['mall_alias_name'] : ''; // 商城
        $this->config['have_appoint_name'] = isset($this->config['appoint_alias_name']) ? $this->config['appoint_alias_name'] : ''; // 预约
        // 商家积分别名使用上积分别名配置 如果没有配置  默认取  积分
        if ($this->config['merchant_score_name']) {
            $this->score_name = $this->config['merchant_score_name'];
        } else {
            $this->score_name = '积分';
        }
		
		
		if($this->config['nmgzhcs_appid'] && $this->merchant && $this->merchant['shop_openid']){
			$url = $this->config['nmgzhcs_base_url'].'/api/business_login/index';
			$appid = $this->config['nmgzhcs_appid'];
			$appsecret = $this->config['nmgzhcs_appsecret'];

			$params=array(
				'appid'=>$appid,
				'username'=>$this->merchant['nmgzhcs_account'],
				'pwd'=>$this->merchant['nmgzhcs_passwd'],
				'deviceCode'=>get_client_ip(1),
			);
			ksort($params);
			$paramsJoined = array();
			foreach($params as $param => $value) {
				$paramsJoined[] = "$param=$value";
			}
			$paramData = implode('&', $paramsJoined);

			$sign = strtoupper(md5($paramData.$appsecret));

			$params['sign'] = $sign;


			$res = httpRequest($url,'POST',$params);
			$res = json_decode($res[1],true);
			
			if($res['status'] == 'y'){
				$data['nmgzhcs_user_id'] = $this->merchant['nmgzhcs_user_id'] = $res['business_user_id'];
				$data['nmgzhcs_appid']= $this->merchant['nmgzhcs_appid'] = $res['data']['appid'];
				$data['money'] = $this->merchant['money']= $res['data']['money_sum'];
				M('Merchant')->where(array('mer_id'=>$this->merchant['mer_id']))->save($data);
			}
		}


        if(strpos($_SERVER['HTTP_USER_AGENT'],'pigcmso2oreallifeapp') !== false){
            $this->is_app = true;
        }
        if(strpos($_SERVER['HTTP_USER_AGENT'],'MicroMessenger') !== false){
            $this->is_weixin = true;
        }
        if($this->is_weixin&& strpos($_SERVER['HTTP_USER_AGENT'],'miniProgram') !== false){
            $this->is_wxapp = true;
        }
        
        $whiteAction = ['Merchantapp/index','Merchantapp/login','Merchantapp/merchant_money_info','Merchantapp/getConfigAjax','Merchantapp/merchant_money_info','Merchantapp/check_withdraw','Merchantapp/withdraw_list','Merchantapp/get_income_list','Merchantapp/merchant_money_info','Merchantapp/withdraw_method','Merchantapp/bank_list','Merchantapp/withdraw','Merchantapp/merchant_money_date','Merchantapp/isSign','Merchantapp/contract','Merchantapp/report','Merchantapp/saveSign','Merchantapp/config'];
        if ($this->merchant && !in_array(MODULE_NAME . '/' . ACTION_NAME, $whiteAction)) {
            $mustSign = D('Config_data')->where(['name' => 'contract_must_sign'])->getField('value');
            if ($mustSign == 1 && ($this->merchant['is_sign_contract'] != 1||($this->merchant['contract_end_time']&&$this->merchant['is_sign_contract']==1 && $this->merchant['contract_end_time']<time()))) {
                $url = C('config.site_url').'/packapp/merchant/';
                $this->returnCode(1302,$url);
            }
        }

        /* 商家后台日志开始 */
        if(MODULE_NAME != 'Store' && ACTON_NAME != 'ping' && (!IS_GET || !empty($_GET))){
            $log = array();
            $log['admin_id'] = intval($_SESSION['system']['id']);
            $log['mer_id'] = intval($this->mer_id);
            $log['staff_id'] = intval($_SESSION['staff']['id']);
            $log['group'] 	 = GROUP_NAME ;
            $log['module'] 	 = MODULE_NAME ;
            $log['action'] 	 = ACTION_NAME ;
            $log['add_time'] = $_SERVER['REQUEST_TIME'];
            $log['content'] = serialize(array('GET'=>$_GET,'POST'=>$_POST));
            $log['add_ip'] 	 = get_client_ip(0);
            M('Merchant_log')->add($log);
        }
        /* 商家后台日志结束 */


//        if(MODULE_NAME != 'Login' && MODULE_NAME != 'Area' && MODULE_NAME != 'Store' && MODULE_NAME != 'Pick' && ACTION_NAME!='return_url'){
//            if(empty($this->merchant)){
//                redirect(U('Login/index'));exit;
//            }

//            /****实时查找商家的权限****/
//            $tmerch = D("Merchant")->field('menus')->where(array('mer_id' => $this->merchant['mer_id']))->find();
//
//            if (empty($tmerch['menus'])) {
//                $this->merchant['menus'] = '';
//            } else {
//                $this->merchant['menus'] = explode(",", $tmerch['menus']);
//            }
//            /****实时查找商家的权限****/
//
//            if($this->config['buy_merchant_auth']==1 && $this->merchant['try_end_time']!=0 && $this->merchant['try_end_time']<time()){
//                $date_auth = array();
//                $date_auth['authority_group_id'] = 0;
//                $date_auth['try_end_time'] = 0;
//                M('Merchant')->where(array('mer_id'=>$this->merchant['mer_id']))->save($date_auth);
//            }
//
//            if($this->config['buy_merchant_auth']==1 && $this->merchant['merchant_end_time']!=0 && $this->merchant['merchant_end_time']<time()){
//                $date_auth = array();
//                $date_auth['authority_group_id'] = 0;
//                $date_auth['merchant_end_time'] = 0;
//                M('Merchant')->where(array('mer_id'=>$this->merchant['mer_id']))->save($date_auth);
//            }
//
////            $merchant_menu_list = S('merchant_menu_list_' . $this->config['system_lang']);
//            $database_merchant_menu = D(_view('New_merchant_menu'));
//            if(empty($merchant_menu_list)){
//                $condition_merchant_menu['status'] = 1;
//                $condition_merchant_menu['show'] = 1;
//                $merchant_menu_list = $database_merchant_menu->field(true)->where($condition_merchant_menu)->order('`sort` desc,`fid` ASC,`id` ASC')->select();
//                S('merchant_menu_list_' . $this->config['system_lang'],$merchant_menu_list);
//            }
//
//
//            if(empty($this->merchant['menus'])){
//                $this->merchant['menus']=[];
//            }
//            if($this->config['buy_merchant_auth'] && !in_array(10013, $this->merchant['menus'])){
//                $this->merchant['menus'][] = 10013;
//            }

//            foreach($merchant_menu_list as $value){
                //****处理权限****//
//                if($value['action']=='buy_merchant_service' && (!$this->config['buy_merchant_auth'])){
//                    continue;
//                }

//                var_dump($value['module']);
//                var_dump(MODULE_NAME);exit();
//                if ($value['module'] == MODULE_NAME && $value['action'] == ACTION_NAME && ACTION_NAME!='auth_package_buy') {
//                    if (!empty($this->merchant['menus']) && !in_array($value['id'], $this->merchant['menus'])) {
//
//                        if($this->config['buy_merchant_auth']){
////							$this->error('您还没有这个使用权限，联系管理员开通！',U('Merchant_money/buy_merchant_service'));
//                            redirect(U('Merchantapp/auth_package'));exit;
//                        }else{
//                            $this->error(L_('您还没有这个使用权限，联系管理员开通！'));
//                        }
//                    }
//                }
//            }
//        }

		
    }
	public function cityList(){
		$area_list = M('Area')->field('`area_id`,`area_pid`,`area_name`')->where(array('is_open'=>'1','area_type'=>array('lt','4')))->order('`area_type` ASC')->select();
		
		$tmp_area_list = array();
		foreach($area_list as $key=>$value){
			$tmp_area_list[$value['area_pid']][] = $value;
		}

		$new_area_list = $tmp_area_list[0];
				
		foreach($new_area_list as $key=>$value){
			if(empty($tmp_area_list[$value['area_id']])){
				unset($new_area_list[$key]);
			}else{
				$new_area_list[$key]['children'] = $tmp_area_list[$value['area_id']];
                $tmp_new_area_list[] = array('children'=>$tmp_area_list[$value['area_id']],'area_id'=>$value['area_id'],'area_name'=>$value['area_name']);
			}
		}

		foreach($tmp_new_area_list as $key=>$value){
            $tmp_new_area_list[$key]['children'] = array();
			foreach($value['children'] as $k=>$v){
				if(empty($tmp_area_list[$v['area_id']])){
					unset($tmp_new_area_list[$key]['children'][$k]);
				}else{
                   // $tmp_new_area_list[$key]['children'][$k]['children'] = $tmp_area_list[$v['area_id']];

                    $tmp_new_area_list[$key]['children'][] = array('children'=>$tmp_area_list[$v['area_id']],'area_id'=>$v['area_id'],'area_name'=>$v['area_name']);
				}
			}
		}
        
        // 街道信息
        $streetList = M('Area_street')->field('`area_id`,`area_pid`,`area_name`')->where(['is_open'=>1,'area_type'=>0])->select();
        
		$returnAreaList = array();
		foreach($tmp_new_area_list as $key=>&$value){
			$value['value'] = $value['area_id'];
			$value['text'] = $value['area_name'];
			unset($value['area_id'],$value['area_name'],$value['area_pid']);
			foreach($value['children'] as $k=>&$v){
				$v['value'] = $v['area_id'];
				$v['text'] = $v['area_name'];
				unset($v['area_id'],$v['area_name'],$v['area_pid']);
				foreach($v['children'] as $kk=>&$vv){
					$vv['value'] = $vv['area_id'];
					$vv['text'] = $vv['area_name'];
					unset($vv['area_id'],$vv['area_name'],$vv['area_pid']);

                    foreach($streetList as $_street){// 街道
                        if($_street['area_pid'] == $vv['value']){
                            $vv['children'][] = [
                                'value'=>$_street['area_id'],
                                'text'=>$_street['area_name'],
                            ];
                        }
                    }
				}
			}
		}
		
		$this->returnCode(0,$tmp_new_area_list);
	}
	public function config(){
		
		$arr['open_score_fenrun'] = isset($this->config['open_score_fenrun']) ? $this->config['open_score_fenrun'] : 0; // 店员中心APP包名
        $arr['open_bd_spread']    =   isset($this->config['open_bd_spread'])?$this->config['open_bd_spread']:'';
        if($this->config['open_distributor']==1){
            $arr['open_score_fenrun'] =1;
        }
        if($this->config['open_c2b_spread']==1){
            $arr['open_score_fenrun'] =1;
        }
		$arr['withdraw_fee_percent'] = isset($this->config['company_pay_mer_percent']) ? $this->config['company_pay_mer_percent'] : 0; // 提现手续费率
		$arr['withdraw_type'] = array(0=>L_('银行卡'),'1'=>L_('支付宝'),'2'=>L_('平台'));
		$arr['company_least_money'] =   $this->config['company_least_money'];

		$arr['merchant_verify'] = $this->config['merchant_verify'];	//注册商家是否审核

        $arr['site_url']  =   $this->config['site_url'];
		$arr['site_phone'] = $this->config['site_phone']; //网站电话
        $arr['coupon_wx_sync'] = $this->config['coupon_wx_sync'];
        $arr['group_name'] = $this->config['have_group_name'];// 团购
        $arr['meal_name'] = $this->config['have_meal_name'];// 餐饮
        $arr['shop_name'] = $this->config['have_shop_name'];// 快店
        $arr['mall_name'] = $this->config['have_mall_name'];// 商城

        $arr['appoint_name'] = $this->config['have_appoint_name']; // 预约
        $arr['cash_alias_name'] = $this->config['cash_alias_name'] ? $this->config['cash_alias_name'] : ''; // 优惠买单
        $arr['open_admin_code'] = $this->config['open_admin_code']; // 优惠买单
        $arr['open_merchant_reg_sms'] = $this->config['open_merchant_reg_sms']; // 优惠买单
		
		$arr['single_system'] = isset($this->config['single_system']) ? true : false; //是否是单系统
		
        $arr['close_register'] = $this->config['close_register'] == 1;
        $arr['site_phone'] = $this->config['site_phone'];
        $arr['is_packapp'] = true;
        
		$appConfig  =   D('Appapi_app_config')->get();
			
        $arr['mer_android_v'] = strval($appConfig['mer_android_v']);
        $arr['mer_android_vcode'] = strval($appConfig['mer_android_vcode']);
        $arr['mer_android_url'] = strval($appConfig['mer_android_url']);
        $arr['mer_android_vdesc'] = strval($appConfig['mer_android_vdesc']);
		
		$arr['staff_android_v'] = $arr['mer_android_v'];
        $arr['staff_android_vcode'] = $arr['mer_android_vcode'];
        $arr['staff_android_url'] = $arr['mer_android_url'];
        $arr['staff_android_vdesc'] = $arr['mer_android_vdesc'];

        $arr['mer_android_package_name'] = $appConfig['mer_android_package_name']?$appConfig['mer_android_package_name']:'';
        $arr['storestaff_android_package_name'] = $appConfig['storestaff_android_package_name']?$appConfig['storestaff_android_package_name']:'';
        $arr['storestaff_ios_download_url'] = $appConfig['storestaff_ios_download_url']?$appConfig['storestaff_ios_download_url']:'';
        $arr['mer_ios_package_name'] = $appConfig['mer_ios_package_name']?$appConfig['mer_ios_package_name']:'';
        $arr['storestaff_ios_package_name'] = $appConfig['storestaff_ios_package_name']?$appConfig['storestaff_ios_package_name']:'';
        $arr['store_register_agreement'] = $this->config['store_register_agreement'];
        $arr['international_phone'] = $this->config['international_phone']?$this->config['international_phone']:0;
        $arr['discount_controler'] = $this->config['discount_controler']?$this->config['discount_controler']:0;
        $arr['open_merchant_change_phone'] = $this->config['open_merchant_change_phone']?$this->config['open_merchant_change_phone']:0;
        $arr['open_distributor'] = $this->config['open_distributor']?$this->config['open_distributor']:0;
        $arr['open_staff_power'] = $this->config['open_staff_power']?$this->config['open_staff_power']:0;
        $arr['national_phone'] = M("National_phone")->where(array("status"=>1))->order('sort desc,id asc')->select();
		$arr['use_https']  = $this->config['use_https'];

        //app端底部显示ICP备案显示
        $arr['app_show_footer'] = [];
        if (isset($appConfig['merchant_icp']) && $appConfig['merchant_icp']) {
            $arr['app_show_footer'][] = [
                'title' => 'ICP备案号：' . $appConfig['merchant_icp'],
                'icon' => '',
                'url' => 'https://beian.miit.gov.cn'
            ];
        }

        // 添加 【商家推荐商家批发佣金功能】开关   0 关 1 开
        $arr['merchant_recommend_wholesale_switch'] = $this->config['merchant_recommend_wholesale_switch']?$this->config['merchant_recommend_wholesale_switch']:0;
        // 添加 【商家类型功能】开关   0 关 1 开
        $arr['merchant_type_switch'] = $this->config['merchant_type_switch']?$this->config['merchant_type_switch']:0;

        $arr['open_change_zhu'] = $this->config['open_change_zhu']?$this->config['open_change_zhu']:0;
		
		$arr['open_multilingual'] = $this->config['open_multilingual'] ? $this->config['open_multilingual'] : '0';
        $arr['lang_config'] = D('Lang')->lang_list();//语言配置



        // 添加 【是否开启移动端批发市场功能】开关  0 关 1 开
        $arr['is_switch_wholesale_mobile'] = $this->config['is_switch_wholesale_mobile']?$this->config['is_switch_wholesale_mobile']:0;
		
		$arr['map_config'] = $this->config['map_config'] ? $this->config['map_config'] : 'baidu';
		$arr['baidu_map_ak'] = $this->config['baidu_map_ak'] ? $this->config['baidu_map_ak'] : '';
		if($arr['map_config'] == 'google'){
			$arr['google_map_ak'] = $this->config['google_map_ak'] ? $this->config['google_map_ak'] : '';
		}

		//添加 【是否开启权限套餐购买权限】开关  0 关  1 开
        $arr['buy_merchant_auth'] = $this->config['buy_merchant_auth']?$this->config['buy_merchant_auth']:0;

        $database_merchant = D('Merchant');
        $merchant = $database_merchant->field('menus, is_publish_market_goods')->where(array('mer_id' => $this->merchant['mer_id']))->find();

        $arr['is_publish_market_goods'] = $merchant['is_publish_market_goods'] ? $merchant['is_publish_market_goods'] : 0;
        // 商家积分别名使用上积分别名配置 如果没有配置  默认取  积分
        if ($this->config['merchant_score_name']) {
            $arr['score_name'] = $this->config['merchant_score_name'];
        } else {
            $arr['score_name'] = '积分';// 积分
        }
        $merchant_menus = $merchant['menus'];
        $merchant = explode(',',$merchant_menus);
        $arr['merchant_integral_mall_control'] = $this->config['merchant_integral_mall_control'] ? $this->config['merchant_integral_mall_control'] : 0;
        if ($merchant_menus) {
            if (!in_array(10008,$merchant)) {
                $arr['is_switch_wholesale_mobile'] = ''; // 商城
            }
            if (!in_array(10045,$merchant)) {
                $arr['merchant_recommend_wholesale_switch'] = ''; // 我推广的商家
            }
            if (!in_array(10009,$merchant) || !in_array(10011,$merchant)) {
                $arr['is_publish_market_goods'] = 1; // 我的销售订单-如果没有 我发布的商品 或者 销售订单 权限 app端去除入口
            }
            if (!in_array(10037,$merchant)) {
                $arr['merchant_integral_mall_control'] = 0; // 积分商城
            }
        }
        if ($this->config['single_system_type'] == 'shop') {
            $arr['is_publish_market_goods'] = 0;
        }

        $arr['Currency_symbol'] = $this->config['Currency_symbol'];
		
		//顶象验证码安全的appid
		$arr['dingxiang_captcha_appid'] = $this->config['dingxiang_captcha_appid'] ? $this->config['dingxiang_captcha_appid'] : '';

		//商家积分定制 wap.hnhlhl.com
        $arr['show_merchant_score'] = isset($this->config['open_add_score_by_system_commission']) ? 1 : 0;
		

        //是否开启了合同
        $contract_item = D('Config_data')->where(['name' => 'contract_must_sign'])->find();
        $arr['show_contract'] = $contract_item['value'] ? true : false;
        //系统logo
        $arr['system_admin_logo'] = $this->config['system_admin_logo'] ? replace_file_domain($this->config['system_admin_logo']) : '';
        //一卡通定制
        $arr['internal_membership_card'] = C('customization.internal_membership_card');
        //天地图
        $arr['tianditu_web_key'] = C('config.tianditu_web_key')?C('config.tianditu_web_key'):'';

		$this->returnCode(0, $arr);
	}    

    // 隐私政策
    public function get_privacy_policy(){  
        /*$app_config = D('Appapi_app_config')->get();
        $arr['privacy_policy'] = htmlspecialchars_decode(htmlspecialchars_decode(htmlspecialchars_decode($app_config['mer_privacy_policy']),ENT_QUOTES));
        $arr['privacy_policy_md5'] = md5($app_config['mer_privacy_policy'].$this->config['register_agreement']);*/
        //H5隐私政策读取商家配置(产品和售后确认的)
        $arr['privacy_policy'] = htmlspecialchars_decode(htmlspecialchars_decode(htmlspecialchars_decode($this->config['store_privacy_policy']),ENT_QUOTES));
        // 注册协议
        $arr['register_agreement'] = htmlspecialchars_decode(htmlspecialchars_decode(htmlspecialchars_decode($this->config['store_register_agreement']),ENT_QUOTES));
        $this->returnCode('0',$arr);
    }
	
	// 商户中心获取信息
    public function indexshow(){
        $arr['site_phone'] = $this->config['site_phone']; // 电话
        $arr['have_group_name'] = isset($this->config['group_alias_name']) ? $this->config['group_alias_name'] : '';// 团购
        $arr['have_meal_name'] = isset($this->config['meal_alias_name']) ? $this->config['meal_alias_name'] : ''; // 餐饮
        $arr['have_shop_name'] = isset($this->config['shop_alias_name']) ? $this->config['shop_alias_name'] : ''; // 快店
        $arr['have_mall_name'] = isset($this->config['mall_alias_name']) ? $this->config['mall_alias_name'] : ''; // 商城
        $arr['have_appoint_name'] = isset($this->config['appoint_alias_name']) ? $this->config['appoint_alias_name'] : ''; // 预约
        $arr['discount_prompt'] = L_('请填写0~100之间的整数，0和100都是表示无折扣，98表示9.8折');
        $arr['sort_prompt'] = L_('默认添加顺序排序！手动调值，数值越大，排序越前');
        $arr['out_img'] = $this->config['site_url'] . '/static/appapi/merchant/out.png'; // 退出
        $arr['more_img'] = $this->config['site_url'] . '/static/appapi/merchant/more.png'; // 更多
        $arr['morecolor_img'] = $this->config['site_url'] . '/static/appapi/merchant/morecolor.png'; // 更多-彩
        $arr['closer_img'] = $this->config['site_url'] . '/static/appapi/merchant/close.png'; // 关闭
        $arr['app_android_shop'] = isset($this->config['app_android_shop']) ? $this->config['app_android_shop'] : ''; // 店员中心APP包名
        $arr['app_ios_shop'] = isset($this->config['app_ios_shop']) ? $this->config['app_ios_shop'] : ''; // 店员中心APP包名
      // $arr['time'] = time();
        
        $this->returnCode(0, $arr);
    }
	# 登录
	public function login()
    {
        $ticket = I('ticket', false);
        $store_ticket = I('store_ticket', false);
        $store_device = I('store_device', false);
        // $store_device =strtolower( $store_device);
        $from = I('from',false);

        $auth =array(
            'group'=>1,
            'shop'=>1,
            'mall'=>1,
            'meal'=>1,
            'appoint'=>1,
            'store_list'=>1,
            'hardware'=>1,
            'merchant_money'=>1,
            'fans_send'=>1,
            'card'=>1,
            'card_recharge'=>1, //是否允许商家会员卡线下充值
            'wholesale'=>1,
            'is_integral_mall'=>1,
            'is_sell_order' => 0,
            'is_buy_order' => 0
        );
        if ($this->config['merchant_card_recharge_offline']==0) {
            $auth['card_recharge'] = 0;
        }
        // 添加 【是否开启移动端批发市场功能】开关  0 关 1 开
        if ($this->config['is_switch_wholesale_mobile']<=0) {
            $auth['wholesale'] = 0;
        }
        // 添加 【是否开启移动端批发市场功能】开关  0 关 1 开
        if ($this->config['merchant_integral_mall_control']<=0) {
            $auth['is_integral_mall'] = 0;
        }

        // 生成v20ticket
        import("@.ORG.Token");
        $token = new Token();

        if($from=='storestaff'){
            $info = ticket::get($store_ticket, $store_device, true);//商家用户信息

            if ($info) {
//                $storeInfo = ticket::get($store_ticket, $store_device, true);//店员信息
//                $now_staff = isset($storeInfo['uid']) ? D('Merchant_store_staff')->field(true)->where(array('id' => $storeInfo['uid']))->find() : [];
                $store_id = $now_staff['store_id'] ?? '';
                //$condition_merchant_store['store_id'] = $now_staff['store_id'];
                //$now_store = M('Merchant_store')->field(true)->where($condition_merchant_store)->find();
                $now_merchant = D('Merchant')->get_info($info['uid']);

                $merchant_menus = $now_merchant['menus'];
                $merchant = explode(',',$merchant_menus);
                if(!$merchant_menus){
                    $merchant = array_column(D('New_merchant_menu')->select(), 'id');
                    $merchant_menus = implode(',', $merchant);
                }
                if ($merchant_menus) {
                    if (!in_array(10023,$merchant) || $this->config['delete_mall_v1']) {
                        $auth['mall'] = 0; // 商城
                    }
                    if (!in_array(8,$merchant)) {
                        $auth['group'] = 0; // 团购
                    }
                    if (!in_array(108,$merchant)) {
                        $auth['shop'] = 0; // 快店
                    }
                    if ((!in_array(6,$merchant) && !in_array(147,$merchant)) || $this->config['delete_foodshop_v1']) {
                        $auth['meal'] = 0; // 餐饮
                    }
                    if (!in_array(60,$merchant)) {
                        $auth['appoint'] =0; // 预约
                    }
                    if (!in_array(5,$merchant)) {
                        $auth['store_list'] = 0;
                    }
                    if (!in_array(49,$merchant)) {
                        $auth['hardware'] = 0; // 打印机
                    }
                    if (!in_array(118,$merchant)) {
                        $auth['merchant_money'] = 0; // 打印机
                    }
                    if (!in_array(35,$merchant)) {
                        $auth['fans_send'] = 0; // 打印机
                    }
                    if (!in_array(146,$merchant)) {
                        $auth['card_recharge'] = 0; // 打印机
                    }
                    if (!in_array(10037,$merchant)) {
                        $auth['is_integral_mall'] = 0; // 积分商城
                    }
                    if (in_array(10011,$merchant)) {
                        $auth['is_sell_order'] = 1; // 销售订单
                    }
                    if (in_array(10012,$merchant)) {
                        $auth['is_buy_order'] = 1; // 进货订单
                    }
                }
                $arr = array(
                    'mer_id' => $now_merchant['mer_id'],
                    'name' => $now_merchant['name'],
                    'phone' => $now_merchant['phone'],
                    'email' => $now_merchant['email'],
                    'txt_info' => $now_merchant['txt_info']
                );
                $aTicket = ticket::create($now_merchant['mer_id'], $this->DEVICE_ID, true);
                $ticket = $aTicket['ticket'];
                $return = array(
                    'ticket' => $ticket,
                    'v20_ticket' => Token::createToken($now_merchant['mer_id']),
                    'user' => $arr,
                    'auth' => $auth,
                    'store_id' => $store_id
                );
                $this->returnCode(0, $return);
            }
        }
        $database_merchant = D('Merchant');
        if($ticket){
            $info = ticket::get($ticket, $this->DEVICE_ID, true);
            if($info){
                $condition_merchant['mer_id'] = $info['uid'];
            }
        }else{
            $condition_merchant['account'] = trim($_POST['phone']);
        }
        $condition_merchant['status'] = ['neq', 4];
        $now_merchant = $database_merchant->field(true)->where($condition_merchant)->find();
        if(empty($now_merchant)){
            $condition_merchant_account['phone'] = trim($_POST['phone']);
            $condition_merchant_account['status'] = ['neq', 4];
            $now_merchant = $database_merchant->field(true)->where($condition_merchant_account)->find();
            if(empty($now_merchant)){
                $this->returnCode('20140001');
            }
        }

        if(!empty($now_merchant['merchant_end_time']) && $now_merchant['merchant_end_time'] < $_SERVER['REQUEST_TIME']){
            $data_merchant['mer_id'] = $now_merchant['mer_id'];
            $data_merchant['status'] = '0';
            $menus = M('Authority_group')->where(array('is_default'=>1))->find();
            if($now_merchant['authority_group_id']>0){
                $data_merchant['authority_group_id'] = $menus['id'];
                $data_merchant['status'] = 1;
            }
            $database_merchant->data($data_merchant)->save();
//            $this->returnCode('20140086');
        }

        if(empty($ticket)){
            if(md5(trim($_POST['pwd'])) != $now_merchant['pwd']){
                $this->returnCode('20140002');
            }
            $aTicket = ticket::create($now_merchant['mer_id'], $this->DEVICE_ID, true);
            $ticket = $aTicket['ticket'];
        }
        
        if ($now_merchant['status'] == 0) {
            $this->returnCode('20140003');
        } elseif ($now_merchant['status'] == 2) {
            $this->returnCode('20140004');
        }
        $arr = array(
            'mer_id' => $now_merchant['mer_id'],
            'name' => $now_merchant['name'],
            'phone' => $now_merchant['phone'],
            'email' => $now_merchant['email'],
            'txt_info' => $now_merchant['txt_info']
        );
        $return = array(
			'device_id' => $this->DEVICE_ID,
            'ticket' => $ticket,
            'v20_ticket' => Token::createToken($now_merchant['mer_id']),
            'user' => $arr
        );
        
        $data_merchant['mer_id'] = $now_merchant['mer_id'];
        $data_merchant['last_ip'] = get_client_ip(0);
        $data_merchant['last_time'] = $_SERVER['REQUEST_TIME'];
        $data_merchant['login_count'] = $now_merchant['login_count'] + 1;

        $database_merchant = D('Merchant');
        $merchant = $database_merchant->field('menus')->where(array('mer_id' => $now_merchant['mer_id']))->find();
        $merchant_menus = $merchant['menus'];
        $merchant = explode(',',$merchant_menus);
        if(!$merchant_menus){
            $merchant = array_column(D('New_merchant_menu')->select(), 'id');
            $merchant_menus = implode(',', $merchant);
        }
        if ($merchant_menus) {
            if (!in_array(10023,$merchant)) {
                $auth['mall'] = 0; // 商城
            }
            if (!in_array(8,$merchant)) {
                $auth['group'] = 0; // 团购
            }
            if (!in_array(108,$merchant)) {
                $auth['shop'] = 0; // 快店
            }
            if (!in_array(6,$merchant) && !in_array(147,$merchant)) {
                $auth['meal'] = 0; // 餐饮
            }
            if (!in_array(60,$merchant)) {
                $auth['appoint'] =0; // 预约
            }
            if (!in_array(5,$merchant)) {
                $auth['store_list'] = 0;
            }
            if (!in_array(49,$merchant)) {
                $auth['hardware'] = 0; // 打印机
            }
            if (!in_array(118,$merchant)) {
                $auth['merchant_money'] = 0; // 打印机
            }
            if (!in_array(35,$merchant)) {
                $auth['fans_send'] = 0; // 打印机
            }
            if (!in_array(146,$merchant)) {
                $auth['card'] = 0; // 打印机
            }
            if (!in_array(10010,$merchant)) {
                $auth['wholesale'] = 0; // 批发市场
            }
            if (!in_array(10023,$merchant)) {
                $auth['mall'] = 0; // 商城
            }
            if (!in_array(10037,$merchant)) {
                $auth['is_integral_mall'] = 0; // 积分商城
            }
            if (in_array(10011,$merchant)) {
                $auth['is_sell_order'] = 1; // 销售订单
            }
            if (in_array(10012,$merchant)) {
                $auth['is_buy_order'] = 1; // 进货订单
            }

        }
        $return['auth'] = $auth;

        if($database_merchant->data($data_merchant)->save()){
            $this->returnCode(0, $return);
        }else{
            $this->returnCode('20140005');
        }
    }
	# 商家入驻
	public function mer_reg() {
        if (C('config.close_register')) {
            $this->returnCode('1001', array(), L_('平台已关闭商家入驻，具体开放时间请留意平台通知'));
        }

        //自定义表单
        $diy_form = $_POST['diy_form'];
        $diy_data = [];
        foreach ($diy_form as $v) {
            if (isset($diy_data[$v['name']])) {
                $diy_data[$v['name']]['value'][] = $v['value'];
            } else {
                if (in_array($v['type'], [2, 4])) {
                    $v['value'] = [$v['value']];
                }
                $diy_data[$v['name']] = $v;
            }
        }
        $diy_data = array_values($diy_data);
        
        if(!I('address_info')){
            $this->returnCode('1001',array(),L_('请填写详细地址！'));
        }

		//短信
		if ($this->config['sms_key'] && $this->config['open_merchant_reg_sms']) {
            $laste_sms = M('Merchant_sms_record')->where(array('extra'=>$_POST['sms_code']))->order('pigcms_id DESC')->find();
            if (empty( $laste_sms) ) {
                $this->returnCode('10044009');
            }
            if($laste_sms['status']==1){
                $this->returnCode('1001',array(),L_('该短信验证码已失效，请重新获取！'));
            }
            if(time()-$laste_sms['send_time']>1200){
                $this->returnCode('1001',array(),L_('短信验证码已超过20分钟'));
            }
        }
		
		//帐号
		$database_merchant = D('Merchant');
		$arr['account'] =	$condition_merchant_account['account'] = I('account');
		$now_merchant = $database_merchant->field('`mer_id`')->field(true)->where($condition_merchant_account)->find();
		if (!empty($now_merchant)) {
			$this->returnCode('20140006');
		}
		
		//手机号作为帐号
		$database_merchant = D('Merchant');
		$condition_merchant_account_phone['phone'] = I('account');
		$now_merchant = $database_merchant->field('`mer_id`')->field(true)->where($condition_merchant_account_phone)->find();
		if(!empty($now_merchant)){
			$this->returnCode(1001,array(),L_('该账号作为手机号已经存在，不允许重复'));
		}
		
		
		//名称
		$arr['name'] = $condition_merchant_name['name'] = I('mername');
		$now_merchant = $database_merchant->field('`mer_id`')->field(true)->where($condition_merchant_name)->find();
		if (!empty($now_merchant)) {
			$this->returnCode('20140007');
		}

        if(($this->config['open_admin_code']==1 || $this->config['open_bd_spread'] == 1) && !empty($_POST['invit_code'])){
            if($this->config['open_bd_spread'] == 1){//这个BD优先
                $bd = M("Bd")->where(['invitation_code'=>strtoupper($_POST['invit_code']), 'status'=>1, 'is_del'=>0])->find();
                if(empty($bd)){
                    $this->returnCode('79000002');
                }
                $arr['bd_type'] = $bd['type'];
                $arr['bd_id'] = $bd['bd_id'];
            }
            else{
                $arr['invit_code'] = $_POST['invit_code'];
                if(!M('Admin')->where(array('invit_code'=>$_POST['invit_code']))->find()){
                    $this->returnCode('20140008');
                }
            }
        }

		//手机号
		$arr['phone'] =	$condition_merchant_phone['phone'] = I('phone');
		$arr['phone_country_type'] = strval($_POST['phone_country_type']);
		$arr['phone'] = phone_format($arr['phone_country_type'], $arr['phone']);

		$now_merchant = $database_merchant->field('`mer_id`')->field(true)->where($condition_merchant_phone)->find();
		if (!empty($now_merchant)) {
			$this->returnCode('20140009');
		}
		
		//帐号作为手机号
		$condition_merchant_phone_account['account'] = I('phone');
		$now_merchant = $database_merchant->field('`mer_id`')->field(true)->where($condition_merchant_phone_account)->find();
		if(!empty($now_merchant)){
			$this->returnCode(1001,array(),L_('该手机号作为账号已经存在，不允许重复'));
		}

		// 处理 公司名称（营业执照上的名称） 在 【商家推荐商家批发佣金功能】开关开启的情况下
        if ($this->config['merchant_type_switch'] == 1) {
            $company_name = $_POST['company_name'];
            $company_name = trim($company_name);
            if (empty($company_name)) {
                $this->returnCode(1001,array(),L_('请填写公司名称（营业执照上的名称）'));
            }else {
                $arr['company_name'] = $company_name; // 去除前后空格
            }
        }

		$arr['mer_id'] = null;
		if ($this->config['merchant_verify']) {
			$arr['status'] = 2;
		} else {
			$arr['status'] = 1;
		}
		$pwd	=	I('pwd');
		$province_id	=	I('province_id');
		$city_id	=	I('city_id');
		$area_id	=	I('area_id');
        $arr['address'] = I('address_info');
        //校验密码
        $check_password = verifyPassword($pwd);
        if($check_password['code']!=0){
            $this->returnCode(1001,array(),L_($check_password['msg']));
        }
        $arr['pwd'] = md5($pwd);
		$arr['reg_ip'] = get_client_ip(0);
		$arr['reg_time'] = $_SERVER['REQUEST_TIME'];
		$arr['province_id'] = $province_id;
		$arr['city_id'] = $city_id;
		$arr['area_id'] = $area_id;
        $arr['street_id'] = I('street_id',0,'intval');
		$arr['login_count'] = 0;
		$arr['reg_from'] = 0;

        $arr['trading_certificate_image'] = I('trading_certificate_image') ?? '';
        $arr['id_card_front'] = I('id_card_front') ?? '';
        $arr['id_card_reverse'] = I('id_card_reverse') ?? '';

        // 定制 用户成为商家老板
        if(C('config.open_bind_merchant_boss') && $arr['phone']){
            $user = D('User')->get_user(I('phone'),'phone');
            if($user){
                $arr['boss_uid'] = $user['uid'];
                
                if(isset($bd) && $bd && $bd['uid']){ // 业务员推广商家，商家绑定个人账号，个人账号如果没有上级，则业务员成为个人账号上级。同时修改商家绑定的个人账号，新绑定的账号同样遵从以上逻辑。
                    // 查询老板账号是否存在上级
                    $spreadWhere = [
                        'uid' => $user['uid']
                    ];
                    $oldSpread = M('User_spread')->where($spreadWhere)->find();
                    if(empty($oldSpread)){
                        $spreadData = [
                            'uid' =>  $user['uid'],
                            'spread_uid' => $bd['uid'],
                            'add_time' => time()
                        ]; 
                        M('User_spread')->add($spreadData);
                    }

                }
            }

        }

		// 处理下推广码问题
        $spread_code = $_POST['spread_code'] ? trim($_POST['spread_code']) : '';
        if ($spread_code && stripos($spread_code,'mer') === 0) {
            // 兼容原来旧数据
            if ($this->config['merchant_recommend_wholesale_switch'] == 1) {
                // 如何符合条件且开关开启的时候可以关联
                $spread_merchant = $database_merchant->field('`mer_id`')->field(true)->where(array('mer_code' => $spread_code))->find();
                if (!$spread_merchant) {
                    $this->returnCode(1001,array(),L_('您填写的推广码错误，请核实后重新填写。'));
                }else{
                    /*校验该推广码的商户是否有商家推荐码的权限*/
                    $root_menu_id=M('New_merchant_menu')->where(array('name'=>L_('我的商家推广'),'status'=>1))->getfield('id');
                    if(empty($root_menu_id)){
						$this->returnCode(1001,array(),L_('您填写的推广码无效，请核实后重新填写！'));
                    }
                    $menus_arr=explode(',', $spread_merchant['menus']);
                    if(!is_array($menus_arr) || !in_array($root_menu_id,$menus_arr)){
						$this->returnCode(1001,array(),L_('您填写的推广码无效，请核实后重新填写！'));    
                    }
                }
                $arr['mer_spread_code'] =  $spread_code;
            } else {
                // 如果关闭了，提示信息
                $this->returnCode(1001,array(),L_('您填写的推广码无效，请核实后重新填写。'));
            }
        } elseif ($spread_code && intval($spread_code) > 100000 && intval($spread_code) < 999999) {
            if ($this->config['merchant_recommend_wholesale_switch'] == 1) {
                // 如何符合条件且开关开启的时候可以关联
                $spread_merchant = $database_merchant->field('`mer_id`')->field(true)->where(array('mer_code' => $spread_code))->find();
                if (!$spread_merchant) {
                    $this->returnCode(1001,array(),L_('您填写的推广码错误，请核实后重新填写。'));
                }else{
                    /*校验该推广码的商户是否有商家推荐码的权限*/
                    $root_menu_id=M('New_merchant_menu')->where(array('name'=>L_('我的商家推广'),'status'=>1))->getfield('id');
                    if(empty($root_menu_id)){
						$this->returnCode(1001,array(),L_('您填写的推广码无效，请核实后重新填写！'));
                    }
                    $menus_arr=explode(',', $spread_merchant['menus']);
                    if(!is_array($menus_arr) || !in_array($root_menu_id,$menus_arr)){
						$this->returnCode(1001,array(),L_('您填写的推广码无效，请核实后重新填写！'));
                    }
                }
                $arr['mer_spread_code'] =  $spread_code;
            } else {
                // 如果关闭了，提示信息
                $this->returnCode(1001,array(),L_('您填写的推广码无效，请核实后重新填写。'));
            }
        } elseif(!$spread_code && $this->config['merchant_recommend_wholesale_switch']) {
            // 如果开启了没填，提示信息
            $this->returnCode(1001,array(),L_('您填写的推广码无效，请核实后重新填写。'));
        } elseif ($spread_code) {
            $arr['spread_code'] =  $spread_code;
        }


        if ($diy_data) {
            $arr['diy_form_data'] = serialize($diy_data);
        }
		if ($insert_id = $database_merchant->data($arr)->add()) {
            if (0 == $this->config['merchant_verify'] && $this->config['open_distributor']==1 && $_POST['spread_code']) {
                D('Distributor_agent')->agent_spread_log($insert_id);
            }
            D('Merchant')->reg_notice($insert_id);
            M('Merchant_score')->add(array('parent_id' => $insert_id, 'type' => 1));
			// 如果传值店铺id
            if ($_POST['store_id']) {
                $this->mer_reg_claim_store($_POST['store_id'], $insert_id);
            }
            /*校验成功，将验证码状态至为已使用*/
            M('Merchant_sms_record')->data(array('pigcms_id'=>$laste_sms['pigcms_id'],'status'=>1))->save();

			if ($this->config['merchant_verify']) {
				$this->returnCode(0,array('type'=>2));	//注册成功,请耐心等待审核或联系工作人员审核。
			} else {
				$this->returnCode(0,array('type'=>1));
			}
		} else {
			$this->returnCode('20140010');
		}
	}
	# 商家后台管理首页
	public function index() {
		if (empty($this->merchant['qrcode_id'])) {
            $qrcode_return = D('Recognition')->get_new_qrcode('merchant', $this->merchant['mer_id']);
        } else {
            $qrcode_return = D('Recognition')->get_qrcode($this->merchant['qrcode_id']);
        }
        //开启购买权限
        if ($this->config['buy_merchant_auth']) {
            $menu_group = [];
            $now_merchant = [];
            $is_card = false;

            $is_has_auths = M('Authority_group')->where(['gid'=>1, 'status'=>1])->count();
            if($is_has_auths) {
                if(empty($this->merchant['menus']) || empty($this->merchant['last_time'])) {
                    $is_has_default = 1;   //包含默认权限

                    $menu_group = D('Merchant_auth')->get_auth_group_list_wap(20,$is_has_default);
                    foreach ($menu_group['group_list'] as &$item) {
                        if ($item['is_try'] && M('Authority_group_order')->where(array('label' => $item['id'], 'mer_id' => $this->merchant['mer_id']))->find()) {
                            $item['had_try'] = 1;
                        }
                        $item['currency_symbol'] = $this->config['Currency_symbol'];
                        $item['currency_txt'] = $this->config['Currency_txt'];
                    }

                    $now_merchant = D('Merchant')->get_info($this->merchant['mer_id']);
                    $now_merchant['merchant_end_time_str'] = date('Y-m-d H:i:s',$now_merchant['merchant_end_time']);

                    if ($now_merchant['is_try']) {
                        $now_merchant['merchant_end_time'] = $now_merchant['try_end_time'];
                    }
                    $is_card = true;

                } elseif(($this->merchant['try_end_time']!=0 && $this->merchant['try_end_time']<time()) || ($this->merchant['merchant_end_time']!=0 && $this->merchant['merchant_end_time']<time())) {

                    $is_has_default = 0;  //不包含默认权限

                    $menu_group = D('Merchant_auth')->get_auth_group_list_wap(20,$is_has_default);
                    foreach ($menu_group['group_list'] as &$item) {
                        if ($item['is_try'] && M('Authority_group_order')->where(array('label' => $item['id'], 'mer_id' => $this->merchant['mer_id']))->find()) {
                            $item['had_try'] = 1;
                        }
                        $item['currency_symbol'] = $this->config['Currency_symbol'];
                        $item['currency_txt'] = $this->config['Currency_txt'];
                    }

                    $now_merchant = D('Merchant')->get_info($this->merchant['mer_id']);
                    $now_merchant['merchant_end_time_str'] = date('Y-m-d H:i:s',$now_merchant['merchant_end_time']);

                    if ($now_merchant['is_try']) {
                        $now_merchant['merchant_end_time'] = $now_merchant['try_end_time'];
                    }
                    $is_card = true;
                }
            }
			
			$arr['qrcodeinfo'] = isset($qrcode_return['qrcode'])?$qrcode_return['qrcode']:'';	//二维码
            $arr['list'] = $menu_group;
            $arr['list']['now_merchant'] = $now_merchant;
            $arr['list']['is_card'] = $is_card;
        }

        $uid = $this->ticket();
        $allincomecount = $this->getallincomecount();
        $wap_MerchantAd = D('Adver')->get_adver_by_key('wap_Merchant', 7);
        if($wap_MerchantAd){
            foreach($wap_MerchantAd as $v){
                $Ad[]	=	array(
                    'url'	=>	$v['url'],
                    'pic'	=>	$v['pic'],
                );
            }
        }
        
        $number	=	$this->getallordercount();

        $todaywhere['mer_id'] = $this->merchant['mer_id'];
        $todaywhere['income'] = 1;
        $todaywhere['type'] = array('neq','withdraw');
        $today_zero = strtotime(date('Y-m-d'));
        $todaywhere['_string'] = 'use_time>' . $today_zero . ' AND use_time <=' . ($today_zero + 86400);

        $arr	=	array(
            'wap_merchantAd'	=>	$wap_MerchantAd==false?array():$Ad,		//广告牌
            'qrcodeinfo'		=>	isset($qrcode_return['qrcode'])?$qrcode_return['qrcode']:'',	//二维码
            'name'				=>	$this->merchant['name'],	//商户名称
            'count_number'		=>	array(
				'allincomecount'	=>	(int)$allincomecount,					//收入总数
				'webviwe'			=>	(int)$this->merchant['hits'],	//浏览总数
				'allordercount'		=>	(int)$number['allordercount'],			//订单总数
				'monthordercount'	=>	(int)$number['monthordercount'],		//本月订单总数
				'todayordercount'	=>	M('Merchant_money_list')->where($todaywhere)->count(),		//本日订单总数
				'fans_count'		=>	(int)$number['fans_count'],				//粉丝总数
				//'appoint_page_row'	=>	isset($this->config['appoint_page_row']) ? 1 : 0,	预约判断
            ),
            'logo'				=>	$this->merchant['logo'] ? replace_file_domain($this->merchant['logo']) : $this->config['wechat_share_img'],	//商户logo
            'invit_code'		=>	$this->merchant['invit_code'] ? $this->merchant['invit_code'] : '',	//邀请码
        );
        $arr['type_name'] = $this->get_alias_c_name();         //业务类型.
        $arr['admin'] = M('Admin')->field('realname as text,invit_code as value')->where(array('invit_code'=>array('neq','')))->select();
        foreach ($arr['admin'] as $value) {
            if($value['value']==$this->merchant['invit_code']){
                $arr['admin_name'] = $value['text'];
                break;
            }
        }
        $config = M('Appapi_app_config')->select();
        foreach($config as $v){
            if($v['var']=='mer_android_v'){
                $arr['android_version'] = $v['value'];
            }elseif($v['var']=='mer_android_url'){
                $arr['android_downurl'] = $v['value'];
            }elseif($v['var']=='mer_android_vcode'){
                $arr['android_version_code'] = $v['value'];
            }elseif($v['var']=='mer_android_vdesc'){
                $arr['android_version_version_desc'] = $v['value'];
            }

        }
        // 获取 merchant_buy_points_switch 商户购买平台积分开关配置
        $arr['merchant_buy_points_switch'] = $this->config['merchant_buy_points_switch'];

        // 平台商户积分开关配置
        $arr['open_score_pay_back_mer'] = $this->config['open_score_pay_back_mer'];

        // 验证当前密码是否为123456，如果是则提示修改密码
        if ($this->config['pwd_verify']&&$this->merchant['pwd']=='e10adc3949ba59abbe56e057f20f883e') {
            $arr['check_pwd'] = 1;
        }

        $database_merchant = D('Merchant');
        $type_id = $database_merchant->where(array('mer_id' => $this->merchant['mer_id']))->getField('type_id');
        if ($type_id>0) {
            $merchant_type = M('Merchant_type')->where(array('type_id'=>$this->merchant['type_id']))->find();
            $arr['Merchant_type'] = $merchant_type;
        }

        //增加商家是否已签署合同
        $nowMerchant = $database_merchant->where(array('mer_id' => $this->merchant['mer_id']))->find();
        $arr['contract_sign_tip'] = '您好，您已成功入驻平台，现需在线签署合同后才可正常使用商家功能哦~';
        $arr['contract_sign_alert'] = 0;
        $mustSign = D('Config_data')->where(['name' => 'contract_must_sign'])->getField('value');
        if ($mustSign == 1) {
            //看下是否开启续费
            $canRenewed = D('Config_data')->where(['name' => 'contract_can_renewed'])->getField('value');
            if($nowMerchant['is_sign_contract']==0){
                $arr['contract_sign_alert'] = 1;
            }elseif($nowMerchant['is_sign_contract']==2){
                $resignTip = D('Config_data')->where(['name' => 'contract_resign_tip'])->getField('value');
                $arr['contract_sign_tip'] = $resignTip;
                $arr['contract_sign_alert'] = 1;
            }elseif($nowMerchant['is_sign_contract']==1&&$canRenewed!=1&&$nowMerchant['contract_end_time']&&$nowMerchant['contract_end_time']<time()){
                $arr['contract_sign_tip'] = L_('合同已到期请联系管理员~');
                $arr['contract_sign_alert'] = 2;
            }elseif($canRenewed&&$nowMerchant['contract_end_time']){
                //看下是否开启续费
                $canRenewed = D('Config_data')->where(['name' => 'contract_can_renewed'])->getField('value');
                if($canRenewed){
                    $fewDayNotice = D('Config_data')->where(['name' => 'contract_few_days_notice'])->getField('value');
                    if(($nowMerchant['contract_end_time']-time())<$fewDayNotice*24*3600){
                        $last_day = ceil(($nowMerchant['contract_end_time']-time())/86400);
                        $arr['contract_sign_tip'] = L_('您好，您的合同还有'.$last_day.'天到期，请提前签署新合同，避免到期后功能无法使用，影响正常经营。');
                        $arr['contract_sign_alert'] = 1;
                    }
                }
            }
        }
        
        // 平台注销商家开关配置
        $arr['cancel_app'] = $this->config['cancel_app'];

        //是否开启了合同
        $contract_item = D('Config_data')->where(['name' => 'contract_must_sign'])->find();
        $arr['show_contract'] = $contract_item['value'] ? true : false;
        $this->returnCode(0,$arr);
	}

	# 商家后台管理左划
	public function leftMenu()
    {
        $mer_id = $this->ticket();
        $appoint_row = isset($this->config['appoint_page_row']) ? 1 : 0; // 预约判断
        $database_merchant = D('Merchant');
        $merchant = $database_merchant->field('menus')->where(array('mer_id' => $mer_id))->find();
        $merchant = $merchant['menus'];
        $mer = array(
            'name' => $this->config['merchant_alias_name'] . L_('管理'), // 快店名
            'alias_name' => $this->config['merchant_alias_name'], // 快店名
            'label' => 'merchant',
            'img' => $this->config['site_url'] . '/static/appapi/merchant/merchant.png',
            'img_color' => $this->config['site_url'] . '/static/appapi/merchant/merchantcolor.png',
            'so_list' => array()
        );
        $group = array(
            'name' => $this->config['group_alias_name'] . L_('管理'), // 团购名
            'alias_name' => $this->config['group_alias_name'], // 团购名
            'label' => 'group',
            'img' => $this->config['site_url'] . '/static/appapi/merchant/group.png',
            'img_color' => $this->config['site_url'] . '/static/appapi/merchant/groupcolor.png',
            'so_list' => array()
        );
        $shop = array(
            'name' => $this->config['shop_alias_name'] . L_('管理'), // 快店名
            'alias_name' => $this->config['shop_alias_name'], // 团购名
            'label' => 'shop',
            'img' => $this->config['site_url'] . '/static/appapi/merchant/merchant.png',
            'img_color' => $this->config['site_url'] . '/static/appapi/merchant/merchantcolor.png',
            'so_list' => array()
        );
        $meal = array(
            'name' => $this->config['meal_alias_name'] . L_('管理'), // 餐饮名
            'alias_name' => $this->config['meal_alias_name'], // 团购名
            'label' => 'meal',
            'img' => $this->config['site_url'] . '/static/appapi/merchant/meal.png',
            'img_color' => $this->config['site_url'] . '/static/appapi/merchant/mealcolor.png',
            'so_list' => array()
        );
        $appoint = array(
            'name' => $this->config['appoint_alias_name'] . L_('管理'), // 预约名
            'alias_name' => $this->config['appoint_alias_name'], // 团购名
            'label' => 'appoint',
            'img' => $this->config['site_url'] . '/static/appapi/merchant/appoint.png',
            'img_color' => $this->config['site_url'] . '/static/appapi/merchant/appointcolor.png',
            'so_list' => array()
        );
        $staff = array(
            'name' => L_('店员管理'), // 店员名
            'alias_name' => L_('店员管理'), // 团购名
            'label' => 'staff',
            'img' => $this->config['site_url'] . '/static/appapi/merchant/staff.png',
            'img_color' => $this->config['site_url'] . '/static/appapi/merchant/staffcolor.png',
            'so_list' => array()
        );
        $hardware = array(
            'name' => L_('打印机管理'), // 打印机
            'alias_name' => L_('打印机管理'), // 团购名
            'label' => 'hardware',
            'img' => $this->config['site_url'] . '/static/appapi/merchant/hardware.png',
            'img_color' => $this->config['site_url'] . '/static/appapi/merchant/hardwarecolor.png',
            'so_list' => array()
        );
        if ($merchant) {
            $arr = array();
            $arr[] = $mer; // 店铺
            if (stripos($merchant, ',8,') !== FALSE) {
                $arr[] = $group; // 团购
            }
            if (stripos($merchant, ',108,') !== FALSE) {
                $arr[] = $shop; // 快店
            }
            if (stripos($merchant, ',6,') !== FALSE) {
                $arr[] = $meal; // 餐饮
            }
            if (stripos($merchant, ',60,') !== FALSE) {
                $arr[] = $appoint; // 预约
            }
            if (stripos($merchant, ',47,') !== FALSE) {
                $arr[] = $staff; // 店员列表
            }
            if (stripos($merchant, ',49,') !== FALSE) {
                $arr[] = $hardware; // 打印机
            }
        } else {
            $arr = array(
                $group,
                $shop,
                $meal
            );
            if ($appoint_row) {
                $arr[] = $appoint;
            }
            $arr[] = $staff;
            $arr[] = $hardware;
        }
        $this->returnCode(0, $arr);
    }
	/***首页图标统计数据***/
    public function getchart() {
    	$this->ticket();
        $nowtime = time();
        $todaystartime = strtotime(date('Y-m-d'));
        $startime = $todaystartime - (7 * 24 * 3600);
        $act	=	I('act');
        $action = trim($act);
        $newdatas = array();
        for ($d = 0; $d < 8; $d++) {
            $datekey = date('m-d', $startime + $d * 24 * 3600);
            $newdatas[$datekey] = 0;
        }
        $meal_orderDb = M('Meal_order');
        $group_orderDb = M('Group_order');
        switch ($action) {
            case 'order' :
                $mdatas = $meal_orderDb->where('mer_id=' . $this->merchant['mer_id'] . ' AND dateline >' . $startime . ' AND dateline <=' . $nowtime . " AND status!=3")->field('count(order_id) as percount,FROM_UNIXTIME(dateline,"%m-%d") as perdate')->group('perdate')->select();
                foreach ($mdatas as $mvv) {
                    $newdatas[$mvv['perdate']] = (int)$mvv['percount'];
                }
                unset($mdatas);
                $gdatas = $group_orderDb->where('mer_id=' . $this->merchant['mer_id'] . ' AND add_time  >' . $startime . ' AND add_time  <=' . $nowtime . " AND status!=3")->field('count(order_id) as percount,FROM_UNIXTIME(add_time,"%m-%d") as perdate')->group('perdate')->select();
                foreach ($gdatas as $gvv) {
                    $newdatas[$gvv['perdate']] = isset($newdatas[$gvv['perdate']]) ? $newdatas[$gvv['perdate']] + $gvv['percount'] : $gvv['percount'];
                }
                break;
            case 'income' :
                $mdatas = $meal_orderDb->where('mer_id=' . $this->merchant['mer_id'] . ' AND paid="1" AND dateline >' . $startime . ' AND dateline <=' . $nowtime . " AND status!=3")->field('sum(if(total_price>0,total_price,price)) as tprice,sum(minus_price) as offprice,FROM_UNIXTIME(dateline,"%m-%d") as perdate')->group('perdate')->select();
                if (!empty($mdatas)) {
                    foreach ($mdatas as $mvv) {
                        $newdatas[$mvv['perdate']] = (int)$mvv['tprice'] - (int)$mvv['offprice'];
                    }
                }
                unset($mdatas);
                $gdatas = $group_orderDb->where('mer_id=' . $this->merchant['mer_id'] . ' AND paid="1" AND add_time  >' . $startime . ' AND add_time  <=' . $nowtime . " AND status!=3")->field('sum(total_money) as tprice,sum(wx_cheap) as offprice,FROM_UNIXTIME(add_time,"%m-%d") as perdate')->group('perdate')->select();
                if (!empty($gdatas)) {
                    foreach ($gdatas as $gvv) {
                        $perprice = $gvv['tprice'] - $gvv['offprice'];
                        $newdatas[$gvv['perdate']] = isset($newdatas[$gvv['perdate']]) ? $newdatas[$gvv['perdate']] + $perprice : $perprice;
                    }
                }
                break;
            case 'member' :
                $fansdata = M('')->table(array(C('DB_PREFIX') . 'merchant_user_relation' => 'm', C('DB_PREFIX') . 'user' => 'u'))->where("`m`.`openid`=`u`.`openid` AND `m`.`mer_id`='" . $this->merchant['mer_id'] . "' AND dateline >" . $startime . " AND dateline <=" . $nowtime)->field('count(dateline) as percount,FROM_UNIXTIME(dateline,"%m-%d") as perdate')->group('perdate')->select();
                if (!empty($fansdata)) {
                    foreach ($fansdata as $fvv) {
                        $newdatas[$fvv['perdate']] = (int)$fvv['percount'];
                    }
                }
                break;
            default:
                break;
        }
        $arr	=	array(
			'key'	=>	array_keys($newdatas),
			'value'	=>	array_values($newdatas),
        );
        $this->returnCode(0,$arr);
    }
    # 收入总数
	private function getallincomecount() {
	    $shopOrderDb= M('Shop_order');
		$group_orderDb = M('Group_order');
		$appoint_orderDb = M('Appoint_order');
		$foodshop_orderDb = M('Foodshop_order');
		$store_orderDb = M('Store_order');
		$tmp_m_price = $shopOrderDb->where('mer_id=' . $this->merchant['mer_id'] . ' AND paid="1" AND status != 0')->field('price as tprice')->find();
		$tmp_m_price['tprice'] = number_format($tmp_m_price['tprice']);
		$meal_price = $tmp_m_price['tprice'] ;
		$tmp_g_price = $group_orderDb->where('mer_id=' . $this->merchant['mer_id'] . ' AND paid="1" AND status != 3')->field('sum(total_money) as tprice')->find();
		$group_price = $tmp_g_price['tprice'] ;
		$tmp_g_price = $appoint_orderDb->where('mer_id=' . $this->merchant['mer_id'] . ' AND paid="1" AND status != 3')->field('sum(pay_money) as tprice')->find();
		$appoint_price = $tmp_g_price['tprice'] ;

        $tmp_g_price = $foodshop_orderDb->where('mer_id=' . $this->merchant['mer_id'] . '  AND (status = 3 OR status = 4)')->field('sum(total_price) as tprice')->find();
        $foodshop_price = $tmp_g_price['tprice'] ;

        $tmp_g_price = $store_orderDb->where('mer_id=' . $this->merchant['mer_id'] . '  AND paid  =1')->field('sum(price) as tprice')->find();
        $store_price = $tmp_g_price['tprice'] ;
		return ($meal_price + $group_price+$appoint_price+$foodshop_price+$store_price);
	}
    # 订单总数 月订单总数 日订单总数 粉丝数量
    private function getallordercount() {
        $shopOrderDb = M('Foodshop_order');
        $group_orderDb = M('Group_order');
        $shop_orderDb = M('Shop_order');
        $store_orderDb = M('Store_order');
        $dining_orderDb = M('Dining_order'); //餐饮2.0订单表
        $appoint_orderDb = M('Appoint_order');
        $income = M('Merchant_money_list');
        $meal_order_all = $shopOrderDb->where(array('mer_id' => $this->merchant['mer_id'], 'status' => array('neq', 0)))->count();
        $nowtime = time();
        $todaystartime = strtotime(date('Y-m-d'));
        $monthstartime = strtotime(date('Y-m') . '-01 00:00:00');
        $meal_order_m = $shopOrderDb->where('mer_id=' . $this->merchant['mer_id'] . ' AND status=3 AND create_time >' . $monthstartime . ' AND create_time <=' . $nowtime)->count();
        $meal_order_d = $shopOrderDb->where('mer_id=' . $this->merchant['mer_id'] . ' AND status=3 AND create_time >' . $todaystartime . ' AND create_time <=' . $nowtime)->count();
        $group_order_all = $group_orderDb->where(array('paid' => 1, 'mer_id' => $this->merchant['mer_id'], 'status' => array('neq', 3)))->count();
        $group_order_m = $group_orderDb->where('paid=1 AND mer_id=' . $this->merchant['mer_id'] . ' AND status!=3 AND add_time >' . $monthstartime . ' AND add_time <=' . $nowtime)->count();
        $group_order_d = $group_orderDb->where('paid=1 AND mer_id=' . $this->merchant['mer_id'] . ' AND status!=3 AND add_time >' . $todaystartime . ' AND add_time <=' . $nowtime)->count();

        $shop_order_all = $shop_orderDb->where(array('paid' => 1, 'mer_id' => $this->merchant['mer_id'], 'status' => array('neq', 4)))->count();
        $shop_order_m = $shop_orderDb->where('paid=1 AND mer_id=' . $this->merchant['mer_id'] . ' AND status!=4 AND create_time >' . $monthstartime . ' AND create_time <=' . $nowtime)->count();
        $shop_order_d = $shop_orderDb->where('paid=1 AND mer_id=' . $this->merchant['mer_id'] . ' AND status!=4 AND create_time >' . $todaystartime . ' AND create_time <=' . $nowtime)->count();

        $store_order_all = $store_orderDb->where(array('paid' => 1, 'mer_id' => $this->merchant['mer_id']))->count();
        $store_order_m = $store_orderDb->where('paid=1 AND mer_id=' . $this->merchant['mer_id'] . ' AND pay_time >' . $monthstartime . ' AND pay_time <=' . $nowtime)->count();
        $store_order_d = $store_orderDb->where('paid=1 AND mer_id=' . $this->merchant['mer_id'] . ' AND pay_time >' . $todaystartime . ' AND pay_time <=' . $nowtime)->count();

        $dining_order_all = $dining_orderDb->where(array('mer_id' => $this->merchant['mer_id']))->count();
        $dining_order_m   = $dining_orderDb->where('mer_id=' . $this->merchant['mer_id'] . ' AND pay_time >' . $monthstartime . ' AND pay_time <=' . $nowtime)->count();
        $dining_order_d   = $dining_orderDb->where('mer_id=' . $this->merchant['mer_id'] . ' AND pay_time >' . $todaystartime . ' AND pay_time <=' . $nowtime)->count();

        $appoint_order_all = $appoint_orderDb->where(array('paid' => 1, 'mer_id' => $this->merchant['mer_id']))->count();
        $appoint_order_m   = $appoint_orderDb->where('paid=1 AND mer_id=' . $this->merchant['mer_id'] . ' AND ((pay_time >' . $monthstartime . ' AND pay_time <=' . $nowtime . ') OR (user_pay_time >' . $monthstartime . ' AND user_pay_time <=' . $nowtime . '))')->count();
        $appoint_order_d   = $appoint_orderDb->where('paid=1 AND mer_id=' . $this->merchant['mer_id'] . ' AND ((pay_time >' . $todaystartime . ' AND pay_time <=' . $nowtime . ') OR (user_pay_time >' . $todaystartime . ' AND user_pay_time <=' . $nowtime . '))')->count();

        $income_all = $income->where(array('type'=>1,'mer_id' => $this->merchant['mer_id']))->sum('money');
        $fans_count = M('')->table(array(C('DB_PREFIX') . 'merchant_user_relation' => 'm', C('DB_PREFIX') . 'user' => 'u'))->where("`m`.`openid`=`u`.`openid` AND `m`.`mer_id`='{$this->merchant['mer_id']}'")->count();
        $arr	=	array(
            'allordercount'	=>	intval($meal_order_all + $group_order_all+$shop_order_all+$store_order_all + $dining_order_all + $appoint_order_all),
            'monthordercount'	=>	intval($meal_order_m + $group_order_m+$shop_order_m+$store_order_m + $dining_order_m + $appoint_order_m),
            'todayordercount'	=>	intval($meal_order_d + $group_order_d+$shop_order_d+$store_order_d + $dining_order_d + $appoint_order_d),
            'fans_count'	=>	$fans_count,
            'income_all'	=>	$income_all,

        );
        return $arr;
    }
    # 店铺列表
    public function store_list() {
        $where['mer_id'] = $this->merchant['mer_id'];
        $where['status'] = array('neq', 4);
        $page = I('pindex', 1);
        $store_id = I('store_id', '');
        $store_id && $where['store_id'] = $condition['store_id'] = $store_id;
        $all = M('Merchant_store')->where($where)->count();
        $condition['mer_id'] = $this->merchant['mer_id'];
        if(isset($_POST['status']) && $_POST['status']>-1){
            $condition['status'] = $_POST['status'];
        }else{
            $condition['status'] =  $where['status'];
        }
        if ($_POST['is_all']) {
            $data = M(_view('Merchant_store'))->field(true)
            ->where($condition)
            ->select();
        }else{
            $data = M(_view('Merchant_store'))->field(true)
            ->where($condition)
            ->page($page, 10)
            ->select();
        }
        foreach ($data as $k=>$v) {
            $data[$k]['auth_desc'] = WapMerchantAction::getAuthDesc($v['auth']);
        }
        $arr['data'] = isset($data) ? $data : array();
        $arr['all'] = $all;
        $where['status'] = 1;
        $arr['normal'] = M('Merchant_store')->where($where)->count();
        $where['status'] = 2;
        $arr['verify'] = M('Merchant_store')->where($where)->count();
        $where['status'] =0;
        $arr['close'] = M('Merchant_store')->where($where)->count();
        $arr['page'] = ceil($arr['all'] / 10);

        // 添加从业者 功能开关配置
        $arr['employee_switch'] = $this->config['employee_switch'];
        $this->returnCode(0, $arr);
    }
    # 店铺修改状态
    public function store_status(){
		$where['store_id']	=	I('store_id');
		$data['status']	=	I('status',1);
		//$data['status']	= $data['status'] == 1 ? 1 : 0;
		if($where['store_id']){
			$save	=	M('Merchant_store')->where($where)->data($data)->save();
		}else{
			$this->returnCode('20140029');
		}
		if($save){
			$this->returnCode(0);
		}else{
			$this->returnCode('20140028');
		}
    }
    # 快店列表
    public function shop_list() {
    	$where['mer_id']	=	$this->merchant['mer_id'];
    	$where['status']		=	1;
    	$where['have_shop']		=	1;
    	$page	=	I('pindex',1);
    	$all	=	M('Merchant_store')->where($where)->count();
        $data	=	M('Merchant_store')->field(array('`mer_id`,`name`,`store_id`,`status`,`phone`'))->where($where)->page($page,10)->select();
        foreach($data as &$v){
			$shop = D('Merchant_store_shop')->field('store_theme')->where(array('store_id' => $v['store_id']))->find();
        	$store_theme = isset($shop['store_theme']) ? intval($shop['store_theme']) : 0;
        	if ($store_theme) {
        		$v['width'] = '900';
        		$v['height'] = '900';
        	} else {
        		$v['width'] = '900';
        		$v['height'] = '500';
        	}
		}
        $arr['data']	=	isset($data)?$data:array();
        $arr['all']		=	$all;
        $arr['page'] 	=	ceil($arr['all']/10);
        $this->returnCode(0,$arr);
    }
    # 快店详情
    public function store_details(){
    	$store_id	=	I('store_id');
    	if(empty($store_id)){
			$this->returnCode('20140029');
    	}
        $data = M('Merchant_store')->where(array('store_id' => $store_id, 'mer_id' => $this->merchant['mer_id']))->find();
        if (!empty($data)) {
            $data['office_time'] = unserialize($data['office_time']);
            if (!empty($data['pic_info'])) {
                $store_image_class = new store_image();
                $tmp_pic_arr = explode(';', $data['pic_info']);
                foreach ($tmp_pic_arr as $key => $value) {
                    $data['pic'][$key] = "'" . $store_image_class->get_image_by_path($value) . "'";
                }
//                $data['picstr'] = implode(',', $data['pic']);
            }
        }
        $keywords = D('Keywords')->where(array('third_type' => 'Merchant_store', 'third_id' => $data['store_id']))->select();
        $str = "";
        foreach ($keywords as $key) {
            $str .= $key['keyword'] . " ";
        }
        $arr	=	array(
			'store_id'	=>	$data['store_id'],		//商铺ID
			'mer_id'	=>	$data['mer_id'],		//商户ID
			'ismain'	=>	$data['ismain'],		//是否是主点	1主店 0不是主店
			'phone'		=>	$data['phone'],			//手机
			'weixin'	=>	$data['weixin'],		//微信
			'qq'		=>	$data['qq'],			//QQ
			'keywords'	=>	$str,					//关键词
			'permoney'	=>	$data['permoney'],		//人均消费
			'feature'	=>	$data['feature'],		//店铺特色
			'province_id'=>	$data['province_id'],	//省
			'city_id'	=>	$data['city_id'],		//市
			'area_id'	=>	$data['area_id'],		//区
			'circle_id'	=>	$data['circle_id'],		//商圈
			'adress'	=>	$data['adress'],		//地址
			'trafficroute'=>$data['trafficroute'],	//交通路线
			'sort'		=>	$data['sort'],			//排序
			'have_meal'	=>	$data['have_meal'],		//餐饮是否开启  0关闭  1开启
			'have_group'=>	$data['have_group'],	//团购是否开启  0关闭  1开启
			'open_1'	=>	$data['open_1'],		//打开时间1
			'open_2'	=>	$data['open_2'],		//打开时间2
			'open_3'	=>	$data['open_3'],		//打开时间3
			'close_1'	=>	$data['close_1'],		//结束时间1
			'close_2'	=>	$data['close_2'],		//结束时间2
			'close_3'	=>	$data['close_3'],		//结束时间3
			'lat'		=>	$data['lat'],			//经
			'long'		=>	$data['long'],			//纬
			'txt_info'	=>	$data['txt_info'],		//简介
			'pic'		=>	isset($data['pic'])?$data['pic']:array(),//图片
        );
        $this->returnCode(0,$arr);
    }
    # 快店二维码
    public function erwm($id='',$type='meal') {
        $type = trim($type);
        $id = trim(2);
        if ($type == 'group') {
            $pigcms_return = D('Group')->get_qrcode($id);
        } elseif ($type == 'meal') {
            $pigcms_return = D('Merchant_store')->get_qrcode($id);
        }

        if (empty($pigcms_return['qrcode_id'])) {
            $qrcode_return = D('Recognition')->get_new_qrcode($type, $id);
        } else {
            $qrcode_return = D('Recognition')->get_qrcode($pigcms_return['qrcode_id']);
        }
        if(empty($qrcode_return['error_code'])){
			return $qrcode_return['qrcode'];
        }
        return '';
    }
    # 快店订单
    public function sorder(){
    	$shop_order_obj = D('Shop_order');
        $store_id	=	I('store_id');
        $status	=	I('status');
        $keyword	=	I('keyword');
        $status = isset($status) ? trim($status) : 'all';
        $keyword = isset($keyword) ? trim($keyword) : '';
        $where = '`s`.`store_id`=' . $store_id;
        if ($status != 'all') {
            $status = intval($status);
            if ($status == 0) {
                $where.=' AND (`s`.`paid`="0" OR (`s`.`third_id` ="0" AND `s`.`pay_type`="offline"))';
            } elseif ($status == 1) {
                $where.=' AND `s`.`status`<2';
            } else {
                $where.=' AND `s`.`status`=' . $status;
            }
        }
        if (!empty($keyword)) {
            $where.=' AND (`s`.`userphone` like "%' . $keyword . '%" OR `s`.`username` like "%' . $keyword . '%")';
        }
        //订单列表
        $tp_count = "SELECT COUNT(*) as tp_count FROM " . C('DB_PREFIX') . "merchant_store AS ms INNER JOIN " . C('DB_PREFIX') . "shop_order AS s ON `s`.`store_id`=`ms`.`store_id` WHERE {$where}";
        $order_count = $shop_order_obj->query($tp_count);
        $pindex	=	I('pindex');
        $pindex = max(1, intval(trim($pindex)));
        $pagsize = 10;
        $offsize = ($pindex - 1) * 10;
        $sql = "SELECT `s`.*, `ms`.`name` AS storename FROM " . C('DB_PREFIX') . "merchant_store AS ms INNER JOIN " . C('DB_PREFIX') . "shop_order AS s ON `s`.`store_id`=`ms`.`store_id` WHERE {$where} ORDER BY `s`.`order_id` DESC LIMIT {$offsize}, {$pagsize}";
        $order_list = $shop_order_obj->query($sql);
        $hasmore = $order_count[0]['tp_count'] > ($pindex * $pagsize) ? 1 : 0;
        $newdatas = array();
		if (!empty($order_list)) {
			foreach ($order_list as $kk => $vv) {
				$order_status	=	'';
				$temp = array();
				$temp['order_status'] = '';
				if ($vv['paid']) {
					if (empty($vv['third_id']) && $vv['pay_type'] == 'offline') {
						$temp['order_status'] = L_('线下未付款');
					} else {
						$temp['order_status'] = L_('已付款');
					}
				}
				switch ($vv['status']) {
					case 0:
						$order_status	= L_('未确认');
						break;
					case 1:
						$order_status	= L_('已确认');
						break;
					case 2:
						$order_status	= L_('已消费');
						break;
					case 3:
						$order_status	= L_('已评价');
						break;
					case 4:
						$order_status	= L_('已退款');
						break;
					case 5:
						$order_status	= L_('已取消');
						break;
				}
				$temp['order_statuss'] =	isset($order_status)?$order_status:'';
				$temp['order_id'] = $vv['order_id'];
                $temp['nickname'] = $vv['username'];
                $temp['storename'] = $vv['storename'];
                $temp['phone'] = $vv['userphone'];
                $temp['address'] = $vv['address'];
                $temp['final_price'] = $vv['price'];
                $temp['num'] = L_( 'X1道菜',$vv['num']);
                $temp['created'] = date('Y-m-d H:i:s', $vv['create_time']);
                $newdatas[] = $temp;
            }
        }
        unset($order_list);
        $arr	=	array(
            'order_count'	=>	$order_count[0]['tp_count'],
            'page'	=>	ceil($order_count[0]['tp_count']/10),
            'list'	=>	$newdatas,
        );
        $this->returnCode(0,$arr);
    }
    # 快店订单详情
    public function sdetail(){
        $order_id	=	I('order_id');
        if(empty($order_id)){
            $this->returnCode('20140025');
        }
        $this->merid = $this->merchant['mer_id'];
        $order = D("Shop_order")->get_order_detail(array('mer_id' => $this->merid, 'order_id' => $order_id));
        if($order){
            if($order['pay_type'] == 'offline' && empty($order['third_id'])){
                $payment	=	floatval($order['price']-$order['card_price']-$order['merchant_balance']-$order['balance_pay']-$order['payment_money']-$order['score_deducte']-floatval($order['coupon_price']));
            }
            $arr['order_details']	=	array(
                'orderid'	=>	$order['orderid'],
                'order_id'	=>	$order['order_id'],
                'real_orderid'	=>	$order['real_orderid'],
                'username'	=>	$order['username'],
                'userphone'	=>	$order['userphone'],
                'create_time'	=>	date('Y-m-d H:i:s',$order['create_time']),
                'pay_time'	=>	date('Y-m-d H:i:s',$order['pay_time']),
                'expect_use_time'	=>	$order['expect_use_time']!=0 ? (date('Y-m-d H:i',$order['expect_use_time'])) : '0',
                'is_pick_in_store'	=>	$order['is_pick_in_store'],
                'address'	=>	$order['address'],
                'deliver_str'	=>	$order['deliver_str'],
                'deliver_status_str'	=>	$order['deliver_status_str'],
                'note'	=>	isset($order['desc'])?$order['desc']:'',
                'invoice_head'	=>	$order['invoice_head'],
                'pay_status'	=>	$order['pay_status_print'],
                'pay_type_str'	=>	$order['pay_type_str'],
                'status_str'	=>	$order['status_str'],
                'score_used_count'	=>	$order['score_used_count'],
                'score_deducte'	=>	floatval($order['score_deducte']),
                'merchant_balance'	=>	floatval($order['merchant_balance']),
                'balance_pay'	=>	floatval($order['balance_pay']),
                'payment_money'	=>	floatval($order['payment_money']),
                'card_id'	=>	$order['card_id'],
                'card_price'	=>	$order['card_price'],
                'coupon_price'	=>	$order['coupon_price'],
                'payment'	=>	isset($payment)?$payment:0,
                'use_time'	=>	$order['use_time']!=0 ? (date('Y-m-d H:i:s',$order['use_time'])) : '0',
                'last_staff'	=>	$order['last_staff'],
                'status'	=>	$order['status'],
                'paid'	=>	$order['paid'],
                'goods_price'	=>	floatval($order['goods_price']),
                'freight_charge'	=>	floatval($order['freight_charge']),
                'total_price'	=>	floatval($order['total_price']),
                'merchant_reduce'	=>	floatval($order['merchant_reduce']),
                'balance_reduce'	=>	floatval($order['balance_reduce']),
                'price'	=>	floatval($order['price']),
                'notes'	=>	L_('注：改成已消费状态后同时如果是未付款状态则修改成线下支付已支付，状态修改后就不能修改了'),
            );
            foreach($order['info'] as $k=>$v){
                $arr['info'][]	=	array(
                    'name'	=>	$v['name'],
                    'price'	=>	floatval($v['price']),
                    'num'	=>	$v['num'],
                    'total'	=>	floatval($v['price']*$v['num']),
                );
            }
        }else{
            $arr['order_details']	=	array();
        }
        if(empty($arr['info'])){
            $arr['info']	=	array();
        }
        $this->returnCode(0,$arr);
    }
    # 快店商品分类
    public function goods_sort(){
        $page	=	I('pindex',1);
        $store_id	=	I('store_id');
        $arr	=	array();
        if($store_id){
            $database_goods_sort = D(_view('Shop_goods_sort'));
            $sort_image_class = new goods_sort_image();
            $condition_goods_sort['store_id'] = $store_id;
            $count = $database_goods_sort->field(true)->where($condition_goods_sort)->count();
            $sort_list = $database_goods_sort->field(true)->where($condition_goods_sort)->order('`sort` DESC,`sort_id` ASC')->page($page,10)->select();
            if($sort_list){
                foreach ($sort_list as $key => $value) {
                    if (!empty($value['week'])) {
                        $week_arr = explode(',',$value['week']);
                        $week_str = '';
                        foreach ($week_arr as $k=>$v){
                            $week_str .= $this->get_week($v).' ';
                        }
                        $value['week_str'] = $week_str;
                    }
                    $image	=	$sort_image_class->get_image_by_path($value['image'],$this->config['site_url'],'s');
                    $value['image'] = $image===false?'':$image;
                    $arr['list'][]	=	$value;
                }
            }
        }else{
            $this->returnCode('20140029');
        }
        $arr['count']	=	ceil($count/10);
        $this->returnCode(0,$arr);
    }
    # 添加商品分类
    public function sort_add(){
        $store_id	=	I('store_id');
        $sort_name	=	I('sort_name');
        $sort		=	I('sort');
        $week		=	I('week');
        $sort_discount=	I('sort_discount');
        if (empty($sort_name)) {
            $this->returnCode('20140030');
        } else {
            $database_goods_sort = D('Shop_goods_sort');
            $data_goods_sort['store_id'] = $store_id;
            $data_goods_sort['sort_name'] = $sort_name;
            $data_goods_sort['sort'] = intval($sort);
            $data_goods_sort['status'] = intval($_POST['status']);
            $data_goods_sort['sort_discount'] = intval($sort_discount);
            if ($week) {
                $data_goods_sort['week'] = $week;
            }
            if ($database_goods_sort->data($data_goods_sort)->add()) {
                $this->returnCode(0);
            }else{
                $this->returnCode('20140031');
            }
        }
    }
    # 修改商品分类
    public function sort_edit(){
        $sort_id	=	I('sort_id');
        $sort_name	=	I('sort_name');
        $sort		=	I('sort');
        $week		=	I('week');
        $sort_discount=	I('sort_discount');
        if (empty($sort_name)) {
            $this->returnCode('20140030');
        } else {
            $database_goods_sort = D('Shop_goods_sort');
            $data_goods_sort['sort_name'] = $sort_name;
            $data_goods_sort['sort'] = intval($sort);
            $data_goods_sort['status'] = intval($_POST['status']);
            $data_goods_sort['sort_discount'] = intval($sort_discount);
            if ($week) {
                $data_goods_sort['week'] = $week;
            }
            if ($database_goods_sort->where(array('sort_id'=>$sort_id))->data($data_goods_sort)->save()) {
                $this->returnCode(0);
            } else {
                $this->returnCode('20140033');
            }
        }
    }
    # 删除分类
    public function sort_del(){
        $sort_id	=	I('sort_id');
        $store_id	=	I('store_id');
        if(empty($sort_id)){
            $this->returnCode('20140034');
        }
        if(empty($store_id)){
            $this->returnCode('20140033');
        }
        $count = D('Shop_goods')->where(array('sort_id' => $sort_id, 'store_id' => $store_id))->count();
        if ($count){
            $this->returnCode('20140035');
        }
        $database_goods_sort = D('Shop_goods_sort');
        $condition_goods_sort['sort_id'] = $sort_id;
        if ($database_goods_sort->where($condition_goods_sort)->delete()) {
            $this->returnCode(0);
        } else {
            $this->returnCode('20140036');
        }
    }
    # 快店商品分类下的商品
    public function goods_list(){
        $sort_id	=	I('sort_id');
        $store_id	=	I('store_id');
        $page		=	I('pindex',1);
        $database_goods = D('Shop_goods');
        $condition_goods['sort_id'] = $sort_id;
        $count_goods = $database_goods->where($condition_goods)->count();
        $goods_list = $database_goods->field(true)->where($condition_goods)->order('`sort` DESC, `goods_id` ASC')->page($page,10)->select();
        if(empty($goods_list)){
            $arr['list']	=	array();
            $arr['count']	=	0;
        }else{
            $plist = array();
            $sort_image_class = new goods_image();
            $prints = D('Orderprinter')->where(array('mer_id' => $this->merchant['mer_id'], 'store_id' => $store_id))->select();
            foreach ($prints as $l) {
                if ($l['is_main']) {
                    $l['name'] .= '(主打印机)';
                } else {
                    $l['name'] = $l['name'] ? $l['name'] : '打印机-' . $l['pigcms_id'];
                }
                $plist[$l['pigcms_id']] = $l;
            }
            foreach ($goods_list as &$rl) {
                $image_tmp = explode(';', $rl['image']);
                foreach($image_tmp as $v){
                    $tmp_image	=	$sort_image_class->get_image_by_path($v,'-1');
                    $image[]	=	array(
                        'url'	=>	$tmp_image['image'],
                        'sql'	=>	$v,
                    );
                }

                $rl['images'] = $image===false?'':$image;
                $arr['list'][] = array(
                    'print_name'=>	isset($plist[$rl['print_id']]['name']) ? $plist[$rl['print_id']]['name'] : '',
                    'goods_id'	=>	$rl['goods_id'],
                    'sort_id'	=>	$rl['sort_id'],
                    'store_id'	=>	$rl['store_id'],
                    'number'	=>	$rl['number'],
                    'name'		=>	$rl['name'],
                    'price'		=>	strval(floatval($rl['price'])),
                    'unit'		=>	$rl['unit'],
                    'stock_num'	=>	$rl['stock_num'],
                    'sell_count'=>	$rl['sell_count'],
                    'last_time'=>	date('Y-m-d H:i:s',$rl['last_time']),
                    'unit'		=>	$rl['unit'],
                    'old_price'	=>	strval(floatval($rl['old_price'])),
                    'seckill_price'	=>	strval(floatval($rl['seckill_price'])),
                    'seckill_open_time'=>	date('Y-m-d H:i:s',$rl['seckill_open_time']),
                    'seckill_close_time'=>	date('Y-m-d H:i:s',$rl['seckill_close_time']),
                    'seckill_type'=>	$rl['seckill_type'],
                    'seckill_stock'=>	$rl['seckill_stock'],
                    'sort'=>	$rl['sort'],
                    'status'=>	$rl['status'],
                    'sell_mouth'=>	$rl['sell_mouth'],
                    'today_sell_count'=>	$rl['today_sell_count'],
                    'reply_count'=>	$rl['reply_count'],
                    'is_properties'=>	$rl['is_properties'],
                    'number'	=>	$rl['number'],
                    'image'		=>	$image,
                );
                unset($image);
            }
            $arr['count']	=	$count_goods;
            $arr['page'] 	=	ceil($count_goods/10);
        }
        $this->returnCode(0,$arr);
    }
    # 商品状态
    public function goods_status(){
        $goods_id	=	I('goods_id');
        if($goods_id){
            $this->returnCode('20140023');
        }
        $type	=	I('type',1);
        $database_goods = D('Shop_goods');
        $condition_goods['goods_id'] = $goods_id;
        $data_goods['status'] =	$type;
        if($database_goods->where($condition_goods)->data($data_goods)->save()){
            $this->returnCode(0);
        }else{
            $this->returnCode('20140024');
        }
    }
    # 添加店铺商品
    public function goods_add(){
        $sort_id	=	I('sort_id');	//分类ID
        $store_id	=	I('store_id');	//店铺ID
        $name		=	I('name');		//商品名
        $unit		=	I('unit');		//单位
        $old_price	=	I('price');		//老价格
        $price		=	I('price');		//新价格
        $stock_num	=	'-1';			//库存
        $pic		=	I('pic');		//图片
        if(empty($sort_id)){
            $this->returnCode('20140034');
        }
        if(empty($store_id)){
            $this->returnCode('20140029');
        }
        if (empty($name)) {
            $this->returnCode('20140037');
        }
        if (empty($unit)) {
            $this->returnCode('20140038');
        }
        if (empty($price)) {
            $this->returnCode('20140039');
        }
        if (empty($pic)) {
            $this->returnCode('20140040');
        }
        $arr	=	array(
            'sort_id'	=>	$sort_id,
            'store_id'	=>	$store_id,
            'name'		=>	$name,
            'unit'		=>	$unit,
            'old_price'	=>	$old_price,
            'price'		=>	$price,
            'stock_num'	=>	$stock_num,
            'image'		=>	$pic,
            'last_time'	=>	$_SERVER['REQUEST_TIME'],
        );
        $goods_id = D('Shop_goods')->data($arr)->add();
        if ($goods_id) {
            $this->returnCode(0);
        } else {
            $this->returnCode('20140041');
        }
    }
    /* 编辑商品 */
    public function goods_edit(){
        $goods_id	=	I('goods_id');
        if(empty($goods_id)){
            $this->returnCode('20140023');
        }
        $sort_id	=	I('sort_id');
        $store_id	=	I('store_id');
        $name		=	I('name');
        $number		=	I('number');
        $unit		=	I('unit');
        $old_price	=	I('old_price');
        $price		=	I('price');
//		$stock_num	=	I('stock_num');
        $pic		=	I('pic');
        $des		=	I('des');
        $print_id	=	I('print_id');
        $specs		=	I('specs');
        $spec_val	=	I('spec_val');
        $properties	=	I('properties');
        $properties_val	=	I('properties_val');
        $prices		=	I('prices');
        if (empty($name)) {
            $this->returnCode('20140037');
        }
        if (empty($unit)) {
            $this->returnCode('20140038');
        }
        if (empty($price)) {
            $this->returnCode('20140039');
        }
        if (empty($pic)) {
            $this->returnCode('20140040');
        }
        $arr	=	array(
            'sort_id'	=>	$sort_id,
            'store_id'	=>	$store_id,
            'name'		=>	$name,
            'number'	=>	$number,
            'unit'		=>	$unit,
            'old_price'	=>	$old_price,
            'price'		=>	$price,
//			'stock_num'	=>	$stock_num,
            'image'		=>	$pic,
            'last_time'	=>	$_SERVER['REQUEST_TIME'],
        );
        $goods_id = D('Shop_goods')->where(array('goods_id'=>$goods_id))->data($arr)->save();
        if ($goods_id) {
            $this->returnCode(0);
        } else {
            $this->returnCode('20140041');
        }
    }
    # 删除店铺商品
    public function goods_del(){
        $goods_id	=	I('goods_id');
        $store_id	=	I('store_id');
        $database_goods = D('Shop_goods');
        $condition_goods['goods_id'] = $goods_id;
        if ($database_goods->where($condition_goods)->delete()) {
            $spec_obj = M('Shop_goods_spec'); //规格表
            $old_spec = $spec_obj->field(true)->where(array('goods_id' => $goods_id, 'store_id' => $store_id))->select();
            foreach ($old_spec as $os) {
                $delete_spec_ids[] = $os['id'];
            }
            $spec_obj->where(array('goods_id' => $goods_id, 'store_id' => $store_id))->delete();
            if ($delete_spec_ids) {
                $old_spec_val = M('Shop_goods_spec_value')->where(array('sid' => array('in', $delete_spec_ids)))->delete();
            }
            M('Shop_goods_properties')->where(array('goods_id' => $goods_id))->delete();
            $this->returnCode(0);
        }else{
            $this->returnCode('20140045');
        }
    }
    # 团购列表
    public function glist() {
        $database_group = D('Group');
        $condition_group['mer_id'] = $this->merchant['mer_id'];
        $keyword	=	I('keyword');
        $keyword 	=	isset($keyword) ? trim($keyword) : '';
        if (!empty($keyword)) {
            $condition_group.=' AND (s_name like "%' . $keyword . '%" OR name like "%' . $keyword . '%")';
        }
        $group_count = $database_group->where($condition_group)->count();
        $page	=	I('pindex',1);
        $group_list = $database_group->field(true)->where($condition_group)->order('`group_id` DESC')->page($page,10)->select();

        $group_image_class = new group_image();
        foreach ($group_list as $key => $value) {
            $tmp_pic_arr = explode(';', $value['pic']);
            if($value['begin_time'] > $_SERVER['REQUEST_TIME']){
                $type	=	L_('未开团');
            }else if($value['end_time'] < $_SERVER['REQUEST_TIME']){
                $type	=	L_(L_('已结束'));
            }else if($value['type'] == 3){
                $type	=	'已结束';
            }else if($value['type'] == 4){
                $type	=	L_('结束失败');
            }else{
                $type	=	L_('进行中');
            }
            $tmp_group_list[]	=	array(
                'group_id'	=>	$value['group_id'],
                's_name'	=>	$value['s_name'],
                'price'		=>	$value['price'],
                'old_price'	=>	$value['old_price'],
                'sale_count'	=>	$value['sale_count'],
                'count_num'	=>	$value['count_num'],	//库存	0是无限制
                'virtual_num'	=>	$value['virtual_num'],
                'begin_time'	=>	date('Y-m-d H:i:s',$value['begin_time']),
                'end_time'	=>	date('Y-m-d H:i:s',$value['end_time']),
                'deadline_time'	=>	date('Y-m-d H:i:s',$value['deadline_time']),
                'hits'		=>	$value['hits'],
                'reply_count'	=>	$value['reply_count'],
                'qrcode'	=>	$this->config['site_url'].'/index.php?g=Index&c=Recognition&a=see_qrcode&type=group&id='.$value['group_id'].'&img=1',
                'type'		=>	$type,
                'status'	=>	$value['status'],
                'list_pic'	=>	$group_image_class->get_image_by_path($tmp_pic_arr[0], 's'),
            );
        }
        $arr	=	array(
            'group_list'	=>	isset($tmp_group_list)?$tmp_group_list:array(),
            'group_count'	=>	$group_count,
            'page' 	=>	ceil($group_count/10),
        );
        $this->returnCode(0,$arr);
    }
    # 团购状态
    public function gorder_status(){
        $condition_group['group_id']	=	I('group_id');
        $data['status']	=	I('status',1);
        $database_group = D('Group');
        if($condition_group){
            $save	=	$database_group->where($condition_group)->data($data)->save();
        }else{
            $this->returnCode('20140049');
        }
        if($save){
            $this->returnCode(0);
        }else{
            $this->returnCode('20140028');
        }
    }
    # 团购订单
    public function gorder() {
        $group_id	=	I('group_id');
        if(empty($group_id)){
            $this->returnCode('20140048');
        }
        $status	=	I('status');
        $keyword	=	I('keyword');
        $status = isset($status) ? trim($status) : 'all';
        $keyword = isset($keyword) ? trim($keyword) : '';
        $where = 'gord.group_id=' . $group_id;
        if ($status != 'all') {
            $status = intval($status);
            if ($status == 0) {
                $where.=' AND (gord.paid="0" OR (gord.third_id ="0" AND gord.pay_type="offline"))';
            } else {
                $where.=' AND gord.status="' . ($status - 1) . '"';
            }
        }
        if (!empty($keyword)) {
            $where.=' AND (gord.phone like "%' . $keyword . '%" OR u.nickname like "%' . $keyword . '%" OR u.truename like "%' . $keyword . '%")';
        }
        //订单列表
        $group_orderDb = M('Group_order');
        $jointable = C('DB_PREFIX') . 'user';
        $group_orderDb->join('as gord LEFT JOIN ' . $jointable . ' as u on gord.uid=u.uid');
        $order_count = $group_orderDb->where($where)->count();
        $pindex	=	I('pindex');
        $pindex = intval(trim($pindex));
        $pindex = $pindex > 0 ? $pindex : 1;
        $pagsize = 10;
        $offsize = ($pindex - 1) * 20;
        $newdatas = array();
        $group_orderDb->join('as gord LEFT JOIN ' . $jointable . ' as u on gord.uid=u.uid');
        $order_list = $group_orderDb->field('gord.*,u.nickname,u.truename')->where($where)->order('gord.add_time DESC')->limit($offsize . ',' . $pagsize)->select();
//        $hasmore = $order_count > ($pindex * $pagsize) ? 1 : 0;
        if (!empty($order_list)) {
            foreach ($order_list as $kk => $vv) {
                $order_statuss	=	'';
                if ($vv['status'] == 3) {
                    $newdatas[$kk]['order_status'] = L_('已取消');
                } elseif ($vv['status'] == 4) {
                    $newdatas[$kk]['order_status'] = L_('已删除');
                } elseif ($vv['paid'] > 0) {
                    if (($vv['third_id'] == "0") && ($vv['pay_type'] == 'offline')) {
                        $newdatas[$kk]['order_status'] = L_('线下未付款');
                    } elseif ($vv['status'] == 0) {
                        $newdatas[$kk]['order_status'] = L_('已付款');
                        if ($vv['tuan_type'] != 2) {
                            $order_statuss=L_('未消费');
                        } else {
                            $order_statuss=L_('未发货');
                        }
                    } elseif ($vv['status'] == 1) {
                        if ($vv['tuan_type'] != 2) {
                            $newdatas[$kk]['order_status'] = L_('已消费');
                        } else {
                            $newdatas[$kk]['order_status'] = L_('已发货');
                        }
                    } else {
                        $newdatas[$kk]['order_status'] = L_('已完成');
                    }
                } else {
                    $newdatas[$kk]['order_status'] = L_('未付款');
                }
                $newdatas[$kk]['order_statuss']	=	isset($order_statuss)?$order_statuss:'';
                $newdatas[$kk]['order_id'] = $vv['order_id'];
                $newdatas[$kk]['nickname'] = !empty($vv['truename']) ? $vv['truename'] : $vv['nickname'];
                $newdatas[$kk]['phone'] = $vv['phone'];
                $newdatas[$kk]['address'] = $vv['adress'];
                $newdatas[$kk]['final_price'] = $vv['total_money'] - $vv['wx_cheap'];
                $newdatas[$kk]['num'] = L_('X1份',$vv['num']);
                $newdatas[$kk]['created'] = date('Y-m-d H:i:s', $vv['add_time']);
            }
        }
        unset($order_list);
        $arr	=	array(
            'order_count'	=>	$order_count,
            'list'		=>	$newdatas,
            'page' 	=>	ceil($order_count/10),
        );
        $this->returnCode(0,$arr);
    }
    #团购列表

    public function group_list()
    {
        $mer_id = $this->merchant['mer_id'];

        $condition_where = "mer_id='$mer_id'";
        $page	=	I('pindex',1);
        if($_POST['keyword']){
            switch($_POST['searchtype']){
                case 'group_id':
                    $condition_where .= " AND `group_id`=" . intval($_POST['keyword']);
                    break;
                case 's_name':
                    $condition_where .= " AND `s_name` LIKE '%" . $_POST['keyword'] . "%'";
                    break;
                case 'name':
                    $condition_where .= " AND `name` LIKE '%" . $_POST['keyword'] . "%'";
                    break;
            }
        }

        isset($_POST['status']) && $condition_where .= " AND status = ".$_POST['status'];

        $count = M('Group')->where($condition_where)->count();

        $list = M('Group')
            ->field('group_id,name,s_name,old_price,price,status,sale_count,pic,status,begin_time,end_time,group_cate')
            ->where($condition_where)
            ->page($page, '10')
            ->order('group_id DESC')
            ->select();
        $group_image_class = new group_image();
        $d_group_order = D('Group_order');
        foreach ($list as &$v) {
            if($v['begin_time'] > $_SERVER['REQUEST_TIME']){
                $group_status = L_('未开团');
            }else if($v['end_time'] < $_SERVER['REQUEST_TIME']){
                $group_status = L_(L_('已结束'));
            }else if($v['status'] == 3){
                $group_status = '已结束';
            }else if($v['status'] == 4){
                $group_status = L_('结束失败');
            }else{
                $group_status = L_('进行中');
            }
            $v['group_status_txt'] = $group_status;
            if($v['group_cate']=='cashing'){
                $v['pic'] = C('config.site_url').'/static/images/group/cashing.png';
            }else{
                $tmp_pic_arr = explode(';', $v['pic']);
                $v['pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0], 's');
            }

            $get_spec_sale_count = $d_group_order->get_spec_sale_count($v['group_id']);
            if ($get_spec_sale_count > 0) {
                $v['sale_count'] = $get_spec_sale_count + intval($v['sale_count']);
            }
        }
        if (empty($list)) {
            $arr['group_list'] = array();
        } else {
            $arr['group_list'] = $list;
        }
        $arr['page'] = ceil($count / 10);
        $arr['count'] = $count;
        $arr['status'] = 1;
        $this->returnCode(0, $arr);
    }


    #团购订单列表

    public function group_order_list()
    {
        $mer_id = $this->merchant['mer_id'];
        $order_id = $_POST['order_id'];
        $group_id = $_POST['group_id'];
        $page	=	I('pindex',1);
        $condition_where = "`o`.`uid`=`u`.`uid` AND `o`.`group_id`=`g`.`group_id` AND `o`.`mer_id`='$mer_id'";
        $group_id && $condition_where.=' AND o.group_id = '.$group_id;
        $order_id && $condition_where.=' AND o.order_id = '.$order_id;
        $condition_table = array(
            C('DB_PREFIX') . 'group' => 'g',
            C('DB_PREFIX') . 'group_order' => 'o',
            C('DB_PREFIX') . 'user' => 'u'
        );

        if(!empty($_POST['keyword'])){
            if ($_POST['searchtype'] == 'real_orderid') {
                $condition_where .= " AND `o`.`real_orderid`='" . htmlspecialchars($_POST['keyword'])."'";
            } elseif ($_POST['searchtype'] == 'orderid') {
                $where['orderid'] = htmlspecialchars($_POST['keyword']);
                $tmp_result = M('Tmp_orderid')->where(array('orderid'=>$_POST['keyword']))->find();
                $condition_where .= " AND `o`.`order_id`='" . htmlspecialchars($tmp_result['order_id'])."'";
            } elseif ($_POST['searchtype'] == 'name') {
                $condition_where .= " AND `u`.`nickname` like '%" . htmlspecialchars($_POST['keyword']) . "%'";
            } elseif ($_POST['searchtype'] == 'phone') {
                $condition_where .= " AND `u`.`phone`='" . htmlspecialchars($_POST['keyword']) . "'";
            } elseif ($_POST['searchtype'] == 's_name') {
                $condition_where .= " AND `g`.`s_name` like '%" . htmlspecialchars($_POST['keyword']) . "%'";
            }elseif ($_POST['searchtype'] == 'third_id') {
                $condition_where .= " AND `o`.`third_id` ='".$_POST['keyword']."'";
            }elseif ($_POST['searchtype'] == 'express_id') {
                $condition_where .= " AND `o`.`express_id` ='".$_POST['keyword']."'";
            }
        }

        $count = D('')->where($condition_where)
            ->table($condition_table)
            ->count();


        $order_list = D('')->field('`o`.`phone` AS `group_phone`,`o`.*,`g`.`s_name`,`g`.`pic`,`u`.`uid`,`u`.`nickname`,`u`.`phone`')
            ->where($condition_where)
            ->table($condition_table)
            ->page($page, '10')
            ->order('`o`.`add_time` DESC')
            ->select();
        if (empty($order_list)) {
            $arr['order_list'] = array();
        } else {
            $arr['order_list'] = $this->group_format($order_list);
        }
        $arr['page'] = ceil($count / 10);
        $arr['count'] = $count;
        $arr['status'] = 1;
        $this->returnCode(0, $arr);
    }


    private function group_format($order_list)
    {
        if ($order_list) {
            // 团购优惠组合
            $combineIds = array_column($order_list, 'combine_id');
            $combineList = array_column(D('Group_combine_activity')->where(['combine_id'=>['in',$combineIds]])->select(),'title','combine_id');

            foreach ($order_list as $v) {
                $status_format = $this->status_format($v['status'], $v['paid'], $v['third_id'],$v['pay_type'], $v['tuan_type'],$v['express_id'],$v['is_pick_in_store']);
                $group_image_class = new group_image();
                $all_pic = $group_image_class->get_allImage_by_path($v['pic']);

                // 团购优惠组合
                if($combineList[$v['combine_id']]){
                    $v['s_name'] .= '【'.$combineList[$v['combine_id']].'】';
                }

                $arr[] = array(
                    's_name' => $v['s_name'], // 名称
                    'num' => $v['num'], // 数量
                    'total_money' => $v['total_money'], // 总价
                    'status' => $status_format['status'], // 状态
                    'type' => $status_format['type'], // 团购券状态
                    'type_txt' => $status_format['type_txt'], // 团购券状态
                    'status_txt' => $status_format['status_txt'], // 团购券状态
                    'order_id' => $v['order_id'], // 团购ID
                    'pic' => $all_pic[0]['image'], // 图片
                    'pass_array' => $v['pass_array'], // 判断多个优惠券还是单个
                    'is_pick_in_store' => $v['is_pick_in_store'], // 取货确认
                    'tuan_type' => $v['tuan_type'], // 取货确认
                    'express' => $v['tuan_type'], // 取货确认
                );
            }
            return $arr;
        }
        return array();
    }


    public function group_status_change(){

        if($this->config['group_verify'] == 1 && $_POST['status']){
            $_POST['status'] = 2;
        }
        M('Group')->save($_POST);
        $this->returnCode(0,array('status'=>$_POST['status']));
    }
    # 团购订单详情
    public function group_edit() {
        $order_id	=	I('order_id');
        if(empty($order_id)){
            $this->returnCode('20140025');
        }
        $now_order = D('Group_order')->get_order_detail_by_id_and_merId($this->merchant['mer_id'],$order_id,false);
        if (empty($now_order)) {
            $this->returnCode('20140026');
        }
        $arr['store_id']	=	$now_order['store_id'];
        $arr['order_id']	=	$now_order['order_id'];
        $arr['s_name']	=	$now_order['s_name'];
        if($now_order['tuan_type'] == 0){
            $now_order['tuan_type']	=	L_('X1券',$this->config['group_alias_name']);
        }else if($now_order['tuan_type'] == 1){
            $now_order['tuan_type']	=	L_('代金券');
        }else{
            $now_order['tuan_type']	=	L_('实物');
        }
        if($now_order['status'] == 3){
            $arr['status']	=	L_('已取消');
        }else if($now_order['paid'] == 1){
            if($now_order['third_id'] == 0 && $now_order['pay_type'] == 'offline'){
                $arr['status']	=	L_('线下未付款');
            }else if($now_order['status'] == 0){
                $arr['status']	=	L_('已付款');
                if($now_order['tuan_type'] != 2){
                    $arr['statuss']	=	L_('未消费');
                }else{
                    $arr['statuss']	=	L_('未发货');
                }
            }else if($now_order['status'] == 1){
                $arr['status']	=	L_('待评价');
                if($now_order['tuan_type'] != 2){
                    $arr['statuss']	=	L_('已消费');
                }else{
                    $arr['statuss']	=	L_('已发货');
                }
            }else{
                $arr['status']	=	L_('已完成');
            }
        }else{
            $arr['status']	=	L_('未付款');
        }
        $arr['num']	=	$now_order['num'];
        $arr['price']	=	$now_order['price'];
        $arr['total_money']	=	$now_order['total_money'];
        $arr['score_used_count']	=	$now_order['score_used_count'];
        $arr['score_deducte']	=	$now_order['score_deducte'];
        if(!empty($now_order['coupon_id'])) {
            $system_coupon = D('System_coupon')->get_coupon_info($now_order['coupon_id']);
            $arr['system_coupon']	=	$system_coupon['price'];
        }else if(!empty($now_order['card_id'])) {
            $card = D('Member_card_coupon')->get_coupon_info($now_order['card_id']);
            $arr['card_coupon']	=	$card['price'];
        }
        if(empty($arr['system_coupon'])){
            $arr['system_coupon']	=	'';
        }
        if(empty($arr['card_id'])){
            $arr['card_coupon']	=	'';
        }
        $arr['pay']	=	$now_order['payment_money']+$now_order['balance_pay'];
        $arr['add_time']	=	date('Y-m-d H:i:s',$now_order['add_time']);
        $arr['pay_time']	=	date('Y-m-d H:i:s',$now_order['pay_time']);
        $arr['use_time']	=	date('Y-m-d H:i:s',$now_order['use_time']);
        $arr['last_staff']	=	$now_order['last_staff'];
        if($now_order['status'] > 0 && $now_order['status'] < 3){
            if($now_order['tuan_type'] != 2){
                $arr['font']	=	L_('消费');
            }else{
                $arr['font']	=	L_('发货');
            }
        }
        if (!empty($now_order['pay_type'])) {
            $arr['paytypestr'] = D('Pay')->get_pay_name($now_order['pay_type']);
            if (($now_order['pay_type'] == 'offline') && !empty($now_order['third_id']) && ($now_order['paid'] == 1)) {
                $arr['paytypestrs'] =L_('已支付');
            } else if (($now_order['pay_type'] != 'offline') && ($now_order['paid'] == 1)) {
                $arr['paytypestrs'] ='已支付';
            } else {
                $arr['paytypestrs'] =L_('未支付');
            }
        } else {
            if ($now_order['balance_pay'] > 0) {
                $arr['paytypestr'] = L_('平台余额支付');
            } elseif ($now_order['merchant_balance'] > 0) {
                $arr['paytypestr'] = L_('商家余额支付');
            } elseif ($now_order['paid']) {
                $arr['paytypestr'] = L_('其他');
            } else {
                $arr['paytypestr'] = L_('未支付');
            }
        }
        $arr['delivery_comment']	=	$now_order['delivery_comment'];
        if($now_order['paid'] == 1){
            $arr['uid']	=	$now_order['uid'];
            $arr['nickname']	=	$now_order['nickname'];
            $arr['order_phone']	=	$now_order['phone'];
            $arr['user_phone']	=	$now_order['user_phone'];
            if($now_order['tuan_type'] == 2){
                $arr['contact_name']	=	$now_order['contact_name'];
                $arr['phone']	=	$now_order['phone'];
                $arr['zipcode']	=	$now_order['zipcode'];
                $arr['adress']	=	$now_order['adress'];
                $arr['delivery_type']	=	$this->order_distribution($now_order['delivery_type']);
            }else{
                $arr['contact_name']	=	'';
                $arr['phone']	=	'';
                $arr['zipcode']	=	'';
                $arr['adress']	=	'';
                $arr['delivery_type']	=	'';
            }
            $arr['merchant_remark']	=	$now_order['merchant_remark'];
        }else{
            $arr['uid']	=	'';
            $arr['nickname']	=	'';
            $arr['order_phone']	=	'';
            $arr['user_phone']	=	'';
            $arr['contact_name']	=	'';
            $arr['phone']	=	'';
            $arr['zipcode']	=	'';
            $arr['adress']	=	'';
            $arr['delivery_type']	=	'';
            $arr['merchant_remark']	=	'';
        }
        $this->returnCode(0,$arr);
    }
    //	团购详情
    public function gdetail(){
        $order_id	=	I('order_id');
        if(empty($order_id)){
            $this->returnCode('20140025');
        }
        $now_order = D('Group_order')->get_order_detail_by_id_and_merId($this->merchant['mer_id'], $order_id, false);
        $now_group = M('Group')->where(array('group_id'=>$now_order['group_id']))->find();
        if (empty($now_order)) {
            $this->returnCode('20130006');
        }
        if(!empty($now_order['paid'])){
            if($now_order['is_pick_in_store']){
                $now_order['paytypestr']=L_("到店自提");
            }else{
                $now_order['paytypestr'] = D('Pay')->get_pay_name($now_order['pay_type']);
            }
            if(($now_order['pay_type']=='offline') && !empty($now_order['third_id']) && ($now_order['paid']==1)){
                $paytypestr	=	L_('已支付');
            }else if(($now_order['pay_type']!='offline') && ($now_order['paid']==1)){
                $paytypestr	=	L_('已支付');
            }else{
                $paytypestr	=	L_('未支付');
            }
        }else{
            $now_order['paytypestr'] = L_('未支付');
        }
        if($now_order['is_group_combine'] == 1){
            $order_type =   L_('优惠组合');
        }else if($now_order['tuan_type'] == 0){
            $order_type	=	L_('X1劵',$this->config['group_alias_name']);
        }else if($now_order['tuan_type'] == 1){
            $order_type	=	L_('代金券');
        }else{
            $order_type	=	L_('实物');
        }
        $status_format	=	$this->status_format($now_order['status'],$now_order['paid'],$now_order['third_id'],$now_order['pay_type'],$now_order['tuan_type']);
        if($now_order['status']>0 && $now_order['status']<3){
            if($now_order['tuan_type'] != 2){
                $operation	=	L_('消费');
            }else{
                $operation	=	L_('发货');
            }
        }
        $group_image_class = new group_image();
        $all_pic = $group_image_class->get_allImage_by_path($now_order['pic']);

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
        $internalCardPayMoney = 0;
        $internalCardPay = @json_decode($now_order['internal_card_pay'], true);
        if(is_array($internalCardPay)){
            $internalCardPayMoney = array_sum($internalCardPay);
        }
        $arr['now_order']	=	array(
            's_name'	=>	$now_order['s_name'],				//团购名
            'pic'	=>	$all_pic[0]['image'],					//团购名
            'order_id'	=>	$now_order['order_id'],				//订单ID
            'real_orderid'	=>	$now_order['real_orderid'],		//订单ID
            'group_id'	=>	$now_order['group_id'],				//团购ID
            'status_s'	=>	$now_order['status'],				//状态
            'is_pick_in_store'	=>	$now_order['is_pick_in_store'],				//状态
            'order_type'=>	$order_type,						//订单类型
            'status'	=>	$status_format['status'],			//订单状态
            'type'		=>	$status_format['type'],				//订单状态
            'pass_array'=>	isset($now_order['pass_array'])?$now_order['pass_array']:'',		//操作
            'group_pass'=>	$now_order['group_pass'],
            'num'		=>	(int)$now_order['num'],						//数量
            'price'		=>	$now_order['price'],						//单价
            'add_time'	=>	date('Y-m-d H:i',$now_order['add_time']),	//下单时间
            'pay_time'	=>	date('Y-m-d H:i:s',$now_order['pay_time']),	//付款时间
            'operation'	=>	isset($operation)?$operation:'',			//消费 发货
            'use_time'	=>	date('Y-m-d H:i:s',$now_order['use_time']),	//消费 发货  时间
            'last_staff'=>	$now_order['last_staff'],			//操作店员
            'paystatus'	=>	isset($paytypestr)?$paytypestr:'',	//已支付	未支付
            'paytypestr'=>	$now_order['paytypestr'],			//货到付款  未支付
            'delivery_comment'=>	$now_order['delivery_comment'],			//备注
            'total_money'	=>	$now_order['total_money'],			//总金额
            'merchant_remark'	=>	$now_order['merchant_remark'],
            'tuan_type'	=>	$now_order['tuan_type'],
            'pin_num'	=>	$now_group['pin_num'],
            'store_id'	=>	$now_order['store_id'],
            'discount'	=>	$now_order['card_discount'],
            'internal_card_pay_money' => $now_order['paid'] ? $internalCardPayMoney : 0,
            'refund_detail' => $refund_detail,
        );
        $pin_info = D('Group_start')->get_group_start_by_order_id($now_order['order_id']);
        $arr['pin_info']  = $pin_info;
        if(empty($pin_info) || $pin_info['status']==1 || $pin_info['status']==3){
            $arr['now_order']['can_assign_store'] = 1;
        }else{
            $arr['now_order']['can_assign_store'] = 0;
        }
        if($now_order['third_id']==0 && $now_order['pay_type']=='offline'){
            $arr['now_order']['total_moneys']	=	$now_order['total_money'];			//总金额
            $arr['now_order']['balance_pay']	=	$now_order['balance_pay'];			//平台余额支付
            $arr['now_order']['merchant_balance']	=	$now_order['merchant_balance']+$now_order['card_give_money'];	//商家会员卡余额支付
            if($now_order['wx_cheap']!='0.00'){
                $arr['now_order']['wx_cheap']	=	$now_order['wx_cheap'];				//微信优惠
            }else{
                $arr['now_order']['wx_cheap']	=	0;
            }
            $arr['now_order']['payment_money']	=	0;									//在线支付金额
            $arr['now_order']['payment']	=	$now_order['total_money']-$now_order['wx_cheap']-$now_order['merchant_balance']-$now_order['balance_pay']-$now_order['score_deducte']-$now_order['coupon_price'];	//线下需向商家付金额 红色字体
        }else{
            $arr['now_order']['total_moneys']	=	0;									//总金额
            $arr['now_order']['balance_pay']	=	$now_order['balance_pay'];			//平台余额支付
            $arr['now_order']['merchant_balance']=	$now_order['merchant_balance']+$now_order['card_give_money'];		//商家会员卡余额支付
            $arr['now_order']['wx_cheap']		=	0;									//微信优惠
            $arr['now_order']['payment_money']	=	$now_order['payment_money'];		//在线支付金额
            $arr['now_order']['payment']		=	0;
        }
        $arr['user']	=	array(
            'uid'	=>	$now_order['uid'],						//用户ID
            'nickname'	=>	$now_order['nickname'],				//用户名
            'phone'	=>	$now_order['phone'],					//订单手机号
            'user_phone'=>	$now_order['user_phone'],			//用户手机
        );
        $arr['distribution']	=	array(
            'contact_name'	=>	$now_order['contact_name'],			//联系名
            'phone'		=>	$now_order['phone'],					//联系电话
            'zipcode'	=>	$now_order['zipcode'],					//邮编
            'adress'	=>	$now_order['adress'],					//地址
            'express_id'	=>	$now_order['express_id'], 			//快递单号
            'express_type'	=>	$now_order['express_type'], 		//快递公司
            'merchant_remark'	=>	$now_order['merchant_remark'], //标记
        );
        switch($now_order['delivery_type']){
            case 1:
                $arr['distribution']['delivery_type']	=	L_('工作日、双休日与假日均可送货');
                break;
            case 2:
                $arr['distribution']['delivery_type']	=	L_('只工作日送货');
                break;
            case 3:
                $arr['distribution']['delivery_type']	=	L_('只双休日、假日送货');
                break;
            case 4:
                $arr['distribution']['delivery_type']	=	L_('白天没人，其它时间送货');
                break;
        }
        $express_list = D('Express')->get_express_list();
        if($express_list){
            foreach($express_list as &$v){
                if($v['id'] == $now_order['express_type']){
                    $arr['distribution']['express_name']	=	$v['name'];
                }
                $v['ids']	=	$v['id'];
                unset($v['code'],$v['url'],$v['sort'],$v['add_time'],$v['status'],$v['id']);
            }
            if(empty($arr['distribution']['express_name'])){
                $arr['distribution']['express_name']	=	$express_list[0]['name'];
                $arr['distribution']['express_type']	=	$express_list[0]['ids'];
            }
        }else{
            $express_list	=	array();
        }
        $arr['express_list']	=	$express_list;

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

        $this->returnCode(0,$arr);
    }
    //	团购状态格式化
    private function status_format($order_status,$paid,$third_id,$pay_type,$tuan_type){
        $type	=	0;
        $status	=	0;
        $status_txt='';
        $type_txt='';
        if($order_status	==	3){
            $status	=	1;	//已取消
            $status_txt=L_('已取消');
        }else if($paid){
            if($third_id==3 && $pay_type=='offline' && $order_status==0){
                $status	=	2;	//线下未付款
                $status_txt=L_('线下未付款');
            }else if($order_status==0){
                $status	=	3;	//已付款
                $status_txt=L_(L_('已付款'));
                if($tuan_type!=2){
                    $type	=	1;	//未消费
                    $type_txt='已付款';
                }else{
                    $type	=	2;	//未发货
                    $type_txt=L_('未发货');
                }
            }else if($order_status==1){
                $status	=	4;	//待评价
                $status_txt=L_('待评价');
                if($tuan_type!=2){
                    $type	=	3;	//已消费
                    $type_txt=L_('已消费');
                }else{
                    $type	=	4;	//已发货
                    $type_txt=L_('已发货');
                }
            }else{
                $status	=	5;	//已完成
                $status_txt=L_('已完成');
            }
        }else{
            $status	=	6;	//未付款
            $status_txt=L_('未付款');
        }
        $arr	=	array(
            'type'	=>	$type,
            'status'	=>	$status,
            'status_txt'	=>	$status_txt,
            'type_txt'	=>	$type_txt,
        );
        return $arr;
    }
    # 修改团购订单归属店铺
    public function order_store_id() {
        $order_id	=	I('order_id');
        if(empty($order_id)){
            $this->returnCode('20140025');
        }
        $now_order = D('Group_order')->get_order_detail_by_id_and_merId($this->merchant['mer_id'],$order_id, true, false);
        if (empty($now_order)) {
            $this->returnCode('20140026');
        }
        if (empty($now_order['paid'])) {
            $this->returnCode('20140027');
        }
        $condition_group_order['order_id'] = $now_order['order_id'];
        $data_group_order['store_id'] = I('store_id');
        if (D('Group_order')->where($condition_group_order)->data($data_group_order)->save()) {
            $this->returnCode(0);
        } else {
            $this->returnCode('20140028');
        }
    }
    # 团购订单额外信息
    public function group_remark() {
        $order_id	=	I('order_id');
        if(empty($order_id)){
            $this->returnCode('20140025');
        }
        $now_order = D('Group_order')->get_order_detail_by_id_and_merId($this->merchant['mer_id'], $order_id, true, false);
        if (empty($now_order)) {
            $this->returnCode('20140026');
        }
        if (empty($now_order['paid'])) {
            $this->returnCode('20140027');
        }
        $condition_group_order['order_id'] = $now_order['order_id'];
        $data_group_order['merchant_remark'] = I('merchant_remark');
        if (D('Group_order')->where($condition_group_order)->data($data_group_order)->save()) {
            $this->returnCode(0);
        } else {
            $this->returnCode('20140028');
        }
    }
    # 团购商品管理
    public function gpro() {
        $database_group = D('Group');
        $condition_group = 'mer_id=' . $this->merchant['mer_id'];
        $keyword	=	I('keyword');
        $keyword = isset($keyword) ? trim($keyword) : '';
        if (!empty($keyword)) {
            $condition_group.=' AND (s_name like "%' . $keyword . '%" OR name like "%' . $keyword . '%")';
        }
        $group_count = $database_group->where($condition_group)->count();
        $pindex	=	I('pindex');
        $pindex = intval(trim($pindex));
        $pindex = $pindex > 0 ? $pindex : 1;
        $pagsize = 20;
        $offsize = ($pindex - 1) * 20;
        $group_list = $database_group->field('group_id,mer_id,prefix_title,name,s_name,pic ,old_price,price,wx_cheap,discount,sale_count,status,type,tuan_type,qrcode_id')->where($condition_group)->order('`group_id` DESC')->limit($offsize . ',' . $pagsize)->select();
        $group_image_class = new group_image();
        foreach ($group_list as $key => $value) {
            $tmp_pic_arr = explode(';', $value['pic']);
            $group[]	=	array(
                'list_pic'	=>	$group_image_class->get_image_by_path($tmp_pic_arr[0], 's'),
                's_name'	=>	$value['s_name'],
                'old_price'	=>	floatval($value['old_price']),
                'price'		=>	floatval($value['price']),
                'wx_cheap'	=>	floatval($value['wx_cheap']),
                'sale_count'=>	$value['sale_count'],
                'group_id'	=>	$value['group_id'],
            );
        }
        $hasmore = $group_count > ($pindex * $pagsize) ? 1 : 0;
        $arr	=	array(
            'group_count'	=>	$group_count,
            'page'	=>	ceil($group_count/10),
            'list' => !empty($group) ? $group : array(),
        );
        $this->returnCode(0,$arr);
    }
    # 餐饮列表
    public function mlist(){
        $where['mer_id']	=	$this->merchant['mer_id'];
        $where['status']	=	array('elt','2');
        $page	=	I('pindex',1);
        $data = M('Merchant_store')->field(array('`mer_id`,`name`,`store_id`,`status`'))->where($where)->page($page,10)->select();
        if ($data != false) {
            foreach($data as &$v){
                $v['qrcode']	=	$this->erwm($v['store_id']);
            }
            $arr['data']	=	$data;
            $arr['all']		=	M('Merchant_store')->where($where)->count();
            $arr['status1'] =	M('Merchant_store')->where(array('status' => 1, 'mer_id' => $this->merchant['mer_id']))->count();
            $arr['status2'] =	M('Merchant_store')->where(array('status' => 2, 'mer_id' => $this->merchant['mer_id']))->count();
            $arr['page']	=	ceil($arr['all']/10);
        }else{
            $arr	=	array(
                'data' 		=>	array(),
                'all'		=>	array(),
                'status1'	=>	array(),
                'status2'	=>	array(),
            );
        }
        $this->returnCode(0,$arr);
    }
    # 餐饮店铺列表
    public function meal_list() {
        $where['mer_id']	=	$this->merchant['mer_id'];
        $where['status']		=	array('neq','2');
        $where['have_meal']		=	array('eq','1');
        $page	=	I('pindex',1);
        $all	=	M('Merchant_store')->where($where)->count();
        $data	=	M('Merchant_store')->field(array('`mer_id`,`name`,`store_id`,`status`,`phone`'))->where($where)->page($page,10)->select();
        foreach($data as &$v){
            $shop = D('Merchant_store_shop')->field('store_theme')->where(array('store_id' => $v['store_id']))->find();
            $store_theme = isset($shop['store_theme']) ? intval($shop['store_theme']) : 0;
            if ($store_theme) {
                $v['width'] = '900';
                $v['height'] = '900';
            } else {
                $v['width'] = '900';
                $v['height'] = '500';
            }
        }
        $arr['data']	=	isset($data)?$data:array();
        $arr['all']		=	$all;
        $arr['page'] 	=	ceil($arr['all']/10);
        $this->returnCode(0,$arr);
    }
    # 餐饮订单
    public function morder() {
        $mer_id = $this->merchant['mer_id'];
        $status	=	I('status');
        $keyword	=	I('keyword');
        $status = isset($status) ? trim($status) : 'all';
        $keyword = isset($keyword) ? trim($keyword) : '';
        $where = 'mord.mer_id=' . $mer_id;
        if ($status != 'all') {
            $status = intval($status);
            if ($status == 0) {
                $where.=' AND (mord.paid="0" OR (mord.third_id ="0" AND mord.pay_type="offline"))';
            } else {
                $where.=' AND mord.status="' . ($status - 1) . '"';
            }
        }
        if (!empty($keyword)) {
            $where.=' AND (mord.phone like "%' . $keyword . '%" OR mord.name like "%' . $keyword . '%")';
        }
        //订单列表
        $meal_orderDb = M('Meal_order');
        $jointable = C('DB_PREFIX') . 'merchant_store';
        $order_count = $meal_orderDb->join('as mord LEFT JOIN ' . $jointable . ' as ms on mord.store_id=ms.store_id')->where($where)->count();
        $pindex	=	I('pindex');
        $pindex = intval(trim($pindex));
        $pindex = $pindex > 0 ? $pindex : 1;
        $pagsize = 20;
        $offsize = ($pindex - 1) * 20;
        $newdatas = array();
        $order_list = $meal_orderDb->join('as mord LEFT JOIN ' . $jointable . ' as ms on mord.store_id=ms.store_id')->field('mord.*,ms.name as storename')->where($where)->order('order_id  DESC')->limit($offsize . ',' . $pagsize)->select();
        $hasmore = $order_count > ($pindex * $pagsize) ? 1 : 0;
        if (!empty($order_list)) {
            foreach ($order_list as $kk => $vv) {
                $order_statuss='';
                if ($vv['status'] == 3) {
                    $newdatas[$kk]['order_status'] = L_('已取消');
                } elseif ($vv['status'] == 4) {
                    $newdatas[$kk]['order_status'] = L_('已删除');
                } elseif ($vv['paid'] > 0) {
                    if (($vv['third_id'] == "0") && ($vv['pay_type'] == 'offline')) {
                        $newdatas[$kk]['order_status'] = L_('线下未付款');
                    } elseif ($vv['status'] == 0) {
                        $newdatas[$kk]['order_status'] = L_('已付款');
                        if ($vv['tuan_type'] != 2) {
                            $order_statuss=L_('未消费');
                        } else {
                            $order_statuss=L_('未发货');
                        }
                    } elseif ($vv['status'] == 1) {
                        if ($vv['tuan_type'] != 2) {
                            $newdatas[$kk]['order_status'] = L_('已消费');
                        } else {
                            $newdatas[$kk]['order_status'] = L_('已发货');
                        }
                    } else {
                        $newdatas[$kk]['order_status'] = L_('已完成');
                    }
                } else {
                    $newdatas[$kk]['order_status'] = L_('未付款');
                }
                $newdatas[$kk]['order_statuss']	=	isset($order_statuss)?$order_statuss:'';
                $newdatas[$kk]['order_id'] = $vv['order_id'];
                $newdatas[$kk]['nickname'] = $vv['name'];
                $newdatas[$kk]['storename'] = $vv['storename'];
                $newdatas[$kk]['phone'] = $vv['phone'];
                $newdatas[$kk]['address'] = $vv['address'];
                $newdatas[$kk]['final_price'] = $vv['total_price'] > 0 ? $vv['total_price'] - $vv['minus_price'] : $vv['price'] - $vv['minus_price'];
                $newdatas[$kk]['num'] = L_( 'X1道菜',$vv['total']);
                $newdatas[$kk]['created'] = date('Y-m-d H:i:s', $vv['dateline']);
            }
        }
        unset($order_list);
        $arr	=	array(
            'order_count'	=>	$order_count,
            'list'	=>	$newdatas,
            'page'	=>	ceil($order_count/10),
        );
        $this->returnCode(0,$arr);
    }
    # 餐饮订单详情
    public function mdetail() {
        $order_id	=	I('order_id');
        if(empty($order_id)){
            $this->returnCode('20140025');
        }
        $Meal_order	=	M('Meal_order');
        $order = $Meal_order->where(array('mer_id' => $this->merchant['mer_id'], 'order_id' => $order_id))->find();
        if(empty($order)){
            $this->returnCode('20140026');
        }
        $order['info'] = unserialize($order['info']);
        if (empty($order['third_id']) && $order['pay_type'] == 'offline') {
            $order['paid'] = 0;
        }
        if (!empty($order['pay_type'])) {
            $order['paytypestr'] = D('Pay')->get_pay_name($order['pay_type'], $order['is_mobile_pay'], $order['paid'], $order['order_id'], $order);
            if (($order['pay_type'] == 'offline') && !empty($order['third_id']) && ($order['paid'] == 1)) {
                $order['paytypestrs'] =' 已支付';
            } else if (($order['pay_type'] != 'offline') && ($order['paid'] == 1)) {
                $order['paytypestrs'] =L_('已支付');
            } else {
                $order['paytypestrs'] =L_('未支付');
            }
        } else {
            if ($order['balance_pay'] > 0) {
                $order['paytypestr'] = L_('平台余额支付');
            } elseif ($order['merchant_balance'] > 0) {
                $order['paytypestr'] = L_('商家余额支付');
            } elseif ($order['paid']) {
                $order['paytypestr'] = L_('其他');
            } else {
                $order['paytypestr'] = L_('未支付');
            }
        }
        $arr['order_id']	=	$order['order_id'];
        if(!empty($order['coupon_id'])) {
            $system_coupon = D('System_coupon')->get_coupon_info($order['coupon_id']);
            $this->assign('system_coupon',$system_coupon);
        }else if(!empty($order['card_id'])) {
            $card = D('Member_card_coupon')->get_coupon_info($order['card_id']);
            $this->assign('card', $card);
        }
        $mode = new Model();
        $sql = "SELECT u.name, u.phone FROM " . C('DB_PREFIX') . "deliver_supply AS s INNER JOIN " . C('DB_PREFIX') . "deliver_user AS u ON u.uid=s.uid WHERE s.order_id={$order['order_id']} AND s.item=0";
        $res = $mode->query($sql);
        $res = isset($res[0]) && $res[0] ? $res[0] : '';
        $arr	=	array(
            'order_id'	=>	$order['order_id'],
            'name'	=>	$order['name'],
            'phone'	=>	$order['phone'],
            'price'	=>	$order['price'],
            'address'	=>	$order['address'],
            'dateline'	=>	date('Y-m-d H:i:s',$order['dateline']),
            'arrive_time'	=>	$order['arrive_time']==0?0:date('Y-m-d H:i:s',$order['arrive_time']),
            'use_time'	=>	$order['use_time']==0?0:date('Y-m-d H:i:s',$order['use_time']),
            'note'	=>	$order['note'],
            'tuan_type'	=>	isset($order['tuan_type'])?$order['tuan_type']:0,	//不等于2 消费时间use_time，2发货时间use_time
            'balance_pay'	=>	$order['balance_pay'],
            'score_deducte'	=>	$order['score_deducte'],
            'score_used_count'	=>	$order['score_used_count'],
            'payment_money'	=>	$order['payment_money'],
            'merchant_balance'	=>	$order['merchant_balance'],
            'coupon_price'	=>	$order['coupon_price'],
            'card_price'	=>	floatval($order['card_price']),
            'paytypestr'	=>	$order['paytypestr'],
            'paytypestrs'	=>	$order['paytypestrs'],
            'info'			=>	isset($order['info'])?$order['info']:array(),
            'status'		=>	$order['status'],	//0未使用	1已使用	2已评价	3已退款	4已取消
            'user_name'		=>	isset($order['deliver_user_info']['name'])?$order['deliver_user_info']['name']:'',
            'user_phone'	=>	isset($order['deliver_user_info']['phone'])?$order['deliver_user_info']['phone']:'',
            'deliver_user_info'	=>	$res,
        );
        if($order['total_price'] == 0){
            $arr['total_price']	=	$order['total_price']-$order['minus_price']-$order['balance_pay']-$order['merchant_balance']-$order['coupon_price']-$order['card_price']-floatval($order['score_deducte']);
        }else{
            $arr['total_price']	=	$order['price']-$order['balance_pay']-$order['merchant_balance']-$order['coupon_price']-$order['card_price']-floatval($order['score_deducte']);
        }
        $this->returnCode(0,$arr);
    }
    # 餐饮商品分类
    public function meal_sort(){
        $store_id	=	I('store_id');
        $page		=	I('pindex',1);
        $database_meal_sort = D('Meal_sort');
        $condition_merchant_sort['store_id'] = $store_id;
        $count_sort = $database_meal_sort->where($condition_merchant_sort)->count();
        $sort_list = $database_meal_sort->field(true)->where($condition_merchant_sort)->order('`sort` DESC,`sort_id` ASC')->page($page,10)->select();
        foreach($sort_list as $key=>$value){
            if(!empty($value['week'])){
                $week_arr = explode(',',$value['week']);
                $week_str = '';
                foreach($week_arr as $k=>$v){
                    $week_str .= $this->get_week($v).' ';
                }
                $sort_list[$key]['week_str'] = $week_str;
            }
        }
        $arr	=	array(
            'sort_list'	=>	$sort_list,
            'count'		=>	$count_sort,
            'page'		=>	ceil($count_sort/10),
        );
        $this->returnCode(0,$arr);
    }
    # 餐饮商品
    public function mpro() {
        $database_meal = D('Meal');
        $sort_id	=	I('sort_id');
        $condition_meal = 'sort_id in (' . $sort_id . ')';
        $keyword	=	I('keyword');
        $keyword = isset($keyword) ? trim($keyword) : '';
        if (!empty($keyword)) {
            $condition_meal.=' AND (name like "%' . $keyword . '%")';
        }
        $count_meal = $database_meal->where($condition_meal)->count();
        $pindex	=	I('pindex',1);
        $pagsize = 20;
        $offsize = ($pindex - 1) * 20;
        $meal_list = $database_meal->field(true)->where($condition_meal)->order('`sort` DESC,`meal_id` ASC')->limit($offsize . ',' . $pagsize)->select();
        $meal_image_class = new meal_image();
        if (!empty($meal_list)) {
            foreach ($meal_list as $mk => $mv) {
                $meal[$mk]['list_pic'] = $meal_image_class->get_image_by_path($mv['image'], $this->config['site_url'], 's');
                $meal[$mk]['s_name'] = $mv['name'];
                $meal[$mk]['meal_id'] = $mv['meal_id'];
                $meal[$mk]['sort_id'] = $mv['sort_id'];
                $meal[$mk]['store_id'] = $mv['store_id'];
                $meal[$mk]['sell_count'] = $mv['sell_count'];
                $meal[$mk]['statusstr'] = $mv['status'] == 1 ? L_('在售') : L_('停售');
                $meal[$mk]['statusoptstr'] = $mv['status'] == 1 ? L_('下架') : L_('上架');
                $meal[$mk]['statusopt'] = $mv['status'] == 1 ? '0' : '1';
                $meal[$mk]['old_price'] = floatval($mv['old_price']);
                $meal[$mk]['price'] = floatval($mv['price']);
            }
        }
        $hasmore = $count_meal > ($pindex * $pagsize) ? 1 : 0;
        $arr	=	array(
            'list'	=>	!empty($meal) ? $meal : array(),
            'count'	=>	$count_meal,
            'page'		=>	ceil($count_meal/10),
        );
        $this->returnCode(0,$arr);
    }
    public function getstore_id_Bymerid($mer_id, $name = false) {
        $tmpdatas = M('merchant_store')->field('store_id,name')->where(array('mer_id' => $mer_id, 'have_meal' => '1', 'status' => '1'))->select();
        if ($name)
            return $tmpdatas;
        $storeids = array();
        if (!empty($tmpdatas)) {
            foreach ($tmpdatas as $vv) {
                $storeids[] = $vv['store_id'];
            }
        }
        return $storeids;
    }
    # 餐饮商品上架、下架
    public function mstatusopt() {
        $status		=	I('status');
        $meal_id	=	I('meal_id');
        $store_id	=	I('store_id');
        if ($store_id > 0 && $meal_id > 0) {
            if (M('Meal')->where(array('store_id' => $store_id, 'meal_id' => $meal_id))->save(array('status' => $status))) {
                $this->returnCode(0);
            }else{
                $this->returnCode('20140046');
            }
        }
        $this->returnCode('20140047');
    }
    # 餐饮商品删除
    public function mdel() {
        $meal_id	=	I('meal_id');
        $store_id	=	I('store_id');
        if (M('Meal')->where(array('store_id' => $store_id, 'meal_id' => $meal_id))->delete()) {
            $this->returnCode(0);
        } else {
            $this->returnCode('20140045');
        }
    }
    # 预约列表
    public function appoint(){
        $database_appoint = D('Appoint');
        $database_merchant = D('Merchant');
        $database_category = D('Appoint_category');
        $condition_appoint['mer_id'] = $this->merchant['mer_id'];
        $appoint_count = $database_appoint->where($condition_appoint)->count();
        $pindex	=	I('pindex',1);
        $appoint_info = $database_appoint->field(true)->where($condition_appoint)->order('`appoint_id` DESC')->page($pindex,10)->select();
        $merchant_info = $database_merchant->field(true)->where('mer_id = ' . $this->merchant['mer_id'] . '')->select();
        $category_info = $database_category->field(true)->where($condition_appoint)->select();
        $appoint_list = $this->formatArray($appoint_info, $merchant_info, $category_info);
        foreach($appoint_list as &$v){
            $v['start_time']	=	date('Y-m-d H:i:s',$v['start_time']);
            $v['end_time']	=	date('Y-m-d H:i:s',$v['end_time']);
            if($v['appoint_status'] == 1){
                $v['appoint_status'] = 0;
            }else{
                $v['appoint_status'] = 1;
            }
            $tmp_pic_arr = explode(';', $v['pic']);
            $appoint_image_class = new appoint_image();
            foreach ($tmp_pic_arr as $key => $value) {
                $pic_list[$key]['title'] = $value;
                $pic_list[$key]['url'] = $appoint_image_class->get_image_by_path($value, 's');

            }
            if(strpos($pic_list[0]['url'],'http')!==false){
                $v['pic']	=	$pic_list[0]['url'];

            }else{
                $v['pic']	=$this->config['site'].$pic_list[0]['url'];
            }
            $v['qrcode_id']	=	$this->config['site_url'].'/index.php?g=Index&c=Recognition&a=see_qrcode&type=appoint&id='.$v['appoint_id'].'&img=1';
            $v['appoint_worker_name'] = $this->config['appoint_worker_name']?$this->config['appoint_worker_name']:L_('技师');
            unset($v['office_time'],$v['appoint_pic_content'],$v['is_store'],$v['cat_fid'],$v['cat_id'],$v['create_time'],$v['mer_id'],$v['pics']);
        }
        $arr	=	array(
            'count'         =>  isset($appoint_count)?$appoint_count:0,
            'appoint_list'	=>	isset($appoint_list)?$appoint_list:array(),
            'page'	=>	ceil($appoint_count/10),
        );
        $this->returnCode(0,$arr);
    }

    public function appoint_list() {
        $mer_id = $this->merchant['mer_id'];
        $database_order = D('Appoint_order');
        $database_user = D('User');
        $database_appoint = D('Appoint');
        $database_store = D('Merchant_store');
        $order_id	=	$_POST['order_id'];
        $page = I('pindex',1);
        $where['o.mer_id'] = $mer_id;
        $appoint_id	= $_POST['appoint_id']??'';

        if(!empty($_POST['keyword'])){
            if ($_POST['searchtype'] == 'order_id') {
                $where['o.order_id'] = $_POST['keyword'] ;
            } elseif ($_POST['searchtype'] == 'orderid') {
                $where['orderid'] = htmlspecialchars($_POST['keyword']);
                $tmp_result = M('Tmp_orderid')->where(array('orderid'=>$_POST['keyword']))->find();
                $where['o.order_id'] = $tmp_result['order_id'] ;
            } elseif ($_POST['searchtype'] == 'name') {
                $where['u.nickname'] = array('like','%'.htmlspecialchars($_POST['keyword']) .'%') ;
            } elseif ($_POST['searchtype'] == 'phone') {
                $where['u.phone'] = array('like','%'.htmlspecialchars($_POST['keyword']) .'%') ;
            } elseif ($_POST['searchtype'] == 'third_id') {
                $where['o.third_id'] = $_POST['keyword'] ;
            }
        }

        if($_POST['pay_type']){
            $where['o.pay_type'] = $_POST['pay_type'];
        }
        if($_POST['stime']&&$_POST['etime']){
            $stime = strtotime($_POST['stime']);
            $etime = strtotime($_POST['etime'])+86400;
            $where['_string'] = " o.order_time >= {$stime} AND o.order_time >={$etime}";
        }

        if (isset($_POST['service_status']) && $_POST['service_status'] !=-1) {
            $where['o.service_status'] = $_POST['service_status'];
        }
        if($appoint_id){
            $where['o.appoint_id'] = $appoint_id;
        }

        $count = $database_order->join('as o LEFT JOIN '.C('DB_PREFIX').'user AS u ON u.uid  = o.uid')->where($where)->count();
        if($order_id){
            $where['order_id']	=	array('lt',$order_id);
        }
        $order_info = $database_order->field('o.*,u.nickname,u.phone')->join('as o LEFT JOIN '.C('DB_PREFIX').'user AS u ON u.uid  = o.uid')->where($where)->page($page,10)->order('`o`.`order_id` DESC')->select();

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
                $appoint_date = $v['appoint_date'].' '.$v['appoint_time'];
                if ($appoint_date==-1) {
                    $appoint_date = '未选择预约时间';
                }
                $arr['order_list'][]	=	array(
                    'order_id'	=>	$v['order_id'],
                    'appoint_name'	=>	$v['appoint_name'],
                    'truename'	=>	$truename,
                    'phone'	=>	$v['phone'],
                    'appoint_date'	=>	$appoint_date,
                    'payment_money'	=>	floatval($v['payment_money']),
                    'appoint_price'	=>	floatval($v['appoint_price']),
                    'paid'			=>	$v['paid'],
                    'service_status'=>	$v['service_status'],
                    'appoint_type'=>	$v['appoint_type'],
                    'order_time'=>	date('Y-m-d H:i:s',$v['order_time']),
                    'product_payment_price' => $v['product_payment_price'] ? floatval($v['product_payment_price']) : 0,
                    'is_initiative' =>$v['is_initiative'],
                    'product_id'=>$v['product_id'],
                    'product_price'=>$v['product_price'],
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
        //$where['mer_id'] = $this->store['mer_id'];

//        if($this->staff_session['is_change']==0){
//            // 店员没有修改价格的权限
//            $this->returnCode('20130055');
//        }

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

    # 预约列表--修改状态
    public function appoint_status(){
        $database_appoint = D('Appoint');
        $condition_appoint['appoint_id']	=	I('appoint_id');
        $data['appoint_status']	=	I('appoint_status');
        if($data['appoint_status'] == 1){
            $data['appoint_status']	=	0;
        }else{
            $data['appoint_status']	=	1;
        }
        if(empty($condition_appoint)){
            $this->returnCode('20140050');
        }
        $appoint_info = $database_appoint->where($condition_appoint)->data($data)->save();
        if($appoint_info){
            $this->returnCode(0);
        }else{
            $this->returnCode('20140028');
        }
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
                $val['store_name'] = $store_array[$val['store_id']]['store_name'];
                $val['store_adress'] = $store_array[$val['store_id']]['store_adress'];
                $val['appoint_type'] = $appoint_array[$val['appoint_id']]['appoint_type'];
            }
        }
        return $order_info;
    }

    public function appointDetail()
    {
        $where = array('mer_id' => $this->merchant['mer_id']);
        $where['order_id'] = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;


        $orderInfo = D('Appoint_order')->field(true)->where($where)->find();
        if (empty($orderInfo)) {
            $this->returnCode(1, null, L_('订单信息不存在'));
        }

        $orderInfo['order_time'] = $orderInfo['order_time'] ? date('Y-m-d H:i:s', $orderInfo['order_time']) : 0;
        $orderInfo['user_pay_time'] = $orderInfo['user_pay_time'] ? date('Y-m-d H:i:s', $orderInfo['user_pay_time']) : 0;

        $orderInfo['user_name'] = '';
        $orderInfo['user_phone'] = '';
        if ($userInfo = D('User')->field('`uid`, `phone`, `nickname`')->where(array('uid' => $orderInfo['uid']))->find()) {
            $orderInfo['user_name'] = $userInfo['nickname'];
            $orderInfo['user_phone'] = $userInfo['phone'];
        }

        $appointInfo = M('Appoint')->where(array('appoint_id'=>$orderInfo['appoint_id']))->field('`appoint_id`, `appoint_name`, `appoint_type`, `appoint_price`')->find();
        $orderInfo['appoint_name'] = $appointInfo['appoint_name'];

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
        if ($product_detail['status']) {
            $orderInfo['product_detail'] = $product_detail['detail'];
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

        $orderInfo['cue_list'] = $cue_list ?: '';
        $orderInfo['appoint_worker_name'] = $this->config['appoint_worker_name']?$this->config['appoint_worker_name']:L_('技师');
        if ($orderInfo['appoint_date']==-1 && !$orderInfo['appoint_time']) {
            $orderInfo['appoint_date'] = '未选择预约时间';
        }
        $this->returnCode(0, $orderInfo);

    }
    /*验证预约服务*/
    public function appoint_verify(){
        $database_order = D('Appoint_order');
        $where['mer_id'] = $this->merchant['mer_id'];
        $where['order_id'] = $_POST['order_id'];
        $now_order = $database_order->field(true)->where($where)->find();
        if(empty($now_order)){
            $this->returnCode('20130006');
        } else if ($now_order['paid']!=2 && $now_order['service_status'] == 0) {
            if($now_order['is_apply_refund']){
                $this->error(L_('当前订单已申请退款！'));
            }
            $condition_group['appoint_id'] = $now_order['appoint_id'];
            D('Appoint')->where($condition_group)->setInc('appoint_sum',1);

            $fields['verify_time'] = time();
            $fields['last_time'] = time();
            $fields['service_status'] = 1;
            if($database_order->where($where)->data($fields)->save()){

                //验证增加商家余额
                $order_info['order_id'] = $now_order['order_id'];
                $order_info['mer_id'] = $now_order['mer_id'];
                $order_info['store_id'] = $now_order['store_id'];
                $order_info['order_type'] = 'appoint';
                $order_info['balance_pay'] = $now_order['balance_pay'];
                $order_info['score_deducte'] = $now_order['score_deducte'];
                $order_info['payment_money'] = $now_order['pay_money'];
                $order_info['is_own'] = $now_order['is_own'];
                $order_info['merchant_balance'] = $now_order['merchant_balance'];
                $order_info['score_used_count'] = $now_order['score_used_count'];
                $order_info['money'] = (($now_order['balance_pay'] == $now_order['payment_money']) ? 0 : $now_order['balance_pay'])
                    + $now_order['score_deducte']
                    + $now_order['payment_money']
                    + $now_order['merchant_balance']
                    + $now_order['product_score_deducte']
                    + $now_order['product_balance_pay']
                    + $now_order['product_coupon_price']
                    + $now_order['user_pay_money']
                    + $now_order['product_merchant_balance'];

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

                $appoint_name = M('Appoint')->field('appoint_name')->where(array('appoint'=>$now_order['appoint_id']))->find();
                //D('Merchant_money_list')->add_money($this->store['mer_id'],'用户预约'.$appoint_name['appoint_name'].'记入收入',$order_info);
                $order_info['desc']=L_('用户预约X1记入收入',$appoint_name['appoint_name']);
                $order_info['score_discount_type']=$now_order['score_discount_type'];
                D('SystemBill')->bill_method($order_info['is_own'],$order_info);
                $now_user = D('User')->get_user($order_info['uid']);
                D('Merchant_spread')->add_spread_list($order_info,$now_user,'appoint',$now_user['nickname'].L_("用户购买预约获得佣金"));
                //if($this->config['open_score_get_percent']==1){
                //	$score_get = $this->config['score_get_percent']/100;
                //}else{
                //	$score_get = $this->config['user_score_get'];
                //}
                if($this->config['add_score_by_system_commission']==0 && $this->config['add_score_by_percent']==0 && ($this->config['open_score_discount']==0 || $order_info['score_discount_type']!=2)){
                    if($order_info['is_own'] && $this->config['user_own_pay_get_score']!=1){
                        $order_info['payment_money'] = 0;
                    }
                    $this->config['score_get'] = D('Percent_rate')->get_user_add_score_times($this->config['score_get'],$order_info['uid']);
                    D('User')->add_score($order_info['uid'], (($order_info['balance_pay']+$order_info['payment_money']) * $this->config['score_get']), L_('购买预约商品获得X1',$this->score_name));
                }
                $this->returnCode(0);
            } else {
                $this->returnCode('20130007');
            }
        }else{
            $this->returnCode('20130008');
        }
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
    # 预约订单--访店员中心
    public function order_list(){
        $store_id = I('appoint_id');
        $database_order = D('Appoint_order');
        $database_user = D('User');
        $database_appoint = D('Appoint');
        $database_store = D('Merchant_store');
        $order_id	=	I('order_id');
        $where['appoint_id'] = $store_id;
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
    # 预约订单
    public function order_list1(){
        $database_order = D('Appoint_order');
        $database_user = D('User');
        $database_appoint = D('Appoint');
        $database_store = D('Merchant_store');
        $database_merchant_workers_appoint = D('Merchant_workers_appoint');
        $appoint_id	=	I('appoint_id');
        if ($appoint_id) {
            $where['appoint_id'] = $appoint_id;
        }else{
            $this->returnCode('20140050');
        }
        $merchant_worker_id	=	I('merchant_worker_id');
        if ($merchant_worker_id) {
            $where['merchant_worker_id'] = $merchant_worker_id;
        }
        $where['mer_id'] = $this->merchant['mer_id'];
        $where['store_id'] = array('neq', 0);
        $order_count = $database_order->where($where)->count();
        $pindex	=	I('pindex',1);
        $order_info = $database_order->field(true)->where($where)->order('`order_id` DESC')->page($pindex,10)->select();
        $uidArr = array();
        foreach ($order_info as $v) {
            array_push($uidArr, $v['uid']);
        }
        $uidArr = array_unique($uidArr);
        $user_info = $database_user->field('`uid`, `phone`, `nickname`')->where(array('uid' => array('in', $uidArr)))->select();
        $appoint_info = $database_appoint->field('`appoint_id`, `appoint_name`, `appoint_type`')->select();
        $store_info = $database_store->field('`store_id`, `name`, `adress`')->select();
        $order_list = $this->formatOrderArray($order_info, $user_info, $appoint_info, $store_info);
        if($order_list){
            foreach($order_list as $v){
                $order[]	=	array(
                    'order_id'	=>	$v['order_id'],
                    'payment_money'	=>	$v['payment_money'],
                    'store_name'	=>	$v['store_name'],
                    'store_adress'	=>	$v['store_adress'],
                    'appoint_type'	=>	$v['appoint_type'],
                    'uid'		=>	$v['uid'],
                    'nickname'	=>	$v['nickname'],
                    'phone'		=>	$v['phone'],
                    'content'	=>	isset($v['content'])?$v['content']:'',
                    'paid'		=>	$v['paid'],
                    'service_status'=>	$v['service_status'],
                    'is_del'	=>	$v['is_del'],
                    'order_time'=>	date('Y-m-d H:i:s',$v['order_time']),
                    'pay_time'	=>	$v['pay_time']==0?0:date('Y-m-d H:i:s',$v['pay_time']),
                    'type'		=>	$v['type'],
                );
            }
        }
        $arr	=	array(
            'count'	=>	$order_count,
            'order_list'	=>	isset($order)?$order:array(),
            'page'	=>	ceil($order_count/10),
        );
        $this->returnCode(0,$arr);
    }

    public function pay_list(){
        $pay_method = D('Config')->get_pay_method('','',1);
        foreach ($pay_method as $key=>$v) {
            $temp[$key] = $v['name'];
        }
        $this->returnCode(0,$temp);
    }
    /*预约订单查找*/
    public function appoint_find(){
        $database_order = D('Appoint_order');
        $database_user = D('User');
        $database_appoint = D('Appoint');
        $database_store = D('Merchant_store');
        $order_id	=	I('order_id');
        $find_type	=	I('find_type');
        $find_value	=	I('find_value');
        $appoint_where['mer_id'] = $this->merchant['mer_id'];
        if($find_type == 1 && strlen($find_value) == 16){
            $appoint_where['appoint_pass'] = $find_value;
        } else {
            if($find_type == 1){
                $appoint_where['appoint_pass'] = array('LIKE', '%'.$find_value.'%');
            } else if($find_type == 2){
                $appoint_where['order_id'] = $find_value;
            } else if($find_type == 3){
                $appoint_where['appoint_id'] = $find_value;
            } else if($find_type == 4){
                $user_where['uid'] = $find_value;
            } else if($find_type == 5){
                $user_where['nickname'] = array('LIKE', '%'.$find_value.'%');
            } else if($find_type == 6){
                $user_where['phone'] = array('LIKE', '%'.$find_value.'%');
            }
        }
        if($order_id && $find_type != 2){
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
            $arr['count'] = 1;
            $arr['page'] = 1;
        }else{
            $arr['count'] = 0;
            $arr['page'] = 0;
            $arr['order_list']	=	array();
        }
        $arr['status']	=	2;
        $this->returnCode(0,$arr);
    }
    //删除预约订单
    public function appoint_del(){
        $order_id	=	I('order_id');
        if (empty($order_id)) {
            $this->returnCode('20140050');
        }
        $database_appoint_order = D('Appoint_order');
        $where['order_id'] = $order_id;
        $data['del_time'] = time();
        $data['is_del'] = 3;
        $result = $database_appoint_order->where($where)->data($data)->save();
        if ($result) {
            /*-----记录系统订单信息 订单取消成功 改写状态已取消 2020/8/7----*/
            D("System_order")->save_order_status('appoint',$order_id,5);
            $this->returnCode(0);
        } else {
            $this->returnCode('20140053');
        }
    }

    # 预约工作人员列表
    public function merchant_worker(){
        //工作人员列表
        $Map['status'] = 1;
        $Map['mer_id'] = $this->merchant['mer_id'];
        $database_merchant_workers = D('Merchant_workers');
        $merchant_worker_list = $database_merchant_workers->where($Map)->field(array('merchant_worker_id','name'))->select();
        if(empty($merchant_worker_list)){
            $merchant_worker_list	=	array();
        }
        $this->returnCode(0,$merchant_worker_list);
    }
    # 预约更改服务人员
    public function worker(){
        $order_id	=	I('order_id');
        if (empty($order_id)) {
            $this->returnCode('20140050');
        }
        $database_appoint_order = D('Appoint_order');
        $where['order_id'] = $order_id;
        $data['merchant_worker_id']	=	I('merchant_worker_id');
        $data['merchant_allocation_time']	=	time();
        if(empty($where)){
            $this->returnCode('20140054');
        }
        $result = $database_appoint_order->where($where)->data($data)->save();
        if($result){
            $this->returnCode(0);
        }else{
            $this->returnCode('20140028');
        }
    }
    # 订单详情
    public function order_detail(){
        $database_order = D('Appoint_order');
        $database_user = D('User');
        $database_appoint = D('Appoint');
        $database_store = D('Merchant_store');
        $database_merchant_workers = D('Merchant_workers');
        $database_appoint_visit_order_info = D('Appoint_visit_order_info');
        $database_appoint_product = D('Appoint_product');
        $order_id = I('order_id');
        if(empty($order_id)){
            $this->returnCode('20140025');
        }
        $where['order_id'] = $order_id;
        $where['mer_id'] = $this->merchant['mer_id'];

        $now_order = $database_order->field(true)->where($where)->find();
        $where_user['uid'] = $now_order['uid'];
        $user_info = $database_user->field('`uid`, `phone`, `nickname`')->where($where_user)->find();
        $where_appoint['appoint_id'] = $now_order['appoint_id'];
        $appoint_info = $database_appoint->field('`appoint_id`, `appoint_name`, `appoint_type`, `appoint_price`')->where($where_appoint)->find();
        $where_store['store_id'] = $now_order['store_id'];
        $store_info = $database_store->field('`store_id`, `name`, `adress`')->where($where_store)->find();

        $now_order['phone'] = $user_info['phone'];
        $now_order['nickname'] = $user_info['nickname'];
        $now_order['appoint_name'] = $appoint_info['appoint_name'];
        $now_order['appoint_type'] = $appoint_info['appoint_type'];
        $now_order['appoint_price'] = $appoint_info['appoint_price'];
        $now_order['store_name'] = $store_info['name'];
        $now_order['store_adress'] = $store_info['adress'];


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
        $product_detail = $database_appoint_product->get_product_info($now_order['product_id']);
        if ($product_detail['status']) {
            $now_order['product_detail'] = $product_detail['detail'];
        }
        $order	=	array(
            'order_id'	=>	$now_order['order_id'],
            'store_id'	=>	$now_order['store_id'],
            'store_name'	=>	$now_order['store_name'],
            'appoint_name'	=>	$now_order['appoint_name'],
            'nickname'	=>	$now_order['nickname'],
            'phone'	=>	$now_order['phone'],
            'appoint_date'	=>	$now_order['appoint_date'],
            'appoint_time'	=>	$now_order['appoint_time'],
            'order_time'	=>	$now_order['order_time']==0?0:date('Y-m-d H:i:s',$now_order['order_time']),
            'payment_money'	=>	$now_order['payment_money'],
            'appoint_type'	=>	$now_order['appoint_type'],
            'appoint_price'	=>	$now_order['appoint_price'],
            'paid'			=>	$now_order['paid'],
            'service_status'=>	$now_order['service_status'],
            'is_del'		=>	$now_order['is_del'],
            'payment_money'	=>	$now_order['payment_money'],
            'del_time'	=>	$now_order['del_time']==0?0:date('Y-m-d H:i:s',$now_order['del_time']),
            'detail_name'	=>	isset($now_order['product_detail']['name'])?$now_order['product_detail']['name']:'',
            'detail_price'	=>	isset($now_order['product_detail']['price'])?$now_order['product_detail']['price']:'',
            'content'		=>	isset($now_order['content'])?$now_order['content']:'',
            'store_name'	=>	$now_order['store_name'],
            'tuan_type'		=>	isset($now_order['tuan_type'])?$now_order['tuan_type']:'',
            'use_time'	=>	$now_order['use_time']==0?0:date('Y-m-d H:i:s',$now_order['use_time']),
            'last_staff'	=>	$now_order['last_staff'],
            'uid'			=>	$now_order['uid'],
            'merchant_balance'=>	$now_order['merchant_balance'],
            'balance_pay'	=>	$now_order['balance_pay'],
            'pay_money'	=>	$now_order['pay_money'],
            'last_time'	=>	$now_order['last_time']==0?0:date('Y-m-d H:i:s',$now_order['last_time']),
            'longs'	=>	isset($long)?$long:0,
            'lats'	=>	isset($lat)?$lat:0,
        );
        //上门预约工作人员信息end
        $arr	=	array(
            'cue_list'	=>	$cue_list,
            'now_order'	=>	$order,
        );
        $this->returnCode(0,$arr);
    }
    # 预约格式化数据
    protected function formatArray($appoint_info, $merchant_info, $category_info){
        if (!empty($merchant_info)) {
            $merchant_array = array();
            foreach ($merchant_info as $val) {
                $merchant_array[$val['mer_id']]['mer_name'] = $val['name'];
            }
        }
        if (!empty($category_info)) {
            $category_array = array();
            foreach ($category_info as $val) {
                $category_array[$val['cat_id']]['category_name'] = $val['cat_name'];
                $category_array[$val['cat_id']]['is_autotrophic'] = $val['is_autotrophic'];
            }
        }
        if (!empty($appoint_info)) {
            foreach ($appoint_info as &$val) {
                $val['mer_name'] = $merchant_array[$val['mer_id']]['mer_name'];
                $val['category_name'] = $category_array[$val['cat_id']]['category_name'];
                $val['is_autotrophic'] = $category_array[$val['cat_id']]['is_autotrophic'];
            }
        }
        return $appoint_info;
    }
    # 店铺列表--选择使用
    public function merchant_store($type=''){
        $store = M('Merchant_store')->field('store_id,name')->where(array('status' => 1, 'mer_id' => $this->mer_id))->select();
        if ($store == false){
            $this->returnCode('20140015');
        }
        if(empty($store)){
            $store = array();
        }
        if($type == 1){
            return $store;
        }
        $this->returnCode(0,$store);
    }
    # 店员列表
    public function staff() {
        $database_merchant_store = M('Merchant_store');
        $mer_id = $this->merchant['mer_id'];
        $all_store = $database_merchant_store->where(array('mer_id' => $mer_id, 'status' => 1))->field('store_id,mer_id,name,status')->order('sort desc,store_id  ASC')->select();
        if (empty($all_store)) {
            $this->returnCodeError(L_('店铺不存在！'));
        }
        $allstore = array();
        foreach ($all_store as $vv) {
            $allstore[$vv['store_id']] = $vv;
        }
        $where['token'] = $mer_id;
        $_POST['store_id'] && $where['store_id'] = $_POST['store_id'];
        $staffList = M('Merchant_store_staff')->where($where)->order('`id` desc')->select();
        $arr = array();
        if (!empty($staffList)) {
            foreach ($staffList as $sv) {
                $sv['staff_id']	=	$sv['id'];
                $ticket = ticket::create($sv['id'], $this->DEVICE_ID, true);
                $sv['ticket']	=	$ticket['ticket'];
                if (isset($allstore[$sv['store_id']])) {
                    $sv['storename'] = $allstore[$sv['store_id']]['name'];
                    $sv['mer_id'] = $allstore[$sv['store_id']]['mer_id'];
                    unset($sv['id'],$sv['password']);
                    $arr[] = $sv;
                }
            }
        }
        unset($staff_list, $allstore, $all_store);
        $this->returnCode(0,$arr);
    }
    # 店员添加
    public function staff_add() {
        $data['tel'] = I('tel');
        $data['name'] = I('name');
        $data['username'] = I('username');
        $data['store_id'] = I('store_id');
        $data['type'] = I('type');
        $data['phone_country_type'] = I('phone_country_type','');
        $data['time'] = $_SERVER['REQUEST_TIME'];
        $data['tel'] = phone_format($data['phone_country_type'], $data['tel']);

        if(empty($data['store_id'])){
            $this->returnCode('20140048');
        }
        $data['password'] = md5(I('password'));
        $data['token'] = $this->merchant['mer_id'];
        $checkUserName = M('Merchant_store_staff')->where(array('username' => $data['username']))->find();
        if ($checkUserName) {
            $this->returnCode('20140017');
        }
        $sql = M('Merchant_store_staff')->add($data);
        if ($sql == false) {
            $this->returnCode('20140018');
        } else {
            $sql['staff_id']	=	$sql['id'];
            unset($sql['id']);
            $this->returnCode(0);
        }
    }

    public function staff_type(){
        $this->returnCode(0,array(0=>L_('店小二'),1=>L_('核销员'),2=>L_('店长')));
    }
    # 店员修改
    public function staff_edit() {
        $data['tel'] = I('tel');
        $data['name'] = I('name');
        $data['username'] = I('username');
        $data['is_change'] = I('is_change');
        $data['type'] = I('type');
        $data['phone_country_type'] = I('phone_country_type','');
        $data['tel'] = phone_format($data['phone_country_type'], $data['tel']);

        $password	=	I('password');
        if($password){
            $data['password'] = md5(I('password'));
        }
        $where['token'] = $this->merchant['mer_id'];
        $where['id'] = I('staff_id');
        $sql = M('Merchant_store_staff')->where($where)->save($data);
        if ($sql == false) {
            $this->returnCode('20140019');
        } else {
            $this->returnCode(0);
        }
    }

    public function staff_detail(){
        $id	=	I('staff_id');
        if ($id == false){
            $this->returnCode('20140020');
        }
        $staff = M('Merchant_store_staff')->where(array('id' => $id, 'token' => $this->merchant['mer_id']))->find();
        if ($staff == false){
            $this->returnCode('20140021');
        }
        $staff['staff_id']	= $staff['id'];
        unset($staff['token'],$staff['last_time'],$staff['time'],$staff['openid'],$staff['id']);
        $staff['phone_country_type'] = empty($staff['phone_country_type'])?L_('区号'): $staff['phone_country_type'];
        $this->returnCode(0,$staff);
    }
    # 店员删除
    public function staff_dell() {
        $id = I('staff_id');
        if ($id == false)
            $this->returnCode('20140020');
        $staff = M('Merchant_store_staff')->where(array('id' => $id, 'token' => $this->merchant['mer_id']))->delete();
        if ($staff == false) {
            $this->returnCode('20140022');
        } else {
            $this->returnCode(0);
        }
    }
    # 打印机设备列表
    public function hardware() {
        $where = array('mer_id' => $this->mer_id);
        $store_id = I('store_id', '');
        $store_id && $where['store_id']  = $store_id;
        $staffList = M('Orderprinter')->where($where)->select();
        foreach($staffList as &$v){
            $store = M('Merchant_store')->field('name')->where(array('store_id'=>$v['store_id']))->find();
            $v['store_name']	=	$store['name'];
        }
        if(empty($staffList)){
            $staffList	=	array();
        }
        $this->returnCode(0,$staffList);
    }
    # 添加和修改打印机
    public function hardware_add() {
        // $this->returnCode('20140013');
        $status	=	I('status',2);
        if ($status == 1) {
            $data['is_main']	=	I('is_main');
            $data['name']		=	I('name');
            $data['mcode']		=	I('mcode');
            $data['username']	=	I('username');
            $data['mkey']		=	I('mkey');
            $data['mp']			=	I('mp');
            $data['count']		=	I('count');
            $data['paid']		=	I('paid');
            $data['store_id']	=	intval($_POST['store_id']);
            $data['mer_id']		=	$this->mer_id;
            $pigcms_id			=	intval($_POST['pigcms_id']);
            $data['image']		=	intval($_POST['image']);
            $data['paper']		=	intval($_POST['paper']);
            $data['is_big']		=	intval($_POST['is_big']);
            $data['device_type']		=	intval($_POST['device_type']);

            $data['print_type']				=	intval($_POST['print_type']);
            $data['print_bluetooth_name']		=	$_POST['print_bluetooth_name'];
            $data['print_bluetooth_code']		=	$_POST['print_bluetooth_code'];
            $data['print_mobile_code']		=	$_POST['print_mobile_code']?$_POST['print_mobile_code']:'';

            $data['version'] = isset($_POST['version']) ? intval($_POST['version']) : '';
            $data['feie_ukey'] = isset($_POST['feie_ukey']) ? trim($_POST['feie_ukey']) : '';
            $data['feie_user'] = isset($_POST['feie_user']) ? trim($_POST['feie_user']) : '';
            $data['language'] = isset($_POST['language']) ? trim($_POST['language']) : '';
            $data['booking_order_print']		=	I('booking_order_print');
            $data['mall_print']		=	I('mall_print');
            $data['store_print']		=	I('store_print');
            
            if($pigcms_id >0){
                $result = M('Orderprinter')->where(array('pigcms_id'=>$pigcms_id,'mer_id'=>$data['mer_id']))->save($data);
            }else{
                $data['add_time'] = time();
                $result = M('Orderprinter')->add($data);
            }
            if ($result == false) {
                if($pigcms_id){
                    $this->returnCode('20140014');
                }else{
                    $this->returnCode('20140013');
                }
            } else {
                $this->returnCode(0,$pigcms_id > 0 ? L_('修改成功') : L_('添加成功'));
            }
        } else {
            $pigcms_id=I('pigcms_id');
            if($pigcms_id>0){
                $Orderprinter	=	M('Orderprinter')->where(array('pigcms_id' => $pigcms_id, 'mer_id' => $this->merchant['mer_id']))->find();
                if(empty($Orderprinter)){
                    $Orderprinter	=	array();
                }
            }else{
                $pigcms_id=0;
                $Orderprinter=array();
            }
            $this->returnCode(0,$Orderprinter);
        }
    }
    # 删除打印机
    public function hardware_del() {
        $pigcms_id	=	I('pigcms_id');
        if ($pigcms_id == false) $this->returnCodeError(L_('非法操作'));
        $staff = M('Orderprinter')->where(array('pigcms_id' => $pigcms_id, 'mer_id' => $this->mer_id))->delete();
        if ($staff !== false) {
            $this->returnCode(0);
        } else {
            $this->returnCode('20140016');
        }
    }
    # 换取星期
    protected function get_week($num) {
        switch ($num) {
            case 1:
                return L_('星期一');
            case 2:
                return L_('星期二');
            case 3:
                return L_('星期三');
            case 4:
                return L_('星期四');
            case 5:
                return L_('星期五');
            case 6:
                return L_('星期六');
            case 0:
                return L_('星期日');
            default:
                return '';
        }
    }
    # 订单配送时间
    protected function order_distribution($num) {
        switch ($num) {
            case 1:
                return L_('工作日、双休日与假日均可送货');
            case 2:
                return L_('只工作日送货');
            case 3:
                return L_('只双休日、假日送货');
            case 4:
                return L_('白天没人，其它时间送货');
            default:
                return '';
        }
    }
    # 验证ticket
    private function ticket() {
        $ticket = I('ticket', false);
        if ($ticket) {
            $info = ticket::get($ticket, $this->DEVICE_ID, true);
            if (!$info) {
                $this->returnCode('20140012');
            }
        }else{
            $this->returnCode('20140011');
        }
        return $info['uid'];
    }
    # 获取省市区
    public function select_area(){
        $pid	=	I('pid',0);
        $city	=	$this->select_area_array($pid);
        $area_type	=	I('area_type',1);
        if(!empty($city)){
            if($area_type == 1){
                $arr = isset($city[0])?$this->del_field($city[0],2):array();
            }else if($area_type){
                $city_list =	M('Area')->where(array('area_pid'=>$pid,'is_open'=>1))->find();
                if($city_list){
                    $city_p =	M('Area')->where(array('area_id'=>$city_list['area_pid'],'is_open'=>1))->find();
                    if($city_p){
                        $city_pp = D('Area')->get_arealist_by_areaPid($city_p['area_pid'],1);
                        if($city_pp){
                            $arr =	$this->del_field($city_pp,1);
                        }else{
                            $arr =	$this->del_field($city_p,1);
                        }
                    }else{
                        $arr[] =	$this->del_field($city_list,1);
                    }
                }else{
                    $arr =	array();
                }
            }
            $this->returnCode(0,$arr);
        }else{
            $this->returnCode('20046027');
        }
    }
    # 获取省市区
    private function select_area_array($pid){
        $area_list[] = D('Area')->get_arealist_by_areaPid($pid,1);
        if($area_list){
            if($area_list[0][0]['area_type'] == 3){
                return $area_list;
            }else{
                $area_list[] = D('Area')->get_arealist_by_areaPid($area_list[0][0]['area_id'],1);
                return $area_list;
            }
        }else{
            return null;
        }
    }
    # 删除多余字段
    private function del_field($arr,$type){
        if($type == 2){
            foreach($arr as &$v){
                unset($v['area_sort'],$v['first_pinyin'],$v['is_open'],$v['area_url'],$v['area_ip_desc'],$v['area_type'],$v['is_hot'],$v['url']);
            }
        }else if($type == 1){
            unset($arr['area_sort'],$arr['first_pinyin'],$arr['is_open'],$arr['area_url'],$arr['area_ip_desc'],$arr['area_type'],$arr['is_hot'],$arr['url']);
        }
        return $arr;
    }
    # 图片上传
    public function up_img(){
        if ($_FILES['imgFile']['error'] != 4) {
            if($_POST['wxcard_img']){
                $path = 'merchant';
                $image = D('Image')->handle($this->merchant['mer_id'], $path, 1);
                if ($image['error']) {
                    $this->returnCode('20140052');
                } else {
                    $url = $_SERVER['DOCUMENT_ROOT'].$image['url']['imgFile'];
                    $mode = D('Access_token_expires');
                    $res = $mode->get_access_token();
                    import('ORG.Net.Http');
                    $http = new Http();
                    $file  = $url;
                    $return = $http->curlUploadFile('https://api.weixin.qq.com/cgi-bin/media/uploadimg?access_token='.$res['access_token'],$file,1);
                    $return = json_decode($return,true);
                    //$arr['url']  =$return['url'];
                    //$this->returnCode(0,$arr);
                    $return['root_img'] = $this->config['site_url'].$image['url']['imgFile'];
                    exit(json_encode(array('errorCode' => 0,'errorMsg'=>'success', 'result' => $return)));
                }
            }else{
                $store_id = I('store_id');
                $shop = D('Merchant_store_shop')->field('store_theme')->where(array('store_id' => $store_id))->find();
                $store_theme = isset($shop['store_theme']) ? intval($shop['store_theme']) : 0;
                if ($store_theme) {
                    $width = '900,450';
                    $height = '900,450';
                } else {
                    $width = '900,450';
                    $height = '500,250';
                }
                $param = array('size' => $this->config['group_pic_size']);
                $param['thumb'] = true;
                $param['imageClassPath'] = 'ORG.Util.Image';
                $param['thumbPrefix'] = 'm_,s_';
                $param['thumbMaxWidth'] = $width;
                $param['thumbMaxHeight'] = $height;
                $param['thumbRemoveOrigin'] = false;
                $image = D('Image')->handle($this->merchant['mer_id'], 'goods', 1, $param);
                if ($image['error']) {
                    $this->returnCode('20140052');
                } else {
                    $arr	=	array(
                        'url'	=>	$this->config['site_url'].$image['url']['file'],
                        'title'	=>	$image['title']['file'],
                    );
                    $this->returnCode(0,$arr);
                }
            }
        } else {
            $this->returnCode('20140051');
        }

    }

    //商家余额接口
    public function merchant_money_info(){
        $mer_id  = $this->merchant['mer_id'];
        $type = !empty($_POST['type'])?$_POST['type']:'group';
        $now_mer = M('Merchant')->where(array('mer_id'=>$mer_id))->find();
        if($this->config['open_allinyun']==1){
            import('@.ORG.AccountDeposit.AccountDeposit');
            $deposit = new AccountDeposit('Allinyun');
            $allyun = $deposit->getDeposit();
            $allinyun = M('Merchant_allinyun_info')->where(array('mer_id'=>$mer_id))->find();
            $allyun->setUser($allinyun);
            if($allinyun['status']!=0){
                $balance = $allyun->queryBalance();
            }
            $now_mer['money'] = floatval($balance['signedResult']['allAmount']/100);
        }
        $arr['merchant_money'] = $this->config['Currency_symbol'].$now_mer['money'];            //商家余额
        $arr['merchant_money_num'] = $now_mer['money'];            //商家余额
        $arr['store_list'] = $this->get_store_name($mer_id,$type);
        $all_arr = array(
            'name'=> L_("全部店铺"),
            'store_id'=> ""
        );
        $store_list = $arr['store_list'];
        if ($arr['store_list'] && is_array($store_list)) {
            array_unshift($store_list,$all_arr);
            $arr['store_list'] = $store_list;
        } else {
            $arr['store_list'][] = $all_arr;
        }

        $where['mer_id'] = $mer_id;
        $where['income'] = 1;
        $where['type'] = array('neq','withdraw');
        $today_zero = strtotime(date('Y-m-d'));
        $where['_string'] = 'use_time>'.$today_zero.' AND use_time <='.($today_zero+86400);
        $today_money  = M('Merchant_money_list')->where($where)->sum('money');
        $arr['today_count'] =  M('Merchant_money_list')->where($where)->count();

        $where['income'] = 2;
        $today_out_money  = M('Merchant_money_list')->where($where)->sum('money');
        $today_money=floatval($today_money-$today_out_money);
        $arr['today_money']  = empty($today_money)?$this->config['Currency_symbol'].'0':$this->config['Currency_symbol'].$today_money;
        $arr['fh'] = $this->config['Currency_txt'] ? $this->config['Currency_txt'] : '元';

        $arr['merchant_score'] = 0;
        $arr['lock_merchant_score'] = 0;
        $arr['show_merchant_score'] = isset($this->config['open_add_score_by_system_commission']) ? 1 : 0;
        if ( $arr['show_merchant_score']) {
            $lock_score = D('Merchant_get_score')->field('sum(score_count) as score_count,sum(score_get) as score_get')->where(['mer_id' => $mer_id])->find();
            $arr['merchant_score'] = round($now_mer['merchant_score'], 4);
            $arr['lock_merchant_score'] = round($lock_score['score_count'] - $lock_score['score_get'], 4);
        }


        $arr['ecard_pay_money'] = D('InternalMembershipCard')->getDayIncome($mer_id, $today_zero-86400)['pay_money'];
        $arr['ecard_pay_money'] = $this->config['Currency_symbol'].$arr['ecard_pay_money'];

        $arr['ecard_pay_count'] = D('InternalMembershipCard')->getDayOrderCount($mer_id, $today_zero);

        //查询银联商户可提现金额
        $chinaums_data = D('Merchant_withdraw_chinaums')->queryChinaumsWithdraw($mer_id);
        $arr['chinaums_merno'] = $chinaums_data['chinaums_merno'];
        $arr['chinaums_money'] = $chinaums_data['money'];
        $arr['chinaums_money_txt'] = $this->config['Currency_symbol'].$chinaums_data['money'];

        //提现最低金额
        $arr['company_least_money'] =   $this->config['min_withdraw_money']?$this->config['min_withdraw_money']:$this->config['company_least_money'];

        //商家收入明细是否显示拉卡拉钱账通
        $qianzhangtongArchive = M('Lakala_qianzhangtong_archives')->where(['item_type' => 'merchant', 'item_id' => $this->merchant['mer_id']])->find();
        if ($qianzhangtongArchive) {
            $arr['show_lakala_qianzhangtong'] = 1;
            unset($where['income']);
            $thirdMoney = M('Merchant_money_list')->where($where)->field('SUM(IF(income=1,merchant_get,merchant_get*-1)) AS thrid_income_money')->find();
            $arr['lakala_qianzhangtong_income_money'] = $this->config['Currency_symbol'].getFormatNumber($thirdMoney['thrid_income_money']);
        } else {
            $arr['show_lakala_qianzhangtong'] = 0;
        }

        $this->returnCode(0,$arr);
    }

    //获取业务类型
    public  function get_alias_name(){
        $arr[]='group';
        $arr[]='shop';
        $arr[]='group';
        $arr[]='meal';
        if(C('config.appoint_page_row')>0){
            $arr[]='appoint';
        }
        if(C('config.is_cashier')	||C('config.pay_in_store')){
            $arr[]='store';
        }
        if(C('config.is_open_weidian')){
            $arr[]='weidian';
        }
        if(C('config.wxapp_url')){
            $arr[]='wxapp';
        }
        return $arr;
    }

    //业务类型中文
    public  function get_alias_c_name(){
        $arr = [];
        if($this->config['group_alias_name']){
            $arr[]=array('type'=>'group','name'=>$this->config['group_alias_name']);
        }
        if($this->config['shop_alias_name']){
            $arr[]=array('type'=>'shop','name'=>$this->config['shop_alias_name']);
        }
        if($this->config['mall_alias_name']){
            $arr[]=array('type'=>'mall','name'=>$this->config['mall_alias_name']);
        }
        if($this->config['mall_alias_name']){
            //$arr[]=array('type'=>'mall','name'=>$this->config['mall_alias_name']);
        }
        if($this->config['meal_alias_name']){
            $arr[]=array('type'=>'meal','name'=>$this->config['meal_alias_name']);
        }


        // 开启多语言
        if (!C('config.open_multilingual')) {
            if(C('config.appoint_page_row')>0) {
                $arr[] = array('type' => 'appoint', 'name' => $this->config['appoint_alias_name']);
            }
            if(C('config.is_cashier')   ||C('config.pay_in_store')) {
                $arr[] = array('type' => 'store', 'name' => L_('优惠买单'));
            }
            if ($this->config['pay_in_store']|| $this->config['is_cashier']) {
                $arr[]=array('type'=>'cash','name'=>L_('到店支付'));
            }
            if(C('config.is_open_weidian')) {
                $arr[] = array('type' => 'weidian', 'name' => L_('微店'));
            }
            if(C('config.wxapp_url')) {
                $arr[] = array('type' => 'wxapp', 'name' => L_('营销'));
            }
            $arr[]=array('type'=>'withdraw','name'=>L_('提现'));
            if(!$this->config['single_system']){
                $arr[]=array('type'=>'activity','name'=>L_('平台活动'));
            }
            $arr[]=array('type'=>'merchant_spread','name'=>L_('商家推广佣金'));
            if(!$this->config['single_system']){
                $arr[]=array('type'=>'market','name'=>L_('批发'));
            }
            if($this->config['merchant_recommend_wholesale_switch'] == 1){
                $arr[]=array('type'=>'market_spread','name'=>L_('批发佣金'));
            }
        }
        return $arr;
    }

    /**
     * @return 选择分类
     */
    public  function get_alias_c_name2(){
        return array(
            'all'=>L_('选择分类'),
            'group'=>$this->config['group_alias_name'],
            'shop'=>$this->config['shop_alias_name'],
            'meal'=>$this->config['meal_alias_name'],
            'appoint'=>$this->config['appoint_alias_name'],
            'waimai'=>$this->config['waimai_alias_name'],
            'store'=>L_('到店'),
            'weidian'=>L_('微店'),
            'wxapp'=>L_('营销'),
            'withdraw'=>L_(L_(L_('提现'))),
            'coupon'=>'提现',
            'withdraw'=>'提现',
            'activity'=>L_('平台活动'),
            'spread'=>L_('商家推广佣金'),
            'mall'=>$this->config['mall_alias_name_new'],
        );
    }

    public function get_store_name($mer_id,$type){
        $store_list = D('Merchant_store')->field('store_id,name,have_group,have_meal,have_shop')->where(array('mer_id'=>$mer_id, 'status' => array('neq', 4)))->select();
        if($type=='group'){
            foreach($store_list as $key=>$value){
                if(empty($value['have_group'])){
                    unset($store_list[$key]);
                }
            }
        }else if($type=='meal'){
            foreach($store_list as $key=>$value){
                if(empty($value['have_meal'])){
                    unset($store_list[$key]);
                }
            }
        }else if($type=='shop' || $type=='mall'){
            foreach($store_list as $key=>$value){
                if(empty($value['have_shop'])){
                    unset($store_list[$key]);
                }
            }
        }else{
            $store_list=$store_list;
        }
        $arr=array();
        foreach($store_list as $key=>$value){
            unset($store_list[$key]['have_group']);
            unset($store_list[$key]['have_meal']);
            unset($store_list[$key]['have_shop']);
            $arr[]=$store_list[$key];
        }
        return $arr;
    }

    //获取商家余额按时间统计数据
    public function merchant_money_date(){
        $mer_id = intval($this->merchant['mer_id']);
        $store_id = $_POST['store_id'];
        $day  = $_POST['day'];
        if(empty($_POST['period']) || $_POST['period'] == '-'){
            $_POST['period'] = date('Y/m/d').'-'.date('Y/m/d');
        }
        if(isset($_POST['period'])&&!empty($_POST['period'])){
            $period = explode('-',$_POST['period']);
            $_POST['begin_time'] = $period[0];
            $_POST['end_time'] = $period[1];
            if ($_POST['begin_time']>$_POST['end_time']) {
                $this->returnCode('20140055'); //##
            }
            $period = array(strtotime($_POST['begin_time']." 00:00:00"),strtotime($_POST['end_time']." 23:59:59"));
            if($_POST['store_id']){
                $time_condition = " (l.use_time BETWEEN ".$period[0].' AND '.$period[1].")";
            }else{
                $time_condition = " (use_time BETWEEN ".$period[0].' AND '.$period[1].")";
            }
            $condition_merchant_request['_string']=$time_condition;
            $period = true;
        }

        if(isset($_POST['type'])&&!empty($_POST['type'])){
            $type=$_POST['type'];
            if($type=='activity'){
                $condition_merchant_request['type'] = 'coupon or yydb';
            }else{
                $condition_merchant_request['type'] = $type;
            }
        }else{
            $type='group';
            $condition_merchant_request['type'] = $type;
        }
        if($_POST['store_id']!=0&&$type!='wxapp'&&$type!='activity'){
            foreach($condition_merchant_request as $k=>$v){
                if($k != '_string'){
                    $condition_merchant_request['l.'.$k] = $v;
                    unset($condition_merchant_request[$k]);
                }
            }
            $condition_merchant_request['l.store_id'] = $_POST['store_id'];
        }
        $now_time = $_SERVER['REQUEST_TIME'];
        $today_zero_time = mktime(0,0,0,date('m',$now_time),date('d',$now_time), date('Y',$now_time));

        if(empty($day)){
            $day =2;
        }
        if($day < 1){
            $this->returnCode('20140056');
        }
        if($day==1&&!$period){
            if(!empty($store_id)){
                $condition_merchant_request['l.use_time'] = array(array('egt',$today_zero_time),array('elt',$now_time));
            }else{
                $condition_merchant_request['use_time'] = array(array('egt',$today_zero_time),array('elt',$now_time));
            }

            if(!empty($store_id)){
                $request_list = M('Merchant_money_list l')->field('l.order_id,l.use_time,l.money,l.type,l.income,l.mer_id,l.store_id')->join(C('DB_PREFIX').$type.'_order o ON o.order_id = l.order_id ')->where($condition_merchant_request)->select();
            }else{
                $condition_merchant_request['mer_id'] =$mer_id;
                $request_list = M('Merchant_money_list')->field(true)->where($condition_merchant_request)->select();
            }
        }else{
            if(!$period) {
                if ($day == 2) {
                    //本月
                    $today_zero_time = mktime(0, 0, 0, date('m'), 1, date('Y'));
                    if (!empty($store_id)) {
                        $condition_merchant_request['l.use_time'] = array(array('egt', $today_zero_time), array('elt', $now_time));
                    } else {
                        $condition_merchant_request['use_time'] = array(array('egt', $today_zero_time), array('elt', $now_time));

                    }
                } else {
                    if (!empty($store_id)) {
                        $condition_merchant_request['l.use_time'] = array(array('egt', $today_zero_time - (($day - 1) * 86400)), array('elt', $today_zero_time));
                    } else {
                        $condition_merchant_request['use_time'] = array(array('egt', $today_zero_time - (($day) * 86400)), array('elt', $now_time));
                    }
                }
            }
            if(!empty($store_id)){
                $request_list = M('Merchant_money_list l')->field('l.order_id,l.use_time,l.money,l.type,l.income,l.mer_id,l.store_id')->join(C('DB_PREFIX').$type.'_order o ON o.order_id = l.order_id ')->where($condition_merchant_request)->select();
            }else{
                $condition_merchant_request['mer_id'] = $mer_id;
                $request_list = M('Merchant_money_list')->field(true)->where($condition_merchant_request)->select();
            }
        }
        $tmp_array=array();
        if(($day==1&&!$period)||($period&&($_POST['end_time']==$_POST['begin_time']))){
            foreach($request_list as $value){
                $tmp_time = date('H',$value['use_time']);
                if($tmp_array[$tmp_time][$value['type']]['count']){
                    $tmp_array[$tmp_time][$value['type']]['count']=1;
                }else{
                    $tmp_array[$tmp_time][$value['type']]['count']++;
                }
                if($value['income']==1){
                    $tmp_array[$tmp_time][$value['type']]['income'] += $value['money'];
                }else{
                    $tmp_array[$tmp_time][$value['type']]['expend'] += $value['money'];
                }
            }
        }else{
            foreach($request_list as $value){
                if($day==2&&!$period){
                    $tmp_time = date('d',$value['use_time']);
                }else{
                    $tmp_time = date('ymd',$value['use_time']);
                }
                if(empty($tmp_array[$tmp_time][$value['type']]['count'])){
                    $tmp_array[$tmp_time][$value['type']]['count']=1;
                }else{
                    $tmp_array[$tmp_time][$value['type']]['count']++;
                }
                if($value['income']==1){
                    $tmp_array[$tmp_time][$value['type']]['income'] += $value['money'];
                }else{
                    $tmp_array[$tmp_time][$value['type']]['expend'] += $value['money'];
                }
            }
        }

        ksort($tmp_array);
        $alias_name = $this->get_alias_name();
        if(($day==1&&!$period)||($period&&($_POST['end_time']==$_POST['begin_time']))){
            for($i=0;$i<=date('H',$now_time);$i++){
                $pigcms_list['xAxis_arr'][]  = $i.L_('时');
                $time_arr[]=$i;
            }
        }else{
            if($day==2){
                $days = date('d',$now_time);
                for($i=1;$i<=$days;$i++){
                    $pigcms_list['xAxis_arr'][]  = $i.L_('日');
                    $time_arr[]=$i;
                }
            }else{
                for($i=$day-1;$i>=0;$i--){
                    $pigcms_list['xAxis_arr'][]  = date('y-m-d',$today_zero_time-$i*86400);
                    $time_arr[]=date('ymd',$today_zero_time-$i*86400);
                }
            }
        }
        if($period){
            unset($pigcms_list['xAxis_arr']);
            unset($time_arr);
            $start_day =strtotime($_POST['end_time']);
            $day = (strtotime($_POST['end_time'])-strtotime($_POST['begin_time']))/86400;
            if($day==0){
                for($i=0;$i<24;$i++){
                    $pigcms_list['xAxis_arr'][]  = $i.L_('时');
                    $time_arr[]=$i;
                }
            }else{
                for($i=$day;$i>=0;$i--){
                    $pigcms_list['xAxis_arr'][]  = date('y-m-d',$start_day-$i*86400);
                    $time_arr[]=date('ymd',$start_day-$i*86400);
                }
            }
        }
        $no_data_time= array();
        foreach($time_arr as $v){
            if($tmp_array[$v]){
                $pigcms_list['income'][] = round($tmp_array[$v][$type]['income'],2);
                $pigcms_list['income_all'] += round($tmp_array[$v][$type]['income'],2);
                $pigcms_list['order_count'][]   = intval($tmp_array[$v][$type]['count']);
                $pigcms_list['order_count_all'] += intval($tmp_array[$v][$type]['count']);
            }else{
                if(!in_array($v,$no_data_time)){
                    $pigcms_list['income'][] = 0;
                    $pigcms_list['order_count'][]   = 0;
                }
            }
        }
        $this->returnCode(0,$pigcms_list);
    }

    //商家收入
    public function get_income_list(){

        $_GET['page'] = $_POST['page'];
        $mer_id = intval($this->merchant['mer_id']);
        if(!empty($_POST['store_id'])){
            $condition['store_id'] = $_POST['store_id'];
        }


        if($_POST['type']!='all'){
            $condition['type'] = $_POST['type'];
        }


        if(isset($_POST['period'])&&!empty($_POST['period'])){
            $period = explode('-',$_POST['period']);
            $_POST['begin_time'] = $period[0];
            $_POST['end_time'] = $period[1];

            if ($_POST['begin_time']>$_POST['end_time']) {
                $this->returnCode('20140055');
            }
            $period = array(strtotime($_POST['begin_time'] . " 00:00:00"), strtotime($_POST['end_time'] . " 23:59:59"));
            $time_condition = " (use_time BETWEEN ".$period[0].' AND '.$period[1].")";
            $condition['_string']=$time_condition;
            // $where['_string'] = " use_time BETWEEN ".$period[0].' AND '.$period[1];

        }
        if ($_GET['page']) {
            $_SESSION['condition'] = $condition;
        }

        if($_POST['type']=='store'){
            unset(  $condition['type'] );
            $condition['_string'] = (empty($condition['_string'])?'':$condition['_string'].' AND ')."(type='store' OR type='cash') ";
        }

        $res = D('Merchant_money_list')->get_income_list($mer_id,0,$condition);

        $alias_name = $this->get_alias_c_name2();
        $income_list = array();
        foreach ( $res['income_list'] as &$inc) {
            $arr=array();
            $arr['id']=$inc['id'];
            if($inc['store_id']>0){
                $arr['store_name'] = $inc['store_name'];
            }else{
                $arr['store_name'] = '';
            }
            $arr['type'] = $inc['type'];
            $arr['type_name'] = $alias_name[$inc['type']]? $alias_name[$inc['type']]:'';
            $arr['desc'] = str_replace('</br>','',$inc['desc']);
            $arr['money'] = strval(pow(-1,($inc['income']+1))*$inc['money']);

            $income_list[]=$arr;
        }

        $arr = array('income_list'=>$income_list,'page_num'=>$res['page_num']);
        $condition['mer_id'] = $mer_id;
//        $where_total = array('mer_id'=>$mer_id,'income'=>1,'type'=>array('neq','withdraw'));
        $where_total['mer_id'] = $mer_id;
        $where_total['income'] = 1;
        $where_total['type'] = array('neq','withdraw');
        $all_money  = M('Merchant_money_list')->where($where_total)->sum('money');
        $arr['all_count'] =  intval(M('Merchant_money_list')->where($where_total)->count());
        $where_total['income'] = 2;
        $all_out_money  = M('Merchant_money_list')->where($where_total)->sum('money');
        $arr['all_money']  = empty($all_money-$all_out_money)?0:getFormatNumber($all_money-$all_out_money);
        if(empty($condition['_string'])|| strtotime($_POST['begin_time'])==strtotime(date('Y-m-d',time()))){
            $today_zero = strtotime(date('Y-m-d'));
            $condition['_string'] = 'use_time>'.$today_zero.' AND use_time <='.($today_zero+86400)." AND type <> 'withdraw'";
        }else{
            $condition['_string'] .= " AND type <> 'withdraw'";
        }
        if($income_list){
            $arr['today_money'] = $this->config['Currency_symbol'].$res['income_total'];
            $arr['today_count'] = $res['income_count'];
            // 只查询收入
            $condition['income'] = 1;
            $today_money  = M('Merchant_money_list')->where($condition)->sum('money');
            $arr['today_count'] =  intval(M('Merchant_money_list')->where($condition)->count());
            $condition['income'] = 2;
            $today_out_money  = M('Merchant_money_list')->where($condition)->sum('money');
            $arr['today_money']  = empty($today_money-$today_out_money)?$this->config['Currency_symbol'].'0':$this->config['Currency_symbol'].getFormatNumber($today_money-$today_out_money);
            $arr['fh'] = $this->config['Currency_txt'] ? $this->config['Currency_txt'] : '元';
        }else{
            $arr['today_money'] = $this->config['Currency_symbol'].'0';
            $arr['today_count'] = 0;
            $arr['fh'] = $this->config['Currency_txt'] ? $this->config['Currency_txt'] : '元';
        }

        $qianzhangtongArchive = M('Lakala_qianzhangtong_archives')->where(['item_type' => 'merchant', 'item_id' => $mer_id])->find();
        if ($qianzhangtongArchive) {
            $arr['third_pay_merchant_get'] = $this->config['Currency_symbol'].getFormatNumber($res['third_pay_merchant_get']??0);
            $thirdMoney = M('Merchant_money_list')->where(['mer_id' => $mer_id])->field('SUM(IF(income=1,merchant_get,merchant_get*-1)) AS thrid_income_money')->find();
            $arr['third_pay_merchant_get_total'] = getFormatNumber($thirdMoney['thrid_income_money'] ?? 0);
        }

        $this->returnCode(0,$arr);

    }

    //商家提现记录
    public function withdraw_list(){
//        $mer_id = intval($this->merchant['mer_id']);
//        $_GET['page'] = $_POST['page'];
//        $res = D('Merchant_money_list')->get_withdraw_list($mer_id);
//
//        $withdraw=array();
//        $pay_type = array(
//            '0' => '银行',
//            '1' => '支付宝',
//        );
//        foreach($res['withdraw_list'] as $v){
//            $arr = array();
//            $arr['id'] = $v['id'];
//            $arr['time'] = date('Y/m/d H:i:s',$v['withdraw_time']);
//            $arr['money'] = strval($v['money']/100);
//            if($v['status']==0){
//                $arr['status'] = '待审核';
//            }elseif($v['status']==1){
//                $arr['status'] = '已通过';
//            }elseif($v['status']==2){
//                $arr['status'] = '被驳回';
//            }else{
//                $arr['status'] = '待审核';
//            }
//            $arr['remark'] = $v['remark'];
//            $withdraw[]=$arr;
//        }
//        $return['withdraw']=$withdraw;
//        $return['page_num']=$res['page_num'];

        import('@.ORG.merchant_page');
        $_GET['page'] = $_POST['page'];
        $mer_id = intval($this->merchant['mer_id']);
        if ($_POST['type'] == 1 || !isset($_POST['type'])) {
            $where['status'] = array('neq',4);
            $where['mer_id'] = $mer_id;
            $withdraw_to_other = M('Withdraw_list')->where(array('pay_id'=>$mer_id))->select();
            foreach ($withdraw_to_other as $value) {
                $ids[] = $value['withdraw_id'];
            }
            if($ids){
                $where['id'] = array('not in',$ids);
            }
            $count = M('Merchant_withdraw')->where($where)->count();
            $p = new Page($count, 20);
            $withdraw_list =M('Merchant_withdraw')->where($where)->order('withdraw_time DESC')->limit($p->firstRow,$p->listRows)->select();

        } elseif ($_POST['type'] == 2) {
            $count = M('Withdraw_list')->where(array('pay_id'=>$mer_id))->count();
            $p = new Page($count, 20);
            $withdraw_list = M('Withdraw_list')->field('id,status,pay_type,remark,desc,old_money as money,add_time as withdraw_time,withdraw_id')->where(array('pay_id' => $mer_id))->order('withdraw_time DESC')->limit($p->firstRow,$p->listRows)->select();

        }elseif ($_POST['type'] == 3) {
            $where['pay_type'] = 3;
            $where['pay_id'] = $mer_id;

            $count = M('Withdraw_list')->where($where)->count();
            $p = new Page($count, 20);
            $withdraw_list = M('Withdraw_list')->field('id,status,pay_type,remark,desc,old_money as money,add_time as withdraw_time')->where($where)->order('withdraw_time DESC')->limit($p->firstRow, $p->listRows)->select();

        }elseif ($_POST['type'] == 4) {
            $where['status'] = array('neq', 4);
            $where['mer_id'] = $mer_id;
            $count = M('Merchant_withdraw_chinaums')->where($where)->count();
            $p = new Page($count, 20);
            $withdraw_list = M('Merchant_withdraw_chinaums')->where($where)->order('withdraw_time DESC')->limit($p->firstRow, $p->listRows)->select();
        }
        $where['pay_type'] = 0;
        $where['pay_id'] =$mer_id;
        //$account_list = M('Withdraw_list')->field('account,remark')->where($where)->group('account')->select();
        $this->assign('withdraw_list', $withdraw_list);
        $pay_type = array(
            '0' => L_('银行'),
            '1' => L_('支付宝'),
            '3' => L_('平台余额'),
        );

        foreach($withdraw_list as $v){
            $arr = array();
            $arr['id'] = $v['id'];
            $arr['time'] = date('Y/m/d H:i:s',$v['withdraw_time']);
            $arr['money'] = strval($v['money']/100);
            if($v['status']==0){
                $arr['status'] = L_(L_('待审核'));
            }elseif($v['status']==1){
                $arr['status'] = L_('已通过');
            }elseif($v['status']==4){
                $arr['status'] = L_(L_('被驳回'));
            }elseif($v['status']==2){
                $arr['status'] = '被驳回';
            }elseif($v['status']==3){
                $arr['status'] = '已提现';
            }elseif($v['status']==10){
                $arr['status'] = '提现中';
            }elseif($v['status']==20){
                $arr['status'] = '提现失败';
            }else{
                $arr['status'] = '待审核';
            }
            $mer_withdraw = M('Merchant_withdraw')->where(array('id'=>$v['withdraw_id']))->find();
            $arr['remark'].=$mer_withdraw['remark'];
            $arr['pay_type'] = isset($v['pay_type'])?$pay_type[$v['pay_type']]:L_('微信');
            if($v['desc']!=''){
                $arr['remark'] .= ' | '.$arr['pay_type'].' | '.$v['desc'].' | '.$v['remark'];
            }else{

                $arr['remark'] .= ' | '.$v['remark'];
            }

            $withdraw[]=$arr;
        }


        $return['withdraw']=$withdraw?$withdraw:array();
        $return['page_num']=$p->totalPage;
        $return['open_mer_monye_to_sys_money']=$this->config['open_mer_monye_to_sys_money'];
        $return['Currency_txt'] = $this->config['Currency_txt'] ? $this->config['Currency_txt'] : '元';

        //是否为银联提现商户
        $chinaumsMerNo = D("Merchant")->where(['mer_id'=>$mer_id])->getField("chinaums_merno");
        $return['chinaums_merno'] = $chinaumsMerNo?:'';
        $this->returnCode(0,$return);
    }

    //提现记录详情
    public function withdraw_info(){
        $withdraw = M('Merchant_withdraw')->where(array('id'=>$_POST['id']))->find();
        $withdraw['name'] = $this->merchant['name'];
        return $withdraw;
    }

    public function income_info(){
        $id = $_POST['id'];
        $res = M('Merchant_money_list')->where(array('id'=>$id))->find();
        if(empty($res)){
            $this->returnCode('20140064');
        }

        $type = $res['type'];
        $alias_name = $this->get_alias_c_name2();
        $mer_id = $this->merchant['mer_id'];
        $merchant = M('Merchant')->field(true)->where(array('mer_id '=> $mer_id))->find();
        $arr['percent'] =  $res['percent'];

        $order_id = $res['order_id'];
        $income[]=array('name'=>L_('订单编号'),'value'=>$res['order_id']);
        if($type!='withdraw'){
            $income[]=array('name'=>L_('平台服务费比例'),'value'=>$arr['percent'].'%');
            $income[]=array('name'=>L_('平台服务费'),'value'=>$res['system_take']);
        }
        if($res['live_money'] > 0){
            $income[]=array('name'=>L_('直播佣金'),'value'=>$res['live_money']);
        }
        $income[]=array('name'=>L_('当前商家余额'),'value'=>strval($res['now_mer_money']));
        if($type!='withdraw'){
            $income[]=array('name'=>L_('支付时间'),'value'=>date('Y/m/d H:i:s',$res['use_time']));
        }else{
            $income[]=array('name'=>L_('提现时间'),'value'=>date('Y/m/d H:i:s',$res['use_time']));
        }
        if($type=='withdraw'){
            $income[]=array('name'=>L_('结算金额'),'value'=>strval($res['money']));
        }else{
            $income[]=array('name'=>'总额','value'=>strval($res['total_money']));
        }
        $income[]=array('name'=>L_('描述'),'value'=>$res['desc']);
        $income[]=array('name'=>L_('类型'),'value'=>$alias_name[$res['type']]);

        switch($type){
            case 'group':
                $where['real_orderid']=$order_id;
                $order = M('Group_order')->where($where)->find();
                $pay_method = D('Pay')->get_pay_name($order['pay_type'],0);
                $income[]=array('name'=>L_(L_('手机号码')),'value'=>$order['phone']);
                $income[]=array('name'=>L_(L_('支付方式')),'value'=>$pay_method);
                $income[]=array('name'=>L_(L_('订单流水')),'value'=>$order['orderid']);
                break;
            case 'meal':
                $where['order_id']=$order_id;
                $order = M('Meal_order')->where($where)->find();
                $income[]=array('name'=>L_('手机号码'),'value'=>$order['phone']);
                $pay_method = D('Pay')->get_pay_name($order['pay_type'],0);
                $income[]=array('name'=>L_('支付方式'),'value'=>$pay_method);
                $income[]=array('name'=>L_('订单流水'),'value'=>$order['orderid']);
                break;
            case 'shop':
                $where['real_orderid']=$order_id;
                $order = M('Shop_order')->where($where)->find();
                $income[]=array('name'=>L_('手机号码'),'value'=>$order['userphone']);
                $pay_method = D('Pay')->get_pay_name($order['pay_type'],0);
                $income[]=array('name'=>L_('支付方式'),'value'=>$pay_method);
                $income[]=array('name'=>L_('订单流水'),'value'=>$order['orderid']);
                break;
            case 'appoint':
                $where['order_id']=$order_id;
                $order = M('Appoint_order')->where($where)->find();
                $income[]=array('name'=>L_('手机号码'),'value'=>$order['phone']);
                $pay_method = D('Pay')->get_pay_name($order['pay_type'],0);
                $income[]=array('name'=>L_('支付方式'),'value'=>$pay_method);
                $income[]=array('name'=>L_(L_('订单流水')),'value'=>$order['orderid']);
                $income[]=array('name'=>L_('预约时间'),'value'=>$order['appoint_date'].'/'.$order['appoint_time']);
                break;
            case 'store':
                $where['order_id']=$order_id;
                $order = M('Store_order s')->field(true)->join(C('DB_PREFIX').'user u ON s.uid = u.uid ')->where($where)->find();
                $pay_method = D('Pay')->get_pay_name($order['pay_type'],0);
                $income[]=array('name'=>L_(L_('手机号码')),'value'=>$order['phone']);
                $income[]=array('name'=>L_('支付方式'),'value'=>$pay_method);
                $income[]=array('name'=>L_('订单流水'),'value'=>$order['orderid']);
                break;
            case 'wxapp':
                $where['order_id']=$order_id;
                $order = M('Wxapp_order w')->field(true)->join(C('DB_PREFIX').'user u ON w.uid = u.uid ')->where($where)->find();
                $pay_method = D('Pay')->get_pay_name($order['pay_type'],0);
                $income[]=array('name'=>L_('手机号码'),'value'=>$order['phone']);
                $income[]=array('name'=>L_('支付方式'),'value'=>$pay_method);
                $income[]=array('name'=>'手机号码','value'=>$order['phone']);
                $income[]=array('name'=>L_('订单流水'),'value'=>$order['orderid']);
                break;
            case 'weidian':
                $where['order_id']=$order_id;
                $order = M('Weidian_order w')->field(true)->join(C('DB_PREFIX').'user u ON w.uid = u.uid ')->where($where)->find();
                $pay_method = D('Pay')->get_pay_name($order['pay_type'],0);
                $income[]=array('name'=>'手机号码','value'=>$order['phone']);
                $income[]=array('name'=>'支付方式','value'=>$pay_method);
                $income[]=array('name'=>'订单流水','value'=>$order['orderid']);
                break;
            case 'coupon':
                $condition_table = array(C('DB_PREFIX').'extension_activity_list'=>'eal',C('DB_PREFIX').'extension_activity_record'=>'ear',C('DB_PREFIX').'extension_coupon_record'=>'ecr',C('DB_PREFIX').'user'=>'u');
                $condition_where = "`ear`.`activity_list_id`=`eal`.`pigcms_id` AND `ecr`.`record_id`=`ear`.`pigcms_id` AND `eal`.`mer_id`='{$mer_id}' AND `ecr`.`pigcms_id`='{$order_id}' AND `ear`.`uid`=`u`.`uid`";
                $order= D('')->field('`ecr`.`pigcms_id`,`ecr`.`number`,`eal`.`title`,`ear`.`uid`,`eal`.`pigcms_id` as id,`eal`.`money`,`eal`.`name`,`u`.`nickname`,`u`.`phone`,`ecr`.`check_time`,`ecr`.`last_staff`')->where($condition_where)->table($condition_table)->find();
                $income[]=array('name'=>L_('优惠券码'),'value'=>$order['number']);
                $income[]=array('name'=>L_('消费者'),'value'=>$order['nickname']);
                $income[]=array('name'=>L_('手机号码'),'value'=>$order['phone']);
                break;
            case 'yydb':
                $where['pigcms_id'] = $order_id;
                $order = M('Extension_activity_list e')->field(true)->join(C('DB_PREFIX').'user u ON e.lottery_uid = u.uid ')->where($where)->find();
                $income[] = array('name'=>L_('项目名称'),'value'=>$order['title']);
                $income[] = array('name'=>L_(L_('提现商家')),'value'=>$this->merchant['name']);
                $income[] = array('name'=>L_(L_('夺宝用户')),'value'=>$order['nickname']);
                $income[] = array('name'=>'夺宝用户','value'=>$order['phone']);
                break;
            case 'withdraw':
                $order = M('Merchant_withdraw')->where(array('id'=>$res['order_id']))->find();
                $income[] = array('name'=>L_('提现人'),'value'=>$order['name']);
                $income[] = array('name'=>'提现商家','value'=>$this->merchant['name']);
                break;
        }

        $this->returnCode(0,array('income_info'=>$income));

    }
    //提现

    public function withdraw_method(){
        $arr['withdraw_type']=array(
            '0'=>L_('银行卡').($this->config['open_real_time_withdrawal'] ? '（'.L_('1-3日到账，最快次日').'）' : ''),
            '1'=>L_('支付宝').($this->config['open_real_time_withdrawal'] ? '（'.L_('实时到账').'）' : ''),
            '2'=>L_('微信').($this->config['open_real_time_withdrawal'] ? '（'.L_('实时到账').'）' : '')
        );
        $user = D('User')->get_user($this->merchant['uid']);
        if ($this->config['open_mer_monye_to_sys_money']==1&&$user) {
            $arr['withdraw_type'][3] = '平台余额';
        }

        if($this->config['city_ownpay'] && $this->merchant['mer_id']  ){

            $can_agree = false;
            if( D('City_ownpay')->field('city_id',true)->where(array('city_id'=>$this->merchant['mer_id']))->find() ){
                unset($arr['withdraw_type'][2]);
            }


        }
        if($this->config['company_bank_pay']==0){
            unset($arr['withdraw_type'][0]);
        }
        if($this->config['company_alipay_pay']==0){
            unset($arr['withdraw_type'][1]);
        }
        if($this->config['company_weixin_pay']==0){
            unset($arr['withdraw_type'][2]);
        }
        $now_merchant = D('Merchant')->get_info($this->merchant['mer_id']);
        $arr['now_money'] = $now_merchant['money'];
        $arr['merchant_withdraw_fee_type'] = $this->config['merchant_withdraw_fee_type'];
        $arr['company_pay_mer_money'] = $this->config['company_pay_mer_money'];
        $arr['company_pay_mer_percent'] = $this->config['company_pay_mer_percent'];
        $arr['company_least_money'] =   $this->config['min_withdraw_money']?$this->config['min_withdraw_money']:$this->config['company_least_money'];

        $this->returnCode(0,$arr);
    }

    public function check_withdraw(){
        if($this->config['company_pay_open']=='0') {
            $this->returnCode('20140059');
        }else{
            $this->returnCode(0);
        }

    }
    public function withdraw(){
        if($this->config['company_pay_open']=='0') {
            $this->returnCode('20140059');
        }
        $mer_id = intval($this->merchant['mer_id']);
        $now_merchant = M('Merchant')->where(array('mer_id'=>$mer_id))->find();

        //提现到微信要先绑定微信
        if($_POST['withdraw_type'] == 2){
            if(!$now_merchant['uid']){
                $qrcode_id = $this->merchant['mer_id'] + 2550000000;
                $qrcode_return = D('Recognition')->get_tmp_qrcode($qrcode_id);
                if($qrcode_return['error_code']){
                    $msg = $qrcode_return['msg'];
                    $this->returnCode('1','',$msg);

                }else{
                    $returnArr  = array('status'=>10,'data'=>$qrcode_return);
                }
                $this->returnCode(0,$returnArr);
            }

        }

        if(M('Merchant_withdraw')->where(array('mer_id'=>$mer_id,'status'=>array('in','0,4')))->find()){
            $this->returnCode('20140060');
        }
        $user = D('User')->get_user($this->merchant['uid']);
        if($_POST['money']>0){
            if($_POST['money']>$now_merchant['money']){
                $this->returnCode('20140061');
            }
            $money = floatval(($_POST['money']))*100;
            if($_POST['money']<$this->config['min_withdraw_money']){
                $this->returnCode('20140062','',L_('不能低于最低提款额 '). C('config.Currency_symbol') . $this->config['min_withdraw_money'].'!');
            }
            if ($_POST['money'] < $this->config['company_least_money']) {
                $this->returnCode('20140062','','不能低于最低提款额 ' . C('config.Currency_symbol') . $this->config['company_least_money'].'!');
            }

            if(isset($_POST['withdraw_type']) && ($_POST['withdraw_type']==0 || $_POST['withdraw_type']==1)){
                $money = $_POST['money'];
                if(empty($_POST['name'])){
                    $this->returnCode('20046031');
                }
                if($money<$this->config['company_least_money']){
                    $this->returnCode('20140062','','不能低于最低提款额 '.C('config.Currency_symbol') . $this->config['company_least_money'].'!');
                }

                if(!is_numeric($_POST['withdraw_type'])){
                    $this->returnCode('20140066');
                }
                $data_companypay['type'] = 'mer';
                $data_companypay['pay_type'] = $_POST['withdraw_type'];//0 银行卡，1 支付宝 2微信
                $data_companypay['pay_id'] = $now_merchant['mer_id'];
                $remark = '';
                if($_POST['withdraw_type']==0){
                    $data_companypay['account'] = $_POST['card_number'];
                    if(empty($_POST['card_number']) || empty($_POST['card_username']) ||empty($_POST['bank']) ){
                        $this->returnCode('20140067');
                    }
                    $remark = L_('开户名：X1，开户行：X2',array('X1'=>$_POST['card_username'], 'X2'=>$_POST['bank']));
                    if($_POST['bank_city']){
                        $remark.= '，' . L_('开户省市：') . $_POST['bank_city'];
                        $remark.= '，' . L_('开户网点：') . $_POST['bank_branch'];
                    }
                    $date_mer['withdraw_type'] = 'bank_card';
                }else if ($_POST['withdraw_type']==1){
                    if(empty($_POST['alipay_account'])  ){
                        $this->returnCode('20140068');
                    }
                    $data_companypay['account'] = $_POST['alipay_account'];
                    $date_mer['withdraw_type'] = 'alipay';
                }

                $data_companypay['truename'] = $_POST['name'];
                $data_companypay['name'] = $now_merchant['name'];
                $data_companypay['remark'] = $remark ;
                $data_companypay['phone'] = $now_merchant['phone'];
                if($this->config['merchant_withdraw_fee_type']==0){
                    $data_companypay['money']     = bcmul($money * ((100 - $this->config['company_pay_mer_percent']) / 100), 100);
                }else if($this->config['merchant_withdraw_fee_type']==1){
                    $data_companypay['money']     = ($money-$this->config['company_pay_mer_money'])*100;
                }
                if($data_companypay['money']<=0){
                    $this->returnCode('20140064','',L_('您的提现金额不足以抵扣提现手续费，不能提现'));
                }
                $data_companypay['old_money'] = $money*100;
                $data_companypay['desc'] = "商户提现对账订单 | 商户ID ".$now_merchant['mer_id']." | 转账 " . C('config.Currency_symbol') . $money;

                if ($this->config['company_pay_mer_percent'] > 0 && $this->config['merchant_withdraw_fee_type']==0) {
                    $system_take = ($data_companypay['old_money'] -  $data_companypay['money'])/100;
                    $data_companypay['desc'] .= ' | 手续费 ' .(($data_companypay['old_money'] -  $data_companypay['money'])/100) . ' 比例 ' . $this->config['company_pay_mer_percent'] . '%';
                }else if($this->config['merchant_withdraw_fee_type']==1&&$this->config['company_pay_mer_money']>0){
                    $data_companypay['desc'] .= ' | 手续费 '.C('config.Currency_symbol') . $this->config['company_pay_mer_money'];
                    $system_take = $this->config['company_pay_mer_money'];
                }

                $data_companypay['status'] = 0;
                $data_companypay['add_time'] = time();


                $date_mer['mer_id']=$mer_id;
                $date_mer['name']=$_POST['name'];
                $date_mer['money']=   $data_companypay['money'] ;
                $date_mer['old_money']=    $data_companypay['old_money'] ;
                $date_mer['remark']=  $_POST['remark'];
                $date_mer['withdraw_time'] = time();
                $date_mer['status'] = 4;
                $date_mer['from_type'] = 1;
                
				// 开启事务
				$database = D('');
				$database->startTrans();

                $res =M('Merchant_withdraw')->add($date_mer);
                if(!$res){
					$database->rollback();// 回滚事务
                    $this->returnCode('20140063');die;
                }
                $data_companypay['withdraw_id'] = $res;
                $result = D('Merchant_money_list')->use_money($mer_id,$money,'withdraw',  $data_companypay['desc'] ,$data_companypay['withdraw_id'],$this->config['company_pay_mer_percent'],$system_take);
                if($result['error_code']){
					$database->rollback();// 回滚事务
                    $this->returnCode('1','','申请失败');die;
                }

                if(C('config.open_real_time_withdrawal')){// 开启实时提现立马到账不需审核
                    $date_mer = [];
					//请求第三方接口
					$param['money'] = $data_companypay['money'];
					if($_POST['withdraw_type']==0){// 银行卡
						$param['money'] = $data_companypay['money'];
						$param['account'] = $data_companypay['account'];
						$param['account_name'] = $_POST['card_username'];
						$param['bank_name'] = $_POST['bank'];
							
						import('@.ORG.withdraw.Weixin');
						$weixinObj = new Weixin();
						$param['partner_trade_no'] = date('YmdHis').$this->merchant['mer_id'];
						$result = $weixinObj->withdrawToBank($param);

                        $date_mer['third_id'] = $result['result']['payment_no'];//第三放订单id
                        $date_mer['cmms_amt'] = $result['result']['cmms_amt'];//第三放手续费
					}else if ($_POST['withdraw_type']==1){//支付宝 TODO
						$param['money'] = round($data_companypay['money']/100,2);
						$param['account'] = $data_companypay['account'];
						$param['truename'] = $data_companypay['truename'];
							
						import('@.ORG.withdraw.Alipay');
						$alipayObj = new Alipay();
						$param['partner_trade_no'] = date('YmdHis').$this->merchant['mer_id'];
						$result = $alipayObj->withdraw($param);
                        $date_mer['third_id'] = $result['pay_fund_order_id'];//第三放订单id
					
					}
					if($result['error']){
						$database->rollback();// 回滚事务
                        $this->returnCode('1','',$result['msg']);die;
					}

					// 修改状态
                    $data_companypay['status'] = 1;
					$date_mer['status'] = 1;
					
                    M('Merchant_withdraw')->where(['id'=>$data_companypay['withdraw_id']])->save($date_mer);
					$msg = L_('提现成功！');
				}

                $database->commit();// 完成事务
                $withdraw_id = M('Withdraw_list')->add($data_companypay);
                D('Merchant_money_list')->withdraw_notice($res);

                $this->returnCode(0,array('status'=>1,'msg'=>L_('提现成功')));die;
            }elseif($_POST['withdraw_type']==2) {
                // 开启事务
                $database = D('');
                $database->startTrans();

                $res = D('Merchant_money_list')->withdraw($this->merchant['mer_id'], $_POST['name'], $money, $_POST['remark']);
                if ($res['error_code']) {
                    $database->rollback();// 回滚事务
                    $this->returnCode('20140063');
                } else {
                    if(C('config.open_real_time_withdrawal')){// 开启实时提现立马到账不需审核

                        // 用户信息
                        if (empty($user)) {
                            $database->rollback();// 回滚事务
                            $this->returnCode('1','',L_('商家绑定微信用户不存在！'));
                        }

                        //请求第三方接口
                        import('@.ORG.withdraw.Weixin');
                        $weixinObj = new Weixin();
                        $data_companypay = $res['data'];
                        $param['money'] = $data_companypay['money'];
                        $param['openid'] = $user['openid'];
                        $param['name'] = $_POST['name'];
                        $param['partner_trade_no'] = date('YmdHis').$this->merchant['mer_id'];
                        $result = $weixinObj->withdraw($param);
                        if($result['error']){
                            $database->rollback();// 回滚事务
                            $this->returnCode('1','',$result['msg']);
                        }

                        // 修改状态
                        $date_mer = [];
                        $date_mer['status'] = 1;
                        M('Merchant_withdraw')->where(['id'=>$data_companypay['id']])->save($date_mer);
                        $msg = L_('提现成功！');
                    }

                    $database->commit();// 完成事务
                    $this->returnCode(0,array('status'=>1,'msg'=>$msg));
                }
            }elseif($_POST['withdraw_type']==3) {
                $money = floatval($_POST['money']);
                $res = D('Merchant_money_list')->withdraw_system_money($mer_id,$_POST['name'],$money,$_POST['remark'],$user);
                if ($res['error_code']) {
                    $this->returnCode('20140063');
                } else {
                    $this->returnCode(0,array('status'=>1,'msg'=>L_('提现成功')));
                }
            }
        }else{
            $this->returnCode('20045014');
        }
    }

    public  function  get_bank_name(){
        $card_number = $_POST['card_number'];
        require_once APP_PATH . 'Lib/ORG/BankList.class.php';
        if($res = $this->bankInfo($card_number,$bankList)){
            $this->returnCode(0,array('bank_name'=>$res));
        }else{
            $this->returnCode('20140065');
        }
    }

    public  function  get_withdraw_fee(){
        $money = $_POST['money']?$_POST['money']:0;
        $this->returnCode(0,array('withdraw_fee'=>$money*($this->config['company_pay_mer_percent'])/100));
    }


    function bankInfo($card,$bankList)
    {
        $card_8 = substr($card, 0, 8);
        if (isset($bankList[$card_8])) {
            return $bankList[$card_8];
        }
        $card_6 = substr($card, 0, 6);
        if (isset($bankList[$card_6])) {
            return $bankList[$card_6];

        }
        $card_5 = substr($card, 0, 5);
        if (isset($bankList[$card_5])) {
            return $bankList[$card_5];

        }
        $card_4 = substr($card, 0, 4);
        if (isset($bankList[$card_4])) {
            return $bankList[$card_4];

        }
        return null;
    }

    //会员卡

    public function card_num_date(){

        //所有
        $where['mer_id'] = $this->merchant['mer_id'];
        $where['uid'] = array('gt',0);
        $arr['all_count'] = M('Card_userlist')->where($where)->count();
        //今日
        $begin=mktime(0,0,0,date('m'),date('d'),date('Y'));
        $end=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
        $where['_string'] = 'add_time >'.$begin.' AND add_time <'.$end;
        $arr['today_count'] = M('Card_userlist')->where($where)->count();
        //本周
        $begin=mktime(0, 0 , 0,date("m"),date("d")-date("w")+1,date("Y"));
        $end=mktime(23,59,59,date("m"),date("d")-date("w")+7,date("Y"));
        $where['_string'] = 'add_time >'.$begin.' AND add_time <'.$end;
        $arr['this_week_count'] = M('Card_userlist')->where($where)->count();
        //本月
        $begin=mktime(0, 0 , 0,date("m"),1,date("Y"));
        $end=mktime(23,59,59,date("m"),date("t"),date("Y"));
        $where['_string'] = 'add_time >'.$begin.' AND add_time <'.$end;
        $arr['this_month_count'] = M('Card_userlist')->where($where)->count();
        //本季度
        $season = ceil((date('n'))/3);
        $begin= mktime(0, 0, 0,$season*3-3+1,1,date('Y'));
        $end=mktime(23,59,59,$season*3,date('t',mktime(0, 0 , 0,$season*3,1,date("Y"))),date('Y'));
        $where['_string'] = 'add_time >'.$begin.' AND add_time <'.$end;
        $arr['this_season_count'] = M('Card_userlist')->where($where)->count();
        //本年
        $t = time();
        $begin = mktime(0,0,0,1,1,date("Y",$t));
        $end=mktime(23,59,59,12,31,date("Y",$t));
        $where['_string'] = 'add_time >'.$begin.' AND add_time <'.$end;
        $arr['this_year_count'] = M('Card_userlist')->where($where)->count();

        //上周
        $begin=mktime(0, 0 , 0,date("m"),date("d")-date("w")+1-7,date("Y"));
        $end=mktime(23,59,59,date("m"),date("d")-date("w")+7-7,date("Y"));
        $where['_string'] = 'add_time >'.$begin.' AND add_time <'.$end;
        $arr['last_week_count'] = M('Card_userlist')->where($where)->count();
        //上月
        $begin=mktime(0, 0 , 0,date("m")-1,1,date("Y"));
        $end=mktime(23,59,59,date("m") ,0,date("Y"));
        $where['_string'] = 'add_time >'.$begin.' AND add_time <'.$end;
        $arr['last_month_count'] = M('Card_userlist')->where($where)->count();
        //上季度
        $season = ceil((date('n'))/3)-1;
        $begin=mktime(0, 0, 0,$season*3-3+1,1,date('Y'));
        $end=mktime(23,59,59,$season*3,date('t',mktime(0, 0 , 0,$season*3,1,date("Y"))),date('Y'));

        $where['_string'] = 'add_time >'.$begin.' AND add_time <'.$end;
        $arr['last_seasion_count'] = M('Card_userlist')->where($where)->count();
        //上年
        $begin=strtotime(date('Y-01-01',strtotime('-1 year')));
        $end=strtotime(date('Y-12-31',strtotime('-1 year')));
        $where['_string'] = 'add_time >'.$begin.' AND add_time <'.$end;
        $arr['last_year_count'] = M('Card_userlist')->where($where)->count();

        $this->returnCode(0,$arr);
    }
    public function card_new_list(){
        if (!empty($_POST['keyword'])) {
            if ($_POST['searchtype'] == 'phone') {
                $condition_user['u.phone'] = array('like', '%' . $_POST['keyword'] . '%');
            }
            if ($_POST['searchtype'] == 'card_id') {
                $condition_['c.id'] = array('like', '%' . $_POST['keyword'] . '%');
                $condition_['c.wx_card_code'] = array('like', '%' . $_POST['keyword'] . '%');
                $condition_['_logic'] = 'or';
                $condition_user['_complex'] = $condition_;
            }
            if ($_POST['searchtype'] == 'physical_id') {
                $condition_user['c.physical_id'] = array('like', '%' . $_POST['keyword'] . '%');
            }
            if ($_POST['searchtype'] == 'nickname') {
                $condition_user['u.nickname'] = array('like', '%' . $_POST['keyword'] . '%');
            }
        }
        $condition_user['c.mer_id'] = $this->merchant['mer_id'];
        $card_count = M('Card_userlist')->join('as c left join '.C('DB_PREFIX').'user as u ON u.uid = c.uid')->where($condition_user)->count();
        $page	=	I('pindex',1);
        $card_user_list = M('Card_userlist')->field('c.id,c.card_id,c.mer_id,c.uid,c.card_money,c.card_money_give,c.card_score,c.qrcode_id,c.physical_id,c.add_time,c.status,c.ticket,c.gid,c.wx_card_code,c.cancel_wx,u.nickname,u.phone,c.add_time as card_add_time')->join('as c left join '.C('DB_PREFIX').'user as u ON u.uid = c.uid')->where($condition_user)->order('c.id DESC')->page($page,10)->select();
        foreach ($card_user_list as &$v) {
            if($v['add_time']>0){

                $v['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
            }else{
                $v['add_time']='';
            }
            $v['card_money']= $v['card_money']+$v['card_money_give'];
            !$v['nickname'] && $v['nickname']='';
            !$v['phone'] && $v['phone']='';
        }

        $arr['data']	=	isset($card_user_list)?$card_user_list:array();
        $arr['all']		=	$card_count;
        $arr['page'] 	=	ceil($arr['all']/10);
        $this->returnCode(0,$arr);
    }

    public function  card_new_status(){
        $where['id']	=	I('card_id');
        $data['status']	=	I('status',1);
        //$data['status']	= $data['status'] == 1 ? 1 : 0;
        if($where['id']){
            $save	=	M('Card_userlist')->where($where)->data($data)->save();
        }else{
            $this->returnCode('20140074');
        }
        if($save){
            $this->returnCode(0);
        }else{
            $this->returnCode('20140028');
        }
    }

    //修改密码
    public function changePwd(){
        $type = I('type',2);
        if($type != 4 && empty($this->mer_id)){
            $this->returnCode('20140071');
        }
        if($type == 4){
            if(empty($_POST['phone'])){
                $this->returnCode('20140072');
            }
            $now_merchant = M('Merchant')->where(array('phone'=>$_POST['phone']))->find();
            if(empty($now_merchant)){
                $this->returnCode('20140073');
            }
            $this->mer_id = $now_merchant['mer_id'];
        }
        $where = array();
        $where['mer_id'] = $this->mer_id;
        $where['extra'] = $_POST['smsCode'];
        $where['type']   = $type;
        $where['status'] = 0;
        $where['expire_time'] = array('gt', time());
        $sms_result = M("Merchant_sms_record")->where($where)->find();
        if(!$sms_result){
            $this->returnCode('20140070');
        }

        if(md5($_POST['newPwd']) == $this->merchant['pwd']){
            $this->returnCode('10045010');
        }

        $condition_merchant['mer_id'] = $this->mer_id;
        //校验密码
        $check_password = verifyPassword($_POST['newPwd']);
        if($check_password['code']!=0){
            $this->returnCode(1001,array(),L_($check_password['msg']));
        }
        $data_merchant['pwd'] = md5($_POST['newPwd']);
        if(M('Merchant')->where($condition_merchant)->data($data_merchant)->save()){
            M("Merchant_sms_record")->where(array('pigcms_id'=>$sms_result['pigcms_id']))->data(array('status'=>'1'))->save();
            $this->returnCode(0);
        }else{
            $this->returnCode('20140069');
        }
    }

    public function changePhone(){

        if(empty($this->mer_id)){
            $this->returnCode('20140071');
        }

        if(empty($_POST['new_phone'])){
            $this->returnCode('20140072');
        }
        $now_merchant = M('Merchant')->where(array('phone'=>$_POST['new_phone']))->find();
        if($now_merchant){
            $this->returnCode('20140084');
        }

        if($this->config['open_merchant_reg_sms']) {
            $where = array();
            $where['mer_id'] = $this->mer_id;
            $where['extra'] = $_POST['smsCode'];
            $where['status'] = 0;
            $where['expire_time'] = array('gt', time());
            $sms_result = M("Merchant_sms_record")->where($where)->find();

            if (!$sms_result) {
                $this->returnCode('20140070');
            }

            $where = array();
            $where['mer_id'] = $this->mer_id;
            $where['extra'] = $_POST['smsCode2'];
            $where['status'] = 0;
            $where['expire_time'] = array('gt', time());
            $sms_result = M("Merchant_sms_record")->where($where)->find();

            if (!$sms_result) {
                $this->returnCode('20140070');
            }
        }
        $condition_merchant['mer_id'] = $this->mer_id;
        $data_merchant['phone'] = $_POST['new_phone'];
        $_POST['phone_country_type'] && $data_merchant['phone_country_type'] = $_POST['phone_country_type'];

        $data_merchant['phone'] = phone_format($data_merchant['phone_country_type'], $data_merchant['phone']);

        if(M('Merchant')->where($condition_merchant)->data($data_merchant)->save()){
            M("Merchant_sms_record")->where(array('pigcms_id'=>$sms_result['pigcms_id']))->data(array('status'=>'1'))->save();
            $this->returnCode(0);
        }else{
            $this->returnCode('20140083');
        }
    }

    public function card_edit()
    {
        $mer_id = $this->merchant['mer_id'];
        if (empty($_POST['support_score_select'])) {
            $_POST['support_score']=0;
        } elseif (!is_numeric($_POST['support_score']) || empty($_POST['support_score'])) {
            $this->returnCode('20170101','',L_('消费一元获得积分数必须是大于1的数字'));
        }

        if(!is_numeric($_POST['discount'])){
            $this->returnCode('20170102','','请填写0到10的数字,0相当于不打折,比如95折 填写9.5即可');
        }


        if ($_POST['recharge_back_rule'] == 1 && !$_POST['support_score_select']) {
            $this->returnCode('20170103','',L_('积分支持已关闭，充值返现规则不能再选积分'));
        }
        foreach ($_POST['recharge_count'] as $key=>$v) {
            $tmp['count'] = $v;
            $tmp['back_money'] = $_POST['recharge_back_money'][$key];
            $tmp['back_score'] = $_POST['recharge_back_score'][$key];

            if (!is_numeric($tmp['back_money'])&&!is_numeric($tmp['back_score'])) {
                $this->returnCode('20170104','',L_('请填写正确的数据'));
            }
            $_POST['recharge_rule'][] = $tmp;
        }
        $_POST['recharge_rule'] = serialize( $_POST['recharge_rule']);
        unset($_POST['recharge_count'],$_POST['recharge_back_money'],$_POST['recharge_back_score']);
//            if (!is_numeric($_POST['recharge_back_money'])&&!is_numeric($_POST['recharge_back_score'])) {
//                $this->returnCode('20170104','','请填写正确的数据');
//            }
        if (!is_numeric($_POST['begin_money']) && $this->config['merchant_card_recharge_offline'] || $_POST['begin_money'] < 0) {
            $this->returnCode('20170105','',L_('开卡初始金额设置错误'));
        }

        if($_POST['discount']<0||$_POST['discount']>10){
            $this->returnCode('20170106','','会员卡折扣设置错误,不给折扣请填10');
        }
        //逗号替换
        $_POST['recharge_suggest'] = preg_replace("/(，)/",',',$_POST['recharge_suggest']);
        $_POST['mer_id'] = $mer_id;
        $_POST['last_time'] = $_SERVER['REQUEST_TIME'];
        $tmp=array();
        foreach($_POST as $key=>$v){
            if(!(strpos($key,'wx')===false)){
                $tmp[str_replace('wx_','',$key)] =is_array($v)?$v:htmlspecialchars_decode($v);
            }
        }

        $_POST['wx_param']  = serialize($tmp);

        if($card = M(_view('Card_new'))->where(array('mer_id'=>$_POST['mer_id']))->find() ){
            M('Card_new')->where(array('mer_id'=>$mer_id))->data($_POST)->save();
            if($_POST['sysc_weixin'] && $this->config['coupon_wx_sync']){
                if(empty($_POST['wx_prerogative'])&& $card['card_id']!=''){
                    $this->returnCode('20170107','',L_('特权说明不能为空！'));
                }

                $_POST['sysc_weixin'] && $res = $this->sysc_wxcard();
            }
            $this->returnCode(0,array('msg'=>L_('保存成功').$res['msg']));
        }else{
            $_POST['add_time'] = $_SERVER['REQUEST_TIME'];
            M('Card_new')->add($_POST);
            if($this->config['coupon_wx_sync']){
                $_POST['sysc_weixin'] && $res = $this->sysc_wxcard();
            }
            $this->returnCode(0,array('msg'=>L_('添加会员卡成功').$res['msg']));
        }
    }

    //增加会员
    public function card_new_add_user(){
        //卡号规则
        $_POST['mer_id'] = $this->merchant['mer_id'];
        $_POST['add_time']  = time();
        $_POST['card_money_give'] = $_POST['card_money'];
        $_POST['phone'] = trim($_POST['phone']);
        if($_POST['phone']){
            if(!preg_match("/^1\d{10}$/", $_POST['phone'])){
                $this->returnCode('20170113','',L_('手机号码格式不正确！'));
            }
            $now_user = D('User')->get_user($_POST['phone'], 'phone');
            if($now_user){
                $_POST['uid'] = $now_user['uid'];
            }else{
                $data_user['phone'] = $_POST['phone'];
                $data_user['source'] = 'merchant_card_auto';
                $reg_result = D('User')->autoreg($data_user);
                if($reg_result['uid']){
                    $_POST['uid'] = $reg_result['uid'];
                }
            }
        }
        if($_POST['uid'] && M('Card_userlist')->where(array('mer_id' => $_POST['mer_id'], 'uid' => $_POST['uid']))->find()){
            $this->error(L_('添加失败，该手机号已绑定该商家会员，不能再绑定'));
        }
        unset($_POST['phone']);
        unset($_POST['card_money']);
        if( $card_id = M('Card_userlist')->add($_POST)){
            if (!empty($_POST['card_money_give'])) {
                $data_money['card_id'] = $card_id ;
                $data_money['type'] = 1;
                $data_money['money_add'] = $_POST['card_money_give'];
                $data_money['desc'] = L_('会员卡创建初始金额');
                D('Card_new')->add_row($data_money);
            }
            if (!empty($_POST['card_score'])) {
                $data_score['card_id'] = $card_id ;
                $data_score['type'] = 1;
                $data_score['score_add'] = $_POST['card_score'];
                $data_score['desc'] = L_('会员卡创建初始积分');
                D('Card_new')->add_row($data_score);
            }
            $this->returnCode(0,array('msg'=>L_('添加成功'),'id'=>$card_id));
        }else{
            $this->returnCode('20170113','',L_('添加失败！'));
        }
    }
    //会员详情
    public function card_new_user_detail(){
        $id = $_POST['id'];
        $card_info = D('Card_new')->get_cardinfo_by_id($id);
        $card_info['card_group']  = M(_view('Card_group'))->where(array('mer_id'=>$this->merchant['mer_id']))->select();
        if($card_info['add_time']>0){
            $card_info['add_time'] = date('Y-m-d H:i:s',$card_info['add_time']);
        }else{
            $card_info['add_time'] = '';
        }
        !$card_info['nickname'] && $card_info['nickname']='';
        !$card_info['phone'] && $card_info['phone']='';
        if (empty($card_info)) {
            $this->returnCode('20170114','',L_('会员卡不存在！'));
        } else {
            $this->returnCode(0,array('card'=>$card_info));
        }
    }
    //会员编辑
    public function card_new_user_edit(){
        $card_info = D('Card_new')->get_cardinfo_by_id($_POST['id']);
        if (empty($card_info)) {
            $this->returnCode('20170114','',L_('会员卡不存在！'));
        } else {
            $data['card_money_give'] = pow(-1, ($_POST['set_money_type'] + 1)) * $_POST['set_money'] + $card_info['card_money_give'];
            if ($data['card_money_give'] < 0) {
                $this->returnCodeError(L_('管理员只能减少会员卡的赠送余额，减少的金额已超出赠送余额，您最多可减少X1',$card_info['card_money_give'] . C('config.Currency_txt')));
            }
            $data['card_score'] = pow(-1, ($_POST['set_score_type'] + 1)) * $_POST['set_score'] + $card_info['card_score'];
            if ($data['card_money_give'] < 0) {
                $data['card_money_give'] = 0;
            }

            if ($data['card_score'] < 0) {
                $data['card_score'] = 0;
            }

            if ($_POST['physical_id']) {
                $isTure=preg_match('/^[0-9a-zA-Z]+$/',$_POST['physical_id']);
                if(!$isTure){
                    $this->returnCode('20170195','',L_('实体卡号只能包含字母、数字'));
                }

                $where_py['physical_id'] = $_POST['physical_id'];
                $where_py['id'] = array('neq',$_POST['id']);
                if(M('Card_userlist')->where($where_py)->find()){
                    $this->returnCode('20170194','',L_('该实体卡已绑定其他会员，不能在绑定！'));
                }
            }

            $_POST['physical_id'] && $data['physical_id'] = $_POST['physical_id'];
            $data['status'] = $_POST['status'];
            $_POST['gid'] && $data['gid'] = $_POST['gid'];
            $data['remark'] = $_POST['remark'];

            if (M('Card_userlist')->where(array('id' => $_POST['id']))->data($data)->save()) {
                if (!empty($_POST['set_money'])) {
                    $des = $_POST['set_money_type'] == 1 ? L_('增加') : L_('减少');
                    $add_use = $_POST['set_money_type'] == 1 ? 'add' : 'use';
                    $data_money['card_id'] = $card_info['id'];
                    $data_money['type'] = $_POST['set_money_type'];
                    $data_money['money_' . $add_use] = $_POST['set_money'];
                    $data_money['desc'] = L_('商家后台操作X1赠送余额',$des);

                    D('Card_new')->add_row($data_money);
                    if ($card_info['wx_card_code'] != '') {
                        D('Card_new')->update_wx_card($card_info['wx_card_code'], $data['card_money_give'] - $card_info['card_money_give'], 0, $data_money['desc']);
                    }
                }

                if (!empty($_POST['set_score'])) {
                    $des = $_POST['set_score_type'] == 1 ? L_('增加') : L_('减少');
                    $add_use = $_POST['set_score_type'] == 1 ? 'add' : 'use';
                    $data_score['card_id'] = $card_info['id'];
                    $data_score['type'] = $_POST['set_score_type'];
                    $data_score['score_' . $add_use] = $_POST['set_score'];
                    $data_score['desc'] = L_('商家后台操作X1积分',$des);
                    D('Card_new')->add_row($data_score);

                    if ($card_info['wx_card_code'] != '') {
                        D('Card_new')->update_wx_card($card_info['wx_card_code'], 0, $data['card_score'] - $card_info['card_score'], '', $data_score['desc']);
                    }
                }
                $this->returnCode(0,array('msg'=>L_('修改成功')));
            } else {
                $this->returnCode('20170115', '', L_('修改失败！请重试。'));

            }
        }
    }

    #微信购买派发设置
    public function card_new_weixin_send_save(){
        $mer_id  =$this->merchant['mer_id'];
        if(!is_numeric($_POST['money'])||$_POST['money']<0){
            $this->returnCode('20170122', '', L_('金额设置错误'));
        }
        $_POST['coupon_id'] = implode(',',$_POST['coupon_id']);
        $data['weixin_send_money'] = $_POST['money'];
        $data['weixin_send_couponlist'] = $_POST['coupon_id'];

        if(M('Card_new')->where(array('mer_id'=>$mer_id))->save($data)){
            $this->returnCode(0,array('msg'=>L_('保存成功')));
        }else{
            $this->returnCode('20170122', '', L_('保存失败'));
        }
    }

    //用户扫描二维码绑定会员卡
    public function see_qrcode(){

        $where['id']  = $_POST['id'];
        $qrcode_id =800000000+$_POST['id'];
        $res = D('Recognition')->get_tmp_qrcode($qrcode_id);
        $date['qrcode_id']=$qrcode_id;
        $date['ticket']=$res['ticket'];
        $where['id']=$_POST['id'];
        M('Card_userlist')->where($where)->save($date);
        $this->returnCode(0,array('qrcode'=>$res['ticket']));
    }


    //获取背景 颜色等属性
    public function get_card_new_config(){
        for ($i = 4; $i <= 20; $i++) {
            $i = $i < 10 ? '0' . $i : $i;
            $str = $this->config['site_url'].'/static/images/card/card_bg' . $i . '.png';
            $arr['bg_list'][] = $str;
        }
        $arr['color_list'] =  D('System_coupon')->color_list();
        $arr['coupon_wx_sync'] =  $this->config['coupon_wx_sync'];

        $arr['wx_server']=array(
            'BIZ_SERVICE_DELIVER'=>L_('外卖服务'),
            'BIZ_SERVICE_FREE_PARK'=>L_('停车位'),
            'BIZ_SERVICE_WITH_PET'=>L_('可带宠物'),
            'BIZ_SERVICE_FREE_WIFI'=>L_('免费wifi'),
        );

        $this->returnCode(0,$arr);
    }

    public function ajax_ordertype_cateid()
    {
        if ($_POST['order_type'] == 'meal') {
            $cate_id = D(ucfirst($_POST['order_type']) . '_store_category')->field('cat_id,cat_name')->where(array('cat_status' => 1, 'cat_fid' => 0))->select();
        } else {
            $cate_id = D(ucfirst($_POST['order_type']) . '_category')->field('cat_id,cat_name')->where(array('cat_status' => 1, 'cat_fid' => 0))->select();
        }
        $this->returnCode(0,$cate_id);
    }

    public function card_detail(){
        $mer_id = $this->merchant['mer_id'];
        $data = M(_view('Card_new'))->where(array('mer_id'=>$mer_id))->find();
        preg_match('/\d+/',$data['bg'],$bg);
        $data['bg_num']=intval($bg[0]);
        $data['wx_param'] = unserialize($data['wx_param']);
        $data['recharge_rule'] = unserialize($data['recharge_rule']);
        /*if(strpos($data['bg'],'http')!==false){
        }else{
            $data['bg'] = $this->config['site_url'].str_replace('./','/',$data['bg']);
        }*/
        $data['bg'] =  $data['bg']?replace_file_domain( $data['bg']):'';
        foreach ($data['wx_param']['text_image_list'] as $k => $v) {
            if(empty($v)){
                continue;
            }
            $text_image_list[] =$v;
        }
        $color_list =  D('System_coupon')->color_list();

        $data['wx_param']['text_image_list'] = $text_image_list;
        $data['wx_param']['color_val'] = $color_list[$data['wx_param']['color']];

        $data['wx_param']['center_url'] = htmlspecialchars_decode($data['wx_param']['center_url']);
        $data['wx_param']['custom_url'] = htmlspecialchars_decode($data['wx_param']['custom_url']);
        $data['wx_param']['promotion_url'] = htmlspecialchars_decode($data['wx_param']['promotion_url']);
        $data['wx_param']['custom_cell1_url'] = htmlspecialchars_decode($data['wx_param']['custom_cell1_url']);
        $data['wx_param']['custom_cell2_url'] = htmlspecialchars_decode($data['wx_param']['custom_cell2_url']);
        $data['wx_param']['custom_cell3_url'] = htmlspecialchars_decode($data['wx_param']['custom_cell3_url']);

        $arr['data'] = $data;
        $this->returnCode(0,$arr);
    }

    //消费记录
    public function card_new_consume_record()
    {
        $where['card_id']=$_POST['id'];
        $count = M(_view('Card_new_record'))->where($where)->count();
        $page = I('pindex',1);
        $where['c.card_id']= $where['card_id'];
        unset( $where['card_id']);
        $result =  M(_view('Card_new_record'))->field('c.* ,uu.nickname,uu.phone')->join('as c left join '.C('DB_PREFIX').'card_userlist as u ON c.card_id = u.id left join '.C('DB_PREFIX').'user as uu on uu.uid=u.uid')->where($where)->order('c.time DESC')->page($page,10)->select();

        foreach ($result as &$v) {
            $v['time'] = date('Y-m-d H:i:s',$v['time']);
            foreach ($v as $key=>$vv) {
                if(!$v[$key]&&$v[$key]!=0){
                    $v[$key] = '';
                }
            }
        }
        $arr['data']	=	isset($result)?$result:array();
        $arr['all']		=	$count;
        $arr['page'] 	=	ceil($arr['all']/10);
        $this->returnCode(0,$arr);
    }

    //会员卡总体消费记录
    public function recharge_list(){
        $mer_id  =$this->merchant['mer_id'];
        $where['u.mer_id'] = $mer_id;
        $count = M(_view('Card_new_record'))->join('as c left join '.C('DB_PREFIX').'card_userlist as u ON c.card_id = u.id')->where($where)->count();
        $page = I('pindex',1);
        $result =  M(_view('Card_new_record'))->field('c.*,uu.phone,uu.nickname')->join('as c left join '.C('DB_PREFIX').'card_userlist as u ON c.card_id = u.id left join '.C('DB_PREFIX').'user as uu on uu.uid=u.uid')->where($where)->order('c.time DESC')->page($page,10)->select();
        foreach ($result as &$v) {
            $v['time'] = date('Y-m-d H:i:s',$v['time']);
        }
        $arr['data']	=	isset($result)?$result:array();
        $arr['all']		=	$count;
        $arr['page'] 	=	ceil($arr['all']/10);
        $this->returnCode(0,$arr);
    }


    /*
     * 会员卡分组
     * */

    //会员卡分组列表
    public function card_new_group(){

        $card_group_list = M(_view('Card_group'))->where(array('mer_id'=>$this->merchant['mer_id']))->select();
        foreach ($card_group_list as &$v) {
            $v['user_count'] = M('Card_userlist')->where(array('mer_id'=>$this->merchant['mer_id'],'gid'=>$v['id']))->count();
        }

        $arr['group_list']		=	$card_group_list?$card_group_list:array();

        $this->returnCode(0,$arr);
    }

    //添加分组
    public function add_card_group(){

        $_POST['mer_id'] = $this->merchant['mer_id'];
        if(empty($_POST['name'])){
            $this->returnCode('20170122','',array('msg'=>L_('名字不能空')));
        }
        if(empty($_POST['gid'])){
            $res = M('Card_group')->add($_POST);
        }else{
            $date['mer_id'] = $_POST['mer_id'];
            $date['name'] = $_POST['name'];
            $date['des'] = $_POST['des'];
            $res = M('Card_group')->where(array('id'=>$_POST['gid']))->save($date);
        }
        if($res){
            $this->returnCode(0,array('msg'=>L_('操作成功')));
        }else {
            $this->returnCode('20170116','',L_('操作失败'));
        }

    }

    public  function card_group_detail(){
        $gid = $_POST['gid'];
        $res = M(_view('Card_group'))->where(array('id'=>$_POST['gid']))->find();
        $this->returnCode(0,array('group'=>$res));
    }

    //分组用户列表
    public function card_group_user_list(){
        $gid = $_POST['gid'];
        $mer_id  = $this->merchant['mer_id'];
        $where['c.mer_id']=$mer_id;
        $where['c.gid']=$gid;
        $page = I('pindex',1);
        $count = M('Card_userlist')->join('as c left join '.C('DB_PREFIX').'user as u ON c.uid=u.uid')->where($where)->count();
        $result = M('Card_userlist')->field('c.id,c.uid,c.card_money,c.card_money_give,c.card_score,c.physical_id,c.add_time,c.status,c.gid,u.nickname,u.phone')->join('as c left join '.C('DB_PREFIX').'user as u ON c.uid=u.uid')->where($where)->page($page,10)->select();
        foreach ($result as &$v) {
            if($v['add_time']>0){
                $v['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
            }else{
                $v['add_time']='';
            }
            !$v['nickname'] && $v['nickname']='';
            !$v['phone'] && $v['phone']='';
        }
        $arr['data']	=	isset($result)?$result:array();
        $arr['all']		=	$count;
        $arr['page'] 	=	ceil($arr['all']/10);
        $this->returnCode(0,$arr);
    }


    //优惠券派发

    //派发记录
    public function send_history(){
        $mer_id  =$this->merchant['mer_id'];
        $page = I('pindex',1);
        $count = M('Card_coupon_send_history')->where(array('mer_id'=>$mer_id))->count();

        $res = M('Card_coupon_send_history')->join('as h left join '.C('DB_PREFIX').'user u ON h.uid = u.uid')
            ->join(C('DB_PREFIX').'card_new_coupon c ON h.coupon_id = c.coupon_id')->field('h.*,u.nickname,c.name as coupon_name')->where(array('h.mer_id'=>$mer_id))->order('add_time DESC')->page($page,10)->select();
        foreach ($res as &$re) {
            $re['add_time'] = date('Y-m-d H:i:s',$re['add_time']);
        }
        $arr['data']	=	isset($res)?$res:array();
        $arr['all']		=	$count;
        $arr['page'] 	=	ceil($arr['all']/10);
        $this->returnCode(0,$arr);
    }

    //派发优惠券
    public function send_coupon(){
        $mer_id  =$this->merchant['mer_id'];

        $coupon_list = D('Card_new_coupon')->get_coupon_list_by_merid($mer_id,1, true);

        foreach ($coupon_list as $v) {
            $v['img'] = replace_file_domain($v['img']);
            $tmp[] = $v;
        }
        $arr['coupon_list']=$tmp?$tmp:array();
        $card_group_list = M(_view('Card_group'))->where(array('mer_id'=>$this->merchant['mer_id']))->select();
        foreach ($card_group_list as &$v) {
            $v['user_count'] = M('Card_userlist')->where(array('mer_id'=>$this->merchant['mer_id'],'gid'=>$v['id']))->count();
        }
        $arr['card_group']=$card_group_list?$card_group_list:array();

        $this->returnCode(0,$arr);
    }

    //根据分组id 获取派送的优惠券列表
    public function ajax_get_send_coupon(){
        if(!empty($_POST['card_group_id'])){
            $count = M('Card_userlist')->where(array('gid'=>array('in',$_POST['card_group_id'])))->count();
            $coupon_list  = D('Card_new_coupon')->get_coupon_list_by_merid($this->merchant['mer_id'],1);
            foreach ($coupon_list as &$v) {

                if($count>$v['num']-$v['had_pull']){
                    $v['disable'] = true;
                }else{
                    $v['disable'] = false;
                }

                $v['img'] = replace_file_domain($v['img']);
            }
        }else{
            $coupon_list  = D('Card_new_coupon')->get_coupon_list_by_merid($this->merchant['mer_id'],1);

        }
        $arr['coupon_list']=$coupon_list;
        $this->returnCode(0,$arr);
    }

    //派发优惠券
    public function card_new_send(){
        $_POST['coupon_id']  =implode(',',$_POST['coupon_id']);
        if($_POST['card_group_id']){
            $_POST['card_group_id']  =implode(',',$_POST['card_group_id']);
            //$res = D('Card_new')->get_userlist_by_car_group($_GET['card_group_id']);
            $res =D('Card_new')->add_send_log($this->merchant['mer_id'],$_POST['card_group_id'],$_POST['coupon_id']);
            // $this->assign('user_list',$res);
            if($res['error_code']){
                $this->error($res['msg']);
                $this->returnCode('20170118','',array('msg'=>$res['msg'],'status'=>0));
            }else{
                $this->template_plan_msg($res['id']);
                $this->returnCode(0,array('msg'=>$res['msg'],'status'=>1));
            }
            die;
        }else if($_POST['all']){
            //$res = D('Card_new')->get_card_user_list_by_mer_id($this->merchant['mer_id']);
            $res =D('Card_new')->add_send_log($this->merchant['mer_id'],'',$_POST['coupon_id']);
            //$this->assign('user_list',$res);
            if($res['error_code']){
                $this->returnCode('20170118','',array('msg'=>$res['msg'],'status'=>0));

            }else{
                $this->template_plan_msg($res['id']);
                $this->returnCode(0,array('msg'=>$res['msg'],'status'=>1));
            }
            die;
        }else if($_POST['uid']){
            $res =D('Card_new')->add_send_log($this->merchant['mer_id'],'',$_POST['coupon_id'],$_POST['uid']);
            if($res['error_code']){
                $this->returnCode('20170118','',array('msg'=>$res['msg'],'status'=>0));

            }else{
                $this->template_plan_msg($res['id']);
                $this->returnCode(0,array('msg'=>$res['msg'],'status'=>1));
            }
        }

    }

    // 添加临时计划任务
    public function template_plan_msg($send_id){
        import('@.ORG.plan');
        $plan_class = new plan();
        $param = array(
            'file'=>'send_coupon',
            'plan_time'=>time(),
            'param'=>array(
                'id'=>$send_id,
            ),
        );
        $plan_class->addTask($param);
    }

    public function send_all(){
        $mer_id  =$this->merchant['mer_id'];
        $where['status'] = 1;
        $where['mer_id'] = $mer_id;
        $user_count = M('Card_userlist')->where($where)->count();
        $coupon_list = D('Card_new_coupon')->get_coupon_list_by_merid($mer_id);
        foreach ($coupon_list as $key=>$vv) {
            if($vv['num']<$user_count){
                unset($coupon_list[$key]);
            }
        }
        if(empty($coupon_list)){
            $this->returnCode('20170119','',L_('没有可分配的优惠券'));
        }
        $arr['coupon_list']=$coupon_list;
        $this->returnCode(0,$arr);
    }

    public function get_user(){
        $where['c.mer_id'] = $this->merchant['mer_id'];
        $where['u.'.$_POST['keyword']] = array('like',"%".trim($_POST['search_val'])."%");
        $res = M('Card_userlist')->join('as c left join '.C('DB_PREFIX').'user u ON c.uid = u.uid')->field('c.*,u.nickname,u.phone')->where($where)->limit(50)->select();

        //查询粉丝
        $fans_lists = M('Merchant_user_relation')->join('as c left join '.C('DB_PREFIX').'user u ON c.openid = u.openid')->field('c.*,u.nickname,u.phone,u.uid')->where($where)->limit(50)->select();
        if ($res) {
            foreach ($res as &$card_user) {
                $card_user['is_card'] = 1;
                foreach ($fans_lists as $key => $fans) {
                    $fans_lists[$key]['is_card'] = 0;
                    if ($fans['uid'] == $card_user['uid']) {
                        unset($fans_lists[$key]);
                    }
                }
            }
            $res = $fans_lists ? array_merge($res,$fans_lists) : $res;
        }else{
            $res = $fans_lists;
        }

        $arr['user_list']=$res;
        $this->returnCode(0,$arr);
    }



    public function uploadimg($url){
        $mode = D('Access_token_expires');
        $res = $mode->get_access_token();
        import('ORG.Net.Http');
        $http = new Http();
        $file  =str_replace('https','http',$url);

        $return = $http->curlUploadFile('https://api.weixin.qq.com/cgi-bin/media/uploadimg?access_token='.$res['access_token'],$file,1);
        return json_decode($return,true);
    }

    public function card_new_add_coupon()
    {
        $now_card = M(_view('Card_new'))->where(array('mer_id'=>$this->merchant['mer_id']))->find();
        if(empty($now_card)){
            $this->returnCode(1,'',L_('您还没有开启会员卡，请编辑会员卡信息后再添加优惠券'));
        }

        if ($_POST['limit'] > $_POST['num']) {
            $this->returnCode('20170109','','请完善基本信息-领取限制不能大于数量!');
        }
        if ($_POST['use_limit'] > $_POST['limit'] || $_POST['use_limit'] > $_POST['num']) {
            $this->returnCode('20170110','','请完善基本信息-使用限制设置错误，不能大于领取限制和数量!');

        }
        if ($_POST['cate_name'] != 'all') {
            if ($_POST['cate_id'] != 0) {
                if ($_POST['cate_name'] == 'meal') {
                    $cate_id = D(ucfirst($_POST['cate_name']) . '_store_category')->field('cat_id,cat_name')->where(array('cat_id' => $_POST['cate_id']))->find();
                } else {
                    $cate_id = D(ucfirst($_POST['cate_name']) . '_category')->field('cat_id,cat_name')->where(array('cat_id' => $_POST['cate_id']))->find();
                }
                $_POST['cate_id'] = serialize($cate_id);
            }
        } else {
            $_POST['cate_id'] = 0;
        }
        $data['platform'] = serialize($_POST['platform']);
        unset($_POST['dosubmit']);
        unset($_POST['platform']);
        $image = D('Image')->handle($this->merchant['mer_id'], 'card_new_coupon', 1);
        if (!$image['error']) {
            $data = array_merge($data, $image['url']);
        }
        $data = array_merge($data, $_POST);
        $data['start_time'] = strtotime($data['start_time']);
        $data['end_time'] = strtotime($data['end_time']) + 86399;//到 23:59:59
        $data['add_time'] = $data['last_time'] = time();
        $data['mer_id'] = $this->merchant['mer_id'];
        $data['card_id'] = $now_card['card_id'];
        $data['notice'] =$_POST['notice'];
        $data['auto_get'] =$_POST['auto_get'];
        if(empty($_POST['store_id']) || count($_POST['store_id']) == 0 || (count($_POST['store_id']) == 1 && $_POST['store_id'][0] == '')){
            $this->returnCode('20170111','','请完善基本信息-店铺不能为空!');
        }
        $data['store_id'] = implode(',',$_POST['store_id']);
        if($_POST['sync_wx']) {
            import('@.ORG.weixincard');
            import('ORG.Net.Http');
            $http	=	new Http();
            $mode = D('Access_token_expires');
            $res = $mode->get_access_token();

            if($this->merchant['logo']){
                $logo_url = $this->uploadimg($_SERVER['DOCUMENT_ROOT'].str_replace($this->config['site_url'],'',$this->merchant['logo']));
            }else{
                $logo_url = $this->uploadimg($_SERVER['DOCUMENT_ROOT'].str_replace($this->config['site_url'],'',$this->config['wechat_share_img']));
            }
            $param['logo_url'] = $logo_url['url'];
            $param['brand_name'] = mb_substr($_POST['brand_name'],0,12,'utf-8');
            $param['title'] =  mb_substr($_POST['name'],0,9,'utf-8');
            $param['color'] = $_POST['color'];
            $param['notice'] = mb_substr($_POST['notice'],0,16,'utf-8');
            $param['phone'] = $this->config['site_phone'];
            $param['description'] = $_POST['des'];
            $param['begin_time'] = $data['start_time'];
            $param['end_time'] = $data['end_time'];
            $param['num'] = $_POST['num'];
            $param['limit'] = $_POST['limit'];
            $param['center_title'] = L_('立即使用');
            $param['center_sub_title'] = mb_substr($_POST['center_sub_title'],0,6,'utf-8');
            $param['center_url'] = html_entity_decode($_POST['center_url']);
            $param['custom_url_name'] = mb_substr($_POST['custom_url_name'],0,5,'utf-8');
            $param['custom_url'] = html_entity_decode($_POST['custom_url']);
            $param['custom_url_sub_title'] = mb_substr($_POST['custom_url_sub_title'],0,6,'utf-8');
            $param['promotion_url'] = html_entity_decode($_POST['promotion_url']);
            $param['promotion_url_name'] = L_('更多优惠');
            $param['icon_url_list'] = $_POST['icon_url_list']; //封面图片
            $param['abstract'] = $_POST['abstract']; //封面图片
            $param['share_friends'] = $_POST['share_friends'];

            foreach ($_POST['image_url'] as $k => $v) {
                $text_image_list[] = array(
                    'image_url' => $v,
                    'text' => $_POST['textall'][$k],
                );
            }

            $param['text_image_list'] = $text_image_list;
            $param['business_service'] = $_POST['business_service'];
            $param['least_cost'] = $_POST['order_money']*100;
            $param['reduce_cost'] = $_POST['discount']*100;
            $param['res'] = $res;

            $card = new Create_card($param);
            $cardinfo = $card->create();
            $ticket = $cardinfo['ticket'];
            $qrcode_url = $cardinfo['qrcode_url'];
            $return = $cardinfo['return'];
            if($return['errcode']){
                $this->returnCode('20170112','',L_('同步微信卡券出错，请检查您配置的数据是否正确，微信返回信息：').$return['errmsg']);
            }
        }
        if ($id = M('Card_new_coupon')->add($data)) {
            $errorms= '';
            if($this->config['coupon_wx_sync'] && $_POST['sync_wx']) {
                $wx_data['sync_wx'] = $_POST['sync_wx'];
                unset($param['res']);

                $wx_data['wx_param'] = serialize($param);
                $errormsg = '';
                if ($return['errcode'] == 0) {
                    $wx_data['wx_cardid'] = $return['card_id'];
                    $wx_data['jsapi_ticket'] = $ticket['ticket'];
                    $wx_data['expires_in'] = $ticket['expires_in'];
                    $wx_data['wx_qrcode'] = $qrcode_url['show_qrcode_url'];
                    $wx_data['wx_ticket_addtime'] = $_SERVER['REQUEST_TIME'];
                    $wx_data['is_wx_card'] = 1;
//                        unset($param['res']);
//                        $wx_data['wx_param'] = serialize($param);
                    $wx_data['cardsign'] = sha1($wx_data['wx_ticket_addtime'] . $ticket['ticket'] . $return['card_id']);
                    M('Card_new_coupon')->where(array('coupon_id' => $id))->save($wx_data);
                    $errormsg = $return['errmsg'];

                } else {
                    $wx_data['is_wx_card'] = 0;
                    $errormsg = $return['errmsg'];
                    $wx_data['weixin_err'] = serialize($errormsg);
                    M('Card_new_coupon')->where(array('coupon_id' => $id))->save($wx_data);

                }
            }

            $this->returnCode(0,array('msg'=>L_('添加优惠券成功！').$errormsg));
        } else {

            $this->returnCode('20170113','',L_('添加失败！'));

        }

    }

    public function card_new_edit_coupon()
    {
        $now_coupont = M('Card_new_coupon')->where(array('coupon_id' => $_POST['coupon_id']))->find();
        $_POST['num']  =$now_coupont['num'];
        $add = pow(-1, (int)$_POST['add']);
        $_POST['num'] += $add * (int)$_POST['num_add'];//数量增减
        if ((int)$_POST['num'] < (int)$_POST['had_pull']) {
            $this->returnCode('20170114','','请完善基本信息-更新优惠券数量有误，不能小于已领取的数量！');
        } else if (strtotime($_POST['end_time']) < strtotime($_POST['start_time'])) {
            $this->returnCode('20170108','','请完善基本信息-起始时间设置有误!');
        }

        if ($_POST['num'] > $_POST['had_pull'] && $_POST['status'] == 3) {
            if ($_POST['end_time'] > time()) {
                $_POST['status'] = 1;
            }
        }
        if ($_POST['num'] <= $_POST['had_pull']) {
            $_POST['status'] = 3;
        }

        unset($_POST['dosubmit']);
        $data = $_POST;
        $data['start_time'] = strtotime($data['start_time']);
        $data['end_time'] = strtotime($data['end_time']) + 86399;//到 23:59:59
        $image = D('Image')->handle($this->merchant['mer_id'], 'card_new_coupon', 1);
        if (!$image['error']) {
            $data = array_merge($data, $image['url']);
        }
        $data['last_time'] = time();
        $data['mer_id'] = $this->merchant['mer_id'];
        if(empty($_POST['store_id'])){
            $this->returnCode('20170111','',L_('店铺不能为空!'));
        }
        $data['store_id'] = implode(',',$_POST['store_id']);

        if (M('Card_new_coupon')->where(array('coupon_id' => $_POST['coupon_id']))->save($data)) {

            $errorms= '';
            if($this->config['coupon_wx_sync']) {
                if ($_POST['is_wx_card']) {
                    import('ORG.Net.Http');
                    $res = D('Access_token_expires')->get_access_token();
                    //修改描述
                    $update_info['card_id'] = $_POST['wx_cardid'];
                    if ($image['url']['img']) {
                        $update_info['general_coupon']['base_info']['logo_url'] = $image['url']['img'];
                    }
                    $update_info['general_coupon']['base_info']['description'] = $_POST['des_detial'];
                    $update_wx_card = httpRequest('https://api.weixin.qq.com/card/update?access_token=' . $res['access_token'], 'post', json_encode($update_info, JSON_UNESCAPED_UNICODE));
                    $update_wx_card = json_decode($update_wx_card[1], true);

                    //修改库存
                    $wx_data['card_id'] = $_POST['wx_cardid'];
                    // $wx_data['color'] = $_POST['color'];
                    $wx_data['increase_stock_value'] = $add > 0 ? $_POST['num_add'] : 0;
                    $wx_data['reduce_stock_value'] = $add < 0 ? $_POST['num_add'] : 0;
                    $wx_data['begin_timestamp'] = $data['start_time'] ;
                    $wx_data['end_timestamp'] = $data['end_time'] ;
                    $update_wx_card = httpRequest('https://api.weixin.qq.com/card/modifystock?access_token=' . $res['access_token'], 'post', json_encode($wx_data, JSON_UNESCAPED_UNICODE));
                    $update_wx_card = json_decode($update_wx_card[1], true);
                    $errorms = $update_wx_card['errmsg'];
                }
            }


            $this->returnCode(0,array('msg'=>L_('保存成功！').$errorms));
        } else {

            $this->returnCode('20170115','',L_('保存失败！'));
        }

    }

    public function card_new_coupon_config(){
        $return = D('Card_new_coupon')->cate_platform();
        $return['coupon_wx_sync'] = $this->config['coupon_wx_sync'];
        $this->returnCodeOk($return);
    }

    public function card_new_coupon_detail(){
        $return = D('Card_new_coupon')->cate_platform(); //模板中定义相关中文名字
        $coupon = D('Card_new_coupon')->where(array('coupon_id' => $_POST['coupon_id']))->find();
        $coupon['platform'] = unserialize($coupon['platform']);
        $coupon['wx_param'] = unserialize($coupon['wx_param']);
        $coupon['store_id'] = explode(',',$coupon['store_id']);
        $coupon['now_num'] = $coupon['now_num'];
        foreach ($coupon['platform'] as &$vv) {
            $vv = $return['platform'][$vv];
        }
        $coupon['platform'] = implode(',', $coupon['platform']);
        $coupon['cate_name'] = $coupon['cate_name'] == 'all' ? L_('全品类通用') : $return['category'][$coupon['cate_name']];
        if (empty($coupon['cate_id'])) {
            $coupon['cate_id'] = L_('全部分类');
        } else {
            $coupon['cate_id'] = unserialize($coupon['cate_id']);
            $coupon['cate_id'] = $coupon['cate_id']['cat_name'];
        }
//        $store_list =  D('Merchant_store')->get_store_list_by_merId($this->merchant['mer_id']);
//        $color_list =  D('System_coupon')->color_list();

        // $this->assign("store_list",$store_list);
        $coupon['category']= $return['category'];

        $coupon['start_time'] = date('Y-m-d',$coupon['start_time']);
        $coupon['end_time'] = date('Y-m-d',$coupon['end_time']);
        $coupon['img'] = replace_file_domain($coupon['img']);
        $arr['coupon']= $coupon;
        $this->returnCode(0,$arr);
    }

    public function card_new_coupon_list(){
        if (!empty($_POST['keyword'])) {
            if ($_POST['searchtype'] == 'id') {
                $condition_coupon['id'] = $_POST['keyword'];
            } else if ($_POST['searchtype'] == 'name') {
                $condition_coupon['name'] = array('like', '%' . $_POST['keyword'] . '%');
            }
        }
        $_POST['status']>0 && $condition_coupon['status'] = $_POST['status'];
        if($_POST['status']!=1 && $_POST['status']>-1){
            $condition_coupon['status'] =array('neq',1);
        }elseif($_POST['status'] == 1){
            $condition_coupon['status'] = array('neq',4);
            $condition_coupon['end_time'] = array('gt',time());
        }else{
            $condition_coupon['status'] = array('neq',4);
        }
        //排序 /*/
        $order_string = '`coupon_id` DESC';
        if ($_POST['sort']) {
            switch ($_POST['sort']) {
                case 'uid':
                    $order_string = '`uid` DESC';
                    break;
                case 'lastTime':
                    $order_string = '`last_time` DESC';
                    break;
                case 'money':
                    $order_string = '`now_money` DESC';
                    break;
                case 'score':
                    $order_string = '`score_count` DESC';
                    break;
            }
        }
        $page	=	I('pindex',1);
        $condition_coupon['mer_id'] = $this->merchant['mer_id'];
        $coupon = M('Card_new_coupon');
        $coupon_count = $coupon->where($condition_coupon)->count();

        $coupon_list = $coupon->field(true)->where($condition_coupon)->order($order_string)->page($page,10)->select();
        $return = D('Card_new_coupon')->cate_platform();
        foreach ($coupon_list as $key => &$v) {
            $ctmp['platform'] = unserialize($v['platform']);
            if ($v['cate_name'] != 'all' && !empty($v['cate_id'])) {
                $tmp = unserialize($v['cate_id']);
                $c_tmp['cate_id'] = $tmp['cat_name'];
            }
            if ($v['end_time'] < time()) {
                D('Card_new_coupon')->where(array('coupon_id' => $v['coupon_id']))->setField('status', 2);
                $c_tmp['status'] = 2;
            }

            $c_tmp['start_time'] = date('Y-m-d',$v['start_time']);
            $c_tmp['end_time'] = date('Y-m-d',$v['end_time']);
            $c_tmp['coupon_id'] =$v['coupon_id'];
            $c_tmp['mer_id'] =$v['mer_id'];
            $c_tmp['card_id'] =$v['card_id'];
            //  $c_tmp['img'] =$v['img'];
            $c_tmp['order_money'] =$v['order_money'];
            $c_tmp['discount'] =$v['discount'];
            $c_tmp['wx_qrcode'] =$v['wx_qrcode'];
            $c_tmp['status'] = $v['status'];
            $c_tmp['name'] = $v['name'];
            switch($c_tmp['status']){
                case 0:
                    $v['status_txt'] =L_('禁止');
                    break;
                case 1:
                    $v['status_txt'] =L_('正常');
                    break;
                case 2:
                    $v['status_txt'] =L_('过期');
                    break;
                case 3:
                    $v['status_txt'] =L_('领完了');
                    break;
            }
            $c_tmp['category'] = $return['category'][$v['cate_name']];
            $c_tmp['platform']='';
            foreach ($ctmp['platform'] as $vv) {
                $c_tmp['platform'].=$return['platform'][$vv].' ';
            }
            //$v['platform'] = $return['platform']['cate_name'];
            $coupon_list_[] = $c_tmp;
        }

//        $this->assign("category", $return['category']);
//        $this->assign("platform", $return['platform']);
//        $this->assign('coupon_list', $coupon_list);


        $arr['data']	=	isset($coupon_list_)?$coupon_list_:array();
        $arr['all']		=	$coupon_count;
        $arr['page'] 	=	ceil($arr['all']/10);
        $where['mer_id'] = $this->merchant['mer_id'];
        $arr['all_count'] 	=	$coupon->where($where)->count();
        $where['status'] =1;
        $arr['normal_cont'] 	=		$coupon->where($where)->count();
        $where['status'] =array('neq',1);
        $arr['ban_count'] 	=		$coupon->where($where)->count();
        $this->returnCode(0,$arr);
    }



    //同步微信卡包
    public function sysc_wxcard(){
        $mer_id = $this->merchant['mer_id'];
        $card_info = M(_view('Card_new'))->where(array('mer_id'=>$mer_id))->find();
        $card_info['wx_param'] = unserialize($card_info['wx_param']);
        $_POST = $card_info['wx_param'];
        import('@.ORG.weixincard');
        $mode = D('Access_token_expires');
        $token = $mode->get_access_token();

        if($this->merchant['logo']){
            $logo_url = $this->uploadimg($_SERVER['DOCUMENT_ROOT'].str_replace($this->config['site_url'],'',$this->merchant['logo']));
        }else{
            $logo_url = $this->uploadimg($_SERVER['DOCUMENT_ROOT'].str_replace($this->config['site_url'],'',$this->config['wechat_share_img']));
        }
        $param['logo_url'] = $logo_url['url'];
        if($card_info['bg']!=''&& strpos( $card_info['wx_param']['background_pic_url'],'mmbiz')==false ){
            $bgArr = parse_url($card_info['bg']);
            $background_url =$this->uploadimg($_SERVER['DOCUMENT_ROOT'].$bgArr['path']);
        }else if($card_info['diybg']!='' && strpos( $card_info['wx_param']['background_pic_url'],'mmbiz')==true){
            $background_url =  $card_info['wx_param']['background_pic_url'] ?array('url'=>$card_info['wx_param']['background_pic_url'] ):$this->uploadimg($_SERVER['DOCUMENT_ROOT'].str_replace($this->config['site_url'],'',$card_info['diybg']));
        }else{
            $this->returnCode('20170116','',L_('没有设置背景！'));
        }
        $param['background_pic_url'] =$background_url['url'];
        $param['card_id'] =$card_info['wx_cardid'];
        $param['brand_name'] = mb_substr($this->merchant['name'],0,12,'utf-8');
        $param['title'] =  mb_substr($_POST['title'],0,9,'utf-8');
        $param['notice'] = mb_substr($_POST['notice'],0,16,'utf-8');
        $param['phone'] = substr($this->merchant['phone'],0,11);
        $param['description'] = $card_info['info'];
        $param['prerogative'] = $_POST['prerogative'];
        $param['color'] = $_POST['color'];
        if($card_info['discount']==0){
            $card_info['discount'] = 10;
        }
        $param['discount'] = (100-$card_info['discount']*10)/10;
        $param['coupon_url'] = $this->config['site_url'].'/wap.php?c=My_card&a=merchant_coupon&mer_id='.$mer_id;
        $param['balance_url'] = $this->config['site_url'].'/wap.php?c=My_card&a=merchant_prepay&mer_id='.$mer_id;
        $param['bonus_url'] = $this->config['site_url'].'/wap.php?c=My_card&a=merchant_point&mer_id='.$mer_id;
        $param['card_url'] = $this->config['site_url'].'/wap.php?c=My_card&a=merchant_point&mer_id='.$mer_id;

        $param['center_title'] = mb_substr($_POST['center_title'],0,6,'utf-8');;
        $param['center_sub_title'] = mb_substr($_POST['center_sub_title'],0,8,'utf-8');
        $param['center_url'] = html_entity_decode($_POST['center_url']);
        $param['custom_url_name'] = mb_substr($_POST['custom_url_name'],0,5,'utf-8');
        $param['custom_url'] = html_entity_decode($_POST['custom_url']);
        $param['custom_url_sub_title'] = mb_substr($_POST['custom_url_sub_title'],0,6,'utf-8');
        $param['promotion_url'] = html_entity_decode($_POST['promotion_url']);
        $param['promotion_url_name'] = mb_substr($_POST['promotion_url_name'],0,6,'utf-8');
        $param['promotion_url_sub_title'] = mb_substr($_POST['promotion_url_sub_title'],0,6,'utf-8');

        $param['custom_cell1_name'] = mb_substr($_POST['custom_cell1_name'],0,5,'utf-8');
        $param['custom_cell2_name'] = mb_substr($_POST['custom_cell2_name'],0,5,'utf-8');
        $param['custom_cell3_name'] = mb_substr($_POST['custom_cell3_name'],0,5,'utf-8');

        $param['custom_cell1_tips'] = mb_substr($_POST['custom_cell1_tips'],0,6,'utf-8');
        $param['custom_cell2_tips'] = mb_substr($_POST['custom_cell2_tips'],0,6,'utf-8');
        $param['custom_cell3_tips'] = mb_substr($_POST['custom_cell3_tips'],0,6,'utf-8');

        $param['custom_cell1_url'] = html_entity_decode($_POST['custom_cell1_url']);
        $param['custom_cell2_url'] = html_entity_decode($_POST['custom_cell2_url']);
        $param['custom_cell3_url'] = html_entity_decode($_POST['custom_cell3_url']);
        foreach ( $card_info['wx_param']['text_image_list'] as $k => $v) {
            if(empty($v)){
                continue;
            }
            $text_image_list[] = array(
                'image_url' => $v['image_url'],
                'text' => $v['text'],
            );
        }


        $param['text_image_list'] = $text_image_list;
        $param['business_service'] = $_POST['business_service'];

        $param['token'] = $token;

        $card = new Create_wxcard($param);
        if($param['card_id']){
            $cardinfo = $card->update();
        }else{
            $cardinfo = $card->create();
        }
        $ticket = $cardinfo['ticket'];
        $qrcode_url = $cardinfo['qrcode_url'];
        $return = $cardinfo['return'];

        if ($return['errcode'] == 0) {
            if($param['card_id']=='') {
                $wx_data['wx_cardid']         = $return['card_id'];
                $wx_data['jsapi_ticket']      = $ticket['ticket'];
                $wx_data['expires_in']        = $ticket['expires_in'];
                $wx_data['wx_qrcode']         = $qrcode_url['show_qrcode_url'];
                $wx_data['wx_ticket_addtime'] = $_SERVER['REQUEST_TIME'];
                $wx_data['is_wx_card']        = 1;
//                        unset($param['res']);
//                        $wx_data['wx_param'] = serialize($param);
                $wx_data['cardsign'] = sha1($wx_data['wx_ticket_addtime'] . $ticket['ticket'] . $return['card_id']);
                M('Card_new')->where(array('mer_id' => $mer_id))->save($wx_data);
                $errormsg = $return['errmsg'];
            }
            if(IS_AJAX){

                $this->returnCode(0,array('msg'=>L_('微信会员卡同步成功')));
            }else{
                return array('error_code'=>0,'msg'=>L_('，微信会员卡同步成功'));
            }
        } else {
            $wx_data['is_wx_card'] = 0;
            $errormsg = $return['errmsg'];
            $wx_data['weixin_err'] = serialize($errormsg);
            M('Card_new')->where(array('mer_id' => $mer_id))->save($wx_data);

            if(IS_AJAX){
                $this->returnCode('20170108','',L_('同步微信会员卡出错，请检查您配置的数据是否正确，微信返回信息：').$return['errmsg']);
            }else{
                return array('error_code'=>1,'msg'=>L_('，同步微信会员卡出错，请检查您配置的数据是否正确，微信返回信息：').$return['errmsg']);
            }
        }
    }

    //修改密码验证码
    public function sendCode(){
        $type = I('type',2);

        if($type!=1) {
            if ($type != 4 && empty($this->mer_id)) {
                $this->returnCode('20140071');
            }

            if ($type == 4) {
                if (empty($_POST['phone'])) {
                    $this->returnCode('20140072');
                }
                $now_merchant = M('Merchant')->where(array('phone' => $_POST['phone']))->find();
                if (empty($now_merchant)) {
                    $this->returnCode('20140073');
                }
                $this->merchant = $now_merchant;
                $this->mer_id = $now_merchant['mer_id'];
            }
            //防止1分钟内多次发送短信
//            $laste_sms = M('Merchant_sms_record')->where(array('mer_id' => $this->mer_id))->order('pigcms_id DESC')->find();
//            if (time() - $laste_sms['send_time'] < 60) {
//                $this->returnCode('20046036');
//            }
            $phone = $this->merchant['phone'];
            $_POST['phone_country_type'] = $this->merchant['phone_country_type'];
        }else{
            if(empty($_POST['phone'])){
                $this->returnCode('20140072');
            }
            $phone = $_POST['phone'];
            $this->mer_id = 0;


            if($this->config['dingxiang_captcha_appid']){
                if(!$_POST['CaptchaToken']){
                    $this->returnCodeError('图形校验码未监控通过，请刷新页面后再试。');
                }
                include('./cms/Lib/ORG/dingxianginc/CaptchaClient.php');
                $appId = $this->config['dingxiang_captcha_appid'];
                $appSecret = $this->config['dingxiang_captcha_appsecret'];
                $client = new CaptchaClient($appId, $appSecret);
                $client->setTimeOut(5);      //设置超时时间，默认2秒
                $response = $client->verifyToken($_POST['CaptchaToken']);  //token指的是前端传递的值，即验证码验证成功颁发的token
                //确保验证状态是SERVER_SUCCESS，SDK中有容错机制，在网络出现异常的情况会返回通过
                if($response->serverStatus != 'SERVER_SUCCESS' || !$response->result){
                    /**token验证失败**/
                    $this->returnCodeError('图形校验码出现错误，请刷新页面后再试。');
                }
            }
        }
        $code = mt_rand(1000, 9999);

        if($type == 4){
            $text = L_('验证码：X1。您正在进行找回密码操作，请不要把验证码泄露给其他人。',$code);  
        }else{
            $text = L_('您的验证码是：X1。此验证码20分钟内有效，请不要把验证码泄露给其他人。如非本人操作，可不用理会！', $code);
        }
        if($_POST['newphone']){
            $phone = $_POST['newphone'];
        }

        $phone = phone_format($_POST['phone_country_type'], $phone);

        $columns = array();
        $columns['phone'] = $phone;
        $columns['mer_id'] = $this->mer_id;
        $columns['extra'] = $code;
        $columns['type'] = $type;
        $columns['status'] = 0;
        $columns['send_time'] = time();
        $columns['expire_time'] = $columns['send_time'] + 1200;
        $result = M("Merchant_sms_record")->add($columns);
        if (!$result){
            $this->returnCode('20044007');
        }
        $sms_data = array('mer_id' => $this->mer_id, 'store_id' => 0, 'content' => $text, 'mobile' => $phone, 'uid' => 0, 'type' => 'merchant_pwd');
        $_POST['phone_country_type'] && $sms_data['nationCode']  = $_POST['phone_country_type'];
        $return = Sms::sendSms($sms_data);

        if ($result != 0) {
            $this->returnCode(self::ConverSmsCode($return));
        }
        $this->returnCode(0, $return);
    }


    //群发记录
    public function custom_fans_list(){
        $mer_id = $this->merchant['mer_id'];

        $sql = "SELECT COUNT(1) as count, from_merchant FROM " . C('DB_PREFIX') . "merchant_user_relation WHERE `mer_id`='$mer_id' GROUP BY from_merchant";
        $mode = new Model();
        $count_list = $mode->query($sql);
        $total = 0;
        $arr['from_type']=array(
            '-1'=>L_(L_('全部粉丝')),
            '0'=>L_('扫描商家产品二维码'),
            '1'=>L_('扫描商家二维码'),
            '2'=>L_('平台赠送'),
            '3'=>L_('扫描产品推广二维码'),
            '5'=>$this->config['group_alias_name'],
            '6'=>$this->config['shop_alias_name'],
            '7'=>$this->config['meal_alias_name'],
            '8'=>$this->config['appoint_alias_name'],
            '9'=>$this->config['cash_alias_name'],
        );
        $tmp =array();
        $tmp[] = array('id' =>'-1', 'name' =>'全部粉丝');
        foreach ($count_list as $rr) {
            $total += $rr['count'];
            $tmp[] = array('id' => $rr['from_merchant'], 'name' =>  $arr['from_type'][$rr['from_merchant']], 'value' => $rr['count'],'score_need'=>$this->config['customer_one_score']*$rr['count']);
        }
        $tmp[0]['value'] = $total;
        $tmp[0]['score_need'] = $this->config['customer_one_score']*$total;
        $arr['fans_list']  =$tmp;


        $list = D('Source_material')->where(array('mer_id' => $this->merchant['mer_id']))->order('pigcms_id DESC')->select();
        $it_ids = array();
        $temp = array();
        foreach ($list as $l) {
            foreach (unserialize($l['it_ids']) as $id) {
                if (!in_array($id, $it_ids)) $it_ids[] = $id;
            }
        }
        $result = array();
        $image_text = D('Image_text')->field('pigcms_id, title')->where(array('pigcms_id' => array('in', $it_ids)))->order('pigcms_id asc')->select();
        foreach ($image_text as $txt) {
            $result[$txt['pigcms_id']] = $txt;
        }
        foreach ($list as &$l) {
            $l['dateline'] = date('Y-m-d H:i:s', $l['dateline']);
            foreach (unserialize($l['it_ids']) as $id) {
                $l['title'] = $result[$id]['title'];
//                $l['list'][] = isset($result[$id]) ? $result[$id] : array();
            }
        }
        $arr['list'] = $list;

        $arr['customer_one_score'] = $this->config['customer_one_score'];
        $arr['plat_score'] = $this->merchant['plat_score'];
        $arr['storage_indexsort'] = $this->merchant['storage_indexsort'];
        $arr['customer_one_score_exchange'] = $this->config['customer_one_score_exchange'];

        $this->returnCode(0, $arr);
    }

    public  function ChangeScore(){
        $mer_id = $this->merchant['mer_id'];
        $score = $_POST['score'];

        $database_merchant = D('Merchant');
        $condition_merchant['mer_id'] = $this->merchant['mer_id'];
        $now_merchant = $database_merchant->field(true,'pwd')->where($condition_merchant)->find();
        if($now_merchant['storage_indexsort'] >= $score){
            // 可以兑换的值
            $exchangeScore = $score*$this->config['customer_one_score_exchange'];
            $storage_indexsort = $now_merchant['storage_indexsort']-$score;
            $storage_indexsort = $storage_indexsort>0?$storage_indexsort:0;
            // 更新积分
            $database_merchant->where($condition_merchant)->setInc('plat_score',$exchangeScore);
            $database_merchant->where($condition_merchant)->save(array('storage_indexsort'=>$storage_indexsort));

            $current = D('Merchant')->field(true,'pwd')->where($condition_merchant)->find();
            $this->returnCode(0, array('error_code' => 0,'current_score'=>$current['plat_score'],'storage_indexsort'=>$current['storage_indexsort']));
        }
    }
    public function custom_send(){
        //$date = $this->custom_fans_list();
        //$fans_list  = $date['fan_list'];
        $mer_id = $this->merchant['mer_id'];
        $source_id = isset($_POST['source_id']) ? intval($_POST['source_id']) : 0;
        $type = isset($_POST['type']) ? intval($_POST['type']) : 0;
        if (empty($source_id)) {
            $this->returnCode('20140080');
        }
        $data = array('mer_id' => $mer_id, 'c_id' => $source_id, 'type' => $type, 'dateline' => time());

        // 扣除积分
        $condition_merchant['mer_id'] = $this->merchant['mer_id'];
        $exchangeScore = $_POST['score'];
        $current = D('Merchant')->field(true,'pwd')->where($condition_merchant)->find();
        if($current['plat_score'] < $exchangeScore){
            $this->returnCode('20140081');
        }

        $id = D('Send_log')->add($data);
        if (empty($id))  $this->returnCode('20140082');
        D('Merchant')->where($condition_merchant)->setDec('plat_score',$exchangeScore);
        $this->returnCode(0);
    }

    public function custom_user_detail(){
        $id = isset($_POST['logid']) ? intval($_POST['logid']) : 0;
        $page =I('pindex',1);

        $sql = "SELECT u.nickname,u.phone,u.avatar,u.sex,u.youaddress,u.uid, s.status as st FROM " . C('DB_PREFIX') . "send_user AS s LEFT JOIN  " . C('DB_PREFIX') . "user AS u ON `s`.`openid`=`u`.`openid` WHERE `s`.`log_id`={$id} LIMIT {$page},10";
        $mode = new Model();
        $fans_list = $mode->query($sql);
        $this->returnCode(0,  $fans_list);
    }

    public  function custom_txtdetail(){
        $p_id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        $source = D('Source_material')->where(array('pigcms_id' => $p_id))->find();
        $ids = unserialize($source['it_ids']);
        $image_text = D('Image_text')->field(true)->where(array('pigcms_id' => array('in', $ids)))->select();
        $result = array();
        foreach ($image_text as $key=>$txt) {
            unset($txt['content']);
            $txt['url']  =$this->config['site_url'].'/wap.php?c=Article&a=index&imid='.$txt['pigcms_id'];
            $txt['cover_pic']  =$this->config['site_url'].$txt['cover_pic'];
            $result[$txt['pigcms_id']] = $txt;
        }
        $image_text = array();
        foreach ($ids as $id) {
            $image_text[] = isset($result[$id]) ? $result[$id] : array();
        }
        $this->returnCode(0,  $image_text);

    }

    public function custom_send_list(){

        $page =I('pindex',1);
        $status = I('status',1);

        $from_type=array(
            '0'=>L_('扫描商家产品二维码'),
            '1'=>L_('扫描商家二维码'),
            '2'=>L_('平台赠送'),
            '3'=>L_('扫描产品推广二维码'),
            '5'=>$this->config['group_alias_name'],
            '6'=>$this->config['shop_alias_name'],
            '7'=>$this->config['meal_alias_name'],
            '8'=>$this->config['appoint_alias_name'],
            '9'=>$this->config['cash_alias_name'],
        );

        $log_list = D('Send_log')->where(array('mer_id' => $this->merchant['mer_id'],'status'=>$status))->order('pigcms_id DESC')->page($page,10)->select();

        $arr['send_count'] = D('Send_log')->where(array('mer_id' => $this->merchant['mer_id'],'status'=>1))->count();
        $arr['check_count'] = D('Send_log')->where(array('mer_id' => $this->merchant['mer_id'],'status'=>0))->count();
        $arr['rej_count'] = D('Send_log')->where(array('mer_id' => $this->merchant['mer_id'],'status'=>2))->count();

        foreach ($log_list as &$v) {
            $source = D('Source_material')->where(array('pigcms_id' => $v['c_id']))->find();
            $ids = unserialize($source['it_ids']);
            $image_text = D('Image_text')->field(true)->where(array('pigcms_id' => array('in', $ids)))->select();

            $v['title'] = $image_text[0]['title'];
            $v['digest'] = $image_text[0]['digest'];
            $v['fan_type'] = $from_type[$v['type']];
            $v['txt_count'] = count($image_text);
            $v['dateline'] = date('Y-m-d H:i:s',$v['dateline']);
        }
        $arr['log_list'] = $log_list?$log_list:array();
        $this->returnCode(0,  $arr);
    }

    //回复评价
    public function user_rating_reply(){
        $pigcms_id = $_POST['detail_id'];

        if(empty($pigcms_id) || empty($_POST['reply_content'])){
            $this->returnCode('20140079');
        }

        $database_reply = D('Reply');

        $reply_detail = $database_reply->where(array('pigcms_id'=>$pigcms_id))->find();
        if(empty($reply_detail)){
            $this->returnCode('20140075');
        }
        if($reply_detail['mer_id'] != $this->mer_id){
            $this->returnCode('20140078');
        }
        if($reply_detail['merchant_reply_time']){
            $this->returnCode('20140076');
        }

        $condition_reply['pigcms_id'] = $reply_detail['pigcms_id'];
        $data_reply['merchant_reply_content'] = $_POST['reply_content'];
        $data_reply['merchant_reply_time'] = $_SERVER['REQUEST_TIME'];
        if($database_reply->where($condition_reply)->data($data_reply)->save()){
            $this->returnCode('0');
        }else{
            $this->returnCode('20140077');
        }
    }

    //评价列表
    public function user_rating_list(){
        if(empty($_POST['page'])){
            $_POST['page'] = 1;
        }
        $return = array();

        $condition_reply['order_type'] = isset($_POST['order_type']) ? $_POST['order_type'] : 3;

        if($_POST['page'] <= 1 && empty($_POST['tab']) && empty($_POST['detail_id'])){
            $store_list = D('Merchant_store')->field('`store_id`,`name`,`have_group`,`have_meal`,`have_shop`')->where(array('mer_id'=>$this->mer_id,'status'=>array('neq',4)))->order('`sort` DESC,`store_id` ASC')->select();
            $return['store_list'] = $store_list;

            $return['have_group'] = false;
            $return['have_meal'] = false;
            $return['have_shop'] = false;
            foreach($return['store_list'] as $value){
                if($value['have_group']){
                    $return['have_group'] = true;
                }
                if($value['have_meal']){
                    $return['have_meal'] = true;
                }
                if($value['have_shop']){
                    $return['have_shop'] = true;
                }
            }
            if(!empty($return['have_shop'])){
                $condition_reply['order_type'] = 3;
            }else if(!empty($return['have_group'])){
                $condition_reply['order_type'] = 0;
            }else{
                $condition_reply['order_type'] = 4;
            }
        }

        if($_POST['store_id']){
            $condition_reply['store_id'] = $_POST['store_id'];
        }else{
            $condition_reply['mer_id']   = $this->mer_id;
        }

        if($_POST['tab']){
            switch($_POST['tab']){
                case 'high':
                    $condition_reply['score'] = array('gt',3);
                    break;
                case 'mid':
                    $condition_reply['score'] = 3;
                    break;
                case 'low':
                    $condition_reply['score'] = array('lt',3);
                    break;
            }
        }
        $condition_reply['status'] = array('elt',1);
        if($_POST['merchant_reply']){
            switch($_POST['merchant_reply']){
                case 'yes':
                    $condition_reply['merchant_reply_content'] = array('neq','');
                    break;
                case 'no':
                    $condition_reply['merchant_reply_content'] = '';
                    break;
            }
        }

        $reply_count = M('Reply')->where($condition_reply)->count();

        $page_size = 10;
        $firstRow = ($_POST['page']-1) * $page_size;

        if($_POST['detail_id']){
            $condition_reply = array(
                'pigcms_id' => $_POST['detail_id'],
            );
        }

        $condition_reply_string = '';
        foreach($condition_reply as $key=>$value){
            $character = '=';
            if(is_array($value)){
                if($value[0] == 'lt'){
                    $character = '<' ;
                }else if($value[0] == 'gt'){
                    $character = '>' ;
                }else if($value[0] == 'neq'){
                    $character = '<>' ;
                }else if($value[0] == 'elt'){
                    $character = '<=' ;
                }
                $value = $value[1];
            }

            $condition_reply_string .= "`r`.`$key`$character'$value' AND";
        }
        $condition_reply_string .= '`r`.`uid`=`u`.`uid`';

        $return['reply_list'] = D('')->field('`u`.`nickname`,`u`.`avatar`,`r`.*')->table(array(C('DB_PREFIX').'reply'=>'r',C('DB_PREFIX').'user'=>'u'))->where($condition_reply_string)->order('`r`.`add_time` DESC')->limit($firstRow . ',' . $page_size)->select();

        if($return['reply_list']){
            $pic_arr = array();
            $new_pic_arr = array();
            foreach($return['reply_list'] as $key=>$value){
                if (isset($value['goods']) && $value['goods']) {
                    $return['reply_list'][$key]['goods'] = explode('#@#', $value['goods']);
                }else{
                    $return['reply_list'][$key]['goods'] = array();
                }
                $return['reply_list'][$key]['add_time'] = date('Y-m-d',$value['add_time']);
                $return['reply_list'][$key]['add_time_hi'] = date('Y-m-d H:i',$value['add_time']);
                $return['reply_list'][$key]['avatar'] = replace_file_domain($value['avatar']);
                if($value['merchant_reply_time']){
                    $return['reply_list'][$key]['merchant_reply_time_hi'] = date('Y-m-d H:i',$value['merchant_reply_time']);
                }
                if($value['anonymous']){
                    if(msubstr($value['nickname'],0,2,false) == $value['nickname']){
                        $return['reply_list'][$key]['nickname'] = msubstr($value['nickname'],0,1,false).'**';
                    }else{
                        $return['reply_list'][$key]['nickname'] = msubstr($value['nickname'],0,1,false).'**'.msubstr($value['nickname'],-1,1,false);
                    }
                }
                if(!empty($value['pic'])){
                    $tmp_arr = explode(',',$value['pic']);
                    foreach($tmp_arr as $v){
                        $new_pic_arr[$v] = $key;
                    }
                    $pic_arr = array_merge($pic_arr,$tmp_arr);
                }
                if ($condition_reply['order_type'] == 3 && $value['reply_pic']) {
                    $images = (new shop_order_reply_image())->get_allImage_by_path($value['reply_pic'], '0');
                    $showImages = [];
                    foreach ($images as $v) {
                        $showImages[] = [
                            'image' => $v,
                            's_image' => thumb_img($v, 67, 67, 'fill')
                        ];
                    }
                    $return['reply_list'][$key]['reply_pic'] = $showImages;
                } else {
                    $return['reply_list'][$key]['reply_pic'] = [];
                }
            }
            if ($order_type == 0) {
                $pic_filepath = 'group';
            } elseif($order_type == 1) {
                $pic_filepath = 'meal';
            } else {
                $pic_filepath = 'appoint';
            }
            if($pic_arr){
                $condition_reply_pic['pigcms_id'] = array('in',implode(',',$pic_arr));
                $reply_pic_list = D('Reply_pic')->field('`pigcms_id`,`pic`')->where($condition_reply_pic)->select();
                $reply_image_class = new reply_image();
                foreach($reply_pic_list as $key=>$value){
                    $tmp_value = $reply_image_class->get_image_by_path($value['pic'],$pic_filepath);
                    $return['reply_list'][$new_pic_arr[$value['pigcms_id']]]['pics'][] = $tmp_value;
                }
            }
        }else{
            $return['reply_list'] = array();
        }
        $return['store_score'] = -1;
        $return['store_deliver_score'] = -1;
        if ($condition_reply['order_type'] == 3 && $_POST['store_id']) {
            $store = D('Merchant_store_shop')->field('score_mean, reply_deliver_score')->where(array('store_id' => $_POST['store_id']))->find();
            $return['store_score'] = isset($store['score_mean']) ? $store['score_mean'] : 0;
            $return['store_deliver_score'] = isset($store['reply_deliver_score']) ? $store['reply_deliver_score'] : 0;
        }
        $this->returnCode(0,$return);
    }

    public function allinyun_money_list(){
        import('@.ORG.AccountDeposit.AccountDeposit');
        $deposit = new AccountDeposit('Allinyun');
        $allyun = $deposit->getDeposit();
        $allinyun = M('Merchant_allinyun_info')->where(array('mer_id'=>$this->mer_id))->find();
        $allyun->setUser($allinyun);
        if($allinyun['status']!=0){
            $balance = $allyun->queryBalance();
        }
        if(isset($_POST['period'])&&!empty($_POST['period'])) {

            $period = explode('-', $_POST['period']);
            $_POST['begin_time'] = str_replace('/','-',$period[0]);
            $_POST['end_time'] = str_replace('/','-',$period[1]);
        }

        $arr['money'] = floatval($balance['signedResult']['allAmount']/100);

        $result = $allyun->queryInExpDetail($_GET['page']?($_POST['page']-1)*10+1:1,$_POST['begin_time'],$_POST['end_time']);
        $order_type  =$this->get_alias_c_name2();
        foreach ($result['signedResult']['inExpDetail'] as &$v) {
            $tmp = explode('_',$v['bizOrderNo']);
            $v['order_type']  =$order_type[$tmp[0]];
            $v['order_id']  =$tmp[1];
            $v['chgAmount']  =$v['chgAmount']/100;
        }
        $count = $result['signedResult']['totalNum'];
        $arr = array('income_list'=>$result['signedResult']['inExpDetail'],'page_num'=>$count);
        $this->returnCode(0,$arr);

    }

    public function create_bank_account(){
        $data_companypay['from']     = 1;
        $data_companypay['type']     = 0;
        $data_companypay['pay_id']   = $this->mer_id;
        $data_companypay['account_name'] = $_POST['account_name'];
        $data_companypay['account'] = $_POST['account'];
        $data_companypay['is_default'] = $_POST['is_default'];
        $data_companypay['add_time'] = $_SERVER['REQUEST_TIME'];
        $data_companypay['remark']    = $_POST['remark'];
        $data_companypay['province_id']    = $_POST['province_id'];
        $data_companypay['city_id']    = $_POST['city_id'];
        $data_companypay['branch']    = $_POST['branch'];

        if($_POST['bank_id']){
            $url  = U('bank_list');
            $result = M('User_withdraw_account_list')->where(array('id'=>$_POST['bank_id']))->save($data_companypay);
            if($result){
                $aid = $_POST['bank_id'];
            }else{
                $aid = false;
            }
        }else{
            $aid = M('User_withdraw_account_list')->add($data_companypay);
            $url  = U('withdraw');
        }

        if($aid){
            if($data_companypay['is_default']){
                $where['from'] = 1;
                $where['type'] = 0;
                $where['pay_id'] =   $this->mer_id;
                $where['id'] = array('neq',$aid);
                M('User_withdraw_account_list')->where($where)->setField('is_default',0);

            }
            $this->returnCode(0,array('status'=>1,'msg'=>L_('保存成功')));die;
        }else{
            $this->returnCode('20170108','',L_('保存失败'));
        }
    }

    public function bank_account_info(){
        $where['id'] =  $_POST['bank_id'];
        $bank = M('User_withdraw_account_list')->where($where)->find();
        if(empty($bank)){
            $this->returnCode('20170109','',L_('卡不存在'));
        }
        $bank['add_time'] = date('Y-m-d H:i:s',$bank['add_time']);

        $province = M('Area')->where(['area_id' => $bank['province_id']])->getField('area_name');
        $city = M('Area')->where(['area_id' => $bank['city_id']])->getField('area_name');
        $bank['city_name'] = $province . ' ' . $city;

        $return['bank']  = $bank;
        $this->returnCode(0,$return);
    }

    public function bank_list(){
        $where['from'] = 1;
        $where['type'] = 0;
        $where['pay_id'] =  $this->mer_id;
        $bank_list = M('User_withdraw_account_list')->where($where)->order('is_default DESC,id DESC')->select();
        if(!$bank_list){
            $bank_list = array();
        }else{
            foreach ($bank_list as &$v) {
                $v['add_time'] = date('Y-m-d H:i:s',$v['add_time']);
                $v['bank_city'] = '';
                if($v['province_id']){
                    $province = M('Area')->where(['area_id' => $v['province_id']])->getField('area_name');
                    $city = M('Area')->where(['area_id' => $v['city_id']])->getField('area_name');
                    $v['bank_city'] = $province . $city;
                }
            }
        }
        $return['bank_list']  = $bank_list;

        $this->returnCode(0,$return);
    }

    public function delete_bank_account(){
        $where['id'] = $_POST['bank_id'];
        M('User_withdraw_account_list')->where($where)->delete();
        $this->returnCode(0,array('status'=>1,'msg'=>L_('删除成功')));die;
    }

    //添加业务员
    public function set_invit_code()
    {
        if(!M('Admin')->where(array('invit_code'=>$_POST['invit_code']))->find()){
            $this->returnCode(1,'',L_('该邀请码不存在'));
        }
        M('Merchant')->where(array('mer_id'=>$this->merchant['mer_id']))->setField('invit_code',$_POST['invit_code']);
        $this->returnCode(0);
    }

    //错误代码
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

    // 商户平台积分信息
    public function system_score(){

        $mer_id = intval($this->merchant['mer_id']);
        $mer_info = D('Merchant')->get_info($mer_id);

        $condition_where['mer_id'] = $mer_id;
        if(isset($_POST['period'])&&!empty($_POST['period'])){
            $period = explode('-',$_POST['period']);
            $start_time = $period[0];
            $end_time = $period[1];
            // 时间
            if(!empty($start_time) && !empty($end_time)){
                $condition_where['time'] = array(array('gt',strtotime($start_time." 00:00:00")),array('lt',strtotime($end_time." 23:59:59")));
            }elseif($start_time){
                $condition_where['time'] = array('gt',strtotime($start_time." 00:00:00"));
            }elseif($end_time){
                $condition_where['time'] = array('lt',strtotime($end_time." 23:59:59"));
            }
        }
        $_GET['page'] = $_POST['page'];
        // 类型
        if($_POST['type']){
            $condition_where['type'] = $_POST['type'];
        }

        $db_merchant_score_list = D("Merchant_score_list");
        $score_list = $db_merchant_score_list->get_limit_list_page($condition_where,10);

        // 统计
        $sore_num = array();
        $condition_where['type'] = 1;
        $score_get = $db_merchant_score_list->field('sum(score) as num')->where($condition_where)->find();
        $sore_num['score_get'] = floatval($score_get['num']) ? $score_get['num'] : 0;
        $condition_where['type'] = 2;
        $score_use = $db_merchant_score_list->field('sum(score) as num')->where($condition_where)->find();
        $sore_num['score_use'] = floatval($score_use['num']) ? $score_use['num'] : 0;

        $return['mer_info'] = $mer_info;
        $return['sore_num'] = $sore_num;
        $return['list'] = $score_list;
        $this->returnCode(0,$return);
    }

    public function system_score_detail(){
        $m_merchant_score_list = M("Merchant_score_list");
        $id = $_POST['id'];
        $detail = $m_merchant_score_list->where(array('id'=>$id))->find();
        if(empty($detail)){
            $this->returnCode(1,array(),L_('没有平台商户积分信息!'));
        }

        $mer_info = D('Merchant')->get_info($detail['mer_id']);
        $detail['mer_name'] = $mer_info['name'];
        $detail['time'] = date('Y-m-d H:i:s',$detail['time']);
        if ($detail['order_id']) {
            $income[]=array('name'=>L_('订单编号'),'value'=>$detail['order_id']);
        }
        $income[]=array('name'=>L_('商户名'),'value'=>$mer_info['name']);
        $income[]=array('name'=>L_('积分数量'),'value'=>$detail['score']);

        if($detail['type']==1){
            $income[]=array('name'=>L_(L_('类型')),'value'=>L_('增加'));
        }else{
            $income[]=array('name'=>'类型','value'=>L_('消耗'));
        }
        if($detail['time']){
            $income[]=array('name'=>L_('操作时间'),'value'=>$detail['time']);
        }
        $income[]=array('name'=>L_('描述'),'value'=>$detail['desc']);


        $this->returnCode(0,array('data'=>$income));

    }

    public function merchant_login(){
        if($this->merchant){
            session_start();
            session('merchant_gift',$this->merchant);
            $this->returnCode(0,array('data'=>$this->config['site_url'].'/wap.php?c=Gift&a=Index'));
        }else{
            $this->returnCode(1,'',L_('请先登录商家账号'));
        }
    }

    //商家平台积分接口
    public function merchant_platform_score(){
        $_GET['page'] = $page =  $_POST['page'];
        $mer_id = intval($this->merchant['mer_id']);
        $condition_where['a.mer_id'] = $mer_id;

        if(isset($_POST['period'])&&!empty($_POST['period'])){
            $period = explode('-',$_POST['period']);
            $begin_time = $period[0];
            $end_time = $period[1];

            if (strtotime($begin_time)>strtotime($end_time)) {
                $this->returnCode('20140055');
            }

            if(!empty($begin_time) && !empty($end_time)){
                $condition_where['a.add_time'] = array(array('gt',strtotime($begin_time." 00:00:00")),array('lt',strtotime($end_time." 23:59:59")));
            }elseif($begin_time){
                $condition_where['a.add_time'] = array('gt',strtotime($begin_time." 00:00:00"));
            }elseif($end_time){
                $condition_where['a.add_time'] = array('lt',strtotime($end_time." 23:59:59"));
            }

        }
        $m_merchant_score_order = M("Merchant_score_order");

        $sore_num = array(
            'pay_score_add_num' => 0,
            'user_score_num' => 0,
            'admin_score_add_num' => 0,
            'admin_score_reduce_num' => 0,
        );
        if($_POST['set_state'] != ''){
            if ($_POST['set_state'] == 1) {
                // 支付成功增加
                $condition_where['_string'] = "a.`paid`=1 AND a.`operation_type`=0 AND a.`set_type`=0";
                // 支付成功增加积分数
                $pay_score_add_num = $m_merchant_score_order->alias('a')->where($condition_where)->field('sum(sore_count) AS sore_count_num')->select();
                $sore_num['pay_score_add_num'] = $pay_score_add_num[0]['sore_count_num'];

            } elseif ($_POST['set_state'] == 2) {
                // 用户消费消耗
                $condition_where['_string'] = "a.`business_id`>0 AND a.`business_type`!='' AND a.`set_type`=1";
                // 用户消费消耗积分数
                $user_score_num = $m_merchant_score_order->alias('a')->where($condition_where)->field('sum(sore_count) AS sore_count_num')->select();
                $sore_num['user_score_num'] = $user_score_num[0]['sore_count_num'];
            } elseif ($_POST['set_state'] == 3) {
                // 管理员操作增加
                $condition_where['_string'] = "a.`operation_type`=1 AND a.`set_type`=0";
                // 管理员操作增加积分数
                $admin_score_add_num = $m_merchant_score_order->alias('a')->where($condition_where)->field('sum(sore_count) AS sore_count_num')->select();
                $sore_num['admin_score_add_num'] = $admin_score_add_num[0]['sore_count_num'];
            } elseif ($_POST['set_state'] == 4) {
                // 管理员操作减少
                $condition_where['_string'] = "a.`operation_type`=1 AND a.`set_type`=1";
                // 管理员操作减少积分数
                $admin_score_reduce_num = $m_merchant_score_order->alias('a')->where($condition_where)->field('sum(sore_count) AS sore_count_num')->select();
                $sore_num['admin_score_reduce_num'] = $admin_score_reduce_num[0]['sore_count_num'];
            }
        } else{
            $condition_where['_string'] = '(a.`paid`>=1) OR (a.`paid`=0 AND a.`operation_type`=1) OR (a.`paid`=0 AND a.`business_id`>0)';

            // 支付成功增加积分数
            $where_pay_score_add = $condition_where;
            $where_pay_score_add['_string'] = "a.`paid`=1 AND a.`operation_type`=0 AND a.`set_type`=0";
            $pay_score_add_num = $m_merchant_score_order->alias('a')->where($where_pay_score_add)->field('sum(sore_count) AS sore_count_num')->select();
            $sore_num['pay_score_add_num'] = $pay_score_add_num[0]['sore_count_num'];
            // 用户消费消耗积分数
            $where_user_score_num = $condition_where;
            $where_user_score_num['_string'] = "a.`business_id`>0 AND a.`business_type`!='' AND a.`set_type`=1";
            $user_score_num = $m_merchant_score_order->alias('a')->where($where_user_score_num)->field('sum(sore_count) AS sore_count_num')->select();
            $sore_num['user_score_num'] = $user_score_num[0]['sore_count_num'];
            // 管理员操作增加积分数
            $where_admin_score_num = $condition_where;
            $where_admin_score_num['_string'] = "a.`operation_type`=1 AND a.`set_type`=0";
            $admin_score_add_num = $m_merchant_score_order->alias('a')->where($where_admin_score_num)->field('sum(sore_count) AS sore_count_num')->select();
            $sore_num['admin_score_add_num'] = $admin_score_add_num[0]['sore_count_num'];
            // 管理员操作减少积分数
            $where_admin_score_num = $condition_where;
            $where_admin_score_num['_string'] = "a.`operation_type`=1 AND a.`set_type`=1";
            $admin_score_reduce_num = $m_merchant_score_order->alias('a')->where($where_admin_score_num)->field('sum(sore_count) AS sore_count_num')->select();
            $sore_num['admin_score_reduce_num'] = $admin_score_reduce_num[0]['sore_count_num'];

        }
        $arr['sore_num'] = $sore_num;


        $count = $m_merchant_score_order->alias('a')
            ->join('LEFT JOIN pigcms_user as c on a.operation=c.uid')
            ->where($condition_where)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count, 10);
        $nowPage = $p->nowPage;
        $totalPage = $p->totalPage;
        if ($page > $totalPage) {
            $orderList = array();
        } else {
            $orderList= $m_merchant_score_order->alias('a')
                ->join('LEFT JOIN pigcms_user as c on a.operation=c.uid')
                ->where($condition_where)->field('a.*,c.nickname,c.phone')->order('order_id DESC')->limit($p->firstRow,$p->listRows)->select();

            if ($orderList) {
                foreach ($orderList as &$val) {
                    if($val['paid']=1 && $val['operation_type']==0 && $val['set_type']==0) {
                        $val['desc'] = L_('支付成功增加积分数');
                        $val['sore_count_desc'] = '+'.$val['sore_count'];
                    } elseif ($val['business_id']>0 && $val['business_type']!='' && $val['set_type']==1) {
                        $val['desc'] = L_('用户消费消耗积分数');
                        $val['sore_count_desc'] = '-'.$val['sore_count'];
                    } elseif ($val['operation_type']==1 && $val['set_type']==0) {
                        $val['desc'] = L_('管理员操作增加积分数');
                        $val['sore_count_desc'] = '+'.$val['sore_count'];
                    } elseif ($val['operation_type']==1 && $val['set_type']==1) {
                        $val['desc'] = L_('管理员操作减少积分数');
                        $val['sore_count_desc'] = '-'.$val['sore_count'];
                    }
                }
            } else {
                $orderList = array();
            }
        }
        $mer_info = D('Merchant')->get_info($mer_id);

        $arr['mer_info'] = $mer_info;
        $arr['order_list'] = $orderList;
        $arr['nowPage'] = $nowPage;
        $arr['page_num'] = $totalPage;
        $arr['count'] = $count;

        $this->returnCode(0,$arr);
    }

    public function merchant_platform_info(){
        $m_merchant_score_order = M("Merchant_score_order");
        $order_id = $_POST['id'];
        $order_info = $m_merchant_score_order->where(array('order_id'=>$order_id))->find();
        if(empty($order_info)){
            $this->returnCode(1,array(),L_('没有商户平台积分信息!'));
        }
        $type_info = '';
        $type = '';
        if($order_info['paid']=1 && $order_info['operation_type']==0 && $order_info['set_type']==0) {
            $type_info = L_('支付成功增加积分数');
            $type = L_(L_('增加积分'));
        } elseif ($order_info['business_id']>0 && $order_info['business_type']!='' && $order_info['set_type']==1) {
            $type_info = L_('用户消费消耗积分数');
            $type = L_('消耗积分');
        } elseif ($order_info['operation_type']==1 && $order_info['set_type']==0) {
            $type_info = L_('管理员操作增加积分数');
            $type = '增加积分';
        } elseif ($order_info['operation_type']==1 && $order_info['set_type']==1) {
            $type_info = L_('管理员操作减少积分数');
            $type = L_('减少积分');
        }

        $income[]=array('name'=>L_('订单编号'),'value'=>$order_info['order_id']);
        $income[]=array('name'=>L_('积分数量'),'value'=>$order_info['sore_count']);
        if ($type) {
            $income[]=array('name'=>L_('操作类型'),'value'=>$type);
        }
        if($order_info['payment_money'] && $order_info['business_id'] > 0){
            $income[]=array('name'=>L_('支付金额'),'value'=>$order_info['payment_money']);
        }
        if ($order_info['operation'] && $order_info['business_id'] > 0) {
            $nickname = M('User')->where(array('uid' => $order_info['operation']))->getField('nickname');
            if ($nickname) {
                $income[]=array('name'=>L_('用户昵称'),'value'=>$nickname);
            }
        }


        if($order_info['pay_time']){
            $income[]=array('name'=>L_('支付时间'),'value'=>date('Y/m/d H:i:s',$order_info['pay_time']));
        }
        if ($type_info) {
            $income[]=array('name'=>L_('描述'),'value'=>$type_info);
        }

        $this->returnCode(0,array('merchant_platform_info'=>$income));

    }

    // 获取相关购买平台积分信息
    public function merchant_buy_score_info() {
        $mer_id = intval($this->merchant['mer_id']);
        $mer_info = D('Merchant')->get_info($mer_id);
        if(empty($mer_info)){
            $this->returnCode(1,'',L_('未获取到您的帐号信息，请重试~'));
        }
        $arr = array();
        $arr['merchant_buy_points'] = $this->config['merchant_buy_points'];
        $arr['mer_info'] = $mer_info;
        $this->returnCode(0,$arr);
    }

    // 提交生成购买订单
    public function score_buy(){
        $mer_id = intval($this->merchant['mer_id']);

        if($_POST['buy'] && $_POST['buy'] > 0){
            $data_sms_buy_order['mer_id'] = $mer_id;
            $data_sms_buy_order['payment_money'] = $_POST['buy'];
            $data_sms_buy_order['sore_count'] = $this->config['merchant_buy_points']*$_POST['buy'];
            $data_sms_buy_order['add_time'] = $_SERVER['REQUEST_TIME'];
            $data_sms_buy_order['orderid'] = 'mer_'.date("ymdHis").rand(10,99).sprintf("%06d",$mer_id);

            if ($order_id = M('Merchant_score_order')->data($data_sms_buy_order)->add()) {
                $arr = array(
                    'order_id' => $order_id,
                    'type' => 'buy_score'
                );
                $this->returnCode(0, $arr);
            } else {
                $this->returnCode(1,'',L_('订单创建失败，请重试~'));
            }
        } else {
            $this->returnCode(1,'',L_('非法提交！'));
        }
    }

    // 获取购买平台积分订单信息
    public function merchant_buy_score_order_check(){
        $mer_id = intval($this->merchant['mer_id']);
        if(!in_array($_POST['type'],array('buy_score'))){
            $this->returnCode(1,'',L_('订单来源无法识别，请重试~'));
        }

        $order_info = D('Merchant_score_order')->where(array('order_id'=>$_POST['order_id'],'mer_id'=>$mer_id))->find();
        if($order_info['paid'] == 1){
            $this->returnCode(1,'',L_('订单已支付'));
        }
        $mer_info = D('Merchant')->get_info($mer_id);
        if(empty($mer_info)){
            $this->returnCode(1,'',L_('未获取到您的帐号信息，请重试~'));
        }

//        $pay_method = D('Config')->get_pay_method();
//        $tmp_pay_method['weixin'] = $pay_method['weixin'];
//        if(empty($pay_method)){
//            $this->returnCode(1,'','系统管理员没开启任一一种支付方式！');
//        }
        $order_info['order_type'] = 'mer_buy_score';


        $arr = array();
        $arr['pay_method'] = array();
        $arr['merchant_buy_points'] = $this->config['merchant_buy_points'];
        $arr['mer_info'] = $mer_info;
        $arr['order_info'] = $order_info;
        $this->returnCode(0,$arr);
    }

    // 积分商城订单
    public function gift_order(){

        $mer_id = intval($this->merchant['mer_id']);
        $mer_info = D('Merchant')->get_info($mer_id);

        $this->assign('mer_info',$mer_info);
        $condition_where['mer_id'] = $mer_id;

        $_GET['page'] = $_POST['page'];

        $database_gift_order = D('Gift_order');
        $orders = $database_gift_order->get_order_list($this->merchant['mer_id'],0,true,10,2);
        foreach ($orders['order_list'] as &$value) {
            $value['score_name'] = $this->score_name;
        }
        $return['list'] = $orders;
        $this->returnCode(0,$return);
    }

    public function gift_order_detail(){
        $mer_id = intval($this->merchant['mer_id']);
        $mer_info = D('Merchant')->get_info($mer_id);
        if(empty($mer_info)){
            $this->returnCode(1,'',L_('未获取到您的帐号信息，请重试~'));
        }
        $order_id = $_POST['id'];
        $database_gift_order = D('Gift_order');
        $order_info = $database_gift_order->get_order_detail_by_id_and_merId($order_id);

        $income[]=array('name'=>L_('订单编号'),'value'=>$order_info['order_id']);
        $income[]=array('name'=>L_('商品名称'),'value'=>$order_info['order_name']);
        $income[]=array('name'=>L_('数量'),'value'=>$order_info['num']);

        if ($order_info['exchange_type']==1) {
            $momey_txt = L_('X1：X2，金额：X3',array('X1'=>$this->score_name,'X2'=>$order_info['total_integral'],'X3'=>$order_info['total_price']));
        }else{
            $momey_txt = $this->score_name.'：' .$order_info['total_integral'];
        }
        $income[]=array('name'=>L_('总价'),'value'=>$momey_txt);

        $income[]=array('name'=>L_('订单状态'),'value'=>$order_info['paid']==1 ? L_('已支付') : L_('未支付'));

        if($order_info['order_time']){
            $income[]=array('name'=>L_('订单时间'),'value'=>date('Y/m/d H:i:s',$order_info['order_time']));
        }
        $this->returnCode(0,array('data'=>$income));
    }


    public function go_pay_yue(){
        $mer_id = intval($this->merchant['mer_id']);

        $order_info = D('Merchant_score_order')->where(array('order_id'=>$_POST['order_id'],'mer_id'=>$mer_id))->find();

        if($order_info['paid'] == 1){
            $this->returnCode(1,'',L_('订单已支付'));
        }

        $mer_info = D('Merchant')->get_info($mer_id);
        if(empty($mer_info)){
            $this->returnCode(1,'',L_('未获取到您的帐号信息，请重试~'));
        }
        if($mer_info['money']<$order_info['payment_money']){
            $this->returnCode(1,'',L_('当前商户余额不足请充值！'));
        }

        $use_money = D('Merchant_money_list')->use_money($mer_id, $order_info['payment_money'], 'buy_score', L_('购买平台积分扣除商家余额'),$_POST['order_id']);
        if (!$use_money['error_code']) {
            M('Merchant_score_order')->where(array('order_id'=>$_POST['order_id']))->data(array('paid'=>1,'pay_time'=>time(),'pay_type'=>'yue'))->save();
            M('Merchant')->where(array('mer_id'=>$mer_id))->setInc('platform_score',$order_info['sore_count']);
            $this->returnCode(0);
        } else {
            if ($use_money['msg']) {
                exit(json_encode(array('error'=>3,'msg'=>$use_money['msg'])));
            } else {
                $this->returnCode(1,'',L_('支付失败，请重试~'));
            }
        }
    }


    /**认领店铺信息*/
    // 店铺列表
    public function claim_store_list() {
        $mer_id = $this->merchant['mer_id'];

        $page = I('pindex', 1);
        $store_id = I('store_id', '');
        $store_id && $where['store_id'] = $condition['store_id'] = $store_id;

        $where['is_claim'] = 1;
        $where['claim_status'] = 1;
        $condition = array();
        $all = M('Merchant_store')->where($where)->count();

        if(isset($_POST['claim_status']) && $_POST['claim_status']>-1){
            if ($_POST['claim_status'] == 1) {
                // 待认领 即mer_id 为空 claim_store_id 为空
                $condition['mer_id'] = array('elt', 0);
            } elseif ($_POST['claim_status'] == 2) {
                // 已认领 即mer_id 有值 claim_store_id 有值
                $condition['mer_id'] = array('gt', 0);
            }
        }
        $condition['is_claim'] = 1;
        $condition['claim_status'] = 1;
        $data = M('Merchant_store')->field(true)
            ->where($condition)
            ->page($page, 10)
            ->select();
        if ($data) {
            foreach ($data as &$val) {
                $val['status'] = $val['claim_status'];
                if ($val['mer_id'] <= 0 || $val['mer_id'] == '') {
                    $val['claim_status'] = 1;  // 待认领
                } elseif ($val['mer_id'] > 0 && $val['mer_id'] == $mer_id) {
                    $val['claim_status'] = 3;  // 用户自己已经认领
                } else {
                    $val['claim_status'] = 2;  // 已经认领
                }
            }
        }


        $arr['data'] = isset($data) ? $data : array();
        $arr['all'] = $all;
        $where['mer_id'] = $condition['mer_id'] = array('elt', 0);
        $arr['claim'] = M('Employee_claim_store')->where($where)->count();
        $where['mer_id'] = $condition['gt'] = array('gt', 0);
        $arr['is_claimed'] = M('Employee_claim_store')->where($where)->count();
        $arr['page'] = ceil($arr['all'] / 10);
        $this->returnCode(0, $arr);
    }

    // 待认领店铺详情
    public function claim_store()
    {
        $store_id = I('store_id');
        if (empty($store_id)) {
            //$this->returnCode('20140029');
        }
        $data = M('Merchant_store')->where(array('store_id' => $store_id, 'claim_status' => 1, 'is_claim' => 1))->find();
        if (empty($data)) {
            $this->returnCode(1,'',L_('未获取到认领店铺信息，请重试~'));
        } elseif ($data['mer_id'] > 0) {
            $this->returnCode(1,'',L_('该店铺已经被认领，不可重复被认领~'));
        }


        if (! empty($data)) {
            if (! empty($data['pic_info'])) {
                $store_image_class = new store_image();
                $tmp_pic_arr = explode(';', $data['pic_info']);
                foreach ($tmp_pic_arr as $key => $value) {
                    $data['pic'][$key]['title'] = $value;
                    $data['pic'][$key]['url'] = $store_image_class->get_image_by_path($value);
                }
            }
        }

        if ($data && $_POST['coordinate_type'] == 'wgs84') {
            list($data['long'], $data['lat']) = Coordinate::bd09ToWgs84($data['long'], $data['lat']);
        }
      
        $catList = D('Merchant_category')->lists();
        $arr = array(
            'store_id' => $data['store_id'], // 商铺ID
            'name' => $data['name'], // 商户ID
            'phone' => $data['phone'], // 手机
            'kefu_phone' => $data['kefu_phone'], // 客服电话
//             'weixin' => $data['weixin'], // 微信
//             'qq' => $data['qq'], // QQ
            'keywords' => '', // 关键词
            'permoney' => $data['permoney'], // 人均消费
            'feature' => $data['feature'], // 店铺特色
            'cat_id' => $data['cat_id'], // 省
            'cat_fid' => $data['cat_fid'], // 市
            'province_id' => $data['province_id'], // 省
            'city_id' => $data['city_id'], // 市
            'area_id' => $data['area_id'], // 区
            'circle_id' => $data['circle_id'], // 商圈
            'adress' => $data['adress'], // 地址
            'trafficroute' => $data['trafficroute'], // 交通路线
            'sort' => $data['sort'], // 排序
            'have_meal' => $data['have_meal'], // 餐饮是否开启 0关闭 1开启
            'have_group' => $data['have_group'], // 团购是否开启 0关闭 1开启
            'have_shop' => $data['have_shop'], // 快店是否开启 0关闭 1开启
            'open_1' => $data['open_1'] ? substr($data['open_1'], 0, 5) : '00:00', // 打开时间1
            'open_2' => $data['open_2'] ? substr($data['open_2'], 0, 5) : '00:00', // 打开时间2
            'open_3' => $data['open_3'] ? substr($data['open_3'], 0, 5) : '00:00', // 打开时间3
            'close_1' => $data['close_1'] ? substr($data['close_1'], 0, 5) : '00:00', // 结束时间1
            'close_2' => $data['close_2'] ? substr($data['close_2'], 0, 5) : '00:00', // 结束时间2
            'close_3' => $data['close_3'] ? substr($data['close_3'], 0, 5) : '00:00', // 结束时间3
            'lat' => $data['lat'], // 经
            'long' => $data['long'], // 纬
            'txt_info' => $data['txt_info'], // 简介
            'pic' => isset($data['pic']) ? $data['pic'] : array(),
//             'discount_type' => $data['discount_type'],
            'discount_txt' => unserialize($data['discount_txt']),
            'vip_discount_type'=>$data['vip_discount_type'],
            'catList' => $catList
        );

        $areaList = D('Area')->field(true)->where(array('area_id' => array('in', array($data['province_id'], $data['city_id'], $data['area_id'], $data['circle_id']))))->select();
        foreach ($areaList as $al) {
            if ($al['area_id'] == $arr['province_id']) {
                $arr['province_name'] = $al['area_name'];
            } elseif ($al['area_id'] == $arr['city_id']) {
                $arr['city_name'] = $al['area_name'];
            } elseif ($al['area_id'] == $arr['area_id']) {
                $arr['area_name'] = $al['area_name'];
            } elseif ($al['area_id'] == $arr['circle_id']) {
                $arr['circle_name'] = $al['area_name'];
            }
        }
        foreach ($catList as $cl) {
            if ($cl['value'] == $arr['cat_fid']) {
                $arr['cat_fname'] = $cl['text'];
                foreach ($cl['children'] as $ch) {
                    if ($ch['value'] == $arr['cat_id']) {
                        $arr['cat_name'] = $ch['text'];
                        break;
                    }
                }
                break;
            }
        }
        $this->returnCode(0, $arr);
    }


    // 认领店铺
    public function storeModify()
    {
        $database_merchant_store = D('Merchant_store');
        $store_id = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
        if(empty($store_id)){
            $this->returnCode(1, null, L_('缺少认领对象！'));
        }
        $mer_id = $this->mer_id;
        $mer_info = D('Merchant')->get_info($this->mer_id);
        if(empty($mer_info)){
            $this->returnCode(1,'',L_('未获取到您的帐号信息，请重试~'));
        }

        if(empty($_POST['name'])){
            $this->returnCode(1, null, L_('店铺名称必填！'));
        }
        if(empty($_POST['phone'])){
            $this->returnCode(1, null, L_('联系电话必填！'));
        }
        if(empty($_POST['long_lat'])){
            $this->returnCode(1, null, L_('店铺经纬度必填！'));
        }
        if(empty($_POST['adress'])){
            $this->returnCode(1, null, L_('店铺地址必填！'));
        }
//         if(empty($_POST['permoney'])){
//             $this->returnCode(1, null, '人均消费必填！');
//         }
//         if(empty($_POST['feature'])){
//             $this->returnCode(1, null, '店铺特色必填！');
//         }
        // 			if(empty($_POST['trafficroute'])){
        // 				$this->returnCode(1, null, '交通路线必填！');
        // 			}
        if(empty($_POST['pic'])){
            $this->returnCode(1, null, L_('请至少上传一张图片'));
        }
        $_POST['pic_info'] = implode(';',$_POST['pic']);

        if(empty($_POST['txt_info'])){
            $this->returnCode(1, null, L_('请输入店铺描述信息'));
        }
        //判断关键词
        $keywords = trim($_POST['keywords']);
        if(!empty($keywords)){
            $tmp_key_arr = explode(' ',$keywords);
            $key_arr = array();
            foreach($tmp_key_arr as $value){
                if(!empty($value)){
                    array_push($key_arr,$value);
                }
            }
            if(count($key_arr)>5){
                $this->returnCode(1, null, L_('关键词最多5个。'));
            }
        }


//         $_POST['office_time'] = '';

//         $_POST['sort'] = intval($_POST['sort']);
        $long_lat = explode(',', $_POST['long_lat']);
        $_POST['long'] = $long_lat[0];
        $_POST['lat'] = $long_lat[1];

        if ($_POST['coordinate_type'] == 'wgs84') {
            list($_POST['long'], $_POST['lat']) = Coordinate::wgs84ToBd09($_POST['long'], $_POST['lat']);
        }

        $_POST['open_1'] = isset($_POST['open_1']) && $_POST['open_1'] ? $_POST['open_1'] : '00:00';
        $_POST['close_1'] = isset($_POST['close_1']) && $_POST['close_1'] ? $_POST['close_1'] : '00:00';
        $_POST['open_2'] = isset($_POST['open_2']) && $_POST['open_2'] ? $_POST['open_2'] : '00:00';
        $_POST['close_2'] = isset($_POST['close_2']) && $_POST['close_2'] ? $_POST['close_2'] : '00:00';
        $_POST['open_3'] = isset($_POST['open_3']) && $_POST['open_3'] ? $_POST['open_3'] : '00:00';
        $_POST['close_3'] = isset($_POST['close_3']) && $_POST['close_3'] ? $_POST['close_3'] : '00:00';
//         $area = D('Area')->cityMatching($long_lat[1], $long_lat[0]);
//         $_POST['province_id'] = isset($area['area_info']['province_id']) ? intval($area['area_info']['province_id']) : 0;
//         $_POST['city_id'] = isset($area['area_info']['city_id']) ? intval($area['area_info']['city_id']) : 0;
//         $_POST['area_id'] = isset($area['area_info']['area_id']) ? intval($area['area_info']['area_id']) : 0;

        if (empty($_POST['province_id']) || empty($_POST['city_id']) || empty($_POST['area_id'])) {
            $this->returnCode(1, null, L_('省市区必选。'));
        }
        $_POST['last_time'] = $_SERVER['REQUEST_TIME'];
        $_POST['circle_id'] = isset($_POST['circle_id']) ? intval($_POST['circle_id']) : 0;
        $_POST['discount_txt'] = '';
        $discount_type = isset($_POST['discount_type']) ? intval($_POST['discount_type']) : 0;
        if ($discount_type == 1) {
            $discount_percent = isset($_POST['discount_percent']) ? (intval($_POST['discount_percent'] * 10) / 10) : 0;
            if ($discount_percent > 0 && $discount_percent < 10) {
                if($this->config['open_extra_price']==1){
                    $_POST['discount_txt'] = serialize(array('discount_type' => $discount_type, 'discount_percent' => $discount_percent,'discount_limit'=>$_POST['discount_limit'],'discount_limit_percent'=>$_POST['discount_limit_percent']));
                }else{
                    $_POST['discount_txt'] = serialize(array('discount_type' => $discount_type, 'discount_percent' => $discount_percent));
                }
            } elseif ($discount_percent < 0 || $discount_percent > 10) {
                $this->returnCode(1, null, L_('折扣率必须在0~10之间的数。'));
            }
        } elseif ($discount_type == 2) {
            $condition_price = isset($_POST['condition_price']) ? (intval($_POST['condition_price'] * 100) / 100) : 0;
            $minus_price = isset($_POST['minus_price']) ? (intval($_POST['minus_price'] * 100) / 100) : 0;
            if ($condition_price < 0 || $minus_price < 0 || $minus_price > $condition_price) {
                $this->returnCode(1, null, L_('满减的填写不正确，必须都是大于0且满足的金额要大于减免金额。'));
            }
            if ($condition_price > 0 && $minus_price > 0 && $minus_price <= $condition_price) {
                $_POST['discount_txt'] = serialize(array('discount_type' => $discount_type, 'condition_price' => $condition_price, 'minus_price' => $minus_price));
            }
        }

        $_POST['store_type'] = isset($_POST['store_type']) ? intval($_POST['store_type']) : 1;
        $_POST['vip_discount_type'] = isset($_POST['vip_discount_type']) ? intval($_POST['vip_discount_type']) : 0;
        unset($_POST['store_id']);
        $ismain = intval($_POST['ismain']);
        if($ismain==1){
            $database_merchant_store->where(array('mer_id'=>$mer_id))->save(array('ismain'=>0));
        }
        $condition_claim_store = array(
            'store_id' => $store_id,
            'is_claim' => 1
        );
        $store = $database_merchant_store->where($condition_claim_store)->find();
        if ($store) {
            $_POST['mer_id'] = $mer_id;
            if($this->config['store_verify']){
                $_POST['status'] = $this->merchant['issign'] ? '1' :'2';
            }else{
                $_POST['status'] = '1';
            }
            $store_save_id = $database_merchant_store->where($condition_claim_store)->data($_POST)->save();
            if ($store_save_id) {
                $data_keywords['third_id'] = $store_id;
                $data_keywords['third_type'] = 'Merchant_store';
                $database_keywords = D('Keywords');
                $database_keywords->where($data_keywords)->delete();
                //判断关键词
                if(!empty($key_arr)){
                    foreach($key_arr as $value){
                        $data_keywords['keyword'] = $value;
                        $database_keywords->data($data_keywords)->add();
                    }
                }
                $this->returnCode(0);
            }else {
                $this->returnCode(1, null, L_('添加失败！请重试~'));
            }
        }else {
            $this->returnCode(1, null, L_('认领店铺不存在或者当前不能被认领'));
        }
    }

    // 注册商家时候默认认领对应店铺
    private function mer_reg_claim_store($store_id, $mer_id) {
        $database_merchant_store = D('Merchant_store');
        $mer_info = D('Merchant')->get_info($mer_id);
        $condition_claim_store = array(
            'store_id' => $store_id,
            'is_claim' => 1,
            'claim_status' => 1
        );
        $store = $database_merchant_store->where($condition_claim_store)->find();
        if ($store) {
            $data['last_time'] = $_SERVER['REQUEST_TIME'];
            $data['mer_id'] = $mer_id;
            if($this->config['store_verify']){
                $data['status'] = $mer_info['issign'] ? '1' :'2';
            }else{
                $data['status'] = '1';
            }
            $data['discount_txt'] = '';
            $data['claim_time'] = time();
            $store_id = $database_merchant_store->where($condition_claim_store)->data($data)->save();
            if ($store_id) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }


    /**商家推荐商家批发佣金功能*/
    // 获取现在佣金数
    public function my_spread_merchant_money() {
        $mer_id = intval($this->merchant['mer_id']);
        $where['income'] = 1;
        $where['mer_id'] = $mer_id;
        $where['type'] = 'market_spread';
        $all_money = M('Merchant_money_list')->where($where)->sum('money');
        if (empty($all_money)) {
            $all_money = 0;
        }
        $logo = file_domain() . $this->merchant['logo'];
        $this->returnCode(0,array('all_money'=>$all_money, 'logo' => $logo,'name' =>	$this->merchant['name']));
    }
    // 获取推广码
    public function my_mer_spread_code(){

        if(empty($this->merchant['mer_code'])){
            $mer_code = $this->make_code();
            M('Merchant')->where(array('mer_id'=>$this->merchant['mer_id']))->setField('mer_code',$mer_code);
        }else{
            $mer_code = $this->merchant['mer_code'];
        }
        $this->returnCode(0,array('mer_code'=>$mer_code));
    }
    // 生成推广码 改为纯数字暂定为 100000-999999  记得对应注册商家的位置添加对应判断-pc和App端
    private function make_code()
    {
        $str = mt_rand(100000,999999);
        if(!M('Merchant')->where(array('mer_spread_code'=>$str))->find()){
            return $str;
        }else{
            return  $this->make_code();
        }
    }
    // 获取自身推荐的商户
    public function spread_merchant_list(){
        $_GET['page'] = $page =  $_POST['page'];
        $mer_id = $this->merchant['mer_id'];
        $database_merchant = D('Merchant');
        $merchant_info = D('Merchant')->get_info($mer_id);
        if ($merchant_info['mer_code']) {
            $condition_merchant['mer_spread_code'] = $merchant_info['mer_code'];
            if ($_POST['keyword']) {
                if ($_POST['searchtype'] == 'name') {
                    $condition_merchant['name'] = array('like', '%'.trim($_POST['keyword']).'%');
                } elseif($_POST['searchtype'] == 'mer_id') {
                    $condition_merchant['mer_id'] = trim($_POST['keyword']);
                }
            }
            $count_merchant = $database_merchant->where($condition_merchant)->count();
            import('@.ORG.merchant_page');
            $p = new Page($count_merchant, 10);
            $nowPage = $p->nowPage;
            $totalPage = $p->totalPage;
            if ($page > $totalPage) {
                $merchant_list = array();
            } else {
                $merchant_list = $database_merchant->field(true)->where($condition_merchant)->order('mer_id DESC')->limit($p->firstRow.','.$p->listRows)->select();

                if ($merchant_list) {
                    foreach ($merchant_list as &$v) {
                        $where['spread_mer_id'] = $v['mer_id'];
                        $where['mer_id'] = $mer_id;
                        $where['order_type'] = 'market_spread';
                        $all_spread_money = M('Merchant_recommend_merchant_record')->where($where)->sum('money');
                        $v['all_spread_money'] = $all_spread_money>0?$all_spread_money:0;
                        $v['spread_time'] = date('Y-m-d H:i:s', $v['reg_time']);
                    }
                } else {
                    $merchant_list = array();
                }
            }

            $arr['mer_info'] = $merchant_info;
            $arr['merchant_list'] = $merchant_list;
            $arr['nowPage'] = $nowPage;
            $arr['page_num'] = $totalPage;
            $arr['count'] = $count_merchant;

            $this->returnCode(0,$arr);
        }
    }

    // 获取推广佣金
    public function spread_merchant_money(){
        $_GET['page'] = $page =  $_POST['page'];
        $mer_id = $this->merchant['mer_id'];
        $database_merchant = D('Merchant');
        $database_merchant_money_list = M('Merchant_money_list');
        $merchant_info = $database_merchant->get_info($mer_id);
        if ($merchant_info['mer_code']) {
            $mer_id = intval($this->merchant['mer_id']);
            $where['income'] = 1;
            $where['mer_id'] = $mer_id;
            $where['type'] = 'market_spread';
            $count_merchant = $database_merchant_money_list->where($where)->count();
            import('@.ORG.merchant_page');
            $p = new Page($count_merchant, 10);
            $nowPage = $p->nowPage;
            $totalPage = $p->totalPage;
            if ($page > $totalPage) {
                $merchant_list = array();
            } else {
                $merchant_list = $database_merchant_money_list->field(true)->where($where)->order('use_time DESC, mer_id DESC')->limit($p->firstRow.','.$p->listRows)->select();

                if ($merchant_list) {
                    foreach ($merchant_list as &$v) {
                        $v['use_time'] = date('Y-m-d H:i:s', $v['use_time']);
                    }
                } else {
                    $merchant_list = array();
                }
            }

            $arr['mer_info'] = $merchant_info;
            $arr['merchant_list'] = $merchant_list;
            $arr['nowPage'] = $nowPage;
            $arr['page_num'] = $totalPage;
            $arr['count'] = $count_merchant;

            $this->returnCode(0,$arr);
        }
    }


    /**商家批发相关功能*/

    // 获取商家自身的进货订单
    public function my_buy_wholesale_order()
    {
        $mer_id = $this->merchant['mer_id'];
        $status = $_POST['status']?$_POST['status']:-1; // -1 查询全部  -2 查询待支付(为了考虑兼容旧数据 0 不做状态查询) 1:支付成，2：发货成功，3：收货成功，4:已退款，10：下单成功
        $data = array(
            'mer_id' => $mer_id,
            'status' => $status
        );
        $d_market_order = D('Market_order');
        $_GET['page'] = $_POST['page'];
        $result = $d_market_order->getList($data, 'buy');
        if ($_POST['page'] && $_POST['page']>$result['totalPage']) {
            $result['orders'] = array();
        }

        // 分别计算商家批发的所有订单数，待支付数，待发货，待收货数量
        $where_num = array(
            'mer_id' => $mer_id
        );
        $all_num = M('Market_order')->where($where_num)->count();
        $where_num['status']=10;
        $no_pay_num = M('Market_order')->where($where_num)->count();
        $where_no_send_num = [
            'mer_id' => $mer_id,
            'type' => 0
        ];
        $where_no_send_num['_string'] = "(status=1 AND pay_type!='offline') OR (status=1 AND offline_status=1 AND pay_type='offline')";
        $no_send_num = M('Market_order')->where($where_no_send_num)->count();
        $where_num['status']=2;
        $where_num['type']=0;
        $send_num = M('Market_order')->where($where_num)->count();

        $result['all_num'] = $all_num;
        $result['no_pay_num'] = $no_pay_num;
        $result['no_send_num'] = $no_send_num;
        $result['send_num'] = $send_num;
        $result['send_num'] = $send_num;
        $config = array(
            'Currency_txt' => $this->config['Currency_txt'],
            'Currency_symbol' => $this->config['Currency_symbol'],
        );
        $result['config'] = $config;


        $this->returnCode(0, $result);
    }

    // 获取商家自身的销售订单
    public function my_sell_wholesale_order()
    {
        $mer_id = $this->merchant['mer_id'];
        $goods_id = $_POST['goods_id']?$_POST['goods_id']:0;
        $status = $_POST['status']?$_POST['status']:-1; // -1 查询全部  -2 查询待支付(为了考虑兼容旧数据 0 不做状态查询) 1:支付成，2：发货成功，3：收货成功，4:已退款，10：下单成功
        $data = array(
            'mer_id' => $mer_id,
            'status' => $status,
            'goods_id' => $goods_id
        );
        $d_market_order = D('Market_order');
        $_GET['page'] = $_POST['page'];
        $result = $d_market_order->getList($data, 'sell');
        if ($_POST['page'] && $_POST['page']>$result['totalPage']) {
            $result['orders'] = array();
        }

        // 分别计算商家批发的所有订单数，待支付数，待发货，待收货数量
        $where_num = array(
            'sell_mer_id' => $mer_id
        );
        $where_no_send_num = array(
            'sell_mer_id' => $mer_id
        );
        if ($goods_id>0) {
            $where_num['goods_id'] = $goods_id;
            $where_no_send_num['goods_id'] = $goods_id;
        }
        $all_num = M('Market_order')->where($where_num)->count();
        $where_num['status']=10;
        $no_pay_num = M('Market_order')->where($where_num)->count();
        $where_no_send_num['_string'] = "(status=1 AND pay_type!='offline') OR (status=1 AND offline_status=1 AND pay_type='offline')";
        $no_send_num = M('Market_order')->where($where_no_send_num)->count();
        $where_num['status']=2;
        $send_num = M('Market_order')->where($where_num)->count();

        $result['all_num'] = $all_num;
        $result['no_pay_num'] = $no_pay_num;
        $result['no_send_num'] = $no_send_num;
        $result['send_num'] = $send_num;
        // 配置项信息
        $config = array(
            'Currency_txt' => $this->config['Currency_txt'],
            'Currency_symbol' => $this->config['Currency_symbol'],
        );
        $result['config'] = $config;


        $this->returnCode(0, $result);
    }

    // 获取详细信息
    public function market_detail() {
        $order_id	=	I('order_id');
        if(empty($order_id)){
            $this->returnCode('20140025');
        }
        $type = I('type');
        if (!in_array($type, array('buy', 'sell'))) {
            $this->error(L_('信息来源不正确'));
        }
        $databaseMarketOrder = D('Market_order');
        $where = array('order_id' => $order_id);
        $mer_id = $this->merchant['mer_id'];
        if ($type == 'buy') {
            $where['mer_id'] = $mer_id;
        } else {
            $where['sell_mer_id'] = $mer_id;
        }
        $now_order = $databaseMarketOrder->field(true)->where($where)->find();

        if (empty($now_order)) {
            $this->returnCode('20130006');
        }
        if(!empty($now_order['status']) && $now_order['status']>=1){
            $now_order['paytypestr']	=	L_('已支付');
        }else{
            $now_order['paytypestr'] = L_('未支付');
        }

        // 如果允许自提获取自提点信息
        $databaseMarketGoods = D('Market_goods');
        $where = array('goods_id' => $now_order['goods_id']);
        $marketGoods = $databaseMarketGoods->field(true)->where($where)->find();
        if ($marketGoods['pick_info'] && $now_order['is_pick_in_store']>0) {
            $pick_info = explode(',', $marketGoods['pick_info']);
            $pick_list = D('Pick_address')->get_pick_addr_by_merid($marketGoods['mer_id'], true);
            $pick_address = array();
            $pick_msg = array();
            foreach ($pick_list as $val) {
                if (in_array($val['pick_addr_id'], $pick_info)) {
                    unset($val['pwd']);
                    $pick_address[] = $val;
                }
                if ($now_order['pick_id'] && $now_order['pick_id']==$val['pick_addr_id']) {
                    $pick_msg = $val;
                }
            }
            $arr['pick_msg'] = $pick_msg;
            $arr['pick_address'] = $pick_address;
        }
        $now_order['min_unit'] = $marketGoods['min_unit'];
        $now_order['min_num'] = $marketGoods['min_num'];
        $now_order['stock_num'] = $marketGoods['stock_num'];

        if(!empty($now_order['image'])){
            $goods_image_class = new goods_image();
            $tmp_pic_arr = explode(';', $now_order['image']);
            foreach ($tmp_pic_arr as $key => $value) {
                $stripos_result = stripos($value,'/upload/');
                if ($stripos_result !== false) {
                    $now_order['pic'] = replace_file_domain($value);
                } else {
                    $now_order['pic'] || $now_order['pic'] = $goods_image_class->get_image_by_path($value, 's');
                }
            }
        }
        $return = D('Market_goods')->format_spec_value($now_order['spec_value'], $now_order['goods_id']);
        $now_order['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
        $now_order['list'] = isset($return['list']) ? $return['list'] : '';
        $now_order['discount_info'] = json_decode($now_order['discount_info'], true);
        if ($now_order['express_id']>0) {
            $now_order['express_name'] = D('Express')->get_express($now_order['express_id']);
            $now_order['code'] = isset($now_order['express_name']['code']) ? $now_order['express_name']['code'] : '';
            $now_order['express_name'] = isset($now_order['express_name']['name']) ? $now_order['express_name']['name'] : '';
        } else {
            $now_order['express_name'] = '';
        }

        if($now_order['express_number'] && $this->config['kdniao_appkey']){
            $now_order['kdniao_url'] = $this->config['site_url'] . '/wap.php?c=Mall&a=express_new&type=' . $now_order['code'] . '&postid=' . $now_order['express_number'];
        }else{
            $now_order['kdniao_url'] = '';
        }

        if ('buy'==$type) {
            $now_order['callbackurl'] = $this->config['site_url'] . "/packapp/merchant/my_buy_wholesale_order.html";
        } else {
            $now_order['callbackurl'] = $this->config['site_url'] . "/packapp/merchant/my_sell_wholesale_order.html";
        }

        if ($type == 'buy') {
            if ($merchant = M('Merchant')->field('name, phone')->where(array('mer_id' => $now_order['sell_mer_id']))->find()) {
                $now_order['merchant_name'] = $merchant['name'];
                $now_order['merchant_phone'] = $merchant['phone'];
            }
            if ($store = M('Merchant_store')->field('name, phone')->where(array('store_id' => $now_order['sell_store_id']))->find()) {
                $now_order['store_name'] = $store['name'];
                $now_order['store_phone'] = $store['phone'];
            }
        } else {
            if ($merchant = M('Merchant')->field('name, phone')->where(array('mer_id' => $now_order['mer_id']))->find()) {
                $now_order['merchant_name'] = $merchant['name'];
                $now_order['merchant_phone'] = $merchant['phone'];
            }
        }
        $userData['username'] = $now_order['username'];
        $userData['userphone'] = $now_order['userphone'];
        $userData['address'] = $now_order['address'];
        $userData['desc'] = $now_order['desc'];
        $userData['express_id'] = $now_order['express_id'];
        $userData['express_name'] = $now_order['express_name'];
        $userData['express_number'] = $now_order['express_number'];
        $userData['sell_note'] = $now_order['sell_note'];
        $userData['create_time'] = $now_order['create_time'];
        $userData['pay_time'] = $now_order['pay_time'];
        $userData['send_time'] = $now_order['send_time'];
        $userData['pull_time'] = $now_order['pull_time'];
        switch ($now_order['status']) {
            case 1:
                if ($now_order['pay_type'] == 'offline' && $now_order['offline_status']==0) {
                    $now_order['status_txt'] = '<span style="color:red">'.L_('线下支付').L_('（未支付）').'</span>';
                } elseif ($now_order['pay_type'] == 'offline' && $now_order['offline_status']==1) {
                    $now_order['status_txt'] = '<span style="color:green">'.L_('线下支付').L_('（已支付）').'</span>';
                } elseif ($now_order['is_pick_in_store'] == 0 && $now_order['pick_id']) {
                    $now_order['status_txt'] = '<span style="color:green">'.L_('待自提').'</span>';
                } else {
                    $now_order['status_txt'] = '<span style="color:green">'.L_('已支付待发货').'</span>';
                }
                break;
            case 2:
                $now_order['status_txt'] = '<span style="color:green">'.L_('已发货').'</span>';
                break;
            case 3:
                if ($now_order['is_pick_in_store'] == 0) {
                    $now_order['status_txt'] = '<span style="color:green">'.L_('已自提').'</span>';
                } else {
                    $now_order['status_txt'] = '<span style="color:green">'.L_('已收货').'</span>';
                }
                break;
            case 4:
                $now_order['status_txt'] = '<span style="color:red">'.L_('已退款').'</span>';
                break;
            case 10:
                $now_order['status_txt'] = '<span style="color:red">'.L_('未支付').'</span>';
                break;
        }
        $now_order['type'] = $type;

        // 统一处理下时间
        if ($now_order['create_time']>0) {
            $now_order['create_time'] = date('Y-m-d H:i:s',$now_order['create_time']);
        }
        if ($now_order['pay_time']>0) {
            $now_order['pay_time'] = date('Y-m-d H:i:s',$now_order['pay_time']);
        }
        if ($now_order['send_time']>0) {
            $now_order['send_time'] = date('Y-m-d H:i:s',$now_order['send_time']);
        }
        if ($now_order['pull_time']>0) {
            $now_order['pull_time'] = date('Y-m-d H:i:s',$now_order['pull_time']);
        }

        if ($now_order['money']>0) {
            $total_money = $now_order['money'];
        } elseif ($now_order['total_price']) {
            $total_money = $now_order['total_price'];
        } else {
            $total_money = 0;
        }
        if ($now_order['total_express_fee']>0) {
            $total_money += $now_order['total_express_fee'];
        }
        $now_order['total_money'] = $total_money;

        $arr['now_order'] = $now_order;
        $arr['userData'] = $userData;
        // 配置项信息
        $config = array(
            'Currency_txt' => $this->config['Currency_txt'],
            'Currency_symbol' => $this->config['Currency_symbol'],
        );
        $arr['config'] = $config;



        $is_vip = false;
        if ($this->merchant['type_id']>0) {
            $merchant_type = M('Merchant_type')->where(array('type_id'=>$this->merchant['type_id']))->find();
            $is_vip = (1==$merchant_type['is_vip'])?true:false;
        }
        $arr['is_vip']=$is_vip;
        $this->returnCode(0,$arr);
    }

    // 获取配送快递相关信息
    public function market_express_list() {;
        $order_id = intval($_POST['order_id']);
        $mer_id = $this->merchant['mer_id'];
        $databaseMarketOrder = D('Market_order');
        $marketOrder = $databaseMarketOrder->field(true)->where(array('order_id' => $order_id, 'sell_mer_id' => $mer_id))->find();
        $arr['now_order'] = $marketOrder;
        $express_list = D('Express')->get_express_list();
        $arr['express_list'] = $express_list;
        $this->returnCode(0,$arr);
    }


    // 确认线下收钱
    public function offline_get_money() {
        $order_id = intval($_POST['order_id']);
        $mer_id = $this->merchant['mer_id'];
        $databaseMarketOrder = D('Market_order');
        if ($marketOrder = $databaseMarketOrder->field(true)->where(array('order_id' => $order_id, 'sell_mer_id' => $mer_id))->find()) {
            if ($marketOrder['pay_type'] == 'offline' && $marketOrder['offline_status'] == 1) {
                $this->returnCode(1001,array(),L_('此单线下已经确认收钱！'));
                exit;
            } elseif ($marketOrder['pay_type'] != 'offline') {
                $this->returnCode(1001,array(),L_('此单非线下支付，无法确认收钱！'));
                exit;
            }

            $set_data = array(
                'offline_status' => 1,
                'last_time' => time()
            );
            $save_id = $databaseMarketOrder->field(true)->where(array('order_id' => $order_id, 'sell_mer_id' => $mer_id))->data($set_data)->save();
            if ($save_id) {
                if($marketOrder['group_id']<=0) {     //不是拼团的订单 且是线下支付， 线下支付 实际付款确认后 开始分佣
                    $merchant = M('Merchant')->where(array('mer_id' => $mer_id))->find();
                    // 支付成功 查看是否存在推广者， 添加推广者分佣  添加开关控制
                    if ($this->config['merchant_recommend_wholesale_switch'] == 1 && $merchant['mer_spread_code'] && $this->config['merchant_recommend_spread_ratio']>0) {
                        $spread_merchant = M('Merchant')->where(array('mer_code' => $merchant['mer_spread_code'], 'mer' => array('neq', $mer_id)))->find();
                        if ($spread_merchant) {
                            $merchant_recommend_spread_ratio = floatval($this->config['merchant_recommend_spread_ratio'] * 0.01);
                            // 计算分佣金额
                            $spread_money = $marketOrder['money'] * $merchant_recommend_spread_ratio;
                            $spread_money = round($spread_money, 2); // 分佣金额

                            $desc = L_('您推广的X1商户在批发市场消费X2,您获得分佣',array('X1'=>$merchant['name'],'X2'=>$marketOrder['money'].$this->config['Currency_txt']));
                            $order_info['money'] = $spread_money;
                            $order_info['order_type'] = 'market_spread';
                            $order_info['mer_id'] = $spread_merchant['mer_id'];
                            $order_info['order_id'] = $marketOrder['order_id'];
                            $order_info['desc'] = $desc;
                            // 处理数量
                            $order_info['num'] = $marketOrder['num'];
                            D('Merchant_money_list')->add_money($order_info);
                            // 同步记录到记录表格中
                            $order_info['spread_mer_id'] = $merchant['mer_id'];
                            $order_info['spread_mer_name'] = $merchant['name'];
                            $order_info['spread_time'] = $merchant['reg_time'];
                            $order_info['add_time'] = time();
                            M('Merchant_recommend_merchant_record')->data($order_info)->add();
                        }
                    }
                }

                // 减少库存
                $return = $databaseMarketOrder->format_spec_value($marketOrder['spec_value'], $marketOrder['goods_id'], $order_id);
                $marketOrder['list'] = isset($return['list']) ? $return['list'] : '';
                $databaseMarketGoods = D('Market_goods');
                $where = array('goods_id' => $marketOrder['goods_id']);
                $marketGoods = $databaseMarketGoods->field(true)->where($where)->find();
                $marketGoodsChageData = array();
                if ($marketOrder['list']) {
                    $return = $databaseMarketGoods->format_spec_value($marketGoods['spec_value'], $marketGoods['goods_id']);
                    $marketGoods['list'] = isset($return['list']) ? $return['list'] : '';

                    $spec_array = explode('#', $marketGoods['spec_value']);

                    foreach ($marketOrder['list'] as $index => $row) {
                        $target_spec_value_array = array();
                        foreach ($spec_array as $str) {
                            $row_array = explode('|', $str);
                            $i = str_replace(':', '_', $row_array[0]);
                            if ($i == $index) {
                                $numArray = explode(':', $row_array[1]);
                                if ($numArray[3]!=-1) {
                                    $numArray[3] = max(0, ($numArray[3] - $row['stock_num']));
                                }
                                $row_array[1] = implode(':', $numArray);
                            }
                            $target_spec_value_array[] = implode('|', $row_array);
                        }
                        $spec_array = $target_spec_value_array;


                        if (isset($marketGoods['list'][$index])) {
                            if ($row['stock_num'] > $marketGoods['list'][$index]['stock_num']) {
                                $this->error(L_('购买的商品库存不足'), U('buy', array('order_id' => $marketOrder['order_id'], 'goods_id' => $marketOrder['goods_id'])));
                                exit();
                            } elseif ($row['stock_num'] < $marketGoods['list'][$index]['min_num']) {
                                $this->error(L_('购买的商品的数量不足最小批发量'), U('buy', array('order_id' => $marketOrder['order_id'], 'goods_id' => $marketOrder['goods_id'])));
                                exit();
                            }
                        } else {
                            $this->error(L_('购买的商品已经没有这种规格了'), U('buy', array('order_id' => $marketOrder['order_id'], 'goods_id' => $marketOrder['goods_id'])));
                            exit();
                        }
                    }
                    $marketGoodsChageData['spec_value'] = implode('#', $spec_array);
                } elseif ($marketOrder['num'] > $marketGoods['stock_num']) {
                    $this->error(L_('购买的商品库存不足'), U('buy', array('order_id' => $marketOrder['order_id'], 'goods_id' => $marketOrder['goods_id'])));
                    exit();
                } elseif ($marketOrder['num'] < $marketGoods['min_num']) {
                    $this->error(L_('购买的商品的数量不足最小批发量'), U('buy', array('order_id' => $marketOrder['order_id'], 'goods_id' => $marketOrder['goods_id'])));
                    exit();
                }

                $marketGoodsChageData['stock_num'] = max(0, ($marketGoods['stock_num'] - $marketOrder['num']));
                $marketGoodsChageData['sell_count'] = $marketGoods['sell_count'] + $marketOrder['num'];


                //更改批发市场中商品的库存
                $databaseMarketGoods->where(array('goods_id' => $marketOrder['goods_id']))->save($marketGoodsChageData);


                $this->returnCode(0,array(),L_('确认成功'));
            } else {
                $this->returnCode(1001,array(),L_('确认收钱失败，请重试~'));
            }
        } else {
            $this->returnCode(1001,array(),L_('错误的订单信息'));
        }
    }


    //发货
    public function market_send()
    {
        $order_id = intval($_POST['order_id']);
        $mer_id = $this->merchant['mer_id'];
        $databaseMarketOrder = D('Market_order');
        if ($marketOrder = $databaseMarketOrder->field(true)->where(array('order_id' => $order_id, 'sell_mer_id' => $mer_id))->find()) {
            if ($marketOrder['status'] != 2 && $marketOrder['status'] != 1) {
                $this->returnCode(1001,array(),L_('此单不能发货！'));
                exit;
            }
            if($marketOrder['group_id']>0){
                $team_status=M('Market_group_team')->where(array('id'=>$marketOrder['team_id']))->getfield('status');
                if($team_status==0){
                    $this->returnCode(1001,array(),L_('该拼团订单尚未成团不能发货！'));
                }elseif($team_status==2){
                    $this->returnCode(1001,array(),L_('该拼团订单拼团失败，不能发货！'));
                }
            }
            if (IS_POST) {
                $express_id = isset($_POST['express_id']) ? intval($_POST['express_id']) : 0;
                $express_number = isset($_POST['express_number']) ? htmlspecialchars(trim($_POST['express_number'])) : 0;
                $sell_note = isset($_POST['sell_note']) ? htmlspecialchars(trim($_POST['sell_note'])) : '';
                $express = D('Express')->get_express($express_id);
                if (empty($express)) {
                    $this->returnCode(1001,array(),L_('请选择正确的快递公司'));
                    exit;
                }
                if (empty($express_number)) {
                    $this->returnCode(1001,array(),L_('请选择填写快递单号'));
                    exit;
                }
                $data['express_id'] = $express_id;
                $data['express_number'] = $express_number;
                $data['sell_note'] = $sell_note;
                $data['send_time'] = time();
                $data['status'] = 2;
                if ($databaseMarketOrder->where(array('order_id' => $order_id, 'sell_mer_id' => $mer_id))->save($data)) {
                    $sms_data = array('mer_id' => $marketOrder['mer_id'], 'store_id' => 0, 'type' => 'market');

                    $buymerchant = M('Merchant')->where(array('mer_id' => $marketOrder['mer_id']))->find();
                    $sms_data['uid'] = 0;
                    $sms_data['mobile'] = $buymerchant['phone'];
                    $sms_data['sendto'] = 'merchant';
                    $sms_data['content'] = L_('您在X1批发了X2共X3订单号：X4对方已经发货。请您注意查看并处理!',array('X1'=>$this->merchant['name'],'X2'=>$marketOrder['name'],'X3'=>$marketOrder['num'] . $marketOrder['unit'],'X4'=>$marketOrder['order_id']));
                    Sms::sendSms($sms_data);

                    $this->returnCode(0,array(),L_('发货成功'));
                } else {
                    $this->returnCode(1001,array(),L_('发货失败'));
                }
            } else {
                $this->returnCode(1001,array(),L_('没有任何提交信息'));
            }
        } else {
            $this->returnCode(1001,array(),L_('错误的订单信息'));
        }
    }

    //收货
    public function market_pull()
    {
        $order_id = intval($_POST['order_id']);
        $databaseMarketOrder = D('Market_order');
        $where = array('order_id' => $order_id, 'mer_id' => $this->merchant['mer_id']);
        if ($marketOrder = $databaseMarketOrder->field(true)->where($where)->find()) {
            if ($marketOrder['status'] != 2) {
                $this->returnCode(1001,array(),L_('此单暂时不能收货！'));
            } else {
                if ($databaseMarketOrder->where($where)->save(array('status' => 3, 'last_time' => time(), 'pull_time' => time()))) {
                    //对方增加余额
                    $total_express_fee = $marketOrder['total_express_fee'] > 0 ? floatval($marketOrder['total_express_fee']) : 0;
                    $money = floatval($marketOrder['money']);
                    $total_money = $money + $total_express_fee;
                    $data = array();
                    $data['order_type'] = 'market';
                    $data['desc'] = L_('批发市场的商品【').$marketOrder['name'].L_('】卖出时增加商家余额');
                    $data['mer_id'] = $marketOrder['sell_mer_id'];
                    $data['money'] = $total_money;
                    $data['order_id'] = $marketOrder['order_id'];
                    $data['total_money'] = $total_money;
                    // D('Merchant_money_list')->add_money($data);
                    D('SystemBill')->bill_method(0,$data);


                    $sellmerchant = M('Merchant')->where(array('mer_id' => $marketOrder['sell_mer_id']))->find();
                    $sms_data = array('mer_id' => $sellmerchant['mer_id'], 'store_id' => 0, 'type' => 'market');
                    $sms_data['uid'] = 0;
                    $sms_data['mobile'] = $sellmerchant['phone'];
                    $sms_data['sendto'] = 'merchant';
                    $sms_data['content'] = L_('您发给X1的X2共X3对方已经收货，订单号：X4',array('X1'=>$this->merchant['name'],'X2'=>$marketOrder['name'],'X3'=>$marketOrder['num'] . $marketOrder['unit'],'X4'=>$marketOrder['order_id']));
                    Sms::sendSms($sms_data);


                    $this->returnCode(0,$marketOrder,L_('收货成功'));
                } else {
                    $this->returnCode(1001,array(),L_('收货失败，稍后重试'));
                }
            }
        } else {
            $this->returnCode(1001,array(),L_('订单信息错误'));
        }
    }

    //自提
    public function market_pick()
    {
        $order_id = intval($_POST['order_id']);
        $databaseMarketOrder = D('Market_order');
        $where = array('order_id' => $order_id);
        if ($marketOrder = $databaseMarketOrder->field(true)->where($where)->find()) {
            if ($marketOrder['mer_id'] != $this->merchant['mer_id'] && $marketOrder['sell_mer_id'] != $this->merchant['mer_id']){
                $this->returnCode(1001,array(),L_('您不能进行此操作！'));
            } elseif ($marketOrder['status'] != 1 || $marketOrder['is_pick_in_store'] != 0) {
                $this->returnCode(1001,array(),L_('此单当前不能自提！'));
            } else {
                if ($databaseMarketOrder->where($where)->save(array('status' => 3, 'last_time' => time(), 'pull_time' => time(), 'pick_mer_id' => $this->merchant['mer_id']))) {
                    //对方增加余额
                    $total_express_fee = $marketOrder['total_express_fee'] > 0 ? floatval($marketOrder['total_express_fee']) : 0;
                    $money = floatval($marketOrder['money']);
                    $total_money = $money + $total_express_fee;
                    $data = array();
                    $data['order_type'] = 'market';
                    $data['desc'] = L_('批发市场的商品【').$marketOrder['name'].L_('】卖出时增加商家余额');
                    $data['mer_id'] = $marketOrder['sell_mer_id'];
                    $data['money'] = $marketOrder['money'];
                    $data['order_id'] = $marketOrder['order_id'];
                    $data['total_money'] = $total_money;
                    // D('Merchant_money_list')->add_money($data);
                    D('SystemBill')->bill_method(0,$data);


                    $sellmerchant = M('Merchant')->where(array('mer_id' => $marketOrder['sell_mer_id']))->find();
                    $sms_data = array('mer_id' => $sellmerchant['mer_id'], 'store_id' => 0, 'type' => 'market');
                    $sms_data['uid'] = 0;
                    $sms_data['mobile'] = $sellmerchant['phone'];
                    $sms_data['sendto'] = 'merchant';
                    $sms_data['content'] = L_('X1的X2共X3对方已经自提，订单号：X4',array('X1'=>$this->merchant['name'],'X2'=>$marketOrder['name'],'X3'=>$marketOrder['num'] . $marketOrder['unit'],'X4'=>$marketOrder['order_id']));
                    Sms::sendSms($sms_data);


                    $this->returnCode(0,$marketOrder,L_('自提成功'));
                } else {
                    $this->returnCode(1001,array(),L_('自提失败，稍后重试'));
                }
            }
        } else {
            $this->returnCode(1001,array(),L_('订单信息错误'));
        }
    }

    // 支付信息
    public function buy_info()
    {
        $goodsId = intval($_POST['goods_id']);
        $order_id = intval($_POST['order_id']);
        $pick_id = $_POST['pick_id'];
        $databaseMarketGoods = D('Market_goods');
        $where = array('goods_id' => $goodsId);
        $marketGoods = $databaseMarketGoods->field(true)->where($where)->find();
        if(empty($marketGoods)){
            $this->error(L_('商品不存在！'));
        }
        $databaseMarketOrder = D('Market_order');
        $where = array('order_id' => $order_id);
        $mer_id = $this->merchant['mer_id'];
        $where['mer_id'] = $mer_id;

        $now_order = $databaseMarketOrder->field(true)->where($where)->find();

        if (empty($now_order)) {
            $this->returnCode('20130006');
        }
        if($now_order['sell_mer_id']==$this->merchant['mer_id']){
            $this->error(L_('自己不能批发自己发布的商品！'));
        }
        if (!$pick_id) {
            $pick_id = $now_order['pick_id'];
        }
        $express_template_info = array();
        // 如果允许自提获取自提点信息
        if ($now_order['is_pick_in_store']==0 && $marketGoods['pick_info']) {
            $pick_info = explode(',', $marketGoods['pick_info']);
            $pick_list = D('Pick_address')->get_pick_addr_by_merid($marketGoods['mer_id'], true);
            $pick_address = array();
            $pick_msg = array();
            foreach ($pick_list as $val) {
                if (in_array($val['pick_addr_id'], $pick_info)) {
                    unset($val['pwd']);
                    $pick_address[] = $val;
                }
                if ($pick_id && $pick_id==$val['pick_addr_id']) {
                    $pick_msg = $val;
                }
            }
            if (!$pick_id) {
                $pick_msg = reset($pick_address);
                $arr['pick_id'] = 0;
            } else {
                $arr['pick_id'] = $pick_id;
            }
            $arr['pick_msg'] = $pick_msg;
            $arr['pick_address'] = $pick_address;
        }

        $address_id = isset($_POST['address_id']) ? intval($_POST['address_id']) : 0;

        $merchant_address_temp = D('Merchant_adress')->field(true)->where(array('mer_id' => $this->merchant['mer_id']))->order('`default` DESC,`adress_id` DESC')->select();
        $arr['address_count'] = count($merchant_address_temp);
        $merchant_address = '';
        foreach ($merchant_address_temp as $address) {
            if ($address_id == $address['adress_id']) {
                $merchant_address = $address;
                break;
            }
            if(empty($address_id) && $address['default']){
                $merchant_address = $address;
                break;
            }
        }
        if (empty($merchant_address) && $merchant_address_temp) {
            $merchant_address = reset($merchant_address_temp);
        }
        if ($merchant_address) {
            $province = D('Area')->get_area_by_areaId($merchant_address['province'],false);
            $merchant_address['province_txt'] = $province['area_name'];

            $city = D('Area')->get_area_by_areaId($merchant_address['city'],false);
            $merchant_address['city_txt'] = $city['area_name'];

            $area = D('Area')->get_area_by_areaId($merchant_address['area'],false);
            $merchant_address['area_txt'] = $area['area_name'];
        }
        $arr['merchant_address']=$merchant_address?$merchant_address:'';
        $arr['address_id']=$address_id?$address_id:'';
        if ($merchant_address['province']) {
            $province_id = $merchant_address['province'];
        } else {
            $province_id = $now_order['province_id'];
        }

        if(!empty($now_order['image'])){
            $goods_image_class = new goods_image();
            $tmp_pic_arr = explode(';', $now_order['image']);
            foreach ($tmp_pic_arr as $key => $value) {
                $now_order['pic'] || $now_order['pic'] = $goods_image_class->get_image_by_path($value, 's');
                $now_order['pic_arr'][$key]['title'] = $value;
                $now_order['pic_arr'][$key]['url'] = $goods_image_class->get_image_by_path($value, 's');
            }
        }
        $return = $databaseMarketGoods->format_spec_value($now_order['spec_value'], $now_order['goods_id']);
        $now_order['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
        $now_order['list'] = isset($return['list']) ? $return['list'] : '';
        $now_order['discount_info'] = json_decode($now_order['discount_info'], true);


        $now_order['buy_num'] = $now_order['num'];

        $totalMoney = 0;
        $group_totalMoney = 0;
        $totalCount = 0;


        $is_vip = false;
        if ($this->merchant['type_id']>0) {
            $merchant_type = M('Merchant_type')->where(array('type_id'=>$this->merchant['type_id']))->find();
            $is_vip = (1==$merchant_type['is_vip'])?true:false;
        }


        if ($now_order['list']) {
            $buy_nums = array();
            foreach ($now_order['list']as &$vo) {
                $totalCount += $vo['min_num'];
                if ($is_vip && $vo['vip_wholesale_price']>0) {
                    $totalMoney += $vo['stock_num'] * $vo['vip_wholesale_price'];
                    $vo['o_price'] = $vo['price'];
                    $vo['price'] = $vo['vip_wholesale_price'];
                } else {
                    $totalMoney += $vo['stock_num'] * $vo['price'];
                }
                if($now_order['is_group']==1){    //拼团活动商品
                    $group_totalMoney += $vo['stock_num'] * $vo['group_team_price'];
                }
                $buy_nums[$vo['index']] = $vo['stock_num'];
            }
            if ($province_id>0 && $now_order['is_pick_in_store']>0) {
                $msg = D('Market_order')->market_express_template_fee($province_id, $buy_nums, $now_order, $now_order['is_pick_in_store']);
                if ($msg['total_express_fee']>0) {
                    $now_order['total_express_fee'] = $msg['total_express_fee'];
                }
                if ($msg && $msg['express_msg']) {
                    $express_template_info = $msg['express_msg'];
                }
            }
        } else {
            if ($is_vip && $now_order['vip_wholesale_price']>0) {
                $totalMoney = $now_order['buy_num'] * $marketGoods['vip_wholesale_price'];
                $now_order['o_price'] = $now_order['price'];
                $now_order['price'] = $now_order['vip_wholesale_price'];
            } else {
                $totalMoney = $now_order['buy_num'] * $now_order['price'];
            }
            $totalCount = $now_order['buy_num'];
            if($now_order['is_group']==1){    //拼团活动商品
                $group_totalMoney = $now_order['buy_num'] * $now_order['group_team_price'];
            }
            $buy_nums = $now_order['buy_num'];
            if ($province_id>0 && $now_order['is_pick_in_store']>0) {
                $msg = D('Market_order')->market_express_template_fee($province_id, $buy_nums, $now_order, $now_order['is_pick_in_store']);
                if ($msg['total_express_fee']>0) {
                    $now_order['total_express_fee'] = $msg['total_express_fee'];
                }
                if ($msg && $msg['express_msg']) {
                    $express_template_info = $msg['express_msg'];
                }
            }
        }

        $discount_info = null;
        if ($now_order['discount_info'] && !$now_order['discount_info']['num'] && is_array($now_order['discount_info'])) {
            foreach ($now_order['discount_info'] as $discount) {
                if ($discount['num'] <= $totalCount) {
                    if ($discount_info && $discount_info['discount']) {
                        if ($discount_info['discount'] > $discount['discount']) {
                            $discount_info = $discount;
                        }
                    } elseif($discount) {
                        $discount_info = $discount;
                    }
                }
            }
        } elseif ($now_order['discount_info']) {
            $discount = $now_order['discount_info'];
            if ($discount['num'] <= $totalCount) {
                if ($discount_info && $discount_info['discount']) {
                    if ($discount_info['discount'] > $discount['discount']) {
                        $discount_info = $discount;
                    }
                } elseif ($discount) {
                    $discount_info = $discount;
                }
            }
        }
        if ($discount_info) {
            $totalMoney= round($totalMoney* $discount_info['discount'] * 0.1,2);
        }
        $now_order['totalMoney'] = $totalMoney;
        $now_order['group_totalMoney'] = $group_totalMoney;

        // 处理系统对应每个商家设置的 批发商品时候的优惠折扣 设置大于零小于100才起效
        $wholesale_discount = $this->merchant['wholesale_discount']>0 ? $this->merchant['wholesale_discount'] : 100;

        if ($wholesale_discount>0 && $wholesale_discount<100) {
            $now_order['wholesale_discount'] = $wholesale_discount/10; // 计算折扣
            $now_order['discount_totalMoney'] = round($totalMoney*$wholesale_discount*0.01, 2);
        }

        $arr['now_order'] = $now_order;
        $arr['team_id'] = $now_order['team_id'];
        // 配置项信息
        $config = array(
            'Currency_txt' => $this->config['Currency_txt'],
            'Currency_symbol' => $this->config['Currency_symbol'],
        );
        $arr['config'] = $config;


        // 处理下配送相关信息显示
        if ($express_template_info) {
            $express_txt = '';
            $first_weight = floatval($express_template_info['first_weight']);
            $first_freight = floatval($express_template_info['first_freight']);
            $add_weight = floatval($express_template_info['add_weight']);
            $add_freight = floatval($express_template_info['add_freight']);
            $freight = floatval($express_template_info['freight']);
            $full_money = floatval($express_template_info['full_money']);
            if ($express_template_info['freight_type']==1 && $express_template_info['total_num']>0) {
                $express_txt .= "<div style='color: red;'>货物件数：" . $express_template_info['total_num'] . "</div>";

                $express_txt .= "<div style='color: red;'>运费标准：";
                $first = false;
                $get = false;
                // 起步价5元（5件以内），每增加1件运费增加0.33元
                if ($first_weight>0 && $first_freight) {
                    $express_txt .= "起步价{$first_weight}{$this->config['Currency_txt']}（{$first_freight}件以内）";
                    $first = true;
                    $get = true;
                }
                if ($add_weight>0 && $add_freight) {
                    if ($first) {
                        $express_txt .= "，每增加{$add_weight}件运费增加{$add_freight}{$this->config['Currency_txt']}";
                    } else {
                        $express_txt .= "每增加{$add_weight}件运费增加{$add_freight}{$this->config['Currency_txt']}";
                    }
                    $get = true;
                }
                if (!$get && $freight) {
                    $express_txt .= "{$freight}{$this->config['Currency_txt']}";
                    $get = true;
                }
                if ($get && $full_money>0) {
                    $express_txt .= "，满{$full_money}{$this->config['Currency_txt']}免运费";
                }
                $express_txt .= "</div>";

            } elseif ($express_template_info['freight_type']==2 && $express_template_info['total_num']>0) {
                $express_txt .= "<div style='color: red;'>货物总重：" . $express_template_info['total_num'] . "公斤</div>";
                $express_txt .= "<div style='color: red;'>运费标准：";
                $first = false;
                $get = false;
                // 起步价5元（2公斤以内），每增加1公斤运费增加0.55元
                if ($first_weight>0 && $first_freight) {
                    $express_txt .= "起步价{$first_weight}{$this->config['Currency_txt']}（{$first_freight}公斤以内）";
                    $first = true;
                    $get = true;
                }
                if ($add_weight>0 && $add_freight) {
                    if ($first) {
                        $express_txt .= "，每增加{$add_weight}公斤运费增加{$add_freight}{$this->config['Currency_txt']}";
                    } else {
                        $express_txt .= "每增加{$add_weight}公斤运费增加{$add_freight}{$this->config['Currency_txt']}";
                    }
                    $get = true;
                }
                if (!$get && $freight) {
                    $express_txt .= "{$freight}{$this->config['Currency_txt']}";
                    $get = true;
                }
                if ($get && $full_money>0) {
                    $express_txt .= "，满{$full_money}{$this->config['Currency_txt']}免运费";
                }
                $express_txt .= "</div>";
            } elseif ($express_template_info['freight_type']==3 && $express_template_info['total_num']>0) {
                $express_txt .= "<div style='color: red;'>货物总体积：" . $express_template_info['total_num'] . "立方米</div>";
                $express_txt .= "<div style='color: red;'>运费标准：";
                $get = false;
                if ($freight) {
                    $express_txt .= "{$freight}{$this->config['Currency_txt']}每立方米";
                    $get = true;
                }
                if ($get && $full_money>0) {
                    $express_txt .= "，满{$full_money}{$this->config['Currency_txt']}免运费";
                }
                $express_txt .= "</div>";
            }
            if ($express_template_info['type']==2 && $express_txt) {
                // 注：货物配送到当地物流园，需要自提，如需送货上门请联系物流园。
                $express_txt .= "<div style='color: red;'>注：货物配送到当地物流园，需要自提，如需送货上门请联系物流园。</div>";
            }
            if ($express_txt) {
                $arr['express_txt'] = $express_txt;
            }
        }


        $this->returnCode(0,$arr);
    }

    // 获取金额
    public function getTotalPrice()
    {
        $is_pick_in_store = isset($_POST['is_pick_in_store']) ?  $_POST['is_pick_in_store'] : 0;
        $goodsId = intval($_POST['goods_id']);
        $order_id = intval($_POST['order_id']);
        $databaseMarketGoods = D('Market_goods');
        $where = array('goods_id' => $goodsId);
        $marketGoods = $databaseMarketGoods->field(true)->where($where)->find();
        if(empty($marketGoods)){
            $this->returnCode(1001,array(),L_('商品不存在！'));
        }
        $is_vip = false;
        if ($this->merchant['type_id']>0) {
            $merchant_type = M('Merchant_type')->where(array('type_id'=>$this->merchant['type_id']))->find();
            $is_vip = (1==$merchant_type['is_vip'])?true:false;
        }
        $data = array(
            'error' => false
        );
        $express_template_info = array();
        if ($order_id>0) {
            $databaseMarketOrder = D('Market_order');
            $where = array('order_id' => $order_id);
            $mer_id = $this->merchant['mer_id'];
            $where['mer_id'] = $mer_id;

            $now_order = $databaseMarketOrder->field(true)->where($where)->find();

            if (empty($now_order)) {
                $this->returnCode('20130006');
            }
            if($now_order['sell_mer_id']==$this->merchant['mer_id']){
                $this->error(L_('自己不能批发自己发布的商品！'));
            }

            $return = $databaseMarketGoods->format_spec_value($now_order['spec_value'], $now_order['goods_id']);
            $now_order['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
            $now_order['list'] = isset($return['list']) ? $return['list'] : '';
            $now_order['discount_info'] = json_decode($now_order['discount_info'], true);
            $now_order['buy_num'] = $now_order['num'];
            $is_pick_in_store = $now_order['is_pick_in_store'];

            /*是否是拼团商品*/
            $is_group=I('is_group',0,'intval');
            $is_detail=I('is_detail',0,'intval');
            $group_totalPrice=0;
            if (IS_POST) {
                if ($now_order['spec_list']) {
                    $buy_nums = $_POST['buy_nums'];

                    $totalNum = 0;
                    $totalPrice = 0;
                    $spec_array = explode('#', $now_order['spec_value']);
                    $target_spec_value_array = array();
                    foreach ($spec_array as $str) {
                        $row_array = explode('|', $str);
                        $i = 'id_' . str_replace(':', '_id_', $row_array[0]);
                        $i_num = str_replace(':', '_id_', $row_array[0]);
                        $numArray = explode(':', $row_array[1]);
                        if (!$buy_nums[$i] && $buy_nums[$i_num]) {
                            $buy_nums[$i] = $buy_nums[$i_num];
                        }
                        if ($buy_nums[$i] >= $numArray[2] && ($buy_nums[$i] <= $numArray[3] || $numArray[3] == -1 || $is_detail==1)) {
                            $totalNum += $buy_nums[$i];
                            if($is_group==1){
                                $group_totalPrice += $buy_nums[$i] * $numArray[5];
                            }
                            if ($is_vip && $numArray[7]>0) {
                                $totalPrice += $buy_nums[$i] * $numArray[7];
                            } else {
                                $totalPrice += $buy_nums[$i] * $numArray[1];
                            }
                        }
                    }
                    if ($totalNum <= 0) {
                        $totalPrice = 0;
                    }
                    if ($_POST['province_id']>0 && $is_pick_in_store>0) {
                        $province_id = $_POST['province_id'];
                        $msg = D('Market_order')->market_express_template_fee($province_id, $buy_nums, $now_order, $is_pick_in_store);
                        if ($msg['total_express_fee']>0) {
                            $data['total_express_fee'] = $msg['total_express_fee'];
                        }
                        if ($msg && $msg['express_msg']) {
                            $express_template_info = $msg['express_msg'];
                        }
                    }
                } else {
                    $totalNum = isset($_POST['buy_num']) ? intval($_POST['buy_num']) : intval($now_order['num']);
                    if ($totalNum > $marketGoods['stock_num'] || $totalNum < $marketGoods['min_num']) {
                        $this->error(L_('数量错误请刷新重试！'));
                    } else {
                        if($is_group==1){
                            $group_totalPrice = $totalNum * $now_order['group_team_price'];
                        }
                        $totalPrice = $totalNum * $now_order['price'];
                    }
                    if ($_POST['province_id']>0 && $is_pick_in_store>0) {
                        $province_id = $_POST['province_id'];
                        $msg = D('Market_order')->market_express_template_fee($province_id, $totalNum, $now_order, $is_pick_in_store);
                        if ($msg['total_express_fee']>0) {
                            $data['total_express_fee'] = $msg['total_express_fee'];
                        }
                        if ($msg && $msg['express_msg']) {
                            $express_template_info = $msg['express_msg'];
                        }
                    }
                }

                $discount_info = null;
                if ($now_order['discount_info'] && !$now_order['discount_info']['num'] && is_array($now_order['discount_info'])) {
                    foreach ($now_order['discount_info'] as $discount) {
                        if ($discount['num'] <= $totalNum) {
                            if ($discount_info && $discount_info['discount']) {
                                if ($discount_info['discount'] > $discount['discount']) {
                                    $discount_info = $discount;
                                }
                            } elseif($discount) {
                                $discount_info = $discount;
                            }
                        }
                    }
                } elseif ($now_order['discount_info']) {
                    $discount = $now_order['discount_info'];
                    if ($discount['num'] <= $totalNum) {
                        if ($discount_info && $discount_info['discount']) {
                            if ($discount_info['discount'] > $discount['discount']) {
                                $discount_info = $discount;
                            }
                        } elseif ($discount) {
                            $discount_info = $discount;
                        }
                    }
                }
                $money = $totalPrice;
                // 计算折扣
                if ($discount_info && $discount_info['discount']>0 && $discount_info['discount']<10) {
                    $money = $totalPrice * $discount_info['discount'] * 0.1;
                }
                $data['totalMoney'] = round($money, 2);
                //拼团总价
                $data['group_totalMoney'] = $group_totalPrice;

                // 处理系统对应每个商家设置的 批发商品时候的优惠折扣 设置大于零小于100才起效
                $wholesale_discount = $this->merchant['wholesale_discount']>0 ? $this->merchant['wholesale_discount'] : 100;

                if ($wholesale_discount>0 && $wholesale_discount<100) {
                    $data['wholesale_discount'] = $wholesale_discount/10; // 计算折扣
                    $data['discount_totalMoney'] = round($money*$wholesale_discount*0.01, 2);
                }
            }
        } else {
            $return = $databaseMarketGoods->format_spec_value($marketGoods['spec_value'], $marketGoods['goods_id']);
            $marketGoods['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
            $marketGoods['list'] = isset($return['list']) ? $return['list'] : '';
            $marketGoods['discount_info'] = json_decode($marketGoods['discount_info'], true);
            $marketGoods['username'] = $this->merchant['name'];
            $marketGoods['userphone'] = $this->merchant['phone'];
            $marketGoods['address'] = '';
            $marketGoods['buy_num'] = $marketGoods['min_num'];

            /*是否是拼团商品*/
            $is_group=I('is_group',0,'intval');
            $group_totalPrice=0;
            if (IS_POST) {
                if ($marketGoods['spec_list']) {
                    $buy_nums = $_POST['buy_nums'];

                    $totalNum = 0;
                    $totalPrice = 0;
                    $spec_array = explode('#', $marketGoods['spec_value']);
                    $target_spec_value_array = array();
                    foreach ($spec_array as $str) {
                        $row_array = explode('|', $str);
                        $i = 'id_' . str_replace(':', '_id_', $row_array[0]);
                        $i_num = str_replace(':', '_id_', $row_array[0]);
                        $numArray = explode(':', $row_array[1]);
                        if (!$buy_nums[$i] && $buy_nums[$i_num]) {
                            $buy_nums[$i] = $buy_nums[$i_num];
                        }
                        if ($buy_nums[$i] >= $numArray[2] && ($buy_nums[$i] <= $numArray[3] || $numArray[3] == -1)) {
                            $totalNum += $buy_nums[$i];
                            if($is_group==1){
                                $group_totalPrice += $buy_nums[$i] * $numArray[5];
                            }
                            if ($is_vip && $numArray[7]>0) {
                                $totalPrice += $buy_nums[$i] * $numArray[7];
                            } else {
                                $totalPrice += $buy_nums[$i] * $numArray[1];
                            }
                        }
                    }
                    if ($totalNum <= 0) {
                        $totalPrice = 0;
                    }
                    if ($_POST['province_id']>0 && $is_pick_in_store>0) {
                        $province_id = $_POST['province_id'];
                        $msg = D('Market_order')->market_express_template_fee($province_id, $buy_nums, $marketGoods, $is_pick_in_store);
                        if ($msg['total_express_fee']>0) {
                            $data['total_express_fee'] = $msg['total_express_fee'];
                        }
                        if ($msg && $msg['express_msg']) {
                            $express_template_info = $msg['express_msg'];
                        }
                    }
                } else {
                    $totalNum = isset($_POST['buy_num']) ? intval($_POST['buy_num']) : $marketGoods['min_num'];
                    if ($totalNum > $marketGoods['stock_num'] || $totalNum < $marketGoods['min_num']) {
                        $totalPrice = 0;
                    } else {
                        if($is_group==1){
                            $group_totalPrice = $totalNum * $marketGoods['group_team_price'];
                        }
                        if ($is_vip && $marketGoods['vip_wholesale_price']>0) {
                            $totalPrice = $totalNum * $marketGoods['vip_wholesale_price'];
                            $marketGoods['o_price'] = $marketGoods['price'];
                            $marketGoods['price'] = $marketGoods['vip_wholesale_price'];
                        } else {
                            $totalPrice = $totalNum * $marketGoods['price'];
                        }
                    }
                    if ($_POST['province_id']>0 && $is_pick_in_store>0) {
                        $province_id = $_POST['province_id'];
                        $msg = D('Market_order')->market_express_template_fee($province_id, $totalNum, $marketGoods, $is_pick_in_store);
                        if ($msg['total_express_fee']>0) {
                            $data['total_express_fee'] = $msg['total_express_fee'];
                        }
                        if ($msg && $msg['express_msg']) {
                            $express_template_info = $msg['express_msg'];
                        }
                    }
                }

                $discount_info = null;
                foreach ($marketGoods['discount_info'] as $discount) {
                    if ($discount['num'] <= $totalNum) {
                        if ($discount_info) {
                            if ($discount_info['discount'] > $discount['discount']) {
                                $discount_info = $discount;
                            }
                        } else {
                            $discount_info = $discount;
                        }
                    }
                }
                $money = $totalPrice;
                if ($discount_info) {
                    $money = $totalPrice * $discount_info['discount'] * 0.1;
                }

                $data['totalMoney'] = round($money, 2);
                //拼团总价
                $data['group_totalMoney']=$group_totalPrice;

                // 处理系统对应每个商家设置的 批发商品时候的优惠折扣 设置大于零小于100才起效
                $wholesale_discount = $this->merchant['wholesale_discount']>0 ? $this->merchant['wholesale_discount'] : 100;

                if ($wholesale_discount>0 && $wholesale_discount<100) {
                    $data['wholesale_discount'] = $wholesale_discount/10; // 计算折扣
                    $data['discount_totalMoney'] = round($money*$wholesale_discount*0.01, 2);
                }
            }
        }
        // 处理下配送相关信息显示
        if ($express_template_info) {
            $express_txt = '';
            $first_weight = floatval($express_template_info['first_weight']);
            $first_freight = floatval($express_template_info['first_freight']);
            $add_weight = floatval($express_template_info['add_weight']);
            $add_freight = floatval($express_template_info['add_freight']);
            $freight = floatval($express_template_info['freight']);
            $full_money = floatval($express_template_info['full_money']);
            if ($express_template_info['freight_type']==1 && $express_template_info['total_num']>0) {
                $express_txt .= "<div style='color: red;'>货物件数：" . $express_template_info['total_num'] . "</div>";

                $express_txt .= "<div style='color: red;'>运费标准：";
                $first = false;
                $get = false;
                // 起步价5元（5件以内），每增加1件运费增加0.33元
                if ($first_weight>0 && $first_freight) {
                    $express_txt .= "起步价{$first_freight}{$this->config['Currency_txt']}（{$first_weight}件以内）";
                    $first = true;
                    $get = true;
                }
                if ($add_weight>0 && $add_freight) {
                    if ($first) {
                        $express_txt .= "，每增加{$add_weight}件运费增加{$add_freight}{$this->config['Currency_txt']}";
                    } else {
                        $express_txt .= "每增加{$add_weight}件运费增加{$add_freight}{$this->config['Currency_txt']}";
                    }
                    $get = true;
                }
                if (!$get && $freight) {
                    $express_txt .= "{$freight}{$this->config['Currency_txt']}";
                    $get = true;
                }
                if ($get && $full_money>0) {
                    $express_txt .= "，满{$full_money}{$this->config['Currency_txt']}免运费";
                }
                $express_txt .= "</div>";

            } elseif ($express_template_info['freight_type']==2 && $express_template_info['total_num']>0) {
                $express_txt .= "<div style='color: red;'>货物总重：" . $express_template_info['total_num'] . "公斤</div>";
                $express_txt .= "<div style='color: red;'>运费标准：";
                $first = false;
                $get = false;
                // 起步价5元（2公斤以内），每增加1公斤运费增加0.55元
                if ($first_weight>0 && $first_freight) {
                    $express_txt .= "起步价{$first_freight}{$this->config['Currency_txt']}（{$first_weight}公斤以内）";
                    $first = true;
                    $get = true;
                }
                if ($add_weight>0 && $add_freight) {
                    if ($first) {
                        $express_txt .= "，每增加{$add_weight}公斤运费增加{$add_freight}{$this->config['Currency_txt']}";
                    } else {
                        $express_txt .= "每增加{$add_weight}公斤运费增加{$add_freight}{$this->config['Currency_txt']}";
                    }
                    $get = true;
                }
                if (!$get && $freight) {
                    $express_txt .= "{$freight}{$this->config['Currency_txt']}";
                    $get = true;
                }
                if ($get && $full_money>0) {
                    $express_txt .= "，满{$full_money}{$this->config['Currency_txt']}免运费";
                }
                $express_txt .= "</div>";
            } elseif ($express_template_info['freight_type']==3 && $express_template_info['total_num']>0) {
                $express_txt .= "<div style='color: red;'>货物总体积：" . $express_template_info['total_num'] . "立方米</div>";
                $express_txt .= "<div style='color: red;'>运费标准：";
                $get = false;
                if ($freight) {
                    $express_txt .= "{$freight}{$this->config['Currency_txt']}每立方米";
                    $get = true;
                }
                if ($get && $full_money>0) {
                    $express_txt .= "，满{$full_money}{$this->config['Currency_txt']}免运费";
                }
                $express_txt .= "</div>";
            }
            if ($express_template_info['type']==2 && $express_txt) {
                // 注：货物配送到当地物流园，需要自提，如需送货上门请联系物流园。
                $express_txt .= "<div style='color: red;'>注：货物配送到当地物流园，需要自提，如需送货上门请联系物流园。</div>";
            }
            if ($express_txt) {
                $data['express_txt'] = $express_txt;
            }
        }
        $this->returnCode(0,$data);
    }

    // 确定下单
    public function market_buy() {
        $goodsId = intval($_POST['goods_id']);
        $order_id = intval($_POST['order_id']);
        $is_pick_in_store = isset($_POST['is_pick_in_store']) ?  $_POST['is_pick_in_store'] : 0;
        $pick_id = isset($_POST['pick_id']) ? $_POST['pick_id'] : '';
        $databaseMarketGoods = D('Market_goods');
        $where = array('goods_id' => $goodsId);
        $marketGoods = $databaseMarketGoods->field(true)->where($where)->find();
        if(empty($marketGoods)){
            $this->returnCode(1001,array(),L_('商品不存在'));
        }
        $databaseMarketOrder = D('Market_order');
        $is_vip = false;
        $is_pick = false;
        $express = false;
        $logistics = false;
        if ($order_id>0) {
            $where = array('order_id' => $order_id);
            $mer_id = $this->merchant['mer_id'];
            $where['mer_id'] = $mer_id;

            $now_order = $databaseMarketOrder->field(true)->where($where)->find();
            $is_pick_in_store = $now_order['is_pick_in_store'];
            if (empty($now_order)) {
                $this->returnCode('20130006');
            }
            if($now_order['sell_mer_id']==$this->merchant['mer_id']){
                $this->returnCode(1001,array(),L_('自己不能批发自己发布的商品'));
            }
        }

        // 如果允许自提获取自提点信息
        if ($marketGoods['is_pick'] == 1 && $marketGoods['pick_info']) {
            $pick_info = explode(',', $marketGoods['pick_info']);
            $pick_list = D('Pick_address')->get_pick_addr_by_merid($marketGoods['mer_id'], true);
            $pick_address = array();
            $pick_msg = array();
            foreach ($pick_list as $val) {
                if (in_array($val['pick_addr_id'], $pick_info)) {
                    unset($val['pwd']);
                    $pick_address[] = $val;
                    if ($pick_id == $val['pick_addr_id']) {
                        $pick_msg = $val;
                    }
                }
            }
            if ($is_pick_in_store==0 && (empty($pick_msg) || !$pick_id)) {
                $this->returnCode(1001,array(),L_('请选择正确的自提点，请刷新重试！'));
            }
            if (empty($pick_address)) {
                $is_pick = false;
            } else {
                $is_pick = true;
            }
            $merchant = M('Merchant')->where(array('mer_id' => $this->merchant['mer_id']))->find();
            if (IS_POST && $pick_msg) {
                $_POST['username'] = $merchant['name'];
                $_POST['userphone'] = $pick_msg['phone'];
                $_POST['address'] = $pick_msg['area_info']['province'].' '.$pick_msg['area_info']['city'].' '.$pick_msg['area_info']['area'].' '.$pick_msg['name'];
                if (!empty($pick_msg['province'])) {
                    $_POST['province_id'] = $pick_msg['province'];
                }
                if (!empty($pick_msg['city'])) {
                    $_POST['city_id'] = $pick_msg['city'];
                }
                if (!empty($pick_msg['area'])) {
                    $_POST['area_id'] = $pick_msg['area'];
                }
            }
        }
        // 获取一下对应运费模板消息
        if ($now_order && $now_order['express_template_id'] > 0) {
            $template_msgs = D('Market_express_template_msg')->field(true)->where(array('tid' => $now_order['express_template_id']))->select();
            foreach ($template_msgs as $val) {
                if ($val['type']==1) {
                    $express = true;
                } elseif ($val['type']==2) {
                    $logistics = true;
                }
            }
        } elseif ($marketGoods && $marketGoods['express_template_id'] > 0) {
            $template_msgs = D('Market_express_template_msg')->field(true)->where(array('tid' => $marketGoods['express_template_id']))->select();
            foreach ($template_msgs as $val) {
                if ($val['type']==1) {
                    $express = true;
                } elseif ($val['type']==2) {
                    $logistics = true;
                }
            }
        }
        if ($marketGoods['is_pick'] == 1 && $marketGoods['pick_info'] && $is_pick_in_store==0 && !$is_pick) {
            $this->returnCode(1001,array(),L_('当前商品无法自提，请刷新重试！'));
        } elseif ($marketGoods['express_template_id'] > 0 && $is_pick_in_store==1 && !$express) {
            $this->returnCode(1001,array(),L_('当前商品无法使用快递，请刷新重试！'));
        } elseif ($marketGoods['express_template_id'] > 0 && $is_pick_in_store==2 && !$logistics) {
            $this->returnCode(1001,array(),L_('当前商品无法使用物流，请刷新重试！'));
        }

        if ($this->merchant['type_id']>0) {
            $merchant_type = M('Merchant_type')->where(array('type_id'=>$this->merchant['type_id']))->find();
            $is_vip = (1==$merchant_type['is_vip'])?true:false;
        }
        if ($order_id>0) {
            $return = $databaseMarketGoods->format_spec_value($now_order['spec_value'], $now_order['goods_id']);
        } else {

            $group_id=I('group_id',0,'intval');
            $team_id=I('team_id',0,'intval');
            if(IS_POST){
                if($group_id>0){
                    $result=$databaseMarketGoods->check_group_act($group_id,$team_id);
                    if($result['error']==1){
                        $this->error($result['msg']);
                    }
                    $marketGoods=$result;
                }
            }else{
                if($team_id>0){ //参团校验
                    $result=$databaseMarketGoods->check_join_group($marketGoods,$team_id,1,$this->merchant['mer_id']);
                    if($result['error']==1){
                        $this->error($result['msg']);
                    }
                    $marketGoods=$result['data'];
                }else{  //判断能否发起拼团
                    $marketGoods=$databaseMarketGoods->check_start_group($marketGoods);
                }
            }
            $now_order = $marketGoods;
            $return = $databaseMarketGoods->format_spec_value($marketGoods['spec_value'], $marketGoods['goods_id']);
        }

        $now_order['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
        $now_order['list'] = isset($return['list']) ? $return['list'] : '';
        $now_order['discount_info'] = json_decode($now_order['discount_info'], true);

        $now_order['buy_num'] = $now_order['num'];
        $totalMoney = 0;
        $group_totalMoney = 0;
        $totalCount = 0;


        if ($now_order['spec_list']) {
            foreach ($now_order['list']as &$vo) {
                $totalCount += $vo['min_num'];
                if ($is_vip && $vo['vip_wholesale_price']>0) {
                    $totalMoney += $vo['min_num'] * $vo['vip_wholesale_price'];
                    $vo['o_price'] = $vo['price'];
                    $vo['price'] = $vo['vip_wholesale_price'];
                } else {
                    $totalMoney += $vo['min_num'] * $vo['price'];
                }
                if($now_order['is_group']==1){    //拼团活动商品
                    $group_totalMoney += $vo['min_num'] * $vo['group_team_price'];
                }
            }
        } else {
            if ($is_vip && $now_order['vip_wholesale_price']>0) {
                $totalMoney = $now_order['buy_num'] * $marketGoods['vip_wholesale_price'];
                $now_order['o_price'] = $now_order['price'];
                $now_order['price'] = $now_order['vip_wholesale_price'];
            } else {
                $totalMoney = $now_order['buy_num'] * $now_order['price'];
            }
            $totalCount = $now_order['buy_num'];
            if($now_order['is_group']==1){    //拼团活动商品
                $group_totalMoney = $now_order['buy_num'] * $now_order['group_team_price'];
            }
        }


        $discount_info = null;
        if ($now_order['discount_info'] && !$now_order['discount_info']['num'] && is_array($now_order['discount_info'])) {
            foreach ($now_order['discount_info'] as $discount) {
                if ($discount['num'] <= $totalCount) {
                    if ($discount_info && $discount_info['discount']) {
                        if ($discount_info['discount'] > $discount['discount']) {
                            $discount_info = $discount;
                        }
                    } elseif($discount) {
                        $discount_info = $discount;
                    }
                }
            }
        } elseif ($now_order['discount_info']) {
            $discount = $now_order['discount_info'];
            if ($discount['num'] <= $totalCount) {
                if ($discount_info && $discount_info['discount']) {
                    if ($discount_info['discount'] > $discount['discount']) {
                        $discount_info = $discount;
                    }
                } elseif ($discount) {
                    $discount_info = $discount;
                }
            }
        }
        if ($discount_info) {
            $totalMoney= round($totalMoney* $discount_info['discount'] * 0.1,2);
        }
        $now_order['totalMoney'] = $totalMoney;
        $now_order['group_totalMoney'] = $group_totalMoney;

        // 处理系统对应每个商家设置的 批发商品时候的优惠折扣 设置大于零小于100才起效
        $wholesale_discount = $this->merchant['wholesale_discount']>0 ? $this->merchant['wholesale_discount'] : 100;

        if ($wholesale_discount>0 && $wholesale_discount<100) {
            $now_order['wholesale_discount'] = $wholesale_discount/10; // 计算折扣
            $now_order['discount_totalMoney'] = round($totalMoney*$wholesale_discount*0.01, 2);
        }


        if (IS_POST) {
            $username = isset($_POST['username']) ? htmlspecialchars(trim($_POST['username'])) : '';
            $userphone = isset($_POST['userphone']) ? htmlspecialchars(trim($_POST['userphone'])) : '';
            $address = isset($_POST['address']) ? htmlspecialchars(trim($_POST['address'])) : '';
            $desc = isset($_POST['desc']) ? htmlspecialchars(trim($_POST['desc'])) : '';
            $province_id = isset($_POST['province_id']) ? intval(trim($_POST['province_id'])) : '';
            $city_id = isset($_POST['city_id']) ? intval(trim($_POST['city_id'])) : '';
            $area_id = isset($_POST['area_id']) ? intval(trim($_POST['area_id'])) : '';
            if (($is_pick || $express || $logistics || (isset($_POST['is_pick_in_store']) && $_POST['is_pick_in_store']!=0)) && empty($username)) {
                $this->returnCode(1001,array(),L_('收货人不能为空！'));
                exit;
            }
            if (($is_pick || $express || $logistics || (isset($_POST['is_pick_in_store']) && $_POST['is_pick_in_store']!=0)) && empty($userphone)) {
                $this->returnCode(1001,array(),L_('联系电话不能为空！'));
                exit;
            }
            if (($is_pick || $express || $logistics || (isset($_POST['is_pick_in_store']) && $_POST['is_pick_in_store']!=0)) && empty($address)) {
                $this->returnCode(1001,array(),L_('收货地址不能为空！'));
                exit;
            }
            $marketOrder = array('mer_id' => $this->merchant['mer_id'], 'last_time' => time());
            $error_tips = '';
            $spec_price_arr = array();
            if ($now_order['spec_list']) {
                $buy_nums = $_POST['buy_nums'];

                $totalNum = 0;
                $totalPrice = 0;
                $spec_array = explode('#', $now_order['spec_value']);
                $target_spec_value_array = array();
                foreach ($spec_array as $str) {
                    $row_array = explode('|', $str);
                    $i = 'id_' . str_replace(':', '_id_', $row_array[0]);
                    $numArray = explode(':', $row_array[1]);
                    if ($buy_nums[$i] >= $numArray[2] && ($buy_nums[$i] <= $numArray[3] || $numArray[3] == -1)) {
                        $totalNum += $buy_nums[$i];
                        if($group_id>0){
                            $totalPrice += $buy_nums[$i] * $numArray[5];
                        }else{
                            if ($is_vip && $numArray[7]>0) {
                                $totalPrice += $buy_nums[$i] * $numArray[7];
                                $spec_price_arr[]=$numArray[7];
                            } else {
                                $totalPrice += $buy_nums[$i] * $numArray[1];
                                $spec_price_arr[]=$numArray[1];
                            }
                        }
                        //规格值ID:规格值ID:...:规格值ID|old_price:price:seckill_price:stock_num:cost_price|属性ID=可选值个数:属性ID=可选值个数:...:属性ID=可选值个数|商品编号#
                        $numArray[3] = $buy_nums[$i];
                        $numArray[2] = 0;
                        $row_array[1] = implode(':', $numArray);
                        $target_spec_value_array[] = implode('|', $row_array);
                    } elseif ($buy_nums[$i] > $numArray[3] && $numArray[3] != -1) {
                        $error_tips .= L_('库存不足').'<br/>';
                    }
                }

                if ($totalNum <= 0) {
                    $error_tips .= L_('您至少要批发一种规格且满足对应的批发条件').'<br/>';
                }

                if ($now_order['express_template_id'] > 0 && $province_id>0 && $is_pick_in_store>0) {
                    $msg = D('Market_order')->market_express_template_fee($province_id, $buy_nums, $now_order, $is_pick_in_store);
                    if ($msg['total_express_fee']>0) {
                        $marketOrder['total_express_fee'] = $msg['total_express_fee'];
                    }
                }
            } else {
                $totalNum = isset($_POST['buy_num']) ? intval($_POST['buy_num']) : $now_order['num'];
                if ($totalNum > $marketGoods['stock_num']) {
                    $error_tips .= L_('该商品库存不足').'<br/>';
                }
                if ($totalNum < $now_order['num']) {
                    $error_tips .= L_('一次至少要批发') . $now_order['num'] . $marketGoods['unit'] . '<br/>';
                }
                if($group_id>0){
                    $totalPrice = $totalNum * $now_order['group_team_price'];
                }else{
                    $totalPrice = $totalNum * $now_order['price'];
                }
                if ($now_order['express_template_id'] && $province_id>0 && $is_pick_in_store>0) {
                    $msg = D('Market_order')->market_express_template_fee($province_id, $totalNum, $now_order, $is_pick_in_store);
                    if ($msg['total_express_fee']>0) {
                        $marketOrder['total_express_fee'] = $msg['total_express_fee'];
                    }
                }
            }

            if (empty($error_tips)) {
                $discount_info = null;
                if ($now_order['discount_info'] && !$now_order['discount_info']['num'] && is_array($now_order['discount_info'])) {
                    foreach ($now_order['discount_info'] as $discount) {
                        if ($discount['num'] <= $totalCount) {
                            if ($discount_info && $discount_info['discount']) {
                                if ($discount_info['discount'] > $discount['discount']) {
                                    $discount_info = $discount;
                                }
                            } elseif($discount) {
                                $discount_info = $discount;
                            }
                        }
                    }
                } elseif ($now_order['discount_info']) {
                    $discount = $now_order['discount_info'];
                    if ($discount['num'] <= $totalCount) {
                        if ($discount_info && $discount_info['discount']) {
                            if ($discount_info['discount'] > $discount['discount']) {
                                $discount_info = $discount;
                            }
                        } elseif ($discount) {
                            $discount_info = $discount;
                        }
                    }
                }
                $actual_money = $totalPrice;
                if ($discount_info && $group_id<=0) {
                    $actual_money = round($totalPrice * $discount_info['discount'] * 0.1, 2);
                }

                // 处理系统对应每个商家设置的 批发商品时候的优惠折扣 设置大于零小于100才起效
                $wholesale_discount = $this->merchant['wholesale_discount']>0 ? $this->merchant['wholesale_discount'] : 100;

                if ($wholesale_discount>0 && $wholesale_discount<100) {
                    $money = round($actual_money*$wholesale_discount*0.01, 2);
                } else {
                    $money = $actual_money;
                }


                // 针对系统 处理系统对应每个商家设置的 批发商品时候的优惠折扣 对应记录 应付金额和折扣
                $marketOrder['actual_money'] = $actual_money;
                $marketOrder['system_discount_info'] = $wholesale_discount;

                // 地址相关信息
                $marketOrder['province_id'] = $province_id;
                $marketOrder['city_id'] = $city_id;
                $marketOrder['area_id'] = $area_id;
                // 配送类型
                $marketOrder['is_pick_in_store'] = $is_pick_in_store;
                $marketOrder['pick_id'] = $pick_id;

                $marketOrder['money'] = $money;
                $marketOrder['total_price'] = $totalPrice;
                $marketOrder['num'] = $totalNum;

                $min_price = $now_order['price'];
                if ($spec_price_arr) {
                    $spec_price = implode(',', $spec_price_arr);
                    $marketOrder['spec_price'] = $spec_price;
                    $min_price = min($spec_price_arr);
                    if (!$min_price) {
                        $min_price = $now_order['price'];
                    }
                }

                if($group_id>0){
                    $marketOrder['price'] = $now_order['group_team_price'];
                }else{
                    $marketOrder['price'] = $min_price;
                }
                $marketOrder['sell_mer_id'] = $marketGoods['mer_id'];
                if ($now_order['store_id'] > 0) {
                    $marketOrder['sell_store_id'] = $now_order['store_id'];
                }
                $marketOrder['status'] = 10;

                $marketOrder['goods_id'] = $now_order['goods_id'];
                $marketOrder['name'] = $now_order['name'];
                $marketOrder['number'] = $now_order['number'];
                $marketOrder['unit'] = $marketGoods['unit'];
                $marketOrder['image'] = $now_order['image'];
                $marketOrder['des'] = $now_order['des'];
                $marketOrder['is_properties'] = $now_order['is_properties'];
                $marketOrder['discount_info'] = $discount_info ? json_encode($discount_info) : '';

                $marketOrder['username'] = $username;
                $marketOrder['userphone'] = $userphone;
                $marketOrder['address'] = $address;
                $marketOrder['desc'] = $desc;
                $marketOrder['create_time'] = time();
                // 配送费相关依据字段
                $marketOrder['freight_type'] = $marketGoods['freight_type']>0?$marketGoods['freight_type']:1;
                $marketOrder['goods_volume'] = $marketGoods['goods_volume']>0?$marketGoods['goods_volume']:0;
                $marketOrder['goods_weight'] = $marketGoods['goods_weight']>0?$marketGoods['goods_weight']:0;
                $marketOrder['express_template_id'] = $marketGoods['express_template_id']>0?$marketGoods['express_template_id']:0;
                $marketOrder['express_fee'] = $marketGoods['express_fee']>0?$marketGoods['express_fee']:0;

                // 是不是享受的vip价格  0 不是  1 是
                $marketOrder['is_vip'] = $is_vip?1:0;

                $marketOrder['spec_value'] = $target_spec_value_array ? implode('#', $target_spec_value_array) : '';
                if($group_id>0){    //参团订单
                    $marketOrder['group_id']=$group_id;
                    if($team_id>0){
                        $marketOrder['team_id']=$team_id;
                        $marketOrder['group_type']=2;  //参团
                    }else{
                        $marketOrder['group_type']=1;  //发起拼团
                    }
                }
                if ($order_id>0) {
                    if ($order_info = M('Market_order')->where(array('order_id' => $order_id, 'goods_id' => $goodsId, 'mer_id' => $this->merchant['mer_id'], 'status' => 10))->save($marketOrder)) {
                        //删除已有的属性与规格的数据
                        $where['order_id'] = $order_id;
                        M('Market_order_properties')->where($where)->delete();
                        $spec_list = M('Market_order_spec')->field(true)->where($where)->select();
                        foreach ($spec_list as $spec) {
                            M('Market_order_spec_value')->where(array('sid' => $spec['id'], 'order_id' => $order_id))->delete();
                        }
                        M('Market_order_spec')->where($where)->delete();
                    }
                } else {
                    $order_id = M('Market_order')->add($marketOrder);
                }

                if ($order_id) {
                    if ($now_order['is_properties']) {
                        $properties = M('Market_goods_properties')->field(true)->where(array('goods_id' => $now_order['goods_id']))->select();
                        foreach ($properties as $pro_data) {
                            $pro_data['order_id'] = $order_id;
                            M('Market_order_properties')->add($pro_data);
                        }
                    }

                    if ($now_order['spec_value']) {
                        $spec_list = M('Market_goods_spec')->field(true)->where(array('goods_id' => $now_order['goods_id']))->select();
                        foreach ($spec_list as $spec) {
                            $spec['order_id'] = $order_id;
                            if (M('Market_order_spec')->add($spec)) {
                                $spec_value_list = M('Market_goods_spec_value')->field(true)->where(array('sid' => $spec['id']))->select();
                                foreach ($spec_value_list as $spec_value) {
                                    $spec_value['order_id'] = $order_id;
                                    M('Market_order_spec_value')->add($spec_value);
                                }
                            }
                        }
                    }
                    $this->returnCode(0,array('order_id' => $order_id));
                } else {
                    $this->returnCode(1001,array(),L_('下单失败！请重试！'));
                }
            } else {
                $this->returnCode(1001,array(),$error_tips);
            }
        } else {
            $this->returnCode(1001,array(),L_('错误的购买信息'));
        }
    }

    // 支付信息
    public function market_pay() {
        $order_id = intval($_POST['order_id']);
        $databaseMarketOrder = D('Market_order');
        $merchant = M('Merchant')->where(array('mer_id' => $this->merchant['mer_id']))->find();
        if($this->config['nmgzhcs_appid']){
            $merchant['money'] = 0;			// 不使用余额
        }
        $is_vip = false;
        if ($this->merchant['type_id']>0) {
            $merchant_type = M('Merchant_type')->where(array('type_id'=>$this->merchant['type_id']))->find();
            $is_vip = (1==$merchant_type['is_vip'])?true:false;
        }
        if ($marketOrder = $databaseMarketOrder->field(true)->where(array('order_id' => $order_id, 'mer_id' => $this->merchant['mer_id'], 'status' => 10))->find()) {
            if(!empty($marketOrder['image'])){
                $goods_image_class = new goods_image();
                $tmp_pic_arr = explode(';', $marketOrder['image']);
                foreach ($tmp_pic_arr as $key => $value) {
                    $marketOrder['pic_arr'][$key]['title'] = $value;
                    $marketOrder['pic_arr'][$key]['url'] = $goods_image_class->get_image_by_path($value, 's');
                    $marketOrder['pic'] || $marketOrder['pic'] = $marketOrder['pic_arr'][$key]['url'];
                }
            }

            $return = $databaseMarketOrder->format_spec_value($marketOrder['spec_value'], $marketOrder['goods_id'], $order_id);
            $marketOrder['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
            $marketOrder['list'] = isset($return['list']) ? $return['list'] : '';
            $marketOrder['discount_info'] = json_decode($marketOrder['discount_info'], true);


            if ($marketOrder['money']>0) {
                $total_money = $marketOrder['money'];
            } elseif ($marketOrder['total_price']) {
                $total_money = $marketOrder['total_price'];
            } else {
                $total_money = 0;
            }
            if ($marketOrder['total_express_fee']>0) {
                $total_money += $marketOrder['total_express_fee'];
            }
            $marketOrder['total_money'] = $total_money;
            $marketOrder['create_time'] = date('Y-m-d H:i:s', $marketOrder['create_time']);

            $arr['now_order'] = $marketOrder;
            $arr['merchant'] = $merchant;
            // 配置项信息
            $config = array(
                'Currency_txt' => $this->config['Currency_txt'],
                'Currency_symbol' => $this->config['Currency_symbol'],
            );
            $arr['config'] = $config;
            $arr['is_vip'] = $is_vip;
            $pay_method = D('Market_order')->pay_method();
            if(strpos($_SERVER['HTTP_USER_AGENT'],'MicroMessenger') !== false){
                $pay_method_all = D('Config')->get_pay_method(0,0);
                if ($pay_method_all['weixin']) {
                    $pay_method['weixin'] = $pay_method_all['weixin'];
                }
                unset($pay_method['weixinh5']);
            }
            $arr['merchant'] = $merchant;
            $arr['pay_method'] = $pay_method;
            $arr['openid'] = $_SESSION['openid'] ? $_SESSION['openid'] : '';
            $this->returnCode(0,$arr);
        } else {
            $this->returnCode(1001,array(),L_('订单已经支付'));
            exit();
        }
    }

    // 支付
    public function market_pay_success()
    {
        $order_id = intval($_POST['order_id']);
        $databaseMarketOrder = D('Market_order');
        $merchant = M('Merchant')->where(array('mer_id' => $this->merchant['mer_id']))->find();
        if($this->config['nmgzhcs_appid']){
            $merchant['money'] = 0;			// 不使用余额
        }
        // 购买总数
        $total_num = 0;
        $pay_type = $_POST['pay_type'] ? $_POST['pay_type'] : '';
        if ($marketOrder = $databaseMarketOrder->field(true)->where(array('order_id' => $order_id, 'mer_id' => $this->merchant['mer_id'], 'status' => 10))->find()) {

            $total_express_fee = $marketOrder['total_express_fee'] > 0 ? $marketOrder['total_express_fee'] : 0;
            $_total = $marketOrder['money'] + $total_express_fee;
            if ($pay_type == '' && $_total > $merchant['money']) {
                $money = floatval($_total - $merchant['money']);
                if (empty($money)||$money <0 ||!is_numeric($money)) {
                    $this->returnCode(1,'',L_('订单信息错误'));
                } else {
                    $this->returnCode(1,'',L_('您的余额不足，请前往商家后台最少充值').$money.$this->config['Currency_txt']);
                }
            }



            $return = $databaseMarketOrder->format_spec_value($marketOrder['spec_value'], $marketOrder['goods_id'], $order_id);
            $marketOrder['list'] = isset($return['list']) ? $return['list'] : '';


            $databaseMarketGoods = D('Market_goods');
            $where = array('goods_id' => $marketOrder['goods_id']);
            $marketGoods = $databaseMarketGoods->field(true)->where($where)->find();
            if(empty($marketGoods)){

                //批发市场是没有这条数据的时候删除没有支付的订单所有的数据
                $where['order_id'] = $order_id;
                M('Market_order_properties')->where($where)->delete();
                $spec_list = M('Market_order_spec')->field(true)->where($where)->select();
                foreach ($spec_list as $spec) {
                    M('Market_order_spec_value')->where(array('sid' => $spec['id'], 'order_id' => $order_id))->delete();
                }
                M('Market_order_spec')->where($where)->delete();

                $databaseMarketOrder->where(array('order_id' => $order_id, 'mer_id' => $this->merchant['mer_id'], 'status' => 10))->delete();

                $this->returnCode(0,array('error' => true, 'msg' =>L_('该商品在批发市场上已下架，不能购买了'),'jump' => 'market_index'));
            }

            $marketGoodsChageData = array();
            if ($marketOrder['list']) {
                $return = $databaseMarketGoods->format_spec_value($marketGoods['spec_value'], $marketGoods['goods_id']);
                $marketGoods['list'] = isset($return['list']) ? $return['list'] : '';

                $spec_array = explode('#', $marketGoods['spec_value']);

                foreach ($marketOrder['list'] as $index => $row) {
                    $target_spec_value_array = array();
                    foreach ($spec_array as $str) {
                        $row_array = explode('|', $str);
                        $i = str_replace(':', '_', $row_array[0]);
                        if ($i == $index) {
                            $numArray = explode(':', $row_array[1]);
                            if ($numArray[3] != -1) {
                                $numArray[3] = max(0, ($numArray[3] - $row['stock_num']));
                            }
                            $row_array[1] = implode(':', $numArray);
                        }
                        $target_spec_value_array[] = implode('|', $row_array);
                    }
                    $spec_array = $target_spec_value_array;


                    if (isset($marketGoods['list'][$index])) {
                        if ($marketGoods['list'][$index]['stock_num']!=-1 && $row['stock_num'] > $marketGoods['list'][$index]['stock_num']) {
                            $this->returnCode(0,array('error' => true, 'msg' =>L_('购买的商品库存不足'),'jump' => 'buy','order_id' => $marketOrder['order_id'], 'goods_id' => $marketOrder['goods_id']));
                        } elseif ($row['stock_num'] < $marketGoods['list'][$index]['min_num']) {
                            $this->returnCode(0,array('error' => true, 'msg' =>L_('购买的商品的数量不足最小批发量'),'jump' => 'buy','order_id' => $marketOrder['order_id'], 'goods_id' => $marketOrder['goods_id']));
                        }
                    } else {
                        $this->returnCode(0,array('error' => true, 'msg' =>L_('购买的商品已经没有这种规格了'),'jump' => 'buy','order_id' => $marketOrder['order_id'], 'goods_id' => $marketOrder['goods_id']));
                    }
                    if ($row['stock_num'] > 0) {
                        $total_num += $row['stock_num'];
                    }
                }
                $marketGoodsChageData['spec_value'] = implode('#', $spec_array);
            } elseif ($marketGoods['stock_num']!=-1 && $marketOrder['num'] > $marketGoods['stock_num']) {
                $this->returnCode(0,array('error' => true, 'msg' =>L_('购买的商品库存不足'),'jump' => 'buy','order_id' => $marketOrder['order_id'], 'goods_id' => $marketOrder['goods_id']));
            } elseif ($marketOrder['num'] < $marketGoods['min_num']) {
                $this->returnCode(0,array('error' => true, 'msg' =>L_('购买的商品的数量不足最小批发量'),'jump' => 'buy','order_id' => $marketOrder['order_id'], 'goods_id' => $marketOrder['goods_id']));
            }

            $marketGoodsChageData['stock_num'] = max(0, ($marketGoods['stock_num'] - $marketOrder['num']));
            $marketGoodsChageData['sell_count'] = $marketGoods['sell_count'] + $marketOrder['num'];

            //自己减少余额
            $merchant = M('Merchant')->where(array('mer_id' => $this->merchant['mer_id']))->find();
            if ($pay_type == '' && $merchant['money'] >= $_total) {
                if($marketOrder['group_id']>0){
                    $desc=L_('购买批发市场的拼团商品【X1】扣除商家余额',$marketOrder['name']);
                }else{
                    $desc=L_('购买批发市场的商品【X1】扣除商家余额',$marketOrder['name']);
                }
                $returnData = D('Merchant_money_list')->use_money($this->merchant['mer_id'], $_total,'market',$desc,$marketOrder['order_id']);
            } elseif ($pay_type == '') {
                $money = floatval($_total - $merchant['money']);
                $this->returnCode(1,'',L_('您的余额不足，请前往商家后台最少充值').$money.$this->config['Currency_txt']);
            }


            if ($pay_type == '' && $returnData['error_code']) {
                $this->returnCode(1,'',L_('支付失败！请重试！'));
            } elseif($pay_type == '' && $marketOrder['group_id']<=0) {     //不是拼团的订单
                // 支付成功 查看是否存在推广者， 添加推广者分佣  添加开关控制
                if ($this->config['merchant_recommend_wholesale_switch'] == 1 && $merchant['mer_spread_code'] && $this->config['merchant_recommend_spread_ratio']>0) {
                    $spread_merchant = M('Merchant')->where(array('mer_code' => $merchant['mer_spread_code'], 'mer' => array('neq', $this->merchant['mer_id'])))->find();
                    if ($spread_merchant) {
                        $merchant_recommend_spread_ratio = floatval($this->config['merchant_recommend_spread_ratio'] * 0.01);
                        // 计算分佣金额
                        $spread_money = $marketOrder['money'] * $merchant_recommend_spread_ratio;
                        $spread_money = round($spread_money, 2); // 分佣金额

                        $desc = L_('您推广的X1商户在批发市场消费X2,您获得分佣',array('X1'=>$merchant['name'],'X2'=>$marketOrder['money'].$this->config['Currency_txt']));
                        $order_info['money'] = $spread_money;
                        $order_info['order_type'] = 'market_spread';
                        $order_info['mer_id'] = $spread_merchant['mer_id'];
                        $order_info['order_id'] = $marketOrder['order_id'];
                        $order_info['desc'] = $desc;
                        // 处理数量
                        if ($total_num > 0) {
                            $order_info['num'] = $total_num;
                        } else {
                            $order_info['num'] = $marketOrder['num'];
                        }
                        D('Merchant_money_list')->add_money($order_info);
                        // 同步记录到记录表格中
                        $order_info['spread_mer_id'] = $merchant['mer_id'];
                        $order_info['spread_mer_name'] = $merchant['name'];
                        $order_info['spread_time'] = $merchant['reg_time'];
                        $order_info['add_time'] = time();
                        M('Merchant_recommend_merchant_record')->data($order_info)->add();
                    }
                }
                $nowtime = date("ymdHis");
                $orderid = $nowtime.rand(10,99).sprintf("%06d",$this->merchant['mer_id']);
                $databaseMarketOrder->where(array('order_id' => $order_id))->save(array('orderid' => $orderid,'pay_time' => time()));
            } elseif ($pay_type == 'offline') {
                $nowtime = date("ymdHis");
                $orderid = $nowtime.rand(10,99).sprintf("%06d",$this->merchant['mer_id']);
                $set_data = array(
                    'offline_money' => $_total,
                    'offline_status' => 0,
                    'is_mobile_pay' => 0,
                    'last_time' => time(),
                    'pay_type' => $pay_type,
                    'orderid' => $orderid
                );
                $databaseMarketOrder->field(true)->where(array('order_id' => $order_id, 'mer_id' => $this->merchant['mer_id'], 'status' => 10))->data($set_data)->save();
            } elseif ($pay_type == 'weixinh5') {
                $nowtime = date("ymdHis");
                $orderid = $nowtime.rand(10,99).sprintf("%06d",$this->merchant['mer_id']);
                $data_tmp['pay_type'] = $pay_type;
                $data_tmp['order_type'] = 'market';
                $data_tmp['order_id'] = $order_id;
                $data_tmp['orderid'] = $orderid;
                $data_tmp['addtime'] = $nowtime;
                if(!D('Tmp_orderid')->add($data_tmp)){
                    $this->returnCode(1001,array(),L_('更新失败，请联系管理员'));
                }
                // 微信h5支付
                $pay_method = D('Market_order')->pay_method(true);

                //支付前保存在线支付金额
                $_service = 0;//支付手续费
                if($pay_method[$_POST['pay_type']]['config']['service_charge_open'] == '1'){
                    $service_charge = $pay_method[$_POST['pay_type']]['config']['service_charge'];
                    $_service = $_total*($service_charge/100);
                }
                $_total = floor(($_total+$_service)*100)/100;//保留两位小数，但不四舍五入
                $payment_money_date['payment_money'] = $_total;
                $payment_money_date['pay_type'] =  $pay_type;
                $payment_money_date['order_type'] = 'market';
                $payment_money_date['order_id'] =  $order_id;
                $payment_money_date['orderid'] =  $orderid;
                $payment_money_date['addtime'] = time();

                $payment_money_date['is_mobile_pay'] = $this->is_app ? 2 : ($this->is_wxapp ? 3 : 1);
                $payment_money_date['service_charge'] = $_service;//支付手续费

                M('Payment_money_order')->add($payment_money_date);

                $pay_class_name = ucfirst($pay_type);
                $import_result = import('@.ORG.pay.'.$pay_class_name);
                if(empty($import_result)){
                    $this->returnCode(1001,array(),L_('系统管理员暂未开启该支付方式，请更换其他的支付方式'));
                }
                $databaseMarketOrder->field(true)->where(array('order_id' => $order_id, 'mer_id' => $this->merchant['mer_id'], 'status' => 10))->save(array('pay_type'=>$pay_type, 'orderid' => $orderid));
                $now_order = D('Market_order')->get_pay_order($this->merchant['mer_id'],$order_id,true);
                if(empty($now_order)){
                    $this->returnCode(1001,array(),L_('当前订单不存在'));
                }
                $order_info = $now_order['order_info'];
                $order_info['order_id'] = $orderid;
                $pay_class = new $pay_class_name($order_info,$_total,$pay_type,$pay_method[$pay_type]['config'],$this->merchant,2);
                $go_pay_param = $pay_class->pay();

                if($go_pay_param['result_code'] == 'SUCCESS'){//如果这两个都为此状态则返回mweb_url，详情看‘统一下单’接口文档
                    $mweb_url = $go_pay_param['mweb_url'];
                    $this->returnCode(0, array('error' => true, 'msg' =>L_('支付跳转'),'jump' => 'mweb_url','url' => $mweb_url, 'total' => $_total, 'name' => $marketOrder['name']));
                } elseif($go_pay_param['result_code'] == 'FAIL'){
                    $err_code_des = $go_pay_param['err_code_des'];
                    $this->returnCode(1001,array(),$err_code_des);
                } elseif ($go_pay_param['error']) {
                    $this->returnCode(1001,array(),$go_pay_param['msg']);
                } elseif($go_pay_param['error']==0) {
                    $mweb_url = $go_pay_param['url'];
                    $this->returnCode(0, array('error' => true, 'msg' =>L_('支付跳转'),'jump' => 'mweb_url','url' => $mweb_url, 'total' => $_total, 'name' => $marketOrder['name']));
                } else {
                    $this->returnCode(1001,array(),L_('支付有误'));
                }

            } elseif ($pay_type == 'weixin'){
                $nowtime = date("ymdHis");
                $orderid = $nowtime.rand(10,99).sprintf("%06d",$this->merchant['mer_id']);


                $data_tmp['pay_type'] = $pay_type;
                $data_tmp['order_type'] = 'market';
                $data_tmp['order_id'] = $order_id;
                $data_tmp['orderid'] = $orderid;
                $data_tmp['addtime'] = $nowtime;
                if(!D('Tmp_orderid')->add($data_tmp)){
                    $this->returnCode(1001,array(),L_('更新失败，请联系管理员'));
                }
                // 微信支付
                $pay_method = D('Market_order')->pay_method(true);
                if(strpos($_SERVER['HTTP_USER_AGENT'],'MicroMessenger') !== false){
                    $pay_method_all = D('Config')->get_pay_method(0,0);
                    if ($pay_method_all['weixin']) {
                        $pay_method['weixin'] = $pay_method_all['weixin'];
                    }
                    unset($pay_method['weixinh5']);
                }

                //支付前保存在线支付金额
                $_service = 0;//支付手续费
                if($pay_method[$_POST['pay_type']]['config']['service_charge_open'] == '1'){
                    $service_charge = $pay_method[$_POST['pay_type']]['config']['service_charge'];
                    $_service = $_total*($service_charge/100);
                }
                $_total = floor(($_total+$_service)*100)/100;//保留两位小数，但不四舍五入
                $payment_money_date['payment_money'] = $_total;
                $payment_money_date['pay_type'] =  $pay_type;
                $payment_money_date['order_type'] = 'market';
                $payment_money_date['order_id'] =  $order_id;
                $payment_money_date['orderid'] =  $orderid;
                $payment_money_date['addtime'] = time();

                $payment_money_date['is_mobile_pay'] = $this->is_app ? 2 : ($this->is_wxapp ? 3 : 1);
                $payment_money_date['service_charge'] = $_service;//支付手续费

                M('Payment_money_order')->add($payment_money_date);

                $pay_class_name = ucfirst($pay_type);
                $import_result = import('@.ORG.pay.'.$pay_class_name);
                if(empty($import_result)){
                    $this->returnCodeError(L_('系统管理员暂未开启该支付方式，请更换其他的支付方式'));
                }
                $databaseMarketOrder->field(true)->where(array('order_id' => $order_id, 'mer_id' => $this->merchant['mer_id'], 'status' => 10))->save(array('pay_type'=>$pay_type, 'orderid' => $orderid));
                $now_order = D('Market_order')->get_pay_order($this->merchant['mer_id'],$order_id,true);
                if(empty($now_order)){
                    $this->returnCode(1001,array(),L_('当前订单不存在'));
                }
                $order_info = $now_order['order_info'];
                $order_info['order_id'] = $orderid;

                if ($_POST['openid']) {
                    $_SESSION['openid'] = $_POST['openid'];
                } else {
                    $this->returnCode(1,'',L_('微信支付需要先绑定微信'));
                }

                $pay_class = new $pay_class_name($order_info,$_total,$pay_type,$pay_method[$pay_type]['config'],$this->merchant,1);
                $go_pay_param = $pay_class->pay();

                if (!empty($go_pay_param['weixin_param'])) {
                    if ($pay_method['weixin']['config']['is_own']) {
                        C('open_authorize_wxpay', true);
                        $share = new WechatShare($this->config, $_SESSION['openid']);
                        $this->hideScript = $share->gethideOptionMenu($this->merchant['mer_id']);
                        $arr['hidScript'] = $this->hideScript;
                        $redirctUrl = C('config.site_url') . '/' . $this->indep_house . '?g=Appapi&c=Merchantapp&a=weixin_back&order_type=' . $order_info['order_type'] . '&order_id=' . $order_info['order_id'] . '&own_mer_id=' . $order_info['mer_id'];
                    } else {
                        $redirctUrl = C('config.site_url') . '/' . $this->indep_house . '?g=Appapi&c=Merchantapp&a=weixin_back&order_type=' . $order_info['order_type'] . '&order_id=' . $order_info['order_id'];
                    }
                    $arr['redirctUrl'] = $go_pay_param['redirctUrl'] ? $go_pay_param['redirctUrl'] : $redirctUrl;
                    $arr['pay_money'] = $_total;
                    $arr['weixin_param'] = json_decode($go_pay_param['weixin_param']);
                    $arr['error'] = false;
                    $this->returnCode(0, $arr);
                } else {
                    $this->returnCode(1,'',$go_pay_param['msg']);
                }
            } elseif($marketOrder['group_id']>0){  //拼团成功
                D("Market_goods")->check_group_team($marketOrder,$marketGoods);
            } else {
                $this->error(L_('您选择的支付方式不存在，请更新支付方式！'));
            }

            //修改订单状态
            if ($pay_type == 'offline') {
                $databaseMarketOrder->where(array('order_id' => $order_id))->save(array('status' => 1));
            } else {
                $databaseMarketOrder->where(array('order_id' => $order_id))->save(array('pay_time' => time(), 'status' => 1));
            }

            if ($pay_type != 'offline') {
                //更改批发市场中商品的库存
                $databaseMarketGoods->where(array('goods_id' => $marketOrder['goods_id']))->save($marketGoodsChageData);
            }

            $sms_data = array('mer_id' => $this->merchant['mer_id'], 'store_id' => 0, 'type' => 'market');
            $sellmerchant = M('Merchant')->where(array('mer_id' => $marketOrder['sell_mer_id']))->find();
            if($marketOrder['group_id']<=0){
                $sms_data['uid'] = 0;
                $sms_data['mobile'] = $sellmerchant['phone'];
                $sms_data['sendto'] = 'merchant';
                $sms_data['type'] = 'market';
                $sms_data['content'] = L_('商家X1批发了X2共X3订单号：X4请您注意查看并处理!',array('X1'=>$merchant['name'],'X2'=>$marketOrder['name'],'X3'=>$marketOrder['num'] . $marketOrder['unit'],'X4'=>$marketOrder['order_id']));
                Sms::sendSms($sms_data);
            }

            $sms_data['uid'] = 0;
            $sms_data['mobile'] = $merchant['phone'];;
            $sms_data['sendto'] = 'merchant';
            $sms_data['type'] = 'market';
            $sms_data['content'] = L_('您在X1批发了X2共X3订单号：X4已经完成支付。欢迎下次光临!',array('X1'=>$sellmerchant['name'],'X2'=>$marketOrder['name'],'X3'=>$marketOrder['num'] . $marketOrder['unit'],'X4'=>$marketOrder['order_id']));
            Sms::sendSms($sms_data);

            $this->returnCode(0,'',L_('支付成功！'));
        } else {
            $this->returnCode(1,'',L_('订单信息错误！'));
        }
    }

    // 批发市场
    public function market_index()
    {
        $cat_fid = isset($_POST['cat_fid']) ? intval($_POST['cat_fid']) : 0;
        $cat_id = isset($_POST['cat_id']) ? intval($_POST['cat_id']) : 0;
        $province_id = isset($_POST['province_id']) ? intval($_POST['province_id']) : 0;
        $city_id = isset($_POST['city_id']) ? intval($_POST['city_id']) : 0;
        $area_id = isset($_POST['area_id']) ? intval($_POST['area_id']) : 0;
        $where = array('mer_id' => $this->merchant['mer_id'], 'status' => 1);
        $where['province_id'] = $province_id;
        $where['city_id'] = $city_id;
        $where['area_id'] = $area_id;
        if ($cat_fid && $cat_id) {
            $where['cat_fid'] = $cat_fid;
            $where['cat_id'] = $cat_id;
        }
        if($_POST['goods_name']){
            $where['goods_name'] = $_POST['goods_name'];
        }
        $goods_type=I('goods_type','','intval');
        $now_time=time();
        if($goods_type==1){
            $where['_string']='(g.is_group=0 or (g.is_group=1 && (g.group_stime>'.$now_time.' || g.group_ftime<'.$now_time.')))';
        }elseif($goods_type){
            $where['_string']='g.is_group=1 && g.group_stime<='.$now_time.' && g.group_ftime>='.$now_time;
        }
        $_GET['page'] = $_POST['page'];
        $result = D('Market_goods')->getList($where, 'market', true);
        $list=$result['orders'];
        /*不在活动时间的拼团商品按照普通商品显示*/
        $market_express_template = M('Market_express_template');

        // 权限处理，虚拟批发的
        $is_integral_mall = 1;
        // 添加 【是否开启移动端批发市场功能】开关  0 关 1 开
        if ($this->config['merchant_integral_mall_control']<=0) {
            $is_integral_mall = 0;
        }

        $now_merchant = D('Merchant')->get_info($this->merchant['mer_id']);
        $merchant_menus = $now_merchant['menus'];
        $merchant = explode(',',$merchant_menus);
        if (!in_array(10037,$merchant)) {
            $is_integral_mall = 0; // 积分商城
        }
        foreach ($list as $k => $v) {
            if($v['is_group']==1){
                if($v['group_stime']>$now_time || $v['group_ftime']<$now_time){
                    $v['is_group']=0;
                }
            }
            $v['Currency_txt']=$this->config['Currency_txt'];
            $v['Currency_symbol']=$this->config['Currency_symbol'];

            if ($v['express_template_id'] > 0) {
                $v['express_is_type'] = $market_express_template->where(array('id' => $v['express_template_id']))->getField('is_type');
                if ($v['express_is_type']!=null) {
                    $v['express_is_type'] += 1;  // 处理后的值 1 快递和物流 2 快递 3 物流
                } else {
                    unset($v['express_is_type']);
                }
            }

            // 批发市场上架商家积分商城权限控制
            $v['is_publish_integral_mall']=($this->merchant['is_publish_integral_mall'] && 1==$is_integral_mall)?$this->merchant['is_publish_integral_mall']:0;

            $list[$k]=$v;
        }
        $result['orders']=$list;
        $arr = $result;
        if ($this->merchant['type_id']>0) {
            $merchant_type = M('Merchant_type')->where(array('type_id'=>$this->merchant['type_id']))->find();
            $arr['merchant_type'] = $merchant_type;
        }

        if ($where) {
            $arr['where'] = array();
            foreach ($where as $k=>$v) {
                if ($v) {
                    $arr['where'][$k] = $v;
                }
            }
        }
        $category_list = D('Goods_wholesale_category')->get_list();
        $arr['category_list'] = $category_list;


        // 如果传值了分类 获取下当前分类信息
        if ($cat_fid && $cat_id) {
            $category_where = array(
                'id' => $cat_id,
                'fid' => $cat_fid
            );
            $category = D('Goods_wholesale_category')->where($category_where)->find();
            $arr['category'] = $category;
            $category_fid_where = array(
                'id' => $cat_fid
            );
            $fid_category = D('Goods_wholesale_category')->where($category_fid_where)->find();
            $arr['category']['fid_info'] = $fid_category;
        }

        $count = $this->cartCount();
        $arr['cartCount'] = $count;

        // 处理首页广告
        $wholesale_top_tip_img = D('Adver')->get_adver_by_key('wholesale_top_tip_img',1);
        if ($wholesale_top_tip_img){
            $wholesale_top_tip_img = reset($wholesale_top_tip_img);
            $wholesale_top_6_img = D('Adver')->get_adver_by_key('wholesale_top_6_img',6);
            if ($wholesale_top_6_img && count($wholesale_top_6_img)==6) {
                $arr['wholesale_top_tip_img'] = $wholesale_top_tip_img;
                $arr['wholesale_top_6_img'] = $wholesale_top_6_img;
            }
        }
        $wholesale_middle_tip_img = D('Adver')->get_adver_by_key('wholesale_middle_tip_img',1);
        if ($wholesale_middle_tip_img) {
            $wholesale_middle_tip_img = reset($wholesale_middle_tip_img);
            $wholesale_middle_4_img = D('Adver')->get_adver_by_key('wholesale_middle_4_img',4);
            if ($wholesale_middle_4_img && count($wholesale_middle_4_img)==4) {
                $arr['wholesale_middle_tip_img'] = $wholesale_middle_tip_img;
                $arr['wholesale_middle_4_img'] = $wholesale_middle_4_img;
            }
        }

        // 首页轮播广告
        $banner_list = D('Adver')->get_adver_by_key('wap_wholesale_index_top',5);
        //中间单图
        $adver_middle_img = D('Adver')->get_adver_by_key('wap_wholesale_index_middle',1);
        //导航
        $slider_list = D('Slider')->get_slider_by_key('wap_wholesale_slider', 0);

        $arr['banner_list']=(array)$banner_list;
        $arr['adver_middle_img']=(array)$adver_middle_img;
        $arr['slider_list']=(array)$slider_list;
        $arr['is_integral_mall'] = $is_integral_mall;

        $this->returnCode(0,$arr);
    }

    private function cartCount()
    {
        $count = D('Market_cart')->where(array('mer_id' => $this->merchant['mer_id']))->count();
        return $count;
    }

    //分类页
    public function category()
    {
        $category_list = D('Goods_wholesale_category')->get_list(1);
        $icon_arr=array();
        $i=1;
        foreach ($category_list as $k => $v) {
            $icon_arr[$i]=0;
            foreach ($v['son_list'] as $k2 => $v2) {
                if(!empty($v2['icon_path'])){
                    $icon_arr[$i]=1;
                    break;
                }
            }
            $i++;
        }
        $arr = array();
        $arr['category_list'] = array_values($category_list);
        $arr['first_cat'] = reset($arr['category_list']);
        $arr['icon_arr'] = array_values($icon_arr);
        $this->returnCode(0,$arr);
    }
    // 二级分类
    public function two_category()
    {
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $where = array();
        if ($id > 0) {
            $where['_string'] = "(fid={$id} AND status=1) OR (fid=0 AND id={$id})";
        }

        $category_list = D('Goods_wholesale_category')->get_list(1,$where);
        $arr = array();
        $arr['category_list'] = array_values($category_list);
        $arr['first_cat'] = reset($arr['category_list']);
        $this->returnCode(0,$arr);
    }

    // 获取搜索信息
    public function market_search()
    {
        $search_hot_list = M('Wholesale_search_hot')->field(true)->order('`hottest` DESC,`sort` DESC,`id` ASC')->select();
        $arr = array();
        $arr['search_hot_list'] = $search_hot_list;
        $this->returnCode(0,$arr);
    }

    // 批发商品详情页面
    public function market_buy_detail()
    {
        $goodsId = intval($_POST['goods_id']);
        $pick_id = $_POST['pick_id'];
        $buy_nums = $_POST['buy_nums'] ? $_POST['buy_nums'] : '';
        $buy_num = $_POST['buy_num'] ? $_POST['buy_num'] : '';
        $is_pick_in_store = intval($_POST['is_pick_in_store']);
        $databaseMarketGoods = D('Market_goods');
        $where = array('goods_id' => $goodsId);
        $marketGoods = $databaseMarketGoods->field(true)->where($where)->find();
        if(empty($marketGoods)){
            $this->error(L_('商品不存在！'));
        }
        if($marketGoods['mer_id']==$this->merchant['mer_id']){
            $this->error(L_('自己不能批发自己发布的商品！'));
        }

        if(!empty($marketGoods['image'])){
            $tmp_pic_arr = explode(';', $marketGoods['image']);
            foreach ($tmp_pic_arr as $key => $value) {
                $marketGoods['pic_arr'][$key]['title'] = $value;
                $stripos_result = stripos($value,'/upload/');
                if ($stripos_result !== false) {
                    $marketGoods['pic_arr'][$key]['url'] = replace_file_domain($value);
                } else {
                    $marketGoods['pic_arr'][$key]['url'] = file_domain() . '/upload/goods/' . str_replace(',', '/', $value);//取原图，客户需要清晰度
                }
            }
            $marketGoods['pic'] = reset($marketGoods['pic_arr'])['url'];
        }

        $return = $databaseMarketGoods->format_spec_value($marketGoods['spec_value'], $marketGoods['goods_id']);
        $marketGoods['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
        $marketGoods['list'] = isset($return['list']) ? $return['list'] : '';
        $marketGoods['spec_count'] = isset($return['list']) ? count($marketGoods['list']) : 1;
        $marketGoods['spec_list_count'] = isset($return['spec_list']) ? count($marketGoods['spec_list']) : 1;
        $marketGoods['discount_info'] = json_decode($marketGoods['discount_info'], true);
        $marketGoods['username'] = $this->merchant['name'];
        $marketGoods['userphone'] = $this->merchant['phone'];
        $marketGoods['address'] = '';
        $marketGoods['buy_num'] = $buy_num?:$marketGoods['min_num'];
        $totalMoney = 0;
        $group_totalMoney = 0;
        $totalCount = 0;
        $is_vip = false;
        $is_pick = false;
        // 配送费总值

        // 如果允许自提获取自提点信息
        if ($marketGoods['is_pick'] == 1 && $marketGoods['pick_info']) {
            $pick_info = explode(',', $marketGoods['pick_info']);
            $pick_list = D('Pick_address')->get_pick_addr_by_merid($marketGoods['mer_id'], true);
            $pick_address = array();
            $pick_msg = array();
            foreach ($pick_list as $val) {
                if (in_array($val['pick_addr_id'], $pick_info)) {
                    unset($val['pwd']);
                    $pick_address[] = $val;
                }
                if ($pick_id && $pick_id==$val['pick_addr_id']) {
                    $pick_msg = $val;
                }
            }
            if (!$pick_id) {
                $pick_msg = reset($pick_address);
                $arr['pick_id'] = $pick_msg['pick_addr_id'];
            } else {
                $arr['pick_id'] = $pick_id;
            }
            if (empty($pick_address)) {
                $is_pick = false;
            } else {
                $is_pick = true;
                $has_check = 'pick';
            }
            $arr['pick_msg'] = $pick_msg;
            $arr['pick_address'] = $pick_address;
        }
        // 获取一下对应运费模板消息
        if ($marketGoods['express_template_id'] > 0) {
            $template_msgs = D('Market_express_template_msg')->field(true)->where(array('tid' => $marketGoods['express_template_id']))->select();
            foreach ($template_msgs as $val) {
                if ($val['type']==1) {
                    $express = true;
                } elseif ($val['type']==2) {
                    $logistics = true;
                }
            }
            if ($express && $logistics) {
                $is_type = 3; // 快递和物流都存在
                if (!$has_check) {
                    $has_check = 'express';
                }
            } elseif ($express) {
                $is_type = 1; // 快递
                if (!$has_check) {
                    $has_check = 'express';
                }
            } elseif ($logistics) {
                $is_type = 2; // 物流
                if (!$has_check) {
                    $has_check = 'logistics';
                }
            } else {
                $is_type = 0; // 既没有快递也没有物流
            }
        } else {
            $is_type = 0; // 既没有快递也没有物流
        }
        $arr['is_pick_in_store'] = $is_pick_in_store;
        $arr['is_pick'] = $is_pick;
        $arr['is_type'] = $is_type;
        $arr['has_check'] = $has_check;

        if ($this->merchant['type_id']>0) {
            $merchant_type = M('Merchant_type')->where(array('type_id'=>$this->merchant['type_id']))->find();
            $is_vip = (1==$merchant_type['is_vip'])?true:false;
        }
        if ($marketGoods['spec_list']) {
            foreach ($marketGoods['list']as &$vo) {
                $totalCount += $vo['min_num'];
                if ($is_vip && $vo['vip_wholesale_price']>0) {
                    if ($vo['min_num']<$vo['stock_num'] || $vo['stock_num']==-1) {
                        $totalMoney += $vo['min_num'] * $vo['vip_wholesale_price'];
                    }
                    $vo['o_price'] = $vo['price'];
                    $vo['price'] = $vo['vip_wholesale_price'];
                } else {
                    if ($vo['min_num']<$vo['stock_num'] || $vo['stock_num']==-1) {
                        $totalMoney += $vo['min_num'] * $vo['price'];
                    }
                }
                if($marketGoods['is_group']==1){    //拼团活动商品
                    $group_totalMoney += $vo['min_num'] * $vo['group_team_price'];
                }
            }
            if ($is_vip && $marketGoods['vip_wholesale_price']>0) {
                $marketGoods['o_price'] = $marketGoods['price'];
                $marketGoods['price'] = $marketGoods['vip_wholesale_price'];
            }
        } else {
            if ($is_vip && $marketGoods['vip_wholesale_price']>0) {
                $totalMoney = $marketGoods['buy_num'] * $marketGoods['vip_wholesale_price'];
                $marketGoods['o_price'] = $marketGoods['price'];
                $marketGoods['price'] = $marketGoods['vip_wholesale_price'];
            } else {
                $totalMoney = $marketGoods['buy_num'] * $marketGoods['price'];
            }
            $totalCount = $marketGoods['buy_num'];
            if($marketGoods['is_group']==1){    //拼团活动商品
                $group_totalMoney = $marketGoods['buy_num'] * $marketGoods['group_team_price'];
            }
        }


        $discount_info = null;
        foreach ($marketGoods['discount_info'] as $discount) {
            if ($discount['num'] <= $totalCount) {
                if ($discount_info) {
                    if ($discount_info['discount'] > $discount['discount']) {
                        $discount_info = $discount;
                    }
                } else {
                    $discount_info = $discount;
                }
            }
        }
        if ($discount_info) {
            $totalMoney= round($totalMoney* $discount_info['discount'] * 0.1,2);
        }
        $marketGoods['totalMoney'] = $totalMoney;
        $marketGoods['group_totalMoney'] = $group_totalMoney;

        // 处理系统对应每个商家设置的 批发商品时候的优惠折扣 设置大于零小于100才起效
        $wholesale_discount = $this->merchant['wholesale_discount']>0 ? $this->merchant['wholesale_discount'] : 100;

        if ($wholesale_discount>0 && $wholesale_discount<100) {
            $marketGoods['wholesale_discount'] = $wholesale_discount/10; // 计算折扣
            $marketGoods['discount_totalMoney'] = round($totalMoney*$wholesale_discount*0.01, 2);
        }

        $marketGoods['des']= replace_file_domain($marketGoods['des']);
        // 获取对应快店价格
        $marketGoods['shop_goods_price'] = M('Shop_goods')->where(array('goods_id' => $marketGoods['goods_id']))->getField('price');
        // 获取相关店铺所在地
        if ($marketGoods['province_id'] || $marketGoods['city_id'] || $marketGoods['area_id']) {
            $areaList = D('Area')->where(array('area_id' => array('in', array($marketGoods['province_id'], $marketGoods['city_id'], $marketGoods['area_id']))))->getField('area_id,area_name');
            $marketGoods['province'] = $areaList[$marketGoods['province_id']] ? $areaList[$marketGoods['province_id']] : '';
            $marketGoods['city'] = $areaList[$marketGoods['city_id']] ? $areaList[$marketGoods['city_id']] : '';
            $marketGoods['area'] = $areaList[$marketGoods['province_id']] ? $areaList[$marketGoods['area_id']] : '';
        } elseif ($marketGoods['store_id']>0) {
            $nowStore = M('Merchant_store')->where(array('store_id' => $marketGoods['store_id']))->find('province_id,city_id,area_id');
            if ($nowStore) {
                $areaList = D('Area')->where(array('area_id' => array('in', array($nowStore['province_id'], $nowStore['city_id'], $nowStore['area_id']))))->getField('area_id,area_name');
                $marketGoods['province'] = $areaList[$nowStore['province_id']] ? $areaList[$nowStore['province_id']] : '';
                $marketGoods['city'] = $areaList[$nowStore['city_id']] ? $areaList[$nowStore['city_id']] : '';
                $marketGoods['area'] = $areaList[$nowStore['area_id']] ? $areaList[$nowStore['area_id']] : '';
            }
        }
        if(!$marketGoods['province']) $marketGoods['province'] = '';
        if(!$marketGoods['city']) $marketGoods['city'] = '';
        if(!$marketGoods['area']) $marketGoods['area'] = '';

        $arr['now_order'] = $marketGoods;


        // 配置项信息
        $config = array(
            'Currency_txt' => $this->config['Currency_txt'],
            'Currency_symbol' => $this->config['Currency_symbol'],
        );
        $arr['config'] = $config;

        $count = $this->cartCount();
        $arr['cartCount'] = $count;
        $arr['is_vip'] = $is_vip;


        $address_id = isset($_POST['address_id']) ? intval($_POST['address_id']) : 0;

        $merchant_address_temp = D('Merchant_adress')->field(true)->where(array('mer_id' => $this->merchant['mer_id']))->order('`default` DESC,`adress_id` DESC')->select();
        $arr['address_count'] = count($merchant_address_temp);
        $merchant_address = '';
        foreach ($merchant_address_temp as $address) {
            if ($address_id == $address['adress_id']) {
                $merchant_address = $address;
                break;
            }
            if(empty($address_id) && $address['default']){
                $merchant_address = $address;
                break;
            }
        }
        if (empty($merchant_address) && $merchant_address_temp) {
            $merchant_address = reset($merchant_address_temp);
        }
        if ($merchant_address) {
            $province = D('Area')->get_area_by_areaId($merchant_address['province'],false);
            $merchant_address['province_txt'] = $province['area_name'];

            $city = D('Area')->get_area_by_areaId($merchant_address['city'],false);
            $merchant_address['city_txt'] = $city['area_name'];

            $area = D('Area')->get_area_by_areaId($merchant_address['area'],false);
            $merchant_address['area_txt'] = $area['area_name'];
        }

        $arr['merchant_address']=$merchant_address?$merchant_address:'';
        $arr['address_id']=$address_id?$address_id:'';
        $arr['buy_nums'] = $buy_nums;

        $merchant_store = M('Merchant_store')->field('name, logo')->where(array('store_id' => $marketGoods['store_id']))->find();
        $merchant_store['logo'] = replace_file_domain($merchant_store['logo']);
        $arr['merchant_store_info'] = $merchant_store;

        $this->returnCode(0,$arr);
    }

    // 加入购物车
    public function add_market_cart()
    {
        if (IS_POST) {
            $goodsId = isset($_POST['goods_id']) ? intval($_POST['goods_id']) : 0;
            $is_pick_in_store = isset($_POST['is_pick_in_store']) ?  $_POST['is_pick_in_store'] : -1;
            $pick_id = isset($_POST['pick_id']) ? $_POST['pick_id'] : ''; // 暂时不会有，默认第一个
            $databaseMarketGoods = D('Market_goods');
            $where = array('goods_id' => $goodsId);
            $marketGoods = $databaseMarketGoods->field(true)->where($where)->find();
            if(empty($marketGoods)){
                $this->returnCode(1,'',L_('商品不存在！'));
            }
            if ($marketGoods['status'] != 1) {
                $this->returnCode(1,'',L_('商品已下架！'));
            }
            $return = $databaseMarketGoods->format_spec_value($marketGoods['spec_value'], $marketGoods['goods_id']);
            $marketGoods['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
            $marketGoods['list'] = isset($return['list']) ? $return['list'] : '';
            $marketGoods['discount_info'] = json_decode($marketGoods['discount_info'], true);

            $is_vip = false;
            $is_pick = false;
            $express = false;
            $logistics = false;
            // 如果允许自提获取自提点信息
            if ($marketGoods['is_pick'] == 1 && $marketGoods['pick_info']) {
                $pick_info = explode(',', $marketGoods['pick_info']);
                $pick_list = D('Pick_address')->get_pick_addr_by_merid($marketGoods['mer_id'], true);
                $pick_address = array();
                $pick_msg = array();
                foreach ($pick_list as $val) {
                    if (in_array($val['pick_addr_id'], $pick_info)) {
                        unset($val['pwd']);
                        $pick_address[] = $val;
                    }
                    if ($pick_id && $pick_id == $val['pick_addr_id']) {
                        $pick_msg = $val;
                    }
                }
                if (!$pick_id && $is_pick_in_store==0) {
                    $pick_msg = reset($pick_list);
                    if ($pick_msg && $pick_msg['id']) {
                        $pick_id = 'p'.$pick_msg['id'];
                    }
                }
                if (empty($pick_address)) {
                    $is_pick = false;
                } else {
                    $is_pick = true;
                }
                $this->assign('pick_address', $pick_address);
                $merchant = M('Merchant')->where(array('mer_id' => $this->merchant['mer_id']))->find();
                if (IS_POST && $pick_msg) {
                    $_POST['username'] = $merchant['name'];
                    $_POST['userphone'] = $pick_msg['phone'];
                    $_POST['address'] = $pick_msg['area_info']['province'].' '.$pick_msg['area_info']['city'].' '.$pick_msg['area_info']['area'].' '.$pick_msg['name'];
                    if (!empty($pick_msg['province'])) {
                        $_POST['province_id'] = $pick_msg['province'];
                    }
                    if (!empty($pick_msg['city'])) {
                        $_POST['city_id'] = $pick_msg['city'];
                    }
                    if (!empty($pick_msg['area'])) {
                        $_POST['area_id'] = $pick_msg['area'];
                    }
                }
            }
            // 获取一下对应运费模板消息
            if ($marketGoods['express_template_id'] > 0) {
                $template_msgs = D('Market_express_template_msg')->field(true)->where(array('tid' => $marketGoods['express_template_id']))->select();
                foreach ($template_msgs as $val) {
                    if ($val['type']==1) {
                        $express = true;
                    } elseif ($val['type']==2) {
                        $logistics = true;
                    }
                }
            }
            if ($marketGoods['is_pick'] == 1 && $marketGoods['pick_info'] && $is_pick_in_store==0 && !$is_pick) {
                $this->returnCode(1001,array(),L_('当前商品无法自提，请刷新重试！'));
            } elseif ($marketGoods['express_template_id'] > 0 && $is_pick_in_store==1 && !$express) {
                $this->returnCode(1001,array(),L_('当前商品无法使用快递，请刷新重试！'));
            } elseif ($marketGoods['express_template_id'] > 0 && $is_pick_in_store==2 && !$logistics) {
                $this->returnCode(1001,array(),L_('当前商品无法使用物流，请刷新重试！'));
            }
            if ($this->merchant['type_id']>0) {
                $merchant_type = M('Merchant_type')->where(array('type_id'=>$this->merchant['type_id']))->find();
                $is_vip = (1==$merchant_type['is_vip'])?true:false;
            }
            $totalNum = 0;
            $totalPrice = 0;
            $specArr = array();
            if ($marketCartOld = M('Market_cart')->field(true)->where(array('goods_id' => $goodsId, 'mer_id' => $this->merchant['mer_id']))->find()) {
                if ($marketCartOld['spec_value'] && $marketGoods['list']) {
                    $spec_array = explode('#', $marketCartOld['spec_value']);
                    foreach ($spec_array as $str) {
                        $row_array = explode('|', $str);
                        $i = str_replace(':', '_', $row_array[0]);
                        $specArr[$i] = explode(':', $row_array[1]);
                    }
                }
            }
            $error_tips = '';
            $spec_price_arr = array();
            if ($marketGoods['spec_list']) {
                $buy_nums = $_POST['buy_nums'];
                $spec_array = explode('#', $marketGoods['spec_value']);
                $target_spec_value_array = array();
                foreach ($spec_array as $str) {
                    $row_array = explode('|', $str);
                    $i = 'id_' . str_replace(':', '_id_', $row_array[0]);
                    $ii = str_replace(':', '_', $row_array[0]);
                    if (isset($specArr[$ii]) && $specArr[$ii]) {
                        $specArr[$ii][3] += $buy_nums[$i];
                        $totalNum += $specArr[$ii][3];
                        if ($is_vip && $specArr[$ii][7]>0) {
                            $totalPrice += $specArr[$ii][3] * $specArr[$ii][7];
                            $spec_price_arr[] = $specArr[$ii][7];
                        } else {
                            $totalPrice += $specArr[$ii][3] * $specArr[$ii][1];
                            $spec_price_arr[] = $specArr[$ii][1];
                        }
                        $row_array[1] = implode(':', $specArr[$ii]);
                        $target_spec_value_array[] = implode('|', $row_array);
                    } else {
                        $numArray = explode(':', $row_array[1]);
                        if ($buy_nums[$i] && $buy_nums[$i] >= $numArray[2] && ($buy_nums[$i] <= $numArray[3] || $numArray[3] == -1)) {
                            $totalNum += $buy_nums[$i];
                            if ($is_vip && $numArray[7]>0) {
                                $totalPrice += $buy_nums[$i] * $numArray[7];
                                $spec_price_arr[] = $numArray[7];
                            } else {
                                $totalPrice += $buy_nums[$i] * $numArray[1];
                                $spec_price_arr[] = $numArray[1];
                            }
                            //[new] => 规格值ID:规格值ID:...:规格值ID|old_price:price:min_num:stock_num:cost_price:::vip_wholesale_price|属性ID=可选值个数:属性ID=可选值个数:...:属性ID=可选值个数|商品编号#
                            //规格值ID:规格值ID:...:规格值ID|old_price:price:min_num:stock_num(购入的数量):cost_price|属性ID=可选值个数:属性ID=可选值个数:...:属性ID=可选值个数|商品编号#
                            $numArray[3] = $buy_nums[$i];
                            $row_array[1] = implode(':', $numArray);
                            $target_spec_value_array[] = implode('|', $row_array);
                        }
                    }
                }

                if ($totalNum <= 0) {
                    $error_tips .= L_('您至少要批发一种规格且满足对应的批发条件').'<br/>';
                }

            } else {
                if ($totalNum) {
                    $totalNum += isset($_POST['buy_num']) ? intval($_POST['buy_num']) : 0;
                } else {
                    $totalNum = isset($_POST['buy_num']) ? intval($_POST['buy_num']) : $marketGoods['min_num'];
                }

                if ($totalNum > $marketGoods['stock_num']) {
                    $error_tips .= L_('该商品库存不足').'<br/>';
                }
                if ($totalNum < $marketGoods['min_num']) {
                    $error_tips .= L_('一次至少要批发X1',$marketGoods['min_num'] . $marketGoods['unit']) .'<br/>';
                }

                if ($is_vip && $marketGoods['vip_wholesale_price']>0) {
                    $totalPrice = $totalNum * $marketGoods['vip_wholesale_price'];
                    $marketGoods['o_price'] = $marketGoods['price'];
                    $marketGoods['price'] = $marketGoods['vip_wholesale_price'];
                } else {
                    $totalPrice = $totalNum * $marketGoods['price'];
                }
            }

            if (empty($error_tips)) {
                $discount_info = null;
                foreach ($marketGoods['discount_info'] as $discount) {
                    if ($discount['num'] <= $totalNum) {
                        if ($discount_info) {
                            if ($discount_info['discount'] > $discount['discount']) {
                                $discount_info = $discount;
                            }
                        } else {
                            $discount_info = $discount;
                        }
                    }
                }
                $money = $totalPrice;
                if ($discount_info) {
                    $money = $totalPrice * $discount_info['discount'] * 0.1;
                }


                $marketCart = array('mer_id' => $this->merchant['mer_id'], 'last_time' => time());
                if ($is_pick_in_store >= 0) {
                    $marketCart['is_pick_in_store'] = $is_pick_in_store;
                }
                $marketCart['pick_id'] = $pick_id;
                $marketCart['money'] = $money;
                $marketCart['total_price'] = $totalPrice;
                $marketCart['num'] = $totalNum;
                $min_price = $marketGoods['price'];
                if ($spec_price_arr) {
                    $spec_price = implode(',', $spec_price_arr);
                    $marketCart['spec_price'] = $spec_price;
                    $min_price = min($spec_price_arr);
                    if (!$min_price) {
                        $min_price = $marketGoods['price'];
                    }
                }
                $marketCart['price'] = $min_price;
                $marketCart['sell_mer_id'] = $marketGoods['mer_id'];
                if ($marketGoods['store_id'] > 0) {
                    $marketCart['sell_store_id'] = $marketGoods['store_id'];
                }
//                 $marketOrder['status'] = 10;

                $marketCart['goods_id'] = $marketGoods['goods_id'];
                $marketCart['name'] = $marketGoods['name'];
                $marketCart['number'] = $marketGoods['number'];
                $marketCart['unit'] = $marketGoods['unit'];
                $marketCart['image'] = $marketGoods['image'];
                $marketCart['des'] = $marketGoods['des'];
                $marketCart['is_properties'] = $marketGoods['is_properties'];
                $marketCart['discount_info'] = $discount_info ? json_encode($discount_info) : '';
                // 是不是享受的vip价格  0 不是  1 是
                $marketCart['is_vip'] = $is_vip?1:0;

//                 $marketOrder['username'] = $username;
//                 $marketOrder['userphone'] = $userphone;
//                 $marketOrder['address'] = $address;
//                 $marketOrder['desc'] = $desc;
//                 $marketOrder['create_time'] = time();

                $marketCart['spec_value'] = $target_spec_value_array ? implode('#', $target_spec_value_array) : '';

                if ($marketCartOld) {
                    if ($cartid = M('Market_cart')->where(array('goods_id' => $goodsId, 'mer_id' => $this->merchant['mer_id']))->save($marketCart)) {
                        $this->returnCode(0,$marketCart);
                    } else {
                        $this->returnCode(1,'',L_('加入购物车失败！请重试！'));
                    }
                } else {
                    $cartid = M('Market_cart')->add($marketCart);
                }

                if ($cartid) {
                    $this->returnCode(0,$marketCart);
                } else {
                    $this->returnCode(1,'',L_('加入购物车失败！请重试！'));
                }
            } else {
                $this->returnCode(1,'',$error_tips);
            }
        }
    }

    // 获取购物车信息
    public function market_cart()
    {
        $fid = isset($_POST['fid']) ? intval($_POST['fid']) : 0;
        $marketCart=array();
        $address_id = isset($_POST['address_id']) ? intval($_POST['address_id']) : 0;
        $province_id = 0;
        $pick_id = isset($_POST['pick_id']) ? $_POST['pick_id'] : '';
        $cartid = isset($_POST['cartid']) ? intval($_POST['cartid']) : 0;

        $merchant_address_temp = D('Merchant_adress')->field(true)->where(array('mer_id' => $this->merchant['mer_id']))->order('`default` DESC,`adress_id` DESC')->select();
        $marketCart['address_count'] = count($merchant_address_temp);
        $merchant_address = '';
        foreach ($merchant_address_temp as $address) {
            if ($address_id == $address['adress_id']) {
                $merchant_address = $address;
                break;
            }
            if(empty($address_id) && $address['default']){
                $merchant_address = $address;
                break;
            }
        }
        if (empty($merchant_address) && $merchant_address_temp) {
            $merchant_address = reset($merchant_address_temp);
        }
        if ($merchant_address) {
            $province = D('Area')->get_area_by_areaId($merchant_address['province'],false);
            $merchant_address['province_txt'] = $province['area_name'];

            $city = D('Area')->get_area_by_areaId($merchant_address['city'],false);
            $merchant_address['city_txt'] = $city['area_name'];

            $area = D('Area')->get_area_by_areaId($merchant_address['area'],false);
            $merchant_address['area_txt'] = $area['area_name'];

            $province_id = $merchant_address['province'];
        }
        $marketCart['merchant_address']=$merchant_address?$merchant_address:'';
        $marketCart['address_id']=$address_id?$address_id:'';

        if ($totalData = D('Market_total')->where(array('id' => $fid, 'mer_id' => $this->merchant['mer_id']))->find()) {
            if ($totalData['status'] == 1) {
                $this->error(L_('此单已支付'));
            }
            $databaseMarketGoods = D('Market_order');
            $cartList = $databaseMarketGoods->where(array('mer_id' => $this->merchant['mer_id'], 'fid' => $fid))->select();

            $goodsDB = D('Market_goods');
            foreach ($cartList as $cart) {
                $gds = $goodsDB->field('status')->where(array('goods_id' => $cart['goods_id']))->find();
                if (empty($gds) || $gds['status'] != 1) {
                    $this->error(L_('商品【X1】已下架，此单已失效，请重新下单订购其他商品',$cart['name']));
                }
            }
            if ($totalData['discount_totalMoney']) {
                $totalMoney = $totalData['discount_totalMoney'];
                $discountMoney = $totalData['money'] - $totalData['discount_totalMoney'];
            } else {
                $totalMoney = $totalData['money'];
                $discountMoney = 0;
            }
            $assign_array = array(
                'totalMoney' => $totalMoney,
                'discountMoney' => $discountMoney,
                'fid' => $fid,
                'totalCount' => count($cartList)
            );
            $marketCart = $assign_array;
            $marketCart['username'] = $cartList[0]['username'];
            $marketCart['userphone'] = $cartList[0]['userphone'];
            $marketCart['address'] = $cartList[0]['address'];
            $marketCart['desc'] = $cartList[0]['desc'];
        } else {
            $cartList = D()->query('select c.*, m.name as merchantName from ' . C('DB_PREFIX') . 'market_cart as c inner join ' . C('DB_PREFIX') . 'merchant as m on c.sell_mer_id=m.mer_id where c.mer_id=' . $this->merchant['mer_id']);
            $marketCart['username'] = $this->merchant['name'];
            $marketCart['userphone'] = $this->merchant['phone'];
            $marketCart['address'] = '';
            $marketCart['desc'] = '';

        }
        $goods_image_class = new goods_image();
        $databaseMarketGoods = D('Market_cart');
        $goodsDB = D('Market_goods');
        $list = array();
        // 处理系统对应每个商家设置的 批发商品时候的优惠折扣 设置大于零小于100才起效
        $wholesale_discount = $this->merchant['wholesale_discount']>0 ? $this->merchant['wholesale_discount'] : 100;

        $is_type = 0;
        foreach ($cartList as $cart) {
            $gds = $goodsDB->field('is_pick, pick_info, mer_id, status, freight_type, goods_volume, goods_weight, store_id, express_fee, express_template_id')->where(array('goods_id' => $cart['goods_id']))->find();
            if (empty($gds) || $gds['status'] != 1) {
                M('Market_cart')->where(array('cartid' => $cart['cartid']))->delete();
                continue;
            }
            // 配送费相关依据字段
            $cart['freight_type'] = $gds['freight_type']>0?$gds['freight_type']:1;
            $cart['goods_volume'] = $gds['goods_volume']>0?$gds['goods_volume']:0;
            $cart['goods_weight'] = $gds['goods_weight']>0?$gds['goods_weight']:0;
            $cart['store_id'] = $gds['store_id']>0?$gds['store_id']:0;
            $cart['express_fee'] = $gds['express_fee']>0?$gds['express_fee']:0;
            $cart['express_template_id'] = $gds['express_template_id']>0?$gds['express_template_id']:0;
            if(!empty($cart['image'])){
                $image = '';
                $tmp_pic_arr = explode(';', $cart['image']);
                foreach ($tmp_pic_arr as $key => $value) {
                    if (empty($image)) {
                        $image = $goods_image_class->get_image_by_path($value, 's');
                    }
                }
                $cart['image'] = $image;
            }
            $return = $databaseMarketGoods->format_spec_value($cart['spec_value'], $cart['goods_id'], $cart['cartid']);
            $cart['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
            $cart['list'] = isset($return['list']) ? $return['list'] : '';

            if ($cart['list']) {
                $buy_nums = array();
                foreach ($cart['list'] as $val) {
                    $buy_nums[$val['index']] = $val['stock_num'];
                }
                if ($province_id>0 && $buy_nums && $cart['express_template_id']>0 && $cart['is_pick_in_store']>0) {
                    $msg = D('Market_order')->market_express_template_fee($province_id, $buy_nums, $cart, $cart['is_pick_in_store']);
                    if ($msg['total_express_fee']>0) {
                        $cart['total_express_fee'] = $msg['total_express_fee'];
                    }
                }
            } else {
                if ($province_id>0 && $cart['express_template_id']>0 && $cart['is_pick_in_store']>0) {
                    $msg = D('Market_order')->market_express_template_fee($province_id, $cart['num'], $cart, $cart['is_pick_in_store']);
                    if ($msg['total_express_fee']>0) {
                        $cart['total_express_fee'] = $msg['total_express_fee'];
                    }
                }
            }
            $cart['discount_info'] = json_decode($cart['discount_info'], true);
            if ($wholesale_discount>0 && $wholesale_discount<100) {
                $cart['wholesale_discount'] = $wholesale_discount/10; // 计算折扣
                $cart['discount_totalMoney'] = round($cart['money']*$wholesale_discount*0.01, 2);
            } else {
                $cart['discount_totalMoney'] = $cart['money'];
            }
            $cart['is_pick'] = false;
            // 配送费总值

            // 如果允许自提获取自提点信息
            if ($cart['is_pick_in_store'] == 0 && $gds['is_pick'] == 1 && $gds['pick_info']) {
                $pick_info = explode(',', $gds['pick_info']);
                $pick_list = D('Pick_address')->get_pick_addr_by_merid($gds['mer_id'], true, 0, true, $pick_info);
                $cart['pick_address'] = $pick_list;
                $cart['pick_msg'] = array();
                if ($pick_id && $pick_list[$pick_id] && $cartid == $cart['cartid']) {
                    $cart['pick_msg'] = $pick_list[$pick_id];
                } else if (!$pick_id && $cart['pick_id'] && $pick_list[$cart['pick_id']]) {
                    $cart['pick_msg'] = $pick_list[$cart['pick_id']];
                }
                if (empty($cart['pick_address'])) {
                    $cart['is_pick'] = false;
                } else {
                    $cart['is_pick'] = true;
                    $cart['has_check'] = 'pick';
                }
                if ($cart['pick_msg'] && $pick_id  && $cartid == $cart['cartid']) {
                    $pick_msg = $cart['pick_msg'];
                    $change_save['pick_id'] = $pick_id;
                    $change_save['username'] = $this->merchant['name'];
                    $change_save['userphone'] = $pick_msg['phone'];
                    $change_save['address'] = $pick_msg['area_info']['province'].' '.$pick_msg['area_info']['city'].' '.$pick_msg['area_info']['area'].' '.$pick_msg['name'];
                    if (!empty($pick_msg['province'])) {
                        $change_save['province_id'] = $pick_msg['province'];
                    }
                    if (!empty($pick_msg['city'])) {
                        $change_save['city_id'] = $pick_msg['city'];
                    }
                    if (!empty($pick_msg['area'])) {
                        $change_save['area_id'] = $pick_msg['area'];
                    }
                    M('Market_cart')->where(array('cartid' => $cartid))->save($change_save);
                }
            }
            // 获取一下对应运费模板消息
            if ($cart['express_template_id'] > 0) {
                $template_msgs = D('Market_express_template_msg')->field(true)->where(array('tid' => $cart['express_template_id']))->select();
                foreach ($template_msgs as $val) {
                    if ($val['type']==1) {
                        $express = true;
                    } elseif ($val['type']==2) {
                        $logistics = true;
                    }
                }
                if ($express && $logistics) {
                    $cart['is_type'] = 3; // 快递和物流都存在
                    if (!$cart['has_check']) {
                        $cart['has_check'] = 'express';
                    }
                    $is_type = 3;
                } elseif ($express) {
                    $cart['is_type'] = 1; // 快递
                    if (!$cart['has_check']) {
                        $cart['has_check'] = 'express';
                    }
                    $is_type = 1;
                } elseif ($logistics) {
                    $cart['is_type'] = 2; // 物流
                    if (!$cart['has_check']) {
                        $cart['has_check'] = 'logistics';
                    }
                    $is_type = 2;
                } else {
                    $cart['is_type'] = 0; // 既没有快递也没有物流
                }
            } else {
                $cart['is_type'] = 0; // 既没有快递也没有物流
            }
            if (isset($list[$cart['sell_mer_id']])) {
                $list[$cart['sell_mer_id']]['cartlist'][]= $cart;
            } else {
                $list[$cart['sell_mer_id']] = array('name' => $cart['merchantName'], 'sell_mer_id'=> $cart['sell_mer_id'], 'cartlist' => array($cart));
            }

        }

        $is_vip = false;
        if ($this->merchant['type_id']>0) {
            $merchant_type = M('Merchant_type')->where(array('type_id'=>$this->merchant['type_id']))->find();
            $is_vip = (1==$merchant_type['is_vip'])?true:false;
        }
        $marketCart['is_vip']=$is_vip;
        $marketCart['is_type']=$is_type;
        $marketCart['cartList']=$list;
        if (empty($list)) {
            $marketCart['is_cart'] = false;
        } else {
            $marketCart['is_cart'] = true;
        }
        // 配置项信息
        $config = array(
            'Currency_txt' => $this->config['Currency_txt'],
            'Currency_symbol' => $this->config['Currency_symbol'],
            'site_url' => $this->config['site_url'],
        );
        $marketCart['config'] = $config;


        $merchant = M('Merchant')->where(array('mer_id' => $this->merchant['mer_id']))->find();
        $pay_method = D('Market_order')->pay_method();
        if(strpos($_SERVER['HTTP_USER_AGENT'],'MicroMessenger') !== false){
            $pay_method_all = D('Config')->get_pay_method(0,0);
            if ($pay_method_all['weixin']) {
                $pay_method['weixin'] = $pay_method_all['weixin'];
            }
            unset($pay_method['weixinh5']);
        }
        $marketCart['merchant'] = $merchant;
        $marketCart['pay_method'] = $pay_method;
        if (!$marketCart['is_pick'] && in_array($marketCart['is_type'],[1,2,3])) {
            $marketCart['is_address'] = 1; // 需要提交地址
        }

        $this->returnCode(0,$marketCart);
    }

    public function goPay()
    {
        $fid = isset($_POST['fid']) ? intval($_POST['fid']) : 0;
        $totalMoney = 0;
        $total_express_fee = 0;
        $resultList = array();
        $databaseMarketTotal = D('Market_total');
        $databaseMarketGoods = D('Market_goods');
        $databaseMarketOrder = D('Market_order');
        $databaseMarketCart = D('Market_cart');
        $total_name = '购物车商品';
        // 处理系统对应每个商家设置的 批发商品时候的优惠折扣 设置大于零小于100才起效
        $wholesale_discount = $this->merchant['wholesale_discount']>0 ? $this->merchant['wholesale_discount'] : 100;
        // 购买总数
        $total_num = 0;
        $province_id = $_POST['province_id'] > 0 ? $_POST['province_id'] : 0;
        $city_id = $_POST['city_id'] > 0 ? $_POST['city_id'] : 0;
        $area_id = $_POST['area_id'] > 0 ? $_POST['area_id'] : 0;
        $is_type = $_POST['is_type'] > 0 ? $_POST['is_type'] : 0;



        if ($totalData = $databaseMarketTotal->where(array('id' => $fid, 'mer_id' => $this->merchant['mer_id']))->find()) {
            if ($totalData['status'] == 1) {
                $this->error(L_('此单已支付'));
            }
            $resultList = $databaseMarketOrder->field(true)->where(array('mer_id' => $this->merchant['mer_id'], 'fid' => $fid))->select();
            foreach ($resultList as $marketOrder) {

                $order_id = $marketOrder['order_id'];
                $return = $databaseMarketOrder->format_spec_value($marketOrder['spec_value'], $marketOrder['goods_id'], $order_id);
                $marketOrder['list'] = isset($return['list']) ? $return['list'] : '';

                $where = array('goods_id' => $marketOrder['goods_id']);
                $marketGoods = $databaseMarketGoods->field(true)->where($where)->find();
                if(empty($marketGoods) || $marketGoods['status'] != 1){
                    //批发市场是没有这条数据的时候删除没有支付的订单所有的数据
                    $where['order_id'] = $order_id;
                    M('Market_order_properties')->where($where)->delete();
                    $spec_list = M('Market_order_spec')->field(true)->where($where)->select();
                    foreach ($spec_list as $spec) {
                        M('Market_order_spec_value')->where(array('sid' => $spec['id'], 'order_id' => $order_id))->delete();
                    }
                    M('Market_order_spec')->where($where)->delete();

                    $databaseMarketOrder->where(array('order_id' => $order_id, 'mer_id' => $this->merchant['mer_id']))->delete();

                    //修改订单的总价
                    $databaseMarketTotal->where(array('id' => $fid, 'mer_id' => $this->merchant['mer_id']))->save(array('money' => $totalData['money'] - $marketOrder['money']));
                    $this->error(L_('商品【X1】在批发市场上已下架，不能购买了',$marketGoods['name']), U('market'));
                    exit();
                }
            }
            // 这里优先取平台折扣后的金额（兼容残留的数据）
            if ($totalData['discount_totalMoney'] > 0) {
                $totalMoney = $totalData['discount_totalMoney'];
            } else {
                $totalMoney = $totalData['money'];
            }
        } else {
            $cartIds = isset($_POST['cartids']) ? $_POST['cartids'] : null;
            if (empty($cartIds)) {
                exit(json_encode(array('errorCode' => 1001,'errorMsg'=>L_('请选择商品'))));
            }
            $username = isset($_POST['username']) ? htmlspecialchars(trim($_POST['username'])) : '';
            $userphone = isset($_POST['userphone']) ? htmlspecialchars(trim($_POST['userphone'])) : '';
            $address = isset($_POST['address']) ? htmlspecialchars(trim($_POST['address'])) : '';
            $desc = isset($_POST['desc']) ? htmlspecialchars(trim($_POST['desc'])) : '';
            if ($is_type > 0 && empty($username)) {
                exit(json_encode(array('errorCode' => 1001,'errorMsg'=>L_('收货人不能为空'))));
//                $this->error(L_('收货人不能为空'));
            }
            if ($is_type > 0 && empty($userphone)) {
                exit(json_encode(array('errorCode' => 1001,'errorMsg'=>L_('联系电话不能为空'))));
                // $this->error(L_('联系电话不能为空'));
            }
            if ($is_type > 0 && empty($address)) {
                exit(json_encode(array('errorCode' => 1001,'errorMsg'=>L_('收货地址不能为空'))));
//                $this->error(L_('收货地址不能为空'));
            }
            $list = $databaseMarketCart->field(true)->where(array('mer_id' => $this->merchant['mer_id'], 'cartid' => array('in', $cartIds)))->select();
            $is_pick = false;
            if ($list) {
                $orderList = array();
                foreach ($list as $marketOrder) {
                    if(count($list)>1) {
                        $total_name = $marketOrder['name'] . ' '.C('config.Currency_symbol') . $marketOrder['price'] . ' *' . $marketOrder['num'] . '  <b style="color:#BEBFBF;">等</b>';
                    }else{
                        $total_name = $marketOrder['name'] . ' '.C('config.Currency_symbol') . $marketOrder['price'] . ' *' . $marketOrder['num'] ;
                    }
                    $cartid = $marketOrder['cartid'];
                    $return = $databaseMarketCart->format_spec_value($marketOrder['spec_value'], $marketOrder['goods_id'], $cartid);
                    $marketOrder['list'] = isset($return['list']) ? $return['list'] : '';

                    $where = array('goods_id' => $marketOrder['goods_id']);
                    $marketGoods = $databaseMarketGoods->field(true)->where($where)->find();
                    if(empty($marketGoods) || $marketGoods['status'] != 1){
                        //$databaseMarketCart->where(array('mer_id' => $this->merchant['mer_id'], 'cartid' => $cartid))->delete();
                        exit(json_encode(array('errorCode' => 1001,'errorMsg'=>L_('商品【X1】在批发市场上已下架，不能购买了',$marketGoods['name']))));
//                                 $this->error(L_('商品【X1】在批发市场上已下架，不能购买了',$marketGoods['name']), U('cart'));
                    }
                    // 处理自提问题
                    if ($marketOrder['is_pick_in_store'] == 0 && $marketGoods['is_pick'] == 1 && $marketGoods['pick_info']) {
                        if (empty($marketOrder['pick_id'])) {
                            exit(json_encode(array('errorCode' => 1001,'errorMsg'=>L_('商品【X1】需要选择自提地点',$marketGoods['name']))));
                        }
                    }


                    // 配送费相关依据字段
                    $marketOrder['freight_type'] = $marketGoods['freight_type']>0?$marketGoods['freight_type']:1;
                    $marketOrder['goods_volume'] = $marketGoods['goods_volume']>0?$marketGoods['goods_volume']:0;
                    $marketOrder['goods_weight'] = $marketGoods['goods_weight']>0?$marketGoods['goods_weight']:0;
                    $marketOrder['store_id'] = $marketGoods['store_id']>0?$marketGoods['store_id']:0;
                    $marketOrder['express_fee'] = $marketGoods['express_fee']>0?$marketGoods['express_fee']:0;
                    $marketOrder['express_template_id'] = $marketGoods['express_template_id']>0?$marketGoods['express_template_id']:0;
                    $marketOrder['sell_store_id'] = $marketGoods['store_id']>0?$marketGoods['store_id']:0;
                    if ($marketOrder['list']) {
                        $return = $databaseMarketGoods->format_spec_value($marketGoods['spec_value'], $marketGoods['goods_id']);
                        $marketGoods['list'] = isset($return['list']) ? $return['list'] : '';

                        foreach ($marketOrder['list'] as $index => $row) {
                            if (isset($marketGoods['list'][$index])) {
                                if ($row['stock_num'] > $marketGoods['list'][$index]['stock_num']) {
                                    exit(json_encode(array('errorCode' => 1001,'errorMsg'=>L_('购买商品【X1】的库存不足',$marketGoods['name']))));
//                                 $this->error(L_('购买商品【X1】的库存不足',$marketGoods['name']), U('cart'));
                                    //exit();
                                } elseif ($row['stock_num'] < $marketGoods['list'][$index]['min_num']) {
                                    exit(json_encode(array('errorCode' => 1001,'errorMsg'=>L_('购买的商品【X1】的数量不足最小批发量',$marketGoods['name']))));
//                                    $this->error(L_('购买的商品【X1】的数量不足最小批发量',$marketGoods['name']), U('cart'));
//                                    exit();
                                }
                            } else {
                                exit(json_encode(array('errorCode' => 1001,'errorMsg'=>L_('购买商品商品【X1】的已经没有这种规格了',$marketGoods['name']))));
//                                 $this->error(L_('购买商品【X1】的已经没有这种规格了',$marketGoods['name']), U('cart'));
//                                exit();
                            }
                            $buy_nums[$row['index']] = $row['stock_num'];
                        }
                        if ($province_id>0 && $buy_nums && $marketOrder['express_template_id']>0) {
                            $msg = D('Market_order')->market_express_template_fee($province_id, $buy_nums, $marketOrder, $marketOrder['freight_type']);
                            if ($msg['total_express_fee']>0) {
                                $marketOrder['total_express_fee'] = $msg['total_express_fee'];
                            }
                        }
                    } elseif ($marketOrder['num'] > $marketGoods['stock_num']) {
                        exit(json_encode(array('errorCode' => 1001,'errorMsg'=>L_('购买商品【X1】的库存不足',$marketGoods['name']))));
//                               $this->error(L_('购买商品【X1】的库存不足',$marketGoods['name']), U('cart'));
//                        exit();
                    } elseif ($marketOrder['num'] < $marketGoods['min_num']) {
//                        $this->error(L_('购买商品【X1】的数量不足最小批发量',$marketGoods['name']), U('cart'));
                        exit(json_encode(array('errorCode' => 1001,'errorMsg'=>L_('购买的商品【X1】的数量不足最小批发量',$marketGoods['name']))));
//                        exit();
                    } elseif (!$marketOrder['list']) {
                        if ($province_id>0 && $marketOrder['express_template_id']>0) {
                            $msg = D('Market_order')->market_express_template_fee($province_id, $marketOrder['num'], $marketOrder, $marketOrder['freight_type']);
                            if ($msg['total_express_fee']>0) {
                                $marketOrder['total_express_fee'] = $msg['total_express_fee'];
                            }
                        }
                    }

                    $totalMoney += $marketOrder['money'];
                    if ($marketOrder['total_express_fee']>0) {
                        $total_express_fee += $marketOrder['total_express_fee'];
                    }
                    // 处理下每个订单价格问题
                    if ($wholesale_discount>0 && $wholesale_discount<100) {
                        $wholesale_discount_money = $wholesale_discount;
                        $marketOrder['actual_money'] = $marketOrder['money'];
                        $marketOrder['money'] = round($marketOrder['money']*$wholesale_discount*0.01, 2);
                    } else {
                        $marketOrder['actual_money'] = $marketOrder['money'];
                        $wholesale_discount_money = 100;
                    }
                    $marketOrder['system_discount_info'] = $wholesale_discount_money;

                    $marketOrder['last_time'] = time();
                    $marketOrder['status'] = 10;
                    if (!$marketOrder['username'] && $username) {
                        $marketOrder['username'] = $username;
                    }
                    if (!$marketOrder['userphone'] && $userphone) {
                        $marketOrder['userphone'] = $userphone;
                    }
                    if (!$marketOrder['address'] && $address) {
                        $marketOrder['address'] = $address;
                    }
                    $marketOrder['desc'] = $desc;
                    $marketOrder['create_time'] = time();
                    $orderList[] = $marketOrder;

                    // 如果允许自提获取自提点信息
                    if ($marketOrder['is_pick_in_store'] == 0 && $marketGoods['is_pick'] == 1 && $marketGoods['pick_info']) {
                        $pick_info = explode(',', $marketGoods['pick_info']);
                        $pick_list = D('Pick_address')->get_pick_addr_by_merid($marketGoods['mer_id'], true, 0, true, $pick_info);
                        $cart['pick_address'] = $pick_list;
                        if (empty($pick_list)) {
                            $is_pick = false;
                        } else {
                            $is_pick = true;
                        }
                    }

                }
                $is_address = 0;
                if (!$is_pick && $is_type!=1 && $is_type!=2 && $is_type!=3) {
                    $is_address = 1; // 需要提交地址
                }
                if ($is_address > 0 && empty($marketOrder['username'])) {
                    exit(json_encode(array('errorCode' => 1001,'errorMsg'=>L_('收货人不能为空'))));
                }
                if ($is_address > 0 && empty($marketOrder['userphone'])) {
                    exit(json_encode(array('errorCode' => 1001,'errorMsg'=>L_('联系电话不能为空'))));
                }
                if ($is_address > 0 && empty($marketOrder['address'])) {
                    exit(json_encode(array('errorCode' => 1001,'errorMsg'=>L_('收货地址不能为空'))));
                }
                if ($wholesale_discount>0 && $wholesale_discount<100) {
                    $system_discount_info = $wholesale_discount/10; // 计算折扣
                    $discount_totalMoney = round($totalMoney*$wholesale_discount*0.01, 2);
                } else {
                    $system_discount_info = 10;
                    $discount_totalMoney = $totalMoney;
                }

                if ($orderList) {
                    $pay_type = $_POST['pay_type'] ? $_POST['pay_type'] : '';
                    //先生成父订单
                    $add_info = array(
                        'mer_id' => $this->merchant['mer_id'],
                        'province_id' => $province_id,
                        'city_id' => $city_id,
                        'area_id' => $area_id,
                        'status' => 0,
                        'money' => $totalMoney,
                        'total_express_fee' => $total_express_fee,
                        'system_discount_info' => $system_discount_info,
                        'discount_totalMoney' => $discount_totalMoney
                    );
                    $fid = $databaseMarketTotal->add($add_info);
                    if (empty($fid)) {
                        exit(json_encode(array('errorCode' => 1001,'errorMsg'=>L_('结算失败，稍后重试'))));
//                        $this->error(L_('结算失败，稍后重试'));
                    }
                    // 把实际要付的钱赋值给原来处理金额字段
                    $totalMoney = $discount_totalMoney;
                    foreach ($orderList as $marketOrder) {
                        $cartid = $marketOrder['cartid'];
                        unset($marketOrder['cartid']);
                        $marketOrder['fid'] = $fid;
                        $marketOrder['province_id'] = $province_id;
                        $marketOrder['city_id'] = $city_id;
                        $marketOrder['area_id'] = $area_id;
                        if ($order_id = $databaseMarketOrder->add($marketOrder)) {
                            $marketOrder['order_id'] = $order_id;
                            $resultList[] = $marketOrder;
                            if ($marketOrder['is_properties']) {
                                $properties = M('Market_goods_properties')->field(true)->where(array('goods_id' => $marketOrder['goods_id']))->select();
                                foreach ($properties as $pro_data) {
                                    $pro_data['order_id'] = $order_id;
                                    M('Market_order_properties')->add($pro_data);
                                }
                            }

                            if ($marketOrder['spec_value']) {
                                $spec_list = M('Market_goods_spec')->field(true)->where(array('goods_id' => $marketOrder['goods_id']))->select();
                                foreach ($spec_list as $spec) {
                                    $spec['order_id'] = $order_id;
                                    if (M('Market_order_spec')->add($spec)) {
                                        $spec_value_list = M('Market_goods_spec_value')->field(true)->where(array('sid' => $spec['id']))->select();
                                        foreach ($spec_value_list as $spec_value) {
                                            $spec_value['order_id'] = $order_id;
                                            M('Market_order_spec_value')->add($spec_value);
                                        }
                                    }
                                }
                            }
                            $databaseMarketCart->where(array('cartid' => $cartid))->delete();
                        } else {
                            exit(json_encode(array('errorCode' => 1001,'errorMsg'=>L_('下单失败！请重试！'))));
//                            $this->error(L_('下单失败！请重试！'));
                        }
                    }
                }
            } else {
                exit(json_encode(array('errorCode' => 1001,'errorMsg'=>L_('结算失败，稍后重试！'))));
//                $this->error(L_('结算失败，稍后重试'));
            }
        }
        $merchant = D('Merchant')->field('money, name, phone, mer_spread_code,mer_id,reg_time')->where(array('mer_id' => $this->merchant['mer_id']))->find();

        if($this->config['nmgzhcs_appid']){
            $merchant['money'] = 0;			// 不使用余额
        }

        $_total = $totalMoney = $totalMoney + $total_express_fee;
        $nowtime = date("ymdHis");
        $orderid = $nowtime.rand(10,99).sprintf("%06d",$this->merchant['mer_id']);
        if ($pay_type=='' && $totalMoney > $merchant['money']) {
            $money = floatval($marketOrder['money'] - $merchant['money']);
            if (empty($money)||$money <0 ||!is_numeric($money)) {
                $this->returnCode(1,'',L_('订单信息错误'));
            } else {
                $this->returnCode(1,'',L_('您的余额不足，请前往商家后台最少充值').$money.$this->config['Currency_txt']);
            }
        } elseif ($pay_type=='') {
            $returnData = D('Merchant_money_list')->use_money($this->merchant['mer_id'], $totalMoney, 'marketmulti', L_('购买批发市场的商品扣除商家余额'), $fid);
            if ($returnData['error_code']) {
                $this->error(L_('支付失败！请重试！'), U('buy_order'));
                exit();
            }
            foreach ($resultList as $marketOrder) {
                $order_id = $marketOrder['order_id'];
                $return = $databaseMarketOrder->format_spec_value($marketOrder['spec_value'], $marketOrder['goods_id'], $order_id);
                $marketOrder['list'] = isset($return['list']) ? $return['list'] : '';

                $where = array('goods_id' => $marketOrder['goods_id']);
                $marketGoods = $databaseMarketGoods->field(true)->where($where)->find();


                $marketGoodsChageData = array();
                if ($marketOrder['list']) {
                    $return = $databaseMarketGoods->format_spec_value($marketGoods['spec_value'], $marketGoods['goods_id']);
                    $marketGoods['list'] = isset($return['list']) ? $return['list'] : '';
                    $spec_array = explode('#', $marketGoods['spec_value']);
                    foreach ($marketOrder['list'] as $index => $row) {
                        $target_spec_value_array = array();
                        foreach ($spec_array as $str) {
                            $row_array = explode('|', $str);
                            $i = str_replace(':', '_', $row_array[0]);
                            if ($i == $index) {
                                $numArray = explode(':', $row_array[1]);
                                if ($numArray[3] != -1) {
                                    $numArray[3] = max(0, ($numArray[3] - $row['stock_num']));
                                }
                                $row_array[1] = implode(':', $numArray);
                            }
                            $target_spec_value_array[] = implode('|', $row_array);
                        }
                        $spec_array = $target_spec_value_array;
                        if ($row['stock_num'] > 0) {
                            $total_num += $row['stock_num'];
                        }
                    }
                    $marketGoodsChageData['spec_value'] = implode('#', $spec_array);
                } else {
                    $total_num += $marketOrder['num'];
                }

                $marketGoodsChageData['stock_num'] = max(0, ($marketGoods['stock_num'] - $marketOrder['num']));
                $marketGoodsChageData['sell_count'] = $marketGoods['sell_count'] + $marketOrder['num'];

                //修改订单状态
                $databaseMarketOrder->where(array('order_id' => $order_id))->save(array('pay_time' => time(), 'status' => 1));

                //更改批发市场中商品的库存
                $databaseMarketGoods->where(array('goods_id' => $marketOrder['goods_id']))->save($marketGoodsChageData);

                $sms_data = array('mer_id' => $this->merchant['mer_id'], 'store_id' => 0, 'type' => 'market');

                $sellmerchant = M('Merchant')->field('name, phone')->where(array('mer_id' => $marketOrder['sell_mer_id']))->find();
                $sms_data['uid'] = 0;
                $sms_data['mobile'] = $sellmerchant['phone'];
                $sms_data['sendto'] = 'merchant';
                $sms_data['type'] = 'market';
                $sms_data['content'] = L_('商家X1批发了X2共X3订单号：X4请您注意查看并处理!',array('X1'=>$merchant['name'],'X2'=>$marketOrder['name'],'X3'=>$marketOrder['num'] . $marketOrder['unit'],'X4'=>$marketOrder['order_id']));
                Sms::sendSms($sms_data);

                $sms_data['uid'] = 0;
                $sms_data['mobile'] = $merchant['phone'];;
                $sms_data['sendto'] = 'merchant';
                $sms_data['type'] = 'market';
                $sms_data['content'] = L_('您在X1批发了X2共X3订单号：X4已经完成支付。欢迎下次光临!',array('X1'=>$merchant['name'],'X2'=>$marketOrder['name'],'X3'=>$marketOrder['num'] . $marketOrder['unit'],'X4'=>$marketOrder['order_id']));
                Sms::sendSms($sms_data);
            }
            // 支付成功 查看是否存在推广者， 添加推广者分佣 添加开关控制
            if ($this->config['merchant_recommend_wholesale_switch'] == 1 && $merchant['mer_spread_code'] && $this->config['merchant_recommend_spread_ratio']>0) {
                $spread_merchant = M('Merchant')->where(array('mer_code' => $merchant['mer_spread_code'], 'mer' => array('neq', $this->merchant['mer_id'])))->find();
                if ($spread_merchant) {
                    $merchant_recommend_spread_ratio = floatval($this->config['merchant_recommend_spread_ratio'] * 0.01);
                    // 分佣不计算 配送费
                    $totalMoney = $totalMoney - $total_express_fee;

                    // 计算分佣金额
                    $spread_money = $totalMoney * $merchant_recommend_spread_ratio;
                    $spread_money = round($spread_money, 2); // 分佣金额

                    $desc = L_('您推广的X1商户在批发市场消费X2,您获得分佣',array('X1'=>$merchant['name'],'X2'=>$totalMoney.$this->config['Currency_txt']));
                    $order_info['money'] = $spread_money;
                    $order_info['order_type'] = 'market_spread';
                    $order_info['mer_id'] = $spread_merchant['mer_id'];
                    $order_info['order_id'] = $fid;
                    $order_info['desc'] = $desc;
                    if ($total_num > 0) {
                        $order_info['num'] = $total_num;
                    }
                    D('Merchant_money_list')->add_money($order_info);
                    // 同步记录到记录表格中
                    $order_info['spread_mer_id'] = $merchant['mer_id'];
                    $order_info['spread_mer_name'] = $merchant['name'];
                    $order_info['spread_time'] = $merchant['reg_time'];
                    $order_info['add_time'] = time();
                    $info = M('Merchant_recommend_merchant_record')->data($order_info)->add();
                }
            }
            $databaseMarketTotal->where(array('id' => $fid))->save(array('pay_time' => time(), 'status' => 1,'orderid' => $orderid));

            $this->returnCode(0,'',L_('支付成功！'));
        } elseif ($pay_type == 'weixinh5') {
            $data_tmp['pay_type'] = $pay_type;
            $data_tmp['order_type'] = 'markets';
            $data_tmp['order_id'] = $fid;
            $data_tmp['orderid'] = $orderid;
            $data_tmp['addtime'] = $nowtime;
            if(!D('Tmp_orderid')->add($data_tmp)){
                $this->returnCodeError(L_('更新失败，请联系管理员'));
            }
            $save_pay_id = $databaseMarketTotal->where(array("id"=>$fid))->save(array('orderid' => $orderid, 'pay_type' => $pay_type));
            if(!$save_pay_id){
                $this->returnCodeError(L_('更新失败，请联系管理员'));
            }

            // 微信h5支付
            $pay_class_name = ucfirst($pay_type);
            $import_result = import('@.ORG.pay.'.$pay_class_name);
            if(empty($import_result)){
                $this->returnCodeError(L_('系统管理员暂未开启该支付方式，请更换其他的支付方式'));
            }
            $now_order = D('Market_total')->field(true)->where(array("id"=>$fid))->find();
            if(empty($now_order)){
                $this->returnCodeError(L_('当前订单不存在'));
            }
            $pay_method = D('Market_order')->pay_method(true);

            //支付前保存在线支付金额
            $_service = 0;//支付手续费
            if($pay_method[$_POST['pay_type']]['config']['service_charge_open'] == '1'){
                $service_charge = $pay_method[$_POST['pay_type']]['config']['service_charge'];
                $_service = $totalMoney*($service_charge/100);
            }
            $totalMoney = floor(($totalMoney+$_service)*100)/100;//保留两位小数，但不四舍五入
            $payment_money_date['payment_money'] = $totalMoney;
            $payment_money_date['pay_type'] =  $pay_type;
            $payment_money_date['order_type'] = 'markets';
            $payment_money_date['order_id'] =  $fid;
            $payment_money_date['orderid'] =  $orderid;
            $payment_money_date['addtime'] = time();

            $payment_money_date['is_mobile_pay'] = $this->is_app ? 2 : ($this->is_wxapp ? 3 : 1);
            $payment_money_date['service_charge'] = $_service;//支付手续费

            M('Payment_money_order')->add($payment_money_date);


            $now_order['order_type'] = 'markets';
            $now_order['order_id'] = $orderid;
            $pay_class = new $pay_class_name($now_order,$totalMoney,$pay_type,$pay_method[$pay_type]['config'],$this->merchant,0);
            $go_pay_param = $pay_class->pay();

            if($go_pay_param['result_code'] == 'SUCCESS'){//如果这两个都为此状态则返回mweb_url，详情看‘统一下单’接口文档
                $mweb_url = $go_pay_param['mweb_url'];
                $this->returnCode(0, array('error' => true, 'msg' =>L_('支付跳转'),'jump' => 'mweb_url','url' => $mweb_url, 'total' => $_total, 'name' => $marketOrder['name']));
            } elseif($go_pay_param['result_code'] == 'FAIL'){
                $err_code_des = $go_pay_param['err_code_des'];
                $this->returnCodeError($err_code_des);
            } elseif ($go_pay_param['error']) {
                $this->returnCodeError($go_pay_param['msg']);
            }

        } elseif ($pay_type == 'weixin'){
            $data_tmp['pay_type'] = $pay_type;
            $data_tmp['order_type'] = 'markets';
            $data_tmp['order_id'] = $fid;
            $data_tmp['orderid'] = $orderid;
            $data_tmp['addtime'] = $nowtime;
            if(!D('Tmp_orderid')->add($data_tmp)){
                $this->returnCodeError(L_('更新失败，请联系管理员'));
            }
            $save_pay_id = $databaseMarketTotal->where(array("id"=>$fid))->save(array('orderid' => $orderid, 'pay_type' => $pay_type));
            if(!$save_pay_id){
                $this->returnCodeError(L_('更新失败，请联系管理员'));
            }
            // 微信h5支付
            $pay_method = D('Market_order')->pay_method(true);
            if(strpos($_SERVER['HTTP_USER_AGENT'],'MicroMessenger') !== false){
                $pay_method_all = D('Config')->get_pay_method(0,0);
                if ($pay_method_all['weixin']) {
                    $pay_method['weixin'] = $pay_method_all['weixin'];
                }
                unset($pay_method['weixinh5']);
            }

            //支付前保存在线支付金额
            $_service = 0;//支付手续费
            if($pay_method[$_POST['pay_type']]['config']['service_charge_open'] == '1'){
                $service_charge = $pay_method[$_POST['pay_type']]['config']['service_charge'];
                $_service = $totalMoney*($service_charge/100);
            }
            $totalMoney = floor(($totalMoney+$_service)*100)/100;//保留两位小数，但不四舍五入
            $payment_money_date['payment_money'] = $totalMoney;
            $payment_money_date['pay_type'] =  $pay_type;
            $payment_money_date['order_type'] = 'markets';
            $payment_money_date['order_id'] =  $fid;
            $payment_money_date['orderid'] =  $orderid;
            $payment_money_date['addtime'] = time();

            $payment_money_date['is_mobile_pay'] = $this->is_app ? 2 : ($this->is_wxapp ? 3 : 1);
            $payment_money_date['service_charge'] = $_service;//支付手续费

            M('Payment_money_order')->add($payment_money_date);

            $pay_class_name = ucfirst($pay_type);
            $import_result = import('@.ORG.pay.'.$pay_class_name);
            if(empty($import_result)){
                $this->returnCodeError(L_('系统管理员暂未开启该支付方式，请更换其他的支付方式'));
            }

            $now_order = D('Market_total')->field(true)->where(array("id"=>$fid))->find();
            if(empty($now_order)){
                $this->returnCodeError(L_('当前订单不存在'));
            }
            $now_order['order_id'] = $orderid;
            $now_order['order_type'] = 'markets';
            $now_order['order_name'] = $total_name;

            if ($_POST['openid']) {
                $_SESSION['openid'] = $_POST['openid'];
            } else {
                $this->returnCode(1,'',L_('微信支付需要先绑定微信'));
            }

            $pay_class = new $pay_class_name($now_order,$totalMoney,$pay_type,$pay_method[$pay_type]['config'],$this->merchant,1);
            $go_pay_param = $pay_class->pay();

            if (!empty($go_pay_param['weixin_param'])) {
                if ($pay_method['weixin']['config']['is_own']) {
                    C('open_authorize_wxpay', true);
                    $share = new WechatShare($this->config, $_SESSION['openid']);
                    $this->hideScript = $share->gethideOptionMenu($this->merchant['mer_id']);
                    $arr['hidScript'] = $this->hideScript;
                    $redirctUrl = C('config.site_url') . '/' . $this->indep_house . '?g=Appapi&c=Merchantapp&a=weixin_back&order_type=' . $now_order['order_type'] . '&order_id=' . $now_order['order_id'] . '&own_mer_id=' . $now_order['mer_id'];
                } else {
                    $redirctUrl = C('config.site_url') . '/' . $this->indep_house . '?g=Appapi&c=Merchantapp&a=weixin_back&order_type=' . $now_order['order_type'] . '&order_id=' . $now_order['order_id'];
                }
                $arr['redirctUrl'] = $go_pay_param['redirctUrl'] ? $go_pay_param['redirctUrl'] : $redirctUrl;
                $arr['pay_money'] = $_total;
                $arr['weixin_param'] = json_decode($go_pay_param['weixin_param']);
                $arr['error'] = false;
                $this->returnCode(0, $arr);
            } else {
                $this->returnCode(1,'',$go_pay_param['msg']);
            }
        } elseif($pay_type=='offline') {
            foreach ($resultList as $marketOrder) {
                $order_id = $marketOrder['order_id'];
                $set_data = array(
                    'offline_money' => $marketOrder['money'] + $marketOrder['total_express_fee'],
                    'status' => 1,
                    'offline_status' => 0,
                    'is_mobile_pay' => 0,
                    'order_id' => $orderid,
                    'last_time' => time()
                );
                $databaseMarketOrder->field(true)->where(array('order_id' => $order_id))->data($set_data)->save();
            }
            $this->returnCode(0,'',L_('支付成功！'));
        } else {
            $this->returnCode(1,'',L_('您选择的支付方式不存在，请更新支付方式！'));
        }

    }

    public function delCart()
    {
        $cartid = isset($_POST['cart_id']) ? intval($_POST['cart_id']) : 0;
        if ($cartid == -1) {
            D('Market_cart')->field(true)->where(array('mer_id' => $this->merchant['mer_id']))->delete();
            $this->returnCode(0,$cartid);
        } else {
            if ($cart = D('Market_cart')->field(true)->where(array('cartid' => $cartid, 'mer_id' => $this->merchant['mer_id']))->find()) {
                D('Market_cart')->field(true)->where(array('cartid' => $cartid, 'mer_id' => $this->merchant['mer_id']))->delete();
                $this->returnCode(0,$cartid);
            } else {
                $this->error(L_('参数错误'));
            }
        }
    }

    // 获取自提地址
    public function pick_address()
    {
        $arr = array();
        $mer_id = $this->merchant['mer_id'];

        $goodsId = isset($_POST['goods_id']) ? intval($_POST['goods_id']) : 0;
        $cartid = isset($_POST['cartid']) ? intval($_POST['cartid']) : 0;
        if ($cartid > 0) {
            $cart = M('Market_cart')->where(array('cartid' => $cartid))->find();
            $goodsId = $cart['goods_id'];
        }

        $databaseMarketGoods = D('Market_goods');
        $where = array('goods_id' => $goodsId);
        $marketGoods = $databaseMarketGoods->field(true)->where($where)->find();
        $pick_info = explode(',', $marketGoods['pick_info']);
        $pick_list = D('Pick_address')->get_pick_addr_by_merid($marketGoods['mer_id'], true);

        if (!empty($_POST['type']) && $_POST['type']=='market_cart') {
            $select_url = 'market_cart';
        } elseif (!empty($_POST['type']) && $_POST['type']=='market_buy' && $_POST['goods_id']) {
            $select_url = 'market_buy';
            $arr['goods_id'] = $_POST['goods_id'];
        } elseif (!empty($_POST['type']) && $_POST['type']=='market_detail' && $_POST['order_id']) {
            $select_url = 'market_detail';
            $arr['goods_id'] = $_POST['goods_id'];
            $arr['order_id'] = $_POST['order_id'];
        }

        foreach ($pick_list as $key => $value) {
            if ($pick_info && $value['pick_addr_id'] && !in_array($value['pick_addr_id'], $pick_info)) {
                unset($pick_list[$key]);
                continue;
            }
            if (!empty($select_url)) {
                $pick_list[$key]['select_url'] = $select_url;
            }
            if (!empty($_POST['current_id'])) {
                $pick_list[$key]['current_id'] = $_POST['current_id'];
            }
            if (!empty($_POST['pick_id'])) {
                $pick_list[$key]['pick_id'] = $_POST['pick_id'];
            }
        }
        $arr['pick_list'] = array_values($pick_list);
        $arr['select_url'] = $select_url ? $select_url : '';
        $arr['current_id'] = $_POST['current_id'] ? $_POST['current_id'] : 0;
        $arr['is_pick'] = $_POST['is_pick'] ? $_POST['is_pick'] : 0;
        $arr['pick_id'] = $_POST['pick_id'] ? $_POST['pick_id'] : 0;
        $this->returnCode(0,$arr);
    }

    // 获取我的地址
    public function address()
    {
        $arr = array();
        $mer_id = $this->merchant['mer_id'];

        $address_list = D('Merchant_adress')->get_adress_list($mer_id);

        if (!empty($_POST['type']) && $_POST['type']=='market_cart') {
            $select_url = 'market_cart';
        } elseif (!empty($_POST['type']) && $_POST['type']=='market_buy' && $_POST['goods_id']) {
            $select_url = 'market_buy';
            $arr['goods_id'] = $_POST['goods_id'];
        } elseif (!empty($_POST['type']) && $_POST['type']=='market_detail' && $_POST['order_id']) {
            $select_url = 'market_detail';
            $arr['goods_id'] = $_POST['goods_id'];
            $arr['order_id'] = $_POST['order_id'];
        }

        foreach ($address_list as $key => $value) {
            $param['adress_id'] = $value['adress_id'];
            if (!empty($select_url)) {
                $address_list[$key]['select_url'] = $select_url;
            }
            if (!empty($_POST['current_id'])) {
                $address_list[$key]['current_id'] = $_POST['current_id'];
            }
            $address_list[$key]['edit_url'] = 'edit_address';
            $address_list[$key]['del_url'] = 'del_adress';
        }
        $arr['address_list'] = $address_list;
        $arr['select_url'] = $select_url ? $select_url : '';
        $arr['current_id'] = $_POST['current_id'] ? $_POST['current_id'] : 0;
        $this->returnCode(0,$arr);
    }

    // 详细信息
    public function edit_address_info() {;
        $mer_id = $this->merchant['mer_id'];
        if($_POST['adress_id']){
            $id = $_POST['adress_id'];
            $now_adress = D('Merchant_adress')->get_adress($mer_id, $id);
            $this->returnCode(0,$now_adress);

        } else {
            $this->returnCode(1,'',L_('参数错误！请重试！'));
        }
    }

    // 添加/编辑地址
    public function edit_address() {;
        $mer_id = $this->merchant['mer_id'];
        if(IS_POST){
            if(empty($_POST['adress']) || empty($_POST['longitude']) || empty($_POST['latitude'])){
                $this->returnCode(1,'',L_('您的位置没有选择！请点击选择位置进行完善！'));
            }
            if(D('Merchant_adress')->post_form_save($mer_id) !== false){
                $param['type'] =$_POST['type'];
                $param['goods_id'] =$_POST['goods_id'];
                $param['order_id'] =$_POST['order_id'];

                $this->returnCode(0,$param);
            }else{
                $this->returnCode(1,'',L_('地址保存失败！请重试！'));
            }
        }else {
            $this->returnCode(1,'',L_('参数错误！请重试！'));
        }
    }

    /*删除地址*/
    public function del_address(){
        $mer_id = $this->merchant['mer_id'];
        $result = D('Merchant_adress')->delete_adress($mer_id,$_POST['adress_id']);
        if($result){
            $param['type'] =$_POST['type'];
            $param['goods_id'] =$_POST['goods_id'];
            $param['order_id'] =$_POST['order_id'];

            $this->returnCode(0,$param);
        }else{
            $this->returnCode(1,'',L_('删除失败！'));
        }
    }


    //微信同步回调页面
    public function weixin_back(){
        switch($_GET['order_type']){
            case 'market':
                $now_order = $this->get_orderid('Market_order', $_GET['order_id']);
                break;
            case 'markets':
                $now_order = $this->get_orderid('Market_total',$_GET['order_id']);
                break;
            default:
                $this->returnCode(1,'',L_('非法的订单！'));
        }


        if(empty($now_order)){
            $this->returnCode(1,'',L_('该订单不存在！'));
        }
        $now_order['order_type'] = $_GET['order_type'];
        if($now_order['status']>1 && $now_order['status']!=10 &&$now_order['order_type']!='balance-appoint'){
            switch($_GET['order_type']){
                case 'market':
                    $redirctUrl = C('config.site_url').'/packapp/merchant/market_detail.html?type=buy&order_id='.$now_order['order_id'];
                    break;
                case 'markets':
                    $redirctUrl = C('config.site_url').'/packapp/merchant/my_buy_wholesale_order.html';
                    break;
            }
            redirect($redirctUrl);exit;
        }

        $pay_method = D('Market_order')->pay_method(true);
        if(strpos($_SERVER['HTTP_USER_AGENT'],'MicroMessenger') !== false){
            $pay_method_all = D('Config')->get_pay_method(0,0);
            if ($pay_method_all['weixin']) {
                $pay_method['weixin'] = $pay_method_all['weixin'];
            }
        }

        $_GET['order_id'] = $now_order['order_id'];
        $now_order['order_id']  =   $now_order['orderid'];
        $import_result = import('@.ORG.pay.Weixin');
        if(empty($pay_method)){
            $this->returnCode(1,'',L_('系统管理员没开启任意一种支付方式！'));
        }
        $pay_class = new Weixin($now_order,0,'weixin',$pay_method['weixin']['config'],$this->merchant,1);
        $go_query_param = $pay_class->query_order();



        if($go_query_param['error'] === 0){
            //判断回调金额
            if($payment_money_order = M('Payment_money_order')->where(array('orderid'=>$now_order['order_id'],'order_type'=>$now_order['order_type']))->find()){
                M('Payment_money_order')->where(array('orderid'=>$now_order['order_id'],'order_type'=>$now_order['order_type']))->setField('paid_money',$go_query_param['order_param']['pay_money']);
            }else{
                $this->returnCode(1,'',L_('支付异常！'));
            }

            $go_query_param['order_param']['return']=1;
            switch($_GET['order_type']){
                case 'market':
                    D('Market_order')->market_after_pay($go_query_param['order_param']);
                    break;
                case 'markets':
                    D('Market_order')->market_total_after_pay($go_query_param['order_param']);
                    break;
            }
        }
        switch($_GET['order_type']){
            case 'market':
                $redirctUrl = C('config.site_url').'/packapp/merchant/market_detail.html?type=buy&order_id='.$_GET['order_id'];
                break;
            case 'markets':
                $redirctUrl = C('config.site_url').'/packapp/merchant/my_buy_wholesale_order.html';
                break;
        }
        if($go_query_param['error'] == 1){
            $this->returnCode(1,'',L_('校验时发生错误！') . $go_query_param['msg']);
        }else{
            redirect($redirctUrl);
        }
    }

    //跳转通知
    public function return_url(){
        $pay_type = isset($_GET['paytype']) ? $_GET['paytype'] : $_GET['pay_type'];
        $pay_method = D('Market_order')->pay_method(true);
        if(strpos($_SERVER['HTTP_USER_AGENT'],'MicroMessenger') !== false){
            $pay_method_all = D('Config')->get_pay_method(0,0);
            if ($pay_method_all['weixin']) {
                $pay_method['weixin'] = $pay_method_all['weixin'];
            }
        }
        if(empty($pay_method)){
            $this->returnCode(1,'',L_('系统管理员没开启任意一种支付方式！'));
        }
        if(empty($pay_method[$pay_type])){
            $this->returnCode(1,'',L_('您选择的支付方式不存在，请更新支付方式！'));
        }

        $pay_class_name = ucfirst($pay_type);
        $import_result = import('@.ORG.pay.'.$pay_class_name);
        if(empty($import_result)){
            $this->returnCode(1,'',L_('系统管理员暂未开启该支付方式，请更换其他的支付方式！'));
        }
        $fileContent = file_get_contents("php://input");
        $xml_data = json_decode(json_encode(simplexml_load_string($fileContent, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        $out_trade_no  = explode('_',$xml_data['out_trade_no']);
        $trade_type = $out_trade_no[0];
        $orderid = $out_trade_no[1];
        $is_own = $out_trade_no[2];


        $pay_class = new $pay_class_name('','',$pay_type,$pay_method[$pay_type]['config'],$this->merchant,1);
        $get_pay_param = $pay_class->return_url();


        $get_pay_param['order_param']['return']=1;
        $offline = $pay_type!='offline'?false:true;

        if(empty($get_pay_param['error'])){

            //在线支付回调判断是否与支付前金额相等
            if($pay_type!='offline'){

                if($payment_money_order = M('Payment_money_order')->where(array('orderid'=>$get_pay_param['order_param']['order_id'],'order_type'=>$get_pay_param['order_param']['order_type']))->find()){
                    M('Payment_money_order')->where(array('orderid'=>$get_pay_param['order_param']['order_id'],'order_type'=>$get_pay_param['order_param']['order_type']))->setField('paid_money',$get_pay_param['order_param']['pay_money']);
                }else{
                    $this->returnCode(1,'',L_('支付异常！'));
                }
            }

            if($get_pay_param['order_param']['order_type'] == 'market'){
                $now_order = $this->get_orderid('Market_order', $get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Market_order')->market_after_pay($get_pay_param['order_param']);
            }else if($get_pay_param['order_param']['order_type'] == 'markets'){
                $now_order = $this->get_orderid('Market_total', $get_pay_param['order_param']['order_id'],$offline);
                $get_pay_param['order_param']['order_id'] = $now_order['orderid'];
                $pay_info = D('Market_order')->market_total_after_pay($get_pay_param['order_param']);
            }else{
                $this->returnCode(1,'',L_('订单类型非法！请重新下单'));
            }
            $urltype = isset($_GET['urltype']) ? $_GET['urltype'] : '';
            if(empty($pay_info['error'])){
                if($get_pay_param['order_param']['pay_type'] == 'weixin'){
                    exit('<xml><return_code><![CDATA[SUCCESS]]></return_code></xml>');
                } elseif ($get_pay_param['order_param']['pay_type'] == 'baidu') {//百度的异步通知返回
                    exit("<html><head><meta name=\"VIP_BFB_PAYMENT\" content=\"BAIFUBAO\"></head><body><h1>这是一个return_url页面</h1></body></html>");
                } elseif ('unionpay' == $get_pay_param['order_param']['pay_type'] && $urltype == 'back') {
                    exit("验签成功");
                } elseif ('weifutong' == $get_pay_param['order_param']['pay_type'] && $urltype == 'back') {
                    exit("success");
                }
                $pay_info['msg'] = '订单付款成功！现在跳转.';
            }
            if(empty($pay_info['url'])){
                $this->returnCodeError($pay_info['msg']);
            }else{
                if(cookie('is_house')){
                    $pay_info['url'] = str_replace('wap.php',C('INDEP_HOUSE_URL'),$pay_info['url']);
                }else{
                    $pay_info['url'] = preg_replace('#/source/(\w+).php#','/wap.php',$pay_info['url']);
                }
                if ($pay_info['error'] && $urltype == 'front') {
                    $this->redirect($pay_info['url']);
                    exit;
                }
                $this->assign('pay_info', $pay_info);
                $this->display('after_pay');
            }
        }else{
            $this->returnCodeError($get_pay_param['msg']);
        }
    }

    public function get_orderid($table,$orderid,$offline=0){
        $order =  D($table);
        $tmp_orderid = D('Tmp_orderid');
        if($offline){
            $now_order = $order->where(array('orderid'=>$orderid))->find();
        }else{
            $now_order = $order->where(array('orderid'=>$orderid))->find();
            if(empty($now_order)){
                $res = $tmp_orderid->where(array('orderid'=>$orderid))->find();
                $now_order = $order->where(array('order_id'=>$res['order_id']))->find();
                $order->where(array('order_id'=>$res['order_id']))->setField('orderid',$orderid);
                $now_order['orderid']=$orderid;
            }
        }
        if(empty($now_order)){
            $this->returnCode(1,'',L_('该订单不存在'));
        }else{
//            $tmp_orderid->where(array('order_id'=>$now_order['order_id']))->delete();
        }

        return $now_order;
    }


    /**批发市场*/
    /**我发发布的商品*/
    public function my_market_goods() {
        $mer_id = $this->merchant['mer_id'];
        $status = $_POST['status']?$_POST['status']:-1; // -1 查询全部  -2 查询待支付(为了考虑兼容旧数据 0 不做状态查询) 1:支付成，2：发货成功，3：收货成功，4:已退款，10：下单成功
        $data = array(
            'mer_id' => $mer_id,
            'status' => $status
        );
        $d_market_goods = D('Market_goods');
        $_GET['page'] = $_POST['page'];
        $result = $d_market_goods->getList($data, 'app_my');
        if ($_POST['page'] && $_POST['page']>$result['totalPage']) {
            $result['orders'] = array();
        }

        // 配置项信息
        $config = array(
            'Currency_txt' => $this->config['Currency_txt'],
            'Currency_symbol' => $this->config['Currency_symbol'],
        );
        $result['config'] = $config;


        $this->returnCode(0, $result);
    }

    /**获取商品详情*/
    public function market_goods_detail() {
        $goods_id = intval($_POST['goods_id']);
        $database_shop_goods = D('Shop_goods');
        $where = array('goods_id' => $goods_id);
        $now_goods = $database_shop_goods->field(true)->where($where)->find();
        if(empty($now_goods)){
            $this->returnCode(1,'',L_('商品不存在！'));
        }
        $now_store = $this->check_store($now_goods['store_id']);

        if(!empty($now_goods['image'])){
            $goods_image_class = new goods_image();
            $tmp_pic_arr = explode(';', $now_goods['image']);
            foreach ($tmp_pic_arr as $key => $value) {
                $now_goods['pic_arr'][$key]['title'] = $value;
                $now_goods['pic_arr'][$key]['url'] = $goods_image_class->get_image_by_path($value, 's');
            }
        }
        $return = $database_shop_goods->format_spec_value($now_goods['spec_value'], $now_goods['goods_id']);

        $now_goods['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
        $now_goods['list'] = isset($return['list']) ? $return['list'] : '';
        $now_goods['discount_info'] = '';
        $now_goods['status'] = 1;
        if ($marketGoodsOld = M('Market_goods')->where($where)->find()) {
            if ($marketGoodsOld['spec_value'] && $now_goods['list']) {
                $spec_array = explode('#', $marketGoodsOld['spec_value']);
                foreach ($spec_array as $str) {
                    $row_array = explode('|', $str);
                    $i = str_replace(':', '_', $row_array[0]);
                    if (isset($now_goods['list'][$i])) {
                        $tArray = explode(':', $row_array[1]);
                        $now_goods['list'][$i]['wholesale_prices'] = $tArray[1];
                        $now_goods['list'][$i]['min_nums'] = $tArray[2];
                        $now_goods['list'][$i]['stocks'] = $tArray[3];
                        $now_goods['list'][$i]['group_team_prices'] = $tArray[5];
                        $now_goods['list'][$i]['group_join_prices'] = $tArray[6];
                        $now_goods['list'][$i]['vip_wholesale_prices'] = $tArray[7];
                        $now_goods['list'][$i]['goods_weights'] = $tArray[8];
                        $now_goods['list'][$i]['goods_volumes'] = $tArray[9];
                    }
                }
            }
            if ($now_goods['pic_arr'] && $now_goods['pic_arr'][0] && $now_goods['pic_arr'][0]['url']) {
                $now_goods['pic'] = $now_goods['pic_arr'][0]['url'];
            } elseif ($now_goods['pic']) {
                $tmp_pic_arr = explode(';', $now_goods['pic']);
                $group_image_class = new group_image();
                $now_goods['pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0], 's');
            }

            $now_goods['mer_id'] = $marketGoodsOld['mer_id'];
            $now_goods['wholesale_price'] = $marketGoodsOld['price'];
            $now_goods['vip_wholesale_price'] = $marketGoodsOld['vip_wholesale_price'];
            $now_goods['min_num'] = $marketGoodsOld['min_num'];
            $now_goods['stock'] = $marketGoodsOld['stock_num'];
            $now_goods['discount_info'] = json_decode($marketGoodsOld['discount_info'], true);
            $now_goods['status'] = $marketGoodsOld['status'];
            $now_goods['cat_fid'] = $marketGoodsOld['cat_fid'];
            $now_goods['cat_id'] = $marketGoodsOld['cat_id'];
            // 是否加入白名单
            $now_goods['is_add_white_list'] = $marketGoodsOld['is_add_white_list'];
            /*拼团活动的配置信息*/
            $now_goods['is_group'] = $marketGoodsOld['is_group'];
            $now_goods['group_team_price'] = $marketGoodsOld['group_team_price'];
            $now_goods['group_join_price'] = $marketGoodsOld['group_join_price'];
            $now_goods['group_stime'] = $marketGoodsOld['group_stime'];
            $now_goods['group_ftime'] = $marketGoodsOld['group_ftime'];

            $day=floor($marketGoodsOld['group_affect_time']/(24*3600));
            $hour=floor(($marketGoodsOld['group_affect_time']-$day*24*3600)/3600);
            $minute=floor(($marketGoodsOld['group_affect_time']%3600)/60);

            $now_goods['day'] = $day;
            $now_goods['hour'] = $hour;
            $now_goods['minute'] = $minute;
            $now_goods['group_complete_num'] = $marketGoodsOld['group_complete_num'];
            $now_goods['min_unit'] = $marketGoodsOld['min_unit'];
            // 配送费计算方式
            $now_goods['freight_type'] = $marketGoodsOld['freight_type'];
            $now_goods['goods_weight'] = $marketGoodsOld['goods_weight'];
            $now_goods['goods_volume'] = $marketGoodsOld['goods_volume'];
            $now_goods['express_template_id'] = $marketGoodsOld['express_template_id'];
            $now_goods['express_fee'] = $marketGoodsOld['express_fee'];
            // 配送方式
            $now_goods['is_pick'] = $marketGoodsOld['is_pick'];
            $now_goods['pick_info'] = explode(',', $marketGoodsOld['pick_info']);
        }

        // 运费模板信息
        if (1==$this->config['merchant_integral_mall_control'] && $now_goods['express_template_id']>0) {
            $where_express['id'] = $now_goods['express_template_id'];
            $express_info = M('Market_express_template')->where($where_express)->find();
            if ($express_info) {
                $template_msgs = D('Market_express_template_msg')->field(true)->where(array('tid' => $now_goods['express_template_id']))->select();
                if ($template_msgs) {
                    $express_info['template_msgs'] = $template_msgs;
                }
                $is_parameter = 1;
                if ($express_info['template_msgs']) {
                    $ii = 1;
                    foreach ($express_info['template_msgs'] as &$item) {
                        $template_value = D('Market_express_template_value')->where(array('tid' => $now_goods['express_template_id'], 'tmid' => $item['id']))->find();
                        $item['template_value'] = $template_value;
                        $template_value['first_weight'] = floatval($template_value['first_weight']);
                        $template_value['first_freight'] = floatval($template_value['first_freight']);
                        $template_value['add_weight'] = floatval($template_value['add_weight']);
                        $template_value['add_weight'] = floatval($template_value['add_weight']);
                        $template_value['full_money'] = floatval($template_value['full_money']);
                        $title = $item['type']==2?'物流':'快递';
                        if ($item['freight_type'] == 1) {
                            $item['express_txt'] .= "<div style='color: red;'>(".$ii.") ".$title."运费标准：";
                            $first = false;
                            if ($template_value['first_weight']>0 && $template_value['first_freight']) {
                                $item['express_txt'] .= "起步价{$template_value['first_weight']}{$this->config['Currency_txt']}({$template_value['first_freight']}件以内)";
                                $first = true;
                            }
                            if ($template_value['add_weight']>0 && $template_value['add_weight']) {
                                if ($first) {
                                    $item['express_txt'] .= "，每增加{$template_value['add_weight']}件运费增加{$template_value['add_weight']}{$this->config['Currency_txt']}";
                                } else {
                                    $item['express_txt'] .= "每增加{$template_value['add_weight']}件运费增加{$template_value['add_weight']}{$this->config['Currency_txt']}";
                                }
                            }

                            if ($template_value['full_money']>0) {
                                $item['express_txt'] .= "，满{$template_value['full_money']}{$this->config['Currency_txt']}免运费";
                            }
                            $item['express_txt'] .= "</div>";
                            $ii++;
                        } elseif ($item['freight_type'] == 2) {
                            $item['express_txt'] .= "<div style='color: red;'>(".$ii.") ".$title."运费标准：";
                            $first = false;
                            if ($template_value['first_weight']>0 && $template_value['first_freight']) {
                                $item['express_txt'] .= "起步价{$template_value['first_weight']}{$this->config['Currency_txt']}({$template_value['first_freight']}公斤以内)";
                                $first = true;
                            }
                            if ($template_value['add_weight']>0 && $template_value['add_weight']) {
                                if ($first) {
                                    $item['express_txt'] .= "，每增加{$template_value['add_weight']}公斤运费增加{$template_value['add_weight']}{$this->config['Currency_txt']}";
                                } else {
                                    $item['express_txt'] .= "每增加{$template_value['add_weight']}公斤运费增加{$template_value['add_weight']}{$this->config['Currency_txt']}";
                                }
                                $first = true;
                            }

                            if ($template_value['full_money']>0) {
                                $item['express_txt'] .= "，满{$template_value['full_money']}{$this->config['Currency_txt']}免运费";
                            }
                            $item['express_txt'] .= "</div>";
                            $ii++;
                            if ($is_parameter==3) {
                                $is_parameter = 5; // 既有重量也有体积
                            } elseif ($is_parameter<5) {
                                $is_parameter = 2; // 按照重量计数
                            }
                        } elseif ($item['freight_type'] == 3) {
                            $item['express_txt'] .= "<div style='color: red;'>(".$ii.") ".$title."运费标准：";
                            if ($template_value['freight']>0) {
                                $item['express_txt'] .= "{$template_value['freight']}{$this->config['Currency_txt']}每立方米";
                            }

                            if ($template_value['full_money']>0) {
                                $item['express_txt'] .= "，满{$template_value['full_money']}{$this->config['Currency_txt']}免运费";
                            }
                            $item['express_txt'] .= "</div>";
                            $ii++;
                            if ($is_parameter==2) {
                                $is_parameter = 5; // 既有重量也有体积
                            } elseif ($is_parameter<5) {
                                $is_parameter = 3; // 按照体积计数
                            }
                        }
                    }
                }
                $arr['express_list'] = $express_info;
                $arr['is_parameter'] = $is_parameter;
            }
        }
        // 配置项信息
        $config = array(
            'Currency_txt' => $this->config['Currency_txt'],
            'Currency_symbol' => $this->config['Currency_symbol'],
        );
        // 添加 【商家类型功能】开关   0 关 1 开
        $config['merchant_type_switch'] = $this->config['merchant_type_switch']?$this->config['merchant_type_switch']:0;
        $arr['config'] = $config;
        // 获取分类信息
        //所有分类
        $database_shop_category = D('Shop_category');
        if ($now_goods['cat_fid']>0) {
            $now_goods['cat_fname'] = $database_shop_category->where(array('cat_id' => $now_goods['cat_fid']))->getField('cat_name');
        }
        if ($now_goods['cat_id']>0) {
            $now_goods['cat_name'] = $database_shop_category->where(array('cat_id' => $now_goods['cat_id']))->getField('cat_name');
        }
        $pick_info =  $now_goods['pick_info'];
        if ($pick_info && reset($pick_info)) {
            $pick_list = D('Pick_address')->get_pick_addr_by_merid($now_goods['mer_id'], true, 0, true, $pick_info);
            $arr['pick_list'] = $pick_list;
        }

        $arr['now_goods'] = $now_goods;


        // 获取白名单信息
        if ($now_goods && $now_goods['white_list']) {
            $white_list = explode(',', $now_goods['white_list']);
            $merchant_info=M('Merchant')->where(array('mer_id' => array('in',  $now_goods['white_list'])))->getField('mer_id, name, phone');

            if ($white_list) {
                foreach ($white_list as $val) {
                    $white_msg[]=$merchant_info[$val];
                }
            } else {
                $white_msg = '';
            }
            $arr['white_msg'] = $white_msg;
        }


        $this->returnCode(0, $arr);
    }

    /* 检测店铺存在，并检测是不是归属于商家 */
    protected function check_store($store_id)
    {
        if ($this->merchant['store_id'] && $this->merchant['store_id'] != $store_id) {
            $this->returnCode(1,'',L_('当前商品您不能查看！'));
        }

        $database_merchant_store = D(_view('Merchant_store'));
        $condition_merchant_store['store_id'] = $store_id;
        $condition_merchant_store['mer_id'] = $this->merchant['mer_id'];
        $now_store = $database_merchant_store->field(true)->where($condition_merchant_store)->find();
        if (empty($now_store)) {
            $this->returnCode(1,'',L_('对应店铺不存在！'));
        } else {
            if ($now_shop = D(_view('Merchant_store_shop'))->field(true)->where($condition_merchant_store)->find()) {
                if (!empty($now_shop['background'])) {
                    $image_tmp = explode(',', $now_shop['background']);
                    $now_shop['background_image'] = file_domain() . '/upload/background/' . $image_tmp[0] . '/' . $image_tmp['1'];
                }
                return array_merge($now_store, $now_shop);
            }
            return $now_store;
        }
    }




    /**
     * 将商品发布到批发市场
     */
    public function market_goods_push()
    {
        $now_merchant = M('Merchant')->where(array('mer_id' => $this->merchant['mer_id']))->field('is_publish_market_goods')->find();
        $is_publish_market_goods = $now_merchant['is_publish_market_goods']?$now_merchant['is_publish_market_goods']:0;
        if ($is_publish_market_goods == 1) {
            $this->returnCode(1,'',L_('当前您不能发布商品，如需发布请联系管理员！'));
        }
        $goodsId = intval($_POST['goods_id']);
        $database_shop_goods = D('Shop_goods');
        $where = array('goods_id' => $goodsId);
        $now_goods = $database_shop_goods->field(true)->where($where)->find();
        if(empty($now_goods)){
            $this->returnCode(1,'',L_('商品不存在！'));
        }
        if ($now_goods['original_goods_id']) {
            $this->returnCode(1,'',L_('该商品不能发布！'));
        }
        $now_store = $this->check_store($now_goods['store_id']);

        if(!empty($now_goods['image'])){
            $goods_image_class = new goods_image();
            $tmp_pic_arr = explode(';', $now_goods['image']);
            foreach ($tmp_pic_arr as $key => $value) {
                $now_goods['pic_arr'][$key]['title'] = $value;
                $now_goods['pic_arr'][$key]['url'] = $goods_image_class->get_image_by_path($value, 's');
            }
        }

        $return = $database_shop_goods->format_spec_value($now_goods['spec_value'], $now_goods['goods_id']);

        $now_goods['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
        $now_goods['list'] = isset($return['list']) ? $return['list'] : '';
        $now_goods['discount_info'] = '';
        $now_goods['status'] = 1;
        if ($marketGoodsOld = M('Market_goods')->where($where)->find()) {
            if ($marketGoodsOld['spec_value'] && $now_goods['list']) {
                $spec_array = explode('#', $marketGoodsOld['spec_value']);
                foreach ($spec_array as $str) {
                    $row_array = explode('|', $str);
                    $i = str_replace(':', '_', $row_array[0]);
                    if (isset($now_goods['list'][$i])) {
                        $tArray = explode(':', $row_array[1]);
                        $now_goods['list'][$i]['wholesale_prices'] = $tArray[1];
                        $now_goods['list'][$i]['min_nums'] = $tArray[2];
                        $now_goods['list'][$i]['stocks'] = $tArray[3];
                        $now_goods['list'][$i]['group_team_prices'] = $tArray[5];
                        $now_goods['list'][$i]['group_join_prices'] = $tArray[6];
                        $now_goods['list'][$i]['vip_wholesale_prices'] = $tArray[7];
                        $now_goods['list'][$i]['goods_weights'] = $tArray[8];
                        $now_goods['list'][$i]['goods_volumes'] = $tArray[9];
                    }
                }
            }

            $now_goods['wholesale_price'] = $marketGoodsOld['price']>0 ? $marketGoodsOld['price']:0;
            $now_goods['vip_wholesale_price'] = $marketGoodsOld['vip_wholesale_price']>0 ? $marketGoodsOld['vip_wholesale_price']:0;
            $now_goods['min_num'] = $marketGoodsOld['min_num'];
            $now_goods['stock'] = $marketGoodsOld['stock_num'];
            $now_goods['discount_info'] = json_decode($marketGoodsOld['discount_info'], true);
            $now_goods['status'] = $marketGoodsOld['status'];
            $now_goods['cat_fid'] = $marketGoodsOld['cat_fid'];
            $now_goods['cat_id'] = $marketGoodsOld['cat_id'];
            // 是否加入白名单
            $now_goods['is_add_white_list'] = $marketGoodsOld['is_add_white_list'];
            /*拼团活动的配置信息*/
            $now_goods['is_group'] = $marketGoodsOld['is_group'];
            $now_goods['group_team_price'] = $marketGoodsOld['group_team_price'];
            $now_goods['group_join_price'] = $marketGoodsOld['group_join_price'];
            $now_goods['group_stime'] = $marketGoodsOld['group_stime'];
            $now_goods['group_ftime'] = $marketGoodsOld['group_ftime'];

            $day=floor($marketGoodsOld['group_affect_time']/(24*3600));
            $hour=floor(($marketGoodsOld['group_affect_time']-$day*24*3600)/3600);
            $minute=floor(($marketGoodsOld['group_affect_time']%3600)/60);

            $now_goods['day'] = $day;
            $now_goods['hour'] = $hour;
            $now_goods['minute'] = $minute;
            $now_goods['group_complete_num'] = $marketGoodsOld['group_complete_num'];
            $now_goods['min_unit'] = $marketGoodsOld['min_unit'];
            // 配送费计算方式
            $now_goods['freight_type'] = $marketGoodsOld['freight_type'];
            $now_goods['goods_weight'] = $marketGoodsOld['goods_weight'];
            $now_goods['goods_volume'] = $marketGoodsOld['goods_volume'];
            $now_goods['express_template_id'] = $marketGoodsOld['express_template_id'];
            $now_goods['express_fee'] = $marketGoodsOld['express_fee'];
            // 配送方式
            $now_goods['is_pick'] = $marketGoodsOld['is_pick'];
            $now_goods['pick_info'] = explode(',', $marketGoodsOld['pick_info']);
        }else{
            $now_goods['wholesale_price'] = '';
            $now_goods['vip_wholesale_price'] = '';
            $now_goods['stock'] = -1;
            $now_goods['goods_weight'] = '';
            $now_goods['goods_volume'] = '';
            $now_goods['is_pick'] = 0;
            $now_goods['pick_info'] = array();
        }

        if(!empty($marketGoodsOld)){
            $where=array();
            $where['group_id']=$marketGoodsOld['mid'];
            $where['status']=0;
            $count=M('Market_group_team')->where($where)->count(1);
            if($count>0){
                $now_goods['no_edit']=1;
            }
        }
        $pick_addr = D('Pick_address')->get_pick_addr_by_merid($this->merchant['mer_id'],true);
        if ($pick_addr && $now_goods['pick_info']) {
            foreach ($pick_addr as &$pick) {
                if (in_array($pick['pick_addr_id'],$now_goods['pick_info'])) {
                    $pick['is_checked'] = true;
                } else {
                    $pick['is_checked'] = false;
                }
            }
        }

        $arr['pick_addr'] = $pick_addr;
        $category_list = D('Goods_wholesale_category')->get_list();
        $arr['category_list'] = $category_list;
        $arr['category_list_json'] = json_encode($category_list);
        $arr['now_goods'] = $now_goods;
        $arr['now_store'] = $now_store;
        // 运费模板信息
        if (1==$this->config['merchant_integral_mall_control']) {
            $where_express = array('mer_id' => $this->merchant['mer_id']);
            $express_list = M('Market_express_template')->where($where_express)->select();
            if (empty($express_list)) {
                $this->returnCode(1,'',L_('请先添加运费模板！'));
            }
            $list = array();
            $tids = array();
            foreach ($express_list as $row) {
                $tids[] = $row['id'];
                $list[$row['id']] = $row;
            }
            $template_msgs = D('Market_express_template_msg')->field(true)->where(array('tid' => array('in', $tids)))->select();
            if ($template_msgs) {
                $freight_type_arr = array();
                foreach ($template_msgs as $val) {
                    if (!$freight_type_arr[$val['tid']]) {
                        $freight_type_arr[$val['tid']] = array();
                    }
                    $freight_type_arr[$val['tid']][] = $val['freight_type'];
                }
                foreach ($freight_type_arr as $key=>$item) {
                    $is_freight = 0; // 不需要衡量运费参数
                    if (in_array(2, $item)) {
                        $is_freight = 1; // 重量-衡量运费参数
                    }
                    if (in_array(3, $item) && $is_freight==1) {
                        $is_freight = 3; // (体积 + 重量)-衡量运费参数
                    } elseif (in_array(3, $item)) {
                        $is_freight = 2; // 体积-衡量运费参数
                    }
                    $list[$key]['is_freight'] = $is_freight;
                }

            }
            $list = array_values($list);
            $arr['express_list'] = $list;
        }
        // 配置项信息
        $config = array(
            'Currency_txt' => $this->config['Currency_txt'],
            'Currency_symbol' => $this->config['Currency_symbol'],
        );
        // 添加 【商家类型功能】开关   0 关 1 开
        $config['merchant_type_switch'] = $this->config['merchant_type_switch']?$this->config['merchant_type_switch']:0;
        $arr['config'] = $config;


        // 获取白名单信息
        if ($now_goods && $now_goods['white_list']) {
            $white_list = explode(',', $now_goods['white_list']);
            $merchant_info=M('Merchant')->where(array('mer_id' => array('in',  $now_goods['white_list'])))->getField('mer_id, name, phone');

            if ($white_list) {
                foreach ($white_list as $val) {
                    $white_msg[]=$merchant_info[$val];
                }
            } else {
                $white_msg = '';
            }
            $arr['white_msg'] = $white_msg;
        }

        $this->returnCode(0,$arr);
    }

    /**将商品发布到批发市场-编辑*/
    public function market_goods_edit()
    {
        $now_merchant = M('Merchant')->where(array('mer_id' => $this->merchant['mer_id']))->field('is_publish_market_goods')->find();
        $is_publish_market_goods = $now_merchant['is_publish_market_goods']?$now_merchant['is_publish_market_goods']:0;
        if ($is_publish_market_goods == 1) {
            $this->returnCode(1,'',L_('当前您不能发布商品，如需发布请联系管理员！'));
        }
        $goodsId = intval($_POST['goods_id']);
        $database_shop_goods = D('Shop_goods');
        $where = array('goods_id' => $goodsId);
        $now_goods = $database_shop_goods->field(true)->where($where)->find();
        if(empty($now_goods)){
            $this->returnCode(1,'',L_('商品不存在！'));
        }
        if ($now_goods['original_goods_id']) {
            $this->returnCode(1,'',L_('该商品不能发布！'));
        }
        $now_store = $this->check_store($now_goods['store_id']);

        if(!empty($now_goods['image'])){
            $goods_image_class = new goods_image();
            $tmp_pic_arr = explode(';', $now_goods['image']);
            foreach ($tmp_pic_arr as $key => $value) {
                $now_goods['pic_arr'][$key]['title'] = $value;
                $now_goods['pic_arr'][$key]['url'] = $goods_image_class->get_image_by_path($value, 's');
            }
        }

        $return = $database_shop_goods->format_spec_value($now_goods['spec_value'], $now_goods['goods_id']);

        $now_goods['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
        $now_goods['list'] = isset($return['list']) ? $return['list'] : '';
        $now_goods['discount_info'] = '';
        $now_goods['status'] = 1;
        if ($marketGoodsOld = M('Market_goods')->where($where)->find()) {
            if ($marketGoodsOld['spec_value'] && $now_goods['list']) {
                $spec_array = explode('#', $marketGoodsOld['spec_value']);
                foreach ($spec_array as $str) {
                    $row_array = explode('|', $str);
                    $i = str_replace(':', '_', $row_array[0]);
                    if (isset($now_goods['list'][$i])) {
                        $tArray = explode(':', $row_array[1]);
                        $now_goods['list'][$i]['wholesale_prices'] = $tArray[1];
                        $now_goods['list'][$i]['min_nums'] = $tArray[2];
                        $now_goods['list'][$i]['stocks'] = $tArray[3];
                        $now_goods['list'][$i]['group_team_prices'] = $tArray[5];
                        $now_goods['list'][$i]['group_join_prices'] = $tArray[6];
                        $now_goods['list'][$i]['vip_wholesale_prices'] = $tArray[7];
                        $now_goods['list'][$i]['goods_weights'] = $tArray[8];
                        $now_goods['list'][$i]['goods_volumes'] = $tArray[9];
                    }
                }
            }

            $now_goods['wholesale_price'] = $marketGoodsOld['price'];
            $now_goods['vip_wholesale_price'] = $marketGoodsOld['vip_wholesale_price'];
            $now_goods['min_num'] = $marketGoodsOld['min_num'];
            $now_goods['stock'] = $marketGoodsOld['stock_num'];
            $now_goods['discount_info'] = json_decode($marketGoodsOld['discount_info'], true);
            $now_goods['status'] = $marketGoodsOld['status'];
            $now_goods['cat_fid'] = $marketGoodsOld['cat_fid'];
            $now_goods['cat_id'] = $marketGoodsOld['cat_id'];
            // 是否加入白名单
            $now_goods['is_add_white_list'] = $marketGoodsOld['is_add_white_list'];
            /*拼团活动的配置信息*/
            $now_goods['is_group'] = $marketGoodsOld['is_group'];
            $now_goods['group_team_price'] = $marketGoodsOld['group_team_price'];
            $now_goods['group_join_price'] = $marketGoodsOld['group_join_price'];
            $now_goods['group_stime'] = $marketGoodsOld['group_stime'];
            $now_goods['group_ftime'] = $marketGoodsOld['group_ftime'];

            $day=floor($marketGoodsOld['group_affect_time']/(24*3600));
            $hour=floor(($marketGoodsOld['group_affect_time']-$day*24*3600)/3600);
            $minute=floor(($marketGoodsOld['group_affect_time']%3600)/60);

            $now_goods['day'] = $day;
            $now_goods['hour'] = $hour;
            $now_goods['minute'] = $minute;
            $now_goods['group_complete_num'] = $marketGoodsOld['group_complete_num'];
            $now_goods['min_unit'] = $marketGoodsOld['min_unit'];
            // 配送费计算方式
            $now_goods['freight_type'] = $marketGoodsOld['freight_type'];
            $now_goods['goods_weight'] = $marketGoodsOld['goods_weight'];
            $now_goods['goods_volume'] = $marketGoodsOld['goods_volume'];
            $now_goods['express_template_id'] = $marketGoodsOld['express_template_id'];
            $now_goods['express_fee'] = $marketGoodsOld['express_fee'];
            // 配送方式
            $now_goods['is_pick'] = $marketGoodsOld['is_pick'];
            $now_goods['pick_info'] = explode(',', $marketGoodsOld['pick_info']);
        }


        if (IS_POST) {
            $error_tips ='';
            if (!$_POST['min_unit'] || $_POST['min_unit']<=0) {
                $this->returnCode(1,'',L_('请填写大于0的最小批发单位！'));
            }

            if ($now_goods['spec_list']) {
                $wholesale_prices = $_POST['wholesale_prices'];
                $group_team_prices_arr = $_POST['group_team_prices_arr'];
                $group_join_prices_arr = $_POST['group_join_prices_arr'];

                if (1==$this->config['merchant_type_switch']) {
                    $vip_wholesale_prices = $_POST['vip_wholesale_prices'];
                }
                $freight_type = 1;
                $is_freight = 0; // 不需要衡量运费参数
                if (1==$this->config['merchant_integral_mall_control']) {
                    if (!$_POST['express_template_id'] || !$_POST['express_fee']) {
                        $this->returnCode(1,'',L_('请选择运费模板及填写其他区域运费！'));
                    }
                    $freight_type = $_POST['freight_type']>0 ? $_POST['freight_type'] : 1; // 默认按件数计算配送费
                    $goods_weights = $_POST['goods_weights'];
                    $goods_volumes = $_POST['goods_volumes'];

                    $where_express = array('mer_id' => $this->merchant['mer_id'], 'id' => $_POST['express_template_id']);
                    $express_list = M('Market_express_template')->where($where_express)->find();
                    if (empty($express_list)) {
                        $this->returnCode(1,'',L_('选择的模板信息不存在或者已经被删除！'));
                    }
                    $template_msgs = D('Market_express_template_msg')->field(true)->where(array('tid' => $_POST['express_template_id']))->select();
                    if ($template_msgs) {
                        $freight_type_arr = array();
                        foreach ($template_msgs as $val) {
                            if (!$freight_type_arr[$val['tid']]) {
                                $freight_type_arr[$val['tid']] = array();
                            }
                            $freight_type_arr[$val['tid']][] = $val['freight_type'];
                        }
                        foreach ($freight_type_arr as $key=>$item) {
                            if (in_array(2, $item)) {
                                $is_freight = 1; // 重量-衡量运费参数
                            }
                            if (in_array(3, $item) && $is_freight==1) {
                                $is_freight = 3; // (体积 + 重量)-衡量运费参数
                            } elseif (in_array(3, $item)) {
                                $is_freight = 2; // 体积-衡量运费参数
                            }
                            $express_list['is_freight'] = $is_freight;
                        }

                    }
                }

                $min_nums = $_POST['min_nums'];
                $stocks = $_POST['stocks'];
                foreach ($wholesale_prices as $i => $val) {
                    if (empty($wholesale_prices[$i]) || empty($stocks[$i])) {
                        $error_tips .= '请正确设置每一种规格对应的批发参数<br/>';
                    }
                    if (empty($min_nums[$i]) || $min_nums[$i]<0) {
                        $min_nums[$i] = 0;
                    }
                    if (!empty($vip_wholesale_prices) && (!$vip_wholesale_prices[$i] && $vip_wholesale_prices[$i]<=0)) {
                        $error_tips .= '请正确设置每一种规格对应的VIP批发参数<br/>';
                    }
                    if ((1==$is_freight || 3==$is_freight) && (($goods_weights[$i] && $goods_weights[$i]<=0) || !$goods_weights[$i])) {
                        $error_tips .= '请正确设置每一种规格对应的大于0的重量<br/>';
                    }
                    if ((2==$is_freight || 3==$is_freight) && (($goods_volumes[$i] && $goods_volumes[$i]<=0) || !$goods_volumes[$i])) {
                        $error_tips .= '请正确设置每一种规格对应的大于0的体积<br/>';
                    }
                }
            } else {
                $wholesale_price = isset($_POST['wholesale_price']) ? floatval($_POST['wholesale_price']) : 0;
                if ($wholesale_price <= 0) {
                    $error_tips .= '请设置批发价且大于0<br/>';
                }
                if (1==$this->config['merchant_type_switch']) {
                    $vip_wholesale_price = isset($_POST['vip_wholesale_price']) ? floatval($_POST['vip_wholesale_price']) : 0;
                    if ($vip_wholesale_price <= 0) {
                        $error_tips .= '请设置VIP批发价且大于0<br/>';
                    }
                }

                $is_freight = 0; // 不需要衡量运费参数
                if (1==$this->config['merchant_integral_mall_control']) {
                    if (!$_POST['express_template_id'] || !$_POST['express_fee']) {
                        $this->returnCode(1,'',L_('请选择运费模板及填写其他区域运费！'));
                    }
                    $freight_type = $_POST['freight_type']>0 ? $_POST['freight_type'] : 1; // 默认按件数计算配送费
                    $goods_weight = $_POST['goods_weight']>0 ? $_POST['goods_weight'] : 0;
                    $goods_volume = $_POST['goods_volume']>0 ? $_POST['goods_volume'] : 0;


                    $where_express = array('mer_id' => $this->merchant['mer_id'], 'id' => $_POST['express_template_id']);
                    $express_list = M('Market_express_template')->where($where_express)->find();
                    if (empty($express_list)) {
                        $this->returnCode(1,'',L_('选择的模板信息不存在或者已经被删除！'));
                    }
                    $template_msgs = D('Market_express_template_msg')->field(true)->where(array('tid' => $_POST['express_template_id']))->select();
                    if ($template_msgs) {
                        $freight_type_arr = array();
                        foreach ($template_msgs as $val) {
                            if (!$freight_type_arr[$val['tid']]) {
                                $freight_type_arr[$val['tid']] = array();
                            }
                            $freight_type_arr[$val['tid']][] = $val['freight_type'];
                        }
                        foreach ($freight_type_arr as $key=>$item) {
                            if (in_array(2, $item)) {
                                $is_freight = 1; // 重量-衡量运费参数
                            }
                            if (in_array(3, $item) && $is_freight==1) {
                                $is_freight = 3; // (体积 + 重量)-衡量运费参数
                            } elseif (in_array(3, $item)) {
                                $is_freight = 2; // 体积-衡量运费参数
                            }
                            $express_list['is_freight'] = $is_freight;
                        }

                    }

                    if ((1==$is_freight || 3==$is_freight) && $goods_weight<=0) {
                        $error_tips .= '请设置重量且大于0<br/>';
                    }
                    if ((2==$is_freight || 3==$is_freight) && $goods_volume<=0) {
                        $error_tips .= '请设置体积且大于0<br/>';
                    }
                }
                $min_num = isset($_POST['min_num']) ? intval($_POST['min_num']) : 0;
                if ($min_num <= 0) {
                    $error_tips .= '请设置最低批发数且大于0<br/>';
                }
                $stock = isset($_POST['stock']) ? intval($_POST['stock']) : 0;
                if ($stock <= 0 && $stock != -1) {
                    $error_tips .= '请设置库存且大于0<br/>';
                }
            }
            $is_group=I('is_group',0,'intval');
            $group_team_price=I('group_team_price',0,'floatval');
            $group_join_price=I('group_join_price',0,'floatval');

            $group_stime=I('group_sdate','','strtotime');
            $group_ftime=I('group_fdate','','strtotime');

            $group_day=I('group_day',0,'intval');
            $group_hour=I('group_hour',0,'intval');
            $group_minute=I('group_minute',0,'intval');
            $group_affect_time=$group_day*24*3600+$group_hour*3600+$group_minute*60;
            $group_complete_num=I('group_complete_num',0,'intval');

            if($is_group==1){
                if(empty($group_stime) || empty($group_ftime) || $group_stime>=$group_ftime){
                    $error_tips.='请设置有效的拼团活动时间<br/>';
                }
                if($group_affect_time<=0){
                    $error_tips.='请设置有效的拼团活动时长<br/>';
                }
                if($group_complete_num<=1){
                    $error_tips.='请设置正确的拼团人数<br/>';
                }
            }

            $nums = $_POST['nums'];
            $discounts = $_POST['discounts'];
            $status = $this->config['market_goods_is_examine'] ? 0 : 1;

//            if (isset($_POST['status'])) {
//                $status = intval($_POST['status']);
//            }

            if (empty($error_tips)) {
                $discount_info = array();
                for ($i = 0; $i < count($nums); $i ++) {
                    if ($nums[$i] && $discounts[$i]) {
                        $discount_info[] = array('num' => $nums[$i], 'discount' => $discounts[$i]);
                    }
                }
                $marketGoods = array('mer_id' => $this->merchant['mer_id'], 'last_time' => time());
                $marketGoods['store_id'] = $now_goods['store_id'];
                $marketGoods['goods_id'] = $now_goods['goods_id'];
                $marketGoods['name'] = $now_goods['name'];
                $marketGoods['number'] = $now_goods['number'];
                $marketGoods['unit'] = $now_goods['unit'];
                $marketGoods['image'] = $now_goods['image'];
                $marketGoods['des'] = $now_goods['des'];
                $marketGoods['is_properties'] = $now_goods['is_properties'];
                $marketGoods['status'] = $status;
                $marketGoods['discount_info'] = $discount_info ? json_encode($discount_info) : '';
                $marketGoods['cat_fid'] = isset($_POST['cat_fid']) ? intval($_POST['cat_fid']) : 0;
                $marketGoods['cat_id'] = isset($_POST['cat_id']) ? intval($_POST['cat_id']) : 0;
                $marketGoods['province_id'] = isset($now_store['province_id']) ? intval($now_store['province_id']) : 0;
                $marketGoods['city_id'] = isset($now_store['city_id']) ? intval($now_store['city_id']) : 0;
                $marketGoods['area_id'] = isset($now_store['area_id']) ? intval($now_store['area_id']) : 0;
                // 白名单信息
                $marketGoods['white_list'] = $now_goods['white_list'];
                // 选择是后必须是加入白名单的商户才能在批发市场中查看和批发 1 选择 0不选择（所有商户可看）
                $marketGoods['is_add_white_list'] = $_POST['is_add_white_list']>0?1:0;
                /*新增拼团活动配置*/
                $marketGoods['is_group']=$is_group;
                $marketGoods['group_team_price']=$group_team_price;
                $marketGoods['group_join_price']=$group_join_price;
                $marketGoods['group_stime']=$group_stime;
                $marketGoods['group_ftime']=$group_ftime;
                $marketGoods['group_affect_time']=$group_affect_time;
                $marketGoods['group_complete_num']=$group_complete_num;
                // 配送计费方式
                $marketGoods['freight_type']= $freight_type>0 ? $freight_type : 0;
                $marketGoods['express_template_id']= $_POST['express_template_id'] > 0 ? $_POST['express_template_id'] : 0;
                $marketGoods['express_fee'] = $_POST['express_fee'] > 0 ? $_POST['express_fee'] : 0;
                $marketGoods['min_unit'] = $_POST['min_unit'] > 0 ? $_POST['min_unit'] : 0;

                if (empty($marketGoods['cat_fid']) || empty($marketGoods['cat_id'])) {
                    $this->returnCode(1,'',L_('请选择商品分类！'));
                }
                if (empty($now_goods['spec_list'])) {
                    $marketGoods['min_num'] = $min_num;
                    $marketGoods['stock_num'] = $stock;
                    $marketGoods['price'] = $wholesale_price;
                    $marketGoods['vip_wholesale_price']=$vip_wholesale_price;
                    $marketGoods['goods_weight']=$goods_weight;
                    $marketGoods['goods_volume']=$goods_volume;
                }
                // 自提相关信息
                if (isset($_POST['is_pick'])) {
                    $marketGoods['is_pick'] = intval($_POST['is_pick']);
                    fdump($_POST['is_pick'],'is_pick');
                    if ($_POST['is_pick'] == 1 && empty($_POST['pick_info'])) {
                        $this->returnCode(1,'',L_('请选择自提地点！'));
                    } elseif ($_POST['pick_info']) {
                        $marketGoods['pick_info'] = implode(',', $_POST['pick_info']);
                    }
                }
                if ($marketGoodsOld) {
                    if ($mid = M('Market_goods')->where(array('mid' => $marketGoodsOld['mid']))->save($marketGoods)) {
                        $mid = $marketGoodsOld['mid'];
                        //删除已有的属性与规格的数据
                        M('Market_goods_properties')->where($where)->delete();
                        $spec_list = M('Market_goods_spec')->field(true)->where($where)->select();
                        foreach ($spec_list as $spec) {
                            M('Market_goods_spec_value')->where(array('sid' => $spec['id']))->delete();
                        }
                        M('Market_goods_spec')->where($where)->delete();
                    }
                } else {
                    $mid = M('Market_goods')->add($marketGoods);
                }

                if ($mid) {
                    if ($now_goods['is_properties']) {
                        $properties = M('Shop_goods_properties')->field(true)->where(array('goods_id' => $now_goods['goods_id']))->select();
                        foreach ($properties as $pro_data) {
                            M('Market_goods_properties')->add($pro_data);
                        }
                    }

                    if ($now_goods['spec_value']) {
                        $spec_list = M('Shop_goods_spec')->field(true)->where(array('goods_id' => $now_goods['goods_id']))->select();
                        foreach ($spec_list as $spec) {
                            if (M('Market_goods_spec')->add($spec)) {
                                $spec_value_list = M('Shop_goods_spec_value')->field(true)->where(array('sid' => $spec['id']))->select();
                                foreach ($spec_value_list as $spec_value) {
                                    M('Market_goods_spec_value')->add($spec_value);
                                }
                            }
                        }
                        //[old] => 规格值ID:规格值ID:...:规格值ID|old_price:price:seckill_price:stock_num:cost_price|属性ID=可选值个数:属性ID=可选值个数:...:属性ID=可选值个数|商品编号 # 规格值ID:规格值ID:...:规格值ID|old_price:price:seckill_price:stock_num:cost_price|属性ID=可选值个数:属性ID=可选值个数:...:属性ID=可选值个数|商品编号#...#规格值ID:规格值ID:...:规格值ID|old_price:price:seckill_price:stock_num:cost_price|属性ID=可选值个数:属性ID=可选值个数:...:属性ID=可选值个数|商品编号
                        //[new] => 规格值ID:规格值ID:...:规格值ID|old_price:price:min_num:stock_num:cost_price|属性ID=可选值个数:属性ID=可选值个数:...:属性ID=可选值个数|商品编号#
                        $spec_array = explode('#', $now_goods['spec_value']);
                        $target_spec_value_array = array();
                        $stock_num = 0;
                        $wholesale_price = 0;
                        $vip_wholesale_price = 0;
                        $min_num = 0;
                        foreach ($spec_array as $str) {
                            $row_array = explode('|', $str);
                            $i = 'id_' . str_replace(':', '_id_', $row_array[0]);
                            $row_array[1] = $wholesale_prices[$i] . ':' . $wholesale_prices[$i] . ':' . $min_nums[$i] . ':' . $stocks[$i] . ':' . $wholesale_prices[$i];
                            //新增拼团活动信息
                            $row_array[1].=':'.$group_team_prices_arr[$i];
                            $row_array[1].=':'.$group_join_prices_arr[$i];
                            // 如果存在vip价格加上vip价格 占据第8个位置
                            $row_array[1].=':'.$vip_wholesale_prices[$i];
                            // 如果存在配送费计费方式【重量】加上配送费计费方式【重量】 占据第9个位置
                            $row_array[1].=':'.$goods_weights[$i];
                            // 如果存在配送费计费方式【体积】加上配送费计费方式【体积】 占据第10个位置
                            $row_array[1].=':'.$goods_volumes[$i];

                            $marketGoods['min_num'] = $min_num;
                            if ($wholesale_price > 0) {
                                $wholesale_price = min($wholesale_price, $wholesale_prices[$i]);
                            } else {
                                $wholesale_price = $wholesale_prices[$i];
                            }
                            if ($vip_wholesale_price > 0) {
                                $vip_wholesale_price = min($vip_wholesale_price, $vip_wholesale_prices[$i]);
                            } else {
                                $vip_wholesale_price = $vip_wholesale_prices[$i];
                            }
                            if ($group_team_price > 0) {
                                $group_team_price = min($group_team_price, $group_team_prices_arr[$i]);
                            } else {
                                $group_team_price = $group_team_prices_arr[$i];
                            }
                            if ($min_num > 0) {
                                $min_num = min($min_num, $min_nums[$i]);
                            } else {
                                $min_num = $min_nums[$i];
                            }
                            $stock_num += $stocks[$i];
                            $target_spec_value_array[] = implode('|', $row_array);
                        }
                        $data = array('spec_value' => implode('#', $target_spec_value_array));
                        $data['min_num'] = $min_num;
                        $data['stock_num'] = $stock_num;
                        $data['price'] = $wholesale_price;
                        $data['vip_wholesale_price'] = $vip_wholesale_price;
                        $data['group_team_price'] = $group_team_price;
                        M('Market_goods')->where(array('mid' => $mid))->save($data);
                    }
                    $this->returnCode(0,$marketGoods,L_('发布成功！'));
                } else {
                    $this->returnCode(1,'',L_('发布失败~请重试！'));
                }
            } else {
                $this->returnCode(1,'',$error_tips);
            }
        } else {
            $this->returnCode(1,'',L_('没有任何参数！'));
        }
    }



    public function capture_merchant() {
        $goods_id = intval($_POST['goods_id']);
        // 查询一下相关物品信息
        $db_shop_goods = M('Shop_goods');
        $goods_info = $db_shop_goods->where(array('goods_id' => $goods_id))->field('white_list')->find();
        $white_msg = array();
        $arr['goods_id'] = $goods_id;
        if ($goods_info && $goods_info['white_list']) {
            $white_msg = explode(',', $goods_info['white_list']);
            $arr['white_msg'] = $white_msg;
        }

        $where=array();
        $where['status']=1;
        $name=I('name','','trim');
        if(!empty($name)){
            $where['name']=array('like','%'.$name.'%');
        }
        $goods_id = I('goods_id','','trim');

        $count=M('Merchant')->where($where)->count(1);
        import('@.ORG.system_page');
        $page=new Page($count,20);

        $list=M('Merchant')->where($where)->order('mer_id desc')->field('name, mer_id, phone')->limit($page->firstRow,$page->listRows)->select();

        if ($list && $white_msg) {
            foreach ($list as &$item) {
                if (in_array($item['mer_id'],$white_msg)) {
                    $item['is_checked'] = true;
                } else {
                    $item['is_checked'] = false;
                }

            }
        }
        $arr['merchant_list'] = $list;
        $arr['first'] = $page->firstRow;
        $arr['keyword'] = $name;
        $this->returnCode(0,$arr);
    }


    // 添加白名单信息
    public function merchant_white_list() {
        $goods_id = intval($_POST['goods_id']);
        $white_list =  $_POST['white_list'];
        $white_list = implode(',', $white_list);
        fdump($white_list,'$white_list');
        $db_shop_goods = M('Shop_goods');
        $goods_info = $db_shop_goods->where(array('goods_id' => $goods_id))->field('white_list')->find();
        if ($goods_info) {
            $save = array(
                'white_list' => $white_list
            );
            $goods_save = $db_shop_goods->where(array('goods_id' => $goods_id))->data($save)->save();
            if ($goods_save) {
                $this->returnCode(0,$save,L_('修改成功！'));
            } else {
                $this->returnCode(1,'',L_('请检查是否未修改内容，请修改后重新提交！'));
            }
        } else {
            $this->returnCode(1,'',L_('修改对象不存在！'));
        }
    }


    /**积分商城模块*/

    private function _get_integral_mall_category($fid){
        $database_merchant_integral_mall_category = D('Merchant_integral_mall_category');
        $where['cat_fid'] = $fid;
        $mer_id = $this->merchant['mer_id'];
        $where['mer_id'] = $mer_id;
        $where['cat_status'] = array('neq', 3);

        $category_list = $database_merchant_integral_mall_category->where($where)->getField('cat_id,cat_id,cat_fid,cat_name');

        if ($category_list) {
            foreach ($category_list as &$item) {
                $where['cat_fid'] = $item['cat_id'];
                $son_list = $database_merchant_integral_mall_category->where($where)->getField('cat_id,cat_id,cat_fid,cat_name');
                if ($son_list) {
                    $item['son_list'] = $son_list;
                }
            }
        }



        return $category_list;
    }

    // 虚拟批发到积分商城信息
    public function virtual_wholesale_info(){
        $goods_id=I('goods_id',0,'intval');
        $type=I('type','','trim');
        $model=D('Merchant_integral_mall_goods');
        $d_market_goods=D('Market_goods');
        $mer_id=$this->merchant['mer_id'];

        $cate_list = $this->_get_integral_mall_category(0);

        $arr = array();
        $arr['cate_list'] = $cate_list;
        $arr['cate_list_json'] = json_encode($cate_list);
        if($goods_id>0 && $type != 'virtual_market'){
            $where=array();
            $where['goods_id']=$goods_id;
            $where['mer_id']=$mer_id;
            $data=$model->goods_detail($where);
            if(!$data){
                $this->returnCode(1,'',L_('数据处理有误！'));
            }else{
                $info=$data['detail'];
                // 处理下，如果当前库存大于批发市场库存，同步减少到与批发市场库存一致
                $virtual_goods_id = $info['virtual_wholesale_goods_id']>0 ? $info['virtual_wholesale_goods_id'] : $info['market_goods_id'];
                $s_where['goods_id']=$virtual_goods_id;
                $s_where['status']=1;
                $market_goods = $d_market_goods->where($s_where)->find();
//                if (empty($market_goods)) {
//                    $this->returnCode(1,'',L_('商品不存在或者下架或者关联店铺不存在了！'));
//                }

                if ($info && $info['spec_val']) {
                    $info['spec_list'] = $info['spec_val']['spec_list'];
                    $info['list'] = $info['spec_val']['list'];
                    $return = $d_market_goods->format_spec_value($market_goods['spec_value'], $market_goods['goods_id']);
                    $list = isset($return['list']) ? $return['list'] : '';
                    foreach ($info['list'] as $key1=>&$val1) {
                        if ($list[$key1]) {
                            $val1['sku'] = min($list[$key1]['stock_num'], $val1['sku']);
                            if ($list[$key1]['sku']>0) {
                                $val1['sku'] = min($list[$key1]['stock_num'], $val1['sku']);
                            } elseif ($list[$key1]['sku'] != -1) {
                                $val1['sku'] = 0;
                            }
                            $val1['exchange_limit_num'] = min($val1['exchange_limit_num'], $val1['sku']);
                        }
                    }
                }
                if ($market_goods['stock_num']>0) {
                    $info['sku'] = min($info['sku'], $market_goods['stock_num']);
                } elseif ($market_goods['stock_num'] != -1) {
                    $info['sku'] = 0;
                }

                $cate_list2 = $this->_get_integral_mall_category($info['cat_fid']);

                // 虚拟批发直接同步
                $info['express_template_id'] = $market_goods['express_template_id'];
                $info['express_fee'] = $market_goods['express_fee'];
                unset($info['virtual_wholesale_goods_id']);
                $arr['now_goods'] = $info;
                $arr['cate_list2'] = $cate_list2;
            }
        } else if ($type && $type=='virtual_market' && $goods_id>0) {
            $now_merchant = M('Merchant')->where(array('mer_id' => $mer_id))->field('money,is_publish_integral_mall')->find();
            $is_publish_integral_mall = $now_merchant['is_publish_integral_mall']?$now_merchant['is_publish_integral_mall']:0;
            if ($is_publish_integral_mall != 1) {
                $this->returnCode(1,'',L_('当前您不能虚拟上架到商家积分商城，如需上架请联系管理员！'));
            }
            $merchant_integral_mall_imprest = floatval($this->config['merchant_integral_mall_imprest']);
            if ($merchant_integral_mall_imprest>0 && floatval($now_merchant['money'])<$merchant_integral_mall_imprest) {
                $add_money = floatval($merchant_integral_mall_imprest) - floatval($now_merchant['money']);
                $this->returnCode(1,'',L_('您的备用金不足,当前余额X1,还需充值X2，请充值！',array("X1" => $now_merchant['money'], 'X2' => $add_money)));
            }


            $where=array();
            $where['goods_id'] = $goods_id;
            $where['status'] = 1;
            $market_goods = $d_market_goods->where($where)->find();
            if ($market_goods['mer_id'] == $mer_id) {
                $this->returnCode(1,'',L_('你不能虚拟上架自己的批发市场商品！'));
            }
            $market_express_template = M('Market_express_template');

            if ($market_goods['express_template_id'] > 0) {
                $express_is_type = 0;
                $market_goods['express_is_type'] = $market_express_template->where(array('id' => $market_goods['express_template_id']))->getField('is_type');
                if ($market_goods['express_is_type']!=null) {
                    $express_is_type += 1;  // 处理后的值 1 快递和物流 2 快递 3 物流
                    if ($express_is_type!=1 && $express_is_type!=2) {
                        $this->returnCode(1,'',L_('当前商品不能进行虚拟上架！'));
                    }
                }

            }


            $goods_image_class = new goods_image();
            $tmp_pic_arr = explode(';', $market_goods['image']);
            $pc_pic_list = array();
            $wap_pic_list = array();
            foreach ($tmp_pic_arr as $key => $value) {
                $pc_pic_list[$key]['title'] = $wap_pic_list[$key]['title'] = $value;
                $tmp_pic = $goods_image_class->get_image_by_path($value);
                $pc_pic_list[$key]['url'] = $wap_pic_list[$key]['url'] = $tmp_pic['image'];
                $pc_pic_list[$key]['m_url'] = $tmp_pic['m_image'];
            }
            $return = $d_market_goods->format_spec_value($market_goods['spec_value'], $market_goods['goods_id']);

            $market_goods['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
            $market_goods['list'] = isset($return['list']) ? $return['list'] : '';


            $g_where['virtual_wholesale_goods_id'] = $market_goods['goods_id'];
            $g_where['virtual_wholesale_store_id'] = $market_goods['store_id'];
            $g_where['virtual_wholesale_mer_id'] = $mer_id;
            $g_where['goods_source_type'] = 3;
            $mall_goods = $model->goods_detail($g_where);
            $detail = $mall_goods['detail'];
            if ($detail && $detail['spec_val']) {
                $list_old = $detail['spec_val']['list'];
            } else {
                $list_old = array ();
            }

            foreach ($market_goods['list'] as $key=>&$vals) {
                $vals['payment_pure_integral'] = $list_old[$key]['payment_pure_integral']?$list_old[$key]['payment_pure_integral']:0;
                $vals['exchange_limit_num'] = $list_old[$key]['exchange_limit_num']?$list_old[$key]['exchange_limit_num']:0;
                $vals['exchanged_num'] = $vals['exchanged_num']?$vals['exchanged_num']:0;
            }

            $info = array(
                'market_goods_id' => $market_goods['goods_id'],
                'goods_source_type' => 3,
                'virtual_wholesale_mer_id' => $market_goods['mer_id'],
                'virtual_wholesale_store_id' => $market_goods['store_id'],
                'virtual_wholesale_goods_id' => $market_goods['goods_id'],
                'goods_name' => $market_goods['name'],
                'sku' => $market_goods['stock_num'],
                'unit' => $market_goods['unit'],
                'sort' => 0,
                'wap_pic_list' => $wap_pic_list,
                'pc_pic_list' => $pc_pic_list,
                'spec_list' => $market_goods['spec_list'],
                'list' => $market_goods['list'],
                'is_spec' =>  $market_goods['spec_list'] ? true : false,
                'status' =>  1,
                'express_template_id' =>  $market_goods['express_template_id'],
                'express_fee' =>  $market_goods['express_fee'],
                'freight_type' =>  $market_goods['freight_type'],
                'goods_content' => replace_file_domain_content($market_goods['des']),
            );

            if ($detail && $detail['cat_fid'] > 0) {
                $info['cat_fid'] = $detail['cat_fid'];
            } elseif (!$detail || !$detail['cat_fid']) {
                $info['cat_fid'] = $market_goods['cat_fid'];
            }
            if ($detail && $detail['cat_id'] > 0) {
                $info['cat_id'] = $detail['cat_id'];
            } elseif (!$detail || !$detail['cat_id']) {
                $info['cat_id'] = $market_goods['cat_id'];
            }
            if ($detail && $detail['deliver_type'] > 0) {
                $info['deliver_type'] = $detail['deliver_type'];
            }
            if ($detail && $detail['exchange_limit_num'] > 0) {
                $info['exchange_limit_num'] = $detail['exchange_limit_num'];
            } elseif (!$info['exchange_limit_num']) {
                $info['exchange_limit_num'] = 0;
            }
            if ($detail && $detail['payment_pure_integral'] > 0) {
                $info['payment_pure_integral'] = $detail['payment_pure_integral'];
            } elseif (!$info['payment_pure_integral']) {
                $info['payment_pure_integral'] = 0;
            }
            if ($detail && $detail['exchanged_num'] > 0) {
                $info['exchanged_num'] = $detail['exchanged_num'];
            } elseif (!$info['exchanged_num']) {
                $info['exchanged_num'] = 0;
            }
            if ($detail && $detail['pc_pic_list'] > 0) {
                $info['pc_pic_list'] = $detail['pc_pic_list'];
            }
            if ($detail && $detail['wap_pic_list'] > 0) {
                $info['wap_pic_list'] = $detail['wap_pic_list'];
            }
            if ($detail && $detail['intro']) {
                $info['intro'] = $detail['intro'];
            }
            if ($detail && $detail['invoice_content']) {
                $info['invoice_content'] = $detail['invoice_content'];
            }
            if ($detail && $detail['goods_content']) {
                $info['goods_content'] = $detail['goods_content'];
            }
            if ($detail && $detail['sort']) {
                $info['sort'] = $detail['sort'];
            }
            if ($detail && $detail['is_new']) {
                $info['is_new'] = $detail['is_new'];
            }
            if ($detail && $detail['status']) {
                $info['status'] = $detail['status'];
            }
            if ($market_goods && $market_goods['goods_weight']>0) {
                $info['goods_weight'] = $market_goods['goods_weight'];
            } elseif ($detail && $detail['goods_weight']>0) {
                $info['goods_weight'] = $detail['goods_weight'];
            }
            if ($market_goods && $market_goods['goods_volume']>0) {
                $info['goods_volume'] = $market_goods['goods_volume'];
            } elseif ($detail && $detail['goods_volume']>0) {
                $info['goods_volume'] = $detail['goods_volume'];
            }

            $arr['now_goods'] = $info;
        }

        // 运费模板信息
        if (1==$this->config['merchant_integral_mall_control']) {
            if ($market_goods['mer_id']>0) {
                $where_express['mer_id'] = $market_goods['mer_id'];
            }
            $express_list = D('Market_express_template')->field(true)->where($where_express)->select();
            if (empty($express_list)) {
                $this->returnCode(1,'',L_('请先添加运费模板！'));
            }
            $arr['express_list'] = $express_list;
            if ($market_goods['express_template_id'] > 0) {
                $tid = $market_goods['express_template_id'];
                $template = D('Market_express_template')->field(true)->where(array('id' => $tid))->find();
                if (empty($template)) {
                    $this->returnCode(1,'',L_('不合法的模板信息！'));
                }

                $template_msgs = D('Market_express_template_msg')->field(true)->where(array('tid' => $tid))->select();


                $tmids = array();
                if ($template_msgs) {
                    foreach ($template_msgs as $val) {
                        $tmids[] = $val['id'];
                        if (empty($template['msg'])) {
                            $template['msg'] = array();
                        }
                        $template['msg'][$val['id']] = $val;
                    }
                }

                if ($tmids) {
                    $areas = D('Market_express_template_area')->field(true)->where(array('tmid' => array('in', $tmids)))->select();
                } else {
                    $areas = D('Market_express_template_area')->field(true)->where(array('tid' => $tid))->select();
                }

                $area_list = array();
                $area_info = array();
                foreach ($areas as $arow) {
                    $area_list[$arow['vid']][] = $arow;
                    if (!$area_info[$arow['vid']]['area_info'] && $arow['area_name']) {
                        $area_info[$arow['vid']]['area_info'] .= "<span area-id='".$arow['area_id']."' class='text-depth'>".$arow['area_name']."</span>";
                    } elseif ($arow['area_name']) {
                        $area_info[$arow['vid']]['area_info'] .= ',' . "<span area-id='".$arow['area_id']."' class='text-depth'>".$arow['area_name']."</span>";
                    }
                }

                $values = D('Market_express_template_value')->field(true)->where(array('tid' => $tid))->select();
                $template['value_list'] = array();
                $is_type = 1; // 1 计件   2 重量   3 体积 4 重量+体积
                foreach ($values as $v) {
                    $v['area_list'] = $area_list[$v['id']];
                    $v['area_info'] = $area_info[$v['id']]['area_info'];
                    $msg = $template['msg'][$v['tmid']];
                    if($msg['freight_type']==1){
                        $v['first_freight'] = $v['first_freight']<>'0.00'?$v['first_freight']:$v['freight'];
                        $v['first_weight']  = floatval($v['first_weight']<>'0.00'?$v['first_weight']:1);
                        $v['add_weight'] = floatval($v['add_weight']);
                    }
                    $template['freight_type_'.$msg['type']] = $msg['freight_type'];
                    if ($msg['freight_type']==2 && $is_type==1) {
                        $is_type = 2;
                    } elseif ($msg['freight_type']==2 && $is_type==3) {
                        $is_type = 4;
                    } elseif ($msg['freight_type']==3 && $is_type==2) {
                        $is_type = 4;
                    }
                    $template['value_list'][$msg['type']][] = $v;
                }
                $arr['template'] = $template;
                $arr['is_type'] = $is_type;
            } else {
                $template = D('Market_express_template')->field(true)->where($where_express)->order('id DESC')->find();
                if (empty($template)) {
                    $this->returnCode(1,'',L_('不合法的模板信息！'));
                }
                $arr['template'] = $template;
            }
        }
        // 配置项信息
        $config = array(
            'Currency_txt' => $this->config['Currency_txt'],
            'Currency_symbol' => $this->config['Currency_symbol'],
        );

        $config['merchant_integral_mall_control'] = $this->config['merchant_integral_mall_control']?$this->config['merchant_integral_mall_control']:0;
        // 添加 【商家类型功能】开关   0 关 1 开
        $config['merchant_type_switch'] = $this->config['merchant_type_switch']?$this->config['merchant_type_switch']:0;
        $arr['config'] = $config;
        $this->returnCode(0,$arr);
    }


    public function upload_market_image()
    {
        if($_FILES['imgFile']['error'] != 4){
            $path = $_GET['path'] ?$_GET['path']:'goods';
            $image = D('Image')->handle($this->merchant['mer_id'], $path, 1);
            if ($image['error']) {
                exit(json_encode($image));
            } else {
                if ($image['title']['wap_pics']) {
                    $title = $image['title']['wap_pics'];
                } elseif  ($image['title']['pc_pics']) {
                    $title = $image['title']['pc_pics'];
                } else {
                    $title = $image['title']['imgFile'];
                }
                $merchant_image_class = new merchant_image();
                $url = $merchant_image_class->get_image_by_path($title);
                $url = str_replace('merchant',$path,$url);
                $arr = array(
                    'url' => $url,
                    'title' => $title,
                );
                exit(json_encode(array('errorCode' => 0,'errorMsg'=>'success', 'result' => $arr)));
//                $this->returnCode(0, $arr);
            }
        } else {
            $this->returnCode('20140051');
        }
    }

    // 虚拟批发发布
    public function virtual_wholesale_push(){
        $goods_id=I('goods_id',0,'intval');
        $type=I('type','','trim');
        $model=D('Merchant_integral_mall_goods');
        $d_market_goods=D('Market_goods');
        $mer_id=$this->merchant['mer_id'];

        $data=$_POST;
        $data['goods_source_type'] = 3;
        $data['deliver_type'] = 0; // 快递配送
        if ($data['virtual_wholesale_goods_id']>0 || $data['market_goods_id']>0) {
            $now_merchant = M('Merchant')->where(array('mer_id' => $mer_id))->field('is_publish_integral_mall')->find();
            $is_publish_integral_mall = $now_merchant['is_publish_integral_mall']?$now_merchant['is_publish_integral_mall']:0;
            if ($is_publish_integral_mall != 1) {
                $this->returnCode(1,'',L_('当前您不能虚拟上架到商家积分商城，如需上架请联系管理员！'));
            }
            $virtual_goods_id = $data['virtual_wholesale_goods_id']>0 ? $data['virtual_wholesale_goods_id'] : $data['market_goods_id'];
            if (!$data['virtual_wholesale_goods_id']) {
                $data['virtual_wholesale_goods_id'] = $virtual_goods_id;
            }
            $where['goods_id']=$virtual_goods_id;
            $where['status']=1;
            $market_goods = $d_market_goods->where($where)->find();
            if ($market_goods['mer_id'] == $mer_id) {
                $this->returnCode(1,'',L_('你不能虚拟商家自己的批发市场商品'));
            }
            $return = $d_market_goods->format_spec_value($market_goods['spec_value'], $market_goods['goods_id']);

            $spec_list = isset($return['spec_list']) ? $return['spec_list'] : '';
            $list = isset($return['list']) ? $return['list'] : '';
            $data['wholesale_price'] = $market_goods['price']>0?$market_goods['price']:1;
            $data['vip_wholesale_price'] = $market_goods['vip_wholesale_price']>0?$market_goods['vip_wholesale_price']:1;
            $data['freight_type'] = $market_goods['freight_type']>0?$market_goods['freight_type']:1;
            $data['goods_volume'] = $market_goods['goods_volume']>0?$market_goods['goods_volume']:0;
            $data['goods_weight'] = $market_goods['goods_weight']>0?$market_goods['goods_weight']:0;

            if ($market_goods['express_template_id'] > 0) {
                $data['express_template_id'] = $market_goods['express_template_id'];
                $where_express_1['id'] = $market_goods['express_template_id'];
                $express_info = M('Market_express_template')->where($where_express_1)->find();
                if (empty($express_info)) {
                    $this->returnCode(1,'',L_('运费模板信息错误！'));
                }
            }
            if ($market_goods['express_fee'] > 0) {
                $data['express_fee'] = $market_goods['express_fee'];
            }
            if ($market_goods['unit']) {
                $data['unit'] = $market_goods['unit'];
            }

            // 规格-所需积分
            $payment_pure_integrals = $data['payment_pure_integrals'] ? $data['payment_pure_integrals'] : array();
            unset($data['payment_pure_integrals']);
            // 规格-库存数量
            $skus = $data['skus'] ? $data['skus'] : array();
            unset($data['skus']);
            // 规格-每人限制兑换数量
            $exchange_limit_nums = $data['exchange_limit_nums'] ? $data['exchange_limit_nums'] : array();
            unset($data['exchange_limit_nums']);
            // 规格-已兑换人数
            $exchanged_nums = $data['exchanged_nums'] ? $data['exchanged_nums'] : array();
            unset($data['exchanged_nums']);

            if ($spec_list && $list) {
                $sku = 0;
                $payment_pure_integral = 0;
                $exchanged_num = 0;
                foreach ($list as $key=>&$val) {
                    $index = $val['index'];
                    if(!is_numeric($payment_pure_integrals[$index])){
                        $this->returnCode(1,'',L_("所需纯X1必须为数字！",array("X1" => $this->score_name)));
                    }
                    $val['payment_pure_integral'] = $payment_pure_integrals[$index];
                    if ($payment_pure_integral>0) {
                        $payment_pure_integral = min($payment_pure_integral, $payment_pure_integrals[$index]);
                    } else {
                        $payment_pure_integral = $payment_pure_integrals[$index];
                    }
                    if ($skus[$index] > $val['stock_num']) {
                        $this->returnCode(1,'',L_("库存不能大于大于当前购买数量！"));
                    }
                    $val['sku'] = $skus[$index];
                    $sku += $skus[$index];
                    $val['exchange_limit_num'] = $exchange_limit_nums[$index];
                    $val['exchanged_num'] = $exchanged_nums[$index];
                    $exchanged_num += $exchanged_nums[$index];
                }
                $spec_val = array();
                $spec_val['spec_list'] = $spec_list;
                $spec_val['payment_pure_integral'] = $payment_pure_integral;
                $spec_val['exchanged_num'] = $exchanged_num;
                $spec_val['list'] = $list;
                $data['sku'] = $sku;
                $data['goods_source_type'] = 3;
                $data['payment_pure_integral'] = $payment_pure_integral;
                $data['exchanged_num'] = $exchanged_num;
                $data['spec_val'] = serialize($spec_val);
            }
        } elseif ($goods_id>0 && ($data['payment_pure_integrals'] || $data['skus'] || $data['exchange_limit_nums'] || $data['exchange_limit_nums'])) {
            $g_where['mer_id']=$mer_id;
            $g_where['goods_id']=$goods_id;
            $mall_goods = $model->goods_detail($g_where);
            $detail = $mall_goods['detail'];
            $spec_val = $detail['spec_val'];

            $spec_list = isset($spec_val['spec_list']) ? $spec_val['spec_list'] : '';
            $list = isset($spec_val['list']) ? $spec_val['list'] : '';

            if ($spec_val && $spec_list && $list) {
                // 规格-所需积分
                $payment_pure_integrals = $data['payment_pure_integrals'] ? $data['payment_pure_integrals'] : array();
                unset($data['payment_pure_integrals']);
                // 规格-库存数量
                $skus = $data['skus'] ? $data['skus'] : array();
                unset($data['skus']);
                // 规格-每人限制兑换数量
                $exchange_limit_nums = $data['exchange_limit_nums'] ? $data['exchange_limit_nums'] : array();
                unset($data['exchange_limit_nums']);
                // 规格-已兑换人数
                $exchanged_nums = $data['exchanged_nums'] ? $data['exchanged_nums'] : array();
                unset($data['exchanged_nums']);

                $sku = 0;
                $payment_pure_integral = 0;
                $exchanged_num = 0;
                foreach ($list as $key=>&$val) {
                    $index = $val['index'];
                    if(!is_numeric($payment_pure_integrals[$index])){
                        $this->returnCode(1,'',L_("所需纯X1必须为数字！",array("X1" => $this->score_name)));
                    }
                    $val['payment_pure_integral'] = $payment_pure_integrals[$index];
                    if ($payment_pure_integral>0) {
                        $payment_pure_integral = min($payment_pure_integral, $payment_pure_integrals[$index]);
                    } else {
                        $payment_pure_integral = $payment_pure_integrals[$index];
                    }
                    if ($skus[$index] > ($val['stock_num'] - floatval($val['sell_num']))) {
                        $this->returnCode(1,'',L_('库存不能大于大于当前购买数量！'));
                    }
                    $val['sku'] = $skus[$index];
                    $sku += $skus[$index];
                    $val['exchange_limit_num'] = $exchange_limit_nums[$index];
                    $val['exchanged_num'] = $exchanged_nums[$index];
                    if (!$val['sell_num'] || $val['sell_num'] < 0) {
                        $val['sell_num'] = 0;
                    }
                    $exchanged_num += $exchanged_nums[$index];
                }
                $spec_val = array();
                $spec_val['spec_list'] = $spec_list;
                $spec_val['payment_pure_integral'] = $payment_pure_integral;
                $spec_val['exchanged_num'] = $exchanged_num;
                $spec_val['list'] = $list;
                $data['sku'] = $sku;
                $data['payment_pure_integral'] = $payment_pure_integral;
                $data['exchanged_num'] = $exchanged_num;
                $data['spec_val'] = serialize($spec_val);
            }
        } else {
            $data['spec_val'] = '';
        }

        $data['mer_id']=$mer_id;
        if($goods_id>0 && $detail){
            $where=array();
            $where['goods_id']=$goods_id;
            unset($data['goods_id']);
            $res=$model->merchant_integral_mall_goods_edit($where,$data);
        }else{
            $data['status']=1;
            unset($data['goods_id']);
            $res=$model->merchant_integral_mall_goods_add($data);
        }
        if(!$res){
            $this->returnCode(1,'',L_('数据处理有误！'));
        }else{
            if($res['status']){
                $this->returnCode(0,array('url' => U('Integral_mall/index')));
            }else{
                $this->returnCode(1,'',$res['msg']);
            }
        }
    }



    public function integral_mall_list(){
        $keyword=I('keyword','','trim');
        $searchtype=I('searchtype','','trim');
        $where=array();
        $mer_id=$this->merchant['mer_id'];
        $where['mer_id']=$mer_id;
        if(!empty($keyword)){
            switch($searchtype){
                case 'goods_id':
                    $where['goods_id'] = $keyword + 0;
                    break;
                case 'goods_name':
                    $where['goods_name'] = array('like','%'.$keyword.'%');
                    break;
            }
        }
        $searchstatus=I('searchstatus',0,'intval');
        if($searchstatus>0){
            switch ($searchstatus) {
                case '1':
                    $where['status']=1;
                    break;
                case '2':
                    $where['status']=0;
                    break;
            }
        }

        $database_merchant_integral_mall_goods = D('Merchant_integral_mall_goods');
        $database_merchant_integral_mall_category = D('Merchant_integral_mall_category');
        $where['is_del'] = 0;

        $data = $database_merchant_integral_mall_goods->Merchant_integral_mall_goods_page_list($where,true,'sort desc, goods_id desc');

        $where_cat['mer_id'] = $mer_id;
        $cat_list = $database_merchant_integral_mall_category->where($where_cat)->getField('cat_id,cat_name');

        $arr = array();
        $arr['cat_list'] = $cat_list;

        $info=$data['list'];
        foreach ($info['list'] as $k => $v) {
            $cat_name='';
            if($v['cat_fid']>0){
                $cat_name.=$cat_list[$v['cat_fid']];
            }
            if($v['cat_id']){
                $cat_name.='-'.$cat_list[$v['cat_id']];
            }
            $info['list'][$k]['cat_name']=$cat_name;

            if ($v['last_time']>0) {
                $info['list'][$k]['info_time']=date('Y-m-d H:i:s', $v['last_time']);
            } else {
                $info['list'][$k]['info_time']=date('Y-m-d H:i:s', $v['add_time']);
            }

            $info['list'][$k]['score_name'] = $this->score_name;
        }

        $now_merchant = M('Merchant')->where(array('mer_id' => $mer_id))->field('is_add_integral_mall')->find();
        $is_add_integral_mall = $now_merchant['is_add_integral_mall']?$now_merchant['is_add_integral_mall']:0;


        $arr['is_add_integral_mall'] = $is_add_integral_mall;

        $arr['info'] = $info;
        $this->returnCode(0, $arr);
    }


    //删除商品
    public function integral_mall_del(){
        $goods_id=I('goods_id',0,'intval');
        if(empty($goods_id)){
            $this->returnCode(1, '',L_('缺少参数！'));
        }
        $where=array();
        $where['goods_id']=$goods_id;
        $res=D('Merchant_integral_mall_goods')->goods_del($where);
        if(!$res){
            $this->returnCode(1,'', L_('数据处理有误！'));
        }else{
            if($res['status']){
                $this->returnCode(0,array(), $res['msg']);
            }else{
                $this->returnCode(1,'', $res['msg']);
            }
        }
    }


    //编辑商品-真实批发
    public function integral_mall_goods_info(){
        $goods_id=I('goods_id',0,'intval');
        $order_id=I('order_id',0,'intval');
        $type=I('type','','trim');
        $model=D('Merchant_integral_mall_goods');
        $mer_id=$this->merchant['mer_id'];

        if ($goods_id<=0 && $order_id<=0 && $type=='') {
            $now_merchant = M('Merchant')->where(array('mer_id' => $mer_id))->field('is_add_integral_mall')->find();
            $is_add_integral_mall = $now_merchant['is_add_integral_mall']?$now_merchant['is_add_integral_mall']:0;
            if ($is_add_integral_mall!=1) {
                $this->returnCode(1,'', L_('当前您不能自主添加/编辑商家积分商城商品，如需上架请联系管理员！'));
            }
        }
        $cate_list = $this->_get_integral_mall_category(0);

        $arr = array();
        $arr['cate_list'] = $cate_list;
        $arr['cate_list_json'] = json_encode($cate_list);
        // 配置项信息
        $config = array(
            'Currency_txt' => $this->config['Currency_txt'],
            'Currency_symbol' => $this->config['Currency_symbol'],
        );

        $config['merchant_integral_mall_control'] = $this->config['merchant_integral_mall_control']?$this->config['merchant_integral_mall_control']:0;
        // 添加 【商家类型功能】开关   0 关 1 开
        $config['merchant_type_switch'] = $this->config['merchant_type_switch']?$this->config['merchant_type_switch']:0;
        $arr['config'] = $config;

        if($goods_id>0){
            $where=array();
            $where['goods_id']=$goods_id;
            $where['mer_id']=$mer_id;
            $data=$model->goods_detail($where);
            if(!$data){
                $this->returnCode(1,'', L_('数据处理有误！'));
            }else{
                $info=$data['detail'];
                if ($info && $info['spec_val']) {
                    if ($info['spec_val']) {
                        $info['spec_list'] = $info['spec_val']['spec_list'];
                        $info['list'] = $info['spec_val']['list'];
                    }
                }
                $cate_list2 = $this->_get_integral_mall_category($info['cat_fid']);
                // 真实批发和自建积分商城商品，计费方式默认为0
                $info['freight_type'] = 0;
                $arr['now_goods'] = $info;
                $arr['cate_list2'] = $cate_list2;
            }
            $this->returnCode(0, $arr);
        } else if ($type && $type=='market' && $order_id>0) {
            $now_merchant = M('Merchant')->where(array('mer_id' => $mer_id))->field('is_publish_integral_mall')->find();
            $is_publish_integral_mall = $now_merchant['is_publish_integral_mall']?$now_merchant['is_publish_integral_mall']:0;
            if ($is_publish_integral_mall != 1) {
                $this->returnCode(1,'', L_('当前您不能上架到商家积分商城，如需上架请联系管理员！'));
            }
            $where=array();
            $where['order_id']=$order_id;
            $where['mer_id']=$mer_id;
            $where['status']=3;
            $market_order = M('Market_order')->where($where)->find();

            $goods_image_class = new goods_image();
            $tmp_pic_arr = explode(';', $market_order['image']);
            $pc_pic_list = array();
            $wap_pic_list = array();
            foreach ($tmp_pic_arr as $key => $value) {
                $pc_pic_list[$key]['title'] = $wap_pic_list[$key]['title'] = $value;
                $tmp_pic = $goods_image_class->get_image_by_path($value);
                $pc_pic_list[$key]['url'] = $wap_pic_list[$key]['url'] = $tmp_pic['image'];
                $pc_pic_list[$key]['m_url'] = $tmp_pic['m_image'];
            }
            $return = D('Market_order')->format_spec_value($market_order['spec_value'], $market_order['goods_id'], $order_id);

            $market_order['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
            $market_order['list'] = isset($return['list']) ? $return['list'] : '';


            $g_where['market_goods_id'] = $market_order['goods_id'];
            $g_where['mer_id'] = $mer_id;
            $mall_goods = $model->goods_detail($g_where, true, true);
//            $detail = $mall_goods['detail'];
            $detail = array();
            if ($detail && $detail['spec_val']) {
                $list_old = $detail['spec_val']['list'];
            } else {
                $list_old = array ();
            }

            foreach ($market_order['list'] as $key=>&$val) {
                $val['payment_pure_integral'] = $list_old[$key]['payment_pure_integral'];
                $val['exchange_limit_num'] = $list_old[$key]['exchange_limit_num'];
            }

            $info = array(
                'market_goods_id' => $market_order['goods_id'],
                'market_order_id' => $market_order['order_id'],
                'goods_name' => $market_order['name'],
                'sku' => $market_order['num'],
                'wap_pic_list' => $wap_pic_list,
                'pc_pic_list' => $pc_pic_list,
                'spec_list' => $market_order['spec_list'],
                'list' => $market_order['list'],
                'is_spec' =>  $market_order['spec_list'] ? true : false,
                'status' =>  1,
            );

            if ($detail && $detail['cat_fid'] > 0) {
                $info['cat_fid'] = $detail['cat_fid'];
            }
            if ($detail && $detail['cat_id'] > 0) {
                $info['cat_id'] = $detail['cat_id'];
            }
            if ($detail && $detail['deliver_type'] > 0) {
                $info['deliver_type'] = $detail['deliver_type'];
            }
            if ($detail && $detail['exchange_limit_num'] > 0) {
                $info['exchange_limit_num'] = $detail['exchange_limit_num'];
            }
            if ($detail && $detail['pc_pic_list'] > 0) {
                $info['pc_pic_list'] = $detail['pc_pic_list'];
            }
            if ($detail && $detail['wap_pic_list'] > 0) {
                $info['wap_pic_list'] = $detail['wap_pic_list'];
            }
            if ($detail && $detail['intro']) {
                $info['intro'] = $detail['intro'];
            }
            if ($detail && $detail['invoice_content']) {
                $info['invoice_content'] = $detail['invoice_content'];
            }
            if ($market_order && $market_order['des']) {
                $info['goods_content'] = replace_file_domain_content($market_order['des']);
            }
            if ($detail && $detail['goods_content']) {
                $info['goods_content'] = replace_file_domain_content($detail['goods_content']);
            }
            if ($detail && $detail['is_new']) {
                $info['is_new'] = $detail['is_new'];
            }
            if ($detail && $detail['status']) {
                $info['status'] = $detail['status'];
            }
            if ($market_order && $market_order['freight_type']>0) {
                $info['freight_type'] = $market_order['freight_type'];
            } elseif ($detail && $detail['freight_type']>0) {
                $info['freight_type'] = $detail['freight_type'];
            }
            // 真实批发和自建积分商城商品，计费方式默认为0
            $info['freight_type'] = 0;
            if ($market_order && $market_order['goods_weight']>0) {
                $info['goods_weight'] = $market_order['goods_weight'];
            } elseif ($detail && $detail['goods_weight']>0) {
                $info['goods_weight'] = $detail['goods_weight'];
            }
            if ($market_order && $market_order['goods_volume']>0) {
                $info['goods_volume'] = $market_order['goods_volume'];
            } elseif ($detail && $detail['goods_volume']>0) {
                $info['goods_volume'] = $detail['goods_volume'];
            }

            $arr['now_goods'] = $info;
            $this->returnCode(0, $arr);
        } else {
            $arr['now_goods'] = array();
            $this->returnCode(0, $arr);
        }
    }

    //编辑商品-真实批发
    public function integral_mall_goods_save(){
        $goods_id=I('goods_id',0,'intval');
        $order_id=I('order_id',0,'intval');
        $type=I('type','','trim');
        $model=D('Merchant_integral_mall_goods');
        $mer_id=$this->merchant['mer_id'];

        if ($goods_id<=0 && $order_id<=0 && $type=='') {
            $now_merchant = M('Merchant')->where(array('mer_id' => $mer_id))->field('is_add_integral_mall')->find();
            $is_add_integral_mall = $now_merchant['is_add_integral_mall']?$now_merchant['is_add_integral_mall']:0;
            if ($is_add_integral_mall!=1) {
                $this->returnCode(1,'', L_('当前您不能自主添加商家积分商城商品，如需上架请联系管理员！'));
            }
        }

        if(IS_POST){
            $data=$_POST;
            if (!$goods_id) {
                $data['deliver_type'] = 1; // 到点自提
                if ($type=='market') {
                    $data['goods_source_type'] = 2; // 商户真实批发
                } else {
                    $data['goods_source_type'] = 1; // 商户创建
                }
            }
            if ($data['is_spec'] && $data['market_order_id']>0) {
                $now_merchant = M('Merchant')->where(array('mer_id' => $mer_id))->field('is_publish_integral_mall')->find();
                $is_publish_integral_mall = $now_merchant['is_publish_integral_mall']?$now_merchant['is_publish_integral_mall']:0;
                if ($is_publish_integral_mall != 1) {
                    $this->returnCode(1,'', L_('当前您不能上架到商家积分商城，如需上架请联系管理员！'));
                }

                $where['order_id']=$data['market_order_id'];
                $where['mer_id']=$mer_id;
                $where['status']=3;
                $market_order = M('Market_order')->where($where)->find();
                $return = D('Market_order')->format_spec_value($market_order['spec_value'], $market_order['goods_id'], $data['market_order_id']);

                $spec_list = isset($return['spec_list']) ? $return['spec_list'] : '';
                $list = isset($return['list']) ? $return['list'] : '';

//                $data['freight_type'] = $market_order['freight_type']>0?$market_order['freight_type']:1;
                // 真实批发和自建积分商城商品，计费方式默认为0
                $data['freight_type'] = 0;
                $data['goods_volume'] = $market_order['goods_volume']>0?$market_order['goods_volume']:0;
                $data['goods_weight'] = $market_order['goods_weight']>0?$market_order['goods_weight']:0;
                if ($market_order['unit']) {
                    $data['unit'] = $market_order['unit'];
                }

                // 规格-所需积分
                $payment_pure_integrals = $data['payment_pure_integrals'] ? $data['payment_pure_integrals'] : array();
                unset($data['payment_pure_integrals']);
                // 规格-库存数量
                $skus = $data['skus'] ? $data['skus'] : array();
                unset($data['skus']);
                // 规格-每人限制兑换数量
                $exchange_limit_nums = $data['exchange_limit_nums'] ? $data['exchange_limit_nums'] : array();
                unset($data['exchange_limit_nums']);
                // 规格-已兑换人数
                $exchanged_nums = $data['exchanged_nums'] ? $data['exchanged_nums'] : array();
                unset($data['exchanged_nums']);

                if ($spec_list && $list) {
                    $payment_pure_integral = 0;
                    $g_where['market_goods_id']=$data['market_goods_id'];
                    $g_where['mer_id']=$mer_id;
                    $mall_goods = $model->goods_detail($g_where);
                    $detail = $mall_goods['detail'];
                    $goods_id = $detail['goods_id'];
                    if ($detail && $detail['spec_val']) {
                        $list_old = $detail['spec_val']['list'];
                    } else {
                        $list_old = array ();
                    }
                    if ($detail['market_order']) {
                        $market_order = explode(',', $detail['market_order']);
                    } else {
                        $market_order = array();
                    }
//                    if (in_array($data['market_order_id'], $market_order)) {
//                        $this->error(L_('该订单商品已上架到商家积分商城，请不要重复上架！'));
//                    }
                    $market_order[] = $data['market_order_id'];
                    $data['market_order'] = implode(',', $market_order);

                    $sku = 0;
                    $exchanged_num = 0;
                    foreach ($list as $key=>&$val) {
                        $index = $val['index'];
                        if(!is_numeric($payment_pure_integrals[$index])){
                            $this->returnCode(1,'', L_("所需纯X1必须为数字！",array("X1" => $this->score_name)));
                        }
                        $val['payment_pure_integral'] = $payment_pure_integrals[$index];
                        if ($payment_pure_integral>0) {
                            $payment_pure_integral = min($payment_pure_integral, $payment_pure_integrals[$index]);
                        } else {
                            $payment_pure_integral = $payment_pure_integrals[$index];
                        }
                        if ($skus[$index] > $val['stock_num']) {
                            $this->returnCode(1,'', L_('当前您不能上架到商家积分商城，库存不能大于大于当前购买数量！'));
                        }
                        $val['sku'] = $skus[$index];
                        $sku += $skus[$index];
                        $val['exchange_limit_num'] = $exchange_limit_nums[$index];
                        $val['exchanged_num'] = $exchanged_nums[$index];
                        if ($exchanged_nums[$index]>0) {
                            $exchanged_num += $exchanged_nums[$index];
                        }
                        if ($list_old && $list_old[$key]) {
                            $val['stock_num'] += $list_old[$key]['stock_num'];
                            $val['sell_num'] = $list_old[$key]['sell_num']>0 ? $list_old[$key]['sell_num'] : 0;
                            $val['exchanged_num'] += $list_old[$key]['exchanged_num'];
                            $val['sku'] += $list_old[$key]['sku'];
                        }
                    }
                    $spec_val = array();
                    $spec_val['spec_list'] = $spec_list;
                    $spec_val['payment_pure_integral'] = $payment_pure_integral;
                    $spec_val['exchanged_num'] = $exchanged_num;
                    $spec_val['list'] = $list;
                    $data['sku'] = $sku;
                    $data['payment_pure_integral'] = $payment_pure_integral;
                    $data['exchanged_num'] = $exchanged_num;
                    $data['spec_val'] = serialize($spec_val);
                }
            } elseif ($goods_id>0 && ($data['payment_pure_integrals'] || $data['skus'] || $data['exchange_limit_nums'] || $data['exchange_limit_nums'])) {
                $g_where['mer_id']=$mer_id;
                $g_where['goods_id']=$goods_id;
                $mall_goods = $model->goods_detail($g_where);
                $detail = $mall_goods['detail'];
                $spec_val = $detail['spec_val'];

                $spec_list = isset($spec_val['spec_list']) ? $spec_val['spec_list'] : '';
                $list = isset($spec_val['list']) ? $spec_val['list'] : '';
                fdump($list,'$list');

                if ($spec_val && $spec_list && $list) {
                    // 规格-所需积分
                    $payment_pure_integrals = $data['payment_pure_integrals'] ? $data['payment_pure_integrals'] : array();
                    unset($data['payment_pure_integrals']);
                    // 规格-库存数量
                    $skus = $data['skus'] ? $data['skus'] : array();
                    unset($data['skus']);
                    // 规格-每人限制兑换数量
                    $exchange_limit_nums = $data['exchange_limit_nums'] ? $data['exchange_limit_nums'] : array();
                    unset($data['exchange_limit_nums']);
                    // 规格-已兑换人数
                    $exchanged_nums = $data['exchanged_nums'] ? $data['exchanged_nums'] : array();
                    unset($data['exchanged_nums']);

                    $sku = 0;
                    $payment_pure_integral = 0;
                    $exchanged_num = 0;
                    foreach ($list as $key=>&$val) {
                        $index = $val['index'];
                        if(!is_numeric($payment_pure_integrals[$index])){
                            $this->returnCode(1,'', L_("所需纯X1必须为数字！",array("X1" => $this->score_name)));
                        }
                        $val['payment_pure_integral'] = $payment_pure_integrals[$index];
                        if ($skus[$index] > floatval($val['stock_num'])) {
                            $this->returnCode(1,'', L_('库存不能大于大于当前库存！'));
                        }
                        $val['sku'] = $skus[$index];
                        $sku += $skus[$index];
                        $val['exchange_limit_num'] = $exchange_limit_nums[$index];
                        $val['exchanged_num'] = $exchanged_nums[$index];
                        if (!$val['sell_num'] || $val['sell_num'] < 0) {
                            $val['sell_num'] = 0;
                        }
                        if ($exchanged_nums[$index]>0) {
                            $exchanged_num += $exchanged_nums[$index];
                        }
                        if ($payment_pure_integral>0) {
                            $payment_pure_integral = min($payment_pure_integral, $payment_pure_integrals[$index]);
                        } else {
                            $payment_pure_integral = $payment_pure_integrals[$index];
                        }
                    }
                    $spec_val = array();
                    $spec_val['spec_list'] = $spec_list;
                    $spec_val['payment_pure_integral'] = $payment_pure_integral;
                    $spec_val['exchanged_num'] = $exchanged_num;
                    $spec_val['list'] = $list;
                    $data['sku'] = $sku;
                    $data['payment_pure_integral'] = $payment_pure_integral;
                    $data['exchanged_num'] = $exchanged_num;
                    $data['spec_val'] = serialize($spec_val);
                }
            } else {
                $data['spec_val'] = '';
            }


            if($goods_id>0){
                $where=array();
                $where['goods_id']=$goods_id;
                unset($data['goods_id']);
                unset($data['goods_source_type']);
                $res=$model->merchant_integral_mall_goods_edit($where,$data);
            }else{
                $data['mer_id']=$mer_id;
                $data['status']=1;
                unset($data['goods_id']);
                $res=$model->merchant_integral_mall_goods_add($data);
            }
            if(!$res){
                $this->returnCode(1,'', L_('数据处理有误！'));
            }else{
                if($res['status']){
                    $this->returnCode(0,$data);
                }else{
                    $this->returnCode(1,'', $res['msg']);
                }
            }
        } else {
            $this->returnCode(1,'', L_('参数错误！'));
        }
    }


    // 销售订单-主要是发货和自提确认
    public function integral_mall_order() {
        $data = array('mer_id' => $this->merchant['mer_id']);
        if (isset($_POST['goods_id']) && $_POST['goods_id']) {
            $data['goods_id'] = intval($_POST['goods_id']);
        }
        $data['virtual_wholesale_mer_id'] = $this->merchant['mer_id'];


        $result = D('Merchant_integral_mall_goods_order')->getList($data, true, true);

        if ($result['orders']) {
            foreach ($result['orders'] as &$val) {
                $val['create_time'] = date('Y-m-d H:i:s', $val['create_time']);
                $val['score_name'] = $this->score_name;
                $val['Currency_symbol']=$this->config['Currency_symbol'];
            }
        }

        $this->returnCode(0, $result);
    }

    // 积分商城订单详情
    public function integral_mall_order_detail()
    {
        $order_id = intval($_POST['order_id']);
        $type = $_POST['type'];
        $mer_id = $this->merchant['mer_id'];
        $where = array('order_id' => $order_id);
        $order_info = D('Merchant_integral_mall_goods_order')->get_order_detail($where);
        if (!$order_info) {
            $this->returnCode(1,'', L_('该订单信息不存在！'));
        } elseif ($order_info['mer_id'] != $mer_id && $order_info['virtual_wholesale_mer_id'] != $mer_id) {
            $this->returnCode(1,'', L_('当前订单不是此商户的订单！'));
        } elseif ($order_info['spec_value'] && $order_info['spec_value']['list'] && $order_info['spec_index']) {
            $choose_spe = $order_info['spec_value']['list'][$order_info['spec_index']];
            $choose_spe['num'] = $order_info['num'];
            $arr['choose_spe'] = $choose_spe;
        }


        if (($type == 'push' || $type == 'self') && $order_info['status'] > 2) {
            $this->returnCode(1,'', L_('当前订单已经收货！'));
        }elseif (($type == 'push' || $type == 'self') && $order_info['status'] == 5) {
            $this->returnCode(1,'', L_('当前订单已经自提！'));
        }elseif (($type == 'push' || $type == 'self') && $order_info['status'] != 1 && $order_info['status'] != 2) {
            $this->returnCode(1,'', L_('当前订单不能发货/自提！'));
        }



        $order_info['create_time_info'] = date('Y-m-d H:i:s', $order_info['create_time']);
        if ($order_info['pay_time']) {
            $order_info['pay_time_info'] = date('Y-m-d H:i:s', $order_info['pay_time']);
        }
        if ($order_info['send_time']) {
            $order_info['send_time_info'] = date('Y-m-d H:i:s', $order_info['send_time']);
        }
        if ($order_info['pull_time']) {
            $order_info['pull_time_info'] = date('Y-m-d H:i:s', $order_info['pull_time']);
        }



        $arr['now_order'] = $order_info;
        $config = array(
            'Currency_txt' => $this->config['Currency_txt'],
            'Currency_symbol' => $this->config['Currency_symbol'],
        );
        $arr['config'] = $config;
        $this->returnCode(0, $arr);
    }


    public function integral_mall_express_list() {;
        $order_id = intval($_POST['order_id']);
        $mer_id = $this->merchant['mer_id'];
        $databaseMarketOrder = D('Merchant_integral_mall_goods_order');
        $marketOrder = $databaseMarketOrder->field(true)->where(array('order_id' => $order_id))->find();
        $arr['now_order'] = $marketOrder;
        $express_list = D('Express')->get_express_list();
        $arr['express_list'] = $express_list;
        $this->returnCode(0,$arr);
    }


    //发货
    public function integral_mall_push()
    {
        $order_id = intval($_POST['order_id']);
        $mer_id = $this->merchant['mer_id'];
        $where = array('order_id' => $order_id);
        $order_info = D('Merchant_integral_mall_goods_order')->get_order_detail($where);
        if (!$order_info) {
            $this->returnCode(1,'', L_('该订单信息不存在！'));
        } elseif ($order_info['mer_id'] != $mer_id && $order_info['virtual_wholesale_mer_id'] != $mer_id) {
            $this->returnCode(1,'', L_('当前订单不是此商户的订单！'));
        }elseif ($order_info['status'] > 2) {
            $this->returnCode(1,'', L_('当前订单已经收货！'));
        }elseif ($order_info['status'] == 5) {
            $this->returnCode(1,'', L_('当前订单已经自提！'));
        }elseif ($order_info['status'] != 1 && $order_info['status'] != 2) {
            $this->returnCode(1,'', L_('当前订单不能发货/自提！'));
        } elseif ($order_info['spec_value'] && $order_info['spec_value']['list'] && $order_info['spec_index']) {
            $choose_spe = $order_info['spec_value']['list'][$order_info['spec_index']];
            $choose_spe['num'] = $order_info['num'];
        }

        if ($order_info) {
            $express_id = isset($_POST['express_id']) ? intval($_POST['express_id']) : 0;
            $express_number = isset($_POST['express_number']) ? htmlspecialchars(trim($_POST['express_number'])) : 0;
            $send_note = isset($_POST['send_note']) ? htmlspecialchars(trim($_POST['send_note'])) : '';
            $express = D('Express')->get_express($express_id);
            if (empty($express)) {
                $this->returnCode(1,'', L_('请选择正确的快递公司！'));
                exit;
            }
            if (empty($express_number)) {
                $this->returnCode(1,'', L_('请选择填写快递单号！'));
                exit;
            }
            $data['express_id'] = $express_id;
            $data['express_number'] = $express_number;
            $data['send_note'] = $send_note;
            $data['send_time'] = time();
            $data['status'] = 2;
            $data['last_time'] = time();
            if (M('Merchant_integral_mall_goods_order')->where(array('order_id' => $order_id, 'mer_id' => $order_info['mer_id']))->save($data)) {
                $sms_data = array('mer_id' => $mer_id, 'store_id' => 0, 'type' => 'integral_mall');
                $now_user = M('User')->where(array('uid' => $order_info['uid']))->field('phone')->find();

                $sms_data['uid'] = 0;
                $sms_data['mobile'] = $now_user['phone'];
                $sms_data['sendto'] = 'user';
                $sms_data['content'] = L_('您在X1兑换了X2共X3订单号：X4对方已经发货。请您注意查看并处理!',array('X1'=>$this->merchant_session['name'],'X2'=>$order_info['goods_name'],'X3'=>$order_info['num'] . '份','X4'=>$order_info['orderid']));
                Sms::sendSms($sms_data);
                D('Merchant_integral_mall_log')->add_log(array('order_id' => $order_id, 'status' => 2, 'from_uid' => $mer_id, 'from_type' => 2, 'note' => '商户发货'));

                $this->returnCode(0,array('url' => U('Integral_mall/sell_order')), L_('发货成功！'));
            } else {
                $this->returnCode(1,'', L_('发货失败！'));
            }
        } else {
            $this->returnCode(1,'', L_('错误的订单信息！'));
        }
    }


    // 自提
    public function integral_mallself_mention() {
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $mer_id = $this->merchant['mer_id'];
        $where = array('order_id' => $order_id, 'mer_id' => $mer_id);
        $order = D('Merchant_integral_mall_goods_order')->field(true)->where($where)->find();
        if (empty($order)) {
            $this->returnCode(1,'', L_('订单信息不正确！'));
        }
        if ($order['status'] >= 5) {
            $this->returnCode(1,'', L_('此单已确认自提，请不要重复确认！'));
        }
        if ($order['status'] != 1) {
            $this->returnCode(1,'', L_('此单当前不能确认自提！'));
        }
        $data = array('status' => 5);
        $data['arrival_time'] = $_SERVER['REQUEST_TIME'];
        $data['last_time'] = $_SERVER['REQUEST_TIME'];
        if (D('Merchant_integral_mall_goods_order')->where($where)->save($data)) {

            D('Merchant_integral_mall_log')->add_log(array('order_id' => $order_id, 'status' => 5, 'from_uid' => $mer_id, 'from_type' => 2,  'note' => '用户确认收货'));

            // 用户确认收货-计算给予商家结算
            D('Merchant_integral_mall_goods_order')->order_settlement($order);

            $this->returnCode(0,array('url' => U('Integral_mall/sell_order')));
        } else {
            $this->returnCode(1,'', L_('订单状态修改失败，稍后重试！'));
        }
    }

    // 营销统计
    // 今日营业统计及与昨日对比数据
    public function statisticsToday(){
        $business_type = $_POST['business_type'] ? $_POST['business_type'] : 'shop';
        $order_type = $_POST['order_type'] ? $_POST['order_type'] : 'shop';

        // 开始时间
        $start_time = strtotime(date('Y-m-d'));
        // 结束时间
        $end_time = time();

        // 昨日开始时间
        $yesterday_start_time =  $start_time-86400;
        // 昨日结束时间
        $yesterday_end_time =  $end_time - 86400;

        // 支付时间
        $condition['pay_time'] = array('between',[$start_time,$end_time]);
        $m_condition['o.pay_time'] = array('between',[$start_time,$end_time]);

        // 商家id
        $condition['mer_id'] = $this->merchant['mer_id'];
        $m_condition['o.mer_id'] = $this->merchant['mer_id'];

        // 店铺id
        $store_id = $_POST['store_list'];
        if ($store_id&&$store_id!=-1) {
            $condition['store_id'] = array('in',$store_id);
            $m_condition['o.store_id'] = array('in',$store_id);
        }

        // 返回数据
        $returnArrFormat = [];

        /*********************** 今日订单 ******************/
        switch ($business_type) {
            case 'market'://营销
                switch ($order_type) {
                    case 'shop':
                        $business_model = 'Shop_order';
                        break;
                }
                $returnArr = D($business_model)->getRevenueStatisticsMarketMerchant($this->merchant['mer_id'],$start_time,$_POST);
                break;
            case 'shop'://外卖
                $business_model = 'Shop_order';
                // $returnArr = D($business_model)->getRevenueStatistics(0,$start_time,$_POST);
                $returnArr = D($business_model)->getHistoryStatistics($condition,$m_condition,1);
                break;
            case 'mall'://外卖
                $business_model = 'Shop_order';
                // $returnArr = D($business_model)->getRevenueStatistics(0,$start_time,$_POST);
                $returnArr = D($business_model)->getHistoryStatistics($condition,$m_condition,1,1);
                break;

        }

        /*********************** 昨日订单  ****************/
        switch ($business_type) {
            case 'market'://营销
                switch ($order_type) {
                    case 'shop':
                        $business_model = 'Shop_order';
                        break;
                }
                $returnArrCompare = D($business_model)->getRevenueStatisticsMarketMerchant($this->merchant['mer_id'],$yesterday_start_time,$_POST);
                break;
            case 'shop'://外卖
                $condition['pay_time'] = array('between',[$yesterday_start_time,$yesterday_end_time]);
                $m_condition['o.pay_time'] = array('between',[$yesterday_start_time,$yesterday_end_time]);
                $business_model = 'Shop_order';
                // $returnArrCompare = D($business_model)->getRevenueStatistics(0,$yesterday_start_time,$_POST);
                $returnArrCompare = D($business_model)->getHistoryStatistics($condition,$m_condition,1);
                break;
            case 'mall'://外卖
                $condition['pay_time'] = array('between',[$yesterday_start_time,$yesterday_end_time]);
                $m_condition['o.pay_time'] = array('between',[$yesterday_start_time,$yesterday_end_time]);
                $business_model = 'Shop_order';
                // $returnArrCompare = D($business_model)->getRevenueStatistics(0,$yesterday_start_time,$_POST);
                $returnArrCompare = D($business_model)->getHistoryStatistics($condition,$m_condition,1,1);
                break;

        }

        /*********************** 订单数据对比  ****************/
        // 获得对比后的数据
        $returnArrFormat = D('Merchant_revenue_statistics')->getCompareData($business_type,$returnArr,$returnArrCompare,0,0,1);

        $this->returnCode(0,$returnArrFormat);
    }

    // 今日营业统计及与昨日对比数据 报表
    public function statisticsTodayChart(){
        $business_type = $_POST['business_type'] ? $_POST['business_type'] : 'shop';
        $order_type = $_POST['order_type'] ? $_POST['order_type'] : 'shop';
        // 累计
        // 营业额 0点到现在 各时段数据
        $data_type = $_POST['data_type']; // 0 营业额 1 有效订单
        $is_add_up = $_POST['is_add_up']; // 0 分时 1 累计
        // 商家id
        $condition['mer_id'] = $this->merchant['mer_id'];

        // 店铺id
        $store_id = $_POST['store_list'];
        if ($store_id&&$store_id!=-1) {
            $condition['store_id'] = array('in',$store_id);
        }

        // 已支付
        $condition['paid'] = 1;

        // 去掉商城
        $condition['order_from'] = ['neq',1];

        $time_arr = array(0,2,4,6,8,10,12,14,16,18,20,22);

        // 今日0点时间戳
        $start_time = strtotime(date('Y-m-d'));

        $returnArr = [];
        $todayArr = [];
        $yesterdayArr = [];
        $yesterdayTotal = 0;
        foreach ($time_arr as $key => $_time) {
            // 开始时间
            if ($is_add_up) {
                // 累计
                $tmp_s_time = $start_time;
            }else{
                // 分时
                $tmp_s_time = $start_time + $_time*3600;
            }

            // 结束时间
            $tmp_e_time = $start_time + $_time*3600 + 7200;

            // 昨日订单
            if ($is_add_up) {
                // 累计
                $tmp_s_time_y = $start_time - 86400;
            }else{
                // 分时
                $tmp_s_time_y = $start_time + $_time*3600 - 86400;
            }

            // 结束时间
            $tmp_e_time_y = $start_time + $_time*3600 + 7200 - 86400;

            switch ($business_type) {
                case 'shop': // 外卖
                    // 今日订单
                    if ($tmp_e_time<time()+7200) {
                        $condition['pay_time'] = array('between',[$tmp_s_time,$tmp_e_time]); // 支付时间
                        if ($data_type==1) {
                            // 有效订单统计
                            $condition['order_status'] = array('gt',0); // 已接单
                            $condition['status'] = array('in','0,1,2,3'); // 未取消 未退款
                            $returnArr[$key]['today'] = M('Shop_order')->where($condition)->count();
                        }else{
                            // 营业额统计
                            $total_price = M('Shop_order')->field('sum(total_price) as total_price')->where($condition)->find();
                            $returnArr[$key]['today'] = getFormatNumber($total_price['total_price']);
                        }
                        $todayArr[] = $returnArr[$key]['today'];
                    }

                    // 昨日订单
                    $condition['pay_time'] = array('between',[$tmp_s_time_y,$tmp_e_time_y]); // 支付时间
                    if ($data_type==1) {
                        // 有效订单统计
                        $condition['order_status'] = array('gt',0); // 已接单
                        $condition['status'] = array('in','0,1,2,3'); // 未取消 未退款
                        $returnArr[$key]['yesterday'] = M('Shop_order')->where($condition)->count();
                    }else{
                        // 营业额统计
                        $total_price = M('Shop_order')->field('sum(total_price) as total_price')->where($condition)->find();
                        $returnArr[$key]['yesterday'] = getFormatNumber($total_price['total_price']);
                    }
                    $yesterdayTotal += $returnArr[$key]['yesterday'];
                    $yesterdayArr[] = $returnArr[$key]['yesterday'];
                    break;
                case 'mall': // 外卖
                    // 今日订单
                    if ($tmp_e_time<time()+7200) {
                        $condition['pay_time'] = array('between',[$tmp_s_time,$tmp_e_time]); // 支付时间
                        if ($data_type==1) {
                            // 有效订单统计
                            $condition['order_status'] = array('gt',0); // 已接单
                            $condition['status'] = array('in','0,1,2,3'); // 未取消 未退款
                            $returnArr[$key]['today'] = M('Shop_order')->where($condition)->count();
                        }else{
                            // 营业额统计
                            $total_price = M('Shop_order')->field('sum(total_price) as total_price')->where($condition)->find();
                            $returnArr[$key]['today'] = getFormatNumber($total_price['total_price']);
                        }
                        $todayArr[] = $returnArr[$key]['today'];
                    }

                    // 昨日订单
                    $condition['pay_time'] = array('between',[$tmp_s_time_y,$tmp_e_time_y]); // 支付时间
                    if ($data_type==1) {
                        // 有效订单统计
                        $condition['order_status'] = array('gt',0); // 已接单
                        $condition['status'] = array('in','0,1,2,3'); // 未取消 未退款
                        $returnArr[$key]['yesterday'] = M('Shop_order')->where($condition)->count();
                    }else{
                        // 营业额统计
                        $total_price = M('Shop_order')->field('sum(total_price) as total_price')->where($condition)->find();
                        $returnArr[$key]['yesterday'] = getFormatNumber($total_price['total_price']);
                    }
                    $yesterdayTotal += $returnArr[$key]['yesterday'];
                    $yesterdayArr[] = $returnArr[$key]['yesterday'];
                    break;
                case 'market': // 营销
                    // 有效订单
                    $condition['order_status'] = array('gt',0);  // 已接单
                    $condition['status'] = array('in','0,1,2,3');  // 未取消 未退款

                    // 今日订单
                    if ($tmp_e_time<time()+7200) {
                        // 支付时间
                        $condition['pay_time'] = array('between',[$tmp_s_time,$tmp_e_time]);

                        $returnArr = D('Shop_order')->getMarketOrderStatisticsMerchant($condition);
                        if ($data_type==1) {
                            // 带动订单
                            $returnArr[$key]['today'] = $returnArr['market_count'];
                        }else{
                            // 活动总营业额
                            $returnArr[$key]['today'] = $returnArr['market_total_money'];
                        }
                        $todayArr[] = $returnArr[$key]['today'];
                    }

                    // 昨日订单
                    $condition['pay_time'] = array('between',[$tmp_s_time_y,$tmp_e_time_y]); // 支付时间
                    $returnArr = D('Shop_order')->getMarketOrderStatisticsMerchant($condition);
                    if ($data_type==1) {
                        // 带动订单
                        $returnArr[$key]['yesterday'] = $returnArr['market_count'];

                    }else{
                        // 活动总营业额
                        $returnArr[$key]['yesterday'] =  $returnArr['market_total_money'];
                    }
                    $yesterdayTotal += $returnArr[$key]['yesterday'];
                    $yesterdayArr[] = $returnArr[$key]['yesterday'];
                    break;
            }
            $returnArr[$key]['time'] = date('H:i',$tmp_s_time).'-'.date('H:i',$tmp_e_time);

        }
        $returnArr['yesterdayArr'] = $yesterdayArr;
        $returnArr['yesterdayTotal'] = L_('昨日全天').getFormatNumber($yesterdayTotal).($data_type ? L_('单') : C('config.Currency_txt'));
        $returnArr['todayArr'] = $todayArr;
        $this->returnCode(0,$returnArr);
    }

    // 历史数据分析 不包括今日
    public function statisticsHistory(){
        $business_type = $_POST['business_type'] ? $_POST['business_type'] : 'shop';
        $order_type = $_POST['order_type'] ? $_POST['order_type'] : 'shop';
        // 昨日 近七日 近30日 自定义
        $type = $_POST['type']?$_POST['type']:2;

        // 昨日23:59点时间戳
        $end_time = strtotime(date('Y-m-d'))-1;
        switch ($type) {
            case '1': // 昨日
                $start_time = $end_time - 86400;

                // 对比前天时间范围
                $compare_end_time = $start_time - 1;
                $compare_start_time = $compare_end_time - 86400;
                break;
            case '2': // 近七日
                $start_time = $end_time - 86400*7;

                // 对比前七日时间范围
                $compare_end_time = $start_time - 1;
                $compare_start_time = $compare_end_time - 86400*7;
                break;
            case '3': // 近30日
                $start_time = $end_time - 86400*30;

                // 对比30天时间范围
                $compare_end_time = $start_time - 1;
                $compare_start_time = $compare_end_time - 86400*30;
                break;
            case '4': // 自定义
                $start_time = strtotime($_POST['start_time']);
                $end_time = strtotime($_POST['end_time'])+86399;

                // 对比时间范围
                $compare_end_time = $start_time - 1;
                $compare_start_time = $compare_end_time - ($end_time - $start_time);
                break;
        }

        $compare_text = L_('比前').ceil(($end_time-$start_time)/86400).L_('日');
        $condition['pay_time'] = array('between',[$start_time,$end_time]); // 支付时间
        $m_condition['o.pay_time'] = array('between',[$start_time,$end_time]); // 支付时间

        // 商家id
        $condition['mer_id'] = $this->merchant['mer_id'];
        $m_condition['o.mer_id'] = $this->merchant['mer_id'];

        // 店铺id
        $store_id = $_POST['store_list'];
        if ($store_id&&$store_id!=-1) {
            $condition['store_id'] = array('in',$store_id);
            $m_condition['o.store_id'] = array('in',$store_id);
        }

        // 返回数据
        $returnArrFormat = [];
        switch ($business_type) {
            case 'shop': // 外卖
                /*********************** 查询显示数据  *************************/
                $returnArr = D('Shop_order')->getHistoryStatistics($condition,$m_condition,1);

                /*********************** 查询对比数据  *************************/
                $condition['pay_time'] = array('between',[$compare_start_time,$compare_end_time]); // 支付时间
                $m_condition['o.pay_time'] = array('between',[$compare_start_time,$compare_end_time]); // 支付时间
                $returnArrCompare = D('Shop_order')->getHistoryStatistics($condition,$m_condition);
                break;
            case 'mall': // 外卖
                /*********************** 查询显示数据  *************************/
                $returnArr = D('Shop_order')->getHistoryStatistics($condition,$m_condition,1,1);

                /*********************** 查询对比数据  *************************/
                $condition['pay_time'] = array('between',[$compare_start_time,$compare_end_time]); // 支付时间
                $m_condition['o.pay_time'] = array('between',[$compare_start_time,$compare_end_time]); // 支付时间
                $returnArrCompare = D('Shop_order')->getHistoryStatistics($condition,$m_condition,0,1);
                break;
            case 'market': // 营销
                $_POST['start_time'] = $start_time;
                $_POST['end_time'] = $end_time;
                $returnArr = D('Shop_order')->getRevenueStatisticsMarketMerchant($this->merchant['mer_id'],0,$_POST);

                // 处理数据
                $returnArr = D('Revenue_statistics')->getFormatData($business_type,$returnArr);

                $_POST['start_time'] = $compare_start_time;
                $_POST['end_time'] = $compare_end_time;
                $returnArrCompare = D('Shop_order')->getRevenueStatisticsMarketMerchant($this->merchant['mer_id'],0,$_POST);

                // 处理数据
                $returnArrCompare = D('Revenue_statistics')->getFormatData($business_type,$returnArrCompare);
                break;
        }

        // 获得对比后的数据
        $returnArrFormat = D('Merchant_revenue_statistics')->getCompareData($business_type,$returnArr,$returnArrCompare,1,$compare_text,1);

        $returnArrFormat['discount_info'] = $returnArr['discount_info'];
        $this->returnCode(0,$returnArrFormat);
    }

    // 菜品分析
    public function statisticsGoods(){
        // $this->merchant['mer_id'] = 811;
        $_POST['type'] = !empty($_POST['type']) ? $_POST['type'] : 'shop';
        $_POST['selectTimeType'] = !empty($_POST['selectTimeType']) ? $_POST['selectTimeType'] : 7;
        $_POST['order_sort'] = !empty($_POST['order_sort']) ? $_POST['order_sort'] : 'sale_money';
        $_POST['page'] = !empty($_POST['page']) ? $_POST['page'] : '1';
        $store_id = intval($_POST['store_id']);

        // 业务类型
        $type_arr = array(
            'shop'=>$this->config['shop_alias_name'],
            'meal'=>$this->config['meal_alias_name'],
            'mall'=>$this->config['mall_alias_name'],
        );
        // 查看有没有老版的订餐管理
        $menu = M('System_menu')->where(['name'=>'订餐管理','status'=>1])->find();
        if($menu){
            $type_arr['dining'] = $this->config['meal_alias_name'] ? '新版'. $this->config['meal_alias_name'] : '新版餐饮';
        }else{
            unset($type_arr['meal']);
            $type_arr['dining'] = $this->config['meal_alias_name'] ?: '餐饮';
        }

        if($this->config['single_system'] && $this->config['shop_alias_name']){
            if($type_arr['meal']){
                unset($type_arr['meal']);
            }
            unset($type_arr['mall']);
            unset($type_arr['dining']);
        }
        $returnArr['typeArr'] = $type_arr;

        // 时间
        $returnArr['selectTimeTypeArr'] = [
            '-1' => L_('昨天'),
            '7' => L_('近7天'),
            '30' => L_('近30天'),
        ];

        // 店铺列表
        $where['mer_id'] = $this->merchant['mer_id'];
        $where['mer_id'] = $this->merchant['mer_id'];
        $where['status'] = array('neq','4');
        $store_list = M('Merchant_store')->where($where)->field('store_id,name')->order('store_id ASC')->select();

        // 默认选中第一个店铺
        if (!$store_id){
            $store_id = $store_list[0]['store_id'];
        }
        $returnArr['storeArr'] = $store_list;
        $param = $_POST;
        $param['store_list'] = (string)$store_id;
        $param['mer_id'] = $this->merchant['mer_id'];

        $goods_list = D('Order')->goods_date(2,$param);
        foreach ($goods_list['goods_date'] as $key => &$value) {
            $value['ranking'] = ($_POST['page']-1)*15+$key+1;
        }
        $returnArr['goods_list'] = $goods_list;

        $this->returnCode(0,$returnArr);
    }

    /*
     * 超值换购
     * 必传 store_id 店铺ID
     */
    public function shop_repurchase()
    {
        $where = array();
        // 店铺ID
        $where['store_id'] = trim($_POST['store_id']);
        $where['is_delete'] = 0;
        $page = $_POST['page'] ? $_POST['page'] : 1;

        $count = D('Shop_repurchase')->where($where)->count();
        $pageTotal = ceil($count/10);
        $start_limit = ($page-1)*10;
        $list = D('Shop_repurchase')->where($where)->limit($start_limit . ',10')->select();
        foreach ($list as $key => &$value) {
            $week_arr = explode(',', $value['week']);
            $week_str = '';
            foreach ($week_arr as $_w) {
                $week_str .= $this->get_week($_w).',';
            }
            $value['week_str'] = trim($week_str,',');
            $value['start_time'] = date('Y-m-d H:i:s',$value['start_time'] );
            $value['end_time'] = date('Y-m-d H:i:s',$value['end_time'] );

            $where_goods['r.rep_id'] = $value['rep_id'];
            $goods = M('Shop_repurchase_goods')->field('r.rep_id,r.price as r_price,r.stock_num as r_stock_num,r.sell_count as r_sell_count,g.*')->join('as r left join '.C('DB_PREFIX')._view('shop_goods').' as g on g.goods_id=r.goods_id')->where($where_goods)->select();
            $goodsName = array_column($goods, 'name');
            $value['goodsName'] = implode('，', $goodsName);

            if ($value['status']==0) {
                if ($value['start_time']>time()) {
                    $value['status_str'] = L_('未开始');
                }elseif ($value['start_time']>time()) {
                    $value['status_str'] = L_('已过期');
                }else{
                    $value['status_str'] = L_('未使用');
                }
            }elseif ($value['status']==1) {
                $value['status_str'] = L_('进行中');
            }elseif ($value['status']==2) {
                $value['status_str'] = L_('已失效');
            }

        }
        $returnArr['list'] = $list;
        $returnArr['pageTotal'] = $pageTotal;
        $returnArr['page'] = $page;
        $this->returnCode(0,$returnArr);
    }

    /*
     * 超值换购 添加
     * 必传 store_id 店铺ID
     */
    public function shop_repurchase_add()
    {
        $data = $_POST;
        $data['start_time']=strtotime($data['start_time']);
        $data['end_time']=strtotime($data['end_time']);
        if(!$data['name']){
            $this->returnCodeError(L_('请输入活动名称'));
        }

        if($data['end_time']<=$data['start_time']){
            $this->returnCodeError(L_('活动起始时间设置有误'));
        }
        if ($data['week'] === '') {
            $this->returnCodeError(L_('请选择星期几显示'));
        }

        if ($data['is_limit'] == 1 && $data['limit_money'] <= 0) {
            $this->returnCodeError(L_('请填写门槛金额'));
        }
        if (!$data['goods_id']) {
            $this->returnCodeError(L_('请添加活动商品'));
        }
        $data['mer_id'] = $this->merchant['mer_id'];
        $data['addtime'] = time();
        if($rep_id = M('Shop_repurchase')->data($data)->add()){
            // 插入商品
            $goodsSource = M('Shop_goods')->where(['goods_id'=>['in',$_POST['goods_id']]])->select();
            $goodsSourcePrice = array_column($goodsSource, 'price','goods_id');
            $goodsSourceStock = array_column($goodsSource, 'stock_num','goods_id');
            $goods_data = array();
            foreach ($_POST['goods_id'] as $key => $value) {
                if ($_POST['price'][$key]>$goodsSourcePrice[$value]) {
                    $this->error(L_('活动价格不能大于商品原价'));
                }

                if ($goodsSourceStock[$value]!=-1) {
                    if ($_POST['stock_num'][$key]==-1) {
                        $this->error(L_('活动库存不能大于商品原库存'));
                    }
                    if ($_POST['stock_num'][$key]>$goodsSourceStock[$value]) {
                        $this->error(L_('活动库存不能大于商品原库存'));
                    }
                }

                $goods_data[] = array(
                    'rep_id' => $rep_id,
                    'store_id' => $data['store_id'],
                    'goods_id' => $value,
                    'price' => $_POST['price'][$key],
                    'stock_num' => $_POST['stock_num'][$key],
                    'addtime' => time(),
                    'mer_id' => $this->merchant['mer_id'],
                );
            }
            M('Shop_repurchase_goods')->addAll($goods_data);
            $this->returnCodeOK(L_('添加成功'));
        }else{
            $this->returnCodeError(L_('添加失败'));
        }

    }

    /*
     * 超值换购 添加
     * 必传 store_id 店铺ID
     */
    public function shop_repurchase_edit()
    {
        $rep_id = intval($_POST['rep_id']);
        $where['store_id'] = intval($_POST['store_id']);
        $where['rep_id'] = intval($_POST['rep_id']);
        $where['is_delete'] = 0;
        $detail = M('Shop_repurchase')->where($where)->find();
        if (!$detail) {
            $this->returnCodeError(L_('活动不存在'));
        }
        $data = $_POST;
        $data['end_time']=strtotime($data['end_time']);
        if($data['end_time']<=$detail['start_time']){
            $this->returnCodeError('活动结束时间设置有误');
        }
        if (!$data['goods_id']) {
            $this->returnCodeError(L_('请添加活动商品'));
        }

        if(false !== M('Shop_repurchase')->where($where)->save($data)){
            // 插入商品
            unset($where['is_delete']);
            $goods_list = array_column(M('Shop_repurchase_goods')->data($where)->select(), 'goods_id');
            // 删除
            $delete_goods_ids = array_diff($goods_list, $_POST['goods_id']);
            if ($delete_goods_ids) {
                $where['goods_id'] = array('in',$delete_goods_ids);
                M('Shop_repurchase_goods')->where($where)->delete();
            }

            $goodsSource = M('Shop_goods')->where(['goods_id'=>['in',$_POST['goods_id']]])->select();
            $goodsSourcePrice = array_column($goodsSource, 'price','goods_id');
            $goodsSourceStock = array_column($goodsSource, 'stock_num','goods_id');
            foreach ($_POST['goods_id'] as $key => $value) {
                $goods_data = array();
                $where['goods_id'] = $value;
                if ($_POST['price'][$key]>$goodsSourcePrice[$value]) {
                    $this->returnCodeError(L_('活动价格不能大于商品原价'));
                }

                if ($goodsSourceStock[$value]!=-1) {
                    if ($_POST['stock_num'][$key]==-1) {
                        $this->returnCodeError(L_('活动库存不能大于商品原库存'));
                    }
                    if ($_POST['stock_num'][$key]>$goodsSourceStock[$value]) {
                        $this->returnCodeError(L_('活动库存不能大于商品原库存'));
                    }
                }
                if (M('Shop_repurchase_goods')->where($where)->find()) {
                    $goods_data['price'] = $_POST['price'][$key];
                    $goods_data['stock_num'] = $_POST['stock_num'][$key];
                    M('Shop_repurchase_goods')->where($where)->save($goods_data);
                }else{
                    $goods_data = array(
                        'rep_id' => $rep_id,
                        'store_id' => $data['store_id'],
                        'goods_id' => $value,
                        'price' => $_POST['price'][$key],
                        'stock_num' => $_POST['stock_num'][$key],
                        'addtime' => time(),
                        'mer_id' => $this->merchant['mer_id'],
                    );
                    M('Shop_repurchase_goods')->add($goods_data);
                }
            }
            $this->returnCodeOk(L_('修改成功'));
        }else{
            $this->returnCodeError(L_('修改失败'));
        }
    }

    // 删除超值换购
    public function shop_repurchase_del(){
        $where = array();
        // 店铺ID
        $where['store_id'] = trim($_POST['store_id']);
        $where['rep_id'] = trim($_POST['rep_id']);
        $where['is_delete'] = 0;
        $detail = M('Shop_repurchase')->where($where)->find();
        if (!$detail) {
            $this->returnCodeError(L_('活动不存在或已删除'));
        }
        if(M('Shop_repurchase')->where($where)->setField('is_delete',1)){
            $this->returnCodeOk(L_('删除成功'));
        }else{
            $this->returnCodeError(L_('删除失败'));
        }
    }

    // 超值换购设置使用
    public function shop_repurchase_set_use(){
        $where = array();
        $where['rep_id'] = intval($_POST['rep_id']);
        $where['mer_id'] = $this->merchant['mer_id'];
        $where['is_delete'] = 0;
        $detail = M('Shop_repurchase')->where($where)->find();
        if (!$detail) {
            $this->returnCodeError(L_('活动不存在或已删除'));
        }

        // 使用中的设为未使用
        $where_nouse['store_id'] = $detail['store_id'];
        $where_nouse['status'] = 1;
        M('Shop_repurchase')->where($where_nouse)->setField('status',0);

        // 将此活动设为已使用
        if(M('Shop_repurchase')->where($where)->setField('status',1)){
            $this->returnCodeError(L_('设置成功'));
        }else{
            $this->returnCodeError(L_('设置失败'));
        }
    }

    // 超值换购设置失效
    public function shop_repurchase_set_lose(){
        $where = array();
        // 店铺ID
        $where['rep_id'] = intval($_POST['rep_id']);
        $where['mer_id'] = $this->merchant['mer_id'];
        $where['is_delete'] = 0;
        $detail = M('Shop_repurchase')->where($where)->find();
        if (!$detail) {
            $this->returnCodeError(L_('活动不存在或已删除'));
        }

        // 将此活动设为已使用
        if(M('Shop_repurchase')->where($where)->setField('status',2)){
            $this->returnCodeOk(L_('设置成功'));
        }else{
            $this->returnCodeError(L_('设置失败'));
        }
    }


    // 超值换购设置失效
    public function getPrintType(){
        $printType = [
            1 => L_('易联云打印机'),
            2 => L_('有线打印机'),
            4 => L_('飞鹅打印机'),
            5 => L_('云打印机'),
            6 => L_('手持POS终端'),
        ];
        $this->returnCodeOk($printType);
    }

    // 权限列表
    public function auth_package(){

        if ($this->config['buy_merchant_auth'] == 0) {
            $this->returnCodeError(L_('管理员未开启该功能'));
        }

        if($this->merchant['merchant_end_time'] && $this->merchant['merchant_end_time'] < time()) {
            $is_has_default = 0; // 不再显示默认套餐
        }

        $menu_group = D('Merchant_auth')->get_auth_group_list_wap($page = 20,$is_has_default);

        foreach ($menu_group['group_list'] as &$item) {
            if ($item['is_try'] && M('Authority_group_order')->where(array('label' => $item['id'], 'mer_id' => $this->merchant['mer_id']))->find()) {
                $item['had_try'] = 1;
            }
        }

        $now_group = M('Authority_group')->where(array('id' => $this->merchant['authority_group_id']))->find();
        $now_merchant = D('Merchant')->get_info($this->merchant['mer_id']);
        $now_merchant['merchant_end_time_str'] = date('Y-m-d H:i:s',$now_merchant['merchant_end_time']);

        if ($now_merchant['is_try']) {
            $now_merchant['merchant_end_time'] = $now_merchant['try_end_time'];
        }
//        if ($this->merchant['is_try']) {
//            $this->merchant['merchant_end_time'] = $this->merchant['try_end_time'];
//        }

        $return['list'] = $menu_group;
        $return['list']['now_merchant'] = $now_merchant;
        $return['list']['config'] = [
            'Currency_symbol' => $this->config['Currency_symbol'],
            'Currency_txt'    => $this->config['Currency_txt']
        ];
        $this->returnCode(0,$return);
    }

    //套餐权限购买记录
    public function auth_package_buy_record() {
        $page		=	I('pindex',1);
        $count = M('Authority_group_order')->where(array('mer_id' => $this->merchant['mer_id'], 'paid' => 1))->count();

        $order_list = M('Authority_group_order')->field('o.* ,g.name,g.time_type,g.time_type,g.is_default,g.des,g.detail,g.percent,g.sort,g.time_type,g.try_time,g.try_time_type')->join('as o LEFT JOIN ' . C('DB_PREFIX') . 'authority_group g ON g.id = o.label')->where(array('mer_id' => $this->merchant['mer_id'], 'paid' => 1))->order('o.order_id DESC')->page($page, 10)->select();

        foreach ($order_list as &$item) {
            $item['time_type_str'] = $this->time_type[$item['time_type']];
            $item['add_time'] = date('Y-m-d H:i:s', $item['add_time']);
            $item['try_time'] = $item['try_time'] ?? '';
            if(!empty($item['try_time'])) {
                $item['try_time_type_str'] = $this->time_type[$item['try_time_type']];
            }

        }

        $newest_order = M('Authority_group_order')->where(array('mer_id' => $this->merchant['mer_id'], 'paid' => 1))->order('order DESC')->find();

        $config = [
            'Currency_symbol' => $this->config['Currency_symbol'],
            'Currency_txt'    => $this->config['Currency_txt']
        ];

        $arr = [
            'order_list' => $order_list,
            'config' => $config,
            'count' => $count,
            'page' => ceil($count / 10),
        ];

        $this->returnCode(0,$arr);
    }

    //购买前验证
    public function check_before_buy()
    {
        $auth_id = $_POST['auth_id'];
        $is_try = $_POST['is_try'];
        $num = isset($_POST['num']) ? $_POST['num'] : 1;

        // 配置项信息
        $config = array(
            'Currency_txt' => $this->config['Currency_txt'],
            'Currency_symbol' => $this->config['Currency_symbol'],
        );
        $res['config'] = $config;

        if (empty($is_try)) {
            $res['fill_money'] = -1;
            $this->returnCode(0,$res);
        }

        $res['fill_money'] = D('Merchant_auth')->get_last_money($this->merchant['mer_id'], $auth_id, $num);
        $this->returnCode(0,$res);
    }


    //支付验证页
    public function auth_pay_check() {
        if ($this->config['buy_merchant_auth'] == 0) {
            $this->returnCodeError(L_('管理员未开启该功能'));
        }
        if (empty($_POST['auth_id']) && empty($_POST['menu_group'])) {
            $this->returnCodeError(L_('非法访问！'));
        }
        $now_merchant = D('Merchant')->get_info($this->merchant['mer_id']);

        if ($_POST['auth_id']) {
            $auth_id = $_POST['auth_id'];
            $now_auth = D('New_merchant_menu')->where(array('id' => $auth_id))->find();
        } else {
            $auth_id = $_POST['menu_group'];
            $now_auth = M('Authority_group')->where(array('id' => $auth_id))->find();
            $now_auth_mer = M('Authority_group')->where(array('id' => $now_merchant['authority_group_id']))->find();
            if ($now_merchant['authority_group_id'] && $now_merchant['merchant_end_time'] > time()) {
                $this->returnCodeError('您已经购买了权限了,还没有到期，不能再试用了');
            }
            if ($_POST['is_try'] == 1 && M('Authority_group_order')->where(array('label' => $now_auth['id'], 'mer_id' => $this->merchant['mer_id']))->find()
                || $_POST['is_try'] == 1 && $now_merchant['authority_group_id'] && $now_merchant['is_try'] == 0 && $now_merchant['merchant_end_time'] > time()) {
                $this->returnCodeError(L_('您已经购买了权限了，不能再试用了'));
            }
        }

        $res = D('Merchant_auth')->get_last_money($this->merchant['mer_id'], $auth_id);

        $date['mer_id'] = $this->merchant['mer_id'];
        $date['add_time'] = time();
        $date['last_time'] = time();
        $date['money'] = $_POST['change_money'] >= 0 ? $res : $now_auth['price'];
        $date['label'] = $now_auth['id'];
        $date['is_try'] = intval($_POST['is_try']);
        $date['num'] = 1;
        $date['is_change'] = $_POST['change_money'] == -1 ? 0 : 1;
        $date['paid'] = $_POST['is_try'] == 1 ? 1 : 0;

        $order_id = M('Authority_group_order')->add($date);

        if ($_POST['is_try'] == 1) {
            $res = D('Merchant_auth')->add_auth($this->merchant['mer_id'], $auth_id, 0);
            if ($res['error_code']) {
                $this->returnCodeError(L_($res['msg']));
            } else {
                $this->returnCodeOK(L_('权限购买成功'));
            }
        }


//        $merchant = M('Merchant')->where(array('mer_id' => $this->merchant['mer_id']))->find();
//        $auth_info = M('Authority_group')->where(array('id' => $auth_id))->find();
        $now_auth['time_type_txt'] = $this->time_type[$now_auth['time_type']];

//        if ($marketOrder = $databaseMarketOrder->field(true)->where(array('order_id' => $order_id, 'mer_id' => $this->merchant['mer_id'], 'status' => 10))->find()) {

        // 配置项信息
        $config = array(
            'Currency_txt' => $this->config['Currency_txt'],
            'Currency_symbol' => $this->config['Currency_symbol'],
        );
        $arr['config'] = $config;
        $pay_method = D('Market_order')->pay_method();
        unset($pay_method['offline']);
        if(strpos($_SERVER['HTTP_USER_AGENT'],'MicroMessenger') !== false){
            $pay_method_all = D('Config')->get_pay_method(0,0);
            if ($pay_method_all['weixin']) {
                $pay_method['weixin'] = $pay_method_all['weixin'];
            }
            unset($pay_method['weixinh5']);
        }
        $arr['order_id'] = $order_id;
        $arr['auth_info'] = $now_auth;
        $arr['merchant'] = $now_merchant;
        $arr['pay_method'] = $pay_method;
        $arr['openid'] = $_SESSION['openid'] ? $_SESSION['openid'] : '';
        $this->returnCode(0,$arr);
    }




    // 支付
    public function auth_pay_success()
    {
        $order_id = intval($_POST['order_id']);
        $total_price = trim($_POST['total_price']);
//        $_POST['order_type'] = !empty($_POST['order_type'])?$_POST['order_type']:'auth';
        $num = !empty($_POST['num'])?intval($_POST['num']):1; //购买个数

        if(empty($order_id) || empty($total_price)){
            $this->returnCodeError(L_('支付参数无效，请重试！'));
        }

        $AuthorityGroupOrder = D('Authority_group_order');
        $merchant = M('Merchant')->where(array('mer_id' => $this->merchant['mer_id']))->find();
        if(empty($merchant)){
            $this->returnCode(L_('未获取到您的帐号信息，请重试！'));
        }
//        if($this->config['nmgzhcs_appid']){
//            $merchant['money'] = 0;			// 不使用余额
//        }

        $pay_type = $_POST['pay_type'] ? $_POST['pay_type'] : '';
        $authOrder = $AuthorityGroupOrder->field(true)->where(array('order_id' => $order_id, 'mer_id' => $this->merchant['mer_id']))->find();

        if ($authOrder) {
            if ($pay_type == '' && $total_price > $merchant['money']) {
                $money = floatval($total_price - $merchant['money']);
                if (empty($money)||$money <0 ||!is_numeric($money)) {
                    $this->returnCodeError(L_('订单信息错误'));
                } else {
                    $data = [
                        'error' => '10001',
                        'msg' => '余额不足'
                    ];
                    $this->returnCode(0, $data);
//                    $this->returnCode(1,'',L_('您的余额不足，请前往商家后台最少充值').$money.$this->config['Currency_txt']);
                }
            }

            $now_order['order_info'] = D('Merchant_auth')->get_pay_order($this->merchant['mer_id'],intval($order_id),$num);
            if($now_order['order_info']['last_money']>0){
                $now_order['order_info']['money'] = $now_order['order_info']['last_money'];
            }

            $order_info = $now_order['order_info'];

            //自己减少余额
            $merchant = M('Merchant')->where(array('mer_id' => $this->merchant['mer_id']))->find();

            if ($pay_type == '' && $merchant['money'] >= $total_price) {

//                $returnData = D('Merchant_money_list')->use_money($this->merchant['mer_id'], $total_price,'merauth',$desc,$marketOrder['order_id']);
                D('Merchant_money_list')->use_money($this->merchant['mer_id'], $total_price, 'merauth', L_('购买商家权限【X1】扣除商家余额', $order_info['order_name']), $order_info['auth_id']);
            } elseif ($pay_type == '') {
                $money = floatval($total_price - $merchant['money']);
//                $this->returnCode(1,'',L_('您的余额不足，请前往商家后台最少充值').$money.$this->config['Currency_txt']);
                $data = [
                    'error' => '10001',
                    'msg' => '余额不足'
                ];
                $this->returnCode(0, $data);
            } elseif ($pay_type == 'weixinh5') {
                $nowtime = date("ymdHis");
                $orderid = $nowtime.rand(10,99).sprintf("%06d",$this->merchant['mer_id']);
                $data_tmp['pay_type'] = $pay_type;
                $data_tmp['order_type'] = 'merauth';
                $data_tmp['order_id'] = $order_id;
                $data_tmp['orderid'] = $orderid;
                $data_tmp['addtime'] = $nowtime;
                if(!D('Tmp_orderid')->add($data_tmp)){
                    $this->returnCode(1001,array(),L_('更新失败，请联系管理员'));
                }
                // 微信h5支付
//                $pay_method = D('Market_order')->pay_method(true);
                $pay_method = D('Config')->get_pay_method(0,0);

                if(empty($pay_method)){
                    $this->returnCodeError(L_('系统管理员没开启任一一种支付方式！'));
                }
                if(empty($pay_method[$_POST['pay_type']])){
                    $this->returnCodeError(L_('您选择的支付方式不存在，请更新支付方式！'));
                }

                //支付前保存在线支付金额
                $_service = 0;//支付手续费
//                if($pay_method[$_POST['pay_type']]['config']['service_charge_open'] == '1'){
//                    $service_charge = $pay_method[$_POST['pay_type']]['config']['service_charge'];
//                    $_service = $_total*($service_charge/100);
//                }
//                $_total = floor(($_total+$_service)*100)/100;//保留两位小数，但不四舍五入
                $_total = floor(($total_price)*100)/100;//保留两位小数，但不四舍五入
                $payment_money_date['payment_money'] = $_total;
                $payment_money_date['pay_type'] =  $pay_type;
                $payment_money_date['order_type'] = 'merauth';
                $payment_money_date['order_id'] =  $order_id;
                $payment_money_date['orderid'] =  $orderid;
                $payment_money_date['addtime'] = time();

                $payment_money_date['is_mobile_pay'] = $this->is_app ? 2 : ($this->is_wxapp ? 3 : 1);
                $payment_money_date['service_charge'] = $_service;//支付手续费

                M('Payment_money_order')->add($payment_money_date);

                $pay_class_name = ucfirst($pay_type);
                $import_result = import('@.ORG.pay.'.$pay_class_name);
                if(empty($import_result)){
                    $this->returnCode(1001,array(),L_('系统管理员暂未开启该支付方式，请更换其他的支付方式'));
                }

//                if($_POST['order_type']=='merrecharge'){
//                    $order_table = 'Merchant_recharge_order';
//                }else  if($_POST['order_type']=='auth'){
//                    $order_table = "Authority_group_order";
//                }else{
//                    $order_table = ucfirst($_POST['order_type']).'_order';
//                }

                $order_table = "Authority_group_order";

                $save_pay_id = D($order_table)->where(array("order_id"=>$order_id))->setField('orderid',$orderid);
                if(!$save_pay_id){
                    $this->returnCodeError(L_('更新失败，请联系管理员'));
                }else{
                    $order_info['order_id']=$orderid;
                }

                if($pay_method[$_POST['pay_type']]['config']['rates'] > 0){
                    $pay_money = getFormatNumber($_total*$pay_method[$_POST['pay_type']]['config']['rates']);
                }

                if(empty($now_order)){
                    $this->returnCode(1001,array(),L_('当前订单不存在'));
                }
                $order_info = $now_order['order_info'];
                $order_info['order_id'] = $orderid;
                $pay_class = new $pay_class_name($order_info,$_total,$pay_type,$pay_method[$pay_type]['config'],$this->merchant,2);
                $go_pay_param = $pay_class->pay();

                if($go_pay_param['result_code'] == 'SUCCESS'){//如果这两个都为此状态则返回mweb_url，详情看‘统一下单’接口文档
                    $mweb_url = $go_pay_param['mweb_url'];
                    $this->returnCode(0, array('error' => true, 'msg' =>L_('支付跳转'),'jump' => 'mweb_url','url' => $mweb_url, 'total' => $_total, 'name' => $authOrder['name']));
                } elseif($go_pay_param['result_code'] == 'FAIL'){
                    $err_code_des = $go_pay_param['err_code_des'];
                    $this->returnCode(1001,array(),$err_code_des);
                } elseif ($go_pay_param['error']) {
                    $this->returnCode(1001,array(),$go_pay_param['msg']);
                }elseif($go_pay_param['error'] == 0){
                    $mweb_url = $go_pay_param['url'];
                    $this->returnCode(0, array('error' => true, 'msg' =>L_('支付跳转'),'jump' => 'mweb_url','url' => $mweb_url, 'total' => $_total, 'name' => '权限套餐购买'));
                }

            } else {
                $this->returnCodeError(L_('您选择的支付方式不存在，请更新支付方式！'));
            }

            $res = D('Merchant_auth')->add_auth($this->merchant['mer_id'], $order_info['auth_id'], $num);

            //修改订单状态
            $AuthorityGroupOrder->where(array('order_id' => $order_id))->save(array('pay_time' => time(), 'paid' => 1));


            $this->returnCode(0,'',L_('支付成功！'));
        } else {
            $this->returnCode(1,'',L_('订单信息错误！'));
        }
    }




    /**
     * 商家余额充值
     */
    public function mer_recharge()
    {
        //商家信息
        $merchant = D('Merchant')->get_info($this->merchant['mer_id']);

        if (!isset($_POST['money'])) {

            // 配置项信息
            $config = array(
                'Currency_txt' => $this->config['Currency_txt'],
                'Currency_symbol' => $this->config['Currency_symbol'],
            );
            $arr['config'] = $config;
            $pay_method = D('Market_order')->pay_method();
            unset($pay_method['offline']);
            if(strpos($_SERVER['HTTP_USER_AGENT'],'MicroMessenger') !== false){
                $pay_method_all = D('Config')->get_pay_method(0,0);
                if ($pay_method_all['weixin']) {
                    $pay_method['weixin'] = $pay_method_all['weixin'];
                }
                unset($pay_method['weixinh5']);
            }

            $arr['merchant'] = $merchant;
            $arr['pay_method'] = $pay_method;
            $arr['openid'] = $_SESSION['openid'] ? $_SESSION['openid'] : '';
            $this->returnCode(0,$arr);
        } else {
            $money = floatval($_POST['money']);
            $pay_type = $_POST['pay_type'];
            if (empty($money) || $money < 0 || !is_numeric($money)) {
                $this->returnCodeError(L_('请输入正确的充值金额'));
            }

            $merchant = M('Merchant')->where(array('mer_id' => $this->merchant['mer_id']))->find();
            if(empty($merchant)){
                $this->returnCodeError(L_('未获取到您的帐号信息，请重试！'));
            }

            $data_mer_recharge_order['mer_id'] = $this->merchant['mer_id'];
            $data_mer_recharge_order['money'] = $money;
            $data_mer_recharge_order['add_time'] = $_SERVER['REQUEST_TIME'];
            $data_mer_recharge_order['last_time'] = $_SERVER['REQUEST_TIME'];
            if ($_POST['auth_id']) {
                // if ($_POST['type']) {
                $data_mer_recharge_order['label'] = 'wap_merauthgroup_' . $_POST['auth_id'];
                // }
            }
            if ($order_id = M('Merchant_recharge_order')->data($data_mer_recharge_order)->add()) {
                $nowtime = date("ymdHis");
                $orderid = $nowtime.rand(10,99).sprintf("%06d",$this->merchant['mer_id']);
                if ($pay_type == 'weixinh5') {
                    $data_tmp['pay_type'] = $pay_type;
                    $data_tmp['order_type'] = 'merrecharge';
                    $data_tmp['order_id'] = $order_id;
                    $data_tmp['orderid'] = $orderid;
                    $data_tmp['addtime'] = $nowtime;
                    if(!D('Tmp_orderid')->add($data_tmp)){
                        $this->returnCode(1001,array(),L_('更新失败，请联系管理员'));
                    }
                }

                //                $pay_method = D('Market_order')->pay_method(true);
                $pay_method = D('Config')->get_pay_method(0,0);

                if(empty($pay_method)){
                    $this->returnCodeError(L_('系统管理员没开启任一一种支付方式！'));
                }
                if(empty($pay_method[$_POST['pay_type']])){
                    $this->returnCodeError(L_('您选择的支付方式不存在，请更新支付方式！'));
                }

                //支付前保存在线支付金额
                $_service = 0;//支付手续费
//                if($pay_method[$_POST['pay_type']]['config']['service_charge_open'] == '1'){
//                    $service_charge = $pay_method[$_POST['pay_type']]['config']['service_charge'];
//                    $_service = $_total*($service_charge/100);
//                }
//                $_total = floor(($_total+$_service)*100)/100;//保留两位小数，但不四舍五入
                $_total = floor(($money)*100)/100;//保留两位小数，但不四舍五入
                $payment_money_date['payment_money'] = $_total;
                $payment_money_date['pay_type'] =  $pay_type;
                $payment_money_date['order_type'] = 'merrecharge';
                $payment_money_date['order_id'] =  $order_id;
                $payment_money_date['orderid'] =  $orderid;
                $payment_money_date['addtime'] = time();

                $payment_money_date['is_mobile_pay'] = $this->is_app ? 2 : ($this->is_wxapp ? 3 : 1);
                $payment_money_date['service_charge'] = $_service;//支付手续费

                M('Payment_money_order')->add($payment_money_date);

                $pay_class_name = ucfirst($pay_type);
                $import_result = import('@.ORG.pay.'.$pay_class_name);
                if(empty($import_result)){
                    $this->returnCode(1001,array(),L_('系统管理员暂未开启该支付方式，请更换其他的支付方式'));
                }

                // if($_POST['order_type']=='merrecharge'){
                //     $order_table = 'Merchant_recharge_order';
                // }else  if($_POST['order_type']=='auth'){
                //     $order_table = "Authority_group_order";
                // }else{
                //     $order_table = ucfirst($_POST['order_type']).'_order';
                // }

                $save_pay_id = D('Merchant_recharge_order')->where(array("order_id"=>$order_id))->setField('orderid',$orderid);
                if(!$save_pay_id){
                    $this->returnCodeError(L_('更新失败，请联系管理员'));
                }else{
                    $order_info['order_id']=$orderid;
                }

                if($pay_method[$_POST['pay_type']]['config']['rates'] > 0){
                    $pay_money = getFormatNumber($_total*$pay_method[$_POST['pay_type']]['config']['rates']);
                }
                $orderIdSelect = $_POST['order_id']??$order_id;
                $now_order = D('Merchant_recharge_order')->get_pay_order($this->merchant['mer_id'],intval($orderIdSelect),true);

                if(empty($now_order)){
                    $this->returnCode(1001,array(),L_('当前订单不存在'));
                }
                $order_info = $now_order['order_info'];
                $order_info['order_id'] = $orderid;
                if(isset($_POST['openid']) && $_POST['openid']){
                    $_SESSION['openid'] = $_POST['openid'];
                }
                $order_info['order_type'] = 'merrecharge';
                $isMobile = (isset($_POST['Device-Id']) && $_POST['Device-Id']=='packapp') ? 1 : 2;
                $pay_class = new $pay_class_name($order_info,$_total,$pay_type,$pay_method[$pay_type]['config'],$this->merchant,$isMobile);
                $go_pay_param = $pay_class->pay();

                if($go_pay_param['url']){ //如果这两个都为此状态则返回mweb_url，详情看‘统一下单’接口文档
                    $mweb_url = $go_pay_param['url'];
                    $this->returnCode(0, array('error' => true, 'msg' =>L_('支付跳转'),'jump' => 'mweb_url','url' => $mweb_url, 'total' => $_total, 'name' => ''));
                } elseif($go_pay_param['result_code'] == 'FAIL'){
                    $err_code_des = $go_pay_param['err_code_des'];
                    $this->returnCode(1001,array(),$err_code_des);
                } elseif ($go_pay_param['error']) {
                    $this->returnCode(1001,array(),$go_pay_param['msg']);
                } elseif (isset($_POST['Device-Id']) && $_POST['Device-Id']=='packapp') {
                    $go_pay_param['weixin_param'] = json_decode($go_pay_param['weixin_param'],true);
                    $this->returnCode(0, $go_pay_param);
                }

                //更新充值记录表
                M('Merchant_recharge_order')->where(['order_id'=>$order_id, 'orderid'=>$orderid])->save(['paid'=>1]);

                //更新商户余额
                $new_money = $merchant['money'] + $money;
                M('Merchant')->where(['mer_id'=>$this->merchant['mer_id']])->save(['money'=>$new_money]);

                // redirect(U('Pay/check', array('order_id' => $order_id, 'type' => 'merrecharge')));
                $this->returnCode(0,'',L_('支付成功！'));
            } else {
                $this->returnCodeError(L_('订单创建失败，请重试。'));
            }
        }

    }


    //充值记录
    public function mer_recharge_record() { 

        $condition['mer_id'] = $this->merchant['mer_id'];
        $condition['paid'] = 1;
        $count = M('Merchant_recharge_order')->where($condition)->count();
        $page = I('pindex',1);

        $recharge_list = M('Merchant_recharge_order')->where($condition)->order('order_id DESC')->page($page,10)->select();
        foreach ($recharge_list as &$item) {
            $item['pay_time'] = date('Y-m-d H:i:s', $item['pay_time']);
        }

        $arr['recharge_list'] = isset($recharge_list)?$recharge_list:[];
        $arr['all']		=	$count;
        $arr['page'] 	=	ceil($arr['all']/10);
        $arr['Currency_txt'] = $this->config['Currency_txt'] ? $this->config['Currency_txt'] : '元';
        $this->returnCode(0,$arr);

    }

    /**
     * 获取我的积分日志
     * @date: 2021/09/28
     */
    public function score_logs()
    {
        $page = I('post.page', 1, 'intval');
        $pageSize = I('post.page_size', 20, 'intval');
        $period = I('period', 1, 'trim');
        $merId = $this->merchant['mer_id'];
        list($startDate, $endDate) = explode('-', $period);


        if (empty($merId)) {
            $this->returnCodeError(L_('商家未登录'));
        }

        $rs = ['score' => round($this->merchant['merchant_score'],4), 'lists' => []];
        $where = ['mer_id' => $merId];
        if ($startDate && $endDate) {
            $startTime = strtotime($startDate);
            $endTime = strtotime($endDate) + 86400;
            $where['add_time'] = ['between', [$startTime, $endTime]];
        }
        $aliasName = D("Merchant_money_list")->get_alias_c_name();
        $lists = D('Merchant_get_score_order_log')->where($where)->limit(($page - 1) * $pageSize, $pageSize)->order('add_time DESC')->select();
        $rs['lists'] = array_map(function ($r) use ($aliasName) {
            $r['order_type_name'] = $aliasName[$r['order_type']] ?? '--';
            $r['add_date'] = date('Y-m-d H:i:s', $r['add_time']);
            return $r;
        }, $lists);
        $this->returnCode(0, $rs);
    }
    
    /**
     * 获取配置
     */
    public function getConfigAjax()
    {
        $merchant_settle_in = C('customization.merchant_settle_in');
        $this->returnCode(0, $merchant_settle_in);
    }

    /**
     * 获取数据
     */
    public function getDataAjax()
    {
        $mer_id = $this->merchant['mer_id'];
        $merchant_info = D('Merchant')->get_info($mer_id); 
        $this->returnCode(0, $merchant_info); 
    }



     /**
     * 商家余额充值
     */
    public function settleInRecharge()
    {
        $merchant_settle_in = C('customization.merchant_settle_in');

        if(!$merchant_settle_in){
            $this->returnCodeError(L_('无访问权限！'));
        }

        //商家信息
        $merchant = D('Merchant')->get_info($this->merchant['mer_id']);

        if (!isset($_POST['money'])) {

            // 配置项信息
            $config = array(
                'Currency_txt' => $this->config['Currency_txt'],
                'Currency_symbol' => $this->config['Currency_symbol'],
            );
            $arr['config'] = $config;
            $pay_method = D('Market_order')->pay_method();
            unset($pay_method['offline']);
            if(strpos($_SERVER['HTTP_USER_AGENT'],'MicroMessenger') !== false){
                $pay_method_all = D('Config')->get_pay_method(0,0);
                if ($pay_method_all['weixin']) {
                    $pay_method['weixin'] = $pay_method_all['weixin'];
                }
                unset($pay_method['weixinh5']);
            }

            $arr['merchant'] = $merchant;
            $arr['pay_method'] = $pay_method;
            $arr['openid'] = $_SESSION['openid'] ? $_SESSION['openid'] : '';
            $this->returnCode(0,$arr);
        } else {
            $money = floatval($_POST['money']);
            $pay_type = $_POST['pay_type'];
            if (empty($money) || $money < 0 || !is_numeric($money)) {
                $this->returnCodeError(L_('请输入正确的充值金额'));
            }

            $merchant = M('Merchant')->where(array('mer_id' => $this->merchant['mer_id']))->find();
            if(empty($merchant)){
                $this->returnCodeError(L_('未获取到您的帐号信息，请重试！'));
            }

            // $data_mer_recharge_order['mer_id'] = $this->merchant['mer_id'];
            // $data_mer_recharge_order['money'] = $money;
            // $data_mer_recharge_order['add_time'] = $_SERVER['REQUEST_TIME'];
            // $data_mer_recharge_order['last_time'] = $_SERVER['REQUEST_TIME'];
            // if ($_POST['auth_id']) {
            //     // if ($_POST['type']) {
            //     $data_mer_recharge_order['label'] = 'wap_merauthgroup_' . $_POST['auth_id'];
            //     // }
            // }
            $tm = time();
            $saveData = [];
            $saveData['mer_id'] = $this->merchant['mer_id'];
            $saveData['money'] = $money;
            $saveData['remark'] = $_POST['remark'];
            $saveData['type'] = 1;
            $saveData['status'] = 0;
            $saveData['pay_type'] = $pay_type;
            $saveData['submit_time'] = $tm;
            $saveData['create_time'] = $tm; 

            if($order_id = M('Merchant_settle_in_pay')->data($saveData)->add()){
 
                $nowtime = date("ymdHis");
                $orderid = $nowtime.rand(10,99).sprintf("%06d",$this->merchant['mer_id']);

                $pay_method = D('Config')->get_pay_method(0,0);

                if(empty($pay_method)){
                    $this->returnCodeError(L_('系统管理员没开启任一一种支付方式！'));
                }
                if(empty($pay_method[$_POST['pay_type']])){
                    $this->returnCodeError(L_('您选择的支付方式不存在，请更新支付方式！'));
                }
                $_service = 0;

                $_total = floor(($money)*100)/100;//保留两位小数，但不四舍五入
                $payment_money_date['payment_money'] = $_total;
                $payment_money_date['pay_type'] =  $pay_type;
                $payment_money_date['order_type'] = 'settlein';
                $payment_money_date['order_id'] =  $order_id;
                $payment_money_date['orderid'] =  $orderid;
                $payment_money_date['addtime'] = time();

                $payment_money_date['is_mobile_pay'] = $this->is_app ? 2 : ($this->is_wxapp ? 3 : 1);
                $payment_money_date['service_charge'] = $_service;//支付手续费

                M('Payment_money_order')->add($payment_money_date);

                $pay_class_name = ucfirst($pay_type);
                $import_result = import('@.ORG.pay.'.$pay_class_name);
                if(empty($import_result)){
                    $this->returnCode(1001,array(),L_('系统管理员暂未开启该支付方式，请更换其他的支付方式'));
                }

 

                $save_pay_id = D('Merchant_settle_in_pay')->where(array("order_id"=>$order_id))->setField('orderid',$orderid);
                if(!$save_pay_id){
                    $this->returnCodeError(L_('更新失败，请联系管理员'));
                }else{
                    $order_info['order_id']=$orderid;
                }

                if($pay_method[$_POST['pay_type']]['config']['rates'] > 0){
                    $pay_money = getFormatNumber($_total*$pay_method[$_POST['pay_type']]['config']['rates']);
                }

                $now_order = D('Merchant_settle_in_pay')->get_pay_order($this->merchant['mer_id'],$_POST['order_id']?:$order_id,true);

                if(empty($now_order)){
                    $this->returnCode(1001,array(),L_('当前订单不存在'));
                }
                $order_info = $now_order['order_info'];
                $order_info['order_id'] = $orderid;
                $order_info['order_type'] = 'settlein';
                $pay_class = new $pay_class_name($order_info,$_total,$pay_type,$pay_method[$pay_type]['config'],$this->merchant,2);
                $go_pay_param = $pay_class->pay();

                if($go_pay_param['url']){ //如果这两个都为此状态则返回mweb_url，详情看‘统一下单’接口文档
                    $mweb_url = $go_pay_param['url'];
                    $this->returnCode(0, array('error' => true, 'msg' =>L_('支付跳转'),'jump' => 'mweb_url','url' => $mweb_url, 'total' => $_total, 'name' => ''));
                } elseif($go_pay_param['result_code'] == 'FAIL'){
                    $err_code_des = $go_pay_param['err_code_des'];
                    $this->returnCode(1001,array(),$err_code_des);
                } elseif ($go_pay_param['error']) {
                    $this->returnCode(1001,array(),$go_pay_param['msg']);
                }

                //更新充值记录表
                M('merchant_settle_in_pay')->where(['order_id'=>$order_id, 'order_id'=>$orderid])->save(['paid'=>1,'pay_time'=>time()]);

                //更新商户余额
                $new_money = $merchant['settle_in_money'] + $money;
                M('Merchant')->where(['mer_id'=>$this->merchant['mer_id']])->save(['settle_in_money'=>$new_money]);

                // redirect(U('Pay/check', array('order_id' => $order_id, 'type' => 'merrecharge')));
                $this->returnCode(0,'',L_('支付成功！'));


            }else {
                $this->returnCodeError(L_('订单创建失败，请重试。'));
            } 
        }
    }


    /**
     * 锁定积分
     * @date: 2021/09/28
     */
    public function lock_score_logs()
    {
        $page = I('post.page', 1, 'intval');
        $pageSize = I('post.page_size', 20, 'intval');
        $period = I('period', 1, 'trim');
        $merId = $this->merchant['mer_id'];
        list($startDate, $endDate) = explode('-', $period);

        if (empty($merId)) {
            $this->returnCodeError(L_('商家未登录'));
        }

        $lockScore = D('Merchant_get_score')->field('sum(score_count) as score_count,sum(score_get) as score_get')->where(['mer_id' => $merId])->find();
        $rs = ['score' => round($lockScore['score_count'] - $lockScore['score_get'],4), 'lists' => []];
        $where = ['mer_id' => $merId];
        if ($startDate && $endDate) {
            $startTime = date('Ymd', strtotime($startDate));
            $endTime = date('Ymd', strtotime($endDate));
            $where['get_date'] = ['between', [$startTime, $endTime]];
        }
        $rs['lists'] = D('Merchant_get_score')->field('*,180 as lock_days')->where($where)->limit(($page - 1) * $pageSize, $pageSize)->order('get_date DESC')->select();
        $this->returnCode(0, $rs);
    }

    /**
     * 退款
     */
    public function settleinRefund()
    { 
        $money = floatval($_POST['money']);
        $remark = $_POST['remark'];
        $mer_id = $this->merchant['mer_id'];
        $merchant_info = D('Merchant')->get_info($mer_id);
        if(empty($mer_id)){
            $this->returnCodeError(L_('请先登录！'));
        }
        if($merchant_info['settle_in_money'] < $money){
            $this->returnCodeError(L_('可退款金额不足！'));
        }

        $nowtime = date("ymdHis");
        $orderid = $nowtime.rand(10,99).sprintf("%06d",$this->merchant['mer_id']);

        $data = [];
        $data['mer_id'] = $mer_id; 
        $data['orderid'] = $orderid; 
        $data['money'] = $money; 
        $data['type'] = 2; 
        $data['status'] = 20; 
        $data['remark'] = $remark; 
        $data['create_time'] = time();  
        if (M('Merchant_settle_in_pay')->data($data)->add()) { 
            $settle_in_money = D('Merchant_settle_in_pay')->calcMoney($mer_id);
            M('Merchant')->where(['mer_id'=>$mer_id])->save(['settle_in_money'=>$settle_in_money]);
        } else {
            $this->returnCodeError(L_('订单创建失败，请重试。'));
        }
        $this->returnCode(0, '退款申请提交成功！', U('SettleIn/index'));
        die;
    }

    /**
     * 入驻缴费记录
     */
    public function settleList()
    {
        $mer_id = $this->merchant['mer_id'];
        if(empty($mer_id)){
            $this->returnCodeError(L_('请先登录！'));
        }

        $condition = [];
        $condition['mer_id'] = $mer_id;  
        $condition['status'] = ['in', [1, 20, 21, 22]];   
        $list = M('Merchant_settle_in_pay')->where($condition)->order('create_time desc')->select();
 
        $merchant_info = D('Merchant')->get_info($mer_id);

        $countMoney = [
            'recharge' => 0,
            'refund' => 0,
            'audit' => 0,
            'money' => round($merchant_info['settle_in_money'], 2)
        ];

        //0=未支付,1=已支付,20=发起退款,21=退款成功,22=退款失败',
        $statusMap = [
            0   =>  '未支付',
            1   =>  '已支付',
            20   =>  '审核中',
            21   =>  '已退款',
            22   =>  '已拒绝',
        ];
 
 
        foreach($list as $key => &$val){
            if($val['type'] == 1){
                $countMoney['recharge'] += $val['money'];
            }
            
            if($val['type'] == 2 && $val['status'] == 21){
                $countMoney['refund'] += $val['money'];
            }
            
            if($val['type'] == 2 && $val['status'] == 20){
                $countMoney['audit'] += $val['money'];
            }
            if($val['type'] == 1){
                $val['type_text'] = '充值';
                $val['format_time'] = date('Y/m/d H:i:s', $val['pay_time']);
            }else{
                $val['type_text'] = '退款';
                $val['format_time'] = $val['refund_time'] ? date('Y/m/d H:i:s', $val['refund_time']) : date('Y/m/d H:i:s', $val['create_time']);
            }

            $val['status_text'] = $statusMap[$val['status']];
           
        }
        $countMoney['recharge'] = round($countMoney['recharge'], 2);
        $countMoney['refund'] = round($countMoney['refund'], 2);
        $countMoney['audit'] = round($countMoney['audit'], 2);
        $data['list'] = $list;
        $data['countMoney'] = $countMoney;
        $this->returnCode(0, $data);
    } 
    
    /**
     * 合同
     * @author: 张涛
     * @date: 2021/01/27
     */
    public function contract()
    {
        $rs = (new Merchant_contractModel())->getContractContentByMerid($this->merchant['mer_id']);
        $this->returnCode(0, $rs);
    }

    /**
     * 保存合同签署
     * @author: 张涛
     * @date: 2021/01/27
     */
    public function saveSign()
    {
        $mod = new Merchant_contractModel();
        $signNameBase64 = $_POST['sign_name_base64'] ?? '';
        $merId = $this->merchant['mer_id'];

        //查看是否已签署
        $contract_info = $mod->where(['mer_id' => $merId,'status'=>1])->find();
        //是否可以续约
        $canRenewed = D('Config_data')->where(['name' => 'contract_can_renewed'])->getField('value');
        //到期前多少天可以续签
        $fewDayNotice = D('Config_data')->where(['name' => 'contract_few_days_notice'])->getField('value');
        if($contract_info){
            if($canRenewed) {
                if(($contract_info['end_time']-time())>$fewDayNotice*24*3600){
                    $this->returnCodeError(L_('您已签署，请勿重复操作！'));
                }
            }elseif(!$canRenewed&&($contract_info['end_time']-time())<0){
                $this->returnCodeError(L_('合同到期后不能续签！'));
            }
        }

        $contentData = (new Merchant_contractModel())->getContractContentByMerid($merId);
        $data = [
            'mer_id' => $merId,
            'content_data' => serialize($contentData),
            'sign_name_base64' => $signNameBase64,
            'sign_time' => time(),
            'contract_number' => $contentData['contract_number'],
            'end_time' => $contentData['end_time'],
            'status' => 1
        ];
        D()->startTrans();
        try {
            //修改以前状态
            D('Merchant_contract')->where(['mer_id'=>$merId,'status'=>1])->data(['status'=>0])->save();
            D('Merchant_contract')->add($data);
            D('Merchant')->where(['mer_id' => $merId])->save(['is_sign_contract' => 1,'contract_end_time'=>$contentData['end_time']]);
            D()->commit();
            $this->returnCode(0,L_('签署成功！'));
        }catch (\Exception $e){
            D()->rollback();
            $this->returnCodeError(L_('签署失败'));
        }
    }

    public function regForm()
    {
        $fields = D('Merchant_register_fields')->order('sort DESC')->select();
        foreach ($fields as $k => $v) {
            if ($v['type'] == 3) {
                $fields[$k]['use_field'] = unserialize($v['use_field']);
            } else {
                $fields[$k]['use_field'] = [];
            }
            $fields[$k]['use_field_json'] = json_encode(array_map(function ($rs) {
                return ['value' => trim($rs), 'text' => trim($rs)];
            }, $fields[$k]['use_field']));
        }
        $this->returnCode(0, $fields);
    }

    /**
     * 判断商家是否已签署合同
     * @author: 张涛
     * @date: 2021/01/29
     */
    public function isSign()
    {
        $merId = $this->merchant['mer_id'];
        $contract = D('Merchant_contract')->where(['mer_id'=>$merId,'status'=>1])->find();
        $merchant = M('Merchant')->where(['mer_id' => $merId])->find();
        $is_sign = true;
        if($contract){
            //是否可以续约
            $canRenewed = D('Config_data')->where(['name' => 'contract_can_renewed'])->getField('value');
            if($canRenewed&&($merchant['contract_end_time']-time())<=0) {
                $is_sign = false;
            }elseif($canRenewed&&($merchant['contract_end_time']-time())>0){
                $fewDayNotice = D('Config_data')->where(['name' => 'contract_few_days_notice'])->getField('value');
                if(($merchant['contract_end_time']-time())<=$fewDayNotice*24*3600){
                    $is_sign = false;
                }
            }
        }
        $isSign = in_array($merchant['is_sign_contract'],[0,2])?false:$is_sign;
        $this->returnCode(0, ['is_sign' => $isSign]);
    }

    /**
     * 注销商家
     * @author Nd
     * @date 2022/4/19
     */
    public function cancel()
    {
        $merchant = M('Merchant')->where(['mer_id' => $this->merchant['mer_id']])->find();
        if(!$merchant){
            $this->returnCode(0, '商家注销成功');
        }
        $saveData = [
            'status' => 4,
            'account' => $merchant['account'].'~no_use',
            'name' => $merchant['name'].'~no_use',
            'phone' => $merchant['phone'].'~no_use',
            'email' => $merchant['email'].'~no_use',
        ];
        $cancel = D('Merchant')->where(['mer_id' => $this->merchant['mer_id']])->save($saveData);
        if($cancel){
            $this->returnCode(0, '商家注销成功');
        }
        $this->returnCodeError(L_('商家注销失败'));
    }

    // 商家余额银联申请提现
    public function applyChinaumsWithdraw()
    {
        $_POST['mer_id'] = $this->merchant['mer_id'];
        $_POST['info'] = $_POST['remark'];
        $res = D('Merchant_withdraw_chinaums')->applyWithdraw($_POST);
        if($res['code']!=0){
            $this->returnCode('1','',L_($res['msg']));
        }
        $this->returnCode(0, L_('申请成功'));
    }


    //获取钱账单入驻链接
    public function getQianZhangTongRegisterUrl()
    {
        $merId = $this->merchant['mer_id'];
        if (empty($merId)) {
            $this->returnCodeError(L_('商家信息错误'));
        }

        $merchant = M('Merchant')->where(['mer_id' => $merId])->find();
        if (empty($merchant) || $merchant['status'] != 1) {
            $this->returnCodeError(L_('商家不存在或者已删除'));
        }

        $itemType = 'merchant';
        $itemId = $merId;
        $outMemberNo = $itemType . $merId;
        $applyMod = M('Lakala_qianzhangtong_archives');
        $apply = $applyMod->where(['item_type' => $itemType, 'item_id' => $itemId])->find();
        if (empty($apply) || empty($apply['member_no'])) {

            //没有入网过
            if (empty($apply)) {
                $data = [
                    'item_type' => $itemType,
                    'item_id' => $itemId,
                    'out_member_no' => $outMemberNo,
                    'create_time' => time(),
                ];
                $applyMod->add($data);
            } else {
                $outMemberNo = $apply['out_member_no'];
            }
            try {
                import('@.ORG.pay.Lakala');
                $result = (new Lakala())->register($outMemberNo);
                $this->returnCode(0,   $result);
            } catch (\Throwable $th) {
                $this->returnCodeError($th->getMessage());
            }
        } else {
            //有入网过
            $result = [
                'url' => C('config.site_url') . '/wap.php?c=Qianzhangtong&a=register&id=' . $apply['id'] . '&token=' . md5($itemType . $itemId)
            ];
            $this->returnCode(0,   $result);
        }
    }
}
?>
