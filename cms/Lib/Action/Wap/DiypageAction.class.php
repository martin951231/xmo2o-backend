<?php
class DiypageAction extends BaseAction{
    protected $send_time_type = array('分钟', '小时', '天', '周', '月');
    public function page(){
    	$condition_page['page_id'] = $_GET['page_id'];
		$condition_page['is_remove'] = 0;
		$now_page = D('Merchant_store_diypage')->where($condition_page)->find();
		if(empty($now_page)){
			$this->error_tips('该页面不存在');
		}
		
		D('Merchant_store_diypage')->where($condition_page)->setInc('hits');
		
		$now_store = D('Merchant_store')->get_store_by_storeId($now_page['store_id']);

		if($_GET['page_from'] == 'store_qr_code'){
			$_SESSION['session_store_id'] = $now_store['store_id'];
		}
		
		//商家小程序进入
		if($_GET['wxapp_own_mer_id']){
			$_SESSION['wxapp_own_mer_id'] = $now_store['mer_id'];
		}

		if ($now_page['page_goods_source']==1) {
            $now_store['url'] = $this->config['site_url'] . '/wap.php?g=Wap&c=Shop&a=index#shop-'.$now_store['store_id'];
        } else {
            $now_store['url'] = U('Mall/store',array('store_id'=>$now_store['store_id']),true,false,true);
        }
		$now_store['card_url'] = U('My_card/merchant_card',array('mer_id'=>$now_store['mer_id']),true,false,true);
		// dump($now_store);
		C('now_store',$now_store);
		
        $uid = $this->user_session['uid'];
		
		if($uid){
            $this->save_user_city($now_store['city_id'], $now_store['area_id']);
        }
		
		$field_list = D('Merchant_store_diypage_field')->getParseFields($now_page['store_id'],$now_page['page_id'], 0, 0, 1, $now_page['page_goods_source'], $uid);
		
		$this->assign('is_mobile',$this->is_mobile());
		
		$this->assign('now_page',$now_page);
		$this->assign('now_store',$now_store);
		$this->assign('field_list',$field_list);

        $merchant_store_info = D('Merchant_store')->where(array('store_id' => $now_page['store_id']))->find();
        $merchant_store_shop_info = D('Merchant_store_shop')->where(array('store_id' => $now_page['store_id']))->find();
        $row = array_merge($merchant_store_info, $merchant_store_shop_info);
        $is_close = 1;
        if (D('Merchant_store_shop')->checkTime($row)) {
            $is_close = 0;
        }
        $this->assign('is_close',$is_close);
        // 经纬度
		if($_GET['lat']&&$_GET['long']){
            $long_lat['lat'] = $_GET['lat'];
            $long_lat['long'] = $_GET['long'];
        }else{
            $long_lat = D('User_long_lat')->getLocation($_SESSION['openid']);
        }
        if(!empty($long_lat)){
            import('@.ORG.longlat');
            $longlat_class = new longlat();
            $long_lat = $longlat_class->gpsToBaidu($long_lat['lat'], $long_lat['long']);
            $long_lat['long'] = $long_lat['lng'];
            $this->assign('long_lat',$long_lat);
        }

		$this->display();
    }
	/**
	 * 判断是否手机访问
	 */
	public function is_mobile(){
		if(preg_match('/(iphone|ipad|ipod|android)/i', strtolower($_SERVER['HTTP_USER_AGENT']))){
			return true;
		}else{
			return false;
		}
	}
	/**
     * 店铺收藏
	 */
	public function store_collection(){
        $uid = $this->user_session['uid'];
        if(empty($uid)){
            $this->error_tips('您还没有登陆，请先登录',U('Login/index'));
            redirect(U('Login/index'));
        }
        $info = D('Merchant_user_relation')->where(array('store_id'=>intval($_POST['store_id']),'uid'=>$uid,'type'=>'foodshop'))->find();
        if($info){
            if(D('Merchant_user_relation')->where(array('store_id'=>intval($_POST['store_id']),'uid'=>$uid,'type'=>'foodshop'))->delete()){
                exit(json_encode(array('error'=>3,'msg'=>'取消收藏成功')));
            }
        }
        $res = D('Merchant_user_relation')->data(array('store_id'=>$_POST['store_id'],'uid'=>$this->user_session['uid'],'type'=>'foodshop'))->add();
        if($res){
            exit(json_encode(array('error'=>1,'msg'=>'收藏成功')));
        }else{
            exit(json_encode(array('error'=>2,'msg'=>'收藏失败请重试')));
        }
    }


