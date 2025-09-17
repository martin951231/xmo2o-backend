<?php

class DeliverAction extends BaseAction
{

    protected $item = [];
    protected $deliver_supply;

    protected function _initialize()
    {
        $this->item["0"] = L_("X1的外送",['X1'=>$this->config['shop_alias_name']]);
        $this->item["1"] = L_("外卖");
        parent::_initialize();
        $ticket = I('ticket', false);
        if ($ticket) {
            $deliver = $this->get_deliver_info();
            //设置当前城市的时区
            if (!empty($deliver['city_id']) || !empty($deliver['area_id'])) {
                $area_db = M("Area");
                if (!empty($deliver['city_id'])) {
                    $city_info = $area_db->where('area_id=' . $deliver['city_id'])->field('timezone')->find();
                }
                if (!empty($deliver['area_id'])) {
                    $area_info = $area_db->where('area_id=' . $deliver['area_id'])->field('timezone')->find();
                }
                //area的时区优先于城市的时区
                if (!empty($area_info['timezone']) || !empty($city_info['timezone'])) {
                    date_default_timezone_set($area_info['timezone'] ? $area_info['timezone'] : $city_info['timezone']);
					D('')->query("SET time_zone='+" . get_timeout_num_by_name() . "'");
                }
            }
        }
    }

    public function get_device_token()
    {
        $where['device_id'] = isset($_POST['device_id']) ? $_POST['device_id'] : $this->DEVICE_ID;
        $device_token = $_POST['device_token'];
        $where['status'] = array('neq',4);
        M('Deliver_user')->where($where)->setField('device_token', $device_token);
        $this->returnCode(0);
    }

    public function config()
    {
        $config['can_register'] = true;
        $config['site_phone'] = $this->config['site_phone'];
        $config['deliver_see_detail'] = $this->config['deliver_see_detail'];
        $config['deliver_see_freight_charge'] = $this->config['deliver_see_freight_charge'];
        $config['is_packapp'] = true;
        if ($this->config['map_config'] == 'google') {
            $config['google_map_ak'] = $this->config['google_map_ak'];
        } else {
            $config['google_map_ak'] = '-1';
        }

        $appConfig = D('Appapi_app_config')->get();
        $config['deliver_android_version'] = $appConfig['deliver_android_version'];
        $config['deliver_android_vcode'] = $appConfig['deliver_android_vcode'];
        $config['deliver_android_url'] = $appConfig['deliver_android_url'];
        $config['deliver_android_vdes'] = $appConfig['deliver_android_vdes'];
		
		$config['deliver_ios_must_upgrade'] = $appConfig['deliver_ios_must_upgrade'] ? true : false;
		$config['deliver_android_must_upgrade'] = $appConfig['deliver_android_must_upgrade'] ? true : false;
		
		
        $this->returnCode('0', $config);
    }

    // 隐私政策
    public function get_privacy_policy(){
        $app_config = D('Appapi_app_config')->get();
        $arr['privacy_policy'] = htmlspecialchars_decode(htmlspecialchars_decode($app_config['deliver_privacy_policy']),ENT_QUOTES);
        $arr['privacy_policy_md5'] = md5($app_config['deliver_privacy_policy'].$this->config['register_agreement']);
        // 注册协议
        $arr['register_agreement'] = htmlspecialchars_decode(htmlspecialchars_decode($this->config['register_agreement']),ENT_QUOTES);
        $this->returnCode('0',$arr);
    }

    public function login()
    {
        $ticket = I('ticket', false);
        $client = I('client', 0);

        if ($ticket) {
            if ($this->_uid) {
                $uid = $this->_uid;
                $now_user = D('Deliver_user')->field('uid,name,pwd,openid,phone,is_notice,status,group')->where(array('uid' => $uid,'status'=>array('neq',4)))->find();
                unset($now_user['pwd']);

                if ($now_user) {
                    $data_deliver_user['last_time'] = $_SERVER['REQUEST_TIME'];
                    $data_deliver_user['client'] = $client;
                    $data_deliver_user['device_id'] = $this->DEVICE_ID;

                    D('Deliver_user')->where(array('uid' => $now_user['uid'],'status'=>array('neq',4)))->data($data_deliver_user)->save();

                    /* 登录历史记录 */
                    $data_deliver_user_login_log = array(
                        'uid' => $uid,
                        'client' => $client,
                        'device_id' => $this->DEVICE_ID,
                        'add_time' => $_SERVER['REQUEST_TIME'],
                        'add_ip' => get_client_ip(0),
                    );
                    M('Deliver_user_login_log')->data($data_deliver_user_login_log)->add();
                }

                $return = array(
                    'device_id' => $this->DEVICE_ID,
                    'ticket' => $ticket,
                    'user' => $now_user,
                );
                $this->returnCode(0, $return);
            } else {
                $this->returnCode('20000009');
            }
        }
        $phone = I('phone', false);
        $now_user = D('Deliver_user')->field('uid,name,pwd,openid,is_notice,phone,status')->where(array('phone' => $phone,'status'=>array('neq',4)))->find();
        if (empty($now_user)) {
            $this->returnCode('20020001');
        }
        if (empty($now_user['status']) || $now_user['status']!=1) {
            $this->returnCode('20020003');
        }
        $pwd = md5(trim($_POST['pwd']));
        if ($pwd != $now_user['pwd']) {
            $this->returnCode('20020002');
        }
        $ticket = ticket::create($now_user['uid'], $this->DEVICE_ID, true);
        preg_match('/versionCode=(\d+)/', $_SERVER['HTTP_USER_AGENT'], $versionCode);
        if ($versionCode[1]) {
            $data_deliver_user['app_version'] = intval($versionCode[1]);
        }
        $data_deliver_user['last_time'] = $_SERVER['REQUEST_TIME'];
        $data_deliver_user['device_id'] = $this->DEVICE_ID;
        $data_deliver_user['client'] = $client;
        unset($now_user['pwd']);
        if (D('Deliver_user')->where(array('uid' => $now_user['uid'],'status'=>array('neq',4)))->data($data_deliver_user)->save()) {
            $arr = array(
                'device_id' => $this->DEVICE_ID,
                'ticket' => $ticket['ticket'],
                'user' => $now_user,
            );

            /* 登录历史记录 */
            $data_deliver_user_login_log = array(
                'uid' => $now_user['uid'],
                'client' => $client,
                'device_id' => $this->DEVICE_ID,
                'add_time' => $_SERVER['REQUEST_TIME'],
                'add_ip' => get_client_ip(0),
            );
            M('Deliver_user_login_log')->data($data_deliver_user_login_log)->add();
            $this->returnCode(0, $arr);
        } else {
            $this->returnCode('20020006');
        }
    }

    public function logout()
    {
        M('Deliver_user')->where(array('uid' => $this->_uid))->setField('last_time', 0);
        $this->returnCode(0);
    }

    //配送列表
    public function deliver_list()
    {
        if (empty($this->_uid)) {
            $this->returnCode('20020008');
        } else {
            $now_user = D('Deliver_user')->field(true)->where(array('uid' => $this->_uid,'status'=>array('neq',4)))->find();
            if (empty($now_user)) {
                $this->returnCode('20020001');
            }
        }
        $exits_list = I('exits_list');
        if (!empty($device_deliver_list)) {
            $device_deliver_list = explode(',', $device_deliver_list);

        }
        $my_lnt = I("lng");
        if (!$my_lnt) {
            $this->returnCode('20020009');
        }
        $my_lat = I("lat");
        if (!$my_lat) {
            $this->returnCode('20020009');
        }
        $my_distance = $now_user['range'] * 1000 * 1000;
        $time = time();
        $db_array = array(
            C('DB_PREFIX') . "deliver_supply" => "d",
            C('DB_PREFIX') . "waimai_order" => "w"
        );
        $nember_6 = 6378.138 * 2;
        $lat_pi = $my_lat * pi() / 180;
        $lnt_pi = $my_lnt * pi() / 180;
        $fields = "*,ROUND({$nember_6}* ASIN(SQRT(POW(SIN(({$lat_pi}-`from_lat`*PI()/180)/2),2)+COS({$lat_pi})*COS(`from_lat`*PI()/180)*POW(SIN(({$lnt_pi}-`from_lnt`*PI()/180)/2),2)))*1000) AS distance";
        if ($now_user['store_id']) {
            $where = "`appoint_time`<$time AND `status`=1";
            $where = "`type`= 1 AND `store_id`=" . $now_user['store_id'] . " AND " . $where;
        } else {
            $where = "`appoint_time`<$time AND `status`=1 AND ROUND({$nember_6} * ASIN(SQRT(POW(SIN(({$lat_pi}-`from_lat`*PI()/180)/2),2)+COS({$lat_pi})*COS(`from_lat`*PI()/180)*POW(SIN(({$lnt_pi}-`from_lnt`*PI()/180)/2),2)))*1000) < $my_distance ";
            $where = "`type`= 0 AND " . $where;
        }

        $list = D("Deliver_supply")->field($fields)->where($where)->order("`create_time` DESC")->select();

        foreach ($list as $key => $val) {
            switch ($val['pay_type']) {
                case 'offline':
                    $list[$key]['pay_method'] = L_('线下支付');
                    break;
                default:
                    if ($val['paid']) {
                        $list[$key]['pay_method'] = L_('在线支付');
                        break;
                    } else {
                        $list[$key]['pay_method'] = L_('未支付');
                        break;
                    }
            }
            if (empty($val['supply_id'])) {
                continue;
            }
            $tmp['supply_id'] = $val['supply_id'];
            $tmp['from_site'] = empty($val['from_site']) ? '' : $val['from_site'];
            $tmp['aim_site'] = empty($val['aim_site']) ? '' : $val['aim_site'];
            $tmp['name'] = empty($val['name']) ? '' : $val['name'];
            $tmp['status'] = empty($val['status']) ? '' : $val['status'];
            $tmp['store_name'] = empty($val['store_name']) ? '' : $val['store_name'];
            $tmp['phone'] = empty($val['phone']) ? '' : $val['phone'];
            $tmp['pay_method'] = $list[$key]['pay_method'];
            $supply_id[] = $tmp['supply_id'];
            $arr[] = $tmp;
        }

        if (!empty($exits_list)) {
            $device_deliver_list = explode(',', $exits_list);

            foreach ($arr as $k => $v) {
                if (in_array($v['supply_id'], $device_deliver_list)) {
                    unset($arr[$k]);
                }
            }
            foreach ($device_deliver_list as $key => $vd) {
                if (!in_array($vd, $supply_id)) {
                    $has_pick_id [] = $vd;
                }
            }
            if (empty($has_pick_id)) {
                $return['old'] = "";
            } else {

                $return['old'] = implode(',', $has_pick_id);
            }
            $return['is_new'] = true;
        } else {
            $return['old'] = "";
            $return['is_new'] = false;
        }


        if (false === $list) {
            $this->returnCode('20020010');
        }
        if (empty($arr)) {
            $arr = array();
        }
        if ($now_user['group'] == 1 && M('Area')->where(['area_id'=>$now_user['city_id']])->getField('deliver_model')){
			$arr = array();
		}
        $return['deliver_list'] = $arr;
        $this->returnCode(0, $return);
    }


