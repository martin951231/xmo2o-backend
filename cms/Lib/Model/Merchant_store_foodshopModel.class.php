<?php
class Merchant_store_foodshopModel extends Model
{
	public function get_list_group_by_option()
	{
		$now_time = time();
		$sql = 'SELECT * FROM ' . C('DB_PREFIX') . 'merchant_store_foodshop AS f INNER JOIN ' . C('DB_PREFIX') . 'merchant_store AS s ON f.store_id=s.store_id WHERE s.have_meal=1 AND s.status=1';
		$temps = D()->query($sql);
		$result = array();
		foreach ($temps as $tmp) {
			$tmp['group_list'] = '';
			$tmp['group_count'] = 0;
			$tmp['discount_txt'] = unserialize($tmp['discount_txt']);
			$result[$tmp['store_id']] = $tmp;
		}
		$store_id_arr = array_keys($result);
		if ($store_id_arr) {
			$store_ids = implode(',', $store_id_arr);
			$sql = 'SELECT * FROM ' . C('DB_PREFIX') . 'group_store AS gs INNER JOIN ' . C('DB_PREFIX') . 'group AS g ON g.group_id=gs.group_id WHERE gs.store_id IN (' . $store_ids . ')';
			$groups = D()->query($sql);
			$group_image_class = new group_image();
			foreach ($groups as $row) {
				$tmp_pic_arr = explode(';', $row['pic']);
				$row['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0], 's');
				$row['url'] = $this->get_group_url($row['group_id'], true);

				$row['price'] = floatval($row['price']);
				$row['old_price'] = floatval($row['old_price']);
				$row['wx_cheap'] = floatval($row['wx_cheap']);
				$row['is_start'] = 1;
				if ($now_time < $row['begin_time']) {
					$row['is_start'] = 0;
				}
				$row['begin_time'] = date("Y-m-d H:i:s", $row['begin_time']);
				if (isset($result[$row['store_id']])) {
					$result[$row['store_id']]['group_count']++;
					$result[$row['store_id']]['group_list'][] = $row;
				}
			}
		}
		return $result;
	}
	/*wap版得到指定分类ID或分类父ID下的分类，带有分页功能*/
// 	public function wap_get_storeList_by_catid($area_id = 0, $circle_id = 0, $order = '', $lat = 0, $long = 0, $cat_fid = 0, $cat_id = 0, $is_queue = -1)
	public function wap_get_storeList_by_catid($params,$is_wap=0)
	{
		$area_id = $params['area_id'];
		$circle_id = $params['circle_id'];
		$sort_type = $params['sort'];
		$lat = $params['lat'];
		$long = $params['long'];
		$cat_fid = $params['cat_fid'];
		$cat_id = $params['cat_id'];
		$is_queue = $params['queue'];
		$keyword = $params['keyword'];
		$site_url  = C('config.site_url');

		$condition_field = '`f`.*, `s`.*, `m`.discount_percent, `m`.is_discount, `m`.other_discount, `m`.foodshop_tables_discount,`f`.foodshop_tables_discount as foodshop_tables_discount_shop,`f`.other_discount as other_discount_shop';

// 		$condition_where = 's.have_meal=1 AND s.status=1';
		$condition_where = "s.city_id='" . C('config.now_city') . "' AND s.have_meal=1 AND s.status=1 AND s.foodshop_on_sale=1";
		//排序方式
		$order='';
		//获取分类下店铺歇业时显示方式
		if($cat_id){
			$show_method=M('Meal_store_category')->where(array('cat_id'=>$cat_id))->getfield('show_method');
		}elseif($cat_fid){
			$show_method=M('Meal_store_category')->where(array('cat_id'=>$cat_fid))->getfield('show_method');
		}
		//0:不显示，1:正常显示，2：靠后显示
		$show_method=is_numeric($show_method)?$show_method:2;
		$time=date('H:i:s');
		if($show_method==0){			
			$condition_where .= " AND (";
            $condition_where .= "(`s`.`open_1`='00:00:00' AND `s`.`close_1`='00:00:00')";
            $condition_where .= " OR ((`s`.`open_1`<'$time' AND `s`.`close_1`>'$time')";
            $condition_where .= " OR (`s`.`open_2`<'$time' AND `s`.`close_2`>'$time')";
            $condition_where .= " OR (`s`.`open_3`<'$time' AND `s`.`close_3`>'$time')))";
		}elseif($show_method==2){
			$condition_field .= ",(CASE
	        WHEN (`s`.`open_1`='00:00:00' and `s`.`open_2`='00:00:00' and `s`.`open_3`='00:00:00' and `s`.`close_1`='00:00:00' and `s`.`close_2`='00:00:00' and `s`.`close_3`='00:00:00') then 2
	        WHEN (`s`.`open_1` = `s`.`close_1`) then 2
	        WHEN ((`s`.`open_1`<'$time' and `s`.`close_1`>'$time') OR (`s`.`open_2`<'$time' and `s`.`close_2`>'$time') OR (`s`.`open_3`<'$time' and `s`.`close_3`>'$time')) then 2
	        ELSE 0
	        END) as `t_sort`";
            $order .= '`t_sort` DESC, ';

		}
		if ($is_queue != -1) {
			if ($is_queue == 1) {
				$condition_where .= ' AND f.is_queue=1';
			} elseif ($is_queue == 0) {
				$condition_where .= ' AND f.is_queue=0';
			}
		}
		//区域
		if($area_id || $circle_id){
			if ($circle_id) {
				$condition_where .= " AND `s`.`circle_id`='$circle_id'";
			} else {
				$condition_where .= " AND `s`.`area_id`='$area_id'";
			}
		}


		if ($cat_fid || $cat_id) {
			if ($cat_fid && $cat_id) {
				$relation = D('Meal_store_category_relation')->where(array(array('cat_fid' => $cat_fid, 'cat_id' => $cat_id)))->select();
			} elseif ($cat_fid) {
				$relation = D('Meal_store_category_relation')->where(array(array('cat_fid' => $cat_fid)))->select();
			} else {
				$relation = D('Meal_store_category_relation')->where(array(array('cat_id' => $cat_id)))->select();
			}
			$store_ids = array();
			foreach ($relation as $r) {
				if (!in_array($r['store_id'], $store_ids)) {
					$store_ids[] = $r['store_id'];
				}
			}
			if ($store_ids) {
				// $condition_where .= ' AND s.store_id IN (' . implode(',', $store_ids) . ')';
			} else {
				return array('store_list' => null, 'store_count' => 0, 'totalPage' => 0);
			}
		}

		if ($params['store_id']) {
            // 取交集
            $store_ids && $params['store_id'] = array_intersect($store_ids, $params['store_id']);
            if ($params['store_id']) {
                $condition_where .= ' AND s.store_id IN (' . implode(',', $params['store_id']) . ')';
            } else {
                return array('store_list' => null, 'totalPage' => null, 'store_count' => 0);
            }
        }else{
            if ($store_ids) {
                $condition_where .= ' AND s.store_id IN (' . implode(',', $store_ids) . ')';
            }
        }

		if ($keyword) {
			$condition_where .= " AND `s`.`name` LIKE '%{$keyword}%'";
		}

		if ($lat>0 && $long>0) {
			if ($sort_type == 'juli') {
                $condition_field .= ", ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) AS juli,m.isverify";
			}
		} else {
			$sort_type = $sort_type == 'juli' ? '' : $sort_type;
		}
		$join_field = '';
        // 排序
        switch ($sort_type) {
//             case 'rating':
//                 $order = '`f`.`sort` DESC, `f`.`score_mean` DESC, `f`.`sort` DESC';
//                 break;
//             case 'start':
//                 $order = '`f`.`sort` DESC, `f`.`create_time` DESC,`f`.`sort` DESC';
//                 break;
            case 'new':
                $order.= '`f`.`create_time` DESC, `f`.`sort` DESC';
                break;
            case 'discount':
                $order.= '`t_discount` ASC,`f`.`sort` DESC';
                $condition_field .= ",`da`.table_discount as t_discount";
                $join_field = " LEFT JOIN pigcms_foodshop_date_discount AS da ON s.store_id = da.store_id ";
                if(isset($params['foodChooseDate'])){
                    $condition_where .= " AND `da`.`book_date` = '{$params['foodChooseDate']}' AND `da`.`table_discount` > 0";
                }
                break;
            case 'juli':
                $order.='`f`.`sort` DESC, `juli` asc, `f`.`sort` DESC';
                break;
            default:
                $order.= '`f`.`sort` DESC, `f`.`create_time` DESC';
        }
        
        import('@.ORG.wap_group_page');
        
        $sql_count = 'SELECT count(1) as cnt  FROM ' . C('DB_PREFIX') . 'merchant_store_foodshop AS f INNER JOIN ' . C('DB_PREFIX') . 'merchant_store AS s ON f.store_id=s.store_id LEFT JOIN ' . C('DB_PREFIX') . 'merchant mm ON mm.mer_id = s.mer_id '.$join_field.' WHERE ' . $condition_where . ' AND mm.status = 1';
        
        $count_result = D()->query($sql_count);
        // echo D()->_sql();
        $count = isset($count_result[0]['cnt']) ? intval($count_result[0]['cnt']) : 0;
        
        $p = new Page($count, 10, 'page');
        if (!$params['limit']) {
	        if ($is_wap == 1) {
	            $pagesize = 10;
	            $star = ((isset($params['page']) ? intval($params['page']) : 0)) * $pagesize;
	        } else if ($is_wap == 2)  {
				$pagesize = 10;
				$star =isset($params['page']) ? intval($params['page']) : 0;
			}else {
	            $star = $p->firstRow;
	            $pagesize = $p->listRows;
	        }
        }else{
            $star = 0;
            $pagesize = $params['limit'];
        }

		$sql = 'SELECT ' . $condition_field . ' FROM ' . C('DB_PREFIX') . 'merchant_store_foodshop AS f INNER JOIN ' . C('DB_PREFIX') . 'merchant_store AS s ON f.store_id=s.store_id LEFT JOIN ' . C('DB_PREFIX') . 'merchant AS m ON m.mer_id = s.mer_id '.$join_field.' WHERE ' . $condition_where . ' AND m.status=1 ORDER BY ' . $order . ' LIMIT ' . $star . ',' . $pagesize;
		$store_list = D()->query($sql);
		import('@.ORG.longlat');
		$longlat_class = new longlat();
		$store_image_class = new store_image();
        $nowTime = time() - 864000;
		foreach ($store_list as $tmp) {
		    $tmps['is_new'] = 0;
		    if ($tmp['create_time'] > $nowTime) {
                if (1 == C('config.is_open_merchant_foodshop_discount')) {
                    $tmps['is_new'] = 1;
                }
		    }
		    $tmps['sys_discount'] = 0;
		    if (1 == C('config.is_open_merchant_discount') && $tmp['is_discount'] == 1 && $tmp['discount_percent'] > 0) {
		        $tmps['sys_discount'] = floatval($tmp['discount_percent'] / 10);
		    }
		    //开启桌台优惠
            if (1 == C('config.is_open_merchant_foodshop_discount')) {
		        $thatday = $params['foodChooseDate'];
		        $thatday =  $thatday ? $thatday : date('Ymd');
                $time_start = strtotime(date($thatday));
		        $time_end = strtotime(date($thatday.' 23:59:59'));
		        $where['book_time'] = array('between',array($time_start,$time_end));
                $where['status'] = array('in',array(1,2,3,4));
                $where['order_from'] = 0;
		        $where['store_id'] = $tmp['store_id'];
		        $order_index = D('foodshop_order')->where($where)->count();
                if(($tmp['other_discount_shop']>0 && $tmp['other_discount_shop']<100) || $tmp['foodshop_tables_discount_shop']!=''){
                    $tmp['other_discount'] = $tmp['other_discount_shop'];
                    $tmp['foodshop_tables_discount'] = $tmp['foodshop_tables_discount_shop'];
                }else{
                    unset($where['store_id']);
                    $where['mer_id'] = $tmp['mer_id'];
                    $order_index = D('foodshop_order')->where($where)->count();
                }
                if($tmp['foodshop_tables_discount']!=''){
                    $tmp['foodshop_tables_discount'] = substr($tmp['foodshop_tables_discount'], 0, -1);
                    $discountArr= explode('|', $tmp['foodshop_tables_discount']);
                    $tables = [];
                    foreach($discountArr as $k => $v){
                        $v = explode(':',$v);
                        $tables[$k] = $v;
                    }
                    if(intval($order_index)>=count($tables)){
                        $mer_discount =  $tmp['other_discount'];
                    }else{
                        $mer_discount =  $tables[$order_index][1];
                    }
                }else{
                    $mer_discount =  $tmp['other_discount'];
                }
                $tmps['mer_discount'] = floatval($mer_discount / 10);
            }
		    $tmps['name'] = $tmp['name'];
		    $images = $store_image_class->get_allImage_by_path($tmp['pic_info']);
		    $tmps['image'] = $images ? thumb(array_shift($images),112) : '';
			$tmps['phone'] = $tmp['phone'];
			$tmps['adress'] = $tmp['adress'];
			$tmps['is_book'] = $tmp['is_book'];
			$tmps['is_queue'] = $tmp['is_queue'];
			$tmps['is_takeout'] = $tmp['is_takeout'];
			$tmps['long'] = $tmp['long'];
			$tmps['lat'] = $tmp['lat'];
			if ($tmp['juli']) {
				$tmps['range'] = getRange($tmp['juli']);
			} else if($long && $lat){
				$location2 = $longlat_class->gpsToBaidu($tmp['lat'], $tmp['long']);//转换腾讯坐标到百度坐标
				$jl = getDistance($location2['lat'], $location2['lng'], $lat, $long);
				$tmps['range'] = getRange($jl);
			}else{
				$tmps['range'] = 0;
			}


			$tmps['state'] = 0;//根据营业时间判断
			$time = time();
			$open_1 = strtotime(date('Y-m-d') . $tmp['open_1']);
			$close_1 = strtotime(date('Y-m-d') . $tmp['close_1']);
			$open_2 = strtotime(date('Y-m-d') . $tmp['open_2']);
			$close_2 = strtotime(date('Y-m-d') . $tmp['close_2']);
			$open_3 = strtotime(date('Y-m-d') . $tmp['open_3']);
			$close_3 = strtotime(date('Y-m-d') . $tmp['close_3']);
			if ($tmp['open_1'] == '00:00:00' && $tmp['close_1'] == '00:00:00') {
				$tmps['state'] = 1;
			} elseif (($open_1 < $time && $close_1 > $time) || ($open_2 < $time && $close_2 > $time) || ($open_3 < $time && $close_3 > $time)) {
				$tmps['state'] = 1;
			}

			$tmps['group_list'] = array();
			$tmps['group_count'] = 0;
			$tmps['pay_in_store'] = C('config.pay_in_store');
			$tmps['store_pay'] = $site_url.str_replace('appapi.php','wap.php',U('My/pay', array('store_id' => $tmp['store_id'])));
			$tmps['url'] = $site_url.str_replace('appapi.php','wap.php',U('Foodshop/shop', array('store_id' => $tmp['store_id'])));
			$tmps['discount_txt'] = $tmp['discount_txt'] ? unserialize($tmp['discount_txt']) : array();
			$tmps['score_mean'] = $tmp['score_mean']==0 ? 5.0 : $tmp['score_mean'];
			$tmps['store_id'] = $tmp['store_id'];
			$result[$tmp['store_id']] = $tmps;
		}

		$store_id_arr = array_keys($result);
		if ($store_id_arr) {
			$store_ids = implode(',', $store_id_arr);
			$now_time = time();
			$sql = 'SELECT g.name,g.pic,g.group_id,g.price,g.old_price,g.wx_cheap,g.pin_num,g.sale_count,g.virtual_num,g.begin_time,gs.store_id FROM ' . C('DB_PREFIX') . 'group_store AS gs INNER JOIN ' . C('DB_PREFIX') . 'group AS g ON g.group_id=gs.group_id WHERE gs.store_id IN (' . $store_ids . ') AND `g`.`status`=1 AND `g`.`type`=1 AND `g`.`end_time`>\'' . $now_time . '\' ORDER BY `g`.`sort` DESC,`g`.`group_id` DESC';
			$groups = D()->query($sql);
			$group_image_class = new group_image();
			foreach ($groups as $row) {
				$tmp_pic_arr = explode(';', $row['pic']);
				$row['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0], 's');
				$row['url'] =$site_url.$this->get_group_url($row['group_id'], true);

				$row['price'] = floatval($row['price']);
				$row['old_price'] = floatval($row['old_price']);
				$row['wx_cheap'] = floatval($row['wx_cheap']);
				$row['is_start'] = 1;
				if ($now_time < $row['begin_time']) {
					$row['is_start'] = 0;
				}
				if($row['begin_time']+864000>time()&&$row['sale_count']==0){
					$row['sale_txt'] = L_('新品上架');
				}elseif($row['begin_time']+864000<time()&&$row['sale_count']==0){
					$row['sale_txt'] = '';
				}else{
					$row['sale_txt'] = L_('已售').floatval($row['sale_count']+$row['virtual_num']);
				}
				$row['begin_time'] = date("Y-m-d H:i:s", $row['begin_time']);
				if (isset($result[$row['store_id']])) {
					if ($result[$row['store_id']]['group_count'] < 2) {
						$result[$row['store_id']]['group_list'][] = $row;
					}
					$result[$row['store_id']]['group_count']++;
				}
			}
		}
		$_store_list = array();
		foreach ($result as $row) {
			$_store_list[] = $row;
		}
		$return['totalPage'] = $p->totalPage;//ceil($count / 10);


		$return['store_list'] = $_store_list;
		$return['store_count'] = $count;
		return $return;

	}
	public function get_group_url($group_id, $is_wap)
	{
		if ($is_wap) {
			return str_replace('appapi.php', 'wap.php', U('Wap/Groupnew/detail', array('group_id' => $group_id)));
		} else {
			return C('config.site_url') . '/group/' . $group_id . '.html';
		}
	}

	public function get_shop_detail($store_id)
	{
		$sql = 'SELECT * FROM ' . C('DB_PREFIX') . 'merchant_store_foodshop AS f INNER JOIN ' . C('DB_PREFIX') . 'merchant_store AS s ON f.store_id=s.store_id WHERE s.have_meal=1 AND s.status=1 AND s.store_id=' . $store_id;
		$temps = D()->query($sql);
// 		echo '<Pre/>';
// 		print_r($temps);
		$shop = isset($temps[0]) ? $temps[0] : '';
		if ($shop) {

			if ($shop['open_1'] == '00:00:00' && $shop['close_1'] == '00:00:00') {
				$shop['business_time'] = '24小时营业';
			} else {
				$shop['business_time'] = $shop['open_1'] . '~' . $shop['close_1'];
				if ($shop['open_2'] != '00:00:00' && $shop['close_2'] != '00:00:00') {
					$shop['business_time'] = ',' . $shop['open_2'] . '~' . $shop['close_2'];
				}
				if ($shop['open_3'] != '00:00:00' && $shop['close_3'] != '00:00:00') {
					$shop['business_time'] = ',' . $shop['open_3'] . '~' . $shop['close_3'];
				}
			}
			$shop['group_list'] = '';
			$shop['group_count'] = 0;
			$sql = 'SELECT * FROM ' . C('DB_PREFIX') . 'group_store AS gs INNER JOIN ' . C('DB_PREFIX') . 'group AS g ON g.group_id=gs.group_id WHERE gs.store_id=' . $store_id;
			$groups = D()->query($sql);
			$group_image_class = new group_image();
			foreach ($groups as $row) {
				$tmp_pic_arr = explode(';', $row['pic']);
				$row['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0], 's');
				$row['url'] = $this->get_group_url($row['group_id'], true);

				$row['price'] = floatval($row['price']);
				$row['old_price'] = floatval($row['old_price']);
				$row['wx_cheap'] = floatval($row['wx_cheap']);
				$row['is_start'] = 1;
				if ($now_time < $row['begin_time']) {
					$row['is_start'] = 0;
				}
				$row['begin_time'] = date("Y-m-d H:i:s", $row['begin_time']);
				$shop['group_list'][] = $row;
				$shop['group_count']++;
			}
		}
		return $shop;
	}
	//判断当前订单是第几桌支付并享受几折优惠
    function getTableDiscount($mer_id,$store_id,$order_id,$now_order=array()){
	    //如果开启
        if (1 == C('config.is_open_merchant_foodshop_discount')) {
            $orderinfo = D('foodshop_order')->where(array('order_id'=>$order_id))->find();
            if($orderinfo['order_from']==1 ||$orderinfo['order_from']==2){
                return false;
            }
            $thatday = date('Ymd',$orderinfo['book_time']);
            $time_start = strtotime($thatday);
            $time_end = strtotime($thatday.' 23:59:59');

            $where['book_time'] = array('between',array($time_start,$time_end));
            if($orderinfo['book_pay_time']>0) $where['book_pay_time'] = array('ELT',$orderinfo['book_pay_time']);
            $where['status'] = array('in',array(1,2,3,4));
            $where['store_id'] = $store_id;
            $order_index = D('foodshop_order')->where($where)->count();
            $tmp = D('Merchant_store_foodshop')->where(array('store_id'=>$store_id))->find();
            if($tmp['other_discount']<=0 || $tmp['other_discount']>=100){
                if($tmp['foodshop_tables_discount']==''){
                    $tmp = D('Merchant')->where(array('mer_id'=>$mer_id))->find();
                    unset($where['store_id']);
                    $where['mer_id'] =$mer_id;
                    $order_index = D('foodshop_order')->where($where)->count();
                }
            }
            M('foodshop_order')->where(array('order_id'=>$order_id))->save(array('table_index'=>$order_index));
            //判断是否同一时间支付预定
            if($orderinfo['book_pay_time']>0){
                $where_same['book_pay_time'] = $orderinfo['book_pay_time'];
                $where_same['book_time'] = array('between',array($time_start,$time_end));
                $where_status['status'] = array('in',array(1,2,3,4));
                if($where['store_id']){
                    $where_same['store_id'] = $where['store_id'];
                }else{
                    $where_same['mer_id'] = $where['mer_id'];
                }
                $orders_samecount = D('foodshop_order')->where($where_same)->count();
                if($orders_samecount>1) {
                    $orders_same =  D('foodshop_order')->where($where_same)->select();
                    foreach($orders_same as $order){
                        //以订单号判断
                        if($order_id > $order['order_id'] && $order_index==$order['table_index']){
                            $order_index = $order_index - 1;
                        }
                    }
                }
            }
            if($tmp['foodshop_tables_discount']!=''){
                $tmp['foodshop_tables_discount'] = substr($tmp['foodshop_tables_discount'], 0, -1);
                $discountArr= explode('|', $tmp['foodshop_tables_discount']);
                $tables = [];
                foreach($discountArr as $k => $v){
                    $v = explode(':',$v);
                    $tables[$k] = $v;
                }
                if(intval($order_index)>count($tables)){
                    $mer_discount =  $tmp['other_discount'];
                    $mer_scale =  $tmp['other_scale'];
                }else{
                    if($orderinfo['status']==0){
                        $count_index = $order_index;
                    }else{
                        $count_index = $order_index - 1;
                    }
                    $mer_discount =  $tables[$count_index][1];
                    $mer_scale =   $tables[$count_index][2];
                    if(intval($order_index)==count($tables) && !$mer_discount){
                        $mer_discount =  $tmp['other_discount'];
                        $mer_scale =  $tmp['other_scale'];
                    }
                }
            }else{
                $mer_discount =  $tmp['other_discount'];
                $mer_scale =  $tmp['other_scale'];
            }
            $tmps['mer_discount'] = floatval($mer_discount / 10);
            $tmps['mer_scale'] = floatval($mer_scale);
           if($now_order){
               $can_discount_table_money = D('Foodshop_order')->count_price($now_order, 1);
               $reduceMoney = 0;
               if($tmps['mer_discount']!=0 && $tmps['mer_discount']!=100){
                   $reduceMoney = round($can_discount_table_money - $can_discount_table_money * ($tmps['mer_discount'] / 10),2);
               }
               $save = array('mer_table_discount'=>$tmps['mer_discount']*10,'mer_table_scale'=>$tmps['mer_scale'],'can_discount_table_money'=>$can_discount_table_money,'table_discount_money'=>$reduceMoney,'table_index'=>$order_index);
               $tmps['can_discount_table_money'] = $can_discount_table_money;
           }else{
               $save = array('mer_table_discount'=>$tmps['mer_discount']*10,'mer_table_scale'=>$tmps['mer_scale'],'table_index'=>$order_index);
           }

            D('foodshop_order')->where(array('order_id'=>$order_id))->save($save);

            if($orderinfo['status']==0) {
                $tmps['tables'] = $order_index+1;
            }else{
                $tmps['tables'] = $order_index;
            }
            return $tmps;
        }else{
            return false;
        }
    }

	public function wap_get_storeList_by_catid_quickpay($params,$is_wap=0)
	{
		$area_id = $params['area_id'];
		$circle_id = $params['circle_id'];
		$order = $params['sort'];
		$lat = $params['lat'];
		$long = $params['long'];
		$cat_fid = $params['cat_fid'];
		$cat_id = $params['cat_id'];
		$is_queue = $params['queue'];
		$keyword = $params['keyword'];
		$site_url  = C('config.site_url');

		$condition_field = '`s`.*, `m`.discount_percent, `m`.is_discount, `m`.other_discount, `m`.foodshop_tables_discount';

// 		$condition_where = 's.have_meal=1 AND s.status=1';
//		$condition_where = "s.city_id='" . C('config.now_city') . "' AND s.have_meal=1 AND s.status=1";//有餐饮展示优惠买单
		$condition_where = "s.city_id='" . C('config.now_city') . "' AND s.status=1";//无餐饮

		// if ($is_queue == 1) {
		// 	$condition_where .= ' AND f.is_queue=1';
		// } elseif ($is_queue == 0) {
		// 	$condition_where .= ' AND f.is_queue=0';
		// }
		//区域
		if($area_id || $circle_id){
			if ($circle_id) {
				$condition_where .= " AND `s`.`circle_id`='$circle_id'";
			} else {
				$condition_where .= " AND `s`.`area_id`='$area_id'";
			}
		}


		if ($cat_fid || $cat_id) {
			if ($cat_fid && $cat_id) {
				$relation = D('Meal_store_category_relation')->where(array(array('cat_fid' => $cat_fid, 'cat_id' => $cat_id)))->select();
			} elseif ($cat_fid) {
				$relation = D('Meal_store_category_relation')->where(array(array('cat_fid' => $cat_fid)))->select();
			} else {
				$relation = D('Meal_store_category_relation')->where(array(array('cat_id' => $cat_id)))->select();
			}
			$store_ids = array();
			foreach ($relation as $r) {
				if (!in_array($r['store_id'], $store_ids)) {
					$store_ids[] = $r['store_id'];
				}
			}
			if ($store_ids) {
				$condition_where .= ' AND s.store_id IN (' . implode(',', $store_ids) . ')';
			} else {
				return array('store_list' => null, 'store_count' => 0, 'totalPage' => 0);
			}
		}
		if ($keyword) {
			$condition_where .= " AND `s`.`name` LIKE '%{$keyword}%'";
		}
		$condition_where .= " AND `s`.`discount_txt`!=''";
		if ($lat && $long) {
			// if ($order == 'juli') {
				$condition_field .= ", ROUND(6378.138 * 2 * ASIN(SQRT(POW(SIN(({$lat}*PI()/180-`s`.`lat`*PI()/180)/2),2)+COS({$lat}*PI()/180)*COS(`s`.`lat`*PI()/180)*POW(SIN(({$long}*PI()/180-`s`.`long`*PI()/180)/2),2)))*1000) AS juli,m.isverify";
			// }
		} else {
			$order = $order == 'juli' ? '' : $order;
		}
		// 排序
		switch ($order) {
//             case 'rating':
//                 $order = '`f`.`sort` DESC, `f`.`score_mean` DESC, `f`.`sort` DESC';
//                 break;
//             case 'start':
//                 $order = '`f`.`sort` DESC, `f`.`create_time` DESC,`f`.`sort` DESC';
//                 break;
			case 'new':
//                $order = '`f`.`create_time` DESC, `f`.`sort` DESC';
				$order = '`s`.`last_time` DESC, `s`.`sort` DESC';
			case 'discount':
//                $order = '`mm`.`is_discount` DESC, `mm`.discount_percent asc, `f`.`sort` DESC';
				$order = '`mm`.`is_discount` DESC, `mm`.discount_percent asc, `s`.`sort` DESC';
			case 'juli':
                if ($lat && $long) {
                    $order = '`juli` asc';
                }
				break;
			default:
//                $order = '`f`.`sort` DESC, `f`.`create_time` DESC';
				$order = '`s`.`sort` DESC, `s`.`last_time` DESC';
		}

		import('@.ORG.wap_group_page');
		$sql_count = 'SELECT count(1) as cnt  FROM ' .C('DB_PREFIX') . 'merchant_store AS s LEFT JOIN ' . C('DB_PREFIX') . 'merchant mm ON mm.mer_id = s.mer_id WHERE ' . $condition_where . ' AND mm.status = 1';
		// $sql_count = 'SELECT count(1) as cnt  FROM ' . C('DB_PREFIX') . 'merchant_store_foodshop AS f INNER JOIN ' . C('DB_PREFIX') . 'merchant_store AS s ON f.store_id=s.store_id LEFT JOIN ' . C('DB_PREFIX') . 'merchant mm ON mm.mer_id = s.mer_id WHERE ' . $condition_where . ' AND mm.status = 1';

		$count_result = D()->query($sql_count);
		// echo D()->_sql();die;
		$count = isset($count_result[0]['cnt']) ? intval($count_result[0]['cnt']) : 0;

		$p = new Page($count, 10, 'page');
		if ($is_wap == 1) {
			$pagesize = 10;
			$star = ((isset($params['page']) ? intval($params['page']) : 0)) * $pagesize;
		} else if ($is_wap == 2)  {
			$pagesize = 10;
			$star =isset($params['page']) ? intval($params['page']) : 0;
		}else {
			$star = $p->firstRow;
			$pagesize = $p->listRows;
		}
		$sql = 'SELECT ' . $condition_field . ' FROM ' . C('DB_PREFIX') . 'merchant_store AS s LEFT JOIN ' . C('DB_PREFIX') . 'merchant AS m ON m.mer_id = s.mer_id WHERE ' . $condition_where . ' AND m.status=1 ORDER BY ' . $order . ' LIMIT ' . $star . ',' . $pagesize;
		// $sql = 'SELECT ' . $condition_field . ' FROM ' . C('DB_PREFIX') . 'merchant_store_foodshop AS f INNER JOIN ' . C('DB_PREFIX') . 'merchant_store AS s ON f.store_id=s.store_id LEFT JOIN ' . C('DB_PREFIX') . 'merchant AS m ON m.mer_id = s.mer_id WHERE ' . $condition_where . ' AND m.status=1 ORDER BY ' . $order . ' LIMIT ' . $star . ',' . $pagesize;
		$store_list = D()->query($sql);
		// var_dump(D()->_sql());
		import('@.ORG.longlat');
		$longlat_class = new longlat();
		$store_image_class = new store_image();
		$nowTime = time() - 864000;
		$user_= M('User')->where(array('uid'=>$_SESSION['user']['uid']))->find();
		$level_off=array();
		foreach ($store_list as $tmp) {
			$user_session['level'] = $user_['level'];

			if( !empty($user_session['level']) && !empty($_SESSION['user']['uid'])){
				$leveloff = !empty($tmp['leveloff']) ? unserialize(($tmp['leveloff'])) : '';
				/***type:0无优惠 1百分比 2立减******/
				if(!empty($leveloff)){
					$level_off=$leveloff[$user_session['level']];
					if($level_off['type']==1){
						$level_off['offstr']=L_('单价按原价').$level_off['vv'].'%来结算';
					}elseif($level_off['type']==2){
						$level_off['offstr']=L_('单价立减').$level_off['vv'].C('config.Currency_txt');
					}

				}
			}

			$tmps['is_new'] = 0;
			if ($tmp['create_time'] > $nowTime) {
				if (1 == C('config.is_open_merchant_foodshop_discount')) {
					$tmps['is_new'] = 1;
				}
			}
			$tmps['sys_discount'] = 0;
			if (1 == C('config.is_open_merchant_discount') && $tmp['is_discount'] == 1 && $tmp['discount_percent'] > 0) {
				$tmps['sys_discount'] = floatval($tmp['discount_percent'] / 10);
			}
			if (1 == C('config.is_open_merchant_foodshop_discount')) {
				$time_start = strtotime(date('Ymd'));
				$time_end = strtotime(date('Ymd 23:59:59'));
				$where['create_time'] = array('between',array($time_start,$time_end));
				$where['status'] = array('in',array(1,2,3,4));
				$where['mer_id'] = $tmp['mer_id'];
				$order_index = D('foodshop_order')->where($where )->count();

				if($tmp['foodshop_tables_discount']!=''){
					$tmp['foodshop_tables_discount'] = substr($tmp['foodshop_tables_discount'], 0, -1);
					$discountArr= explode('|', $tmp['foodshop_tables_discount']);
					$tables = [];
					foreach($discountArr as $k => $v){
						$v = explode(':',$v);
						$tables[$k] = $v;
					}
					if(intval($order_index)>=count($tables)){
						$mer_discount =  $tmp['other_discount'];
					}else{
						$mer_discount =  $tables[$order_index][1];
					}
				}else{
					$mer_discount =  $tmp['other_discount'];
				}
				$tmps['mer_discount'] = floatval($mer_discount / 10);
			}
			$tmps['name'] = $tmp['name'];
			$images = $store_image_class->get_allImage_by_path($tmp['pic_info']);
			$tmps['image'] = $images ? array_shift($images) : '';
			if($tmp['logo']){
	            $tmps['image'] = replace_file_domain($tmp['logo']);
	        }
			$tmps['phone'] = $tmp['phone'];
			$tmps['adress'] = $tmp['adress'];
			$tmps['is_book'] = $tmp['is_book'];
			$tmps['is_queue'] = $tmp['is_queue'];
			$tmps['is_takeout'] = $tmp['is_takeout'];
			$tmps['long'] = $tmp['long'];
			$tmps['lat'] = $tmp['lat'];
			$tmps['leveloff'] = unserialize($tmp['leveloff']);
			$tmps['level_off'] = $level_off;
			if ($tmp['juli']) {
				$tmps['range'] = getRange($tmp['juli']);
			} else if($location2['lat'] && $location2['lng']){
				$location2 = $longlat_class->gpsToBaidu($tmp['lat'], $tmp['long']);//转换腾讯坐标到百度坐标
				$jl = getDistance($location2['lat'], $location2['lng'], $lat, $long);
				$tmps['range'] = getRange($jl);
			}else{
				$tmps['range'] = '';
			}


			$tmps['state'] = 0;//根据营业时间判断
			$time = time();
			$open_1 = strtotime(date('Y-m-d') . $tmp['open_1']);
			$close_1 = strtotime(date('Y-m-d') . $tmp['close_1']);
			$open_2 = strtotime(date('Y-m-d') . $tmp['open_2']);
			$close_2 = strtotime(date('Y-m-d') . $tmp['close_2']);
			$open_3 = strtotime(date('Y-m-d') . $tmp['open_3']);
			$close_3 = strtotime(date('Y-m-d') . $tmp['close_3']);
			if ($tmp['open_1'] == '00:00:00' && $tmp['close_1'] == '00:00:00') {
				$tmps['state'] = 1;
			} elseif (($open_1 < $time && $close_1 > $time) || ($open_2 < $time && $close_2 > $time) || ($open_3 < $time && $close_3 > $time)) {
				$tmps['state'] = 1;
			}

			$tmps['group_list'] = array();
			$tmps['group_count'] = 0;
			$tmps['pay_in_store'] = C('config.pay_in_store');
			$tmps['store_pay'] = $site_url.str_replace('appapi.php','wap.php',U('My/pay', array('store_id' => $tmp['store_id'])));
			$tmps['url'] = $site_url.str_replace('appapi.php','wap.php',U('Foodshop/shop', array('store_id' => $tmp['store_id'])));
			$tmps['discount_txt'] = $tmp['discount_txt'] ? unserialize($tmp['discount_txt']) : array();
			$tmps['score_mean'] = $tmp['score_mean']==0 ? 5.0 : $tmp['score_mean'];
			$tmps['store_id'] = $tmp['store_id'];
			$result[$tmp['store_id']] = $tmps;
		}

		$store_id_arr = array_keys($result);
		if ($store_id_arr) {
			$store_ids = implode(',', $store_id_arr);
			$now_time = time();
			$sql = 'SELECT g.name,g.pic,g.group_id,g.price,g.old_price,g.wx_cheap,g.pin_num,g.sale_count,g.virtual_num,g.begin_time,gs.store_id FROM ' . C('DB_PREFIX') . 'group_store AS gs INNER JOIN ' . C('DB_PREFIX') . 'group AS g ON g.group_id=gs.group_id WHERE gs.store_id IN (' . $store_ids . ') AND `g`.`status`=1 AND `g`.`type`=1 AND `g`.`end_time`>\'' . $now_time . '\' ORDER BY `g`.`sort` DESC,`g`.`group_id` DESC';
			$groups = D()->query($sql);
			$group_image_class = new group_image();
			foreach ($groups as $row) {
				$tmp_pic_arr = explode(';', $row['pic']);
				$row['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0], 's');
				$row['url'] =$site_url.$this->get_group_url($row['group_id'], true);

				$row['price'] = floatval($row['price']);
				$row['old_price'] = floatval($row['old_price']);
				$row['wx_cheap'] = floatval($row['wx_cheap']);
				$row['is_start'] = 1;
				if ($now_time < $row['begin_time']) {
					$row['is_start'] = 0;
				}
				if($row['begin_time']+864000>time()&&$row['sale_count']==0){
					$row['sale_txt'] = L_('新品上架');
				}elseif($row['begin_time']+864000<time()&&$row['sale_count']==0){
					$row['sale_txt'] = '';
				}else{
					$row['sale_txt'] = L_('已售').floatval($row['sale_count']+$row['virtual_num']);
				}
				$row['begin_time'] = date("Y-m-d H:i:s", $row['begin_time']);
				if (isset($result[$row['store_id']])) {
					if ($result[$row['store_id']]['group_count'] < 2) {
						$result[$row['store_id']]['group_list'][] = $row;
					}
					$result[$row['store_id']]['group_count']++;
				}
			}
		}

		$_store_list = array();
		foreach ($result as $row) {
			$_store_list[] = $row;
		}
		$return['totalPage'] = $p->totalPage;//ceil($count / 10);
		$return['store_list'] = $_store_list;
		$return['store_count'] = $count;
		return $return;

	}
	
	public function get_table_service_alias($store_id){
		if(!$store_id) return '';
		$info = $this->where(array('store_id'=>$store_id))->field('table_service_fee_alias')->find();
		$name = $info['table_service_fee_alias']?$info['table_service_fee_alias']:L_('服务费');
		return $name;
	}

	
    //获取account_type
    public function get_account_type($store_id){
        if(!$store_id) return 1;
        $where = array();
        $where['store_id'] = $store_id;
        $data = $this->where($where)->field('account_type')->find();
        return $data['account_type']?$data['account_type']:1;
    }

    public function now_store($store_id, $is_return = true,$is_check_open_time = true)
	{ 
		$store = M('Merchant_store')->field(true)->where(array('store_id' => $store_id))->find();
		if (empty($store) && $is_return) {
			return array('err_code' => true, 'msg' => '不存在的店铺');
		} elseif ($store['status'] != 1 && $is_return) {
			return array('err_code' => true, 'msg' => '店铺状态异常');
		}  elseif ($store['have_meal'] != 1 && $is_return) {
			return array('err_code' => true, 'msg' => '店铺不支持餐饮');
		} else {
			if ($is_check_open_time == true) {
				$store['business_time'] = '';
				if ($store['open_1'] == '00:00:00' && $store['close_1'] == '00:00:00') {
					$store['business_time'] = '24小时营业';
				} else {
				    $now_time = time();
				    $is_close = 1;
				    $open_1 = strtotime(date('Y-m-d') . $store['open_1']);
				    $close_1 = strtotime(date('Y-m-d') . $store['close_1']);
				    if ($open_1 >= $close_1) {
				        $close_1 += 86400;
				    }
				    if ($open_1 < $now_time && $now_time < $close_1) {
				        $is_close = 0;
				    }
				    if ($store['open_2'] != '00:00:00' || $store['close_2'] != '00:00:00') {
				        $open_2 = strtotime(date('Y-m-d') . $store['open_2']);
				        $close_2 = strtotime(date('Y-m-d') . $store['close_2']);
				        if ($open_2 >= $close_2) {
				            $close_2 += 86400;
				        }
				        if ($open_2 < $now_time && $now_time < $close_2) {
				            $is_close = 0;
				        }
				    }
				    if ($store['open_3'] != '00:00:00' || $store['close_3'] != '00:00:00') {
				        $store['business_time'] .= ';' . substr($store['open_3'], 0, -3) . '~' . substr($store['close_3'], 0, -3);
				        $open_3 = strtotime(date('Y-m-d') . $store['open_3']);
				        $close_3 = strtotime(date('Y-m-d') . $store['close_3']);
				        if ($open_3 >= $close_3) {
				            $close_3 += 86400;
				        }
				        if ($open_3 < $now_time && $now_time < $close_3) {
				            $is_close = 0;
				        }
				    }
				}
				if ($is_close && $is_return) {
					return array('err_code' => true, 'msg' => '店铺不在营业中');
				}
			}
			
		}
		$store_image_class = new store_image();
		$images = $store_image_class->get_allImage_by_path($store['pic_info']);
		$store['image_list'] = $images;
		$store['image'] = $images ? array_shift($images) : array();


		$merchant = M('Merchant')->field(true)->where(array('mer_id' => $store['mer_id']))->find();
		if (empty($merchant) && $is_return) {
			return array('err_code' => true, 'msg' => '不存在的商家');
		} elseif ($merchant['status'] != 1 && $is_return) {
			return array('err_code' => true, 'msg' => '商家状态异常');
		} else {

		}
		$foodshop = M('Merchant_store_foodshop')->field(true)->where(array('store_id' => $store_id))->find();
		if (empty($foodshop) && $is_return) {
			return array('err_code' => true, 'msg' => '不存在的餐饮店铺');
		}

		$background = '';
		if(!empty($foodshop['background'])){
		    $goods_image_class = new foodshopstore_image();
		    $tmp_pic_arr = explode(';', $foodshop['background']);
		    $background = $goods_image_class->get_image_by_path($tmp_pic_arr[0]);
		    $background = $background && $background['image'] ? $background['image'] : '';
		}
		$foodshop['background'] = $background ? $background : '';

		return array('err_code' => false, 'data' => array_merge($store, $foodshop));
	}

}
?>