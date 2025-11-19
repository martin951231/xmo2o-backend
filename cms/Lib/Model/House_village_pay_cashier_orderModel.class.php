<?php
class House_village_pay_cashier_orderModel extends Model{
	/*得到已付款列表*/
	public function get_limit_list_page($column,$pageSize=20,$isSystem=false){
		if(!$column['village_id']){
			return null;
		}        

    	$condition_table  = array(C('DB_PREFIX').'house_village_pay_cashier_order'=>'o',C('DB_PREFIX').'house_village_user_bind'=>'b');
    	$condition_where = " `o`.`village_id` = `b`.`village_id`  AND `o`.`pigcms_id` = `b`.`pigcms_id`  AND `o`.`village_id` =".$column['village_id'];

    	if($column['pigcms_id']){
            $condition_where .= " AND `o`.`pigcms_id`=".intval($column['pigcms_id']);
        }

        // if($column['paid']){
        //     $condition_where .= " AND `o`.`paid`=".intval($column['paid']); 
        // }
        
        $condition_where .= " AND `o`.`paid`=1";

        
        if ($column['name']) {
            $condition_where .= " AND REPLACE(`b`.`name`,' ','') like '%".str_replace(' ','',$column['name'])."%'";
        }  

        if($column['usernum']){
            $condition_where .= " AND (`b`.`usernum` like '%{$column['usernum']}%' OR `b`.`bind_number` like '%{$column['usernum']}%')";
        }

        if($column['pay_type'] == 1){//线上支付
            $condition_where .= " AND `o`.`pay_type`=0";
        }elseif($column['pay_type'] == 2){//线下 支付
            $condition_where .= " AND `o`.`pay_type`<>0";

        }

        if($column['phone']){
            $condition_where .= " AND `b`.`phone` like '%".$column['phone']."%'";
        }
        
        if($column['pay_time_str']){
    		$condition_where .= " AND ".$column['pay_time_str'];
    	}
    	
    	$condition_field = '`b`.`name` AS `username` ,b.*,o.*';
    	$order = ' `o`.`cashier_id` DESC';
    	if($isSystem){
    		import('@.ORG.system_page');
    	}else{
    		import('@.ORG.merchant_page');
    	}
    	$count_order = D('')->table($condition_table)->where($condition_where)->count();
    	$p = new Page($count_order,$pageSize,'page');
    	$order_list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();
        if ($order_list) {
            $sing_arr = M('House_village_single')->where(array('village_id' => $column['village_id']))->getField('id,single_name');
            foreach($order_list as &$val) {
                if($val['floor_id']){
                    $floor_info = D('House_village_floor')->field('floor_name,floor_layer')->where(array('floor_id'=>$val['floor_id']))->find();
                }
                $word_data = array();
                $single_id = $val['single_id'] ? $val['single_id'] : ($floor_info['single_id']?$floor_info['single_id']:0);
                if ($sing_arr[$single_id] && $single_id>0) {
                    $sing_name = $sing_arr[$single_id];
                } else {
                    $sing_name = '';
                }
                $word_data['layer'] = $val['layer_num'];
                $word_data['room'] = $val['room_addrss'];
                if (!empty($floor_info)) {
                    $word_data['floor_name'] = $floor_info['floor_name'];
                    if (!$sing_name) {
                        $sing_name = $floor_info['floor_layer'];
                    }
                }
                $word_data['single_name'] = $sing_name;
                $room_diy_name = D('House_village')->word_replce_msg($word_data,$column['village_id']);
                $val['address'] = $room_diy_name;
            }
        } else {
            $order_list = array();
        }

        $condition_where .= " AND `o`.`order_status`=1";
    	$total = D('')->field(' SUM(`o`.`money` ) AS totalMoney ')->table($condition_table)->where($condition_where)->find();
    	
    	$return['pagebar'] = $p->show();
    	$return['order_list'] = $order_list;
    	$return['totalMoney'] = $total;
    	return $return;
	}

    /*得到订单详情*/
    public function get_cashier_detail($column,$pageSize=20,$isSystem=false){
        if(!$column['village_id']||!$column['cashier_id']){
            return null;
        }        

        $condition_table  = array(C('DB_PREFIX').'house_village_pay_order'=>'o',C('DB_PREFIX').'house_village_user_bind'=>'b');
        $condition_where = " `o`.`village_id` = `b`.`village_id`  AND `o`.`bind_id` = `b`.`pigcms_id`  AND `o`.`village_id` =".$column['village_id'];

        if($column['pigcms_id']){
            $condition_where .= " AND `o`.`bind_id`=".intval($column['pigcms_id']);
        }

        if($column['cashier_id']){
            $condition_where .= " AND `o`.`cashier_id`=".intval($column['cashier_id']);
        }
        
        $condition_field = '`b`.`name` AS `username` ,b.*,o.*';
        $order = ' `o`.`order_id` DESC';
        if($isSystem){
            import('@.ORG.system_page');
        }else{
            import('@.ORG.merchant_page');
        }
        $count_order = D('')->table($condition_table)->where($condition_where)->count();
        $p = new Page($count_order,$pageSize,'page');
        $order_list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->order($order)->limit($p->firstRow.','.$p->listRows)->select();


        $total = D('')->field(' SUM(`o`.`money` ) AS totalMoney ')->table($condition_table)->where($condition_where)->find();
        
        $return['pagebar'] = $p->show();
        $return['order_list'] = $order_list;
        $return['totalMoney'] = $total;
        return $return;
    } 

