<?php
//include(WEB_PATH.'plugin/paotui/Common/convert.php');
class PaotuiAction extends BaseAction{
    public function moni_login() {
        $uid = I('uid');
        $now_user = plugin_model('user')->autologin('uid',$uid);
        if(!empty($now_user)){
            loadSystemOrg('ticket');
            $plat_wxapp_ticket_info = ticket::create($now_user['uid'],'w_paotui', true);
            $return = array(
                'ticket'    =>	$plat_wxapp_ticket_info['ticket'],
                'user'      =>	$now_user,
            );
            dump($return);
        }
    }


    public function wxapp_login(){
        if($_POST['ticket']){
            if ($this->_uid){
                $now_user = $this->autologin('uid',$this->_uid);
                if(!empty($now_user)){
                    $return = array(
                        'ticket'    =>	$_POST['ticket'],
                        'user'      =>	$now_user,
                    );
					$plat_wxapp_ticket_info = ticket::create($now_user['uid'],'wxapp', true);
					$return['user']['plat_wxapp_ticket'] = $plat_wxapp_ticket_info['ticket'];
                    returnCodeOk($return);
                }else{
                    returnCodeOk(array('emptyUser'=>true));
                }
            }else{
                returnCodeOk(array('emptyUser'=>true));
            }
        }

        $appid = $this->config['pay_wxapp_paotui_appid'];
        $appsecret = $this->config['pay_wxapp_paotui_appsecret'];


        import('ORG.Net.Http');
        $http = new Http();

        $return = Http::curlPost('https://api.weixin.qq.com/sns/jscode2session?appid='.$appid.'&secret='.$appsecret.'&js_code='.$_POST['code'].'&grant_type=authorization_code', array());

        import('@.ORG.aeswxapp.wxBizDataCrypt');

        $pc = new WXBizDataCrypt($appid, $return['session_key']);
        $errCode = $pc->decryptData($_POST['encryptedData'],$_POST['iv'],$data);
        $jsonrt = json_decode($data,true);
        /*优先使用 unionId 登录*/
        if(!empty($jsonrt['unionId'])){
            $now_user = $this->autologin('union_id',$jsonrt['unionId'],$jsonrt['openId']);
        }else{
            /*再次使用 openId 登录*/
            $now_user = $this->autologin('paotui_openid',$jsonrt['openId']);
        }
		$jsonrt['avatarUrl'] = str_replace('http://wx.qlogo.cn','https://thirdwx.qlogo.cn',$jsonrt['avatarUrl']);
        if(empty($now_user)){
            $data_user = array(
                'paotui_openid' 	=> $jsonrt['openId'],
                'union_id' 	=> ($jsonrt['unionId'] ? $jsonrt['unionId'] : ''),
                'nickname' 	=> $jsonrt['nickName'],
                'sex' 		=> $jsonrt['gender'],
                'province' 	=> $jsonrt['province'],
                'city' 		=> $jsonrt['city'],
                'avatar' 	=> $jsonrt['avatarUrl'],
                'is_follow' => 1,
                'source' 	=> 'wxapp_paotui'
            );
            $reg_result = D('User')->autoreg($data_user);
            if(!$reg_result['error_code']){
                $now_user = $this->autologin('paotui_openid',$jsonrt['openId']);
            }
        }else{
			if($now_user['avatar'] != $jsonrt['avatarUrl']){
				D('User')->save_user($now_user['uid'],'avatar',$jsonrt['avatarUrl']);
			}
		}

        if(!empty($now_user)){
            $ticket = ticket::create($now_user['uid'],'wxapp_paotui', true);
            $return = array(
                'ticket'=>	$ticket['ticket'],
                'user'	=>	$now_user,
            );
			
			$plat_wxapp_ticket_info = ticket::create($now_user['uid'],'wxapp', true);
			$return['user']['plat_wxapp_ticket'] = $plat_wxapp_ticket_info['ticket'];
			
            returnCodeOk($return);
        }else{
            returnCodeOk(array('emptyUser'=>true));
        }
    }
    protected function autologin($field,$value,$openid = ''){
        $result = D('User')->autologin($field,$value);
        $now_user = array();
        if(empty($result['error_code'])){
            if($field == 'union_id' && empty($result['user']['paotui_openid'])){
                $condition_user['union_id'] = $value;
                D('User')->where($condition_user)->data(array('paotui_openid'=>$openid))->save();
                $result['user']['paotui_openid'] = $openid;
            }
            $result['user']['showPhone'] = substr_replace($result['user']['phone'], '****', 3, 4);
            $now_user = $result['user'];
        }
        return $now_user;
    }
	
	// 小程序配置接口
	public function config(){
		$return['config'] = array(
			'pay_wxapp_appid' => !empty($this->config['pay_wxapp_appid']) && empty($this->config['pay_wxapp_paotui_mchid']) ? $this->config['pay_wxapp_appid'] : '-1',
			'site_phone' 	  => $this->config['site_phone'],
		);
		$return['config']['moreLink'] = array(
			array(
				'title' => L_('更多功能'),
				'desc'  => L_('本地团购外卖'),
				'path'  => 'pages/index/index',
			),
			array(
				'title' => L_('本地商城'),
				'desc'  => L_('精选本地好货'),
				'path'  => 'pages/index/location?redirect=webview&webview_url='.urlencode($this->config['site_url'].'/wap.php?g=Wap&c=Mall&a=index').'&webview_title='.urlencode(L_('本地商城')),
			),
			array(
				'title' => L_('配送员入驻'),
				'desc'  => L_(''),
				'path'  => 'pages/index/location?redirect=webview&webview_url='.urlencode($this->config['site_url'].'/packapp/deliver_web/register.html').'&webview_title='.urlencode(L_('配送员入驻')),
			),
			// array(
				// 'title' => L_('小猪社群'),
				// 'desc'  => L_('寻找本地交流群'),
				// 'path'  => '',
				// 'wxapp_appid' => $this->config['pay_wxapp_group_appid']
			// )
		);
		// $return['config']['moreLink'] = [];
		$return['catList'] = D('Service_category')->field('`cid`,`cat_name`,`accept_time`,`type`')->where(array('cat_status'=>1,'type'=>array('neq','1')))->order('cat_sort desc,cid asc')->select();
        if(!is_array($return['catList'])){
			$return['catList'] = array();
        }
        $return['page_title'] = $this->config['service_alias_name'] ? : "跑腿";
        $return['page_share_title'] = "同城跑腿";
        $return['page_share_desc'] = $this->config['site_name'];
        $return['page_share_img'] = replace_file_domain($this->config['wechat_share_img']);
        $return['page_share_wx_img'] = '';
        
        // 是否隐藏就近购买
        $return['hide_purchase_nearby'] = $this->config['hide_purchase_nearby'] ? $this->config['hide_purchase_nearby'] : 0;

//        $return['order_url'] = $this->config['site_url'].'/wap.php?c=Service&a=need_list_app&from=paotui';
        $return['order_url'] = get_base_url('pages/my/my_order?state=0&type=service');

        $return['user_center_url'] = $this->config['site_url'] . PD('House_village')->base_url . 'pages/runErrands/my';

		returnCodeOk($return);
	}
	
	//根据用户上报的经纬度，得到能为其服务的配送员数量
	public function deliverAround(){
		$fromType = 0;
		if($_POST['type'] == 'gcj02'){
			$fromType = 3;
		}elseif($_POST['type'] == 'baidu'){
            $fromType = 5;
        }

		loadSystemOrg('longlat');
		$longlatClass = new longlat();
		if($fromType){
			$longlat = $longlatClass->toBaidu($_POST['lat'],$_POST['lng'],$fromType);
			if(!$longlat){
				returnCodeOk(array('returnDeliver'=>array(),'userAdress'=>array()));
			}
			$_POST['lat'] = $longlat['lat'];
			$_POST['lng'] = $longlat['lng'];
		}
		
		
		//返回配送员数量
        $deliver = PD('Deliver_user')->hasUser($_POST['lat'],$_POST['lng']);
		$returnDeliver = array();
		if($deliver){
			foreach($deliver as $deliverValue){
                if (abs($deliverValue['now_lat']) > 0 && abs($deliverValue['now_lng']) > 0) {
                    $lng = $deliverValue['now_lng'];
                    $lat = $deliverValue['now_lat'];
                } else {
                    $lng = $deliverValue['lng'];
                    $lat = $deliverValue['lat'];
                }
                if ($_POST['type'] == 'gcj02') {
                    $longlat = $longlatClass->baiduToGcj02($lat,$lng);
                    $lng = $longlat['lng'];
                    $lat = $longlat['lat'];
                }
                $returnDeliver[] = array(
                    'lng' => $lng,
                    'lat' => $lat,
                );
			}
		}
		
		//若用户登录情况下返回离用户最近的收货地址
		if($this->_uid){
			$userAdress = plugin_model('user_adress')->getNearAdress($this->_uid,$_POST['lat'],$_POST['lng']);
			if($userAdress && $_POST['type'] == 'gcj02'){
				$userAdress['lng'] = $userAdress['longitude'];
				$userAdress['lat'] = $userAdress['latitude'];
				$longlat = $longlatClass->baiduToGcj02($userAdress['latitude'],$userAdress['longitude']);
				$userAdress['formartLng'] = $longlat['lng'];
				$userAdress['formartLat'] = $longlat['lat'];
			}
		}
		if(!$userAdress){
			$userAdress = array();
		}
        $return['returnDeliver'] = $returnDeliver;
        $return['userAdress'] = $userAdress;
        $return['advert'] = new \stdClass();
        if (empty($returnDeliver)) {
            $return['advert'] = [
                'image' => $this->config['service_spread_adver_image'],
                'url' => $this->config['service_spread_adver_url']
            ];
        }
        returnCodeOk($return);
	}
	
	//根据用户上报的位置，得到省市区
	public function wxapp_get_location(){
		$fromType = 0;
		if($_POST['type'] == 'gcj02'){
			$fromType = 3;
		}elseif($_POST['type'] == 'baidu'){
            $fromType = 5;
        }

        loadSystemOrg('longlat');
        $longlatClass = new longlat();
		if($fromType){
			$longlat = $longlatClass->toBaidu($_POST['lat'],$_POST['lng'],$fromType);
			if(!$longlat){
				returnCodeOk(array());
			}
			$address_lat = $longlat['lat'];
			$address_lng = $longlat['lng'];
		}else{
			$address_lat = $_POST['lat'];
			$address_lng = $_POST['lng'];
		}
		
		// 指定地点 获得省市区id
		$area_info = plugin_model('Area')->cityMatching($address_lat, $address_lng);
		
		if(empty($area_info['area_id']) || $area_info['is_open'] == 0){
			returnCodeError(L_('当前城市暂未开通该服务'));
		}
		
		if(empty($area_info['area_info']['area_id']) || $area_info['area_info']['area_is_open'] == 0){
			returnCodeError(L_('当前区域暂未开通该服务'));
		}
		
		$return = array(
			'province' 		=> $area_info['area_info']['province_id'],
			'province_txt'  => $area_info['area_info']['province_name'],
			
			'city'  		=> $area_info['area_info']['city_id'],
			'city_txt' 		=> $area_info['area_info']['city_name'],
			
			'area' 			=> $area_info['area_info']['area_id'],
			'area_txt' 		=> $area_info['area_info']['area_name'],
			
			'adress'		=> $_POST['adress'],
			
			'lng'			=> $address_lng,
			'lat'			=> $address_lat,
		);
		if($_POST['type'] == 'gcj02'){
			$return['formartLng'] = $_POST['lng'];
			$return['formartLat'] = $_POST['lat'];
		}
		returnCodeOk($return);
	}

