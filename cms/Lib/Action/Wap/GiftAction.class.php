<?php
class GiftAction extends BaseAction{
    public function __construct(){
        parent::__construct();
        $this->merchant_session = session('merchant_gift');
        $this->assign('merchant_session' , $this->merchant_session);
    }

    // 商家退出登录积分商城
    public function merchant_logout()
    {
        session('merchant_gift',null);
        $pigcms_referer = !empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : U('Index/Index/index');
        redirect($pigcms_referer);
    }


    public function index(){ 
        $database_adver = D('Adver');
        $database_gift_category = D('Gift_category');

        //导航start
        $gift_adver_list = $database_adver->get_adver_by_key('wap_gift_slider', 3);
        //导航end

        if( $this->user_session['uid'] && $this->config['open_rand_send']){
             $coupon_html = D('System_coupon')->rand_send_coupon_get(array('time'=>$_SERVER['REQUEST_TIME'],'uid'=>$this->user_session['uid']));
            $coupon_html && $this->assign('coupon_html',$coupon_html);
        }

        //用户信息start
        if($this->user_session){
            $database_user = D('User');
            $now_user = $database_user->get_user($this->user_session['uid']);
            $this->assign('now_user',$now_user);
        }
        //用户信息end

        //分类列表start
        $gift_cat_condition['is_del'] = 0;
        //$gift_cat_condition['status'] = 1;
        $gift_cat_condition['cat_fid'] = 0;
        $gift_cat_condition['cat_status'] = 1 ;
        $gift_category_list = $database_gift_category->gift_category_page_list($gift_cat_condition , true , 'cat_sort desc' , 999);
        //分类列表end

        // 查询广告
        // $gift_adver = D("Adver")->get_adver_by_key("wap_gift_adver", 3);

        $this->assign('gift_adver_list' , $gift_adver_list);
        $this->assign('gift_category_list' , $gift_category_list['list']);
        $this->assign('gift_adver' , $gift_adver);
        $this->display();
    }

    public function gift_list()
    {
        $cat_id = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;
        $category = M('Gift_category')->field(true)->where(array('is_del' => 0, 'status' => 1, 'cat_id' => $cat_id))->find();
        $this->assign('gift_category_detail', $category);
        $this->display();
    }

    public function ajax_gift()
    {
        $cat_id = isset($_GET['cat_id']) ? intval($_GET['cat_id']) : 0;
        $order = isset($_POST['order']) ? trim($_POST['order']) : '';
        $type = isset($_GET['type']) ? $_GET['type'] : '';
        
        switch ($order) {
            case 'integral_desc':
                $order = 'exchange_type asc, payment_pure_integral desc';
                break;
            case 'integral_asc':
                $order = 'exchange_type asc, payment_pure_integral asc';
                break;
            default:
                $order = 'sort desc';
                break;
        }
        
        $where = array('is_del' => 0, 'status' => 1);
        if (!empty($cat_id)) {
            $where['cat_fid'] = $cat_id;
        }
        if(!empty($type) && $type == 'new'){
            $where['is_new'] = '1';
        }
        $return = D('Gift')->gift_page_list($where, true, $order, 10);
        exit(json_encode($return));
    }
    