    public function grab()
    {
        if (empty($this->_uid)) {
            $this->returnCode('20020008');
        } else {
            $deliver = D('Deliver_supply');
            $supply_id = intval(I("supply_id"));
            if (!$supply_id) {
                $this->returnCode('20020011');
            }
            $supply = $deliver->find($supply_id);
            if (!$supply) {
                $this->returnCode(L_('配送订单不存在'));
            }
            if ($supply['status'] == 0) {
                $this->returnCode(L_('配送单已取消'));
            }
            $uid = $this->_uid;
            $now_user = D('Deliver_user')->field(true)->where(array('uid' => $uid,'status'=>array('neq',4)))->find();
            if (empty($now_user)) {
                $this->returnCode('20020001');
            }
            if ($now_user['group'] == 2 && ($now_user['store_id'] || $now_user['store_ids'])) {
                $storeIds = !empty($now_user['store_ids']) ? explode(',', $now_user['store_ids']) : [$now_user['store_id']];
                if (!in_array($supply['store_id'], $storeIds)) {
                    $this->returnCode(L_('非该店铺配送员禁止接单'));
                }
            }
        }

        if ($now_user['max_num'] > 0) {
            $count = $deliver->where('uid=' . $now_user['uid'] . ' AND status>0 AND status<5')->count();
            if ($count >= $now_user['max_num']) {
                $this->returnCode(1, array(), L_('您当前已有X1单没有完成配送，请配送完成再来抢单！', array('X1' => $count)));
            }
        }

        // 兼职配送员只有在系统配送员不能抢单时才能抢单
        /*if ($now_user['user_type'] == 1) {
            $isGrab = D('Deliver_supply')->checkGrab();
            if (!$isGrab) {
                $this->returnCode('20020024');
            }
        }*/

        // 开启配送员等级
        if (C('config.open_deliver_level') && $now_user['group'] == 1) {
            $now_level = D('Deliver_user')->get_user_level($now_user['score']);
            if ($now_level['order_num'] >= 0) {
                // 今日接单量
                $where = array();
                $where['uid'] = $now_user['uid'];
                $where['start_time'] = array('between', array(strtotime(date("Y-m-d")), time()));
                $todayNum = D('Deliver_supply')->where($where)->count();
                if ($todayNum >= $now_level['order_number']) {
                    // 今日接单量已达上限！
                    $this->returnCode('20020023');
                }
            }
        }

        $columns = array();
        $columns['uid'] = $uid;
        $columns['status'] = 2;
        $columns['is_fetch_order'] = 1;
        $columns['start_time'] = time();

        D()->startTrans();
        $supply = $deliver->lock(true)->where(array("supply_id" => $supply_id))->find();
        if ($supply['item'] == 3) {//帮买帮送
            $publishInfo = D('Service_user_publish')->where(array('publish_id' => $supply['order_id']))->find();
            if ($publishInfo['catgory_type'] == 2) {
                //帮我买
                $cat_field_info = D('Service_user_publish_buy')->where(array('publish_id' => $publishInfo['publish_id']))->find();
            }
            if ($publishInfo['catgory_type'] == 3) {
                //帮我送
                $cat_field_info = D('Service_user_publish_give')->where(array('publish_id' => $publishInfo['publish_id']))->find();
            }
            if ($cat_field_info) {
                $fc = $cat_field_info['basic_distance_price'] + $cat_field_info['weight_price'] + $cat_field_info['distance_price'];//基础配送费+超重费用+超距离配送
                $columns['deliver_user_fee'] = $fc - ($fc * ($now_user['take_percent'] / 100));//配送员所得费用（扣除平台抽成）
            }
        } elseif ($supply['item'] == 8) {//社区拼团
            $order_village_group = D('Village_group_order')->where(array('order_id' => $supply['order_id']))->find();
            if ($order_village_group && $order_village_group['delivery_fee'] > 0) {
                $delivery_fee = $order_village_group['delivery_fee'];
                $columns['deliver_user_fee'] = $delivery_fee - ($delivery_fee * ($now_user['take_percent'] / 100));//配送员所得费用（扣除平台抽成）
            }
        } else {
            $columns['deliver_user_fee'] = $supply['freight_charge'] - ($supply['freight_charge'] * ($now_user['take_percent'] / 100));
        }

        if ($supply['status'] != 1) {
            $this->returnCode('20020012');
        }

        $result = $deliver->where(array("supply_id" => $supply_id, 'status' => 1))->data($columns)->save();

        //记录订单配送状态流转记录
        M('Scan_order_transfer_log')->add(['uid' => $uid, 'status' => $columns['status'], 'add_time' => time(), 'supply_id' => $supply_id]);
        if (false === $result) {
            D()->rollback();
            $this->returnCode('20020012');
        }
        //获取order_id
        if (!$supply['order_id']) {
            D()->rollback();
            $this->returnCode('20020013');
        }
        //获取订单信息
        $order_id = $supply['order_id'];

        if ($supply['item'] == 1) {
            $order = D("Waimai_order")->find($order_id);
            if ($order['order_status'] != 3) {
                D()->rollback();
                $this->returnCode('20020014');
            }
            //更新订单状态
            $result = D("Waimai_order")->where(array('order_id' => $order_id))->data(array('order_status' => 8))->save();
            if (!$result) {
                D()->rollback();
                $this->returnCode('20020015');
            }
            //添加订单日志
            $log = array();
            $log['status'] = 8;
            $log['order_id'] = $order_id;
            $log['store_id'] = $order['store_id'];
            $log['uid'] = $uid;
            $log['time'] = time();
            $log['group'] = 4;
            $result = D("Waimai_order_log")->add($log);
            if (!$result) {
                $this->returnCode('20020016');
                D()->rollback();
            }
        } elseif ($supply['item'] == 0) {
            //更新订单状态
            $order = D("Meal_order")->where(array('order_id' => $order_id))->find();

            if ($order['order_status'] != 3) {
                D()->rollback();
                $this->returnCode('20020014');
            }
            //更新订单状态
            $result = D("Meal_order")->where(array('order_id' => $order_id))->data(array('order_status' => 8))->save();
            if (!$result) {
                D()->rollback();
                $this->returnCode('20020015');
            }
        } elseif ($supply['item'] == 2) {
            D()->commit();
            //更新订单状态
            $order = D("Shop_order")->where(array('order_id' => $order_id))->find();
            if ($order['order_status'] != 1) {
                $this->returnCode('20020014');
            }
            //更新订单状态
            $deliver_info = serialize(array('uid' => $now_user['uid'], 'name' => $now_user['name'], 'phone' => $now_user['phone'], 'store_id' => $now_user['store_id']));
            $result = D("Shop_order")->where(array('order_id' => $order_id))->data(array('order_status' => 2, 'deliver_info' => $deliver_info))->save();
            if (!$result) {
                $this->returnCode('20020015');
            }

            $order_type = $order['order_from'] == 1 ? 'mall' : 'shop';
            D('System_order')->save_order_info($order_type, $order_id, ['status' => 1, 'last_time' => time()]);

            // 订单详情
            $order =  D("Shop_order")->where(array('order_id' => $order_id))->find();
            // 周期购
            if($order['periodic_type']){//周期购
                $p_data = array();
                $p_data['status'] = 1;
                $p_data['update_time'] = $_SERVER['REQUEST_TIME'];
                $p_data['accept_time'] = $_SERVER['REQUEST_TIME'];
                $p_data['deliver_uid'] = $now_user['uid'];
                $p_data['deliver_type'] = 2;
                $p_data['deliver_money'] = $supply['freight_charge'];

                $p_where = array();
                //获得最近需要配送的周期
                $periodic_deliver =M('Mall_periodic_deliver')->where(array('order_id'=>$order_id,'status'=>0))->order('deliver_num asc')->find();
                $p_where['id'] = $periodic_deliver['id'];
                M('Mall_periodic_deliver')->where($p_where)->data($p_data)->save();                
            }
            D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 3, 'name' => $now_user['name'], 'phone' => $now_user['phone'],'periodic_id'=>intval($periodic_deliver['id'])));
        } elseif ($supply['item'] == 3) {
            $offer_id = D('Service_offer')->add_offer($order_id, $uid);
            $saveData = array('offer_id' => $offer_id);
            if (($supply['server_type'] == 2 || $supply['server_type'] == 3) && !$supply['appoint_time']) {
                $saveData['appoint_time'] = time() + 60 * $supply['server_time'];
            }

            $deliver->where(array('supply_id' => $supply_id))->save($saveData);
        } elseif ($supply['item'] == 8) {
            // 社区拼团
            $deliver_info = serialize(array('uid' => $now_user['uid'], 'name' => $now_user['name'], 'phone' => $now_user['phone'], 'store_id' => $now_user['store_id']));
            D('Village_group_order')->where(array('order_id' => $supply['order_id']))->data(array('order_status' => 1, 'deliver_info' => $deliver_info))->save();

            $detail_info = M('Village_group_order_detail')->where(array('order_id' => $supply['order_id']))->select();

            foreach ($detail_info as $value) {
                $param['goods_id'] = $value['goods_id'];
                $param['from_type'] = 1;
                $param['deliver_status'] = 6;
                $param['note'] = '配送接单';
                $param['name'] = $now_user['name'];

                $param['order_id'] = $value['order_id'];
                D('Village_group')->add_log($param);
            }
        }
        D()->commit();
        $this->returnCode(0);//抢单成功
    }

    public function detial()
    {
        $ticket = I('ticket', false);
        if ($ticket) {
            $info = ticket::get($ticket, $this->DEVICE_ID, true);
            $this->user_session['uid'] = $info['uid'];
        }

        if (empty($this->user_session)) {
            $this->returnCode('20020008');
        } else {
            $now_user = D('Deliver_user')->field(true)->where(array('uid' => $this->user_session['uid'],'status'=>array('neq',4)))->find();
            if (empty($now_user)) {
                $this->returnCode('20020001');
            }
        }
        $deliver = D('Deliver_supply');
        $supply_id = intval(I("supply_id"));
        if (!$supply_id) {
            $this->returnCode('20020011');
        }
        $uid = $this->user_session['uid'];

        $where = array();
        if (empty($uid)) {
            $where['uid'] = $uid;
        }
        $where['supply_id'] = $supply_id;
// 		$where['item'] = 1;
        $supply = D("Deliver_supply")->where($where)->find();


        if (!$supply) {
            $this->returnCode('20020018');
        }
        $arr['supply_id'] = $supply['supply_id'];
        $arr['from_site'] = $supply['from_site'];
        $arr['status'] = $supply['status'];
        $arr['aim_site'] = $supply['aim_site'];
        $arr['name'] = $supply['name'];
        $arr['phone'] = $supply['phone'];
        $arr['from_lat'] = empty($supply['from_lat']) ? 0 : $supply['from_lat'];
        $arr['from_lnt'] = empty($supply['from_lnt']) ? 0 : $supply['from_lnt'];
        $arr['aim_lat'] = empty($supply['aim_lat']) ? 0 : $supply['aim_lat'];
        $arr['aim_lnt'] = empty($supply['aim_lnt']) ? 0 : $supply['aim_lnt'];
        if ($supply['item'] == 1) {
            //订单信息
            $where = array();
            $where['order_id'] = $supply['order_id'];
            $order = D("Waimai_order")->where($where)->find();
            if (!$order) {
                $this->returnCode('20020014');
            }
            $pay_method = D('Config')->get_pay_method();
            $order['pay_type'] = $pay_method[$order['pay_type']]['name'];

            $arr['order_discount_price'] = $order['discount_price'];
            $arr['order_code'] = $order['code'];
            $arr['order_pay_type'] = $order['pay_type'];

            //商品信息
            $goods = D()->field(true)->table(array(C('DB_PREFIX') . 'waimai_sell_log' => 'sl', C('DB_PREFIX') . 'waimai_goods' => 'wg'))->where("sl.order_id=" . $supply['order_id'] . " AND sl.goods_id=wg.goods_id")->select();

            $arr['goods'] = $goods;

            //店铺信息
            $store = D()->field(true)->table(array(C('DB_PREFIX') . 'merchant_store' => 'ms', C('DB_PREFIX') . 'waimai_store' => 'ws'))->where("ms.store_id=" . $order['store_id'] . " AND ms.store_id=ws.store_id")->find();
            if (!$store) {
                $this->returnCode('20020019');
            }
            if ($store['total_money'] <= 0) {
                $store['send_money'] = $store['send_money'];
            } else {
                $store['send_money'] = $order['price'] > $store['total_money'] ? 0 : $store['send_money'];
            }
            $store['tools_money'] = 0;
            if ($store['tools_money_have'] == 1) {
                foreach ($goods as $v) {
                    $store['tools_money'] += $v['tools_price'] * $v['num'];
                }
            }

            $arr['store_send_money'] = empty($store['send_money']) ? 0 : $store['send_money'];
            $arr['store_name'] = $store['name'];
            $arr['store_phone'] = $store['phone'];
            $this->returnCode(0, $arr);

            //红包信息
//			$where = array();
//			$where['id'] = $order['coupon_id'];
//			$couponInfo = D("Waimai_user_coupon")->field(true)->where($where)->find();
//			$this->assign('couponInfo', $couponInfo);
//			//优惠信息
//			$discountInfo = json_decode($order['discount_detail'], true);
//			$this->assign('discountInfo', $discountInfo);

        } elseif ($supply['item'] == 0) {
            //订单信息
            $where = array();
            $where['order_id'] = $supply['order_id'];
            $order = D("Meal_order")->where($where)->find();

            if (!$order) {
                $this->returnCode('20020014');
            }
            $pay_method = D('Config')->get_pay_method();
            $order['pay_type'] = $pay_method[$order['pay_type']]['name'];
            $order['total_price'] = $order['total_price'];

            $arr['total_price'] = $order['price'] - $order['system_pay'];
            $arr['order_code'] = (string)$arr['total_price'];
            if (empty($order['pay_type'])) {
                if ($order['balance_pay'] > 0) {
                    $arr['order_pay_type'] = L_('余额支付');
                } else {
                    $arr['order_pay_type'] = L_('未支付');
                }
            } else {
                $arr['order_pay_type'] = $order['pay_type'];
            }

            $goods = unserialize($order['info']);

//			foreach ($goods as &$g) {
//				//$g['tools_money'] = 0;
//			}
            $arr['goods'] = $goods;

            //店铺信息
            $store = D()->field(true)->table(array(C('DB_PREFIX') . 'merchant_store' => 'ms', C('DB_PREFIX') . 'merchant_store_meal' => 'ml'))->where("ms.store_id=" . $order['store_id'] . " AND ms.store_id=ml.store_id")->find();
            if (!$store) {
                $this->returnCode('20020019');
            }
            if ($store['total_money'] <= 0) {
                $store['send_money'] = $store['send_money'];
            } else {
                $store['send_money'] = $order['price'] > $store['total_money'] ? 0 : $store['send_money'];
            }
            $store['tools_money'] = 0;
            if ($store['tools_money_have'] == 1) {
                foreach ($goods as $v) {
                    $store['tools_money'] += $v['tools_price'] * $v['num'];
                }
            }
            if ($store['tools_money_have']) {
                $arr['store']['tools_money'] = $store['tools_money'];
            }

            $arr['store_send_money'] = 0;
            $arr['tools_money'] = 0;
            $arr['store_name'] = $store['name'];
            $arr['store_phone'] = $store['phone'];
            $this->returnCode(0, $arr);
        } elseif ($supply['item'] == 2) {
            //订单信息
            $where = array();
            $where['order_id'] = $supply['order_id'];
            $order = D("Shop_order")->where($where)->find();
            if (!$order) {
                $this->error_tips(L_("订单信息有误"));
            }
            $order['pay_type'] = D('Pay')->get_pay_name($order['pay_type'], $order['is_mobile_pay'], $order['paid']);

            $order['discount_price'] = $order['price'];
            $arr['total_price'] = $order['total_price'];
            $arr['order_code'] = (string)$arr['total_price'];
            $goods = D('Shop_order_detail')->field(true)->where(array('order_id' => $supply['order_id']))->select();
            foreach ($goods as &$g) {
                if ($g['spec']) {
                    $g['name'] = $g['name'] . '(' . $g['spec'] . ')';
                }
                $g['tools_money'] = 0;
            }
            if (empty($order['pay_type'])) {
                if ($order['balance_pay'] > 0) {
                    $arr['order_pay_type'] = L_('余额支付');
                } else {
                    $arr['order_pay_type'] = L_('未支付');
                }
            } else {
                $arr['order_pay_type'] = $order['pay_type'];
            }

            $arr['goods'] ['id'] = $goods[0]['id'];
            $arr['goods'] ['name'] = $goods[0]['name'];
            $arr['goods'] ['price'] = $goods[0]['price'];
            $arr['goods'] ['num'] = $goods[0]['num'];
            $arr['goods'] ['iscount'] = 1;
            //店铺信息
            $store = D()->field(true)->table(array(C('DB_PREFIX') . 'merchant_store' => 'ms', C('DB_PREFIX') . 'merchant_store_shop' => 'ml'))->where("ms.store_id=" . $order['store_id'] . " AND ms.store_id=ml.store_id")->find();

            if (!$store) {
                $this->error_tips(L_("店铺信息有误"));
            }

            $store['tools_money'] = 0;
            if ($store['tools_money_have'] == 1) {
                foreach ($goods as $v) {
                    $store['tools_money'] += $v['tools_price'] * $v['num'];
                }
            }
            $arr['store_send_money'] = 0;
            $arr['tools_money'] = $store['tools_money'];
            $arr['store_name'] = $store['name'];
            $arr['store_phone'] = $store['phone'];
            $this->returnCode(0, $arr);
        }
    }

    public function map()
    {
        $ticket = I('ticket', false);
        if ($ticket) {
            $info = ticket::get($ticket, $this->DEVICE_ID, true);
            $this->user_session['uid'] = $info['uid'];
        }
        if (empty($this->user_session)) {
            $this->returnCode('20020008');
        } else {
            $now_user = D('Deliver_user')->field(true)->where(array('uid' => $this->user_session['uid'],'status'=>array('neq',4)))->find();
            if (empty($now_user)) {
                $this->returnCode('20020001');
            }
        }
        $supply_id = I("supply_id", 0, 'intval');
        if (!$supply_id) {
            $this->returnCode('20020011');
        }
        $supply = D("Deliver_supply")->where(array('supply_id' => $supply_id))->find();
        if (!$supply) {
            $this->returnCode('20020018');
        }
        $this->returnCode(0, $supply);
    }

    public function pick_list()
    {
        $ticket = I('ticket', false);
        if ($ticket) {
            $info = ticket::get($ticket, $this->DEVICE_ID, true);
            $this->user_session['uid'] = $info['uid'];
        }
        if (empty($this->user_session)) {
            $this->returnCode('20020008');
        } else {
            $now_user = D('Deliver_user')->field(true)->where(array('uid' => $this->user_session['uid'],'status'=>array('neq',4)))->find();
            if (empty($now_user)) {
                $this->returnCode('20020001');
            }
        }

        $where = array();
        $where['status'] = 2;
//     	$where['item'] = 1;
        $where['uid'] = $this->user_session['uid'];

        $list = D('Deliver_supply')->field(true)->where($where)->order("`start_time` DESC")->select();
        foreach ($list as $key => $val) {
            switch ($val['pay_type']) {
                case 'offline':
                    $list[$key]['pay_method'] = L_('线下支付');
                    break;
                default:
                    if ($val['paid']) {
                        $list[$key]['pay_method'] = L_('在线支付');
                        break;
                    } else {
                        $list[$key]['pay_method'] = L_('未支付');
                        break;
                    }
            }
            if (empty($val['supply_id'])) {
                continue;
            }
            $tmp['supply_id'] = $val['supply_id'];
            $tmp['from_site'] = empty($val['from_site']) ? '' : $val['from_site'];
            $tmp['aim_site'] = empty($val['aim_site']) ? '' : $val['aim_site'];
            $tmp['name'] = empty($val['name']) ? '' : $val['name'];
            $tmp['status'] = empty($val['status']) ? '' : $val['status'];
            $tmp['store_name'] = empty($val['store_name']) ? '' : $val['store_name'];
            $tmp['phone'] = empty($val['phone']) ? '' : $val['phone'];
            $tmp['pay_method'] = $list[$key]['pay_method'];
            $supply_id[] = $tmp['supply_id'];
            $arr[] = $tmp;
        }
        if (false === $list) {
            $this->returnCode('20020010');
        }
        $arr = empty($arr) ? array() : $arr;
        $this->returnCode(0, $arr);
    }

	public function fetch()
    {
        if (empty($this->_uid)) {
            $this->returnCode('20020008');
        } else {
            $now_user = D('Deliver_user')->field(true)->where(array('uid' => $this->_uid,'status'=>array('neq',4)))->find();
            if (empty($now_user)) {
                $this->returnCode('20020001');
            }
        }

        $supply_id = intval(I("supply_id"));
        if (!$supply_id) {
            $this->returnCode('20020011');
        }
        //获取order_id
        $supply = D('Deliver_supply')->find($supply_id);
        if (!$supply['order_id']) {
            $this->returnCode('20020013');
        }
        if ($supply['is_fetch_order'] != 0) {
            $this->returnCodeError(L_("当前订单已接单"));
        }
        $order_id = $supply['order_id'];

        D()->startTrans();
        $columns = array();
        $columns['is_fetch_order'] = 1;
        $result = D('Deliver_supply')->where(array("supply_id" => $supply_id))->data($columns)->save();
        if (false === $result) {
            $this->returnCode('20020022');
        }
		//暂时只处理快店
		if ($supply['item'] == 2) {
            D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 35, 'name' => $now_user['name'], 'phone' => $now_user['phone']));
        }
        D()->commit();
        $this->returnCode(0);
    }
	
    public function pick()
    {

        $ticket = I('ticket', false);
        if ($ticket) {
            $info = ticket::get($ticket, $this->DEVICE_ID, true);
            $uid = $info['uid'];
        }
        if (empty($uid)) {
            $this->returnCode('20020008');
        } else {
            $now_user = D('Deliver_user')->field(true)->where(array('uid' => $uid,'status'=>array('neq',4)))->find();
            if (empty($now_user)) {
                $this->returnCode('20020001');
            }
        }

        $supply_id = intval(I("supply_id"));
        if (!$supply_id) {
            $this->returnCode('20020011');
        }
        //获取order_id
        $supply = D('Deliver_supply')->find($supply_id);
        if (!$supply['order_id']) {
            $this->returnCode('20020013');
        }
        if ($supply['status'] != 2) {
            $this->returnCode(1, null, L_("当前状态不能修改成取货状态！"));
        }
        $order_id = $supply['order_id'];

        D()->startTrans();
        $columns = array();
        $columns['uid'] = $uid;
        $columns['status'] = 3;
        $columns['is_fetch_order'] = 1;
        $columns['real_fetch_good_time'] = date('Y-m-d H:i:s');
        $result = D('Deliver_supply')->where(array("supply_id" => $supply_id, 'status' => 2))->data($columns)->save();
        //记录订单配送状态流转记录
        M('Scan_order_transfer_log')->add(['uid' => $uid, 'status' => $columns['status'], 'add_time' => time(), 'supply_id' => $supply_id]);
        if (false === $result) {
            $this->returnCode('20020022');
        }
        if ($supply['item'] == 1) {
            //获取订单信息
            $order = D("Waimai_order")->find($order_id);
            if (!$order) {
                $this->returnCode('20020014');
            }

            //更新订单状态
            $result = D("Waimai_order")->where(array('order_id' => $order_id))->data(array('order_status' => 4))->save();
            if (!$result) {
                D()->rollback();
                $this->returnCode('20020015');
            }
            //添加订单日志
            $log = array();
            $log['status'] = 4;
            $log['order_id'] = $order_id;
            $log['store_id'] = $order['store_id'];
            $log['uid'] = $uid;
            $log['time'] = time();
            $log['group'] = 4;
            $result = D("Waimai_order_log")->add($log);
            if (!$result) {
                $this->returnCode('20020016');
                D()->rollback();
            }
        } elseif ($supply['item'] == 0) {
            //更新订单状态
            if($supply['order_from']!=11) {
                $result = D("Meal_order")->where(array('order_id' => $order_id))->data(array('order_status' => 4))->save();
                if (!$result) {
                    D()->rollback();
                    $this->returnCode('20020015');
                }
            }
        } elseif ($supply['item'] == 2) {
            //更新订单状态
            $result = D("Shop_order")->where(array('order_id' => $order_id))->data(array('order_status' => 3))->save();
            if (!$result) {
                $this->returnCode('20020015');
            }
            D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 4, 'name' => $now_user['name'], 'phone' => $now_user['phone']));
        } elseif ($supply['item'] == 3) {
            D('Service_offer')->offer_save_status($order_id, $uid, $supply['offer_id'], 9);
        } elseif ($supply['item'] == 8) {
            // 社区拼团
            D('Village_group_order')->where(array('order_id' => $supply['order_id']))->data(array('order_status' => 2))->save();

            $detail_info = M('Village_group_order_detail')->where(array('order_id' => $supply['order_id']))->select();

            foreach ($detail_info as $value) {
                $param['goods_id'] = $value['goods_id'];
                $param['from_type'] = 1;
                $param['deliver_status'] = 7;
                $param['note'] = '配送在团长处取货';
                $param['name'] = $now_user['name'];

                $param['order_id'] = $value['order_id'];
                D('Village_group')->add_log($param);
            }
        }
        D()->commit();
        $this->returnCode(0);
    }

    public function send_list()
    {
        $ticket = I('ticket', false);
        if ($ticket) {
            $info = ticket::get($ticket, $this->DEVICE_ID, true);
            $this->user_session['uid'] = $info['uid'];
        }
        if (empty($this->user_session)) {
            $this->returnCode('20020008');
        } else {
            $now_user = D('Deliver_user')->field(true)->where(array('uid' => $this->user_session['uid'],'status'=>array('neq',4)))->find();
            if (empty($now_user)) {
                $this->returnCode('20020001');
            }
        }

        $where = array();
        $where['status'] = 3;
//     	$where['item'] = 1;
        $where['uid'] = $this->user_session['uid'];

        $list = D('Deliver_supply')->field(true)->where($where)->order("`end_time` DESC")->select();
        if (false === $list) {
            $this->returnCode('20020010');
        }
        foreach ($list as $key => $val) {
            switch ($val['pay_type']) {
                case 'offline':
                    $list[$key]['pay_method'] = L_('线下支付');
                    break;
                default:
                    if ($val['paid']) {
                        $list[$key]['pay_method'] = L_('在线支付');
                        break;
                    } else {
                        $list[$key]['pay_method'] = L_('未支付');
                        break;
                    }
            }
            if (empty($val['supply_id'])) {
                continue;
            }
            $tmp['supply_id'] = $val['supply_id'];
            $tmp['from_site'] = empty($val['from_site']) ? '' : $val['from_site'];
            $tmp['aim_site'] = empty($val['aim_site']) ? '' : $val['aim_site'];
            $tmp['name'] = empty($val['name']) ? '' : $val['name'];
            $tmp['status'] = empty($val['status']) ? '' : $val['status'];
            $tmp['store_name'] = empty($val['store_name']) ? '' : $val['store_name'];
            $tmp['phone'] = empty($val['phone']) ? '' : $val['phone'];
            $tmp['pay_method'] = $list[$key]['pay_method'];
            $supply_id[] = $tmp['supply_id'];
            $arr[] = $tmp;
        }
        $arr = empty($arr) ? array() : $arr;
        $this->returnCode(0, $arr);
    }

    public function send()
    {

        $ticket = I('ticket', false);
        if ($ticket) {
            $info = ticket::get($ticket, $this->DEVICE_ID, true);
            $uid = $info['uid'];
        }
        if (empty($uid)) {
            $this->returnCode('20020008');
        } else {
            $now_user = D('Deliver_user')->field(true)->where(array('uid' => $uid,'status'=>array('neq',4)))->find();
            if (empty($now_user)) {
                $this->returnCode('20020001');
            }
        }

        $supply_id = intval(I("supply_id"));
        if (!$supply_id) {
            $this->returnCode('20020011');
        }
        //获取order_id
        $supply = D('Deliver_supply')->find($supply_id);
        if (!$supply['order_id']) {
            $this->returnCode('20020013');
        }
        if ($supply['status'] != 3) {
            $this->returnCode(1, null, L_("当前状态不能修改成送达状态！"));
        }
        $order_id = $supply['order_id'];

        D()->startTrans();
        $columns = array();
        $columns['status'] = 4;
        $columns['end_time'] = time();
        $columns['real_deliver_start_time'] = date('Y-m-d H:i:s');
        $columns['deliver_user_fee'] = $supply['freight_charge'] - ($supply['freight_charge'] * ($now_user['take_percent'] / 100));
        $result = D('Deliver_supply')->where(array("supply_id" => $supply_id, 'status' => 3))->data($columns)->save();
        //记录订单配送状态流转记录
        M('Scan_order_transfer_log')->add(['uid' => $uid, 'status' => $columns['status'], 'add_time' => time(), 'supply_id' => $supply_id]);
        if (false === $result) {
            $this->returnCode('20020022');
        }
        if ($supply['item'] == 1) {
            //获取订单信息
            $order = D("Waimai_order")->find($order_id);
            if (!$order) {
                $this->returnCode('20020014');
            }

            //更新订单状态
            $result = D("Waimai_order")->where(array('order_id' => $order_id))->data(array('order_status' => 5))->save();
            if (!$result) {
                D()->rollback();
                $this->returnCode('20020015');
            }
            //添加订单日志
            $log = array();
            $log['status'] = 5;
            $log['order_id'] = $order_id;
            $log['store_id'] = $order['store_id'];
            $log['uid'] = $uid;
            $log['time'] = time();
            $log['group'] = 4;
            $result = D("Waimai_order_log")->add($log);
            if (!$result) {
                $this->returnCode('20020016');
                D()->rollback();
            }
        } elseif ($supply['item'] == 0) {
            //更新订单状态
            if($supply['order_from']!=11) {
                $result = D("Meal_order")->where(array('order_id' => $order_id))->data(array('order_status' => 5))->save();
                if (!$result) {
                    D()->rollback();
                    $this->returnCode('20020015');
                }
            }
        } elseif ($supply['item'] == 2) {

            //更新订单状态
            $result = D("Shop_order")->where(array('order_id' => $order_id))->data(array('order_status' => 4))->save();
            if (!$result) {
                $this->returnCode('20020015');
            }
            D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 5, 'name' => $now_user['name'], 'phone' => $now_user['phone']));

        } elseif ($supply['item'] == 8) {
            // 社区拼团
            D('Village_group_order')->where(array('order_id' => $supply['order_id']))->data(array('order_status' => 3))->save();

            $detail_info = M('Village_group_order_detail')->where(array('order_id' => $supply['order_id']))->select();

            foreach ($detail_info as $value) {
                $param['goods_id'] = $value['goods_id'];
                $param['from_type'] = 1;
                $param['deliver_status'] = 8;
                $param['note'] = '配送员配送中';
                $param['name'] = $now_user['name'];

                $param['order_id'] = $value['order_id'];
                D('Village_group')->add_log($param);
            }
        }


        D()->commit();
        $this->returnCode(0);
    }

    public function my()
    {
        $ticket = I('ticket', false);
        if ($ticket) {
            $info = ticket::get($ticket, $this->DEVICE_ID, true);
            $this->user_session['uid'] = $info['uid'];
        }
        if (empty($this->user_session)) {
            $this->returnCode('20020008');
        } else {
            $now_user = D('Deliver_user')->field(true)->where(array('uid' => $this->user_session['uid'],'status'=>array('neq',4)))->find();
            if (empty($now_user)) {
                $this->returnCode('20020001');
            }
        }

        $where = array();
        $where['status'] = array('in', array(4, 5, 6));
//     	$where['item'] = 1;
        $where['uid'] = $this->user_session['uid'];
        $_GET['page'] = I('page');
        import('@.ORG.common_page');
        $count = D('Deliver_supply')->field(true)->where($where)->count();
        $p = new Page($count, 10);
        $list = D('Deliver_supply')->field(true)->where($where)->order("`status` ASC,`end_time` DESC")->limit($p->firstRow, $p->listRows)->select();

        if (false === $list) {
            $this->returnCode('20020010');
        }
        foreach ($list as $key => $val) {
            switch ($val['pay_type']) {
                case 'offline':
                    $list[$key]['pay_method'] = L_('线下支付');
                    break;
                default:
                    if ($val['paid']) {
                        $list[$key]['pay_method'] = L_('在线支付');
                        break;
                    } else {
                        $list[$key]['pay_method'] = L_('未支付');
                        break;
                    }
            }
            if (empty($val['supply_id'])) {
                continue;
            }
            $tmp['supply_id'] = $val['supply_id'];
            $tmp['from_site'] = empty($val['from_site']) ? '' : $val['from_site'];
            $tmp['aim_site'] = empty($val['aim_site']) ? '' : $val['aim_site'];
            $tmp['name'] = empty($val['name']) ? '' : $val['name'];
            $tmp['status'] = empty($val['status']) ? '' : $val['status'];
            $tmp['store_name'] = empty($val['store_name']) ? '' : $val['store_name'];
            $tmp['phone'] = empty($val['phone']) ? '' : $val['phone'];
            $tmp['pay_method'] = $list[$key]['pay_method'];
            $supply_id[] = $tmp['supply_id'];
            $arr[] = $tmp;
        }
        $arr = empty($arr) ? array() : $arr;
        $return['count'] = empty($p->totalPage) ? 0 : $p->totalPage;
        $return['lists'] = $arr;
        $this->returnCode(0, $return);
    }

    public function complete()
    {

        $ticket = I('ticket', false);
        if ($ticket) {
            $info = ticket::get($ticket, $this->DEVICE_ID, true);
            $uid = $info['uid'];
        }
        if (empty($uid)) {
            $this->returnCode('20020008');
        } else {
            $now_user = D('Deliver_user')->field(true)->where(array('uid' => $uid,'status'=>array('neq',4)))->find();
            if (empty($now_user)) {
                $this->returnCode('20020001');
            }
        }

        $supply_id = intval(I("supply_id"));
        if (!$supply_id) {
            $this->returnCode('20020011');
        }
        //获取order_id
        $supply = D('Deliver_supply')->find($supply_id);
        if (!$supply['order_id']) {
            $this->error(L_("配送信息错误"));
        }
        if ($supply['status'] != 4) {
            $this->returnCode(1, null, L_("当前状态不能修改成完成状态！"));
        }
        $order_id = $supply['order_id'];
// 	D()->startTrans();
        $columns = array();
        $columns['uid'] = $uid;
        $columns['status'] = 5;
        $columns['end_time'] = time();
        $columns['finish_time'] = time();
        $result = D('Deliver_supply')->where(array("supply_id" => $supply_id, 'status' => 4))->data($columns)->save();
        //记录订单配送状态流转记录
        M('Scan_order_transfer_log')->add(['uid' => $uid, 'status' => $columns['status'], 'add_time' => time(), 'supply_id' => $supply_id]);

        if (false === $result) {
            $this->returnCode('20020022');
        }


        //统计每日配送订单量
        $total = D('Deliver_supply')->where(array('uid' => $uid, 'status' => array('gt', 4)))->count();
        D('Deliver_user')->where(array('uid' => $uid,'status'=>array('neq',4)))->save(array('num' => $total));

        $todayNum = D('Deliver_supply')->where(array('uid' => $uid, 'status' => array('gt', 4), 'end_time' => array(array('gt', strtotime(date('Y-m-d') . ' 00:00:00')), array('lt', time() + 2))))->count();
        $date = date('Ymd');
        if ($deliver_count = D('Deliver_count')->field(true)->where(array('uid' => $uid, 'today' => $date))->find()) {
            D('Deliver_count')->where(array('uid' => $uid, 'today' => $date))->save(array('num' => $todayNum));
        } else {
            D('Deliver_count')->add(array('uid' => $uid, 'today' => $date, 'num' => $todayNum));
        }


        if ($supply['item'] == 1) {
            //获取订单信息
            $order = D("Waimai_order")->find($order_id);
            if (!$order) {
                $this->returnCode('20020014');
            }

            //更新订单状态
            $result = D("Waimai_order")->where(array('order_id' => $order_id))->data(array('order_status' => 1))->save();
            if (!$result) {
// 			D()->rollback();
                $this->returnCode('20020015');
            }
            //添加订单日志
            $log = array();
            $log['status'] = 1;
            $log['order_id'] = $order_id;
            $log['store_id'] = $order['store_id'];
            $log['uid'] = $uid;
            $log['time'] = time();
            $log['group'] = 4;
            $result = D("Waimai_order_log")->add($log);
            if (!$result) {
                $this->returnCode('20020016');
// 			D()->rollback();
            }
        } elseif ($supply['item'] == 0) {
            //更新订单状态//获取订单信息
            if($supply['order_from']==11){
                $order = D("Gift_order")->field(true)->where(array('order_id' => $order_id))->find();
            }else{
                $order = D("Meal_order")->field(true)->where(array('order_id' => $order_id))->find();
            }
            if (!$order) {
                $this->returnCode('20020014');
            }
            if($supply['order_from']==11){
                $result = D("Gift_order")->where(array('order_id' => $order_id))->data(['status'=>2])->save();
            }else{
                $data = array('order_status' => 1, 'status' => 1);
                if ($order['paid'] == 0) {
                    $data['paid'] = 1;
                    if (empty($data['pay_type']) && empty($data['pay_time'])) $data['pay_type'] = 'offline';
                    if (empty($data['pay_time'])) $data['pay_time'] = time();
                }
                //验证商家余额增加

                $order['order_type'] = 'meal';
                $info = unserialize($order['info']);
                $info_str = '';
                foreach ($info as $v) {
                    $info_str .= $v['name'] . ':' . $v['price'] . '*' . $v['num'] . '</br>';
                }
                //D('Merchant_money_list')->add_money($this->store['mer_id'],'用户购买'.$info_str.'记入收入',$order);
                $order['desc'] = L_('用户购买X1记入收入', array('X1' => $info_str));
                D('SystemBill')->bill_method($order['is_own'], $order);

                $result = D("Meal_order")->where(array('order_id' => $order_id))->data($data)->save();
            }
            if (!$result) {
// 			D()->rollback();
                $this->returnCode('20020015');
            }
            if($supply['order_from']==11){
                D('System_order')->where(['type'=>'gift','order_id'=>$order_id])->save(['system_status'=>6]);
            }
        } elseif ($supply['item'] == 2) {//快店的配送
// 		D()->commit();
            if ($order = D("Shop_order")->field(true)->where(array('order_id' => $order_id))->find()) {
                //赠送满减送优惠卷
                if($order['status'] == 2){
                    $order_detail = M('Shop_order_detail')->where(array('order_id'=>$order_id))->select();
                    if($order_detail){
                        foreach($order_detail as $k=>$v){
                            if($v['give_coupon_id']){
                                $data['coupon_id'] = $v['give_coupon_id'];
                                $data['uid'] = $order['uid'];
                                $data['num'] = 1;
                                $data['receive_time'] = time();
                                $data['is_use'] = 0;
                                M('Card_new_coupon_hadpull')->data($data)->add();
                            }
                        }
                    }
                }
                //配送状态更改成已完成，订单状态改成已消费
                if ($order['status'] != 1) {
                    $this->returnCode(1, null, L_('该订单的当前状态无法修改成配送完成状态'));
                }
                $data = array('order_status' => 5, 'status' => 2);
                if ($order['is_pick_in_store'] == 0) {//平台配送

                    if ($order['paid'] == 0 || ($order['pay_type'] == 'offline' && empty($order['third_id']))) {
                        $data['paid'] = $order['paid'] == 0 ? 1 : $order['paid'];
                        $data['pay_type'] = '';
                        $data['balance_pay'] = $order['balance_pay'] + $supply['deliver_cash'];
                        $order['balance_pay'] = $order['balance_pay'] + $supply['deliver_cash'];
                    }
                } else {
                    if ($order['paid'] == 0) {
                        $data['paid'] = 1;
                        if (empty($order['pay_type']) && empty($order['pay_time'])) $data['pay_type'] = 'offline';
                    }
                }
                if (empty($order['pay_time'])) $data['pay_time'] = time();
                if (empty($order['use_time'])) $data['use_time'] = time();
                if (empty($order['third_id'])) $data['third_id'] = $order['order_id'];
                if ($result = D("Shop_order")->where(array('order_id' => $order_id, 'status' => array('neq', 2)))->data($data)->save()) {
                    if ($order['is_pick_in_store'] == 0) {//平台配送
                        if ($order['paid'] == 0 || ($order['pay_type'] == 'offline' && empty($order['third_id']))) {
                            D('User_money_list')->add_row($order['uid'], 1, $supply['deliver_cash'], L_('配送员模拟手动充值'));
                            D('User_money_list')->add_row($order['uid'], 2, $supply['deliver_cash'], L_('用户购买X1产品',['X1'=>$this->config['shop_alias_name']]));
                        }
                    }
                    D('Pick_order')->where(array('store_id' => $order['store_id'], 'order_id' => $order['order_id']))->save(array('status' => 4));
                    D('Shop_order')->shop_notice($order);
                    //检查含有称重商品的订单，是否需要部分退款
                    if ($this->config['is_open_shop_weigh'] == 1 && $order['is_weight'] == 1) {
                        D('Shop_order')->checkWeightOrder($order);
                    }
                    
                    if ($order['is_pick_in_store'] == 5) {
                        if ($order['platform'] == 1) {
                            $returnData = D('Merchant_money_list')->use_money($order['mer_id'], $order['freight_charge'], 'deliver', L_('平台配送商家由饿了么平台的订单的配送费'), $order['order_id']);
                        } elseif ($order['platform'] == 2) {
                            $returnData = D('Merchant_money_list')->use_money($order['mer_id'], $order['freight_charge'], 'deliver', L_('平台配送商家由美团外卖平台的订单的配送费'), $order['order_id']);
                        }
                    }
                    // 周期购
                    if($order['periodic_type']){//周期购
                        //获得最近需要配送的周期
                        $periodic_deliver = M('Mall_periodic_deliver')->where(array('order_id'=>$order['order_id'],'status'=>0))->order('deliver_num asc')->find();
                        // 进入下一周期 配送状态更新
                        if ($periodic_deliver) {
                            $o_data['is_pick_in_store'] = 3;
                            $o_data['order_status'] = 0;
                            $o_data['status'] = 0;
                            $o_data['deliver_info'] = '';
                            D("Shop_order")->where(array('order_id' => $order_id))->data($o_data)->save();
                            D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 35, 'name' => $now_user['name'], 'phone' => $now_user['phone'],'note'=>'配送员确认收货'));
                        }
                    }else{
                        D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 6, 'name' => $now_user['name'], 'phone' => $now_user['phone']));
                    }
                } else {
                    $this->returnCode('20020015');
                }
            } else {
                $this->returnCode('20020014');
            }


            if (C('config.open_deliver_level') && $now_user['group'] == 1) {
                if ($order['expect_use_time'] >= time()) {
                    // 准时达积分
                    $score = C('config.deliver_level_ontime_get_score');
                    $desc = '准时配送获得' . $score . '积分';
                    $res = D('Deliver_user')->add_score($uid, $score, $desc, 4, $supply);
                } else {
                    // 配送超时扣除积分
                    $score = C('config.deliver_level_overtime_deduct_score');
                    $desc = '配送超时扣除' . $score . '积分';
                    $res = D('Deliver_user')->deduct_score($uid, $score, $desc, 6, $supply);
                }
            }
        } elseif ($supply['item'] == 3) {
            D('Service_offer')->offer_save_status($order_id, $uid, $supply['offer_id'], 4);
        } elseif ($supply['item'] == 8) {
            // 社区拼团
            D('Village_group_order')->where(array('order_id' => $supply['order_id']))->data(array('order_status' => 4))->save();

            $detail_info = M('Village_group_order_detail')->where(array('order_id' => $supply['order_id']))->select();

            foreach ($detail_info as $value) {
                $param['goods_id'] = $value['goods_id'];
                $param['from_type'] = 1;
                $param['deliver_status'] = 9;
                $param['note'] = '配送员送达，完成核销';
                $param['name'] = $now_user['name'];

                $param['order_id'] = $value['order_id'];
                D('Village_group')->add_log($param);
            }
            M('Village_group_order_detail')->where(array('order_id' => $supply['order_id']))->setField('status', 3);
            $order = M('Village_group_order')->where(array('order_id' => $supply['order_id']))->find();
            $my_header = D('Village_group')->get_header_info($order['start_uid']);
            $my_header = $my_header['msg'];
            $order_detail = D('Village_group')->get_order_detail($supply['order_id'], 1);
            D('Village_group')->verify_notice($order_detail, $order, $my_header, 2);
        }

        // 完成订单获取配送员积分
        if (C('config.open_deliver_level') && $now_user['group'] == 1) {
            $score = C('config.deliver_level_complate_get_score');
            $desc = '完成订单获得' . $score . '积分';
            $res = D('Deliver_user')->add_score($uid, $score, $desc, 2, $supply);
        }

        // 完成订单新增配送小费记录
        if($supply['tip_price'] > 0){
            $tip_price_log = array(
                'uid' => $supply['uid'],
                'income' => 1,
                'money' => $supply['tip_price'],
                'note' => '小费收入',
                'type' => 2,
                'business_type' => 'service',
                'is_valid' => 1,
                'business_id' => $supply['order_id'],
                'create_time' => time()
            );
            $is_tip_price = D('Deliver_user_money_log')->field(true)->where(array('uid' => $supply['uid'], 'type' => 2, 'business_type' => 'service', 'business_id' => $supply['order_id']))->find();
            if (empty($is_tip_price)) {
                D('Deliver_user_money_log')->add($tip_price_log);
            }
        }

        $this->returnCode(0);
    }


    public function reg()
    {
        $data['name'] = $_POST['name'];
        if (empty($data['name'])) {
            $this->returnCode('20030001');
        }
        $data['phone'] = $_POST['phone'];
        if (empty($data['phone'])) {
            $this->returnCode('20030002');
        }
        if (M('Deliver_user')->where(array(
            'phone' => $data['phone'],'status'=>array('neq',4)
        ))->find()) {
            $this->returnCode('20030005');
        }

        $data['pwd'] = $_POST['pwd'];
        if (strlen($data['pwd']) < 6) {
            $this->returnCode('20030003');
        }
        $data['pwd'] = md5($data['pwd']);
        $data['create_time'] = time();
        $data['last_time'] = time();
        $data['status'] = '0';
        $data['group'] = '1';
        if (M('Deliver_user')->data($data)->add()) {
            $this->returnCode('0');
        } else {
            $this->returnCode('20030004');
        }
    }

    protected function get_deliver_info()
    {
        $now_deliver = M('Deliver_user')->where(array('uid' => $this->_uid, 'status' => '1'))->find();
        if (empty($now_deliver)) {
            $this->returnCode('20030102');
        }
        return $now_deliver;
    }

    public function new_index()
    {
        if (empty($this->_uid)) {
            $this->returnCode('20030101');
        }
        $now_deliver = $this->get_deliver_info();
        $return_deliver = array();
        if (empty($_POST['poll'])) {
            $return_deliver['map_lng'] = $now_deliver['now_lng'];
            $return_deliver['map_lat'] = $now_deliver['now_lat'];

            $return_deliver['name'] = $now_deliver['name'];
            if ($now_deliver['store_id'] || $now_deliver['store_ids']) {
                $store = D('Merchant_store')->field(true)->where(array('store_id' => $now_deliver['store_id']))->find();
                $store_image_class = new store_image();
                $images = $store_image_class->get_allImage_by_path($store['pic_info']);

                $return_deliver['store_image'] = $images ? array_shift($images) : '';
                $return_deliver['is_system'] = false;
                $return_deliver['store_name'] = $store['name'];
                $return_deliver['store_num'] = !empty($now_deliver['store_ids']) ? count(explode(',', $now_deliver['store_ids'])) : 1;
                if ($return_deliver['store_num'] == 1) {
                    $return_deliver['store_num_show'] = '(' . $store['name'] . ')';
                } else {
                    $return_deliver['store_num_show'] = '(' . L_('店铺数量') . '：' . $return_deliver['store_num'] . ')';
                }
            } else {
                $return_deliver['system_image'] = $this->config['wechat_share_img'] ? $this->config['wechat_share_img'] : $this->config['site_logo'];
                $return_deliver['is_system'] = true;
            }
        }


        $my_distance = $now_deliver['range'] * 1000;
        $time = time();
        if ($now_deliver['group'] == 2 && ($now_deliver['store_id'] || $now_deliver['store_ids'])) {
            $storeIds = !empty($now_deliver['store_ids']) ? $now_deliver['store_ids'] : $now_deliver['store_id'];
            $where = "`create_time`<$time AND `status`=1";
            $where = "`type`= 1 AND `store_id` IN (" . $storeIds . ") AND " . $where;
        }else {
            $where = "`create_time`<$time AND `status`=1 ";
            if ($now_deliver['delivery_range_type'] == 0) {
                $where .= " AND ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$now_deliver['lat']}*PI()/180-`from_lat`*PI()/180)/2),2)+COS({$now_deliver['lat']}*PI()/180)*COS(`from_lat`*PI()/180)*POW(SIN(({$now_deliver['lng']}*PI()/180-`from_lnt`*PI()/180)/2),2)))*1000) < $my_distance ";
            } else {
                $where .= " AND MBRContains(PolygonFromText('{$now_deliver['delivery_range_polygon']}'),PolygonFromText(CONCAT('Point(',from_lnt,' ',from_lat,')')))>0";
            }
            $where = "`type`= 0 AND " . $where;
        }
        $gray_count = D("Deliver_supply")->where(['item'=>['IN',[0,1,2,3]]])->where($where)->count();

        if (empty($_POST['onlyGrab'])) {
            $deliver_count = D('Deliver_supply')->where(array('item'=>['IN',[0,1,2,3]],'uid' => $now_deliver['uid'], 'status' => array(array('gt', 0), array('lt', 5))))->count();
            $finish_count = D('Deliver_supply')->where(array('item'=>['IN',[0,1,2,3]],'uid' => $now_deliver['uid'], 'status' => array('gt', 4)))->count();
            $arr = array('deliver_info' => $return_deliver, 'gray_count' => $gray_count, 'deliver_count' => $deliver_count, 'finish_count' => $finish_count);
            $config = M('Appapi_app_config')->select();
            foreach ($config as $v) {
                if ($v['var'] == 'deliver_android_version') {
                    $arr['deliver_android_version'] = $v['value'];
                } elseif ($v['var'] == 'deliver_android_url') {
                    $arr['deliver_android_url'] = $v['value'];
                } elseif ($v['var'] == 'deliver_android_vcode') {
                    $arr['deliver_android_vcode'] = $v['value'];
                } elseif ($v['var'] == 'deliver_android_vdes') {
                    $arr['deliver_android_vdes'] = $v['value'];
                }

            }
        } else {
            $arr['gray_count'] = $gray_count;
        }
        if ($now_deliver['group'] == 1 && M('Area')->where(['area_id' => $now_deliver['city_id']])->getField('deliver_model')) {
            $arr['gray_count'] = 0;
        }

        $arr['open_multilingual'] = $this->config['open_multilingual'] ? $this->config['open_multilingual'] : '0';
		$arr['site_phone'] 	      = strval($this->config['site_phone']);		//网站电话
        $arr['lang_config'] = D('Lang')->lang_list();//语言配置
        $this->returnCode('0', $arr);
    }

    // 获取待取货待配送订单
    public function get_deliver_list()
    {
        if (empty($this->_uid)) {
            $this->returnCode('20030101');
        }
        $now_deliver = $this->get_deliver_info();
        $status = intval($_POST['status']);
        $where = array();
        if ($status) {
            if ($status == 3) {
                $where['status'] = array('in', array('3', '4'));
            } else {
                $where['status'] = $status;
            }
        } else {
            $where['status'] = array('in', array('2', '3', '4'));
        }

        $where['uid'] = $now_deliver['uid'];
        if ($now_deliver['group'] == 2 && ($now_deliver['store_id'] || $now_deliver['store_ids'])) {
            $storeIds = !empty($now_deliver['store_ids']) ? explode(',', $now_deliver['store_ids']) : [$now_deliver['store_id']];
            $where['store_id'] = ['IN', $storeIds];
        }
        $list = D('Deliver_supply')
            ->where($where)
            ->field('supply_id,order_id,from_lnt,from_lat,aim_lnt,aim_lat,status')
            ->order("`create_time` DESC")
            ->limit(100)
            ->select();
        $list = $list ? $list : array();
        foreach ($list as &$value) {
            if ($value['status'] == 4) {
                $value['status'] = 3;
            }
        }
        $this->returnCode('0', $list);
    }


    public function lang()
    {
        $arr['lang_config'] = D('Lang')->lang_list();//语言配置
        $this->returnCode('0', $arr);
    }

    public function new_tongji()
    {
        if (empty($this->_uid)) {
            $this->returnCode('20030101');
        }
        $now_deliver = $this->get_deliver_info();

        $storeId = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
        $begin_time = isset($_POST['begin_time']) && $_POST['begin_time'] ? $_POST['begin_time'] : date('Y-m-d');
        $end_time = isset($_POST['end_time']) && $_POST['end_time'] ? $_POST['end_time'] : date('Y-m-d');

        $order_from = isset($_POST['order_from']) && $_POST['order_from'] ? intval($_POST['order_from']) : 0;
        $where = array('uid' => $now_deliver['uid'], 'status' => array('gt', 0));
        if ($storeId > 0) {
            $where['store_id'] = $storeId;
        }

        if ($begin_time > $end_time) {
            $this->returnCode('1', array(), L_('开始时间应该比结束时间早'));
        }
        if($now_deliver['group'] == 2){
            $where['order_from'] = array('in', array(0, 1, 2, 3, 4, 5));
        }else{
            if ($order_from == 0) {
                $where['order_from'] = array('in', array(0, 1, 2));
            } elseif ($order_from == 3) {
                $where['order_from'] = $order_from;
            } else {
                $where['order_from'] = array('in', array(4, 5));
            }
        }

        if ($begin_time && $end_time) {
            $where['end_time'] = array(array('gt', strtotime($begin_time)), array('lt', strtotime($end_time . '23:59:59')));
        }
        $result = D('Deliver_supply')->field('sum(deliver_cash) as offline_money, sum(money-deliver_cash) as online_money, sum(freight_charge) as freight_charge, sum(tip_price) as tip_price , sum(deliver_user_fee) as deliver_user_fee , sum(freight_charge-deliver_user_fee) as platform_fee')->where($where)->find();

        $where['status'] = array('egt', 0);
        $count_list = D('Deliver_supply')->field('count(1) as cnt, get_type')->where($where)->group('get_type')->select();

        $where['status'] = array('eq', 0);
        $freight_charge_info = D('Deliver_supply')->field('sum(freight_charge) as freight_charge')->where($where)->find();
        foreach ($count_list as $row) {
            if ($row['get_type'] == 0) {
                $result['self_count'] = $row['cnt'];
            } elseif ($row['get_type'] == 1) {
                $result['system_count'] = $row['cnt'];
            } elseif ($row['get_type'] == 2) {
                $result['change_count'] = $row['cnt'];
            }
        }
        $result['offline_money'] = $result['offline_money'] ? $result['offline_money'] : 0;
        $result['online_money'] = $result['online_money'] ? $result['online_money'] : 0;
        $result['freight_charge'] = $result['freight_charge'] ? $result['freight_charge'] : 0;
        $result['deliver_user_fee'] = $result['deliver_user_fee'] ? $result['deliver_user_fee'] : 0;
        $result['platform_fee'] = $result['platform_fee'] ? $result['platform_fee'] : 0;
        if ($freight_charge_info && $freight_charge_info['freight_charge'] > 0) {
            $result['freight_charge'] += $freight_charge_info['freight_charge'];
        }

        $result['self_count'] = $result['self_count'] ? $result['self_count'] : 0;
        $result['system_count'] = $result['system_count'] ? $result['system_count'] : 0;
        $result['change_count'] = $result['change_count'] ? $result['change_count'] : 0;
        $result['tip_price'] = $result['tip_price'] ? $result['tip_price'] : 0;
        $result['appDate'] = $begin_time;
        $result['appDate1'] = $end_time;

        // 查询一下帮我买、帮我送是否存在
        $database_Service_category = D('Service_category');
        $buy_category = $database_Service_category->field(true)->order('cid ASC')->where(array('type'=>2, 'cat_status' => 1))->find();
        $give_category = $database_Service_category->field(true)->order('cid ASC')->where(array('type'=>3, 'cat_status' => 1))->find();
        if ($buy_category) {
            $result['is_buy'] = true;
        } else {
            $result['is_buy'] = false;
        }
        if ($give_category) {
            $result['is_give'] = true;
        } else {
            $result['is_give'] = false;
        }
        // 获取 调度模式设置配置
        $config_data = D('Config_data')->get_config_data();
        $result['deliver_model_switch'] = ($config_data && $config_data['deliver_model_switch']) ?  true : false;
        $result['shop_shop_alias_name'] = L_('X1订单', $this->config['shop_alias_name']);

        //外卖展示取货数量、配送数量、送达数量
        if ($order_from == 0) {
            $where = array();
            $where['l.uid'] = $now_deliver['uid'];
            if ($now_deliver['group'] == 2 && ($now_deliver['store_id'] || $now_deliver['store_ids'])) {
                $storeIds = !empty($now_deliver['store_ids']) ? explode(',', $now_deliver['store_ids']) : [$now_deliver['store_id']];
                $where['s.store_id'] = ['IN', $storeIds];
            }
            if ($begin_time && $end_time) {
                $where['l.add_time'] = array(array('gt', strtotime($begin_time)), array('lt', strtotime($end_time . '23:59:59')));
            }
            //取货数量
            $where['l.status'] = 3;
            $result['pick_count'] = D('Scan_order_transfer_log')->alias('l')->join(C('DB_PREFIX') . 'deliver_supply AS s ON s.supply_id = l.supply_id')->where($where)->count();
            //配送数量
            $where['l.status'] = 4;
            $result['send_count'] = D('Scan_order_transfer_log')->alias('l')->join(C('DB_PREFIX') . 'deliver_supply AS s ON s.supply_id = l.supply_id')->where($where)->count();
            //完成数量
            $where['l.status'] = ['gt', 4];
            $result['complete_count'] = D('Scan_order_transfer_log')->alias('l')->join(C('DB_PREFIX') . 'deliver_supply AS s ON s.supply_id = l.supply_id')->where($where)->count();
        }
        $this->returnCode('0', $result);
    }

    public function new_info()
    {
        if (empty($this->_uid)) {
            $this->returnCode('20030101');
        }
        $now_deliver = $this->get_deliver_info();

        $total_list = D('Deliver_supply')->field('count(1) as cnt, sum(distance) as distance')->where(array('uid' => $now_deliver['uid'], 'status' => array('gt', 4)))->find();
        $grap_count = D('Deliver_supply')->where(array('uid' => $now_deliver['uid'], 'get_type' => 0))->count();


        $result['finish_total'] = isset($total_list['cnt']) ? intval($total_list['cnt']) : 0;
        $result['distance'] = isset($total_list['distance']) ? number_format($total_list['distance'], 2) : 0;
        $result['total'] = $grap_count;
        $result['score'] = $now_deliver['average_score'];
        $result['score_width'] = 20 * $now_deliver['average_score'];
        // 配送员等级
        $now_level = D('Deliver_user')->get_user_level($now_deliver['score']);
        $result['initateKnight'] = $now_level['name'];
        // 是否开启配送员等级
        $result['open_deliver_level'] = $this->config['open_deliver_level'];
        // 是否设置了配送员等级
        $result['level_count'] = M('Deliver_level')->count();
        $this->returnCode('0', $result);
    }

    // 配送员等级
    public function my_level()
    {
        if (empty($this->_uid)) {
            $this->returnCode('20030101');
        }

        // 配送员信息
        $now_deliver = $this->get_deliver_info();

        // 当前等级
        $now_level = D('Deliver_user')->get_user_level($now_deliver['score']);
        $now_level['diff_score'] = max(0, round($now_level['next_score'] - $now_deliver['score'], 2));
        $result['now_level'] = $now_level;

        // 配送员等级
        $level_list = M('Deliver_level')->order('score asc')->select();
        $result['level_list'] = $level_list ? $level_list : array();
        foreach ($result['level_list'] as &$value) {
            $value['image'] = file_domain() . $value['image'];
        }

        // 等级规则
        $deliver_level_rule = M('Deliver_level_rule')->where('status=1')->order('sort desc')->select();
        $result['level_rule'] = $deliver_level_rule ? $deliver_level_rule : array();
        foreach ($result['level_rule'] as &$rule) {
            $rule['content'] = htmlspecialchars_decode($rule['content']);
            // $rule['content'] = strip_tags(html_entity_decode(htmlspecialchars_decode($rule['content'])));
        }
        $this->returnCode('0', $result);
    }


    // 配送员等级积分记录
    public function my_level_log()
    {
        if (empty($this->_uid)) {
            $this->returnCode('20030101');
        }
        $now_deliver = $this->get_deliver_info();
        // 配送员等级
        $where['uid'] = $now_deliver['uid'];
        $_GET['page'] = max(1, $_POST['page']);
        $score_log = D('Deliver_score_log')->get_limit_list_page($where, 20);
        if ($score_log['list']) {
            foreach ($score_log['list'] as &$value) {
                $value['time'] = date('Y-m-d H:i:s', $value['time']);
            }
        }
        $score_log['page'] = $_GET['page'];

        $this->returnCode('0', $score_log);
    }

    /**
     * 记录配送员的坐标
     */
    public function location()
    {
        if (empty($this->_uid)) {
            $this->returnCode('20030101');
        }
        $now_deliver = $this->get_deliver_info();

        $lng = floatval($_POST['lng']);
        if (!$lng || $lng==0) {
            $this->returnCode('20020009');
        }
        $lat = floatval($_POST['lat']);
        if (!$lat || $lat==0) {
            $this->returnCode('20020009');
        }
        $columns = array();
        $columns['uid'] = $now_deliver['uid'];
        $columns['lng'] = $lng;
        $columns['lat'] = $lat;
        $columns['create_time'] = time();
        $columns['create_ip'] = get_client_ip(0);

        $result = D("Deliver_user_location_log")->add($columns);
        D('Deliver_user')->where(array('uid' => $now_deliver['uid'],'status'=>array('neq',4)))->save(array('now_lng' => $lng, 'now_lat' => $lat, 'last_time' => time()));
        if (!$result) {
            $this->returnCode('20020021');
        }
        $this->returnCode(0);
    }

    public function new_deliver_list()
    {
        if (empty($this->_uid)) {
            $this->returnCode('20030101');
        }

        $now_deliver = $this->get_deliver_info();

        $lat = isset($_POST['lat']) && $_POST['lat'] ? $_POST['lat'] : $now_deliver['lat'];
        $lng = isset($_POST['lng']) && $_POST['lng'] ? $_POST['lng'] : $now_deliver['lng'];


        $my_lnt = $now_deliver['lng'];
        $my_lat = $now_deliver['lat'];
        $my_distance = $now_deliver['range'] * 1000;

        if ($now_deliver['group'] == 2 && ($now_deliver['store_id'] || $now_deliver['store_ids'])) {
            $storeIds = !empty($now_deliver['store_ids'])?$now_deliver['store_ids']:$now_deliver['store_id'];
            $where = "`status`=1";
            $where = "`type`= 1 AND `store_id` IN (" . $storeIds . ") AND " . $where;
        } else {
            // 状态必须为待结单和没有关联配送员
            $where = "`status`=1 AND `uid`=0";
            if ($now_deliver['delivery_range_type'] == 0) {
                $where .= " AND ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$my_lat}*PI()/180-`from_lat`*PI()/180)/2),2)+COS({$my_lat}*PI()/180)*COS(`from_lat`*PI()/180)*POW(SIN(({$my_lnt}*PI()/180-`from_lnt`*PI()/180)/2),2)))*1000) < $my_distance ";
            } else {
                $where .= " AND MBRContains(PolygonFromText('{$now_deliver['delivery_range_polygon']}'),PolygonFromText(CONCAT('Point(',from_lnt,' ',from_lat,')')))>0";
            }
            $where = "`type`= 0 AND " . $where;
        }
        $where .= " AND `create_time`>" . (time()-7*86400);
        $where .= " AND `item` IN (0,1,2,3)";

        $list = D('Deliver_supply')->field(true)->where($where)->order("`is_fetch_order` ASC,`supply_id` ASC")->select();
        $id_list = [];
        foreach ($list as &$value) {
            $value['phone'] = str_replace(' ', '', $value['phone']);
            if ($value['deliver_check'] == 0) {
                $id_list[] = $value['supply_id'];
            }
            $stripos_result = stripos($value['server_time'], '-');
            if (!($stripos_result == 0)) {
                $value['arrival_time_info'] = $value['server_time'];
            }
        }
        if (!empty($id_list)) {
            $data['deliver_check'] = 1;
            $data['check_time'] = $_SERVER['REQUEST_TIME'];
            M('Deliver_supply')->where(array('supply_id' => array('in', $id_list)))->save($data);
        }

        if (empty($list)) {
            $list = array();
        }else{
            $list = $this->add_store_image($list);
        }
        if ($now_deliver['group'] == 1 && M('Area')->where(['area_id'=>$now_deliver['city_id']])->getField('deliver_model')){
			$list = array();
		}

        $this->returnCode('0', $this->foramt_arr($list, $lng, $lat));
    }

    public function pick_count()
    {
        if (empty($this->_uid)) {
            $this->returnCode('20030101');
        }

        $now_deliver = $this->get_deliver_info();
        $where = array();
        $where['uid'] = $now_deliver['uid'];
        if ($now_deliver['group'] == 2 && ($now_deliver['store_id'] || $now_deliver['store_ids'])) {
            $storeIds = !empty($now_deliver['store_ids']) ? explode(',', $now_deliver['store_ids']) : [$now_deliver['store_id']];
            $where['store_id'] = ['IN', $storeIds];
        }

        //待取货
        $where['status'] = 2;
        $return['pick_count'] = D('Deliver_supply')->where($where)->count();
        //待配送
        $where['status'] = 3;
        $return['send_count'] = D('Deliver_supply')->where($where)->count();
        //配送中
        $where['status'] = 4;
        $return['my_count'] = D('Deliver_supply')->where($where)->count();
        $this->returnCode('0', $return);
    }

    public function new_pick_list()
    {
        if (empty($this->_uid)) {
            $this->returnCode('20030101');
        }

        $now_deliver = $this->get_deliver_info();

        $lat = isset($_POST['lat']) && $_POST['lat'] ? $_POST['lat'] : '';
        $lng = isset($_POST['lng']) && $_POST['lng'] ? $_POST['lng'] : '';
        $where = array();
        $where['status'] = 2;
        $where['uid'] = $now_deliver['uid'];
        if ($now_deliver['group'] == 2 && ($now_deliver['store_id'] || $now_deliver['store_ids'])) {
            $storeIds = !empty($now_deliver['store_ids']) ? explode(',', $now_deliver['store_ids']) : [$now_deliver['store_id']];
            $where['store_id'] = ['IN', $storeIds];
        }
        $where['item']=['IN',[0,1,2,3]];
        $list = D('Deliver_supply')->field(true)->where($where)->order("`is_fetch_order` ASC,`create_time` DESC")->select();
        $id_list = [];
        foreach ($list as &$value) {
            $value['phone'] = str_replace(' ', '', $value['phone']);
            if ($this->config['deliver_see_freight_charge'] == 0) {
                $value['freight_charge'] = '-';
            }
            if ($value['deliver_check'] == 0) {

                $id_list[] = $value['supply_id'];
            }
            $stripos_result = stripos($value['server_time'], '-');
            if (!($stripos_result == 0)) {
                $value['arrival_time_info'] = $value['server_time'];
            }
        }

        if (!empty($id_list)) {

            $data['deliver_check'] = 1;
            $data['check_time'] = $_SERVER['REQUEST_TIME'];
            M('Deliver_supply')->where(array('supply_id' => array('in', $id_list)))->save($data);
        }

        if (empty($list)) {
            $list = array();
        }else{
            $list = $this->add_store_image($list);
        }
        $this->returnCode('0', $this->foramt_arr($list, $lng, $lat));
    }

    public function new_send_list()
    {
        if (empty($this->_uid)) {
            $this->returnCode('20030101');
        }

        $now_deliver = $this->get_deliver_info();

        $lat = isset($_POST['lat']) && $_POST['lat'] ? $_POST['lat'] : '';
        $lng = isset($_POST['lng']) && $_POST['lng'] ? $_POST['lng'] : '';
        $where = array();
        $where['status'] = 3;
        $where['uid'] = $now_deliver['uid'];
        if ($now_deliver['group'] == 2 && ($now_deliver['store_id'] || $now_deliver['store_ids'])) {
            $storeIds = !empty($now_deliver['store_ids']) ? explode(',', $now_deliver['store_ids']) : [$now_deliver['store_id']];
            $where['store_id'] = ['IN', $storeIds];
        }
        $where['item']=['IN',[0,1,2,3]];
        $list = D('Deliver_supply')->field(true)->where($where)->order("`create_time` DESC")->select();
        foreach ($list as &$value) {
            $value['phone'] = str_replace(' ', '', $value['phone']);
        }
        if (empty($list)) {
            $list = array();
        }else{
            $list = $this->add_store_image($list);
        }

        $this->returnCode('0', $this->foramt_arr($list, $lng, $lat));
    }

    public function new_my_list()
    {
        if (empty($this->_uid)) {
            $this->returnCode('20030101');
        }

        $now_deliver = $this->get_deliver_info();

        $lat = isset($_POST['lat']) && $_POST['lat'] ? $_POST['lat'] : '';
        $lng = isset($_POST['lng']) && $_POST['lng'] ? $_POST['lng'] : '';
        $where = array();
        $where['status'] = 4;
        $where['uid'] = $now_deliver['uid'];
        if ($now_deliver['group'] == 2 && ($now_deliver['store_id'] || $now_deliver['store_ids'])) {
            $storeIds = !empty($now_deliver['store_ids']) ? explode(',', $now_deliver['store_ids']) : [$now_deliver['store_id']];
            $where['store_id'] = ['IN', $storeIds];
        }
        $list = D('Deliver_supply')->field(true)->where($where)->order("`create_time` DESC")->select();
        foreach ($list as &$value) {
            $value['phone'] = str_replace(' ', '', $value['phone']);
        }
        if (empty($list)) {
            $list = array();
        }else{
            $list = $this->add_store_image($list);
        }

        $this->returnCode('0', $this->foramt_arr($list, $lng, $lat));
    }

    public function new_finish()
    {
        if (empty($this->_uid)) {
            $this->returnCode('20030101');
        }

        $now_deliver = $this->get_deliver_info();

        $lat = isset($_POST['lat']) && $_POST['lat'] ? $_POST['lat'] : '';
        $lng = isset($_POST['lng']) && $_POST['lng'] ? $_POST['lng'] : '';

        $where = array();
        $page = isset($_POST['page']) && $_POST['page'] ? intval($_POST['page']) : 1;
        $page = max(1, $page);
        $where['status'] = array('gt', 4);
        $where['is_hide'] = 0;
        $where['uid'] = $now_deliver['uid'];
        if ($now_deliver['group'] == 2 && ($now_deliver['store_id'] || $now_deliver['store_ids'])) {
            $storeIds = !empty($now_deliver['store_ids']) ? explode(',', $now_deliver['store_ids']) : [$now_deliver['store_id']];
            $where['store_id'] = ['IN', $storeIds];
        }


        $count = D('Deliver_supply')->where($where)->count();

        $page_size = 5;
        $start = $page_size * ($page - 1);

        $list = D('Deliver_supply')->field(true)->where($where)->order("`create_time` DESC")->limit($start . ',' . $page_size)->select();
        foreach ($list as &$value) {
            $value['phone'] = str_replace(' ', '', $value['phone']);
            $stripos_result = stripos($value['server_time'], '-');
            if (!($stripos_result == 0)) {
                $value['arrival_time_info'] = $value['server_time'];
            }
        }
        
        if (empty($list)) {
            $list = array();
        }else{
            $list = $this->add_store_image($list);
        }

        $this->returnCode('0', array('list' => $this->foramt_arr($list, $lng, $lat), 'total_page' => ceil($count / $page_size)));
    }


    private function add_store_image($list){
        $store_ids = array_filter(array_column($list, 'store_id'));
        $where['store_id'] = ['in',$store_ids];
        $image_list = M('Merchant_store')->where($where)->field('store_id,pic_info')->select();
        $image_arr = [];
        foreach ($image_list as $key => $value) {
            $img = explode(';', $value['pic_info']);
            $image = [];
            foreach ($img as $val) {
                $image[] = file_domain().$val;
            }
            $image_arr[$value['store_id']] = array_slice($image,0,3);//只取三张图片
        }

        $show_image = C('config.show_store_image');
        foreach ($list as $k => $v) {
            $list[$k]['store_image'] = [];
            if($v['item']==2&&$show_image){//快店
                $list[$k]['store_image'] = $image_arr[$v['store_id']];
            }
        }
        return $list;
    }

    public function new_del()
    {
        if (empty($this->_uid)) {
            $this->returnCode('20030101');
        }
        $now_deliver = $this->get_deliver_info();

        $supply_id_arr = explode(',', $_POST['supply_ids']);
        foreach ($supply_id_arr as &$value) {
            if (empty($value)) {
                unset($value);
            }
        }
        D('Deliver_supply')->where(array('uid' => $now_deliver['uid'], 'supply_id' => array('in', $supply_id_arr), 'status' => array('gt', 4)))->save(array('is_hide' => 1));
        $this->returnCode('0');
    }

    public function new_detail()
    {
        if (empty($this->_uid)) {
            $this->returnCode('20030101');
        }

        $now_deliver = $this->get_deliver_info();

        $supply_id = intval($_POST['supply_id']);
        $where = array('supply_id' => $supply_id);
        $supply = D("Deliver_supply")->where($where)->find();
        if (empty($supply)) {
            $this->returnCode('20030201');
        }
        if ($supply['uid'] && $supply['uid'] != $now_deliver['uid']) {
            fdump('>>>>>>>>>>>>>>>>>>>>>'.__LINE__,'api/log/'.date('Ymd').'/deliver_app/new_detail_err_log',true);
            fdump($supply,'api/log/'.date('Ymd').'/deliver_app/new_detail_err_log',true);
            fdump($now_deliver,'api/log/'.date('Ymd').'/deliver_app/new_detail_err_log',true);
            fdump($this->_uid,'api/log/'.date('Ymd').'/deliver_app/new_detail_err_log',true);
            fdump($_POST,'api/log/'.date('Ymd').'/deliver_app/new_detail_err_log',true);
            fdump('<<<<<<<<<<<<<<<<<<<<'.__LINE__,'api/log/'.date('Ymd').'/deliver_app/new_detail_err_log',true);
            $this->returnCode('20030202');
        }
        $supply['phone'] = str_replace(' ', '', $supply['phone']);
        $supply['deliver_cash'] = floatval($supply['deliver_cash']);
        $supply['distance'] = floatval($supply['distance']);
        if ($this->config['deliver_see_freight_charge'] == 0 && $supply['status'] < 3) {
            $supply['freight_charge'] = '-';
            $supply['deliver_user_fee'] = '-';
        } else {
            $supply['freight_charge'] = floatval($supply['freight_charge']);
        }
        $supply['create_time'] = date('Y-m-d H:i', $supply['create_time']);
        if ($supply['item'] == 3) {
            if ($supply['status'] > 1) {
                $supply['appoint_time'] = date('Y-m-d H:i', $supply['appoint_time']);
            } else {
                $supply['appoint_time'] = "预计{$supply['server_time']}分钟内送达";
            }
        } else {
            $supply['appoint_time'] = $supply['appoint_time']?date('Y-m-d H:i', $supply['appoint_time']):'';
        }

        $supply['order_time'] = $supply['order_time'] ? date('Y-m-d H:i', $supply['order_time']) : '--';
        $supply['end_time'] = $supply['end_time'] ? date('Y-m-d H:i', $supply['end_time']) : L_('未送达');
        $supply['real_orderid'] = $supply['real_orderid'] ? $supply['real_orderid'] : $supply['order_id'];
        $supply['image'] = $supply['image'] ? explode(';', trim($supply['image'], ';')) : array();
		foreach($supply['image'] as $key=>$value){
			$supply['image'][$key] = replace_file_domain($value);
		}
        if ($supply['change_log']) {
            $changes = explode(',', $supply['change_log']);
            $uid = array_pop($changes);
            $supply['change_name'] = $this->getDeliverUser($uid);
        }

        // 隐私号处理
        $virtual_phone_arr = array();
        $virtual_phone = array_filter(explode(';', $supply['virtual_phone']));

        foreach ($virtual_phone as $v_phone) {
            $virtual_phone_arr[] = explode(',',$v_phone);
        }
        $supply['virtual_phone'] = $virtual_phone_arr[0] ? $virtual_phone_arr[0]: [];
        if ($supply['virtual_phone']) {
            $supply['phone_true'] = $supply['phone'];
            $supply['phone'] = phone_show($supply['phone']);
            $supply['virtual_phone_str'] = $virtual_phone_arr[0][0].($virtual_phone_arr[0][1] ? L_('转').$virtual_phone_arr[0][1] : '');
            $supply['phone'] .= ' '.$supply['virtual_phone_str'];
        }
        // 隐私号码是否过期
        if ($supply['virtual_phone_overtime']>time()) {
            $supply['virtual_phone_overtime'] = 0;
        }else{
            $supply['virtual_phone_overtime'] = 1;
        }

        $return_arr['supply'] = $supply;

        $goods = array();
        if ($supply['item'] == 1) {//外送系统的外送
            //订单信息
            $where = array();
            $where['order_id'] = $supply['order_id'];
            $order = D("Waimai_order")->where($where)->find();
            if (empty($order)) {
                $this->returnCode('20030203');
            }
            $pay_method = D('Config')->get_pay_method();
            $order['pay_type'] = $pay_method[$order['pay_type']]['name'];

            //商品信息
            $goods = D()->field(true)->table(array(C('DB_PREFIX') . 'waimai_sell_log' => 'sl', C('DB_PREFIX') . 'waimai_goods' => 'wg'))->where("sl.order_id=" . $supply['order_id'] . " AND sl.goods_id=wg.goods_id")->select();

            //店铺信息
            $store = D()->field(true)->table(array(C('DB_PREFIX') . 'merchant_store' => 'ms', C('DB_PREFIX') . 'waimai_store' => 'ws'))->where("ms.store_id=" . $order['store_id'] . " AND ms.store_id=ws.store_id")->find();
            if (!$store) {
                $this->returnCode('20030204');
            }
            if ($store['total_money'] <= 0) {
                $store['send_money'] = $store['send_money'];
            } else {
                $store['send_money'] = $order['price'] > $store['total_money'] ? 0 : $store['send_money'];
            }
            $store['tools_money'] = 0;
            if ($store['tools_money_have'] == 1) {
                foreach ($goods as $v) {
                    $store['tools_money'] += $v['tools_price'] * $v['num'];
                }
            }
            //红包信息
            $where = array();
            $where['id'] = $order['coupon_id'];
            $couponInfo = D("Waimai_user_coupon")->field(true)->where($where)->find();
            //优惠信息
            $discountInfo = json_decode($order['discount_detail'], true);
        } elseif ($supply['item'] == 0) {//老的餐饮外送
            //订单信息
            $where = array();
            $where['order_id'] = $supply['order_id'];
            if($supply['order_from']==11){
                $order = D("Gift_order")->where($where)->find();
            }else{
                $order = D("Meal_order")->where($where)->find();
            }
            if (empty($order)) {
                $this->returnCode('20030203');
            }
            $pay_method = D('Config')->get_pay_method();
            $order['pay_type'] = $pay_method[$order['pay_type']]['name'];
            $order['discount_price'] = $order['price'];
            
            if($supply['order_from']==11){
                $order['pay_type'] = $order['exchange_type']?'积分+余额':'积分';
                $order['real_orderid'] = '';
                $order['create_time'] = $order['order_time']?:'';
                $goods[0] = [
                    'name' => $order['order_name'],
                    'num' => $order['num'],
                    'spec' => '',
                ];
            }else{
                $goods = unserialize($order['info']);
                foreach ($goods as &$g) {
                    $g['tools_money'] = 0;
                }
                //店铺信息
                $store = D()->field(true)->table(array(C('DB_PREFIX') . 'merchant_store' => 'ms', C('DB_PREFIX') . 'merchant_store_meal' => 'ml'))->where("ms.store_id=" . $order['store_id'] . " AND ms.store_id=ml.store_id")->find();
                if (!$store) {
                    $this->returnCode('20030204');
                }
                if ($store['total_money'] <= 0) {
                    $store['send_money'] = $store['send_money'];
                } else {
                    $store['send_money'] = $order['price'] > $store['total_money'] ? 0 : $store['send_money'];
                }
                $store['tools_money'] = 0;
                if ($store['tools_money_have'] == 1) {
                    foreach ($goods as $v) {
                        $store['tools_money'] += $v['tools_price'] * $v['num'];
                    }
                }
            }
        } elseif ($supply['item'] == 2) {
            //订单信息
            $where = array();
            $where['order_id'] = $supply['order_id'];
            $order = D("Shop_order")->where($where)->find();

            if (empty($order)) {
                $this->returnCode('20030203');
            }
            $order['pay_type'] = D('Pay')->get_pay_name($order['pay_type'], $order['is_mobile_pay'], $order['paid']);
            $order['discount_price'] = $order['price'];
            $order['cue_field'] = $order['cue_field'] ? unserialize($order['cue_field']) : array();
            $this->assign('order', $order);
            $goods = D('Shop_order_detail')->field(true)->where(array('order_id' => $supply['order_id']))->select();
			//替换商品名称 (多语言开启时)
			if (C('config.open_multilingual')) {
				$goods = D('Shop_goods')->replace_goods_name($goods);
			}
			
            foreach ($goods as &$g) {
                $g['tools_money'] = 0;
            }

            //店铺信息
            $store = D()->field(true)->table(array(C('DB_PREFIX') . _view('merchant_store') => 'ms', C('DB_PREFIX') . 'merchant_store_shop' => 'ml'))->where("ms.store_id=" . $order['store_id'] . " AND ms.store_id=ml.store_id")->find();
            if (!$store) {
                $this->returnCode('20030204');
            }
            if ($store['total_money'] <= 0) {
                $store['send_money'] = $store['send_money'];
            } else {
                $store['send_money'] = $order['price'] > $store['total_money'] ? 0 : $store['send_money'];
            }
            $store['tools_money'] = 0;
            if ($store['tools_money_have'] == 1) {
                foreach ($goods as $v) {
                    $store['tools_money'] += $v['tools_price'] * $v['num'];
                }
            }
            $store['store_image'] = [];
            if(C('config.show_store_image')){
                $store_image = explode(';', $store['pic_info']);
                $store_arr = [];
                foreach ($store_image as $k => $v) {
                    $store_arr[] = file_domain().$v;
                }
                $store['store_image'] = array_slice($store_arr, 0,3);
            }
        } elseif ($supply['item'] == 3) {
            if ($supply['server_type'] == 1) {
                $order = D('Service_user_publish_give')->field(true)->where(array('publish_id' => $supply['order_id']))->find();
            } else {
                $order = D('Service_user_publish_buy')->field(true)->where(array('publish_id' => $supply['order_id']))->find();
                $order['start_adress_lng'] = $order['address_lng'] ? $order['address_lng'] : $order['end_adress_lng'];
                $order['start_adress_lat'] = $order['address_lat'] ? $order['address_lat'] : $order['end_adress_lat'];


                // 帮我买指定店铺
                if ($supply['order_from'] == 4) {
                    $publishInfo = M('Service_user_publish')->field('publish_id,auto_goods_id')->where(['publish_id'=>$supply['order_id']])->find();
                    if ($publishInfo['auto_goods_id']) {
                        $auto_goods_where['a.id'] = $publishInfo['auto_goods_id'];
                        $store_info = M('Service_auto_goods')->field('s.name,s.kefu_phone')->join(' as a left join '.C('DB_PREFIX').'merchant_store as s on a.store_id=s.store_id')->where($auto_goods_where)->find();
                        $return_arr['supply']['store_name'] = $store_info['name'];
                        $return_arr['supply']['store_phone'] = $store_info['kefu_phone'];
                    }
                }

            }
            if ($supply['distance'] <= 0 && $order['end_adress_lat'] && $order['end_adress_lng']) {
                $distance = round($this->ajax_distance($order['start_adress_lat'], $order['start_adress_lng'], $order['end_adress_lat'], $order['end_adress_lng']), 2);
                $supply['distance'] = $distance;//配送距离
                if ($distance > 0) {
                    $where = array('supply_id' => $supply_id);
                    D("Deliver_supply")->where($where)->data(array('distance' => $distance))->save();
                }
            }
            $order['num'] = 1;
            $order['freight_charge'] = $supply['freight_charge'];
        }elseif($supply['item'] == 4){//新版商城
            //订单信息
            $where = array();
            $where['order_id'] = $supply['order_id'];
            $order = M("Mall_order")->where($where)->find();
            $order_detail = M("Mall_order_detail")->where($where)->find();
            if (empty($order)) {
                $this->returnCode('20030203');
            }
            $order['price'] = $order['money_real'];
            $order['pay_type'] = $supply['pay_type'];
            $order['real_orderid'] = $order['order_no'];
            $order['num'] = 0;
            foreach($order_detail as $d){
                $order['num'] += $d['num'];
            }
            $pay_method = D('Config')->get_pay_method();
            $order['pay_type'] = $pay_method[$order['pay_type']]['name'];
            $order['discount_price'] = $order['price'];
            $where['goods_id'] = $order_detail['goods_id'];
            $goods = M('mall_goods')->where($where)->select();
            foreach ($goods as &$g) {
                $g['tools_money'] = 0;
            }

            //店铺信息
            $store = D()->field(true)->table(array(C('DB_PREFIX') . 'merchant_store' => 'ms', C('DB_PREFIX') . 'merchant_store_mall' => 'ml'))->where("ms.store_id=" . $order['store_id'] . " AND ms.store_id=ml.store_id")->find();
            if (!$store) {
                $this->returnCode('20030204');
            }
            if ($store['total_money'] <= 0) {
            } else {
                $store['send_money'] = $order['price'] > $store['total_money'] ? 0 : $store['send_money'];
            }
            $store['tools_money'] = 0;
            if ($store['tools_money_have'] == 1) {
                foreach ($goods as $v) {
                    $store['tools_money'] += $v['tools_price'] * $v['num'];
                }
            }
        }elseif($supply['item'] == 8){//社区团购
            //订单信息
            $where = array();
            $where['order_id'] = $supply['order_id'];
            $order = M("Village_group_order")->where($where)->find();
            $order['create_time'] = $order['add_time'];
            $order_detail = M("Village_group_order_detail")->where($where)->select();
            if (empty($order)) {
                $this->returnCode('20030203');
            }
            $order['price'] = $order['discount_price'];
            $order['pay_type'] = $supply['pay_type'];
            $order['real_orderid'] = $order['real_orderid'];
            $order['num'] = 0;
            foreach($order_detail as $d){
                $order['num'] += $d['num'];
            }
            $pay_method = D('Config')->get_pay_method();
            $order['pay_type'] = $pay_method[$order['pay_type']]['name'];
            $where = [];
            $where['goods_id'] = ['in',array_column($order_detail, 'goods_id')];
            $goods = M('Village_group_goods')->where($where)->select();
            $goodsName = array_column($order_detail,'num', 'goods_id');
            foreach ($goods as &$g) {
                $g['tools_money'] = 0;
                $g['spec'] = '';
                $g['num'] = $goodsName[$g['goods_id']];
            }
   
        }

        if ($order['create_time']) {
            $order['create_time'] = date('Y-m-d H:i', $order['create_time']);
        }
        $return_arr['order'] = $order;

        if($return_arr['supply']['appoint_time']){
            $appoint_time = strtotime($return_arr['supply']['appoint_time']);
            $return_arr['supply']['appoint_time'] = date('Y-m-d H:i', $appoint_time) . '-' . date('H:i', $appoint_time + 900);
        }else{
            $return_arr['supply']['appoint_time'] = '--';
        }

        if($supply['item'] != 4 && $supply['item'] != 8 ){
            if($supply['order_from'] == 11){
                $return_arr['goods_list'][0] = [
                    'list' => $goods,
                    'name' => '',
                ];
            }else{
                // 组合附属商品
                $goods =  D('Shop_goods')->combination_data($goods, 'spec');
                foreach ($goods as $v) {
                    $index = isset($v['packname']) && $v['packname'] ? $v['packname'] : 0;
                    if (isset($return_arr['goods_list'][$index])) {
                        $return_arr['goods_list'][$index]['list'][] = $v;
                    } else {
                        $return_arr['goods_list'][$index] = array('name' => $v['packname'], 'list' => array($v));
                    }
                }
            }
            $return_arr['goods_list'] = array_values($return_arr['goods_list']);
        }elseif($supply['item'] == 8){
            $return_arr['goods_list'] = [['name'=>'','list'=>$goods]];
        }else{
            $return_arr['goods_list'] = $goods;
        }
        $return_arr['goods'] = $goods;
        //组合拼单关系
        //$return_arr['goods_list'] = [];
        if (in_array($supply['item'], [4, 5])) {
            $return_arr['supply']['server_type'] = 1;
            $return_arr['supply']['goods_name'] = '商城商品';
            $return_arr['supply']['goods_weight'] = '未知';
            $return_arr['supply']['goods_price'] = $order['money_real'];
            $return_arr['store']['image'] = replace_file_domain($store['logo']);
            $return_arr['order']['start_adress_nickname'] = $order['username'];
            $return_arr['order']['start_adress_phone'] = $store['phone'];
            $return_arr['order']['start_adress_name'] = $supply['from_site'];
        }
        $store['phone'] = trim($store['phone']);
        
        if ($return_arr['supply']['order_from'] == '10') {
            $uids = D('Village_group_order')->where(['order_id'=>$return_arr['supply']['order_id']])->field('uid,address')->find();
            $nick = D('User')->where(['uid'=>$uids['uid']])->field('nickname,phone')->find();
            $return_arr['supply']['name'] = $nick['nickname'];
            $return_arr['supply']['phone'] = $nick['phone'];
            $store['phone'] = trim($nick['phone']);
            if(!$return_arr['supply']['aim_site']){
                $return_arr['supply']['aim_site'] = $uids['address'];
            }
        }

        $return_arr['store'] = $store;
        $return_arr['is_cancel_order'] = $now_deliver['is_cancel_order'];
        $this->returnCode('0', $return_arr);
    }

    public function foramt_arr($list, $lng = 0, $lat = 0)
    {
        foreach ($list as &$val) {
            switch ($val['pay_type']) {
                case 'offline':
                    $val['pay_method'] = 0;
                    break;
                default:
                    if ($val['paid']) {
                        $val['pay_method'] = 1;
                    } else {
                        $val['pay_method'] = 0;
                    }
                    break;
            }

            $val['pay_method'] = strval($val['pay_method']);
            $val['deliver_cash'] = strval(floatval($val['deliver_cash']));
            $val['distance'] = strval(floatval($val['distance']));
            if ($val['freight_charge'] != '-') {
                $val['freight_charge'] = strval(floatval($val['freight_charge']));
            }

            if ($val['appoint_time']) {

                if ($val['is_right_now']) {
                    $val['appoint_time'] = '<span style="color:red;">'.L_('立即送达').' </span>'.date('H:i', $val['appoint_time']) . '-' . date('H:i', $val['appoint_time'] + 900) ;
                }else{
                    $val['appoint_time'] = date('Y-m-d H:i', $val['appoint_time']) . '-' . date('H:i', $val['appoint_time'] + 900);
                }
            } else {
                $val['appoint_time'] = L_('尽快送达');
            }

            $val['order_time'] = $val['order_time'] ? date('Y-m-d H:i', $val['order_time']) : '--';
            $val['real_orderid'] = $val['real_orderid'] ? $val['real_orderid'] : $val['order_id'];

            if ($val['from_lat'] > 0 && $val['from_lnt'] > 0 && $lat > 0 && $lng > 0) {
                $store_distance = $this->ajax_distance($val['from_lat'], $val['from_lnt'], $lat, $lng);
                if ($store_distance > 0) {
                    $store_distance = 1000 * $store_distance;
                    $val['store_distance'] = getRange($store_distance);
                } else {
                    $val['store_distance'] = getRange(getDistance($val['from_lat'], $val['from_lnt'], $lat, $lng));
                }
            } else {
                $val['store_distance'] = '';
            }

            $val['image'] = $val['image'] ? explode(';', trim($val['image'], ';')) : array();
            if ($val['image']) {
                foreach ($val['image'] as &$image_v) {
                    $image_v = replace_file_domain($image_v);
                }
            }

            if ($val['change_log']) {
                $changes = explode(',', $val['change_log']);
                $uid = array_pop($changes);
                $val['change_name'] = $this->getDeliverUser($uid);
            }

            // 帮我买指定店铺
            if ($val['order_from'] == 4) {
                $publishInfo = M('Service_user_publish')->field('publish_id,auto_goods_id')->where(['publish_id'=>$val['order_id']])->find();
                if ($publishInfo['auto_goods_id']) {
                    $auto_goods_where['a.id'] = $publishInfo['auto_goods_id'];
                    $store_info = M('Service_auto_goods')->field('s.name,s.kefu_phone')->join(' as a left join '.C('DB_PREFIX').'merchant_store as s on a.store_id=s.store_id')->where($auto_goods_where)->find();
                    $val['store_name'] = $store_info['name'];
                    $val['store_phone'] = $store_info['kefu_phone'];
                }
            }
			
			//店铺名称多语言，优先读缓存
			if($val['store_id'] && C('config.open_multilingual')){
				$cache_name = 'store_name/' . $val['store_id'] . '_' . C('config.system_lang');
				$store_name = S($cache_name);
				if(!empty($store_name)){
					$store_arr = M(_view('Merchant_store'))->where(['store_id'=>$val['store_id']])->find();
					$store_name = $store_arr['name'];
					S($cache_name, $store_name, 86400);	//避免调整，缓存1天。
				}
				if($store_name){
					$val['store_name'] = $store_name;
				}
			}

            $virtual_phone_arr = array();
            $virtual_phone = explode(';', $val['virtual_phone']);
            $virtual_phone_arr = [];
            foreach ($virtual_phone as $v_phone) {
                $virtual_phone_arr[] = explode(',',$v_phone);
            }
            // 隐私号处理
            $val['virtual_phone'] = $virtual_phone_arr[0] ? $virtual_phone_arr[0]: [];
            if ($val['virtual_phone']) {
                //$val['phone'] = phone_show($val['phone']);
                $val['virtual_phone_str'] = $virtual_phone_arr[0][0].($virtual_phone_arr[0][1] ? L_('转').$virtual_phone_arr[0][1] : '');
            }
            // 隐私号码是否过期
            if ($val['virtual_phone_overtime']>time()) {
                $val['virtual_phone_overtime'] = 0;
            }else{
                $val['virtual_phone_overtime'] = 1;
            }

            if ($val['order_from'] == '10') {
                $uids = D('Village_group_order')->where(['order_id'=>$val['order_id']])->field('uid,address')->find();
                $nick = D('User')->where(['uid'=>$uids['uid']])->field('nickname,phone')->find();
                $val['name'] = $nick['nickname'];
                $val['phone'] = $nick['phone'];
                if(!$val['aim_site']){
                    $val['aim_site'] = $uids['address'];
                }
            }

            unset($val['mer_id']);
            unset($val['store_id']);
            unset($val['order_id']);
            unset($val['type']);
            unset($val['create_time']);
            unset($val['start_time']);
            unset($val['end_time']);
// 			unset($val['item']);
            unset($val['code']);
            unset($val['score']);
            // unset($val['status']);
            unset($val['money']);
            unset($val['uid']);
            unset($val['pay_type']);
            unset($val['paid']);
            unset($val['is_hide']);
            // unset($val['note']);
            unset($val['change_log']);
        }

        return $list;
    }

    private function getDeliverUser($uid)
    {
        $user = D('Deliver_user')->field(true)->where(array('uid' => $uid,'status'=>array('neq',4)))->find();
        return isset($user['name']) ? $user['name'] : '';
    }

    public function save_openid()
    {
        if (empty($this->_uid)) {
            $this->returnCode('20030101');
        }
        D('Deliver_user')->data(array('openid' => $_POST['openid']))->where(array('uid' => $this->_uid,'status'=>array('neq',4)))->save();
        $this->returnCode('0');
    }

    public function save_notice()
    {
        if (empty($this->_uid)) {
            $this->returnCode('20030101');
        }
        D('Deliver_user')->data(array('is_notice' => $_POST['is_notice']))->where(array('uid' => $this->_uid,'status'=>array('neq',4)))->save();
        $this->returnCode('0');
    }

    public function reply()
    {
        if (empty($this->_uid)) {
            $this->returnCode('20030101');
        }
        $now_deliver = $this->get_deliver_info();
        $list = D('Deliver_supply')->field('count(1) AS count, score')->where(array('uid' => $this->_uid, 'score' => array('gt', 0)))->group('score')->select();
        $return = array();
        $return['score'] = $now_deliver['average_score'];
        $return['scoreWidth'] = $now_deliver['average_score'] * 20;
        $return['good'] = 0;
        $return['bad'] = 0;
        $return['middle'] = 0;
        $return['total'] = 0;
        foreach ($list as $row) {
            if ($row['score'] < 3) {
                $return['bad'] += $row['count'];
            } elseif ($row['score'] == 3) {
                $return['middle'] += $row['count'];
            } else {
                $return['good'] += $row['count'];
            }
            $return['total'] += $row['count'];
        }
        $this->returnCode(0, $return);
    }

    public function replyList()
    {
        if (empty($this->_uid)) {
            $this->returnCode('20030101');
        }
        $status = isset($_POST['status']) ? intval($_POST['status']) : 0;

        $page = isset($_POST['page']) && $_POST['page'] ? intval($_POST['page']) : 1;
        $page = max(1, $page);

        $page_size = 5;
        $start = $page_size * ($page - 1);

        $where = 'uid=' . $this->_uid;
        $whereList = 's.uid=' . $this->_uid;
        if ($status == 0) {

        } elseif ($status == 1) {
            $where .= ' AND score>3';
            $whereList .= ' AND s.score>3';
        } elseif ($status == 2) {
            $where .= ' AND score=3';
            $whereList .= ' AND s.score=3';
        } else {
            $where .= ' AND score<3';
            $whereList .= ' AND s.score<3';
        }
        $count = D('Deliver_supply')->where($where)->count();

        $sql = "SELECT s.name, u.avatar, s.score, s.comment, s.comment_time, s.from_site";
        $sql .= " FROM " . C('DB_PREFIX') . "deliver_supply AS s ";
        $sql .= "INNER JOIN " . C('DB_PREFIX') . "user AS u ON s.userId=u.uid WHERE " . $whereList;
        $sql .= ' ORDER BY `s`.`comment_time` DESC LIMIT ' . $start . ',' . $page_size;

        $list = D()->query($sql);
        if (empty($list)) {
            $list = array();
        } else {
            $defalut = C('config.site_url') . '/static/images/user_avatar.jpg';
            foreach ($list as &$row) {
                if (empty($row['avatar'])) {
                    $row['avatar'] = $defalut;
                }
                $row['score'] = intval($row['score']);
                $row['comment_time'] = date('Y-m-d H:i', $row['comment_time']);
            }
        }
        $this->returnCode(0, array('list' => $list, 'total_page' => ceil($count / $page_size)));
    }

    public function cancelOrder()
    {
		$uid = $this->_uid;
        if (empty($uid)) {
            $this->returnCode('20020008');
        } else {
            $now_user = D('Deliver_user')->field(true)->where(array('uid' => $uid,'status'=>array('neq',4)))->find();
            if (empty($now_user)) {
                $this->returnCode('20020001');
            }
        }
        if ($now_user['is_cancel_order'] == 0) {
            $this->returnCode(1, null, L_('您没有扔回的权限'));
        }
        $supply_id = intval(I('supply_id'));
        if (!$supply_id) {
            $this->returnCode('20020011');
        }
        $supplyDB = D('Deliver_supply');
        $supply = $supplyDB->where(array("supply_id" => $supply_id, 'status' => 2, 'uid' => $uid))->find();
        if (empty($supply)) {
            $this->returnCode(1, null, L_('订单信息错误'));
        }
		
		$save_data = ['uid' => 0, 'status' => 1, 'start_time' => 0, 'offer_id' => 0, 'back_time' => time(), 'is_fetch_order' => 0];
		
		$save_data['back_log'] = $supply['back_log'] ? $supply['back_log'] . ',' . $uid : $uid;
		
        if ($supplyDB->where(array("supply_id" => $supply_id))->save($save_data)) {
            $order_id = $supply['order_id'];
            if ($supply['item'] == 2) {
                $result = D('Shop_order')->where(array('order_id' => $order_id))->data(array('order_status' => 1, 'deliver_info' => ''))->save();
                if (!$result) {
                    $this->returnCode('20020015');
                }
                D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 31, 'name' => $now_user['name'], 'phone' => $now_user['phone'], 'note' => L_('配送员【X1】放弃配送，等待下个配送员接单配送', array('X1' => $now_user['name']))));
            } elseif ($supply['item'] == 3) {
                D('Service_offer')->cancel_order($supply['order_id'], $uid);
            }
            $cancelData = array();
            $cancelData['uid'] = $now_user['uid'];
            $cancelData['username'] = $now_user['name'];
            $cancelData['userphone'] = $now_user['phone'];
            $cancelData['supply_id'] = $supply['supply_id'];
            $cancelData['store_id'] = $supply['store_id'];
            $cancelData['order_id'] = $supply['order_id'];
            $cancelData['item'] = $supply['item'];
            $cancelData['dateline'] = time();
            D('Deliver_cancel_log')->add($cancelData);
            $supply['uid'] = 0;
            $supply['status'] = 1;
            $supply['start_time'] = 0;
            $supplyDB->sendMsg($supply, $uid);
            $this->returnCode(0);
        } else {
            $this->returnCode(1, null, L_('放弃失败，稍后重试'));
        }
    }

    // 计算两个地址的距离（直线/骑行)
    public function ajax_distance($start_lat, $start_lng, $end_lat, $end_lng)
    {
        //计算配送距离
        $distance = 0;
        if (C('config.service_is_riding_distance') && C('config.baidu_map_ak')) {
            import('@.ORG.longlat');
            $longlat_class = new longlat();
            $distance = $longlat_class->getRidingDistance($start_lat, $start_lng, $end_lat, $end_lng);
            if ($distance == -1) {
                $this->returnCode('50000008');
            }
        }
        $distance || $distance = getDistance($start_lat, $start_lng, $end_lat, $end_lng);
        $distance = $distance / 1000;
        return $distance;
    }

    /**
     * 获取店铺配送员配送店铺列表
     */
    public function deliverStoreList()
    {
        if (empty($this->_uid)) {
            $this->returnCode('20030101');
        }
        $deliverUserMod = D('Deliver_user');
        $deliverUser = $deliverUserMod->where(array('uid'=>$this->_uid,'status'=>array('neq',4)))->find();
        if (empty($deliverUser)) {
            $this->returnCode('10090002');
        }
        $storeIds = !empty($deliverUser['store_ids']) ? explode(',', $deliverUser['store_ids']) : [$deliverUser['store_id']];
        $rs = M('Merchant_store')
            ->where(['store_id' => ['IN', $storeIds]])
            ->field('store_id,name')
            ->select();
        $this->returnCode(0, $rs);
    }
    /**
     * 获取用户催单列表
     */
    public function reminder_list()
    {
        if (empty($this->_uid)) {
            $this->returnCode('20030101');
        }
        $where['d.uid'] = $this->_uid;
        $where['s.reminder_status'] = 0;
        $where['s.reminder_time'] = array('gt',0);
        $reminder_list = M('Shop_order')->field('s.order_id,d.supply_id')->join('as s left join '.C('DB_PREFIX').'deliver_supply as d on s.order_id=d.order_id')->where($where)->select();
        $order_ids = array_column($reminder_list, 'order_id');
        // 状态改为已通知
        if ($order_ids) {
            M('Shop_order')->where(array('order_id'=>array('in',$order_ids)))->setField('reminder_status',1);
        }

        $this->returnCode(0, $reminder_list);
    }


    /**
     * 扫码收单
     * @author 张涛
     * @date 2020/06/23
     */
    public function scanOrderCheck()
    {
        //这里的order_id是real_orderid
        $orderId = $_POST['order_id'] ?? '';
        $shopOrder = M('Shop_order')->where(['real_orderid' => $orderId])->find();
        if (empty($shopOrder)) {
            $this->returnCode(1, null, L_('订单不存在'));
        }
        if ($shopOrder['paid'] != 1) {
            $this->returnCode(1, null, L_('订单未支付'));
        }
        if ($shopOrder['is_pick_in_store'] != 0) {
            $this->returnCode(1, null, L_('扫码收单仅支持平台配送订单'));
        }
        $orderTransfer = M('Merchant_store_shop')->where(['store_id' => $shopOrder['store_id']])->getField('order_transfer');

        $deliverSupply = M('Deliver_supply')->where(['item' => 2, 'order_id' => $shopOrder['order_id']])->find();
        if (empty($deliverSupply)) {
            $this->returnCode(1, null, L_('配送信息不存在'));
        }
        $statusDesc = [
            '0' => L_('已失效'),
            '1' => L_('等待接单'),
            '2' => L_('已接单'),
            '3' => L_('已取货'),
            '4' => L_('开始配送'),
            '5' => L_('已完成'),
            '6' => L_('已评价')
        ];
        $rs = [
            'uid' => intval($deliverSupply['uid']),
            'status' => intval($deliverSupply['status']),
            'status_desc' => $statusDesc[$deliverSupply['status']] ?? L_('未知状态'),
            'supply_id' => intval($deliverSupply['supply_id']),
            'order_transfer' => intval($orderTransfer),
            'order_id' => $shopOrder['order_id']
        ];
        $this->returnCode(0, $rs);
    }

    /**
     * 转接订单
     * @author 张涛
     * @date 2020/06/23
     */
    public function supplyTransfer()
    {
        $orderId = $_POST['order_id'] ?? 0;
        $supplyId = $_POST['supply_id'] ?? 0;
        $tag = $_POST['tag'] ?? 0;
        $shopOrder = M('Shop_order')->where(['order_id' => $orderId])->find();
        if (empty($shopOrder)) {
            $this->returnCode(1, null, L_('订单不存在'));
        }
        $supplyMod = D('Deliver_supply');
        $deliverSupply = $supplyMod->where(['supply_id' => $supplyId, 'item' => 2, 'order_id' => $orderId])->find();
        if (empty($deliverSupply)) {
            $this->returnCode(1, null, L_('配送信息不存在'));
        }
        
        $nowUser = D('Deliver_user')->field(true)->where(array('uid' => $this->_uid, 'status' => ['neq', 4]))->find();
        if($nowUser['group'] != 1){
            $this->returnCode(1, null, L_('非平台配送员禁止扫码收单'));
        }

        $action = 0;
        $grab = 1;
        $pick = 2;
        $send = 4;
        $complete = 8;
        $successMsg = '';
        if ($tag == 'pick') {
            $action = $grab + $pick;
            $successMsg = '取货成功';
        } else if ($tag == 'send') {
            $action = $grab + $pick + $send;
            $successMsg = '已设置为配送中';
        } else if ($tag == 'complete') {
            $action = $grab + $pick + $send + $complete;
            $successMsg = '配送已完成';
        } else {
            $this->returnCode(1, null, L_('未知来源'));
        }
        if ($deliverSupply['status'] == 0 || $deliverSupply['status'] == 5) {
            $deliverStatus = $deliverSupply['status'] == 0 ? L_('配送失败') : L_('已完成');
            if ($deliverSupply['uid'] == $this->_uid) {
                $errrmsg = L_('重复操作，当前状态为“X1”', ['X1' => $deliverStatus]);
            } else {
                $errrmsg = L_('操作失败，订单已被其他骑手处理，当前状态为“X1”', ['X1' => $deliverStatus]);
            }
            $this->returnCode(1, null, $errrmsg);
        }

        //0失败 1等待接单 2接单 3取货 4开始配送 5完成 6已评价 7退款失败
        $tm = time();
        $isGrab = $isPick = $isSend = $isComplete = false;
        $columns = [];
        $transferLog = [];
        if (($action & 1) && $deliverSupply['status'] < 2) {
            if ($shopOrder['order_status'] != 1) {
                $this->returnCode(1, null, L_('店铺未接单，无法配送'));
            }

            $columns['status'] = 2;
            $columns['is_fetch_order'] = 1;
            $columns['start_time'] = $tm;
            $columns['deliver_user_fee'] = $deliverSupply['freight_charge'] - ($deliverSupply['freight_charge'] * ($nowUser['take_percent'] / 100));
            $isGrab = true;
            $transferLog[] = ['uid' => $this->_uid, 'status' => 2, 'add_time' => $tm, 'supply_id' => $supplyId];
        }
        if (($action & 2) && $deliverSupply['status'] < 3) {
            $columns['status'] = 3;
            $columns['is_fetch_order'] = 1;
            $columns['real_fetch_good_time'] = date('Y-m-d H:i:s');
            $isPick = true;
            $transferLog[] = ['uid' => $this->_uid, 'status' => 3, 'add_time' => $tm, 'supply_id' => $supplyId];
        }
        if (($action & 4) && $deliverSupply['status'] < 4) {
            $columns['status'] = 4;
            $columns['end_time'] = $tm;
            $columns['real_deliver_start_time'] = date('Y-m-d H:i:s');
            $isSend = true;
            $transferLog[] = ['uid' => $this->_uid, 'status' => 4, 'add_time' => $tm, 'supply_id' => $supplyId];
        }
        if (($action & 8) && $deliverSupply['status'] < 5) {
            $columns['status'] = 5;
            $columns['end_time'] = $tm;
            $isComplete = true;
            $transferLog[] = ['uid' => $this->_uid, 'status' => 5, 'add_time' => $tm, 'supply_id' => $supplyId];
        }
        if (empty($columns)) {
            $statusDesc = [
                '0' => L_('已失效'),
                '1' => L_('等待接单'),
                '2' => L_('已接单'),
                '3' => L_('已取货'),
                '4' => L_('开始配送'),
                '5' => L_('已完成'),
                '6' => L_('已评价')
            ];
            $deliverStatus = $statusDesc[$deliverSupply['status']] ?? L_('未知状态');
            if ($deliverSupply['uid'] == $this->_uid) {
                $errrmsg = L_('重复操作，当前状态为“X1”', ['X1' => $deliverStatus]);
            } else {
                $errrmsg = L_('操作失败，订单已被其他骑手处理，当前状态为“X1”', ['X1' => $deliverStatus]);
            }
            $this->returnCode(1, null, $errrmsg);
        }
        $columns['uid'] = $this->_uid;

        $supplyMod->startTrans();
        try {
            $orderUpdateData = [];
            if ($isGrab) {
                $deliverInfo = serialize(['uid' => $nowUser['uid'], 'name' => $nowUser['name'], 'phone' => $nowUser['phone'], 'store_id' => $nowUser['store_id']]);
                $orderUpdateData['order_status'] = 2;
                $orderUpdateData['deliver_info'] = $deliverInfo;
                $log = [
                    'order_id' => $orderId,
                    'status' => 3,
                    'name' => $nowUser['name'],
                    'phone' => $nowUser['phone'],
                    'periodic_id' => $periodic_deliver['id'] ?? 0
                ];
                D('Shop_order_log')->add_log($log);
                M('Shop_order')->where(['order_id' => $orderId])->save($orderUpdateData);
            }

            if ($isPick) {
                $orderUpdateData['order_status'] = 3;
                $log = [
                    'order_id' => $orderId,
                    'status' => 4,
                    'name' => $nowUser['name'],
                    'phone' => $nowUser['phone']
                ];
                D('Shop_order_log')->add_log($log);
                M('Shop_order')->where(['order_id' => $orderId])->save($orderUpdateData);
            }

            if ($isSend) {
                $orderUpdateData['order_status'] = 4;
                $log = [
                    'order_id' => $orderId,
                    'status' => 5,
                    'name' => $nowUser['name'],
                    'phone' => $nowUser['phone']
                ];
                D('Shop_order_log')->add_log($log);
                M('Shop_order')->where(['order_id' => $orderId])->save($orderUpdateData);
            }

            if ($isComplete) {
                //配送状态更改成已完成，订单状态改成已消费
                if ($shopOrder['status'] != 1) {
                    $this->returnCode(1, null, L_('该订单的当前状态无法修改成配送完成状态'));
                }
                //订单完成赠送优惠券
                $orderDetail = M('Shop_order_detail')->where(['order_id' => $orderId])->select();
                if ($orderDetail) {
                    foreach ($orderDetail as $k => $v) {
                        if ($v['give_coupon_id']) {
                            $data['coupon_id'] = $v['give_coupon_id'];
                            $data['uid'] = $shopOrder['uid'];
                            $data['num'] = 1;
                            $data['receive_time'] = time();
                            $data['is_use'] = 0;
                            M('Card_new_coupon_hadpull')->add($data);
                        }
                    }
                }
                $orderUpdateData['order_status'] = 5;
                $orderUpdateData['status'] = 2;
                if ($shopOrder['is_pick_in_store'] == 0) {
                    //平台配送
                    if ($shopOrder['paid'] == 0 || ($shopOrder['pay_type'] == 'offline' && empty($shopOrder['third_id']))) {
                        $orderUpdateData['paid'] = $shopOrder['paid'] == 0 ? 1 : $shopOrder['paid'];
                        $orderUpdateData['pay_type'] = '';
                        $orderUpdateData['balance_pay'] = $shopOrder['balance_pay'] + $deliverSupply['deliver_cash'];
                        $shopOrder['balance_pay'] = $orderUpdateData['balance_pay'];
                    }
                } else {
                    if ($shopOrder['paid'] == 0) {
                        $orderUpdateData['paid'] = 1;
                        if (empty($shopOrder['pay_type']) && empty($shopOrder['pay_time'])) {
                            $orderUpdateData['pay_type'] = 'offline';
                        }
                    }
                }
                empty($shopOrder['pay_time']) && $orderUpdateData['pay_time'] = $tm;
                empty($shopOrder['use_time']) && $orderUpdateData['use_time'] = $tm;
                empty($shopOrder['third_id']) && $orderUpdateData['third_id'] = $shopOrder['order_id'];

                M('Shop_order')->where(['order_id' => $orderId])->save($orderUpdateData);
                if ($shopOrder['periodic_type']) {//周期购
                    //获得最近需要配送的周期
                    $periodic_deliver = M('Mall_periodic_deliver')->where(array('order_id' => $shopOrder['order_id'], 'status' => 0))->order('deliver_num asc')->find();
                    // 进入下一周期 配送状态更新
                    if ($periodic_deliver) {
                        $o_data['is_pick_in_store'] = 3;
                        $o_data['order_status'] = 0;
                        $o_data['status'] = 0;
                        $o_data['deliver_info'] = '';
                        D("Shop_order")->where(array('order_id' => $orderId))->data($o_data)->save();
                        D('Shop_order_log')->add_log(array('order_id' => $orderId, 'status' => 35, 'name' => $nowUser['name'], 'phone' => $nowUser['phone'], 'note' => '配送员确认收货'));
                    }
                } else {
                    $log = [
                        'order_id' => $orderId,
                        'status' => 6,
                        'name' => $nowUser['name'],
                        'phone' => $nowUser['phone']
                    ];
                    D('Shop_order_log')->add_log($log);
                }
            }

            $rs = $supplyMod->where(['supply_id' => $deliverSupply['supply_id']])->save($columns);
            if ($rs === false) {
                throw new \Exception('配送信息更新失败');
            }

            if ($transferLog) {
                M('Scan_order_transfer_log')->addAll($transferLog);
            }

            if ($isComplete) {
                $total = D('Deliver_supply')->where(['uid' => $nowUser['uid'], 'status' => ['gt', 4]])->count();
                D('Deliver_user')->where(['uid' => $nowUser['uid']])->save(['num' => $total]);

                $todayNum = D('Deliver_supply')->where(['uid' => $nowUser['uid'], 'status' => ['gt', 4], 'end_time' => [['gt', strtotime(date('Y-m-d') . ' 00:00:00')], ['lt', time() + 2]]])->count();
                $date = date('Ymd');
                if ($deliver_count = D('Deliver_count')->field(true)->where(array('uid' => $nowUser['uid'], 'today' => $date))->find()) {
                    D('Deliver_count')->where(array('uid' => $nowUser['uid'], 'today' => $date))->save(array('num' => $todayNum));
                } else {
                    D('Deliver_count')->add(array('uid' => $nowUser['uid'], 'today' => $date, 'num' => $todayNum));
                }

                if ($shopOrder['is_pick_in_store'] == 0) {//平台配送
                    if ($shopOrder['paid'] == 0 || ($shopOrder['pay_type'] == 'offline' && empty($shopOrder['third_id']))) {
                        D('User_money_list')->add_row($shopOrder['uid'], 1, $deliverSupply['deliver_cash'], L_('配送员模拟手动充值'));
                        D('User_money_list')->add_row($shopOrder['uid'], 2, $deliverSupply['deliver_cash'], L_('用户购买X1产品', ['X1' => $this->config['shop_alias_name']]));
                    }
                }
                D('Pick_order')->where(array('store_id' => $shopOrder['store_id'], 'order_id' => $shopOrder['order_id']))->save(array('status' => 4));
                D('Shop_order')->shop_notice($shopOrder);
                //检查含有称重商品的订单，是否需要部分退款
                if ($this->config['is_open_shop_weigh'] == 1 && $shopOrder['is_weight'] == 1) {
                    D('Shop_order')->checkWeightOrder($shopOrder);
                }

                if ($shopOrder['is_pick_in_store'] == 5) {
                    if ($shopOrder['platform'] == 1) {
                        $returnData = D('Merchant_money_list')->use_money($shopOrder['mer_id'], $shopOrder['freight_charge'], 'deliver', L_('平台配送商家由饿了么平台的订单的配送费'), $shopOrder['order_id']);
                    } elseif ($shopOrder['platform'] == 2) {
                        $returnData = D('Merchant_money_list')->use_money($shopOrder['mer_id'], $shopOrder['freight_charge'], 'deliver', L_('平台配送商家由美团外卖平台的订单的配送费'), $shopOrder['order_id']);
                    }
                }
            }
            $supplyMod->commit();
            $this->returnCode(0, $successMsg);
        } catch (\Exception $e) {
            $supplyMod->rollback();
            $this->returnCode(1, null, $e->getMessage());
        }
    }


    public function auto_set(){
        $order_id = $_POST['order_id'];
        $order =  D("Shop_order")->where(array('order_id' => $order_id))->find();

        $supply = array();
        //if (empty($order['third_id']) && $order['pay_type'] == 'offline') $order['paid'] = 0;
        //订单来源（0：wap快店，1：wap商城，2：Android，3：ios,4:小程序,5：pc快店,6:线下零售,7.饿了么，8.美团，9.扫码购）',
        $order_from=0;
        if($order['order_from']==7){
            $order_from=1;
        }elseif ($order['order_from']==8){
            $order_from=2;
        }
        $supply['order_from'] = $order_from;//订单来源(0:快店，1:饿了么，2:美团，3：帮送，4：帮买(指定地址)),5：帮买（未指定地址）
        $supply['order_id'] = $order_id;
        $supply['paid'] = $order['paid'];
        $supply['pay_time'] = intval($order['pay_time']);
        $supply['real_orderid'] = isset($order['real_orderid']) ? $order['real_orderid'] : '';
        $supply['pay_type'] = $order['pay_type'];
        $supply['money'] = $order['price'];
        $supply['deliver_cash'] = round($order['price']+$order['extra_price'] - round($order['card_price'] + $order['merchant_balance'] + $order['card_give_money'] +$order['balance_pay'] + $order['payment_money'] + $order['score_deducte'] + $order['coupon_price'], 2), 2);
        $supply['deliver_cash'] = max(0, $supply['deliver_cash']);
        $supply['store_id'] = $order['store_id'];
        $store =  D("Merchant_store")->where(array('store_id' => $order['store_id']))->find();
        $supply['store_name'] = $store['name'];
        $supply['mer_id'] = $store['mer_id'];
        $supply['from_site'] = $store['adress'];
        $supply['from_lnt'] = $store['long'];
        $supply['from_lat'] = $store['lat'];

        $supply['province_id'] = $store['province_id'];
        $supply['city_id'] = $store['city_id'];
        $supply['area_id'] = $store['area_id'];

        //目的地
        $supply['aim_site'] =  $order['address'];
        $supply['aim_lnt'] = $order['lng'];
        $supply['aim_lat'] = $order['lat'];
        $supply['name']  = $order['username'];
        $supply['phone'] = str_replace(' ', '', $order['userphone']);
        $supply['fetch_number'] = isset($order['fetch_number']) ? $order['fetch_number'] : '';

        $supply['status'] =  1;
        $supply['type'] = $order['is_pick_in_store'] != 5 ? $order['is_pick_in_store'] : 0;
        $supply['item'] = 2;//0:老快店的外卖，1：外送系统，2：新快店, 3:对接的
        $supply['create_time'] = $_SERVER['REQUEST_TIME'];
        $shop = M('Merchant_store_shop')->field(true)->where(array('store_id' => $order['store_id']))->find();
        $supply['order_out_time'] = $_SERVER['REQUEST_TIME'] + $shop['work_time'] * 60;//预计出单时间
        //$supply['start_time'] = $_SERVER['REQUEST_TIME'];
        $supply['appoint_time'] = $order['expect_use_time'];
        $supply['note'] = $order['desc'];
        $supply['is_right_now'] = intval($order['is_right_now']);//是否立即送达时间

        $supply['order_time'] = $order['pay_time'];//订单支付时间
        $delivery_fee=0;
        $delivery_fee = $delivery_fee ? $delivery_fee : ($order['freight_charge_original']?$order['freight_charge_original']:$order['freight_charge']);//原始配送费

        $supply['freight_charge'] = $delivery_fee;//配送费

        if ($order['distance']) {
            $supply['distance'] = round($order['distance']/1000, 2);//配送距离
        } else {
            $supply['distance'] = round(getDistance($order['lat'], $order['lng'], $store['lat'], $store['long'])/1000, 2);//配送距离
        }

        // 号码隐私处理
        $supply['virtual_phone'] = $order['virtual_phone']??'';//虚拟号码可以多个用;分割，主机号与分机号用,号分割
        $supply['virtual_phone_overtime'] = $order['virtual_phone_overtime']??0;//虚拟号码过期时间
        $arr=M('Deliver_supply')->data($supply)->add();
        fdump_api(['auto_set' => $order], 'shop_order/deliver',1);
        D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 2, 'name' => L_('自动接单'), 'phone' => '', 'note' => L_('店铺设置下单后自动接单')));
        $this->returnCode(0, $arr);
    }


    /**
     * 拼团成功漏接单订单批量处理
     */

    public function all_auto_set(){
        $where = [
            'mgt.status'=>1,
            'od.paid'=>1
        ];
        $list=M('Shop_order')->alias('od')->join('inner join '.C('DB_PREFIX').'mall_group_team mgt on od.team_id = mgt.id')
            ->where($where)->field("od.*")->select();
        if(!empty($list)){
            foreach ($list as $key=>$val){
                 $where1=[
                     'order_id'=>$val['order_id'],
                 ];

            $msg=M('Deliver_supply')->where($where1)->find();
            if(empty($msg)){
               $this->auto_set_by_orderid($val['order_id']);
            }
            }
        }

    }

    /**
     * 批量改变状态
     */
    public function all_auto_set_del(){
        $where = [
            'mgt.status'=>1,
            'od.paid'=>0
        ];
        $list=M('Shop_order')->alias('od')->join('inner join '.C('DB_PREFIX').'mall_group_team mgt on od.team_id = mgt.id')
            ->where($where)->field("od.*")->select();
        if(!empty($list)){
            foreach ($list as $key=>$val){
                $where1=[
                    'order_id'=>$val['order_id'],
                ];

                $msg=M('Deliver_supply')->where($where1)->find();
                if(!empty($msg)){
                    $where2['order_id'] = $val['order_id'];
                    $data['status'] = 5;
                    M('Shop_order')->where($where2)->data($data)->save();
                    D("System_order")->save_order_status('shop', $val['order_id'], 5);
                    D('Shop_order_log')->add_log(array('order_id' => $val['order_id'], 'status' => 10));
                }
            }
        }

    }