    // 用户发布需求详情
    public function publish_detail(){
        $cid = I('cid');
        $id = I('id');
        $lat = I('lat');
        $lng = I('lng');
        $appid = I('appid', '', 'strval');
        $app_key = I('app_key', '', 'strval');
        $now_city = I('now_city');//城市编号
        $start_city = I('start_city');//发货地城市编号
        if (empty($start_city)) {
            $start_city = !empty($now_city) ? $now_city : 0;
        }

        $address_id = $_POST['address_id'];
        $database_Service_category = D('Service_category');
        $datas = array();
        if ($appid && $app_key) {
            // sign 校验信息
            $p_sign = I('sign', '', 'strval');
            $sign_arr = $_POST;
            unset($sign_arr['sign']);
            unset($sign_arr['app_key']);
            unset($sign_arr['now_lang']);
            $sign = $this->get_encrypt_key($sign_arr, $app_key);
            if ($sign != $p_sign) {
                returnCodeError('数据校验失败');
            }

            $merchant = M('Merchant')->where(array('appid' => $appid, 'key' => $app_key))->find();
            if (empty($merchant)) {
                returnCodeError('平台配置信息错误');
            }
            $datas['merchant'] = array('money' => $merchant['money']);  // 商家金额
            // 获取帮我送类型-取正常的最先添加的
            $now_category = $database_Service_category->field(true)->order('cid ASC')->where(array('type'=>3, 'cat_status' => 1))->find();
            if (empty($now_category)) {
                returnCodeError('平台配送类型不存在，请联系管理员！');
            }
        } elseif (!$this->_uid) {
            returnCodeError('ticket不能为空');
        } else {
            // 获取分类信息
            $now_category = $database_Service_category->field(true)->where(array('cid'=>$cid))->find();
            if (empty($now_category)) {
                returnCodeError('没有找到该分类信息');
            }
        }

        //返回配送员数量
        if ($lat>0 && $lng>0) {
            $deliver = PD('Deliver_user')->hasUser($lat,$lng);
            $datas['deliver_count'] = count($deliver);
        }

        $adress_list = plugin_model('user_adress')->get_adress_list($this->_uid);
        foreach ($adress_list as $key => $value) {
            if($value['adress_id'] == $_GET['adress_id']){
                $addressInfo = $value;
            }elseif($value['default'] == 1 && $now_category['type'] != 2){
                $addressInfo = $value;
            }
        }


        $address_msg = array();
        $publish_config = array();
       

        if ($lng > 0 && $lat > 0) {
            $area_info = PD('Area')->cityMatching($lat, $lng);
            if ($area_info['area_info']['province_id']) {
                $address_msg['province_id'] = $area_info['area_info']['province_id'];
            }
            if ($area_info['area_info']['city_id'] && $area_info['area_info']['city_id'] > $address_msg['province_id']) {
                $address_msg['city_id'] = $area_info['area_info']['city_id'];
            }
            if ($area_info['area_info']['area_id'] && $area_info['area_info']['area_id'] > 0) {
                $address_msg['area_id'] = $area_info['area_info']['area_id'];
            }
        } elseif ($address_id) {
            $adress_info = plugin_model("user_adress")->get_adress($this->_uid,$address_id);
            $address_msg['province_id'] = $adress_info['province'];
            $address_msg['city_id'] = $adress_info['city'];
            $address_msg['area_id'] = $adress_info['area'];
        }elseif($addressInfo){
            $address_msg = array(
                'province_id' =>  $addressInfo['province'],
                'city_id' =>  $addressInfo['city'],
                'area_id' =>  $addressInfo['area'],
            );
        }else{
            $addressInfo = getObj();
        }
        
        if ($address_msg) {
            $publish_config = PD('Deliver_service_set')->address_publish_config($address_msg);
        }

        // 如果未开启 帮我买 分段时间配送配置
        if ($now_category['type'] == 2 && !$this->config['service_buy_deliver_times_switch']) {
            if ($publish_config && $publish_config['buy_status'] == 1) {
                $config_time['service_basic_distance'] = $publish_config['buy_base_distance'];
                $config_time['service_delivery_fee'] = $publish_config['buy_base_fee'];
                $config_time['service_per_km_price'] = $publish_config['buy_per_km_price'];
            } else {
                // 如果配置全空且不为0，仍然取之前的配置一中的配置
                $service_give_basic_distance = $this->config['service_give_basic_distance'];
                $service_give_delivery_fee = $this->config['service_give_delivery_fee'];
                $service_give_per_km_price = $this->config['service_give_per_km_price'];
                $config_time = array();
                $service_give_basic_distance_if =  (!$service_give_basic_distance && $service_give_basic_distance !== 0 && $service_give_basic_distance !== '0');
                $service_give_delivery_fee_if =  (!$service_give_delivery_fee && $service_give_delivery_fee !== 0 && $service_give_delivery_fee !== '0');
                $service_give_per_km_price_if =  (!$service_give_per_km_price && $service_give_per_km_price !== 0 && $service_give_per_km_price !== '0');
                if ($service_give_basic_distance_if && $service_give_delivery_fee_if && $service_give_per_km_price_if) {
                    $config_time['service_basic_distance'] = $this->config['service_basic_distance'];
                    $config_time['service_delivery_fee'] = $this->config['service_delivery_fee'];
                    $config_time['service_per_km_price'] = $this->config['service_per_km_price'];
                } else {
                    $config_time['service_basic_distance'] = $this->config['service_give_basic_distance'] ? $this->config['service_give_basic_distance'] : 0;
                    $config_time['service_delivery_fee'] = $this->config['service_give_delivery_fee'] ? $this->config['service_give_delivery_fee'] : 0;
                    $config_time['service_per_km_price'] = $this->config['service_give_per_km_price'] ? $this->config['service_give_per_km_price'] : 0;
                }
            }
            $daysList = array();
            $timeList = array();

            // 帮我买商家单独设置起送费
            if ($id) {
                $config_time['service_delivery_fee'] = PD('Deliver_service_set')->getStoreDeliveryFee($id, $config_time['service_delivery_fee']);
            }
        } else {
            // 获取对应时间信息
            $time_info = PD('Deliver_service_set')->publish_time_info($now_category['type'], $address_msg, true, $id);
            $config_time = $time_info['config_time'];
            $daysList = $time_info['daysList'];
            $timeList = $time_info['timeList'];
        }
        //开启了运费模板
        if (C('config.open_paotui_price_template') && in_array($now_category['type'], [2, 3])) {
            try {
                $from = $_POST['from'] ?? '';
                $specialDelivery = intval($_POST['special_delivery']);
                if ($now_category['type'] == 2) {
                    //帮我买
                    $business = 1;
                } else if ($now_category['type'] == 3) {
                    //帮我送
                    $business = $from == 'merchant' ? 3 : 2;
                }
                $result = D('Paotui_price_template')->calFee($business, 1, 1, $specialDelivery,$_POST['mer_id']);
                $baseFee = $result['basic_distance_price'];
            } catch (\Exception $e) {
                returnCodeError($e->getMessage());
            }
        }

        $datas['now_category'] = $now_category; //分类详情

        if (!$appid || !$app_key) {
            $datas['addressInfo'] = $addressInfo; //默认地址
            $datas['adress_list'] = array_values($adress_list); //地址列表
        }
        $datas['config_time'] = $config_time;
        //重新排序时间
        ksort($timeList);
        foreach($timeList as $value){
            $daysList[$value[0]['date']]['timeList'] = $value;
        }

        $datas['daysList'] = array_values($daysList);
        if(empty($datas['daysList'][0]['timeList'])){
            unset($datas['daysList'][0]);
            $datas['daysList']  = array_merge($datas['daysList']);
        }

        //配送优惠券
        $discountList = [];
        if (!empty($start_city) && !empty($datas['daysList'])) {
            $bindCityIds = M('Service_time_discount_bind_area')->where(['city_id' => $start_city])->field('discount_id')->select();
            $bindCityIds = array_column($bindCityIds, 'discount_id');
            $discountList = M('Service_time_discount')->where(['discount_id' => ['in', $bindCityIds], 'is_del' => 0])->select();
            if ($discountList) {
                foreach ($discountList as $disk => &$disv) {
                    $orderCount = M('Service_time_discount')->where(['address_area_id' => $start_city, 'time_discount_id' => $disv['id']])->count();
                    if ($orderCount >= $disv['number']) {//优惠次数已用完
                        unset($discountList[$disk]);
                        continue;
                    }
                    $start_time = explode(':', $disv['start_time']);
                    $disv['stime'] = $start_time[0] * 3600 + $start_time[1] * 60 + $start_time[2];
                    $end_time = explode(':', $disv['end_time']);
                    $disv['etime'] = $end_time[0] * 3600 + $end_time[1] * 60 + $end_time[2];
                }
                $discountList = array_values($discountList);
            }
        }
        foreach ($datas['daysList'] as &$dv) {
            if (!empty($dv['timeList'])) {
                foreach ($dv['timeList'] as &$tv) {
                    $tv['discount_money'] = "0";
                    $tv['discount_id'] = "0";
                    if(isset($baseFee)){
                        $tv['delivery_fee'] = $baseFee;
                    }
                    if ($discountList) {
                        $daytime = explode(':', $tv['time']);
                        $daytime = $daytime[0] * 3600 + $daytime[1] * 60;
                        foreach ($discountList as $disv1) {
                            if ($daytime >= $disv1['stime'] && $daytime <= $disv['etime']) {
                                $tv['discount_money'] = $disv1['money'];
                                $tv['discount_id'] = $disv1['discount_id'];
                            }
                        }
                    }
                }
            }
        }

        $config['service_basic_km'] = $this->config['service_basic_km'];
        $config['service_basic_km_time'] = $this->config['service_basic_km_time'];
        $config['service_basic_weight'] = $this->config['service_basic_weight'];
        $config['service_basic_weight_price'] = $this->config['service_basic_weight_price'];
        $config['service_bounds_weight'] = $this->config['service_bounds_weight'];
        $config['service_days_number'] = $config_time['service_days_number'];
        $config['count_freight_charge_method'] = $this->config['count_freight_charge_method'];

        if (!$appid || !$app_key) {
            $config['service_weight_multilevel_switch'] = $this->config['service_weight_multilevel_switch'] ? $this->config['service_weight_multilevel_switch'] : 0;
            $config['service_buy_deliver_times_switch'] = $this->config['service_buy_deliver_times_switch'] ? $this->config['service_buy_deliver_times_switch'] : 0;
        }

        $datas['config'] = $config;
        if ($now_category['type'] == 2) {
            // 帮我买
            // 判断获取 重量档次信息
            if($this->config['service_weight_multilevel_switch'] == 1) {
                $service_buy_weight_info = $this->config['service_buy_weight_info'];
                $service_buy_weight_info = unserialize($service_buy_weight_info);
                // start_weight 范围起始公斤数 end_weight 范围结束公斤数 范围价格 weight_price
                foreach ($service_buy_weight_info as $key => &$val) {
                    if ($val && $val['start_weight']>=0 && $val['start_weight']!='' && $val['end_weight'] && $val['weight_price']>=0 && $val['weight_price']!='') {
                        $val['msg'] = $val['start_weight'] . 'kg-' . $val['end_weight'] . 'kg';
                    } else {
                        unset($service_buy_weight_info[$key]);
                    }
                }
                if ($service_buy_weight_info) {
                    $datas['service_weight_info'] = $service_buy_weight_info;
                } else {
                    $datas['service_weight_info'] = array();
                }
            }

        } elseif ($now_category['type'] == 3) {
            // 帮我送
            // 判断获取 重量档次信息
            if($this->config['service_weight_multilevel_switch'] == 1) {
                $service_give_weight_info = $this->config['service_give_weight_info'];
                $service_give_weight_info = unserialize($service_give_weight_info);
                // start_weight 范围起始公斤数 end_weight 范围结束公斤数 范围价格 weight_price
                foreach ($service_give_weight_info as $key => &$val) {
                    if ($val && $val['start_weight']>=0 && $val['start_weight']!=''  && $val['end_weight'] && $val['weight_price']>=0 && $val['weight_price']!='') {
                        // 拼接信息
                        $val['msg'] = $val['start_weight'] . 'kg - ' . $val['end_weight'] . 'kg';
                    } else {
                        unset($service_give_weight_info[$key]);
                    }
                }
                if ($service_give_weight_info) {
                    $datas['service_weight_info'] = $service_give_weight_info;
                } else {
                    $datas['service_weight_info'] = array();
                }
            }
        }
        returnCodeOk($datas);
    }

    // 单独获取重量信息
    public function service_weight_info() {
        $datas = array();
        $datas['service_weight_multilevel_switch'] = $this->config['service_weight_multilevel_switch'];
        if ($_POST['type'] == 2) {
            // 帮我买
            // 判断获取 重量档次信息
            if($this->config['service_weight_multilevel_switch'] == 1) {
                $service_buy_weight_info = $this->config['service_buy_weight_info'];
                $service_buy_weight_info = unserialize($service_buy_weight_info);
                // start_weight 范围起始公斤数 end_weight 范围结束公斤数 范围价格 weight_price
                foreach ($service_buy_weight_info as $key => &$val) {
                    if ($val && $val['start_weight']>=0 && $val['start_weight']!='' && $val['end_weight'] && $val['weight_price']>=0 && $val['weight_price']!='') {
                        $val['msg'] = $val['start_weight'] . 'kg-' . $val['end_weight'] . 'kg';
                    } else {
                        unset($service_buy_weight_info[$key]);
                    }
                }
                if ($service_buy_weight_info) {
                    $datas['service_weight_info'] = $service_buy_weight_info;
                } else {
                    $datas['service_weight_info'] = array();
                }
            }

        } elseif ($_POST['type'] == 3) {
            // 帮我送
            // 判断获取 重量档次信息
            if($this->config['service_weight_multilevel_switch'] == 1) {
                $service_give_weight_info = $this->config['service_give_weight_info'];
                $service_give_weight_info = unserialize($service_give_weight_info);
                // start_weight 范围起始公斤数 end_weight 范围结束公斤数 范围价格 weight_price
                foreach ($service_give_weight_info as $key => &$val) {
                    if ($val && $val['start_weight']>=0 && $val['start_weight']!='' && $val['end_weight'] && $val['weight_price']>=0 && $val['weight_price']!='') {
                        // 拼接信息
                        $val['msg'] = $val['start_weight'] . 'kg - ' . $val['end_weight'] . 'kg';
                    } else {
                        unset($service_give_weight_info[$key]);
                    }
                }
                if ($service_give_weight_info) {
                    $datas['service_weight_info'] = $service_give_weight_info;
                } else {
                    $datas['service_weight_info'] = array();
                }
            }
        }

        returnCode('0',$datas);
    }

	// 计算两个收货地址的距离（骑行/直线）帮我送
    public function get_distance(){
		$returnArr = array();
		$returnArr['is_ride'] = false;
        $appid = I('appid', '', 'strval');
        $app_key = I('app_key', '', 'strval');

        if ($appid && $app_key) {
            // sign 校验信息
            $p_sign = $_POST['sign'];
            $sign_arr = $_POST;
            unset($sign_arr['sign']);
            unset($sign_arr['app_key']);
            unset($sign_arr['now_lang']);
            $sign = $this->get_encrypt_key($sign_arr, $app_key);
            if ($sign != $p_sign) {
                returnCodeError('数据校验失败');
            }
            $merchant = M('Merchant')->where(array('appid' => $appid, 'key' => $app_key))->find();
            if (empty($merchant)) {
                returnCodeError('平台配置信息错误');
            }
        }

        //计算配送距离
        $distance = 0;
        if (C('config.service_is_riding_distance') && C('config.baidu_map_ak')) {
            loadSystemOrg('longlat');
            $longlat_class = new longlat();
            $distance = $longlat_class->getRidingDistance($_POST['start_lat'],$_POST['start_lng'],$_POST['end_lat'],$_POST['end_lng']);
            if ($distance == -1) {
                returnCodeError('距离计算失败');
            }
			if($distance){
				$returnArr['is_ride'] = true;
			}
        }
        $distance || $distance = getDistance($_POST['start_lat'],$_POST['start_lng'],$_POST['end_lat'],$_POST['end_lng']);
        $distance = $distance / 1000;
		$returnArr['distance'] = $distance;
        returnCodeOk($returnArr);
    }


    // 计算两个收货地址的距离（骑行/直线）帮我送
    private function distance_calculation($data){
        //计算配送距离
        $distance = 0;
        if (C('config.service_is_riding_distance') && C('config.baidu_map_ak')) {
            loadSystemOrg('longlat');
            $longlat_class = new longlat();
            $distance = $longlat_class->getRidingDistance($data['start_lat'],$data['start_lng'],$data['end_lat'],$data['end_lng']);
            if ($distance == -1) {
                returnCodeError('距离计算失败');
            }
        }
        $distance || $distance = getDistance($data['start_lat'],$data['start_lng'],$data['end_lat'],$data['end_lng']);
        $distance = $distance / 1000;
        return $distance;
    }

    // 服务快派 金钱计算方式
    private function publish_count_method($price) {
        // 计算完成后按照 配送配置-配送条件-配送计算方式
        // 0 精确到分  1 向上取整到角收取 2 向下取整到角收取  3 四舍五入到角收取  4 向上取整到元收取  5 向下取整到元收取 6 四舍五入到元收取
        switch (intval($this->config['count_freight_charge_method'])) {
            case 0:
                $price = round_number($price, 2);
                break;
            case 1:
                $price = ceil($price * 10) / 10;
                break;
            case 2:
                $price = floor($price * 10) / 10;
                break;
            case 3:
                $price = round_number($price, 1);
                break;
            case 4:
                $price = ceil($price);
                break;
            case 5:
                $price = floor($price);
                break;
            case 6:
                $price = round_number($price);
                break;
        }
        return $price;
    }


