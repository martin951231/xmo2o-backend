<?php
/*
 *   Writers    guo
 */
class ServiceAction extends BaseAction{

    // 分类列表
    public function cat_list(){
        $catList = D('Service_category')->where(array('cat_status'=>1,'type'=>array('neq','1')))->order('cat_sort desc,cid asc')->select();
        if(is_array($catList)){
            $this->returnCode('0',$catList);
        }else{
            $this->returnCode('50000002');
        }
    }

    // 实名认证
    public function authentication(){
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        if($this->config['user_is_real_name'] == 1){
            $user_authentication = D("User_authentication")->where(array('uid'=>$this->_uid))->field('authentication_id,authentication_status,uid')->find();
            $url = "wap.php?g=Wap&c=My&a=authentication";
            if(empty($user_authentication)){
                exit(json_encode(array('errorCode'=>'50000001','errorMsg'=>L_('请先实名认证再进行发布'),'result'=>$this->config['site_url'].'/wap.php?g=Wap&c=My&a=authentication')));
            } elseif($user_authentication['authentication_status'] != 1){
                $this->redirect(U('My/authentication_index'));
            }
        }else{
            $this->returnCode('0');
        }
    }

    // 用户发布需求详情
    public function publish_detail(){
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $cid = I('cid');
        $lat = I('lat');
        $lng = I('lng');

        // 获取分类信息
        $database_Service_category = D('Service_category');
        $now_category = $database_Service_category->field(true)->where(array('cid'=>$cid))->find();
        if (empty($now_category)) {
            $this->returnCode('50000002');
        }

        //返回配送员数量
        $deliver = D('Deliver_user')->hasUser($lat,$lng);
        $datas['deliver_count'] = count($deliver);

        $adress_list = D('User_adress')->get_adress_list($this->_uid);
        foreach ($adress_list as $key => $value) {
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
            if ($_POST['app_type'] && $_POST['app_type']==1 && $addressInfo['latitude'] && $addressInfo['longitude']) {
                // 单独处理ios
                $lats = $addressInfo['latitude'];
                $lngs = $addressInfo['longitude'];
                $addressInfo['lat'] = $lats;
                $addressInfo['lng'] = $lngs;
            }
        }else{
			$addressInfo = $this->getObj();
		}
        if ($address_msg) {
            $publish_config = D('Deliver_service_set')->address_publish_config($address_msg);
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
            if ($_POST['app_type']==1 && $_POST['app_version'] && $_POST['app_version']<=1406) {
                $service_buy_deliver_send_time = $this->config['service_buy_deliver_send_time'];
                if (empty($service_buy_deliver_send_time)) {
                    $service_buy_deliver_send_time = 60;
                }
                $service_time = time() + $service_buy_deliver_send_time * 60;
                $today = strtotime(date('Y-m-d 23:59:59'));

                if ($service_time > $today) {
                    $day = '明天';
                } else {
                    $day = '今天';
                }
                $daysList = array($day);

                $time_msg = array(
                    'week' => $day,
                    'time' => date('H:i', $service_time),
                    'basic_distance' => $config_time['service_basic_distance'],
                    'delivery_fee' => $config_time['service_delivery_fee'],
                    'per_km_price' => $config_time['service_per_km_price'],
                    'date' => date('Y-m-d', $service_time),
                );
                $timeList = array(0=>array($time_msg));
            }
        } else {

            // 获取对应时间信息
//            $time_info = $this->publish_time_info($now_category['type']);
            $time_info = D('Deliver_service_set')->publish_time_info($now_category['type'], $address_msg);

            $config_time = $time_info['config_time'];
            $daysList = $time_info['daysList'];
            $timeList = $time_info['timeList'];
        }

        $datas['now_category'] = $now_category; //分类详情
        $datas['addressInfo'] = $addressInfo; //默认地址
        $datas['adress_list'] = array_values($adress_list); //地址列表
        $datas['config_time'] = $config_time;
        $datas['daysList'] = !empty($daysList) ? $daysList : array();
        $timeListBak = [];
        ksort($timeList);
        foreach ($timeList as $key => $value) {
            $timeListBak[] = $value;
        }
        $datas['timeList'] = $timeListBak;

        $config['service_basic_km'] = $this->config['service_basic_km'] ? $this->config['service_basic_km'] : 0;
        $config['service_basic_km_time'] = $this->config['service_basic_km_time'] ? $this->config['service_basic_km_time'] : 0;
        $config['service_basic_weight'] = $this->config['service_basic_weight'] ? $this->config['service_basic_weight'] : 0;
        $config['service_basic_weight_price'] = $this->config['service_basic_weight_price'] ? $this->config['service_basic_weight_price'] : 0;
        $config['service_bounds_weight'] = $this->config['service_bounds_weight'] ? $this->config['service_bounds_weight'] : 0;
        $config['service_days_number'] = $config_time['service_days_number'] ? $config_time['service_days_number'] : 0;
        $config['count_freight_charge_method'] = $this->config['count_freight_charge_method'] ? $this->config['count_freight_charge_method'] : 0;
        $config['service_weight_multilevel_switch'] = $this->config['service_weight_multilevel_switch'] ? $this->config['service_weight_multilevel_switch'] : 0;
        $config['service_buy_deliver_times_switch'] = $this->config['service_buy_deliver_times_switch'] ? $this->config['service_buy_deliver_times_switch'] : 0;

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


        $this->returnCode('0',$datas);
    }

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

