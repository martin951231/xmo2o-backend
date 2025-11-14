<?php

class PrintHaddle
{

    public $orderPrint = null;
    
    public $payarr = array(
                'alipay' => '支付宝',
                'weixin' => '微信支付',
                'tenpay' => '财付通[wap手机]',
                'tenpaycomputer' => '财付通[即时到帐]',
                'yeepay' => '易宝支付',
                'allinpay' => '通联支付',
                'daofu' => '货到付款',
                'dianfu' => '到店付款',
                'chinabank' => '网银在线',
                'offline' => '线下支付'
            );

    public  $order_from_arr = array(
                '0' => '用户下单',
                '1' => '桌台二维码',
                '2' => '店员下单',
            );
    public function __construct() 
    {
        $this->orderPrint = new orderPrint(C('config.print_server_key'), C('config.print_server_topdomain'));
    }
    
	public function printit($order_id, $table = 'shop_order', $status = 0, $isPrint = 0, $staff_name = '',$re=0)
    {
         // 是否启用餐饮打印模式 新
        if (C('config.open_foodshop_print_type')&&$table =='foodshop_order') {
            $table = $table.'_new';
            return $this->$table($order_id, $status, $isPrint, $staff_name);
        }elseif($table =='shop_order'){//快店重打小票
			return $this->$table($order_id, $status,$re);
		}else{
            return $this->$table($order_id, $status, $isPrint);
        }
    }

    /**
     * 预订单打印
     * @author: 张涛
     * @date: 2020/11/26
     */
    public function book_order_printit($order_id, $table = 'shop_order', $status = 0, $re = 0)
    {
        if ($table == 'shop_order') {
            return $this->shop_order($order_id, $status, $re, true);
        }
    }

    private function shop_order_old($order_id, $status = 0)
    {
        $order = D('Shop_order')->get_order_detail(array('order_id' => $order_id));
        if (empty($order)) return false;
        fdump_api(['订单小票打印shop_order_old'=>$order], 'orderPrinter',1);
        $prints = $this->getPrints($order['store_id']);
        if (empty($prints)) return false;
        
        $this->shop_order_every($order, $prints, $status);
        
        $store = D('Merchant_store')->field(true)->where(array('store_id' => $order['store_id']))->find();
        $store_shop = D('Merchant_store_shop')->field(true)->where(array('store_id' => $order['store_id']))->find();
        $freight_alias = $store_shop['freight_alias'] ? $store_shop['freight_alias'] : '配送费用';
        $pack_alias = $store_shop['pack_alias'] ? $store_shop['pack_alias'] : '打包费用';
        
        foreach ($prints as $usePrinter) {
            if ($usePrinter['is_main'] == 0) {
                continue;
            }
            
            if ($status != -1) {
                $statusArr = explode(',', $usePrinter['paid']);
                if (!in_array($status, $statusArr)) {
                    continue;
                }
            }
            
            $formatData = $this->getThreeData($usePrinter);
            $width = $formatData['width'];
            $firstSpace = $formatData['one'];
            $secondSpace = $formatData['two'];
            $thirdSpace = $formatData['three'];
            $spaceWidth= $formatData['spaceWidth'];
            
            $format_str = '';
            if (isset($order['fetch_number']) && $order['fetch_number']) {
                $format_str .= chr(10) . '取单编号:' . $order['fetch_number'];
            }
            $format_str .= chr(10) . '订单编号:' . $order['real_orderid'];
            
            $tempList = array();
            foreach ($order['info'] as $val) {
                $index = isset($val['packname']) && $val['packname'] ? $val['packname'] : 0;
                if (isset($tempList[$index])) {
                    $tempList[$index]['list'][] = $val;
                } else {
                    $tempList[$index] = array('name' => $val['packname'], 'list' => array($val));
                }
            }
            
            foreach ($tempList as $list) {
                $format_str .= chr(10) . str_repeat('*', $width * 2);
                if ($list['name']) {
                    $format_str .= chr(10) . $list['name'];
                }
                $format_str .= chr(10) . str_repeat(' ', $firstSpace) . '品名' . str_repeat(' ', $secondSpace) . '数量' . str_repeat(' ', $thirdSpace) . '单价';//16
                
                foreach ($list['list'] as $val) {
                    if ($val['spec']) {
                        $val['name'] .= '(' . $val['spec'] . ')';
                    }
                    if ($val['number']) {
                        $format_str .= chr(10) . $val['name'];
                        $len = self::dstrlen($val['number']);
                        $len = $len % ($width * 2);
                        if ($len > $spaceWidth) {
                            $space = $spaceWidth + 1;
                        } else {
                            $space = $spaceWidth + 1 - $len;
                        }
                        $format_str .= chr(10) . self::cutstr($val['number'], $spaceWidth, $width * 2) . self::format($val['num'], $space) . self::format(C('config.Currency_symbol') . floatval($val['price']) . '/' . $val['unit'], $thirdSpace);
                    } else {
                        $len = self::dstrlen($val['name']);
                        $len = $len % ($width * 2);
                        if ($len > $spaceWidth) {
                            $space = $spaceWidth + 1;
                        } else {
                            $space = $spaceWidth + 1 - $len;
                        }
                        $format_str .= chr(10) . self::cutstr($val['name'], $spaceWidth, $width * 2) . self::format($val['num'], $space) . self::format(C('config.Currency_symbol') . floatval($val['price']) . '/' . $val['unit'], $thirdSpace);
                    }
                }
                $format_str .= chr(10) . str_repeat('*', $width * 2);
            }
            
            $format_str .= chr(10) . '客户姓名：' . $order['username'];
            $format_str .= chr(10) . '客户手机：' . $order['userphone'];
            if ($order['desc']) {
                $format_str .= chr(10) . '客户留言:' . $order['desc'];
            }
            if ($order['order_from'] != 6) {
                if ($order['is_pick_in_store'] == 2) {
                    if($order['address']!=''){
                        $format_str .= chr(10) . '自提地址：' . $order['address'];
                    }
                } else {
                    $format_str .= chr(10) . '客户地址：' . $order['address'];
                }
                $format_str .= chr(10) . '配送方式：' . $order['deliver_str'];
                $format_str .= chr(10) . '配送状态：' . $order['deliver_status_str'];
            }
            
            $format_str .= chr(10) . '下单时间：' . date('Y-m-d H:i', $order['create_time']);
            if ($order['pay_time']) {
                $format_str .= chr(10) . '支付时间：' . date('Y-m-d H:i', $order['pay_time']);
            }
            if ($order['expect_use_time']) {
                $format_str .= chr(10) . '送达时间：' . date('Y-m-d H:i', $order['expect_use_time']);
            }
            $format_str .= chr(10) . str_repeat('*', $width * 2);
            $format_str .= chr(10) . '商品总价：'.C('config.Currency_symbol') . floatval($order['goods_price']);
            $format_str .= chr(10) . $freight_alias . '：'.C('config.Currency_symbol') . floatval($order['freight_charge']);
            $format_str .= chr(10) . $pack_alias . '：'.C('config.Currency_symbol') . floatval($order['packing_charge']);
            $format_str .= chr(10) . '订单总价：'.C('config.Currency_symbol') . floatval($order['total_price']);
            if ($order['merchant_reduce'] > 0) {
                $format_str .= chr(10) . '店铺优惠：'.C('config.Currency_symbol') . floatval($order['merchant_reduce']);
            }
            if ($order['balance_reduce'] > 0) {
                $format_str .= chr(10) . '平台优惠：'.C('config.Currency_symbol') . floatval($order['balance_reduce']);
            }
            $format_str .= chr(10) . '实付金额：'.C('config.Currency_symbol') . floatval($order['price']);
            if ($order['score_used_count'] > 0) {
                $format_str .= chr(10) . '使用' . C('config.score_name') . '：' . $order['score_used_count'];
                $format_str .= chr(10) . '' . C('config.score_name') . '抵现：'.C('config.Currency_symbol') . floatval($order['score_deducte']);
            }
            if ($order['merchant_balance'] > 0) {
                $format_str .= chr(10) . '商家余额：'.C('config.Currency_symbol') . floatval($order['merchant_balance']);
            }
            if ($order['balance_pay'] > 0) {
                $format_str .= chr(10) . '平台余额：'.C('config.Currency_symbol') . floatval($order['balance_pay']);
            }
            if ($order['payment_money'] > 0) {
                $format_str .= chr(10) . '在线支付：'.C('config.Currency_symbol') . floatval($order['payment_money']);
            }
            if ($order['card_id'] > 0) {
                $format_str .= chr(10) . '店铺优惠券金额：'.C('config.Currency_symbol') . floatval($order['card_price']);
            }
            if ($order['coupon_id'] > 0) {
                $format_str .= chr(10) . '平台优惠券金额：'.C('config.Currency_symbol') . floatval($order['coupon_price']);
            }
            
            if ($order['pay_type'] == 'offline' && empty($order['third_id'])) {
                $format_str .= chr(10) . '线下需支付：'.C('config.Currency_symbol') . round($order['price'] - round($order['card_price'] + $order['merchant_balance'] + $order['balance_pay'] + $order['payment_money'] + $order['score_deducte'] + $order['coupon_price'], 2), 2);
            }
            $format_str .= chr(10) . '支付状态：' . $order['pay_status_print'];
            $format_str .= chr(10) . '支付方式：' . $order['pay_type_str'];
            $format_str .= chr(10) . '订单状态：' . $order['status_str'];
            if ($order['cue_field']) {
                foreach ($order['cue_field'] as $cue) {
                    $format_str .= chr(10) . $cue['title'] . ':' . $cue['txt'];
                }
            }
            $format_str .= chr(10) . str_repeat('※', $width);
            $format_str .= chr(10) . '店铺名称：' . $store['name'];
            $format_str .= chr(10) . '店铺电话：' . $store['phone'];
            $format_str .= chr(10) . '店铺地址：' . $store['adress'];
            $format_str .= chr(10) . '打印时间：' . date('Y-m-d H:i:s');
            $format_str .= chr(10) . '谢谢惠顾，欢迎再次光临！';
            $this->orderPrint->newPrint($usePrinter, $format_str);
        }
        return false;
    }
  
    private function foodshop_order($order_id, $status = 0, $isPrint = 0)
    {
        $order = D('Foodshop_order')->get_order_detail(array('order_id' => $order_id), 3);
		$table_service_name = $this->get_table_service_alias($order['store_id']);       
        if (empty($order)) return false;
        
        $prints = $this->getPrints($order['store_id']);
        if (empty($prints)) return false;
        
        $store = D('Merchant_store')->field(true)->where(array('store_id' => $order['store_id']))->find();
        if ($isPrint == 0) {
            $this->foodshop_order_every($order, $prints, $status, 1);
        }
        
        foreach ($prints as $usePrinter) {
            if ($usePrinter['is_main'] == 0) {
                continue;
            }
            if ($status != -1) {
                $statusArr = explode(',', $usePrinter['paid']);
                if (!in_array($status, $statusArr)) {
                    continue;
                }
            }
            $formatData = $this->getThreeData($usePrinter);
            $width = $formatData['width'];
            $firstSpace = $formatData['one'];
            $secondSpace = $formatData['two'];
            $thirdSpace = $formatData['three'];
            $spaceWidth = $formatData['spaceWidth'];
            
            $format_str = '';
            $format_str .= chr(10) . '订单编号:' . $order['real_orderid'];
            $order['name'] && $format_str .= chr(10) . '客户姓名：' . $order['name'];
            $order['phone'] && $format_str .= chr(10) . '客户手机：' . $order['phone'];
            
            if (!empty($order['note']) && $order['note'] != '无') {
                $format_str .= chr(10) . '客户留言:' . $order['note'];
            }
            $format_str .= chr(10) . '桌台类型：' . $order['table_type_name'];
            $format_str .= chr(10) . '桌台名称：' . $order['table_name'];
            floatval($order['book_price']) && $format_str .= chr(10) . '预定金额：' . floatval($order['book_price']);
            
            $format_str .= chr(10) . str_repeat('*', $width * 2);
            $format_str .= chr(10) . str_repeat(' ', $firstSpace) . '品名' . str_repeat(' ', $secondSpace) . '数量' . str_repeat(' ', $thirdSpace) . '单价';;
            if ($order['status'] == 2) { // 就餐中
                $order['total_price'] = D('Foodshop_order')->count_price($order);
            }
            foreach ($order['info'] as $val) {
                if ($val['spec']) {
                    $val['name'] .= '(' . $val['spec'] . ')';
                }
                if ($val['note']) {
                    $val['name'] .= '(备注：' . $val['note'] . ')';
                }
                $len = self::dstrlen($val['name']);
                $len = $len % ($width * 2);
                if ($len > $spaceWidth) {
                    $space = $spaceWidth + 1;
                } else {
                    $space = $spaceWidth + 1 - $len;
                }
                $val['num'] = intval($val['num']) . '';
                $format_str .= chr(10) . self::cutstr($val['name'], $spaceWidth, $width * 2) . self::format($val['num'], $space) . self::format(C('config.Currency_symbol') . floatval($val['price']) . '/' . $val['unit'], $thirdSpace);
            }
            $format_str .= chr(10) . str_repeat('*', $width * 2);
            
            if ($order['book_time_show'] != '--') {
                $format_str .= chr(10) . '预定时间：' . $order['book_time_show'];
            }
            
            if (isset($order['book_pay_type'])&&$order['book_pay_type']) {
                $format_str .= chr(10) . '预定支付方式：' . $order['book_pay_type'];
            }
            if (isset($order['book_pay_time']) && $order['book_pay_time']) {
                $format_str .= chr(10) . '预定支付时间：' . date('Y-m-d H:i', $order['book_pay_time']);
            }
            
            if (isset($order['pay_type'])&&$order['pay_type']) {
                $format_str .= chr(10) . '买单支付方式：' . $order['pay_type'];
            }
            if (isset($order['pay_time']) && $order['pay_time']) {
                $format_str .= chr(10) . '买单支付时间：' . date('Y-m-d H:i', $order['pay_time']);
            }
            //桌台服务费
            if (isset($order['table_service_payable']) && $order['table_service_payable']>0) {
                $format_str .= chr(10) . '应付'.$table_service_name.':' . $order['table_service_payable'];
            }
            if (isset($order['table_service_payment']) && $order['table_service_payment']>0) {
                $format_str .= chr(10) . '实付'.$table_service_name.':' . $order['table_service_payment'];
            }
            if ($order['status'] > 1) {
                $format_str .= chr(10) . '订单总价：' . floatval($order['total_price']);
                if($order['order_from']==2||$order['from_plat']==2){
                    $store_order = M('Store_order')->where(array('business_type'=>'foodshop','business_id'=>$order['order_id'],'paid'=>1))->find();
                    floatval($store_order['discount_price']) && $format_str .= chr(10) . '优惠金额：' . floatval($store_order['discount_price']);
                    floatval($store_order['price']) && $format_str .= chr(10) . '实付金额：' . floatval($store_order['price']);
                }else{
                    if($order['table_discount_money']>0 && $order['table_or_vipcard_discount']==0){
                        floatval($order['mer_table_discount']) && $format_str .= chr(10) . '桌台折扣：' . floatval($order['mer_table_discount']/10);
                        floatval($order['table_discount_money']) && $format_str .= chr(10) . '桌台优惠金额：' . floatval($order['table_discount_money']);
                    }
                    floatval($order['price']) && $format_str .= chr(10) . '实付金额：' . floatval($order['price']);
                }
            }
            
            $format_str .= chr(10) . str_repeat('※', $width);
            $format_str .= chr(10) . '店铺名称：' . $store['name'];
            $format_str .= chr(10) . '店铺电话：' . $store['phone'];
            $format_str .= chr(10) . '店铺地址：' . $store['adress'];
            $format_str .= chr(10) . '打印时间：' . date('Y-m-d H:i:s');
            $format_str .= chr(10) . '谢谢惠顾，欢迎再次光临！';
            fdump($format_str,'foodshop',true);
            $this->orderPrint->newPrint($usePrinter, $format_str);
        }
    }

    private function meal_order($order_id, $status = 0)
    {
        $order = D('Meal_order')->field(true)->where(array('order_id' => $order_id))->find();
        if (empty($order)) return false;
        
        $prints = $this->getPrints($order['store_id']);
        if (empty($prints)) return false;
        $this->meal_order_every($order, $prints);
        $print_format = C('config.print_format');
        
        $print_format = preg_replace('/\{user_name\}/', $order['name'], $print_format);
        $print_format = preg_replace('/\{user_phone\}/', $order['phone'], $print_format);
        $print_format = preg_replace('/\{user_address\}/', $order['address'], $print_format);
        $print_format = preg_replace('/\{user_message\}/', $order['note'], $print_format);
        $print_format = preg_replace('/\{user_num\}/', $order['num'], $print_format);
        $print_format = preg_replace('/\{buy_time\}/', date("Y-m-d H:i:s", $order['dateline']), $print_format);
        $print_format = preg_replace('/\{pay_time\}/', date("Y-m-d H:i:s", $order['pay_time']), $print_format);
        $print_format = preg_replace('/\{use_time\}/', date("Y-m-d H:i:s", $order['use_time']), $print_format);
        if ($order['arrive_time']) {
            $print_format = preg_replace('/\{arrive_time\}/', date("Y-m-d H:i:s", $order['arrive_time']), $print_format);
        } else {
            $print_format = preg_replace('/\{arrive_time\}/', '尽快送达', $print_format);
        }
        
        $goods_list = '';
        if ($order['info']) {
            $list = unserialize($order['info']);
            $meal_ids = array();
            foreach ($list as $row) {
                if (! in_array($row['id'], $meal_ids)) {
                    $meal_ids[] = $row['id'];
                }
            }
            if ($meal_ids) {
                $meals = D('Meal')->field(true)->where(array('meal_id' => array('in', $meal_ids)))->select();
                $mid_pid = array();
                foreach ($meals as $m) {
                    $mid_pid[$m['meal_id']] = $m;
                }
                foreach ($list as $k => $row) {
                    $goods_list .= chr(10) . $row['name'] . ": ".C('config.Currency_symbol') . $row['price'] . " * " . $row['num'] . "({$mid_pid[$row['id']]['unit']})";
                    $row['omark'] && $goods_list .= chr(10) . "菜品备注: " . $row['omark'];
                }
            }
        }
        $print_format = preg_replace('/\{goods_list\}/', $goods_list, $print_format);
        $print_format = preg_replace('/\{goods_count\}/', $order['total'], $print_format);
        $print_format = preg_replace('/\{goods_price\}/', $order['total_price'], $print_format);
        $print_format = preg_replace('/\{minus_price\}/', $order['minus_price'], $print_format);
        $print_format = preg_replace('/\{true_price\}/', $order['total_price'] - $order['minus_price'], $print_format);
        $print_format = preg_replace('/\{orderid\}/', $order['order_id'], $print_format);
        if (empty($order['paid'])) {
            $pay_status = '未支付';
        } else {
            if (empty($order['status'])) {
                $pay_status = '未消费';
            } elseif ($order['status'] == 1) {
                $pay_status = '已消费';
            } elseif ($order['status'] == 2) {
                $pay_status = '已完成';
            } elseif ($order['status'] == 3) {
                $pay_status = '已退款';
            }
        }
        $print_format = preg_replace('/\{pay_status\}/', $pay_status, $print_format);
        $pay_type = isset($this->payarr[$order['pay_type']]) ? $this->payarr[$order['pay_type']] : '';
        if (empty($pay_type)) {
            if ($order['price'] == $order['balance_pay']) {
                $pay_type = '平台余额支付';
            } elseif ($order['price'] == $order['merchant_balance']) {
                $pay_type = '商家会员卡余额支付';
            }
        }
        $print_format = preg_replace('/\{pay_type\}/', $pay_type, $print_format);
        $table_name = '未选择';
        if ($order['meal_type'] == 1) {
            $meal_type = '外送';
        } elseif ($order['meal_type'] == 0) {
            $meal_type = '预定';
            if ($table = D('Merchant_store_table')->field(true)->where(array('pigcms_id' => $order['tableid']))->find()) {
                $table_name = $table['name'];
            }
        } elseif ($order['meal_type'] == 2) {
            $meal_type = '现场pad点餐';
            if ($table = D('Merchant_store_table')->field(true)->where(array('pigcms_id' => $order['tableid']))->find()) {
                $table_name = $table['name'];
            }
        } elseif ($order['meal_type'] == 3) {
            $meal_type = '在线购买';
            if ($table = D('Merchant_store_table')->field(true)->where(array('pigcms_id' => $order['tableid']))->find()) {
                $table_name = $table['name'];
            }
        }
        $print_format = preg_replace('/\{delivery_fee\}/', $order['delivery_fee'], $print_format);
        $print_format = preg_replace('/\{meal_type\}/', $meal_type, $print_format);
        $print_format = preg_replace('/\{table_name\}/', $table_name, $print_format);

        // 优化添加获取店铺信息
        $store = D('Merchant_store')->get_store_by_storeId($order['store_id']);

        $print_format = preg_replace('/\{store_name\}/', $store['name'], $print_format);
        $print_format = preg_replace('/\{store_phone\}/', $store['phone'], $print_format);
        $print_format = preg_replace('/\{store_address\}/', $store['adress'], $print_format);
        
        $print_format = preg_replace('/\{print_time\}/', date('Y-m-d H:i:s'), $print_format);
        
        foreach ($prints as $usePrinter) {
            if ($usePrinter['is_main'] == 0) {
                continue;
            }
            if ($status != -1) {
                $statusArr = explode(',', $usePrinter['paid']);
                if (!in_array($status, $statusArr)) {
                    continue;
                }
            }
            $this->orderPrint->newPrint($usePrinter, $print_format);
        }
        return false;
    }
    
    private function group_order($order_id, $status = 0)
    {
        $order = D('Group_order')->field(true)->where(array('order_id' => $order_id))->find();
        if (empty($order)) return false;
        
        $prints = $this->getPrints($order['store_id']);
        if (empty($prints)) return false;
        
        $print_format = C('config.group_print_format');
        $user = D('User')->field(true)->where(array('uid' => $order['uid']))->find();
        $nickname = isset($user['nickname']) ? $user['nickname'] : '';
        // 优化添加获取店铺信息
        $store = D('Merchant_store')->get_store_by_storeId($order['store_id']);
        
        $print_format = preg_replace('/\{user_name\}/', $nickname, $print_format);
        $print_format = preg_replace('/\{user_phone\}/', $order['phone'], $print_format);
        $print_format = preg_replace('/\{user_address\}/', $order['adress'], $print_format);
        $print_format = preg_replace('/\{orderid\}/', $order['real_orderid'], $print_format);
        
        $print_format = preg_replace('/\{goods_name\}/', $order['order_name'], $print_format);
        $print_format = preg_replace('/\{goods_count\}/', $order['num'], $print_format);
        $print_format = preg_replace('/\{goods_price\}/', $order['total_money'], $print_format);
        $print_format = preg_replace('/\{minus_price\}/', $order['wx_cheap'], $print_format);
        $print_format = preg_replace('/\{true_price\}/', $order['total_money'] - $order['wx_cheap'], $print_format);

        // 处理时间显示
        $use_time = $order['use_time']>0 ? date("Y-m-d H:i:s", $order['use_time']) : '未使用';

        $print_format = preg_replace('/\{buy_time\}/', date("Y-m-d H:i:s", $order['add_time']), $print_format);
        $print_format = preg_replace('/\{pay_time\}/', date("Y-m-d H:i:s", $order['pay_time']), $print_format);
        $print_format = preg_replace('/\{use_time\}/', $use_time, $print_format);
        
        $print_format = preg_replace('/\{store_name\}/', $store['name'], $print_format);
        $print_format = preg_replace('/\{store_phone\}/', $store['phone'], $print_format);
        $print_format = preg_replace('/\{store_address\}/', $store['adress'], $print_format);
        
        if (empty($order['paid'])) {
            $pay_status = '未支付';
        } else {
            if (empty($order['status'])) {
                $pay_status = '未消费';
            } elseif ($order['status'] == 1) {
                $pay_status = '已消费';
            } elseif ($order['status'] == 2) {
                $pay_status = '已完成';
            } elseif ($order['status'] == 3) {
                $pay_status = '已退款';
            }
        }
        
        $pay_type = isset($this->payarr[$order['pay_type']]) ? $this->payarr[$order['pay_type']] : '';
        if (empty($pay_type) && $order['paid']) {
            $pay_type = '余额支付';
        }
        $print_format = preg_replace('/\{pay_status\}/', $pay_status, $print_format);
        $print_format = preg_replace('/\{pay_type\}/', $pay_type, $print_format);
        $print_format = preg_replace('/\{print_time\}/', date('Y-m-d H:i:s'), $print_format);
        
        
        foreach ($prints as $usePrinter) {
            if ($usePrinter['is_main'] == 0) {
                continue;
            }
            if ($status != -1) {
                $statusArr = explode(',', $usePrinter['paid']);
                if (!in_array($status, $statusArr)) {
                    continue;
                }
            }

            $this->orderPrint->newPrint($usePrinter, $print_format);
        }
        return false;
    }
    
    private function waimai_order($order_id, $status = 0)
    {
        $order = D('Group_order')->field(true)->where(array('order_id' => $order_id))->find();
        if (empty($order)) return false;
        
        $prints = $this->getPrints($order['store_id']);
        if (empty($prints)) return false;
        
        $store_where['store_id'] = array('in', $order['store_id']);
        $store_info = D('Merchant_store')->field(true)->where($store_where)->select();
        $deliverStore = D("Deliver_store")->field(true)->where($store_where)->find();
        
        $merchant_where['mer_id'] = array('in', $order['mer_id']);
        $merchant_info = D('Merchant')->field(true)->where($merchant_where)->select();
        
        $orderId[$order_id] = $order_id;
        $deliver_where['order_id'] = array('in', $orderId);
        $deliverSupplyInfo = D("Deliver_supply")->field('`order_id`,`start_time`,`end_time`')->where($deliver_where)->select();
        
        $orderObj = new Waimai_orderModel();
        $now_order = $orderObj->formatArray(array($order), $store_info, $merchant_info, $deliverSupplyInfo);
        $now_order = $now_order[0];
        $now_order['deliver_type'] = $deliverStore['type'];
        
        
        foreach ($prints as $usePrinter) {
            if ($usePrinter['is_main'] == 0) {
                continue;
            }
            if ($status != -1) {
                $statusArr = explode(',', $usePrinter['paid']);
                if (!in_array($status, $statusArr)) {
                    continue;
                }
            }
            $formatData = $this->getThreeData($usePrinter);
            $width = $formatData['width'];
            $firstSpace = $formatData['one'];
            $secondSpace = $formatData['two'];
            $thirdSpace = $formatData['three'];
            
            $format_str = '联系人：' . $now_order['nickname'];
            $format_str .= chr(10) . "联系电话：" . $now_order['phone'];
            $format_str .= chr(10) . "送货地址：" . $now_order['address'];
            $format_str .= chr(10) . "支付方式：" . $now_order['pay_type'];
            if ($now_order['paid'] == 1) {
                $format_str .= chr(10) . "支付状态：已支付";
            } else {
                $format_str .= chr(10) . "支付状态：已支付";
            }
            
            $format_str .= chr(10) . "备注：" . $now_order['desc'];
            $format_str .= chr(10) . str_repeat('*', $width * 2);
            foreach ($now_order['goods_list'] as $val) {
                $format_str .= chr(10) . $val['name'] . ": ".C('config.Currency_symbol') . $val['price'] . " * " . $val['num'];
            }
            $format_str .= chr(10) . str_repeat('*', $width * 2);
            if ($now_order['tools_price'] > 0) {
                $format_str .= chr(10) . "打包费: ".C('config.Currency_symbol') . $now_order['tools_price'];
            }
            
            if ($now_order['send_money'] > 0 && ($now_order['goods_money'] < $now_order['total_money'] || $now_order['total_money'] == 0)) {
                $format_str .= chr(10) . "配送费: ".C('config.Currency_symbol') . $now_order['send_money'];
            }
            $format_str .= chr(10) . "总价: ".C('config.Currency_symbol') . $now_order['price'];
            $discountInfo = json_decode($now_order['discount_detail'], true);
            if (! empty($discountInfo)) {
                foreach ($discountInfo as $val) {
                    $format_str .= chr(10) . "优惠活动：" . $val['desc'] . "-".C('config.Currency_symbol') . $val['discount_money'];
                }
            }
            
            $where['id'] = $now_order['coupon_id'];
            $couponInfo = D("Waimai_user_coupon")->field(true)->where($where)->find();
            $where_coupon['coupon_id'] = $couponInfo['coupon_id'];
            $coupon = D("Waimai_coupon")->field(true)->where($where_coupon)->find();
            $coupon['money'] = $couponInfo['money'];
            $couponInfo = $coupon;
            if (! empty($couponInfo['money'])) {
                $format_str .= chr(10) . "红包：" . $couponInfo['name'] . "-".C('config.Currency_symbol') . $couponInfo['money'];
            }
            $format_str .= chr(10) . "实际支付：".C('config.Currency_symbol') . $now_order['discount_price'];
            $this->orderPrint->newPrint($usePrinter, $format_str);
        }
        return false;
    }
    
