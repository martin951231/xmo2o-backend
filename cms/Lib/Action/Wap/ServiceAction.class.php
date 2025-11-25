<?php

/*
 * 个人需求发布
 *
 */

class ServiceAction extends BaseAction {
    // 防止重复操作退款
    protected $user_order_refund;
	//首页
    public function index(){
        // 轮播
        $service_index_lunbo = D('Adver')->get_adver_by_key('service_index_lunbo',5);
        $this->assign('service_index_lunbo',$service_index_lunbo);

    	$catListBak = D('Service_category')->where(array('cat_status'=>1))->order('cat_sort desc,cid asc')->select();
    	$catList = array();
    	foreach ($catListBak as $key => $value) {
    		if($value['fcid'] == 0){
    			$catList [$value['cid']]= $value;
    		}
    	}
    	foreach ($catListBak as $k => $v) {
    		if($v['fcid'] != 0){
    			$catList [$v['fcid']]['catList'][$v['cid']]= $v;
    		}
    	}
		$this->assign('catList',$catList);

        //顶部广告
        $wap_index_top_adver = D('Adver')->get_adver_by_key('wap_index_top',5);
        $this->assign('wap_index_top_adver',$wap_index_top_adver);

        $this->display();
    }

    // 用户发布需求详情
    public function publish_detail(){

        $long_lat = D('User_long_lat')->getLocation($_SESSION['openid'],0);
        if ($this->config['now_city']>0) {
            $now_city = M('Area')->where(array('area_id' => $this->config['now_city']))->field('area_lng, area_lat')->find();
            if ((!$long_lat || !$long_lat['long'] || !$long_lat['lat']) && $now_city) {
                $long_lat = array(
                    'long' => $now_city['area_lng'],
                    'lat' => $now_city['area_lat'],
                    'dateline' => time(),
                );
            }
        }
        if (empty($long_lat)) {
            $long_lat = array(
                'long' => 0,
                'lat' => 0,
            );
        }
        $this->assign('long_lat',$long_lat);
        //得到所有城市并以城市首拼排序
        $database_area = D('Area');
        $all_city = S('all_city_address');
        if(empty($all_city)){
            $database_field = '`area_id`,`area_name`,`area_url`,`first_pinyin`,`is_hot`';
            $condition_all_city['area_type'] = 2;
            $all_city_old = $database_area->field($database_field)->where($condition_all_city)->order('`first_pinyin` ASC,`area_id` ASC')->select();
            foreach($all_city_old as $key=>$value){
                //首拼转成大写
                if(!empty($value['first_pinyin'])){
                    $first_pinyin = strtoupper($value['first_pinyin']);
                    $all_city[$first_pinyin][] = $value;
                }
            }
            S('all_city_address',$all_city);
        }
        $this->assign('all_city',$all_city);

        if (empty($_GET['cid']) && !empty($_GET['type'])) {
            $this->redirect('Service/index');
        }

        if(empty($this->user_session)){
			if($this->is_app_browser){
				$location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
				$this->error_tips('请先进行登录！',U('Login/index',$location_param));
			}else{
				$location_param['referer'] = urlencode($_SERVER['REQUEST_URI']);
				redirect(U('Login/index',$location_param));
			}
		}
		
        if($this->config['user_is_real_name'] == 1){
            $user_authentication = D("User_authentication")->where(array('uid'=>$this->user_session['uid']))->field('authentication_id,authentication_status,uid')->find();
            if(empty($user_authentication)){
                $this->error_tips('请先实名认证再进行发布',U('My/authentication'));
            } elseif($user_authentication['authentication_status'] != 1){
                $this->redirect(U('My/authentication_index'));
            }
        }

        $database_Service_category = D('Service_category');
        $condition_now_Service_category['cid'] = intval($_GET['cid']);
        $now_category = $database_Service_category->field(true)->where($condition_now_Service_category)->find();
        if (empty($now_category)) {
            $this->error_tips('没有找到该分类信息！');
        }
        if (!empty($now_category['cat_field'])) {
            $now_category['cat_field'] = unserialize($now_category['cat_field']);
        }else if($now_category['type'] == 1){
            $this->error_tips('后台暂未完善此分类，无法发布。');
        }
        $cat_field_count = count($now_category['cat_field']);

        $this->assign('now_category', $now_category);
        $this->assign('cat_field_count', $cat_field_count);

        $database_area = D('Area');
        $now_city = $database_area->get_area_by_areaId($this->config['now_city']);
        if(!$now_city['area_lng']){
            $now_city['area_lng'] = '116.403847';
        }
        if(!$now_city['area_lat']){
            $now_city['area_lat'] = '39.915526';
        }
        if (!$now_city['area_name']) {
            $now_city['area_name'] = '北京';
        }
        $this->assign('now_city',$now_city);

        $adress_list = D('User_adress')->get_adress_list($this->user_session['uid']);
        foreach ($adress_list as $key => $value) {
            if($value['adress_id'] == $_GET['adress_id']){
                $addressInfo = $value;
            }elseif($value['default'] == 1){
                $addressInfo = $value;
            }
        }
        $this->assign('addressInfo', $addressInfo);
        $this->assign('adress_list', $adress_list);

        $address_msg = array();
        $publish_config = array();
        if ($addressInfo) {
            $address_msg = array(
                'province_id' =>  $addressInfo['province'],
                'city_id' =>  $addressInfo['city'],
                'area_id' =>  $addressInfo['area'],
            );
        }
        if ($address_msg) {
            $publish_config = D('Deliver_service_set')->address_publish_config($address_msg);
        }
        // 如果未开启 帮我买 分段时间配送配置
        if ($now_category['type'] == 2 && $this->config['service_buy_deliver_times_switch'] != 1) {
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
            $this->assign('config_time',$config_time);

            $this->assign('daysList',array());
            $this->assign('timeList',array());
        } else {
            // 获取对应时间信息
            if ($now_category['type'] == 2) {
                $time_info = D('Deliver_service_set')->publish_time_info($now_category['type'], $address_msg);
            } else {
                $time_info = $this->publish_time_info($now_category['type']);
            }
            if (empty($time_info['timeList'])) {
                $time_info['time_is_empty'] = true;
            } else {
                $time_info['time_is_empty'] = false;
            }
            $this->assign($time_info);
        }


        if($now_category['type'] == 2){
			
			redirect($this->config['site_url'] . D('House_village')->base_url . 'pages/runErrands/index');
			
            if($_GET['adress_id']){
                $temp_buy_data = $_SESSION['temp_buy_data'];

                $temp_buy_data['img'] = array_filter(explode(';', $temp_buy_data['img']));
                $this->assign('temp_buy_data',$temp_buy_data);
            }else{
                $_SESSION['temp_buy_data'] = '';
            }
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
                    $this->assign('service_weight_info',$service_buy_weight_info);
                }
            }

            // dump($temp_buy_data);
            $this->display('publish_detail_buy');
        }elseif ($now_category['type'] == 3) {
			
			redirect($this->config['site_url'] . D('House_village')->base_url . 'pages/runErrands/index');
			
            // temp_give_data
            // $temp_give_data = $_SESSION['temp_give_data'];
            // $temp_give_data['img'] = array_filter(explode(';', $temp_give_data['img']));
            // $this->assign('temp_give_data',$temp_give_data);

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
                    $this->assign('service_weight_info',$service_give_weight_info);
                }
            }
            if($_GET['adress_id']){
                $temp_give_data = $_SESSION['temp_give_data'];
                $temp_give_data['img'] = array_filter(explode(';', $temp_give_data['img']));
                $this->assign('temp_give_data',$temp_give_data);
            }else{
                $_SESSION['temp_give_data'] = '';
            }
            $this->display('publish_detail_give');
        }else{
            $this->display();
        }
    }


    /*处理服务快派时间问题*/
    public function publish_time_info($type = 2) {
        // 判断是否有今天数据
        $weekarray=array("日","一","二","三","四","五","六");

        // 统一默认获取一小时后的时间 即为配送最早为一小时后（如果有配置去配置时间)
        if (2 == $type) {
            // 帮我买
            // 配送时长
            $service_deliver_send_time = intval(C('config.service_buy_deliver_send_time'));
            // 可预约时间
            $service_days_number = intval($this->config['service_buy_days_number']);

            // 配送时间段一
            $delivery_times = explode('-', $this->config['service_buy_delivery_time']);
            // 配送时间段一 起步距离
            $service_basic_distance = $this->config['service_buy_basic_distance'];
            // 配送时间段一 起步配送费
            $service_delivery_fee = $this->config['service_buy_delivery_fee'];
            // 配送时间段一 每公里的配送费
            $service_per_km_price = $this->config['service_buy_per_km_price'];

            // 配送时间段二
            $delivery_times2 = explode('-', $this->config['service_buy_delivery_time2']);
            // 配送时间段二 起步距离
            $service_basic_distance2 = $this->config['service_buy_basic_distance2'];
            // 配送时间段二 起步配送费
            $service_delivery_fee2 = $this->config['service_buy_delivery_fee2'];
            // 配送时间段二 每公里的配送费
            $service_per_km_price2 = $this->config['service_buy_per_km_price2'];

        } elseif (3 == $type) {
            // 帮我送
            // 配送时长
            $service_deliver_send_time = intval(C('config.service_deliver_send_time'));
            // 可预约时间
            $service_days_number = intval($this->config['service_days_number']);

            // 配送时间段一
            $delivery_times = explode('-', $this->config['service_delivery_time']);
            // 配送时间段一 起步距离
            $service_basic_distance = $this->config['service_basic_distance'];
            // 配送时间段一 起步配送费
            $service_delivery_fee = $this->config['service_delivery_fee'];
            // 配送时间段一 每公里的配送费
            $service_per_km_price = $this->config['service_per_km_price'];

            // 配送时间段二
            $delivery_times2 = explode('-', $this->config['service_delivery_time2']);
            // 配送时间段二 起步距离
            $service_basic_distance2 = $this->config['service_basic_distance2'];
            // 配送时间段二 起步配送费
            $service_delivery_fee2 = $this->config['service_delivery_fee2'];
            // 配送时间段二 每公里的配送费
            $service_per_km_price2 = $this->config['service_per_km_price2'];
        } else {
            return false;
        }
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

            for ($d=0; $d <= $service_days_number; $d++) {
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

                                if ($i != $end_time1 || intval($s."0") <= $delivery_endTime_minute1) {
                                    if ($i >= 24) {
                                        $hour = $i - 24;
                                        $day = $d + 1;
                                        $is_add_day = 1;
                                    } else {
                                        $hour = $i;
                                        $day = $d;
                                    }
                                    if ($day == 0) {
                                        $time[$day][$hour][$s]['week'] = "今天";
                                        $is_today = 1;
                                    } else if ($day == 1) {
                                        $time[$day][$hour][$s]['week'] = "明天";
                                    }
                                    $time[$day][$hour][$s]['time'] = $hour . ":" . $minute_info;
                                    $time[$day][$hour][$s]['basic_distance'] = $service_basic_distance;
                                    $time[$day][$hour][$s]['delivery_fee'] = $service_delivery_fee;
                                    $time[$day][$hour][$s]['per_km_price'] = $service_per_km_price;
                                    $time[$day][$hour][$s]['date'] = date("Y-m-d", strtotime("+" . $day . " day"));
                                }
                            }
                        }
                    }else{
                        for ($s=0; $s <= 5; $s++) {
                            if ($i == $start_time1 && intval($s."0") < $delivery_startTime_minute1) {
                                continue;
                            }
                            if ($i != $end_time1 || intval($s."0") <= $delivery_endTime_minute1) {
                                if ($i >= 24) {
                                    $hour = $i - 24;
                                    $day = $d + 1;
                                    $is_add_day = 1;
                                } else {
                                    $hour = $i;
                                    $day = $d;
                                }
                                $time[$day][$hour][$s]['time'] = $hour . ":" . $s . "0";
                                $time[$day][$hour][$s]['basic_distance'] = $service_basic_distance;
                                $time[$day][$hour][$s]['delivery_fee'] = $service_delivery_fee;
                                $time[$day][$hour][$s]['per_km_price'] = $service_per_km_price;
                                if ($day == 1) {
                                    $time[$day][$hour][$s]['week'] = "明天";
                                } else if ($day == 2) {
                                    $time[$day][$hour][$s]['week'] = "后天";
                                } else {
                                    $time[$day][$hour][$s]['week'] = "周" . $weekarray[date("w", strtotime("+" . $day . " day"))];
                                }
                                $time[$day][$hour][$s]['date'] = date("Y-m-d", strtotime("+" . $day . " day"));
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

            for ($d=0; $d <= $service_days_number; $d++) {
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
                                        $time[$day][$hour][$s]['week'] = "今天";
                                        $is_today = 1;
                                    } else if ($day == 1) {
                                        $time[$day][$hour][$s]['week'] = "明天";
                                    }
                                    $time[$day][$hour][$s]['time'] = $hour.":".$minute_info;
                                    $time[$day][$hour][$s]['basic_distance'] = $service_basic_distance2;
                                    $time[$day][$hour][$s]['delivery_fee'] = $service_delivery_fee2;
                                    $time[$day][$hour][$s]['per_km_price'] = $service_per_km_price2;
                                    $time[$day][$hour][$s]['date'] = date("Y-m-d",strtotime("+".$day." day"));
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
                                $time[$day][$hour][$s]['basic_distance'] = $service_basic_distance2;
                                $time[$day][$hour][$s]['delivery_fee'] = $service_delivery_fee2;
                                $time[$day][$hour][$s]['per_km_price'] = $service_per_km_price2;
                                if ($day == 1) {
                                    $time[$day][$hour][$s]['week'] = "明天";
                                } else if ($day == 2) {
                                    $time[$day][$hour][$s]['week'] = "后天";
                                } else {
                                    $time[$day][$hour][$s]['week'] = "周" . $weekarray[date("w", strtotime("+" . $day . " day"))];
                                }

                                $time[$day][$hour][$s]['date'] = date("Y-m-d", strtotime("+" . $day . " day"));
                            }
                        }
                    }

                }
            }
        }
        //客户需求为上线功能 可删除
// $delivery_times = explode('-', $this->config['service_delivery_time']);
// $delivery_times2 = explode('-', $this->config['service_delivery_time2']);
        // string(5) "08:20"