    /**
     *  获取分组商品
     */
    public function showGoodsByGroupId()
    {
        $group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
        $where = array('id' => $group_id);
        $shop_goods_group = D('Shop_goods_group')->field(true)->where($where)->find();

        $goods_list = M('Shop_goods_group_list')->field('l.id,l.goods_id,g.*')->join(' as l left join '.C('DB_PREFIX').'shop_goods as g on l.goods_id = g.goods_id')->where(['l.group_id' => $group_id])->select();

        // 直接处理成html页面
        $currency_txt = $this->config['Currency_txt'];
        $currency_symbol = $this->config['Currency_symbol'];
        $uid = $this->user_session['uid'];
        $shop_html = '';
        // 右边对应商品分组的商品
        $shop_html .= '<div id="shopProductRightBar2" style="width: 275px;">';
        $shop_html .= '   <dl>';
        $shop_html .= '        <dd id="shopProductRightBar2-'.$shop_goods_group['id'].'" data-cat_id="'.$shop_goods_group['id'].'">';
        $shop_html .= '            <div class="cat_name">'.$shop_goods_group['name'].'　</div>';
        $shop_html .= '            <ul>';

        if (empty($goods_list)) {
            $shop_html .= '<li>暂无数据</li>';
        } else {
            $now_time = time();
            $goods_image_class = new goods_image();
            foreach ($goods_list as $item) {
                if ($item['seckill_open_time'] < $now_time && $now_time < $item['seckill_close_time'] && floatval($item['seckill_price']) > 0 && $item['seckill_stock'] != 0) {
                    $is_seckill = true;
                } else {
                    $is_seckill = false;
                }
                $uid && $item['user_buy_num']  = D('Shop_goods')->getBuyGoodsNum($uid,$item['goods_id']);
                if (empty($item['user_buy_num'])) {
                    $item['user_buy_num'] = 0;
                }

                $tmp_pic_arr = explode(';', $item['image']);

                foreach ($tmp_pic_arr as $key => $value) {
                    $item['pic_arr'][$key]['title'] = $value;
                    if('http' === substr($value,0,4)){
                        $item['pic_arr'][$key]['url'] = $value;
                    }else{
                        $item['pic_arr'][$key]['url'] = $goods_image_class->get_image_by_path($value, 'm');
                    }
                }
                $item['pic'] = reset($item['pic_arr'])['url'];



                $shop_html .= '<li class="product_'.$item['goods_id'].'" data-unit="'.$item['unit'].'" data-product_id="'.$item['goods_id'].'" data-product_price="'.$item['price'].'" data-packing_charge="'.$item['packing_charge'].'" data-product_name="'.$item['name'].'" data-stock="'.$item['stock_num'].'" data-max_num="'.$item['max_num'].'" data-min_num="'.$item['min_num'].'" data-old_price="'.$item['old_price'].'" data-is_seckill="'.$is_seckill.'" data-limit_type="'.$item['limit_type'].'" data-user_buy_num="'.$item['user_buy_num'].'">';
                $shop_html .= '<div class="position_img mall">';
                $shop_html .= '    <img src="'.$item['pic'].'" style="width:60px;">';
                $shop_html .= '</div>';
                $shop_html .= '<div class="product_text">';
                $shop_html .= '<div class="title">'.$item['name'].'</div>';
                $shop_html .= '<div class="sale">已售'.$item['sell_count'].$item['unit'].' 好评'.$item['reply_count'].'</div>';


                if ($item['spec_value'] || $item['is_properties']) {
                    $shop_html .= '<div class="price">'.$currency_symbol.$item['price'];
                    if ($item['spec_value']) {
                        $shop_html .= '起';
                    }
                    $shop_html .= '</div>';
                }else{
                    $shop_html .= '<div class="price">'.$currency_symbol.$item['price'];
                    if ($is_seckill) {
                        $shop_html .= '<span>'.$currency_symbol.$item['o_price'].'</span>';
                    }
                    $shop_html .= '</div>';
                }

                if ($is_seckill) {
                    $shop_html .= '<div class="skill_discount" style="margin-top: 5px;">每个用户限购20份</div>';
                }
                $shop_html .= '</div>';

                if ($item['spec_value'] || $item['is_properties']) {
                    $shop_html .= '<div class="product_btn">可选规格</div>';
                } else {
                    $shop_html .= '<div class="product_btn plus"></div>';
                    $shop_html .= '<div class="bgPlusBack"></div>';
                    $shop_html .= '<div class="bgMinBack"></div>';
                }

                $shop_html .= '</li>';

            }
        }


        $shop_html .= '            </ul>';
        $shop_html .= '        </dd>';
        $shop_html .= '    </dl>';
        $shop_html .= '</div>';


        echo json_encode(array('shop_html' => $shop_html,'goods_group' => $shop_goods_group,'goods_list' => $goods_list));
    }