        $this->returnCode('0',$datas);
    }

    /*处理服务快派时间问题*/
    public function publish_time_info($type = 2) {
        // 判断是否有今天数据
        $weekarray=array(L_("日"),L_("一"),L_("二"),L_("三"),L_("四"),L_("五"),L_("六"));

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
                                        $time[$day][$hour][$s]['week'] = L_("今天");
                                        $is_today = 1;
                                    } else if ($day == 1) {
                                        $time[$day][$hour][$s]['week'] = L_("明天");
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
                                    $time[$day][$hour][$s]['week'] = L_("明天");
                                } else if ($day == 2) {
                                    $time[$day][$hour][$s]['week'] = L_("后天");
                                } else {
                                    $time[$day][$hour][$s]['week'] = L_("周") . $weekarray[date("w", strtotime("+" . $day . " day"))];
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
                                        $time[$day][$hour][$s]['week'] = L_("今天");
                                        $is_today = 1;
                                    } else if ($day == 1) {
                                        $time[$day][$hour][$s]['week'] = L_("明天");
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
                                    $time[$day][$hour][$s]['week'] = L_("明天");
                                } else if ($day == 2) {
                                    $time[$day][$hour][$s]['week'] = L_("后天");
                                } else {
                                    $time[$day][$hour][$s]['week'] = L_("周") . $weekarray[date("w", strtotime("+" . $day . " day"))];
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

        $daysList = array();
        for ($d=0; $d <= $service_days_number+intval($is_add_day); $d++) {
            if($d == 0){
                if (1 == $is_today) {
                    $daysList[] =  L_("今天");
                }
            }else if($d==1){
                $daysList[] =  L_("明天");
            }else if($d==2){
                $daysList[] =  L_("后天");
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
        $config_time['service_days_number'] = $service_days_number;

        $time_info = array(
            'config_time' => $config_time,
            'daysList' => $daysList,
            'timeList' => $timeList
        );
        return $time_info;
    }

    //获取附近的配送员数量
    public function deliver_count(){
        $lat = I('lat', false);
        $lng = I('lng', false);
        $deliver = D('Deliver_user')->hasUser($lat,$lng);
        if($deliver){
            $this->returnCode('0',count($deliver));
        }else{
            $this->returnCode('50000003');
        }
    }

    // 帮我买
    public function publish_buy_data(){
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
//        $userInfo = D('User')->where(array('uid'=>$this->_uid))->field('now_money')->find();
        // if($userInfo['now_money']<$_POST['total_price']){
        //     $this->returnCode('50000005');
        // }

        // 获取提交的收货地址信息
        $adress_info = D("User_adress")->get_adress($this->_uid,$_POST['adress_id']);
        if($_POST['buy_type'] == 2){
            if (empty($_POST['address_lat']) || empty($_POST['address_lng'])) {
                $this->returnCode(3001,array(),L_('请选择购买地址!'));
            }
            // 指定地点 获得省市区id
            $area_info = D('Area')->cityMatching($_POST['address_lat'], $_POST['address_lng']);

            $data['address'] = $_POST['address'];
            $data['address_lng'] = $_POST['address_lng'];
            $data['address_lat'] = $_POST['address_lat'];
            $data['address_area_id'] = $_POST['address_area_id'];
            $data['province_id'] = $area_info['area_info']['province_id'];
            $data['city_id'] = $area_info['area_info']['city_id'];
            $data['area_id'] = $area_info['area_info']['area_id'];



            if ($_POST['address_lat'] && $_POST['address_lng'] && $adress_info['latitude'] && $adress_info['longitude']) {
                $destance_sum = $this->compute_distance($_POST['address_lat'], $_POST['address_lng'], $adress_info['latitude'], $adress_info['longitude']);
            } else {
                $this->returnCode(3003,array(),L_('定位信息获取不全!'));
            }

        }else{
            if (empty($adress_info)) {
                $this->returnCode(3002,array(),L_('请选择收货地址!'));
            }

            $data['address'] = $adress_info['adress'];
            $data['address_lng'] = $adress_info['longitude'];
            $data['address_lat'] = $adress_info['latitude'];
            $data['address_area_id'] = $adress_info['city'];
            $data['province_id'] = $adress_info['province'];
            $data['city_id'] = $adress_info['city'];
            $data['area_id'] = $adress_info['area'];
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
                $this->returnCode(3005,array(),L_('请选择送达时间!'));
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
                                $this->returnCode(3004,array(),L_('该重量档次价格已更新，请刷新获取最新价格!'));
                            } else {
                                $weight_info = serialize($val);
                                $weight = $val['start_weight'] . 'kg-' . $val['end_weight'] . 'kg';
                            }
                        }
                    }
                    if (!$weight) {
                        $this->returnCode(3004,array(),L_('物品重量信息有误！请刷新重试！'));
                    }
                }
            } else {
                $this->returnCode(3005,array(),L_('请选择物品重量！'));
            }
        }
        // 获取收货地址信息
        $publish_config = D('Deliver_service_set')->location_publish_config($data['address_lat'], $data['address_lng']);
        // 帮我买 获取选择送达时间的各个配置

        if (!empty($_POST['time_info'])) {
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
                    $this->returnCode(3006,array(),L_('费用计算错误，请重试！'));
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
                    $this->returnCode(3006,array(),L_('费用计算错误，请重试！'));
                }
            } else {
                $this->returnCode(3007,array(),L_('所选时间配送员已经休息！'));
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
                exit(json_encode(array('errorCode'=>2,'msg'=>L_('费用计算错误，请重试！'))));
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
        $data['destance_sum'] = $destance_sum;
        $data['add_time'] = time();
        $data['cid'] = $_POST['cid'];
        $data['uid'] = $this->_uid;
        $data['status'] = 1;
        $data['catgory_type'] = 2;
        $nowtime = date("mdHis");
        $data['order_sn'] = $nowtime.rand(10,99).sprintf("%08d",$this->_uid);
        $res = D('Service_user_publish')->data($data)->add();
        if($res){

            $buyData['publish_id'] =  $res;
            $buyData['goods_remarks'] = $this->filterEmoji2($_POST['goods_remarks']);
            $buyData['buy_type'] =  $_POST['buy_type'];
            $buyData['address'] =  $_POST['address'];
            $buyData['address_lng'] =  $_POST['address_lng'];
            $buyData['address_lat'] =  $_POST['address_lat'];

            $buyData['adress_id'] =  $_POST['adress_id'];

            $buyData['end_adress_name'] =  $adress_info['adress'].'，'.$adress_info['detail'];
            $buyData['end_adress_lng'] =  $adress_info['longitude'];
            $buyData['end_adress_lat'] =  $adress_info['latitude'];
            $buyData['end_adress_nickname'] =  $adress_info['name'];
            $buyData['end_adress_phone'] =  $adress_info['phone'];

            $buyData['estimate_goods_price'] = $this->filterEmoji2($_POST['estimate_goods_price']);
            $buyData['basic_distance_price'] =  $_POST['basic_distance_price'];
            $buyData['distance_price'] =  $_POST['distance_price'];
            $buyData['tip_price'] =  $_POST['tip_price'];
            $buyData['total_price'] =  $_POST['total_price'];
            $buyData['img'] =  $_POST['img'];
            $buyData['weight_price'] =  $weight_price;
            $buyData['weight_info'] =  $weight_info;
            $buyData['weight'] = $weight;
            $buy_res = D('Service_user_publish_buy')->data($buyData)->add();

            if(!$buy_res){
                D('Service_user_publish')->where(array('publish_id'=>$res))->delete();
                $this->returnCode('50000006');
            }

            $pay_order_param = array(
                'business_type' => 'service',
                'business_id' => $res,
                'order_name' => L_('帮我买订单'),
                'uid' => $this->_uid,
                'total_money' => $_POST['total_price'],
                'wx_cheap' => 0,
            );
            $plat_order_result = D('Plat_order')->add_order($pay_order_param);
            if ($plat_order_result['error_code']) {
                $this->error_tips($plat_order_result['error_msg']);
                exit(json_encode(array('errorCode'=>50000008,'errorMsg'=>$plat_order_result['error_msg'])));
            } else {
                exit(json_encode(array('errorCode'=>0,'errorMsg'=>'需求已发布,请去支付!','order_id'=>$plat_order_result['order_id'],'type'=>'plat')));
            }

            // exit(json_encode(array('errorCode'=>0,'errorMsg'=>'需求提交成功','order_id'=>$res,'type'=>'service')));
        }else{
            $this->returnCode('50000006');
        }


    }

    // 帮我送
    public function publish_give_data(){
        if(!$this->_uid){
            $this->returnCode('20044013');
        }

//        $userInfo = D('User')->where(array('uid'=>$this->_uid))->field('now_money')->find();
        // if($userInfo['now_money']<$_POST['total_price']){
        //     $this->returnCode('50000005');
        // }
        if (empty($_POST['start_adress_id'])) {
            $this->returnCode(3001,array(),L_('请选择取货地址!'));
        }
        if (empty($_POST['start_adress_id'])) {
            $this->returnCode(3002,array(),L_('请选择收货地址!'));
        }
        $start_adress_info = D("User_adress")->get_adress($this->_uid,$_POST['start_adress_id']);
        $end_adress_info = D("User_adress")->get_adress($this->_uid,$_POST['end_adress_id']);

        $data['address'] = $start_adress_info['adress'];
        $data['address_lng'] = $start_adress_info['longitude'];
        $data['address_lat'] = $start_adress_info['latitude'];
        $data['address_area_id'] = $start_adress_info['city'];
        $data['province_id'] = $start_adress_info['province'];
        $data['city_id'] = $start_adress_info['city'];
        $data['area_id'] = $start_adress_info['area'];


        if ($start_adress_info['latitude'] && $start_adress_info['longitude'] && $end_adress_info['latitude'] && $end_adress_info['longitude']) {
            $destance_sum = $this->compute_distance($start_adress_info['latitude'], $start_adress_info['longitude'], $end_adress_info['latitude'], $end_adress_info['longitude']);
        } else {
            $this->returnCode(3003,array(),L_('定位信息获取不全!'));
        }


        // 处理重量费用
        // 判断获取 重量档次信息
        $weight_price = 0;
        $weight_info = '';
        $weight = '';
        if($this->config['service_weight_multilevel_switch'] == 1) {
            $weight_price = round($_POST['weight_price'], 2);
            if ($_POST['weight_price']>=0 && $_POST['start_weight'] >=0 && $_POST['end_weight']>0) {
                $service_give_weight_info = $this->config['service_give_weight_info'];
                $service_give_weight_info = unserialize($service_give_weight_info);
                if ($service_give_weight_info) {
                    // start_weight 范围起始公斤数 end_weight 范围结束公斤数 范围价格 weight_price
                    foreach ($service_give_weight_info as $key => &$val) {
                        if (floatval($val['start_weight'])==floatval($_POST['start_weight']) && floatval($val['end_weight'])==floatval($_POST['end_weight'])) {
                            $val['weight_price'] = round($val['weight_price'], 2);
                            if ($val['weight_price'] != $weight_price) {
                                $this->returnCode(3004,array(),L_('该重量档次价格已更新，请刷新获取最新价格!'));
                            } else {
                                $weight_info = serialize($val);
                                $weight = $val['start_weight'] . 'kg-' . $val['end_weight'] . 'kg';
                            }
                        }
                    }
                    if (!$weight) {
                        $this->returnCode(3004,array(),L_('物品重量信息有误！请刷新重试！'));
                    }
                }
            } else {
                $this->returnCode(3005,array(),L_('请选择物品重量！'));
            }
        }

        // 如果传入了时间值
        if ($_POST['time_info']) {
            // 获取收货地址信息
            $publish_config = D('Deliver_service_set')->location_publish_config($end_adress_info['latitude'], $end_adress_info['longitude']);

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
                $tip_price = $_POST['tip_price'] ? $_POST['tip_price'] : 0;
                $total = $distance_price + $basic_distance_price + $weight_price + $tip_price;
                $total_price = round($total, 2);

                if ($total_price < $service_delivery_fee2) {
                    $this->returnCode(3006,array(),L_('费用计算错误，请重试！'));
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

        $data['destance_sum'] = $destance_sum ? $destance_sum : $_POST['destance_sum'];
        if (!$data['destance_sum']) {
            $data['destance_sum'] = 0;
        }

//        if(empty($data['destance_sum']) || $data['destance_sum'] == '0.00'){
//            $this->returnCode(2,array(),L_('距离计算失败，或没有配送距离，无法提交。请重试！'));
//        }
        $data['add_time'] = time();
        $data['cid'] = $_POST['cid'];
        $data['uid'] = $this->_uid;
        $data['catgory_type'] = 3;
        $data['status'] = 1;
        $nowtime = date("mdHis");
        $data['order_sn'] = $nowtime.rand(10,99).sprintf("%08d",$this->_uid);
        $res = D('Service_user_publish')->data($data)->add();
        if($res){
            $giveData['publish_id'] =  $res;
            $giveData['cid'] = $_POST['cid'];
            $giveData['goods_catgory'] =  $_POST['goods_catgory'];
            $giveData['weight'] =  $_POST['weight'];
            $giveData['price'] =  $_POST['price'];
            $giveData['img'] =  $_POST['img'];

            $giveData['start_adress_id'] =  $_POST['start_adress_id'];

            $giveData['start_adress_name'] =  $start_adress_info['adress'].'，'.$start_adress_info['detail'];
            $giveData['start_adress_lng'] =  $start_adress_info['longitude'];
            $giveData['start_adress_lat'] =  $start_adress_info['latitude'];
            $giveData['start_adress_nickname'] =  $start_adress_info['name'];
            $giveData['start_adress_phone'] =  $start_adress_info['phone'];


            $giveData['end_adress_id'] =  $_POST['end_adress_id'];
            $giveData['end_adress_name'] =  $end_adress_info['adress'].'，'.$end_adress_info['detail'];
            $giveData['end_adress_lng'] =  $end_adress_info['longitude'];
            $giveData['end_adress_lat'] =  $end_adress_info['latitude'];
            $giveData['end_adress_nickname'] =  $end_adress_info['name'];
            $giveData['end_adress_phone'] =  $end_adress_info['phone'];

            $giveData['fetch_time'] =  $_POST['fetch_time'];
            $giveData['arrival_time_info'] =  $_POST['time_info'];
            $giveData['estimate_goods_price'] =  $_POST['estimate_goods_price'];

            $giveData['tip_price'] =  $_POST['tip_price'];
            $giveData['remarks'] = $this->filterEmoji2($_POST['remarks']);

            $giveData['basic_distance_price'] =  $_POST['basic_distance_price'];
            $giveData['distance_price'] =  $_POST['distance_price'];
            $giveData['weight_price'] =  $_POST['weight_price'];
            $giveData['weight_info'] =  $weight_info;

            $giveData['total_price'] =  $_POST['total_price'];
            $giveData['addtime'] =  time();
            $giveData['uid'] =  $this->_uid;
            // D("Service_user_publish_give")->data($giveData)->add();

            $give_res = D("Service_user_publish_give")->data($giveData)->add();

            if(!$give_res){
                $this->returnCode(3008,array(),L_('数据添加异常请重试！'));
            }

            $pay_order_param = array(
                'business_type' => 'service',
                'business_id' => $res,
                'order_name' => L_('帮我送订单'),
                'uid' => $this->_uid,
                'total_money' => $_POST['total_price'],
                'wx_cheap' => 0,
            );
            $plat_order_result = D('Plat_order')->add_order($pay_order_param);
            if ($plat_order_result['error_code']) {
                $this->error_tips($plat_order_result['error_msg']);
                exit(json_encode(array('errorCode'=>50000008,'errorMsg'=>$plat_order_result['error_msg'])));
            } else {
                exit(json_encode(array('errorCode'=>0,'errorMsg'=>'需求已发布,请去支付!','order_id'=>$plat_order_result['order_id'],'type'=>'plat')));
            }

        }else{
            $this->returnCode('50000006');
        }
    }


    public function publish_config(){
        $lng = $_POST['lng'];
        $lat = $_POST['lat'];
        $type = $_POST['type'];
        $address_id = $_POST['address_id'];
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


    public function template_message(){
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        $publish_id = I('publish_id', false);

        $info = M('Service_user_publish')->where(array('publish_id'=>$publish_id))->find();
        if($info['deliver_type'] == 1){
            //推送配送订单，
            D('Deliver_supply')->saveOrder($publish_id, null,3,'service_user_publish');
        }else{
            //模板消息
            $where = "`cid`={$info['cid']} AND `area_id`={$info['address_area_id']} AND (ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$info['address_lat']}*PI()/180-`lat`*PI()/180)/2),2)+COS({$info['address_lat']}*PI()/180)*COS(`lat`*PI()/180)*POW(SIN(({$info['address_lng']}*PI()/180-`lng`*PI()/180)/2),2)))*1000) <= `radius`*1000 OR `radius` = 0) AND `uid` != {$this->_uid} ";
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
                        $href = C('config.site_url') . '/wap.php?g=Wap&c=Service&a=detail_special&publish_id='.$publish_id;
                        $model->sendTempMsg('TM00017',
                            array('href' => $href,
                                'wecha_id' => $user_info['openid'],
                                'first' => L_('平台用户发布了一个符合您的“X1”需求，请及时接单！',$value['cat_name']),
                                'OrderSn' => $info['order_sn'],
                                'OrderStatus' => L_('待处理'),
                                'remark' => L_('请您及时处理！')));
                    }
                }
            }
        }

        // D('User')->where(array('uid'=>$this->user_session['uid']))->setDec('now_money',$_POST['total_price']);
        // D('User_money_list')->add_row($this->user_session['uid'],2,$_POST['total_price'],"支付帮我买服务费 ".$_POST['total_price'].C('config.Currency_txt')."",true,4,$res);

        D('Service_offer_record')->data(array('offer_id'=>0,'publish_id'=>$publish_id,'add_time'=>time(),'remarks'=>L_('订单支付成功')))->add();

        $now_user = D('User')->get_user($this->_uid);
        $publishInfo = D("Service_user_publish")->where(array('publish_id' => $publish_id))->field('publish_id,uid,cid,order_sn')->find();
        if ($now_user['openid']) {
            $model = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
            $href = C('config.site_url') . '/wap.php?g=Wap&c=Service&a=price_list&publish_id=' . $publish_id;
            // 帮买帮送修改  待办事项通知 模板 为 订单状态更新 模板
            $model->sendTempMsg('TM00017', array('href' => $href, 'wecha_id' => $now_user['openid'], 'OrderStatus' => C('config.service_alias_name'), 'OrderSn' => $publishInfo['order_sn'], 'first' =>L_('您发布的需求已经完成支付，请查看。'), 'remark' => date('Y.m.d H:i:s')));
        }
        // 进行极光App推送
        if ($now_user) {
            if (!$publishInfo['order_sn']) $publishInfo['order_sn'] = $publish_id;
            import('@.ORG.Apppush');
            $order['status'] = 2;
            $order['order_id'] = $publish_id;
            $order['order_sn'] = $publishInfo['order_sn'];
            $order['uid'] = $this->_uid;
            $order['catgory_type'] = $publishInfo['catgory_type'];
            $apppush = new Apppush();
            $apppush->send($order, 'publish');
        }
    }




    // 图片上传
    public function up_img(){
        if (!$this->_uid) {
            $this->returnCode('20044013');
        }
        if(!$_FILES){
            $this->returnCode('20140051');
        }
        if($_FILES['imgFile']['error'] == 3){
            $this->returnCode('20140051');
        }

        if ($_FILES['imgFile']['error'] != 4) {
            $image = D('Image')->handle($this->_uid, 'yuedan', 0, array('size' => 20), false);
            if ($image['error']) {
                $this->returnCode('20140052');
            } else {
                exit(json_encode(array('errorCode' => 0,'errorMsg'=>'success', 'result' =>$this->config['site_url'].$image['url']['file'])));
            }
        } else {
            $this->returnCode('20140051');
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


    // 计算两个收货地址的距离（直线）帮我送
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


    // 计算两个收货地址的距离（骑行/直线）
    private function compute_distance($start_lat, $start_lng, $end_lat, $end_lng)
    {
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
                exit(json_encode(array('errorCode' => 2, 'msg' => L_('距离计算失败！'))));
            }
        }
        $destance_sum || $destance_sum = getDistance($start_lat, $start_lng, $end_lat, $end_lng);
        $destance_sum = $destance_sum / 1000;
        return $destance_sum;
    }


    // 服务快派 金钱计算方式
    private function publish_count_method($price)
    {
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
}
?>
