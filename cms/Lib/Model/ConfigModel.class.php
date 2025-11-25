<?php
class ConfigModel extends Model{
	public function get_config($now_lang = ''){
		$config = S('system_config/lang_'.$now_lang);
		if(empty($config)){
			$open_multilingual = array_column($this->field('name,value')->where('name in ("open_multilingual","default_language")')->select(), 'value', 'name');
			$_field = '`name`,`value`';
			$lang_model = D('Lang');
			if($open_multilingual['open_multilingual'] == '1'){
				$_field .= ',`is_lang`';
				if(empty($now_lang)){
					$now_lang = $open_multilingual['default_language'];
				}
				$_field .= ($now_lang && $now_lang != 'chinese') ? ',`value_'.$now_lang.'`' : '';

				$configs = $this->field($_field)->select();
				foreach($configs as $key=>$value){
					if($open_multilingual['open_multilingual'] == '1' && $value['is_lang'] == '1' && $now_lang && $now_lang != 'chinese'){//系统开启了多语言
						$config[$value['name']] = $value['value_'.$now_lang];
					}
					else{
						$config[$value['name']] = $value['value'];
					}
				}
				unset($configs);
			}else{
				$config = $this->getField('name,value');
			}
			
			
			$domain_array = parse_url($config['site_url']);
			$config['top_domain'] = $this->get_domain($domain_array['host']);
			if(stripos($config['site_url'],'https://') === 0){
				$config['use_https'] = true;
			}else{
				$config['use_https'] = false;
			}


			//图片、APK链接强制更改为 资源域名 OSS
			if($config['static_oss_switch'] && $config['static_oss_access_domain_names']){
				$replace_extension = array('jpg','png','gif','apk','jpeg','ico');
				foreach($config as $key=>$value){
					//图片、APK链接强制更改为 资源域名
					if(stripos($value,'/upload/') !== false){
						$pathinfoArr = pathinfo($value);
						if(in_array(strtolower($pathinfoArr['extension']),$replace_extension)){
							if(stripos($value, '/upload/') === 0){
								$config[$key] = $_SERVER['REQUEST_SCHEME'].'://'.$config['static_oss_access_domain_names'] . $value;
							}else if(stripos($value, 'upload/') === 0){
								$config[$key] = $_SERVER['REQUEST_SCHEME'].'://'.$config['static_oss_access_domain_names']  . '/' . $value;
							}else{
								$config[$key] = str_replace($config['site_url'], $_SERVER['REQUEST_SCHEME'].'://'.$config['static_oss_access_domain_names'], $value);
							}
						}
					}
				}
			}else if($config['static_cos_switch'] && $config['static_cos_region']){	//图片、APK链接强制更改为 资源域名 OBS
				$replace_extension = array('jpg','png','gif','apk','jpeg','ico');
				foreach($config as $key=>$value){
					//图片、APK链接强制更改为 资源域名
					if(stripos($value,'/upload/') !== false){
						$pathinfoArr = pathinfo($value);
						if(in_array(strtolower($pathinfoArr['extension']),$replace_extension)){
							if(stripos($value, '/upload/') === 0){
								$config[$key] = $_SERVER['REQUEST_SCHEME'].'://' . $config['static_cos_access_domain_names'] . $value;
							}else if(stripos($value, 'upload/') === 0){
								$config[$key] = $_SERVER['REQUEST_SCHEME'].'://' . $config['static_cos_access_domain_names']  . '/' . $value;
							}else{
								$config[$key] = str_replace($config['site_url'], $_SERVER['REQUEST_SCHEME'].'://' . $config['static_cos_access_domain_names'], $value);
							}
						}
					}
				}
			}else if($config['static_obs_switch'] && $config['static_obs_access_domain_names']){	//图片、APK链接强制更改为 资源域名 OBS
				$replace_extension = array('jpg','png','gif','apk','jpeg','ico');
				foreach($config as $key=>$value){
					//图片、APK链接强制更改为 资源域名
					if(stripos($value,'/upload/') !== false){
						$pathinfoArr = pathinfo($value);
						if(in_array(strtolower($pathinfoArr['extension']),$replace_extension)){
							if(stripos($value, '/upload/') === 0){
								$config[$key] = $_SERVER['REQUEST_SCHEME'].'://'.$config['static_obs_access_domain_names'] . $value;
							}else if(stripos($value, 'upload/') === 0){
								$config[$key] = $_SERVER['REQUEST_SCHEME'].'://'.$config['static_obs_access_domain_names']  . '/' . $value;
							}else{
								$config[$key] = str_replace($config['site_url'], $_SERVER['REQUEST_SCHEME'].'://'.$config['static_obs_access_domain_names'], $value);
							}
						}
					}
				}
			}else{	//本地的
				$replace_extension = array('jpg','png','gif','apk','jpeg','ico');
				foreach($config as $key=>$value){
					//图片、APK链接强制更改为 资源域名
					if(stripos($value,'/upload/') !== false){
						$pathinfoArr = pathinfo($value);
						if(in_array(strtolower($pathinfoArr['extension']),$replace_extension)){
							if(stripos($value, '/upload/') === 0){
								$config[$key] = $config['site_url'] . $value;
							}else if(stripos($value, 'upload/') === 0){
								$config[$key] = $config['site_url']  . '/' . $value;
							}
						}
					}
				}
			}

			$config['group_alias_name'] === '' && $config['group_alias_name']=L_('团购');
			$config['meal_alias_name'] === '' && $config['meal_alias_name']=L_('快店');
			$config['wxapp_alias_name'] === '' && $config['wxapp_alias_name']=L_('营销');
			$config['store_alias_name'] === '' && $config['store_alias_name']=L_('收银');
			$config['weidian_alias_name'] === '' && $config['weidian_alias_name']=L_('微店');
			$config['weidian_url'] === '' && $config['weidian_url']='http://v.bupu.net';


			if(!$config['baidu_map_ak']){
				$config['baidu_map_ak'] = '4c1bb2055e24296bbaef36574877b4e2';
			}
			if(!$config['im_url']){
				$config['im_url'] = 'https://im-link.weihubao.com';
			}
			if($config['mobile_head_color']){
				if(stripos($config['mobile_head_color'],'rgb') !== false){
					$config['mobile_head_color'] = $this->RGBToHex($config['mobile_head_color']);
				}else if(stripos($config['mobile_head_color'],'#') !== 0){
					$config['mobile_head_color'] = '#'.$config['mobile_head_color'];
				}
				if(strlen($config['mobile_head_color']) != 7){
					$config['mobile_head_color'] = strtoupper('#06c1ae');
				}
			}else{
				$config['mobile_head_color'] = strtoupper('#06c1ae');
			}
			$config['mobile_head_color'] = strtoupper($config['mobile_head_color']);

			$config['upload_site_url'] = $config['site_url'];

			$config['group_page_row'] 	= 21;
			$config['group_page_val'] 	= 'page';
			$config['group_pic_width'] 	= '900,502';
			$config['group_pic_height'] = '500,279';
			$config['group_around_range'] = '5000';
			$config['reply_pic_size'] = '10';
			$config['reply_pic_width'] = '375,95';
			$config['reply_pic_height'] = '500,126';
			$config['gift_pic_size'] = '10';
			$config['group_pic_size'] = '10';
			$config['meal_pic_size'] = '10';
			$config['meal_pic_width'] = '900,641';
			$config['meal_pic_height'] = '500,356';
			$config['meal_page_row'] = '20';
			$config['meal_page_val'] = 'page';


			$config['system_lang'] = $now_lang;
			$config['system_lang_alias'] = $config['system_lang_alias'] ? $config['system_lang_alias'] : 'zh_CN';

			$config['Currency_txt'] = $config['Currency_txt'] ? $config['Currency_txt'] : '元';
			$config['Currency_symbol'] = $config['Currency_symbol'] ? $config['Currency_symbol'] : '￥';

			S('system_config/lang_'.$now_lang,$config,120);
		}

		$config['config_site_url'] = $config['site_url'];
		$config['now_site_url'] = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'];

		if($_SERVER['HTTP_HOST'] == 'www.group.com'){
			$config['local_dev'] = true;
		}

		/* if($config['map_config']=='google'){
            $IP = get_client_ip(0);
			if($IP == '60.173.195.178'){
				$config['map_config'] = 'baidu';
			}
        } */


		return $config;
	}


