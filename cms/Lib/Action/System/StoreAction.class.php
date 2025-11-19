<?php
/*
 * 到店消费
 *
 * @  BuildTime  2016/8/13 15:41
 */

class StoreAction extends BaseAction{
	public function buy_order(){

        $province_id = isset($_GET['province_idss']) ? intval($_GET['province_idss']) : 0;
        $city_id = isset($_GET['city_idss']) ? intval($_GET['city_idss']) : 0;
        $area_id = isset($_GET['area_id']) ? intval($_GET['area_id']) : 0;
        $circle_id = isset($_GET['circle_id']) ? intval($_GET['circle_id']) : 0;
        $street_id = I('get.street_id', 0, 'intval');

        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if ($now_area['area_type'] == 3) {
                $area_id = $this->system_session['area_id'];
                $city_id = $now_area['area_pid'];
                $temp_area = D('Area')->field(true)->where(array('area_id' => $city_id))->find();
                $province_id = $temp_area['area_pid'];
            } elseif($now_area['area_type'] == 2) {
                $city_id = $this->system_session['area_id'];
                $province_id = $now_area['area_pid'];
            } elseif ($now_area['area_type'] == 1) {
                $province_id = $this->system_session['area_id'];
            }
            $this->assign('admin_area', $now_area['area_type']);
        }

		import('@.ORG.system_page');
        $where_sql = 's.from_plat=1';
        if(C('customization.internal_membership_card')){
            $where_sql = 's.from_plat IN (1, 4)';//1 优惠买单 4 一卡通充值
        }
		if (empty($_GET['keyword'])){
			$where_sql .= ' AND s.paid=1';
		}

        if (!empty($_GET['keyword']) && $_GET['searchtype'] == 'store_name') {
            $where_sql .= ' AND ms.name LIKE "%' . $_GET['keyword'] .'%"';
        } elseif (!empty($_GET['keyword']) && $_GET['searchtype'] == 'order_id') {
            $where_sql .= " AND s.order_id=" . trim($_GET['keyword']);
        } elseif (!empty($_GET['keyword']) && $_GET['searchtype'] == 'phone') {
            $where_sql .= " AND u.phone LIKE '%" . trim($_GET['keyword']) . "%'";
        } elseif (!empty($_GET['keyword']) && $_GET['searchtype'] == 'name') {
            $where_sql .= " AND u.nickname LIKE '%" . trim($_GET['keyword']) . "%'";
        } elseif (!empty($_GET['keyword']) && $_GET['searchtype'] == 'orderid') {
            $where_sql .= " AND s.orderid = '" . ltrim($_GET['keyword'], 'store_') . "'";
        }

