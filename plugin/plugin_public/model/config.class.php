<?php
class config{
	public function get_config($now_lang = ''){
		$config = S('system_config_'.$now_lang);
		if(empty($config)){
			$open_multilingual = array_column(M('Config')->field('name,value')->where('name in ("open_multilingual","default_language")')->select(), 'value', 'name');
			$_field = '`name`,`value`';
			$lang_model = D('Lang');
			if($open_multilingual['open_multilingual'] == '1'){
				$_field .= ',`is_lang`';
				if(empty($now_lang)){
					$now_lang = $open_multilingual['default_language'];
				}
				$_field .= ($now_lang && $now_lang != 'chinese') ? ',`value_'.$now_lang.'`' : '';
			}
			$configs = M('Config')->field($_field)->select();
			foreach($configs as $key=>$value){
				if($open_multilingual['open_multilingual'] == '1' && $value['is_lang'] == '1' && $now_lang && $now_lang != 'chinese'){//系统开启了多语言
					$config[$value['name']] = $value['value_'.$now_lang];
				}
				else{
					$config[$value['name']] = $value['value'];
				}
			}
			$domain_array = parse_url($config['site_url']);
			$config['top_domain'] = $this->get_domain($domain_array['host']);
			if(stripos($config['site_url'],'https://') === 0){
				$config['use_https'] = true;
			}else{
				$config['use_https'] = false;
			}
			
		
			//图片、APK链接强制更改为 资源域名
			if($config['static_oss_switch'] && $config['static_oss_access_domain_names']){
				$replace_extension = array('jpg','png','gif','apk','jpeg','ico');
				foreach($config as $key=>$value){
					//图片、APK链接强制更改为 资源域名
					if(stripos($value,'/upload/') !== false){
						$pathinfoArr = pathinfo($value);
						if(in_array(strtolower($pathinfoArr['extension']),$replace_extension)){
							$config[$key] = str_replace($config['site_url'],$_SERVER['REQUEST_SCHEME'].'://'.$config['static_oss_access_domain_names'],$value);
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
		
			
			$config['system_lang'] = $now_lang;
		
			S('system_config_'.$now_lang,$config,120);
		}
		
		$config['config_site_url'] = $config['site_url'];
		$config['now_site_url'] = $_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'];
		
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
		$config = M('Config')->field(true)->where($condition_config)->order('`sort` DESC')->select();
		
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
		return M('Config')->where($where)->find();
 	}
	
	public function remove(){
		$this->remove_cache();
	}
	
	public function remove_cache(){
		S('system_config',null);
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
	public function get_pay_method($notOnline=0,$notOffline=0,$is_wap=false,$is_app=false){
		$tmp_config_list = $this->get_gid_config(7);
		$tmp_config_list_app = $this->get_gid_config(23);
		$tmp_config_list_tl = $this->get_gid_config(60);

		foreach($tmp_config_list as $key=>$value){
			if(in_array($value['tab_id'],array('paylinx','wepayez'))){
				continue;
			}
			$config_list[$value['tab_id']]['name'] = $value['tab_name'];
			$config_list[$value['tab_id']]['config'][$value['name']] = $value['value'];
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
		}
		foreach($tmp_config_list_app as $key=>$value){
			$config_list[$value['tab_id']]['name'] = $value['tab_name'];
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
		}
		foreach($tmp_config_list_tl as $key=>$value){
			$config_list[$value['tab_id']]['name'] = $value['tab_name'];
			$config_list[$value['tab_id']]['config'][$value['name']] = $value['value'];
		}
		//剔除已关闭的支付
		foreach($config_list as $key=>$value){
			$pigcms_key = 'pay_'.$key.'_open';
			if(empty($value['config'][$pigcms_key]) || ($is_wap && $key == 'chinabank') || ($is_wap && $key == 'alipay' && $value['config'][$pigcms_key] == 3) || (empty($is_wap) && $key == 'alipay' && $value['config'][$pigcms_key] == 2)){
				unset($config_list[$key]);
			}else{	
				$tmp_alias = 'pay_'.$key.'_alias_name';
				if(!empty($value['config'][$tmp_alias])){
					$config_list[$key]['name'] = $value['config'][$tmp_alias];
				}
			}
		}
		if (!$is_wap||$is_app) unset($config_list['alipayh5']);
		if (isset($config_list['alipayh5']) && $config_list['alipayh5']['config']['pay_alipayh5_open'] && $config_list['alipayh5']['config']['pay_alipayh5_appid'] && $config_list['alipayh5']['config']['pay_alipayh5_merchant_private_key'] && $config_list['alipayh5']['config']['pay_alipayh5_public_key']) {
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
		if(C('DATA_CACHE_TYPE') == 'File'){
			import('ORG.Util.Dir');
			Dir::delDirnotself(WEB_PATH.trim(C('DATA_CACHE_PATH'),'.').$dirName.'/');
		}
	}
}
?>