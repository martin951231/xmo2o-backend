<?php
/*
 * 社区首页
 *
 */
class House_indexAction extends BaseAction{
	//	获取社区基本信息管理
    public function index(){
    	$ticket = I('ticket');
    	if(empty($ticket)){
			$this->returnCode('20044013');
    	}
    	$village_id	=	I('village_id');
		if($village_id){
			$info = ticket::get($ticket, $this->DEVICE_ID, true);
			if(empty($info)){
				$this->returnCode('20000009');
			}
			if($info['uid'] <= 10000000){
				$this->returnCode('20000010');
    		}
			$village_info = M('House_village')->field(array('qrcode_id','wx_image','wx_desc'),true)->where(array('village_id'=>$village_id))->find();
			$village_info	=	isset($village_info)?$village_info:array();
//			$village_info['long'] = floatval($village_info['long']);
//			$village_info['lat'] = floatval($village_info['lat']);
			unset($village_info['pwd']);
			if($village_info['province_id']){
				$province_id	=	$this->getCityId($village_info['province_id']);
				$village_info['default']['province_name']	=	$province_id['area_name'];
				$village_info['default']['province_id']	=	$village_info['province_id'];
			}
			if($village_info['city_id']){
				$city_id	=	$this->getCityId($village_info['city_id']);
				$village_info['default']['city_name']	=	$city_id['area_name'];
				$village_info['default']['city_id']	=	$village_info['city_id'];
			}
			if($village_info['area_id']){
				$area_id	=	$this->getCityId($village_info['area_id']);
				$village_info['default']['area_name']	=	$area_id['area_name'];
				$village_info['default']['area_id']	=	$village_info['area_id'];
			}
			if($village_info['circle_id']){
				$circle_id	=	$this->getCityId($village_info['circle_id']);
				$village_info['default']['circle_name']	=	$circle_id['area_name'];
				$village_info['default']['circle_id']	=	$village_info['circle_id'];
			}
			if(empty($village_info['default'])){
				$village_info['default']	=	(object)array();
			}
			$village_info['longs']	=	$village_info['long'];
			$village_info['lats']	=	$village_info['lat'];
			$village_info['now_city']	=	$this->config['now_city'];
			$village_info['many_city']	=	$this->config['many_city'];
			unset($village_info['long'],$village_info['lat']);
			$arr	=	array(
				'village'	=>	isset($village_info)?$village_info:(object)array(),
			);
		}else{
			$this->returnCode('30000001');
		}
		$this->returnCode(0,$arr);
    }
    //	获取省、市、区、商圈
    public	function	getProvince($stats=0){
    	$province_id	=	I('area_pid',0);
    	$aProvince_id	=	M('Area')->field(array('area_id','area_name','area_pid'))->where(array('area_pid'=>$province_id,'is_open'=>1))->select();
    	if($stats){
			return $aProvince_id;
    	}else{
			$this->returnCode(0,$aProvince_id);
    	}
    }
    //	获取市
   // public	function	getCity(){
//		$province_id	=	I('area_pid');
//		if(empty($province_id)){
//			$this->returnCode('20090001');
//		}
//		$aCity_id	=	M('Area')->field(array('area_id','area_name','area_pid'))->where(array('area_pid'=>$province_id,'is_open'=>1))->select();
//		$this->returnCode(0,$aCity_id);
//    }
//    //	获取区
//    public	function	getArea(){
//		$city_id	=	I('area_pid');
//		if(empty($city_id)){
//			$this->returnCode('20090002');
//		}
//		$aArea_id	=	M('Area')->field(array('area_id','area_name','area_pid'))->where(array('area_pid'=>$city_id,'is_open'=>1))->select();
//		$this->returnCode(0,$aArea_id);
//    }
//    //	获取商圈
//    public	function	getCircle(){
//		$area_id	=	I('area_pid');
//		if(empty($area_id)){
//			$this->returnCode('20090003');
//		}
//		$aCircle_id	=	M('Area')->field(array('area_id','area_name','area_pid'))->where(array('area_pid'=>$area_id,'is_open'=>1))->select();
//		$this->returnCode(0,$aCircle_id);
//    }
    //	用ID获取城市
    public	function	getCityId($area_id=0){
		if(empty($area_id)){
			return array();
		}
		$aArea_id	=	M('Area')->field(array('area_id','area_name','area_pid'))->where(array('area_id'=>$area_id,'is_open'=>1))->find();
		if(empty($aArea_id)){
			$aArea_id	=	(object)array();
		}
		return $aArea_id;
    }
    //	修改社区基本信息管理
    public	function	villageEdit(){
		$ticket = I('ticket');
    	if(empty($ticket)){
			$this->returnCode('20044013');
    	}
    	$info = ticket::get($ticket, $this->DEVICE_ID, true);
    	if(empty($info)){
			$this->returnCode('20000009');
		}
		if($info['uid'] <= 10000000){
			$this->returnCode('20000010');
    	}
    	$village_id	=	I('village_id');
    	if(empty($village_id)){
			$this->returnCode('30000001');
    	}else{
    		$arr	=	array(
    			'property_phone'	=>	I('property_phone'),		//物业联系电话
    			'property_address'	=>	I('property_address'),		//物业联系地址
    			'long'				=>	I('longs'),					//经度
    			'lat'				=>	I('lats'),					//纬度
    			'province_id'		=>	I('province_id'),			//省
    			'city_id'			=>	I('city_id'),				//市
    			'area_id'			=>	I('area_id'),				//区
    			'circle_id'			=>	I('circle_id'),				//商圈
    			'village_address'	=>	I('village_address'),		//社区地址
    			'property_price'	=>	I('property_price'),		//一平方米的物业费单价
    			'water_price'		=>	I('water_price'),			//水费单价
    			'electric_price'	=>	I('electric_price'),		//电费单价
    			'gas_price'			=>	I('gas_price'),				//燃气费单价
    			'park_price'		=>	I('park_price'),			//停车位每月价格
    			'has_custom_pay'	=>	I('has_custom_pay'),		//是否支持自定义缴费
    			'has_express_service'=>	I('has_express_service'),	//是否开启快递代收
    			'has_visitor'		=>	I('has_visitor'),			//是否开启访客登记
    			'has_slide'			=>	I('has_slide'),				//是否开启社区幻灯片
    			'has_service_slide'	=>	I('has_service_slide'),		//是否开启便民页面幻灯片 0，关闭 1，开启
    		);
    		foreach($arr as $k=>$v){
				if($v==null){
					unset($arr[$k]);
				}
    		}
    		$aVillage_id	=	M('House_village')->field(array('village_id','status'))->where(array('village_id'=>$village_id))->find();
    		if($aVillage_id){
    			if($aVillage_id['status'] == 0){
					if($arr['long'] && $arr['lat']){
						$arr['status']	=	1;
					}
    			}
				$aSave	=	M('House_village')->where(array('village_id'=>$village_id))->data($arr)->save();
    		}else{
				$this->returnCode('20090005');
    		}
    	}
    	if($aSave){
			$this->returnCode(0);
    	}else if($aSave === 0){
			$this->returnCode('20090007');
    	}else{
			$this->returnCode('20090006');
    	}
    }
    public function see_qrcode(){
    	$this->is_existence();
    	$type		=	'house';
    	$village_id	=	I('village_id');
		//判断ID是否正确，如果正确且以前生成过二维码则得到ID
		$pigcms_return = D('House_village')->get_qrcode($village_id);
		if(empty($pigcms_return)){
			$this->returnCode('20090053');
		}
		if(empty($pigcms_return['qrcode_id'])){
			$qrcode_return = D('Recognition')->get_new_qrcode($type,$village_id);
		}else{
			$qrcode_return = D('Recognition')->get_qrcode($pigcms_return['qrcode_id']);
			if($qrcode_return['error_code']){
				$this->returnCode('20090055');
			}
		}
		if($qrcode_return['error_code']){
			$this->returnCode('20090056');
		}else if($qrcode_return['qrcode'] == 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='){
			$qrcode_return = D('Recognition')->get_new_qrcode($type,$village_id);
		}

		//echo $_SERVER['DOCUMENT_ROOT'].'/runtime/qrcode/house';
		if(!file_exists($_SERVER['DOCUMENT_ROOT'].'/runtime/qrcode/house/'.$village_id.'.png')){
			if(!file_exists($_SERVER['DOCUMENT_ROOT'].'/runtime/qrcode/house')){
				echo $_SERVER['DOCUMENT_ROOT'].'/runtime/qrcode/house';
				mkdir($_SERVER['DOCUMENT_ROOT'].'/runtime/qrcode/house/',0777,true);
			}
			import('ORG.Net.Http');
			$http = new Http();
			file_put_contents('./runtime/qrcode/house/'.$village_id.'.png',Http::curlGet($qrcode_return['qrcode']));
		}
		$arr	=	array(
			'img'	=>	$this->config['site_url'].'/runtime/qrcode/house/'.$village_id.'.png',
		);
		$this->returnCode(0,$arr);
    }
	