    private function appoint_order($order_id, $status = 0)
    {
        $order = D('Appoint_order')->field(true)->where(array('order_id' => $order_id))->find();
        if (empty($order)) return false;
        
        $prints = $this->getPrints($order['store_id']);
        if (empty($prints)) return false;
        
        $order_info = D('Appoint_order')->get_order_detail_by_id($order['uid'], $order['order_id']);
        
        $user = D('User')->field(true)->where(array('uid' => $order['uid']))->find();
        $store_info = D('Merchant_store')->get_store_by_storeId($order_info['store_id']);
        
        foreach ($prints as $usePrinter) {
            if ($usePrinter['is_main'] == 0) {
                continue;
            }
            if ($status != -1) {
                $statusArr = explode(',', $usePrinter['paid']);
                if (!in_array($status, $statusArr)) {
                    continue;
                }
            }
            
            $formatData = $this->getThreeData($usePrinter);
            $width = $formatData['width'];
            $firstSpace = $formatData['one'];
            $secondSpace = $formatData['two'];
            $thirdSpace = $formatData['three'];
            
            $format_str = '';
            if ($order_info['paid'] == 0) {
                $format_str .= chr(10) . '未支付订单';
            } else if ($order_info['paid'] == 1) {
                $format_str .= chr(10) . '已支付订单';
            } elseif ($order_info['paid'] == 2) {
                $format_str .= chr(10) . '已退款订单';
            }
            
            $format_str .= chr(10) . '客户姓名：' . $user['nickname'];
            $format_str .= chr(10) . '客户电话：' . $user['phone'];
            
            if ($order['cue_field']) {
                $cue_field = unserialize($order['cue_field']);
                foreach ($cue_field as $v) {
                    if (isset($v['address'])) {
                        // $format_str .= chr(10) . '客户地址：' .$v['value'];
                        $format_str .= chr(10) . '客户地址：' . $v['address'];
                    }
                }
            }
            
            if ($order_info['content']) {
                $format_str .= chr(10) . '客户留言：' . $order_info['content'];
            }
            $format_str .= chr(10) . '下单时间：' . date('Y-m-d H:i:s', $order['order_time']);
            $format_str .= chr(10) . str_repeat('*', $width * 2);
            if ($order_info['appoint_date'] && $order_info['appoint_date']) {
                $format_str .= chr(10) . '预约时间：' . $order_info['appoint_date'] . ' ' . $order_info['appoint_date'];
            }
            $format_str .= chr(10) . '预约名称：' . $order_info['appoint_name'];
            $format_str .= chr(10) . '预约类型：' . ($order_info['appoint_type'] == 0 ? '到店' : '上门');
            
            if ($order_info['worker_detail']) {
                $worker_detail = $order_info['worker_detail'];
                $format_str .= chr(10) . '工作人员：' . $worker_detail['name'];
            }
            $format_str .= chr(10) . str_repeat('*', $width * 2);
            
            $format_str .= chr(10) . '订单号：' . $order_info['order_id'];
            $format_str .= chr(10) . '定金：'.C('config.Currency_symbol') . ($order_info['product_payment_price']>0?$order_info['product_payment_price']:$order_info['payment_money']);
            
            $database_appoint_product = D('Appoint_product');
            $product_info = $database_appoint_product->get_productlist_by_appointId($order_info['appoint_id']);
            $product_info = reset($product_info);
            if ( $order_info['product_detail']) {
                $format_str .= chr(10) . '总价：'.C('config.Currency_symbol') . $order_info['product_detail']['price'];
            } else {
                $format_str .= chr(10) . '总价：'.C('config.Currency_symbol') . $order_info['appoint_price'];
            }
            
            $format_str .= chr(10) . '订单状态：' . ($order_info['paid'] == 0 ? '未支付' : ($order_info['paid'] == 1 ? '已支付' : '已退款'));
            $format_str .= chr(10) . '服务状态：' . ($order_info['service_status'] == 0 ? '未服务' : '已服务');
            
            if (! $order_info['pay_type']) {
                $format_str .= chr(10) . '支付方式：到店支付';
            } else {
                $format_str .= chr(10) . '支付方式：' . $order_info['pay_type_txt'];
            }
            $format_str .= chr(10) . str_repeat('※', $width);
            $format_str .= chr(10) . '店铺名称：' . $store_info['name'];
            $format_str .= chr(10) . '店铺电话：' . $store_info['phone'];
            $format_str .= chr(10) . '店铺地址：' . $store_info['area_ip_desc'] . chr(32) . $store_info['adress'];
            $format_str .= chr(10) . '打印时间：' . date('Y-m-d H:i:s');
            $format_str .= chr(10) . '谢谢惠顾，欢迎再次光临！';
            $this->orderPrint->newPrint($usePrinter, $format_str);
        }
        return false;
    }
    
    private function store_order($order_id, $status = 0)
    {
        $order = D('Store_order')->field(true)->where(array('order_id' => $order_id))->find();
        if (empty($order)) return false;

        $prints = $this->getPrints($order['store_id']);
        if (empty($prints)) return false;
        
        $store = D('Merchant_store')->field(true)->where(array('store_id' => $order['store_id']))->find();

        if ($order['staff_id']) {
            $staff = D('Merchant_store_staff')->where(['id' => $order['staff_id']])->find();
            if ($staff && $staff['client'] > 0 && $staff['device_id']) {
                $prints = array_filter($prints, function ($v) use ($staff) {
                    //如果是pos机店员下单，商米打印机只允许店员设备出票
                    if ($v['mcode'] == '600002') {
                        return $v['mkey'] == $staff['device_id'];
                    } else {
                        return true;
                    }
                });
            }
        }
       
        foreach ($prints as $usePrinter) {
            if ($usePrinter['is_main'] == 0) {
                continue;
            }

            // if ($status != -1) {
            //     $statusArr = explode(',', $usePrinter['paid']);
            //     if (!in_array($status, $statusArr)) {
            //         continue;
            //     }
            // }

            //快速买单支付成功后打印
            if ($status != -1) {
                $statusArr = explode(',', $usePrinter['store_print']);
                if (!in_array($status, $statusArr)) {
                    continue;
                }
            }
            
            $formatData = $this->getThreeData($usePrinter);
            $width = $formatData['width'];
            $firstSpace = $formatData['one'];
            $secondSpace = $formatData['two'];
            $thirdSpace = $formatData['three'];
            
            $format_str = '';
            $format_str .= chr(10) . '订单编号:' . $order['orderid'];
            $format_str .= chr(10) . '订单总价:' . $order['total_price'];
			if($order['discount_price'] > 0){
				$format_str .= chr(10) . '优惠金额:' . $order['discount_price'];
			}
			if($order['card_price'] > 0){
				$format_str .= chr(10) . '商家优惠券:' . $order['card_price'];
			}
            $format_str .= chr(10) . '实付金额:' . ($order['price'] - $order['card_price']);

            if ($order['balance_pay'] > 0){
                $format_str .= chr(10) . '平台余额金额:' . ($order['balance_pay']);

            }
			if ($order['merchant_balance'] > 0){
                $format_str .= chr(10) . '商家余额金额:' . ($order['merchant_balance']);

            }
			if ($order['card_give_money'] > 0){
                $format_str .= chr(10) . '商家赠送余额金额:' . ($order['card_give_money']);
            }
			if ($order['internal_card_pay']){
                $internal_card_pay_arr = @json_decode($order['internal_card_pay']) ?: [];
                $_card_counter = 0;
                foreach ($internal_card_pay_arr as $_ck => $_cv) {
                    $_card_log_info = M('internal_membership_card_log')->where(['card_id' => $_ck, 'order_id' => $order['orderid'], 'business_type' => ['in', ['store', 'shop_offline','shop', 'cash']]])->find();
                    $_card_str = chr(10);
                    if ($_card_counter == 0) {
                        $_card_str .= L_('一卡通支付：').$_card_str;
                    }
                    $_card_str .= "{$_ck}（使用：{$_cv} 余额：{$_card_log_info['after_balance']}）";
                    $format_str .= $_card_str;
                    $_card_counter++;
                }
            }


            if ($order['pay_time']) {
                $format_str .= chr(10) . '支付时间：' . date('Y-m-d H:i:s', $order['pay_time']);
            }
            $pay_type_str = D('Pay')->get_pay_name($order['pay_type'], $order['is_mobile_pay'], $order['paid']);
            if ($pay_type_str) {
                $format_str .= chr(10) . '支付类型：' . $pay_type_str;
            }
			if($order['desc']){
				$format_str .= chr(10) . '买单备注:' . $order['desc'];
			}
            $format_str .= chr(10) . str_repeat('※', $width);
            $format_str .= chr(10) . '店铺名称：' . $store['name'];
            $format_str .= chr(10) . '店铺电话：' . $store['phone'];
            $format_str .= chr(10) . '店铺地址：' . $store['adress'];
            $format_str .= chr(10) . '打印时间：' . date('Y-m-d H:i:s');
            if($order['staff_name']){
                $format_str .= chr(10) . '收银员：' .$order['staff_name'];
            }
            $this->orderPrint->newPrint($usePrinter, $format_str);
        }
        return $format_str;
        
    }
    
    /**
     * 快店的分单打印
     * @param array $order 订单详情
     * @param array $prints 打印机数组
     * @param int $status  当前状态
     * @param number $printType 打印类型， 0：按菜品归属打印机进行分组打印，1：按商品分类所属打印机进行分组打印
     */
    public function shop_order_every($order, $prints, $status, $printType = 0)
    {
        if ($order['info']) {
            $list = $order['info'];
            if ($printType == 1) {//按照分类打印
                $shopGoodsSortDB = D('Shop_goods_sort');
                $sortGoods = array();
                $fids = array();
                foreach ($list as $row) {
                    $sortIds = $shopGoodsSortDB->getIds($row['sort_id'], $row['store_id']);
                    if ($sortIds) {
                        $fid = array_shift($sortIds);
                        $fids[] = $fid;
                        $sortGoods[$fid][] = $row;
                    }
                }
                if ($fids) {
                    $sortList = $shopGoodsSortDB->field(true)->where(array('sort_id' => array('in', $fids)))->select();
                    $store = D('Merchant_store')->field(true)->where(array('store_id' => $order['store_id']))->find();
                    
                    foreach ($sortList as $sort) {
                        if ($sort['print_id'] && isset($sortGoods[$sort['sort_id']])) {
                            $mainPrint = isset($prints[$sort['print_id']]) ? $prints[$sort['print_id']] : null;
                            if (empty($mainPrint)) {
                                continue;
                            }
                            if ($status != -1) {
                                $statusArr = explode(',', $mainPrint['paid']);
                                if (!in_array($status, $statusArr)) {
                                    continue;
                                }
                            }
                            $formatData = $this->getTwoData($mainPrint);
                            $width = $formatData['width'];
                            $firstSpace = $formatData['one'];
                            $secondSpace = $formatData['two'];
                            
                            $format_str = chr(10) . '商品分类：' . $sort['sort_name'];
                            $format_str .= chr(10) . '店铺名称：' . $store['name'];
                            $format_str .= chr(10) . '店铺电话：' . $store['phone'];
                            $format_str .= chr(10) . '店铺地址：' . $store['adress'];
                            $format_str .= chr(10) . '打印时间：' . date('Y-m-d H:i:s');
                            $format_str .= chr(10) . '订单编号:' . $order['real_orderid'];
                            $format_str .= chr(10) . '客户姓名：' . $order['username'];
                            $format_str .= chr(10) . '客户手机：' . $order['userphone'];
                            if ($order['desc']) {
                                $format_str .= chr(10) . '客户留言:' . $order['desc'];
                            }
                            if ($order['order_from'] != 6) {
                                if ($order['is_pick_in_store'] == 2) {
                                    if($order['address']!=''){
                                        $format_str .= chr(10) . '自提地址：' . $order['address'];
                                    }
                                } else {
                                    $format_str .= chr(10) . '客户地址：' . $order['address'];
                                }
                                
                                $format_str .= chr(10) . '配送方式：' . $order['deliver_str'];
                                $format_str .= chr(10) . '配送状态：' . $order['deliver_status_str'];
                            }
                            $format_str .= chr(10) . '下单时间：' . date('Y-m-d H:i:s', $order['create_time']);
                            if ($order['pay_time']) {
                                $format_str .= chr(10) . '支付时间：' . date('Y-m-d H:i:s', $order['pay_time']);
                            }
                            if ($order['expect_use_time']) {
                                $format_str .= chr(10) . '预计送达时间：' . date('Y-m-d H:i:s', $order['expect_use_time']);
                            }
                            $format_str .= chr(10) . '支付状态：' . $order['pay_status_print'];
                            
                            $format_str .= chr(10) . str_repeat('*', $width * 2);
                            $format_str .= chr(10) . str_repeat(' ', $firstSpace) . '品名' . str_repeat(' ', $secondSpace) . '数量';//16
                            
                            foreach ($sortGoods[$sort['sort_id']] as $sGoods) {
                                if ($sGoods['number']) {
                                    $len = self::dstrlen($sGoods['number']);
                                    $len = $len % ($width * 2);
                                    if ($len > ($width * 2 - 8)) {
                                        $space = ($width * 2 - 7);
                                    } else {
                                        $space = ($width * 2 - 7)- $len;
                                    }
                                    $format_str .= chr(10) . self::cutstr($sGoods['number'], ($width * 2 - 8), $width * 2) . self::format($sGoods['num'], $space);
                                } else {
                                    $sGoods['spec'] && $sGoods['name'] .= '(' . $sGoods['spec'] . ')';
                                    
                                    $len = self::dstrlen($sGoods['name']);
                                    $len = $len % ($width * 2);
                                    if ($len > ($width * 2 - 8)) {
                                        $space = ($width * 2 - 7);
                                    } else {
                                        $space = ($width * 2 - 7)- $len;
                                    }
                                    $format_str .= chr(10) . self::cutstr($sGoods['name'], ($width * 2 - 8), $width * 2) . self::format($sGoods['num'], $space);
                                }
                            }
                            $this->orderPrint->newPrint($mainPrint, $format_str);
                        }
                    }
                }
            } else {
                $goods_ids = array();
                $newList = array();
                foreach ($list as $row) {
                    if (! in_array($row['goods_id'], $goods_ids)) {
                        $goods_ids[] = $row['goods_id'];
                    }
                    $newList[$row['goods_id']][] = $row;
                }
                if ($goods_ids) {
                    $goods = D('Shop_goods')->field(true)->where(array('goods_id' => array('in', $goods_ids)))->select();
                    $printGoodsArr = array();
                    foreach ($goods as $m) {
                        if (isset($printGoodsArr[$m['print_id']])) {
                            $printGoodsArr[$m['print_id']] = array_merge($printGoodsArr[$m['print_id']], $newList[$m['goods_id']]);
                        } else {
                            $printGoodsArr[$m['print_id']] = $newList[$m['goods_id']];
                        }
                    }
                    
                    foreach ($printGoodsArr as $pintid => $goodsList) {
                        $mainPrint = isset($prints[$pintid]) ? $prints[$pintid] : null;
                        if (empty($mainPrint)) {
                            continue;
                        }
                        if ($status != -1) {
                            $statusArr = explode(',', $mainPrint['paid']);
                            if (!in_array($status, $statusArr)) {
                                continue;
                            }
                        }
                        
                        $formatData = $this->getTwoData($mainPrint);
                        $width = $formatData['width'];
                        $firstSpace = $formatData['one'];
                        $secondSpace = $formatData['two'];
                        
                        $format_str = chr(10) . "订单编号：" . $order['real_orderid'];
                        $format_str .= chr(10) . str_repeat('*', $width * 2);
                        $format_str .= chr(10) . str_repeat(' ', $firstSpace) . '品名' . str_repeat(' ', $secondSpace) . '数量';//16
                        
                        foreach ($goodsList as $l) {
                            if ($l['number']) {
                                $len = self::dstrlen($l['number']);
                                $len = $len % ($width * 2);
                                if ($len > ($width * 2 - 8)) {
                                    $space = ($width * 2 - 7);
                                } else {
                                    $space = ($width * 2 - 7)- $len;
                                }
                                $format_str .= chr(10) . self::cutstr($l['number'], ($width * 2 - 8), $width * 2) . self::format($l['num'], $space);
                            } else {
                                $l['spec'] && $l['name'] .= '(' . $l['spec'] . ')';
                                $len = self::dstrlen($l['name']);
                                $len = $len % ($width * 2);
                                if ($len > ($width * 2 - 8)) {
                                    $space = ($width * 2 - 7);
                                } else {
                                    $space = ($width * 2 - 7)- $len;
                                }
                                
                                $format_str .= chr(10) . self::cutstr($l['name'], ($width * 2 - 8), $width * 2) . self::format($l['num'], $space);
                            }
                        }
                        $this->orderPrint->newPrint($mainPrint, $format_str);
                    }
                }
            }
        }
        
    }
	
	//获取餐饮桌台服务费别名
    private function get_table_service_alias($store_id){
        if(!$store_id) return '';
        $info = D('Merchant_store_foodshop')->where(array('store_id'=>$store_id))->field('table_service_fee_alias')->find();
        $name = $info['table_service_fee_alias']?$info['table_service_fee_alias']:L_('服务费');
        return $name;
    }
	

    /*
     *  一菜一单   printType 设置为1，打印机打印全部归属的菜品设置为0
     */
    public function foodshop_menu($order_id, $menus, $status, $printType = 1)
    {
        $order = D('Foodshop_order')->get_order_detail(array('order_id' => $order_id), 3);
        
        if (empty($order)) return false;
        
        $prints = $this->getPrints($order['store_id']);
        if (empty($prints)) return false;

        $list = $order['info'];
        $goods_ids = array();
        $newList = array();
        foreach ($list as $row) {
            $goods_ids[] = $row['id'];
            $goods_gids[] = $row['goods_id'];
            $newList[$row['id']] = $row;
        }
        if ($goods_ids) {
            // $goods = D('Foodshop_goods')->field(true)->where(array('goods_id' => array('in', $goods_gids)))->select();
            $goods_list = D('Foodshop_goods')->get_limit_list_page(array('goods_id' => implode(',', $goods_gids)),0);
            $goods = $goods_list['list'];
            foreach($newList as  &$nlist){
                foreach($goods as $good){
                    if($nlist['goods_id']==$good['goods_id']){
                        // 如果设置分类打印机，使用分类打印机设置20180917
                        $nlist['print_id'] = ($good['is_open_print'] && $good['sort_print_id']) ? $good['sort_print_id'] : $good['print_id'];
                    }
                }
            }
            $printGoodsArr = array();
            // foreach ($goods as $m) {
            //     if (isset($printGoodsArr[$m['print_id']])) {
            //         $printGoodsArr[$m['print_id']][] = $newList[$m['goods_id']];
            //     } else {
            //         $printGoodsArr[$m['print_id']] = array($newList[$m['goods_id']]);
            //     }
            // }
            foreach ($goods as $m) {
                foreach ($menus as $_menus) {
                    if ($_menus['goods_id']==$m['goods_id']) {
                        if (isset($printGoodsArr[$m['print_id']])) {
                            $printGoodsArr[$m['print_id']][] = $_menus;
                        } else {
                            $printGoodsArr[$m['print_id']] = array($_menus);
                        }
                    }
                }
            }
            //分单打印
            foreach ($printGoodsArr as $pintid => $goodsList) {
                $mainPrint = isset($prints[$pintid]) ? $prints[$pintid] : null;
                if (empty($mainPrint)) {
                    continue;
                }
                
                if ($status != -1) {
                    $statusArr = explode(',', $mainPrint['paid']);
                    if (!in_array($status, $statusArr)) {
                        continue;
                    }
                }
                
                $formatData = $this->getTwoData($mainPrint);
                $width = $formatData['width'];
                $firstSpace = $formatData['one'];
                $secondSpace = $formatData['two'];
                if ($printType == 0) {
                    $format_str = chr(10) . "订单编号：" . $order['real_orderid'];
                    $format_str .= chr(10) . "桌台编号：" . $order['table_name'];
                    $format_str .= chr(10) . "打印时间：" . date('Y-m-d H:i');
                    $format_str .= chr(10) . str_repeat('*', $width * 2);
                    $format_str .= chr(10) . str_repeat(' ', $firstSpace) . '品名' . str_repeat(' ', $secondSpace) . '数量';//16
                }
                foreach ($goodsList as $l) {
                    if ($printType == 1) {
                        $format_str = chr(10) . "订单编号：" . $order['real_orderid'];
                        $format_str .= chr(10) . "桌台编号：" . $order['table_name'];
                        $format_str .= chr(10) . "打印时间：" . date('Y-m-d H:i');
                        $format_str .= chr(10) . str_repeat('*', $width * 2);
                        $format_str .= chr(10) . str_repeat(' ', $firstSpace) . '品名' . str_repeat(' ', $secondSpace) . '数量';//16
                    }
                    $l['spec'] && $l['name'] .= '(' . $l['spec'] . ')';
                    if ($l['note']) {
                        $l['name'] .= '(备注：' . $l['note'] . ')';
                    }
                    $len = self::dstrlen($l['name']);
                    $len = $len % ($width * 2);
                    if ($len > ($width * 2 - 8)) {
                        $space = ($width * 2 - 7);
                    } else {
                        $space = ($width * 2 - 7)- $len;
                    }
                    $format_str .= chr(10) . self::cutstr($l['name'], ($width * 2 - 8), $width * 2) . self::format($l['num'], $space);
                    
                    if ($printType == 1) {
                        $format_str .= chr(10) . chr(10) . chr(10) . chr(10);
                        $this->orderPrint->newPrint($mainPrint, $format_str);
                    }
                }
                if ($printType == 0) {
                    $this->orderPrint->newPrint($mainPrint, $format_str);
                }
            }
            
            //打印全部(主打印机)
            foreach ($prints as $mainPrint) {
                $statusArr = explode(',', $mainPrint['paid']);
                if ($mainPrint['is_main'] == 0 && !in_array(4, $statusArr)) {
                    continue;
                }
                $formatData = $this->getTwoData($mainPrint);
                $width = $formatData['width'];
                $firstSpace = $formatData['one'];
                $secondSpace = $formatData['two'];
                $print_all_str = '';
                $print_all_str .= chr(10) . "订单编号：" . $order['real_orderid'];
                $print_all_str .= chr(10) . "桌台编号：" . $order['table_name'];
                $print_all_str .= chr(10) . "打印时间：" . date('Y-m-d H:i');
                
                $print_all_str .= chr(10) . str_repeat('*', $width * 2);
                $print_all_str .= chr(10) . str_repeat(' ', $firstSpace) . '品名' . str_repeat(' ', $secondSpace) . '数量';//16
                foreach ($menus as $l) {
                    $l['spec'] && $l['name'] .= '(' . $l['spec'] . ')';
                    if ($l['note']) {
                        $l['name'] .= '(备注：' . $l['note'] . ')';
                    }
                    $len = self::dstrlen($l['name']);
                    $len = $len % ($width * 2);
                    if ($len > ($width * 2 - 8)) {
                        $space = ($width * 2 - 7);
                    } else {
                        $space = ($width * 2 - 7)- $len;
                    }
                    $print_all_str .= chr(10) . self::cutstr($l['name'], ($width * 2 - 8), $width * 2) . self::format($l['num'], $space);
                }
                if ($print_all_str) {
                    $this->orderPrint->newPrint($mainPrint, $print_all_str);
                }
            }
        }
    }

    public function foodshop_order_every($order, $prints, $status, $printType = 1)
    {
        if ($order['info']) {
			$table_service_name = $this->get_table_service_alias($order['store_id']);
            $list = $order['info'];
            $goods_ids = array();
            $newList = array();
            foreach ($list as $row) {
                $goods_ids[] = $row['id'];
                $goods_gids[] = $row['goods_id'];
                $newList[$row['id']] = $row;
            }
            if ($goods_ids) {
                // $goods = D('Foodshop_goods')->field(true)->where(array('goods_id' => array('in', $goods_gids)))->select();
                $goods_list = D('Foodshop_goods')->get_limit_list_page(array('goods_id' => implode(',', $goods_gids)),0);
                $goods = $goods_list['list'];
                foreach($newList as  &$nlist){
                    foreach($goods as $good){
                        if($nlist['goods_id']==$good['goods_id']){
                            // $nlist['print_id'] = $good['print_id'];
                            // 如果设置分类打印机，使用分类打印机设置20180917
                            $nlist['print_id'] = ($good['is_open_print'] && $good['sort_print_id']) ? $good['sort_print_id'] : $good['print_id'];
                        }
                    }
                }

                $printGoodsArr = array();
                foreach ($newList as $m) {
                    if (isset($printGoodsArr[$m['print_id']])) {
                        $printGoodsArr[$m['print_id']][] = $newList[$m['id']];
                    } else {
                        $printGoodsArr[$m['print_id']] = array($newList[$m['id']]);
                    }
                }

                
                foreach ($printGoodsArr as $pintid => $goodsList) {
                    $mainPrint = isset($prints[$pintid]) ? $prints[$pintid] : null;
                    if (empty($mainPrint)) {
                        continue;
                    }
                    
                    if ($status != -1) {
                        $statusArr = explode(',', $mainPrint['paid']);
                        if (!in_array($status, $statusArr)) {
                            continue;
                        }
                    }
                    
                    $formatData = $this->getTwoData($mainPrint);
                    $width = $formatData['width'];
                    $firstSpace = $formatData['one'];
                    $secondSpace = $formatData['two'];
                    
                    if ($printType == 0) {
                        $format_str = chr(10) . "订单编号：" . $order['real_orderid'];
                        $format_str .= chr(10) . "桌台编号：" . $order['table_name'];
                        $format_str .= chr(10) . str_repeat('*', $width * 2);
                        $format_str .= chr(10) . str_repeat(' ', $firstSpace) . '品名' . str_repeat(' ', $secondSpace) . '数量';
                    }
                    foreach ($goodsList as $l) {
                        if ($printType == 1) {
                            $format_str = chr(10) . "订单编号：" . $order['real_orderid'];
                            $format_str .= chr(10) . "桌台编号：" . $order['table_name'];
                            $format_str .= chr(10) . str_repeat('*', $width * 2);
                            $format_str .= chr(10) . str_repeat(' ', $firstSpace) . '品名' . str_repeat(' ', $secondSpace) . '数量';
                        }
                        
                        $l['spec'] && $l['name'] .= '(' . $l['spec'] . ')';
                        if ($l['note']) {
                            $l['name'] .= '(备注：' . $l['note'] . ')';
                        }
                        $len = self::dstrlen($l['name']);
                        $len = $len % ($width * 2);
                        if ($len > ($width * 2 - 8)) {
                            $space = ($width * 2 - 7);
                        } else {
                            $space = ($width * 2 - 7)- $len;
                        }
                        $format_str .= chr(10) . self::cutstr($l['name'], ($width * 2 - 8), $width * 2) . self::format($l['num'], $space);
                        if ($printType == 1) {
                            $format_str .= chr(10) . chr(10) . chr(10) . chr(10);
                            fdump($format_str,'foodshop',true);
                            $this->orderPrint->newPrint($mainPrint, $format_str);
                        }
                    }
                    if ($printType == 0) {
                        $this->orderPrint->newPrint($mainPrint, $format_str);
                    }
                }
            }
        }
    }
      