    public function ajax_get_shop_by_ids(){
        //和快店列表返回一致$_POST['ids']
        $ids = $_POST['ids'];

        $where = array('lat' => $_POST['user_lat'], 'long' => $_POST['user_long']);
        $page = $_GET['page'] ? $_GET['page'] : 0;
        $page_count = 10;
        $lists = D('Merchant_store_shop')->get_list_by_ids($ids, $where,$page.','.$page_count);

        // dump(D('Merchant_store_shop'));
        $return = array();
        $now_time = date('H:i:s');
        $id_array = explode(',', $ids);
        foreach ($id_array as $store_id) {
            $row = isset($lists['shop_list'][$store_id]) ? $lists['shop_list'][$store_id] : '';
            if (empty($row)) continue;
            $temp = array();
            $temp['id'] = $row['store_id'];
            $temp['name'] = $row['name'];
            $temp['range'] = $row['range'];
            $temp['image'] = $row['image'];
            $temp['star'] = round($row['score_mean'],1);
            $temp['month_sale_count'] = $row['sale_count'];
            $temp['delivery'] = $deliver_type == 'pick' ? 0 : $row['deliver'];//是否支持配送
            $temp['delivery_time'] = $row['send_time'];//配送时长
            $temp['send_time_type'] = $row['send_time_type'];//配送时长类型
            $temp['delivery_time_type'] = $this->send_time_type[$row['send_time_type']];//配送时长单位
            $temp['delivery_price'] = floatval($row['basic_price']);//起送价
            $temp['delivery_money'] = floatval($row['delivery_fee']);//配送费
            $temp['delivery_system'] = $row['deliver_type'] == 0 || $row['deliver_type'] == 3 ? true : false;//是否是平台配送

            $temp['is_close'] = $row['state'] ? intval($row['is_close']) : 1;

            $temp['coupon_list'] = array();
            if ($row['is_invoice']) {
                $temp['coupon_list']['invoice'] = floatval($row['invoice_price']);
            }
            if ($row['store_discount'] != 0 && $row['store_discount'] != 100) {
                $temp['coupon_list']['discount'] = $row['store_discount']/10;
            }
            $system_delivery = array();
            
            foreach ($row['system_discount'] as $row_d) {
                if ($row_d['percentage_status']==1) {
                    // 折扣
                    $temp['coupon_list']['sys_discount'][] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money'])*10);

                }else{
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
                
            }
            foreach ($row['merchant_discount'] as $row_m) {
                if ($row_d['percentage_status']==1) {
                    // 折扣
                    $temp['coupon_list']['mer_discount'][] = array('money' => floatval($row_m['full_money']), 'minus' => floatval($row_m['reduce_money'])*10);
                }else{
                    if ($row_m['type'] == 0) {
                        $temp['coupon_list']['newuser'][] = array('money' => floatval($row_m['full_money']), 'minus' => floatval($row_m['reduce_money']));
                    } elseif ($row_m['type'] == 1) {
                        $temp['coupon_list']['minus'][] = array('money' => floatval($row_m['full_money']), 'minus' => floatval($row_m['reduce_money']));
                    }
                }
            }
            if ($row['deliver']) {
                if ($temp['delivery_system']) {
                    $system_delivery && $temp['coupon_list']['delivery'] = $system_delivery;
                } else {
                    if ($row['reach_delivery_fee_type'] == 0) {
                        if ($row['basic_price'] > 0 && $row['delivery_fee'] > 0) {
                            $temp['coupon_list']['delivery'][] = array('money' => floatval($row['basic_price']), 'minus' => floatval($row['delivery_fee']));
                        }
                    } elseif ($row['reach_delivery_fee_type'] == 1) {
                        //$temp['coupon_list']['delivery'] = array('money' => false, 'minus' => $row['delivery_fee']);
                    } elseif ($row['reach_delivery_fee_type'] == 2) {
                        $temp['coupon_list']['delivery'][] = array('money' => floatval($row['no_delivery_fee_value']), 'minus' => floatval($row['delivery_fee']));
                    }
                }
            }

            $temp['coupon_count'] = count($temp['coupon_list']);
            if($temp['is_close']==1){
                $append[] = $temp;
            }else{
                $return[] = $temp;
            }
        }
        if($append){
           $return =  array_merge($return,$append);
        }
        if(empty($return)){
            $return = array();
        }

        $this->success($return);
    }

}
?>