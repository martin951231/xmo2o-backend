<?php
/*
 * 地图处理
 *
 */
class ShopAction extends BaseAction{

    protected $send_time_type = [];

    public function _initialize()
    {
        parent::_initialize();
        $this->send_time_type = [
            L_('分钟'),
            L_('小时'),
            L_('天'),
            L_('周'),
            L_('月'),
        ];
    }

    public function index()
    {
        /*最多5个*/
        if ($_POST['user_lat']) {
            $now_city = D('Area')->cityMatching($_POST['user_lat'], $_POST['user_long']);
            C('config.now_city', $now_city['area_id']);
        }
        $return = array();
        $return['banner_list'] = D('Adver')->get_adver_by_key('wap_shop_index_top', 5);
        if (empty($return['banner_list'])) {
            $return['banner_list'] = array();
        } else {
            foreach ($return['banner_list'] as &$banner_value) {
                unset($banner_value['id']);
                unset($banner_value['bg_color']);
                unset($banner_value['cat_id']);
                unset($banner_value['status']);
                unset($banner_value['last_time']);
                unset($banner_value['sub_name']);
            }
        }
        $return['slider_list'] = D('Slider')->get_slider_by_key('wap_shop_slider', 0);
        if (empty($return['slider_list'])) {
            $return['slider_list'] = array();
        } else {
            foreach ($return['slider_list'] as &$slider_value) {
                unset($slider_value['id']);
                unset($slider_value['cat_id']);
                unset($slider_value['sort']);
                unset($slider_value['status']);
                unset($slider_value['last_time']);
            }
        }
        $return['adver_list'] = D('Adver')->get_adver_by_key('wap_shop_index_cente', 3);
        if (empty($return['adver_list'])) {
            $return['adver_list'] = array();
        } else {
            foreach ($return['adver_list'] as &$adver_value) {
                unset($adver_value['id']);
                unset($adver_value['bg_color']);
                unset($adver_value['cat_id']);
                unset($adver_value['status']);
                unset($adver_value['last_time']);
                unset($adver_value['sub_name']);
            }
        }
        $return['category_list'] = D('Shop_category')->lists(true);
        if (empty($return['category_list'])) {
            $return['category_list'] = array();
        } else {
            foreach ($return['category_list'] as &$cat_value) {
                unset($cat_value['cat_id']);
                unset($cat_value['cat_fid']);
                unset($cat_value['cat_sort']);
                unset($cat_value['cat_status']);
                unset($cat_value['show_method']);
                unset($cat_value['cue_field']);
                if ($cat_value['son_list']) {
                    foreach ($cat_value['son_list'] as &$cat_v) {
                        unset($cat_v['cat_id']);
                        unset($cat_v['cat_fid']);
                        unset($cat_v['cat_sort']);
                        unset($cat_v['cat_status']);
                        unset($cat_v['show_method']);
                        unset($cat_v['cue_field']);
                    }
                }
            }
        }
        $return['sort_list'] = array(
            array(
                'name' => L_('智能排序'),
                'sort_url' => 'juli'
            ),
            array(
                'name' => L_('销售数量最高'),
                'sort_url' => 'sale_count'
            ),
            array(
                'name' => L_('配送时间最短'),
                'sort_url' => 'send_time'
            ),
            array(
                'name' => L_('起送价最低'),
                'sort_url' => 'basic_price'
            ),
            array(
                'name' => L_('评分最高'),
                'sort_url' => 'score_mean'
            ),
            array(
                'name' => L_('最新发布'),
                'sort_url' => 'create_time'
            )
        );

        if ($this->config['shop_sort']) {
            $first_sort = array();
            foreach ($return['sort_list'] as $key => $value) {
                if ($value['sort_url'] == $this->config['shop_sort']) {
                    $first_sort = $value;
                    unset($return['sort_list'][$key]);
                    break;
                }
            }
            if ($first_sort) {
                array_unshift($return['sort_list'], $first_sort);
            }
        }

        $return['type_list'] = array(
            array(
                'name' => L_('全部'),
                'type_url' => 'all'
            ),
            array(
                'name' => L_('配送'),
                'type_url' => 'delivery'
            ),
            array(
                'name' => L_('自提'),
                'type_url' => 'pick'
            )
        );
        $this->returnCode(0, $return);
    }

	public function ajax_list(){
		if(empty($_POST)){
			$input_post = file_get_contents('php://input');
			$_POST = json_decode($input_post,true);
		}
		$key = $_POST['key'] ? htmlspecialchars($_POST['key']) : '';
		$cat_url = $_POST['cat_url'] ? htmlspecialchars($_POST['cat_url']) : 'all';
		$order = $_POST['sort_url'] ? htmlspecialchars($_POST['sort_url']) : 'juli';
		$deliver_type = $_POST['type_url'] ? htmlspecialchars($_POST['type_url']) : 'all';
		$lat = isset($_POST['user_lat']) ? htmlspecialchars($_POST['user_lat']) : 0;
		$long = isset($_POST['user_long']) ? htmlspecialchars($_POST['user_long']) : 0;
		cookie('userLocationLong', $long);
		cookie('userLocationLat', $lat);
		$page = isset($_POST['page']) ? intval($_POST['page']) : 1;
		$page = max(1, $page);
		$cat_id = 0;
		$cat_fid = 0;
        $village_id = $_POST['village_id'] + 0;
		if($_POST['village_id']){
			$now_village =  D('House_village')->get_one($_POST['village_id']);
			$lat = $now_village['lat'];
			$long = $now_village['long'];
		}
		if ($cat_url != 'all') {
			$now_category = D('Shop_category')->get_category_by_catUrl($cat_url);
			if ($now_category) {
				if ($now_category['cat_fid']) {
					$cat_id = $now_category['cat_id'];
					$cat_fid = $now_category['cat_fid'];
				} else {
					$cat_id = 0;
					$cat_fid = $now_category['cat_id'];
				}
			}
			if(empty($now_category)){
				$array = array('store_list' => array(), 'has_more' => false);
				$this->returnCode(0,$array);
			}
		}

		$where = array('deliver_type' => $deliver_type, 'order' => $order, 'lat' => $lat, 'long' => $long, 'cat_id' => $cat_id, 'cat_fid' => $cat_fid, 'page' => $page);
		$key && $where['key'] = $key;

		$merchantStoreShopDB = D('Merchant_store_shop');
        // 如果存在社区id ,获取社区关联商户id, 加入查询条件中
        if ($village_id) {
            $where['village_id'] = $village_id;
            $lists = D('House_village_store')->get_list_by_option($where);
        } else {
            $lists = $merchantStoreShopDB->get_list_by_option($where);
        }
		$return = array();
		$now_time = date('H:i:s');
		foreach ($lists['shop_list'] as $row) {
			$temp = array();
			$temp['store_id'] = $row['store_id'];
			$temp['openclassfyindex'] = $row['is_mult_class'];
			$temp['name'] = $row['name'];
			$temp['store_theme'] = $row['store_theme'];
			$temp['isverify'] = $row['isverify'];
			$temp['juli_wx'] = $row['juli'];
			$temp['range'] = $row['range'];
			$temp['image'] = $row['image'];
			$temp['star'] = round($row['score_mean'],1);
			$temp['month_sale_count'] = $row['sale_count'];
			$temp['is_new'] = ($row['create_time'] + 864000) < time() ? 0 : 1;
			$temp['delivery'] = $deliver_type == 'pick' ? 0 : $row['deliver'];//是否支持配送
			$temp['delivery'] = $temp['delivery'] ? true : false;
			$temp['delivery_time'] = $row['send_time'];//配送时长
			$temp['send_time_type'] = $row['send_time_type'];
			$temp['delivery_time_type'] = $this->send_time_type[$row['send_time_type']];
			$temp['delivery_price'] = floatval($row['basic_price']);//起送价
			$temp['delivery_money'] = floatval($row['delivery_fee']);//配送费
			$temp['delivery_system'] = $row['deliver_type'] == 0 || $row['deliver_type'] == 3 ? true : false;//是否是平台配送
			$temp['is_close'] = 1;
			$temp['deliver_type'] = $row['deliver_type'];
			if ($merchantStoreShopDB->checkTime($row)) {
			    $temp['is_close'] = 0;
			}
			$temp['is_close'] = $temp['is_close'] ? $temp['is_close'] : $row['is_close'];
			$temp['time'] = $merchantStoreShopDB->getBuniessName($row);
			

			$temp['coupon_list'] = array();
			if ($row['is_invoice']) {
				$temp['coupon_list']['invoice'] = floatval($row['invoice_price']);
			}
// 			$temp['coupon_list']['isdiscountgoods'] = 0;
			if ($row['isDiscountGoods']) {
			    $temp['coupon_list']['isdiscountgoods'] = 1;
			}
			if ($row['isdiscountsort']) {//店铺的商品分类折扣
			    $temp['coupon_list']['isdiscountsort'] = 1;
			}
			if ($row['store_discount'] != 0 && $row['store_discount'] != 100) {
				$temp['coupon_list']['discount'] = $row['store_discount']/10;
			}
			$system_delivery = array();
			foreach ($row['system_discount'] as $row_d) {
				if ($row_d['type'] == 0) {//新单
					$temp['coupon_list']['system_newuser'][] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']));
				} elseif ($row_d['type'] == 1) {//满减
					$temp['coupon_list']['system_minus'][] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']));
				} elseif ($row_d['type'] == 2) {//配送
					if ($row_d['full_money'] > 0 && $row_d['reduce_money'] > 0) {
						$system_delivery[] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']));
					}
				}
			}
			foreach ($row['merchant_discount'] as $row_m) {
				if ($row_m['type'] == 0) {
					$temp['coupon_list']['newuser'][] = array('money' => floatval($row_m['full_money']), 'minus' => floatval($row_m['reduce_money']));
				} elseif ($row_m['type'] == 1) {
					$temp['coupon_list']['minus'][] = array('money' => floatval($row_m['full_money']), 'minus' => floatval($row_m['reduce_money']));
				}
			}
			if ($row['deliver']) {
				if ($temp['delivery_system']) {
					$system_delivery && $temp['coupon_list']['delivery'] = $system_delivery;
				} else {
					if ($row['is_have_two_time']) {
						if ($row['reach_delivery_fee_type2'] == 0) {
							if ($row['basic_price'] > 0 && $row['delivery_fee2'] > 0) {
								$temp['coupon_list']['delivery'][] = array('money' => floatval($row['basic_price']), 'minus' => floatval($row['delivery_fee2']));
							}
						} elseif ($row['reach_delivery_fee_type2'] == 1) {
							//$temp['coupon_list']['delivery'] = array('money' => false, 'minus' => $row['delivery_fee']);
						} elseif ($row['reach_delivery_fee_type2'] == 2) {
							$row['delivery_fee2'] && $temp['coupon_list']['delivery'][] = array('money' => floatval($row['no_delivery_fee_value2']), 'minus' => floatval($row['delivery_fee2']));
						}
					} else {
						if ($row['reach_delivery_fee_type'] == 0) {
							if ($row['basic_price'] > 0 && $row['delivery_fee'] > 0) {
								$temp['coupon_list']['delivery'][] = array('money' => floatval($row['basic_price']), 'minus' => floatval($row['delivery_fee']));
							}
						} elseif ($row['reach_delivery_fee_type'] == 1) {
							//$temp['coupon_list']['delivery'] = array('money' => false, 'minus' => $row['delivery_fee']);
						} elseif ($row['reach_delivery_fee_type'] == 2) {
							$row['delivery_fee'] && $temp['coupon_list']['delivery'][] = array('money' => floatval($row['no_delivery_fee_value']), 'minus' => floatval($row['delivery_fee']));
						}
					}
				}
			}
			$temp['coupon_count'] = count($temp['coupon_list']);
			$temp['coupon_list'] = $this->parseCoupon($temp['coupon_list'],'array');
			$temp['deliver_name'] = isset($this->config['deliver_name']) && !empty($this->config['deliver_name']) ? $this->config['deliver_name'] : L_('平台配送');
			$return[] = $temp;
		}
		$array = array('store_list' => $return, 'has_more' => $lists['has_more'] ? true : false);
		$this->returnCode(0,$array);
    }

    public function parseCoupon($obj, $type)
    {
        $returnObj = array();
        foreach ($obj as $key => $value) {
            if ($key == 'invoice') {
                $returnObj[$key] = L_('满X1支持开发票，请在下单时填写发票抬头',C('config.Currency_symbol') . $obj[$key]);
            } elseif ($key == 'discount') {
                $returnObj[$key] = L_('店内全场X1折',$obj[$key]);
            } elseif ($key == 'isdiscountgoods') {
                $returnObj[$key] = L_('店内有部分商品限时优惠');
            } elseif ($key == 'isdiscountsort') {
				if($this->app_version<1200){
					continue;
				}
                $returnObj[$key] = L_('部分商品分类参与折扣优惠');
            } else {
                $returnObj[$key] = [];
                foreach ($obj[$key] as $k => $v) {

                    if ($v['percentage_status']==1) {
                        $dis_text = '享X2折';
                        $dis_num = floatval($v['minus']*10);
                    }else{
                        $dis_text = '减X2';
                        $dis_num = C('config.Currency_symbol') . floatval($v['minus']);
                    }

                    switch ($key) {
                        case 'newuser':
                            $returnObj[$key][] = L_('店铺首单X1'.$dis_text,array('X1'=>C('config.Currency_symbol') . $obj[$key][$k]['money'],'X2'=>$dis_num));
                            break;
                        case 'system_newuser':
                            if($v['order_count'] == 1){
                                $returnObj[$key][] = L_('商品满X1,平台首单X1'.$dis_text,array('X1'=>C('config.Currency_symbol') . $obj[$key][$k]['money'],'X2'=>$dis_num));
                            }else{
                                $returnObj[$key][] = L_('平台首X0单X1'.$dis_text,array('X1'=>C('config.Currency_symbol') . $obj[$key][$k]['money'],'X2'=>$dis_num));
                            }
                            break;
                        case 'system_minus':
                            $text = L_('平台优惠满X1'.$dis_text, array('X1' => C('config.Currency_symbol') . floatval($dval['money']), 'X2' => $dis_num));
                            $new_discount_list[] = array('type' => $key, 'time_select' => 1, 'value' => $text, 'minus' => floatval($dval['minus']));
                            break;
                        case 'delivery':
                            $returnObj[$key][] = L_('商品满X1,配送费'.$dis_text,array('X1'=>C('config.Currency_symbol') . $obj[$key][$k]['money'],'X2'=>$dis_num));
                            break;
                        default:
                            $returnObj[$key][] = L_('满X1'.$dis_text,array('X1'=>C('config.Currency_symbol') . $obj[$key][$k]['money'],'X2'=>$dis_num));
                            break;
                    }
                }
            }
        }
        
        $textObj = array();
        foreach ($returnObj as $key => $value) {
            if ($key == 'invoice' || $key == 'discount' || $key == 'isdiscountgoods' || $key == 'isdiscountsort') {
                $textObj[$key] = $value;
            } else {
                switch ($key) {
                    case 'system_newuser':
                        $textObj[$key] = L_('平台首单') . implode(',', $value);
                        break;
                    case 'system_minus':
                        $textObj[$key] = L_('平台优惠') . implode(',', $value);
                        break;
                    case 'newuser':
                        $textObj[$key] = L_('店铺首单') . implode(',', $value);
                        break;
                    case 'minus':
                        $textObj[$key] = L_('店铺优惠') . implode(',', $value);
                        break;
                    case 'system_minus':
                        $textObj[$key] = L_('平台优惠') . implode(',', $value);
                        break;
                    case 'delivery':
                        $textObj[$key] = implode(',', $value);
                        break;
                }
            }
        }
        if ($type == 'text') {
            $tmpObj = array();
            foreach ($textObj as $key => $value) {
                $tmpObj[] = $value;
            }
            return implode(';', $tmpObj);
        } else {
            $returnObj = array();
            foreach ($textObj as $key => $value) {
                $returnObj[] = array(
                    'type' => $key,
                    'value' => $value
                );
            }
            return $returnObj;
        }
    }

	public function ajax_shop(){
		if(empty($_POST)){
			$input_post = file_get_contents('php://input');
			$_POST = json_decode($input_post,true);
		}
		$store_id = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
		$where = array('store_id' => $store_id);
		$now_store = D('Merchant_store')->field(true)->where($where)->find();
		$now_shop = D('Merchant_store_shop')->field(true)->where($where)->find();
		$now_mer = M('Merchant')->field('isverify')->where(array('mer_id'=>$now_store['mer_id']))->find();
		if (empty($now_shop) || empty($now_store)) {
		    $this->returnCode(1, null, L_('该X1暂未完善信息，无法使用',$this->config['shop_alias_name']));
		}
		$auth_files = array();
		if ($now_store['auth'] > 2) {
			if (!empty($now_store['auth_files'])) {
				$auth_file_class = new auth_file();
				$tmp_pic_arr = explode(';', $now_store['auth_files']);
				foreach($tmp_pic_arr as $key => $value){
					$auth_file_temp = $auth_file_class->get_image_by_path($value);//array('title' => $value, 'url' => $auth_file_class->get_image_by_path($value, 's'));
					$auth_files[] = $auth_file_temp['image'];
				}
			}
		}
		$now_store['auth_files'] = $auth_files;
		
// 		$discounts = D('Shop_discount')->get_discount_byids(array($store_id));
		$discounts = D('Shop_discount')->getDiscounts($now_store['mer_id'], $store_id);
		$row = array_merge($now_store, $now_shop);
		
		$store = array();
		$store_image_class = new store_image();
		$images = $store_image_class->get_allImage_by_path($row['pic_info']);
	
		$store['id'] = $row['store_id'];
		$store['phone'] = trim($row['phone']);
		$store['long'] = $row['long'];
		$store['lng'] = $row['long'];
		$store['lat'] = $row['lat'];
		$store['isverify'] = $now_mer['isverify'];
		$store['store_theme'] = $row['store_theme'];

        // 验证是否有三级分类
        $row['is_mult_class'] = D('Merchant_store_shop')->checkMultClass($row['store_id']);
		if($row['is_mult_class']==1){
      		$store['store_theme'] = 0;
		}
		$store['adress'] = $row['adress'];
		$store['is_close'] = 1;
		
		$store['share_title'] = $row['name'];
		$store['share_content'] = $row['store_notice'];
		$store['share_image'] = !empty($images) ? array_shift($images) : '';
		
		if ($row['store_theme'] && empty($row['is_mult_class'])) {
		    $store['share_url'] = $this->config['site_url'] . 'wap.php?c=Shop&a=classic_shop&shop_id=' . $row['store_id'];
		} else {
		    $store['share_url'] = $this->config['site_url'] . '/wap.php?g=Wap&c=Shop&a=index&shop-id=' . $row['store_id'];
		}

		if (D('Merchant_store_shop')->checkTime($row)) {
		    $store['is_close'] = 0;
		}
		$store['is_close'] = $store['is_close'] ? $store['is_close'] : $row['is_close'];
		$store['time'] = D('Merchant_store_shop')->getBuniessName($row);
		$store['time_arr'] = D('Merchant_store_open_time')->get_one_time_by_store($store_id);
		
		if (M('Classify')->field(true)->where(array('token' => $row['mer_id']))->find()) {
            $store['home_url'] = $this->config['site_url'].'/wap.php?g=Wap&c=Index&a=index&token='.$row['mer_id'];
        } else {
            $store['home_url'] = '';
        }
		
		$store['name'] = $row['name'];
		$store['store_notice'] = $row['store_notice'];
		$store['txt_info'] = $row['txt_info'];
		$store['image'] = isset($images[0]) ? $images[0] : '';
		$store['images'] = $images ? $images : array();
		$store['auth_files'] = $auth_files;
		$store['star'] = round($row['score_mean'],1);
		$store['month_sale_count'] = $row['sale_count'];
		$store['is_new'] = ($row['create_time'] + 864000) < time() ? 0 : 1;
		$store['delivery'] = $row['deliver_type'] == 2 ? false : true;//是否支持配送
// 		$store['delivery_time'] = $row['send_time'];
        if($row['deliver_type']==2){ //只有自提情况下，让安卓也有起送价
            $store['delivery'] = true;
        }
		
		//配送时长
		if ($row['send_time_type'] == 0) {
		    $store['delivery_time'] = $row['sort_time'];
		} elseif ($row['send_time_type'] == 1) {
		    $store['delivery_time'] = floatval(round($row['sort_time'] / 60, 2));
		} elseif ($row['send_time_type'] == 2) {
		    $store['delivery_time'] = floatval(round($row['sort_time'] / 1440, 2));
		} elseif ($row['send_time_type'] == 3) {
		    $store['delivery_time'] = floatval(round($row['sort_time'] / 10080, 2));
		} elseif ($row['send_time_type'] == 4) {
		    $store['delivery_time'] = floatval(round($row['sort_time'] / 43200, 2));
		}
		
		$store['send_time_type'] = $row['send_time_type'];
		$store['delivery_time_type'] = $this->send_time_type[$row['send_time_type']];
		$store['delivery_price'] = floatval($row['basic_price']);//起送价
		
		//配送时间
		$deliverReturn = D('Deliver_set')->getDeliverInfo($row);
		$delivery_fee = $deliverReturn['delivery_fee'];
		$per_km_price = $deliverReturn['per_km_price'];
		$basic_distance = $deliverReturn['basic_distance'];
		
		$delivery_fee2 = $deliverReturn['delivery_fee2'];
		$per_km_price2 = $deliverReturn['per_km_price2'];
		$basic_distance2 = $deliverReturn['basic_distance2'];
		
		$delivery_fee3 = $deliverReturn['delivery_fee3'];
		$per_km_price3 = $deliverReturn['per_km_price3'];
		$basic_distance3 = $deliverReturn['basic_distance3'];
		
		$start_time = $deliverReturn['delivertime_start'];
		$stop_time = $deliverReturn['delivertime_stop'];
		
		$start_time2 = $deliverReturn['delivertime_start2'];
		$stop_time2 = $deliverReturn['delivertime_stop2'];
		
		$start_time3 = $deliverReturn['delivertime_start3'];
		$stop_time3 = $deliverReturn['delivertime_stop3'];
		
		$store['delivery_money'] = $delivery_fee;
		
		$time = time();
        $selectDeliverTime = 0;
        if (! ($start_time == $stop_time && $start_time == '00:00:00')) {
            $start_time = strtotime(date('Y-m-d') . ' ' . $start_time);
            $stop_time = strtotime(date('Y-m-d') . ' ' . $stop_time);
            if ($start_time > $stop_time) {
                if (strtotime(date('Y-m-d')) <= $time && $time <= $stop_time) {
                    $selectDeliverTime = 1;
                } else {
                    $stop_time2 += 86400;
                    if ($start_time <= $time && $time <= $stop_time) {
                        $selectDeliverTime = 1;
                    }
                }
            } elseif ($start_time <= $time && $time <= $stop_time) {
                $selectDeliverTime = 1;
            }
            if (! ($start_time2 == $stop_time2 && $start_time2 == '00:00:00')) {
                $start_time2 = strtotime(date('Y-m-d') . ' ' . $start_time2);
                $stop_time2 = strtotime(date('Y-m-d') . ' ' . $stop_time2);

                if ($start_time2 > $stop_time2) {
                    if (strtotime(date('Y-m-d')) <= $time && $time <= $stop_time2) {
                        $store['delivery_money'] = $delivery_fee2;
                        $selectDeliverTime = 2;
                    } else {
                        $stop_time2 += 86400;
                        if ($start_time2 <= $time && $time <= $stop_time2) {
                            $store['delivery_money'] = $delivery_fee2;
                            $selectDeliverTime = 2;
                        }
                    }
                } elseif ($start_time2 <= $time && $time <= $stop_time2) {
                    $store['delivery_money'] = $delivery_fee2;
                    $selectDeliverTime = 2;
                }
            }
            if (! ($start_time3 == $stop_time3 && $start_time3 == '00:00:00')) {
                $start_time3 = strtotime(date('Y-m-d') . ' ' . $start_time3);
                $stop_time3 = strtotime(date('Y-m-d') . ' ' . $stop_time3);

                if ($start_time3 > $stop_time3) {
                    if (strtotime(date('Y-m-d')) <= $time && $time <= $stop_time3) {
                        $store['delivery_money'] = $delivery_fee3;
                        $selectDeliverTime = 3;
                    } else {
                        $stop_time3 += 86400;
                        if ($start_time3 <= $time && $time <= $stop_time3) {
                            $store['delivery_money'] = $delivery_fee3;
                            $selectDeliverTime = 3;
                        }
                    }
                } elseif ($start_time3 <= $time && $time <= $stop_time3) {
                    $store['delivery_money'] = $delivery_fee3;
                    $selectDeliverTime = 3;
                }
            }
        }else{
            $selectDeliverTime = 1;
        }

        $set = D('Deliver_set')->field(true)->where(array('area_id' => $row['area_id'], 'status' => 1))->find();
        if (empty($set)) {
            $set = D('Deliver_set')->field(true)->where(array('area_id' => $row['city_id'], 'status' => 1))->find();
            if (empty($set)) {
                $set = D('Deliver_set')->field(true)->where(array('area_id' => $row['province_id'], 'status' => 1))->find();
            }
        }
        if($selectDeliverTime==1){
            if ($row['deliver_type'] == 0 || $row['deliver_type'] == 3) {
                if($row['s_is_open_own']==1){
                    $store['delivery_price'] = floatval($row['s_basic_price1']) > -1 ? floatval($row['s_basic_price1']) : (floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) :(floatval($set['basic_price1']) ? floatval($set['basic_price1']): (floatval($this->config['basic_price1']) ? floatval($this->config['basic_price1']) : floatval($this->config['basic_price']))));//起送价
                }else{
                    $store['delivery_price'] = floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) :(floatval($set['basic_price1']) ? floatval($set['basic_price1']): (floatval($this->config['basic_price1']) ? floatval($this->config['basic_price1']) : floatval($this->config['basic_price'])));//起送价
                }
                $store['extra_price'] = floatval($row['s_extra_price']) ? floatval($row['s_extra_price']) : floatval($this->config['extra_price']);
            } else {
                $store['delivery_price'] = floatval($row['basic_price1'])?floatval($row['basic_price1']):floatval($row['basic_price']);//起送价
                $store['extra_price'] = floatval($row['extra_price']);//不满起送价另付金额支持配送
            }
        }elseif($selectDeliverTime==2){
            if ($row['deliver_type'] == 0 || $row['deliver_type'] == 3) {
                if($row['s_is_open_own']==1) {
                    $store['delivery_price'] = floatval($row['s_basic_price2']) > -1 ? floatval($row['s_basic_price2']) : (floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) : (floatval($set['basic_price2']) ? floatval($set['basic_price2']) : (floatval($this->config['basic_price2']) ? floatval($this->config['basic_price2']) : floatval($this->config['basic_price']))));//起送价
                }else{
                    $store['delivery_price'] = floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) : (floatval($set['basic_price2']) ? floatval($set['basic_price2']) : (floatval($this->config['basic_price2']) ? floatval($this->config['basic_price2']) : floatval($this->config['basic_price'])));//起送价
                }
                $store['extra_price'] = floatval($row['s_extra_price']) ? floatval($row['s_extra_price']) : floatval($this->config['extra_price']);
            } else {
                $store['delivery_price'] = floatval($row['basic_price2'])?floatval($row['basic_price2']):floatval($row['basic_price']);//起送价
                $store['extra_price'] = floatval($row['extra_price']);//不满起送价另付金额支持配送
            }

        }elseif($selectDeliverTime==3){
            if ($row['deliver_type'] == 0 || $row['deliver_type'] == 3) {
                if($row['s_is_open_own']==1) {
                    $store['delivery_price'] = floatval($row['s_basic_price3']) > -1 ? floatval($row['s_basic_price3']) : (floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) : (floatval($set['basic_price3']) ? floatval($set['basic_price3']) : (floatval($this->config['basic_price3']) ? floatval($this->config['basic_price3']) : floatval($this->config['basic_price']))));//起送价
                }else{
                    $store['delivery_price'] = floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) : (floatval($set['basic_price3']) ? floatval($set['basic_price3']) : (floatval($this->config['basic_price3']) ? floatval($this->config['basic_price3']) : floatval($this->config['basic_price'])));//起送价
                }
                $store['extra_price'] = floatval($row['s_extra_price']) ? floatval($row['s_extra_price']) : floatval($this->config['extra_price']);
            } else {
                $store['delivery_price'] = floatval($row['basic_price3']) ? floatval($row['basic_price3']) : floatval($row['basic_price']);//起送价
                $store['extra_price'] = floatval($row['extra_price']);//不满起送价另付金额支持配送
            }
        }else{
            if ($row['deliver_type'] == 0 || $row['deliver_type'] == 3) {
                $store['delivery_price'] = floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) : floatval($this->config['basic_price']);//起送价
                $store['extra_price'] = floatval($row['s_extra_price']) ? floatval($row['s_extra_price']) : floatval($this->config['extra_price']);
            } else {
                $store['delivery_price'] = floatval($row['basic_price']);//起送价
                $store['extra_price'] = floatval($row['extra_price']);//不满起送价另付金额支持配送
            }
        }
        if($row['deliver_type']==2){ //只有自提的情况下，去掉加价送
            $store['extra_price'] = 0;
        }
        if($row['s_is_open_virtual']==1){
            $store['delivery_money'] = floatval($row['virtual_delivery_fee']);
        }else{
            $store['delivery_money'] = floatval($store['delivery_money']);
        }
