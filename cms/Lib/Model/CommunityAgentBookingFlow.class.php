<?php
// Shared existing booking implementation; keep both entry points on the same rules.
trait CommunityAgentBookingFlow {
    public function order_detail() {
        $database_merchant_workers = D('Merchant_workers');
        $database_appoint_store = D('Appoint_store');
        $database_user_long_lat = D('User_long_lat');
        $database_user = D('User');
        $database_appoint = D('Appoint');
        $database_appoint_product =  D('Appoint_product');
        $database_area = D('Area');
        $database_appoint_order = D('Appoint_order');
        $database_appoint_category = D('Appoint_category');

        $merchant_workers_id = I('merchantWorkerId');
        $appoint_id = I('appoint_id');
        $order_from = I('order_from'); //fromWorkerOrder-技师代下单
        if (empty($appoint_id)) {
            $this->returnCodeError('当前服务不存在！');
        }
        $arr = array();

        $now_appoint = $database_appoint->get_appoint_by_appointId($appoint_id, 'hits-setInc');
        if (empty($now_appoint)) {
            $this->returnCodeError('当前预约项不存在！');
        }
        if ($now_appoint['start_time'] > $_SERVER['REQUEST_TIME']) {
            $this->returnCodeError('此单还未开始！');
        }

        if($order_from=='fromWorkerOrder'){
            $merchant_workers_id = $this->_uid;
        }

        // 自定义表单项
        $category = $database_appoint_category->get_category_by_id($now_appoint['cat_id']);
        if (empty($category['cue_field'])) {
            $category = $database_appoint_category->get_category_by_id($category['cat_fid']);
        }

        $cuefield_icon = [
            0 => 'txt_icon', // 单行文本
            1 => 'txt_icon',// 多行文本
            2 => 'map_icon',// 地图
            3 => 'select_icon',// 下拉选择框
            4 => 'number_icon',// 数字格式
            5 => 'mail_icon',// 邮件格式
            6 => 'datetime_icon',// 日期格式
            7 => 'datetime_icon',// 时间格式
            8 => 'phone_icon',// 手机格式
            9 => 'datetime_icon',// 日期时间格式
        ];
        if ($category['is_autotrophic']) {
            // 是否平台自营 0，商家入驻 1，平台自营 2，第三方入驻   非商家入驻单独处理
            $cuefield = [];
            if ($category) {
                $cuefield = unserialize($category['cue_field']);
                $site_url_appoint_icon = $this->config['site_url'] . '/static/appoint_new/icon_img/';
                foreach ($cuefield as &$val) {
                    $val['icon_img'] = $site_url_appoint_icon . $cuefield_icon[$val['type']] . '.png';
                    $sort[] = $val['sort'];
                    if (!isset($val['value'])) {
                        $val['value'] = '';
                    }
                }
                array_multisort($sort, SORT_DESC, $cuefield);
            }
            $now_city = D('Area')->get_area_by_areaId($this->config['now_city']);

            $arr['city_name'] = $now_city['area_name'];
            $arr['formData'] = $cuefield ? $cuefield : [];
            $this->returnCode(0, $arr);
        }

        $now_appoint_arr = [];
        $now_appoint_arr['appoint_type'] = $now_appoint['appoint_type'];
        $now_appoint_arr['is_store'] = $now_appoint['is_store'];
        // 产品列表
        $appointProduct = $database_appoint_product->get_productlist_by_appointId($appoint_id);
        $defaultAppointProduct = [];
        if ($appointProduct) {
            $arr['appoint_product'] = $appointProduct;
            if (empty($_POST['menuId'])) {
//                $defaultAppointProduct = $appointProduct[0];
                $this->returnCodeError('请选择对应预约规格！');
            } else {
                $defaultAppointProduct = $database_appoint_product->get_product_info($_POST['menuId']);
                $defaultAppointProduct = $defaultAppointProduct['detail'];
                if (empty($defaultAppointProduct)) {
                    $this->returnCodeError('当前所选预约规格不存在！');
                }
            }
            $arr['defaultAppointProduct'] = $defaultAppointProduct;
        }
        // 如果有默认选中的规格
        if ($defaultAppointProduct) {
            // 预约规格名称 存在赋值 不存在为空
            $now_appoint_arr['product_name'] = $defaultAppointProduct['name'] ? $defaultAppointProduct['name'] : '';
            // 预约规格定价 如果存在就有，不存在即-1
            $now_appoint_arr['product_payment_price'] = isset($defaultAppointProduct['payment_price']) ? $defaultAppointProduct['payment_price'] : -1;
            // 预约规格全价 如果存在就有，不存在即-1
            $now_appoint_arr['product_price'] = isset($defaultAppointProduct['price']) ? $defaultAppointProduct['price'] : -1;
        } else {
            $now_appoint_arr['product_name'] = '';
            $now_appoint_arr['product_payment_price'] = -1;
            $now_appoint_arr['product_price'] = -1;
            $now_appoint_arr['product_txt_tip'] = '';
        }
        // 显示全价
        $now_appoint_arr['total_price'] = floatval($now_appoint_arr['product_price'])>=0 ? $now_appoint_arr['product_price'] : $now_appoint['appoint_price'];
        if ($now_appoint['payment_status']==1) {
            // 显示应支付金额
            $now_appoint_arr['pay_price'] = floatval($now_appoint_arr['product_payment_price'])>=0 ? $now_appoint_arr['product_payment_price'] : $now_appoint['payment_money'];
            // 显示
            $now_appoint_arr['txt_tip'] = '预付金';
            $now_appoint_arr['deposit_txt_tip'] = '定金'.$this->config['Currency_symbol'].$now_appoint_arr['pay_price'];
            $now_appoint_arr['payment_money'] = $now_appoint['payment_money'];
            if (floatval($now_appoint_arr['product_price'])<0 && 0==$now_appoint['is_appoint_price'] && floatval($now_appoint_arr['total_price'])<=0) {
                $now_appoint_arr['txt_tip'] = '预付金';
                $now_appoint_arr['total_price'] = "面议({$now_appoint_arr['deposit_txt_tip']})";
                $now_appoint_arr['deposit_txt_tip'] = '';
            }
        } else {
            // 显示应支付金额
            $now_appoint_arr['pay_price'] = floatval($now_appoint_arr['product_price'])>=0 ? $now_appoint_arr['product_price'] : $now_appoint['appoint_price'];
            // 显示
            if (floatval($now_appoint_arr['product_price'])<0 && 0==$now_appoint['is_appoint_price']) {
                $now_appoint_arr['txt_tip'] = '一口价';
                if (floatval($now_appoint_arr['total_price'])<=0) {
                    $now_appoint_arr['total_price'] = '面议';
                }
            } else {
                $now_appoint_arr['txt_tip'] = '一口价';
            }
            $now_appoint_arr['deposit_txt_tip'] = '';
        }
        $now_appoint_arr['payment_status'] = $now_appoint['payment_status'];
        // 记录初始价格
        $now_appoint_arr['order_price'] = $now_appoint_arr['pay_price'];
        //商家优惠券
        if ($this->DEVICE_ID == 'wxapp') {
            $platform = 'wxapp';
        }
        elseif($this->is_weixin_browser){
            $platform = 'weixin';
        }
        elseif($this->is_app_browser){
            $platform = 'app';
        }
        else{
            $platform = 'wap';
        }
        $order_type = 'appoint';

//        $card_info = D('Card_new')->get_card_by_uid_and_mer_id($this->_uid, $now_appoint['mer_id']);
        // 会员卡默认为空  暂时不进行处理
        $card_info = [];
        if (empty($card_info)||empty($card_info['discount']) || $card_info['discount'] == '0.00') {
            $card_info['discount'] = 10;
        }
        // 先折后减
        $now_appoint_arr['pay_price']  = ($now_appoint_arr['pay_price']) * $card_info['discount'] / 10;
        $now_appoint_arr['card_info'] = $card_info;

//        $tmp_order = $now_appoint;
//        if (floatval($now_appoint_arr['pay_price'])>0) {
//            $tmp_order['total_money'] = $now_appoint_arr['pay_price'];
//        } else {
//            $tmp_order['total_money'] = 0;
//        }
//        $tmp_order['uid'] = $this->_uid;

//        $merchant_coupon_id = $_POST['merchant_coupon_id']?$_POST['merchant_coupon_id']:0;
//        $system_coupon_id = $_POST['system_coupon_id']?$_POST['system_coupon_id']:0;
        // 此处暂时不支持优惠券
//        $merchant_coupon_id = 0;
//        $system_coupon_id = 0;

//        $tmp_order['freight_charge'] = 0;

        // 返回商家优惠券和系统优惠券跳转链接-改为不支持优惠券（去支付页面进行优惠券选择）
//        $appoint_base_url = D('Appoint')->appoint_base_url;
//        $arr['system_coupon_list'] = $this->config['site_url'].$appoint_base_url."pages/pay/coupon?appoint_id={$appoint_id}&type=appoint&coupon_type=system&coupon_id=".$system_coupon_id;
//        $arr['merchant_coupon_list'] =  $this->config['site_url'].$appoint_base_url."pages/pay/coupon?appoint_id={$appoint_id}&type=appoint&coupon_type=mer&coupon_id=".$merchant_coupon_id;
//
//        if (!empty($merchant_coupon_id)) {
//            $tmp_coupon = D('Card_new_coupon')->get_coupon_info($merchant_coupon_id);
//        } else {
//            $card_coupon_list = D('Card_new_coupon')->get_noworder_coupon_list($tmp_order, $order_type, $platform, 'appoint');
//
//            $tmp_coupon = reset($card_coupon_list);
//        }
//        if (!empty($tmp_coupon)) {
//            $arr['has_merchant_coupon'] = '1';
//            $mer_coupon['had_id'] = $tmp_coupon['id'];
//            $mer_coupon['order_money'] = $tmp_coupon['order_money'];
//            $mer_coupon['discount'] = $tmp_coupon['discount'];
//        } else {
//            $arr['has_merchant_coupon'] = '0';
//            $mer_coupon = array();
//        }
//        $discount = empty($mer_coupon['discount']) ? 0 : $mer_coupon['discount'];
//        // 商家优惠券
//        $now_appoint_arr['pay_price']  = floatval($now_appoint_arr['pay_price']) - floatval($discount);
//        $arr['mer_coupon'] = $mer_coupon;

//        if(!empty($system_coupon_id)){
//            $tmp_coupon = D('System_coupon')->get_coupon_info($system_coupon_id);
//            if($tmp_coupon['is_discount']==1){
//                if($tmp_coupon['discount_type']==1){//减免运费
//                    $tmp_coupon['discount_money'] =round( $tmp_order['freight_charge'] * (100-$tmp_coupon['discount_value']*10)/100,2);
//                }else{
//                    $tmp_coupon['discount_money'] =round( $tmp_order['total_money'] * (100-$tmp_coupon['discount_value']*10)/100,2);
//                }
//            }else{
//                $tmp_coupon['discount_money'] = $tmp_coupon['discount'];
//            }
//        }else{
//            $phone = D('User')->where(array('uid' => $this->_uid))->getField('phone');
//            $now_coupon = D('System_coupon')->get_noworder_coupon_list($tmp_order, $order_type,$phone,$this->_uid, $platform, 'appoint');
//
//            $tmp_coupon = reset($now_coupon);
//        }
//
//        if($tmp_coupon){
//            $arr['has_system_coupon'] = '1';
//            $system_coupon['had_id'] = $tmp_coupon['id'];
//            //	$system_coupon['coupon_id'] = $tmp_coupon['coupon_id'];
//            $system_coupon['order_money'] = $tmp_coupon['order_money'];
//
//            $system_coupon['discount'] = $tmp_coupon['discount_money'];
//            $system_coupon['is_discount'] = $tmp_coupon['is_discount'];
//            $system_coupon['discount_value'] = $tmp_coupon['discount_value'];
//
//            $system_coupon['discount_type'] = $tmp_coupon['discount_type'];
//            if($tmp_coupon['discount_type']=='1'){//如果是减免运费,减免的金额不能还大于运费金额
//                $system_coupon['discount'] = $tmp_order['freight_charge']>$system_coupon['discount']?$system_coupon['discount']:$tmp_order['freight_charge'];
//            }
//        }else{
//            $arr['has_system_coupon'] = '0';
//            $system_coupon =array();
//        }
//
//        $system_discount = empty($system_coupon['discount']) ? 0 : $system_coupon['discount'];
//        // 商家优惠券
//        $now_appoint_arr['pay_price']  = floatval($now_appoint_arr['pay_price']) - floatval($system_discount);
//
//        // 未使用优惠券钱金额
//        $now_appoint_arr['order_price'] = $tmp_order['total_money'];
//
//        $arr['system_coupon'] = $system_coupon;
        // 不支持优惠券后全部默认为空和不存在
        $arr['has_merchant_coupon'] = '0';
        $mer_coupon = array();
        $arr['mer_coupon'] = $mer_coupon;
        $arr['has_system_coupon'] = '0';
        $system_coupon =array();
        $arr['system_coupon'] = $system_coupon;


        $store_id = 0;
        if($order_from=='fromWorkerOrder'){
            $now_user = D('Merchant_workers')->field('merchant_worker_id as uid,name,merchant_store_id')->where(['merchant_worker_id'=>$this->_uid])->find();
            $store_id = $now_user['merchant_store_id'];
        }else {
            $now_user = $database_user->get_user($this->_uid);
            if (empty($this->user_session['phone']) && !empty($now_user['phone'])) {
                $_SESSION['user']['phone'] = $this->user_session['phone'] = $now_user['phone'];
            }
        }
        $arr['now_user'] = $now_user;

        $now_appoint_arr['store_list'] = $database_appoint_store->get_storelist_by_appointId($now_appoint['appoint_id'],$store_id);

        $long_lat = $database_user_long_lat->getLocation($_SESSION['openid']);
        if (!empty($long_lat) && $long_lat['lat'] && $long_lat['long']) {
            foreach ($now_appoint_arr['store_list'] as &$value) {
                $value['range'] = getDistance($value['lat'], $value['long'], $long_lat['lat'], $long_lat['long']);
                $value['range_txt'] = getRange($value['range']);
                $rangeSort[] = $value['range'];
                array_multisort($rangeSort, SORT_ASC, $now_appoint_arr['store_list']);
            }

            $arr['long_lat'] = $long_lat;
        }
        $now_city = $database_area->get_area_by_areaId($this->config['now_city']);

        $arr['city_name'] = $now_city['area_name'];

        $tmp_gap = $now_appoint['time_gap'];
        $beforeTime = $now_appoint['before_time'] > 0 ? ($now_appoint['before_time']) * 3600 : 0;
        $gap = $tmp_gap > 0 ? $tmp_gap * 60 : 1800;

        if (!empty($merchant_workers_id)) {
            // 预约开始时间 结束时间
            $merchant_workers_info = $database_merchant_workers->where(array('merchant_worker_id' => $merchant_workers_id))->find();
            $office_time = unserialize($merchant_workers_info['office_time']);
            if(isset($office_time['open']) && isset($office_time['close'])){
                $office_time = array($office_time);
            }

            $tmp_gap = $merchant_workers_info['time_gap'];
            // 发起预约时候的起始时间 还有提前多长时间可预约
            $beforeTime = $merchant_workers_info['before_time'] > 0 ? ($merchant_workers_info['before_time']) * 3600 : 0;
            $gap = $tmp_gap > 0 ? $tmp_gap * 60 : 1800;
        } else {
            $office_time = unserialize($now_appoint['office_time']);
            if(isset($office_time['open']) && isset($office_time['close'])){
                $office_time = array($office_time);
            }
        }

        $arr['time_gap'] = $tmp_gap;
        $tempTime = array();
        foreach ($office_time as $office_time_val) {
            // 如果设置的营业时间为0点到0点则默认是24小时营业
            $office_time_val['open'] = str_replace("：" ,':' ,$office_time_val['open']);
            $office_time_val['close'] = str_replace("：" ,':' ,$office_time_val['close']);
            if ((count($office_time_val) < 1)|| (($office_time_val['open'] == '00:00') && ($office_time_val['close']=='00:00'))) {
                $office_time_val['open'] = '00:00';
                $office_time_val['close'] = '24:00';
            } else {
                foreach ($office_time_val as $i => $time) {
                    $time['open'] = str_replace("：" ,':' ,$time['open']);
                    $time['close'] = str_replace("：" ,':' ,$time['close']);
                    if ($time['open'] == '00:00' && $time['close'] == '00:00') {
                        unset($office_time_val[$i]);
                    }
                }
            }
            $startTime = strtotime(date('Y-m-d') . ' ' . $office_time_val['open']);
            $endTime = strtotime(date('Y-m-d') . ' ' . $office_time_val['close']);
            for ($time = $startTime; $time < $endTime; $time = $time + $gap) {
                $tempKey = date('H:i', $time) . '-' . date('H:i', $time + $gap);
                $tempTime[$tempKey]['time'] = $tempKey;
                $tempTime[$tempKey]['start'] = date('H:i', $time);
                $tempTime[$tempKey]['end'] = date('H:i', $time + $gap);
                $tempTime[$tempKey]['order'] = 'no';
                if ((date('H:i') < date('H:i', $time - $beforeTime))) {
                    $tempTime[$tempKey]['order'] = 'yes';
                }
            }
        }

        $startTimeAppoint = $now_appoint['start_time'] > strtotime('now') ? $now_appoint['start_time'] : strtotime('now');

        if($tmp_gap > 0){
            $endTimeAppoint = $now_appoint['end_time'] > strtotime('+3 day') ? strtotime('+3 day') : $now_appoint['end_time'];
        }else{
            $endTimeAppoint = $now_appoint['end_time'] > strtotime('+30 day') ? strtotime('+30 day') : $now_appoint['end_time'];
        }


        $dateArray[date('Y-m-d', $startTimeAppoint)] = date('Y-m-d', $startTimeAppoint);
        $dateArray[date('Y-m-d', $endTimeAppoint)] = date('Y-m-d', $endTimeAppoint);
        for ($date = $startTimeAppoint; $date < $endTimeAppoint; $date = $date + 86400) {
            $dateArray[date('Y-m-d', $date)] = date('Y-m-d', $date);
        }
        ksort($dateArray);

        if($tmp_gap > 0){
            foreach ($dateArray as $i => $date) {
                $timeOrder[$date] = $tempTime;
            }
            ksort($timeOrder);


            foreach ($timeOrder as $i => $tem) {
                foreach ($tem as $key => $temval){
                    if ($temval['end'] == '00:00') {
                        $temval['end'] = '23:59';
                    }
                    if (strtotime($i . ' ' . $temval['end']) < strtotime('now') + $beforeTime && ($temval['order'] == 'yes')) {
                        $timeOrder[$i][$key]['order'] = 'no';
                    } elseif (strtotime($i . ' ' . $temval['end']) > strtotime('now') + $beforeTime + $gap && strtotime($i . ' ' . $temval['start']) > strtotime('now') + $beforeTime && ($temval['order'] == 'no')) {
                        $timeOrder[$i][$key]['order'] = 'yes';
                    }
                }
            }

            // 查询可预约时间点
            if ($now_appoint['is_store']) {
                $appoint_num = $database_appoint_order->get_worker_appoint_num($now_appoint['appoint_id'], $merchant_workers_id);
            } else {
                $appoint_num = $database_appoint_order->get_appoint_num($now_appoint['appoint_id']);
            }

            if (count($appoint_num) > 0) {
                foreach ($appoint_num as $val) {
                    $key = date('Y-m-d', strtotime($val['appoint_date']));
//                    if ($timeOrder[$key][$val['appoint_time']]['order'] != 'no') {
//                        if (isset($timeOrder[$key]) && (1 == $val['appointNum'])) {
//                            $timeOrder[$key][$val['appoint_time']]['order'] = 'all';
//                        }
//
//                    }
                    if (isset($timeOrder[$key][$val['appoint_time'].'-'.date('H:i',strtotime($val['appoint_time'])+$gap)]['order'])&&$timeOrder[$key][$val['appoint_time'].'-'.date('H:i',strtotime($val['appoint_time'])+$gap)]['order'] != 'no') {
                        $timeOrder[$key][$val['appoint_time'].'-'.date('H:i',strtotime($val['appoint_time'])+$gap)]['order'] ='all';
                    }
                }
            }

            $arr['timeOrder'] = $timeOrder;
        }else{

            $arr['timeOrder'] = $dateArray;
        }

        // 自定义表单项
        $cuefield = [];
        if ($category) {
            $cuefield = unserialize($category['cue_field']);
            $site_url_appoint_icon = $this->config['site_url'] . '/static/appoint_new/icon_img/';
            foreach ($cuefield as &$val) {
                $val['icon_img'] = $site_url_appoint_icon . $cuefield_icon[$val['type']] . '.png?t=01';
                $sort[] = $val['sort'];
            }
            array_multisort($sort, SORT_DESC, $cuefield);
        }

        $arr['formData'] = $cuefield ? $cuefield : [];
        if (isset($_POST['merchantWorkerId']) && !empty($merchant_workers_id)) {
            $_where['merchant_worker_id'] = $merchant_workers_id;
            $default_workers_info = $database_merchant_workers->where($_where)->find();
            $default_workers_info['avatar_path'] = str_replace(',','/',$default_workers_info['avatar_path']);
            $default_workers_info['desc'] = htmlspecialchars_decode($default_workers_info['desc']);
            $reward_order = M('Reward_order');
            $uid = $this->_uid;
            $order = $reward_order->field('order_id')->where(array('status' => 1, 'reward_id' => $merchant_workers_id, 'type' => 3, 'uid' => $uid))->find();
            if ($order) $default_workers_info['is_reward'] = 1;

            // 获取服务次数
            $supply_where['service_status'] = array('gt', 0);
            $supply_where['merchant_worker_id'] = $merchant_workers_id;
            $default_workers_info['finish_count'] = $database_appoint_order->where($supply_where)->count();
            if (!$default_workers_info['finish_count'] && $default_workers_info['appoint_num']>0) {
                $default_workers_info['finish_count'] = $default_workers_info['appoint_num'];
            }

            $arr['default_workers_info'] = $default_workers_info;
            $arr['default_store_id'] = $default_workers_info['merchant_store_id'];
        }

        $phone = D('User')->where(array('uid' => $this->_uid))->getField('phone');
        if ($this->user_session['phone'] || $phone) {
            if (!$this->user_session['phone']) {
                $this->user_session['phone'] = $phone;
            }
            $arr['pigcms_phone'] = substr($this->user_session['phone'], 0, 3) . '****' . substr($this->user_session['phone'], 7);
        } else {
            $arr['pigcms_phone'] = '您需要绑定手机号码';
        }
        $address_id = isset($_POST['adress_id']) ? intval($_POST['adress_id']) : cookie('userLocationId');
        $user_adress = D('User_adress')->get_one_adress($this->_uid, intval($address_id));

        $arr['user_adress'] = $user_adress;

        $now_appoint_arr['all_pic'] = $now_appoint['all_pic'];
        $now_appoint_arr['appoint_name'] = $now_appoint['appoint_name'];
        $now_appoint_arr['is_appoint_price'] = $now_appoint['is_appoint_price'];
        $now_appoint_arr['merchant_workers_id'] = $merchant_workers_id ? $merchant_workers_id : 0;
        $now_appoint_arr['appoint_worker_label'] = '选择预约的'.(C('config.appoint_worker_name')?:'技师');
        //积分展示
        $now_appoint_arr['score_text'] = '';
        if(C('customization.goods_show_score') && $scorePrice = floatval($now_appoint_arr['order_price'])){
            $scorePercent = D('Percent_rate')->scorePercent('appoint', $now_appoint['appoint_id'], $now_appoint['mer_id']);
            $score = bcmul($scorePrice, $scorePercent,2);
            if(C('config.score_round_two')){
                $score = sprintf('%.2f',floatval($score));
            }else{
                $score = round($score);
            }
            floatval($score) > 0 && $now_appoint_arr['score_text'] = C('config.score_name').'+'.$score;
        }
        $arr['now_appoint'] = $now_appoint_arr;
        $this->returnCode(0,$arr);
    }


