<?php
/*
 * 新版快店接口
 *
 */
class Shop_newAction extends BaseAction{
	
	protected $send_time_type = [];

	public function _initialize(){
		parent::_initialize();
		$this->send_time_type = [
			L_('分钟'),
			L_('小时'),
			L_('天'),
			L_('周'),
			L_('月'),
		];
	}
		
	public function lbs_location(){
		if(empty($_POST['lng']) || empty($_POST['lat'])){
			$this->returnCode(1001,array(),L_('请携带用户经纬度'));
		}
		$lng = $_POST['lng'];
		$lat = $_POST['lat'];
		$return = array();
		
		$lbs_type = 0;
		if($_POST['lbs_type'] == 'gcj02'){
			$lbs_type = 3;
		}else if($_POST['lbs_type'] == 'gps'){
			$lbs_type = 1;
		}
		if($lbs_type){
			import('@.ORG.longlat');
			$longlat_class = new longlat();
			$location2 = $longlat_class->toBaidu($_POST['lat'],$_POST['lng'],$lbs_type);
			$lat = $location2['lat'];
			$lng = $location2['lng'];
		}
		
		//获取当前省市区
		$now_city = D('Area')->cityMatching($lat,$lng,true);
		//如果当前城市没开，使用默认城市
		if(empty($now_city['is_open'])){
			$tmp_now_city = M('Area')->where(array('area_id'=>C('config.now_city')))->find();
			$area_lng = floatval($tmp_now_city['area_lng']);
			if(empty($area_lng)){
				$result = D('Area')->address_info($tmp_now_city['area_name']);
				$lng = $result['result']['location']['lng'];
				$lat = $result['result']['location']['lat'];
				M('Area')->where(array('area_id'=>C('config.now_city')))->data(array('area_lng'=>$lng,'area_lat'=>$lat))->save();
			}else{
				$lng = $tmp_now_city['area_lng'];
				$lat = $tmp_now_city['area_lat'];
			}
			$now_city = D('Area')->cityMatching($lat,$lng,true);
			$now_city['address_name'] = $tmp_now_city['area_name'];
			$now_city['address_addr'] = $tmp_now_city['area_name'];
			unset($now_city['google_place_id']);
		}
		$this->config['now_city'] = $now_city['area_id'];
		C('config.now_city',$now_city['area_id']);
		$return = array(
			'lng'			=>$lng,
			'lat'			=>$lat,
			'province_id'	=>$now_city['area_info']['province_id'],
			'province_name'	=>$now_city['area_info']['province_name'],
			'city_id'		=>$now_city['area_id'],
			'city_name'		=>$now_city['area_name'],
			'area_id'		=>$now_city['area_info']['area_id'],
			'area_name'		=>$now_city['area_info']['area_name'],
			'address_name'	=>$now_city['address_name'],
			'address_addr'	=>$now_city['address_addr'],
			'pois'			=>$now_city['pois'],
		);
		if($now_city['google_place_id']){
			$return['google_place_id'] = $now_city['google_place_id'];
		}
		
		$this->save_user_city($return['city_id'], $return['area_id']);
		
		$this->returnCode(0,$return);
	}

    public function index()
    {
        if (empty($_POST['lng']) || empty($_POST['lat'])) {
			//优先取默认城市下排序最高的第一个店铺，防止出现出不来店铺的情况。
			$top_store = M('Merchant_store')->where(['city_id'=>C('config.now_city'), 'status'=>'1'])->order('`sort` DESC')->find();
			if($top_store){
				$lng = $top_store['long'];
				$lat = $top_store['lat'];
			}else{
				$tmp_now_city = M('Area')->where(array('area_id' => $this->config['now_city']))->find();
				$area_lng = floatval($tmp_now_city['area_lng']);
				if (empty($area_lng)) {
					$result = D('Area')->address_info($tmp_now_city['area_name']);
					$lng = $result['result']['location']['lng'];
					$lat = $result['result']['location']['lat'];
					M('Area')->where(array('area_id' => $this->config['now_city']))->data(array('area_lng' => $lng, 'area_lat' => $lat))->save();
				} else {
					$lng = $tmp_now_city['area_lng'];
					$lat = $tmp_now_city['area_lat'];
				}
            }
        } else {
            $lng = $_POST['lng'];
            $lat = $_POST['lat'];
            $return = array();
            $lbs_type = 0;
            if ($_POST['lbs_type'] == 'gcj02') {
                $lbs_type = 3;
            } else if ($_POST['lbs_type'] == 'gps') {
                $lbs_type = 1;
            }
            if ($lbs_type) {
                import('@.ORG.longlat');
                $longlat_class = new longlat();
                $location2 = $longlat_class->toBaidu($_POST['lat'], $_POST['lng'], $lbs_type);
                $lat = $location2['lat'];
                $lng = $location2['lng'];
            }
        }

        $app_type   =   I('app_type');
        //获取当前省市区
        $now_city = D('Area')->cityMatching($lat, $lng);
        //如果当前城市没开，使用默认城市
        if (empty($now_city['is_open'])) {
            $tmp_now_city = M('Area')->where(array('area_id' => C('config.now_city')))->find();
            $area_lng = floatval($tmp_now_city['area_lng']);
            if (empty($area_lng)) {
                $result = D('Area')->address_info($tmp_now_city['area_name']);
                $lng = $result['result']['location']['lng'];
                $lat = $result['result']['location']['lat'];
                M('Area')->where(array('area_id' => C('config.now_city')))->data(array('area_lng' => $lng, 'area_lat' => $lat))->save();
            } else {
                $lng = $tmp_now_city['area_lng'];
                $lat = $tmp_now_city['area_lat'];
            }
            $now_city = D('Area')->cityMatching($lat, $lng);
            $now_city['address_name'] = $tmp_now_city['area_name'];
            $now_city['address_addr'] = $tmp_now_city['area_name'];
            if (empty($now_city['area_id'])) {
                $now_city['area_id'] = $tmp_now_city['area_id'];
            }
			unset($now_city['google_place_id']);
        }

        $this->config['now_city'] = $now_city['area_id'];
        C('config.now_city', $now_city['area_id']);
        C('config.now_shop_city', $now_city['area_id']);
        $return['city_info'] = array(
            'lng' => $lng,
            'lat' => $lat,
            'province_id' => $now_city['area_info']['province_id'],
            'province_name' => $now_city['area_info']['province_name'],
            'city_id' => $now_city['area_id'],
            'city_name' => $now_city['area_name'],
            'area_id' => $now_city['area_info']['area_id'],
            'area_name' => $now_city['area_info']['area_name'],
            'address_name' => $now_city['address_name'],
            'address_addr' => $now_city['address_addr'],
        );
		if($now_city['google_place_id']){
			$return['city_info']['google_place_id'] = $now_city['google_place_id'];
		}

        $this->save_user_city($return['city_info']['city_id'], $return['city_info']['area_id']);
		
        //轮播图
        $return['banner_list'] = D('Adver')->get_adver_by_key('wap_shop_index_top', 5, true);

        // 小程序跳转到别的小程序
        if ($this->DEVICE_ID == 'wxapp') {
            $return['banner_list'] = D('Adver')->replace_url_to_wxapp_page($return['banner_list']);
        }

		// app跳转到别的app
		if ($this->DEVICE_ID != 'wxapp') {
			$return['banner_list'] = D('Adver')->replace_url_to_app_page($return['banner_list'], $app_type);
		}

        //导航栏  分十个或八个的场景
        $tmp_wap_index_slider = D('Slider')->get_slider_by_key('wap_shop_slider', 0, true);
        $return['slider_list'] = array();
        if (count($tmp_wap_index_slider) > 8) {
            $wap_slider_number = 10;
        } else {
            $wap_slider_number = 8;
        }

		// app跳转到别的app
		if ($this->DEVICE_ID != 'wxapp') {
			$tmp_wap_index_slider = D('Adver')->replace_url_to_app_page($tmp_wap_index_slider, $app_type);
		}

        foreach ($tmp_wap_index_slider as $key => $value) {
            $tmp_i = floor($key / $wap_slider_number);

            // 小程序跳转到别的小程序
            if ($this->DEVICE_ID == 'wxapp' && $value['wxapp_id']) {
                $value['url'] = 'wxapp://' . $value['wxapp_id'];
                if ($value['wxapp_page']) {
                    // 小程序页面
                    $value['url'] .= '://' . $value['wxapp_page'];
                }
                unset($value['wxapp_id'], $value['wxapp_page']);
            }
            $return['slider_list'][$tmp_i][] = $value;
        }


        $return['slider_list_length'] = $wap_slider_number;

        //中间橱窗广告
        $showcase_adver_arr = D('Adver')->get_adver_by_key('wap_shop_index_showcase_adver', 1, true);
        if (empty($showcase_adver_arr)) {
            $return['showcase_adver'] = $this->getObj();
        } else {
            // 小程序跳转到别的小程序
            if ($_POST['Device-Id'] == 'wxapp') {
                $showcase_adver_arr = D('Adver')->replace_url_to_wxapp_page($showcase_adver_arr);
            }

			// app跳转到别的app
			if ($this->DEVICE_ID != 'wxapp') {
				$showcase_adver_arr = D('Adver')->replace_url_to_app_page($showcase_adver_arr, $app_type);
			}
            $return['showcase_adver'] = $showcase_adver_arr[0];
        }

        //中间3、4、5图广告
        // 广告开启根据单个城市
        $now_city_info = M('Area')->where(array('area_id'=>$return['city_info']['city_id']))->find();
        $center_type = $now_city_info['shop_main_page_center_type'];
        $return['center_slider_type'] = $center_type;
        if ($now_city_info['shop_main_page_show_ad']) {
			$today = date('Y-m-d');
			$time = date('H:i:s');
			$week = date('w');
        	$where['is_del'] = 0;
            $where['type'] = $center_type;
            $where['city_id'] = ['in', $this->config['now_city'].',0'];
			$where['_string'] = sprintf("(effect_date_type = 1 OR (start_date <= '%s' AND end_date >= '%s' AND FIND_IN_SET(%d,week))) AND (effect_time_type = 1 OR (start_time <= '%s' AND end_time >= '%s'))", $today, $today, $week, $time, $time);
            $slider_list = M('Shop_main_page_pic_slider')->field(true)->where($where)->order('sort DESC')->limit($center_type)->select();
            foreach ($slider_list as &$v) {
                $v['url'] = htmlspecialchars_decode($v['url']);
                $v['pic'] = file_domain() . '/upload/slider/' . $v['pic'];
                // 小程序跳转到别的小程序
                if ($this->DEVICE_ID == 'wxapp' && $v['wxapp_id']) {
                    $v['url'] = 'wxapp://' . $v['wxapp_id'];
                    if ($v['wxapp_page']) {
                        // 小程序页面
                        $v['url'] .= '://' . $v['wxapp_page'];
                    }
                    unset($v['wxapp_id'], $v['wxapp_page']);
                }
            }
            
			// app跳转到别的app
			if ($this->DEVICE_ID != 'wxapp') {
				$slider_list = D('Adver')->replace_url_to_app_page($slider_list, $app_type);
			}else{
				$slider_list = replace_domain($slider_list);
			}
            $return['center_slider'] = $slider_list;
        } else {
            $return['center_slider'] = array();
        }


        $config_data = D('Config_data')->get_config_data();
        // 小程序流量广告
        if($config_data['wxapp_adver_shop_index_status'] == 1 && $config_data['wxapp_adver_shop_index_unit_id']){
            $return['wxapp_adver'] = [
                'type' => 'banner',
                'id' => $config_data['wxapp_adver_shop_index_unit_id'],
            ];
        }

        /*$show_ad = $this->config['shop_main_page_show_ad'];
        $center_type = $this->config['shop_main_page_center_type'];
        $return['center_slider_type'] = $center_type;
        if ($show_ad) {
            $where['is_del'] = 0;
            $where['type'] = $center_type;
            $where['city_id'] = ['in', $this->config['now_city'].',0'];
            $slider_list = M('Shop_main_page_pic_slider')->field(true)->where($where)->order('sort DESC')->limit($center_type)->select();
            foreach ($slider_list as &$v) {
                $v['url'] = htmlspecialchars_decode($v['url']);
                $v['pic'] = file_domain() . '/upload/slider/' . $v['pic'];
                // 小程序跳转到别的小程序
                if ($this->DEVICE_ID == 'wxapp' && $v['wxapp_id']) {
                    $v['url'] = 'wxapp://' . $v['wxapp_id'];
                    if ($v['wxapp_page']) {
                        // 小程序页面
                        $v['url'] .= '://' . $v['wxapp_page'];
                    }
                    unset($v['wxapp_id'], $v['wxapp_page']);
                }
            }
            
			// app跳转到别的app
			if ($this->DEVICE_ID != 'wxapp') {
				$slider_list = D('Adver')->replace_url_to_app_page($slider_list, $app_type);
			}else{
				$slider_list = replace_domain($slider_list);
			}
            $return['center_slider'] = $slider_list;
        } else {
            $return['center_slider'] = array();
        }*/

        //首页中间轮播宽图
        $return['center_adver_list'] = D('Adver')->get_adver_by_key('wap_shop_index_center_slider_adver', 3, true);

        // 小程序跳转到别的小程序
        if ($_POST['Device-Id'] == 'wxapp') {
            $return['center_adver_list'] = D('Adver')->replace_url_to_wxapp_page($return['center_adver_list']);
        }
		// app跳转到别的app
		if ($this->DEVICE_ID != 'wxapp') {
			$return['center_adver_list'] = D('Adver')->replace_url_to_app_page($return['center_adver_list'], $app_type);
		}

        //为您优选
        $where = array('preference_status' => '1', 'order' => 'preference_sort', 'lat' => $lat, 'long' => $lng, 'page' => '1', 'limit' => 4, 'show_method' => 0);
        $lists = D('Merchant_store_shop')->get_list_by_option($where, 1, 0, 0, true);
        $return['preference_shop_list'] = array();
        if ($lists) {
            foreach ($lists['shop_list'] as $value) {
                $tmp_shop = array(
                    'store_id' => $value['store_id'],
                    'store_name' => htmlspecialchars_decode($value['name']),
                    'merchant_logo' => $value['merchant_logo'],
                    'preference_reason' => $value['preference_reason'],
                    'store_image' => $value['image'],
                    'shop_fitment_color' => $value['shop_fitment_color'] ? $value['shop_fitment_color'] : '113533', //背景色
                    'shop_fitment_front_color' => 'ffffff', //标题色
                );

                $return['preference_shop_list'][] = $tmp_shop;
            }
        }

        //爆款商品推荐
		$where = [
			'order' => 'recommend_sort',
			'lat'   => $lat,
			'long'  => $lng,
			'page'  => '1',
			'limit' => 6,
			'recommend_status' => '1',
			'is_business_open' => 1,
			'show_method'      => 0
		];
        $lists = D('Merchant_store_shop')->get_list_by_option($where, 1, 0, 0, true);
        $return['recommend_shop_list'] = array();
        if ($lists) {
            foreach ($lists['shop_list'] as $row) {
                $tmp_shop = array(
                    'store_id' => $row['store_id'],
                    'store_name' => htmlspecialchars_decode($row['name']),
                    'recommend_desc' => $row['recommend_desc'] ? $row['recommend_desc'] : $row['name'],
                    'merchant_logo' => $row['merchant_logo'],
                    'store_image' => $row['recommend_pic'] ? $row['recommend_pic'] : $row['image'],
                );
                // 查询推荐商品
                $rec_goods_list = D('Shop_goods')->get_list_by_option(['goods_ids'=>explode(',',$row['recommend_good_ids'])],3,1,false,1,true);
                // var_dump($rec_goods_list);
                $tmp_shop['goods_list'] = $rec_goods_list['goods_list'] ? $rec_goods_list['goods_list'] : [];
                foreach ($tmp_shop['goods_list'] as &$_goods) {
                    $_goods['fid'] = 0;
                    $_goods['ffid'] = 0;
                    $now_sort = D(_view('Shop_goods_sort'))->field(true)->where(['sort_id'=>$_goods['sort_id']])->find();
                    if ($now_sort['fid']) {
                        $_goods['fid'] = $now_sort['fid'];
                        $now_sort = D(_view('Shop_goods_sort'))->field(true)->where(['sort_id'=>$now_sort['fid']])->find();
                        if ($now_sort['fid']) { 
                            $_goods['ffid'] = $now_sort['fid'];
                        }
                    }
                }

                // 店铺优惠信息
                $tmp_shop['coupon_list'] = D('Merchant_store_shop')->get_store_coupon_list($row);

                $return['recommend_shop_list'][] = $tmp_shop;
                
            }
        }

        //分类、排序、筛选
        $_GET['is_return'] = 1;
        $categoryType = $this->getCategoryType();
        $return = array_merge($return, $categoryType);

        //天气
        if ($this->config['map_config'] != 'google') {
            $im = new im();
            $return['now_weather'] = $im->get_now_weather($return['city_info']['city_name']);
            if (!$return['now_weather']) {
                $return['now_weather'] = $this->getObj();
            }
        } else {
            $return['now_weather'] = $this->getObj();
        }

        //是否显示发现好菜
		$return['shop_index_find_good_goods_show'] = intval($this->config['shop_index_find_good_goods_show']);
        // 查看是否有商品，没有商品不展示
        $city_id = intval(C('config.now_shop_city')) ? : intval(C('config.now_city'));
        $city_id && $where['city_id'] = $city_id;
        // 查询列表
        $where['business_type'] = 'shop';
        $goods_list = D('Shop_goods')->get_goods_by_guess_like($where);
        if($goods_list['count']<=0){
            $return['shop_index_find_good_goods_show'] = '0';
        }

        //发现好菜标题
        $return['shop_index_find_good_goods_title'] = $this->config['shop_index_find_good_goods_title'] ? $this->config['shop_index_find_good_goods_title'] : L_('发现好菜');

		//发现好菜分类列表
		//$where = [];
        //$where['business_type'] = 'shop';
   		//$where['city_id'] =  $this->config['now_city'];
		//$sort_list = D('Shop_goods')->get_sort_by_guess_like($where);
        $sort_list = D('Diy_category')->get_normal_category(2,$this->config['now_city']);
		$return['shop_goods_sort_list'] = $sort_list;

        // 限时秒杀20210111
        $seckillInfo = D('Shop_seckill_category')->getBaseInfo($return['city_info']['city_id'], $this->_uid ?? 0);
        $return['shop_seckill_show'] = $seckillInfo['goods_list'] ? 1 : 0;
        $return['shop_seckill_title'] = $seckillInfo['title'];
        $return['shop_seckill_end_time'] = $seckillInfo['last_time']>time() ? $seckillInfo['last_time']-time() : 0;
        $return['shop_seckill_goods_list'] = $seckillInfo['goods_list'];

        $this->returnCode(0, $return);
    }

    // 发现好菜 推荐
	public function plat_recommend(){
		$config_data = D('Config_data')->get_index_guess_like();
		if(!$config_data){
			//$this->returnCodeOk(['count'=>0, 'page'=>'1', 'list'=>[]]);
		}
		
		$page = max(1,intval($_POST['page']));
		$pageSize = 10;
		$lat = getFormatNumber($_POST['lat']);
		$lng = getFormatNumber($_POST['lng']);
		$where['lat'] = $lat;
		$where['lng'] = $lng;
		// 城市
		$city_id = intval($_POST['now_shop_city']) ? intval($_POST['now_shop_city']) : intval($_POST['now_city']);
		$city_id && $where['city_id'] = $city_id;

		// 分类
		$_POST['sort_id'] && $where['sort_id'] = $_POST['sort_id'];

		// 查询列表
        $where['business_type'] = 'shop';
    	$goods_list = D('Shop_goods')->get_goods_by_guess_like($where);
    	$count = $goods_list['count'];
    	$list = $goods_list['goods_list'];

    	$return['count'] = ceil($count/$pageSize);
        $return['page'] = $page;
        $return['list'] = $list ? $list : array();
		$this->returnCodeOk($return);
	}
	
	//分类、排序、筛选
	public function getCategoryType(){
		$return['category_list'] = D('Shop_category')->lists(true,true);
		if(empty($return['category_list'])){
			$return['category_list'] = array();
		}else{
			foreach($return['category_list'] as &$cat_value){
				unset($cat_value['cat_id']);
				unset($cat_value['cat_fid']);
				unset($cat_value['cat_sort']);
				unset($cat_value['cat_status']);
				unset($cat_value['show_method']);
				unset($cat_value['cue_field']);
				if($cat_value['son_list']){
					foreach($cat_value['son_list'] as &$cat_v){
						unset($cat_v['cat_id']);
						unset($cat_v['cat_fid']);
						unset($cat_v['cat_sort']);
						unset($cat_v['cat_status']);
						unset($cat_v['show_method']);
						unset($cat_v['cue_field']);
					}
				}
			}
		}
		$return['sort_list'] = array(
			array(
				'name' => L_('智能排序'),
				'sort_url' => 'juli'
			),
			array(
				'name' => L_('销售数量最高'),
				'sort_url'=>'sale_count'
			),
			array(
				'name' => L_('离我最近'),
				'sort_url'=>'only_juli'
			),
			array(
				'name' => L_('起送价最低'),
				'sort_url' => 'basic_price'
			),
			array(
				'name' => L_('评分最高'),
				'sort_url' => 'score_mean'
			),
			array(
				'name' => L_('最新发布'),
				'sort_url' => 'create_time'
			)
		);
		
		if($this->config['shop_sort']){
			$first_sort = array();
			foreach($return['sort_list'] as $key=>$value){
				if($value['sort_url'] == $this->config['shop_sort']){
					$first_sort = $value;
					unset($return['sort_list'][$key]);
					break;
				}
			}
			if($first_sort){
				array_unshift($return['sort_list'],$first_sort);
			}
		}
		
		$return['type_list'] = array(
			/*array(
				'name' => L_('全部'),
				'type_url' => 'all'
			),*/
			array(
				'name' => L_('配送'),
				'type_url' => 'delivery'
			),
			array(
				'name' => L_('自提'),
				'type_url' => 'pick'
			)
		);
        $return['type_list_title'] = L_('配送方式');
		//优惠信息
        $return['special_list'] = [
			['name' => L_('免配送费'), 'special_url' => 'mpsf'],
            ['name' => L_('进店领券'), 'special_url' => 'jdlq'],
            ['name' => L_('满减优惠'), 'special_url' => 'mjyh'],
            ['name' => L_('首单立减'), 'special_url' => 'sdlj'],
            ['name' => L_('折扣商品'), 'special_url' => 'zksp'],
            ['name' => L_('0元起送'), 'special_url' => 'lyqs'],
            ['name' => L_('品牌商家'), 'special_url' => 'ppsj'],
        ];
        $return['special_list_title'] = L('优惠信息');
		if($_GET['is_return']){
			return $return;
		}else{
			$this->returnCode(0,$return);
		}
	}

	/**
	 * 首页设置不喜欢
	 */
	public function userSetDislike()
	{
		$type = $_POST['type'];
		$storeId = intval($_POST['store_id']);
		$activityId = intval($_POST['activity_id']);
		
		if(empty($this->_uid)){
			$this->returnCode(1002,[],'请先登录！');
		}
		if(empty($type) || (empty($storeId) && empty($activityId))){
			$this->returnCode(1001,[],'设置参数缺失！');
		}
		
		$cacheKey = [
			'store' => sprintf('uid:%s,store_id:%s',$this->_uid, $storeId),
			'activity' => sprintf('uid:%s,store_id:%s,activity_id:%s',$this->_uid, $storeId, $activityId)
		];
		if(empty($cacheKey[$type])){
			$this->returnCode(1001,[],'设置类型错误！');
		}
		
		cache($cacheKey[$type], 1, intval(C('config.dislike_day'))*3600);

		$this->returnCode(0, [],'');
	}
	
	public function getStoreShow($store)
	{
		//copy from ajax_list()
		$condition['store_id'] = $store['store_id'];
		$today = date('Ymd');
		$shopShowGoods = [
			'goods_list' => [],
			'goods_count' => 0
		];
		$subject_info = M('Merchant_store_shop_data')->where($condition)->find();
		if(empty($subject_info)){
			return $shopShowGoods;
		}
		//爆款橱窗是否展示
		$where_goods = array(
			'store_id'	=> $store['store_id'],
			'goods_ids'	=> explode(',',$subject_info['showcase_good_id']),
			'uid' => $this->_uid ?? 0
		);
		$good_list = D('Shop_goods')->get_list_by_option($where_goods, 1, 1, false, 1, true);
		if(empty($good_list['goods_list'])){
			$good_list['goods_list'] = array();
		}else{
			$tempGoodList = array();
			foreach($good_list['goods_list'] as $tmp_good_value){
				$tempGoodList[$tmp_good_value['goods_id']] = $tmp_good_value;
			}
			$good_list['goods_list'] = array();
			foreach($where_goods['goods_ids'] as $goods_id){
				if($tempGoodList[$goods_id]){
					$good_list['goods_list'][] = $tempGoodList[$goods_id];
				}
			}
			foreach($good_list['goods_list'] as &$good_value){
				$good_value['sell_day'] = $store['stock_type'] ? $today : $good_value['sell_day'];
				if ($good_value['is_seckill_price']) {
					$good_value['stock'] = $good_value['seckill_stock'];
				} else {
					if ($today != $good_value['sell_day']) {
						if($good_value['sell_type']==2){
							$good_value['stock'] = $good_value['original_stock'];
						}
					}
				}
				$good_value['product_sale'] = $good_value['sell_count'] + $good_value['shop_virtual_sales'];
				$good_value['product_id']	= $good_value['goods_id'];
				$good_value['product_name']	= $good_value['name'];
				$good_value['product_image']= thumb_img($good_value['image'], 240, 240, 'fill');
				$good_value['image'] = $good_value['product_image'];
				$good_value['product_price']= $good_value['price'];
				$good_value['has_format']   = ($good_value['spec_value'] || $good_value['is_properties']) ? true : false;
				$good_value['has_spec']   = $good_value['spec_value'] ? true : false;
			}
		}

		$shopShowGoods['goods_list'] = $good_list['goods_list'];
		$shopShowGoods['goods_count'] = count($good_list['goods_list']);
		
		return $shopShowGoods;
	}
	