     // 餐饮打印模式 新
    /*
        $isPrint 是否不打印后厨菜单 foodshop_order_new使用新版格式兼容有线打印机商米打印机字体大小设置，此方法废弃
    */
    private function foodshop_order_new_old($order_id, $status = 0, $isPrint = 0, $staff_name = '')
    {
        $order = D('Foodshop_order')->get_order_detail(array('order_id' => $order_id), 3);
        $table_service_name = $this->get_table_service_alias($order['store_id']);
        if (empty($order)) return false;
        
        $prints = $this->getPrints($order['store_id']);
        if (empty($prints)) return false;
        
        $store = D('Merchant_store')->field(true)->where(array('store_id' => $order['store_id']))->find();
        if ($isPrint == 0 ) { // 后厨补打
            $this->foodshop_order_every_new($order, $prints, $status, 0, $staff_name);
            return true;
        }
        
        foreach ($prints as $usePrinter) {
            if ($usePrinter['is_main'] == 0) {
                continue;
            }
            if ($status != -1) {
                $statusArr = explode(',', $usePrinter['paid']);
                if (!in_array($status, $statusArr)) {
                    continue;
                }
            }
            $formatData = $this->getThreeData($usePrinter);
            $width = $formatData['width'];
            $firstSpace = $formatData['one'];
            $secondSpace = $formatData['two'];
            $thirdSpace = $formatData['three'];
            $spaceWidth = $formatData['spaceWidth'];
            
            $format_str = '';
            $format_str .= chr(10) . str_repeat(' ', $width - 8) .$store['name'] ;
            if ($order['status'] <= 2) { // 预结单
                $format_str .= chr(10) .str_repeat(' ', $width - 4) . '预结单';
            }else{
                $format_str .= chr(10) .str_repeat(' ', $width - 4) . '结账单';
            }
            $format_str .= chr(10) . '订单编号:' . $order['real_orderid'];
            $format_str .= chr(10) . "流水号：" . $order['fetch_number'];
            $order['name'] && $format_str .= chr(10) . '客户姓名：' . $order['name'];
            $order['phone'] && $format_str .= chr(10) . '客户手机：' . $order['phone'];

            $format_str .= chr(10) . '桌台类型：' . $order['table_type_name'];
            if ($usePrinter['is_big'] == 1) {
                $format_str .= '<FH2><FW2>' . chr(10) . "桌台名称：" . $order['table_name'];
                $format_str .= chr(10) . "订单来源：" . $this->order_from_arr[$order['order_from']] . '</FW2></FH2>';
            } elseif ($usePrinter['is_big'] == 2) {
                $format_str .= '<FB>' . chr(10) . "桌台名称：" . $order['table_name'];
                $format_str .= chr(10) . "订单来源：" . $this->order_from_arr[$order['order_from']] . '</FB>';
            }else{
                $format_str .= '<FW><FH>' . chr(10) . "桌台名称：" . $order['table_name'];
                $format_str .= chr(10) . "订单来源：" . $this->order_from_arr[$order['order_from']] . '</FW></FH>';
            }
            // $format_str .= chr(10) . '桌台名称：' . $order['table_name'];
            // $format_str .= chr(10) . "订单来源：" . $this->order_from_arr[$order['order_from']];
            $staff_name && $format_str .= chr(10) . "操作员：" . $staff_name;
            floatval($order['book_price']) && $format_str .= chr(10) . '预定金额：' . floatval($order['book_price']);
            
            $format_str .= chr(10) . str_repeat('-', $width * 2);
            $format_str .= chr(10) . str_repeat(' ', $firstSpace) . '品名' . str_repeat(' ', $secondSpace) . '数量' . str_repeat(' ', $thirdSpace) . '单价';;
            if ($order['status'] == 2) { // 就餐中
                $order['total_price'] = D('Foodshop_order')->count_price($order);
            }
            $total_num = 0;
            foreach ($order['info'] as $val) {
                $total_num += $val['num'];
                $len = self::dstrlen($val['name']);
                $len = $len % ($width * 2);
                if ($len > $spaceWidth) {
                    $space = $spaceWidth + 1;
                } else {
                    $space = $spaceWidth + 1 - $len;
                }
                $val['num'] = intval($val['num']) . '';
                $name_desc = chr(10) . self::cutstr($val['name'], $spaceWidth, $width * 2) . self::format($val['num'], $space) . self::format(C('config.Currency_symbol') . floatval($val['price']) . '/' . $val['unit'], $thirdSpace) ;
                if ($val['spec']) {
                    $name_desc .= chr(10) . '  [' . $val['spec'] . ']';
                }
                if ($val['note']) {
                    $name_desc .= chr(10) . '  [备注：' . $val['note'] . ']';
                }

                if ($usePrinter['is_big'] == 1) {
                    $format_str .= '<FH2><FW2>' . $name_desc . '</FW2></FH2>';
                } elseif ($usePrinter['is_big'] == 2) {
                    $format_str .= '<FB>' . $name_desc . '</FB>';
                }else{
                    $format_str .= '<FH><FW>' . $name_desc . '</FH></FW>';
                }

                // $format_str .= chr(10) . self::cutstr($val['name'], $spaceWidth, $width * 2) . self::format($val['num'], $space) . self::format(C('config.Currency_symbol') . floatval($val['price']) . '/' . $val['unit'], $thirdSpace);
            }
            $format_str .= chr(10) . str_repeat('-', $width * 2);
            
            if ($order['book_time_show'] != '--') {
                $format_str .= chr(10) . '预定时间：' . $order['book_time_show'];
            }
            
            if (isset($order['book_pay_type'])&&$order['book_pay_type']) {
                $format_str .= chr(10) . '预定支付方式：' . $order['book_pay_type'];
            }
            if (isset($order['book_pay_time']) && $order['book_pay_time']) {
                $format_str .= chr(10) . '预定支付时间：' . date('Y-m-d H:i', $order['book_pay_time']);
            }
            
            if (isset($order['pay_type'])&&$order['pay_type']) {
                $format_str .= chr(10) . '买单支付方式：' . $order['pay_type'];
            }
            if (isset($order['pay_time']) && $order['pay_time']) {
                $format_str .= chr(10) . '买单支付时间：' . date('Y-m-d H:i', $order['pay_time']);
            }
            //桌台服务费
            if (isset($order['table_service_payable']) && $order['table_service_payable']>0) {
                $format_str .= chr(10) . '应付'.$table_service_name.':' . $order['table_service_payable'];
            }
            if (isset($order['table_service_payment']) && $order['table_service_payment']>0) {
                $format_str .= chr(10) . '实付'.$table_service_name.':' . $order['table_service_payment'];
            }
            
            if ($order['status'] > 1) {
                if ($total_num) {
                    if ($order['status']<=2) {
                        $format_str .= chr(10) . '共' . $total_num . '件，应付金额：' . floatval($order['total_price']);
                    }else{
                        $format_str .= chr(10) . '共' . $total_num . '件，订单总价：' . floatval($order['total_price']);
                    }
                }else{
                    if ($order['status']<=2) {
                        $format_str .= chr(10) . '应付金额：' . floatval($order['total_price']);
                    }else{
                        $format_str .= chr(10) . '订单总价：' . floatval($order['total_price']);
                    } 
                }
                $format_str .= chr(10) . '订单总价：'.$total_num.'订单总价：' . floatval($order['total_price']);
                if($order['order_from']==2||$order['from_plat']==2){
                    $store_order = M('Store_order')->where(array('business_type'=>'foodshop','business_id'=>$order['order_id'],'paid'=>1))->find();
                    floatval($store_order['discount_price']) && $format_str .= chr(10) . '优惠金额：' . floatval($store_order['discount_price']);
                    floatval($store_order['price']) && $format_str .= chr(10) . '实付金额：' . floatval($store_order['price']);
                }else{
                    if($order['table_discount_money']>0 && $order['table_or_vipcard_discount']==0){
                        floatval($order['mer_table_discount']) && $format_str .= chr(10) . '桌台折扣：' . floatval($order['mer_table_discount']/10);
                        floatval($order['table_discount_money']) && $format_str .= chr(10) . '桌台优惠金额：' . floatval($order['table_discount_money']);
                    }
                    floatval($order['price']) && $format_str .= chr(10) . '实付金额：' . floatval($order['price']);
                }
            }
            if (!empty($order['note']) && $order['note'] != '无') {
                $format_str .= chr(10) . str_repeat('-', $width * 2);
                if ($usePrinter['is_big'] == 1) {
                    $format_str .= '<FH2><FW2>' . chr(10) . '整单留言:' . $order['note']. '</FW2></FH2>';
                } elseif ($usePrinter['is_big'] == 2) {
                    $format_str .= '<FB>' . chr(10) . '整单留言:' . $order['note'] . '</FB>';
                }else{
                    $format_str .= '<FH><FW>' . chr(10) . '整单留言:' . $order['note'] . '</FH></FW>';
                }
            }
            $format_str .= chr(10) . str_repeat('※', $width);
            // $format_str .= chr(10) . '店铺名称：' . $store['name'];
            $format_str .= chr(10) . '店铺电话：' . $store['phone'];
            $format_str .= chr(10) . '店铺地址：' . $store['adress'];
            $format_str .= chr(10) . '打印时间：' . date('Y-m-d H:i:s');
            $format_str .= chr(10) . '谢谢惠顾，欢迎再次光临！';
            $this->orderPrint->newPrint($usePrinter, $format_str);
        }
    }


    /*
     *  一菜一单   printType 设置为1，打印机打印全部归属的菜品设置为0
     *  使用新版格式兼容有线打印机商米打印机字体大小设置，此方法废弃
     */
    public function foodshop_menu_new_old($order_id, $menus, $status, $printType = 1, $is_back = 0, $staff_name='', $old_status = 0)
    {
        $order = D('Foodshop_order')->get_order_detail(array('order_id' => $order_id), 3);
        
        if (empty($order)) return false;

        $now_store = D('Merchant_store_foodshop')->where(array('store_id' => $order['store_id']))->find();
        
        $prints = $this->getPrints($order['store_id']);

        // 排除主打印机设置不打印后厨的打印机
        foreach ($prints as $key=>$l) {
            if ($l['is_main']&&$l['is_print_menu']==0) {
                unset($prints[$key]);
                continue;
            }
        }
        if (empty($prints)) return false;

        $list = $order['info'];
        if ($is_back == 1) {
            $list = $menus;
        }
        $goods_ids = array();
        $newList = array();
        foreach ($list as $row) {
            $goods_ids[] = $row['id'];
            $goods_gids[] = $row['goods_id'];
            $newList[$row['id']] = $row;
        }
        if ($goods_ids) {
            // $goods = D('Foodshop_goods')->field(true)->where(array('goods_id' => array('in', $goods_gids)))->select();
            $goods_list = D('Foodshop_goods')->get_limit_list_page(array('goods_id' => implode(',', $goods_gids)),0);
            $goods = $goods_list['list'];
            foreach($newList as  &$nlist){
                foreach($goods as $good){
                    if($nlist['goods_id']==$good['goods_id']){
                        // 如果设置分类打印机，使用分类打印机设置20180917
                        $nlist['print_id'] = ($good['is_open_print'] && $good['sort_print_id']) ? $good['sort_print_id'] : $good['print_id'];
                    }
                }
            }
            $printGoodsArr = array();
            foreach ($menus as &$_menus) {
                foreach ($newList as $m) {
                    if ($_menus['goods_id']==$m['goods_id']) {
                        $_menus['print_id'] = explode(',', $m['print_id']);
                    }
                }
                if ($_menus['print_id']) {
                    foreach ($_menus['print_id'] as $key => $value) {
                        if (isset($printGoodsArr[$value])) {
                            $printGoodsArr[$value][] = $_menus;
                        } else {
                            $printGoodsArr[$value] = array($_menus);
                        }
                    }
                }
                
            }
            //分单打印
            foreach ($printGoodsArr as $pintid => $goodsList) {
                $mainPrint = isset($prints[$pintid]) ? $prints[$pintid] : null;
                if (empty($mainPrint)) {
                    continue;
                }
                // 打印方式
                if ($mainPrint['print_way'] == 2) {// 整单打印不打印一菜一单
                    continue;
                }
                
                // if ($status != -1) {
                //     $statusArr = explode(',', $mainPrint['paid']);
                //     if (!in_array($status, $statusArr)) {
                //         continue;
                //     }
                // }
                $formatData = $this->getTwoData($mainPrint);
                $width = $formatData['width'];
                $firstSpace = $formatData['one'];
                $secondSpace = $formatData['two']+2;
                if ($printType == 0) {
                    if ($is_back == 1) {
                        $format_str = chr(10) . str_repeat('-', $width - 8) ." #".$order['fetch_number']."退菜单 ".str_repeat('*', $width - 8);
                    }else{
                        if ($old_status<2) {
                            $name = '点菜单';
                        }else{
                            $name = '加菜单';
                        }
                        $format_str = chr(10) . str_repeat('-', $width - 8) ." #".$order['fetch_number'].$name." ".str_repeat('-', $width - 8);
                    }
                    $format_str .= chr(10) . "订单编号：" . $order['real_orderid'];
                    if ($mainPrint['is_big'] == 1) {
                            $format_str .= '<FH2><FW2>' . chr(10) . "桌台编号：" . $order['table_name'];
                        $format_str .= chr(10) . "订单来源：" . $this->order_from_arr[$order['order_from']] . '</FW2></FH2>';
                    } elseif ($mainPrint['is_big'] == 2) {
                        $format_str .= '<FB>' . chr(10) . "桌台编号：" . $order['table_name'];
                        $format_str .= chr(10) . "订单来源：" . $this->order_from_arr[$order['order_from']] . '</FB>';
                    }else{
                        $format_str .= '<FH><FW>' . chr(10) . "桌台编号：" . $order['table_name'];
                        $format_str .= chr(10) . "订单来源：" . $this->order_from_arr[$order['order_from']] . '</FW></FH>';
                    }
                    // $format_str .= chr(10) . "桌台编号：" . $order['table_name'];
                    // $format_str .= chr(10) . "订单来源：" . $this->order_from_arr[$order['order_from']];
                    $staff_name && $format_str .= chr(10) . "操作员：" . $staff_name;
                    $format_str .= chr(10) . "打印时间：" . date('Y-m-d H:i');
                    $format_str .= chr(10) . str_repeat('-', $width * 2);
                    $format_str .= chr(10) . str_repeat(' ', $firstSpace) . '品名' . str_repeat(' ', $secondSpace) . '数量';//16
                }
                foreach ($goodsList as $l) {
                    if ($printType == 1) {
                        if ($is_back == 1) {
                            $format_str = chr(10) . str_repeat('-', $width - 8) ." #".$order['fetch_number']."退菜单 ".str_repeat('*', $width - 8);
                        }else{
                            if ($old_status<2) {
                                $name = '点菜单';
                            }else{
                                $name = '加菜单';
                            }
                            $format_str = chr(10) . str_repeat('-', $width - 8) ." #".$order['fetch_number']. $name. " ".str_repeat('-', $width - 8);
                        }

                        $format_str .= chr(10) . "订单编号：" . $order['real_orderid'];
                      
                        if ($mainPrint['is_big'] == 1) {
                            $format_str .= '<FH2><FW2>' . chr(10) . "桌台编号：" . $order['table_name'];
                            $format_str .= chr(10) . "订单来源：" . $this->order_from_arr[$order['order_from']] . '</FW2></FH2>';
                        } elseif ($mainPrint['is_big'] == 2) {
                            $format_str .= '<FB>' . chr(10) . "桌台编号：" . $order['table_name'];
                            $format_str .= chr(10) . "订单来源：" . $this->order_from_arr[$order['order_from']] . '</FB>';
                        }else{
                            $format_str .= '<FH><FW>' . chr(10) . "桌台编号：" . $order['table_name'];
                            $format_str .= chr(10) . "订单来源：" . $this->order_from_arr[$order['order_from']] . '</FW></FH>';
                        }

                        // $format_str .= chr(10) . "桌台编号：" . $order['table_name'];
                        // $format_str .= chr(10) . "订单来源：" . $this->order_from_arr[$order['order_from']];

                        $staff_name && $format_str .= chr(10) . "操作员：" . $staff_name;
                        $format_str .= chr(10) . "打印时间：" . date('Y-m-d H:i');
                        $format_str .= chr(10) . str_repeat('-', $width * 2);
                        $format_str .= chr(10) . str_repeat(' ', $firstSpace) . '品名' . str_repeat(' ', $secondSpace) . '数量';//16
                    }

                    // 退菜
                    if ($is_back==1) {
                        $l['name'] = '【退】'. $l['name'];
                    }

                    $len = self::dstrlen($l['name']);
                    $len = $len % ($width * 2);
                    if ($len > ($width * 2 - 8)) {
                        $space = ($width * 2 - 7);
                    } else {
                        $space = ($width * 2 - 7)- $len;
                    }

                    $name_desc = chr(10) . self::cutstr($l['name'], ($width * 2 - 8), $width * 2) . self::format(intval($l['num']), $space);
                    if ($l['spec']) {
                        $name_desc .= chr(10) . '  [' . $l['spec'] . ']';
                    }
                    if ($l['note']) {
                        $name_desc .= chr(10) . '  [备注：' . $l['note'] . ']';
                    }
                    if ($mainPrint['is_big'] == 1) {
                        $format_str .= '<FH2><FW2>' . $name_desc . '</FW2></FH2>';
                    } elseif ($format_str['is_big'] == 2) {
                        $format_str .= '<FB>' . $name_desc . '</FB>';
                    }else{
                        $format_str .= '<FH><FW>' . $name_desc . '</FW></FH>';
                    }

                    // $format_str .= chr(10) . self::cutstr($l['name'], ($width * 2 - 8), $width * 2) . self::format($l['num'], $space);
                    
                    if ($printType == 1) {
                        if (!empty($order['note']) && $order['note'] != '无') {
                            $format_str .= chr(10) . str_repeat('*', $width * 2);
                            if ($mainPrint['is_big'] == 1) {
                                $format_str .= '<FH2><FW2>' . chr(10) . '整单留言:' . $order['note']. '</FW2></FH2>';
                            } elseif ($mainPrint['is_big'] == 2) {
                                $format_str .= '<FB>' . chr(10) . '整单留言:' . $order['note'] . '</FB>';
                            }else{
                                $format_str .= '<FH><FW>' . chr(10) . '整单留言:' . $order['note'] . '</FH></FW>';
                            }
                        }
                        $format_str .= chr(10) . chr(10) . chr(10) . chr(10);
                        $this->orderPrint->newPrint($mainPrint, $format_str);
                    }
                }
                if ($printType == 0) {
                    if (!empty($order['note']) && $order['note'] != '无') {
                        $format_str .= chr(10) . str_repeat('-', $width * 2);
                        if ($mainPrint['is_big'] == 1) {
                            $format_str .= '<FH2><FW2>' . chr(10) . '整单留言:' . $order['note']. '</FW2></FH2>';
                        } elseif ($mainPrint['is_big'] == 2) {
                            $format_str .= '<FB>' . chr(10) . '整单留言:' . $order['note'] . '</FB>';
                        }else{
                            $format_str .= '<FH><FW>' . chr(10) . '整单留言:' . $order['note'] . '</FH></FW>';
                        }
                    }
                    $this->orderPrint->newPrint($mainPrint, $format_str);
                }
            }
            
            //打印全部(主打印机)
            foreach ($prints as $mainPrint) {
                // $statusArr = explode(',', $mainPrint['paid']);
                // if ($mainPrint['is_main'] == 0 && !in_array(4, $statusArr)) {
                //     //排除 是非主打印机且没有勾打印总单的打印机
                //     continue;
                // }

                // 打印方式
                if ($mainPrint['is_main'] == 0 && $mainPrint['print_way'] == 1) {// 副打印机 一菜一单不打印整单
                    continue;
                }

                $formatData = $this->getTwoData($mainPrint);
                $width = $formatData['width'];
                $firstSpace = $formatData['one'];
                $secondSpace = $formatData['two']+2;
                $print_all_str = '';
                if ($is_back == 1) {
                    $print_all_str .= chr(10) . str_repeat('-', $width - 8) ." #".$order['fetch_number']."退菜单 ".str_repeat('-', $width - 8);
                }else{
                    if ($old_status<2) {
                        $name = '点菜单';
                    }else{
                        $name = '加菜单';
                    }
                    $print_all_str .= chr(10) . str_repeat('-', $width - 8) ." #".$order['fetch_number'].$name." ".str_repeat('-', $width - 8);
                }
                $print_all_str .= chr(10) . "订单编号：" . $order['real_orderid'];

                if ($mainPrint['is_big'] == 1) {
                    $print_all_str .= '<FH2><FW2>' . chr(10) . "桌台编号：" . $order['table_name'];
                    $print_all_str .= chr(10) . "订单来源：" . $this->order_from_arr[$order['order_from']] . '</FW2></FH2>';
                } elseif ($mainPrint['is_big'] == 2) {
                    $print_all_str .= '<FB>' . chr(10) . "桌台编号：" . $order['table_name'];
                    $print_all_str .= chr(10) . "订单来源：" . $this->order_from_arr[$order['order_from']] . '</FB>';
                }else{
                    $print_all_str .= '<FH><FW>' . chr(10) . "桌台编号：" . $order['table_name'];
                    $print_all_str .= chr(10) . "订单来源：" . $this->order_from_arr[$order['order_from']] . '</FW></FH>';
                }

                // $print_all_str .= chr(10) . "桌台编号：" . $order['table_name'];
                // $print_all_str .= chr(10) . "订单来源：" . $this->order_from_arr[$order['order_from']];

                $staff_name && $print_all_str .= chr(10) . "操作员：" . $staff_name;
                $print_all_str .= chr(10) . "打印时间：" . date('Y-m-d H:i');
                
                $print_all_str .= chr(10) . str_repeat('-', $width * 2);
                $print_all_str .= chr(10) . str_repeat(' ', $firstSpace) . '品名' . str_repeat(' ', $secondSpace) . '数量';//16
                $has_goods = false;
                foreach ($menus as $l) {
                    if ($now_store['print_belong_goods']==1 && !in_array($mainPrint['pigcms_id'], $l['print_id'])) {
                        continue;
                    }
                    $has_goods = true;

                    // 退菜
                    if ($is_back==1) {
                        $l['name'] = '【退】'. $l['name'];
                    }

                    $len = self::dstrlen($l['name']);
                    $len = $len % ($width * 2);
                    if ($len > ($width * 2 - 8)) {
                        $space = ($width * 2 - 7);
                    } else {
                        $space = ($width * 2 - 7)- $len;
                    }

                    $name_desc = chr(10) . self::cutstr($l['name'], ($width * 2 - 8), $width * 2) . self::format(intval($l['num']), $space);
                    if ($l['spec']) {
                        $name_desc .= chr(10) . '  [' . $l['spec'] . ']';
                    }
                    if ($l['note']) {
                        $name_desc .= chr(10) . '  [备注：' . $l['note'] . ']';
                    }
                    if ($mainPrint['is_big'] == 1) {
                        $print_all_str .= '<FH2><FW2>' . $name_desc . '</FW2></FH2>';
                    } elseif ($format_str['is_big'] == 2) {
                        $print_all_str .= '<FB>' . $name_desc . '</FB>';
                    }else{
                        $print_all_str .= '<FH><FW>' . $name_desc . '</FW></FH>';
                    }

                    // $print_all_str .= chr(10) . self::cutstr($l['name'], ($width * 2 - 8), $width * 2) . self::format($l['num'], $space);
                }
                if ($print_all_str&&$has_goods) {
                    if (!empty($order['note']) && $order['note'] != '无') {
                        $print_all_str .= chr(10) . str_repeat('-', $width * 2);
                        if ($mainPrint['is_big'] == 1) {
                            $print_all_str .= '<FH2><FW2>' . chr(10) . '整单留言:' . $order['note']. '</FW2></FH2>';
                        } elseif ($mainPrint['is_big'] == 2) {
                            $print_all_str .= '<FB>' . chr(10) . '整单留言:' . $order['note'] . '</FB>';
                        }else{
                            $print_all_str .= '<FH><FW>' . chr(10) . '整单留言:' . $order['note'] . '</FH></FW>';
                        }
                    }
                    $this->orderPrint->newPrint($mainPrint, $print_all_str);
                }
            }
        }
    }

     // 餐饮打印模式 新
    /*
        $isPrint 是否不打印后厨菜单
    */
    private function foodshop_order_new($order_id, $status = 0, $isPrint = 0, $staff_name = '')
    {
        $order = D('Foodshop_order')->get_order_detail(array('order_id' => $order_id), 3);
        $table_service_name = $this->get_table_service_alias($order['store_id']);
        if (empty($order)) return false;
        
        $prints = $this->getPrints($order['store_id']);
        if (empty($prints)) return false;
        
        $store = D('Merchant_store')->field(true)->where(array('store_id' => $order['store_id']))->find();
        if ($isPrint == 0 ) { // 后厨补打
            $this->foodshop_order_every_new($order, $prints, $status, 0, $staff_name);
            return true;
        }
        
        foreach ($prints as $usePrinter) {
            if ($usePrinter['is_main'] == 0) {
                continue;
            }
            if ($status != -1) {
                $statusArr = explode(',', $usePrinter['paid']);
                if (!in_array($status, $statusArr)) {
                    continue;
                }
            }

            // 打印内容头部信息
            $format_arr = $this->foodshop_header($order,$usePrinter,$staff_name,0,$store,0);

            // 获得数量和价格的最大长度（和下面名字组合判断是否换行）
            $num_price_length = 0;
            foreach ($order['info'] as $val) {
                $num_str = '×'.$val['num'] .'  '.floatval($val['price']);
                $tmp_length = (strlen($num_str) + mb_strlen($num_str,'UTF8')) / 2;
                if ($tmp_length>$num_price_length) {
                    $num_price_length = $tmp_length;
                }
            }

            // $format_str .= chr(10) . str_repeat('-', $width * 2);
            // $format_str .= chr(10) . str_repeat(' ', $firstSpace) . '品名' . str_repeat(' ', $secondSpace) . '数量' . str_repeat(' ', $thirdSpace) . '单价';;
            if ($order['status'] == 2) { // 就餐中
                $order['total_price'] = D('Foodshop_order')->count_price($order);
            }
            $total_num = 0;
            foreach ($order['info'] as $val) {
                // 商品总数
                $total_num += $val['num'];

                // 打印内容商品信息
                $body_arr = $this->foodshop_body($val,$usePrinter,$num_price_length);
                $format_arr = array_merge($format_arr,$body_arr);
            }

            // 商品总数
            $order['total_num'] = $total_num;

            // 打印内容底部信息
            $footer_arr = $this->foodshop_footer($order,$usePrinter,$staff_name,0,$store);
            $format_arr = array_merge($format_arr,$footer_arr);
	    
            // 根据打印机返回对应数据格式
            $format_str = $this->formatPrintByVersion($format_arr,$usePrinter);
            $this->orderPrint->newPrint($usePrinter, $format_str);
        }
    } 

