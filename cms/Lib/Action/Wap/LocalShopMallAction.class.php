<?php
/*
 * 商城
 *
 */
class LocalShopMallAction extends BaseAction{

    protected $send_time_type = array('分钟', '小时', '天', '周', '月');

	public function index()
	{
        // 本地化商城首页顶部轮播图
        $localization_mall_store_adver = D("Adver")->get_adver_by_key("localization_mall_store_adver", 1);
        $this->assign('localization_mall_store_adver',$localization_mall_store_adver);
        // wap站本地化商城首页中间3张图片
        $localization_mall_center_adver = D("Adver")->get_adver_by_key("localization_mall_center_adver", 3);
        $this->assign('localization_mall_center_adver',$localization_mall_center_adver);
        $category_list = D('Goods_category')->get_list();
        $this->assign('cat_fid', isset($category_list[0]['id']) ? $category_list[0]['id'] : 0);
		$this->display();
	}

    public function ajax_index()
    {
        $return = array();
        $return['banner_list'] = D('Adver')->get_adver_by_key('wap_mall_index_top', 5);
        $return['slider_list'] = D('Slider')->get_slider_by_key('wap_mall_slider', 0);
        $return['category_list'] = D('Goods_category')->get_list();


        $where = array();
        $where['cat_fid'] = $return['category_list'][0]['id'];
        $where['page'] = 1;

        $localization_shopping_mall_store = $this->config['localization_shopping_mall_store'];
        if ($localization_shopping_mall_store > 0) {
            $where['store_id'] = $localization_shopping_mall_store;
        }


        $return['goods_list'] = D('Shop_goods')->get_list_by_option($where, 1, 1, false);


        $_SESSION['session_store_id'] = 0;
        echo json_encode($return);
    }


    public function ajax_list()
    {
        $where = array();
        $where['cat_fid'] = isset($_POST['cat_fid']) ? intval($_POST['cat_fid']) : 0;
        $where['cat_id'] = isset($_POST['cat_id']) ? intval($_POST['cat_id']) : 0;
        $where['store_id'] = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
        $where['page'] = isset($_POST['page']) ? intval($_POST['page']) : 0;

        $pids1 = isset($_POST['pids1']) ? trim(htmlspecialchars($_POST['pids1'])) : '';
        $pids2 = isset($_POST['pids2']) ? trim(htmlspecialchars($_POST['pids2'])) : '';
        $pids3 = isset($_POST['pids3']) ? trim(htmlspecialchars($_POST['pids3'])) : '';
        $pids4 = isset($_POST['pids4']) ? trim(htmlspecialchars($_POST['pids4'])) : '';
        $where['key'] = isset($_POST['key']) ? trim(htmlspecialchars($_POST['key'])) : '';
        $search_type = isset($_POST['search_type']) ? intval($_POST['search_type']) : 0;
        $sort = isset($_POST['sort']) ? intval($_POST['sort']) : 1;//排序字段(1:goods_id, 2:sell_count, 3:price)
        if (!in_array($sort, array(1, 2, 3))) $sort = 1;
        $sort_type = isset($_POST['sort_type']) ? intval($_POST['sort_type']) : 1;//排序方式(1:DESC, 2:ASC)
        if ($sort_type != 1 && $sort_type != 2) $sort_type = 1;

        $where['pids'] = null;
        if ($pids1) $where['pids'][] = $pids1;
        if ($pids2) $where['pids'][] = $pids2;
        if ($pids3) $where['pids'][] = $pids3;
        if ($pids4) $where['pids'][] = $pids4;

        // 绑定了店铺 始终取该店铺的信息
        $localization_shopping_mall_store = $this->config['localization_shopping_mall_store'];
        if ($localization_shopping_mall_store > 0) {
            $where['store_id'] = $localization_shopping_mall_store;
        }

        if ($search_type == 0) {
            $return = D('Shop_goods')->get_list_by_option($where, $sort, $sort_type, false);
        } else {
            $return = D('Merchant_store_shop')->get_store_by_search($where);
        }
        exit(json_encode($return));
    }

    public function search()
    {
        $keyword = isset($_GET['key']) ? trim(htmlspecialchars($_GET['key'])) : '';
        $store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;

        $localization_shopping_mall_store = $this->config['localization_shopping_mall_store'];
        if ($localization_shopping_mall_store > 0) {
            $store_id = $localization_shopping_mall_store;
        }

        $this->assign('store_id', $store_id);
        $this->assign('keyword', $keyword);
        $this->display();
    }


    public function goods_list()
    {
        $cat_fid = isset($_GET['cat_fid']) ? intval($_GET['cat_fid']) : 0;
        $cat_id = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;
        $properties = D('Goods_properties')->field(true)->where(array('cat_id' => $cat_id, 'status' => 1))->select();
        $pids = array();
        $names = array();
        foreach ($properties as $pro) {
            $pids[] = $pro['id'];
            $names[] = $pro['name'];
        }
        $names = implode(',', $names);
        $value_list = array();
        if ($pids) {
            $temp_list = D('Goods_properties_value')->field(true)->where(array('pid' => array('in', $pids)))->select();
            foreach ($temp_list as $val) {
                $value_list[$val['pid']][] = $val;
            }
        }
        foreach ($properties as &$vo) {
            if (isset($value_list[$vo['id']])) {
                $vo['value_list'] = $value_list[$vo['id']];
            } else {
                $vo['value_list'] = null;
            }
        }
        $title = '';
        if ($category = D('Goods_category')->field(true)->where(array('id' => $cat_fid))->find()) {
            $title = $category['name'];
        }
        if ($category = D('Goods_category')->field(true)->where(array('id' => $cat_id))->find()) {
            $title = $title ? $title . '-' . $category['name'] : $category['name'];
        }
        $this->assign(array('cat_fid' => $cat_fid, 'cat_id' => $cat_id));
        $this->assign('properties', $properties);
        $this->assign('title', $title);
        $this->assign('names', $names);
        $this->display();
    }

