<?php

//餐饮列表
class FoodshopAction extends BaseAction{
    public function index(){
        $this->header_json();
        $page	=	$_POST['page']?$_POST['page']-1:0;
        $page_count	=	10;
        $user_long_lat['long'] =$_POST['long'];
        $user_long_lat['lat']  =$_POST['lat'];
        if(!$user_long_lat['long'] && !$user_long_lat['lat']){
            $user_long_lat = D('User_long_lat')->getLocation($_SESSION['openid']);
        }
        if($_POST['city_id']>0) C('config.now_city',$_POST['city_id']);
        $circle_id = 0;
        $area_url = I('area_url','');
        $sort_id =   I('sort_id','juli');
        $cat_url =   I('cat_url','all');
        if (!empty($area_url)) {
            $tmp_area = D('Area')->get_area_by_areaUrl($area_url);
            if(empty($tmp_area)){
                $this->returnCode('20045008');
            }

            if ($tmp_area['area_type'] == 3) {
                $now_area = $tmp_area;
            } else {
                $now_circle = $tmp_area;
                $now_area = D('Area')->get_area_by_areaId($tmp_area['area_pid'], true, $cat_url);
                if (empty($tmp_area)) {
                    $this->returnCode('20045008');
                }
                $circle_url = $now_circle['area_url'];
                $circle_id = $now_circle['area_id'];
                $area_url = $now_area['area_url'];
            }
            $area_id = $now_area['area_id'];
        }
        //判断排序信息

        $cat_id = 0;
        if($cat_url != 'all'){
            $now_category = D('Meal_store_category')->get_category_by_catUrl($cat_url);
            if (empty($now_category)) {
                $this->returnCode('20045009');
            }
            if (!empty($now_category['cat_fid'])) {
                $f_category = D('Meal_store_category')->get_category_by_id($now_category['cat_fid']);
                $all_category_url = $f_category['cat_url'];
                $cat_fid = $now_category['cat_fid'];
                $cat_id = $now_category['cat_id'];
            } else {
                $cat_id = 0;
                $cat_fid = $now_category['cat_id'];
            }
        }
        $params['area_id'] = $area_id;
        $params['circle_id'] = $circle_id;
        $params['sort'] = $sort_id;
        $params['lat'] = $user_long_lat['lat'];
        $params['long'] = $user_long_lat['long'];
        $params['cat_fid'] = $cat_fid;
        $params['cat_id'] = $cat_id;
        $params['queue'] = -1;
        $params['page'] = $page;
        $return = D('Merchant_store_foodshop')->wap_get_storeList_by_catid($params, 1);
        $page_all = $return['totalPage'];
        $n=1;
        if(!empty($return)) {
            foreach ($return['store_list'] as $v) {
                if ($n > $page_count)
                    break;
                $n++;
                if($v['discount_txt']['discount_type']==1){
                    $v['discount_txt']  = L_('X折',array('X1'=>$v['discount_txt']['discount_percent']));
                }else if($v['discount_txt']['discount_type']==2) {
                    $v['discount_txt']  = L_('每满X1减X2',array('X1'=>$v['discount_txt']['condition_price'],'X2'=>C('config.Currency_symbol') . $v['discount_txt']['minus_price']));
                }else{
                    $v['discount_txt']  = '';
                }
                $tmp_store_list[] = $v;
            }
            $store_lsit = $tmp_store_list;
        }

        $new_group_list =$store_lsit;


        if(!empty($new_group_list)){
            $this->returnCode(0,array('content'=>$new_group_list,'page_all'=>$page_all));exit;
        }else{
            $this->returnCode(0,array('content'=>array(),'page_all'=>$page_all));exit;
        }
    }

    public function header_json(){
        header('Content-type: application/json');
    }

    public function home()
    {
        $return = array();

        $adver = D('Adver')->get_adver_by_key('wap_foodshop_index_top', 5);
        $return['adver'] = empty($adver) ? array() : $adver;

        $sliber = D('Slider')->get_slider_by_key('wap_foodshop_slider', 8);
        $return['sliber'] = empty($sliber) ? array() : $sliber;


        //判断地区信息
        $area_url = !empty($_POST['area_url']) ? $_POST['area_url'] : '';
        //判断排序信息
        $sort = !empty($_POST['sort']) ? htmlspecialchars(trim($_POST['sort'])) : 'juli';

        $queue = isset($_POST['queue']) ? intval($_POST['queue']) : -1;

        $cat_url = isset($_POST['cat_url']) ? htmlspecialchars(trim(($_POST['cat_url']))) : 'all';


        $sort_array = array(
            array('sort_id'=>'juli', 'sort_value'=>L_('离我最近')),
            array('sort_id'=>'rating', 'sort_value'=>L_('评价最高')),
            array('sort_id'=>'defaults', 'sort_value'=>L_('智能排序')),
            array('sort_id'=>'discount', 'sort_value'=>L_('折扣最大')),
        );
        if (0 == $this->config['is_open_merchant_foodshop_discount']) {
            foreach($sort_array as $k => $v){
                if($v['sort_id']=='new' || $v['sort_id']=='discount'){
                    unset($sort_array[$k]);
                }
            }
        }
        $return['sort_list'] = $sort_array;

        $return['queue_list'] = array(L_('不限'), L_('无排号'), L_('可排号'));

        $all_area_list = D('Area')->get_all_area_list();

        $return['area_list'] = $this->removeNumberKey($all_area_list, 'area_list');


        $all_category_list = D('Meal_store_category')->get_all_category();
        $return['category_list'] = $this->removeNumberKey($all_category_list);
        //返回日历可预定天数
        $return['appoint_day'] = $this->config['mer_table_discount_appoint'];
        $this->returnCode(0, $return);
    }
    private function removeNumberKey($list, $index = 'category_list')
    {
        $newList = array();
        foreach ($list as $row) {
            if ($row[$index]) {
                $row[$index] = $this->removeNumberKey($row[$index]);
            }
            $newList[] = $row;
        }
        return $newList;
    }
    public function ajaxList()
    {
        $this->header_json();

        $area_url = !empty($_POST['area_url']) ? $_POST['area_url'] : '';
        $sort = !empty($_POST['sort']) ? htmlspecialchars(trim($_POST['sort'])) : 'juli';
        $queue = isset($_POST['queue']) ? intval($_POST['queue']) : -1;
        $cat_url = isset($_POST['cat_url']) ? htmlspecialchars(trim(($_POST['cat_url']))) : 'all';
        $keyword = isset($_POST['keyword']) ? htmlspecialchars($_POST['keyword']) : '';

        $circle_id = 0;
        $area_id = 0;
        if (!empty($area_url)) {
            $tmp_area = D('Area')->get_area_by_areaUrl($area_url);
            if(empty($tmp_area)){
                $this->returnCode(1, null, L_('当前区域不存在！'));
            }

            if ($tmp_area['area_type'] == 3) {
                $now_area = $tmp_area;
            } else {
                $now_circle = $tmp_area;
                $now_area = D('Area')->get_area_by_areaId($tmp_area['area_pid'], true, $cat_url);
                if (empty($tmp_area)) {
                    $this->returnCode(1, null, L_('当前区域不存在！'));
                }
                $circle_url = $now_circle['area_url'];
                $circle_id = $now_circle['area_id'];
                $area_url = $now_area['area_url'];
            }
            $area_id = $now_area['area_id'];
        }


        $cat_id = 0;
        if($cat_url != 'all'){
            $now_category = D('Meal_store_category')->get_category_by_catUrl($cat_url);
            if (empty($now_category)) {
                $this->returnCode(1, null, L_('此分类不存在！'));
            }
            if (!empty($now_category['cat_fid'])) {
                $f_category = D('Meal_store_category')->get_category_by_id($now_category['cat_fid']);
                $all_category_url = $f_category['cat_url'];

                $cat_fid = $now_category['cat_fid'];
                $cat_id = $now_category['cat_id'];
            } else {
                $cat_id = 0;
                $cat_fid = $now_category['cat_id'];
            }
        }

        $lat = isset($_POST['lat']) ? $_POST['lat'] : 0;
        $long = isset($_POST['long']) ? $_POST['long'] : 0;


        $where = array('area_id' => $area_id, 'circle_id' => $circle_id, 'cat_fid' => $cat_fid, 'cat_id' => $cat_id, 'lat' => $lat, 'long' => $long, 'sort' => $sort, 'queue' => $queue, 'keyword' => $keyword);
        $_GET['page'] = isset($_POST['page']) ? intval($_POST['page']) : 1;
        if (1 == C('config.is_open_merchant_foodshop_discount')) {
            $where['foodChooseDate'] = $_POST['foodChooseDate'];
        }
        $return = D('Merchant_store_foodshop')->wap_get_storeList_by_catid($where);

        if(!empty($return)) {
            foreach ($return['store_list'] as &$v) {
                if ($v['discount_txt']['discount_type'] == 1) {
                    $v['discount_txt'] = L_('X1折',array('X1'=>$v['discount_txt']['discount_percent']));
                } else if ($v['discount_txt']['discount_type'] == 2) {
                    $v['discount_txt'] = L_('每满X1减X2',array('X1'=>$v['discount_txt']['condition_price'],'X2'=>C('config.Currency_symbol') . $v['discount_txt']['minus_price']));
                } else {
                    $v['discount_txt'] = '';
                }
            }
        }
        $this->returnCode(0, $return);
    }

