<?php
/*社区欠费自动发送微信通知提醒 一天一次

 * */
class plan_house_notice extends plan_base{
    public function runTask(){
        fdump_api(['欠费通知开始'.__LINE__,microtime(true)],'plan_house_notice_log',1);
        $daysTimes = strtotime(date('Y-m-d 09:30:30'));
        $nowTimes = time();
        if (intval($nowTimes)>$daysTimes) {
            // 超过每天的9:30:30不进行执行 避免重复执行问题
            return false;
        }
        $return_msg = $this->get_house_unpaid_list();
        $list = $return_msg['list'];
        fdump_api(['获取到通知对象'.__LINE__,microtime(true),count($list)],'plan_house_notice_log',1);
        if ($list) {
            foreach ($list as $key => $value) {
                if (floatval($value['total'])<=0) {
                    continue;
                }
                $pigcms_id = $value['pigcms_id']?$value['pigcms_id']:$value['bind_id'];
                fdump_api(['通知对应人员'.__LINE__,microtime(true),$pigcms_id,$value['address'],$value['send_user_type']],'plan_house_notice_log',1);
                $this->keepThread();
//                $href = C('config.site_url') .house_new_charge_time '/wap.php?g=Wap&c=House&a=village_my_pay&village_id='.$value['village_id'];
                $href = C('config.site_url') . '/wap.php?g=Wap&c=House&a=village_my_paylists&village_id=' . $value['village_id'].'&pigcms_id='.$pigcms_id;
                fdump_api($href,'href',true);

                $database_house_village_user_bind = D('House_village_user_bind');
                $property_name = $value['property_name'];
                if ($value['village_name']) {
                    $property_name .= "【{$value['village_name']}】";
                }
                $param = array(
                    'address' => $value['address'],
                    'total' => round_number($value['total'], 2),
                    'property_name' => $property_name,
                );

                switch ($value['send_user_type']) {
                    case '1': // 只发送业主
                        if ($value['openid']) {
                            $this->keepThread();
                            $database_house_village_user_bind->send_weixin($href,$value['openid'],$param);
                        }
                        $this->keepThread();
                        break;
                    case '2': // 业主和家属
                        if ($value['openid']) {
                            $this->keepThread();
                            $database_house_village_user_bind->send_weixin($href,$value['openid'],$param);
                        }
                        // 查询家属
                        $condition = '`u`.`uid` = `b`.`uid` AND `b`.`status`=1 AND `b`.`type` in(1,2) AND b.vacancy_id ='.$value['vacancy_id'];
                        $condition_table  = array(C('DB_PREFIX').'user'=>'u',C('DB_PREFIX').'house_village_user_bind'=>'b');
                        $field = '`u`.`openid`';
                        $bind_list = D()->field($field)->table($condition_table)->where($condition)->select();
                        $this->keepThread();
                        if ($bind_list) {
                            foreach ($bind_list as $bind) {
                                $this->keepThread();
                                if(!empty($bind['openid'])){
                                    $database_house_village_user_bind->send_weixin($href,$bind['openid'],$param);
                                }
                            }
                        }
                        break;
                    case '3':// 只发送家属
                        $condition = '`u`.`uid` = `b`.`uid` AND `b`.`status`=1 AND `b`.`type` in(1,2) AND b.vacancy_id ='.$value['vacancy_id'];
                        $condition_table  = array(C('DB_PREFIX').'user'=>'u',C('DB_PREFIX').'house_village_user_bind'=>'b');
                        $field = '`u`.`openid`';
                        $bind_list = D()->field($field)->table($condition_table)->where($condition)->select();
                        $this->keepThread();
                        if ($bind_list) {
                            foreach ($bind_list as $bind) {
                                $this->keepThread();
                                if(!empty($bind['openid'])){
                                    $database_house_village_user_bind->send_weixin($href,$bind['openid'],$param);
                                }
                            }
                        }
                        break;
                }
            }

        }

        fdump_api(['通知对应完成'.__LINE__,microtime(true)],'plan_house_notice_log',1);
        // 处理欠费预存款问题//以及未交费订单问题
        // $special_arr = $list['special_arr'];
        // if ($special_arr) {
        //     foreach ($special_arr as $val) {
        //         // D('House_deposit_order')->special_info($val);

        //         //D('Village_unpaid_pay')->unpaid_pay($val);//处理物业费以及自定义缴费===>郭先森

        //     }
        // }


        fdump_api(['1移动抄表'.__LINE__,microtime(true)],'plan_house_notice_log',1);
        $house_worker_msg = $this->send_meter_read_house_worker();
        fdump_api(['2移动抄表'.__LINE__,microtime(true)],'plan_house_notice_log',1);
        if($house_worker_msg){
            foreach($house_worker_msg as $k1=>$v1){
                $this->keepThread();
                $token_info = array();
                if(date('m',time()) == $v1['cycle_time'] && date('H:i',time()) == $v1['remind_time']){

                    $href = C('config.site_url').'/wap.php';//移动抄表地址未填完//后期补充

                    $data = array(
                        'tempKey' => 'OPENTM405462911',
                        'dataArr' => array(
                            'href' => $href,
                            'wecha_id' => $v1['openid'],
                            'first' => '您有一项工作需处理',
                            'keyword1' => '工作',
                            'keyword2' =>'已发送',
                            'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']),
                            'keyword4' => '',
                            'remark' => '\n请点击查看详细信息！'
                        )
                    );
                    $this->keepThread();
                    D('Property_group')->send_msg('work', $v1['wid'], $data, $token_info, false, $this);
                }
            }
            fdump_api(['3移动抄表'.__LINE__,microtime(true)],'plan_house_notice_log',1);
        }

        fdump_api(['1智慧二维码'.__LINE__,microtime(true)],'plan_house_notice_log',1);
        $send_wisdom_qrcode_msg = $this->send_wisdom_qrcode();
        fdump_api(['2智慧二维码'.__LINE__,microtime(true)],'plan_house_notice_log',1);
        if($send_wisdom_qrcode_msg){
            $token_info = array();
            foreach($send_wisdom_qrcode_msg as $k2=>$v2){
                $this->keepThread();
                if($v2['record_type'] == 1){//每天
                    $now_time = date('Y-m-d',time());

                    $remind_time_list = explode(',', $v2['record_time']);
                    if( !empty( $remind_time_list ) ){
                        $startTimeArr = array_map(function($time) {
                            return strtotime(date('Y-m-d',time()).' '.explode('-', $time)[0]);
                        }, $remind_time_list);
                        $isExists = array_search(time(), $startTimeArr);
                        if($isExists !== false){
                            $href = C('config.site_url').'/wap.php';//智慧二维码未填完//后期补充

                            $data = array(
                                'tempKey' => 'OPENTM405462911',
                                'dataArr' => array(
                                    'href' => $href,
                                    'wecha_id' => $v2['openid'],
                                    'first' => '您有一项工作需处理',
                                    'keyword1' => '工作',
                                    'keyword2' =>'已发送',
                                    'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']),
                                    'keyword4' => '',
                                    'remark' => '\n请点击查看详细信息！'
                                )
                            );
                            $this->keepThread();
                            D('Property_group')->send_msg('work', $v2['wid'], $data, $token_info);
                        }
                    }
                }elseif($v2['record_type'] == 2){//每周
                    if($v2['record_time']){
                        $record_time = explode(',',$v2['record_time']);
                        $dates = date('w',time());
                        if(in_array($dates,$record_time)){
                            if(time() == strtotime(date('Y-m-d',time()).' '.$v2['remind_time'])){
                                $href = C('config.site_url').'/wap.php';//智慧二维码地址未填完//后期补充

                                $data = array(
                                    'tempKey' => 'OPENTM405462911',
                                    'dataArr' => array(
                                        'href' => $href,
                                        'wecha_id' => $v2['openid'],
                                        'first' => '您有一项工作需处理',
                                        'keyword1' => '工作',
                                        'keyword2' =>'已发送',
                                        'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']),
                                        'keyword4' => '',
                                        'remark' => '\n请点击查看详细信息！'
                                    )
                                );
                                D('Property_group')->send_msg('work', $v2['wid'], $data, $token_info);
                            }
                        }

                    }

                }elseif($v2['record_type'] == 1){//每月
                    if($v2['record_time']){
                        $record_time = explode(',',$v2['record_time']);
                        $dates = date('d',time());
                        if(in_array($dates,$record_time)){
                            if(time() == strtotime(date('Y-m-d',time()).' '.$v2['remind_time'])){
                                $href = C('config.site_url').'/wap.php';//智慧二维码地址未填完//后期补充

                                $data = array(
                                    'tempKey' => 'OPENTM405462911',
                                    'dataArr' => array(
                                        'href' => $href,
                                        'wecha_id' => $v2['openid'],
                                        'first' => '您有一项工作需处理',
                                        'keyword1' => '工作',
                                        'keyword2' =>'已发送',
                                        'keyword3' => date('H时i分',$_SERVER['REQUEST_TIME']),
                                        'keyword4' => '',
                                        'remark' => '\n请点击查看详细信息！'
                                    )
                                );
                                $this->keepThread();
                                D('Property_group')->send_msg('work', $v2['wid'], $data, $token_info);
                            }
                        }
                    }
                }
            }
            fdump_api(['3智慧二维码'.__LINE__,microtime(true)],'plan_house_notice_log',1);
        }
        fdump_api(['结束所有通知'.__LINE__,microtime(true)],'plan_house_notice_log',1);

        return true;
    }

    /*得到未缴列表*/
    public function get_house_unpaid_list(){
        if(C('config.customized_meter_reading')){
            $return_msg = array(
                'list' => [],
                'special_arr' => []
            );
            return $return_msg;
        }
        $propertyIdArr=D('House_village')->checkNewChargeAllIds();
        $whereVillage = ['status'=>1];
        if (!empty($propertyIdArr)) {
            // 新版本收费生效后 老版本欠费不在进行推送催缴，避免客户进入老版本缴费导致问题
            $whereVillage['property_id'] = ['not in', $propertyIdArr];
        }
        $village_id = D('House_village')->where($whereVillage)->getField('village_id',true);
        $this->keepThread();
        if ($village_id) {
            $village_id = implode(',', $village_id);
            $village_id = '('.$village_id.')';
        }
        // 水电燃气停车费
        $condition = '`b`.`status`=1 AND `v`.`status`=1 AND `v`.`village_id` = `b`.`village_id`  AND (`b`.`water_price`>0 OR `b`.`electric_price`>0 OR `b`.`gas_price`>0 OR `b`.`park_price`>0) AND b.type in (0,3) AND v.status=1 AND `v`.`send_date` ="'.intval(date('d')).'"';
        $condition_table  = array(C('DB_PREFIX').'house_village'=>'v',C('DB_PREFIX').'house_village_user_bind'=>'b');
        $field = '`b`.`pigcms_id`';

        $list = D('')->field($field)->table($condition_table)->where($condition)->select();
        $this->keepThread();
        $ids = array();
        foreach ($list as $key => $value) {
            $ids[] = $value['pigcms_id'];
        }

        // 物业费
        $condition = '( ((property_endtime <> "" OR property_endtime <> null) AND property_endtime <="'.strtotime(date('Y-m-d')).'")) AND status=1 AND type in (0,3) AND village_id in '.$village_id;
        $list = M('House_village_user_bind')->field('pigcms_id,property_endtime')->where($condition)->select();
        $this->keepThread();

        $m = date("Y-m");
        $last_day = date('d', strtotime("$m +1 month -1 day"));
        foreach ($list as $key => $value) {
            $d = date('d',$value['property_endtime']);
            if (($d > $last_day && $last_day == date('d')) || $d == date('d')) {
                $ids[] = $value['pigcms_id'];
            }
            $this->keepThread();
        }
        $this->keepThread();

        // 自定义缴费
        $condition = '`b`.`status`=1 AND `psb`.`village_id`=`b`.`village_id` AND `psb`.`pigcms_id` = `b`.`pigcms_id`  AND `psb`.`standard_id` = `ps`.`standard_id` AND `psb`.`cycle_sum` > `psb`.`paid_cycle` AND `psb`.`start_time` <"'.time().'" AND `b`.`village_id` in '.$village_id;;
        $condition_table  = array(C('DB_PREFIX').'house_village_payment_standard_bind'=>'psb',C('DB_PREFIX').'house_village_user_bind'=>'b',C('DB_PREFIX').'house_village_payment_standard'=>'ps');
        $field = '`psb`.*,`ps`.`cycle_type`,`ps`.`pay_cycle`,`b`.`pigcms_id`';

        $list1 = D('')->field($field)->table($condition_table)->where($condition)->select();
        $list1 = $list1 ? $list1 : array();
        $this->keepThread();

        // 车位自定义缴费
        $condition = '`b`.`status`=1 AND `psb`.`village_id`=`b`.`village_id` AND `bp`.`user_id` = `b`.`pigcms_id`  AND `bp`.`position_id` = `psb`.`position_id`  AND `psb`.`standard_id` = `ps`.`standard_id` AND `psb`.`cycle_sum` > `psb`.`paid_cycle` AND `psb`.`start_time` <"'.time().'" AND `b`.`village_id` in '.$village_id;
        $condition_table  = array(C('DB_PREFIX').'house_village_payment_standard_bind'=>'psb',C('DB_PREFIX').'house_village_user_bind'=>'b',C('DB_PREFIX').'house_village_payment_standard'=>'ps',C('DB_PREFIX').'house_village_bind_position'=>'bp');
        $field = '`psb`.*,`ps`.`cycle_type`,`ps`.`pay_cycle`,`b`.`pigcms_id`';

        $list2 = D('')->field($field)->table($condition_table)->where($condition)->select();
        $list2 = $list2 ? $list2 : array();

        $this->keepThread();

        $custom_list = array_merge($list1,$list2);

        $custom_ids = array();
        if ($custom_list) {
            foreach ($custom_list as $key => $value) {
                switch ($value['cycle_type']) {
                    case 'Y': // 年
                        // 计算到期时间 = 开始时间 + 已缴时间（收费周期*已缴周期）
                        $end_time = $value['start_time'] + $value['pay_cycle']*$value['paid_cycle']*86400*365;
                        break;
                    case 'M': //月
                        $end_time = $value['start_time'] + $value['pay_cycle']*$value['paid_cycle']*86400*30;
                        break;
                    case 'D': // 日
                        $end_time = $value['start_time'] + $value['pay_cycle']*$value['paid_cycle']*86400;
                        break;
                }
                if ($end_time<time() && date("d",$end_time) == date("d")) {
                    $custom_ids[] = $value['pigcms_id'];;
                }
                $this->keepThread();
            }
        }

        $this->keepThread();

        //欠费业主id集
        $ids = array_merge($ids,$custom_ids);
        $condition = '`b`.`status`=1 AND `v`.`status`=1 AND f.floor_id=b.floor_id AND b.pigcms_id in('.implode(',',$ids).') AND b.village_id=v.village_id AND b.uid=u.uid AND b.village_id in '.$village_id;

        $condition_table  = array(C('DB_PREFIX').'house_village_floor'=>'f',C('DB_PREFIX').'house_village_user_bind'=>'b',C('DB_PREFIX').'user'=>'u',C('DB_PREFIX').'house_village'=>'v');
        $field = 'f.single_id as floor_single_id,b.single_id,b.floor_id,b.layer_id,b.room_addrss,b.layer_num,b.housesize,v.village_name,b.village_id,b.name,b.property_endtime,b.pigcms_id,b.water_price,b.electric_price,b.gas_price,b.park_price,f.floor_name,f.floor_layer,f.property_fee,v.property_name,v.send_user_type,v.send_date,v.property_price,u.openid,u.uid,b.vacancy_id';
        $order = ' `b`.`pigcms_id` DESC';

        // $list = D('')->field($field)->table($condition_table)->where($condition)->order($order)->select();
        $list = D('')->field($field)->table($condition_table)->where($condition)->order($order)->group('b.pigcms_id')->select();

        $this->keepThread();

        // 整合物业欠费/自定义缴费欠费数组
        $special_arr = array();
        if($list){
            foreach ($list as $ks=>&$v){
                $this->keepThread();
                $village_info = D('House_village')->where(array('village_id'=>$v['village_id']))->find();
                $property_name = M('House_property')->where(['id'=>$village_info['property_id']])->getField('property_name');
                $v['property_name'] = $property_name;
                $this->keepThread();
                //物业欠费
                $property_price = 0;
                $dateMonth = [];
                if ($v['property_endtime'] && $v['property_endtime'] < strtotime(date("Y-m-d"))) {
                    // 计算欠缴物业费
                    $standard_id = M('House_village_property_standard_bind')->where(['pigcms_id'=>$v['vacancy_id'],'is_del'=>1])->field('standard_id')->find();
                    $this->keepThread();
                    if(!$standard_id['standard_id']){
                        $property_price = 0;
                    }elseif ($v['property_endtime'] && $v['property_endtime'] < time()){
                        $date = M('House_village_property_standard')->where(['id'=>$standard_id['standard_id']])->field('date_month')->find();
                        $this->keepThread();
                        $num = D('House_village_user_bind')->getMonthNum($v['property_endtime'] + 1, time());
                        $this->keepThread();
                        if(date('d',time()) > date('d',$v['property_endtime'] + 1))
                            $num+=1;
                        $start_time = strtotime(date('Y-m',$v['property_endtime']));
                        $dateMonth[] = $start_time;
                        for ($i=1;$i<$num;$i++){
                            $dateMonth[] = strtotime("+ $i month",$start_time);
                        }
                        $this->keepThread();
                        $property_price = $this->property_price($dateMonth,unserialize($date['date_month']),$v['housesize']);
                    }else{
                        $property_price = 0;
                    }
                    if ($property_price > 0) {
                        $property_msg = array(
                            'money' => $property_price,
                            'property_month_num' => $num,
                            'uid' => $v['uid'],
                            'bind_id' => $v['pigcms_id'],
                            'village_id' => $v['village_id'],
                            'pay_type' => 'property',
                            'payment_id' => '-2'
                        );
                        $special_arr[] = $property_msg;
                        $v['property_price'] = $property_price;
                    }
                }
                $this->keepThread();

                //自定义项欠费
                $payment_list = D('')->table(array(C('DB_PREFIX').'house_village_payment_standard_bind'=>'psb',
                    C('DB_PREFIX').'house_village_payment_standard'=>'ps',
                    C('DB_PREFIX').'house_village_payment'=>'p'))
                    ->where("psb.pigcms_id= '".$v['pigcms_id']."' AND psb.village_id= '".$v['village_id']."' AND p.payment_id = psb.payment_id AND `psb`.`cycle_sum` > `psb`.`paid_cycle` AND ps.standard_id = psb.standard_id AND `psb`.`start_time` <".time())->select();
                $payment_list = $payment_list ? $payment_list : array();

                $this->keepThread();

                // 车位缴费
                $position_payment_list = D('House_village_bind_position')->get_user_position_payment_list(array('pigcms_id'=>$v['pigcms_id']));
                $this->keepThread();
                $payment_list = array_merge($payment_list, $position_payment_list);
                $cunstom_money = 0;
                if ($payment_list) {
                    foreach ($payment_list as $key => $value) {
                        $this->keepThread();
                        switch ($value['cycle_type']) {
                            case 'Y':
                                $end_time = $value['start_time'] + $value['pay_cycle']*$value['paid_cycle']*86400*365;
                                break;
                            case 'M':
                                $end_time = $value['start_time'] + $value['pay_cycle']*$value['paid_cycle']*86400*30;
                                break;
                            case 'D':
                                $end_time = $value['start_time'] + $value['pay_cycle']*$value['paid_cycle']*86400;
                                break;
                        }
                        if ($end_time<time()) {
                            $this->keepThread();
                            $num = $this->getTimeNum($end_time,time(),$value['cycle_type']);
                            // 除以周期 向上取整
                            $num = ceil($num/$value['pay_cycle']);
                            // 除以周期 取计算出来的周期和剩余周期取最小值
                            $num = min($num,$value['cycle_sum']);
                            if ($value['pay_type']==1) {
                                $cunstom_money += $value['pay_money'] * $num;
                            }else{
                                $cunstom_money += $value['pay_money'] * $num * $value['metering_mode_val'];
                            }
                        }
                        if ($cunstom_money > 0) {
                            $cunstom_msg = array(
                                'money' => $cunstom_money,
                                'payment_bind_id' => $value['bind_id'],
                                'payment_paid_cycle' => $num,
                                'uid' => $v['uid'],
                                'bind_id' => $v['pigcms_id'],
                                'village_id' => $v['village_id'],
                                'pay_type' => 'custom_payment',
                                'payment_id' => $value['payment_id']
                            );
                            $special_arr[] = $cunstom_msg;
                        }
                        $this->keepThread();
                    }
                }

                $this->keepThread();

                $v['total'] =  $v['water_price']+$v['electric_price']+$v['gas_price']+$v['park_price'] + $property_price + $cunstom_money;
                if (floatval($v['total'])<=0) {
                    unset($list[$ks]);
                } else {
                    $word_data = array();
                    $single_id = $v['single_id'] ? $v['single_id'] : ($v['floor_single_id']?$v['floor_single_id']:0);
                    if ($single_id) {
                        $single_name = M('House_village_single')->where(array('id' => $single_id))->getField('single_name');
                    } else {
                        $single_name = '';
                    }
                    if ($v['layer_id']) {
                        $layer_name = M('House_village_layer')->where(array('id' => $v['layer_id']))->getField('layer_name');
                    } else {
                        $layer_name = $v['layer_num'];
                    }

                    $word_data['layer'] = $layer_name;
                    $word_data['room'] = $v['room_addrss'];
                    $word_data['floor_name'] = $v['floor_name'];
                    if (!$single_name) {
                        $single_name = $v['floor_layer'];
                    }
                    $word_data['single_name'] = $single_name;
                    $this->keepThread();
                    $room_diy_name = D('House_village')->get_single_floor_room($v['single_id'],$v['floor_id'],$v['layer_id'],$v['vacancy_id'],$v['village_id']);
                    $v['address'] = $room_diy_name;
                }
            }
        }
        $return_msg = array(
            'list' => $list,
            'special_arr' => $special_arr
        );

        return $return_msg;
    }

    //获得相差时间 $date2>$date1
    function getTimeNum($date1,$date2,$type){
        switch ($type) {
            case 'Y': //相差年份
                $date_1['y'] = date('Y',$date1);
                $date_2['y'] = date('Y',$date2);
                $num = $date_2['y']-$date_1['y'];
                if (strtotime(date('m-d',$date2))-strtotime(date('m-d',$date1))>=0) {
                    $num += 1;
                }
                break;
            case 'M': //相差月份
                list($date_1['y'],$date_1['m'],$date_1['d']) = explode("-",date('Y-m-d',$date1));
                list($date_2['y'],$date_2['m'],$date_2['d']) = explode("-",date('Y-m-d',$date2));
                $num = ($date_2['y']-$date_1['y'])*12 +$date_2['m']-$date_1['m'];
                if ($date_2['d']- $date_1['d'] >= 0) { //多相差1天则加一个月
                    $num += 1;
                }
                break;
            case 'D': //相差天数
                $num = abs( ceil(($date2-$date1)/86400));
                break;
        }
        return $num;
    }



    public function send_meter_read_house_worker()
    {
        //移动抄表
        $this->keepThread();
        $read_person = M('meter_read_person')->where(array('status'=>1))->select();
        $dataList = [];
        foreach($read_person as $k=>$v){
            $this->keepThread();
            $read_cate = M('Meter_read_cate')->where(array('id'=>$v['cate_id'],'village_id'=>$v['village_id']))->find();
            $house_worker = D('House_worker')->where(array('status'=>1,'is_del'=>0,'village_id'=>$v['village_id'],'wid'=>$v['uid']))->find();
            if($read_cate && $house_worker['openid']){
                $list['remind_time'] = $v['remind_time'];
                $list['cycle_time'] = $read_cate['cycle_time'];
                $list['uid'] = $v['uid'];//工作人员id
                $list['openid'] = $house_worker['openid'];
                $list['wid'] = $house_worker['wid'];
                $dataList[] = $list;
            }
        }
        return $dataList;
    }


    public function send_wisdom_qrcode()
    {
        $this->keepThread();
        $qrcode_person = M('Wisdom_qrcode_person')->where(array('status'=>1))->select();
        $dataList = [];
        foreach($qrcode_person as $k=>$v){
            $this->keepThread();
            $house_worker = D('House_worker')->where(array('status'=>1,'is_del'=>0,'wid'=>$v['uid']))->find();
            if($house_worker['openid']){
                $list['wid'] = $house_worker['wid'];
                $list['openid'] = $house_worker['openid'];
                $list['uid'] = $v['uid'];
                $list['record_type'] = $v['record_type'];//周期类型
                $list['record_time'] = $v['record_time'];//周期时间
                $list['remind_time'] = $v['remind_time'];//提醒时间
                $dataList[] = $list;
            }
        }
        return $dataList;
    }

    /**
     * 计算物业费
     * @author lijie
     * @date_time 2020/07/20 15:48
     * @param $dateMonths
     * @param $chargingStandard
     * @param $housesize
     * @return float|int
     */
    public function property_price($dateMonths,$chargingStandard,$housesize)
    {
        $this->keepThread();
        $property_price = 0;
        foreach ($dateMonths as $value){
            $this->keepThread();
            foreach ($chargingStandard as $v){
                $this->keepThread();
                if($value >= strtotime($v['date_month'])){
                    $price = $v['price'];
                }
            }
            if(isset($price)){
                $property_price += $price*$housesize;
            }
            unset($price);
        }
        return $property_price;
    }

}
?>