    public function ajaxWorker(){
        $database_merchant_workers = D('Merchant_workers');
        $database_merchant_workers_appoint = D('Merchant_workers_appoint');
        $database_appoint_supply = D('Appoint_supply');

        $merchant_store_id = I('merchant_store_id');
        $appoint_id = I('appoint_id');
        $order_from = I('order_from'); //fromWorkerOrder-技师代下单

        $where['wa.merchant_store_id'] = $merchant_store_id;
        $where['wa.appoint_id'] = $appoint_id;
        $where['a.is_store'] = 1;
        if($order_from=='fromWorkerOrder'){
            $where['wa.merchant_worker_id'] = $this->_uid;
        }
        $merchant_workers_appoint_list = $database_merchant_workers_appoint
            ->join('as wa LEFT JOIN '.C('DB_PREFIX').'appoint as a ON a.appoint_id=wa.appoint_id')
            ->where($where)
            ->getField('wa.id,wa.merchant_worker_id');

        if ($merchant_workers_appoint_list) {
            $Map['merchant_worker_id'] = array('in', $merchant_workers_appoint_list);
            $Map['status'] = 1;
            $worker_list = $database_merchant_workers->where($Map)->select();
            $reward_order = M('Reward_order');
            $uid = $this->_uid;
            $supply_where['status'] = 3;
            $supply_where['store_id'] = $merchant_store_id;
            foreach($worker_list as &$worker){
                $worker['avatar_path'] = file_domain() . '/upload/appoint/' .str_replace(',','/',$worker['avatar_path']);
                //$worker['desc'] = htmlspecialchars_decode(strip_tags(html_entity_decode($worker['desc'])));
                $worker['desc'] = strip_tags(htmlspecialchars_decode(html_entity_decode($worker['desc'])));
                $worker['is_reward'] = 0;
                $worker['info'] = $worker['info'] ?: '';
                if($order_from!='fromWorkerOrder') {
                    $order = $reward_order->field('order_id')->where(array('status' => 1, 'reward_id' => $worker['merchant_worker_id'], 'type' => 3, 'uid' => $uid))->find();
                    if ($order) $worker['is_reward'] = 1;
                }
                // 获取服务次数
                $supply_where['worker_id'] = $worker['merchant_worker_id'];
                $worker['finish_count'] = $database_appoint_supply->where($supply_where)->count();
            }

            $arr['worker_list'] = $worker_list;
            $arr['appoint_worker_name'] = C('config.appoint_worker_name');
            $this->returnCode(0,$arr);
        } else {
            $arr['worker_list'] = array();
            $this->returnCode(0,$arr);
        }

    }