    /**
     * 店铺详情
     */
    public function shop()
    {
        $store_id = $_POST['store_id'];

        $store = M('Merchant_store')->field(true)->where(array('store_id' => $store_id))->find();
        if (empty($store)) {
            $this->returnCode('20020019');
        } else {
            $store['business_time'] = '';
            $store['is_close']      = 1;
            if ($store['open_1'] == '00:00:00' && $store['close_1'] == '00:00:00') {
                $store['is_close']      = 0;
                $store['business_time'] = L_('24小时营业');
            } else {
                $now_time = time();
                $store['business_time'] = substr($store['open_1'], 0, -3) . '~' . substr($store['close_1'], 0, -3);
                $open_1 = strtotime(date('Y-m-d') . $store['open_1']);
                $close_1 = strtotime(date('Y-m-d') . $store['close_1']);
                if ($open_1 >= $close_1) {
                    $close_1 += 86400;
                }
                if ($open_1 < $now_time && $now_time < $close_1) {
                    $store['is_close'] = 0;
                }
                if ($store['open_2'] != '00:00:00' || $store['close_2'] != '00:00:00') {
                    $store['business_time'] .= ';' . substr($store['open_2'], 0, -3) . '~' . substr($store['close_2'], 0, -3);
                    $open_2 = strtotime(date('Y-m-d') . $store['open_2']);
                    $close_2 = strtotime(date('Y-m-d') . $store['close_2']);
                    if ($open_2 >= $close_2) {
                        $close_2 += 86400;
                    }
                    if ($open_2 < $now_time && $now_time < $close_2) {
                        $store['is_close'] = 0;
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
                        $store['is_close'] = 0;
                    }
                }
            }
        }


        $store_image_class   = new store_image();
        $images              = $store_image_class->get_allImage_by_path($store['pic_info']);
        $store['image_list'] = $images;
        $store['image']      = $images ? array_shift($images) : array();

        $merchant = M('Merchant')->field(true)->where(array('mer_id' => $store['mer_id']))->find();
        if (empty($merchant)) {
            $this->returnCode('20140071');
        }
        $foodshop = M('Merchant_store_foodshop')->field(true)->where(array('store_id' => $store_id))->find();
        if (empty($foodshop)) {
            $this->returnCode('20020019');
        }
        $foodshop['pic_count'] = 1;
        $foodshop['pic_str']   = '';
        if (!empty($foodshop['pic'])) {
            $goods_image_class     = new foodshopstore_image();
            $foodshop['pic']       = $goods_image_class->get_allImage_by_path($foodshop['pic'], 'm');
            $foodshop['pic_count'] = count($foodshop['pic']);
            $foodshop['pic_str']   = implode(',', $foodshop['pic']);
        }

        $foodshop = array_merge($store, $foodshop);

        $card_info   = D('Card_new')->get_card_by_mer_id($foodshop['mer_id']);
        $coupon_list = D('Card_new_coupon')->get_coupon_list_by_type_merid('meal', $foodshop['mer_id'], 0, 5, -1);
        if($card_info['self_get']==1){
            $card_info = array('card_id'=>$card_info['card_id'],'mer_id'=>$card_info['mer_id'],'url'=>U('My_card/merchant_card',array('mer_id'=>$card_info['mer_id'])));
            $arr['card_info']= $card_info;
        }
        if($coupon_list){
            foreach ($coupon_list as $coupon) {
                $tmp_coupon[] = array(
                    'coupon_id'=>$coupon['coupon_id'],
                    'name'=>$coupon['name'],
                    'img'=>$coupon['img'],
                    'discount'=>$coupon['discount'],
                    'order_money'=>$coupon['order_money'],
                    'start_time'=>date('Y-m-d',$coupon['start_time']),
                    'end_time'=>date('Y-m-d',$coupon['end_time']),
                );
            }
            $arr['coupon_list']= $tmp_coupon;
        }

        $now_time          = time();
        $sql               = 'SELECT * FROM ' . C('DB_PREFIX') . 'group_store AS gs INNER JOIN ' . C('DB_PREFIX') . 'group AS g ON g.group_id=gs.group_id WHERE gs.store_id=' . $store_id . ' AND `g`.`status`=1  AND `g`.`type`=1 AND `g`.`end_time`>\'' . $now_time . '\' ORDER BY `g`.`sort` DESC,`g`.`group_id` DESC';
        $groups            = D()->query($sql);
        $group_image_class = new group_image();
        foreach ($groups as $row) {
            $tmp_pic_arr     = explode(';', $row['pic']);
            $row_['name'] = $row['name'];
            $row_['is_hotel'] = $row['trade_type']=='hotel'?1:0;
            $row_['list_pic'] = $group_image_class->get_image_by_path($tmp_pic_arr[0], 's');
            $row_['url']      = U('Group/detail', array('pin_num' => $row['pin_num'], 'group_id' => $row['group_id']), true, false, true);

            $row_['price']     = floatval($row['price']);
            $row_['old_price'] = floatval($row['old_price']);
            $row_['wx_cheap']  = floatval($row['wx_cheap']);
            $row_['is_start']  = 1;
            $row_['pin_num']   = $row['pin_num'];
            if ($now_time < $row['begin_time']) {
                $row_['is_start'] = 0;
            }
            if ($row['begin_time'] + 864000 > time() && $row['sale_count'] == 0) {
                $row_['sale_txt'] = L_('新品上架');
            } elseif ($row['begin_time'] + 864000 < time() && $row['sale_count'] == 0) {
                $row_['sale_txt'] = '';
            } else {
                $row_['sale_txt'] = L_('已售X1',array('X1'=>floatval($row['sale_count'] + $row['virtual_num'])));
            }
            $row_['begin_time']        = date("Y-m-d H:i:s", $row['begin_time']);
            $foodshop['group_list'][] = $row_;
        }
        if ($foodshop['is_takeout'] && ($shop = M('Merchant_store_shop')->field(true)->where(array('store_id' => $store_id))->find())) {
            $foodshop['is_takeout'] = 1;
        } else {
            $foodshop['is_takeout'] = 0;
        }
        if($foodshop['long']){
            $foodshop['lng'] = $foodshop['long'];
        }
        $arr['shop']= $foodshop;

        //桌次优惠
        if (1 ==$this->config['is_open_merchant_foodshop_discount']) {
            $thatday = $_POST['foodChooseDate'];
            $thatday = $thatday ? $thatday :  date('Ymd');
            $time_start = strtotime(date($thatday));
            $time_end = strtotime(date($thatday.' 23:59:59'));
            $where['book_time'] = array('between',array($time_start,$time_end));
            $where['status'] = array('in',array(1,2,3,4));
            $where['order_from'] = 0;
            $where['store_id'] = $foodshop['store_id'];
            $order_index = D('foodshop_order')->where($where)->count();

            if($foodshop['foodshop_tables_discount']!=''|| ($foodshop['other_discount']>0 && $foodshop['other_discount']<100)){
                $merchant['foodshop_tables_discount'] = $foodshop['foodshop_tables_discount'];
                $merchant['other_discount'] = $foodshop['other_discount'];
            }else{
                unset($where['store_id']);
                $where['mer_id'] = $store['mer_id'];
                $order_index = D('foodshop_order')->where($where)->count();
            }
            if($merchant['foodshop_tables_discount']!=''){
                $merchant['foodshop_tables_discount'] = substr($merchant['foodshop_tables_discount'], 0, -1);
                $discountArr= explode('|', $merchant['foodshop_tables_discount']);
                $tables = [];
                foreach($discountArr as $k => $v){
                    $v = explode(':',$v);
                    $tables[$k] = $v;
                }
                if(intval($order_index)>=count($tables)){
                    $mer_discount =  $merchant['other_discount'];
                }else{
                    $mer_discount =  $tables[$order_index][1];
                }
            }else{
                $mer_discount =  $merchant['other_discount'];
            }
            $mer_discount = floatval($mer_discount / 10);
            if($mer_discount>0 && $mer_discount<100){
                $arr['mer_discount']=  $mer_discount;
                $arr['date']=  $thatday;
            }
        }
        $tmp_good_list = array();
        $goods_list = M('Foodshop_goods')->field(true)->where(array('store_id' => $store_id, 'status' => 1, 'is_hot' => 1))->select();

        $goods_image_class = new foodshop_goods_image();
        foreach ($goods_list as &$row) {
            $tmp_pic_arr = explode(';', $row['image']);
            foreach ($tmp_pic_arr as $key => $value) {
                $row['pic_arr'][$key]['title'] = $value;
                $row['pic_arr'][$key]['url'] = $goods_image_class->get_image_by_path($value);
            }
            $tmp_good_list[] = array(
                'goods_id'=>$row['goods_id'],
                'name'=>$row['name'],
                'pic'=>$row['pic_arr'][$key]['url']['image'],
            );
        }
        $arr['goods_list']= $tmp_good_list;
        $reply_list = array();
        $reply_list = D('Reply')->get_reply_list($store_id, 4, 1, 3);

        $reply_count = D('Reply')->where(array('status' => array('lt', 2), 'parent_id' => $store_id, 'order_type' => 4))->count();
		// echo '<pre/>';
		// print_r($reply_list);die;
        $arr['reply_list']= $reply_list;
        $arr['isverify']= $merchant['isverify'];
        $arr['reply_count']= $reply_count;


        // $collection_info = D('Merchant_store_collection')->where(array('store_id' => intval($_GET['store_id']), 'uid' => $this->user_session['uid']))->find();
        $collection_info = D('Merchant_user_relation')->get_foodshop_collect($this->user_session['uid'],$_GET['store_id']);
        if ($collection_info) {
            $arr['collection_info']= $collection_info;
        }

        //==================
        $tmp_wap_index_slider = M('Store_slider')->field('url,pic,name')->where(array('status' => 1, 'store_id' => $store_id))->order('sort DESC')->select();

        foreach($tmp_wap_index_slider as &$vs){
            $vs['pic'] = file_domain().'/upload/slider/'.$vs['pic'];
        }
        if ($foodshop['is_book']) {
            $default_slider[] = array(
                'url'  => $this->config['site_url'].'/wap.php?c=Foodshop&a=book_order&store_id='.$store_id,
                'pic'  => $this->config['site_url'].'/tpl/Wap/pure/static/images/book.png',
                'name' => L_('订桌点餐')
            );
        }
        if ($foodshop['is_queue']) {

            $default_slider[] = array(
                'url'  => $this->config['site_url'].'/wap.php?c=Foodshop&a=queue&store_id='.$store_id,
                'pic'  => $this->config['site_url'].'/tpl/Wap/pure/static/images/xqym_05.png',
                'name' => L_('排号')
            );
        }
        if ($this->config['pay_in_store']) {
            $default_slider[] = array(
                'url'  => $this->config['site_url'].'/wap.php?c=My&a=pay&store_id='.$store_id,
                'pic'  => $this->config['site_url'].'/tpl/Wap/pure/static/images/xqym_07.png',
                'name' => $this->config['cash_alias_name']
            );
        }
        if ($foodshop['is_takeout']) {
            $default_slider[] = array(
                'url'  =>$this->config['site_url'].'/wap.php?c=Shop&a=index#shop-'.$store_id,
                'pic'  => $this->config['site_url'].'/tpl/Wap/pure/static/images/xqym_09.png',
                'name' => $this->config['shop_alias_name']
            );
        }
        if ($card_info && $card_info['self_get'] == 1){
            $default_slider[] = array(
                'url'  =>$this->config['site_url'].'/wap.php?c=My_card&a=merchant_card&mer_id='.$store['mer_id'],
                'pic'  => $this->config['site_url'].'/tpl/Wap/pure/static/images/merchant_card.png',
                'name' => L_('会员卡')
            );
        }


        $wap_index_slider = array();
        if($tmp_wap_index_slider){

            $tmp_wap_index_slider = array_merge($default_slider,$tmp_wap_index_slider);
        }else{
            $tmp_wap_index_slider = $default_slider;
        }

        //if(count($tmp_wap_index_slider) >= 10 &&$this->config['wap_slider_number'] == 10){
        $wap_slider_number = $this->config['wap_slider_number'];
        //}else{
        //	$wap_slider_number = 8;
        //}
        $slider_num =0;
        foreach($tmp_wap_index_slider as $key=>$value){

            $tmp_i = floor($key/$wap_slider_number);
            $wap_index_slider[$tmp_i][] = $value;
            $slider_num ++;
        }

        $arr['slider_num'] = $slider_num;
        $arr['isnew'] = D('User')->check_new($_SESSION['user']['uid'],'shop');
        $arr['wap_index_slider'] = $wap_index_slider;
        $arr['wap_index_slider_number'] = $wap_slider_number;

        $this->returnCode(0,$arr);
    }