    /*
     *  一菜一单   printType 设置为1，打印机打印全部归属的菜品设置为0
     */
    public function foodshop_menu_new($order_id, $menus, $status, $printType = 1, $is_back = 0, $staff_name='', $old_status = 0)
    {
        $order = D('Foodshop_order')->get_order_detail(array('order_id' => $order_id), 3);
        
        if (empty($order)) return false;

        $now_store = D('Merchant_store_foodshop')->where(array('store_id' => $order['store_id']))->find();
        
        $prints = $this->getPrints($order['store_id']);

        // 排除主打印机设置不打印后厨的打印机
        foreach ($prints as $key=>$l) {
            if ($l['is_main']&&$l['is_print_menu']==0) {
                unset($prints[$key]);
                continue;
            }
        }
        if (empty($prints)) return false;

        $list = $order['info'];
        if ($is_back == 1) {
            $list = $menus;
        }
        $goods_ids = array();
        $newList = array();
        foreach ($list as $row) {
            $goods_ids[] = $row['id'];
            $goods_gids[] = $row['goods_id'];
            $newList[$row['id']] = $row;
        }
        if ($goods_ids) {
            // $goods = D('Foodshop_goods')->field(true)->where(array('goods_id' => array('in', $goods_gids)))->select();
            $goods_list = D('Foodshop_goods')->get_limit_list_page(array('goods_id' => implode(',', $goods_gids)),0);
            $goods = $goods_list['list'];
            foreach($newList as  &$nlist){
                foreach($goods as $good){
                    if($nlist['goods_id']==$good['goods_id']){
                        // 如果设置分类打印机，使用分类打印机设置20180917
                        $nlist['print_id'] = ($good['is_open_print'] && $good['sort_print_id']) ? $good['sort_print_id'] : $good['print_id'];
                    }
                }
            }
            $printGoodsArr = array();
            foreach ($menus as &$_menus) {
                foreach ($newList as $m) {
                    if ($_menus['goods_id']==$m['goods_id']) {
                        $_menus['print_id'] = explode(',', $m['print_id']);
                    }
                }
                if ($_menus['print_id']) {
                    foreach ($_menus['print_id'] as $key => $value) {
                        if (isset($printGoodsArr[$value])) {
                            $printGoodsArr[$value][] = $_menus;
                        } else {
                            $printGoodsArr[$value] = array($_menus);
                        }
                    }
                }
                
            }
            //分单打印
            foreach ($printGoodsArr as $pintid => $goodsList) {
                $mainPrint = isset($prints[$pintid]) ? $prints[$pintid] : null;
                if (empty($mainPrint)) {
                    continue;
                }
                // 打印方式
                if ($mainPrint['print_way'] == 2) {// 整单打印不打印一菜一单
                    continue;
                }
                
                // if ($status != -1) {
                //     $statusArr = explode(',', $mainPrint['paid']);
                //     if (!in_array($status, $statusArr)) {
                //         continue;
                //     }
                // }

                // 整单
                if ($printType == 0) {
                    // 打印内容头部信息
                    $format_arr = $this->foodshop_header($order,$mainPrint,$staff_name,1,[],$is_back);
                   
                    // $format_str .= chr(10) . str_repeat(' ', $firstSpace) . '品名' . str_repeat(' ', $secondSpace) . '数量';//16
                }

                // 获得数量和价格的最大长度（和下面名字组合判断是否换行）
                $num_price_length = 0;
                foreach ($goodsList as $val) {
                    $num_str = '×'.$val['num'] .'  '.floatval($val['price']);
                    $tmp_length = (strlen($num_str) + mb_strlen($num_str,'UTF8')) / 2;
                    if ($tmp_length>$num_price_length) {
                        $num_price_length = $tmp_length;
                    }
                }

                foreach ($goodsList as $val) {
                    // 一菜一单
                    if ($printType == 1) {
                        // 打印内容头部信息
                        $format_arr = $this->foodshop_header($order,$mainPrint,$staff_name,1,[],$is_back);
                        // $format_str .= chr(10) . str_repeat(' ', $firstSpace) . '品名' . str_repeat(' ', $secondSpace) . '数量';//16
                    }

                    // 打印内容商品信息
                    $returnArr = $this->foodshop_body($val,$mainPrint,$num_price_length,$is_back);
                    $format_arr = array_merge($format_arr,$returnArr);
                    
                    if ($printType == 1) {
                        // 打印内容底部信息
                        $footer_arr = $this->foodshop_footer($order,$mainPrint,$staff_name,1,[]);
                        $format_arr = array_merge($format_arr,$footer_arr);

                        // 根据打印机返回对应数据格式
                        $format_str = $this->formatPrintByVersion($format_arr,$mainPrint);
                        $this->orderPrint->newPrint($mainPrint, $format_str);
                    }
                }
                if ($printType == 0) {
                    // 打印内容底部信息
                    $footer_arr = $this->foodshop_footer($order,$mainPrint,$staff_name,1,[]);
                    $format_arr = array_merge($format_arr,$footer_arr);

                    // 根据打印机返回对应数据格式
                    $format_str = $this->formatPrintByVersion($format_arr,$mainPrint);
                    $this->orderPrint->newPrint($mainPrint, $format_str);
                }
            }

            // 定制餐饮整单打印不打印未勾选打印机的商品
            if (C('config.print_belong_goods')==1) {
                $this->foodshop_order_print_belong_goods($order,$printGoodsArr,$prints, $is_back, $staff_name);
            }else{
                 //打印全部(主打印机)
                foreach ($prints as $mainPrint) {
                    // $statusArr = explode(',', $mainPrint['paid']);
                    // if ($mainPrint['is_main'] == 0 && !in_array(4, $statusArr)) {
                    //     //排除 是非主打印机且没有勾打印总单的打印机
                    //     continue;
                    // }

                    // 打印方式
                    if ($mainPrint['is_main'] == 0 && $mainPrint['print_way'] == 1) {// 副打印机 一菜一单不打印整单
                        continue;
                    }                
                    $print_all_str = $this->foodshop_header($order,$mainPrint,$staff_name,1,[],$is_back);

                    // $print_all_str .= chr(10) . str_repeat(' ', $firstSpace) . '品名' . str_repeat(' ', $secondSpace) . '数量';//16
                    $has_goods = false;
                    foreach ($menus as $l) {
                        if ($now_store['print_belong_goods']==1 && !in_array($mainPrint['pigcms_id'], $l['print_id'])) {
                            continue;
                        }
                        $has_goods = true;

                        // 打印内容商品信息
                        $returnArr = $this->foodshop_body($l,$mainPrint,$num_price_length,$is_back);
                        $print_all_str = array_merge($print_all_str,$returnArr);
                    }
                    if ($print_all_str&&$has_goods) {
                        // 打印内容底部信息
                        $footer_arr = $this->foodshop_footer($order,$mainPrint,$staff_name,1,[]);
                        $print_all_str = array_merge($print_all_str,$footer_arr);

                        // 根据打印机返回对应数据格式
                        $print_all_str = $this->formatPrintByVersion($print_all_str,$mainPrint);
                        $this->orderPrint->newPrint($mainPrint, $print_all_str);
                    }
                }
            }
            
           
        }
    }

    // 定制餐饮整单打印不打印未勾选打印机的商品
    public function foodshop_order_print_belong_goods($order,$printGoodsArr,$prints,$is_back='0', $staff_name = ''){
        //分单打印
        foreach ($printGoodsArr as $pintid => $goodsList) {
            $mainPrint = isset($prints[$pintid]) ? $prints[$pintid] : null;
            if (empty($mainPrint)) {
                continue;
            }

            // 打印方式
            if ($mainPrint['is_main'] == 0 && $mainPrint['print_way'] == 1) {// 副打印机 一菜一单不打印整单
                continue;
            }
            
            // 整单
           
            // 打印内容头部信息
            $format_arr = $this->foodshop_header($order,$mainPrint,$staff_name,1,[],$is_back);
               
            // 获得数量和价格的最大长度（和下面名字组合判断是否换行）
            $num_price_length = 0;
            foreach ($goodsList as $val) {
                $num_str = '×'.$val['num'] .'  '.floatval($val['price']);
                $tmp_length = (strlen($num_str) + mb_strlen($num_str,'UTF8')) / 2;
                if ($tmp_length>$num_price_length) {
                    $num_price_length = $tmp_length;
                }
            }

            foreach ($goodsList as $val) {
                // 打印内容商品信息
                $returnArr = $this->foodshop_body($val,$mainPrint,$num_price_length,$is_back);
                $format_arr = array_merge($format_arr,$returnArr);
            }
            // 打印内容底部信息
            $footer_arr = $this->foodshop_footer($order,$mainPrint,$staff_name,1,[]);
            $format_arr = array_merge($format_arr,$footer_arr);

            // 根据打印机返回对应数据格式
            $format_str = $this->formatPrintByVersion($format_arr,$mainPrint);
            $this->orderPrint->newPrint($mainPrint, $format_str);
        }
        return true;
    }

    // printType 1-一菜一单，0-打印整单
    public function foodshop_order_every_new($order, $prints, $status, $printType = 1, $staff_name = '')
    {
        // 排除主打印机设置不打印后厨的打印机
        foreach ($prints as $key=>$l) {
            if ($l['is_main']&&$l['is_print_menu']==0) {
                unset($prints[$key]);
                continue;
            }
        }   
        if (empty($prints)) return false;

        if ($order['info']) {
            $list = $order['info'];
            $goods_ids = array();
            $newList = array();
            foreach ($list as $row) {
                $goods_ids[] = $row['id'];
                $goods_gids[] = $row['goods_id'];
                $newList[$row['id']] = $row;
            }
            if ($goods_ids) {
                // $goods = D('Foodshop_goods')->field(true)->where(array('goods_id' => array('in', $goods_gids)))->select();
                $goods_list = D('Foodshop_goods')->get_limit_list_page(array('goods_id' => implode(',', $goods_gids)),0);
                $goods = $goods_list['list'];
                foreach($newList as  &$nlist){
                    foreach($goods as $good){
                        if($nlist['goods_id']==$good['goods_id']){
                            // $nlist['print_id'] = $good['print_id'];
                            // 如果设置分类打印机，使用分类打印机设置20180917
                            $nlist['print_id'] = ($good['is_open_print'] && $good['sort_print_id']) ? $good['sort_print_id'] : $good['print_id'];
                        }
                    }
                }

                $printGoodsArr = array();
                foreach ($newList as $m) {
                    $m['print_id'] = explode(',', $m['print_id']);
                    if ($m['print_id']) {
                        foreach ($m['print_id'] as $key => $value) {
                            if (isset($printGoodsArr[$value])) {
                                $printGoodsArr[$value][] = $newList[$m['id']];
                            } else {
                                $printGoodsArr[$value] = array($newList[$m['id']]);
                            }
                        }
                    }

                    // if (isset($printGoodsArr[$m['print_id']])) {
                    //     $printGoodsArr[$m['print_id']][] = $newList[$m['id']];
                    // } else {
                    //     $printGoodsArr[$m['print_id']] = array($newList[$m['id']]);
                    // }
                }

                 foreach ($newList as $m) {
                    if ($_menus['goods_id']==$m['goods_id']) {
                        $_menus['print_id'] = explode(',', $m['print_id']);
                    }
                }

                
                foreach ($printGoodsArr as $pintid => $goodsList) {
                    $mainPrint = isset($prints[$pintid]) ? $prints[$pintid] : null;
                    if (empty($mainPrint)) {
                        continue;
                    }
                    
                    if ($status != -1) {
                        $statusArr = explode(',', $mainPrint['paid']);
                        if (!in_array($status, $statusArr)) {
                            continue;
                        }
                    }
                    
                    $formatData = $this->getTwoData($mainPrint);
                    $width = $formatData['width'];
                    $firstSpace = $formatData['one'];
                    $secondSpace = $formatData['two']+2;
                    
                    if ($printType == 0) {//整单
                        // 打印内容头部信息
                        $format_arr = $this->foodshop_header($order,$mainPrint,$staff_name,1,[],0);
                    }
                    foreach ($goodsList as $l) {
                        if ($printType == 1) {//一菜一单
                            // 打印内容头部信息
                            $format_arr = $this->foodshop_header($order,$mainPrint,$staff_name,1,[],0);
                        }

                        // 打印内容商品信息
                        $returnArr = $this->foodshop_body($val,$mainPrint,$num_price_length,$is_back);
                        $format_arr = array_merge($format_arr,$returnArr);

                        if ($printType == 1) {
                            // 打印内容底部信息
                            $footer_arr = $this->foodshop_footer($order,$mainPrint,$staff_name,1,[]);
                            $format_arr = array_merge($format_arr,$footer_arr);

                            // 根据打印机返回对应数据格式
                            $format_arr = $this->formatPrintByVersion($format_arr,$mainPrint);
                            $this->orderPrint->newPrint($mainPrint, $format_str);
                        }
                    }
                    if ($printType == 0) {
                        // 打印内容底部信息
                        $footer_arr = $this->foodshop_footer($order,$mainPrint,$staff_name,1,[]);
                        $format_arr = array_merge($format_arr,$footer_arr);

                        // 根据打印机返回对应数据格式
                        $format_str = $this->formatPrintByVersion($format_arr,$mainPrint);
                        $this->orderPrint->newPrint($mainPrint, $format_str);
                    }
                }
            }
        }
    } 

    /*
     *  餐饮打印头部数据（菜品以上打印内容）
     *  param order array 订单详情
     *  param print array 打印机
     *  param staff_name string 店员名字
     *  param order_type int 打印类型（非结账单预结单）0-结账单预结单 1-一菜一单 2-后厨补打
     *  param store array 店铺信息
     *  param is_back int 是否退菜
     *  return format_arr array 返回打印数组
     */
    public function foodshop_header($order,$print,$staff_name='',$order_type=0,$store=[],$is_back=0){
        $format_arr = [];
        $_small_arr = array('is_big'=>0,'paper'=>$print['paper']);
        $formatData = $this->getThreeData_new($_small_arr);
        $width_small = $formatData['width'];

        $_middle_arr = array('is_big'=>1,'paper'=>$print['paper']);
        $formatData_middle = $this->getThreeData_new($_middle_arr);
        $width_mid = $formatData_middle['width'];

        $_big_arr = array('is_big'=>2,'paper'=>$print['paper']);
        $formatData_big = $this->getThreeData_new($_big_arr);
        $width_big = $formatData_big['width'];

        if ($mainPrint['is_big']==2) {
            $smallSize = 'big';
            $middleSize = 'big';
            $width = $width_big;
        }else{
            $smallSize = 'small';
            $middleSize = 'middle';
            $width = $width_small;
        }

        if ($order_type==1) {
            // 标题
            if ($is_back == 1) {
                $title = "#".$order['fetch_number']."退菜单";
            }else{
                if ($old_status<2) {
                    $title = "#".$order['fetch_number'].'点菜单';
                }else{
                    $title = "#".$order['fetch_number'].'加菜单';
                }
            }
        }elseif ($order_type==2) {
            $title = "#".$order['fetch_number']."后厨-补打";
        }else{
            // 店铺名
            $format_arr[] = $this->print_format_to_json($store['name'],'big','center');
            
            // 头部标题
            if ($order['status'] <= 2) { // 预结单
                $title = '预结单';
            }else{
                $title ='结账单';
            }
        }
        $content =   '**'. $title.'**';
        $format_arr[] = $this->print_format_to_json($content,'big','center');

        // 分割线
        $format_arr[] = $this->print_format_to_json(str_repeat('*', $width * 2),$smallSize,'center'); 

        $format_arr[] = $this->print_format_to_json('订单编号:' . $order['real_orderid'],$smallSize);
        if ($order_type==0) {
            $format_arr[] = $this->print_format_to_json("流水号：" . $order['fetch_number'],$smallSize);
            $order['name'] && $format_arr[] = $this->print_format_to_json('客户姓名：' . $order['name'],$smallSize);
            $order['phone'] && $format_arr[] = $this->print_format_to_json('客户手机：' . $order['phone'],$smallSize);
        }

        $format_arr[] = $this->print_format_to_json('桌台类型：' . $order['table_type_name'],$middleSize);
        $format_arr[] = $this->print_format_to_json("桌台名称：" . $order['table_name'],$middleSize);

        if ($order_type==0) {
            $format_arr[] = $this->print_format_to_json("订单来源：" . $this->order_from_arr[$order['order_from']],$smallSize);
            $format_arr[] = $this->print_format_to_json("操作员：" . $staff_name,$smallSize);
            floatval($order['book_price']) && $format_arr[] = $this->print_format_to_json('预定金额：' . floatval($order['book_price']),$smallSize);
        }

        if ($order_type==1 || $order_type==2) {
            $format_arr[] = $this->print_format_to_json("打印时间：" . date('Y-m-d H:i'),$smallSize);
        }

        // 分割线
        $format_arr[] = $this->print_format_to_json(str_repeat('*', $width * 2),$smallSize,'center'); 
        return $format_arr;
    }

    /*
     *  餐饮打印商品数据
     *  param goods array 商品
     *  param print array 打印机
     *  param num_price_length float 商品价格与数量最大长度
     *  param is_back int 是否退菜
     *  return format_arr array 返回打印数组
     */
    public function foodshop_body($goods,$print,$num_price_length,$is_back=0){
        $format_arr = [];
        $_big_arr = array('is_big'=>2,'paper'=>$print['paper']);
        $formatData_big = $this->getThreeData_new($_big_arr);
        $width_big = $formatData_big['width'];
        // 退菜
        if ($is_back==1) {
            $goods['name'] = '【退】'. $goods['name'];
        }

        if ($goods['spec']) {
            $goods['name'] .= '[' . trim($goods['spec']) . ']';
        }

        $len = self::dstrlen($goods['name']);
        $num_price_str = '';//数量与价格拼接
        if ($len+$num_price_length+2 > $width_big * 2) { 
            $format_arr[] = $this->print_format_to_json($goods['name'],'big');
            // 拼接数量
            $num_price_str .= str_repeat(' ',($width_big * 2-$num_price_length)).'×'.$goods['num'];
           
        }else{
            $num_price_str .= $goods['name'].'  ';
            $name_length = (strlen($num_price_str) + mb_strlen($num_price_str,'UTF8')) / 2;
            // 拼接数量
            $num_price_str .= str_repeat(' ',($width_big * 2-$num_price_length - $name_length)).'×'.$goods['num'];
        }

        // 数量长度
        $num_length = (strlen($num_price_str) + mb_strlen($num_price_str,'UTF8')) / 2;

        // 价格长度
        $price_length = strlen(getFormatNumber($goods['price']*$goods['num']));

        // 拼接价格，价格居右显示
        $num_price_str .= str_repeat(' ',($width_big * 2 - $price_length - $num_length)).getFormatNumber($goods['price']*$goods['num']);
        $format_arr[] = $this->print_format_to_json($num_price_str,'big');

        if ($goods['note']) {
            $format_arr[] = $this->print_format_to_json('  ['.L_('备注') . '：' . $goods['note'] . ']');
        }
        return $format_arr;
    }

    /*
     *  餐饮打印底部数据（菜品以下打印内容）
     *  param order array 订单详情
     *  param print array 打印机
     *  param staff_name string 店员名字
     *  param num_price_length float 商品价格与数量最大长度
     *  param is_back int 是否退菜
     *  param order_type int 打印类型（非结账单预结单）0-结账单预结单 1-一菜一单 2-后厨补打
     *  param store array 店铺信息
     *  return format_arr array 返回打印数组
     */
    public function foodshop_footer($order,$print,$staff_name='',$order_type=1,$store=[]){
        $format_arr = [];

        $_small_arr = array('is_big'=>0,'paper'=>$print['paper']);
        $formatData = $this->getThreeData_new($_small_arr);
        $width_small = $formatData['width'];

        $_middle_arr = array('is_big'=>1,'paper'=>$print['paper']);
        $formatData_middle = $this->getThreeData_new($_middle_arr);
        $width_mid = $formatData_middle['width'];

        $_big_arr = array('is_big'=>2,'paper'=>$print['paper']);
        $formatData_big = $this->getThreeData_new($_big_arr);
        $width_big = $formatData_big['width'];

        if ($mainPrint['is_big']==2) {
            $smallSize = 'big';
            $middleSize = 'big';
            $width = $width_big;
        }else{
            $smallSize = 'small';
            $middleSize = 'middle';
            $width = $width_small;
        }
      
        if ($order_type) { 
            if (!empty($order['note']) && $order['note'] != '无') {
                // 分割线
                $format_arr[] = $this->print_format_to_json(str_repeat('*', $width * 2),$smallSize,'center');
                $format_arr[] = $this->print_format_to_json('整单留言:' . $order['note'],$middleSize);
                $format_arr[] = $this->print_format_to_json(str_repeat(' ', $width * 2),$smallSize,'center');
            }
        }else{
            // 桌台服务名称
            $table_service_name = $this->get_table_service_alias($order['store_id']);

            // 分割线
            $format_arr[] = $this->print_format_to_json(str_repeat('*', $width * 2),$smallSize,'center');
            
            if ($order['book_time_show'] != '--') {
                $format_arr[] = $this->print_format_to_json('预定时间：' . $order['book_time_show'],$smallSize);
            }
            
            if (isset($order['book_pay_type'])&&$order['book_pay_type']) {
                $format_arr[] = $this->print_format_to_json('预定支付方式：' . $order['book_pay_type'],$smallSize);
            }
            if (isset($order['book_pay_time']) && $order['book_pay_time']) {
                $format_arr[] = $this->print_format_to_json('预定支付时间：' . date('Y-m-d H:i', $order['book_pay_time']),$smallSize);
            }
            
            if (isset($order['pay_type'])&&$order['pay_type']) {
                $format_arr[] = $this->print_format_to_json('买单支付方式：' . $order['pay_type'],$smallSize);
            }
            if (isset($order['pay_time']) && $order['pay_time']) {
                $format_arr[] = $this->print_format_to_json('买单支付时间：' . date('Y-m-d H:i', $order['pay_time']),$smallSize);
            }
            //桌台服务费
            if (isset($order['table_service_payable']) && $order['table_service_payable']>0) {
                $format_arr[] = $this->print_format_to_json('应付'.$table_service_name.':' . $order['table_service_payable'],$smallSize);
            }
            if (isset($order['table_service_payment']) && $order['table_service_payment']>0) {
                $format_arr[] = $this->print_format_to_json('实付'.$table_service_name.':' . $order['table_service_payment'],$smallSize);
            }
            
            if ($order['status'] > 1) {
                if ($total_num) {
                    if ($order['status']<=2) {
                        $format_arr[] = $this->print_format_to_json('共' . $order['total_num'] . '件，应付金额：' . floatval($order['total_price']),$middleSize);
                    }else{
                        $format_arr[] = $this->print_format_to_json('共' . $order['total_num'] . '件，订单总价：' . floatval($order['total_price']),$middleSize);
                    }
                }else{
                    if ($order['status']<=2) {
                        $format_arr[] = $this->print_format_to_json('应付金额：' . floatval($order['total_price']),$middleSize);
                    }else{
                        $format_arr[] = $this->print_format_to_json('订单总价：' . floatval($order['total_price']),$middleSize);
                    } 
                }

                if($order['order_from']==2||$order['from_plat']==2){
                    $store_order = M('Store_order')->where(array('business_type'=>'foodshop','business_id'=>$order['order_id'],'paid'=>1))->find();
                    $store_order['discount_price']>0 && $format_arr[] = $this->print_format_to_json('优惠金额：' . floatval($store_order['discount_price']),$middleSize);
                    $store_order['price']>0 && $format_arr[] = $this->print_format_to_json('实付金额：' . floatval($store_order['price']),$middleSize);
                }else{
                    if($order['table_discount_money']>0 && $order['table_or_vipcard_discount']==0){
                        $order['mer_table_discount']>0 && $format_arr[] = $this->print_format_to_json('桌台折扣：' . floatval($order['mer_table_discount']/10),$middleSize);
                        $order['table_discount_money']>0 && $format_arr[] = $this->print_format_to_json('桌台优惠金额：' . floatval($order['table_discount_money']),$middleSize);
                    }
                    $order['price']>0 && $format_arr[] = $this->print_format_to_json('实付金额：' . floatval($order['price']),$middleSize);
                }
            }
            if (!empty($order['note']) && $order['note'] != '无') {
                // 分割线
                $format_arr[] = $this->print_format_to_json(str_repeat('*', $width * 2),$smallSize,'center');
                $format_arr[] = $this->print_format_to_json('整单留言:' . $order['note'],$middleSize);
            }
            // $format_str .= chr(10) . str_repeat('※', $width);
            $format_arr[] = $this->print_format_to_json(str_repeat('*', $width * 2),$smallSize,'center');
            $format_arr[] = $this->print_format_to_json('店铺电话：' . $store['phone'],$smallSize);
            $format_arr[] = $this->print_format_to_json('店铺地址：' . $store['adress'],$smallSize);
            $format_arr[] = $this->print_format_to_json('打印时间：' . date('Y-m-d H:i:s'),$smallSize);
            $format_arr[] = $this->print_format_to_json('店铺电话：' . $store['phone'],$smallSize);
            $format_arr[] = $this->print_format_to_json('谢谢惠顾，欢迎再次光临！',$smallSize,'center');
        }
        return $format_arr;
    }

    /*
     *  根据打印机型号返回打印数据
     *  param format_arr array 打印数组
     *  param print array 打印机
     *  return format_arr array 返回打印数组
     */
    public function formatPrintByVersion($format_arr,$print){
        if (($print['print_type'] == 6 && $print['version'] >= 1450) || ($print['print_type'] == 2 && $print['version'] >= 50)) {
            // 商米打印机 有线打印机
            $format_str['print_data'] = $format_arr;
            $format_str = json_encode($format_str, JSON_UNESCAPED_UNICODE);
            fdump($format_str,'print1',1);
        }else{ 
            $format_str = $this->print_format_to_str($format_arr);
            fdump($format_str,'print2',1);
        }
        return $format_str;
    }