    public function ajaxWorkerTime(){
        $database_merchant_workers = D('Merchant_workers');
        $worker_id = I('worker_id');

        $appoint_id = $_POST['appoint_id'] + 0;
        $now_group = D('Appoint')->get_appoint_by_appointId($appoint_id, 'hits-setInc');
        // 预约开始时间 结束时间
       
        $office_time = unserialize($now_group['office_time']);
        if(!empty($worker_id)){
            $workersInfo = $database_merchant_workers
                ->where([
                    'merchant_worker_id' => $worker_id,
                    'status' => 1
                ])
                ->find();
            if(empty($workersInfo)){
                $this->returnCodeError(L_('当前工作人员不存在！'));
            }
            $workersTimes = unserialize($workersInfo['office_time']);
            if(!empty($workersTimes) ){
                $office_time = $workersTimes;
                $now_group['time_gap'] = $workersInfo['time_gap'];
            }
        }
        if(isset($office_time['open']) && isset($office_time['close'])){
            $office_time = array($office_time);
        }

        $tempTime = array();
        foreach ($office_time as $office_time_val) {
            // 如果设置的营业时间为0点到0点则默认是24小时营业
            $office_time_val['open'] = str_replace("：" ,':' ,$office_time_val['open']);
            $office_time_val['close'] = str_replace("：" ,':' ,$office_time_val['close']);
            if ((count($office_time_val) < 1)|| (($office_time_val['open'] == '00:00') && ($office_time_val['close']=='00:00'))) {
                $office_time_val['open'] = '00:00';
                $office_time_val['close'] = '24:00';
            } else {
                foreach ($office_time_val as $i => $time) {
                    $time['open'] = str_replace("：" ,':' ,$time['open']);
                    $time['close'] = str_replace("：" ,':' ,$time['close']);
                    if ($time['open'] == '00:00' && $time['close'] == '00:00') {
                        unset($office_time_val[$i]);
                    }
                }
            }
            // 发起预约时候的起始时间 还有提前多长时间可预约
            $tmp_gap = $now_group['time_gap'];
            $beforeTime = $now_group['before_time'] > 0 ? ($now_group['before_time']) * 3600 : 0;
            $gap = $tmp_gap > 0 ? $tmp_gap * 60 : 1800;

            $startTime = strtotime(date('Y-m-d') . ' ' . $office_time_val['open']);
            $endTime = strtotime(date('Y-m-d') . ' ' . $office_time_val['close']);
            
            if(empty($now_group['time_gap'])){
                $tempKey = date('H:i', $startTime) . '-' . date('H:i', $endTime);
                $tempTime[$tempKey]['time'] = $tempKey;
                $tempTime[$tempKey]['start'] = date('H:i', $startTime);
                $tempTime[$tempKey]['end'] = date('H:i', $endTime);
                $tempTime[$tempKey]['order'] = 'yes';
            }else{
                for ($time = $startTime; $time < $endTime; $time = $time + $gap) {
                    $tempKey = date('H:i', $time) . '-' . date('H:i', $time + $gap);
                    $tempTime[$tempKey]['time'] = $tempKey;
                    $tempTime[$tempKey]['start'] = date('H:i', $time);
                    $tempTime[$tempKey]['end'] = date('H:i', $time + $gap);
                    $tempTime[$tempKey]['order'] = 'no';
                    if ((date('H:i') < date('H:i', $time - $beforeTime))) {
                        $tempTime[$tempKey]['order'] = 'yes';
                    }
                }  
            }
        }

        $startTimeAppoint = $now_group['start_time'] > strtotime('now') ? $now_group['start_time'] : strtotime('now');
        if($tmp_gap >= 0){
            $endTimeAppoint = $now_group['end_time'] > strtotime('+3 day') ? strtotime('+3 day') : $now_group['end_time'];
        }else{
            $endTimeAppoint = $now_group['end_time'] > strtotime('+30 day') ? strtotime('+30 day') : $now_group['end_time'];
        }

        $dateArray[date('Y-m-d', $startTimeAppoint)] = date('Y-m-d', $startTimeAppoint);
        $dateArray[date('Y-m-d', $endTimeAppoint)] = date('Y-m-d', $endTimeAppoint);
        for ($date = $startTimeAppoint; $date < $endTimeAppoint; $date = $date + 86400) {
            $dateArray[date('Y-m-d', $date)] = date('Y-m-d', $date);
        }
        ksort($dateArray);

        // if($tmp_gap >= 0){
        foreach ($dateArray as $i => $date) {
            $timeOrder[$date] = $tempTime;
        }
        ksort($timeOrder);
        $database_appoint_order = D('Appoint_order');
  
        foreach ($timeOrder as $i => $tem) {

            // 验证时间段是否已预约过
            $cindition_a['service_status'] =  0;
            $cindition_a['is_del'] =  0;
            $cindition_a['merchant_worker_id'] =  $worker_id;
            $cindition_a['appoint_id'] = $appoint_id;
            $cindition_a['appoint_date'] = $i;
            $cindition_a['paid'] =  array('not in',[2,3]);
            $appoint_times = $database_appoint_order->where($cindition_a)->getField('appoint_time', true);
            
            foreach ($tem as $key => $temval){
                if ($temval['end'] == '00:00') {
                    $temval['end'] = '23:59';
                }
                if (strtotime($i . ' ' . $temval['end']) < strtotime('now') + $beforeTime && ($temval['order'] == 'yes')) {
                    $timeOrder[$i][$key]['order'] = 'no';
                } elseif (strtotime($i . ' ' . $temval['end']) > strtotime('now') + $beforeTime + $gap && strtotime($i . ' ' . $temval['start']) > strtotime('now') + $beforeTime && ($temval['order'] == 'no')) {
                    $timeOrder[$i][$key]['order'] = 'yes';
                }
                if(in_array($temval['start'], $appoint_times)){
                    $timeOrder[$i][$key]['order'] = 'no';
                }
            }
        }

        // 查询可预约时间点
        $appoint_num = D('Appoint_order')->get_worker_appoint_num($now_group['appoint_id'], $worker_id);

        if (count($appoint_num) > 0) {
            foreach ($appoint_num as $val) {
                $key = date('Y-m-d', strtotime($val['appoint_date']));
                if(isset($timeOrder[$key])) {
                    foreach ($timeOrder[$key] as $tk => &$t) {
                        if ($t['order'] != 'no' && $t['start'] == $val['appoint_time']) {

                            $t['order'] = 'all';
                        }
                    }
                }
            }
        }
        $arr['tmp_gap'] = $tmp_gap;
        $arr['timeOrder'] = $timeOrder;
        $this->returnCode(0,$arr);
    }