    //餐饮点餐首页
    public function menu(){
        $store_id = $_POST['store_id'];
        if(empty($store_id)){
            $this->returnCodeError(L_('请携带店铺ID'));
        }
        $now_store = M('Merchant_store')->field(true)->where(array('store_id' => $store_id))->find();
        $now_shop = M('Merchant_store_foodshop')->field(true)->where(array('store_id' => $store_id))->find();
        if (!$now_store || !$now_shop) {
            $this->returnCode('20020019');
        }
        $row = array_merge($now_store, $now_shop);
        $store = array();
        $store_image_class = new store_image();
        $images = $store_image_class->get_allImage_by_path($row['pic_info']);

        $store['id'] = $row['store_id'];
        $store['store_id'] = $row['store_id'];

        /*获取自营商家下的店铺*/
        if (C('config.open_kefu_url') && C('config.kefu_mer_id') > 0) {
            $store_arr = M('Merchant_store')->where(array('mer_id' => C('config.kefu_mer_id')))->getField('store_id', true);
        }
        if (is_array($store_arr) && in_array($store['store_id'], $store_arr)) {
            $store['is_self'] = 1;
        } else {
            $store['is_self'] = 0;
        }

        $store['phone'] = trim($row['phone']);
        $store['long'] = $row['long'];
        $store['lng'] = $row['long'];
        $store['lat'] = $row['lat'];
        $store['isverify'] = $now_mer['isverify'];
        $store['store_theme'] = $row['store_theme'];
        $store['adress'] = $row['adress'];
        $store['close_reason'] = $row['close_reason'];
        $store['openclassfyindex'] = $row['is_mult_class'];
        $store['deliver_type'] = $row['deliver_type'];
        $store['store_notice'] = $row['store_notice'];

        //背景色
        $store['shop_fitment_color'] = $row['shop_fitment_color'] ? $row['shop_fitment_color'] : '113533';
        //标题色
        $store['shop_fitment_front_color'] = 'ffffff';

        $store['image'] = isset($images[0]) ? $images[0] : '';
        if($row['logo']){
            $store['image'] = replace_file_domain($row['logo']);
        }
        $store['origin_image'] = $store['image'];
        $store['image'] = thumb_img($store['image'],130,130,'fill');

        $store['name'] = html_entity_decode($row['name'], ENT_QUOTES);
        $store['images'] = $images ? $images : array();
        $store['share_title'] = $store['name'];
        $store['share_content'] = L_('推荐你一个店铺').'    「 ' . $store['name'] . ' 」';
        $store['share_image'] = $store['image'];

        if ($row['store_theme'] && empty($row['is_mult_class'])) {
            $store['share_url'] = $this->config['site_url'] . 'wap.php?c=Shop&a=classic_shop&shop_id=' . $row['store_id'];
        } else {
            $store['share_url'] = $this->config['site_url'] . '/wap.php?g=Wap&c=Shop&a=index#shop-' . $row['store_id'];
        }

        //返回商家是否支持客服
        if($this->config['open_kefu_plat'] == 'zendesk'){
            $zend_tags = [];
            if($row['city_id']){
                $zend_tags[] = M('Area')->where(['area_id'=>$row['city_id']])->getField('area_name');
            }
            $zend_tags[] = $store['name'];
            
            $store['kefu_url'] = $this->config['site_url'].'/wap.php?c=Redirect&a=packapp&url='.urlencode('my/zendesk_chat.html?account_key='.$this->config['open_kefu_zendesk'].'&zendesk_tag='.urlencode(implode('^&^',$zend_tags)));
        }
        else if($this->DEVICE_ID == 'wxapp'&& M('Merchant_store_kefu')->where(array('store_id'=>$store_id))->find()){
            $store['kefu_url'] = $this->config['site_url'].'/wap.php?c=My&a=concact_kefu&mer_id='.$row['mer_id'].'&store_id='.$store_id.'&from=meal';
        }else{
            $store['kefu_url'] = '';
        }
        //营业时间
        $week = array(L_('周日'),L_('周一'),L_('周二'),L_('周三'),L_('周四'),L_('周五'),L_('周六'));

        $time_arr = D('Merchant_store_open_time')->get_one_time_by_store($row['store_id']);
        $store['time_arr'] = empty($time_arr) ? array() : $time_arr;
        
        $store['time'] = '';
        foreach($time_arr as $value){
            if(date("w") == $value['week']){
                $store['time'] = $value['week_show'] . ' ' . $value['time_show'];
                break;
            }
        }

        // 店铺装修数据 未同步餐饮数据
        $subject_info = M('Merchant_store_foodshop_data')->where(array('store_id'=>$store_id))->find();
       
        $store['shop_preferential'] = array('good_count'=>0);
        $store['banner_list'] = array();
        $today = date('Ymd');
        if($subject_info){
            //轮播图
            if($subject_info['banner']){
                $banner = unserialize($subject_info['banner']);
                foreach ($banner as $key => $value) {
                    if ($value['status']==0) {
                        unset($banner[$key]);
                        continue;
                    }
                    $banner[$key]['pic'] = replace_file_domain($value['pic']);
                    $banner[$key]['pic'] = thumb_img($banner[$key]['pic'],700,180,'fill');
                }
                $store['banner_list'] = array_values($banner);
            }

            //今日特惠是否展示
            if($subject_info['preferential_status']){
                $store['shop_preferential']['name'] = $subject_info['preferential_name'];
                $where_goods = array(
                    'store_id'  => $store['store_id'],
                    'goods_ids' => explode(',',$subject_info['preferential_good_id']),
                );
                $good_list = D('Foodshop_goods_new')->get_list_by_option($where_goods,1);
                if(empty($good_list['goods_list'])){
                    $good_list['goods_list'] = array();
                }else{
                    $tempGoodList = array();
                    foreach($good_list['goods_list'] as $tmp_good_value){
                        $tempGoodList[$tmp_good_value['goods_id']] = $tmp_good_value;
                    }
                    $good_list['goods_list'] = array();
                    foreach($where_goods['goods_ids'] as $goods_id){
                        if($tempGoodList[$goods_id]){
                            $good_list['goods_list'][] = $tempGoodList[$goods_id];
                        }
                    }
                    
                    foreach($good_list['goods_list'] as &$good_value){
                        $good_value['product_sale'] = $good_value['sell_count'];
                        $good_value['product_id']   = $good_value['goods_id'];
                        $good_value['store_id'] = $good_value['store_id'];
                        $good_value['product_name'] = $good_value['name'];
                        $good_value['image']  = $good_value['image'] ? $good_value['image'] : C('config.goods_default_image');
                        $good_value['product_image']= thumb_img($good_value['image'],500,300,'fill');
                        
                        $good_value['product_price']= $good_value['price'];
                        $good_value['has_format']   = ($good_value['spec_value'] || $good_value['is_properties']) ? true : false;
                        $good_value['has_spec']   = $good_value['spec_value'] ? true : false;
                    }
                }
                
                $store['shop_preferential']['good_list'] = $good_list['goods_list'];
                $store['shop_preferential']['good_count'] = count($good_list['goods_list']);
                if($store['shop_preferential']['good_count'] == 0){
                    $store['shop_preferential_show'] = 0;
                    $store['shop_preferential'] = array('good_count'=>0);
                }
            }

            //特殊分类-热销
            if($subject_info['cat_hot_status']){
                $store['cat_hot']['spec_cat_id']  = 'cat_hot';
                $store['cat_hot']['cat_name']   = $subject_info['cat_hot_name'];
                $store['cat_hot']['desc']   = $subject_info['cat_hot_desc'];
                $where_goods = array(
                    'store_id'  => $store['store_id'],
                    'goods_ids' => explode(',',$subject_info['cat_hot_good_id']),
                );
                $sort = 1;
                $sort_type = 1;
                switch($subject_info['cat_hot_sort']){
                    case 1:     //价格从高到低排序
                        $sort = 3;
                        $sort_type = 1;
                        break;
                    case 2:     //价格从低到高排序
                        $sort = 3;
                        $sort_type = 0;
                        break;
                    case 3:     //销量从高到低排序
                        $sort = 2;
                        $sort_type = 1;
                        break;
                    case 4:     //销量从低到高排序
                        $sort = 2;
                        $sort_type = 0;
                        break;
                }
                $good_list = D('Foodshop_goods_new')->get_list_by_option($where_goods,$sort,$sort_type);
                if(empty($good_list['goods_list'])){
                    $good_list['goods_list'] = array();
                }else{
                    foreach($good_list['goods_list'] as &$good_value){
                        $good_value['sell_day'] = $store['stock_type'] ? $today : $good_value['sell_day'];
                       
                        // if ($today == $good_value['sell_day']) {
                        //     $good_value['stock'] = $good_value['stock'];
                        // } else {
                        //     $good_value['stock'] = $good_value['original_stock'];
                        // }
                        
                        $good_value['product_sale'] = $good_value['sell_count'];
                        $good_value['product_id']   = $good_value['goods_id'];
                        $good_value['store_id'] = $good_value['store_id'];
                        $good_value['product_name'] = $good_value['name'];
                        $good_value['product_describe'] = $good_value['describe'];
                        $good_value['describe'] = $good_value['describe'];
                        $good_value['product_image']= $good_value['image'] ? thumb_img($good_value['image'],180,180,'fill') : '';
                        $good_value['product_price']= $good_value['price'];
                        $good_value['has_format']   = ($good_value['spec_value'] || $good_value['is_properties']) ? true : false;
                        $good_value['has_spec']   = $good_value['spec_value'] ? true : false;
                    }
                }
                $store['cat_hot']['product_list'] = $good_list['goods_list'];
                $store['cat_hot']['good_count'] = count($good_list['goods_list']);
                if($store['cat_hot']['good_count'] == 0){
                    unset($store['cat_hot']);
                }
            }
            //特殊分类-优惠
            if($subject_info['cat_discount_status']){
                $store['cat_discount']['spec_cat_id']  = 'cat_discount';
                $store['cat_discount']['cat_name']  = $subject_info['cat_discount_name'];
                $store['cat_discount']['desc']  = $subject_info['cat_discount_desc'];
                $where_goods = array(
                    'store_id'  => $store['store_id'],
                    'goods_ids' => explode(',',$subject_info['cat_discount_good_id']),
                );
                $sort = 1;
                $sort_type = 1;
                switch($subject_info['cat_discount_sort']){
                    case 1:     //价格从高到低排序
                        $sort = 3;
                        $sort_type = 1;
                        break;
                    case 2:     //价格从低到高排序
                        $sort = 3;
                        $sort_type = 0;
                        break;
                    case 3:     //销量从高到低排序
                        $sort = 2;
                        $sort_type = 1;
                        break;
                    case 4:     //销量从低到高排序
                        $sort = 2;
                        $sort_type = 0;
                        break;
                }
                $good_list = D('Foodshop_goods_new')->get_list_by_option($where_goods,$sort,$sort_type);
                if(empty($good_list['goods_list'])){
                    $good_list['goods_list'] = array();
                }else{
                    foreach($good_list['goods_list'] as &$good_value){
                        $good_value['product_sale'] = $good_value['sell_count'];
                        $good_value['product_id']   = $good_value['goods_id'];
                        $good_value['store_id'] = $good_value['store_id'];
                        $good_value['product_name'] = $good_value['name'];
                        $good_value['product_describe'] = $good_value['describe'];
                        $good_value['describe'] = $good_value['describe'];
                        $good_value['product_image']= $good_value['image'] ? thumb_img($good_value['image'],180,180,'fill') : '';
                        $good_value['product_price']= $good_value['price'];
                        $good_value['has_format']   = ($good_value['spec_value'] || $good_value['is_properties']) ? true : false;
                        $good_value['has_spec']   = $good_value['spec_value'] ? true : false;
                    }
                }
                $store['cat_discount']['product_list'] = $good_list['goods_list'];
                $store['cat_discount']['good_count'] = count($good_list['goods_list']);
                if($store['cat_discount']['good_count'] == 0){
                    unset($store['cat_discount']);
                }
            }
        }


        // 商品列表
        // $return['category_list'] = D('Shop_goods_sort')->lists($store['store_id']);
        $store['product_list'] = D('Foodshop_goods_new')->getListByStoreId($store['store_id']);
        fdump($store['product_list'],'product_list');
        if ($store['cat_discount']) {
            $store['product_list'] = array_merge(array($store['cat_discount']),$store['product_list']);
        }else{
            $store['product_list'] = array_merge(array(),$store['product_list']);
        }
        fdump($store['cat_discount'],'product_list',1);
        if ($store['cat_hot']) {
            $store['product_list'] = array_merge(array($store['cat_hot']),$store['product_list']);
        }else{
            $store['product_list'] = array_merge(array(),$store['product_list']);
        }
        fdump($store['cat_hot'],'product_list',1);
        fdump($store['product_list'],'product_list',1);
        // var_dump($return['category_list']);
      
        // 店铺优惠
        $discounts = D('Shop_discount')->getDiscounts($now_store['mer_id'], $store_id, '', -1, 1);

         if (isset($discounts[0]) && $discounts[0]) {
            foreach ($discounts[0] as $row_d) {

                if ($row_d['type'] == 0) {//新单
                    $store['coupon_list']['system_newuser'][] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']), 'order_count' => $row_d['order_count'], 'usage_scenario' => $row_d['usage_scenario'], 'percentage_status' => $row_d['percentage_status']);
                } elseif ($row_d['type'] == 1 ) {//满减
                    $store['coupon_list']['system_minus'][] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']), 'order_count' => $row_d['order_count'], 'usage_scenario' => $row_d['usage_scenario'], 'percentage_status' => $row_d['percentage_status']);
                } elseif ($row_d['type'] == 2 ) {//配送
                    $system_delivery[] = array('money' => floatval($row_d['full_money']), 'minus' => floatval($row_d['reduce_money']), 'order_count' => $row_d['order_count'], 'usage_scenario' => $row_d['usage_scenario'], 'percentage_status' => $row_d['percentage_status']);
                }
            }
        }

        if (isset($discounts[$store_id]) && $discounts[$store_id]) {
            foreach ($discounts[$store_id] as $row_m) {
                if ($row_m['type'] == 0) {
                    $store['coupon_list']['newuser'][] = array('money' => floatval($row_m['full_money']), 'minus' => floatval($row_m['reduce_money']), 'order_count' => $row_m['order_count'], 'usage_scenario' => $row_m['usage_scenario']);
                } elseif ($row_m['type'] == 1) {
                    $store['coupon_list']['minus'][] = array('money' => floatval($row_m['full_money']), 'minus' => floatval($row_m['reduce_money']), 'order_count' => $row_m['order_count'], 'usage_scenario' => $row_m['usage_scenario']);
                }
            }
        }

        $store['coupon_list_simple'] = D('Merchant_store')->simpleParseCouponFoodshop($store['coupon_list'],'array');
        $store['coupon_list'] = D('Merchant_store')->parseCoupon($store['coupon_list'],'array');

        $this->returnCodeOk($store);

    }


