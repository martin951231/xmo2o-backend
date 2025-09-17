<?php
class Market_orderModel extends Model
{
    public function getList($data, $type)
    {
        
        if ($type == 'sell') {
            import('@.ORG.merchant_page');
            $where = array('sell_mer_id' => $data['mer_id']);
            $sqlWhere = ' WHERE `o`.`sell_mer_id`=' . $data['mer_id'];
            if ($data['keyword']) {
                if ($data['search_type'] == 'order_id') {
                    $where['order_id'] = $data['keyword'];
                    $sqlWhere .= ' AND `o`.`order_id`=' . $data['keyword'];
                }else if ($data['search_type'] == 'orderid') {
                    $where['orderid'] = array('like', "%{$data['keyword']}%");
                    $sqlWhere .= ' AND `o`.`orderid` Like "%' . $data['keyword'] . '%"';
                } elseif ($data['search_type'] == 'third_id') {
                    $where['third_id'] = array('like', "%{$data['keyword']}%");
                    $sqlWhere .= ' AND `o`.`third_id` Like "%' . $data['keyword'] . '%"';
                } elseif ($data['search_type'] == 'merchant_name') {
                    $mer_info = M('Merchant')->where(array('name'=>array('like', "%{$data['keyword']}%")))->field('mer_id')->select();
                    $mer_arr = array();
                    if ($mer_info) {
                        foreach ($mer_info as $vals) {
                            $mer_arr[] = $vals['mer_id'];
                        }
                    }
                    $where['mer_id'] = array('in', $mer_arr);
                    $sqlWhere .= ' AND `o`.`mer_id` IN ' . "(".implode(',',$mer_arr).")";
                } elseif ($data['search_type'] == 'username') {
                    $where['username'] = array('like', "%{$data['keyword']}%");
                    $sqlWhere .= ' AND `o`.`username` Like  "%' . $data['keyword'] . '%"';
                } elseif ($data['search_type'] == 'phone') {
                    $where['userphone'] = array('like', "%{$data['username']}%");
                    $sqlWhere .= ' AND `o`.`userphone` Like "%' . $data['keyword'] . '%"';
                }
            }

            if ($data['pay_type']!=-1 && $data['pay_type']) {
                if ($data['pay_type']=='merchant_money') {
                    $where['pay_type'] = '';
                    $sqlWhere .= " AND `o`.`pay_type`=''";
                } else {
                    $where['pay_type'] = $data['pay_type'];
                    $sqlWhere .= " AND `o`.`pay_type`='{$data['pay_type']}'";
                }
            }

            if ($data['is_pick_in_store']!=-1 && $data['is_pick_in_store']) {
                if ($data['is_pick_in_store']==-2) {
                    $where['is_pick_in_store'] = 0;
                    $sqlWhere .= ' AND `o`.`is_pick_in_store`=0';
                } else {
                    $where['is_pick_in_store'] = $data['is_pick_in_store'];
                    $sqlWhere .= " AND `o`.`is_pick_in_store`={$data['is_pick_in_store']}";
                }
            }

            if ($data['begin_time'] && $data['end_time']) {
                $where['_string'] = "create_time BETWEEN {$data['begin_time']} AND {$data['end_time']}";
                $sqlWhere .= " AND (`o`.`create_time`  BETWEEN  {$data['begin_time']} AND {$data['end_time']})";
            }

        } elseif ($type == 'buy') {
            import('@.ORG.merchant_page');
            $where = array('mer_id' => $data['mer_id'], 'type' => 0);
            $sqlWhere = ' WHERE `o`.`type`=0 AND `o`.`mer_id`=' . $data['mer_id'];
        } else {
            $where = [];
            $sqlWhere = ' WHERE 1';
            import('@.ORG.system_page');
            if (isset($data['key']) && isset($data['key'])) {

            }
            if(isset($data['fid']) && $data['fid']>0){
                $sqlWhere .=  ' AND g.cat_fid=' . $data['fid'];
            }
            if(isset($data['cid']) && $data['cid']>0){
                $sqlWhere .=  ' AND g.cat_id=' . $data['cid'];
            }

            if ($data['keyword']) {
                if ($data['search_type'] == 'order_id') {
                    $where['order_id'] = $data['keyword'];
                    $sqlWhere .= ' AND `o`.`order_id`=' . $data['keyword'];
                }else if ($data['search_type'] == 'orderid') {
                    $where['orderid'] = array('like', "%{$data['keyword']}%");
                    $sqlWhere .= ' AND `o`.`orderid` Like "%' . $data['keyword'] . '%"';
                } elseif ($data['search_type'] == 'third_id') {
                    $where['third_id'] = array('like', "%{$data['keyword']}%");
                    $sqlWhere .= ' AND `o`.`third_id` Like "%' . $data['keyword'] . '%"';
                } elseif ($data['search_type'] == 'merchant_name') {
                    $mer_info = M('Merchant')->where(array('name'=>array('like', "%{$data['keyword']}%")))->field('mer_id')->select();
                    $mer_arr = array();
                    if ($mer_info) {
                        foreach ($mer_info as $vals) {
                            $mer_arr[] = $vals['mer_id'];
                        }
                    }
                    $where['mer_id'] = array('in', $mer_arr);
                    $sqlWhere .= ' AND `o`.`mer_id` IN ' . "(".implode(',',$mer_arr).")";
                } elseif ($data['search_type'] == 'username') {
                    $where['username'] = array('like', "%{$data['keyword']}%");
                    $sqlWhere .= ' AND `o`.`username` Like  "%' . $data['keyword'] . '%"';
                } elseif ($data['search_type'] == 'phone') {
                    $where['userphone'] = array('like', "%{$data['keyword']}%");
                    $sqlWhere .= ' AND `o`.`userphone` Like "%' . $data['keyword'] . '%"';
                }
            }

            if ($data['pay_type']!=-1 && $data['pay_type']) {
                if ($data['pay_type']=='merchant_money') {
                    $where['pay_type'] = '';
                    $sqlWhere .= " AND `o`.`pay_type`=''";
                } else {
                    $where['pay_type'] = $data['pay_type'];
                    $sqlWhere .= " AND `o`.`pay_type`='{$data['pay_type']}'";
                }
            }

            if ($data['is_pick_in_store']!=-1 && $data['is_pick_in_store']) {
                if ($data['is_pick_in_store']==-2) {
                    $where['is_pick_in_store'] = 0;
                    $sqlWhere .= ' AND `o`.`is_pick_in_store`=0';
                } else {
                    $where['is_pick_in_store'] = $data['is_pick_in_store'];
                    $sqlWhere .= " AND `o`.`is_pick_in_store`={$data['is_pick_in_store']}";
                }
            }

            if ($data['begin_time'] && $data['end_time']) {
                $where['_string'] = "create_time BETWEEN {$data['begin_time']} AND {$data['end_time']}";
                $sqlWhere .= " AND (`o`.`create_time`  BETWEEN  {$data['begin_time']} AND {$data['end_time']})";
            }
        }
        if (isset($data['goods_id']) && $data['goods_id']>0) {
            $where['goods_id'] = $data['goods_id'];
            $sqlWhere .= ' AND o.goods_id=' . $data['goods_id'];
        }
        if ($data['status'] && $data['status'] != -1) {
            // -1 查询全部  -2 查询待付款(为了考虑兼容旧数据 0 不做状态查询) 1:待发货，2：待收货，3：收货成功，4:已退款，10：下单成功
            // 暂时只针对  待付款  待发货  待收货进行查询0
            if (-2 == $data['status']) {
                $where['_string'] = "(status=10 AND pay_type!='offline') OR (offline_status=0 AND pay_type='offline')";
                $sqlWhere .= " AND ((o.status=10 AND o.pay_type!='offline') OR (o.offline_status=0 AND o.pay_type='offline'))";
            } elseif ($data['status']==1) {
                $where['_string'] = "(status=1 AND pay_type!='offline') OR (status=1 AND offline_status=1 AND pay_type='offline')";
                $sqlWhere .= " AND ((o.status=1 && o.pay_type!='offline') || (o.status=1 && o.offline_status=1 && o.pay_type='offline'))";
            } elseif ($data['status']==2) {
                $where['status'] = 2;
                $sqlWhere .= ' AND o.status=2';
            } else {
                $where['status'] = $data['status'];
                $sqlWhere .= ' AND o.status='.$data['status'];
            }
        }
        //省市区
        if (isset($data['province_id']) && $data['province_id'] > 0) {
            if ($where['_string']) {
                $where['_string'] .= ' AND (province_id='. $data['province_id'] . ' OR province_id=0)';
            } else {
                $where['_string'] = 'province_id='. $data['province_id'] . ' OR province_id=0';
            }
            $sqlWhere .= ' AND (o.province_id=' . $data['province_id'] . ' OR o.province_id=0)';
        }
        if (isset($data['city_id']) && $data['city_id'] > 0) {
            if ($where['_string']) {
                $where['_string'] .= ' AND (city_id='. $data['city_id'] . ' OR city_id=0)';
            } else {
                $where['_string'] = 'city_id='. $data['city_id'] . ' OR city_id=0';
            }
            $sqlWhere .= ' AND (o.city_id=' . $data['city_id'] . ' OR o.city_id=0)';
        }
        if (isset($data['area_id']) && $data['area_id'] > 0) {
            if ($where['_string']) {
                $where['_string'] .= ' AND (area_id='. $data['area_id'] . ' OR area_id=0)';
            } else {
                $where['_string'] = 'area_id='. $data['area_id'] . ' OR area_id=0';
            }
            $sqlWhere .= ' AND (o.area_id=' . $data['area_id'] . ' OR o.area_id=0)';
        }
        $count = $this->where($where)->count();
        $p = new Page($count, 20);
        if($data['fid']>0){
            $sql = 'SELECT count(*) as num';
            $sql .= ' FROM ';
            $sql .= C('DB_PREFIX') . 'merchant AS m';
            $sql .= ' INNER JOIN ' . C('DB_PREFIX') . 'merchant_store AS s ON s.mer_id=m.mer_id';
            $sql .= ' INNER JOIN ' . C('DB_PREFIX') . 'market_order AS o ON o.sell_store_id=s.store_id';
            $sql .= ' INNER JOIN ' . C('DB_PREFIX') . 'merchant AS b ON o.mer_id=b.mer_id';
            $sql .= ' INNER JOIN ' . C('DB_PREFIX') . 'market_goods AS g ON o.goods_id=g.goods_id';
            $sql .= $sqlWhere;
            $sql .= ' ORDER BY o.pay_time DESC';
            $count = $this->query($sql);
            $p = new Page($count[0]['num'], 20);
        }
        if ($type == 'buy') {
            $sql = 'SELECT o.*, m.name AS merchant_name, m.phone AS merchant_phone, s.name AS store_name, s.phone AS store_phone';
            $sql .= ' FROM ' . C('DB_PREFIX') . 'merchant AS m INNER JOIN ' . C('DB_PREFIX') . 'market_order AS o ON o.sell_mer_id=m.mer_id INNER JOIN ' . C('DB_PREFIX') . 'merchant_store AS s ON s.store_id=o.sell_store_id';
        } elseif ($type == 'sell') {
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
        $sql .= ' LIMIT ' . $p->firstRow . ',' . $p->listRows;
        $orders = $this->query($sql);
        $goods_image_class = new goods_image();
        foreach ($orders as &$order) {
            if(!empty($order['image'])){
                $tmp_pic_arr = explode(';', $order['image']);
                foreach ($tmp_pic_arr as $key => $value) {
                    $order['pic'] || $order['pic'] = $goods_image_class->get_image_by_path($value, 's');
                }
            }
            $order['discount_info_txt'] = L_('无优惠');
            if ($order['discount_info']) {
               $order['discount_info'] = json_decode($order['discount_info'], true);
               $order['discount_info_txt'] = L_("批发满：X1，享受：X2折优惠<br/>",array("X1" => $order['discount_info']['num'] . $order['unit'],"X2" => $order['discount_info']['discount'])); 
            }
            $order['status_txt'] = '';
            switch ($order['status']) {
                case 1:
                    if ($order['pay_type'] == 'offline' && $order['offline_status']==0) {
                        $order['status_txt'] = '<span style="color:red">'.L_('线下支付').L_('（未支付）').'</span>';
                    } elseif ($order['pay_type'] == 'offline' && $order['offline_status']==1) {
                        $order['status_txt'] = '<span style="color:green">'.L_('线下支付').L_('（已支付）').'</span>';
                    } else {
                        $order['status_txt'] = '<span style="color:green">'.L_('已支付').'</span>';
                    }
                    break;
                case 2:
                    $order['status_txt'] = '<span style="color:green">'.L_('已发货').'</span>';
                    break;
                case 3:
                    $order['status_txt'] = '<span style="color:green">'.L_('已收货').'</span>';
                    break;
                case 4:
                    $order['status_txt'] = '<span style="color:red">'.L_('已退款').'</span>';
                    break;
                case 10:
                    $order['status_txt'] = '<span style="color:red">'.L_('未支付').'</span>';
                    break;
            }
            
//             $return = $this->format_spec_value($order['spec_value'], $order['goods_id'], $order['order_id']);
//             $list = isset($return['list']) ? $return['list'] : '';
//             $spec_list = isset($return['spec_list']) ? $return['spec_list'] : '';
//             $order['good_detail'] = '';
//             if ($spec_list) {
//                 $order['good_detail'] .= '<div>';
//                 $order['good_detail'] .= '<span>商品条形码</span>';
//                 foreach ($spec_list as $gs) {
//                     $order['good_detail'] .= '<span>' . $gs['name'] . '</span>';
//                 }
//                 $order['good_detail'] .= '<span>批发价</span>';
//                 $order['good_detail'] .= '<span>本次批发数</span>';
//                 $order['good_detail'] .= '<span>总价</span>';
//                 $order['good_detail'] .= '</div>';
//                 foreach ($list as $gl) {
//                     $order['good_detail'] .= '<div>';
//                     $order['good_detail'] .= '<span>' . $gl['number'] . '</span>';
//                     foreach ($gl['spec'] as $g) {
//                         $order['good_detail'] .= '<span>' . $g['spec_val_name'] . '</span>';
//                     }
//                     $order['good_detail'] .= '<span>' . $gl['price'] . '</span>';
//                     $order['good_detail'] .= '<span>' . $gl['stock_num'] . '</span>';
//                     $order['good_detail'] .= '<span>' . floatval($gl['stock_num'] * $gl['price']) . '</span>';
//                     $order['good_detail'] .= '</div>';
//                 }
//             }
        }
        return array('orders' => $orders, 'totalPage' => $p->totalPage, 'pagebar' => $p->show(), 'first' => $p->firstRow);
    }
    
    public function format_spec_value($str, $goods_id, $order_id)
    {
        if ($str) {
            $spec_obj = M('Market_order_spec'); //规格表
            $spec_value_obj = M('Market_order_spec_value');//规格对应的属性值
            $goods_spec_temp = $spec_obj->field(true)->where(array('goods_id' => $goods_id, 'order_id' => $order_id))->order('id ASC')->select();
            $goods_spec_list = array();
            $specids = array();
            foreach ($goods_spec_temp as $goods_t) {
                $specids[] = $goods_t['id'];
                $goods_spec_list[$goods_t['id']] = $goods_t;
            }
            unset($goods_spec_temp);
            $spec_valuse_list = array();
            if ($specids) {
                $spec_valuse_temp = $spec_value_obj->field(true)->where(array('sid' => array('in', $specids), 'order_id' => $order_id))->order('id ASC')->select();
                foreach ($spec_valuse_temp as $v_temp) {
                    $spec_valuse_list[$v_temp['id']] = $v_temp;
                    $goods_spec_list[$v_temp['sid']]['list'][$v_temp['id']] = $v_temp;
                }
                unset($spec_valuse_temp, $specids);
            }
             
            $return = array();
            $json = array();
            // 		if ($str) {
            $spec_array = explode('#', $str);
            $p_ids = array();
            $is_sort = true;
            $new_goods_spec_list = array();
            foreach ($spec_array as $row) {
                $row_array = explode('|', $row);
                $spec_ids = explode(':', $row_array[0]);
                $t_index = '';
                $t_pre = '';
                $spec_data = array();
                foreach ($spec_ids as $id) {
                    $t_index .= $t_pre . 'id_' . $id;
                    $t_pre = '_';
                    $spec_data[] = array('spec_val_id' => $id, 'spec_val_name' => isset($spec_valuse_list[$id]['name']) ? $spec_valuse_list[$id]['name'] : '');
                    if ($is_sort && isset($spec_valuse_list[$id]['sid']) && isset($goods_spec_list[$spec_valuse_list[$id]['sid']])) {
                        $new_goods_spec_list[] = $goods_spec_list[$spec_valuse_list[$id]['sid']];
                    }
                }
                $is_sort = false;
                $index = implode('_', $spec_ids);
    
                $return[$index]['index'] = $t_index;
                $return[$index]['spec'] = $spec_data;
    
                $prices = explode(':', $row_array[1]);
                $return[$index]['old_price'] = floatval($prices[0]);
                $return[$index]['price'] = floatval($prices[1]);
                $return[$index]['seckill_price'] = floatval($prices[2]);
                $return[$index]['stock_num'] = $prices[3];
                $return[$index]['cost_price'] = isset($prices[4]) ? $prices[4] : 0;
                $return[$index]['group_team_price'] = isset($prices[5]) ? $prices[5] : 0;
                $return[$index]['group_join_price'] = isset($prices[6]) ? $prices[6] : 0;

                $return[$index]['vip_wholesale_price'] = isset($prices[7]) ? $prices[7] : 0;
                $return[$index]['goods_weight'] = isset($prices[8]) ? $prices[8] : 0;
                $return[$index]['goods_volume'] = isset($prices[9]) ? $prices[9] : 0;
    
                $return[$index]['number'] = isset($row_array[3]) ? $row_array[3] : '';
    
                if (isset($row_array[2]) && $row_array[2] && strstr($row_array[2], '=')) {
                    $p_data = array();
                    $tdata = array();
                    $properties = explode(':', $row_array[2]);
                    foreach ($properties as $k => $pro) {
                        $pro_array = explode('=', $pro);
                        $p_data[] = array('id' => intval($pro_array[0]), 'num' => intval($pro_array[1]), 'name' => isset($goods_properties_list[$pro_array[0]]['name']) ? $goods_properties_list[$pro_array[0]]['name'] : '');
                        $tdata['num' . $k . '[]'] = $pro_array[1];
                    }
                    $return[$index]['properties'] = $p_data;
                    $json[$t_index] = $tdata;
                }
                if (empty($return[$index]['number']) && isset($row_array[2]) && $row_array[2] && !strstr($row_array[2], '=')) {
                    $return[$index]['number'] = $row_array[2];
                }
                $json[$t_index]['old_prices[]'] = $prices[0];
                $json[$t_index]['prices[]'] = $prices[1];
                $json[$t_index]['min_num[]'] = $prices[2];
                $json[$t_index]['stock_nums[]'] = $prices[3];
                $json[$t_index]['group_team_price[]'] = $prices[5];
                $json[$t_index]['group_join_price[]'] = $prices[6];
                $json[$t_index]['numbers[]'] = isset($row_array[3]) ? $row_array[3] : '';
            }
        }
    
        $data = array();
        $new_goods_spec_list && $data['spec_list'] = $new_goods_spec_list;
        $return && $data['list'] = $return;
        $json && $data['json'] = $json;
        return $data = $data ? $data : null;
    }

    public function get_pay_order($mer_id, $order_id, $is_web = false)
    {
        $now_order = $this->field(true)->where(array('mer_id' => $mer_id, 'order_id' => $order_id))->find();
        if (empty($now_order)) {
            return array(
                'error' => 1,
                'msg' => L_('当前订单不存在！')
            );
        }

        $order_info = array(
            'order_id' => $now_order['order_id'],
            'order_type' => 'market',
            'recharge_money' => floatval($now_order['money']),
            'order_name' => $now_order['name'],
            'order_num' => $now_order['num'],
            'num' => $now_order['num'],
            'price' => floatval($now_order['money']),
            'money' => floatval($now_order['money']),
            'order_total_money' => floatval($now_order['money'])
        );

        return array(
            'error' => 0,
            'order_info' => $order_info
        );
    }

    public function web_befor_pay($order_info, $now_merchant)
    {
//         $data_user_recharge_order['last_time'] = $_SERVER['REQUEST_TIME'];
//         $data_user_recharge_order['submit_order_time'] = $_SERVER['REQUEST_TIME'];
//         $condition_user_recharge_order['order_id'] = $order_info['order_id'];
//         if (! $this->where($condition_user_recharge_order)->data($data_user_recharge_order)->save()) {
//             return array(
//                 'error_code' => true,
//                 'msg' => '保存订单失败！请重试或联系管理员。'
//             );
//         }
        return array(
            'error_code' => false,
            'pay_money' => $order_info['order_total_money']
        );
    }

    public function after_pay($order_param, $is_pay=false)
    {
        if ($is_pay) {
            $this->market_after_pay($order_param);
        }

        if ($order_param['pay_type'] != '') {
            $where['id'] = $order_param['order_id'];
        } else {
            $where['id'] = $order_param['order_id'];
        }


        $now_order = D('Market_total')->field(true)->where($where)->find();
        
        if (empty($now_order)) {
            return array(
                'error' => 1,
                'msg' => L_('当前订单不存在')
            );
        } elseif ($now_order['status'] != 0) {
            return array(
                'error' => 1,
                'msg' => L_('该订单已付款！'),
            );
        } else {
            // 得到当前商家信息，不将session作为调用值，因为可能会失效或错误。
            $now_merchant = D('Merchant')->get_info($now_order['mer_id']);
            
            if (empty($now_merchant)) {
                return array(
                    'error' => 1,
                    'msg' => L_('没有查找到此订单归属的商家，请联系管理员！')
                );
            }
            
            $data_user_recharge_order = array();
            $data_user_recharge_order['pay_time'] = $_SERVER['REQUEST_TIME'];
            $data_user_recharge_order['payment_money'] = floatval($order_param['pay_money']);
            $data_user_recharge_order['pay_type'] = $order_param['pay_type'];
            $data_user_recharge_order['third_id'] = $order_param['third_id'];
            $data_user_recharge_order['status'] = 1;
            if ($this->where($where)->save($data_user_recharge_order)) {
                $order_param['desc']  = L_('批发在线充值') ;
                $order_param['mer_id']  = $now_order['mer_id'] ;
                $order_param['order_type']  = 'market' ;

                D('SystemBill')->bill_method(0,$order_param);

                // $return = D('Merchant_money_list')->add_money($order_param);
                $now_merchant = D('Merchant')->get_info($now_order['mer_id']);
                if ($now_merchant['money'] > 0 && $now_merchant['status'] == 3) {
                    M('Merchant')->where(array(
                        'mer_id' => $now_order['mer_id']
                    ))->setField('status', 1);
                }
                unset($data_user_recharge_order['payment_money']);
                $data_user_recharge_order['last_time'] = $_SERVER['REQUEST_TIME'];
                $data_user_recharge_order['paid'] = 1;
                $this->where(array('fid' => $now_order['id']))->save($data_user_recharge_order);
                
                D('Scroll_msg')->add_msg('mer_recharge', $now_merchant['uid'], L_('商家【x1】于x2充值成功！',array('x1'=>str_replace_name($now_merchant['name']) ,'x2'=>date('Y-m-d H:i', $_SERVER['REQUEST_TIME']))));
                
                return array(
                    'error' => 0,
                    'msg' => L_('充值成功！'),
//                     'url' => $this->get_pay_after_url($now_order['label'], $now_order['is_mobile_pay'], $now_order)
                );
            } else {
                return array(
                    'error' => 1,
                    'msg' => L_('修改订单状态失败，请联系系统管理员！')
                );
            }
        }
    }


    public function market_after_pay($order_param)
    {
        if ($order_param['pay_type'] != '') {
            $where['orderid'] = $order_param['order_id'];
        } else {
            $where['id'] = $order_param['order_id'];
        }

        $now_order = $this->field(true)->where($where)->find();

        if (empty($now_order)) {
            return array(
                'error' => 1,
                'msg' => L_('当前订单不存在')
            );
        } elseif ($now_order['status'] != 10) {
            return array(
                'error' => 1,
                'msg' => L_('该订单已付款！'),
            );
        } else {
            // 得到当前商家信息，不将session作为调用值，因为可能会失效或错误。
            $now_merchant = D('Merchant')->get_info($now_order['mer_id']);

            if (empty($now_merchant)) {
                return array(
                    'error' => 1,
                    'msg' => L_('没有查找到此订单归属的商家，请联系管理员！')
                );
            }

            $data_order = array();
            $data_order['pay_time'] = $_SERVER['REQUEST_TIME'];
            $data_order['payment_money'] = floatval($order_param['pay_money']);
            $data_order['third_id'] = $order_param['third_id'];
            $data_order['status'] = 1;
            $data_order['is_mobile_pay'] = $order_param['is_mobile'];
            $data_order['pay_type'] = $order_param['pay_type'];
            if ($this->where($where)->save($data_order)) {
                D('Scroll_msg')->add_msg('mer_recharge',$now_merchant['uid'],L_('商家【x1】于x2批发成功！',array('x1'=>str_replace_name($now_merchant['name']),'x2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']))));

                $sms_data = array('mer_id' => $now_merchant['mer_id'], 'store_id' => 0, 'type' => 'market');

                $sellmerchant = M('Merchant')->field('name, phone')->where(array('mer_id' => $now_order['sell_mer_id']))->find();
                $sms_data['uid'] = 0;
                $sms_data['mobile'] = $sellmerchant['phone'];
                $sms_data['sendto'] = 'merchant';
                $sms_data['type'] = 'market';
                $sms_data['content'] = L_('商家X1批发了X2共X3订单号：X4请您注意查看并处理!',array('X1'=>$now_merchant['name'],'X2'=>$now_order['name'],'X3'=>$now_order['num'] . $now_order['unit'],'X4'=>$now_order['order_id']));
                Sms::sendSms($sms_data);

                $sms_data['uid'] = 0;
                $sms_data['mobile'] = $now_merchant['phone'];;
                $sms_data['sendto'] = 'merchant';
                $sms_data['type'] = 'market';
                $sms_data['content'] = L_('您在X1批发了X2共X3订单号：X4已经完成支付。欢迎下次光临!',array('X1'=>$now_merchant['name'],'X2'=>$now_order['name'],'X3'=>$now_order['num'] . $now_order['unit'],'X4'=>$now_order['order_id']));
                Sms::sendSms($sms_data);

                if ($order_param['is_mobile'] == 0) {
                    return array('error' => 0, 'url' => U('merchant/Market/sell_order', array('order_id'=>$now_order['order_id'])));
                }
            } else {
                return array('error' => 1, 'msg' => L_('修改订单状态失败，请联系系统管理员！'));
            }
        }
    }


    public function market_total_after_pay($order_param)
    {
        if ($order_param['pay_type'] != '') {
            $where['orderid'] = $order_param['order_id'];
        } else {
            $where['id'] = $order_param['order_id'];
        }

        $now_order = D('Market_total')->field(true)->where($where)->find();

        if (empty($now_order)) {
            return array(
                'error' => 1,
                'msg' => L_('当前订单不存在')
            );
        } elseif ($now_order['status'] != 0) {
            return array(
                'error' => 1,
                'msg' => L_('该订单已付款！'),
            );
        } else {
            // 得到当前商家信息，不将session作为调用值，因为可能会失效或错误。
            $now_merchant = D('Merchant')->get_info($now_order['mer_id']);
            $pay_type = $order_param['pay_type'];
            if (empty($now_merchant)) {
                return array(
                    'error' => 1,
                    'msg' => L_('没有查找到此订单归属的商家，请联系管理员！')
                );
            }
            $resultList = $this->field(true)->where(array('mer_id' => $now_order['mer_id'], 'fid' => $now_order['fid']))->select();
            $databaseMarketGoods = M('Market_goods');
            $total_num = 0;
            $totalMoney = 0;
            $total_express_fee = 0;
            foreach ($resultList as $marketOrder) {
                $order_id = $marketOrder['order_id'];
                $return = $this->format_spec_value($marketOrder['spec_value'], $marketOrder['goods_id'], $order_id);
                $marketOrder['list'] = isset($return['list']) ? $return['list'] : '';

                $where = array('goods_id' => $marketOrder['goods_id']);
                $marketGoods = $databaseMarketGoods->field(true)->where($where)->find();


                $marketGoodsChageData = array();
                if ($marketOrder['list']) {
                    $return = $databaseMarketGoods->format_spec_value($marketGoods['spec_value'], $marketGoods['goods_id']);
                    $marketGoods['list'] = isset($return['list']) ? $return['list'] : '';
                    $spec_array = explode('#', $marketGoods['spec_value']);
                    foreach ($marketOrder['list'] as $index => $row) {
                        $target_spec_value_array = array();
                        foreach ($spec_array as $str) {
                            $row_array = explode('|', $str);
                            $i = str_replace(':', '_', $row_array[0]);
                            if ($i == $index) {
                                $numArray = explode(':', $row_array[1]);
                                if ($numArray[3]!=-1) {
                                    $numArray[3] = max(0, ($numArray[3] - $row['stock_num']));
                                }
                                $row_array[1] = implode(':', $numArray);
                            }
                            $target_spec_value_array[] = implode('|', $row_array);
                        }
                        $spec_array = $target_spec_value_array;
                        if ($row['stock_num'] > 0) {
                            $total_num += $row['stock_num'];
                        }
                    }
                    $marketGoodsChageData['spec_value'] = implode('#', $spec_array);
                } else {
                    $total_num += $marketOrder['num'];
                }

                $marketGoodsChageData['stock_num'] = max(0, ($marketGoods['stock_num'] - $marketOrder['num']));
                $marketGoodsChageData['sell_count'] = $marketGoods['sell_count'] + $marketOrder['num'];

                // 当前线上支付都会支付所有
                if ($pay_type && $pay_type != 'offline') {
                    $payment_money = $marketOrder['money'] + $marketOrder['total_express_fee'];
                } else {
                    $payment_money = 0;
                }
                //修改订单状态
                $this->where(array('order_id' => $order_id))->save(array('payment_money' => $payment_money, 'pay_time' => time(), 'status' => 1, 'pay_type' => $pay_type));

                //更改批发市场中商品的库存
                $databaseMarketGoods->where(array('goods_id' => $marketOrder['goods_id']))->save($marketGoodsChageData);

                $sms_data = array('mer_id' => $now_merchant['mer_id'], 'store_id' => 0, 'type' => 'market');

                $sellmerchant = M('Merchant')->field('name, phone')->where(array('mer_id' => $marketOrder['sell_mer_id']))->find();
                $sms_data['uid'] = 0;
                $sms_data['mobile'] = $sellmerchant['phone'];
                $sms_data['sendto'] = 'merchant';
                $sms_data['type'] = 'market';
                $sms_data['content'] = L_('商家X1批发了X2共X3订单号：X4请您注意查看并处理!',array('X1'=>$now_merchant['name'],'X2'=>$marketOrder['name'],'X3'=>$marketOrder['num'] . $marketOrder['unit'],'X4'=>$marketOrder['order_id']));
                Sms::sendSms($sms_data);

                $sms_data['uid'] = 0;
                $sms_data['mobile'] = $now_merchant['phone'];;
                $sms_data['sendto'] = 'merchant';
                $sms_data['type'] = 'market';
                $sms_data['content'] = L_('您在X1批发了X2共X3订单号：X4已经完成支付。欢迎下次光临!',array('X1'=>$now_merchant['name'],'X2'=>$marketOrder['name'],'X3'=>$marketOrder['num'] . $marketOrder['unit'],'X4'=>$marketOrder['order_id']));
                Sms::sendSms($sms_data);
            }
            // 支付成功 查看是否存在推广者， 添加推广者分佣 添加开关控制
            if (C('config.merchant_recommend_wholesale_switch') == 1 && $now_merchant['mer_spread_code'] && C('config.merchant_recommend_spread_ratio')>0) {
                $spread_merchant = M('Merchant')->where(array('mer_code' => $now_merchant['mer_spread_code'], 'mer' => array('neq', $now_merchant['mer_id'])))->find();
                if ($spread_merchant) {
                    $merchant_recommend_spread_ratio = floatval(C('config.merchant_recommend_spread_ratio') * 0.01);
                    // 分佣不计算 配送费
                    $totalMoney = $totalMoney - $total_express_fee;

                    // 计算分佣金额
                    $spread_money = $totalMoney * $merchant_recommend_spread_ratio;
                    $spread_money = round($spread_money, 2); // 分佣金额

                    $desc = L_('您推广的X1商户在批发市场消费X2,您获得分佣',array('X1'=>$now_merchant['name'],'X2'=>$totalMoney.C('config.Currency_txt')));
                    $order_info['money'] = $spread_money;
                    $order_info['order_type'] = 'market_spread';
                    $order_info['mer_id'] = $spread_merchant['mer_id'];
                    $order_info['order_id'] = $now_order['fid'];
                    $order_info['desc'] = $desc;
                    if ($total_num > 0) {
                        $order_info['num'] = $total_num;
                    }
                    D('Merchant_money_list')->add_money($order_info);
                    // 同步记录到记录表格中
                    $order_info['spread_mer_id'] = $now_merchant['mer_id'];
                    $order_info['spread_mer_name'] = $now_merchant['name'];
                    $order_info['spread_time'] = $now_merchant['reg_time'];
                    $order_info['add_time'] = time();
                    $info = M('Merchant_recommend_merchant_record')->data($order_info)->add();
                }
            }
            $data_order = array();
            $data_order['pay_time'] = $_SERVER['REQUEST_TIME'];
            $data_order['payment_money'] = floatval($order_param['pay_money']);
            $data_order['pay_type'] = $order_param['pay_type'];
            $data_order['third_id'] = $order_param['third_id'];
            $data_order['status'] = 1;
            if (D('Market_total')->where(array('id' => $now_order['id']))->save($data_order)) {
                D('Scroll_msg')->add_msg('mer_recharge',$now_merchant['uid'],L_('商家【x1】于x2批发成功！',array('x1'=>str_replace_name($now_merchant['name']),'x2'=>date('Y-m-d H:i',$_SERVER['REQUEST_TIME']))));

                $order = array();
                $order['pay_time'] = $_SERVER['REQUEST_TIME'];
                $order['third_id'] = $order_param['third_id'];
                $order['status'] = 1;
                $order['is_mobile_pay'] = $order_param['is_mobile'];
                $order['pay_type'] = $order_param['pay_type'];
                $where = array('fid' => $now_order['id']);
                $this->where($where)->save($order);
                if ($order_param['is_mobile'] == 0) {
                    return array('error' => 0, 'url' => U('merchant/Market/sell_order', array('order_id'=>$now_order['order_id'])));
                }
            } else {
                return array(
                    'error' => 1,
                    'msg' => L_('修改订单状态失败，请联系系统管理员！')
                );
            }
        }
    }

    //获取失败拼团订单
    public function get_fail_group_list(){
        //把过期的拼团状态为进行中的更新为失败拼团
        $now_time=time();
        $where=array();
        $where['te.status']=0;
        $where['_string']="te.start_time+gd.group_affect_time<".$now_time;
        $fail_arr=M('Market_goods')->alias('gd')->join('inner join '.C('DB_PREFIX').'market_group_team te on gd.mid=te.group_id')->join('inner join '.C('DB_PREFIX').'market_group_mer mer on te.id=mer.team_id')->where($where)->group('te.id')->field('te.*')->select();
        foreach ($fail_arr as $k => $v) {
            $data=array();
            $data['id']=$v['id'];
            $data['update_time']=$now_time;
            if($v['complete_num']<=$v['num']){
                $data['status']=1;
                D("Market_goods")->send_group_msg($v['sell_mer_id'],$v['id']);
            }else{
                $data['status']=2;
            }
            M('Market_group_team')->data($data)->save();
        }
        //获取失败的拼团订单
        $where2=array();
        $where2['ord.status']=1; //支付成功的订单
        $where2['te.status']=2;
        $order_list=M('Market_group_team')->alias('te')->join('inner join '.C('DB_PREFIX').'market_group_mer mer on te.id=mer.team_id')->join('inner join '.C('DB_PREFIX').'market_order ord on mer.order_id=ord.order_id')->where($where2)->field('ord.*')->select();
        return $order_list;
    }

    //返还订单金额和商品库存
    public function market_order_refund($now_order){
        if($now_order['status']!=1){
            return array('error'=>1,'msg'=>L_('订单状态错误！'));
        }
        $return=$this->format_spec_value($now_order['spec_value'],$now_order['goods_id'],$order_id);
        $now_order['list']=isset($return['list'])?$return['list']:'';
        $goods_info=D('Market_goods')->where(array('goods_id'=>$now_order['goods_id']))->find();
        if(empty($goods_info)){
            return array('error'=>1,'msg'=>L_('商品信息错误！'));
        }
        $goods_data=array();
        if($now_order['list']){
            $return=D('Market_goods')->format_spec_value($goods_info['spec_value'],$goods_info['goods_id']);
            $goods_info['list']=isset($return['list'])?$return['list']:'';
            $spec_arr=explode('#', $goods_info['spec_value']);
            foreach($now_order['list'] as $k=>$v){
                $arr=array();
                foreach($spec_arr as $spec_str){
                    $row_arr=explode('|', $spec_str);
                    $key=str_replace(':', '_', $row_arr['0']);
                    if($k==$key){
                        $num_arr=explode(':', $row_arr['1']);
                        $num_arr['3']=max(0,($num_arr['3']+$v['stock_num']));
                        $row_arr['1']=implode(':', $num_arr);
                    }
                    $arr[]=implode('|',$row_arr);
                }
                $spec_arr=$arr;
            }
            $goods_data['spec_value']=implode('#', $spec_arr);
        }
        $goods_data['stock_num']=$goods_info['stock_num']+$now_order['num'];
        $goods_data['sell_count']=$goods_info['sell_count']-$now_order['num'];

        $data=array();
        $data['order_type']='marketcancel';
        $data['desc']=L_("拼团超时自动取消在批发市场已购的商品【X1】时返回的金额",array("X1" => $now_order['name'])); 
        $data['mer_id']=$now_order['mer_id'];
        $data['money']=$now_order['money'];
        $data['order_id']=$now_order['order_id'];
        $data['total_money']=$now_order['money'];
        D('SystemBill')->bill_method(0,$data);

        $data2=array();
        $data2['order_id']=$now_order['order_id'];
        $data2['status']=4;
        $this->data($data2)->save();

        //更改批发市场中商品库存
        $goods_data['mid']=$goods_info['mid'];
        M('Market_goods')->data($goods_data)->save();
        return array('error'=>0,'msg'=>L_('退款成功'));
    }

    // 获取支付方式
    public function pay_method($is_config=false, $is_app=true) {
        $pay_method = array();
        if($is_app && 1==C('config.pay_weixinh5_open') && !empty(C('config.pay_weixinh5_appid')) && !empty(C('config.pay_weixinh5_mchid')) && !empty(C('config.pay_weixinh5_key')) && !empty(C('config.pay_weixinh5_appsecret'))){
            $weixin_h5 =  array (
                'name' => '微信h5支付'
            );
            if ($is_config) {
                $weixin_h5['config'] = array (
                    'pay_weixinh5_open' => C('config.pay_weixinh5_open'),
                    'pay_weixinh5_appid' => C('config.pay_weixinh5_appid'),
                    'pay_weixinh5_mchid' => C('config.pay_weixinh5_mchid'),
                    'pay_weixinh5_key' => C('config.pay_weixinh5_key'),
                    'pay_weixinh5_appsecret' => C('config.pay_weixinh5_appsecret')
                );
            }
            $pay_method['weixinh5'] = $weixin_h5;
        }
        if (1==C('config.pay_market_offline')) {
            $offline =  array (
                'name' => '线下支付'
            );
            if ($is_config) {
                $weixin_h5['config'] = array (
                    'pay_market_offline' => C('config.pay_market_offline')
                );
            }
            $pay_method['offline'] = $offline;
        }
        return $pay_method;
    }

    // 批发市场-获取地址信息对应的配送费
    public function market_express_template_fee($province_id, $buy_nums, $info, $is_pick_in_store=1) {
        $express_fee = null;
        $total_express_fee = 0; // 总计配送费
        $total_num = 0; // 总计数-件数、重量、体积
        $where = array(
            'tid' => $info['express_template_id']
        );
        if ($is_pick_in_store==1) {
            $where['type'] = 1;
        } elseif ($is_pick_in_store==2) {
            $where['type'] = 2;
        } else {
            $info['total_express_fee'] = $total_express_fee;
            return $info;
        }
        if ($templates = M('Market_express_template_msg')->field(true)->where($where)->find()) {
            $freight_type = $templates['freight_type'];
            // 模板相关信息
            $return = array();
            if ($templates['id']) {
                $sql = "SELECT v.*, a.area_id FROM " . C('DB_PREFIX') . "market_express_template_value AS v INNER JOIN " . C('DB_PREFIX') . "market_express_template_area AS a ON v.id=a.vid WHERE v.tid = ({$templates['tid']}) AND v.tmid = ({$templates['id']}) ORDER BY v.id DESC";
                $list = $this->query($sql);
                if ($info['store_id']) {
                    $store = D('Merchant_store')->field(true)->where(array('store_id' => $info['store_id']))->find();
                    $this_city = $store['province_id'];
                }
                foreach ($list as $row) {
                    if ($return && $return[$row['tid']] && $return[$row['tid']][$row['area_id']]) {
                        continue;
                    } elseif ($row['area_id'] == 0) {//同城
                        $return[$row['tid']][$this_city] = $row;
                    } else {
                        $return[$row['tid']][$row['area_id']] = $row;
                    }
                }
            }
            foreach ($return as $k => $value) {
                if ($k == $templates['tid']) {
                    $value['freight_type'] = $templates['freight_type'];
                }
                $return[$k] = $value;
            }
            // 计算当前运费
            $express_msg = array();
            if ($province_id) {
                $msg = $return[$templates['tid']];
                if ($msg[$province_id]) {
                    $express_msg = $msg[$province_id];
                } else {
                    $express_fee =  $info['express_fee'];
                }
            }
            // 计算各配送费
            if ($express_msg) {
                if ($info['list'] && $info['spec_list']) {
                    $spec_list = $info['list'];
                    $num = 0;
                    $parameter_total = 0;
                    $total_weight = 0;
                    foreach ($spec_list as $key=>$val) {
                        if (2==$freight_type) {
                            $parameter_key = $val['goods_weight'];
                        } elseif (3==$freight_type) {
                            $parameter_key = $val['goods_volume'];
                        } elseif (1==$freight_type) {
                            $parameter_key = 1;
                        } elseif (!$val) {
                            return array('error'=> true, 'err_msg' => '所选的规格信息错误，请重新选择！');
                        }
                        if ($parameter_key<=0) {
                            $parameter_key = 1;
                        }
                        $num_key = $buy_nums[$val['index']];
                        $num += $num_key;
                        $parameter_total += $parameter_key;
                        $total_weight += $num_key * $parameter_key;
                    }
                    $total_num = $total_weight;
                    $express_fee = $this->express_fee_count($express_msg, $num, $parameter_total, $freight_type, $total_weight);
                    $total_express_fee = $express_fee;
                } else {
                    if (2==$freight_type && $info) {
                        $parameter = $info['goods_weight'];
                    } elseif (3==$freight_type && $info) {
                        $parameter = $info['goods_volume'];
                    } elseif (1==$freight_type) {
                        $parameter = 1;
                    }
                    if ($parameter<=0) {
                        $parameter = 1;
                    }
                    $express_fee = $this->express_fee_count($express_msg, $buy_nums, $parameter, $freight_type);
                    $total_express_fee = $express_fee;
                    $total_num = $parameter * (is_array($buy_nums) ? count($buy_nums) : $buy_nums);
                }
                $express_msg['total_num'] = $total_num;
                $express_msg['freight_type'] = $templates['freight_type'];
                $express_msg['type'] = $is_pick_in_store;
                $info['total_express_fee'] = $total_express_fee;
                $info['express_msg'] = $express_msg;
            } else {
                $info['total_express_fee'] = $express_fee;
                $info['express_msg'] = array();
            }
        }
        return $info;
    }

    /**
     * 配送费计算
     * @param array $template
     * @param int $num
     * @param int $parameter 单个商品的重量或者体积
     * @param int $freight_type
     * @param int $total_weight
     * @return float|int
     */
    protected function express_fee_count($template,$num, $parameter=1,$freight_type, $total_weight=0){
        if(empty($template)||empty($num)) return 0;
        if(empty($template['first_freight'])){//这个是老数据，不支持首件+续件的计费方式。
            return $template['freight'];
        }
		
		if(is_array($num)){
			$num = count($num);
		}
		
        if ($total_weight<=0) {
            $total_weight = $num * $parameter;
        }
        if(1 == $freight_type && $num<=$template['first_weight']){
            return $template['first_freight'];
        } elseif(2 == $freight_type && $total_weight<=$template['first_weight']){
            return $template['first_freight'];
        } elseif(3 == $freight_type){
            return $total_weight * $template['freight'];
        }
        //运费计算公式：首件运费+续件运费（向上取整）
        if(1 == $freight_type && $num<$template['first_weight']){
            $fee = $template['first_freight']+floatval(($num-$template['first_weight'])/$template['add_weight'])*$template['add_freight'];
        } else {
            $fee = $template['first_freight']+floatval(($total_weight-$template['first_weight'])/$template['add_weight'])*$template['add_freight'];
        }
        return $fee;
    }
}