	public function get_gid_config($gid){
		$condition_config['gid'] = $gid;
		$config = $this->field(true)->where($condition_config)->order('`sort` DESC')->select();

		return $config;
	}

	/**
	 * @desc 根据name获取单个配置信息
	 * @param null $name
	 * @return array|mixed
	 */
	public function get_config_by_name($name=null){
		if(!$name) return array();
		$where = array();
		$where['status'] = 1;
		$where['name'] = $name;
		return $this->where($where)->find();
 	}

    public function get_config_all($condition_config,$field='*'){
        $config = M('Config')->field($field)->where($condition_config)->order('`sort` DESC')->getField('info,value',true);
        return $config;
    }

	public function remove(){
		$this->remove_cache();
	}

	public function remove_cache(){
		$this->clearDataCacheDir('system_config');
	}

	protected function get_domain($host){
		$host = strtolower($host);
		$two_suffix = array('.com.cn','.gov.cn','.net.cn','.org.cn','.ac.cn');
		foreach($two_suffix as $key=>$value){
			preg_match('#(.*?)'.$value.'$#',$host,$match_arr);
			if(!empty($match_arr)){
				$match_array = $match_arr;
				break;
			}
		}
		$host_arr = explode('.',$host);
		if(!empty($match_array)){
			$host_arr_last1 = array_pop($host_arr);
			$host_arr_last2 = array_pop($host_arr);
			$host_arr_last3 = array_pop($host_arr);

			return $host_arr_last3.'.'.$host_arr_last2.'.'.$host_arr_last1;
		}else{
			$host_arr_last1 = array_pop($host_arr);
			$host_arr_last2 = array_pop($host_arr);
			return $host_arr_last2.'.'.$host_arr_last1;
		}
	}
	public function get_pay_method($notOnline=0,$notOffline=0,$is_wap=false,$is_app=false,$is_all=false,$is_refund=false){
		$tmp_config_list = $this->get_gid_config(7);
		$tmp_config_list_app = $this->get_gid_config(23);
		$tmp_config_list_tl = $this->get_gid_config(60);

		foreach($tmp_config_list as $key=>$value){
			if(in_array($value['tab_id'],array('paylinx','wepayez','fubei'))){
				continue;
			}
			$config_list[$value['tab_id']]['name'] = L_($value['tab_name']);
			$config_list[$value['tab_id']]['config'][$value['name']] = $value['value'];

			if(strpos($value['name'],'xiantao_card') !== false){
				$config_list[$value['tab_id']]['name'] = C('config.xiantao_card_alias_name') ?: L_($value['tab_name']);
			}
			
			if(strpos($value['name'],'service_charge_open') !== false){
				$config_list[$value['tab_id']]['config']['service_charge_open'] = $value['value'];
				unset($value);
			}
			if(strpos($value['name'],'discount_tips') !== false){
				$config_list[$value['tab_id']]['config']['discount_tips'] = $value['value'] ? $value['value'] : '';
				unset($value);
			}
			if(strpos($value['name'],'service_charge') !== false){
				$config_list[$value['tab_id']]['config']['service_charge'] = $value['value'] ? $value['value'] : 0;
				unset($value);
			}
			if(strpos($value['name'],'rates') !== false){
				$config_list[$value['tab_id']]['config']['rates'] = $value['value'] ? $value['value'] : 0;
				unset($value);
			}
		}
		foreach($tmp_config_list_app as $key=>$value){
			$config_list[$value['tab_id']]['name'] = L_($value['tab_name']);
			$config_list[$value['tab_id']]['config'][$value['name']] = $value['value'];
			if(strpos($value['name'],'service_charge_open') !== false){
				$config_list[$value['tab_id']]['config']['service_charge_open'] = $value['value'];
				unset($value);
			}
			if(strpos($value['name'],'discount_tips') !== false){
				$config_list[$value['tab_id']]['config']['discount_tips'] = $value['value'];
				unset($value);
			}
			if(strpos($value['name'],'service_charge') !== false){
				$config_list[$value['tab_id']]['config']['service_charge'] = $value['value'];
				unset($value);
			}
			if(strpos($value['name'],'rates') !== false){
				$config_list[$value['tab_id']]['config']['rates'] = $value['value'] ? $value['value'] : 0;
				unset($value);
			}
		}
		foreach($tmp_config_list_tl as $key=>$value){
			$config_list[$value['tab_id']]['name'] = L_($value['tab_name']);
			$config_list[$value['tab_id']]['config'][$value['name']] = $value['value'];
		}
		//剔除已关闭的支付
		foreach($config_list as $key=>$value){
			$pigcms_key = 'pay_'.$key.'_open';
            if (empty($value['config'][$pigcms_key])
                || (!$is_all && $is_wap && $key == 'chinabank')
                || (!$is_all && !$is_refund && $is_wap && $key == 'alipay' && $value['config'][$pigcms_key] == 3)
                || (!$is_all && !$is_refund && empty($is_wap) && $key == 'alipay' && $value['config'][$pigcms_key] == 2)
                || ($_POST['app_type'] != 'wxapp' && $key == 'farmersbankpay') // farmersbankpay目前只支持小程序环境下可用
            ) {
				unset($config_list[$key]);
			}else{
				$tmp_alias = 'pay_'.$key.'_alias_name';
				if(!empty($value['config'][$tmp_alias])){
					$config_list[$key]['name'] = $value['config'][$tmp_alias];
				}
			}

		}
				
		if(C('config.pay_xiantao_debit_card_open')){
			$config_list['xiantao_debit_card']['name'] = C('config.xiantao_debit_card_alias_name') ?: L_('智慧仙桃储值卡');
			$config_list['xiantao_debit_card']['config']['xiantao_debit_card'] = $value['value'] ? $value['value'] : 0;
		}

		if(C('config.pay_xiantao_abc_card_open')){
			$config_list['xiantao_abc_card']['name'] = C('config.xiantao_abc_card_alias_name') ?:  L_('农行支付');
			$config_list['xiantao_abc_card']['config']['xiantao_abc_card'] = $value['value'] ? $value['value'] : 0;
		}

		if(C('config.pay_xiantao_union_card_open')){
			$config_list['xiantao_union_card']['name'] = C('config.xiantao_union_card_alias_name') ?:  L_('银联支付');
			$config_list['xiantao_union_card']['config']['xiantao_union_card'] = $value['value'] ? $value['value'] : 0;
		}



		if (!$is_all && (!$is_wap||$is_app)) unset($config_list['alipayh5']);
		if (!$is_all && isset($config_list['alipayh5']) && $config_list['alipayh5']['config']['pay_alipayh5_open'] && $config_list['alipayh5']['config']['pay_alipayh5_appid'] && $config_list['alipayh5']['config']['pay_alipayh5_merchant_private_key'] && $config_list['alipayh5']['config']['pay_alipayh5_public_key']) {
		    unset($config_list['alipay']);
		}
		if($notOffline && $config_list['offline']){
			unset($config_list['offline']);
		}
		if($notOnline){
			$new_config_list = array();
			if($config_list['offline']){
				$new_config_list['offline'] = $config_list['offline'];
			}
			$config_list = $new_config_list;
		}

		if(C('config.pay_yzfpay_open')==1){
			$config_list['yzfpay_offline'] = array(
				'name'=>L_('翼支付线下')
			);
		}

		if(C('customization.life_tools') == 1){
			$config_list['employee_balance_pay'] = array(
				'name'=>L_('员工卡余额支付')
			);
			$config_list['employee_score_pay'] = array(
				'name'=>L_('员工卡积分支付')
			);
		}

        //hqpay环球汇通聚合支付
        if ($config_list['hqpay']) {
            $env = 'pc';//扫码
            $is_wap && $env = 'wap';//手机浏览器
            if (!$is_app) {
                $is_weixin_browser = false;
                $is_alipay_browser = false;
                //微信H5
                if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
                    $is_weixin_browser = true;
                    $env = 'wxH5';
                }
                //微信小程序
                if ($is_weixin_browser && strpos($_SERVER['HTTP_USER_AGENT'], 'miniProgram') !== false) {
                    $env = 'wxMini';
                }
                //支付宝H5
                if (strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false) {
                    $is_alipay_browser = true;
                    $env = 'alH5';
                }
                //支付宝小程序
                if ($is_alipay_browser && (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'miniprogram') !== false || strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'alipayide') !== false)) {
                    $env = 'alMini';
                }
            } else {
                $env = 'app';
            }
            $config_list['hqpay']['config']['env'] = $env;
            if ($env == 'app' || $env == 'pc' || $env == 'wap') {
                $config_list['hqpay_wx'] = [
                    'name' => L_('环球汇通-微信支付'),
                    'config' => $config_list['hqpay']['config']
                ];
                $config_list['hqpay_al'] = [
                    'name' => L_('环球汇通-支付宝支付'),
                    'config' => $config_list['hqpay']['config']
                ];
            } else {
                if ($env == 'wxH5' || $env == 'wxMini') {
                    $config_list['hqpay_wx'] = [
                        'name' => L_('环球汇通-微信支付'),
                        'config' => $config_list['hqpay']['config']
                    ];
                } else if ($env == 'alH5') { //|| $env == 'alMini'
                    $config_list['hqpay_al'] = [
                        'name' => L_('环球汇通-支付宝支付'),
                        'config' => $config_list['hqpay']['config']
                    ];
                }
            }
            unset( //隐藏原有的微信和支付宝支付方式
                $config_list['hqpay'],
                $config_list['alipay'],
                $config_list['weixin'],
                $config_list['alipayh5'],
                $config_list['weixinh5'],
                $config_list['weixinapp'],
                $config_list['alipayapp']
            );
        }

		return $config_list;
	}
	/**
     * RGB转 十六进制
     * @param $rgb RGB颜色的字符串 如：rgb(255,255,255);
     * @return string 十六进制颜色值 如：#FFFFFF
     */
	protected function RGBToHex($rgb){
		$rgb = str_replace(' ','',$rgb);
        $regexp = "/^rgb\(([0-9]{0,3})\,\s*([0-9]{0,3})\,\s*([0-9]{0,3})\)/";
        $re = preg_match($regexp, $rgb, $match);
        $re = array_shift($match);
        $hexColor = "#";
        $hex = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'A', 'B', 'C', 'D', 'E', 'F');
        for ($i = 0; $i < 3; $i++) {
            $r = null;
            $c = $match[$i];
            $hexAr = array();
            while ($c > 16) {
                $r = $c % 16;
                $c = ($c / 16) >> 0;
                array_push($hexAr, $hex[$r]);
            }
            array_push($hexAr, $hex[$c]);
            $ret = array_reverse($hexAr);
            $item = implode('', $ret);
            $item = str_pad($item, 2, '0', STR_PAD_LEFT);
            $hexColor .= $item;
        }
        return $hexColor;
    }
    /**
     * 十六进制 转 RGB
     */
    protected function hex2rgb($hexColor){
		$hexColor = str_replace(' ','',$hexColor);
        $color = str_replace('#', '', $hexColor);
        if (strlen($color) > 3) {
            $rgb = array(
                'r' => hexdec(substr($color, 0, 2)),
                'g' => hexdec(substr($color, 2, 2)),
                'b' => hexdec(substr($color, 4, 2))
            );
        } else {
            $color = $hexColor;
            $r = substr($color, 0, 1) . substr($color, 0, 1);
            $g = substr($color, 1, 1) . substr($color, 1, 1);
            $b = substr($color, 2, 1) . substr($color, 2, 1);
            $rgb = array(
                'r' => hexdec($r),
                'g' => hexdec($g),
                'b' => hexdec($b)
            );
        }
        return $rgb;
    }

	/*
	 *  清空系统缓存
	 */
	public function clearCache(){
		import('ORG.Util.Dir');
        Dir::delDirnotself(WEB_PATH.'/runtime');
	}

	/*
	 *  删除目录下的缓存
	 */
	public function clearDataCacheDir($dirName){
		if($dirName == 'system_config'){
	        // 清除v20缓存
	        invoke_v20_service('\app\common\model\service\CacheSqlService/deleteCache',['param' => ['name'=>'app_config']]);
	        // 清除v20缓存
	        invoke_v20_service('\app\common\model\service\CacheSqlService/deleteCache',['param' => ['name'=>'o2oconfig']]);
		}


		if(C('DATA_CACHE_TYPE') == 'File'){
			import('ORG.Util.Dir');
			Dir::delDirnotself(WEB_PATH.trim(C('DATA_CACHE_PATH'),'.').$dirName.'/');
		}
	}

    public function get_config_list($where,$field = 'name,value') {
        // 初始化 物业管理员 数据层
        $configs = $this->where($where)->field($field)->select();
        $config = [];
        if (true) {
            foreach($configs as $key=>$value){
                $config[$value['name']] = $value['value'];
            }
            if (isset($config['Currency_txt'])) {
                $config['Currency_txt'] = $config['Currency_txt'] ? $config['Currency_txt'] : '元';
            }
            if (isset($config['Currency_symbol'])) {
                $config['Currency_symbol'] = $config['Currency_symbol'] ? $config['Currency_symbol'] : '￥';
            }
        }
        return $config;
    }

    /**
     * 获取当前环境
     */
    public function get_this_env($device_id = '', $app_type = 0) {
        $env = 'pc';//扫码
        $device_id == 'packapp' && $env = 'wap';
        if (strpos($_SERVER['HTTP_USER_AGENT'],'pigcmso2oreallifeapp') !== false || in_array($app_type, [1, 2])) {
            $env = 'app';
        }
        if ($env != 'app') {
            $is_weixin_browser = false;
            $is_alipay_browser = false;
            //微信H5
            if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
                $is_weixin_browser = true;
                $env = 'wxH5';
            }
            //微信小程序
            if ($is_weixin_browser && (strpos($_SERVER['HTTP_USER_AGENT'], 'miniProgram') !== false || $device_id == 'wxapp')) {
                $env = 'wxMini';
            }
            //支付宝H5
            if (strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false) {
                $is_alipay_browser = true;
                $env = 'alH5';
            }
            //支付宝小程序
            if ($is_alipay_browser && (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'miniprogram') !== false || strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'alipayide') !== false)) {
                $env = 'alMini';
            }
        }
        return $env;
    }
    
/**   
     * 获取js微信配置信息
     * @param $work
     * @param $location_url
     * @return array
     */
    public function getWxConfig($work,$location_url){
        $_POST['work'] = $work;
        $_POST['location_url'] = $location_url;
        $share = new WechatShare($this->get_config(), '');
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
        }
        else if($_POST['work'] == 'deliver'){
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
        }
        else if($_POST['work'] == 'merchant'){
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
        }
        else if ($_POST['work'] == 'share_coupon') {
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
        }
        else if ($_POST['work'] == 'my') {
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
        return $arr;
    }
}
?>