// 		$store['delivery_money'] = $row['deliver_type'] == 0 ? C('config.delivery_fee') : $row['delivery_fee'];//配送费
// 		$store['delivery_money'] = floatval($store['delivery_money']);//配送费
		$store['delivery_system'] = $row['deliver_type'] == 0 || $row['deliver_type'] == 3 ? true : false;//是否是平台配送
		if (in_array($row['deliver_type'], array(2, 3, 4))) {
			$store['pick'] = 1;//是否支持自提
		} else {
			$store['pick'] = 0;//是否支持自提
		}
		$store['pack_alias'] = $row['pack_alias'];//打包费别名
		$store['freight_alias'] = $row['freight_alias'];//运费别名
		$store['coupon_list'] = array();
		if ($row['is_invoice']) {
			$store['coupon_list']['invoice'] = floatval($row['invoice_price']);
		}
		if ($row['store_discount'] != 0 && $row['store_discount'] != 100) {
			$store['coupon_list']['discount'] = floatval(round($row['store_discount']/10, 2));
		}
		$system_delivery = array();
		if (isset($discounts[0]) && $discounts[0]) {
			foreach ($discounts[0] as $row_d) {
				if ($row_d['type'] == 0) {//新单
					$store['coupon_list']['system_newuser'][] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']));
				} elseif ($row_d['type'] == 1) {//满减
					$store['coupon_list']['system_minus'][] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']));
				} elseif ($row_d['type'] == 2) {//配送
					$system_delivery[] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']));
				}
			}
		}
		if (isset($discounts[$store_id]) && $discounts[$store_id]) {
			foreach ($discounts[$store_id] as $row_m) {
				if ($row_m['type'] == 0) {
					$store['coupon_list']['newuser'][] = array('money' => floatval($row_m['full_money']), 'minus' => floatval($row_m['reduce_money']));
				} elseif ($row_m['type'] == 1) {
					$store['coupon_list']['minus'][] = array('money' => floatval($row_m['full_money']), 'minus' => floatval($row_m['reduce_money']));
				}
			}
		}
        if ($store['delivery']) {
            if ($store['delivery_system']) {
                $system_delivery && $store['coupon_list']['delivery'] = $system_delivery;
            } else {
                if ($selectDeliverTime == 1) {
                    if ($row['reach_delivery_fee_type'] == 0) {
                        if ($row['basic_price'] > 0 && $row['delivery_fee'] > 0) {
                            $store['coupon_list']['delivery'][] = array(
                                'money' => floatval($row['basic_price']),
                                'minus' => floatval($row['delivery_fee'])
                            );
                        }
                    } elseif ($row['reach_delivery_fee_type'] == 1) {
                        // $store['coupon_list']['delivery'] = array('money' => false, 'minus' => $row['delivery_fee']);
                    } else {
                        $row['delivery_fee'] && $store['coupon_list']['delivery'][] = array(
                            'money' => floatval($row['no_delivery_fee_value']),
                            'minus' => floatval($row['delivery_fee'])
                        );
                    }
                } elseif ($selectDeliverTime == 2) {
                    if ($row['reach_delivery_fee_type2'] == 0) {
                        if ($row['basic_price'] > 0 && $row['delivery_fee2'] > 0) {
                            $store['coupon_list']['delivery'][] = array(
                                'money' => floatval($row['basic_price']),
                                'minus' => floatval($row['delivery_fee2'])
                            );
                        }
                    } elseif ($row['reach_delivery_fee_type2'] == 1) {
                        // $store['coupon_list']['delivery'] = array('money' => false, 'minus' => $row['delivery_fee']);
                    } else {
                        $row['delivery_fee2'] && $store['coupon_list']['delivery'][] = array(
                            'money' => floatval($row['no_delivery_fee_value2']),
                            'minus' => floatval($row['delivery_fee2'])
                        );
                    }
                } elseif ($selectDeliverTime == 3) {
                    if ($row['reach_delivery_fee_type3'] == 0) {
                        if ($row['basic_price'] > 0 && $row['delivery_fee3'] > 0) {
                            $store['coupon_list']['delivery'][] = array(
                                'money' => floatval($row['basic_price']),
                                'minus' => floatval($row['delivery_fee3'])
                            );
                        }
                    } elseif ($row['reach_delivery_fee_type3'] == 1) {
                        // $store['coupon_list']['delivery'] = array('money' => false, 'minus' => $row['delivery_fee']);
                    } else {
                        $row['delivery_fee3'] && $store['coupon_list']['delivery'][] = array(
                            'money' => floatval($row['no_delivery_fee_value3']),
                            'minus' => floatval($row['delivery_fee3'])
                        );
                    }
                }
            }
        }
		$store['coupon_list'] = $this->parseCoupon($store['coupon_list'],'array');
        $store['merchant_links'] = array();
		if($this->config['open_scene_vr'] && $row['store_vr']){
            $vr['title'] = L_('720全景');
            $vr['icon'] = $this->config['site_url'].'/static/images/VR.png';
            $vr['link'] = $this->config['site_url'].$row['store_vr'];
            $vr['type'] = 1;
            $store['merchant_links'][] = $vr;
        }

		$today = date('Ymd');
		
		$product_list = D('Shop_goods')->get_list_by_storeid($store_id);
		$list = array();
		foreach ($product_list as $row) {
			$temp = array();
			$temp['cat_id'] = $row['sort_id'];
			$temp['cat_name'] = $row['sort_name'];
			$temp['sort_discount'] = strval(round(intval($row['sort_discount']) * 0.1, 2));
			foreach ($row['goods_list'] as $r) {
				$glist = array();
				$glist['product_id'] = $r['goods_id'];
				$glist['product_name'] = $r['name'];
				$glist['product_price'] = strval(floatval($r['price']));
				$glist['o_price'] = strval(floatval($r['o_price']));
				$glist['number'] = $r['number'];
				//如果设置了最小起购，限购就无效
				if ($r['min_num'] > 1) {
				    $glist['max_num'] = 0;
				} else {
				    $glist['max_num'] = $r['max_num'];
				}
				$glist['min_num'] = $r['min_num'];
				$glist['limit_type'] = $r['limit_type'];
				$glist['packing_charge'] = strval(floatval($r['packing_charge']));
				$glist['unit'] = $r['unit'];
				if (isset($r['pic_arr'][0])) {
					$glist['product_image'] = $r['pic_arr'][0]['url']['s_image'];
				}
				$glist['is_new'] = ($r['last_time'] + 864000) < time() ? 0 : 1;
				$glist['product_sale'] = $r['sell_count'];
				$glist['product_reply'] = $r['reply_count'];
				$glist['has_format'] = false;
				if ($r['spec_value'] || $r['is_properties']) {
					$glist['has_format'] = true;
				}
				
				if ($r['seckill_type'] == 1) {
				    $now_time = date('H:i');
				    $open_time = date('H:i', $r['seckill_open_time']);
				    $close_time = date('H:i', $r['seckill_close_time']);
				    
				    //秒杀库存的计算
				    if ($today == $r['sell_day']) {
				        $seckill_stock_num = $r['seckill_stock'] == -1 ? -1 : (intval($r['seckill_stock'] - $r['today_seckill_count']) > 0 ? intval($r['seckill_stock'] - $r['today_seckill_count']) : 0);
				    } else {
				        $seckill_stock_num = $r['seckill_stock'];
				    }
				} else {
				    $now_time = time();
				    $open_time = $r['seckill_open_time'];
				    $close_time = $r['seckill_close_time'];
				    $seckill_stock_num = $r['seckill_stock'] == -1 ? -1 : (intval($r['seckill_stock'] - $r['today_seckill_count']) > 0 ? intval($r['seckill_stock'] - $r['today_seckill_count']) : 0);
				}
				
				$r['is_seckill_price'] = false;
				$r['o_price'] = floatval($r['price']);
				if ($open_time < $now_time && $now_time < $close_time && floatval($r['seckill_price']) > 0 && $seckill_stock_num != 0) {
				    $r['price'] = floatval($r['seckill_price']);
				    $r['is_seckill_price'] = true;
				} else {
				    $r['price'] = floatval($r['price']);
				}
				
				$glist['is_seckill_price'] = $r['is_seckill_price'];
				$r['sell_day'] = $now_shop['stock_type'] ? $today : $r['sell_day'];
				if ($r['is_seckill_price']) {
				    $glist['stock'] = $seckill_stock_num;
				} else {
				    if ($today == $r['sell_day']) {
// 				        $glist['stock'] = $r['stock_num'] == -1 ? $r['stock_num'] : (intval($r['stock_num'] - $r['today_sell_count']) > 0 ? intval($r['stock_num'] - $r['today_sell_count']) : 0);
				        $glist['stock'] = $r['stock_num'];
                        if($r['sell_type'] == 2 && $r['stock_weight'] == -1){
                            $glist['stock'] = -1;
                        }elseif($r['sell_type'] == 2){
                            $goods_weight =  $r['weight_unit'] == 2 ? $r['weight'] * 1000 : $r['weight'];
                            $glist['stock'] = floor($r['stock_weight'] / $goods_weight);
                        }
				    } else {
// 				        $glist['stock'] = $r['stock_num'];
				        $glist['stock'] = $r['original_stock'];
                        if($r['sell_type'] == 2 && $r['original_weight'] == -1){
                            $glist['stock'] = -1;
                        }elseif($r['sell_type'] == 2){
                            $goods_weight =  $r['weight_unit'] == 2 ? $r['weight'] * 1000 : $r['weight'];
                            $glist['stock'] = floor($r['original_weight'] / $goods_weight);
                        }
				    }
				}
				if($_POST['app_type']==2){
					$glist['is_seckill_price']=$r['is_seckill_price'];
				}
				$temp['product_list'][] = $glist;
			}
			$list[] = $temp;
		}
		$array = array('store' => $store, 'product_list' => $list);
		$this->returnCode(0,$array);
	}

	public function ajax_goods(){
		$goods_id = isset($_POST['goods_id']) ? intval($_POST['goods_id']) : 1;
		$now_goods = D('Shop_goods')->get_goods_by_id($goods_id);
		foreach($now_goods['spec_list'] as &$value){
			$value['list'] = array_values($value['list']);
		}
		
		if($now_goods['spec_list']){
			foreach($now_goods['spec_list'] as &$value){
				$value['id_'] = $value['id'];
				unset($value['id']);
				foreach($value['list'] as &$v){
					$v['id_'] = $v['id'];
					unset($v['id']);
				}
			}
			$now_goods['spec_list'] = array_values($now_goods['spec_list']);
		}else{
			$now_goods['spec_list'] = array();
		}
		
		if($now_goods['properties_list']){
			foreach($now_goods['properties_list'] as &$value){
				$value['id_'] = $value['id'];
				unset($value['id']);
			}
			$now_goods['properties_list'] = array_values($now_goods['properties_list']);
		}else{
			$now_goods['properties_list'] = array();
		}
		
		if($now_goods['list']){
			foreach($now_goods['list'] as &$value){
				if ($now_goods['is_seckill_price']) {
					$value['price'] = strval(floatval($value['seckill_price']));
				} else {
				    $value['price'] = strval(floatval($value['price']));
				}
				if($value['properties']){
					foreach($value['properties'] as &$v){
						$v['id_'] = $v['id'];
						unset($v['id']);
					}
				}else{
					$value['properties'] = array();
				}
			}
		}else{
			$now_goods['list'] = $this->getObj();
		}
		$now_goods['content'] = '';
		if ($now_goods['des']) {
			if($_POST['Device-Id'] != 'wxapp'){
				$src = '<img src="'.file_domain().'/';
				$now_goods['des'] = str_replace('<img src="/', $src, $now_goods['des']);
				$now_goods['des'] = str_replace('<img alt="" src="/', $src, $now_goods['des']);
				$now_goods['des'] = str_replace('<img src="'.$this->config['site_url'].'/', $src, $now_goods['des']);
				$content = '<!DOCTYPE html>
							<html lang="zh-CN">
								<head>
									<meta charset="utf-8" />
									<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no, width=device-width"/>
									<meta name="apple-mobile-web-app-capable" content="yes"/>
									<meta name="apple-touch-fullscreen" content="yes"/>
									<meta name="apple-mobile-web-app-status-bar-style" content="black"/>
									<meta name="format-detection" content="telephone=no"/>
									<meta name="format-detection" content="address=no"/>
							<style type="text/css">
								article, aside, audio, body, canvas, caption, details, div, p, figure, footer, header, hgroup, html, iframe, img, mark, menu, nav, object, section, span, summary, table, tbody, td, tfoot, thead, tr, video, dl, dd {margin: 0;padding: 0;border: 0;}body {font-size: 14px;line-height: 1.5;-webkit-user-select: none;-webkit-touch-callout: none;background-color:white;padding-bottom: 10px;padding-left:10px;padding-right:10px;}
								table {border-collapse: collapse;border-spacing: 0;}
								 .content img {max-width: 100%!important;vertical-align: middle;}
							</style></head><body><div class="detail"><div class="content">' . $now_goods['des'] . '</div></div></body></html>';
				$now_goods['content'] = $content;
			}else{
				$src = '<img style="max-width:100%;" src="'.C('config.site_url').'/';
				$now_goods['des'] = str_replace('<img src="/', $src, $now_goods['des']);
				$now_goods['des'] = str_replace('<img src="'.$this->config['site_url'].'/', $src, $now_goods['des']);
				$now_goods['des'] = str_replace('<img src="'.str_replace('https://','http://',$this->config['site_url']).'/', $src, $now_goods['des']);
				$now_goods['des'] = str_replace('<img src="', '<img style="max-width:100%;vertical-align:top;" src="', $now_goods['des']);
			}
		}
		
		$now_goods['share_title'] = $now_goods['name'];
		$now_goods['share_content'] = $now_goods['name'];
		$now_goods['share_image'] = $now_goods['pic_arr'][0]['url'];
		$store = D('Merchant_store_shop')->field('store_id, store_theme')->where(array('store_id' => $now_goods['store_id']))->find();

        // 验证是否有三级分类
        $store['is_mult_class'] = D('Merchant_store_shop')->checkMultClass($store['store_id']);

		if ($store['store_theme'] && empty($store['is_mult_class'])) {
		    $now_goods['share_url'] = $this->config['site_url'] . 'wap.php?c=Shop&a=classic_shop&shop_id=' . $store['store_id'] . '&goods_id=' . $now_goods['goods_id'];
		} else {
		    $now_goods['share_url'] = $this->config['site_url'] . '/wap.php?g=Wap&c=Shop&a=index#shop-' . $store['store_id'] . '-' . $now_goods['goods_id'];
		}
		
		$now_goods['price'] = strval($now_goods['price']);
		$now_goods['old_price'] = strval($now_goods['old_price']);
        $now_goods['store_theme'] = $store['store_theme'];
		$this->returnCode(0, $now_goods);
	}
	
	public function ajax_reply(){
		C('VAR_PAGE','page');
		$reply_return = D('Reply')->get_page_reply_list(intval($_POST['store_id']), 3, $_POST['tab'], '', 0, true);
		if(!$reply_return['count']){
			$reply_return['count'] = 0;
		}else{
			foreach($reply_return['list'] as &$value){
				if(empty($value['goods'])){
					$value['goods'] = array();
				}
                if ($value['reply_pic']) {
                    $images = (new shop_order_reply_image())->get_allImage_by_path($value['reply_pic'], '-1');
                    $value['reply_pic'] = array_values($images);
                    foreach ($value['reply_pic'] as $k => $v) {
                        $value['reply_pic'][$k]['s_image'] = thumb_img($v['image'], 320, 320, 'fill');
                    }
                } else {
                    $value['reply_pic'] = [];
                }
			}
		}
		$shop = D('Merchant_store_shop')->field('score_mean, reply_deliver_score')->where(array('store_id' => intval($_POST['store_id'])))->find();
		$reply_return['score_mean'] = getFormatNumber($shop['score_mean']) ? round($shop['score_mean'],1) : 5.0;
		$reply_return['reply_deliver_score'] = getFormatNumber($shop['reply_deliver_score']) ? round($shop['reply_deliver_score'],1) : 5;
		$this->returnCode(0, $reply_return);
	}

	//计算价格准备提交订单
	public function ajax_cart()
	{
		$store_id = intval($_POST['store_id']);
		$deliverExtraPrice = isset($_POST['deliverExtraPrice']) ? intval($_POST['deliverExtraPrice']) : 0;//配送附加费
		$lat = isset($_POST['lat']) ? htmlspecialchars($_POST['lat']) : 0;
		$long = isset($_POST['long']) ? htmlspecialchars($_POST['long']) : 0;
		if(empty($long)){//兼容APP
            $long = isset($_POST['lng']) ? htmlspecialchars($_POST['lng']) : 0;
        }
		cookie('userLocationLong', $long);
		cookie('userLocationLat', $lat);
        fdump($_POST,'_POST');
		$cartid = isset($_POST['cartid']) ? htmlspecialchars(trim($_POST['cartid'])) : '';

        D('Card_new')->sync_tw_card($this->_uid,$store_id);

        //选择升级VIP
        $isUpgradeVip = isset($_POST['upgrade_vip_level']) ? intval($_POST['upgrade_vip_level']) : 0;
		if ($cartid && ($orderTemp = D('Shop_order_temp')->field(true)->where(array('cartid' => $cartid, 'store_id' => $store_id))->find())) {
		    $list= json_decode($orderTemp['info'], true);
		    $data = array();
		    foreach ($list as $rowset) {
		        if (isset($rowset['data']) && $rowset['data']) {
		            $data = array_merge($data, $rowset['data']);
		        }
		    }
		    $return = D('Shop_goods')->checkCart($store_id, $this->_uid, $data);
		} else {
            $productCart = $_POST['productCart'];
            if (!is_array($productCart)) {
                $productCart = json_decode(htmlspecialchars_decode($productCart), true);
            }
            if (empty($productCart)) {
                //新版购物车
                $cartMod = M('Cart');
                $cart = $cartMod->where(['uid' => $this->_uid, 'store_id' => $store_id, 'is_choose' => 1])->select();
                $product = [];
                foreach ($cart as $v) {
                    $info = json_decode($v['info'], true);
                    foreach ($info as &$i) {
                        if ($i['host_goods_id'] == 0) {
                            $i['count'] = $i['count'] * $v['num'];
                        }
                    }
                    $product = array_merge($product, $info);
                }
                $productCart = $product;
            }
            if (empty($productCart)) {
    			$this->returnCode(77000001);
    		}

            $upgradeVipLevel = 0;
            $upgradeVipInfo = D('User')->getNextVipLevel($this->_uid, $store_id);
            if ($isUpgradeVip == 1) {
                if ($upgradeVipInfo) {
                    $upgradeVipLevel = $upgradeVipInfo['level'];
                }
            }
    		$return = D('Shop_goods')->checkCart($store_id, $this->_uid, $productCart, 1, 0, false, 1, null, null, null, $upgradeVipLevel);
		}
        if ($return['error_code']) {
            //增加code返回，订单确认页用于判断是否返回上一页
            $this->returnCode(10110101, array(), $return['msg']);
        }

		$is_own = 0;
		$merchant_ownpay = D('Merchant_ownpay')->field('mer_id',true)->where(array('mer_id' => $return['mer_id']))->find();
		foreach ($merchant_ownpay as $ownKey => $ownValue) {
			$ownValueArr = unserialize($ownValue);
			if($ownValueArr['open']){
				//$is_own = 1;
			}
		}
		if ($is_own) {
			if ($return['delivery_type'] == 0) {
				$this->returnCode(1, null, L_('商家配置的配送信息不正确'));
			} elseif ($return['delivery_type'] == 3) {
				$return['delivery_type'] = 2;
			}
		}

		$address_id = isset($_POST['adress_id']) ? intval($_POST['adress_id']) : 0;
		$userLocationLong = cookie('userLocationLong');
		$userLocationLat = cookie('userLocationLat');
		if (empty($userLocationLong) || empty($userLocationLat)) {
		    $userLocationLat = $return['store']['lat'];
		    $userLocationLong = $return['store']['long'];
		}

		$user_address_temp = D('User_adress')->field(true)->where(array('uid' => $this->_uid))->order('`default` DESC,`adress_id` DESC')->select();
        $userOrderAddress = D('Shop_order')
            ->field('address_id')
            ->where([
                'uid' => $this->_uid,
                'store_id' => $store_id
            ])
            ->order('order_id DESC')
            ->find();
        
		$user_adress = array();
		$distance_array = array();
		$user_address_list = array();
		foreach ($user_address_temp as $address) {
		    $address['distance'] = getDistance($address['latitude'], $address['longitude'], $userLocationLat, $userLocationLong);
		    if ($return['store']['delivery_range_type'] == 0) {
		        $distance = getDistance($address['latitude'], $address['longitude'], $return['store']['lat'], $return['store']['long']);
		        $distance = $distance / 1000;
		        if ($return['store']['delivery_radius'] >= $distance) {
		            $address['is_deliver'] = true;
		        } else {
		            $address['is_deliver'] = false;
		        }
		    } else {
		        if ($return['store']['delivery_range_polygon']) {
		            if (!isPtInPoly($address['longitude'], $address['latitude'], $return['store']['delivery_range_polygon'])) {
		                $address['is_deliver'] = false;
		            } else {
		                $address['is_deliver'] = true;
		            }
		        } else {
		            $address['is_deliver'] = false;
		        }
		    }
			
			if ($address_id == $address['adress_id']) {
				$user_adress = $address;
				break;
			}
			
            if(
                $address_id <= 0 &&
                !empty($userOrderAddress['address_id']) &&
                $address['is_deliver'] && 
                $userOrderAddress['address_id'] == $address['adress_id']
            ){
                $user_adress = $address;
                $user_adress['default_select'] = true;
                break;
            }
            
			if(empty($address_id) && $address['default'] && $address['is_deliver']){
				$user_adress = $address;
				break;
			}

			if($address['is_deliver'] || $return['store']['deliver_type'] == 5){// 快递配送不校验距离
                $distance_array[] = $address['distance'];
                $user_address_list[] = $address;
            }
		}
		
		if (empty($user_adress) && $distance_array && $user_address_list) {
			array_multisort($distance_array, SORT_ASC, $user_address_list);
			$user_adress = $user_address_list[0];
		}
        if ($user_adress) {
            if (!($user_adress['latitude'] != 0 && $user_adress['longitude'] != 0))
                $this->returnCodeError(L_('地址不正确'));
            $province = D('Area')->get_area_by_areaId($user_adress['province'], false);
            $user_adress['province_txt'] = $province['area_name'];

            $city = D('Area')->get_area_by_areaId($user_adress['city'], false);
            $user_adress['city_txt'] = $city['area_name'];

            $area = D('Area')->get_area_by_areaId($user_adress['area'], false);
            $user_adress['area_txt'] = $area['area_name'];
			$user_adress['country_code'] = $area['country_code'];
			
			if(!$this->config['open_multilingual']){
				//$user_adress['show_address'] = $user_adress['area_txt'] . $user_adress['adress'] . $user_adress['detail'];
				$user_adress['show_address'] = $user_adress['adress'] . $user_adress['detail'];
			}else if($user_adress['country_code'] == 'nz'){
				$user_adress['show_address'] = trim(($user_adress['detail'] ? $user_adress['detail']  . ', ' : '') . $user_adress['adress']  . ', ' . $user_adress['area_txt'] . ' ' . $user_adress['city_txt'] . ' ' . $area['zip_code']);
			}else if($this->config['country_code'] == 'au'){
				$user_adress['show_address'] = trim(($user_adress['detail'] ? $user_adress['detail']  . ', ' : '') . $user_adress['adress']  . ', ' . $user_adress['area_txt'] . ' ' . $user_adress['province_txt'] . ' ' . $area['zip_code']);
			}else{
				$user_adress['show_address'] = trim(($user_adress['detail'] ? $user_adress['detail']  . ', ' : '') . $user_adress['adress']  . ', ' . $user_adress['area_txt'] . ' ' . $user_adress['city_txt']);
			}
        }
		
		//实际要支付的价格
		$extraPrice = $return['store']['extra_price'];
		
		if ($return['store']['basic_price'] <= floatval($return['price'] + $return['packing_charge']) || ($deliverExtraPrice == 1 && $extraPrice > 0)) {
            if ($return['store']['basic_price'] > floatval($return['price'] + $return['packing_charge'])){//自提未达到起送价不显示
                if($return['delivery_type']==3){
                    $return['delivery_type'] = 0;
                }elseif($return['delivery_type']==4){
                    $return['delivery_type'] = 1;
                }
            }
		} else {
		    if (in_array($return['delivery_type'], array(2, 3, 4))) {
		        if (!($deliverExtraPrice == 1 && $extraPrice > 0)) {
		            $return['delivery_type'] = 2;
		        }
		    } elseif (!($deliverExtraPrice == 1 && $extraPrice > 0)) {
		        $this->returnCode(1, null, L_('没有达到起送价，不予以配送'));
		    }
		}
		
        if ($return['delivery_type'] == 2 || $return['delivery_type'] == 5 || $return['store']['basic_price'] <= floatval($return['price'] + $return['packing_charge'])) {
		    $extraPrice = 0;
		}
		//计算配送费
		$delivery_fee_reduce = $return['delivery_fee_reduce'];
		$delivery_fee_reduce2 = $return['delivery_fee_reduce'];
		$delivery_fee_reduce3 = $return['delivery_fee_reduce'];
        $delivery_fee_reduce_type1 = 'manjian';
        $delivery_fee_reduce_type2 = 'manjian';
        $delivery_fee_reduce_type3 = 'manjian';
		if ($user_adress && $user_adress['is_deliver']) {
		    $distance = 0;
		    if (C('config.is_riding_distance') || C('config.map_config') == 'google') {
		        import('@.ORG.longlat');
		        $longlat_class = new longlat();
		        $distance = $longlat_class->getRidingDistance($return['store']['lat'], $return['store']['long'], $user_adress['latitude'], $user_adress['longitude']);
		        // if($distance == -1) $this->returnCode(1, null, L_('请求不到您的具体定位，请重试！'));
		    }
		    $distance || $distance = getDistance($return['store']['lat'], $return['store']['long'], $user_adress['latitude'], $user_adress['longitude']);
		    
			$distance = $distance / 1000;        
           
            // 配送时间段一
            $pass_distance = $distance > $return['basic_distance'] ? floatval($distance - $return['basic_distance']) : 0;
            $return['delivery_fee'] += round($pass_distance * $return['per_km_price'], 2);
            if ($return['delivery_fee'] < $return['delivery_fee_reduce']) {
                $delivery_fee_reduce = $return['delivery_fee'];
            }
            // 计算折扣优惠
            $return_delivery = D('Shop_goods')->delivery_discount($return,$return['delivery_fee'],0,$this->app_version);
            $delivery_fee_reduce_type1 = $return_delivery['discount_type'];

            $delivery_fee_reduce = ($return['delivery_fee_reduce']>$return['delivery_fee']) ? $return['delivery_fee'] : $return['delivery_fee_reduce'];
            $return['delivery_fee'] = $return['delivery_fee'] - $delivery_fee_reduce;
            $return['delivery_fee'] = $return['delivery_fee'] > 0 ? $return['delivery_fee'] : 0;

            // 配送时间段二
            $pass_distance = $distance > $return['basic_distance2'] ? floatval($distance - $return['basic_distance2']) : 0;
            $return['delivery_fee2'] += round($pass_distance * $return['per_km_price2'], 2);
            if ($return['delivery_fee2'] < $return['delivery_fee_reduce']) {
                $delivery_fee_reduce2 = $return['delivery_fee2'];
            }

            // 计算折扣优惠
            $return_delivery = D('Shop_goods')->delivery_discount($return,$return['delivery_fee2'],0,$this->app_version);
            $delivery_fee_reduce_type2 = $return_delivery['discount_type'];

            $delivery_fee_reduce2 = ($return['delivery_fee_reduce']>$return['delivery_fee2']) ? $return['delivery_fee2'] : $return['delivery_fee_reduce'];
            $return['delivery_fee2'] = $return['delivery_fee2'] - $delivery_fee_reduce2;
            $return['delivery_fee2'] = $return['delivery_fee2'] > 0 ? $return['delivery_fee2'] : 0;

            // 配送时间段三
            $pass_distance = $distance > $return['basic_distance3'] ? floatval($distance - $return['basic_distance3']) : 0;
            $return['delivery_fee3'] += round($pass_distance * $return['per_km_price3'], 2);
            if ($return['delivery_fee3'] < $return['delivery_fee_reduce']) {
                $delivery_fee_reduce3 = $return['delivery_fee3'];
            }

            // 计算折扣优惠
            $return_delivery = D('Shop_goods')->delivery_discount($return,$return['delivery_fee3'],0,$this->app_version);
            $delivery_fee_reduce_type3 = $return_delivery['discount_type'];

            $delivery_fee_reduce3 = ($return['delivery_fee_reduce']>$return['delivery_fee3']) ? $return['delivery_fee3'] : $return['delivery_fee_reduce'];
            $return['delivery_fee3'] = $return['delivery_fee3'] - $return['delivery_fee_reduce'];
            $return['delivery_fee3'] = $return['delivery_fee3'] > 0 ? $return['delivery_fee3'] : 0;

			$return['distance'] = $distance;
		}
		switch (intval($this->config['count_freight_charge_method'])) {
		    case 0:
		        break;
		    case 1:
		        $return['delivery_fee'] = ceil($return['delivery_fee'] * 10) / 10;
		        $return['delivery_fee2'] = ceil($return['delivery_fee2'] * 10) / 10;
		        $return['delivery_fee3'] = ceil($return['delivery_fee3'] * 10) / 10;
		        break;
		    case 2:
		        $return['delivery_fee'] = floor($return['delivery_fee'] * 10) / 10;
		        $return['delivery_fee2'] = floor($return['delivery_fee2'] * 10) / 10;
		        $return['delivery_fee3'] = floor($return['delivery_fee3'] * 10) / 10;
		        break;
		    case 3:
		        $return['delivery_fee'] = round($return['delivery_fee'] * 10) / 10;
		        $return['delivery_fee2'] = round($return['delivery_fee2'] * 10) / 10;
		        $return['delivery_fee3'] = round($return['delivery_fee3'] * 10) / 10;
		        break;
		    case 4:
		        $return['delivery_fee'] = ceil($return['delivery_fee']);
		        $return['delivery_fee2'] = ceil($return['delivery_fee2']);
		        $return['delivery_fee3'] = ceil($return['delivery_fee3']);
		        break;
		    case 5:
		        $return['delivery_fee'] = floor($return['delivery_fee']);
		        $return['delivery_fee2'] = floor($return['delivery_fee2']);
		        $return['delivery_fee3'] = floor($return['delivery_fee3']);
		        break;
		    case 6:
		        $return['delivery_fee'] = round($return['delivery_fee']);
		        $return['delivery_fee2'] = round($return['delivery_fee2']);
		        $return['delivery_fee3'] = round($return['delivery_fee3']);
		        break;
		}

        $date_list = $date_list2 = [];
        if ($return['delivery_type'] != 5) {
            //配送可选时间
            $date_list = D('Shop_goods')->getSelectTime_new($return, time(), 0, 900);
            //自提可选时间
            $date_list2 = D('Shop_goods')->getSelectTime_new($return, time(), 1, 1200);
        }

        foreach ($date_list as $key => $rowset) {
            $temp = array();
            asort($rowset);
            foreach ($rowset as $rv) {
                if ($rv['time_select']==1) {
                    $rv['delivery_free'] = $return['delivery_free'];
                    $rv['delivery_free_money'] = $return['delivery_free_money'];
                }elseif ($rv['time_select']==2) {
                    $rv['delivery_free'] = $return['delivery_free2'];
                    $rv['delivery_free_money'] = $return['delivery_free_money2'];
                }elseif ($rv['time_select']==3) {
                    $rv['delivery_free'] = $return['delivery_free3'];
                    $rv['delivery_free_money'] = $return['delivery_free_money3'];
                }
                $temp[] = $rv;
            }
            if(!empty($temp)){
	            if ($key == date('Y-m-d')) {
	                $d_list[] = array('ymd' => $key, 'show_date' => L_('今天'), 'date_list' => $temp);
	            } else if ($key == date('Y-m-d', time() + 86400)) {
	                $d_list[] = array('ymd' => $key, 'show_date' => L_('明天'), 'date_list' => $temp);
	            } else if ($key == date('Y-m-d', time() + 86400 * 2)) {
	                $d_list[] = array('ymd' => $key, 'show_date' => L_('后天'), 'date_list' => $temp);
	            } else {
	                $d_list[] = array('ymd' => $key, 'show_date' => date('m月d日', strtotime($key)), 'date_list' => $temp);
	            }
	        }
        }
        //员工专区自提时间
//        $set_employee_card_mer_id = C('config.set_employee_card_mer_id');
        $open_pick_time = M('Config_data')->where(['name'=>'open_pick_time'])->getField('value');
        $new_date_list = false;
        $setEmployeeCardMerId = C('config.set_employee_card_mer_id') ? explode(',',C('config.set_employee_card_mer_id')) : [];
        if($setEmployeeCardMerId && in_array($return['mer_id'],$setEmployeeCardMerId) && $open_pick_time == 1){
            $pick_time = json_decode(M('Config_data')->where(['name'=>'pick_time'])->getField('value'), true);
            if($pick_time){
                $new_date_list = [];
                $ShopOrderModel = M('Shop_order');
                $nowTime = time();
                foreach($pick_time as $key => $val)
                {
                    if($val['select_date'] < date('Y-m-d') || !isset($val['time_list']) || !is_array($val['time_list']) || count($val['time_list']) == 0){
                        continue;
                    }
                    //查询当前日期是否可售
                    $can_select = true;
                    if($val['limit_num'] != -1){
                        if($val['limit_num'] == 0){
                            $can_select = false;
                        }else{
                            $nowDayStarttime = strtotime($val['select_date']);
                            $nowDayEndtime = $nowDayStarttime + 96400;
                            $condition = [];
                            $condition['paid'] = 1;
                            $condition['mer_id'] = $return['mer_id'];
                            $condition['status'] = ['in', [0,1,2,3]];
                            $condition['expect_use_time'] = ['between', [$nowDayStarttime, $nowDayEndtime]];
                            $hasNum = $ShopOrderModel->where($condition)->count();
                            if($hasNum >= $val['limit_num']){
                                $can_select = false;
                            }
                        }
                    }
                    //时间段
                    $time_list = [];
                    foreach($val['time_list'] as $k => $v){
                        $start_time = strtotime($val['select_date'] . ' ' . $v['start_time']);
                        $end_time = strtotime($val['select_date'] . ' ' . $v['end_time']);
                        for($now = $start_time; $now <= $end_time; $now += 1200 ){
                            $start = date('H:i', $now);
                            $end = date('H:i', $now + 1200);
                            if($start == $v['end_time']){
                                continue;
                            }
                            if($now < $nowTime){
                                continue;
                            }
                            if($end > $v['end_time']){
                                $end = $v['end_time'];
                            }
                            $m = [];
                            $m['hour_minute'] = $start;
                            $m['time_select'] = 0;
                            $m['show_hour_minute'] = $start . '-' . $end;
                            $m['delivery_fee_old'] = 0;
                            $m['delivery_fee'] = 0;
                            $m['first_asap'] = true;
                            $m['unix_time'] = $now;
                            $m['can_select'] = $can_select;
                            $m['can_select_msg'] = $can_select ? '' : '当日配送已约满';
                            $time_list[$m['hour_minute']] = $m;
                        }
                        
                    }
                    $new_date_list[$val['select_date']] = $time_list;
                }
            }
        }
        if($new_date_list){
            $date_list2 = $new_date_list;
        }
        
        foreach ($date_list2 as $key => $rowset) {
            $temp = array();
            asort($rowset);
            foreach ($rowset as $rv) {
                $temp[] = $rv;
            }
            if(!empty($temp)){
	            if ($key == date('Y-m-d')) {
	                $d_list2[] = array('ymd' => $key, 'show_date' => L_('今天'), 'date_list' => $temp);
	            } else if ($key == date('Y-m-d', time() + 86400)) {
	                $d_list2[] = array('ymd' => $key, 'show_date' => L_('明天'), 'date_list' => $temp);
	            } else if ($key == date('Y-m-d', time() + 86400 * 2)) {
	                $d_list2[] = array('ymd' => $key, 'show_date' => L_('后天'), 'date_list' => $temp);
	            } else {
	                $d_list2[] = array('ymd' => $key, 'show_date' => date('m月d日', strtotime($key)), 'date_list' => $temp);
	            }
	        }
        }

		if (($return['delivery_type'] == 0 || $return['delivery_type'] == 1) && empty($d_list2)) {
		    $this->returnCode(1, null, '抱歉,当前暂无可配送的预计送达时间');
		}

		$pick_addr_id = isset($_GET['pick_addr_id']) ? $_GET['pick_addr_id'] : '';
		$pick_list = D('Pick_address')->get_pick_addr_by_merid($return['mer_id'], true, $store_id,false,[],1);

        //格式化自提地址
        foreach ($pick_list as &$p) {
            if ($this->config['country_code'] == 'au') {
                $p['name'] = $p['name'] . ' ' . $p['area_info']['area'] . ', ' . $p['area_info']['city'] . ' ' . $p['area_info']['province'] . ' ' . $p['area_info']['zip_code'];
            } else {
                $p['name'] = $p['area_info']['province'] . ' ' . $p['area_info']['city'] . ' ' . $p['area_info']['area'] . ' ' . $p['name'];
            }
        }


        foreach($pick_list as $key => $v){
            if(strpos($v['pick_addr_id'],'s')!==false){
                preg_match('/\d+/',$v['pick_addr_id'],$r);
                if(!in_array($r[0],array())){
                    unset($pick_list[$key]);
                    continue;
                }
            }
        }
        $pick_list = $pick_list ? array_values($pick_list) : array();
		if (empty($pick_list)) {
            if (cookie('userLocationLong') && cookie('userLocationLat')) {
                $lng_lat = [
                    'long' => cookie('userLocationLong'),
                    'lat' => cookie('userLocationLat')
                ];
            } else {
                $lng_lat = D('User_long_lat')->getLocation($_SESSION['openid'], 0);
            }
		    
		    $nowStore = $return['store'];
		    $distance = getDistance($lng_lat['lat'], $lng_lat['long'], $nowStore['lat'], $nowStore['long']);
		    $areaList = D('Area')->where(array('area_id' => array('in', array($nowStore['province_id'], $nowStore['city_id'], $nowStore['area_id']))))->getField('area_id,area_name');
		    $pick_list[] = array(
		        'name' => $nowStore['name'] . ' ' . $nowStore['adress'],
		        'area_info' => array(
		            'province' => $areaList[$nowStore['province_id']],
		            'city' => $areaList[$nowStore['city_id']],
		            'area' => $areaList[$nowStore['area_id']]
		        ),
		        'pick_addr_id' => 's' . $nowStore['store_id'],
		        'phone' => $nowStore['phone'],
		        'long' => $nowStore['long'],
		        'lat' => $nowStore['lat'],
		        'addr_type' => 1,
		        'distance' => $distance,
                'image' => $nowStore['logo']?replace_file_domain($nowStore['logo']): C('config.site_url').'/static/appapi/img/pick_address_default.png',
		    );
		}
		if ($pick_addr_id) {
			foreach ($pick_list as $k => $v) {
				if ($v['pick_addr_id'] == $pick_addr_id) {
					$pick_address = $v;
					break;
				}
			}
		} else {
            if(C('customization.life_tools') && (!$this->is_app_browser || $this->app_version >= 30450) && count($pick_list) > 1){// 定制不默认选中自提地址
                $pick_address = [];
            }else{
                $pick_address = $pick_list[0];
            }
		}
		if($pick_address){
			if(!$this->config['open_multilingual']){
				//$pick_address['show_address'] = $pick_address['area_info']['area'] . $pick_address['name'];
				$pick_address['show_address'] = $pick_address['name'];
			}else{
				$pick_address['show_address'] = $pick_address['name'] . ' ' . $pick_address['area_info']['area'];
			}
            $pick_address['show_address'] = htmlspecialchars_decode($pick_address['show_address']);
		}

		$pick_address_count = count($pick_list);
		if (isset($pick_address['long'])) $pick_address['lng'] = $pick_address['long'];
		unset($pick_address['long']);

		$now_store_category_relation = M('Shop_category_relation')->where(array('store_id'=>$return['store_id']))->find();
		$now_store_category = M(('shop_category'))->where(array('cat_id'=>$now_store_category_relation['cat_id']))->find();
		
		$goodsList = $return['goods'];
		$tempList = array();
		foreach ($goodsList as $goods) {
		    $goods['discount_type'] = $goods['discount_type'] ? true : false;
		    $goods['discount_type_data'] = in_array($goods['discount_type'], array(1, 3, 4)) ? 1 : (in_array($goods['discount_type'], array(2, 5)) ? 2 : 0);
		    $index = isset($goods['packname']) && $goods['packname'] ? $goods['packname'] : 0;
		    if (isset($tempList[$index])) {
		        $tempList[$index]['list'][] = $goods;
		    } else {
		        $tempList[$index] = array('name' => $goods['packname'], 'list' => array($goods));
		    }
		}
		if (count($tempList) == 1) {
		    $tempList[$index]['name'] = '';
		}
		$tempList = array_values($tempList);
		
		foreach($return['goods'] as &$v) {
            $v['discount_price'] = $v['discount_price'] + round($v['this_goods_vip_reduce'] / $v['num'],2);
            if(C('config.vip_level_discount_sequence')==1) { //满减后计算
                if ($return['vip_discount'] < 100 && $return['vip_discount'] > 0) {
                    if ($return['sorts_discount_type'] != 0) { //折扣最优
                        if ($return['optimalDiscountMoney'] > 0) {
                            $v['discount_price'] = $v['price'];
                        }
                    }
                }
            }
		    $v['old_price'] = strval($v['old_price']);
		    $v['discount_type'] = $v['discount_type'] ? true : false;//true:有折扣，false:无折扣
		    $v['discount_type_data'] = in_array($v['discount_type'], array(1, 3, 4)) ? 1 : (in_array($v['discount_type'], array(2, 5)) ? 2 : 0);//0:无折扣，1：店铺折扣，2：分类折扣
		    $v['old_price_all'] = getFormatNumber($v['old_price'] * $v['num']);
		    $v['discount_price_all'] = getFormatNumber($v['discount_price'] * $v['num']);
		}

		$result = array('goods_list' => $return['goods'], 'goodsList' => $tempList);

		$result['cue_field'] = array();
		if ($now_store_category['cue_field']) {
            $result['cue_field'] = unserialize($now_store_category['cue_field']);
            $result['cue_field'] = array_map(function ($r) {
                $r['value'] = '';
                return $r;
            }, $result['cue_field']);
			$cue_field = D('Shop_order')->where(['uid' => $this->_uid, 'store_id' => $store_id])->order('order_id desc')->limit(1)->getField('cue_field') ?? '';
			if ($cue_field) {
                $cue_field = unserialize($cue_field);
                foreach ($result['cue_field'] as $cfk => $cfv) {
                    $result['cue_field'][$cfk]['value'] = '';
                    foreach ($cue_field as $ofv) {
                        if ($cfv['name'] == $ofv['title']) {
                            $result['cue_field'][$cfk]['value'] = $ofv['txt'];
                        }
                    }
                }
            }
		}
		
		$new_discount_list = array();
		$deliver_minus_list = array();
		if (isset($return['discount_list']) && $return['discount_list']) {
            //修改展示顺序
            foreach($return['discount_list'] as $key => $value){
                if($key == 'newuser'){
                    unset($return['discount_list']['newuser']);
                    $return['discount_list']['newuser'] = $value;
                }elseif($key == 'minus'){
                    unset($return['discount_list']['minus']);
                    $return['discount_list']['minus'] = $value;
                }
            }
            foreach($return['discount_list'] as $key => $value){
                if($key == 'system_newuser'){
                    unset($return['discount_list']['system_newuser']);
                    $return['discount_list']['system_newuser'] = $value;
                }elseif($key == 'system_minus'){
                        unset($return['discount_list']['system_minus']);
                    $return['discount_list']['system_minus'] = $value;
                }elseif($key == 'delivery'){
                    unset($return['discount_list']['delivery']);
                    $return['discount_list']['delivery'] = $value;
                }elseif($key == 'delivery_zhekou'){
                    unset($return['discount_list']['delivery_zhekou']);
                    $return['discount_list']['delivery_zhekou'] = $value;
                }
            }
            foreach ($return['discount_list'] as $key => $dval) {
                if ($dval['percentage_status']==1) {
                    $dis_text = '享X2折';
                    $dis_num = floatval($dval['minus']*10);
                }elseif($dval['type']==2){
					$dis_text = '减X2';
                    $dis_num = C('config.Currency_symbol') . floatval($dval['minus']);
					if($dval['minus']>$return['delivery_fee']){
						$outprice = $dval['minus'] - $return['delivery_fee'];
						$dval['minus'] = $return['delivery_fee'];
						$dval['plat_money'] = $dval['minus'];
						 
					}
				}else{
                    $dis_text = '减X2';
                    $dis_num = C('config.Currency_symbol') . floatval($dval['minus']);
                }
                switch ($key) {
                    case 'plat_discount':
                        $text = L_('商品满X1,享X2折', array('X1' => C('config.Currency_symbol') . getFormatNumber($dval['money']), 'X2' => getFormatNumber($dval['minus'] * 10)));
                        $new_discount_list[] = array('type' => $key, 'time_select' => 1, 'value' => $text, 'minus' => getFormatNumber($dval['minus2']));
                        break;
                    case 'newuser':
                        if ($dval['order_count'] == 1) {
                            $text = L_('店铺首单满X1'.$dis_text, array('X1' => C('config.Currency_symbol') . getFormatNumber($dval['money']), 'X2' => $dis_num));
                        } else { 
                            $text = L_('店铺首X0单满X1'.$dis_text, array('X0' => $dval['order_count'], 'X1' => C('config.Currency_symbol') . getFormatNumber($dval['money']), 'X2' => $dis_num));
                            
                        }
                        $new_discount_list[] = array('type' => $key, 'time_select' => 1, 'value' => $text, 'minus' => getFormatNumber($dval['minus']));
                        break;
                    case 'minus':
                        if($dval['money'] > 0){
                            $text = L_('店铺优惠满X1'.$dis_text, array('X1' => C('config.Currency_symbol') . getFormatNumber($dval['money']), 'X2' => $dis_num));
                        }else{
                            $text = L_('店铺优惠'.$dis_text, array('X2' => $dis_num));
                        }
                        $new_discount_list[] = array('type' => $key, 'time_select' => 1, 'value' => $text, 'minus' => getFormatNumber($dval['minus']));
                        break;
                    case 'system_newuser':
                        if ($dval['order_count'] == 1) {
                            $text = L_('平台首单满X1'.$dis_text, array('X1' => C('config.Currency_symbol') . getFormatNumber($dval['money']), 'X2' => $dis_num));
                        } else {
                            $text = L_('平台首X0单满X1'.$dis_text, array('X0' => $dval['order_count'], 'X1' => C('config.Currency_symbol') . getFormatNumber($dval['money']), 'X2' => $dis_num));
                        }
                        $new_discount_list[] = array('type' => $key, 'time_select' => 1, 'value' => $text, 'minus' => getFormatNumber($dval['minus']));
                        break;
                    case 'system_minus':
                        if($dval['money'] > 0){
                            $text = L_('平台优惠满X1'.$dis_text, array('X1' => C('config.Currency_symbol') . getFormatNumber($dval['money']), 'X2' => $dis_num));
                        }else{
                            $text = L_('平台优惠'.$dis_text, array('X2' => $dis_num));
                        }
                        $new_discount_list[] = array('type' => $key, 'time_select' => 1, 'value' => $text, 'minus' => getFormatNumber($dval['minus']));
                        break;
                    case 'delivery':
                        if ($dval['order_count'] == 1) {
                            if($dval['money'] > 0){
                                $text = L_('首单满X1,配送费'.$dis_text, array('X1' => C('config.Currency_symbol') . getFormatNumber($dval['money']), 'X2' => $dis_num));
                            }else{
                                $text = L_('首单配送费立减X1', array('X1' => $dis_num));
                            }
                        }elseif($dval['order_count'] > 1){
                            if($dval['money'] > 0){
                                $text = L_('首X0单满X1,配送费'.$dis_text, array('X0' => $dval['order_count'], 'X1' => C('config.Currency_symbol') . getFormatNumber($dval['money']), 'X2' => $dis_num));
                            }else{
                                $text = L_('首X0单配送费立减X1', array('X0' => $dval['order_count'], 'X1' => $dis_num));
                            }
                        }else{
                            if($dval['money'] > 0){
                                $text = L_('商品满X1,配送费'.$dis_text, array('X1' => C('config.Currency_symbol') . getFormatNumber($dval['money']), 'X2' => $dis_num));
                            }else{
                                $text = L_('配送费立减X1', array('X1' => $dis_num ));
                            }
                        }
                        $deliver_minus_list[] = array('type' => $key, 'time_select' => 1, 'value' => $text, 'minus' => getFormatNumber($delivery_fee_reduce));
                        $deliver_minus_list[] = array('type' => $key, 'time_select' => 2, 'value' => $text, 'minus' => getFormatNumber($delivery_fee_reduce2));
                        $deliver_minus_list[] = array('type' => $key, 'time_select' => 3, 'value' => $text, 'minus' => getFormatNumber($delivery_fee_reduce3));
                        break;
                    case 'delivery_zhekou':  
                        if ($dval['order_count'] == 1) {
                            $text = L_('首单满X1,配送费'.$dis_text, array('X1' => C('config.Currency_symbol') . getFormatNumber($dval['money']), 'X2' => $dis_num));
                        }elseif($dval['order_count'] > 1){
                            $text = L_('首X0单满X1,配送费'.$dis_text, array('X0' => $dval['order_count'], 'X1' => C('config.Currency_symbol') . getFormatNumber($dval['money']), 'X2' => $dis_num));
                        }else{
                            $text = L_('商品满X1,配送费'.$dis_text, array('X1' => C('config.Currency_symbol') . getFormatNumber($dval['money']), 'X2' => $dis_num));
                        }
                        $deliver_minus_list[] = array('type' => $key, 'time_select' => 1, 'value' => $text, 'minus' => getFormatNumber($delivery_fee_reduce));
                        $deliver_minus_list[] = array('type' => $key, 'time_select' => 2, 'value' => $text, 'minus' => getFormatNumber($delivery_fee_reduce2));
                        $deliver_minus_list[] = array('type' => $key, 'time_select' => 3, 'value' => $text, 'minus' => getFormatNumber($delivery_fee_reduce3));
                        break;
                }
            }
		}

		$noDiscountList = array();
		if (isset($return['noDiscountList']) && $return['noDiscountList']) {
		    foreach ($return['noDiscountList'] as $nval) {
                if ($nval['percentage_status']==1) {
                    $dis_text = '享X2折';
                    $dis_num = getFormatNumber($nval['minus']*10);
                }else{
                    $dis_text = '减X2';
                    $dis_num = C('config.Currency_symbol') . getFormatNumber($nval['minus']);
                }
		        switch($nval['type']){
		            case 1:
		                $text = L_('平台首单满X2X1'.$dis_text.'X3活动与限时优惠、店铺/分类折扣、会员优惠不同享',array('X1'=>getFormatNumber($nval['money']),'X2'=>C('config.Currency_symbol'),'X3'=>floatval($nval['minus'])));
		                $noDiscountList[] = array('type' => $nval['type'], 'value' => $text, 'minus' => $dis_num);
		                break;
		            case 2:
		                $text = L_('平台优惠满X2X1'.$dis_text.'X3活动与限时优惠、店铺/分类折扣、会员优惠不同享',array('X1'=>getFormatNumber($nval['money']),'X2'=>C('config.Currency_symbol'),'X3'=>floatval($nval['minus'])));
		                $noDiscountList[] = array('type' => $nval['type'], 'value' => $text, 'minus' => $dis_num);
		                break;
		            case 3:
		                $text = L_('店铺首单满X2X1'.$dis_text.'X3活动与限时优惠、店铺/分类折扣、会员优惠不同享',array('X1'=>getFormatNumber($nval['money']),'X2'=>C('config.Currency_symbol'),'X3'=>floatval($nval['minus'])));
		                $noDiscountList[] = array('type' => $nval['type'], 'value' => $text, 'minus' => $dis_num);
		                break;
		            case 4:
		                $text = L_('店铺优惠满X2X1'.$dis_text.'X3活动与限时优惠、店铺/分类折扣、会员优惠不同享',array('X1'=>getFormatNumber($nval['money']),'X2'=>C('config.Currency_symbol'),'X3'=>floatval($nval['minus'])));
		                $noDiscountList[] = array('type' => $nval['type'], 'value' => $text, 'minus' => $dis_num);
		                break;
		        }
		    }
		}
		
		$result['deliver_minus_list'] = $deliver_minus_list;
		$result['discount_list'] = $new_discount_list;
		$result['noDiscountList'] = $noDiscountList;
		$result['price'] = strval($return['price']);
		$result['pay_price'] = strval(round($return['vip_discount_money'] - $return['sto_first_reduce'] - $return['sto_full_reduce'] - $return['sys_first_reduce'] - $return['sys_full_reduce'], 2));

//		//dump($return);
//		dump($result['price']);//调试代码勿删
//		echo 'VIP折扣后的总价 vip_discount_money : '.$return['vip_discount_money']. '<br>';
//		echo '店铺新单优惠的金额 sto_first_reduce : '.$return['sto_first_reduce']. '<br>';
//		echo '店铺满减优惠的金额 sto_full_reduce : '.$return['sto_full_reduce']. '<br>';
//		echo '平台新单优惠的金额 sys_first_reduce : '.$return['sys_first_reduce']. '<br>';
//		echo '平台满减优惠的金额 sys_full_reduce : '.$return['sys_full_reduce']. '<br>';
//		die;

        $hasReduceMoney = 0;
        if(C('config.vip_level_discount_sequence')==1) { //满减后计算
            if ($return['vip_discount'] < 100 && $return['vip_discount'] > 0) {
                if ($return['sorts_discount_type'] == 0) { //折上折
                    $hasReduceMoney = round($result['pay_price'] - round($result['pay_price'] * ($return['vip_discount'] * 0.01), 2), 2);
                    $text = L_('会员等级优惠： X1折',$return['vip_discount'] * 0.1);
                    $new_discount_list[] = array('type' => 'vip_level', 'time_select' => 1, 'value' => $text, 'minus' => getFormatNumber($hasReduceMoney));
                    $result['discount_list'] = $new_discount_list;
                } else {  //折扣最优
                    if ($return['optimalDiscountMoney'] > 0) {
                        $totalOptimalMoney = $return['optimalDiscountMoney'];
                        $result['vip_discount_money'] = $totalOptimalMoney;
                        $result['pay_price'] = round($totalOptimalMoney - round($return['sto_first_reduce'] + $return['sto_full_reduce'] + $return['sys_first_reduce'] + $return['sys_full_reduce'], 2), 2);//实际要支付商品的总价格
                        $hasReduceMoney = round($result['pay_price'] - round($result['pay_price'] * ($return['vip_discount'] * 0.01), 2), 2);
                        $text = L_('会员等级优惠： X1折',$return['vip_discount'] * 0.1);
                        $new_discount_list[] = array('type' => 'vip_level', 'time_select' => 1, 'value' => $text, 'minus' => getFormatNumber($hasReduceMoney));
                        $result['discount_list'] = $new_discount_list;
                    }
                }
            }
            $result['pay_price']  = round($result['pay_price'] - $hasReduceMoney,2);
        }else{ //正常计算
			if ($return['vip_discount'] < 100 && $return['vip_discount'] > 0) {
				$hasReduceMoney = $return['vip_level_reduce_money'];
				$text = L_('会员等级优惠： X1折',$return['vip_discount'] * 0.1);
				array_unshift($new_discount_list,array('type' => 'vip_level', 'time_select' => 1, 'value' => $text, 'minus' => getFormatNumber($hasReduceMoney)));
			}
            $result['discount_list'] = $new_discount_list;
        }
        
        if ($return['vip_discount'] == 100 && !empty($return['vip_level_reduce_money'])) {
            $result['discount_list'][] = [
                'type'        => 'vip_level',
                'time_select' => 1,
                'value'       => L_('会员等级优惠： 立减', $return['vip_level_reduce_money']),
                'minus'       => getFormatNumber($return['vip_level_reduce_money'])
            ];
        }

        $result['pay_price'] = $result['pay_price'] > 0 ? $result['pay_price'] : 0;
        $result['discount_price'] = strval(round($return['price'] - $result['pay_price'], 2));

		$result['delivery_type'] = $return['delivery_type'];
		$result['user_adress'] = $user_adress ? $user_adress : array('adress_id' => '', 'uid' => '', 'name' => '', 'phone' => '', 'province' => '', 'city' => '', 'area' => '', 'adress' => '', 'zipcode' => '', 'default' => '', 'longitude' => '', 'latitude' => '', 'detail' => '', 'province_txt' => '', 'city_txt' => '', 'area_txt' => '', 'is_deliver' => false);
		if($return['store']['deliver_type']==5) $result['user_adress']['is_deliver'] = true;

		$result['deliver_time_list'] = $d_list ? array_slice($d_list, 0, 7) : array();
        
		$result['deliver_unix_time'] = 0;
		if(!empty($result['deliver_time_list'])){
            $book = D('Merchant_store')->storeIsBooking($store_id);
            if(empty($book['is_booking'])){
                $result['deliver_unix_time'] = $result['deliver_time_list'][0]['date_list'][0]['unix_time'];
            }
        }
		if(!empty($pick_address)){
		    if($pick_address['distance'] > 1000){
                $pick_address['distance'] = round($pick_address['distance']/1000, 2).'km';
            } else {
                $pick_address['distance'] .= 'm';
            }
        }
		$result['pick_time_list'] = $d_list2 ? array_slice($d_list2, 0, 7) : array();
		$result['pick_address'] = $pick_address ? $pick_address : array('name' => '', 'area_info' => array('province' => '', 'city' => '', 'area' => ''), 'pick_addr_id' => '', 'phone' => '', 'lat' => '', 'lng' => '', 'addr_type' => '');
		$result['pick_address_count'] = $pick_address_count;
		$result['pack_alias'] = $return['store']['pack_alias'];
		$result['freight_alias'] = isset($return['store']['freight_alias']) && $return['store']['freight_alias'] ? $return['store']['freight_alias'] : L_('配送费');
		$result['pack_alias'] = isset($return['store']['pack_alias']) && $return['store']['pack_alias'] ? $return['store']['pack_alias'] : L_('打包费');
		$result['packing_charge'] = getFormatNumber($return['packing_charge']);
		$result['store_id'] = $return['store']['store_id'];
		$result['mer_id'] = $return['store']['mer_id'];
		$result['name'] = htmlspecialchars_decode($return['store']['name']);
		$result['images'] = $return['store']['images'];
		$result['delivery_fee'] = getFormatNumber($return['delivery_fee']);//起步配送费
		$result['basic_distance'] = getFormatNumber($return['basic_distance']);//起步距离
		$result['per_km_price'] = getFormatNumber($return['per_km_price']);//超出起步距离部分的距离每公里的单价
		$result['delivery_fee_reduce'] = getFormatNumber($delivery_fee_reduce);//配送费减免的金额
		$result['delivery_fee_reduce2'] = getFormatNumber($delivery_fee_reduce2);//配送费减免的金额
		$result['delivery_fee_reduce3'] = getFormatNumber($delivery_fee_reduce3);//配送费减免的金额
		$result['basic_distance2'] = getFormatNumber($return['basic_distance2']);//起步距离
		$result['per_km_price2'] = getFormatNumber($return['per_km_price2']);//超出起步距离部分的距离每公里的单价
		$result['delivery_fee2'] = getFormatNumber($return['delivery_fee2']);//起步配送费
        $result['delivery_fee3'] = getFormatNumber($return['delivery_fee3']);//起步配送费
		$result['basic_distance3'] = getFormatNumber($return['basic_distance3']);//起步距离
		$result['per_km_price3'] = getFormatNumber($return['per_km_price3']);//超出起步距离部分的距离每公里的单价
		$result['is_invoice'] = ($return['store']['is_invoice'] && $return['store']['invoice_price'] <= $result['price']) ? 1 : 0;
        $result['delivery_fee_reduce_type1'] = $delivery_fee_reduce_type1 ? $delivery_fee_reduce_type1 : 'manjian';
        $result['delivery_fee_reduce_type2'] = $delivery_fee_reduce_type2 ? $delivery_fee_reduce_type2 : 'manjian';
        $result['delivery_fee_reduce_type3'] = $delivery_fee_reduce_type3 ? $delivery_fee_reduce_type3 : 'manjian';
		
		$plat_discount = D('Merchant')->getDiscount($return['store']['mer_id']);
		$plat_discount = floatval($plat_discount);
		if ($plat_discount) {
		    $result['plat_discount'] = floatval(round($plat_discount /10, 2));
		} else {
		    $result['plat_discount'] = 10;
		}
		$result['userphone'] = $return['userphone'];
		$result['deliver_name'] = isset($this->config['deliver_name']) && !empty($this->config['deliver_name']) ? $this->config['deliver_name'] : L_('平台配送');
		
		//是否提醒用户期望送达时间过久远
		$result['showSendTimeTip'] = false;
        if ($return['delivery_type'] != 5) {
            $defaultSendTime = D('Shop_goods')->defaultSendTime;
            if($defaultSendTime && $result['deliver_time_list'] && $this->app_version >= 1400){
                if($return['delivery_type'] == 2){
                    //针对自提要读取自提时间
                    $nextSendTime = strtotime($result['pick_time_list'][0]['ymd'].' '.$result['pick_time_list'][0]['date_list'][0]['hour_minute']);
                    $tipDate = $result['pick_time_list'][0]['show_date'] . ' ' . $result['pick_time_list'][0]['date_list'][0]['hour_minute'];
                    $result['deliver_time_list'] = [$result['pick_time_list'][0]];
                }else{
                    $nextSendTime = strtotime($result['deliver_time_list'][0]['ymd'].' '.$result['deliver_time_list'][0]['date_list'][0]['hour_minute']);
                    $tipDate = $result['deliver_time_list'][0]['show_date'] . ' ' . $result['deliver_time_list'][0]['date_list'][0]['hour_minute'];
                }
                if($nextSendTime - $defaultSendTime > 900 ){
                    $result['showSendTimeTip'] = true;
                    $result['showSendTimeTip_title'] = L_('期望到达时间提醒');
                    $result['showSendTimeTip_content'] = L_('请注意，离现在最近的可选择到达时间是X1',['X1'=> $tipDate]);
                }
            }
        }

		//自提联系手机号
        //$result['pick_phone'] = D('Shop_order')->where(array('uid' => $this->_uid, 'is_pick_in_store' => 2, 'username' => ['neq', ''], 'userphone' => ['neq', '']))->order('order_id DESC')->getField('userphone') ?: '';
		$pick_last_order = D('Shop_order')->field('userphone,pick_username')->where(array('uid' => $this->_uid, 'is_pick_in_store' => 2, 'username' => ['neq', ''], 'userphone' => ['neq', '']))->order('order_id DESC')->find();
        $buyer_info = D('User')->where(['uid'=>$this->_uid])->find();
        $result['pick_phone']     =  $pick_last_order['userphone']?$pick_last_order['userphone']: $buyer_info ['phone'];
        $result['pick_username']  =  $pick_last_order['pick_username']?$pick_last_order['pick_username']: $buyer_info ['nickname'];


        if (!is_null(C('config.open_selfpick_auto_input_user')) && !C('config.open_selfpick_auto_input_user')) { // 定制不自动填入自提人信息
            $result['pick_phone'] = '';
            $result['pick_username'] = '';
        }
        

        $result['goods_list'] = D('Shop_goods')->replace_goods_name($result['goods_list']);
        // 组合附属商品
        $result['goods_list'] =  D('Shop_goods')->combination_data($result['goods_list'], 'str', 0);
        foreach ($result['goodsList'] as $key => $value){
            $result['goodsList'][$key]['list'] = D('Shop_goods')->combination_data($value['list'], 'str', 0);
        }
        //记录商品总金额+打包费给满减活动使用
        $goodsTotalMoney = array_sum(array_column($return['goods'],'discount_price_all'));
        $result['repurchase_list'] =  D('Shop_goods')->get_repurchase($store_id,$goodsTotalMoney + $result['packing_charge']);

        // 返回是否开启号码保护
        $result['protect_phone_number'] =  C('config.protect_phone_number');

        //判断是否开启定时配送
        $result['timing_delivery'] = 1;
        $result['timing_delivery_time_list'] = [];
		$this->returnCode(0, $result);
	}

	//提交订单
	public function save_order()
	{
		$store_id = intval($_POST['store_id']);
		if(empty($store_id)){
            $this->returnCodeError('店铺ID不能为空！');
        }
		
		$merchantStore = M('merchant_store')->where(['store_id' => $store_id, 'status' => 1])->find();
        if(empty($merchantStore)){
            $this->returnCodeError('当前店铺未正常营业！暂无法下单！');
        }
		
		$cartid = isset($_POST['cartid']) ? htmlspecialchars(trim($_POST['cartid'])) : '';
        //【直播+短视频平台】 https://www.tapd.cn/47145533/prong/stories/view/1147145533001002522
        $live_id = $_POST['live_id'];
        $video_id = $_POST['video_id'];

        // 超值换购
        $rep_id = isset($_POST['rep_id']) ? intval($_POST['rep_id']) : 0;
        $rep_goods_id = isset($_POST['rep_goods_id']) ? $_POST['rep_goods_id'] : 0;
        if ($rep_id) { 
            $rep_return = D('Shop_goods')->addRepurchase($store_id, $rep_id, $rep_goods_id);
            if ($rep_return['error'] == 1) {
                $this->returnCode(1, null, $rep_return['msg']);
            }elseif ($rep_return['error'] == 2) {
                $this->returnCode(1, null, L_('抱歉！ 您选中的超值换购商品已被商家下架，暂时无法继续换购'));
            }elseif ($rep_return['error'] == 0) {
            }
        }

        $upgradeVipLevel = 0;
        $upgradePrice = 0;
        $currentUserLevel = 0;
		if ($cartid && ($orderTemp = D('Shop_order_temp')->field(true)->where(array('cartid' => $cartid, 'store_id' => $store_id))->find())) {
		    $list= json_decode($orderTemp['info'], true);
		    $data = array();
		    foreach ($list as $rowset) {
		        if (isset($rowset['data']) && $rowset['data']) {
		            $data = array_merge($data, $rowset['data']);
		        }
		    }

            // 超值换购
            if ($rep_return['info']) {
                $data = array_merge($data, $rep_return['info']);
            }

		    $return = D('Shop_goods')->checkCart($store_id, $this->_uid, $data);
		} else {
            $productCart = $_POST['productCart'];
            if (empty($productCart)) {
                $cart = M('Cart')->where(['uid' => $this->_uid, 'store_id' => $store_id, 'is_choose' => 1])->select();
                $productCart = [];
                $com_id = 0;// 组合套餐ID
                foreach ($cart as $v) {
                    $info = json_decode($v['info'], true);
                    foreach ($info as &$i) {
                        if ($i['host_goods_id'] == 0) {
                            $i['count'] = $i['count'] * $v['num'];
                        }
                    }
                    $com_id = $v['com_id'];
                    $productCart = array_merge($productCart, $info);
                }
            }
            if (empty($productCart)) {
                $this->returnCode(10100003);
            }
    		if (!is_array($productCart)) {
    			$productCart = json_decode(htmlspecialchars_decode($productCart), true);
    		}
            
            // 超值换购
            if ($rep_return['info']) {
                $productCart = array_merge($productCart, $rep_return['info']);
            }

            //选择升级VIP
            $isUpgradeVip = isset($_POST['upgrade_vip_level']) ? intval($_POST['upgrade_vip_level']) : 0;
            $upgradeVipInfo = D('User')->getNextVipLevel($this->_uid, $store_id);
            if ($isUpgradeVip == 1) {
                if ($upgradeVipInfo) {
                    $upgradeVipLevel = $upgradeVipInfo['level'];
                    $upgradePrice = $upgradeVipInfo['use_money'];
                    $currentUserLevel = $upgradeVipInfo['current_user_level'];
                }
            }
            $upgradeVipInfo=0;

            //是否选中开通平台会员卡
            $isOpenMembershipCard = isset($_POST['open_membership_card']) ? intval($_POST['open_membership_card']) : 0;
            if ($isOpenMembershipCard) {
                $membershipCard = (new Membership_cardModel())->getCurrentMembershipCard();
            }
            $membershipCardPrice = $membershipCard['price'] ?? 0;

            $return = D('Shop_goods')->checkCart($store_id, $this->_uid, $productCart, 1, 0, false, 1, null, null, null, $upgradeVipLevel);
		}

		if ($return['error_code']) {
			$this->returnCode(1, null, $return['msg']);
		}

        $goodsIds = array_column($return['goods'],'goods_id');
        $checkRs = D('Shop_goods')->checkBeforePay($goodsIds);
        if ($checkRs['error_no']) {
            $this->returnCode(1, null, $checkRs['error_msg']);
        }
		
		//下单减库存，防止并发超卖
        if ($return['store']['reduce_stock_type'] && empty($_POST['tmp_order_id'])) {
            $check_db = M('shop_goods_stock_check');
            $check_data = [];
            $check_data['goods'] = json_encode($return['goods']);
            $check_id = $check_db->add($check_data);

            $arr = array();
            $arr['param'] = serialize(array());
            $arr['plan_time'] = -100;
            $arr['space_time'] = 0;
            $arr['add_time'] = time();
            $arr['file'] = 'shop_goods_stock_check';
            $arr['time_type'] = 1;
            $arr['unique_id'] = 'shop_goods_stock_check';
			$arr['rand_number'] = mt_rand(1, max(C('config.sub_process_num'), 3));
            M('process_sub_plan')->data($arr)->add();

            $process = true;
            $i = 0;
            while ($process) {
                $check_info = $check_db->where(array('id' => $check_id))->find();
                if ($check_info['status'] == 1) {//成功
                    $process = false;
                } elseif ($check_info['status'] == 2) {//失败
                    $process = false;
                    $check_goods = json_decode($check_info['msg']);
                    $check_goods_str = implode(",", $check_goods);
                    $check_tips = '抱歉，商品：' . $check_goods_str . '，库存不足！';
                    $this->returnCode(1, null, $check_tips);
                }
                if ($i >= 50) {
                    $process = false;
                    $this->returnCode(1, null, '抱歉，系统繁忙，请稍后再试！');
                }
                $i++;
                usleep(300000);
            }
        }


// 		if (IS_POST) {
			$invoice_head = isset($_POST['invoice_head']) ? htmlspecialchars($_POST['invoice_head']) : '';  //cue_field
			$order_from = $_POST['app_type'] == 'packapp' ? 0 : (isset($_POST['order_from']) ? intval($_POST['order_from']) : 3);
			$address_id = isset($_POST['address_id']) ? intval($_POST['address_id']) : 0;
			$pick_id = isset($_POST['pick_id']) ? htmlspecialchars($_POST['pick_id']) : 0;
// 			$pick_id = substr($pick_id, 1);
			$deliver_type = isset($_POST['deliver_type']) ? intval($_POST['deliver_type']) : 0;
			$arrive_time = isset($_POST['expect_use_time']) ? htmlspecialchars($_POST['expect_use_time']) : 0;
			$arrive_time = str_replace(L_("今天"), date('Y-m-d'), $arrive_time);
			$arrive_time = str_replace(L_("明天"), date('Y-m-d', strtotime('+1 day')), $arrive_time);
			$arrive_time = str_replace(L_("后天"), date('Y-m-d', strtotime('+2 day')), $arrive_time);
			$note = isset($_POST['desc']) ? htmlspecialchars($_POST['desc']) : '';
			$extraPrice = 0;
			if (floatval($return['price'] + $return['packing_charge']) < $return['store']['basic_price']) {
			    $extraPrice = $return['store']['extra_price'];
			    if (empty($extraPrice)) {
			        if (in_array($return['store']['deliver_type'], array(2, 3, 4))) {
			            $deliver_type = 1;
			        } else {
			            $this->returnCode(1, null, L_('订单没有达到起送价，不予配送'));
			        }
			    }
			}

			//自提订单判断自提人必须填写
            $_POST['pick_username'] = $_POST['pick_username'] ?: ($_POST['username'] ?: '');
            if ($deliver_type == 1 && empty($_POST['pick_username'])) {
                $this->returnCode(1, null, L_('请填写自提人信息！'));
            }

			if ($deliver_type != 1) {//配送方式是：非自提和非快递配送
				if ($user_address = D('User_adress')->field(true)->where(array('adress_id' => $address_id, 'uid' => $this->_uid))->find()) {
					if ($user_address['longitude'] != 0 && $user_address['latitude'] != 0) {
					    $distance = getDistance($user_address['latitude'], $user_address['longitude'], $return['store']['lat'], $return['store']['long']);
					    if ($return['store']['delivery_range_type'] == 0) {
							$delivery_radius = $return['store']['delivery_radius'] * 1000;
							if ($distance > $delivery_radius && $return['delivery_type'] != 5) {
								$this->returnCode(1, null, L_('已有地址均不在配送范围'));
							}
					    } else {
					        if ($return['store']['delivery_range_polygon']) {
                                if (!isPtInPoly($user_address['longitude'], $user_address['latitude'], $return['store']['delivery_range_polygon']) && $return['delivery_type'] != 5) {
						            $this->returnCode(1, null, L_('您的地址不在本店指定的配送区域'));
						        }
					        } else {
					            $this->returnCode(1, null, '您的地址不在本店指定的配送区域');
					        }
					    }
// 						$distance = getDistance($user_address['latitude'], $user_address['longitude'], $return['store']['lat'], $return['store']['long']);
// 						$delivery_radius = $return['store']['delivery_radius'] * 1000;
// 						if ($distance > $delivery_radius && $return['delivery_type'] != 5) {
// 							$this->returnCode(1, null, L_('已有地址均不在配送范围'));
// 						}

						$province = D('Area')->get_area_by_areaId($user_address['province'],false);
						$user_address['province_txt'] = $province['area_name'];
							
						$city = D('Area')->get_area_by_areaId($user_address['city'],false);
						$user_address['city_txt'] = $city['area_name'];
							
						$area = D('Area')->get_area_by_areaId($user_address['area'],false);
						$user_address['area_txt'] = $area['area_name'];
						$user_address['zip_code'] = $area['zip_code'];
						$user_address['country_code'] = $area['country_code'];
					} else {
						$this->returnCode(1, null, L_('您选择的地址没有完善，请先编辑地址，点击“点击选择位置”进行完善'));
					}
				} else {
					$this->returnCode(1, null, L_('地址信息不存在'));
				}
			}
			$user_info = D('User')->where(array('uid' => $this->_uid))->find();
			$now_time = time();
			$order_data = array();
			$order_data['pick_username'] = $_POST['pick_username']?$_POST['pick_username']:'';
			$order_data['mer_id'] = $return['mer_id'];
			$order_data['store_id'] = $return['store_id'];
			$order_data['uid'] = $this->_uid;
			$order_data['is_tmp_order'] = $_POST['is_tmp_order'] ? true : false;	//临时订单号
			$order_data['tmp_order_id'] = $_POST['tmp_order_id'] && $order_data['is_tmp_order'] ? intval($_POST['tmp_order_id']) : 0;	//临时订单号
			$order_data['desc'] = $note;
			$order_data['create_time'] = $now_time;
			$order_data['last_time'] = $now_time;
			$order_data['invoice_head'] = $invoice_head;
			$order_data['village_id'] = intval($_POST['village_id']);
			$order_data['num'] = $return['total'];
			$order_data['packing_charge'] = $return['packing_charge'];//打包费
			$order_data['merchant_reduce'] = $return['sto_first_reduce'] + $return['sto_full_reduce'];//店铺优惠
            $order_data['merchant_reduce_total'] = $return['sto_first_reduce'] + $return['sto_full_reduce'];//所有店铺优惠
			//$order_data['balance_reduce'] = $return['sys_first_reduce'] + $return['sys_full_reduce'];//平台优惠
            $order_data['balance_reduce_total'] = $return['sys_first_reduce'] + $return['sys_full_reduce'];//平台优惠
			$orderid = strlen($this->_uid) <= 8 ? date('ymdhis') . substr(microtime(), 2, 8 - strlen($this->_uid)) . $this->_uid : date('ymdhis') . substr($this->_uid,0,8);
			$order_data['real_orderid'] = $orderid;
			$order_data['no_bill_money'] = 0;//无需跟平台对账的金额
			$diffTime = 60;
			if ($return['store']['send_time_type'] == 1) {
			    $diffTime = 3600;
			} elseif ($return['store']['send_time_type'] == 2) {
			    $diffTime = 86400;
			} elseif ($return['store']['send_time_type'] == 3) {
			    $diffTime = 86400 * 7;
			} elseif ($return['store']['send_time_type'] == 4) {
			    $diffTime = 86400 * 30;
			}

			if ($deliver_type == 1) {//自提
			    $extraPrice = 0;
				if (empty($pick_id)) {
					$this->returnCode(1, null, '抱歉!未选择自提点,无法购买');
				} else {
					$pre = substr($pick_id, 0, 1);
					$pick_id = substr($pick_id, 1);
					if ($pre == 's') {
						if ($store = D('Merchant_store')->field(true)->where(array('store_id' => $pick_id))->find()) {
							$area[] = $store['province_id'];
							$area[] = $store['city_id'];
							$area[] = $store['area_id'];
							$pick_addr = array('name' => $store['adress'], 'area_info' => array('province' => $store['province_id'], 'city' => $store['city_id'], 'area' => $store['area_id']), 'pick_addr_id' => 's' . $store['store_id'], 'phone' => $store['phone'], 'long' => $store['long'],'lat' => $store['lat'], 'addr_type' => 1);
						} else {
							$this->returnCode(1, null, L_('不存在的自提点'));
						}
					} else {
						if ($address_p = M('Pick_address')->field(true)->where(array('id' => $pick_id))->find()) {
							$area[] = $address_p['province_id'];
							$area[] = $address_p['city_id'];
							$area[] = $address_p['area_id'];
							$pick_addr = array('name' => $address_p['pick_addr'], 'area_info' => array('province' => $address_p['province_id'], 'city' => $address_p['city_id'], 'area' => $address_p['area_id']), 'pick_addr_id' => 'p' . $address_p['id'], 'phone' => $address_p['phone'], 'long' => $address_p['long'], 'lat' => $address_p['lat'], 'addr_type' => 2);
						} else {
							$this->returnCode(1, null, '抱歉!该商家暂未添加自提点,无法购买');
						}
					}
                    $where['area_id'] = array('in', implode(',', $area));
                    $area_name = M('Area')->where($where)->getField('area_id,area_name,zip_code');
                    $pick_addr['area_info']['province'] = $area_name[$pick_addr['area_info']['province']]['area_name'];
                    $pick_addr['area_info']['city'] = $area_name[$pick_addr['area_info']['city']]['area_name'];
                    $pick_addr['area_info']['zip_code'] = $area_name[$pick_addr['area_info']['area']]['zip_code'];
                    $pick_addr['area_info']['area'] = $area_name[$pick_addr['area_info']['area']]['area_name'];
				}
				
				$order_data['is_pick_in_store'] = 2;//配送方式 0：平台配送，1：商家配送，2：自提，3:快递配送
				$delivery_fee = $order_data['freight_charge'] = 0;//运费
				
				$_POST['userphone'] = str_replace(' ', '', trim($_POST['userphone']));
				$order_data['username'] = isset($_POST['username']) && $_POST['username'] ? $_POST['username'] : $order_data['pick_username'];
				$order_data['userphone'] = isset($_POST['userphone']) && $_POST['userphone'] ? $_POST['userphone'] : '';

//                $order_data['username'] = isset($user_info['nickname']) && $user_info['nickname'] ? $user_info['nickname'] : '';
//                $order_data['userphone'] = isset($user_info['phone']) && $user_info['phone'] ? $user_info['phone'] : '';
                //格式化自提地址
                if (!$this->config['open_multilingual']) {
                    $order_data['address'] = $pick_addr['name'];
                } else if ($this->config['country_code'] == 'au') {
                    $order_data['address'] = $pick_addr['name'] . ' ' . $pick_addr['area_info']['area'] . ', ' . $pick_addr['area_info']['city'] . ' ' . $pick_addr['area_info']['province'] . ' ' . $pick_addr['area_info']['zip_code'];
                } else {
                    $order_data['address'] = $pick_addr['area_info']['province'] . ' ' . $pick_addr['area_info']['city'] . ' ' . $pick_addr['area_info']['area'] . ' ' . $pick_addr['name'];
                }

				$order_data['address_id'] = 0;
				if ($pre == 's') {
				    $order_data['pick_id'] = 0;
					/* if(!$order_data['is_tmp_order']){
						$order_data['status'] = 9;
					} */
				} else {
				    $order_data['pick_id'] = $pick_id;
					/* if(!$order_data['is_tmp_order']){
						$order_data['status'] = 7;
					} */
				}
                $order_data['expect_use_time'] = strtotime($_POST['expect_use_time']);//客户选择的自提时间
                //$order_data['expect_use_time'] = time() + $return['store']['work_time'] * $diffTime;//客户期望使用时间
                $order_data['lat'] = $pick_addr['lat'];
				$order_data['lng'] = $pick_addr['long'];
			} else {//配送
				$order_data['username'] = $user_address['name'];
				$order_data['userphone'] = $user_address['phone'];
				if(!$this->config['open_multilingual']){
					$order_data['address'] = $user_address['adress'] . ' ' . $user_address['detail'];
				}else if($user_address['country_code'] == 'nz'){
					$order_data['address'] = trim(($user_address['detail'] ? $user_address['detail']  . ', ' : '') . $user_address['adress']  . ', ' . $user_address['area_txt'] . ' ' . $user_address['city_txt'] . ' ' . $user_address['zip_code']);
				}else if($this->config['country_code'] == 'au'){
					$order_data['address'] = trim(($user_address['detail'] ? $user_address['detail']  . ', ' : '') . $user_address['adress']  . ', ' . $user_address['area_txt'] . ' ' . $user_address['province_txt'] . ' ' . $user_address['zip_code']);
				}else{
					$order_data['address'] = trim(($user_address['detail'] ? $user_address['detail']  . ', ' : '') . $user_address['adress']  . ', ' . $user_address['area_txt'] . ' ' . $user_address['city_txt']);
				}
				
				$order_data['address_id'] = $address_id;
				$order_data['lat'] = $user_address['latitude'];
				$order_data['lng'] = $user_address['longitude'];
// 				if ($return['delivery_type'] == 0 || $return['delivery_type'] == 3) {
// 					$delivery_times = explode('-', $this->config['delivery_time']);
// 					$start_time = $delivery_times[0] . ':00';
// 					$stop_time = $delivery_times[1] . ':00';
	
// 					$delivery_times2 = explode('-', $this->config['delivery_time2']);
// 					$start_time2 = $delivery_times2[0] . ':00';
// 					$stop_time2 = $delivery_times2[1] . ':00';
// 				} else {
					$start_time = $return['store']['delivertime_start'];
					$stop_time = $return['store']['delivertime_stop'];
					
					$start_time2 = $return['store']['delivertime_start2'];
					$stop_time2 = $return['store']['delivertime_stop2'];
					
					$start_time3 = $return['store']['delivertime_start3'];
					$stop_time3 = $return['store']['delivertime_stop3'];
// 				}
	
				if ($start_time == $stop_time && $start_time == '00:00:00') {
					$stop_time = '23:59:59';
				}
	
				if ($arrive_time == 0) {
				    if ($return['delivery_type'] != 5) $this->returnCode(1, null, L_('请选择配送时间！'));
			    } else {
					$arrive_time = strtotime($arrive_time);
				}
				
				$order_data['expect_use_time'] = $arrive_time ? $arrive_time : time() + $return['store']['send_time'] + $return['store']['work_time']* $diffTime;//客户期望使用时间
	
				//计算配送费
    		    $distance = 0;
    		    if (C('config.is_riding_distance') || C('config.map_config') == 'google') {
    		        import('@.ORG.longlat');
    		        $longlat_class = new longlat();
    		        $distance = $longlat_class->getRidingDistance($return['store']['lat'], $return['store']['long'], $user_address['latitude'], $user_address['longitude']);
                    if($distance == -1) $this->returnCode(1, null, L_('请求不到您的具体定位，请重试！'));
    		    }
    		    $distance || $distance = getDistance($return['store']['lat'], $return['store']['long'], $user_address['latitude'], $user_address['longitude']);
    		    $order_data['distance'] = $distance;
				
				$distance = $distance / 1000;
                $return['distance'] = $distance;
				if ($return['delivery_type'] == 5) {//快递配送
					$pass_distance = $distance > $return['basic_distance'] ? floatval($distance - $return['basic_distance']) : 0;
					$return['delivery_fee'] += round($pass_distance * $return['per_km_price'], 2);

                    // 存在折扣优惠
                    if ($return['discount_list']['delivery_zhekou']) {
                        D('Shop_goods')->delivery_discount($return,$return['delivery_fee'],1,$this->app_version);
                    }
					$order_data['freight_charge_original'] = $return['delivery_fee'];
					$return['delivery_fee'] = $return['delivery_fee'] - $return['delivery_fee_reduce'];
					$return['delivery_fee'] = $return['delivery_fee'] > 0 ? $return['delivery_fee'] : 0;
					$delivery_fee = $order_data['freight_charge'] = $return['delivery_fee'];//运费
				} else {
                    
					//获取期望送达时间计算出来的合理接单时间
                    if (getFormatNumber($return['deliver_speed_hour']) && $return['store']['send_time_type'] == 0 && isset($order_data['distance'])) {    //按时速计算
                        $send_time =  $return['distance'] / $return['deliver_speed_hour'] * 3600 + ($return['deliver_delay_time'] * 60);
                    } else {
                        $send_time = $return['store']['send_time'] * 60;
                    }
                    $addTime = $return['store']['work_time'] * $diffTime + $send_time;
                    $tmp_expect_use_time = $order_data['expect_use_time'] - $addTime;
                    $expect_use_time_temp = strtotime(date('Y-m-d ') . date('H:i:s', $tmp_expect_use_time));

					//匹配时间段获取配送费
                    if (D('Deliver_set')->getDeliverTime($expect_use_time_temp, $start_time3, $stop_time3, 3)) {
                        //时间段三
                        $pass_distance = $distance > $return['basic_distance3'] ? floatval($distance - $return['basic_distance3']) : 0;
                        $return['delivery_fee3'] += round($pass_distance * $return['per_km_price3'], 2);

                        // 存在折扣优惠
                        if ($return['discount_list']['delivery_zhekou']) {
                            D('Shop_goods')->delivery_discount($return,$return['delivery_fee3'],1,$this->app_version);
                        }
						$order_data['freight_charge_original'] = $return['delivery_fee3'];
                        $return['delivery_fee3'] = $return['delivery_fee3'] - $return['delivery_fee_reduce'];
                        $return['delivery_fee3'] = $return['delivery_fee3'] > 0 ? $return['delivery_fee3'] : 0;
                        $delivery_fee = $order_data['freight_charge'] = $return['delivery_fee3'];//运费
                    } elseif (D('Deliver_set')->getDeliverTime($expect_use_time_temp, $start_time2, $stop_time2, 2)) {
                        //时间段二
                        $pass_distance = $distance > $return['basic_distance2'] ? floatval($distance - $return['basic_distance2']) : 0;
                        $return['delivery_fee2'] += round($pass_distance * $return['per_km_price2'], 2);

                        // 存在折扣优惠
                        if ($return['discount_list']['delivery_zhekou']) {
                            D('Shop_goods')->delivery_discount($return,$return['delivery_fee2'],1,$this->app_version);
                        }
						$order_data['freight_charge_original'] = $return['delivery_fee2'];
                        $return['delivery_fee2'] = $return['delivery_fee2'] - $return['delivery_fee_reduce'];
                        $return['delivery_fee2'] = $return['delivery_fee2'] > 0 ? $return['delivery_fee2'] : 0;
                        $delivery_fee = $order_data['freight_charge'] = $return['delivery_fee2'];//运费
                    } elseif (D('Deliver_set')->getDeliverTime($expect_use_time_temp, $start_time, $stop_time, 1)) {
                        //时间段一
                        $pass_distance = $distance > $return['basic_distance'] ? floatval($distance - $return['basic_distance']) : 0;
                        $return['delivery_fee'] += round($pass_distance * $return['per_km_price'], 2);

                        // 存在折扣优惠
                        if ($return['discount_list']['delivery_zhekou']) {
                            D('Shop_goods')->delivery_discount($return,$return['delivery_fee'],1,$this->app_version);
                            $delivery_zhekou = $return['discount_list']['delivery_zhekou'];
                        }
						$order_data['freight_charge_original'] = $return['delivery_fee'];
                        $return['delivery_fee'] = $return['delivery_fee'] - $return['delivery_fee_reduce'];
                        $return['delivery_fee'] = $return['delivery_fee'] > 0 ? $return['delivery_fee'] : 0;
                        $delivery_fee = $order_data['freight_charge'] = $return['delivery_fee'];//运费
                    } else {
                        $this->returnCode(1, null, L_('您选择的时间不在配送时间段内！'));
                    }
                }

                //自动应用平台会员卡配送费满减
                $reduceFreightCharge = 0;
                $reduceId = 0;
                if (($return['delivery_type'] == 0 || $return['delivery_type'] == 3) && $order_data['freight_charge'] > 0) {
                    //平台配送
                    $membershipCardMod = new Membership_cardModel();
                    if ($isOpenMembershipCard) {
                        //新开通平台配送费抵扣
                        $membershipCard = $membershipCardMod->getCurrentMembershipCard();
                        if ($membershipCard['discount_type'] & 2) {
                            $reduceFreightCharge = $membershipCard['deliver_price'];
                        }
                    } else {
                        //历史平台配送费抵扣
                        $item = $membershipCardMod->getOneOrderDeliverItem($this->_uid);
                        if ($item) {
                            $reduceId = $item['id'];
                            $reduceFreightCharge = $item['deliver_price'];
                        }
                    }
                    $order_data['reduce_id'] = $reduceId;
                    $order_data['reduce_freight_charge'] = min($reduceFreightCharge, $order_data['freight_charge']);
                    $delivery_fee -= $order_data['reduce_freight_charge'];
                    $order_data['freight_charge'] -= $order_data['reduce_freight_charge'];
                    if ($order_data['reduce_freight_charge'] > 0) {
                        $membershipDeliverDiscount = [
                            'discount_type' => 30,
                            'money' => '0.00',
                            'minus' => getFormatNumber($order_data['reduce_freight_charge']),
                            'did' => $reduceId,
                            'plat_money' => getFormatNumber($order_data['reduce_freight_charge']),
                            'merchant_money' => 0,
                            'type' => '2',
                        ];
                        $return['discount_list']['membership_delivery'] = $membershipDeliverDiscount;
                    }
                }

				if($order_data['address_id']==0){
					$order_data['freight_charge'] = 0;
				}
				switch (intval($this->config['count_freight_charge_method'])) {
				    case 0:
				        break;
				    case 1:
				        $delivery_fee = $order_data['freight_charge'] = ceil($order_data['freight_charge'] * 10) / 10;
				        break;
				    case 2:
				        $delivery_fee = $order_data['freight_charge'] = floor($order_data['freight_charge'] * 10) / 10;
				        break;
				    case 3:
				        $delivery_fee = $order_data['freight_charge'] = round($order_data['freight_charge'] * 10) / 10;
				        break;
				    case 4:
				        $delivery_fee = $order_data['freight_charge'] = ceil($order_data['freight_charge']);
				        break;
				    case 5:
				        $delivery_fee = $order_data['freight_charge'] = floor($order_data['freight_charge']);
				        break;
				    case 6:
				        $delivery_fee = $order_data['freight_charge'] = round($order_data['freight_charge']);
				        break;
				}
				$order_data['freight_charge_original'] = format_number_by_config($order_data['freight_charge_original']);
				if ($return['delivery_type'] == 0 || $return['delivery_type'] == 3) {//平台配送
					$order_data['is_pick_in_store'] = 0;//配送方式 0：平台配送，1：商家配送，2：自提，3:快递配送
					$order_data['no_bill_money'] = $delivery_fee + $extraPrice + $return['merchant_reduce_deliver_money'];
                    //平台配送运费满减优先使用平台
                    $reduceFreightPrice = $order_data['freight_charge_original'] - $order_data['freight_charge'];
                    if ($reduceFreightPrice > 0) {
                        if ($return['plat_reduce_deliver_money'] >= $reduceFreightPrice) {
                            $order_data['balance_reduce_total'] += $reduceFreightPrice;
                        } else {
                            $order_data['balance_reduce_total'] += $return['plat_reduce_deliver_money'];
                            $order_data['merchant_reduce_total'] += min($return['merchant_reduce_deliver_money'], ($reduceFreightPrice - $return['plat_reduce_deliver_money']));
                        }
                    }
                    $order_data['balance_reduce_total'] = $order_data['balance_reduce_total'] > 0 ? $order_data['balance_reduce_total'] : 0;
                    $order_data['merchant_reduce_total'] = $order_data['merchant_reduce_total'] > 0 ? $order_data['merchant_reduce_total'] : 0;
				} elseif ($return['delivery_type'] == 1 || $return['delivery_type'] == 4)  {
					$order_data['is_pick_in_store'] = 1;//配送方式 0：平台配送，1：商家配送，2：自提，3:快递配送
				} else {
					$order_data['is_pick_in_store'] = 3;//配送方式 0：平台配送，1：商家配送，2：自提，3:快递配送
				}
			}
			$hasReduceMoney = 0;
		if($return['sys_first_reduce_type']==1&&$return['sys_first_reduce']>$delivery_fee){//配送费
			$order_data['balance_reduce'] = $delivery_fee + $return['sys_full_reduce'];//平台优惠
		}else{
			$order_data['balance_reduce'] = $return['sys_first_reduce'] + $return['sys_full_reduce'];//平台优惠
		}
        if(C('config.vip_level_discount_sequence')==1 && !in_array($return['vip_discount'],[0,100])) { //满减后计算
            if ($return['vip_discount'] < 100 && $return['vip_discount'] > 0) {
                if ($return['sorts_discount_type'] == 0) { //折上折
                    $tempMoney = round($return['vip_discount_money'] - round(($order_data['merchant_reduce'] + $order_data['balance_reduce']), 2), 2);
                    $hasReduceMoney = round($tempMoney - round($tempMoney * ($return['vip_discount'] * 0.01), 2), 2);
                    foreach ($return['goods'] as &$goods) {
                        $rate = sprintf('%.2f', $goods['price'] / $return['price']);
                        $rate = $rate ? $rate : 1;
                        $goods['discount_price'] = floatval(round($goods['discount_price'] - ($hasReduceMoney * $rate), 2));
                    }
                } else { //折扣最优
                    if ($return['optimalDiscountMoney'] > 0) {
                        $totalOptimalMoney = $return['optimalDiscountMoney'];
                        $return['vip_discount_money'] = $totalOptimalMoney;
                        $tempMoney = round($totalOptimalMoney - round(($order_data['merchant_reduce'] + $order_data['balance_reduce']), 2), 2);
                        $hasReduceMoney = round($tempMoney - round($tempMoney * ($return['vip_discount'] * 0.01), 2), 2);
                        foreach ($return['goods'] as &$goods) {
                            $rate = sprintf('%.2f', $goods['price'] / $return['price']);
                            $rate = $rate ? $rate : 1;
                            $goods['discount_price'] = floatval(round($goods['price'] - ($hasReduceMoney * $rate), 2));
                        }
                    }
                }
            }
            $return['vip_discount_money'] = bcsub($return['vip_discount_money'], $hasReduceMoney, 2);
            $return['discount_price'] = $return['vip_discount_money'];
            $return['can_discount_money'] = round($return['can_discount_money'] - $hasReduceMoney, 2);
            $order_data['vip_level_after'] = 1;//会员等级优惠满减后计算
        }else{ //正常计算
            $hasReduceMoney = $return['vip_level_reduce_money'];
        }
        $order_data['cartid'] = $cartid;//拼单号
        $order_data['other_money'] = $extraPrice;//加价送的金额
        $order_data['order_from'] = $order_from;//订单来源:0：wap快店，1：wap商城，2：Android，3：ios,4:小程序,5：pc快店
        $order_data['goods_price'] = $return['price'];//商品的价格
        $order_data['extra_price'] = $return['extra_price'];//另外要支付的金额
        $order_data['can_discount_money'] = $return['can_discount_money'] + $return['packing_charge'] + $extraPrice;//可用商家优惠券的总价
        $order_data['discount_price'] = $return['vip_discount_money'];//商品折扣后的总价
        $order_data['vip_level_reduce_money'] = $hasReduceMoney;//会员等级折扣优惠金额
        $order_data['vip_level_discount'] = $return['vip_discount'];//会员等级折扣
        $order_data['total_price'] = $return['price'] + $delivery_fee + $return['packing_charge'] + $extraPrice;//订单总价  商品价格+打包费+配送费
        $order_data['price'] = $order_data['discount_price'] + $delivery_fee + $return['packing_charge'] - $order_data['merchant_reduce'] - $order_data['balance_reduce'] + $extraPrice;//实际要支付的价格

        // 超值换购商品
        if ($rep_id && $rep_return) {
            $order_data['price'] =  getFormatNumber($order_data['price'] + $rep_return['price']); 
            $order_data['discount_price'] =  getFormatNumber($order_data['discount_price'] + $rep_return['price']);

        }

        if($order_data['price'] < 0){
			$order_data['price'] = 0;
		}
		$order_data['discount_detail'] = $return['discount_list'] ? serialize($return['discount_list']) : '';//优惠详情
        $order_data['reduce_stock_type'] = $return['store']['reduce_stock_type'];//'减库存类型（0：支付后，1：下单后）'

        //自定义字段
        if ($_POST['cue_field']) {
			$cue_field_arr = is_array($_POST['cue_field']) ? $_POST['cue_field'] : json_decode(htmlspecialchars_decode($_POST['cue_field']), true);
			foreach($cue_field_arr as $cue_k => $cue_v){
				if(empty(trim($cue_v['txt']))){
					unset($cue_field_arr[$cue_k]);
				}
			}
            $order_data['cue_field'] = serialize(array_values($cue_field_arr));
        }
        
        //【直播+短视频平台】 https://www.tapd.cn/47145533/prong/stories/view/1147145533001002522
        if($live_id && $this->config['live_open'] == '1'){
            $live_goods = D("Live_show_goods")->get_goods_percent_by_live($live_id,'shop');
            $is_liveshow = 0;
            if($live_goods['goodids']){
                foreach ($return['goods'] as $_k => $_g) {
                    if(in_array($_g['goods_id'], $live_goods['goodids'])){
                        $is_liveshow = 1;
                        $return['goods'][$_k]['is_liveshow'] = 1;
                        $return['goods'][$_k]['liveshow_id'] = $live_id;
                        $return['goods'][$_k]['liveshow_percent'] = $live_goods['percent'][$_g['goods_id']] == -1 ? C('config.live_show_commission_percent') : $live_goods['percent'][$_g['goods_id']];
                    }
                }
            }
            if($is_liveshow == 1){
                $order_data['is_liveshow'] = 1;
                $order_data['liveshow_id'] = $live_id;
            }
        }
        elseif($video_id && $this->config['live_open'] == '1'){
            $live_goods = D("Live_video_goods")->get_goods_percent_by_live($video_id,'shop');
            $is_livevideo = 0;
            if($live_goods['goodids']){
                foreach ($return['goods'] as $_k => $_g) {
                    if(in_array($_g['goods_id'], $live_goods['goodids'])){
                        $is_livevideo = 1;
                        $return['goods'][$_k]['is_livevideo'] = 1;
                        $return['goods'][$_k]['livevideo_id'] = $video_id;
                        $return['goods'][$_k]['liveshow_percent'] = $live_goods['percent'][$_g['goods_id']] == -1 ? C('config.live_video_commission_percent') : $live_goods['percent'][$_g['goods_id']];
                    }
                }
            }
            if($is_livevideo == 1){
                $order_data['is_livevideo'] = 1;
                $order_data['livevideo_id'] = $video_id;
            }
        }

        $order_data['current_user_level'] = $currentUserLevel;
        $order_data['upgrade_level'] = $upgradeVipLevel;
        $order_data['upgrade_level_price'] = $upgradePrice;
        $order_data['is_open_membership_card'] = $isOpenMembershipCard ?? 0;
        $order_data['membership_card_price'] = $membershipCardPrice ?? 0;
        $order_data['membership_card_name'] = $membershipCard['card_name']?:'';
        $order_data['com_id'] = intval($com_id);//组合套餐ID

        fdump_api([$order_data, $return, $user_info],'save_order',1);
        if ($order_id = D('Shop_order')->saveOrder($order_data, $return, $user_info)) {
            if (!$order_data['is_tmp_order'] && $order_data['status'] == 9) {
				if($order_data['tmp_order_id']){
					D('Shop_order_log')->where(array('order_id' => $order_id, 'status' => 13))->delete(); //删除原数据
				}
                D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 13, 'name' => $return['store']['name'], 'phone' => $return['store']['phone']));//接货
            }
            $order_detail_url = str_replace('appapi.php', 'wap.php', U('Wap/Shop/status', ['order_id' => $order_id], true, false, true));

            if(!empty(C('REDIS_HOST'))){
                try{
                    $redis = new \Redis();
                    $redis->connect(C('REDIS_HOST'), C('REDIS_PORT'));
                    C('REDIS_AUTH') && $redis->auth(C('REDIS_AUTH'));
                    $tempOrderDataId = $redis->get($_POST['ticket'] . $store_id . 'tem_orderid');
                } catch (\Exception $e) {
                    fdump_sql($e->getMessage(), 'redis_error');
                }
            }else{
                $tempOrderDataId = S($_POST['ticket'] . $store_id . 'tem_orderid');
            }
            
            if ($tempOrderDataId) {
                $tempOrderData = M('Temp_order_data')->where(['temp_id' => $tempOrderDataId])->find();
                if ($tempOrderData) {
                    M('Temp_order_data')->where(['temp_id' => $tempOrderDataId])->save(['order_id' => $order_id]);
                    $shopOrderData = [
                        'card_id' => $tempOrderData['card_id'],
                        'card_price' => $tempOrderData['card_price'],
                        'coupon_id' => $tempOrderData['coupon_id'],
                        'coupon_price' => $tempOrderData['coupon_price'],
                    ];
                    M('Shop_order')->where(['order_id' => $order_id])->save($shopOrderData);
                }
            }
            $this->returnCodeOk(array('order_id' => $order_id, 'order_detail_url' => $order_detail_url, 'type' => 'shop'));
        } else {
            $this->returnCodeError(L_('订单保存失败'));
        }
	}
	
	public function checkTime($time, $start_time, $stop_time, $select)
	{
	    $stime = strtotime(date('Y-m-d ' . $start_time));
	    $etime = strtotime(date('Y-m-d ' . $stop_time));
	    $next_stime = 0;
	    $next_etime = 0;
	    if ($etime < $stime) {
	        $etime = strtotime(date('Y-m-d 23:59:59'));
	        $next_stime = strtotime(date('Y-m-d'));
	        $next_etime = strtotime(date('Y-m-d ' . $stop_time));
	    }
	     
	    if ($stime <= $time && $time <= $etime) {
	        return $select;
	    }
	    if ($next_stime <= $time && $time <= $next_etime) {
	        return $select;
	    }
	    return 0;
	}
	
	public function order_list()
	{
		$status = isset($_POST['status']) ? intval($_POST['status']) : 0;
		import('@.ORG.new_reply_ajax_page');
		$where = "is_del=0 AND uid={$this->_uid}";//array('uid' => $this->user_session['uid'], 'status' => array('lt', 3));
		if ($status == -1) {
			$where .= " AND paid=0 AND status=0";
		} elseif ($status == 1) {
			$where .= " AND paid=1 AND status<2";
		} elseif ($status == 2) {
			$where .= " AND paid=1 AND status=2";
		}
		$where.=' AND order_from<>1';
		
		if($_POST['mer_id']){
			$where .= " AND mer_id=".$_POST['mer_id'];
		}
		
		$order_count = D("Shop_order")->where($where)->count();
		$order_count = intval($order_count);
		$page_size = 10;
		$p = new Page($order_count, $page_size);
		$order_list = D("Shop_order")->field(true)->where($where)->order('order_id DESC')->limit($p->firstRow . ',' . $page_size)->select();
		foreach ($order_list as $st) {
			$store_ids[] = $st['store_id'];
		}
		$m = array();
		if ($store_ids) {
			$store_image_class = new store_image();
			$merchant_list = D(_view("Merchant_store"))->where(array('store_id' => array('in', $store_ids)))->select();
			foreach ($merchant_list as $li) {
				$images = $store_image_class->get_allImage_by_path($li['pic_info']);
				$li['image'] = $images ? array_shift($images) : array();
				unset($li['status']);
				$m[$li['store_id']] = $li;
			}
		}
		$list = array();
		foreach ($order_list as $ol) {
			$temp = array('real_orderid' => $ol['real_orderid']);
			$temp['create_time'] = date('Y-m-d H:i:s', $ol['create_time']);
			if (isset($m[$ol['store_id']]) && $m[$ol['store_id']]) {
				$temp['image'] = $m[$ol['store_id']]['image']?$m[$ol['store_id']]['image']:'';
				$temp['name'] = $m[$ol['store_id']]['name'];
			} else {
				$temp['image'] = '';
				$temp['name'] = '';
			}
			
			$temp['num'] = $ol['num'];
			$temp['order_id'] = $ol['order_id'];
			$temp['paid'] = $ol['paid'];
			$temp['status'] = $ol['status'];
			$temp['price'] = strval(floatval($ol['price']));
			$temp['use_sys_coupon'] = $ol['coupon_id'] ? true : false;
			$temp['use_mer_coupon'] = $ol['card_id'] ? true : false;
			$temp['system_coupon_id'] = $ol['coupon_id'];
			$temp['merchant_coupon_id'] = $ol['card_id'];
			$list[] = $temp;
		}
	
		$return['count'] = $order_count;
		$return['list']  = $list;
// 		$return['page']  = $p->show();
// 		$return['now']  = $p->nowPage;
		$return['total']  = $p->totalPage;
		$this->returnCode(0, $return);
	}
	
	public function order_detail()
	{
		$order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
		if ($order = D('Shop_order')->field(true)->where(array('order_id' => $order_id, 'uid' => $this->_uid))->find()) {
			$store = D(_view("Merchant_store"))->where(array('store_id' => $order['store_id']))->find();
			if (empty($store)) $this->returnCode(1, null, L_('订单信息不合法'));
			$store_image_class = new store_image();
			$images = $store_image_class->get_allImage_by_path($store['pic_info']);
			$return = array('real_orderid' => $order['real_orderid']);
			$return['image'] = $images ? array_shift($images) : '';
			$return['name'] = $store['name'];
			$return['num'] = $order['num'];
			$return['order_id'] = $order['order_id'];
			$return['paid'] = $order['paid'];
			$return['status'] = $order['status'];
			$return['price'] = floatval($order['price']);
			$return['create_time'] = date('Y-m-d H:i:s', $order['create_time']);
	
            //删除购物车
            M('Shop_cart')->where(['shop_id'=>$order['store_id'],'uid'=>$this->_uid])->delete();
			$this->returnCode(0, $return);
		} else {
			$this->returnCode(1, null, L_('订单信息不合法'));
		}
	}


    // 是否有待评价订单
    public function order_reply()
    {
        $return['is_have'] = 0;
        if ($this->_uid) {// 是否有待评价订单
            $order_reply = M('Shop_order')->where(array('status'=>2,'paid'=>1,'uid'=>$this->_uid))->order('last_time DESC')->find();
            if ($order_reply) {
                $return['is_have'] = 1;
                if (C('config.feedback_score_add')) {
                    $order_reply_text = L_('您有一笔待评价订单，评价后即可获得X1X2哦！',array('X1'=>C('config.feedback_score_add'),'X2'=>C('config.score_name')));
                }else{
                    $order_reply_text = L_('您有一笔待评价订单,参与好评商家可能会意外奖励哦！');
                }
            }else{
                $order_reply_text = '';
            }
        }else{
            $order_reply_text = '';
        }
        $return['order_reply_text'] = $order_reply_text;
        $return['url'] = C('config.site_url').'/wap.php?g=Wap&c=My&a=shop_feedback&order_id='.$order_reply['order_id'];
        $this->returnCode(0, $return);
    }
    public function search()
    {
		if($_GET['key']){
			$_POST  =$_GET;
		}
        if(empty($_POST)){
            $input_post = file_get_contents('php://input');
            $_POST = json_decode($input_post,true);

		}
        $key = isset($_POST['key']) ? htmlspecialchars($_POST['key']) : '';
        //if (empty($key)) {
        //    $this->returnCode(1, null, '请输入搜索的关键词');
        //}
        $cat_url = isset($_POST['cat_url']) ? htmlspecialchars($_POST['cat_url']) : 'all';
        $order = isset($_POST['sort_url']) ? htmlspecialchars($_POST['sort_url']) : 'juli';
        $deliver_type = isset($_POST['type_url']) ? htmlspecialchars($_POST['type_url']) : 'all';
        $lat = isset($_POST['user_lat']) ? htmlspecialchars($_POST['user_lat']) : 0;
        $long = isset($_POST['user_long']) ? htmlspecialchars($_POST['user_long']) : 0;
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $page = max(1, $page);
        $cat_id = 0;
        $cat_fid = 0;
        if ($cat_url != 'all') {
            $now_category = D('Shop_category')->get_category_by_catUrl($cat_url);
            if ($now_category) {
                if ($now_category['cat_fid']) {
                    $cat_id = $now_category['cat_id'];
                    $cat_fid = $now_category['cat_fid'];
                } else {
                    $cat_id = 0;
                    $cat_fid = $now_category['cat_id'];
                }
            }
        }
        
        $where = array('deliver_type' => $deliver_type, 'order' => $order, 'lat' => $lat, 'long' => $long, 'cat_id' => $cat_id, 'cat_fid' => $cat_fid, 'page' => $page);
        $key && $where['key'] = $key;
        $merchantStoreShopDB = D('Merchant_store_shop');
        $lists = D('Merchant_store_shop')->get_list_by_option($where, 1, 1);
        $return = array();
        $now_time = date('H:i:s');
        foreach ($lists['shop_list'] as $row) {
            $temp = array();
            $temp['store_id'] = $row['store_id'];
            $temp['name'] = $row['name'];
			$temp['openclassfyindex'] = $row['is_mult_class'];
            $temp['search_name'] = str_replace($key, '<font color="#06c1ae">' . $key . '</font>', $row['name']);
            $temp['store_theme'] = $row['store_theme'];
            $temp['isverify'] = $row['isverify'];
            $temp['juli_wx'] = $row['juli'];
            $temp['range'] = $row['range'];
            $temp['image'] = thumb_img($row['image'],180,120,'fill');
			if($row['logo']){
				$temp['image'] = thumb_img($row['logo'],180,120,'fill');
			}
            $temp['star'] = round($row['score_mean'],1);
            $temp['month_sale_count'] = $row['sale_count'];
            $temp['is_new'] = ($row['create_time'] + 864000) < time() ? 0 : 1;
            $temp['delivery'] = $deliver_type == 'pick' ? 0 : $row['deliver'];//是否支持配送
            $temp['delivery'] = $temp['delivery'] ? true : false;
            $temp['deliver_type'] = $row['deliver_type'];
            $temp['delivery_time'] = $row['send_time'];//配送时长
            $temp['send_time_type'] = $row['send_time_type'];
            $temp['delivery_time_type'] = $this->send_time_type[$row['send_time_type']];
            $temp['delivery_price'] = floatval($row['basic_price']);//起送价
            $temp['delivery_money'] = floatval($row['delivery_fee']);//配送费
            $temp['delivery_system'] = $row['deliver_type'] == 0 || $row['deliver_type'] == 3 ? true : false;//是否是平台配送
            $temp['goods_list'] = isset($row['goods_list']) && $row['goods_list'] ? $row['goods_list'] : array();
            $temp['is_close'] = $row['state'] ? intval($row['is_close']) : 1;
            $temp['time'] = $merchantStoreShopDB->getBuniessName($row);

            $temp['coupon_list'] = array();
            if ($row['is_invoice']) {
                $temp['coupon_list']['invoice'] = floatval($row['invoice_price']);
            }
            if ($row['store_discount'] != 0 && $row['store_discount'] != 100) {
                $temp['coupon_list']['discount'] = $row['store_discount']/10;
            }
            $system_delivery = array();
            foreach ($row['system_discount'] as $row_d) {
                if ($row_d['type'] == 0) {//新单
                    $temp['coupon_list']['system_newuser'][] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']));
                } elseif ($row_d['type'] == 1) {//满减
                    $temp['coupon_list']['system_minus'][] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']));
                } elseif ($row_d['type'] == 2) {//配送
                    if ($row_d['full_money'] > 0 && $row_d['reduce_money'] > 0) {
                        $system_delivery[] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']));
                    }
                }
            }
            foreach ($row['merchant_discount'] as $row_m) {
                if ($row_m['type'] == 0) {
                    $temp['coupon_list']['newuser'][] = array('money' => floatval($row_m['full_money']), 'minus' => floatval($row_m['reduce_money']));
                } elseif ($row_m['type'] == 1) {
                    $temp['coupon_list']['minus'][] = array('money' => floatval($row_m['full_money']), 'minus' => floatval($row_m['reduce_money']));
                }
            }
            if ($row['deliver']) {
                if ($temp['delivery_system']) {
                    $system_delivery && $temp['coupon_list']['delivery'] = $system_delivery;
                } else {
                    if ($row['is_have_two_time']) {
                        if ($row['reach_delivery_fee_type2'] == 0) {
                            if ($row['basic_price'] > 0 && $row['delivery_fee2'] > 0) {
                                $temp['coupon_list']['delivery'][] = array('money' => floatval($row['basic_price']), 'minus' => floatval($row['delivery_fee2']));
                            }
                        } elseif ($row['reach_delivery_fee_type2'] == 1) {
                            //$temp['coupon_list']['delivery'] = array('money' => false, 'minus' => $row['delivery_fee']);
                        } elseif ($row['reach_delivery_fee_type2'] == 2) {
                            $row['delivery_fee2'] && $temp['coupon_list']['delivery'][] = array('money' => floatval($row['no_delivery_fee_value2']), 'minus' => floatval($row['delivery_fee2']));
                        }
                    } else {
                        if ($row['reach_delivery_fee_type'] == 0) {
                            if ($row['basic_price'] > 0 && $row['delivery_fee'] > 0) {
                                $temp['coupon_list']['delivery'][] = array('money' => floatval($row['basic_price']), 'minus' => floatval($row['delivery_fee']));
                            }
                        } elseif ($row['reach_delivery_fee_type'] == 1) {
                            //$temp['coupon_list']['delivery'] = array('money' => false, 'minus' => $row['delivery_fee']);
                        } elseif ($row['reach_delivery_fee_type'] == 2) {
                            $row['delivery_fee'] && $temp['coupon_list']['delivery'][] = array('money' => floatval($row['no_delivery_fee_value']), 'minus' => floatval($row['delivery_fee']));
                        }
                    }
                }
            }
            $temp['coupon_count'] = count($temp['coupon_list']);
            $temp['coupon_list'] = $this->parseCoupon($temp['coupon_list'],'array');
            
            if($_POST['Device-Id'] != 'wxapp' || $temp['is_close'] == 0){
                $return[] = $temp;
            }
        }
        $array = array('store_list' => $return, 'has_more' => $lists['has_more'] ? true : false);
        $this->returnCode(0, $array);
    }
    
    /**
     * 生成拼单数据
     */
    public function addNew()
    {
        if (empty($_POST)) {
            $input_post = file_get_contents('php://input');
            $_POST = json_decode($input_post, true);
        }
        
        $cartData= $_POST['productCart'];
//         if (empty($cartData)) {
//             $this->returnCode(10110003);
//         }
        if (!is_array($cartData)) {
            $cartData = json_decode(htmlspecialchars_decode($cartData), true);
        }
        $store_id = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
        $from = isset($_POST['from']) ? intval($_POST['from']) : 0;
        
        $return = $this->checkStore($store_id);
        if ($return['error']) {
            $this->returnCode(1, null, $return['msg']);
        }
        $shop = $return['store'];
        $cartid = isset($_POST['cartid']) ? htmlspecialchars(trim($_POST['cartid'])) : '';
        $avatar = isset($_POST['avatar']) ? htmlspecialchars(trim($_POST['avatar'])) : '';
        $name= isset($_POST['name']) ? htmlspecialchars(trim($_POST['name'])) : '';

        $index = isset($_POST['index']) ? htmlspecialchars(trim($_POST['index'])) : '';
        $copyindex = isset($_POST['copyindex']) ? htmlspecialchars(trim($_POST['copyindex'])) : '';
        if ($copyindex) {
            $copyindex = is_numeric($copyindex)  ? 'index_' . $copyindex : $copyindex;
        }
        $where = array('cartid' => $cartid, 'store_id' => $store_id);
        $orderTemp = D('Shop_order_temp')->field(true)->where($where)->find();
        
        if (empty($index) && $orderTemp) {
            $index = $orderTemp['max_index'] + 1;
        } elseif (empty($index)) {
            $index = 1;
        }
        is_numeric($index) && $index = max(1, $index);
        
        if (empty($cartid)) {
            $cartid = md5($store_id . uniqid() . date('YmdHis'));
        }
        
        if ($cartData) {
            $shopGoodsDB = D('Shop_goods');
            $resultGoods = D('Shop_order_temp')->getGoods($cartid, $store_id, $index);
            foreach ($cartData as &$row) {
                $goods_id = $row['productId'];
                $num = $row['count'];
                if ($num < 1) continue;
                $spec_ids = array();
                $str_s = array(); $str_p = array();
                foreach ($row['productParam'] as $r) {
                    if ($r['type'] == 'spec') {
                        $spec_ids[] = $r['id'];
                        $str_s[] = $r['name'];
                    } else {
                        foreach ($r['data'] as $d) {
                            $str_p[] = $d['name'];
                        }
                    }
                }
                if (isset($resultGoods[$goods_id])) {
                    $num += $resultGoods[$goods_id]['count'];
                }
                $spec_str = $spec_ids ? implode('_', $spec_ids) : '';
                $t_return = $shopGoodsDB->check_stock($goods_id, $num, $spec_str, $shop['stock_type'], $store_id, false, $this->_uid, '', '', '', '', $row['host_goods_id']);
                if ($t_return['status'] != 1) {
                    $this->returnCode(1, null, $t_return['msg']);
                }
                $row['productPrice'] = floatval($t_return['price']);
                $row['oldPrice'] = floatval($t_return['old_price']);
                $row['productPackCharge'] = floatval($t_return['packing_charge']);
                $row['maxNum'] = $t_return['maxNum'];
                $row['limit_type'] = $t_return['limit_type'];
            }
        }
        
        $data = array('cartid' => $cartid, 'store_id' => $store_id);
        
        $info = array();
        $info['index'] = $index;
        $info['from'] = $from;
        $info['data'] = $cartData;
        $info['name'] = $name ?: L_('X1号订购人',$index);
        $info['avatar'] = $avatar;
        
        $arrIndex = is_numeric($index) ? 'index_' . $index : $index;
        if ($orderTemp) {
            $data = array();
            if (is_numeric($index) && intval($orderTemp['max_index']) < $index) {
                $data['max_index'] = $index;
            }
            $orderTemp['info'] = json_decode($orderTemp['info'], true);
            
            if (isset($orderTemp['info'][$arrIndex])) {
                if (empty($name) && $orderTemp['info'][$arrIndex]['name']) {
                    $info['name'] = $orderTemp['info'][$arrIndex]['name'];
                }
                if (empty($avatar) && $orderTemp['info'][$arrIndex]['avatar']) {
                    $info['avatar'] = $orderTemp['info'][$arrIndex]['avatar'];
                }
            }
            
            if ($copyindex && isset($orderTemp['info'][$copyindex]['data']) && $orderTemp['info'][$copyindex]['data']) {
                $info['data']= $orderTemp['info'][$copyindex]['data'];
            }
            if ($info['data']) {
                foreach ($info['data'] as &$nowData) {
                    $nowData['packname'] = $info['name'];
                }
            }
            $orderTemp['info'][$arrIndex] = $info;
            $data['info'] = json_encode($orderTemp['info']);
            D('Shop_order_temp')->where($where)->save($data);
        } else {
            if ($info['data']) {
                foreach ($info['data'] as &$nowData) {
                    $nowData['packname'] = $info['name'];
                }
            }
            $data['info'] = json_encode(array($arrIndex => $info));
            $data['max_index'] = 1;
			$data['uid'] = $this->_uid;
            D('Shop_order_temp')->add($data);
        }
        
        $data = array(
            'name' => $info['name'],
            'avatar' => $avatar,
            'cartid' => $cartid,
            'index' => $index,
            'share_url' => $this->config['site_url'] . '/wap.php?c=Shop&a=spell&cartid=' . $cartid . '&store_id=' . $store_id
        );
        
        $this->returnCode(0, $data);
    }

    public function ajaxAll()
    {
        $store_id = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
        $cartid = isset($_POST['cartid']) ? htmlspecialchars(trim($_POST['cartid'])) : '';
        $myIndex = isset($_POST['myindex']) ? htmlspecialchars(trim($_POST['myindex'])) : '';
        $from = isset($_POST['from']) ? intval($_POST['from']) : 0;

        $return = $this->checkStore($store_id);
        if ($return['error']) {
            $this->returnCode(1, null, $return['msg']);
        }
        $store = $return['store'];
        empty($store['shop_fitment_color']) && $value['shop_fitment_color'] = '113533';//背景色
        $store['shop_fitment_front_color'] = 'ffffff'; //标题色


        $where = array('cartid' => $cartid, 'store_id' => $store_id);
        $arrIndex = is_numeric($myIndex) ? 'index_' . $myIndex : $myIndex;
        if ($orderTemp = D('Shop_order_temp')->field(true)->where($where)->find()) {
            $list = json_decode($orderTemp['info'], true);
            $data = array();
            $myData = '';
            $myTotalPrice = 0;
            $myTotalPack = 0;
            $totalPrice = 0;
            $totalPack = 0;
            $myDiscountPrice = 0;
            $newList = array();


            //获取店铺是否存在必选分类商品
            $mustSort = D('Merchant_store')->getMustSortIdsByStoreId($store_id, 'sort_id,sort_name');
            if ($mustSort) {
                $mustSortId = D('Shop_goods_sort')->getAllSonIds($mustSort['sort_id'], $store_id, true);
            } else {
                $mustSortId = [];
            }
            $containsRequiredSort = empty($mustSortId) ? true : false;
            $shopGoodsMod = D('Shop_goods');

            foreach ($list as $this_index => &$rowset) {
                $frm = $rowset['from'] ? 'spell' : 'sync';
//                 if ($store['is_mult_class'] == 0) {
//                     $rowset['add_cart_url'] = $this->config['site_url'] . U('Shop/index', array('index' => $rowset['index'], 'frm' => $frm, 'cartid' => $cartid)) . '#shop-' . $store_id;
//                 } else {
//                     $rowset['add_cart_url'] = $this->config['site_url'] . U('Shop/classic_shop', array('index' => $rowset['index'], 'frm' => $frm, 'cartid' => $cartid, 'shop_id' => $store_id));
//                 }
                $rowset['status'] = $orderTemp['status'];
                $tempList = array();
                foreach ($rowset['data'] as $row) {
                    $goodSortId = $shopGoodsMod->where(['goods_id' => $row['productId']])->getField('sort_id');
                    if ($mustSortId && !$containsRequiredSort && in_array($goodSortId, $mustSortId)) {
                        $containsRequiredSort = true;
                    }

					$row['productPrice'] = getFormatNumber($row['productPrice']);
                    if ($row['limit_type'] == 0 && $row['maxNum'] > 0) {
                        if (isset($useGoods[$row['productId']])) {
                            $useNum = $useGoods[$row['productId']];
                            $discountNum = max(0, $row['maxNum'] - $useNum);
                            $useGoods[$row['productId']] += $discountNum;
                        } else {
                            $discountNum = $row['maxNum'];
                            $useGoods[$row['productId']] = $row['maxNum'];
                        }
                        $oldNum = 0;
                        if ($row['count'] > $discountNum) {
                            $oldNum = $row['count'] - $discountNum;
                        } else {
                            $discountNum = min($discountNum, $row['count']);
                        }

                        $oldPrice = $row['oldPrice'];
                        $discountPrice = $row['productPrice'];

                        if ($row['productParam']) {
                            $row['productName'] .= '(';
                            $names = array();
                            foreach ($row['productParam'] as $val) {
                                if ($val['type'] == 'spec') {
                                    $names[] = $val['name'];
                                } elseif ($val['type'] == 'properties') {
                                    foreach ($val['data'] as $vo) {
                                        $names[] = $vo['name'];
                                    }
                                }
                            }
                            $row['productName'] .= implode(',', $names) . ')';
                        }

                        if ($oldNum > 0) {
                            $totalPrice += floatval($row['oldPrice'] * $oldNum);
                            $totalPack += floatval($row['productPackCharge'] * $oldNum);

                            $row['productPrice'] = $row['oldPrice'];
                            $row['count'] = $oldNum;
                            $tempList[] = $row;
                        }
                        if ($discountNum > 0) {
                            $row['productPrice'] = $discountPrice;
                            $row['count'] = $discountNum;

                            $totalPrice += floatval($row['productPrice'] * $row['count']);
                            $totalPack += floatval($row['productPackCharge'] * $row['count']);
                            $tempList[] = $row;
                        }
                    } else {
                        $totalPrice += floatval($row['productPrice'] * $row['count']);
                        $totalPack += floatval($row['productPackCharge'] * $row['count']);
                        if ($row['productParam']) {
                            $row['productName'] .= '(';
                            $names = array();
                            foreach ($row['productParam'] as $val) {
                                if ($val['type'] == 'spec') {
                                    $names[] = $val['name'];
                                } elseif ($val['type'] == 'properties') {
                                    foreach ($val['data'] as $vo) {
                                        $names[] = $vo['name'];
                                    }
                                }
                            }
                            $row['productName'] .= implode(',', $names) . ')';
                        }
                        $tempList[] = $row;
                    }
                }
                $rowset['data'] = $tempList;

                if ($from && $arrIndex == $this_index) {
                    $myData = $rowset;
                    foreach ($myData['data'] as $my) {
                        $myTotalPrice += floatval($my['productPrice'] * $my['count']);
                        $myTotalPack += floatval($my['productPackCharge'] * $my['count']);
                    }
                    unset($list[$this_index]);
                }

                if (isset($rowset['data']) && $rowset['data']) {
                    $data = array_merge($data, $rowset['data']);
                } elseif ($from) {
                    unset($list[$this_index]);
                } elseif ($from == 0 && $rowset['from'] == 1) {
                    unset($list[$this_index]);
                }
            }

            if ($orderTemp['status'] > 1) {
                $myDiscountPrice = floatval(round(floatval(($myTotalPack + $myTotalPrice) * $orderTemp['price']) / ($totalPack + $totalPrice), 2));
            }
            $myDiscountPrice = is_nan($myDiscountPrice) ? 0 : $myDiscountPrice;

            foreach (array_reverse($list) as $vo) {

                //组合附属商品
                $goodsList = $vo['data'];
                foreach ($vo['data'] as $key => $value) {
                    if ($value['host_goods_id'] == 0) {
                        $speclist = '';
                        foreach ($goodsList as $k => $v) {
                            if ($v['host_goods_id'] == $value['productId'] AND $v['uniqueness_number'] == $value['uniqueness_number']) {
                                $vo['data'][$key]['productPrice'] += $v['productPrice'] * $v['count'] / $value['count'];
                                $vo['data'][$key]['oldPrice'] += $v['oldPrice'] * $v['count'] / $value['count'];
                                $speclist .= $v['productName'] . '×' . $v['count'] . '，';
                            }
                        }
                        $vo['data'][$key]['oldPrice'] = getFormatNumber($vo['data'][$key]['oldPrice']);

                        $vo['data'][$key]['spec'] = substr($speclist, 0, strlen($speclist) - 3);
                    } else {
                        unset($vo['data'][$key]);
                    }
                }
				foreach ($vo['data'] as $key => $value) {
					$vo['data'][$key]['productPrice'] = getFormatNumber($vo['data'][$key]['productPrice']);
                }
				
                $vo['data'] = array_values($vo['data']);
                $newList[] = $vo;
            }
            if ($_POST['return_product_index']) {
                foreach ($newList as $v){
                    if($v['index'] == $myIndex){
                        return $v;
                    }
                }
            }

            $data = array('data' => $newList, 'totalPack' => round($totalPack,2), 'totalPrice' => round($totalPrice,2), 'myData' => $myData, 'myDiscountPrice' => $myDiscountPrice, 'myTotalPack' => $myTotalPack, 'myTotalPrice' => $myTotalPrice, 'store' => $store, 'status' => $orderTemp['status'], 'order' => $orderTemp);
            $data['required_sort'] = $mustSort ? $mustSort : new \stdClass();
            $data['contains_required_sort'] = $containsRequiredSort;
            $this->returnCode(0, $data);
        } else {
            $this->returnCode(1, null, L_('拼单信息有误'));
        }
    }
    
    /**
     * 删除一个拼单人
     */
    public function delCart()
    {
        $cartid = isset($_POST['cartid']) ? htmlspecialchars(trim($_POST['cartid'])) : '';
        $index = isset($_POST['index']) ? trim($_POST['index']) : '';
        $store_id = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
        
        $return = $this->checkStore($store_id);
        if ($return['error']) {
            $this->returnCode(1, null, $return['msg']);
        }
        $shop = $return['store'];
        
        $arrIndex = is_numeric($index) ? 'index_' . $index : $index;
        $where = array('cartid' => $cartid, 'store_id' => $store_id);
        if ($orderTemp = D('Shop_order_temp')->field(true)->where($where)->find()) {
            $orderTemp['info'] = json_decode($orderTemp['info'], true);
            unset($orderTemp['info'][$arrIndex]);
            D('Shop_order_temp')->where($where)->save(array('info' => json_encode($orderTemp['info'])));
            
            $this->returnCode(0);
        }
        
        $this->returnCode(1, null, L_('拼单信息有误'));
    }
    
    /**
     * 锁定拼单去买单
     */
    public function saveCart()
    {
        $cartid = isset($_POST['cartid']) ? htmlspecialchars(trim($_POST['cartid'])) : '';
        $store_id = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
        $status = isset($_POST['status']) ? intval($_POST['status']) : 0;
        $return = $this->checkStore($store_id);
        if ($return['error']) {
            $this->returnCode(1, null, $return['msg']);
        }
        $shop = $return['store'];
        
        $where = array('cartid' => $cartid, 'store_id' => $store_id);
        if ($orderTemp = D('Shop_order_temp')->field(true)->where($where)->find()) {
            $orderTemp['info'] = json_decode($orderTemp['info'], true);
            unset($orderTemp['info'][$index]);
            D('Shop_order_temp')->where($where)->save(array('status' => $status));
            $this->returnCode(0);
        }
        $this->returnCode(1, null, L_('拼单信息有误'));
    }
    
    private function checkStore($store_id)
    {
        $store = D("Merchant_store")->field(true)->where(array('store_id' => $store_id))->find();
        if ($store['have_shop'] == 0 || $store['status'] != 1) {
            return array('error' => true, 'msg' => L_('商家已经关闭了该业务,不能下单了!'));
        }
        if (C('config.store_shop_auth') == 1 && $store['auth'] < 3) {
            return array('error' => true, 'msg' => L_('您查看的X1没有通过资质审核！', C('config.shop_alias_name')));
        }
        
        $store_image_class = new store_image();
        $images = $store_image_class->get_allImage_by_path($store['pic_info']);
        $store['images'] = $images ? array_shift($images) : '';
        
        $now_time = date('H:i:s');
        $is_open = 0;
        if ($store['open_1'] == '00:00:00' && $store['close_1'] == '00:00:00') {
            $is_open = 1;
        } else {
            if ($store['open_1'] < $now_time && $now_time < $store['close_1']) {
                $is_open = 1;
            }
            if ($store['open_2'] != '00:00:00' || $store['close_2'] != '00:00:00') {
                if ($store['open_2'] < $now_time && $now_time < $store['close_2']) {
                    $is_open = 1;
                }
            }
            if ($store['open_3'] != '00:00:00' || $store['close_3'] != '00:00:00') {
                if ($store['open_3'] < $now_time && $now_time < $store['close_3']) {
                    $is_open = 1;
                }
            }
        }
        /* if ($is_open == 0) {
            return array('error' => true, 'msg' => L_('店铺休息中'));
        } */
        
        $store_shop = D("Merchant_store_shop")->field(true)->where(array('store_id' => $store_id))->find();
        if (empty($store) || empty($store_shop)) return array('error' => true, 'msg' => L_('店铺信息错误'));
        $store = array_merge($store, $store_shop);
        
        return array('error' => false, 'store' => $store);
    }
    
    public function getData()
    {
        $store_id = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
        $cartid = isset($_POST['cartid']) ? htmlspecialchars(trim($_POST['cartid'])) : '';
        
        $return = $this->checkStore($store_id);
        if ($return['error']) {
            $this->returnCode(1, null, $return['msg']);
        }
        $store = $return['store'];
        
        $where = array('cartid' => $cartid, 'store_id' => $store_id);
        if ($orderTemp = D('Shop_order_temp')->field(true)->where($where)->find()) {
            $listData = json_decode($orderTemp['info'], true);
            $returnData = array();
            foreach ($listData as $this_index => $rowset) {
                if ($rowset['from'] == 0) {
                    $goodsTemp = array();
                    foreach ($rowset['data'] as $row) {
                        $list = array();
                        $list['product_id'] = $row['productId'];
                        $list['has_format'] = false;
                        $list['counts'] = $row['count'];
                        $list['productPrice'] = floatval($row['productPrice']);
                        $list['detail'] = '';
                        $list['standardList'] = array();
                        $list['jardiniereList'] = array();
                        $list['indexMap'] = array();
                        if ($row['productParam']) {
                            $list['has_format'] = true;
                            $names = array();
                            foreach ($row['productParam'] as $val) {
                                if ($val['type'] == 'spec') {
                                    $list['standardList'][] = $val;
                                    $names[] = $val['name'];
                                } elseif ($val['type'] == 'properties') {
                                    foreach ($val['data'] as $vo) {
                                        $names[] = $vo['name'];
                                        $list['jardiniereList'][] = $vo;
                                        if (!in_array($vo['list_id'], $list['indexMap'])) {
                                            $list['indexMap'][] = $vo['list_id'];
                                        }
                                    }
                                }
                            }
                            $list['detail'] = implode('-', $names);
                        }
                        $goodsTemp[] = $list;
                    }
                    $returnData[] = array('list' => $goodsTemp, 'index' => $rowset['index'], 'from' => $rowset['from'], 'name' => $rowset['name'], 'avatar' => $rowset['avatar']);
                }
            }
            
            $arr = array('share_url' => $this->config['site_url'] . '/wap.php?c=Shop&a=spell&cartid=' . $cartid . '&store_id=' . $store_id, 'data' => $returnData);
            $arr['basic_price'] = floatval($store['basic_price']);
            $this->returnCode(0, $arr);
        } else {
            $this->returnCode(1, null, L_('拼单信息有误'));
        }
    }

    public function ajaxShop()
    {
        if (empty($_POST)) {
            $input_post = file_get_contents('php://input');
            $_POST = json_decode($input_post, true);
        }
        $store_id = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
        $where = array('store_id' => $store_id);
        $now_store = D('Merchant_store')->field(true)->where($where)->find();
        $now_shop = D('Merchant_store_shop')->field(true)->where($where)->find();
        $now_mer = M('Merchant')->field('isverify')->where(array('mer_id' => $now_store['mer_id']))->find();
        if (empty($now_shop) || empty($now_store)) {
            $this->returnCode(1, null, L_('该') . $this->config['shop_alias_name'] . L_('暂未完善信息，无法使用'));
        }
		if ($now_store['shop_on_sale'] == 2) {
			 $this->returnCode(1, null, L_('该') . $this->config['shop_alias_name'] . L_('已被下架，请联系系统管理员'));
		}
        $auth_files = array();
        if ($now_store['auth'] > 2) {
            if (! empty($now_store['auth_files'])) {
                $auth_file_class = new auth_file();
                $tmp_pic_arr = explode(';', $now_store['auth_files']);
                foreach ($tmp_pic_arr as $key => $value) {
                    $auth_file_temp = $auth_file_class->get_image_by_path($value);
                    $auth_files[] = $auth_file_temp['image'];
                }
            }
        }
        $now_store['auth_files'] = $auth_files;
        
        $discounts = D('Shop_discount')->getDiscounts($now_store['mer_id'], $store_id);
        $row = array_merge($now_store, $now_shop);
        
        $store = array();
        $store_image_class = new store_image();
        $images = $store_image_class->get_allImage_by_path($row['pic_info']);
        
        $store['id'] = $row['store_id'];
        $store['phone'] = trim($row['phone']);
        $store['long'] = $row['long'];
        $store['lng'] = $row['long'];
        $store['lat'] = $row['lat'];
        $store['isverify'] = $now_mer['isverify'];
        $store['store_theme'] = $row['store_theme'];

        // 验证是否有三级分类
        $row['is_mult_class'] = D('Merchant_store_shop')->checkMultClass($row['store_id']);
        if($row['is_mult_class']==1){
            $store['store_theme'] = 0;
        }
        $store['adress'] = $row['adress'];
        $store['close_reason'] = $row['close_reason'];
        $store['openclassfyindex'] = $row['is_mult_class'];
        $store['is_close'] = 1;
        
        $store['share_title'] = $row['name'];
        $store['share_content'] = $row['store_notice'];

		$store['share_image'] = !empty($images) ? $images[0] : '';
        
        if ($row['store_theme'] && empty($row['is_mult_class'])) {
            $store['share_url'] = $this->config['site_url'] . 'wap.php?c=Shop&a=classic_shop&shop_id=' . $row['store_id'];
        } else {
            $store['share_url'] = $this->config['site_url'] . '/wap.php?g=Wap&c=Shop&a=index#shop-' . $row['store_id'];
        }
        
        if (D('Merchant_store_shop')->checkTime($row)) {
            $store['is_close'] = 0;
        }
        $store['is_close'] = $store['is_close'] ? $store['is_close'] : $row['is_close'];
        
        $store['time'] = D('Merchant_store_shop')->getBuniessName($row);
        
        $store['name'] = $row['name'];
        $store['store_notice'] = $row['store_notice'];
        $store['txt_info'] = $row['txt_info'];
        $store['image'] = isset($images[0]) ? $images[0] : '';
        $store['images'] = $images ? $images : array();
        $store['auth_files'] = $auth_files;
        $store['star'] = round($row['score_mean'],1);
        $store['month_sale_count'] = $row['sale_count'];
        $store['is_new'] = ($row['create_time'] + 864000) < time() ? 0 : 1;
        $store['delivery'] = $row['deliver_type'] == 2 ? false : true; // 是否支持配送
//         $store['delivery_time'] = $row['send_time']; // 配送时长
        if($row['deliver_type']==2){ //只有自提情况下，让安卓也有起送价
            $store['delivery'] = true;
        }
        //配送时长
        if ($row['send_time_type'] == 0) {
            $store['delivery_time'] = $row['sort_time'];
        } elseif ($row['send_time_type'] == 1) {
            $store['delivery_time'] = floatval(round($row['sort_time'] / 60, 2));
        } elseif ($row['send_time_type'] == 2) {
            $store['delivery_time'] = floatval(round($row['sort_time'] / 1440, 2));
        } elseif ($row['send_time_type'] == 3) {
            $store['delivery_time'] = floatval(round($row['sort_time'] / 10080, 2));
        } elseif ($row['send_time_type'] == 4) {
            $store['delivery_time'] = floatval(round($row['sort_time'] / 43200, 2));
        }
        
        $store['send_time_type'] = $row['send_time_type'];
        $store['delivery_time_type'] = $this->send_time_type[$row['send_time_type']];
        $store['delivery_price'] = floatval($row['basic_price']); // 起送价
        
        //配送时间
        $deliverReturn = D('Deliver_set')->getDeliverInfo($row);
        $delivery_fee = $deliverReturn['delivery_fee'];
        $per_km_price = $deliverReturn['per_km_price'];
        $basic_distance = $deliverReturn['basic_distance'];
        
        $delivery_fee2 = $deliverReturn['delivery_fee2'];
        $per_km_price2 = $deliverReturn['per_km_price2'];
        $basic_distance2 = $deliverReturn['basic_distance2'];
        
        $delivery_fee3 = $deliverReturn['delivery_fee3'];
        $per_km_price3 = $deliverReturn['per_km_price3'];
        $basic_distance3 = $deliverReturn['basic_distance3'];
        
        $start_time = $deliverReturn['delivertime_start'];
        $stop_time = $deliverReturn['delivertime_stop'];
        
        $start_time2 = $deliverReturn['delivertime_start2'];
        $stop_time2 = $deliverReturn['delivertime_stop2'];
        
        $start_time3 = $deliverReturn['delivertime_start3'];
        $stop_time3 = $deliverReturn['delivertime_stop3'];

        $store['delivery_money'] = $delivery_fee;
        
        $time = time();
        $selectDeliverTime = 0;
        if (! ($start_time == $stop_time && $start_time == '00:00:00')) {
            $start_time = strtotime(date('Y-m-d') . ' ' . $start_time);
            $stop_time = strtotime(date('Y-m-d') . ' ' . $stop_time);
            if ($start_time > $stop_time) {
                if (strtotime(date('Y-m-d')) <= $time && $time <= $stop_time) {
                    $selectDeliverTime = 1;
                } else {
                    $stop_time2 += 86400;
                    if ($start_time <= $time && $time <= $stop_time) {
                        $selectDeliverTime = 1;
                    }
                }
            } elseif ($start_time <= $time && $time <= $stop_time) {
                $selectDeliverTime = 1;
            }
            if (! ($start_time2 == $stop_time2 && $start_time2 == '00:00:00')) {
                $start_time2 = strtotime(date('Y-m-d') . ' ' . $start_time2);
                $stop_time2 = strtotime(date('Y-m-d') . ' ' . $stop_time2);
                
                if ($start_time2 > $stop_time2) {
                    if (strtotime(date('Y-m-d')) <= $time && $time <= $stop_time2) {
                        $store['delivery_money'] = $delivery_fee2;
                        $selectDeliverTime = 2;
                    } else {
                        $stop_time2 += 86400;
                        if ($start_time2 <= $time && $time <= $stop_time2) {
                            $store['delivery_money'] = $delivery_fee2;
                            $selectDeliverTime = 2;
                        }
                    }
                } elseif ($start_time2 <= $time && $time <= $stop_time2) {
                    $store['delivery_money'] = $delivery_fee2;
                    $selectDeliverTime = 2;
                }
            }
            if (! ($start_time3 == $stop_time3 && $start_time3 == '00:00:00')) {
                $start_time3 = strtotime(date('Y-m-d') . ' ' . $start_time3);
                $stop_time3 = strtotime(date('Y-m-d') . ' ' . $stop_time3);
                
                if ($start_time3 > $stop_time3) {
                    if (strtotime(date('Y-m-d')) <= $time && $time <= $stop_time3) {
                        $store['delivery_money'] = $delivery_fee3;
                        $selectDeliverTime = 3;
                    } else {
                        $stop_time3 += 86400;
                        if ($start_time3 <= $time && $time <= $stop_time3) {
                            $store['delivery_money'] = $delivery_fee3;
                            $selectDeliverTime = 3;
                        }
                    }
                } elseif ($start_time3 <= $time && $time <= $stop_time3) {
                    $store['delivery_money'] = $delivery_fee3;
                    $selectDeliverTime = 3;
                }
            }
        }else{
            $selectDeliverTime = 1;
        }

        $set = D('Deliver_set')->field(true)->where(array('area_id' => $row['area_id'], 'status' => 1))->find();
        if (empty($set)) {
            $set = D('Deliver_set')->field(true)->where(array('area_id' => $row['city_id'], 'status' => 1))->find();
            if (empty($set)) {
                $set = D('Deliver_set')->field(true)->where(array('area_id' => $row['province_id'], 'status' => 1))->find();
            }
        }
        if($selectDeliverTime==1){
            if ($row['deliver_type'] == 0 || $row['deliver_type'] == 3) {
                if($row['s_is_open_own']==1){
                    $store['delivery_price'] = floatval($row['s_basic_price1']) > -1 ? floatval($row['s_basic_price1']) : (floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) :(floatval($set['basic_price1']) ? floatval($set['basic_price1']): (floatval($this->config['basic_price1']) ? floatval($this->config['basic_price1']) : floatval($this->config['basic_price']))));//起送价
                }else{
                    $store['delivery_price'] = floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) :(floatval($set['basic_price1']) ? floatval($set['basic_price1']): (floatval($this->config['basic_price1']) ? floatval($this->config['basic_price1']) : floatval($this->config['basic_price'])));//起送价
                }
                $store['extra_price'] = floatval($row['s_extra_price']) ? floatval($row['s_extra_price']) : floatval($this->config['extra_price']);
            } else {
                $store['delivery_price'] = floatval($row['basic_price1'])?floatval($row['basic_price1']):floatval($row['basic_price']);//起送价
                $store['extra_price'] = floatval($row['extra_price']);//不满起送价另付金额支持配送
            }
        }elseif($selectDeliverTime==2){
            if ($row['deliver_type'] == 0 || $row['deliver_type'] == 3) {
                if($row['s_is_open_own']==1) {
                    $store['delivery_price'] = floatval($row['s_basic_price2']) > -1 ? floatval($row['s_basic_price2']) : (floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) : (floatval($set['basic_price2']) ? floatval($set['basic_price2']) : (floatval($this->config['basic_price2']) ? floatval($this->config['basic_price2']) : floatval($this->config['basic_price']))));//起送价
                }else{
                    $store['delivery_price'] = floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) : (floatval($set['basic_price2']) ? floatval($set['basic_price2']) : (floatval($this->config['basic_price2']) ? floatval($this->config['basic_price2']) : floatval($this->config['basic_price'])));//起送价
                }
                $store['extra_price'] = floatval($row['s_extra_price']) ? floatval($row['s_extra_price']) : floatval($this->config['extra_price']);
            } else {
                $store['delivery_price'] = floatval($row['basic_price2'])?floatval($row['basic_price2']):floatval($row['basic_price']);//起送价
                $store['extra_price'] = floatval($row['extra_price']);//不满起送价另付金额支持配送
            }

        }elseif($selectDeliverTime==3){
            if ($row['deliver_type'] == 0 || $row['deliver_type'] == 3) {
                if($row['s_is_open_own']==1) {
                    $store['delivery_price'] = floatval($row['s_basic_price3']) > -1 ? floatval($row['s_basic_price3']) : (floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) : (floatval($set['basic_price3']) ? floatval($set['basic_price3']) : (floatval($this->config['basic_price3']) ? floatval($this->config['basic_price3']) : floatval($this->config['basic_price']))));//起送价
                }else{
                    $store['delivery_price'] = floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) : (floatval($set['basic_price3']) ? floatval($set['basic_price3']) : (floatval($this->config['basic_price3']) ? floatval($this->config['basic_price3']) : floatval($this->config['basic_price'])));//起送价
                }
                $store['extra_price'] = floatval($row['s_extra_price']) ? floatval($row['s_extra_price']) : floatval($this->config['extra_price']);
            } else {
                $store['delivery_price'] = floatval($row['basic_price3'])?floatval($row['basic_price3']):floatval($row['basic_price']);//起送价
                $store['extra_price'] = floatval($row['extra_price']);//不满起送价另付金额支持配送
            }
        }else{
            if ($row['deliver_type'] == 0 || $row['deliver_type'] == 3) {
                $store['delivery_price'] = floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) : floatval($this->config['basic_price']);//起送价
                $store['extra_price'] = floatval($row['s_extra_price']) ? floatval($row['s_extra_price']) : floatval($this->config['extra_price']);
            } else {
                $store['delivery_price'] = floatval($row['basic_price']);//起送价
                $store['extra_price'] = floatval($row['extra_price']);//不满起送价另付金额支持配送
            }
        }
        if($row['deliver_type']==2){ //只有自提的情况下，去掉加价送
            $store['extra_price'] = 0;
        }
        if($row['s_is_open_virtual']==1){
            $store['delivery_money'] = floatval($row['virtual_delivery_fee']);
        }else{
            $store['delivery_money'] = floatval($store['delivery_money']);
        }
        $store['delivery_system'] = $row['deliver_type'] == 0 || $row['deliver_type'] == 3 ? true : false; // 是否是平台配送
        if (in_array($row['deliver_type'], array(2, 3, 4))) {
            $store['pick'] = 1; // 是否支持自提
        } else {
            $store['pick'] = 0; // 是否支持自提
        }
        $store['pack_alias'] = $row['pack_alias']; // 打包费别名
        $store['freight_alias'] = $row['freight_alias']; // 运费别名
        $store['coupon_list'] = array();
        if ($row['is_invoice']) {
            $store['coupon_list']['invoice'] = floatval($row['invoice_price']);
        }
        if ($row['store_discount'] != 0 && $row['store_discount'] != 100) {
            $store['coupon_list']['discount'] = floatval(round($row['store_discount'] / 10, 2));
        }
        $system_delivery = array();
        if (isset($discounts[0]) && $discounts[0]) {
            foreach ($discounts[0] as $row_d) {
                if ($row_d['type'] == 0) { // 新单
                    $store['coupon_list']['system_newuser'][] = array(
                        'money' => floatval($row_d['full_money']),
                        'minus' => floatval($row_d['reduce_money'])
                    );
                } elseif ($row_d['type'] == 1) { // 满减
                    $store['coupon_list']['system_minus'][] = array(
                        'money' => floatval($row_d['full_money']),
                        'minus' => floatval($row_d['reduce_money'])
                    );
                } elseif ($row_d['type'] == 2) { // 配送
                    $system_delivery[] = array(
                        'money' => floatval($row_d['full_money']),
                        'minus' => floatval($row_d['reduce_money'])
                    );
                }
            }
        }
        if (isset($discounts[$store_id]) && $discounts[$store_id]) {
            foreach ($discounts[$store_id] as $row_m) {
                if ($row_m['type'] == 0) {
                    $store['coupon_list']['newuser'][] = array(
                        'money' => floatval($row_m['full_money']),
                        'minus' => floatval($row_m['reduce_money'])
                    );
                } elseif ($row_m['type'] == 1) {
                    $store['coupon_list']['minus'][] = array(
                        'money' => floatval($row_m['full_money']),
                        'minus' => floatval($row_m['reduce_money'])
                    );
                }
            }
        }
        if ($store['delivery']) {
            if ($store['delivery_system']) {
                $system_delivery && $store['coupon_list']['delivery'] = $system_delivery;
            } else {
                if ($selectDeliverTime == 1) {
                    if ($row['reach_delivery_fee_type'] == 0) {
                        if ($row['basic_price'] > 0 && $row['delivery_fee'] > 0) {
                            $store['coupon_list']['delivery'][] = array(
                                'money' => floatval($row['basic_price']),
                                'minus' => floatval($row['delivery_fee'])
                            );
                        }
                    } elseif ($row['reach_delivery_fee_type'] == 1) {
                        // $store['coupon_list']['delivery'] = array('money' => false, 'minus' => $row['delivery_fee']);
                    } else {
                        $row['delivery_fee'] && $store['coupon_list']['delivery'][] = array(
                            'money' => floatval($row['no_delivery_fee_value']),
                            'minus' => floatval($row['delivery_fee'])
                        );
                    }
                } elseif ($selectDeliverTime == 2) {
                    if ($row['reach_delivery_fee_type2'] == 0) {
                        if ($row['basic_price'] > 0 && $row['delivery_fee2'] > 0) {
                            $store['coupon_list']['delivery'][] = array(
                                'money' => floatval($row['basic_price']),
                                'minus' => floatval($row['delivery_fee2'])
                            );
                        }
                    } elseif ($row['reach_delivery_fee_type2'] == 1) {
                        // $store['coupon_list']['delivery'] = array('money' => false, 'minus' => $row['delivery_fee']);
                    } else {
                        $row['delivery_fee2'] && $store['coupon_list']['delivery'][] = array(
                            'money' => floatval($row['no_delivery_fee_value2']),
                            'minus' => floatval($row['delivery_fee2'])
                        );
                    }
                } elseif ($selectDeliverTime == 3) {
                    if ($row['reach_delivery_fee_type3'] == 0) {
                        if ($row['basic_price'] > 0 && $row['delivery_fee3'] > 0) {
                            $store['coupon_list']['delivery'][] = array(
                                'money' => floatval($row['basic_price']),
                                'minus' => floatval($row['delivery_fee3'])
                            );
                        }
                    } elseif ($row['reach_delivery_fee_type3'] == 1) {
                        // $store['coupon_list']['delivery'] = array('money' => false, 'minus' => $row['delivery_fee']);
                    } else {
                        $row['delivery_fee3'] && $store['coupon_list']['delivery'][] = array(
                            'money' => floatval($row['no_delivery_fee_value3']),
                            'minus' => floatval($row['delivery_fee3'])
                        );
                    }
                }
            }
        }
        $store['coupon_list'] = $this->parseCoupon($store['coupon_list'], 'array');
        $product_list = D('Shop_goods')->getListByStoreId($store_id,0,'',false,false,true);
        $array = array(
            'store' => $store,
            'product_list' => $product_list
        );
        $this->returnCode(0, $array);
    }

	//快店优惠券 增加了店内发放的判断
	public function coupon_list(){
		$now_user = D('User')->get_user($this->_uid);

		$now_mer = M('Merchant_store')->where(array('store_id'=>$_POST['store_id']))->find();
		//$coupont_list = D('Card_new_coupon')->get_coupon_list(array('is_shop'=>1,'mer_id'=>$now_mer['mer_id'],'send_type'=>array('neq',1)));
		$where = array('mer_id'=>$now_mer['mer_id'],'status'=>array('gt',0),'end_time'=>array('gt',time()),'mer_id'=>$now_mer['mer_id'],'send_type'=>array('neq',1));
		$where['_string'] = "cate_name='shop' OR cate_name='all'";
		$coupont_list =  M('Card_new_coupon')->where($where)
						->order('status ASC,allow_new DESC,discount DESC')
						->field('coupon_id,name,img,had_pull,num,limit,des,cate_name,cate_id,discount,order_money,start_time,end_time,status,allow_new,store_id')->select();
						
		$category = array(
				'all' => L_('通用券'),
				'group' => C('config.group_alias_name'),
				'meal' => C('config.meal_alias_name'),
				'appoint' => C('config.appoint_alias_name'),
				'shop' => C('config.shop_alias_name'),
				'store' => L_('优惠买单'),
		);
		$new_coupon = array();
		$old_num=  0;

		foreach ($coupont_list as $k=>&$v) {
			$is_new_coupon = false;

			$store_ids = explode(',',$v['store_id']);
	
			if(!empty($store_ids) && !in_array($_POST['store_id'],$store_ids)){
				continue;
			}
			
			if(strpos($v['img'],'http')===false){
				$v['img']=  $this->config['site_url'].$v['img'];
			}
			$v['effective_days'] =L_('有效期X1天',intval(($v['end_time']-$v['start_time'])/86400));
			$v['start_time'] = date('Y-m-d',$v['start_time']);
			$v['end_time'] = date('Y-m-d',$v['end_time']);

			$v['platform'] = unserialize($v['platform']);
			if(!empty($v['platform'])&& !in_array('app',$v['platform'])){
				continue;
			}

			if(!empty($now_user)){
				$count = M('Card_new_coupon_hadpull')->where(array('coupon_id'=>$v['coupon_id'],'uid'=>$now_user['uid']))->count();

				if($count>0) {
					$v['limit'] = $count;
					$old_num++;
				}else if($v['status']==1 && $count==0){
					$is_new_coupon =  true;
				}
				if($count==0 && $v['status']!=1){
					continue;
				}


				if($count == 0 && $v['allow_new'] && !D('User')->check_new($now_user['uid'],'shop')){
					continue;
				}
			}

			$v['discount'] = floatval($v['discount']);
			$v['order_money'] = floatval($v['order_money']);

			$v['cate_id'] = unserialize($v['cate_id']);
			$v['cate_name']  = $category[$v['cate_name']];
			if(!empty($v['cate_id'])){
				$v['type_name']=$v['cate_id']['cat_name'];
			}
			unset($coupont_list[$k]['cate_id']);
			if($is_new_coupon){
				$new_coupon[] =  $v;
			}
			$tmp[]  =$v;
			
		}
		if(empty($tmp)){
			$tmp = array();
		}

		$arr['coupont_list'] = $tmp;
		if(!empty($new_coupon)){
			$arr['coupont_list'] = $new_coupon;
			$arr['get_status'] = 0;
		}else{
			if($old_num==count($tmp)){
			
				$arr['get_status'] = 1;
			}else{
				$arr['get_status'] = 0;
			}
			
				
		}
		$this->returnCode(0,$arr);
	}

	public function had_pull()
	{
		$ticket = I('ticket', false);
		$device_id    =   I('Device-Id',false);
		if($ticket && $device_id){
			$info = ticket::get($ticket, $device_id, true);
			$now_user = D('User')->get_user($info['uid']);
		}else{
			$this->returnCode('20046009');
		}

		$coupon_id = explode(',',trim($_POST['coupon_id']));
		$uid = $now_user['uid'];
		$model = D('Card_new_coupon');
		$total_num=0;
		foreach ($coupon_id as $vv) {
			if(empty($vv)){
				continue;
			}
			$now_coupon = $model->where(array('coupon_id' => $vv))->find();
			$num=0;
			for($i=0;$i<$now_coupon['limit'];$i++)  {
				$result = $model->had_pull($vv, $uid,'','','',true);
				if (!$result['error_code']) {
					$num++;
				}
			}
			$model->decrease_sku(0,$num, $vv);//网页领取完，微信卡券库存需要同步减少
			$total_num+=$num;
		}
		if($total_num){

			if($now_user = M('User')->where(array('uid'=>$uid))->find()){
				$models = new templateNews(C('config.wechat_appid'), C('config.wechat_appsecret'));
				$now_merchant = M('Merchant')->where(array('mer_id'=>$now_coupon['mer_id']))->find();
				$cate_platform = $model->cate_platform();
				$res = $models->sendTempMsg(
					'TM00251', 
					array('href' => C('config.site_url').'/wap.php?g=Wap&c=My_card&a=merchant_card&mer_id='.$now_coupon['mer_id'], 
					'wecha_id' => $now_user['openid'], 
					'first' => L_('您成功领取了X1张商家【X2】的X3优惠券',array('X1'=>$total_num,'X2'=>$now_merchant['name'],'X3'=>$cate_platform['category'][$now_coupon['cate_name']])), 
					'toName' => $now_user['nickname'], 
					'gift' => '优惠券 【'.$now_coupon['name'].'】，满'.$now_coupon['order_money'].'减'.$now_coupon['discount'],'time'=>date(L_("Y年m月d日 H:i")), 
					'remark' => L_('有效期：X1 至 X2',array('X1'=>date("Y-m-d",$now_coupon['start_time']),'X2'=>date("Y-m-d",$now_coupon['end_time'])))),
					$now_coupon['mer_id']
				);
			}
			$this->returnCode(0, array('msg'=>L_('成功领取了X1张优惠券',$total_num)));
		}else{
			$this->returnCode('20171011', '',L_('领取失败'));
		}
	}
}
?>