    // 帮我送
    public function publish_give_data(){
        $time_info = strtotime(I('time_info'));
        if($time_info < time() && !$_POST['is_voice']){
            returnCodeError('送达时间已超过当前时间');
        }
        $appid = I('appid', '', 'strval');
        $app_key = I('app_key', '', 'strval');
        $discount_money = I('discount_money', 0, 'intval');
        $discount_id = I('discount_id', 0, 'intval');
        $coupon_id = I('coupon_id') > 0 ? I('coupon_id', 0, 'intval') : 0;
        $coupon_money = I('coupon_money', 0, 'intval');
        $is_third = 0;
        $mer_id = 0;
        $data = array();

        if ($appid && $app_key) {
            // sign 校验信息
            $p_sign = $_POST['sign'];
            $sign_arr = $_POST;
            if ($_POST['goods_detail']) {
                $goods_detail = unserialize($_POST['goods_detail']);
                $_POST['goods_detail'] = $goods_detail;
            }
            unset($sign_arr['goods_detail']);
            unset($sign_arr['sign']);
            unset($sign_arr['app_key']);
            unset($sign_arr['now_lang']);
            $sign = $this->get_encrypt_key($sign_arr, $app_key);
            if ($sign != $p_sign) {
                returnCodeError('数据校验失败');
            }
            // 校验必传字段
            if (empty($_POST['time_info'])){
                $service_deliver_send_time = $this->config['service_deliver_send_time']>0?$this->config['service_deliver_send_time']:0;
                $_POST['time_info'] = time() + ($service_deliver_send_time*60);
                $_POST['time_info'] = date('Y-m-d H:i:s', $_POST['time_info']);
            }
            if (strtotime($_POST['time_info']) <= time()) {
                returnCodeError('上传时间格式错误或者小于当前时间');
            }
            if (!$_POST['notifyurl']) {
                returnCodeError('请上传回调地址');
            }
            if (!$_POST['third_id']) {
                returnCodeError('请上传订单');
            }
            $data['notifyurl'] = $_POST['notifyurl'];
            $merchant = M('Merchant')->where(array('appid' => $appid, 'key' => $app_key))->find();
            if (empty($merchant)) {
                returnCodeError('平台配置信息错误');
            }
            // 获取帮我送类型-取正常的最先添加的
            $database_Service_category = D('Service_category');
            $now_category = $database_Service_category->field(true)->order('cid ASC')->where(array('type'=>3, 'cat_status' => 1))->find();
            if (empty($now_category)) {
                returnCodeError('平台配送类型不存在，请联系管理员！');
            }
            $is_third = 1;
            $mer_id = $merchant['mer_id'];
        } elseif (!$this->_uid){
            returnCodeError('登录信息有误，请重新登录');
        }

        $buyer = D('User')->where(['uid' => $this->_uid])->field('uid,phone')->find();
        if (empty($buyer['phone'])) {
            returnCodeError(L_('当前用户未绑定手机号，请先绑定手机号后下单'));
        }

        if(!$_POST['is_voice']){
            if (!$_POST['start_adress_lat'] || !$_POST['start_adress_lng'] || !$_POST['end_adress_lat'] || !$_POST['end_adress_lng']) {
                returnCodeError(L_('请选择购买/送达地址！'));
            }
        }

        if(empty($_POST['weight']) && $this->config['service_weight_multilevel_switch'] != 1){
            $_POST['weight'] = $this->config['service_basic_weight'];
        }

        // 如果传入了时间值
        if (empty($_POST['time_info']) && (empty($_POST['fetch_time']) || empty($_POST['per_km_price']) || empty($_POST['basic_distance']))) {
            returnCodeError('参数有误');
        }
        if (empty($_POST['fetch_time']) && $_POST['time_info']) {
            $_POST['fetch_time'] = $_POST['time_info'];
        }

        $data['address'] = trim($_POST['start_adress_detail']);
        $data['address_lng'] = $_POST['start_adress_lng'];
        $data['address_lat'] = $_POST['start_adress_lat'];

        if ($_POST['start_province_id'] && $_POST['start_city_id'] && $_POST['start_area_id']) {
            $data['province_id'] = $_POST['start_province_id'];
            $data['city_id'] = $_POST['start_city_id'];
            $data['area_id'] = $_POST['start_area_id'];
        } else {
            if (empty($_POST['start_adress_lat']) || empty($_POST['start_adress_lng'])) {
                returnCodeError('请选择取货地址！');
            }
            // 指定地点 获得省市区id
            $area_info = PD('Area')->cityMatching($_POST['start_adress_lat'], $_POST['start_adress_lng']);
            $data['province_id'] = $area_info['area_info']['province_id'];
            $data['city_id'] = $area_info['area_info']['city_id'];
            $data['area_id'] = $area_info['area_info']['area_id'];
        }
        $start_adress_area_id = $_POST['start_adress_area_id'] ? $_POST['start_adress_area_id'] : 0;
        if ($data['city_id']) {
            $start_adress_area_id = $data['city_id'];
        }
        $data['address_area_id'] = $start_adress_area_id;

        // 如果有图片上传处理图片
        $img = '';
        if(!empty($_FILES)){
            $upload_file = PD('Image')->handle($this->_uid, 'service', 0, array('size' => 20), false);
            if ($upload_file['error'] == 1){
                returnCodeError(L_('上传失败！') . $upload_file['message'] . L_('请重试！'));
            }
            foreach ($_FILES as $key => $val) {
                if($val['imgFile']['error'] == 4){
                    returnCodeError('没有选择图片');
                }
                if($val['imgFile']['error'] == 3){
                    returnCodeError('文件只有部分被上传');
                }
                if (is_array($upload_file['url'][$key])) {
                    $img_key = reset($upload_file['url'][$key]);
                } else {
                    $img_key = $upload_file['url'][$key];
                }
                if ($img && $img_key) {
                    $img .= ';'.$img_key;
                } else {
                    $img .= $img_key;
                }
            }
        }

        // 如果关闭了 服务商抢单 不论什么情况均走 平台配送
        if($this->config['service_provider_is_grab'] == 1) {
            $data['deliver_type'] = 1;
        } elseif (M('Area')->where(['area_id'=>$data['city_id']])->getField('deliver_model')) {
            // 开启了手动调度 不论是否存在平台配送员 均默认选择 平台配送
            $data['deliver_type'] = 1;
        } else {
            // 开启抢单模式情况 如果存在平台配送员 选择平台配送 没有 选择商家配送
            //获取平台配送员信息
            $deliver = PD('Deliver_user')->hasUser($data['address_lat'],$data['address_lng']);
            if(!empty($deliver)){
                $data['deliver_type'] = 1;
            }else{
                $data['deliver_type'] = 2;
            }
        }

        $_POST['is_service'] = true;
        $compute_money = $this->compute_money();
        unset($_POST['is_service']);
        $destance_sum = $compute_money['distance_num'];
        $basic_distance_price = $compute_money['basic_distance_price'];
        $distance_price = $compute_money['extra_distance_price'];
        $weight_price = $compute_money['weight_price'];
        $total_price = $compute_money['total_price'];
        $tip_price = $_POST['tip_price'] ? $_POST['tip_price'] : 0;
        //增值服务费用、名称、是否专送
        $added_fee = $compute_money['added_fee'] ?? 0;
        $added_fee_name = $compute_money['added_fee_name'] ?? '';
        $added_fee_list = $compute_money['added_fee_list'] ?? [];
        $special_delivery = isset($_POST['special_delivery']) ? intval($_POST['special_delivery']) : 0;
        $data['add_time'] = time();

        if ($is_third<0) {
            // 三方一定为平台配送
            $data['deliver_type'] = 1;
        }

        if ($is_third>0 && $now_category) {
            $data['cid'] = $now_category['cid'];
        } else {
            $data['cid'] = $_POST['cid'];
        }
        if ($is_third>0 && $_POST['user_id']>0) {
            $data['uid'] = $_POST['user_id'];
        } elseif($is_third<=0) {
            $data['uid'] = $this->_uid;
        }

        if ($is_third>0 && $_POST['third_id']) {
            $data['third_id'] = $_POST['third_id'];
        }
        $data['is_third'] = $is_third;
        $data['mer_id'] = $mer_id;

        $data['catgory_type'] = 3;
        $data['status'] = 1;
        $data['destance_sum'] = $destance_sum;
        $nowtime = date("mdHis");
        $data['order_sn'] = $nowtime.rand(10,99).sprintf("%08d",$this->_uid);
        // 添加 跑腿和服务快派小程序下单区分
        if ($this->config['pay_wxapp_paotui_appid'] && $this->config['pay_wxapp_paotui_mchid']) {
            $data['service_type'] = 1;
        } else {
            $data['service_type'] = 0;
        }
        if (trim($_POST['weight_tip'])) {
            $data['weight_tip'] = trim($_POST['weight_tip']);
        } elseif($_POST['weight']) {
            $data['weight_tip'] = trim($_POST['weight']);
        } elseif($compute_money['weight']) {
            $data['weight_tip'] = trim($compute_money['weight']);
        }

        if($_POST['publish_id']){// 已生成订单，生成尾款
            $res = $_POST['publish_id'];
            $publishOrder = D('Service_user_publish')->where(['publish_id'=>$_POST['publish_id']])->find();
            if(empty($publishOrder)){
                returnCodeError(L_('订单信息有误'));
            }
            if(!D('Service_user_publish')->where(['publish_id'=>$_POST['publish_id']])->save(['voice_status'=>1, 'destance_sum'=>$destance_sum])){
                returnCodeError(L_('订单更新错误'));
            }
        }else{// 生成首次订单
            if($_POST['is_voice']){// 语音发单订单
                $data['is_voice'] = 1;// 记录订单类型
                $data['voice_url'] = $_POST['voice_url'];// 语音地址
                $data['voice_time'] = $_POST['voice_time'];// 语音时长
            }
            $data['time_discount_money'] = $discount_money;
            $data['time_discount_id'] = $discount_id;
            if ($coupon_id) {
                if(!$coupon_money){
                    $system_coupon = M('System_coupon')->where(['coupon_id'=>$coupon_id])->find();
                    $coupon_money = $system_coupon['discount'] ?: 0;
                }
                $data['system_coupon_id'] = $coupon_id;
                $data['system_coupon_price'] = $coupon_money;
            }
            if(C('config.mall_third_deliver_open')){
                $data['is_third_deliver'] = 1;
            }
            $res = D('Service_user_publish')->data($data)->add();
            fdump_api([$data,D('Service_user_publish')->getLastSql()],'yzz111',1);
        }

        if($res){
            if ($coupon_id) {//使用优惠券
                $hadpullData = M('System_coupon_hadpull')->where(['coupon_id' => $coupon_id, 'uid' => $this->_uid, 'is_use' => 0])->find();
                if ($hadpullData) {
                    M('System_coupon_use_list')->data([
                        'order_type' => 'deliver',
                        'order_id' => $res,
                        'hadpull_id' => $hadpullData['id'],
                        'coupon_id' => $coupon_id,
                        'uid' => $this->_uid,
                        'num' => 1,
                        'use_time' => time()
                    ])->add();
                    if ($hadpullData['num'] == 1) {
                        M('System_coupon_hadpull')->where(['id' => $hadpullData['id']])->data(['is_use' => 1, 'use_time' => time()])->save();
                    }
                }
            }

            $giveData['publish_id'] =  $res;

            if ($is_third>0 && $now_category) {
                $giveData['cid'] = $now_category['cid'];
            } else {
                $giveData['cid'] = $_POST['cid'];
            }

            if ($is_third<=0) {
                $giveData['goods_catgory'] =  $_POST['goods_catgory'] ?? '';
            } elseif($_POST['goods_catgory']) {
                $giveData['goods_catgory'] =  $_POST['goods_catgory'] ?? '';
            }
            $giveData['weight'] =  $_POST['weight'];

            if ($_POST['img']) {
                $giveData['img'] = $_POST['img'];
            } elseif ($img) {
                $giveData['img'] = $img;
            }
            if ($_POST['price']) {
                $giveData['price'] = $_POST['price'];
            }
            if ($_POST['third_price']) {
                $giveData['third_price'] = $_POST['third_price'];
            }
            $giveData['is_third'] = $is_third;
            $giveData['mer_id'] = $mer_id;

            $giveData['start_adress_name'] =  trim($_POST['start_adress_detail']) ?? '';
            $giveData['start_adress_lng'] =  $_POST['start_adress_lng'] ?? '';
            $giveData['start_adress_lat'] =  $_POST['start_adress_lat'] ?? '';
            $giveData['start_adress_nickname'] =  $_POST['start_adress_name'] ?? '';
            $giveData['start_adress_phone'] =  $_POST['start_adress_phone'] ?? '';

            $giveData['end_adress_name'] =  trim($_POST['end_adress_detail']) ?? '';
            $giveData['end_adress_lng'] =  $_POST['end_adress_lng'] ?? '';
            $giveData['end_adress_lat'] =  $_POST['end_adress_lat'] ?? '';
            $giveData['end_adress_nickname'] =  $_POST['end_adress_name'] ?? '';
            $giveData['end_adress_phone'] =  $_POST['end_adress_phone'] ?? '';

            $giveData['fetch_time'] =  $_POST['fetch_time'];
            $giveData['arrival_time_info'] = $_POST['time_info'] ?: str_replace('  ', ' ', preg_replace('/[^\d\-\:\s]+/u', '', $_POST['fetch_time']));

            $giveData['tip_price'] =  $tip_price;
            if ($_POST['remarks']) {
                $giveData['remarks'] = $this->filterEmoji2($_POST['remarks']);
            }

            $giveData['added_fee'] =  $added_fee;
            $giveData['added_fee_name'] =  $added_fee_name;
            $giveData['added_fee_list'] =  json_encode($added_fee_list,JSON_UNESCAPED_UNICODE);
            $giveData['special_delivery'] =  $special_delivery;
            $giveData['from_mer_id'] = isset($_POST['mer_id']) ? intval($_POST['mer_id']) : 0;
            $giveData['price_template_id'] = $compute_money['price_template_id'] ?? 0;

            $giveData['basic_distance_price'] =  $basic_distance_price;
            $giveData['distance_price'] =  $distance_price;
            $giveData['weight_price'] =  $weight_price;

            // 把重量记录下
            if ($_POST['weight']) {
                $buyData['weight'] = $_POST['weight'];
            } elseif($compute_money['weight']) {
                $buyData['weight'] = $compute_money['weight'];
            }
            if ($compute_money['weight_info']) {
                $buyData['weight_info'] = $compute_money['weight_info'];
            }

            $giveData['total_price'] =  $total_price;
            $giveData['addtime'] =  time();

            if ($is_third>0 && $_POST['user_id']>0) {
                $giveData['uid'] =  $_POST['user_id'];
            } elseif($is_third<=0) {
                $giveData['uid'] = $this->_uid;
            }

            if($_POST['is_voice']){// 语音发单订单
                if($_POST['is_final_pay']){//记录基础费用
                    unset($giveData['add_time']);
                }else{
                    $giveData['basic_price'] = $total_price;// 记录基础费用
                }
            }

            if($_POST['publish_id']){
                $giveOrderInfo = D("Service_user_publish_give")->where(['publish_id' => $_POST['publish_id']])->find();
                if(empty($giveOrderInfo)){
                    returnCodeError(L_('订单信息有误'));
                }
                $give_res = D("Service_user_publish_give")->where(['publish_id' => $_POST['publish_id']])->data($giveData)->save();
            }else{
                $give_res = D("Service_user_publish_give")->data($giveData)->add();
            }
            if(!$give_res){
                fdump_sql(D('Service_user_publish_give')->getLastSql(),'Service_user_publish_give_add_err');
                D('Service_user_publish')->where(array('publish_id'=>$res))->delete();
                returnCodeError(L_('数据添加异常请重试！'));
            }

            $pay_order_param = array(
                'business_type' => 'service',
                'business_id' => $res,
                'order_name' => L_('帮我送订单'),
                'total_money' => $giveOrderInfo ? getFormatNumber($total_price-$giveOrderInfo['basic_price']) : $total_price,
                'wx_cheap' => 0,
                'is_final_pay' => $_POST['is_final_pay'] ?? 0,// 是否尾款支付
            );
            if ($is_third>0 && $_POST['user_id']>0) {
                $pay_order_param['uid'] =  $_POST['user_id'];
            } elseif($is_third<=0) {
                $pay_order_param['uid'] = $this->_uid;
            }

            if ($appid && $app_key) {
                // 处理成商家余额支付
                $money = $merchant['money'] > 0 ? $merchant['money'] : 0;
                if ($money < $total_price) {
                    returnCodeError('商家余额不足，请先充值！');
                }
                $des = '使用平台配送，商家对应扣除余额';
                $save_result = PD('Merchant_money_list')->use_money($mer_id,$total_price,'service', $des ,$res);
                if ($save_result['error_code']) {
                    returnCodeError($save_result['error_msg']);
                }
                $pay_order_param['pay_time'] = time();
                $pay_order_param['pay_money'] = 0;//在线支付的钱
                $pay_order_param['is_mobile_pay'] = $_POST['is_mobile']>0 ? $_POST['is_mobile'] : 0; // 是否是手机支付 0 1 微信 2 手机 3 小程序
                $pay_order_param['paid'] = 1;

                $plat_order_result = PD('Plat_order')->add_order($pay_order_param);
                if (!$plat_order_result['error_code']) {
                    $pay_order_param['order_id'] = $plat_order_result['order_id'];
                } elseif ($plat_order_result['error_code']) {
                    returnCodeError($plat_order_result['error_msg']);
                }
                PD('Service_order')->after_pay($res,$pay_order_param);
                if($is_third<=0) {
                    $now_user = M('User')->where(array('uid' => $this->_uid))->field(true)->find();
                } else {
                    $now_user = array();
                }
                // 添加记录
                $log = array(
                    'publish_id' => $res,
                    'uid' => $pay_order_param['uid']?$pay_order_param['uid']:0,
                    'status' => 1,
                    'name' => $now_user?$now_user['name']:'',
                    'phone' => $now_user?$now_user['phone']:'',
                    'remark' => '用户已经支付待配送员接单',
                );
                D('Service_user_publish_log')->add_log($log);

                $returnArr = array(
                    'msg' => '需求已发布!',
                    'publish_id' => $res,
                    'order_id' => $plat_order_result['order_id']
                );
                returnCodeOk($returnArr);
            } else {
                $plat_order_result = PD('Plat_order')->add_order($pay_order_param);
            }
            if ($plat_order_result['error_code']) {
                returnCodeError($plat_order_result['error_msg']);
            } else {
                if($is_third<=0) {
                    $now_user = M('User')->where(array('uid' => $this->_uid))->field(true)->find();
                } else {
                    $now_user = array();
                }
                // 添加记录
                $log = array(
                    'publish_id' => $res,
                    'uid' => $pay_order_param['uid']?$pay_order_param['uid']:0,
                    'status' => 0,
                    'name' => $now_user?$now_user['name']:'',
                    'phone' => $now_user?$now_user['phone']:'',
                    'remark' => '用户下单',
                );
                D('Service_user_publish_log')->add_log($log);
                $returnArr = array(
                    'msg' => '需求已发布,请去支付!',
                    'order_id' => $plat_order_result['order_id'],
                    'type' => 'plat'
                );
                returnCodeOk($returnArr);
            }
        }else{
            returnCodeError('发布失败请重试!');
        }
    }