    //餐饮点餐首页
    public function goods_detail(){
        $goods_id = isset($_POST['goods_id']) ? intval($_POST['goods_id']) : 1;
        $now_goods = D('Foodshop_goods_new')->get_goods_by_id($goods_id);
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
                $value['price'] = strval(floatval($value['price']));
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
       

        $store = M('Merchant_store_shop')->field('store_id, store_theme, is_mult_class')->where(array('store_id' => $now_goods['store_id']))->find();
        
        $store_name = html_entity_decode(M('Merchant_store')->where(array('store_id' => $now_goods['store_id']))->getField('name'), ENT_QUOTES);

        $now_goods['image'] = $now_goods['image'] ? thumb($now_goods['image'],750,1000) : '';
        $now_goods['share_image'] = thumb($now_goods['share_image'],750,1000);
        foreach($now_goods['pic_arr'] as $pic_key=>$pic_value){
            $now_goods['pic_arr'][$pic_key]['url'] = thumb($pic_value['url'],750,1000);
        }

        // unset($now_goods['old_price']);
        unset($now_goods['seckill_open_time']);
        unset($now_goods['seckill_close_time']);
        unset($now_goods['last_time']);
        unset($now_goods['sort']);
        unset($now_goods['status']);
        unset($now_goods['print_id']);
        unset($now_goods['sell_day']);
        unset($now_goods['spec_value']);
        unset($now_goods['is_properties']);
        unset($now_goods['today_sell_spec']);
        unset($now_goods['today_seckill_count']);
        unset($now_goods['cat_fid']);
        unset($now_goods['cat_id']);
        unset($now_goods['freight_type']);
        unset($now_goods['freight_value']);
        unset($now_goods['freight_template']);
        unset($now_goods['extra_pay_price']);
        unset($now_goods['cost_price']);
        unset($now_goods['original_goods_id']);
        unset($now_goods['relation_map']);
        unset($now_goods['original_stock']);
        unset($now_goods['score_percent']);
        unset($now_goods['score_max']);
        unset($now_goods['zbwId']);
        unset($now_goods['show_start_time']);
        unset($now_goods['show_end_time']);
        unset($now_goods['limit_type']);
        unset($now_goods['json']);
        unset($now_goods['val_status']);
        unset($now_goods['properties_status_list']);
        unset($now_goods['deliver_fee']);

        // 如果有附属商品就获取商品区块列表,关联的附属商品等信息
        $now_goods['subsidiary_piece'] = array();
          if($now_goods['is_subsidiary_goods'] == 1){
            $now_goods['subsidiary_piece'] = D('Shop_goods')->get_Subsidiary_Piece($now_goods['goods_id']);
            foreach ($now_goods['subsidiary_piece'] as $key => $_subsidiary_piece) {
                $goods_ids = array();
            
                fdump($_subsidiary_piece['goods'],'now_goods11');
                foreach($_subsidiary_piece['goods'] as &$good_value){
                    $good_value['sell_day'] = $store['stock_type'] ? $today : $good_value['sell_day'];
                    if ($good_value['is_seckill_price']) {
                        $good_value['stock'] = $good_value['seckill_stock'];
                    } else {
                        if ($today == $good_value['sell_day']) {
                            $good_value['stock'] = $good_value['stock'];
                        } else {
                            $good_value['stock'] = $good_value['original_stock'];
                        }
                    }

                    $good_value['price'] = floatval($good_value['price_sug']);
                    $good_value['old_price'] = floatval($good_value['old_price']);
                    $good_value['product_sale'] = $good_value['sell_count'];
                    $good_value['product_id']   = $good_value['goods_id'];
                    $good_value['store_id'] = $good_value['store_id'];
                    $good_value['product_name'] = $good_value['name'];
                    $good_value['product_image']= $good_value['image'];
                    $good_value['product_price']= floatval($good_value['price']);
                    $good_value['has_format']   = ($good_value['spec_value'] || $good_value['is_properties']) ? true : false;
                    $good_value['has_spec']   = $good_value['spec_value'] ? true : false;


                    $return = D('Shop_goods')->format_spec_value($good_value['spec_value'], $good_value['goods_id'], $good_value['is_properties'], $good_value['min_num']);
                    $good_value['json'] = isset($return['json']) ? json_encode($return['json']) : '';
                    $good_value['properties_list'] = isset($return['properties_list']) ? $return['properties_list'] : '';
                    $good_value['properties_status_list'] = isset($return['properties_status_list']) ? $return['properties_status_list'] : '';
                    $good_value['spec_list'] = isset($return['spec_list']) ? $return['spec_list'] : '';
                    $good_value['list'] = isset($return['list']) ? $return['list'] : '';

                    foreach($good_value['spec_list'] as &$value){
                        $value['list'] = array_values($value['list']);
                    }

                    if($good_value['spec_list']){
                        foreach($good_value['spec_list'] as &$value){
                            $value['id_'] = $value['id'];
                            unset($value['id']);
                            foreach($value['list'] as &$v){
                                $v['id_'] = $v['id'];
                                unset($v['id']);
                            }
                        }
                        $good_value['spec_list'] = array_values($good_value['spec_list']);
                    }else{
                        $good_value['spec_list'] = array();
                    }

                    if($good_value['properties_list']){
                        foreach($good_value['properties_list'] as &$value){
                            $value['id_'] = $value['id'];
                            unset($value['id']);
                        }
                        $good_value['properties_list'] = array_values($good_value['properties_list']);
                    }else{
                        $good_value['properties_list'] = array();
                    }

                    if($good_value['list']){
                        foreach($good_value['list'] as &$good_value){
                            $good_value['price'] = strval(floatval($good_value['price']));
                            if($good_value['properties']){
                                foreach($good_value['properties'] as &$v){
                                    $v['id_'] = $v['id'];
                                    unset($v['id']);
                                }
                            }else{
                                $good_value['properties'] = array();
                            }
                        }
                    }else{
                        $good_value['list'] = $this->getObj();
                    }
                    unset($good_value['seckill_open_time'],$good_value['seckill_close_time'],$good_value['last_time'],$good_value['sort'],$good_value['status'],$good_value['print_id'],$good_value['sell_day'],$good_value['spec_value'],$good_value['is_properties'],$good_value['today_sell_spec'],$good_value['today_seckill_count'],$good_value['cat_fid'],$good_value['cat_id'],$good_value['freight_type'],$good_value['freight_value'],$good_value['freight_template'],$good_value['extra_pay_price'],$good_value['cost_price'],$good_value['original_goods_id'],$good_value['relation_map'],$good_value['score_percent'],$good_value['score_max'],$good_value['zbwId'],$good_value['show_start_time'],$good_value['show_end_time'],$good_value['limit_type'],$good_value['json'],$good_value['val_status'],$good_value['properties_status_list'],$good_value['deliver_fee']);
                }
                $now_goods['subsidiary_piece'][$key]['goods'] = $_subsidiary_piece['goods'];
            }
        }
        $this->returnCodeOk($now_goods);
    }