	/*获取店铺列表*/
	public function ajax_list(){ 
		$key = isset($_POST['key']) ? addslashes($_POST['key']) : '';
		$cat_url = isset($_POST['cat_url']) ? htmlspecialchars($_POST['cat_url']) : 'all';
		$order = isset($_POST['sort_url']) ? htmlspecialchars($_POST['sort_url']) : 'juli';
		$deliver_type = isset($_POST['type_url']) ? htmlspecialchars($_POST['type_url']) : 'all';
		$lat = isset($_POST['lat']) ? floatval(htmlspecialchars($_POST['lat'])) : 0;
        $long = isset($_POST['lng']) ? floatval(htmlspecialchars($_POST['lng'])) : (isset($_POST['long']) ? floatval(htmlspecialchars($_POST['long'])) : 0);
		$is_search = $_POST['is_search'] ? 1 : 0;
        $pageSize = intval($_POST['pageSize']) ?: intval($_POST['page_size']);
		$search_type = isset($_POST['search_type']) ? htmlspecialchars($_POST['search_type']) : '';
		$store_ids = isset($_POST['store_ids']) ? $_POST['store_ids'] : array();
        $specialUrl = isset($_POST['special_url']) ? htmlspecialchars($_POST['special_url']) : '';
        $limit = isset($_POST['limit']) ? $_POST['limit'] : 0;

        //商家入驻缴费开关
		$merchant_settle_in = C('customization.merchant_settle_in');
		
		//直接进入到快店分类页，可能会没有携带经纬度，在用户登录情况下尽量优化一下
		if($cat_url && !$lat && !$long && $this->_uid){
			$now_user = D('User')->get_user($this->_uid);
			if($now_user['openid']){
				$user_long_lat = D('User_long_lat')->getLocation($now_user['openid'], 0);
				if($user_long_lat){
					$long = $user_long_lat['long'];
					$lat = $user_long_lat['lat'];
				}
			}
		}
		//如果没有传递lat和long，则取当前城市的经纬度
		if ($cat_url && $lat === 0 && $long === 0) {
			$cityId = C('config.now_shop_city') ? C('config.now_shop_city') : C('config.now_city');
			$nowCity = M('Area')->where(['area_id' => $cityId, 'is_open' => 1])->find();
			if ($nowCity) {
				$lat = $nowCity['area_lat'];
				$long = $nowCity['area_lng'];
			}
		}
		
		foreach($store_ids as $id_k=>$id_v){
			$store_ids[$id_k] = intval($id_v);
		}
		cookie('userLocationLong', $long);
		cookie('userLocationLat', $lat);
		$page = isset($_POST['page']) ? intval($_POST['page']) : 1;
		$page = max(1, $page);
		$cat_id = 0;
		$cat_fid = 0;
        $village_id = $_POST['village_id'] + 0;
		if($_POST['village_id']){
			$now_village =  D('House_village')->get_one($_POST['village_id']);
			$lat = $now_village['lat'];
			$long = $now_village['long'];
		}
		if ($cat_url != 'all') {
			$now_category = D('Shop_category')->get_category_by_catUrl($cat_url);
			if ($now_category) {
				if ($now_category['cat_fid']) {
					$cat_id = $now_category['cat_id'];
					$cat_fid = $now_category['cat_fid'];
				} else {
					$cat_id = 0;
					$cat_fid = $now_category['cat_id'];
				}
			}
		} else {
			$today = date('Y-m-d');
			$time = date('H:i:s');
			$week = date('w');
			$where['_string'] = sprintf("cat_status = 1 AND cat_fid > 0 AND (effect_date_type = 1 OR (start_date <= '%s' AND end_date >= '%s' AND FIND_IN_SET(%d,week))) AND (effect_time_type = 1 OR (start_time <= '%s' AND end_time >= '%s'))", $today, $today, $week, $time, $time);
			$catIds = D('Shop_category')->where($where)->getField('cat_id', true);
		}

		$where = array('deliver_type' => $deliver_type, 'order' => $order, 'lat' => $lat, 'long' => $long, 'cat_id' => $cat_id, 'cat_fid' => $cat_fid, 'page' => $page, 'search_type'=>$search_type,'special' => $specialUrl,'cat_ids'=>$catIds);
		$key && $where['key'] = $key;
		$store_ids && $where['store_ids'] = $store_ids;
        $limit && $where['limit'] = $limit;
        
		$where['goods_num_limit'] = $_POST['goods_num_limit'] ?: 0;
		
		$merchantStoreShopDB = D('Merchant_store_shop');
		
        // 如果存在社区id ,获取社区关联商户id, 加入查询条件中
        if ($village_id) {
            $where['village_id'] = $village_id;
            $lists = D('House_village_store')->get_list_by_option($where);
        } else {
            $lists = $merchantStoreShopDB->get_list_by_option($where,1,$is_search,$pageSize,true);
        }
		
		$return = array();
		$now_time = date('H:i:s');
		if($_POST['hot_keywords'] && is_string($_POST['hot_keywords'])){
			$_POST['hot_keywords'] = explode(' ',$_POST['hot_keywords']);
		}
		$hot_keywords = is_array($_POST['hot_keywords']) ? $_POST['hot_keywords'] : array();
		$hot_keywords_time_unix = $_POST['hot_keywords_time'] ? $_POST['hot_keywords_time'] : time();
		$hot_keywords_time_hour = date('G',$hot_keywords_time_unix);
		$now_time_hour = date('G');
		if(($hot_keywords_time_hour >= 4 && $hot_keywords_time_hour < 10) && ($now_time_hour >= 10 || $now_time_hour < 4)){	//早饭
			$hot_keywords = array();
		}else if(($hot_keywords_time_hour >= 10 && $hot_keywords_time_hour < 14) && ($now_time_hour >= 14 || $now_time_hour < 10)){	//午饭
			$hot_keywords = array();
		}else if(($hot_keywords_time_hour >= 14 && $hot_keywords_time_hour < 17) && ($now_time_hour >= 17 || $now_time_hour < 14)){	//下午茶
			$hot_keywords = array();
		}else if(($hot_keywords_time_hour >= 17 && $hot_keywords_time_hour < 4) && ($now_time_hour >= 4 && $now_time_hour < 17)){	//晚饭+夜宵
			$hot_keywords = array();
		}
		/*获取自营商家下的店铺*/
		if(C('config.open_kefu_url') && C('config.kefu_mer_id')>0){
			$store_arr=M('Merchant_store')->where(array('mer_id'=>C('config.kefu_mer_id')))->getField('store_id',true);
		}

        // 店铺分类
        $database_shop_category = D('Shop_category');
        $category_list = $database_shop_category->lists();
		foreach ($lists['shop_list'] as $row) {
			if(!empty($this->_uid)){
				$key = sprintf('uid:%s,store_id:%s',$this->_uid,$row['store_id']);
				if(cache($key)){//用户设置为不喜欢，暂不显示
					continue;
				}
			}
			
			$temp = array();
			$temp['store_id'] = $row['store_id'];
			//店铺推荐商品
			$temp['shop_show_goods'] = $this->getStoreShow($row);
			
            $temp['jdlq'] = $row['jdlq'];
			if(is_array($store_arr) && in_array($temp['store_id'], $store_arr)){
				$temp['is_self']=1;
			}else{
				$temp['is_self']=0;
			}
			$temp['name'] = html_entity_decode($row['name'], ENT_QUOTES);
			$temp['store_theme'] = $row['store_theme'];
			$temp['isverify'] = $row['isverify'];
			$temp['extra_price'] = $row['extra_price'];
			$temp['juli_wx'] = $row['juli'];
			$temp['range'] = $row['range'];
			$temp['image'] = $row['image'];
			$temp['permoney'] = $row['permoney'];
			if($row['logo']){
				$temp['image'] = replace_file_domain($row['logo']);
			}
			$temp['image'] = thumb_img($temp['image'],180,120,'fill');
			
			$temp['star'] = number_format($row['score_mean'],1);
			if($temp['star'] == '0.0'){
				$temp['star'] = '5.0';
			}
            $temp['month_sale_count'] = $row['sale_count'] + $row['virtual_sale_count'];
			$temp['shop_fitment_color'] = $row['shop_fitment_color'] ? $row['shop_fitment_color'] : '113533';			//背景色
			$temp['shop_fitment_front_color'] = 'ffffff';		//标题色
			$temp['is_new'] = ($row['create_time'] + 864000) < time() ? 0 : 1;
			$temp['delivery'] = $deliver_type == 'pick' ? 0 : $row['deliver'];//是否支持配送
			$temp['delivery'] = $temp['delivery'] ? true : false;
			$temp['delivery_time'] = $row['send_time'];//配送时长
			$temp['send_time_type'] = $row['send_time_type'];
			$temp['delivery_time_type'] = $this->send_time_type[$row['send_time_type']];
			$temp['delivery_price'] = getFormatNumber($row['basic_price']);//起送价
			$temp['delivery_money'] = getFormatNumber($row['delivery_fee']) ?: '0';//配送费
			$temp['delivery_system'] = $row['deliver_type'] == 0 || $row['deliver_type'] == 3 ? true : false;//是否是平台配送
			$temp['is_close'] = 1;
			$temp['deliver_type'] = $row['deliver_type'];
			if ($merchantStoreShopDB->checkTime($row)) {
			    $temp['is_close'] = 0;
			}
			$temp['is_close'] = $temp['is_close'] ? $temp['is_close'] : $row['is_close'];
			$temp['time'] = $merchantStoreShopDB->getBuniessName($row);

			$temp['check_age_object'] = array();
		    $temp['check_age_object']['age_tips_status0'] = 0;//0=正常 1=重新输入生日 2=未达到法定年龄
		    $temp['check_age_object']['age_tips0'] = L_('澳洲法律规定X1岁以下的未成年人不能抽烟、喝酒，如果要购买烟酒类商品必须出示身份证件证明自己已满X1周岁。',$row['age_limit_buy']);
		    $temp['check_age_object']['age_tips_title0'] = L_('您已达到购买烟酒产品法定年龄');
		    $temp['check_age_object']['age_tips_status1'] = 1;//0=正常 1=重新输入生日 2=未达到法定年龄
		    $temp['check_age_object']['age_tips1'] = L_('您购买的是烟酒类产品，所以我们需要确认您的年龄需满X1周岁或者以上。在递送时，您有可能被要求出示相关证件。',$row['age_limit_buy']);
		    $temp['check_age_object']['age_tips_title1'] = L_('请输入您的生日信息');
		    $temp['check_age_object']['age_tips_status2'] = 2;//0=正常 1=重新输入生日 2=未达到法定年龄
		    $temp['check_age_object']['age_tips2'] = L_('澳洲法律规定X1岁以下的未成年人不能抽烟、喝酒，如果要购买烟酒类商品必须出示身份证件证明自己已满X1周岁。',$row['age_limit_buy']);
		    $temp['check_age_object']['age_tips_title2'] = L_('您未达到购买烟酒产品法定年龄');
		    $temp['check_age_object']['age_limit_buy'] = $row['age_limit_buy'];
		    $temp['check_age_object']['check_age'] = false;//是否判断用户年龄
		    if($row['age_limit_buy_switch'] == 1 && $row['age_limit_buy'] > 0){ // 1=开启验证
		    	$temp['check_age_object']['check_age'] = true;
		    }
			
			if($is_search){
				$temp['goods_list'] = $row['goods_list'] ? $row['goods_list'] : array();
				if (C('customization.life_tools') && !empty($temp['goods_list'])) {
					foreach ($temp['goods_list'] as $gk => $gv) {
						$mer_id = D('Merchant_store')->where(['store_id' => $gv['store_id']])->getField('mer_id');
						$setEmployeeCardMerId = C('config.set_employee_card_mer_id') ? explode(',',C('config.set_employee_card_mer_id')) : [];
						if (in_array($mer_id,$setEmployeeCardMerId) && $gv['open_employee_card'] == 1) { //已开启开启员工专卖商品，且用户标签不对应的不能购买
							$can_buy = 0;
							if ($this->_uid && $gv['employee_lables']) {
								$employee_card_user = D('Employee_card_user')->where(['uid' => $this->_uid, 'mer_id' => $gv['employee_mer_id'], 'status' => 1])->field('lable_ids')->find();
								if (!empty($employee_card_user['lable_ids'])) {
									$lable_ids = explode(',', $employee_card_user['lable_ids']);
									$goods_lable_ids = explode(',', $gv['employee_lables']);
									if (array_intersect($lable_ids, $goods_lable_ids)) { //存在交集可以购买
										$can_buy = 1;
									}
								}
							}
							if ($can_buy == 0) {
								unset($temp['goods_list'][$gk]);
							}
						}
                        $temp['goods_list'][$gk]['sell_count'] =  $gv['sell_count'] + $gv['shop_virtual_sales'];
						$setEmployeeCardMerId = C('config.set_employee_card_mer_id') ? explode(',',C('config.set_employee_card_mer_id')) : [];
                        if(in_array($mer_id,$setEmployeeCardMerId)){
                            unset($temp['goods_list'][$gk]);
                        }
					}
					$temp['goods_list'] = array_values($temp['goods_list']);
                } else {
                    $temp['goods_list'] = array_map(function ($r) {
                        $r['sell_count'] =  $r['sell_count'] + $r['shop_virtual_sales'];
                        return $r;
                    }, $temp['goods_list']);
                }
			}
			
			if($where['key']){
				$temp['name_arr'] = $row['name_arr'];
			}

			$temp['coupon_list'] = array();
			if ($row['is_invoice']) {
				$temp['coupon_list']['invoice'] = floatval($row['invoice_price']);
			}
			if ($row['isDiscountGoods']) {
			    $temp['coupon_list']['isdiscountgoods'] = 1;
			}
			if ($row['isdiscountsort']) {//店铺的商品分类折扣
			    $temp['coupon_list']['isdiscountsort'] = 1;
			}
			if ($row['store_discount'] != 0 && $row['store_discount'] != 100) {
				$temp['coupon_list']['discount'] = $row['store_discount']/10;
			}
			$system_delivery = array();
			foreach ($row['system_discount'] as $row_d) { 
				if ($row_d['type'] == 0) {//新单
					$temp['coupon_list']['system_newuser'][] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']),'order_count'=>$row_d['order_count'],'percentage_status'=>$row_d['percentage_status'],'usage_scenario'=>$row_d['usage_scenario']);
				} elseif ($row_d['type'] == 1) {//满减
					$temp['coupon_list']['system_minus'][] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']),'order_count'=>$row_d['order_count'],'percentage_status'=>$row_d['percentage_status'],'usage_scenario'=>$row_d['usage_scenario']);
				} elseif ($row_d['type'] == 2) {//配送
					if ($row_d['full_money'] >= 0 && $row_d['reduce_money'] > 0) {
						//$system_delivery[] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']),'order_count'=>$row_d['order_count'],'percentage_status'=>$row_d['percentage_status']);
						$temp['coupon_list']['system_minus'][] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']),'order_count'=>$row_d['order_count'],'percentage_status'=>$row_d['percentage_status'],'type'=>$row_d['type'],'usage_scenario'=>$row_d['usage_scenario']);
					}
				}
			}
			
			foreach ($row['merchant_discount'] as $row_m) {
				if ($row_m['type'] == 0) {
					$temp['coupon_list']['newuser'][] = array('money' => floatval($row_m['full_money']), 'minus' => floatval($row_m['reduce_money']));
				} elseif ($row_m['type'] == 1) {
					$temp['coupon_list']['minus'][] = array('money' => floatval($row_m['full_money']), 'minus' => floatval($row_m['reduce_money']));
				}
			}

			if ($row['deliver']) {
				if ($temp['delivery_system']) {
					$system_delivery && $temp['coupon_list']['delivery'] = $system_delivery;
				} else {
					if ($row['is_have_two_time']) {
						if ($row['reach_delivery_fee_type2'] == 0) {
							if ($row['basic_price'] > 0 && $row['delivery_fee2'] > 0) {
								$temp['coupon_list']['delivery'][] = array('money' => floatval($row['basic_price']), 'minus' => floatval($row['delivery_fee2']));
							}
						} elseif ($row['reach_delivery_fee_type2'] == 1) {
							//$temp['coupon_list']['delivery'] = array('money' => false, 'minus' => $row['delivery_fee']);
						} elseif ($row['reach_delivery_fee_type2'] == 2) {
							$row['delivery_fee2'] && $temp['coupon_list']['delivery'][] = array('money' => floatval($row['no_delivery_fee_value2']), 'minus' => floatval($row['delivery_fee2']));
						}
					} else {
						if ($row['reach_delivery_fee_type'] == 0) {
							if ($row['basic_price'] > 0 && $row['delivery_fee'] > 0) {
								$temp['coupon_list']['delivery'][] = array('money' => floatval($row['basic_price']), 'minus' => floatval($row['delivery_fee']));
							}
						} elseif ($row['reach_delivery_fee_type'] == 1) {
							//$temp['coupon_list']['delivery'] = array('money' => false, 'minus' => $row['delivery_fee']);
						} elseif ($row['reach_delivery_fee_type'] == 2) {
							$row['delivery_fee'] && $temp['coupon_list']['delivery'][] = array('money' => floatval($row['no_delivery_fee_value']), 'minus' => floatval($row['delivery_fee']));
						}
					}
				}

                // 平台免配送费设置
                if ($row['deliver_discount']) {
                    $temp['coupon_list']['delivery_free'] = $row['deliver_discount'];
                    $temp['deliver_discount'] = $row['deliver_discount'];
                }
			}
			$temp['coupon_count'] = count($temp['coupon_list']);
			//dump($temp['coupon_list']);exit;
			$temp['coupon_list'] = $this->simpleParseCoupon($temp['coupon_list'],'array');

			//增加满赠活动
            $activity = D('Store_full_gift_activity')->getCurrentActivityByStoreId($row['store_id']);
            if ($activity) {
                $setting = D('Store_full_gift_activity')->getSettingByActivityId($activity['id']);
                if ($setting) {
                    if($setting[0]['full_price'] > 0){
                        array_push($temp['coupon_list'], ['type' => 'full_gift', 'text' => L_('满') . C('config.Currency_symbol') . $setting[0]['full_price'] . L_('得赠品')]);
                    }else{
                        array_push($temp['coupon_list'], ['type' => 'full_gift', 'text' => L_('下单得赠品')]);
                    }
                }
            }

			$temp['deliver_name'] = isset($this->config['deliver_name']) && !empty($this->config['deliver_name']) ? $this->config['deliver_name'] : L_('平台配送');

            // 显示描述 所属分类
            if ($row['preference_desc']) {
                // 优选 主推描述
                $temp['preference_desc'] = $row['preference_desc'];
            }else{
                // 前三子分类
                $condition_shop_category['store_id'] = $row['store_id'];
                $relation_ids = array_column(D('Shop_category_relation')->field(true)->where($condition_shop_category)->select(), 'cat_id') ;
                $category_arr = array();
                $index = 0;
                foreach ($category_list as $_cate) {
                    if ($index>=3) {
                        break;
                    }
                    foreach ($_cate['son_list'] as $_sub_cate) {
                        if ($index>=3) {
                            break;
                        }
                        if (in_array($_sub_cate['cat_id'], $relation_ids)) {
                            $index ++;
                            $category_arr[] = $_sub_cate['cat_name'];
                        }
                    }
                }
                $temp['category_arr'] = $category_arr;
            }
            //相似类别的跳转URL
			$shopCategory = D('Shop_category_relation')
				->alias('r')
				->join('INNER JOIN '.C('DB_PREFIX').'shop_category c ON r.cat_fid = c.cat_id')
				->field('c.cat_url')
				->where(['store_id' => $row['store_id']])
				->find();
            
			$temp['similar_cate_url'] = $shopCategory['cat_url'] ? C('config.site_url')."/packapp/plat/pages/shop_new/shopType/shopType?cat_url={$shopCategory['cat_url']}" : '';
            $temp['next_date']  = (object)array();//下一阶段营业时间
			$temp['is_advance'] = false;

			if($temp['is_close']){
				$next_date = D('Merchant_store_open_time')->get_next_time($temp['store_id']);
				$temp['next_date'] = $next_date;
				if($next_date['type']<>3){
					$temp['is_advance'] = true;
				}	
				if(M('Merchant_store_shop')->where(['store_id' => $temp['store_id']])->getField('is_close')){
					$temp['is_advance'] = false;
				}
			}
            $temp['current_liveshow_id'] = $row['current_liveshow_id'];
            $temp['url'] = C('config.site_url').'/wap.php?g=Wap&c=Shop&a=index&shop-id='.$row['store_id'];

            if($merchant_settle_in == 1){
                //商家入驻缴费标志 
                $temp['settle_in_sign'] =  $row['settle_in_money'] > 0 ? $row['settle_in_sign'] : '';
                unset($row['settle_in_money']);
            }
			$temp['good_goods_list'] = [];
            //商品列表推荐商品（只显示3条）
            if($row['good_goods_show']){
            	$store_shop_data = D('Merchant_store_shop_data')->where(['store_id'=>$row['store_id']])->getField('good_goods_ids');
            	if($store_shop_data){
					// 查询推荐商品
					$rec_goods_list = D('Shop_goods')->get_list_by_option(['goods_ids'=>explode(',',$store_shop_data),'page_size'=>3],3,1,false,1,false);
				}else{
					$rec_goods_list = D('Shop_goods')->get_list_by_option(['store_id'=>$row['store_id'],'page_size'=>3],1,1,false,1,false);
				}
				$temp['good_goods_list'] = $rec_goods_list?$rec_goods_list['goods_list']:[];
				if ($temp['good_goods_list']) {
					$temp['good_goods_list'] = array_map(function ($r) {
						$r['image'] = $r['image'] ? thumb_img($r['image'], 240, 240, 'fill') : '';
						return $r;
					}, $temp['good_goods_list']);
				}
			}
			
			$return[] = $temp;
			
			//热门关键词
			$tmp_key = explode(' ',$row['search_keywords']);
			if(!empty($tmp_key)){
				foreach($tmp_key as $key=>$value){
					if(empty($value)){
						unset($tmp_key[$key]);
					}
				}
			}
			if(!empty($tmp_key)){
				$hot_keywords = array_unique(array_merge($hot_keywords,$tmp_key));
			}
		}

		// 活动
		if ($page == 1 && !$is_search) {
   			// 活动
	        $condition_store = [];
	        $condition_store['business_type'] = 'shop';
       		$condition_store['city_id'] = C('config.now_shop_city') ? C('config.now_shop_city') : C('config.now_city');
			$condition_store['deliver_type'] = $deliver_type;
			$condition_store['cat_id'] = $cat_id;
			$condition_store['cat_fid'] = $cat_fid;
			$condition_store['search_type'] = $search_type;
			$condition_store['special'] = $specialUrl;
			$condition_store['uid'] = $this->_uid;
   			$shop_list = D('Merchant_store_shop')->get_store_by_guess_like($condition_store,$lat,$long);
   			$activity_list = $shop_list['shop_list'];
   		}

		$array = [
			'store_list' => $return, 
			'activity_list' => $activity_list ?? array(), 
			'hot_keywords' => $hot_keywords ? array_values($hot_keywords) : [], 
			'hot_keywords_time' => time() , 
			'has_more' => $lists['has_more'] ? true : false
		];
        
        if($_POST['data_is_return']){
            return $array;
        }else{
            $this->returnCode(0,$array);
        }
    }

