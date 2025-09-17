<?php
class Deliver_service_setModel extends Model
{

    /*处理服务快派时间问题*/
    public function publish_time_info($type = 2, $address_info = array(), $is_wxapp = false, $id = 0) {
        // 判断是否有今天数据
        $weekarray=array(L_("日"),L_("一"),L_("二"),L_("三"),L_("四"),L_("五"),L_("六"));
        // 如果存在地址信息，获取地址对应启用的配置
        $publish_config = array();
        if ($address_info) {
            $publish_config = D('Deliver_service_set')->address_publish_config($address_info);
        }

        // 统一默认获取一小时后的时间 即为配送最早为一小时后（如果有配置去配置时间)
        if (2 == $type) {
            // 帮我买
            // 配送时长
            $service_deliver_send_time = intval(C('config.service_buy_deliver_send_time'));
            // 可预约时间
            $service_days_number = intval(C('config.service_buy_days_number'));

            if ($publish_config && $publish_config['buy_status'] == 1) {
                // 取区域配置

                // 配送时间段一
                $delivery_times = explode('-', $publish_config['buy_deliver_time1']);
                // 配送时间段一 起步距离
                $service_basic_distance = $publish_config['buy_base_distance1'];
                // 配送时间段一 起步配送费
                $service_delivery_fee = $publish_config['buy_base_fee1'];
                // 配送时间段一 每公里的配送费
                $service_per_km_price = $publish_config['buy_per_km_price1'];

                // 配送时间段二
                $delivery_times2 = explode('-', $publish_config['buy_deliver_time2']);
                // 配送时间段二 起步距离
                $service_basic_distance2 = $publish_config['buy_base_distance2'];
                // 配送时间段二 起步配送费
                $service_delivery_fee2 = $publish_config['buy_base_fee2'];
                // 配送时间段二 每公里的配送费
                $service_per_km_price2 = $publish_config['buy_per_km_price2'];
            } else {
                // 取总配置

                // 配送时间段一
                $delivery_times = explode('-', C('config.service_buy_delivery_time'));
                // 配送时间段一 起步距离
                $service_basic_distance = C('config.service_buy_basic_distance');
                // 配送时间段一 起步配送费
                $service_delivery_fee = C('config.service_buy_delivery_fee');
                // 配送时间段一 每公里的配送费
                $service_per_km_price = C('config.service_buy_per_km_price');

                // 配送时间段二
                $delivery_times2 = explode('-',  C('config.service_buy_delivery_time2'));
                // 配送时间段二 起步距离
                $service_basic_distance2 =  C('config.service_buy_basic_distance2');
                // 配送时间段二 起步配送费
                $service_delivery_fee2 =  C('config.service_buy_delivery_fee2');
                // 配送时间段二 每公里的配送费
                $service_per_km_price2 =  C('config.service_buy_per_km_price2');
            }

        } elseif (3 == $type) {
            // 帮我送
            // 配送时长
            $service_deliver_send_time = intval(C('config.service_deliver_send_time'));
            // 可预约时间
            $service_days_number = intval( C('config.service_days_number'));

            if ($publish_config && $publish_config['give_status'] == 1) {
                // 取区域配置

                // 配送时间段一
                $delivery_times = explode('-', $publish_config['give_deliver_time1']);
                // 配送时间段一 起步距离
                $service_basic_distance = $publish_config['give_base_distance1'];
                // 配送时间段一 起步配送费
                $service_delivery_fee = $publish_config['give_base_fee1'];
                // 配送时间段一 每公里的配送费
                $service_per_km_price = $publish_config['give_per_km_price1'];

                // 配送时间段二
                $delivery_times2 = explode('-', $publish_config['give_deliver_time2']);
                // 配送时间段二 起步距离
                $service_basic_distance2 = $publish_config['give_base_distance2'];
                // 配送时间段二 起步配送费
                $service_delivery_fee2 = $publish_config['give_base_fee2'];
                // 配送时间段二 每公里的配送费
                $service_per_km_price2 = $publish_config['give_per_km_price2'];
            } else {
                // 配送时间段一
                $delivery_times = explode('-', C('config.service_delivery_time'));
                // 配送时间段一 起步距离
                $service_basic_distance = C('config.service_basic_distance');
                // 配送时间段一 起步配送费
                $service_delivery_fee = C('config.service_delivery_fee');
                // 配送时间段一 每公里的配送费
                $service_per_km_price = C('config.service_per_km_price');

                // 配送时间段二
                $delivery_times2 = explode('-', C('config.service_delivery_time2'));
                // 配送时间段二 起步距离
                $service_basic_distance2 = C('config.service_basic_distance2');
                // 配送时间段二 起步配送费
                $service_delivery_fee2 = C('config.service_delivery_fee2');
                // 配送时间段二 每公里的配送费
                $service_per_km_price2 = C('config.service_per_km_price2');
            }
        } else {
            return false;
        }


        // 帮我买商家单独设置起送费
        if ($id>0) {
            $service_delivery_fee = $this->getStoreDeliveryFee($id, $service_delivery_fee);
            $service_delivery_fee2 = $this->getStoreDeliveryFee($id, $service_delivery_fee2);
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
                        //预计送达时间到0点后，跳过今天的时间
                        if(strtotime("+".$service_deliver_send_time." minutes") > strtotime(date('Y-m-d'. ' 23:59:59'))){
                            continue;
                        }
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
                                    $time[$day][$hour][$s]['delivery_fee'] = (C('config.mall_third_deliver_open')&&$type==3)?0:$service_delivery_fee;
                                    $time[$day][$hour][$s]['per_km_price'] = $service_per_km_price;
                                    $time[$day][$hour][$s]['date'] = date("Y-m-d", strtotime("+" . $day . " day"));
                                }
                            }
                        }
                    }else{
                        for ($s=0; $s <= 5; $s++) {
                            //跨天订单，跳过预计送达时间之前的时间
                            if($d == 1 && $i == $start_time1 && intval($s."0") < $date_time_M &&  strtotime("+".$service_deliver_send_time." minutes") > strtotime(date('Y-m-d'. ' 23:59:59'))){
                                continue;
                            }
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
                                $time[$day][$hour][$s]['delivery_fee'] = (C('config.mall_third_deliver_open')&&$type==3)?0:$service_delivery_fee;
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
                        //预计送达时间到0点后，跳过今天的时间
                        if(strtotime("+".$service_deliver_send_time." minutes") > strtotime(date('Y-m-d'. ' 23:59:59'))){
                            continue;
                        }
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
                                    $time[$day][$hour][$s]['delivery_fee'] = (C('config.mall_third_deliver_open')&&$type==3)?0:$service_delivery_fee2;
                                    $time[$day][$hour][$s]['per_km_price'] = $service_per_km_price2;
                                    $time[$day][$hour][$s]['date'] = date("Y-m-d",strtotime("+".$day." day"));
                                }

                            }
                        }
                    }else{
                        for ($s=0; $s <= 5; $s++) {
                            //跨天订单，跳过预计送达时间之前的时间
                            if($d == 1 && $i == $start_time1 && intval($s."0") <  $date_time_M && strtotime("+".$service_deliver_send_time." minutes") > strtotime(date('Y-m-d'. ' 23:59:59'))){
                                continue;
                            }
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
                                $time[$day][$hour][$s]['delivery_fee'] = (C('config.mall_third_deliver_open')&&$type==3)?0:$service_delivery_fee2;
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
        $timeList = array();
        $daysList = array();

        //过滤掉小于当前时间的
        $tm = time();
        foreach ($time as $k1 => $v1) {
            foreach ($v1 as $k2 => $v2) {
                $time[$k1][$k2] = array_values(array_filter($v2, function ($r) use ($tm) {
                    return strtotime($r['date'] . ' ' . $r['time']) > $tm;
                }));
            }
        }

        if ($is_wxapp) {
            foreach ($time as $key => $value) {
                ksort($value);
                foreach ($value as $kk => $vv) {
                    foreach ($vv as $kkk => $vvv) {
                        $timeList[$key][] = $vvv;
                    }
                }
            }
            for ($d=0; $d <= $service_days_number+intval($is_add_day); $d++) {
                $day = date("Y-m-d",strtotime("+".$d." day"));
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
        } else {
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
            ksort($timeList);
            $timeList = array_values($timeList);
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

    /*获取对应地区跑腿配置 以最下级地区为准*/
    public function address_publish_config($address_info) {
        $publish_config = array();
        if ($address_info['area_id']) {
            // 首先获取最下级 区id 对应的跑腿配置
            $where = array('area_id' => $address_info['area_id'],'is_open' => 1);
            $now_area = M('Area')->field(true)->where($where)->find();
            if(empty($now_area)){
                $publish_config = array();
            } else {
                $deliverServiceSetDB = M('Deliver_service_set');
                $publish_config = $deliverServiceSetDB->field(true)->where($where)->find();
            }
        }
        if ($address_info['city_id'] && !$publish_config) {
            // 如果 区无对应 配置 取 市id 对应配置
            $where = array('area_id' => $address_info['city_id'],'is_open' => 1);
            $now_area = M('Area')->field(true)->where($where)->find();
            if(empty($now_area)){
                $publish_config = array();
            } else {
                $deliverServiceSetDB = M('Deliver_service_set');
                $publish_config = $deliverServiceSetDB->field(true)->where($where)->find();
            }
        }
        if ($address_info['province_id'] && !$publish_config) {
            // 如果 区无对应 配置 取 市id 对应配置
            $where = array('area_id' => $address_info['province_id'],'is_open' => 1);
            $now_area = M('Area')->field(true)->where($where)->find();
            if(empty($now_area)){
                $publish_config = array();
            } else {
                $deliverServiceSetDB = M('Deliver_service_set');
                $publish_config = $deliverServiceSetDB->field(true)->where($where)->find();
            }
        }
        if (!$publish_config) {
            $publish_config = array();
        }
        return $publish_config;
    }

    /*获取对应定位地区跑腿配置 以最下级地区为准*/
    public function location_publish_config($lat, $lng) {
        $publish_config = array();
        $address_info = array();
        if ($lng > 0 && $lat > 0) {
            $area_info = PD('Area')->cityMatching($lat, $lng);
            if ($area_info['area_info']['province_id']) {
                $address_info['province_id'] = $area_info['area_info']['province_id'];
            }
            if ($area_info['area_info']['city_id'] && $area_info['area_info']['city_id'] > 0) {
                $address_info['city_id'] = $area_info['area_info']['city_id'];
            }
            if ($area_info['area_info']['area_id'] && $area_info['area_info']['area_id'] > 0) {
                $address_info['area_id'] = $area_info['area_info']['area_id'];
            }
        }
        if ($address_info['area_id']) {
            // 首先获取最下级 区id 对应的跑腿配置
            $where = array('area_id' => $address_info['area_id']);
            $now_area = M('Area')->field(true)->where($where)->find();
            if(empty($now_area)){
                $this->error(L_('提交数据错误！'));
            }
            $deliverServiceSetDB = M('Deliver_service_set');
            $publish_config = $deliverServiceSetDB->field(true)->where($where)->find();
        }
        if ($address_info['city_id'] && !$publish_config) {
            // 如果 区无对应 配置 取 市id 对应配置
            $where = array('area_id' => $address_info['city_id']);
            $now_area = M('Area')->field(true)->where($where)->find();
            if(empty($now_area)){
                $this->error(L_('提交数据错误！'));
            }
            $deliverServiceSetDB = M('Deliver_service_set');
            $publish_config = $deliverServiceSetDB->field(true)->where($where)->find();
        }
        if ($address_info['province_id'] && !$publish_config) {
            // 如果 区无对应 配置 取 市id 对应配置
            $where = array('area_id' => $address_info['province_id']);
            $now_area = M('Area')->field(true)->where($where)->find();
            if(empty($now_area)){
                $this->error(L_('提交数据错误！'));
            }
            $deliverServiceSetDB = M('Deliver_service_set');
            $publish_config = $deliverServiceSetDB->field(true)->where($where)->find();
        }
        if (!$publish_config) {
            $publish_config = array();
        }
        return $publish_config;
    }


    /*
     * 获取帮我买商家单独设置起送费
     * datatime 2020/06/23
    */
    public function getStoreDeliveryFee($id, $oldDeliveryFee = 0, $auto_goods = array()) {
        if(!$auto_goods){
            // 获得帮我买商品信息（ 用于查看商家是否单独设置起送费 ）
            $auto_goods = M('Service_auto_goods')->where(['id'=> $id])->find();
        }

        if ($auto_goods['delivery_fee'] === '' || $auto_goods['delivery_fee'] < 0) {//使用平台的
            $delivery_fee = $oldDeliveryFee;
        }elseif($auto_goods['delivery_fee']>0){
            $delivery_fee = $auto_goods['delivery_fee'];
        }else{
            $delivery_fee = 0;
        }
        return $delivery_fee;
    }


    public function getNowTimeConfig($type=2, $time=0) {
        if (!$time) {
            // 没有传值 自行获取当前时间戳
            $service_buy_deliver_send_time = C('config.service_buy_deliver_send_time');
            if (empty($service_buy_deliver_send_time)) {
                $service_buy_deliver_send_time = 60;
            }
            $time = time() + $service_buy_deliver_send_time * 60;
        }
        $config = [];
        // 统一默认获取一小时后的时间 即为配送最早为一小时后（如果有配置去配置时间)
        if (2 == $type) {
            if (C('config.service_buy_deliver_times_switch') == 1 && !empty($time)) { // 如果帮我买 开启了 帮我买多分段送达时间设置
                // 配送时间段一
                $delivery_times = explode('-', C('config.service_buy_delivery_time'));
                // 配送时间段二
                $delivery_times2 = explode('-', C('config.service_buy_delivery_time2'));


                $pin_time = date("Y-m-d");
                $pin_next_time = date('Y-m-d', strtotime('+1 day'));
                $time_info_tip = date("H:i", $time);;
                $time_info_tip = $pin_time . ' ' . $time_info_tip;
                $delivery_times_start = $pin_time . ' ' . $delivery_times[0];
                $delivery_times_end = $pin_time . ' ' . $delivery_times[1];
                $delivery_times2_start = $pin_time . ' ' . $delivery_times2[0];
                $delivery_times2_end = $pin_time . ' ' . $delivery_times2[1];

                $time_info_tip = strtotime($time_info_tip);
                $delivery_times_start = strtotime($delivery_times_start);
                $delivery_times_end = strtotime($delivery_times_end);
                if ($delivery_times_end <= $delivery_times_start) {
                    $delivery_times_end = $pin_next_time . ' ' . $delivery_times[1];
                    $delivery_times_end = strtotime($delivery_times_end);
                }

                $delivery_times2_start = strtotime($delivery_times2_start);
                $delivery_times2_end = strtotime($delivery_times2_end);
                if ($delivery_times2_end <= $delivery_times2_start) {
                    $delivery_times2_end = $pin_next_time . ' ' . $delivery_times[1];
                    $delivery_times2_end = strtotime($delivery_times2_end);
                }

                if ($time_info_tip >= $delivery_times_start && $time_info_tip <= $delivery_times_end) {
                    // 配送时间段一 起步距离
                    $service_basic_distance = C('config.service_buy_basic_distance');
                    // 配送时间段一 起步配送费
                    $service_delivery_fee = C('config.service_buy_delivery_fee');
                    // 配送时间段一 每公里的配送费
                    $service_per_km_price = C('config.service_buy_per_km_price');
                    $config['service_basic_distance'] = $service_basic_distance;
                    $config['service_delivery_fee'] = $service_delivery_fee;
                    $config['service_per_km_price'] = $service_per_km_price;

                } elseif ($time_info_tip >= $delivery_times2_start && $time_info_tip <= $delivery_times2_end) {
                    // 配送时间段二 起步距离
                    $service_basic_distance2 = C('config.service_buy_basic_distance2');
                    // 配送时间段二 起步配送费
                    $service_delivery_fee2 = C('config.service_buy_delivery_fee2');
                    // 配送时间段二 每公里的配送费
                    $service_per_km_price2 = C('config.service_buy_per_km_price2');

                    $config['service_basic_distance'] = $service_basic_distance2;
                    $config['service_delivery_fee'] = $service_delivery_fee2;
                    $config['service_per_km_price'] = $service_per_km_price2;
                } else {
                    // 如果当前时间不处于任意时间范围内，那么 取最近时间配送配置
                    if ($delivery_times_start<$delivery_times2_start) {
                        // 配送时间段一 起步距离
                        $service_basic_distance = C('config.service_buy_basic_distance');
                        // 配送时间段一 起步配送费
                        $service_delivery_fee = C('config.service_buy_delivery_fee');
                        // 配送时间段一 每公里的配送费
                        $service_per_km_price = C('config.service_buy_per_km_price');

                        $config['service_basic_distance'] = $service_basic_distance;
                        $config['service_delivery_fee'] = $service_delivery_fee;
                        $config['service_per_km_price'] = $service_per_km_price;
                    } else {
                        // 配送时间段二 起步距离
                        $service_basic_distance2 = C('config.service_buy_basic_distance2');
                        // 配送时间段二 起步配送费
                        $service_delivery_fee2 = C('config.service_buy_delivery_fee2');
                        // 配送时间段二 每公里的配送费
                        $service_per_km_price2 = C('config.service_buy_per_km_price2');

                        $config['service_basic_distance'] = $service_basic_distance2;
                        $config['service_delivery_fee'] = $service_delivery_fee2;
                        $config['service_per_km_price'] = $service_per_km_price2;
                    }
                }
            } else {
                //  基础起步距离
                $service_basic_distance = $this->config['service_give_basic_distance'];
                // 基础起步配送费
                $service_delivery_fee = $this->config['service_give_delivery_fee'];
                // 基础每公里的配送费
                $service_per_km_price = $this->config['service_give_per_km_price'];

                $config['service_basic_distance'] = $service_basic_distance;
                $config['service_delivery_fee'] = $service_delivery_fee;
                $config['service_per_km_price'] = $service_per_km_price;
            }
        } elseif (3 == $type) {
            // 帮我送-暂时未进行处理
        }
        return $config;
    }
}