    public function detail()
    {
        $goods_id = intval($_GET['goods_id']);
        if(empty($goods_id)){
            $this->error_tips('商品不存在！');
        }
        $database_shop_goods = D('Shop_goods');
        $now_goods = $database_shop_goods->get_goods_by_id($goods_id);
        if(empty($now_goods)){
            $this->error_tips('商品不存在！');
        }
        if($now_goods['jd_sku_id'] != 0){
            import('ORG.Net.Http');
            $url_state = 'https://bizapi.jd.com/api/product/skuState';
            $request_state['token'] = $this->config['jd_access_token'];
            $request_state['sku'] = $now_goods['jd_sku_id'];
            $return1 = http::curlPost($url_state,$request_state);
            if($return1['result'][0]['state']==0){
                $this->error_tips('当前商品已下架！');
            }
            $url_check = 'https://bizapi.jd.com/api/product/check';
            $request_c['token'] = $this->config['jd_access_token'];
            $request_c['skuIds'] = $now_goods['jd_sku_id'];
            $return2 = http::curlPost($url_check,$request_c);
            $this->assign('is7ToReturn',$return2['result'][0]['is7ToReturn']);
            if($return2['result'][0]['saleState']==0){
                $this->error_tips('当前商品不可售！');
            }
            $url_price = "https://bizapi.jd.com/api/price/getSellPrice";
            $data_price['token'] = $this->config['jd_access_token'];
            $data_price['sku']=$now_goods['jd_sku_id'];
            $data_price['queryExts'] =  'containsTax';
            $return_price = Http::curlPost($url_price,$data_price);
            //价格变动
            if($return_price['result'][0]['price'] != $now_goods['cost_price']){
                //价格加成
                $percent = floatval(C('config.jd_price_add_percent'));
                if($percent <= 0){
                    $percent = 1;
                }else{
                    $percent = 1 + ($percent / 100);
                }
                $goods['price'] = round($return_price['result'][0]['price'] * $percent,1);
                $goods['cost_price'] = $return_price['result'][0]['price'];
                M('Shop_goods')->where(array('goods_id'=>$now_goods['goods_id']))->save($goods);
                $now_goods = $database_shop_goods->get_goods_by_id($goods_id);
            }
        }
        //如果设置了最小起购，限购就无效
        if ($now_goods['min_num'] > 1) {
            $now_goods['max_num'] = 0;
        }

        $store_id = $now_goods['store_id'];

        $where = array('store_id' => $store_id);
        $now_store = D('Merchant_store')->field(true)->where($where)->find();
        //资质认证
        // if ($this->config['store_shop_auth'] == 1 && $now_store['auth'] < 3) {
        // $this->error_tips('店铺没有通过资质认证');
        // }
        $now_shop = D('Merchant_store_shop')->field(true)->where($where)->find();
        if (empty($now_shop) || empty($now_store)) {
            $this->error_tips('该店铺没有完善店铺信息');
        }
        $city_name = $province_name = '';
        $areas = D('Area')->field(true)->where(array('area_id' => array('in', array($now_store['province_id'], $now_store['city_id']))))->select();
        foreach ($areas as $area) {
            if ($area['area_pid']) {
                $city_name = $area['area_name'];
            } else {
                $province_name = $area['area_name'];
            }
        }
// 		$discounts = D('Shop_discount')->get_discount_byids(array($store_id));
        $discounts = D('Shop_discount')->getDiscounts($now_store['mer_id'], $store_id);
        $row = array_merge($now_store, $now_shop);

        $store = array();
        $store_image_class = new store_image();
        $images = $store_image_class->get_allImage_by_path($row['pic_info']);

        $store['store_id'] = $row['store_id'];
        $store['phone'] = $row['phone'];
        $store['long'] = $row['long'];
        $store['lat'] = $row['lat'];
        $store['store_theme'] = $row['store_theme'];
        $store['adress'] = $row['adress'];
        $store['now_city_name'] = $province_name . ' ' . $city_name;
        $store['is_close'] = 1;
        $now_time = date('H:i:s');
        if ($row['open_1'] == '00:00:00' && $row['close_1'] == '00:00:00') {
            $store['time'] = '24小时营业';
            $store['is_close'] = 0;
        } else {
            $store['time'] = substr($row['open_1'], 0, -3) . '~' . substr($row['close_1'], 0, -3);
            if ($row['open_1'] < $now_time && $now_time < $row['close_1']) {
                $store['is_close'] = 0;
            }
            if ($row['open_2'] != '00:00:00' || $row['close_2'] != '00:00:00') {
                $store['time'] .= ',' . substr($row['open_2'], 0, -3) . '~' . substr($row['close_2'], 0, -3);
                if ($row['open_2'] < $now_time && $now_time < $row['close_2']) {
                    $store['is_close'] = 0;
                }
            }
            if ($row['open_3'] != '00:00:00' || $row['close_3'] != '00:00:00') {
                $store['time'] .= ',' . substr($row['open_3'], 0, -3) . '~' . substr($row['close_3'], 0, -3);
                if ($row['open_3'] < $now_time && $now_time < $row['close_3']) {
                    $store['is_close'] = 0;
                }
            }
        }

        $store['name'] = $row['name'];
        $store['store_notice'] = $row['store_notice'];
        $store['txt_info'] = $row['txt_info'];
        $store['image'] = isset($images[0]) ? $images[0] : '';
        $store['star'] = round($row['score_mean'],1);
        $store['month_sale_count'] = $row['sale_count'];
        $store['delivery'] = $row['deliver_type'] == 2 ? false : true;//是否支持配送
        $store['delivery_time'] = $row['send_time'];//配送时长
        $store['delivery_price'] = floatval($row['basic_price']);//起送价


        $store['pack_alias'] = $row['pack_alias'];//打包费别名
        $store['freight_alias'] = $row['freight_alias'];//运费别名
        $store['coupon_list'] = array();
// 		if ($row['is_invoice']) {
// 			$store['coupon_list']['invoice'] = floatval($row['invoice_price']);
// 		}
        if ($row['store_discount'] != 0 && $row['store_discount'] != 100) {
            $store['coupon_list']['discount'] = $row['store_discount']/10;
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
                if ($is_have_two_time) {
                    if ($row['reach_delivery_fee_type2'] == 0) {
                        if ($row['basic_price'] > 0 && $row['delivery_fee2'] > 0) {
                            $store['coupon_list']['delivery'][] = array('money' => floatval($row['basic_price']), 'minus' => floatval($row['delivery_fee2']));
                        }
                    } elseif ($row['reach_delivery_fee_type'] == 1) {
                        //$store['coupon_list']['delivery'] = array('money' => false, 'minus' => $row['delivery_fee']);
                    } else {
                        $row['delivery_fee2'] && $store['coupon_list']['delivery'][] = array('money' => floatval($row['no_delivery_fee_value2']), 'minus' => floatval($row['delivery_fee2']));
                    }
                } else {
                    if ($row['reach_delivery_fee_type'] == 0) {
                        if ($row['basic_price'] > 0 && $row['delivery_fee'] > 0) {
                            $store['coupon_list']['delivery'][] = array('money' => floatval($row['basic_price']), 'minus' => floatval($row['delivery_fee']));
                        }
                    } elseif ($row['reach_delivery_fee_type'] == 1) {
                        //$store['coupon_list']['delivery'] = array('money' => false, 'minus' => $row['delivery_fee']);
                    } else {
                        $row['delivery_fee'] && $store['coupon_list']['delivery'][] = array('money' => floatval($row['no_delivery_fee_value']), 'minus' => floatval($row['delivery_fee']));
                    }
                }
            }
        }
        $this->assign('goods_count', D('Shop_goods')->where(array('store_id' => $store_id, 'status' => 1))->count());
        $store['phone'] = explode(' ', $store['phone']);
        $this->assign('store', $store);
        $this->assign('goods_detail', $now_goods['list'] ? json_encode($now_goods['list']) : '');
        $this->assign('now_goods', $now_goods);
        if ($_SESSION['openid'] && $services = D('Customer_service')->where(array('mer_id' => $now_store['mer_id']))->select()) {
            $key = $this->get_encrypt_key(array('app_id'=>$this->config['im_appid'],'openid' => $_SESSION['openid']), $this->config['im_appkey']);
            $kf_url = ($this->config['im_url'] ? $this->config['im_url'] : 'http://im-link.weihubao.com').'/?app_id=' . $this->config['im_appid'] . '&openid=' . $_SESSION['openid'] . '&key=' . $key . '#serviceList_' . $now_store['mer_id'];
            $this->assign('kf_url', $kf_url);
        }
        $this->display();
    }


    public function cart()
    {
// 		$carts = $_COOKIE['mall_goods_cart'];
// 		$carts = json_decode($carts, true);

        $store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : $_SESSION['session_store_id'];
        $localization_shopping_mall_store = $this->config['localization_shopping_mall_store'];
        if ($localization_shopping_mall_store > 0) {
            $store_id = $localization_shopping_mall_store;
        }
        $carts = array();
        for ($i = 0; $i < 40; $i++) {
            $tmpCookie = cookie('shop_mall_goods_cart_' . $store_id . '_' . $i);
            if (!empty($tmpCookie)) {
                $tmpArr = json_decode($tmpCookie, true);
                if (empty($tmpArr)) {
                    $tmpArr = array();
                }
                $carts = array_merge($carts, $tmpArr);
            } else {
                break;
            }
        }

        $goods_store = array();
        $goods_ids = array();
        $cart_list = array();
        foreach ($carts as $cart) {
            $goods_store[$cart['productId']] = $cart['store_id'];
            $goods_ids[] = $cart['productId'];
        }
        if ($goods_ids) {
            $store_list = array();
            $field = 'a.name, a.store_id, a.open_1, a.close_1, a.open_2, a.close_2, a.open_3, a.close_3, a.area_id, a.city_id, a.province_id, a.mer_id, b.*';
            $s_list = D('Merchant_store')
                ->alias('a')
                ->join('join pigcms_merchant_store_shop as b on a.store_id=b.store_id')
                ->field($field)->where(array('a.store_id' => array('in', $goods_store)))->select();

            foreach ($s_list as $s) {
                if ($store_id && $store_id != $s['store_id']) continue;
                $store_list[$s['store_id']] = array();
                $store_list[$s['store_id']] = $s;
            }
            $is_close = 1;
            if (D('Merchant_store_shop')->checkTime($store_list[$store_id])) {
                $is_close = 0;
            }
            $store_info = $store_list[$store_id];
            $store_info['is_close'] = $is_close ? $is_close :  $store_info['is_close'];

            $store_info['delivery'] = $store_info['deliver_type'] == 2 ? false : true;//是否支持配送


            //配送时长
            if ($store_info['send_time_type'] == 0) {
                $store_info['delivery_time'] = $store_info['sort_time'];
            } elseif ($store_info['send_time_type'] == 1) {
                $store_info['delivery_time'] = floatval(round($store_info['sort_time'] / 60, 2));
            } elseif ($store_info['send_time_type'] == 2) {
                $store_info['delivery_time'] = floatval(round($store_info['sort_time'] / 1440, 2));
            } elseif ($store_info['send_time_type'] == 3) {
                $store_info['delivery_time'] = floatval(round($store_info['sort_time'] / 10080, 2));
            } elseif ($store_info['send_time_type'] == 4) {
                $store_info['delivery_time'] = floatval(round($store_info['sort_time'] / 43200, 2));
            }
            $store_info['delivery_time_type'] = $this->send_time_type[$store_info['send_time_type']];//时间单位

            $store_info['delivery'] = $store_info['deliver_type'] == 2 ? false : true;//是否支持配送

            $deliverReturn = D('Deliver_set')->getDeliverInfo($store_info);

            $delivery_fee = $deliverReturn['delivery_fee'];

            $delivery_fee2 = $deliverReturn['delivery_fee2'];

            $delivery_fee3 = $deliverReturn['delivery_fee3'];

            $start_time = $deliverReturn['delivertime_start'];
            $stop_time = $deliverReturn['delivertime_stop'];

            $start_time2 = $deliverReturn['delivertime_start2'];
            $stop_time2 = $deliverReturn['delivertime_stop2'];

            $start_time3 = $deliverReturn['delivertime_start3'];
            $stop_time3 = $deliverReturn['delivertime_stop3'];

            $store_info_info['delivery_money'] = $delivery_fee;

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
                            $store_info['delivery_money'] = $delivery_fee2;
                            $selectDeliverTime = 2;
                        } else {
                            $stop_time2 += 86400;
                            if ($start_time2 <= $time && $time <= $stop_time2) {
                                $store_info['delivery_money'] = $delivery_fee2;
                                $selectDeliverTime = 2;
                            }
                        }
                    } elseif ($start_time2 <= $time && $time <= $stop_time2) {
                        $store_info['delivery_money'] = $delivery_fee2;
                        $selectDeliverTime = 2;
                    }
                }
                if (! ($start_time3 == $stop_time3 && $start_time3 == '00:00:00')) {
                    $start_time3 = strtotime(date('Y-m-d') . ' ' . $start_time3);
                    $stop_time3 = strtotime(date('Y-m-d') . ' ' . $stop_time3);

                    if ($start_time3 > $stop_time3) {
                        if (strtotime(date('Y-m-d')) <= $time && $time <= $stop_time3) {
                            $store_info['delivery_money'] = $delivery_fee3;
                            $selectDeliverTime = 3;
                        } else {
                            $stop_time3 += 86400;
                            if ($start_time3 <= $time && $time <= $stop_time3) {
                                $store_info['delivery_money'] = $delivery_fee3;
                                $selectDeliverTime = 3;
                            }
                        }
                    } elseif ($start_time3 <= $time && $time <= $stop_time3) {
                        $store_info['delivery_money'] = $delivery_fee3;
                        $selectDeliverTime = 3;
                    }
                }
            }else{
                $selectDeliverTime = 1;
            }
            $set = D('Deliver_set')->field(true)->where(array('area_id' => $store_info['area_id'], 'status' => 1))->find();
            if (empty($set)) {
                $set = D('Deliver_set')->field(true)->where(array('area_id' => $store_info['city_id'], 'status' => 1))->find();
                if (empty($set)) {
                    $set = D('Deliver_set')->field(true)->where(array('area_id' => $store_info['province_id'], 'status' => 1))->find();
                }
            }
            if($selectDeliverTime==1){
                if ($store_info['deliver_type'] == 0 || $store_info['deliver_type'] == 3) {
                    if($store_info['s_is_open_own']==1){
                        $store_info['delivery_price'] = floatval($store_info['s_basic_price1']) > -1 ? floatval($store_info['s_basic_price1']) : (floatval($store_info['s_basic_price']) > -1 ? floatval($store_info['s_basic_price']) :(floatval($set['basic_price1']) ? floatval($set['basic_price1']): (floatval($this->config['basic_price1']) ? floatval($this->config['basic_price1']) : floatval($this->config['basic_price']))));//起送价
                    }else{
                        $store_info['delivery_price'] = floatval($store_info['s_basic_price']) > -1 ? floatval($store_info['s_basic_price']) :(floatval($set['basic_price1']) ? floatval($set['basic_price1']): (floatval($this->config['basic_price1']) ? floatval($this->config['basic_price1']) : floatval($this->config['basic_price'])));//起送价
                    }
                    $store_info['extra_price'] = floatval($store_info['s_extra_price']) ? floatval($store_info['s_extra_price']) : floatval($this->config['extra_price']);
                } else {
                    $store_info['delivery_price'] = floatval($store_info['basic_price1'])?floatval($store_info['basic_price1']):floatval($store_info['basic_price']);//起送价
                    $store_info['extra_price'] = floatval($store_info['extra_price']);//不满起送价另付金额支持配送
                }
            }elseif($selectDeliverTime==2){
                if ($store_info['deliver_type'] == 0 || $store_info['deliver_type'] == 3) {
                    if($store_info['s_is_open_own']==1) {
                        $store_info['delivery_price'] = floatval($store_info['s_basic_price2']) > -1 ? floatval($store_info['s_basic_price2']) : (floatval($store_info['s_basic_price']) > -1 ? floatval($store_info['s_basic_price']) : (floatval($set['basic_price2']) ? floatval($set['basic_price2']) : (floatval($this->config['basic_price2']) ? floatval($this->config['basic_price2']) : floatval($this->config['basic_price']))));//起送价
                    }else{
                        $store_info['delivery_price'] = floatval($store_info['s_basic_price']) > -1 ? floatval($store_info['s_basic_price']) : (floatval($set['basic_price2']) ? floatval($set['basic_price2']) : (floatval($this->config['basic_price2']) ? floatval($this->config['basic_price2']) : floatval($this->config['basic_price'])));//起送价
                    }
                    $store_info['extra_price'] = floatval($store_info['s_extra_price']) ? floatval($store_info['s_extra_price']) : floatval($this->config['extra_price']);
                } else {
                    $store_info['delivery_price'] = floatval($store_info['basic_price2'])?floatval($store_info['basic_price2']):floatval($store_info['basic_price']);//起送价
                    $store_info['extra_price'] = floatval($store_info['extra_price']);//不满起送价另付金额支持配送
                }

            }elseif($selectDeliverTime==3){
                if ($store_info['deliver_type'] == 0 || $store_info['deliver_type'] == 3) {
                    if($store_info['s_is_open_own']==1) {
                        $store_info['delivery_price'] = floatval($store_info['s_basic_price3']) > -1 ? floatval($store_info['s_basic_price3']) : (floatval($store_info['s_basic_price']) > -1 ? floatval($store_info['s_basic_price']) : (floatval($set['basic_price3']) ? floatval($set['basic_price3']) : (floatval($this->config['basic_price3']) ? floatval($this->config['basic_price3']) : floatval($this->config['basic_price']))));//起送价
                    }else{
                        $store_info['delivery_price'] = floatval($store_info['s_basic_price']) > -1 ? floatval($store_info['s_basic_price']) : (floatval($set['basic_price3']) ? floatval($set['basic_price3']) : (floatval($this->config['basic_price3']) ? floatval($this->config['basic_price3']) : floatval($this->config['basic_price'])));//起送价
                    }
                    $store_info['extra_price'] = floatval($store_info['s_extra_price']) ? floatval($store_info['s_extra_price']) : floatval($this->config['extra_price']);
                } else {
                    $store_info['delivery_price'] = floatval($store_info['basic_price3'])?floatval($store_info['basic_price3']):floatval($store_info['basic_price']);//起送价
                    $store_info['extra_price'] = floatval($store_info['extra_price']);//不满起送价另付金额支持配送
                }
            }else{
                if ($store_info['deliver_type'] == 0 || $store_info['deliver_type'] == 3) {
                    $store_info['delivery_price'] = floatval($store_info['s_basic_price']) > -1 ? floatval($store_info['s_basic_price']) : floatval($this->config['basic_price']);//起送价
                    $store_info['extra_price'] = floatval($store_info['s_extra_price']) ? floatval($store_info['s_extra_price']) : floatval($this->config['extra_price']);
                } else {
                    $store_info['delivery_price'] = floatval($store_info['basic_price']);//起送价
                    $store_info['extra_price'] = floatval($store_info['extra_price']);//不满起送价另付金额支持配送
                }
            }


            $store_info['delivery_system'] = $store_info['deliver_type'] == 0 || $store_info['deliver_type'] == 3 ? true : false;//是否是平台配送
            if (in_array($store_info['deliver_type'], array(2, 3, 4))) {
                $store_info['pick'] = "" . 1;//是否支持自提
            } else {
                $store_info['pick'] = "" . 0;//是否支持自提
            }
            $store_info['pack_alias'] = $store_info['pack_alias'];//打包费别名
            $store_info['freight_alias'] = $store_info['freight_alias'];//运费别名