    /*
        $isPrint 是否不打印后厨菜单
    */
    private function foodshop_order_report($report_info, $staff_name = '')
    {
        
        if (empty($report_info)) return false;
        
        $prints = $this->getPrints($report_info['store_id']);
        if (empty($prints)) return false;
        
        $store = D('Merchant_store')->field(true)->where(array('store_id' => $report_info['store_id']))->find();

        foreach ($prints as $usePrinter) {
            if ($usePrinter['is_print_report'] == 0) {
                continue;
            }
            
            $formatData = $this->getThreeData($usePrinter);
            $width = $formatData['width'];
            $firstSpace = $formatData['one'];
            $secondSpace = $formatData['two'];
            $thirdSpace = $formatData['three'];
            $spaceWidth = $formatData['spaceWidth'];
            
            $format_str = '';           
            $format_str .= chr(10) .str_repeat(' ', $width - 4) . '日结账单';            
            $format_str .= chr(10) . '打印时间:' . date('Y-m-d H:i:s');
            $format_str .= chr(10) . "店铺名称：" . $store['name'];
            $staff_name && $format_str .= chr(10) . "操作员：" . $staff_name;

          
            
            $format_str .= chr(10) . str_repeat('-', $width * 2);
            $format_str .= chr(10) . str_repeat(' ', $firstSpace) . '消费单数' . str_repeat(' ', $secondSpace) . $report_info['num'];
            $format_str .= chr(10) . str_repeat(' ', $firstSpace) . '消费人数' . str_repeat(' ', $secondSpace) . $report_info['people_num'];
            $format_str .= chr(10) . str_repeat(' ', $firstSpace) . '人均消费' . str_repeat(' ', $secondSpace) . $report_info['per_capita_consumption'];
           
            $format_str .= chr(10) . str_repeat(' ', $width * 2);
            $format_str .= chr(10) . str_repeat(' ', $firstSpace) . '支付合计';
            $total_num = 0;
            foreach ($report_info['pay_type_report'] as $val) {
                    $len = self::dstrlen($val['pay_type_name']);
                    $len = $len % ($width * 2);
                    if ($len > ($width * 2 - 8)) {
                        $space = ($width * 2 - 7);
                    } else {
                        $space = ($width * 2 - 7)- $len;
                    }

                    $name_desc = chr(10) . self::cutstr($l['name'], ($width * 2 - 8), $width * 2) . self::format($l['num'], $space);

                    fdump($val['pay_type_name'],'print',true);
                    fdump($len,'print',true);
                    if ($len > 12) {
                        $name_pre = mb_substr($val['pay_type_name'], 0, 6);
                        $name_end = mb_substr($val['pay_type_name'], 6);

                        $name_desc = chr(10) . str_repeat(' ', $firstSpace) . $name_pre.self::format( $val['money']  . '(' . $val['num'] . '次)',$space);
                        $format_str .= chr(10) . str_repeat(' ', $width * 2);
                        $name_desc .=  chr(10) . str_repeat(' ', $firstSpace) . mb_substr($val['pay_type_name'], 5);
                        fdump($name_pre,'print',true);
                    } else {
                        $name_desc = chr(10) . str_repeat(' ', $firstSpace) . $val['pay_type_name'] . self::format( $val['money']  . '(' . $val['num'] . '次)',$space);
                        // $name_desc = chr(10) . str_repeat(' ', $firstSpace) . $val['pay_type_name'] .str_repeat(' ', $secondSpace+$space) . $val['money']  . '(' . $val['num'] . '次)';
                    }

                    
                //     $name_desc = chr(10) . self::cutstr($val['name'], ($width * 2 - 8), $width * 2) . self::format($l['num'], $space);

                // $name_desc = chr(10) . str_repeat(' ', $firstSpace) . $val['pay_type_name'] .str_repeat(' ', $secondSpace) . $val['money']  . '(' . $val['num'] . '次)';
                if ($usePrinter['is_big'] == 1) {
                    $format_str .= '<FH2><FW2>' . $name_desc . '</FW2></FH2>';
                } elseif ($usePrinter['is_big'] == 2) {
                    $format_str .= '<FB>' . $name_desc . '</FB>';
                }else{
                    $format_str .= '<FH><FW>' . $name_desc . '</FH></FW>';
                }
            }
            $format_str .= chr(10) . str_repeat('-', $width * 2);
           
            $format_str .= chr(10) . '店铺电话：' . $store['phone'];
            $format_str .= chr(10) . '店铺地址：' . $store['adress'];
            fdump($format_str,'print',true);
            $this->orderPrint->newPrint($usePrinter, $format_str);
        }
    }
    
    public function meal_order_every($order, $prints)
    {
        if ($order['info']) {
            $list = unserialize($order['info']);
            $meal_ids = array();
            $newList = array();
            foreach ($list as $row) {
                if (! in_array($row['id'], $meal_ids)) {
                    $meal_ids[] = $row['id'];
                }
                $newList[$row['id']] = $row;
            }
            if ($meal_ids) {
                $meals = D('Meal')->field(true)->where(array('meal_id' => array('in', $meal_ids)))->select();
                $mid_pid = array();
                foreach ($meals as $m) {
                    $mid_pid[$m['meal_id']] = $m['print_id'];
                }
                
                $printGoodsArr = array();
                foreach ($meals as $m) {
                    if (isset($printGoodsArr[$m['print_id']])) {
                        $printGoodsArr[$m['print_id']][] = $newList[$m['meal_id']];
                    } else {
                        $printGoodsArr[$m['print_id']] = array($newList[$m['meal_id']]);
                    }
                }
                
                foreach ($printGoodsArr as $pintid => $goodsList) {
                    $mainPrint = isset($prints[$pintid]) ? $prints[$pintid] : null;
                    if (empty($mainPrint)) {
                        continue;
                    }
                    $format_str = "订单号：" . $order['order_id'];
                    $table_name = '';
                    if ($order['meal_type'] == 1) {
                        $meal_type = '外送';
                    } elseif ($order['meal_type'] == 0) {
                        $meal_type = '预定';
                        if ($table = D('Merchant_store_table')->field(true)->where(array('pigcms_id' => $order['tableid']))->find()) {
                            $table_name = $table['name'];
                        }
                    } elseif ($order['meal_type'] == 2) {
                        $meal_type = '现场pad点餐';
                        if ($table = D('Merchant_store_table')->field(true)->where(array('pigcms_id' => $order['tableid']))->find()) {
                            $table_name = $table['name'];
                        }
                    } elseif ($order['meal_type'] == 3) {
                        $meal_type = '在线购买';
                        if ($table = D('Merchant_store_table')->field(true)->where(array('pigcms_id' => $order['tableid']))->find()) {
                            $table_name = $table['name'];
                        }
                    }
                    $format_str .= chr(10) . "订单类型：" . $meal_type;
                    $table_name && $format_str .= chr(10) . "就餐桌位：" . $table_name;
                    $format_str .= chr(10) . str_repeat('*', $width * 2);
                    
                    foreach ($goodsList as $l) {
                        $format_str .= chr(10) . $l['name'] . ": ".C('config.Currency_symbol') . $l['price'] . " * " . $l['num'] . "({$l['unit']})";
                        $l['omark'] && $format_str .= chr(10) . "菜品备注: " . $l['omark'];
                    }
                    
                    $this->orderPrint->newPrint($mainPrint, $format_str);
                }
            }
        }
    }

    // 餐饮打印模式 哈克定制堂扫
    /*
        *$isPrint 1结账单0重打小票
    */
    private function foodshop_order_print($order_id, $status = 0, $isPrint = 0, $staff_name = '')
    {
        // 商家语言
        $order = D('Foodshop_order')->get_order_detail(array('order_id' => $order_id),2,array(),true);
        if (C('config.open_multilingual')) {
            $merchant_lang = M('Merchant')->where(['mer_id' => $order['mer_id']])->getField('merchant_default_lang');
            if($merchant_lang != C('config.system_lang')){
                C('config.tmp_system_lang',$merchant_lang);
            }
        }
        if (empty($order)) return false;
        
        $prints = $this->getPrints($order['store_id']);
        if (empty($prints)) return false;
        
        $store = D('Merchant_store')->field(true)->where(array('store_id' => $order['store_id']))->find();        
        foreach ($prints as $usePrinter) {
            if ($usePrinter['is_main'] == 0) {
                continue;
            }
            if ($status != -1) {
                // $statusArr = explode(',', $usePrinter['paid']);
                // if (!in_array($status, $statusArr)) {
                //     continue;
                // }
            }
            $formatData = $this->getThreeData_new($usePrinter);
            $width = $formatData['width'];
            $firstSpace = $formatData['one'];
            $secondSpace = $formatData['two'];
            $thirdSpace = $formatData['three'];
            $spaceWidth = $formatData['spaceWidth'];

            $_middle_arr = array('is_big'=>1,'paper'=>$usePrinter['paper']);
            $formatData_middle = $this->getThreeData_new($_middle_arr);
            $width_mid = $formatData_middle['width'];
            $firstSpace_mid = $formatData_middle['one'];
            $secondSpace_mid = $formatData_middle['two'];
            $thirdSpace_mid = $formatData_middle['three'];
            $spaceWidth_mid = $formatData_middle['spaceWidth'];

            $_big_arr = array('is_big'=>2,'paper'=>$usePrinter['paper']);
            $formatData_big = $this->getThreeData_new($_big_arr);
            $width_big = $formatData_big['width'];
            $firstSpace_big = $formatData_big['one'];
            $secondSpace_big = $formatData_big['two'];
            $thirdSpace_big = $formatData_big['three'];
            $spaceWidth_big = $formatData_big['spaceWidth'];

            
            $format_arr = array();

            // 头部
            $content =   '**'. (C('config.site_print_name') ? C('config.site_print_name') : C('config.site_name')).'**';
            $format_arr[] = $this->print_format_to_json($content,'big','center'); 
            $format_arr[] = $this->print_format_to_json(str_repeat('*', $width * 2),'small','center'); 

            // 重打小票
            if ($isPrint == 0 ) {
                if (C('config.is_hark_system')) {
                    $content = L_('重打小票，请勿重复出餐。Hark不负责重复出餐带来的损失');
                }else{
                    $content = L_('重打小票，请勿重复出餐。平台不负责重复出餐带来的损失');
                }
                $format_arr[] = $this->print_format_to_json($content,'middle','left');
            }else{
                $format_arr[] = $this->print_format_to_json(L_('请确认客人桌台再出餐，本单已完成支付'),'middle','left');
            }
            $format_arr[] = $this->print_format_to_json(str_repeat('*', $width * 2),'small','center'); 

            // 订单编号
            $format_arr[] = $this->print_format_to_json(L_('订单编号') . '：' . $order['real_orderid']);
            // 客户姓名
            $order['name'] &&  $format_arr[] = $this->print_format_to_json(L_('客户姓名') . '：' . $order['name']);
            // 客户手机
            $order['phone'] && $format_arr[] = $this->print_format_to_json(L_('客户手机') . '：' . $order['phone']);

            // 桌台类型
           $format_arr[] = $this->print_format_to_json(L_('桌台类型') . '：' . $order['table_type_name'],'middle','left');
            
            // 桌台名称
            $format_arr[] = $this->print_format_to_json(L_("桌台名称") . '：' . $order['table_name'],'middle','left');
            // 订单来源：
            $format_arr[] = $this->print_format_to_json(L_("订单来源") . '：' . $this->order_from_arr[$order['order_from']]);
            // 订操作员：来源：
            $format_arr[] = $this->print_format_to_json(L_("操作员") . '：' . $staff_name);

            // 客户留言
            if ($order['note'] !=  L_('无')) {      
                $format_arr[] = $this->print_format_to_json(str_repeat('*', $width * 2),'small','center');       
                $format_arr[] = $this->print_format_to_json(L_('客户留言') . '：' . $order['note'],'middle');
            }

            /********************* 菜品信息结束 start******************/
            $format_arr[] = $this->print_format_to_json(str_repeat('*', $width * 2),'small','center');  
            $total_num = 0;

            // 组合附属商品
            $order['info'] = D('Shop_goods')->combination_data($order['info'], 'spec');

            // 获得数量和价格的最大长度（和下面名字组合判断是否换行）
            $num_price_length = 0;
            foreach ($order['info'] as $val) {
                $num_str = 'x'.$val['num'] .'  '.floatval($val['price']);
                $tmp_length = (strlen($num_str) + mb_strlen($num_str,'UTF8')) / 2;
                if ($tmp_length>$num_price_length) {
                    $num_price_length = $tmp_length;
                }
            }

            foreach ($order['info'] as $val) {
                $total_num += $val['num'];
                if ($val['spec']) {
                    $val['name'] .= '[' . trim($val['spec']) . ']';
                }

                $len = self::dstrlen($val['name']);
                $num_price_str = '';//数量与价格拼接
                if ($len+$num_price_length+2 > $width_big * 2) { 
                    $format_arr[] = $this->print_format_to_json($val['name'],'big');
                    // 拼接数量
                    $num_price_str .= str_repeat(' ',($width_big * 2-$num_price_length)).'x'.$val['num'];
                   
                }else{
                    $num_price_str .= $val['name'].'  ';
                    $name_length = (strlen($num_price_str) + mb_strlen($num_price_str,'UTF8')) / 2;
                    // 拼接数量
                    $num_price_str .= str_repeat(' ',($width_big * 2-$num_price_length - $name_length)).'x'.$val['num'];
                }

                // 数量长度
                $num_length = (strlen($num_price_str) + mb_strlen($num_price_str,'UTF8')) / 2;

                // 价格长度
                $price_length = strlen(getFormatNumber($val['price']*$val['num']));

                // 拼接价格，价格居右显示
                $num_price_str .= str_repeat(' ',($width_big * 2 - $price_length - $num_length)).getFormatNumber($val['price']*$val['num']);
                $format_arr[] = $this->print_format_to_json($num_price_str,'big');

                if ($val['note']) {
                    $format_arr[] = $this->print_format_to_json('  ['.L_('备注') . '：' . $val['note'] . ']');
                }
            }
            /********************* 菜品信息结束 end******************/


            /********************* 订单金额及优惠信息 start******************/
            $format_arr[] = $this->print_format_to_json(str_repeat('*', $width * 2),'small','center');
            $format_arr[] = $this->print_format_to_json(L_('总计份数') . '：' . $order['goods_num'],'middle');
            // 优惠前金额，菜品总价
            $format_arr[] = $this->print_format_to_json(L_('小计') . '：' . C('config.Currency_symbol') . $order['total_price']);
            // 税率
            $format_arr[] = $this->print_format_to_json(L_('GST') . '：' . C('config.Currency_symbol') . $order['tax_rate_price']);
            // 满减优惠
            if ($order['discount_detail']) {
                $new_discount_list = D('Foodshop_goods_new')->discount_formart($order['discount_detail']);
                foreach ($new_discount_list as  $_discount) {
                    $format_arr[] = $this->print_format_to_json($_discount['value'].'：-' . C('config.Currency_symbol') . $_discount['minus']);
                }
            }

            // 商家优惠券
            if ($order['card_id']) { 
                $merchant_coupon = D('Card_new_coupon')->get_coupon_info($order['card_id']);
                $merchant_coupon = D('Foodshop_goods_new')->formart_coupon(array($merchant_coupon));
                $merchant_coupon = $merchant_coupon[0];
                $format_arr[] = $this->print_format_to_json($merchant_coupon['coupon_des'].'：-' . C('config.Currency_symbol') . $order['card_price']);
                $order['price'] -= $order['card_price'];
            }
           
            //平台优惠券
            if ($order['coupon_id']) { 
                $system_coupon = D('System_coupon')->get_coupon_info($order['coupon_id']);
                $system_coupon = D('Foodshop_goods_new')->formart_coupon(array($system_coupon));
                $system_coupon = $system_coupon[0];
                $format_arr[] = $this->print_format_to_json($system_coupon['coupon_des'].'：-' . C('config.Currency_symbol') . $order['coupon_price']);
                $order['price'] -= $order['coupon_price'];
            }
            $order['price'] = max(0,$order['price']);

            // 总计 实付金额
            //支付手续费
            $payment_money_order_db = M("payment_money_order");
            $payment_money_order = $payment_money_order_db->where(array("order_id"=>$order['order_id']))->find();
            $order['service_charge'] = '';
            if($payment_money_order['paid_money']>0 && $payment_money_order['service_charge'] != '0.00'){
                $order['service_charge'] = $payment_money_order['service_charge'];
                $order['price'] += $order['service_charge'];
            }
            $format_arr[] = $this->print_format_to_json(L_('总计') . '：' . C('config.Currency_symbol') . getFormatNumber($order['price']+ $order['tax_rate_price']));
            /********************* 订单金额及优惠信息 end******************/
            
            // 底部
            $format_arr[] = $this->print_format_to_json(str_repeat('*', $width * 2),'small','center');            
            $format_arr[] = $this->print_format_to_json(L_('打印时间') . '：' . date('Y-m-d H:i:s'));
            $format_arr[] = $this->print_format_to_json(L_('打印时订单状态') . '：' . $order['show_operate_status']);
            
            if (isset($order['pay_type'])&&$order['pay_type']) {
                $format_arr[] = $this->print_format_to_json(L_('买单支付方式') . '：' . $order['pay_type']);
            }

            if (isset($order['pay_time']) && $order['pay_time']) {
                $format_arr[] = $this->print_format_to_json(L_('买单支付时间') . '：' . date('Y-m-d H:i', $order['pay_time']));
            }
            if (($usePrinter['print_type'] == 6 && $usePrinter['version'] >= 1450) || ($usePrinter['print_type'] == 2 && $usePrinter['version'] >= 50)) {
                // 商米打印机 有线打印机
                $format_str = array();
                $format_str['print_data'] = $format_arr;
                $format_str = json_encode($format_str, JSON_UNESCAPED_UNICODE);
                // fdump(json_encode($format_arr),'print',1);
            }else{ 
                $format_str = $this->print_format_to_str($format_arr);
            }     
            C('config.tmp_system_lang',null);
            $this->orderPrint->newPrint($usePrinter, $format_str, 1);
        }
    }

    /*
     * 格式化打印内容，转化成array格式
     * content  打印内容
     * size     字体大小 small middle big
     * type     text文本 image图片
     * bold     是否加粗 true false
     * align    显示位置 center left right
    */
    private function print_format_to_json($content, $size = 'small', $align = 'left', $bold = false, $type='text'){
        $return = array(
                    "size" => $size,
                    "bold" => $bold,
                    "align" => $align,
                    "type" => $type,
                    "text" => '',
                    "image" =>'',
                );
        if ($type=='text') {
           $return["text"] = $content;
        }else{
           $return["image"] = $content;
        }
        return $return;
    }

    /*
     * 格式化打印内容，array转化成str格式
     * content  打印内容
     * size     字体大小 small middle big
     * type     text文本 image图片
     * bold     是否加粗 true false
     * align    显示位置 center left right
    */
    private function print_format_to_str($content,$print = []){
        // 维文处理
        header("Content-type: text/html; charset=utf-8");
        import('@.ORG.UyghurCharUtils');
        $utils = new UyghurCharUtils();

        if (!$content) {
            return '';
        }
        $temp_str = '';
        foreach ($content as $key => $value) {
            if ($print['language'] == 'Uyghur') {
                // 维文打印
                $value['text'] = $utils->Basic2RExtend($value['text']);//基本区 转换 扩展区
            }

            if ($value['type']=='text') {
                if ($value['align']=='center') {
                    // 文本居中
                    $value['text'] = "<center>".$value['text']."</center>";
                }elseif($value['align']=='right'){
                    // 文本居右
                    $value['text'] = "<right>".$value['text']."</right>";
                }
                switch ($value['size']) {
                    case 'middle': 
                        $value['text'] = "<FH><FW>".$value['text']."</FW></FH>";
                        break;
                    case 'big':
                        $value['text'] = "<FH2><FW2>".$value['text']."</FW2></FH2>";
                        break;
                }
                if ($value['bold']) {
                    // 字体加粗
                    $value['text'] = "<FB>".$value['text']."</FB>";
                }
                
                $temp_str .="\n" . $value['text'];
                // $temp_str .= chr(10) . $value['text'];                
            } else if ($value['content']) {
                $temp_str .= "\n" . $value['content'];
            }
        }
        return $temp_str;
    }
    
    /**
     * 一菜一单【快店和餐饮】   主打印机打印，采用一条数据中间空行来分单
     */
    private function shop_order_only_one($order, $mainPrint)
    {
        $formatData = $this->getTwoData($mainPrint);
        $width = $formatData['width'];
        $firstSpace = $formatData['one'];
        $secondSpace = $formatData['two'];
        $format_str = '';
        foreach ($order['info'] as $val) {
            $format_str .= chr(10) . '订单编号:' . $order['real_orderid'];
            $format_str .= chr(10) . '下单时间：' . date('Y-m-d H:i', $order['create_time']);
            if ($order['pay_time']) {
                $format_str .= chr(10) . '支付时间：' . date('Y-m-d H:i', $order['pay_time']);
            }
            if ($order['expect_use_time']) {
                $format_str .= chr(10) . '送达时间：' . date('Y-m-d H:i', $order['expect_use_time']);
            }
            
            $format_str .= chr(10) . str_repeat('*', $width * 2);
            if ($val['packname']) {
                $format_str .= chr(10) . $val['packname'];
            }
            $format_str .= chr(10) . str_repeat(' ', $firstSpace) . '品名' . str_repeat(' ', $secondSpace) . '数量';//16
            if ($val['number']) {
                $len = self::dstrlen($val['number']);
                $len = $len % ($width * 2);
                if ($len > ($width * 2 - 8)) {
                    $space = ($width * 2 - 7);
                } else {
                    $space = ($width * 2 - 7) - $len;
                }
                $format_str .= chr(10) . self::cutstr($val['number'], ($width * 2 - 8), $width * 2) . self::format($val['num'], $space);
            } else {
                $val['spec'] && $val['name'] .= '(' . $val['spec'] . ')';
                $len = self::dstrlen($val['name']);
                $len = $len % ($width * 2);
                if ($len > ($width * 2 - 8)) {
                    $space = ($width * 2 - 7);
                } else {
                    $space = ($width * 2 - 7) - $len;
                }
                
                $format_str .= chr(10) . self::cutstr($val['name'], ($width * 2 - 8), $width * 2) . self::format($val['num'], $space);
            }
            $format_str .= chr(10) . str_repeat('*', $width * 2);
            
            
            if ($order['desc']) {
                $format_str .= chr(10) . '客户留言:' . $order['desc'];
            }
            $format_str .= chr(10) . chr(10) . chr(10) . chr(10);
        }
        return $this->orderPrint->newPrint($mainPrint, $format_str);
    }
    
    /**
     * 一菜一单【快店和餐饮】   主打印机打印，采用一条数据中间空行来分单
     */
    private function foodshop_order_only_one($order, $mainPrint)
    {
        $formatData = $this->getTwoData($mainPrint);
        $width = $formatData['width'];
        $firstSpace = $formatData['one'];
        $secondSpace = $formatData['two'];
        
        $format_str = '';
        foreach ($order['info'] as $val) {
            $format_str .= chr(10) . '订单编号:' . $order['real_orderid'];
            $format_str .= chr(10) . '桌台编号：' . $order['table_name'];
            if ($order['book_time_show']) {
                $format_str .= chr(10) . '预定时间：' . $order['book_time_show'];
            }
            $format_str .= chr(10) . str_repeat('*', $width * 2);
            $format_str .= chr(10) . str_repeat(' ', $firstSpace) . '品名' . str_repeat(' ', $secondSpace) . '数量';
            $val['spec'] && $val['name'] .= '(' . $val['spec'] . ')';
            if ($val['note']) {
                $val['name'] .= '(备注：' . $val['note'] . ')';
            }
            $len = self::dstrlen($val['name']);
            $len = $len % ($width * 2);
            if ($len > ($width * 2 - 8)) {
                $space = ($width * 2 - 7);
            } else {
                $space = ($width * 2 - 7) - $len;
            }
            
            $format_str .= chr(10) . self::cutstr($val['name'], ($width * 2 - 8), $width * 2) . self::format($val['num'], $space);
            
            $format_str .= chr(10) . str_repeat('*', $width * 2);
            if ($order['note']) {
                $format_str .= chr(10) . '客户留言:' . $order['note'];
            }
            $format_str .= chr(10) . chr(10) . chr(10) . chr(10);
        }
        return $this->orderPrint->newPrint($mainPrint, $format_str);
    }
    
    private function getThreeData($usePrinter)
    {
        if ($usePrinter['is_big'] == 0 && $usePrinter['paper'] == 0) {
            $width = 16;
            $firstSpace = 4;
            $secondSpace = 9;
            $thirdSpace = 6;
            $spaceWidth = 16;//数量前的所占的字符数
        } elseif ($usePrinter['is_big'] == 1 && $usePrinter['paper'] == 0) {
            $width = 12;
            $firstSpace = 3;
            $secondSpace = 4;
            $thirdSpace = 3;
            $spaceWidth = 10;
        } elseif ($usePrinter['is_big'] == 2 && $usePrinter['paper'] == 0) {
            $width = 8;
            $firstSpace = 1;
            $secondSpace = 2;
            $thirdSpace = 1;
            $spaceWidth = 8;
        } elseif ($usePrinter['is_big'] == 0 && $usePrinter['paper'] == 1) {
            $width = 24;
            $firstSpace = 7;
            $secondSpace = 14;
            $thirdSpace = 12;
            $spaceWidth = 24;
        } elseif ($usePrinter['is_big'] == 1 && $usePrinter['paper'] == 1) {
            $width = 18;
            $firstSpace = 4;
            $secondSpace = 11;
            $thirdSpace = 8;
            $spaceWidth = 18;
        } elseif ($usePrinter['is_big'] == 2 && $usePrinter['paper'] == 1) {
            $width = 12;
            $firstSpace = 3;
            $secondSpace = 4;
            $thirdSpace = 3;
            $spaceWidth = 10;
        }
        return array('width' => $width, 'one' => $firstSpace, 'two' => $secondSpace, 'three' => $thirdSpace, 'spaceWidth' => $spaceWidth);
    }

    private function getThreeData_new($usePrinter)
    {
        if ($usePrinter['is_big'] == 0 && $usePrinter['paper'] == 0) {
            $width = 16;
            $firstSpace = 4;
            $secondSpace = 5;
            $thirdSpace = 4;
            $spaceWidth = 14;//数量前的所占的字符数
        } elseif ($usePrinter['is_big'] == 1 && $usePrinter['paper'] == 0) {
            $width = 12;
            $firstSpace = 3;
            $secondSpace = 4;
            $thirdSpace = 3;
            $spaceWidth = 10;
        } elseif ($usePrinter['is_big'] == 2 && $usePrinter['paper'] == 0) {
            $width = 8;
            $firstSpace = 1;
            $secondSpace = 1;
            $thirdSpace = 1;
            $spaceWidth = 4;
        } elseif ($usePrinter['is_big'] == 0 && $usePrinter['paper'] == 1) {
            $width = 24;
            $firstSpace = 7;
            $secondSpace = 14;
            $thirdSpace = 12;
            $spaceWidth = 22;
        } elseif ($usePrinter['is_big'] == 1 && $usePrinter['paper'] == 1) {
            $width = 18;
            $firstSpace = 4;
            $secondSpace = 11;
            $thirdSpace = 8;
            $spaceWidth = 18;
        } elseif ($usePrinter['is_big'] == 2 && $usePrinter['paper'] == 1) {
            $width = 12;
            $firstSpace = 3;
            $secondSpace = 4;
            $thirdSpace = 3;
            $spaceWidth = 10;
        }
        return array('width' => $width, 'one' => $firstSpace, 'two' => $secondSpace, 'three' => $thirdSpace, 'spaceWidth' => $spaceWidth);
    }
    