        if ($province_id) {
            $where_sql .= ' AND ms.province_id=' . $province_id .'';
        }
        if ($city_id) {
            $where_sql .= ' AND ms.city_id=' . $city_id .'';
        }
        if ($area_id) {
            $where_sql .= ' AND ms.area_id=' . $area_id .'';
        }
        if ($circle_id) {
            $where_sql .= ' AND ms.circle_id=' . $circle_id .'';
        }
        if ($street_id) {
            $where_sql .= ' AND ms.street_id=' . $street_id .'';
        }
        if (! empty($_GET['begin_time']) && ! empty($_GET['end_time'])) {
            if ($_GET['begin_time'] > $_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(
                strtotime($_GET['begin_time'] . " 00:00:00"),
                strtotime($_GET['end_time'] . " 23:59:59")
            );
            $where_sql .= " AND (s.pay_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
        }
        $pay_type = isset($_GET['pay_type']) && $_GET['pay_type'] ? $_GET['pay_type'] : '';
        if ($pay_type && $pay_type != 'balance') {
            if(strpos($pay_type,'App')){
                preg_match('/(\w+)App/',$pay_type,$res);
                $where_sql .= " AND `s`.`pay_type`='" . $res[1] . "' AND s.is_mobile_pay=2 ";
            }else{
                $where_sql .= " AND `s`.`pay_type`='" . $pay_type . "'";
            }
        } else if ($pay_type == 'balance') {
            $where_sql .= " AND (`s`.`balance_pay`<>0 OR `s`.`merchant_balance` <> 0 )";
        }

        $count =  M('Store_order')->join("AS s LEFT JOIN ".C('DB_PREFIX')."merchant AS m ON s.mer_id=m.mer_id LEFT JOIN " . C('DB_PREFIX') . "merchant_store AS ms ON s.store_id=ms.store_id LEFT JOIN " . C('DB_PREFIX') . "user AS u ON s.uid=u.uid")
            ->where($where_sql)->count();

		$p = new Page($count, 20);


		$sql = "SELECT s.*, u.nickname, u.phone, m.name AS merchant_name, ms.name AS store_name FROM " . C('DB_PREFIX') . "store_order AS s LEFT JOIN ".C('DB_PREFIX')."merchant AS m ON s.mer_id=m.mer_id LEFT JOIN " . C('DB_PREFIX') . "merchant_store AS ms ON s.store_id=ms.store_id LEFT JOIN " . C('DB_PREFIX') . "user AS u ON s.uid=u.uid WHERE ".$where_sql." ORDER BY s.order_id DESC LIMIT {$p->firstRow}, {$p->listRows}";
		$order_list = M()->query($sql);
		foreach ($order_list as &$l) {
			if($l['paid']){
				$l['pay_type_show'] = D('Pay')->get_pay_name($l['pay_type'], $l['is_mobile_pay'], $l['paid'], $l['order_id'], $l);
			}else{
				$l['pay_type_show'] = '未支付';
			}

            $card_discount_money = 0;
            if ($l['card_discount']) {
                $card_discount_money = round($l['price']-($l['price']-$l['no_discount_money'])*$l['card_discount']/10-$l['no_discount_money'],2);
            }
            $l['card_discount_money'] = $card_discount_money;
            $l['price'] =  $l['price'] - $l['card_discount_money'];

            if ($l['card_id']) { // 商家优惠券
                $sql = "SELECT `c`.`discount`,`c`.`order_money` FROM " . C('DB_PREFIX') . "card_new_coupon_hadpull AS h INNER JOIN " . C('DB_PREFIX') . "card_new_coupon AS c ON h.coupon_id = c.coupon_id WHERE h.id='{$l['card_id']}'";
                $coupon_info = M()->query($sql);
                if ($coupon_info) {                    
                    $l['merchant_coupon'] = '满'.$coupon_info[0]['order_money'].'减'.$coupon_info[0]['discount'];                    
                }
            }

            if ($l['coupon_id']) { // 平台优惠券 
                $sql = "SELECT `c`.`discount`,`c`.`order_money`,`c`.`is_discount`,`c`.`discount_value` FROM " . C('DB_PREFIX') . "system_coupon_hadpull AS h INNER JOIN " . C('DB_PREFIX') . "system_coupon AS c ON h.coupon_id = c.coupon_id WHERE h.id='{$l['coupon_id']}'";
                $system_coupon_info = M()->query($sql);
                if ($system_coupon_info[0]['is_discount'] == 1) {
                    $l['system_coupon'] = $system_coupon_info[0]['discount_value'].'折';
                }else{
                    $l['system_coupon'] = '满'.$system_coupon_info[0]['order_money'].'减'.$system_coupon_info[0]['discount'];
                }
            }
		}
        $pay_method = D('Config')->get_pay_method('', '', 1);
        $pay_method['alipayApp'] = array(
            'name'=>'支付宝APP'
        );

        $pay_method['weixin']['name'] ='微信WAP支付';
        $pay_method['alipayh5']['name'] ='支付宝WAP支付';
        $this->assign('pay_method', $pay_method);
		$pagebar = $p->show();
		
		$this->assign(array('order_list' => $order_list, 'pagebar' => $pagebar));
		$this->display();
	}
	public function store_ticket(){
		if(IS_POST){
			$database_config = D('Config');
			foreach($_POST as $key=>$value){
				$data['name'] = $key;
				$data['value'] = trim(stripslashes(htmlspecialchars_decode($value)));
				$database_config->data($data)->save();
			}
			
			//删除缓存
			D('Config')->clearDataCacheDir('system_config');
			
			$this->success('修改成功');
		}else{
			$this->display();
		}
	}
	public function arrival_order(){

        $province_id = isset($_GET['province_idss']) ? intval($_GET['province_idss']) : 0;
        $city_id = isset($_GET['city_idss']) ? intval($_GET['city_idss']) : 0;
        $area_id = isset($_GET['area_id']) ? intval($_GET['area_id']) : 0;
        $circle_id = isset($_GET['circle_id']) ? intval($_GET['circle_id']) : 0;
        $street_id = I('get.street_id', 0, 'intval');

        if ($this->system_session['area_id']) {
            $now_area = D('Area')->field(true)->where(array('area_id' => $this->system_session['area_id']))->find();
            if ($now_area['area_type'] == 3) {
                $area_id = $this->system_session['area_id'];
                $city_id = $now_area['area_pid'];
                $temp_area = D('Area')->field(true)->where(array('area_id' => $city_id))->find();
                $province_id = $temp_area['area_pid'];
            } elseif($now_area['area_type'] == 2) {
                $city_id = $this->system_session['area_id'];
                $province_id = $now_area['area_pid'];
            } elseif ($now_area['area_type'] == 1) {
                $province_id = $this->system_session['area_id'];
            }
            $this->assign('admin_area', $now_area['area_type']);
        }

        import('@.ORG.system_page');
        $where_sql = 's.from_plat IN (2,3)';
		
		// if (empty($_GET['keyword'])){
			$where_sql .= ' AND s.paid=1';
		// }

        if (!empty($_GET['keyword']) && $_GET['searchtype'] == 'store_name') {
            $where_sql .= ' AND ms.name LIKE "%' . $_GET['keyword'] .'%"';
        } elseif (!empty($_GET['keyword']) && $_GET['searchtype'] == 'order_id') {
            $where_sql .= " AND s.order_id=" . $_GET['keyword'];
        } elseif (!empty($_GET['keyword']) && $_GET['searchtype'] == 'phone') {
            $where_sql .= " AND u.phone LIKE '%" . htmlspecialchars($_GET['keyword']) . "%'";
        } elseif (!empty($_GET['keyword']) && $_GET['searchtype'] == 'name') {
            $where_sql .= " AND u.nickname LIKE '%" . trim($_GET['keyword']) . "%'";
        } elseif (!empty($_GET['keyword']) && $_GET['searchtype'] == 'orderid') {
            $where_sql .= " AND s.orderid = '" . ltrim($_GET['keyword'], 'store_') . "'";
        }
        
        if ($province_id) {
            $where_sql .= ' AND ms.province_id=' . $province_id .'';
        }
        if ($city_id) {
            $where_sql .= ' AND ms.city_id=' . $city_id .'';
        }
        if ($area_id) {
            $where_sql .= ' AND ms.area_id=' . $area_id .'';
        }
        if ($circle_id) {
            $where_sql .= ' AND ms.circle_id=' . $circle_id .'';
        }
        if ($street_id) {
            $where_sql .= ' AND ms.street_id=' . $street_id .'';
        }
        if (! empty($_GET['begin_time']) && ! empty($_GET['end_time'])) {
            if ($_GET['begin_time'] > $_GET['end_time']) {
                $this->error_tips("结束时间应大于开始时间");
            }
            $period = array(
                strtotime($_GET['begin_time'] . " 00:00:00"),
                strtotime($_GET['end_time'] . " 23:59:59")
            );
            $where_sql .= " AND (s.pay_time BETWEEN " . $period[0] . ' AND ' . $period[1] . ")";
        }
        $pay_type = isset($_GET['pay_type']) && $_GET['pay_type'] ? $_GET['pay_type'] : '';
        if ($pay_type && $pay_type != 'balance') {
            if(strpos($pay_type,'App')){
                preg_match('/(\w+)App/',$pay_type,$res);
                $where_sql .= " AND `s`.`pay_type`='" . $res[1] . "' AND s.is_mobile_pay=2 ";
            }else{
                $where_sql .= " AND `s`.`pay_type`='" . $pay_type . "'";
            }
        } else if ($pay_type == 'balance') {
            $where_sql .= " AND (`s`.`balance_pay`<>0 OR `s`.`merchant_balance` <> 0 )";
        }

        $count =  M('Store_order')->join("AS s LEFT JOIN ".C('DB_PREFIX')."merchant AS m ON s.mer_id=m.mer_id LEFT JOIN " . C('DB_PREFIX') . "merchant_store AS ms ON s.store_id=ms.store_id LEFT JOIN " . C('DB_PREFIX') . "user AS u ON s.uid=u.uid")
            ->where($where_sql)->count();
        $p = new Page($count, 20);

		$sql = "SELECT s.*, u.nickname, u.phone, m.name AS merchant_name, ms.name AS store_name FROM " . C('DB_PREFIX') . "store_order AS s LEFT JOIN ".C('DB_PREFIX')."merchant AS m ON s.mer_id=m.mer_id LEFT JOIN " . C('DB_PREFIX') . "merchant_store AS ms ON s.store_id=ms.store_id LEFT JOIN " . C('DB_PREFIX') . "user AS u ON s.uid=u.uid WHERE ".$where_sql." ORDER BY s.order_id DESC LIMIT {$p->firstRow}, {$p->listRows}";
		$order_list = M()->query($sql);
		foreach ($order_list as &$l) {
			if($l['paid']){
				$l['pay_type_show'] = D('Pay')->get_pay_name($l['pay_type'], $l['is_mobile_pay']);
			}else{
				$l['pay_type_show'] = '未支付';
			}
			
            $card_discount_money = 0;
            if ($l['card_discount']) {
                $card_discount_money = round($l['price']-($l['price']-$l['no_discount_money'])*$l['card_discount']/10-$l['no_discount_money'],2);
            }
            $l['card_discount_money'] = $card_discount_money;
            $l['price'] =  $l['price'] - $l['card_discount_money'];

            if ($l['card_id']) { // 商家优惠券
                $sql = "SELECT `c`.`discount`,`c`.`order_money` FROM " . C('DB_PREFIX') . "card_new_coupon_hadpull AS h INNER JOIN " . C('DB_PREFIX') . "card_new_coupon AS c ON h.coupon_id = c.coupon_id WHERE h.id='{$l['card_id']}'";
                $coupon_info = M()->query($sql);
                if ($coupon_info) {                    
                    $l['merchant_coupon'] = '满'.$coupon_info[0]['order_money'].'减'.$coupon_info[0]['discount'];                    
                }
            }

            if ($l['coupon_id']) { // 平台优惠券 
                $sql = "SELECT `c`.`discount`,`c`.`order_money`,`c`.`is_discount`,`c`.`discount_value` FROM " . C('DB_PREFIX') . "system_coupon_hadpull AS h INNER JOIN " . C('DB_PREFIX') . "system_coupon AS c ON h.coupon_id = c.coupon_id WHERE h.id='{$l['coupon_id']}'";
                $system_coupon_info = M()->query($sql);
                if ($system_coupon_info[0]['is_discount'] == 1) {
                    $l['system_coupon'] = $system_coupon_info[0]['discount_value'].'折';
                }else{
                    $l['system_coupon'] = '满'.$system_coupon_info[0]['order_money'].'减'.$system_coupon_info[0]['discount'];
                }
            }
		}
        $pay_method = D('Config')->get_pay_method('', '', 1);
        $pay_method['alipayApp'] = array(
            'name'=>'支付宝APP'
        );
        $pay_method['weixin']['name'] ='微信WAP支付';
        $pay_method['alipayh5']['name'] ='支付宝WAP支付';

        $this->assign('pay_method', $pay_method);

        $this->assign('pay_type', $pay_type);
		$pagebar = $p->show();
		
		$this->assign(array('order_list' => $order_list, 'pagebar' => $pagebar));
		$this->display();
	}


    public function buy_order_export(){
        $param = $_POST;
        $param['type'] = 'store';
        $param['rand_number'] = time();
        $param['paid'] = 1;
        $param['from_plat'] = 1;
        $param['info_type'] = 1;
        if($res = D('Order')->order_export($param)){
            echo json_encode(array('error_code'=>0,'msg'=>'添加导出计划成功','file_name'=>$res['file_name'],'export_id'=>$res['export_id'],'rand_number'=> $param['rand_number']));
        }else{
            echo json_encode(array('error_code'=>1,'msg'=>'导出失败'));
        }
        die;
    }


    public function arrival_order_export(){
        $param = $_POST;
        $param['type'] = 'store';
        $param['rand_number'] = time();
        $param['paid'] = 1;
        $param['from_plat'] = '2,3';
        $param['info_type'] = 2;
        if($res = D('Order')->order_export($param)){
            echo json_encode(array('error_code'=>0,'msg'=>'添加导出计划成功','file_name'=>$res['file_name'],'export_id'=>$res['export_id'],'rand_number'=> $param['rand_number']));
        }else{
            echo json_encode(array('error_code'=>1,'msg'=>'导出失败'));
        }
        die;
    }
}
