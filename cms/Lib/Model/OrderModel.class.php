<?php

class OrderModel extends Model
{
    public function __construct()
    {
    }


    public function get_mer_bill($condition_merchant, $page_count = 15)
    {
        $database_merchant = M('Merchant');
        foreach ($condition_merchant as $k => $v) {
            $condition_merchant['m.' . $k] = $v;
            unset($condition_merchant[$k]);
        }

        $count_merchant = $database_merchant->where($condition_merchant)->count();
        import('@.ORG.system_page');
        $p = new Page($count_merchant, $page_count);
        $merchant_list = $database_merchant->join('as m left join ' . C('DB_PREFIX') . 'bill_time t ON m.mer_id = t.merid ')->field(true)->where($condition_merchant)->order('m.bill_time ASC ,m.mer_id DESC')->limit($p->firstRow . ',' . $p->listRows)->select();
        foreach ($merchant_list as $key => $val) {
            $val['meal_bill_info'] && $merchant_list[$key]['bill_info']['meal'] = unserialize($val['meal_bill_info']);
            $val['group_bill_info'] && $merchant_list[$key]['bill_info']['group'] = unserialize($val['group_bill_info']);
            $val['appoint_bill_info'] && $merchant_list[$key]['bill_info']['appoint'] = unserialize($val['appoint_bill_info']);
            $val['store_bill_info'] && $merchant_list[$key]['bill_info']['store'] = unserialize($val['store_bill_info']);
            $val['weidian_bill_info'] && $merchant_list[$key]['bill_info']['weidian'] = unserialize($val['weidian_bill_info']);
            $val['waimai_bill_info'] && $merchant_list[$key]['bill_info']['waimai'] = unserialize($val['waimai_bill_info']);
            $val['wxapp_bill_info'] && $merchant_list[$key]['bill_info']['wxapp'] = unserialize($val['wxapp_bill_info']);
            $val['shop_bill_info'] && $merchant_list[$key]['bill_info']['shop'] = unserialize($val['shop_bill_info']);
        }
        $pagebar = $p->show();
        return array('merchant_list' => $merchant_list, 'pagebar' => $pagebar);
    }

    public function get_mer_billed($condition_merchant, $page_count = 15)
    {
        $database_merchant = M('Merchant m');
        foreach ($condition_merchant as $k => $v) {
            $condition_merchant['m.' . $k] = $v;
            unset($condition_merchant[$k]);
        }
        $count_merchant = M('Bill_time')->count();
        import('@.ORG.system_page');
        $p = new Page($count_merchant, $page_count);
        $merchant_list = $database_merchant->join('RIGHT JOIN ' . C('DB_PREFIX') . 'bill_time t ON m.mer_id = t.merid ')->field(true)->where($condition_merchant)->order('t.update_time DESC')->limit($p->firstRow . ',' . $p->listRows)->select();

        $pagebar = $p->show();
        foreach ($merchant_list as $key => $val) {
            $val['meal_bill_info'] && $merchant_list[$key]['bill_info']['meal'] = unserialize($val['meal_bill_info']);
            $val['group_bill_info'] && $merchant_list[$key]['bill_info']['group'] = unserialize($val['group_bill_info']);
            $val['appoint_bill_info'] && $merchant_list[$key]['bill_info']['appoint'] = unserialize($val['appoint_bill_info']);
            $val['store_bill_info'] && $merchant_list[$key]['bill_info']['store'] = unserialize($val['store_bill_info']);
            $val['weidian_bill_info'] && $merchant_list[$key]['bill_info']['weidian'] = unserialize($val['weidian_bill_info']);
            $val['waimai_bill_info'] && $merchant_list[$key]['bill_info']['waimai'] = unserialize($val['waimai_bill_info']);
            $val['wxapp_bill_info'] && $merchant_list[$key]['bill_info']['wxapp'] = unserialize($val['wxapp_bill_info']);
            $val['shop_bill_info'] && $merchant_list[$key]['bill_info']['shop'] = unserialize($val['shop_bill_info']);
            $bill_info_id = array(
                $merchant_list[$key]['bill_info']['meal']['bill_id'],
                $merchant_list[$key]['bill_info']['group']['bill_id'],
                $merchant_list[$key]['bill_info']['appoint']['bill_id'],
                $merchant_list[$key]['bill_info']['store']['bill_id'],
                $merchant_list[$key]['bill_info']['weidian']['bill_id'],
                $merchant_list[$key]['bill_info']['waimai']['bill_id'],
                $merchant_list[$key]['bill_info']['wxapp']['bill_id'],
                $merchant_list[$key]['bill_info']['shop']['bill_id']
            );
            $tmp = M('Bill_info')->where(array('id' => array('in', $bill_info_id)))->select();
            foreach ($tmp as $k => $v) {
                $merchant_list[$key]['bill_info'][$v['name']]['money'] = $v['money'];
            }
        }
        return array('merchant_list' => $merchant_list, 'pagebar' => $pagebar);
    }

    public function get_order_by_mer_id($mer_id, $type = 'meal', $is_system = false, $time, $is_pay_bill)
    {
        if ($is_system) {
            import('@.ORG.system_page');
        } else {
            import('@.ORG.merchant_page');
        }
        $time_condition = '';
        if ($time) {
            $time = unserialize($time);
            if ($time['period']) {
                if (is_array($time['period'])) {
                    $time_condition = " AND (pay_time BETWEEN " . $time['period'][0] . ' AND ' . $time['period'][1] . ")";
                } else {
                    $time_condition = " AND pay_time=" . $time['period'];
                }
            } elseif ($time['month']) {
                $time_condition = ' AND year(FROM_UNIXTIME(pay_time))=' . $time['year'] . ' AND month(FROM_UNIXTIME(pay_time))=' . $time['month'];
            } elseif ($time['year']) {
                $time_condition = ' AND year(FROM_UNIXTIME(pay_time))=' . $time['year'];
            }

        }

        if ($is_pay_bill == 1) {
            $tmp_condition = " AND is_pay_bill in (1,2)";
        } else if ($is_pay_bill == 2) {
            $tmp_condition = " AND is_pay_bill=0";
        }
        $time_condition .= $tmp_condition;
        $mode = new Model();
        switch ($type) {
            case 'meal':
                $db = D('Meal_order');
                if (empty($time_condition)) {
                    $count = $db->where(array('mer_id' => $mer_id, 'paid' => 1, 'is_own' => 0, 'pay_type' => array('NEQ', 'offline'), 'status' => array('in', '1, 2')))->count();
                } else {
                    $count = $db->where(array('mer_id' => $mer_id, 'paid' => 1, 'is_own' => 0, 'pay_type' => array('NEQ', 'offline'), 'status' => array('in', '1, 2'), '_string' => substr($time_condition, 5)))->count();
                }
                $p = new Page($count, 20);
                $sql = "SELECT order_id, info as order_name, uid, mer_id, store_id, phone, total, (balance_pay+payment_money) as price, price as order_price, dateline, paid, status, pay_type, pay_time, third_id, is_mobile_pay, balance_pay, payment_money,coupon_price,score_deducte, card_id, merchant_balance, is_pay_bill,bill_time FROM " . C('DB_PREFIX') . "meal_order WHERE mer_id={$mer_id} AND paid=1 AND is_own=0 AND status in (1,2) AND (pay_type<>'offline' OR balance_pay<>'0.00') AND (payment_money<>'0.00' OR balance_pay<>'0.00'  OR score_deducte <> '0.00' OR coupon_price <> '0.00') " . $time_condition;
                $sql .= " ORDER BY is_pay_bill, dateline DESC LIMIT {$p->firstRow}, {$p->listRows}";

                $order_list = $mode->query($sql);
                $total_list = $db->field('sum(balance_pay + payment_money) as price, is_pay_bill')->where("mer_id={$mer_id} AND paid=1 AND is_own=0 AND status in (1,2) AND (pay_type<>'offline' OR balance_pay<>'0.00')")->group('is_pay_bill')->select();
                break;
            case 'group':
                $db = D('Group_order');
                if (empty($time_condition)) {
                    $count = $db->where(array('mer_id' => $mer_id, 'pay_type' => array('NEQ', 'offline'), 'paid' => 1, 'is_own' => 0, 'status' => array('in', '1, 2, 6')))->count();
                } else {
                    $count = $db->where(array('mer_id' => $mer_id, 'pay_type' => array('NEQ', 'offline'), 'paid' => 1, 'is_own' => 0, 'status' => array('in', '1, 2, 6'), '_string' => substr($time_condition, 5)))->count();
                }
                $p = new Page($count, 20);
                $sql = "SELECT order_id, order_name, uid, mer_id, store_id, phone, num as total, (balance_pay+payment_money) as price, total_money as order_price,use_time as dateline, pay_time, paid, status, pay_type, third_id, is_mobile_pay, balance_pay, payment_money,coupon_price, card_id, merchant_balance,score_deducte , refund_fee,refund_money ,is_pay_bill,real_orderid FROM " . C('DB_PREFIX') . "group_order WHERE mer_id={$mer_id} AND paid=1 AND is_own=0 AND status in (1,2,6) AND (pay_type<>'offline' OR balance_pay<>'0.00') AND (payment_money<>'0.00' OR balance_pay<>'0.00'  OR score_deducte <> '0.00' OR coupon_price <> '0.00') " . $time_condition;
                $sql .= " ORDER BY  is_pay_bill ASC ,use_time DESC LIMIT {$p->firstRow}, {$p->listRows}";
                $order_list = $mode->query($sql);
                $total_list = $db->field('sum(balance_pay + payment_money) as price, is_pay_bill')->where("mer_id={$mer_id} AND paid=1 AND is_own=0 AND status in (1,2) AND (pay_type<>'offline' OR balance_pay<>'0.00')")->group('is_pay_bill')->select();
                break;
            case 'weidian':
                $db = D('Weidian_order');
                $time = time() - 10 * 86400; //十天前的订单才能对账
                $count = $db->where("mer_id={$mer_id} AND paid=1 AND pay_type<>'offline' AND pay_time<'{$time}' {$time_condition}")->count();
                $p = new Page($count, 20);
                $sql = "SELECT order_id, order_name, uid, mer_id, store_id, order_num as total, (payment_money+balance_pay) as price, money as order_price, add_time as dateline, paid, pay_type, pay_time, third_id, balance_pay, payment_money, merchant_balance, is_pay_bill FROM " . C('DB_PREFIX') . "weidian_order WHERE mer_id={$mer_id} AND paid=1 AND pay_time<'{$time}' AND pay_type<>'offline'" . $time_condition;
                $sql .= " ORDER BY  is_pay_bill,order_id DESC LIMIT {$p->firstRow}, {$p->listRows}";
                $order_list = $mode->query($sql);
                $total_list = $db->field('sum(balance_pay + payment_money) as price, is_pay_bill')->where("mer_id={$mer_id} AND paid=1 AND pay_type<>'offline' AND pay_time<'{$time}'")->group('is_pay_bill')->select();
                break;
            case 'wxapp':
                $db = D('Wxapp_order');
                $count = $db->where("mer_id={$mer_id} AND paid=1 AND pay_type<>'offline' {$time_condition}")->count();
                $p = new Page($count, 20);
                $sql = "SELECT order_id, order_name, uid, mer_id, 0 as store_id, order_num as total, (payment_money+balance_pay) as price, money as order_price, add_time as dateline, paid, pay_type, pay_time, third_id, balance_pay, payment_money, merchant_balance, is_pay_bill FROM " . C('DB_PREFIX') . "wxapp_order WHERE mer_id={$mer_id} AND paid=1 AND pay_type<>'offline'" . $time_condition;
                $sql .= " ORDER BY  is_pay_bill,order_id DESC LIMIT {$p->firstRow}, {$p->listRows}";
                $order_list = $mode->query($sql);
                $total_list = $db->field('sum(balance_pay + payment_money) as price, is_pay_bill')->where("mer_id={$mer_id} AND paid=1 AND pay_type<>'offline'")->group('is_pay_bill')->select();
                break;
            case 'appoint':
                $db = D('Appoint_order');
                $count = $db->where("mer_id={$mer_id} AND paid=1 AND is_own=0 AND pay_type<>'offline' AND service_status=1 {$time_condition}")->count();
                $p = new Page($count, 20);
                $sql = "SELECT o.order_id, o.appoint_type as order_name, o.uid,o.mer_id, o.store_id, 1 as total, (pay_money+balance_pay) as price, o.payment_money as order_price, order_time as dateline, paid, pay_type, pay_time, third_id, balance_pay, pay_money as payment_money, merchant_balance,coupon_price,score_deducte, is_pay_bill FROM " . C('DB_PREFIX') . "appoint_order o LEFT JOIN " . C('DB_PREFIX') . "appoint a ON o.appoint_id = a.appoint_id  WHERE a.payment_status=1 AND  o.mer_id={$mer_id} AND paid=1 AND is_own=0 AND pay_type<>'offline' AND service_status=1" . $time_condition;
                $sql .= " ORDER BY  is_pay_bill,order_id DESC LIMIT {$p->firstRow}, {$p->listRows}";
                $order_list = $mode->query($sql);

                $total_list = $db->field('sum(balance_pay + pay_money) as price, is_pay_bill')->where("mer_id={$mer_id} AND paid=1 AND is_own=0 AND pay_type<>'offline' AND service_status=1")->group('is_pay_bill')->select();
                break;
            case 'store':
                $db = D('Store_order');
                $count = $db->where("mer_id={$mer_id} AND paid=1 AND is_own=0 AND refund=0 {$time_condition}")->count();
                $p = new Page($count, 20);
                $sql = "SELECT order_id, name as order_name, 0 as uid, mer_id, store_id, 1 as total, (payment_money+balance_pay) as price, (payment_money+balance_pay) as order_price, dateline, paid, pay_type, pay_time, third_id, balance_pay, payment_money, merchant_balance, is_pay_bill FROM " . C('DB_PREFIX') . "store_order WHERE mer_id={$mer_id} AND paid=1 AND is_own=0 AND refund=0 AND (payment_money<>'0.00' OR balance_pay<>'0.00')" . $time_condition;
                $sql .= " ORDER BY  is_pay_bill,order_id DESC LIMIT {$p->firstRow}, {$p->listRows}";
                $order_list = $mode->query($sql);
                $total_list = $db->field('sum(balance_pay + payment_money) as price, is_pay_bill')->where("mer_id={$mer_id} AND paid=1 AND is_own=0 AND refund=0 AND (payment_money<>'0.00' OR balance_pay<>'0.00')")->group('is_pay_bill')->select();
                break;
            case 'waimai':
                $db = D('Waimai_order');
                $count = $db->where("mer_id={$mer_id} AND paid=1 AND is_own=0 {$time_condition}")->count();
                $p = new Page($count, 20);
                $sql = "SELECT order_id, uid, `desc` as order_name ,mer_id, store_id, 1 as total, (online_pay+balance_pay) as price, (online_pay+balance_pay) as order_price,create_time as dateline, paid, pay_type, pay_time, third_id, balance_pay, online_pay as payment_money, merchant_balance, is_pay_bill FROM " . C('DB_PREFIX') . "waimai_order WHERE mer_id={$mer_id} AND paid=1 AND is_own=0 AND (online_pay<>'0.00' OR balance_pay<>'0.00')" . $time_condition;
                $sql .= " ORDER BY  is_pay_bill,order_id DESC LIMIT {$p->firstRow}, {$p->listRows}";
                $order_list = $mode->query($sql);
                $total_list = $db->field('sum(balance_pay + online_pay) as price, is_pay_bill')->where("mer_id={$mer_id} AND paid=1 AND is_own=0  AND (payment_money<>'0.00' OR balance_pay<>'0.00')")->group('is_pay_bill')->select();
                break;
            case 'shop':
                $db = D('Shop_order');
                if (empty($time_condition)) {
                    $count = $db->where(array('mer_id' => $mer_id, 'paid' => 1, 'is_own' => 0, 'pay_type' => array('NEQ', 'offline'), 'status' => array('in', '2,3')))->count();
                } else {
                    $count = $db->where(array('mer_id' => $mer_id, 'paid' => 1, 'is_own' => 0, 'pay_type' => array('NEQ', 'offline'), 'status' => array('in', '2,3'), '_string' => substr($time_condition, 5)))->count();
                }
                $p = new Page($count, 20);
                $sql = "SELECT order_id, 3 as order_name, uid, mer_id, store_id, userphone as phone, num as total, (balance_pay+payment_money+coupon_price+score_deducte+balance_reduce-no_bill_money) as price, (balance_pay+payment_money+coupon_price+score_deducte+balance_reduce-no_bill_money) as order_price, create_time as dateline, paid, status, pay_type, pay_time, third_id, is_mobile_pay, balance_pay, payment_money,coupon_price,card_price,score_deducte, card_id, merchant_balance, is_pay_bill,real_orderid FROM " . C('DB_PREFIX') . "shop_order WHERE mer_id={$mer_id} AND paid=1 AND is_own=0 AND status in (3,2) AND (pay_type<>'offline' OR balance_pay<>'0.00' OR coupon_price<>'0.00' OR payment_money<>'0.00' OR score_deducte<>'0.00')" . $time_condition;
                $sql .= " ORDER BY is_pay_bill, dateline DESC LIMIT {$p->firstRow}, {$p->listRows}";

                $order_list = $mode->query($sql);
                $total_list = $db->field('sum(balance_pay+payment_money+balance_reduce-no_bill_money) as price, is_pay_bill')->where("mer_id={$mer_id} AND paid=1 AND is_own=0 AND status in (3,2) AND (pay_type<>'offline' OR balance_pay<>'0.00' OR coupon_price<>'0.00' OR payment_money<>'0.00' OR score_deducte<>'0.00')")->group('is_pay_bill')->select();
                break;
        }

        /** total: 本页的总额 ; finshtotal:本页已对账的总额; alltotal:未对账的总额; alltotalfinsh:全部已对账总额*/
        $total = $finshtotal = $alltotal = $alltotalfinsh = 0;
        foreach ($total_list as $row) {
            $row['is_pay_bill'] && $alltotalfinsh += $row['price'];//已对账的总额
            $row['is_pay_bill'] || $alltotal += $row['price'];     //未对账的总额
        }
        //商家的门店信息
        $store_list = D('Merchant_store')->field('store_id, name')->where(array('mer_id' => $mer_id))->select();
        $store_idkey_list = array();
        foreach ($store_list as $store) {
            $store_idkey_list[$store['store_id']] = $store;
        }

        foreach ($order_list as &$order) {
            $order['store_name'] = isset($store_idkey_list[$order['store_id']]['name']) ? $store_idkey_list[$order['store_id']]['name'] : '';
            if ($type == 'meal') {
                $order['order_name'] = unserialize($order['order_name']);
            } elseif ($type == 'appoint') {
                $order['order_name'] = $order['order_name'] == 0 ? L_('到店') : L_('上门');
            }
            $order['pay_type_show'] = D('Pay')->get_pay_name($order['pay_type'], $order['is_mobile_pay']);

            if ($order['coupon_price']) {
                $total += $order['price'] + $order['coupon_price'] + $order['score_deducte']; //本页的总额
            } else {
                $total += $order['price'];
            }
            //$order['system_pay']=$system_pay;
            $order['is_pay_bill'] && $finshtotal += $order['price'];    //本页已对账的总额
        }
        return array('order_list' => $order_list, 'pagebar' => $p->show(), 'total' => $total, 'finshtotal' => $finshtotal, 'alltotalfinsh' => $alltotalfinsh, 'alltotal' => $alltotal);

    }

    public function bill_order($mer_id, $type = 'meal', $is_system = false, $time, $order_id)
    {
        if ($is_system) {
            import('@.ORG.system_page');
        } else {
            import('@.ORG.merchant_page');
        }
        $time_condition = '';
        if ($time) {
            //$time = unserialize($time);
            if ($time['period']) {
                if (is_array($time['period'])) {
                    $time_condition = " AND (pay_time BETWEEN " . $time['period'][0] . ' AND ' . $time['period'][1] . ")";
                } else {
                    $time_condition = " AND pay_time=" . $time['period'];
                }

            }

        }
        if ($order_id) {
            $time_condition .= ' AND order_id IN (' . $order_id . ') ';
        } else {

            $time_condition .= " AND is_pay_bill=0";
        }

        $mode = new Model();
        switch ($type) {
            case 'meal':
                $db = D('Meal_order');
                if (empty($time)) {
                    $count = $db->where(array('mer_id' => $mer_id, 'paid' => 1, 'is_own' => 0, '_string' => "balance_pay<>'0.00' OR score_deducte <> '0.00' OR coupon_price <> '0.00' OR payment_money <>'0.00'", 'status' => array('in', '1, 2'), 'is_pay_bill' => 0))->count();
                } else {
                    $count = $db->where(array('mer_id' => $mer_id, 'paid' => 1, 'is_own' => 0, '_string' => "balance_pay<>'0.00' OR score_deducte <> '0.00' OR coupon_price <> '0.00' OR payment_money <>'0.00'", 'status' => array('in', '1, 2'), '_string' => substr($time_condition, 5)))->count();
                }
                $limit = $time ? $count : 20;
                $p = new Page($count, $limit);
                $sql = "SELECT order_id,orderid, info as order_name, uid, mer_id, store_id, phone, total, (balance_pay+payment_money) as price, price as order_price, dateline, paid, status, pay_type, pay_time, third_id, is_mobile_pay, balance_pay, payment_money,coupon_price,score_deducte, card_id, merchant_balance, is_pay_bill,bill_time FROM " . C('DB_PREFIX') . "meal_order WHERE mer_id={$mer_id} AND paid=1 AND is_own=0 AND status in (1,2) AND (payment_money <> '0.00' OR balance_pay<>'0.00' OR score_deducte <> '0.00' OR coupon_price <> '0.00')" . $time_condition;
                $sql .= " ORDER BY dateline DESC LIMIT {$p->firstRow}, {$p->listRows}";
                $order_list = $mode->query($sql);
                $total_list = $db->field('sum(balance_pay + payment_money) as price, is_pay_bill')->where("mer_id={$mer_id} AND paid=1 AND is_own=0 AND status in (1,2) AND (pay_type<>'offline' OR balance_pay<>'0.00')")->group('is_pay_bill')->select();
                break;
            case 'group':
                $db = D('Group_order');
                if (empty($time_condition)) {
                    $count = $db->where(array('mer_id' => $mer_id, '_string' => "balance_pay<>'0.00' OR score_deducte <> '0.00' OR coupon_price <> '0.00' OR payment_money <>'0.00'", 'paid' => 1, 'is_own' => 0, 'status' => array('in', '1, 2, 6')))->count();
                } else {
                    $count = $db->where(array('mer_id' => $mer_id, '_string' => "balance_pay<>'0.00' OR score_deducte <> '0.00' OR coupon_price <> '0.00' OR payment_money <>'0.00'", 'paid' => 1, 'is_own' => 0, 'status' => array('in', '1, 2, 6'), '_string' => substr($time_condition, 5)))->count();
                }
                $limit = $time ? $count : 20;
                $p = new Page($count, $limit);
                $sql = "SELECT order_id, orderid,real_orderid,order_name, uid, mer_id, store_id, phone, num as total, (balance_pay+payment_money) as price, total_money as order_price, pay_time, paid, status, pay_type, third_id, is_mobile_pay, balance_pay, payment_money,coupon_price, card_id, merchant_balance,score_deducte , refund_fee,refund_money ,is_pay_bill FROM " . C('DB_PREFIX') . "group_order WHERE mer_id={$mer_id} AND paid=1 AND is_own=0 AND status in (1,2,6) AND ( payment_money <> '0.00' OR balance_pay<>'0.00' OR score_deducte <> '0.00' OR coupon_price <> '0.00')" . $time_condition;
                $sql .= " ORDER BY use_time DESC LIMIT {$p->firstRow}, {$p->listRows}";
                $order_list = $mode->query($sql);
                $total_list = $db->field('sum(balance_pay + payment_money) as price, is_pay_bill')->where("mer_id={$mer_id} AND paid=1 AND is_own=0 AND status in (1,2) AND (pay_type<>'offline' OR balance_pay<>'0.00')")->group('is_pay_bill')->select();
                break;
            case 'weidian':
                $db = D('Weidian_order');
                $time = time() - 10 * 86400; //十天前的订单才能对账
                $count = $db->where("mer_id={$mer_id} AND paid=1 AND pay_type<>'offline' AND pay_time<'{$time}' {$time_condition}")->count();
                $limit = $time ? $count : 20;
                $p = new Page($count, $limit);
                $sql = "SELECT order_id, orderid,order_name, uid, mer_id, store_id, order_num as total, (payment_money+balance_pay) as price, money as order_price, add_time as dateline, paid, pay_type, pay_time, third_id, balance_pay, payment_money, merchant_balance, is_pay_bill FROM " . C('DB_PREFIX') . "weidian_order WHERE mer_id={$mer_id} AND paid=1 AND pay_time<'{$time}' AND pay_type<>'offline'" . $time_condition;
                $sql .= " ORDER BY  order_id DESC LIMIT {$p->firstRow}, {$p->listRows}";
                $order_list = $mode->query($sql);
                $total_list = $db->field('sum(balance_pay + payment_money) as price, is_pay_bill')->where("mer_id={$mer_id} AND paid=1 AND pay_type<>'offline' AND pay_time<'{$time}'")->group('is_pay_bill')->select();
                break;
            case 'wxapp':
                $db = D('Wxapp_order');
                $count = $db->where("mer_id={$mer_id} AND paid=1 AND pay_type<>'offline' {$time_condition}")->count();
                $limit = $time ? $count : 20;
                $p = new Page($count, $limit);
                $sql = "SELECT order_id,orderid,order_name, uid, mer_id, 0 as store_id, order_num as total, (payment_money+balance_pay) as price, money as order_price, add_time as dateline, paid, pay_type, pay_time, third_id, balance_pay, payment_money, merchant_balance, is_pay_bill FROM " . C('DB_PREFIX') . "wxapp_order WHERE mer_id={$mer_id} AND paid=1 AND pay_type<>'offline'" . $time_condition;
                $sql .= " ORDER BY order_id DESC LIMIT {$p->firstRow}, {$p->listRows}";
                $order_list = $mode->query($sql);
                $total_list = $db->field('sum(balance_pay + payment_money) as price, is_pay_bill')->where("mer_id={$mer_id} AND paid=1 AND pay_type<>'offline'")->group('is_pay_bill')->select();
                break;
            case 'appoint':
                $db = D('Appoint_order');
                $count = $db->where("mer_id={$mer_id} AND paid=1 AND is_own=0 AND pay_type<>'offline' AND service_status=1 {$time_condition}")->count();
                $limit = $time ? $count : 20;
                $p = new Page($count, $limit);
                $sql = "SELECT o.order_id,o.orderid, o.appoint_type as order_name, o.uid,o.mer_id, o.store_id, 1 as total, (pay_money+balance_pay) as price, o.payment_money as order_price, order_time as dateline, paid, pay_type, pay_time, third_id, balance_pay, pay_money as payment_money, merchant_balance,coupon_price,score_deducte, is_pay_bill FROM " . C('DB_PREFIX') . "appoint_order o LEFT JOIN " . C('DB_PREFIX') . "appoint a ON o.appoint_id = a.appoint_id  WHERE a.payment_status=1 AND  o.mer_id={$mer_id} AND paid=1 AND is_own=0 AND pay_type<>'offline'  AND service_status=1" . $time_condition;
                $sql .= " ORDER BY  order_id DESC LIMIT {$p->firstRow}, {$p->listRows}";
                $order_list = $mode->query($sql);

                $total_list = $db->field('sum(balance_pay + pay_money) as price, is_pay_bill')->where("mer_id={$mer_id} AND paid=1 AND is_own=0 AND pay_type<>'offline' AND service_status=1")->group('is_pay_bill')->select();
                break;
            case 'store':
                $db = D('Store_order');
                $count = $db->where("mer_id={$mer_id} AND paid=1 AND is_own=0 AND refund=0 {$time_condition}")->count();
                $limit = $time ? $count : 20;
                $p = new Page($count, $limit);
                $sql = "SELECT order_id,orderid, name as order_name, 0 as uid, mer_id, store_id, 1 as total, (payment_money+balance_pay) as price, (payment_money+balance_pay) as order_price, dateline, paid, pay_type, pay_time, third_id, balance_pay, payment_money, merchant_balance, is_pay_bill FROM " . C('DB_PREFIX') . "store_order WHERE mer_id={$mer_id} AND paid=1 AND is_own=0 AND refund=0 AND (payment_money<>'0.00' OR balance_pay<>'0.00' )" . $time_condition;
                $sql .= " ORDER BY order_id DESC LIMIT {$p->firstRow}, {$p->listRows}";
                $order_list = $mode->query($sql);
                $total_list = $db->field('sum(balance_pay + payment_money) as price, is_pay_bill')->where("mer_id={$mer_id} AND paid=1 AND is_own=0 AND refund=0 AND (payment_money<>'0.00' OR balance_pay<>'0.00')")->group('is_pay_bill')->select();
                break;
            case 'waimai':
                $db = D('Waimai_order');
                $count = $db->where("mer_id={$mer_id} AND paid=1 AND is_own=0 {$time_condition}")->count();
                $limit = $time ? $count : 20;
                $p = new Page($count, $limit);
                $sql = "SELECT order_id,orderid, uid, `desc` as order_name ,mer_id, store_id, 1 as total, (online_pay+balance_pay) as price, (online_pay+balance_pay) as order_price,create_time as dateline, paid, pay_type, pay_time, third_id, balance_pay, online_pay as payment_money, merchant_balance, is_pay_bill FROM " . C('DB_PREFIX') . "waimai_order WHERE mer_id={$mer_id} AND paid=1 AND is_own=0 AND (online_pay<>'0.00' OR balance_pay<>'0.00' )" . $time_condition;
                $sql .= " ORDER BY order_id DESC LIMIT {$p->firstRow}, {$p->listRows}";
                $order_list = $mode->query($sql);
                $total_list = $db->field('sum(balance_pay + online_pay) as price, is_pay_bill')->where("mer_id={$mer_id} AND paid=1 AND is_own=0  AND (payment_money<>'0.00' OR balance_pay<>'0.00')")->group('is_pay_bill')->select();
                break;
            case 'shop':
                $db = D('Shop_order');
                if (empty($time_condition)) {
                    $count = $db->where(array('mer_id' => $mer_id, 'paid' => 1, 'is_own' => 0, '_string' => "balance_pay<>'0.00' OR score_deducte <> '0.00' OR coupon_price <> '0.00' OR payment_money <>'0.00'", 'status' => array('in', '2,3')))->count();
                } else {
                    $count = $db->where(array('mer_id' => $mer_id, 'paid' => 1, 'is_own' => 0, '_string' => "balance_pay<>'0.00' OR score_deducte <> '0.00' OR coupon_price <> '0.00' OR payment_money <>'0.00'", 'status' => array('in', '2,3'), '_string' => substr($time_condition, 5)))->count();
                }
                $limit = $time ? $count : 20;
                $p = new Page($count, $limit);
                $sql = "SELECT order_id,orderid, real_orderid,3 as order_name, uid, mer_id, store_id, userphone as phone, num as total, (balance_pay+payment_money+coupon_price+score_deducte+balance_reduce-no_bill_money) as price, (balance_pay+payment_money+coupon_price+score_deducte+balance_reduce-no_bill_money) as order_price, create_time as dateline, paid, status, pay_type, pay_time, third_id, is_mobile_pay, balance_pay, payment_money,coupon_price,card_price,score_deducte, card_id, merchant_balance, is_pay_bill FROM " . C('DB_PREFIX') . "shop_order WHERE mer_id={$mer_id} AND paid=1 AND is_own=0 AND status in (3,2) AND ( balance_pay<>'0.00' OR coupon_price<>'0.00' OR payment_money<>'0.00' OR score_deducte<>'0.00')" . $time_condition;
                $sql .= " ORDER BY  dateline DESC LIMIT {$p->firstRow}, {$p->listRows}";

                $order_list = $mode->query($sql);
                $total_list = $db->field('sum(balance_pay+payment_money+balance_reduce-no_bill_money) as price, is_pay_bill')->where("mer_id={$mer_id} AND paid=1 AND is_own=0 AND status in (3,2) AND ( balance_pay<>'0.00' OR coupon_price<>'0.00' OR payment_money<>'0.00' OR score_deducte<>'0.00')")->group('is_pay_bill')->select();
                break;
        }

        /** total: 本页的总额 ; finshtotal:本页已对账的总额; alltotal:未对账的总额; alltotalfinsh:全部已对账总额*/
        $total = $finshtotal = $alltotal = $alltotalfinsh = 0;
        foreach ($total_list as $row) {
            $row['is_pay_bill'] && $alltotalfinsh += $row['price'];//已对账的总额
            $row['is_pay_bill'] || $alltotal += $row['price'];     //未对账的总额
        }
        //商家的门店信息
        $store_list = D('Merchant_store')->field('store_id, name')->where(array('mer_id' => $mer_id))->select();
        $store_idkey_list = array();
        foreach ($store_list as $store) {
            $store_idkey_list[$store['store_id']] = $store;
        }

        foreach ($order_list as &$order) {
            $order['store_name'] = isset($store_idkey_list[$order['store_id']]['name']) ? $store_idkey_list[$order['store_id']]['name'] : '';
            if ($type == 'meal') {
                $order['order_name'] = unserialize($order['order_name']);
            } elseif ($type == 'appoint') {
                $order['order_name'] = $order['order_name'] == 0 ? L_('到店') : L_('上门');
            }
            $order['pay_type_show'] = D('Pay')->get_pay_name($order['pay_type'], $order['is_mobile_pay']);
            if ($type == 'group') {
                $total += $order['balance_pay'] + $order['payment_money'] + $order['score_deducte'] + $order['coupon_price'] - $order['refund_money'];
            } else if ($type == 'meal' || $type == 'shop' || $type == 'appoint') {
                $total += $order['balance_pay'] + $order['payment_money'] + $order['score_deducte'] + $order['coupon_price'];
            } else {
                $total += $order['order_price'];
            }
            $order['is_pay_bill'] && $finshtotal += $order['price'];    //本页已对账的总额
        }
        return array('order_list' => $order_list, 'pagebar' => $p->show(), 'total' => $total, 'finshtotal' => $finshtotal, 'alltotalfinsh' => $alltotalfinsh, 'alltotal' => $alltotal);

    }

    public function mer_bill($mer_id, $is_system = false, $type, $time, $store_id)
    {
        if ($is_system) {
            import('@.ORG.system_page');
        } else {
            import('@.ORG.merchant_page');
        }
        $time_condition = '';
        if ($time) {
            $time = unserialize($time);
            if ($time['period']) {
                if (is_array($time['period'])) {
                    $time_condition = " AND (pay_time BETWEEN " . $time['period'][0] . ' AND ' . $time['period'][1] . ")";
                } else {
                    $time_condition = " AND pay_time=" . $time['period'];
                }
            } elseif ($time['month']) {
                $time_condition = ' AND year(FROM_UNIXTIME(pay_time))=' . $time['year'] . ' AND month(FROM_UNIXTIME(pay_time))=' . $time['month'];
            } elseif ($time['year']) {
                $time_condition = ' AND year(FROM_UNIXTIME(pay_time))=' . $time['year'];
            }

        }
        if (!empty($store_id)) {
            $time_condition .= ' AND store_id=' . $store_id;
        }
        if ($type == 'waimai') {
            if (empty($time_condition)) {
                $count = D(ucwords($type) . '_order')->where(array('mer_id' => $mer_id, 'pay_type' => array('NEQ', 'offline'), 'paid' => 1))->count();
            } else {
                $count = D(ucwords($type) . '_order')->where(array('mer_id' => $mer_id, 'pay_type' => array('NEQ', 'offline'), 'paid' => 1, '_string' => substr($time_condition, 5)))->count();
            }
        } elseif ($type == 'shop') {
            if (empty($time_condition0)) {
                $count = D(ucwords($type) . '_order')->where(array('mer_id' => $mer_id, 'is_own' => 0, 'paid' => 1, 'status' => array('in', '3,2')))->count();
            } else {
                $count = D(ucwords($type) . '_order')->where(array('mer_id' => $mer_id, 'is_own' => 0, 'paid' => 1, 'status' => array('in', '3,2'), '_string' => substr($time_condition, 5)))->count();
            }
//          echo D(ucwords($type).'_order')->_sql();die;
        } else {
            if (empty($time_condition0)) {
                $count = D(ucwords($type) . '_order')->where(array('mer_id' => $mer_id, 'pay_type' => array('NEQ', 'offline'), 'paid' => 1, 'status' => array('in', '1, 2')))->count();
            } else {
                $count = D(ucwords($type) . '_order')->where(array('mer_id' => $mer_id, 'pay_type' => array('NEQ', 'offline'), 'paid' => 1, 'status' => array('in', '1, 2'), '_string' => substr($time_condition, 5)))->count();
            }
        }

        $p = new Page($count, 20);


        switch ($type) {
            case 'meal':
                $sql = "SELECT order_id,  1 as name,info as order_name, uid, mer_id, store_id, phone, total, (balance_pay+payment_money) as price, price as order_price, dateline, paid, status, pay_type, pay_time, third_id, is_mobile_pay, balance_pay, payment_money, coupon_price,score_deducte,card_id, merchant_balance, is_pay_bill FROM " . C('DB_PREFIX') . "meal_order WHERE mer_id={$mer_id} AND paid=1 AND status in (1,2)  AND (payment_money<>'0.00' OR balance_pay<>'0.00'  OR score_deducte <> '0.00' OR coupon_price <> '0.00')";
                break;
            case 'group':
                $sql = "SELECT order_id,real_orderid, order_name, uid, mer_id, store_id, phone, num as total, (balance_pay+payment_money) as price, total_money as order_price, use_time as dateline, paid, status, pay_type, pay_time, third_id, is_mobile_pay, balance_pay, payment_money, coupon_price,score_deducte,card_id, merchant_balance,refund_fee,refund_money, is_pay_bill FROM " . C('DB_PREFIX') . "group_order WHERE mer_id={$mer_id} AND paid=1 AND status in (1,2,6) AND (payment_money<>'0.00' OR balance_pay<>'0.00'  OR score_deducte <> '0.00' OR coupon_price <> '0.00')";
                break;
            case 'weidian':
                $time = time() - 10 * 86400;
                $sql = "SELECT order_id, order_name, uid, mer_id, store_id, order_num as total, (payment_money+balance_pay) as price, money as order_price, add_time as dateline, paid, pay_type, pay_time, third_id, balance_pay, payment_money, merchant_balance, is_pay_bill FROM " . C('DB_PREFIX') . "weidian_order WHERE mer_id={$mer_id} AND paid=1 AND pay_time<'{$time}' AND pay_type<>'offline' ";
                break;
            case 'appoint':
                $sql = "SELECT o.order_id, o.appoint_type as order_name, o.uid, o.mer_id, o.store_id, 1 as total, (pay_money+balance_pay) as price, o.payment_money as order_price, order_time as dateline, paid, pay_type, pay_time, third_id, balance_pay, pay_money as payment_money, merchant_balance ,coupon_price,card_pirce,score_deducte,is_pay_bill FROM " . C('DB_PREFIX') . "appoint_order o LEFT JOIN " . C('DB_PREFIX') . "appoint a ON o.appoint_id = a.appoint_id  WHERE a.payment_status=1 AND o.mer_id={$mer_id} AND paid=1 AND is_own=0 AND pay_type<>'offline' AND service_status=1 AND (o.payment_money<>'0.00' OR balance_pay<>'0.00')";
                break;
            case 'wxapp':
                $sql = "SELECT order_id, order_name, uid, mer_id, 0 as store_id, order_num as total, (payment_money+balance_pay) as price, money as order_price, add_time as dateline, paid, pay_type, pay_time, third_id, balance_pay, payment_money, merchant_balance, is_pay_bill FROM " . C('DB_PREFIX') . "wxapp_order WHERE mer_id={$mer_id} AND paid=1 AND pay_type<>'offline' AND (payment_money<>'0.00' OR balance_pay<>'0.00')";
                break;
            case 'store':
                $sql = "SELECT order_id, name as order_name, 0 as uid, mer_id, store_id, 1 as total, (payment_money+balance_pay) as price, (payment_money+balance_pay) as order_price, dateline, paid, pay_type, pay_time, third_id, balance_pay, payment_money,  merchant_balance, is_pay_bill FROM " . C('DB_PREFIX') . "store_order WHERE mer_id={$mer_id} AND paid=1 AND is_own=0 AND refund=0 AND (payment_money<>'0.00' OR balance_pay<>'0.00')";
                break;
            case 'waimai':
                $sql = "SELECT order_id, `desc` as order_name,  uid, mer_id, store_id, 1 as total, (online_pay+balance_pay) as price, (online_pay+balance_pay) as order_price, create_time as dateline, paid, pay_type, pay_time, third_id, balance_pay, online_pay as payment_money,  merchant_balance, is_pay_bill FROM " . C('DB_PREFIX') . "waimai_order WHERE mer_id={$mer_id} AND paid=1 AND is_own=0 AND (online_pay<>'0.00' OR balance_pay<>'0.00')";
                break;
            case 'shop':
                $sql = "SELECT order_id, real_orderid, 3 as name, 3 as order_name,  uid, mer_id, store_id, num as total, (balance_pay+payment_money+coupon_price+score_deducte+balance_reduce-no_bill_money) as price, (balance_pay+payment_money+coupon_price+score_deducte+balance_reduce-no_bill_money) as order_price, create_time as dateline, paid, pay_type, pay_time, third_id, balance_pay, payment_money,  merchant_balance, is_pay_bill, coupon_price, card_price, score_deducte FROM " . C('DB_PREFIX') . "shop_order WHERE mer_id={$mer_id} AND paid=1 AND is_own=0 AND status IN (2,3) AND (pay_type<>'offline' OR balance_pay<>'0.00' OR coupon_price<>'0.00' OR payment_money<>'0.00' OR score_deducte<>'0.00')";
                break;
            default:
                break;
        }

        $sql .= $time_condition . " ORDER BY is_pay_bill ASC, dateline DESC LIMIT {$p->firstRow}, {$p->listRows}";
        $mode = new Model();
        $res = $mode->query($sql);
//      echo $mode->_sql();
        $stores = D('Merchant_store')->field('store_id, name')->where(array('mer_id' => $mer_id))->select();
        $temp = array();
        foreach ($stores as $store) {
            $temp[$store['store_id']] = $store;
        }
        $alltotal = $alltotalfinsh = 0;
        $total = $finshtotal = 0;
        foreach ($res as &$l) {
            $l['store_name'] = isset($temp[$l['store_id']]['name']) ? $temp[$l['store_id']]['name'] : '';
            $l['name'] == 1 && $l['order_name'] = unserialize($l['order_name']);
            $l['pay_type_show'] = D('Pay')->get_pay_name($l['pay_type'], $l['is_mobile_pay']);
            $l['score_deducte'] = isset($l['score_deducte']) ? $l['score_deducte'] : 0;
            $l['coupon_price'] = isset($l['coupon_price']) ? $l['coupon_price'] : 0;
            $l['card_price'] = isset($l['card_price']) ? $l['card_price'] : 0;
            $total += $l['price'] + $l['score_deducte'] + $l['coupon_price'];
            $l['is_pay_bill'] && $finshtotal += $l['price'];    //本页已对账的总额
            $l['order_price'] = round($l['order_price'], 2);
        }
        $pagebar = $p->show();
        return array('order_list' => $res, 'pagebar' => $pagebar, 'total' => $total, 'finshtotal' => $finshtotal, 'alltotalfinsh' => $alltotalfinsh, 'alltotal' => $alltotal);

    }

    public function export_order_by_mid($mer_id, $type = 'meal', $is_pay_bill = 0, $order_id)
    {
        $condition = '';
        $is_pay_bill = ' is_pay_bill = ' . $is_pay_bill;
        if ($order_id) {
            $condition .= ' AND order_id IN (' . $order_id . ') ';
        }
        $mode = new Model();
        switch ($type) {
            case 'meal':
                $db = D('Meal_order');
                $sql = "SELECT order_id, orderid, info as order_name, uid, mer_id, store_id, phone, total as num, price as order_price, dateline, paid, status, pay_type, pay_time,score_deducte, balance_pay, payment_money, coupon_price FROM " . C('DB_PREFIX') . "meal_order WHERE {$is_pay_bill} AND mer_id={$mer_id} AND paid=1 AND is_own=0 AND status in (1,2) AND ( balance_pay<>'0.00'  OR score_deducte <> '0.00' OR coupon_price <> '0.00' OR payment_money <> '0.00')" . $condition;
                $sql .= " ORDER BY dateline DESC ";

                $order_list = $mode->query($sql);
                break;
            case 'group':
                $db = D('Group_order');

                $sql = "SELECT order_id, orderid,real_orderid,  order_name, uid, mer_id, store_id, phone, num , total_money as order_price,  pay_type, pay_time,  balance_pay, payment_money,  score_deducte,coupon_price,refund_money,refund_fee FROM " . C('DB_PREFIX') . "group_order WHERE {$is_pay_bill} AND mer_id={$mer_id} AND paid=1 AND is_own=0 AND status in (1,2,6) AND (balance_pay<>'0.00'  OR score_deducte <> '0.00' OR coupon_price <> '0.00' OR payment_money <> '0.00')" . $condition;
                $sql .= " ORDER BY order_id DESC ";

                $order_list = $mode->query($sql);
                break;
            case 'weidian':
                $db = D('Weidian_order');
                $time = time() - 10 * 86400; //十天前的订单才能对账
                $sql = "SELECT order_id, orderid, order_name, uid, mer_id, store_id, order_num as num,  money as order_price, pay_type, pay_time, balance_pay, payment_money FROM " . C('DB_PREFIX') . "weidian_order WHERE {$is_pay_bill} AND mer_id={$mer_id} AND paid=1 AND pay_time<'{$time}' AND pay_type<>'offline'" . $condition;
                $sql .= " ORDER BY order_id DESC ";
                $order_list = $mode->query($sql);
                break;
            case 'wxapp':
                $db = D('Wxapp_order');
                $sql = "SELECT order_id,  orderid, order_name, uid, mer_id, 0 as store_id, order_num as num, money as order_price, pay_type, pay_time, balance_pay, payment_money FROM " . C('DB_PREFIX') . "wxapp_order WHERE {$is_pay_bill} AND mer_id={$mer_id} AND paid=1 AND pay_type<>'offline'" . $condition;
                $sql .= " ORDER BY order_id ";
                $order_list = $mode->query($sql);
                break;
            case 'appoint':
                $db = D('Appoint_order');
                $sql = "SELECT order_id,  orderid, appoint_id as order_name, uid, mer_id, store_id, 1 as num,  payment_money as order_price, pay_type, pay_time, balance_pay, pay_money as payment_money,  score_deducte,coupon_price FROM " . C('DB_PREFIX') . "appoint_order WHERE {$is_pay_bill} AND mer_id={$mer_id} AND paid=1 AND is_own=0  AND pay_type<>'offline' AND service_status=1" . $condition;
                $sql .= " ORDER BY order_id DESC ";
                $order_list = $mode->query($sql);
                break;
            case 'store':
                $db = D('Store_order');
                $sql = "SELECT order_id, orderid, name as order_name, 0 as uid, mer_id, store_id, 1 as num, total_price as order_price, pay_type, pay_time, balance_pay, payment_money FROM " . C('DB_PREFIX') . "store_order WHERE {$is_pay_bill} AND mer_id={$mer_id} AND paid=1 AND is_own=0 AND refund=0" . $condition;
                $sql .= " ORDER BY order_id DESC";
                $order_list = $mode->query($sql);
                break;
            case 'waimai':
                $db = D('Waimai_order');
                $sql = "SELECT order_id, orderid, `desc` as order_name,  uid, mer_id, store_id, 1 as num, price as order_price, pay_type, pay_time, balance_pay, online_pay as payment_money FROM " . C('DB_PREFIX') . "waimai_order WHERE {$is_pay_bill} AND mer_id={$mer_id} AND paid=1 AND is_own=0 " . $condition;
                $sql .= " ORDER BY order_id DESC";
                $order_list = $mode->query($sql);
                break;
            case 'shop':
                $db = D('Shop_order');
                $sql = "SELECT order_id,orderid, real_orderid, 3 as order_name, uid, mer_id, store_id, userphone as phone, num ,  (balance_pay+payment_money+balance_reduce-no_bill_money) as order_price,  pay_type, pay_time, balance_pay, payment_money,coupon_price,score_deducte FROM " . C('DB_PREFIX') . "shop_order WHERE {$is_pay_bill} AND mer_id={$mer_id} AND paid=1 AND is_own=0 AND status in (3,2) AND ( balance_pay<>'0.00' OR coupon_price<>'0.00' OR payment_money<>'0.00' OR score_deducte<>'0.00')" . $condition;
                $sql .= " ORDER BY order_id DESC";
                $order_list = $mode->query($sql);
                break;
        }

        //商家的门店信息
        $store_list = D('Merchant_store')->field('store_id, name')->where(array('mer_id' => $mer_id))->select();
        $store_idkey_list = array();
        foreach ($store_list as $store) {
            $store_idkey_list[$store['store_id']] = $store;
        }

        foreach ($order_list as &$order) {
            $order['store_name'] = isset($store_idkey_list[$order['store_id']]['name']) ? $store_idkey_list[$order['store_id']]['name'] : '';
            if ($type == 'meal') {
                $order['order_name'] = unserialize($order['order_name']);
            } elseif ($type == 'appoint') {
                $order['order_name'] = $order['order_name'] == 0 ? L_('到店') : L_('上门');
            }
            $order['pay_type'] = D('Pay')->get_pay_name($order['pay_type'], $order['is_mobile_pay']);
        }
        return $order_list;
        //return array('order_list' => $order_list);
    }

    public function get_offlineorder_by_mer_id($mer_id, $staff_name = '', $type = 'shop', $limit = 20, $staffid = 0)
    {
        import('@.ORG.merchant_page');
        $field = '';
        if ($type == 'shop') {
            $field = "3 as name, order_id, orderid, real_orderid, price, payment_money, balance_pay, merchant_balance, coupon_price, (card_give_money + card_price) as card_price, score_deducte, score_deducte, (price-payment_money-balance_pay-merchant_balance-coupon_price-card_price-card_give_money-score_deducte) as cash, create_time, pay_time, is_pay_bill";
            $table_name = 'Shop_order';
            $where = " mer_id={$mer_id} AND paid=1 AND status IN (3,2) AND pay_type='offline'";
        } elseif ($type == 'meal') {
            $field = "1 as name, business_id as order_id, orderid, orderid as real_orderid, price, payment_money, balance_pay, merchant_balance, coupon_price, card_price, score_deducte, (price-payment_money-balance_pay-merchant_balance-coupon_price-card_price-score_deducte) as cash, dateline as create_time, pay_time, is_pay_bill";
            $table_name = 'Store_order';
            $where = " mer_id={$mer_id} AND paid=1 AND business_type='foodshop' AND pay_type='offline'";
        }elseif ($type == 'dining') {// 新版餐饮
            $field = "4 as name, o.order_id,  o.real_orderid, o.price, o.pay_money as payment_money, o.system_balance as balance_pay, o.merchant_balance_pay as merchant_balance, o.coupon_price, o.card_price, o.system_score_money as score_deducte, (o.price-o.pay_money-o.system_balance-o.merchant_balance_pay-o.system_score_money) as cash, o.create_time,o.pay_time, o.is_pay_bill";
            $table_name = 'Dining_order';
            $where = " o.mer_id={$mer_id} AND p.paid=1 AND o.status IN (40,41) AND p.pay_type='offline'";
        } elseif ($type == 'group') {
            $field = "2 as name, order_id, orderid, real_orderid, price, payment_money, balance_pay, merchant_balance, coupon_price, card_price, score_deducte, score_deducte, (price-payment_money-balance_pay-merchant_balance-coupon_price-card_price-score_deducte) as cash, add_time as create_time, pay_time, is_pay_bill";
            $table_name = 'Group_order';
            $where = " mer_id={$mer_id} AND paid=1 AND status IN (1,2) AND pay_type='offline'";
        }
        if ($type == 'meal') {
            $staff_name && $where .= " AND staff_name='{$staff_name}'";
        } elseif($type == 'dining') {
            $staff_name && $where .= " AND p.staff_id='{$staffid}'";
        } else {
            $staff_name && $where .= " AND last_staff='{$staff_name}'";
        }

        if($type == 'dining'){
            $count = D($table_name)->join(' as o left join '.C('DB_PREFIX').'dining_order_pay as p on o.order_id= p.order_id')->where($where)->count();
            $uncount = D($table_name)->join(' as o left join '.C('DB_PREFIX').'dining_order_pay as p on o.order_id= p.order_id')->where($where . ' AND o.is_pay_bill=0')->count();
            $p = new Page($count, $limit);
            $list = D($table_name)->field($field)->join(' as o left join '.C('DB_PREFIX').'dining_order_pay as p on o.order_id= p.order_id')->where($where)->order('o.pay_time DESC,o.is_pay_bill ASC')->limit("{$p->firstRow}, {$p->listRows}")->select();
            $pagebar = $p->show();
        }else{
            $count = D($table_name)->where($where)->count();
            $uncount = D($table_name)->where($where . ' AND is_pay_bill=0')->count();
            $p = new Page($count, $limit);
            $list = D($table_name)->field($field)->where($where)->order('pay_time DESC,is_pay_bill ASC')->limit("{$p->firstRow}, {$p->listRows}")->select();
            $pagebar = $p->show();
        }
        return array('order_list' => $list, 'pagebar' => $p->show(), 'uncount' => $uncount);
    }

    /*
     * plat_type 1 系统后台 2 商家 3 店员
     * $param  惨数数组
     * */
    public function order_export($param)
    {
        import('@.ORG.plan');
        $plan_class = new plan();
        $file_fix = 'xls';
        switch ($param['type']) {
            case 'meal':
                $title = C('config.meal_alias_name') . L_('账单');
                break;
            case 'group':
                $title = C('config.group_alias_name') . L_('账单');
                $file_fix = 'csv';
                break;
            case 'appoint':
                $title = C('config.appoint_alias_name') . L_('账单');
                break;
            case 'shop':
                if($param['from_type']==2){
                    $title =  C('config.mall_alias_name').L_('账单');
                }else{
                    $title =  C('config.shop_alias_name').L_('账单');
                }
                $file_fix = 'csv';
                break;
            case 'income':
                $title = L_('收入明细');
                break;
            case 'user':                // user 走 csv
                $title = L_('用户信息');
                $file_fix = 'csv';
                break;
            case 'express':
                $title = L_('配送信息');
                break;
            case 'trade':
                $title = L_('交易信息');
                break;
            case 'trade_new':
                $title = L_('交易信息');
                break;
            case 'goods':
                $title = L_('菜品信息');
                break;
            case 'card_new':
                $title = L_('会员信息');
                break;
            case 'fans':
                $title = L_('粉丝信息');
                break;
            case 'merchant_list':
                $title = L_('商家列表');
                break;
            case 'percentage':
                $title = L_('抽成列表');
                break;
            case 'admin_recharge':
                $title = L_('管理员充值导出');
                break;
            case 'store':
                $title = L_('到店消费');
                $file_fix = 'csv';
                break;
            case 'activity_join':
                $title = L_('活动报名列表');
                break;
            case 'salesman_list':
                $title = L_('业务员流水');
                break;
            case 'admin_money_list':
                $title = L_('业务员流水');
                break;
            case 'service_offer':
                $title = L_('服务快派列表');
                break;
            case 'auto_store_order':
                $title = L_('帮我买自定义店铺订单列表');
                break;
            case 'score_pay_back':
                $title = L_('积分返现记录列表');
                break;
            case 'deliver_list':
                $title = L_('配送列表');
                break;
            case 'reply':
                $title = L_('评论列表');
                break;
            case 'score_all':
                $title = L_('积分记录列表');
                break;
            case 'village_group':
                $title = L_('X1订单', array('X1' => C('config.village_group_alias')));
                break;
            case 'village_group_send':
                $title = L_('X1发货单', array('X1' => C('config.village_group_alias')));
                break;
            case 'village_group_trade':
                $title = L_('X1统计', array('X1' => C('config.village_group_alias')));
                break;
            case 'distributor_spread':
                $title = L_('分销佣金记录');
                break;
            case 'activation_code':
                $title = L_('会员等级激活码');
                break;
            case 'open_level_log':
                $title = L_('平台会员开通记录');
                break;
            case 'market_sell':
                $title = L_('批发销售订单');
                break;
            case 'market_buy':
                $title = L_('批发进货订单');
                break;
            case 'mer_union':
                $title = L_('商家联盟统计');
                break;
            case 'statistics':
                $title = L_('营业分析');
                break;
            case 'platStatistics':
                $title = L_('系统后台营业分析');
                break;
            case 'meal_card_buy':
                $title = L_('商家饭票充值记录');
                break;
             case 'meal_card_spend':
                $title = L_('商家饭票消费记录');
                break;
            case 'pick_order':
                $title = L_('自提点订单');
                break;
            case 'plugin_vote':
                $title = L_('投票详情');
                break;
            case 'staff_bill':
                $title = L_('店员对账');
                break;
            case 'goods_to_be_delivered':
                $title = L_('待发商品清单');
                break;
            case 'merchant_bill':
                $title = L_('商家对账');
                break;
            case 'recharge_order':
                $title = L_('充值订单');
                break;
            case 'room_export':
                $title = L_('房屋列表');
                break;
            case 'card_recharge':
                $title = L_('商家线下充值订单列表');
                break;
            case 'online_recharge':
                $title = L_('用户线上充值订单列表');
                break;
        }
        $param['rand_number']?$param['rand_number']:time();
        $file_name = date("Y-m-d", time()) . '-' . $param['rand_number'] . '.' . $file_fix;
        $date = array(
            'type' => $param['type'],
            'file_name' => $file_name,
            'title' => $title,
            'status' => 0,
            'param' => serialize($param),
            'dateline' => $param['rand_number']
        );
        if ($export_id = M('Export_log')->add($date)) {
            $param['export_id'] = $export_id;
            $params = array(
                'file' => 'order_export',
                'plan_time' => time() - 86400,
                'param' => $param,
                'unique_id'=>'house_room_export_out'.$export_id
            );
            $plan_class->addTask($params);
            return array('file_name' => $file_name, 'export_id' => $export_id);

        } else {
            return false;
        }
    }

    public function order_export_runtask($param)
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');

        // user 走 csv，不需要申明 excel
        if($param['type'] != 'user' && $param['type'] != 'store' && $param['type'] != 'shop'){
            require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
            $title = '';
            $cacheMethod = PHPExcel_CachedObjectStorageFactory:: cache_to_discISAM;
            $cacheSettings = array('dir' => './runtime');
            PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
            $objExcel = new PHPExcel();

            $objProps = $objExcel->getProperties();
        }

        switch ($param['type']) {
            case 'meal':
            case 'foodshop':
                $param['type'] = 'meal';
                $title = C('config.meal_alias_name') . L_('账单');
                // 设置文档基本属性
                $objProps->setCreator($title);
                $objProps->setTitle($title);
                $objProps->setSubject($title);
                $objProps->setDescription($title);
                // 设置当前的sheet
                $objExcel->setActiveSheetIndex(0);
                // $where_store = array('status' => 1);
                if (!empty($param['keyword']) && $param['searchtype'] == 's_name') {
                    $where_store['name'] = array('like', '%' . $param['keyword'] . '%');
                }

                if ($param['system_session']['area_id']) {
                    $now_area = D('Area')->field(true)->where(array('area_id' => $param['system_session']['area_id']))->find();
                    if ($now_area['area_type'] == 3) {
                        $area_index = 'area_id';
                    } elseif ($now_area['area_type'] == 2) {
                        $area_index = 'city_id';
                    } elseif ($now_area['area_type'] == 1) {
                        $area_index = 'province_id';
                    }
                    $where_store[$area_index] = $param['system_session']['area_id'];
                    $stores = D('Merchant_store')->field('store_id')->where($where_store)->select();//
                } else {
                    $stores = D('Merchant_store')->field('store_id')->where($where_store)->select();
                }

                $store_ids = array();
                foreach ($stores as $row) {
                    $store_ids[] = $row['store_id'];
                }

                $condition_where = 'Where 1=1 ';
                if ($store_ids) {
                    $where['store_id'] = array('in', $store_ids);
                    $condition_where .= ' AND o.store_id in(' . implode(',', $store_ids) . ')';
                }

                if ($param['merchant_session']) {
                    $condition_where = "WHERE o.mer_id = " . $param['merchant_session']['mer_id'];
                }
                if ($param['store_session']) {
                    $condition_where = "WHERE o.store_id = " . $param['store_session']['store_id'];
                }

                if (!empty($param['keyword'])) {
                    if ($param['searchtype'] == 'real_orderid') {
                        $where['real_orderid'] = htmlspecialchars($param['keyword']);
                        $condition_where .= ' AND o.real_orderid =' . $where['real_orderid'];
                    } elseif ($param['searchtype'] == 'orderid') {
                        $where['orderid'] = htmlspecialchars($param['keyword']);
                        $condition_where .= ' AND o.orderid =' . $where['orderid'];
                    } elseif ($param['searchtype'] == 'name') {
                        $where['name'] = htmlspecialchars($param['keyword']);
                        $condition_where .= ' AND o.name =' . $where['name'];
                    } elseif ($param['searchtype'] == 'phone') {
                        $where['phone'] = htmlspecialchars($param['keyword']);
                        $condition_where .= ' AND o.phone =' . $where['phone'];
                    } elseif ($param['searchtype'] == 'third_id') {
                        $where['third_id'] = htmlspecialchars($param['keyword']);
                        $condition_where .=' AND p.third_id ="'.$where['third_id'].'"';
                    }
                }
                $status = isset($param['status']) ? intval($param['status']) : -1;
                $type = isset($param['type']) && $param['type'] ? $param['type'] : '';
                $sort = isset($param['sort']) && $param['sort'] ? $param['sort'] : '';
                if ($sort != 'DESC' && $sort != 'ASC') $sort = '';
                if ($type != 'price' && $type != 'pay_time') $type = '';
                $order_sort = '';
                if ($type && $sort) {
                    $order_sort .= $type . ' ' . $sort . ',';
                    $order_sort .= 'order_id DESC';
                } else {
                    $order_sort .= 'order_id DESC';
                }
                $pay_type = isset($param['pay_type']) && $param['pay_type'] ? $param['pay_type'] : '';

                if($pay_type){
                    if($pay_type=='balance'){
                        $condition_where .= " AND (`p`.`system_balance`<>0 OR `p`.`merchant_balance_pay` <> 0 )";
                    }elseif($pay_type=='offline'){
                        $condition_where .=' AND s.pay_type ="'.$pay_type.'"';
                    }else{
                        $condition_where .=' AND p.pay_type ="'.$pay_type.'"';
                    }
                }


                if (!empty($param['begin_time']) && !empty($param['end_time'])) {
                    if ($param['begin_time'] > $param['end_time']) {
                        return false;
                    }
                    $period = array(strtotime($param['begin_time'] . " 00:00:00"), strtotime($param['end_time'] . " 23:59:59"));
                    $where['_string'] .= ($where['_string'] ? ' AND ' : '') . " (create_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
                    $condition_where .= " AND  (o.create_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
                }

                if ($status != -1) {
                    $where['status'] = $status;
                    $condition_where .= ' AND o.status =' . $status;
                }
                //$count = D("Foodshop_order")->where($where)->count();
//              $sql_count = 'SELECT count(o.order_id) as count from pigcms_foodshop_order o LEFT JOIN (select a.* from pigcms_plat_order a,(select max(order_id) as order_id,business_id from pigcms_plat_order group by business_id) b where a.order_id=b.order_id and a.business_id=b.business_id) p on o.order_id  = p.business_id '.$condition_where;
                $sql_count = 'SELECT count(o.order_id) as count from ' . C('DB_PREFIX') . 'foodshop_order o LEFT JOIN  ' . C('DB_PREFIX') . 'plat_order  p on o.order_id  = p.business_id AND p.paid =1 LEFT JOIN ' . C('DB_PREFIX') . 'foodshop_order_detail as fd ON fd.order_id = o.order_id LEFT JOIN ' .C('DB_PREFIX').'store_order AS s ON o.order_id=s.business_id AND s.business_type="foodshop"' .  $condition_where;
                $count = M()->query($sql_count);
                $count = $count[0]['count'];
                set_time_limit(0);
                //require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';

                $length = ceil($count / 1000);

                for ($i = 0; $i < $length; $i++) {
                    $i && $objExcel->createSheet();
                    $objExcel->setActiveSheetIndex($i);

                    $objExcel->getActiveSheet()->setTitle(L_('第X1个一千个订单信息', ($i + 1)));
                    $objActSheet = $objExcel->getActiveSheet();
                    $objExcel->getActiveSheet()->getColumnDimension('A')->setAutoSize(true);
                    $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('D')->setAutoSize(true);
                    $objExcel->getActiveSheet()->getColumnDimension('E')->setAutoSize(true);
                    $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('G')->setAutoSize(true);
                    $objExcel->getActiveSheet()->getColumnDimension('H')->setAutoSize(true);
                    $objExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('J')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('L')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('M')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('N')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('O')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('P')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('Q')->setAutoSize(true);
                    $objExcel->getActiveSheet()->getColumnDimension('R')->setAutoSize(true);
                    $objExcel->getActiveSheet()->getColumnDimension('S')->setAutoSize(true);
                    $objExcel->getActiveSheet()->getColumnDimension('T')->setAutoSize(true);
                    $objExcel->getActiveSheet()->getColumnDimension('U')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('V')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('W')->setWidth(20);

                    $objActSheet->setCellValue('A1', L_('订单流水号'));
                    $objActSheet->setCellValue('B1', L_('商家名称'));
                    $objActSheet->setCellValue('C1', L_('店铺名称'));
                    $objActSheet->setCellValue('D1', L_('客户名称'));
                    $objActSheet->setCellValue('E1', L_('客户电话'));
                    $objActSheet->setCellValue('F1', L_('预定金'));
                    $objActSheet->setCellValue('G1', L_('预定时间'));
                    $objActSheet->setCellValue('H1', L_('桌台类型'));
                    $objActSheet->setCellValue('I1', L_('桌台名称'));
                    $objActSheet->setCellValue('J1', L_('商品名称'));
                    $objActSheet->setCellValue('K1', L_('规格/属性'));
                    $objActSheet->setCellValue('L1', L_('单价'));
                    $objActSheet->setCellValue('M1', L_('数量'));
                    $objActSheet->setCellValue('N1', L_('单位'));
                    $objActSheet->setCellValue('O1', L_('订单状态'));
                    $objActSheet->setCellValue('P1', L_('订单总价'));
                    $objActSheet->setCellValue('Q1', L_('余额支付'));
                    $objActSheet->setCellValue('R1', L_('平台在线支付'));
                    $objActSheet->setCellValue('S1', L_('商家余额支付'));
                    $objActSheet->setCellValue('T1', C('config.score_name'));
                    $objActSheet->setCellValue('U1', L_('实付总价'));
                    $objActSheet->setCellValue('V1', L_('支付时间'));
                    $objActSheet->setCellValue('W1', L_('支付方式'));
                    $objActSheet->setCellValue('X1', L_('支付类型'));
                    $objActSheet->setCellValue('Y1', L_('第三方流水号'));


                    //$objActSheet->setCellValue('R1', '支付情况');

                    //$sql = 'SELECT o.*,p.pay_type as pay_method,p.system_balance,p.merchant_balance_pay,p.pay_money,p.system_score_money,p.pay_time ,p.paid from pigcms_foodshop_order o LEFT JOIN  pigcms_plat_order  p on o.order_id  = p.business_id '.$condition_where .'limit '.($i*1000).',1000';

                    // $sql = 'SELECT o.*,fd.name as goods_name,fd.num as goods_num ,fd.spec as goods_spec,fd.unit as goods_unit,fd.price as goods_price,p.pay_type as pay_method,p.system_balance,p.merchant_balance_pay,p.pay_money,p.system_score_money,p.pay_time ,p.paid,p.third_id from '.C('DB_PREFIX').'foodshop_order o LEFT JOIN  '.C('DB_PREFIX').'plat_order  p on o.order_id  = p.business_id AND p.business_type="foodshop" AND p.paid =1 LEFT JOIN '.C('DB_PREFIX').'foodshop_order_detail as fd ON fd.order_id = o.order_id '.$condition_where.' ORDER BY o.order_id DESC ' .'limit '.($i*1000).',1000';

                    $sql = 'SELECT o.*,fd.name as goods_name,fd.num as goods_num ,fd.spec as goods_spec,fd.unit as goods_unit,fd.price as goods_price,p.pay_type as pay_method,p.system_balance,p.merchant_balance_pay,p.pay_money,p.system_score_money,p.pay_time ,p.paid,p.third_id,p.order_id as p_id,`s`.`pay_type` as s_pay_type,`s`.`offline_pay` as s_offline_pay from ' . C('DB_PREFIX') . 'foodshop_order o LEFT JOIN  ' . C('DB_PREFIX') . 'plat_order  p on o.order_id  = p.business_id AND p.business_type="foodshop" AND p.paid =1 LEFT JOIN  ' . C('DB_PREFIX') . 'store_order  s on o.order_id  = s.business_id AND s.business_type="foodshop" AND s.paid =1 LEFT JOIN ' . C('DB_PREFIX') . 'foodshop_order_detail as fd ON fd.order_id = o.order_id ' . $condition_where . ' ORDER BY o.order_id DESC ' . 'limit ' . ($i * 1000) . ',1000';

                    $list = M('')->query($sql);
                    //appdump(M());
                    $mer_ids = $store_ids = array();
                    foreach ($list as $l) {
                        $mer_ids[] = $l['mer_id'];
                        $store_ids[] = $l['store_id'];
                        $table_types[] = $l['table_type'];
                        $tids[] = $l['table_id'];

                    }


                    $type_list = array();
                    if ($table_types) {
                        $temp_type_list = M('Foodshop_table_type')->field(true)->where(array('id' => array('in', $table_types)))->select();
                        foreach ($temp_type_list as $tmp) {
                            $type_list[$tmp['id']] = $tmp;
                        }
                    }
                    $table_list = array();
                    if ($tids) {
                        $temp_table_list = M('Foodshop_table')->field(true)->where(array('id' => array('in', $tids)))->select();
                        foreach ($temp_table_list as $temp) {
                            $table_list[$temp['id']] = $temp;
                        }
                    }


                    $store_temp = $mer_temp = array();
                    if ($mer_ids) {
                        $merchants = D("Merchant")->where(array('mer_id' => array('in', $mer_ids)))->select();
                        foreach ($merchants as $m) {
                            $mer_temp[$m['mer_id']] = $m;
                        }
                    }
                    if ($store_ids) {
                        $merchant_stores = D("Merchant_store")->where(array('store_id' => array('in', $store_ids)))->select();
                        foreach ($merchant_stores as $ms) {
                            $store_temp[$ms['store_id']] = $ms;
                        }
                    }
                    foreach ($list as &$li) {
                        $li['merchant_name'] = isset($mer_temp[$li['mer_id']]['name']) ? $mer_temp[$li['mer_id']]['name'] : '';
                        $li['store_name'] = isset($store_temp[$li['store_id']]['name']) ? $store_temp[$li['store_id']]['name'] : '';

                        $li['table_type_name'] = isset($type_list[$li['table_type']]) ? $type_list[$li['table_type']]['name'] . '(' . $type_list[$li['table_type']]['min_people'] . '-' . $type_list[$li['table_type']]['max_people'] . '人)' : '';
                        $li['table_name'] = isset($table_list[$li['table_id']]) ? $table_list[$li['table_id']]['name'] : '';
                        $li['show_status'] = D('Foodshop_order')->status_list[$li['status']];
                    }

                    $tmp_id = 0;
                    $index_good = 0;
                    if (!empty($list)) {
                        $index = 1;
                        foreach ($list as $value) {
                            if ($tmp_id == $value['order_id']) {
                                $objActSheet->setCellValueExplicit('A' . $index, '');
                                $objActSheet->setCellValueExplicit('B' . $index, '');
                                $objActSheet->setCellValueExplicit('C' . $index, '');
                                $objActSheet->setCellValueExplicit('D' . $index, '');
                                $objActSheet->setCellValueExplicit('E' . $index, '');
                                $objActSheet->setCellValueExplicit('F' . $index, '');
                                $objActSheet->setCellValueExplicit('G' . $index, '');
                                $objActSheet->setCellValueExplicit('H' . $index, '');
                                $objActSheet->setCellValueExplicit('I' . $index, '');
                                $objActSheet->setCellValueExplicit('J' . $index, $value['goods_name']);
                                $objActSheet->setCellValueExplicit('K' . $index, $value['goods_spec']);
                                $objActSheet->setCellValueExplicit('L' . $index, $value['goods_price']);
                                $objActSheet->setCellValueExplicit('M' . $index, $value['goods_num']);
                                $objActSheet->setCellValueExplicit('N' . $index, $value['goods_unit']);
                                $objActSheet->setCellValueExplicit('O' . $index, '');
                                $objActSheet->setCellValueExplicit('P' . $index, '');
                                $objActSheet->setCellValueExplicit('Q' . $index, '');
                                $objActSheet->setCellValueExplicit('R' . $index, '');
                                $objActSheet->setCellValueExplicit('S' . $index, '');
                                $objActSheet->setCellValueExplicit('T' . $index, '');
                                $objActSheet->setCellValueExplicit('U' . $index, '');
                                $objActSheet->setCellValueExplicit('V' . $index, '');
                                $objActSheet->setCellValueExplicit('W' . $index, '');
                                $objActSheet->setCellValueExplicit('X' . $index, '');
                                $objActSheet->setCellValueExplicit('Y' . $index, '');
                                $index++;
                            } else {
                                $index++;
                                $objActSheet->setCellValueExplicit('A' . $index, $value['real_orderid']);
                                $objActSheet->setCellValueExplicit('B' . $index, $value['merchant_name']);
                                $objActSheet->setCellValueExplicit('C' . $index, $value['store_name']);
                                $objActSheet->setCellValueExplicit('D' . $index, $value['name']);
                                $objActSheet->setCellValueExplicit('E' . $index, $value['phone']);
                                $objActSheet->setCellValueExplicit('F' . $index, $value['book_price']);
                                $objActSheet->setCellValueExplicit('G' . $index, $value['book_time'] ? date('Y-m-d H:i:s', $value['book_time']) : '');
                                $objActSheet->setCellValueExplicit('H' . $index, $value['table_type_name']);
                                $objActSheet->setCellValueExplicit('I' . $index, $value['table_name']);
                                $objActSheet->setCellValueExplicit('J' . $index, '');
                                $objActSheet->setCellValueExplicit('K' . $index, '');
                                $objActSheet->setCellValueExplicit('L' . $index, '');
                                $objActSheet->setCellValueExplicit('M' . $index, '');
                                $objActSheet->setCellValueExplicit('N' . $index, '');
                                $objActSheet->setCellValueExplicit('O' . $index, $value['show_status']);
                                $objActSheet->setCellValueExplicit('P' . $index, floatval($value['total_price']));

                                $plat_order_list = D('Plat_order')->field(true)->where(array('business_id' => $value['order_id'], 'business_type' => 'foodshop', 'paid' => 1))->order('order_id ASC')->select();
                                $system_balance = 0;
                                $pay_money = 0;
                                $merchant_balance_pay = 0;
                                $system_score_money = 0;
                                foreach ($plat_order_list as $plat) {
                                    $system_balance += $plat['system_balance'];
                                    $pay_money += $plat['pay_money'];
                                    $merchant_balance_pay += $plat['merchant_balance_pay'];
                                    $system_score_money += $plat['system_score_money'];
                                }

                                $objActSheet->setCellValueExplicit('Q' . $index, floatval($system_balance));
                                $objActSheet->setCellValueExplicit('R' . $index, floatval($pay_money));
                                $objActSheet->setCellValueExplicit('S' . $index, floatval($merchant_balance_pay));
                                $objActSheet->setCellValueExplicit('T' . $index, floatval($system_score_money));

                                $objActSheet->setCellValueExplicit('U' . $index, floatval($value['price']));


                                $objActSheet->setCellValueExplicit('V' . $index, $value['pay_time'] ? date('Y-m-d H:i:s', $value['pay_time']) : '');
                                if (!$value['p_id'] && $value['pay_type'] == 1) {
                                    if ($value['s_pay_type'] == 'offline') {
                                        if ($value['s_offline_pay'] && ($store_pay = D('Store_pay')->field('name')->where(array('id' => $value['s_offline_pay']))->find())) {
                                            $pay_type = $store_pay['name'];
                                        } else {
                                            $pay_type = L_('现下支付');
                                        }
                                    } else {
                                        $pay_type = D('Pay')->get_pay_name($value['pay_type'], 0, 1);
                                    }
                                    $objActSheet->setCellValueExplicit('W' . $index, $pay_type);
                                } else {
                                    $objActSheet->setCellValueExplicit('W' . $index, D('Pay')->get_pay_name($value['pay_method'], $value['is_mobile_pay'], $value['paid']));
                                }
                                $objActSheet->setCellValueExplicit('X' . $index, L_('支付定金'));
                                $objActSheet->setCellValueExplicit('Y' . $index, $value['third_id']);
                                $index++;
                                $index_good = 0;

                                //商品
                                if ($value['goods_name']) {
                                    $objActSheet->setCellValueExplicit('A' . $index, '');
                                    $objActSheet->setCellValueExplicit('B' . $index, '');
                                    $objActSheet->setCellValueExplicit('C' . $index, '');
                                    $objActSheet->setCellValueExplicit('D' . $index, '');
                                    $objActSheet->setCellValueExplicit('E' . $index, '');
                                    $objActSheet->setCellValueExplicit('F' . $index, '');
                                    $objActSheet->setCellValueExplicit('G' . $index, '');
                                    $objActSheet->setCellValueExplicit('H' . $index, '');
                                    $objActSheet->setCellValueExplicit('I' . $index, '');
                                    $objActSheet->setCellValueExplicit('J' . $index, $value['goods_name']);
                                    $objActSheet->setCellValueExplicit('K' . $index, $value['goods_spec']);
                                    $objActSheet->setCellValueExplicit('L' . $index, $value['goods_price']);
                                    $objActSheet->setCellValueExplicit('M' . $index, $value['goods_num']);
                                    $objActSheet->setCellValueExplicit('N' . $index, $value['goods_unit']);
                                    $objActSheet->setCellValueExplicit('O' . $index, '');
                                    $objActSheet->setCellValueExplicit('P' . $index, '');
                                    $objActSheet->setCellValueExplicit('Q' . $index, '');
                                    $objActSheet->setCellValueExplicit('R' . $index, '');
                                    $objActSheet->setCellValueExplicit('S' . $index, '');
                                    $objActSheet->setCellValueExplicit('T' . $index, '');
                                    $objActSheet->setCellValueExplicit('U' . $index, '');
                                    $objActSheet->setCellValueExplicit('V' . $index, '');
                                    $objActSheet->setCellValueExplicit('W' . $index, '');
                                    $objActSheet->setCellValueExplicit('X' . $index, '');
                                    $objActSheet->setCellValueExplicit('Y' . $index, '');
                                    $index_good = 1;
                                    $index++;
                                }
                            }
                            $tmp_id = $value['order_id'];

                        }
                    }
                    sleep(2);
                }
                break;
            case 'group':
                $title = C('config.group_alias_name') . L_('账单');

                // 设置文档基本属性
                $objProps->setCreator($title);
                $objProps->setTitle($title);
                $objProps->setSubject($title);
                $objProps->setDescription($title);
                // 设置当前的sheet
                $objExcel->setActiveSheetIndex(0);

                if ($param['merchant_session']) {
                    $condition_where = "WHERE o.mer_id = " . $param['merchant_session']['mer_id'];
                } else {
                    $condition_where = "WHERE 1=1";
                }
                if ($param['store_session']) {
                    $condition_where = "WHERE o.store_id = " . $param['store_session']['store_id'];
                }
                if (!empty($param['keyword'])) {
                    if ($param['searchtype'] == 'real_orderid') {
                        $condition_where .= " AND `o`.`real_orderid`='" . htmlspecialchars($param['keyword']) . "'";
                    } elseif ($param['searchtype'] == 'orderid') {
                        $where['orderid'] = htmlspecialchars($param['keyword']);
                        $tmp_result = M('Tmp_orderid')->where(array('orderid' => $param['keyword']))->find();
                        $condition_where .= " AND  `o`.`order_id`='" . htmlspecialchars($tmp_result['order_id']) . "'";
                    } elseif ($param['searchtype'] == 'name') {
                        $condition_where .= " AND  `u`.`nickname` like '%" . htmlspecialchars($param['keyword']) . "%'";
                    } elseif ($param['searchtype'] == 'phone') {
                        $condition_where .= " AND `u`.`phone`='" . htmlspecialchars($param['keyword']) . "'";
                    } elseif ($param['searchtype'] == 's_name') {
                        $condition_where .= " AND `g`.`s_name` like '%" . htmlspecialchars($param['keyword']) . "%'";
                    } elseif ($param['searchtype'] == 'third_id') {
                        $condition_where .= " AND `o`.`third_id` ='" . $param['keyword'] . "'";
                    } elseif ($param['searchtype'] == 'm_name') {
                        $condition_where .= " AND `m`.`name` like '%" . htmlspecialchars($param['keyword']) . "%'";
                    }
                }
                if ($param['system_session']['area_id']) {
                    $now_area = D('Area')->field(true)->where(array('area_id' => $param['system_session']['area_id']))->find();
                    if ($now_area['area_type'] == 3) {
                        $area_index = 'area_id';
                    } elseif ($now_area['area_type'] == 2) {
                        $area_index = 'city_id';
                    } elseif ($now_area['area_type'] == 1) {
                        $area_index = 'province_id';
                    }
                    $condition_where .= " AND `m`.`{$area_index}`={$param['system_session']['area_id']}";
                }

                $group_id = isset($param['group_id']) ? intval($param['group_id']) : -1;
                $status = isset($param['status']) ? intval($param['status']) : -1;
                $type = isset($param['type']) && $param['type'] ? $param['type'] : '';
                $sort = isset($param['sort']) && $param['sort'] ? $param['sort'] : '';
                $pay_type = isset($param['pay_type']) && $param['pay_type'] ? $param['pay_type'] : '';
                if ($sort != 'DESC' && $sort != 'ASC') $sort = '';
                if ($type != 'price' && $type != 'pay_time') $type = '';
                $order_sort = '';
                if ($type && $sort) {
                    $order_sort .= 'o.' . $type . ' ' . $sort . ',';
                    $order_sort .= 'o.order_id DESC';
                } else {
                    $order_sort .= 'o.order_id DESC';
                }

                if ($status != -1 && $status != 8) {
                    $condition_where .= " AND `o`.`status`={$status}";
                } elseif ($status == 8) {
                    $condition_where .= " AND `o`.`paid`=0";
                }
                if ($group_id > 0) {
                    $condition_where .= " AND `o`.`group_id`={$group_id}";
                }
                if ($pay_type) {
                    if ($pay_type == 'balance') {
                        $condition_where .= " AND (`o`.`balance_pay`<>0 OR `o`.`merchant_balance` <> 0 )";
                    } else if ($pay_type == 'weixin') {
                        $condition_where .= " AND `o`.`pay_type` IN ('weixin','wechat')";
                    } else {
                        $condition_where .= " AND `o`.`pay_type`='{$pay_type}'";
                    }
                }

                if (!empty($param['begin_time']) && !empty($param['end_time'])) {
                    if ($param['begin_time'] > $param['end_time']) {
                        return false;
                        //$this->error_tips("结束时间应大于开始时间");
                    }

                    $period = array(strtotime($param['begin_time'] . " 00:00:00"), strtotime($param['end_time'] . " 23:59:59"));

                    $param['searchTimeType'] = $param['searchTimeType'] ? $param['searchTimeType'] : 'add_time';
                    $condition_where .= " AND (o.".$param['searchTimeType']." BETWEEN ".$period[0].' AND '.$period[1].")";
                    //$condition_where['_string']=$time_condition;

                }

                if ($param['area_id'] && $param['area_id'] != 'undefined') {
                    $condition_where .= " AND m.area_id={$param['area_id']}";
                } else if ($param['city_idss'] && $param['city_idss'] != 'undefined') {
                    $condition_where .= " AND m.city_id={$param['city_idss']}";
                } else if ($param['province_idss'] && $param['province_idss'] != 'undefined') {
                    $condition_where .= " AND m.province_id={$param['province_idss']}";
                }


                $sql = "SELECT count(order_id) as count FROM " . C('DB_PREFIX') . "group_order AS o  LEFT JOIN " . C('DB_PREFIX') . "group g ON g.group_id = o.group_id  LEFT JOIN " . C('DB_PREFIX') . "merchant AS m ON `o`.`mer_id`=`m`.`mer_id` LEFT JOIN " . C('DB_PREFIX') . "user u ON u.uid = o.uid " . $condition_where . " ORDER BY o.order_id DESC ";
                $count = D()->query($sql);

                $length = ceil($count[0]['count'] / 1000);
                $m_group_specifications = M('Group_specifications');
                $m_merchant_money_list = M('Merchant_money_list');
                $m_user_spread_list = M('User_spread_list');

                $csv_head = array(
                    L_('商家ID'),
                    L_('商家电话'),
                    L_('订单编号'),
                    L_('订单流水号'),
                    L_('团购名称'),
                    L_('商家名称'),
                    L_('客户姓名'),
                    L_('客户电话'),

                    L_('单价'),
                    L_('数量'),

                    L_('订单总价'),
                    L_('平台余额'),
                    L_('商家余额'),
                    L_('在线支付金额'),
                    L_('平台') . C('config.score_name'),
                    L_('平台优惠券'),
                    L_('商家优惠券'),
                    L_('商家折扣'),
                    L_('支付时间'),
                    L_('收货人'),
                    L_('收货人电话'),
                    L_('地址'),
                    L_('订单状态'),
                    L_('支付情况'),

                    L_('验证消费时间'),
                    L_('平台给商家结款'),
                    L_('第一级分享佣金'),
                    L_('第二级分享佣金'),

                    L_('第三方支付流水号'),
                    L_('备注'),
                    L_('自定义填写信息'),
                );
                $csv_data = [];

                for ($i = 0; $i < $length; $i++) {
                    $sql = "SELECT o.*, m.name AS merchant_name,u.nickname as username,u.phone as user_phone,g.name,g.s_name,m.phone as mer_phone FROM " . C('DB_PREFIX') . "group_order AS o  LEFT JOIN " . C('DB_PREFIX') . "group g ON g.group_id = o.group_id  LEFT JOIN " . C('DB_PREFIX') . "merchant AS m ON `o`.`mer_id`=`m`.`mer_id` LEFT JOIN " . C('DB_PREFIX') . "user u ON u.uid = o.uid " . $condition_where . " ORDER BY o.order_id DESC LIMIT " . $i * 1000 . ",1000";
                    $result_list = D()->query($sql);

                    if (!empty($result_list)) {
                        foreach ($result_list as $value) {
                            // 判断是否存在规格，获取规格，赋予到 团购名称后面
                            if ($value['specifications_id']) {
                                $specifications_name = $m_group_specifications->where(array('specifications_id' => $value['specifications_id']))->getField('specifications_name');
                                $s_name = $value['s_name'] . '(' . $specifications_name . ')';
                            } else {
                                $s_name = $value['s_name'];
                            }
                            // 处理下手机号问题
                            $user_phone = $value['phone'] ? $value['phone'] : $value['user_phone'];
                            // 平台给商家结款金额
                            $system_take = $m_merchant_money_list->where(array('order_id' => $value['real_orderid'], 'type' => 'group'))->getField('money');
                            // 用户推广列表
                            // 第一级分享佣金
                            $spread_money = $m_user_spread_list->where(array('order_id' => $value['order_id'], 'order_type' => 'group', 'spread_uid' => 0))->getField('money');
                            // 第二级分享佣金
                            $spread_info = $m_user_spread_list->where(array('order_id' => $value['order_id'], 'order_type' => 'group', 'spread_uid' => array('gt', 0)))->order('pigcms_id DESC')->field('money')->find();
                            $spread_second_money = $spread_info['money'];

                            // 处理填写项
                            $write_info = unserialize($value['write_field']);
                            $write_msg = '';
                            if ($write_info) {
                                foreach($write_info as $key=>$val){
                                    if(!empty($val['value'])){
                                        $write_msg .= $val['name'] .':'.$val['value'] .';';
                                    }
                                }
                            }

                            $csv_data[] = [
                                "\t" . $value['mer_id'],
                                "\t" . $value['mer_phone'],
                                "\t" . $value['real_orderid'],
                                "\t" . ($value['orderid'] ? 'group_'.$value['orderid'] : ''),
                                "\t" . $s_name,
                                "\t" . $value['merchant_name'],
                                "\t" . $value['username'],
                                "\t" . $user_phone,
                                "\t" . $value['price'],
                                "\t" . $value['num'],
                                "\t" . $value['total_money'],
                                "\t" . $value['balance_pay'],
                                "\t" . $value['merchant_balance'],
                                "\t" . $value['payment_money'],
                                "\t" . $value['score_reducte'],
                                "\t" . $value['coupon_price'],
                                "\t" . $value['card_price'],
                                "\t" . ($value['card_discount'] != 0 && $value['card_discount'] != 10 ? floatval($value['card_discount']) . L_('折') : ''),
                                "\t" . ($value['pay_time'] ? date('Y-m-d H:i:s', $value['pay_time']) : ''),
                                "\t" . $value['contact_name'],
                                "\t" . $value['phone'],
                                "\t" . $value['adress'],
                                "\t" . $this->get_order_status($value),
                                "\t" . D('Pay')->get_pay_name($value['pay_type'], $value['is_mobile_pay'], $value['paid']),
                                "\t" . ($value['use_time'] ? date('Y-m-d H:i:s', $value['use_time']) : ''),
                                "\t" . ($system_take > 0 ? $system_take : 0),
                                "\t" . ($spread_money > 0 ? $spread_money : 0),
                                "\t" . ($spread_second_money > 0 ? $spread_second_money : 0),
                                "\t" . $value['third_id'],
                                "\t" . $value['note_info'],
                                "\t" . $write_msg,
                            ];
                        }
                    }
                }
                $filename = date("Y-m-d", time()) . '-' . $param['rand_number'] . '.csv';
                $csvFileName = './runtime/' . $filename;
                $this->putCsv($csvFileName, $csv_data, $csv_head);
                return true;
                break;
            case 'appoint':
                $title = C('config.appoint_alias_name') . L_('账单');
                // 设置文档基本属性
                $objProps->setCreator($title);
                $objProps->setTitle($title);
                $objProps->setSubject($title);
                $objProps->setDescription($title);
                // 设置当前的sheet
                $objExcel->setActiveSheetIndex(0);
                if ($param['appoint_id'] > 0) {
                    $where['a.appoint_id'] = intval($param['appoint_id']);
                }
                $where['a.mer_id'] = array('neq', 0);
                if (!empty($param['keyword'])) {
                    if ($param['searchtype'] == 'order_id') {
                        $where['a.order_id'] = $param['keyword'];
                    } elseif ($param['searchtype'] == 'orderid') {
                        $where['orderid'] = htmlspecialchars($param['keyword']);
                        $tmp_result = M('Tmp_orderid')->where(array('orderid' => $where['orderid']))->find();
                        unset($where['orderid']);
                        $where['a.order_id'] = $tmp_result['order_id'];
                    } elseif ($param['searchtype'] == 'name') {
                        $where['u.username'] = htmlspecialchars($param['keyword']);
                    } elseif ($param['searchtype'] == 'phone') {
                        $where['u.phone'] = htmlspecialchars($param['keyword']);
                    } elseif ($param['searchtype'] == 'third_id') {
                        $where['a.third_id'] = $param['keyword'];
                    } elseif ($param['searchtype'] == 'real_orderid') {
                        $where['a.real_orderid'] = $param['keyword'];
                    }
                }
                $province_id = intval($param['province_id']);
                $city_id = intval($param['city_id']);
                $area_id = intval($param['area_id']);
                if ($param['system_session']['area_id']) {
                    $now_area = D('Area')->field(true)->where(array('area_id' => $param['system_session']['area_id']))->find();
                    if ($now_area['area_type'] == 3) {
                        $area_id = $param['system_session']['area_id'];
                        $city_id = $now_area['area_pid'];
                        $temp_area = D('Area')->field(true)->where(array('area_id' => $city_id))->find();
                        $province_id = $temp_area['area_pid'];
                    } elseif ($now_area['area_type'] == 2) {
                        $city_id = $param['system_session']['area_id'];
                        $province_id = $now_area['area_pid'];
                    } elseif ($now_area['area_type'] == 1) {
                        $province_id = $param['system_session']['area_id'];
                    }
                }
                if ($province_id) {
                    $where_store['province_id'] = $province_id;
                }
                if ($city_id) {
                    $where_store['city_id'] = $city_id;
                }
                if ($area_id) {
                    $where_store['area_id'] = $area_id;
                }
                $stores = D('Merchant_store')->field('store_id')->where($where_store)->select();
                foreach ($stores as $row) {
                    $header_arr[]=$row['store_id'];
                }

                $where['a.store_id'] = array('in', $header_arr);
                if ($param['merchant_session']) {
                    $where['a.mer_id'] = $param['merchant_session']['mer_id'];
                }
                if ($param['store_session']) {
                    $where['a.store_id'] = $param['store_session']['store_id'];
                    unset($where['a.appoint_id']);
                    //$condition_where = "WHERE o.store_id = " . $param['store_session']['store_id'];
                }
                $pay_type = isset($param['pay_type']) && $param['pay_type'] ? $param['pay_type'] : '';
                $project_id = isset($_GET['project_id']) && $_GET['project_id'] ? $_GET['project_id'] : '';
                $project_city_id = isset($_GET['project_city_id']) && $_GET['project_city_id'] ? $_GET['project_city_id'] : '';
                if($project_id){
                    $where['a.project_id'] = $project_id;
                }
                if($project_city_id){
                    $where['a.project_city_id'] = $project_city_id;
                }
                if ($pay_type) {
                    if ($pay_type == 'balance') {
                        $where['_string'] = "(`a`.`balance_pay`<>0 OR `a`.`merchant_balance` <> 0 OR a.product_merchant_balance <> 0 OR a.product_balance_pay <> 0 )";
                    } else {
                        $where['a.pay_type|a.product_pay_type'] = $pay_type;
                    }
                }
                $database_appoint = D('Appoint');
                $database_order = D('Appoint_order');
                $now_appoint = $database_appoint->field(true)->where(array('appoint_id' => $param['appoint_id']))->find();
                if (!empty($param['begin_time']) && !empty($param['end_time'])) {
                    if ($param['begin_time'] > $param['end_time']) {
                        return false;
                    }
                    $period = array(strtotime($param['begin_time']." 00:00:00"),strtotime($param['end_time']." 23:59:59"));
                    $param['searchTimeType'] = $param['searchTimeType'] ? $param['searchTimeType'] : 'order_time';
                    $where['_string'] .= (empty($where['_string'])?'':' AND ')." (a.".$param['searchTimeType']." BETWEEN ".$period[0].' AND '.$period[1].")";
                }

                // 服务状态
                if (isset($param['service_status']) && $param['service_status'] !=-1 ) {
                    $where['a.service_status'] = $param['service_status'];
                }
                // 支付状态
                $paid = isset($param['paid']) && $param['paid'] ? $param['paid'] : -1;
                if ($paid == -10) {
                    $where['_string'] .= (empty($where['_string'])?'':' AND ')." (a.paid=1 AND a.service_status<=0)";
                } elseif ($paid == 1) {
                    $where['_string'] .= (empty($where['_string'])?'':' AND ')." ((a.paid=1 AND a.payment_status!=1) OR (a.paid=1 AND a.service_status>0))";
                } elseif ($paid==3)  {
                    $where['_string'] .= (empty($where['_string'])?'':' AND ')." ((a.paid=3) OR (a.is_del>0))";
                } elseif ($paid==-2)  {
                    $where['a.paid'] = 0;
                } elseif ($paid>=0)  {
                    $where['a.paid'] = $paid;
                }

                $order_count = $database_order->join('AS a left join ' . C('DB_PREFIX') . 'appoint ap on a.appoint_id =ap.appoint_id left join ' . C('DB_PREFIX') . 'user u ON a.uid = u.uid left join ' . C('DB_PREFIX') . 'merchant_store s ON s.store_id = a.store_id ')
                    ->where($where)->count();
                $length = ceil($order_count / 1000);
                for ($i = 0; $i < $length; $i++) {
                    $i && $objExcel->createSheet();
                    $objExcel->setActiveSheetIndex($i);

                    $objExcel->getActiveSheet()->setTitle(L_('第X1个一千个订单信息', ($i + 1)));
                    $objActSheet = $objExcel->getActiveSheet();

                    $objActSheet->setCellValue('A1', L_('订单编号'));
                    $objActSheet->setCellValue('B1', L_('订单流水号'));
                    $objActSheet->setCellValue('C1', L_('定金'));
                    $objActSheet->setCellValue('D1', L_('总价'));
                    $objActSheet->setCellValue('E1', L_('类型'));
                    $objActSheet->setCellValue('F1', L_('用户昵称'));
                    $objActSheet->setCellValue('G1', L_('手机号码'));
                    $objActSheet->setCellValue('H1', L_('订单状态'));
                    $objActSheet->setCellValue('I1', L_('服务状态'));
                    $objActSheet->setCellValue('J1', L_('平台余额支付'));
                    $objActSheet->setCellValue('K1', L_('商家会员卡支付'));
                    $objActSheet->setCellValue('L1', L_('在线支付金额'));
                    $objActSheet->setCellValue('M1', L_('下单时间'));
                    $objActSheet->setCellValue('N1', L_('支付时间'));
                    $objActSheet->setCellValue('O1', L_('支付方式'));
                    $objActSheet->setCellValue('P1', L_('项目名称'));
                    $objActSheet->setCellValue('Q1', L_('项目城市'));
                    $objActSheet->setCellValue('R1', L_('服务名称'));
                    $objActSheet->setCellValue('S1', L_('尾款支付状态'));
                    $objActSheet->setCellValue('T1', L_('尾款支付时间'));
                    $objActSheet->setCellValue('U1', L_('用户地址'));
                    $objActSheet->setCellValue('V1', L_('预约时间'));
                    $objActSheet->setCellValue('W1', L_('店铺名称'));
                    $objActSheet->setCellValue('X1', L_('商家名称'));
                    $objActSheet->setCellValue('Y1', L_('验证时间'));
                    $objActSheet->setCellValue('Z1', L_('定金流水号'));
                    $objActSheet->setCellValue('AA1', L_('备注'));
                    $objActSheet->setCellValue('AB1', L_('定金支付方式'));
                    $objActSheet->setCellValue('AC1', L_('尾款支付方式'));
                    $objActSheet->setCellValue('AD1', L_('联系人'));
                    $objActSheet->setCellValue('AE1', L_('联系人电话'));
                    $objActSheet->setCellValue('AF1', L_('第三方支付流水号'));
                    $objActSheet->setCellValue('AG1', L_('平台结算给商家金额（元）'));
                    $objActSheet->setCellValue('AH1', L_('订单ID'));
                    $objActSheet->setCellValue('AI1', L_('用户留言'));

                    $order_list = $database_order->field('a.*,ap.appoint_name,a.appoint_type,a.mer_id,u.uid,u.nickname,a.order_time,a.pay_time,u.phone,s.name as store_name,s.adress as store_adress,a.verify_time,a.user_pay_time')
                        ->join('AS a left join ' . C('DB_PREFIX') . 'appoint ap on a.appoint_id =ap.appoint_id left join ' . C('DB_PREFIX') . 'user u ON a.uid = u.uid left join ' . C('DB_PREFIX') . 'merchant_store s ON s.store_id = a.store_id ')
                        ->where($where)->limit(($i * 1000) . ',1000')->order('`order_id` DESC')->select();
                    foreach($order_list as $k=> &$v){
                        $merchant= M('Merchant')->where(array('mer_id'=>$v['mer_id']))->find();
                        $v['mername'] = $merchant['name'];
                        $merchantMoney = M('Merchant_money_list')
                            ->where([
                                'mer_id'   => $v['mer_id'],
                                'order_id' => $v['order_id'],
                                'type'     => 'appoint',
                                'income'   => 1
                            ])->field('money')->find();
                        $v['merchant_money'] = $merchantMoney['money'] ?: 0;//平台结算给商家的钱
                    }
                    $result_list = $order_list;
                    foreach($result_list as $k=>&$v){
                        $cue_info = unserialize($v['cue_field']);
                        $v['info'] = $cue_info[0];
                    }
                    if (!empty($result_list)) {
                        $index = 2;
                        foreach ($result_list as $value) {
                            if($value['real_orderid'] != ''){
                                $objActSheet->setCellValueExplicit('A' . $index, $value['real_orderid']);
                            }else{
                                $objActSheet->setCellValueExplicit('A' . $index, $value['order_id']);
                            }
                            $objActSheet->setCellValueExplicit('B' . $index, $value['orderid']);
                            if ($value['product_id'] > 0) {
                                $objActSheet->setCellValueExplicit('C' . $index, floatval($value['product_payment_price']));
                            } else {
                                $objActSheet->setCellValueExplicit('C' . $index, floatval($value['payment_money']));
                            }
                            if ($value['product_price'] > 0) {
                                $objActSheet->setCellValueExplicit('D' . $index, floatval($value['product_price']));
                            } else {
                                $objActSheet->setCellValueExplicit('D' . $index, floatval($value['appoint_price']));
                            }
                            if ($value['type'] == 1) {
                                $objActSheet->setCellValueExplicit('E' . $index, L_('自营'));
                            } else {
                                $objActSheet->setCellValueExplicit('E' . $index, L_('商家'));
                            }
                            $objActSheet->setCellValueExplicit('F' . $index, $value['nickname'] . ' ');
                            $objActSheet->setCellValueExplicit('G' . $index, $value['phone'] . ' ');
                            if ($value['paid'] == 0) {
                                $objActSheet->setCellValueExplicit('H' . $index, L_('未支付'));
                            } elseif ($value['is_del']) {
                                $objActSheet->setCellValueExplicit('H' . $index, L_('已取消'));
                            } elseif ($value['paid'] == 1) {
                                if ((floatval($value['payment_money']) || floatval($value['product_payment_price'])) && $value['payment_status']!=1) {
                                    $objActSheet->setCellValueExplicit('H' . $index, L_('已支付'));
                                }elseif (empty(floatval($value['payment_money'])) && empty(floatval($value['product_payment_price'])) && $value['is_initiative']<=0) {
                                    $objActSheet->setCellValueExplicit('H' . $index, L_('待支付'));
                                } elseif ($value['service_status']<=0 && $value['is_initiative']<=0) {
                                    $objActSheet->setCellValueExplicit('H' . $index, L_('定金已支付 | 尾款待支付'));
                                } elseif ($value['service_status']>0 && $value['is_initiative']<=0) {
                                    $objActSheet->setCellValueExplicit('H' . $index, L_('已服务 | 尾款未支付'));
                                } else {
                                    $objActSheet->setCellValueExplicit('H' . $index, L_('已支付'));
                                }
                            } elseif ($value['paid'] == 2) {
                                $objActSheet->setCellValueExplicit('H' . $index, L_('已退款'));
                            } elseif($value['paid']==3){
                                $objActSheet->setCellValueExplicit('H' . $index, '用户已取消');
                            }
                            if ($value['service_status'] == 0) {
                                $objActSheet->setCellValueExplicit('I' . $index, L_('未服务'));
                            } elseif ($value['service_status'] == 1) {
                                $objActSheet->setCellValueExplicit('I' . $index, L_('已服务'));
                            } elseif ($value['service_status'] == 2) {
                                $objActSheet->setCellValueExplicit('I' . $index, L_('已评价'));
                            }
                            $objActSheet->setCellValueExplicit('J' . $index, floatval($value['balance_pay']+$value['product_balance_pay']));
                            $objActSheet->setCellValueExplicit('K' . $index, floatval($value['merchant_balance']));


                            if ($value['paid'] >= 1 && $value['pay_money']) {
                                $pay_money = $value['pay_money'] + $value['user_pay_money'];
                            } elseif ($value['paid'] >= 1 && $value['product_payment_price']) {
                                $pay_money = $value['product_payment_price'] + $value['user_pay_money'];
                            } else {
                                $pay_money = 0;
                            }

                            $objActSheet->setCellValueExplicit('L' . $index, floatval($pay_money));
                            $objActSheet->setCellValueExplicit('M' . $index, $value['order_time'] ? date('Y-m-d H:i:s', $value['order_time']) : '');
                            $objActSheet->setCellValueExplicit('N' . $index, $value['pay_time'] ? date('Y-m-d H:i:s', $value['pay_time']) : '');
                            $objActSheet->setCellValueExplicit('O' . $index, D('Pay')->get_pay_name($value['pay_type'] ?: $value['product_pay_type'], $value['is_mobile_pay'], $value['paid']));
                            $objActSheet->setCellValueExplicit('P' . $index,  $value['project_id']);
                            $objActSheet->setCellValueExplicit('Q' . $index,  $value['project_city_id']);
                            $cat_info = D('Appoint_category')->field('cat_name')->where(array('cat_id'=>$value['cat_id']))->find();
                            $objActSheet->setCellValueExplicit('R' . $index,  $value['appoint_name']);

                            if($value['paid'] == 0){
                               $objActSheet->setCellValueExplicit('S' . $index, L_('未支付'));
                            }elseif($value['paid'] == 1){
                                if ($value['payment_status']!=1) {
                                    $objActSheet->setCellValueExplicit('S' . $index, L_('已支付'));
                                } elseif ($value['service_status']<=0 && $value['is_initiative']<=0) {
                                    $objActSheet->setCellValueExplicit('S' . $index, L_('定金已支付 | 尾款待支付'));
                                } elseif ($value['service_status']>0 && $value['is_initiative']<=0) {
                                    $objActSheet->setCellValueExplicit('S' . $index, L_('已服务 | 尾款未支付'));
                                } else {
                                    $objActSheet->setCellValueExplicit('S' . $index, L_('已支付'));
                                }
                            }elseif($value['paid'] == 2){
                               $objActSheet->setCellValueExplicit('S' . $index, L_('已退款'));
                            } elseif($value['paid']==3){
                                if ($value['payment_status']!=1) {
                                    $objActSheet->setCellValueExplicit('S' . $index, L_('已支付'));
                                } elseif ($value['service_status']<=0 && $value['is_initiative']<=0) {
                                    $objActSheet->setCellValueExplicit('S' . $index, L_('定金已支付 | 尾款待支付'));
                                } elseif ($value['service_status']>0 && $value['is_initiative']<=0) {
                                    $objActSheet->setCellValueExplicit('S' . $index, L_('已服务 | 尾款未支付'));
                                } else {
                                    $objActSheet->setCellValueExplicit('S' . $index, L_('已支付'));
                                }
                            }

                            if ($value['user_pay_time'] && $value['is_initiative']>0) {
                                $user_pay_time_info = date('Y-m-d H:i:s', $value['user_pay_time']);
                            } else {
                                $user_pay_time_info ='';
                            }
                            $objActSheet->setCellValueExplicit('T' . $index,  $user_pay_time_info);
                            $objActSheet->setCellValueExplicit('U' . $index, $value['info']['address']);
                            $objActSheet->setCellValueExplicit('V' . $index, $value['merchant_assign_time'] ? date('Y-m-d H:i:s', $value['merchant_assign_time']) : '');
                            $objActSheet->setCellValueExplicit('W' . $index,  $value['store_name']);
                            $objActSheet->setCellValueExplicit('X' . $index,  $value['mername']);

                            if ($value['is_initiative']>0 && $value['payment_status'] == 1 && $value['user_pay_time']>0) {
                                $verify_time = $value['user_pay_time'];
                            } else {
                                $verify_time = $value['verify_time'];
                            }

                            $objActSheet->setCellValueExplicit('Y' . $index,  $verify_time? date('Y-m-d H:i:s', $verify_time) : '');
                            if($value['paid'] == 0){
                                $objActSheet->setCellValueExplicit('Z' . $index,  0);
                            }else{
                                $objActSheet->setCellValueExplicit('Z' . $index,  $value['orderids']);
                            }
                            $objActSheet->setCellValueExplicit('AA' . $index,  $value['note']);
                            if($value['pay_type'] == 'weixin'){
                                $objActSheet->setCellValueExplicit('AB' . $index,  '微信');
                            }elseif($value['pay_type'] == 'alipayh5'){
                                $objActSheet->setCellValueExplicit('AB' . $index,  '支付宝');
                            }else{
                                $objActSheet->setCellValueExplicit('AB' . $index,  '');
                            }
                            if($value['product_pay_type'] == 'weixin'){
                                $objActSheet->setCellValueExplicit('AC' . $index,  '微信');
                            }elseif($value['product_pay_type'] == 'alipayh5'){
                                $objActSheet->setCellValueExplicit('AC' . $index,  '支付宝');
                            }else{
                                $objActSheet->setCellValueExplicit('AC' . $index,  '');
                            }
                            $objActSheet->setCellValueExplicit('AD' . $index,  $value['nickname']);
                            $objActSheet->setCellValueExplicit('AE' . $index,  $value['info']['phone']);
                            if($value['third_id']){
                                $objActSheet->setCellValueExplicit('AF' . $index,  $value['third_id']);
                            }elseif($value['product_third_id']){
                                $objActSheet->setCellValueExplicit('AF' . $index,  $value['product_third_id']);
                            }else{
                                $objActSheet->setCellValueExplicit('AF' . $index,  '');
                            }
                            $objActSheet->setCellValueExplicit('AG' . $index, $value['merchant_money']);
                            $objActSheet->setCellValueExplicit('AH' . $index, $value['order_id']);
                            $objActSheet->setCellValueExplicit('AI' . $index, $value['content']);
                            $index++;
                        }
                    }
                    sleep(2);
                }
                break;
            case 'shop':
                // $this->shopExport($objProps,$objExcel,$param);
                D('Shop_order')->shopExportCsv($param);
                return true;
            case 'income':
                $title = L_('收入明细');
                $type = $param['type'];
                // 设置文档基本属性
                $objProps->setCreator($title);
                $objProps->setTitle($title);
                $objProps->setSubject($title);
                $objProps->setDescription($title);
                // 设置当前的sheet
                $objExcel->setActiveSheetIndex(0);
                $objExcel->getActiveSheet()->setTitle($type);
                $objActSheet = $objExcel->getActiveSheet();
                $cell_income = array(
                    'store_name' => L_('店铺名称'),
                    'type' => L_('类型'),
                    'order_id' => L_('订单编号'),
                    'num' => L_('数量'),
                    'total_money' => L_('订单总价'),
                    'system_take' => L_('平台服务费'),
                    'score_deducte' => L_('积分抵扣'),
                    'coupon_price' => L_('系统优惠券抵扣'),
                    'card_price' => L_('商家优惠券抵扣'),
                    'no_bill_money' => L_('不参与对账金额'),
                    'freight_charge' => L_('运费'),
                    'merchant_reduce' => L_('商家优惠'),
                    'balance_reduce' => L_('平台优惠'),
                    'money' => L_('金额'),
                    //'system_take' => L_('系统抽成'),
                    'score' => L_('送出') . C('config.score_name'),
                    'score_count' => C('config.score_name') . L_('使用数量'),
                    'use_time' => L_('记账时间'),
                    'desc' => L_('描述')
                );
                $cell_name = 'cell_' . $type;
                $cell_count = count($$cell_name);
                $cell_start = 1;
                for ($f = 'A'; $f <= 'Z'; $f++, $cell_start++) {
                    if ($cell_start > $cell_count) {
                        break;
                    }
                    $col_char[] = $f;
                }
                $col_k = 0;
                $title_index = 1;
                $i = 2;
                if ($type == 'income') {
                    $title_index = 3;
                    $i = 4;
                }
                foreach ($$cell_name as $key => $v) {
                    $objActSheet->getColumnDimension($col_char[$col_k])->setWidth(20);
                    $objActSheet->setCellValue($col_char[$col_k] . $title_index, $v);
                    $col_k++;
                }
                $mer_id = $param['mer_id'];
                $where['m.mer_id'] = $mer_id;
                if ($param['order_type'] == 'activity') {
                    $where['type'] = array('in', array('coupon', 'yydb'));
                }
                if ($param['order_type'] == 'shop') {
                    $where['type'] = array('in', array('shop', 'mall','shop_offline'));
                } elseif ($param['order_type'] == 'meal') {
                    $where['type'] = array('in', array('meal', 'dining'));
                } elseif ($param['order_type'] != 'all' && !empty($param['order_type'])) {
                    $where['type'] = $param['order_type'];
                }

                if ($param['order_id']) {
                    $where['order_id'] = $param['order_id'];
                }
                if ($param['store_id']) {
                    $where['m.store_id'] = $param['store_id'];
                }

                if (isset($param['begin_time']) && isset($param['end_time']) && !empty($param['begin_time']) && !empty($param['end_time'])) {
                    if ($param['begin_time'] > $param['end_time']) {
                        $this->error(L_("结束时间应大于开始时间"));
                    }
                    $period = array(strtotime($param['begin_time'] . " 00:00:00"), strtotime($param['end_time'] . " 23:59:59"));

                    $time_condition = " (use_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
                    $where['_string'] = $time_condition;
                }
                $result = M('Merchant_money_list')->join('as m left join ' . C('DB_PREFIX') . 'merchant_store s ON m.store_id = s.store_id')->field('m.type,m.total_money,(m.system_take+pow(-1,m.income+1)*m.money) as goods_money,(m.total_money-m.system_take-pow(-1,m.income+1)*m.money) as no_bill_money,m.system_take,m.order_id,m.num,pow(-1,m.income+1)*m.money as money,m.use_time,m.desc,m.score,m.score_count,s.name as store_name')->where($where)->order('use_time DESC')->select();

                $total_where = array_merge($where, array('m.mer_id' => $mer_id, 'm.income' => 1,'m.type'=>array('NOT IN',array('withdraw'))));
                $total = M('Merchant_money_list')->join('as m left join ' . C('DB_PREFIX') . 'merchant mm ON m.mer_id = mm.mer_id left join ' . C('DB_PREFIX') . 'merchant_store s ON m.store_id = s.store_id ')->where($total_where)->order('use_time DESC')->sum('m.money');

                $income_total_where = array_merge($where, array('m.mer_id' => $mer_id, 'm.income' => 1, 'm.type' => array('NOT IN', array('withdraw','merrecharge'))));
                $income_total = M('Merchant_money_list')->join('as m left join ' . C('DB_PREFIX') . 'merchant mm ON mm.mer_id = m.mer_id left join ' . C('DB_PREFIX') . 'merchant_store s ON m.store_id = s.store_id ')->where($income_total_where)->order('use_time DESC')->sum('m.money');

                if ($type == 'income') {
                    $objActSheet->setCellValue('A1', L_('总计 '));
                    $objActSheet->setCellValue('B1', $total);
                    $objActSheet->setCellValue('C1', L_('消费收入'));
                    $objActSheet->setCellValue('D1', $income_total);
                }

                $alias_name = D('Merchant_money_list')->get_alias_c_name();
//                $alias_name = $this->get_alias_name();
                foreach ($result as $row) {
                    $col_k = 0;
                    $now_order = [];
                    switch ($row['type']) {
                        case 'group':
                            $now_order = D('Group_order')->field('score_deducte,coupon_price,card_price')->where(array('real_orderid' => $row['order_id']))->find();
                            break;
                        case 'mall':
                        case 'shop':
                            $now_order = D('Shop_order')->field('score_deducte,coupon_price,card_price,freight_charge,merchant_reduce,balance_reduce')->where(array('real_orderid' => $row['order_id']))->find();
                            break;
                        case 'meal':
                            $now_order = D('Plat_order')->join('as p LEFT JOIN ' . C('DB_PREFIX') . 'foodshop_order as f ON f.order_id = p.order_id')->field('system_coupon_price as coupon_price,merchant_coupon_price as card_price,system_score_money as score_deducte')
                                ->where(array(
                                    'business_type' => 'foodshop',
                                    'f.real_orderid' => $row['order_id'],
                                    '_string' => 'system_coupon_price<>0 OR merchant_coupon_price<>0 OR system_score_money <>0'
                                ))
                                ->find();
                            if (empty($now_order)) {
                                $now_order['score_deducte'] = 0;
                                $now_order['coupon_price'] = 0;
                                $now_order['card_price'] = 0;
                            }
                            break;
                        case 'store':
                            $now_order = D('Store_order')->field('score_deducte,coupon_price,card_price')->where(array('order_id' => $row['order_id']))->find();
                            break;
                        case 'cash':
                            $now_order = D('Store_order')->field('score_deducte,coupon_price,card_price')->where(array('order_id' => $row['order_id']))->find();
                            break;
                        case 'appoint':
                            $now_order = D('Appoint_order')->field('score_deducte,coupon_price,card_price')->where(array('order_id' => $row['order_id']))->find();
                            break;

                    }
                    foreach ($$cell_name as $k => $vv) {
                        switch ($k) {
                            case 'type':
                                if($row[$k]=='dining'){
                                    $alias_name[$row[$k]] = $alias_name['meal'];
                                }
                                $objActSheet->setCellValue($col_char[$col_k] . $i, $alias_name[$row[$k]] . ' ');
                                break;
                            case 'order_id':
                                $objActSheet->setCellValue($col_char[$col_k] . $i, $row[$k] . ' ');
                                break;
                            case 'real_orderid':
                                $objActSheet->setCellValue($col_char[$col_k] . $i, $row[$k] . ' ');
                                break;
                            case 'orderid':
                                $objActSheet->setCellValue($col_char[$col_k] . $i, $row[$k] . ' ');
                                break;
                            case 'money':
                                if ($type == 'withdraw') {
                                    $objActSheet->setCellValue($col_char[$col_k] . $i, floatval($row[$k] / 100));
                                } else {
                                    $objActSheet->setCellValue($col_char[$col_k] . $i, floatval($row[$k]));
                                }
                                break;
                            case 'status':
                                if ($type == 'withdraw') {
                                    $row[$k] == 0 && $row[$k] = L_('审核中');
                                    $row[$k] == 1 && $row[$k] = L_('已通过');
                                    $row[$k] == 2 && $row[$k] = L_('被驳回');
                                    $row[$k] == 3 && $row[$k] = L_('已提现');
                                    $row[$k] == 4 && $row[$k] = L_('审核中');
                                }
                                $objActSheet->setCellValue($col_char[$col_k] . $i, $row[$k] . ' ');
                                break;
                            case 'pay_time':
                            case 'use_time':
                            case 'withdraw_time':
                                $objActSheet->setCellValue($col_char[$col_k] . $i, $row[$k] ? date('Y-m-d H:i:s', $row[$k]) : '');
                                break;
                            case 'desc':
                            case 'remark':
                            case 'name':
                                $objActSheet->setCellValue($col_char[$col_k] . $i, $row[$k] . ' ');
                                break;
                            case 'score_deducte':
                                $objActSheet->setCellValue($col_char[$col_k] . $i, $now_order['score_deducte'] . ' ');
                                break;
                            case 'coupon_price':
                                $objActSheet->setCellValue($col_char[$col_k] . $i, $now_order['coupon_price'] . ' ');
                                break;
                            case 'card_price':
                                $objActSheet->setCellValue($col_char[$col_k] . $i, $now_order['card_price'] . ' ');
                                break;
                            case 'freight_charge':
                                if ($row['type'] == 'withdraw') {
                                    $objActSheet->setCellValue($col_char[$col_k] . $i, ' ');
                                }
                                else{
                                    $objActSheet->setCellValue($col_char[$col_k] . $i, $now_order['freight_charge'] . ' ');
                                }
                                break;
                            case 'merchant_reduce':
                                $objActSheet->setCellValue($col_char[$col_k] . $i, $now_order['merchant_reduce'] . ' ');
                                break;
                            case 'balance_reduce':
                                $objActSheet->setCellValue($col_char[$col_k] . $i, $now_order['balance_reduce'] . ' ');
                                break;
                            case 'goods_money':
                                if($row[$k] > 0){
                                    $objActSheet->setCellValue($col_char[$col_k] . $i, $row[$k]);
                                }
                                else{
                                    $objActSheet->setCellValue($col_char[$col_k] . $i, 0);
                                }
                                break;
                            default:
                                $objActSheet->setCellValue($col_char[$col_k] . $i, $row[$k]);
                                break;
                        }
                        $col_k++;
                    }
                    if ($type != 'income' && $type != 'withdraw') {
                        $objActSheet->setCellValue($col_char[$cell_count - 1] . $i, $row['balance_pay'] + $row['coupon_price'] + $row['score_deducte'] + $row['payment_money']);
                    }
                    $i++;
                }
                break;
            case 'user':
                $title = L_('用户信息');

                $database_user = D('User');
                if (!empty($param['keyword'])) {
                    if ($param['searchtype'] == 'uid') {
                        $condition_user['uid'] = $param['keyword'];
                    } else if ($param['searchtype'] == 'nickname') {
                        $condition_user['nickname'] = array('like', '%' . $param['keyword'] . '%');
                    } else if ($param['searchtype'] == 'phone') {
                        $condition_user['phone'] = array('like', '%' . $param['keyword'] . '%');
                    }
                }
                if (isset($param['user_type']) && !empty($param['user_type'])) {
                    $House_village_user_bind = M('House_village_user_bind')->where(['type' => $param['user_type']])->getField('uid', true);
                    if(!empty($House_village_user_bind)){
                        $condition_user['uid'] = ['in', $House_village_user_bind];
                    }
                }
                $condition_user['_string'] = '(openid NOT LIKE "%no_use%" OR openid IS NULL)';
                $order_string = '`uid` DESC';

                $condition_user['status'] = array('neq', 4);
                if ($param['status'] != '') {
                    $condition_user['status'] = $param['status'];
                }
                if ($param['level'] > 0) {
                    $condition_user['level'] = $param['level'];
                }

                if (!empty($param['begin_time']) && !empty($param['end_time'])) {
                    if ($param['begin_time'] > $param['end_time']) {
                        return false;
                    }
                    $period = array(strtotime($param['begin_time'] . " 00:00:00"), strtotime($param['end_time'] . " 23:59:59"));
                    $condition_user['_string'] = " (add_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
                }
                if(C('config.open_school') == '1'){
                    $condition_user['school_type'] = $param['school_type'] == '1' ? 0 : 1;
                }
                // if($param['province_idss']){
                //  $condition_user['province_id'] = $param['province_idss'];
                // }
                // if($param['city_idss']){
                //  $condition_user['city_id'] = $param['city_idss'];
                // }

                $client = array(0 => 'WAP端', 1 => L_('苹果'), 2 => L_('安卓'), 3 => L_('电脑'), 4 => L_('小程序'), 5 => L_('微信'), 6 => L_('支付宝'), '7' => L_('微信扫描商家二维码'));

                if($condition_user['school_type'] == '1'){
                    $csv_head = array(
                        L_('用户ID'),
                        L_('昵称'),
                        L_('真实姓名'),
                        L_('手机号'),
                        L_('身份证号'),
                        L_('学工号'),
                        L_('性别 (男； 女； 其他)'),
                        L_('省份'),
                        L_('城市'),
                        L_('县/区'),
                        C('config.score_name'),
                        L_('余额'),
                        L_('是否手机认证'),
                        L_('是否关注公众号'),
                        L_('账号是否正常'),
                        L_('用户来源'),
                        L_('注册时间'),
                        L_('最后登录时间'),
                    );
                }
                else{
                    $csv_head = array(
                        L_('用户ID'),
                        L_('昵称'),
                        L_('真实姓名'),
                        L_('手机号'),
                        L_('性别 (男； 女； 其他)'),
                        L_('省份'),
                        L_('城市'),
                        L_('县/区'),
                        C('config.score_name'),
                        L_('余额'),
                        L_('是否手机认证'),
                        L_('是否关注公众号'),
                        L_('账号是否正常'),
                        L_('用户来源'),
                        L_('注册时间'),
                        L_('最后登录时间'),
                    );
                }
                $csv_data = [];
                $count_user = $database_user->where($condition_user)->count();
                $length = ceil($count_user/1000);
                for ($i = 0; $i < $length; $i++) {
                    $user_list = $database_user->where($condition_user)->limit($i * 1000 . ',1000')->select();
                    fdump($i,'user_list_i',true);
                    if (!empty($user_list)) {
                        $userAreaIds = array_filter(array_unique(array_column($user_list,'area_id')));
                        $userAreaArr = array_column(M('Area')->field('`area_name`,`area_id`')->where(['area_id'=>['in',$userAreaIds]])->select(),'area_name','area_id');

                        $user_city_ids = array_filter(array_unique(array_column($user_list,'city_id')));
                        $user_city_arr = array_column(M('Area')->field('`area_name`,`area_id`')->where(['area_id'=>['in',$user_city_ids]])->select(),'area_name','area_id');

                        $user_province_ids = array_filter(array_unique(array_column($user_list,'province_id')));
                        $user_province_arr = array_column(M('Area')->field('`area_name`,`area_id`')->where(['area_id'=>['in',$user_province_ids]])->select(),'area_name','area_id');

                        $index = 2;
                        foreach ($user_list as $value) {
                            if (strpos($value['source'], 'weixin') === 0) {
                                $tmp_client = $client[5];
                            } elseif (strpos($value['source'], 'wxapp') === 0) {
                                $tmp_client = $client[4];
                            } elseif (strpos($value['source'], 'wap') === 0) {
                                $tmp_client = $client[0];
                            } elseif (strpos($value['source'], 'alipay') === 0) {
                                $tmp_client = $client[6];
                            } elseif ($value['client'] == 2 || strpos($value['source'], 'androidapp') === 0) {
                                $tmp_client = $client[2];
                            } elseif ($value['client'] == 1 || strpos($value['source'], 'iosapp') === 0) {
                                $tmp_client = $client[1];
                            } elseif ($value['client'] == 1 || strpos($value['source'], 'scan_mer_qrcode') === 0) {
                                $tmp_client = $client[7];
                            } else {
                                $tmp_client = $client[3];
                            }
                            
                            if($condition_user['school_type'] == '1'){
                                $csv_data[] = [
                                    $value['uid'],
                                    $value['nickname'],
                                    $value['truename'],
                                    ($value['phone_country_type'] ? '+' . $value['phone_country_type'] : '') . ' ' . $value['phone'],
                                    ' '.$value['school_sfz'],
                                    ' '.$value['school_no'],
                                    $value['sex'] == 0 ? L_('未知') : ($value['sex'] == 1 ? L_('男') : L_('女')),
                                    $user_province_arr[$value['province_id']] ? $user_province_arr[$value['province_id']] : '',
                                    $user_city_arr[$value['city_id']] ? $user_city_arr[$value['city_id']] : '',
                                    $userAreaArr[$value['area_id']] ?? '',
                                    $value['score_count'],
                                    $value['now_money'],
                                    $value['is_check_phone'] == 0 ? L_('否') : L_('是'),
                                    $value['is_follow'] ? L_('是') : L_('否'),
                                    $value['status'] ? L_('正常') : L_('禁用'),
                                    $tmp_client,
                                    date('Y-m-d H:i:s', $value['add_time']),
                                    date('Y-m-d H:i:s', $value['last_time'])
                                ];
                            }
                            else{
                                $csv_data[] = [
                                    $value['uid'],
                                    $value['nickname'],
                                    $value['truename'],
                                    ($value['phone_country_type'] ? '+' . $value['phone_country_type'] : '') . ' ' . $value['phone'],
                                    $value['sex'] == 0 ? L_('未知') : ($value['sex'] == 1 ? L_('男') : L_('女')),
                                    $user_province_arr[$value['province_id']] ? $user_province_arr[$value['province_id']] : '',
                                    $user_city_arr[$value['city_id']] ? $user_city_arr[$value['city_id']] : '',
                                    $userAreaArr[$value['area_id']] ?? '',
                                    $value['score_count'],
                                    $value['now_money'],
                                    $value['is_check_phone'] == 0 ? L_('否') : L_('是'),
                                    $value['is_follow'] ? L_('是') : L_('否'),
                                    $value['status'] ? L_('正常') : L_('禁用'),
                                    $tmp_client,
                                    date('Y-m-d H:i:s', $value['add_time']),
                                    date('Y-m-d H:i:s', $value['last_time'])
                                ];
                            }
                        }
                    }
                }

                $filename = date("Y-m-d", time()) . '-' . $param['rand_number'] . '.csv';
                $csvFileName = './runtime/' . $filename;
                // fdump($csvFileName,'csvFileName');
                // fdump($csv_head,'csvFileName',1);
                // fdump($csv_data,'csvFileName',1);
                $this->putCsv($csvFileName, $csv_data, $csv_head);
                return true;
                break;
            case 'express':

                $title = L_('配送信息');

                // 设置文档基本属性
                $objProps->setCreator($title);
                $objProps->setTitle($title);
                $objProps->setSubject($title);
                $objProps->setDescription($title);
                // 设置当前的sheet
                $objExcel->setActiveSheetIndex(0);

                if ($param['order_id']) {
                    $where['order_id'] = $param['order_id'];
                }

                $where['mer_id'] = $param['mer_id'];
                $where['type'] = 0;
                $where['item'] = 2;


                if (isset($param['begin_time']) && isset($param['end_time']) && !empty($param['begin_time']) && !empty($param['end_time'])) {
                    if ($param['begin_time'] > $param['end_time']) {
                        return false;
                    }
                    $period = array(strtotime($param['begin_time'] . " 00:00:00"), strtotime($param['end_time'] . " 23:59:59"));
                    $time_condition = " (create_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
                    $where['_string'] = $time_condition;

                }

                $mer_id = $param['mer_id'];

                $count = M('Deliver_supply')->where($where)->count();


                unset($where['mer_id']);
                unset($where['order_id']);
                $where['l.mer_id'] = $mer_id;
                $param['order_id'] && $where['l.order_id'] = $param['order_id'];
                $param['begin_time'] && $where['_string'] = " (l.create_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";


                $length = ceil($count[0]['count'] / 1000);
                for ($i = 0; $i < $length; $i++) {
                    $i && $objExcel->createSheet();
                    $objExcel->setActiveSheetIndex($i);

                    $objExcel->getActiveSheet()->setTitle(L_('第X1个一千个配送信息', ($i + 1)));

                    $objActSheet = $objExcel->getActiveSheet();
                    $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);


                    $objActSheet->setCellValue('A1', L_('店铺名称'));
                    $objActSheet->setCellValue('B1', L_('订单号'));
                    $objActSheet->setCellValue('C1', L_('数量'));
                    $objActSheet->setCellValue('D1', L_('订单总价'));
                    $objActSheet->setCellValue('E1', L_('配送费'));
                    $objActSheet->setCellValue('F1', L_('支付时间'));
                    $result_list = M('Deliver_supply')->join('as l left join ' . C('DB_PREFIX') . 'merchant_store ms ON l.store_id = ms.store_id LEFT JOIN ' . C('DB_PREFIX') . 'shop_order o ON o.order_id  = l.order_id ')->field('ms.name as store_name,l.supply_id,l.order_id,l.money,o.create_time,l.uid,l.freight_charge,o.real_orderid,o.pay_time,o.num')->where($where)->order('order_id DESC')->limit($i * 1000, 1000)->select();


                    if (!empty($result_list)) {
                        $index = 2;
                        foreach ($result_list as $value) {
                            $objActSheet->setCellValueExplicit('A' . $index, $value['store_name']);
                            $objActSheet->setCellValueExplicit('B' . $index, $value['real_orderid']);
                            $objActSheet->setCellValueExplicit('C' . $index, $value['num']);
                            $objActSheet->setCellValueExplicit('D' . $index, $value['money']);
                            $objActSheet->setCellValueExplicit('E' . $index, floatval($value['freight_charge']));
                            $objActSheet->setCellValueExplicit('F' . $index, $value['pay_time'] ? date('Y-m-d H:i:s', $value['pay_time']) : '');
                            $index++;
                        }
                    }
                    sleep(2);
                }
                break;
            case 'trade':
                $title = L_('交易数据');
                $type = $param['type'];
                if (isset($param['order_from']) && $param['order_from'] == 6) {
                    $where['order_from'] = array('eq', 6);
                } elseif (isset($param['order_from']) && $param['order_from'] == 0) {
                    $where['order_from'] = array('neq', 6);
                }
                // 设置文档基本属性
                $objProps->setCreator($title);
                $objProps->setTitle($title);
                $objProps->setSubject($title);
                $objProps->setDescription($title);
                // 设置当前的sheet
                $objExcel->setActiveSheetIndex(0);
                $objExcel->getActiveSheet()->setTitle($type);
                $objExcel->getActiveSheet()->mergeCells('A1:V1');
                $objExcel->getActiveSheet()->mergeCells('A3:H3');
                $objActSheet = $objExcel->getActiveSheet();
                $cell_trade = array(
                    'date_id' => L_('日期'),
                    'province' => L_('省份'),
                    'city' => L_('市'),
                    'area' => L_('区/县'),
                    'platform' => L_('收入来源'),
                    'type' => L_('业务'),
                    'merchant_name' => L_('商家'),
                    'store_name' => L_('店铺'),
                    'total_money' => L_('营业金额'),
                    'total_count' => L_('营业单数'),
                    'consume_money' => L_('实收金额'),
                    'consume_count' => L_('实收单数'),
                    'refund_money' => L_('退款金额'),
                    'refund_count' => L_('退款单数'),
                    'alipay_money' => L_('支付宝交易金额'),
                    'alipay_count' => L_('支付宝交易单数'),
                    'weixin_money' => L_('微信交易金额'),
                    'weixin_count' => L_('微信交易单数'),
                    'balance_money' => L_('余额支付交易金额'),
                    'balance_count' => L_('余额支付交易单数'),
                    'score_money' => L_('积分抵扣金额'),
                    'score_count' => L_('积分抵扣单数'),
                );

                $cell_name = 'cell_trade';
                $cell_count = count($$cell_name);
                $cell_start = 1;
                for ($f = 'A'; $f <= 'Z'; $f++, $cell_start++) {
                    if ($cell_start > $cell_count) {
                        break;
                    }
                    $col_char[] = $f;
                }
                $col_k = 0;
                $title_index = 1;
                $i = 2;
                if ($type == 'trade') {
                    $title_index = 2;
                    $i = 3;
                }
                $objActSheet->setCellValue('A1', L_('说明：已停用的支付方式不计入统计，如需查看，请查看具体业务下的订单记录'));
                $objActSheet->setCellValue('A3', L_('合计'));
                //$objActSheet->setCellValue('G3', '合计');


                $param['type'] = $param['order_type'];
                $mer_list = $param['mer_list'];
                $store_list = $param['store_list'];
                $staff_list = $param['staff_list'];
                $type = $param['type'];
                $pay_type = $param['pay_type'];
                $start_time = strtotime($param['begin_time']);
                $end_time = strtotime($param['end_time']) + 86400;
                if ($param['selectTimeType'] > 0) {
                    $start_time = strtotime(date('Y-m-d', time() - ($param['selectTimeType'] - 1) * 86400));
                    $end_time = time();
                }

                if ($param['area_id'] && $param['area_id'] != 'undefined') {
                    $where_store['area_id'] = $param['area_id'];
                } else if ($param['city_idss'] && $param['city_idss'] != 'undefined') {
                    $where_store['city_id'] = $param['city_idss'];
                } else if ($param['province_idss'] && $param['province_idss'] != 'undefined') {
                    $where_store['province_id'] = $param['province_idss'];
                }
                if ($param['merchant_session']) {
                    $where['mer_id'] = $param['merchant_session']['mer_id'];
                    $where_store['mer_id'] = $where['mer_id'];
                }
                if ($where_store) {
                    $store_ids = M('Merchant_store')->where($where_store)->select();

                    foreach ($store_ids as $st) {
                        $store_list_id[] = $st['store_id'];
                    }
                    if ($store_list_id) {
                        $where['store_id'] = array('in', $store_list_id);
                    } else {
                        $where['store_id'] = -2; // 区域下没有店铺信息 给一个不能查询的店铺
                    }
                }

                if ($type == 'shop' && $param['platform']) {
                    $where['platform'] = $param['platform'];
                }

                if ($end_time < $start_time) {
                    return array('error_code' => 1, 'msg' => L_('结束时间不能比开始时间小'));
                }
                $param['mer_id'] && $where['mer_id'] = $param['mer_id'];

                if ($type == 'meal') {
                    $Order = 'Foodshop_order';
                } else {
                    $where['paid'] = 1;
                    $Order = ucfirst($type) . '_order';
                }

                if ($mer_list && $mer_list != -1) {
                    $where['mer_id'] = array('in', $mer_list);
                }

                if ($store_list && $store_list != -1) {
                    $where['store_id'] = array('in', $store_list);
                }

                if ($staff_list && $staff_list != -1) {
                    $where['staff_id'] = array('in', $staff_list);
                }

                if ($pay_type != 'all') {
                    if ($pay_type == 'balance') {
                        $where['pay_type'] = '';
                    } elseif ($pay_type == 'alipay') {
                        $where['pay_type'] = array('like', 'alipay%');
                    } else {
                        $where['pay_type'] = $param['pay_type'];
                    }
                }

                if ($start_time) {
                    $where['_string'] = "pay_time <= {$end_time} AND pay_time>={$start_time}  ";
                }

                //营业金额
                switch ($type) {
                    case 'group':
                        $where['status'] = array('elt', 3);
                        $sum_name = 'total_money';
                        break;
                    case 'shop':
                        //$where['status'] = array('elt', 3);
                        $where['status'] = array('not in', '4,5,6');
                        $sum_name = 'price';
                        break;
                    case 'meal':
                        $where['status'] = array('in', '3,4');
                        $sum_name = 'total_price';
                        break;
                    case 'appoint':
                        unset($where['paid']);
                        if($where['_string']){
                            $where['_string'] .= ' AND (paid = 1 OR paid = 3)';
                        }else{
                            $where['_string'] = '(paid = 1 OR paid = 3)';
                        }
                        $sum_name = 'balance_pay+score_deducte+payment_money+merchant_balance+product_score_deducte+product_balance_pay+product_coupon_price+user_pay_money+product_merchant_balance';
                        break;
                    case 'store':
                        $sum_name = 'price';

                        break;
                }

                $arr['total_money_count'] = M($Order)->where($where)->count();
                $arr['total_money_sum'] = M($Order)->where($where)->sum($sum_name);
                $objActSheet->setCellValue('I3', floatval($arr['total_money_sum']));
                $objActSheet->setCellValue('J3', floatval($arr['total_money_count']));


                //$count = M($Order)->where($where)->group("FROM_UNIXTIME(pay_time, '%Y-%m-%d'),store_id")->count();
                $subQuery = M($Order)->field('store_id')->where($where)->group("FROM_UNIXTIME(pay_time, '%Y-%m-%d'),store_id")->buildSql();
                $count = M()->table($subQuery . ' a')->count();


                $lenght = $count / 1000;
                $merchant_arr = array();
                $store_arr = array();
                $province = array();
                $city = array();
                $area = array();
                $ailas_name = $this->get_alias_name();
                $type_name = $ailas_name[$param['type']];
                $platform_arr = array(0 => L_('平台'), '1' => L_('饿了吗'), '2' => L_('美团'));
                $platform_name = $platform_arr[$param['$platform']];
                for ($i = 0; $i < $lenght; $i++) {
                    if($type == 'appoint'){
                        $total_money_sum_by_store = M($Order)->field("FROM_UNIXTIME(order_time, '%Y-%m-%d') as date_id,store_id,mer_id,sum({$sum_name}) as income,count(order_id) as counts")
                        ->where($where)->group("FROM_UNIXTIME(order_time, '%Y-%m-%d'),store_id")
                        ->order("FROM_UNIXTIME(order_time, '%Y-%m-%d') DESC,store_id DESC")
                        ->limit($i * 1000, 1000)->select();
                    }else{
                        $total_money_sum_by_store = M($Order)->field("FROM_UNIXTIME(pay_time, '%Y-%m-%d') as date_id,store_id,mer_id,sum({$sum_name}) as income,count(order_id) as counts")
                        ->where($where)->group("FROM_UNIXTIME(pay_time, '%Y-%m-%d'),store_id")
                        ->order("FROM_UNIXTIME(pay_time, '%Y-%m-%d') DESC,store_id DESC")
                        ->limit($i * 1000, 1000)->select();
                    }
                    
                    //实收金额
                    $where_ = $where;
                    unset($where_['status']);
                    if ($where_['_string']) {
                        $where_['_string'] .= ' AND ';
                    }
                    switch ($type) {
                        case 'group':
                            $where_['_string'] .= '   (status = 1 OR status = 2) ';
                            break;
                        case 'shop':
                            $where_['_string'] .= '  (status = 2 OR status = 3) ';
                            break;
                        case 'meal':
                            $where_['_string'] .= '  (status = 3 OR status = 4) ';
                            break;
                        case 'appoint':
                            $where_['_string'] .= '  service_status>0 ';
                            break;
                        case 'store':
                            break;
                    }
                    if ($i == 0) {
                        $arr['consume_money_count'] = M($Order)->where($where_)->count();
                        if ($type == 'meal') {
                            $arr['consume_money_sum'] = M($Order)->where($where)->sum('price');
                        } else {
                            $arr['consume_money_sum'] = M($Order)->where($where_)->sum($sum_name);
                        }
                        $objActSheet->setCellValue('K3', floatval($arr['consume_money_sum']));
                        $objActSheet->setCellValue('L3', floatval($arr['consume_money_count']));
                    }

                    $consume_money_count_store = M($Order)->where($where_)->group("FROM_UNIXTIME(pay_time, '%Y-%m-%d'),store_id")->field("FROM_UNIXTIME(pay_time, '%Y-%m-%d') as date_id ,store_id,count(order_id) as consume_count,sum({$sum_name}) as consume_money")->select();

                    $cosumen_count_tmp = array();
                    $cosumen_money_tmp = array();
                    foreach ($consume_money_count_store as $v) {
                        $cosumen_count_tmp[$v['date_id']][$v['store_id']] = $v['consume_count'];
                        $cosumen_money_tmp[$v['date_id']][$v['store_id']] = $v['consume_money'];
                    }

                    $where_['pay_type'] = 'alipay';
                    if ($i == 0) {
                        $arr['alipay_money_count'] = M($Order)->where($where_)->count();
                        $arr['alipay_money_sum'] = M($Order)->where($where_)->sum($sum_name);


                        $objActSheet->setCellValue('O3', floatval($arr['alipay_money_sum']));
                        $objActSheet->setCellValue('P3', floatval($arr['alipay_money_count']));
                    }
                    $alipay_money_count_store = M($Order)->where($where_)->group("FROM_UNIXTIME(pay_time, '%Y-%m-%d'),store_id")->field("FROM_UNIXTIME(pay_time, '%Y-%m-%d') as date_id ,store_id,count(order_id) as consume_count,sum({$sum_name}) as consume_money")->select();
                    $alipay_count_tmp = array();
                    $alipay_money_tmp = array();
                    foreach ($alipay_money_count_store as $v) {
                        $alipay_count_tmp[$v['date_id']][$v['store_id']] = $v['consume_count'];
                        $alipay_money_tmp[$v['date_id']][$v['store_id']] = $v['consume_money'];
                    }

                    $where_['pay_type'] = 'weixin';
                    if ($i == 0) {
                        $arr['weixin_money_count'] = M($Order)->where($where_)->count();
                        $arr['weixin_money_sum'] = M($Order)->where($where_)->sum($sum_name);
                        $objActSheet->setCellValue('Q3', floatval($arr['weixin_money_sum']));
                        $objActSheet->setCellValue('R3', floatval($arr['weixin_money_count']));
                    }
                    $weixin_money_count_store = M($Order)->where($where_)->group("FROM_UNIXTIME(pay_time, '%Y-%m-%d'),store_id")->field("FROM_UNIXTIME(pay_time, '%Y-%m-%d') as date_id ,store_id,count(order_id) as consume_count,sum({$sum_name}) as consume_money")->select();
                    $weixin_count_tmp = array();
                    $weixin_money_tmp = array();
                    foreach ($weixin_money_count_store as $v) {
                        $weixin_count_tmp[$v['date_id']][$v['store_id']] = $v['consume_count'];
                        $weixin_money_tmp[$v['date_id']][$v['store_id']] = $v['consume_money'];
                    }

                    $where_['pay_type'] = '';
                    if ($i == 0) {
                        $arr['balance_money_count'] = M($Order)->where($where_)->count();
                        $arr['balance_money_sum'] = M($Order)->where($where_)->sum($sum_name);
                        $objActSheet->setCellValue('S3', floatval($arr['balance_money_sum']));
                        $objActSheet->setCellValue('T3', floatval($arr['balance_money_count']));
                    }
                    $balance_money_count_store = M($Order)->where($where_)->group("FROM_UNIXTIME(pay_time, '%Y-%m-%d'),store_id")->field("FROM_UNIXTIME(pay_time, '%Y-%m-%d') as date_id ,store_id,count(order_id) as consume_count,sum({$sum_name}) as consume_money")->select();
                    $balance_count_tmp = array();
                    $balance_money_tmp = array();
                    foreach ($balance_money_count_store as $v) {
                        $balance_count_tmp[$v['date_id']][$v['store_id']] = $v['consume_count'];
                        $balance_money_tmp[$v['date_id']][$v['store_id']] = $v['consume_money'];
                    }

                    $where_['score_deducte'] = array('gt', 0);

                    if ($i == 0) {
                        $arr['score_money_count'] = M($Order)->where($where_)->count();
                        $arr['score_money_sum'] = M($Order)->where($where_)->sum($sum_name);
                        $objActSheet->setCellValue('U3', floatval($arr['score_money_sum']));
                        $objActSheet->setCellValue('V3', floatval($arr['score_money_count']));
                    }
                    $balance_money_count_store = M($Order)->where($where_)->group("FROM_UNIXTIME(pay_time, '%Y-%m-%d'),store_id")->field("FROM_UNIXTIME(pay_time, '%Y-%m-%d') as date_id ,store_id,count(order_id) as consume_count,sum(score_deducte) as consume_money")->select();
                    $score_count_tmp = array();
                    $score_money_tmp = array();
                    foreach ($balance_money_count_store as $v) {
                        $score_count_tmp[$v['date_id']][$v['store_id']] = $v['consume_count'];
                        $score_money_tmp[$v['date_id']][$v['store_id']] = $v['consume_money'];
                    }


                    //退款金额
                    switch ($type) {
                        case 'group':
                            $where['status'] = 3;
                            break;
                        case 'shop':
                            //$where['status'] = 4;
                            $where['status'] = ['in','4,5'];
                            break;
                        case 'meal':
                            $where['status'] = 5;
                            break;
                        case 'appoint':
                            $where['paid'] = 2;
//                            $where['_string'] .= ' AND service_status>0 ';
                            break;
                        case 'store':
                            $where['_string'] .= ' 1=0 ';
                            break;
                    }
                    if ($i == 0) {
                        $arr['refund_money_count'] = M($Order)->where($where)->count();
                        $arr['refund_money_sum'] = M($Order)->where($where)->sum($sum_name);
                        $objActSheet->setCellValue('M3', getFormatNumber($arr['refund_money_sum']));
                        $objActSheet->setCellValue('N3', getFormatNumber($arr['refund_money_count']));

                    }
                    $refund_money_count_store = M($Order)->where($where)->group("FROM_UNIXTIME(pay_time, '%Y-%m-%d'),store_id")->field("FROM_UNIXTIME(pay_time, '%Y-%m-%d') as date_id,store_id,count(order_id) as refund_count,sum({$sum_name}) as refund_money")->select();

                    $refund_count_tmp = array();
                    $refund_money_tmp = array();
                    foreach ($refund_money_count_store as $v) {
                        $refund_count_tmp[$v['date_id']][$v['store_id']] = $v['refund_count'];
                        $refund_money_tmp[$v['date_id']][$v['store_id']] = getFormatNumber($v['refund_money']);
                    }

                    foreach ($$cell_name as $key => $v) {
                        $objActSheet->getColumnDimension($col_char[$col_k])->setWidth(20);
                        $objActSheet->setCellValue($col_char[$col_k] . $title_index, $v);
                        $col_k++;
                    }


                    $index = $i == 0 ? 4 : 2;
                    foreach ($total_money_sum_by_store as &$v) {
//                      $tmp['store_id'] = $v['store_id'];
//                      $tmp['mer_id'] = $v['mer_id'];
                        if (!$merchant_arr[$v['mer_id']]) {
                            $merchant_arr[$v['mer_id']] = $merchant = M('Merchant')->field('mer_id,name,province_id,city_id,area_id')->where(array('mer_id' => $v['mer_id']))->find();
                        } else {
                            $merchant = $merchant_arr[$v['mer_id']];
                        }

                        if (!$store_arr[$v['store_id']]) {
                            $store_arr[$v['store_id']] = $store = M('Merchant_store')->field('store_id,name,province_id,city_id,area_id')->where(array('store_id' => $v['store_id']))->find();
                        } else {
                            $store = $store_arr[$v['store_id']];
                        }

                        if (!$province[$store['province_id']]) {
                            $province[$store['province_id']] = $province = M('Area')->where(array('area_id' => $store['province_id']))->find();
                        } else {
                            $province = $province[$store['province_id']];
                        }

                        if (!$city[$store['city_id']]) {
                            $city[$store['city_id']] = $city = M('Area')->where(array('area_id' => $store['city_id']))->find();
                        } else {
                            $city = $city[$store['city_id']];
                        }

                        if (!$area[$store['area_id']]) {
                            $area[$store['area_id']] = $area = M('Area')->where(array('area_id' => $store['area_id']))->find();
                        } else {
                            $area = $area[$store['area_id']];
                        }


                        $v['merchant_name'] = $merchant['name'];
                        $v['store_name'] = $store['name'];

                        $v['province'] = $province['area_name'];
                        $v['city'] = $city['area_name'];
                        $v['area'] = $area['area_name'];


                        $v['total_money'] = $v['income'];
                        $v['total_count'] = $v['counts'];
                        $v['date_id'] = $v['date_id'];
                        $v['type'] = $type_name;
                        $v['platform'] = $platform_name;

//
                        $v['consume_money'] = getFormatNumber($cosumen_money_tmp[$v['date_id']][$v['store_id']]);
                        $v['consume_count'] = getFormatNumber($cosumen_count_tmp[$v['date_id']][$v['store_id']]);

                        $v['refund_money'] = getFormatNumber($refund_money_tmp[$v['date_id']][$v['store_id']]);
                        $v['refund_count'] = getFormatNumber($refund_count_tmp[$v['date_id']][$v['store_id']]);

                        $v['alipay_money'] = getFormatNumber($alipay_money_tmp[$v['date_id']][$v['store_id']]);
                        $v['alipay_count'] = getFormatNumber($alipay_count_tmp[$v['date_id']][$v['store_id']]);

                        $v['weixin_money'] = getFormatNumber($weixin_money_tmp[$v['date_id']][$v['store_id']]);
                        $v['weixin_count'] = getFormatNumber($weixin_count_tmp[$v['date_id']][$v['store_id']]);

                        $v['balance_money'] = getFormatNumber($balance_money_tmp[$v['date_id']][$v['store_id']]);
                        $v['balance_count'] = getFormatNumber($balance_count_tmp[$v['date_id']][$v['store_id']]);

                        $v['score_money'] = getFormatNumber($score_money_tmp[$v['date_id']][$v['store_id']]);
                        $v['score_count'] = getFormatNumber($score_count_tmp[$v['date_id']][$v['store_id']]);

                        $col_k = 0;
                        foreach ($$cell_name as $k => $vv) {
                            $objActSheet->setCellValueExplicit($col_char[$col_k] . $index, $v[$k]);
                            $col_k++;
                        }
                        $index++;
                    }
                }
                break;
            case 'goods':

                $title = L_('菜品信息');
                $type = $param['type'];
                // 设置文档基本属性
                $objProps->setCreator($title);
                $objProps->setTitle($title);
                $objProps->setSubject($title);
                $objProps->setDescription($title);
                // 设置当前的sheet
                $objExcel->setActiveSheetIndex(0);
                $objExcel->getActiveSheet()->setTitle($type);
                $objExcel->getActiveSheet()->mergeCells('A1:J1');

                $objActSheet = $objExcel->getActiveSheet();
                $cell_goods = array(
                    'type' => L_('业务'),
                    'store_name' => L_('店铺'),
                    'rank' => L_('排名'),
                    'name' => L_('商品名称'),
                    'number' => L_('商品条码'),
                    'sort_name' => L_('商品类别'),
                    'price' => L_('单价'),
                    'sale_count' => L_('销售数量'),
                    'sale_money' => L_('销售金额'),
                    'sale_percent' => L_('销售占比'),
                );

                $cell_name = 'cell_goods';
                $cell_count = count($$cell_name);
                $cell_start = 1;
                for ($f = 'A'; $f <= 'Z'; $f++, $cell_start++) {
                    if ($cell_start > $cell_count) {
                        break;
                    }
                    $col_char[] = $f;
                }
                $col_k = 0;
                $title_index = 1;
                $i = 2;
                if ($type == 'goods') {
                    $title_index = 2;
                    $i = 3;
                }
                $mer_list = $param['mer_list'];
                $store_list = $param['store_list'];
                $type = $param['order_type'];
                $start_time = strtotime($param['begin_time']);
                $end_time = strtotime($param['end_time']) + 86400;
                $goods_name = $param['goods_name'];
                $type_name = $type == 'meal' || 'foodshop' ? C('config.meal_alias_name') : C('config.shop_alias_name');


                if ($param['area_id'] && $param['area_id'] != 'undefined') {
                    $where_store['area_id'] = $param['area_id'];
                } else if ($param['city_idss'] && $param['city_idss'] != 'undefined') {
                    $where_store['city_id'] = $param['city_idss'];
                } else if ($param['province_idss'] && $param['province_idss'] != 'undefined') {
                    $where_store['province_id'] = $param['province_idss'];
                }
                if ($where_store) {
                    $store_ids = M('Merchant_store')->where($where_store)->select();

                    foreach ($store_ids as $st) {
                        $store_list_id[] = $st['store_id'];
                    }
                    if ($store_list_id) {
                        $where['store_id'] = array('in', $store_list_id);
                    } else {
                        return array('error_code' => 1, 'msg' => L_('该地区没有数据'));
                    }
                }
                if (isset($param['order_from']) && $param['order_from'] == 6) {
                    $where['_string'] = "o.order_from = 6 AND ";
                } elseif (isset($param['order_from']) && $param['order_from'] == 0) {
                    $where['_string'] = "o.order_from != 6 AND ";
                }
                if ($param['begin_time'] > 0) {
                    $objActSheet->setCellValue('A1', L_("日期：X1至X2", array("X1" => $param['begin_time'], "X2" => $param['end_time'])));
                } else {
                    $objActSheet->setCellValue('A1', L_('全部数据'));
                }

                if ($param['selectTimeType'] > 0) {
                    $start_time = strtotime(date('Y-m-d', time() - ($param['selectTimeType'] - 1) * 86400));
                    $end_time = time();
                    $objActSheet->setCellValue('A1', L_("日期：X1天内", array("X1" => $param['selectTimeType'])));
                }

                if ($end_time < $start_time) {
                    return array('error_code' => 1, 'msg' => L_('结束时间不能比开始时间小'));
                }
                $param['merchant_session']['mer_id'] && $where['mer_id'] = $param['merchant_session']['mer_id'];

                if ($mer_list && $mer_list != -1) {
                    $where['mer_id'] = array('in', $mer_list);
                }

                if ($store_list && $store_list != -1) {
                    $where['store_id'] = array('in', $store_list);
                }

                if ($param['type_list'] && $param['type_list'] != -1) {
                    $where['sort_id'] = array('in', $param['type_list']);
                }

                if ($goods_name) {
                    $where['name'] = array('like', "%" . $goods_name . "%");
                }


                $listRows = 1000;
                if ($type == 'meal' || $type == 'foodshop') {
                    if ($start_time) {
                        $where['_string'] .= "o.create_time <= {$end_time} AND o.create_time>={$start_time}";
                    }

                    $where_tmp = array();
                    foreach ($where as $key => &$v) {

                        if ($key != '_string') {

                            if ($key != 'mer_id') {
                                $tmp['d.' . $key] = $v;
                            } else {
                                $tmp['o.' . $key] = $v;
                            }
                        } else {
                            $tmp['_string'] = $v;
                        }
                        $where_tmp[] = $tmp;
                        unset($where[$key]);
                    }
                    $where = $where_tmp;


//                  $all_sale_money = M('Foodshop_order_detail')->where($where)->sum('price');
//                  //$count = M('Foodshop_order_detail')->where($where)->group('goods_id')->count();
//                  $subQuery = M('Foodshop_order_detail')->field('goods_id')->group('goods_id')->where($where)->buildSql();
//                  $count =  M()->table($subQuery.' a')->count();
                    $all_sale_money = floatval(M('Foodshop_order_detail')->join('as d LEFT JOIN ' . C('DB_PREFIX') . 'foodshop_order AS o ON d.order_id = o.order_id ')->where($where)->sum('d.price*d.num'));
                    $all_sale_num = floatval(M('Foodshop_order_detail')->join('as d LEFT JOIN ' . C('DB_PREFIX') . 'foodshop_order AS o ON d.order_id = o.order_id ')->where($where)->sum('d.num'));

                    //$count = M('Foodshop_order_detail')->where($where)->group('goods_id')->count();
                    $subQuery = M('Foodshop_order_detail')->field('goods_id')->join('as d LEFT JOIN ' . C('DB_PREFIX') . 'foodshop_order AS o ON d.order_id = o.order_id ')->group('d.goods_id')->where($where)->buildSql();
                    $count = M()->table($subQuery . ' a')->count();


                    if ($param['sort'] && $count > $param['sort']) {
                        $count = $param['sort'];
                    }        
                } elseif($type == 'dining') {
                    if ($start_time) {
                        $where['_string'] .= "o.create_time <= {$end_time} AND o.create_time>={$start_time}";
                    }

                    $where_tmp = array();
                    foreach ($where as $key => &$v) {
                        if ($key != '_string') {
                            if ($key != 'mer_id') {
                                $tmp['d.' . $key] = $v;
                            } else {
                                $tmp['o.' . $key] = $v;
                            }
                        } else {
                            $tmp['_string'] = $v;
                        }
                        $where_tmp[] = $tmp;
                        unset($where[$key]);
                    }
                    $where = $where_tmp;
                    $where['o.status'] = array('in', '4,20,30,40,41,50');

                    $all_sale_money = floatval(M('Dining_order_detail')->join('as d LEFT JOIN ' . C('DB_PREFIX') . 'dining_order AS o ON d.order_id = o.order_id ')->where($where)->sum('d.price*d.num'));
                    $all_sale_num = floatval(M('Dining_order_detail')->join('as d LEFT JOIN ' . C('DB_PREFIX') . 'dining_order AS o ON d.order_id = o.order_id ')->where($where)->sum('d.num'));

                    $subQuery = M('Dining_order_detail')->field('goods_id')->join('as d LEFT JOIN ' . C('DB_PREFIX') . 'dining_order AS o ON d.order_id = o.order_id ')->group('d.goods_id')->where($where)->buildSql();
                    $count = M()->table($subQuery . ' a')->count();

                    if ($param['sort'] && $count > $param['sort']) {
                        $count = $param['sort'];
                    }
                } else {

                    if ($start_time) {
                        $where['_string'] .= "o.create_time <= {$end_time} AND o.create_time>={$start_time}";
                    }
                    $where_tmp = array();
                    foreach ($where as $key => &$v) {
                        if ($key != '_string') {

                            if ($key != 'mer_id') {
                                $tmp['d.' . $key] = $v;
                            } else {
                                $tmp['o.' . $key] = $v;
                            }
                        } else {
                            $tmp['_string'] = $v;
                        }
                        $where_tmp[] = $tmp;
                        unset($where[$key]);
                    }
                    $where = $where_tmp;
                    $where['o.paid'] = 1;
                    $where['o.status'] = array('in', '0,1,2,3');
                    $all_sale_money = floatval(M('Shop_order_detail')->join('as d LEFT JOIN ' . C('DB_PREFIX') . 'shop_order AS o ON d.order_id = o.order_id ')->where($where)->sum('d.price*d.num'));
                    $all_sale_num = floatval(M('Shop_order_detail')->join('as d LEFT JOIN ' . C('DB_PREFIX') . 'shop_order AS o ON d.order_id = o.order_id ')->where($where)->sum('d.num'));
                    $subQuery = M('Shop_order_detail')->join('as d LEFT JOIN ' . C('DB_PREFIX') . 'shop_order AS o ON d.order_id = o.order_id ')->field('d.goods_id')->group('d.goods_id')->where($where)->buildSql();

                    $count = M()->table($subQuery . ' a')->count();
                    if ($param['sort'] && $count > $param['sort']) {
                        $count = $param['sort'];
                    }
                }

                $lenght = $count / 1000;
                $merchant_arr = array();
                $store_arr = array();
                $sort_arr = array();
                $rank_i = 0;
                for ($i = 0; $i < $lenght; $i++) {
                    foreach ($$cell_name as $key => $v) {
                        $objActSheet->getColumnDimension($col_char[$col_k])->setWidth(20);
                        $objActSheet->setCellValue($col_char[$col_k] . $title_index, $v);
                        $col_k++;
                    }

                    if ($type == 'meal' || $type == 'foodshop') {
                        $res = M('Foodshop_order_detail')->join('as d LEFT JOIN ' . C('DB_PREFIX') . 'foodshop_order AS o ON d.order_id = o.order_id ')->where($where)->group('d.goods_id,d.spec')->order('sale_money DESC')->field("d.sort_id,d.goods_id,d.spec,d.name,d.price,d.store_id,sum(d.price * d.num) as sale_money ,sum(d.num) as sale_count,concat ( left (sum(d.price*d.num)/{$all_sale_money} *100,5),'%') as sale_percent")->limit($i * 1000, $listRows)->select();

                    }elseif($type == 'dining'){                  
                        $res = M('Dining_order_detail')->join('as d LEFT JOIN ' . C('DB_PREFIX') . 'dining_order AS o ON d.order_id = o.order_id ')->where($where)->group('d.goods_id,d.spec')->order($order_sort)->field("d.goods_id,d.spec,d.name,d.price,d.store_id,d.number,sum(d.price * d.num) as sale_money ,sum(d.num) as sale_count,concat ( left (sum(d.price*d.num)/{$all_sale_money} *100,5),'%') as sale_percent")->limit($start, $pagesize)->select();
                    } else {
                        $where['o.paid'] = 1;
                        $where['o.status'] = array('in', '0,1,2,3');
                        $res = M('Shop_order_detail')->join('as d LEFT JOIN ' . C('DB_PREFIX') . 'shop_order AS o ON d.order_id = o.order_id ')->where($where)->group('d.goods_id')->order('sale_money DESC')->field("d.sort_id,d.goods_id,d.name,d.price,d.store_id,d.number,sum(d.price * d.num) as sale_money ,sum(d.num) as sale_count,concat ( left (sum(d.price * d.num)/{$all_sale_money} *100,5),'%') as sale_percent")->limit($i * 1000, $listRows)->select();
                    }


                    $index = $i == 0 ? 3 : 2;
                    foreach ($res as $v) {
                        $rank_i++;
                        if (!$store_arr[$v['store_id']]) {
                            $store_arr[$v['store_id']] = $store = M('Merchant_store')->field('store_id,name,province_id,city_id,area_id')->where(array('store_id' => $v['store_id']))->find();
                        } else {
                            $store = $store_arr[$v['store_id']];
                        }

                        $v['type'] = $type_name;
                        $v['store_name'] = $store['name'];

                        if ($v['sort_id'] > 0) {
                            if (!$sort_arr[$v['sort_id']]) {
                                if ($type == 'meal' || $type == 'dining') {
                                    $sort_arr[$v['sort_id']] = $sort_info = M('Foodshop_goods_sort')->where(array('sort_id' => $v['sort_id']))->find();
                                } else {
                                    $sort_arr[$v['sort_id']] = $sort_info = M(_view('Shop_goods_sort'))->where(array('sort_id' => $v['sort_id']))->find();

                                }
                            } else {
                                $sort_info = $sort_arr[$v['sort_id']];
                            }
                            $v['sort_name'] = $sort_info['sort_name'];
                        }
                        $v['rank'] = $rank_i;
                        $col_k = 0;
                        foreach ($$cell_name as $k => $vv) {
                            $objActSheet->setCellValueExplicit($col_char[$col_k] . $index, $v[$k]);
                            $col_k++;
                        }
                        $index++;
                    }


                }


                break;
            case 'card_new':
                $title = L_('会员信息');
                // 设置文档基本属性
                $objProps->setCreator($title);
                $objProps->setTitle($title);
                $objProps->setSubject($title);
                $objProps->setDescription($title);
                // 设置当前的sheet
                $objExcel->setActiveSheetIndex(0);
                $database_user = D('User');

                if (!empty($param['keyword'])) {
                    if ($param['searchtype'] == 'phone') {
                        $condition_user['u.phone'] = array('like', '%' . $param['keyword'] . '%');
                    }
                    if ($param['searchtype'] == 'card_id') {
                        $condition_['c.id'] = array('like', '%' . $param['keyword'] . '%');
                        $condition_['c.wx_card_code'] = array('like', '%' . $param['keyword'] . '%');
                        $condition_['_logic'] = 'or';
                        $condition_user['_complex'] = $condition_;
                    }
                    if ($param['searchtype'] == 'physical_id') {
                        $condition_user['c.physical_id'] = array('like', '%' . $param['keyword'] . '%');
                    }
                    if ($param['searchtype'] == 'nickname') {
                        $condition_user['u.nickname'] = array('like', '%' . $param['keyword'] . '%');
                    }
                }

                if(!empty($param['begin_time']) && !empty($param['end_time'])){
                    $condition_user['c.add_time'] = array('between', [strtotime($param['begin_time']), strtotime($param['end_time'])+86400]);
                }


                $condition_user['c.mer_id'] = $param['merchant_session']['mer_id'];
                $count_user = M('Card_userlist')->join('as c left join ' . C('DB_PREFIX') . 'user as u ON u.uid = c.uid')->where($condition_user)->count();

                $length = ceil($count_user / 2000);
                for ($i = 0; $i < $length; $i++) {
                    $i && $objExcel->createSheet();
                    $objExcel->setActiveSheetIndex($i);

                    $objExcel->getActiveSheet()->setTitle(L_('会员分页') . $i);
                    $objActSheet = $objExcel->getActiveSheet();

                    $objActSheet->setCellValue('A1', L_('会员卡号'));
                    $objActSheet->setCellValue('B1', L_('微信卡号'));
                    $objActSheet->setCellValue('C1', L_('用户姓名'));
                    $objActSheet->setCellValue('D1', L_('用户生日'));
                    $objActSheet->setCellValue('E1', L_('用户手机'));
                    $objActSheet->setCellValue('F1', L_('会员卡余额'));
                    $objActSheet->setCellValue('G1', L_('会员卡积分'));
                    $objActSheet->setCellValue('H1', L_('实体卡号'));
                    $objActSheet->setCellValue('I1', L_('领卡时间'));
                    $objActSheet->setCellValue('J1', L_('会员卡状态'));
                    $objActSheet->setCellValue('K1', L_('备注'));
                    $user_list = M('Card_userlist')->field('c.*,u.*,c.add_time as card_add_time,c.status as card_status,remark')->join('as c left join ' . C('DB_PREFIX') . 'user as u ON u.uid = c.uid')->where($condition_user)->order('c.id DESC')->limit($i * 2000 . ',2000')->select();
                    if (!empty($user_list)) {
                        $index = 2;
                        foreach ($user_list as $value) {
                            $objActSheet->setCellValueExplicit('A' . $index, $value['id']);
                            $objActSheet->setCellValueExplicit('B' . $index, $value['wx_card_code']);
                            $objActSheet->setCellValueExplicit('C' . $index, $value['name']);
                            $objActSheet->setCellValueExplicit('D' . $index, $value['birthday'] . ' ');
                            $objActSheet->setCellValueExplicit('E' . $index, $value['phone']);
                            $objActSheet->setCellValueExplicit('F' . $index, $value['card_money'] + $value['card_money_give']);
                            $objActSheet->setCellValueExplicit('G' . $index, $value['card_score']);
                            $objActSheet->setCellValueExplicit('H' . $index, $value['physical_id'] . ' ');
                            $objActSheet->setCellValueExplicit('I' . $index, $value['add_time'] ? date('Y-m-d H:i:s', $value['add_time']) : '');
                            $objActSheet->setCellValueExplicit('J' . $index, $value['card_status'] == '1' ? L_('正常') : L_('禁止'));
                            $objActSheet->setCellValueExplicit('K' . $index, $value['remark']);
                            $index++;
                        }
                    }
                    sleep(2);
                }
                break;
            case 'fans':
                $title = L_('粉丝信息');
                // 设置文档基本属性
                $objProps->setCreator($title);
                $objProps->setTitle($title);
                $objProps->setSubject($title);
                $objProps->setDescription($title);
                // 设置当前的sheet
                $objExcel->setActiveSheetIndex(0);

                $where = " `m`.`mer_id`={$param['merchant_session']['mer_id']}";

                $mode = new Model();
                $sql_count = "SELECT COUNT(1) as count FROM " . C('DB_PREFIX') . "merchant_user_relation AS m LEFT JOIN " . C('DB_PREFIX') . "user AS u ON `m`.`openid`=`u`.`openid` WHERE {$where}";

                $count_user = $mode->query($sql_count);


                $from_type = array(
                    '0' => L_('扫描商家产品二维码'),
                    '1' => L_('扫描商家二维码'),
                    '2' => L_('平台赠送'),
                    '3' => L_('扫描产品推广二维码'),
                    '5' => C('config.group_alias_name'),
                    '6' => C('config.shop_alias_name'),
                    '7' => C('config.meal_alias_name'),
                    '8' => C('config.appoint_alias_name'),
                    '9' => C('config.cash_alias_name'),
                );

                $length = ceil($count_user[0]['count'] / 2000);

                for ($i = 0; $i < $length; $i++) {
                    $i && $objExcel->createSheet();
                    $objExcel->setActiveSheetIndex($i);

                    $objExcel->getActiveSheet()->setTitle(L_('用户分页') . $i);
                    $objActSheet = $objExcel->getActiveSheet();

                    $objActSheet->setCellValue('A1', L_('会员ID'));
                    $objActSheet->setCellValue('B1', L_('会员昵称'));
                    $objActSheet->setCellValue('C1', L_('手机号'));
                    $objActSheet->setCellValue('D1', L_('性别'));
                    $objActSheet->setCellValue('E1', L_('省(直辖市)'));
                    $objActSheet->setCellValue('F1', L_('城市'));
                    $objActSheet->setCellValue('G1', L_('关注时间'));
                    $objActSheet->setCellValue('H1', L_('最后登录'));
                    $objActSheet->setCellValue('I1', L_('获取渠道'));
                    $start = $i * 2000;
                    $sql = "SELECT u.*, m.* FROM " . C('DB_PREFIX') . "merchant_user_relation AS m LEFT JOIN " . C('DB_PREFIX') . "user AS u ON `m`.`openid`=`u`.`openid` WHERE {$where} ORDER BY `m`.`dateline` DESC LIMIT  $start,2000 ";
                    $user_list = $mode->query($sql);

                    if (!empty($user_list)) {

                        $index = 2;
                        foreach ($user_list as $value) {

                            $objActSheet->setCellValueExplicit('A' . $index, $value['uid']);
                            $objActSheet->setCellValueExplicit('B' . $index, $value['nickname']);
                            $sex = $value['sex'] == 0 ? L_('未知') : ($value['sex'] == 1 ? L_('男') : L_('女'));
                            $objActSheet->setCellValueExplicit('C' . $index, $value['phone']);
                            $objActSheet->setCellValueExplicit('D' . $index, $sex);
                            $objActSheet->setCellValueExplicit('E' . $index, $value['province'] . ' ');
                            $objActSheet->setCellValueExplicit('F' . $index, $value['city']);
                            $objActSheet->setCellValueExplicit('G' . $index, $value['dateline'] ? date('Y-m-d H:i:s', $value['dateline']) : '');
                            $objActSheet->setCellValueExplicit('H' . $index, $value['last_time'] ? date('Y-m-d H:i:s', $value['last_time']) : '');
                            $objActSheet->setCellValueExplicit('I' . $index, $from_type[$value['from_merchant']]);

                            $index++;
                        }
                    }
                    sleep(2);
                }
                break;
            case 'merchant_list':
                $param['type'] = 'merchant_list';
                $title = L_('商家列表');
                // 设置文档基本属性
                $objProps->setCreator($title);
                $objProps->setTitle($title);
                $objProps->setSubject($title);
                $objProps->setDescription($title);
                // 设置当前的sheet
                $objExcel->setActiveSheetIndex(0);


                $database_merchant = D('Merchant');
                //搜索
                if (!empty($param['keyword'])) {
                    if ($param['searchtype'] == 'mer_id') {
                        $condition_merchant['mer_id'] = $param['keyword'];
                    } else if ($param['searchtype'] == 'account') {
                        $condition_merchant['account'] = array('like', '%' . $param['keyword'] . '%');
                    } else if ($param['searchtype'] == 'name') {
                        $condition_merchant['name'] = array('like', '%' . $param['keyword'] . '%');
                    } else if ($param['searchtype'] == 'phone') {
                        $condition_merchant['phone'] = array('like', '%' . $param['keyword'] . '%');
                    } else if ($param['searchtype'] == 'store') {
                        $where['name'] = array('like', '%' . htmlspecialchars($param['keyword']) . '%');
                        $store_list = M('Merchant_store')->field('mer_id')->where($where)->group('mer_id')->select();
                        foreach ($store_list as $value) {
                            $store_arr[] = $value['mer_id'];
                        }
                        $condition_merchant['mer_id'] = array('in', $store_arr);
                    }
                }
                if ($param['system_session']['area_id']) {
                    $now_area = D('Area')->field(true)->where(array('area_id' => $param['system_session']['area_id']))->find();
                    if ($now_area['area_type'] == 3) {
                        $area_index = 'area_id';
                    } elseif ($now_area['area_type'] == 2) {
                        $area_index = 'city_id';
                    } elseif ($now_area['area_type'] == 1) {
                        $area_index = 'province_id';
                    }

                    // $area_index = $this->system_session['level'] == 1 ? 'area_id' : 'city_id';
                    $condition_merchant[$area_index] = $param['system_session']['area_id'];
                }
                if ($param['province_idss']) {
                    $condition_merchant['province_id'] = $param['province_idss'];
                }
                if ($param['city_idss']) {
                    $condition_merchant['city_id'] = $where['city_id'] = $param['city_idss'];
                }
                if ($param['area_id']) {
                    $condition_merchant['area_id'] = $where['area_id'] = $param['area_id'];
                }

                $searchstatus = intval($param['searchstatus']);
                switch ($searchstatus) {
                    case 0:
                        //3 是欠款状态 该状态下商家业务暂停，不能支付，但是可以管理，充值
                        $condition_merchant['_string'] = 'status = 1 OR status = 3';
                        break;
                    case 1:
                        $condition_merchant['status'] = 2;
                        break;
                    case 2:
                        $condition_merchant['status'] = 0;
                        break;
                }

                if (!isset($condition_merchant['status'])) {
                    $condition_merchant['status'] = array('lt', 4);//4：假删除状态
                }
                switch ($param['searchorder']) {
                    case 0:
                        $order = 'mer_id DESC';
                        break;
                    case 1:
                        $order = 'money DESC,mer_id DESC';
                        break;

                }

                if(C('customization.life_tools') && $param['system_session']['level'] != 2){// 城投生活通定制 商家绑定管理员
                    $adminIdArr = D('Admin')->getAdminByFid($param['system_session']['id']);
                    $condition_merchant['admin_id'] = ['in', $adminIdArr];
                }

                $sql_count = $database_merchant->where($condition_merchant)->count();
                $count = $sql_count;
                set_time_limit(0);
                $length = ceil($count / 1000);

                for ($i = 0; $i < $length; $i++) {
                    $i && $objExcel->createSheet();
                    $objExcel->setActiveSheetIndex($i);
                    $objExcel->getActiveSheet()->setTitle(L_('第X1个一千个商家信息', ($i + 1)));
                    $objActSheet = $objExcel->getActiveSheet();
                    $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);

                    $objActSheet->setCellValue('A1', L_('商家编号'));
                    $objActSheet->setCellValue('B1', L_('商家名称'));
                    $objActSheet->setCellValue('C1', L_('联系电话'));
                    $objActSheet->setCellValue('D1', L_('粉丝数'));
                    $objActSheet->setCellValue('E1', L_('账户余额'));
                    $objActSheet->setCellValue('F1', L_('店铺编号'));
                    $objActSheet->setCellValue('G1', L_('店铺名称'));
                    $objActSheet->setCellValue('H1', L_('店铺电话'));
                    $list = M('Merchant')->where($condition_merchant)->order($order)->limit($i * 1000, 1000)->select();

                    if (!empty($list)) {
                        $index = 2;
                        foreach ($list as $value) {
                            $fans_count = M('Merchant_user_relation')->where(array('mer_id' => $value['mer_id']))->count();
                            $objActSheet->setCellValueExplicit('A' . $index, $value['mer_id']);
                            $objActSheet->setCellValueExplicit('B' . $index, $value['name']);
                            $objActSheet->setCellValueExplicit('C' . $index, $value['phone']);
                            $objActSheet->setCellValueExplicit('D' . $index, $fans_count);
                            $objActSheet->setCellValueExplicit('E' . $index, $value['money']);
                            $objActSheet->setCellValueExplicit('F' . $index, '');
                            $objActSheet->setCellValueExplicit('G' . $index, '');
                            $objActSheet->setCellValueExplicit('H' . $index, '');
                            $store_list = M('Merchant_store')->where(array('mer_id' => $value['mer_id']))->select();
                            $index++;
                            if ($store_list) {
                                foreach ($store_list as $s) {
                                    $objActSheet->setCellValueExplicit('A' . $index, '');
                                    $objActSheet->setCellValueExplicit('B' . $index, '');
                                    $objActSheet->setCellValueExplicit('C' . $index, '');
                                    $objActSheet->setCellValueExplicit('D' . $index, '');
                                    $objActSheet->setCellValueExplicit('E' . $index, '');
                                    $objActSheet->setCellValueExplicit('F' . $index, $s['store_id']);
                                    $objActSheet->setCellValueExplicit('G' . $index, $s['name']);
                                    $objActSheet->setCellValueExplicit('H' . $index, $s['phone']);
                                    $index++;
                                }
                            }
                        }
                    }
                    sleep(2);
                }
                break;
            case 'percentage':

                $title = L_('抽成列表');

                // 设置文档基本属性
                $objProps->setCreator($title);
                $objProps->setTitle($title);
                $objProps->setSubject($title);
                $objProps->setDescription($title);
                // 设置当前的sheet
                $objExcel->setActiveSheetIndex(0);
                $start_time = strtotime($param['start_time']);
                $end_time = strtotime($param['end_time']) + 86400;
                if (!empty($param['keyword'])) {
                    if ($param['searchtype'] == 'mer_id') {
                        $condition_merchant['mer_id'] = $param['keyword'];
                    } else if ($param['searchtype'] == 'account') {
                        $condition_merchant['account'] = array('like', '%' . $param['keyword'] . '%');
                    } else if ($param['searchtype'] == 'name') {
                        $condition_merchant['name'] = array('like', '%' . $param['keyword'] . '%');
                    } else if ($param['searchtype'] == 'phone') {
                        $condition_merchant['phone'] = array('like', '%' . $param['keyword'] . '%');
                    }
                }
                if ($param['area_id']) {
                    $condition_merchant['area_id'] = $param['area_id'];
                    $where['area_id'] = $condition_merchant['area_id'];
                }
                if ($param['city_id']) {
                    $condition_merchant['city_id'] = $param['city_id'];
                    $where['city_id'] = $condition_merchant['city_id'];
                }

                if ($param['province_id']) {
                    $condition_merchant['province_id'] = $param['province_id'];
                    $where['province_id'] = $condition_merchant['province_id'];
                }
                if($param['street_id'] > 0){
                    $condition_merchant['street_id'] = $param['street_id'];
                    $where['street_id'] = $condition_merchant['street_id'];
                }
                $param['invit_code'] && $condition_merchant['invit_code'] = $param['invit_code'];

                if ($end_time < $start_time) {
                    return array('error_code' => 1, 'msg' => L_('结束时间不能比开始时间小'));
                }

                if (isset($param['begin_time']) && isset($param['end_time']) && !empty($param['begin_time']) && !empty($param['end_time'])) {
                    if ($param['begin_time'] > $param['end_time']) {
                        return array('error_code' => 1, 'msg' => L_('结束时间不能比开始时间小'));
                    }
                    $period = array(strtotime($param['begin_time'] . " 00:00:00"), strtotime($param['end_time'] . " 23:59:59"));
                    $time_condition = " (use_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
                    $condition_merchant['_string'] = $time_condition;

                }

                $database_merchant = M('Merchant');
                $condition_merchant['area_id'] && $where['area_id'] = $condition_merchant['area_id'];
                $count_merchant = $database_merchant->where($where)->count();
                $time_condition = '';
                foreach ($condition_merchant as $k => $v) {
                    if ($k != '_string') {
                        $condition_merchant['m.' . $k] = $v;
                    } else {
                        $time_condition = 'WHERE ' . $condition_merchant[$k];
                    }
                    unset($condition_merchant[$k]);
                }
                $extra_str = '';
                $extra_field = '';

                import('@.ORG.system_page');

                $length = ceil($count_merchant / 1000);
                for ($i = 0; $i < $length; $i++) {
                    $i && $objExcel->createSheet();
                    $objExcel->setActiveSheetIndex($i);
                    $objExcel->getActiveSheet()->setTitle(L_('第X1个一千个商家抽成信息', ($i + 1)));
                    $objActSheet = $objExcel->getActiveSheet();
                    $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);


                    $objActSheet->setCellValue('A1', L_('编号'));
                    $objActSheet->setCellValue('B1', L_('商家名称'));
                    $objActSheet->setCellValue('C1', L_('联系电话'));
                    $objActSheet->setCellValue('D1', L_('当前商家余额'));
                    $objActSheet->setCellValue('E1', L_('送出积分'));
                    $objActSheet->setCellValue('F1', L_('抽成金额'));
                    $mer_percentage_list = $database_merchant->join('as m left join ' . '(SELECT  mer_id,SUM(system_take) AS  money ,SUM(score) as all_score FROM '
                        . C('DB_PREFIX') . 'merchant_money_list ' . $time_condition . ' GROUP BY mer_id) w ON m.mer_id = w.mer_id  ' . $extra_str)
                        ->field('m.mer_id,m.money as now_money,m.name,m.phone,w.money,w.all_score' . $extra_field)
                        ->where($condition_merchant)
                        ->order('w.money DESC,w.all_score DESC')
                        ->limit($i * 1000, 1000)
                        ->select();

                    if (!empty($mer_percentage_list)) {
                        $index = 2;
                        foreach ($mer_percentage_list as $value) {
                            $objActSheet->setCellValueExplicit('A' . $index, $value['mer_id']);
                            $objActSheet->setCellValueExplicit('B' . $index, $value['name']);
                            $objActSheet->setCellValueExplicit('C' . $index, $value['phone']);
                            $objActSheet->setCellValueExplicit('D' . $index, $value['now_money']);
                            $objActSheet->setCellValueExplicit('E' . $index, floatval($value['all_score']));
                            $objActSheet->setCellValueExplicit('F' . $index, floatval($value['money']));
                            $index++;
                        }
                    }
                    sleep(2);
                }
                break;
            case 'admin_recharge':
                $title = L_('管理员充值');
                // 设置文档基本属性
                $objProps->setCreator($title);
                $objProps->setTitle($title);
                $objProps->setSubject($title);
                $objProps->setDescription($title);
                // 设置当前的sheet
                $objExcel->setActiveSheetIndex(0);
                $model = M('User_money_list');
                $where['l.admin_id'] = array('neq', 0);
                if (!empty($param['admin_id'])) {
                    if ($param['admin_id'] == '0') {
                        $where['l.admin_id'] = array('neq', 0);
                    } else {
                        $where['l.admin_id'] = $param['admin_id'];
                    }
                }
                if (!empty($param['begin_time']) && !empty($param['end_time'])) {
                    if ($param['begin_time'] > $param['end_time']) {
                        $this->error_tips(L_("结束时间应大于开始时间"));
                    }
                    $period = array(strtotime($param['begin_time'] . " 00:00:00"), strtotime($param['end_time'] . " 23:59:59"));
                    $where['_string'] = " (l.time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";

                }
                $count = $model->join('as l left join ' . C('DB_PREFIX') . 'admin a ON a.id = l.admin_id')->where($where)->count();

                $length = ceil($count / 2000);

                for ($i = 0; $i < $length; $i++) {
                    $i && $objExcel->createSheet();
                    $objExcel->setActiveSheetIndex($i);

                    $objExcel->getActiveSheet()->setTitle(L_('用户分页') . $i);
                    $objActSheet = $objExcel->getActiveSheet();

                    $objActSheet->setCellValue('A1', L_('订单编号'));
                    $objActSheet->setCellValue('B1', L_('订单信息'));
                    $objActSheet->setCellValue('C1', L_('订单用户'));
                    $objActSheet->setCellValue('D1', L_('操作管理员'));
                    $objActSheet->setCellValue('E1', L_('时间'));


                    $user_list = $model->field('l.pigcms_id,l.money,l.desc,l.type,l.time,l.admin_id,l.uid,u.nickname,u.phone,a.realname,a.level')->join('as l left join ' . C('DB_PREFIX') . 'user u ON l.uid = u.uid left join ' . C('DB_PREFIX') . 'admin a ON a.id = l.admin_id')->where($where)->order('l.pigcms_id DESC')->limit($i * 2000 . ',2000')->select();
                    if (!empty($user_list)) {

                        $index = 2;
                        foreach ($user_list as $value) {
                            $objActSheet->setCellValueExplicit('A' . $index, $value['pigcms_id']);
                            if ($value['type'] == 1) {
                                $money_text = L_('充值');
                            } else {
                                $money_text = L_('减少');
                            }
                            $money_text .= $value['money'] . C('config.Currency_txt');
                            $objActSheet->setCellValueExplicit('B' . $index, $money_text);
                            $objActSheet->setCellValueExplicit('C' . $index, $value['nickname']);
                            $objActSheet->setCellValueExplicit('D' . $index, $value['realname'] . ' ');
                            $objActSheet->setCellValueExplicit('E' . $index, date("Y-m-d", $value['time']));
                            $index++;
                        }
                    }
                    sleep(2);
                }
                break;
            case 'store':
                $title = C('config.store_alias_name') . L_('账单');

                if ($param['merchant_session']) {
                    $condition_where = " o.mer_id = " . $param['merchant_session']['mer_id'];
                } else {
                    $condition_where = " 1=1";
                }

                if ($param['store_session']) {
                    $condition_where = " o.store_id = " . $param['store_session']['store_id'];
                }

                if (!empty($param['keyword'])) {
                    if ($param['searchtype'] == 'order_id') {
                        $condition_where .= " AND  `o`.`order_id`='" . $param['keyword'] . "'";
                    } else if ($param['searchtype'] == 'orderid') {
                        // $where['orderid'] = trim($param['keyword']);
                        // $tmp_result = M('Tmp_orderid')->where(array('orderid' => $param['keyword']))->find();
                        // $condition_where .= " AND  `o`.`order_id`='" . trim($_GET['keyword']) . "'";
                        $condition_where .= " AND  `o`.`orderid`='" . trim($param['keyword']) . "'";
                    } elseif ($param['searchtype'] == 'name') {
                        $condition_where .= " AND  `u`.`nickname` like '%" . $param['keyword'] . "%'";
                    } elseif ($param['searchtype'] == 'phone') {
                        $condition_where .= " AND `u`.`phone` like '%" . trim($_GET['keyword']) . "%'";
                    } elseif ($param['searchtype'] == 'third_id') {
                        $condition_where .= " AND `o`.`third_id` ='" . $param['keyword'] . "'";
                    } elseif ($param['searchtype'] == 'store_name') {
                        $condition_where .= " AND  `ms`.`name` like '%" . $param['keyword'] . "%'";
                    }
                }

                if ($param['system_session']['area_id']) {
                    $now_area = D('Area')->field(true)->where(array('area_id' => $param['system_session']['area_id']))->find();
                    if ($now_area['area_type'] == 3) {
                        $area_index = 'area_id';
                    } elseif ($now_area['area_type'] == 2) {
                        $area_index = 'city_id';
                    } elseif ($now_area['area_type'] == 1) {
                        $area_index = 'province_id';
                    }
                    $condition_where .= " AND `m`.`{$area_index}`={$param['system_session']['area_id']}";
                }

                $pay_type = isset($param['pay_type']) && $param['pay_type'] ? $param['pay_type'] : '';

                if ($pay_type && $pay_type != 'balance') {
                    if (strpos($pay_type, 'App')) {
                        preg_match('/(\w+)App/', $pay_type, $res);
                        $condition_where .= " AND (`o`.`pay_type`='" . $res[1] . "' AND o.is_mobile_pay=2) ";
                    } else {
                        $condition_where .= " AND ( `o`.`pay_type`='" . $pay_type . "')";
                    }
                } else if ($pay_type == 'balance') {
                    $condition_where .= " AND (`o`.`balance_pay`<>0 OR `o`.`merchant_balance` <> 0)";
                }

                if (!empty($param['begin_time']) && !empty($param['end_time'])) {
                    if ($param['begin_time'] > $param['end_time']) {
                        return false;
                        //$this->error_tips("结束时间应大于开始时间");
                    }
                    $period = array(strtotime($param['begin_time'] . " 00:00:00"), strtotime($param['end_time'] . " 23:59:59"));
                    $condition_where .= " AND (o.pay_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
                    //$condition_where['_string']=$time_condition;
                }

                if ($param['area_id'] && $param['area_id'] != 'undefined') {
                    $condition_where .= " AND m.area_id={$param['area_id']}";
                } else if ($param['city_idss'] && $param['city_idss'] != 'undefined') {
                    $condition_where .= " AND m.city_id={$param['city_idss']}";
                } else if ($param['province_idss'] && $param['province_idss'] != 'undefined') {
                    $condition_where .= " AND m.province_id={$param['province_idss']}";
                } else if ($param['circle_id'] && $param['circle_id'] != 'undefined') {
                    $condition_where .= " AND m.circle_id={$param['circle_id']}";
                }

                if ($param['paid']) {
                    $condition_where .= " AND o.paid={$param['paid']}";
                }
                if ($param['info_type'] == 1 && $param['from_plat']) {
                    $condition_where .= " AND o.from_plat={$param['from_plat']}";
                } elseif ($param['info_type'] == 2 && $param['from_plat']) {
                    $condition_where .= " AND o.from_plat IN ({$param['from_plat']}) ";
                }


                if($param['info_type'] == 1){
                    $count = M('Store_order')->join("AS o LEFT JOIN " . C('DB_PREFIX') . "merchant AS m ON o.mer_id=m.mer_id LEFT JOIN " . C('DB_PREFIX') . "merchant_store AS ms ON o.store_id=ms.store_id LEFT JOIN " . C('DB_PREFIX') . "user AS u ON o.uid=u.uid")
                        ->where($condition_where)->count();
                }else{
                    $count = M('Store_order')->join("AS o LEFT JOIN " . C('DB_PREFIX') . "merchant AS m ON o.mer_id=m.mer_id LEFT JOIN " . C('DB_PREFIX') . "merchant_store AS ms ON o.store_id=ms.store_id LEFT JOIN " . C('DB_PREFIX') . "user AS u ON o.uid=u.uid")
                        ->where($condition_where)->count();
                }

                $length = ceil($count / 5000);
                $levelDb = M('User_level');
                $tmparr = M(_view('User_level'))->field(true)->order('`id` ASC')->select();
                $levelarr = array();
                if ($tmparr) {
                    foreach ($tmparr as $vv) {
                        $levelarr[$vv['level']] = $vv;
                    }
                }
                $m_area = M('Area');

                $csv_head = [
                    L_('商家ID'),
                    L_('商家名称'),
                    L_('店铺ID'),
                    L_('店铺名称'),
                    L_('订单编号'),
                    L_('用户ID'),
                    L_('客户姓名'),
                    L_('客户电话'),
                    L_('订单总价'),
                    L_('平台余额'),
                    L_('商家余额'),
                    L_('实付金额'),
                    L_('在线支付金额'),
                    L_("获得X1数", array("X1" => C('config.score_name'))),
                    L_("使用X1数", array("X1" => C('config.score_name'))),
                    '' . C('config.score_name') . L_('抵扣金钱'),
                    L_('平台优惠券'),
                    L_('商家优惠券'),
                    L_('商家会员卡折扣'),
                    L_('不可优惠金额'),
                    // L_('会员等级'),
                    // L_('会员等级优惠金额'),
                    L_('店铺折扣金额'),
                    L_('总计优惠金额'),
                    // L_('优惠计算方式'),
                    L_('店铺的区域商圈'),
                    L_('平台给商家结款金额'),
                    L_('支付时间'),
                    L_('支付情况'),
                    L_('支付方式'),
                    L_('第三方支付流水号'),
                    L_('订单描述'),
                    L_('备注'),
                ];
                $csv_data = [];
                
                //优化下面sql查询效率
                if ($param['merchant_session']) {
                    $condition_where .= " AND mm.mer_id = " . $param['merchant_session']['mer_id'];
                }

                for ($i = 0; $i < $length; $i++) {

                    $sql = "SELECT DISTINCT o.order_id,o.*, ms.leveloff, ms.discount_txt, ms.vip_discount_type, ms.circle_id, u.nickname, u.phone, u.level, u.uid, m.name AS merchant_name, ms.name AS store_name,mm.money as income_money,mm.system_take FROM " . C('DB_PREFIX') . "store_order AS o LEFT JOIN " . C('DB_PREFIX') . "merchant AS m ON o.mer_id=m.mer_id LEFT JOIN " . C('DB_PREFIX') . "merchant_store AS ms ON o.store_id=ms.store_id LEFT JOIN " . C('DB_PREFIX') . "user AS u ON o.uid=u.uid  left join " . C('DB_PREFIX') . "merchant_money_list mm ON ((mm.order_id = o.orderid or mm.order_id=o.order_id) AND mm.type in ('cash','store')) WHERE " . $condition_where . " ORDER BY o.order_id DESC LIMIT " . $i * 5000 . ",5000";
                    $result_list = D()->query($sql);
                    if (!empty($result_list)) {
                        foreach ($result_list as $value) {
                            // 参与优惠的金额

                            $user_level = $value['level'];
                            // 查一下会员等级名称
                            if ($user_level) {
                                $user_level_name = $levelarr[$user_level]['lname'];
                            } else {
                                $user_level_name = '-';
                            }

                            $discount_money = $value['total_price'] - $value['no_discount_money'];
                            $tmp = unserialize($value['discount_txt']);
                            // 店铺折扣金额
                            $store_discount_money = 0;
                            $store_discount_money_tip = $discount_money;
                            if ($tmp['discount_type'] == 1) {
                                $store_discount_money = round($discount_money * $tmp['discount_percent'] * 0.1, 2);
                                $store_discount_money_tip = $store_discount_money;
                                if ($store_discount_money > 0) {
                                    $store_discount_money = L_('折扣，') . sprintf("%.2f", round($discount_money - $store_discount_money, 2)) . C('config.Currency_txt');
                                }
                            } else if ($tmp['discount_type'] == 2) {
                                if (floatval($discount_money) >= floatval($tmp['condition_price'])) {
                                    $store_discount_money = $tmp['minus_price'];
                                    $store_discount_money_tip = $store_discount_money;
                                    if ($store_discount_money > 0) {
                                        $store_discount_money = L_('满减，') . $store_discount_money . C('config.Currency_txt');
                                    }
                                }
                            }
                            // 折扣方式
                            if ($value['vip_discount_type'] == 2) {
                                $vip_discount_type = L_('折上折');
                            } else {
                                $vip_discount_type = L_('折扣最优');
                            }

                            if ($param['info_type'] == 1) {

                                $level_money = 0; // 等级优惠金额
                                if (!empty($user_level)) {
                                    $leveloff = !empty($value['leveloff']) ? unserialize(($value['leveloff'])) : '';
                                    /***type:0无优惠 1百分比 2立减******/
                                    if (!empty($leveloff)) {
                                        $level_off = $leveloff[$user_level];

                                        if ($level_off['type'] == 1) {
                                            if ($value['vip_discount_type'] == 2) {
                                                // 折上折 暂定 先 折扣 店铺折扣 后 用户等级折扣
                                                $level_money = round($store_discount_money_tip * $level_off['vv'] * 0.01, 2);
                                            } else {
                                                // 折扣最优
                                                $level_money = round($store_discount_money_tip * $level_off['vv'] * 0.01, 2);
                                                if ($level_money <= ($discount_money - $store_discount_money)) {
                                                    $level_money = 0;
                                                } else {
                                                    $store_discount_money = 0;
                                                }
                                            }
                                            if ($level_money > 0) {
                                                $level_money = L_('折扣，') . sprintf("%.2f", round($store_discount_money_tip - $level_money, 2)) . C('config.Currency_txt');
                                            }
                                        } elseif ($level_off['type'] == 2) {
                                            if ($value['vip_discount_type'] == 2) {
                                                // 折上折 暂定 先 折扣 店铺折扣 后 用户等级折扣
                                                $level_money = round($store_discount_money_tip - $level_off['vv'], 2);
                                            } else {
                                                // 折扣最优
                                                $level_money = round($store_discount_money_tip - $level_off['vv'], 2);
                                                if ($level_money <= ($discount_money - $store_discount_money)) {
                                                    $level_money = 0;
                                                } else {
                                                    $store_discount_money = 0;
                                                }
                                            }
                                            if ($level_money > 0) {
                                                $level_money = L_('立减，') . $level_money . C('config.Currency_txt');
                                            }
                                        }

                                    }
                                } else {
                                    $user_level = '';
                                    $level_money = '-';
                                }
                            } else {
                                $level_money = '-';
                            }
                            // 总优惠金额 $value['discount_price']
                            // 用户uid $value['uid']
                            $store_discount_money =  $value['discount_price'];

                            // 店铺区域商圈
                            $circle_info = '';
                            $circle_id = $value['circle_id'];
                            if ($circle_id) {
                                // 获取对应商圈信息
                                $condition_area = array(
                                    'area_id' => $circle_id
                                );
                                $circle_arr = $m_area->where($condition_area)->field('area_pid, area_name, area_ip_desc')->find();
                                if (!$circle_arr['area_ip_desc']) {
                                    if ($circle_arr['area_pid']) {
                                        $condition_pid_area = array(
                                            'area_id' => $circle_arr['area_pid']
                                        );
                                        $circle_msg = $m_area->where($condition_pid_area)->getField('area_name');
                                        $circle_info = $circle_msg . $circle_arr['area_name'];
                                    } else {
                                        $circle_info = $circle_arr['area_name'];
                                    }
                                } else {
                                    $circle_info = $circle_arr['area_ip_desc'];
                                }
                            }
                            // 平台给商家结款金额
                            $platform_give_merchant = $value['balance_pay'] + $value['merchant_balance'] + $value['payment_money'] + $value['score_deducte'];

                            // 商家余额
                            $merchant_balance = $value['merchant_balance'] + $value['card_give_money'];

                            // 总优惠金额
                            $total_discount_price = sprintf("%.2f", round($value['total_price'] - $platform_give_merchant, 2));
                            if ($total_discount_price < 0) {
                                $total_discount_price = 0;
                            }

                            $csv_data[] = [
                                $value['mer_id'],
                                $value['merchant_name'],
                                $value['store_id'],
                                $value['store_name'],
                                $value['order_id'],
                                $value['uid'],
                                $value['nickname'] . ' ',
                                $value['phone'] . ' ',
                                $value['total_price'],
                                $value['balance_pay'],
                                $merchant_balance,
//                                $value['price'],
                                getFormatNumber($value['balance_pay']+$value['merchant_balance']+$value['card_give_money']+$value['payment_money']+$value['score_deducte']),
                                $value['payment_money'],
                                $value['score_give'],
                                $value['score_used_count'],
                                $value['score_deducte'],
                                $value['coupon_price'],
                                $value['card_price'],
                                $value['card_discount'] ? $value['card_discount'] . L_('折') : '',
                                $value['no_discount_money'],
                                // $user_level_name,
                                // $level_money,
                                $store_discount_money,
                                $total_discount_price,
                                // $vip_discount_type,
                                $circle_info,
                                $value['income_money'],
                                $value['pay_time'] ? date('Y-m-d H:i:s', $value['pay_time']) : '',
                                $this->get_order_status($value),
                                D('Pay')->get_pay_name($value['pay_type'], $value['is_mobile_pay'], $value['paid'],$value['order_id'],[],'store'),
                                '`'.$value['third_id'],
                                $value['desc'],
                                $value['remark'],
                                
                            ];
                        }
                    }
                }
                $filename = date("Y-m-d", time()) . '-' . $param['rand_number'] . '.csv';
                $csvFileName = './runtime/' . $filename;
                // fdump($csvFileName,'csvFileName');
                // fdump($csv_head,'csvFileName',1);
                // fdump($csv_data,'csvFileName',1);
                $this->putCsv($csvFileName, $csv_data, $csv_head);
                return true;
                break;
            case 'admin_money_list':
                $title = L_('业务员流水');

                // 设置文档基本属性
                $objProps->setCreator($title);
                $objProps->setTitle($title);
                $objProps->setSubject($title);
                $objProps->setDescription($title);
                // 设置当前的sheet
                $objExcel->setActiveSheetIndex(0);

                $condition['invit_code'] = array('neq', '');
                if (!empty($param['keyword'])) {
                    if ($param['searchtype'] == 'admin_id') {
                        $condition['id'] = $param['keyword'];
                    } elseif ($param['searchtype'] == 'realname') {
                        $condition['realname'] = array('like', "%{$param['keyword']}%");
                    } elseif ($param['searchtype'] == 'phone') {
                        $condition['phone'] = $param['keyword'];
                    }
                }

                if ($param['pid']) {
                    $system_area = M('Area')->where(array('area_id' => $param['pid']))->find();
                    $area_arr = $this->get_area_arr($param['pid'], $system_area['area_type'] + 1);
                    $area_arr[] = $param['pid'];
                    $condition['area_id'] = array('in', $area_arr);
                }

                $count = M('Admin')->where($condition)->count();
                $condition['a.area_id'] = $condition['area_id'];
                unset($condition['area_id']);

//              $admin_list = M('Admin')->where($condition)->order('id DESC')->select();
                $admin_list = M('Admin')->field('a.*,ar.area_name,ar.area_type,ar.area_ip_desc')->join('AS a left join ' . C('DB_PREFIX') . 'area ar ON a.area_id = ar.area_id ')->where($condition)->order('id DESC')->select();
                $direct_city = array('1', '21', '42', '62');
                $sprecial_city = array('3235', '3239', '3242', '2792', '1989', '2103', '2873', '566');
                $objExcel->createSheet();
                $objExcel->setActiveSheetIndex(1);
                $objExcel->getActiveSheet()->setTitle(L_('业务员流水'));
                $objActSheet = $objExcel->getActiveSheet();
                $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
                $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
                $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
                $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
                $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);

                $objActSheet->setCellValue('A1', 'ID');
                $objActSheet->setCellValue('B1', L_('业务员名称'));
                $objActSheet->setCellValue('C1', L_('区域'));
                $objActSheet->setCellValue('D1', L_('手机号'));
                $objActSheet->setCellValue('E1', L_('商家流水总额'));
                $objActSheet->setCellValue('F1', L_('推广商家数量'));
                $index = 2;
                foreach ($admin_list as &$v) {
                    $where['invit_code'] = $v['invit_code'];
                    $where['status'] = 1;
                    $v['spread_count'] = M('Merchant')->where($where)->count();

                    unset($where['status']);
                    $where['m.status'] = 1;
                    $v['spread_money'] = M('Merchant')->join('as m LEFT JOIN ' . C('DB_PREFIX') . 'merchant_money_list as l ON m.mer_id = l.mer_id')
                        ->where($where)->sum('total_money');
                    unset($where['m.status']);
                    if ($v['level'] == 1) {
                        if ($vo['area_id'] > 0 && $v['area_ip_desc'] != '') {
                            $area_des = $v['area_ip_desc'];
                        } else if (in_array($v['area_id'], $direct_city)) {
                            $area_des = $v['area_name'] . L_('市');
                        } else if (in_array($v['area_id'], $special_city)) {
                            $area_des = $v['area_name'];

                        } else {

                            $area_des = $vo['area_name'] . L_('省');
                        }
                    }


                    $objActSheet->setCellValueExplicit('A' . $index, $v['id']);
                    $objActSheet->setCellValueExplicit('B' . $index, $v['realname']);
                    $objActSheet->setCellValueExplicit('C' . $index, $v['phone']);
                    $objActSheet->setCellValueExplicit('D' . $index, $v['phone']);
                    $objActSheet->setCellValueExplicit('E' . $index, floatval($v['spread_money']));
                    $objActSheet->setCellValueExplicit('F' . $index, floatval($v['spread_count']));
                    $index++;


                }

                break;
            case 'salesman_list':
                $title = L_('业务员流水');

                // 设置文档基本属性
                $objProps->setCreator($title);
                $objProps->setTitle($title);
                $objProps->setSubject($title);
                $objProps->setDescription($title);
                // 设置当前的sheet
                $objExcel->setActiveSheetIndex(0);

                $condition['invit_code'] = array('neq', '');
                if (!empty($param['keyword'])) {
                    if ($param['searchtype'] == 'admin_id') {
                        $condition['id'] = $param['keyword'];
                    } elseif ($param['searchtype'] == 'realname') {
                        $condition['realname'] = array('like', "%{$param['keyword']}%");
                    } elseif ($param['searchtype'] == 'phone') {
                        $condition['phone'] = $param['keyword'];
                    }
                }
                if (empty($param['pid'])) {
                    if ($param['system_session']['area_id']) {
                        $system_area_id = $param['system_session']['area_id'];
                        if ($system_area_id) {
                            $system_area = M('Area')->where(array('area_id' => $system_area_id))->find();
                            $area_arr = $this->get_area_arr($system_area_id, $system_area['area_type'] + 1);
                            $condition['area_id'] = array('in', $area_arr);
                        }
                    } else {
                        $area_arr = $this->get_area_arr(0, 3);
                        $condition['area_id'] = array('in', $area_arr);
                    }
                }
                if ($param['province_idss'] == 0) {
                    $param['area_type'] = 1;
                }

                if ($param['province_idss'] && C('config.many_city')) {
                    $condition_area['area_id'] = $param['province_idss'];
                    $param['area_type'] = 1;
                }

                if ($param['city_idss']) {
                    $condition_area['area_id'] = $where['city_id'] = $param['city_idss'];
                    $param['area_type'] = 2;
                }

                if ($param['area_id']) {
                    $condition_area['area_id'] = $where['area_id'] = $param['area_id'];
                    $param['area_type'] = 3;
                }

                if ($param['province_idss'] && $param['city_idss'] == 0) {
                    if ($param['is_city'] == 1) {

                        $condition_area['area_pid'] = $param['province_idss'];
                        $param['area_type'] = 2;
                        unset($condition_area['area_id']);
                    } else {
                        $condition_area['area_id'] = $param['province_idss'];
                        $param['area_type'] = 1;


                    }
                }

                $database_area = D('Area');


                $param['pid'] && $condition_area['area_pid'] = $param['pid'];
//              $tempArea['area_id'] && $condition_area['area_id'] = $tempArea['area_id'];
                $param['area_type'] && $condition_area['area_type'] = $param['area_type'];
                $condition_area['is_open'] = 1;

                if ($condition_area['area_pid'] && $condition_area['area_id'] && $condition_area['area_pid'] == $condition_area['area_id']) {
                    unset($condition_area['area_id']);
                    unset($condition_area['area_type']);
                }
                $now_area = $database_area->field(true)->where(array('area_id' => $_GET['pid']))->find();


                if ($param['area_type'] == 4) {
                    $order = '`area_sort` DESC,`is_open` DESC,`first_pinyin` ASC';
                } else {
                    $order = '`area_sort` DESC,`is_open` DESC,`area_id` ASC';
                }
                $area_list = $database_area->field(true)->where($condition_area)->order($order)->select();

                $all_money = 0;
                foreach ($area_list as &$vv) {

                    if ($vv['area_id'] && $vv['area_type'] < 3) {

                        $area_arr = $this->get_area_arr($vv['area_id'], $vv['area_type'] + 1);
                        if ($vv['area_type'] == 2) {
                            $area_arr[] = $vv['area_id'];
                        }
                        $condition['area_id'] = array('in', $area_arr);
                    } else {
                        $condition['area_id'] = $vv['area_id'];

                    }
                    $condition['invit_code'] = array('neq', '');
                    $admin_list = M('Admin')->where($condition)->select();

                    $vv['spread_count'] = 0;
                    $vv['spread_money'] = 0;
                    $vv['spread_count'] = count($admin_list);
                    $vv['admin_name'] = $admin_list[0]['realname'];
                    foreach ($admin_list as &$v) {
                        $where['invit_code'] = $v['invit_code'];
                        $where['status'] = 1;
                        // $v['spread_count'] = M('Merchant')->where($where)->count();

                        // $vv['spread_count']+=$v['spread_count'];

                        unset($where['status']);
                        $where['m.status'] = 1;
                        $v['spread_money'] = M('Merchant')->join('as m LEFT JOIN ' . C('DB_PREFIX') . 'merchant_money_list as l ON m.mer_id = l.mer_id')
                            ->where($where)->sum('total_money');
                        $vv['spread_money'] += $v['spread_money'];
                        $all_money += $v['spread_money'];
                        unset($where['m.status']);
                    }
                }

                switch ($area_list[0]['area_type']) {
                    case 1:
                        $now_type_str = L_('省份');
                        break;
                    case 2:
                        $now_type_str = L_('城市');
                        break;
                    case 3:
                        $now_type_str = L_('区域');
                        break;
                    default:
                        $now_type_str = L_('商圈');
                }


                $objExcel->createSheet();
                $objExcel->setActiveSheetIndex(1);
                $objExcel->getActiveSheet()->setTitle(L_('业务员流水'));
                $objActSheet = $objExcel->getActiveSheet();
                $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
                $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
                $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
                $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);


                $objActSheet->setCellValue('A1', 'ID');
                $objActSheet->setCellValue('B1', L_('管理员名称'));
                $objActSheet->setCellValue('C1', $now_type_str);
                $objActSheet->setCellValue('D1', L_('商家流水总额'));
                $objActSheet->setCellValue('E1', L_('业务员数量'));
                $index = 2;
                foreach ($area_list as &$va) {
                    $objActSheet->setCellValueExplicit('A' . $index, $va['area_id']);
                    $objActSheet->setCellValueExplicit('B' . $index, $va['admin_name']);
                    $objActSheet->setCellValueExplicit('C' . $index, $va['area_name']);
                    $objActSheet->setCellValueExplicit('D' . $index, floatval($va['spread_money']));
                    $objActSheet->setCellValueExplicit('E' . $index, floatval($va['spread_count']));

                    $index++;

                }

                break;
            case 'service_offer':
                $title = L_('服务快派订单');

                // 设置文档基本属性
                $objProps->setCreator($title);
                $objProps->setTitle($title);
                $objProps->setSubject($title);
                $objProps->setDescription($title);
                // 设置当前的sheet
                $objExcel->setActiveSheetIndex(0);

                $condition_where = "so.publish_id= sup.publish_id AND u.uid = so.uid";
                // 处理状态查询
                if (!empty($param['status'])) {
                    // 用户申请退款 查询 service_offer 表
                    if (intval($param['status']) == 5) {
                        $condition_where .= " AND so.status = " . $param['status'];
                    } else {
                        $condition_where .= " AND sup.status = " . $param['status'];
                    }
                }
                // 搜索
                if (!empty($param['keyword'])) {
                    $keyword = htmlspecialchars($param['keyword']);
                    if ($param['searchtype'] == 'order_sn') {
                        $condition_where .= " AND sup.order_sn LIKE '%" . $keyword . "%' ";
                    } elseif ($param['searchtype'] == 'nickname') {
                        $condition_where .= " AND u.nickname LIKE '%" . $keyword . "%' ";
                    } elseif ($param['searchtype'] == 'phone') {
                        $condition_where .= " AND u.phone LIKE '%" . $keyword . "%' ";
                    }
                }

                // 时间范围查询
                if (!empty($param['start_time']) && !empty($param['end_time'])) {
                    if ($param['start_time'] > $param['end_time']) {
                        return false;
                        //$this->error_tips("结束时间应大于开始时间");
                    }
                    $period = array(strtotime($param['start_time'] . " 00:00:00"), strtotime($param['end_time'] . " 23:59:59"));
                    $condition_where .= " AND (so.add_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
                }


                $count = D()->table(array(C('DB_PREFIX') . 'service_offer' => 'so', C('DB_PREFIX') . 'service_user_publish' => 'sup', C('DB_PREFIX') . 'user' => 'u'))->where($condition_where)->field('so.*,sup.*,so.status as offer_status,u.nickname,u.phone')->count();

                $length = ceil($count / 1000);
                $m_service_user_publish_buy = M('Service_user_publish_buy');
                $m_service_user_publish_give = M('Service_user_publish_give');
                $m_service_category = M('Service_category');
                $m_service_provider = M('Service_provider');
                $d_user_adress = D('User_adress');
                $m_plat_order = M('Plat_order');
                $m_deliver_supply = M('Deliver_supply');
                $d_pay = D('Pay');
                $offer_status_info = array(
                    1 => L_('待支付'),
                    2 => L_('已支付待服务'),
                    3 => L_('已服务待确认'),
                    4 => L_('订单完成'),
                    5 => L_('用户申请退款'),
                    6 => L_('退款成功'),
                    7 => L_('服务已完成评价成功'),
                    8 => L_('等待取货'),
                    9 => L_('配送中'),
                    10 => L_('用户已取消'),
                    11 => L_('服务过期'),
                    12 => L_('过期退款'),
                    13 => L_('退款失败'),
                );
                for ($i = 0; $i < $length; $i++) {
                    $i && $objExcel->createSheet();
                    $objExcel->setActiveSheetIndex($i);

                    $objExcel->getActiveSheet()->setTitle(L_('第X1个一千个订单信息', ($i + 1)));
                    $objActSheet = $objExcel->getActiveSheet();
                    $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(40);
                    $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('J')->setWidth(40);
                    $objExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('L')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('M')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('N')->setWidth(40);
                    $objExcel->getActiveSheet()->getColumnDimension('O')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('P')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('Q')->setWidth(40);
                    $objExcel->getActiveSheet()->getColumnDimension('R')->setWidth(40);
                    $objExcel->getActiveSheet()->getColumnDimension('S')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('T')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('U')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('V')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('W')->setWidth(30);
                    $objExcel->getActiveSheet()->getColumnDimension('X')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('Y')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('Z')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('AA')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('AB')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('AC')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('AD')->setWidth(20);

                    $objActSheet->setCellValue('A1', L_('订单编号'));
                    $objActSheet->setCellValue('B1', L_('下单用户昵称'));
                    $objActSheet->setCellValue('C1', L_('下单用户手机号'));
                    $objActSheet->setCellValue('D1', L_('服务商'));
                    $objActSheet->setCellValue('E1', L_('分类'));
                    $objActSheet->setCellValue('F1', L_('总价'));
                    $objActSheet->setCellValue('G1', L_('平台余额支付金额'));
                    $objActSheet->setCellValue('H1', L_('线上支付金额'));
                    $objActSheet->setCellValue('I1', L_('下单时间'));

                    $objActSheet->setCellValue('J1', L_('支付时间'));
                    $objActSheet->setCellValue('K1', L_('状态'));

                    $objActSheet->setCellValue('L1', L_('流水单号'));
                    $objActSheet->setCellValue('M1', L_('预估费用/商品价值'));
                    $objActSheet->setCellValue('N1', L_('基础配送费'));
                    $objActSheet->setCellValue('O1', L_('超出基础配送距离配送费'));
                    $objActSheet->setCellValue('P1', L_('重量费用'));
                    $objActSheet->setCellValue('Q1', L_('小费'));
                    $objActSheet->setCellValue('R1', L_('支付方式'));
                    $objActSheet->setCellValue('S1', L_('取货/购买地址'));
                    $objActSheet->setCellValue('T1', L_('收货地址'));
                    $objActSheet->setCellValue('U1', L_('取货联系人'));
                    $objActSheet->setCellValue('V1', L_('取货联系人手机'));
                    $objActSheet->setCellValue('W1', L_('收货联系人'));
                    $objActSheet->setCellValue('X1', L_('收货联系人手机'));
                    $objActSheet->setCellValue('Y1', L_('备注/商品备注'));
                    $objActSheet->setCellValue('Z1', L_('物品分类'));
                    $objActSheet->setCellValue('AA1', L_('物品重量'));
                    $objActSheet->setCellValue('AB1', L_('接单时间'));
                    $objActSheet->setCellValue('AC1', L_('送达时间'));
                    $objActSheet->setCellValue('AD1', L_('用户ID'));


                    $result_list = D()->table(array(C('DB_PREFIX') . 'service_offer' => 'so', C('DB_PREFIX') . 'service_user_publish' => 'sup', C('DB_PREFIX') . 'user' => 'u'))->limit($i * 1000 . ',' . 1000)->where($condition_where)->field('so.*,sup.*,so.status as offer_status,u.nickname,u.phone')->order('so.offer_id desc')->select();

                    if (!empty($result_list)) {
                        $index = 2;
                        foreach ($result_list as $value) {
                            // 查询下帮我买帮我送具体信息
                            $deliver_supply = $m_deliver_supply->where(array('order_id' => $value['publish_id'], 'server_type' => array('neq', 0)))->field('start_time, end_time')->find();

                            if ($value['catgory_type'] == 2) {
                                $publish_info = $m_service_user_publish_buy->where(array('publish_id' => $value['publish_id']))->field('weight_price, weight, arrival_time_info, goods_remarks, end_adress_phone, end_adress_nickname, adress_id, end_adress_name, address, tip_price, distance_price, basic_distance_price, total_price, estimate_goods_price')->find();

                                $total_price = $publish_info['total_price'];// 帮我买 订单总价
                                // 预估费用
                                $goods_price = $publish_info['estimate_goods_price'];
                                // 基础配送费
                                $basic_distance_price = $publish_info['basic_distance_price'];
                                // 超出基础配送距离配送费
                                $distance_price = $publish_info['distance_price'];
                                // 重量费用
                                $weight_price = $publish_info['weight_price'] ? $publish_info['weight_price'] : '-';
                                // 小费
                                $tip_price = $publish_info['tip_price'];
                                // 购买地址
                                $address = $publish_info['address'] ? $publish_info['address'] : '-';
                                // 收货地址
                                $end_address = $publish_info['end_adress_name'];
                                // 判断表中获取到地址id 且地址信息为空 获取对应id的地址信息写入，否则取表中记录的地址
                                if ($publish_info['adress_id'] && !$end_address) {
                                    $adress_info = $d_user_adress->get_adress($publishInfo['uid'], $publishInfo['adress_id']);

                                    $end_address = $adress_info['adress'] . $adress_info['detail'];
                                }
                                // 取货联系人
                                $start_name = '-';
                                // 取货联系人手机号
                                $start_phone = '-';
                                // 收货联系人
                                $end_name = $publish_info['end_adress_nickname'];
                                // 收货联系人手机号
                                $end_phone = $publish_info['end_adress_phone'];
                                // 商品备注
                                $remark = $publish_info['goods_remarks'];
                                // 物品分类
                                $goods_catgory = '-';
                                // 物品重量
                                $weight = $publish_info['weight'];
                                // 取件时间
                                $fetch_time = $deliver_supply['start_time'] ? date('Y-m-d H:i:s', $deliver_supply['start_time']) : '-';
                                // 送达时间
                                $arrival_time_info = $deliver_supply['end_time'] ? date('Y-m-d H:i:s', $deliver_supply['end_time']) : '-';
                            } else if ($value['catgory_type'] == 3) {
                                $publish_info = $m_service_user_publish_give->where(array('publish_id' => $value['publish_id']))->field('fetch_time, weight, goods_catgory, remarks, distance_price, end_adress_phone, end_adress_nickname, start_adress_phone, start_adress_nickname, end_adress_id, end_adress_name, start_adress_id, start_adress_name, tip_price, weight_price, basic_distance_price, price, total_price')->find();
                                // 帮我送 订单总价
                                $total_price = $publish_info['total_price'];
                                // 商品价值
                                $goods_price = $publish_info['price'];
                                // 基础配送费
                                $basic_distance_price = $publish_info['basic_distance_price'];
                                // 超出基础配送距离配送费
                                $distance_price = $publish_info['distance_price'];
                                // 重量费用
                                $weight_price = $publish_info['weight_price'];
                                // 小费
                                $tip_price = $publish_info['tip_price'];
                                // 取货地址
                                $address = $publish_info['start_adress_name'];
                                // 收货地址
                                $end_address = $publish_info['end_adress_name'];
                                // 判断表中获取到地址id 且地址信息为空 获取对应id的地址信息写入，否则取表中记录的地址
                                if ($publish_info['start_adress_id'] && !$address) {
                                    $start_adress_info = $d_user_adress->get_adress($publishInfo['uid'], $publishInfo['start_adress_id']);
                                    $address = $start_adress_info['adress'] . $start_adress_info['detail'];
                                }
                                if ($publish_info['end_adress_id'] && !$end_address) {
                                    $end_adress_info = $d_user_adress->get_adress($publishInfo['uid'], $publishInfo['end_adress_id']);
                                    $end_address = $end_adress_info['adress'] . $end_adress_info['detail'];
                                }
                                // 取货联系人
                                $start_name = $publish_info['start_adress_nickname'];
                                // 取货联系人手机号
                                $start_phone = $publish_info['start_adress_phone'];
                                // 收货联系人
                                $end_name = $publish_info['end_adress_nickname'];
                                // 收货联系人手机号
                                $end_phone = $publish_info['end_adress_phone'];
                                // 备注
                                $remark = $publish_info['remarks'];
                                // 物品分类
                                $goods_catgory = $publish_info['goods_catgory'];
                                // 物品重量
                                $weight = $publish_info['weight'];
                                // 取件时间
                                $fetch_time = $deliver_supply['start_time'] ? date('Y-m-d H:i:s', $deliver_supply['start_time']) : '-';
                                // 送达时间
                                $arrival_time_info = $deliver_supply['end_time'] ? date('Y-m-d H:i:s', $deliver_supply['end_time']) : '-';
                            }
                            $categoryInfo = $m_service_category->where(array('cid' => $value['cid']))->field('cid,cat_name,cut_proportion,return_integral_proportion')->find();

                            // 分类
                            $cat_name = $categoryInfo['cat_name'];
                            $providerInfo = $m_service_provider->where(array('uid' => $value['p_uid']))->field('name')->find();
                            // 服务商
                            $provider_name = $providerInfo['name'] ? $providerInfo['name'] : '-';
                            // 查询支付信息
                            $plat_order_info = $m_plat_order->where(array('business_type' => "service", 'business_id' => $value['publish_id']))->field('orderid, pay_time, paid, is_mobile_pay, pay_type,system_balance,pay_money')->find();

                            if ($plat_order_info) {
                                $paytypestr = $d_pay->get_pay_name($plat_order_info['pay_type'], $plat_order_info['is_mobile_pay'], $plat_order_info['paid']);
                                $paid = $plat_order_info['paid'] == 1 ? L_("已支付") : L_("未支付");
                                $pay_time = $plat_order_info['pay_time'];
                                if ($plat_order_info['orderid']) {
                                    $third_id = 'plat_' . $plat_order_info['orderid'];
                                } else {
                                    $third_id = '';
                                }
                            } else {
                                $paytypestr = L_('未支付');
                                $paid = L_('未支付');
                                $pay_time = '';
                                $third_id = '';
                            }
                            // 处理状态
                            if ($value['offer_status'] == 5) {
                                $offer_status_msg = L_('用户申请退款');
                            } else {
                                $status = $value['status'];
                                $offer_status_msg = $offer_status_info[$status];
                            }
                            $system_balance = $plat_order_info['system_balance'] > 0 ? round_number($plat_order_info['system_balance'],2) : 0;
                            $pay_money = $plat_order_info['pay_money'] > 0 ? round_number($plat_order_info['pay_money'],2) : 0;


                            $objActSheet->setCellValueExplicit('A' . $index, $value['order_sn']);
                            $objActSheet->setCellValueExplicit('B' . $index, $value['nickname']);
                            $objActSheet->setCellValueExplicit('C' . $index, $value['phone']);
                            $objActSheet->setCellValueExplicit('D' . $index, $provider_name);
                            $objActSheet->setCellValueExplicit('E' . $index, $cat_name);
                            $objActSheet->setCellValueExplicit('F' . $index, $total_price);

                            $objActSheet->setCellValueExplicit('G' . $index, $system_balance);
                            $objActSheet->setCellValueExplicit('H' . $index, $pay_money);



                            $objActSheet->setCellValueExplicit('I' . $index, $value['add_time'] ? date('Y-m-d H:i:s', $value['add_time']) : '');

                            $objActSheet->setCellValueExplicit('J' . $index, $pay_time ? date('Y-m-d H:i:s', $pay_time) : '');
                            $objActSheet->setCellValueExplicit('K' . $index, $offer_status_msg);

                            $objActSheet->setCellValueExplicit('L' . $index, $third_id);
                            $objActSheet->setCellValueExplicit('M' . $index, $goods_price);
                            $objActSheet->setCellValueExplicit('N' . $index, $basic_distance_price);
                            $objActSheet->setCellValueExplicit('O' . $index, $distance_price);
                            $objActSheet->setCellValueExplicit('P' . $index, $weight_price);
                            $objActSheet->setCellValueExplicit('Q' . $index, $tip_price);
                            $objActSheet->setCellValueExplicit('R' . $index, $paytypestr);
                            $objActSheet->setCellValueExplicit('S' . $index, $address);
                            $objActSheet->setCellValueExplicit('T' . $index, $end_address);
                            $objActSheet->setCellValueExplicit('U' . $index, $start_name);
                            $objActSheet->setCellValueExplicit('V' . $index, $start_phone);
                            $objActSheet->setCellValueExplicit('W' . $index, $end_name);

                            $objActSheet->setCellValueExplicit('X' . $index, $end_phone);
                            $objActSheet->setCellValueExplicit('Y' . $index, $remark);

                            $objActSheet->setCellValueExplicit('Z' . $index, $goods_catgory);
                            $objActSheet->setCellValueExplicit('AA' . $index, $weight);
                            $objActSheet->setCellValueExplicit('AB' . $index, $fetch_time);
                            $objActSheet->setCellValueExplicit('AC' . $index, $arrival_time_info);
                            $objActSheet->setCellValueExplicit('AD' . $index, $value['uid']);
                            $index++;
                        }
                    }
                    sleep(2);
                }
                break;

            case 'service_offer':
                D('Service_auto_category')->autoStoreOrderExport($objProps,$objExcel,$param);
                break;
            case 'score_pay_back':
                $title = L_('积分返现记录列表');

                // 设置文档基本属性
                $objProps->setCreator($title);
                $objProps->setTitle($title);
                $objProps->setSubject($title);
                $objProps->setDescription($title);
                // 设置当前的sheet
                $objExcel->setActiveSheetIndex(0);

                $model = D('Merchant_money_list');
                $now_time = time();
                $today_time_zero = strtotime(date('Y-m-d', $now_time));;
                $today_time_condition['_string'] = "use_time>{$today_time_zero} AND use_time<{$now_time}";
                $today_system_take = $model->get_system_take_by_condition($today_time_condition);
                $yesterday_time_zero = $today_time_zero - 86400;
                $yesterday_time_condition['_string'] = "use_time<{$today_time_zero} AND use_time>{$yesterday_time_zero}";
                $yesterday_system_take = $model->get_system_take_by_condition($yesterday_time_condition);
                $tomorrow = $today_time_zero + 86400;
                $yesterday_money_condition['_string'] = "time>{$today_time_zero} AND time<{$tomorrow}";
                $yesterday_money_condition['ask'] = 3;
                $yesterday_pay_back = M('User_money_list')->where($yesterday_money_condition)->sum('money');

                $condition_where['_string'] = '1=1 ';
                if (!empty($param['keyword'])) {
                    if ($param['searchtype'] == 'name') {
                        $condition_where['_string'] .= " AND `u`.`nickname` like '%" . htmlspecialchars($param['keyword']) . "%'";
                    } elseif ($param['searchtype'] == 'phone') {
                        $condition_where['_string'] .= " AND  `u`.`phone` like '%" . htmlspecialchars($param['keyword']) . "%'";
                    }

                }

                if (!empty($param['begin_time']) && !empty($param['end_time'])) {
                    if ($param['begin_time'] > $param['end_time']) {
                        $this->error_tips(L_("结束时间应大于开始时间"));
                    }
                    $period = array(strtotime($param['begin_time'] . " 00:00:00"), strtotime($param['end_time'] . " 23:59:59"));
                    $condition_where['_string'] .= " AND (o.time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
                }


                $condition_where['is_pay_back'] = 1;
                $count = D('User_score_list')->join('as o left join ' . C('DB_PREFIX') . 'user AS u ON u.uid = o.uid')->where($condition_where)->count();


                $today_to_be_return = floatval($today_system_take) * floatval(C('config.score_pay_back_percent')) / 100;
                $title_msg = L_('昨日抽成：x1   今日抽成：x2   返现比例：x3%   昨天返现：x4   今天待返现：x5', array('x1' => floatval($yesterday_system_take) . C('config.Currency_txt'), 'x2' => floatval($today_system_take) . C('config.Currency_txt'), 'x3' => floatval(C('config.score_pay_back_percent')), 'x4' => floatval($yesterday_pay_back) . C('config.Currency_txt'), 'x5' => $today_system_take));

                $length = ceil($count / 1000);

                for ($i = 0; $i < $length; $i++) {
                    $i && $objExcel->createSheet();
                    $objExcel->setActiveSheetIndex($i);

                    $objExcel->getActiveSheet()->setTitle(L_('第X1个一千个订单信息', ($i + 1)));
                    $objActSheet = $objExcel->getActiveSheet();
                    $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(40);
                    $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(50);
                    $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);

                    $objExcel->getActiveSheet()->mergeCellsByColumnAndRow('0', '1', 6, '1');
                    $objExcel->getActiveSheet()->setCellValueByColumnAndRow('0', '1', $title_msg);

                    $objActSheet->setCellValue('A2', L_('编号'));
                    $objActSheet->setCellValue('B2', L_('昵称'));
                    $objActSheet->setCellValue('C2', L_('电话'));
                    $objActSheet->setCellValue('D2', L_('积分数量'));
                    $objActSheet->setCellValue('E2', L_('描述'));
                    $objActSheet->setCellValue('F2', L_('时间'));


                    $result_list = D('User_score_list')->field('o.*,u.nickname,u.phone')->join('as o left join ' . C('DB_PREFIX') . 'user AS u ON u.uid = o.uid')->where($condition_where)->order('o.id DESC')->limit($i * 1000 . ',' . 1000)->select();

                    if (!empty($result_list)) {
                        $index = 3;
                        foreach ($result_list as $value) {

                            $objActSheet->setCellValueExplicit('A' . $index, $value['id']);
                            $objActSheet->setCellValueExplicit('B' . $index, $value['nickname']);
                            $objActSheet->setCellValueExplicit('C' . $index, $value['phone']);
                            if ($value['type'] == 1) {
                                $score = '+' . $value['score'];
                            } else {
                                $score = '-' . $value['score'];
                            }
                            $objActSheet->setCellValueExplicit('D' . $index, $score);
                            $objActSheet->setCellValueExplicit('E' . $index, $value['desc']);
                            $objActSheet->setCellValueExplicit('F' . $index, $value['time'] ? date('Y-m-d H:i:s', $value['time']) : '');

                            $index++;
                        }
                    }
                    sleep(2);
                }
                break;
            case 'deliver_list_bak':
                require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
                $title = L_('配送列表');
                $objExcel = new PHPExcel();
                $objProps = $objExcel->getProperties();
                // 设置文档基本属性
                $objProps->setCreator($title);
                $objProps->setTitle($title);
                $objProps->setSubject($title);
                $objProps->setDescription($title);
                // 设置当前的sheet
                $status = intval($param['status']);
                $deliver_status = intval($param['deliver_status']);
                $day = intval($param['day']);
                $period = htmlspecialchars($param['period']);
                $order_from = isset($param['order_from']) ? intval($param['order_from']) : -1;


                $province_id = intval($param['province_id']);
                $city_id = intval($param['city_id']);
                $area_id = intval($param['area_id']);
                if ($param['system_session']['area_id']) {
                    $now_area = D('Area')->field(true)->where(array('area_id' => $param['system_session']['area_id']))->find();
                    if ($now_area['area_type'] == 3) {
                        $area_id = $param['system_session']['area_id'];
                        $city_id = $now_area['area_pid'];
                        $temp_area = D('Area')->field(true)->where(array('area_id' => $city_id))->find();
                        $province_id = $temp_area['area_pid'];
                    } elseif ($now_area['area_type'] == 2) {
                        $city_id = $param['system_session']['area_id'];
                        $province_id = $now_area['area_pid'];
                    } elseif ($now_area['area_type'] == 1) {
                        $province_id = $param['system_session']['area_id'];
                    }
                }

                $timetype = intval($param['timetype']);
                $time = intval($param['time']);
                $stime = $etime = 0;
                if ($day) {
                    if ($day == 1) {
                        $stime = strtotime(date('Y-m-d'));
                        $etime = time();
                    } else {
                        $stime = strtotime("-{$day} day");
                        $etime = time();
                    }
                }
                if ($period) {
                    $time_array = explode('-', $period);
                    $stime = strtotime($time_array[0]);
                    $etime = strtotime($time_array[1]) + 86400;
                }

                $sql = "SELECT count(1) AS cnt ,case  when s.`server_type` <> 0 then sp.`order_sn` else so.real_orderid end as order_sn FROM " . C('DB_PREFIX') . "merchant_store AS m RIGHT JOIN " . C('DB_PREFIX') . "deliver_supply AS s ON m.store_id=s.store_id LEFT JOIN " . C('DB_PREFIX') . "deliver_user AS u ON s.uid=u.uid LEFT JOIN " . C('DB_PREFIX') . "service_user_publish AS sp ON (sp.publish_id=s.order_id AND s.`server_type` <> 0) LEFT JOIN " . C('DB_PREFIX') . "user AS us ON (sp.uid=us.uid AND s.`server_type` <> 0) LEFT JOIN " . C('DB_PREFIX') . "shop_order AS so ON (so.order_id=s.order_id AND s.`server_type` = 0)";

                $sql .= ' WHERE s.type=0';
                if ($province_id > 0) {
                    $sql .= " AND s.province_id=" . $province_id;
                }
                if ($city_id > 0) {
                    $sql .= " AND s.city_id=" . $city_id;
                }
                if ($area_id > 0) {
                    $sql .= " AND s.area_id=" . $area_id;
                }
                // 判断需要查询的类型，做判断
                $keyword = htmlspecialchars($param['keyword']);
                if (!empty($keyword)) {
                    $searchtype = htmlspecialchars($param['searchtype']);
                    if ($searchtype == 'real_orderid') {
                        $sql .= ' AND (sp.order_sn LIKE "%' . $keyword . '%" OR so.real_orderid LIKE "%' . $keyword . '%")';
                    } elseif ($searchtype == 's_name') {
                        $sql .= ' AND m.name LIKE "%' . $keyword . '%"';
                    } elseif ($searchtype == 'name') {
                        $sql .= ' AND s.name LIKE "%' . $keyword . '%"';
                    } elseif ($searchtype == 'phone') {
                        $sql .= ' AND (s.phone LIKE "%' . $keyword . '%" OR us.phone LIKE "%' . $keyword . '%")';
                    } elseif ($searchtype == 'd_name') {
                        $sql .= ' AND u.name LIKE "%' . $keyword . '%"';
                    }
                }


                if ($time > 0) {
                    $sqltime = $time * 60;
                    if ($timetype == 0) {
                        $sql .= " AND s.create_time>s.order_time AND s.create_time-s.order_time>" . $sqltime;
                    } elseif ($timetype == 1) {
                        $sql .= " AND s.start_time>s.create_time AND s.start_time-s.create_time>" . $sqltime;
                    } elseif ($timetype == 2) {
                        $sql .= " AND s.end_time>s.start_time AND s.end_time-s.start_time>" . $sqltime;
                    } elseif ($timetype == 3) {
                        $sql .= " AND s.end_time>s.pay_time AND s.end_time-s.pay_time>" . $sqltime;
                    }
                }
                if ($stime && $etime) {
                    $sql .= " AND s.create_time>'{$stime}' AND s.create_time<'{$etime}'";
                }

                if ($deliver_status > -1) {
                    if ($deliver_status == 1) {
                        $sql .= " AND ((s.deliver_status=2 AND s.is_fetch_order=0) OR (s.deliver_status=" . $deliver_status . "))";
                    } else {
                        $sql .= " AND s.deliver_status=" . $deliver_status;
                    }
                }

                if ($status != -1) {
                    $sql .= " AND s.status='{$status}'";
                }

                if ($order_from > -1) {
                    if ($order_from == 0) {
                        $sql .= " AND s.order_from<3";
                    } elseif ($order_from == 4) {
                        $sql .= " AND (s.order_from=4 OR s.order_from=5)";
                    } else {
                        $sql .= " AND s.order_from=" . $order_from;
                    }
                }

                if ($param['system_session']['area_id']) {
                    $area_index = D('Area')->getIndexByAreaID($param['system_session']['area_id']);
                    $sql .= ' AND m.' . $area_index . '=' . $param['system_session']['area_id'];
                }

                $count = D()->query($sql);
                fdump($count, 'deliver_list_param_count', true);
                $count = isset($count[0]['cnt']) ? intval($count[0]['cnt']) : 0;

                $length = ceil($count / 1000);
                $m_shop_order_detail = M('Shop_order_detail');
                $m_shop_order = M('Shop_order');
                $m_service_user_publish_buy = M('Service_user_publish_buy');
                $m_service_user_publish_give = M('Service_user_publish_give');
                $m_service_user_publish = M('Service_user_publish');
                $m_area = M('Area');
                $d_pay = D('Pay');
                for ($i = 0; $i < $length; $i++) {
                    $i && $objExcel->createSheet();
                    $objExcel->setActiveSheetIndex($i);

                    $objExcel->getActiveSheet()->setTitle(L_('第X1个一千配送订单', ($i + 1)));
                    $objActSheet = $objExcel->getActiveSheet();

                    $objActSheet->setCellValue('A1', L_('订单编号'));
                    $objActSheet->setCellValue('B1', L_('配送ID'));
                    $objActSheet->setCellValue('C1', L_('订单来源'));
                    $objActSheet->setCellValue('D1', L_('商品名称'));
                    $objActSheet->setCellValue('E1', L_('商品价格'));
                    $objActSheet->setCellValue('F1', L_('商品数量'));
                    $objActSheet->setCellValue('G1', L_('店铺名称'));
                    $objActSheet->setCellValue('H1', L_('店铺商圈'));
                    $objActSheet->setCellValue('I1', L_('客户名称'));
                    $objActSheet->setCellValue('J1', L_('用户ID'));
                    $objActSheet->setCellValue('K1', L_('客户手机'));
                    $objActSheet->setCellValue('L1', L_('客户地址'));
                    $objActSheet->setCellValue('M1', L_('支付状态'));
                    $objActSheet->setCellValue('N1', L_('支付方式'));
                    $objActSheet->setCellValue('O1', L_('支付来源'));
                    $objActSheet->setCellValue('p1', L_('支付时间'));
                    $objActSheet->setCellValue('Q1', L_('订单价格'));
                    $objActSheet->setCellValue('R1', L_('应收现金'));
                    $objActSheet->setCellValue('S1', L_('配送员昵称'));
                    $objActSheet->setCellValue('T1', L_('配送员手机号'));
                    $objActSheet->setCellValue('U1', L_('开始时间'));
                    $objActSheet->setCellValue('V1', L_('送达时间'));
                    $objActSheet->setCellValue('W1', L_('配送费'));
                    $objActSheet->setCellValue('X1', L_('小费'));
                    $objActSheet->setCellValue('Y1', L_('店员接单时长'));
                    $objActSheet->setCellValue('Z1', L_('配送员接单时长'));
                    $objActSheet->setCellValue('AA1', L_('配送时长'));
                    $objActSheet->setCellValue('AB1', L_('配送员取货时间'));
                    $objActSheet->setCellValue('AC1', L_('配送员开始配送时间'));
                    $objActSheet->setCellValue('AD1', L_('配送状态'));
                    $objActSheet->setCellValue('AE1', L_('接单类型'));
                    $objActSheet->setCellValue('AF1', L_('流水单号'));
                    $objActSheet->setCellValue('AG1', L_('订单备注'));
                    $objActSheet->setCellValue('AH1', L_('期望送达时间'));

                    $fied = "spb.`arrival_time_info`,so.`expect_use_time`,s.`tip_price`,s.`freight_charge`,m.`circle_id`, s.`supply_id`, s.`order_id`, case  when s.`server_type` <> 0 then po.`is_own` else so.is_own end as is_own, case  when s.`server_type` <> 0 then po.`pay_time` else so.pay_time end as order_pay_time, case  when s.`server_type` <> 0 then po.`orderid` else so.orderid end as third_id, case  when s.`server_type` <> 0 then sp.`order_sn` else so.real_orderid end as order_sn, case  when s.`server_type` <> 0 then po.`pay_type` else so.pay_type end as order_pay_type, case  when s.`server_type` <> 0 then po.`is_mobile_pay` else so.is_mobile_pay end as order_is_mobile_pay, case  when s.`server_type` <> 0 then po.`paid` else so.paid end as order_paid, s.order_id, s.create_time,s.order_time, s.order_from, s.item, s.name as username, s.phone as userphone, m.name as storename, s.money, u.name, u.phone, s.start_time, s.end_time,s.pay_time, s.aim_site, s.pay_type, s.paid, s.status, s.deliver_cash, s.distance, s.from_lat, s.aim_lat, s.from_lnt, s.aim_lnt, s.get_type, s.server_type ";

                    $sql = "SELECT " . $fied . " FROM " .
                        C('DB_PREFIX') . "merchant_store AS m RIGHT JOIN " .
                        C('DB_PREFIX') . "deliver_supply AS s ON m.store_id=s.store_id LEFT JOIN " .
                        C('DB_PREFIX') . "deliver_user AS u ON s.uid=u.uid LEFT JOIN " .
                        C('DB_PREFIX') . "service_user_publish AS sp ON (sp.publish_id=s.order_id AND s.`server_type` <> 0) LEFT JOIN " .
                        C('DB_PREFIX') . "plat_order AS po ON (po.business_id=s.order_id AND po.business_type='service' AND s.`server_type` <> 0) LEFT JOIN " .
                        C('DB_PREFIX') . "user AS us ON (sp.uid=us.uid AND s.`server_type` <> 0) LEFT JOIN " .
                        C('DB_PREFIX') . "shop_order AS so ON (so.order_id=s.order_id AND s.`server_type` = 0) LEFT JOIN " .
                        C('DB_PREFIX') . "service_user_publish_buy AS spb ON (sp.publish_id=spb.publish_id)";


                    $sql .= ' WHERE s.type=0';
//            }
                    if ($province_id > 0) {
                        $sql .= " AND s.province_id=" . $province_id;
                    }
                    if ($city_id > 0) {
                        $sql .= " AND s.city_id=" . $city_id;
                    }
                    if ($area_id > 0) {
                        $sql .= " AND s.area_id=" . $area_id;
                    }
                    // 判断需要查询的类型，做判断
                    if (!empty($keyword)) {
                        $searchtype = htmlspecialchars($param['searchtype']);
                        if ($searchtype == 'real_orderid') {
                            $sql .= ' AND (sp.order_sn LIKE "%' . $keyword . '%" OR so.real_orderid LIKE "%' . $keyword . '%")';
                        } elseif ($searchtype == 's_name') {
                            $sql .= ' AND m.name LIKE "%' . $keyword . '%"';
                        } elseif ($searchtype == 'name') {
                            $sql .= ' AND s.name LIKE "%' . $keyword . '%"';
                        } elseif ($searchtype == 'phone') {
                            $sql .= ' AND (s.phone LIKE "%' . $keyword . '%" OR us.phone LIKE "%' . $keyword . '%")';
                        } elseif ($searchtype == 'd_name') {
                            $sql .= ' AND u.name LIKE "%' . $keyword . '%"';
                        }
                    }
                    if ($time > 0) {
                        $sqltime = $time * 60;
                        if ($timetype == 0) {
                            $sql .= " AND s.create_time>s.order_time AND s.create_time-s.order_time>" . $sqltime;
                        } elseif ($timetype == 1) {
                            $sql .= " AND s.start_time>s.create_time AND s.start_time-s.create_time>" . $sqltime;
                        } elseif ($timetype == 2) {
                            $sql .= " AND s.end_time>s.start_time AND s.end_time-s.start_time>" . $sqltime;
                        } elseif ($timetype == 3) {
                            $sql .= " AND s.end_time>s.pay_time AND s.end_time-s.pay_time>" . $sqltime;
                        }
                    }
                    if ($stime && $etime) {
                        $sql .= " AND s.create_time>'{$stime}' AND s.create_time<'{$etime}'";
                    }
                    if ($status != -1) {
                        $sql .= " AND s.status='{$status}'";
                    }
                    if ($order_from > -1) {
                        if ($order_from == 0) {
                            $sql .= " AND s.order_from<3";
                        } elseif ($order_from == 4) {
                            $sql .= " AND (s.order_from=4 OR s.order_from=5)";
                        } else {
                            $sql .= " AND s.order_from=" . $order_from;
                        }
                    }
                    if ($param['system_session']['area_id']) {
                        $area_index = D('Area')->getIndexByAreaID($param['system_session']['area_id']);
                        $sql .= ' AND m.' . $area_index . '=' . $param['system_session']['area_id'];
                    }

                    $sql .= ' ORDER BY s.`supply_id` DESC LIMIT ' . $i * 1000 . ', 1000';


                    $supply_list = D()->query($sql);

                    if (!empty($supply_list)) {
                        import('ORG.Net.IpLocation');
                        $IpLocation = new IpLocation();
                        $index = 2;
                        foreach ($supply_list as $value) {
                            $value['confirm_time'] = $value['create_time'] && $value['order_time'] ? intval(($value['create_time'] - $value['order_time']) / 60) . L_('分钟') : '-';
                            $value['grab_time'] = $value['start_time'] ? intval(($value['start_time'] - $value['create_time']) / 60) . L_('分钟') : '-';
                            $value['deliver_use_time'] = $value['end_time'] ? intval(($value['end_time'] - $value['start_time']) / 60) . L_('分钟') : '-';
                            $where['order_id'] = $value['order_id'];
                            $where['status'] = array('in', '4,5');
                            $times = D('Shop_order_log')->where($where)->select();

                            $third_id = '';
                            // 查询一下关联 快店的商品信息
                            $circle_info = '';
                            $order_from_info = '-'; // 订单来源 0：wap快店，1：wap商城，2：Android，3：ios,4:小程序,5：pc快店,6:线下零售,7.饿了么，8.美团
                            if ($value['item'] != 3 && $value['order_from'] != 3 && $value['order_from'] != 4 && $value['order_from'] != 5) {
                                $shop_detail = $m_shop_order_detail->where(array('order_id' => $value['order_id']))->field('name, num, price')->select();
                                $info = $m_shop_order->where(array('order_id' => $value['order_id']))->field('uid, desc, order_from')->find();
                                $userId = $info['uid'];
                                $remark = $info['desc'];
                                if ($info['order_from'] == 0) {
                                    $order_from_info = L_('wap快店');
                                } elseif ($info['order_from'] == 1) {
                                    $order_from_info = L_('wap商城');
                                } elseif ($info['order_from'] == 2) {
                                    $order_from_info = 'Android';
                                } elseif ($info['order_from'] == 3) {
                                    $order_from_info = 'ios';
                                } elseif ($info['order_from'] == 4) {
                                    $order_from_info = L_('小程序');
                                } elseif ($info['order_from'] == 5) {
                                    $order_from_info = L_('pc快店');
                                } elseif ($info['order_from'] == 6) {
                                    $order_from_info = L_('线下零售');
                                } elseif ($info['order_from'] == 7) {
                                    $order_from_info = L_('饿了么');
                                } elseif ($info['order_from'] == 8) {
                                    $order_from_info = L_('美团');
                                }
                                if ($value['circle_id']) {
                                    // 获取对应商圈信息
                                    $condition_area = array(
                                        'area_id' => $value['circle_id']
                                    );
                                    $circle_arr = $m_area->where($condition_area)->field('area_pid, area_name, area_ip_desc')->find();
                                    if (!$circle_arr['area_ip_desc']) {
                                        if ($circle_arr['area_pid']) {
                                            $condition_pid_area = array(
                                                'area_id' => $circle_arr['area_pid']
                                            );
                                            $circle_msg = $m_area->where($condition_pid_area)->getField('area_name');
                                            $circle_info = $circle_msg . $circle_arr['area_name'];
                                        } else {
                                            $circle_info = $circle_arr['area_name'];
                                        }
                                    } else {
                                        $circle_info = $circle_arr['area_ip_desc'];
                                    }
                                } else {
                                    $circle_info = '';
                                }
                            } else {
                                // 帮我送
                                $userId = $m_service_user_publish->where(array('publish_id' => $value['order_id']))->getField('uid');
                                if ($value['order_from'] == 3) {
                                    $remark = $m_service_user_publish_give->where(array('publish_id' => $value['order_id']))->getField('remarks');
                                } else {
                                    // 帮我买
                                    $remark = $m_service_user_publish_buy->where(array('publish_id' => $value['order_id']))->getField('goods_remarks');
                                }
                                $shop_detail = array();
                            }

                            $pay_time = $value['pay_time'] ? $value['pay_time'] : $value['order_pay_time'];
                            if ($shop_detail) {
                                $shop_first = false;
                                foreach ($shop_detail as $val) {
                                    if (!$shop_first) {
                                        $objActSheet->setCellValueExplicit('A' . $index, $value['order_sn']);
                                        $objActSheet->setCellValueExplicit('B' . $index, $value['supply_id']);
                                        if ($value['item'] == 0) {
                                            $objActSheet->setCellValueExplicit('C' . $index, C('config.meal_alias_name'));
                                        } elseif ($value['item'] == 1) {
                                            $objActSheet->setCellValueExplicit('C' . $index, C('config.waimai_alias_name'));
                                        } else {
                                            if ($value['order_from'] == 0) {
                                                $objActSheet->setCellValueExplicit('C' . $index, C('config.shop_alias_name'));
                                            } elseif ($value['order_from'] == 1) {
                                                $objActSheet->setCellValueExplicit('C' . $index, L_('饿了么'));
                                            } elseif ($value['order_from'] == 2) {
                                                $objActSheet->setCellValueExplicit('C' . $index, L_('美团'));
                                            } elseif ($value['order_from'] == 3) {
                                                $objActSheet->setCellValueExplicit('C' . $index, L_('帮我送'));
                                            } elseif ($value['order_from'] == 4 || $value['order_from'] == 5) {
                                                $objActSheet->setCellValueExplicit('C' . $index, L_('帮我买'));
                                            }
                                        }
                                        $objActSheet->setCellValueExplicit('D' . $index, $val['name']);
                                        $objActSheet->setCellValueExplicit('E' . $index, $val['price']);
                                        $objActSheet->setCellValueExplicit('F' . $index, $val['num']);


                                        $objActSheet->setCellValueExplicit('G' . $index, $value['storename']);
                                        $objActSheet->setCellValueExplicit('H' . $index, $circle_info);
                                        $objActSheet->setCellValueExplicit('I' . $index, $value['username']);
                                        $objActSheet->setCellValueExplicit('J' . $index, $userId);
                                        $objActSheet->setCellValueExplicit('K' . $index, $value['userphone'] . ' ');
                                        $objActSheet->setCellValueExplicit('L' . $index, $value['aim_site']);

                                        if ($value['order_paid'] == 1) {
                                            $objActSheet->setCellValueExplicit('M' . $index, L_('已支付'));
                                        } else {
                                            $objActSheet->setCellValueExplicit('M' . $index, L_('未支付'));
                                        }
                                        $value['paytypestr'] = $d_pay->get_pay_name($value['order_pay_type'], $value['order_is_mobile_pay'], $value['order_paid']);
                                        $objActSheet->setCellValueExplicit('N' . $index, $value['paytypestr']);

                                        $objActSheet->setCellValueExplicit('O' . $index, $order_from_info);

                                        $objActSheet->setCellValueExplicit('P' . $index, $pay_time ? date('Y-m-d H:i:s', $pay_time) : '-');

                                        $objActSheet->setCellValueExplicit('Q' . $index, floatval($value['money']));
                                        $objActSheet->setCellValueExplicit('R' . $index, floatval($value['deliver_cash']));


                                        $objActSheet->setCellValueExplicit('S' . $index, $value['name']);
                                        $objActSheet->setCellValueExplicit('T' . $index, $value['phone'] . ' ');
                                        $objActSheet->setCellValueExplicit('U' . $index, $value['start_time'] ? date('Y-m-d H:i:s', $value['start_time']) : L_('还未开始'));
                                        $objActSheet->setCellValueExplicit('v' . $index, $value['end_time'] ? date('Y-m-d H:i:s', $value['end_time']) : L_('还未完成'));

                                        $objActSheet->setCellValueExplicit('W' . $index, sprintf("%.2f", floatval($value['freight_charge'])));
                                        $objActSheet->setCellValueExplicit('X' . $index, sprintf("%.2f", floatval($value['tip_price'])));

                                        $objActSheet->setCellValueExplicit('Y' . $index, $value['confirm_time']);
                                        $objActSheet->setCellValueExplicit('Z' . $index, $value['grab_time']);
                                        $objActSheet->setCellValueExplicit('AA' . $index, $value['deliver_use_time']);
                                        if ($value['order_from'] == 3 || $value['order_from'] == 4 || $value['order_from'] == 5) {
                                            $objActSheet->setCellValueExplicit('AB' . $index, '-');
                                            $objActSheet->setCellValueExplicit('AC' . $index, '-');
                                            if ($value['third_id']) {
                                                $third_id = 'plat_' . $value['third_id'];
                                                if ($value['is_own'] > 0) {
                                                    $third_id .= '_' . $value['is_own'];
                                                }
                                            }
                                        } else {
                                            $objActSheet->setCellValueExplicit('AB' . $index, $times[0]['dateline'] ? date('Y-m-d H:i:s', $times[0]['dateline']) : L_('还未开始'));
                                            $objActSheet->setCellValueExplicit('AC' . $index, $times[1]['dateline'] ? date('Y-m-d H:i:s', $times[1]['dateline']) : L_('还未开始'));
                                            if ($value['third_id']) {
                                                $third_id = 'shop_' . $value['third_id'];
                                                if ($value['is_own'] > 0) {
                                                    $third_id .= '_' . $value['is_own'];
                                                }
                                            }
                                        }

                                        switch ($value['status']) {
                                            case 1:
                                                $value['order_status'] = L_("等待接单");
                                                break;
                                            case 2:
                                                $value['order_status'] = L_("已接单");
                                                break;
                                            case 3:
                                                $value['order_status'] = L_("已取货");
                                                break;
                                            case 4:
                                                $value['order_status'] = L_("开始配送");
                                                break;
                                            case 5:
                                                $value['order_status'] = L_("已完成");
                                                break;
                                            case 6:
                                                $value['order_status'] = L_("已评价");
                                                break;
                                            case 7:
                                                $value['order_status'] = L_("退款退款失败");
                                                break;
                                            default:
                                                $value['order_status'] = L_("订单失效");
                                                break;
                                        }
                                        $objActSheet->setCellValueExplicit('AD' . $index, $value['order_status']);
                                        if ($value['status'] < 2) {
                                            $value['get_type'] = '--';
                                        } elseif ($value['get_type'] == 0) {
                                            $value['get_type'] = L_('抢单');
                                        } else {
                                            $value['get_type'] = L_('系统派单');
                                        }
                                        $objActSheet->setCellValueExplicit('AE' . $index, $value['get_type']);
                                        $objActSheet->setCellValueExplicit('AF' . $index, $third_id);
                                        $objActSheet->setCellValueExplicit('AG' . $index, $remark);
                                        if ($value['order_from'] == 0) {
                                            $objActSheet->setCellValueExplicit('AH' . $index, $value['expect_use_time'] ? date('Y-m-d H:i:s', $value['expect_use_time']) : L_('无'));
                                        } else {
                                            $objActSheet->setCellValueExplicit('AH' . $index, $value['arrival_time_info'] ? $value['arrival_time_info'] : L_('无'));
                                        }
                                        $shop_first = true;
                                        $index++;
                                    } else {
                                        $objActSheet->setCellValueExplicit('D' . $index, $val['name']);
                                        $objActSheet->setCellValueExplicit('E' . $index, $val['price']);
                                        $objActSheet->setCellValueExplicit('F' . $index, $val['num']);
                                        $index++;
                                    }
                                }
                            } else {
                                $objActSheet->setCellValueExplicit('A' . $index, $value['order_sn']);
                                $objActSheet->setCellValueExplicit('B' . $index, $value['supply_id']);
                                if ($value['item'] == 0) {
                                    $objActSheet->setCellValueExplicit('C' . $index, C('config.meal_alias_name'));
                                } elseif ($value['item'] == 1) {
                                    $objActSheet->setCellValueExplicit('C' . $index, C('config.waimai_alias_name'));
                                } else {
                                    if ($value['order_from'] == 0) {
                                        $objActSheet->setCellValueExplicit('C' . $index, C('config.shop_alias_name'));
                                    } elseif ($value['order_from'] == 1) {
                                        $objActSheet->setCellValueExplicit('C' . $index, L_('饿了么'));
                                    } elseif ($value['order_from'] == 2) {
                                        $objActSheet->setCellValueExplicit('C' . $index, L_('美团'));
                                    } elseif ($value['order_from'] == 3) {
                                        $objActSheet->setCellValueExplicit('C' . $index, L_('帮我送'));
                                    } elseif ($value['order_from'] == 4 || $value['order_from'] == 5) {
                                        $objActSheet->setCellValueExplicit('C' . $index, L_('帮我买'));
                                    }
                                }
                                $objActSheet->setCellValueExplicit('D' . $index, '-');
                                $objActSheet->setCellValueExplicit('E' . $index, '-');
                                $objActSheet->setCellValueExplicit('F' . $index, '-');


                                $objActSheet->setCellValueExplicit('G' . $index, $value['storename']);
                                $objActSheet->setCellValueExplicit('H' . $index, $circle_info);
                                $objActSheet->setCellValueExplicit('I' . $index, $value['username']);
                                $objActSheet->setCellValueExplicit('J' . $index, $userId);
                                $objActSheet->setCellValueExplicit('K' . $index, $value['userphone'] . ' ');
                                $objActSheet->setCellValueExplicit('L' . $index, $value['aim_site']);

                                if ($value['order_paid'] == 1) {
                                    $objActSheet->setCellValueExplicit('M' . $index, L_('已支付'));
                                } else {
                                    $objActSheet->setCellValueExplicit('M' . $index, L_('未支付'));
                                }
                                $value['paytypestr'] = $d_pay->get_pay_name($value['order_pay_type'], $value['order_is_mobile_pay'], $value['order_paid']);
                                $objActSheet->setCellValueExplicit('N' . $index, $value['paytypestr']);

                                $objActSheet->setCellValueExplicit('O' . $index, $order_from_info);

                                $objActSheet->setCellValueExplicit('P' . $index, $pay_time ? date('Y-m-d H:i:s', $pay_time) : '-');

                                $objActSheet->setCellValueExplicit('Q' . $index, floatval($value['money']));
                                $objActSheet->setCellValueExplicit('R' . $index, floatval($value['deliver_cash']));


                                $objActSheet->setCellValueExplicit('S' . $index, $value['name']);
                                $objActSheet->setCellValueExplicit('T' . $index, $value['phone'] . ' ');
                                $objActSheet->setCellValueExplicit('U' . $index, $value['start_time'] ? date('Y-m-d H:i:s', $value['start_time']) : L_('还未开始'));
                                $objActSheet->setCellValueExplicit('V' . $index, $value['end_time'] ? date('Y-m-d H:i:s', $value['end_time']) : L_('还未完成'));

                                $objActSheet->setCellValueExplicit('W' . $index, sprintf("%.2f", floatval($value['freight_charge'])));
                                $objActSheet->setCellValueExplicit('X' . $index, sprintf("%.2f", floatval($value['tip_price'])));

                                $objActSheet->setCellValueExplicit('Y' . $index, $value['confirm_time']);
                                $objActSheet->setCellValueExplicit('Z' . $index, $value['grab_time']);
                                $objActSheet->setCellValueExplicit('AA' . $index, $value['deliver_use_time']);

                                if ($value['order_from'] == 3 || $value['order_from'] == 4 || $value['order_from'] == 5) {
                                    $objActSheet->setCellValueExplicit('AB' . $index, '-');
                                    $objActSheet->setCellValueExplicit('AC' . $index, '-');
                                    if ($value['third_id']) {
                                        $third_id = 'plat_' . $value['third_id'];
                                        if ($value['is_own'] > 0) {
                                            $third_id .= '_' . $value['is_own'];
                                        }
                                    }
                                } else {
                                    $objActSheet->setCellValueExplicit('AB' . $index, $times[0]['dateline'] ? date('Y-m-d H:i:s', $times[0]['dateline']) : L_('还未开始'));
                                    $objActSheet->setCellValueExplicit('AC' . $index, $times[1]['dateline'] ? date('Y-m-d H:i:s', $times[1]['dateline']) : L_('还未开始'));
                                    if ($value['third_id']) {
                                        $third_id = 'shop_' . $value['third_id'];
                                        if ($value['is_own'] > 0) {
                                            $third_id .= '_' . $value['is_own'];
                                        }
                                    }
                                }

                                switch ($value['status']) {
                                    case 1:
                                        $value['order_status'] = L_("等待接单");
                                        break;
                                    case 2:
                                        $value['order_status'] = L_("已接单");
                                        break;
                                    case 3:
                                        $value['order_status'] = L_("已取货");
                                        break;
                                    case 4:
                                        $value['order_status'] = L_("开始配送");
                                        break;
                                    case 5:
                                        $value['order_status'] = L_("已完成");
                                        break;
                                    case 6:
                                        $value['order_status'] = L_("已评价");
                                        break;
                                    case 7:
                                        $value['order_status'] = L_("退款退款失败");
                                        break;
                                    default:
                                        $value['order_status'] = L_("订单失效");
                                        break;
                                }
                                $objActSheet->setCellValueExplicit('AD' . $index, $value['order_status']);
                                if ($value['status'] < 2) {
                                    $value['get_type'] = '--';
                                } elseif ($value['get_type'] == 0) {
                                    $value['get_type'] = L_('抢单');
                                } else {
                                    $value['get_type'] = L_('系统派单');
                                }
                                $objActSheet->setCellValueExplicit('AE' . $index, $value['get_type']);
                                $objActSheet->setCellValueExplicit('AF' . $index, $third_id);
                                $objActSheet->setCellValueExplicit('AG' . $index, $remark);
                                if ($value['order_from'] == 0) {
                                    $objActSheet->setCellValueExplicit('AH' . $index, $value['expect_use_time'] ? date('Y-m-d H:i:s', $value['expect_use_time']) : L_('无'));
                                } else {
                                    $objActSheet->setCellValueExplicit('AH' . $index, $value['arrival_time_info'] ? $value['arrival_time_info'] : L_('无'));
                                }
                                $index++;
                            }
                        }
                    }
                    sleep(2);
                }
                break;
            case 'deliver_list':
                require_once APP_PATH . 'Lib/ORG/phpexcel/PHPExcel.php';
                $title = L_('配送列表');
                $objExcel = new PHPExcel();
                $objProps = $objExcel->getProperties();
                // 设置文档基本属性
                $objProps->setCreator($title);
                $objProps->setTitle($title);
                $objProps->setSubject($title);
                $objProps->setDescription($title);
                // 设置当前的sheet
                $status = $param['status']??-1;
                $day = intval($param['day']);
                $period = htmlspecialchars($param['period']);
                $order_from = isset($param['order_from']) ? intval($param['order_from']) : -1;
                $deliver_status = $param['deliver_status'] ?? -1;

                $province_id = intval($param['province_idss']);
                $city_id = intval($param['city_idss']);
                $area_id = intval($param['area_id']);
                if ($param['system_session']['area_id']) {
                    $now_area = D('Area')->field(true)->where(array('area_id' => $param['system_session']['area_id']))->find();
                    if ($now_area['area_type'] == 3) {
                        $area_id = $param['system_session']['area_id'];
                        $city_id = $now_area['area_pid'];
                        $temp_area = D('Area')->field(true)->where(array('area_id' => $city_id))->find();
                        $province_id = $temp_area['area_pid'];
                    } elseif ($now_area['area_type'] == 2) {
                        $city_id = $param['system_session']['area_id'];
                        $province_id = $now_area['area_pid'];
                    } elseif ($now_area['area_type'] == 1) {
                        $province_id = $param['system_session']['area_id'];
                    }
                    //$this->assign('admin_area', $now_area['area_type']);
                }

                $timetype = $param['timetype'];
                $time = $param['time'];
                $stime = $etime = 0;
                if ($day) {
                    if ($day == 1) {
                        $stime = strtotime(date('Y-m-d'));
                        $etime = time();
                    } else {
                        $stime = strtotime("-{$day} day");
                        $etime = time();
                    }
                }
                if ($period) {
                    $time_array = explode('-', $period);
                    $stime = strtotime($time_array[0]);
                    $etime = strtotime($time_array[1]) + 86400;
                }

                //$sql = "SELECT count(1) AS cnt FROM " . C('DB_PREFIX') . "merchant_store AS m RIGHT JOIN " . C('DB_PREFIX') . "deliver_supply AS s ON m.store_id=s.store_id LEFT JOIN " . C('DB_PREFIX') . "deliver_user AS u ON s.uid=u.uid";
                $sql = "SELECT count(1) AS cnt FROM " .
                    C('DB_PREFIX') . "deliver_supply AS s LEFT JOIN " .
                    C('DB_PREFIX') . "merchant_store AS m ON m.store_id=s.store_id LEFT JOIN " .
                    C('DB_PREFIX') . "deliver_user AS u ON s.uid=u.uid LEFT JOIN " .
                    C('DB_PREFIX') . "service_user_publish AS sp ON (sp.publish_id=s.order_id AND s.`server_type` <> 0) LEFT JOIN " .
                    C('DB_PREFIX') . "user AS us ON (sp.uid=us.uid AND s.`server_type` <> 0)";


                $sql .= ' WHERE s.type=0';
                if ($province_id > 0) {
                    $sql .= " AND s.province_id=" . $province_id;
                }
                if ($city_id > 0) {
                    $sql .= " AND s.city_id=" . $city_id;
                }
                if ($area_id > 0) {
                    $sql .= " AND s.area_id=" . $area_id;
                }
                // 判断需要查询的类型，做判断
                $keyword = htmlspecialchars($param['keyword']);
                $searchtype = htmlspecialchars($param['searchtype']);
                if (!empty($keyword)) {
                    if ($searchtype == 'real_orderid') {
                        $order_sql = '(SELECT CASE  WHEN s.`server_type` <> 0 THEN (SELECT order_sn FROM pigcms_service_user_publish AS spps WHERE spps.publish_id = s.order_id) ELSE (SELECT real_orderid FROM pigcms_shop_order AS sho WHERE sho.order_id = s.order_id) END AS order_sn)';
                        $sql .= ' AND ' . $order_sql . ' = "' . $keyword . '"';
                    } elseif ($_GET['searchtype'] == 'orderid') {
                        $orderid_sql = "(SELECT CASE  WHEN s.`server_type` <> 0 THEN (SELECT orderid FROM pigcms_plat_order AS spp WHERE spp.business_id = s.order_id AND spp.business_type = 'service' AND spp.is_final_pay = '0') ELSE (SELECT orderid FROM pigcms_shop_order AS sho WHERE sho.order_id = s.order_id) END AS third_id)";
                        $sql .= ' AND ' . $orderid_sql . ' = "' . $keyword . '"';
                    } elseif ($searchtype == 's_name') {
                        $sql .= ' AND m.name LIKE "%' . $keyword . '%"';
                    } elseif ($searchtype == 'name') {
                        $sql .= ' AND s.name LIKE "%' . $keyword . '%"';
                    } elseif ($searchtype == 'phone') {
                        $sql .= ' AND (s.phone LIKE "%' . $keyword . '%" OR us.phone LIKE "%' . $keyword . '%")';
                    } elseif ($searchtype == 'd_name') {
                        $sql .= ' AND u.name LIKE "%' . $keyword . '%"';
                    }
                }


                if ($time > 0) {
                    $sqltime = $time * 60;
                    if ($timetype == 0) {
                        $sql .= " AND s.create_time>s.order_time AND s.create_time-s.order_time>" . $sqltime;
                    } elseif ($timetype == 1) {
                        $sql .= " AND s.start_time>s.create_time AND s.start_time-s.create_time>" . $sqltime;
                    } elseif ($timetype == 2) {
                        $sql .= " AND s.end_time>s.start_time AND s.end_time-s.start_time>" . $sqltime;
                    } elseif ($timetype == 3) {
                        $sql .= " AND s.end_time>s.pay_time AND s.end_time-s.pay_time>" . $sqltime;
                    } elseif ($timetype == 4) {
                        $sql .= " AND s.end_time > 0 AND s.end_time-s.appoint_time>" . $sqltime;
                    }
                }
                if ($stime && $etime) {
                    $sql .= " AND s.create_time>'{$stime}' AND s.create_time<'{$etime}'";
                }

                if ($deliver_status > -1) {
                    if ($deliver_status == 1) {
                        $sql .= " AND ((s.deliver_status=2 AND s.is_fetch_order=0) OR (s.deliver_status=" . $deliver_status . "))";
                    } else {
                        $sql .= " AND s.deliver_status=" . $deliver_status;
                    }
                }

                if ($status != -1) {
                    $sql .= " AND s.status='{$status}'";
                }

                if ($order_from > -1) {
                    if ($order_from == 0) {
                        $sql .= " AND s.order_from<3";
                    } elseif ($order_from == 4) {
                        $sql .= " AND (s.order_from=4 OR s.order_from=5)";
                    } else {
                        $sql .= " AND s.order_from=" . $order_from;
                    }
                }

                if ($param['system_session']['area_id']) {
                    $area_index = D('Area')->getIndexByAreaID($param['system_session']['area_id']);
                    $sql .= ' AND (s.' . $area_index . '=' . $param['system_session']['area_id'] . ' OR m.' . $area_index . '=' . $param['system_session']['area_id'] . ')';
                }

                $count = D()->query($sql);
                $count = isset($count[0]['cnt']) ? intval($count[0]['cnt']) : 0;

                $length = ceil($count / 1000);
                $m_shop_order_detail = M('Shop_order_detail');
                $m_shop_order = M('Shop_order');
                $m_service_user_publish_buy = M('Service_user_publish_buy');
                $m_service_user_publish_give = M('Service_user_publish_give');
                $m_service_user_publish = M('Service_user_publish');
                $m_area = M('Area');
                $d_pay = D('Pay');
                for ($i = 0; $i < $length; $i++) {
                    $i && $objExcel->createSheet();
                    $objExcel->setActiveSheetIndex($i);

                    $objExcel->getActiveSheet()->setTitle(L_('第X1个一千配送订单', ($i + 1)));
                    $objActSheet = $objExcel->getActiveSheet();

                    $objActSheet->setCellValue('A1', L_('订单编号'));
                    $objActSheet->setCellValue('B1', L_('配送ID'));
                    $objActSheet->setCellValue('C1', L_('订单来源'));
                    $objActSheet->setCellValue('D1', L_('商品名称'));
                    $objActSheet->setCellValue('E1', L_('商品价格'));
                    $objActSheet->setCellValue('F1', L_('商品数量'));
                    $objActSheet->setCellValue('G1', L_('店铺名称'));
                    $objActSheet->setCellValue('H1', L_('店铺商圈'));
                    $objActSheet->setCellValue('I1', L_('客户名称'));
                    $objActSheet->setCellValue('J1', L_('用户ID'));
                    $objActSheet->setCellValue('K1', L_('客户手机'));
                    $objActSheet->setCellValue('L1', L_('客户地址'));
                    $objActSheet->setCellValue('M1', L_('支付状态'));
                    $objActSheet->setCellValue('N1', L_('支付方式'));
                    $objActSheet->setCellValue('O1', L_('支付来源'));
                    $objActSheet->setCellValue('p1', L_('支付时间'));
                    $objActSheet->setCellValue('Q1', L_('订单价格'));
                    $objActSheet->setCellValue('R1', L_('应收现金'));
                    $objActSheet->setCellValue('S1', L_('配送员昵称'));
                    $objActSheet->setCellValue('T1', L_('区'));
                    $objActSheet->setCellValue('U1', L_('配送员手机号'));
                    $objActSheet->setCellValue('V1', L_('开始时间'));
                    $objActSheet->setCellValue('W1', L_('送达时间'));
                    $objActSheet->setCellValue('X1', L_('配送费'));
                    $objActSheet->setCellValue('Y1', L_('小费'));
                    $objActSheet->setCellValue('Z1', L_('店员接单时长'));
                    $objActSheet->setCellValue('AA1', L_('配送员接单时长'));
                    $objActSheet->setCellValue('AB1', L_('配送时长'));
                    $objActSheet->setCellValue('AC1', L_('配送员取货时间'));
                    $objActSheet->setCellValue('AD1', L_('配送员开始配送时间'));
                    $objActSheet->setCellValue('AE1', L_('配送状态'));
                    $objActSheet->setCellValue('AF1', L_('接单类型'));
                    $objActSheet->setCellValue('AG1', L_('流水单号'));
                    $objActSheet->setCellValue('AH1', L_('订单备注'));
                    $objActSheet->setCellValue('AI1', L_('期望送达时间'));
                    $objActSheet->setCellValue('AJ1', L_('取件地址'));

                    $fied = "s.`tip_price`,s.`freight_charge`,m.`circle_id`, s.`supply_id`, s.`order_id`,sp.`publish_id`,sp.`address`, " .
                        "CASE  WHEN s.`server_type` = 0 THEN (SELECT expect_use_time FROM pigcms_shop_order AS sho WHERE sho.order_id = s.order_id)  WHEN s.`server_type` = 1 THEN (SELECT fetch_time FROM pigcms_service_user_publish_give AS supg WHERE supg.publish_id = s.order_id)  ELSE (SELECT arrival_time_info FROM pigcms_service_user_publish_buy AS supb WHERE supb.publish_id = s.order_id) END AS expect_time, " .
                        "CASE  WHEN s.`server_type` <> 0 THEN (SELECT is_own FROM pigcms_plat_order AS spp WHERE spp.business_id = s.order_id AND spp.business_type = 'service' AND spp.is_final_pay = '0') ELSE (SELECT is_own FROM pigcms_shop_order AS sho WHERE sho.order_id = s.order_id) END AS is_own, " .
                        "CASE  WHEN s.`server_type` <> 0 THEN (SELECT pay_time FROM pigcms_plat_order AS spp WHERE spp.business_id = s.order_id AND spp.business_type = 'service' AND spp.is_final_pay = '0') ELSE (SELECT pay_time FROM pigcms_shop_order AS sho WHERE sho.order_id = s.order_id) END AS order_pay_time, " .
                        "CASE  WHEN s.`server_type` <> 0 THEN (SELECT orderid FROM pigcms_plat_order AS spp WHERE spp.business_id = s.order_id AND spp.business_type = 'service' AND spp.is_final_pay = '0') ELSE (SELECT orderid FROM pigcms_shop_order AS sho WHERE sho.order_id = s.order_id) END AS third_id, " .
                        "CASE  WHEN s.`server_type` <> 0 THEN (SELECT order_sn FROM pigcms_service_user_publish AS spps WHERE spps.publish_id = s.order_id) WHEN s.`item`= 8 THEN (SELECT real_orderid FROM pigcms_village_group_order AS vgo WHERE vgo.order_id = s.order_id) WHEN s.`item`= 4 THEN (SELECT order_no FROM pigcms_mall_order AS mo WHERE mo.order_id = s.order_id)  WHEN s.`item`= 5 THEN (SELECT mo.order_no FROM pigcms_mall_new_periodic_purchase_order AS periodic_o LEFT JOIN pigcms_mall_order AS mo ON periodic_o.order_id=mo.order_id WHERE periodic_o.id = s.order_id) ELSE (SELECT real_orderid FROM pigcms_shop_order AS sho WHERE sho.order_id = s.order_id) END AS order_sn, ".

//                        "CASE  WHEN s.`server_type` <> 0 THEN (SELECT pay_type FROM pigcms_plat_order AS spp WHERE spp.business_id = s.order_id AND spp.business_type = 'service' AND spp.is_final_pay = '0') ELSE (SELECT pay_type FROM pigcms_shop_order AS sho WHERE sho.order_id = s.order_id) END AS order_pay_type, " .
                        "CASE  WHEN s.`server_type` <> 0 THEN (SELECT pay_type FROM pigcms_plat_order AS spp WHERE spp.business_id = s.order_id AND spp.business_type = 'service' AND spp.is_final_pay = '0') ELSE (CASE  WHEN s.`item`=4 THEN( SELECT online_pay_type as pay_type FROM pigcms_mall_order AS smo WHERE smo.order_id = s.order_id )ELSE( SELECT pay_type FROM pigcms_shop_order AS sho WHERE sho.order_id = s.order_id )END ) END AS order_pay_type, " .
                        "CASE  WHEN s.`server_type` <> 0 THEN (SELECT is_mobile_pay FROM pigcms_plat_order AS spp WHERE spp.business_id = s.order_id AND spp.business_type = 'service' AND spp.is_final_pay = '0') ELSE (SELECT is_mobile_pay FROM pigcms_shop_order AS sho WHERE sho.order_id = s.order_id) END AS order_is_mobile_pay, " .
//                        "CASE  WHEN s.`server_type` <> 0 THEN (SELECT paid FROM pigcms_plat_order AS spp WHERE spp.business_id = s.order_id AND spp.business_type = 'service' AND spp.is_final_pay = '0') else (SELECT paid FROM pigcms_shop_order AS sho WHERE sho.order_id = s.order_id) END AS order_paid, " .
                        "CASE  WHEN s.`server_type` <> 0 THEN (SELECT paid FROM pigcms_plat_order AS spp WHERE spp.business_id = s.order_id AND spp.business_type = 'service' AND spp.is_final_pay = '0') else (CASE paid WHEN s.`item` = 4 THEN (SELECT paid FROM pigcms_system_order AS sso WHERE sso.order_id = s.order_id AND sso.type = 'mall3') ELSE (SELECT paid FROM pigcms_shop_order AS sho WHERE sho.order_id = s.order_id) END) END AS order_paid, " .
                        "s.order_id, s.create_time,s.order_time, s.order_from, s.item, s.name as username, s.phone as userphone, m.name as storename, s.money, u.name, u.phone, s.start_time, s.end_time,s.pay_time, s.aim_site, s.pay_type, s.paid, s.status, s.deliver_cash, s.distance, s.from_lat, s.aim_lat, s.from_lnt, s.aim_lnt, s.get_type, s.server_type,s.province_id,s.city_id,s.area_id,s.uid";

                    $sql = "SELECT " . $fied . " FROM " .
                        C('DB_PREFIX') . "deliver_supply AS s LEFT JOIN " .
                        C('DB_PREFIX') . "merchant_store AS m ON m.store_id=s.store_id LEFT JOIN " .
                        C('DB_PREFIX') . "deliver_user AS u ON s.uid=u.uid LEFT JOIN " .
                        C('DB_PREFIX') . "service_user_publish AS sp ON (sp.publish_id=s.order_id AND s.`server_type` <> 0) LEFT JOIN " .
                        C('DB_PREFIX') . "user AS us ON (sp.uid=us.uid AND s.`server_type` <> 0)";


                    $sql .= ' WHERE s.type=0';
                    if ($province_id > 0) {
                        $sql .= " AND s.province_id=" . $province_id;
                    }
                    if ($city_id > 0) {
                        $sql .= " AND s.city_id=" . $city_id;
                    }
                    if ($area_id > 0) {
                        $sql .= " AND s.area_id=" . $area_id;
                    }
                    // 判断需要查询的类型，做判断
                    if (!empty($keyword)) {
                        if ($searchtype == 'real_orderid') {
                            $order_sql = '(SELECT CASE  WHEN s.`server_type` <> 0 THEN (SELECT order_sn FROM pigcms_service_user_publish AS spps WHERE spps.publish_id = s.order_id) ELSE (SELECT real_orderid FROM pigcms_shop_order AS sho WHERE sho.order_id = s.order_id) END AS order_sn)';
                            $sql .= ' AND ' . $order_sql . ' = "' . $keyword . '"';
                        } elseif ($searchtype == 'orderid') {
                            $orderid_sql = "(SELECT CASE  WHEN s.`server_type` <> 0 THEN (SELECT orderid FROM pigcms_plat_order AS spp WHERE spp.business_id = s.order_id AND spp.business_type = 'service' AND spp.is_final_pay = '0') ELSE (SELECT orderid FROM pigcms_shop_order AS sho WHERE sho.order_id = s.order_id) END AS third_id)";
                            $sql .= ' AND ' . $orderid_sql . ' = "' . $keyword . '"';
                        } elseif ($searchtype == 's_name') {
                            $sql .= ' AND m.name LIKE "%' . $keyword . '%"';
                        } elseif ($searchtype == 'name') {
                            $sql .= ' AND s.name LIKE "%' . $keyword . '%"';
                        } elseif ($searchtype == 'phone') {
                            $sql .= ' AND (s.phone LIKE "%' . $keyword . '%" OR us.phone LIKE "%' . $keyword . '%")';
                        } elseif ($searchtype == 'd_name') {
                            $sql .= ' AND u.name LIKE "%' . $keyword . '%"';
                        }
                    }
                    if ($time > 0) {
                        $sqltime = $time * 60;
                        if ($timetype == 0) {
                            $sql .= " AND s.create_time>s.order_time AND s.create_time-s.order_time>" . $sqltime;
                        } elseif ($timetype == 1) {
                            $sql .= " AND s.start_time>s.create_time AND s.start_time-s.create_time>" . $sqltime;
                        } elseif ($timetype == 2) {
                            $sql .= " AND s.end_time>s.start_time AND s.end_time-s.start_time>" . $sqltime;
                        } elseif ($timetype == 3) {
                            $sql .= " AND s.end_time>s.pay_time AND s.end_time-s.pay_time>" . $sqltime;
                        } elseif($timetype == 4){
                            $sql .= " AND s.end_time > 0 AND s.end_time-s.appoint_time>" . $sqltime;
                        }
                    }
                    if ($stime && $etime) {
                        $sql .= " AND s.create_time>'{$stime}' AND s.create_time<'{$etime}'";
                    }
                    if ($deliver_status > -1) {
                        if ($deliver_status == 1) {
                            $sql .= " AND ((s.deliver_status=2 AND s.is_fetch_order=0) OR (s.deliver_status=" . $deliver_status . "))";
                        }elseif($deliver_status == 2){
                            $sql .= " AND s.deliver_status=" . $deliver_status." AND s.is_fetch_order=1";
                        }elseif($deliver_status == 5) {
                            $sql .= " AND (s.deliver_status=5 AND s.status<>0)";
                        }elseif($deliver_status == 0) {
                            $sql .= " AND (s.deliver_status=0 OR s.status=0)";
                        } else {
                            $sql .= " AND s.deliver_status=" . $deliver_status;
                        }
                    }
                    if ($status != -1) {
                        $sql .= " AND s.status='{$status}'";
                    }
                    if ($order_from > -1) {
                        if ($order_from == 0) {
                            $sql .= " AND s.order_from<3";
                        } elseif ($order_from == 4) {
                            $sql .= " AND (s.order_from=4 OR s.order_from=5)";
                        } else {
                            $sql .= " AND s.order_from=" . $order_from;
                        }
                    }
                    if ($param['system_session']['area_id']) {
                        $area_index = D('Area')->getIndexByAreaID($param['system_session']['area_id']);
                        $sql .= ' AND (s.' . $area_index . '=' . $param['system_session']['area_id'] . ' OR m.' . $area_index . '=' . $param['system_session']['area_id'] . ')';
                    }

                    $sql .= ' ORDER BY s.`supply_id` DESC LIMIT ' . $i * 1000 . ', 1000';

                    $supply_list = D()->query($sql);
                    

                    if (!empty($supply_list)) {
                        $areaids = array_unique(array_merge(array_column($supply_list, 'province_id'),array_column($supply_list, 'city_id'),array_column($supply_list, 'area_id')));
                        $areaNames = M("Area")->where(['area_id'=>['in',$areaids]])->field("area_id,area_name")->select();
                        $areaNames = $areaNames ? array_column($areaNames, 'area_name', 'area_id') : [];
                        import('ORG.Net.IpLocation');
                        $IpLocation = new IpLocation();
                        $index = 2;
                        foreach ($supply_list as $value) {
                            $value['confirm_time'] = $value['create_time'] && $value['order_time'] ? intval(($value['create_time'] - $value['order_time']) / 60) . L_('分钟') : '-';
                            $value['grab_time'] = $value['start_time'] ? intval(($value['start_time'] - $value['create_time']) / 60) . L_('分钟') : '-';
                            $value['deliver_use_time'] = $value['end_time'] ? intval(($value['end_time'] - $value['start_time']) / 60) . L_('分钟') : '-';
                            $where['order_id'] = $value['order_id'];
                            $where['status'] = array('in', '4,5');
                            $times = D('Shop_order_log')->where($where)->select();

                            $third_id = '';
                            // 查询一下关联 快店的商品信息
                            $circle_info = '';
                            $order_from_info = '-'; // 订单来源 0：wap快店，1：wap商城，2：Android，3：ios,4:小程序,5：pc快店,6:线下零售,7.饿了么，8.美团
                            if ($value['item'] != 3 && $value['order_from'] != 3 && $value['order_from'] != 4 && $value['order_from'] != 5) {
                                if($value['item']==4){
                                    $shop_detail = D('Mall_order_detail')->where(array('order_id' => $value['order_id']))->field('name, num, price')->select();
                                }else{
                                    $shop_detail = $m_shop_order_detail->where(array('order_id' => $value['order_id']))->field('name, num, price')->select();
                                }
                                $info = $m_shop_order->where(array('order_id' => $value['order_id']))->field('uid, desc, order_from')->find();
                                $userId = $info['uid'];
                                $remark = $info['desc'];
                                if ($info['order_from'] == 0) {
                                    $order_from_info = L_('wapX1',array('X1'=>$this->config['shop_alias_name']));
                                } elseif ($info['order_from'] == 1) {
                                    $order_from_info = L_('wap商城');
                                } elseif ($info['order_from'] == 2) {
                                    $order_from_info = 'Android';
                                } elseif ($info['order_from'] == 3) {
                                    $order_from_info = 'ios';
                                } elseif ($info['order_from'] == 4) {
                                    $order_from_info = L_('小程序');
                                } elseif ($info['order_from'] == 5) {
                                    $order_from_info = L_('pcX1',array('X1'=>$this->config['shop_alias_name']));
                                } elseif ($info['order_from'] == 6) {
                                    $order_from_info = L_('线下零售');
                                } elseif ($info['order_from'] == 7) {
                                    $order_from_info = L_('饿了么');
                                } elseif ($info['order_from'] == 8) {
                                    $order_from_info = L_('美团');
                                }
                                if ($value['circle_id']) {
                                    // 获取对应商圈信息
                                    $condition_area = array(
                                        'area_id' => $value['circle_id']
                                    );
                                    $circle_arr = $m_area->where($condition_area)->field('area_pid, area_name, area_ip_desc')->find();
                                    if (!$circle_arr['area_ip_desc']) {
                                        if ($circle_arr['area_pid']) {
                                            $condition_pid_area = array(
                                                'area_id' => $circle_arr['area_pid']
                                            );
                                            $circle_msg = $m_area->where($condition_pid_area)->getField('area_name');
                                            $circle_info = $circle_msg . $circle_arr['area_name'];
                                        } else {
                                            $circle_info = $circle_arr['area_name'];
                                        }
                                    } else {
                                        $circle_info = $circle_arr['area_ip_desc'];
                                    }
                                } else {
                                    $circle_info = '';
                                }
                            } else {
                                // 帮我送
                                $userId = $m_service_user_publish->where(array('publish_id' => $value['order_id']))->getField('uid');
                                if ($value['order_from'] == 3) {
                                    $remark = $m_service_user_publish_give->where(array('publish_id' => $value['order_id']))->getField('remarks');
                                } else {
                                    // 帮我买
                                    $remark = $m_service_user_publish_buy->where(array('publish_id' => $value['order_id']))->getField('goods_remarks');
                                }
                                $shop_detail = array();
                            }

                            $pay_time = $value['pay_time'] ? $value['pay_time'] : $value['order_pay_time'];
                            if ($shop_detail) {
                                $shop_first = false;
                                foreach ($shop_detail as $val) {
                                    if (!$shop_first) {
                                        $objActSheet->setCellValueExplicit('A' . $index, $value['order_sn']);
                                        $objActSheet->setCellValueExplicit('B' . $index, $value['supply_id']);
                                        if ($value['item'] == 0) {
                                            $objActSheet->setCellValueExplicit('C' . $index, $this->config['meal_alias_name']);
                                        } elseif ($value['item'] == 1) {
                                            $objActSheet->setCellValueExplicit('C' . $index, $this->config['waimai_alias_name']);
                                        } else {
                                            if ($value['order_from'] == 0) {
                                                $objActSheet->setCellValueExplicit('C' . $index, $this->config['shop_alias_name']);
                                            } elseif ($value['order_from'] == 1) {
                                                $objActSheet->setCellValueExplicit('C' . $index, L_('饿了么'));
                                            } elseif ($value['order_from'] == 2) {
                                                $objActSheet->setCellValueExplicit('C' . $index, L_('美团'));
                                            } elseif ($value['order_from'] == 3) {
                                                $objActSheet->setCellValueExplicit('C' . $index, L_('帮我送'));
                                            } elseif ($value['order_from'] == 4 || $value['order_from'] == 5) {
                                                $objActSheet->setCellValueExplicit('C' . $index, L_('帮我买'));
                                            }
                                        }
                                        $objActSheet->setCellValueExplicit('D' . $index, $val['name']);
                                        $objActSheet->setCellValueExplicit('E' . $index, $val['price']);
                                        $objActSheet->setCellValueExplicit('F' . $index, $val['num']);


                                        $objActSheet->setCellValueExplicit('G' . $index, $value['storename']);
                                        $objActSheet->setCellValueExplicit('H' . $index, $circle_info);
                                        $objActSheet->setCellValueExplicit('I' . $index, $value['username']);
                                        $objActSheet->setCellValueExplicit('J' . $index, $userId);
                                        $objActSheet->setCellValueExplicit('K' . $index, $value['userphone'] . ' ');
                                        $objActSheet->setCellValueExplicit('L' . $index, $value['aim_site']);

                                        if ($value['order_paid'] == 1) {
                                            $objActSheet->setCellValueExplicit('M' . $index, L_('已支付'));
                                        } else {
                                            $objActSheet->setCellValueExplicit('M' . $index, L_('未支付'));
                                        }
                                        $value['paytypestr'] = $d_pay->get_pay_name($value['order_pay_type'], $value['order_is_mobile_pay'], $value['order_paid']);
                                        $objActSheet->setCellValueExplicit('N' . $index, $value['paytypestr']);

                                        $objActSheet->setCellValueExplicit('O' . $index, $order_from_info);

                                        $objActSheet->setCellValueExplicit('P' . $index, $pay_time ? date('Y-m-d H:i:s', $pay_time) : '-');

                                        $objActSheet->setCellValueExplicit('Q' . $index, sprintf("%.2f", floatval($value['money'])));
                                        $objActSheet->setCellValueExplicit('R' . $index, sprintf("%.2f", floatval($value['deliver_cash'])));


                                        $objActSheet->setCellValueExplicit('S' . $index, $value['name']);
                                        $objActSheet->setCellValueExplicit('T' . $index, $areaNames[$value['province_id']].$areaNames[$value['city_id']].$areaNames[$value['area_id']]);
                                        $objActSheet->setCellValueExplicit('U' . $index, $value['phone'] . ' ');
                                        $objActSheet->setCellValueExplicit('V' . $index, $value['start_time'] ? date('Y-m-d H:i:s', $value['start_time']) : L_('还未开始'));
                                        $objActSheet->setCellValueExplicit('W' . $index, $value['end_time'] ? date('Y-m-d H:i:s', $value['end_time']) : L_('还未完成'));

                                        $objActSheet->setCellValueExplicit('X' . $index, sprintf("%.2f", floatval($value['freight_charge'])));
                                        $objActSheet->setCellValueExplicit('Y' . $index, sprintf("%.2f", floatval($value['tip_price'])));

                                        $objActSheet->setCellValueExplicit('Z' . $index, $value['confirm_time']);
                                        $objActSheet->setCellValueExplicit('AA' . $index, $value['grab_time']);
                                        $objActSheet->setCellValueExplicit('AB' . $index, $value['deliver_use_time']);
                                        if ($value['order_from'] == 3 || $value['order_from'] == 4 || $value['order_from'] == 5) {
                                            $objActSheet->setCellValueExplicit('AC' . $index, '-');
                                            $objActSheet->setCellValueExplicit('AD' . $index, '-');
                                            if ($value['third_id']) {
                                                $third_id = 'plat_' . $value['third_id'];
                                                if ($value['is_own'] > 0) {
                                                    $third_id .= '_' . $value['is_own'];
                                                }
                                            }
                                        } else {
                                            $objActSheet->setCellValueExplicit('AC' . $index, $times[0]['dateline'] ? date('Y-m-d H:i:s', $times[0]['dateline']) : L_('还未开始'));
                                            $objActSheet->setCellValueExplicit('AD' . $index, $times[1]['dateline'] ? date('Y-m-d H:i:s', $times[1]['dateline']) : L_('还未开始'));
                                            if ($value['third_id']) {
                                                $third_id = 'shop_' . $value['third_id'];
                                                if ($value['is_own'] > 0) {
                                                    $third_id .= '_' . $value['is_own'];
                                                }
                                            }
                                        }

                                        switch ($value['status']) {
                                            case 1:
                                                $value['order_status'] = L_("等待接单");
                                                break;
                                            case 2:
                                                $value['order_status'] = L_("已接单");
                                                break;
                                            case 3:
                                                $value['order_status'] = L_("已取货");
                                                break;
                                            case 4:
                                                $value['order_status'] = L_("开始配送");
                                                break;
                                            case 5:
                                                $value['order_status'] = L_("已完成");
                                                break;
                                            case 6:
                                                $value['order_status'] = L_("已评价");
                                                break;
                                            case 7:
                                                $value['order_status'] = L_("退款退款失败");
                                                break;
                                            default:
                                                $value['order_status'] = L_("订单失效");
                                                break;
                                        }
                                        $objActSheet->setCellValueExplicit('AE' . $index, $value['order_status']);
                                        if ($value['status'] < 2) {
                                            $value['get_type'] = '--';
                                        } elseif ($value['get_type'] == 0) {
                                            $value['get_type'] = L_('抢单');
                                        } else {
                                            $value['get_type'] = L_('系统派单');
                                        }
                                        $objActSheet->setCellValueExplicit('AF' . $index, $value['get_type']);
                                        $objActSheet->setCellValueExplicit('AG' . $index, $third_id);
                                        $objActSheet->setCellValueExplicit('AH' . $index, $remark);
                                        if ($value['server_type'] == 0) {
                                            $objActSheet->setCellValueExplicit('AI' . $index, $value['expect_time'] ? date('Y-m-d H:i:s', $value['expect_time']) : L_('无'));
                                        } else {
                                            $objActSheet->setCellValueExplicit('AI' . $index, $value['expect_time'] ? $value['expect_time'] : L_('无'));
                                        }
                                        $shop_first = true;
                                        $index++;
                                    } else {
                                        $objActSheet->setCellValueExplicit('D' . $index, $val['name']);
                                        $objActSheet->setCellValueExplicit('E' . $index, $val['price']);
                                        $objActSheet->setCellValueExplicit('F' . $index, $val['num']);
                                        $index++;
                                    }
                                }
                            } else {
                                $areaFind = D('Deliver_user')->where(['uid'=>$value['uid']])->find();
                                if(!$areaNames[$areaFind['area_id']]){
                                    $areaName = D('Area')->where(['area_id'=>$areaFind['area_id']])->find();
                                    $areaNamesAreaName = $areaName['area_name'];
                                }else{
                                    $areaNamesAreaName = $areaNames[$areaFind['area_id']];
                                }
                                $objActSheet->setCellValueExplicit('A' . $index, $value['order_sn']);
                                $objActSheet->setCellValueExplicit('B' . $index, $value['supply_id']);
                                if ($value['item'] == 0) {
                                    $objActSheet->setCellValueExplicit('C' . $index, $this->config['meal_alias_name']);
                                } elseif ($value['item'] == 1) {
                                    $objActSheet->setCellValueExplicit('C' . $index, $this->config['waimai_alias_name']);
                                } else {
                                    if ($value['order_from'] == 0) {
                                        $objActSheet->setCellValueExplicit('C' . $index, $this->config['shop_alias_name']);
                                    } elseif ($value['order_from'] == 1) {
                                        $objActSheet->setCellValueExplicit('C' . $index, L_('饿了么'));
                                    } elseif ($value['order_from'] == 2) {
                                        $objActSheet->setCellValueExplicit('C' . $index, L_('美团'));
                                    } elseif ($value['order_from'] == 3) {
                                        $objActSheet->setCellValueExplicit('C' . $index, L_('帮我送'));
                                    } elseif ($value['order_from'] == 4 || $value['order_from'] == 5) {
                                        $objActSheet->setCellValueExplicit('C' . $index, L_('帮我买'));
                                    }
                                }
                                $objActSheet->setCellValueExplicit('D' . $index, '-');
                                $objActSheet->setCellValueExplicit('E' . $index, '-');
                                $objActSheet->setCellValueExplicit('F' . $index, '-');


                                $objActSheet->setCellValueExplicit('G' . $index, $value['storename']);
                                $objActSheet->setCellValueExplicit('H' . $index, $circle_info);
                                $objActSheet->setCellValueExplicit('I' . $index, $value['username']);
                                $objActSheet->setCellValueExplicit('J' . $index, $userId);
                                $objActSheet->setCellValueExplicit('K' . $index, $value['userphone'] . ' ');
                                $objActSheet->setCellValueExplicit('L' . $index, $value['aim_site']);

                                if ($value['order_paid'] == 1) {
                                    $objActSheet->setCellValueExplicit('M' . $index, L_('已支付'));
                                } else {
                                    $objActSheet->setCellValueExplicit('M' . $index, L_('未支付'));
                                }
                                $value['paytypestr'] = $d_pay->get_pay_name($value['order_pay_type'], $value['order_is_mobile_pay'], $value['order_paid']);
                                $objActSheet->setCellValueExplicit('N' . $index, $value['paytypestr']);

                                $objActSheet->setCellValueExplicit('O' . $index, $order_from_info);

                                $objActSheet->setCellValueExplicit('P' . $index, $pay_time ? date('Y-m-d H:i:s', $pay_time) : '-');

                                $objActSheet->setCellValueExplicit('Q' . $index, sprintf("%.2f", floatval($value['money'])));
                                $objActSheet->setCellValueExplicit('R' . $index, sprintf("%.2f", floatval($value['deliver_cash'])));


                                $objActSheet->setCellValueExplicit('S' . $index, $value['name']);
                                $objActSheet->setCellValueExplicit('T' . $index, $areaNames[$areaFind['province_id']].$areaNames[$areaFind['city_id']].$areaNamesAreaName);
                                $objActSheet->setCellValueExplicit('U' . $index, $value['phone'] . ' ');
                                $objActSheet->setCellValueExplicit('V' . $index, $value['start_time'] ? date('Y-m-d H:i:s', $value['start_time']) : L_('还未开始'));
                                $objActSheet->setCellValueExplicit('W' . $index, $value['end_time'] ? date('Y-m-d H:i:s', $value['end_time']) : L_('还未完成'));

                                $objActSheet->setCellValueExplicit('X' . $index, sprintf("%.2f", floatval($value['freight_charge'])));
                                $objActSheet->setCellValueExplicit('Y' . $index, sprintf("%.2f", floatval($value['tip_price'])));

                                $objActSheet->setCellValueExplicit('Z' . $index, $value['confirm_time']);
                                $objActSheet->setCellValueExplicit('AA' . $index, $value['grab_time']);
                                $objActSheet->setCellValueExplicit('AB' . $index, $value['deliver_use_time']);

                                if ($value['order_from'] == 3 || $value['order_from'] == 4 || $value['order_from'] == 5) {
                                    $objActSheet->setCellValueExplicit('AC' . $index, '-');
                                    $objActSheet->setCellValueExplicit('AD' . $index, '-');
                                    if ($value['third_id']) {
                                        $third_id = 'plat_' . $value['third_id'];
                                        if ($value['is_own'] > 0) {
                                            $third_id .= '_' . $value['is_own'];
                                        }
                                    }
                                } else {
                                    $objActSheet->setCellValueExplicit('AC' . $index, $times[0]['dateline'] ? date('Y-m-d H:i:s', $times[0]['dateline']) : L_('还未开始'));
                                    $objActSheet->setCellValueExplicit('AD' . $index, $times[1]['dateline'] ? date('Y-m-d H:i:s', $times[1]['dateline']) : L_('还未开始'));
                                    if ($value['third_id']) {
                                        $third_id = 'shop_' . $value['third_id'];
                                        if ($value['is_own'] > 0) {
                                            $third_id .= '_' . $value['is_own'];
                                        }
                                    }
                                }

                                switch ($value['status']) {
                                    case 1:
                                        $value['order_status'] = L_("等待接单");
                                        break;
                                    case 2:
                                        $value['order_status'] = L_("已接单");
                                        break;
                                    case 3:
                                        $value['order_status'] = L_("已取货");
                                        break;
                                    case 4:
                                        $value['order_status'] = L_("开始配送");
                                        break;
                                    case 5:
                                        $value['order_status'] = L_("已完成");
                                        break;
                                    case 6:
                                        $value['order_status'] = L_("已评价");
                                        break;
                                    case 7:
                                        $value['order_status'] = L_("退款退款失败");
                                        break;
                                    default:
                                        $value['order_status'] = L_("订单失效");
                                        break;
                                }
                                $objActSheet->setCellValueExplicit('AE' . $index, $value['order_status']);
                                if ($value['status'] < 2) {
                                    $value['get_type'] = '--';
                                } elseif ($value['get_type'] == 0) {
                                    $value['get_type'] = L_('抢单');
                                } else {
                                    $value['get_type'] = L_('系统派单');
                                }
                                $objActSheet->setCellValueExplicit('AF' . $index, $value['get_type']);
                                $objActSheet->setCellValueExplicit('AG' . $index, $third_id);
                                $objActSheet->setCellValueExplicit('AH' . $index, $remark);
                                if ($value['server_type'] == 0) {
                                    $objActSheet->setCellValueExplicit('AI' . $index, $value['expect_time'] ? date('Y-m-d H:i:s', $value['expect_time']) : L_('无'));
                                } else {
                                    $objActSheet->setCellValueExplicit('AI' . $index, $value['expect_time'] ? $value['expect_time'] : L_('无'));
                                }
                                $objActSheet->setCellValueExplicit('AJ' . $index, $value['address']);
                                $index++;
                            }
                        }
                    }
                    sleep(2);
                }
                break;
            case 'reply':
                $title = L_('商家评论列表');
                // 设置文档基本属性
                $objProps->setCreator($title);
                $objProps->setTitle($title);
                $objProps->setSubject($title);
                $objProps->setDescription($title);
                // 设置当前的sheet
                $objExcel->setActiveSheetIndex(0);

                $where = ' WHERE r.status<2';
                if (!empty($param['keyword'])) {
                    if ($param['searchtype'] == 'm_name') {
                        $where .= " AND m.name LIKE '%" . htmlspecialchars($param['keyword']) . "%'";
                        $condition_merchant['mer_id'] = $param['keyword'];
                    } else if ($param['searchtype'] == 's_name') {
                        $where .= " AND s.name LIKE '%" . htmlspecialchars($param['keyword']) . "%'";
                    } else if ($param['searchtype'] == 'nickname') {
                        $where .= " AND u.nickname LIKE '%" . htmlspecialchars($param['keyword']) . "%'";
                    } else if ($param['searchtype'] == 'phone') {
                        $where .= " AND u.phone LIKE '%" . htmlspecialchars($param['keyword']) . "%'";
                    }
                }

                $order_type = isset($param['order_type']) ? intval($param['order_type']) : -1;
                if ($order_type != -1) {
                    $where .= ' AND r.order_type=' . $order_type;
                } else {
                    $param['order_type'] = -1;
                }
                if ($param['area_id']) {
                    $now_area = D('Area')->field(true)->where(array('area_id' => $param['area_id']))->find();
                    if ($now_area['area_type'] == 3) {
                        $area_index = 'area_id';
                    } elseif ($now_area['area_type'] == 2) {
                        $area_index = 'city_id';
                    } elseif ($now_area['area_type'] == 1) {
                        $area_index = 'province_id';
                    }
                    $where .= ' AND m.' . $area_index . '=' . $param['area_id'];
                }
                $sql_count = "SELECT count(1) AS count FROM " . C('DB_PREFIX') . "merchant AS m INNER JOIN " . C('DB_PREFIX') . "reply AS r ON r.mer_id = m.mer_id INNER JOIN " . C('DB_PREFIX') . "user AS u ON r.uid=u.uid LEFT JOIN " . C('DB_PREFIX') . "merchant_store AS s ON s.store_id=r.store_id {$where}";
                $count = M()->query($sql_count);
                $count = $count[0]['count'];
                $length = ceil($count / 1000);
                for ($i = 0; $i < $length; $i++) {
                    $i && $objExcel->createSheet();
                    $objExcel->setActiveSheetIndex($i);
                    $objExcel->getActiveSheet()->setTitle(L_('第X1个一千条订单信息', ($i + 1)));
                    $objActSheet = $objExcel->getActiveSheet();
                    $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('J')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('L')->setWidth(40);


                    $objActSheet->setCellValue('A1', L_('评论编号'));
                    $objActSheet->setCellValue('B1', L_('商家名称'));
                    $objActSheet->setCellValue('C1', L_('店铺名称'));
                    $objActSheet->setCellValue('D1', L_('用户名'));
                    $objActSheet->setCellValue('E1', L_('用户电话'));
                    $objActSheet->setCellValue('F1', L_('评论类型'));
                    $objActSheet->setCellValue('G1', L_('评论时间'));
                    $objActSheet->setCellValue('H1', L_('评分'));
                    $objActSheet->setCellValue('I1', L_('评论内容'));
                    $objActSheet->setCellValue('J1', L_('订单编号'));
                    $objActSheet->setCellValue('K1', L_('推荐商品'));
                    $objActSheet->setCellValue('L1', L_('配送评分'));


                    $sql = "SELECT r.*, m.name AS m_name, s.name AS s_name, u.nickname, u.phone FROM " . C('DB_PREFIX') . "merchant AS m INNER JOIN " . C('DB_PREFIX') . "reply AS r ON r.mer_id = m.mer_id INNER JOIN " . C('DB_PREFIX') . "user AS u ON r.uid=u.uid LEFT JOIN " . C('DB_PREFIX') . "merchant_store AS s ON s.store_id=r.store_id {$where}  ORDER BY r.`pigcms_id` DESC LIMIT " . $i * 1000 . ", 1000";
                    $result_list = D()->query($sql);
                    if (!empty($result_list)) {
                        $index = 2;
                        foreach ($result_list as $value) {
                            $objActSheet->setCellValueExplicit('A' . $index, $value['pigcms_id']);
                            $objActSheet->setCellValueExplicit('B' . $index, $value['m_name']);
                            $objActSheet->setCellValueExplicit('C' . $index, $value['s_name']);
                            $objActSheet->setCellValueExplicit('D' . $index, $value['nickname']);
                            $objActSheet->setCellValueExplicit('E' . $index, $value['phone']);
                            if ($value['order_type'] == 0) {
                                $type = C('config.group_alias_name');
                                $res = M('Group_order')->field('real_orderid')->where(array('order_id' => $value['order_id']))->find();
                                $value['order_id'] = $res['real_orderid'];
                            } elseif ($value['order_type'] == 2) {
                                $type = C('config.appoint_alias_name');
                            } elseif ($value['order_type'] == 3) {
                                $type = C('config.shop_alias_name');
                                $res = M('Shop_order')->field('real_orderid')->where(array('order_id' => $value['order_id']))->find();
                                $value['order_id'] = $res['real_orderid'];
                            } elseif ($value['order_type'] == 4) {
                                $type = C('config.meal_alias_name');
                                $res = M('Foodshop_order')->field('real_orderid')->where(array('order_id' => $value['order_id']))->find();
                                $value['order_id'] = $res['real_orderid'];
                            } else {
                                $type = L_('未知');
                            }
                            $objActSheet->setCellValueExplicit('F' . $index, $type);
                            $objActSheet->setCellValueExplicit('G' . $index, $value['add_time'] ? date('Y-m-d H:i:s', $value['add_time']) : '');
                            $objActSheet->setCellValueExplicit('H' . $index, $value['score']);
                            $objActSheet->setCellValueExplicit('I' . $index, $value['comment'] ? $value['comment'] : '');
                            $objActSheet->setCellValueExplicit('J' . $index, $value['order_id']);
                            $objActSheet->setCellValueExplicit('K' . $index, $value['goods'] ? $value['goods'] : '');
                            $objActSheet->setCellValueExplicit('L' . $index, $value['deliver_score'] ? $value['deliver_score'] : '');
                            $index++;
                        }
                    }
                    sleep(2);
                }
                break;
            case 'score_all':
                $title = L_('积分记录列表');
                // 设置文档基本属性
                $objProps->setCreator($title);
                $objProps->setTitle($title);
                $objProps->setSubject($title);
                $objProps->setDescription($title);
                // 设置当前的sheet
                $objExcel->setActiveSheetIndex(0);


                $condition_where = '22=22 ';
                if (!empty($param['keyword'])) {
                    if ($param['searchtype'] == 'name') {
                        $condition_where .= " AND `u`.`nickname` like '%" . htmlspecialchars($param['keyword']) . "%'";
                    } elseif ($param['searchtype'] == 'phone') {
                        $condition_where .= " AND  `u`.`phone` like '%" . htmlspecialchars($param['keyword']) . "%'";
                    }

                }
                $condition_where .= $param['type_info'] ? ' AND o.type = ' . $param['type_info'] : ' AND o.type = 1 ';
                if (!empty($param['begin_time']) && !empty($param['end_time'])) {
                    if ($param['begin_time'] > $param['end_time']) {
                        $this->error_tips(L_("结束时间应大于开始时间"));
                    }
                    $period = array(strtotime($param['begin_time'] . " 00:00:00"), strtotime($param['end_time'] . " 23:59:59"));
                    $condition_where .= " AND (o.time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
                }
                $order_count = D('User_score_list')->join('as o left join ' . C('DB_PREFIX') . 'user AS u ON u.uid = o.uid')->where($condition_where)->count();

                $length = ceil($order_count / 1000);

                for ($i = 0; $i < $length; $i++) {
                    $i && $objExcel->createSheet();
                    $objExcel->setActiveSheetIndex($i);
                    $objExcel->getActiveSheet()->setTitle(L_('第X1个一千条订单信息', ($i + 1)));
                    $objActSheet = $objExcel->getActiveSheet();
                    $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
                    $objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(35);
                    $objExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);


                    $objActSheet->setCellValue('A1', L_('编号'));
                    $objActSheet->setCellValue('B1', L_('昵称'));
                    $objActSheet->setCellValue('C1', L_('电话'));
                    $objActSheet->setCellValue('D1', L_('类型'));
                    $objActSheet->setCellValue('E1', L_('积分数量'));
                    $objActSheet->setCellValue('F1', L_('描述'));
                    $objActSheet->setCellValue('G1', L_('时间'));
                    $objActSheet->setCellValue('H1', L_('用户ID'));

                    $result_list = D('User_score_list')->join('as o left join ' . C('DB_PREFIX') . 'user AS u ON u.uid = o.uid')->where($condition_where)->order('o.id DESC')->limit($i * 1000 . ",1000")->select();

                    if (!empty($result_list)) {
                        $index = 2;
                        foreach ($result_list as $value) {
                            $objActSheet->setCellValueExplicit('A' . $index, $value['id']);
                            $objActSheet->setCellValueExplicit('B' . $index, $value['nickname']);
                            $objActSheet->setCellValueExplicit('C' . $index, $value['phone']);
                            if ($value['type'] == 1) {
                                $type = L_('获得积分');
                            } elseif ($value['type'] == 2) {
                                $type = L_('消费积分');
                            } elseif ($value['type'] == 3) {
                                $type = L_('分润积分');
                            } else {
                                $type = L_('未知');
                            }
                            $objActSheet->setCellValueExplicit('D' . $index, $type);
                            $objActSheet->setCellValueExplicit('E' . $index, $value['score']);
                            $objActSheet->setCellValueExplicit('F' . $index, $value['desc']);
                            $objActSheet->setCellValueExplicit('G' . $index, $value['time'] ? date('Y-m-d H:i:s', $value['time']) : '');
                            $objActSheet->setCellValueExplicit('H' . $index, $value['uid']);
                            $index++;
                        }
                    }
                    sleep(2);
                }
                break;
            case 'village_group':
                $this->export_village_group($objProps, $objExcel, $param);
                break;
            case 'village_group_send':
                $this->export_village_group_send($objProps, $objExcel, $param);
                break;
            case 'village_group_trade':
                $this->export_village_group_trade($objProps, $objExcel, $param);
                break;
            case 'distributor_spread':
                $this->export_distributor_spread($objProps, $objExcel, $param);
                break;
            case 'activation_code':
                $this->export_activation_code($objProps, $objExcel, $param);
                break;
            case 'open_level_log':
                $this->export_open_level_log($objProps, $objExcel, $param);
            case 'trade_new':
                $this->tradeExport($objProps, $objExcel, $param);
                break;
            case 'market_sell':
            case 'market_buy':
                $this->marketSellExport($objProps, $objExcel, $param);
                break;
            case 'mer_union':
                $this->merUnionExport($objProps, $objExcel, $param);
                break;
            case 'export_goods':
                //商品信息导出
                M('Shop_goods_batch_log')->where(['pigcms_id' => intval($param['log_id'])])->save(['status' => 1]);
                $this->exportGoods($objProps, $objExcel, $param);
                break;
            case 'export_goods_library':
                //商品库信息导出
                M('Shop_goods_batch_log')->where(['pigcms_id' => intval($param['log_id'])])->save(['status' => 1]);
                D('Shop_order')->exportGoods($objProps, $objExcel, $param);
                break;
            case 'statistics':
                $this->statisticsExport($objProps, $objExcel, $param);
                break;
            case 'platStatistics':
                $this->platStatisticsExport($objProps, $objExcel, $param);
                break;
            case 'meal_card_buy'://商家饭票充值记录
                $this->mealCardBuyExport($objProps, $objExcel, $param);
                break;
            case 'meal_card_spend'://商家饭票消费记录
                $this->mealCardSpendExport($objProps, $objExcel, $param);
                break;
            case 'pick_order':
                $this->pickOrderExport($objProps, $objExcel, $param);
               break;
            case 'plugin_vote':
                $this->pluginVoteExport($objProps, $objExcel, $param);
                break;
            case 'staff_bill':
                $this->exportStaffBill($objProps, $objExcel, $param);
                break;
            case 'goods_to_be_delivered':
                $this->exportGoodsToBeDelivered($objProps, $objExcel, $param);
                break;
            case 'merchant_bill':
                D('System_order')->exportBillOrder($objProps, $objExcel, $param);
                break;
            case 'recharge_order':
                D('User_recharge_order')->exportRechargeOrder($objProps, $objExcel, $param);
                break;
            case 'room_export':
                try {
                    D('House_village_user_vacancy')->roomExport($objProps, $objExcel, $param);
                } catch (\Exception $e) {
                    fdump_api([$param,$e->getMessage()],'$roomExport');
                }
                break;
            case 'card_recharge':
                $this->cardRechargeExport($param);
                return true;
                break;
            case 'online_recharge':
                $this->onlineRechargeExport($param);
                return true;
                break;
            default:
                return false;
        }


        ob_end_clean();
        //$objWriter = new PHPExcel_Writer_Excel5($objExcel);
        $param['rand_number'] = $param['rand_number']?$param['rand_number']:time();
        $filename = date("Y-m-d", time()) . '-' . $param['rand_number'] . '.xls';
        $objWriter = PHPExcel_IOFactory::createWriter($objExcel, 'Excel5');

        $objWriter->save('./runtime/' . $filename);

        if ($param['type'] == 'export_goods' || $param['type'] == 'export_goods_library') {
            //导出订单保存到upload列表
            $dir = './upload/export/goods/';
            $filePath = $dir . $filename;
            if (!is_dir($dir)) {
                mkdir($dir, 755, true);
            }
            if (copy('./runtime/' . $filename, $filePath)) {
                $update = [
                    'status' => 2,
                    'file' => $filePath
                ];
            } else {
                $update = [
                    'status' => 2,
                    'file' => './runtime/' . $filename
                ];
            };
            M('Shop_goods_batch_log')->where(['pigcms_id' => intval($param['log_id'])])->save($update);
        }
        return true;
    }
    
    public function cardRechargeExport($param){
        $card_new = D('Card_new');
        $param['is_export'] = 1;
        $card_list = $card_new->offline_recharge_list(1,$param);
        
        $csv_head = array(
            L_('订单编号'),
            L_('会员卡号'),
            L_('金额'),
            L_('V币'),
            L_('描述'),
            L_('充值时间'),
            L_('商家名称'),
            L_('订单用户'),
            L_('订单手机'),
        );

        $csv_data = [];

        foreach ($card_list as $value) {
            $csv_data[] = [
                $value['id'],
                $value['cardid'],
                $value['money_add'],
                $value['score_add'],
                $value['desc'],
                $value['time'] ? date('Y-m-d H:i:s',$value['time']) : '',
                $value['name'],
                $value['nickname'],
                $value['phone'],
            ];
        }
        $filename = date("Y-m-d", time()) . '-' . $param['rand_number'] . '.xls';
        $csvFileName = './runtime/' . $filename;
        $this->putCsv($csvFileName, $csv_data, $csv_head);
        return true;
    }

    public function onlineRechargeExport($param){
        $card_new = D('Card_new');
        $param['is_export'] = 1;
        $card_list = $card_new->online_recharge_list(1, 'DISTINCT c.order_id,o.order_id AS plat_order_id,o.pay_type AS plat_pay_type,o.is_mobile_pay,us.cardid,c.money,c.add_time,m.name,u.uid,us.nickname,us.phone',$param);

        $csv_head = array(
            L_('订单编号'),
            L_('会员卡号'),
            L_('金额'),
            L_('充值时间'),
            L_('商家名称'),
            L_('订单用户'),
            L_('订单手机'),
            L_('支付方式'),
        );

        $csv_data = [];

        foreach ($card_list as $value) {
            $csv_data[] = [
                $value['order_id'],
                $value['cardid'],
                $value['money'],
                $value['add_time'] ? date('Y-m-d H:i:s',$value['add_time']) : '',
                $value['name'],
                $value['nickname'],
                $value['phone'],
                D('Pay')->get_pay_name($value['plat_pay_type'], $value['is_mobile_pay'], 1,$value['plat_order_id'],[],'plat'),
            ];
        }
        $filename = date("Y-m-d", time()) . '-' . $param['rand_number'] . '.xls';
        $csvFileName = './runtime/' . $filename;
        $this->putCsv($csvFileName, $csv_data, $csv_head);
        return true;
    }

    public function get_order_status($order)
    {
        $status = '';
        if ($order['paid']) {
            if ($order['pay_type'] == 'offline' && empty($order['third_id']) && $order['status'] == 0) {
                $status = L_('线下支付，未付款');
            } elseif ($order['status'] == 0) {
                $status = L_('已付款');
                if ($order['tuan_type'] != 2) {
                    $status = L_('已付款');
                } else {
                    if ($order['is_pick_in_store']) {
                        $status .= L_('未取货');
                    } else {
                        if ($order['express_id']) {
                            $status .= L_('未确认收货');
                        } else {
                            $status .= L_('未发货');
                        }
                    }
                }
            } elseif ($order['status'] == 1) {
                if ($order['tuan_type'] != 2) {
                    $status = L_('已消费');
                } else {
                    if ($order['is_pick_in_store']) {
                        $status = L_('已取货');
                    } else {
                        $status = L_('已发货');
                    }
                }
                $status .= L_('待评价');
            } elseif ($order['status'] == 2) {
                $status = L_('已完成');
            } elseif ($order['status'] == 3) {
                $status = L_('已退款');
            } elseif ($order['status'] == 4) {
                $status = L_('已取消');
            }
        } else {
            if ($status == 4) {
                $status = L_('已取消');
            } else {
                $status = L_('未付款');
            }
        }

        return $status;
    }

    protected function get_alias_name()
    {
        $c_name = array(
            'all' => L_('选择分类'),
            'group' => C('config.group_alias_name'),
            'shop' => C('config.shop_alias_name'),
            'shop_offline' => C('config.shop_alias_name') . L_('线下零售'),
            'meal' => C('config.meal_alias_name'),
            'appoint' => C('config.appoint_alias_name'),
            'waimai' => L_('外卖'),
            'store' => L_('优惠买单'),
            'cash' => L_('到店支付'),
            'weidian' => L_('微店'),
            'wxapp' => L_('营销'),
            'withdraw' => L_('提现'),
            'coupon' => L_('优惠券'),
            'withdraw' => L_('提现'),
            'activity' => L_('平台活动'),
            'spread' => L_('商家推广'),
            'market_order' => L_('批发'),
            'village_group' => L_('社区团购'),
        );
        if (!C('config.store_open_waimai')) unset($c_name['waimai']);
        if (!C('config.wxapp_url')) unset($c_name['wxapp']);
        if (!C('config.appoint_page_row')) unset($c_name['appoint']);
        if (!C('config.is_open_weidian')) unset($c_name['weidian']);
        if (!C('config.is_cashier')) unset($c_name['store']);
        if (!C('config.pay_in_store') || !C('config.is_cashier')) unset($c_name['cash'], $c_name['shop_offline']);
        return $c_name;
    }


    /*
     *  1. 营业金额：即支付成功+退款失败的订单金额之和
     *  2. 营业单数：即支付成功+退款失败的订单数之和
     *  3. 实收金额：即已验证消费金额之和
     *  4. 实收单数：即已验证消费订单数之和
     *  5. 退款金额：退款成功的退款金额之和
     *  6. 退款单数：退款成功的退款订单数之和
     */

    public function sell_order_date($is_system = true, $param)
    {
        $mer_list = $param['mer_list'];
        $store_list = $param['store_list'];
        $staff_list = $param['staff_list'];
        $pay_type = $param['pay_type'];
        $get_type = $param['type'];
        if ($get_type == 'all') {
            $type_arr = array('group','shop','meal','dining','appoint','store','sport','scenic','life_tools_appoint_join','mall');
        }else{
            $type_arr[] =  $get_type;
        }
        // 营业总额
        $arr['total_money_sum'] = 0;
        // 实收总额
        $arr['consume_money_sum'] = 0;
        // 退款金额
        $arr['refund_money_sum'] = 0;

        // 营业总订单数
        $arr['total_money_count'] = 0;
        // 实收总订单数
        $arr['consume_money_count'] = 0;
        // 退款总订单数
        $arr['refund_money_count'] = 0;

        // 总数 用于计算页数
        $total_count = 0;

        $total_count_all = 0;

        $date_arr_all = array();

        // 记录已有日期
        $date_arr = array();
        foreach ($type_arr as $key => $type) {
            $where = array();
            if ($type == 'offline_shop') {
                $type = 'shop';
                $where['order_from'] = array('eq', 6);
            } elseif ($type == 'only_shop') {
                $type = 'shop';
                $where['order_from'] = array('neq', 6);
            }

            if ($param['selectTimeType'] > 0) {
                $start_time = strtotime(date('Y-m-d', time() - ($param['selectTimeType'] - 1) * 86400));
                $end_time = time();
            }

            // 区域删选筛选
            $store_list_id = array();
            // 店铺筛选
            $store_list_id1 = array();
            if ($store_list && $store_list != -1) {
                $store_list_id1 = explode(',', $store_list);
            }

            if ($param['area_id'] && $param['area_id'] != 'undefined') {
                $where_store['area_id'] = $param['area_id'];
            } else if ($param['city_idss'] && $param['city_idss'] != 'undefined') {
                $where_store['city_id'] = $param['city_idss'];
            } else if ($param['province_idss'] && $param['province_idss'] != 'undefined') {
                $where_store['province_id'] = $param['province_idss'];
            }
            if ($where_store) {
                $store_ids = M('Merchant_store')->where($where_store)->select();

                foreach ($store_ids as $st) {
                    $store_list_id[] = $st['store_id'];
                }
                if (!$store_list_id) {
                    return array();
                }
            }           

            if($store_list_id1&&$store_list_id){
                // 店铺和地区都筛选
                $store_list_id = array_intersect($store_list_id,$store_list_id1);
                $where_pay_dining['store_id'] = $where['store_id'] = array('in', $store_list_id);
                $where_meal .= " AND  store_id  in (".implode(',',$store_list_id).") ";
            }elseif (!$where_store&&$store_list_id1) {
                // 只筛选店铺
                $where_pay_dining['store_id'] = $where['store_id'] = array('in', $store_list_id1);
                $where_meal .= " AND  store_id  in (".implode(',',$store_list_id1).") ";
            }elseif ($where_store){
                // 只筛选地区
                $where_pay_dining['store_id'] = $where['store_id'] = array('in', $store_list_id);
                $where_meal .= " AND  store_id  in (".implode(',',$store_list_id).") ";
            }

            if ($param['begin_time'] && $param['end_time']) {
                $start_time = strtotime($param['begin_time']);
                $end_time = strtotime($param['end_time']) + 86400;
                unset($_GET['selectTimeType']);
            }
            if ($type == 'shop' && $param['platform']) {
                $where['platform'] = $param['platform'];
            }

            if ($end_time < $start_time) {
                return array('error_code' => 1, 'msg' => L_('结束时间不能比开始时间小'));
            }
            $param['mer_id'] && $where['mer_id'] = $param['mer_id'];

            if ($type == 'meal') {
                $Order = 'Foodshop_order';
                $where_meal = ' 1 = 1 ';
                $param['mer_id'] && $where_meal .= ' AND o.mer_id=' . $param['mer_id'];
            } else {
                $where['paid'] = 1;
                $Order = ucfirst($type) . '_order';

                if($type == 'sport' || $type == 'scenic'){
                    $Order = 'life_tools_order';
                }
            }

            if ($mer_list && $mer_list != -1) {
                $where_pay_dining['mer_id'] = $where['mer_id'] = array('in', $mer_list);
                $where_meal .= " AND mer_id  in ({$mer_list}) ";
            }

            $where_staff = [];
            if ($staff_list && $staff_list != -1) {
                $where_pay_dining['staff_id'] = $where['staff_id'] = array('in', $staff_list);
                $where_meal .= " AND  staff_id  in ({$staff_list}) ";
                $where_staff['staff_id'] = ['in', $staff_list];
            }

            if ($pay_type != 'all') {
                if($type == 'appoint'){
                    if ($pay_type == 'balance') {
                        $where['pay_type'] = '';
                        $where['product_pay_type'] = '';
                    } elseif ($pay_type == 'alipay') {
                        $where['_string'] = " (pay_type like 'alipay%' OR product_pay_type like 'alipay%')";
                    } elseif ($pay_type == 'weixin_and_balance') { // 微信支付 + 余额支付
                        $where['pay_type'] = 'weixin';
                        $where['_string'] = " (pay_type='weixin' OR product_pay_type='weixin')";
                        $where = array_merge($where,['balance_pay|merchant_balance'=>['gt',0]]);
                    } elseif ($pay_type == 'alipay_and_balance') { // 支付宝支付 + 余额支付
                        $where['_string'] = " (pay_type like 'alipay%' OR product_pay_type like 'alipay%')";
                        $where = array_merge($where,['balance_pay|merchant_balance'=>['gt',0]]);
                    } else {
                        $where['_string'] = " (pay_type='".$param['pay_type']."' OR product_pay_type='".$param['pay_type']."')";
                    }
                }else{
                    if ($pay_type == 'balance') {
                        $where['pay_type'] = '';
                        $where_pay = " ='' ";
                    } elseif ($pay_type == 'alipay') {
                        $where['pay_type'] = array('like', 'alipay%');
                        $where_pay = " like 'alipay%' ";
                    } elseif ($pay_type == 'weixin_and_balance') { // 微信支付 + 余额支付
                        $where['pay_type'] = 'weixin';
                        $where = array_merge($where,['balance_pay|merchant_balance'=>['gt',0]]);
                        $where_pay = " ='" . $where['pay_type'] . "'";
                    } elseif ($pay_type == 'alipay_and_balance') { // 支付宝支付 + 余额支付
                        $where['pay_type'] = array('like', 'alipay%');
                        $where = array_merge($where,['balance_pay|merchant_balance'=>['gt',0]]);
                        $where_pay = " like 'alipay%' ";
                    } else {
                        $where['pay_type'] = $param['pay_type'];
                        $where_pay = " ='" . $param['pay_type'] . "'";
                    }
                }
            }
            if ($start_time) {
                if($type == 'sport' || $type == 'scenic') {
                    $where['_string'] = "o.pay_time <= {$end_time} AND o.pay_time>={$start_time}  ";
                }elseif($type=='appoint'){
                    $where['_string'] = "order_time <= {$end_time} AND order_time>={$start_time}  ";
                    $where_meal .= " AND order_time <= {$end_time} AND order_time>={$start_time} ";
                }else{
                    $where_pay_dining['_string'] = $where['_string'] = "pay_time <= {$end_time} AND pay_time>={$start_time}  ";
                    $where_meal .= " AND pay_time <= {$end_time} AND pay_time>={$start_time} ";
                }
            }
            if ($pay_type != 'all') {
                if ($pay_type == 'balance') {
                    $where_pay_dining['pay_type'] = $where['pay_type'] = '';
                    $where_pay = " ='' ";
                } elseif ($pay_type == 'alipay') {
                    $where['pay_type'] = array('like', 'alipay%');
                    $where_pay_dining['pay_type'] = $where_pay = " like 'alipay%' ";
                } elseif ($pay_type == 'weixin_and_balance') { // 微信支付 + 余额支付
                    $where_pay_dining['pay_type'] = $where['pay_type'] = 'weixin';
                    $where = array_merge($where,['balance_pay|merchant_balance'=>['gt',0]]);                    
                    $where_pay_dining = array_merge($where_pay_dining,['system_balance|merchant_balance_pay|merchant_balance_give'=>['gt',0]]);
                    $where_pay = " ='" . $where['pay_type'] . "'";
                } elseif ($pay_type == 'alipay_and_balance') { // 支付宝支付 + 余额支付
                    $where_pay_dining['pay_type'] = $where['pay_type'] = array('like', 'alipay%');
                    $where = array_merge($where,['balance_pay|merchant_balance'=>['gt',0]]);
                    $where_pay_dining = array_merge($where_pay_dining,['system_balance|merchant_balance_pay|merchant_balance_give'=>['gt',0]]);
                    $where_pay = " like 'alipay%' ";
                } elseif ($pay_type == 'weixinapp') { // app微信支付
                    $where_pay_dining['pay_type'] = $where['pay_type'] = 'weixin';
                    $where_pay_dining['is_mobile_pay'] = $where['is_mobile_pay'] = 2;
                    $where_pay = " ='" . $param['pay_type'] . " AND is_mobile_pay = 2'";
                } else {
                    $where_pay_dining['pay_type'] = $where['pay_type'] = $param['pay_type'];
                    $where_pay = " ='" . $param['pay_type'] . "'";
                }

                if($type == 'dining'){// 新版餐饮支付方式查询
                    $where_pay_dining['paid'] = 1;
                    $dinng_order = array_column( M('Dining_order_pay')->where($where_pay_dining)->field('distinct(order_id)')->select(),'order_id');
                    $where['order_id'] = ['in', $dinng_order];
                }
            }
            
            //营业金额
            switch ($type) {
                case 'group':
                    $where['status'] = array('elt', 3);
                    $sum_name = 'total_money';
                    break;
                case 'sport':
                    unset($where['paid']);
                    $where['_string'] .= " AND o.paid = 2 AND (l.type='stadium' or l.type='course')";
                    $sum_name = 'o.price';
                    break;
                case 'scenic':
                    unset($where['paid']);
                    $where['_string'] .= " AND o.paid = 2 AND l.type='scenic'";
                    $sum_name = 'o.price';
                    break;
                case 'shop':
                    $where['status'] = array('NOT IN', '5,6');
                    $sum_name = 'price';
                    break; 
                case 'mall':
                    unset($where['paid']);
                    $where['_string'] .= " AND ((status >= 10 AND status < 50) OR (status >=60 and  status < 70))";
                    $sum_name = 'money_total';
                    if(!empty($where['pay_type'])){
                        $where['online_pay_type'] = $where['pay_type'];
                        unset($where['pay_type']);
                        if($param['pay_type'] == 'weixin'){
                            $where['online_pay_type'] = 'wechat';
                        }
                    }
                    break;
                case 'meal':
                    $where['status'] = array('in', '3,4');
                    $sum_name = 'price';
                    break;
                case 'dining':
                    $where['status'] = array('in', '4,20,30,40,41,50');
                    $sum_name = 'price';
                    break;
                case 'appoint':
                    unset($where['paid']);
                    if(isset($where['_string']) && ((strpos($where['_string'],'order_time') !== false) || (strpos($where['_string'],'pay_type') !== false))){
                        $where['_string'] .= ' AND (paid = 1 OR paid = 3)';
                    }else{
                        $where['_string'] .= ' (paid = 1 OR paid = 3)';
                    }
                    $sum_name = 'balance_pay+score_deducte+pay_money+merchant_balance+product_score_deducte
                    +product_balance_pay+product_coupon_price+user_pay_money+product_merchant_balance+card_price+coupon_price+product_card_price';
                    break;
                case 'store':
                    $sum_name = 'price';
                    if($where['_string']){
                        $where['_string'] .= ' AND ((business_id = 0 AND from_plat =1) OR from_plat in (2,3))';
                    }else{
                        $where['_string'] = '((business_id = 0 AND from_plat =1) OR from_plat in (2,3))';
                    }
                    break;
                case 'life_tools_appoint_join':
                    if($where_staff){
                        $appoint_order_ids = M('Life_tools_appoint_verify')->where($where_staff)->getField('appoint_order_id', true);
                        $where['pigcms_id'] = ['in', $appoint_order_ids];
                    }
                    $sum_name = 'price';
                    $where['status'] = array('in', [1,3,4]);
                    break;
            }
            
            if ($type == 'meal') {
                if ($param['mer_id']) {
                    $store_ids = M('Merchant_store')->where(array('mer_id' => $param['mer_id']))->select();

                    foreach ($store_ids as $st) {
                        $store_list_id[] = $st['store_id'];
                    }
                    if ($store_list_id) {
                        $where_plat['store_id'] = implode(',', $store_list_id);
                        $where_plat['store_id'] = " AND o.store_id in (" . $where_plat['store_id'] . ") ";
                    }
                }
                $where_tm = $where;
                $where['pay_type'] && $where_tm['p.pay_type'] = $where['pay_type'];
                unset($where_tm['pay_type']);
                $counts = M('')->query("SELECT count(t.order_id) as count_ FROM ( SELECT o.order_id,o.store_id, o.pay_time, o.status, o.total_price, o.price, o.book_price, ( CASE WHEN o.pay_type = 0 THEN ( SELECT pay_type FROM pigcms_plat_order p WHERE p.business_type = 'foodshop' AND p.business_id = o.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) WHEN o.pay_type = 1 THEN ( SELECT pay_type FROM pigcms_store_order s WHERE s.business_type = 'foodshop' AND s.business_id = s.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) END ) AS pay_types FROM pigcms_foodshop_order AS o where " . $where_meal . " AND (o.status IN (3, 4) OR (o.status=5 AND o.pay_time>0) ) ) t " . ($where_pay ? 'WHERE t.pay_types ' . $where_pay : ''));

                // 营业总订单数
                $total_money_count = $counts[0]['count_'];


                $price = M('')->query("SELECT sum(t.total_price) as price_ FROM ( SELECT o.order_id,o.store_id, o.pay_time, o.status, o.total_price, o.price, o.book_price, ( CASE WHEN o.pay_type = 0 THEN ( SELECT pay_type FROM pigcms_plat_order p WHERE p.business_type = 'foodshop' AND p.business_id = o.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) WHEN o.pay_type = 1 THEN ( SELECT pay_type FROM pigcms_store_order s WHERE s.business_type = 'foodshop' AND s.business_id = s.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) END ) AS pay_types FROM pigcms_foodshop_order AS o where " . $where_meal . " AND (o.status IN (3, 4) OR (o.status=5 AND o.pay_time>0) ) ) t " . ($where_pay ? 'WHERE t.pay_types ' . $where_pay : ''));
                // 营业总额
                $total_money_sum = $price[0]['price_'];

            } else {
                if($type == 'sport' || $type == 'scenic'){
                    $total_money_count = M($Order)->
                        join('AS o LEFT JOIN '.C('DB_PREFIX') .'life_tools AS l on o.tools_id=l.tools_id')
                        ->where($where)->count();
                    $total_money_sum = M($Order)->
                    join('AS o LEFT JOIN '.C('DB_PREFIX') .'life_tools AS l on o.tools_id=l.tools_id ')
                        ->where($where)->sum($sum_name);
                }else{
                    // 营业总订单数
                    $total_money_count = M($Order)->where($where)->count();
                    // 营业总额
                    $total_money_sum = M($Order)->where($where)->sum($sum_name);
                }
            }
            if ($type == 'meal') {
                $subQuery = M($Order)->field('store_id')->join('AS o LEFT JOIN ( SELECT pay_type ,business_id,business_type,total_money FROM ' . C('DB_PREFIX') . 'plat_order where business_type="foodshop"  ' . $where_plat['store_id'] . ' AS p on p.business_id=o.order_id ')->where($where_tm)->group("FROM_UNIXTIME(pay_time, '%Y-%m-%d'),store_id")->buildSql();

                $counts = M('')->query("select count(m.order_id_) as count_ from (SELECT t.order_id as order_id_ FROM ( SELECT o.order_id,o.store_id, o.pay_time, o.status, o.total_price, o.price, o.book_price, ( CASE WHEN o.pay_type = 0 THEN ( SELECT pay_type FROM pigcms_plat_order p WHERE p.business_type = 'foodshop' AND p.business_id = o.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) WHEN o.pay_type = 1 THEN ( SELECT pay_type FROM pigcms_store_order s WHERE s.business_type = 'foodshop' AND s.business_id = s.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) END ) AS pay_types FROM pigcms_foodshop_order AS o where " . $where_meal . " AND o.status IN (3, 4) ) t " . ($where_pay ? 'WHERE t.pay_types ' . $where_pay : '') . " group by FROM_UNIXTIME(t.pay_time, '%Y-%m-%d'),t.store_id) as m");

                $count = $counts[0]['count_'];
            } else {
                if($type == 'sport' || $type == 'scenic') {
                    $subQuery = M($Order)->field('order_id')
                        ->join('AS o LEFT JOIN '.C('DB_PREFIX') .'life_tools AS l on o.tools_id=l.tools_id')->where($where)
                        ->group("FROM_UNIXTIME(o.pay_time, '%Y-%m-%d'),o.mer_id")->buildSql();
                }elseif($type=='appoint'){
                    $subQuery = M($Order)->field('store_id')->where($where)->group("FROM_UNIXTIME(order_time, '%Y-%m-%d'),store_id")->buildSql();
                }else{
                    $subQuery = M($Order)->field('store_id')->where($where)->group("FROM_UNIXTIME(pay_time, '%Y-%m-%d'),store_id")->buildSql();
                }
                $count = M()->table($subQuery . ' a')->count();
            }

            if ($is_system) {
                import('@.ORG.system_page');
            } else {
                import('@.ORG.merchant_page');
            }

            // 获得所有页数
            if ($type == 'meal') {
                $total_money_sum_by_store_all = M('')->query("select * from (SELECT t.store_id,t.mer_id,count(t.order_id) as counts ,sum(t.total_price) as income,FROM_UNIXTIME(t.pay_time, '%Y-%m-%d') as date_id FROM ( SELECT o.order_id,o.mer_id,o.store_id, o.pay_time, o.status, o.total_price, o.price, o.book_price, ( CASE WHEN o.pay_type = 0 THEN ( SELECT pay_type FROM pigcms_plat_order p WHERE p.business_type = 'foodshop' AND p.business_id = o.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) WHEN o.pay_type = 1 THEN ( SELECT pay_type FROM pigcms_store_order s WHERE s.business_type = 'foodshop' AND s.business_id = s.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) END ) AS pay_types FROM pigcms_foodshop_order AS o where " . $where_meal . " AND o.status in (3,4)) t " . ($where_pay ? 'WHERE t.pay_types ' . $where_pay : '') . " group by FROM_UNIXTIME(t.pay_time, '%Y-%m-%d'),t.store_id) as m order by date_id DESC");
            } else {
                if($type == 'sport' || $type == 'scenic') {
                    $total_money_sum_by_store_all = M($Order)->field("FROM_UNIXTIME(o.pay_time, '%Y-%m-%d') as date_id,o.order_id,o.mer_id,count(o.order_id) as counts")
                        ->join('AS o LEFT JOIN ' . C('DB_PREFIX') . 'life_tools AS l on o.tools_id=l.tools_id')->where($where)->group("FROM_UNIXTIME(o.pay_time, '%Y-%m-%d'),o.mer_id")
                        ->order("FROM_UNIXTIME(o.pay_time, '%Y-%m-%d') DESC,o.mer_id DESC")
                        ->select();
                }elseif($type=='appoint') {
                    $total_money_sum_by_store_all = M($Order)->field("FROM_UNIXTIME(order_time, '%Y-%m-%d') as date_id,store_id,mer_id,count(order_id) as counts")
                        ->where($where)->group("FROM_UNIXTIME(order_time, '%Y-%m-%d'),store_id")
                        ->order("FROM_UNIXTIME(order_time, '%Y-%m-%d') DESC,store_id DESC")
                        ->select();
                }else{
                    $total_money_sum_by_store_all = M($Order)->field("FROM_UNIXTIME(pay_time, '%Y-%m-%d') as date_id,store_id,mer_id,count(order_id) as counts")
                        ->where($where)->group("FROM_UNIXTIME(pay_time, '%Y-%m-%d'),store_id")
                        ->order("FROM_UNIXTIME(pay_time, '%Y-%m-%d') DESC,store_id DESC")
                        ->select();
                }
            }
            foreach ($total_money_sum_by_store_all as $v) {
                $date_store_a = $v['date_id'].'-'.$v['store_id'];
                if (!in_array($date_store_a,$date_arr_all)) {
                    $total_count_all++;
                    $date_arr_all[] = $date_store_a;
                }
            }

            // 用于计算页数
            $total_count = $count > $total_count ? $count : $total_count;
            $p = new Page($count, 10, true);
            if ($type == 'meal') {
                $total_money_sum_by_store = M('')->query("select * from (SELECT t.store_id,t.mer_id,count(t.order_id) as counts ,sum(t.total_price) as income,FROM_UNIXTIME(t.pay_time, '%Y-%m-%d') as date_id FROM ( SELECT o.order_id,o.mer_id,o.store_id, o.pay_time, o.status, o.total_price, o.price, o.book_price, ( CASE WHEN o.pay_type = 0 THEN ( SELECT pay_type FROM pigcms_plat_order p WHERE p.business_type = 'foodshop' AND p.business_id = o.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) WHEN o.pay_type = 1 THEN ( SELECT pay_type FROM pigcms_store_order s WHERE s.business_type = 'foodshop' AND s.business_id = s.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) END ) AS pay_types FROM pigcms_foodshop_order AS o where " . $where_meal . " AND o.status in (3,4)) t " . ($where_pay ? 'WHERE t.pay_types ' . $where_pay : '') . " group by FROM_UNIXTIME(t.pay_time, '%Y-%m-%d'),t.store_id) as m order by date_id DESC  limit {$p->firstRow},{$p->listRows}");
            } else {
                if($type == 'sport' || $type == 'scenic') {
                    $total_money_sum_by_store = M($Order)
                        ->field("FROM_UNIXTIME(o.pay_time, '%Y-%m-%d') as date_id,o.order_id,o.mer_id,sum({$sum_name}) as income,count(o.order_id) as counts")
                        ->join('AS o LEFT JOIN ' . C('DB_PREFIX') . 'life_tools AS l on o.tools_id=l.tools_id')
                        ->where($where)->group("FROM_UNIXTIME(o.pay_time, '%Y-%m-%d'),o.mer_id")
                        ->order("FROM_UNIXTIME(o.pay_time, '%Y-%m-%d') DESC,o.mer_id DESC")
                        ->limit($p->firstRow, $p->listRows)->select();
                }elseif($type=='appoint') {
                    $total_money_sum_by_store = M($Order)->field("FROM_UNIXTIME(order_time, '%Y-%m-%d') as date_id,store_id,mer_id,sum({$sum_name}) as income,count(order_id) as counts")
                        ->where($where)->group("FROM_UNIXTIME(order_time, '%Y-%m-%d'),store_id")
                        ->order("FROM_UNIXTIME(order_time, '%Y-%m-%d') DESC,store_id DESC")
                        ->limit($p->firstRow, $p->listRows)->select();
                }elseif($type=='life_tools_appoint_join') {
                    $total_money_sum_by_store = M($Order)->field("FROM_UNIXTIME(add_time, '%Y-%m-%d') as date_id,0 as store_id,mer_id,sum({$sum_name}) as income,count(pigcms_id) as counts")
                        ->where($where)->group("FROM_UNIXTIME(add_time, '%Y-%m-%d')")
                        ->order("FROM_UNIXTIME(add_time, '%Y-%m-%d') DESC")
                        ->limit($p->firstRow, $p->listRows)->select();
                }else{
                    $total_money_sum_by_store = M($Order)->field("FROM_UNIXTIME(pay_time, '%Y-%m-%d') as date_id,store_id,mer_id,sum({$sum_name}) as income,count(order_id) as counts")
                        ->where($where)->group("FROM_UNIXTIME(pay_time, '%Y-%m-%d'),store_id")
                        ->order("FROM_UNIXTIME(pay_time, '%Y-%m-%d') DESC,store_id DESC")
                        ->limit($p->firstRow, $p->listRows)->select();
                }
            }

            //实收金额
            $where_ = $where;
            unset($where_['status']);
            empty($where_['_string']) && $where_['_string'] = ' 1=1 ';
            switch ($type) {
                case 'group':
                    $where_['_string'] .= ' AND  (status = 1 OR status = 2) ';
                    break;
                case 'shop':
                    $where_['_string'] .= ' AND status in (2,3,7,8,9,10) ';
                    break;
                case 'mall':
                    $sum_name = 'money_real';
                    $where_['_string'] .= ' AND status in (30,31,32,33,40) ';
                    break;
                case 'meal':
                    $where_['_string'] .= ' AND (status = 3 OR status = 4) ';
                    break;
                case 'dining':
                    $where_['_string'] .= ' AND (status = 40 OR status = 41) ';
                    break;
                case 'appoint':
                    $where_['_string'] .= ' AND service_status>0 ';
                    break;
                case 'store':
		    $where_['_string'] .= ' AND refund = 0 ';
                    break;
                case 'sport':
                case 'scenic':
                    $where['_string'] .= " AND o.order_status>10 AND o.order_status<=40";
                    break;
                case 'life_tools_appoint_join':
                    $where_['_string'] .= ' AND status in (3, 4) ';
                    break;
            }

            if ($type == 'meal') {
                $where_t = $where_;
                isset($where['pay_type']) && $where_t['p.pay_type'] = $where['pay_type'];
                unset($where_t['pay_type']);
                $counts = M('')->query("SELECT count(t.order_id) as count_ FROM ( SELECT o.order_id,o.store_id, o.pay_time, o.status, o.total_price, o.price, o.book_price, ( CASE WHEN o.pay_type = 0 THEN ( SELECT pay_type FROM pigcms_plat_order p WHERE p.business_type = 'foodshop' AND p.business_id = o.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) WHEN o.pay_type = 1 THEN ( SELECT pay_type FROM pigcms_store_order s WHERE s.business_type = 'foodshop' AND s.business_id = s.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) END ) AS pay_types FROM pigcms_foodshop_order AS o where " . $where_meal . " AND o.status IN (3, 4) ) t " . ($where_pay ? 'WHERE t.pay_types ' . $where_pay : ''));
                $consume_money_count = $counts[0]['count_'];

                $price = M('')->query("SELECT sum(t.price) as price_ FROM ( SELECT o.order_id,o.store_id, o.pay_time, o.status, o.total_price, o.price, o.book_price, ( CASE WHEN o.pay_type = 0 THEN ( SELECT pay_type FROM pigcms_plat_order p WHERE p.business_type = 'foodshop' AND p.business_id = o.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) WHEN o.pay_type = 1 THEN ( SELECT pay_type FROM pigcms_store_order s WHERE s.business_type = 'foodshop' AND s.business_id = s.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) END ) AS pay_types FROM pigcms_foodshop_order AS o where " . $where_meal . " AND o.status IN (3, 4) ) t " . ($where_pay ? 'WHERE t.pay_types ' . $where_pay : ''));

                $consume_money_sum = $price[0]['price_'];

                $consume_money_count_store = M('')->query("select * from (SELECT t.store_id,t.mer_id,count(t.order_id) as consume_count ,sum(price) as consume_money,FROM_UNIXTIME(t.pay_time, '%Y-%m-%d') as date_id FROM ( SELECT o.order_id,o.mer_id,o.store_id, o.pay_time, o.status, o.total_price, o.price, o.book_price, ( CASE WHEN o.pay_type = 0 THEN ( SELECT pay_type FROM pigcms_plat_order p WHERE p.business_type = 'foodshop' AND p.business_id = o.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) WHEN o.pay_type = 1 THEN ( SELECT pay_type FROM pigcms_store_order s WHERE s.business_type = 'foodshop' AND s.business_id = s.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) END ) AS pay_types FROM pigcms_foodshop_order AS o where " . $where_meal . " AND o.status in (3,4)) t " . ($where_pay ? 'WHERE t.pay_types ' . $where_pay : '') . " group by FROM_UNIXTIME(t.pay_time, '%Y-%m-%d'),t.store_id) as m order by date_id DESC  limit {$p->firstRow},{$p->listRows}");

            } else {
                if($type == 'sport' || $type == 'scenic') {
                    $consume_money_count = M($Order)->
                    join('AS o LEFT JOIN '.C('DB_PREFIX') .'life_tools AS l on o.tools_id=l.tools_id')
                        ->where($where)->count();
                    $consume_money_sum = M($Order)->
                    join('AS o LEFT JOIN '.C('DB_PREFIX') .'life_tools AS l on o.tools_id=l.tools_id ')
                        ->where($where)->sum($sum_name);
                    $consume_money_count_store = M($Order)->where($where_)
                        ->join('AS o LEFT JOIN '.C('DB_PREFIX') .'life_tools AS l on o.tools_id=l.tools_id')
                        ->group("FROM_UNIXTIME(o.pay_time, '%Y-%m-%d'),o.mer_id")
                        ->field("FROM_UNIXTIME(o.pay_time, '%Y-%m-%d') as date_id ,o.mer_id,count(o.order_id) as consume_count,sum({$sum_name}) as consume_money")->select();

                }else if($type == 'life_tools_appoint_join'){

                    $consume_money_count = M($Order)->where($where_)->count();

                    $consume_money_sum = M($Order)->where($where_)->sum($sum_name);
                    $consume_money_count_store = M($Order)->where($where_)
                        ->group("FROM_UNIXTIME(pay_time, '%Y-%m-%d'),mer_id")
                        ->field("FROM_UNIXTIME(pay_time, '%Y-%m-%d') as date_id ,mer_id,count(pigcms_id) as consume_count,sum({$sum_name}) as consume_money")->select();
    
                }else{

                    $consume_money_count = M($Order)->where($where_)->count();

                    $consume_money_sum = M($Order)->where($where_)->sum($sum_name);
                    if($type=='appoint') {
                        $consume_money_count_store = M($Order)->where($where_)->group("FROM_UNIXTIME(order_time, '%Y-%m-%d'),store_id")->field("FROM_UNIXTIME(order_time, '%Y-%m-%d') as date_id ,store_id,count(order_id) as consume_count,sum({$sum_name}) as consume_money")->select();
                    }else{
                        $consume_money_count_store = M($Order)->where($where_)->group("FROM_UNIXTIME(pay_time, '%Y-%m-%d'),store_id")->field("FROM_UNIXTIME(pay_time, '%Y-%m-%d') as date_id ,store_id,count(order_id) as consume_count,sum({$sum_name}) as consume_money")->select();
                    }
                }
            }
            $cosumen_count_tmp = array();
            $cosumen_money_tmp = array();
            foreach ($consume_money_count_store as $v) {
                if(in_array($type, ['sport', 'scenic', 'life_tools_appoint_join'])) {
                    $cosumen_count_tmp[$v['date_id']][$v['mer_id']] = $v['consume_count'];
                    $cosumen_money_tmp[$v['date_id']][$v['mer_id']] = $v['consume_money'];
                }else{
                    $cosumen_count_tmp[$v['date_id']][$v['store_id']] = $v['consume_count'];
                    $cosumen_money_tmp[$v['date_id']][$v['store_id']] = $v['consume_money'];
                }
            }
            // dump($cosumen_count_tmp);die;
            //退款金额
            switch ($type) {
                case 'group':
                    $where['status'] = 3;
                    break;
                case 'shop':
                    $where['status'] = ['in','4,5'];
                    break;
                case 'mall':
                    $where['_string'] = str_replace(' AND ((status >= 10 AND status < 50) OR (status >=60 and  status < 70))', '', $where['_string'] );
                    $where['status'] = 70;
                    $sum_name = 'money_refund';
                    break;
                case 'meal':
                    $where['status'] = 5;
                    break;
                case 'dining':
                    $where['status'] = ['in', '50,51'];
                    $sum_name = 'refund_money';
                    break;
                case 'appoint':
                    $where['paid'] = 2;
                    break;
                case 'store':
                    $where['refund'] = 1;
                    break;
                case 'sport':
                case 'scenic':
                    $where['_string']=str_replace(" AND o.order_status>10 AND o.order_status<=40","",$where['_string']);
                    $where['o.order_status'] = 50;
                    break;
                case 'life_tools_appoint_join':
                    $sum_name = 'price';
                    $where['status'] = 5;
                    break;
            }

            if ($type == 'meal') {
                $counts = M('')->query("SELECT count(t.order_id) as count_ FROM ( SELECT o.order_id,o.store_id, o.pay_time, o.status, o.total_price, o.price, o.book_price, ( CASE WHEN o.pay_type = 0 THEN ( SELECT pay_type FROM pigcms_plat_order p WHERE p.business_type = 'foodshop' AND p.business_id = o.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) WHEN o.pay_type = 1 THEN ( SELECT pay_type FROM pigcms_store_order s WHERE s.business_type = 'foodshop' AND s.business_id = s.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) END ) AS pay_types FROM pigcms_foodshop_order AS o where " . $where_meal . " AND o.status =5 AND o.pay_time>0) t " . ($where_pay ? 'WHERE t.pay_types ' . $where_pay : ''));

                $refund_money_count = $counts[0]['count_'];

                $price = M('')->query("SELECT sum(t.price) as price_ FROM ( SELECT o.order_id,o.store_id, o.pay_time, o.status, o.total_price, o.price, o.book_price, ( CASE WHEN o.pay_type = 0 THEN ( SELECT pay_type FROM pigcms_plat_order p WHERE p.business_type = 'foodshop' AND p.business_id = o.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) WHEN o.pay_type = 1 THEN ( SELECT pay_type FROM pigcms_store_order s WHERE s.business_type = 'foodshop' AND s.business_id = s.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) END ) AS pay_types FROM pigcms_foodshop_order AS o where " . $where_meal . " AND o.status=5 AND o.pay_time>0) t " . ($where_pay ? 'WHERE t.pay_types ' . $where_pay : ''));

                $refund_money_sum = $price[0]['price_'];

                $refund_money_count_store = M('')->query("select * from (SELECT t.store_id,t.mer_id,count(t.order_id) as refund_count ,sum(t.price) as refund_money,FROM_UNIXTIME(t.pay_time, '%Y-%m-%d') as date_id FROM ( SELECT o.order_id,o.mer_id,o.store_id, o.pay_time, o.status, o.total_price, o.price, o.book_price, ( CASE WHEN o.pay_type = 0 THEN ( SELECT pay_type FROM pigcms_plat_order p WHERE p.business_type = 'foodshop' AND p.business_id = o.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) WHEN o.pay_type = 1 THEN ( SELECT pay_type FROM pigcms_store_order s WHERE s.business_type = 'foodshop' AND s.business_id = s.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) END ) AS pay_types FROM pigcms_foodshop_order AS o where " . $where_meal . " AND o.status=5 AND o.pay_time>0) t " . ($where_pay ? 'WHERE t.pay_types ' . $where_pay : '') . " group by FROM_UNIXTIME(t.pay_time, '%Y-%m-%d'),t.store_id) as m order by date_id DESC");

            } else {
                if($type == 'sport' || $type == 'scenic') {
                    $refund_money_count = M($Order)->
                    join('AS o LEFT JOIN '.C('DB_PREFIX') .'life_tools AS l on o.tools_id=l.tools_id')
                        ->where($where)->count();
                    $refund_money_sum = M($Order)->
                    join('AS o LEFT JOIN '.C('DB_PREFIX') .'life_tools AS l on o.tools_id=l.tools_id ')
                        ->where($where)->sum($sum_name);
                    $refund_money_count_store = M($Order)->where($where)
                        ->join('AS o LEFT JOIN '.C('DB_PREFIX') .'life_tools AS l on o.tools_id=l.tools_id ')
                        ->group("FROM_UNIXTIME(o.pay_time, '%Y-%m-%d'),o.mer_id")
                        ->field("FROM_UNIXTIME(o.pay_time, '%Y-%m-%d') as date_id,o.mer_id,count(o.order_id) as refund_count,sum({$sum_name}) as refund_money")->select();

                }else if($type == 'life_tools_appoint_join'){

                    $refund_money_count = M($Order)->where($where)->count();
                    $refund_money_sum = M($Order)->where($where)->sum($sum_name);
                    
                    $refund_money_count_store = M($Order)->where($where)
                        ->group("FROM_UNIXTIME(pay_time, '%Y-%m-%d'),mer_id")
                        ->field("FROM_UNIXTIME(pay_time, '%Y-%m-%d') as date_id,mer_id,count(pigcms_id) as refund_count,sum({$sum_name}) as refund_money")->select();

                }else{
                    $refund_money_count = M($Order)->where($where)->count();
                    $refund_money_sum = M($Order)->where($where)->sum($sum_name);
                    if($type=='appoint') {
                        $refund_money_count_store = M($Order)->where($where)->group("FROM_UNIXTIME(order_time, '%Y-%m-%d'),store_id")->field("FROM_UNIXTIME(order_time, '%Y-%m-%d') as date_id,store_id,count(order_id) as refund_count,sum({$sum_name}) as refund_money")->select();
                    }else{
                        $refund_money_count_store = M($Order)->where($where)->group("FROM_UNIXTIME(pay_time, '%Y-%m-%d'),store_id")->field("FROM_UNIXTIME(pay_time, '%Y-%m-%d') as date_id,store_id,count(order_id) as refund_count,sum({$sum_name}) as refund_money")->select();
                    }
                }
            }
            $refund_count_tmp = array();
            $refund_money_tmp = array();
            foreach ($refund_money_count_store as $v) {
                if(in_array($type, ['sport', 'scenic', 'life_tools_appoint_join'])) {
                    $refund_count_tmp[$v['date_id']][$v['mer_id']] = $v['refund_count'];
                    $refund_money_tmp[$v['date_id']][$v['mer_id']] = $v['refund_money'];
                }else{
                    $refund_count_tmp[$v['date_id']][$v['store_id']] = $v['refund_count'];
                    $refund_money_tmp[$v['date_id']][$v['store_id']] = $v['refund_money'];
                }
            }

            //线下支付
            $merchant_arr = array();
            $store_arr = array();
            foreach ($total_money_sum_by_store as $v) {

                $tmp['store_id'] = $v['store_id'];
                $tmp['mer_id'] = $v['mer_id'];
                if (empty($merchant_arr[$v['mer_id']])) {
                    $merchant_arr[$v['mer_id']] = $merchant = M('Merchant')->field('mer_id,name')->where(array('mer_id' => $v['mer_id']))->find();
                } else {
                    $merchant = $merchant_arr[$v['mer_id']];
                }

                if (empty($store_arr[$v['store_id']])) {
                    $store_arr[$v['store_id']] = $store = M('Merchant_store')->field('store_id,name')->where(array('store_id' => $v['store_id']))->find();
                } else {
                    $store = $store_arr[$v['store_id']];
                }
                $tmp['merchant_name'] = ($type=='group'&&!$merchant['name'])?'团购组合套餐':$merchant['name'];
                $tmp['store_name'] = $store['name'] ?? '-';
                $tmp['total_money'] = round($v['income'], 2);

                $tmp['date_id'] = $v['date_id'];
                if(in_array($type, ['sport', 'scenic', 'life_tools_appoint_join'])) {
                    $tmp['consume_money'] = round($cosumen_money_tmp[$v['date_id']][$v['mer_id']], 2);
                    $tmp['consume_count'] = $cosumen_count_tmp[$v['date_id']][$v['mer_id']];
                    $tmp['refund_money'] = round($refund_money_tmp[$v['date_id']][$v['mer_id']], 2);
                    $tmp['refund_count'] = $refund_count_tmp[$v['date_id']][$v['mer_id']];
                    $date_store = $v['date_id'].'-'.$v['mer_id'];
                }else{
                    $tmp['consume_money'] = round($cosumen_money_tmp[$v['date_id']][$v['store_id']], 2);
                    $tmp['consume_count'] = $cosumen_count_tmp[$v['date_id']][$v['store_id']];
                    $tmp['refund_money'] = round($refund_money_tmp[$v['date_id']][$v['store_id']], 2);
                    $tmp['refund_count'] = $refund_count_tmp[$v['date_id']][$v['store_id']];
                    $date_store = $v['date_id'].'-'.$v['store_id'];
                }
                $tmp['total_count'] = $v['counts'];
                if (in_array($date_store,$date_arr)) {
                    // 已存在 相加
                    $tmp['consume_money'] += floatval($arr['sale_date'][$date_store]['consume_money']);
                    $tmp['refund_money'] += floatval($arr['sale_date'][$date_store]['refund_money']);
                    $tmp['total_money'] += floatval($arr['sale_date'][$date_store]['total_money']);
                    $tmp['total_count'] += intval($arr['sale_date'][$date_store]['total_count']);
                    $tmp['consume_count'] += intval($arr['sale_date'][$date_store]['consume_count']);
                    $tmp['refund_count'] += intval($arr['sale_date'][$date_store]['refund_count']);
                    $tmp['total_money'] = round($tmp['total_money'], 2);
                    $tmp['consume_money'] = round($tmp['consume_money'], 2);
                    $tmp['refund_money'] = round($tmp['refund_money'], 2);

                    $arr['sale_date'][$date_store] = $tmp;
                }else{
                    $date_arr[] = $date_store;
                    $arr['sale_date'][$date_store] = $tmp;
                }
            }

            $p = new Page($total_count, 10, false, $total_count_all);
            $arr['pagebar'] = $p->show();

            $arr['consume_money_sum'] += round(floatval($consume_money_sum), 2);
            $arr['refund_money_sum'] += round(floatval($refund_money_sum), 2);
            $arr['total_money_sum'] += round(floatval($total_money_sum), 2);

            $arr['consume_money_count'] += intval($consume_money_count);
            $arr['refund_money_count'] += intval($refund_money_count);
            $arr['total_money_count'] += intval($total_money_count);
        }

        $saleSort = [];
        foreach ($arr['sale_date'] as $value) {
            $saleSort[] = strtotime($value['date_id']);
        }
        array_multisort($saleSort, SORT_DESC, SORT_NUMERIC, $arr['sale_date']);
        $arr['sale_date'] = array_slice($arr['sale_date'],0,10);
        return $arr;
    }

    public function goods_date($is_system = true, $param)
    {
        $mer_list = $param['mer_list'];
        $store_list = $param['store_list'];
        $type = $param['type'];
        if ($type == 'offline_shop') {
            $type = 'shop';
            $where['_string'] = "o.order_from = 6 AND ";
        } elseif ($type == 'only_shop') {
            $type = 'shop';
            $where['_string'] = "o.order_from != 6 AND ";
        }
        //$pay_type = $param['pay_type'];

        $goods_name = $param['goods_name'];
        if ($param['selectTimeType'] > 0) {
            $start_time = strtotime(date('Y-m-d', time() - ($param['selectTimeType'] - 1) * 86400));
            $end_time = time();;
        }elseif ($param['selectTimeType'] == -1) {
            // 昨日
            $start_time = strtotime('yesterday');
            $end_time = $start_time + 86400;
        }

        if ($param['begin_time'] > 0) {
            $start_time = strtotime($param['begin_time']);
            $end_time = strtotime($param['end_time']) + 86400;
        }

        if ($param['area_id'] && $param['area_id'] != 'undefined') {
            $where_store['area_id'] = $param['area_id'];
        } else if ($param['city_idss'] && $param['city_idss'] != 'undefined') {
            $where_store['city_id'] = $param['city_idss'];
        } else if ($param['province_idss'] && $param['province_idss'] != 'undefined') {
            $where_store['province_id'] = $param['province_idss'];
        }
        if ($where_store) {
            $store_ids = M('Merchant_store')->where($where_store)->select();
            foreach ($store_ids as $st) {
                $store_list_id[] = $st['store_id'];
            }
            if ($store_list_id) {
                $where['store_id'] = array('in', $store_list_id);
            } else {
                return array();
            }
        }

        if ($end_time < $start_time) {
            return array('error_code' => 1, 'msg' => L_('结束时间不能比开始时间小'));
        }
        $param['mer_id'] && $where['mer_id'] = $param['mer_id'];

        if ($mer_list && $mer_list != -1) {
            $where['mer_id'] = array('in', $mer_list);
        }

        if ($store_list && $store_list != -1) {
            $where['store_id'] = array('in', $store_list);
        }

        if ($param['type_list'] && $param['type_list'] != -1) {
            $where['sort_id'] = array('in', $param['type_list']);
        }

        if ($goods_name) {
            $where['name'] = array('like', "%" . $goods_name . "%");
        }

        if ($start_time) {
            $where['_string'] .= "o.create_time <= {$end_time} AND o.create_time>={$start_time}";
        }

        if ($is_system==1) {
            import('@.ORG.system_page');
        }elseif ($is_system==2) {
            // wap端
            $page = isset($param['page']) ? intval($param['page']) : 1;
            $pagesize = 15;
        } else {
            // 商家PC
            import('@.ORG.merchant_page');
        }

        // 排序
        if ($param['order_sort']) {
            switch ($param['order_sort']) {
                case 'sale_money':
                    // 销量額
                    $order_sort = 'sale_money DESC';
                    break;
                case 'sale_count':
                    // 销量
                    $order_sort = 'sale_count DESC';
                    break;
                case 'sale_percent':
                    // 销量占比
                    $order_sort = 'sale_percent DESC';
                    break;
                case 'is_goods':
                    // 赞
                    $order_sort = 'is_goods DESC';
                    break;
            }
        }else{
            $order_sort = 'sale_money DESC';
        }

        $type = $type == 'foodshop' ? 'meal' : $type;
        if ($type == 'meal') {
            $where_tmp = array();
            foreach ($where as $key => &$v) {
                if ($key != '_string') {
                    if ($key != 'mer_id') {
                        $tmp['d.' . $key] = $v;
                    } else {
                        $tmp['o.' . $key] = $v;
                    }
                } else {
                    $tmp['_string'] = $v;
                }
                $where_tmp[] = $tmp;
                unset($where[$key]);
            }
            $where = $where_tmp;
            $where['o.status'] = array('in', '1,2,3,4');
            $all_sale_money = floatval(M('Foodshop_order_detail')->join('as d LEFT JOIN ' . C('DB_PREFIX') . 'foodshop_order AS o ON d.order_id = o.order_id ')->where($where)->sum('d.price*d.num'));
            $all_sale_num = floatval(M('Foodshop_order_detail')->join('as d LEFT JOIN ' . C('DB_PREFIX') . 'foodshop_order AS o ON d.order_id = o.order_id ')->where($where)->sum('d.num'));

            //$count = M('Foodshop_order_detail')->where($where)->group('goods_id')->count();
            $subQuery = M('Foodshop_order_detail')->field('goods_id')->join('as d LEFT JOIN ' . C('DB_PREFIX') . 'foodshop_order AS o ON d.order_id = o.order_id ')->group('d.goods_id')->where($where)->buildSql();
            $count = M()->table($subQuery . ' a')->count();

            if ($param['sort'] && $count > $param['sort']) {
                $count = $param['sort'];
            }

            if ($is_system==2) {//wap端
                $totalPage = ceil($count / $pagesize);
                $start = $pagesize * ($page - 1);
                $arr['has_more'] = $totalPage > $page ? true : false;
            }else{
                $p = new Page($count, 15);
                $start = $p->firstRow;
                $pagesize = $p->listRows;
            }

            if ($param['sort']) {
                if ($param['sort'] > 15 && intval($param['sort'] / 15) == $param['page'] - 1) {
                    $p->listRows = $param['sort'] - (($param['page'] - 1) * 15);
                } else if ($param['sort'] < 15) {
                    $p->listRows = $param['sort'];
                }
            }
            if ($all_sale_money <= 0) {
                $arr['goods_date'] = M('Foodshop_order_detail')->join('as d LEFT JOIN ' . C('DB_PREFIX') . 'foodshop_order AS o ON d.order_id = o.order_id ')->where($where)->group('d.goods_id,d.spec')->order($order_sort)->field("d.goods_id,d.spec,d.name,d.price,d.store_id,d.number,sum(d.price * d.num) as sale_money ,sum(d.num) as sale_count,concat ( left (sum(d.price*d.num)/{$all_sale_num} *100,5),'%') as sale_percent")->limit($start, $pagesize)->select();
            } else {
                $arr['goods_date'] = M('Foodshop_order_detail')->join('as d LEFT JOIN ' . C('DB_PREFIX') . 'foodshop_order AS o ON d.order_id = o.order_id ')->where($where)->group('d.goods_id,d.spec')->order($order_sort)->field("d.goods_id,d.spec,d.name,d.price,d.store_id,d.number,sum(d.price * d.num) as sale_money ,sum(d.num) as sale_count,concat ( left (sum(d.price*d.num)/{$all_sale_money} *100,5),'%') as sale_percent")->limit($start, $pagesize)->select();
            }
        } elseif($type == 'dining') {
            $where_tmp = array();
            foreach ($where as $key => &$v) {
                if ($key != '_string') {
                    if ($key != 'mer_id') {
                        $tmp['d.' . $key] = $v;
                    } else {
                        $tmp['o.' . $key] = $v;
                    }
                } else {
                    $tmp['_string'] = $v;
                }
                $where_tmp[] = $tmp;
                unset($where[$key]);
            }
            $where = $where_tmp;
            $where['o.status'] = array('in', '4,20,30,40,41,50');

            $all_sale_money = floatval(M('Dining_order_detail')->join('as d LEFT JOIN ' . C('DB_PREFIX') . 'dining_order AS o ON d.order_id = o.order_id ')->where($where)->sum('d.price*d.num'));
            $all_sale_num = floatval(M('Dining_order_detail')->join('as d LEFT JOIN ' . C('DB_PREFIX') . 'dining_order AS o ON d.order_id = o.order_id ')->where($where)->sum('d.num'));

            $subQuery = M('Dining_order_detail')->field('goods_id')->join('as d LEFT JOIN ' . C('DB_PREFIX') . 'dining_order AS o ON d.order_id = o.order_id ')->group('d.goods_id')->where($where)->buildSql();
            $count = M()->table($subQuery . ' a')->count();

            if ($param['sort'] && $count > $param['sort']) {
                $count = $param['sort'];
            }

            if ($is_system==2) {//wap端
                $totalPage = ceil($count / $pagesize);
                $start = $pagesize * ($page - 1);
                $arr['has_more'] = $totalPage > $page ? true : false;
            }else{
                $p = new Page($count, 15);
                $start = $p->firstRow;
                $pagesize = $p->listRows;
            }

            if ($param['sort']) {
                if ($param['sort'] > 15 && intval($param['sort'] / 15) == $param['page'] - 1) {
                    $p->listRows = $param['sort'] - (($param['page'] - 1) * 15);
                } else if ($param['sort'] < 15) {
                    $p->listRows = $param['sort'];
                }
            }
            if ($all_sale_money <= 0) {
                $arr['goods_date'] = M('Dining_order_detail')->join('as d LEFT JOIN ' . C('DB_PREFIX') . 'dining_order AS o ON d.order_id = o.order_id ')->where($where)->group('d.goods_id,d.spec')->order($order_sort)->field("d.goods_id,d.spec,d.name,d.price,d.store_id,d.number,sum(d.price * d.num) as sale_money ,sum(d.num) as sale_count,concat ( left (sum(d.price*d.num)/{$all_sale_num} *100,5),'%') as sale_percent")->limit($start, $pagesize)->select();
            } else {
                $arr['goods_date'] = M('Dining_order_detail')->join('as d LEFT JOIN ' . C('DB_PREFIX') . 'dining_order AS o ON d.order_id = o.order_id ')->where($where)->group('d.goods_id,d.spec')->order($order_sort)->field("d.goods_id,d.spec,d.name,d.price,d.store_id,d.number,sum(d.price * d.num) as sale_money ,sum(d.num) as sale_count,concat ( left (sum(d.price*d.num)/{$all_sale_money} *100,5),'%') as sale_percent")->limit($start, $pagesize)->select();
            }
        } else {
            $where_tmp = array();
            foreach ($where as $key => &$v) {
                if ($key != '_string') {
                    if ($key != 'mer_id') {
                        $tmp['d.' . $key] = $v;
                    } else {
                        $tmp['o.' . $key] = $v;
                    }
                } else {
                    $tmp['_string'] = $v;
                }
                $where_tmp[] = $tmp;
                unset($where[$key]);
            }

            $where = $where_tmp;
            $where['o.paid'] = 1;
            $where['o.status'] = array('in', '0,1,2,3');
            $all_sale_money = floatval(M('Shop_order_detail')->join('as d LEFT JOIN ' . C('DB_PREFIX') . 'shop_order AS o ON d.order_id = o.order_id ')->where($where)->sum('d.price*d.num'));
            $all_sale_num = floatval(M('Shop_order_detail')->join('as d LEFT JOIN ' . C('DB_PREFIX') . 'shop_order AS o ON d.order_id = o.order_id ')->where($where)->sum('d.num'));
            //$count = M('Shop_order_detail')->where($where)->group('goods_id')->count();
            $subQuery = M('Shop_order_detail')->join('as d LEFT JOIN ' . C('DB_PREFIX') . 'shop_order AS o ON d.order_id = o.order_id ')->field('d.goods_id')->group('d.goods_id,d.spec')->where($where)->buildSql();
            $count = M()->table($subQuery . ' a')->count();
            if ($param['sort'] && $count > $param['sort']) {
                $count = $param['sort'];
            }

            if ($is_system==2) {//wap端
                $totalPage = ceil($count / $pagesize);
                $start = $pagesize * ($page - 1);
                $arr['has_more'] = $totalPage > $page ? true : false;
            }else{
                $p = new Page($count, 15);
                $start = $p->firstRow;
                $pagesize = $p->listRows;
            }

            $p = new Page($count, 15);
            if ($param['sort']) {
                if ($param['sort'] > 15 && intval($param['sort'] / 15) == $param['page'] - 1) {
                    $p->listRows = $param['sort'] - (($param['page'] - 1) * 15);
                } else if ($param['sort'] < 15) {
                    $p->listRows = $param['sort'];
                }
            }
            if ($all_sale_money <= 0) {
                $arr['goods_date'] = M('Shop_order_detail')->join('as d LEFT JOIN ' . C('DB_PREFIX') . 'shop_order AS o ON d.order_id = o.order_id ')->where($where)->group('d.goods_id,d.spec')->order($order_sort)->field("d.goods_id,d.spec,d.name,d.price,d.store_id,d.number,sum(d.price*d.num) as sale_money ,sum(d.num) as sale_count,sum(d.is_goods) as is_goods,concat ( left (sum(d.price*d.num)/{$all_sale_num} *100,5),'%') as sale_percent")->limit($start, $pagesize)->select();
            } else {
                $arr['goods_date'] = M('Shop_order_detail')->join('as d LEFT JOIN ' . C('DB_PREFIX') . 'shop_order AS o ON d.order_id = o.order_id ')->where($where)->group('d.goods_id,d.spec')->order($order_sort)->field("d.goods_id,d.spec,d.name,d.price,d.store_id,d.number,sum(d.price*d.num) as sale_money ,sum(d.num) as sale_count,sum(d.is_goods) as is_goods,concat ( left (sum(d.price*d.num)/{$all_sale_money} *100,5),'%') as sale_percent")->limit($start, $pagesize)->select();
            }
        }

        $arr['total'] = $count;
        $arr['totalPage'] = $totalPage;
        if ($is_system!=2) {
            $arr['pagebar'] = $p->show();
        }
        return $arr;
    }

    public function get_area_arr($fid, $level = 2, $tmp = array())
    {
        if ($level > 3) {
            return true;
        }
        $area = M('Area')->where(array('area_pid' => $fid))->select();
        foreach ($area as $v) {

            $tmp[] = $v['area_id'];
            if ($level < 3) {
                $tmp = $this->get_area_arr($v['area_id'], $v['area_type'] + 1, $tmp);
            }
        }
        return $tmp;
    }

    private function allArea($areaId)
    {
        static $allAreas;
        if ($allAreas) {

        } else {
            $condition_area['is_open'] = array('neq', 2);
            $areaList = D('Area')->field(true)->where($condition_area)->select();
            $tmpMap = array();
            foreach ($areaList as $area) {
                $tmpMap[$area['area_id']] = $area['area_pid'];
            }
            return array_reverse($this->ids($tmpMap, $areaId));//[父ID，子ID, 孙ID, ..., $sortId（指定的ID）]
        }
    }

    private function ids($idsArray, $areaId, $ids = array())
    {
        $ids[] = $areaId;
        if (isset($idsArray[$areaId]) && $idsArray[$areaId]) {
            return $this->ids($idsArray, $idsArray[$areaId], $ids);
        } else {
            return $ids;
        }
    }

    //京东统计
    public function sell_order_date_jd($is_system = true, $param)
    {
        $mer_list = $param['mer_list'];
        $store_list = $param['store_list'];
        $mer_id = $param['mer_id'];
        $type = $param['type'];
        if ($param['selectTimeType'] > 0) {
            $start_time = strtotime(date('Y-m-d', time() - ($param['selectTimeType'] - 1) * 86400));
            $end_time = time();
        }
        $where['s.jd_order_id'] = array('gt', 0);
        $where['s.jd_paid'] = 1;
        if ($mer_id) {
            $store_ids = M('Merchant_store')->where(array('mer_id' => $mer_id))->select();
            foreach ($store_ids as $st) {
                $store_list_id[] = $st['store_id'];
            }
            if ($store_list_id) {
                $where['s.store_id'] = array('in', $store_list_id);
            } else {
                return array();
            }
        }
        if ($param['begin_time'] && $param['end_time'] && ($param['end_time'] > $param['begin_time'])) {

            $start_time = strtotime($param['begin_time']);
            $end_time = strtotime($param['end_time']) + 86400;
            unset($_GET['selectTimeType']);
        }
        if ($end_time < $start_time) {
            return array('error_code' => 1, 'msg' => L_('结束时间不能比开始时间小'));
        }
        $param['mer_id'] && $where['s.mer_id'] = $param['mer_id'];
        $where['s.paid'] = 1;
        $Order = ucfirst($type) . '_order';
        if ($mer_list && $mer_list != -1) {
            $where['s.mer_id'] = array('in', $mer_list);
        }
        if ($store_list && $store_list != -1) {
            $where['s.store_id'] = array('in', $store_list);
        }
        if ($start_time) {
            $where['_string'] = "s.pay_time <= {$end_time} AND s.pay_time>={$start_time}  ";
        }
        $where['s.status'] = array('in', array(1, 2, 3));
        //总单数
        $arr['total_money_count'] = M($Order)->alias('s')->where($where)->count();
        //京东运费
        $where_freight = $where;
        $where_freight['jd_freight'] = array('gt', 0);
        $arr['total_freight_money_count'] = M($Order)->alias('s')->where($where_freight)->count();
        $arr['total_freight_money_sum'] = M($Order)->alias('s')->where($where)->sum('jd_freight');
        //京东商品总价合计
        $where_goods = $where;
        $where_goods['d.jd_sku_id'] = array('gt', 0);
        $arr['total_goods_money_sum'] = M($Order)->join('as s left join ' . C('DB_PREFIX') . 'shop_order_detail d ON s.order_id = d.order_id ')->where($where_goods)->sum('d.pay_price');
        //京东商品进价合计
        $arr['total_cost_money_sum'] = M($Order)->join('as s left join ' . C('DB_PREFIX') . 'shop_order_detail d ON s.order_id = d.order_id ')->where($where_goods)->sum('d.cost_price');
        //总额
        $arr['total_money_sum'] = $arr['total_goods_money_sum'] + $arr['total_freight_money_sum'];
        $sum_name = 'd.pay_price';
        $sum_cost = 'd.cost_price';
        $count = M($Order)
            ->alias('s')
            ->field("s.store_id,s.mer_id,s.jd_freight,sum({$sum_name}) as income,count(s.order_id) as counts,sum({$sum_cost}) as costPay")
            ->join('left join ' . C('DB_PREFIX') . 'shop_order_detail d ON s.order_id = d.order_id ')
            ->where($where)
            ->group('d.order_id')
            ->order("s.pay_time DESC,s.store_id DESC")
            ->select();

        $count = count($count);
        if ($is_system) {
            import('@.ORG.system_page');
        } else {
            import('@.ORG.merchant_page');
        }
        $p = new Page($count, 10);
        $total_money_sum_by_store = M($Order)
            ->alias('s')
            ->field("s.store_id,s.mer_id,s.jd_freight,s.pay_time,s.jd_order_id,s.real_orderid,sum({$sum_name}) as income,count(s.order_id) as counts,sum({$sum_cost}) as costPay")
            ->join('left join ' . C('DB_PREFIX') . 'shop_order_detail d ON s.order_id = d.order_id ')
            ->where($where)
            ->group('d.order_id')
            ->order("s.pay_time DESC,s.store_id DESC")
            ->limit($p->firstRow, $p->listRows)
            ->select();

        $arr['pagebar'] = $p->show();
        foreach ($total_money_sum_by_store as &$value) {
            $value['mer_name'] = M('Merchant')->where(array('mer_id' => $value['mer_id']))->getField('name');
            $value['store_name'] = M('Merchant_store')->where(array('store_id' => $value['store_id']))->getField('name');
            $value['total'] = $value['jd_freight'] + $value['income'];
        }
        $arr['sale_date'] = $total_money_sum_by_store;
        return $arr;
    }

    public function export_village_group(&$objProps, &$objExcel, $param)
    {
        $param['type'] = 'village_group';
        $title = L_('X1账单', array('X1' => C('config.village_group_alias')));
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        // 设置当前的sheet
        $objExcel->setActiveSheetIndex(0);

        $condition_where = '1=1 ';
        $where = array();

        if ($param['deliver_status'] > -1 && ($param['status'] == 1 || $param['status'] == -1)) {
            $where['o.status'] = $param['deliver_status'];
            $condition_where .= 'AND o.status=' . $param['deliver_status'];
        } else if ($param['status'] > -1) {
            switch ($param['status']) {
                case 1:
                    $where['_string'] = 'o.status>0 AND o.status<10';
                    $condition_where .= ' AND (o.status>0 AND o.status<10)';
                    break;
                case 0:
                    $where['o.status'] = 0;
                    $condition_where .= ' AND o.status = 0 ';
                    break;
                case 4:
                    $where['_string'] = '(o.status=3 or o.status=4)';
                    $condition_where .= ' AND (o.status=3 or o.status=4 )';
                    break;
                case 11:
                    $where['_string'] = '(o.status=11 or o.status=12)';
                    $condition_where .= ' AND (o.status=11 or o.status=12 )';
                    break;
                case 2: //导出已完成订单包含已评价
                    $where['_string'] = '(o.status=2 or o.status=13)';
                    $condition_where .= ' AND (o.status=2 or o.status=13 )';
                    break;
                case 200:
		    break;
                case 12:
                    $where['_string'] = '(o.status=2 or o.status=3 or o.status=13)';
                    $where['is_apply_refund'] = 1;
                    $condition_where .= ' AND (o.status=2 or o.status=3 or o.status=13) AND is_apply_refund=1';
                    break;
                case 15:
                    $where['_string'] = '(o.status in (4,12))';
                    $condition_where .= ' AND (o.status in (4,12))';
                    break;
                default:
                    $where['o.status'] = $param['status'];
                    $condition_where .= 'AND o.status=' . $param['status'];
                    break;
            }
        }
        $get_header_uid = false;
        $start_uid = 0;
        if ($param['keyword']) {
            switch ($param['searchtype']) {
                case 'user_phone':
                    $user = D('User')->get_user($param['keyword'], 'phone');
                    $where['o.uid'] = $user['uid'];
                    $condition_where .= " AND o.uid={$where['o.uid'] }";
                    break;
                case 'user_id':
                    $where['o.uid'] = $param['keyword'];
                    $condition_where .= " AND o.uid={$where['o.uid'] }";
                    break;
                case 'user_nickname':
                    $user = D('User')->get_user($param['keyword'], 'nickname');
                    $where['o.uid'] = $user['uid'];
                    $condition_where .= " AND o.uid={$where['o.uid'] }";
                    break;
                case 'header_phone':
                    $header = M('Village_group_header')->where(array('phone' => $param['keyword']))->find();
                    $start_uid = $header['id'];
                    $get_header_uid = true;
                    break;

                case 'header_uid':
                    $header = M('Village_group_header')->where(array('id' => $param['keyword']))->find();
                    $start_uid = $header['id'];
                    $get_header_uid = true;
                    break;
                case 'header_name':
                    $header = M('Village_group_header')->where(array('name' => $param['keyword']))->find();
                    $start_uid = $header['id'];
                    $get_header_uid = true;
                    break;

                case 'order_id':
                    $where['o.order_id'] = $param['keyword'];
                    $condition_where .= " AND o.order_id={$param['keyword']}";
                    break;
                case 'real_orderid':
                    $where['o.real_orderid'] = $param['keyword'];
                    $condition_where .= " AND o.real_orderid={$param['keyword']}";
                    break;

            }

        }
        // 处理省市区
        if ($param['province_idss'] > 0 || $param['city_idss'] > 0 || $param['area_id'] > 0) {
            $where_header = array(
                'header_uid' => array('gt', 0)
            );
            if ($param['province_idss']) {
                $where_header['province_id'] = $param['province_idss'];
            }
            if ($param['city_idss']) {
                $where_header['city_id'] = $param['city_idss'];
            }
            if ($param['area_id']) {
                $where_header['area_id'] = $param['area_id'];
            }
            $pick_address = M('Village_group_pick_address')->where($where_header)->select();
            $header_arr = array();
            foreach ($pick_address as $val) {
                $header_arr[] = $val['header_uid'];
            }
            if ($start_uid > 0) {
                $header_arr[] = $start_uid;
            }
            $where['start_uid'] = array('in', $header_arr);
            $header_str = implode(',', $header_arr);
            $condition_where .= " AND o.start_uid IN ({$header_str})";
        } elseif ($get_header_uid) {
            $where['start_uid'] = $start_uid;
            $condition_where .= " AND o.start_uid={$where['start_uid'] }";
        }

        $pay_type = $param['pay_type'];
        if ($pay_type) {
            if ($pay_type == 'balance') {
                $where['system_balance'] = array('gt', 0);
                $condition_where .= " AND (p.system_balance>0) ";
            } else {
                $where['p.pay_type'] = $pay_type;
                $condition_where .= " AND p.pay_type='{$pay_type}'";
            }

        }

        if (!empty($param['begin_time']) && !empty($param['end_time'])&& !empty($param['searchtimetype'])) {
            $begin_time = strtotime($param['begin_time'] . " 00:00:00");
            $end_time = strtotime($param['end_time'] . " 23:59:59");
            if ($param['begin_time'] > $param['end_time']) {
                return false;
            }
            $period = array($begin_time, $end_time);
            $where['o.'.$param['searchtimetype']] = ['between', $period];
            $condition_where .= " AND (o.".$param['searchtimetype']." BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
        }
        $where['d.is_delay'] = 0;
        $where['_string'] = $where['_string'] ? $where['_string'] . " AND (o.newest_delivery_periods>=d.date_num)" : "(o.newest_delivery_periods>=d.date_num)";
        $condition_where .= " AND (o.newest_delivery_periods>=d.date_num)";

        //部分退款问题
        if ($param['status'] == 200) {
            $partRefundOrder = M('Village_group_order')->alias('o')
                ->join('LEFT JOIN ' . C('DB_PREFIX') . 'village_group_order_detail d ON d.order_id = o.order_id')
                ->where($where)
                ->field('o.order_id,o.real_orderid,SUM(d.num) AS t_total,SUM(d.refundNum) AS r_total')
                ->group('o.order_id')
                ->having('r_total > 0 AND t_total > r_total')
                ->select();
            if ($partRefundOrder) {
                $refundOrderIds = array_column($partRefundOrder, 'order_id');
                $where['o.order_id'] = ['IN', $refundOrderIds];
                $condition_where .=" AND o.order_id IN (".implode(',',$refundOrderIds).")";
            } else {
                $condition_where .= " AND o.order_id=0";
            }
        }
        $count = M('Village_group_order')
            ->join('as o left join ' . C('DB_PREFIX') . 'village_group_order_detail d ON o.order_id=d.order_id')
            ->join(' left join ' . C('DB_PREFIX') . 'plat_order p ON p.business_id = o.order_id AND p.business_type="village_group"')
            ->where($condition_where)->count();

        set_time_limit(0);
        $length = ceil($count / 1000);
        $order_status = D('Village_group')->order_status;
        $mer_temp = $user_temp = $leader_temp = array();
        $refundMod = D('Village_group_refund_detail');
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle(L_('第X1个一千个订单信息', (array('X1' => $i + 1))));
            $objActSheet = $objExcel->getActiveSheet();
            $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('J')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('L')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('M')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('N')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('O')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('P')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('Q')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('R')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('S')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('T')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('U')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('V')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('W')->setWidth(80);
            $objExcel->getActiveSheet()->getColumnDimension('X')->setWidth(20);

            $objActSheet->setCellValue('A1', L_('订单号'));
            $objActSheet->setCellValue('B1', L_('商家名称'));
            $objActSheet->setCellValue('C1', L_('商品名称'));
            $objActSheet->setCellValue('D1', L_('商品进价'));
            $objActSheet->setCellValue('E1', L_('单价'));
            $objActSheet->setCellValue('F1', L_('数量'));
            $objActSheet->setCellValue('G1', L_('已退数量'));
            $objActSheet->setCellValue('H1', L_('总价'));
            $objActSheet->setCellValue('I1', L_('客户姓名'));
            $objActSheet->setCellValue('J1', L_('客户电话'));
            $objActSheet->setCellValue('K1', L_('团长姓名'));
            $objActSheet->setCellValue('L1', L_('团长电话'));
            $objActSheet->setCellValue('M1', L_('自提地址'));
            $objActSheet->setCellValue('N1', L_('订单状态'));
            $objActSheet->setCellValue('O1', L_('订单总价'));
            $objActSheet->setCellValue('P1', L_('余额支付'));
            $objActSheet->setCellValue('Q1', L_('平台在线支付'));
            $objActSheet->setCellValue('R1', C('config.score_name'));
            $objActSheet->setCellValue('S1', L_('配送状态'));
            $objActSheet->setCellValue('T1', L_('添加时间'));
            $objActSheet->setCellValue('U1', L_('支付时间'));
            $objActSheet->setCellValue('V1', L_('完成时间'));
            $objActSheet->setCellValue('W1', L_('支付方式'));
            $objActSheet->setCellValue('X1', L_('相关备注信息'));
            $objActSheet->setCellValue('Y1', L_('退款在线支付金额'));
            $objActSheet->setCellValue('Z1', L_('退款时间'));

            $sql = 'SELECT d.status as d_status,d.is_delay,d.delay_time,o.order_id,o.village_group_type,o.delivery_periods,o.newest_delivery_periods,d.date_num,d.send_time,d.real_send_time,d.service_time,d.area_price,d.increase_price,o.real_orderid,o.uid,o.status,o.start_uid,o.total_money,o.discount_price,o.add_time,o.bill_time ,o.pay_time,o.pay_type ,d.name as goods_name,d.price as goods_price,d.purchase_price,d.num,d.mer_id,p.system_balance,p.paid ,p.pay_money,p.system_score_money,o.score_used_count,o.last_time,d.refundNum from pigcms_village_group_order as o left join '
                . C('DB_PREFIX') . 'village_group_order_detail as d on o.order_id=d.order_id left join ' . C('DB_PREFIX') . "plat_order p on p.business_id=o.order_id AND p.business_type='village_group' "
                . " where " . $condition_where . ' order by o.order_id DESC limit ' . ($i * 1000) . ',1000';

            $list = M('')->query($sql);
            
            //获取订单id
            $order_arr = $list?array_column($list,'order_id'):[];
            $refund_orders = [];
            if($order_arr){
                $order_arr = array_flip($order_arr);
                $order_arr = array_flip($order_arr);
                $refundOrderArr = M('Village_group_order')->alias('o')
                    ->join('LEFT JOIN ' . C('DB_PREFIX') . 'village_group_order_detail od ON od.order_id = o.order_id')
                    ->where(['o.order_id'=>['in',$order_arr]])
                    ->field('o.order_id,o.real_orderid,SUM(od.num) AS t_total,SUM(od.refundNum) AS r_total')
                    ->group('o.order_id')
                    ->having('r_total > 0 AND t_total > r_total')
                    ->select();
                $refund_orders = $refundOrderArr?array_column($refundOrderArr,'order_id'):[];
            }
            $mer_ids = $uid_ids = $leader_ids = array();
            foreach ($list as $l) {
                if (!$mer_temp[$l['mer_id']])
                    $mer_ids[] = $l['mer_id'];
                if (!$user_temp[$l['uid']])
                    $uid_ids[] = $l['uid'];
                if (!$leader_temp[$l['start_uid']])
                    $leader_ids[] = $l['start_uid'];
            }
            $mer_ids = array_unique($mer_ids);
            $mer_ids = implode(',', $mer_ids);

            $uid_ids = array_unique($uid_ids);
            $uid_ids = implode(',', $uid_ids);

            $leader_ids = array_unique($leader_ids);
            $leader_ids = implode(',', $leader_ids);

            if ($mer_ids) {
                $merchants = M("Merchant")->field('name,mer_id')->where(array('mer_id' => array('in', $mer_ids)))->select();
                foreach ($merchants as $m) {
                    if (!$mer_temp[$m['mer_id']])
                        $mer_temp[$m['mer_id']] = $m;
                }
            }
            if ($uid_ids) {
                $users = M("User")->field('nickname,uid,phone')->where(array('uid' => array('in', $uid_ids)))->select();
                foreach ($users as $u) {
                    if (!$user_temp[$u['uid']])
                        $user_temp[$u['uid']] = $u;
                }
            }
            if ($leader_ids) {
                $leaders = M("Village_group_pick_address")->field('h.id,h.name,h.phone,pick_id,ad.pick_addr')->join('ad left join ' . C('DB_PREFIX') . 'village_group_header as h ON h.id=ad.header_uid')->where(array('h.id' => array('in', $leader_ids)))->select();

                foreach ($leaders as $lr) {
                    if (!$leader_temp[$lr['id']])
                        $leader_temp[$lr['id']] = $lr;
                }
            }

            $tmp_id = 0;

            if (!empty($list)) {
                $index = 1;
                foreach ($list as $value) {
                    $increase_price = $value['increase_price'] > 0 ? $value['increase_price'] : 0;
                    if ($value['area_price'] > 0) {
                        $value['goods_price'] = round_number($value['area_price'] + $increase_price, 2);
                    } else {
                        $value['goods_price'] = round_number($value['goods_price'] + $increase_price, 2);
                    }
                    $value['all_price'] = round_number($value['goods_price'] * $value['num'], 2);
                    if ($value['delivery_periods'] > 1) {
                        $value['goods_price'] = round_number($value['goods_price'] / $value['delivery_periods'], 2);
                        $value['purchase_price'] = round_number($value['purchase_price'] / $value['delivery_periods'], 2);
                        $value['all_price'] = round_number($value['all_price'] / $value['delivery_periods'], 2);
                    }
                    if ($tmp_id == $value['order_id']) {
                        $index++;
                        $objActSheet->setCellValueExplicit('A' . $index, '');
                        $objActSheet->setCellValueExplicit('B' . $index, $mer_temp[$value['mer_id']]['name']);
                        $objActSheet->setCellValueExplicit('C' . $index, $value['goods_name']);
                        $objActSheet->setCellValueExplicit('D' . $index, round_number($value['purchase_price'], 2));
                        $objActSheet->setCellValueExplicit('E' . $index, round_number($value['goods_price'], 2));
                        $objActSheet->setCellValueExplicit('F' . $index, $value['num']);
                        $objActSheet->setCellValueExplicit('G' . $index, $value['refundNum']);
                        $objActSheet->setCellValueExplicit('H' . $index, $value['all_price']);
                        $objActSheet->setCellValueExplicit('I' . $index, '');
                        $objActSheet->setCellValueExplicit('J' . $index, '');
                        $objActSheet->setCellValueExplicit('K' . $index, '');
                        $objActSheet->setCellValueExplicit('L' . $index, '');
                        $objActSheet->setCellValueExplicit('M' . $index, '');
                        $objActSheet->setCellValueExplicit('N' . $index, '');
                        $objActSheet->setCellValueExplicit('O' . $index, '');
                        $objActSheet->setCellValueExplicit('P' . $index, '');
                        $objActSheet->setCellValueExplicit('Q' . $index, '');
                        $objActSheet->setCellValueExplicit('R' . $index, '');
                        $objActSheet->setCellValueExplicit('S' . $index, '');
                        $objActSheet->setCellValueExplicit('T' . $index, '');
                        $objActSheet->setCellValueExplicit('U' . $index, '');
                        $objActSheet->setCellValueExplicit('V' . $index, '');
                        $objActSheet->setCellValueExplicit('W' . $index, '');
                    } else {
                        $index++;
                        $objActSheet->setCellValueExplicit('A' . $index, $value['order_id']);
                        $objActSheet->setCellValueExplicit('B' . $index, '');
                        $objActSheet->setCellValueExplicit('C' . $index, '');
                        $objActSheet->setCellValueExplicit('D' . $index, '');
                        $objActSheet->setCellValueExplicit('E' . $index, '');
                        $objActSheet->setCellValueExplicit('F' . $index, '');
                        $objActSheet->setCellValueExplicit('G' . $index, '');
                        $objActSheet->setCellValueExplicit('H' . $index, '');
                        $objActSheet->setCellValueExplicit('I' . $index, $user_temp[$value['uid']]['nickname']);
                        $objActSheet->setCellValueExplicit('J' . $index, $user_temp[$value['uid']]['phone']);
                        $objActSheet->setCellValueExplicit('K' . $index, $leader_temp[$value['start_uid']]['name']);
                        $objActSheet->setCellValueExplicit('L' . $index, $leader_temp[$value['start_uid']]['phone']);
                        $objActSheet->setCellValueExplicit('M' . $index, $leader_temp[$value['start_uid']]['pick_addr']);


                        $order_status_txt_a = '';
                        if (in_array($value['order_id'],$refund_orders)){
                            $order_status_txt_a = L_('部分退款');
                        }
                        if ($value['status'] == 2 || $value['status'] == 13) {
                            $order_status_txt = L_('已完成');
                        } else if ($value['status'] >= 1 && $value['status'] < 10 && $value['status'] != 4) {
                            $order_status_txt = L_('已支付');
                        } else if ($value['status'] == 10) {
                            $order_status_txt = L_('超时未支付');
                        } else if ($value['status'] == 0) {
                            $order_status_txt = L_('未支付');
                        } else if ($value['status'] == 12 || $value['status'] == 4) {
                            $order_status_txt = L_('已退款');
                        } else if ($value['status'] == 11) {
                            $order_status_txt = L_('已取消');
                        }

                        $objActSheet->setCellValueExplicit('N' . $index, $order_status_txt_a?:$order_status_txt);
                        $objActSheet->setCellValueExplicit('O' . $index, $value['total_money']);
                        $objActSheet->setCellValueExplicit('P' . $index, $value['system_balance']);
                        $objActSheet->setCellValueExplicit('Q' . $index, $value['pay_money']);
                        $objActSheet->setCellValueExplicit('R' . $index, $value['score_used_count']);

                        if ($value['status'] == 1) {
                            $deliver_status = L_('待发货');
                        } else if ($value['status'] == 7) {
                            $deliver_status = L_('部分已发货');
                        } else if ($value['status'] == 5) {
                            $deliver_status = L_('全部已发货');
                        } else if ($value['status'] == 4 || $value['status'] == 12) {
                            $deliver_status = L_('已退款');
                        } else if ($value['status'] == 8) {
                            $deliver_status = L_('部分已到货');
                        } else if ($value['status'] == 6) {
                            $deliver_status = L_('全部已到货');
                        } else if ($value['status'] == 3) {
                            $deliver_status = L_('部分已自提');
                        } else if ($value['status'] == 2) {
                            $deliver_status = L_('全部已自提');
                        } else if ($value['status'] == 13) {
                            $deliver_status = L_('已评价');
                        } else {
                            $deliver_status = ' ';
                        }
                        if ($value['village_group_type'] == 1) {
                            $remark = L_('类型：') . L_('周期购  ');
                            if ($value['delivery_periods'] > 0) {
                                $remark .= L_('周期购期数：') . "{$value['delivery_periods']}  ";
                            }
                            if ($value['send_time'] > 0) {
                                $remark .= L_('期望派送时间：') . date('Y-m-d', $value['send_time']) . '  ';
                            }
                            if ($value['newest_delivery_periods'] > 0) {
                                $remark .= L_("当前期数：") . "{$value['newest_delivery_periods']}；";
                            }
                            if ($value['real_send_time'] > 0) {
                                $remark .= L_('实际派送时间：') . date('Y-m-d H:i:s', $value['real_send_time']) . '  ';
                            }
                            if ($value['service_time'] > 0) {
                                $remark .= L_('送达时间：') . date('Y-m-d H:i:s', $value['service_time']) . '  ';
                            }
                        } else {
                            $remark = L_('类型：') . L_('普通  ');
                        }

                        //计算在线退款金额
                        if($value['status'] == 12){
                            //未发货取消订单
                            $onlineRefundMoney = $value['discount_price'];
                            $maxRefundTime =  $value['last_time'];
                        }else{
                            $onlineRefundMoney = $refundMod->where(['order_id'=>$value['order_id'],'status'=>1])->sum('pay_money');
                            $maxRefundTime =  $refundMod->where(['order_id'=>$value['order_id'],'status'=>1])->max('reply_time');
                        }
                        
                        $objActSheet->setCellValueExplicit('S' . $index, $deliver_status);
                        $objActSheet->setCellValueExplicit('T' . $index, $value['add_time'] ? date('Y-m-d H:i:s', $value['add_time']) : '');
                        $objActSheet->setCellValueExplicit('U' . $index, $value['pay_time'] ? date('Y-m-d H:i:s', $value['pay_time']) : '');
                        $objActSheet->setCellValueExplicit('V' . $index, $value['bill_time'] ? date('Y-m-d H:i:s', $value['bill_time']) : '');
                        $objActSheet->setCellValueExplicit('W' . $index, D('Pay')->get_pay_name($value['pay_type'], 0, $value['paid']));
                        $objActSheet->setCellValueExplicit('X' . $index, $remark);
                        $objActSheet->setCellValueExplicit('Y' . $index, getFormatNumber($onlineRefundMoney));
                        $objActSheet->setCellValueExplicit('Z' . $index, $maxRefundTime?date('Y-m-d H:i:s',$maxRefundTime):'');

                        $index++;

                        $objActSheet->setCellValueExplicit('A' . $index, '');
                        $objActSheet->setCellValueExplicit('B' . $index, $mer_temp[$value['mer_id']]['name']);
                        $objActSheet->setCellValueExplicit('C' . $index, $value['goods_name']);
                        $objActSheet->setCellValueExplicit('D' . $index, $value['purchase_price']);
                        $objActSheet->setCellValueExplicit('E' . $index, $value['goods_price']);
                        $objActSheet->setCellValueExplicit('F' . $index, $value['num']);
                        $objActSheet->setCellValueExplicit('G' . $index, $value['refundNum']);
                        $objActSheet->setCellValueExplicit('H' . $index, $value['all_price']);
                        $objActSheet->setCellValueExplicit('I' . $index, '');
                        $objActSheet->setCellValueExplicit('J' . $index, '');
                        $objActSheet->setCellValueExplicit('K' . $index, '');
                        $objActSheet->setCellValueExplicit('L' . $index, '');
                        $objActSheet->setCellValueExplicit('M' . $index, '');
                        $objActSheet->setCellValueExplicit('N' . $index, '');
                        $objActSheet->setCellValueExplicit('O' . $index, '');
                        $objActSheet->setCellValueExplicit('P' . $index, '');
                        $objActSheet->setCellValueExplicit('Q' . $index, '');
                        $objActSheet->setCellValueExplicit('R' . $index, '');
                        $objActSheet->setCellValueExplicit('S' . $index, '');
                        $objActSheet->setCellValueExplicit('T' . $index, '');
                        $objActSheet->setCellValueExplicit('U' . $index, '');
                        $objActSheet->setCellValueExplicit('V' . $index, '');
                        $objActSheet->setCellValueExplicit('W' . $index, '');
                    }
                    $tmp_id = $value['order_id'];
                }
            }
            sleep(2);
        }
    }

    public function export_village_group_send(&$objProps, &$objExcel, $param)
    {
        if($param['goods_id']){
            $now_goods = M('Village_group_goods')->where(array('goods_id' => $param['goods_id']))->find();
            $param['type'] = 'village_group';
            $title = L_("【X1】发货单", array("X1" => $now_goods['name']));
        }
        else{
            $param['type'] = 'village_group';
            $title = L_("发货单");   
        }
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        // 设置当前的sheet
        $objExcel->setActiveSheetIndex(0);
        // $objExcel->getActiveSheet()->setTitle(L_('发货总单 (x1至x2)', array('x1' => $param['begin_time'], 'x2' => $param['end_time'])));
        $objActSheet = $objExcel->getActiveSheet();

        $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
        $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
        $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
        $objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
        $objExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
        $objExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
        $objExcel->getActiveSheet()->getColumnDimension('J')->setWidth(20);
        $objExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);

        $objActSheet->setCellValue('A1', L_('商品ID'));
        $objActSheet->setCellValue('B1', L_('商品名称'));
        $objActSheet->setCellValue('C1', L_('商家团购ID'));
        $objActSheet->setCellValue('D1', L_('商家信息'));
        $objActSheet->setCellValue('E1', L_('进货价'));
        $objActSheet->setCellValue('F1', L_('进货总价'));
        $objActSheet->setCellValue('G1', L_('售价'));
        $objActSheet->setCellValue('H1', L_('总金额'));
        $objActSheet->setCellValue('I1', L_('购买数量'));
        $objActSheet->setCellValue('J1', L_('未发货数量'));
        $objActSheet->setCellValue('K1', L_('已发货数量'));

        if (empty($param['begin_time'])) {
//            $param['begin_time'] = date('Y-m-d').' 00:00';
        }

        if (empty($param['end_time'])) {
//            $param['end_time'] = date('Y-m-d').' 23:59';
        }
        $start_uid = array();
        if ($param['searchtype'] && $param['keyword'] != '') {
            switch ($param['searchtype']) {
                case 'order_id':
                    $where['o.order_id'] = $param['keyword'];
                    break;
                case 'name':
                    $where['g.name'] = $param['keyword'];
                    break;
                case 'goods_id':
                    $where['g.goods_id'] = $param['keyword'];
                    break;
                case 'mer_id':
                    $where['g.mer_id'] = $param['keyword'];
                    break;
                case 'mer_name':
                    $where['m.name'] = ['like', '%'.$param['keyword'].'%'];
                    break;
                case 'village_name':
                    $village_list = M('Village_group_pick_address')->field('p.header_uid')->join('as p LEFT JOIN ' . C('DB_PREFIX') . 'village_group_header h ON h.id = p.header_uid')->where(array('p.name' => array('like', "%{$param['keyword']}%"), 'p.status' => 1, 'h.status' => 1))->select();

                    foreach ($village_list as $v) {
                        $start_uid[] = $v['header_uid'];
                    }
                    $where['o.start_uid'] = array('in', $start_uid);
                    break;
            }
        }

        // 处理省市区
        if (($param['province_idss'] > 0 || $param['province_id'] > 0 || $param['city_idss'] > 0 || $param['city_id'] > 0 || $param['area_id'] > 0) && empty($where['o.start_uid'])) {
            $where_header = array(
                'header_uid' => array('gt', 0)
            );
            if ($param['province_idss'] || $param['province_id']) {
                $where_header['province_id'] = $param['province_idss'] > 0 ? $param['province_idss'] : $param['province_id'];
            }
            if ($param['city_idss'] || $param['city_id']) {
                $where_header['city_id'] = $param['city_idss'] > 0 ? $param['city_idss'] : $param['city_id'];
            }
            if ($param['area_id']) {
                $where_header['area_id'] = $param['area_id'];
            }
            $pick_address = M('Village_group_pick_address')->where($where_header)->select();
            $header_arr = array();
            foreach ($pick_address as $val) {
                $header_arr[] = $val['header_uid'];
            }
            if ($start_uid) {
                $header_arr = array_merge($header_arr, $start_uid);
            }
            if($header_arr){
                $where['o.start_uid'] = array('in', $header_arr);
            }
        }

       if (!empty($param['begin_time']) && !empty($param['end_time'])) {
            $begin_time = strtotime($param['begin_time'].":00");
            $end_time = strtotime($param['end_time'].":00");
            if ($begin_time > $end_time) {
                $this->error("结束时间应大于开始时间");
            }

            $period = array($begin_time, $end_time);
             if($param['searchtimetype'] == 'pay_time'){
                $where['o.'.$param['searchtimetype']] = ['between', $period];
            }
            else{
                $where['d.'.$param['searchtimetype']] = ['between', $period];
            }
            // $where['_string'] = "(g.village_group_type=1 AND (d.send_time  BETWEEN " . $period[0] . ' AND ' . $period[1] . ") AND d.date_num<=o.newest_delivery_periods) OR (g.village_group_type=0 AND ((g.send_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ") OR (g.send_time=0 AND o.add_time BETWEEN " . ($period[0] - 86400) . ' AND ' . ($period[1] - 86400) . ")))";
        }

//        $where['d.status'] = 0;
        if (!$param['village_group_type']) {
            $where['o.village_group_type'] = array('neq', 2);
        } else {
            $where['o.village_group_type'] = $param['village_group_type'];
        }
        $where['o.status'] = array('in', '1,2,3,5,6,7,8,13');
        $param['goods_id'] && $where['d.goods_id'] = $param['goods_id'];
        $param['is_merchant_send'] && $where['d.is_merchant_send'] = $param['is_merchant_send'];
        if (!$param['is_merchant_send']) {
            $where['d.is_merchant_send'] = 0;
        }
        $param['mer_id'] && $where['d.mer_id'] = $param['mer_id'];
//        $where['d.is_delay'] = 0;

        $where['_string'] = 'd.date_num = o.newest_delivery_periods AND d.is_delay=0';
        $list_goods = M('Village_group_order_detail')
            ->field('o.delivery_periods,o.newest_delivery_periods,d.id as detail_id,o.pick_addr,d.increase_price,d.area_price,d.village_group_type,d.send_time as d_send_time,d.real_send_time,d.service_time,d.date_num,d.is_delay,d.delay_time,d.delay_user,d.name,o.order_id,o.pay_time,o.status,d.status as deliver_status,o.start_uid,d.goods_id,d.price,d.purchase_price,d.num,u.phone,u.nickname,g.group_id,g.start_time,g.end_time,g.send_time,g.get_time,g.mer_id')
            ->join('as d left join ' . C('DB_PREFIX') . 'village_group_goods as g ON g.goods_id=d.goods_id left join '.C('DB_PREFIX').'merchant m ON g.mer_id=m.mer_id left join ' . C('DB_PREFIX') . 'village_group_order o ON o.order_id= d.order_id  left join ' . C('DB_PREFIX') . 'user AS u ON u.uid=o.uid')
            ->where($where)
            ->order('d.order_id DESC,d.date_num DESC')
            ->group('d.id')
            ->select();
        fdump(M('Village_group_order_detail')->_sql(),'guo',true);
        $goods_list = array();
        foreach ($list_goods as $list_good) {
            $goods_list[$list_good['goods_id']][$list_good['detail_id']] = $list_good;
        }

        $index = 1;
        foreach ($goods_list as $item) {
            $index++;
            reset($item);
            $val = current($item);

            $val['purchase_price'] = 0;
            $val['all_purchase_price'] = 0;
            $val['price'] = 0;
            $val['all_price'] = 0;
            $val['all_num'] = 0;
            $val['unsend_num'] = 0;
            $val['send_num'] = 0;
            foreach ($item as $v) {
                $increase_price = $v['increase_price'] > 0 ? $v['increase_price'] : 0;
                if ($v['area_price'] > 0) {
                    $v['all_price'] = ($v['area_price'] + $increase_price) * $v['num'];
                    $v['price'] = $v['area_price'] + $increase_price;
                } else {
                    $v['all_price'] = ($v['price'] + $increase_price) * $v['num'];
                    $v['price'] = $v['price'] + $increase_price;
                }
                $v['all_purchase_price'] = $v['purchase_price'] * $v['num'];
                if ($v['delivery_periods'] > 1) {
                    $v['price'] = round_number($v['price'] / $v['delivery_periods'], 2);
                    $v['purchase_price'] = round_number($v['purchase_price'] / $v['delivery_periods'], 2);
                    $v['all_price'] = round_number($v['all_price'] / $v['delivery_periods'], 2);
                    $v['all_purchase_price'] = round_number($v['all_purchase_price'] / $v['delivery_periods'], 2);
                }
                if ($v['deliver_status'] < 1) {
                    $val['unsend_num'] += $v['num'];
                } else {
                    $val['send_num'] += $v['num'];
                }
                $val['purchase_price'] += $v['purchase_price'];
                $val['all_purchase_price'] += $v['all_purchase_price'];
                $val['price'] += $v['price'];
                $val['all_price'] += $v['all_price'];
                $val['all_num'] += $v['num'];
            }

            $now_merchant = D('Merchant')->get_info($val['mer_id']);
            $objActSheet->setCellValueExplicit('A' . $index, $val['goods_id']);
            $objActSheet->setCellValueExplicit('B' . $index, $val['name']);
            $objActSheet->setCellValueExplicit('C' . $index, $val['group_id']);
            $objActSheet->setCellValueExplicit('D' . $index, $now_merchant['name'] . '/' . $now_merchant['phone']);
            $objActSheet->setCellValueExplicit('E' . $index, round_number($val['purchase_price'], 2));
            $objActSheet->setCellValueExplicit('F' . $index, round_number($val['all_purchase_price'], 2));
            $objActSheet->setCellValueExplicit('G' . $index, round_number($val['price'], 2));
            $objActSheet->setCellValueExplicit('H' . $index, round_number($val['all_price'], 2));
            $objActSheet->setCellValueExplicit('I' . $index, $val['all_num']);
            $objActSheet->setCellValueExplicit('J' . $index, $val['unsend_num']);
            $objActSheet->setCellValueExplicit('K' . $index, $val['send_num']);

        }

        unset($where['d.is_delay']);
//      $count = M('Village_group_order_detail')->join('as d left join '.C('DB_PREFIX').'village_group_order o ON o.order_id= d.order_id')->where($where)->count();
        $count = M('Village_group_order_detail')->join('as d left join ' . C('DB_PREFIX') . 'village_group_goods as g ON g.goods_id=d.goods_id  left join ' . C('DB_PREFIX') . 'village_group_order o ON o.order_id= d.order_id left join '.C('DB_PREFIX').'merchant m ON g.mer_id=m.mer_id')->where($where)->count();


        set_time_limit(0);
        $length = ceil($count / 1000);
        $order_status = D('Village_group')->order_status;
        $deliver_status = D('Village_group')->deliver_status;
        $leader_tmp = array();
        for ($i = 1; $i <= $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);
            $objExcel->getActiveSheet()->setTitle(L_('第X1个一千个发货单', ($i)));
            $objActSheet = $objExcel->getActiveSheet();

            $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(30);
            $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('J')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('L')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('M')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('N')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('O')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('P')->setWidth(20);

            $objActSheet->setCellValue('A1', L_('订单号'));
            $objActSheet->setCellValue('B1', L_('团长信息'));
            $objActSheet->setCellValue('C1', L_('用户信息'));
            $objActSheet->setCellValue('D1', L_('提货人信息'));
            $objActSheet->setCellValue('E1', L_('自提地址'));
            $objActSheet->setCellValue('F1', L_('商品名称'));
            $objActSheet->setCellValue('G1', L_('进货价'));
            $objActSheet->setCellValue('H1', L_('进货总价'));
            $objActSheet->setCellValue('I1', L_('售价'));
            $objActSheet->setCellValue('J1', L_('总金额'));
            $objActSheet->setCellValue('K1', L_('购买数量'));
            $objActSheet->setCellValue('L1', L_('支付时间'));
            $objActSheet->setCellValue('M1', L_('订单状态'));
            $objActSheet->setCellValue('N1', L_('备注'));
            $objActSheet->setCellValue('O1', L_('配送状态'));
            $objActSheet->setCellValue('P1', L_('商家信息'));


//          $sql='SELECT o.order_id,o.real_orderid,o.uid,o.status,o.start_uid,o.total_money ,o.pay_time,o.pay_type ,d.name as goods_name,d.price as goods_price,d.purchase_price,d.num,d.mer_id,p.system_balance,p.paid ,p.pay_money,p.system_score_money from pigcms_village_group_order as o left join '
//                  .C('DB_PREFIX').'village_group_order_detail as d on o.order_id=d.order_id left join '.C('DB_PREFIX')."plat_order p on p.business_id=o.order_id AND p.business_type='village_group' "
//                  ." where ".$condition_where .' order by o.order_id DESC limit '.($i*1000).',1000';
            $list = M('Village_group_order_detail')->field('o.note,o.delivery_periods,o.newest_delivery_periods,o.pick_addr,o.pick_name,o.pick_phone,d.increase_price,d.area_price,d.village_group_type,d.send_time as d_send_time,d.real_send_time,d.service_time,d.date_num,d.is_delay,d.delay_time,d.delay_user,d.name,o.real_orderid,d.name,o.order_id,o.pay_time,o.status,d.status as deliver_status,o.start_uid,d.goods_id,d.price,d.purchase_price,d.num,u.uid,u.phone,u.nickname,g.start_time,g.end_time,g.send_time,g.get_time')
                ->join('as d left join ' . C('DB_PREFIX') . 'village_group_goods as g ON g.goods_id=d.goods_id left join ' . C('DB_PREFIX') . 'village_group_order o ON o.order_id= d.order_id  left join ' . C('DB_PREFIX') . 'user AS u ON u.uid=o.uid left join '.C('DB_PREFIX').'merchant m ON g.mer_id=m.mer_id')
                ->where($where)->order('o.order_id DESC')->limit((($i - 1) * 1000) . ',1000')->select();


            $m_village_group_header = D('Village_group');
            if (!empty($list)) {
                $index = 1;
                foreach ($list as $value) {
                    if (!$leader_tmp[$value['start_uid']]) {
                        $leader = $m_village_group_header->get_header_info('', $value['start_uid'], true);
                        $leader_tmp[$value['start_uid']] = $leader['msg'];
                    }
                    $header_info = $leader_tmp[$value['start_uid']];

                    // 查询一下团购购买数量
                    $increase_price = $value['increase_price'] > 0 ? $value['increase_price'] : 0;
                    if ($value['area_price'] > 0) {
                        $value['all_price'] = round_number(($value['area_price'] + $increase_price) * $value['num'], 2);
                        $value['price'] = round_number($value['area_price'] + $increase_price, 2);
                    } else {
                        $value['all_price'] = round_number(($value['price'] + $increase_price) * $value['num'], 2);
                        $value['price'] = round_number($value['price'] + $increase_price, 2);
                    }
                    $value['all_purchase_price'] = round_number($value['purchase_price'] * $value['num'], 2);
                    if ($value['delivery_periods'] > 1) {
                        $value['price'] = round_number($value['price'] / $value['delivery_periods'], 2);
                        $value['purchase_price'] = round_number($value['purchase_price'] / $value['delivery_periods'], 2);
                        $value['all_price'] = round_number($value['all_price'] / $value['delivery_periods'], 2);
                        $value['all_purchase_price'] = round_number($value['all_purchase_price'] / $value['delivery_periods'], 2);
                    }

                    $index++;
                    $objActSheet->setCellValueExplicit('A' . $index, $value['order_id']);
                    fdump($value['order_id'] . 'A'.$index,'test11',true);
                    $objActSheet->setCellValueExplicit('B' . $index, is_array($header_info) ? $header_info['name'] . '/' . $header_info['phone'] : '没有团长信息');
                    $value['nickname'] = removeEmojiChar($value['nickname']);

                    $objActSheet->setCellValueExplicit('C' . $index, $value['uid'] . '/' . $value['phone'] . '/' . $value['nickname']);

                    //增加提货人
                    if ($value['pick_name']) {
                        $objActSheet->setCellValueExplicit('D' . $index, $value['pick_name'] . '/' . $value['pick_phone']);
                    } else {
                        $objActSheet->setCellValueExplicit('D' . $index, '');
                    }
                   
                    if (empty($header_info['pick_address']) || empty($header_info['pick_address']['pick_addr'])) {
                        $objActSheet->setCellValueExplicit('E' . $index, $value['pick_addr']);
                    } else {
                        $objActSheet->setCellValueExplicit('E' . $index, $header_info['pick_address']['pick_addr']);
                    }
                    $objActSheet->setCellValueExplicit('F' . $index, $value['name']);
                    $objActSheet->setCellValueExplicit('G' . $index, $value['purchase_price']);
                    $objActSheet->setCellValueExplicit('H' . $index, $value['all_purchase_price']);
                    $objActSheet->setCellValueExplicit('I' . $index, $value['price']);
                    $objActSheet->setCellValueExplicit('J' . $index, $value['all_price']);
                    $objActSheet->setCellValueExplicit('K' . $index, $value['num']);
                    $objActSheet->setCellValueExplicit('L' . $index, $value['pay_time'] ? date('Y-m-d H:i:s', $value['pay_time']) : '');
                    if ($value['status'] > 0 && $value['status'] < 10) {
                        $objActSheet->setCellValueExplicit('M' . $index, L_('已支付'));
                    } elseif ($value['status'] == 2) {
                        $objActSheet->setCellValueExplicit('M' . $index, L_('已完成'));
                    } elseif ($value['status'] == 0) {
                        $objActSheet->setCellValueExplicit('M' . $index, L_('未支付'));
                    } elseif ($value['status'] == 11) {
                        $objActSheet->setCellValueExplicit('M' . $index, L_('已取消'));
                    } elseif ($value['status'] == 10) {
                        $objActSheet->setCellValueExplicit('M' . $index, L_('超时未支付'));
                    }
                    $objActSheet->setCellValueExplicit('N' . $index, removeEmojiChar($value['note']));
                    if ($value['is_delay'] == 1) {
                        $objActSheet->setCellValueExplicit('O' . $index, L_('已延期'));
                    } else {
                        $objActSheet->setCellValueExplicit('O' . $index, $deliver_status[$value['deliver_status']]);
                    }
                    $objActSheet->setCellValueExplicit('P' . $index, $value['merchant_name']);
                }


            }
            sleep(2);
        }

    }

    public function export_village_group_trade(&$objProps,&$objExcel,$param)
    {
        $param['type'] = 'village_group_trade';
        $title = L_('X1统计', array('X1' => C('config.village_group_alias')));
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        // 设置当前的sheet
        $objExcel->setActiveSheetIndex(0);

        $condition_where = '1=1 ';
        $where = array();

        // 社区
        $village_list = $param['village_list'];  
        if ($village_list && $village_list != -1) {
            $where['a.id'] = array('in', $village_list);
        }

        // 社区所在城市
        if ($param['area_id'] && $param['area_id'] != 'undefined') {
            $where['a.area_id'] = $param['area_id'];
        } else if ($param['city_idss'] && $param['city_idss'] != 'undefined') {
            $where['a.city_id'] = $param['city_idss'];
        } else if ($param['province_idss'] && $param['province_idss'] != 'undefined') {
            $where['a.province_id'] = $param['province_idss'];
        }

        // 筛选时间
        if ($param['selectTimeType'] > 0) {
            $start_time = strtotime(date('Y-m-d', time() - ($param['selectTimeType'] - 1) * 86400));
            $end_time = time();
        }

        if ($param['begin_time'] && $param['end_time']) {
            $start_time = strtotime($param['begin_time']);
            $end_time = strtotime($param['end_time']) + 86400;
            unset($_GET['selectTimeType']);
        }
        if ($end_time < $start_time) {
            return array('error_code' => 1, 'msg' => L_('结束时间不能比开始时间小'));
        }
        if ($start_time) {
            $where['_string'] = "o.pay_time <= {$end_time} AND o.pay_time>={$start_time} ";
        }

        // 支付方式
        $pay_type = $param['pay_type'];
        if ($pay_type != 'all') {
            if ($pay_type == 'balance') {
                $where['pay_type'] = '';
                $where_pay = " ='' ";
            } elseif ($pay_type == 'alipay') {
                $where['pay_type'] = array('like', 'alipay%');
                $where_pay = " like 'alipay%' ";
            } else {
                $where['pay_type'] = $param['pay_type'];
                $where_pay = " ='" . $param['pay_type'] . "'";
            }
        }

        // 已支付
        $where['o.status'] = array('not in',array(0,10));

        $count = M('Village_group_order')->field("count(DISTINCT(o.start_uid)) as count")->join('as o left join '.C('DB_PREFIX').'village_group_pick_address as a on a.header_uid=o.start_uid' )->where($where)->select();
        $count = $count[0]['count'];

        set_time_limit(0);
        $length = ceil($count / 1000);

        $mer_temp = $user_temp = $leader_temp = array();
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle(L_('第X1个一千个订单信息', (array('X1' => $i + 1))));
            $objActSheet = $objExcel->getActiveSheet();
            $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);

            $objActSheet->setCellValue('A1', L_('日期'));
            $objActSheet->setCellValue('B1', L_('社区信息'));
            $objActSheet->setCellValue('C1', L_('总成交额'));
            $objActSheet->setCellValue('D1', L_('总订单数'));
            $objActSheet->setCellValue('E1', L_('总佣金数'));
            $objActSheet->setCellValue('F1', L_('总退款金额'));

            $list = M('Village_group_order')->field("gh.name AS header_name,FROM_UNIXTIME(pay_time, '%Y-%m-%d') as date_id,sum(o.total_money) as total_money,count(order_id) as counts,a.name,sum(o.spread_money) as spread_money,sum(o.refund_money) as refund_money")->join('as o left join '.C('DB_PREFIX').'village_group_pick_address as a on a.header_uid=o.start_uid left join '.C('DB_PREFIX').'village_group_header as gh on gh.id=o.start_uid')->order("FROM_UNIXTIME(pay_time, '%Y-%m-%d') DESC")->group('o.start_uid')->where($where)->limit($i * 1000, 1000)->select();

            if (!empty($list)) {
                $index = 1;
                foreach ($list as $value) {
                    $index++;
                    $objActSheet->setCellValueExplicit('A' . $index, $value['date_id']);
                    $objActSheet->setCellValueExplicit('B' . $index, $value['name']?:'所属团长：'.$value['header_name']);
                    $objActSheet->setCellValueExplicit('C' . $index, round_number($value['total_money'],2));
                    $objActSheet->setCellValueExplicit('D' . $index, round_number($value['counts'], 2));
                    $objActSheet->setCellValueExplicit('E' . $index, round_number($value['spread_money'], 2));
                    $objActSheet->setCellValueExplicit('F' . $index,  round_number($value['refund_money'], 2));
                    
                }
            }
            sleep(2);
        }
    }

    // 分销佣金导出
    public function export_distributor_spread(&$objProps,&$objExcel,$param)
    {
        $param['type'] = 'distributor_spread';
        $title = L_('分销佣金记录');
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        // 设置当前的sheet
        $objExcel->setActiveSheetIndex(0);

        $condition_where = '1=1 ';
        $where = array();

       //搜索
        if (!empty($param['keyword'])) {
            if ($param['searchtype'] == 'pigcms_id') {
                $condition_distributor['d.pigcms_id'] = $param['keyword'];
            } else if ($param['searchtype'] == 'name') {
                $condition_distributor['d.name'] = array('like', '%' . $param['keyword'] . '%');
            } else if ($param['searchtype'] == 'phone') {
                $condition_distributor['d.phone'] = array('like', '%' . $param['keyword'] . '%');
            }
        }


        if($param['project_city_id']){
            $condition_distributor['d.project_city_id'] = $param['project_city_id'];
        }
        if($param['project_id']){
            $condition_distributor['d.project_id'] = $param['project_id'];
        }

        //状态
        if ($param['status'] != '') {
            $condition_distributor['d.status'] = $param['status'];
        }

        if (!empty($param['begin_time']) && !empty($param['end_time'])) {
            $period = array(strtotime($param['begin_time'] . " 00:00:00"), strtotime($param['end_time'] . " 23:59:59"));
            $condition_distributor['_string'] = " (d.add_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
        }

        $database_distributor_spread_list = D('Distributor_spread_list');
        $count = $database_distributor_spread_list->join(' as l left join '.C('DB_PREFIX').'distributor as d on d.uid=l.spread_uid left join '.C('DB_PREFIX').'user as u on u.uid=d.uid left join '.C('DB_PREFIX').'appoint_project as p on p.id=d.project_id')->where($condition_distributor)->count();

        set_time_limit(0);
        $length = ceil($count / 1000);

        $mer_temp = $user_temp = $leader_temp = array();
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle(L_('第X1个一千个订单信息', (array('X1' => $i + 1))));
            $objActSheet = $objExcel->getActiveSheet();
            $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('J')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('L')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('M')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('N')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('O')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('P')->setWidth(20);

            $objActSheet->setCellValue('A1', L_('所在城市'));
            $objActSheet->setCellValue('B1', L_('项目'));
            $objActSheet->setCellValue('C1', L_('姓名'));
            $objActSheet->setCellValue('D1', L_('用户昵称'));
            $objActSheet->setCellValue('E1', L_('手机号'));
            $objActSheet->setCellValue('F1', L_('订单编号'));
            $objActSheet->setCellValue('G1', L_('订单类型'));
            $objActSheet->setCellValue('H1', L_('商户名称'));
            $objActSheet->setCellValue('I1', L_('店铺名称'));
            $objActSheet->setCellValue('J1', L_('商品编码'));
            $objActSheet->setCellValue('K1', L_('商品名称'));
            $objActSheet->setCellValue('L1', L_('佣金比例'));
            $objActSheet->setCellValue('M1', L_('佣金总额'));
            $objActSheet->setCellValue('N1', L_('下单时间'));
            $objActSheet->setCellValue('O1', L_('结算状态'));
            $objActSheet->setCellValue('P1', L_('结算时间'));

            $list = $database_distributor_spread_list->field('l.*,d.city_id,d.name,d.phone,p.project_name,u.nickname')->join(' as l left join '.C('DB_PREFIX').'distributor as d on d.uid=l.spread_uid left join '.C('DB_PREFIX').'user as u on u.uid=d.uid left join '.C('DB_PREFIX').'appoint_project as p on p.id=d.project_id')->where($condition_distributor)->order('l.add_time DESC')->limit($i * 1000, 1000)->select();
            if (!empty($list)) {
                $index = 1;
                $user_city_ids = array_filter(array_unique(array_column($list,'city_id')));
                $user_city_arr = array_column(M('Area')->field('`area_name`,`area_id`')->where(['area_id'=>['in',$user_city_ids]])->select(),'area_name','area_id');
                $order_type = array(
                        'group'=>C('config.group_alias_name'),
                        'mall'=>C('config.mall_alias_name'),
                        'appoint'=>C('config.appoint_alias_name')
                    );
                foreach ($list as &$value) {
                    $value['city_name'] = $user_city_arr[$value['city_id']];
                    if ($value['status']==1) {
                        $status = L_('已结算');
                    }elseif($value['status']==0){
                        $status = L_('未结算');
                    }else{
                        $status = L_('不予结算');                        
                    }

                    //查询商品商户信息
                    switch ($value['order_type']) {
                        case 'mall':
                            $good_detail = D('Shop_goods')->field('g.goods_id,g.name,g.distributor_percent,s.name as s_name,m.name as m_name')->join(' as g left join '.C('DB_PREFIX').'merchant_store as s on s.store_id=g.store_id left join '.C('DB_PREFIX').'merchant as m on m.mer_id=s.mer_id where g.goods_id='.$value['third_id'])->find();
                            fdump(D('Shop_goods')->_sql(),'order1',1);
                            break;
                        case 'appoint':
                            $good_detail = D('Appoint_order')->field('g.appoint_id as goods_id,g.appoint_name as name,g.distributor_percent,s.name as s_name,m.name as m_name')->join(' as o left join '.C('DB_PREFIX').'merchant as m on m.mer_id=o.mer_id left join '.C('DB_PREFIX').'appoint as g on o.appoint_id=g.appoint_id left join '.C('DB_PREFIX').'merchant_store as s on s.store_id=o.store_id where o.order_id='.$value['order_id'])->find();
                            fdump(D('Appoint_order')->_sql(),'order1',1);
                            break;
                        case 'group':
                            $good_detail = D('Group_order')->field('g.group_id as goods_id,g.name,g.distributor_percent,s.name as s_name,m.name as m_name')->join(' as o left join '.C('DB_PREFIX').'merchant_store as s on s.store_id=o.store_id left join '.C('DB_PREFIX').'merchant as m on m.mer_id=o.mer_id left join '.C('DB_PREFIX').'group as g on g.group_id=o.group_id where o.order_id='.$value['order_id'])->find();
                            fdump(D('Group_order')->_sql(),'order1',1);
                            break;
                    }

                    $index++;
                    $objActSheet->setCellValueExplicit('A' . $index, $value['city_name']);
                    $objActSheet->setCellValueExplicit('B' . $index, $value['project_name']);
                    $objActSheet->setCellValueExplicit('C' . $index, $value['name']);
                    $objActSheet->setCellValueExplicit('D' . $index, $value['nickname']);
                    $objActSheet->setCellValueExplicit('E' . $index, $value['phone']);
                    $objActSheet->setCellValueExplicit('F' . $index, $value['real_orderid'] ? $value['real_orderid']: $value['order_id']);
                    $objActSheet->setCellValueExplicit('G' . $index, $order_type[$value['order_type']]);
                    $objActSheet->setCellValueExplicit('H' . $index, $good_detail['m_name']);
                    $objActSheet->setCellValueExplicit('I' . $index, $good_detail['s_name']);
                    $objActSheet->setCellValueExplicit('J' . $index, $good_detail['goods_id']);
                    $objActSheet->setCellValueExplicit('K' . $index, $good_detail['name']);
                    $objActSheet->setCellValueExplicit('L' . $index, $good_detail['distributor_percent']);
                    $objActSheet->setCellValueExplicit('M' . $index, $value['money']);
                    $objActSheet->setCellValueExplicit('N' . $index, date('Y-m-d H:i:s',$value['add_time']));
                    $objActSheet->setCellValueExplicit('O' . $index, $status);
                    $objActSheet->setCellValueExplicit('P' . $index, $value['check_time'] ? date('Y-m-d H:i:s',$value['check_time']) : '');
                }
            }
            sleep(2);
        }
    }

    // 会员等级激活码
    public function export_activation_code(&$objProps,&$objExcel,$param)
    {
        $param['type'] = 'activation_code';
        $title = L_('会员等级激活码');
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        // 设置当前的sheet
        $objExcel->setActiveSheetIndex(0);

        $condition_where = '1=1 ';
        $where = array();

        //搜索
        if (!empty($param['keyword'])) {
            if ($param['searchtype'] == 'uid') {
                $where['a.uid'] = intval($param['keyword']);
            } else if ($param['searchtype'] == 'code_id') {
                $where['a.code_id'] = intval($param['keyword']);
            } else if ($param['searchtype'] == 'mer_id') {
                $where['a.mer_id'] = intval($param['keyword']);
            } else if ($param['searchtype'] == 'activation_code') {
                $where['a.activation_code'] = array('LIKE', '%'.$where['keyword'].'%');;
            }
        }
        if ($param['level_id']>0) {
            $where['a.level_id'] = $param['level_id'];
        }
        if ($param['status'] == -2) {
            $where['status'] = 0;
        } elseif ($param['status'] > 0) {
            $where['status'] = $param['status'];
        }
        // 激活时间范围查询
        if (!empty($param['begin_time']) && !empty($param['end_time'])) {
            $begin_time = strtotime($param['begin_time'] . " 00:00:00");
            $end_time = strtotime($param['end_time'] . " 23:59:59");
            $period = array($begin_time, $end_time);
            $where['_string'] = " (a.last_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
        }
        // 去除删除的信息
        $where['a.status'] = array('neq', 4);


        $database_user_level_activation_code = D('User_level_activation_code');
        $count = $database_user_level_activation_code->join('as a LEFT JOIN '.C('DB_PREFIX').'user_level as ul ON a.level_id=ul.id')->where($where)->count();

        set_time_limit(0);
        $length = ceil($count / 1000);

        $mer_temp = $user_temp = $leader_temp = array();
        $status_info = $database_user_level_activation_code->status_info;
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle(L_('第X1个一千个订单信息', (array('X1' => $i + 1))));
            $objActSheet = $objExcel->getActiveSheet();
            $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);

            $objActSheet->setCellValue('A1', L_('ID'));
            $objActSheet->setCellValue('B1', L_('激活码'));
            $objActSheet->setCellValue('C1', L_('用户ID'));
            $objActSheet->setCellValue('D1', L_('会员等级'));
            $objActSheet->setCellValue('E1', L_('商家ID'));
            $objActSheet->setCellValue('F1', L_('激活时间'));
            $objActSheet->setCellValue('G1', L_('最后修改时间'));
            $objActSheet->setCellValue('H1', L_('状态'));


            $list = $database_user_level_activation_code->join('as a LEFT JOIN '.C('DB_PREFIX').'user_level as ul ON a.level_id=ul.id')->field('a.*, ul.lname')->where($where)->order('a.code_id desc')->limit($i * 1000, 1000)->select();

            if (!empty($list)) {
                $index = 1;
                foreach ($list as &$value) {
                    $index++;
                    $objActSheet->setCellValueExplicit('A' . $index, $value['code_id']);
                    $objActSheet->setCellValueExplicit('B' . $index, $value['activation_code']);
                    $objActSheet->setCellValueExplicit('C' . $index, $value['uid']>0?$value['uid']:'没有绑定用户');
                    $objActSheet->setCellValueExplicit('D' . $index, $value['lname']);
                    $objActSheet->setCellValueExplicit('E' . $index, $value['mer_id']);
                    $objActSheet->setCellValueExplicit('F' . $index, $value['activation_time'] ? date('Y-m-d H:i:s',$value['activation_time']) : '');
                    $objActSheet->setCellValueExplicit('G' . $index, $value['last_time'] ? date('Y-m-d H:i:s',$value['last_time']) : date('Y-m-d H:i:s',$value['add_time']));
                    $objActSheet->setCellValueExplicit('H' . $index, $status_info[$value['status']]);
                }
            }
            sleep(2);
        }
    }

    // 平台会员开通记录
    public function export_open_level_log(&$objProps,&$objExcel,$param)
    {
        $param['type'] = 'open_level_log';
        $title = L_('会员等级激活码');
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        // 设置当前的sheet
        $objExcel->setActiveSheetIndex(0);

        $where = array();

        $where['log_type'] = 2; // 当前只查询开通记录
        $where['_string'] = 'level > 0'; // 当前只查询等级大于零的操作
        if ($param['level_id']>0) {
            $where['alevel_id'] = $param['level_id'];
        }
        // 激活时间范围查询
        if (!empty($param['begin_time']) && !empty($param['end_time'])) {
            $begin_time = strtotime($param['begin_time'] . " 00:00:00");
            $end_time = strtotime($param['end_time'] . " 23:59:59");
            $period = array($begin_time, $end_time);
            $where['_string'] .= " (open_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
        }
        // 查询全部 open_type 0 管理员 1 用户积分 2 用户余额 3 激活码
        if ($param['open_type'] == -2) {
            $where['open_type'] = 0;
        } elseif ($param['open_type'] > 0) {
            $where['open_type'] = $param['open_type'];
        }

        if ($param['level'] > 0) {
            $where['level'] = $param['level'];
        }
        if ($param['level_id'] == -2) {
            $where['level_id'] = 0;
        } elseif ($param['level_id'] > 0) {
            $where['level_id'] = $param['level'];
        }


        $database_user_level_open_log = D('User_level_open_log');
        $count = $database_user_level_open_log->where($where)->count();

        set_time_limit(0);
        $length = ceil($count / 1000);

        $open_type_info = $database_user_level_open_log->open_type_info;
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle(L_('第X1个一千个订单信息', (array('X1' => $i + 1))));
            $objActSheet = $objExcel->getActiveSheet();
            $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);

            $objActSheet->setCellValue('A1', L_('ID'));
            $objActSheet->setCellValue('B1', L_('用户ID'));
            $objActSheet->setCellValue('C1', L_('会员等级'));
            $objActSheet->setCellValue('D1', L_('开通方式'));
            $objActSheet->setCellValue('E1', L_('扣除积分'));
            $objActSheet->setCellValue('F1', L_('扣除余额'));
            $objActSheet->setCellValue('G1', L_('开通时间'));
            $objActSheet->setCellValue('H1', L_('到期时间'));
            $objActSheet->setCellValue('I1', L_('操作时间'));


            $list = $database_user_level_open_log->where($where)->field(true)->order('log_time desc, log_id desc')->limit($i * 1000, 1000)->select();

            if (!empty($list)) {
                $index = 1;
                foreach ($list as &$value) {
                    $index++;
                    $objActSheet->setCellValueExplicit('A' . $index, $value['log_id']);
                    $objActSheet->setCellValueExplicit('B' . $index, $value['uid']>0?$value['uid']:'-');
                    $objActSheet->setCellValueExplicit('C' . $index, $value['level']>0?$value['level']:'-');
                    $objActSheet->setCellValueExplicit('D' . $index, $open_type_info[$value['open_type']]);
                    $objActSheet->setCellValueExplicit('E' . $index, $value['score_used_count']>0 ? $value['score_used_count'] : '-');
                    $objActSheet->setCellValueExplicit('F' . $index, $value['balance_pay']>0 ? $value['balance_pay'] : '-');
                    $objActSheet->setCellValueExplicit('G' . $index, $value['open_time'] ? date('Y-m-d H:i:s',$value['open_time']) : date('Y-m-d H:i:s',$value['open_time']));
                    $objActSheet->setCellValueExplicit('H' . $index, $value['end_time'] ? date('Y-m-d H:i:s',$value['end_time']) : date('Y-m-d H:i:s',$value['end_time']));
                    $objActSheet->setCellValueExplicit('I' . $index, $value['log_time'] ? date('Y-m-d H:i:s',$value['log_time']) : date('Y-m-d H:i:s',$value['log_time']));
                }
            }
            sleep(2);
        }
    }
    
    /**
     * [putCsv description]
     * @param  string  $csvFileName [description] 文件名
     * @param  array   $dataArr     [description] 数组，每组数据都是使用，分割的字符串
     * @param  string  $haderText   [description] 标题（默认第一行）
     * @param  integer $line        [description] 从第几行开始写
     * @param  integer $offset      [description] 共计写几行
     * @return [type]               [description]
     */
    public function putCsv($csvFileName, $dataArr ,$haderText = '', $line = 0, $offset = 0){
        // 设置输出头部信息
        header('Content-Encoding: UTF-8');
        header("Content-Type: text/csv; charset=UTF-8");
        header("Content-Disposition: attachment; filename={$csvFileName}");

        $handle = fopen($csvFileName,"w");//写方式打开
        if(!$handle){
            return '文件打开失败';
        }
        
        fwrite($handle, chr(0xEF).chr(0xBB).chr(0xBF));
        
        //判断是否定义头标题
        if(!empty($haderText)){
            $re = fputcsv($handle,$haderText);//该函数返回写入字符串的长度。若出错，则返回 false。。
        }

        foreach ($dataArr as $key => $value) {
            /*店员打印订单身份证号*/
            /*if(isset($value[50])){
                $value[50]=$value[50]." ";
            }*/
            $re = fputcsv($handle,$value);//该函数返回写入字符串的长度。若出错，则返回 false。。
        }

        fclose($handle);
    }


    public function setPHPExcelHeader(&$excelClass, $config, $startRow, $defaultRowHeight = 20)
    {
        fdump($config,'letter',1);
        if (gettype($excelClass) == 'object') {
            if (is_array($config) && sizeof($config)) {
                foreach ($config as $k => $v) {
                    $letter = '';
                    $letter = $this->getLetterColumn($k);
                    fdump($letter,'letter',1);
                    if ($letter) {
                        $excelClass->getActiveSheet()->getColumnDimension($letter)->setWidth($v[0]);
                        $excelClass->getActiveSheet()->setCellValue($letter . $startRow, $v[1]);
                    }
                }
            }
            $excelClass->getActiveSheet()->getDefaultRowDimension()->setRowHeight($defaultRowHeight);
        }
        return $letter;
    }   

    public function getLetterColumn($k)
    {
        $a = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');
        $num = floor($k/26);
        $key = $k%26;
        $return = '';
        if ($k<26) {
            return $a[$key];
        }else{
            return $a[$num-1].$a[$key];
        }
    }
    public function mealCardBuyExport(&$objProps,&$objExcel,$param){
        $title = '商家饭票购买记录';
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        // 设置当前的sheet
        $objExcel->setActiveSheetIndex(0);
        
        $id = $param['id'];
        $model = M('Card_new_recharge_order');
        $where = array();
        //dump($_POST);exit;
        if($param['keyword']){
            if($param['searchtype']=='mer_name'){
                $where['m.name'] = array('like','%'.$param['keyword'].'%');
            }else{
                $where['u.nickname'] = array('like','%'.$param['keyword'].'%');
            }
        }
        if($id){
            $where['co.meal_card_id'] = $id;
        }
        $time_string = '';
        if(!empty($param['begin_time'])){
            $begin_time = strtotime($param['begin_time'] . " 00:00:00");
            $time_string .= 'po.pay_time>='.$begin_time;
        }
        if(!empty($param['end_time'])){
            $end_time =strtotime($param['end_time'] . " 23:59:59");
            if($time_string){
                $time_string .= ' AND po.pay_time<='.$end_time;
            }else{
                $time_string = ' AND po.pay_time<='.$end_time;
            }
        }
        if($time_string){
            $where['_string'] = $time_string;
        }
        $where['po.paid'] = 1;
        $where['po.business_type'] = 'card_new_recharge';
        $field = 'co.order_id,m.name as mer_name,u.nickname as user_name,co.money,po.pay_time,po.order_id as ppd';
        
        $count = $model->alias('co')
            ->join('left join '.C('DB_PREFIX').'plat_order as po on co.order_id=po.business_id')
            ->join('left join '.C('DB_PREFIX').'user as u on co.uid=u.uid')
            ->join('left join '.C('DB_PREFIX').'merchant as m on co.mer_id=m.mer_id')
            ->field($field)
            ->where($where)
            ->count();
            
        $length = ceil($count / 1000);
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle('第' . ($i + 1) . '个一千条购买记录');
            $objActSheet = $objExcel->getActiveSheet();

            $objActSheet->setCellValue('A1', '序号');
            $objActSheet->setCellValue('B1', '使用商家');
            $objActSheet->setCellValue('C1', '充值用户');
            $objActSheet->setCellValue('D1', '充值金额（元）');
            $objActSheet->setCellValue('E1', '充值时间');


            $list = $model->alias('co')
                    ->join('left join '.C('DB_PREFIX').'plat_order as po on co.order_id=po.business_id')
                    ->join('left join '.C('DB_PREFIX').'user as u on co.uid=u.uid')
                    ->join('left join '.C('DB_PREFIX').'merchant as m on co.mer_id=m.mer_id')
                    ->field($field)
                    ->where($where)
                    ->limit($i * 1000 . ',1000')->select();

            if (!empty($list)) {
                $index = 2;
                foreach ($list as $value) {

                    $objActSheet->setCellValueExplicit('A' . $index, $value['order_id']);
                    $objActSheet->setCellValueExplicit('B' . $index, $value['mer_name']);
                    $objActSheet->setCellValueExplicit('C' . $index, $value['user_name']);
                    $objActSheet->setCellValueExplicit('D' . $index, $value['money']);
                    $objActSheet->setCellValueExplicit('E' . $index, date('Y-m-d H:i:s', $value['pay_time']));
                    $index++;
                }
            }
            sleep(2);
        }
    }
    
    public function mealCardSpendExport(&$objProps,&$objExcel,$param){
        $title = '商家饭票消费记录';
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        // 设置当前的sheet
        $objExcel->setActiveSheetIndex(0);
        
        $where = array();
        if($param['keyword']){
            if($param['searchtype']=='mer_name'){
                $where['m.name'] = array('like','%'.$param['keyword'].'%');
            }else{
                $where['uu.nickname'] = array('like','%'.$param['keyword'].'%');
            }
        }
        $time_string = '';
        if(!empty($param['begin_time'])){
            $begin_time = strtotime($param['begin_time'] . " 00:00:00");
            $time_string .= 'c.time>='.$begin_time;
        }
        if(!empty($param['end_time'])){
            $end_time =strtotime($param['end_time'] . " 23:59:59");
            if($time_string){
                $time_string .= ' AND c.time<='.$end_time;
            }else{
                $time_string = ' AND c.time<='.$end_time;
            }
        }
        if($time_string){
            $where['_string'] = $time_string;
        }

        $count = M(_view('Card_new_record'))
            ->field('c.*,uu.phone,uu.nickname,m.name as mer_name')
            ->join('as c left join ' . C('DB_PREFIX') . 'card_userlist as u ON c.card_id = u.id left join ' . C('DB_PREFIX') . 'user as uu on uu.uid=u.uid')
            ->join('left join ' . C('DB_PREFIX') . 'merchant as m on u.mer_id=m.mer_id')
            ->where($where)
            ->order('c.time DESC')
            ->count();
        $length = ceil($count / 1000);
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle('第' . ($i + 1) . '个一千条消费记录');
            $objActSheet = $objExcel->getActiveSheet();

            $objActSheet->setCellValue('A1', '序号');
            $objActSheet->setCellValue('B1', '消费时间');
            $objActSheet->setCellValue('C1', '商家名称');
            $objActSheet->setCellValue('D1', '消费用户');
            $objActSheet->setCellValue('E1', '消费金额（元）');
            $objActSheet->setCellValue('F', '当前余额（元）');

            $list = M(_view('Card_new_record'))
                ->field('c.*,uu.phone,uu.nickname,m.name as mer_name')
                ->join('as c left join ' . C('DB_PREFIX') . 'card_userlist as u ON c.card_id = u.id left join ' . C('DB_PREFIX') . 'user as uu on uu.uid=u.uid')
                ->join('left join ' . C('DB_PREFIX') . 'merchant as m on u.mer_id=m.mer_id')
                ->where($where)
                ->order('c.time DESC')
                ->limit($i * 1000 . ',1000')->select();

            if (!empty($list)) {
                $index = 2;
                foreach ($list as $value) {

                    $objActSheet->setCellValueExplicit('A' . $index, $value['id']);
                    $objActSheet->setCellValueExplicit('B' . $index, date('Y-m-d H:i:s', $value['time']));
                    $objActSheet->setCellValueExplicit('C' . $index, $value['mer_name']);
                    $objActSheet->setCellValueExplicit('D' . $index, $value['nickname']);
                    $objActSheet->setCellValueExplicit('E' . $index, $value['money_use']);
                    $objActSheet->setCellValueExplicit('F' . $index, $value['current_money']);
                    $index++;
                }
            }
            sleep(2);
        }
        
    }
    
    public function shopExport(&$objProps,&$objExcel,$param)
    {
        $title = C('config.shop_alias_name') . L_('账单');
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        // 设置当前的sheet
        $objExcel->setActiveSheetIndex(0);
        $where_store = null;
        if (!empty($param['keyword']) && $param['searchtype'] == 's_name') {
            $where_store['name'] = array('like', '%' . $param['keyword'] . '%');
        }

        if ($param['system_session']['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $param['system_session']['area_id']))->find();
            if ($now_area['area_type'] == 3) {
                $area_index = 'area_id';
            } elseif ($now_area['area_type'] == 2) {
                $area_index = 'city_id';
            } elseif ($now_area['area_type'] == 1) {
                $area_index = 'province_id';
            }
            $where_store[$area_index] = $param['system_session']['area_id'];
        }

        if ($param['area_id'] && $param['area_id'] != 'undefined') {
            $where_store['area_id'] = $param['area_id'];
        } else if ($param['city_idss'] && $param['city_idss'] != 'undefined') {
            $where_store['city_id'] = $param['city_idss'];
        } else if ($param['province_idss'] && $param['province_idss'] != 'undefined') {
            $where_store['province_id'] = $param['province_idss'];
        }

        $store_ids = array();
        $where = array('platform' => 0);
        $condition_where = 'WHERE `o`.`platform`=0';
        if ($where_store) {
            $stores = D('Merchant_store')->field('store_id')->where($where_store)->select();
            foreach ($stores as $row) {
                $store_ids[] = $row['store_id'];
            }
            if ($store_ids) {
                $where['store_id'] = array('in', $store_ids);
                $condition_where .= ' AND o.store_id IN (' . implode(',', $store_ids) . ')';
            }
        }
        //区分线下零售
        $order_from = $param['order_from'];
        if ($order_from == 1) {
            $where['order_from'] = array('neq', 6);
            $condition_where .= ' AND o.order_from!=6';
        } elseif ($order_from == 6) {
            $where['order_from'] = 6;
            $condition_where .= ' AND o.order_from=6';
        }


        if (!empty($param['keyword'])) {
            if ($param['searchtype'] == 'real_orderid') {
                $where['real_orderid'] = htmlspecialchars($param['keyword']);
                $condition_where .= ' AND o.real_orderid = "' . htmlspecialchars($param['keyword']) . '"';
            } elseif ($param['searchtype'] == 'orderid') {
                $where['orderid'] = htmlspecialchars($param['keyword']);
                $tmp_result = M('Tmp_orderid')->where(array('orderid' => $where['orderid']))->find();
                unset($where['orderid']);
                $where['order_id'] = $tmp_result['order_id'];
                $condition_where .= ' AND o.order_id = ' . $tmp_result['order_id'];
            } elseif ($param['searchtype'] == 'name') {
                $where['username'] = htmlspecialchars($param['keyword']);
                $condition_where .= ' AND o.username = "' . htmlspecialchars($param['keyword']) . '"';
            } elseif ($param['searchtype'] == 'phone') {
                $where['userphone'] = htmlspecialchars($param['keyword']);
                $condition_where .= ' AND o.userphone = "' . htmlspecialchars($param['keyword']) . '"';
            } elseif ($param['searchtype'] == 'third_id') {
                $where['third_id'] = $param['keyword'];
                $condition_where .= ' AND o.third_id = "' . $param['keyword'] . '"';
            }

        }
        if ($param['merchant_session']) {
            $where['mer_id'] = $param['merchant_session']['mer_id'];
            $condition_where .= ' AND o.mer_id = ' . $param['store_session']['mer_id'];
        }
        if ($param['store_session']) {
            $where['store_id'] = $param['store_session']['store_id'];
            $condition_where .= ' AND o.store_id = ' . $param['store_session']['store_id'];
        }


        $status = isset($param['status']) ? intval($param['status']) : -1;
        if ($status != -1) {
            if ($status == 2) {
                $where['status'] = array('in', '2, 3');
            }else{
                $where['status'] = $status;
                if ($status === 0) {
                    $where['paid'] = 1;
                    $condition_where .= ' AND o.paid = 1 ';
                }
            }
        }

        $type = isset($param['type']) && $param['type'] ? $param['type'] : '';
        $sort = isset($param['sort']) && $param['sort'] ? $param['sort'] : '';
        $from_type = isset($param['from_type']) && $param['from_type'] ? $param['from_type'] : '';
        $shop_type = isset($param['shop_type']) && $param['shop_type'] ? $param['shop_type'] : '';
        $pay_type = isset($param['pay_type']) && $param['pay_type'] ? $param['pay_type'] : '';
        if ($sort != 'DESC' && $sort != 'ASC') $sort = '';
        if ($type != 'price' && $type != 'pay_time') $type = '';

        if ($status == 11) {
            $where['status'] = 2;
            $where['is_apply_refund'] = 1;
            $condition_where .= ' AND o.status=2 AND o.is_apply_refund=1';
        } elseif ($status == 100) {
            $where['paid'] = 0;
            $condition_where .= ' AND o.paid=0';
        } else if ($status == 2) {
            $where['status'] = ['IN', [2, 3]];
            $condition_where .= ' AND o.status IN (2,3)';
        } else if ($status == '0') {
            $where['status'] = ['IN', [0, 9]];
            $condition_where .= ' AND o.status IN (0,9)';
        } else if ($status != -1) {
            $where['status'] = $status;
            $condition_where .= ' AND o.status=' . $status;
        }

        if ($pay_type && $pay_type != 'balance') {
            $where['pay_type'] = $pay_type;
            $condition_where .= ' AND o.pay_type="' . $pay_type . '"';
        } else if ($pay_type == 'balance') {
            $where['_string'] = "(`balance_pay`<>0 OR `merchant_balance` <> 0 )";
            $condition_where .= ' AND (`o`.`balance_pay`<>0 OR `o`.`merchant_balance` <> 0 )';
        }

        if (!empty($param['begin_time']) && !empty($param['end_time'])) {
            if ($param['begin_time'] > $param['end_time']) {
                return false;
            }
            $searchTimeType = $param['searchTimeType'] ? $param['searchTimeType'] : 'create_time';
            $period = array(strtotime($param['begin_time'] . " 00:00:00"), strtotime($param['end_time'] . " 23:59:59"));
            $where['_string'] .= ($where['_string'] ? ' AND ' : '') . " (o.".$searchTimeType." BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            $condition_where .= " AND (o.".$searchTimeType." BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
        }
        //判断是否属于商城订单
        if($from_type==2||$shop_type==2){//商城订单
            $where['order_from']=1;
            $condition_where.=' and o.order_from=1';
        }else{
            $where['order_from']=array('neq',1);
            $condition_where.=' and o.order_from!=1';
        }
        $sql_count = "SELECT count(*) as count FROM " . C('DB_PREFIX') . "shop_order AS o INNER JOIN " . C('DB_PREFIX') . "merchant_store AS s ON s.store_id=o.store_id INNER JOIN " . C('DB_PREFIX') . "merchant AS m ON `s`.`mer_id`=`m`.`mer_id` INNER JOIN " . C('DB_PREFIX') . "shop_order_detail AS d ON `d`.`order_id`=`o`.`order_id` " . $condition_where;
        $count = M()->query($sql_count);
        $count = $count[0]['count'];


        $length = ceil($count / 1000);
        $m_area = M('Area');
        $m_shop_order_detail = M('Shop_order_detail');
        $m_merchant_store_shop = M("Merchant_store_shop");
        $m_merchant_store = M("Merchant_store");
        $m_shop_goods = M('shop_goods');
        $d_shop_discount = D('Shop_discount');
        $m_merchant_money_list = M('Merchant_money_list');

        // 分类表单填写项
        $category_name = array();
        $category_list = M(_view('Shop_category'))->field(true)->where(array('status'=>1))->select();
        foreach ($category_list as $key => $value) {
            $value['cue_field'] = unserialize($value['cue_field']);
            foreach ($value['cue_field'] as $_cue_field) {
                $category_name[] = $_cue_field['name'];
            }
        }
        $lang = $param['lang'];
        C('config.tmp_system_lang',$param['lang']);
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);
            $objExcel->getActiveSheet()->setTitle(L_('第X1个一千条订单信息', ($i + 1)));
            $objActSheet = $objExcel->getActiveSheet();
          

           $title_arr = array(
                array(40,L_('订单编号')),
                array(40,L_('订单流水号')),
                array(20,L_('商品名称')),
                array(20,L_('商品进价')),
                array(20,L_('单价')),
                array(20,L_('单位')),
                array(20,L_('规格/属性')),
                array(20,L_('数量')),
                array(20,L_('商家名称')),
                array(20,L_('店铺名称')),
                array(20,L_('客户姓名')),
                array(20,L_('客户电话')),
                array(20,L_('客户地址')),
                array(20,L_('订单总价')),
                array(20,L_('平台优惠')),
                array(20,L_('商家优惠')),
                array(20,L_('实付总价')),
                array(20,L_('在线支付金额')),
                array(20,L_('原平台配送费')),
                array(20,L_('惠后平台配送费')),
                array(20,L_('商家配送费')),
                array(20,L_('支付时间')),
                array(20,L_('送达时间')),
                array(20,L_('订单状态')),
                array(20,L_('支付情况')),
                array(20,L_('第三方流水号')),
                array(20,L_('区域商圈')),
                array(20,L_('平台抽佣金额')),
                array(20,L_('骑士应交账金额（货到付款订单）')),
                array(20,L_('平台满减金额（平台出补贴）')),
                array(20,L_('平台满减金额（商家出补贴）')),
                array(20,L_('平台券金额')),
                array(20,L_('商家券金额')),
                array(20,L_('平台承担配送费补贴金额')),
                array(20,L_('商家承担配送费补贴金额')),
                array(20,L_('店铺折扣金额')),
                array(20,L_('分类折扣金额')),
                array(20,L_('限时优惠金额')),
                array(20,L_('平台会员等级优惠金额')),
                array(20,L_('店铺满减金额')),
                array(20,L_('店铺首单满减金额')),
                array(20,L_('平台给商家结款金额')),
                array(20,L_('期望送达时间')),
                array(20,L_('取单号')),
                array(20,L_('取餐方式')),
                array(20,L_('备注')),
            );
            if ($category_name) {
                $category_name_format = array_unique($category_name);
                foreach ($category_name_format as $key => $value) {
                    $category_name_format[$key] = array('20',$value);
                }
                $title_arr = array_merge($title_arr,$category_name_format);
            }


            //参数依次为excel对象、表头数组、表头起始行、默认行高
            $lastColumn = $this->setPHPExcelHeader($objExcel, $title_arr, 1, 15);



            $sql = "SELECT  o.*,d.goods_id, (SELECT m.name FROM " . C('DB_PREFIX') . "merchant AS m WHERE o.mer_id=m.mer_id) AS merchant_name,d.name as good_name,d.price as good_price ,d.spec,d.unit,d.cost_price, d.num as good_num,(SELECT s.name FROM " . C('DB_PREFIX') . "merchant_store AS s WHERE s.store_id=o.store_id) AS store_name , (SELECT mm.money FROM " . C('DB_PREFIX') . "merchant_money_list AS mm WHERE mm.order_id = o.real_orderid AND mm.type = 'shop' LIMIT 1) AS income_money FROM " . C('DB_PREFIX') . "shop_order AS o INNER JOIN " . C('DB_PREFIX') . "shop_order_detail AS d ON `d`.`order_id`=`o`.`order_id` " . $condition_where . " ORDER BY o.order_id DESC LIMIT " . $i * 1000 . ",1000";
            // 平台给商家结款金额 Merchant_money_list money left join '.C('DB_PREFIX') . 'merchant_money_list mm ON mm.order_id = o.real_orderid AND mm.type = 'shop'
            $result_list = D()->query($sql);
            fdump($result_list,'result_list');

            //商户名称多语言替换
            $result_list = D('Merchant')->change_merchant_name_lang($result_list,$lang);
            
            //多语言店铺名称
            $store_ids = array_column($result_list,'store_id');
            $store_name_list = D('Merchant_store')->get_store_name_lang($store_ids,$lang);
            //多语言环境商品信息
            $goods_id = array_column($result_list,'goods_id');
            $goods_info_list = D('Shop_goods')->get_goods_info_lang($goods_id,$lang);
            
            foreach($result_list as $key =>$val){
                //店铺名称替换
                $result_list[$key]['store_name'] = $store_name_list[$val['store_id']]?$store_name_list[$val['store_id']]:$val['store_name'];
                //商品信息替换
                $result_list[$key]['good_name'] = $goods_info_list[$val['goods_id']]['name_'.$lang]?$goods_info_list[$val['goods_id']]['name_'.$lang]:$val['good_name'];
                $result_list[$key]['unit'] = $goods_info_list[$val['goods_id']]['unit_'.$lang]?$goods_info_list[$val['goods_id']]['unit_'.$lang]:$val['unit'];
            }

            $tmp_id = 0;
            if (!empty($result_list)) {
                $index = 1;
                foreach ($result_list as $value) {
                    if($value['spec']){
                        $spec = explode(',',$value['spec']);
                        $spec_arr = [];
                        foreach($spec as $sv){
                            $spec_arr[] = L_($sv);
                        }
                        $value['spec'] = implode(',',$spec_arr);
                    }
                    
                    $column = 0;
                    if ($tmp_id == $value['real_orderid']) {
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['good_name']);
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['cost_price']);
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['good_price']);
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['unit']);
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['spec']);
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['good_num']);
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        // 自定义填写项
                      
                        foreach ($category_name_format as $_cat_name) {
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        }

                        $index++;
                    } else {
                        //用getLetterCllumn($column)来表示列，新增列只需$column++即可
                        //如下，就表示，向A2写入‘张三’，向B2写入‘男’

                        $circle_info = '';
                        $circle_id = $m_merchant_store->where(array('store_id' => $value['store_id']))->getField('circle_id');
                        if ($circle_id) {
                            // 获取对应商圈信息
                            $condition_area = array(
                                'area_id' => $circle_id
                            );
                            $circle_arr = $m_area->where($condition_area)->field('area_pid, area_name, area_ip_desc')->find();
                            if (!$circle_arr['area_ip_desc']) {
                                if ($circle_arr['area_pid']) {
                                    $condition_pid_area = array(
                                        'area_id' => $circle_arr['area_pid']
                                    );
                                    $circle_msg = $m_area->where($condition_pid_area)->getField('area_name');
                                    $circle_info = $circle_msg . $circle_arr['area_name'];
                                } else {
                                    $circle_info = $circle_arr['area_name'];
                                }
                            } else {
                                $circle_info = $circle_arr['area_ip_desc'];
                            }
                        }


                        $system_take = $m_merchant_money_list->where(array('order_id' => $value['real_orderid'], 'type' => 'shop'))->getField('system_take');


                        // 骑士应交账金额 $value['price']   货到付款  即  $value['pay_type'] == 'offline' 时候

                        // 满减优惠 （平台出补贴） 和 满减优惠 （商家补贴）
                        $shop_detail = $m_shop_order_detail->where(array('order_id' => $value['order_id']))->field(true)->select();
                        // 满减优惠 （平台出补贴）
                        $plat_full_money = 0;
                        // 满减优惠 （商家补贴）
                        $merchant_full_money = 0;
                        // 商家承担配送费补贴金额
                        $merchant_express_money = 0;
                        // 平台承担配送费补贴金额
                        $plat_express_money = 0;
                        // 店铺满减金额
                        $merchant_reduce_money = 0;
                        //店铺首单满减金额
                        $merchant_reduce_money_first = 0;

                        if ($value['discount_detail'] && @unserialize($value['discount_detail'])) {
                            $details = unserialize($value['discount_detail']);
                            foreach ($details as $_discount) {
                                if ($_discount['discount_type'] == 1 || $_discount['discount_type'] == 2) { // 平台首单满减,平台满减
                                    $plat_full_money += $_discount['plat_money'];
                                    $merchant_full_money += $_discount['merchant_money'];
                                }
                                if ($_discount['discount_type'] == 5) { // 平台配送订单满减
                                    $plat_express_money += $_discount['plat_money'];
                                    $merchant_express_money += $_discount['merchant_money'];
                                }
                                if ($_discount['discount_type'] == 3) { // 商家首单满减
                                    $merchant_reduce_money_first += $_discount['minus'];
                                }
                                if ($_discount['discount_type'] == 4) { // 商家满减
                                    $merchant_reduce_money += $_discount['minus'];
                                }
                            }
                        }

                        // 平台券金额（平台出补贴） $value['coupon_price']
                        // 商家券金额（商家出补贴） $value['card_price']

                        // 限时优惠金额
                        $limit_money = 0;
                        $leveloff_money = 0;
                        $store_discount_money = 0; // 店铺折扣金额
                        $cat_discount_money = 0; // 分类折扣金额
                        // 计算优惠 等级优惠
                        if ($shop_detail) {
                            foreach ($shop_detail as $k => $v) {
                                if ($v && $v['price'] <= $v['old_price']) {
                                    $limit_money += round((intval($v['num']) * floatval(($v['old_price'] - $v['price']))), 2);
                                }
                                $total_price = floatval($v['price'] * $v['num']);
                                if ($v['discount_type'] == 1) {
                                    $store_discount_money += round(($total_price - floatval($v['discount_price'])), 2);
                                } elseif ($v['discount_type'] == 2) {
                                    $cat_discount_money += round(($total_price - floatval($v['discount_price'])), 2);
                                } elseif ($v['discount_type'] == 3) {
                                    $leveloff_money += round(($total_price - floatval($v['discount_price'])), 2);
                                } elseif ($v['discount_type'] == 4) {
                                    // 先计算店铺折扣
                                    $discount_arr = explode(',', $v['discount_rate']);
                                    $first_discount_money = $total_price - ($total_price * $discount_arr[0] * 0.01);
                                    if ($first_discount_money > 0) {
                                        $store_discount_money += round($first_discount_money, 2);
                                    }
                                    // 再计算vip折扣
                                    $second_price = $total_price * $discount_arr[0] * 0.01; // 第二次打折的总价钱
                                    $second_discount_money = $second_price - ($second_price * $discount_arr[1] * 0.01);
                                    if ($second_discount_money > 0) {
                                        $leveloff_money += round($second_discount_money, 2);
                                    }
                                } elseif ($v['discount_type'] == 5) {
                                    // 先计算分类折扣
                                    $discount_arr = explode(',', $v['discount_rate']);
                                    $first_discount_money = $total_price - floatval($total_price * $discount_arr[0] * 0.01);
                                    if ($first_discount_money > 0) {
                                        $cat_discount_money += round($first_discount_money, 2);
                                    }
                                    // 再计算vip折扣
                                    $second_price = $total_price * $discount_arr[1] * 0.01; // 第二次打折的总价钱
                                    $second_discount_money = $second_price - floatval($second_price * $discount_arr[1] * 0.01);
                                    if ($second_discount_money > 0) {
                                        $leveloff_money += round($second_discount_money, 2);
                                    }
                                }
                            }
                        }
                        // 实付总价
                        $real_total_price = $value['price'] - $value['coupon_price'] - $value['card_price'];
                        $real_total_price = sprintf("%.2f", $real_total_price);
                        // 原平台配送费
                        $deliver_fee = floatval($value['freight_charge']) + $merchant_express_money + $plat_express_money;
                        $deliver_fee = sprintf("%.2f", $deliver_fee);

                        $index++;
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['real_orderid'].' ');
                        // 流水号
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, ($value['orderid'] ? $value['orderid'] : '').' ');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['good_name']);
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['cost_price']);
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['good_price']);
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['unit']);
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['spec']);
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['good_num']);
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['merchant_name']);
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['store_name']);
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['username']);
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['userphone'] . ' ');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['address'] . ' ');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, floatval($value['total_price']));
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, floatval($value['balance_reduce']));
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, floatval($value['merchant_reduce']));
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $real_total_price);
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, floatval($value['payment_money']));


                        if ($value['is_pick_in_store'] == 0) {
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $deliver_fee);
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, floatval($value['freight_charge']));
                        } else {
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, 0);
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, 0);
                        }

                        if ($value['is_pick_in_store'] == 1) {
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, floatval($value['freight_charge']));
                        } else {
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, 0);
                        }

                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['pay_time'] ? date('Y-m-d H:i:s', $value['pay_time']) : '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['use_time'] ? date('Y-m-d H:i:s', $value['use_time']) : '');
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, D('Shop_order')->status_list[$value['status']]);
                       $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, D('Pay')->get_pay_name($value['pay_type'], $value['is_mobile_pay'], $value['paid']));
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['third_id']."\t");
                       $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $circle_info);
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $system_take);
                        if ($value['pay_type'] == 'offline') {
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['price']);
                        } else {
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                        }
                       $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $plat_full_money);
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $merchant_full_money);
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['coupon_price']);
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['card_price']);
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $plat_express_money);
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $merchant_express_money);
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $store_discount_money);

                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $cat_discount_money);
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $limit_money);

                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $leveloff_money);
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $merchant_reduce_money);
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $merchant_reduce_money_first);
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['income_money']);
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['expect_use_time'] ? date('Y-m-d H:i:s', $value['expect_use_time']) : L_('无'));
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['fetch_number'] ? $value['fetch_number'] : '');

                        // 取餐方式
                        if ($value['is_pick_in_store'] == 2) {
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, L_('自提'));
                        } else {
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, L_('配送'));
                        }

                        // 备注
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['desc']);
                        
                        // 自定义填写项
                        $cue_field_arr =  $value['cue_field'] ? unserialize($value['cue_field']) : array();
                        $cue_field_format = array();
                        $cue_field_title = array();
                        foreach ($cue_field_arr as $_cue_field) {
                            $cue_field_format[$_cue_field['title']] = $_cue_field['txt'];
                            $cue_field_title[] = $_cue_field['title'];
                           
                        }
                        foreach ($category_name_format as $_cat_name) {
                            if (in_array($_cat_name[1],$cue_field_title)) {
                                $objExcel->getActiveSheet()->setCellValueExplicit($this->getLetterColumn($column++) . $index, $cue_field_format[$_cat_name[1]], \PHPExcel_Cell_DataType::TYPE_STRING);
                            }else{
                                $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, '');
                            }
                        }
                        
                        $index++;
                    }
                    if(C('config.site_url')<> C('config.export_orderid_except_site')){//该客户不需要合并同一订单显示
                        $tmp_id = $value['real_orderid'];
                    }

                }
            }
            sleep(2);
        }
    }
    
    // 交易数据
    public function tradeExport(&$objProps,&$objExcel,$param)
    {
        $title = L_('交易数据');
        $type = $param['type']; 
        $pay_type = $param['pay_type'];

        if (isset($param['order_from']) && $param['order_from'] == 6) {
            $where['order_from'] = array('eq', 6);
        } elseif (isset($param['order_from']) && $param['order_from'] == 0) {
            $where['order_from'] = array('neq', 6);
        }
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        // 设置当前的sheet
        $objExcel->setActiveSheetIndex(0);
        $objExcel->getActiveSheet()->setTitle($type);
        $objExcel->getActiveSheet()->mergeCells('A1:V1');
        $objExcel->getActiveSheet()->mergeCells('A3:H3');
        $objActSheet = $objExcel->getActiveSheet();
        $cell_trade = array(
            'date_id' => L_('日期'),
            'province' => L_('省份'),
            'city' => L_('市'),
            'area' => L_('区/县'),
            'platform' => L_('收入来源'),
            'type' => L_('业务'),
            'merchant_name' => L_('商家'),
            'store_name' => L_('店铺'),
            'total_money' => L_('营业金额'),
            'total_count' => L_('营业单数'),
            'consume_money' => L_('实收金额'),
            'consume_count' => L_('实收单数'),
            'refund_money' => L_('退款金额'),
            'refund_count' => L_('退款单数'),
            'alipay_money' => L_('支付宝交易金额'),
            'alipay_count' => L_('支付宝交易单数'),
            'weixin_money' => L_('微信交易金额'),
            'weixin_count' => L_('微信交易单数'),
            'balance_money' => L_('余额支付交易金额'),
            'balance_count' => L_('余额支付交易单数'),
            'score_money' => L_('积分抵扣金额'),
            'score_count' => L_('积分抵扣单数'),
        );

        $cell_name = 'cell_trade';
        $cell_count = count($$cell_name);
        $cell_start = 1;
        for ($f = 'A'; $f <= 'Z'; $f++, $cell_start++) {
            if ($cell_start > $cell_count) {
                break;
            }
            $col_char[] = $f;
        }
        $col_k = 0;
        $title_index = 2;
        $i = 4;
       
        $objActSheet->setCellValue('A1', L_('说明：已停用的支付方式不计入统计，如需查看，请查看具体业务下的订单记录'));
        $objActSheet->setCellValue('A3', L_('合计'));

        $param['type'] = $param['order_type'];
        $mer_list = $param['mer_list'];
        $store_list = $param['store_list'];
        $staff_list = $param['staff_list'];
        $type = $param['type'];

        if ($type == 'all') {
            $type_arr = array('group','shop','mall', 'meal','dining','appoint','store','sport','scenic','life_tools_appoint_join');
        }else{
            $type_arr[] =  $type;
        }
        // 营业总额
        $arr['total_money_sum'] = 0;
        // 实收总额
        $arr['consume_money_sum'] = 0;
        // 退款金额
        $arr['refund_money_sum'] = 0;

        // 营业总订单数
        $arr['total_money_count'] = 0;
        // 实收总订单数
        $arr['consume_money_count'] = 0;
        // 退款总订单数
        $arr['refund_money_count'] = 0;


        // 退款总订单数
        $arr['alipay_money_count'] = 0;
        $arr['alipay_money_sum'] = 0;
        $arr['weixin_money_count'] = 0;
        $arr['weixin_money_sum'] = 0;
        $arr['balance_money_count'] = 0;
        $arr['balance_money_sum'] = 0;
        $arr['score_money_count'] = 0;
        $arr['score_money_sum'] = 0;

        // 总数 用于计算页数
        $total_count = 0;

        // 记录已有日期
        $date_arr = array();
        // 排序字段
        $date_sort = array();
        $merchant_arr = array();
        $store_arr = array();
        $province = array();
        $city = array();
        $area = array();
        $ailas_name = $this->get_alias_name();
        $platform_arr = array(0 => L_('平台'), '1' => L_('饿了吗'), '2' => L_('美团'));
        foreach ($type_arr as $key => $type) {
            $where = array();
            if ($param['type']=='all') {
                $type_name = '全部';
            }else{
                $type_name = $ailas_name[$type];
            }
            $platform_name = $platform_arr[$param['platform']];
            if ($param['selectTimeType'] > 0) {
                $start_time = strtotime(date('Y-m-d', time() - ($param['selectTimeType'] - 1) * 86400));
                $end_time = time();
            }

            // 区域删选筛选
            $store_list_id = array();
            // 店铺筛选
            $store_list_id1 = array();
            if ($store_list && $store_list != -1) {
                $store_list_id1 = explode(',', $store_list);
            }

            if ($param['area_id'] && $param['area_id'] != 'undefined') {
                $where_store['area_id'] = $param['area_id'];
            } else if ($param['city_idss'] && $param['city_idss'] != 'undefined') {
                $where_store['city_id'] = $param['city_idss'];
            } else if ($param['province_idss'] && $param['province_idss'] != 'undefined') {
                $where_store['province_id'] = $param['province_idss'];
            }
            if ($where_store) {
                $store_ids = M('Merchant_store')->where($where_store)->select();

                foreach ($store_ids as $st) {
                    $store_list_id[] = $st['store_id'];
                }
                if (!$store_list_id) {
                    return array();
                }
            }


            if($store_list_id1&&$store_list_id){
                // 店铺和地区都筛选
                $store_list_id = array_intersect($store_list_id,$store_list_id1);
                $where_pay_dining['store_id'] = $where['store_id'] = array('in', $store_list_id);
                $where_meal .= " AND  store_id  in (".implode(',',$store_list_id).") ";
            }elseif (!$where_store&&$store_list_id1) {
                // 只筛选店铺
                $where_pay_dining['store_id'] = $where['store_id'] = array('in', $store_list_id1);
                $where_meal .= " AND  store_id  in (".implode(',',$store_list_id1).") ";
            }elseif ($where_store){
                // 只筛选地区
                $where_pay_dining['store_id'] = $where['store_id'] = array('in', $store_list_id);
                $where_meal .= " AND  store_id  in (".implode(',',$store_list_id).") ";
            }
            
            if ($param['begin_time'] && $param['end_time']) {
                $start_time = strtotime($param['begin_time']);
                $end_time = strtotime($param['end_time']) + 86400;
                unset($_GET['selectTimeType']);
            }
            if ($type == 'shop' && $param['platform']) {
                $where['platform'] = $param['platform'];
            }

            if ($end_time < $start_time) {
                return array('error_code' => 1, 'msg' => L_('结束时间不能比开始时间小'));
            }
            $param['mer_id'] && $where['mer_id'] = $param['mer_id'];

            if ($type == 'meal') {
                $Order = 'Foodshop_order';
                $where_meal = ' 1 = 1 ';
                $param['mer_id'] && $where_meal .= ' AND o.mer_id=' . $param['mer_id'];
            } else {
                $where['paid'] = 1;
                $Order = ucfirst($type) . '_order';
            }

            if ($mer_list && $mer_list != -1) {
                $where_pay_dining['mer_id'] = $where['mer_id'] = array('in', $mer_list);
                $where_meal .= " AND mer_id  in ({$mer_list}) ";
            }
      

            if ($staff_list && $staff_list != -1) {
                $where_pay_dining['staff_id'] = $where['staff_id'] = array('in', $staff_list);
                $where_meal .= " AND  staff_id  in ({$staff_list}) ";
            }


            if ($start_time) {
                if($type=='appoint'){
                    $where['_string'] = "order_time <= {$end_time} AND order_time>={$start_time}  ";
                    $where_meal .= " AND order_time <= {$end_time} AND order_time>={$start_time} ";
                }else {
                    $where_pay_dining['_string'] = $where['_string'] = "pay_time <= {$end_time} AND pay_time>={$start_time}  ";
                    $where_meal .= " AND pay_time <= {$end_time} AND pay_time>={$start_time} ";
                }
            }

            if ($pay_type != 'all') {
                if ($pay_type == 'balance') {
                    $where_pay_dining['pay_type'] = $where['pay_type'] = '';
                    $where_pay = " ='' ";
                } elseif ($pay_type == 'alipay') {
                    $where['pay_type'] = array('like', 'alipay%');
                    $where_pay_dining['pay_type'] = $where_pay = " like 'alipay%' ";
                } elseif ($pay_type == 'weixin_and_balance') { // 微信支付 + 余额支付
                    $where_pay_dining['pay_type'] = $where['pay_type'] = 'weixin';
                    $where = array_merge($where,['balance_pay|merchant_balance'=>['gt',0]]);
                    $where_pay_dining = array_merge($where_pay_dining,['system_balance|merchant_balance_pay|merchant_balance_give'=>['gt',0]]);
                    $where_pay = " ='" . $where['pay_type'] . "'";
                } elseif ($pay_type == 'alipay_and_balance') { // 支付宝支付 + 余额支付
                    $where_pay_dining['pay_type'] = $where['pay_type'] = array('like', 'alipay%');
                    $where = array_merge($where,['balance_pay|merchant_balance'=>['gt',0]]);
                    $where_pay_dining = array_merge($where_pay_dining,['system_balance|merchant_balance_pay|merchant_balance_give'=>['gt',0]]);
                    $where_pay = " like 'alipay%' ";
                } elseif ($pay_type == 'weixinapp') { // app微信支付
                    $where_pay_dining['pay_type'] = $where['pay_type'] = 'weixin';
                    $where_pay_dining['is_mobile_pay'] = $where['is_mobile_pay'] = 2;
                    $where_pay = " ='" . $param['pay_type'] . " AND is_mobile_pay = 2'";
                } else {
                    $where_pay_dining['pay_type'] = $where['pay_type'] = $param['pay_type'];
                    $where_pay = " ='" . $param['pay_type'] . "'";
                }

                if($type == 'dining'){// 新版餐饮支付方式查询
                    $where_pay_dining['paid'] = 1;
                    $dinng_order = array_column( M('Dining_order_pay')->where($where_pay_dining)->field('distinct(order_id)')->select(),'order_id');
                    $where['order_id'] = ['in', $dinng_order];
                }
            }

            //营业金额
            switch ($type) {
                case 'group':
                    $where['status'] = array('elt', 3);
                    $sum_name = 'total_money';
                    break;
                case 'shop':
                    $where['status'] = array('neq', '5,6');
                    $sum_name = 'price';
                    break;
                case 'mall':
                    unset($where['paid']);
                    $where['_string'] .= " AND ((status >= 10 AND status < 50) OR (status >=60 and  status < 70))";
                    $sum_name = 'money_total';
                    if(!empty($where['pay_type'])){
                        $where['online_pay_type'] = $where['pay_type'];
                        unset($where['pay_type']);
                        if($param['pay_type'] == 'weixin'){
                            $where['online_pay_type'] = 'wechat';
                        }
                    }
                    break;
                case 'meal':
                    $where['status'] = array('in', '3,4');
                    $sum_name = 'price';
                    break;                
                case 'dining':
                    $where['status'] = array('in', '4,20,30,40,41,50');
                    $sum_name = 'price';
                    break;
                case 'appoint':
                    unset($where['paid']);
                    if(isset($where['_string']) && ((strpos($where['_string'],'order_time') !== false) || (strpos($where['_string'],'pay_type') !== false))){
                        $where['_string'] .= ' AND (paid = 1 OR paid = 3)';
                    }else{
                        $where['_string'] .= ' (paid = 1 OR paid = 3)';
                    }
                    $sum_name = 'balance_pay+score_deducte+pay_money+merchant_balance+product_score_deducte
                    +product_balance_pay+product_coupon_price+user_pay_money+product_merchant_balance+card_price+coupon_price+product_card_price';
                    break;
                case 'store':
                    $sum_name = 'price';
                    if($where['_string']){
                        $where['_string'] .= ' AND ((business_id = 0 AND from_plat =1) OR from_plat in (2,3))';
                    }else{
                        $where['_string'] = '((business_id = 0 AND from_plat =1) OR from_plat in (2,3))';
                    }

                    break;
            }


            if ($type == 'meal') {
                if ($param['mer_id']) {
                    $store_ids = M('Merchant_store')->where(array('mer_id' => $param['mer_id']))->select();

                    foreach ($store_ids as $st) {
                        $store_list_id[] = $st['store_id'];
                    }
                    if ($store_list_id) {
                        $where_plat['store_id'] = implode(',', $store_list_id);
                        $where_plat['store_id'] = " AND o.store_id in (" . $where_plat['store_id'] . ") ";
                    }
                }

                $where_tm = $where;
                $where['pay_type'] && $where_tm['p.pay_type'] = $where['pay_type'];
                unset($where_tm['pay_type']);

                $counts = M('')->query("SELECT count(t.order_id) as count_ FROM ( SELECT o.order_id,o.store_id, o.pay_time, o.status, o.total_price, o.price, o.book_price, ( CASE WHEN o.pay_type = 0 THEN ( SELECT pay_type FROM pigcms_plat_order p WHERE p.business_type = 'foodshop' AND p.business_id = o.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) WHEN o.pay_type = 1 THEN ( SELECT pay_type FROM pigcms_store_order s WHERE s.business_type = 'foodshop' AND s.business_id = s.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) END ) AS pay_types FROM pigcms_foodshop_order AS o where " . $where_meal . " AND (o.status IN (3, 4) OR (o.status=5 AND o.pay_time>0) ) ) t " . ($where_pay ? 'WHERE t.pay_types ' . $where_pay : ''));
                // 营业总订单数
                $total_money_count = $counts[0]['count_'];


                $price = M('')->query("SELECT sum(t.total_price) as price_ FROM ( SELECT o.order_id,o.store_id, o.pay_time, o.status, o.total_price, o.price, o.book_price, ( CASE WHEN o.pay_type = 0 THEN ( SELECT pay_type FROM pigcms_plat_order p WHERE p.business_type = 'foodshop' AND p.business_id = o.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) WHEN o.pay_type = 1 THEN ( SELECT pay_type FROM pigcms_store_order s WHERE s.business_type = 'foodshop' AND s.business_id = s.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) END ) AS pay_types FROM pigcms_foodshop_order AS o where " . $where_meal . " AND (o.status IN (3, 4) OR (o.status=5 AND o.pay_time>0) ) ) t " . ($where_pay ? 'WHERE t.pay_types ' . $where_pay : ''));
                // 营业总额
                $total_money_sum = $price[0]['price_'];

            } else {
                // 营业总订单数
                $total_money_count = M($Order)->where($where)->count();

                // 营业总额
                $total_money_sum = M($Order)->where($where)->sum($sum_name);
            }
            if ($type == 'meal') {
                $subQuery = M($Order)->field('store_id')->join('AS o LEFT JOIN ( SELECT pay_type ,business_id,business_type,total_money FROM ' . C('DB_PREFIX') . 'plat_order where business_type="foodshop"  ' . $where_plat['store_id'] . ' AS p on p.business_id=o.order_id ')->where($where_tm)->group("FROM_UNIXTIME(pay_time, '%Y-%m-%d'),store_id")->buildSql();

                $counts = M('')->query("select count(m.order_id_) as count_ from (SELECT t.order_id as order_id_ FROM ( SELECT o.order_id,o.store_id, o.pay_time, o.status, o.total_price, o.price, o.book_price, ( CASE WHEN o.pay_type = 0 THEN ( SELECT pay_type FROM pigcms_plat_order p WHERE p.business_type = 'foodshop' AND p.business_id = o.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) WHEN o.pay_type = 1 THEN ( SELECT pay_type FROM pigcms_store_order s WHERE s.business_type = 'foodshop' AND s.business_id = s.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) END ) AS pay_types FROM pigcms_foodshop_order AS o where " . $where_meal . " AND o.status IN (3, 4) ) t " . ($where_pay ? 'WHERE t.pay_types ' . $where_pay : '') . " group by FROM_UNIXTIME(t.pay_time, '%Y-%m-%d'),t.store_id) as m");

                $count = $counts[0]['count_'];
            } else {
                if($type=='appoint'){
                    $subQuery = M($Order)->field('store_id')->where($where)->group("FROM_UNIXTIME(order_time, '%Y-%m-%d'),store_id")->buildSql();
                }else{
                    $subQuery = M($Order)->field('store_id')->where($where)->group("FROM_UNIXTIME(pay_time, '%Y-%m-%d'),store_id")->buildSql();
                }
                $count = M()->table($subQuery . ' a')->count();
            }
            if ($type == 'meal') {
                $total_money_sum_by_store = M('')->query("select * from (SELECT t.store_id,t.mer_id,count(t.order_id) as counts ,sum(t.total_price) as income,FROM_UNIXTIME(t.pay_time, '%Y-%m-%d') as date_id FROM ( SELECT o.order_id,o.mer_id,o.store_id, o.pay_time, o.status, o.total_price, o.price, o.book_price, ( CASE WHEN o.pay_type = 0 THEN ( SELECT pay_type FROM pigcms_plat_order p WHERE p.business_type = 'foodshop' AND p.business_id = o.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) WHEN o.pay_type = 1 THEN ( SELECT pay_type FROM pigcms_store_order s WHERE s.business_type = 'foodshop' AND s.business_id = s.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) END ) AS pay_types FROM pigcms_foodshop_order AS o where " . $where_meal . " AND o.status in (3,4)) t " . ($where_pay ? 'WHERE t.pay_types ' . $where_pay : '') . " group by FROM_UNIXTIME(t.pay_time, '%Y-%m-%d'),t.store_id) as m order by date_id DESC");
            }elseif($type=='appoint') {
                $total_money_sum_by_store = M($Order)->field("FROM_UNIXTIME(order_time, '%Y-%m-%d') as date_id,store_id,mer_id,sum({$sum_name}) as income,count(order_id) as counts")
                    ->where($where)->group("FROM_UNIXTIME(order_time, '%Y-%m-%d'),store_id")
                    ->order("FROM_UNIXTIME(order_time, '%Y-%m-%d') DESC,store_id DESC")
                    ->select();
            } else {
                $total_money_sum_by_store = M($Order)->field("FROM_UNIXTIME(pay_time, '%Y-%m-%d') as date_id,store_id,mer_id,sum({$sum_name}) as income,count(order_id) as counts")
                    ->where($where)->group("FROM_UNIXTIME(pay_time, '%Y-%m-%d'),store_id")
                    ->order("FROM_UNIXTIME(pay_time, '%Y-%m-%d') DESC,store_id DESC")
                    ->select();
            }
            //实收金额
            $where_ = $where;
            unset($where_['status']);
            empty($where_['_string']) && $where_['_string'] = ' 1=1 ';
            switch ($type) {
                case 'group':
                    $where_['_string'] .= ' AND  (status = 1 OR status = 2) ';
                    break;
                case 'shop':
                    $where_['_string'] .= ' AND status in(2,3,7,8,9,10) ';
                    break;
                case 'mall':
                    $where_['_string'] .= ' AND status in (30,31,32,33,40) ';
                    $sum_name = 'money_real';
                    break;
                case 'meal':
                    $where_['_string'] .= ' AND (status = 3 OR status = 4) ';
                    break;                
                case 'dining':
                    $where_['_string'] .= ' AND (status = 40 OR status = 41) ';
                    break;
                case 'appoint':
                    $where_['_string'] .= ' AND service_status>0 ';
                    break;
                case 'store':
		    $where_['_string'] .= ' AND refund = 0 ';
                    break;
            }
            if($type == 'shop') {
                if ($pay_type == 'balance') {
                    $sum_name='balance_pay';
                    $where_['balance_pay'] =array('gt',0);
                    unset($where_['score_deducte']);
                }
            }
            if ($type == 'meal') {
                $where_t = $where_;
                isset($where['pay_type']) && $where_t['p.pay_type'] = $where['pay_type'];
                unset($where_t['pay_type']);
                $counts = M('')->query("SELECT count(t.order_id) as count_ FROM ( SELECT o.order_id,o.store_id, o.pay_time, o.status, o.total_price, o.price, o.book_price, ( CASE WHEN o.pay_type = 0 THEN ( SELECT pay_type FROM pigcms_plat_order p WHERE p.business_type = 'foodshop' AND p.business_id = o.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) WHEN o.pay_type = 1 THEN ( SELECT pay_type FROM pigcms_store_order s WHERE s.business_type = 'foodshop' AND s.business_id = s.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) END ) AS pay_types FROM pigcms_foodshop_order AS o where " . $where_meal . " AND o.status IN (3, 4) ) t " . ($where_pay ? 'WHERE t.pay_types ' . $where_pay : ''));
                $consume_money_count = $counts[0]['count_'];

                $price = M('')->query("SELECT sum(t.price) as price_ FROM ( SELECT o.order_id,o.store_id, o.pay_time, o.status, o.total_price, o.price, o.book_price, ( CASE WHEN o.pay_type = 0 THEN ( SELECT pay_type FROM pigcms_plat_order p WHERE p.business_type = 'foodshop' AND p.business_id = o.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) WHEN o.pay_type = 1 THEN ( SELECT pay_type FROM pigcms_store_order s WHERE s.business_type = 'foodshop' AND s.business_id = s.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) END ) AS pay_types FROM pigcms_foodshop_order AS o where " . $where_meal . " AND o.status IN (3, 4) ) t " . ($where_pay ? 'WHERE t.pay_types ' . $where_pay : ''));

                $consume_money_sum = $price[0]['price_'];


                $consume_money_count_store = M('')->query("select * from (SELECT t.store_id,t.mer_id,count(t.order_id) as consume_count ,sum(price) as consume_money,FROM_UNIXTIME(t.pay_time, '%Y-%m-%d') as date_id FROM ( SELECT o.order_id,o.mer_id,o.store_id, o.pay_time, o.status, o.total_price, o.price, o.book_price, ( CASE WHEN o.pay_type = 0 THEN ( SELECT pay_type FROM pigcms_plat_order p WHERE p.business_type = 'foodshop' AND p.business_id = o.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) WHEN o.pay_type = 1 THEN ( SELECT pay_type FROM pigcms_store_order s WHERE s.business_type = 'foodshop' AND s.business_id = s.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) END ) AS pay_types FROM pigcms_foodshop_order AS o where " . $where_meal . " AND o.status in (3,4)) t " . ($where_pay ? 'WHERE t.pay_types ' . $where_pay : '') . " group by FROM_UNIXTIME(t.pay_time, '%Y-%m-%d'),t.store_id) as m order by date_id DESC");


            } else {

                $consume_money_count = M($Order)->where($where_)->count();

                $consume_money_sum = M($Order)->where($where_)->sum($sum_name);
                if($type=='appoint') {
                    $consume_money_count_store = M($Order)->where($where_)->group("FROM_UNIXTIME(order_time, '%Y-%m-%d'),store_id")->field("FROM_UNIXTIME(order_time, '%Y-%m-%d') as date_id ,store_id,count(order_id) as consume_count,sum({$sum_name}) as consume_money")->select();
                }else{
                    $consume_money_count_store = M($Order)->where($where_)->group("FROM_UNIXTIME(pay_time, '%Y-%m-%d'),store_id")->field("FROM_UNIXTIME(pay_time, '%Y-%m-%d') as date_id ,store_id,count(order_id) as consume_count,sum({$sum_name}) as consume_money")->select();
                }
            }
            foreach ($consume_money_count_store as $v) {
                $cosumen_count_tmp[$v['date_id']][$v['store_id']] = $v['consume_count'];
                $cosumen_money_tmp[$v['date_id']][$v['store_id']] = $v['consume_money'];
            }

            $where_['pay_type'] = 'alipayh5';
            $alipay_money_count = M($Order)->where($where_)->count();
            $alipay_money_sum = M($Order)->where($where_)->sum($sum_name);
            
            $alipay_money_count_store = M($Order)->where($where_)->group("FROM_UNIXTIME(pay_time, '%Y-%m-%d'),store_id")->field("FROM_UNIXTIME(pay_time, '%Y-%m-%d') as date_id ,store_id,count(order_id) as consume_count,sum({$sum_name}) as consume_money")->select();
            foreach ($alipay_money_count_store as $v) {
                $alipay_count_tmp[$v['date_id']][$v['store_id']] = $v['consume_count'];
                $alipay_money_tmp[$v['date_id']][$v['store_id']] = $v['consume_money'];
            }

            $where_['pay_type'] = 'weixin';
            if($type == 'store') {
                $sum_name='payment_money';
            }

            $weixin_money_count = M($Order)->where($where_)->count();
            $weixin_money_sum = M($Order)->where($where_)->sum($sum_name);
            $weixin_money_count_store = M($Order)->where($where_)->group("FROM_UNIXTIME(pay_time, '%Y-%m-%d'),store_id")->field("FROM_UNIXTIME(pay_time, '%Y-%m-%d') as date_id ,store_id,count(order_id) as consume_count,sum({$sum_name}) as consume_money")->select();
            foreach ($weixin_money_count_store as $v) {
                $weixin_count_tmp[$v['date_id']][$v['store_id']] = $v['consume_count'];
                $weixin_money_tmp[$v['date_id']][$v['store_id']] = $v['consume_money'];
            }

            $where_['score_deducte'] = array('gt', 0);
            $score_money_count = M($Order)->where($where_)->count();
            $score_money_sum = M($Order)->where($where_)->sum($sum_name);
            
            $balance_money_count_store = M($Order)->where($where_)->group("FROM_UNIXTIME(pay_time, '%Y-%m-%d'),store_id")->field("FROM_UNIXTIME(pay_time, '%Y-%m-%d') as date_id ,store_id,count(order_id) as consume_count,sum(score_deducte) as consume_money")->select();
            foreach ($balance_money_count_store as $v) {
                $score_count_tmp[$v['date_id']][$v['store_id']] = $v['consume_count'];
                $score_money_tmp[$v['date_id']][$v['store_id']] = $v['consume_money'];
            }
            unset($where_['score_deducte']);
            //退款金额
            switch ($type) {
                case 'group':
                    $where['status'] = 3;
                    break;
                case 'shop':
                    $where['status'] = ['in','4,5'];
                    break;
                case 'mall':
                    $where['_string'] = str_replace(' AND ((status >= 10 AND status < 50) OR (status >=60 and  status < 70))', '', $where['_string'] );
                    $where['status'] = 70;
                    $sum_name = 'money_refund';
                    break;
                case 'meal':
                    $where['status'] = 5;
                    break;                
                case 'dining':
                    $where['status'] = ['in', '50,51'];
                    $sum_name = 'refund_money';
                    break;
                case 'appoint':
                    $where['paid'] = 2;
                    break;
                case 'store':
                    $sum_name='price';
                    $where['refund'] = 1;
                    break;
            }


            if ($type == 'meal') {
                $counts = M('')->query("SELECT count(t.order_id) as count_ FROM ( SELECT o.order_id,o.store_id, o.pay_time, o.status, o.total_price, o.price, o.book_price, ( CASE WHEN o.pay_type = 0 THEN ( SELECT pay_type FROM pigcms_plat_order p WHERE p.business_type = 'foodshop' AND p.business_id = o.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) WHEN o.pay_type = 1 THEN ( SELECT pay_type FROM pigcms_store_order s WHERE s.business_type = 'foodshop' AND s.business_id = s.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) END ) AS pay_types FROM pigcms_foodshop_order AS o where " . $where_meal . " AND o.status =5 AND o.pay_time>0) t " . ($where_pay ? 'WHERE t.pay_types ' . $where_pay : ''));

                $refund_money_count = $counts[0]['count_'];

                $price = M('')->query("SELECT sum(t.price) as price_ FROM ( SELECT o.order_id,o.store_id, o.pay_time, o.status, o.total_price, o.price, o.book_price, ( CASE WHEN o.pay_type = 0 THEN ( SELECT pay_type FROM pigcms_plat_order p WHERE p.business_type = 'foodshop' AND p.business_id = o.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) WHEN o.pay_type = 1 THEN ( SELECT pay_type FROM pigcms_store_order s WHERE s.business_type = 'foodshop' AND s.business_id = s.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) END ) AS pay_types FROM pigcms_foodshop_order AS o where " . $where_meal . " AND o.status=5 AND o.pay_time>0) t " . ($where_pay ? 'WHERE t.pay_types ' . $where_pay : ''));

                $refund_money_sum = $price[0]['price_'];

                $refund_money_count_store = M('')->query("select * from (SELECT t.store_id,t.mer_id,count(t.order_id) as refund_count ,sum(t.price) as refund_money,FROM_UNIXTIME(t.pay_time, '%Y-%m-%d') as date_id FROM ( SELECT o.order_id,o.mer_id,o.store_id, o.pay_time, o.status, o.total_price, o.price, o.book_price, ( CASE WHEN o.pay_type = 0 THEN ( SELECT pay_type FROM pigcms_plat_order p WHERE p.business_type = 'foodshop' AND p.business_id = o.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) WHEN o.pay_type = 1 THEN ( SELECT pay_type FROM pigcms_store_order s WHERE s.business_type = 'foodshop' AND s.business_id = s.order_id " . $where_plat['store_id'] . " ORDER BY order_id DESC LIMIT 1 ) END ) AS pay_types FROM pigcms_foodshop_order AS o where " . $where_meal . " AND o.status=5 AND o.pay_time>0) t " . ($where_pay ? 'WHERE t.pay_types ' . $where_pay : '') . " group by FROM_UNIXTIME(t.pay_time, '%Y-%m-%d'),t.store_id) as m order by date_id DESC");


            } else {
                $refund_money_count = M($Order)->where($where)->count();
                $refund_money_sum = M($Order)->where($where)->sum($sum_name);
                if($type=='appoint') {
                    $refund_money_count_store = M($Order)->where($where)->group("FROM_UNIXTIME(order_time, '%Y-%m-%d'),store_id")->field("FROM_UNIXTIME(order_time, '%Y-%m-%d') as date_id,store_id,count(order_id) as refund_count,sum({$sum_name}) as refund_money")->select();
                }else{
                    $refund_money_count_store = M($Order)->where($where)->group("FROM_UNIXTIME(pay_time, '%Y-%m-%d'),store_id")->field("FROM_UNIXTIME(pay_time, '%Y-%m-%d') as date_id,store_id,count(order_id) as refund_count,sum({$sum_name}) as refund_money")->select();
                }
            }

            $refund_count_tmp = array();
            $refund_money_tmp = array();
            foreach ($refund_money_count_store as $v) {
                $refund_count_tmp[$v['date_id']][$v['store_id']] = $v['refund_count'];
                $refund_money_tmp[$v['date_id']][$v['store_id']] = $v['refund_money'];
            }

            if($type == 'store'){
                $where_['balance_pay'] =array('gt',0);
                $sum_name='balance_pay';
                unset($where_['score_deducte']);
                unset($where_['pay_type']);
            }else{
                $where_['pay_type'] = '';
            }
            $balance_money_count = M($Order)->where($where_)->count();
            $balance_money_sum = M($Order)->where($where_)->sum($sum_name);
            $balance_money_count_store = M($Order)->where($where_)->group("FROM_UNIXTIME(pay_time, '%Y-%m-%d'),store_id")->field("FROM_UNIXTIME(pay_time, '%Y-%m-%d') as date_id ,store_id,count(order_id) as consume_count,sum({$sum_name}) as consume_money")->select();
            foreach ($balance_money_count_store as $v) {
                $balance_count_tmp[$v['date_id']][$v['store_id']] = $v['consume_count'];
                $balance_money_tmp[$v['date_id']][$v['store_id']] = $v['consume_money'];
            }

            //线下支付
            $merchant_arr = array();
            $store_arr = array();
            foreach ($total_money_sum_by_store as $v) {
                if (!$merchant_arr[$v['mer_id']]) {
                    $merchant_arr[$v['mer_id']] = $merchant = M('Merchant')->field('mer_id,name,province_id,city_id,area_id')->where(array('mer_id' => $v['mer_id']))->find();
                } else {
                    $merchant = $merchant_arr[$v['mer_id']];
                }

                if (!$store_arr[$v['store_id']]) {
                    $store_arr[$v['store_id']] = $store = M('Merchant_store')->field('store_id,name,province_id,city_id,area_id')->where(array('store_id' => $v['store_id']))->find();
                } else {
                    $store = $store_arr[$v['store_id']];
                }
                if (!$province[$store['province_id']]) {
                    $province[$store['province_id']] = $province = M('Area')->where(array('area_id' => $store['province_id']))->find();
                } else {
                    $province = $province[$store['province_id']];
                }

                if (!$city[$store['city_id']]) {
                    $city[$store['city_id']] = $city = M('Area')->where(array('area_id' => $store['city_id']))->find();
                } else {
                    $city = $city[$store['city_id']];
                }

                if (!$area[$store['area_id']]) {
                    $area[$store['area_id']] = $area = M('Area')->where(array('area_id' => $store['area_id']))->find();
                } else {
                    $area = $area[$store['area_id']];
                }

                $tmp['merchant_name'] = $merchant['name'];
                $tmp['store_name'] = $store['name'];
                $tmp['province'] = $province['area_name'];
                $tmp['city'] = $city['area_name'];
                $tmp['area'] = $area['area_name'];
                $tmp['total_money'] = getFormatNumber($v['income']);
                $tmp['total_count'] = $v['counts'];
                $tmp['date_id'] = $v['date_id'];

                $tmp['type'] = $type_name;
                $tmp['platform'] = $platform_name;

                $tmp['consume_money'] = getFormatNumber($cosumen_money_tmp[$v['date_id']][$v['store_id']]);
                $tmp['consume_count'] = $cosumen_count_tmp[$v['date_id']][$v['store_id']];

                $tmp['alipay_money'] = round($alipay_money_tmp[$v['date_id']][$v['store_id']], 2);
                $tmp['alipay_count'] = $alipay_count_tmp[$v['date_id']][$v['store_id']];
                $tmp['weixin_money'] = round($weixin_money_tmp[$v['date_id']][$v['store_id']], 2);
                $tmp['weixin_count'] = $weixin_count_tmp[$v['date_id']][$v['store_id']];
                $tmp['balance_money'] = round($balance_money_tmp[$v['date_id']][$v['store_id']], 2);
                $tmp['balance_count'] = $balance_count_tmp[$v['date_id']][$v['store_id']];
                $tmp['score_money'] = round($score_money_tmp[$v['date_id']][$v['store_id']], 2);
                $tmp['score_count'] = $score_count_tmp[$v['date_id']][$v['store_id']];

                $tmp['refund_money'] = round($refund_money_tmp[$v['date_id']][$v['store_id']], 2);
                $tmp['refund_count'] = $refund_count_tmp[$v['date_id']][$v['store_id']];
                $date_store = $v['date_id'].'-'.$v['store_id'];
                if (in_array($date_store,$date_arr)) {
                    // 已存在 相加
                    $tmp['total_money'] += $arr['sale_date'][$date_store]['total_money'];
                    $tmp['consume_money'] += $arr['sale_date'][$date_store]['consume_money'];
                    $tmp['refund_money'] += $arr['sale_date'][$date_store]['refund_money'];
                    $tmp['total_count'] += $arr['sale_date'][$date_store]['total_count'];
                    $tmp['consume_count'] += $arr['sale_date'][$date_store]['consume_count'];
                    $tmp['refund_count'] += $arr['sale_date'][$date_store]['refund_count'];

                    $tmp['alipay_money'] += $arr['sale_date'][$date_store]['alipay_money'];
                    $tmp['alipay_count'] += $arr['sale_date'][$date_store]['alipay_count'];
                    $tmp['weixin_money'] += $arr['sale_date'][$date_store]['weixin_money'];
                    $tmp['weixin_count'] += $arr['sale_date'][$date_store]['weixin_count'];
                    $tmp['balance_money'] += $arr['sale_date'][$date_store]['balance_money'];
                    $tmp['balance_count'] += $arr['sale_date'][$date_store]['balance_count'];
                    $tmp['score_money'] += $arr['sale_date'][$date_store]['score_money'];
                    $tmp['score_count'] += $arr['sale_date'][$date_store]['score_count'];
                }else{
                    $date_arr[] = $date_store;
                    $date_sort[] = $v['date_id'];
                }

                $tmp['total_money'] = getFormatNumber($tmp['total_money']);
                $tmp['consume_money'] = getFormatNumber($tmp['consume_money']);
                $tmp['refund_money'] = getFormatNumber($tmp['refund_money']);
                $tmp['weixin_money'] = getFormatNumber($tmp['weixin_money']);
                $tmp['alipay_money'] = getFormatNumber($tmp['alipay_money']);
                $tmp['balance_money'] = getFormatNumber($tmp['balance_money']);
                $tmp['score_money'] = getFormatNumber($tmp['score_money']);
                $arr['sale_date'][$date_store] = $tmp;
            }

            $arr['total_money_sum'] += round(floatval($total_money_sum), 2);
            $arr['consume_money_sum'] += round(floatval($consume_money_sum), 2);
            $arr['refund_money_sum'] += round(floatval($refund_money_sum), 2);
            $arr['total_money_count'] += intval($total_money_count);
            $arr['consume_money_count'] += intval($consume_money_count);
            $arr['refund_money_count'] += intval($refund_money_count);

            $arr['alipay_money_sum'] += round(floatval($alipay_money_sum), 2);
            $arr['alipay_money_count'] += intval($alipay_money_count);
            $arr['weixin_money_sum'] += round(floatval($weixin_money_sum), 2);
            $arr['weixin_money_count'] += intval($weixin_money_count);
            $arr['balance_money_sum'] += round(floatval($balance_money_sum), 2);
            $arr['balance_money_count'] += intval($balance_money_count);
            $arr['score_money_sum'] += round(floatval($score_money_sum), 2);
            $arr['score_money_count'] += intval($score_money_count);
        }

        $objActSheet->setCellValue('I3', getFormatNumber($arr['total_money_sum']));
        $objActSheet->setCellValue('J3', floatval($arr['total_money_count']));
        $objActSheet->setCellValue('K3', getFormatNumber($arr['consume_money_sum']));
        $objActSheet->setCellValue('L3', floatval($arr['consume_money_count']));
        $objActSheet->setCellValue('M3', getFormatNumber($arr['refund_money_sum']));
        $objActSheet->setCellValue('N3', floatval($arr['refund_money_count']));
        $objActSheet->setCellValue('O3', getFormatNumber($arr['alipay_money_sum']));
        $objActSheet->setCellValue('P3', floatval($arr['alipay_money_count']));
        $objActSheet->setCellValue('Q3', getFormatNumber($arr['weixin_money_sum']));
        $objActSheet->setCellValue('R3', floatval($arr['weixin_money_count']));
        $objActSheet->setCellValue('S3', getFormatNumber($arr['balance_money_sum']));
        $objActSheet->setCellValue('T3', floatval($arr['balance_money_count']));
        $objActSheet->setCellValue('U3', getFormatNumber($arr['score_money_sum']));
        $objActSheet->setCellValue('V3', floatval($arr['score_money_count']));
        foreach ($cell_trade as $key => $v) {
            $objActSheet->getColumnDimension($col_char[$col_k])->setWidth(20);
            $objActSheet->setCellValue($col_char[$col_k] . $title_index, $v);
            $col_k++;
        }
        $index = 4 ;

        $arr['sale_date'] = array_values($arr['sale_date']);
        array_multisort($date_sort, SORT_DESC, $arr['sale_date']);
        foreach ($arr['sale_date'] as $v) { 
            $col_k = 0;
            foreach ($cell_trade as $k => $vv) {
                if(in_array($col_char[$col_k],['I','J','K','L','M','N','O','P','Q','R','S','T','U','V'])){
                    $objActSheet->setCellValueExplicit($col_char[$col_k] . $index, $v[$k],PHPExcel_Cell_DataType::TYPE_NUMERIC);
                }else{
                    $objActSheet->setCellValueExplicit($col_char[$col_k] . $index, $v[$k]);
                }
                $objActSheet->setCellValueExplicit($col_char[$col_k] . $index, $v[$k]);
                $col_k++;
            }
            $index++;
        }
    }

    // 批发市场
    public function marketSellExport(&$objProps,&$objExcel,$param)
    { 
        if ($param['order_type']=='buy') {
            $title = L_('批发进货订单');
            $param['type'] = 'market_buy';
        }else{
            $title = L_('批发销售订单');
            $param['type'] = 'market_sell';
         }
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        // 设置当前的sheet
        $objExcel->setActiveSheetIndex(0);

        //搜索

        if ($param['merchant_session']['mer_id']>0) {
            if ($param['order_type']=='buy') {
            $title = L_('批发进货订单');
                $where = array('mer_id' => $param['merchant_session']['mer_id']);
                $sqlWhere = ' WHERE `o`.`type`=0 AND `o`.`mer_id`=' . $param['merchant_session']['mer_id'];
            }else{
                $where = array('sell_mer_id' => $param['merchant_session']['mer_id']);
                $sqlWhere = ' WHERE `o`.`sell_mer_id`=' . $param['merchant_session']['mer_id'];
            }
        } else {
            $where = array();
            $sqlWhere = ' WHERE 1=1 ';
        }
        if ($param['keyword']) {
            if ($param['search_type'] == 'order_id') {
                $where['order_id'] = $param['keyword'];
                $sqlWhere .= ' AND `o`.`order_id`=' . $param['keyword'];
            }else if ($param['search_type'] == 'orderid') {
                $where['orderid'] = array('like', "%{$param['keyword']}%");
                $sqlWhere .= ' AND `o`.`orderid` Like "%' . $param['keyword'] . '%"';
            } elseif ($param['search_type'] == 'third_id') {
                $where['third_id'] = array('like', "%{$param['keyword']}%");
                $sqlWhere .= ' AND `o`.`third_id` Like "%' . $param['keyword'] . '%"';
            } elseif ($param['search_type'] == 'merchant_name') {
                $mer_info = M('Merchant')->where(array('name', array('like', "%{$param['keyword']}%")))->field('mer_id')->select();
                $mer_arr = array();
                if ($mer_info) {
                    foreach ($mer_info as $vals) {
                        $mer_arr[] = $vals['mer_id'];
                    }
                }
                $where['mer_id'] = array('in', $mer_arr);
                $sqlWhere .= ' AND `o`.`mer_id` IN ' . "(".implode(',',$mer_arr).")";
            } elseif ($param['search_type'] == 'username') {
                $where['username'] = array('like', "%{$param['keyword']}%");
                $sqlWhere .= ' AND `o`.`username` Like  "%' . $param['keyword'] . '%"';
            } elseif ($param['search_type'] == 'phone') {
                $where['userphone'] = array('like', "%{$param['keyword']}%");
                $sqlWhere .= ' AND `o`.`userphone` Like "%' . $param['keyword'] . '%"';
            }
        }

        if(isset($param['fid']) && $param['fid']>0){
            $sqlWhere .=  ' AND g.cat_fid=' . $param['fid'];
        }
        if(isset($param['cid']) && $param['cid']>0){
            $sqlWhere .=  ' AND g.cat_id=' . $param['cid'];
        }


        if ($param['status'] && $param['status'] != -1) {
            // -1 查询全部  -2 查询待付款(为了考虑兼容旧数据 0 不做状态查询) 1:待发货，2：待收货，3：收货成功，4:已退款，10：下单成功
            // 暂时只针对  待付款  待发货  待收货进行查询0
            if (-2 == $param['status']) {
                $where['_string'] = "(status=10 && pay_type!='offline') || (offline_status=0 && pay_type='offline')";
                $sqlWhere .= " AND ((o.status=10 && o.pay_type!='offline') || (o.offline_status=0 && o.pay_type='offline'))";
            } elseif ($param['status']==1) {
                $where['_string'] = "(status=1 && pay_type!='offline') || (status=1 && offline_status=1 && pay_type='offline')";
                $sqlWhere .= " AND ((o.status=1 && o.pay_type!='offline') || (o.status=1 && o.offline_status=1 && o.pay_type='offline'))";
            } elseif ($param['status']==2) {
                $where['status'] = 2;
                $sqlWhere .= ' AND o.status=2';
            } else {
                $where['status'] = $param['status'];
                $sqlWhere .= ' AND o.status='.$param['status'];
            }
        }
        if (isset($param['province_id']) && $param['province_id'] > 0) {
            if ($where['_string']) {
                $where['_string'] .= ' AND (province_id='. $param['province_id'] . ' OR province_id=0)';
            } else {
                $where['_string'] = 'province_id='. $param['province_id'] . ' OR province_id=0';
            }
            $sqlWhere .= ' AND (o.province_id=' . $param['province_id'] . ' OR o.province_id=0)';
        }
        if (isset($param['city_id']) && $param['city_id'] > 0) {
            if ($where['_string']) {
                $where['_string'] .= ' AND (city_id='. $param['city_id'] . ' OR city_id=0)';
            } else {
                $where['_string'] = 'city_id='. $param['city_id'] . ' OR city_id=0';
            }
            $sqlWhere .= ' AND (o.city_id=' . $param['city_id'] . ' OR o.city_id=0)';
        }
        if (isset($param['area_id']) && $param['area_id'] > 0) {
            if ($where['_string']) {
                $where['_string'] .= ' AND (area_id='. $param['area_id'] . ' OR area_id=0)';
            } else {
                $where['_string'] = 'area_id='. $param['area_id'] . ' OR area_id=0';
            }
            $sqlWhere .= ' AND (o.area_id=' . $param['area_id'] . ' OR o.area_id=0)';
        }

        if ($param['pay_type']!=-1 && $param['pay_type']) {
            if ($param['pay_type']=='merchant_money') {
                $where['pay_type'] = '';
                $sqlWhere .= " AND `o`.`pay_type`=''";
            } else {
                $where['pay_type'] = $param['pay_type'];
                $sqlWhere .= " AND `o`.`pay_type`='{$param['pay_type']}'";
            }
        }

        if ($param['is_pick_in_store']!=-1 && $param['is_pick_in_store']) {
            if ($param['is_pick_in_store']==-2) {
                $where['is_pick_in_store'] = 0;
                $sqlWhere .= ' AND `o`.`is_pick_in_store`=0';
            } else {
                $where['is_pick_in_store'] = $param['is_pick_in_store'];
                $sqlWhere .= " AND `o`.`is_pick_in_store`={$param['is_pick_in_store']}";
            }
        }

        if ($param['begin_time'] && $param['end_time']) {
            $start_time = strtotime($param['begin_time']);
            $end_time =  strtotime($param['end_time'])+60*60*24-1;
            $where['_string'] = "create_time BETWEEN {$start_time} AND {$end_time}";
            $sqlWhere .= " AND (`o`.`create_time`  BETWEEN  {$start_time} AND {$end_time})";
        }

        if($param['fid']>0 ||  $param['cid']>0){
            $sql = 'SELECT count(*) as num';
            $sql .= ' FROM ';
            $sql .= C('DB_PREFIX') . 'merchant AS m';
            $sql .= ' INNER JOIN ' . C('DB_PREFIX') . 'merchant_store AS s ON s.mer_id=m.mer_id';
            $sql .= ' INNER JOIN ' . C('DB_PREFIX') . 'market_order AS o ON o.sell_store_id=s.store_id';
            $sql .= ' INNER JOIN ' . C('DB_PREFIX') . 'merchant AS b ON o.mer_id=b.mer_id';
            $sql .= ' INNER JOIN ' . C('DB_PREFIX') . 'market_goods AS g ON o.goods_id=g.goods_id';
            $sql .= $sqlWhere;
            $sql .= ' ORDER BY o.pay_time DESC';
            $count_info = D('')->query($sql);
            $count = $count_info[0]['num'];
        } else {
            $count = M('Market_order')->where($where)->count();
        }

        if ($param['merchant_session']['mer_id']>0) {
            $sql = 'SELECT o.*, m.name AS merchant_name, m.phone AS merchant_phone';
            $sql .= ' FROM ' . C('DB_PREFIX') . 'market_order AS o INNER JOIN ' . C('DB_PREFIX') . 'merchant AS m ON o.mer_id=m.mer_id';
        } else {
            $sql = 'SELECT o.*, m.name AS merchant_name, m.phone AS merchant_phone, s.name AS store_name, s.phone AS store_phone, b.name AS buy_merchant_name, b.phone AS buy_merchant_phone';
            $sql .= ' FROM ';
            $sql .= C('DB_PREFIX') . 'merchant AS m';
            $sql .= ' INNER JOIN ' . C('DB_PREFIX') . 'merchant_store AS s ON s.mer_id=m.mer_id';
            $sql .= ' INNER JOIN ' . C('DB_PREFIX') . 'market_order AS o ON o.sell_store_id=s.store_id';
            $sql .= ' INNER JOIN ' . C('DB_PREFIX') . 'merchant AS b ON o.mer_id=b.mer_id';
            $sql .= ' INNER JOIN ' . C('DB_PREFIX') . 'market_goods AS g ON o.goods_id=g.goods_id';
        }

        $sql .= $sqlWhere;
        $sql .= ' ORDER BY o.order_id DESC';

        set_time_limit(0);
        $length = ceil($count / 1000);
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);
            $objExcel->getActiveSheet()->setTitle(L_('第X1个一千个订单信息', (array('X1' => $i + 1))));
            $objActSheet = $objExcel->getActiveSheet();

            $title_arr = array(
                array(20,L_('ID')),
                array(20,L_('订单编号')),
                array(20,L_('商家名称（卖家）')),
                array(20,L_('商家电话（卖家）')),
                array(20,L_('店铺名称（卖家）')),
                array(20,L_('店铺电话（卖家）')),
                array(20,L_('商品名称')),
                array(20,L_('单价')),
                array(20,L_('批发数量')),
                array(20,L_('总价')),
                array(20,L_('运费')),
                array(20,L_('优惠明细')),
                array(20,L_('商家名称（买家）')),
                array(20,L_('商家电话（买家）')),
                array(20,L_('联系人')),
                array(20,L_('联系电话')),
                array(20,L_('收货地址')),
                array(20,L_('订单状态')),
                array(20,L_('订单类型')),
                array(20,L_('配送方式')),
                array(20,L_('微信支付流水号')),
                array(20,L_('下单时间')),
                array(20,L_('支付时间')),
                array(20,L_('发货时间')),
                array(20,L_('收货时间')),
                array(20,L_('备注')),
                array(20,L_('平台结算给商家的金额')),
            );

            //参数依次为excel对象、表头数组、表头起始行、默认行高
            $lastColumn = $this->setPHPExcelHeader($objExcel, $title_arr, 1, 15);
            $sql .= ' LIMIT ' . $i * 1000 . ',1000';
            $list = D('')->query($sql);

            if (!empty($list)) {
                $index = 1;
                foreach ($list as &$value) {
                    //平台结算给商家的钱
                    $merchantMoney = M('Merchant_money_list')->where([
                        'order_id' => $value['order_id'],
                        'type'     => 'market_order',
                        'income'   => 1
                    ])->field('money')->find();
                    $value['settlement_merchant_money'] = $merchantMoney['settlement_merchant_money'] ?? 0;
                    
                    $index++;
                    $column = 0;
                    // ID
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['order_id']);
                    // 订单编号
                    $objExcel->getActiveSheet()->setCellValueExplicit($this->getLetterColumn($column++) . $index, $value['orderid'],\PHPExcel_Cell_DataType::TYPE_STRING);
                    // 商家名称（卖家）
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['merchant_name']);
                    // 商家电话（卖家）
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['merchant_phone']);
                    // 店铺名称
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['store_name']);
                    // 店铺电话
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['merchant_phone']);
                    // 商品名称
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['name']);
                    // 单价
                    if ($value['spec_price'] != '') {
                        $price = $value['spec_price'];
                    } else {
                        $price = $value['price'];
                    }
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $price);
                    // 批发数量
                    if ($value['unit']!='') {
                        $num = $value['num'] . '('.$value['unit'].')';
                    } else {
                        $num = $value['num'];
                    }
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $num);
                    // 总价
                    if ($value['actual_money']) {
                        $total = floatval($value['actual_money']);
                    } else {
                        $total = floatval($value['money']);
                    }
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $total);
                    // 运费
                    if ($value['total_express_fee']) {
                        $total_express_fee = floatval($value['total_express_fee']);
                    } else {
                        $total_express_fee = '-';
                    }
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $total_express_fee);
                    // 优惠明细
                    if ($value['discount_info']) {
                        $discount_info = json_decode($value['discount_info'], true);
                        $discount_info_txt = "批发满 {$discount_info['num']}{$value['unit']}，享受 {$discount_info['discount']} 折优惠";
                        if ($value['system_discount_info'] > 0) {
                            $money = floatval($value['money']);
                            $discount_info_txt .= ";享受 ".floatval($value['system_discount_info']/10)." 折优惠，实际以 {$money} ".C('config.Currency_txt')."购买;";
                        }
                    } else {
                        $discount_info_txt = '-';
                    }
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $discount_info_txt);
                    // 商家名称（买家）
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['buy_merchant_name']);
                    // 商家电话（买家）
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['buy_merchant_phone']);
                    // 联系人
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['username']);
                    // 联系电话
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['userphone']);
                    // 收货地址
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['address']);

                    // 订单状态
                    $status_txt = '';
                    switch ($value['status']) {
                        case 1:
                            if ($value['pay_type'] == 'offline' && $value['offline_status']==0) {
                                $status_txt = L_('线下支付').L_('（未支付）');
                            } elseif ($value['pay_type'] == 'offline' && $value['offline_status']==1) {
                                $status_txt = L_('线下支付').L_('（已支付）');
                            } else {
                                $status_txt = L_('已支付');
                            }
                            break;
                        case 2:
                            $status_txt = L_('已发货');
                            break;
                        case 3:
                            $status_txt = L_('已收货');
                            break;
                        case 4:
                            $status_txt = L_('已退款');
                            break;
                        case 10:
                            $status_txt = L_('未支付');
                            break;
                    }
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $status_txt);
                    // 订单类型
                    if ($value['type']==1) {
                        $type_txt = L_('虚拟批发');
                    } else {
                        $type_txt = L_('真实批发');
                    }
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $type_txt);
                    // 配送方式
                    if ($value['is_pick_in_store']==1) {
                        $delivery_txt = L_('快递发货');
                    } elseif ($value['is_pick_in_store']==2)  {
                        $delivery_txt = L_('物流发货');
                    } else {
                        $delivery_txt = L_('到店自提');
                    }
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $delivery_txt);
                    // 微信支付流水号
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['third_id']);


                    // 下单时间
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['create_time'] ? date('Y-m-d H:i:s',$value['create_time']) : '');
                    // 支付时间
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['pay_time'] ? date('Y-m-d H:i:s',$value['pay_time']) : '');
                    // 发货时间
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['send_time'] ? date('Y-m-d H:i:s',$value['send_time']) : '');
                    //  收货时间
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['pull_time']);
                    //备注
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['desc']??'');
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['settlement_merchant_money']);
                }
            }
            sleep(2);
        }
    } 

    // 商家联盟统计
    public function merUnionExport(&$objProps,&$objExcel,$param)
    {        
        $title = L_('商家联盟统计');
        $param['type'] = 'mer_union';
     
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        // 设置当前的sheet
        $objExcel->setActiveSheetIndex(0);

        //搜索       
        $mer_id = intval($param['merchant_session']['mer_id']);

        if ($param['order_type'] == 'shop') {
            $condition['type'] = array('in', array('shop', 'mall'));
        } elseif ($param['order_type'] != 'all' && !empty($param['order_type'])) {
            $condition['type'] = $param['order_type'];
        }

        if (isset($param['begin_time']) && isset($param['end_time']) && !empty($param['begin_time']) && !empty($param['end_time'])) {
            if ($param['begin_time'] > $param['end_time']) {
                $this->error(L_("结束时间应大于开始时间"));
            }
            $period = array(strtotime($param['begin_time'] . " 00:00:00"), strtotime($param['end_time'] . " 23:59:59"));
            $time_condition = " (use_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
            $condition['_string'] = $time_condition;
        }

        $database_merchant_card_union = D('Merchant_card_union');
        if ($param['union_type']==1) {
            // 其他商家待兑现给我的统计
            $condition_merchant['union_mer_id'] = $mer_id;
            $condition_merchant['is_del'] = 0;
            $merchant_card_union = $database_merchant_card_union->where($condition_merchant)->select();
            $mer_ids = array_column($merchant_card_union, 'mer_id');
            $condition['union_mer_id'] = $mer_id;
            $mer_list = $param['mer_list'];
            if ($mer_list && $mer_list != -1) {
                $mer_list = explode(',',$mer_list);
                $mer_list = $mer_list ? $mer_list : array();                
                $mer_ids = array_intersect($mer_list, $mer_ids);
            }
            $mer_id = implode(',', $mer_ids);
        }else{
            // 我待兑现给其他商家的统计 
            $condition_merchant['mer_id'] = $mer_id;
            $condition_merchant['is_del'] = 0;
            $merchant_card_union = $database_merchant_card_union->where($condition_merchant)->select();
            $mer_ids = array_column($merchant_card_union, 'union_mer_id');
            $condition['union_mer_id'] = $mer_ids;

            $mer_list = $param['mer_list'];
            if ($mer_list && $mer_list != -1) {
                $mer_list = explode(',',$mer_list);
                $mer_list = $mer_list ? $mer_list : array();
                $condition['union_mer_id'] = array_intersect($mer_list, $condition['union_mer_id']);
            }
        }
        $res = D('Merchant_money_list')->get_union_money_list($mer_id, 0, $condition);
        $count = $res['income_count'];
        set_time_limit(0);
        $length = ceil($count / 1000);
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);
            $objExcel->getActiveSheet()->setTitle(L_('第X1个一千个订单信息', (array('X1' => $i + 1))));
            $objActSheet = $objExcel->getActiveSheet();

            $title_arr = array(
                array(20,L_('订单编号')),
                array(20,L_('订单类型')),
                array(20,L_('商户信息')),
                array(20,L_('订单总额')),
                array(20,L_('商家会员卡消费总额')),
                array(20,L_('平台佣金')),
                array(20,L_('支付时间')),
            );

            //参数依次为excel对象、表头数组、表头起始行、默认行高
            $lastColumn = $this->setPHPExcelHeader($objExcel, $title_arr, 1, 15);
            $_GET['page'] = $i;
            $res = D('Merchant_money_list')->get_union_money_list($mer_id, 0, $condition,1000);
            $list = $res['income_list'];
            if (!empty($list)) {
                $index = 1;
                foreach ($list as &$value) {
                    $index++;
                    $column = 0;
                    // 订单编号
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['order_id']);
                    // 订单类型
                    $c_name = D('Merchant_money_list')->get_alias_c_name();
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $c_name[$value['type']]);
                    // 商户信息
                    $name = L_('商家名称').'：'.$value['name'];
                    if ($param['union_type'] ==1) {
                       $name .= L_('店铺名称').'：'.$value['store_name'];
                    }
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $name);

                    // 订单总额
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, getFormatNumber($value['total_money']));

                    // 商家会员卡消费总额
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, ($value['income']==2?'-':'+').getFormatNumber($value['money']));

                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, getFormatNumber($value['system_take']).($value['system_take']>0?'（'.L_('抽成比例').'：）'.$value['percent'] : ''));

                    // 支付时间
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['use_time'] ? date('Y-m-d H:i:s',$value['use_time']) : '');
                }
            }
            sleep(2);
        }
    }


    /**
     * 获取订单分类  目前只支持快店、到店付，其他按需开发扩展
     */
    public function getOrderTypeByTableName($tablename = '')
    {
        if ($tablename == 'shop_order') {
            return 'shop';
        } else if ($tablename == 'store_order') {
            return 'store';
        } else {
            return '';
        }
    }

    /**投票插件-投票详情*/
    public function pluginVoteExport(&$objProps,&$objExcel,$param)
    {
        $param['type'] = 'plugin_vote';
        $title = L_('投票详情');
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        // 设置当前的sheet
        $objExcel->setActiveSheetIndex(0);

        //搜索
        $where = array();
        if(!empty($param['vote_id'])){
            $where['vote_id'] = $param['vote_id'];
        } else {
            return;
        }
        $from = $param['from'];

        $d_plugin_vote_join = M('Plugin_vote_join');
        $d_plugin_vote_block = M('Plugin_vote_block');
        $d_plugin_vote_option = M('Plugin_vote_option');
        $d_house_village_user_bind = M('House_village_user_bind');
        $d_house_village_single = M('House_village_single');
        $d_house_village_floor = M('House_village_floor');
        $d_house_village = D('House_village');
        $d_area_street = D('Area_street');
        $d_user = M('User');
        // 以用户和投票id为分组查询 查询投票者信息（同一个用户-绑定身份用户记1）
        $plugin_vote_join_info = $d_plugin_vote_join->where($where)->group('vote_id, uid, bind_id')->field('join_id,bind_id,from')->select();
        $house_size_total = 0;
        if ($plugin_vote_join_info && (!$from || 'house' == $from)) {
            foreach ($plugin_vote_join_info as $val) {
                if ('house' == $val['from'] && $val['bind_id']>0) {
                    $housesize = $d_house_village_user_bind->where(array('pigcms_id' => $val['bind_id']))->getField('housesize');
                    if ($housesize && $housesize>0) {

                        // 如果面积大于0 累加
                        $house_size_total += $housesize;
                    }
                }
            }
        }
        $count = count($plugin_vote_join_info);
        // 查询对应区块信息
        $block_field = 'block_id, block_id, vote_id, block_title, block_type, is_write, block_sort, block_number, block_last_time';
        $where_block['vote_id'] = $param['vote_id'];
        $vote_block = $d_plugin_vote_block->where($where_block)->order('block_sort ASC')->getField($block_field,true);
        // 查询对应选项信息
        $option_field = 'option_id, option_id, vote_id, block_id, option_name, option_type, option_number, option_img, last_time';
        $where_option['vote_id'] = $param['vote_id'];
        $vote_option = $d_plugin_vote_option->where($where_option)->order('last_time ASC')->getField($option_field,true);


        set_time_limit(0);
        $length = ceil($count / 1000);
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);
            $objExcel->getActiveSheet()->setTitle(L_('第X1个一千个订单信息', (array('X1' => $i + 1))));
            $objActSheet = $objExcel->getActiveSheet();

            $title_arr = array(
                array(20,L_('用户')),
                array(20,L_('电话')),
                array(20,L_('住户身份')),
                array(20,L_('身份证号码')),
                array(20,L_('手机IP')),
                array(20,L_('投票时间'))
            );
            if (!$from || 'house' == $from) {
                $title_arr[] = array(20,L_('专有面积比'));
            }
            $title_arr[] = array(20,L_('地址'));
            foreach ($vote_block as &$value){
                $title_arr[] = array(20,L_($value['block_title']));
            }
//            $title_arr[] = array(20,L_('题目'));
//            $title_arr[] = array(20,L_('描述/选择'));


            //参数依次为excel对象、表头数组、表头起始行、默认行高
            $lastColumn = $this->setPHPExcelHeader($objExcel, $title_arr, 1, 15);

            $objExcel->getActiveSheet()->getStyle('D')->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);

            $list = $d_plugin_vote_join->where($where)->group('vote_id, uid, dis_key')->limit(($i * 1000) . ',1000')->select();

            if (!empty($list)) {
                $index = 1;
                foreach ($list as $k=>&$value) {
                    $index++;
                    $join_where = array(
                        'vote_id' => $value['vote_id'],
                        'uid' => $value['uid'],
                        'bind_id' => $value['bind_id'],
                        'dis_key' => $value['dis_key'],
                    );
                    $vote_join = $d_plugin_vote_join->where($join_where)->order('join_id ASC')->select();

                    $address = '';
                    $bind_info = array();
                    if ('house' == $value['from'] && $value['bind_id']>0) {
                        $bind_info = $d_house_village_user_bind->where(array('pigcms_id' => $value['bind_id']))->find();
                        if ($bind_info && $bind_info['housesize']>0) {
                            $value['housesize'] = $bind_info['housesize'];
                        } else {
                            $value['housesize'] = 0;
                        }
                        if (4==$bind_info['type']) {
                            $address = '小区工作人员';
                        } elseif (!$bind_info){
                            $address = '-';
                        } else {
                            // 处理投票者的基本信息
                            if ($bind_info) {
                                $value['name'] = $bind_info['name'];
                                $value['phone'] = $bind_info['phone'];
                            }
                            $single_name = $d_house_village_single->where(array('id'=>$bind_info['single_id']))->getField('single_name');
                            $floor_name = $d_house_village_floor->where(array('id'=>$bind_info['floor_id']))->getField('floor_name');
                            $word = [
                                'single_name' => $single_name,
                                'floor_name' => $floor_name,
                                'layer' => $bind_info['layer_num'],
                                'room' => $bind_info['room_addrss'],
                            ];
                            $address = $d_house_village->get_single_floor_room($bind_info['single_id'],$bind_info['floor_id'],$bind_info['layer_id'],$bind_info['vacancy_id'],$bind_info['village_id']);
                            if (!$address) {
                                $address = $bind_info['address'];
                            }
                        }
                    } elseif(('street' == $value['from'] || 'community' == $value['from']) && $value['bind_id']>0) {
                        $bind_info = $d_house_village_user_bind->where(array('pigcms_id' => $value['bind_id']))->find();
                        // 处理投票者的基本信息
                        if ($bind_info) {
                            $value['name'] = $bind_info['name'];
                            $value['phone'] = $bind_info['phone'];
                        }
                        $single_name = $d_house_village_single->where(array('id'=>$bind_info['single_id']))->getField('single_name');
                        $floor_name = $d_house_village_floor->where(array('id'=>$bind_info['floor_id']))->getField('floor_name');
                        $word = [
                            'single_name' => $single_name,
                            'floor_name' => $floor_name,
                            'layer' => $bind_info['layer_num'],
                            'room' => $bind_info['room_addrss'],
                        ];
                        $address = $d_house_village->get_single_floor_room($bind_info['single_id'],$bind_info['floor_id'],$bind_info['layer_id'],$bind_info['vacancy_id'],$bind_info['village_id']);
                        if (!$address) {
                            $address = $bind_info['address'];
                        }
                        // 单独处理街道的时候获取社区名称
                        if ('street' == $value['from'] && $bind_info['village_id']) {
                            $village_info = $d_house_village->where(array('village_id'=>$bind_info['village_id']))->field('village_id, village_name, street_id,community_id')->find();
                            $address = $village_info['village_name'] .' ' . $address;
                            if ($village_info['community_id']) {
                                $community_info = $d_area_street->where(array('area_id'=>$village_info['community_id']))->field('area_id, area_name')->find();
                                $address = $community_info['area_name'] .' ' . $address;
                            }
                        }
                    }
                    if($bind_info){
                        $house_type = M('House_village_user_vacancy')->where(['pigcms_id'=>$bind_info['vacancy_id']])->getField('house_type');
                        switch ($bind_info['type']){
                            case 0:
                                $list[$k]['relative'] = '房主';
                                break;
                            case 1:
                                $list[$k]['relative'] = '家属';
                                break;
                            case 2:
                                if($house_type == 1){
                                    $list[$k]['relative'] = '租客';
                                }else{
                                    $list[$k]['relative'] = '租客/员工';
                                }
                                break;
                            case 3:
                                $list[$k]['relative'] = '房主';
                                break;
                            default:
                                $list[$k]['relative'] = '工作人员';
                        }
                    }else{
                        $list[$k]['relative'] = '-';
                    }
                    if (empty($value['name']) && $value['uid']>0) {
                        $user_info = $d_user->where(array('uid'=>$value['uid']))->field('nickname, phone')->find();
                        // 处理投票者的基本信息
                        if ($user_info) {
                            $value['name'] = $user_info['nickname'];
                            $value['phone'] = $user_info['phone'];
                        }
                    }
                    if ($value['last_time']) {
                        $value['vote_time'] = date('Y-m-d H:i',$value['last_time']);
                    } elseif ($value['add_time']) {
                        $value['vote_time'] = date('Y-m-d H:i',$value['add_time']);
                    }



                    $column = 0;
                    // 用户名
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['name']);
                    // 电话
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['phone']);
                    //身份
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $list[$k]['relative']);
                    //身份证号码
                    $id_card = $bind_info['id_card'] ? strval($bind_info['id_card']).' ' : '';
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $id_card);
                    //手机IP
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['join_ip']);
                    // 投票时间
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['vote_time']);

                    if (!$from || 'house' == $from) {
                        // 专有面积比
                        $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['housesize'] . '/' . getFormatNumber($house_size_total));
                    }
                    // 地址
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $address);
                    if ($vote_join) {
                        foreach ($vote_join as $msg) {
                            if ($msg) {
                                $vote_msg = '';
                                if (3==$vote_block[$msg['block_id']]['block_type'] || 4==$vote_block[$msg['block_id']]['block_type']) {
                                    // 如果是选项获取选中选项信息
                                    if ($vote_option && $msg['option_id']) {
                                        $option_id = explode(',', $msg['option_id']);
                                        $choose_info = array();
                                        if ($option_id) {
                                            foreach ($option_id as $val) {
                                                if ($vote_option[$val]) {
                                                    $vote_option_info = $vote_option[$val];
                                                } else {
                                                    $vote_option_info = $d_plugin_vote_option->where(array('option_id' => $val))->find();
                                                }
                                                if (1==$vote_option_info['option_type']) {
                                                    $choose_info[] = '【其他】：'. $msg['option_txt'];
                                                } else {
                                                    $choose_info[] = $vote_option_info['option_name'];
                                                }
                                            }
                                        }
                                        if ($choose_info) {
                                            $vote_msg = implode(',', $choose_info);
                                        }
                                    }
                                }elseif (1==$vote_block[$msg['block_id']]['block_type'] || 2==$vote_block[$msg['block_id']]['block_type']) {
                                    $vote_msg = $msg['option_txt'];
                                }elseif (5==$vote_block[$msg['block_id']]['block_type']) {
                                    $vote_msg = $msg['option_num'];
                                }
                                fdump_api('-------------','$vote_join',1);
                                fdump_api($column,'$vote_join',1);
                                $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $vote_msg);
                            }
                        }
                    }
//                    if ($vote_join) {
//                        $column_index = $column++;
//                        $previous = '';
//                        $previous_dis_key = '';
//                        foreach ($vote_join as $msg) {
//                            if ($msg) {
//                                $now = $msg['vote_id'].'_'.$msg['uid'].'_'.$msg['bind_id'].'_'.$msg['from'].'_'.$msg['from_id'];
//                                $dis_key = $msg['dis_key'];
//                                if ($previous && $now==$previous && $previous_dis_key && $dis_key!=$previous_dis_key) {
//                                    $index++;
//                                }
//                                $previous = $now;
//                                $previous_dis_key = $dis_key;
//                                if ($msg['block_id'] && $vote_block && $vote_block[$msg['block_id']]) {
//                                    $index++;
//                                    // 题目
//                                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column_index) . $index, $vote_block[$msg['block_id']]['block_title']);
//                                    $vote_msg = '';
//                                    if (3==$vote_block[$msg['block_id']]['block_type'] || 4==$vote_block[$msg['block_id']]['block_type']) {
//                                        // 如果是选项获取选中选项信息
//                                        if ($vote_option && $msg['option_id']) {
//                                            $option_id = explode(',', $msg['option_id']);
//                                            $choose_info = array();
//                                            if ($option_id) {
//                                                foreach ($option_id as $val) {
//                                                    if ($vote_option[$val]) {
//                                                        $vote_option_info = $vote_option[$val];
//                                                    } else {
//                                                        $vote_option_info = $d_plugin_vote_option->where(array('option_id' => $val))->find();
//                                                    }
//                                                    if (1==$vote_option_info['option_type']) {
//                                                        $choose_info[] = '【其他】：'. $msg['option_txt'];
//                                                    } else {
//                                                        $choose_info[] = $vote_option_info['option_name'];
//                                                    }
//                                                }
//                                            }
//                                            if ($choose_info) {
//                                                $vote_msg = implode(',', $choose_info);
//                                            }
//                                        }
//                                    }elseif (1==$vote_block[$msg['block_id']]['block_type'] || 2==$vote_block[$msg['block_id']]['block_type']) {
//                                        $vote_msg = $msg['option_txt'];
//                                    }elseif (5==$vote_block[$msg['block_id']]['block_type']) {
//                                        $vote_msg = $msg['option_num'];
//                                    }
//                                    // 描述/选择
//                                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column_index+1) . $index, strval($vote_msg));
//                                }
//                            }
//
//                        }
//                    }
                }
            }
            sleep(2);
        }
    }

    /**营业分析*/
    public function statisticsExport(&$objProps,&$objExcel,$param)
    {
        $param['type'] = 'tatistics';
        $title = L_('营业分析');
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        // 设置当前的sheet
        $objExcel->setActiveSheetIndex(0);

        // 统计类型
        $business_type = $param['business_type'] ? $param['business_type'] : 'shop';

        // 订单类型
        $order_type = $param['order_type'] ? $param['order_type'] : 'shop';

        // 昨日 近七日 近30日 自定义
        $type = $param['time_type'];

        // 昨日23:59点时间戳
        $end_time = strtotime(date('Y-m-d'))-1;
        $time_show = '';
        switch ($type) {
            case '1': // 昨日
                $start_time = $end_time - 86400;
                break;
            case '2': // 近七日
                $start_time = $end_time - 86400*7;
                $time_show = L_('近7日');
                break;
            case '3': // 近30日
                $start_time = $end_time - 86400*30;
                $time_show = L_('近30日');
                break;
            case '4': // 自定义
                $start_time = strtotime($_GET['start_time']);
                $end_time = strtotime($_GET['end_time'])+86399;
                break;
        }
        $time_show .= '（'.date('Y-m-d',$start_time).'-'.date('Y-m-d',$end_time).'）';

        // 已支付
        $condition['paid'] = 1;
        $m_condition['o.paid'] = 1;

        // 商家id
        $store_condition['ms.mer_id'] = $param['merchant_session']['mer_id'];

        // 店铺id
        $store_id = $param['store_list'];
        if ($store_id&&$store_id!=-1) {
            $store_condition['s.store_id'] = array('in',$store_id);
        }

        $count = M('Merchant_store_shop')->join(' as s left join '.C('DB_PREFIX')._view('merchant_store').' as ms on ms.store_id=s.store_id')->where($store_condition)->count();
        set_time_limit(0);
        $length = ceil($count / 1000);
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);
            $objExcel->getActiveSheet()->setTitle(L_('第X1个一千个订单信息', (array('X1' => $i + 1))));
            $objActSheet = $objExcel->getActiveSheet();
            switch ($business_type) {
                case 'shop': // 外卖 
                    $title_arr = array(
                        array(20,L_('日期')),
                        array(20,L_('店铺名称')),
                        array(20,L_('营业额')),
                        array(20,L_('打包费')),
                        array(20,L_('平台配送费用')),
                        array(20,L_('商家配送费用')),
                        array(20,L_('有效订单')),
                        array(20,L_('无效订单')),
                        array(20,L_('平台满减补贴金额')),
                        array(20,L_('商家满减补贴金额')),
                        array(20,L_('平台优惠券补贴金额')),
                        array(20,L_('平台积分补贴金额')),
                        array(20,L_('商家优惠券补贴金额')),
                        array(20,L_('损失')),
                    );
                    break;
                case 'market': // 营销
                    $title_arr = array(
                        array(20,L_('日期')),
                        array(20,L_('店铺名称')),
                        array(20,L_('活动总营业额')),
                        array(20,L_('活动总支出')),
                        array(20,L_('带动订单')),
                        array(20,L_('活动盈利')),
                        array(20,L_('活动客单价')),
                        array(20,L_('活动老客数')),
                        array(20,L_('活动新客数')),
                        array(20,L_('投入产出比')),
                        array(20,L_('领券数量')),
                        array(20,L_('核销率')),
                    );
                    break; 
                case 'dining': // 新版餐饮 
                    $title_arr = array(
                        array(20,L_('日期')),
                        array(20,L_('店铺名称')),
                        array(20,L_('营业额')),
                        array(20,L_('有效订单')),
                        array(20,L_('无效订单')),
                        array(20,L_('平台满减补贴金额')),
                        array(20,L_('商家满减补贴金额')),
                        array(20,L_('平台优惠券补贴金额')),
                        array(20,L_('平台积分补贴金额')),
                        array(20,L_('商家优惠券补贴金额')),
                        array(20,L_('损失')),
                    );
                    break;
            }
            

            //参数依次为excel对象、表头数组、表头起始行、默认行高
            $lastColumn = $this->setPHPExcelHeader($objExcel, $title_arr, 1, 15);

            // 店铺列表
            $list = M('Merchant_store_shop')->field('s.*,ms.*')->join(' as s left join '.C('DB_PREFIX')._view('merchant_store').' as ms on ms.store_id=s.store_id')->where($store_condition)->order('s.store_id ASC')->limit(($i * 1000) . ',1000')->select();

            if (!empty($list)) {
                $index = 1;
                foreach ($list as $value) {
                    $index++;
                    $column = 0;
                    // 日期
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $time_show);
                    // 店铺名称
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['name']);

                    switch ($business_type) {
                        case 'shop': // 外卖 
                            $condition['store_id'] = $value['store_id']; // 店铺id
                            $m_condition['o.store_id'] = $value['store_id']; // 店铺id
                            $condition['pay_time'] = array('between',[$start_time,$end_time]); // 支付时间
                            $m_condition['o.pay_time'] = array('between',[$start_time,$end_time]); // 支付时间
                            $returnArr = D('Shop_order')->getHistoryStatistics($condition,$m_condition);
                            // 营业额
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $returnArr['total_money']);
                            // 打包费
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $returnArr['discount_info']['platform_plat']);
                            // 平台配送费用
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $returnArr['discount_info']['plat_freight_charge']);
                            // 商家配送费用
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $returnArr['discount_info']['merchant_freight_charge']);
                            // 有效订单
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $returnArr['effective_count']);
                            // 无效订单
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $returnArr['invalid_count']);
                            // 平台满减补贴金额
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $returnArr['discount_info']['platform_plat']);
                            // 商家满减补贴金额
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $returnArr['discount_info']['merchant_reduce']);
                            // 平台优惠券补贴金额
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $returnArr['discount_info']['coupon_price']);
                            // 平台积分补贴金额
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $returnArr['discount_info']['score_deducte']);
                            // 商家优惠券补贴金额
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $returnArr['discount_info']['card_price']);
                            // 损失
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $returnArr['loss_money']);
                            break;
                        case 'market': // 营销
                            $param['start_time'] = $start_time;
                            $param['end_time'] = $end_time;
                            $param['store_list'] = $value['store_id'];
                            $returnArr = D('Shop_order')->getRevenueStatisticsMarketMerchant($param['merchant_session']['mer_id'],0,$param);

                            // 活动总营业额
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $returnArr['market_total_money']);
                            // 活动总支出
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $returnArr['mer_total_money']);
                            // 带动订单
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $returnArr['market_count']);
                            // 活动盈利
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['mer_income']);
                            // 活动客单价
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['unit_price']);
                            // 活动老客数
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['regular_customer']);
                            // 活动新客数
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['new_customer']);
                            // 投入产出比
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['input_output_rate'].'%');
                            // 领券数量
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['system_coupon_hadpull']);
                            // 核销率
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['coupon_use_rate'].'%');
                            break;
                        case 'dining': // 新版餐饮 
                            $condition['store_id'] = $value['store_id']; // 店铺id
                            $m_condition['o.store_id'] = $value['store_id']; // 店铺id
                            $condition['pay_time'] = array('between',[$start_time,$end_time]); // 支付时间
                            $m_condition['o.pay_time'] = array('between',[$start_time,$end_time]); // 支付时间
                            $returnArr = D('Dining_order')->getHistoryStatistics($condition,$m_condition);
                            // 营业额
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $returnArr['total_money']);
                            // 有效订单
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $returnArr['effective_count']);
                            // 无效订单
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $returnArr['invalid_count']);
                            // 平台满减补贴金额
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $returnArr['discount_info']['platform_plat']);
                            // 商家满减补贴金额
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $returnArr['discount_info']['merchant_reduce']);
                            // 平台优惠券补贴金额
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $returnArr['discount_info']['coupon_price']);
                            // 平台积分补贴金额
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $returnArr['discount_info']['score_deducte']);
                            // 商家优惠券补贴金额
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $returnArr['discount_info']['card_price']);
                            // 损失
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $returnArr['loss_money']);
                            break;
                    }

                    
                }
            }
            sleep(2);
        }
    }

    /**系统后台营业分析*/
    public function platStatisticsExport(&$objProps,&$objExcel,$param)
    {
        $param['type'] = 'platStatistics';
        $title = L_('营业分析');
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        // 设置当前的sheet
        $objExcel->setActiveSheetIndex(0);

        // 昨日 近七日 近30日 自定义
        $type = $param['time_type'];

        // 昨日23:59点时间戳
        $end_time = strtotime(date('Y-m-d'))-1;
        $time_show = '';
        switch ($type) {
            case '1': // 昨日
                $start_time = $end_time - 86400;
                break;
            case '2': // 近七日
                $start_time = $end_time - 86400*7;
                $time_show = L_('近7日');
                break;
            case '3': // 近30日
                $start_time = $end_time - 86400*30;
                $time_show = L_('近30日');
                break;
            case '4': // 自定义
                $start_time = strtotime($param['start_time']);
                $end_time = strtotime($param['end_time'])+86399;
                break;
        }
        $time_show .= '（'.date('Y-m-d',$start_time).'-'.date('Y-m-d',$end_time).'）';

        $condition['s.statistics_date'] = array('between',[$start_time,$end_time]); // 时间

        if ($param['area_id'] && $param['area_id'] != 'undefined') {
            $where_mer['area_id'] = $param['area_id'];
        } else if ($param['city_idss'] && $param['city_idss'] != 'undefined') {
            $where_mer['city_id'] = $param['city_idss'];
        } else if ($param['province_idss'] && $param['province_idss'] != 'undefined') {
            $where_mer['province_id'] = $param['province_idss'];
        }
        if ($where_mer) {
            $mer_ids = M('Merchant')->where($where_mer)->select();

            foreach ($mer_ids as $st) {
                $mer_id_arr[] = $st['mer_id'];
            }
            if (!$mer_id_arr) {
                $condition['s.mer_id'] = -1;
            }else{
                $condition['s.mer_id'] = array('in',$mer_id_arr);
            }
        }

        // 查询统计类型
        $condition['s.business_type'] = $param['business_type'];

        if ($condition['s.business_type'] == 'market') {
            // 查询订单类型
            $condition['s.order_type'] = $param['order_type'];
        }

        // 查询字段
        $field = D('Revenue_statistics')->getRevenueStatisticsField($param['business_type']);
        $field .= ',m.name';
        $count = M('Revenue_statistics')->field('s.mer_id')->join(' as s left join '.C('DB_PREFIX')._view('merchant').' as m on m.mer_id=s.mer_id')->where($condition)->group('s.mer_id')->select();
        $count = count($count);
        fdump(M('Revenue_statistics')->_sql(),'order',1);
        set_time_limit(0);
        $length = ceil($count / 1000);
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);
            $objExcel->getActiveSheet()->setTitle(L_('第X1个一千个商家信息', (array('X1' => $i + 1))));
            $objActSheet = $objExcel->getActiveSheet();

            switch ($param['business_type']) {
                case 'shop':
                    $title_arr = array(
                        array(20,'日期'),
                        array(20,'商家名称'),
                        array(20,'营业额'),
                        array(20,'商家收入'),
                        array(20,'平台活动补贴'),
                        array(20,'商家活动补贴'),
                        array(20,'平台盈利'),
                        array(20,'平台抽成'),
                        array(20,'平台配送费'),
                        array(20,'客单价'),
                        array(20,'有效订单'),
                        array(20,'无效订单'),
                        array(20,'损失'),
                    );
                    break;
                case 'dining':
                    $title_arr = array(
                        array(20,'日期'),
                        array(20,'商家名称'),
                        array(20,'营业额'),
                        array(20,'商家收入'),
                        array(20,'平台活动补贴'),
                        array(20,'商家活动补贴'),
                        array(20,'平台盈利'),
                        array(20,'平台抽成'),
                        array(20,'有效订单'),
                        array(20,'无效订单'),
                    );
                    break;
                case 'deliver':
                    $title_arr = array(
                        array(20,'日期'),
                        array(20,'商家名称'),
                        array(20,'平台总配送费'),
                        array(20,'平台配送费抽成'),
                        array(20,'有效配送单数'),
                        array(20,'活动补贴'),
                        array(20,'配送超时订单数'),
                        array(20,'配送超时超时率'),
                        array(20,'催单数'),
                        array(20,'催单率'),
                        array(20,'差评订单数'),
                        array(20,'差评率'),
                        array(20,'无效配送单数'),
                        array(20,'损失'),
                    );
                    break;
                case 'market':
                    $title_arr = array(
                        array(20,'日期'),
                        array(20,'商家名称'),
                        array(20,'活动总营业额'),
                        array(20,'活动总支出'),
                        array(20,'带动订单'),
                        array(20,'活动盈利'),
                        array(20,'活动商家收入'),
                        array(20,'活动客单价'),
                        array(20,'活动老客数'),
                        array(20,'活动新客数'),
                        array(20,'投入产出比'),
                    );
                    break;
            }

            //参数依次为excel对象、表头数组、表头起始行、默认行高
            $lastColumn = $this->setPHPExcelHeader($objExcel, $title_arr, 1, 15);

            // 店铺列表
            $list = M('Revenue_statistics')->field($field)->join(' as s left join '.C('DB_PREFIX')._view('merchant').' as m on m.mer_id=s.mer_id')->where($condition)->group('s.mer_id')->order('total_money DESC')->limit(($i * 1000) . ',1000')->select();
             // 配送统计
            if ($param['business_type']=='deliver'&&$i==0) {
                // 跑腿 社区团购
                $condition['s.mer_id'] = ['in',[-1,-2]];
                $mer_list_pt = M('Revenue_statistics')->field($field)->join(' as s left join '.C('DB_PREFIX')._view('merchant').' as m on m.mer_id=s.mer_id')->where($condition)->select();
                $mer_list_pt = $mer_list_pt ? $mer_list_pt : array();
                $list = array_merge($list,$mer_list_pt);
            }
            if (!empty($list)) {
                $index = 1;
                foreach ($list as &$value) {
                    $index++;

                    $column = 0;
                     // 日期
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $time_show);
                    // 店铺名称
                    $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['name']);

                    $value = D('Revenue_statistics')->getFormatData($param['business_type'],$value);

                    switch ($param['business_type']) {
                        case 'shop': // 外卖
                            // 营业额
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['total_money']);
                            // 商家收入
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['mer_income']);
                            // 平台活动补贴
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['plat_total_money']);
                            // 商家活动补贴
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['mer_total_money']);
                            // 平台盈利
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['plat_income']);
                            // 平台抽成
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['plat_get_commission']);
                            // 平台配送费用
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['plat_freight_charge']);
                            // 客单价
                            $unit_price = $value['effective_count'] ? getFormatNumber($value['mer_income']/$value['effective_count']) : 0;
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $unit_price);
                            // 有效订单
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['effective_count']);
                            // 无效订单
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['invalid_count']);
                            // 损失
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['loss_money']);
                            break;
                        case 'dining': // 新版餐饮
                            // 营业额
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['total_money']);
                            // 商家收入
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['mer_income']);
                            // 平台活动补贴
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['plat_total_money']);
                            // 商家活动补贴
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['mer_total_money']);
                            // 平台盈利
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['plat_income']);
                            // 平台抽成
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['plat_get_commission']);
                            // 有效订单
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['effective_count']);
                            // 无效订单
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['invalid_count']);
                            break;
                        case 'deliver': // 配送
                            // 平台总配送费
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['total_money']);
                            // 平台配送费抽成
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['plat_freight_charge']);
                            // 有效配送单数
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['effective_count']);
                            // 活动补贴
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['discount_money']);
                            // 配送超时订单数
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['overtime_count']);
                            // 配送超时超时率
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['overtime_rate'].'%');
                            // 催单数
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['reminder_count']);
                            // 催单率
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['reminder_rate'].'%');
                            // 差评订单数
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['bad_count']);
                            // 差评率
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['bad_rate'].'%');
                            // 无效配送单数
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['invalid_count']);
                            // 损失
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['loss_money']);
                            break;
                        case 'market': // 营销
                            // 活动总营业额
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['market_total_money']);
                            // 活动总支出
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['plat_total_money']);
                            // 带动订单
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['market_count']);
                            // 活动盈利
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['plat_income']);
                            // 活动商家收入
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['mer_income']);
                            // 活动客单价
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['unit_price']);
                            // 活动老客数
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['regular_customer']);
                            // 活动新客数
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['new_customer']);
                            // 投入产出比
                            $objExcel->getActiveSheet()->setCellValue($this->getLetterColumn($column++) . $index, $value['input_output_rate'].'%');
                            break;
                    }
                }
            }
            sleep(2);
        }
    }
    
    /**
     * 商品信息导出
     */
    public function exportGoods(&$objProps,&$objExcel,$param)
    {
        $title = L_('商品信息导出');
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        // 设置当前的sheet
        $objExcel->setActiveSheetIndex(0);

        $sell_type = isset($param['sell_type']) ? $param['sell_type'] : 1;
        $is_shop = isset($param['is_shop']) ? intval($param['is_shop']) : 0;
        $where = ['store_id' => $param['store_id'], 'sell_type' => $sell_type];
        $is_shop && $where['goods_type'] = 0;
        $fields = '*';
        //$goods = M('Shop_goods')->field($fields)->where($where)->select();
        $count = M('Shop_goods')->where($where)->count();
        $length = $count / 1000;
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);
            $objExcel->getActiveSheet()->setTitle(L_('第X1个一千条商品信息', ($i + 1)));
            $objActSheet = $objExcel->getActiveSheet();

            $goods = M('Shop_goods')->field($fields)->where($where)->limit($i * 1000, 1000)->order('goods_id ASC')->select();
            //计重商品导出模板
            if ($sell_type == 2) {
                $title_arr = array(
                    array(40, L_('商品名称')),
                    array(40, L_('商品副标题')),
                    array(20, L_('商品分类名称')),
                    array(20, L_('商品单位')),
                    array(20, L_('商品单价/元/一斤（500g）')),
                    array(20, L_('商品重量（单位：g或者kg）')),
                    array(20, L_('商品皮重（单位：g）')),
                    array(20, L_('打包费/元')),
                    array(20, L_('当前库存（单位：g）')),
                    array(20, L_('原始库存（单位：g）')),
                    array(20, L_('条形码')),
                    array(20, L_('上下架'))
                );

                $lastColumn = $this->setPHPExcelHeader($objExcel, $title_arr, 1, 15);
                $tmp_id = 0;

                $goodsMod = D('Shop_goods');
                $goodsSortMod = M('Shop_goods_sort');
                if (!empty($goods)) {
                    $index = 2;
                    foreach ($goods as $value) {
                        //计算分类名称
                        $sort1 = $goodsSortMod->where(['sort_id' => $value['sort_id']])->find();
                        $sort2 = $sort1['fid'] == 0 ? [] : $goodsSortMod->where(['sort_id' => $sort1['fid']])->find();
                        $sort3 = $sort2['fid'] == 0 ? [] : $goodsSortMod->where(['sort_id' => $sort2['fid']])->find();
                        $sort = [$sort3 ? $sort3['sort_name'] : '', $sort2 ? $sort2['sort_name'] : '', $sort1 ? $sort1['sort_name'] : ''];
                        $sort = array_filter($sort);
                        $sort = implode($sort, '#');

                        $column = 0;
                        $objExcel->getActiveSheet()->setCellValueExplicit('A' . $index, $value['name']);
                        $objExcel->getActiveSheet()->setCellValueExplicit('B' . $index, $value['describe']);
                        $objExcel->getActiveSheet()->setCellValueExplicit('C' . $index, $sort);
                        $objExcel->getActiveSheet()->setCellValueExplicit('D' . $index, $value['unit']);
                        $objExcel->getActiveSheet()->setCellValueExplicit('E' . $index, $value['unit_price']);
                        $objExcel->getActiveSheet()->setCellValueExplicit('F' . $index, $value['weight'] . ($value['weight_unit'] == 1 ? 'g' : 'kg'));
                        $objExcel->getActiveSheet()->setCellValueExplicit('G' . $index, $value['tare_weight']);
                        $objExcel->getActiveSheet()->setCellValueExplicit('H' . $index, $value['packing_charge']);
                        $objExcel->getActiveSheet()->setCellValueExplicit('I' . $index, $value['stock_weight']);
                        $objExcel->getActiveSheet()->setCellValueExplicit('J' . $index, $value['original_weight']);
                        $objExcel->getActiveSheet()->setCellValueExplicit('K' . $index, $value['number']);
                        $objExcel->getActiveSheet()->setCellValueExplicit('L' . $index, $value['status']);
                        $index++;
                    }
                }
            }else{
                //下面是计件商品的逻辑
                $title_arr = array(
                    array(40, L_('商品名称')),
                    array(40, L_('商品副标题')),
                    array(20, L_('商品分类名称')),
                    array(20, L_('商品单位')),
                    array(20, L_('最小购买量')),
                    array(20, L_('商品现价')),
                    array(20, L_('规格名称')),
                    array(20, L_('属性名称')),
                    array(20, L_('规格价/元')),
                    array(20, L_('打包费/元')),
                    array(20, L_('当前库存')),
                    array(20, L_('原始库存')),
                    array(20, L_('条形码')),
                    array(20, L_('上/下架')),
                    array(20, L_('多规格商品库存')),
                    array(20, L_('商品进价/元')),
                    array(20, L_('规格进价/元')),
                );

                $lastColumn = $this->setPHPExcelHeader($objExcel, $title_arr, 1, 15);
                $tmp_id = 0;
                $propertiesMod = M('Shop_goods_properties');
                $specMod = M('Shop_goods_spec');
                $specValMod = M('Shop_goods_spec_value');
                $goodsMod = D('Shop_goods');
                $goodsSortMod = M('Shop_goods_sort');
                if (!empty($goods)) {
                    $index = 2;
                    foreach ($goods as $value) {
                        //计算分类名称
                        $sort1 = $goodsSortMod->where(['sort_id' => $value['sort_id']])->find();
                        $sort2 = $sort1['fid'] == 0 ? [] : $goodsSortMod->where(['sort_id' => $sort1['fid']])->find();
                        $sort3 = $sort2['fid'] == 0 ? [] : $goodsSortMod->where(['sort_id' => $sort2['fid']])->find();
                        $sort = [$sort3 ? $sort3['sort_name'] : '', $sort2 ? $sort2['sort_name'] : '', $sort1 ? $sort1['sort_name'] : ''];
                        $sort = array_filter($sort);
                        $sort = implode($sort, '#');

                        //计算属性
                        $propertieLists = $propertiesMod->where(['goods_id' => $value['goods_id']])->select();
                        $propertieStr = '';
                        foreach ($propertieLists as $p) {
                            $vals = array_map(function ($v) {
                                return substr($v, 0, -2);
                            }, explode(',', $p['val']));

                            if ($propertieStr) {
                                $propertieStr .= '+' . $p['name'] . '#' . implode($vals, '&');
                            } else {
                                $propertieStr .= $p['name'] . '#' . implode($vals, '&');
                            }
                        }

                        //计算规格
                        $return = $goodsMod->format_spec_value($value['spec_value'], $value['goods_id']);
                        $specIndex = array_combine(array_column($return['spec_list'], 'id'), array_column($return['spec_list'], 'name'));
                        $specValue = $return['list'];
                        $specArr = [];
                        foreach ($specValue as $spec) {
                            $str = '';
                            foreach ($spec['spec'] as $sv) {
                                if ($str) {
                                    $str .= '+';
                                }
                                $str .= isset($specIndex[$sv['spec_val_sid']]) ? $specIndex[$sv['spec_val_sid']] : '';
                                $str .= '#' . $sv['spec_val_name'];
                            }
                            $specArr[] = [
                                'spec' => $str,
                                'sku' => $spec['number'],
                                'goods_weight' => $spec['goods_weight'],
                                'price' => $spec['price'],
                                'cost_price' => $spec['cost_price'],
                                'stock_num' => $spec['stock_num']
                            ];
                        }
                        
                        $column = 0;
                        $objExcel->getActiveSheet()->setCellValueExplicit('A' . $index, $value['name']);
                        $objExcel->getActiveSheet()->setCellValueExplicit('B' . $index, $value['describe']);
                        $objExcel->getActiveSheet()->setCellValueExplicit('C' . $index, removeEmojiChar($sort));
                        $objExcel->getActiveSheet()->setCellValueExplicit('D' . $index, $value['unit']);
                        $objExcel->getActiveSheet()->setCellValueExplicit('E' . $index, $value['min_num']);
                        $objExcel->getActiveSheet()->setCellValueExplicit('F' . $index, $value['price']);
                        $objExcel->getActiveSheet()->setCellValueExplicit('H' . $index, $propertieStr);
                        $objExcel->getActiveSheet()->setCellValueExplicit('J' . $index, $value['packing_charge']);
                        $objExcel->getActiveSheet()->setCellValueExplicit('K' . $index, $value['stock_num']);
                        $objExcel->getActiveSheet()->setCellValueExplicit('L' . $index, $value['original_stock']);
                        $objExcel->getActiveSheet()->setCellValueExplicit('M' . $index, $value['number']);
                        $objExcel->getActiveSheet()->setCellValueExplicit('N' . $index, $value['status']);
                        $objExcel->getActiveSheet()->setCellValueExplicit('P' . $index, $value['cost_price']);
                        if ($specArr) {
                            foreach ($specArr as $sk => $v) {
                                $objExcel->getActiveSheet()->setCellValueExplicit('G' . $index, $v['spec']);
                                $objExcel->getActiveSheet()->setCellValueExplicit('I' . $index, $v['price']);
                                $objExcel->getActiveSheet()->setCellValueExplicit('O' . $index, $v['stock_num']);
                                $objExcel->getActiveSheet()->setCellValueExplicit('Q' . $index, $v['cost_price']);
                                $index++;
                            }
                        } else {
                            $index++;
                        }
                    }
                }
            }
           // sleep(2);
        }
        unset($goods);
    }
    
    /**
     *自提订单导出
     */
    public function pickOrderExport(&$objProps,&$objExcel,$param)
    {
        $title = L_('自提订单');
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        // 设置当前的sheet
        $objExcel->setActiveSheetIndex(0);

        $where = ['p.pick_id' => $param['pick_id'], 'p.type' => 0];
        if (!empty($param['keyword'])) {
            if ($param['searchtype'] == 'real_orderid') {
                $where['s.real_orderid'] = htmlspecialchars($param['keyword']);
            } elseif ($param['searchtype'] == 'orderid') {
                $where['s.orderid'] = htmlspecialchars($param['keyword']);
                $tmpResult = M('Tmp_orderid')->where(['orderid' => $where['orderid']])->find();
                unset($where['orderid']);
                $where['s.order_id'] = $tmpResult['order_id'];
            } elseif ($param['searchtype'] == 'name') {
                $where['s.username'] = htmlspecialchars($param['keyword']);
            } elseif ($param['searchtype'] == 'phone') {
                $where['s.userphone'] = htmlspecialchars($param['keyword']);
            } elseif ($param['searchtype'] == 'third_id') {
                $where['s.third_id'] = $param['keyword'];
            }
        }

        if (!empty($param['begin_time']) && !empty($param['end_time'])) {
            $period = [strtotime($param['begin_time'] . " 00:00:00"), strtotime($param['end_time'] . " 23:59:59")];
            $where['_string'] = "(s.create_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
        }
        $count = D('Pick_order')
            ->join('AS p INNER JOIN ' . C('DB_PREFIX') . 'shop_order AS s  ON p.order_id=s.order_id')
            ->where($where)
            ->count();
        $count = intval($count);
        $length = ceil($count / 1000);

        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);
            $objExcel->getActiveSheet()->setTitle(L_('第X1个一千条订单信息', ($i + 1)));
            $objActSheet = $objExcel->getActiveSheet();

            $title_arr = array(
                array(40, L_('订单号')),
                array(40, L_('客户姓名')),
                array(20, L_('客户电话')),
                array(20, L_('客户地址')),
                array(20, L_('配送类型')),
                array(20, L_('订单总价')),
                array(20, L_('已付金额')),
                array(20, L_('未付金额'))
            );
            //参数依次为excel对象、表头数组、表头起始行、默认行高
            $lastColumn = $this->setPHPExcelHeader($objExcel, $title_arr, 1, 15);
            $result_list = D('Pick_order')->field('s.*, p.status as pstatus')
                ->join('AS p INNER JOIN ' . C('DB_PREFIX') . 'shop_order AS s  ON p.order_id=s.order_id')
                ->where($where)
                ->order('s.order_id DESC')
                ->limit($i * 1000 . ",1000")
                ->select();
            $tmp_id = 0;
            if (!empty($result_list)) {
                $index = 2;
                foreach ($result_list as $value) {

                    $value['offline_price'] = round($value['price'] - round($value['card_price'] + $value['merchant_balance'] + $value['balance_pay'] + $value['payment_money'] + $value['score_deducte'] + $value['coupon_price'], 2), 2);
                    $value['pay_price'] = (floor($value['card_price'] * 100) + floor($value['merchant_balance'] * 100) + floor($value['balance_pay'] * 100) + floor($value['payment_money'] * 100) + floor($value['score_deducte'] * 100) + floor($value['coupon_price'] * 100)) / 100;
                    
                    $column = 0;
                    $objExcel->getActiveSheet()->setCellValueExplicit($this->getLetterColumn($column++) . $index, $value['real_orderid']);
                    $objExcel->getActiveSheet()->setCellValueExplicit($this->getLetterColumn($column++) . $index, $value['username']);
                    $objExcel->getActiveSheet()->setCellValueExplicit($this->getLetterColumn($column++) . $index, $value['userphone']);
                    $objExcel->getActiveSheet()->setCellValueExplicit($this->getLetterColumn($column++) . $index, $value['is_pick_in_store']==1 ? $value['address'] :'');
                    $objExcel->getActiveSheet()->setCellValueExplicit($this->getLetterColumn($column++) . $index, $value['is_pick_in_store'] == 1 ? L_("配送") : L_("自提"));
                    $objExcel->getActiveSheet()->setCellValueExplicit($this->getLetterColumn($column++) . $index, $value['price']);
                    $objExcel->getActiveSheet()->setCellValueExplicit($this->getLetterColumn($column++) . $index, $value['pay_price']);
                    $objExcel->getActiveSheet()->setCellValueExplicit($this->getLetterColumn($column++) . $index, $value['offline_price']);
                    $index++;
                }
            }
            sleep(2);
        }
    }

    public function exportStaffBill(&$objProps, &$objExcel, $param){
        set_time_limit(0);
        $param['type'] = 'staff_bill';
        $title = L_('店员对账');
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        // 设置当前的sheet
        $objExcel->setActiveSheetIndex(0);

        $staffid = isset($param['staff_id']) ? intval($param['staff_id']) : 0;
        $type = isset($param['business_type']) && $param['business_type'] ? $param['business_type'] : 'shop';
        $staffs = D('Merchant_store_staff')->where(array('token' => $param['merchant_session']['mer_id']))->select();
        $staff_name = '';
        foreach ($staffs as $row) {
            if ($row['id'] == $staffid) $staff_name = $row['name'];
        }
        $data = $this->get_offlineorder_by_mer_id($param['merchant_session']['mer_id'], $staff_name, $type, 1000);
        $length = ceil($data['uncount'] / 1000);
        for ($i = 0; $i < $length; $i++) {
            $i && $objExcel->createSheet();
            $objExcel->setActiveSheetIndex($i);

            $objExcel->getActiveSheet()->setTitle(L_('第'.($i + 1).'个一千个对账信息'));
            $objActSheet = $objExcel->getActiveSheet();
            $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(40);
            $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('E')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('F')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('G')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('H')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('I')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('J')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('K')->setWidth(20);
            $objExcel->getActiveSheet()->getColumnDimension('L')->setWidth(20);

            $objActSheet->setCellValue('A1', L_('订单号'));
            $objActSheet->setCellValue('B1', L_('总价'));
            $objActSheet->setCellValue('C1', L_('在线支付金额'));
            $objActSheet->setCellValue('D1', L_('平台余额支付金额'));
            $objActSheet->setCellValue('E1', L_('商家会员卡余额支付金额'));
            $objActSheet->setCellValue('F1', L_('平台优惠券抵现'));
            $objActSheet->setCellValue('G1', L_('商户优惠券抵现'));
            $objActSheet->setCellValue('H1', C('config.score_name').L_('抵现'));
            $objActSheet->setCellValue('I1', L_('店员收取现金'));
            $objActSheet->setCellValue('J1', L_('下单时间'));
            $objActSheet->setCellValue('K1', L_('支付时间'));
            $objActSheet->setCellValue('L1', L_('对账状态'));

            if($i == '0'){
                $index = 1;
                foreach ($data['order_list'] as $key  => $vo) {
                    $index++;
                    $objActSheet->setCellValueExplicit('A' . $index, $vo['real_orderid']);
                    $objActSheet->setCellValueExplicit('B' . $index, floatval($vo['price']));
                    $objActSheet->setCellValueExplicit('C' . $index, floatval($vo['payment_money']));
                    $objActSheet->setCellValueExplicit('D' . $index, floatval($vo['balance_pay']));
                    $objActSheet->setCellValueExplicit('E' . $index, floatval($vo['merchant_balance']));
                    $objActSheet->setCellValueExplicit('F' . $index, floatval($vo['coupon_price']));
                    $objActSheet->setCellValueExplicit('G' . $index, floatval($vo['card_price']));
                    $objActSheet->setCellValueExplicit('H' . $index, floatval($vo['score_deducte']));
                    $objActSheet->setCellValueExplicit('I' . $index, floatval($vo['cash']));
                    $objActSheet->setCellValueExplicit('J' . $index, date('Y-m-d H:i:s', $vo['create_time']));
                    $objActSheet->setCellValueExplicit('K' . $index, date('Y-m-d H:i:s', $vo['pay_time']));
                    $objActSheet->setCellValueExplicit('L' . $index, ($vo['is_pay_bill'] == '0' ? '未对账' : '已对账'));
                }
            }
            else{
                $_GET['page'] = $i;
                $data = $this->get_offlineorder_by_mer_id($param['merchant_session']['mer_id'], $staff_name, $type, 1000);
                $index = 1;
                foreach ($data['order_list'] as $key  => $vo) {
                    $index++;
                    $objActSheet->setCellValueExplicit('A' . $index, $vo['real_orderid']);
                    $objActSheet->setCellValueExplicit('B' . $index, floatval($vo['price']));
                    $objActSheet->setCellValueExplicit('C' . $index, floatval($vo['payment_money']));
                    $objActSheet->setCellValueExplicit('D' . $index, floatval($vo['balance_pay']));
                    $objActSheet->setCellValueExplicit('E' . $index, floatval($vo['merchant_balance']));
                    $objActSheet->setCellValueExplicit('F' . $index, floatval($vo['coupon_price']));
                    $objActSheet->setCellValueExplicit('G' . $index, floatval($vo['card_price']));
                    $objActSheet->setCellValueExplicit('H' . $index, floatval($vo['score_deducte']));
                    $objActSheet->setCellValueExplicit('I' . $index, floatval($vo['cash']));
                    $objActSheet->setCellValueExplicit('J' . $index, date('Y-m-d H:i:s', $vo['create_time']));
                    $objActSheet->setCellValueExplicit('K' . $index, date('Y-m-d H:i:s', $vo['pay_time']));
                    $objActSheet->setCellValueExplicit('L' . $index, ($vo['is_pay_bill'] == '0' ? '未对账' : '已对账'));
                }
            }
        }
        sleep(2);
    }


    public function exportGoodsToBeDelivered(&$objProps, &$objExcel, $param)
    {
        set_time_limit(0);
        $title = L_('待发商品清单');
        // 设置文档基本属性
        $objProps->setCreator($title);
        $objProps->setTitle($title);
        $objProps->setSubject($title);
        $objProps->setDescription($title);
        // 设置当前的sheet
        $objExcel->setActiveSheetIndex(0);

        $data = [];
        if ($param['begin_time']) {
            $data['begin_time'] = strtotime($param['begin_time']);
        }
        if ($param['end_time']) {
            $data['end_time'] = strtotime($param['end_time']);
        }
        if (isset($param['store_id'])) {
            $data['store_id'] = $param['store_id'];
        }
        $shop_type = $param['shop_type'] ?? 1;
        if ($shop_type == 1) {
            $data['order_from'] = array('neq', 1);
        } else {
            $data['order_from'] = 1;
        }
        $result = D('Shop_order')->order_count($data);
        //dump($result);exit;

        $objExcel->createSheet();
        $objExcel->setActiveSheetIndex(0);
        $objActSheet = $objExcel->getActiveSheet();
        $objExcel->getActiveSheet()->getColumnDimension('A')->setWidth(40);
        $objExcel->getActiveSheet()->getColumnDimension('B')->setWidth(20);
        $objExcel->getActiveSheet()->getColumnDimension('C')->setWidth(20);
        $objExcel->getActiveSheet()->getColumnDimension('D')->setWidth(20);
        $objActSheet->setCellValue('A1', L_('商品名'));
        $objActSheet->setCellValue('B1', L_('商品属性'));
        $objActSheet->setCellValue('C1', L_('数量'));
        $objActSheet->setCellValue('D1', L_('总数量'));
        $start = 2;
        $index = 2;
        foreach ($result as $r) {
            $objActSheet->setCellValueExplicit('A' . $index, $r['name']);
            $objActSheet->setCellValueExplicit('D' . $index, $r['count']);
            if ($r['row'] > 1) {
                $objActSheet->mergeCells('A' . $start . ':' . 'A' . ($start + $r['row'] - 1));
                $objActSheet->mergeCells('D' . $start . ':' . 'D' . ($start + $r['row'] - 1));
                $start = $start + $r['row'] - 1;
            } else {
                $start++;
            }
            foreach ($r['list'] as $k => $l) {
                $objActSheet->setCellValueExplicit('B' . ($index + $k), $l['spec']);
                $objActSheet->setCellValueExplicit('C' . ($index + $k), $l['total']);
            }
            $index++;
        }
    }
}

?>