    private function getTwoData($mainPrint)
    {
        if ($mainPrint['is_big'] == 0 && $mainPrint['paper'] == 0) {
            $width = 16;
            $firstSpace = 4;
            $secondSpace = 16;
        } elseif ($mainPrint['is_big'] == 1 && $mainPrint['paper'] == 0) {
            $width = 12;
            $firstSpace = 4;
            $secondSpace = 8;
        } elseif ($mainPrint['is_big'] == 2 && $mainPrint['paper'] == 0) {
            $width = 8;
            $firstSpace = 2;
            $secondSpace = 2;
        } elseif ($mainPrint['is_big'] == 0 && $mainPrint['paper'] == 1) {
            $width = 24;
            $firstSpace = 4;
            $secondSpace = 32;
        } elseif ($mainPrint['is_big'] == 1 && $mainPrint['paper'] == 1) {
            $width = 18;
            $firstSpace = 4;
            $secondSpace = 20;
        } elseif ($mainPrint['is_big'] == 2 && $mainPrint['paper'] == 1) {
            $width = 12;
            $firstSpace = 4;
            $secondSpace = 8;
        }
        return array('width' => $width, 'one' => $firstSpace, 'two' => $secondSpace);
    }

    public static function dstrlen($string)
    {
        $n = $tn = $noc = 0;
        while ($n < strlen($string)) {
            $t = ord($string[$n]);
            if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
                $tn = 1;
                $n ++;
                $noc ++;
            } elseif (194 <= $t && $t <= 223) {
                $tn = 2;
                $n += 2;
                $noc += 2;
            } elseif (224 <= $t && $t <= 239) {
                $tn = 3;
                $n += 3;
                $noc += 2;
            } elseif (240 <= $t && $t <= 247) {
                $tn = 4;
                $n += 4;
                $noc += 2;
            } elseif (248 <= $t && $t <= 251) {
                $tn = 5;
                $n += 5;
                $noc += 2;
            } elseif ($t == 252 || $t == 253) {
                $tn = 6;
                $n += 6;
                $noc += 2;
            } else {
                $n ++;
            }
        }
        return $noc;
    }

    public static function cutstr($string, $length = 16, $mod = 32)
    {
        $n = $tn = $noc = 0;
        while ($n < strlen($string)) {
            $t = ord($string[$n]);
            if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
                $tn = 1;
                $n ++;
                $noc ++;
            } elseif (194 <= $t && $t <= 223) {
                $tn = 2;
                $n += 2;
                $noc += 2;
            } elseif (224 <= $t && $t <= 239) {
                $tn = 3;
                $n += 3;
                $noc += 2;
            } elseif (240 <= $t && $t <= 247) {
                $tn = 4;
                $n += 4;
                $noc += 2;
            } elseif (248 <= $t && $t <= 251) {
                $tn = 5;
                $n += 5;
                $noc += 2;
            } elseif ($t == 252 || $t == 253) {
                $tn = 6;
                $n += 6;
                $noc += 2;
            } else {
                $n ++;
            }
        }
        $noc = $noc % $mod;
        if ($noc > $length) {
            return $string . chr(10);
        } else {
            return $string;
        }
    }

    public static function format($string, $space)
    {
        $n = $tn = $noc = 0;
        while ($n < strlen($string)) {
            $t = ord($string[$n]);
            if ($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
                $tn = 1;
                $n ++;
                $noc ++;
            } elseif (194 <= $t && $t <= 223) {
                $tn = 2;
                $n += 2;
                $noc += 2;
            } elseif (224 <= $t && $t <= 239) {
                $tn = 3;
                $n += 3;
                $noc += 2;
            } elseif (240 <= $t && $t <= 247) {
                $tn = 4;
                $n += 4;
                $noc += 2;
            } elseif (248 <= $t && $t <= 251) {
                $tn = 5;
                $n += 5;
                $noc += 2;
            } elseif ($t == 252 || $t == 253) {
                $tn = 6;
                $n += 6;
                $noc += 2;
            } else {
                $n ++;
            }
        }
        if ($noc < 4 && $space == 0) {
            $space += 1;
        } elseif ($noc > 4) {
            $space = $space - $noc + 4;
        }
        for ($i = 0; $i < $space; $i ++) {
            $string = chr(32) . $string;
        }
        if ($noc < 4) {
            for ($k = 0; $k < (3 - $noc); $k ++) {
                $string = $string . chr(32);
            }
        }
        return $string;
    }
    
    /**
     * 获取店铺的打印机列表 (如果店铺没有主打印机的话，将最先添加的打印机当做主打印机)
     * @param int $store_id
     * @return number|unknown[]
     */
    private function getPrints($store_id)
    {
        $prints = D('Orderprinter')->where(array('store_id' => $store_id))->order('is_main DESC, pigcms_id ASC')->select();
        $result = array();
        $isMain = 0;
        $firstId = 0;
        foreach ($prints as $print) {
            if ($print['is_main']) {
                $isMain = $print['pigcms_id'];
            } elseif (empty($firstId)) {
                $firstId = $print['pigcms_id'];
            }
            $result[$print['pigcms_id']] = $print;
        }
        if ($isMain == 0 && $firstId != 0) {
            $result[$firstId]['is_main'] = 1;
        }
        return $result;
    }

    //大趋云打印机外卖打印
    private function trenditiotPrintForWaimai($order_id, $status = 0, $re, $isBookOrder = false, $usePrinter = [])
    {
        $order = D('Shop_order')->get_order_detail(array('order_id' => $order_id), true);
        if (empty($order)) {
            return false;
        }

        $store = M(_view('Merchant_store'))->field(true)->where(array('store_id' => $order['store_id']))->find();
        $store_shop = M(_view('Merchant_store_shop'))->field(true)->where(array('store_id' => $order['store_id']))->find();
        $freight_alias = $store_shop['freight_alias'] ? $store_shop['freight_alias'] : L_('配送费');
        $pack_alias = $store_shop['pack_alias'] ? $store_shop['pack_alias'] : L_('打包费');

        $open_scan_qrcode_receive = C('config.open_scan_qrcode_receive');
        if ($usePrinter['is_main'] == 0 || $usePrinter['print_type'] != 7) {
            return false;
        }

        if ($status != -1) {
            //预订单接单判断
            if ($isBookOrder) {
                if (($usePrinter['booking_order_print'] & $status) != $status) {
                    return false;
                }
            } else {
                if ($order['is_booking'] && in_array($status, [5])) {
                    $bookStatus = 1;
                    if (($usePrinter['booking_order_print'] & $bookStatus) != $bookStatus) {
                        return false;
                    }
                } else {
                    $statusArr = $usePrinter['paid'] !== '' ? explode(',', $usePrinter['paid']) : [];
                    if (!in_array($status, $statusArr)) {
                        return false;
                    }
                }
            }
        }

        // 商品列表
        $goodList = D('Shop_goods')->combination_data($order['info'], 'spec');

        $content = '';

        if ($re) {
            $content .=  '<C>补打订单：</C><BR>';
        }

        $lineByte = 16 * 2; //正常显示字体一行最多16个中文汉字，58mm打印机一行总占32字节
        if ($order['paid'] == 1 && $order['order_from'] != 6 && $order['is_pick_in_store'] <= 2) {
            $title = '#' . $order['fetch_number'] . C('config.shop_notice_plat_name');
        } else {
            $title = C('config.shop_notice_plat_name');
        }
        $content .= '<C><font# bolder=1 height=2 width=2>' . $title . '</font#></C><BR><BR>';

        $content .= '<C>*' . $store['name'] . '*</C><BR><BR>';

        if ($order['is_booking'] && $order['time_id'] == 0) {
            $content .= '<C><font# bolder=1 height=2 width=2>【预订单】</font#></C><BR>';
        }

        if ($order['desc']) {
            $content .= '<LEFT><font# bolder=0 height=2 width=1>备注: ' . $order['desc'] . '</font#></LEFT><BR>';
            $content .= '<LEFT>' . str_repeat('-', $lineByte) . '</LEFT><BR>';
        }

        $content .= '<LEFT><font# bolder=0 height=2 width=1>期望送达时间: ' . date('Y-m-d H:i', $order['expect_use_time']) . '</font#></LEFT><BR>';

        $content .= '<LEFT>下单时间：' . date('Y-m-d H:i', $order['create_time']) . '</LEFT><BR>';
        $content .= '<LEFT>订单编号：' . $order['real_orderid'] . '</LEFT><BR>';


        if ($store['phone']) {
            $content .= '<LEFT>客服电话：' . $store['phone'] . '</LEFT><BR>';
        }
        if ($order['is_pick_in_store'] == 1) {
            $content .= '<LEFT>' .  L_('配送方式') . '：' . L_('商家配送') . '</LEFT><BR>';
        } else if ($order['is_pick_in_store'] == 2) {
            $content .= '<LEFT>' .  L_('配送方式') . '：' . L_('用户自取') . '</LEFT><BR>';
        } else if ($order['is_pick_in_store'] == 3) {
            $content .= '<LEFT>' .  L_('配送方式') . '：' . L_('快递配送') . '</LEFT><BR>';
        } else {
            $content .= '<LEFT>' .  L_('配送方式') . '：' . L_('平台配送') . '</LEFT><BR>';
        }

        //商品信息
        $tempList = array();
        foreach ($goodList as $val) {
            $index = isset($val['packname']) && $val['packname'] ? $val['packname'] : 0;
            if (isset($tempList[$index])) {
                $tempList[$index]['list'][] = $val;
            } else {
                $tempList[$index] = array('name' => $val['packname'], 'list' => array($val));
            }
        }
        ksort($tempList);

        foreach ($tempList as $list) {
            $packname = $list['name'] ?: L_('1号订购人');
            $dotLen = intval(($lineByte - mb_strwidth($packname)) / 2);
            $content .= '<LEFT>' .  str_repeat('-', $dotLen) . $packname . str_repeat('-', $dotLen) . '</LEFT><BR>';

            $total = 0;
            $totalPrice = 0;
            foreach ($list['list'] as $val) {
                if ($order['order_from'] == 6 && $val['sell_type'] == 2) {
                    $val['num'] = 1;
                    $val['price'] = $val['code_price'] ?: $val['price'];
                }

                //金额按照占用6字节，数量占用3字节，中间再额外加1个空格，一共10个字节
                //商品名称   X1  23.5

                $num = $val['num'];
                $price = getFormatNumber($val['price'] * $val['num']);

                if ($val['spec']) {
                    //有规格，价格和数量和规格显示在一行
                    $content .= '<LEFT>' .  $val['name'] . '</LEFT><BR>';
                    $priceNumStr = 'x' . $num . str_repeat(' ', (10 - mb_strwidth('x' . $num . $price))) . $price;
                    if (mb_strwidth($val['spec']) < ($lineByte - 6 - 3 - 1 - 2)) {
                        $content .= '<LEFT>[' .  $val['spec'] . ']';
                        $content .= str_repeat(' ', $lineByte - mb_strwidth($priceNumStr . $val['spec']) - 2) . $priceNumStr . '</LEFT><BR>';
                    } else {
                        $content .= '<LEFT>[' .  $val['spec'] . ']</LEFT><BR>';
                        $content .= '<LEFT>' .  str_repeat(' ', $lineByte - mb_strwidth($priceNumStr)) . $priceNumStr . '</LEFT><BR>';
                    }
                } else {
                    //没规格
                    $priceNumStr = 'x' . $num . str_repeat(' ', (10 - mb_strwidth('x' . $num . $price))) . $price;
                    if (mb_strwidth($val['name']) < ($lineByte - 6 - 3 - 1 - 2)) {
                        $content .= '<LEFT>' .  $val['name'];
                        $content .= str_repeat(' ', $lineByte - mb_strwidth($priceNumStr . $val['name'])) . $priceNumStr . '</LEFT><BR>';
                    } else {
                        $content .= '<LEFT>' .  $val['name'] . '</LEFT><BR>';
                        $content .= '<LEFT>' .  str_repeat(' ', $lineByte - mb_strwidth($priceNumStr)) . $priceNumStr . '</LEFT><BR>';
                    }
                }

                $total += intval($val['num']);
                $totalPrice = getFormatNumber($totalPrice + $price);
            }

            //合计
            $toalStr = 'x' . $total . str_repeat(' ', (10 - mb_strwidth('x' . $total . $totalPrice))) . $totalPrice;
            $content .= '<LEFT>' .  '合计:' . str_repeat(' ', $lineByte - mb_strwidth($toalStr) - 5) . $toalStr . '</LEFT><BR>';
        }

        $content .= '<LEFT>' .  str_repeat('-', $lineByte) . '</LEFT><BR>';

        $freightStr = C('config.Currency_symbol') . getFormatNumber($order['freight_charge']);
        $content .= '<LEFT>' .  $freight_alias . ':' . str_repeat(' ', $lineByte - mb_strwidth($freightStr . $freight_alias) - 1) . $freightStr . '</LEFT><BR>';

        $packStr = C('config.Currency_symbol') . getFormatNumber($order['packing_charge']);
        $content .= '<LEFT>' .  $pack_alias . ':' . str_repeat(' ', $lineByte - mb_strwidth($packStr . $pack_alias) - 1) . $packStr . '</LEFT><BR>';

        $goodsPrice = C('config.Currency_symbol') . getFormatNumber($order['goods_price']);
        $content .= '<LEFT>' .  '原价:' . str_repeat(' ', $lineByte - mb_strwidth($goodsPrice) - 5) . $goodsPrice . '</LEFT><BR>';

        $content .= '<LEFT>' .  str_repeat('-', $lineByte) . '</LEFT><BR>';
        $payMoney = C('config.Currency_symbol') . getFormatNumber($order['merchant_balance'] + $order['balance_pay'] + $order['payment_money'] + $order['score_deducte'] + $order['coupon_price'] + $order['card_give_money'] + $order['qiye_pay']);

        $content .= '<LEFT><font# bolder=0 height=2 width=1>实付:' . str_repeat(' ', $lineByte  - mb_strwidth($payMoney) - 5) . $payMoney  . '</font#></LEFT><BR>';
        $content .= '<LEFT><font# bolder=0 height=2 width=1>' . str_repeat('-', $lineByte)  . '</font#></LEFT><BR>';
        $content .= '<LEFT><font# bolder=0 height=2 width=1>' . $order['address']  . '</font#></LEFT><BR>';
        $content .= '<LEFT><font# bolder=0 height=2 width=1>' . $order['username']  . '</font#></LEFT><BR>';
        $content .= '<LEFT><font# bolder=0 height=2 width=1>' . $order['userphone']  . '</font#></LEFT><BR>';

        if ($open_scan_qrcode_receive && $order['is_pick_in_store'] == 0 && $order['paid'] == 1) {
            $content .= '<BR>';
            $content .= '<QR>' . $order['real_orderid'] . '</QR>';
        }

        $content .= '<BR>';
        $content .= '<C><QR>'.get_base_url("pages/shop_new/shopDetail/shopDetail?store_id=".$order['store_id']).'</QR></C>';
        $this->orderPrint->newPrint($usePrinter, $content);
    }

    //飞蛾打印机外卖打印
    private function feieyunPrintForWaimai($order_id, $status = 0, $re, $isBookOrder = false, $usePrinter = [])
    {
        $order = D('Shop_order')->get_order_detail(array('order_id' => $order_id), true);
        if (empty($order)) {
            return false;
        }

        $store = M(_view('Merchant_store'))->field(true)->where(array('store_id' => $order['store_id']))->find();
        $store_shop = M(_view('Merchant_store_shop'))->field(true)->where(array('store_id' => $order['store_id']))->find();
        $freight_alias = $store_shop['freight_alias'] ? $store_shop['freight_alias'] : L_('配送费');
        $pack_alias = $store_shop['pack_alias'] ? $store_shop['pack_alias'] : L_('打包费');

        $open_scan_qrcode_receive = C('config.open_scan_qrcode_receive');
        if ($usePrinter['is_main'] == 0) {
            return false;
        }
        if ($usePrinter['print_type'] != 4) {
            return false;
        }

        if ($status != -1) {
            //预订单接单判断
            if ($isBookOrder) {
                if (($usePrinter['booking_order_print'] & $status) != $status) {
                    return false;
                }
            } else {
                if ($order['is_booking'] && in_array($status, [5])) {
                    $bookStatus = 1;
                    if (($usePrinter['booking_order_print'] & $bookStatus) != $bookStatus) {
                        return false;
                    }
                } else {
                    $statusArr = $usePrinter['paid'] !== '' ? explode(',', $usePrinter['paid']) : [];
                    if (!in_array($status, $statusArr)) {
                        return false;
                    }
                }
            }
        }

        // 商品列表
        $goodList = D('Shop_goods')->combination_data($order['info'], 'spec');

        $content = '';

        if ($re) {
            $content .= chr(10) . '补打订单：';
        }

        $lineByte = 16 * 2; //正常显示字体一行最多16个中文汉字，58mm打印机一行总占32字节
        if ($order['paid'] == 1 && $order['order_from'] != 6 && $order['is_pick_in_store'] <= 2) {
            $title = '#' . $order['fetch_number'] . C('config.shop_notice_plat_name');
        } else {
            $title = C('config.shop_notice_plat_name');
        }
        $content .= chr(10) . '<CB>' . $title . '</CB>';

        $content .= chr(10) . '<C>*' . $store['name'] . '*</C>';

        if ($order['is_booking'] && $order['time_id'] == 0) {
            $content .= chr(10) . '<CB>【预订单】</CB>';
        }

        if ($order['desc']) {
            $content .= chr(10) . '<L>备注：' . $order['desc'] . '</L>';
            $content .= chr(10) . str_repeat('-', $lineByte);
        }

        $content .= chr(10) . '<BOLD>期望送达时间：' . date('Y-m-d H:i', $order['expect_use_time']) . '</BOLD>';
        $content .= chr(10) . '下单时间：' . date('Y-m-d H:i', $order['create_time']);
        $content .= chr(10) . '订单编号：' . $order['real_orderid'];

        if ($store['phone']) {
            $content .= chr(10) . '客服电话：' . $store['phone'];
        }
        if ($order['is_pick_in_store'] == 1) {
            $content .= chr(10) . L_('配送方式') . '：' . L_('商家配送');
        } else if ($order['is_pick_in_store'] == 2) {
            $content .= chr(10) . L_('配送方式') . '：' . L_('用户自取');
        } else if ($order['is_pick_in_store'] == 3) {
            $content .= chr(10) . L_('配送方式') . '：' . L_('快递配送');
        } else {
            $content .= chr(10) . L_('配送方式') . '：' . L_('平台配送');
        }

        //商品信息
        $tempList = array();
        foreach ($goodList as $val) {
            $index = isset($val['packname']) && $val['packname'] ? $val['packname'] : 0;
            if (isset($tempList[$index])) {
                $tempList[$index]['list'][] = $val;
            } else {
                $tempList[$index] = array('name' => $val['packname'], 'list' => array($val));
            }
        }
        ksort($tempList);

        foreach ($tempList as $list) {
            $packname = $list['name'] ?: L_('1号订购人');
            $dotLen = intval(($lineByte - mb_strwidth($packname)) / 2);
            $content .= chr(10) . str_repeat('-', $dotLen) . $packname . str_repeat('-', $dotLen);

            $total = 0;
            $totalPrice = 0;
            foreach ($list['list'] as $val) {
                if ($order['order_from'] == 6 && $val['sell_type'] == 2) {
                    $val['num'] = 1;
                    $val['price'] = $val['code_price'] ?: $val['price'];
                }

                //金额按照占用6字节，数量占用3字节，中间再额外加1个空格，一共10个字节
                //商品名称   X1  23.5

                $num = $val['num'];
                $price = getFormatNumber($val['price'] * $val['num']);

                if ($val['spec']) {
                    //有规格，价格和数量和规格显示在一行
                    $content .= chr(10) . '<L>' . $val['name'] . '</L>';
                    $priceNumStr = 'x' . $num . str_repeat(' ', (10 - mb_strwidth('x' . $num . $price))) . $price;
                    if (mb_strwidth($val['spec']) < ($lineByte - 6 - 3 - 1 - 2)) {
                        $content .= chr(10) . '<L>[' . $val['spec'] . ']';
                        $content .= str_repeat(' ', $lineByte - mb_strwidth($priceNumStr . $val['spec']) - 2) . $priceNumStr . '</L>';
                    } else {
                        $content .= chr(10) . '<L>[' . $val['spec'] . ']</L>';
                        $content .= chr(10) . '<L>' . str_repeat(' ', $lineByte - mb_strwidth($priceNumStr)) . $priceNumStr . '</L>';
                    }
                } else {
                    //没规格
                    $priceNumStr = 'x' . $num . str_repeat(' ', (10 - mb_strwidth('x' . $num . $price))) . $price;
                    if (mb_strwidth($val['name']) < ($lineByte - 6 - 3 - 1 - 2)) {
                        $content .= chr(10) . '<L>' . $val['name'];
                        $content .= str_repeat(' ', $lineByte - mb_strwidth($priceNumStr . $val['name'])) . $priceNumStr . '</L>';
                    } else {
                        $content .= chr(10) . '<L>' . $val['name'] . '</L>';
                        $content .= chr(10) . '<L>' . str_repeat(' ', $lineByte - mb_strwidth($priceNumStr)) . $priceNumStr . '</L>';
                    }
                }

                $total += intval($val['num']);
                $totalPrice = getFormatNumber($totalPrice + $price);
            }

            //合计
            $toalStr = 'x' . $total . str_repeat(' ', (10 - mb_strwidth('x' . $total . $totalPrice))) . $totalPrice;
            $content .= chr(10) . '合计:' . str_repeat(' ', $lineByte - mb_strwidth($toalStr) - 5) . $toalStr;
        }

        $content .= chr(10) . str_repeat('-', $lineByte);

        $freightStr = C('config.Currency_symbol') . getFormatNumber($order['freight_charge']);
        $content .= chr(10) . $freight_alias . ':' . str_repeat(' ', $lineByte - mb_strwidth($freightStr . $freight_alias) - 1) . $freightStr;

        $packStr = C('config.Currency_symbol') . getFormatNumber($order['packing_charge']);
        $content .= chr(10) . $pack_alias . ':' . str_repeat(' ', $lineByte - mb_strwidth($packStr . $pack_alias) - 1) . $packStr;

        $goodsPrice = C('config.Currency_symbol') . getFormatNumber($order['goods_price']);
        $content .= chr(10) . '原价:' . str_repeat(' ', $lineByte - mb_strwidth($goodsPrice) - 5) . $goodsPrice;

        $content .= chr(10) . str_repeat('-', $lineByte);
        $payMoney = C('config.Currency_symbol') . getFormatNumber($order['merchant_balance'] + $order['balance_pay'] + $order['payment_money'] + $order['score_deducte'] + $order['coupon_price'] + $order['card_give_money'] + $order['qiye_pay']);
        $content .= chr(10) . '<B>实付:' . str_repeat(' ', $lineByte / 2 - mb_strwidth($payMoney) - 5) . $payMoney . '</B>';
        $content .= chr(10) . str_repeat('-', $lineByte);

        $content .= chr(10) . '<L>' . $order['address'] . '</L>';
        $content .= chr(10) . '<L>' . $order['username'] . '</L>';
        $content .= chr(10) . '<L>' . $order['userphone'] . '</L>';


        if ($open_scan_qrcode_receive && $order['is_pick_in_store'] == 0 && $order['paid'] == 1) {
            $content .= chr(10) . '<L></L>';
            $content .= chr(10) . '<QR>' . $order['real_orderid'] . '</QR>';
        }
        $this->orderPrint->newPrint($usePrinter, $content);
    }
    
    
    
    private function shop_order($order_id, $status = 0,$re, $isBookOrder = false)
    {
        $order = D('Shop_order')->get_order_detail(array('order_id' => $order_id), true);
        if (empty($order)) return false;
        
        $prints = $this->getPrints($order['store_id']);
        if (empty($prints)) return false;
        
        $store = M(_view('Merchant_store'))->field(true)->where(array('store_id' => $order['store_id']))->find();
        $store_shop = M(_view('Merchant_store_shop'))->field(true)->where(array('store_id' => $order['store_id']))->find();
        $freight_alias = $store_shop['freight_alias'] ? $store_shop['freight_alias'] : L_('配送费用');
        $pack_alias = $store_shop['pack_alias'] ? $store_shop['pack_alias'] : L_('打包费用');

        // 外卖打印模式新
        if ($store_shop['print_type']==1) {
            $this->shop_order_new($order_id, $status, $re,$isBookOrder);
            return false;
        }
        fdump_api(['订单小票打印shop_order'=>$order], 'printer/shop_order_'.date('H'),1);
        //调用分单打印的数据处理
        $this->shopOrderMenu($order, $prints, $status, 0, $isBookOrder);

        $open_scan_qrcode_receive = C('config.open_scan_qrcode_receive');
        foreach ($prints as $usePrinter) {
            if ($usePrinter['is_main'] == 0) {
                continue;
            }
            
            //线下零售未开启打印跳过
            if($status == 1 && $order['order_from'] == 6 && $usePrinter['shop_offline_order_print'] != 1){
                continue;
            }
            if($status == 1 && $order['order_from'] == 6 && C('customization.shop_offline_staff_print') == 1) {
                //线下零售未关联当前店员跳过
                $staff_uids_print = $usePrinter['staff_uids'] ? explode(',', $usePrinter['staff_uids']) : [];
                if (!$staff_uids_print || !in_array($order['staff_id'], $staff_uids_print)) {
                    continue;
                }
            }

            if ($usePrinter['print_type'] == 4) {
                return $this->feieyunPrintForWaimai($order_id, $status, $re, $isBookOrder,$usePrinter);
            }
            if ($usePrinter['print_type'] == 7) {
                return $this->trenditiotPrintForWaimai($order_id, $status, $re, $isBookOrder,$usePrinter);
            }

            if ($status != -1) {
                //预订单接单判断
                if ($isBookOrder) {
                    if (($usePrinter['booking_order_print'] & $status) != $status) {
                        continue;
                    }
                } else {
                    if ($order['is_booking'] && in_array($status, [5])) {
                        $bookStatus = 1;
                        if (($usePrinter['booking_order_print'] & $bookStatus) != $bookStatus) {
                            continue;
                        }
                    } else {
                        $statusArr = $usePrinter['paid'] !== '' ? explode(',', $usePrinter['paid']) : [];
                        if (!in_array($status, $statusArr)) {
                            continue;
                        }
                    }
                }
            }

            $formatData = $this->getThreeData($usePrinter);
            $width = $formatData['width'];
            $firstSpace = $formatData['one'];
            $secondSpace = $formatData['two'];
            $thirdSpace = $formatData['three'];
            $spaceWidth= $formatData['spaceWidth'];
            
            // 商品列表
            $goodList = D('Shop_goods')->combination_data($order['info'], 'spec');
            $goodsCount = count($goodList);// 商品总数大于20使用分单打印
            $pageNum = ceil($goodsCount/20);
            
            for($page = 0; $page<$pageNum ; $page++){
                $format_str = '';
                if($pageNum > 1){// 头部页码
                    $pageTitle = ($page+1).'/' .$pageNum;

                    $titleNum = self::dstrlen($pageTitle);
                    if ($usePrinter['is_big'] == 0) {
                        $s = $width - $titleNum;
                        $e = $width * 2 - $titleNum * 2 - $s;
                    } elseif ($usePrinter['is_big'] == 1) {
                        $s = floor(($width * 2 - $titleNum * 1.5) / 2);
                        $e = floor($width * 2 - $titleNum * 1.5 - $s);
                    } else {
                        $s = floor(($width * 2 - $titleNum) / 2);
                        $e = $width * 2 - $titleNum - $s;
                    }
                    $format_str .= ($format_str ? chr(10) : '' ).str_repeat('-', $s) . '<FH2><FW2>' . $pageTitle . '</FW2></FH2>'. str_repeat('-', $e);
                }

                // 本单打印商品列表
                $tempGoodsList = array_slice($goodList, $page*20, 20);

                if ($order['paid'] == 1 && $order['order_from'] != 6 && $order['is_pick_in_store'] <= 2) {
                    $title = '#' . $order['fetch_number']. C('config.shop_notice_plat_name');
                } else {
                    $title = C('config.shop_notice_plat_name');
                }
                $titleNum = self::dstrlen($title);
                if ($usePrinter['is_big'] == 0) {
                    $s = $width - $titleNum;
                    $e = $width * 2 - $titleNum * 2 - $s;
                } elseif ($usePrinter['is_big'] == 1) {
                    $s = floor(($width * 2 - $titleNum * 1.5) / 2);
                    $e = floor($width * 2 - $titleNum * 1.5 - $s);
                } else {
                    $s = floor(($width * 2 - $titleNum) / 2);
                    $e = $width * 2 - $titleNum - $s;
                }
                $format_str .= ($format_str ? chr(10) : '') . str_repeat('-', $s) . '<FH2><FW2>' . $title . '</FW2></FH2>'. str_repeat('-', $e);
                if($re){
                    if (C('config.is_hark_system')) {
                        $re_title = L_('重打小票，请勿重复出餐。HARK平台不负责重复出餐带来的损失。');
                    }else{
                        $re_title = L_('重打小票，请勿重复出餐。平台不负责重复出餐带来的损失。');
                    }
                    $format_str .= chr(10) . chr(10) . $re_title . chr(10);
                }
                //退款成功打印
                if ($status == 3 || ($status == -1 && ($order['is_refund'] == 1 || $order['refund_detail']))) {
                    $format_str .= chr(10) . L_('退款订单');
                    $refundMoney = floatval($order['price']);
                    if ((MODULE_NAME == 'Store' && ACTION_NAME == 'replyRefund') || (MODULE_NAME == 'Storestaff' && ACTION_NAME == 'replyRefund')) {
                        //售后同意退款
                        $refundMoney = M('Shop_order_refund')->where(['order_id' => $order['order_id'], 'status' => 1])->order('id DESC')->getField('price');
                    }

                    $format_str .= chr(10) . L_('退款金额') . '：' . C('config.Currency_symbol') . $refundMoney;
                }

                if (C('config.is_hark_system')) {
                    $area_service_phone = M('Area')->where(['area_id' => $store['city_id']])->getField('service_phone');
                } else {
                    $area_service_phone = $store['phone'];
                }
                if($area_service_phone){
                    $format_str .= chr(10) . L_('客服电话') . '：' . $area_service_phone;
                }
                if ($order['is_booking'] && $order['time_id'] == 0) {
                    $format_str .= chr(10) . chr(10) . L_('请注意！此单为预约单，请勿提前出单') . chr(10);
                }
                
                if($order['time_id'] > 0){
                    $format_str .= chr(10) . L_('预计送达时间') . '：' . date('d M H:i', $order['expect_use_time']);
                        
                    $distance_time = $order['expect_use_time'] - time();
                    $day_time = intval($distance_time/3600/24);
                    $hour_time = intval((($distance_time/86400)-$day_time)*24);
                    $minute_time = intval((((($distance_time/86400)-$day_time)*24) - $hour_time)*60);;
                    
                    $format_str .= chr(10) . L_('距离当前时间') . '：' . L_('X1天X2小时X3分钟', ['X1' => $day_time, 'X2' => $hour_time, 'X3' => $minute_time]);
                    $format_str .= chr(10) . L_('请您注意备货时间！') . chr(10);
                }else{
                    if ($order['is_pick_in_store'] != '1' && $order['expect_pick_time']) {
                        if ($order['expect_use_time']) {
                            $format_str .= chr(10) . L_('预计送达时间') . '：' . date('Y-m-d H:i', $order['expect_use_time']);
                        }
                        $format_str .= chr(10) . L_('取单时间') . '：' . date('Y-m-d H:i', $order['expect_pick_time']);
                    }else if($order['expect_use_time']){
                        $format_str .= chr(10) . L_('预计送达时间') . '：' . date('Y-m-d H:i', $order['expect_use_time']);
                    }
                }
                
                $format_str .= chr(10) . L_('店铺名称') . '：' . $store['name'];
                
                if($order['is_pick_in_store'] == 1){
                    $format_str .= chr(10) . L_('配送方式') . '：' . L_('商家配送');
                }else if($order['is_pick_in_store'] == 2){
                    $format_str .= chr(10) . L_('配送方式') . '：' . L_('用户自取');
                }else if($order['is_pick_in_store'] == 3){
                    $format_str .= chr(10) . L_('配送方式') . '：' . L_('快递配送');
                }else{
                    $format_str .= chr(10) . L_('配送方式') . '：' . L_('平台配送');
                }

                $format_str .= chr(10) . L_('订单编号') . '：' . $order['real_orderid'];
                $format_str .= chr(10) . L_('下单时间') . '：' . date('Y-m-d H:i', $order['create_time']);
                
                if($order['desc'] || $order['cue_field']){
                    $format_str .= chr(10) . str_repeat('-', $width * 2);
                }
                if ($order['desc']) {
                    $format_str .= chr(10) . '<FH2><FW2>' . L_('客户留言') . '：' . $order['desc'] . '</FW2></FH2>';
                }
                if ($order['cue_field']) {
                    foreach ($order['cue_field'] as $cue) {
                        $format_str .= chr(10) . $cue['title'] . ':' . $cue['txt'];
                    }
                }

                $tempList = array();
                foreach ($tempGoodsList as $val) {
                    $index = isset($val['packname']) && $val['packname'] ? $val['packname'] : 0;
                    if (isset($tempList[$index])) {
                        $tempList[$index]['list'][] = $val;
                    } else {
                        $tempList[$index] = array('name' => $val['packname'], 'list' => array($val));
                    }
                }
       
                ksort($tempList);
                if ($order['order_from'] == 1 || $order['order_from'] == 6) {//商城或线下零售
                    $format_str .= chr(10) . str_repeat('-', $width * 2);
                }
                $order_total = 0;
                foreach ($tempList as $list) {
                    if ($order['order_from'] != 1 && $order['order_from'] != 6) {
                        if (empty($list['name'])) {
                            $list['name'] = L_('1号订购人');
                        }
                        $titleNum = self::dstrlen($list['name']);
                        $s = floor(($width * 2 - $titleNum) / 2);
                        $e = $width * 2 - $titleNum - $s;
                        $format_str .= chr(10) . str_repeat('-', $s). $list['name'] . str_repeat('-', $e);
                    }
                    
                    foreach ($list['list'] as $val) {
                        if ($order['order_from'] == 6 && $val['sell_type'] == 2) {
                            $val['num'] = 1;
                            $val['price'] = $val['code_price'] ?: $val['price'];
                        }

                        if ($val['spec'] && $order['order_from']!=6) {
                            $val['name'] .= chr(10) .chr(32).chr(32).'(' . trim($val['spec']) . ')';
                        }
                        /* $val['name'] = '<FS2>' .$val['name']. '</FS2>'; */
                        if ($val['number']) {
                            $format_str .= chr(10) . $val['name'];
                            $len = self::dstrlen($val['number']);
                            $len = $len % ($width * 2);
                            if ($len > $spaceWidth) {
                                $space = $spaceWidth + 1;
                            } else {
                                $space = $spaceWidth + 1 - $len;
                            }
                            $format_str .= chr(10) . self::cutstr($val['number'], $spaceWidth, $width * 2) . self::format('x' . $val['num'], $space) . self::format(floatval($val['price']* $val['num']), $thirdSpace);
                        } else {
                            $len = self::dstrlen($val['name']);
                            if ($val['spec'] && $order['order_from']!=6) {
                                $len = self::dstrlen('(' . trim($val['spec']) . ')');
                            }
                            $len = $len % ($width * 2);
                            if ($len > $spaceWidth) {
                                $space = $spaceWidth + 1;
                            } else {
                                $space = $spaceWidth + 1 - $len;
                            }
                            $format_str .= chr(10) . self::cutstr($val['name'], $spaceWidth, $width * 2) . self::format('x' . $val['num'], $space) . self::format(floatval($val['price'] * $val['num']), $thirdSpace);
                        }
                        if($val['sell_type'] == 2){//展示重量单价
                            $goodsInfo = D('Shop_goods')->where(['goods_id' => $val['goods_id']])->find();
                            $format_str .= chr(10) .self::cutstr('单价：', $spaceWidth, $width * 2) . self::format(floatval($goodsInfo['unit_price']).'/500g', $space) . self::format('', $thirdSpace);
                        }
                        $order_total += intval($val['num']);
                    }
                }
                $format_str .= chr(10) . str_repeat('-', $width * 2);
                if (count($tempList)>0) {
                    $format_str .= chr(10) . L_('商品总数') . '：' . L_('X1件',['X1'=> $order_total ]);
                }
                $config_data = D('Config_data')->get_config_data();
                $deliver_model_switch = ($config_data && $config_data['deliver_model_switch']) ?  true : false;

                $format_str .= chr(10) . L_('商品总价') . '：'.C('config.Currency_symbol') . floatval($order['goods_price']);
                if (!$deliver_model_switch || $order['is_pick_in_store']>0) {
                    $format_str .= chr(10) . $freight_alias . '：'.C('config.Currency_symbol') . floatval($order['freight_charge']);
                }
                $format_str .= chr(10) . $pack_alias . '：'.C('config.Currency_symbol') . floatval($order['packing_charge']);
                if ($order['paid']) {
                    
                    if ($order['pay_type'] == 'offline' && empty($order['third_id'])) {
                        $format_str .= chr(10) . L_('线下需支付') . '：'.C('config.Currency_symbol') . round($order['price'] - round($order['card_price'] + $order['merchant_balance'] + $order['balance_pay'] + $order['payment_money'] + $order['score_deducte'] + $order['coupon_price'] + $order['card_give_money'], 2), 2);
                    } elseif ($order['pay_type'] == 'offline' && !empty($order['third_id'])) {
                        $format_str .= chr(10) . L_('实付') . '：' . C('config.Currency_symbol') . round($order['price'] - round($order['card_price'] + $order['merchant_balance'] + $order['balance_pay'] + $order['payment_money'] + $order['score_deducte'] + $order['coupon_price'] + $order['card_give_money'], 2), 2) . '(' . L_('现金支付') . ')';
                    } else {
                        $internalCardPayMoney = 0;
                        $internalCardPay = @json_decode($order['internal_card_pay'], true);
                        if (is_array($internalCardPay)) {
                            $internalCardPayMoney = array_sum($internalCardPay);
                        }
                        $payMethod = D('Pay')->get_pay_name($order['pay_type'], $order['is_mobile_pay'], $order['paid'], 0, $order);
                        $format_str .= chr(10) . L_('实付') . '：' . C('config.Currency_symbol') . round(
                                $order['merchant_balance'] +
                                $order['balance_pay'] +
                                $order['payment_money'] +
                                $order['score_deducte'] +
                                $order['coupon_price'] +
                                $order['qiye_pay'] +
                                $order['card_give_money'] +
                                $internalCardPayMoney
                                , 2) . '(' . L_("{$payMethod}") . ')';
                    }
                }

                if (!$deliver_model_switch || $order['is_pick_in_store']>0) {
                    if ($order['username'] || $order['userphone']) {
                        $format_str .= chr(10) . str_repeat('※', $width);
                        if ($order['username']) {
                            $format_str .= chr(10) . L_('客户姓名') . '：' . $order['username'];
                        }
                        if ($order['userphone']) {
                            if ($order['virtual_phone']) {
                                $format_str .= chr(10) . L_('客户手机') . '：' . phone_show($order['userphone']);
                                $format_str .= chr(10) . L_('客户隐私手机') . '：' . $order['virtual_phone_arr'][0][0].L_('转').$order['virtual_phone_arr'][0][1];
                            }else{
                                $format_str .= chr(10) . L_('客户手机') . '：' . $order['userphone'];
                            }
                        }
                        if ($order['order_from'] != 6) {
                            if ($order['is_pick_in_store'] == 2) {
                                //                     $format_str .= chr(10) . '自提地址：' . $order['address'];
                            } else {
                                $format_str .= chr(10) . L_('客户地址') . '：' . $order['address'];
                            }
                        }
                    }
                }

                if(in_array($order['is_pick_in_store'],[1,2]) && !empty($order['pick_id'])){
                    $pickAddressInfo = M('Pick_address')->where(['id' => $order['pick_id']])->find();
                    if(!empty($pickAddressInfo)){
                        $format_str .= chr(10) . L_('自提点地址') . '：' . $pickAddressInfo['pick_addr'];
                    }
                }

                //扫码取单，小票增加二维码（易联云、飞蛾）
                if ($open_scan_qrcode_receive && $order['is_pick_in_store'] == 0 && $order['paid'] == 1) {
                    $format_str .= chr(10) . '<QR>' . $order['real_orderid'] . '</QR>';
                }else{
                    $format_str .= chr(10) . chr(10);
                }
                $format_str .= chr(10) . ' ';
                $format_str .= chr(10) . ' ';
                
                fdump($format_str,'shopprint',1);
                fdump_sql(['shop_order_print' => $format_str],'shop/print',1);
                
                $this->orderPrint->newPrint($usePrinter, $format_str);

            }
            
        }
		
		C('config.tmp_system_lang','');
		
        return false;
    }
    
    
    /**
     * 快店的分单打印
     * @param array $order 订单详情
     * @param array $prints 打印机数组
     * @param int $status  当前状态
     * @param number $printType 打印类型， 0：按菜品归属打印机进行分组打印，1：按商品分类所属打印机进行分组打印
     */
    public function shopOrderMenu($order, $prints, $status, $printType = 0, $isBookOrder = false)
    {
        if ($order['info']) {
            $list = $order['info'];
            $store = M(_view('Merchant_store'))->field(true)->where(array('store_id' => $order['store_id']))->find();
            $goods_ids = array();
            $newList = array();
            foreach ($list as $row) {
                if (! in_array($row['goods_id'], $goods_ids)) {
                    $goods_ids[] = $row['goods_id'];
                }
                $newList[$row['goods_id']][] = $row;
            }
            if ($goods_ids) {
                $goods = D('Shop_goods')->field(true)->where(array('goods_id' => array('in', $goods_ids)))->select();
                $shopGoodsSortDB = D('Shop_goods_sort');
                $sortGoods = array();
                $fids = array();
                // 获得商品父分类
                foreach ($goods as &$row) {
                    $sortIds = $shopGoodsSortDB->getIds($row['sort_id'], $row['store_id']);
                    if ($sortIds) {
                        $fid = array_shift($sortIds);
                        $fids[] = $fid;
                        $row['fid'] = $fid;
                    }
                } 
                if ($fids) {
                    $sortList = array_column($shopGoodsSortDB->field(true)->where(array('sort_id' => array('in', $fids)))->select(), 'print_id','sort_id');
                }

                $printGoodsArr = array();
                foreach ($goods as $m) {
                    if (!$m['print_id'] && $sortList[$m['fid']]) {
                        // 使用分类归属打印机
                        $m['print_id'] = $sortList[$m['fid']];
                    }

                    if (isset($printGoodsArr[$m['print_id']])) {
                        $printGoodsArr[$m['print_id']] = array_merge($printGoodsArr[$m['print_id']], $newList[$m['goods_id']]);
                    } else {
                        // 分类打印机
                        $printGoodsArr[$m['print_id']] = $newList[$m['goods_id']];
                    }
                }
                
                foreach ($printGoodsArr as $pintid => $goodsList) {
                    $mainPrint = isset($prints[$pintid]) ? $prints[$pintid] : null;
                    if (empty($mainPrint)) {
                        continue;
                    }
                    if ($status != -1) {
                        //预订单接单判断
                        if ($isBookOrder) {
                            if (($mainPrint['booking_order_print'] & $status) != $status) {
                                continue;
                            }
                        } else {
                            if ($order['is_booking'] && in_array($status, [5])) {
                                $bookStatus = 1;
                                if (($mainPrint['booking_order_print'] & $bookStatus) != $bookStatus) {
                                    continue;
                                }
                            } else {
                                $statusArr = $mainPrint['paid'] !== '' ? explode(',', $mainPrint['paid']) : [];
                                if (!in_array($status, $statusArr)) {
                                    continue;
                                }
                            }
                        }
                    }
                    
                    $formatData = $this->getTwoData($mainPrint);
                    $width = $formatData['width'];
                    $firstSpace = $formatData['one'];
                    $secondSpace = $formatData['two'];

                    $goodsCount = count($goodsList);// 商品总数大于20使用分单打印
                    $pageNum = ceil($goodsCount/20);// 分页数量
                    
                    for($page = 0; $page<$pageNum ; $page++){
                        $format_str = '';
                        if($pageNum > 1){// 头部页码
                            $pageTitle = ($page+1).'/' .$pageNum;

                            $titleNum = self::dstrlen($pageTitle);
                            if ($mainPrint['is_big'] == 0) {
                                $s = $width - $titleNum;
                                $e = $width * 2 - $titleNum * 2 - $s;
                            } elseif ($mainPrint['is_big'] == 1) {
                                $s = floor(($width * 2 - $titleNum * 1.5) / 2);
                                $e = floor($width * 2 - $titleNum * 1.5 - $s);
                            } else {
                                $s = floor(($width * 2 - $titleNum) / 2);
                                $e = $width * 2 - $titleNum - $s;
                            }
                            $format_str .= ($format_str ? chr(10) : '' ).str_repeat('-', $s) . '<FH2><FW2>' . $pageTitle . '</FW2></FH2>'. str_repeat('-', $e);
                        }

                        // 本单打印商品列表
                        $tempGoodsList = array_slice($goodsList, $page*20, 20);

                        if ($order['order_from'] != 6 && $order['is_pick_in_store'] <= 2) {
                            $title = '#' . $order['fetch_number']. C('config.shop_notice_plat_name');
                        } else {
                            $title = C('config.shop_notice_plat_name');
                        }
                        
                        $titleNum = self::dstrlen($title);
                        if ($mainPrint['is_big'] == 0) {
                            $s = $width - $titleNum;
                            $e = $width * 2 - $titleNum * 2 - $s;
                        } elseif ($mainPrint['is_big'] == 1) {
                            $s = floor(($width * 2 - $titleNum * 1.5) / 2);
                            $e = floor($width * 2 - $titleNum * 1.5 - $s);
                        } else {
                            $s = floor(($width * 2 - $titleNum) / 2);
                            $e = $width * 2 - $titleNum - $s;
                        }
                        $format_str .= ($format_str ? chr(10) : '' ).str_repeat('-', $s) . '<FH2><FW2>' . $title . '</FW2></FH2>'. str_repeat('-', $e);
                        $format_str .= chr(10) . L_('店铺名称') . '：' . $store['name'];
                        $format_str .= chr(10) . L_('订单编号') . '：' . $order['real_orderid'];
                        $format_str .= chr(10) . L_('下单时间') . '：' . date('m-d H:i', $order['create_time']);
                        
                        if ($order['desc']) {
                            $format_str .= chr(10) . str_repeat('-', $width * 2);
                            $format_str .= chr(10) . '<FH2><FW2>' . L_('客户留言') . '：' . $order['desc'] . '</FW2></FH2>';
                        }
                        
                        
                        $tempList = array();
                        foreach ($tempGoodsList as $val) {
                            $index = isset($val['packname']) && $val['packname'] ? $val['packname'] : 0;
                            if (isset($tempList[$index])) {
                                $tempList[$index]['list'][] = $val;
                            } else {
                                $tempList[$index] = array('name' => $val['packname'], 'list' => array($val));
                            }
                        }
                        ksort($tempList);
                        if ($order['order_from'] == 1 || $order['order_from'] == 6) {//商城或线下零售
                            $format_str .= chr(10) . str_repeat('-', $width * 2);
                        }
                        $total = 0;
                        foreach ($tempList as $list) {
                            if ($order['order_from'] != 1 && $order['order_from'] != 6) {
                                if (empty($list['name'])) {
                                    $list['name'] = L_('1号订购人');
                                }
                                $titleNum = self::dstrlen($list['name']);
                                $s = floor(($width * 2 - $titleNum) / 2);
                                $e = $width * 2 - $titleNum - $s;
                                $format_str .= chr(10) . str_repeat('-', $s). $list['name'] . str_repeat('-', $e);
                            }
                            foreach ($list['list'] as $sGoods) {
                                if ($sGoods['spec']) {
                                    $sGoods['name'] .= chr(10).chr(32).chr(32).'(' . $sGoods['spec'] . ')';
                                }
                                $sGoods['name'] = '<FS2>'.$sGoods['name'].'</FS2>';
                                if ($sGoods['number']) {
                                    $format_str .= chr(10) . $sGoods['name'];
                                    $goodsRowNum = self::dstrlen($sGoods['number'] . 'x' . $sGoods['num']);
                                    $goodsSpaceNum = $width * 2 - $goodsRowNum;
                                    $format_str .= chr(10) . $sGoods['number'] . str_repeat(' ', $goodsSpaceNum) . 'x' . $sGoods['num'];
                                } else {
                                    $goodsRowNum = self::dstrlen($sGoods['name'] . 'x' . $sGoods['num']);
                                    $goodsSpaceNum = $width * 2 - $goodsRowNum;
                                    $format_str .= chr(10) . $sGoods['name'] . str_repeat(' ', $goodsSpaceNum) . 'x' . $sGoods['num'];
                                }
                                $total += $sGoods['num'];
                            }
                        }
                        $format_str .= chr(10) . str_repeat('-', $width * 2);
                        $footerNum = self::dstrlen(L_('共计X1件商品',['X1'=>$total]));
                        $spaceNum = $width * 2 - $footerNum;
                        $format_str .= chr(10) . L_('共计X1件商品',['X1'=>str_repeat(' ', $spaceNum) . $total]);
                        fdump($format_str,'format_str',1);
                        $this->orderPrint->newPrint($mainPrint, $format_str);
                    }
                }
            }
        }
    }

    // 外卖模式打印
    private function shop_order_new($order_id, $status = 0,$re, $isBookOrder = false)
    {
        $order = D('Shop_order')->get_order_detail(array('order_id' => $order_id), true);
        if (empty($order)) return false;
        fdump_api(['订单小票打印shop_order_new'=>$order], 'printer/shop_order_'.date('H'),1);
        $prints = $this->getPrints($order['store_id']);
        if (empty($prints)) return false;
        
        $store = M(_view('Merchant_store'))->field(true)->where(array('store_id' => $order['store_id']))->find();
        $store_shop = M(_view('Merchant_store_shop'))->field(true)->where(array('store_id' => $order['store_id']))->find();
        $freight_alias = $store_shop['freight_alias'] ? $store_shop['freight_alias'] : L_('配送费用');
        $pack_alias = $store_shop['pack_alias'] ? $store_shop['pack_alias'] : L_('打包费用');

        //调用分单打印的数据处理
        $this->shopOrderMenuNew($order, $prints, $status, 0, $isBookOrder);

        $open_scan_qrcode_receive = C('config.open_scan_qrcode_receive');
        foreach ($prints as $usePrinter) {
            if ($usePrinter['is_main'] == 0) {
                continue;
            }

            //线下零售未开启打印跳过
            if($status == 1 && $order['order_from'] == 6 && $usePrinter['shop_offline_order_print'] != 1){
                continue;
            }
            if($status == 1 && $order['order_from'] == 6 && C('customization.shop_offline_staff_print') == 1) {
                //线下零售未关联当前店员跳过
                $staff_uids_print = $usePrinter['staff_uids'] ? explode(',', $usePrinter['staff_uids']) : [];
                if (!$staff_uids_print || !in_array($order['staff_id'], $staff_uids_print)) {
                    continue;
                }
            }

            if ($usePrinter['print_type'] == 4) {
                return $this->feieyunPrintForWaimai($order_id, $status, $re, $isBookOrder,$usePrinter);
            }
            if ($usePrinter['print_type'] == 7) {
                return $this->trenditiotPrintForWaimai($order_id, $status, $re, $isBookOrder,$usePrinter);
            }


            if ($status != -1) {
                //预订单接单判断
                if ($isBookOrder) {
                    if (($usePrinter['booking_order_print'] & $status) != $status) {
                        continue;
                    }
                } else {
                    if ($order['is_booking'] && in_array($status, [5])) {
                        $bookStatus = 1;
                        if (($usePrinter['booking_order_print'] & $bookStatus) != $bookStatus) {
                            continue;
                        }
                    } else {
                        $statusArr = $usePrinter['paid'] !== '' ? explode(',', $usePrinter['paid']) : [];
                        if (!in_array($status, $statusArr)) {
                            continue;
                        }
                    }
                }
            }

            $_small_arr = array('is_big'=>0,'paper'=>$usePrinter['paper']);
            $formatData = $this->getThreeData_new($_small_arr);
            $width_small = $formatData['width'];

            $_middle_arr = array('is_big'=>1,'paper'=>$usePrinter['paper']);
            $formatData_middle = $this->getThreeData_new($_middle_arr);
            $width_mid = $formatData_middle['width'];

            $_big_arr = array('is_big'=>2,'paper'=>$usePrinter['paper']);
            $formatData_big = $this->getThreeData_new($_big_arr);
            $width_big = $formatData_big['width'];

            if ($usePrinter['is_big']==2) {
                $smallSize = 'big';
                $middleSize = 'big';
                $width = $width_big;
            }else{
                $smallSize = 'small';
                $middleSize = 'middle';
                $width = $width_small;
            }

            // 商品列表
            $tempList = array();
            $goodList = D('Shop_goods')->combination_data($order['info'], 'spec');
            $goodsCount = count($goodList);// 商品总数大于20使用分单打印
            $pageNum = ceil($goodsCount/20);
            
            for($page = 0; $page<$pageNum ; $page++){
                $format_arr = [];
                if($pageNum > 1){// 头部页码
                    $pageTitle = ($page+1).'/' .$pageNum;
                    $format_arr[] = $this->print_format_to_json($pageTitle,$middleSize,'center');
                }

                // 本单打印商品列表
                $tempGoodsList = array_slice($goodList, $page*20, 20);

                if ($order['paid'] == 1 && $order['order_from'] != 6 && $order['is_pick_in_store'] <= 2) {
                    $title = '#' . $order['fetch_number'].'  '. C('config.shop_notice_plat_name');
                } else {
                    $title = C('config.shop_notice_plat_name');
                }
                $titleNum = self::dstrlen($title);

                // 头部标题
                $content =   '--'. $title.'--';
                $format_arr[] = $this->print_format_to_json(str_repeat(' ', $width * 2),'small','center'); 
                $format_arr[] = $this->print_format_to_json($content,'big','center'); 

                // 头部描述
                $format_arr[] = $this->print_format_to_json(str_repeat('-', $width * 2),$smallSize,'center'); 
                if($re){
                    if (C('config.is_hark_system')) {
                        $re_title = L_('重打小票，请勿重复出餐。HARK平台不负责重复出餐带来的损失。');
                    }else{
                        $re_title = L_('重打小票，请勿重复出餐。平台不负责重复出餐带来的损失。');
                    }
                    $format_arr[] = $this->print_format_to_json($re_title,$middleSize,'left');
                    $format_arr[] = $this->print_format_to_json(str_repeat('-', $width * 2),$smallSize,'center'); 
                }

                //退款成功打印
                if ($status == 3) {
                    // $format_str .= chr(10) . L_('退款订单');
                    $format_arr[] = $this->print_format_to_json(L_('退款订单'),$smallSize,'center'); 
                    $refundMoney = floatval($order['price']);
                    if ((MODULE_NAME == 'Store' && ACTION_NAME == 'replyRefund') || (MODULE_NAME == 'Storestaff' && ACTION_NAME == 'replyRefund')) {
                        //售后同意退款
                        $refundMoney = M('Shop_order_refund')->where(['order_id' => $order['order_id'], 'status' => 1])->order('id DESC')->getField('price');
                    }

                    $content = L_('退款金额') . '：' . C('config.Currency_symbol') . $refundMoney;
                    $format_arr[] = $this->print_format_to_json($content,$smallSize); 
                }

                if (C('config.is_hark_system')) {
                    $area_service_phone = M('Area')->where(['area_id'=>$store['city_id']])->getField('service_phone');
                } else {
                    $area_service_phone = $store['phone'];
                }
                if($area_service_phone){
                    $format_arr[] = $this->print_format_to_json(L_('客服电话') . '：' . $area_service_phone,$smallSize); 
                }
                if ($order['is_booking'] && $order['time_id'] == 0) {
                    $format_arr[] = $this->print_format_to_json(L_('请注意！此单为预约单，请勿提前出单'),$smallSize);
                }
                
                if($order['time_id'] > 0){
                    $format_arr[] = $this->print_format_to_json(L_('预期送达时间') . '：' . date('d M H:i', $order['expect_use_time']), $middleSize,'left');
                    
                    $distance_time = $order['expect_use_time'] - time();
                    $day_time = intval($distance_time/3600/24);
                    $hour_time = intval((($distance_time/86400)-$day_time)*24);
                    $minute_time = intval((((($distance_time/86400)-$day_time)*24) - $hour_time)*60);
                    
                    $format_arr[] = $this->print_format_to_json(L_('距离当前时间') . '：' . L_('X1天X2小时X3分钟', ['X1' => $day_time, 'X2' => $hour_time, 'X3' => $minute_time]), $middleSize,'left');
                    $format_arr[] = $this->print_format_to_json(L_('请您注意备货时间！'), $middleSize,'left');
                }else{
                    if ($order['is_pick_in_store'] != '1' && $order['expect_pick_time']) {
                        if ($order['expect_use_time'] > 0) {
                            $format_arr[] = $this->print_format_to_json(L_('预计送达时间') . '：' . date('Y-m-d H:i', $order['expect_use_time']), $middleSize);
                        }
                        $format_arr[] = $this->print_format_to_json(L_('取单时间') . '：' . date('Y-m-d H:i', $order['expect_pick_time']),$middleSize);
                    }else if($order['expect_use_time']){
                        $format_arr[] = $this->print_format_to_json(L_('预计送达时间') . '：' . date('Y-m-d H:i', $order['expect_use_time']),$middleSize);
                    }
                }
                
                $format_arr[] = $this->print_format_to_json(L_('店铺名称') . '：' . $store['name'],$smallSize);
                
                if($order['is_pick_in_store'] == 1){
                    $format_arr[] = $this->print_format_to_json(L_('配送方式') . '：' . L_('商家配送'),$smallSize);
                }else if($order['is_pick_in_store'] == 2){
                    $format_arr[] = $this->print_format_to_json(L_('配送方式') . '：' . L_('用户自取'),$smallSize);
                }else if($order['is_pick_in_store'] == 3){
                    $format_arr[] = $this->print_format_to_json(L_('配送方式') . '：' . L_('快递配送'),$smallSize);
                }else{
                    $format_arr[] = $this->print_format_to_json(L_('配送方式') . '：' . L_('平台配送'),$smallSize);
                }

                $format_arr[] = $this->print_format_to_json(L_('订单编号') . '：' . $order['real_orderid'],$smallSize);
                $format_arr[] = $this->print_format_to_json(L_('下单时间') . '：' . date('Y-m-d H:i', $order['create_time']),$smallSize);

                if ($order['desc'] || $order['cue_field']) {
                    $format_arr[] = $this->print_format_to_json(str_repeat('-', $width * 2),$smallSize,'center');
                    $order['desc'] && $format_arr[] = $this->print_format_to_json(L_('客户留言') . '：' . $order['desc'], $middleSize);
                    foreach ($order['cue_field'] as $cue) {
                        $format_arr[] = $this->print_format_to_json($cue['title'] . ':' . $cue['txt'], $middleSize);
                    }
                    $format_arr[] = $this->print_format_to_json(str_repeat('-', $width * 2),$smallSize,'center');
                }

                $tempList = array();
                foreach ($tempGoodsList as $val) {
                    if ($order['order_from'] == 6 && $val['sell_type'] == 2) {
                        $val['num'] = 1;
                        $val['price'] = $val['code_price'] ?: $val['price'];
                    }

                    $index = isset($val['packname']) && $val['packname'] ? $val['packname'] : 0;
                    if (isset($tempList[$index])) {
                        $tempList[$index]['list'][] = $val;
                    } else {
                        $tempList[$index] = array('name' => $val['packname'], 'list' => array($val));
                    }
                }
        
                ksort($tempList);
                if ($order['order_from'] == 1 || $order['order_from'] == 6) {//商城或线下零售
                    $format_arr[] = $this->print_format_to_json(str_repeat('-', $width * 2),$smallSize,'center');
                }

                // 获得数量和价格的最大长度（和下面名字组合判断是否换行）
                $num_price_length = 0;
                foreach ($tempList as $list) {
                    foreach ($list['list'] as $val) {
                        $num_str = 'x'.$val['num'] .'  '.floatval($val['price']);
                        $tmp_length = (strlen($num_str) + mb_strlen($num_str,'UTF8')) / 2;
                        if ($tmp_length>$num_price_length) {
                            $num_price_length = $tmp_length;
                        }
                    }
                }

                $order_total = 0;
                foreach ($tempList as $list) {
                    if ($order['order_from'] != 1 && $order['order_from'] != 6) {
                        if (empty($list['name'])) {
                            $list['name'] = L_('1号订购人');
                        }
                        $titleNum = self::dstrlen($list['name']);
                        $s = floor(($width * 2 - $titleNum) / 2);
                        $e = $width * 2 - $titleNum - $s;
                        $format_arr[] = $this->print_format_to_json(str_repeat('-', $s). $list['name'] . str_repeat('-', $e),$smallSize,'center');
                    }
                    foreach ($list['list'] as $val) {
                        if ($val['spec'] && $order['order_from']!=6) {
                            $val['name'] .= '[' . trim($val['spec']) . ']';
                        }

                        if ($val['number']) {
                            $len = self::dstrlen($val['number']);
                        }else{
                            $len = self::dstrlen($val['name']);
                        }

                        $num_price_str = '';//数量与价格拼接
                        if ($len+$num_price_length+2 > $width_big * 2) { 
                            if ($val['number']) {
                                // 有条形码，商品名独立显示一行
                                $format_arr[] = $this->print_format_to_json($val['name'],'big');
                                $format_arr[] = $this->print_format_to_json($val['number'],'big');
                            }else{
                                $format_arr[] = $this->print_format_to_json($val['name'],'big');
                            }
                            // 拼接数量
                            $num_price_str .= str_repeat(' ',($width_big * 2-$num_price_length)).'x'.$val['num'];
                        }else{
                            if ($val['number']) {
                                // 有条形码，商品名独立显示一行
                                $format_arr[] = $this->print_format_to_json($val['name'],'big');
                                $num_price_str .= $val['number'].'  ';
                            }else{
                                $num_price_str .= $val['name'].'  ';
                            }
                            $name_length = (strlen($num_price_str) + mb_strlen($num_price_str,'UTF8')) / 2;
                            // 拼接数量
                            $num_price_str .= str_repeat(' ',($width_big * 2-$num_price_length - $name_length)).'x'.$val['num'];
                        }

                        // 数量长度
                        $num_length = (strlen($num_price_str) + mb_strlen($num_price_str,'UTF8')) / 2;

                        // 价格长度
                        $price_length = strlen(getFormatNumber($val['price']*$val['num']));

                        // 拼接价格，价格居右显示
                        $num_price_str .= str_repeat(' ',($width_big * 2 - $price_length - $num_length)).getFormatNumber($val['price']*$val['num']);
                        $format_arr[] = $this->print_format_to_json($num_price_str,'big');
                        if($val['sell_type'] == 2){//展示重量单价
                            $goodsInfo = D('Shop_goods')->where(['goods_id' => $val['goods_id']])->find();
                            $format_arr[] = $this->print_format_to_json('单价：'.floatval($goodsInfo['unit_price']).'/500g','big');
                        }
                        $order_total += intval($val['num']);
                    }
                }

                $format_arr[] = $this->print_format_to_json(str_repeat('-', $width * 2),$smallSize,'center');
                if (count($tempList)>0) {
                    $format_arr[] = $this->print_format_to_json(L_('商品总数') . '：' . L_('X1件',['X1'=> $order_total ]),$middleSize);
                }
                $config_data = D('Config_data')->get_config_data();
                $deliver_model_switch = ($config_data && $config_data['deliver_model_switch']) ?  true : false;

                $format_arr[] = $this->print_format_to_json(L_('商品总价') . '：'.C('config.Currency_symbol') . floatval($order['goods_price']),$middleSize);
                if (!$deliver_model_switch || $order['is_pick_in_store']>0) {
                    $format_arr[] = $this->print_format_to_json($freight_alias . '：'.C('config.Currency_symbol') . floatval($order['freight_charge']),$smallSize);
                }
                $format_arr[] = $this->print_format_to_json($pack_alias . '：'.C('config.Currency_symbol') . floatval($order['packing_charge']),$smallSize);
                if ($order['paid']) {
                    if ($order['pay_type'] == 'offline' && empty($order['third_id'])) {
                        $content =  L_('线下需支付') . '：'.C('config.Currency_symbol') . round($order['price'] - round($order['card_price'] + $order['merchant_balance'] + $order['balance_pay'] + $order['payment_money'] + $order['score_deducte'] + $order['coupon_price'] + $order['card_give_money'], 2), 2);
                        $format_arr[] = $this->print_format_to_json($content,$middleSize);
                    } elseif ($order['pay_type'] == 'offline' && !empty($order['third_id'])) {
                        $content = L_('实付') . '：' . C('config.Currency_symbol') . round($order['price'] - round($order['card_price'] + $order['merchant_balance'] + $order['balance_pay'] + $order['payment_money'] + $order['score_deducte'] + $order['coupon_price'] + $order['card_give_money'], 2), 2) . '(' . L_('现金支付') . ')';
                        $format_arr[] = $this->print_format_to_json($content,$middleSize);
                    } else {
                        $internalCardPayMoney = 0;
                        $internalCardPay = @json_decode($order['internal_card_pay'], true);
                        if (is_array($internalCardPay)) {
                            $internalCardPayMoney = array_sum($internalCardPay);
                        }
                        $payMethod = D('Pay')->get_pay_name($order['pay_type'], $order['is_mobile_pay'], $order['paid'], 0, $order);
                        $content = L_('实付') . '：' . C('config.Currency_symbol') . round(
                                $order['merchant_balance'] +
                                $order['balance_pay'] +
                                $order['payment_money'] +
                                $order['score_deducte'] +
                                $order['coupon_price'] +
                                $order['card_give_money'] +
                                $internalCardPayMoney
                                , 2) . '(' . L_("{$payMethod}") . ')';
                        
                        $format_arr[] = $this->print_format_to_json($content, $middleSize);
                    }
                }
                if (!$deliver_model_switch || $order['is_pick_in_store']>0) {
                    if ($order['username'] || $order['userphone']) {
                        // $format_str .= chr(10) . str_repeat('※', $width);
                        $format_arr[] = $this->print_format_to_json(str_repeat('-', $width*2),$smallSize,'center');
                        if ($order['username']) {
                            $format_arr[] = $this->print_format_to_json(L_('客户姓名') . '：' . $order['username'],$middleSize);
                        }
                        if ($order['userphone']) {
                            if ($order['virtual_phone']) {
                                $format_arr[] = $this->print_format_to_json(L_('客户手机') . '：' . phone_show($order['userphone']),'big');
                                $virtual_phone = $order['virtual_phone_arr'][0][0];
                                $order['virtual_phone_arr'][0][1] &&  $virtual_phone .= L_('转').$order['virtual_phone_arr'][0][1];
                                $format_arr[] = $this->print_format_to_json(L_('客户隐私手机') . '：' . $virtual_phone,'big');
                            }else{
                                $format_arr[] = $this->print_format_to_json(L_('客户手机') . '：' . $order['userphone'],'big');
                            }
                        }
                        if ($order['order_from'] != 6) {
                            if ($order['is_pick_in_store'] == 2) {
                                //                     $format_str .= chr(10) . '自提地址：' . $order['address'];
                            } else {
                            $format_arr[] = $this->print_format_to_json(L_('客户地址') . '：' . $order['address'],'big');
                            }
                        }
                    }
                }
                if(in_array($order['is_pick_in_store'],[1,2]) && !empty($order['pick_id'])){
                    $pickAddressInfo = M('Pick_address')->where(['id' => $order['pick_id']])->find();
                    if(!empty($pickAddressInfo)){
                        $format_arr[] = $this->print_format_to_json(L_('自提点地址') . '：' . $pickAddressInfo['pick_addr'],'big');
                    }
                }
                if (($usePrinter['print_type'] == 6 && $usePrinter['version'] >= 1450) || ($usePrinter['print_type'] == 2 && $usePrinter['version'] >= 50)) {
                    $format_arr[] = $this->print_format_to_json(str_repeat(' ', $width * 2),'small'); 
                    $format_arr[] = $this->print_format_to_json(str_repeat(' ', $width * 2),'small'); 
                    // 商米打印机 有线打印机
                    $format_str = array();
                    $format_str['print_data'] = $format_arr;
                    $format_str = json_encode($format_str, JSON_UNESCAPED_UNICODE);
                    fdump([$open_scan_qrcode_receive, $order['is_pick_in_store'], $order['paid'], $format_arr, $format_str], 'shopprint', 1);
                }else{
					//扫码取单，小票增加二维码（易联云、飞蛾）
					if ($open_scan_qrcode_receive && $order['is_pick_in_store'] == 0 && $order['paid'] == 1) {
						$format_arr[] = $this->print_format_to_json(str_repeat('*', $width * 2), $smallSize, 'center');
						$format_arr[] = $this->print_format_to_json('<QR>' . $order['real_orderid'] . '</QR>', 'small', 'center');
					}
					
                    $format_arr[] = $this->print_format_to_json(str_repeat(' ', $width * 2),'small'); 
                    $format_arr[] = $this->print_format_to_json(str_repeat(' ', $width * 2),'small'); 
                    $format_str = $this->print_format_to_str($format_arr,$usePrinter);
                    fdump($format_str,'shopprint',1);
                }
                fdump_sql(['shop_order_new_print' => $format_str],'shop/print',1);
                
                $this->orderPrint->newPrint($usePrinter, $format_str, 1);
                
            }
        }
        
        C('config.tmp_system_lang','');
        
        return false;
    }


    /**
     * 快店的分单打印
     * @param array $order 订单详情
     * @param array $prints 打印机数组
     * @param int $status  当前状态
     * @param number $printType 打印类型， 0：按菜品归属打印机进行分组打印，1：按商品分类所属打印机进行分组打印
     */
    public function shopOrderMenuNew($order, $prints, $status, $printType = 0,$isBookOrder=false)
    {
        if ($order['info']) {
            $list = $order['info'];
            $store = M(_view('Merchant_store'))->field(true)->where(array('store_id' => $order['store_id']))->find();
            $goods_ids = array();
            $newList = array();
            foreach ($list as $row) {
                if (! in_array($row['goods_id'], $goods_ids)) {
                    $goods_ids[] = $row['goods_id'];
                }
                $newList[$row['goods_id']][] = $row;
            }
            if ($goods_ids) {
                $goods = D('Shop_goods')->field(true)->where(array('goods_id' => array('in', $goods_ids)))->select();
                $shopGoodsSortDB = D('Shop_goods_sort');
                $sortGoods = array();
                $fids = array();
                // 获得商品父分类
                foreach ($goods as &$row) {
                    $sortIds = $shopGoodsSortDB->getIds($row['sort_id'], $row['store_id']);
                    if ($sortIds) {
                        $fid = array_shift($sortIds);
                        $fids[] = $fid;
                        $row['fid'] = $fid;
                    }
                } 
                if ($fids) {
                    $sortList = array_column($shopGoodsSortDB->field(true)->where(array('sort_id' => array('in', $fids)))->select(), 'print_id','sort_id');
                }

                $printGoodsArr = array();
                foreach ($goods as $m) {
                    if (!$m['print_id'] && $sortList[$m['fid']]) {
                        // 使用分类归属打印机
                        $m['print_id'] = $sortList[$m['fid']];
                    }

                    if (isset($printGoodsArr[$m['print_id']])) {
                        $printGoodsArr[$m['print_id']] = array_merge($printGoodsArr[$m['print_id']], $newList[$m['goods_id']]);
                    } else {
                        // 分类打印机
                        $printGoodsArr[$m['print_id']] = $newList[$m['goods_id']];
                    }
                }
                
                foreach ($printGoodsArr as $pintid => $goodsList) {
                    $mainPrint = isset($prints[$pintid]) ? $prints[$pintid] : null;
                    if (empty($mainPrint)) {
                        continue;
                    }
                    $usePrinter = $mainPrint;
                    if ($status != -1) {
                        //预订单接单判断
                        if ($isBookOrder) {
                            if (($mainPrint['booking_order_print'] & $status) != $status) {
                                continue;
                            }
                        } else {
                            if ($order['is_booking'] && in_array($status, [5])) {
                                $bookStatus = 1;
                                if (($mainPrint['booking_order_print'] & $bookStatus) != $bookStatus) {
                                    continue;
                                }
                            } else {
                                $statusArr = $mainPrint['paid'] !== '' ? explode(',', $mainPrint['paid']) : [];
                                if (!in_array($status, $statusArr)) {
                                    continue;
                                }
                            }
                        }
                    }

                    $_small_arr = array('is_big'=>0,'paper'=>$mainPrint['paper']);
                    $formatData = $this->getThreeData_new($_small_arr);
                    $width_small = $formatData['width'];

                    $_middle_arr = array('is_big'=>1,'paper'=>$mainPrint['paper']);
                    $formatData_middle = $this->getThreeData_new($_middle_arr);
                    $width_mid = $formatData_middle['width'];

                    $_big_arr = array('is_big'=>2,'paper'=>$mainPrint['paper']);
                    $formatData_big = $this->getThreeData_new($_big_arr);
                    $width_big = $formatData_big['width'];

                    if ($mainPrint['is_big']==2) {
                        $smallSize = 'big';
                        $middleSize = 'big';
                        $width = $width_big;
                    }else{
                        $smallSize = 'small';
                        $middleSize = 'middle';
                        $width = $width_small;
                    }

                    $goodsCount = count($goodsList);// 商品总数大于20使用分单打印
                    $pageNum = ceil($goodsCount/20);// 分页数量
                    
                    for($page = 0; $page<$pageNum ; $page++){
                        $format_arr = [];
                        if($pageNum > 1){// 头部页码
                            $pageTitle = ($page+1).'/' .$pageNum;
                            $format_arr[] = $this->print_format_to_json($pageTitle,$middleSize,'center');
                        }

                        // 本单打印商品列表
                        $tempGoodsList = array_slice($goodsList, $page*20, 20);

                        if ($order['order_from'] != 6 && $order['is_pick_in_store'] <= 2) {
                            $title = '#' . $order['fetch_number']. C('config.shop_notice_plat_name');
                        } else {
                            $title = C('config.shop_notice_plat_name');
                        }

                        // 头部标题
                        $content =   '--'. $title.'--';
                        $format_arr = [];
                        $format_arr[] = $this->print_format_to_json(str_repeat('', $width * 2),'small','center'); 
                        $format_arr[] = $this->print_format_to_json($content,'big','center'); 
                        $format_arr[] = $this->print_format_to_json(str_repeat('-', $width * 2),'small','center'); 
                        $format_arr[] = $this->print_format_to_json(L_('店铺名称') . '：' . $store['name'],$smallSize); 
                        $format_arr[] = $this->print_format_to_json(L_('订单编号') . '：' . $order['real_orderid'],$smallSize); 
                        $format_arr[] = $this->print_format_to_json(L_('下单时间') . '：' . date('m-d H:i', $order['create_time'],$smallSize));
                        
                        if ($order['desc']) {
                            $format_arr[] = $this->print_format_to_json(str_repeat('-', $width * 2),$smallSize); 
                            $format_arr[] = $this->print_format_to_json(L_('客户留言') . '：' . $order['desc'],'middle','center');
                        }
                        
                        $tempList = array();
                        foreach ($tempGoodsList as $val) {
                            $index = isset($val['packname']) && $val['packname'] ? $val['packname'] : 0;
                            if (isset($tempList[$index])) {
                                $tempList[$index]['list'][] = $val;
                            } else {
                                $tempList[$index] = array('name' => $val['packname'], 'list' => array($val));
                            }
                        }
                        ksort($tempList);
                        if ($order['order_from'] == 1 || $order['order_from'] == 6) {//商城或线下零售
                            $format_arr[] = $this->print_format_to_json(str_repeat('-', $width * 2),$smallSize); 
                        }

                        // 获得数量和价格的最大长度（和下面名字组合判断是否换行）
                        $num_price_length = 0;
                        foreach ($tempList as $list) {
                            foreach ($list['list'] as $val) {
                                $num_str = 'x'.$val['num'];
                                $tmp_length = (strlen($num_str) + mb_strlen($num_str,'UTF8')) / 2;
                                if ($tmp_length>$num_price_length) {
                                    $num_price_length = $tmp_length;
                                }
                            }
                        }

                        $total = 0;
                        foreach ($tempList as $list) {
                            if ($order['order_from'] != 1 && $order['order_from'] != 6) {
                                if (empty($list['name'])) {
                                    $list['name'] = L_('1号订购人');
                                }
                                $titleNum = self::dstrlen($list['name']);
                                $s = floor(($width * 2 - $titleNum) / 2);
                                $e = $width * 2 - $titleNum - $s;
                                $format_arr[] = $this->print_format_to_json(str_repeat('-', $s). $list['name'] . str_repeat('-', $e),$smallSize,'center'); 
                            }
                            foreach ($list['list'] as $sGoods) {
                                if ($sGoods['spec']) {
                                    // $sGoods['name'] .= chr(10).chr(32).chr(32).'(' . $sGoods['spec'] . ')';
                                    $sGoods['name'] .= '[' . trim($sGoods['spec']) . ']';
                                }
                                // $sGoods['name'] = '<FS2>'.$sGoods['name'].'</FS2>';
                                // if ($sGoods['number']) {
                                //     $format_str .= chr(10) . $sGoods['name'];
                                //     $goodsRowNum = self::dstrlen($sGoods['number'] . '×' . $sGoods['num']);
                                //     $goodsSpaceNum = $width * 2 - $goodsRowNum;
                                //     $format_str .= chr(10) . $sGoods['number'] . str_repeat(' ', $goodsSpaceNum) . '×' . $sGoods['num'];
                                // } else {
                                //     $goodsRowNum = self::dstrlen($sGoods['name'] . '×' . $sGoods['num']);
                                //     $goodsSpaceNum = $width * 2 - $goodsRowNum;
                                //     $format_str .= chr(10) . $sGoods['name'] . str_repeat(' ', $goodsSpaceNum) . '×' . $sGoods['num'];
                                // }

                                if ($sGoods['number']) {
                                    $len = self::dstrlen($sGoods['number']);
                                }else{
                                    $len = self::dstrlen($sGoods['name']);
                                }

                                $num_price_str = '';//数量与价格拼接
                                if ($len+$num_price_length+2 > $width_big * 2) { 
                                    if ($sGoods['number']) {
                                        // 有条形码，商品名独立显示一行
                                        $format_arr[] = $this->print_format_to_json($sGoods['name'],'big');
                                        $format_arr[] = $this->print_format_to_json($sGoods['number'],'big');
                                    }else{
                                        $format_arr[] = $this->print_format_to_json($sGoods['name'],'big');
                                    }
                                    // 拼接数量
                                    $num_price_str .= str_repeat(' ',($width_big * 2-$num_price_length)).'x'.$sGoods['num'];
                                   
                                }else{
                                    if ($sGoods['number']) {
                                        // 有条形码，商品名独立显示一行
                                        $format_arr[] = $this->print_format_to_json($sGoods['name'],'big');
                                        $num_price_str .= $sGoods['number'].'  ';
                                    }else{
                                        $num_price_str .= $sGoods['name'].'  ';
                                    }
                                    $name_length = (strlen($num_price_str) + mb_strlen($num_price_str,'UTF8')) / 2;
                                    // 拼接数量
                                    $num_price_str .= str_repeat(' ',($width_big * 2-$num_price_length - $name_length)).'x'.$sGoods['num'];
                                }

                                // 数量长度
                                $num_length = (strlen($num_price_str) + mb_strlen($num_price_str,'UTF8')) / 2;
                                $format_arr[] = $this->print_format_to_json($num_price_str,'big');
                                        $total += $sGoods['num'];
                            }
                        }
                        $format_arr[] = $this->print_format_to_json(str_repeat('-', $width * 2),$smallSize,'center'); 
                        $footerNum = self::dstrlen(L_('共计X1件商品',['X1'=>$total]));
                        $spaceNum = $width * 2 - $footerNum;
                        // $format_str .= chr(10) . L_('共计X1件商品',['X1'=>str_repeat(' ', $spaceNum) . $total]);
                        $format_arr[] = $this->print_format_to_json(L_('共计X1件商品',['X1'=>str_repeat(' ', $spaceNum) . $total]),$middleSize);  
                        $format_arr[] = $this->print_format_to_json(str_repeat('', $width * 2),'small','center'); 
                        if (($mainPrint['print_type'] == 6 && $mainPrint['version'] >= 1450) || ($mainPrint['print_type'] == 2 && $mainPrint['version'] >= 50)) {
                            // 商米打印机 有线打印机
                            $format_str = array();
                            $format_str['print_data'] = $format_arr;
                            $format_str = json_encode($format_str, JSON_UNESCAPED_UNICODE);
                            fdump($format_str,'print',1);
                        }else{ 
                            $format_str = $this->print_format_to_str($format_arr,$mainPrint);
                            fdump($format_str,'print',1);
                        }     
                        $this->orderPrint->newPrint($mainPrint, $format_str, 1);
                    }
                }
            }
        }
    }
}