	public function simpleParseCoupon($obj){
		
		$returnObj = array();
        foreach ($obj as $key => $value) {
            if ($key == 'invoice') {
				$returnObj[] = array(
					'type' => 'invoice',
					'text' => L_('票'),
				);
            } elseif ($key == 'discount') {
				$returnObj[] = array(
					'type' => 'discount',
					'text' => L_('全场X1折', $obj[$key]),
				);
            } elseif ($key == 'isdiscountgoods') {
				$returnObj[] = array(
					'type' => 'isdiscountgoods',
					'text' => L_('限时优惠'),
				);
            } elseif ($key == 'isdiscountsort') {
				$returnObj[] = array(
					'type' => 'isdiscountsort',
					'text' => L_('折扣优惠'),
				);
            } elseif ($key == 'delivery_free') {
                $Currency_symbol = C('config.Currency_symbol');
                if ($value['money']>0) {
                    $d_text = L_('满'.$Currency_symbol.'X1免配送费',$value['money']);
                }else{
                    $d_text = L_('免配送费');
                }
                $returnObj[] = array(
                    'type' => 'delivery_free',
                    'text' => $d_text,
                );
            } else {
                foreach ($obj[$key] as $k => $v) {
                	if ($v['percentage_status']==1) {
						if($obj[$key][$k]['money']>0){
							$dis_text = '享X2折';
						}else{
							$dis_text = 'X2折';
						}
                        if($v['type'] == 2){
                            $dis_text = L_('配送费').$dis_text;
                        }
                        $dis_num = round($v['minus']*10,2);
                    }elseif($v['type']==2){
                        $dis_text = '减'.C('config.Currency_symbol').'X2配送费';
                        $dis_num = round($v['minus'],2);
                    }else{
                        $dis_text = '减'.C('config.Currency_symbol').'X2';
                        $dis_num = round($v['minus'],2);
                    }

                    if($key == 'delivery'){
                    	if($obj[$key][$k]['order_count'] >= 1){
	                        $returnObj[] = array(
								'type' => 'delivery',
								'text' => L_('平台首X0单满'.C('config.Currency_symbol').'X1'.$dis_text.'配送费',array('X0'=>$obj[$key][$k]['order_count'],'X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
							);
                    	}elseif($obj[$key][$k]['order_count'] == 1){
	                        $returnObj[] = array(
								'type' => 'delivery',
								'text' => L_('平台首单满'.C('config.Currency_symbol').'X1'.$dis_text.'配送费',array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
							);
                    	}else{
							if($obj[$key][$k]['order_count'] >0){
								$returnObj[] = array(
									'type' => 'delivery',
									'text' => $obj[$key][$k]['money'] > 0 ? L_('满'.C('config.Currency_symbol').'X1'.$dis_text.'配送费',array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)) : L_($dis_text.'配送费',array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
								);
							}else{
								$returnObj[] = array(
									'type' => 'delivery',
                                    'text' => $obj[$key][$k]['money'] > 0 ? L_('满'.C('config.Currency_symbol').'X1'.$dis_text.'配送费',array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)) : L_($dis_text.'配送费',array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
								);
							}
                    	}
                    } else if($key == 'system_newuser'){
                        if($obj[$key][$k]['order_count'] == 1){
                            $returnObj[] = array(
                                'type' => 'system_newuser',
                                'text' => L_('平台首单满'.C('config.Currency_symbol').'X1'.$dis_text,array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
                            );
                        }else{
                            $returnObj[] = array(
                                'type' => 'system_newuser',
                                'text' => L_('平台首X0单满'.C('config.Currency_symbol').'X1'.$dis_text,array('X0'=>$obj[$key][$k]['order_count'],'X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
                            );
                        }
                    } else if($key == 'newuser'){
                    	if($obj[$key][$k]['order_count'] == 1){
                            if ($obj[$key][$k]['money'] > 0) {
                                $returnObj[] = array(
                                    'type' => 'newuser',
                                    'text' => L_('店铺首单满' . C('config.Currency_symbol') . 'X1' . $dis_text, array('X1' => $obj[$key][$k]['money'], 'X2' => $dis_num)),
                                );
                            } else {
                                $returnObj[] = array(
                                    'type' => 'newuser',
                                    'text' => L_('店铺首单' . $dis_text, array('X2' => $dis_num)),
                                );
                            }
                        }else{
                        	if($obj[$key][$k]['order_count']){
		                        $returnObj[] = array(
									'type' => 'newuser',
									'text' => L_('店铺首X0单满'.C('config.Currency_symbol').'X1'.$dis_text,array('X0'=>$obj[$key][$k]['order_count'],'X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
								);
		                    }
		                    else{
		                        if($obj[$key][$k]['money'] > 0){
                                    $returnObj[] = array(
                                        'type' => 'newuser',
                                        'text' => L_('店铺首单满'.C('config.Currency_symbol').'X1'.$dis_text,array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
                                    );
                                }else{
                                    $returnObj[] = array(
                                        'type' => 'newuser',
                                        'text' => L_('店铺首单'.$dis_text,array('X2'=>$dis_num)),
                                    );
                                }
		                    }
                        }
                    } else {
						
                    	if($v['order_count'] > 1){
                    		$returnObj[] = array(
								'type' => 'minus',
								'text' => L_('首X0单满'.C('config.Currency_symbol').'X1'.$dis_text,array('X0'=>$obj[$key][$k]['order_count'],'X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
							);
                    	}elseif($v['order_count'] == 1){
							if ($obj[$key][$k]['money'] > 0) {
								$returnObj[] = array(
									'type' => 'minus',
									'text' => L_('首单满' . C('config.Currency_symbol') . 'X1' . $dis_text, array('X1' => $obj[$key][$k]['money'], 'X2' => $dis_num)),
								);
							} else {
								$returnObj[] = array(
									'type' => 'minus',
									'text' => L_('首单' . $dis_text, array('X2' => $dis_num)),
								);
							}


                    	}else{
							if($obj[$key][$k]['money']>0){
								if($v['usage_scenario'] == 2){
									$returnObj[] = array(
										'type' => 'minus',
										'text' => L_('自取满'.C('config.Currency_symbol').'X1'.$dis_text,array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
									);
								}else{
									$returnObj[] = array(
										'type' => 'minus',
										'text' => L_('满'.C('config.Currency_symbol').'X1'.$dis_text,array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
									);
								}
							}else{
								
								if($v['usage_scenario'] == 2){
									$returnObj[] = array(
										'type' => 'minus',
										'text' => L_('自取'.$dis_text,array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
									);
								}else{
									$returnObj[] = array(
										'type' => 'minus',
										'text' => L_($dis_text,array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num)),
									);
								}	
							}	
                    	}
                    }
                }
            }
        }
		return $returnObj;
	}

	/* 解析优惠券 */
	/* 解析优惠券 */
	public function parseCoupon($obj, $type)
    {	
        $returnObj = array();
        foreach ($obj as $key => $value) {
            if ($key == 'invoice') {
                $returnObj[$key] = L_('满X1支持开发票，请在下单时填写发票抬头',C('config.Currency_symbol') . $obj[$key]);
            } elseif ($key == 'discount') {
                $returnObj[$key] = L_('店内全场X1折',$obj[$key]);
            } elseif ($key == 'isdiscountgoods') {
                $returnObj[$key] = L_('店内有部分商品限时优惠');
            } elseif ($key == 'isdiscountsort') {
                $returnObj[$key] = L_('部分商品分类参与折扣优惠');
            } elseif ($key == 'delivery_free') {
                $Currency_symbol = C('config.Currency_symbol');
                if ($value['money']) {
                    $d_text = L_('满'.$Currency_symbol.'X1免配送费',$value['money']);
                }else{
                    $d_text = L_('免配送费');
                }
                $returnObj[$key] =  $d_text;
            } else {
                $returnObj[$key] = [];
                foreach ($obj[$key] as $k => $v) {
                	if ($v['percentage_status']==1) {
                        $dis_num = round($v['minus']*10,2);
                    }else{
                        $dis_num = C('Config.Currency_symbol').round($v['minus'],2);
                    }
                    if($key == 'delivery'){
                    	if($obj[$key][$k]['order_count'] > 1){
                    		if($v['percentage_status']==1){
		                        $returnObj[$key][] = L_('平台首X0单满X1配送享X2折',array('X0'=>$obj[$key][$k]['order_count'],'X1'=>C('Config.Currency_symbol').$obj[$key][$k]['money'],'X2'=>$dis_num));
		                    }
		                    else{
		                    	$returnObj[$key][] = L_('平台首X0单满X1配送减X2',array('X0'=>$obj[$key][$k]['order_count'],'X1'=>C('Config.Currency_symbol').$obj[$key][$k]['money'],'X2'=>$dis_num));
		                    }
                    	}elseif($obj[$key][$k]['order_count'] == 1){
                    		if($v['percentage_status']==1){
	                        	$returnObj[$key][] = L_('平台首单满X1配送享X2折',array('X1'=>C('Config.Currency_symbol').$obj[$key][$k]['money'],'X2'=>$dis_num));
	                        }else{
	                        	$returnObj[$key][] = L_('平台首单满X1配送减X2',array('X1'=>C('Config.Currency_symbol').$obj[$key][$k]['money'],'X2'=>$dis_num));
	                        }
                    	}else{
                            if ($obj[$key][$k]['order_count'] >= 0) {
                                if ($v['percentage_status'] == 1) {
                                    if ($obj[$key][$k]['money'] > 0) {
                                        $returnObj[$key][] = L_('满X1享X2折配送费', array('X1' => C('Config.Currency_symbol') . $obj[$key][$k]['money'], 'X2' => $dis_num));
                                    } else {
                                        $returnObj[$key][] = L_('享X2折配送费', array('X2' => $dis_num));
                                    }
                                } else {
                                    if ($obj[$key][$k]['money'] > 0) {
                                        $returnObj[$key][] = L_('满X1减X2配送费', array('X1' => C('Config.Currency_symbol') . $obj[$key][$k]['money'], 'X2' => $dis_num));
                                    } else {
                                        $returnObj[$key][] = L_('减X2配送费', array('X2' => $dis_num));
                                    }
                                }
                            }
                    	}
                    } else if($key == 'system_newuser'){
                        if($obj[$key][$k]['order_count'] == 1){
                        	if($v['percentage_status']==1){
                            	$returnObj[$key][] = L_('平台首单X1享X2折',array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num));
							} else{
                            	$returnObj[$key][] = L_('平台首单X1减X2',array('X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num));
                            }
                        }else{
                        	if($v['percentage_status']==1){
                           		$returnObj[$key][] = L_('平台首X0单X1享X2折',array('X0'=>$obj[$key][$k]['order_count'],'X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num));
                           	}else{
                           		$returnObj[$key][] = L_('平台首X0单X1减X2',array('X0'=>$obj[$key][$k]['order_count'],'X1'=>$obj[$key][$k]['money'],'X2'=>$dis_num));
                           	}
                        }
                    } else if($key == 'newuser'){
                    	if($obj[$key][$k]['order_count'] <= 1){
                    		if($v['percentage_status']==1){
		                        $returnObj[$key][] = L_('店铺首单满X1享X2折',array('X1'=>C('Config.Currency_symbol').$obj[$key][$k]['money'],'X2'=>$dis_num));
		                    }
		                    else{
		                        if($obj[$key][$k]['money'] > 0){
                                    $returnObj[$key][] = L_('店铺首单满X1减X2',array('X1'=>C('Config.Currency_symbol').$obj[$key][$k]['money'],'X2'=>$dis_num));
                                }else{
                                    $returnObj[$key][] = L_('店铺首单减X2',array('X2'=>$dis_num));
                                }
		                    }
                        }else{
                        	if($v['percentage_status']==1){
		                        $returnObj[$key][] = L_('店铺首X0单满X1享X2折',array('X0'=>$obj[$key][$k]['order_count'],'X1'=>C('Config.Currency_symbol').$obj[$key][$k]['money'],'X2'=>$dis_num));
		                    }
		                    else{
		                    	$returnObj[$key][] = L_('店铺首X0单满X1减X2',array('X0'=>$obj[$key][$k]['order_count'],'X1'=>C('Config.Currency_symbol').$obj[$key][$k]['money'],'X2'=>$dis_num));
		                    }
                        }
                    } else {
                    	if($obj[$key][$k]['order_count'] > 1){
                    		if($v['percentage_status']==1){
	                        	$returnObj[$key][] = L_('首X0单满X1享X2折',array('X0'=>$obj[$key][$k]['order_count'],'X1'=>C('Config.Currency_symbol').$obj[$key][$k]['money'],'X2'=>$dis_num));
	                        }
	                        else{
	                        	$returnObj[$key][] = L_('首X0单满X1减X2',array('X0'=>$obj[$key][$k]['order_count'],'X1'=>C('Config.Currency_symbol').$obj[$key][$k]['money'],'X2'=>$dis_num));
	                        }
                    	}elseif($obj[$key][$k]['order_count'] == 1){
                    		if($v['percentage_status']==1){
		                        $returnObj[$key][] = L_('首单满X1享X2折',array('X1'=>C('Config.Currency_symbol').$obj[$key][$k]['money'],'X2'=>$dis_num));
		                    }
		                    else{
		                    	$returnObj[$key][] = L_('首单满X1减X2',array('X1'=>C('Config.Currency_symbol').$obj[$key][$k]['money'],'X2'=>$dis_num));
		                    }
                    	}else{
							if($obj[$key][$k]['money'] > 0){
								if($v['percentage_status']==1){
									$returnObj[$key][] = L_('满X1享X2折',array('X1'=>C('Config.Currency_symbol').$obj[$key][$k]['money'],'X2'=>$dis_num));
								}
								else{
									$returnObj[$key][] = L_('满X1减X2',array('X1'=>C('Config.Currency_symbol').$obj[$key][$k]['money'],'X2'=>$dis_num));
								}
							}else{
								if($v['percentage_status']==1){
									$returnObj[$key][] = L_('享X1折',array('X1'=>$dis_num));
								}
								else{
									$returnObj[$key][] = L_('减X1',array('X1'=>$dis_num));
								}
							}	
                    	}
                    }

                    // if ($key == 'delivery') {
                    //     $returnObj[$key][] = L_('商品满X1,配送费'.$dis_text, array('X1' => $obj[$key][$k]['money'], 'X2' => $dis_num));
                    // } else if ($key == 'system_newuser') {
                    //     if ($obj[$key][$k]['order_count'] == 1) {
                    //         $returnObj[$key][] = L_('平台首单满X1'.$dis_text, array('X1' => $obj[$key][$k]['money'], 'X2' => $dis_num));
                    //     } else {
                    //         $returnObj[$key][] = L_('平台首X0单满X1'.$dis_text, array('X0' => $obj[$key][$k]['order_count'], 'X1' => $obj[$key][$k]['money'], 'X2' => $dis_num));
                    //     }
                    // } else {
                    //     $returnObj[$key][] = L_('满X1'.$dis_text, array('X1' => $obj[$key][$k]['money'], 'X2' => $dis_num));
                        
                    // }
                }
            }
        }

        $textObj = array();
        foreach ($returnObj as $key => $value) {
            if ($key == 'invoice' || $key == 'discount' || $key == 'isdiscountgoods' || $key == 'isdiscountsort' || $key == 'delivery_free') {
                $textObj[$key] = $value;
            } else {
                switch ($key) {
                    case 'system_newuser':
                        //$textObj[$key] = L_('平台首单X1' ,implode(',', $value));
                        $textObj[$key] = L_(implode(',', $value));
                        break;
                    case 'system_minus':
                        $textObj[$key] = L_('平台优惠').implode(',', $value);
                        break;
                    case 'newuser':
                        //$textObj[$key] = L_('店铺首单').implode(',', $value);
                        $textObj[$key] = L_(implode(',', $value));
                        break;
                    case 'minus':
                        $textObj[$key] = L_('店铺优惠').implode(',', $value);
                        break;
                    case 'system_minus':
                        $textObj[$key] = L_('平台优惠').implode(',', $value);
                        break;
                    case 'delivery':
                        $textObj[$key] = implode(',', $value);
                        break;
                }
            }
        }
        if ($type == 'text') {
            $tmpObj = array();
            foreach ($textObj as $key => $value) {
                $tmpObj[] = $value;
            }
            return implode(';', $tmpObj);
        } else {
            $returnObj = array();
            foreach ($textObj as $key => $value) {
                $returnObj[] = array(
                    'type' => $key,
                    'value' => $value
                );
            }
            return $returnObj;
        }
    }

	public function barcodeGetName(){
		if(empty($_POST['barcode'])){
			$this->returnCodeError(L_('请携带条形码值'));
		}
		$barcode = trim($_POST['barcode']);
		$where = array(
			'number' => $barcode
		);
        $nowGood = M('System_goods')->field('`name`')->where($where)->find();
		if(!$nowGood){
			$nowGood['name'] = $barcode;
		}
		$this->returnCodeOk($nowGood);
	}

	public function ajax_shop(){ 
		$store_id = $_GET['store_id'];
			
        if (empty($store_id)) {
            $store_id = $_POST['store_id'];
            if (empty($store_id)) {
              if($_POST['data_is_return']){
                $store['is_close'] = '1';
                return $store;
              }else{
                $this->returnCodeError(L_('请携带 store_id 参数'));
              }
            }
        }

        D('Card_new')->sync_tw_card($this->_uid,$store_id);

		$where = array('store_id' => $store_id);
		//$now_store = D(_view('Merchant_store'))->field(true)->where($where)->find();
		$now_store = D(_view('Merchant_store'))->where($where)->find();
		$now_shop = D(_view('Merchant_store_shop'))->field(true)->where($where)->find();
		$now_mer = M('Merchant')->field('isverify')->where(array('mer_id'=>$now_store['mer_id']))->find();
		if (empty($now_shop) || empty($now_store)) {
			if($_POST['data_is_return']){
				$store['is_close'] = '1';
				return $store;
			}else{
				$this->returnCodeError(L_('该X1暂未完善信息，无法使用',$this->config['shop_alias_name']));
			}
		}
		if ($now_store['status'] == 4) {
			$this->returnCodeError(L_('店铺不存在'));
		}
		if($this->_uid){
			$this->save_user_city($now_store['city_id'], $now_store['area_id']);
		}

		// 1/5的概率同步一下评价数字，防止几率性的不一致
		if(mt_rand(1,5) == 5){
			$replyWhere = [
				'order_type' => 3,
				'parent_id' => $now_store['store_id'],
				'status' => ['lt', 2],
			];
			if($this->config['open_shop_reply_check']){
				$replyWhere['check'] = 2;
			}
			$reply_count = M('Reply')->where($replyWhere)->count();
			if($now_shop['reply_count'] != $reply_count){
				D('Merchant_store_shop')->data(array('reply_count'=>$reply_count))->where($where)->save();
				$now_shop['reply_count'] = $reply_count;
			}
		}

		$auth_files = array();
		if ($now_store['auth'] == 3) {
			if (!empty($now_store['auth_files'])) {
				$auth_file_class = new auth_file();
				$tmp_pic_arr = explode(';', $now_store['auth_files']);
				foreach($tmp_pic_arr as $key => $value){
					$auth_file_temp = $auth_file_class->get_image_by_path($value);//array('title' => $value, 'url' => $auth_file_class->get_image_by_path($value, 's'));
					if($auth_file_temp['image']){
                        $auth_files[] = $auth_file_temp['image'];
                    }
				}
			}
		}
		$now_store['auth_files'] = $auth_files;
		

		//系统优惠
		$usage_scenario = -1;
		if ($now_shop['deliver_type'] == 2 || $now_shop['deliver_type'] == 4) {//自提
			$usage_scenario = 2;
		} else if ($now_shop['deliver_type'] == 0){//平台配送
			$usage_scenario = 1;
		} else if ($now_shop['deliver_type'] == 3){
			$usage_scenario = 0;
		} else if($now_shop['deliver_type'] == 1){
		    //商家配送
            $usage_scenario = 1;
        }
		if($usage_scenario != -1){
			$discounts = D('Shop_discount')->getDiscounts($now_store['mer_id'], $store_id, 1, $usage_scenario);
		}else{
			$discounts = [];
		}

		$row = array_merge($now_store, $now_shop);
        $res = D('Merchant_store')->get_store_deliver_info($row);

        $store = array();
        $store_image_class = new store_image();
        $images = $store_image_class->get_allImage_by_path($row['pic_info']);
		//顶部背景图片
		$store['head_bg_image'] = $now_store['head_bg_image'] ? file_domain().'/'.$now_store['head_bg_image'] : '';
        $store['id'] = $row['store_id'];
        $store['pack_type'] = $row['pack_type'];
        $store['pack_fee'] = $row['pack_fee'];
        $store['store_id'] = $row['store_id'];
        $store['is_business_open'] = $row['is_business_open'];
        $store['open_splicing_order'] = isset($row['open_splicing_order'])?intval($row['open_splicing_order']):0;

		$store['yuanjino2o_customized_func'] = 0;//C('customization.yuanjino2o_customized_func')?1:0;

        /*获取自营商家下的店铺*/
        if (C('config.open_kefu_url') && C('config.kefu_mer_id') > 0) {
            $store_arr = M('Merchant_store')->where(array('mer_id' => C('config.kefu_mer_id')))->getField('store_id', true);
        }
        if (is_array($store_arr) && in_array($store['store_id'], $store_arr)) {
            $store['is_self'] = 1;
        } else {
            $store['is_self'] = 0;
        }

        // 验证是否有三级分类
        $row['is_mult_class'] = D('Merchant_store_shop')->checkMultClass($row['store_id']);
		//如果店铺上线商品超过400个，则强制多级分类
		$saleGoodsCount = M('Shop_goods')->where(['store_id' => $row['store_id'], 'status' => 1])->count();
		if ($saleGoodsCount > 300) {
			$row['is_mult_class'] = 1;
		}

		$store['phone'] = trim($row['phone']);
		$store['long'] = $row['long'];
		$store['lng'] = $row['long'];
		$store['lat'] = $row['lat'];
		$store['isverify'] = $now_mer['isverify'];
		$store['store_theme'] = $row['store_theme'];
		$store['adress'] = $row['adress'];
		$store['close_reason'] = $row['close_reason'];
        $store['openclassfyindex'] = $row['is_mult_class'];
        $store['deliver_type'] = $row['deliver_type'];

        //直播地址
		$storeLiveUrls = json_decode($row['store_live_urls'], true);
        if (empty($storeLiveUrls)) {
            $store['store_live_is_show'] = false;
			$store['store_live_urls'] = [];
        } else {
            $store['store_live_is_show'] = true;
            foreach($storeLiveUrls as $k=>$v){
				$storeLiveUrls[$k]['live_pic'] = thumb_img($v['live_pic'],700,420,'fill');
			}
			$store['store_live_urls'] = $storeLiveUrls;
        }
		//背景色
		$store['shop_fitment_color'] = $row['shop_fitment_color'] ? $row['shop_fitment_color'] : '113533';
		//标题色
		$store['shop_fitment_front_color'] = 'ffffff';

		$store['image'] = isset($images[0]) ? $images[0] : '';
		if($row['logo']){
			$store['image'] = replace_file_domain($row['logo']);
		}
		$store['origin_image'] = $store['image'];
		$store['image'] = thumb_img($store['image'],240,240,'fill');

		$store['name'] = html_entity_decode($row['name'], ENT_QUOTES);
		$store['images'] = $images ? $images : array();
		$store['share_title'] = $store['name'];
		$store['share_content'] = L_('推荐你一个店铺').'    「 ' . $store['name'] . ' 」';
		$store['share_image'] = $store['image'];

		if ($row['store_theme'] && empty($row['is_mult_class'])) {
		    $store['share_url'] = $this->config['site_url'] . '/wap.php?c=Shop&a=classic_shop&shop_id=' . $row['store_id'];
		} else {
		    $store['share_url'] = $this->config['site_url'] . '/wap.php?g=Wap&c=Shop&a=index&shop-id=' . $row['store_id'];
		}
		
		$store['share_wx'] = ($this->config['pay_wxapp_important'] && $this->config['pay_wxapp_username']) ? 'wxapp' : 'h5';
		if($store['share_wx'] == 'wxapp'){
			$store['share_wx_info'] = [
				'userName' 	=> $this->config['pay_wxapp_username'],
				'path' 		=> '/pages/plat_menu/index?redirect=webview&webview_url='.urlencode($store['share_url']).'&webview_title='.urlencode($row['name']),
				'image' 	=> $store['share_image'],
				'title' 	=> $store['share_content'],
			];
		}

		//返回商家是否支持客服
        $store['kefu_url'] = '';
		if($this->config['open_kefu_plat'] == 'zendesk'){
			$zend_tags = [];
			if($row['city_id']){
				$zend_tags[] = M('Area')->where(['area_id'=>$row['city_id']])->getField('area_name');
			}
			$zend_tags[] = $store['name'];
			
			$store['kefu_url'] = $this->config['site_url'].'/wap.php?c=Redirect&a=packapp&url='.urlencode('my/zendesk_chat.html?account_key='.$this->config['open_kefu_zendesk'].'&zendesk_tag='.urlencode(implode('^&^',$zend_tags)));
		} else if($this->DEVICE_ID == 'wxapp'){
            if ($services = D('Customer_service')->where(array('mer_id' => $row['mer_id']))->select()) {
                $store['kefu_url'] = $this->config['site_url'] . '/wap.php?c=My&a=concact_kefu&mer_id=' . $row['mer_id'];
            }
        }else{
			$store['kefu_url'] = $this->config['site_url'] . '/wap.php?c=My&a=concact_kefu&mer_id=' . $row['mer_id'];
		}

        $uid = $this->_uid;
        $kf = M('Merchant_store_kefu')->where(['store_id' => $store_id,'belong'=>'store'])->find();
        if ($kf && $uid > 0) {
            $store['kefu_url'] = $this->config['site_url'] . '/wap.php?c=My&a=concact_kefu&mer_id=' . $row['mer_id'] . '&store_id=' . $store_id . '&from=meal';
        }else{
            $store['kefu_url'] = '';
        }    

            //营业时间
		$week = array(L_('周日'),L_('周一'),L_('周二'),L_('周三'),L_('周四'),L_('周五'),L_('周六'));

		$time_arr = D('Merchant_store_open_time')->get_one_time_by_store($row['store_id']);
		$store['time_arr'] = empty($time_arr) ? array() : $time_arr;
		
		$store['time'] = '';
		foreach($time_arr as $value){
			if(date("w") == $value['week']){
				$store['time'] = $value['week_show'] . ' ' . $value['time_show'];
				break;
			}
		}
		
		$next_time = D('Merchant_store_open_time')->get_next_time($row['store_id']);
		$store['next_time'] = ($store['is_business_open']&&!$store['is_close'])? ['1'=>'1'] : $next_time;
        //dump($row);exit;
		if($row['is_close']){
			$store['is_close'] = 1;
			$store['time_arr'] = [];
			$store['time'] = L_('紧急关店');
		}else{
			$store['close_reason'] = '';
			$store['is_booking'] = false;
			if(D('Merchant_store_shop')->checkTime($row)){
                $store['is_close'] = 0;
            }else{
                $store['is_close'] = 1;
				$store['advance_day'] = $row['advance_day'];
				if($store['advance_day'] > 0){
					$store['is_booking'] = true;
                    $store['is_close'] = 0;
				}
            }
		}
		if($store['next_time']['type']==3){//如果没有营业时间，则是关店状态且不可预定
			$store['is_close'] = 1;
			$store['is_booking'] = false;
		}
		if (M('Classify')->field(true)->where(array('token' => $row['mer_id']))->find()) {
            $store['home_url'] = $this->config['site_url'].'/wap.php?g=Wap&c=Index&a=index&token='.$row['mer_id'];
        } else {
            $store['home_url'] = '';
        }
		
		$store['reply_deliver_count'] = $row['reply_deliver_count'];
		$store['reply_deliver_score'] = number_format($row['reply_deliver_score'],1);
		if($store['reply_deliver_score'] == '0.0'){
			$store['reply_deliver_score'] = '5';
		}
        $store['reply_deliver_percent'] = $store['reply_deliver_score'] > 0 ? intval(($store['reply_deliver_score'] / 5) * 100).'%' : '0%';
		$store['reply_count'] = $row['reply_count'];
		$store['store_live_url'] = $row['store_live_url'];
		$store['store_notice'] = $row['store_notice'];
		$store['txt_info'] = $row['txt_info'];
		$store['auth_files'] = $auth_files;
		$store['star'] = number_format($row['score_mean'],1);
		if($store['star'] == '0.0'){
			$store['star'] = '5.0';
		}
		$store['stock_type'] = $row['stock_type'];
        $store['month_sale_count'] = $row['sale_count'] + $row['virtual_sale_count'];
		$store['is_new'] = ($row['create_time'] + 864000) < time() ? 0 : 1;
		$store['delivery'] = $row['deliver_type'] == 2 ? false : true;//是否支持配送
		//判断用户生日
        $store['check_age_object'] = array();
        $store['check_age_object']['age_tips_status0'] = 0;//0=正常 1=重新输入生日 2=未达到法定年龄
        $store['check_age_object']['age_tips0'] = L_('澳洲法律规定X1岁以下的未成年人不能抽烟、喝酒，如果要购买烟酒类商品必须出示身份证件证明自己已满X1周岁。', $row['age_limit_buy']);
        $store['check_age_object']['age_tips_title0'] = L_('您已达到购买烟酒产品法定年龄');
        $store['check_age_object']['age_tips_status1'] = 1;//0=正常 1=重新输入生日 2=未达到法定年龄
        $store['check_age_object']['age_tips1'] = L_('您购买的是烟酒类产品，所以我们需要确认您的年龄需满X1周岁或者以上。在递送时，您有可能被要求出示相关证件。', $row['age_limit_buy']);
        $store['check_age_object']['age_tips_title1'] = L_('请输入您的生日信息');
        $store['check_age_object']['age_tips_status2'] = 2;//0=正常 1=重新输入生日 2=未达到法定年龄
        $store['check_age_object']['age_tips2'] = L_('澳洲法律规定X1岁以下的未成年人不能抽烟、喝酒，如果要购买烟酒类商品必须出示身份证件证明自己已满X1周岁。', $row['age_limit_buy']);
        $store['check_age_object']['age_tips_title2'] = L_('您未达到购买烟酒产品法定年龄');
        $store['check_age_object']['age_limit_buy'] = $row['age_limit_buy'];
        $store['check_age_object']['check_age'] = false;//是否判断用户年龄
        if ($row['age_limit_buy_switch'] == 1 && $row['age_limit_buy'] > 0) { // 1=开启验证
            $store['check_age_object']['check_age'] = true;
        }

		$store['goods_count'] = S('store_shop_goods_count'.$store['store_id']);
		if(!$store['store_shop_goods_count']){
			$store['goods_count'] = M('Shop_goods')->where(array('store_id'=>$store['store_id'],'status'=>1))->count();
			S('store_shop_goods_count'.$store['store_id'],$store['goods_count'],86400);
		}
		
		//店铺小程序二维码
		$store['wxapp_qrcode'] = $this->config['site_url'].'/index.php?c=Recognition_wxapp&a=create_unlimit_qrcode&page=pages/shop_new/shopDetail/shopDetail&scene='.urlencode('store_id='.$store['store_id'].'&pf=store_qr');

		//配送时长
		if ($row['send_time_type'] == 0) {
		    $store['delivery_time'] = $row['sort_time'];
		} elseif ($row['send_time_type'] == 1) {
		    $store['delivery_time'] = floatval(round($row['sort_time'] / 60, 2));
		} elseif ($row['send_time_type'] == 2) {
		    $store['delivery_time'] = floatval(round($row['sort_time'] / 1440, 2));
		} elseif ($row['send_time_type'] == 3) {
		    $store['delivery_time'] = floatval(round($row['sort_time'] / 10080, 2));
		} elseif ($row['send_time_type'] == 4) {
		    $store['delivery_time'] = floatval(round($row['sort_time'] / 43200, 2));
		}
		
		$store['send_time_type'] = $row['send_time_type'];
		$store['delivery_time_type'] = $this->send_time_type[$row['send_time_type']];
		$store['delivery_price'] = getFormatNumber($row['basic_price']);//起送价
		
		//门店专题、爆款橱窗等信息
		$store['shop_fitment_color'] = $row['shop_fitment_color'] ? $row['shop_fitment_color'] : '113533';
		$store['shop_subject_show'] = $row['shop_subject_show'];
		$store['shop_showcase_show'] = $row['shop_showcase_show'];
		$store['shop_brand_weipage'] = $row['shop_brand_weipage'];
		if($row['shop_brand_weipage']){
			//$store['shop_brand_weipage_url'] = $this->config['site_url'].'/wap.php?c=Diypage&a=page&page_id='.$store['shop_brand_weipage'];
			$store['shop_brand_weipage_url'] = get_base_url('pages/customPage/customPage?source=store&source_id=' . $store['store_id'] . '&pageId=' . $store['shop_brand_weipage']);
		}else{
			$store['shop_brand_weipage_url'] = '';
		}
		
		//配送时间
		
		$deliverReturn = D('Deliver_set')->getDeliverInfo($row);
		$delivery_fee = $deliverReturn['delivery_fee'];
		$per_km_price = $deliverReturn['per_km_price'];
		$basic_distance = $deliverReturn['basic_distance'];
		
		$delivery_fee2 = $deliverReturn['delivery_fee2'];
		$per_km_price2 = $deliverReturn['per_km_price2'];
		$basic_distance2 = $deliverReturn['basic_distance2'];
		
		$delivery_fee3 = $deliverReturn['delivery_fee3'];
		$per_km_price3 = $deliverReturn['per_km_price3'];
		$basic_distance3 = $deliverReturn['basic_distance3'];
		
		$start_time = $deliverReturn['delivertime_start'];
		$stop_time = $deliverReturn['delivertime_stop'];
		
		$start_time2 = $deliverReturn['delivertime_start2'];
		$stop_time2 = $deliverReturn['delivertime_stop2'];
		
		$start_time3 = $deliverReturn['delivertime_start3'];
		$stop_time3 = $deliverReturn['delivertime_stop3'];

        $delivery_free = $deliverReturn['delivery_free'];//免配送费1
        $delivery_free2 = $deliverReturn['delivery_free2'];//免配送费2
        $delivery_free3 = $deliverReturn['delivery_free3'];//免配送费3
        $delivery_free_money = $deliverReturn['delivery_free_money'];//满￥免配送费1
        $delivery_free_money2 = $deliverReturn['delivery_free_money2'];//满￥免配送费2
        $delivery_free_money3 = $deliverReturn['delivery_free_money3'];//满￥免配送费3
		fdump($deliverReturn,'deliverReturn');
		$store['delivery_money'] = $delivery_fee;
		
		if(getFormatNumber($deliverReturn['deliver_speed_hour']) && getFormatNumber($_POST['lat']) && getFormatNumber($_POST['lng'])){
			if ($store['send_time_type'] == 0) {
				$jl = getDistance($_POST['lat'], $_POST['lng'], $row['lat'], $row['long']);
				//做单时长 + 配送时速 + 配送延迟时间
				$store['delivery_time'] = $row['work_time'] + round($jl / ($deliverReturn['deliver_speed_hour'] * 1000 / 60),0) + $deliverReturn['deliver_delay_time'];
			}
		}
		
		$time = time();
        $selectDeliverTime = 0;
        if (! ($start_time == $stop_time && $start_time == '00:00:00')) {
            $start_time = strtotime(date('Y-m-d') . ' ' . $start_time);
            $stop_time = strtotime(date('Y-m-d') . ' ' . $stop_time);
            if ($start_time > $stop_time) {
                if (strtotime(date('Y-m-d')) <= $time && $time <= $stop_time) {
                    $selectDeliverTime = 1;
                } else {
                    $stop_time2 += 86400;
                    if ($start_time <= $time && $time <= $stop_time) {
                        $selectDeliverTime = 1;
                    }
                }
            } elseif ($start_time <= $time && $time <= $stop_time) {
                $selectDeliverTime = 1;
            }
            if (! ($start_time2 == $stop_time2 && $start_time2 == '00:00:00')) {
                $start_time2 = strtotime(date('Y-m-d') . ' ' . $start_time2);
                $stop_time2 = strtotime(date('Y-m-d') . ' ' . $stop_time2);

                if ($start_time2 > $stop_time2) {
                    if (strtotime(date('Y-m-d')) <= $time && $time <= $stop_time2) {
                        $store['delivery_money'] = $delivery_fee2;
                        $selectDeliverTime = 2;
                    } else {
                        $stop_time2 += 86400;
                        if ($start_time2 <= $time && $time <= $stop_time2) {
                            $store['delivery_money'] = $delivery_fee2;
                            $selectDeliverTime = 2;
                        }
                    }
                } elseif ($start_time2 <= $time && $time <= $stop_time2) {
                    $store['delivery_money'] = $delivery_fee2;
                    $selectDeliverTime = 2;
                }
            }
            if (! ($start_time3 == $stop_time3 && $start_time3 == '00:00:00')) {
                $start_time3 = strtotime(date('Y-m-d') . ' ' . $start_time3);
                $stop_time3 = strtotime(date('Y-m-d') . ' ' . $stop_time3);

                if ($start_time3 > $stop_time3) {
                    if (strtotime(date('Y-m-d')) <= $time && $time <= $stop_time3) {
                        $store['delivery_money'] = $delivery_fee3;
                        $selectDeliverTime = 3;
                    } else {
                        $stop_time3 += 86400;
                        if ($start_time3 <= $time && $time <= $stop_time3) {
                            $store['delivery_money'] = $delivery_fee3;
                            $selectDeliverTime = 3;
                        }
                    }
                } elseif ($start_time3 <= $time && $time <= $stop_time3) {
                    $store['delivery_money'] = $delivery_fee3;
                    $selectDeliverTime = 3;
                }
            }
        }else{
            $selectDeliverTime = 1;
        }

        $set = D('Deliver_set')->field(true)->where(array('area_id' => $row['area_id'], 'status' => 1))->find();
        if (empty($set)) {
            $set = D('Deliver_set')->field(true)->where(array('area_id' => $row['city_id'], 'status' => 1))->find();
            if (empty($set)) {
                $set = D('Deliver_set')->field(true)->where(array('area_id' => $row['province_id'], 'status' => 1))->find();
            }
        }
        if ($selectDeliverTime == 1) {
            if ($row['deliver_type'] == 0 || $row['deliver_type'] == 3) {
                if ($row['s_is_open_own'] == 1) {
                    $store['delivery_price'] = floatval($row['s_basic_price1']) > -1 ? floatval($row['s_basic_price1']) : (floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) : (floatval($set['basic_price1']) ? floatval($set['basic_price1']) : (floatval($this->config['basic_price1']) ? floatval($this->config['basic_price1']) : ($this->config['basic_price'] ? floatval($this->config['basic_price']) : floatval($row['basic_price'])))));//起送价
                } else {
                    $store['delivery_price'] = floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) : (floatval($set['basic_price1']) ? floatval($set['basic_price1']) : (floatval($this->config['basic_price1']) ? floatval($this->config['basic_price1']) : ($this->config['basic_price'] ? floatval($this->config['basic_price']) : floatval($row['basic_price']))));//起送价
                }
                $store['extra_price'] = isset($row['s_extra_price']) ? floatval($row['s_extra_price']) : floatval($this->config['extra_price']);

                // 平台设置的配送优惠
                if ($delivery_free==1) {
                    $store['deliver_discount'] = array('money' => 0, 'type'=>'deliver_free');
                }elseif ($delivery_free==2) {
                    $store['deliver_discount'] = array('money' => $delivery_free_money, 'type'=>'deliver_free');
                }
            } else {
                $store['delivery_price'] = floatval($row['basic_price1']) ? floatval($row['basic_price1']) : floatval($row['basic_price']);//起送价
                $store['extra_price'] = floatval($row['extra_price']);//不满起送价另付金额支持配送
            }
        } elseif ($selectDeliverTime == 2) {
            if ($row['deliver_type'] == 0 || $row['deliver_type'] == 3) {
                if ($row['s_is_open_own'] == 1) {
                    $store['delivery_price'] = floatval($row['s_basic_price2']) > -1 ? floatval($row['s_basic_price2']) : (floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) : (floatval($set['basic_price2']) ? floatval($set['basic_price2']) : (floatval($this->config['basic_price2']) ? floatval($this->config['basic_price2']) : ($this->config['basic_price'] ? floatval($this->config['basic_price']) : floatval($row['basic_price'])))));//起送价
                } else {
                    $store['delivery_price'] = floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) : (floatval($set['basic_price2']) ? floatval($set['basic_price2']) : (floatval($this->config['basic_price2']) ? floatval($this->config['basic_price2']) : ($this->config['basic_price'] ? floatval($this->config['basic_price']) : floatval($row['basic_price']))));//起送价
                }
                $store['extra_price'] = isset($row['s_extra_price']) ? floatval($row['s_extra_price']) : floatval($this->config['extra_price']);

                // 平台设置的配送优惠
                if ($delivery_free2==1) {
                    $store['deliver_discount'] = array('money' => 0, 'type'=>'deliver_free');
                }elseif ($delivery_free2==2) {
                    $store['deliver_discount'] = array('money' => $delivery_free_money2, 'type'=>'deliver_free');
                }
            } else {
                $store['delivery_price'] = floatval($row['basic_price2']) ? floatval($row['basic_price2']) : floatval($row['basic_price']);//起送价
                $store['extra_price'] = floatval($row['extra_price']);//不满起送价另付金额支持配送
            }

        } elseif ($selectDeliverTime == 3) {
            if ($row['deliver_type'] == 0 || $row['deliver_type'] == 3) {
                if ($row['s_is_open_own'] == 1) {
                    $store['delivery_price'] = floatval($row['s_basic_price3']) > -1 ? floatval($row['s_basic_price3']) : (floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) : (floatval($set['basic_price3']) ? floatval($set['basic_price3']) : (floatval($this->config['basic_price3']) ? floatval($this->config['basic_price3']) : ($this->config['basic_price'] ? floatval($this->config['basic_price']) : floatval($row['basic_price'])))));//起送价
                } else {
                    $store['delivery_price'] = floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) : (floatval($set['basic_price3']) ? floatval($set['basic_price3']) : (floatval($this->config['basic_price3']) ? floatval($this->config['basic_price3']) : ($this->config['basic_price'] ? floatval($this->config['basic_price']) : floatval($row['basic_price']))));//起送价
                }
                $store['extra_price'] = isset($row['s_extra_price']) ? floatval($row['s_extra_price']) : floatval($this->config['extra_price']);
                // 平台设置的配送优惠
                if ($delivery_free3==1) {
                    $store['deliver_discount'] = array('money' => 0, 'type'=>'deliver_free');
                }elseif ($delivery_free3==2) {
                    $store['deliver_discount'] = array('money' => $delivery_free_money3, 'type'=>'deliver_free');
                }
            } else {
                $store['delivery_price'] = floatval($row['basic_price3']) ? floatval($row['basic_price3']) : floatval($row['basic_price']);//起送价
                $store['extra_price'] = floatval($row['extra_price']);//不满起送价另付金额支持配送
            }
        } else {
            if ($row['deliver_type'] == 0 || $row['deliver_type'] == 3) {
                $store['delivery_price'] = floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) : ($this->config['basic_price'] ? floatval($this->config['basic_price']) : floatval($row['basic_price']));//起送价
                $store['extra_price'] = floatval($row['s_extra_price']) ? floatval($row['s_extra_price']) : floatval($this->config['extra_price']);
            } else {
                $store['delivery_price'] = floatval($row['basic_price']);//起送价
                $store['extra_price'] = floatval($row['extra_price']);//不满起送价另付金额支持配送
            }
        }
		
		if($store['delivery_price']){
			$store['delivery_price'] = getFormatNumber($store['delivery_price']);
		}
		
        if ($store['deliver_type'] == 2) { //只有自提的情况下，去掉加价送
            $store['extra_price'] = 0;
        }
        if ($row['s_is_open_virtual'] == 1 && in_array($store['deliver_type'],[0,3])) {
            $store['delivery_money'] = getFormatNumber($row['virtual_delivery_fee']);
        } else {
            $store['delivery_money'] = getFormatNumber($store['delivery_money']);
        }
// 		$store['delivery_money'] = $row['deliver_type'] == 0 ? C('config.delivery_fee') : $row['delivery_fee'];//配送费
// 		$store['delivery_money'] = floatval($store['delivery_money']);//配送费
        $store['delivery_system'] = $row['deliver_type'] == 0 || $row['deliver_type'] == 3 ? true : false;//是否是平台配送
        if (in_array($row['deliver_type'], array(2, 3, 4))) {
            $store['pick'] = 1;//是否支持自提
        } else {
            $store['pick'] = 0;//是否支持自提
        }
        $store['pack_alias'] = $row['pack_alias'];//打包费别名
        $store['freight_alias'] = $row['freight_alias'];//运费别名
        $store['coupon_list'] = array();
        if ($row['is_invoice']) {
            $store['coupon_list']['invoice'] = floatval($row['invoice_price']);
        }
        if ($row['store_discount'] != 0 && $row['store_discount'] != 100) {
            $store['coupon_list']['discount'] = floatval(round($row['store_discount'] / 10, 2));
        }

        $system_delivery = array();
        if (isset($discounts[0]) && $discounts[0]) {
            foreach ($discounts[0] as $row_d) {
                // if ($row_d['order_count'] > 0) {//新单
                //     $store['coupon_list']['system_newuser'][] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']), 'order_count' => $row_d['order_count'], 'usage_scenario' => $row_d['usage_scenario'], 'percentage_status' => $row_d['percentage_status']);
                // } elseif ($row_d['type'] == 1 && $row_d['order_count'] == 0) {//满减
                //     $store['coupon_list']['system_minus'][] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']), 'order_count' => $row_d['order_count'], 'usage_scenario' => $row_d['usage_scenario'], 'percentage_status' => $row_d['percentage_status']);
                // } elseif ($row_d['type'] == 2 && $row_d['order_count'] == 0) {//配送
                //     $system_delivery[] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']), 'order_count' => $row_d['order_count'], 'usage_scenario' => $row_d['usage_scenario'], 'percentage_status' => $row_d['percentage_status']);
                // }

                if ($row_d['type'] == 0) {//新单
                    $store['coupon_list']['system_newuser'][] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']), 'order_count' => $row_d['order_count'], 'usage_scenario' => $row_d['usage_scenario'], 'percentage_status' => $row_d['percentage_status']);
                } elseif ($row_d['type'] == 1 ) {//满减
                    $store['coupon_list']['system_minus'][] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']), 'order_count' => $row_d['order_count'], 'usage_scenario' => $row_d['usage_scenario'], 'percentage_status' => $row_d['percentage_status']);
                } elseif ($row_d['type'] == 2 ) {//配送
                    $system_delivery[] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']), 'order_count' => $row_d['order_count'], 'usage_scenario' => $row_d['usage_scenario'], 'percentage_status' => $row_d['percentage_status']);
                }
            }
        }

        if (isset($discounts[$store_id]) && $discounts[$store_id]) {
            foreach ($discounts[$store_id] as $row_m) {
                if ($row_m['type'] == 0) {
                    $store['coupon_list']['newuser'][] = array('money' => floatval($row_m['full_money']), 'minus' => floatval($row_m['reduce_money']), 'order_count' => $row_m['order_count'], 'usage_scenario' => $row_m['usage_scenario']);
                } elseif ($row_m['type'] == 1) {
                    $store['coupon_list']['minus'][] = array('money' => floatval($row_m['full_money']), 'minus' => floatval($row_m['reduce_money']), 'order_count' => $row_m['order_count'], 'usage_scenario' => $row_m['usage_scenario']);
                }
            }
        }

        if ($store['delivery']) {
            if ($store['delivery_system']) {
                $system_delivery && $store['coupon_list']['delivery'] = $system_delivery;
            } else {
                if ($selectDeliverTime == 1) {
                    if ($row['reach_delivery_fee_type'] == 0) {
                        if ($row['basic_price'] > 0 && $row['delivery_fee'] > 0) {
                            $store['coupon_list']['delivery'][] = array(
                                'money' => floatval($row['basic_price']),
                                'minus' => floatval($row['delivery_fee'])
                            );
                        }
                    } elseif ($row['reach_delivery_fee_type'] == 1) {
                        // $store['coupon_list']['delivery'] = array('money' => false, 'minus' => $row['delivery_fee']);
                    } else {
                        $row['delivery_fee'] && $store['coupon_list']['delivery'][] = array(
                            'money' => floatval($row['no_delivery_fee_value']),
                            'minus' => floatval($row['delivery_fee'])
                        );
                    }
                } elseif ($selectDeliverTime == 2) {
                    if ($row['reach_delivery_fee_type2'] == 0) {
                        if ($row['basic_price'] > 0 && $row['delivery_fee2'] > 0) {
                            $store['coupon_list']['delivery'][] = array(
                                'money' => floatval($row['basic_price']),
                                'minus' => floatval($row['delivery_fee2'])
                            );
                        }
                    } elseif ($row['reach_delivery_fee_type2'] == 1) {
                        // $store['coupon_list']['delivery'] = array('money' => false, 'minus' => $row['delivery_fee']);
                    } else {
                        $row['delivery_fee2'] && $store['coupon_list']['delivery'][] = array(
                            'money' => floatval($row['no_delivery_fee_value2']),
                            'minus' => floatval($row['delivery_fee2'])
                        );
                    }
                } elseif ($selectDeliverTime == 3) {
                    if ($row['reach_delivery_fee_type3'] == 0) {
                        if ($row['basic_price'] > 0 && $row['delivery_fee3'] > 0) {
                            $store['coupon_list']['delivery'][] = array(
                                'money' => floatval($row['basic_price']),
                                'minus' => floatval($row['delivery_fee3'])
                            );
                        }
                    } elseif ($row['reach_delivery_fee_type3'] == 1) {
                        // $store['coupon_list']['delivery'] = array('money' => false, 'minus' => $row['delivery_fee']);
                    } else {
                        $row['delivery_fee3'] && $store['coupon_list']['delivery'][] = array(
                            'money' => floatval($row['no_delivery_fee_value3']),
                            'minus' => floatval($row['delivery_fee3'])
                        );
                    }
                }
            }
            // 平台免配送费设置
            if ($store['deliver_discount']) {
                $store['coupon_list']['delivery_free'] = $store['deliver_discount'];
            }
        }

		$store['coupon_list_simple'] = $this->simpleParseCoupon($store['coupon_list'],'array');
		$store['coupon_list'] = $this->parseCoupon($store['coupon_list'],'array');
		//满赠活动
        $activity = D('Store_full_gift_activity')->getCurrentActivityByStoreId($store_id);
        if ($activity) {
            $setting = D('Store_full_gift_activity')->getSettingByActivityId($activity['id']);
            if ($setting) {
                if($setting[0]['full_price'] > 0){
                    array_push($store['coupon_list'], ['type' => 'full_gift', 'value' => L_('满') . C('config.Currency_symbol') . $setting[0]['full_price'] . L_('得赠品')]);
                }else{
                    array_push($store['coupon_list'], ['type' => 'full_gift', 'value' => L_('下单得赠品')]);
                }
            }
        }

        $store['merchant_links'] = array();
        if($this->config['open_scene_vr'] && $row['store_vr']){
            $vr['title'] = '720全景';
            $vr['icon'] = $this->config['site_url'].'/static/images/VR.png';
            $vr['link'] = $this->config['site_url'].$row['store_vr'];
            $store['merchant_links'][] = $vr;
        }

		//店铺优惠券
		$now_mer = M('Merchant_store')->where(array('store_id'=>$store['store_id']))->find();
		$where = array('mer_id'=>$now_mer['mer_id'],'status'=>array(array('gt',0),array('neq',4)),'end_time'=>array('gt',time()),'mer_id'=>$now_mer['mer_id'],'send_type'=>array('neq',1),'allow_im_get'=>0,'only_assign'=>0);
		$where['_string'] = "cate_name='shop' OR cate_name='all'";
		$coupont_list =  M('Card_new_coupon')->where($where)
						->order('status ASC,allow_new DESC,discount DESC')
						->field('coupon_id,name,img,had_pull,num,limit,des,cate_name,cate_id,discount,order_money,start_time,end_time,status,allow_new,store_id')->select();

		$category = array(
				'all' => L_('通用券'),
				'group' => C('config.group_alias_name'),
				'meal' => C('config.meal_alias_name'),
				'appoint' => C('config.appoint_alias_name'),
				'shop' => C('config.shop_alias_name'),
				'store' => L_('优惠买单'),
		);
		$new_coupon = array();
		$old_num=  0;

		foreach ($coupont_list as $k=>&$v) {
			$is_new_coupon = false;
			$store_ids = explode(',',$v['store_id']);
			if(!empty($store_ids) && !in_array($store['store_id'],$store_ids)){
				continue;
			}
			if(strpos($v['img'],'http')===false){
				$v['img']=  file_domain().$v['img'];
			}
			$v['effective_days'] =L_('有效期X1天',intval(($v['end_time']-$v['start_time'])/86400));
			$v['start_time'] = date('Y.m.d',$v['start_time']);
			$v['end_time'] = date('Y.m.d',$v['end_time']);

			$v['platform'] = unserialize($v['platform']);
			if(!empty($v['platform'])&& !in_array('app',$v['platform'])){
				continue;
			}
			
			$v['active'] = false;
			
			if(!empty($this->_uid)){
				$count = M('Card_new_coupon_hadpull')->where(array('coupon_id'=>$v['coupon_id'],'uid'=>$this->_uid))->count();
				if($count>0) {
					$v['limit'] = $count;
					$old_num++;
					$v['active'] = true;
				}else if($v['status']==1 && $count==0){
					$is_new_coupon =  true;
				}
				if($count==0 && $v['status']!=1){
					continue;
				}
				if($count == 0 && $v['allow_new'] && !D('User')->check_new($this->_uid,'shop')){
					continue;
				}
			}

			$v['discount'] = floatval($v['discount']);
			$v['order_money'] = floatval($v['order_money']);
			$v['cate_id'] = unserialize($v['cate_id']);
			$v['cate_name']  = $category[$v['cate_name']];
			if(!empty($v['cate_id'])){
				$v['type_name']=$v['cate_id']['cat_name'];
			}
			unset($coupont_list[$k]['cate_id']);
			if($is_new_coupon){
				$new_coupon[] =  $v;
			}
			$tmp[]  =$v;
		}
		if(empty($tmp)){
			$tmp = array();
		}else{
			foreach($tmp as &$tmp_value){
				$tmp_value = array(
					'coupon_id' 	=> $tmp_value['coupon_id'],
					'order_money' 	=> getFormatNumber($tmp_value['order_money']),
					'discount' 		=> getFormatNumber($tmp_value['discount']),
					'end_time' 		=> $tmp_value['end_time'],
					'active' 		=> $tmp_value['active'],
					'active_sort' 	=> $tmp_value['active'] ? 1 : 0,
				);
			}
			$tmp = sortArray($tmp,'active_sort');
		}
		$store['ticket_list'] = $tmp;
		if(!empty($new_coupon)){
			$store['ticket_list'] = $new_coupon;
			$store['ticket_get_status'] = 0;
		}else{
			if($old_num==count($tmp)){	
				$store['ticket_get_status'] = 1;
			}else{
				$store['ticket_get_status'] = 0;
			}	
		}
		
		//三级分类返回分类目录结构，一级分类直接返回分类+商品
		if($store['openclassfyindex']){
			$store['category_list'] = $this->format_category_value(D('Shop_goods_sort')->lists($store['store_id'],1,'',true,true));
			foreach ($store['category_list'] as $key=>$item){
				$store['category_list'][$key]['sort_discount'] = round($item['sort_discount']/10,2);
				$store['category_list'][$key]['sort_discount'] = $store['category_list'][$key]['sort_discount']>10||$store['category_list'][$key]['sort_discount']<0?0:$store['category_list'][$key]['sort_discount'];
				if($store['yuanjino2o_customized_func']){
					if($item['son_list']){
						foreach ($item['son_list'] as $k=>$v){
							if($v['son_list']){
								foreach ($v['son_list'] as $kk=>$vv){
									$store['category_list'][$key]['son_list'][$k]['son_list'][$kk]['product_list'] = $this->goodsBySortIdFormat($vv['sort_id'],$store_id,1);
								}
							}else{
								$store['category_list'][$key]['son_list'][$k]['product_list'] = $this->goodsBySortIdFormat($v['sort_id'],$store_id,1);
							}
						}
					}else{
						$store['category_list'][$key]['product_list'] = $this->goodsBySortIdFormat($item['sort_id'],$store_id,1);
					}
				}
			}
			
		}else{
            $store['product_list'] = D('Shop_goods')->getListByStoreId($store['store_id'],0,'',true,true,true,$this->_uid ?? 0);
			foreach($store['product_list'] as $value){
				if($value['son_list']){
					unset($store['product_list']);
					$store['openclassfyindex'] = 1;
					$store['category_list'] = $this->format_category_value(D('Shop_goods_sort')->lists($store['store_id'],0,'',true,true));
					break;
				}
			}

            $levelDiscount = (new Merchant_store_shopModel())->getVipLevelDiscount($this->_uid, $store['store_id'], 'shop');
            //每个商品要计算会员价
            if ($store['product_list']) {
                foreach ($store['product_list'] as $k => $v) {
					$store['product_list'][$k]['sort_discount'] = $v['sort_discount']>10||$v['sort_discount']<0?0:$v['sort_discount'];
                    foreach ($v['product_list'] as $k2 => $v2) {
						if (C('config.is_show_sellout_goods') == 0 && $v2['stock'] == 0) {//隐藏外卖售罄商品
							unset($v['product_list'][$k2]);
							continue;
						}

                        $store['product_list'][$k]['product_list'][$k2]['vip_show'] = $levelDiscount['show_level_price'] ? true : false;
                        $store['product_list'][$k]['product_list'][$k2]['vip_tag'] = M('User_level')->where(['level'=>$levelDiscount['level']])->getField('lname');
                        $store['product_list'][$k]['product_list'][$k2]['vip_price'] = getFormatNumber($v2['product_price'] * $levelDiscount['discount'] - $levelDiscount['discount_price'], 2, false);
						if($store['product_list'][$k]['sort_discount']>0){
							$sort_dis_price = getFormatNumber($store['product_list'][$k]['sort_discount']*$v2['o_price']/10,2,false);
							$store['product_list'][$k]['product_list'][$k2]['product_price'] = $sort_dis_price;
							if($store['product_list'][$k]['product_list'][$k2]['vip_show']){
								$store['product_list'][$k]['product_list'][$k2]['vip_price'] = $sort_dis_price - ($v2['o_price'] - $store['product_list'][$k]['product_list'][$k2]['vip_price']);
								$store['product_list'][$k]['product_list'][$k2]['vip_price'] = $store['product_list'][$k]['product_list'][$k2]['vip_price']<0?0:getFormatNumber($store['product_list'][$k]['product_list'][$k2]['vip_price'],2,false);
							}
						}
						//消费一元获得积分
						$buy_can_get_score = D('Goods_score')->getGoodsScore($store['product_list'][$k]['product_list'][$k2]['product_price'],'shop',$v2['has_spec'],$v2['score_percent']);
						$store['product_list'][$k]['product_list'][$k2]['buy_can_get_score'] = $buy_can_get_score?$buy_can_get_score['score']:0;
						$store['product_list'][$k]['product_list'][$k2]['buy_can_get_score_txt'] = $buy_can_get_score?$buy_can_get_score['score_txt']:'';
                    }

					$store['product_list'][$k]['product_list'] = array_values($v['product_list']);
                }
            }
		}
		
		//当前用户会员等级
		$levelDiscount = (new Merchant_store_shopModel())->getVipLevelDiscount($this->_uid, $store_id, 'shop');

		//门店专题、爆款橱窗、品牌故事、特色分类
		$condition['store_id']	= $store['store_id'];
		$subject_info = M('Merchant_store_shop_data')->where($condition)->find();
		$store['shop_subject_pic'] = '';
		$store['shop_showcase'] = array('good_count'=>0);
		$today = date('Ymd');
		if($subject_info){
			//门店专题是否展示
			if($store['shop_subject_show'] && $subject_info['subject_pic']){
				//$subject_pic = substr($subject_info['subject_pic'], 0, 1) == '/' ? $subject_info['subject_pic'] : '/' . $subject_info['subject_pic'];
				$store['shop_subject_pic'] = replace_file_domain($subject_info['subject_pic']);
			}
			//爆款橱窗是否展示
			if($store['shop_showcase_show']){
				$store['shop_showcase']['name']	= $subject_info['showcase_name'];
				$where_goods = array(
                    'store_id'	=> $store['store_id'],
                    'goods_ids'	=> explode(',',$subject_info['showcase_good_id']),
					'uid' => $this->_uid ?? 0
                );
				$good_list = D('Shop_goods')->get_list_by_option($where_goods, 1, 1, false, 1, true);
				if(empty($good_list['goods_list'])){
					$good_list['goods_list'] = array();
				}else{
					$tempGoodList = array();
					foreach($good_list['goods_list'] as $tmp_good_value){
						if (D('Shop_goods')->isShow($tmp_good_value['goods_id'], 1 | 4)) {
							$tempGoodList[$tmp_good_value['goods_id']] = $tmp_good_value;
						}
					}
					$good_list['goods_list'] = array();
					foreach($where_goods['goods_ids'] as $goods_id){
						if($tempGoodList[$goods_id]){
							$good_list['goods_list'][] = $tempGoodList[$goods_id];
						}
					}
					
					foreach($good_list['goods_list'] as &$good_value){
						$good_value['sell_day'] = $store['stock_type'] ? $today : $good_value['sell_day'];
						if ($good_value['is_seckill_price']) {
							$good_value['stock'] = $good_value['seckill_stock'];
						} else {
							if ($today == $good_value['sell_day']) {
							} else {
								if($good_value['sell_type']==2){
									$good_value['stock'] = $good_value['original_stock'];
								}
							}
						}
						$good_value['product_sale'] = $good_value['sell_count'] + $good_value['shop_virtual_sales'];
						$good_value['product_id']	= $good_value['goods_id'];
						$good_value['product_name']	= $good_value['name'];
						$good_value['product_image']= thumb_img($good_value['image'], 240, 240, 'fill');
						$good_value['image'] = $good_value['product_image'];
						$good_value['product_price']= $good_value['price'];
						$good_value['has_format']   = ($good_value['spec_value'] || $good_value['is_properties']) ? true : false;
						$good_value['has_spec']   = $good_value['spec_value'] ? true : false;
					}
				}
				
				$store['shop_showcase']['good_list'] = $good_list['goods_list'];
				$store['shop_showcase']['good_count'] = count($good_list['goods_list']);
				if($store['shop_showcase']['good_count'] == 0){
					$store['shop_showcase_show'] = 0;
					$store['shop_showcase'] = array('good_count'=>0);
				}
			}
			//特殊分类-热销
			if($subject_info['cat_hot_status']){
				$store['cat_hot']['name']	= $subject_info['cat_hot_name'];
				$store['cat_hot']['desc']	= $subject_info['cat_hot_desc'];
				$where_goods = array(
					'store_id'	=> $store['store_id'],
					'goods_ids'	=> explode(',',$subject_info['cat_hot_good_id']),
				);
				$sort = 1;
				$sort_type = 1;
				switch($subject_info['cat_hot_sort']){
					case 1:		//价格从高到低排序
						$sort = 3;
						$sort_type = 1;
						break;
					case 2:		//价格从低到高排序
						$sort = 3;
						$sort_type = 0;
						break;
					case 3:		//销量从高到低排序
						$sort = 2;
						$sort_type = 1;
						break;
					case 4:		//销量从低到高排序
						$sort = 2;
						$sort_type = 0;
						break;
				}
				$good_list = D('Shop_goods')->get_list_by_option($where_goods,$sort,$sort_type,false,1,true);
				if(empty($good_list['goods_list'])){
					$good_list['goods_list'] = array();
				}else{
					foreach($good_list['goods_list'] as &$good_value){
						$good_value['sell_day'] = $store['stock_type'] ? $today : $good_value['sell_day'];
						if ($good_value['is_seckill_price']) {
							$good_value['stock'] = $good_value['seckill_stock'];
						} else {
							if ($today == $good_value['sell_day']) {
							} else {
								if($good_value['sell_type']==2){
									$good_value['stock'] = $good_value['original_stock'];
								}
							}
						}
						$good_value['product_sale'] = $good_value['sell_count'] + $good_value['shop_virtual_sales'];
						$good_value['product_id']	= $good_value['goods_id'];
						$good_value['product_name']	= $good_value['name'];
						$good_value['product_describe']	= $good_value['describe'];
						$good_value['product_image']= thumb_img($good_value['image'], 240, 240, 'fill');
						$good_value['product_price']= $good_value['price'];
						$good_value['has_format']   = ($good_value['spec_value'] || $good_value['is_properties']) ? true : false;
						$good_value['has_spec']   = $good_value['spec_value'] ? true : false;
						$good_value['vip_show']   =  $levelDiscount['show_level_price'] ? true : false;
						$good_value['vip_tag']   =  M('User_level')->where(['level'=>$levelDiscount['level']])->getField('lname');
						$good_value['vip_price']   =  getFormatNumber($good_value['price'] * $levelDiscount['discount']);
						$good_value['vip_unit_price']   =  getFormatNumber($good_value['unit_price'] * $levelDiscount['discount']);
					}
				}
				$store['cat_hot']['good_list'] = $good_list['goods_list'];
				$store['cat_hot']['good_count'] = count($good_list['goods_list']);
				if($store['cat_hot']['good_count'] == 0){
					unset($store['cat_hot']);
				}
			}
			//特殊分类-优惠
			if($subject_info['cat_discount_status']){
				$store['cat_discount']['name']	= $subject_info['cat_discount_name'];
				$store['cat_discount']['desc']	= $subject_info['cat_discount_desc'];
				$where_goods = array(
					'store_id'	=> $store['store_id'],
					'goods_ids'	=> explode(',',$subject_info['cat_discount_good_id']),
				);
				$sort = 1;
				$sort_type = 1;
				switch($subject_info['cat_discount_sort']){
					case 1:		//价格从高到低排序
						$sort = 3;
						$sort_type = 1;
						break;
					case 2:		//价格从低到高排序
						$sort = 3;
						$sort_type = 0;
						break;
					case 3:		//销量从高到低排序
						$sort = 2;
						$sort_type = 1;
						break;
					case 4:		//销量从低到高排序
						$sort = 2;
						$sort_type = 0;
						break;
				}
				$good_list = D('Shop_goods')->get_list_by_option($where_goods,$sort,$sort_type,false,1,true);
				if(empty($good_list['goods_list'])){
					$good_list['goods_list'] = array();
				}else{
					foreach($good_list['goods_list'] as &$good_value){
						$good_value['sell_day'] = $store['stock_type'] ? $today : $good_value['sell_day'];
						if ($good_value['is_seckill_price']) {
							$good_value['stock'] = $good_value['seckill_stock'];
						} else {
							if ($today == $good_value['sell_day']) {
                                if($good_value['sell_type'] == 2 && $good_value['stock_weight'] == -1){
                                    $good_value['stock'] = -1;
                                }elseif($good_value['sell_type'] == 2){
                                    $goods_weight =  $good_value['weight_unit'] == 2 ? $good_value['weight'] * 1000 : $good_value['weight'];
									$good_value['stock'] = $goods_weight > 0 ? floor($good_value['stock_weight'] / $goods_weight) : 0;
                                }
							} else {
								if($good_value['sell_type']==2){
									$good_value['stock'] = $good_value['original_stock'];
								}
                                if($good_value['sell_type'] == 2 && $good_value['original_weight'] == -1){
                                    $good_value['stock'] = -1;
                                }elseif($good_value['sell_type'] == 2){
                                    $goods_weight =  $good_value['weight_unit'] == 2 ? $good_value['weight'] * 1000 : $good_value['weight'];
                                    if($goods_weight > 0){
                                        $good_value['stock'] = floor($good_value['original_weight'] / $goods_weight);
                                    }else{
                                        $good_value['stock'] = 0;
                                    }
                                }
							}
						}
						$good_value['product_sale'] = $good_value['sell_count'];
						$good_value['product_id']	= $good_value['goods_id'];
						$good_value['product_name']	= $good_value['name'];
						$good_value['product_describe']	= $good_value['describe'];
						$good_value['product_image']= thumb_img($good_value['image'], 240, 240, 'fill');
						$good_value['product_price']= $good_value['price'];
						$good_value['has_format']   = ($good_value['spec_value'] || $good_value['is_properties']) ? true : false;
						$good_value['has_spec']   = $good_value['spec_value'] ? true : false;
						$good_value['vip_show']   =  $levelDiscount['show_level_price'] ? true : false;
						$good_value['vip_tag']   =  M('User_level')->where(['level'=>$levelDiscount['level']])->getField('lname');
						$good_value['vip_price']   =  getFormatNumber($good_value['price'] * $levelDiscount['discount']);
						$good_value['vip_unit_price']   =  getFormatNumber($good_value['unit_price'] * $levelDiscount['discount']);
					}
				}
				$store['cat_discount']['good_list'] = $good_list['goods_list'];
				$store['cat_discount']['good_count'] = count($good_list['goods_list']);
				if($store['cat_discount']['good_count'] == 0){
					unset($store['cat_discount']);
				}
			}
		}
        //必选分类
        $mustSort = D('Merchant_store')->getMustSortIdsByStoreId($store_id, 'sort_id,sort_name');
        $store['required_sort'] = $mustSort ?: new \stdClass();
		//店铺收藏
        $store['is_collect'] = M("Merchant_user_relation")->where(['uid'=>$this->_uid,'store_id'=>$store_id])->find() ? '1' : '0';
        $store['current_liveshow_id'] = $row['current_liveshow_id'];


        //分类限购提示背景色
        $store['category_limit_num_bg'] = $now_shop['category_limit_num_bg'];


        // 是否订阅
        $is_subscribe = 0;
        $remind_time = '';
        $storeRemind = D("Merchant_store_shop")->where(['store_id'=>$store_id])->find();
        if($storeRemind['is_remind'] == 1 && $storeRemind['remind_weekend']){
            $countSubscribe = D("User_subscribe")->where(['uid'=>$this->_uid,'store_id'=>$store_id])->count();
            $weekend = array_unique(explode(',',$storeRemind['remind_weekend']));
            $week = [
                '0' => L_('周日'),
                '1' => L_('周一'),
                '2' => L_('周二'),
                '3' => L_('周三'),
                '4' => L_('周四'),
                '5' => L_('周五'),
                '6' => L_('周六')
            ];
            $weekend = array_map(function ($r) use ($week) {
                return $week[$r] ?? '';
            }, $weekend);
            $timeTip = '每天';
            if (count($weekend) != 7) {
                $timeTip = implode('、', $weekend);
            }
            $timeTip .= ' ' . date('H:i', $storeRemind['remind_time']);
            if ($countSubscribe > 0) {
                $is_subscribe = 1;
                $remind_time = '已订阅（' . $timeTip . '）';
            } else {
                $is_subscribe = 0;
                $remind_time = '未订阅（' . $timeTip . '）';
            }
        }else{
            $is_subscribe = 2;
        }
        $store['is_subscribe'] = $is_subscribe;
        $store['remind_time'] = $remind_time;

		$shopCategory = D('Shop_category_relation')
			->alias('r')
			->join('INNER JOIN ' . C('DB_PREFIX').'merchant_store_shop s ON r.store_id = s.store_id')
			->join('INNER JOIN ' . C('DB_PREFIX').'shop_category c ON r.cat_id = c.cat_id')
			->where(['s.store_id' => $store_id])
			->field('c.cat_url')
			->find();
		
		$store['cat_url'] = $shopCategory['cat_url'] ?? '';

		//判断用户是否关注了公众号
		$store['is_follow'] = false;
		$store['wechat_qrcode'] = replace_file_domain(C('config.wechat_qrcode'));
		if ($this->_uid) {
			$thisUser = D('User')->where(['uid' => $this->_uid])->find();
			$store['is_follow'] = $thisUser['is_follow'] == 1 ? true : false;
		}
		
		//店铺首页展示集点返券活动
		$rebate_info = null;
		$rebate_detail = D('Shop_rebate')->where(['store_id'=>$store_id,'status'=>1,'is_del'=>0,'end_time'=>['gt',time()],'start_time'=>['lt',time()]])->order('id desc')->find();
		if($rebate_detail){
			//获取优惠券信息
			$rebate_coupon = D('card_new_coupon')->where(['coupon_id'=>$rebate_detail['coupon_id']])->find();
			$rebate_user = [];
			if($this->_uid){
				//获取用户以获取优惠券数量
				$user_coupon_num = D('Card_new_coupon_hadpull')->where(['uid'=>$this->_uid,'coupon_id'=>$rebate_detail['coupon_id']])->sum('num');
				//查询用户是否已参与
				$rebate_user = D('Shop_rebate_user')->where(['uid'=>$this->_uid,'rid'=>$rebate_detail['id'],'status'=>['neq',2]])->order('id desc')->find();
				
				if($rebate_user['status']==1&&($user_coupon_num>=$rebate_coupon['limit'])){
					$rebate_user['total'] = $rebate_detail['total_order'];
				}elseif($rebate_user['status']==1&&($user_coupon_num<$rebate_coupon['limit'])){
					$rebate_user['total'] = 0;
				}
			}
			$rebate_info = [
				'id' => $rebate_detail['id'],
				'name' => $rebate_detail['name'],
				'note' => $rebate_detail['note'],
				'total_order' => $rebate_detail['total_order'],
				'coupon_money' => ($rebate_coupon['discount']+0),
				'description' => '集满'.$rebate_detail['total_order'].'单得'.($rebate_coupon['discount']+0).'元代金券',
				'is_ready' => ($rebate_user&&$rebate_user['status']==1&&$rebate_user['is_notice']==0)?1:0,
				'msg' => $rebate_user&&$rebate_user['status']?'集单成功，优惠券已发放，请到优惠券列表查看':'',
				'user_total_order' => $rebate_user?$rebate_user['total']:0,
			];
			if($this->_uid&&$rebate_user&&$rebate_user['status']==1&&$rebate_user['is_notice']==0){
				D('Shop_rebate_user')->where(['id'=>$rebate_user['id']])->data(['is_notice'=>1])->save();
			}
		}
		$store['rebate_info'] = $rebate_info;
		
		//获取导航栏
		$store['slider_list'] = [];
		$slider_list = D('Shop_slider')->field('id,name,url,pic')->where('status = 1 and store_id = '.$now_store['store_id'].' and is_del = 0')->order('sort desc,id desc')->select();
		foreach ($slider_list as $key=>$item){
			$slider_list[$key]['pic'] = $item['pic']?replace_file_domain($item['pic']):'';
		}
		$store['slider_list'] = $slider_list?:null;

        //外卖首页点餐引导语
        $store['order_guide_words'] = $now_shop['order_guide_words'] ?: L_("点菜");
		
		if(!$_POST['data_is_return']){
			//增加店铺浏览量
			D('Merchant_store_shop')->where(['store_id'=>$store_id])->setInc('hits',1);
			$this->returnCodeOk($store);
		}else{
			return $store;
		}
	}

	//门店专题页面
	public function shop_subject(){
		$store_id = intval($_POST['store_id']);
		if(empty($store_id)){
			$this->returnCodeError('请携带 store_id 参数');
		}
		$condition['store_id']	= $store_id;
		$subject_info = M('Merchant_store_shop_data')->where($condition)->find();
		$returnArr['subject_name'] = $subject_info['subject_name'];
		$returnArr['subject_pic'] = replace_file_domain($subject_info['subject_pic']);
		$where_goods = array(
			'store_id'	=> $store_id,
			'goods_ids'	=> explode(',',$subject_info['subject_good_id']),
			'page_size'	=> 20,
		);
		$good_list = D('Shop_goods')->get_list_by_option($where_goods, 1, 1, false, 1, true);
		if(empty($good_list['goods_list'])){
			$good_list['goods_list'] = array();
		}else{
			$tempGoodList = array();
			foreach($good_list['goods_list'] as $tmp_good_value){
                if(!D('Shop_goods')->isShow($tmp_good_value['goods_id'])){
                    continue;
                }
				$tempGoodList[$tmp_good_value['goods_id']] = $tmp_good_value;
			}
			$good_list['goods_list'] = array();
			foreach($where_goods['goods_ids'] as $goods_id){
				if($tempGoodList[$goods_id]){
					$good_list['goods_list'][] = $tempGoodList[$goods_id];
				}
			}
		}
		$returnArr['good_list'] = $good_list['goods_list'];
		$returnArr['good_count'] = count($good_list['goods_list']);
		
		foreach($returnArr['good_list'] as &$good_value){
            $good_value['product_sale'] = $good_value['sell_count'] + $good_value['shop_virtual_sales'];
			$good_value['product_id']	= $good_value['goods_id'];
			$good_value['product_name']	= $good_value['name'];
			$good_value['product_image']= $good_value['image'];
            $good_value['product_price']= $good_value['price'];
            if($good_value['unit_price']){
                $good_value['unit_price'] = getFormatNumber($good_value['unit_price']);
            }
			$good_value['has_format']   = ($good_value['spec_value'] || $good_value['is_properties']) ? true : false;
			$good_value['has_spec']   = $good_value['spec_value'] ? true : false;
			$good_value['is_subsidiary_goods'] = $good_value['is_subsidiary_goods'];
		}
		
		$this->returnCodeOk($returnArr);
	}
	
	public function format_category_value($category_list){
		if($category_list){
			foreach($category_list as $key=>$value){
				$category_list[$key]['sort_id'] = $value['cat_id'];
				if($value['son_list']){
					$category_list[$key]['son_list'] = $value['son_list'] = array_values($value['son_list']);
					foreach($value['son_list'] as $k=>$v){
						if($v['son_list']){
							$category_list[$key]['son_list'][$k]['sort_id'] = $v['cat_id'];
							$category_list[$key]['son_list'][$k]['son_list'] = array_values($v['son_list']);
						}
					}
				}
			}
			$category_list = array_values($category_list);
		}else{
			$category_list = array();
		}
		return $category_list;
	}

	//根据商品分类ID获取商品列表
	public function getGoodsBySortId(){
		$sort_id = $_POST['sort_id'];
		if(empty($sort_id)){
			$this->returnCodeError(L_('请携带分类ID'));
		}
		$store_id = $_POST['store_id'];
		if(empty($store_id)){
			$this->returnCodeError(L_('请携带店铺ID'));
		}
		
		$is_shop = isset($_POST['is_shop'])?$_POST['is_shop']:1;

        $productList = $this->goodsBySortIdFormat($sort_id,$store_id,$is_shop);
		$this->returnCodeOk($productList);
	}
	
	private function goodsBySortIdFormat($sort_id,$store_id,$is_shop){
		$sort_info = D(_view('Shop_goods_sort'))->field(true)->where(['sort_id'=>$sort_id,'store_id'=>$store_id])->find();
		if(!$sort_info){
			$this->returnCodeError(L_('分类不存在！'));
		}
		$sort_info['sort_discount'] = round($sort_info['sort_discount']/10,2);
		$sort_info['sort_discount'] = $sort_info['sort_discount']>10||$sort_info['sort_discount']<0?0:$sort_info['sort_discount'];
		
		//当前用户会员等级
		$levelDiscount = (new Merchant_store_shopModel())->getVipLevelDiscount($this->_uid, $store_id, 'shop');

		$today = date('Ymd');
		$now_shop = D('Merchant_store_shop')->field(true)->where(array('store_id' => $store_id))->find();
		$merchantStore = D('Merchant_store')->where(array('store_id' => $store_id))->find();
		$nowSort = D('Shop_goods')->getGoodsBySortIds(array($sort_id),$store_id,false,'sort DESC, goods_id ASC',$is_shop, 0, $this->_uid ?? 0);
		$productList = $nowSort[0]['goods_list'];
		foreach($productList as $key=>$value){
			if (C('config.is_show_sellout_goods') == 0 && $value['stock_num'] == 0) {//隐藏外卖售罄商品
				unset($productList[$key]);
				continue;
			}
			$value['sell_day'] = $now_shop['stock_type'] ? $today : $value['sell_day'];
			if ($value['is_seckill_price']) {
				$value['stock_num'] = $value['seckill_stock'];
			} else {
				if ($today == $value['sell_day']) {
				} else {
					if($value['sell_type']==2){
						$value['stock_num'] = $value['original_stock'];
					}
				}
			}
			$value['name'] = html_entity_decode($value['name'], ENT_QUOTES);
			//积分展示
			$value['score_text'] = '';
			if(C('customization.goods_show_score') && $scorePrice = floatval($value['price'])){
				$scorePercent = D('Percent_rate')->scorePercent('shop', $value['goods_id'], $merchantStore['mer_id']);
				$score = bcmul($scorePrice, $scorePercent,2);
				if(C('config.score_round_two')){
					$score = sprintf('%.2f',floatval($score));
				}else{
					$score = round($score);
				}
				floatval($score) > 0  && $value['score_text'] = C('config.score_name').'+'.$score;
			}
			$vip_price = getFormatNumber($value['price'] * $levelDiscount['discount'] - $levelDiscount['discount_price'], 2 ,false);
			if($sort_info['sort_discount']>0){
				$sort_dis_price = getFormatNumber($sort_info['sort_discount']*$value['o_price']/10,2,false);
				$value['product_price'] = $sort_dis_price;
				if($levelDiscount['show_level_price']){
					$vip_price = $sort_dis_price - ($value['price'] - $vip_price);
					$vip_price = $vip_price<0?0:getFormatNumber($vip_price,2,false);
				}
			}
			$productList[$key] = array(
				'product_id' 		=> $value['goods_id'],			//商品ID
				'goods_id' 			=> $value['goods_id'],			//商品ID
				'store_id' 			=> $value['store_id'],			//店铺ID
				'name' 				=> $value['name'],				//商品名称
				'product_name' 		=> $value['name'],  			//商品名称
				'product_describe' 	=> $value['describe'],  			//商品描述
				'describe' 	        => $value['describe'],  			//商品描述
				'unit' 				=> $value['unit'],				//商品单位
				'o_price' 			=> $value['o_price'],			//商品价格
				'price' 			=> $value['price'],				//商品价格
				'product_price' 	=> $value['product_price']?:$value['price'],				//商品价格
				'score_text' 	    => $value['score_text'],		//积分展示
				'is_seckill_price' 	=> $value['is_seckill_price'],	//是否在秒杀
				'seckill_discount' 	=> $value['o_price'] ? round($value['price']/$value['o_price']*10,1) : 10,//秒杀折扣
				'seckill_stock' 	=> $value['seckill_stock'],		//秒杀库存，-1代表无限
				'image' 			=> $value['image'],				//商品图片
				'product_image' 	=> $value['image'],				//商品图片
				'limit_type' 		=> $value['limit_type'],		//限购类别
				'sell_count' 		=> $value['sell_count'],		//商品已售数量
				'stock_num' 		=> $value['stock_num'],			//商品库存
				'stock' 		    => $value['stock_num'],	//商品库存
				'today_sell_count' 	=> $value['today_sell_count'],	//商品今日销量
				'reply_count' 		=> $value['reply_count'],		//商品评论数量
				'packing_charge' 	=> $value['packing_charge'],	//商品打包费
				'number' 			=> $value['number'],			//商品条码数值
				'min_num' 			=> $value['min_num'],			//商品最少购买数量
				'max_num' 			=> $value['max_num'],				//商品最多购买数量
				'has_format' 		=> ($value['spec_value'] || $value['is_properties']) ? true : false,//商品是否有规格属性
				'has_spec'   		=> $value['spec_value'] ? true : false, //商品是否有规格
				'is_new' 			=> $value['is_new'],			//是否新品
				'product_sale' 		=> $value['sell_count'] + $value['shop_virtual_sales'],		//商品销量
				'product_reply' 	=> $value['reply_count'],		//好评数
				'is_subsidiary_goods' 	=> $value['is_subsidiary_goods'], //1有附属商品 0没有附属商品
				'unit_price' 		=> $value['unit_price'],
				'sell_type' 		=> $value['sell_type'],
				'weight'            => $value['weight'],
				'weight_unit'       => $value['weight_unit'],
				'have_combined_package' => D('Shop_goods')->haveCombinedPackage($value['goods_id']),
				'vip_show' => $levelDiscount['show_level_price'] ? true : false,
				'vip_tag' => M('User_level')->where(['level'=>$levelDiscount['level']])->getField('lname'),
				'vip_price' => $vip_price,
				'vip_unit_price' => $vip_price,
				'shop_video_url' => $value['shop_video_url'] ? file_domain().'/'.$value['shop_video_url'] : '',
				'single_no_delivery' => $value['single_no_delivery'] ?? 0,
				'score_percent' => $value['score_percent'],
			);
			//消费一元获得积分
			$buy_can_get_score = D('Goods_score')->getGoodsScore($productList[$key]['product_price'],'shop',$productList[$key]['has_spec'],$value['score_percent']);
			$productList[$key]['buy_can_get_score'] = $buy_can_get_score?$buy_can_get_score['score']:0;
			$productList[$key]['buy_can_get_score_txt'] = $buy_can_get_score?$buy_can_get_score['score_txt']:'';
		}
		$productList = array_values($productList);
		return $productList;
	}

	//商品详情
	public function ajax_goods(){
		$goods_id = isset($_POST['goods_id']) ? intval($_POST['goods_id']) : 1;
		$activity_id = isset($_POST['activity_id']) ? intval($_POST['activity_id']) : 0;
		if (C('customization.life_tools') && $activity_id) {
			M('Cart')->where(['uid' => $this->_uid, 'goods_id' => ['neq', $goods_id]])->delete(); //员工专区活动商品进详情页，清购物车
		}
		$isShopGoods = (new Shop_goodsModel())->isShopOrMallGoods($goods_id, true);
		if (!$isShopGoods) {
			$this->returnCodeError(L_('该商品只能在' . C('config.mall_alias_name') . '购买'));
		}

		$now_goods = D('Shop_goods')->get_goods_by_id($goods_id);

		if (C('customization.life_tools')) {
			$now_goods['mer_id'] = D('Merchant_store')->where(['store_id' => $now_goods['store_id']])->getField('mer_id');
			$setEmployeeCardMerId = C('config.set_employee_card_mer_id') ? explode(',',C('config.set_employee_card_mer_id')) : [];
			if (in_array($now_goods['mer_id'],$setEmployeeCardMerId) && $now_goods['open_employee_card'] == 1) { //已开启开启员工专卖商品，且用户标签不对应的不能购买
				$can_buy = 0;
				if ($this->_uid && $now_goods['employee_lables']) {
					$employee_card_user = D('Employee_card_user')->where(['uid' => $this->_uid, 'mer_id' => $now_goods['employee_mer_id'], 'status' => 1])->field('lable_ids')->find();
					if (!empty($employee_card_user['lable_ids'])) {
						$lable_ids = explode(',', $employee_card_user['lable_ids']);
						$goods_lable_ids = explode(',', $now_goods['employee_lables']);
						if (array_intersect($lable_ids, $goods_lable_ids)) { //存在交集可以购买
							$can_buy = 1;
						}
					}
				}
				if ($can_buy == 0) {
					$this->returnCodeError(L_('该商品只能指定标签的员工才可购买'));
				}
			}
		}

        //当前会员等级折扣优惠
        $levelDiscount = (new Merchant_store_shopModel())->getVipLevelDiscount($this->_uid, $now_goods['store_id'], 'shop');
        $now_goods['product_id'] = $goods_id;

        $now_goods['vip_show'] = $levelDiscount['show_level_price'] ? true : false;
        $now_goods['vip_tag'] = M('User_level')->where(['level'=>$levelDiscount['level']])->getField('lname');
        $now_goods['vip_price'] = getFormatNumber($now_goods['price'] * $levelDiscount['discount'] - $levelDiscount['discount_price']);
        $now_goods['vip_unit_price'] = getFormatNumber($now_goods['unit_price'] * $levelDiscount['discount'] - $levelDiscount['discount_price']);
        
		$sort_info = D(_view('Shop_goods_sort'))->field(true)->where(['sort_id'=>$now_goods['sort_id'],'store_id'=>$now_goods['store_id']])->find();
		if($sort_info){
			$sort_info['sort_discount'] = round($sort_info['sort_discount']/10,2);
			$sort_info['sort_discount'] = $sort_info['sort_discount']>10||$sort_info['sort_discount']<0?0:$sort_info['sort_discount'];
			if($sort_info['sort_discount']>0){
				$sort_dis_price = getFormatNumber($sort_info['sort_discount']*$now_goods['o_price']/10,2,false);
				$now_goods['price'] = $sort_dis_price;
				if($levelDiscount['show_level_price']){
					$vip_price = $sort_dis_price - ($now_goods['o_price'] - $now_goods['vip_price']);
					$now_goods['vip_price'] = $vip_price<0?0:getFormatNumber($vip_price,2,false);
				}
			}
		}

        foreach ($now_goods['spec_list'] as &$value) {
            $value['list'] = array_values($value['list']);
        }


		if($now_goods['spec_list']){
			foreach($now_goods['spec_list'] as &$value){
				$value['id_'] = $value['id'];
				unset($value['id']);
				foreach($value['list'] as &$v){
					$v['id_'] = $v['id'];
					unset($v['id']);
				}
			}
			$now_goods['spec_list'] = array_values($now_goods['spec_list']);
		}else{
			$now_goods['spec_list'] = array();
		}

		if($now_goods['properties_list']){
			foreach($now_goods['properties_list'] as &$value){
				$value['id_'] = $value['id'];
				unset($value['id']);
			}
			$now_goods['properties_list'] = array_values($now_goods['properties_list']);
            //删除未开启状态的属性
            foreach ($now_goods['properties_list'] as $key => $item) {
                foreach ($item['val_status'] as $ikey => $ival) {
                    if (!isset($ival[1]) || $ival[1] == 0) {
                        unset($item['val_status'][$ikey]);
                    }
                }
                $now_goods['properties_list'][$key]['val_status'] = array_values($item['val_status']);
            }
		}else{
			$now_goods['properties_list'] = array();
		}

		if($now_goods['list']){
			foreach($now_goods['list'] as &$value){
				if ($now_goods['is_seckill_price']) {
					$value['price'] = strval(floatval($value['seckill_price'] > 0 ? $value['seckill_price'] : $value['price']));
				} else {
				    $value['price'] = strval(floatval($value['price']));
				}

				if ($now_goods['vip_show']) {
					$value['vip_price'] = getFormatNumber($value['price'] * $levelDiscount['discount'] - $levelDiscount['discount_price']);
				}else{
					$value['vip_price'] = $value['price'];
				}
				if($value['properties']){
					foreach($value['properties'] as &$v){
						$v['id_'] = $v['id'];
						unset($v['id']);
					}
				}else{
					$value['properties'] = array();
				}
				
				if($sort_info&&$sort_info['sort_discount']>0){
					$val_sort_dis_price = getFormatNumber($sort_info['sort_discount']*$value['price']/10,2,false);
					$value['price'] = $val_sort_dis_price;
					if($levelDiscount['show_level_price']){
						$val_vip_price = $sort_dis_price - ($value['price'] - $value['vip_price']);
						$value['vip_price'] = $val_vip_price<0?0:getFormatNumber($val_vip_price,2,false);
					}
				}
				//消费一元获得积分
				$buy_can_get_score = D('Goods_score')->getGoodsScore($value['price'],'shop');
				$value['buy_can_get_score'] = $buy_can_get_score?$buy_can_get_score['score']:0;
				$value['buy_can_get_score_txt'] = $buy_can_get_score?$buy_can_get_score['score_txt']:'';
			}
		}else{
			$now_goods['list'] = NULL;
		}
		$now_goods['content'] = '';
        if ($now_goods['des']) {
            if($_POST['Device-Id'] != 'wxapp'){
                $src = '<img style="max-width: 100%!important;vertical-align: middle;" src="'.file_domain().'/';
                $now_goods['des'] = str_replace('<img src="/', $src, $now_goods['des']);
                $now_goods['des'] = str_replace('<img src="'.$this->config['site_url'].'/', $src, $now_goods['des']);
                $now_goods['des'] = str_replace('<img alt="" src="/', $src, $now_goods['des']);

				$now_goods['des'] = str_replace('<img src="http', '<img style="max-width: 100%!important;vertical-align: middle;" src="http', $now_goods['des']);
				$now_goods['des'] = str_replace('<img alt="" ', '<img alt="" style="max-width:100%;vertical-align:top;" ', $now_goods['des']);

            }else{
				$now_goods['des'] = preg_replace('/<img(.*)style=/U', '<img$1_style=', $now_goods['des']);
                $src = '<img style="max-width:100%;vertical-align:top;" src="'.file_domain().'/';
                $now_goods['des'] = str_replace('<img src="/', $src, $now_goods['des']);
                $now_goods['des'] = str_replace('<img src="'.$this->config['site_url'].'/', $src, $now_goods['des']);
                $now_goods['des'] = str_replace('<img src="'.str_replace('https://','http://',$this->config['site_url']).'/', $src, $now_goods['des']);
                $now_goods['des'] = str_replace('<img alt="" src="/', $src, $now_goods['des']);
                $now_goods['des'] = str_replace('<img src="', '<img style="max-width:100%;vertical-align:top;" src="', $now_goods['des']);
                $now_goods['des'] = str_replace('<img alt="" ', '<img alt="" style="max-width:100%;vertical-align:top;" ', $now_goods['des']);
            }
        }
		
		$now_goods['pic_empty'] = false;
		if(empty($now_goods['pic_arr']) && C('config.goods_default_image')){
			$now_goods['pic_empty'] = true;
			$now_goods['pic_arr'][0]['url'] = C('config.goods_default_image');
		}

		$store = M('Merchant_store_shop')->field('store_id, store_theme')->where(array('store_id' => $now_goods['store_id']))->find();
		
		$now_goods['share_title'] = $now_goods['name'];
		$now_goods['share_image'] = $now_goods['pic_arr'][0]['url'];
		$now_goods['share_content'] = L_('感觉X1不错，分享给你看看',['X1'=>'「' . $now_goods['name'] . '」']);
		
        // 验证是否有三级分类
        $store['is_mult_class'] = D('Merchant_store_shop')->checkMultClass($store['store_id']);

		if ($store['store_theme'] && empty($store['is_mult_class'])) {
		    $now_goods['share_url'] = $this->config['site_url'] . '/wap.php?c=Shop&a=classic_shop&shop_id=' . $store['store_id'] . '&goods_id=' . $now_goods['goods_id'];
		} else {
		    $now_goods['share_url'] = $this->config['site_url'] . '/wap.php?g=Wap&c=Shop&a=index&shop-id=' . $store['store_id'] . '&good-id=' . $now_goods['goods_id'];
		}
		
		$now_goods['share_wx'] = ($this->config['pay_wxapp_important'] && $this->config['pay_wxapp_username']) ? 'wxapp' : 'h5';
		if($now_goods['share_wx'] == 'wxapp'){
			$now_goods['share_wx_info'] = [
				'userName' 	=> $this->config['pay_wxapp_username'],
				'path' 		=> '/pages/shop_new/goodsDetail/goodsDetail?store_id=' . $store['store_id'] . '&product_id=' . $now_goods['goods_id'],
				'image' 	=> $now_goods['share_image'],
				'title' 	=> $now_goods['share_content'],
			];
		}

		$store['shop_fitment_color'] = $store['shop_fitment_color'] ? $store['shop_fitment_color'] : '113533'; //背景色
		$store['shop_fitment_front_color'] = 'ffffff'; //标题色

		//商品小程序二维码
		$now_goods['wxapp_qrcode'] = $this->config['site_url'].'/index.php?c=Recognition_wxapp&a=create_unlimit_qrcode&page=pages/shop_new/goodsDetail/goodsDetail&scene='.urlencode('id='.$now_goods['goods_id'].'&store_id='.$now_goods['store_id']) . ($_POST['wxapp_own_mer_id'] ? '&mer_id=' . $_POST['wxapp_own_mer_id'] : '');

		if($now_goods['seckill_stock'] && $now_goods['stock_num']){
			$now_goods['seckill_stock'] = $now_goods['stock_num'];
		}
		
		$now_goods['image'] = thumb($now_goods['image'],750,1000);
		$now_goods['product_image'] = $now_goods['image'];
		$now_goods['share_image'] = thumb($now_goods['share_image'],750,1000);
		foreach($now_goods['pic_arr'] as $pic_key=>$pic_value){
			$now_goods['pic_arr'][$pic_key]['url'] = thumb($pic_value['url'],750,1000);
		}

		unset($now_goods['old_price']);
		unset($now_goods['seckill_open_time']);
		unset($now_goods['seckill_close_time']);
		unset($now_goods['last_time']);
		unset($now_goods['sort']);
		unset($now_goods['status']);
		unset($now_goods['print_id']);
		unset($now_goods['sell_day']);
		unset($now_goods['spec_value']);
		unset($now_goods['is_properties']);
		unset($now_goods['today_sell_spec']);
		unset($now_goods['today_seckill_count']);
		unset($now_goods['cat_fid']);
		unset($now_goods['cat_id']);
		unset($now_goods['freight_type']);
		unset($now_goods['freight_value']);
		unset($now_goods['freight_template']);
		unset($now_goods['extra_pay_price']);
		unset($now_goods['cost_price']);
		unset($now_goods['original_goods_id']);
		unset($now_goods['relation_map']);
		unset($now_goods['original_stock']);
		//unset($now_goods['score_percent']);
		unset($now_goods['score_max']);
		unset($now_goods['zbwId']);
		unset($now_goods['show_start_time']);
		unset($now_goods['show_end_time']);
		//unset($now_goods['limit_type']);
		unset($now_goods['json']);
		unset($now_goods['val_status']);
		unset($now_goods['properties_status_list']);
		unset($now_goods['deliver_fee']);
		$now_goods['seckill_discount'] = $now_goods['o_price'] ? round($now_goods['price']/$now_goods['o_price']*10,1) : 10;

        // 如果有附属商品就获取商品区块列表,关联的附属商品等信息
        $now_goods['subsidiary_piece'] = array();
        if ($now_goods['is_subsidiary_goods'] == 1) {
            $subsidiaryPieceGoods = D('Shop_goods')->get_Subsidiary_Piece($now_goods['goods_id']);
            foreach ($subsidiaryPieceGoods as $subk => $subv) {
                foreach ($v['goods'] as $subk2 => $subv2) {
                    $subsidiaryPieceGoods[$subk]['goods'][$subk2]['vip_price'] = getFormatNumber($subk2['price'] * $levelDiscount['discount']);
                }
            }
            $now_goods['subsidiary_piece'] = $subsidiaryPieceGoods;
        }
		$now_goods['sell_count'] = $now_goods['sell_count'] + $now_goods['shop_virtual_sales'];
        // 组合套餐
        $now_goods['combination_list'] = D('Shop_goods')->get_combination_list($goods_id,$this->_uid,true);

        //浏览量增加
        $shop_goods_views = D('Shop_goods_views')->where('goods_id = ' . $goods_id)->find();
        if($shop_goods_views){
            D('Shop_goods_views')->where('goods_id = ' . $goods_id)->data(['count'=>$shop_goods_views['count'] + 1])->save();
        }else{
            D('Shop_goods_views')->add(['goods_id' => $goods_id, 'count' => 1]);
        }
        // //增加系统产品库数据
        $system_goods = D('System_goods')->where('number = '.$now_goods['number'])->find();
        if($system_goods){  
            $browse_num = $system_goods['browse_num'] + 1;
            $sql = "UPDATE `".C('DB_PREFIX')."system_goods` SET `browse_num` = ". $browse_num ." WHERE ( `goods_id` = ".$system_goods['goods_id']." )";
            M('System_goods')->execute($sql);
        }
        
		//begin 增加店铺信息返回
		$storeId = $now_goods['store_id'] ?? 0;
		$lat = $_POST['lat'] ?? '';
		$lng = $_POST['lng'] ?? '';
		$storeInfo = D('Merchant_store')
			->alias('ms')->join(C('DB_PREFIX') . 'merchant_store_shop ss ON ms.store_id = ss.store_id')
			->where(['ms.store_id' => $storeId])
			->field('ms.store_id,ms.name,ms.logo,ms.pic_info,ms.long,ms.lat,ms.adress,ss.score_mean')
			->find();
		if ($storeInfo['logo']) {
			$storeInfo['logo'] = thumb_img(replace_file_domain($storeInfo['logo']), 240, 240, 'fill');
		} else if ($storeInfo['pic_info']) {
			$images = explode(';', $storeInfo['pic_info']);
			$storeInfo['logo'] = thumb_img(replace_file_domain($images[0]), 240, 240, 'fill');
		}
		$distance = 0;
		if (abs($lat) > 0 && abs($lng) > 0 && abs($storeInfo['long']) > 0 && abs($storeInfo['lat']) > 0) {
			$distance = getDistance($lat, $lng, $storeInfo['lat'], $storeInfo['long']);
		}
		$storeInfo['distance'] = $distance > 0 ? getRange($distance, false, true) : '';
		$storeInfo['score_mean'] = number_format($storeInfo['score_mean'], 1);
		$storeInfo['url'] = get_base_url('pages/shop_new/shopDetail/shopDetail?store_id=' . $storeId);
		$now_goods['store'] = $storeInfo;
		//end
		
		//增加商品浏览数
		D('Shop_goods')->where(['goods_id'=>$goods_id])->setInc('hits',1);

		//消费一元获得积分
		$outside_buy_can_get_score = D('Goods_score')->getGoodsScore($now_goods['price'],'shop',($now_goods['list']?true:false),$now_goods['score_percent']);
		$now_goods['buy_can_get_score'] = $outside_buy_can_get_score?$outside_buy_can_get_score['score']:0;
		$now_goods['buy_can_get_score_txt'] = $outside_buy_can_get_score?$outside_buy_can_get_score['score_txt']:'';
		$this->returnCodeOk($now_goods);
	}

	public function near_offline_shop(){
		if(empty($_POST['lng']) || empty($_POST['lat'])){
			$this->returnCode(1001,array(),L_('请携带用户经纬度'));
		}
		
		$lat = isset($_POST['lat']) ? htmlspecialchars($_POST['lat']) : 0;
		$long = isset($_POST['lng']) ? htmlspecialchars($_POST['lng']) : 0;
		$page = isset($_POST['page']) ? intval($_POST['page']) : 1;
		$page = max(1, $page);
		$key = isset($_POST['key']) ? htmlspecialchars($_POST['key']) : '';
		
		$where = array('deliver_type' => 'offline', 'order' => 'only_juli', 'lat' => $lat, 'long' => $long,'page' => $page,'key'=>$key,'show_method'=>0);
		$merchantStoreShopDB = D('Merchant_store_shop');
 
        $lists = $merchantStoreShopDB->get_list_by_option($where,1);
		
		if($_POST['only_one']){
			if($lists['shop_list']){
				$only_one_shop = $lists['shop_list'][0];
				$only_one_shop = array(
					'store_id' 	=> $only_one_shop['store_id'],
					'name' 		=> $only_one_shop['name'],
					'adress' 	=> $only_one_shop['adress'],
					'range' 	=> $only_one_shop['range'],
					'image' 	=> $only_one_shop['image'],
				);
			}else{
				$only_one_shop = (object)array();
			}
			$this->returnCodeOk($only_one_shop);
		}else{
			if($lists['shop_list']){
				foreach($lists['shop_list'] as &$shop_value){
					$shop_value = array(
						'store_id' 	=> $shop_value['store_id'],
						'name' 		=> $shop_value['name'],
						'adress' 	=> $shop_value['adress'],
						'range' 	=> $shop_value['range'],
						'image' 	=> $shop_value['image'],
					);
				}
			}
			
			$this->returnCodeOk($lists);
		}
	}

	//店铺商品搜索
	public function shop_search(){
		$store_id = intval($_POST['store_id']);
		if(empty($store_id)){
			$this->returnCodeError('请携带 store_id 参数');
		}
		$page = intval($_POST['page']);
		if(empty($page)){
			$page = 1;
		}
		
		$key = safeValue(trim($_POST['key']));
		$where_goods = array(
			'store_id'	=> $store_id,
			'key'		=> $key,
			'page'		=> $page,
			'uid'		=> $this->_uid
		);
        $good_list = D('Shop_goods')->get_list_by_option($where_goods, 1, 1, false, 1, true);
		
		foreach($good_list['goods_list'] as $key=>$good_value){
			if (!D('Shop_goods')->isShow($good_value['goods_id'], 13)) {
				unset($good_list['goods_list'][$key]);
				continue;
			}

            $good_value['product_sale'] = $good_value['sell_count'] + $good_value['shop_virtual_sales'];
			$good_value['product_id']	= $good_value['goods_id'];
			$good_value['product_name']	= $good_value['name'];
			$good_value['product_image']= $good_value['image'];
            $good_value['product_price']= $good_value['price'];
            if($good_value['unit_price']){
                $good_value['unit_price'] = getFormatNumber($good_value['unit_price']);
            }
			$good_value['stock']        = $good_value['stock_num'];
			$good_value['has_format']   = ($good_value['spec_value'] || $good_value['is_properties']) ? true : false;
			$good_value['has_spec']     = $good_value['spec_value'] ? true : false;
			$good_value['is_subsidiary_goods'] = $good_value['is_subsidiary_goods']; //1有附属商品 0没有附属商品;
			$good_value['product_describe'] = $good_value['describe'];
			$good_list['goods_list'][$key] = $good_value;
		}
		$good_list['goods_list'] = array_values($good_list['goods_list']);
		
		$this->returnCodeOk($good_list);
	}

	public function shop_barcode_search(){
		$store_id = intval($_POST['store_id']);
		$barcode = safeValue($_POST['barcode']);
		if(empty($store_id)){
			$this->returnCodeError('请携带 store_id 参数');
		}
		if(empty($barcode)){
			$this->returnCodeError(L_('条形码为空'));
		}
		
		$is_refresh = isset($_POST['refresh']) ? intval($_POST['refresh']) : 1;
		$product_list = D('Shop_goods')->get_list($store_id, $is_refresh);
		
		foreach($product_list as $key=>$value){
			foreach($value['goods_list'] as $k=>$v){
				if($v['number'] == $barcode){
					if($v['spec_arr']){
						foreach($v['spec_arr'] as $kk=>$vv){
							if($vv['spec_val_id']){
								unset($v['spec_arr'][$kk]);
								$v['spec_arr'][$kk] = array(
									'type' => 'spec',
									'name' => $vv['spec_val_name'],
									'spec_id' => $vv['spec_val_sid'],
									'id' => $vv['spec_val_id'],
								);
							}
						}
					}
					$this->returnCodeOk($v);
				}
			}
		}
		
		$this->returnCodeOk(array());
	}

	public function merchant_card(){
		$store_id = intval($_POST['store_id']);
		if(empty($store_id)){
			$this->returnCodeError('请携带 store_id 参数');
		}
		
		$condition_merchant_store['store_id'] = $_POST['store_id'];
        $now_store = M('Merchant_store')->field(true)->where($condition_merchant_store)->find();
		if(empty($now_store)){
			$this->returnCodeError(L_('店铺不存在'));
		}
		
		//查找会员卡
		$merchant_card = M(_view('Card_new'))->where(array('mer_id'=>$now_store['mer_id']))->find();
		
		$returnArr = array(
			'has_card' => false
		);
		if(empty($merchant_card) || empty($merchant_card['status'])){
			$this->returnCodeOk($returnArr);
		}
		$returnArr['has_card'] = true;
		$returnArr['card_url'] = $this->config['site_url'].'/wap.php?c=My_card&a=merchant_card&mer_id='.$now_store['mer_id'];
		
		if($this->_uid){
			$card = D('Card_userlist')->field(true)->where(array('uid' => $this->_uid, 'mer_id' => $now_store['mer_id']))->find();
			if(!empty($card)){
				$user = D('User')->field(true)->where(array('uid' => $card['uid']))->find();
				$user_card = array('name' => $user['truename'] ? $user['truename'] : $user['nickname'], 'sex' => $user['sex'] == 1 ? L_('男') : L_('女'), 'card_id' => $card['id'], 'phone' => $user['phone']);
				$user_card['card_money'] = $card['card_money'] + $card['card_money_give'];
				$user_card['card_score'] = $card['card_score'];
				$user_card['physical_id'] = $card['physical_id'];
				$user_card['uid'] = $user['uid'];
				$user_card['discount'] = empty($merchant_card['discount']) ? 10 : $merchant_card['discount'];

				$user_card['card_new'] = D('Card_new')->get_use_coupon_by_params($user['uid'], $now_store['mer_id'], 'shop');
				$returnArr['card_info'] = $user_card;
			}
		}
		$this->returnCode(0,$returnArr);
	}

    public function confirm()
    {
        //新版购物车
        $cartMod = M('Cart');
        $storeId = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
        $deliverExtraPrice = isset($_POST['deliverExtraPrice']) ? intval($_POST['deliverExtraPrice']) : 0;//配送附加费
        $cartid = isset($_POST['cartid']) ? htmlspecialchars(trim($_POST['cartid'])) : '';

        if ($cartid && ($orderTemp = D('Shop_order_temp')->field(true)->where(array('cartid' => $cartid, 'store_id' => $storeId))->find())) {
            $list = json_decode($orderTemp['info'], true);
            $productCart = [];
            foreach ($list as $rowset) {
                if (isset($rowset['data']) && $rowset['data']) {
                    $productCart = array_merge($productCart, $rowset['data']);
                }
            }
        } else {
            $cart = $cartMod->where(['uid' => $this->_uid, 'store_id' => $storeId, 'is_choose' => 1])->select();
            $productCart = [];
            foreach ($cart as $v) {
                $info = json_decode($v['info'], true);
                foreach ($info as &$i) {
                    if ($i['host_goods_id'] == 0) {
                        $i['count'] = $i['count'] * $v['num'];
                    }
                }
                $productCart = array_merge($productCart, $info);
            }
        }

        if (empty($productCart)) {
            $this->returnCode(10100003);
        }

        // 超值换购
        $rep_id = isset($_POST['rep_id']) ? intval($_POST['rep_id']) : 0;
        $rep_goods_id = isset($_POST['rep_goods_id']) ? $_POST['rep_goods_id'] : 0;
        if ($rep_id) { 
            $rep_return = D('Shop_goods')->addRepurchase($storeId, $rep_id, $rep_goods_id);
            fdump($rep_return,'confirm',1);
            if ($rep_return['error'] == 1) {
                $this->returnCode(1, null, $rep_return['msg']);
            }elseif ($rep_return['error'] == 2) {
                $this->returnCode(1, null, L_('抱歉！ 您选中的超值换购商品已被商家下架，暂时无法继续换购'));
            }elseif ($rep_return['error'] == 0) {
                $productCart = array_merge($productCart, $rep_return['info']);
            }
        }

        //选择升级VIP
        $isUpgradeVip = isset($_POST['upgrade_vip_level']) ? intval($_POST['upgrade_vip_level']) : 0;
        $upgradeVipLevel = 0;
        $upgradePrice = 0;
        $upgradeVipInfo = D('User')->getNextVipLevel($this->_uid, $storeId);
        if ($isUpgradeVip == 1) {
            if ($upgradeVipInfo) {
                $upgradeVipLevel = $upgradeVipInfo['level'];
				$upgradePrice = getFormatNumber($upgradeVipInfo['use_money']);
            }
        }

		//是否选中开通平台会员卡
		$isOpenMembershipCard = isset($_POST['open_membership_card']) ? intval($_POST['open_membership_card']) : 0;

        //购物车校验
        $return = D('Shop_goods')->checkCart($storeId, $this->_uid, $productCart, 1, 0, false, 1, null, null, null, $upgradeVipLevel);
        // var_dump($rep_return['info']);
        // var_dump($return);
        if ($return['error_code']) {
            $this->returnCode(1, null, $return['msg']);
        }
        //记录商品总金额+打包费给满减活动使用
        $goodsTotalMoney = 0;
        $goodsIds = [];
        foreach ($return['goods'] as $v) {
            $goodsTotalMoney += $v['num'] * $v['discount_price'];
            $goodsIds[]=$v['goods_id'];
        }

        $checkRs = D('Shop_goods')->checkBeforePay($goodsIds);
        if ($checkRs['error_no']) {
            $this->returnCode(1, null, $checkRs['error_msg']);
        }

        $cartid = isset($_POST['cartid']) ? htmlspecialchars(trim($_POST['cartid'])) : '';
        $invoice_head = isset($_POST['invoice_head']) ? htmlspecialchars($_POST['invoice_head']) : '';  //cue_field
        $order_from = isset($_POST['order_from']) ? intval($_POST['order_from']) : 3;
        $address_id = isset($_POST['address_id']) ? intval($_POST['address_id']) : 0;
        $pick_id = isset($_POST['pick_id']) ? htmlspecialchars($_POST['pick_id']) : 0;
        $deliver_type = isset($_POST['deliver_type']) ? intval($_POST['deliver_type']) : 0;
        $arrive_time = isset($_POST['expect_use_time']) ? htmlspecialchars($_POST['expect_use_time']) : '';
        $arrive_time = str_replace(L_("今天"), date('Y-m-d'), $arrive_time);
        $arrive_time = str_replace(L_("明天"), date('Y-m-d', strtotime('+1 day')), $arrive_time);
        $arrive_time = str_replace(L_("后天"), date('Y-m-d', strtotime('+2 day')), $arrive_time);
        $note = isset($_POST['desc']) ? htmlspecialchars($_POST['desc']) : '';
        $is_right_now = isset($_POST['is_right_now']) ? intval($_POST['is_right_now']) : 0;//是否立即送达时间段
        $extraPrice = 0;
        if (floatval($return['price'] + $return['packing_charge']) < $return['store']['basic_price']) {
            $extraPrice = $return['store']['extra_price'];
            if (empty($extraPrice)) {
                if (in_array($return['store']['deliver_type'], array(2, 3, 4))) {
                    $deliver_type = 1;
                } else {
                    $this->returnCode(1, null, L_('订单没有达到起送价，不予配送'));
                }
            }
        }
        if ($deliver_type != 1) {//配送方式是：非自提和非快递配送
            if ($user_address = D('User_adress')->field(true)->where(array('adress_id' => $address_id, 'uid' => $this->_uid))->find()) {
                if ($user_address['longitude'] != 0 && $user_address['latitude'] != 0) {
                    $distance = getDistance($user_address['latitude'], $user_address['longitude'], $return['store']['lat'], $return['store']['long']);
                    if ($return['store']['delivery_range_type'] == 0) {
                        $delivery_radius = $return['store']['delivery_radius'] * 1000;
                        if ($distance > $delivery_radius && $return['delivery_type'] != 5) {
                            $this->returnCode(1, null, L_('已有地址均不在配送范围'));
                        }
                    } else {
                        if ($return['store']['delivery_range_polygon']) {
                            if (!isPtInPoly($user_address['longitude'], $user_address['latitude'], $return['store']['delivery_range_polygon']) && $return['delivery_type'] != 5) {
                                $this->returnCode(1, null, L_('您的地址不在本店指定的配送区域'));
                            }
                        } else {
                            $this->returnCode(1, null, '您的地址不在本店指定的配送区域');
                        }
                    }


                    $province = D('Area')->get_area_by_areaId($user_address['province'], false);
                    $user_address['province_txt'] = $province['area_name'];

                    $city = D('Area')->get_area_by_areaId($user_address['city'], false);
                    $user_address['city_txt'] = $city['area_name'];

                    $area = D('Area')->get_area_by_areaId($user_address['area'], false);
                    $user_address['area_txt'] = $area['area_name'];
                    $user_address['zip_code'] = $area['zip_code'];
                } else {
                    $this->returnCode(1, null, L_('您选择的地址没有完善，请先编辑地址，点击“点击选择位置”进行完善'));
                }
            }
        }
        $user_info = D('User')->where(array('uid' => $this->_uid))->find();
        $now_time = time();
        $order_data = array();
        $order_data['mer_id'] = $return['mer_id'];
        $order_data['store_id'] = $return['store_id'];
        $order_data['uid'] = $this->_uid;
        $order_data['is_tmp_order'] = $_POST['is_tmp_order'] ? true : false;    //临时订单号
        $order_data['tmp_order_id'] = $_POST['tmp_order_id'] ? intval($_POST['tmp_order_id']) : 0;    //临时订单号
        $order_data['desc'] = $note;
        $order_data['create_time'] = $now_time;
        $order_data['last_time'] = $now_time;
        $order_data['invoice_head'] = $invoice_head;
        $order_data['village_id'] = intval($_POST['village_id']);
        $order_data['num'] = $return['total'];
        $order_data['packing_charge'] = $return['packing_charge'];//打包费
        $order_data['merchant_reduce'] = $return['sto_first_reduce'] + $return['sto_full_reduce'];//店铺优惠
        $order_data['merchant_reduce_total'] = $return['sto_first_reduce'] + $return['sto_full_reduce'];//所有店铺优惠
        //$order_data['balance_reduce'] = $return['sys_first_reduce'] + $return['sys_full_reduce'];//平台优惠
        $orderid = strlen($this->_uid) <= 8 ? date('ymdhis') . substr(microtime(), 2, 8 - strlen($this->_uid)) . $this->_uid : date('ymdhis') . substr($this->_uid,0,8);
        $order_data['real_orderid'] = $orderid;
        $order_data['no_bill_money'] = 0;//无需跟平台对账的金额
        $diffTime = 60;
        if ($return['store']['send_time_type'] == 1) {
            $diffTime = 3600;
        } elseif ($return['store']['send_time_type'] == 2) {
            $diffTime = 86400;
        } elseif ($return['store']['send_time_type'] == 3) {
            $diffTime = 86400 * 7;
        } elseif ($return['store']['send_time_type'] == 4) {
            $diffTime = 86400 * 30;
        }

        if ($deliver_type == 1) {//自提
            $extraPrice = 0;
            if (empty($pick_id)) {
                if(!C('customization.life_tools') || ($this->is_app_browser && $this->app_version < 30450) ){// 定制不默认选中自提地址 所以去掉报错
                    $this->returnCode(1, null, '抱歉!该商家暂未添加自提点,无法购买');
                }
            } else {
                $pre = substr($pick_id, 0, 1);
                $pick_id = substr($pick_id, 1);
                if ($pre == 's') {
                    if ($store = D('Merchant_store')->field(true)->where(array('store_id' => $pick_id))->find()) {
                        $area[] = $store['province_id'];
                        $area[] = $store['city_id'];
                        $area[] = $store['area_id'];
                        $pick_addr = array('name' => $store['adress'], 'area_info' => array('province' => $store['province_id'], 'city' => $store['city_id'], 'area' => $store['area_id']), 'pick_addr_id' => 's' . $store['store_id'], 'phone' => $store['phone'], 'long' => $store['long'], 'lat' => $store['lat'], 'addr_type' => 1);
                    } else {
                        $this->returnCode(1, null, L_('不存在的自提点'));
                    }
                } else {
                    if ($address_p = M('Pick_address')->field(true)->where(array('id' => $pick_id))->find()) {
                        $area[] = $address_p['province_id'];
                        $area[] = $address_p['city_id'];
                        $area[] = $address_p['area_id'];
                        $pick_addr = array('name' => $address_p['pick_addr'], 'area_info' => array('province' => $address_p['province_id'], 'city' => $address_p['city_id'], 'area' => $address_p['area_id']), 'pick_addr_id' => 'p' . $address_p['id'], 'phone' => $address_p['phone'], 'long' => $address_p['long'], 'lat' => $address_p['lat'], 'addr_type' => 2);
                    } else {
                        $this->returnCode(1, null, '抱歉!该商家暂未添加自提点,无法购买');
                    }
                }
                $where['area_id'] = array('in', implode(',', $area));
                $area_name = M('Area')->where($where)->getField('area_id,area_name,zip_code');
                $pick_addr['area_info']['province'] = $area_name[$pick_addr['area_info']['province']]['area_name'];
                $pick_addr['area_info']['city'] = $area_name[$pick_addr['area_info']['city']]['area_name'];
                $pick_addr['area_info']['zip_code'] = $area_name[$pick_addr['area_info']['area']]['zip_code'];
                $pick_addr['area_info']['area'] = $area_name[$pick_addr['area_info']['area']]['area_name'];
            }

            $order_data['is_pick_in_store'] = 2;//配送方式 0：平台配送，1：商家配送，2：自提，3:快递配送
            $delivery_fee = $order_data['freight_charge'] = 0;//运费

            $order_data['username'] = isset($_POST['username']) && $_POST['username'] ? $_POST['username'] : '';
            $order_data['userphone'] = isset($_POST['userphone']) && $_POST['userphone'] ? $_POST['userphone'] : '';

            //格式化自提地址
            if (!$this->config['open_multilingual']) {
                $order_data['address'] = $pick_addr['name'];
            } else if ($this->config['country_code'] == 'au') {
                $order_data['address'] = $pick_addr['name'] . ' ' . $pick_addr['area_info']['area'] . ', ' . $pick_addr['area_info']['city'] . ' ' . $pick_addr['area_info']['province'] . ' ' . $pick_addr['area_info']['zip_code'];
            } else {
                $order_data['address'] = $pick_addr['area_info']['province'] . ' ' . $pick_addr['area_info']['city'] . ' ' . $pick_addr['area_info']['area'] . ' ' . $pick_addr['name'];
            }

            $order_data['address_id'] = 0;
            if ($pre == 's') {
                $order_data['pick_id'] = 0;
            } else {
                $order_data['pick_id'] = $pick_id;
            }
            $order_data['expect_use_time'] = strtotime($_POST['expect_use_time']);//客户选择的自提时间
            //$order_data['expect_use_time'] = time() + $return['store']['work_time'] * $diffTime;//客户期望使用时间
            $order_data['lat'] = $pick_addr['lat'];
            $order_data['lng'] = $pick_addr['long'];
        } else {//配送
            $order_data['username'] = $user_address['name'];
            $order_data['userphone'] = $user_address['phone'];
            if (!$this->config['open_multilingual']) {
                $order_data['address'] = $user_address['adress'] . ' ' . $user_address['detail'];
            } else if ($this->config['country_code'] == 'au') {
                $order_data['address'] = trim(($user_address['detail'] ? $user_address['detail'] . ', ' : '') . $user_address['adress'] . ', ' . $user_address['area_txt'] . ' ' . $user_address['province_txt'] . ' ' . $user_address['zip_code']);
            } else {
                $order_data['address'] = trim(($user_address['detail'] ? $user_address['detail'] . ', ' : '') . $user_address['adress'] . ', ' . $user_address['area_txt'] . ' ' . $user_address['city_txt']);
            }

            $order_data['address_id'] = $address_id;
            $order_data['lat'] = $user_address['latitude'];
            $order_data['lng'] = $user_address['longitude'];
            $start_time = $return['store']['delivertime_start'];
            $stop_time = $return['store']['delivertime_stop'];

            $start_time2 = $return['store']['delivertime_start2'];
            $stop_time2 = $return['store']['delivertime_stop2'];

            $start_time3 = $return['store']['delivertime_start3'];
            $stop_time3 = $return['store']['delivertime_stop3'];

            if ($start_time == $stop_time && $start_time == '00:00:00') {
                $stop_time = '23:59:59';
            }

            if ($arrive_time == 0) {
                //if ($return['delivery_type'] != 5) $this->returnCode(1, null, L_('请选择配送时间！'));
            } else {
                $arrive_time = strtotime($arrive_time);
            }

            $order_data['expect_use_time'] = $arrive_time ? $arrive_time : time() + $return['store']['send_time'] + $return['store']['work_time'] * $diffTime;//客户期望使用时间
            
            //客户期望使用时间是否为立即送达时间
            if ($order_data['expect_use_time'] == (time() + $return['store']['send_time'] + $return['store']['work_time'] * $diffTime)) {
                $is_right_now = 1;
            }
            $order_data['is_right_now'] = $is_right_now;

            //计算配送费
            $distance = 0;
            if($user_address){
				if (C('config.is_riding_distance') || C('config.map_config') == 'google') {
					import('@.ORG.longlat');
					$longlat_class = new longlat();
					$distance = $longlat_class->getRidingDistance($return['store']['lat'], $return['store']['long'], $user_address['latitude'], $user_address['longitude']);
					if ($distance == -1) $this->returnCode(1, null, L_('请求不到您的具体定位，请重试！'));
				}
				$distance || $distance = getDistance($return['store']['lat'], $return['store']['long'], $user_address['latitude'], $user_address['longitude']);
				$order_data['distance'] = $distance;
			}

            $distance = $distance / 1000;
            $return['distance'] = $distance;
            if ($return['delivery_type'] == 5) {//快递配送
                $pass_distance = $distance > $return['basic_distance'] ? floatval($distance - $return['basic_distance']) : 0;
                $return['delivery_fee'] += round($pass_distance * $return['per_km_price'], 2);

                // 存在折扣优惠
                if ($return['discount_list']['delivery_zhekou']) {
                    D('Shop_goods')->delivery_discount($return, $return['delivery_fee'], 1, $this->app_version);
                }
                $order_data['freight_charge_original'] = $return['delivery_fee'];
                $return['delivery_fee'] = $return['delivery_fee'] - $return['delivery_fee_reduce'];
                $return['delivery_fee'] = $return['delivery_fee'] > 0 ? $return['delivery_fee'] : 0;
                $delivery_fee = $order_data['freight_charge'] = $return['delivery_fee'];//运费
            } else {

                //获取期望送达时间计算出来的合理接单时间
                if (getFormatNumber($return['deliver_speed_hour']) && $return['store']['send_time_type'] == 0 && isset($order_data['distance'])) {    //按时速计算
                    $send_time = $return['distance'] / $return['deliver_speed_hour'] * 3600 + ($return['deliver_delay_time'] * 60);
                } else {
                    $send_time = $return['store']['send_time'] * 60;
                }
                $addTime = $return['store']['work_time'] * $diffTime + $send_time;
                $tmp_expect_use_time = $order_data['expect_use_time'] - $addTime;
                $expect_use_time_temp = strtotime(date('Y-m-d ') . date('H:i:s', $tmp_expect_use_time));

                //匹配时间段获取配送费
                if (D('Deliver_set')->getDeliverTime($expect_use_time_temp, $start_time3, $stop_time3, 3)) {
                    //时间段三
                    $pass_distance = $distance > $return['basic_distance3'] ? floatval($distance - $return['basic_distance3']) : 0;
                    $return['delivery_fee3'] += round($pass_distance * $return['per_km_price3'], 2);

                    // 存在折扣优惠
                    if ($return['discount_list']['delivery_zhekou']) {
                        D('Shop_goods')->delivery_discount($return, $return['delivery_fee3'], 1, $this->app_version);
                    }
                    $order_data['freight_charge_original'] = $return['delivery_fee3'];
                    $return['delivery_fee3'] = $return['delivery_fee3'] - $return['delivery_fee_reduce'];
                    $return['delivery_fee3'] = $return['delivery_fee3'] > 0 ? $return['delivery_fee3'] : 0;
                    $delivery_fee = $order_data['freight_charge'] = $return['delivery_fee3'];//运费
                } elseif (D('Deliver_set')->getDeliverTime($expect_use_time_temp, $start_time2, $stop_time2, 2)) {
                    //时间段二
                    $pass_distance = $distance > $return['basic_distance2'] ? floatval($distance - $return['basic_distance2']) : 0;
                    $return['delivery_fee2'] += round($pass_distance * $return['per_km_price2'], 2);

                    // 存在折扣优惠
                    if ($return['discount_list']['delivery_zhekou']) {
                        D('Shop_goods')->delivery_discount($return, $return['delivery_fee2'], 1, $this->app_version);
                    }
                    $order_data['freight_charge_original'] = $return['delivery_fee2'];
                    $return['delivery_fee2'] = $return['delivery_fee2'] - $return['delivery_fee_reduce'];
                    $return['delivery_fee2'] = $return['delivery_fee2'] > 0 ? $return['delivery_fee2'] : 0;
                    $delivery_fee = $order_data['freight_charge'] = $return['delivery_fee2'];//运费
                } elseif (D('Deliver_set')->getDeliverTime($expect_use_time_temp, $start_time, $stop_time, 1)) {
                    //时间段一
                    $pass_distance = $distance > $return['basic_distance'] ? floatval($distance - $return['basic_distance']) : 0;
                    $return['delivery_fee'] += round($pass_distance * $return['per_km_price'], 2);

                    // 存在折扣优惠
                    if ($return['discount_list']['delivery_zhekou']) {
                        D('Shop_goods')->delivery_discount($return, $return['delivery_fee'], 1, $this->app_version);
                        $delivery_zhekou = $return['discount_list']['delivery_zhekou'];
                    }
                    $order_data['freight_charge_original'] = $return['delivery_fee'];
                    $return['delivery_fee'] = $return['delivery_fee'] - $return['delivery_fee_reduce'];
                    $return['delivery_fee'] = $return['delivery_fee'] > 0 ? $return['delivery_fee'] : 0;
                    $delivery_fee = $order_data['freight_charge'] = $return['delivery_fee'];//运费
                } else {
					if (!in_array($order_data['address_id'], [0, -1])) {
						$this->returnCode(1, null, L_('您选择的时间不在配送时间段内！'));
					}
                }
            }
            
			if (in_array($order_data['address_id'], [0, -1])) {
				$order_data['freight_charge'] = 0;
			}

            switch (intval($this->config['count_freight_charge_method'])) {
                case 0:
                    break;
                case 1:
                    $delivery_fee = $order_data['freight_charge'] = ceil($order_data['freight_charge'] * 10) / 10;
                    break;
                case 2:
                    $delivery_fee = $order_data['freight_charge'] = floor($order_data['freight_charge'] * 10) / 10;
                    break;
                case 3:
                    $delivery_fee = $order_data['freight_charge'] = round($order_data['freight_charge'] * 10) / 10;
                    break;
                case 4:
                    $delivery_fee = $order_data['freight_charge'] = ceil($order_data['freight_charge']);
                    break;
                case 5:
                    $delivery_fee = $order_data['freight_charge'] = floor($order_data['freight_charge']);
                    break;
                case 6:
                    $delivery_fee = $order_data['freight_charge'] = round($order_data['freight_charge']);
                    break;
            }
			$delivery_fee = $order_data['freight_charge'] = getFormatNumber($order_data['freight_charge']);

            $order_data['freight_charge_original'] = format_number_by_config($order_data['freight_charge_original']);

			//自动应用平台会员卡配送费满减
			$reduceFreightCharge = 0;
			$appendDiscount = [];
			if (($return['delivery_type'] == 0 || $return['delivery_type'] == 3) && $order_data['freight_charge'] > 0) {
				//平台配送
				$membershipCardMod = new Membership_cardModel();
				if ($isOpenMembershipCard) {
					//新开通平台配送费抵扣
					$membershipCard = $membershipCardMod->getCurrentMembershipCard();
					if ($membershipCard['discount_type'] & 2) {
						$reduceFreightCharge = $membershipCard['deliver_price'];
					}
				} else {
					//历史平台配送费抵扣
					$item = $membershipCardMod->getOneOrderDeliverItem($this->_uid);
					if ($item) {
						$reduceFreightCharge = $item['deliver_price'];
					}
				}
				$order_data['reduce_freight_charge'] = min($reduceFreightCharge, $order_data['freight_charge']);
				$delivery_fee -= $order_data['reduce_freight_charge'];
				$order_data['freight_charge'] -= $order_data['reduce_freight_charge'];
				$order_data['freight_charge'] = getFormatNumber($order_data['freight_charge']);
				if ($order_data['reduce_freight_charge'] > 0) {
					$appendDiscount[] = [
						'minus' => getFormatNumber($order_data['reduce_freight_charge']),
						'time_select' => 1,
						'type' => "delivery",
						'value' => L_('配送费立减X1', array('X1' => C('config.Currency_symbol') . getFormatNumber($order_data['reduce_freight_charge'])))
					];
				}
			}

			if ($order_data['address_id'] == 0) {
				$order_data['freight_charge'] = 0;
			}

            if ($return['delivery_type'] == 0 || $return['delivery_type'] == 3) {//平台配送
                $order_data['is_pick_in_store'] = 0;//配送方式 0：平台配送，1：商家配送，2：自提，3:快递配送
                $order_data['no_bill_money'] = $delivery_fee + $extraPrice + $return['merchant_reduce_deliver_money'];
                $order_data['merchant_reduce_total'] = $return['sto_first_reduce'] + $return['sto_full_reduce'] + $return['merchant_reduce_deliver_money'];//所有店铺优惠
            } elseif ($return['delivery_type'] == 1 || $return['delivery_type'] == 4) {
                $order_data['is_pick_in_store'] = 1;//配送方式 0：平台配送，1：商家配送，2：自提，3:快递配送
            } else {
                $order_data['is_pick_in_store'] = 3;//配送方式 0：平台配送，1：商家配送，2：自提，3:快递配送
            }
        }
        $hasReduceMoney = 0;
        if ($return['sys_first_reduce_type'] == 1 && $return['sys_first_reduce'] > $delivery_fee) {//配送费
            $order_data['balance_reduce'] = $delivery_fee + $return['sys_full_reduce'];//平台优惠
        } else {
            $order_data['balance_reduce'] = $return['sys_first_reduce'] + $return['sys_full_reduce'];//平台优惠
        }
        if (C('config.vip_level_discount_sequence') == 1) { //满减后计算
            if ($return['vip_discount'] < 100 && $return['vip_discount'] > 0) {
                if ($return['sorts_discount_type'] == 0) { //折上折
                    $tempMoney = round($return['vip_discount_money'] - round(($order_data['merchant_reduce'] + $order_data['balance_reduce']), 2), 2);
                    $hasReduceMoney = round($tempMoney - round($tempMoney * ($return['vip_discount'] * 0.01), 2), 2);
                    foreach ($return['goods'] as &$goods) {
                        $rate = sprintf('%.2f', $goods['price'] / $return['price']);
                        $rate = $rate ? $rate : 1;
                        $goods['discount_price'] = floatval(round($goods['discount_price'] - ($hasReduceMoney * $rate), 2));
                    }
                } else { //折扣最优
                    if ($return['optimalDiscountMoney'] > 0) {
                        $totalOptimalMoney = $return['optimalDiscountMoney'];
                        $return['vip_discount_money'] = $totalOptimalMoney;
                        $tempMoney = round($totalOptimalMoney - round(($order_data['merchant_reduce'] + $order_data['balance_reduce']), 2), 2);
                        $hasReduceMoney = round($tempMoney - round($tempMoney * ($return['vip_discount'] * 0.01), 2), 2);
                        foreach ($return['goods'] as &$goods) {
                            $rate = sprintf('%.2f', $goods['price'] / $return['price']);
                            $rate = $rate ? $rate : 1;
                            $goods['discount_price'] = floatval(round($goods['price'] - ($hasReduceMoney * $rate), 2));
                        }
                    }
                }
            }
            $return['vip_discount_money'] = bcsub($return['vip_discount_money'], $hasReduceMoney, 2);
            $return['discount_price'] = $return['vip_discount_money'];
            $return['can_discount_money'] = round($return['can_discount_money'] - $hasReduceMoney, 2);
            $order_data['vip_level_after'] = 1;//会员等级优惠满减后计算
        } else { //正常计算
            $hasReduceMoney = $return['vip_level_reduce_money'];
        }
        $order_data['cartid'] = $cartid;//拼单号
        $order_data['other_money'] = $extraPrice;//加价送的金额
        $order_data['order_from'] = $order_from;//订单来源:0：wap快店，1：wap商城，2：Android，3：ios,4:小程序,5：pc快店
        $order_data['goods_price'] = $return['price'];//商品的价格
        $order_data['extra_price'] = $return['extra_price'];//另外要支付的金额
        $order_data['can_discount_money'] = $return['can_discount_money'] + $return['packing_charge'] + $extraPrice;//可用商家优惠券的总价
        $order_data['discount_price'] = $return['vip_discount_money'];//商品折扣后的总价
        $order_data['vip_level_reduce_money'] = $hasReduceMoney;//会员等级折扣优惠金额
        $order_data['vip_level_discount'] = $return['vip_discount'];//会员等级折扣
        $order_data['total_price'] = $return['price'] + $delivery_fee + $return['packing_charge'] + $extraPrice;//订单总价  商品价格+打包费+配送费
        $order_data['price'] = $order_data['discount_price'] + $delivery_fee + $return['packing_charge'] - $order_data['merchant_reduce'] - $order_data['balance_reduce'] + $extraPrice;//实际要支付的价格
        // 超值换购商品
        if ($rep_id && $rep_return) {
            $order_data['price'] =  getFormatNumber($order_data['price'] + $rep_return['price']);
            $order_data['discount_price'] =  getFormatNumber($order_data['discount_price'] + $rep_return['price']);
        }
        // var_dump( $order_data['price']);

        if ($order_data['price'] < 0) {
            $order_data['price'] = 0;
        }
        $order_data['discount_detail'] = $return['discount_list'] ? serialize($return['discount_list']) : '';//优惠详情
        $order_data['reduce_stock_type'] = $return['store']['reduce_stock_type'];//'减库存类型（0：支付后，1：下单后）'

        //自定义字段
        if ($_POST['cue_field']) {
            $order_data['cue_field'] = is_array($_POST['cue_field']) ? serialize($_POST['cue_field']) : serialize(json_decode(htmlspecialchars_decode($_POST['cue_field']), true));
        }

        $order_data = D('Shop_order')->saveOrder($order_data, $return, $user_info, false);
        $tempOrderData = [
            'uid' => $this->_uid,
            'mer_id' => $order_data['mer_id'],
            'store_id' => $order_data['store_id'],
            'params' => json_encode($_POST, JSON_UNESCAPED_UNICODE),
            'order_data' => json_encode($order_data, JSON_UNESCAPED_UNICODE),
            'update_time' => time()
        ];
        $tempOrderDataId = M('Temp_order_data')->add($tempOrderData);

        //pay_check
		$order_type = I('type');
        $app_version = I('app_version');
        $app_type = I('app_type');
        $merchant_coupon_id = I('merchant_coupon_id');
        $system_coupon_id = I('system_coupon_id');
        $use_score = I('use_score');
        $use_merchant_money = I('use_merchant_money');
        $use_balance_money = I('use_balance_money');
        $use_sys_coupon = I('use_sys_coupon');
        $use_mer_coupon = I('use_mer_coupon');
        $use_qiye_money = I('use_qiye_money');
        $union_mer_id = I('union_mer_id');

		if ($system_coupon_id >= Membership_cardModel::HADPULL_BEGIN_ID && !$isOpenMembershipCard) {
			$system_coupon_id = 0;
		}

        $ticket = I('ticket', false);
        if ($ticket) {
            $info = ticket::get($ticket, $this->DEVICE_ID, true);
            $this->user_session['uid'] = $info['uid'];
        }
        if(empty($this->user_session)){
            $this->returnCode('20044010');
        }
        $now_user = D('User')->get_user($this->user_session['uid']);
        if (empty($now_user['phone']) && $this->DEVICE_ID == 'wxapp') {
            $need_bind_phone = true;

            //跑腿小程序独立支付，不校验手机号
            if ($this->user_session['from_device_id'] == 'wxapp_paotui') {
                $need_bind_phone = false;
            }
            if ($this->config['open_extra_price']) {
                $need_bind_phone = false;
            }
            if (!$this->config['cash_bind_phone']) {
                $need_bind_phone = false;
            }
            if ($need_bind_phone) {
                $this->returnCode('10070013');
            }

        }

        $group_pay_offline = true;
        $now_order = D('Shop_order')->get_pay_order($this->user_session['uid'], intval($_GET['order_id']), false, true, $order_data);

        if ($now_order['error'] == 1) {
            //小程序需要  20049999 来判断订单支付情况，做特殊页面操作
            if (stripos($now_order['msg'], L_('已经支付')) !== false) {
                if ($this->app_version >= 1295 && $now_order['url']) {
                    $now_order['url'] = str_replace('/appapi.php', '/wap.php', $now_order['url']);
                    if (stripos($now_order['url'], '/') === 0) {
                        $now_order['url'] = $this->config['site_url'] . $now_order['url'];
                    }
                    $this->returnCode('20049998', array('url' => $now_order['url'], 'msg' => $now_order['msg']));
                } else {
                    $this->returnCode('20049999', array(), $now_order['msg']);
                }
            }
            if ($now_order['url']) {
                $this->returnCode('1001', array(), $now_order['msg']);
            } else {
                $this->returnCode('1001', array(), $now_order['msg']);
            }
        }

        if (!$now_order['order_info']['order_txt_type']) {
            $now_order['order_info']['order_txt_type'] = '';
        }
        if (!$now_order['order_info']['order_num']) {
            if ($order_type == 'appoint') {
                $now_order['order_info']['order_num'] = 1;
            } else {
                $now_order['order_info']['order_num'] = 0;
            }
        }
        if (!$now_order['order_info']['order_price']) {
            $now_order['order_info']['order_price'] = 0;
        }
        if (!$now_order['order_info']['delivery_comment']) {
            $now_order['order_info']['delivery_comment'] = 0;
        }
        $order_info['order_name'] = html_entity_decode($now_order['order_info']['order_name']);
        $arr['order_info'] = $order_info = isset($now_order) ? $now_order['order_info'] : array();
        $arr['pay_config']['is_recharge'] = 0;
        if ($order_info['business_type'] == 'card_new_recharge' || $order_type == 'recharge') {
            $arr['pay_config']['is_recharge'] = 1;
        }
        //来自商城的活动（拼团、砍价）不允许使用平台/商家优惠券抵扣
        if ($order_info['order_from'] == 1 && ($order_info['group_id'] > 0 || $order_info['bar_id'] > 0)) {
            $order_info['discount_status'] = false;
        }
        if ($order_info['mer_id']) {
            $now_merchant = D('Merchant')->get_info($order_info['mer_id']);
            $use_discount = true;
            if ($order_info['discount_status'] === false) {
                $use_discount = false;
            }

            $use_discount && $arr['user_discount_type'] = $now_merchant['user_discount_type'];

            if ($this->config['open_score_discount'] && $use_discount) {
                switch ($now_merchant['user_discount_type']) {
                    case 1 :   //优惠
                        break;
                    case 2 :   //积分
                        $use_discount = false;
                        break;
                    case 3 :   //2选1
                        $use_discount = $_POST['discount_type'] == 'score' ? false : true;
                        break;
                }
            }
        } else {
            $arr['user_discount_type'] = 0;
        }

        //得到微信优惠金额,判断用户能否购买此团购
        $cheap_info = array('can_buy' => true, 'can_cheap' => false, 'wx_cheap' => 0);
        $arr['cheap_info'] = isset($cheap_info) ? $cheap_info : array();

        //用户信息
        if (($this->config['cash_nobind_phone_use_balance'] == 0 && empty($now_user['phone'])) || $now_merchant['open_sub_mchid'] && $now_merchant['sub_mch_id'] > 0 && $now_merchant['sub_mch_sys_pay'] != 1) {
            $now_user['now_money'] = 0;
        }

        if (empty($now_user)) {
            $this->returnCode('20045011');
        }

        $arr['now_user'] = isset($now_user) ? $now_user : array();
        $order_info['total_money'] = $order_info['order_total_money'];
        $tmp_order = $order_info;
        if ($order_info['order_type'] == 'store') {
            $tmp_order['total_money'] -= $tmp_order['no_discount_money'];
        } else if ($order_info['business_type'] == 'foodshop' && $order_info['order_info']['can_discount_money'] > 0) {
            $tmp_order['total_money'] = $order_info['order_info']['can_discount_money'];
        }
        $pay_infact = 0;
        //不同版本使用不同优惠券规则
        if (($this->config['cash_nobind_phone_use_balance'] || !empty($now_user['phone'])) && $order_type != 'recharge' && $order_type != 'integral_mall' && $order_type != 'weidian' && (empty($order_info['business_type']) || $order_info['business_type'] != 'card_new_recharge')) {
            //商家优惠券

            if ($this->DEVICE_ID == 'wxapp') {
                $platform = 'wxapp';
            } elseif ($this->is_weixin_browser) {
                $platform = 'weixin';
            } elseif ($this->is_app_browser) {
                $platform = 'app';
            } else {
                $platform = 'wap';
            }

            $merchant_balance = 0;

            // 获取联盟卡
            if ($now_order['order_info']['can_use_mer_union_card']) {
                // 使用联盟会员卡
                if ($_GET['union_mer_id'] && $union_mer_id != $order_info['mer_id']) {
                    // 获取联盟关系
                    $union_mer = D('Card_new')->get_union_merchant($_GET['union_mer_id'], $order_info['mer_id']);
                    if ($union_mer) {
                        $union_card_info = D('Card_new')->get_card_by_uid_and_mer_id($this->user_session['uid'], $_GET['union_mer_id']);
                    }
                }
            }

            $card_info = D('Card_new')->get_card_by_uid_and_mer_id($this->user_session['uid'], $order_info['mer_id']);

            // 使用联盟会员卡(会员卡余额和折扣使用选择的会员卡，优惠券使用本商家的优惠券)
            if ($union_card_info) {
                $card_info['discount'] = $union_card_info['union_card_info'];
                $card_info['card_money'] = $union_card_info['card_money'];
                $card_info['card_money_give'] = $union_card_info['card_money_give'];
            }

            $merchant_balance = $card_info['card_money'] + $card_info['card_money_give'];

            if (!$use_discount || isset($order_info['discount_status']) && !$order_info['discount_status'] || empty($card_info) || empty($card_info['discount']) || $card_info['discount'] == '0.00') {
                $card_info['discount'] = 10;
            }
            $tmp_order['uid'] = $this->user_session['uid'];

            // 超值换购不参与会员卡折扣
            $rep_momey = 0;
            if ($rep_id && $rep_return) {
                $rep_momey = $rep_return['price'];

            }
            
            $tmp_order['total_money'] = ($tmp_order['total_money'] - $tmp_order['freight_charge'] - $rep_momey) * $card_info['discount'] / 10 - $cheap_info['wx_cheap'] + $tmp_order['freight_charge'] + $rep_momey;

            //快店部分商品不参加商家优惠券
            if ($tmp_order['can_discount_money'] > 0) {
                $tmp_order['can_discount_money'] = $tmp_order['can_discount_money'] * $card_info['discount'] / 10;
            }

            $arr['has_merchant_coupon'] = '1';
			$arr['merchant_coupon_conut'] = '0';//商家优惠券可使用数量
            $tmp_order['goods'] = $order_data['goods'];
            if ((!isset($order_info['discount_status']) || $order_info['discount_status']) && $use_mer_coupon && $use_discount) {
                if ($order_type != 'mall' && ($order_info['order_type'] == 'shop' || $order_info['order_type'] == 'meal')) {
                    if ($tmp_order['can_discount_money'] > 0) {
                        if (!empty($merchant_coupon_id)) {
                            $tmp_coupon = D('Card_new_coupon')->get_coupon_info($merchant_coupon_id);
                        } else {
                            if (!empty($order_info['business_type'])) {
                                $card_coupon_list = D('Card_new_coupon')->get_noworder_coupon_list($tmp_order, $order_type, $platform, $order_info['business_type']);
                            } else {
                                $card_coupon_list = D('Card_new_coupon')->get_noworder_coupon_list($tmp_order, $order_type, $platform);
                            }
                            $tmp_coupon = reset($card_coupon_list);

                            // 可使用优惠券数量
							$coupon_list_formart = D('System_coupon')->getCanUseCoupon($tmp_order, $card_coupon_list, $order_type, $platform, 'mer');
							$arr['merchant_coupon_conut'] = $coupon_list_formart['can_use_total'];
                        }
                        if (!empty($tmp_coupon)) {
                            $mer_coupon['had_id'] = $tmp_coupon['id'];
                            //$mer_coupon['coupon_id'] = $tmp_coupon['coupon_id'];
                            $mer_coupon['order_money'] = $tmp_coupon['order_money'];
                            $mer_coupon['discount'] = $tmp_coupon['discount'];
                        } else {
                            $arr['has_merchant_coupon'] = '0';
                            $mer_coupon = array();
                        }
                    }
                } else {
                    if (!empty($merchant_coupon_id)) {
                        $tmp_coupon = D('Card_new_coupon')->get_coupon_info($merchant_coupon_id);
                    } else {
                        if (!empty($order_info['business_type'])) {
                            $card_coupon_list = D('Card_new_coupon')->get_noworder_coupon_list($tmp_order, $order_type, $platform, $order_info['business_type']);
                        } else {
                            $card_coupon_list = D('Card_new_coupon')->get_noworder_coupon_list($tmp_order, $order_type, $platform);
                        }
                        $tmp_coupon = reset($card_coupon_list);

                        // 可使用优惠券数量
						$coupon_list_formart = D('System_coupon')->getCanUseCoupon($tmp_order, $card_coupon_list, $order_type, $platform, 'mer');
						$arr['merchant_coupon_conut'] = $coupon_list_formart['can_use_total'];
                    }
                    if (!empty($tmp_coupon)) {
                        $mer_coupon['had_id'] = $tmp_coupon['id'];
                        //$mer_coupon['coupon_id'] = $tmp_coupon['coupon_id'];
                        $mer_coupon['order_money'] = $tmp_coupon['order_money'];
                        $mer_coupon['discount'] = $tmp_coupon['discount'];
                    } else {
                        $arr['has_merchant_coupon'] = '0';
                        $mer_coupon = array();
                    }
                }
            } elseif (!$use_mer_coupon) {//不使用不代表没有
                if ($tmp_order['can_discount_money'] > 0) {
                    if (!empty($order_info['business_type'])) {
                        $card_coupon_list = D('Card_new_coupon')->get_noworder_coupon_list($tmp_order, $order_type, $platform, $order_info['business_type']);
                    } else {
                        $card_coupon_list = D('Card_new_coupon')->get_noworder_coupon_list($tmp_order, $order_type, $platform);
                    }
                    $arr['has_merchant_coupon'] = !empty($card_coupon_list) ? '1' : '0'; 

                    // 可使用优惠券数量
					$coupon_list_formart = D('System_coupon')->getCanUseCoupon($tmp_order, $card_coupon_list, $order_type, $platform, 'mer');
					$arr['merchant_coupon_conut'] = $coupon_list_formart['can_use_total'];
                }
            } else {
                $arr['has_merchant_coupon'] = '0';
                $mer_coupon = array();
            }

            $tmp_order['total_money'] -= empty($mer_coupon['discount']) ? 0 : $mer_coupon['discount'];
            //平台优惠券
            $arr['has_system_coupon'] = '1';
			$arr['system_coupon_conut'] = '0';//可使用平台优惠券数量
			$extra_where = [];
            if ($use_discount && ($tmp_order['total_money'] > 0 || empty($mer_coupon)) && $use_sys_coupon && (!isset($order_info['discount_status']) || $order_info['discount_status'])) {
                if ($_POST['app_version'] >= 500) {
                    //$extra_where['is_discount']=0;
                }
                if (!empty($system_coupon_id)) {
                    $tmp_coupon = D('System_coupon')->get_coupon_info($system_coupon_id);
                    if ($tmp_coupon['is_discount'] == 1) {
                        if ($tmp_coupon['discount_type'] == 1) {//减免运费
                            $tmp_coupon['discount_money'] = round($tmp_order['freight_charge'] * (100 - $tmp_coupon['discount_value'] * 10) / 100, 2);
                        } else {
                            $tmp_coupon['discount_money'] = round($tmp_order['total_money'] * (100 - $tmp_coupon['discount_value'] * 10) / 100, 2);
                        }
                    } else {
                        $tmp_coupon['discount_money'] = $tmp_coupon['discount'];
                    }
                } else {
					$temp_detail = unserialize($order_data['discount_detail']);
					$is_system_full_discount_order = D('Shop_order')->is_system_full_discount_order($temp_detail);

                    if (!empty($order_info['business_type'])) {
                        $now_coupon = D('System_coupon')->get_noworder_coupon_list($tmp_order, $order_type, $this->user_session['phone'], $this->user_session['uid'], $platform, $order_info['business_type'], $extra_where,true);
                    } else {
                        $now_coupon = D('System_coupon')->get_noworder_coupon_list($tmp_order, $order_type, $this->user_session['phone'], $this->user_session['uid'], $platform, '', $extra_where,true);
                    }
                    //平台优惠券是否和满减共享
					$tmp_coupon = [];
                    $now_coupon = array_reverse($now_coupon);
					if ($is_system_full_discount_order) {
						foreach ($now_coupon as $c) {
							if ($c['is_share_with_discount'] == 1) {
								$tmp_coupon = $c;
								break;
							}
						}
					} else {
						$tmp_coupon = reset($now_coupon);
					}

					// 可使用优惠券数量
					$coupon_list_formart = D('System_coupon')->getCanUseCoupon($tmp_order, $now_coupon, $order_type, $platform, 'system');
					$arr['system_coupon_conut'] = $coupon_list_formart['can_use_total'];
                }

                if ($tmp_coupon) {
					$system_coupon['had_id'] = $tmp_coupon['is_membership_card'] ? $tmp_coupon['had_id'] : $tmp_coupon['id'];
                    //	$system_coupon['coupon_id'] = $tmp_coupon['coupon_id'];
                    $system_coupon['order_money'] = $tmp_coupon['order_money'];

                    $system_coupon['discount'] = $tmp_coupon['discount_money'];
                    $system_coupon['is_discount'] = $tmp_coupon['is_discount'];
                    $system_coupon['discount_value'] = $tmp_coupon['discount_value'];

                    $system_coupon['discount_type'] = $tmp_coupon['discount_type'];
                    if ($tmp_coupon['discount_type'] == '1') {//如果是减免运费,减免的金额不能还大于运费金额
                        $system_coupon['discount'] = $order_info['freight_charge'] > $system_coupon['discount'] ? $system_coupon['discount'] : $order_info['freight_charge'];
                    }
                } else {
                    $arr['has_system_coupon'] = '0';
                    $system_coupon = array();
                }
            } elseif (!$use_sys_coupon) {//不使用不代表没有
                if (!empty($order_info['business_type'])) {
                    $now_coupon = D('System_coupon')->get_noworder_coupon_list($tmp_order, $order_type, $this->user_session['phone'], $this->user_session['uid'], $platform, $order_info['business_type'], $extra_where);
                } else {
                    $now_coupon = D('System_coupon')->get_noworder_coupon_list($tmp_order, $order_type, $this->user_session['phone'], $this->user_session['uid'], $platform, '', $extra_where);
                }
                $arr['has_system_coupon'] = !empty($now_coupon) ? '1' : '0';

				// 可使用优惠券数量
				$coupon_list_formart = D('System_coupon')->getCanUseCoupon($tmp_order, $now_coupon, $order_type, $platform, 'system');
				$arr['system_coupon_conut'] = $coupon_list_formart['can_use_total'];
            } else {
                $arr['has_system_coupon'] = '0';
                $system_coupon = array();
            }

            if (isset($order_info['discount_status']) && !$order_info['discount_status']) {
                $card_info['discount'] = 10;
            }
            $tmp_order['total_money'] -= $system_coupon['discount'];
        } else {
            $tmp_order['total_money'] = $tmp_order['total_money'] - $cheap_info['wx_cheap'];
            //$score_can_use_count=0;
            //使用积分
        }
        if ($tmp_order['total_money'] < 0) {
            $tmp_order['total_money'] = 0;
        }
        if ($order_info['order_type'] == 'store') {
            // $pay_infact+=$tmp_order['no_discount_money'];
            $tmp_order['total_money'] += $tmp_order['no_discount_money'];
        }
        $score_can_use_count = 0;
        $score_deducte = 0;
        if ($order_type == 'group' || $order_type == 'store' || $order_type == 'meal' || $order_type == 'takeout' || $order_type == 'food' || $order_type == 'foodPad' || $order_type == 'appoint' || $order_type == 'mall' || $order_type == 'shop' || $order_type == 'balance-appoint' || ($order_info['order_type'] == 'plat' && $order_info['pay_system_score'])) {
            $type_ = $order_type;
            if ($order_info['business_type'] == 'foodshop') {
                $type_ = 'meal';
            }
            if ($type_ == 'balance-appoint') {
                $type_ = 'appoint';
            }

            $user_score_use_condition = $this->config['user_score_use_condition'];
            $user_score_max_use = D('Percent_rate')->get_max_core_use($order_info['mer_id'], $type_, $tmp_order['total_money'], $tmp_order);

            $user_score_use_percent = $this->config['user_score_use_percent'];
            // $score_max_deducte=bcdiv($user_score_max_use,$user_score_use_percent,2);
            $score_max_deducte = $user_score_use_percent ? sprintf('%.2f', $user_score_max_use / $user_score_use_percent) : 0;

            if ($user_score_use_percent > 0 && $score_max_deducte > 0 && $user_score_use_condition > 0 && $now_user['score_count'] > 0) {   //如果设置没有错误
                $total_ = $tmp_order['total_money'];

                if ($total_ >= $user_score_use_condition) {             //判断是否够使用积分的条件，即这个订单的总额是否大于规定的金额
                    if ($total_ > $score_max_deducte) {                    //判断积分最大抵扣金额是否比这个订单的总额大
                        $score_can_use_count = (int)($now_user['score_count'] > $user_score_max_use ? $user_score_max_use : $now_user['score_count']);//如果用户积分大于每个订单的积分可使用数 则按可使用数算
                        $score_deducte = bcdiv($score_can_use_count, $user_score_use_percent, 2);
                        $score_deducte = $score_deducte > $total_ ? $total_ : $score_deducte;
                    } else {                                                                      //最大可抵扣的金额比总单金额大 只扣掉总单范围内的积分 扣除积分=总单*积分抵扣比例
                        $score_can_use_count = ceil($total_ * $user_score_use_percent) > (int)$now_user['score_count'] ? (int)$now_user['score_count'] : ceil($total_ * $user_score_use_percent);
                        $score_deducte = bcdiv($score_can_use_count, $user_score_use_percent, 2);
                        $score_deducte = $score_deducte > $total_ ? $total_ : $score_deducte;
                    }
                }
            }
        }

        //子商户设置不允许使用平台优惠抵扣
        if ($now_user['forzen_score'] == 1 || ($this->config['cash_nobind_phone_use_balance'] == 0 && empty($now_user['phone']))) {
            $score_can_use_count = 0;
            $score_deducte = 0;
        }

        //调出支付方式
        $notOnline = intval($_GET['notOnline']);
        $notOffline = intval($_GET['notOffline']);

        $pay_offline = D('Percent_rate')->pay_offline($order_info['mer_id'], $order_type);
        $merchant = D('Merchant')->get_info($order_info['mer_id']);
        if ($merchant) {
            $notOffline = $pay_offline && $merchant['is_offline'] == 1 ? 0 : 1;
        }

        if (isset($_GET['online']) && $order_type == 'foodPad') {
            $online = isset($_GET['online']) ? intval($_GET['online']) : 1;
            $notOnline = $online ? 0 : 1;
            $notOffline = $online ? 1 : $notOffline;
        }

        $pay_method = D('Config')->get_pay_method($notOnline, $notOffline, true, true);
        if ($order_type == 'appoint') {
            unset($pay_method['offline']);
        }
        if ($pay_method['weixinapp']) {
            $pay_method['weixinapp']['config'] = array_merge($pay_method['weixin']['config'], $pay_method['weixinapp']['config']);
            $pay_method['weixin'] = $pay_method['weixinapp'];
            $pay_method['weixin']['name'] = L_('微信支付');
            unset($pay_method['weixinapp']);
        }
        //H5微信支付不展示
        if (($_POST['app_type'] == 'packapp' && strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') === false) || $_POST['app_type'] == 'alipayapp') {
            unset($pay_method['weixin']);
        }

        $arr['pay_config']['balance_disable'] = 0;
        if ($this->config['open_allinyun'] == 1) {
            $can_pay_allinyun = true;
            $allinyun = M('User_allinyun_info')->where(array('uid' => $this->user_session['uid']))->find();

            if ($order_info['mer_id'] && $order_type != 'recharge') {
                $allinyun_mer = D('Deposit')->get_merchant_info($order_info['mer_id'], 1);
                if ($allinyun_mer['status'] == 1) {
                    $can_pay_allinyun = true;
                } else {
                    $can_pay_allinyun = false;
                }
            }
            if ($allinyun['bizUserId'] && $can_pay_allinyun) {
                $pay_method = array();
                $pay_method['allinyun_alipay']['name'] = L_('云商通网关');
                $order_type != 'recharge' && $pay_method['allinyun_balance']['name'] = L_('云商通余额');
                $arr['pay_config']['balance_disable'] = 1;
            }
        }


        if (!$pay_offline || !$group_pay_offline) {
            unset($pay_method['offline']);
        }

        // 堂扫，新餐饮走自己支付方式
        if ($now_order['order_info']['is_foodshop_new']) {
            $pay_method = D('Merchant_store')->get_pay_method($now_order['order_info']['store_id']);
        }

        $display_tips = $arr['order_info']['order_name'];
        if (!empty($pay_method)) {
            foreach ($pay_method as $k => $v) {
                if ($this->DEVICE_ID == 'wxapp') {
                    if ($k != 'weixin' && $k != 'merchantwarrior' && $k != 'offline') {
                        continue;
                    }
                }
                if ($display_tips == '' && isset($v['config']['service_charge'])) {
                    $display_tips = L_("以下选项会对最终支付金额产生变化");
                }
                $array[] = array(
                    'name' => $v['name'],
                    'type' => $k,
                    'pic' => $this->config['site_url'] . '/static/images/pay_ico/' . $k . '.png',
                    'service_charge' => $v['config']['service_charge_open'] && $v['config']['service_charge'] ? $v['config']['service_charge'] : '0',
                    'discount_tips' => isset($v['config']['discount_tips']) ? $v['config']['discount_tips'] : '',
                    'rates' => isset($v['config']['rates']) ? $v['config']['rates'] : 0,
                );
            }
        }
        $arr['order_info']['order_price'] = rtrim(rtrim(number_format($arr['order_info']['order_total_money'], 2, '.', ''), '0'), '.');

        if (empty($arr['order_info']['img']) && $this->config['wechat_share_img']) {
            $arr['order_info']['img'] = $this->config['wechat_share_img'];
        }
        $arr['order_info']['order_total_money'] = rtrim(rtrim(number_format($arr['order_info']['order_total_money'], 2, '.', ''), '0'), '.');

        //订单号
        if (empty($arr['order_info']['real_orderid'])) {
            $arr['order_info']['real_orderid'] = $arr['order_info']['order_id'];
            if ($arr['order_info']['business_type'] == 'foodshop') {
                $arr['order_info']['real_orderid'] = $arr['order_info']['order_info']['real_orderid'];
            }
        }
        $arr['pay_method'] = isset($array) ? $array : array();

        //$arr['merchant_balance'] = $merchant_balance;
        if (empty($mer_coupon)) {
            $mer_coupon['had_id'] = "";
            //	$mer_coupon['coupon_id'] = "";
            $mer_coupon['order_money'] = "";
            $mer_coupon['discount'] = "";
			$mer_coupon['coupon_des'] = '';
            $arr['mer_coupon'] = $mer_coupon;
        } else {
			$mer_coupon['coupon_des'] = '-' . C('config.Currency_symbol') . getFormatNumber($mer_coupon['discount']);
            $arr['mer_coupon'] = $mer_coupon;
        }

        if (empty($system_coupon)) {
            $system_coupon['had_id'] = "";
            //	$system_coupon['coupon_id'] = "";
            $system_coupon['order_money'] = "";
            $system_coupon['discount'] = "";
            $arr['system_coupon'] = $system_coupon;
        } else {
            if ($system_coupon['is_discount']) {
                if ($system_coupon['order_money'] > 0) {
                    $system_coupon['coupon_des'] = L_('满X1享X2折', array('X1' => floatval($system_coupon['order_money']), 'X2' => floatval($system_coupon['discount_value'])));
                } else {
                    $system_coupon['coupon_des'] = L_('享X1折', array('X1' => floatval($system_coupon['discount_value'])));
                }
                if ($system_coupon['discount_type'] == '1') {
                    if ($system_coupon['order_money'] > 0) {
                        $system_coupon['coupon_des'] = L_('满X1配送享X2折', array('X1' => floatval($system_coupon['order_money']), 'X2' => floatval($system_coupon['discount_value'])));
                    } else {
                        $system_coupon['coupon_des'] = L_('配送享X1折', array('X1' => floatval($system_coupon['discount_value'])));
                    }
                }

            } else {
                $des_str = '满X1减X2';
                if ($system_coupon['discount_type'] == 1) {
                    $des_str = '满X1减运费X2';
                }
                if ($system_coupon['discount_type'] == 2) {
                    $des_str = '满X1减餐费X2';
                }
                if ($system_coupon['order_money'] > 0) {
                    $system_coupon['coupon_des'] = L_($des_str, array('X1' => C('config.Currency_symbol') . floatval($system_coupon['order_money']), 'X2' => C('config.Currency_symbol') . floatval($system_coupon['discount'])));
                } else {
                    $system_coupon['coupon_des'] = L_("减X1", array('X1' => C('config.Currency_symbol') . floatval($system_coupon['discount'])));
                }

            }
            if ($system_coupon['discount']) {
                $system_coupon['discount'] = getFormatNumber($system_coupon['discount']);
            }
            $system_coupon && $system_coupon['coupon_des'] = '-' . C('config.Currency_symbol') . getFormatNumber($system_coupon['discount']);
            $arr['system_coupon'] = $system_coupon;
        }

        $arr['pay_config']['balance_money'] = empty($now_user['now_money']) ? "0" : strval($now_user['now_money']);//余额

        if ($order_type != 'integral_mall') {
            $arr['pay_config']['merchant_money'] = empty($merchant_balance) ? "0" : strval($merchant_balance); //商家余额
        }

        $arr['pay_config']['total_money'] = strval(floatval($order_info['order_total_money']));
        $arr['pay_config']['order_id'] = $tmp_order['order_id'];
        if (!empty($card_info['discount'])) {
            $arr['pay_config']['card_discount'] = $card_info['discount'] == 10 ? 0 : strval(floatval($card_info['discount']));
        } else {
            $arr['pay_config']['card_discount'] = 0;
        }

        $arr['pay_config']['wx_cheap'] = strval($cheap_info['wx_cheap']);
        $arr['pay_config']['merchant_coupon_discount'] = strval($mer_coupon['discount']);
        $arr['pay_config']['system_coupon_discount'] = strval($system_coupon['discount']);
        $arr['pay_config']['score_money'] = strval($score_deducte);
        $arr['pay_config']['score_count'] = strval($score_can_use_count);
        if ($order_type == 'plat' && $order_info['business_type'] == 'village_group') {
            $arr['pay_config']['score_count'] = 0;//社区拼团小程序端口不需要使用积分，因为社区拼团前面已经使用过积分了
        }

        if ($tmp_order['total_money'] > 0 && $use_score) {
            $tmp_order['total_money'] -= $score_deducte;
            if ($tmp_order['total_money'] >= 0) {
                $pay_infact += $score_deducte;
            } else {
                $pay_infact += $score_deducte + $tmp_order['total_money'];
            }
        }
        if ($tmp_order['total_money'] > 0 && $use_merchant_money) {
            $tmp_order['total_money'] -= $merchant_balance;
            if ($tmp_order['total_money'] >= 0) {
                $pay_infact += $merchant_balance;
            } else {
                $pay_infact += $merchant_balance + $tmp_order['total_money'];
            }
        }

        if ($tmp_order['total_money'] > 0 && $use_balance_money) {
            $tmp_order['total_money'] -= $now_user['now_money'];
            if ($tmp_order['total_money'] >= 0) {
                $pay_infact += $now_user['now_money'];
            } else {
                $pay_infact += $now_user['now_money'] + $tmp_order['total_money'];
            }
        }
		$qiyeBalanceMoney = $qiyeBalanceMoney ?: 0;
        if ($tmp_order['total_money'] > 0 && $use_qiye_money) {
            $tmp_order['total_money'] -= $qiyeBalanceMoney;
            if ($tmp_order['total_money'] >= 0) {
                $pay_infact += $qiyeBalanceMoney;
            } else {
                $pay_infact += $qiyeBalanceMoney + $tmp_order['total_money'];
            }
        }

        $tmp_order['total_money'] = $tmp_order['total_money'] < 0 ? 0 : floatval($tmp_order['total_money']);
        $arr['pay_config']['online_pay'] = strval(sprintf("%.2f", $tmp_order['total_money'])) < 0.01 ? 0 : strval(round(floatval($tmp_order['total_money']) * 100) / 100);
        //$arr['pay_config']['pay_infact'] = strval(sprintf("%.2f",floatval($pay_infact+$tmp_order['total_money'])))<0.01?0:strval(sprintf("%.2f",floatval($pay_infact+$tmp_order['total_money'])));
        $arr['pay_config']['pay_infact'] = getFormatNumber($pay_infact + $tmp_order['total_money']) < 0.01 ? 0 : getFormatNumber($pay_infact + $tmp_order['total_money']);

        // 堂扫税率
        if ($now_order['order_info']['is_foodshop_new'] && $now_order['order_info']['order_info']['tax_rate_price'] > 0) {
            $arr['pay_config']['online_pay'] = getFormatNumber($arr['pay_config']['online_pay'] + $now_order['order_info']['order_info']['tax_rate_price']);
            $arr['pay_config']['pay_infact'] = getFormatNumber($arr['pay_config']['pay_infact'] + $now_order['order_info']['order_info']['tax_rate_price']);
        }

        /* 默认选中第一个 */
        if ((($this->DEVICE_ID == 'wxapp' && $this->app_version >= 1500) || $this->app_version >= 1510) && empty($_POST['pay_type']) && $pay_method && $_POST['page_from'] == 'pay') {
            $_POST['pay_type'] = $arr['pay_method'][0]['type'];
        }

        $arr['pay_config']['online_pay'] = strval(sprintf("%.2f", $tmp_order['total_money'])) < 0.01 ? 0 : strval(round(floatval($tmp_order['total_money']) * 100) / 100);
        //$arr['pay_config']['pay_infact'] = strval(sprintf("%.2f",floatval($pay_infact+$tmp_order['total_money'])))<0.01?0:strval(sprintf("%.2f",floatval($pay_infact+$tmp_order['total_money'])));
        $arr['pay_config']['pay_infact'] = getFormatNumber($pay_infact + $tmp_order['total_money']) < 0.01 ? 0 : getFormatNumber($pay_infact + $tmp_order['total_money']);

        // 堂扫税率
        if ($now_order['order_info']['is_foodshop_new'] && $now_order['order_info']['order_info']['tax_rate_price'] > 0) {
            $arr['pay_config']['online_pay'] = getFormatNumber($arr['pay_config']['online_pay'] + $now_order['order_info']['order_info']['tax_rate_price']);
            $arr['pay_config']['pay_infact'] = getFormatNumber($arr['pay_config']['pay_infact'] + $now_order['order_info']['order_info']['tax_rate_price']);
        }

        $_service = 0;//支付手续费
        if (isset($_POST['pay_type']) && $pay_method[$_POST['pay_type']]['config']['service_charge_open'] == '1' && $pay_method[$_POST['pay_type']]['config']['service_charge'] > 0) {
            $service_charge = $pay_method[$_POST['pay_type']]['config']['service_charge'];
            $_service = $arr['pay_config']['online_pay'] * ($service_charge / 100);
        }
        //升级会员价格
        if ($upgradePrice) {
            $arr['pay_config']['pay_infact'] += $upgradePrice;
            $arr['pay_config']['online_pay'] += $upgradePrice;
        }
		$arr['pay_config']['upgrade_level_price'] = $upgradePrice;

        $arr['pay_config']['pay_infact_old'] = $arr['pay_config']['pay_infact'];
        $arr['pay_config']['pay_infact'] = round(($arr['pay_config']['pay_infact'] + $_service) * 100) / 100;
        $arr['pay_config']['online_pay'] = round(($arr['pay_config']['online_pay'] + $_service) * 100) / 100;
        $arr['pay_config']['display_tips'] = $display_tips;
        $arr['pay_config']['pay_infact'] = (string)$arr['pay_config']['pay_infact'];

        foreach ($arr['pay_method'] as $_k => $method) {
            if (isset($method['rates']) && $method['rates'] > 0) {
                $arr['pay_method'][$_k]['discount_tips'] = '（$' . $arr['pay_config']['online_pay'] . '*汇率' . $method['rates'] . '=￥' . getFormatNumber($arr['pay_config']['online_pay'] * $method['rates']) . '）';
            }
        }

        //判断是否符合满赠互动(订单总金额 + 打包费)
        $fullGift = D('Store_full_gift_activity')->getFullGiveGood($storeId, $goodsTotalMoney + $order_data['packing_charge'],$order_data['uid']);
        $arr['full_gift'] = $fullGift ?: new \stdClass();

        $arr['temp_order_id'] = $tempOrderDataId;
        if ($upgradeVipInfo && false) {
            $upgradeVipInfo['is_show'] = 1;
            $arr['upgrade_vip_level_info'] = $upgradeVipInfo;
        } else {
            $arr['upgrade_vip_level_info'] = ['is_show' => 0];
        }

        //增加平台优惠券返回
        $arr['membership_card_info'] = ['is_show' => 0];
        $arr['membership_card_coupon_tip'] = "";
		$membershipCard = (new Membership_cardModel())->getCurrentMembershipCard();
		if (empty($cartid) && $user_info['membership_card_time'] < time() && $membershipCard) {
			$title = '购买后，享';
			$discountTxt = [];
			$discount = [];
			if ($membershipCard['discount_type'] & 1) {
				$discountTxt[] = sprintf('%s元红包', getFormatNumber($membershipCard['coupon_num'] * $membershipCard['coupon_price']));
				$discount[] = [
					'price' => getFormatNumber($membershipCard['coupon_price']),
					'num' => $membershipCard['coupon_num'],
					'type' => '优惠券',
					'unit' => '张'
				];
			}
			if ($membershipCard['discount_type'] & 2) {
				$discountTxt[] = sprintf('%d次立减机会', $membershipCard['deliver_num']);
				$discount[] = [
					'price' => getFormatNumber($membershipCard['deliver_price']),
					'num' => $membershipCard['deliver_num'],
					'type' => '配送费立减',
					'unit' => '次'
				];
			}
			$cardDiscount = round($membershipCard['price'] * 10 / $membershipCard['old_price'], 2);
			$info = [
				'label' => $membershipCard['card_name'],
				'price' => getFormatNumber($membershipCard['price']),
				'old_price' => getFormatNumber($membershipCard['old_price']),
				'days' => $membershipCard['days'],
                'note' => ['本单可用', $membershipCard['days'] . '天内有效', '不限商家'],
				'discount_txt' => $cardDiscount > 0 ? sprintf('%s折优惠', $cardDiscount) : L_('1折起优惠'),
				'title' => $title . implode('+', $discountTxt),
				'discount' => $discount
			];
            $arr['membership_card_info'] = ['is_show' => 1, 'info' => $info];
            if (($membershipCard['discount_type'] & 1) && $membershipCard['coupon_num'] > 0 && $isOpenMembershipCard) {
                $arr['membership_card_coupon_tip'] = sprintf("%d张赠送券可用", $membershipCard['coupon_num']);
            }
		}

        //为了达到快店后续在详情页也能发起付款，使用优惠券，在这里先同步保存优惠券
        /*if($order_type == 'shop'){
            $data_shop_order = [];
            if($arr['mer_coupon']['had_id']){
                $data_shop_order['card_id'] = $arr['mer_coupon']['had_id'];
                $data_shop_order['card_price'] = $arr['mer_coupon']['discount'];
            }else{
                $data_shop_order['card_id'] = 0;
                $data_shop_order['card_price'] = 0;
            }
            if($arr['system_coupon']['had_id']){
                $data_shop_order['coupon_id'] = $arr['system_coupon']['had_id'];
                $data_shop_order['coupon_price'] = $arr['system_coupon']['discount'];
            }else{
                $data_shop_order['coupon_id'] = 0;
                $data_shop_order['coupon_price'] = 0;
            }
            M('Shop_order')->where(['order_id'=>$arr['order_info']['order_id']])->data($data_shop_order)->save();
        }*/

        if ($rep_id && $rep_return) {
            $arr['repurchase_info']['price'] = $rep_return['price'];
            $arr['repurchase_info']['total_money'] = $rep_return['total_money'];
            $arr['repurchase_info']['discount_money'] = $rep_return['discount_money'];
            $arr['repurchase_info']['packing_charge'] = $rep_return['packing_charge'];

            $arr['pay_config']['total_money_all'] = getFormatNumber($arr['pay_config']['total_money']+$arr['repurchase_info']['price']);

            // 合计
            $arr['pay_config']['pay_infact_all'] = getFormatNumber($arr['pay_config']['pay_infact']);
            $arr['pay_config']['total_money'] = max(0,getFormatNumber($arr['pay_config']['total_money'] - $rep_return['price']));
            // 小计
            $arr['pay_config']['pay_infact'] = max(0,getFormatNumber($arr['pay_config']['pay_infact'] - $rep_return['price']));

        }
		//增加代付链接
		$arr['pay_by_others']['is_show'] = $this->config['is_open_pay_for_others'];
		if ($this->config['is_open_pay_for_others'] == 1) {
			$arr['pay_by_others']['url'] = str_replace('appapi.php', 'wap.php', U('Wap/Pay/pay_by_others', ['temp_data_id' => $tempOrderDataId], true, false, true));
			//$arr['pay_by_others']['url'] = C('config.site_url') . '/packapp/plat/pages/shop_new/payByOthers/payByOthers?temp_data_id=' . $tempOrderDataId;
		}
        //通过缓存关联temp_orderid和order_id
        if(!empty(C('REDIS_HOST'))){
            try{
                $redis = new \Redis();
                $redis->connect(C('REDIS_HOST'), C('REDIS_PORT'));
                C('REDIS_AUTH') && $redis->auth(C('REDIS_AUTH'));
                $redis->set($_POST['ticket'] . $_POST['store_id'] . 'tem_orderid', $tempOrderDataId);
            } catch (\Exception $e) {
                // throw new \think\Exception($e->getMessage());
                fdump_sql($e->getMessage(), 'redis_error');
            }
        }else{
            S($_POST['ticket'] . $_POST['store_id'] . 'tem_orderid', $tempOrderDataId, 120);
        }
       


        //为了达到快店后续在详情页也能发起付款，使用优惠券，在这里先同步保存优惠券
        $tempShopOrderData = ['pay_infact' => $arr['pay_config']['pay_infact']];
        if ($arr['mer_coupon']['had_id']) {
            $tempShopOrderData['card_id'] = $arr['mer_coupon']['had_id'];
            $tempShopOrderData['card_price'] = $arr['mer_coupon']['discount'];
        } else {
            $tempShopOrderData['card_id'] = 0;
            $tempShopOrderData['card_price'] = 0;
        }
        if ($arr['system_coupon']['had_id']) {
            $tempShopOrderData['coupon_id'] = $arr['system_coupon']['had_id'];
            $tempShopOrderData['coupon_price'] = $arr['system_coupon']['discount'];
        } else {
            $tempShopOrderData['coupon_id'] = 0;
            $tempShopOrderData['coupon_price'] = 0;
        }
        M('Temp_order_data')->where(['temp_id' => $tempOrderDataId])->save($tempShopOrderData);

        //额外的优惠信息展示
		$arr['append_discount_list'] = $appendDiscount;
        $this->returnCode(0, $arr);
    }

    /**
     * 找人代付
     */
    public function paid()
    {
        if ($this->config['is_open_pay_for_others'] != 1) {
            $this->returnCode(79000001);
        }
		$orderId = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
		$tmp = M('Temp_order_data')->where(['order_id' => $orderId])->find();

        $ticket = I('ticket', false);
        $orderWhere = [
            'o.order_id' => $orderId
        ];
        $currentUid = 0;
        if ($ticket) {
            $info = ticket::get($ticket, $this->DEVICE_ID, true);
            $this->user_session['uid'] = $info['uid'];
            //$orderWhere['o.uid'] = $info['uid'];
            $currentUid = $info['uid'];
        }

        $nowOrder = M('Shop_order')->alias('o')
            ->join(C('DB_PREFIX') . 'merchant_store AS s ON o.store_id = s.store_id')
            ->join(C('DB_PREFIX') . 'user AS u ON u.uid = o.uid')
            ->where($orderWhere)
            ->field('o.order_id,o.orderid,o.real_orderid,o.uid,o.price,o.status,o.pay_time,o.is_open_membership_card,o.membership_card_price,s.name AS store_name,s.pic_info,u.nickname,u.avatar')
            ->find();
        if (empty($nowOrder)) {
            $this->returnCode(10070006);
        }
        $nowOrder['price'] = (isset($tmp['pay_infact']) && $tmp['pay_infact'] > 0) ? getFormatNumber($tmp['pay_infact']) : $nowOrder['price'];
		if ($nowOrder['is_open_membership_card']) {
			$nowOrder['price'] += $nowOrder['membership_card_price'];
		}
		$nowOrder['price'] = getFormatNumber($nowOrder['price']);
        if (empty($nowOrder['avatar'])) {
            $nowOrder['avatar'] = $this->config['site_url'] . '/static/images/user_avatar.jpg';
        }else{
			$nowOrder['avatar'] = replace_file_domain($nowOrder['avatar']);
		}
        $store_image_class = new store_image();
        $images = $store_image_class->get_allImage_by_path($nowOrder['pic_info']);
        $firstStoreImage = isset($images[0]) ? $images[0] : '';
        $nowOrder['store_image'] = thumb_img($firstStoreImage, 240, 240, 'fill');

        $goodsDetail = D('Shop_order_detail')->where(['order_id' => $nowOrder['order_id']])->select();
        $goodsDetail = D('Shop_goods')->combination_data($goodsDetail, 'spec');
        $goodsList = [];
        foreach ($goodsDetail as $v) {
            $goodsList[] = [
                'goods_id' => $v['goods_id'],
                'name' => $v['name'],
				'goods_image' => thumb_img($v['goods_image'], 240, 240, 'fill'),
                'str' => $v['spec'],
                'num' => $v['num'],
                'discount_price_all' => getFormatNumber($v['discount_price'] * $v['num'])
            ];
        }
        $shareWxType = ($this->config['pay_wxapp_important'] && $this->config['pay_wxapp_username']) ? 'wxapp' : 'h5';
        $shareUrl = C('config.site_url') . D('House_village')->base_url . 'pages/shop_new/payByOthers/payByOthers?order_id=' . $orderId;
        $shareTitle = sprintf("【%s】邀请你帮忙代付%s%.2f的外卖订单", $nowOrder['nickname'], C('config.Currency_symbol'), $nowOrder['price']);
        $shareSubTitle = "点击去给好友帮忙";
        $sharePic = $nowOrder['store_image'];

        $rs = [
            'order_info' => $nowOrder,
            'goods_list' => $goodsList,
            'uid' => $currentUid,
            'note' => [
                '1.付款前请先与好友确认无误，避免受骗。',
                '2.付款成功后，如果申请退款，可以联系好友申请退款。',
                '3.当代付订单退款成功后，实付金额将原路退还代付人。',
            ],
            'order_detail_url' => str_replace('appapi.php', 'wap.php', U('Wap/Shop/status', ['order_id' => $nowOrder['order_id']], true, false, true)),
            'share_wx' => $shareWxType,
            'share_h5_info' => [
                'title' => $shareTitle,
                'content' => $shareSubTitle,
                'image' => $sharePic,
                'url' => $shareUrl,
            ],
            'share_wxapp_info' => [
                'userName' => $this->config['pay_wxapp_username'],
                'path' => '/pages/plat_menu/index?redirect=webview&webview_url=' . urlencode($shareUrl) . '&webview_title=' . urlencode($shareSubTitle),
                'image' => $sharePic,
                'title' => $shareTitle
            ]
        ];
        $this->returnCode(0, $rs);
    }

    public function pay_status()
    {
		$orderId = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        $orderInfo = M('Shop_order')->where(['order_id' => $orderId])->field('order_id,orderid,real_orderid,uid,price,status,paid,pay_time,pay_uid')->find();
        if (empty($orderInfo)) {
            $this->returnCode(10070006);
        }
        $orderInfo['pay_time'] = $orderInfo['pay_time'] > 0 ? date('Y-m-d H:i:s', $orderInfo['pay_time']) : '';
        $this->returnCode(0, $orderInfo);
    }

    /**
     * 满减组合
     */
	public function full_reduction()
	{
		$ticket = I('ticket', false);
		if ($ticket) {
			$info = ticket::get($ticket, $this->DEVICE_ID, true);
			$this->user_session['uid'] = $info['uid'];
		}
		$storeId = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
		$discountId = isset($_POST['discount_id']) ? intval($_POST['discount_id']) : 0;
		$storeInfo = D('Merchant_store')->where(['store_id' => $storeId])->find();

		$priceList = D('Merchant_store')->getStoreBasePrice($storeId);
		//起送价
		$basePrice = isset($priceList['delivery_price']) ? $priceList['delivery_price'] : 0;

		//获取所有必选商品
		$mustSort = D('Merchant_store')->getMustSortIdsByStoreId($storeId, 'sort_id,sort_name');
		if ($mustSort) {
			$mustSortId = D('Shop_goods_sort')->getAllSonIds($mustSort['sort_id'], $storeId, true, true);
		} else {
			$mustSortId = [];
		}

		//外卖 + 非折扣优惠 + 启用 + 满减 + 优惠程度不限制 + 使用场景不限制 + 同享
		$discounts = D('Shop_discount')->getDiscounts($storeInfo['mer_id'], $storeId, 1, 5, 0, ['type' => 1, 'percentage_status' => 0, 'order_count' => 0, 'is_share' => 1]);
		$discounts = isset($discounts[$storeId]) ? $discounts[$storeId] : [];
		if (empty($discounts)) {
			$this->returnCode(0, ['is_show' => false]);
		} else {
			$fullMoney = array_column($discounts, 'full_money');
			array_multisort($fullMoney, SORT_ASC, $discounts);
			$rs = ['discount_list' => [], 'combine' => []];
			$fullMoney = 0;
			$reduceMoney = 0;
			$fullReduceGoods = [];
			foreach ($discounts as $k => $d) {
				$checked = false;
				if ($discountId > 0) {
					//满减id
					if ($d['id'] == $discountId) {
						$fullMoney = $d['full_money'];
						$reduceMoney = $d['reduce_money'];
						$checked = true;
					}
					$coupon = [
						'id' => $d['id'],
						'checked' => $checked,
						'full_money' => getFormatNumber($d['full_money']),
						'reduce_money' => getFormatNumber($d['reduce_money']),
						'str' => L_('满X1减X2', ['X1' => getFormatNumber($d['full_money']), 'X2' => getFormatNumber($d['reduce_money'])])
					];
					$rs['discount_list'][] = $coupon;
				} else {
					//初始化未选择满减，则保证第一个满减一定要有组合数据
					$fullMoney = $d['full_money'];
					$reduceMoney = $d['reduce_money'];
					$checked = true;
					if (empty($fullReduceGoods)) {
						$fullReduceGoods = D('Shop_order')->getFullReduction(0, $storeId, $fullMoney, $reduceMoney);
						if (empty($fullReduceGoods)) {
							continue;
						}
					}
					$coupon = [
						'id' => $d['id'],
						'checked' => $checked,
						'full_money' => getFormatNumber($d['full_money']),
						'reduce_money' => getFormatNumber($d['reduce_money']),
						'str' => L_('满X1减X2', ['X1' => getFormatNumber($d['full_money']), 'X2' => getFormatNumber($d['reduce_money'])])
					];
					$rs['discount_list'][] = $coupon;
				}
			}
			if ($discountId == 0 && empty($fullReduceGoods)) {
				$this->returnCode(0, ['is_show' => false]);
			}
			if ($discountId > 0) {
				$fullReduceGoods = D('Shop_order')->getFullReduction(0, $storeId, $fullMoney, $reduceMoney);
			}
			$rs['is_show'] = true;
			$rs['combine'] = array_values($fullReduceGoods);
			$this->returnCode(0, $rs);
		}
	}

	/**
	 * 定点配送地址
	 * @author 张涛
	 * @date 2020/06/03
	 */
	public function shop_fixed_point_address()
	{
		$roomId = $_POST['room_id'] ?? 0;
		if ($roomId < 1) {
			$this->returnCode(1, [], L_('参数有误'));
		}
		if ($this->config['open_fixed_point_delive'] != 1) {
			$this->returnCode(1, [], L_('未开启定点配送功能'));
		}
		$room = M('Shop_fixed_point_room')->alias('r')
			->join([
				'INNER JOIN ' . C('DB_PREFIX') . 'shop_fixed_point_layer as l on l.layer_id = r.layer_id',
				'INNER JOIN ' . C('DB_PREFIX') . 'shop_fixed_point_floor as f on f.floor_id = l.floor_id',
				'INNER JOIN ' . C('DB_PREFIX') . 'shop_fixed_point_area as a on a.id = f.area_id'
			])
			->field('r.room_id,r.room_num,l.layer_num,f.floor_name,a.*')
			->where(['room_id' => $roomId])
			->find();
		if (empty($room) || $room['status'] != 1) {
			$this->returnCode(1, [], L_('地址不存在或已关闭'));
		}
		if ($this->_uid && M('User_adress')->where(['uid' => $this->_uid, 'room_id' => $roomId])->find()) {
			$this->returnCode(1, [], L_('已添加过地址'));
		}
		$areas = M('Area')->where(['area_id' => ['IN', [$room['province_id'], $room['city_id'], $room['area_id']]]])->getField('area_id,area_name', 'area_id');
		$province = $areas[$room['province_id']] ?? '';
		$city = $areas[$room['city_id']] ?? '';
		$area = $areas[$room['area_id']] ?? '';
		$address = $province . $city . $area . $room['address'] . $room['floor_name'] . $room['layer_num'] . $room['room_num'];
		$this->returnCode(0, ['address' => $address]);
	}

	/**
	 * 添加定点配送用户地址
	 * @author 张涛
	 * @date 2020/06/03
	 */
	public function shop_fixed_point_add_address()
	{
		$roomId = $_POST['room_id'] ?? 0;
		$name = $_POST['name'] ?? '';
		$phone = $_POST['phone'] ?? '';
		if ($this->_uid < 1) {
			$this->returnCode(30010010, array());
		}
		if (empty($name) || empty($phone)) {
			$this->returnCode(1, [], L_('姓名和电话不能为空'));
		}
		$room = M('Shop_fixed_point_room')->alias('r')
			->join([
				'INNER JOIN ' . C('DB_PREFIX') . 'shop_fixed_point_layer as l on l.layer_id = r.layer_id',
				'INNER JOIN ' . C('DB_PREFIX') . 'shop_fixed_point_floor as f on f.floor_id = l.floor_id',
				'INNER JOIN ' . C('DB_PREFIX') . 'shop_fixed_point_area as a on a.id = f.area_id'
			])
			->field('r.room_id,r.room_num,l.layer_num,f.floor_name,a.*')
			->where(['room_id' => $roomId])
			->find();
		if (empty($room) || $room['status'] != 1) {
			$this->returnCode(1, [], L_('地址不存在或已关闭'));
		}
		if (M('User_adress')->where(['uid' => $this->_uid, 'room_id' => $roomId])->find()) {
			$this->returnCode(1, [], L_('已添加过地址'));
		}
		$user = M('User')->where(['uid' => $this->_uid])->find();
		$data = [
			'uid' => $this->_uid,
			'name' => $name,
			'phone' => $phone,
			'sex' => $user['sex'],
			'phone_country_type' => $user['phone_country_type'],
			'room_id' => $room['room_id'],
			'province' => $room['province_id'],
			'city' => $room['city_id'],
			'area' => $room['area_id'],
			'adress' => $room['address'],
			'longitude' => $room['lng'],
			'latitude' => $room['lat'],
			'default' => 1,
			'detail' => $room['floor_name'] . $room['layer_num'] . $room['room_num']
		];
		if ($addressId = M('User_adress')->add($data)) {
			M('User_adress')->where(['adress_id' => ['neq', $addressId], 'uid' => $this->_uid])->setField('default', 0);
			$this->returnCode(0);
		} else {
			$this->returnCode(1, [], L_('添加收货地址失败'));
		}
	}
		
	/**
	 * 获得小程序订阅消息模板
	 */
	public function getTemplateList()
	{
		$param['type'] = I("type");
		$result = D('Wxapp_template')->getNormalWxappTemplate($param);
		
		$result['template_list'] = array_column($result['template_list'], 'template_key');
		$result['template_list'] = array_unique($result['template_list']);
		
		$this->returnCode(0, $result);
	}

	/**
	 * 保存小程序订阅消息模板授权信息
	 */
	public function saveTemplateRole()
	{
		$param['template_list'] = $_POST['template_list'];
		$param['wxapp_openid'] = $_POST['wxapp_openid'];
	        if(empty($_POST['wxapp_openid']) && $this->_uid){
	            $nowUser = D('User')->get_user($this->_uid);
	            $param['wxapp_openid'] = $nowUser['wxapp_openid'];
        
	        }
		$result = D('Wxapp_template_bind_user_list')->saveTemplateRole($param);
		$this->returnCode(0, $result);
	}


	/**
	 * 最新订单状态
	 * @author: 张涛
	 * @date: 2021/03/05
	 */
	public function lastOrderStatus()
	{
		$rs = ['is_show' => false, 'order' => []];
		if ($this->_uid) {
			$zeroTime = strtotime(date('Y-m-d'));
			$where = [
				'uid' => $this->_uid,
				'is_pick_in_store' => ['IN', [0, 1]],
				'pay_time' => ['GT', $zeroTime],
				'paid' => 1,
				'status' => ['NOT IN', [2, 3, 4, 5]],
				'order_from' => ['NEQ', 1]
			];
			$lastOrder = D('Shop_order')->where($where)->order('order_id DESC')->find();
			if ($lastOrder && $lastOrder['status'] == 1) {
				$tip = '';
				switch ($lastOrder['order_status']) {
					case 1:
						$tip = L_('店员已接单');
						break;
					case 2:
						$tip = L_('骑手正赶往商家');
						break;
					case 3:
						$tip = L_('骑手已到店');
						break;
					case 4:
						$tip = L_('骑手配送中');
						break;
					default:
						break;
				}
				if ($tip) {
					if ($this->config['map_config'] == 'google') {
						$jumpUrl = packapp_url('my/shop_order.html?order_id=' . $lastOrder['order_id'], D('User')->get_user($this->_uid));
					} else {
						$jumpUrl = $this->config['site_url'] . D('House_village')->base_url . "pages/shop_new/orderDetail/orderDetail?order_id=" . $lastOrder['order_id'];
					}
					$rs['is_show'] = true;
					$rs['order']['status_txt'] = $tip;
					$rs['order']['expect_arrive_time'] = L_('预计送达:X1', array('X1' => date('H:i', $lastOrder['expect_use_time'])));
					$rs['order']['jump_url'] = $jumpUrl;
					$rs['order']['pic'] = replace_file_domain($this->config['wechat_share_img']);
				}
			}
		}
        if (empty($rs['order'])) {
            $rs['order'] = new \stdClass();
        }
		$this->returnCode(0, $rs);
	}

	/**
	 * 用户订阅/取消订阅
	 * @author: 汪晨
	 * @date: 2021/09/24
	 */
	public function subscribeAdd()
	{
        $uid = $this->_uid;
        if ($uid < 1) {
			$this->returnCode(30010010, array());
		}
        $store_id = $_GET['store_id'];
        if (empty($store_id)) {
            $store_id = $_POST['store_id'];
            if (empty($store_id)) {
                $this->returnCodeError(L_('请携带 store_id 参数'));
            }
        }

        $storeRemind = D('Merchant_store_shop')->where(['store_id'=>$store_id])->find();
        $remind_time = date('H:i',$storeRemind['remind_time']);

        $weekend = array_unique(explode(',',$storeRemind['remind_weekend']));
        $week = [
            '0' => L_('周日'),
            '1' => L_('周一'),
            '2' => L_('周二'),
            '3' => L_('周三'),
            '4' => L_('周四'),
            '5' => L_('周五'),
            '6' => L_('周六')
        ];
        $weekend = array_map(function ($r) use ($week) {
            return $week[$r] ?? '';
        }, $weekend);
        $timeTip = '每天';
        if (count($weekend) != 7) {
            $timeTip = implode('、', $weekend);
        }
        $timeTip .= ' ' . date('H:i', $storeRemind['remind_time']);


        // 是否写入主计划任务
        $count = D('User_subscribe')->count();

        // 判断订阅/取消订阅
        $subscribe = D('User_subscribe')->where(['uid'=>$uid, 'store_id'=>$store_id])->count();
        if($subscribe > 0){
            // 取消订阅
            $del = D('User_subscribe')->where(['uid'=>$uid, 'store_id'=>$store_id])->delete();
            if($del){
                // 删除子计划任务
                $rs['status'] = '取消订阅成功';
                $rs['remind_time'] = '未订阅（'.$timeTip.'）';
            }else{
                $rs['status'] = '取消订阅失败';
                $rs['remind_time'] = '未订阅（'.$timeTip.'）';
            }
        }else{
            // 订阅
			if(strtotime($remind_time) > time()){
				$nextNoticeTime = strtotime($remind_time);
			}else{
				$nextNoticeTime = strtotime($remind_time) + 86400;
			}
			$add = D('User_subscribe')->add(['uid' => $uid, 'store_id' => $store_id, 'add_time' => time(), 'next_notice_time' => $nextNoticeTime]);
            if($add){
                $rs['status'] = '订阅成功';
                $rs['remind_time'] = '已订阅（'.$timeTip.'）';
            }else{
                $rs['status'] = '订阅失败';
                $rs['remind_time'] = '已订阅（'.$timeTip.'）';
            }
        }

		$this->returnCode(0, $rs);
	}
}
?>