    public function gift_detail(){
        $gift_id = $_GET['gift_id'] + 0;
        if(empty($gift_id)){
            $this->error_tips('传递参数有误！');
        }

        $database_gift = D('Gift');
        $gift_condition['is_del'] = 0;
        $gift_condition['status'] = 1;
        $gift_condition['gift_id'] = $gift_id;
        $gift_detail = $database_gift->gift_detail($gift_condition);
        /*商家积分商城商品获取商家信息*/
        if($gift_detail['detail']['mer_id']>0){
            $mer_info=M('Merchant')->where(array('mer_id'=>$gift_detail['detail']['mer_id']))->field('name,province_id,city_id,area_id,phone')->find();
            if(!empty($mer_info)){
                $where=array();
                $where['area_id']=$mer_info['province_id'];
                $mer_info['provice']=D('Area')->where($where)->getfield('area_name');
                $where['area_id']=$mer_info['city_id'];                
                $mer_info['city']=D('Area')->where($where)->getfield('area_name');
                $where['area_id']=$mer_info['area_id'];                
                $mer_info['area']=D('Area')->where($where)->getfield('area_name');
            }
            $this->assign('mer_info',$mer_info);
        }
        $gift_detail = $gift_detail['detail'];
        if ($this->merchant_session) {
            $where = array('gift_id'=>$gift_id,'mer_id'=>$this->merchant_session['mer_id']);
        }else{
            $where = array('gift_id'=>$gift_id,'uid'=>$this->user_session['uid']);
        }
        $order_sum = M('Gift_order')->where($where)->sum('num');
        if ($gift_detail['exchange_limit_num'] && $gift_detail['exchange_limit_num'] > 0) {
            $exchange_limit_num = $gift_detail['exchange_limit_num'] - $order_sum;
            if ($exchange_limit_num <= 0) {
                $gift_detail['exchange_limit_num'] = 0;
            } else {
                $gift_detail['exchange_limit_num'] = $exchange_limit_num;
            }
        }
        $gift_detail['specification'] = explode("\r\n" , $gift_detail['specification']);
        $this->assign('gift_detail' , $gift_detail);
        $this->display();
    }

    public function fast_gift(){
        if(empty($this->user_session) && empty($this->merchant_session)){
            $this->error_tips('请先进行登录！', U('Login/index'));
        }

        $order = $_GET['order'];
        $database_gift = D('Gift');

        if ($this->merchant_session) {
            $now_user['now_money'] = $this->merchant_session['money'];
            $now_user['score_count'] = $this->merchant_session['system_score'];
        }else{
            $database_user = D('User');
            $now_user = $database_user->get_user($this->user_session['uid']);
        }

         $this->assign('now_user' , $now_user);

        $gift_condition['is_del'] = 0;
        $gift_condition['status'] = 1;
        //$gift_condition['exchange_type'] = 0;
        $gift_condition['payment_pure_integral'] = array('lt',$now_user['score_count']);
        $gift_condition['payment_integral'] = array('lt',$now_user['score_count']);
        if(!empty($order)){
            switch($order){
                case 'integral_desc':
                    $order = 'exchange_type asc,payment_pure_integral desc';
                    break;
                case 'integral_asc':
                    $order = 'exchange_type asc,payment_pure_integral asc';
                    break;
                default:
                    $order = 'sort desc';
                    break;
            }
        }



        $fast_gift_list = $database_gift->gift_page_list($gift_condition , true , $order , 9999);
        $this->assign('fast_gift_list' , $fast_gift_list['list']);
        $this->display();
    }