    // 帮我买
    public function publish_buy_data(){
        if (!$this->_uid) {
            returnCodeError('ticket不能为空');
        }
        $d_are = PD('Area');

        if (empty($_POST['estimate_goods_price'])) {
            returnCodeError(L_('请填写估价！'));
        }

        $time_info = strtotime($_POST['time_info']);
        if($time_info < time()){
            //returnCodeError('送达时间已超过当前时间');
        }
        $discount_money = I('discount_money', 0, 'intval');
        $discount_id = I('discount_id', 0, 'intval');
        $coupon_id = I('coupon_id', 0, 'intval');
        $coupon_money = I('coupon_money', 0, 'intval');

        $buyer = D('User')->where(['uid' => $this->_uid])->field('uid,phone')->find();
        if (empty($buyer['phone'])) {
            returnCodeError(L_('当前用户未绑定手机号，请先绑定手机号后下单'));
        }

        // 获取提交的收获地址信息
        if($_POST['buy_type'] == 2){
            if (!$_POST['start_adress_lat'] || !$_POST['start_adress_lng'] || !$_POST['end_adress_lat'] || !$_POST['end_adress_lng']) {
                returnCodeError(L_('请选择购买/送达地址！'));
            }
            // 指定地点 获得省市区id
            $data['address'] = trim($_POST['start_adress_detail']);
            $data['address_lng'] = $_POST['start_adress_lng'];
            $data['address_lat'] = $_POST['start_adress_lat'];
            if ($_POST['start_province_id'] && $_POST['start_city_id'] && $_POST['start_area_id']) {
                $data['province_id'] = $_POST['start_province_id'];
                $data['city_id'] = $_POST['start_city_id'];
                $data['area_id'] = $_POST['start_area_id'];
            } else {
                if (empty($_POST['start_adress_lat']) || empty($_POST['start_adress_lng'])) {
                    returnCodeError(L_('请选择购买地址！'));
                }
                // 指定地点 获得省市区id
                $area_info = $d_are->cityMatching($_POST['start_adress_lat'], $_POST['start_adress_lng']);
                $data['province_id'] = $area_info['area_info']['province_id'];
                $data['city_id'] = $area_info['area_info']['city_id'];
                $data['area_id'] = $area_info['area_info']['area_id'];
            }
            $start_adress_area_id = $_POST['start_adress_area_id'] ? $_POST['start_adress_area_id'] : 0;
            if ($data['city_id']) {
                $start_adress_area_id = $data['city_id'];
            }
            $data['address_area_id'] = $start_adress_area_id;

        }else{
            $data['address'] = trim($_POST['end_adress_detail']);
            $data['address_lng'] = $_POST['end_adress_lng'];
            $data['address_lat'] = $_POST['end_adress_lat'];

            if ($_POST['end_province_id'] && $_POST['end_city_id'] && $_POST['end_area_id']) {
                $data['province_id'] = $_POST['end_province_id'];
                $data['city_id'] = $_POST['end_city_id'];
                $data['area_id'] = $_POST['end_area_id'];
            } else {
                if (empty($_POST['end_adress_lat']) || empty($_POST['end_adress_lng'])) {
                    returnCodeError(L_('请选择收货地址！'));
                }
                // 指定地点 获得省市区id
                $area_info = $d_are->cityMatching($_POST['end_adress_lat'], $_POST['end_adress_lng']);
                $data['province_id'] = $area_info['area_info']['province_id'];
                $data['city_id'] = $area_info['area_info']['city_id'];
                $data['area_id'] = $area_info['area_info']['area_id'];
            }
            $adress_area_id = $_POST['end_adress_area_id'] ? $_POST['end_adress_area_id'] : 0;
            if ($data['city_id']) {
                $adress_area_id = $data['city_id'];
            }
            $data['address_area_id'] = $adress_area_id;
        }


        // 帮我买数据处理
        $buyData = array();
        if ($_POST['arrival_time']) {
            $buyData['arrival_time'] =  $_POST['arrival_time'];
        }
        if ($this->config['service_buy_deliver_times_switch'] == 1) {
            if ($_POST['time_info']) {
                $time = strtotime($_POST['time_info']);
                $arrival_time = ceil(($time - time()) / 60);
                if ($arrival_time > 0) {
                    $buyData['arrival_time'] =  $arrival_time;
                }
                $buyData['arrival_time_info'] =  $_POST['time_info'];
            } else {
                returnCodeError('请选择送达时间');
            }
        } elseif (intval($buyData['arrival_time'])>0) {
            $arrival_time_info = time() + intval($buyData['arrival_time']) * 60;
            if ($arrival_time_info) {
                $buyData['arrival_time_info'] = date('Y-m-d H:i:s', $arrival_time_info);
            }
        }
        // 如果有图片上传处理图片
        $img = '';
        if(!empty($_FILES)){
            $upload_file = PD('Image')->handle($this->_uid, 'service', 0, array('size' => 20), false);
            if ($upload_file['error'] == 1){
                returnCodeError(L_('上传失败！') . $upload_file['message'] . L_('请重试！'));
            }
            foreach ($_FILES as $key => $val) {
                if($val['imgFile']['error'] == 4){
                    returnCodeError('没有选择图片');
                }
                if($val['imgFile']['error'] == 3){;
                    returnCodeError('文件只有部分被上传');
                }
                if (is_array($upload_file['url'][$key])) {
                    $img_key = reset($upload_file['url'][$key]);
                } else {
                    $img_key = $upload_file['url'][$key];
                }
                if ($img && $img_key) {
                    $img .= ';'.$img_key;
                } else {
                    $img .= $img_key;
                }
            }
        }

        // 如果关闭了 服务商抢单 不论什么情况均走 平台配送
        if($this->config['service_provider_is_grab'] == 1) {
            $data['deliver_type'] = 1;
        } elseif (M('Area')->where(['area_id'=>$data['city_id']])->getField('deliver_model')) {
            // 开启了手动调度 不论是否存在平台配送员 均默认选择 平台配送
            $data['deliver_type'] = 1;
        } else {
            // 开启抢单模式情况 如果存在平台配送员 选择平台配送 没有 选择商家配送
            //获取平台配送员信息
            $deliver = PD('Deliver_user')->hasUser($data['address_lat'],$data['address_lng']);
            if(!empty($deliver)){
                $data['deliver_type'] = 1;
            }else{
                $data['deliver_type'] = 2;
            }
        }

        $_POST['is_service'] = true;
        $compute_money = $this->compute_money();
        unset($_POST['is_service']);
        $distance_info = $compute_money['distance_num'];
        $service_delivery_fee = $compute_money['basic_distance_price'];
        $distance_price = $compute_money['extra_distance_price'];
        $tip_price = $compute_money['tip_price'];
        $total_price = $compute_money['total_price'];
        //增值服务费用、名称、是否专送
        $added_fee = $compute_money['added_fee'] ?? 0;
        $added_fee_name = $compute_money['added_fee_name'] ?? '';
        $added_fee_list = $compute_money['added_fee_list'] ?? [];
        $special_delivery = isset($_POST['special_delivery']) ? intval($_POST['special_delivery']) : 0;

        $data['add_time'] = time();
        $data['cid'] = $_POST['cid'];
        $data['auto_goods_id'] = intval($_POST['id']);
        $data['uid'] = $this->_uid;
        $data['status'] = 1;
        $data['catgory_type'] = 2;
        $data['destance_sum'] = $distance_info;
        $nowtime = date("mdHis");
        $data['order_sn'] = $nowtime.rand(10,99).sprintf("%08d",$this->_uid);
        // 添加 跑腿和服务快派小程序下单区分
        if ($this->config['pay_wxapp_paotui_appid'] && $this->config['pay_wxapp_paotui_mchid']) {
            $data['service_type'] = 1;
        } else {
            $data['service_type'] = 0;
        }
        if (trim($_POST['weight_tip'])) {
            $data['weight_tip'] = trim($_POST['weight_tip']);
        } elseif($_POST['weight']) {
            $data['weight_tip'] = trim($_POST['weight']);
        } elseif($compute_money['weight']) {
            $data['weight_tip'] = trim($compute_money['weight']);
        }

        $data['time_discount_money'] = $discount_money;
        $data['time_discount_id'] = $discount_id;
        if ($coupon_id) {
            $data['system_coupon_id'] = $coupon_id;
            $data['system_coupon_price'] = $coupon_money;
        }
        $res = M('Service_user_publish')->data($data)->add();
        if($res){

            if ($coupon_id) {//使用优惠券
                $hadpullData = M('System_coupon_hadpull')->where(['coupon_id' => $coupon_id, 'uid' => $this->_uid, 'is_use' => 0])->find();
                if ($hadpullData) {
                    M('System_coupon_use_list')->data([
                        'order_type' => 'deliver',
                        'order_id' => $res,
                        'hadpull_id' => $hadpullData['id'],
                        'coupon_id' => $coupon_id,
                        'uid' => $this->_uid,
                        'num' => 1,
                        'use_time' => time()
                    ])->add();
                    if ($hadpullData['num'] == 1) {
                        M('System_coupon_hadpull')->where(['id' => $hadpullData['id']])->data(['is_use' => 1, 'use_time' => time()])->save();
                    }
                }
            }

            $buyData['publish_id'] =  $res;
            if ($_POST['goods_remarks']) {
                $buyData['goods_remarks'] = $this->filterEmoji2($_POST['goods_remarks']);
            }
            $buyData['buy_type'] =  $_POST['buy_type'];
            if (!empty($_POST['start_adress_detail'])) {
                $buyData['address'] =  trim($_POST['start_adress_detail']);
            }
            if (!empty($_POST['start_adress_lng'])) {
                $buyData['address_lng'] =  $_POST['start_adress_lng'];
            }
            if (!empty($_POST['start_adress_lat'])) {
                $buyData['address_lat'] =  $_POST['start_adress_lat'];
            }
            if(!empty($_POST['start_adress_name'])){
                $buyData['adress_nickname'] = $_POST['start_adress_name'];
            }
            if(!empty($_POST['start_adress_phone'])){
                $buyData['adress_phone'] = $_POST['start_adress_phone'];
            }

            // $buyData['adress_id'] =  $_POST['adress_id'];

            $buyData['end_adress_name'] =  trim($_POST['end_adress_detail']);
            $buyData['end_adress_lng'] =  $_POST['end_adress_lng'];
            $buyData['end_adress_lat'] =  $_POST['end_adress_lat'];
            $buyData['end_adress_nickname'] =  $_POST['end_adress_name'];
            $buyData['end_adress_phone'] =  $_POST['end_adress_phone'];



            $buyData['estimate_goods_price'] = $_POST['estimate_goods_price'];
            $buyData['basic_distance_price'] =  $service_delivery_fee;
            $buyData['distance_price'] =  $distance_price;
            $buyData['tip_price'] =  $tip_price;
            $buyData['total_price'] =  $total_price;
            $buyData['added_fee'] =  $added_fee;
            $buyData['added_fee_name'] =  $added_fee_name;
            $buyData['added_fee_list'] =  json_encode($added_fee_list,JSON_UNESCAPED_UNICODE);
            $buyData['special_delivery'] =  $special_delivery;
            $buyData['price_template_id'] = $compute_money['price_template_id'] ?? 0;
            if ($_POST['img']) {
                $buyData['img'] =  $_POST['img'];
            } elseif ($img) {
                $buyData['img'] =  $img;
            }
            // 把重量记录下
            if ($_POST['weight']) {
                $buyData['weight'] = $_POST['weight'];
            } elseif($compute_money['weight']) {
                $buyData['weight'] = $compute_money['weight'];
            }
            if ($compute_money['weight_price']) {
                $buyData['weight_price'] = $compute_money['weight_price'];
            }
            if ($compute_money['weight_info']) {
                $buyData['weight_info'] = $compute_money['weight_info'];
            }

            $buy_res = D('Service_user_publish_buy')->data($buyData)->add();
            if(!$buy_res){
                fdump( D('Service_user_publish_buy'),'Service_user_publish_buy_add_err');
                D('Service_user_publish')->where(array('publish_id'=>$res))->delete();
                returnCodeError(L_('数据添加异常请重试！'));
            }

            $pay_order_param = array(
                'business_type' => 'service',
                'business_id' => $res,
                'order_name' => L_('帮我买订单'),
                'uid' => $this->_uid,
                'total_money' => $total_price,
                'wx_cheap' => 0,
            );
            $plat_order_result = PD('Plat_order')->add_order($pay_order_param);
            if ($plat_order_result['error_code']) {
				fdump_sql($plat_order_result,'service_buy_plat_add_order_err');
                returnCodeError($plat_order_result['error_msg']);
            } else {
                $returnArr = array(
                    'msg' => '需求已发布,请去支付!',
                    'order_id' => $plat_order_result['order_id'],
                    'type' => 'plat'
                );
                returnCodeOk($returnArr);
            }
        }else{
            returnCodeError(L_('发布失败请重试!'));
        }
    }


