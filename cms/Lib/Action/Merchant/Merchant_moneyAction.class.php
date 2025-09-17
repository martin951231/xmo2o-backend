<?php

class Merchant_moneyAction extends BaseAction
{
    public $time_type = array('year' => '年', 'month' => '月', 'day' => '日');

    public function index()
    {
        $mer_id = intval($this->merchant_session['mer_id']);
        $store_list = D('Merchant_store')->where(array('mer_id' => $mer_id, 'status'=>1))->select();
        $period = false;

        if (!empty($_GET['day'])) {
            $this->assign('day', $_GET['day']);
        }
        if (isset($_GET['begin_time']) && isset($_GET['end_time']) && !empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            if ($_GET['begin_time'] > $_GET['end_time']) {
                $this->error(L_("结束时间应大于开始时间"));
            }
            $period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));
            if ($_GET['store_id']) {
                $time_condition = " (l.use_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            } else {
                $time_condition = " (use_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            }
            $condition_merchant_request['_string'] = $time_condition;
            $this->assign('begin_time', $_GET['begin_time']);
            $this->assign('end_time', $_GET['end_time']);
            $period = true;
        }
        if (isset($_GET['type']) && !empty($_GET['type'])) {
            $type = $_GET['type'];
            if ($type == 'activity') {
                $condition_merchant_request['type'] = array('in', 'coupon,yydb');
            } elseif ($type == 'store') {
                $condition_merchant_request['type'] = array('in', 'store,cash');
            }  elseif ($type == 'shop') {
                $condition_merchant_request['type'] = array('in', 'shop,shop_offline');
            } else {
                $condition_merchant_request['type'] = $type;
            }
        } else {
            $type = 'group';
            $condition_merchant_request['type'] = $type;
        }

        if ($type == 'group') {
            foreach ($store_list as $key => $value) {
                if (empty($value['have_group'])) {
                    unset($store_list[$key]);
                }
            }
        } else if ($type == 'meal') {
            foreach ($store_list as $key => $value) {
                if (empty($value['have_meal'])) {
                    unset($store_list[$key]);
                }
            }
        } else if ($type == 'shop' || $type == 'mall') {
            foreach ($store_list as $key => $value) {
                if (empty($value['have_shop'])) {
                    unset($store_list[$key]);
                }
            }
        }
        $this->assign('store_list', $store_list);

        if ($_GET['store_id'] != 0 && $type != 'wxapp' && $type != 'activity') {
            $store_id = $_GET['store_id'];
            foreach ($condition_merchant_request as $k => $v) {
                if ($k != '_string') {
                    $condition_merchant_request['l.' . $k] = $v;
                    unset($condition_merchant_request[$k]);
                }
            }
            $this->assign('store_id', $_GET['store_id']);
            $condition_merchant_request['o.store_id'] = $_GET['store_id'];
        }

        $today_zero_time = mktime(0, 0, 0, date('m', $_SERVER['REQUEST_TIME']), date('d', $_SERVER['REQUEST_TIME']), date('Y', $_SERVER['REQUEST_TIME']));

        if (empty($_GET['day'])) {
            $_GET['day'] = 2;
        }
        if ($_GET['day'] < 1) {
            $this->error(L_('日期非法！'));
        }

        if ($_GET['day'] == 1 && !$period) {
            if ($store_id) {
                $condition_merchant_request['l.use_time'] = array(array('egt', $today_zero_time), array('elt', time()));
            } else {
                $condition_merchant_request['use_time'] = array(array('egt', $today_zero_time), array('elt', time()));
            }
            if ($_GET['store_id']) {
                if (in_array($type, array('group', 'shop', 'meal', 'mall'))) {
                    if ($type == 'meal') {
                        $request_list = M('Merchant_money_list')->field('l.order_id,l.use_time,l.money,l.type,l.income,l.mer_id,o.store_id')->join('as l left join ' . C('DB_PREFIX') . 'foodshop_order o ON o.real_orderid = l.order_id ')->where($condition_merchant_request)->select();
                    } else {
                        $request_list = M('Merchant_money_list')->field('l.order_id,l.use_time,l.money,l.type,l.income,l.mer_id,o.store_id')->join('as l left join ' . C('DB_PREFIX') . $type . '_order o ON o.real_orderid = l.order_id ')->where($condition_merchant_request)->select();
                    }
                } else {
                    $request_list = M('Merchant_money_list')->field('l.order_id,l.use_time,l.money,l.type,l.income,l.mer_id,o.store_id')->join('as l left join ' . C('DB_PREFIX') . $type . '_order o ON o.order_id = l.order_id ')->where($condition_merchant_request)->select();
                }
            } else {
                $condition_merchant_request['mer_id'] = $this->merchant_session['mer_id'];
                $request_list = M('Merchant_money_list')->field(true)->where($condition_merchant_request)->select();
            }
        } else {
            if (!$period) {
                if ($_GET['day'] == 2) {
                    //本月
                    $today_zero_time = mktime(0, 0, 0, date('m'), 1, date('Y'));
                    if ($store_id) {
                        $condition_merchant_request['l.use_time'] = array(array('egt', $today_zero_time), array('elt', $_SERVER['REQUEST_TIME']));
                    } else {
                        $condition_merchant_request['use_time'] = array(array('egt', $today_zero_time), array('elt', $_SERVER['REQUEST_TIME']));
                    }
                } else {
                    if ($store_id) {
                        $condition_merchant_request['l.use_time'] = array(array('egt', $today_zero_time - (($_GET['day'] - 1) * 86400)), array('elt', $today_zero_time));
                    } else {
                        $condition_merchant_request['use_time'] = array(array('egt', $today_zero_time - (($_GET['day']) * 86400)), array('elt', time()));
                    }
                }
            }

            if ($_GET['store_id']) {
                if (in_array($type, array('group', 'shop', 'meal', 'mall'))) {
                    if ($type == 'meal') {
                        $request_list = M('Merchant_money_list')->field('l.order_id,l.use_time,l.money,l.type,l.income,l.mer_id,o.store_id')->join('as l left join ' . C('DB_PREFIX') . 'foodshop_order o ON o.real_orderid = l.order_id ')->where($condition_merchant_request)->select();

                    } else {

                        $request_list = M('Merchant_money_list')->field('l.order_id,l.use_time,l.money,l.type,l.income,l.mer_id,o.store_id')->join('as l left join ' . C('DB_PREFIX') . $type . '_order o ON o.real_orderid = l.order_id ')->where($condition_merchant_request)->select();
                    }
                } else {
                    $request_list = M('Merchant_money_list')->field('l.order_id,l.use_time,l.money,l.type,l.income,l.mer_id,o.store_id')->join('as l left join ' . C('DB_PREFIX') . $type . '_order o ON o.order_id = l.order_id ')->where($condition_merchant_request)->select();
                }

            } else {
                $condition_merchant_request['mer_id'] = $this->merchant_session['mer_id'];
                $request_list = M('Merchant_money_list')->field(true)->where($condition_merchant_request)->select();
            }
        }

        $tmp_array = array();
        if (($_GET['day'] == 1 && !$period) || ($period && ($_GET['end_time'] == $_GET['begin_time']))) {
            foreach ($request_list as $value) {
                if ($value['type'] == 'cash') {
                    $value['type'] = 'store';
                }
                if ($value['type'] == 'shop_offline') {
                    $value['type'] = 'shop';
                }
                $tmp_time = date('G', $value['use_time']);
                if (empty($tmp_array[$tmp_time][$value['type']]['count'])) {
                    $tmp_array[$tmp_time][$value['type']]['count'] = 1;
                } else {
                    $tmp_array[$tmp_time][$value['type']]['count']++;
                }
                $value['order_id'] && $tmp_array[$tmp_time][$value['type']]['order_ids'][] = $value['order_id'];
                if ($value['income'] == 1) {
                    $tmp_array[$tmp_time][$value['type']]['income'] += $value['money'];
                } else {
                    $tmp_array[$tmp_time][$value['type']]['expend'] += $value['money'];
                }
            }
        } else {
            foreach ($request_list as $value) {
                if ($value['type'] == 'cash') {
                    $value['type'] = 'store';
                }
                if ($value['type'] == 'shop_offline') {
                    $value['type'] = 'shop';
                }
                if ($_GET['day'] == 2 && !$period) {
                    $tmp_time = date('j', $value['use_time']);
                } else {
                    $tmp_time = date('ymd', $value['use_time']);
                }
                if (empty($tmp_array[$tmp_time][$value['type']]['count'])) {
                    $tmp_array[$tmp_time][$value['type']]['count'] = 1;
                } else {
                    $tmp_array[$tmp_time][$value['type']]['count']++;
                }
                $value['order_id'] && $tmp_array[$tmp_time][$value['type']]['order_ids'][] = $value['order_id'];
                if ($value['income'] == 1) {
                    $tmp_array[$tmp_time][$value['type']]['income'] += $value['money'];
                } else {
                    $tmp_array[$tmp_time][$value['type']]['expend'] += $value['money'];
                }
            }
        }

        foreach($tmp_array as $k=>$v){
            foreach($v as $k2=>$v2){
                $tmp_array[$k][$k2]['count'] = count(array_unique($v2['order_ids']));
            }
        }

        ksort($tmp_array);
        $alias_name = $this->get_alias_name();
        if (($_GET['day'] == 1 && !$period) || ($period && ($_GET['end_time'] == $_GET['begin_time']))) {
            $day = date('G', time());
            for ($i = 0; $i <= date('H', time()); $i++) {
                $pigcms_list['xAxis_arr'][] = '"' . $i . '时"';
                $time_arr[] = $i;
            }
        } else {
            if ($_GET['day'] == 2) {
                $day = date('d', time());
                for ($i = 1; $i <= $day; $i++) {
                    $pigcms_list['xAxis_arr'][] = '"' . $i . '日"';
                    $time_arr[] = $i;
                }
            } else {
                $day = $_GET['day'];
                for ($i = $day - 1; $i >= 0; $i--) {
                    $pigcms_list['xAxis_arr'][] = '"' . date('j', $today_zero_time - $i * 86400) . '日"';
                    $time_arr[] = date('ymd', $today_zero_time - $i * 86400);
                }
            }
        }