    public function order(){
        if(empty($this->user_session) && empty($this->merchant_session)){
            $this->error_tips('请先进行登录！', U('Login/index'));
        }

        $gift_id = $_GET['gift_id'] + 0;
        $adress_id = $_GET['adress_id'] + 0;
        $pick_id = $_GET['pick_id'] + 0;

        $database_gift = D('Gift');

        $gift_condition['is_del'] = 0;
        $gift_condition['status'] = 1;
        $gift_condition['gift_id'] = $gift_id;

        $gift_detail = $database_gift->gift_detail($gift_condition);

        if ($this->merchant_session) {
            $now_user['now_money'] = $this->merchant_session['money'];
            $now_user['score_count'] = $this->merchant_session['system_score'];
            $now_user['mer_id'] = $this->merchant_session['mer_id'];
        }else{
            $database_user_adress = D('User_adress');
            $now_user_adress = $database_user_adress->get_one_adress($this->user_session['uid'] , $adress_id);
            $database_user = D('User');
            $now_user = $database_user->get_user($this->user_session['uid']);
        }

        //获取平台配送设置
        $plat_deliver = M('Gift_plat_deliver')->where(array('id'=>2))->find();
        $userLocationLat = $plat_deliver['lat'];
        $userLocationLong = $plat_deliver['long'];

        $address_where = array('uid' => $this->user_session['uid']);
        if($_GET['adress_id']){
            $address_where['adress_id'] = $_GET['adress_id'];
        }

        $user_address_temp = D('User_adress')->field(true)->where($address_where)->order('`default` DESC,`adress_id` DESC')->select();
        $user_adress = array();
        $distance_array = array();
        $user_address_list = array();
        $is_adress = false;


        $plat_deliver['delivery_range_polygon'] = substr($plat_deliver['delivery_range_polygon'], 9, strlen($plat_deliver['delivery_range_polygon']) - 11);
        $lngLatData = explode(',', $plat_deliver['delivery_range_polygon']);
        array_pop($lngLatData);
        $lngLats = array();
        foreach ($lngLatData as $lnglat) {
            $lng_lat = explode(' ', $lnglat);
            $lngLats[] = array('lng' => $lng_lat[0], 'lat' => $lng_lat[1]);
        }
        $plat_deliver['delivery_range_polygon'] = $lngLats ? array($lngLats) : '';
        //dump($plat_deliver['delivery_range_polygon']);exit;
        foreach ($user_address_temp as $address) {
            $address['distance'] = getDistance($address['latitude'], $address['longitude'], $userLocationLat, $userLocationLong);
            if ($plat_deliver['delivery_range_type'] == 0) {
                $distance = getDistance($address['latitude'], $address['longitude'], $plat_deliver['lat'], $plat_deliver['long']);
                $distance = $distance / 1000;
                
                if ($plat_deliver['delivery_radius'] < $distance) {
                    continue;
                }
            } else {
                if ($plat_deliver['delivery_range_polygon']) {
                    if (!isPtInPoly($address['longitude'], $address['latitude'], $plat_deliver['delivery_range_polygon'])) {
                        continue;
                    }
                } else {
                    continue;
                }
            }
            $is_adress = true;
            if ($adress_id == $address['adress_id']) {
                $user_adress = $address;
                break;
            }

            if(empty($adress_id) && $address['default']){
                $user_adress = $address;
                $user_address_list[] = $address;
                break;
            }
            $distance_array[] = $address['distance'];

            $user_address_list[] = $address;
        }

        $this->assign('is_adress', $is_adress);
        
        //dump($now_user_adress);exit;
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
			
			$now_user_adress = $user_adress;
        }

        //计算配送费
        if ($user_adress) {
            $distance = 0;
            if (C('config.is_riding_distance')) {
                import('@.ORG.longlat');
                $longlat_class = new longlat();
                $distance = $longlat_class->getRidingDistance($plat_deliver['lat'], $plat_deliver['long'], $userLocationLat, $userLocationLong);
                //if($distance == -1) $this->error_tips('请求不到您的具体定位，请重试！');
            }

            $distance = getDistance($plat_deliver['lat'], $plat_deliver['long'], $user_adress['latitude'], $user_adress['longitude']);
            $distance = $distance / 1000;

            $pass_distance = $distance > $plat_deliver['s_basic_distance'] ? floatval($distance - $plat_deliver['s_basic_distance']) : 0;

            // 配送配费折扣
            $plat_deliver['delivery_fee'] = $plat_deliver['s_delivery_fee'];
            $plat_deliver['delivery_fee'] += round($pass_distance * $plat_deliver['s_per_km_price'], 2);
            $plat_deliver['delivery_fee_old'] = $plat_deliver['delivery_fee'];

            $pass_distance = $distance > $plat_deliver['s_basic_distance2'] ? floatval($distance - $plat_deliver['basic_distance2']) : 0;
            $plat_deliver['delivery_fee2'] = $plat_deliver['s_delivery_fee2'];
            $plat_deliver['delivery_fee2'] += round($pass_distance * $plat_deliver['s_per_km_price2'], 2);
            $plat_deliver['delivery_fee2_old'] = $plat_deliver['delivery_fee2'];

            $pass_distance = $distance > $plat_deliver['s_basic_distance3'] ? floatval($distance - $plat_deliver['basic_distance3']) : 0;
            $plat_deliver['delivery_fee3'] = $plat_deliver['s_delivery_fee3'];
            $plat_deliver['delivery_fee3'] += round($pass_distance * $plat_deliver['s_per_km_price3'], 2);
            $plat_deliver['delivery_fee3_old'] = $plat_deliver['delivery_fee3'];
        }