    /**
     * 过滤emoji表情
     * @param string   $str 字符串
     * @return string
     */
    function filterEmoji2($str){
        if(empty($str)) return null;
        $str = preg_replace_callback(
            '/[\xf0-\xf7].{3}/',
            function($r){
                return '@E' . base64_encode($r[0]);
            },$str);
        $countt=substr_count($str,"@");
        for ($i=0; $i < $countt; $i++) {
            $c = stripos($str,"@");
            $str=substr($str,0,$c).substr($str,$c+10,strlen($str)-1);
        }
        $str = preg_replace_callback(
            '/@E(.{6}==)/',
            function($r){
                return base64_decode($r[1]);
            }, $str);
        return $str;
    }


    /**处理平台配送*/
    /**送达地址上传过来获取对应配置*/
    public function publish_config(){
        $lng = $_POST['lng'];
        $lat = $_POST['lat'];
        $type = $_POST['type'];
        $address_id = $_POST['address_id'];
        $time_info = array();
        $data = array();
        $appid = I('appid', '', 'strval');
        $app_key = I('app_key', '', 'strval');
        $now_city = I('now_city');//城市编号
        $start_city = I('start_city');//发货地城市编号
        if (empty($start_city)) {
            $start_city = !empty($now_city) ? $now_city : 0;
        }
        if ($appid && $app_key) {
            // sign 校验信息
            $type = 3; // 只查询帮我送
            $p_sign = $_POST['sign'];
            $sign_arr = $_POST;
            unset($sign_arr['sign']);
            unset($sign_arr['app_key']);
            unset($sign_arr['now_lang']);
            $sign = $this->get_encrypt_key($sign_arr, $app_key);
            if ($sign != $p_sign) {
                returnCodeError('数据校验失败');
            }
            $merchant = M('Merchant')->where(array('appid' => $appid, 'key' => $app_key))->find();
            if (empty($merchant)) {
                returnCodeError('平台配置信息错误');
            }
        }

        if ($lng > 0 && $lat > 0) {
            $area_info = PD('Area')->cityMatching($lat, $lng);
            if ($area_info['area_info']['province_id']) {
                $data['province_id'] = $area_info['area_info']['province_id'];
            }
            if ($area_info['area_info']['city_id'] && $area_info['area_info']['city_id'] > $data['province_id']) {
                $data['city_id'] = $area_info['area_info']['city_id'];
            }
            if ($area_info['area_info']['area_id'] && $area_info['area_info']['area_id'] > 0) {
                $data['area_id'] = $area_info['area_info']['area_id'];
            }
        } elseif ($address_id) {
            $adress_info = plugin_model("user_adress")->get_adress($this->_uid,$address_id);
            $data['province_id'] = $adress_info['province'];
            $data['city_id'] = $adress_info['city'];
            $data['area_id'] = $adress_info['area'];
        } else {
            returnCodeError('请上传地址');
        }

        // 获得帮我买商品信息（ 用于查看商家是否单独设置起送费 ）
        $id = I('id');

        if ($type == 2 && $this->config['service_buy_deliver_times_switch'] != 1) {
            $publish_config =  array();
            if ($data) {
                $publish_config = PD('Deliver_service_set')->address_publish_config($data);
            }
            if ($publish_config && $publish_config['buy_status'] == 1) {
                $config_time['service_basic_distance'] = $publish_config['buy_base_distance'];
                $config_time['service_delivery_fee'] = $publish_config['buy_base_fee'];
                $config_time['service_per_km_price'] = $publish_config['buy_per_km_price'];
            } else {
                // 如果配置全空且不为0，仍然取之前的配置一中的配置
                $service_give_basic_distance = $this->config['service_give_basic_distance'];
                $service_give_delivery_fee = $this->config['service_give_delivery_fee'];
                $service_give_per_km_price = $this->config['service_give_per_km_price'];
                $config_time = array();
                $service_give_basic_distance_if =  (!$service_give_basic_distance && $service_give_basic_distance !== 0 && $service_give_basic_distance !== '0');
                $service_give_delivery_fee_if =  (!$service_give_delivery_fee && $service_give_delivery_fee !== 0 && $service_give_delivery_fee !== '0');
                $service_give_per_km_price_if =  (!$service_give_per_km_price && $service_give_per_km_price !== 0 && $service_give_per_km_price !== '0');
                if ($service_give_basic_distance_if && $service_give_delivery_fee_if && $service_give_per_km_price_if) {
                    $config_time['service_basic_distance'] = $this->config['service_basic_distance'];
                    $config_time['service_delivery_fee'] = $this->config['service_delivery_fee'];
                    $config_time['service_per_km_price'] = $this->config['service_per_km_price'];
                } else {
                    $config_time['service_basic_distance'] = $this->config['service_give_basic_distance'] ? $this->config['service_give_basic_distance'] : 0;
                    $config_time['service_delivery_fee'] = $this->config['service_give_delivery_fee'] ? $this->config['service_give_delivery_fee'] : 0;
                    $config_time['service_per_km_price'] = $this->config['service_give_per_km_price'] ? $this->config['service_give_per_km_price'] : 0;
                }
            }


            // 帮我买商家单独设置起送费
            if ($id) {
                $config_time['service_delivery_fee'] = PD('Deliver_service_set')->getStoreDeliveryFee($id, $config_time['service_delivery_fee']);
            }
            $time_info['config_time'] = $config_time;
            $time_info['daysList'] = array();
            $time_info['timeList'] = array();
        } else {
            // 获取对应时间信息

            $time_info = PD('Deliver_service_set')->publish_time_info($type, $data, false, $id);
        }

        //配送优惠券
        $discountList = [];
        if (!empty($start_city) && !empty($time_info['timeList'])) {
            $bindCityIds = M('Service_time_discount_bind_area')->where(['city_id' => $start_city])->field('discount_id')->select();
            $bindCityIds = array_column($bindCityIds, 'discount_id');
            $discountList = M('Service_time_discount')->where(['discount_id' => ['in', $bindCityIds], 'is_del' => 0])->select();
            if ($discountList) {
                foreach ($discountList as $disk => &$disv) {
                    $orderCount = M('Service_time_discount')->where(['address_area_id' => $start_city, 'time_discount_id' => $disv['id']])->count();
                    if ($orderCount >= $disv['number']) {//优惠次数已用完
                        unset($discountList[$disk]);
                        continue;
                    }
                    $start_time = explode(':', $disv['start_time']);
                    $disv['stime'] = $start_time[0] * 3600 + $start_time[1] * 60 + $start_time[2];
                    $end_time = explode(':', $disv['end_time']);
                    $disv['etime'] = $end_time[0] * 3600 + $end_time[1] * 60 + $end_time[2];
                }
                $discountList = array_values($discountList);
            }
        }

        //开启了运费模板
        if (C('config.open_paotui_price_template') && in_array($type, [2, 3])) {
            try {
                $from = $_POST['from'] ?? '';
                $specialDelivery = intval($_POST['special_delivery']);
                if ($type == 2) {
                    //帮我买
                    $business = 1;
                } else if ($type == 3) {
                    //帮我送
                    $business = $from == 'merchant' ? 3 : 2;
                }
                $result = D('Paotui_price_template')->calFee($business, 1, 1, $specialDelivery,$_POST['mer_id']);
                $baseFee = $result['basic_distance_price'];
            } catch (\Exception $e) {
                returnCodeError($e->getMessage());
            }
        }

        foreach ($time_info['timeList'] as &$dv) {
            if (!empty($dv)) {
                foreach ($dv as &$tv) {
                    $tv['discount_money'] = "0";
                    $tv['discount_id'] = "0";
                    if(isset($baseFee)){
                        $tv['delivery_fee'] = $baseFee;
                    }
                    if ($discountList) {
                        $daytime = explode(':', $tv['time']);
                        $daytime = $daytime[0] * 3600 + $daytime[1] * 60;
                        foreach ($discountList as $disv1) {
                            if ($daytime >= $disv1['stime'] && $daytime <= $disv['etime']) {
                                $tv['discount_money'] = $disv1['money'];
                                $tv['discount_id'] = $disv1['discount_id'];
                            }
                        }
                    }
                }
            }
        }

        returnCodeOk($time_info);
    }

    /**获取对应订单信息*/
    public function service_info() {
        $appid = I('appid', '', 'strval');
        $app_key = I('app_key', '', 'strval');
        // sign 校验信息
        $p_sign = $_POST['sign'];
        $sign_arr = $_POST;
        unset($sign_arr['sign']);
        unset($sign_arr['app_key']);
        unset($sign_arr['now_lang']);
        $sign = $this->get_encrypt_key($sign_arr, $app_key);
        if ($sign != $p_sign) {
            returnCodeError('数据校验失败');
        }
        $merchant = M('Merchant')->where(array('appid' => $appid, 'key' => $app_key))->find();
        if (empty($merchant)) {
            returnCodeError('平台配置信息错误');
        }
        if (!$_POST['publish_id'] && $_POST['publish_id']<=0 && !$_POST['third_id'] && $_POST['third_id']<=0) {
            returnCodeError('请传值订单');
        }
        $where = array();
        if ($_POST['publish_id']>0) {
            $where['publish_id'] = $_POST['publish_id'];
        }
        if ($_POST['third_id']>0) {
            $where['third_id'] = $_POST['third_id'];
        }
        $publish_detail = M('Service_user_publish')->where($where)->find();
        $service_user_publish_give = M('Service_user_publish_give')->where(array('publish_id' => $publish_detail['publish_id']))->find();
        $service_user_publish_give['status'] = $publish_detail['status'];
        $log_list = M('Service_user_publish_log')->where(array('publish_id' => $publish_detail['publish_id']))->order('add_time DESC')->select();
        if ($log_list) {
            $service_user_publish_give['log'] = $log_list;
        } else {
            $service_user_publish_give['log'] = array();
        }
        returnCodeOk($service_user_publish_give);
    }