        if ($period) {
            unset($pigcms_list['xAxis_arr']);
            unset($time_arr);
            $start_day = strtotime($_GET['end_time']);

            $day = (strtotime($_GET['end_time']) - strtotime($_GET['begin_time'])) / 86400;
            if ($day == 0) {
                for ($i = 0; $i < 24; $i++) {
                    $pigcms_list['xAxis_arr'][] = '"' . $i . '时"';
                    $time_arr[] = $i;
                }
            } else {
                for ($i = $day; $i >= 0; $i--) {
                    $pigcms_list['xAxis_arr'][] = '"' . date('d', $start_day - $i * 86400) . '日"';
                    $time_arr[] = date('ymd', $start_day - $i * 86400);
                }
            }
        }
        $no_data_time = array();
        //根据时间组装数据
        foreach ($time_arr as $v) {
            if ($tmp_array[$v]) {
                foreach ($alias_name as $name) {
                    $pigcms_list[$name . '_income'][] = '"' . floatval($tmp_array[$v][$name]['income']) . '"';
                    $pigcms_list[$name . '_income_all'] += floatval($tmp_array[$v][$name]['income']);
                    $pigcms_list[$name . '_order_count'][] = '"' . intval($tmp_array[$v][$name]['count']) . '"';
                    $pigcms_list[$name . '_order_count_all'] += intval($tmp_array[$v][$name]['count']);
                }
            } else {
                if (!in_array($v, $no_data_time)) {
                    foreach ($alias_name as $name) {
                        $pigcms_list[$name . '_income'][] = '"0"';
                        $pigcms_list[$name . '_order_count'][] = '"0"';
                    }
                }
            }
        }

        //基础统计
        $pigcms_list['xAxis_txt'] = implode(',', $pigcms_list['xAxis_arr']);

        foreach ($alias_name as $name) {
            $pigcms_list[$name]['income_txt'] = implode(',', $pigcms_list[$name . '_income']);
            $pigcms_list[$name]['order_count_txt'] = implode(',', $pigcms_list[$name . '_order_count']);
        }
        if (!$period && !$_GET['day'] != '') {
            $this->assign('day', $_GET['day']);
        }
        $mer_money = M('Merchant')->field('money')->where(array('mer_id' => $mer_id))->find();
        $this->assign('all_money', $mer_money['money']);
        $this->assign('pigcms_list', $pigcms_list);
        $this->assign('alias_name', $alias_name);
        $this->assign('mer_id', $mer_id);
        $this->assign('type', $type);
        krsort($tmp_array);
        $this->assign('request_list', $tmp_array);
        
        //查询银联商户可提现金额
        $chinaums_data = D('Merchant_withdraw_chinaums')->queryChinaumsWithdraw($mer_id);
        $this->assign('chinaums_merno', $chinaums_data['chinaums_merno']);
        $this->assign('chinaums_money', $chinaums_data['money']);
        