        //平台配送时间戳
        $date_list = D('Shop_goods')->get_plat_select_time($plat_deliver);
        //dump($date_list);exit;
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
        //dump($d_list);exit;
        if (empty($d_list) && !in_array(1, $gift_detail['detail']['deliver_type'])) {
            $this->error_tips('抱歉,当前暂无可配送的预计送达时间');
        }
        //dump($d_list);exit;
        $this->assign('dates', $d_list);

        $gift_detail = $gift_detail['detail'];
        if($gift_detail['exchange_type'] == 0){
            if($now_user['score_count'] < $gift_detail['payment_pure_integral']){
                $this->error_tips(''.$this->config['score_name'].'不足，暂时无法兑换！');
            }
        }elseif($gift_detail['exchange_type'] == 1){
            if($now_user['score_count'] < $gift_detail['payment_integral']){
                $this->error_tips($this->config['score_name'].'不足，暂时无法兑换！');
            }
        }

        if(!$gift_detail['status']){
            $this->error_tips('该礼品不存在！');
        }
		
		$surplus_count = $gift_detail['sku'] - $gift_detail['sale_count'];
        if($surplus_count <= 0){
             $this->error_tips('该礼品已经兑换完了！');
        }
		
        if($gift_detail['mer_id']>0){
            $mer_info=M('Merchant')->where(array('mer_id'=>$gift_detail['mer_id']))->field('name as mer_name,phone')->find();
            $gift_detail=array_merge($gift_detail,(array)$mer_info);
        }

        // 获取最近自提点
        $gift_pick_address = D('Gift_pick_address')->get_one_pick($pick_id);
        if ($gift_pick_address && $gift_pick_address['distance'] != -1) {
            $gift_pick_address['distance'] = $this->wapFriendRange($gift_pick_address['distance']);
        }
        $now_delivery_type = 0;
        if (in_array(0,$gift_detail['deliver_type'])) {
            $now_delivery_type = 0;
        } elseif (in_array(1,$gift_detail['deliver_type'])) {
            $now_delivery_type = 1;
        } elseif (in_array(2,$gift_detail['deliver_type']) && 1==$this->config['open_plat_deliver']) {
            $now_delivery_type = 2;
        }
        $this->assign('now_delivery_type',$now_delivery_type);