// $aa = explode(':', $delivery_times[0]);
// dump($aa);
// if((date("H")<=intval($delivery_times[0]) && date("H")>= intval($delivery_times[1])) || (date("H")<=intval($delivery_times2[0]) && date("H")>= intval($delivery_times2[1]))){
//     $this->error_tips('我们的配送员下班了',U('My/authentication'));
// }
        $timeList = array();
        foreach ($time as $key => $value) {
            ksort($value);
            if(1 != $is_today) {
                $time_key =  $key - 1;
            } else {
                $time_key = $key;
            }
            foreach ($value as $kk => &$vv) {
                foreach ($vv as $kkk => $vvv) {
                    $timeList[$time_key][] = $vvv;
                }
            }
        }


        // $weekarray=array("日","一","二","三","四","五","六");
        for ($d=0; $d <= intval($this->config['service_days_number'])+intval($is_add_day); $d++) {
            if($d == 0){
                if (1 == $is_today) {
                    $daysList[] =  "今天";
                }
            }else if($d==1){
                $daysList[] =  "明天";
            }else if($d==2){
                $daysList[] =  "后天";
            }else{
                $daysList[] =  date("Y-m-d",strtotime("+".$d." day"));
            }

        }
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
            $config_time['service_basic_distance'] = $service_basic_distance;
            $config_time['service_delivery_fee'] = $service_delivery_fee;
            $config_time['service_per_km_price'] = $service_per_km_price;
        } elseif ($now_time >= $start_time_info2 && $now_time <= $end_time_info2) {
            $config_time['service_basic_distance'] = $service_basic_distance2;
            $config_time['service_delivery_fee'] = $service_delivery_fee2;
            $config_time['service_per_km_price'] = $service_per_km_price2;
        } else {
            $config_time['service_basic_distance'] = 0;
            $config_time['service_delivery_fee'] = 0;
            $config_time['service_per_km_price'] = 0;
        }
        $time_info = array(
            'config_time' => $config_time,
            'daysList' => $daysList,
            'timeList' => $timeList
        );
        return $time_info;
    }


    public function publish_buy_data(){

//        $userInfo = D('User')->where(array('uid'=>$this->user_session['uid']))->field('now_money')->find();
        $data = array();
        if($_POST['buy_type'] == 2){
            if (empty($_POST['start_adress_lat']) || empty($_POST['start_adress_lng'])) {
                exit(json_encode(array('error'=>2,'msg'=>'请选择购买地址！')));
            }
            $data['address'] = $_POST['start_adress_detail'];
            $data['address_lng'] = $_POST['start_adress_lng'];
            $data['address_lat'] = $_POST['start_adress_lat'];
            $data['address_area_id'] = $_POST['start_adress_area_id'];

            $area_info = D('Area')->cityMatching($_POST['start_adress_lat'], $_POST['start_adress_lng']);
            if ($area_info['area_info']['province_id']) {
                $data['province_id'] = $area_info['area_info']['province_id'];
            } else {
                $data['province_id'] = $_POST['start_province_id'];
            }
            if ($area_info['area_info']['city_id'] && $area_info['area_info']['city_id'] > $data['province_id']) {
                $data['city_id'] = $area_info['area_info']['city_id'];
            } else {
                $data['city_id'] = $_POST['start_city_id'];
            }
            if ($area_info['area_info']['area_id'] && $area_info['area_info']['area_id'] > 0) {
                $data['area_id'] = $area_info['area_info']['area_id'];
            } else {
                $data['area_id'] = $_POST['start_area_id'];
            }
//            if ($_POST['start_province_id'] && $_POST['start_city_id'] && $_POST['start_area_id']) {
//                $data['province_id'] = $_POST['start_province_id'];
//                $data['city_id'] = $_POST['start_city_id'];
//                $data['area_id'] = $_POST['start_area_id'];
//            } else {
//                if (empty($_POST['start_adress_lat']) || empty($_POST['start_adress_lat'])) {
//                    exit(json_encode(array('error'=>2,'msg'=>'请选择购买地址！')));
//                }
//                // 指定地点 获得省市区id
//                $area_info = D('Area')->cityMatching($_POST['start_adress_lat'], $_POST['start_adress_lng']);
//                $data['province_id'] = $area_info['area_info']['province_id'];
//                $data['city_id'] = $area_info['area_info']['city_id'];
//                $data['area_id'] = $area_info['area_info']['area_id'];
//            }

            if ($data['province_id'] <=0 || $data['city_id'] <=0 || $data['area_id'] <=0) {
                fdump_sql($area_info,'publish_service_buy_info');
                fdump_sql($_POST, 'publish_service_buy_info');
            }

            if ($_POST['start_adress_lat'] && $_POST['start_adress_lng'] && $_POST['end_adress_lat'] && $_POST['end_adress_lng']) {
                $destance_sum = $this->compute_distance($_POST['start_adress_lat'], $_POST['start_adress_lng'], $_POST['end_adress_lat'], $_POST['end_adress_lng']);
            } else {
                exit(json_encode(array('error'=>2,'msg'=>'定位信息获取不全！')));
            }
        }else{
            if (empty($_POST['end_adress_lat']) || empty($_POST['end_adress_lng'])) {
                exit(json_encode(array('error'=>2,'msg'=>'请选择收货地址！')));
            }
            $data['address'] = $_POST['end_adress_detail'];
            $data['address_lng'] = $_POST['end_adress_lng'];
            $data['address_lat'] = $_POST['end_adress_lat'];
            $data['address_area_id'] = $_POST['end_adress_area_id'];


            // 就近购买 获得省市区id
            $area_info = D('Area')->cityMatching($_POST['end_adress_lat'], $_POST['end_adress_lng']);
            if ($area_info['area_info']['province_id'] && $area_info['area_info']['province_id'] > 0) {
                $data['province_id'] = $area_info['area_info']['province_id'];
            } else {
                $data['province_id'] = $_POST['end_province_id'];
            }
            if ($area_info['area_info'] && $area_info['area_info']['city_id'] > 0) {
                $data['city_id'] = $area_info['area_info']['city_id'];
            } else {
                $data['city_id'] = $_POST['end_city_id'];
            }
            if ($area_info['area_info'] && $area_info['area_info']['area_id'] > 0) {
                $data['area_id'] = $area_info['area_info']['area_id'];
            } else {
                $data['area_id'] = $_POST['end_area_id'];
            }

            if ($data['province_id'] <=0 || $data['city_id'] <=0 || $data['area_id'] <=0) {
                fdump_sql($area_info,'publish_service_buy_info');
                fdump_sql($_POST, 'publish_service_buy_info');
            }

            $destance_sum = 0;
        }
        // 帮我买数据处理
        $buyData = array();
        $buyData['arrival_time'] =  $_POST['arrival_time'];
        if ($this->config['service_buy_deliver_times_switch'] == 1) {
            if ($_POST['time_info']) {
                $time = strtotime($_POST['time_info']);
                $arrival_time = ceil(($time - time()) / 60);
                if ($arrival_time > 0) {
                    $buyData['arrival_time'] =  $arrival_time;
                }
                $buyData['arrival_time_info'] =  $_POST['time_info'];
            } else {
                exit(json_encode(array('error'=>2,'msg'=>'请选择送达时间')));
            }
        }
        // 处理重量费用
        // 判断获取 重量档次信息
        $weight_price = 0;
        $weight_info = '';
        $weight = '';
        if($this->config['service_weight_multilevel_switch'] == 1) {
            $weight_price = round($_POST['weight_price'], 2);
            if ($_POST['weight_price']>=0 && $_POST['start_weight'] >=0 && $_POST['end_weight']>0) {
                $service_buy_weight_info = $this->config['service_buy_weight_info'];
                $service_buy_weight_info = unserialize($service_buy_weight_info);
                if ($service_buy_weight_info) {
                    // start_weight 范围起始公斤数 end_weight 范围结束公斤数 范围价格 weight_price
                    foreach ($service_buy_weight_info as $key => &$val) {
                        if (floatval($val['start_weight'])==floatval($_POST['start_weight']) && floatval($val['end_weight'])==floatval($_POST['end_weight'])) {
                            $val['weight_price'] = round($val['weight_price'], 2);
                            if ($val['weight_price'] != $weight_price) {
                                exit(json_encode(array('error'=>2,'msg'=>'该重量档次价格已更新，请刷新获取最新价格!')));
                            } else {
                                $weight_info = serialize($val);
                                $weight = $val['start_weight'] . 'kg-' . $val['end_weight'] . 'kg';
                            }
                        }
                    }
                    if (!$weight) {
                        exit(json_encode(array('error'=>2,'msg'=>'物品重量信息有误！请刷新重试')));
                    }
                }
            } else {
                exit(json_encode(array('error'=>2,'msg'=>'请选择物品重量')));
            }
        }

        // 获取收货地址信息
        $publish_config = D('Deliver_service_set')->location_publish_config($_POST['end_adress_lat'], $_POST['end_adress_lng']);


        if (!empty($_POST['time_info'])) {
            // 帮我买 获取选择送达时间的各个配置
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
                $basic_distance_price = $service_delivery_fee;
                $tip_price = $_POST['tip_price'] ? $_POST['tip_price'] : 0;
                $total = $distance_price + $basic_distance_price + $weight_price + $tip_price;
                $total_price = round($total, 2);

                if ($total_price < $service_delivery_fee) {
                    exit(json_encode(array('error'=>2,'msg'=>'费用计算错误，请重试！')));
                }
            } elseif ($delivery_times2_start && $delivery_times2_end && (($time_info_tip >= $delivery_times2_start && $time_info_tip <= $delivery_times2_end) || $delivery_times2_start == $delivery_times2_end)) {
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
                $basic_distance_price = $service_delivery_fee2;
                $tip_price = $_POST['tip_price'] ? $_POST['tip_price'] : 0;
                $total = $distance_price + $basic_distance_price + $weight_price + $tip_price;
                $total_price = round($total, 2);

                if ($total_price < $service_delivery_fee2) {
                    exit(json_encode(array('error'=>2,'msg'=>'费用计算错误，请重试！')));
                }
            } else {
                exit(json_encode(array('error'=>2,'msg'=>'所选时间配送员已经休息！')));
            }
            $_POST['distance_price'] = $distance_price;
            $_POST['tip_price'] = $tip_price;
            $_POST['total_price'] = $total_price;
            $_POST['basic_distance_price'] = $basic_distance_price;
        } else {

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
            $tip_price = $_POST['tip_price'] ? $_POST['tip_price'] : 0;
            $total = $distance_price + $basic_distance_price + $weight_price + $tip_price;
            $total_price = round($total, 2);

            if ($total_price < $service_delivery_fee) {
                exit(json_encode(array('error'=>2,'msg'=>'费用计算错误，请重试！')));
            }
            $_POST['distance_price'] = $distance_price;
            $_POST['tip_price'] = $tip_price;
            $_POST['total_price'] = $total_price;
            $_POST['basic_distance_price'] = $basic_distance_price;
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
        $data['uid'] = $this->user_session['uid'];
        $data['status'] = 1;
        $data['catgory_type'] = 2;
        $data['destance_sum'] = $_POST['destance_sum'];
        $nowtime = date("mdHis");
        // $data['order_sn'] = $nowtime.sprintf("%04d",$this->user_session['uid']);
        $data['order_sn'] = $nowtime.rand(10,99).sprintf("%08d",$this->user_session['uid']);
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
            $buyData['end_adress_phone'] =  $_POST['end_adress_phone'];

            if ($_POST['estimate_goods_price']) {
                $buyData['estimate_goods_price'] =  $_POST['estimate_goods_price'];
            }

            $buyData['basic_distance_price'] =  $_POST['basic_distance_price'];
            $buyData['distance_price'] =  $_POST['distance_price'];
            $buyData['tip_price'] =  $_POST['tip_price'];
            $buyData['total_price'] =  $_POST['total_price'];
            $buyData['img'] = $_POST['img'];
            $buyData['weight_price'] =  $weight_price;
            $buyData['weight_info'] =  $weight_info;
            $buyData['weight'] = $weight;
            $buy_res = D('Service_user_publish_buy')->data($buyData)->add();
            if(!$buy_res){
                D('Service_user_publish')->where(array('publish_id'=>$res))->delete();
                exit(json_encode(array('error'=>2,'msg'=>'数据添加异常请重试！')));
            }

            $pay_order_param = array(
                'business_type' => 'service',
                'business_id' => $res,
                'order_name' => '帮我买订单',
                'uid' => $this->user_session['uid'],
                'total_money' => $_POST['total_price'],
                'wx_cheap' => 0,
            );
            $plat_order_result = D('Plat_order')->add_order($pay_order_param);
            if ($plat_order_result['error_code']) {
                $this->error_tips($plat_order_result['error_msg']);
            } else {
//                redirect(U('Pay/check', array('order_id' => $plat_order_result['order_id'], 'type' => 'plat')));
                exit(json_encode(array('error'=>1,'msg'=>'需求已发布,请去支付!','order_id'=>$plat_order_result['order_id'])));
                exit(json_encode(array('error'=>1,'msg'=>'需求已发布,请去支付!','order_id'=>$res,'url'=>U('Pay/check', array('order_id' => $plat_order_result['order_id'], 'type' => 'plat')))));
            }
        }else{
            exit(json_encode(array('error'=>2,'msg'=>'发布失败请重试！')));
        }


    }

    public function temp_buy_data(){
        $_SESSION['temp_buy_data'] = $_POST;
        exit(json_encode(array('error'=>1,'msg'=>'存储成功')));
    }

    public function publish_give_data(){

        if(IS_POST){
            // $_POST['img'] = array_filter(explode(';', $_POST['img']));
            // $userInfo = D('User')->where(array('uid'=>$this->user_session['uid']))->field('now_money')->find();

            if (empty($_POST['start_adress_lat']) || empty($_POST['start_adress_lng'])) {
                exit(json_encode(array('error'=>2,'msg'=>'请选择取货地址！')));
            }
            $data['address'] = $_POST['start_adress_detail'];
            $data['address_lng'] = $_POST['start_adress_lng'];
            $data['address_lat'] = $_POST['start_adress_lat'];
            $data['address_area_id'] = $_POST['start_adress_area_id'];


            $area_info = D('Area')->cityMatching($_POST['start_adress_lat'], $_POST['start_adress_lng']);
            if ($area_info['area_info']['province_id']) {
                $data['province_id'] = $area_info['area_info']['province_id'];
            } else {
                $data['province_id'] = $_POST['start_province_id'];
            }
            if ($area_info['area_info']['city_id'] && $area_info['area_info']['city_id'] > $data['province_id']) {
                $data['city_id'] = $area_info['area_info']['city_id'];
            } else {
                $data['city_id'] = $_POST['start_city_id'];
            }
            if ($area_info['area_info']['area_id'] && $area_info['area_info']['area_id'] > $data['city_id']) {
                $data['area_id'] = $area_info['area_info']['area_id'];
            } else {
                $data['area_id'] = $_POST['start_area_id'];
            }

//            if ($_POST['start_province_id'] && $_POST['start_city_id'] && $_POST['start_area_id']) {
//                $data['province_id'] = $_POST['start_province_id'];
//                $data['city_id'] = $_POST['start_city_id'];
//                $data['area_id'] = $_POST['start_area_id'];
//            } else {
//                if (empty($_POST['start_adress_lat']) || empty($_POST['start_adress_lng'])) {
//                    exit(json_encode(array('error'=>2,'msg'=>'请选择取货地址！')));
//                }
//                // 指定地点 获得省市区id
//                $area_info = D('Area')->cityMatching($_POST['start_adress_lat'], $_POST['start_adress_lng']);
//                $data['province_id'] = $area_info['area_info']['province_id'];
//                $data['city_id'] = $area_info['area_info']['city_id'];
//                $data['area_id'] = $area_info['area_info']['area_id'];
//            }
            if (!$data['province_id'] || !$data['city_id'] || !$data['area_id']) {
                fdump_sql($area_info,'publish_service_give_info');
                fdump_sql($_POST, 'publish_service_give_info');
            }
            if ($_POST['start_adress_lat'] && $_POST['start_adress_lng'] && $_POST['end_adress_lat'] && $_POST['end_adress_lng']) {
                $destance_sum = $this->compute_distance($_POST['start_adress_lat'], $_POST['start_adress_lng'], $_POST['end_adress_lat'], $_POST['end_adress_lng']);
            } else {
                exit(json_encode(array('error'=>2,'msg'=>'定位信息获取不全！')));
            }


            // 处理重量费用
            // 判断获取 重量档次信息
            $weight_price = 0;
            $weight_info = '';
            $weight = '';
            if($this->config['service_weight_multilevel_switch'] == 1) {
                $weight_price = round($_POST['weight_price'],2);
                if ($_POST['weight_price']>=0 && $_POST['start_weight'] >=0 && $_POST['end_weight']>0) {
                    $service_give_weight_info = $this->config['service_give_weight_info'];
                    $service_give_weight_info = unserialize($service_give_weight_info);
                    if ($service_give_weight_info) {
                        // start_weight 范围起始公斤数 end_weight 范围结束公斤数 范围价格 weight_price
                        foreach ($service_give_weight_info as $key => &$val) {
                            if (floatval($val['start_weight'])==floatval($_POST['start_weight']) && floatval($val['end_weight'])==floatval($_POST['end_weight'])) {
                                $val['weight_price'] = round($val['weight_price'],2);
                                if ($val['weight_price'] != $weight_price) {
                                    exit(json_encode(array('error'=>2,'msg'=>'该重量档次价格已更新，请刷新获取最新价格!')));
                                } else {
                                    $weight_info = serialize($val);
                                    $weight = $val['start_weight'] . 'kg-' . $val['end_weight'] . 'kg';
                                }
                            }
                        }
                        if (!$weight) {
                            exit(json_encode(array('error'=>2,'msg'=>'物品重量信息有误！请刷新重试')));
                        }
                    }
                } else {
                    exit(json_encode(array('error'=>2,'msg'=>'请选择物品重量')));
                }
            }

            // 帮我买 获取选择送达时间的各个配置
            if (!empty($_POST['time_info'])) {
                // 获取收货地址信息
                $publish_config = D('Deliver_service_set')->location_publish_config($_POST['end_adress_lat'], $_POST['end_adress_lng']);

                $time_info = explode(' ', trim($_POST['time_info']));

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
                    $tip_price = $_POST['tip_price'] ? $_POST['tip_price'] : 0;
                    $total = $distance_price + $basic_distance_price + $weight_price + $tip_price;
                    $total_price = round($total, 2);

                    if ($total_price < $service_delivery_fee) {
                        exit(json_encode(array('error'=>2,'msg'=>'费用计算错误，请重试！')));
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
                    $tip_price = $_POST['tip_price'] ? $_POST['tip_price'] : 0;
                    $total = $distance_price + $basic_distance_price + $weight_price + $tip_price;
                    $total_price = round($total, 2);

                    if ($total_price < $service_delivery_fee2) {
                        exit(json_encode(array('error'=>2,'msg'=>'费用计算错误，请重试！')));
                    }
                } else {
                    exit(json_encode(array('error'=>2,'msg'=>'所选时间配送员已经休息！')));
                }
                $_POST['distance_price'] = $distance_price;
                $_POST['tip_price'] = $tip_price;
                $_POST['total_price'] = $total_price;
                $_POST['basic_distance_price'] = $basic_distance_price;
                $_POST['weight'] = $weight;
                $_POST['weight_price'] = $weight_price;
            } else if (empty($_POST['time_info']) && empty($_POST['fetch_time'])){
                exit(json_encode(array('error'=>2,'msg'=>'请选择取件时间！')));
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
            $data['uid'] = $this->user_session['uid'];
            $data['catgory_type'] = 3;
            $data['status'] = 1;
            $data['destance_sum'] = $destance_sum ? $destance_sum : $_POST['destance_sum'];
			
			if(empty($data['destance_sum']) || $data['destance_sum'] == '0.00'){
				exit(json_encode(array('error'=>2,'msg'=>'距离计算失败，或没有配送距离，无法提交。请重试')));
			}
			
            $nowtime = date("mdHis");
            // $data['order_sn'] = $nowtime.sprintf("%04d",$this->user_session['uid']);
            $data['order_sn'] = $nowtime.rand(10,99).sprintf("%08d",$this->user_session['uid']);
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
                if ($_POST['estimate_goods_price']) {
                    $giveData['estimate_goods_price'] =  $_POST['estimate_goods_price'];
                }

                $giveData['tip_price'] =  $_POST['tip_price'];
                $giveData['remarks'] = $this->filterEmoji2($_POST['remarks']);

                $giveData['basic_distance_price'] =  $_POST['basic_distance_price'];
                $giveData['distance_price'] =  $_POST['distance_price'];
                $giveData['weight_price'] =  $_POST['weight_price'];
                $giveData['weight_info'] =  $weight_info;

                $giveData['total_price'] =  $_POST['total_price'];
                $giveData['addtime'] =  time();
                $giveData['uid'] =  $this->user_session['uid'];
                $give_res = D("Service_user_publish_give")->data($giveData)->add();

                if(!$give_res){
                    D('Service_user_publish')->where(array('publish_id'=>$res))->delete();
                    exit(json_encode(array('error'=>2,'msg'=>'数据添加异常请重试！')));
                }


                $pay_order_param = array(
                    'business_type' => 'service',
                    'business_id' => $res,
                    'order_name' => '帮我送订单',
                    'uid' => $this->user_session['uid'],
                    'total_money' => $_POST['total_price'],
                    'wx_cheap' => 0,
                );
                $plat_order_result = D('Plat_order')->add_order($pay_order_param);
                if ($plat_order_result['error_code']) {
                    $this->error_tips($plat_order_result['error_msg']);
                } else {
//                redirect(U('Pay/check', array('order_id' => $plat_order_result['order_id'], 'type' => 'plat')));
					exit(json_encode(array('error'=>1,'msg'=>'需求已发布,请去支付!','order_id'=>$plat_order_result['order_id'])));
                    exit(json_encode(array('error'=>1,'msg'=>'需求已发布,请去支付!','order_id'=>$res,'url'=>U('Pay/check', array('order_id' => $plat_order_result['order_id'], 'type' => 'plat')))));
                }
                die;
                exit(json_encode(array('error'=>1,'msg'=>'需求已发布,请去支付！','order_id'=>$res)));
            }else{
                exit(json_encode(array('error'=>2,'msg'=>'发布失败请重试')));
            }
        }
    }


    public function publish_config(){
        $lng = $_POST['lng'];
        $lat = $_POST['lat'];
        $type = $_POST['type'];
        $time_info = array();
        $data = array();
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
        exit(json_encode(array('error'=>1,'msg'=>'获取成功','time_info'=>$time_info)));
    }



    // 计算两个收货地址的距离（骑行/直线）
    private function compute_distance($start_lat, $start_lng, $end_lat, $end_lng){
        $destance_sum = 0;
        $start_lat = trim($start_lat);
        $start_lng = trim($start_lng);
        $end_lat = trim($end_lat);
        $end_lng = trim($end_lng);
        if (C('config.service_is_riding_distance') && C('config.baidu_map_ak')) {
            import('@.ORG.longlat');
            $longlat_class = new longlat();
            $destance_sum = $longlat_class->getRidingDistance($start_lat, $start_lng, $end_lat, $end_lng);
            if ($destance_sum == -1) {
                exit(json_encode(array('error'=>2,'msg'=>'距离计算失败！')));
            }
        }
        $destance_sum || $destance_sum = getDistance($start_lat, $start_lng, $end_lat, $end_lng);
        $destance_sum = $destance_sum / 1000;
        return $destance_sum;
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


    public function temp_give_data(){
        $_SESSION['temp_give_data'] = $_POST;
        exit(json_encode(array('error'=>1,'msg'=>'存储成功')));
    }

    public  function publish_data(){
        if(IS_POST){
            $data = array();
            foreach ($_POST as $key => $value) {
                if (empty($value['type'])) continue;
                if(!$data['address']){
                    if($value['type'] == 6){
                        $data['address'] = $value['value']['address'];
                        $data['address_lng'] = $value['value']['address_lng'];
                        $data['address_lat'] = $value['value']['address_lat'];
                        $data['address_area_id'] = $value['value']['address_area_id'];
                        $data['address_area_name'] = $value['value']['address_area_name'];
                        // break;
                    }else if($value['type'] == 7){
                        $data['address'] = $value['value']['address_start'];
                        $data['address_lng'] = $value['value']['address_start_lng'];
                        $data['address_lat'] = $value['value']['address_start_lat'];
                        $data['address_area_id'] = $value['value']['address_start_area_id'];
                        $data['address_area_name'] = $value['value']['address_start_area_name'];
                    }
                }

                if($value['type'] == 1 || $value['type'] == 5){
                    $value['value'] = $this->filterEmoji2($value['value']);
                    $_POST[$key]['value'] = $value['value'];
                }

            }

            $data['add_time'] = time();
            $data['cid'] = $_POST['cid'];
            unset($_POST['cid']);
            $data['cat_field'] = serialize($_POST);
            $data['uid'] = $this->user_session['uid'];

            $res = D('Service_user_publish')->data($data)->add();
            if($res){
                //模板消息
                $where = "`cid`={$data['cid']} AND `area_id`={$data['address_area_id']} AND (ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$data['address_lat']}*PI()/180-`lat`*PI()/180)/2),2)+COS({$data['address_lat']}*PI()/180)*COS(`lat`*PI()/180)*POW(SIN(({$data['address_lng']}*PI()/180-`lng`*PI()/180)/2),2)))*1000) <= `radius`*1000 OR `radius` = 0) ";
                $plist = D("Service_provider_category")->where($where)->field('uid,cat_name')->select();

                // 多维数组去重复
                foreach ($plist as $v){
                    $temp[] = serialize($v);
                }
                $temp=array_unique($temp); //去掉重复的字符串,也就是重复的数组
                foreach ($temp as $k => $v){
                    $temp[$k]=unserialize($v); //再将拆开的数组重新组装
                }
                $plist = $temp;

                if($plist){
                    foreach ($plist as $key => $value) {
                        $user_info = D("User")->where(array('uid'=>$value['uid']))->field('uid,openid,nickname')->find();
                        if ($user_info['openid']) {
                            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                            $href = C('config.site_url') . '/wap.php?g=Wap&c=Service&a=detail&publish_id='.$res;

                            $model->sendTempMsg('TM00017',
                                array('href' => $href,
                                    'wecha_id' => $user_info['openid'],
                                    'first' => '平台用户发布了一个符合您的“'.$value['cat_name'].'”需求，请及时报价！',
                                    'OrderSn' => $res,
                                    'OrderStatus' => '待处理',
                                    'remark' => '请您及时处理！'));
                        }
                    }
                }

                $this->success_tips('发布成功！', U('Service/need_list'));
            }else{
                $this->error_tips('发布失败请重试');
            }
        }
    }


    function filterEmoji($str) {
        $str = preg_replace_callback(
            '/./u',
            function (array $match) {
                return strlen($match[0]) >= 4 ? '' : $match[0];
            },
            $str);
        return $str;
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




    // 用户选择分类列表
    public function cat_list(){
    	$catListBak = D('Service_category')->where(array('cat_status'=>1))->order('cat_sort desc,cid asc')->select();
    	$catList = array();
    	foreach ($catListBak as $key => $value) {
    		if($value['fcid'] == 0){
    			$catList [$value['cid']]= $value;
    		}
    	}
    	foreach ($catListBak as $k => $v) {
    		if($v['fcid'] != 0){
    			$catList [$v['fcid']]['catList'][$v['cid']]= $v;
    		}
    	}
		// dump($catList);
		$this->assign('catList',$catList);
        $this->display();
    }

    // 商家选择分类
    public function search_cate(){
        if (empty($this->user_session)) {
            $this->error_tips('请先进行登录！', U('Login/index'));
        }
        if($this->config['provider_is_real_name'] == 1){
            $service_authentication = D("Service_authentication")->where(array('uid'=>$this->user_session['uid'],'authentication_status'=>2))->field('authentication_id,authentication_status,uid')->find();
            if(empty($service_authentication)){
                $this->error_tips('请先通过认证再进行发布',U('Service/authentication'));
            }
        }


    	$catListBak = D('Service_category')->where(array('cat_status'=>1))->order('cat_sort desc,cid asc')->select();
    	$catList = array();
    	foreach ($catListBak as $key => $value) {
    		if($value['fcid'] == 0){
    			$catList [$value['cid']]= $value;
    		}
    	}
    	foreach ($catListBak as $k => $v) {
    		if($v['fcid'] != 0){
    			$catList [$v['fcid']]['catList'][$v['cid']]= $v;
    		}
    	}
		// dump($catList);
		$this->assign('catList',$catList);
    	$this->display();
    }
    //发布服务
    public function service_publish(){
        if (empty($this->user_session)) {
            $this->error_tips('请先进行登录！', U('Login/index'));
        }
    	if(IS_POST){
    		$_SESSION['service_publish'] = null;
    		$catList = D('Service_category')->field(true)->where(array('cid'=>array('in',implode(',',$_POST['selectCateArr']))))->select();
    		$this->assign('catList',$catList);
    		$_SESSION['service_publish']['catList'] = $catList;
    	}else if($_SESSION['service_publish']['catList']){
    		$_SESSION['service_publish']['sname'] = $_GET['sname'];
    		$_SESSION['service_publish']['lng'] = $_GET['lng'];
    		$_SESSION['service_publish']['lat'] = $_GET['lat'];
            $_SESSION['service_publish']['area_id'] = $_GET['area_id'];
            $_SESSION['service_publish']['area_name'] = $_GET['area_name'];
            $adressInfo = D('Service_provider_address')->where(array('lng'=>$_GET['lng'],'lat'=>$_GET['lat'],'uid'=>$this->user_session['uid']))->find();
            if($adressInfo){
                $_SESSION['service_publish']['paid'] = $adressInfo['paid'];
            }else{
                $_SESSION['service_publish']['paid'] = '';
            }

    	}else{
    		$_SESSION['service_publish'] = null;
    		$this->error_tips('传递参数有误！',U('Service/search_cate'));
    	}
    	// dump($_SESSION['service_publish']);
    	$this->assign('service_info',$_SESSION['service_publish']);
    	$this->display();
    }

    // 发布服务数据
    public function service_publish_data(){
    	if(empty($this->user_session['uid'])){
    		$this->error_tips('请先登录再进行操作',U('Service/service_publish'));
    	}
    	if(empty($_POST['sname']) && empty($_POST['sname']) && empty($_POST['sname'])){
    		$this->error_tips('服务地址有误请重试',U('Service/service_publish'));
    	}
    	if(!is_array($_POST['cat'])){
    		$this->error_tips('没有添加任何分类',U('Service/service_publish'));
    	}
    	$info = D('Service_provider')->where(array('uid'=>$this->user_session['uid']))->find();
    	if(is_array($info)){
    		$pid = $info['pid'];
    	}else{
    		$data['uid']=$this->user_session['uid'];
    		$data['phone']=$this->user_session['phone'];
    		$data['name']=$this->user_session['nickname'];
    		$data['avatar']=$this->user_session['avatar'];
    		$res = D('Service_provider')->data($data)->add();
    		if($res){
    			$pid = $res;
    		}else{
    			$this->error_tips('服务商添加异常',U('Service/service_publish'));
    		}
    	}


        if($_POST['paid']){
            $paid = $_POST['paid'];
        }else{
            $address_data['sname'] = $_POST['sname'];
            $address_data['lng'] = $_POST['lng'];
            $address_data['lat'] = $_POST['lat'];
            $address_data['area_id'] = $_POST['area_id'];
            $address_data['area_name'] = $_POST['area_name'];
            $address_data['add_time'] = time();
            $address_data['uid']=$this->user_session['uid'];
            $address_data['pid'] = $pid;
            $paid = D('Service_provider_address')->data($address_data)->add();
            if(empty($paid)){
                $this->error_tips('数据添加异常',U('Service/service_publish'));
            }
        }

    	foreach ($_POST['cat'] as $key => $value) {
            $provider_category_data = $value;
            $provider_category_data['add_time'] = time();
            $provider_category_data['sname'] = $_POST['sname'];
            $provider_category_data['lng'] = $_POST['lng'];
            $provider_category_data['lat'] = $_POST['lat'];
            $provider_category_data['area_id'] = $_POST['area_id'];
            $provider_category_data['area_name'] = $_POST['area_name'];
            $provider_category_data['pid'] = $pid;
            $provider_category_data['paid'] = $paid;
            $provider_category_data['uid']=$this->user_session['uid'];
            $cat_info = D('Service_provider_category')->where(array('uid'=>$this->user_session['uid'],'cid'=>$value['cid'],'paid'=>$paid))->find();
            if($cat_info){
                D('Service_provider_category')->where(array('uid'=>$this->user_session['uid'],'cid'=>$value['cid'],'paid'=>$paid))->data($provider_category_data)->save();
            }else{
                D('Service_provider_category')->data($provider_category_data)->add();
            }
    	}
    	$_SESSION['service_publish'] = null;
    	$this->success_tips('添加成功！',U('Service/provider_home'));
    }

    // 修改服务信息
    public function edit_service_publish(){
    	$addresInfo = D("Service_provider_address")->where(array('paid'=>$_GET['paid']))->find();
    	$catList = D('Service_provider_category')->where(array('paid'=>$addresInfo['paid']))->select();
    	if($_GET['sname']){
    		$addresInfo['sname'] = $_GET['sname'];
    		$addresInfo['lng'] = $_GET['lng'];
    		$addresInfo['lat'] = $_GET['lat'];
            $addresInfo['area_id'] = $_GET['area_id'];
            $addresInfo['area_name'] = $_GET['area_name'];
    	}
        // dump($addresInfo);
    	$this->assign('addresInfo',$addresInfo);
    	$this->assign('catList',$catList);
        // dump($catList);
    	$this->display();
    }

    public function edit_service_publish_data(){

        if(empty($this->user_session['uid'])){
            $this->error_tips('请先登录再进行操作',U('Service/service_publish'));
        }
        if(empty($_POST['sname']) && empty($_POST['sname']) && empty($_POST['sname'])){
            $this->error_tips('服务地址有误请重试',U('Service/service_publish'));
        }
        if(!is_array($_POST['cat'])){
            $this->error_tips('没有添加任何分类',U('Service/service_publish'));
        }

        $address_data['sname'] = $_POST['sname'];
        $address_data['lng'] = $_POST['lng'];
        $address_data['lat'] = $_POST['lat'];
        $address_data['area_id'] = $_POST['area_id'];
        $address_data['area_name'] = $_POST['area_name'];
        // $address_data['add_time'] = time();
        // $address_data['uid']=$this->user_session['uid'];
        // $address_data['pid'] = $pid;
        $paid = D('Service_provider_address')->where(array('paid'=>intval($_POST['paid']),'uid'=>$this->user_session['uid']))->data($address_data)->save();
        // if(empty($paid)){
        //     $this->error_tips('数据修改异常',U('Service/service_publish'));
        // }
        foreach ($_POST['cat'] as $key => $value) {
            $provider_category_data = $value;

            // $provider_category_data['add_time'] = time();
            $provider_category_data['sname'] = $_POST['sname'];
            $provider_category_data['lng'] = $_POST['lng'];
            $provider_category_data['lat'] = $_POST['lat'];
            $provider_category_data['area_id'] = $_POST['area_id'];
            $provider_category_data['area_name'] = $_POST['area_name'];
            // $provider_category_data['pid'] = $pid;
            // $provider_category_data['paid'] = $paid;
            // $provider_category_data['uid']=$this->user_session['uid'];

            D('Service_provider_category')->where(array('sp_cid'=>$value['sp_cid'],'uid'=>$this->user_session['uid'],'paid'=>intval($_POST['paid'])))->data($provider_category_data)->save();
        }
        $_SESSION['service_publish'] = null;
        $this->success_tips('修改成功！',U('Service/editdesc'));
    }

    public function service_cat_del(){
        $catInfo = D('Service_provider_category')->where(array('cid'=>$_POST['cid'],'paid'=>$_POST['paid'],'uid'=>$this->user_session['uid']))->find();
        if(D('Service_provider_category')->where(array('cid'=>$_POST['cid'],'uid'=>$this->user_session['uid']))->delete()){
            $paidList = D('Service_provider_category')->where(array('paid'=>$catInfo['paid'],'uid'=>$this->user_session['uid']))->find();
            if(empty($paidList)){
                D('Service_provider_address')->where(array('paid'=>$catInfo['paid'],'uid'=>$this->user_session['uid']))->delete();
            }
            exit(json_encode(array('error'=>1,'msg'=>'删除成功。')));
        }else{
            exit(json_encode(array('error'=>2,'msg'=>'数据异常，删除失败。')));
        }
    }

    // 修改地址地图adres_map
    public function edit_adres_map(){
    	if(empty($_GET['paid'])){
    		$this->error_tips('数据异常请重试！');
    	}
    	$this->display();
    }

    // 商户主页
    public function provider_home(){
        if (empty($this->user_session)) {
            $this->error_tips('请先进行登录！', U('Login/index'));
            die;
        }
    	$providerInfo = D('Service_provider')->where(array('uid'=>$this->user_session['uid']))->find();
        if(!is_array($providerInfo)){
            // $this->error_tips('您暂时还不是服务商，即将至发布服务页面',U('Service/search_cate'));
            $this->redirect('Service/search_cate');
            die;
        }
        if (empty($providerInfo['avatar'])) {
            // 没有头像取默认头像
            $providerInfo['avatar'] = C('config.site_url') . '/static/default.png';
        }
    	$this->assign('providerInfo',$providerInfo);
    	$imgList = D('Service_provider_img')->where(array('pid'=>$providerInfo['pid']))->select();
    	$this->assign('imgList',$imgList);

    	$addresList = D("Service_provider_address")->where(array('pid'=>$providerInfo['pid']))->select();
    	foreach ($addresList as $key => $value) {
    		$addresList[$key]['catList'] = D('service_provider_category')->where(array('paid'=>$value['paid'],'pid'=>$providerInfo['pid']))->select();
    	}
    	$this->assign('addresList',$addresList);



        $offer_evaluate_list = D('')->table(array(C('DB_PREFIX').'service_offer_evaluate'=>'soe', C('DB_PREFIX').'user'=>'u'))->where("soe.p_uid= '".$this->user_session['uid']."' AND soe.uid = u.uid")->field('soe.*,u.nickname,u.avatar')->order('soe.add_time desc')->select();

        // $offer_evaluate_list = D('Service_offer_evaluate')->where(array('p_uid'=>$this->user_session['uid']))->order('add_time desc')->select();
        $this->assign('offer_evaluate_list',$offer_evaluate_list);
    	// dump($offer_evaluate_list);
    	$this->display();
    }

    public function provider_info(){
        if (empty($this->user_session)) {
            $this->error_tips('请先进行登录！', U('Login/index'));
            die;
        }
        $providerInfo = D('Service_provider')->where(array('uid'=>$_GET['puid']))->find();
        if(!is_array($providerInfo)){
            $this->error_tips('数据异常请重试！',U('Service/search_cate'));
            die;
        }
        $this->assign('providerInfo',$providerInfo);
        $imgList = D('Service_provider_img')->where(array('pid'=>$providerInfo['pid']))->select();
        $this->assign('imgList',$imgList);

        $addresList = D("Service_provider_address")->where(array('pid'=>$providerInfo['pid']))->select();
        foreach ($addresList as $key => $value) {
            $addresList[$key]['catList'] = D('service_provider_category')->where(array('paid'=>$value['paid'],'pid'=>$providerInfo['pid']))->select();
        }
        $this->assign('addresList',$addresList);

        $offer_evaluate_list = D('')->table(array(C('DB_PREFIX').'service_offer_evaluate'=>'soe', C('DB_PREFIX').'user'=>'u'))->where("soe.p_uid= '".$_GET['puid']."' AND soe.uid = u.uid")->field('soe.*,u.nickname,u.avatar')->order('soe.add_time desc')->select();
        $this->assign('offer_evaluate_list',$offer_evaluate_list);
        $this->display();
    }

    // 商户相册
    public function editphoto(){
    	$providerInfo = D('Service_provider')->where(array('uid'=>$this->user_session['uid']))->find();
    	if(IS_POST){
    		D('Service_provider_img')->where(array('pid'=>$providerInfo['pid']))->delete();
    		foreach ($_POST['img'] as $key => $value) {
    			$data = array();
    			$data['img_url'] = $value;
    			$data['pid'] = $providerInfo['pid'];
    			D('Service_provider_img')->data($data)->add();
    		}
    		$this->success_tips('保存成功',U('Service/provider_home'));
    	}
    	$imgList = D('Service_provider_img')->where(array('pid'=>$providerInfo['pid']))->select();
    	$this->assign('imgList',$imgList);
    	$this->display();
    }

    // 修改头像名称
    public function editheader(){
    	if(IS_POST){
    		$data['name'] = $_POST['name'];
    		$data['avatar'] = $_POST['avatar'];
    		$data['phone'] = $_POST['phone'];
     		$res = D('Service_provider')->where(array('uid'=>$this->user_session['uid'],'pid'=>intval($_POST['pid'])))->data($data)->save();
     		$this->success_tips('保存成功',U('Service/provider_home'));
    	}else{
    		$providerInfo = D('Service_provider')->where(array('uid'=>$this->user_session['uid']))->find();
	    	$this->assign('providerInfo',$providerInfo);
	    	$this->display();
    	}

    }

    // 修改备注
    public function editdesc(){
    	if(IS_POST){
    		$data['describe'] = $_POST['describe'];
     		$res = D('Service_provider')->where(array('uid'=>$this->user_session['uid'],'pid'=>intval($_POST['pid'])))->data($data)->save();
     		$this->success_tips('保存成功',U('Service/provider_home'));
    	}else{
    		$providerInfo = D('Service_provider')->where(array('uid'=>$this->user_session['uid']))->find();
	    	$this->assign('providerInfo',$providerInfo);

	    	$addresList = D("Service_provider_address")->where(array('pid'=>$providerInfo['pid']))->select();
	    	foreach ($addresList as $key => $value) {
	    		$addresList[$key]['catList'] = D('Service_provider_category')->where(array('paid'=>$value['paid'],'pid'=>$providerInfo['pid']))->select();
	    	}
	    	$this->assign('addresList',$addresList);
	    	$this->display();
    	}
    }

    // 修改地址
    public function editaddress(){

    	if(IS_POST){
    		$data['sname'] = $_POST['sname'];
    		$data['lng'] = $_POST['lng'];
    		$data['lat'] = $_POST['lat'];
     		$res = D('Service_provider')->where(array('uid'=>$this->user_session['uid'],'pid'=>intval($_POST['pid'])))->data($data)->save();
     		// if($res){
     		// 	$this->success_tips('保存成功',U('Service/provider_home'));
     		// }else{
     		// 	$this->error_tips('保存失败请重试');
     		// }
            $this->success_tips('保存成功',U('Service/provider_home'));
    	}

    	$providerInfo = D('Service_provider')->where(array('uid'=>$this->user_session['uid']))->find();
    	if($_GET['sname']){
    		$providerInfo['sname'] = $_GET['sname'];
    		$providerInfo['lng'] = $_GET['lng'];
    		$providerInfo['lat'] = $_GET['lat'];
    	}
    	$this->assign('providerInfo',$providerInfo);
    	$this->display();
    }
    // 修改地址地图
    public function editaddressdata(){
    	$this->display();
    }


    /* 地图 */
	public function adres_map(){
        $long_lat = D('User_long_lat')->getLocation($_SESSION['openid'],0);
        if ($this->config['now_city']>0) {
            $now_city = M('Area')->where(array('area_id' => $this->config['now_city']))->field('area_lng, area_lat')->find();
            if ((!$long_lat || !$long_lat['long'] || !$long_lat['lat']) && $now_city) {
                $long_lat = array(
                    'long' => $now_city['area_lng'],
                    'lat' => $now_city['area_lat'],
                    'dateline' => time(),
                );
            }
        }
        if (empty($long_lat)) {
            $long_lat = array(
                'long' => 0,
                'lat' => 0,
            );
        }
        $this->assign('long_lat',$long_lat);
		$this->display();
	}

	// 上传图片
	public function ajax_upload_file(){
        if(!$_FILES){
            exit(json_encode(array('error'=>1,'msg'=>'没有选择图片')));
        }
        if($_FILES['imgFile']['error'] == 4){
            exit(json_encode(array('error'=>1,'msg'=>'没有选择图片')));
        }
        if($_FILES['imgFile']['error'] == 3){
            exit(json_encode(array('error'=>1,'msg'=>'文件只有部分被上传')));
        }
        $upload_file = D('Image')->handle($this->user_session['uid'], 'service_provider', 0, array('size' => 20), false);
        if ($upload_file['error'] == 1){
            exit(json_encode(array('error'=>1,'msg'=>'上传失败，' .$upload_file['message']. '请重试！')));
        }else{
            exit(json_encode(array('error'=>2,'msg'=>'上传成功','url'=>$upload_file['url']['imgFile'])));
        }

	}
    // 上传微信图片
    public function ajax_upload_wx_file(){
        if (!$_POST['serverId']) {
            exit(json_encode(array('error'=>1,'msg'=>'没有选择图片')));
        }
        $mulu=isset($_GET['ml']) ? trim($_GET['ml']):'wx_service';
        import('ORG.Net.Http');
        $http = new Http();
        $media_id = $_POST['serverId'];
        $access_token_array = D('Access_token_expires')->get_access_token();
        if (!$access_token_array['errcode']) {
            $access_token = $access_token_array['access_token'];
            $url = 'http://file.api.weixin.qq.com/cgi-bin/media/get?access_token='.$access_token_array['access_token'].'&media_id='.$media_id;
            $fileInfo = $this->downloadWxFile($url);
            // 生成文件路径
            $path = 'service_provider';
            $img_id = sprintf("%09d", $this->user_session['uid']);
            $rand_num = substr($img_id, 0, 3) . '/' . substr($img_id, 3, 3) . '/' . substr($img_id, 6, 3);
            $upload_dir = "./upload/{$path}/{$rand_num}/";
            if(!is_dir($upload_dir)){
                mkdir($upload_dir, 0777, true);
            }

            $newfilename = $media_id.'_' . date('YmdHis') . '.jpg';

            $file_name = $upload_dir . $newfilename;
            $ret = $this->saveWxFile($file_name, $fileInfo['body']);
            $img_url = file_domain() . "/upload/{$path}/{$rand_num}/" . $newfilename;
            exit(json_encode(array('error'=>2,'msg'=>'上传成功','url'=>$img_url)));
        } else {
            exit(json_encode(array('error'=>1,'msg'=>'上传失败，请重试！')));
        }

    }

    function downloadWxFile($url) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_NOBODY, 0); // 只取body头
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $package = curl_exec($ch);
        $httpinfo = curl_getinfo($ch);
        curl_close($ch);
        $imageAll = array_merge(array('header' => $httpinfo), array('body'=> $package));
        return $imageAll;
    }

    function saveWxFile($filename, $filecontent) {
        $local_file = fopen($filename, 'w');
        if (false !== $local_file) {
            if (false !== fwrite($local_file, $filecontent)) {
                fclose($local_file);
            }
        }
    }

    // 我的需求列表
    public function need_list(){
        if (empty($this->user_session)) {
            $this->error_tips('请先进行登录！', U('Login/index'));
        }

        $publishList = D('')->table(array(C('DB_PREFIX').'service_user_publish'=>'sup', C('DB_PREFIX').'service_category'=>'sc'))->where("sup.uid= '".intval($this->user_session['uid'])."' AND sup.cid = sc.cid")->field('sup.*,sc.cat_name,sc.accept_time')->order('sup.add_time desc')->limit(6)->select();
        $publishCount = D('')->table(array(C('DB_PREFIX').'service_user_publish'=>'sup', C('DB_PREFIX').'service_category'=>'sc'))->where("sup.uid= '".intval($this->user_session['uid'])."' AND sup.cid = sc.cid")->count();

        foreach ($publishList as $key => $value) {
            if($value['catgory_type'] == 1){
                $time = intval($value['add_time'])+(intval($value['accept_time'])*60*60*24);
            }else{
                $time = intval($value['add_time'])+(intval($value['accept_time'])*60*60);
            }
            if(time() > $time){
                // D('Service_user_publish')->where(array('uid'=>$this->user_session['uid'],'publish_id'=>$vvalue['publish_id']))->data(array('status'=>3))->save();
                if($value['status'] < 2){
                    $publishList[$key]['status'] = 11;
                    D('Service_user_publish')->where(array('uid'=>$this->user_session['uid'],'publish_id'=>$value['publish_id']))->data(array('status'=>11))->save();
                }
            }
            if ($time) {
                $time = date("Y-m-d H:i", $time);
            }

            $publishList[$key]['endtime'] =$time;

            $publishList[$key]['cat_field'] = unserialize($value['cat_field']);
            $offer_list = '';
            $offer_list = D('')->table(array(C('DB_PREFIX').'service_offer'=>'so', C('DB_PREFIX').'service_provider'=>'u'))->where("so.publish_id= '".$value['publish_id']."' AND u.uid = so.p_uid")->field('so.offer_id,so.price,u.avatar')->select();
            $publishList[$key]['offer_count'] = count($offer_list);
            $publishList[$key]['offer_list'] = $offer_list;
        }

        $this->assign('publishList',$publishList);
        $this->assign('publishCount', $publishCount);
        $this->display();
    }


    // 我的需求列表
    public function need_list_ajax(){
        $listRows = 6;
        $firstRow = $_POST['page'] * $listRows;

        $publishList = D('')->table(array(C('DB_PREFIX').'service_user_publish'=>'sup', C('DB_PREFIX').'service_category'=>'sc'))->where("sup.uid= '".intval($this->user_session['uid'])."' AND sup.cid = sc.cid")->field('sup.*,sc.cat_name,sc.accept_time')->order('sup.add_time desc')->limit($firstRow,$listRows)->select();

        if(empty($publishList)){
            exit(json_encode(array('error'=>2,'msg'=>'暂无数据！')));
        }

        foreach ($publishList as $key => $value) {
            if($value['catgory_type'] == 1){
                $time = $value['add_time']+(intval($value['accept_time'])*60*60*24);
            }else{
                $time = $value['add_time']+(intval($value['accept_time'])*60*60);
            }

            $publishList[$key]['endtime'] =$time;
            if(time() > $time){
                if($value['status'] < 2){
                    $publishList[$key]['status'] = 11;
                    D('Service_user_publish')->where(array('uid'=>$this->user_session['uid'],'publish_id'=>$value['publish_id']))->data(array('status'=>11))->save();
                }
            }

            $publishList[$key]['cat_field'] = unserialize($value['cat_field']);
            $offer_list = '';
            $offer_list = D('')->table(array(C('DB_PREFIX').'service_offer'=>'so', C('DB_PREFIX').'service_provider'=>'u'))->where("so.publish_id= '".$value['publish_id']."' AND u.uid = so.p_uid")->field('so.offer_id,so.price,u.avatar')->select();
            $publishList[$key]['offer_count'] = count($offer_list);
            $publishList[$key]['offer_list'] = $offer_list;
        }

        $status_info1 = [
            0 =>  '正常',
            1 =>  '已发布',
            2 =>  '已付款待服务',
            3 =>  '已服务待确认',
            4 =>  '订单完成',
            5 =>  '申请退款',
            6 =>  '退款成功',
            7 =>  '评价成功',
            8 =>  '等待取货',
            9 =>  '配送中',
            10 =>  '已暂停报价',
            11 =>  '已经过期',
            12 =>  '过期退款',
            13 =>  '退款失败',
        ];
        $status_info2 = [
            0 =>  '正常',
            1 =>  '去支付',
            2 =>  '待接单',
            3 =>  '已服务',
            4 =>  '订单完成',
            5 =>  '申请退款',
            6 =>  '退款成功',
            7 =>  '评价成功',
            8 =>  '等待取货',
            9 =>  '配送中',
            10 =>  '已暂停报价',
            11 =>  '已经过期',
            12 =>  '过期退款',
            13 =>  '退款失败',
        ];
        $status_info3 = [
            0 =>  '正常',
            1 =>  '正常',
            2 =>  '待接单',
            3 =>  '已服务',
            4 =>  '订单完成',
            5 =>  '申请退款',
            6 =>  '退款成功',
            7 =>  '评价成功',
            8 =>  '等待取货',
            9 =>  '配送中',
            10 =>  '已暂停报价',
            11 =>  '已经过期',
            12 =>  '过期退款',
            13 =>  '退款失败',
        ];

        $imgHtml = '';
        $html = '';
        foreach ($publishList as $key => $value) {

            if($value['catgory_type'] == 1){
                $status = $status_info1[$value['status']];

                foreach ($value['offer_list'] as $offer_k => $offer_v) {
                    if($offer_k <5){
                        $imgHtml .= '<img style="border-radius:35px; width:35px; height:35px; border:#cdefeb 1px solid;" src="{pigcms{$offer_vo.avatar}" alt="">';
                    }else{
                        $dian = '...';
                    }
                }
                if ($value['endtime']) {
                    $endTime = date("Y-m-d H:i", $value['endtime']);
                }

                $html .= '<a href="'.U('Service/price_list',array('publish_id'=>$value['publish_id'])).'">
                                <li class="link-url" data-url="shop_detail.html?order_id=8867" data-webview="true">
                                    <div class="top clr">
                                        <if condition="'.$endTime.'">
                                            <div class="fl">截至日期：' . $endTime . '</div>
                                        </if>
                                    </div>
                                    <div class="con">
                                        <div class="con_top">
                                            <p>
                                                所属分类：
                                                <span class="red">'.$value['cat_name'].'</span>
                                            </p>
                                            <p>
                                                报价人数：'.$value['offer_count'].'个
                                            </p>
                                            <p>
                                                发布时间：'.date('Y-m-d H:i:s',$value['add_time']).'
                                            </p>
                                        </div>
                                        <div class="remark">
                                            <div>
                                               '.$imgHtml.$dian.'
                                            </div>
                                            <div class="overtime">
                                                '.$status.'
                                            </div>
                                        </div>
                                    </div>
                                </li>

                                </a>';
            }elseif ($value['catgory_type'] == 2) {
                $status = '';
                $status = $status_info2[$value['status']];

                foreach ($value['offer_list'] as $offer_k => $offer_v) {
                    if($offer_k <5){
                        $imgHtml .= '<img style="border-radius:35px; width:35px; height:35px; border:#cdefeb 1px solid;" src="{pigcms{$offer_vo.avatar}" alt="">';
                    }
                }
                $msg = $status;
                if ($value['status'] == 1) {
                    $msg = '<a style="border: 1px solid #06C1AE; padding: 3px 10px;border-radius: 3px;color: #06C1AE;" data-id="'.$value['publish_id'].'"  onclick="payMoney(this)" href="javascript:void(0);">去支付</a>';
                }
                $style = '';
                if ($value['status'] == 2) {
                    $msg .= '&nbsp;<a style="border: 1px solid #999c9e; padding: 3px 10px;border-radius: 3px;color: #999c9e;" data-id="'.$value['publish_id'].'"  onclick="order_refund(this)" href="javascript:void(0);">取消订单</a>';
                    $style = 'style="width: 125px;"';
                }

                $html .= '<a href="'.U('Service/price_list',array('publish_id'=>$value['publish_id'])).'">
                    <li class="link-url">
                        <div class="top clr">
                            <div class="fl">截至日期：'.date('Y-m-d H:i:s',$value['endtime']).'</div>
                        </div>
                        <div class="con">
                            <div class="con_top">
                                <p>
                                    所属分类：
                                    <span class="red">'.$value['cat_name'].'</span>
                                </p>
                                <p>
                                    发布时间：'.date('Y-m-d H:i:s',$value['add_time']).'
                                </p>
                            </div>
                            <div class="remark">
                                <div>
                                    '.$imgHtml.'
                                </div>
                                <div class="overtime" '. $style .'>
                                    '.$msg.'
                                </div>
                            </div>
                        </div>
                    </li>
                </a>';

            }elseif ($value['catgory_type'] == 3) {
                $status = $status_info3[$value['status']];
                foreach ($value['offer_list'] as $offer_k => $offer_v) {
                    if($offer_k <5){
                        $imgHtml .= '<img style="border-radius:35px; width:35px; height:35px; border:#cdefeb 1px solid;" src="{pigcms{$offer_vo.avatar}" alt="">';
                    }
                }

                $msg = $status;
                if ($value['status'] == 1) {
                    $msg = '<a style="border: 1px solid #06C1AE; padding: 3px 10px;border-radius: 3px;color: #06C1AE;" data-id="'.$value['publish_id'].'"  onclick="payMoney(this)" href="javascript:void(0);">去支付</a>';
                }
                $style = '';
                if ($value['status'] == 2) {
                    $msg .= '&nbsp;<a style="border: 1px solid #999c9e; padding: 3px 10px;border-radius: 3px;color: #999c9e;" data-id="'.$value['publish_id'].'"  onclick="order_refund(this)" href="javascript:void(0);">取消订单</a>';
                    $style = 'style="width: 125px;"';
                }

                $html .= '<a href="'.U('Service/price_list',array('publish_id'=>$value['publish_id'])).'">
                    <li class="link-url"  data-webview="true">
                        <div class="top clr">
                            <div class="fl">截至日期：'.date('Y-m-d H:i:s',$value['endtime']).'</div>
                        </div>
                        <div class="con">
                            <div class="con_top">
                                <p>
                                    所属分类：
                                    <span class="red">'.$value['cat_name'].'</span>
                                </p>
                                <p>
                                    发布时间：'.date('Y-m-d H:i:s',$value['add_time']).'
                                </p>
                            </div>
                            <div class="remark">
                                <div>
                                    '.$imgHtml.'
                                </div>
                                <div class="overtime" '.$style.'>
                                    '.$msg.'
                                </div>
                            </div>
                        </div>
                    </li>
                </a>';
            }
        }

        exit(json_encode(array('error'=>1,'msg'=>'返回成功','publishListHtml'=>$html)));
    }





    // 我的需求列表
    public function need_list_app(){
        if (empty($this->user_session)) {
            $this->error_tips('请先进行登录！', U('Login/index'));
        }
		
		$publishList_where = "`sup`.`uid`='".intval($this->user_session['uid'])."' AND `sup`.`cid`=`sc`.`cid`";
		
		if($_GET['from'] == 'paotui'){
			$publishList_where .= " AND (`sup`.`catgory_type`='2' OR`sup`.`catgory_type`='3')";
		}
		
        $publishList = D('')->table(array(C('DB_PREFIX').'service_user_publish'=>'sup', C('DB_PREFIX').'service_category'=>'sc'))->where($publishList_where)->field('sup.*,sc.cat_name,sc.accept_time')->order('sup.add_time desc')->select();
        $publishCount = D('')->table(array(C('DB_PREFIX').'service_user_publish'=>'sup', C('DB_PREFIX').'service_category'=>'sc'))->where($publishList_where)->count();

        foreach ($publishList as $key => $value) {
            if($value['catgory_type'] == 1){
                $time = $value['add_time']+(intval($value['accept_time'])*60*60*24);
            }else{
                $time = $value['add_time']+(intval($value['accept_time'])*60*60);
            }

            $publishList[$key]['endtime'] =$time;
            if(time() > $time){
                // D('Service_user_publish')->where(array('uid'=>$this->user_session['uid'],'publish_id'=>$vvalue['publish_id']))->data(array('status'=>3))->save();
                if($value['status'] < 2){
                    $publishList[$key]['status'] = 11;
                    D('Service_user_publish')->where(array('uid'=>$this->user_session['uid'],'publish_id'=>$value['publish_id']))->data(array('status'=>11))->save();
                }
            }

            $publishList[$key]['cat_field'] = unserialize($value['cat_field']);
            $offer_list = '';
            $offer_list = D('')->table(array(C('DB_PREFIX').'service_offer'=>'so', C('DB_PREFIX').'service_provider'=>'u'))->where("so.publish_id= '".$value['publish_id']."' AND u.uid = so.p_uid")->field('so.offer_id,so.price,u.avatar')->select();
            $publishList[$key]['offer_count'] = count($offer_list);
            $publishList[$key]['offer_list'] = $offer_list;
        }

        $this->assign('publishList',$publishList);
        $this->assign('publishCount',$publishCount);
        $this->display();
    }






    /**
     *
     * @param  $latitude    纬度
     * @param  $longitude    经度
     * @param  $raidus        半径范围(单位：米)
     * @return multitype:number
     */
    public function getAround($latitude,$longitude,$raidus){
        $PI = 3.14159265;
        $degree = (24901*1609)/360.0;
        $dpmLat = 1/$degree;
        $radiusLat = $dpmLat*$raidus;
        $minLat = $latitude - $radiusLat;
        $maxLat = $latitude + $radiusLat;
        $mpdLng = $degree*cos($latitude * ($PI/180));
        $dpmLng = 1 / $mpdLng;
        $radiusLng = $dpmLng*$raidus;
        $minLng = $longitude - $radiusLng;
        $maxLng = $longitude + $radiusLng;
        return array (minLat=>$minLat, maxLat=>$maxLat, minLng=>$minLng, maxLng=>$maxLng);
    }



    // 带报价需求
    public function trade(){
        if (empty($this->user_session)) {
            $this->error_tips('请先进行登录！', U('Login/index'));
        }
        $cidlist = implode(',',$_SESSION['cid']);
        if($_GET['cid']){
            if($_GET['cid'] !='qb'){
                $catwhere['cid'] = $_GET['cid'];
                $catwhere['uid'] =$this->user_session['uid'];
            }else{
                $catwhere['uid'] =$this->user_session['uid'];
            }
        }else{
            if($cidlist){
                 $catwhere['cid'] = array('in',$cidlist);
                 $this->assign('cur','cur');
            }
            $catwhere['uid'] =$this->user_session['uid'];
        }

        $myOfferIdList = D('Service_offer')->where(array('p_uid'=>$this->user_session['uid']))->field('publish_id')->select();
        foreach ($myOfferIdList as $key => $value) {
            $offer_publish_id_list[] = $value['publish_id'];
        }

        $categoryList = D('Service_provider_category')->where($catwhere)->select();
        $publishList = array();
        foreach ($categoryList as $key => $value) {
            if($offer_publish_id_list){
                $where['publish_id'] = array('not in',implode(',',$offer_publish_id_list));
            }

            $where['deliver_type'] = 2;
            $where['cid'] = $value['cid'];
            $where['address_area_id'] = $value['area_id'];
            $where['catgory_type'] = 1;
            $where['status'] = 1;
            $where['uid'] = array('neq',$this->user_session['uid']);

            if($value['radius'] < 1 ){
                if($_GET['time']){
                    $time= $_GET['time']-1;
                    $where['add_time'] = array('gt',strtotime(date('Ymd'))-($time*60*60*24));
                }
                $list= D('Service_user_publish')->where($where)->order('add_time desc')->select();
            }else{
                if($_GET['time']){
                    $time= $_GET['time']-1;
                    $where['add_time'] = array('gt',strtotime(date('Ymd'))-($time*60*60*24));
                }
                $array = $this->getAround($value['lat'],$value['lng'],$value['radius']*1000);
                $where['address_lat']  = array(array('EGT',$array['minLat']),array('ELT',$array['maxLat']),'and');
                $where['address_lng'] = array(array('EGT',$array['minLng']),array('ELT',$array['maxLng']),'and');
                $list = D('Service_user_publish')->where($where)->order('add_time desc')->select();
            }

            foreach ($list as $kk => $vv) {
                $publishList[] = $vv;
            }
        }
        foreach ($publishList as $key => $value) {
            $publishList[$key]['cat_field'] = unserialize($value['cat_field']);
            $cat = D('Service_category')->where(array('cid'=>$value['cid']))->field('cat_name')->find();
            $publishList[$key]['cat_name'] = $cat['cat_name'];
            $userinfo = D('User')->where(array('uid'=>$value['uid']))->field('phone,nickname')->find();
            if($userinfo['phone']){
                $publishList[$key]['phone'] = substr_replace($userinfo['phone'],'*****',3,5);
            }
            $publishList[$key]['nickname'] = $userinfo['nickname'];
        }

        foreach ($publishList as $v){
            $v=serialize($v);
            $temp[]=$v;
        }
        $temp=array_unique($temp); //去掉重复的字符串,也就是重复的数组
        foreach ($temp as $k => $v){
            $temp[$k]=unserialize($v); //再将拆开的数组重新组装
        }
        $publishList = $temp;

        $sort = array(
                'direction' => 'SORT_DESC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
                'field'     => 'add_time',       //排序字段
        );
        $arrSort = array();
        foreach($publishList AS $uniqid => $row){
            foreach($row AS $key=>$value){
                $arrSort[$key][$uniqid] = $value;
            }
        }
        if($sort['direction']){
            array_multisort($arrSort[$sort['field']], constant($sort['direction']), $publishList);
        }

        $this->assign('publishList',$publishList);
        $this->assign('publishCount',count($publishList));
        $this->display();
    }





    public function authentication(){
        // echo $this->config['authentication_price'];
        $authentication_info = D('Service_authentication')->where(array('uid'=>$this->user_session['uid']))->find();
        if($authentication_info){

            $authentication_info['authentication_field'] = unserialize($authentication_info['authentication_field']);
            $this->assign('authentication_info',$authentication_info);
            if($authentication_info['authentication_status'] == 3){
                $authentication_config_list = D('Service_authentication_config')->order('type asc')->select();
                foreach ($authentication_config_list as $key => $value) {
                    $value['key'] = str_replace(['/', '.', '\\'], '-', $value['key']);
                    if($value['type'] == 1){
                        $authentication_wenben[] = $value;
                    }else{
                        $authentication_tupian[] = $value;
                    }
                }
                $this->assign('authentication_wenben',$authentication_wenben);
                $this->assign('authentication_tupian',$authentication_tupian);

                $this->display('authentication_edit');
            }else{
                $this->display('authentication_index');
            }
        }else{
            $authentication_config_list = D('Service_authentication_config')->order('type asc')->select();
            foreach ($authentication_config_list as $key => $value) {
                $value['key'] = str_replace(['/', '.', '\\'], '-', $value['key']);
                if($value['type'] == 1){
                    $authentication_wenben[] = $value;
                }else{
                    $authentication_tupian[] = $value;
                }
            }
            $this->assign('authentication_wenben',$authentication_wenben);
            $this->assign('authentication_tupian',$authentication_tupian);
            $this->display();
        }

    }

    public function authentication_data(){

        $data['uid'] = $this->user_session['uid'];
        $data['authentication_status'] = 1;
        $data['authentication_time'] = time();
        $authentication_id = $_POST['authentication_id'];
        unset($_POST['imgFile']);
        unset($_POST['authentication_id']);
        foreach ($_POST as $key => $value) {
            if(!$value['value']){
                $this->error_tips($value['title'].'不可以为空！');
            }
        }
        $data['authentication_field'] = serialize($_POST);

        if($authentication_id){
            $res = D('Service_authentication')->where(array('authentication_id'=>$authentication_id))->data($data)->save();
            if($res){
                //扣款写记录
                $this->success_tips('提交成功！', U('Service/authentication'));
            }else{
                $this->error_tips('提交失败请重试');
            }
        }else{
            //查询是否已提交审核
            $ready_data = D('Service_authentication')->where(['uid'=>$data['uid']])->find();
            if($ready_data){
                $this->success_tips('审核已提交，请耐心等待管理员审核通知！', U('Service/authentication'));
            }
            $res = D('Service_authentication')->data($data)->add();
            if($res){
                $this->success_tips('提交成功！', U('Service/authentication'));
            }else{
                $this->error_tips('提交失败请重试');
            }
        }

    }




    // 自定义分类筛选
    public function trade_setting(){
        if (empty($this->user_session)) {
            $this->error_tips('请先进行登录！', U('Login/index'));
        }
        if(IS_POST){
            $cidStr = $_POST['select_cate_ids'];
            $cid = explode(',',$cidStr);
            foreach( $cid as $k=>$v){
                if( !$v )
                    unset( $cid[$k] );
            }
            $_SESSION['cid'] = $cid;
            $this->redirect('Service/trade');
        }else{
            $cidlist = $_SESSION['cid'];
            $categoryList = D('Service_provider_category')->where(array('uid'=>$this->user_session['uid']))->field('cat_name,cid')->group('cid')->select();
            foreach ($categoryList as $key => $value) {
                foreach ($cidlist as $k => $v) {
                    if($v == $value['cid']){
                        $categoryList[$key]['status'] = 1;
                    }
                }
            }
            $this->assign('cidlist',implode(',',$cidlist));
            $this->assign('categoryList',$categoryList);
            $this->display();
        }
    }

    //特殊需求
    public function trade_special(){
        if (empty($this->user_session)) {
            $this->error_tips('请先进行登录！', U('Login/index'));
        }
        $catwhere['catgory_type'] = array('neq',1);
        $catwhere['uid'] =$this->user_session['uid'];

        $myOfferIdList = D('Service_offer')->where(array('p_uid'=>$this->user_session['uid']))->field('publish_id')->select();
        foreach ($myOfferIdList as $key => $value) {
            $offer_publish_id_list[] = $value['publish_id'];
        }

        $categoryList = D('Service_provider_category')->where($catwhere)->select();
        // dump($categoryList);
        $publishList = array();
        foreach ($categoryList as $key => $value) {
            if($offer_publish_id_list){
                $where['publish_id'] = array('not in',implode(',',$offer_publish_id_list));
            }

            $where['deliver_type'] = 2;
            $where['status'] = 2;
            $where['cid'] = $value['cid'];
            $where['address_area_id'] = $value['area_id'];
            // $where['catgory_type'] = array('neq',1);
            $where['uid'] = array('neq',$this->user_session['uid']);

            if($value['radius'] < 1 ){
                $list= D('Service_user_publish')->where($where)->select();
            }else{
                $array = $this->getAround($value['lat'],$value['lng'],$value['radius']*1000);
                $where['address_lat']  = array(array('EGT',$array['minLat']),array('ELT',$array['maxLat']),'and');
                $where['address_lng'] = array(array('EGT',$array['minLng']),array('ELT',$array['maxLng']),'and');
                $list = D('Service_user_publish')->where($where)->select();
            }

            foreach ($list as $kk => $vv) {
                    $cat = D('Service_category')->where(array('cid'=>$value['cid']))->field('cat_name')->find();
                    $vv['cat_name'] = $cat['cat_name'];

                    $userinfo = '';
                    $userinfo = D('User')->where(array('uid'=>$vv['uid']))->field('phone,nickname')->find();
                    if($userinfo['phone']){
                        $vv['phone'] = substr_replace($userinfo['phone'],'*****',3,5);
                    }
                    $vv['nickname'] = $userinfo['nickname'];
                    if ($value['catgory_type'] == 2) {
                        $vv['cat_field'] =  D('Service_user_publish_buy')->where(array('publish_id'=>$vv['publish_id']))->find();
                    }elseif ($value['catgory_type'] == 3) {
                        $vv['cat_field'] =  D('Service_user_publish_give')->where(array('publish_id'=>$vv['publish_id']))->find();
                    }
                $publishList[] = $vv;
            }
        }


        // 多维数组去重复
        foreach ($publishList as $v){
            $v=serialize($v);
            $temp[]=$v;
        }

        $temp=array_unique($temp); //去掉重复的字符串,也就是重复的数组
        foreach ($temp as $k => $v){
            $temp[$k]=unserialize($v); //再将拆开的数组重新组装
        }
        $publishList = $temp;

        // 排序
        $sort = array(
                'direction' => 'SORT_DESC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
                'field'     => 'add_time',       //排序字段
        );
        $arrSort = array();
        foreach($publishList AS $uniqid => $row){
            foreach($row AS $key=>$value){
                $arrSort[$key][$uniqid] = $value;
            }
        }
        if($sort['direction']){
            array_multisort($arrSort[$sort['field']], constant($sort['direction']), $publishList);
        }


        $this->assign('publishList',$publishList);
        $this->assign('publishCount',count($publishList));
        $this->display();
    }

    public function special_add_offer(){

        $publishInfo = D("Service_user_publish")->where(array('publish_id'=>$_POST['publish_id'],'status'=>2))->field('publish_id,uid,cid,order_sn')->find();
        if(empty($publishInfo)){
            exit(json_encode(array('error'=>4,'msg'=>'接单失败，请重试！')));
        }
        $offer_info = D("Service_offer")->where(array('publish_id'=>$_POST['publish_id']))->find();
        if($offer_info){
            exit(json_encode(array('error'=>3,'msg'=>'接单失败，此单已经被别的配送员接了！')));
        }

        $offer_data['publish_id'] = $publishInfo['publish_id'];
        $offer_data['price'] = 0;
        $offer_data['uid'] = $publishInfo['uid'];
        $offer_data['cid'] = $publishInfo['cid'];
        $offer_data['add_time'] = time();
        $offer_data['order_sn'] = date("ymdHis").rand(10,99).sprintf("%08d",$this->user_session['uid']);
        $offer_data['p_uid'] = $this->user_session['uid'];
        $offer_data['deliver_type'] = 2;
        $offer_data['status'] = 8;
        $offer_id = D("Service_offer")->data($offer_data)->add();

        if($offer_id){
            D("Service_user_publish")->where(array('publish_id'=>$publishInfo['publish_id']))->save(array('status' => 8));
            D('Service_offer_record')->data(array('offer_id'=>$offer_id,'publish_id'=>$publishInfo['publish_id'],'add_time'=>time(),'remarks'=>'配送员已接单'))->add();     // 添加推送状态消息
            $now_user = D('User')->get_user($publishInfo['uid']);
            if ($now_user['openid']) {
                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                $href = C('config.site_url') . '/wap.php?g=Wap&c=Service&a=price_list&publish_id=' . $_POST['publish_id'];
                // 帮买帮送修改  待办事项通知 模板 为 订单状态更新 模板
                $model->sendTempMsg('TM00017', array('href' => $href, 'wecha_id' => $now_user['openid'], 'OrderStatus' => C('config.service_alias_name'), 'OrderSn' => $publishInfo['order_sn'], 'first' =>'您发布的需求配送员已接单，请查看。', 'remark' => date('Y.m.d H:i:s')));
            }
            exit(json_encode(array('error'=>1,'msg'=>'接单成功，请尽快完成。',)));
        }else{
            exit(json_encode(array('error'=>2,'msg'=>'接单失败，请重试！')));
        }
    }

    public function offer_save_status(){
        $user_publish_where['publish_id']  = $_POST['publish_id'];
        D("Service_user_publish")->where($user_publish_where)->save(array('status'=>$_POST['status']));
        $offerInfo = D('Service_offer')->where(array('p_uid'=>$this->user_session['uid'],'publish_id'=>$_POST['publish_id']))->field('offer_id,p_uid')->find();
        $offer_where['p_uid'] = $this->user_session['uid'];
        $offer_where['publish_id']  = $_POST['publish_id'];
        // $offer_where['offer_id'] = $offer_id;
        $offer_where['deliver_type'] = 2;
        $res = D('Service_offer')->where($offer_where)->data(array('status'=>$_POST['status']))->save();

        if($_POST['status'] == 4){
            $remarks = '订单已完成';
        }else if($_POST['status'] == 9){
            $remarks = '配送员送货中';
        }else if($_POST['status'] == 8){
            $remarks = '配送员已接单';
        }

        if($res){
            $publishInfo = D('Service_user_publish')->where(array('publish_id'=>$_POST['publish_id']))->field('publish_id,cid,uid,catgory_type,order_sn')->find();
            if($_POST['status'] == 4){

                if($publishInfo['catgory_type'] == 2){
                    $publish_field_info = D('Service_user_publish_buy')->where(array('publish_id'=>$publishInfo['publish_id']))->find();
                }else if($publishInfo['catgory_type'] == 3){
                    $publish_field_info = D('Service_user_publish_give')->where(array('publish_id'=>$publishInfo['publish_id']))->find();
                }

                $categoryInfo = D('Service_category')->where(array('cid'=>$publishInfo['cid']))->field('cid,cut_proportion')->find();
                $cut_proportion = $categoryInfo['cut_proportion']/100;

                $poundage = round($publish_field_info['total_price']*$cut_proportion,2);
                $price = round($publish_field_info['total_price']-$poundage, 2);

                D('User')->where(array('uid'=>$offerInfo['p_uid']))->setInc('now_money',$price);
                D('User_money_list')->add_row($offerInfo['p_uid'],1,$price,"用户付款 ".$publish_field_info['total_price'].C('config.Currency_txt').",包含小费".$publish_field_info['tip_price'].C('config.Currency_txt').",扣除手续费".round($poundage,2).C('config.Currency_txt'));
                D('Service_provider')->where(array('uid'=>$offerInfo['p_uid']))->setInc('total_amount',$price);

            }
            D('Service_offer_record')->data(array('offer_id'=>$offerInfo['offer_id'],'publish_id'=>$_POST['publish_id'],'add_time'=>time(),'remarks'=>$remarks))->add();
            // 添加推送状态消息
            $now_user = D('User')->get_user($publishInfo['uid']);
            if ($now_user['openid']) {
                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                $href = C('config.site_url') . '/wap.php?g=Wap&c=Service&a=price_list&publish_id=' . $_POST['publish_id'];
                // 帮买帮送修改  待办事项通知 模板 为 订单状态更新 模板
                $model->sendTempMsg('TM00017', array('href' => $href, 'wecha_id' => $now_user['openid'], 'OrderStatus' => C('config.service_alias_name'), 'OrderSn' => $publishInfo['order_sn'], 'first' =>'您发布的需求'.$remarks.'，请查看。', 'remark' => date('Y.m.d H:i:s')));
            }


            // 处理返回信息
            // 获取配送员信息
            $user = D('Deliver_user')->field(true)->where(array('uid' => $offerInfo['p_uid'],'status'=>array('neq',4)))->find();
            if ($_POST['status']==8) {
                $log_status = 2;
                $msg = '配送员已经接单，待取货';
            } elseif ($_POST['status']==9) {
                $log_status = 3;
                $msg = '配送员取货，配送中';
            } elseif ($_POST['status']==4) {
                $log_status = 4;
                $msg = '配送员送达，订单完成';
            }

            // 添加记录
            $log = array(
                'publish_id' => $publishInfo['publish_id'],
                'uid' => $user['uid']?$user['uid']:0,
                'status' => $log_status,
                'name' => $user?$user['name']:'',
                'phone' => $user?$user['phone']:'',
                'remark' => $msg,
                'type' => 1,
                'admin_id' => 0,
            );
            D('Service_user_publish_log')->add_log($log);
            if ($publishInfo['is_third']>0 && $publishInfo['notifyurl']) {
                // 回调信息
                $notifyurl = $publishInfo['notifyurl'];
                $notify_data = array(
                    'third_id' => $publishInfo['third_id'],
                    'publish_id' => $publishInfo['publish_id'],
                    'status' => $log_status,
                    'name' => $user?$user['name']:'',
                    'phone' => $user?$user['phone']:'',
                    'msg' => $msg,
                    'time' => time(),
                );
                D('Service_user_publish_log')->notify_url($notify_data, $notifyurl);
            }


            exit(json_encode(array('error'=>1,'msg'=>'状态已修改，请尽快完成。')));
        }else{
            exit(json_encode(array('error'=>2,'msg'=>'变动失败，请刷新重试！多次重试无用请联系管理员。')));
        }
    }

    public function detail_special(){
        $publishInfo = D('Service_user_publish')->where(array('publish_id'=>$_GET['publish_id']))->find();
        $offer_info = D('Service_offer')->where(array('publish_id'=>$_GET['publish_id'],'deliver_type'=>2,'p_uid'=>$this->user_session['uid']))->find();

        if(empty($publishInfo)){
            $this->error_tips('需求异常请重试', U('Service/trade_special'));
        }

        if(empty($offer_info)){
            $offer_info_return = D('Service_offer')->where(array('publish_id'=>$_GET['publish_id'],'deliver_type'=>2))->find();
            if($offer_info_return){
                $this->error_tips('已有别的服务商接单。', U('Service/trade_special'));
            }
        }
        // dump($offer_info);
        $this->assign('offer_info',$offer_info);
        $catInfo = D('Service_category')->where(array('cid'=>$publishInfo['cid']))->field('cat_name')->find();
        $publishInfo['cat_name'] = $catInfo['cat_name'];

        if($publishInfo['catgory_type'] == 2){
            $cat_field_info = D('Service_user_publish_buy')->where(array('publish_id'=>$publishInfo['publish_id']))->find();
            $cat_field_info['img'] = array_filter(explode(';', $cat_field_info['img']));
        }else if($publishInfo['catgory_type'] == 3){
            $cat_field_info = D('Service_user_publish_give')->where(array('publish_id'=>$publishInfo['publish_id']))->find();
            $cat_field_info['img'] = array_filter(explode(';', $cat_field_info['img']));
        }
        $this->assign('cat_field_info',$cat_field_info);
        // dump($cat_field_info);
        $userinfo = D('User')->where(array('uid'=>$publishInfo['uid']))->field('phone,nickname')->find();
        if($userinfo['phone']){
            $publishInfo['phone'] = substr_replace($userinfo['phone'],'*****',3,5);
        }
        $publishInfo['nickname'] = $userinfo['nickname'];

        $offer_record_list = D('service_offer_record')->where(array('publish_id'=>$_GET['publish_id']))->order('rid desc')->select();
        $this->assign('offer_record_list',$offer_record_list);
        $this->assign('publishInfo',$publishInfo);
        $this->display();
    }

    // 需求详情 报价列表
    public function price_list(){
        if (empty($this->user_session)) {
            $this->error_tips('请先进行登录！', U('Login/index'));
        }
        $publishInfo = D('Service_user_publish')->where(array('publish_id'=>$_GET['publish_id']))->find();
        $catInfo = D('Service_category')->where(array('cid'=>$publishInfo['cid']))->field('cat_name,accept_time')->find();
        $publishInfo['cat_name'] = $catInfo['cat_name'];

        $publishId = $_GET['publish_id'] ?? 0;
        $supply = M('Deliver_supply')->where(['item' => 3, 'order_id' => $publishId])->find();

        if($publishInfo['catgory_type'] == 1){
            $publishInfo['cat_field'] = unserialize($publishInfo['cat_field']);
            foreach ($publishInfo['cat_field'] as $kk => $vv) {
                if($vv['type'] == 7){
                   $publishInfo['address_start'] = $vv['value']['address_start'];
                   $publishInfo['address_start_lng'] = $vv['value']['address_start_lng'];
                   $publishInfo['address_start_lat'] = $vv['value']['address_start_lat'];
                   $publishInfo['address_end'] = $vv['value']['address_end'];
                   $publishInfo['address_end_lng'] = $vv['value']['address_end_lng'];
                   $publishInfo['address_end_lat'] = $vv['value']['address_end_lat'];
                }
            }
            unset($publishInfo['cat_field']['cid']);

            $offer_list = D('')->table(array(C('DB_PREFIX').'service_offer'=>'so', C('DB_PREFIX').'service_provider'=>'u'))->where("so.publish_id= '".$publishInfo['publish_id']."' AND u.uid = so.p_uid")->field('so.offer_id,so.price,so.p_uid,so.status,u.avatar,u.phone,u.name')->select();
            foreach ($offer_list as $key => $value) {
                $offer_list[$key]['msg_list'] = D('Service_offer_message')->where(array('offer_id'=>$value['offer_id']))->field('message,type,add_time')->order('add_time asc')->select();
                $offer_list[$key]['msg_count'] = count($offer_list[$key]['msg_list']);
                if (empty($offer_list[$key]['avatar'])) {
                    // 没有头像取默认头像
                    $offer_list[$key]['avatar'] = C('config.site_url') . '/static/default.png';
                }
            }
            $this->assign('offer_list',$offer_list);
            $this->assign('offer_count',count($offer_list));
        }else if($publishInfo['catgory_type'] == 2){

            $cat_field_info = D('Service_user_publish_buy')->where(array('publish_id'=>$publishInfo['publish_id']))->find();
            $cat_field_info['img'] = array_filter(explode(';', $cat_field_info['img']));
            // $adress_info = D("User_adress")->get_adress($this->user_session['uid'],$cat_field_info['adress_id']);

            // $cat_field_info['arrival_time']= $publishInfo['add_time']+($cat_field_info['arrival_time']*60); //发布时间加上分钟
            // $cat_field_info['arrival_time']=$cat_field_info['arrival_time']/60; //小时送达

            // $cat_field_info['end_adress_name'] = $adress_info['adress'].$adress_info['detail'];
            //增值服务费用列表
            $cat_field_info['added_fee_list'] = json_decode($cat_field_info['added_fee_list'],true);

            if($publishInfo['deliver_type'] == 1){
                $offer_info = D('')->table(array(C('DB_PREFIX').'deliver_supply'=>'ds', C('DB_PREFIX').'deliver_user'=>'du'))->where("ds.order_id= '".$publishInfo['publish_id']."' AND du.uid = ds.uid AND ds.order_from in (4,5)")->field(true)->find();
                if (!empty($offer_info)) {
                    $offer_info['avatar'] = C('config.wechat_share_img') ? C('config.wechat_share_img') : C('config.site_logo');
                }
            }else{
                $offer_info = D('')->table(array(C('DB_PREFIX').'service_offer'=>'so', C('DB_PREFIX').'service_provider'=>'u'))->where("so.publish_id= '".$publishInfo['publish_id']."' AND u.uid = so.p_uid")->field('so.offer_id,so.price,so.p_uid,so.status,u.avatar,u.phone,u.name')->find();
            }
            $register = time() - $offer_info['create_time'];
            if($register > 86400){
                // 显示天
                $registers = $register / 86400;
                if((int)$registers < $registers){
                    $register_time = (int)$registers + 1 . '天';
                }else{
                    $register_time = (int)$registers . '天';
                }
            }else{
                // 显示小时
                $registers = $register / 86400;
                if((int)$registers < $registers){
                    $register_time = (int)$registers + 1 . '小时';
                }else{
                    $register_time = (int)$registers . '小时';
                }
            }
            $offer_info['register_time'] = $register_time;

            // $offer_info = D('')->table(array(C('DB_PREFIX').'service_offer'=>'so', C('DB_PREFIX').'service_provider'=>'u'))->where("so.publish_id= '".$publishInfo['publish_id']."' AND u.uid = so.p_uid")->field('so.offer_id,so.price,so.p_uid,so.status,u.avatar,u.phone,u.name')->find();
            $this->assign('offer_info',$offer_info);

            $this->assign('cat_field_info',$cat_field_info);

            $whereAry = [];
            $whereAry['publish_id'] = $_GET['publish_id'];
            if($supply['deliver_status'] == 1){
                $whereAry['offer_id'] = 0;
            }
            $offer_record_list = D('service_offer_record')->where($whereAry)->order('rid desc')->select();
            $this->assign('offer_record_list',$offer_record_list);

        }else if($publishInfo['catgory_type'] == 3){
            $catInfo['accept_time']*60*60;

            $cat_field_info = D('Service_user_publish_give')->where(array('publish_id'=>$publishInfo['publish_id']))->find();
            $cat_field_info['img'] = array_filter(explode(';', $cat_field_info['img']));
//            $offer_info = D('')->table(array(C('DB_PREFIX').'service_offer'=>'so', C('DB_PREFIX').'service_provider'=>'u'))->where("so.publish_id= '".$publishInfo['publish_id']."' AND u.uid = so.p_uid")->field('so.offer_id,so.price,so.p_uid,so.status,u.avatar,u.phone,u.name')->find();
            if($publishInfo['deliver_type'] == 1) {
                if ($publishInfo['is_third_deliver']) {
                    $p_log = D('Service_user_publish_log')->where(['type'=>1,'catgory_type'=>3,'publish_id'=>$publishId])->order('log_id desc')->find();
                    $offer_info = [];
                    if($p_log){
                        $offer_info = [
                            'phone' => $p_log['phone'],
                            'name' => $p_log['name']
                        ];
                    }
                } else {
                    $offer_info = D('')->table(array(C('DB_PREFIX') . 'deliver_supply' => 'ds', C('DB_PREFIX') . 'deliver_user' => 'du'))->where("ds.order_id='" . $publishInfo['publish_id'] . "' AND du.uid=ds.uid AND ds.order_from=3")->field(true)->find();
                }
                if (!empty($offer_info)) {
                    $offer_info['avatar'] = C('config.wechat_share_img') ? C('config.wechat_share_img') : C('config.site_logo');
                }
            }else{
                $offer_info = D('')->table(array(C('DB_PREFIX').'service_offer'=>'so', C('DB_PREFIX').'service_provider'=>'u'))->where("so.publish_id= '".$publishInfo['publish_id']."' AND u.uid = so.p_uid")->field('so.offer_id,so.price,so.p_uid,so.status,u.avatar,u.phone,u.name')->find();
            }
            // 任职时间
            $register = time() - $offer_info['create_time'];
            $register_time = '未知';
            if($register > 86400){
                // 显示天
                $registers = $register / 86400;
                if((int)$registers < $registers){
                    $register_time = (int)$registers + 1 . '天';
                }else{
                    $register_time = (int)$registers . '天';
                }
            }else{
                // 显示小时
                $registers = $register / 86400;
                if((int)$registers < $registers){
                    $register_time = (int)$registers + 1 . '小时';
                }else{
                    $register_time = (int)$registers . '小时';
                }
            }
            $offer_info['register_time'] = $register_time;
            // 配送员等级标签
            if (C('config.open_deliver_level')) {
                $now_level = D('Deliver_user')->get_user_level($offer_info['score']);
                $level_name = $now_level['name'];
            }else{
               $level_name = '';
            }
            $offer_info['level_name'] = $level_name;

            $this->assign('offer_info',$offer_info);

            // $start_adress_info = D("User_adress")->get_adress($this->user_session['uid'],$cat_field_info['start_adress_id']);
            // $cat_field_info['start_adress_name'] = $cat_field_info['start_adress_name'];

            // $end_adress_info = D("User_adress")->get_adress($this->user_session['uid'],$cat_field_info['end_adress_id']);
            // $cat_field_info['end_adress_name'] = $end_adress_info['end_adress_name'];

            //增值服务费用列表
            $cat_field_info['added_fee_list'] = json_decode($cat_field_info['added_fee_list'],true);
            $this->assign('cat_field_info',$cat_field_info);

            $whereAry = [];
            $whereAry['publish_id'] = $_GET['publish_id'];
            if($supply['deliver_status'] == 1){
                $whereAry['offer_id'] = 0;
            }
            $offer_record_list = D('service_offer_record')->where($whereAry)->order('rid desc')->select();
            $this->assign('offer_record_list',$offer_record_list);
        }

        //显示联系配送员
//        $publishId = $_GET['publish_id'] ?? 0;
        $imUrl = '';
        $tm = time();
        $expireSecond = 6 * 3600;
//        $supply = M('Deliver_supply')->where(['item' => 3, 'order_id' => $publishId])->find();
        if (in_array($supply['deliver_status'], [2, 3, 4]) || ($supply['deliver_status'] == 5 && $supply['finish_time'] + $expireSecond > $tm)) {
            //$imUrl = C('config.site_url') . '/packapp/project_message/pages/shop/groupConversation/index?order_type=paotui&username=user_' . $this->user_session['uid'] . '&order_id=' . $publishId;
            $imUrl = build_im_chat_url('user_' . $this->user_session['uid'],'deliver_'.$supply['uid'],'user2deliver');
        }
        $showDeliverUser = 1;//默认显示配送员信息
        //如果外卖没有配送员接单，只显示支付成功, 不显示配送员信息
        if($supply['deliver_status'] == 1){
            $showDeliverUser = 0;
        }
        $this->assign('show_deliver_user', $showDeliverUser);

        $this->assign('imUrl', $imUrl);
        $publishInfo['system_coupon_price'] = empty($publishInfo['system_coupon_price']) ? "0.00" : $publishInfo['system_coupon_price'];

        $now_time = time();
        $fetch_time = 0;
        if($publishInfo['catgory_type'] == 2){
            $service_user_publish_buy = M('Service_user_publish_buy')->field('arrival_time_info')->where(['publish_id'=>$publishInfo['publish_id']])->find();
            $fetch_time = strtotime($service_user_publish_buy['arrival_time_info']);

        }else if($publishInfo['catgory_type'] == 3){
            $service_user_publish_give = M('Service_user_publish_give')->field('fetch_time')->where(['publish_id'=>$publishInfo['publish_id']])->find();
            $fetch_time = strtotime($service_user_publish_give['fetch_time']);
        }

        $this->assign('hidePay',$fetch_time>0?($now_time>$fetch_time):false);
        $this->assign('publishInfo',$publishInfo);
        $this->display();
    }

    public function cancel_publish(){
        $res = D('service_user_publish')->where(array('publish_id'=>$_POST['publish_id'],'uid'=>$this->user_session['uid']))->save(array('status'=>10));
        if($res){
            exit(json_encode(array('error'=>1,'msg'=>'需求已取消，您接受不到新的报价了。')));
        }else{
            exit(json_encode(array('error'=>2,'msg'=>'取消失败，请重试。')));
        }
    }

    // 需求详情 商户报价
    public function detail(){
        $publishInfo = D('Service_user_publish')->where(array('publish_id'=>$_GET['publish_id']))->find();
        if (10==$publishInfo['status']) {
            $this->error_tips('已暂停报价',U('Service/price_list',array('publish_id'=>$_GET['publish_id'])));
        }

        $offerInfo = D('service_offer')->where(array('uid'=>$publishInfo['uid'],'p_uid'=>$this->user_session['uid'],'publish_id'=>$_GET['publish_id']))->find();
        if(is_array($offerInfo)){
            $this->error_tips('已报价请联系客户',U('Service/offer_detail',array('offer_id'=>$offerInfo['offer_id'])));
        }
        $publishInfo['cat_field'] = unserialize($publishInfo['cat_field']);
        foreach ($publishInfo['cat_field'] as $kk => $vv) {
            if($vv['type'] == 7){
               $publishInfo['address_start'] = $vv['value']['address_start'];
               $publishInfo['address_start_lng'] = $vv['value']['address_start_lng'];
               $publishInfo['address_start_lat'] = $vv['value']['address_start_lat'];
               $publishInfo['address_end'] = $vv['value']['address_end'];
               $publishInfo['address_end_lng'] = $vv['value']['address_end_lng'];
               $publishInfo['address_end_lat'] = $vv['value']['address_end_lat'];
            }
        }
        unset($publishInfo['cat_field']['cid']);
        $catInfo = D('Service_category')->where(array('cid'=>$publishInfo['cid']))->field('cat_name')->find();
        $publishInfo['cat_name'] = $catInfo['cat_name'];

        $userinfo = '';
        $userinfo = D('User')->where(array('uid'=>$publishInfo['uid']))->field('phone,nickname')->find();
        if($userinfo['phone']){
            $publishInfo['phone'] = substr_replace($userinfo['phone'],'*****',3,5);
            $publishInfo['old_phone'] = $userinfo['phone'];
        }
        $publishInfo['nickname'] = $userinfo['nickname'];

        $this->assign('publishInfo',$publishInfo);
        // dump($publishInfo);
        $this->display();
    }

    // 发送报价
    public function send_offer(){
        $publishInfo = D("Service_user_publish")->where(array('publish_id'=>$_POST['publish_id']))->field('publish_id,uid,cid')->find();
        $offer_info = D('Service_offer')->where(array('publish_id'=>$_POST['publish_id'],'p_uid'=>$this->user_session['uid']))->find();
        if($offer_info){
            exit(json_encode(array('error'=>3,'msg'=>'报价失败，您已经对此条需求报价了。')));
        }
        $offer_data['publish_id'] = $_POST['publish_id'];
        $offer_data['price'] = $_POST['price'];
        $offer_data['uid'] = $publishInfo['uid'];
        $offer_data['cid'] = $_POST['cid'];
        $offer_data['add_time'] = time();
        $offer_data['order_sn'] = date("ymdHis").rand(10,99).sprintf("%08d",$this->user_session['uid']);
        $offer_data['p_uid'] = $this->user_session['uid'];
        $res = D('Service_offer')->data($offer_data)->add();
        if($res){
            //抢单人数增加1位
            D("Service_user_publish")->where(array('publish_id'=>$_POST['publish_id']))->setInc('offer_sum');

            //模板消息
            $user_info = D("User")->where(array('uid'=>$publishInfo['uid']))->field('uid,openid,nickname')->find();
            if ($user_info['openid']) {
                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                $href = C('config.site_url') . '/wap.php?g=Wap&c=Service&a=price_list&publish_id='.$_POST['publish_id'];

                $model->sendTempMsg('TM00017',
                    array('href' => $href,
                        'wecha_id' => $user_info['openid'],
                        'first' => $user_info['nickname'] . '已有服务商给您的需求发送了报价！',
                        'OrderSn' => $offer_data['order_sn'],
                        'OrderStatus' => '待处理',
                        'remark' => '请您及时处理！'));
            }

            exit(json_encode(array('error'=>1,'msg'=>'报价成功')));
        }else{
            exit(json_encode(array('error'=>2,'msg'=>'报价失败，请重新尝试。')));
        }
    }

    //联系中的需求
    public function trade_contact(){
        if (empty($this->user_session)) {
            $this->error_tips('请先进行登录！', U('Login/index'));
        }
        $uid = $this->user_session['uid'];
        $publishList = D('')
            ->table(array(C('DB_PREFIX').'service_offer'=>'so', C('DB_PREFIX').'service_user_publish'=>'sup',C('DB_PREFIX').'service_category'=>'sc',C('DB_PREFIX').'user'=>'u'))
            ->where("so.p_uid= '".intval($uid)."' AND sup.publish_id = so.publish_id  AND sc.cid = sup.cid AND u.uid = so.uid AND so.status != 4 AND so.status != 7 AND sup.status IN (1,2,3,4,8,9)")
            ->field('so.*,sup.*,sc.cat_name,u.phone,u.nickname')
            ->order('so.add_time desc')
            ->select();

        // echo D('')->getlastsql();
        foreach ($publishList as $key => $value) {
            if($value['catgory_type'] == 1){
                $publishList[$key]['cat_field'] = unserialize($value['cat_field']);
            }elseif ($value['catgory_type'] == 2) {
                $publishList[$key]['cat_field'] =  D('Service_user_publish_buy')->where(array('publish_id'=>$value['publish_id']))->find();
            }elseif ($value['catgory_type'] == 3) {
                $publishList[$key]['cat_field'] =  D('Service_user_publish_give')->where(array('publish_id'=>$value['publish_id']))->find();
            }
            if($value['phone']){
                if($value['status']==1 || $value['status'] == 11){
                    $publishList[$key]['phone'] = substr_replace($value['phone'],'*****',3,5);
                }
            }
        }
        $this->assign('publishList',$publishList);
        // dump($publishList);
        $this->display();
    }
    // 已完成需求
    public function trade_over(){
        if (empty($this->user_session)) {
            $this->error_tips('请先进行登录！', U('Login/index'));
        }
        $publishList = D('')->table(array(C('DB_PREFIX').'service_offer'=>'so', C('DB_PREFIX').'service_user_publish'=>'sup',C('DB_PREFIX').'service_category'=>'sc',C('DB_PREFIX').'user'=>'u'))->where("so.p_uid= '".intval($this->user_session['uid'])."' AND sup.publish_id = so.publish_id  AND sc.cid = sup.cid AND u.uid = so.uid AND so.status IN ('4','7')")->field('so.*,sup.*,sc.cat_name,u.phone,u.nickname')->order('so.add_time desc')->select();

        foreach ($publishList as $key => $value) {
            if($value['catgory_type'] == 1){
                $publishList[$key]['cat_field'] = unserialize($value['cat_field']);
            }elseif ($value['catgory_type'] == 2) {
                $publishList[$key]['cat_field'] =  D('Service_user_publish_buy')->where(array('publish_id'=>$value['publish_id']))->find();
            }elseif ($value['catgory_type'] == 3) {
                $publishList[$key]['cat_field'] =  D('Service_user_publish_give')->where(array('publish_id'=>$value['publish_id']))->find();
            }

        }
        $this->assign('publishList',$publishList);
        $this->display();
    }

    // 商户报价详情
    public function offer_detail(){
        if (empty($this->user_session)) {
            $this->error_tips('请先进行登录！', U('Login/index'));
        }
        $publishInfo = D('')->table(array(C('DB_PREFIX').'service_offer'=>'so', C('DB_PREFIX').'service_user_publish'=>'sup',C('DB_PREFIX').'service_category'=>'sc',C('DB_PREFIX').'user'=>'u'))->where("so.offer_id = '".intval($_GET['offer_id'])."' AND so.p_uid= '".intval($this->user_session['uid'])."' AND sup.publish_id = so.publish_id AND sc.cid = sup.cid AND u.uid = so.uid")->field('sup.*,so.*,sc.cat_name,u.phone,u.nickname,u.avatar')->order('so.add_time desc')->find();

        $publishInfo['cat_field'] = unserialize($publishInfo['cat_field']);
        foreach ($publishInfo['cat_field'] as $kk => $vv) {
            if($vv['type'] == 7){
                $publishInfo['address_start'] = $vv['value']['address_start'];
                $publishInfo['address_start_lng'] = $vv['value']['address_start_lng'];
                $publishInfo['address_start_lat'] = $vv['value']['address_start_lat'];
                $publishInfo['address_end'] = $vv['value']['address_end'];
                $publishInfo['address_end_lng'] = $vv['value']['address_end_lng'];
                $publishInfo['address_end_lat'] = $vv['value']['address_end_lat'];
            }
        }
        unset($publishInfo['cat_field']['cid']);
        if($publishInfo['status'] == 7){
            $evaluate_info = D('service_offer_evaluate')->where(array('offer_id'=>$_GET['offer_id']))->find();
            $this->assign('evaluate_info',$evaluate_info);
        }
        if($publishInfo['phone']){
            if($publishInfo['status']==1 || $publishInfo['status'] == 11){
                $publishInfo['old_phone'] = $publishInfo['phone'];
                $publishInfo['phone'] = substr_replace($publishInfo['phone'],'*****',3,5);
            }
        }

        $this->assign('publishInfo',$publishInfo);

        $offerMsgList = D('Service_offer_message')->where(array('offer_id'=>$_GET['offer_id']))->select();
        $this->assign('offerMsgList',$offerMsgList);
        $this->display();
    }

    // 用户支付
    public function offer_pay(){
        $offerInfo = D('Service_offer')->where(array('uid'=>$this->user_session['uid'],'offer_id'=>intval($_POST['offer_id'])))->find();
        // 如果已经付款提示已付款
        if (!empty($offerInfo) && $offerInfo['status'] == 2) {
            exit(json_encode(array('error'=>4,'msg'=>'已支付待服务')));
        }
        $userInfo = D('User')->where(array('uid'=>$this->user_session['uid']))->field('uid,now_money')->find();

        $cat_name_info = D('')->table(array(C('DB_PREFIX').'service_user_publish'=>'sup', C('DB_PREFIX').'service_category'=>'sc'))->where("sup.publish_id= '".$offerInfo['publish_id']."' AND sup.cid = sc.cid")->field('sc.cat_name')->select();

        if($offerInfo['price'] > $userInfo['now_money']){
            exit(json_encode(array('error'=>3,'msg'=>'当前余额不足请充值')));
        }

        $res = D('User')->where(array('uid'=>$this->user_session['uid']))->setDec('now_money',$offerInfo['price']);
        if($res){
            D('Service_offer')->where(array('offer_id'=>$_POST['offer_id']))->data(array('status'=>2))->save();
            D('Service_user_publish')->where(array('publish_id'=>$offerInfo['publish_id']))->data(array('status'=>2))->save();
            D('User_money_list')->add_row($offerInfo['uid'],2,$offerInfo['price'],"我购买".$cat_name_info['cat_name']."服务 ".$offerInfo['price'].C('config.Currency_txt')."");

            $providerUserInfo = D('User')->where(array('uid'=>$offerInfo['p_uid']))->find();
            // 订单状态提醒 用户付款
            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
            $href = C('config.site_url').'/wap.php?g=Wap&c=Service&a=offer_detail&offer_id='.intval($_POST['offer_id']);
            $model->sendTempMsg('TM00017', array('href' => $href,
                    'wecha_id' => $providerUserInfo['openid'],
                    'first' => '用户付款通知',
                    'OrderSn' => $offerInfo['order_sn'],
                    'OrderStatus' =>$this->user_session['nickname'].'已付款',
                    'remark' =>'请尽快服务'));

            exit(json_encode(array('error'=>1,'msg'=>'支付成功')));
        }else{
            exit(json_encode(array('error'=>2,'msg'=>'支付失败请重试')));
        }

    }

    //服务商确认服务
    public function confirm_service(){
        $offerInfo = D('Service_offer')->where(array('offer_id'=>intval($_POST['offer_id'])))->find();
        $res = D('Service_offer')->where(array('offer_id'=>$_POST['offer_id']))->data(array('status'=>3))->save();
        D('Service_user_publish')->where(array('publish_id'=>$offerInfo['publish_id']))->data(array('status'=>3))->save();
        if($res){

            $offerInfo = D('Service_offer')->where(array('offer_id'=>$_POST['offer_id']))->find();
            // 订单状态提醒 服务商确认服务
            $publishUserInfo = D('User')->where(array('uid'=>$offerInfo['uid']))->find();
            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
            $href = C('config.site_url').'/wap.php?c=Service&a=price_list&publish_id='.$offerInfo['publish_id'];
            $model->sendTempMsg('TM00017', array('href' => $href,
                    'wecha_id' => $publishUserInfo['openid'],
                    'first' => '服务商服务通知',
                    'OrderSn' => $offerInfo['order_sn'],
                    'OrderStatus' =>$this->user_session['nickname'].'已服务',
                    'remark' =>'请尽快确认'));

            exit(json_encode(array('error'=>1,'msg'=>'服务已完成')));
        }else{
            exit(json_encode(array('error'=>2,'msg'=>'完成服务失败请重试')));
        }
    }

    //用户确认服务
    public function user_confirm_service(){
        $offerInfo = D('Service_offer')->where(array('offer_id'=>$_POST['offer_id']))->find();
        $publishInfo = D('Service_user_publish')->where(array('publish_id'=>$offerInfo['publish_id']))->field('publish_id,cid,uid')->find();
        $categoryInfo = D('Service_category')->where(array('cid'=>$publishInfo['cid']))->field('cid,cat_name,cut_proportion,return_integral_proportion')->find();
        $cut_proportion = $categoryInfo['cut_proportion']/100;
        $poundage = $offerInfo['price']*$cut_proportion;
        $price = round($offerInfo['price']-$poundage, 2);
        $res = D('Service_offer')->where(array('offer_id'=>$_POST['offer_id']))->data(array('status'=>4))->save();
        D('Service_user_publish')->where(array('publish_id'=>$offerInfo['publish_id']))->data(array('status'=>4))->save();
        if($res){
            D('User')->where(array('uid'=>$offerInfo['p_uid']))->setInc('now_money',$price);
            D('User_money_list')->add_row($offerInfo['p_uid'],1,$price,"用户付款 ".$offerInfo['price'].C('config.Currency_txt').",扣除手续费".round($poundage,2).C('config.Currency_txt'));
            D('Service_provider')->where(array('uid'=>$offerInfo['p_uid']))->setInc('total_amount',$price);
            $providerUserInfo = D('User')->where(array('uid'=>$offerInfo['p_uid']))->find();
            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
            $href = C('config.site_url').'/wap.php?g=Wap&c=Service&a=offer_detail&offer_id='.intval($_POST['offer_id']);
            $model->sendTempMsg('TM00017', array('href' => $href,
                    'wecha_id' => $providerUserInfo['openid'],
                    'first' => '用户确认服务通知',
                    'OrderSn' => $offerInfo['order_sn'],
                    'OrderStatus' =>$this->user_session['nickname'].'已经确认您完成了服务',
                    'remark' =>'查看详情'));
            exit(json_encode(array('error'=>1,'msg'=>'服务已完成')));
        }else{
            exit(json_encode(array('error'=>2,'msg'=>'完成服务失败请重试')));
        }
    }

    // 用户申请退款
    public function offer_refund(){
//        $offerInfo = D('Service_offer')->where(array('offer_id'=>intval($_POST['offer_id'])))->find();
//        D('Service_user_publish')->where(array('publish_id'=>$offerInfo['publish_id']))->data(array('status'=>5))->save();
        $res = D('Service_offer')->where(array('offer_id'=>$_POST['offer_id']))->data(array('status'=>5,'reason'=>$_POST['reason']))->save();
        if($res){
            exit(json_encode(array('error'=>1,'msg'=>'用户申请退款成功！')));
        }else{
            exit(json_encode(array('error'=>2,'msg'=>'用户申请退款失败请重试。')));
        }
    }

    // 服务商回复用户退款
    public function refund_reply(){
        $res = D('Service_offer')->where(array('offer_id'=>$_POST['offer_id']))->data(array('status'=>$_POST['status']))->save();
        $offerInfo = D('Service_offer')->where(array('offer_id'=>intval($_POST['offer_id'])))->find();
        D('Service_user_publish')->where(array('publish_id'=>$offerInfo['publish_id']))->data(array('status'=>$_POST['status']))->save();

        $offerInfo = D('Service_offer')->where(array('offer_id'=>$_POST['offer_id']))->find();

        if($res){
            if($_POST['status'] == 6){
                D('User')->where(array('uid'=>$offerInfo['uid']))->setInc('now_money',$offerInfo['price']);
                D('User_money_list')->add_row($offerInfo['uid'],1,$offerInfo['price'],"服务商退款 ".$offerInfo['price'].C('config.Currency_txt')."");

                $publishUserInfo = D('User')->where(array('uid'=>$offerInfo['uid']))->find();
                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                $href = C('config.site_url').'/wap.php?c=Service&a=price_list&publish_id='.$offerInfo['publish_id'];
                $model->sendTempMsg('TM00017', array('href' => $href,
                        'wecha_id' => $publishUserInfo['openid'],
                        'first' => '服务商同意退款',
                        'OrderSn' => $offerInfo['order_sn'],
                        'OrderStatus' =>$this->user_session['nickname'].'同意退款',
                        'remark' =>'有问题请联系服务商'));
            }else{

                $publishUserInfo = D('User')->where(array('uid'=>$offerInfo['uid']))->find();
                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                $href = C('config.site_url').'/wap.php?c=Service&a=price_list&publish_id='.$offerInfo['publish_id'];
                $model->sendTempMsg('TM00017', array('href' => $href,
                        'wecha_id' => $publishUserInfo['openid'],
                        'first' => '服务商拒绝退款',
                        'OrderSn' => $offerInfo['order_sn'],
                        'OrderStatus' =>$this->user_session['nickname'].'拒绝退款',
                        'remark' =>'有问题请联系服务商'));
            }
            exit(json_encode(array('error'=>1,'msg'=>'操作成功')));
        }else{
            exit(json_encode(array('error'=>2,'msg'=>'操作失败请重试。')));
        }
    }

    // 发送留言
    public function send_msg(){
        $_POST['add_time'] = time();
        $res = D('Service_offer_message')->data($_POST)->add();
        if($res){
            if($_POST['type'] == 1){
                $href = C('config.site_url').'/wap.php?g=Wap&c=Service&a=offer_detail&offer_id='.$_POST['offer_id'];
                $userInfo = D('User')->where(array('uid'=>$_POST['p_uid']))->find();
            }else{
                $href = C('config.site_url').'/wap.php?g=Wap&c=Service&a=price_list&publish_id='.$_POST['publish_id'];
                $userInfo = D('User')->where(array('uid'=>$_POST['uid']))->find();
            }

            // 留言模板消息
			$model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
			$model->sendTempMsg('OPENTM405462911', array('href' => $href, 'wecha_id' => $userInfo['openid'], 'first' => '您好，您有一条新的留言\n', 'keyword1' => '留言提醒', 'keyword2' =>'未查看','keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']), 'keyword4' => $userInfo['nickname'], 'remark' => '\n请点击查看详细信息！'));

            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