    // 前端上传对应数值，进行费用计算   距离+时间=配送费，小费， 重量费用
    public function compute_money() {
        fdump_api($_REQUEST,'paotui/compute_money',true);
        $cid = I('cid'); // 上传分类id
        $id = I('id'); // 商品id
        $buy_type = I('buy_type'); // 上传分类id
        $tip_price = $_POST['tip_price'] ? $_POST['tip_price'] : 0;
        // 重量费用
        $weight = $_POST['weight'] ? $_POST['weight'] : 0;
        $discount_money = I('discount_money', 0, 'intval');// 配送优惠金额
        $coupon_id = I('coupon_id', 0, 'intval');// 优惠券ID
        // 获取分类信息
        $database_Service_category = D('Service_category');
        $now_category = $database_Service_category->field(true)->where(array('cid'=>$cid))->find();
        if (empty($now_category)) {
            returnCode('50000002');
        }

        $msg = array();
        // 判断获取 重量档次信息
        $weight_price = 0;
        $destance_sum = 0;
        $distance_price = 0;
        $total_price = 0;
        $basic_distance_price = 0;
        $basic_weight_price = 0;
        $weight_info = '';
        if($this->config['service_weight_multilevel_switch'] == 1 && ($weight || $_POST['weight_price']>0)) {
            if (2==$now_category['type']) {
                $service_weight_info = $this->config['service_buy_weight_info'];
            } else {
                $service_weight_info = $this->config['service_give_weight_info'];
            }
            $service_weight_info = unserialize($service_weight_info);
            if (floatval($_POST['start_weight']) >=0 && floatval($_POST['end_weight'])>0) {
                $weight_price = round_number($_POST['weight_price'], 2);
                if ($service_weight_info) {
                    // start_weight 范围起始公斤数 end_weight 范围结束公斤数 范围价格 weight_price
                    foreach ($service_weight_info as $key => &$val) {
                        if (floatval($val['start_weight'])==floatval($_POST['start_weight']) && floatval($val['end_weight'])==floatval($_POST['end_weight'])) {
                            $val['weight_price'] = round_number($val['weight_price'], 2);
                            $weight_info = serialize($val);
                            $weight = $val['start_weight'] . 'kg-' . $val['end_weight'] . 'kg';
                            if (floatval($weight_price)<=0) {
                                $weight_price = $val['weight_price'];
                            }
                        } elseif (floatval($val['start_weight'])<floatval($weight) && floatval($val['end_weight'])>=floatval($weight)) {
                            $val['weight_price'] = round_number($val['weight_price'], 2);
                            $weight_info = serialize($val);
                            $weight_price = $val['weight_price'];
                            $weight = $val['start_weight'] . 'kg-' . $val['end_weight'] . 'kg';
                        }
                    }
                    if (!$weight) {
                        returnCodeError(L_('物品重量信息有误！请刷新重试！'));
                    }
                }
            } elseif ($weight>0) {
                if ($service_weight_info) {
                    // start_weight 范围起始公斤数 end_weight 范围结束公斤数 范围价格 weight_price
                    $is_weight_price = false;
                    foreach ($service_weight_info as $key => &$val) {
                        if (floatval($val['start_weight'])<=floatval($weight) && floatval($val['end_weight'])>=floatval($weight)) {
                            $val['weight_price'] = round_number($val['weight_price'], 2);
                            $weight_price = $val['weight_price'];
                            $weight = $val['start_weight'] . 'kg-' . $val['end_weight'] . 'kg';
                            $is_weight_price = true;
                        }
                    }
                    if (!$is_weight_price) {
                        returnCodeError(L_('该重量档次未设置，请联系管理员设置!'));
                    }
                    if (!$weight) {
                        returnCodeError(L_('物品重量信息有误！请刷新重试！'));
                    }
                }
            } else {
                returnCodeError(L_('请选择物品重量！'));
            }
        } elseif($this->config['service_weight_multilevel_switch'] != 1 && $weight > 0) {
            $basic_weight = $this->config['service_basic_weight'];//基础重量
            $basic_weight_price = $this->config['service_basic_weight_price'];// 基础价格
            $bounds_weight = $this->config['service_bounds_weight'];// 超出配送费
            if($weight <= $basic_weight){
                $weight_price = $basic_weight_price;
            }else{
                $weight_price =  (($weight - $basic_weight)*$bounds_weight)+$basic_weight_price;
            }
        }
        if ($_POST['end_adress_lat'] && $_POST['end_adress_lng']) {
            // 获取收货地址信息
            $publish_config = PD('Deliver_service_set')->location_publish_config($_POST['end_adress_lat'], $_POST['end_adress_lng']);
        } else {
            if($_POST['is_voice']){// 语音发单没有收货地址，改成使用发货地址区域计算费用          
                $publish_config = PD('Deliver_service_set')->location_publish_config($_POST['start_adress_lat'], $_POST['start_adress_lng']);
            }else{
                $publish_config = array();
            }
        }

        if ($now_category['type'] == 2){
            // 帮我买
            if ($buy_type == 2) {
                if ($_POST['start_adress_lat'] && $_POST['start_adress_lng'] && $_POST['end_adress_lat'] && $_POST['end_adress_lng']) {
                    // 指定地址购买
                    $distance = array(
                        'start_lat' => $_POST['start_adress_lat'],
                        'start_lng' => $_POST['start_adress_lng'],
                        'end_lat' =>   $_POST['end_adress_lat'],
                        'end_lng' =>   $_POST['end_adress_lng'],
                    );
                    $destance_sum = $this->distance_calculation($distance);
                }
            }
            if ($this->config['service_buy_deliver_times_switch'] == 1 && !empty($_POST['time_info'])) { // 如果帮我买 开启了 帮我买多分段送达时间设置
                if (stripos(trim($_POST['time_info']),'(') || stripos(trim($_POST['time_info']),')')) {
                    $time_info_arr = explode('(', trim($_POST['time_info']));
                    $_POST['time_info'] = $time_info_arr[0];
                }
                $time_info = explode(' ', trim($_POST['time_info']));
                if ($publish_config && $publish_config['buy_status'] == 1) {
                    // 配送时间段一
                    $delivery_times = explode('-', $publish_config['buy_deliver_time1']);
                    // 配送时间段二
                    $delivery_times2 = explode('-', $publish_config['buy_deliver_time2']);
                } else {
                    // 配送时间段一
                    $delivery_times = explode('-', $this->config['service_buy_delivery_time']);
                    // 配送时间段二
                    $delivery_times2 = explode('-', $this->config['service_buy_delivery_time2']);
                }

                $pin_time = date("Y-m-d");
                $pin_next_time = date('Y-m-d', strtotime('+1 day'));
                $time_info_tip = $time_info[1];
                $time_info_tip = $pin_time . ' ' . $time_info_tip;

                if ($delivery_times[0] && $delivery_times[1]) {
                    $delivery_times_start = $pin_time . ' ' . $delivery_times[0];
                    $delivery_times_end = $pin_time . ' ' . $delivery_times[1];
                } else {
                    $delivery_times_start = 0;
                    $delivery_times_end = 0;
                }
                if ($delivery_times2[0] && $delivery_times2[1]) {
                    $delivery_times2_start = $pin_time . ' ' . $delivery_times2[0];
                    $delivery_times2_end = $pin_time . ' ' . $delivery_times2[1];
                } else {
                    $delivery_times2_start = 0;
                    $delivery_times2_end = 0;
                }
                $time_info_tip = strtotime($time_info_tip);
                if ($delivery_times_start && $delivery_times_end) {
                    $delivery_times_start = strtotime($delivery_times_start);
                    $delivery_times_end = strtotime($delivery_times_end);
                    if ($delivery_times_end <= $delivery_times_start) {
                        $delivery_times_end = $pin_next_time . ' ' . $delivery_times[1];
                        $delivery_times_end = strtotime($delivery_times_end);
                    }
                }
                if ($delivery_times2_start && $delivery_times2_end) {
                    $delivery_times2_start = strtotime($delivery_times2_start);
                    $delivery_times2_end = strtotime($delivery_times2_end);
                    if ($delivery_times2_end <= $delivery_times2_start) {
                        $delivery_times2_end = $pin_next_time . ' ' . $delivery_times[1];
                        $delivery_times2_end = strtotime($delivery_times2_end);
                    }
                }

                if ($time_info_tip >= $delivery_times_start && $time_info_tip <= $delivery_times_end) {

                    if ($publish_config && $publish_config['buy_status'] == 1) {
                        // 配送时间段一 起步距离
                        $service_basic_distance = $publish_config['buy_base_distance1'];
                        // 配送时间段一 起步配送费
                        $service_delivery_fee = $publish_config['buy_base_fee1'];
                        // 配送时间段一 每公里的配送费
                        $service_per_km_price = $publish_config['buy_per_km_price1'];
                    } else {
                        // 配送时间段一 起步距离
                        $service_basic_distance = $this->config['service_buy_basic_distance'];
                        // 配送时间段一 起步配送费
                        $service_delivery_fee = $this->config['service_buy_delivery_fee'];
                        // 配送时间段一 每公里的配送费
                        $service_per_km_price = $this->config['service_buy_per_km_price'];
                    }


                    if ($destance_sum && $destance_sum > $service_basic_distance) {
                        $distance_price = ($destance_sum - $service_basic_distance) * $service_per_km_price;
                        $distance_price = $this->publish_count_method($distance_price);
                    } else {
                        $distance_price = 0;
                    }

                    // 帮我买商家单独设置起送费
                    if ($id) {
                        $service_delivery_fee = PD('Deliver_service_set')->getStoreDeliveryFee($id, $service_delivery_fee);
                    }
                    $basic_distance_price = $service_delivery_fee;
                    $tip_price = $_POST['tip_price'] ? $_POST['tip_price'] : 0;
                    $total = $distance_price + $basic_distance_price + $weight_price + $tip_price;
                    $total_price = round_number($total, 2);

                    if ($total_price < $service_delivery_fee) {
                        returnCodeError(L_('费用计算错误，请重试！'));
                    }
                } elseif ($time_info_tip >= $delivery_times2_start && $time_info_tip <= $delivery_times2_end) {
                    if ($publish_config && $publish_config['buy_status'] == 1) {
                        // 配送时间段二 起步距离
                        $service_basic_distance2 = $publish_config['buy_base_distance2'];
                        // 配送时间段二 起步配送费
                        $service_delivery_fee2 = $publish_config['buy_base_fee2'];
                        // 配送时间段二 每公里的配送费
                        $service_per_km_price2 = $publish_config['buy_per_km_price2'];
                    } else {
                        // 配送时间段二 起步距离
                        $service_basic_distance2 = $this->config['service_buy_basic_distance2'];
                        // 配送时间段二 起步配送费
                        $service_delivery_fee2 = $this->config['service_buy_delivery_fee2'];
                        // 配送时间段二 每公里的配送费
                        $service_per_km_price2 = $this->config['service_buy_per_km_price2'];
                    }

                    if ($destance_sum && $destance_sum > $service_basic_distance2) {
                        $distance_price = ($destance_sum - $service_basic_distance2) * $service_per_km_price2;
                        $distance_price = $this->publish_count_method($distance_price);
                    } else {
                        $distance_price = 0;
                    }

                    // 帮我买商家单独设置起送费
                    if ($id) {
                        $service_delivery_fee2 = PD('Deliver_service_set')->getStoreDeliveryFee($id, $service_delivery_fee2);
                    }
                    $basic_distance_price = $service_delivery_fee2;
                    $total = $distance_price + $basic_distance_price + $weight_price + $tip_price;
                    $total_price = round_number($total, 2);

                    if ($total_price < $service_delivery_fee2) {
                        returnCodeError(L_('费用计算错误，请重试！'));
                    }
                } else {
                    returnCode(3007,array(),L_('所选时间配送员已经休息！'));
                    returnCodeError(L_('所选时间配送员已经休息！'));
                }
            } elseif($this->config['service_buy_deliver_times_switch'] != 1) {
                if ($publish_config && $publish_config['buy_status'] == 1) {
                    //  基础起步距离
                    $service_basic_distance = $publish_config['buy_per_km_price'];
                    // 基础起步配送费
                    $service_delivery_fee = $publish_config['buy_base_fee'];
                    // 基础每公里的配送费
                    $service_per_km_price = $publish_config['buy_base_distance'];
                } else {
                    //  基础起步距离
                    $service_basic_distance = $this->config['service_give_basic_distance'];
                    // 基础起步配送费
                    $service_delivery_fee = $this->config['service_give_delivery_fee'];
                    // 基础每公里的配送费
                    $service_per_km_price = $this->config['service_give_per_km_price'];
                }

                if ($destance_sum && $destance_sum > $service_basic_distance) {
                    $distance_price = ($destance_sum - $service_basic_distance) * $service_per_km_price;
                    $distance_price = $this->publish_count_method($distance_price);
                } else {
                    $distance_price = 0;
                }
                $basic_distance_price = $service_delivery_fee;

                // 帮我买商家单独设置起送费
                if ($id) {
                    $basic_distance_price = PD('Deliver_service_set')->getStoreDeliveryFee($id, $basic_distance_price);
                }

                $total = $distance_price + $basic_distance_price + $weight_price + $tip_price;
                $total_price = round_number($total, 2);
                if ($total_price < $service_delivery_fee) {
                    returnCodeError(L_('费用计算错误，请重试！'));
                }
            } else {
                returnCodeError(L_('请上传送达时间！'));
            }
        }
        elseif ($now_category['type'] == 3) {
            if ($_POST['start_adress_lat'] && $_POST['start_adress_lng'] && $_POST['end_adress_lat'] && $_POST['end_adress_lng']) {
                $distance = array(
                    'start_lat' => $_POST['start_adress_lat'],
                    'start_lng' => $_POST['start_adress_lng'],
                    'end_lat' =>   $_POST['end_adress_lat'],
                    'end_lng' =>   $_POST['end_adress_lng'],
                );
                $destance_sum = $this->distance_calculation($distance);
            }
            // 如果传入了时间值
            if ($_POST['time_info']) {
                if (stripos(trim($_POST['time_info']),'(') || stripos(trim($_POST['time_info']),')')) {
                    $time_info_arr = explode('(', trim($_POST['time_info']));
                    $_POST['time_info'] = $time_info_arr[0];
                }
                // 获取收货地址信息
                $time_info = explode(' ', trim($_POST['time_info']));
                // 帮我买 获取选择送达时间的各个配置
                if ($publish_config && $publish_config['give_status'] == 1) {
                    // 配送时间段一
                    $delivery_times = explode('-', $publish_config['give_deliver_time1']);
                    // 配送时间段二
                    $delivery_times2 = explode('-', $publish_config['give_deliver_time2']);
                } else {
                    // 配送时间段一
                    $delivery_times = explode('-', $this->config['service_delivery_time']);
                    // 配送时间段二
                    $delivery_times2 = explode('-', $this->config['service_delivery_time2']);
                }
                $pin_time = date("Y-m-d");
                $pin_next_time = date('Y-m-d', strtotime('+1 day'));
                $time_info_tip = $time_info[1];
                $time_info_tip = $pin_time . ' ' . $time_info_tip;


                if ($delivery_times[0] && $delivery_times[1]) {
                    $delivery_times_start = $pin_time . ' ' . $delivery_times[0];
                    $delivery_times_end = $pin_time . ' ' . $delivery_times[1];
                } else {
                    $delivery_times_start = 0;
                    $delivery_times_end = 0;
                }
                if ($delivery_times2[0] && $delivery_times2[1]) {
                    $delivery_times2_start = $pin_time . ' ' . $delivery_times2[0];
                    $delivery_times2_end = $pin_time . ' ' . $delivery_times2[1];
                } else {
                    $delivery_times2_start = 0;
                    $delivery_times2_end = 0;
                }

                $time_info_tip = strtotime($time_info_tip);

                if ($delivery_times_start && $delivery_times_end) {
                    $delivery_times_start = strtotime($delivery_times_start);
                    $delivery_times_end = strtotime($delivery_times_end);
                    if ($delivery_times_end <= $delivery_times_start) {
                        $delivery_times_end = $pin_next_time . ' ' . $delivery_times[1];
                        $delivery_times_end = strtotime($delivery_times_end);
                    }
                }
                if ($delivery_times2_start && $delivery_times2_end) {
                    $delivery_times2_start = strtotime($delivery_times2_start);
                    $delivery_times2_end = strtotime($delivery_times2_end);
                    if ($delivery_times2_end <= $delivery_times2_start) {
                        $delivery_times2_end = $pin_next_time . ' ' . $delivery_times[1];
                        $delivery_times2_end = strtotime($delivery_times2_end);
                    }
                }

                if ($time_info_tip >= $delivery_times_start && $time_info_tip <= $delivery_times_end) {
                    if ($publish_config && $publish_config['give_status'] == 1) {
                        // 配送时间段一 起步距离
                        $service_basic_distance = $publish_config['give_base_distance1'];
                        // 配送时间段一 起步配送费
                        $service_delivery_fee = $publish_config['give_base_fee1'];
                        // 配送时间段一 每公里的配送费
                        $service_per_km_price = $publish_config['give_per_km_price1'];
                    } else {
                        // 配送时间段一 起步距离
                        $service_basic_distance = $this->config['service_basic_distance'];
                        // 配送时间段一 起步配送费
                        $service_delivery_fee = $this->config['service_delivery_fee'];
                        // 配送时间段一 每公里的配送费
                        $service_per_km_price = $this->config['service_per_km_price'];
                    }
                    if ($destance_sum && $destance_sum > $service_basic_distance) {
                        $distance_price = ($destance_sum - $service_basic_distance) * $service_per_km_price;
                        $distance_price = $this->publish_count_method($distance_price);
                    } else {
                        $distance_price = 0;
                    }

                    $basic_distance_price = $service_delivery_fee;
                    $total = $distance_price + $basic_distance_price + $weight_price + $tip_price;
                    $total_price = round_number($total, 2);
                    $service_delivery_fee = round_number($service_delivery_fee, 2);
                    
                    if ($total_price < $service_delivery_fee) {
                        returnCode(3006,array(),L_('费用计算错误，请重试！'));
                    }
                } elseif ($time_info_tip >= $delivery_times2_start && $time_info_tip <= $delivery_times2_end) {

                    if ($publish_config && $publish_config['give_status'] == 1) {
                        // 配送时间段二 起步距离
                        $service_basic_distance2 = $publish_config['give_base_distance2'];
                        // 配送时间段二 起步配送费
                        $service_delivery_fee2 = $publish_config['give_base_fee2'];
                        // 配送时间段二 每公里的配送费
                        $service_per_km_price2 = $publish_config['give_per_km_price2'];
                    } else {
                        // 配送时间段二 起步距离
                        $service_basic_distance2 = $this->config['service_basic_distance2'];
                        // 配送时间段二 起步配送费
                        $service_delivery_fee2 = $this->config['service_delivery_fee2'];
                        // 配送时间段二 每公里的配送费
                        $service_per_km_price2 = $this->config['service_per_km_price2'];
                    }
                    if ($destance_sum && $destance_sum > $service_basic_distance2) {
                        $distance_price = ($destance_sum - $service_basic_distance2) * $service_per_km_price2;
                        $distance_price = $this->publish_count_method($distance_price);
                    } else {
                        $distance_price = 0;
                    }
                    $basic_distance_price = $service_delivery_fee2;
                    $total = $distance_price + $basic_distance_price + $weight_price + $tip_price;
                    $total_price = round_number($total, 2);

                    if ($total_price < $service_delivery_fee2) {
                        returnCode(3006,array(),L_('费用计算错误，请重试！'));
                    }
                } else {
                    returnCode(3007,array(),L_('所选时间配送员已经休息！'));
                }
            } elseif (!empty($_POST['fetch_time']) && !empty($_POST['per_km_price']) && !empty($_POST['basic_distance'])) {
                $per_km_price = floatval($_POST['per_km_price']);
                $basic_distance = floatval($_POST['basic_distance']);
                if ($destance_sum < $basic_distance) {
                    $distance_price = 0;
                } else {
                    $distance_price = ($destance_sum - $basic_distance) * $per_km_price;
                }
                $distance_price = $this->publish_count_method($distance_price);
                $_POST['distance_price'] = $distance_price;
                // 计算总费用
                $basic_distance_price = floatval($_POST['basic_distance_price']);
                $total = $weight_price + $distance_price + $basic_distance_price + $tip_price;
                $total_price = round_number($total, 2);
                if ($total_price < $basic_distance_price) {
                    returnCodeError(L_('费用计算错误，请重试！'));
                }
            }
            if(C('config.mall_third_deliver_open')){
                if ($_POST['start_adress_lat'] && $_POST['start_adress_lng'] && $_POST['end_adress_lat'] && $_POST['end_adress_lng']) {
                    $distance_params = array(
                        'store_lat' => $_POST['start_adress_lat'],
                        'store_lng' => $_POST['start_adress_lng'],
                        'store_name' => '',
                        'store_address' => '',
                        'user_lat' => $_POST['end_adress_lat'],
                        'user_lng' => $_POST['end_adress_lng'],
                        'user_address' => '',
                        'user_deliver_time' => $_POST['time_info'].':00'
                    );
                    $distance_fee = invoke_v20_service('\app\mall\model\service\third_order_deliver\DeliverApiService/countPrice',['params'=>$distance_params]);
                    if($distance_fee['status']!=1000){
                        returnCodeError(L_($distance_fee['msg']));
                    }
                    if($distance_fee['data']['error_no']!=0){
                        returnCodeError(L_($distance_fee['data']['error_msg']));
                    }
                    $third_delivery_fee = $distance_fee['data']['retval'];
                    $service_delivery_fee = 0;
                    $service_delivery_fee2 = 0;
                    $distance_price = 0;
                    $basic_distance_price = 0;
                    $weight_price = 0;
                    $total = $third_delivery_fee + $distance_price + $basic_distance_price + $weight_price + $tip_price;
                    $total_price = round_number($total, 2);
                }else{
                    returnCodeError(L_('发货/送达地址经纬度信息错误！'));
                }
            }
        }

        if (C('config.open_paotui_price_template') && in_array($now_category['type'], [2, 3])) {
            try {
                $from = $_POST['from'] ?? '';
                $specialDelivery = intval($_POST['special_delivery']);
                if ($now_category['type'] == 2) {
                    //帮我买
                    $business = 1;
                } else if ($now_category['type'] == 3) {
                    //帮我送
                    $business = $from == 'merchant' ? 3 : 2;
                }
                $result = D('Paotui_price_template')->calFee($business, $destance_sum * 1000, $_POST['weight'], $specialDelivery,$_POST['mer_id']);
                $basic_distance_price = $result['basic_distance_price'];
                $distance_price = $result['extra_distance_price'];
                $basic_weight_price = $result['basic_weight_price'];
                $weight_price = $result['basic_weight_price'] + $result['extra_weight_price'];
                $total = $distance_price + $basic_distance_price + $weight_price + $tip_price + $result['added_fee'];
                $total_price = round_number($total, 2);
                $msg['added_fee_name'] = $result['added_fee_name'];
                $msg['added_fee'] = $result['added_fee'];
                $msg['added_fee_list'] = $result['added_fee_list'];
                $msg['price_template_id'] = $result['price_template_id'];
            } catch (\Exception $e) {
                returnCodeError($e->getMessage());
            }
        } else {
            $msg['added_fee_name'] = '';
            $msg['added_fee'] = 0;
            $msg['added_fee_list'] = [];
        }

        $msg['old_price'] = floatval($total_price);
        $msg['coupon_money'] = 0;
        if ($coupon_id) {
            $coupon_data = M('System_coupon')->where(['coupon_id' => $coupon_id, 'cate_name' => 'deliver'])->find();
            if ($coupon_data['order_money'] > $msg['old_price']) {
                returnCodeError(L_('订单金额必须大于等于'.$coupon_data['order_money'].'元'));
            }
            if ($coupon_data['is_discount'] == 1) {
                $msg['coupon_money'] = number_format($msg['old_price'] * (1 - $coupon_data['discount_value'] / 10), 2 , '.', '');
            } else {
                $msg['coupon_money'] = $coupon_data['discount'];
            }
        }
        $msg['coupon_title'] = $msg['coupon_money'] == 0 ? '' : '￥' . $msg['old_price'] . '(-￥' . $msg['coupon_money'] . '元)';
        $msg['total_price'] = floatval($total_price - $msg['coupon_money']);
        $msg['distance_num'] = round_number($destance_sum,3);//总距离
        $msg['tip_price'] = $tip_price;
        $msg['discount_money'] = $discount_money > 0 ? $discount_money : 0;
        $msg['discount_title'] = '立减' . $msg['discount_money'] . '元';
        $msg['total_price'] = floatval($msg['total_price'] - $msg['discount_money']);
        $msg['total_price'] = $msg['total_price'] > 0 ? $msg['total_price'] : 0;
        $msg['basic_distance_price'] = $basic_distance_price;
        $msg['weight'] = $weight;//总重量
        $msg['weight_info'] = $weight_info;
        $msg['basic_weight_price'] = $basic_weight_price;
        $msg['weight_price'] = $weight_price;
        $msg['extra_distance_price'] = $distance_price;
        $msg['distance_price'] = $distance_price + $basic_distance_price;
        $msg['extra_weight_price'] = round_number($weight_price - $basic_weight_price, 2);

        /*造的数据*/
        if($service_basic_distance>0){
            $msg['distance_basic'] = $service_basic_distance;//基础距离
        }elseif (empty($service_basic_distance) || $service_basic_distance==0){
            if($service_basic_distance2>0){
                $msg['distance_basic'] = $service_basic_distance2;
            }
        }else{
            $msg['distance_basic'] = 0;
        }

        $msg['distance_add'] = $msg['extra_distance_price'];//距离附加
        $msg['url'] = $this->config['site_url']."/plat/pages/runErrands/billingRules";//计费规则地址
        $weight_basic=$this->config['basic_buy_weight'];
        if($weight_basic>=$_POST['weight']){
            $msg['over_weight'] = 0;//重量附加
        }else{
            $msg['over_weight'] = $_POST['weight']-$weight_basic;//重量附加
        }
        $msg['weight_real'] =$_POST['weight'];
        $msg['weight_base'] = $weight_basic;//基础重量
        if ($_POST['is_service']) {
            return $msg;
        } else {
            returnCode('0',$msg);
        }
    }