    /*得到打印详情*/
    public function get_cashier_print_detail($column){
        if(!$column['village_id']||!$column['cashier_id']){
            return null;
        }    

        //查询订单
        $cashier_info = $this->get_one($column['cashier_id']);

        //小区信息
        $village_info = D('House_village')->where(array('village_id'=>$column['village_id']))->find();
        $cashier_info['village_name'] = $village_info['village_name'];

        //收款人
        if ($cashier_info['role_id']) {
            $role_info = D('House_admin')->get_info_one(array('id'=>$cashier_info['role_id']));
            $cashier_info['payee'] = $role_info['realname'] ? $role_info['realname'] : $role_info['account'];
        }else{
            $cashier_info['payee'] =  $village_info['property_name'];
        }

        //业主信息
        $user_info = D('house_village_user_bind')->where(array('pigcms_id'=>$cashier_info['pigcms_id']))->find();
        //楼层信息
        $floor_where['floor_id'] = $user_info['floor_id'];
        $floor_where['status'] = 1;
        $database_house_village_floor = D('House_village_floor');
        $floor_info = $database_house_village_floor->house_village_floor_detail($floor_where);

        $floor_info = $floor_info['detail'];
        $cashier_info['username'] = $user_info['name'];
        $cashier_info['payer'] = $user_info['name'];
        $cashier_info['phone'] = $user_info['phone'];
        $cashier_info['usernum'] = $user_info['usernum'];
        //$cashier_info['room_num'] = $floor_info['floor_name'].$floor_info['floor_layer']  .$user_info['layer_num'].$user_info['room_addrss'];
        $cashier_info['room_num'] = D('House_village')->get_single_floor_room($floor_info['single_id'],$floor_info['floor_id'],$user_info['layer_id'],$user_info['vacancy_id'],$column['village_id']);
        // $cashier_info['room_num'] = $user_info['address'];
        $cashier_info['time'] = date('Y-m-d H:i:s' ,$cashier_info['time']);
        $cashier_info['pay_time'] = date('Y-m-d H:i:s' ,$cashier_info['pay_time']);
        $cashier_info['print_time'] = date('Y-m-d H:i:s' ,time());

        // 房屋面积  $user_info['vacancy_id']
        $housesize = D('house_village_user_vacancy')->where(array('pigcms_id' => $user_info['vacancy_id']))->field('housesize')->find();
        $cashier_info['housesize'] = $housesize['housesize'].' ㎡';

        //收款方式
        if ($cashier_info['pay_type']) {
            $pay_type = D('house_village_pay_type')->where(array('id'=>$cashier_info['pay_type']))->find();
            $cashier_info['pay_type_name'] = $pay_type['name'];
        }else{
            $cashier_info['pay_type_name'] = '扫码支付';
        }
        //查询详细订单
        $condition_table  = array(C('DB_PREFIX').'house_village_pay_order'=>'o',C('DB_PREFIX').'house_village_user_bind'=>'b');
        $condition_where = " `o`.`village_id` = `b`.`village_id`  AND `o`.`bind_id` = `b`.`pigcms_id`  AND `o`.`village_id` =".$column['village_id'];

        if($column['cashier_id']){
            $condition_where .= " AND `o`.`cashier_id`=".intval($column['cashier_id']);
        }
        
        $condition_field = '`b`.`name` AS `username` ,b.*,o.*';
        $order = ' `o`.`order_id` ASC';
       
//        $p = new Page($count_order,$pageSize,'page');
        $order_list = D('')->field($condition_field)->table($condition_table)->where($condition_where)->order($order)->select();

        if ($order_list) {
            // 记录下总金额
            $total = 0;
            $cashier_money = $cashier_info['money'];
            foreach ($order_list as &$value) {
                //收款时间
                $value['pay_time'] = date('Y-m-d H:i:s' ,$value['pay_time']);
                //优惠
                if ($value["presented_property_month_num"]&&$value["diy_type"] == 0) {
                    $value['discount'] = $value["presented_property_month_num"].'个月';
                } elseif($value["diy_type"] == 1) {
                    $value['discount'] = $value["diy_content"];
                }else{
                    $value['discount'] = '';
                }
                //服务时间
                if ($value["order_type"] == 'custom_payment') {
                    $value['property_time_str'] = $value["property_time_str"];
                }
                //缴费周期
                $value['service_cycle'] = '';
                if ($value["property_month_num"]) { //物业服务周期
                    $value['service_cycle'] = $value["property_month_num"].'个月';
                }elseif($value["order_type"] == 'custom_payment'){ //自定义缴费周期
                    $value['service_cycle'] = $value["payment_paid_cycle"].'/周期';
                }
                if($value['cashier_id']){
                    $cashier_info1 = D('House_village_pay_cashier_order')->get_one($value['cashier_id']);
                    $cashier_order_list = M('House_village_pay_order')->where(['cashier_id'=>$value['cashier_id']])->select();
                    $order_all_money = 0;
                    foreach ($cashier_order_list as $v){
                        $order_all_money += $v['money'];
                    }
                    if($order_all_money != 0){
                        $value['real_money'] = sprintf("%.2f",($value['money']/$order_all_money)* $cashier_info1['money']);
                    }else{
                        $value['real_money'] = '0.00';
                    }
                }else{
                    $value['real_money'] = $value['money'];
                }

                $total += floatval($value['money']);
                //楼层信息
                $floor_where['floor_id'] = $value['floor_id'];
                $floor_where['status'] = 1;

                //房号
//                $value['room_num'] = $floor_info['floor_name']. $floor_info['floor_layer'] . $value['layer_num'].$value['room_addrss'];
                // $value['room_num'] = $value['address'];
                $value['room_num'] = D('House_village')->get_single_floor_room($value['single_id'],$value['floor_id'],$value['layer_id'],$value['vacancy_id'],$column['village_id']);
                //收费标准
                switch ($value["order_type"]) {
                    case 'property':
                        //$value["price"] = $value['property_fee'];
                        if($value['vacancy_id']){
                            $date_month = M('House_village_property_standard_bind')
                                ->join('AS sb LEFT JOIN '.C('DB_PREFIX').'house_village_property_standard AS s ON s.id = sb.standard_id')
                                ->where(['sb.pigcms_id'=>$value['vacancy_id'],'is_del'=>1])
                                ->getField('s.date_month');
                            if(!empty($date_month)){
                                $date_month = unserialize($date_month);
                                $property_fee = '';
                                foreach ($date_month as $k1=>$v1){
                                    $property_fee = $v1['price'].'元/平方米/月';
                                }
                            }else{
                                $property_fee = '';
                            }
                        }else{
                            $property_fee = '';
                        }
                        $value["price"] = $property_fee;
                        // 开始时间，结束时间
                        $paylist = D('House_village_property_paylist')->where(array('order_id'=>$value['order_id']))->find();
                        if ($paylist && $paylist['start_time']) {
                            $value["start_time"] = date('Y-m-d' ,$paylist['start_time']);
                        }
                        if ($paylist && $paylist['end_time']) {
                            $value["end_time"] = date('Y-m-d' ,$paylist['end_time']);
                        }
                        break;
                    case 'water':
                        $join = 'left join '.C('DB_PREFIX').'meter_read_cate as c on p.cate_id = c.id';
                        $vacancy_id = $value['vacancy_id'];
                        $p_map = [];
                        $p_map['c.cate_name'] = '水费';
                        $p_map['p.village_id'] = $column['village_id'];
                        $p_map['_string']="FIND_IN_SET($vacancy_id,room_id)";
                        $unit_price = D('Meter_read_project')->alias('p')->join($join)->where($p_map)->getField('unit_price');
                        if($unit_price){
                            $value["price"] = $unit_price;
                        }else {
                            if (($floor_info['water_fee'] == '0.00') || (!isset($floor_info['water_fee']))) {
                                // $value["price"] = $village_info['water_price'].C('config.Currency_txt').'/立方米';
                                $value["price"] = $village_info['water_price'];
                            } else {
                                $value["price"] = $floor_info['water_fee'];
                            }
                        }
                        $value["number"] = round($value["money"]/$value["price"],2);
                        break;
                    case 'electric':
                        $join = 'left join '.C('DB_PREFIX').'meter_read_cate as c on p.cate_id = c.id';
                        $vacancy_id = $value['vacancy_id'];
                        $p_map = [];
                        $p_map['c.cate_name'] = '电费';
                        $p_map['p.village_id'] = $column['village_id'];
                        $p_map['_string']="FIND_IN_SET($vacancy_id,room_id)";
                        $unit_price = D('Meter_read_project')->alias('p')->join($join)->where($p_map)->getField('unit_price');
                        if($unit_price){
                            $value["price"] = $unit_price;
                        }else {
                            if (($floor_info['electric_fee'] == '0.00') || (!isset($floor_info['electric_fee']))) {
                                // $value["price"] = $village_info['electric_price'].C('config.Currency_txt').'/千瓦时(度)';
                                $value["price"] = $village_info['electric_price'];
                            } else {
                                $value["price"] = $floor_info['electric_fee'];
                            }
                        }
                        $value["number"] = round($value["money"]/$value["price"],2);
                        break;
                    case 'gas':
                        $join = 'left join '.C('DB_PREFIX').'meter_read_cate as c on p.cate_id = c.id';
                        $vacancy_id = $value['vacancy_id'];
                        $p_map = [];
                        $p_map['c.cate_name'] = '燃气费';
                        $p_map['p.village_id'] = $column['village_id'];
                        $p_map['_string']="FIND_IN_SET($vacancy_id,room_id)";
                        $unit_price = D('Meter_read_project')->alias('p')->join($join)->where($p_map)->getField('unit_price');
                        if($unit_price){
                            $value["price"] = $unit_price;
                        }else {
                            if (($floor_info['gas_fee'] == '0.00') || (!isset($floor_info['gas_fee']))) {
                                // $value["price"] = $village_info['gas_price'].C('config.Currency_txt').'/立方米';
                                $value["price"] = $village_info['gas_price'];
                            } else {
                                $value["price"] = $floor_info['gas_fee'];
                            }
                        }
                        $value["number"] = round($value["money"]/$value["price"],2);
                        break;
                    case 'park':
                        if(($floor_info['park_fee'] == '0.00') || (!isset($floor_info['park_fee']))){
                           // $value["price"] = $village_info['park_price'].C('config.Currency_txt').'/月';
                           $value["price"] = $village_info['park_price'];
                        }else{
                            $value["price"] = $floor_info['park_fee'];
                        }
                        break;  
                    case 'custom_payment': // 自定义缴费 
                        $payment_standard_bind = D('House_village_payment_standard_bind')->where(array('bind_id'=>$value['payment_bind_id']))->find();
                        $payment_standard = D('House_village_payment_standard')->where(array('standard_id'=>$payment_standard_bind['standard_id']))->find();
                        $payment_standard_info = D('House_village_payment')->where(array('payment_id'=>$payment_standard_bind['payment_id']))->find();

                        // 周期类型
                        $cycle_type = '';
                        switch ($payment_standard['cycle_type']) {
                            case 'M':
                                $cycle_type = $payment_standard['pay_cycle'].'月/周期';
                                break;
                            case 'Y':
                                $cycle_type = $payment_standard['pay_cycle'].'年/周期';
                                break;
                            case 'D':
                                $cycle_type = $payment_standard['pay_cycle'].'天/周期';
                                break;
                        }
                        $value["service_cycle"] = $value['payment_paid_cycle'].'（'.$cycle_type.'）'; //支付周期
                        $value["price"] = $payment_standard['pay_money'];
                        $value["number"] = $payment_standard_bind['metering_mode_val']; //自定义数量
                        // 开始时间，结束时间
                        $paylist = D('House_village_payment_paylist')->where(array('order_id'=>$value['order_id']))->find();
                        if ($paylist && $paylist['start_time']) {
                            $value["start_time"] = date('Y-m-d' ,$paylist['start_time']);
                        }
                        if ($paylist && $paylist['end_time']) {
                            $value["end_time"] = date('Y-m-d' ,$paylist['end_time']);
                        }
                        $value["order_name"] =$payment_standard_info['payment_name'];
                        //客户定制
                        // $value["end_time"] =  $paylist['start_time'] ? date('Y-m-d' ,$paylist['end_time']-86400) : '';
                        break;    
                    case 'custom': // 自定义缴费 
                        $value["price"] = $value["money"];
                        $value["number"] = 1;

                        $pattern = '#【(.*?)】#i';
                        preg_match_all($pattern, $value["order_name"], $matches); 
                        $value["order_name"] = $matches[1][0] ? $matches[1][0] : $value["order_name"];
                       

                        break;
                }
            }
        }
        if ($total) {
            foreach ($order_list as &$vals) {
                $money = floatval($vals['money']);
                if ($money>0) {
                    $vals['real_money'] = sprintf("%.2f",($money/$total)* $cashier_money);
                }
            }
        }
        // $total = D('')->field(' SUM(`o`.`money` ) AS totalMoney ')->table($condition_table)->where($condition_where)->find();
        $cashier_info['totalMoney'] = C('config.Currency_symbol').$cashier_info['money'].'（人民币大写：'.cny($cashier_info['money']).'）';
        $cashier_info['case'] = cny($cashier_info['money']);
        $cashier_info['order_list'] = $order_list;

        return $cashier_info;
    }

	public function get_one($cashier_id){
		if(!$cashier_id){
			return false;
		}
		return $this->where(array('cashier_id'=>$cashier_id))->find();
	}
    