    //计算价格准备提交订单
    public function ajax_cart()
    {
        $store_id = intval($_POST['store_id']);
        $productCart = $_POST['productCart'];
        
        if (empty($productCart)) {
            $this->returnCode(10110003);
        }
        if (!is_array($productCart)) {
            $productCart = json_decode(htmlspecialchars_decode($productCart), true);
        }

        $return = D('Foodshop_goods_new')->checkCart($store_id, $this->_uid, $productCart);
        
        if ($return['error_code']) {
            $this->returnCodeError($return['msg']);
        }
        foreach($return['goods'] as &$v) {
            $v['old_price'] = strval($v['old_price']);
            $v['old_price_all'] = getFormatNumber($v['old_price'] * $v['num']);
        }

        $result = array('goods_list' => $return['goods']);

        $result['price'] = strval($return['price']);
        
        // 税率
        $result['tax_rate_price'] = 0;
        if ($this->config['open_meal_tax_rate']) {
            $result['tax_rate_price'] =  round($this->config['open_meal_tax_rate']*$result['price']/100,2);
        }

        // 应付金额
        $result['pay_price'] =  $result['price'];
        // 优惠金额
        $result['discount_price'] = strval(round($return['price'] - $result['pay_price'], 2));

        // 应付金额+税率
        $result['pay_price'] =  $result['price']+$result['tax_rate_price'];

        $result['store_id'] = $return['store']['store_id'];
        $result['mer_id'] = $return['store']['mer_id'];
        $result['name'] = $return['store']['name'];
        $result['images'] = $return['store']['images'];
        $result['cn_name'] = $return['store']['name'];
        $result['order_time'] = date('H:i d/m/y');
       
      
        $result['userphone'] = $return['userphone'];

        $merchant = D('Merchant')->where(array('mer_id'=>$return['store']['mer_id']))->find();
        $result['goods_list'] = D('Shop_goods')->replace_goods_name($result['goods_list'],$merchant['merchant_default_lang']);

        // 组合附属商品
        $result['goods_list'] =  D('Shop_goods')->combination_data($result['goods_list'], 'str', 0);
        
        $this->returnCode(0, $result);
    }