    // 语音发单获取语音
    public function save_voice_order() {      
        // $this->_uid = 1358679;        
        $cid = I('cid'); // 上传分类id

        // 重量费用
        $_POST['weight'] = $_POST['weight'] ?? 0;


        if($_POST['publish_id']){// 尾款支付
            $_POST['is_final_pay'] = '1';
            $_POST['is_voice'] = '1';
            $order = M('Service_user_publish')->where(['publish_id'=>$_POST['publish_id']])->find();

            $giveOrderInfo = D("Service_user_publish_give")->where(['publish_id' => $_POST['publish_id']])->find();
            $this->_uid = $order['uid'];
            $_POST['time_info'] = $giveOrderInfo['arrival_time_info'];
            $_POST['cid'] = $giveOrderInfo['cid'];
            $_POST['start_adress_lat'] = $giveOrderInfo['start_adress_lat'];
            $_POST['start_adress_lng'] = $giveOrderInfo['start_adress_lng'];
            $_POST['start_adress_area_id'] = $giveOrderInfo['start_adress_id'];
            $_POST['start_adress_phone'] = $giveOrderInfo['start_adress_phone'];
            $_POST['start_adress_detail'] = $giveOrderInfo['start_adress_name'];
            $_POST['start_adress_name'] = $giveOrderInfo['start_adress_nickname'];
            $_POST['start_area_id'] = $order['area_id'];
            $_POST['start_city_id'] = $order['city_id'];
            $_POST['start_province_id'] = $order['province_id'];
        }else{
            // 获取分类信息
            $database_Service_category = D('Service_category');
            $now_category = $database_Service_category->field(true)->where(array('cid'=>$cid))->find();
            if (empty($now_category)) {
                returnCode('50000002');
            }

            // 获取默认地址
            $condition_user_adress = [
                'uid' => $this->_uid,
                'default' => 1,
            ];
            $userAddress = M('User_adress')->field(true)->where($condition_user_adress)->find();
            if(empty($userAddress)){
                returnCodeError(L_('未设置默认地址'));
            }
            $addressInfo = plugin_model('User_adress')->get_one_adress($this->_uid, $userAddress['adress_id']);

            $address_msg = array();
            if ($addressInfo) {
                $address_msg = array(
                    'province_id' =>  $addressInfo['province'],
                    'city_id' =>  $addressInfo['city'],
                    'area_id' =>  $addressInfo['area'],
                );
            }
           
            // 获取对应时间信息
            $time_info = PD('Deliver_service_set')->publish_time_info($now_category['type'], $address_msg, true, $id);
            $date = $time_info['timeList'][0][0]['date'];
            $time = $time_info['timeList'][0][0]['time'];
            $now_time = $date.' '.$time;

            $_POST['start_adress_lat'] = $addressInfo['latitude'];
            $_POST['start_adress_lng'] = $addressInfo['longitude'];
            $_POST['start_adress_area_id'] = $addressInfo['adress_id'];
            $_POST['start_adress_phone'] = $addressInfo['phone'];
            $_POST['start_adress_detail'] = $addressInfo['adress'];
            $_POST['start_adress_name'] = $addressInfo['name'];
            $_POST['start_area_id'] = $addressInfo['area'];
            $_POST['start_city_id'] = $addressInfo['city'];
            $_POST['start_province_id'] = $addressInfo['province'];
            $_POST['time_info'] = $now_time;
            $_POST['is_voice'] = '1';
            $_POST['is_final_pay'] = '0';
        }

        $return = $this->publish_give_data();
        returnCode('0',$return);
    }