/*
    public function all_auto_save(){
        $where2 = [
            'order_id'=>array('in',[293,322,365,429,449,468,475,510,570,592,667,697,700])
        ];
        $data['status'] = 5;
        M('Shop_order')->where($where2)->data($data)->save();
    }*/

public function all_select_save(){
    $where = [
        'mgt.order_from'=>1,
        'od.order_from'=>1
    ];
    $list=M('Shop_order')->alias('od')->join('inner join '.C('DB_PREFIX').'deliver_supply mgt on od.order_id = mgt.order_id')
        ->where($where)->field("od.*")->select();
    if(!empty($list)){
        foreach ($list as $key=>$val){
            $where1=[
                'order_id'=>$val['order_id'],
            ];
            $data['order_from'] = 0;
            M('Deliver_supply')->where($where1)->data($data)->save();
        }
    }
}
    public function auto_set_by_orderid($order_id){
        $order =  D("Shop_order")->where(array('order_id' => $order_id))->find();

        $supply = array();
        $order_from=0;
        if($order['order_from']==7){
            $order_from=1;
        }elseif ($order['order_from']==8){
            $order_from=2;
        }
        //if (empty($order['third_id']) && $order['pay_type'] == 'offline') $order['paid'] = 0;
        $supply['order_from'] = $order_from;//订单来源(0:快店，1:饿了么，2:美团，3：帮送，4：帮买(指定地址)),5：帮买（未指定地址）
        $supply['order_id'] = $order_id;
        $supply['paid'] = $order['paid'];
        $supply['pay_time'] = intval($order['pay_time']);
        $supply['real_orderid'] = isset($order['real_orderid']) ? $order['real_orderid'] : '';
        $supply['pay_type'] = $order['pay_type'];
        $supply['money'] = $order['price'];
        $supply['deliver_cash'] = round($order['price']+$order['extra_price'] - round($order['card_price'] + $order['merchant_balance'] + $order['card_give_money'] +$order['balance_pay'] + $order['payment_money'] + $order['score_deducte'] + $order['coupon_price'], 2), 2);
        $supply['deliver_cash'] = max(0, $supply['deliver_cash']);
        $supply['store_id'] = $order['store_id'];
        $store =  D("Merchant_store")->where(array('store_id' => $order['store_id']))->find();
        $supply['store_name'] = $store['name'];
        $supply['mer_id'] = $store['mer_id'];
        $supply['from_site'] = $store['adress'];
        $supply['from_lnt'] = $store['long'];
        $supply['from_lat'] = $store['lat'];

        $supply['province_id'] = $store['province_id'];
        $supply['city_id'] = $store['city_id'];
        $supply['area_id'] = $store['area_id'];

        //目的地
        $supply['aim_site'] =  $order['address'];
        $supply['aim_lnt'] = $order['lng'];
        $supply['aim_lat'] = $order['lat'];
        $supply['name']  = $order['username'];
        $supply['phone'] = str_replace(' ', '', $order['userphone']);
        $supply['fetch_number'] = isset($order['fetch_number']) ? $order['fetch_number'] : '';

        $supply['status'] =  1;
        $supply['type'] = $order['is_pick_in_store'] != 5 ? $order['is_pick_in_store'] : 0;
        $supply['item'] = 2;//0:老快店的外卖，1：外送系统，2：新快店, 3:对接的
        $supply['create_time'] = $_SERVER['REQUEST_TIME'];
        $shop = M('Merchant_store_shop')->field(true)->where(array('store_id' => $order['store_id']))->find();
        $supply['order_out_time'] = $_SERVER['REQUEST_TIME'] + $shop['work_time'] * 60;//预计出单时间
        //$supply['start_time'] = $_SERVER['REQUEST_TIME'];
        $supply['appoint_time'] = $order['expect_use_time'];
        $supply['note'] = $order['desc'];
        $supply['is_right_now'] = intval($order['is_right_now']);//是否立即送达时间

        $supply['order_time'] = $order['pay_time'];//订单支付时间
        $delivery_fee=0;
        $delivery_fee = $delivery_fee ? $delivery_fee : ($order['freight_charge_original']?$order['freight_charge_original']:$order['freight_charge']);//原始配送费

        $supply['freight_charge'] = $delivery_fee;//配送费

        if ($order['distance']) {
            $supply['distance'] = round($order['distance']/1000, 2);//配送距离
        } else {
            $supply['distance'] = round(getDistance($order['lat'], $order['lng'], $store['lat'], $store['long'])/1000, 2);//配送距离
        }

        // 号码隐私处理
        $supply['virtual_phone'] = $order['virtual_phone']??'';//虚拟号码可以多个用;分割，主机号与分机号用,号分割
        $supply['virtual_phone_overtime'] = $order['virtual_phone_overtime']??0;//虚拟号码过期时间
        $arr=M('Deliver_supply')->data($supply)->add();
        fdump_api(['auto_set_by_orderid' => $order], 'shop_order/deliver',1);
        D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 2, 'name' => L_('自动接单'), 'phone' => '', 'note' => L_('店铺设置下单后自动接单')));

    }

}

?>