    //餐饮点餐首页
    public function order_save(){
        $store_id = intval($_POST['store_id']);
        $cartid = isset($_POST['cartid']) ? htmlspecialchars(trim($_POST['cartid'])) : '';

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

            if (empty($productCart)) {
                $this->returnCode(10100003);
            }
            if (!is_array($productCart)) {
                $productCart = json_decode(htmlspecialchars_decode($productCart), true);
            }

            $return = D('Shop_goods')->checkCart($store_id, $this->_uid, $productCart);
        }
        if ($return['error_code']) {
            $this->returnCode(1, null, $return['msg']);
        }


        $invoice_head = isset($_POST['invoice_head']) ? htmlspecialchars($_POST['invoice_head']) : '';  //cue_field
  
        $note = isset($_POST['desc']) ? htmlspecialchars($_POST['desc']) : '';
        $extraPrice = 0;
       
        $user_info = D('User')->where(array('uid' => $this->_uid))->find();
        $now_time = time();
        $order_data = array();
        $order_data['mer_id'] = $return['mer_id'];
        $order_data['store_id'] = $return['store_id'];
        $order_data['uid'] = $this->_uid;
        $order_data['is_tmp_order'] = $_POST['is_tmp_order'] ? true : false;    //临时订单号
        $order_data['tmp_order_id'] = $_POST['tmp_order_id'] ? intval($_POST['tmp_order_id']) : 0;  //临时订单号
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
        $orderid = strlen($this->_uid) <= 8 ? date('ymdhis') . substr(microtime(), 2, 8 - strlen($this->_uid)) . $this->_uid : date('ymdhis') . substr($this->_uid,0,8);
        $order_data['real_orderid'] = $orderid;
        $order_data['no_bill_money'] = 0;//无需跟平台对账的金额
        $order_data['username'] = isset($_POST['username']) && $_POST['username'] ? $_POST['username'] : '';
        $order_data['userphone'] = isset($_POST['userphone']) && $_POST['userphone'] ? $_POST['userphone'] : '';
        $order_data['userphone'] = isset($user_info['phone']) && $user_info['phone'] ? $user_info['phone'] : '';
        $order_data['balance_reduce'] = $return['sys_first_reduce'] + $return['sys_full_reduce'];//平台优惠
        $order_data['other_money'] = $extraPrice;//加价送的金额
        $order_data['order_from'] = $order_from;//订单来源:0：wap快店，1：wap商城，2：Android，3：ios,4:小程序,5：pc快店
        $order_data['goods_price'] = $return['price'];//商品的价格
        $order_data['extra_price'] = $return['extra_price'];//另外要支付的金额
        $order_data['can_discount_money'] = $return['can_discount_money'] + $return['packing_charge'] + $extraPrice;//可用商家优惠券的总价
        $order_data['discount_price'] = $return['vip_discount_money'];//商品折扣后的总价
        $order_data['vip_level_discount'] = $return['vip_discount'];//会员等级折扣
        $order_data['total_price'] = $return['price'] + $delivery_fee + $return['packing_charge'] + $extraPrice;//订单总价  商品价格+打包费+配送费
        $order_data['price'] = $order_data['discount_price'] + $delivery_fee + $return['packing_charge'] - $order_data['merchant_reduce'] - $order_data['balance_reduce'] + $extraPrice;//实际要支付的价格
        $order_data['discount_detail'] = $return['discount_list'] ? serialize($return['discount_list']) : '';//优惠详情
        $order_data['reduce_stock_type'] = $return['store']['reduce_stock_type'];//'减库存类型（0：支付后，1：下单后）'