    // 收银台收款
    public function cashier_pay($cashier_id ,$remarks = ''){
        if (!$cashier_id) {
            return array('error_code'=>1,'msg'=>'参数传递出错！');
        }

        $cashier_order = $this->get_one($cashier_id);
        if($cashier_order['paid'] > 0){
            return array('error_code'=>1,'msg'=>'该订单已经支付！');
        }

        $res = $this->where(array('cashier_id'=>$cashier_id))->data(array('paid'=>1,'pay_time'=>time()))->save();
        if (!$res) {
            return array('error_code'=>1,'msg'=>'支付失败！');
        }

        //查询订单
        $order_list = D('House_village_pay_order')->where(array('cashier_id'=>$cashier_id))->select();
        if (!$order_list) {
            return array('error_code'=>1,'msg'=>'没有子订单！');
        }
        fdump_api(['cashier_pay'.__LINE__,$cashier_id,$cashier_order,$order_list],'after_pay/house_village_pay_cashier_pay',1);
        // 跟新订单状态
        $data['paid'] = 1;
        $data['pay_time'] = time();
        $data['pay_type'] = ($cashier_order['pay_type']==0) ? 0 : 1;
        if ($remarks) {
            $data['remarks'] = $remarks;
        }
        foreach ($order_list as $key => $value) {
            $now_order = D('House_village_pay_order')->get_one($value['order_id']);

            switch($now_order['order_type']){
                case 'property':
                    $bind_field = 'property_price';
                    $now_user_info = D('House_village_user_bind')->get_one_by_bindId($now_order['bind_id']);
                    $House_village_property_paylist = D('House_village_property_paylist');
                    $paylist_data['bind_id'] = $now_order['bind_id'];
                    $paylist_data['uid'] = $now_order['uid'];
                    $paylist_data['village_id'] = $now_order['village_id'];
                    $paylist_data['property_month_num'] = $now_order['property_month_num'];
                    $paylist_data['presented_property_month_num'] = $now_order['presented_property_month_num'];
                    $paylist_data['house_size'] = $now_order['house_size'];
                    $paylist_data['property_fee'] = $now_order['property_fee'];
                    $paylist_data['floor_type_name'] = $now_order['floor_type_name'];

                    // $now_pay_info = $House_village_property_paylist->where(array('bind_id'=>$now_order['bind_id']))->order('add_time desc')->find();


                    if($now_user_info['property_endtime']){
                        $paylist_data['start_time'] = $now_user_info['property_endtime']+1;
                        $paylist_data['end_time'] = strtotime("+" .($paylist_data['property_month_num'] + $paylist_data['presented_property_month_num']). " months", $now_user_info['property_endtime'])-1;
                    }else{
                        if($now_user_info['add_time'] > 0){
                            $paylist_data['start_time'] = strtotime(date('Y-m-d 00:00:00',$now_user_info['add_time'])) ;
                            $paylist_data['end_time'] = strtotime("+" .($paylist_data['property_month_num'] + $paylist_data['presented_property_month_num']). " months", strtotime(date('Y-m-d 00:00:00',$now_user_info['add_time'])))-1;
                        }else{
                            $paylist_data['start_time'] = strtotime(date('Y-m-d 00:00:00',time()));
                            $paylist_data['end_time'] = strtotime("+" .($paylist_data['property_month_num'] + $paylist_data['presented_property_month_num']). " months", strtotime(date('Y-m-d 00:00:00',time())))-1;
                        }

                    }
                    $data['month_start_time'] = $paylist_data['start_time'];
                    $data['month_end_time'] = $paylist_data['end_time'];
                    $paylist_data['add_time'] = time();
                    $paylist_data['order_id'] = $now_order['order_id'];

                    $paylist_data['end_time'] = strtotime(date('Y-m-d 23:59:59',$paylist_data['end_time']));
                    fdump_api(['cashier_pay'.__LINE__,$paylist_data],'after_pay/house_village_pay_cashier_pay',1);
                    $House_village_property_paylist->data($paylist_data)->add();
                    
                    //同步物业到期时间 
                    $where['uid'] = $now_order['uid'];
                    $where['village_id'] = $now_order['village_id'];
                    $where['pigcms_id'] = $now_order['bind_id']; 
                    M('House_village_user_bind')->where($where)->save(array('property_endtime'=>$paylist_data['end_time']));
                    $tmp_order['desc'] = '用户交物业费';
                    D('House_face_img')->house_user_face_device_info($now_order['uid'],$now_order['bind_id']);
                    if($now_user_info['property_endtime']!=$paylist_data['end_time']){
                        $database_house_village_user_bind = D('House_village_user_bind');
                        $childrenList = $database_house_village_user_bind->where(array('parent_id'=>$now_order['bind_id'],'status'=>1))->field('pigcms_id,uid')->select();
                        if (!empty($childrenList)) {
                            foreach ($childrenList as $items) {
                                D('House_face_img')->house_user_face_device_info($items['uid'],$items['pigcms_id']);
                            }
                        }
                    }
                    break;
                case 'water':
                    $bind_field = 'water_price';
                    $tmp_order['desc'] = '用户交水费';
                    break;
                case 'electric':
                    $bind_field = 'electric_price';
                    $tmp_order['desc'] = '用户交电费';
                    break;
                case 'gas':
                    $bind_field = 'gas_price';
                    $tmp_order['desc'] = '用户交燃气费';
                    break;
                case 'park':
                    $bind_field = 'park_price';
                    $tmp_order['desc'] = '用户交车位费';
                    $eTime = D('House_village_parking_car')->where(['car_id'=>$now_order['car_id']])->getField('end_time');
                    if(!$eTime){
                        $num = $now_order['property_month_num']+$now_order['presented_property_month_num'];
                        $eTime = strtotime('+'.$num.' month',strtotime(date('Y-m-d 00:00:00',time())))-1;
                        D('House_village_parking_car')->where(['car_id'=>$now_order['car_id']])->save(['start_time'=>strtotime(date('Y-m-d 00:00:00',time())),'end_time'=>$eTime]);
                    }else{
                        $num = $now_order['property_month_num']+$now_order['presented_property_month_num'];
                        $eTime = strtotime('+'.$num.' month',strtotime(date('Y-m-d 23:59:59',$eTime)));
                        D('House_village_parking_car')->where(['car_id'=>$now_order['car_id']])->save(['end_time'=>$eTime]);
                    }
                    $pay_order = D('House_village_pay_order')->where(array('order_id'=>$value['order_id']))->find();
                    $park_config = M('House_village_park_config')->where(array('village_id'=>$pay_order['village_id']))->find();
                    if ($park_config['park_versions'] == 2) {
                        $order_id = $value['order_id'];
                        if($pay_order['car_type'] == 'month_type'){//月租
                            $parking_car_single = D('House_village_parking_car')->where(array('car_id'=>$pay_order['car_id']))->find();
                            $parking_position = D('House_village_parking_position')->where(array('position_id'=>$parking_car_single['car_position_id']))->find();
                            $house_village = D('House_village')->where(array('village_id'=>$pay_order['village_id']))->find();
//                            $channel_number_arr = M('Park_passage')->where(array('village_id'=>$pay_order['village_id']))->getField('channel_number',true);
//                            $channel_number_str = implode(',', $channel_number_arr);
//                            if (!$channel_number_str) {
//                                $channel_number_str = '';
//                            }
                            $park_system = M('Park_system')->where(array('park_id'=>$pay_order['village_id']))->find();
                            $nowtime = date("mdHis");
                            $trade_no = $nowtime . sprintf("%04d", $order_id);
                            // 月租车暂时不支持逗号分隔方式上传 只能一个一个上传
                            if (2!=$parking_position['position_pattern']) {
                                // 固定停车位
                                $park_position_set = [];
                                $park_data['begin_time'] = $parking_position['start_time'] ?  strtotime(date('Y-m-d 00:00:00',$parking_position['start_time'])) : strtotime(date('Y-m-d 00:00:00',time()));
                                $park_data['end_time'] = strtotime(date('Y-m-d 23:59:59',$pay_order['month_end_time']));
                                $car_number_arr = [];
                                $parking_car = D('House_village_parking_car')->where(array('car_position_id'=>$parking_position['position_id']))->select();
                                foreach($parking_car as $vl){
                                    $car_number_arr[] = $vl['province'].$vl['car_number'];
                                }
                                $car_number_str = implode(',', $car_number_arr);
                                if ($car_number_str) {
                                    // 处理下开始时间-开始时间大于当前时间的以当前时间为准
                                    if (intval($park_data['begin_time']) > intval(time())) {
                                        $park_data['begin_time'] = strtotime(date('Y-m-d 00:00:00',time()));
                                    }
                                    $park_position_set['start_time'] = $park_data['begin_time'];
                                    $park_position_set['end_time'] = $park_data['end_time'];

                                    $park_data['park_id'] = $parking_position['village_id'];
                                    $park_data['remark'] = '';
                                    $park_data['car_number'] = $car_number_str;
                                    $park_data['tel'] = '';
                                    $park_data['pid'] = '';

                                    if ($parking_position['card_id']) {
                                        $park_data['operate_type'] = '2';//更新操作
                                        $park_data['update_time'] = time();
                                        $card_id = $parking_position['card_id'];
                                    } else {
                                        $card_id = '1'.sprintf("%09d",$parking_position['position_id']);//月卡会员编号（收费系统唯一编号  是
                                        $park_position_set['card_id'] = $card_id;
                                        $park_data['operate_type'] = '1';//添加操作
                                        $park_data['create_time'] = time();
                                    }
                                    $park_data['card_id'] = $card_id;
                                    $park_data['p_lot'] = strval($parking_position['position_num']);
                                    $park_data['price'] = $pay_order['money'];//实收金额
                                    $park_data['name'] = '车位号：'.$parking_position['position_num'];
                                    $park_data['car_type_id'] = '月租车A';
                                    $park_data['amount_receivable'] = $pay_order['money'];//应收金额
                                    $park_data['channel_id'] = '';//月卡允许通行的通道编号
                                    $park_data['tel'] = $parking_car['car_user_phone'] ? $parking_car['car_user_phone'] : '';
                                    $park_data['address'] = $house_village['village_name'];
                                    $json_data['service_name'] = 'month_member_sync';
                                    $json_data['msg_id'] = createRandomStr(8,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(12,true,true);//是
                                    $json_data['data'] = $park_data;

                                    $post_data['unserialize_desc'] = serialize($json_data);
                                    $post_data['card_id'] = $card_id;
                                    $post_data['status'] = 2;
                                    $post_data['msg_id'] = $json_data['msg_id'];
                                    $post_data['village_id'] = $parking_position['village_id'];
                                    $post_data['park_id'] = $park_system['park_id'];
                                    $post_data['token'] = $park_system['token'];
                                    $post_data['service_name'] = 'month_member_sync';
                                    $post_data['create_time'] = time();
                                    $res = M('Park_total_record')->data($post_data)->add();//月卡会员下发
                                    if($res){
                                        D('House_village_parking_position')->where(array('position_id'=>$parking_position['position_id']))->data($park_position_set)->save();
                                        D('House_village_parking_car')->where(array('car_position_id'=>$parking_position['position_id']))->data($park_position_set)->save();
                                    }
                                } else {
                                    // 打印记录下
                                    fdump(__LINE__.'>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>','api/log/'.date('Ymd').'/park/'.$park_system['park_id'].'/month_type_add_log',true);
                                    fdump($pay_order,'api/log/'.date('Ymd').'/park/'.$park_system['park_id'].'/month_type_add_log',true);
                                    fdump($parking_car_single,'api/log/'.date('Ymd').'/park/'.$park_system['park_id'].'/month_type_add_log',true);
                                    fdump($parking_position,'api/log/'.date('Ymd').'/park/'.$park_system['park_id'].'/month_type_add_log',true);
                                    fdump($parking_car,'api/log/'.date('Ymd').'/park/'.$park_system['park_id'].'/month_type_add_log',true);
                                    fdump(__LINE__.'<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<','api/log/'.date('Ymd').'/park/'.$park_system['park_id'].'/month_type_add_log',true);
                                }
                            } else {
                                if ($park_config['park_sys_type'] && 'D1'==$park_config['park_sys_type']) {
                                    $park_sys_type = 'D1';
                                } else {
                                    $park_sys_type = 'A1';
                                }
                                if (2!=$parking_position['position_pattern']) {
                                    // 固定车位-共同使用一个月卡会员编号
                                    $parking_car = D('House_village_parking_car')->where(array('car_position_id'=>$parking_position['position_id']))->select();
                                    if ($parking_position['card_id']) {
                                        $now_car_id = $parking_position['card_id'];
                                    } else {
                                        $now_car_id = '1'.sprintf("%09d",$parking_position['position_id']);//月卡会员编号（收费系统唯一编号  是
                                        $park_position_set['card_id'] = $now_car_id;
                                    }
                                    $car_group = '3'.sprintf("%06d",$parking_position['position_id']);
                                    $address = $house_village['village_name'] .'车位：'.$parking_position['position_num'];
                                } else {
                                    // 虚拟停车位 每个车都是独立的
                                    $parking_car = D('House_village_parking_car')->where(array('car_id'=>$pay_order['car_id']))->select();
                                    $car_group = '';
                                    $address = $house_village['village_name'];
                                }
                                // 如果是虚拟停车位 对应每一个车生成唯一收费编号
                                $p_lot_number = 1;
                                $parking_car_type_arr = D('House_village_parking_car')->parking_car_type_arr;
                                foreach($parking_car as $k=>$v){
                                    $park_set = [];
                                    $park_data['begin_time'] = $v['start_time'] ?  strtotime(date('Y-m-d 00:00:00',$v['start_time'])) : strtotime(date('Y-m-d 00:00:00',time()));
                                    $park_data['end_time'] = strtotime(date('Y-m-d 23:59:59',$pay_order['month_end_time']));
                                    if (intval($park_data['end_time']) > intval(time())) {
                                        $park_data['begin_time'] = strtotime(date('Y-m-d 00:00:00',time()));
                                    }
                                    $park_set['start_time'] = $park_data['begin_time'];
                                    $park_set['end_time'] = $park_data['end_time'];
                                    if ($park_sys_type!='D1') {
                                        $park_data['park_id'] = $v['village_id'];
                                    }
                                    $park_data['car_number'] = $v['province'].$v['car_number'];
                                    if ($now_car_id) {
                                        $park_position_set['start_time'] = $park_data['begin_time'];
                                        $park_position_set['end_time'] = $park_data['end_time'];
                                    }
                                    if($v['card_id'] && false){	//暂停使用续费操作，全都以编辑车辆下发
                                        // 3.16月卡续费记录下发(停车云)
                                        $service_name = 'month_pay_sync';// 续费操作
                                        if ($now_car_id) {
                                            $card_id = $now_car_id;
                                        } else {
                                            $card_id = $v['card_id'];
                                        }
                                        $park_data['trade_no'] = $trade_no;
                                        $park_data['pay_time']  = $pay_order['pay_time'] ? $pay_order['pay_time'] : time();
                                        $park_data['amount_receivable'] = $pay_order['money'];
                                        $park_data['amount_pay'] = $pay_order['money'];
                                        $park_data['pay_type'] = $pay_order['pay_type']==0?'线上支付':'现金';
                                    }else{
                                        if ($parking_car_type_arr && $v['parking_car_type'] && $parking_car_type_arr[$v['parking_car_type']]) {
                                            $car_type_id = $parking_car_type_arr[$v['parking_car_type']];
                                        } else if($v['temporary_car_type']) {
                                            $car_type_id = $v['temporary_car_type'];
                                        } else {
                                            $car_type_id = '月租车A';
                                        }
                                        // 3.7月卡会员同步（停车云）
                                        $park_data['remark'] = '';
                                        $park_data['pid'] = '';
                                        $service_name = 'month_member_sync';// 添加会员卡操作
                                        if ($now_car_id) {
                                            $card_id = $now_car_id;
                                        } else {
                                            $card_id = '2'.sprintf("%09d",$v['car_id']);//月卡会员编号（收费系统唯一编号  是
                                        }
                                        $park_set['card_id'] = $card_id;

                                        if ($park_sys_type=='D1' && $v['card_id']) {
                                            $park_data['is_edit'] = 1;
                                        } elseif ($park_sys_type!='D1') {
                                            $park_data['operate_type'] =  $v['card_id'] ? '2' : '1';	//1 添加，2 编辑
                                            if ($park_data['operate_type']==1) {
                                                $park_data['create_time'] = time();
                                            } else {
                                                $park_data['update_time'] = time();
                                            }
                                            $park_data['price'] = $pay_order['money'];//实收金额
                                            $park_data['amount_receivable'] = $pay_order['money'];//应收金额
//                                        $park_data['channel_id'] = '';//月卡允许通行的通道编号
                                        }
                                        $park_data['name'] = $v['car_user_name'];
                                        $park_data['car_type_id'] = $car_type_id;
                                        $park_data['tel'] = $v['car_user_phone'] ? $v['car_user_phone'] : '';
                                        $park_data['address'] = $address;
                                        $park_data['p_lot'] = strval($parking_position['position_num']);
                                    }
                                    $park_data['card_id'] = $card_id;
                                    if ($park_sys_type=='D1') {
                                        $res = D('ParkService')->handleMonthCard($v['village_id'], $v['car_id'], $park_data, $v);
                                        D('House_village_parking_car')->where(array('car_id'=>$v['car_id']))->data($park_set)->save();
                                        if ($park_position_set) {
                                            D('House_village_parking_position')->where(array('position_id'=>$parking_position['position_id']))->data($park_position_set)->save();
                                        }
                                    } else {
                                        //2021/2/1 多车多位  修改 start
//                                    if ($car_group) {
//                                        $park_data['car_group'] = intval($car_group);
//                                        $park_data['p_lot_number'] = intval($p_lot_number);
//                                    }
                                        $infos = D('House_village_parking_car')->bind_parking_position($card_id);
                                        if ($infos) {
                                            $park_data['car_group'] = intval($infos['car_group']);
                                            $park_data['p_lot_number'] = intval($infos['p_lot_number']);
                                        }
                                        //2021/2/1 多车多位  修改 end
                                        $json_data['msg_id'] = createRandomStr(8, true, true) . '-' . createRandomStr(4, true, true) . '-' . createRandomStr(4, true, true) . '-' . createRandomStr(4, true, true) . '-' . createRandomStr(12, true, true);//是
                                        $json_data['data'] = $park_data;
                                        $json_data['service_name'] = $service_name;
                                        $post_data['unserialize_desc'] = serialize($json_data);
                                        $post_data['status'] = 2;
                                        $post_data['card_id'] = $card_id;
                                        $post_data['msg_id'] = $json_data['msg_id'];
                                        $post_data['village_id'] = $v['village_id'];
                                        $post_data['park_id'] = $park_system['park_id'];
                                        $post_data['token'] = $park_system['token'];
                                        $post_data['car_number'] = $park_data['car_number'] ? $park_data['car_number'] : '';
                                        $post_data['service_name'] = $service_name;
                                        $post_data['create_time'] = time();
                                        $res = M('Park_total_record')->data($post_data)->add();//月卡会员下发
                                        if ($res) {
                                            D('House_village_parking_car')->where(array('car_id' => $v['car_id']))->data($park_set)->save();
                                            if ($park_position_set) {
                                                D('House_village_parking_position')->where(array('position_id' => $parking_position['position_id']))->data($park_position_set)->save();
                                            }
                                        }
                                    }
                                }
                            }
                        }elseif($pay_order['car_type'] == 'stored_type'){//储值
                            $house_village = D('House_village')->where(array('village_id'=>$pay_order['village_id']))->find();
                            $address = $house_village['village_name'];

                            $parking_car = D('House_village_parking_car')->where(array('car_id'=>$pay_order['car_id']))->find();
                            $park_system = M('Park_system')->where(array('park_id'=>$pay_order['village_id']))->find();

                            $nowtime = date("mdHis");
                            $trade_no = $nowtime . sprintf("%04d", $order_id);
                            if ($parking_car['stored_card']) {
                                // 储值卡续充
                                $service_name = 'prepay_card_trade_sync';
                                $park_data['trade_no'] = $trade_no;
                                $park_data['card_id'] = $parking_car['stored_card'];
                                $park_data['pay_time'] = $pay_order['pay_time'] ? $pay_order['pay_time'] : time();
                                $park_data['pay_type'] = $pay_order['pay_type']==0?'线上支付':'现金';
                                $park_data['total_price'] = $pay_order['money'];
                                $park_data['car_number'] = $parking_car['province'].$parking_car['car_number'];
                                $park_data['mobile'] = $parking_car['car_user_phone'];
                                $park_data['park_id'] = $park_system['park_id'];
                                $park_data['name'] = $parking_car['car_user_name'];
                                $json_data['service_name'] = $service_name;
                                $json_data['msg_id'] = createRandomStr(8,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(12,true,true);//是
                                $json_data['data'] = $park_data;
                                $post_data['unserialize_desc'] = serialize($json_data);
                                $post_data['park_id'] = $park_system['park_id'];
                                $post_data['car_number'] = $park_data['car_number'];
                                $post_data['token'] = $park_system['token'];
                                $post_data['service_name'] = $service_name;
                                $post_data['village_id'] = $pay_order['village_id'];
                                $post_data['msg_id'] = $json_data['msg_id'];
                                $post_data['stored_card'] = $park_data['card_id'];
                                $post_data['order_id'] = $order_id;
                                $post_data['car_id'] = isset($pay_order['car_id'])?intval($pay_order['car_id']):0;
                                $post_data['create_time'] = time();
                                M('Park_total_record')->data($post_data)->add();//储值卡续充同步
                            } else {
                                $parking_car_type_arr = D('House_village_parking_car')->parking_car_type_arr;
                                // 同步储值卡
                                if ($parking_car_type_arr && $parking_car['parking_car_type'] && $parking_car_type_arr[$parking_car['parking_car_type']]) {
                                    $car_type_id = $parking_car_type_arr[$parking_car['parking_car_type']];
                                } else if($parking_car['temporary_car_type']) {
                                    $car_type_id = $parking_car['store_value_car_type'];
                                } else {
                                    $car_type_id = '储值车A';
                                }
                                $service_name = 'prepay_card_sync';
                                $park_data['operate_type'] = 1;
                                $park_data['create_time'] = time();
                                $park_data['total_price'] = $pay_order['money'];
                                $park_data['car_number'] = $parking_car['province'].$parking_car['car_number'];
                                $park_data['mobile'] = $parking_car['car_user_phone'];
                                $park_data['name'] = $parking_car['car_user_name'];
                                $park_data['park_id'] = $park_system['park_id'];
                                $park_data['remark'] = '储值卡数据下发';
                                $park_data['card_id'] = '3_'.$park_system['park_id'].$parking_car['car_id'];//储值卡编号（收费系统唯一编号）
                                $park_data['car_type_id'] = $car_type_id;
                                $park_data['address'] = $address;
                                $json_data['service_name'] = $service_name;
                                $json_data['msg_id'] = createRandomStr(8,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(4,true,true).'-'.createRandomStr(12,true,true);//是
                                $json_data['data'] = $park_data;
                                $post_data['unserialize_desc'] = serialize($json_data);
                                $post_data['park_id'] = $park_system['park_id'];
                                $post_data['token'] = $park_system['token'];
                                $post_data['service_name'] = $service_name;
                                $post_data['car_number'] = $park_data['car_number'];
                                $post_data['village_id'] = $pay_order['village_id'];
                                $post_data['msg_id'] = $json_data['msg_id'];
                                $post_data['stored_card'] = $park_data['card_id'];
                                $post_data['car_id'] = isset($pay_order['car_id'])?intval($pay_order['car_id']):0;
                                $post_data['create_time'] = time();
                                $id = M('Park_total_record')->data($post_data)->add();//储值卡同步

                                $data_set = [
                                    'stored_card' => $park_data['card_id']
                                ];
                                if ($id) {
                                    D('House_village_parking_car')->where(array('car_id'=>$parking_car['car_id']))->data($data_set)->save();
                                }
                            }
                        }elseif($pay_order['car_type'] == 'temporary_type'){ //临时
                            if ($park_config['park_sys_type'] && 'D1'==$park_config['park_sys_type']) {
                                $park_sys_type = 'D1';
                            } else {
                                $park_sys_type = 'A1';
                            }

                            if ($park_sys_type=='D1') {
                                // d1上报缴费结果
                                $where_temp = [
                                    'car_number' => $pay_order['car_number'],
                                    'order_id' => $pay_order['third_id'],
                                    'is_paid' => 0,
                                ];
                                $house_village_parking_temp = M('House_village_parking_temp')->where($where_temp)->order('id desc')->find();
                                $car_number = $pay_order['car_number'] ? $pay_order['car_number'] : $house_village_parking_temp['car_number'];
                                $param = [];
                                $param['car_number'] = $car_number;
                                $param['money'] = $pay_order['money'];
                                $param['pay_time'] = $pay_order['pay_time'] ? $pay_order['pay_time'] : time();
                                $param['order_id'] = $pay_order['third_id'];
                                $toPark = D('ParkService')->payNoticeToPark($pay_order['village_id'], $param);
                            } else {
                                //临时车处理
                                $park_system = M('Park_system')->where(array('park_id' => $pay_order['village_id']))->find();
                                if ($park_system) {
                                    $house_village_parking_temp = M('House_village_parking_temp')->where(array('car_number' => $pay_order['car_number'], 'token' => $park_system['token']))->order('id desc')->find();
                                    if ($house_village_parking_temp) {

                                        if ($house_village_parking_temp['is_pay_scene'] == 1) {//直付
                                            $nowtime = date("mdHis");
                                            $orderid = $nowtime . sprintf("%04d", $pay_order['order_id']);

                                            $json_data['state'] = 1;
                                            $json_data['order_id'] = $house_village_parking_temp['order_id'];
                                            $json_data['service_name'] = 'outpark';
                                            $json_data['trade_no'] = $orderid;
                                            $json_data['pay_type'] = 'scancode';
                                            $json_data['out_channel_id'] = '1';//通道号暂时写1
                                            $json_data['total'] = floatval($house_village_parking_temp['price']);
                                            $json_data['errmsg'] = '支付成功';
                                            $json_data['pay_time'] = $pay_order['pay_time'] ? $pay_order['pay_time'] : time();//
                                            $json_data['car_number'] = $pay_order['car_number'] ? $pay_order['car_number'] : $house_village_parking_temp['car_number'];
                                            $json_data['arrive_money'] = $house_village_parking_temp['price'];
                                            $json_data['fee'] = 0.00;
                                            $json_data['account_type'] = 1;
                                            $json_data['remark'] = '收到停车费-' . $json_data['car_number'];

                                            $post_data['unserialize_desc'] = serialize($json_data);
                                            $post_data['park_id'] = $park_system['park_id'];
                                            $post_data['token'] = $park_system['token'];
                                            $post_data['service_name'] = 'outpark';
                                            $post_data['order_id'] = $house_village_parking_temp['order_id'] ? $house_village_parking_temp['order_id'] : '';
                                            $post_data['create_time'] = time();
                                            $post_data['car_number'] = $pay_order['car_number'] ? $pay_order['car_number'] : $house_village_parking_temp['car_number'];
                                            $post_data['village_id'] = $pay_order['village_id'];
                                            $res = M('Park_total_record')->data($post_data)->add();
                                        } elseif ($house_village_parking_temp['is_pay_scene'] == 2) {//无牌车直付
                                            $nowtime = date("mdHis");
                                            $orderid = $nowtime . sprintf("%04d", $pay_order['order_id']);
                                            $json_data['state'] = 1;
                                            $json_data['order_id'] = $house_village_parking_temp['order_id'];
                                            $json_data['service_name'] = 'outpark';
                                            $json_data['trade_no'] = $orderid;
                                            $json_data['pay_type'] = 'scancode';
                                            $json_data['out_channel_id'] = '1';//通道号暂时写1
                                            $json_data['total'] = floatval($house_village_parking_temp['price']);
                                            $json_data['errmsg'] = '支付成功';
                                            $json_data['pay_time'] = $pay_order['pay_time'] ? $pay_order['pay_time'] : time();//
                                            $json_data['car_number'] = $pay_order['car_number'] ? $pay_order['car_number'] : $house_village_parking_temp['car_number'];
                                            $json_data['arrive_money'] = $house_village_parking_temp['price'];
                                            $json_data['fee'] = 0.00;
                                            $json_data['account_type'] = 1;
                                            $json_data['remark'] = '收到无牌车停车费-' . $json_data['car_number'];

                                            $post_data['unserialize_desc'] = serialize($json_data);
                                            $post_data['park_id'] = $park_system['park_id'];
                                            $post_data['token'] = $park_system['token'];
                                            $post_data['service_name'] = 'outpark';
                                            $post_data['order_id'] = $house_village_parking_temp['order_id'] ? $house_village_parking_temp['order_id'] : '';
                                            $post_data['create_time'] = time();
                                            $post_data['car_number'] = $pay_order['car_number'] ? $pay_order['car_number'] : $house_village_parking_temp['car_number'];
                                            $post_data['village_id'] = $pay_order['village_id'];
                                            $res = M('Park_total_record')->data($post_data)->add();

                                        } elseif ($house_village_parking_temp['is_pay_scene'] == 0) {//预付
                                            fdump("走第二个", '123456', 1);
                                            $nowtime = date("mdHis");
                                            $orderid = $nowtime . sprintf("%04d", $pay_order['order_id']);

                                            $json_data['car_number'] = $pay_order['car_number'] ? $pay_order['car_number'] : $house_village_parking_temp['car_number'];
                                            $json_data['service_name'] = 'prepay_order';
                                            $json_data['prepay'] = '';//金额 float
                                            $json_data['prepay_type'] = '';
                                            $json_data['pay_channel'] = '0微信';
                                            $json_data['order_id'] = $house_village_parking_temp['order_id'];//车场订单id //跟入场一起
                                            $json_data['query_order_no'] = '';//查询价格编号，查询时收费系统返回的查询价格编号 否
                                            $json_data['park_id'] = $park_system['park_id'];//
                                            $json_data['trade_no'] = $orderid;//
                                            $json_data['query_time'] = $house_village_parking_temp['add_time'];//
                                            $json_data['pay_time'] = $pay_order['pay_time'] ? $pay_order['pay_time'] : time();//
                                            $json_data['arrive_money'] = $house_village_parking_temp['price'];//到账金额
                                            $json_data['fee'] = 0.00;//手续费
                                            $json_data['account_type'] = 1;//
                                            $json_data['remark'] = '收到停车费-' . $json_data['car_number'];

                                            $post_data['unserialize_desc'] = serialize($json_data);
                                            $post_data['park_id'] = $park_system['park_id'];
                                            $post_data['token'] = $park_system['token'];
                                            $post_data['service_name'] = 'prepay_order';
                                            $post_data['order_id'] = $house_village_parking_temp['order_id'] ? $house_village_parking_temp['order_id'] : '';
                                            $post_data['create_time'] = time();
                                            $post_data['car_number'] = $pay_order['car_number'] ? $pay_order['car_number'] : $house_village_parking_temp['car_number'];
                                            $post_data['village_id'] = $pay_order['village_id'];
                                            $res = M('Park_total_record')->data($post_data)->add();
                                        }
                                    }

                                }
                            }
                        }
                    }
                    break;
                case 'custom_payment':
                    $bind_field = '';
                    $tmp_order['desc']= $now_order['order_name'];
                    // 计算缴费时间
                    $bind_payment = M('House_village_payment_standard_bind')->where(array('bind_id'=>$now_order['payment_bind_id']))->find();
                    $payment_standard = M('House_village_payment_standard')->where(array('standard_id'=>$bind_payment['standard_id']))->find();
                    $paylist_data = array();
                    $paylist_data['bind_id'] = $now_order['bind_id'];
                    $paylist_data['uid'] = $now_order['uid'];
                    $paylist_data['village_id'] = $now_order['village_id'];
                    $paylist_data['paid_cycle'] = $now_order['payment_paid_cycle'];

                    switch ($payment_standard['cycle_type']) {
                        case 'Y': // 年
                            // 计算到期时间 = 开始时间 + 已缴时间（收费周期*已缴周期）
                            $start_time = strtotime("+" .($payment_standard['pay_cycle'] * $bind_payment['paid_cycle']  ). " years", strtotime(date('Y-m-d 00:00:00',$bind_payment['start_time'])));
                            $end_time = strtotime("+" .($payment_standard['pay_cycle'] * $paylist_data['paid_cycle'] ). " years", $start_time)-1;
                            break;
                        case 'M': //月
                            $start_time = strtotime("+" .($payment_standard['pay_cycle'] * $bind_payment['paid_cycle']  ). " months",  strtotime(date('Y-m-d 00:00:00',$bind_payment['start_time'])));
                            $end_time = strtotime("+" .($payment_standard['pay_cycle'] * $paylist_data['paid_cycle'] ). " months", $start_time)-1;;
                            break;
                        case 'D': // 日
                            $start_time = strtotime("+" .($payment_standard['pay_cycle'] * $bind_payment['paid_cycle']  ). " days",  strtotime(date('Y-m-d 00:00:00',$bind_payment['start_time'])));
                            $end_time = strtotime("+" .($payment_standard['pay_cycle'] * $paylist_data['paid_cycle'] ). " days", $start_time)-1;

                            break;
                    }
                    $paylist_data['start_time'] = $start_time;
                    $paylist_data['end_time'] = $end_time;
                    $data['month_start_time'] = $paylist_data['start_time'];
                    $data['month_end_time'] = $paylist_data['end_time'];
                    $paylist_data['add_time'] = time();
                    $paylist_data['order_id'] = $now_order['order_id'];
                    D('House_village_payment_paylist')->data($paylist_data)->add();
                    //修改绑定缴费项目
                    M('House_village_payment_standard_bind')->where(array('bind_id'=>$now_order['payment_bind_id']))->setInc('paid_cycle',$now_order['payment_paid_cycle']);
                default:
                    $bind_field = '';
                    $tmp_order['desc']= $now_order['order_name'];
            }
            fdump_api(['cashier_pay'.__LINE__,$data],'after_pay/house_village_pay_cashier_pay',1);
            D('House_village_pay_order')->where(array('order_id'=>$value['order_id']))->data($data)->save();
            //修改绑定缴费项目


            // 欠费更新
            if(!empty($bind_field)){
                $now_user_info = D('House_village_user_bind')->get_one($now_order['village_id'],$now_order['bind_id'],'pigcms_id');

                $data_bind['pigcms_id'] = $now_user_info['pigcms_id'];

                if($now_user_info[$bind_field] - $now_order['money'] >= 0){
                    $data_bind[$bind_field] = $now_user_info[$bind_field] - $now_order['money'];
                }else{
                    $data_bind[$bind_field] = 0;
                }
                $data_bind[$bind_field] = $now_user_info[$bind_field] - $now_order['money'] >= 0 ? $now_user_info[$bind_field] - $now_order['money'] : 0;

                fdump_api(['cashier_pay'.__LINE__,$data_bind],'after_pay/house_village_pay_cashier_pay',1);
                D('House_village_user_bind')->data($data_bind)->save();

            }

            // 小票打印start
            // $printHaddle = new PrintVillage();
            // $printHaddle->printit($order_id);
            // 小票打印end
        }
        return array('error_code'=>0,'msg'=>'提交成功！');
        
    }

    public function get_pay_order($order_id, $uid=0){
        $now_order = $this->get_one($order_id);
        $order_info = array(
                'pay_offline'           => false,         //线下支付
                'pay_merchant_balance'  => false,         //商家余额
                'pay_merchant_coupon'   => false,         //商家优惠券
                'pay_merchant_ownpay'   => false,         //商家自有支付
                'pay_system_balance'    => true,          //平台余额
                'pay_system_coupon'     => false,         //平台优惠券
                'pay_system_score'      => true,          //平台积分抵现
                'order_info'            => $now_order,    //平台积分抵现
        );
        if($now_order['order_type']=='property'){
            $order_info['pay_system_score']=true; 
        }
        $now_village = D('House_village')->get_one($now_order['village_id']);
        if(C('config.open_sub_mchid') && $now_village['open_sub_mchid'] && $now_village['sub_mch_id']>0 ){
            $order_info['pay_merchant_ownpay'] = true;
        }

        if(C('config.open_sub_mchid') && $now_village['open_sub_mchid'] && $now_village['sub_mch_id']>0 && $now_village['sub_mch_discount']==0 ){
            $order_info['pay_system_score'] = false;
        }

        if(C('config.open_sub_mchid') && $now_village['open_sub_mchid'] && $now_village['sub_mch_id']>0 && $now_village['sub_mch_sys_pay']==0 ){
            $order_info['pay_system_balance'] = false;
        }
        if ($now_order['uid'] != $uid) {
            $this->where(array('cashier_id'=>$order_id))->data(array('uid'=>$uid))->save();
            D('House_village_pay_order')->where(array('cashier_id'=>$order_id))->data(array('uid'=>$uid))->save();
        }
        return $order_info;
    }
	
    public function after_pay($order_id,$plat_order_info){
        $cashier_order = $this->get_one($order_id);
        if($cashier_order['village_id']){
            $e_config = D('House_e_config')->where(array('village_id'=>$cashier_order['village_id']))->find();
            if(empty($e_config))
                $auto = false;
            elseif ($e_config['is_open'] == 1 && $e_config['is_tui'] == 1)
                $auto = true;
            else
                $auto = false;
        }else{
            $auto = false;
        }
        fdump_api(['支付回调'.__LINE__,$order_id,$plat_order_info,$cashier_order,$auto],'after_pay/house_village_pay_cashier_order',1);
        // 积分
        if (intval($plat_order_info['system_score']) > 0) { // 使用了积分
            $score_can_get = $cashier_order['score_can_get'];
            fdump_api(['缴纳社区收银台缴费获得积分'.__LINE__,$score_can_get],'after_pay/house_village_pay_cashier_order',1);
            D('User')->add_score($plat_order_info['uid'],$score_can_get,'缴纳社区收银台缴费获得积分');
        }else{
            $user = D('User')->where(array('uid'=>$plat_order_info['uid']))->find();
            $scroe = $this->get_score($order_id,$user,false);
            $score_can_get = $scroe['score_can_get'];
            fdump_api(['缴纳社区收银台缴费获得积分'.__LINE__,$user,$scroe,$score_can_get],'after_pay/house_village_pay_cashier_order',1);
            D('User')->add_score($plat_order_info['uid'],$score_can_get,'缴纳社区收银台缴费获得积分');
        }

        $date_order['pay_time'] = $plat_order_info['pay_time'] ? $plat_order_info['pay_time'] : time();
        $date_order['paid'] = 1;
        $date_order['score_used_count'] = $plat_order_info['system_score'] ? $plat_order_info['system_score'] : 0;
        $date_order['score_can_get'] = $score_can_get ? $score_can_get : 0;
        $date_order['score_deducte'] = $plat_order_info['system_score_money'] ? $plat_order_info['system_score_money'] : 0;

        $this->where(array('cashier_id'=>$order_id))->save($date_order);

        fdump_api(['支付回调'.__LINE__,$date_order],'after_pay/house_village_pay_cashier_order',1);


        $tmp_order = $cashier_order;
        $tmp_order['plat_order_id'] = $plat_order_info['order_id'];
        $tmp_order['score_used_count'] = $date_order['score_used_count'] ? $date_order['score_used_count'] : 0;
        $tmp_order['score_can_get'] =$score_can_get ? $score_can_get : 0;
        $tmp_order['order_id'] = $cashier_order['cashier_id'];
        $tmp_order['is_own'] = $plat_order_info['is_own'];
        $tmp_order['payment_money'] = $plat_order_info['pay_money'];
        $tmp_order['score_deducte'] = $plat_order_info['system_score_money'];
        $tmp_order['balance_pay'] = $plat_order_info['system_balance'];   
        $tmp_order['order_type'] = 'village_pay_cashier';
        $tmp_order['desc'] = '社区收银台缴费';
        if($plat_order_info['is_own']==0) {
            $plat_order_info['is_own'] += 4;
        }

        fdump_api(['社区对账'.__LINE__,$plat_order_info['is_own'],$tmp_order],'after_pay/house_village_pay_cashier_order',1);
        //社区对账
        $res = D('SystemBill')->bill_method($plat_order_info['is_own'],$tmp_order);
        fdump_api(['社区对账'.__LINE__,$res],'after_pay/house_village_pay_cashier_order',1);
        if(!$res['error_code']){
            $this->where(array('cashier_id'=>$order_id))->setField('is_pay_bill',2);
        }
        

        //查询子订单
        $order_list = D('House_village_pay_order')->where(array('cashier_id'=>$order_id))->select();
        if (!$order_list) {
            return array('error_code'=>1,'msg'=>'没有子订单！');
        }
        fdump_api(['子订单'.__LINE__,$order_list],'after_pay/house_village_pay_cashier_order',1);

        // 跟新订单状态
        foreach ($order_list as $key => $now_order) {
            $now_village = D('House_village')->get_one($now_order['village_id']);
            $detail[$key]['goodsname'] = $now_order['order_name'];
            $detail[$key]['price'] = $now_order['money'];
            $detail[$key]['order_id'] = $now_order['order_id'];
            if($now_order['order_type'] == 'property'){
                $user_bind_info = M('House_village_user_bind')->where(['pigcms_id'=>$now_order['bind_id']])->find();
                $start_time = $user_bind_info['property_endtime']?$user_bind_info['property_endtime']:time();
                $end_time = strtotime('+'.($now_order['property_month_num']+$now_order['presented_property_month_num']).' month',$start_time);
                $spec = date('Y-m-d',$start_time) . '至' . date('Y-m-d',$end_time);
            }
            $spec = isset($spec)?$spec:'';

            $date_order['pay_time'] = $plat_order_info['pay_time'];
            $date_order['paid'] = 1;
            $date_order['pay_type'] = ($cashier_order['pay_type']==0) ? 0 : 1;
            $date_order['score_used_count'] = $plat_order_info['system_score'];
            $date_order['score_deducte'] = $plat_order_info['system_score_money'];

            $now_user_info = D('House_village_user_bind')->get_one_by_bindId($now_order['bind_id']);

            switch($now_order['order_type']){
                case 'property':
                    // $now_village = D('House_village')->get_one($now_order['village_id']);

                    $bind_field = 'property_price';
                    $now_user_info = D('House_village_user_bind')->get_one_by_bindId($now_order['bind_id']);
                    $House_village_property_paylist = D('House_village_property_paylist');
                    $paylist_data = array();
                    $paylist_data['bind_id'] = $now_order['bind_id'];
                    $paylist_data['uid'] = $now_order['uid'];
                    $paylist_data['village_id'] = $now_order['village_id'];
                    $paylist_data['property_month_num'] = $now_order['property_month_num'];
                    $paylist_data['presented_property_month_num'] = $now_order['presented_property_month_num'];
                    $paylist_data['house_size'] = $now_order['house_size'];
                    $paylist_data['property_fee'] = $now_order['property_fee'];
                    $paylist_data['floor_type_name'] = $now_order['floor_type_name'];

                    // $now_pay_info = $House_village_property_paylist->where(array('bind_id'=>$now_order['bind_id']))->order('add_time desc')->find();


                    if($now_user_info['property_endtime']){
                        $paylist_data['start_time'] = $now_user_info['property_endtime']+1;
                        $paylist_data['end_time'] = strtotime("+" .($paylist_data['property_month_num'] + $paylist_data['presented_property_month_num']). " months", $now_user_info['property_endtime']);
                    }else{
                        if($now_user_info['add_time'] > 0){
                            $paylist_data['start_time'] = strtotime(date('Y-m-d 00:00:00',$paylist_data['end_time']));
                            $paylist_data['end_time'] = strtotime("+" .($paylist_data['property_month_num'] + $paylist_data['presented_property_month_num']). " months", $paylist_data['start_time'])-1;
                        }else{
                            $paylist_data['start_time'] =strtotime(date('Y-m-d 00:00:00',time()));
                            $paylist_data['end_time'] = strtotime("+" .($paylist_data['property_month_num'] + $paylist_data['presented_property_month_num']). " months",  $paylist_data['start_time'])-1;
                        }

                    }
                    $date_order['month_start_time'] = $paylist_data['start_time'];
                    $date_order['month_end_time'] = $paylist_data['end_time'];
                    $paylist_data['add_time'] = time();
                    $paylist_data['order_id'] = $now_order['order_id'];

                    $paylist_data['end_time'] = strtotime(date('Y-m-d 23:59:59',$paylist_data['end_time']));
                    
                    // $where['uid'] = $now_order['uid'];
                    $where['village_id'] = $now_order['village_id'];
                    $where['pigcms_id'] = $now_order['bind_id']; 
                    M('House_village_user_bind')->where($where)->save(array('property_endtime'=>$paylist_data['end_time']));
                    fdump_api(['子订单'.__LINE__,$paylist_data],'after_pay/house_village_pay_cashier_order',1);
                    $House_village_property_paylist->data($paylist_data)->add();
                    $tmp_order['desc'] = '用户交物业费';
                    // 存在物业时间和家属同步下业主家属信息
                    D('House_face_img')->house_user_face_device_info($now_order['uid'],$now_order['bind_id']);
                    if($now_user_info['property_endtime']!=$paylist_data['end_time']){
                        $database_house_village_user_bind = D('House_village_user_bind');
                        $childrenList = $database_house_village_user_bind->where(array('parent_id'=>$now_order['bind_id'],'status'=>1))->field('pigcms_id,uid')->select();
                        if (!empty($childrenList)) {
                            foreach ($childrenList as $items) {
                                D('House_face_img')->house_user_face_device_info($items['uid'],$items['pigcms_id']);
                            }
                        }
                    }
                    break;
                case 'water':
                    $bind_field = 'water_price';
                    $tmp_order['desc'] = '用户交水费';
                    break;
                case 'electric':
                    $bind_field = 'electric_price';
                    $tmp_order['desc'] = '用户交电费';
                    break;
                case 'gas':
                    $bind_field = 'gas_price';
                    $tmp_order['desc'] = '用户交燃气费';
                    break;
                case 'park':
                    $bind_field = 'park_price';
                    $tmp_order['desc'] = '停车缴费';
                    break;
                case 'custom_payment':
                    $bind_field = '';
                    $tmp_order['desc']= $plat_order_info['order_name'];
                    // 计算缴费时间
                    $bind_payment = M('House_village_payment_standard_bind')->where(array('bind_id'=>$now_order['payment_bind_id']))->find();
                    $payment_standard = M('House_village_payment_standard')->where(array('standard_id'=>$bind_payment['standard_id']))->find();
                    $paylist_data = array();
                    $paylist_data['bind_id'] = $now_order['bind_id'];
                    $paylist_data['uid'] = $now_order['uid'];
                    $paylist_data['village_id'] = $now_order['village_id'];
                    $paylist_data['paid_cycle'] = $now_order['payment_paid_cycle'];

                    switch ($payment_standard['cycle_type']) {
                        case 'Y': // 年
                            // 计算到期时间 = 开始时间 + 已缴时间（收费周期*已缴周期）
                            $start_time = strtotime("+" .($payment_standard['pay_cycle'] * $bind_payment['paid_cycle']  ). " years", $bind_payment['start_time']);
                            $end_time = strtotime("+" .($payment_standard['pay_cycle'] * $paylist_data['paid_cycle'] ). " years", $start_time);;
                            break;
                        case 'M': //月
                            $start_time = strtotime("+" .($payment_standard['pay_cycle'] * $bind_payment['paid_cycle']  ). " months", $bind_payment['start_time']);
                            $end_time = strtotime("+" .($payment_standard['pay_cycle'] * $paylist_data['paid_cycle'] ). " months", $start_time);;
                            break;
                        case 'D': // 日
                            $start_time = strtotime("+" .($payment_standard['pay_cycle'] * $bind_payment['paid_cycle']  ). " days", $bind_payment['start_time']);
                            $end_time = strtotime("+" .($payment_standard['pay_cycle'] * $paylist_data['paid_cycle'] ). " days", $start_time);;
                            break;
                    }
                    $paylist_data['start_time'] = $start_time;
                    $paylist_data['end_time'] = $end_time;
                    $date_order['month_start_time'] = $paylist_data['start_time'];
                    $date_order['month_end_time'] = $paylist_data['end_time'];
                    $paylist_data['add_time'] = time();
                    $paylist_data['order_id'] = $now_order['order_id'];
                    D('House_village_payment_paylist')->data($paylist_data)->add();
                    break;
                default:
                    $bind_field = '';
                    $tmp_order['desc']= $plat_order_info['order_name'];
                    break;
            }
            fdump_api(['子订单'.__LINE__,$date_order],'after_pay/house_village_pay_cashier_order',1);
            D('House_village_pay_order')->where(array('cashier_id'=>$order_id))->save($date_order);
            if(!empty($bind_field)){
                $now_user_info = D('House_village_user_bind')->get_one($now_order['village_id'],$now_order['bind_id'],'pigcms_id');
                $data_bind['pigcms_id'] = $now_user_info['pigcms_id'];
                if($now_user_info[$bind_field] - $now_order['money'] >= 0){
                    $data_bind[$bind_field] = $now_user_info[$bind_field] - $now_order['money'];
                }else{
                    $data_bind[$bind_field] = 0;
                }
                $data_bind[$bind_field] = $now_user_info[$bind_field] - $now_order['money'] >= 0 ? $now_user_info[$bind_field] - $now_order['money'] : 0;
                fdump_api(['子订单'.__LINE__,$data_bind],'after_pay/house_village_pay_cashier_order',1);
                D('House_village_user_bind')->data($data_bind)->save();

            }

            //修改绑定缴费项目已缴周期
            if($now_order['order_type'] == 'custom_payment'){
                M('House_village_payment_standard_bind')->where(array('bind_id'=>$now_order['payment_bind_id']))->setInc('paid_cycle',$now_order['payment_paid_cycle']);
            }
            
            // 对账
            M('House_village_pay_order')->where(array('order_id' => $now_order['order_id']))->setField('is_pay_bill', 2);

        }

        if($auto){
            $order_no = date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
            $now_bind_info['name'] = !empty($now_bind_info['name'])?$now_bind_info['name']:'未知用户';
            $res = $this->addInvoice($now_bind_info['address'].'-'.$now_bind_info['name'],$now_bind_info['phone'],$order_no,$e_config['duty_paragraph'],$detail,$now_village['village_name'],$spec,$now_village['property_id']);
            if($res['status'] == '0000'){
                $this->addERecord($e_config['duty_paragraph'],$now_bind_info['name'],$now_bind_info['phone'],$res['fpqqlsh'],$order_no,$detail,$now_bind_info['pigcms_id']);
            }
        }

        $now_user = D('User')->get_user($cashier_order['uid']);
        if(!empty($now_user['openid'])){
            $href = C('config.site_url').'/wap.php?g=Wap&c=House&a=village_my_paylists&village_id='.$cashier_order['village_id'];
            $model = new templateNews(C('config.wechat_appid'),C('config.wechat_appsecret'));
            $model->sendTempMsg('TM01008', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' =>  ' 缴费成功提醒', 'keynote1' =>'社区收银台缴费', 'keynote2' =>'物业号 '. $now_user_info['usernum'], 'remark' => '缴费时间：'.date('Y年n月j日 H:i',$cashier_order['time']).'\n'.'缴费金额：'.C('config.Currency_symbol').$cashier_order['money']));
        }

        $village_info = D('House_village')->where(array('village_id'=>$cashier_order['village_id']))->find();
        $village_bind = D('House_village_user_bind')->where(array('pigcms_id'=>$cashier_order['pigcms_id']))->find();
        if ($village_bind['single_id'] && $village_bind['floor_id'] && $village_bind['layer_id'] && $village_bind['vacancy_id'] && $village_bind['village_id']) {
            $address = D('House_village')->get_single_floor_room($village_bind['single_id'],$village_bind['floor_id'],$village_bind['layer_id'],$village_bind['vacancy_id'],$village_bind['village_id']);
            if ($address) {
                $village_bind['address'] = $address;
            }
        }
        if($village_info['openid']){
            $property_name = M('House_property')->where(['id'=>$village_info['property_id']])->getField('property_name');
            $village_info['property_name'] = $property_name;
            $href = "";
            $model = new templateNews(C('config.wechat_appid'),C('config.wechat_appsecret'));
            $model->sendTempMsg('TM01008', array('href' => $href, 'wecha_id' => $now_user['openid'], 'first' =>  ' 业主 '.$village_bind['name'].'( '.$village_bind['address'].' )缴费成功提醒', 'keynote1' =>$village_info['property_name'], 'keynote2' =>$village_bind['name']."( ".$village_bind['phone']." )", 'remark' => '社区收银台缴费\n缴费时间：'.date('Y年n月j日 H:i',$cashier_order['time']).'\n'.'缴费金额：'.C('config.Currency_symbol').$cashier_order['money']));
        }

    }

    public function get_order_url($order_id,$is_mobile){
        $now_order = $this->get_one($order_id);
        M('House_village')->where(array('village_id'=>$now_order['village_id']))->setInc('payment_reminder');
        // M('House_village_payment_standard_bind')->where(array('bind_id'=>$now_order['payment_bind_id']))->setInc('paid_cycle',$now_order['payment_paid_cycle']);
        
        // 小票打印start
        $printHaddle = new PrintVillage();
        $printHaddle->printit($order_id);
        // 小票打印end
        $db_house_village = D('House_village');
        $app_version = intval($_POST['app_version']);
        $deviceId = trim($_POST['Device-Id']);
        $param = [
            'pageName' => 'payMentlist',
            'isAll' => true,
            'urlParam' => 'village_id='.$now_order['village_id'].'&no_head=true&current=1',
        ];
        $payMentlistUrl = $db_house_village->villagePagePath($app_version,$deviceId,$param);

        if ($now_order['pigcms_id']>0) {
            $payMentlistUrl .= '&pigcms_id='.$now_order['pigcms_id'];
        }
        return $payMentlistUrl;
    }

    // 计算积分
    #计算可使用积分，获得积分
    public function get_score($order_id,$now_user,$user_score=true){
        $order_info = $this->get_one($order_id);
        // 其他缴费也可获得积分
        $percent = 0;            //计算物业费和抵扣金额
        //判断是否允许使用积分
        $village_config = D('House_village_config')->where(array('village_id'=>$order_info['village_id']))->find();
        //首先得到 积分比例 和 用户总积分
        $system_config = D('Config')->get_config();
        //得到多少积分抵扣一元
        $moneytointegral = $system_config['user_score_use_percent'];
        //得到用户总积分
        $user_score_count = $now_user['score_count'];
        //得到总金额
        $total_price = $order_info['money'];
        //第一步 判断是否允许使用积分 (注意 这里是针对设置了小区本身的缴费配置)
        if($village_config['village_pay_use_integral']==1){ //允许使用积分 针对小区开启
            $percent = $village_config['use_max_integral_percentage'];
            $property_info = $this->get_use_score($total_price ,$village_config['use_max_integral_percentage'] , $village_config['use_max_integral_num'] , $moneytointegral , $user_score_count);
        }elseif($village_config['village_pay_use_integral']==0){ // 继承系统设置
            if($system_config['village_pay_use_integral']==1){
                $percent = $system_config['use_max_integral_percentage'];
                $property_info = $this->get_use_score($total_price ,$system_config['use_max_integral_percentage'] , $system_config['use_max_integral_num'] , $moneytointegral , $user_score_count);
             }else{
                $property_info['use_total_score_count'] = 0;
                $property_info['use_total_money'] = 0;  
            }
        }else{
            $property_info['use_total_score_count'] = 0;
            $property_info['use_total_money'] = 0;
        }
        
        //计算可生成积分多少
        if($village_config['village_pay_integral']==1){ //允许生成积分
            if($village_config['open_score_get_percent']==1){
                // 开启了积分百分比， 取当前小区的积分百分比计算
                $score_get = $village_config['score_get_percent']/100;
            }else{
                // 关闭了积分百分比， 取当前小区的积分计算
                $score_get = $village_config['user_score_get'];
            }
            if ($user_score) { // 使用了积分
                $property_info['generate_integral'] = ($total_price - $property_info['use_total_money']) * $score_get; // 得到生成积分
            }else{ // 未使用积分
                $property_info['generate_integral'] = $total_price * $score_get; // 得到生成积分
            }
        }elseif($village_config['village_pay_integral']==0){ //继承平台设置 是否允许生成积分
            if($system_config['village_pay_integral']==1){ // 平台允许生成积分
                if($system_config['open_score_get_percent']==1){
                    // 开启了积分百分比， 取平台小区的积分百分比计算
                    $score_get = $system_config['score_get_percent']/100;
                }else{
                    // 关闭了积分百分比， 取平台小区的积分计算
                    $score_get = $system_config['user_score_get'];
                }
                if ($user_score) { // 使用了积分
                    $property_info['generate_integral'] = ($total_price - $property_info['use_total_money']) * $score_get; // 得到生成积分
                }else{ // 未使用积分
                    $property_info['generate_integral'] = $total_price * $score_get; // 得到生成积分
                }
            }else{
                $property_info['generate_integral'] = 0; // 得到生成积分
            }
        }elseif($village_config['village_pay_integral']==-1){ //不允许生成积分 
            $property_info['generate_integral'] = 0;    
        }
        
        $data_order['score_can_use'] = $property_info['use_total_score_count'];
        $data_order['score_can_pay'] = $property_info['use_total_money'];
        $data_order['score_percent'] = $percent;
        $data_order['score_can_get'] = $property_info['generate_integral'];
        $this->where(array('cashier_id'=>$order_id))->data($data_order)->save();
        return $data_order;
    }

    // 计算可使用积分
    /****************************************************************
        $total_price 总价格    
        $use_max_integral_percentage 使用最大积分占总额百分比
        $use_max_integral_num 允许使用的最大积分
        $moneytointegral 多少积分抵扣一元
        $user_score_count 用户总共多少积分
    ****************************************************************/
    public function get_use_score($total_price ,$use_max_integral_percentage , $use_max_integral_num , $moneytointegral , $user_score_count){
        if ($user_score_count<=0) {
            $property_info['use_total_score_count'] = 0;
            $property_info['use_total_money'] = 0;          
            return $property_info;
        }
        if($use_max_integral_percentage > 0){ //使用最大百分比
                        
            //得到最大抵扣金额
            $max_use_price = sprintf("%.2f",$total_price * $use_max_integral_percentage / 100);
            
            //计算可以使用多少积分
            $use_total_score_count = floor($max_use_price * $moneytointegral);
            
            //这里计算用户应该支出的积分  和 应该抵扣的钱  和 要支付的钱
            if($user_score_count < $use_total_score_count){
                
                $property_info['use_total_money'] =sprintf("%.2f",$user_score_count/$moneytointegral);
                $property_info['use_total_score_count'] = $property_info['use_total_money'] * $moneytointegral;
                
            }else{
                $property_info['use_total_money'] = $max_use_price;
                $property_info['use_total_score_count'] = $use_total_score_count;
            }
        }elseif($use_max_integral_num > 0){ //使用最大积分
            if($use_max_integral_num < $user_score_count){
                $property_info['use_total_money'] = sprintf("%.2f",$use_max_integral_num / $moneytointegral);
                $property_info['use_total_score_count'] = $property_info['use_total_money'] * $moneytointegral;
            }else{
                $property_info['use_total_money'] = sprintf("%.2f",$user_score_count/$moneytointegral);
                $property_info['use_total_score_count'] = $property_info['use_total_money'] * $moneytointegral;
            }

            if ($property_info['use_total_money']>$total_price) { // 可抵扣金额大于支付金额
                $property_info['use_total_money'] = $total_price;
                $property_info['use_total_score_count'] = $total_price * $moneytointegral;
            }
        }else{ // 否则不计算
            $property_info['use_total_score_count'] = 0;
            $property_info['use_total_money'] = 0; 
        }
        return $property_info;
    }

    /**
     * 提交发票申请
     * @author lijie
     * @date_time 2020/07/21
     * @param $name
     * @param $phone
     * @param $order_no
     * @param $duty_paragraph
     * @param $detail
     * @param $village_name
     * @param $spec
     * @param $property_id
     * @return mixed
     */
    public function addInvoice($name,$phone,$order_no,$duty_paragraph,$detail,$village_name,$spec,$property_id=0)
    {
        if(!$property_id)
            return false;
        $e_config = M('House_property')->where(['id'=>$property_id])->field('identity,house_e_invoice_url,house_e_pdf_url')->find();
        $arr['identity'] = $e_config['identity'];
        $order['buyername'] = $name;
        //$order['taxnum'] = '339901999999142';
        $order['phone'] = $phone;
        $order['order_no'] = $order_no;
        $order['invoicedate'] = date('Y-m-d H:i:s',time());
        $order['clerk'] = 'name';
        $order['saletaxnum'] = $duty_paragraph;
        $order['kptype'] = 1;
        $order['message'] = $village_name;
        foreach ($detail as $k=>$v){
            $order['detail'][$k]['goodsname'] = $v['goodsname'];
            $order['detail'][$k]['hsbz'] = 0;
            $order['detail'][$k]['taxrate'] = 0.13;
            $order['detail'][$k]['spbm'] = 304080101;
            $order['detail'][$k]['fphxz'] = 0;
            $order['detail'][$k]['num'] = 1;
            $order['detail'][$k]['price'] = $v['price'];
            if($spec){
                $order['detail'][$k]['spec']  = $spec;
            }
        }

        $arr['order'] = $order;
        $res = $this->sendInvoice($arr,$e_config['house_e_invoice_url']);
        return $res;
    }

    /**
     * 调开具电子发票接口
     * @author lijie
     * @date_time 2020/07/08
     * @param $order
     * @param $url
     * @return mixed
     */
    public function sendInvoice($order,$url)
    {
        $order = $this->encrypt($order);
        $response = httpRequest($url,'POST',['order'=>$order]);
        return json_decode($response[1],true);
    }

    /**
     * 加密
     * @param $data
     * @return string
     */
    public function encrypt($data)
    {
        $srcKey = base64_decode('LmMGStGtOpF4xNyvYt54EQ==');
        $content = json_encode($data,JSON_UNESCAPED_UNICODE);
        $srcBuf = md5($content,true) . $content;
        // $result = base64_encode(
        //     openssl_encrypt($srcBuf,'DES-CBC',$this->key,0,$this->iv)
        // );
        $result = openssl_encrypt($srcBuf,'DES-CBC',substr($srcKey, 0, 8),0, substr($srcKey,8));
        return $result;
    }

    /**
     * 添加开发票记录
     * @author lijie
     * @date_time 2020/07/21
     * @param $duty_paragraph
     * @param $name
     * @param $phone
     * @param $fpqqlsh
     * @param $order_no
     * @param $detail
     * @param $pigcms_id
     */
    public function addERecord($duty_paragraph,$name,$phone,$fpqqlsh,$order_no,$detail,$pigcms_id)
    {
        $money = 0;
        $order_name = '*';
        foreach ($detail as $k=>$v){
            $money += $v['price'];
            $order_name .= $v['goodsname'].'*';
        }
        $post['duty_paragraph'] = $duty_paragraph;
        $post['create_time'] = time();
        $post['head_up_type'] = 1;
        $post['money'] = $money;
        $post['pigcms_id'] = $pigcms_id;
        $post['receive_tel'] = $phone;
        $post['name'] = $name;
        $post['fpqqlsh'] = $fpqqlsh;
        $post['order_no'] = $order_no;
        $post['property_type'] = rtrim($order_name,'*');
        $id = M('House_village_e_record')->insertData($post);
        foreach ($detail as $k=>$v){
            $insertAll['order_id'] = $v['order_id'];
            $insertAll['property_type'] = $v['goodsname'];
            $insertAll['price'] = $v['price'];
            $insertAll['e_record_id'] = $id;
            $insertAll['create_time'] = time();
            M('House_village_detail_record')->insertData($insertAll);
        }
    }
}