    // 订单提交保存
    public function order_save() {
        $database_appoint_store = D('Appoint_store');
        $database_appoint = D('Appoint');
        $database_appoint_order = D('Appoint_order');
        $database_appoint_category = D('Appoint_category');

        $merchant_workers_id = I('merchantWorkerId') ?: I('worker_id');
        $appoint_id = I('appoint_id');
        $order_from = I('order_from'); //fromWorkerOrder-技师代下单
        $user_phone = I('user_phone'); //技师代下单用户手机号
        if (empty($appoint_id)) {
            $this->returnCodeError('当前服务不存在！');
        }
        if (empty($this->_uid)) {
            $this->returnCode('20044010');
        }
        $uid = $this->_uid;
        
        //技师代下单，查询用户是否存在，不存在注册新用户后下单
        if($order_from=='fromWorkerOrder'){
            if(!preg_match('/^1[3-9]{1}[0-9]{9}$/',$user_phone)) {
                $this->returnCodeError('客户手机号格式错误！');
            }
            if($merchant_workers_id!=$this->_uid){
                $this->returnCodeError('无法下其他技师的服务！');
            }
            $date_user['phone'] = $user_phone;
            $date_user['client'] = remove_xss($_POST['client']);
            $date_user['source'] ='sms';
            $date_user['spread_code'] = strval($_POST['spread_code']);
            $date_user['nickname'] = substr_replace($user_phone, '****', 3, 4);
            $reg_user  = D("User")->autoreg($date_user,false);
            if($reg_user['error_code']){
                $this->returnCodeError($reg_user['msg']);
            }
            $uid = $reg_user['uid'];
        }

        $now_appoint = $database_appoint->get_appoint_by_appointId($appoint_id, 'hits-setInc');
        if (empty($now_appoint)) {
            $this->returnCodeError('当前预约项不存在！');
        }
        if ($now_appoint['start_time'] > $_SERVER['REQUEST_TIME']) {
            $this->returnCodeError('此单还未开始！');
        }
        if (1==$now_appoint['appoint_type'] && intval($_POST['adress_id'])>0) {
            if (empty($_POST['custom_field'])) {
                $_POST['custom_field'] = [];
            }
            if($order_from=='fromWorkerOrder'){
                $address_info = M('Merchant_workers_address')->where(['adress_id' => $_POST['adress_id']])->find();
            }else{
                $address_info = M('User_adress')->where(['adress_id' => $_POST['adress_id']])->find();
            }
            
            if ($address_info) {
                $province_txt = D('Area')->get_area_by_areaId($address_info['province'],false);
                $province_txt = $province_txt ? $province_txt['area_name'] : '';
                $city_txt = D('Area')->get_area_by_areaId($address_info['city'],false);
                $city_txt = $city_txt ? $city_txt['area_name'] : '';
                $area_txt = D('Area')->get_area_by_areaId($address_info['area'],false);
                $area_txt = $area_txt ? $area_txt['area_name'] : '';
                $address_arr = [];
                $address_arr[] = $province_txt;
                $address_arr[] = $city_txt;
                $address_arr[] = $area_txt;
                $address_arr[] = $address_info['adress'];
                $address_arr[] = $address_info['detail'];
                $address = implode(' ', $address_arr);
                $custom_field = [
                    "name" => "服务位置",
                    "type" => "2",
                    "long" => $address_info['longitude'] ? $address_info['longitude'] : '',
                    "lat" => $address_info['latitude'] ? $address_info['latitude'] : '',
                    "value" => $address,
                    "address" => $address,
                    'phone' => $address_info['phone'],
                    'showName' => $address_info['name'],
                    'province' => $address_info['province'],
                    'city' => $address_info['city'],
                    'area' => $address_info['area'],
                ];
                array_unshift($_POST['custom_field'], $custom_field);
            }
        }
        // 自定义表单项
        $category = $database_appoint_category->get_category_by_id($now_appoint['cat_id']);
        $appoint_category = $category;
        if (empty($category['cue_field'])) {
            $appoint_category = $database_appoint_category->get_category_by_id($category['cat_fid']);
        }
        $now_time = time();
        $order_detail_qrcode = C('config.site_url').'/index.php?c=Recognition&a=get_own_qrcode&qrCon='.C('config.site_url').'/packapp/plat/pages/appoint/goods/submitDetail?orderid=';
        if ($appoint_category['is_autotrophic']) {
                // 是否平台自营 0，商家入驻 1，平台自营 2，第三方入驻   非商家入驻单独处理
                $data['type'] = 1;
                $data['cat_id'] = $now_appoint['cat_id'];
                $data['cat_fid'] = $now_appoint['cat_fid'];
                $data['uid'] = $uid;
                $data['appoint_id'] = $now_appoint['appoint_id'];
                $data['appoint_name'] = $now_appoint['appoint_name'];
                $data['appoint_pic_content'] = $now_appoint['appoint_pic_content'];
                if (isset($_POST['custom_field']) && $_POST['custom_field']) {
                    $custom_field = $_POST['custom_field'];
                    foreach ($custom_field as $items) {
                        if ($items['iswrite']==1 && !$items['value']) {
                            $this->returnCodeError("{$items['name']}为必填项");
                            break;
                        }
                    }
                    $data['cue_field'] = serialize($_POST['custom_field']);
                }
                $appoint_where = array(
                    'cat_id' => $now_appoint['cat_id'],
                    'start_time' => array('elt', $now_time),
                    'end_time' => array('gt', $now_time),
                    'appoint_status' => 0,
                    'check_status' => 1,
                );

                $appoint_info = $database_appoint->field('appoint_id, mer_id, is_store')->where($appoint_where)->select();
                if ($appoint_info && 1==count($appoint_info)) {
                    $appoint_info = reset($appoint_info);
                    $data['mer_id'] = $appoint_info['mer_id'];
                    // 查询下关联店铺 取默认第一个
                    if ($appoint_info['is_store']!=1) {
                        $store_where = array(
                            'appoint_id' => $appoint_info['appoint_id']
                        );
                        $store = $database_appoint_store->where($store_where)->order('store_sort DESC,store_id DESC')->find();
                        if ($store>0) {
                            $data['store_id'] = $store['store_id'];
                            // 查询下店铺技师关联技师
                            $d_merchant_workers_appoint = M('Merchant_workers_appoint');
                            $work = $d_merchant_workers_appoint->where(array('merchant_store_id' => $store['store_id']))->order('id ASC')->find();
                            if ($work) {
                                $data['merchant_worker_id'] = $work['merchant_worker_id'];
                            }
                        }
                    }
                }
                if($order_from=='fromWorkerOrder'){
                    $data['place_order_worker'] = $this->_uid;
                }
                $result = $database_appoint_order->platform_save_post_form($data);
                if($result['status']){
                    if($this->is_weixin_browser){
                        $from_type = 0;
                    }
                    elseif($this->is_wxapp_browser){
                        $from_type = 3;
                    }
                    elseif($this->is_app_browser && strpos(strtolower($_SERVER['HTTP_USER_AGENT']),'android') !== false){
                        $from_type = 2;
                    }
                    elseif($this->is_app_browser){
                        $from_type = 1;
                    }
                    else{
                        $from_type = 0;
                    }
                    D('User_behavior_appoint')->add_record($data['cat_id'],0,$uid,$appoint_category['cat_name'],1,time(),$from_type,$result['order_id']);
                }

				$href = $this->config['site_url'] . D('House_village')->base_url . 'pages/appoint/goods/submitDetail?orderid=' . $result['order_id'].'&appoint_id=' . $appoint_info['appoint_id'];
                $order_detail_qrcode .= $result['order_id'];
                $hrefArr = ['href' => $href, 'order_id' => $result['order_id'], 'order_detail_qrcode' => $order_detail_qrcode];

                $this->returnCode(0,$hrefArr);
            } else {
	        if ($now_appoint['end_time'] < $_SERVER['REQUEST_TIME']) {
		        $this->returnCodeError('此单已经结束！');
	        }
	        $database_appoint_product = D('Appoint_product');
	        $paymentStatus = 0;
	        if ($now_appoint['payment_status'] && floatval($now_appoint['payment_money'])) {
		        $paymentStatus = 1;
	        }
	        $noServiceAppointOrder = D('Appoint_order')->where([
		        'appoint_id' => $appoint_id,
		        'uid' => $uid,
				'is_del' => 0,
		        'service_status' => 0,
		        'is_apply_refund' => 0,
		        'paid' => ['in', [0, 1]],
		        'order_time' => ['between',[time()-24*3600, time()]]
	        ])->find();
			if($noServiceAppointOrder) {
				$this->returnCodeError('您尚有未服务订单，不可多次下单！');
			}
            // 产品列表
            if ($_POST['product_id']>0) {
                $paymentStatus = 0;
                $defaultAppointProduct = D('Appoint_product')->get_product_info($_POST['product_id']);
               
                if(empty($defaultAppointProduct['detail'])){
                    $this->returnCodeError('当前规格不存在！');
                }
                $defaultAppointProduct = $defaultAppointProduct['detail'];
                if(floatval($defaultAppointProduct['payment_price'])){
                    $paymentStatus = 1;
                }
            } else {
                $defaultAppointProduct = array();
                // 产品列表
                $appointProduct = $database_appoint_product->get_productlist_by_appointId($appoint_id);
                if ($appointProduct) {
                    $this->returnCodeError('请选择规格！');
                }
            }

            $now_appoint_arr = [];
            $now_appoint_arr['appoint_type'] = $now_appoint['appoint_type'];
            // 如果有默认选中的规格
            $now_appoint_arr = [];
            if ($defaultAppointProduct) {
                // 预约规格名称 存在赋值 不存在为空
                $now_appoint_arr['product_name'] = $defaultAppointProduct['name']?? '';
                // 预约规格定价 如果存在就有，不存在即-1
                $now_appoint_arr['product_payment_price'] = $defaultAppointProduct['payment_price']?? -1;
                // 预约规格全价 如果存在就有，不存在即-1
                $now_appoint_arr['product_price'] = $defaultAppointProduct['price']?? -1;
            } else {
                $now_appoint_arr['product_name'] = '';
                $now_appoint_arr['product_payment_price'] = -1;
                $now_appoint_arr['product_price'] = -1;
            }
            // 显示全价
            $now_appoint_arr['total_price'] = floatval($now_appoint_arr['product_price'])>= 0 ? $now_appoint_arr['product_price'] : $now_appoint['appoint_price'];
            if ($now_appoint['payment_status']==1) {
                // 显示应支付金额
                $now_appoint_arr['pay_price'] = floatval($now_appoint_arr['product_payment_price'])>= 0 ? $now_appoint_arr['product_payment_price'] : $now_appoint['payment_money'];
            } else {
                // 显示应支付金额
                $now_appoint_arr['pay_price'] = floatval($now_appoint_arr['product_price'])>= 0 ? $now_appoint_arr['product_price'] : $now_appoint['appoint_price'];
            }
            $now_appoint_arr['payment_status'] = $now_appoint['payment_status'];

            $now_appoint['order_price'] = $now_appoint_arr['pay_price'];

            //商家优惠券
            if ($this->DEVICE_ID == 'wxapp') {
                $platform = 'wxapp';
            }
            elseif($this->is_weixin_browser){
                $platform = 'weixin';
            }
            elseif($this->is_app_browser){
                $platform = 'app';
            }
            else{
                $platform = 'wap';
            }
            $order_type = 'appoint_new';

            //        $card_info = D('Card_new')->get_card_by_uid_and_mer_id($uid, $now_appoint['mer_id']);
            // 会员卡默认为空  暂时不进行处理
            $card_info = [];
            if (empty($card_info)||empty($card_info['discount']) || $card_info['discount'] == '0.00') {
                $card_info['discount'] = 10;
            }
            // 先折后减
            $now_appoint_arr['pay_price']  = ($now_appoint_arr['pay_price']) * $card_info['discount'] / 10;
            $now_appoint_arr['card_info'] = $card_info;

            $service_date = trim($_POST['service_date']);
            $service_time = trim($_POST['service_time']);
            if ($service_date==-1 || !$service_date) {
                $this->returnCodeError('请选择预约时段！');
            }
            if ($service_time==-1 || !$service_time) {
                $this->returnCodeError('请选择预约时段！');
            }

            $custom_field = $_POST['custom_field'];
            if (!empty($custom_field)) {
                foreach ($custom_field as $items) {
                    if ($items['iswrite']==1 && !$items['value']) {
                        $this->returnCodeError("{$items['name']}为必填项");
                        break;
                    }
                }
            }

            $now_appoint['cue_field'] = serialize($_POST['custom_field']);
            $now_appoint['appoint_date'] = $service_date;
            $now_appoint['appoint_time'] = $service_time;
            $now_appoint['store_id'] = !empty($_POST['store_id']) ? $_POST['store_id'] + 0 : 0;
            $now_appoint['product_id'] = $_POST['product_id'] ? $_POST['product_id'] + 0 : 0;

            if (C('config.open_single_spread')==1) {
                // 处理分销人
                if (isset($_POST['openid']) && $_POST['openid']) {
                    $distributor_user = D('User')->get_user($_POST['openid'],'openid');
                    // 验证是否有分销权限
                    $distributor = M('Distributor')->where(array('uid'=>$distributor_user['uid'],'status'=>1))->find();
                    if ($distributor) {
                        $now_appoint['distributor_uid'] = intval($distributor_user['uid']);
                    }
                }elseif (isset($_POST['share_uid']) && $_POST['share_uid']) {
                    // 验证是否有分销权限
                    $distributor = M('Distributor')->where(array('uid'=>$_POST['share_uid'],'status'=>1))->find();
                    if ($distributor) {
                        $now_appoint['distributor_uid'] = intval($_POST['share_uid']);
                    }
                }
            }
            $phone = D('User')->where(array('uid' => $uid))->getField('phone');
            if (empty($this->user_session['phone']) && !$phone) {
                $arr = array('url' =>U('My/bind_user',array('referer'=>urlencode(U('Appoint/order',$_GET)))));
                $array = array(
                    'errorCode'=>'10070013',
                    'errorMsg'=>'您需要绑定手机号码！',
                    'result'=>$arr
                );
                echo json_encode($array);
                exit();
            }
            if(!empty($now_appoint['is_store']) && empty($now_appoint['store_id'])){
                $this->returnCodeError('店铺不能为空！');
            }
            if (1==$now_appoint['payment_status']) {
                $now_appoint['payment_money'] = $now_appoint_arr['pay_price'];
            } else {
                $now_appoint['payment_money'] = 0;
            }
            if(empty($now_appoint['payment_money']) && empty($now_appoint['product_payment_price'])){
                $now_appoint['paid'] = 1;
				if($defaultAppointProduct) {
					if($defaultAppointProduct['price']) {
						$now_appoint['paid'] = 0;//一口价要求必须付钱
					}
				}else if($now_appoint['appoint_price']){//一口价要求必须付钱
					$now_appoint['paid'] = 0;
				}
            }
            $now_appoint['content'] = trim($_POST['content']) ? trim($_POST['content']) : '';
            if($order_from=='fromWorkerOrder'){
                $now_appoint['place_order_worker'] = $this->_uid;
            }
            $result = $database_appoint_order->save_post_form($now_appoint, $uid, 0, $merchant_workers_id);
            if ($result['error'] == 1) {
                $this->returnCodeError($result['msg']);
            }
            $category = $database_appoint_category->get_category_by_id($now_appoint['cat_id']);
            if($this->is_weixin_browser){
                $from_type = 0;
            }
            elseif($this->is_wxapp_browser){
                $from_type = 3;
            }
            elseif($this->is_app_browser && strpos(strtolower($_SERVER['HTTP_USER_AGENT']),'android') !== false){
                $from_type = 2;
            }
            elseif($this->is_app_browser){
                $from_type = 1;
            }
            else{
                $from_type = 0;
            }
            D('User_behavior_appoint')->add_record($now_appoint['cat_id'],$appoint_id,$uid,$category['cat_name'],1,time(),$from_type,$result['order_id']);
            $now_order = $database_appoint_order->where(array('order_id' => $result['order_id']))->find();
	        $recordContent['content'] = [[
		        'title' => '下单时间',
		        'value' => date('Y-m-d H:i:s'),
		        'type' => 'text'
	        ]];
	        D('Appoint_order_records')->addOrderRecord($now_order['mer_id'], $now_order['order_id'], '用户下单', $recordContent);

            // 验证时间段是否已预约过
            $cindition_a['service_status'] =  0;
            $cindition_a['is_del'] =  0;
            $cindition_a['merchant_worker_id'] =  $now_order['merchant_worker_id'];
            $cindition_a['appoint_id'] =  $now_order['appoint_id'];
            $cindition_a['appoint_date'] =  $now_appoint['appoint_date'];
            $cindition_a['appoint_time'] =  $now_appoint['appoint_time'];
            $cindition_a['order_id'] =  array('neq',$result['order_id']);
            $cindition_a['paid'] =  array('not in',[2,3]);
            if(!empty($now_order['merchant_worker_id'])){
                $exit_appoint = $database_appoint_order->where($cindition_a)->find();
                if ($exit_appoint) {
                    $this->returnCodeError('该时间段已有预约，请选择其他时间段！');
                } 
            }
            $order_detail_qrcode .= $result['order_id'];
            // 如果需要定金
            if ($paymentStatus) {
                $href = $this->config['site_url'] . D('House_village')->base_url . 'pages/shop_new/confirmOrder/pay?order_id=' . $result['order_id'] . '&type=appoint';
                $hrefArr = ['href' => $href, 'order_id' => $result['order_id'], 'page_type' => 'pay', 'order_detail_qrcode' => $order_detail_qrcode];
            } else {
                $database_appoint_supply = D('Appoint_supply');
                $supply_data['appoint_id'] = $now_order['appoint_id'];
                $supply_data['mer_id'] = $now_order['mer_id'];
                $supply_data['store_id'] = $now_order['store_id'];
                $supply_data['create_time'] = time();
                $supply_data['worker_id'] = $now_order['merchant_worker_id'];
                $supply_data['start_time'] = $_SERVER['REQUEST_TIME'];
                $supply_data['paid'] = $now_order['paid'];
                if($now_order['merchant_worker_id']){//服务员工服务费
                    $supply_data['commission'] = D('Merchant_workers')->appointServiceFee($now_order['merchant_worker_id'], $now_appoint_arr['total_price']);
                    $supply_data['status'] =  2;
                }else{
                    $supply_data['status'] =  1;
                }
                $supply_data['uid'] =  $now_order['uid'];
                $supply_data['pay_type'] = $now_order['pay_type'];
                $supply_data['order_time'] = $now_order['order_time'];
                $supply_data['deliver_cash'] = floatval($now_order['product_price'] - $now_order['product_card_price'] - $now_order['product_merchant_balance'] - $now_order['product_balance_pay'] - $now_order['product_payment_money'] - $now_order['product_score_deducte'] - $now_order['product_coupon_price']);
                $supply_data['order_id'] = $now_order['order_id'];
                $database_appoint_supply->data($supply_data)->add();

                // 通知店员
                if($now_order['store_id']){
                    D('Merchant_store_staff')->sendMsgAppointOrder($now_order);
                }
				
				$href = $this->config['site_url'] . D('House_village')->base_url . 'pages/appoint/goods/submitDetail?orderid=' . $result['order_id'].'&appoint_id=' . $now_order['appoint_id'];
				
                $hrefArr = ['href' => $href, 'order_id' => $result['order_id'], 'order_detail_qrcode' => $order_detail_qrcode];
            }
            $this->returnCode(0,$hrefArr);
        }
    }
    // 生成技师打赏金钱信息
}