	public function footer_list(){
		//可以根据小区情况来返回不同的  $_POST['village_id']
		$village_id = I('village_id','','intval');
		if ($village_id>0) {
            $switch= C('config.house_visualize_switch') ? C('config.house_visualize_switch') : 0;
            if(intval($switch) < 1){
                $where_footer_menu = [
                    'village_id' => $village_id,
                    'status' => 1,
                ];
                $home_menu_list = M('House_village_footer_menu')->where($where_footer_menu)->order('sort desc,id asc')->select();
            }else{

                $where_footer_menu=' (village_id= 0 or village_id= '.$village_id.' ) and status= 1 and (filter_village_ids is null OR  !FIND_IN_SET('.$village_id.', filter_village_ids )) ';
                $link=A('System/link');
                $modules = D('House_link')->village_library($village_id,$this->config,$link->village_modules,'wap.php');
                if($modules){
                    foreach ($modules as $k=>$v){
                        if(intval($v['sub']) > 0){
                            unset($modules[$k]);
                        }
                    }
                    $module=array_column($modules, 'module');
                    $where_str_modules = "'".implode("','", $module)."'";
                    $where_footer_menu.=' and ( source_type = "0" or source_type in( '.$where_str_modules.'))';
                }
                $home_menu_list = M('House_village_footer_menu')->where($where_footer_menu)->order('sort desc,village_id asc,id asc')->select();
            }

        } else {
            $home_menu_list = [];
        }
        $tabbar_type =  $_POST['wxapp_type'] == 'village' ? 'tabbar' : 'business';
        if (isset($_POST['wxapp_own_village_id'])&&intval($_POST['wxapp_own_village_id'])>0) {
            $tabbar_type = 'tabbar';
        }
        if ($home_menu_list) {
            $footer_menu_list = [];
            $hasVillage = $this->config['system_type'] == 'village' ? true : false;
            if (!$hasVillage) {
                $hasVillage = $_POST['wxapp_type'] == 'village' ? true : false;
            }
            foreach ($home_menu_list as $val) {
				
				//外卖首页
				if($hasVillage && (stripos($val['url'], 'c=Shop&a=index') !== false || stripos($val['url'], 'pages/shop_index/index') !== false || stripos($val['url'], 'c=Shop&a=classic_index') !== false)){
					$val['url'] = $this->config['site_url'] . D('House_village')->base_url . 'pages/nofooter/shop/shop_index';
				}
				
                if (strpos($val['hover_pic_path'],'/upload/') !== false) {
                    $hover_pic_path = replace_file_domain($val['hover_pic_path']);
                } else {
                    $hover_pic_path = file_domain().'/upload/slider/'.$val['hover_pic_path'];
                }
                if (strpos($val['pic_path'],'/upload/') !== false) {
                    $pic_path = replace_file_domain($val['pic_path']);
                } else {
                    $pic_path = file_domain().'/upload/slider/'.$val['pic_path'];
                }
                $msg = [
                    'hover_pic_path' => $hover_pic_path,
                    'name' => $val['name'],
                    'pic_path' => $pic_path,
                    'url' => $val['url']
                ];
                if (strpos($val['url'],'/pages/village_menu/index') !== false) {
                    $msg['is_tab'] = true;
                    $msg['original_page'] = 'village_menu_index';
                } elseif (strpos($val['url'],'/pages/village_menu/convenient') !== false) {
                    $msg['is_tab'] = true;
                    $msg['original_page'] = 'village_menu_convenient';
                } elseif (strpos($val['url'],'/pages/village_menu/resident') !== false || strpos($val['url'],'/pages/village_menu/new_resident') !== false) {
                    $msg['is_tab'] = true;
                    $msg['original_page'] = 'village_menu_resident';
                } elseif (strpos($val['url'],'/pages/village_menu/my') !== false) {
                    $msg['is_tab'] = true;
                    $msg['original_page'] = 'village_menu_my';
                } else {
                    $msg['is_tab'] = false;
                    $msg['original_page'] = '';
                }
                $msg['wxapp_id']='';
                $msg['wxapp_page']='';
                if(isset($val['wxapp_id']) && !empty($val['wxapp_id'])){
                    $msg['wxapp_id']=$val['wxapp_id'];
                    $msg['wxapp_page']=$val['wxapp_page'];
                }

                $footer_menu_list[] = $msg;
            }
            $footer_menu_list = array_values($footer_menu_list);
            $now_village = M('House_village')->where(['village_id' => $village_id])->field('app_footer_txtcolor, app_footer_txtactivecolor, app_footer_bgcolor')->find();
            $backgroundColor = preg_match("/^rgb/", $now_village['app_footer_bgcolor']) ? RGBToHex($now_village['app_footer_bgcolor']) : $now_village['app_footer_bgcolor'];
            $selectedColor = preg_match("/^rgb/", $now_village['app_footer_txtactivecolor']) ? RGBToHex($now_village['app_footer_txtactivecolor']) : $now_village['app_footer_txtactivecolor'];
            $color = preg_match("/^rgb/", $now_village['app_footer_txtcolor']) ? RGBToHex($now_village['app_footer_txtcolor']) : $now_village['app_footer_txtcolor'];
            if (!$backgroundColor) {
                $backgroundColor = preg_match("/^rgb/", $this->config['app_footer_bgcolor']) ? RGBToHex($this->config['app_footer_bgcolor']) : $this->config['app_footer_bgcolor'];
            }
            if (!$selectedColor) {
                $selectedColor = preg_match("/^rgb/", $this->config['app_footer_txtactivecolor']) ? RGBToHex($this->config['app_footer_txtactivecolor']) : $this->config['app_footer_txtactivecolor'];
            }
            if (!$color) {
                $color = preg_match("/^rgb/", $this->config['app_footer_txtcolor']) ? RGBToHex($this->config['app_footer_txtcolor']) : $this->config['app_footer_txtcolor'];
            }
            $return = [
                'hidden' => false,
                'backgroundColor' => $backgroundColor,
                'selectedColor' => $selectedColor,
                'color' => $color,
                'list' => $footer_menu_list,
                'tabbar_type' => $tabbar_type,
            ];
        } else {
            $footer_menu_list = include('conf/village_footer_menu.php');
            if($this->config['house_show_center'] == 3){//不显示
                unset($footer_menu_list[2]);
            }elseif($this->config['house_show_center'] == 2){//社区团购
                $footer_menu_list[2] = array(
                    'hover_pic_path' => $this->config['site_url']."/static/images/house/village_group2.png",
                    'name' => empty($this->config['village_group_alias'])?'社区拼团':$this->config['village_group_alias'],
                    'pic_path' => $this->config['site_url']."/static/images/house/village_group.png",
                    'url' => $this->config['site_url']."/wap.php?c=House&a=go_villagegroup",
					'is_tab' => false,
                    'original_page' => ''
                );
            }elseif($this->config['house_show_center'] == 1){//社区超市
				$now_village = D('House_village')->get_one($village_id);
				$market_shop_id = 0;
				if(empty($now_village['shop_id'])){
					if(!$this->config['house_market_shopId']){
						unset($footer_menu_list[2]);
					}else{
						$market_shop_id = $this->config['house_market_shopId'];
					}
				}else{
					$market_shop_id = $now_village['shop_id'];
				}
				if($market_shop_id){
					$footer_menu_list[2] = array(
						'hover_pic_path' => $this->config['site_url']."/static/images/house/village_group2.png",
						'name' => $this->config['house_market_name'] ? $this->config['house_market_name'] : '社区超市',
						'pic_path' => $this->config['site_url']."/static/images/house/village_group.png",
						'url' => $this->config['site_url'] . D('House_village')->base_url . 'pages/shop_new/shopDetail/shopDetail?store_id=' . $market_shop_id . '&village_id=' . $village_id,
						'is_tab' => false,
                        'original_page' => ''
					);
				}
            }elseif($this->config['house_show_center'] == 0){//常用电话
                $base_url = D('House_village')->base_url;
                $footer_menu_list[2] = array(
                    'hover_pic_path' => $this->config['site_url']."/static/images/house/phone2.png",
                    'name' => '常用电话',
                    'pic_path' => $this->config['site_url']."/static/images/house/phone.png",
                    'url' => $this->config['site_url'] . $base_url . "pages/village/commonly_used/house_phone",
					'is_tab' => false,
                    'original_page' => ''
                );
            }
            $footer_menu_list = array_values($footer_menu_list);
            $return = [
                'hidden' => false,
                'backgroundColor' => preg_match("/^rgb/", $this->config['app_footer_bgcolor']) ? RGBToHex($this->config['app_footer_bgcolor']) : $this->config['app_footer_bgcolor'],
                'selectedColor' => preg_match("/^rgb/", $this->config['app_footer_txtactivecolor']) ? RGBToHex($this->config['app_footer_txtactivecolor']) : $this->config['app_footer_txtactivecolor'],
                'color' => preg_match("/^rgb/", $this->config['app_footer_txtcolor']) ? RGBToHex($this->config['app_footer_txtcolor']) : $this->config['app_footer_txtcolor'],
                'list' => $footer_menu_list,
                'tabbar_type' => $tabbar_type,
            ];
        }

        // 底部导航配置
        $return['footer_menu_config'] = 1;
        // 是否开启新版首页 可视化页面类型  加域名限制 如果不是营山，强制老版
        $return['visualization_page_type'] = 0;
        if (!empty(C('customization.huizhisq'))||!empty(C('customization.device_car_pile'))){
            $village_about = D('house_village_info')->where(array('village_id' => $village_id))->field('visualization_page_type,template_floor')->find();
            $return['visualization_page_type'] = $village_about['visualization_page_type'];
            // 底部导航配置
            $return['footer_menu_config'] = $village_about['template_floor'];
        }
        $jsdhwy_new_wap_index=D('Config_customization')->getWapIndexJsdhwyUi();
        if($jsdhwy_new_wap_index>0){
            $village_about = D('house_village_info')->where(array('village_id' => $village_id))->field('visualization_page_type,template_floor')->find();
            $return['footer_menu_config'] = $village_about['template_floor'];
            if($village_about['template_floor']>1){
                $return['visualization_page_type'] = 1;
            }
        }
        $yuanJino2oCustomized=D('Config_customization')->getYuanJino2oCustomized();
        if($yuanJino2oCustomized){
            $return['visualization_page_type'] = 0;
        }
		if(!$return['backgroundColor']){
			$return['backgroundColor'] = '#FAFAFC';
		}
		if(!$return['color']){
			$return['color'] = '#777777';
		}
		if(!$return['selectedColor']){
			$return['selectedColor'] = '#06c1ae';
		}
		if (isset($return['list']) && $return['list']) {
            $db_house_village = D('House_village');
            $app_version      = intval($_POST['app_version']);
            $deviceId         = trim($_POST['Device-Id']);
		    foreach ($return['list'] as &$tab) {
                if (strstr($tab['url'], 'pages/village/my/') || strstr($tab['url'], 'pages/village_my/')) {
                    $paramPath = [
                        'pagePath' => $tab['url'],
                    ];
                    $tab['url'] = $db_house_village->villagePagePath($app_version,$deviceId,$paramPath);
                }
            }
        }
		
		$this->returnCodeOk($return);
	}
}