        if ($order_id = D('Shop_order')->saveOrder($order_data, $return, $user_info)) {
            if (!$order_data['is_tmp_order'] && $order_data['status'] == 9) {
                if($order_data['tmp_order_id']){
                    D('Shop_order_log')->where(array('order_id' => $order_id, 'status' => 13))->delete(); //删除原数据
                }
                D('Shop_order_log')->add_log(array('order_id' => $order_id, 'status' => 13, 'name' => $return['store']['name'], 'phone' => $return['store']['phone']));//接货
            }
            $this->returnCodeOk(array('order_id' => $order_id, 'type' => 'shop'));
        } else {
            $this->returnCodeError(L_('订单保存失败'));
        }
    }

    // 获得所有商品
    public function getGoodsList()
    {
        $store_id = isset($_POST['store_id']) ? intval($_POST['store_id']) : 0;
        $foodshop = D('Merchant_store_foodshop')->now_store($store_id);
        if ($foodshop['err_code']) {
            $this->returnCodeError($foodshop['msg']);
        }

        $foodshop = $foodshop['data'];

        $lists = D('Foodshop_goods')->get_list_by_storeid($foodshop['store_id'], 0, '', 0);
        $goods_detail = array();
        foreach ($lists as $rowset) {
            foreach ($rowset['goods_list'] as $row) {
                if ($row['list']) {
                    $goods_detail[$row['goods_id']] = array();
                    foreach ($row['list'] as $index => $r) {
                        $propertiesArr = array();
                        if (isset($r['properties']) && $r['properties']) {
                            foreach ($r['properties'] as $vr) {
                                $propertiesArr[] = array('id' => $vr['id'], 'num' => $vr['num']);
                            }
                        }
                        $goods_detail[$row['goods_id']][$index] = array('price' => $r['price'], 'stock_num' => $r['stock_num'], 'properties' => $propertiesArr);
                    }
                }
            }
        }
        $goods_package = D('Foodshop_goods_package')->where(array('store_id' => $store_id,'status' => 1))->order('`id` ASC')->select();
        if ($goods_package) {
            foreach($goods_package as &$value){
                $value['price'] = floatval($value['price']);
                $value['goods_id'] = 0;
            }
            $lists[] = array('goods_list' => $goods_package, 'sort_name' => '团购套餐', 'sort_id' => -1, 'store_id' => $store_id);
        }
        $rerturnArr['goods_list'] = $lists;
        $rerturnArr['all_goods'] = $goods_detail;
        $rerturnArr['store'] = $foodshop;
        $this->returnCodeOk($rerturnArr);
        
    }

    // 获取购物车商品
    public function ajaxGetGoods(){

        // $this->_uid = 1358679;
        // 未登录
        if (!$this->_uid) {
            $this->returnCode('20046009');
        }

        // 店铺id
        $store_id = $_POST['store_id'];
        if (!$store_id) {
            $this->returnCodeError(L_('缺少店铺id'));
        }

        // 店铺信息
        $foodshop = D('Merchant_store_foodshop')->now_store($store_id, false);
        if ($foodshop['err_code']) {
            $this->returnCodeError($foodshop);
        }
        
        $foodshop = $foodshop['data'];

        $cartDB = D('Foodshop_cart');
        $goodsDB = D('Foodshop_goods');

        // 原来的数据
        $goods_where = array('uid'=>$this->_uid, 'store_id' => $store_id);
        $goods_list = $cartDB->field(true)->where(array($goods_where))->select();
        $goods_list_format = array();
        foreach ($goods_list as &$value) {
            $value['num'] = intval($value['num']);
            if ($value['package_id']) {
                if ($value['ishide']==1) {
                    // 同一个套餐，不同菜品算不同套餐
                    $goods_list_tmp = $cartDB->field(true)->where(array('uid' => $this->_uid, 'fid' => $value['id'], 'store_id' => $store_id))->select();
                    $value['params'] = $goods_list_tmp;
                    $value['type'] = 'group';
                    $value['index'] = $value['goods_id'];
                    foreach ($goods_list_tmp as $_tmp) {
                        $value['index'] .= '_' . $_tmp['goods_id'];
                    }
                    $goods_list_format[] = $value;
                
                }
            } else {
                $value['type'] = 'only';
                $value['params'] = array('goods_id'=>$value['goods_id'],'unit'=>$value['unit'],'name'=>$value['name'],'price'=>$value['price']);
                $spec = $goodsDB->format_spec_ids($value,array());
                $properties = $goodsDB->format_properties_ids($value,array());
                $value['params'] = array_merge($spec,$properties );
                $goods_list_format[] = $value;
            }
        }
        $this->returnCodeOk($goods_list_format);
    }

    // 保存购物车
    public function ajaxSaveGoods(){
        // 未登录
        if (!$this->_uid) {
            $this->returnCode('20046009');
        }

        // 店铺id
        $store_id = $_POST['store_id'];
        if (!$store_id) {
            $this->returnCodeError(L_('缺少店铺id'));
        }

        // 商品信息
        $productCart = $_POST['goodsCart'];
        if (!$productCart) {
            $this->returnCodeError(L_('缺少商品信息'));
        }

        // 验证店铺状态
        $foodshop = D('Merchant_store_foodshop')->now_store($store_id, false);
        if ($foodshop['err_code']) {
            $this->returnCodeError($foodshop);
        }
        
        $foodshop = $foodshop['data'];
        
        $cartDB = D('Foodshop_cart');
        $goodsDB = D('Foodshop_goods');

        $goods_where = array('uid'=>$this->_uid, 'store_id' => $store_id);

        // 删除原来的数据
        $goods_list = $cartDB->field(true)->where($goods_where)->select();
        if ($goods_list) {
            $del_ids = array();
            foreach ($goods_list as $tmp) {
                $del_ids[] = $tmp['id'];
            }
            $cartDB->where(array('id' => array('in', $del_ids)))->delete();
        }
        
        $new_goods_list = array();//提交的商品信息（除套餐中的商品）
        $packageTemp = array();//提交的套餐数据
        if ($productCart) {
            $productCart2 = array();
            foreach ($productCart as $row) {
                if ($row['type'] == 'only') {
                    $row['fid'] = 0;
                    $row['package_id'] = 0;
                    $row['uid'] = intval($this->_uid);
                    $productCart2[] = $row;
                } else {
                    $packageTemp[] = $row;
                }
            }
            
            $cart_data = $goodsDB->format_cart($productCart2, $store_id, $order_id);
            if ($cart_data['err_code']) {
                $this->returnCodeError($cart_data);
            }
            $new_goods_list = $cart_data['data'];
        }

        // 添加新的套餐
        foreach ($packageTemp as $pt) {
            //增加套餐
            $tmp = array();
            $tmp['uid'] = intval($this->_uid);
            $tmp['store_id'] = $store_id;
            $tmp['package_id'] = $pt['goods_id'];
            $tmp['goods_id'] = $pt['goods_id'];
            $tmp['ishide'] = 1;
            $tmp['is_discount'] = 1;
            $tmp['name'] = $pt['name'];
            $tmp['price'] = $pt['price'];
            $tmp['extra_price'] = 0;
            $tmp['num'] = $pt['num'];
            $tmp['create_time'] = time();
            $fid = $cartDB->add($tmp);
            foreach ($pt['params'] as $gd) {
                $gd['uid'] = intval($this->_uid);
                $gd['store_id'] = $store_id;
                $gd['package_id'] = $pt['goods_id'];
                $gd['extra_price'] = 0;
                $gd['is_discount'] = 1;
                $gd['num'] = $pt['num'];
                $gd['fid'] = $fid;
                $gd['create_time'] = time();
                $cartDB->add($gd);
            }
        }
        // 添加新的菜品
        if ($new_goods_list) {
            foreach ($new_goods_list as $index => $new_row) {
                $new_row['store_id'] = $store_id;
                $new_row['uid'] = intval($this->_uid);
                $new_row['extra_price'] = empty($new_row['extra_price'])?0:$new_row['extra_price'];
                $new_row['create_time'] = time();
                $cartDB->add($new_row);
                
            }
        }
        $this->returnCodeOk(0);
    }

    // 不绑定桌台 保存订单
    public function unbindTableSaveOrder(){
        // $this->_uid = 1358679;
        // 未登录
        if (!$this->_uid) {
            $this->returnCode('20046009');
        }

        // 店铺id
        $store_id = $_POST['store_id'];
        if (!$store_id) {
            $this->returnCodeError(L_('缺少店铺id'));
        }

        // 验证店铺状态
        $foodshop = D('Merchant_store_foodshop')->now_store($store_id, false);
        if ($foodshop['err_code']) {
            $this->returnCodeError($foodshop);
        }

        // 购物车
        $cartDB = D('Foodshop_cart');
        $goods_where = array('uid'=>$this->_uid, 'store_id' => $store_id);
        $goods_list = $cartDB->field(true)->where($goods_where)->select();
        if (!$goods_list) {
            $this->returnCodeError(L_('购物车为空'));
        }
        $price = D('Foodshop_cart')->count_price($goods_list);
        // 订单数据
        $data = array(
            'mer_id' => $foodshop['data']['mer_id'], 
            'uid' => $this->_uid, 
            'store_id' => $store_id,
        );
        $data['create_time'] = time();
        // $data['total_price'] = $price;
        // $data['price'] = $price;
        $data['status'] = 1;
        $data['unbind_table'] = 1;
        $data['real_orderid'] = build_real_orderid($this->_uid);
        
        // 用户名称
        $now_user = D('User')->get_user($this->_uid);
        $data['name'] = $now_user['nickname'];
        if ($order_id = D('Foodshop_order')->save_order($data,false)) {
            // 保存商品
            $save_goods = [];
            foreach ($goods_list as $key => $value) {
                //增加套餐
                $tmp = $value;
                unset($tmp['id']);
                $tmp['order_id'] = $order_id;
                $save_goods[] = $tmp;
            }

            if(!D('Foodshop_order_detail')->addAll($save_goods)){
                $this->returnCodeError(L_('商品保存失败，稍后重试！'));
            }
            
            // Plat_order表
            $pay_order_param = array(
                    'business_type' => 'foodshop',
                    'business_id' => $order_id,
                    'order_name' => '餐饮订单',
                    'uid' => $this->_uid,
                    'store_id' => $store_id,
                    'total_money' => $price,
                    'wx_cheap' => 0,
            );
            $result = D('Plat_order')->add_order($pay_order_param);
            if($result['error_code']){
                $this->returnCodeError(L_('订单保存失败，稍后重试！'));
            }else{
                /* 增加系统订单记录 2019/01/29 */
                D('Foodshop_order')->add_system_order($order_id,1);

                $this->returnCodeOk(['order_id'=>$result['order_id'],'type' => 'plat']);
            }
        } else {
                $this->returnCodeError(L_('订单保存失败，稍后重试！'));
        }
    }
}
?>