        $this->display();
    }
	
	public function qiye_index(){
        $mer_id = intval($this->merchant_session['mer_id']);
        $merchant = D('Merchant')->field('qiye_money')->where(['mer_id' => $mer_id])->find();
        $this->assign('merchant', $merchant);

        $store_list = D('Merchant_store')->where(array('mer_id' => $mer_id))->select();
        $period = false;

        if (!empty($_GET['day'])) {
            $this->assign('day', $_GET['day']);
        }
        if (isset($_GET['begin_time']) && isset($_GET['end_time']) && !empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            if ($_GET['begin_time'] > $_GET['end_time']) {
                $this->error(L_("结束时间应大于开始时间"));
            }
            $period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));
            if ($_GET['store_id']) {
                $time_condition = " (l.use_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            } else {
                $time_condition = " (use_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            }
            $condition_merchant_request['_string'] = $time_condition;
            $this->assign('begin_time', $_GET['begin_time']);
            $this->assign('end_time', $_GET['end_time']);
            $period = true;
        }
        if (isset($_GET['type']) && !empty($_GET['type'])) {
            $type = $_GET['type'];
            if ($type == 'activity') {
                $condition_merchant_request['type'] = array('in', 'coupon,yydb');
            } elseif ($type == 'store') {
                $condition_merchant_request['type'] = array('in', 'store,cash');
            } else {
                $condition_merchant_request['type'] = $type;
            }
        } else {
            $type = 'shop';
            $condition_merchant_request['type'] = $type;
        }

        if ($type == 'group') {
            foreach ($store_list as $key => $value) {
                if (empty($value['have_group'])) {
                    unset($store_list[$key]);
                }
            }
        } else if ($type == 'meal') {
            foreach ($store_list as $key => $value) {
                if (empty($value['have_meal'])) {
                    unset($store_list[$key]);
                }
            }
        } else if ($type == 'shop' || $type == 'mall') {
            foreach ($store_list as $key => $value) {
                if (empty($value['have_shop'])) {
                    unset($store_list[$key]);
                }
            }
        }
		
        $this->assign('store_list', $store_list);

        if ($_GET['store_id'] != 0 && $type != 'wxapp' && $type != 'activity') {
            $store_id = $_GET['store_id'];
            foreach ($condition_merchant_request as $k => $v) {
                if ($k != '_string') {
                    $condition_merchant_request['l.' . $k] = $v;
                    unset($condition_merchant_request[$k]);
                }
            }
            $this->assign('store_id', $_GET['store_id']);
            $condition_merchant_request['o.store_id'] = $_GET['store_id'];
        }

        $today_zero_time = mktime(0, 0, 0, date('m', $_SERVER['REQUEST_TIME']), date('d', $_SERVER['REQUEST_TIME']), date('Y', $_SERVER['REQUEST_TIME']));

        if (empty($_GET['day'])) {
            $_GET['day'] = 2;
        }
        if ($_GET['day'] < 1) {
            $this->error(L_('日期非法！'));
        }

        if ($_GET['day'] == 1 && !$period) {
            if ($store_id) {
                $condition_merchant_request['l.use_time'] = array(array('egt', $today_zero_time), array('elt', time()));
            } else {
                $condition_merchant_request['use_time'] = array(array('egt', $today_zero_time), array('elt', time()));
            }
            if ($_GET['store_id']) {
                if (in_array($type, array('group', 'shop', 'meal', 'mall'))) {
                    if ($type == 'meal') {
                        $request_list = M('Merchant_money_list')->field('l.order_id,l.use_time,l.money,l.type,l.income,l.mer_id,o.store_id')->join('as l left join ' . C('DB_PREFIX') . 'foodshop_order o ON o.real_orderid = l.order_id ')->where($condition_merchant_request)->select();
                    } else {
                        $request_list = M('Merchant_money_list')->field('l.order_id,l.use_time,l.money,l.type,l.income,l.mer_id,o.store_id')->join('as l left join ' . C('DB_PREFIX') . $type . '_order o ON o.real_orderid = l.order_id ')->where($condition_merchant_request)->select();
                    }
                } else {
                    $request_list = M('Merchant_money_list')->field('l.order_id,l.use_time,l.money,l.type,l.income,l.mer_id,o.store_id')->join('as l left join ' . C('DB_PREFIX') . $type . '_order o ON o.order_id = l.order_id ')->where($condition_merchant_request)->select();
                }
            } else {
                $condition_merchant_request['mer_id'] = $this->merchant_session['mer_id'];
                $request_list = M('Merchant_money_list')->field(true)->where($condition_merchant_request)->select();
            }
        } else {
            if (!$period) {
                if ($_GET['day'] == 2) {
                    //本月
                    $today_zero_time = mktime(0, 0, 0, date('m'), 1, date('Y'));
                    if ($store_id) {
                        $condition_merchant_request['l.use_time'] = array(array('egt', $today_zero_time), array('elt', $_SERVER['REQUEST_TIME']));
                    } else {
                        $condition_merchant_request['use_time'] = array(array('egt', $today_zero_time), array('elt', $_SERVER['REQUEST_TIME']));
                    }
                } else {
                    if ($store_id) {
                        $condition_merchant_request['l.use_time'] = array(array('egt', $today_zero_time - (($_GET['day'] - 1) * 86400)), array('elt', $today_zero_time));
                    } else {
                        $condition_merchant_request['use_time'] = array(array('egt', $today_zero_time - (($_GET['day']) * 86400)), array('elt', time()));
                    }
                }
            }

            if ($_GET['store_id']) {
                if (in_array($type, array('group', 'shop', 'meal', 'mall'))) {
                    if ($type == 'meal') {
                        $request_list = M('Merchant_money_list')->field('l.order_id,l.use_time,l.money,l.type,l.income,l.mer_id,o.store_id')->join('as l left join ' . C('DB_PREFIX') . 'foodshop_order o ON o.real_orderid = l.order_id ')->where($condition_merchant_request)->select();

                    } else {

                        $request_list = M('Merchant_money_list')->field('l.order_id,l.use_time,l.money,l.type,l.income,l.mer_id,o.store_id')->join('as l left join ' . C('DB_PREFIX') . $type . '_order o ON o.real_orderid = l.order_id ')->where($condition_merchant_request)->select();
                    }
                } else {
                    $request_list = M('Merchant_money_list')->field('l.order_id,l.use_time,l.money,l.type,l.income,l.mer_id,o.store_id')->join('as l left join ' . C('DB_PREFIX') . $type . '_order o ON o.order_id = l.order_id ')->where($condition_merchant_request)->select();
                }

            } else {
                $condition_merchant_request['mer_id'] = $this->merchant_session['mer_id'];
                $request_list = M('Merchant_money_list')->field(true)->where($condition_merchant_request)->select();

            }
        }

        $tmp_array = array();
        if (($_GET['day'] == 1 && !$period) || ($period && ($_GET['end_time'] == $_GET['begin_time']))) {
            foreach ($request_list as $value) {
                if ($value['type'] == 'cash') {
                    $value['type'] = 'store';
                }
                $tmp_time = date('G', $value['use_time']);
                if (empty($tmp_array[$tmp_time][$value['type']]['count'])) {
                    $tmp_array[$tmp_time][$value['type']]['count'] = 1;
                } else {
                    $tmp_array[$tmp_time][$value['type']]['count']++;
                }
                if ($value['income'] == 1) {
                    $tmp_array[$tmp_time][$value['type']]['income'] += $value['money'];
                } else {
                    $tmp_array[$tmp_time][$value['type']]['expend'] += $value['money'];
                }
            }
        } else {
            foreach ($request_list as $value) {
                if ($value['type'] == 'cash') {
                    $value['type'] = 'store';
                }
                if ($_GET['day'] == 2 && !$period) {
                    $tmp_time = date('j', $value['use_time']);
                } else {
                    $tmp_time = date('ymd', $value['use_time']);
                }
                if (empty($tmp_array[$tmp_time][$value['type']]['count'])) {
                    $tmp_array[$tmp_time][$value['type']]['count'] = 1;
                } else {
                    $tmp_array[$tmp_time][$value['type']]['count']++;
                }
                if ($value['income'] == 1) {
                    $tmp_array[$tmp_time][$value['type']]['income'] += $value['money'];
                } else {
                    $tmp_array[$tmp_time][$value['type']]['expend'] += $value['money'];
                }
            }
        }

        ksort($tmp_array);
        $alias_name = $this->get_alias_name();
        if (($_GET['day'] == 1 && !$period) || ($period && ($_GET['end_time'] == $_GET['begin_time']))) {
            $day = date('G', time());
            for ($i = 0; $i <= date('H', time()); $i++) {
                $pigcms_list['xAxis_arr'][] = '"' . $i . '时"';
                $time_arr[] = $i;
            }
        } else {
            if ($_GET['day'] == 2) {
                $day = date('d', time());
                for ($i = 1; $i <= $day; $i++) {
                    $pigcms_list['xAxis_arr'][] = '"' . $i . '日"';
                    $time_arr[] = $i;
                }
            } else {
                $day = $_GET['day'];
                for ($i = $day - 1; $i >= 0; $i--) {
                    $pigcms_list['xAxis_arr'][] = '"' . date('j', $today_zero_time - $i * 86400) . '日"';
                    $time_arr[] = date('ymd', $today_zero_time - $i * 86400);
                }
            }
        }

        if ($period) {
            unset($pigcms_list['xAxis_arr']);
            unset($time_arr);
            $start_day = strtotime($_GET['end_time']);

            $day = (strtotime($_GET['end_time']) - strtotime($_GET['begin_time'])) / 86400;
            if ($day == 0) {
                for ($i = 0; $i < 24; $i++) {
                    $pigcms_list['xAxis_arr'][] = '"' . $i . '时"';
                    $time_arr[] = $i;
                }
            } else {
                for ($i = $day; $i >= 0; $i--) {
                    $pigcms_list['xAxis_arr'][] = '"' . date('d', $start_day - $i * 86400) . '日"';
                    $time_arr[] = date('ymd', $start_day - $i * 86400);
                }
            }
        }
        $no_data_time = array();
        //根据时间组装数据
        foreach ($time_arr as $v) {
            if ($tmp_array[$v]) {
                foreach ($alias_name as $name) {
                    $pigcms_list[$name . '_income'][] = '"' . floatval($tmp_array[$v][$name]['income']) . '"';
                    $pigcms_list[$name . '_income_all'] += floatval($tmp_array[$v][$name]['income']);
                    $pigcms_list[$name . '_order_count'][] = '"' . intval($tmp_array[$v][$name]['count']) . '"';
                    $pigcms_list[$name . '_order_count_all'] += intval($tmp_array[$v][$name]['count']);
                }
            } else {
                if (!in_array($v, $no_data_time)) {
                    foreach ($alias_name as $name) {
                        $pigcms_list[$name . '_income'][] = '"0"';
                        $pigcms_list[$name . '_order_count'][] = '"0"';
                    }
                }
            }
        }

        //基础统计
        $pigcms_list['xAxis_txt'] = implode(',', $pigcms_list['xAxis_arr']);

        foreach ($alias_name as $name) {
            $pigcms_list[$name]['income_txt'] = implode(',', $pigcms_list[$name . '_income']);
            $pigcms_list[$name]['order_count_txt'] = implode(',', $pigcms_list[$name . '_order_count']);
        }
        if (!$period && !$_GET['day'] != '') {
            $this->assign('day', $_GET['day']);
        }
		$all_money = $this->get_qiye_all_money($mer_id);
		$cashout   = $this->get_qiye_cashout_money($mer_id);//提现金额

        $this->assign('all_money',$all_money-$cashout);
        $this->assign('pigcms_list', $pigcms_list);
        $this->assign('alias_name', $alias_name);
        $this->assign('mer_id', $mer_id);
        $this->assign('type', $type);
        krsort($tmp_array);
        $this->assign('request_list', $tmp_array);
		
        $this->display();
	}
	
	//企业商户申请提现
	public function qiye_cashout(){
		$mer_id = intval($this->merchant_session['mer_id']);
        $now_merchant = M('Merchant')->where(array('mer_id' => $mer_id))->find();
        $can_cashout_money = $now_merchant['qiye_money'];
		$this->assign('can_cashout_money', $can_cashout_money);
        $this->assign('now_merchant', $now_merchant);
        if (M('Qiye_cashout')->where(array('mer_id' => $mer_id, 'status' => 0))->find()) {
            $this->error(L_('您有一笔提现在审核，请审核通过了再申请！'));
        }

        if (isset($_POST['money']) && $_POST['money']<1) {
            $this->error(L_('金额输入有误'));
        }
        if ($_POST['money']) {
            if ($_POST['money'] > $can_cashout_money) {
                $this->error(L_('提现金额超过了您的余额'));
            }

            if (empty($_POST['name'])) {
                $this->error(L_('真实姓名不能为空'));
            }

			$money = $_POST['money'];
			if (!is_numeric($_POST['account_type'])) {
				$this->error(L_('提现方式没有选择'));
			}
			$_POST['account_type'] += 1;
			$account = '';
			if ($_POST['account_type']==1) {
				if(empty($_POST['bank_id'])){
					$this->error(L_('请选择银行账户'));
				}else{
					$account = $_POST['bank_id'];
					echo $_POST['bank_id'];
				}     
            }
			if ($_POST['account_type']==2) {
				if(empty($_POST['alipay_account'])){
					$this->error(L_('请填写支付宝账号'));
				}else{
					$account = $_POST['alipay_account'];
				}   
            }
			
			if(empty($account)){
				$this->error(L_('提现账户不能为空！'));
			}
			$data = array();
			$data['name'] = $_POST['name'];
			$data['mer_id'] = $mer_id;
			$data['money'] = $_POST['money'];
			$data['remark'] = $_POST['remark'];
			$data['add_time'] = time();
			$data['account_type'] = $_POST['account_type'];
			$data['account'] = $account;
			$data['status'] = 0;
			$res = M('Qiye_cashout')->add($data);
			if (!$res) {
				$this->error(L_("申请失败！"));
			}else{
				$this->success(L_("申请成功，请等待审核！"), U('Merchant_money/qiye_index'));
			}
        } else {
			$where['from'] = 1;
            $where['type'] = 0;
            $where['pay_id'] = $mer_id;
            $bank_list = M('User_withdraw_account_list')->where($where)->order('is_default DESC,id DESC')->select();

            if ($this->config['city_ownpay'] && $mer_id) {
                $can_agree = false;
                if (D('City_ownpay')->field('city_id', true)->where(array('city_id' => $this->merchant_session['city_id']))->find()) {
                    $can_agree = true;
                }
                $this->assign('can_agree', $can_agree);
            }
            $this->assign('bank_list', $bank_list);
            $this->display();
        }
	}
	
	//获取企业已经提现的金额
	protected function get_qiye_cashout_money($mer_id){
		if(!$mer_id) return 0;
		$model = M('Qiye_cashout');
		$where = array();
		$where['mer_id'] = $mer_id;
		$where['status'] = 1;//提现成功
		$cashout_money = $model->where($where)->sum('`money`');
		return $cashout_money;
	}
	//获取企业商户预存款总额(未减去已提现的)
	protected function get_qiye_all_money($mer_id){
		if(!$mer_id) return 0;
		//业务模块(目前仅支持快店和到店付:“表名”=>“查询条件”)
		$module_table = array(
						'Shop_order'  => array('paid'=>1,'status'=>array('lt',4)),
						'Store_order' => array('paid'=>1,'refund'=>0),
					);
		$total_money = 0;
		foreach($module_table as $table => $where){
			$money = M($table)->where($where)->sum('`qiye_pay`');
			$total_money += $money;
		}
		return $total_money;
	}
	
	//企业申请提现列表
	public function qiye_cashout_list(){
		import('@.ORG.merchant_page');
        $mer_id = intval($this->merchant_session['mer_id']);
		$where = array();
		$where['mer_id'] = $mer_id;
		if($_GET['type']){
			$where['account_type'] = $_GET['type'];
		}
		$count = M('Qiye_cashout')->where($where)->count();
		$p = new Page($count, 20);
		$cashout_list = M('Qiye_cashout')->where($where)->order('add_time DESC')->limit($p->firstRow, $p->listRows)->select();
        $this->assign('cashout_list', $cashout_list);
       
        $this->assign('pagebar', $p->show());
        $this->display();
	}

    protected function get_alias_name()
    {
        return array('group', 'shop', 'mall', 'meal', 'appoint', 'waimai', 'store', 'weidian', 'wxapp');
    }

    protected function get_alias_c_name()
    {
        $c_name = D('Merchant_money_list')->get_alias_c_name();
        if (!$this->config['group_alias_name']) unset($c_name['group']);
        if (!$this->config['meal_alias_name']) unset($c_name['meal']);
        if (!$this->config['appoint_alias_name']) unset($c_name['appoint']);
        if (!$this->config['shop_alias_name']) unset($c_name['shop']);
        if (!$this->config['mall_alias_name']) unset($c_name['mall']);
        if (!$this->config['store_open_waimai']) unset($c_name['waimai']);
        if (!$this->config['wxapp_url']) unset($c_name['wxapp']);
        if (!$this->config['appoint_page_row']) unset($c_name['appoint']);
        if (!$this->config['is_open_weidian']) unset($c_name['weidian']);
        if (!$this->config['pay_in_store']) unset($c_name['store']);
        if (!$this->config['pay_in_store'] && !$this->config['is_cashier']) unset($c_name['cash'], $c_name['shop_offline']);
        if (!$this->config['open_sub_card']) unset($c_name['sub_card']);
        if ($this->config['single_system']) unset($c_name['activity']);
        if (!$this->config['mall_alias_name']) unset($c_name['market']);
        if (!$this->config['open_score_pay_back_mer']) unset($c_name['score_pay_back']);
        if ($this->config['single_system_type'] == 'village_group') unset($c_name['coupon']);
        if ($this->config['single_system_type'] == 'village_group') unset($c_name['spread']);
        if ($this->config['single_system_type'] == 'village_group') unset($c_name['group']);
        if (!$this->config['life_tools_scenic_alias_name']) unset($c_name['scenic']);
        if (!$this->config['life_tools_sports_alias_name']) unset($c_name['sports']);
        return $c_name;
    }
	
	protected function get_qiye_alias_c_name(){
		
	}

    public function withdraw()
    {
        if (!$this->config['company_bank_pay'] && !$this->config['company_alipay_pay'] && !$this->config['company_weixin_pay']) {
            $this->error(L_('平台没有开启提现功能！'));
        }
        $mer_id = intval($this->merchant_session['mer_id']);
        $now_merchant = M('Merchant')->where(array('mer_id' => $mer_id))->find();
        $this->assign('now_merchant', $now_merchant);
        if (M('Merchant_withdraw')->where(array('mer_id' => $mer_id, 'status' => array('in', '0,4,10,20')))->find()) {
            $this->error(L_('您有一笔提现在审核，请审核通过了再申请！'));
        }

        // 绑定的商家管理用户
        $user = D('User')->get_user($now_merchant['uid']);
        $this->assign('user', $user);

        if (isset($_POST['money']) && !$_POST['money']) {
            $this->error(L_('金额输入有误'));
        }
        if ($_POST['money']) {
            if ($_POST['money'] > $now_merchant['money']) {
                $this->error(L_('提现金额超过了您的余额'));
            }
            $money = bcmul($_POST['money'],'100',2);
            if ($_POST['money'] < $this->config['min_withdraw_money']) {
                $this->error(L_('不能低于最小商家提现金额 ') . $this->config['min_withdraw_money'] . C('config.Currency_txt') . '!');
            }
            if ($_POST['money'] < $this->config['company_least_money']) {
                $this->error(L_('不能低于对账最小提款金额 ') . $this->config['company_least_money'] . C('config.Currency_txt') . '!');
            }
            if (empty($_POST['name'])) {
                $this->error(L_('真实姓名不能为空'));
            }

            $msg = L_('申请成功，请等待审核！');
            if ($_POST['withdraw_type'] == 0 || $_POST['withdraw_type'] == 1) {
                $money = $_POST['money'];
                if (empty($_POST['name'])) {
                    $this->error(L_('真实姓名不能为空'));
                }

                if (!is_numeric($_POST['withdraw_type'])) {
                    $this->error(L_('提现方式没有选择'));
                }
                $data_companypay['type'] = 'mer';
                $data_companypay['pay_type'] = $_POST['withdraw_type'];//0 银行卡，1 支付宝 2微信 3平台余额
                $data_companypay['pay_id'] = $now_merchant['mer_id'];
                $remark = '';
                if ($_POST['withdraw_type'] == 0) {
                    $bank_id = $_POST['bank_id'];
                    $bank = M('User_withdraw_account_list')->where(array('id' => $bank_id))->find();

                    $data_companypay['account'] = $bank['account'];
                    if (empty($bank['account']) || empty($bank['account_name']) || empty($bank['remark'])) {
                        $this->error(L_('银行账号不全'));
                    }
                    $remark = L_('开户名：') . $bank['account_name'] . '，开户行：' . $bank['remark'];
					
					if($bank['province_id']){
						$province = M('Area')->where(['area_id' => $bank['province_id']])->getField('area_name');
						$city = M('Area')->where(['area_id' => $bank['city_id']])->getField('area_name');
						
						$remark.= '，' . L_('开户省市：') . $province . $city;
						$remark.= '，' . L_('开户网点：') . $bank['branch'];
					}
                    $date_mer['withdraw_type'] = 'bank_card';
                } else if ($_POST['withdraw_type'] == 1) {
                    if (empty($_POST['alipay_account'])) {
                        $this->error(L_('支付宝账号不全'));
                    }
                    $data_companypay['account'] = $_POST['alipay_account'];
                    $date_mer['withdraw_type'] = 'alipay';
                }

                $data_companypay['name'] = $now_merchant['name'];
                $data_companypay['truename'] = $_POST['name'];
                $data_companypay['remark'] = $remark;
                $data_companypay['phone'] = $now_merchant['phone'];
                if ($this->config['merchant_withdraw_fee_type'] == 0) {
                    $data_companypay['money'] = bcmul($money * ((100 - $this->config['company_pay_mer_percent']) / 100), 100);
                } else if ($this->config['merchant_withdraw_fee_type'] == 1) {
                    $data_companypay['money'] = ($money - $this->config['company_pay_mer_money']) * 100;
                }

                if ($data_companypay['money'] <= 0) {
                    $this->error(L_('您的提现金额不足以抵扣提现手续费，不能提现'));
                }
                $data_companypay['old_money'] = $money * 100;
                $data_companypay['desc'] = "商户提现对账订单 | 商户ID " . $now_merchant['mer_id'] . " | 转账 " . C('config.Currency_symbol') . $money;
                if ($this->config['company_pay_mer_percent'] > 0 && $this->config['merchant_withdraw_fee_type'] == 0) {
                    $system_take = ($data_companypay['old_money'] - $data_companypay['money']) / 100;
                    $data_companypay['desc'] .= ' | 手续费 ' . C('config.Currency_symbol') . (($data_companypay['old_money'] - $data_companypay['money']) / 100) . ' 比例 ' . $this->config['company_pay_mer_percent'] . '%';
                } else if ($this->config['merchant_withdraw_fee_type'] == 1 && $this->config['company_pay_mer_money'] > 0) {
                    $data_companypay['desc'] .= ' | 手续费 ' . C('config.Currency_symbol') . $this->config['company_pay_mer_money'];
                    $system_take = $this->config['company_pay_mer_money'];

                }
				
                $data_companypay['status'] = 0;
                $data_companypay['add_time'] = time();

				// 开启事务
				$database = D('');
				$database->startTrans();

                //商家提现记录
                $date_mer['mer_id'] = $mer_id;
                $date_mer['name'] = $_POST['name'];
                $date_mer['money'] = $data_companypay['money'];
                $date_mer['old_money'] = $data_companypay['old_money'];
                $date_mer['remark'] = $data_companypay['desc'] . ' | ' . $_POST['info'];
                $date_mer['withdraw_time'] = time();
                $date_mer['status'] = 4;
                $res = M('Merchant_withdraw')->add($date_mer);
                if (!$res) {
					$database->rollback();// 回滚事务
                    $this->error(L_("申请失败！"));
                    die;
                }
                $data_companypay['withdraw_id'] = $res;

                $result = D('Merchant_money_list')->use_money($mer_id, $money, 'withdraw', $data_companypay['desc'] . ' | ' . $_POST['info'], $data_companypay['withdraw_id'], $this->config['company_pay_mer_percent'], $system_take);
                if($result['error_code']){
					$database->rollback();// 回滚事务
                    $this->error('申请失败');die;
                }

                if(C('config.open_real_time_withdrawal')){// 开启实时提现立马到账不需审核
                    $date_mer = [];
					//请求第三方接口
					$param['money'] = $data_companypay['money'];
					if($_POST['pay_type']==0){// 银行卡
						$param['money'] = $data_companypay['money'];
						$param['account'] = $bank['account'];
						$param['account_name'] = $bank['account_name'];
						$param['bank_name'] = $bank['remark'];
							
						import('@.ORG.withdraw.Weixin');
						$weixinObj = new Weixin();
						$param['partner_trade_no'] = date('YmdHis').$this->merchant_session['mer_id'];
						$result = $weixinObj->withdrawToBank($param);

                        $date_mer['third_id'] = $result['result']['payment_no'];//第三放订单id
                        $date_mer['cmms_amt'] = $result['result']['cmms_amt'];//第三放手续费
					}else if ($_POST['pay_type']==1){//支付宝 TODO
						$param['money'] = round($data_companypay['money']/100,2);
						$param['account'] = $data_companypay['account'];
						$param['truename'] = $data_companypay['truename'];
							
						import('@.ORG.withdraw.Alipay');
						$alipayObj = new Alipay();
						$param['partner_trade_no'] = date('YmdHis').$this->merchant_session['mer_id'];
						$result = $alipayObj->withdraw($param);
						$date_mer['third_id'] = $result['pay_fund_order_id'];//第三放订单id
					}
					if($result['error']){
						$database->rollback();// 回滚事务
						$this->error_tips($result['msg']);
					}

					// 修改状态
                    $data_companypay['status'] = 1;
					$date_mer['status'] = 1;
					
                    M('Merchant_withdraw')->where(['id'=>$data_companypay['withdraw_id']])->save($date_mer);
					$msg = L_('提现成功！');
				}

                $database->commit();// 完成事务
                
                $withdraw_id = M('Withdraw_list')->add($data_companypay);
                D('Merchant_money_list')->withdraw_notice($res);
                $this->success(L_($msg), U('Merchant_money/index'));
                die;
            } elseif($_POST['withdraw_type'] == 2) {
                // 开启事务
                $database = D('');
                $database->startTrans();

                $res = D('Merchant_money_list')->withdraw($mer_id, $_POST['name'], $money, $_POST['info']);
                if ($res['error_code']) {
                    $database->rollback();// 回滚事务
                    $this->error($res['msg']);
                } else {
                    if(C('config.open_real_time_withdrawal')){// 开启实时提现立马到账不需审核
                        
                        // 用户信息
                        $user = D('User')->get_user($this->merchant_session['uid']);
                        if (empty($user)) {
                            $database->rollback();// 回滚事务
                            $this->error(L_('商家绑定微信用户不存在！'));
                        }

						//请求第三方接口
						import('@.ORG.withdraw.Weixin');
						$weixinObj = new Weixin();
                        $data_companypay = $res['data'];
						$param['money'] = $data_companypay['money'];
						$param['openid'] = $user['openid'];
                        $param['name'] = $_POST['name'];
						$param['partner_trade_no'] = date('YmdHis').$this->merchant_session['mer_id'];
						$result = $weixinObj->withdraw($param);
						if($result['error']){
							$database->rollback();// 回滚事务
							$this->error($result['msg']);
						}

						// 修改状态
                        $date_mer = [];
                        $date_mer['status'] = 1;
                        M('Merchant_withdraw')->where(['id'=>$data_companypay['id']])->save($date_mer);
						$msg = L_('提现成功！');
					}


                    $database->commit();// 完成事务
                    D('Scroll_msg')->add_msg('mer_withdraw', $now_merchant['mer_id'], L_('商家X1于X2提现成功！', array('X1' => str_replace_name($now_merchant['name']), 'X2' => date('Y-m-d H:i', $_SERVER['REQUEST_TIME']))));
                    $this->success($msg, U('Merchant_money/index'));
                }
            } elseif($_POST['withdraw_type'] == 3) {
                // 提现到平台余额不收手续费 不需审核
                $money = floatval($_POST['money']);
                $res = D('Merchant_money_list')->withdraw_system_money($mer_id,$_POST['name'],$money,$_POST['info'],$user);
                if ($res['error_code']) {
                    $this->error($res['msg']);
                } else {
                    $this->success(L_("提现成功！"), U('Merchant_money/index'));
                }
            }
        } else {
            $where['from'] = 1;
            $where['type'] = 0;
            $where['pay_id'] = $mer_id;
            $bank_list = M('User_withdraw_account_list')->where($where)->order('is_default DESC,id DESC')->select();

            if ($this->config['city_ownpay'] && $mer_id) {
                $can_agree = false;
                if (D('City_ownpay')->field('city_id', true)->where(array('city_id' => $this->merchant_session['city_id']))->find()) {
                    $can_agree = true;
                }
                $this->assign('can_agree', $can_agree);
            }
            $this->assign('bank_list', $bank_list);
            $this->display();
        }
    }

    // 验证商家是否绑定了微信
    public function scan_bind_weixin(){
        $qrcode_id = $this->merchant_session['mer_id'] + 2550000000;
        $qrcode_return = D('Recognition')->get_tmp_qrcode($qrcode_id);
        if($qrcode_return['error_code']){
            // echo '<html><head></head><body>'.$qrcode_return['msg'].'<br/><br/><font color="red">请关闭此窗口再打开重试。</font></body></html>';
            $msg = $qrcode_return['msg'];
            $this->assign('msg',$msg);
            $this->assign('sucess',0);
            $this->display();

        }else{
            $this->assign('sucess',1);
            $this->assign($qrcode_return);
            $this->display();
        }
    }

    public function withdraw_list()
    {
        import('@.ORG.merchant_page');
        $mer_id = intval($this->merchant_session['mer_id']);
        if ($_GET['type'] == 1 || !isset($_GET['type'])) {
            $where['status'] = array('neq', 4);
            $where['mer_id'] = $mer_id;
            $withdraw_to_other = M('Withdraw_list')->where(array('pay_id' => $mer_id))->select();
            foreach ($withdraw_to_other as $value) {
                $ids[] = $value['withdraw_id'];
            }
            if ($ids) {
                $where['id'] = array('not in', $ids);
            }
            $count = M('Merchant_withdraw')->where($where)->count();
            $p = new Page($count, 20);
            $withdraw_list = M('Merchant_withdraw')->where($where)->order('withdraw_time DESC')->limit($p->firstRow, $p->listRows)->select();

        } elseif ($_GET['type'] == 2) {
            $count = M('Withdraw_list')->where(array('pay_id' => $mer_id,'type'=>'mer'))->count();
            $p = new Page($count, 20);
            $withdraw_list = M('Withdraw_list')->field('id,status,pay_type,remark,desc,old_money as money,add_time as withdraw_time')->where(array('pay_id' => $mer_id,'type'=>'mer'))->order('withdraw_time DESC')->limit($p->firstRow, $p->listRows)->select();

        } elseif ($_GET['type'] == 3) {
            $where['pay_type'] = 3;
            $where['pay_id'] = $mer_id;

            $count = M('Withdraw_list')->where($where)->count();
            $p = new Page($count, 20);
            $withdraw_list = M('Withdraw_list')->field('id,status,pay_type,remark,desc,old_money as money,add_time as withdraw_time')->where($where)->order('withdraw_time DESC')->limit($p->firstRow, $p->listRows)->select();

        }elseif ($_GET['type'] == 4) {
            $where['status'] = array('neq', 4);
            $where['mer_id'] = $mer_id;
            $count = M('Merchant_withdraw_chinaums')->where($where)->count();
            $p = new Page($count, 20);
            $withdraw_list = M('Merchant_withdraw_chinaums')->where($where)->order('withdraw_time DESC')->limit($p->firstRow, $p->listRows)->select();
        }
        $where['pay_type'] = 0;
        $where['pay_id'] = $this->merchant_session['mer_id'];;
        $this->assign('withdraw_list', $withdraw_list);
        $pay_type = array(
            '0' => L_('银行'),
            '1' => L_('支付宝'),
            '3' => L_('提现至平台余额'),
        );
        $this->assign('pay_type', $pay_type);
        $this->assign('pagebar', $p->show());
        if($_GET['type'] == 4){
            $this->display('chinaumsWithdrawList');
        }else{
            $this->display();
        }
    }
    
    public function income_list()
    {
        $_POST = $_REQUEST;
        if (!empty($_POST['order_id'])) {
            if (empty($_POST['order_type'])) {
                $this->error(L_("没有选分类"));
            }
            if ($_POST['order_type'] == 'all') {
                $this->error(L_("该分类下不能填写订单id"));
            } else if ($_POST['order_type'] == 'withdraw') {
                $condition['id'] = $_POST['order_id'];
            } else {
                $condition['l.order_id'] = $_POST['order_id'];
            }
        }
        $mer_id = intval($this->merchant_session['mer_id']);
        $store_list = D('Merchant_store')->where(array('mer_id' => $mer_id, 'status' => array('neq', 4)))->select();
        $this->assign('store_list', $store_list);

        $merchant = M('Merchant')->field(true)->where(array('mer_id' => $mer_id))->find();
        if ($merchant['percent']) {
            $percent = $merchant['percent'];
        } elseif (C('config.platform_get_merchant_percent')) {
            $percent = C('config.platform_get_merchant_percent');
        }
        if (!empty($_POST['store_id'])) {
            $condition['store_id'] = $_POST['store_id'];
            $this->assign('store_id', $_POST['store_id']);
        }

        $this->assign('percent', $percent);
        $this->assign('order_id', $_POST['order_id']);
        $this->assign('order_type', $_POST['order_type']);

        if ($_POST['order_type'] == 'activity') {
            $condition['type'] = array('in', array('coupon', 'yydb'));
        }
        if ($_POST['order_type'] == 'shop') {
            $condition['type'] = array('in', array('shop', 'mall','shop_offline'));
        } elseif ($_POST['order_type'] == 'meal') {
            $condition['type'] = array('in', array('meal', 'dining'));
        } elseif ($_POST['order_type'] != 'all' && !empty($_POST['order_type'])) {
            $condition['type'] = $_POST['order_type'];
        }
        if (isset($_POST['begin_time']) && isset($_POST['end_time']) && !empty($_POST['begin_time']) && !empty($_POST['end_time'])) {
            if ($_POST['begin_time'] > $_POST['end_time']) {
                $this->error(L_("结束时间应大于开始时间"));
            }
            $period = array(strtotime($_POST['begin_time'] . " 00:00:00"), strtotime($_POST['end_time'] . " 23:59:59"));
            $time_condition = " (l.use_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            $condition['_string'] = $time_condition;
            $this->assign('begin_time', $_POST['begin_time']);
            $this->assign('end_time', $_POST['end_time']);
        }

        if(in_array($_POST['order_type'],['scenic','sports'])){
            foreach ($condition as $kk=>$vWhere){
                if(!in_array($kk,['type','_string'])){
                    $condition['l.'.$kk] = $vWhere;
                    unset($condition[$kk]);
                }
            }
        }

        if (!$_GET['page']) {
            $_SESSION['condition'] = $condition;
        }
        $res = D('Merchant_money_list')->get_income_list($mer_id, 0, $condition);
       
        //商家入驻缴费开关
		$merchant_settle_in = 0;// C('customization.merchant_settle_in');

        $shopOrderMod = D('Shop_order'); 
        foreach ($res['income_list'] as $k => $v) {
            if($v['type'] == 'type' && !$merchant_settle_in){
                continue;
            }
            $res['income_list'][$k]['type'] = $this->getType($v['type'],$v['life_tools_type']);
            if ($v['income'] == 1 && in_array($v['type'], ['shop', 'mall', 'shop_offline'])) {
                $thisOrder = $shopOrderMod->where(['real_orderid' => $v['order_id']])->find();
                if ($thisOrder) {
                    $res['income_list'][$k]['pay_money'] = getFormatNumber($thisOrder['price'] - $thisOrder['card_price'] - $thisOrder['coupon_price']);
                }
            }
        }

        $lakalaArchive = M('Lakala_qianzhangtong_archives')->where(['item_type' => 'merchant', 'item_id' => $mer_id])->find();
        $this->assign('lakalaArchive', $lakalaArchive);
        $this->assign('mer_id', $mer_id);
        $this->assign('total', $res['total']);
        $this->assign('income_total', $res['income_total']);
        $this->assign('expend_total', $res['expend_total']);
        $this->assign('total_score', $res['total_score']);
        $this->assign('recharge_total', $res['recharge_total']);
        $this->assign('system_take', $res['system_take']);
        $this->assign('system_no_take', $res['system_no_take']);
        $this->assign('income_list', $res['income_list']);
        $this->assign('alias_name', $this->get_alias_c_name());
        $this->assign('pagebar', $res['pagebar']);
        $this->assign('total_back_price', $res['total_back_price']);
        $this->assign('total_order_money', $res['total_order_money']);
        $this->assign('total_back_order_money', $res['total_back_order_money']);
        $this->assign('total_refund', $res['total_refund']);
        $this->assign('third_pay_merchant_get', $res['third_pay_merchant_get']);
        $this->assign('third_pay_platform_get', $res['third_pay_platform_get']);
        $this->display();
    }

    public function getType($type,$lifeToolsType)
    {
        if($type == 'lifetools' && $lifeToolsType == 'scenic'){
            return 'scenic';
        }
        if($type == 'lifetools' && in_array($lifeToolsType,['stadium','course'])){
            return 'sports';
        }
        if($type == 'LifeToolsAppoint'){
            return 'activeappoint';
        }
        return $type;
    }

    public function getTypeAlias()
    {
        $orderTypeAlias = [
            'shop' => '快店',
            'store' => '到店付',
            'micro' => '新大陆扫码'
        ];
        if (!$this->config['shop_alias_name']) {
            unset($orderTypeAlias['shop']);
        } else {
            $orderTypeAlias['shop'] = $this->config['shop_alias_name'];
        }
        return $orderTypeAlias;
    }

    public function qiye_income_list()
    {
        $merId = intval($this->merchant_session['mer_id']);
        $store_list = D('Merchant_store')->where(array('mer_id' => $merId, 'status' => array('neq', 4)))->select();
        $this->assign('store_list', $store_list);
        $orderType = $this->getTypeAlias();

        $search = [
            'mer_id' => $merId,
            'store_id' => !empty($_GET['store_id']) ? intval($_GET['store_id']) : 0,
            'begin_time' => !empty($_GET['begin_time']) ? strtotime($_GET['begin_time']) : 0,
            'end_time' => !empty($_GET['end_time']) ? strtotime($_GET['end_time'])+86400 : 0,
            'type'=>!empty($_GET['order_type'])?trim($_GET['order_type']):'',
            'order_id'=>!empty($_GET['order_id'])?trim($_GET['order_id']):''
        ];
        if($search['type'] == 'micro'){
            unset($search['store_id']);
        }

        $rs = D('Merchant_qiye_money_list')->get_income_list($search);

        $this->assign('orderType', $orderType);
        $this->assign('type_name', $this->get_alias_c_name());
        $this->assign('pagebar', $rs['pagebar']);
        $this->assign('income_list',$rs['income_list']);
        $this->display();
    }

    public function withdraw_order_info()
    {
        $withdraw = M('Merchant_withdraw')->field('w.*,m.system_take')->join('as w LEFT JOIN ' . C('DB_PREFIX') . 'merchant_money_list m ON m.order_id = w.id')->where(array('w.id' => $_GET['id'], 'm.type' => 'withdraw'))->find();
        $now_merchant = M('Merchant')->where(array('mer_id' => $withdraw['mer_id']))->find();
        $this->assign('withdraw', $withdraw);
        $this->assign('now_merchant', $now_merchant);
        $this->display();
    }

    public function buy_system()
    {
        $this->error(L_('该功能正在开发中'));
    }

    //商家送出元宝记录 定制的
    public function score_log()
    {
        import('@.ORG.merchant_page');
        $where['mer_id'] = intval($this->merchant_session['mer_id']);
        if (isset($_POST['begin_time']) && isset($_POST['end_time']) && !empty($_POST['begin_time']) && !empty($_POST['end_time'])) {
            if ($_POST['begin_time'] > $_POST['end_time']) {
                $this->error(L_("结束时间应大于开始时间"));
            }
            $period = array(strtotime($_POST['begin_time'] . " 00:00:00"), strtotime($_POST['end_time'] . " 23:59:59"));
            $time_condition = " (add_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            $where['_string'] = $time_condition;
            $this->assign('begin_time', $_POST['begin_time']);
            $this->assign('end_time', $_POST['end_time']);
        }
        $count = M('Merchant_score_send_log')->where($where)->count();

        $p = new Page($count, 20);
        $socre_list = M('Merchant_score_send_log')->where($where)->order('id DESC')->limit($p->firstRow, $p->listRows)->select();
        $pagebar = $p->show();
        $this->assign('score_list', $socre_list);
        $this->assign('pagebar', $pagebar);
        $this->display();
    }

    /*
     *能还未拥有的权限列表
     * */
    public function buy_merchant_service()
    {
        $mer_menus = $this->merchant_session['menus'];
        if ($this->config['buy_merchant_auth'] == 0) {
            $this->error(L_('管理员未开启该功能'));
        }
//            $menus = D('New_merchant_menu')->where(array('status' => 1,'show'=>1))->select();

//            if (empty($mer_menus)||count($mer_menus)==count($menus)) {
//                $this->error('您已经拥有所有的权限了！', U('Merchant_money/index'));
//            }

//			foreach($menus as &$value){
//				$value['name'] = str_replace(array('团购','订餐','快店','预约'),array($this->config['group_alias_name'],$this->config['meal_alias_name'],$this->config['shop_alias_name'],$this->config['appoint_alias_name']),$value['name']);
//			}

        $menu_group = D('Merchant_auth')->get_auth_group_list();

        foreach ($menu_group as &$item) {
            if ($item['is_try'] && M('Authority_group_order')->where(array('label' => $item['id'], 'mer_id' => $this->merchant_session['mer_id']))->find()) {
                $item['had_try'] = 1;
            }
        }

        $now_group = M('Authority_group')->where(array('id' => $this->merchant_session['authority_group_id']))->find();
        $this->merchant_session = D('Merchant')->get_info($this->merchant_session['mer_id']);
        if ($this->merchant_session['is_try']) {
            $this->merchant_session['merchant_end_time'] = $this->merchant_session['try_end_time'];
        }

        $this->assign('now_merchant', $this->merchant_session);
        $this->assign('group', $menu_group);
        $this->assign('time_type', $this->time_type);
        $this->display();
    }

    public function auth_gorup_order_list()
    {
        $count = M('Authority_group_order')->where(array('mer_id' => $this->merchant_session['mer_id'], 'paid' => 1))->count();
        import('@.ORG.merchant_page');
        $p = new Page($count, 20);
        $order_list = M('Authority_group_order')->field('o.* ,g.name,g.time_type,g.time_type,g.is_default,g.des,g.detail,g.percent,g.sort,g.time_type')->join('as o LEFT JOIN ' . C('DB_PREFIX') . 'authority_group g ON g.id = o.label')->where(array('mer_id' => $this->merchant_session['mer_id'], 'paid' => 1))->order('o.order_id DESC')->limit($p->firstRow, $p->listRows)->select();
        $this->assign('order_list', $order_list);
        $this->assign('pagebar', $p->show());
        $newest_order = M('Authority_group_order')->where(array('mer_id' => $this->merchant_session['mer_id'], 'paid' => 1))->order('order DESC')->find();
        $this->assign('time_type', $this->time_type);
        $this->assign('newest_order', $newest_order);
        $this->display();
    }

    /*
     * 购买权限
     * */
    public function pay_merchant_service()
    {
        if ($this->config['buy_merchant_auth'] == 0) {
            $this->error(L_('管理员未开启该功能'));
        }
        if (empty($_GET['auth_id']) && empty($_GET['menu_group'])) {
            $this->error(L_('非法访问！'));
        }
        $now_merchant = D('Merchant')->get_info($this->merchant_session['mer_id']);

        if ($_GET['auth_id']) {
            $auth_id = $_GET['auth_id'];
            $now_auth = D('New_merchant_menu')->where(array('id' => $auth_id))->find();
        } else {
            $auth_id = $_GET['menu_group'];
            $now_auth = M('Authority_group')->where(array('id' => $auth_id))->find();
            $now_auth_mer = M('Authority_group')->where(array('id' => $now_merchant['authority_group_id']))->find();
            if ($now_merchant['authority_group_id'] && $now_merchant['merchant_end_time'] > time()) {
                $this->error('您已经购买了权限了,还没有到期，不能再试用了');
            }
            if ($_GET['is_try'] == 1 && M('Authority_group_order')->where(array('label' => $now_auth['id'], 'mer_id' => $this->merchant_session['mer_id']))->find()
                || $_GET['is_try'] == 1 && $now_merchant['authority_group_id'] && $now_merchant['is_try'] == 0 && $now_merchant['merchant_end_time'] > time()) {
                $this->error(L_('您已经购买了权限了，不能再试用了'));
            }
        }

        $res = D('Merchant_auth')->get_last_money($this->merchant_session['mer_id'], $auth_id);

        $date['mer_id'] = $this->merchant_session['mer_id'];
        $date['add_time'] = time();
        $date['last_time'] = time();
        $date['money'] = $_GET['change_money'] >= 0 ? $res : $now_auth['price'];
        $date['label'] = $now_auth['id'];
        $date['is_try'] = intval($_GET['is_try']);
        $date['num'] = 1;
        $date['is_change'] = $_GET['change_money'] == -1 ? 0 : 1;
        $date['paid'] = $_GET['is_try'] == 1 ? 1 : 0;
        $order_id = M('Authority_group_order')->add($date);
        if ($_GET['is_try'] == 1) {
            $res = D('Merchant_auth')->add_auth($this->merchant_session['mer_id'], $auth_id, 0);
            if ($res['error_code']) {
                $this->error($res['msg'], U('Merchant_money/buy_merchant_service'));
            } else {
                $this->success(L_('权限购买成功'), $this->config['site_url'] . '/merchant.php?c=Index&refresh=1');
                die;
            }
        }

        redirect($this->config['site_url'] . '/merchant.php?c=Pay&a=check&order_id=' . $order_id . '&type=auth');
        if ($now_auth['price'] > $now_merchant['money']) {
            if ($_GET['menu_group']) {
                $this->error(L_('您的商家余额不足，不能购买该权限，请充值'), U('mer_recharge', array('money' => ($now_auth['price'] - $now_merchant['money']), 'auth_id' => $auth_id, 'type' => 1)));
            } else {
                $this->error(L_('您的商家余额不足，不能购买该权限，请充值'), U('mer_recharge', array('money' => ($now_auth['price'] - $now_merchant['money']), 'auth_id' => $auth_id)));
            }
        } else {
            if ($_GET['menu_group']) {
                $res = D('Merchant_auth')->add_auth($this->merchant_session['mer_id'], $auth_id, 1);
            } else {
                $res = D('Merchant_auth')->add_auth($this->merchant_session['mer_id'], $auth_id);
            }
            if ($res['error_code']) {
                $this->error($res['msg'], U('Merchant_money/buy_merchant_service'));
            } else {
                D('Merchant_money_list')->use_money($this->merchant_session['mer_id'], $now_auth['price'], 'merauth', L_('购买商家权限【X1】扣除商家余额', $now_auth['name']), $auth_id);
                $this->success($res['msg'], U('Merchant_money/buy_merchant_service'));
            }
        }
    }

    /*
     * 权限套餐详情
     * */
    public function service_detail()
    {
        $menu_group = $_GET['menu_group'];
        $menu_group_arr = M('Authority_group')->where(array('id' => $menu_group))->find();
        $menu_group = explode(',', $menu_group_arr['menus']);
        $menus = D('New_merchant_menu')->field(true)->where(array('status' => 1, 'show' => 1))->order('`sort` DESC,`id` ASC')->select();
        $list = arrayPidProcess($menus);
        $this->assign('menus', $list);
        $this->assign('menu_group_arr', $menu_group_arr);
        $this->assign('menu_group', $menu_group);
        $this->display();
    }

    /*
     * 商家余额充值
     * */
    public function mer_recharge()
    {
        //商家信息
        $now_merchant = D('Merchant')->get_info($this->merchant_session['mer_id']);
        if (empty($_GET['money'])) {
            $condition['mer_id'] = $this->merchant_session['mer_id'];
            $condition['paid'] = 1;
            $count = M('Merchant_recharge_order')->where($condition)->count();
            import("@.ORG.merchant_page");
            $p = new Page($count, 15);
            $recharge_list = M('Merchant_recharge_order')->where($condition)->order('order_id DESC')->limit($p->firstRow, $p->listRows)->select();
            foreach ($recharge_list as &$item) {
                $item['pay_type'] = D('Pay')->get_pay_name($item['pay_type'], 0, $item['paid']);
            }

            $this->assign('recharge_list', $recharge_list);
            $this->assign('page', $p->show());
            $this->assign('now_merchant', $now_merchant);
            $this->display();
        } else {
            $money = floatval($_GET['money']);
            if (empty($money) || $money < 0 || !is_numeric($money)) {
                $this->error(L_('请输入正确的充值金额'));
            }

            $data_mer_recharge_order['mer_id'] = $now_merchant['mer_id'];
            $data_mer_recharge_order['money'] = $money;
            $data_mer_recharge_order['add_time'] = $_SERVER['REQUEST_TIME'];
            $data_mer_recharge_order['last_time'] = $_SERVER['REQUEST_TIME'];
            if ($_GET['auth_id']) {
                if ($_GET['type']) {
                    $data_mer_recharge_order['label'] = 'web_merauthgroup_' . $_GET['auth_id'];
                } else {

                    $data_mer_recharge_order['label'] = 'web_merauth_' . $_GET['auth_id'];
                }
            }
            if ($order_id = M('Merchant_recharge_order')->data($data_mer_recharge_order)->add()) {
                redirect(U('Pay/check', array('order_id' => $order_id, 'type' => 'merrecharge')));
            } else {
                $this->error(L_('订单创建失败，请重试。'));
            }
        }
    }


    public function create_bank_account()
    {
        if (IS_POST) {
            if (empty($_POST['account'])) {
                $this->error(L_('银行卡号不能为空'));
            }
            if (empty($_POST['account_name'])) {
                $this->error(L_('持卡人姓名不能为空'));
            }
            $data_companypay['from'] = 1;
            $data_companypay['type'] = 0;
            $data_companypay['pay_id'] = $this->merchant_session['mer_id'];
            $data_companypay['account_name'] = $_POST['account_name'];
            $data_companypay['account'] = $_POST['account'];
            $data_companypay['is_default'] = $_POST['is_default'];
            $data_companypay['add_time'] = $_SERVER['REQUEST_TIME'];
            $data_companypay['remark'] = $_POST['remark'];
            $data_companypay['province_id'] = $_POST['province_id'];
            $data_companypay['city_id'] = $_POST['city_id'];
            $data_companypay['branch'] = $_POST['branch'];

            if ($_POST['bank_id']) {
                $url = U('bank_list');
                $result = M('User_withdraw_account_list')->where(array('id' => $_POST['bank_id']))->save($data_companypay);
                if ($result) {
                    $aid = $_POST['bank_id'];
                } else {
                    $aid = false;
                }
            } else {
                $aid = M('User_withdraw_account_list')->add($data_companypay);
                $url = U('withdraw');
            }

            if ($aid) {
                if ($data_companypay['is_default']) {
                    $where['from'] = 1;
                    $where['type'] = 0;
                    $where['pay_id'] = $this->merchant_session['mer_id'];
                    $where['id'] = array('neq', $aid);
                    M('User_withdraw_account_list')->where($where)->setField('is_default', 0);

                }
                $this->success(L_('保存成功'), $url);
            } else {
                $this->error(L_('保存失败'));
            }
        } else {
            if ($_GET['bank_id']) {
                $where['id'] = $_GET['bank_id'];
                $bank = M('User_withdraw_account_list')->where($where)->find();
                $this->assign('bank', $bank);
            }
            $this->display();
        }
    }

    public function bank_list()
    {
        $where['from'] = 1;
        $where['type'] = 0;
        $where['pay_id'] = $this->merchant_session['mer_id'];
        $bank_list = M('User_withdraw_account_list')->where($where)->order('is_default DESC,id DESC')->select();
        $this->assign('bank_list', $bank_list);
        M('User_withdraw_account_list')->where($where)->select();
        $this->display();

    }

    public function get_bank_name()
    {
        $card_number = $_POST['card_number'];
        require_once APP_PATH . 'Lib/ORG/BankList.class.php';
        if ($res = $this->bankInfo($card_number, $bankList)) {
            $this->success($res);
        } else {
            $this->error(L_('没有查询到相关银行，请手动填写'));
        }
    }

    public function delete_bank_account()
    {
        $where['id'] = $_GET['bank_id'];
        if ($this->config['open_allinyun'] == 1) {
            $bank = M('User_withdraw_account_list')->where($where)->find();
            import('@.ORG.AccountDeposit.AccountDeposit');
            $deposit = new AccountDeposit('Allinyun');
            $allyun = $deposit->getDeposit();
            $allinyun = M('Merchant_allinyun_info')->where(array('mer_id' => $this->merchant_session['mer_id']))->find();
            $allyun->setUser($allinyun);
            $param['cardNo'] = $bank['account'];
            $res = $allyun->unbindBankCard($param);
            if ($res['status'] == 'error') {
                $this->error($res['message']);
            }

        }
        M('User_withdraw_account_list')->where($where)->delete();
        $this->success(
            L_('删除成功')
        );
    }

    function bankInfo($card, $bankList)
    {
        $card_8 = substr($card, 0, 8);
        if (isset($bankList[$card_8])) {
            return $bankList[$card_8];
        }
        $card_6 = substr($card, 0, 6);
        if (isset($bankList[$card_6])) {
            return $bankList[$card_6];

        }
        $card_5 = substr($card, 0, 5);
        if (isset($bankList[$card_5])) {
            return $bankList[$card_5];

        }
        $card_4 = substr($card, 0, 4);
        if (isset($bankList[$card_4])) {
            return $bankList[$card_4];

        }

        return null;
    }

    public function discount_detail()
    {
        $where = 'l.mer_id=' . $this->merchant_session['mer_id'];
        $condition = 'mer_id=' . $this->merchant_session['mer_id'];
        if (isset($_POST['begin_time']) && isset($_POST['end_time']) && !empty($_POST['begin_time']) && !empty($_POST['end_time'])) {
            if ($_POST['begin_time'] > $_POST['end_time']) {
                $this->error_tips(L_("结束时间应大于开始时间"));
            }
            $period = array(strtotime($_POST['begin_time'] . " 00:00:00"), strtotime($_POST['end_time'] . " 23:59:59"));
            $where .= " AND (use_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            $condition .= " AND (use_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";

            $this->assign('begin_time', $_POST['begin_time']);
            $this->assign('end_time', $_POST['end_time']);
        }
        if (isset($_POST['order_id']) && !empty($_POST['order_id'])) {
            $where .= " AND real_orderid='" . htmlspecialchars(trim($_POST['order_id'])) . "'";
            $condition .= " AND real_orderid='" . htmlspecialchars(trim($_POST['order_id'])) . "'";
        }
        $this->assign('order_id', $_POST['order_id']);
        $count = D('Merchant_discount_log')->where($condition)->count();
        import('@.ORG.merchant_page');
        $p = new Page($count, 15);
        $sql = "SELECT `s`.`name`, `l`.* FROM " . C('DB_PREFIX') . "merchant_store as s INNER JOIN " . C('DB_PREFIX') . "merchant_discount_log as l ON `s`.`store_id`=`l`.`store_id` ";
        $sql .= "WHERE $where ORDER BY `l`.`id` DESC LIMIT {$p->firstRow},{$p->listRows}";
        $order_list = D()->query($sql);

        $this->assign('order_list', $order_list);
        $this->assign('pagebar', $p->show());
        $merchant = D('Merchant')->field('discount_price, discount_num')->where(array('mer_id' => $this->merchant_session['mer_id']))->find();
        $this->assign('now_merchant', $merchant);
        $this->display();
    }

    public function exportDiscount()
    {
        set_time_limit(0);

        $where = 'l.mer_id=' . $this->merchant_session['mer_id'];
        $condition = 'mer_id=' . $this->merchant_session['mer_id'];
        if (isset($_REQUEST['begin_time']) && isset($_REQUEST['end_time']) && !empty($_REQUEST['begin_time']) && !empty($_REQUEST['end_time'])) {
            if ($_REQUEST['begin_time'] > $_REQUEST['end_time']) {
                $this->error_tips(L_("结束时间应大于开始时间"));
            }
            $period = array(strtotime($_REQUEST['begin_time'] . " 00:00:00"), strtotime($_REQUEST['end_time'] . " 23:59:59"));
            $where .= " AND (use_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            $condition .= " AND (use_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";

        }
        if (isset($_REQUEST['order_id']) && !empty($_REQUEST['order_id'])) {
            $where .= " AND real_orderid='" . htmlspecialchars(trim($_REQUEST['order_id'])) . "'";
            $condition .= " AND real_orderid='" . htmlspecialchars(trim($_REQUEST['order_id'])) . "'";
        }

        require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
        $title = L_('商家统一折扣订单列表');
        $objExcel = new PHPExcel();
        $objProps = $objExcel->getProperties();
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        $count = D('Merchant_discount_log')->where($condition)->count();
        $length = ceil($count / 1000);
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle(L_('第X1个一千配送订单', ($i + 1)));
            $objActSheet = $objExcel->getActiveSheet();
            $objActSheet->setCellValue('A1', L_('店铺名称'));
            $objActSheet->setCellValue('B1', L_('订单号'));
            $objActSheet->setCellValue('C1', L_('订单总额'));
            $objActSheet->setCellValue('D1', L_('商家统一折扣比例'));
            $objActSheet->setCellValue('E1', L_('商家统一折扣优惠总金额'));
            $objActSheet->setCellValue('F1', L_('支付时间'));

            $sql = "SELECT `s`.`name`, `l`.* FROM " . C('DB_PREFIX') . "merchant_store as s INNER JOIN " . C('DB_PREFIX') . "merchant_discount_log as l ON `s`.`store_id`=`l`.`store_id` ";
            $sql .= "WHERE $where ORDER BY `l`.`id` DESC LIMIT " . $i * 1000 . ', 1000';
            $order_list = D()->query($sql);
            if (!empty($order_list)) {
                $index = 2;
                foreach ($order_list as $value) {
                    $value['confirm_time'] = $value['create_time'] && $value['order_time'] ? intval(($value['create_time'] - $value['order_time']) / 60) . L_('分钟') : '-';
                    $value['grab_time'] = $value['start_time'] ? intval(($value['start_time'] - $value['create_time']) / 60) . L_('分钟') : '-';
                    $value['deliver_use_time'] = $value['end_time'] ? intval(($value['end_time'] - $value['start_time']) / 60) . L_('分钟') : '-';

                    $objActSheet->setCellValueExplicit('A' . $index, $value['name']);
                    $objActSheet->setCellValueExplicit('B' . $index, $value['real_orderid'] . ' ');
                    $objActSheet->setCellValueExplicit('C' . $index, $value['price']);
                    $objActSheet->setCellValueExplicit('D' . $index, floatval($value['discount'] / 10) . L_('折'));
                    $objActSheet->setCellValueExplicit('E' . $index, floatval($value['discount_money']) . ' ');
                    $objActSheet->setCellValueExplicit('F' . $index, date('Y-m-d H:i:s', $value['dateline']));

                    $index++;
                }
            }
            sleep(2);
        }
        //输出
        $objWriter = new PHPExcel_Writer_Excel5($objExcel);
        ob_end_clean();
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");
        header('Content-Disposition:attachment;filename="' . $title . '_' . date("Y-m-d h:i:s", time()) . '.xls"');
        header("Content-Transfer-Encoding:binary");
        $objWriter->save('php://output');
        exit();
    }

    public function check_before_buy()
    {
        if ($_POST['is_try']) {
            $this->success(-1);
            die;
        }

        $res = D('Merchant_auth')->get_last_money($this->merchant_session['mer_id'], $_POST['auth_id'], isset($_POST['num']) ? $_POST['num'] : 1);
        $this->success($res);
    }

    // 商家联盟统计
    public function card_union_statistics()
    {
        $mer_id = intval($this->merchant_session['mer_id']);

        $merchant = M('Merchant')->field(true)->where(array('mer_id' => $mer_id))->find();
        if ($merchant['percent']) {
            $percent = $merchant['percent'];
        } elseif (C('config.platform_get_merchant_percent')) {
            $percent = C('config.platform_get_merchant_percent');
        }

        $this->assign('percent', $percent);
        $this->assign('order_type', $_GET['order_type']);

        if ($_GET['order_type'] == 'shop') {
            $condition['type'] = array('in', array('shop', 'mall'));
        } elseif ($_GET['order_type'] != 'all' && !empty($_GET['order_type'])) {
            $condition['type'] = $_GET['order_type'];
        }

        if (isset($_GET['begin_time']) && isset($_GET['end_time']) && !empty($_GET['begin_time']) && !empty($_GET['end_time'])) {
            if ($_GET['begin_time'] > $_GET['end_time']) {
                $this->error(L_("结束时间应大于开始时间"));
            }
            $period = array(strtotime($_GET['begin_time'] . " 00:00:00"), strtotime($_GET['end_time'] . " 23:59:59"));
            $time_condition = " (use_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            $condition['_string'] = $time_condition;
            $this->assign('begin_time', $_GET['begin_time']);
            $this->assign('end_time', $_GET['end_time']);
        }

        if (!$_GET['page']) {
            $_SESSION['condition'] = $condition;
        }

        $database_merchant_card_union = D('Merchant_card_union');
        if ($_GET['union_type']==1) {
            // 其他商家待兑现给我的统计
            $condition_merchant['union_mer_id'] = $this->merchant_session['mer_id'];
            $condition_merchant['is_del'] = 0;
            $merchant_card_union = $database_merchant_card_union->where($condition_merchant)->select();
            $mer_ids = array_column($merchant_card_union, 'mer_id');
            $condition['union_mer_id'] = $this->merchant_session['mer_id'];
            $mer_list = $_GET['mer_list'];
            if ($mer_list && $mer_list != -1) {
                $mer_list = explode(',',$mer_list);
                $mer_list = $mer_list ? $mer_list : array();                
                $mer_ids = array_intersect($mer_list, $mer_ids);
            }
            $mer_id = implode(',', $mer_ids);
        }else{
            // 我待兑现给其他商家的统计 
            $condition_merchant['mer_id'] = $this->merchant_session['mer_id'];
            $condition_merchant['is_del'] = 0;
            $merchant_card_union = $database_merchant_card_union->where($condition_merchant)->select();
            $mer_ids = array_column($merchant_card_union, 'union_mer_id');
            $condition['union_mer_id'] = $mer_ids;

            $mer_list = $_GET['mer_list'];
            if ($mer_list && $mer_list != -1) {
                $mer_list = explode(',',$mer_list);
                $mer_list = $mer_list ? $mer_list : array();
                $condition['union_mer_id'] = array_intersect($mer_list, $condition['union_mer_id']);
            }
        }
        $condition['union_type'] = $_GET['union_type'];
        $res = D('Merchant_money_list')->get_union_money_list($mer_id, 0, $condition);
        $this->assign('mer_id', $mer_id);
        $this->assign('income_sum', $res['income_sum']);
        $this->assign('income_list', $res['income_list']);
        $get_alias_c_name = array(
            'all'=>L_('选择分类'),
            'shop'=>C('config.shop_alias_name'),
            'meal'=>C('config.meal_alias_name'),
        );
        $this->assign('alias_name', $get_alias_c_name);
        $this->assign('pagebar', $res['pagebar']);
        $this->display();
    }

    public  function mer_list()
    {
        $database_merchant_card_union = D('Merchant_card_union');
        if ($_GET['union_type']==1) {
            // 其他商家待兑现给我的统计
            $condition_merchant['union_mer_id'] = $this->merchant_session['mer_id'];
            $condition_merchant['is_del'] = 0;
            $merchant_card_union = $database_merchant_card_union->where($condition_merchant)->select();
            $mer_ids = array_column($merchant_card_union, 'mer_id');
        }else{
            // 我待兑现给其他商家的统计 
            $condition_merchant['mer_id'] = $this->merchant_session['mer_id'];
            $condition_merchant['is_del'] = 0;
            $merchant_card_union = $database_merchant_card_union->where($condition_merchant)->select();
            $mer_ids = array_column($merchant_card_union, 'union_mer_id');
        }
       
        $_POST['name'] && $where['name'] = array('like', "%{$_POST['name']}%");
        $where['mer_id'] = array('in',$mer_ids); 
        $count = M('Merchant')->where($where)->count();

        $Page = new Page($count, 5);
        $mer_list = M('Merchant')->where($where)->field('mer_id,name')->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $get_list = explode(',', $_GET['mer_list']);

        if ($get_list){
            foreach ($mer_list as &$item) {
                if(in_array($item['mer_id'],$get_list)){
                    $item['select'] =1;
                }
            }
        }
        $this->assign('page', $Page->show());
        $this->assign('mer_list',$mer_list);
        $this->display();
    }

    public function export(){
        $param = $_GET;
        $param['type'] = 'mer_union';
        $param['rand_number'] = $_SERVER['REQUEST_TIME'];
        $param['merchant_session']['mer_id'] = $this->merchant_session['mer_id'];
        if($res = D('Order')->order_export($param)){
            echo json_encode(array('error_code'=>0,'msg'=>L_('添加导出计划成功'),'file_name'=>$res['file_name'],'export_id'=>$res['export_id'],'rand_number'=> $param['rand_number']));
        }else{
            echo json_encode(array('error_code'=>1,'msg'=>L_('导出失败')));
        }
    }

    // 商家余额银联申请提现
    public function applyChinaumsWithdraw()
    {
        $mer_id = $this->merchant_session['mer_id'];
        if(IS_POST){
            $_POST['mer_id'] = $mer_id;
            $res = D('Merchant_withdraw_chinaums')->applyWithdraw($_POST);
            if($res['code']!=0){
                $this->error(L_($res['msg']));
            }
            $this->success(L_('申请成功'));
        }
        //查询银联商户可提现金额
        $chinaums_data = D('Merchant_withdraw_chinaums')->queryChinaumsWithdraw($mer_id);
        $this->assign('chinaums_money', $chinaums_data['money']);
        $this->display();
    }
}
	