    //计费规则
    public function pay_rule(){
        //include(WEB_PATH.'plugin/paotui/Common/convert.php');
         /*分类里有个区域的id*/
        $name=$_POST['city_name'];//城市名称
        //$area_id=$_POST['area_id'];//区域id
        if(!empty($name)){
            $where1="area_name ='".$name."'";
            $city = M('Area')->field("area_id")->where($where1)->select();
            $where['area_id']=$city[0]['area_id'];
        }else{
            $where=[];
        }

        $mer_id=$_POST['store_id'];//门店id
        $point['lng']=$_POST['long1'];//起始
        $point['lat']=$_POST['lat1'];

        $point1['lng']=$_POST['long2'];//目的地
        $point1['lat']=$_POST['lat2'];

        $type=$_POST['type'];//0帮我买1帮我送
        $basic_price['distance']=[];//距离公里
        $basic_price['basic_price']=[];//基础配送费
        $basic_price['weight_list']=[];//重量附加费
        $basic_price['over_distance_price']=[];//距离附加费
        $basic_price['spe_list']=[];//特殊时段费
        $store_id['store_id']=$mer_id;
        //$convert = new Convert();

        /*$dist= $convert->distance($point['lat'], $point['lng'], $point1['lat'], $point1['long']);
        if(!empty($dist)){
            $basic_price['distance']=$dist;
        }*/

        /*判断是否设置了指定店铺配送费用，设置了的话基础配送费就是这个*/
        if(!empty($store_id)){
            $stores=M('service_auto_goods')->where($store_id)->find();
            if(!empty($stores)){
                $delivery_fee=$basic['delivery_fee']=$stores['delivery_fee'];
            }
        }

        /*区级*/
        if(!empty($where)){
            $res=M('Deliver_service_set')->where($where)->find();
        }else{
            /*不存在条件*/
            $res=[];
        }
        //fdump($type,"f99999",1);
        if($type==2){//帮我买配置
            /*----重量附加费用-------*/
            $service_buy_weight_info = $this->config['service_buy_weight_info'];
            $service_buy_weight_info = unserialize($service_buy_weight_info);
            // start_weight 范围起始公斤数 end_weight 范围结束公斤数 范围价格 weight_price
            foreach ($service_buy_weight_info as $key => &$val) {
                if ($val && $val['start_weight']>=0 && $val['start_weight']!='' && $val['end_weight'] && $val['weight_price']>=0 && $val['weight_price']!='') {
                    $val['msg'] = $val['start_weight'] . '-' . $val['end_weight'] .'kg';
                    $val['price']=$val['weight_price'];
                } else {
                    unset($service_buy_weight_info[$key]);
                }
            }
            if ($service_buy_weight_info) {
                $datas = $service_buy_weight_info;
            } else {
                $datas = array();
            }
            $basic_price['weight_list']=$datas;
            /*------重量附加费用-------*/

            if(empty($res)){
                /*市级*/
                $where['area_id']=$res['area_pid'];
                $res1=M('Deliver_service_set')->where($where)->find();
                if(empty($res1)){
                    /*省级*/
                    $where['area_id']=$res1['area_pid'];
                    $res2=M('Deliver_service_set')->where($where)->find();
                    if(empty($res2)){
                        /*省级没有,帮买*/
                        /*if($type==0){*/
                        /*帮我买配置 平台配送时间段一*/
                        $res3= $this->config['service_buy_delivery_time'];
                        $result=explode("-",$res3);

                        /*帮我买配置 平台配送时间段二*/
                        $res4=$this->config['service_buy_delivery_time2'];//平台配送时间段2
                        if($result[0]==$result[1] || $result[0]>$result[1]){
                            /*24小时营业 基础配送费*/
                            if(empty($basic['delivery_fee'])){
                                $basic['delivery_fee']=$this->config['service_buy_delivery_fee'];//起步配送费1
                                $basic['delivery_fee']=$this->config['service_buy_delivery_fee2'];//起步配送费2
                            }else{
                                $basic['delivery_fee']=$delivery_fee;
                            }

                            /*基础配送距离*/
                            $basic['distance']=$this->config['service_buy_basic_distance'];//起步距离
                            $basic_price['basic_price']=$basic;

                            /*超出距离每公里费用*/
                            $basic['over_price']=$this->config['service_buy_per_km_price'];//超出起步距离的路程每公里的单价，超出部分的配送费计算规则根据【配送条件>配送费计算方式中为准】，配送距离计算规格根据【配送条件>配送距离计算方式中为准】
                            $basic_price['over_distance_price']=$basic['over_price'];
                           // return;
                        }else{
                            /*分时间段*/
                            /*1·第一个时间段包含第二个时间段，则只用第一个*/
                            if(empty($basic['delivery_fee'])){
                                $basic['delivery_fee']=$this->config['service_buy_delivery_fee'];//起步配送费1
                                $specil[0]['price']="";
                                $specil[1]['price']="";
                            }else{
                                $specil[0]['price']=$this->config['service_buy_delivery_fee'];//起步配送费1
                                $specil[1]['price']=$this->config['service_buy_delivery_fee2'];//起步配送费2
                            }
                            $specil[0]['time']=$res4;
                            $specil[0]['distance']=$this->config['service_buy_basic_distance'];//起步距离
                            $specil[0]['over_distance_price']=$this->config['service_buy_per_km_price'];//超出起步距离的路程每公里的单价，超出部分的配送费计算规则根据【配送条件>配送费计算方式
                            //$basic_price['spe_list']=$specil;

                            /*2·第一个时间段不包含第二个时间段，则用两个*/
                            $specil[1]['time']=$res4;
                            $specil[1]['distance']=$this->config['service_buy_basic_distance2'];
                            $specil[1]['over_distance_price']=$this->config['service_buy_per_km_price2'];
                            $basic_price['spe_list']=$specil;
                        }
                    }
                    elseif($res2['buy_status']==1){
                        /*省级有*/
                        if(!empty($basic['delivery_fee'])){
                            $basic['delivery_fee']=$delivery_fee;
                            $basic['distance']=$res['buy_base_distance1'];//起步距离
                            $basic_price['basic_price']=$basic;
                        }
                        else{
                            $basic_price['basic_price']="";
                            /*分时间段*/
                            /*1·第一个时间段包含第二个时间段，则只用第一个*/
                            $specil[0]['price']=$res2['buy_base_fee1'];//起步配送费1
                            $specil[1]['price']=$res2['buy_base_fee2'];//起步配送费2

                            //$specil[0]['time']=$res4;
                            $specil[0]['distance']=$res2['buy_base_distance1'];//起步距离
                            $specil[1]['distance1']=$res2['buy_base_distance2'];//起步距离

                            $specil[0]['over_distance_price']=$res2['buy_per_km_price1'];//超出起步距离的路程每公里的单价，超出部分的配送费计算规则根据【配送条件>配送费计算方式
                            $specil[1]['over_distance_price1']=$res2['buy_per_km_price2'];
                            $basic_price['spe_list']=$specil;
                        }
                    }
                }
                elseif(!empty($res1) && $res1['buy_status']==1){
                    /*市级有*/
                    if(!empty($basic['delivery_fee'])){
                        $basic['delivery_fee']=$delivery_fee;
                        $basic['distance']=$res['buy_base_distance1'];//起步距离
                        $basic_price['basic_price']=$basic;
                    }
                    else{
                        $basic_price['basic_price']="";
                        /*分时间段*/
                        /*1·第一个时间段包含第二个时间段，则只用第一个*/
                        $specil[0]['price']=$res1['buy_base_fee1'];//起步配送费1
                        $specil[1]['price']=$res1['buy_base_fee2'];//起步配送费2

                        //$specil[0]['time']=$res4;
                        $specil[0]['distance']=$res1['buy_base_distance1'];//起步距离
                        $specil[1]['distance1']=$res1['buy_base_distance2'];//起步距离

                        $specil[0]['over_distance_price']=$res1['buy_per_km_price1'];//超出起步距离的路程每公里的单价，超出部分的配送费计算规则根据【配送条件>配送费计算方式
                        $specil[1]['over_distance_price1']=$res1['buy_per_km_price2'];
                        $basic_price['spe_list']=$specil;
                    }
                }
            }
            elseif(!empty($res) && $res['buy_status']==1){
                /*区级有*/
                if(!empty($basic['delivery_fee'])){
                    $basic['delivery_fee']=$delivery_fee;
                    $basic['distance']=$res['buy_base_distance1'];//起步距离
                    $basic_price['basic_price']=$basic;
                }
                else{
                    $basic_price['basic_price']="";
                    /*分时间段*/
                    /*1·第一个时间段包含第二个时间段，则只用第一个*/
                    $specil[0]['price']=$res['buy_base_fee1'];//起步配送费1
                    $specil[1]['price']=$res['buy_base_fee2'];//起步配送费2

                    //$specil[0]['time']=$res4;
                    $specil[0]['distance']=$res['buy_base_distance1'];//起步距离
                    $specil[1]['distance1']=$res['buy_base_distance2'];//起步距离

                    $specil[0]['over_distance_price']=$res['buy_per_km_price1'];//超出起步距离的路程每公里的单价，超出部分的配送费计算规则根据【配送条件>配送费计算方式
                    $specil[1]['over_distance_price1']=$res['buy_per_km_price2'];
                    $basic_price['spe_list']=$specil;
                }
            }
        }
        else{//帮我送
            /*----重量附加费用-------*/
            $service_buy_weight_info = $this->config['service_give_weight_info'];
            $service_buy_weight_info = unserialize($service_buy_weight_info);
            // start_weight 范围起始公斤数 end_weight 范围结束公斤数 范围价格 weight_price
            foreach ($service_buy_weight_info as $key => &$val) {
                if ($val && $val['start_weight']>=0 && $val['start_weight']!='' && $val['end_weight'] && $val['weight_price']>=0 && $val['weight_price']!='') {
                    $val['msg'] = $val['start_weight'] . '-' . $val['end_weight'] .'kg';
                    $val['price']=$val['weight_price'];
                } else {
                    unset($service_buy_weight_info[$key]);
                }
            }
            if ($service_buy_weight_info) {
                $datas = $service_buy_weight_info;
            } else {
                $datas = array();
            }
            $basic_price['weight_list']=$datas;
            /*------重量附加费用-------*/

            if(empty($res)){
                /*市级*/
                $where['area_id']=$res['area_pid'];
                $res1=M('Deliver_service_set')->where($where)->find();
                if(empty($res1)){
                    /*省级*/
                    $where['area_id']=$res1['area_pid'];
                    $res2=M('Deliver_service_set')->where($where)->find();
                    if(empty($res2)){
                        /*省级没有,帮买*/
                        /*if($type==0){*/
                        /*帮我买配置 平台配送时间段一*/
                        $res3= $this->config['service_buy_delivery_time'];
                        $result=explode("-",$res3);

                        /*帮我买配置 平台配送时间段二*/
                        $res4=$this->config['service_buy_delivery_time2'];//平台配送时间段2
                        if($result[0]==$result[1] || $result[0]>$result[1]){
                            /*24小时营业 基础配送费*/
                            if(empty($basic['delivery_fee'])){
                                $basic['delivery_fee']=$this->config['service_buy_delivery_fee'];//起步配送费1
                                $basic['delivery_fee']=$this->config['service_buy_delivery_fee2'];//起步配送费2
                            }else{
                                $basic['delivery_fee']=$delivery_fee;
                            }

                            /*基础配送距离*/
                            $basic['distance']=$this->config['service_buy_basic_distance'];//起步距离
                            $basic_price['basic_price']=$basic;

                            /*超出距离每公里费用*/
                            $basic['over_price']=$this->config['service_buy_per_km_price'];//超出起步距离的路程每公里的单价，超出部分的配送费计算规则根据【配送条件>配送费计算方式中为准】，配送距离计算规格根据【配送条件>配送距离计算方式中为准】
                            $basic_price['over_distance_price']=$basic['over_price'];
                            //return;
                        }else{
                            /*分时间段*/
                            /*1·第一个时间段包含第二个时间段，则只用第一个*/
                            if(empty($basic['delivery_fee'])){
                                $basic['delivery_fee']=$this->config['service_buy_delivery_fee'];//起步配送费1
                                $specil[0]['price']="";
                                $specil[1]['price']="";
                            }else{
                                $specil[0]['price']=$this->config['service_buy_delivery_fee'];//起步配送费1
                                $specil[1]['price']=$this->config['service_buy_delivery_fee2'];//起步配送费2
                            }
                            $specil[0]['time']=$res4;
                            $specil[0]['distance']=$this->config['service_buy_basic_distance'];//起步距离
                            $specil[0]['over_distance_price']=$this->config['service_buy_per_km_price'];//超出起步距离的路程每公里的单价，超出部分的配送费计算规则根据【配送条件>配送费计算方式
                            //$basic_price['spe_list']=$specil;

                            /*2·第一个时间段不包含第二个时间段，则用两个*/
                            $specil[1]['time']=$res4;
                            $specil[1]['distance']=$this->config['service_buy_basic_distance2'];
                            $specil[1]['over_distance_price']=$this->config['service_buy_per_km_price2'];
                            $basic_price['spe_list']=$specil;
                        }
                    }
                    elseif($res2['buy_status']==1){
                        /*省级有*/
                        if(!empty($basic['delivery_fee'])){
                            $basic['delivery_fee']=$delivery_fee;
                            $basic['distance']=$res['buy_base_distance1'];//起步距离
                            $basic_price['basic_price']=$basic;
                        }
                        else{
                            $basic_price['basic_price']="";
                            /*分时间段*/
                            /*1·第一个时间段包含第二个时间段，则只用第一个*/
                            $specil[0]['price']=$res2['give_base_fee1'];//起步配送费1
                            $specil[1]['price']=$res2['give_base_fee2'];//起步配送费2

                            $specil[0]['time']=$res2['give_deliver_time1'];
                            $specil[1]['time']=$res2['give_deliver_time2'];
                            $specil[0]['distance']=$res2['give_base_distance1'];//起步距离
                            $specil[1]['distance1']=$res2['give_base_distance2'];//起步距离

                            $specil[0]['over_distance_price']=$res2['give_per_km_price1'];//超出起步距离的路程每公里的单价，超出部分的配送费计算规则根据【配送条件>配送费计算方式
                            $specil[1]['over_distance_price1']=$res2['give_per_km_price2'];
                            $basic_price['spe_list']=$specil;
                        }
                    }
                }
                elseif(!empty($res1) && $res1['buy_status']==1){
                    /*市级有*/
                    if(!empty($basic['delivery_fee'])){
                        $basic['delivery_fee']=$delivery_fee;
                        $basic['distance']=$res['buy_base_distance1'];//起步距离
                        $basic_price['basic_price']=$basic;
                    }
                    else{
                        $basic_price['basic_price']="";
                        /*分时间段*/
                        /*1·第一个时间段包含第二个时间段，则只用第一个*/
                        $specil[0]['price']=$res1['give_base_fee1'];//起步配送费1
                        $specil[1]['price']=$res['give_base_fee2'];//起步配送费2

                        $specil[0]['time']=$res1['give_deliver_time1'];
                        $specil[1]['time']=$res1['give_deliver_time2'];
                        $specil[0]['distance']=$res1['give_base_distance1'];//起步距离
                        $specil[1]['distance1']=$res1['give_base_distance2'];//起步距离

                        $specil[0]['over_distance_price']=$res1['give_per_km_price1'];//超出起步距离的路程每公里的单价，超出部分的配送费计算规则根据【配送条件>配送费计算方式
                        $specil[1]['over_distance_price1']=$res1['give_per_km_price2'];
                        $basic_price['spe_list']=$specil;
                    }
                }
            }
            elseif(!empty($res) && $res['give_status']==1){
                /*区级有*/
                if(!empty($basic['delivery_fee'])){
                    $basic['delivery_fee']=$delivery_fee;
                    $basic['distance']=$res['buy_base_distance1'];//起步距离
                    $basic_price['basic_price']=$basic;
                }
                else{
                    $basic_price['basic_price']="";
                    /*分时间段*/
                    /*1·第一个时间段包含第二个时间段，则只用第一个*/
                    $specil[0]['price']=$res['give_base_fee1'];//起步配送费1
                    $specil[1]['price']=$res['give_base_fee2'];//起步配送费2

                    $specil[0]['time']=$res['give_deliver_time1'];
                    $specil[1]['time']=$res['give_deliver_time2'];
                    $specil[0]['distance']=$res['give_base_distance1'];//起步距离
                    $specil[1]['distance1']=$res['give_base_distance2'];//起步距离

                    $specil[0]['over_distance_price']=$res['give_per_km_price1'];//超出起步距离的路程每公里的单价，超出部分的配送费计算规则根据【配送条件>配送费计算方式
                    $specil[1]['over_distance_price1']=$res['give_per_km_price2'];
                    $basic_price['spe_list']=$specil;
                }
            }
        }
        //$basic_price['spe_list']=$specil;
        returnCode(0, array('data' => $basic_price));
    }

    // 图片上传
    public function up_img(){
        if (!$this->_uid) {
            returnCode('20044013');
        }
        if(!$_FILES){
            returnCode('20140051');
        }
        if($_FILES['imgFile']['error'] == 3){
            returnCode('20140051');
        }

        if ($_FILES['imgFile']['error'] != 4) {
            $image = PD('Image')->handle($this->_uid, 'paotui', 0, array('size' => 20), false);
            if ($image['error']) {
                returnCode('20140052');
            } else {
                returnCode(0, array('url' => replace_file_domain($image['url']['imgFile'])));
            }
        } else {
            returnCode('20140051');
        }
    }

    public function test() {
        $img = PD('Area')->cityMatching(31.869754,117.281689);
        $info = D('Deliver_service_set')->test();
        dump($img);
    }

    //--story=1002202 --user=卢敏 跑腿售卖商品自定义--接口 https://www.tapd.cn/47145533/s/1146182
    public function auto_cate_goods(){
        //$city=$_POST['city'];//城市编号
        $city=$_POST['now_city'];//城市编号
        $long=$_POST['long'];//用户经度
        $lat=$_POST['lat'];//用户纬度
        $name=$_POST['city_name'];//城市名称
        $where="is_del=0 AND status=1";
       
        /*模糊查询城市id*/
        if(empty($city) && !empty($name)){
            $where1="area_name ='".$name."'";
            $city = M('Area')->where($where1)->getField('area_id');
        }
        if($city){
            $where.=' AND (is_common=0 OR (is_common=1 AND city='.$city.'))';
        }else{
            $where.=' AND is_common=0';
        }
        $all_category = M('Service_auto_category')->field("id,name,img,type")->where($where)->order("sort desc")->select();
        $all_category = $all_category ?: [];
        foreach ($all_category as $key => $value) {
            $all_category[$key]['img'] = thumb('/upload/system/merchant/'.$value['img'], 120, 120);

            if ($value['type'] == 0) {
                $all_category[$key]['goods'] = M("Service_auto_goods")->where("is_del=0 and cid=".$value['id'])->field("id, name")->order("sort desc,id asc")->select();
            }else{
                // 自定义店铺
                $result = PD('Service_auto_category')->getAutoStoreByCid($value['id'],false,$long,$lat);
                foreach ($result['store_list'] as $_k => $_store) {
                   if($_store['delivery_fee']==='' || $_store['delivery_fee']=='-1'){
                        $deliver_fee = PD('Deliver_service_set')->getNowTimeConfig();
                        $result['store_list'][$_k]['delivery_fee'] = $deliver_fee['service_delivery_fee'];
                        
                   }
                }
                $result = PD('Service_auto_category')->getAutoStoreByCid($value['id'],false,$long,$lat);
                $all_category[$key]['goods'] = $result['store_list'];

            }
        }
        returnCodeOk($all_category);
    }


    public function voiceConfig()
    {
        $return = ['is_show' => false];
        $uid = $this->_uid;
        if (C('config.paotui_voice_open') && $uid) {
            $limit = C('config.paotui_voice_condition');
            $user = M('User')->where(['uid' => $uid])->field('now_money')->find();
            $return['is_show'] = ($user && $user['now_money'] >= $limit);
        }
        returnCodeOk($return);
    }


}
?>