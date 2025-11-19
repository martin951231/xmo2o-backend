<?php
class PaotuiAction extends BaseAction{
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
                    $this->returnCode(0, $return);
                }else{
                    $this->returnCode(0,array('emptyUser'=>true));
                }
            }else{
                $this->returnCode(0,array('emptyUser'=>true));
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
			
            $this->returnCode(0,$return);
        }else{
            $this->returnCode(0,array('emptyUser'=>true));
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
		$this->returnCode('0',$return);
	}
	
	//根据用户上报的经纬度，得到能为其服务的配送员数量
	public function deliverAround(){
		$fromType = 0;
		if($_POST['type'] == 'gcj02'){
			$fromType = 3;
        }
		
		$longlatClass = new longlat();
		if($fromType){
			$longlat = $longlatClass->toBaidu($_POST['lat'],$_POST['lng'],$fromType);
			if(!$longlat){
				$this->returnCode(0,array('returnDeliver'=>array(),'userAdress'=>array()));
			}
			$_POST['lat'] = $longlat['lat'];
			$_POST['lng'] = $longlat['lng'];
		}
		
		
		//返回配送员数量
        $deliver = D('Deliver_user')->hasUser($_POST['lat'],$_POST['lng']);
		$returnDeliver = array();
		if($deliver){
			foreach($deliver as $deliverValue){
				$longlat = $longlatClass->baiduToGcj02($deliverValue['now_lat'],$deliverValue['now_lng']);
				$returnDeliver[] = array(
					'lng' => $longlat['lng'],
					'lat' => $longlat['lat'],
				);
			}
		}
		
		//若用户登录情况下返回离用户最近的收货地址
		if($this->_uid){
			$userAdress = D('User_adress')->getNearAdress($this->_uid,$_POST['lat'],$_POST['lng'],true);
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
		$return['returnDeliver']  = $returnDeliver;
		$return['userAdress']  = $userAdress;
        $this->returnCode(0,$return);
	}
	
	//根据用户上报的位置，得到省市区
	public function wxapp_get_location(){
		$fromType = 0;
		if($_POST['type'] == 'gcj02'){
			$fromType = 3;
        }
		
		$longlatClass = new longlat();
		if($fromType){
			$longlat = $longlatClass->toBaidu($_POST['lat'],$_POST['lng'],$fromType);
			if(!$longlat){
				$this->returnCode(0,array());
			}
			$address_lat = $longlat['lat'];
			$address_lng = $longlat['lng'];
		}else{
			$address_lat = $_POST['lat'];
			$address_lng = $_POST['lng'];
		}
		
		// 指定地点 获得省市区id
		$area_info = D('Area')->cityMatching($address_lat, $address_lng);
		
		if(empty($area_info['area_id']) || $area_info['is_open'] != 1){
			$this->returnCode('10001',array(),L_('当前城市暂未开通该服务'));
		}
		
		if(empty($area_info['area_info']['area_id']) || $area_info['area_info']['area_is_open'] != 1){
			$this->returnCode('10001',array(),L_('当前区域暂未开通该服务'));
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
		$this->returnCode(0,$return);
	}
	
	//获取时间和运费配置项-备份
	public function publish_detail_bak(){
        $cid = $_POST['cid'];

        // 获取分类信息
        $database_Service_category = D('Service_category');
        $now_category = $database_Service_category->field(true)->where(array('cid'=>$cid))->find();
        if (empty($now_category)) {
            $this->returnCode('50000002');
        }

        // 判断是否有今天数据
        $weekarray=array(L_("日"),L_("一"),L_("二"),L_("三"),L_("四"),L_("五"),L_("六")); //定义星期
        // 统一获取一小时后的时间 即为配送最早为一小时后
        $service_deliver_send_time = intval(C('config.service_deliver_send_time'));
        if ($service_deliver_send_time && $service_deliver_send_time > 0) {
            $date_time_H = date("H", strtotime("+".$service_deliver_send_time." minutes"));
            $date_time_M = date("i", strtotime("+".$service_deliver_send_time." minutes"));
        } else {
            $date_time_H = date("H", strtotime("+1 hours"));
            $date_time_M = date("i", strtotime("+1 hours"));
        }
        // 处理今天是否有数据
        $is_today = 0;
        $start_time1 = 0;
        $end_time1 = 0;
        $delivery_startTime_minute1 = 0;
        $delivery_endTime_minute1 = 0;
        // 是否跨天
        $is_add_day = 0;
        $delivery_times = explode('-', $this->config['service_delivery_time']);
        if ($delivery_times[0] && $delivery_times[1]) {
            $delivery_startTime_minute_arr1 = explode(':', $delivery_times[0]);
            $delivery_endTime_minute_arr1 = explode(':', $delivery_times[1]);
            // 获取截止时间的分钟数，做最后取件时间判断
            $delivery_startTime_minute1 = intval($delivery_startTime_minute_arr1[1]);
            $delivery_endTime_minute1 = intval($delivery_endTime_minute_arr1[1]);
            // 做超过(包含)24点的时间范围处理
            $delivery_times1_start = intval($delivery_startTime_minute_arr1[0]);
            $delivery_times1_end = intval($delivery_endTime_minute_arr1[0]);

            // 处理起始时间
            if ($delivery_times1_start < $delivery_times1_end || ($delivery_times1_start == $delivery_times1_end && $delivery_startTime_minute1 < $delivery_endTime_minute1)) {
                $start_time1 = $delivery_times1_start;
                $end_time1 = $delivery_times1_end;
            } else {
                $start_time1 = $delivery_times1_start;
                $end_time1 = $delivery_times1_end + 24;
            }

            for ($d=0; $d <= intval($this->config['service_days_number']); $d++) {
                for ($i = $start_time1; $i <= $end_time1 ; $i++) {
                    if($d == 0){
                        if($i >= $date_time_H){
                            for ($s=0; $s <= 5; $s++) {
                                if ($i == $date_time_H && intval($s."0") < $date_time_M) {
                                    continue;
                                } else if ($i == $start_time1 && intval($s."0") < $delivery_startTime_minute1) {
                                    continue;
                                } else {
                                    if ($service_deliver_send_time && $service_deliver_send_time > 0) {
                                        $num = intval($s."0") - intval($date_time_M);
                                        if ($i == $date_time_H && $num < 10) {
                                            $minute_info = $date_time_M;
                                        } else {
                                            $minute_info = $s."0";
                                        }
                                    } else {
                                        $minute_info = $s."0";
                                    }
                                }
                                if ($i != $end_time1 || intval($s."0") < $delivery_endTime_minute1) {
                                    if ($i >= 24) {
                                        $hour = $i - 24;
                                        $day = $d + 1;
                                        $is_add_day = 1;
                                    } else {
                                        $hour = $i;
                                        $day = $d;
                                    }
                                    if ($day == 0) {
                                        $time[$day][$hour][$s]['week'] = L_("今天");
                                        $is_today = 1;
                                    } else if ($day == 1) {
                                        $time[$day][$hour][$s]['week'] = L_("明天");
                                    } else if ($day == 2) {
										$time[$day][$hour][$s]['week'] = L_("后天");
									}
                                    $time[$day][$hour][$s]['time'] = $hour . ":" . $minute_info;
                                    $time[$day][$hour][$s]['basic_distance'] = $this->config['service_basic_distance'];
                                    $time[$day][$hour][$s]['delivery_fee'] = $this->config['service_delivery_fee'];
                                    $time[$day][$hour][$s]['per_km_price'] = $this->config['service_per_km_price'];
                                    $time[$day][$hour][$s]['date'] = date("m-d", strtotime("+" . $day . " day"));
                                }
                            }
                        }
                    }else{
                        for ($s=0; $s <= 5; $s++) {
                            if ($i == $start_time1 && intval($s."0") < $delivery_startTime_minute1) {
                                continue;
                            }
                            if (empty($time[$d][$i][$s]) && ($i != $end_time1 || intval($s."0") < $delivery_endTime_minute1)) {
                                if ($i >= 24) {
                                    $hour = $i - 24;
                                    $day = $d + 1;
                                    $is_add_day = 1;
                                } else {
                                    $hour = $i;
                                    $day = $d;
                                }
                                $time[$day][$hour][$s]['time'] = $hour . ":" . $s . "0";
                                $time[$day][$hour][$s]['basic_distance'] = $this->config['service_basic_distance'];
                                $time[$day][$hour][$s]['delivery_fee'] = $this->config['service_delivery_fee'];
                                $time[$day][$hour][$s]['per_km_price'] = $this->config['service_per_km_price'];
                                if ($day == 1) {
                                    $time[$day][$hour][$s]['week'] = L_("明天");
                                } else if ($day == 2) {
                                    $time[$day][$hour][$s]['week'] = L_("后天");
                                } else {
                                    $time[$day][$hour][$s]['week'] = L_("周") . $weekarray[date("w", strtotime("+" . $day . " day"))];
                                }
                                $time[$day][$hour][$s]['date'] = date("m-d", strtotime("+" . $day . " day"));
                            }
                        }
                    }
                }
            }
        }

        $start_time = 0;
        $end_time = 0;
        $delivery_startTime_minute2 = 0;
        $delivery_endTime_minute2 = 0;
        $delivery_times2 = explode('-', $this->config['service_delivery_time2']);
        if ($delivery_times2[0] && $delivery_times2[1]) {
            $delivery_startTime_minute_arr2 = explode(':', $delivery_times2[0]);
            $delivery_endTime_minute_arr2 = explode(':', $delivery_times2[1]);
            // 获取截止时间的分钟数，做最后取件时间判断
            $delivery_startTime_minute2 = intval($delivery_startTime_minute_arr2[1]);
            $delivery_endTime_minute2 = intval($delivery_endTime_minute_arr2[1]);
            // 做超过(包含)24点的时间范围处理
            $delivery_times2_start = intval($delivery_times2[0]);
            $delivery_times2_end = intval($delivery_times2[1]);
            // 处理起始时间 结束时间大于开始时间或者两者时间小时相同，但分钟不同
            if ($delivery_times2_start < $delivery_times2_end || ($delivery_times2_start == $delivery_times2_end && $delivery_startTime_minute2 < $delivery_endTime_minute2)) {
                $start_time = $delivery_times2_start;
                $end_time = $delivery_times2_end;
            } else {
                $start_time = $delivery_times2_start;
                $end_time = $delivery_times2_end + 24;
            }

            for ($d=0; $d <= intval($this->config['service_days_number']); $d++) {
                for ($i = $start_time; $i <= $end_time ; $i++) {
                    if($d==0){
                        if($i >= $date_time_H){
                            for ($s=0; $s <= 5; $s++) {
                                if ($i == $date_time_H && intval($s."0") < $date_time_M) {
                                    continue;
                                } else if ($i == $start_time && intval($s."0") < $delivery_startTime_minute2) {
                                    continue;
                                } else {
                                    if ($service_deliver_send_time && $service_deliver_send_time > 0) {
                                        $num = intval($s."0") - intval($date_time_M);
                                        if ($i == $date_time_H && $num < 10) {
                                            $minute_info = $date_time_M;
                                        } else {
                                            $minute_info = $s."0";
                                        }
                                    } else {
                                        $minute_info = $s."0";
                                    }
                                }
                                if (empty($time[$d][$i][$s]) && ($i != $end_time || intval($s."0") < $delivery_endTime_minute2)) {
                                    if ($i >= 24) {
                                        $hour = $i - 24;
                                        $day = $d + 1;
                                        $is_add_day = 1;
                                    } else {
                                        $hour = $i;
                                        $day = $d;
                                    }
                                    if ($day == 0) {
                                        $time[$day][$hour][$s]['week'] = L_("今天");
                                        $is_today = 1;
                                    } else if ($day == 1) {
                                        $time[$day][$hour][$s]['week'] = L_("明天");
                                    }
                                    $time[$day][$hour][$s]['time'] = $hour.":".$minute_info;
                                    $time[$day][$hour][$s]['basic_distance'] = $this->config['service_basic_distance2'];
                                    $time[$day][$hour][$s]['delivery_fee'] = $this->config['service_delivery_fee2'];
                                    $time[$day][$hour][$s]['per_km_price'] = $this->config['service_per_km_price2'];
                                    $time[$day][$hour][$s]['date'] = date("m-d",strtotime("+".$day." day"));
                                }

                            }
                        }
                    }else{
                        for ($s=0; $s <= 5; $s++) {
                            if ($i == $start_time && intval($s."0") < $delivery_startTime_minute2) {
                                continue;
                            }
                            if (empty($time[$d][$i][$s]) && ($i != $end_time || intval($s."0") < $delivery_endTime_minute2)) {
                                if ($i >= 24) {
                                    $hour = $i - 24;
                                    $day = $d + 1;
                                    $is_add_day = 1;
                                } else {
                                    $hour = $i;
                                    $day = $d;
                                }
                                $time[$day][$hour][$s]['time'] = $hour . ":" . $s . "0";
                                $time[$day][$hour][$s]['basic_distance'] = $this->config['service_basic_distance2'];
                                $time[$day][$hour][$s]['delivery_fee'] = $this->config['service_delivery_fee2'];
                                $time[$day][$hour][$s]['per_km_price'] = $this->config['service_per_km_price2'];
                                if ($day == 1) {
                                    $time[$day][$hour][$s]['week'] = "明天";
                                } else if ($day == 2) {
                                    $time[$day][$hour][$s]['week'] = L_("后天");
                                } else {
                                    $time[$day][$hour][$s]['week'] = L_("周") . $weekarray[date("w", strtotime("+" . $day . " day"))];
                                }

                                $time[$day][$hour][$s]['date'] = date("m-d", strtotime("+" . $day . " day"));
                            }
                        }
                    }

                }
            }
        }

        foreach ($time as $key => $value) {
            ksort($value);
            foreach ($value as $kk => $vv) {
                foreach ($vv as $kkk => $vvv) {
                    $timeList[$key][] = $vvv;
                }
            }
        }


        // $weekarray=array("日","一","二","三","四","五","六");
        for ($d=0; $d <= intval($this->config['service_days_number']) + intval($is_add_day); $d++) {
			$day = date("m-d",strtotime("+".$d." day"));
			$daysList[$day]  =  array('show'=>$day,'day'=>$day);
            if($d == 0){
                if (1 == $is_today) {
                    $daysList[$day]['show'] = L_('今天');
                } else {
                    unset($daysList[$day]);
                }
            }else if($d==1){
				$daysList[$day]['show'] = L_('明天');
            }else if($d==2){
				$daysList[$day]['show'] = L_('后天');
            }else{
                $daysList[$day]['show'] =  date("Y-m-d",strtotime("+".$d." day"));
            }
        }

        if ($now_category['type'] == 2) {
            // 如果配置未全，仍然去之前的配置一中的配置
            if (empty($this->config['service_give_basic_distance']) || empty($this->config['service_give_delivery_fee']) || empty($this->config['service_give_per_km_price'])) {
                $config_time['service_basic_distance'] = $this->config['service_basic_distance'];
                $config_time['service_delivery_fee'] = $this->config['service_delivery_fee'];
                $config_time['service_per_km_price'] = $this->config['service_per_km_price'];
            } else {
                $config_time['service_basic_distance'] = $this->config['service_give_basic_distance'];
                $config_time['service_delivery_fee'] = $this->config['service_give_delivery_fee'];
                $config_time['service_per_km_price'] = $this->config['service_give_per_km_price'];
            }
        } else {
            $h =  intval(date("H"));
            $m =  intval(date("i"));
            $now_time = intval(strtotime('' . $h . ':' . $m));
            $start_time_info1 = intval(strtotime('' . $start_time1 . ':' . $delivery_startTime_minute1));
            if ($end_time1 > 24) {
                $end_time1_msg = $end_time1 - 24;
                $end_time_info1 = intval(strtotime('' . $end_time1_msg . ':' . $delivery_endTime_minute1 . ' + 1 day'));
            } else {
                $end_time_info1 = intval(strtotime('' . $end_time1 . ':' . $delivery_endTime_minute1));
            }
            $start_time_info2 = intval(strtotime('' . $start_time . ':' . $delivery_startTime_minute2));
            if ($end_time > 24) {
                $end_time_msg = $end_time - 24;
                $end_time_info2 = intval(strtotime('' . $end_time_msg . ':' . $delivery_endTime_minute2 . ' + 1 day'));
            } else {
                $end_time_info2 = intval(strtotime('' . $end_time . ':' . $delivery_endTime_minute2));
            }
            // 判断当前属于的时间段
            if ($now_time >= $start_time_info1 && $now_time <= $end_time_info1) {
                $config_time['service_basic_distance'] = $this->config['service_basic_distance'];
                $config_time['service_delivery_fee'] = $this->config['service_delivery_fee'];
                $config_time['service_per_km_price'] = $this->config['service_per_km_price'];
            } elseif ($now_time >= $start_time_info2 && $now_time <= $end_time_info2) {
                $config_time['service_basic_distance'] = $this->config['service_basic_distance2'];
                $config_time['service_delivery_fee'] = $this->config['service_delivery_fee2'];
                $config_time['service_per_km_price'] = $this->config['service_per_km_price2'];
            } else {
                $config_time['service_basic_distance'] = 0;
                $config_time['service_delivery_fee'] = 0;
                $config_time['service_per_km_price'] = 0;
            }
        }



        $datas['config_time'] = $config_time;

		//重新排序时间
        ksort($timeList);
		foreach($timeList as $value){
			$daysList[$value[0]['date']]['timeList'] = $value;
		}

        $datas['daysList'] = array_values($daysList);

        $config['service_basic_km'] = $this->config['service_basic_km'];
        $config['service_basic_km_time'] = $this->config['service_basic_km_time'];
        $config['service_basic_weight'] = $this->config['service_basic_weight'];
        $config['service_basic_weight_price'] = $this->config['service_basic_weight_price'];
        $config['service_bounds_weight'] = $this->config['service_bounds_weight'];
        $config['service_days_number'] = $this->config['service_days_number'];
        $config['count_freight_charge_method'] = $this->config['count_freight_charge_method'];
        $datas['config'] = $config;


        $this->returnCode('0',$datas);
	}


    // 用户发布需求详情
    public function publish_detail(){
        $cid = I('cid');
        $lat = I('lat');
        $lng = I('lng');
        $appid = I('appid', '', 'strval');
        $app_key = I('app_key', '', 'strval');
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
                $this->returnCodeError('数据校验失败');
            }

            $merchant = M('Merchant')->where(array('appid' => $appid, 'key' => $app_key))->find();
            if (empty($merchant)) {
                $this->returnCodeError('平台配置信息错误');
            }
            $datas['merchant'] = array('money' => $merchant['money']);  // 商家金额
            // 获取帮我送类型-取正常的最先添加的
            $now_category = $database_Service_category->field(true)->order('cid ASC')->where(array('type'=>3, 'cat_status' => 1))->find();
            if (empty($now_category)) {
                $this->returnCodeError('平台配送类型不存在，请联系管理员！');
            }
        } elseif (!$this->_uid) {
            $this->returnCode('20044013');
        } else {
            // 获取分类信息
            $now_category = $database_Service_category->field(true)->where(array('cid'=>$cid))->find();
            if (empty($now_category)) {
                $this->returnCode('50000002');
            }
        }

        //返回配送员数量
        if ($lat>0 && $lng>0) {
            $deliver = D('Deliver_user')->hasUser($lat,$lng);
            $datas['deliver_count'] = count($deliver);
        }

        $adress_list = D('User_adress')->get_adress_list($this->_uid);
        foreach ($adress_list as $key => $value) {
            if ($value['latitude'] && $value['longitude']) {
                // 指定地点 获得省市区id
                $area_info = D('Area')->cityMatching($value['latitude'], $value['longitude']);
                if(empty($area_info['area_id']) || $area_info['is_open'] != 1){
                    continue;
                }
                if(empty($area_info['area_info']['area_id']) || $area_info['area_info']['area_is_open'] != 1){
                    continue;
                }
            }
            if($value['adress_id'] == $_GET['adress_id']){
                $addressInfo = $value;
            }elseif($value['default'] == 1){
                $addressInfo = $value;
            }
        }


        $address_msg = array();
        $publish_config = array();
        if ($addressInfo) {
            $address_msg = array(
                'province_id' =>  $addressInfo['province'],
                'city_id' =>  $addressInfo['city'],
                'area_id' =>  $addressInfo['area'],
            );
        }else{
            $addressInfo = $this->getObj();
        }
        if ($address_msg) {
            $publish_config = D('Deliver_service_set')->address_publish_config($address_msg);
        }

        // 如果未开启 帮我买 分段时间配送配置

        if($this->DEVICE_ID == 'wxapp'  || $this->DEVICE_ID == 'wxapp_paotui'){
            $is_wxapp_browser = true;
        } else {
            $is_wxapp_browser = false;
        }

        if ($now_category['type'] == 2 && (!$this->config['service_buy_deliver_times_switch'] || $is_wxapp_browser)) {
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
        } else {

            // 获取对应时间信息
//            $time_info = $this->publish_time_info($now_category['type']);
            $time_info = D('Deliver_service_set')->publish_time_info($now_category['type'], $address_msg, true);

            $config_time = $time_info['config_time'];
            $daysList = $time_info['daysList'];
            $timeList = $time_info['timeList'];
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
        $timeListBak = [];
        ksort($timeList);
        foreach ($timeList as $key => $value) {
            $timeListBak[] = $value;
        }
        $datas['timeList'] = $timeListBak;

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


        $this->returnCode('0',$datas);
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
                $this->returnCodeError('数据校验失败');
            }
            $merchant = M('Merchant')->where(array('appid' => $appid, 'key' => $app_key))->find();
            if (empty($merchant)) {
                $this->returnCodeError('平台配置信息错误');
            }
        }

        //计算配送距离
        $distance = 0;
        if (C('config.service_is_riding_distance') && C('config.baidu_map_ak')) {
            import('@.ORG.longlat');
            $longlat_class = new longlat();
            $distance = $longlat_class->getRidingDistance($_POST['start_lat'],$_POST['start_lng'],$_POST['end_lat'],$_POST['end_lng']);
            if ($distance == -1) {
                $this->returnCode('50000008');
            }
			if($distance){
				$returnArr['is_ride'] = true;
			}
        }
        $distance || $distance = getDistance($_POST['start_lat'],$_POST['start_lng'],$_POST['end_lat'],$_POST['end_lng']);
        $distance = $distance / 1000;
		$returnArr['distance'] = $distance;
        $this->returnCode('0',$returnArr);
    }


    // 计算两个收货地址的距离（骑行/直线）帮我送
    private function distance_calculation($data){
        //计算配送距离
        $distance = 0;
        if (C('config.service_is_riding_distance') && C('config.baidu_map_ak')) {
            import('@.ORG.longlat');
            $longlat_class = new longlat();
            $distance = $longlat_class->getRidingDistance($data['start_lat'],$data['start_lng'],$data['end_lat'],$data['end_lng']);
            if ($distance == -1) {
                $this->returnCode('50000008');
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
                $price = round($price, 2);
                break;
            case 1:
                $price = ceil($price * 10) / 10;
                break;
            case 2:
                $price = floor($price * 10) / 10;
                break;
            case 3:
                $price = round($price, 1);
                break;
            case 4:
                $price = ceil($price);
                break;
            case 5:
                $price = floor($price);
                break;
            case 6:
                $price = round($price);
                break;
        }
        return $price;
    }

    // 帮我送订单信息-备份
    public function publish_give_data_bak(){
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $distance = array(
            'start_lat' => $_POST['start_adress_lat'],
            'start_lng' => $_POST['start_adress_lng'],
            'end_lat' =>   $_POST['end_adress_lat'],
            'end_lng' =>   $_POST['end_adress_lng'],
        );
        $distance_info = $this->distance_calculation($distance);
        $weight_price = 0;
		if(empty($_POST['weight'])){
			$_POST['weight'] = $this->config['service_basic_weight'];
		}
        // 计算中重量费用
        if (!empty($_POST['weight'])) {
            // 初始重量
            $service_basic_weight = floatval($this->config['service_basic_weight']);
            // 初始重量费用
            $service_basic_weight_price = floatval($this->config['service_basic_weight_price']);
            // 超出初始重量每公斤费用
            $service_bounds_weight = floatval($this->config['service_bounds_weight']);
            $weight = floatval($_POST['weight']);
            if ($weight > $service_basic_weight) {
                $weight_price = ($weight - $service_basic_weight) * $service_bounds_weight + $service_basic_weight_price;
            } else {
                $weight_price = $service_basic_weight_price;
            }
            $weight_price = $this->publish_count_method($weight_price);
        } else {
            $this->returnCode('50000004');
        }
        // 计算服务费用
        $distance_price = 0;
        if (!empty($_POST['fetch_time']) && !empty($_POST['per_km_price']) && !empty($_POST['basic_distance'])) {
            $per_km_price = floatval($_POST['per_km_price']);
            $basic_distance = floatval($_POST['basic_distance']);
            if ($distance_info < $basic_distance) {
                $distance_price = 0;
            } else {
                $distance_price = ($distance_info - $basic_distance) * $per_km_price;
            }
            $distance_price = $this->publish_count_method($distance_price);
        } else {
			fdump($_POST,'give_data_error_post_2');
            $this->returnCode('50000004');
        }
        // 计算总费用
        $basic_distance_price = floatval($_POST['basic_distance_price']);
        $tip_price = floatval($_POST['tip_price']);
        $total_price = $weight_price + $distance_price + $basic_distance_price + $tip_price;



        $data['address'] = $_POST['start_adress_detail'];
        $data['address_lng'] = $_POST['start_adress_lng'];
        $data['address_lat'] = $_POST['start_adress_lat'];
        $data['address_area_id'] = $_POST['start_adress_area_id'] ? $_POST['start_adress_area_id'] : 0;

        if ($_POST['start_province_id'] && $_POST['start_city_id'] && $_POST['start_area_id']) {
            $data['province_id'] = $_POST['start_province_id'];
            $data['city_id'] = $_POST['start_city_id'];
            $data['area_id'] = $_POST['start_area_id'];
        } else {
            if (empty($_POST['start_adress_lat']) || empty($_POST['start_adress_lng'])) {
                exit(json_encode(array('error'=>2,'msg'=>L_('请选择取货地址！'))));
            }
            // 指定地点 获得省市区id
            $area_info = D('Area')->cityMatching($_POST['start_adress_lat'], $_POST['start_adress_lng']);
            $data['province_id'] = $area_info['area_info']['province_id'];
            $data['city_id'] = $area_info['area_info']['city_id'];
            $data['area_id'] = $area_info['area_info']['area_id'];
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
            $deliver = D('Deliver_user')->hasUser($data['address_lat'],$data['address_lng']);
            if($deliver){
                $data['deliver_type'] = 1;
            }else{
                $data['deliver_type'] = 2;
            }
        }


        $data['add_time'] = time();
        $data['cid'] = $_POST['cid'];
        $data['uid'] = $this->_uid;
        $data['catgory_type'] = 3;
        $data['status'] = 1;
        $data['destance_sum'] = $distance_info;
        $nowtime = date("mdHis");
        $data['order_sn'] = $nowtime.rand(10,99).sprintf("%08d",$this->_uid);
        // 添加 跑腿和服务快派小程序下单区分
        if ($this->config['pay_wxapp_paotui_appid'] && $this->config['pay_wxapp_paotui_mchid']) {
            $data['service_type'] = 1;
        } else {
            $data['service_type'] = 0;
        }

        $res = D('Service_user_publish')->data($data)->add();
        if($res){
            $giveData['publish_id'] =  $res;
            $giveData['cid'] = $_POST['cid'];
            $giveData['goods_catgory'] =  $_POST['goods_catgory'];
            $giveData['weight'] =  $_POST['weight'];
            $giveData['price'] =  $_POST['price'];
            $giveData['img'] =  $_POST['img'];

            // $giveData['start_adress_id'] =  $_POST['start_adress_id'];

            $giveData['start_adress_name'] =  $_POST['start_adress_detail'];
            $giveData['start_adress_lng'] =  $_POST['start_adress_lng'];
            $giveData['start_adress_lat'] =  $_POST['start_adress_lat'];
            $giveData['start_adress_nickname'] =  $_POST['start_adress_name'];
            $giveData['start_adress_phone'] =  $_POST['start_adress_phone'];


            // $giveData['end_adress_id'] =  $_POST['end_adress_id'];

            $giveData['end_adress_name'] =  $_POST['end_adress_detail'];
            $giveData['end_adress_lng'] =  $_POST['end_adress_lng'];
            $giveData['end_adress_lat'] =  $_POST['end_adress_lat'];
            $giveData['end_adress_nickname'] =  $_POST['end_adress_name'];
            $giveData['end_adress_phone'] =  $_POST['end_adress_phone'];

            $giveData['fetch_time'] =  $_POST['fetch_time'];

            $giveData['tip_price'] =  $_POST['tip_price'];
            $giveData['remarks'] = $this->filterEmoji2($_POST['remarks']);

            $giveData['basic_distance_price'] =  $basic_distance_price;
            $giveData['distance_price'] =  $distance_price;
            $giveData['weight_price'] =  $weight_price;

            $giveData['total_price'] =  $total_price;
            $giveData['addtime'] =  time();
            $giveData['uid'] =  $this->_uid;
            // D("Service_user_publish_give")->data($giveData)->add();

            $give_res = D("Service_user_publish_give")->data($giveData)->add();

            if(!$give_res){
				fdump_sql(D('Service_user_publish_buy')->getLastSql(),'Service_user_publish_give_add_err');
                D('Service_user_publish')->where(array('publish_id'=>$res))->delete();
                $this->returnCodeError(L_('数据添加异常请重试！'));
            }

            $pay_order_param = array(
                'business_type' => 'service',
                'business_id' => $res,
                'order_name' => L_('帮我送订单'),
                'uid' => $this->_uid,
                'total_money' => $total_price,
                'wx_cheap' => 0,
            );
            $plat_order_result = D('Plat_order')->add_order($pay_order_param);
            if ($plat_order_result['error_code']) {
				fdump_sql($plat_order_result,'service_give_plat_add_order_err');
                $this->returnCodeError($plat_order_result['error_msg']);
            } else {
                $returnArr = array(
                    'msg' => '需求已发布,请去支付!',
                    'order_id' => $plat_order_result['order_id'],
                    'type' => 'plat'
                );
                $this->returnCode('0',$returnArr);
            }

        }else{
            $this->returnCode('50000006');
        }
    }

    // 帮我送
    public function publish_give_data(){
        $appid = I('appid', '', 'strval');
        $app_key = I('app_key', '', 'strval');
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
                $this->returnCodeError('数据校验失败');
            }
            // 校验必传字段
            if (empty($_POST['time_info'])){
                $service_deliver_send_time = $this->config['service_deliver_send_time']>0?$this->config['service_deliver_send_time']:0;
                $_POST['time_info'] = time() + ($service_deliver_send_time*60);
                $_POST['time_info'] = date('Y-m-d H:i:s', $_POST['time_info']);
            }
            if (strtotime($_POST['time_info']) <= time()) {
                $this->returnCodeError('上传时间格式错误或者小于当前时间');
            }
            if (!$_POST['notifyurl']) {
                $this->returnCodeError('请上传回调地址');
            }
            if (!$_POST['third_id']) {
                $this->returnCodeError('请上传订单');
            }
            $data['notifyurl'] = $_POST['notifyurl'];
            $merchant = M('Merchant')->where(array('appid' => $appid, 'key' => $app_key))->find();
            if (empty($merchant)) {
                $this->returnCodeError('平台配置信息错误');
            }
            // 获取帮我送类型-取正常的最先添加的
            $database_Service_category = D('Service_category');
            $now_category = $database_Service_category->field(true)->order('cid ASC')->where(array('type'=>3, 'cat_status' => 1))->find();
            if (empty($now_category)) {
                $this->returnCodeError('平台配送类型不存在，请联系管理员！');
            }
            $is_third = 1;
            $mer_id = $merchant['mer_id'];
        } elseif (!$this->_uid){
            $this->returnCode('20044013');
        }

        $distance = array(
            'start_lat' => $_POST['start_adress_lat'],
            'start_lng' => $_POST['start_adress_lng'],
            'end_lat' =>   $_POST['end_adress_lat'],
            'end_lng' =>   $_POST['end_adress_lng'],
        );
        $destance_sum = $this->distance_calculation($distance);
        $weight_price = 0;
        if(empty($_POST['weight'])){
            $_POST['weight'] = $this->config['service_basic_weight'];
        }

        // 计算中重量费用
        if (!empty($_POST['weight'])) {
            // 初始重量
            $service_basic_weight = floatval($this->config['service_basic_weight']);
            // 初始重量费用
            $service_basic_weight_price = floatval($this->config['service_basic_weight_price']);
            // 超出初始重量每公斤费用
            $service_bounds_weight = floatval($this->config['service_bounds_weight']);
            $weight = floatval($_POST['weight']);
            if ($weight > $service_basic_weight) {
                $weight_price = ($weight - $service_basic_weight) * $service_bounds_weight + $service_basic_weight_price;
            } else {
                $weight_price = $service_basic_weight_price;
            }
            $weight_price = $this->publish_count_method($weight_price);
        } elseif ($appid && $app_key) {
            $weight_price = 0;
        } else{
            $this->returnCode('50000004');
        }


        $tip_price = $_POST['tip_price'] ? $_POST['tip_price'] : 0;

        // 如果传入了时间值
        if ($_POST['time_info']) {
            // 获取收货地址信息
            $publish_config = D('Deliver_service_set')->location_publish_config($_POST['end_adress_lat'], $_POST['end_adress_lng']);

            $time_info = explode(' ', trim($_POST['time_info']));
            if (!$_POST['fetch_time']) {
                $_POST['fetch_time'] = $_POST['time_info'];
            }

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
            if ($delivery_times[0]) {
                $delivery_times_start = $pin_time . ' ' . $delivery_times[0];
            } else {
                $delivery_times_start = '';
            }
            if ($delivery_times[1]) {
                $delivery_times_end = $pin_time . ' ' . $delivery_times[1];
            } else {
                $delivery_times_end = '';
            }
            if ($delivery_times2[0]) {
                $delivery_times2_start = $pin_time . ' ' . $delivery_times2[0];
            } else {
                $delivery_times2_start = '';
            }
            if ($delivery_times2[1]) {
                $delivery_times2_end = $pin_time . ' ' . $delivery_times2[1];
            } else {
                $delivery_times2_end = '';
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

            if ($delivery_times_start && $delivery_times_end && (($time_info_tip >= $delivery_times_start && $time_info_tip <= $delivery_times_end) || $delivery_times_start == $delivery_times_end)) {
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
                // 重量费用
                if($this->config['service_weight_multilevel_switch'] != 1) {
                    $weight = $_POST['weight'] ? $_POST['weight'] : 0;
                    $basic_weight = $this->config['service_basic_weight'];//基础重量
                    $basic_weight_price = $this->config['service_basic_weight_price'];// 基础价格
                    $bounds_weight = $this->config['service_bounds_weight'];// 超出配送费


                    if($weight <= $basic_weight){
                        $weight_price = $basic_weight_price;
                    }else{
                        $weight_price =  (($weight - $basic_weight)*$bounds_weight)+$basic_weight_price;
                    }
                }

                $basic_distance_price = $service_delivery_fee;
                $total = $distance_price + $basic_distance_price + $weight_price + $tip_price;
                $total_price = round($total, 2);

                if ($total_price < $service_delivery_fee) {
                    $this->returnCode(3006,array(),L_('费用计算错误，请重试！'));
                }
            } elseif ($delivery_times2_start && $delivery_times2_end && (($time_info_tip >= $delivery_times2_start && $time_info_tip <= $delivery_times2_end) || $delivery_times2_start == $delivery_times2_end)) {
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

                // 重量费用
                if($this->config['service_weight_multilevel_switch'] != 1) {
                    $weight = $_POST['weight'] ? $_POST['weight'] : 0;
                    $basic_weight = $this->config['service_basic_weight'];//基础重量
                    $basic_weight_price = $this->config['service_basic_weight_price'];// 基础价格
                    $bounds_weight = $this->config['service_bounds_weight'];// 超出配送费


                    if($weight <= $basic_weight){
                        $weight_price = $basic_weight_price;
                    }else{
                        $weight_price =  (($weight - $basic_weight)*$bounds_weight)+$basic_weight_price;
                    }
                }

                $basic_distance_price = $service_delivery_fee2;
                $total = $distance_price + $basic_distance_price + $weight_price + $tip_price;
                $total_price = round($total, 2);

                if ($total_price < $service_delivery_fee2) {
                    $this->returnCode(3006,array(),L_('费用计算错误，请重试！'));
                }
            } elseif ($appid && $app_key) {
                $distance_price = 0;
                $total_price = 0;
                $basic_distance_price = 0;
                if (!$weight || $weight<=0) {
                    $weight = 0;
                }
                if (!$tip_price || $tip_price<=0) {
                    $tip_price = 0;
                }
            } else {
                $this->returnCode(3007,array(),L_('所选时间配送员已经休息！'));
            }
            $_POST['distance_price'] = $distance_price;
            $_POST['tip_price'] = $tip_price;
            $_POST['total_price'] = $total_price;
            $_POST['basic_distance_price'] = $basic_distance_price;
            $_POST['weight'] = $weight;
            $_POST['weight_price'] = $weight_price;
        } elseif (!empty($_POST['fetch_time'])) {
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
            $tip_price = floatval($_POST['tip_price']);
            $total = $weight_price + $distance_price + $basic_distance_price + $tip_price;
            $total_price = round($total, 2);
            if ($total_price < $basic_distance_price) {
                $this->returnCode(3006,array(),L_('费用计算错误，请重试！'));
            }
        } else {
            $this->returnCode('50000004');
        }

        $data['address'] = $_POST['start_adress_detail'];
        $data['address_lng'] = $_POST['start_adress_lng'];
        $data['address_lat'] = $_POST['start_adress_lat'];
        $data['address_area_id'] = $_POST['start_adress_area_id'] ? $_POST['start_adress_area_id'] : 0;

        if ($_POST['start_province_id'] && $_POST['start_city_id'] && $_POST['start_area_id']) {
            $data['province_id'] = $_POST['start_province_id'];
            $data['city_id'] = $_POST['start_city_id'];
            $data['area_id'] = $_POST['start_area_id'];
        } else {
            if (empty($_POST['start_adress_lat']) || empty($_POST['start_adress_lng'])) {
                exit(json_encode(array('error'=>2,'msg'=>L_('请选择取货地址！'))));
            }
            // 指定地点 获得省市区id
            $area_info = D('Area')->cityMatching($_POST['start_adress_lat'], $_POST['start_adress_lng']);
            $data['province_id'] = $area_info['area_info']['province_id'];
            $data['city_id'] = $area_info['area_info']['city_id'];
            $data['area_id'] = $area_info['area_info']['area_id'];
        }
        if ($appid && $app_key && ($data['city_id'] || $data['area_id'])) {
            // 如果对接传过来的值，判断一下当前城市是否开启
            if ($data['city_id'] && $data['city_id']>0) {
                $area_msg = D('Area')->where(array('area_id' => $data['city_id']))->find();
                if ($area_msg && $area_msg['is_open']!=1) {
                    $this->returnCodeError('当前城市未开启，请选择别的城市');
                }
            }
            if ($data['area_id'] && $data['area_id']>0) {
                $area = D('Area')->where(array('area_id' => $data['area_id']))->find();
                if ($area && $area['is_open']!=1) {
                    $this->returnCodeError('当前城市区域未开启，请选择别的城市或区域');
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
            $deliver = D('Deliver_user')->hasUser($data['address_lat'],$data['address_lng']);
            if($deliver){
                $data['deliver_type'] = 1;
            }else{
                $data['deliver_type'] = 2;
            }
        }

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
        $res = D('Service_user_publish')->data($data)->add();
        if($res){
            $giveData['publish_id'] =  $res;

            if ($is_third>0 && $now_category) {
                $giveData['cid'] = $now_category['cid'];
            } else {
                $giveData['cid'] = $_POST['cid'];
            }

            if ($is_third<=0) {
                $giveData['goods_catgory'] =  $_POST['goods_catgory'];
            } elseif($_POST['goods_catgory']) {
                $giveData['goods_catgory'] =  $_POST['goods_catgory'];
            }
            $giveData['weight'] =  $_POST['weight'];

            if ($_POST['img']) {
                $giveData['img'] = $_POST['img'];
            }
            if ($_POST['price']) {
                $giveData['price'] = $_POST['price'];
            }
            if ($_POST['third_price']) {
                $giveData['third_price'] = $_POST['third_price'];
            }
            $giveData['is_third'] = $is_third;
            $giveData['mer_id'] = $mer_id;

            $giveData['start_adress_name'] =  $_POST['start_adress_detail'];
            $giveData['start_adress_lng'] =  $_POST['start_adress_lng'];
            $giveData['start_adress_lat'] =  $_POST['start_adress_lat'];
            $giveData['start_adress_nickname'] =  $_POST['start_adress_name'];
            $giveData['start_adress_phone'] =  str_replace(' ', '', $_POST['start_adress_phone']);

            $giveData['end_adress_name'] =  $_POST['end_adress_detail'];
            $giveData['end_adress_lng'] =  $_POST['end_adress_lng'];
            $giveData['end_adress_lat'] =  $_POST['end_adress_lat'];
            $giveData['end_adress_nickname'] =  $_POST['end_adress_name'];
            $giveData['end_adress_phone'] =  str_replace(' ', '', $_POST['end_adress_phone']);

            $giveData['fetch_time'] =  $_POST['fetch_time'];
            $giveData['arrival_time_info'] = str_replace('  ', ' ', preg_replace('/[^\d\-\:\s]+/u', '', $_POST['fetch_time']));

            $giveData['tip_price'] =  $_POST['tip_price'];
            if ($_POST['remarks']) {
                $giveData['remarks'] = $this->filterEmoji2($_POST['remarks']);
            }


            $giveData['basic_distance_price'] =  $basic_distance_price;
            $giveData['distance_price'] =  $distance_price;
            $giveData['weight_price'] =  $weight_price;

            $giveData['total_price'] =  $total_price;
            $giveData['addtime'] =  time();

            if ($is_third>0 && $_POST['user_id']>0) {
                $giveData['uid'] =  $_POST['user_id'];
            } elseif($is_third<=0) {
                $giveData['uid'] = $this->_uid;
            }

            $give_res = D("Service_user_publish_give")->data($giveData)->add();

            if(!$give_res){
                D('Service_user_publish')->where(array('publish_id'=>$res))->delete();
                $this->returnCodeError(L_('数据添加异常请重试！'));
            }

            $pay_order_param = array(
                'business_type' => 'service',
                'business_id' => $res,
                'order_name' => L_('帮我送订单'),
                'total_money' => $total_price,
                'wx_cheap' => 0,
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
                    $this->returnCodeError('商家余额不足，请先充值！');
                }
                $des = '使用平台配送，商家对应扣除余额';
                $save_result = D('Merchant_money_list')->use_money($mer_id,$total_price,'service', $des ,$res);
                if ($save_result['error_code']) {
                    $this->returnCodeError($save_result['error_msg']);
                }
                $pay_order_param['pay_time'] = time();
                $pay_order_param['pay_money'] = 0;//在线支付的钱
                $pay_order_param['is_mobile_pay'] = $_POST['is_mobile']>0 ? $_POST['is_mobile'] : 0; // 是否是手机支付 0 1 微信 2 手机 3 小程序
                $pay_order_param['paid'] = 1;

                $plat_order_result = D('Plat_order')->add_order($pay_order_param);
                if (!$plat_order_result['error_code']) {
                    $pay_order_param['order_id'] = $plat_order_result['order_id'];
                } elseif ($plat_order_result['error_code']) {
                    $this->returnCodeError($plat_order_result['error_msg']);
                }
                D('Service_order')->after_pay($res,$pay_order_param);
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
                $this->returnCode('0',$returnArr);
            } else {
                $plat_order_result = D('Plat_order')->add_order($pay_order_param);
            }
            if ($plat_order_result['error_code']) {
                $this->returnCodeError($plat_order_result['error_msg']);
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
                $this->returnCode('0',$returnArr);
            }
        }else{
            $this->returnCode('50000006');
        }
    }

    // 帮我买
    public function publish_buy_data(){
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        // 指定地点 计算费用
        if (empty($this->config['service_give_basic_distance']) || empty($this->config['service_give_delivery_fee']) || empty($this->config['service_give_per_km_price'])) {
            $service_basic_distance = floatval($this->config['service_basic_distance']);
            $service_delivery_fee = floatval($this->config['service_delivery_fee']);
            $service_per_km_price = floatval($this->config['service_per_km_price']);
        } else {
            $service_basic_distance = floatval($this->config['service_give_basic_distance']);
            $service_delivery_fee = floatval($this->config['service_give_delivery_fee']);
            $service_per_km_price = floatval($this->config['service_give_per_km_price']);
        }

        // 获取提交的收获地址信息
        if($_POST['buy_type'] == 2){
            $distance = array(
                'start_lat' => $_POST['start_adress_lat'],
                'start_lng' => $_POST['start_adress_lng'],
                'end_lat' =>   $_POST['end_adress_lat'],
                'end_lng' =>   $_POST['end_adress_lng'],
            );
            $distance_info = $this->distance_calculation($distance);
            // 计算服务费用
            $distance_price = 0;
            if (!empty($service_basic_distance) && !empty($service_delivery_fee) && !empty($service_per_km_price)) {
                if ($distance_info < $service_basic_distance) {
                    $distance_price = 0;
                } else {
                    $distance_price = ($distance_info - $service_basic_distance) * $service_per_km_price;
                }
                $distance_price = $this->publish_count_method($distance_price);
            } else {
				fdump($_POST,'buy_data_error_post');
                $this->returnCode('50000004');
            }
            // 计算总费用
            $tip_price = floatval($_POST['tip_price']);
            $total_price = $distance_price + $service_delivery_fee + $tip_price;

            // 指定地点 获得省市区id
            $data['address'] = $_POST['start_adress_detail'];
            $data['address_lng'] = $_POST['start_adress_lng'];
            $data['address_lat'] = $_POST['start_adress_lat'];
            $data['address_area_id'] = $_POST['start_adress_area_id'] ? $_POST['start_adress_area_id'] : 0;
            if ($_POST['start_province_id'] && $_POST['start_city_id'] && $_POST['start_area_id']) {
                $data['province_id'] = $_POST['start_province_id'];
                $data['city_id'] = $_POST['start_city_id'];
                $data['area_id'] = $_POST['start_area_id'];
            } else {
                if (empty($_POST['start_adress_lat']) || empty($_POST['start_adress_lat'])) {
                    exit(json_encode(array('error'=>2,'msg'=>L_('请选择购买地址！'))));
                }
                // 指定地点 获得省市区id
                $area_info = D('Area')->cityMatching($_POST['start_adress_lat'], $_POST['start_adress_lat']);
                $data['province_id'] = $area_info['area_info']['province_id'];
                $data['city_id'] = $area_info['area_info']['city_id'];
                $data['area_id'] = $area_info['area_info']['area_id'];
            }

        }else{
            $data['address'] = $_POST['end_adress_detail'];
            $data['address_lng'] = $_POST['end_adress_lng'];
            $data['address_lat'] = $_POST['end_adress_lat'];
            $data['address_area_id'] = $_POST['end_adress_area_id'] ? $_POST['end_adress_area_id'] : 0;

            if ($_POST['end_province_id'] && $_POST['end_city_id'] && $_POST['end_area_id']) {
                $data['province_id'] = $_POST['end_province_id'];
                $data['city_id'] = $_POST['end_city_id'];
                $data['area_id'] = $_POST['end_area_id'];
            } else {
                if (empty($_POST['end_adress_lat']) || empty($_POST['end_adress_lng'])) {
                    exit(json_encode(array('error'=>2,'msg'=>L_('请选择收货地址！'))));
                }
                // 指定地点 获得省市区id
                $area_info = D('Area')->cityMatching($_POST['end_adress_lat'], $_POST['end_adress_lng']);
                $data['province_id'] = $area_info['area_info']['province_id'];
                $data['city_id'] = $area_info['area_info']['city_id'];
                $data['area_id'] = $area_info['area_info']['area_id'];
            }
            // 计算总费用
            $distance_price = 0;
            $tip_price = floatval($_POST['tip_price']);
            $total_price = $service_delivery_fee + $tip_price;
            $distance_info = 0;
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
            $deliver = D('Deliver_user')->hasUser($data['address_lat'],$data['address_lng']);
            if($deliver){
                $data['deliver_type'] = 1;
            }else{
                $data['deliver_type'] = 2;
            }
        }

        $data['add_time'] = time();
        $data['cid'] = $_POST['cid'];
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
        $res = M('Service_user_publish')->data($data)->add();
        if($res){

            $buyData['publish_id'] =  $res;
            $buyData['goods_remarks'] = $this->filterEmoji2($_POST['goods_remarks']);
            $buyData['buy_type'] =  $_POST['buy_type'];
            $buyData['address'] =  $_POST['start_adress_detail'];
            $buyData['address_lng'] =  $_POST['start_adress_lng'];
            $buyData['address_lat'] =  $_POST['start_adress_lat'];

            // $buyData['adress_id'] =  $_POST['adress_id'];

            $buyData['end_adress_name'] =  $_POST['end_adress_detail'];
            $buyData['end_adress_lng'] =  $_POST['end_adress_lng'];
            $buyData['end_adress_lat'] =  $_POST['end_adress_lat'];
            $buyData['end_adress_nickname'] =  $_POST['end_adress_name'];
            $buyData['end_adress_phone'] =  str_replace(' ', '', $_POST['end_adress_phone']);



            $buyData['arrival_time'] =  $_POST['arrival_time'];
            if ($this->config['service_buy_deliver_times_switch'] == 1) {
                if ($_POST['time_info']) {
                    $time = strtotime($_POST['time_info']);
                    $arrival_time = ceil(($time - time()) / 60);
                    if ($arrival_time > 0) {
                        $buyData['arrival_time'] =  $arrival_time;
                    }
                    $buyData['arrival_time_info'] =  $_POST['time_info'];
                } elseif (intval($buyData['arrival_time'])>0) {
                    $arrival_time_info = time() + intval($buyData['arrival_time']) * 60;
                    if ($arrival_time_info) {
                        $buyData['arrival_time_info'] = date('Y-m-d H:i:s', $arrival_time_info);
                    }
                }
            } elseif (intval($buyData['arrival_time'])>0) {
                $arrival_time_info = time() + intval($buyData['arrival_time']) * 60;
                if ($arrival_time_info) {
                    $buyData['arrival_time_info'] = date('Y-m-d H:i:s', $arrival_time_info);
                }
            }
            $buyData['estimate_goods_price'] = $_POST['estimate_goods_price'];
            $buyData['basic_distance_price'] =  $service_delivery_fee;
            $buyData['distance_price'] =  $distance_price;
            $buyData['tip_price'] =  $tip_price;
            $buyData['total_price'] =  $total_price;
            $buyData['img'] =  $_POST['img'];
            $buy_res = D('Service_user_publish_buy')->data($buyData)->add();
            if(!$buy_res){
				fdump_sql(D('Service_user_publish_buy')->getLastSql(),'Service_user_publish_buy_add_err');
                D('Service_user_publish')->where(array('publish_id'=>$res))->delete();
                $this->returnCodeError(L_('数据添加异常请重试！'));
            }

            $pay_order_param = array(
                'business_type' => 'service',
                'business_id' => $res,
                'order_name' => L_('帮我买订单'),
                'uid' => $this->_uid,
                'total_money' => $total_price,
                'wx_cheap' => 0,
            );
            $plat_order_result = D('Plat_order')->add_order($pay_order_param);
            if ($plat_order_result['error_code']) {
				fdump_sql($plat_order_result,'service_buy_plat_add_order_err');
                $this->returnCodeError($plat_order_result['error_msg']);
            } else {
                $returnArr = array(
                    'msg' => '需求已发布,请去支付!',
                    'order_id' => $plat_order_result['order_id'],
                    'type' => 'plat'
                );
                $this->returnCode('0',$returnArr);
            }
        }else{
            $this->returnCode('50000006');
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
                $this->returnCodeError('数据校验失败');
            }
            $merchant = M('Merchant')->where(array('appid' => $appid, 'key' => $app_key))->find();
            if (empty($merchant)) {
                $this->returnCodeError('平台配置信息错误');
            }
        }

        if ($lng > 0 && $lat > 0) {
            $area_info = D('Area')->cityMatching($lat, $lng);
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
            $adress_info = D("User_adress")->get_adress($this->_uid,$address_id);
            $data['province_id'] = $adress_info['province'];
            $data['city_id'] = $adress_info['city'];
            $data['area_id'] = $adress_info['area'];
        }
        if ($type == 2 && $this->config['service_buy_deliver_times_switch'] != 1) {
            $publish_config =  array();
            if ($data) {
                $publish_config = D('Deliver_service_set')->address_publish_config($data);
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
            $time_info['config_time'] = $config_time;
            $time_info['daysList'] = array();
            $time_info['timeList'] = array();
        } else {
            // 获取对应时间信息

            $time_info = D('Deliver_service_set')->publish_time_info($type, $data);
        }
        $this->returnCode('0',$time_info);
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
            $this->returnCodeError('数据校验失败');
        }
        $merchant = M('Merchant')->where(array('appid' => $appid, 'key' => $app_key))->find();
        if (empty($merchant)) {
            $this->returnCodeError('平台配置信息错误');
        }
        if (!$_POST['publish_id'] && $_POST['publish_id']<=0 && !$_POST['third_id'] && $_POST['third_id']<=0) {
            $this->returnCodeError('请传值订单');
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
        $this->returnCode('0',$service_user_publish_give);
    }

    //--story=1002202 --user=卢敏 跑腿售卖商品自定义--接口 https://www.tapd.cn/47145533/s/1146182
    public function auto_cate_goods(){
        $all_category = M('Service_auto_category')->field("id,name,type")->where(['is_del'=>0, 'status'=> 1])->order("sort desc")->select();
       

        foreach ($all_category as $key => $value) {
            if ($value['type'] == 0) {
                $all_category[$key]['goods'] = M("Service_auto_goods")->where("is_del=0 and cid=".$value['id'])->field("id, name")->order("sort desc,id asc")->select();
            }else{
                // 自定义店铺
                $result = D('Service_auto_category')->getAutoStoreByCid($value['id']);
                $all_category[$key]['goods'] = $result['store_list'];
            }

        }
        $this->returnCode('0',$all_category);
    }

}
?>