		$delivery_type = $gift_detail['deliver_type'];
        if ($this->merchant_session) {
            $where = array('gift_id'=>$gift_id,'mer_id'=>$this->merchant_session['mer_id']);
        }else{
            $where = array('gift_id'=>$gift_id,'uid'=>$this->user_session['uid']);
        }
        $order_sum = M('Gift_order')->where($where)->sum('num');
        if ($gift_detail['exchange_limit_num'] && $gift_detail['exchange_limit_num'] > 0) {
            $exchange_limit_num = $gift_detail['exchange_limit_num'] - $order_sum;
            if ($exchange_limit_num <= 0) {
                $gift_detail['exchange_limit_num'] = 0;
            } else {
                $gift_detail['exchange_limit_num'] = $exchange_limit_num;
            }
        }

        
        $this->assign('gift_pick_address',$gift_pick_address);
        $this->assign('delivery_type',$delivery_type);
        $this->assign('gift_detail',$gift_detail);
        $this->assign('now_user_adress' , $now_user_adress);
        $this->display();
    }
    // 自提点
    public function order_adress()
    {
        $pick_id = $_GET['pick_id'] + 0;
        $pick_list = D('Gift_pick_address')->get_pick_address(array('is_open'=>1));
        $now_pick = D('Gift_pick_address')->get_one_pick($pick_id);
        if (! empty($_GET['gift_id'])) {
            $select_url = 'Gift/order';
        }
        $param = $_GET;
        foreach ($pick_list as &$value) {
            $value['distance'] = $value['distance'] != -1 ? $this->wapFriendRange($value['distance']) : -1;
            $param['pick_id'] = $value['pick_id'];
            $value['select_url'] = U($select_url,$param);
        }
        $this->assign('pick_list',$pick_list);
        $this->assign('back_url',U($select_url,$_GET));
        $this->assign('now_pick',$now_pick);
        $this->display();
    }

    public function adress_map(){
        $this->display();
    }

    public function get_route(){
        $long_lat = D('User_long_lat')->getLocation($_SESSION['openid']);
        $this->assign('long_lat',$long_lat);

        $now_store = D('Gift_order')->where(array('order'=>$_GET['store_id']))->find();
        if(empty($now_store)){
            $this->error_tips('该地址不存在！');
        }
        $this->assign('now_store',$now_store);

        $this->assign('no_gotop',true);

        $this->display(); 
    }


    public function success_pointer(){
        $wap_index_top_adver = D('Adver')->get_adver_by_key('wap_score_get',5);
        $this->assign('adver',$wap_index_top_adver);
        $this->display();
    }

    public function buy(){
        if(IS_POST){
			if(empty($this->user_session) && empty($this->merchant_session)){
                $this->assign('jumpUrl',U('Index/Login/index'));
                $this->error('请先登录！');
            }
  
            $database_gift = D('Gift');
            $database_gift_order = D('Gift_order');
            $gift_condition['is_del'] = 0;
            $gift_condition['status'] = 1;

            $gift_id = $_POST['gift_id'] + 0;
            $gift_condition['gift_id'] = $gift_id;
            $gift_detail = $database_gift->gift_detail($gift_condition);
            $gift_detail = $gift_detail['detail'];

            if(empty($gift_detail['status'])){
                $this->error_tips('该礼品不存在！');
            }

            if ($this->merchant_session) {
                $now_user['score_count'] = $this->merchant_session['system_score'];
            }else{
                $database_user = D('User');
                $now_user = $database_user->get_user($this->user_session['uid']);
            }

            if($_POST['exchange_type'] == 0){
                if($now_user['score_count'] < $gift_detail['payment_pure_integral']){
                    $this->error_tips(''.$this->config['score_name'].'不足，暂时无法兑换！');
                }
            }elseif($_POST['exchange_type'] == 1){
                if($now_user['score_count'] < $gift_detail['payment_integral']){
                    $this->error_tips(''.$this->config['score_name'].'不足，暂时无法兑换！');
                }
            }

            /* if($now_user['score_count'] < $gift_detail['payment_integral']){
                $this->error_tips('积分不足，暂时无法兑换！');
            } */

            if ($this->merchant_session) { 
                $where = array('gift_id'=>$gift_id,'mer_id'=>$this->merchant_session['mer_id']);
            }else{
                $where = array('gift_id'=>$gift_id,'uid'=>$this->user_session['uid']);
            }
            $order_sum = $database_gift_order->where($where)->sum('num');

            if(($gift_detail['exchange_limit_num'] > 0) && ($gift_detail['exchange_limit_num'] < $order_sum + $_POST['num'])){
                $this->error_tips('该礼品您每人限定购买'.$gift_detail['exchange_limit_num'].'件,您现在只可购买'.($gift_detail['exchange_limit_num'] - $order_sum.'件'));
            }

            $_POST['is_source'] = 1;
            if ($this->merchant_session) { 
                $result = $database_gift_order->save_post_form($gift_detail,$this->merchant_session['mer_id'] , 0 , 2);
            }else{
                $result = $database_gift_order->save_post_form($gift_detail,$this->user_session['uid'] , 0);
            }
           
            if($result['error']==1){
                $this->error_tips($result['msg']);
            }

            if(!empty($result['flag'])){
                $this->redirect('chk_buy',array('order_id'=>$result['order_id'],'num'=>$_POST['num']));
                exit;
            }

            $this->redirect(U('success_order'),array('order_id'=>$result['order_id']));
        }else{
            $this->error_tips('访问页面有误！');
        }
    }


    public function pay_order(){
        if(empty($this->user_session) && empty($this->merchant_session)){
            $this->assign('jumpUrl',U('Index/Login/index'));
            $this->error('请先登录！');
        }

        $database_gift = D('Gift');
        $database_gift_order = D('Gift_order');

        $order_id = $_GET['order_id'] + 0;
        if(!$order_id){
            $this->error_tips('传递参数有误！');
        }
        if ($this->merchant_session) { 
            $now_order = $database_gift_order->get_pay_order($this->merchant_session['mer_id'],$order_id,2);
        }else{
            $now_order = $database_gift_order->get_pay_order($this->user_session['uid'],$order_id);
        }
        $now_order = $now_order['order_info'];
        $gift_condition['gift_id'] = $now_order['gift_id'];
        $gift_detail = $database_gift->gift_detail($gift_condition);
        if(empty($gift_detail['status'])){
            $this->error_tips('该礼品不存在！');
        }
        if ($this->merchant_session) { 
            $result = $database_gift_order->save_post_form($gift_detail['detail'],$this->merchant_session['mer_id'] , $order_id , 2);
        }else{
            $result = $database_gift_order->save_post_form($gift_detail['detail'],$this->user_session['uid'] , $order_id);
        }

        if(!empty($result['flag'])){
            $this->redirect('chk_buy',array('order_id'=>$result['order_id'],'num'=>$result['num']));
            exit;
        }

        if($result['error']==1){
            $this->error_tips($result['msg']);
        }
        if(!$result['error']){
            $this->redirect($result['url']);
        }else{
            $this->error('数据处理有误！');
        }
    }

    public function chk_buy(){
        $order_id = $_GET['order_id'] + 0;

        if(empty($order_id)){
            $this->error_tips('传递参数有误！');
        }

        $gift_order_condition['order_id'] = $order_id;
        $database_gift_order = D('Gift_order');
        if ($this->merchant_session) { 
            $now_order = $database_gift_order->get_order_detail_by_id($this->merchant_session['mer_id'] , $order_id ,true , true ,2);
        }else{
            $now_order = $database_gift_order->get_order_detail_by_id($this->user_session['uid'] , $order_id);
        }

        if(isset($now_order['remain_num']) && ($now_order['remain_num'] <= 0)){
            $this->error_tips('该礼品您每人限定购买'.$now_order['exchange_limit_num'].'件');
        }

        if(empty($now_order)){
            $this->error_tips('该订单不存在！');
        }

        $this->assign('now_order' , $now_order);
        $this->display();
    }


    public function submit(){
        header('Content-Type: application/json; charset=utf-8');
        $quantity = isset($_GET['q']) ? intval($_GET['q']) : intval($_POST['q']);
        $order_id = $_GET['order_id'] + 0;
        $databse_gift_order = D('Gift_order');

        if($quantity < 1){
            exit(json_encode(array('status'=>0,'info'=>'最少需要参与1次')));
        }else if($quantity > 200){
            exit(json_encode(array('status'=>0,'info'=>'一次性最多购买200次，请分批次购买')));
        }
        if(empty($this->user_session)){
            exit(json_encode(array('status'=>-3,'info'=>'请先进行登录')));
        }

        $now_order = $databse_gift_order->get_order_detail_by_id($this->user_session['uid'],$order_id);


        $database_gift = D('Gift');
        $gift_id = $now_order['gift_id'];
        $gift_condition['gift_id'] = $gift_id;
        $gift_condition['is_del'] = 0;
        $gift_condition['status'] = 1;

        $now_gift = $database_gift->gift_detail($gift_condition);
        if(empty($now_gift['status'])){
            exit(json_encode(array('status'=>0,'info'=>'该礼品不存在')));
        }

        $now_gift = $now_gift['detail'];
        $surplus_count = $now_gift['sku'] - $now_gift['sale_count'];
        if($surplus_count <= 0){
            exit(json_encode(array('status'=>-2,'info'=>'该礼品已经结束，点击确定按钮 刷新页面！')));
        }
        if($surplus_count < $quantity){
            if($exchange_limit_num = $now_gift['exchange_limit_num']){
                exit(json_encode(array('status'=>-1,'info'=>'您最多只能参加 '.($exchange_limit_num).' 次','count'=>$surplus_count)));
            }else{
                exit(json_encode(array('status'=>-1,'info'=>'您最多只能参加 '.($surplus_count).' 次','count'=>$surplus_count)));
            }
        }

        $now_gift['money'] = floatval($now_gift['payment_money']);

        $now_user = D('User')->get_user($this->user_session['uid']);
        if(empty($now_user)){
            exit(json_encode(array('status'=>0,'info'=>'未获取到您的帐号信息，请重试')));
        }
        if($now_order['is_pick_in_store'] != 1 && !(M('User_adress')->where(array('uid'=>$this->user_session['uid']))->find())){
            exit(json_encode(array('status'=>-5,'info'=>'您必须添加一个收货地址才能进行兑换')));
        }

        if($now_order['exchange_type'] == 0){
            $use_score = $now_order['payment_pure_integral'] * $quantity;
            if($now_user['score_count'] < $use_score){
                exit(json_encode(array('status'=>0,'info'=>'您的帐户'.$this->config['score_name'].'为 '. $now_user['score_count'].' ，不足以兑换此奖品')));
            }

        }elseif($now_order['exchange_type'] == 1){
            $use_score = $now_order['payment_integral'] * $now_order['num'];
            if($now_user['score_count'] < $use_score){
                exit(json_encode(array('status'=>0,'info'=>'您的帐户'.$this->config['score_name'].'为 '. $now_user['score_count'].' ，不足以兑换此奖品')));
            }

            $use_money = $now_order['payment_money'] * $quantity;
            if($now_user['now_money'] < $use_money){
                exit(json_encode(array('status'=>-4,'info'=>'您的帐户余额为 <span>'.$now_user['now_money'].'</span> '.C('config.Currency_txt').'，请先充值帐户余额','recharge'=>$use_money-$now_user['now_money'])));
            }

        }

        $order_param['order_id'] = $order_id;
        $order_param['num'] = $quantity;
        $databse_gift_order->after_pay($order_param);

        if($_GET['source'] == 'wap'){
            exit(json_encode(array('status'=>-6,'info'=>'兑换成功')));
        }else{
            $this->redirect('success_order',array('order_id'=>$order_id));
        }
    }

    public function success_order(){
        $order_id = $_GET['order_id'] + 0;
        if(empty($order_id)){
            $this->error_tips('传递参数有误！~~~');
        }

        $database_gift_order = D('Gift_order');
        if ($this->merchant_session) { 
            $now_gift_order = $database_gift_order->get_order_detail_by_id($this->merchant_session['mer_id'] , $order_id ,true , true ,2);
        }else{
            $now_gift_order = $database_gift_order->get_order_detail_by_id($this->user_session['uid'] , $order_id , true);
        }
        $this->assign('now_gift_order' , $now_gift_order);
        $this->display();
    }


    public function ajax_gift_list(){
        if(IS_POST){
            $cat_fid  = $_POST['cat_fid'] + 0;
            // if(!isset($cat_fid) && empty($cat_fid)){
            //     $this->error_tips('传递参数有误！');
            // }
            $keyword = trim($_POST['keyword']);


            $database_gift = D('Gift');
            $gift_condition['is_del'] = 0;
            $gift_condition['status'] = 1;
            if($cat_fid){
                $gift_condition['cat_fid'] = $cat_fid;
            }
            if($keyword){
                $gift_condition['gift_name'] = ['like', '%'.$keyword.'%'];
            }
            $gift_list = $database_gift->gift_page_list($gift_condition , true ,'sort desc', 8);

            if($gift_list['status']){
                exit(json_encode(array('status'=>1,'gift_list'=>$gift_list['list'])));
            }else{
                exit(json_encode(array('status'=>0,'gift_list'=>$gift_list['list'])));
            }

        }else{
            $this->error_tips('访问页面有误！~~~');
        }
    }


    public function chk_order(){
        $order_id = $_GET['order_id'] + 0;

        if(!$order_id){
            $this->error_tips('传递参数有误！');
        }

        $database_gift_order = D('Gift_order');
        $where['order_id'] = $order_id;
        $insert_id = $database_gift_order->where($where)->setField('status',2);

        if($insert_id){
            D("System_order")->save_order_status('gift', $order_id, 6,2);
            $this->assign('lastPageNeedReload' , 1);
            $this->success_tips('确认收货成功！',U('My/gift_order',array('order_id'=>$order_id)));
        }else{
            $this->assign('lastPageNeedReload' , 1);
            $this->error_tips('确认收货失败！',U('My/gift_order',array('order_id'=>$order_id)));
        }
    }

    public function search(){
        $this->display();
    }

}
?>