//            $model->sendTempMsg('OPENTM203574543',
//                array('href' => $href,
//                    'wecha_id' => $userInfo['openid'],
//                    'first' => '您好，您有一条新的留言\n',
//                    'keyword1' => $userInfo['nickname'],
//                    'keyword2' => date('H时i分',$_SERVER['REQUEST_TIME']),
//                    'keyword3' => '点击查看',
//                    'remark' => '\n请点击查看详细信息！'));

            $model->sendTempMsg('TM00017',
                array('href' => $href,
                    'wecha_id' => $userInfo['openid'],
                    'first' => '您好，您有一条新的留言\n！',
                    'OrderSn' => '-',
                    'OrderStatus' => '待处理',
                    'remark' => '\n请点击查看详细信息！'));


            exit(json_encode(array('error'=>1,'msg'=>'留言发布成功！')));
        }else{
            exit(json_encode(array('error'=>2,'msg'=>'留言发布失败！')));
        }
    }

    // 修改价格
    public function save_price(){
        $res = D('Service_offer')->where(array('p_uid'=>$this->user_session['uid'],'offer_id'=>intval($_POST['offer_id'])))->data(array('price'=>$_POST['price']))->save();
        if($res){
            $offer_info = D('Service_offer')->field('uid, publish_id')->where(array('p_uid'=>$this->user_session['uid'],'offer_id'=>intval($_POST['offer_id'])))->find();
            //模板消息
            $user_info = D("User")->where(array('uid'=>$offer_info['uid']))->field('uid,openid,nickname')->find();
            if ($user_info['openid']) {
                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                $href = C('config.site_url') . '/wap.php?g=Wap&c=Service&a=price_list&publish_id='.$offer_info['publish_id'];

                $model->sendTempMsg('TM00017',
                    array('href' => $href,
                        'wecha_id' => $user_info['openid'],
                        'first' => $user_info['nickname'] . '已有服务商给您的需求重新发布新报价！',
                        'OrderSn' => $offer_info['order_sn'],
                        'OrderStatus' => '待处理',
                        'remark' => '请您及时处理！'));
            }
            exit(json_encode(array('error'=>1,'msg'=>'修改成功！')));
        }else{
            exit(json_encode(array('error'=>2,'msg'=>'修改失败！')));
        }
    }


    //通过坐标获取城市  通过城市名字查询数据
    public function cityMatching(){
        $area_info = D('Area')->cityMatching($_GET['lat'], $_GET['lng']);
        if ($area_info && $area_info['area_info']) {
            $city_name = $area_info['area_info']['city_name'];
            $district_name = $area_info['area_info']['area_name'];
        } else {
            $url = 'http://api.map.baidu.com/geocoder/v2/?output=json&ak=' . $this->config['baidu_map_ak'] . '&location='.$_GET['lat'].','.$_GET['lng'];
            import('ORG.Net.Http');
            $http = new Http();
            $result = $http->curlGet($url);
            $result = json_decode($result,true);
            $city_name = $result['result']['addressComponent']['city'];
            // 查询一下所在区,东莞没有区只有街道
            $district_name = $result['result']['addressComponent']['district']?:$result['result']['addressComponent']['town'];
			
			$full_city_name = trim($city_name);
            $long   =   strlen($city_name);
            if($long >= 7){
                $city_name    =   str_replace('市','',$city_name);
                $city_name    =   str_replace('地区','',$city_name);
                $city_name    =   str_replace('特别行政区','',$city_name);
                $city_name    =   str_replace('特別行政區','',$city_name);
                $city_name    =   str_replace('蒙古自治州','',$city_name);
                $city_name    =   str_replace('回族自治州','',$city_name);
                $city_name    =   str_replace('柯尔克孜自治州','',$city_name);
                $city_name    =   str_replace('哈萨克自治州','',$city_name);
                $city_name    =   str_replace('土家族苗族自治州','',$city_name);
                $city_name    =   str_replace('藏族羌族自治州','',$city_name);
                $city_name    =   str_replace('傣族自治州','',$city_name);
                $city_name    =   str_replace('布依族苗族自治州','',$city_name);
                $city_name    =   str_replace('苗族侗族自治州','',$city_name);
                $city_name    =   str_replace('壮族苗族自治州','',$city_name);
                $city_name    =   str_replace('黔东南苗族侗族自治州','',$city_name);
                $city_name    =   str_replace('澳门','澳門',$city_name);
                $city_name    =   str_replace('朝鲜族自治州','',$city_name);
                $city_name 	  =   str_replace('哈尼族彝族自治州','',$city_name);
                $city_name 	  =   str_replace('傣族景颇族自治州','',$city_name);
                $city_name 	  =   str_replace('藏族自治州','',$city_name);
                $city_name 	  =   str_replace('彝族自治州','',$city_name);
                $city_name 	  =   str_replace('白族自治州','',$city_name);
                $city_name 	  =   str_replace('傈僳族自治州','',$city_name);
            }
        }
        $database_area = D('Area');
        $database_field = '`area_id`,`area_name`,`area_url`';
        $condition_all_city['area_name'] = [['eq', $full_city_name],['eq',  $city_name], 'OR'];
        $condition_all_city['area_type'] = 2;
        $condition_all_city['is_open'] = 1;
        $oCity = $database_area->field($database_field)->where($condition_all_city)->find();
        if(!$oCity){
            exit(json_encode(array('error'=>2,'msg'=>'当前城市未开启，请选择别的城市')));
        }
        $condition_all_district['area_name'] = $district_name;
        $condition_all_district['area_type'] = 3;
        $condition_all_district['is_open'] = 1;
        $oDistrict = $database_area->field($database_field)->where($condition_all_district)->find();
        if(!$oDistrict){
            exit(json_encode(array('error'=>2,'msg'=>'当前城市区域未开启，请选择别的城市或区域')));
        }
        if($oCity){
            exit(json_encode(array('error'=>1,'msg'=>'获取成功','area_id'=>$oCity['area_id'],'area_name'=>$oCity['area_name'], 'now_area_id' => $oDistrict['area_id'])));
        }else{
            exit(json_encode(array('error'=>2,'msg'=>'当前城市未开启，请选择别的城市')));
        }

    }


    public function service_evaluate(){
        $offerInfo = D('Service_offer')->where(array('uid'=>$this->user_session['uid'],'offer_id'=>intval($_GET['offer_id'])))->find();
        if(!is_array($offerInfo)){

            $this->error_tips('数据异常请重试！');
        }
        $this->assign('offerInfo',$offerInfo);
        $this->display();
    }

    public function service_evaluate_data(){
        if(IS_POST){
            $data = $_POST;
            $data['add_time'] = time();
            $data['uid'] = $this->user_session['uid'];
            $res = D('Service_offer_evaluate')->data($data)->add();
            if($res){
                D('Service_offer')->where(array('offer_id'=>$_POST['offer_id']))->data(array('status'=>7))->save();
                exit(json_encode(array('error'=>1,'msg'=>'评论成功','url'=>U('Service/price_list',array('publish_id'=>$_POST['publish_id'])))));
            }else{
                exit(json_encode(array('error'=>2,'msg'=>'评论失败请重试！')));
            }
        }
    }


    function distance($lat1, $lng1, $lat2, $lng2, $miles = true) {
        $pi80 = M_PI / 180;
        $lat1 *= $pi80;
        $lng1 *= $pi80;
        $lat2 *= $pi80;
        $lng2 *= $pi80;
        $r = 6372.797; // mean radius of Earth in km
        $dlat = $lat2 - $lat1;
        $dlng = $lng2 - $lng1;
        $a = sin($dlat/2)*sin($dlat/2)+cos($lat1)*cos($lat2)*sin($dlng/2)*sin($dlng/2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $km = $r * $c;
        return ($miles ? ($km * 0.621371192) : $km);
    }


    /**
    * 计算两组经纬度坐标 之间的距离
    * params ：lat1 纬度1； lng1 经度1； lat2 纬度2； lng2 经度2； len_type （1:m or 2:km);
    * return m or km
    */
    function GetDistance($lat1, $lng1, $lat2, $lng2, $len_type = 2, $decimal = 2){
        $EARTH_RADIUS = 6378.137; //地球半径
        $PI = 3.1415926;
        $radLat1 = $lat1 * $PI / 180.0;
        $radLat2 = $lat2 * $PI / 180.0;
        $a = $radLat1 - $radLat2;
        $b = ($lng1 * $PI / 180.0) - ($lng2 * $PI / 180.0);
        $s = 2 * asin(sqrt(pow(sin($a/2),2) + cos($radLat1) * cos($radLat2) * pow(sin($b/2),2)));
        $s = $s * $EARTH_RADIUS;
        $s = round($s * 1000);
        if ($len_type > 1){
            $s /= 1000;
        }
        return round($s,2);
    }


    // 计算两个收货地址的距离 帮我送
    public function ajax_distance(){
        //计算配送距离
        $distance = 0;
        // 去除前后空格
        $start_lat = trim($_POST['start_lat']);
        $start_lng = trim($_POST['start_lng']);
        $end_lat = trim($_POST['end_lat']);
        $end_lng = trim($_POST['end_lng']);
        if (C('config.service_is_riding_distance') && C('config.baidu_map_ak')) {
            import('@.ORG.longlat');
            $longlat_class = new longlat();
            $distance = $longlat_class->getRidingDistance($start_lat,$start_lng,$end_lat,$end_lng);
            if ($distance == -1) {
                $this->returnCode('50000008');
            }
        }
        $distance || $distance = getDistance($start_lat,$start_lng,$end_lat,$end_lng);
        $distance = $distance / 1000;
        exit(json_encode(array('destance_sum'=>$distance)));
    }

    // 计算两个收货地址的距离 帮我买
    public function ajax_buy_distance(){
        //计算配送距离
        $distance = 0;
        // 去除前后空格
        $start_lat = trim($_POST['start_lat']);
        $start_lng = trim($_POST['start_lng']);
        $end_lat = trim($_POST['end_lat']);
        $end_lng = trim($_POST['end_lng']);
        if (C('config.service_is_riding_distance') && C('config.baidu_map_ak')) {
            import('@.ORG.longlat');
            $longlat_class = new longlat();
            $distance = $longlat_class->getRidingDistance($start_lat,$start_lng,$end_lat,$end_lng);
            if ($distance == -1) {
                $this->returnCode('50000008');
            }
        }
        $distance || $distance = getDistance($start_lat,$start_lng,$end_lat,$end_lng);
        $distance = $distance / 1000;
        exit(json_encode(array('destance_sum'=>$distance)));
    }

    // 查询服务快派订单
    public function service_plat_order() {
        $publish_id = $_POST['publish_id'] + 0;
        if (empty($publish_id)) {
            $this->returnCode('50000004');
        }

        $publishOrder = D('Service_user_publish')->where(['publish_id'=>$_POST['publish_id']])->find();
        if(empty($publishOrder)){
            $this->returnCode('50000004');
        }

        $where = [
            'business_type' => 'service', 
            'business_id' => $publish_id
        ];

        if($publishOrder['is_voice'] && $publishOrder['voice_status'] == 1){// 语音发单尾款支付
            $where['is_final_pay'] = 1;
        }
        $order_info = M('Plat_order')->field('order_id')->where($where)->find();
        if (empty($order_info)) {
            $this->returnCode('10100001');
        }
        exit(json_encode(array('order_id'=>$order_info['order_id'])));
    }


    // 服务快派 帮我买帮我送 在未接单之前 用户 可取消订单-然后原路自行退款
    public function service_order_refund() {
        if (empty($this->user_session)) {
            exit(json_encode(array('error'=>3,'msg'=>'请先进行登录！', 'url' => U('Login/index'))));
        }
        $uid = $this->user_session['uid'];
        $publish_id = $_POST['publish_id'] + 0;
        if (empty($publish_id)) {
            $this->returnCode('50000004');
        }

        $publish_info = D('Service_user_publish')->field('status, order_sn, catgory_type, is_voice, voice_status, is_third_deliver')->where(array('publish_id' => $publish_id))->find();
        if ($publish_info && $publish_info['catgory_type'] == 1) {
            exit(json_encode(array('error'=>1,'msg'=>'只有帮我买和帮我送订单才可取消！')));
        }
        
        //已抢单的订单不能取消
        $deliver_supply = D('Deliver_supply')->where(['item' => 3, 'order_id' => $publish_id])->find();
        if($deliver_supply&&$deliver_supply['uid']>0||$deliver_supply['deliver_status']>1){
            exit(json_encode(array('error'=>1,'msg'=>'配送员已接单无法取消！')));
        }
        
        $session_name = 'user_'.$uid.'_'.$publish_id;
        $lockRes = setLock($session_name,5);
        if ($lockRes['error_no']) {
            exit(json_encode(array('error'=>1,'msg'=>$lockRes['error_msg'])));
        }
        // 查询支付订单
        $plat_where = [
            'business_type' => 'service', 
            'business_id' => $publish_id
        ];

        if($publish_info['is_voice'] && $publish_info['voice_status']==20){// 语音发单 已付尾款只退尾款不退基础费用
            $plat_where['is_final_pay'] = 1;
        }

        $order_info = M('Plat_order')->field('order_id')->where($plat_where)->find();
        if (empty($order_info)) {
            $this->returnCode('10100001');
        }
        if ($order_info && $order_info['is_refund'] == 1) {
            $this->returnCode('10100001');
            exit(json_encode(array('error'=>2,'msg'=>'订单已经退款！')));
        }
        if (($publish_info && !$publish_info['is_voice'] && $publish_info['status'] != 2) || ($publish_info && $publish_info['is_voice'] && ($publish_info['voice_status'] == 0 || $publish_info['status'] > 2))) {
            $publish_msg = array(
                1 => '已发布待付款',
                3 => '已服务待确认',
                4 => '已经完成',
                5 => '申请退款中',
                6 => '已经退款成功',
                7 => '服务已完成且评价成功',
                8 => '等待取货',
                9 => '配送中',
                10 => '用户已取消',
                11 => '服务过期',
                12 => '过期退款',
                13 => '退款失败',
            );
            if ($publish_info['status'] == 13) {
                exit(json_encode(array('error'=>2,'msg'=>'当前订单'.$publish_msg[$publish_info['status']].', 可以联系管理员！')));
            }
            exit(json_encode(array('error'=>2,'msg'=>'当前订单'.$publish_msg[$publish_info['status']].',不可取消订单！')));
        }
        $refund = $uid . '_' . $publish_id;
        if ($this->user_order_refund[$refund]) {
            exit(json_encode(array('error'=>2,'msg'=>'当前正在取消订单，请勿重复点击！')));
        }
        $this->user_order_refund[$refund] = true;
        // 进行原路退款
        $plat_info = D('Plat_order')->where($plat_where)->find();
        $refund = array();
//        $payment_money_order = M('Payment_money_order')->where(array('order_id'=>$plat_info['order_id'],'order_type'=>'plat','pay_type'=>$plat_info['pay_type'],'paid_money'=>array('gt',0)))->find();
        $payment_money_order = M('Payment_money_order')->order('id DESC')->where(array('order_id'=>$plat_info['order_id'],'orderid'=> $plat_info['orderid'],'order_type'=>'plat','pay_type'=>$plat_info['pay_type'],'paid_money'=>array('gt',0)))->find();

        if($plat_info['pay_money'] > 0 && ($plat_info['is_refund'] == 0 || $plat_info['is_refund'] == 4) && $payment_money_order && ($payment_money_order['pay_method'] == 'wft' || $payment_money_order['pay_method'] == 'paylinx' || $payment_money_order['pay_method'] == 'wepayez' || $payment_money_order['pay_method'] == 'weikebaba' || $payment_money_order['pay_method'] == 'nong')){

            if($plat_info['pay_money'] == $payment_money_order['payment_money']){
                //如果是全退，那就把用户实际支付的钱一起退了吧。包括支付手续费
                $plat_info['pay_money'] = $payment_money_order['paid_money'];
            }
            $plat_info['payment_money'] = $plat_info['pay_money'];
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
            if($payment_money_order['pay_method'] == 'nong'){
                $pay_method = 'nong';
            }
            import('@.ORG.LowFeePay');
            $lowfeepay = new LowFeePay($pay_method);
            $plat_info['order_type'] = 'service';
            $plat_info['orderNo'] = $payment_money_order['orderNo'];
            if($plat_info['mer_id'] && $payment_money_order['pay_method'] == 'wft'){
                $mer_juhe = M('Merchant_juhe_config')->where(array('mer_id'=>$plat_info['mer_id']))->find();
                if(!empty($mer_juhe)){
                    $lowfeepay->userId =$mer_juhe['userid'];
                    $plat_info['is_own'] =1 ;
                    $plat_info['orderNo'] =  $plat_info['orderNo'].'_1';
                }

            }elseif($plat_info['mer_id'] && $payment_money_order['pay_method'] == 'weikebaba'){
                $mer_juhe = M('Weikebaba')->where(array('mer_id'=>$plat_info['mer_id']))->find();
                if(!empty($mer_juhe)){
                    $lowfeepay->user_id = $mer_juhe['user_id'];
                    $lowfeepay->key = $mer_juhe['key'];
                    $plat_info['is_own'] =1 ;
                }

            }
            $go_refund_param= $lowfeepay->refund($plat_info);
            $data_plat_order['order_id'] = $plat_info['order_id'];
            if(!$go_refund_param['error'] && $go_refund_param['type'] == 'ok'){
                $data_plat_order['is_refund'] = 1;
                $refund['error'] = 0;
            }else{
                $data_plat_order['refund_detail'] = serialize($go_refund_param);
                $data_plat_order['is_refund'] = 4;
                $refund['error'] = 1;
            }
            if ($plat_info['is_refund']==1) {
                // 如果是推过款的重复性退款 只记录退款失败原因，依旧保持退款成功状态
                $data_plat_order['is_refund'] = 1;
                $refund['error'] = 0;
            }

            $data_plat_order['last_time'] = time();
            D('Plat_order')->data($data_plat_order)->save();
        } else {
            fdump_sql(['uid'=>$uid,'publish_id'=>$publish_id],'service_refund_time');  //记录退款请求次数
            $refund = D('Plat_order')->order_refund($plat_where);
        }
        if(!$refund['error']) {
            D('Service_user_publish')->where(array('publish_id' => $publish_id))->save(array('status' => 10));
            if($publish_info['is_third_deliver']){
                $res = invoke_v20_service('\app\mall\model\service\third_order_deliver\DeliverApiService/cancel',[['business_type'=>'service','order_no'=>$publish_info['order_sn']]]);
                fdump_api([$publish_info['order_sn'],$res],'mall_third_deliver/cancel',1);
            }else {
                D('Deliver_supply')->updateStatusToZero($publish_id);
            }
            $this->user_order_refund[$refund] = false;
            // 推送模板消息
            $now_user = D('User')->field(true)->where(array('uid' => $uid))->find();
            if ($now_user['openid']) {
                $href = C('config.site_url') . '/wap.php?g=Wap&c=Service&a=price_list&publish_id=' . $publish_id;
                $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
                $model->sendTempMsg('TM00017', array(
                    'href' => $href,
                    'wecha_id' => $now_user['openid'],
                    'first' => '您的跑腿订单退款已完成',
                    'OrderSn' => $publish_info['order_sn'],
                    'OrderStatus' => '已完成退款',
                    'remark' => date('Y-m-d H:i:s')
                ));
            }
            D("System_order")->save_order_status('service', $plat_info['order_id'], 5);
            unLock($session_name);
            exit(json_encode(array('error'=>1,'msg'=>'取消订单成功！')));
        } else {
            fdump('跑腿用户退款失败>>>'.__LINE__,'api/log/'.date('Ymd').'/paotui/refund_err_log',true);
            fdump($_POST,'api/log/'.date('Ymd').'/paotui/refund_err_log',true);
            fdump($plat_info,'api/log/'.date('Ymd').'/paotui/refund_err_log',true);
            fdump($payment_money_order,'api/log/'.date('Ymd').'/paotui/refund_err_log',true);
            fdump($refund,'api/log/'.date('Ymd').'/paotui/refund_err_log',true);
            M('Service_user_publish')->where(array('publish_id' => $publish_id))->save(array('status' => 13));
            M('Deliver_supply')->where(array('item' => 3, 'order_id' => $publish_id))->save(array('status' => 7));
            $this->user_order_refund[$refund] = false;
            unLock($session_name);
            exit(json_encode(array('error'=>1,'msg'=>'取消订单失败！')));
        }
    }
	public function paotui(){
		redirect(get_base_url('pages/runErrands/index'));
	}

    // 配送员定位
    public function line()
    {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        if ($order = M('Plat_order')->field(true)->where(array('business_id' => $order_id, 'business_type' => 'service'))->find()) {
            if ($order['paid'] == 1) {
                $service_user_publish = D('Service_user_publish')->where(['publish_id'=>$order_id])->find();
                if($service_user_publish['is_third_deliver']){
                    $publish_give = D('Service_user_publish_give')->where(['publish_id'=>$order_id])->find();
                    $lines = D("Service_user_publish_log")->where(['publish_id'=>$order_id,'catgory_type'=>3,'type'=>1])->order("`log_id` ASC")->select();
                    $start_time = 0;
                    $l_status = 1;
                    if($lines){
                        foreach ($lines as $item){
                            if($item['status']==2){
                                $start_time = $item['add_time'];
                            }
                            $l_status = $item['status'];
                        }
                        $now_third_deliver_position = invoke_v20_service('\app\mall\model\service\third_order_deliver\DeliverApiService/realTimeLocation',['business_type'=>'service','order_no'=>$service_user_publish['order_sn']]);
                        if($now_third_deliver_position&&$now_third_deliver_position['status']==1000&&$now_third_deliver_position['data']['error_no']==0){
                            if($now_third_deliver_position['lng']&&$now_third_deliver_position['lat']){
                                $lines = array_merge($lines,['lng'=>$now_third_deliver_position['lng'],'lat'=>$now_third_deliver_position['lat'],'name'=>$lines[0]['name'],'phone'=>$lines[0]['phone']]);
                            }
                        }
                    }
                    $s_g_status = [2=>2,3=>3,4=>5];
                    $supply = [
                        'start_time' => $start_time?:0,
                        'from_lnt' => $publish_give['start_adress_lng'],
                        'from_lat' => $publish_give['start_adress_lat'],
                        'aim_lnt' => $publish_give['end_adress_lng'],
                        'aim_lat' => $publish_give['end_adress_lat'],
                        'server_type' => 1, //平台配送
                        'status' => $s_g_status[$l_status]??1,
                    ];
                }else {
                    $supply = D("Deliver_supply")->where(array('order_id' => $order_id, 'item' => 3))->find();
                }
                if (empty($supply)) {
                    exit(json_encode(array('err_code' => true)));
                }
                $start_time = $supply['start_time'];
                if($service_user_publish['is_third_deliver']){
                    $deliver_user = [
                        'name' => $lines?$lines[0]['name']:'',
                        'phone' => $lines?$lines[0]['phone']:'',
                    ];
                }else {
                    $where = array();
                    $where['uid'] = $supply['uid'];
                    $where['create_time'] = array('gt', $start_time);
                    $lines = D("Deliver_user_location_log")->where($where)->order("`create_time` ASC")->select();
                    // 获取配送员信息
                    $deliver_user = M('Deliver_user')->where(array('uid' => $supply['uid'],'status'=>array('neq',4)))->field('name, phone')->find();
                }
                $points = array();
                $points['from_site'] = array('lng' => $supply['from_lnt'], 'lat' => $supply['from_lat']);
                $points['aim_site'] = array('lng' => $supply['aim_lnt'], 'lat' => $supply['aim_lat']);

                if ($lines) {
                    $center = array_pop($lines);
                } else {
                    $center = array('lng' => $supply['from_lnt'], 'lat' => $supply['from_lat']);
                }

                // 计算下距离
                $destance_sum = '';
                $tip = '';
                if ($supply['status'] < 4 && !empty($lines)) {
                    // 计算 配送员和购买东西或者取东西的地点距离
                    $start_lat = $center['lat'];
                    $start_lng = $center['lng'];
                    $end_lat = $points['from_site']['lat'];
                    $end_lng = $points['from_site']['lng'];
                    $destance_sum = $this->compute_distance($start_lat, $start_lng, $end_lat, $end_lng);
                    if ($destance_sum > 0) {
                        $destance_sum = $this->destance_sum_range($destance_sum*1000);
                    }
                    $tip = "配送员：<b style='color: red;'>{$deliver_user['name']}</b> {$deliver_user['phone']} ";
                    if ($supply['server_type'] == 2) {
                        $tip .='正在赶往指定地点购买';
                        $destance_sum = '距离指定地点购买：'.$destance_sum;
                    } elseif ($supply['server_type'] == 1) {
                        $tip .='正在赶往取货点';
                        $destance_sum = '距离取货点：'.$destance_sum;
                    }
                } elseif (!empty($lines)) {
                    // 计算 配送员和需要送达的地点距离
                    $start_lat = $center['lat'];
                    $start_lng = $center['lng'];
                    $end_lat = $points['aim_site']['lat'];
                    $end_lng = $points['aim_site']['lng'];
                    $destance_sum = $this->compute_distance($start_lat, $start_lng, $end_lat, $end_lng);
                    if ($destance_sum > 0) {
                        $destance_sum = $this->destance_sum_range($destance_sum*1000);
                    }

                    $tip = "配送员：<b style='color: red;'>{$deliver_user['name']}</b> {$deliver_user['phone']} ";
                    if ($supply['server_type'] == 2) {
                        $tip .='正快速向您靠拢，请耐心等待';
                        $destance_sum = '距离您：'.$destance_sum;
                    } elseif ($supply['server_type'] == 1) {
                        $tip .='正在赶往送达地点';
                        $destance_sum = '距离送达地点：'.$destance_sum;
                    }
                }

                exit(json_encode(array('err_code' => false, 'lines' => $lines, 'status' => $supply['status'], 'points' => $points, 'center' => $center, 'destance_sum' => $destance_sum, 'tip' => $tip)));
            }
        }
        exit(json_encode(array('err_code' => true)));
    }

    // 处理距离显示
    private function destance_sum_range($meter){
        if($meter < 100){
            return '<100m';
        }else if($meter <1000){
            return $meter.'m';
        }else{
            return round($meter/1000,1).'km';
        }
    }
}