//            $store_info['coupon_list'] = array();
//            if ($store_info['is_invoice']) {
//                $store_info['coupon_list']['invoice'] = "" . floatval($store_info['invoice_price']);
//            }
//            if ($store_info['store_discount'] != 0 && $store_info['store_discount'] != 100) {
//                $store_info['coupon_list']['discount'] = "" . $store_info['store_discount']/10;
//            }
            $store_info['delivery_price'] = "" . $store_info['delivery_price'];
            $store_info['extra_price'] = "" . $store_info['extra_price'];
            $store_info['delivery_system'] = "" . $store_info['delivery_system'];
            $store_info['deliver_type'] = "" . $store_info['deliver_type'];

            $store_list[$store_id] = $store_info;

            $goods_list = array();
            $g_list = D('Shop_goods')->field(true)->where(array('goods_id' => array('in', $goods_ids), 'status' => 1))->select();
            $goods_image_class = new goods_image();
            foreach ($g_list as $row) {
                if ($store_id && $store_id != $row['store_id']) continue;
//				if($row['spec_value']!=''){
//					$row['extra_pay_price']=0;
//				}
                if ($row['seckill_type'] == 1) {
                    $now_time = date('H:i');
                    $open_time = date('H:i', $row['seckill_open_time']);
                    $close_time = date('H:i', $row['seckill_close_time']);
                } else {
                    $now_time = time();
                    $open_time = $row['seckill_open_time'];
                    $close_time = $row['seckill_close_time'];
                }
                $row['is_seckill_price'] = false;
                $row['o_price'] = floatval($row['price']);
                if ($open_time < $now_time && $now_time < $close_time && floatval($row['seckill_price']) > 0) {
                    $row['price'] = floatval($row['seckill_price']);
                    $row['is_seckill_price'] = true;
                } else {
                    $row['price'] = floatval($row['price']);
                }

                $row['old_price'] = floatval($row['old_price']);
                $row['seckill_price'] = floatval($row['seckill_price']);

                $row['url'] = U('LocalShopMall/detail', array('goods_id' => $row['goods_id']), true, false, true);
                $tmp_pic_arr = explode(';', $row['image']);
                foreach ($tmp_pic_arr as $key => $value) {
                    if('http' === substr($value,0,4)){
                        $temp_image = $value;
                    }else{
                        $temp_image = $goods_image_class->get_image_by_path($value);
                    }
                    if ($temp_image) {
                        if(is_array($temp_image)){
                            $row['image'] = $temp_image['image'];
                            break;
                        }else{
                            $row['image'] = $temp_image;
                            break;
                        }
                    }
                }
                $goods_list[$row['goods_id']] = $row;
            }
        }

        foreach ($carts as $vo) {
            if ($store_id && $store_id != $vo['store_id']) continue;
            $index_key = 's_' . $vo['store_id'] . '_g_' . $vo['productId'];
            $name = $pre = '';
            if ($vo['productParam']) {
                foreach ($vo['productParam'] as $param) {
                    if ($param['type'] == 'spec') {
                        $index_key .= '_s_' . $param['id'];
                        $name .= $pre . $param['name'];
                        $pre = ',';
                    } else {
                        foreach ($param['data'] as $d) {
                            $index_key .= '_v_' . $d['id'];
                            $name .= $pre . $d['name'];
                            $pre = ',';
                        }
                    }
                }
            }

            $goods_list[$vo['productId']]['index_key'] = $index_key;
// 			$goods_list[$vo['productId']]['price'] = $vo['productPrice'];
            $goods_list[$vo['productId']]['num'] = $vo['count'];
            if ($name) {
                $goods_list[$vo['productId']]['name'] = $vo['productName'] . '(' . $name . ')';
            }
            $goods_list[$vo['productId']]['price'] = $vo['productPrice'];
            $total_price = $vo['count'] * $goods_list[$vo['productId']]['price'];
            if (isset($store_list[$vo['store_id']]['goods_list'])) {
                $store_list[$vo['store_id']]['total_price'] += $total_price;
                $store_list[$vo['store_id']]['goods_list'][] = $goods_list[$vo['productId']];
            } else {
                $store_list[$vo['store_id']]['total_price'] = $total_price;
                $store_list[$vo['store_id']]['goods_list'] = array($goods_list[$vo['productId']]);
            }
        }

        $this->assign('product_list', $store_list ? $store_list : array());
        if ($store_info['leveloff']) {
            unset($store_info['leveloff']);
        }
        $this->assign('store_info', $store_info ?   json_encode($store_info, true) : '');
        $this->assign('store_id', $store_id);
        $this->display();
    }





    public function reply()
    {
        $goods_id = isset($_GET['goods_id']) ? intval($_GET['goods_id']) : 0;
        $store_shop = array();
        if ($goods = D('Shop_goods')->field(true)->where(array('goods_id' => $goods_id))->find()) {
            $store = D('Merchant_store')->field(true)->where(array('store_id' => $goods['store_id']))->find();
            $store_shop = D('Merchant_store_shop')->field(true)->where(array('store_id' => $goods['store_id']))->find();
            $store = array_merge($store, $store_shop);
            $this->assign('store', $store);
        } else {
            $this->error_tips('商品不存在！');
        }
        $this->display();
    }

    public function addressinfo()
    {
        $now_store = D('Merchant_store')->get_store_by_storeId($_GET['store_id']);
        if(empty($now_store)){
            $this->error_tips('该店铺不存在！');
        }
        $this->assign('now_store',$now_store);

        $this->display();
    }

    public function confirm_order()
    {
        $this->isLogin();
        //delivery_type 0:平台配送，1：商家配送，2：自提，3:平台配送或自提，4：商家配送或自提
        $store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
        $cartid = isset($_GET['cartid']) ? htmlspecialchars(trim($_GET['cartid'])) : '';
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        $deliverExtraPrice = isset($_GET['deliverExtraPrice']) ? intval($_GET['deliverExtraPrice']) : 0;//配送附加费

        if ($cartid && ($orderTemp = D('Shop_order_temp')->field(true)->where(array('cartid' => $cartid, 'store_id' => $store_id))->find())) {
            $list = json_decode($orderTemp['info'], true);
            $data = array();
            foreach ($list as $rowset) {
                if (isset($rowset['data']) && $rowset['data']) {
                    $data = array_merge($data, $rowset['data']);
                }
            }
            $return = D('Shop_goods')->checkCart($store_id, $this->user_session['uid'], $data);
            $this->assign('cartid', $cartid);
        } elseif ($order_id && ($order = D('Shop_order')->get_order_detail(array('order_id' => $order_id, 'uid' => $this->user_session['uid'])))) {
            $return = D('Shop_goods')->checkCart($store_id, $this->user_session['uid'], $order['info'], 0);
            $this->assign('order_id', $order_id);
        } else {
            $cookieData = $this->getCookieData('cart', $store_id);
            if(empty($cookieData)) {
                redirect(U('LocalShopMall/index'));
                exit;
            }
            $return = D('Shop_goods')->checkCart($store_id, $this->user_session['uid'], $cookieData);
        }

        if ($return['error_code']) {
            $this->error_tips($return['msg']);
        }
        if ($_GET['village_id']) {
            $village_id = intval($_GET['village_id']);
        } elseif (cookie('visit_village_id')) {
            $village_id = cookie('visit_village_id');
        } else {
            $village_id = 0;
        }
        $this->assign('village_id', $village_id);

        //判断商家是否开启了自有支付
        $is_own = 0;
        $merchant_ownpay = D('Merchant_ownpay')->field('mer_id', true)->where(array('mer_id' => $return['mer_id']))->find();
        foreach ($merchant_ownpay as $ownKey => $ownValue) {
            $ownValueArr = unserialize($ownValue);
            if ($ownValueArr['open']) {
                //$is_own = 1;
            }
        }
        if ($is_own) {
            if ($return['delivery_type'] == 0) {
                $this->error_tips('商家配置的配送信息不正确');
            } elseif ($return['delivery_type'] == 3) {
                $return['delivery_type'] = 2;
            }
        }

        if($return['sorts_discount_type']==0){
            $return['this_discount_price'] = round($return['sto_first_reduce'] + $return['sto_full_reduce'] + $return['sys_first_reduce'] + $return['sys_full_reduce'], 2) + ($return['price'] - $return['vip_discount_money']);

        }else{
            if($return['optimalDiscountMoney']>0){
                $return['this_discount_price'] = round($return['sto_first_reduce'] + $return['sto_full_reduce'] + $return['sys_first_reduce'] + $return['sys_full_reduce'], 2) + ($return['price'] - $return['optimalDiscountMoney']);
            }
        }
        $basic_price = $return['price'];//商品实际总价
        if ($this->config['open_extra_price'] > 0) {
            $basic_price += $return['extra_price'];
        }
        $return['price'] = round($return['vip_discount_money'] - round($return['sto_first_reduce'] + $return['sto_full_reduce'] + $return['sys_first_reduce'] + $return['sys_full_reduce'], 2), 2);//实际要支付商品的总价格
        $hasReduceMoney = 0;
        if($return['vip_discount']<100 && $return['vip_discount']>0){
            if($return['sorts_discount_type']==0){ //折上折
                $hasReduceMoney = $return['price'] - round($return['price'] * ($return['vip_discount'] * 0.01),2);
                $return['discount_list']['vip_level'] = array('discount_type'=>6,'vip_level_discount'=>$return['vip_discount'] * 0.1,'vip_level_reduce_money'=>$hasReduceMoney);
            }else{ //折扣最优
                if($return['optimalDiscountMoney']>0){
                    $totalOptimalMoney = $return['optimalDiscountMoney'];
                    $return['vip_discount_money'] = $totalOptimalMoney;
                    $return['price'] = round($totalOptimalMoney - round($return['sto_first_reduce'] + $return['sto_full_reduce'] + $return['sys_first_reduce'] + $return['sys_full_reduce'], 2), 2);//实际要支付商品的总价格
                    $hasReduceMoney = $return['price'] - round($return['price'] * ($return['vip_discount'] * 0.01),2);
                    $return['discount_list']['vip_level'] = array('discount_type'=>6,'vip_level_discount'=>$return['vip_discount'] * 0.1,'vip_level_reduce_money'=>$hasReduceMoney);
                }
            }
        }
        $return['vipLevelReduceMoney'] =  $hasReduceMoney;
        $return['vip_discount'] =  $return['vip_discount'] * 0.1;
        $return['price']  = $return['price'] - $hasReduceMoney;
        $return['price'] += $return['store']['pack_alias'];//商品的实际支付总加打包费



        $address_id = isset($_GET['adress_id']) ? intval($_GET['adress_id']) : cookie('userLocationId');

        $userLocationLong = cookie('userLocationLong');
        $userLocationLat = cookie('userLocationLat');
        if (empty($userLocationLong) || empty($userLocationLat)) {
            $userLocationLat = $return['store']['lat'];
            $userLocationLong = $return['store']['long'];
        }

        $user_address_temp = D('User_adress')->field(true)->where(array('uid' => $this->user_session['uid']))->order('`default` DESC,`adress_id` DESC')->select();
        $return['address_count'] = count($user_address_temp);
        $user_adress = array();
        $distance_array = array();
        $user_address_list = array();
        foreach ($user_address_temp as $address) {
            $address['distance'] = getDistance($address['latitude'], $address['longitude'], $userLocationLat, $userLocationLong);
            if ($return['store']['delivery_range_type'] == 0) {
                $distance = getDistance($address['latitude'], $address['longitude'], $return['store']['lat'], $return['store']['long']);
                $distance = $distance / 1000;
                if ($return['store']['delivery_radius'] < $distance && $return['delivery_type'] != 5) {
                    continue;
                }
            } else {
                if ($return['store']['delivery_range_polygon']) {
                    if (!isPtInPoly($address['longitude'], $address['latitude'], $return['store']['delivery_range_polygon']) && $return['delivery_type'] != 5) {
                        continue;
                    }
                } else {
                    continue;
                }
            }

            if ($address_id == $address['adress_id']) {
                $user_adress = $address;
                break;
            }

            if(empty($address_id) && $address['default']){
                $user_adress = $address;
                break;
            }
            $distance_array[] = $address['distance'];
            $user_address_list[] = $address;
        }

        if (empty($user_adress) && $distance_array && $user_address_list) {
            array_multisort($distance_array, SORT_ASC, $user_address_list);
            $user_adress = $user_address_list[0];
        }
        if ($user_adress) {
            $province = D('Area')->get_area_by_areaId($user_adress['province'],false);
            $user_adress['province_txt'] = $province['area_name'];

            $city = D('Area')->get_area_by_areaId($user_adress['city'],false);
            $user_adress['city_txt'] = $city['area_name'];

            $area = D('Area')->get_area_by_areaId($user_adress['area'],false);
            $user_adress['area_txt'] = $area['area_name'];
        }

        $extraPrice = $return['store']['extra_price'];
        if ($return['store']['basic_price'] <= floatval($basic_price + $return['packing_charge']) || ($deliverExtraPrice == 1 && $extraPrice > 0)) {
            $this->assign('user_adress', $user_adress);
        } else {
            if (in_array($return['delivery_type'], array(2, 3, 4))) {
                if (!($deliverExtraPrice == 1 && $extraPrice > 0)) {
                    $return['delivery_type'] = 2;
                }
            } elseif (!($deliverExtraPrice == 1 && $extraPrice > 0)) {
                $this->error_tips('没有达到起送价，不能使用配送');
            }
        }
        if ($return['delivery_type'] == 2 || $return['delivery_type'] == 5 || $return['store']['basic_price'] <= floatval($basic_price + $return['packing_charge'])) {
            $extraPrice = 0;
        }
        //计算配送费
        if ($user_adress) {
            $distance = 0;
            if (C('config.is_riding_distance')) {
                import('@.ORG.longlat');
                $longlat_class = new longlat();
                $distance = $longlat_class->getRidingDistance($return['store']['lat'], $return['store']['long'], $user_adress['latitude'], $user_adress['longitude']);
            }
            $distance || $distance = getDistance($return['store']['lat'], $return['store']['long'], $user_adress['latitude'], $user_adress['longitude']);

            $distance = $distance / 1000;
            $pass_distance = $distance > $return['basic_distance'] ? floatval($distance - $return['basic_distance']) : 0;
            $return['delivery_fee'] += round($pass_distance * $return['per_km_price'], 2);
            $return['delivery_fee_old'] = $return['delivery_fee'];
            $return['delivery_fee'] = $return['delivery_fee'] - $return['delivery_fee_reduce'];
            $return['delivery_fee'] = $return['delivery_fee'] > 0 ? $return['delivery_fee'] : 0;


            $pass_distance = $distance > $return['basic_distance2'] ? floatval($distance - $return['basic_distance2']) : 0;
            $return['delivery_fee2'] += round($pass_distance * $return['per_km_price2'], 2);
            $return['delivery_fee2_old'] = $return['delivery_fee2'];
            $return['delivery_fee2'] = $return['delivery_fee2'] - $return['delivery_fee_reduce'];
            $return['delivery_fee2'] = $return['delivery_fee2'] > 0 ? $return['delivery_fee2'] : 0;


            $pass_distance = $distance > $return['basic_distance3'] ? floatval($distance - $return['basic_distance3']) : 0;
            $return['delivery_fee3'] += round($pass_distance * $return['per_km_price3'], 2);
            $return['delivery_fee3_old'] = $return['delivery_fee3'];
            $return['delivery_fee3'] = $return['delivery_fee3'] - $return['delivery_fee_reduce'];
            $return['delivery_fee3'] = $return['delivery_fee3'] > 0 ? $return['delivery_fee3'] : 0;

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
        }

        $date_list = D('Shop_goods')->getSelectTime($return);
        foreach ($date_list as $key => $rowset) {
            $temp = array();
            asort($rowset);
            foreach ($rowset as $rv) {
                $temp[] = $rv;
            }
            if ($key == date('Y-m-d')) {
                $d_list[] = array('ymd' => $key, 'show_date' => '今天', 'date_list' => $temp);
            } else if($key == date('Y-m-d',time()+86400)){
                $d_list[] = array('ymd' => $key, 'show_date' => '明天', 'date_list' => $temp);
            } else if($key == date('Y-m-d',time()+86400*2)){
                $d_list[] = array('ymd' => $key, 'show_date' => '后天', 'date_list' => $temp);
            } else {
                $d_list[] = array('ymd' => $key, 'show_date' => date('m月d日', strtotime($key)), 'date_list' => $temp);
            }
        }
        if (($return['delivery_type'] == 0 || $return['delivery_type'] == 1) && empty($d_list)) {
            $this->error_tips('抱歉,当前暂无可配送的预计送达时间');
        }
        $this->assign('dates', $d_list);

        $pick_addr_id = isset($_GET['pick_addr_id']) ? $_GET['pick_addr_id'] : '';
        $pick_list = D('Pick_address')->get_pick_addr_by_merid($return['mer_id'], true, $store_id);
        if (empty($pick_list)) {
            $lng_lat = D('User_long_lat')->getLocation($_SESSION['openid'], 0);
            $nowStore = $return['store'];
            $distance = getDistance($lng_lat['lat'], $lng_lat['long'], $nowStore['lat'], $nowStore['long']);
            $areaList = D('Area')->where(array('area_id' => array('in', array($nowStore['province_id'], $nowStore['city_id'], $nowStore['area_id']))))->getField('area_id,area_name');
            $pick_list[] = array(
                'name' => $nowStore['adress'] . ' ' . $nowStore['name'],
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
                'distance' => $distance
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
            $pick_address = $pick_list[0];
        }
        $pick_address['distance'] = $this->wapFriendRange($pick_address['distance']);
        $goodsList = $return['goods'];
//         echo '<pre/>';
//         print_r($goodsList);die;
        $tempList = array();
        foreach ($goodsList as $goods) {
            $index = isset($goods['packname']) && $goods['packname'] ? $goods['packname'] : 0;
            if (isset($tempList[$index])) {
                $tempList[$index]['list'][] = $goods;
            } else {
                $tempList[$index] = array(
                    'name' => $goods['packname'],
                    'list' => array($goods)
                );
            }
        }
        if (count($tempList) == 1) {
            $tempList[$index]['name'] = '';
        }
        $return['goods'] = $tempList;
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
            }elseif($key == 'vip_level'){
                unset($return['discount_list']['vip_level']);
                $return['discount_list']['vip_level'] = $value;
            }
        }
        foreach($return['discount_list'] as $key => $value){
            if($key == 'delivery'){
                unset($return['discount_list']['delivery']);
                $return['discount_list']['delivery'] = $value;
            }
        }
        $this->assign($return);
        $this->assign('pick_addr_id', $pick_addr_id);
        $this->assign('pick_address', $pick_address);

        $now_store_category_relation = M('Shop_category_relation')->where(array('store_id' => $return['store_id']))->find();
        $now_store_category = M((_view('Shop_category')))->where(array('cat_id' => $now_store_category_relation['cat_id']))->find();
        if ($now_store_category['cue_field']) {
            $this->assign('cue_field', unserialize($now_store_category['cue_field']));
        }
        $plat_discount = D('Merchant')->getDiscount($return['store']['mer_id']);
        $plat_discount = floatval($plat_discount);
        if ($plat_discount) {
            $plat_discount = floatval(round($plat_discount /10, 2));
        } else {
            $plat_discount = 10;
        }
        $this->assign('plat_discount', $plat_discount);
        $this->assign('extraPrice', $extraPrice);
        $this->assign('deliverExtraPrice', $deliverExtraPrice);
        $this->display();
    }


    public function save_order()
    {
        $this->isLogin();
        $now_merchant = M('Merchant')->where(array('mer_id'=>$_GET['mer_id']))->find();
        if($now_merchant['status']!=1){
            $this->error_tips(L_('该商家状态异常，无法支付'));
        }
        //delivery_type 0:平台配送，1：商家配送，2：自提，3:平台配送或自提，4：商家配送或自提
        $store_id = isset($_GET['store_id']) ? intval($_GET['store_id']) : 0;
        $cartid = isset($_POST['cartid']) ? htmlspecialchars(trim($_POST['cartid'])) : '';
        $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
        //-------------------------------
        if ($cartid && ($orderTemp = D('Shop_order_temp')->field(true)->where(array('cartid' => $cartid, 'store_id' => $store_id))->find())) {
            $list= json_decode($orderTemp['info'], true);
            $data = array();
            foreach ($list as $rowset) {
                if (isset($rowset['data']) && $rowset['data']) {
                    $data = array_merge($data, $rowset['data']);
                }
            }
            $return = D('Shop_goods')->checkCart($store_id, $this->user_session['uid'], $data);
        } elseif ($order_id && ($order = D('Shop_order')->get_order_detail(array('order_id' => $order_id, 'uid' => $this->user_session['uid'])))) {
            $return = D('Shop_goods')->checkCart($store_id, $this->user_session['uid'], $order['info'], 0);
        } else {
            $cookieData = $this->getCookieData('cart', $store_id);
            if(empty($cookieData)) {
                redirect(U('LocalShopMall/index'));
                exit;
            }
            $return = D('Shop_goods')->checkCart($store_id, $this->user_session['uid'], $cookieData);
        }
        //---------------------------------
        if ($return['error_code']) $this->error_tips($return['msg']);
        if (IS_POST) {
            $village_id = isset($_REQUEST['village_id']) ? intval($_REQUEST['village_id']) : 0;
            $phone = isset($_POST['ouserTel']) ? htmlspecialchars($_POST['ouserTel']) : '';
            $name = isset($_POST['ouserName']) ? htmlspecialchars($_POST['ouserName']) : '';
            $address = isset($_POST['ouserAddres']) ? htmlspecialchars($_POST['ouserAddres']) : '';
            $pick_address = isset($_POST['pick_address']) ? htmlspecialchars($_POST['pick_address']) : '';
            $invoice_head = isset($_POST['invoice_head']) ? htmlspecialchars($_POST['invoice_head']) : '';
            $address_id = isset($_POST['address_id']) ? intval($_POST['address_id']) : 0;
            $pick_id = isset($_POST['pick_id']) ? htmlspecialchars($_POST['pick_id']) : 0;
            $pickType = substr($pick_id, 0, 1);
            $pick_id = substr($pick_id, 1);
            $deliver_type = isset($_POST['deliver_type']) ? intval($_POST['deliver_type']) : 0;//0:配送，1：自提
            $arrive_time = isset($_POST['oarrivalTime']) ? htmlspecialchars($_POST['oarrivalTime']) : 0;
            $arrive_date = isset($_POST['oarrivalDate']) ? htmlspecialchars($_POST['oarrivalDate']) : 0;
            $note = isset($_POST['omark']) ? htmlspecialchars($_POST['omark']) : '';

            $extraPrice = 0;
            if ($deliver_type == 0 && floatval($return['price'] + $return['packing_charge']) < $return['store']['basic_price']) {
                $extraPrice = $return['store']['extra_price'];
                if (empty($extraPrice)) {
                    if (in_array($return['store']['deliver_type'], array(2, 3, 4))) {
                        $deliver_type = 1;
                    } else {
                        $this->error_tips('订单没有达到起送价，不予配送');
                    }
                }
            }
            if ($deliver_type != 1 && $deliver_type != 9) {//配送方式是：非自提和非快递配送
                if (empty($name)) $this->error_tips('联系人不能为空');
                if (empty($phone)) $this->error_tips('联系电话不能为空');
// 				if ($return['delivery_type'] == 1 || $return['delivery_type'] == 4) {
                if ($user_address = D('User_adress')->field(true)->where(array('adress_id' => $address_id, 'uid' => $this->user_session['uid']))->find()) {
                    if ($user_address['longitude'] > 0 && $user_address['latitude'] > 0) {
                        if ($return['store']['delivery_range_type'] == 0) {
                            $distance = getDistance($user_address['latitude'], $user_address['longitude'], $return['store']['lat'], $return['store']['long']);
                            $delivery_radius = $return['store']['delivery_radius'] * 1000;
                            if ($distance > $delivery_radius && $return['delivery_type'] != 5) {
                                $this->error_tips('您到本店的距离是' . $distance . '米,超过了' . $delivery_radius . '米的配送范围');
                            }
                        } else {
                            if ($return['store']['delivery_range_polygon']) {
                                if (!isPtInPoly($user_address['longitude'], $user_address['latitude'], $return['store']['delivery_range_polygon'])) {
                                    $this->error_tips('您的地址不在本店指定的配送区域');
                                }
                            } else {
                                $this->error_tips('您的地址不在本店指定的配送区域');
                            }
                        }
                    } else {
                        $this->error_tips('您选择的地址没有完善，请先编辑地址，点击“点击选择位置”进行完善', U('My/adress',array('buy_type' => 'shop', 'store_id' => $return['store_id'], 'village_id' => $village_id, 'mer_id' => $return['mer_id'], 'current_id'=>$user_adress['adress_id'])));
                    }
                } else {
                    $this->error_tips('地址信息不存在');
                }
// 				}
            }

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


            $now_time = time();
            $order_data = array();
            $order_data['mer_id'] = $return['mer_id'];
            $order_data['store_id'] = $return['store_id'];
            $order_data['uid'] = $this->user_session['uid'];

            $order_data['desc'] = $note;
            $order_data['create_time'] = $now_time;
            $order_data['last_time'] = $now_time;
            $order_data['invoice_head'] = $invoice_head;
            $order_data['village_id'] = $village_id;

            $order_data['num'] = $return['total'];
            $order_data['packing_charge'] = $return['packing_charge'];//打包费
            if($deliver_type == 9){
                $order_data['packing_charge'] = 0;
                $return['packing_charge']=  0;
            }
            $order_data['merchant_reduce'] = $return['sto_first_reduce'] + $return['sto_full_reduce'];//店铺优惠
            $order_data['merchant_reduce_total'] = $return['sto_first_reduce'] + $return['sto_full_reduce'];//所有店铺优惠
            $order_data['balance_reduce'] = $return['sys_first_reduce'] + $return['sys_full_reduce'];//平台优惠
            $orderid  = date('ymdhis').substr(microtime(),2,8-strlen($this->user_session['uid'])).$this->user_session['uid'];
            $order_data['real_orderid'] = $orderid;
            $order_data['no_bill_money'] = 0;//无需跟平台对账的金额

            if ($deliver_type == 1) {//自提
                $extraPrice = 0;
                if (empty($pick_id))$this->error_tips('请选择自提点');
                $order_data['is_pick_in_store'] = 2;//配送方式 0：平台配送，1：商家配送，2：自提，3:快递配送
                $delivery_fee = $order_data['freight_charge'] = 0;//运费
                $order_data['username'] = isset($this->user_session['nickname']) && $this->user_session['nickname'] ? $this->user_session['nickname'] : '';
                $order_data['userphone'] = isset($this->user_session['phone']) && $this->user_session['phone'] ? $this->user_session['phone'] : '';
                $order_data['address'] = $pick_address;
                $order_data['address_id'] = 0;
                if ($pickType == 's') {
                    $order_data['pick_id'] = 0;
                    $order_data['status'] = 9;
                } else {
                    $order_data['pick_id'] = $pick_id;
                    $order_data['status'] = 7;
                }
                $order_data['expect_use_time'] = time() + $return['store']['work_time'] * $diffTime;//客户期望使用时间
            } elseif ($deliver_type == 9) {//扫码
                $extraPrice = 0;
                $order_data['is_pick_in_store'] = 2;//配送方式 0：平台配送，1：商家配送，2：自提，3:快递配送
                $delivery_fee = $order_data['freight_charge'] = 0;//运费
                $order_data['username'] = isset($this->user_session['nickname']) && $this->user_session['nickname'] ? $this->user_session['nickname'] : '';
                $order_data['userphone'] = isset($this->user_session['phone']) && $this->user_session['phone'] ? $this->user_session['phone'] : '';
                $order_data['address'] = '';
                $order_data['address_id'] = 0;
                $order_data['pick_id'] = 0;
                $order_data['order_from'] = 9;
                $order_data['expect_use_time'] = time() + $return['store']['work_time'] * $diffTime;//客户期望使用时间
            } else {//配送
                $order_data['username'] = $name;
                $order_data['userphone'] = $phone;
                $order_data['address'] = $address;
                $order_data['address_id'] = $address_id;
                $order_data['lat'] = $user_address['latitude'];
                $order_data['lng'] = $user_address['longitude'];
                if ($arrive_date == 0) {
                    $arrive_date = date('Y-m-d');
                }

                $start_time = $return['store']['delivertime_start'];
                $stop_time = $return['store']['delivertime_stop'];

                $start_time2 = $return['store']['delivertime_start2'];
                $stop_time2 = $return['store']['delivertime_stop2'];

                $start_time3 = $return['store']['delivertime_start3'];
                $stop_time3 = $return['store']['delivertime_stop3'];

                if ($start_time == $stop_time && $start_time == '00:00:00') {
                    $stop_time = '23:59:59';
                }

                $arrive_time = strtotime($arrive_date . $arrive_time);
                if ($arrive_time == 0) {
                    if($return['delivery_type'] != 5) $this->error_tips('请选择配送时间');
                }

                $order_data['expect_use_time'] = $arrive_time ? $arrive_time : time() + $return['store']['send_time'] + $return['store']['work_time'] * $diffTime;//客户期望使用时间

                //计算配送费
                $distance = 0;
                if (C('config.is_riding_distance')) {
                    import('@.ORG.longlat');
                    $longlat_class = new longlat();
                    $distance = $longlat_class->getRidingDistance($return['store']['lat'], $return['store']['long'], $user_address['latitude'], $user_address['longitude']);
                }
                $distance || $distance = getDistance($return['store']['lat'], $return['store']['long'], $user_address['latitude'], $user_address['longitude']);
                $order_data['distance'] = $distance;
// 				$distance = getDistance($user_address['latitude'], $user_address['longitude'], $return['store']['lat'], $return['store']['long']);
                $distance = $distance / 1000;
                if ($return['delivery_type'] == 5) {//快递配送
                    $pass_distance = $distance > $return['basic_distance'] ? floatval($distance - $return['basic_distance']) : 0;
                    $return['delivery_fee'] += round($pass_distance * $return['per_km_price'], 2);
                    $return['delivery_fee'] = $return['delivery_fee'] - $return['delivery_fee_reduce'];
                    $return['delivery_fee'] = $return['delivery_fee'] > 0 ? $return['delivery_fee'] : 0;
                    $delivery_fee = $order_data['freight_charge'] = $return['delivery_fee'];//运费
                } else {
                    $expect_use_time_temp = strtotime(date('Y-m-d ') . date('H:i:s', $order_data['expect_use_time']));
                    if (D('Deliver_set')->getDeliverTime($expect_use_time_temp, $start_time, $stop_time, 1)) {
                        //时间段一
                        $pass_distance = $distance > $return['basic_distance'] ? floatval($distance - $return['basic_distance']) : 0;
                        $return['delivery_fee'] += round($pass_distance * $return['per_km_price'], 2);
                        $return['delivery_fee'] = $return['delivery_fee'] - $return['delivery_fee_reduce'];
                        $return['delivery_fee'] = $return['delivery_fee'] > 0 ? $return['delivery_fee'] : 0;
                        $delivery_fee = $order_data['freight_charge'] = $return['delivery_fee'];//运费
                    } elseif (D('Deliver_set')->getDeliverTime($expect_use_time_temp, $start_time2, $stop_time2, 2)) {
                        //时间段二
                        $pass_distance = $distance > $return['basic_distance2'] ? floatval($distance - $return['basic_distance2']) : 0;
                        $return['delivery_fee2'] += round($pass_distance * $return['per_km_price2'], 2);
                        $return['delivery_fee2'] = $return['delivery_fee2'] - $return['delivery_fee_reduce'];
                        $return['delivery_fee2'] = $return['delivery_fee2'] > 0 ? $return['delivery_fee2'] : 0;
                        $delivery_fee = $order_data['freight_charge'] = $return['delivery_fee2'];//运费
                    } elseif (D('Deliver_set')->getDeliverTime($expect_use_time_temp, $start_time3, $stop_time3, 3)) {
                        //时间段三
                        $pass_distance = $distance > $return['basic_distance3'] ? floatval($distance - $return['basic_distance3']) : 0;
                        $return['delivery_fee3'] += round($pass_distance * $return['per_km_price3'], 2);
                        $return['delivery_fee3'] = $return['delivery_fee3'] - $return['delivery_fee_reduce'];
                        $return['delivery_fee3'] = $return['delivery_fee3'] > 0 ? $return['delivery_fee3'] : 0;
                        $delivery_fee = $order_data['freight_charge'] = $return['delivery_fee3'];//运费
                    }else {
                        $this->error_tips('您选择的时间不在配送时间段内！');
                    }
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

                if ($return['delivery_type'] == 0 || $return['delivery_type'] == 3) {//平台配送
                    $order_data['is_pick_in_store'] = 0;//配送方式 0：平台配送，1：商家配送，2：自提，3:快递配送
                    $order_data['no_bill_money'] = $delivery_fee + $extraPrice + $return['merchant_reduce_deliver_money'];
                    $order_data['merchant_reduce_total'] = $return['sto_first_reduce'] + $return['sto_full_reduce']+ $return['merchant_reduce_deliver_money'];//所有店铺优惠
                } elseif ($return['delivery_type'] == 1 || $return['delivery_type'] == 4)  {
                    $order_data['is_pick_in_store'] = 1;//配送方式 0：平台配送，1：商家配送，2：自提，3:快递配送
                } else {
                    $order_data['is_pick_in_store'] = 3;//配送方式 0：平台配送，1：商家配送，2：自提，3:快递配送
                    $extraPrice = 0;
                }
            }
            $hasReduceMoney = 0;
            if($return['vip_discount']<100 && $return['vip_discount']>0){
                if($return['sorts_discount_type']==0){ //折上折
                    $tempMoney = round($return['vip_discount_money'] - round(($order_data['merchant_reduce'] + $order_data['balance_reduce']), 2), 2);
                    $hasReduceMoney = $tempMoney - round($tempMoney * ($return['vip_discount'] * 0.01),2);
                    foreach($return['goods'] as &$goods){
                        $rate = sprintf('%.2f',$goods['price'] /$return['price']);
                        $rate = $rate ? $rate : 1;
                        $goods['discount_price'] = floatval(round($goods['discount_price'] - ($hasReduceMoney * $rate), 2));
                    }
                }else{ //折扣最优
                    if($return['optimalDiscountMoney']>0){
                        $totalOptimalMoney = $return['optimalDiscountMoney'];
                        $return['vip_discount_money'] = $totalOptimalMoney;
                        $tempMoney = round($totalOptimalMoney - round(($order_data['merchant_reduce'] + $order_data['balance_reduce']), 2), 2);
                        $hasReduceMoney =  $tempMoney - round( $tempMoney * ($return['vip_discount'] * 0.01),2);
                        foreach($return['goods'] as &$goods){
                            $rate = sprintf('%.2f',$goods['price'] /$return['price']);
                            $rate = $rate ? $rate : 1;
                            $goods['discount_price'] = floatval(round($goods['price'] - ($hasReduceMoney * $rate), 2));
                        }
                    }
                }
            }
            $return['vip_discount_money']  = round($return['vip_discount_money'] - $hasReduceMoney,2);

            $return['discount_price']  = $return['vip_discount_money'];
            $return['can_discount_money'] = round($return['can_discount_money'] - $hasReduceMoney,2);
            $order_data['cartid'] = $cartid;//拼单号
            $order_data['other_money'] = $extraPrice;//加价送的金额
            $order_data['goods_price'] = $return['price'];//商品的价格
            $order_data['extra_price'] = $return['extra_price'];//另外要支付的金额
            $order_data['can_discount_money'] = $return['can_discount_money'] + $delivery_fee + $return['packing_charge'] + $extraPrice;//可用商家优惠券的总价
            $order_data['discount_price'] = $return['vip_discount_money'];//商品折扣后的总价

            $order_data['vip_level_reduce_money'] = $hasReduceMoney;//会员等级折扣优惠金额
            $order_data['vip_level_discount'] = $return['vip_discount'];//会员等级折扣

            $order_data['total_price'] = $return['price'] + $delivery_fee + $return['packing_charge'] + $extraPrice;//订单总价  商品价格+打包费+配送费
            $order_data['price'] = $order_data['discount_price'] + $delivery_fee + $return['packing_charge'] - $order_data['merchant_reduce'] - $order_data['balance_reduce'] + $extraPrice;//实际要支付的价格

            if($order_data['price'] < 0){
                $order_data['price'] = 0;
            }

            $order_data['discount_detail'] = $return['discount_list'] ? serialize($return['discount_list']) : '';//优惠详情

            $order_data['reduce_stock_type'] = $return['store']['reduce_stock_type'];//'减库存类型（0：支付后，1：下单后）'
            $order_data['vip_discount_money'] = $return['vip_discount_money']?$return['vip_discount_money']:0;

            //自定义字段
            if($_POST['cue_field']){
                $order_data['cue_field'] = serialize($_POST['cue_field']);
            }
            if ($order_id = D('Shop_order')->saveOrder($order_data, $return, $this->user_session)) {
                if ($order_data['status'] == 9) {
                    D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 13, 'name' => $return['store']['name'], 'phone' => $return['store']['phone']));//接货
                }
                /* 粉丝行为分析 */
                $this->behavior(array('mer_id' => $return['mer_id'], 'biz_id' => $order_id));
                redirect(U('Pay/check', array('order_id' => $order_id, 'type' => 'shop')));
            } else {
                $this->error_tips('订单保存失败');
            }
        } else {
            redirect(U('confirm_order',$_GET));
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


    private function check_cart($address_id,$onlybuy='cart')
    {
        $this->isLogin();
        $store_id = isset($_REQUEST['store_id']) ? intval($_REQUEST['store_id']) : 0;
        if($onlybuy=='onlybuy'){
            $productCart = $this->getCookieData($onlybuy, $store_id);
        }else{
            $productCart = $this->getCookieData('cart', $store_id);//json_decode(cookie('buy_mall_goods_cart'), true);
        }
        return D('Shop_goods')->checkCart($store_id, $this->user_session['uid'], $productCart, 1, $address_id);
    }

    private function getCookieData($onlybuy, $store_id)
    {
        $productCart = array();
        for ($i = 0; $i < 40; $i++) {
            if($onlybuy=='onlybuy'){
                $tmpCookie = cookie('shop_only_buy_cart_' . $store_id . '_' . $i);
            }else{
                $tmpCookie = cookie('shop_buy_mall_goods_cart_' . $store_id . '_' . $i);
            }
            if (!empty($tmpCookie)) {
                $tmpArr = json_decode($tmpCookie, true);
                if (empty($tmpArr)) {
                    $tmpArr = array();
                }
                $productCart = array_merge($productCart, $tmpArr);
            } else {
                break;
            }
        }
        return $productCart;
    }

    //获取时段起送价
    public function ajax_basic_price()
    {
        $where = array('store_id' => $_GET['store_id']);
        $now_store = D('Merchant_store')->field(true)->where($where)->find();
        $now_shop = D('Merchant_store_shop')->field(true)->where($where)->find();
        $row = array_merge($now_store, $now_shop);
        $deliverReturn = D('Deliver_set')->getDeliverInfo($row);

        $start_time = $deliverReturn['delivertime_start'];
        $stop_time = $deliverReturn['delivertime_stop'];

        $start_time2 = $deliverReturn['delivertime_start2'];
        $stop_time2 = $deliverReturn['delivertime_stop2'];

        $start_time3 = $deliverReturn['delivertime_start3'];
        $stop_time3 = $deliverReturn['delivertime_stop3'];


        $time = time();
        $selectDeliverTime = 0;
        if (!($start_time == $stop_time && $start_time == '00:00:00')) {
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
            if (!($start_time2 == $stop_time2 && $start_time2 == '00:00:00')) {
                $start_time2 = strtotime(date('Y-m-d') . ' ' . $start_time2);
                $stop_time2 = strtotime(date('Y-m-d') . ' ' . $stop_time2);
                if ($start_time2 > $stop_time2) {
                    if (strtotime(date('Y-m-d')) <= $time && $time <= $stop_time2) {
                        $selectDeliverTime = 2;
                    } else {
                        $stop_time2 += 86400;
                        if ($start_time2 <= $time && $time <= $stop_time2) {
                            $selectDeliverTime = 2;
                        }
                    }
                } elseif ($start_time2 <= $time && $time <= $stop_time2) {
                    $selectDeliverTime = 2;
                }
            }
            if (!($start_time3 == $stop_time3 && $start_time3 == '00:00:00')) {
                $start_time3 = strtotime(date('Y-m-d') . ' ' . $start_time3);
                $stop_time3 = strtotime(date('Y-m-d') . ' ' . $stop_time3);

                if ($start_time3 > $stop_time3) {
                    if (strtotime(date('Y-m-d')) <= $time && $time <= $stop_time3) {
                        $selectDeliverTime = 3;
                    } else {
                        $stop_time3 += 86400;
                        if ($start_time3 <= $time && $time <= $stop_time3) {
                            $selectDeliverTime = 3;
                        }
                    }
                } elseif ($start_time3 <= $time && $time <= $stop_time3) {
                    $selectDeliverTime = 3;
                }
            }
        }else{
            $selectDeliverTime = 1;
        }
        $store = [];
        $set = D('Deliver_set')->field(true)->where(array('area_id' => $row['area_id'], 'status' => 1))->find();
        if (empty($set)) {
            $set = D('Deliver_set')->field(true)->where(array('area_id' => $row['city_id'], 'status' => 1))->find();
            if (empty($set)) {
                $set = D('Deliver_set')->field(true)->where(array('area_id' => $row['province_id'], 'status' => 1))->find();
            }
        }
        if ($selectDeliverTime == 1) {
            if ($row['deliver_type'] == 0 || $row['deliver_type'] == 3) {
                if($row['s_is_open_own']==1){
                    $store['basic_price'] = floatval($row['s_basic_price1']) > -1 ? floatval($row['s_basic_price1']) : (floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) : (floatval($set['basic_price1']) ? floatval($set['basic_price1']):(floatval($this->config['basic_price1']) ? floatval($this->config['basic_price1']) : floatval($this->config['basic_price']))));//起送价
                }else{
                    $store['basic_price'] = floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) : (floatval($set['basic_price1']) ? floatval($set['basic_price1']):(floatval($this->config['basic_price1']) ? floatval($this->config['basic_price1']) : floatval($this->config['basic_price'])));//起送价
                }
                $store['extra_price'] = floatval($row['s_extra_price']) ? floatval($row['s_extra_price']) : floatval($this->config['extra_price']);
            } else {
                $store['basic_price'] = floatval($row['basic_price1']) ? floatval($row['basic_price1']) : floatval($row['basic_price']);//起送价
                $store['extra_price'] = floatval($row['extra_price']);//不满起送价另付金额支持配送
            }
        } elseif ($selectDeliverTime == 2) {
            if ($row['deliver_type'] == 0 || $row['deliver_type'] == 3) {
                if($row['s_is_open_own']==1) {
                    $store['basic_price'] = floatval($row['s_basic_price2']) > -1 ? floatval($row['s_basic_price2']) : (floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) : (floatval($set['basic_price2']) ? floatval($set['basic_price2']) : (floatval($this->config['basic_price2']) ? floatval($this->config['basic_price2']) : floatval($this->config['basic_price']))));//起送价
                }else{
                    $store['basic_price'] = floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) : (floatval($set['basic_price2']) ? floatval($set['basic_price2']) : (floatval($this->config['basic_price2']) ? floatval($this->config['basic_price2']) : floatval($this->config['basic_price'])));//起送价
                }
                $store['extra_price'] = floatval($row['s_extra_price']) ? floatval($row['s_extra_price']) : floatval($this->config['extra_price']);
            } else {
                $store['basic_price'] = floatval($row['basic_price2']) ? floatval($row['basic_price2']) : floatval($row['basic_price']);//起送价
                $store['extra_price'] = floatval($row['extra_price']);//不满起送价另付金额支持配送
            }

        } elseif ($selectDeliverTime == 3) {
            if ($row['deliver_type'] == 0 || $row['deliver_type'] == 3) {
                if($row['s_is_open_own']==1) {
                    $store['basic_price'] = floatval($row['s_basic_price3']) > -1 ? floatval($row['s_basic_price3']) : (floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) : (floatval($set['basic_price3']) ? floatval($set['basic_price3']) : (floatval($this->config['basic_price3']) ? floatval($this->config['basic_price3']) : floatval($this->config['basic_price']))));//起送价
                }else{
                    $store['basic_price'] = floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) : (floatval($set['basic_price3']) ? floatval($set['basic_price3']) : (floatval($this->config['basic_price3']) ? floatval($this->config['basic_price3']) : floatval($this->config['basic_price'])));//起送价
                }
                $store['extra_price'] = floatval($row['s_extra_price']) ? floatval($row['s_extra_price']) : floatval($this->config['extra_price']);
            } else {
                $store['basic_price'] = floatval($row['basic_price3']) ? floatval($row['basic_price3']) : floatval($row['basic_price']);//起送价
                $store['extra_price'] = floatval($row['extra_price']);//不满起送价另付金额支持配送
            }
        }else{
            if ($row['deliver_type'] == 0 || $row['deliver_type'] == 3) {
                $store['basic_price'] = floatval($row['s_basic_price']) > -1 ? floatval($row['s_basic_price']) : floatval($this->config['basic_price']);//起送价
                $store['extra_price'] = floatval($row['s_extra_price']) ? floatval($row['s_extra_price']) : floatval($this->config['extra_price']);
            } else {
                $store['basic_price'] = floatval($row['basic_price']);//起送价
                $store['extra_price'] = floatval($row['extra_price']);//不满起送价另付金额支持配送
            }
        }
        echo json_encode($store);
    }

    private function isLogin()
    {
        if (empty($this->user_session)) {
            if($this->is_app_browser){
                $this->error_tips('请先进行登录！',U('Login/index'));
            }else{
                redirect(U('Login/index',array('referer'=>urlencode($_SERVER["REQUEST_URI"]))));
            }
        }
    }

    private function get_reduce($discounts, $type, $price, $store_id = 0)
    {
        $reduce_money = 0;
        $return = null;
        if (isset($discounts[$store_id])) {
            foreach ($discounts[$store_id] as $row) {
                if ($row['type'] == $type) {
                    if ($price >= $row['full_money']) {
                        if ($reduce_money < $row['reduce_money']) {
                            $reduce_money = $row['reduce_money'];
                            $return = $row;
                        }
                    }
                }
            }
        }
        return $return;
    }

    private function clear_cookie($store_id)
    {
        $goodsCart = array();
        $type = cookie('shop_mall_buy_type');
        if($type=='add'){
            $buy_mall_goods_cart = $this->getCookieData('cart', $store_id);//json_decode(cookie('buy_mall_goods_cart'), true);
            foreach ($buy_mall_goods_cart as $row) {
                $goodsCartKey = 's_' . $row['store_id'] . '_g_' . $row['productId'];
                if ($row['productParam']) {
                    foreach ($row['productParam'] as $param) {
                        if ($param['type'] == 'spec') {
                            $goodsCartKey .= '_s_' . $param['id'];
                        } else {
                            if ($param['data']) {
                                foreach ($param['data'] as $da) {
                                    $goodsCartKey .= '_v_' . $da['id'];
                                }
                            }
                        }
                    }
                }
                $goodsCart[$goodsCartKey] = $row;
            }
            for ($i = 0; $i < 40; $i++) {
                $tmpCookie = cookie('shop_buy_mall_goods_cart_' . $store_id. '_' . $i);
                if (!empty($tmpCookie)) {
                    cookie('shop_buy_mall_goods_cart_'  . $store_id. '_' . $i, null);
                } else {
                    break;
                }
            }
            cookie('shop_buy_mall_goods_cart', null);
            if ($goodsCart) {
                $cart_goods = array();
                $productCart_t = array();
                for ($i = 0; $i < 40; $i++) {
                    $tmpCookie = cookie('shop_mall_goods_cart_' . $store_id. '_'  . $i);
                    if (!empty($tmpCookie)) {
                        $tmpArr = json_decode($tmpCookie, true);
                        if (empty($tmpArr)) {
                            $tmpArr = array();
                        }
                        $productCart_t = array_merge($productCart_t, $tmpArr);
                    } else {
                        break;
                    }
                }
                //$productCart_t = json_decode(cookie('mall_goods_cart'), true);
                foreach ($productCart_t as $row) {
                    $this_index = 's_' . $row['store_id'] . '_g_' . $row['productId'];
                    if ($row['productParam']) {
                        foreach ($row['productParam'] as $param) {
                            if ($param['type'] == 'spec') {
                                $this_index .= '_s_' . $param['id'];
                            } else {
                                if ($param['data']) {
                                    foreach ($param['data'] as $da) {
                                        $this_index .= '_v_' . $da['id'];
                                    }
                                }
                            }
                        }
                    }
                    if (!isset($goodsCart[$this_index])) {
                        $cart_goods[] = $row;
                    }
                }
                if ($cart_goods) {
                    $cart_goods = array_chunk($cart_goods, 5);
                    $t = 0;
                    foreach ($cart_goods as $i => $cart) {
                        cookie('shop_mall_goods_cart_'  . $store_id. '_' . $i, json_encode($cart));
                        $t = $i;
                    }
                    $t ++;
                    for ($i = $t; $i < 40; $i++) {
                        cookie('shop_mall_goods_cart_'  . $store_id. '_' . $i, null);
                    }
                } else {
                    for ($i = 0; $i < 40; $i++) {
                        cookie('shop_mall_goods_cart_' . $store_id. '_'  . $i, null);
                    }
                }
            }
            cookie('shop_mall_buy_cart',null);
        }else{
            cookie('shop_mall_buy_cart',null);
        }

    }

    public function order_detail()
    {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        if ($order = D('Shop_order')->get_order_detail(array('order_id' => $order_id, 'uid' => $this->user_session['uid']))) {
            $storeName = D("Merchant_store")->field('`name`, `phone`')->where(array('store_id' => $order['store_id']))->find();
            $this->assign('storeName', $storeName);
            cookie('shop_cart_' . $order['store_id'], null);
            $this->clear_cookie($order['store_id']);

            $status = D('Shop_order_log')->field(true)->where(array('order_id' => $order_id))->order('id DESC')->select();
            $statusCount = D("Shop_order_log")->where(array('order_id' => $order_id))->count();
            $this->assign('statusCount', $statusCount);
            $this->assign('status', $status);
            $this->assign('order_id', $order_id);
            $this->assign('order', $order);
            $this->display('status_new');
        } else {
            $this->error_tips('错误的订单信息！');
        }
    }



    /**
     * 订单详情
     */
    public function status()
    {
        $order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
        $order = D("Shop_order")->get_order_detail(array('order_id' => $order_id, 'uid' => $this->user_session['uid']));
        if ($order) {
            $store = D('Merchant_store')->field(true)->where(array('store_id' => $order['store_id']))->find();
            $store_image_class = new store_image();
            $images = $store_image_class->get_allImage_by_path($store['pic_info']);
            $store['image'] = isset($images[0]) ? $images[0] : '';

            cookie('shop_cart_' . $order['store_id'], null);
            $this->clear_cookie($order['store_id']);

            if($order['pay_type'] == 'offline' && empty($order['third_id'])){
                $payment = rtrim(rtrim(number_format($order['price']-$order['card_price']-$order['merchant_balance']-$order['card_give_money']-$order['balance_pay']-$order['payment_money']-$order['score_deducte']-floatval($order['coupon_price']),2,'.',''),'0'),'.');
            }
            $discount_price = floatval(round($order['discount_price'] + $order['freight_charge'] + $order['packing_charge'], 2));
            $order['card_discount'] = $order['card_discount'] == 0 ? 10 : $order['card_discount'];
            $arr['order_details'] = array(
                'orderid' => $order['orderid'],
                'order_id' => $order['order_id'],
                'real_orderid' => $order['real_orderid'],
                'username' => $order['username'],
                'userphone' => $order['userphone'],
                'create_time' => date('Y-m-d H:i:s',$order['create_time']),
                'pay_time' => date('Y-m-d H:i:s',$order['pay_time']),
                'expect_use_time' => $order['expect_use_time'] != 0 ? date('Y-m-d H:i',$order['expect_use_time']) : '尽快',
                'is_pick_in_store' => $order['is_pick_in_store'],//配送方式 0：平台配送，1：商家配送，2：自提，3:快递配送
                'address' => $order['address'],
                'deliver_str' => $order['deliver_str'],
                'deliver_status_str' => $order['deliver_status_str'],
                'note' => isset($order['desc']) ? $order['desc'] : '',
                'invoice_head' => $order['invoice_head'],//发票抬头
                'pay_status' => $order['pay_status_print'],
                'pay_type_str' => $order['pay_type_str'],
                'status_str' => $order['status_str'],
                'score_used_count' => $order['score_used_count'],//抵用的积分
                'score_deducte' => strval(floatval($order['score_deducte'])),//积分兑现的金额
                'card_give_money' => strval(floatval($order['card_give_money'])),//会员卡赠送余额
                'merchant_balance' => strval(floatval($order['merchant_balance'])),//商家余额
                'balance_pay' => strval(floatval($order['balance_pay'])),//平台余额
                'payment_money' => strval(floatval($order['payment_money'])),//在线支付的金额
                'change_price' => strval(floatval($order['change_price'])),//店员修改前的原始价格（如果是0表示没有修改过，可不显示）
                'change_price_reason' => $order['change_price_reason'],//店员修改价格的理由
                'card_id' => $order['card_id'],
                'card_price' => strval(floatval($order['card_price'])),//商家优惠券的金额
                'coupon_price' => strval(floatval($order['coupon_price'])),//平台优惠券的金额
                'payment' => isset($payment) ? $payment : 0,
                'use_time' => $order['use_time'] != 0 ? date('Y-m-d H:i:s',$order['use_time']) : '0',
                'last_staff' => $order['last_staff'],
                'status' => $order['status'],
                'paid' => $order['paid'],
                'register_phone' => $order['register_phone'],//注册时的用户手机号
                'lat' => $order['lat'],
                'lng' => $order['lng'],
                'cue_field' => $order['cue_field'],//商家自定义字段值（如果没有的话是空 即：''）
                'card_discount' => $order['card_discount'],//会员卡折扣
                'goods_price' => strval(floatval($order['goods_price'])),//商品的总价
                'freight_charge' => strval(floatval($order['freight_charge'])),//配送费
                'packing_charge' => strval(floatval($order['packing_charge'])),//打包费
                'total_price' => strval(floatval($order['total_price'])),//订单总价
                'merchant_reduce' => strval(floatval($order['merchant_reduce'])),//商家优惠的金额
                'balance_reduce' => strval(floatval($order['balance_reduce'])),//平台优惠的金额
                'price' => strval(floatval($order['price'])),//实际支付金额
                'distance' => round(getDistance($order['lat'], $order['lng'], $this->store['lat'], $this->store['long'])/1000, 2),//距离
                'discount_price' => strval($discount_price),//折扣后的总价  = floatval(round($order['discount_price'] + $order['freight_charge'] + $order['packing_charge'], 2));
                'minus_price' => strval(floatval(round($order['merchant_reduce'] + $order['balance_reduce'], 2))),//平台和商家的优惠金额
                'go_pay_price' => strval(floatval(round($discount_price - $order['merchant_reduce'] - $order['balance_reduce'], 2))),//应付的金额
                'minus_card_discount' => strval(floatval(round(($discount_price - $order['merchant_reduce'] - $order['balance_reduce'] - $order['freight_charge']) * (1 - $order['card_discount'] * 0.1), 2))),//折扣与优惠的优惠金额
                'order_from_txt' => $this->order_froms[$order['order_from']],
                'deliver_log_list' => D('Shop_order_log')->where(array('order_id' => $order['order_id']))->order('id DESC')->find(),
                'deliver_info' => unserialize($order['deliver_info']),
                'express_name' => isset($order['express_name']) ? $order['express_name'] : '',
                'express_code' => isset($order['express_code']) ? $order['express_code'] : '',
                'express_number' => isset($order['express_number']) ? $order['express_number'] : '',
            );
            foreach($order['info'] as $v) {
                $discount_price = floatval($v['discount_price']) > 0 ? floatval($v['discount_price']) : floatval($v['price']);
                $arr['info'][] = array(
                    'name' => $v['name'],
                    'discount_type' => $v['discount_type'],
                    'price' => strval(floatval($v['price'])),
                    'discount_price' => strval($discount_price),
                    'spec' => empty($v['spec']) ? '' : $v['spec'],
                    'num' => $v['num'],
                    'total' => strval(floatval($v['price'] * $v['num'])),
                    'discount_total' => strval(floatval($discount_price * $v['num'])),
                );
            }
            $arr['discount_detail'] = $order['discount_detail'] ?: '';
//             echo '<pre/>';
//             print_r($arr);die;
            $this->assign($arr);
            $shop = D('Merchant_store_shop')->field(true)->where(array('store_id' => $order['store_id']))->find();
            $this->assign('store', array_merge($store, $shop));
            $this->display('order_detail_new');
        } else {
            $this->error_tips('订单信息错误！');
        }
    }


    public function category()
    {
        $category_list = D('Goods_category')->get_list();
        $this->assign('category_list', $category_list);
        $this->display();
    }


}