<?php

class My_cardAction extends BaseAction
{
    public $now_user;
    public $mer_id;

    public function __construct()
    {
        parent::__construct();
        if (empty($this->user_session) && empty($_GET['all_mer'])) {
			 if(IS_AJAX){
				$location_param['referer'] = urlencode($_POST['referer']);
				echo json_encode(array('error_code'=>7,'msg'=>'请先进行登录！','url'=>U('Login/index', $location_param)));die;
			}
        }
        $now_user = D('User')->get_user($this->user_session['uid']);
        $mer_id = $_GET['mer_id'];

        $this->mer_id = $mer_id;
        if (empty($now_user)&& empty($_GET['all_mer'])) {
            session('user', null);
            $this->error_tips('未获取到您的帐号信息，请重新登录！', U('Login/index'));
        }
        $now_user['now_money'] = floatval($now_user['now_money']);
        $now_user['now_money_two'] = number_format(floatval($now_user['now_money']), 2);
        $this->now_user = $now_user;
        $this->assign('now_user', $now_user);
    }

    //商家信息
    private function get_merchant_info($mer_id)
    {
        $now_merchant = D('Merchant')->get_info($mer_id);
        if (empty($now_merchant)) {
            $this->error_tips('此商家不存在');
        }
        $this->assign('now_merchant', $now_merchant);
        return $now_merchant;
    }

    public function merchant_card()
    {
		if(empty($this->now_user['phone'])){
			$this->error_tips('尊敬的用户，为了提供更好的服务，请您在领取会员卡之前先绑定手机号码！',U('My/bind_user',array('referer'=>urlencode(U('merchant_card',$_GET)))));
		}

        //商家信息
        $now_merchant = $this->get_merchant_info($_GET['mer_id']);
        $now_card = M(_view('Card_new'))->where(array('mer_id' => $_GET['mer_id']))->find();
		
		$is_exchange_card = $_GET['is_exchange_card']?:0;
		$cardmi = $_GET['cardmi']?:'';
        $mer_id = $_GET['mer_id']?:'';
        $uid = $this->now_user['uid'];
        
        $cardmi_success = 0;
		if($is_exchange_card>0&&$cardmi){
            //查询是否有实体卡
            $user_card = D('Card_userlist')->where(['mer_id'=>$mer_id,'uid'=>$uid,'card_id'=>$now_card['card_id']])->find();
            //查询卡密是否存在
            $card_mi = D('Merchant_physical_card')->where(['mer_id'=>$mer_id,'carmi'=>$cardmi])->find();
            if(!$card_mi){
                $this->error_tips('卡密不存在！');
            }
            if(($card_mi['money']-$card_mi['use_money'])<=0){
                $this->error_tips('该卡金额已用完，无法充值！');
            }
            if($card_mi['uid']&&$card_mi['uid']!=$uid){
                $this->error_tips('无法使用他人卡密充值！');
            }
            //扫码兑换实体卡余额的自动创建商家会员卡
            if(!$user_card){
                $user_card_data = [
                    'card_id' => $now_card['card_id'],
                    'mer_id' => $_GET['mer_id'],
                    'uid' => $this->now_user['uid']
                ];
                $card_user_id = D('Card_userlist')->add($user_card_data);
            }else{
                $card_user_id = $user_card['id'];
            }
            $re = D('Card_userlist')->where(['id'=>$card_user_id])->setInc('card_money',($card_mi['money']-$card_mi['use_money']));
            if($re){
                D('Merchant_physical_card')->addPhysicalCardOrder(($card_mi['money']-$card_mi['use_money']),$card_mi['id'],'merchant_card',$mer_id,0,0,$uid);
                $cardmi_success = 1;
            }
        }
		$this->assign('cardmi_success',$cardmi_success);
		
        $card_info = D('Card_new')->get_card_by_uid_and_mer_id($this->now_user['uid'], $this->mer_id);
        $now_user_card = M('Card_userlist')->where(array('uid'=>$this->now_user['uid'],'mer_id'=> $_GET['mer_id']))->find();

        if(!empty($now_user_card) && $now_user_card['status']==0){
            $this->error_tips('您的会员卡不能使用！');
        }
        $this->assign('now_card', $now_card);
        if (empty($card_info['id'])) {
            if ($_GET['id']) {
                // 扫会员卡二维码进入
                $user_card = M('Card_userlist')->where(array('id'=>$_GET['id']))->find();
                $result = D('Card_new')->auto_reg_or_bind($user_card['mer_id'],$this->now_user['openid'],$user_card['id'],1);
                if (!$result['error_code']) {
                    $card_info = D('Card_new')->get_card_by_uid_and_mer_id($this->now_user['uid'], $this->mer_id);
                } else {
                    $this->error_tips($result['msg'],U('Home/index'));
                }
            } elseif ($now_card['self_get'] || $now_card['auto_get']) {
                $result = D('Card_new')->auto_get($this->now_user['uid'], $_GET['mer_id']);
                if (!$result['error_code']) {
                    $card_info = D('Card_new')->get_card_by_uid_and_mer_id($this->now_user['uid'], $_GET['mer_id']);
                } else {
                    $this->error_tips($result['msg']);
                }
            } else {
                $this->error_tips('您没有会员卡，请联系商家开通！');
            }

        } elseif ($card_info['status'] == 0) {
            $this->error_tips('您的会员卡不能使用！');
        }

		if(!empty($card_info['diybg'])){
           $card_info['style'] = 'background:url('.replace_file_domain($card_info['diybg']).')center; background-size: 100%';
		}elseif(!empty($card_info['bg'])){
            $card_info['style'] ='background:url('.$card_info['bg'].')center; background-size: 100%';
		}

        $database_merchant = D('Merchant');
        $merchant = $database_merchant->field('menus')->where(array('mer_id' => $_GET['mer_id']))->find();
        $merchant_menus = $merchant['menus'];
        $merchant = explode(',',$merchant_menus);

        if ($merchant_menus) {
            if (!in_array(10037,$merchant)) {
                $config = $this->config;
                $config['merchant_integral_mall_control'] = 0; // 积分商城
                $this->assign('config', $config);
            }
        }

        //优惠券地址
        $couponUrl = C('config.site_url') . D('House_village')->base_url . 'pages/coupon/storeCoupon?mer_id=' . $_GET['mer_id'];
        $this->assign('couponUrl', $couponUrl);

        $card_info['have_deposita']=empty(C('customization.deposita'))?0:C('customization.deposita');
        $_SESSION['user']['avatar'] = str_replace('http:','https:',$_SESSION['user']['avatar']);
        $this->assign('card', $card_info);
        $this->display();

    }

    public function ajax_cancel_wx(){
        $card_info = D('Card_new')->get_card_by_uid_and_mer_id($this->now_user['uid'], $this->mer_id);
        M('Card_userlist')->where(array('id'=>$card_info['id']))->setField('cancel_wx',1);
        if($card_info['wx_card_code']==''){
            $this->error('');
        }else{
            $this->success($card_info['wx_card_code']);
        }
    }

    public function cardqrcode()
    {
        import('@.ORG.phpqrcode');
        QRcode::png($_SESSION['tmp_card_new'], false, 2, 8, 2);
    }


    public function merchant_store()
    {
        //商家信息
        $now_merchant = $this->get_merchant_info($_GET['mer_id']);
        //店铺列表
        $store_list = D('Merchant_store')->get_store_list_by_merId($now_merchant['mer_id']);
		if($store_list){
	        foreach($store_list as $k=>&$v){
				if($_GET['shop'] && !$v['have_shop']){
					unset($store_list[$k]);
				}
				$v['url']	=	U('Merchant/shop',array('store_id'=>$v['store_id']));
	        }
        }
        $this->assign('store_list', $store_list);
        // dump($store_list);
        $this->display();
    }

    public function merchant_right()
    {
        //商家信息
        $now_merchant = $this->get_merchant_info($_GET['mer_id']);
        $card_info = D('Card_new')->get_card_by_uid_and_mer_id($this->now_user['uid'], $_GET['mer_id']);
        $card_info['info'] = str_replace(PHP_EOL, '</br>', $card_info['info']);
        $this->assign('card_info', $card_info);
        $this->display();
    }

    //积分说明
    public function merchant_point()
    {
        //商家信息
        $now_merchant = $this->get_merchant_info($_GET['mer_id']);
        $card_info = D('Card_new')->get_card_by_uid_and_mer_id($this->now_user['uid'], $_GET['mer_id']);
        $card_info['score_des'] = str_replace(PHP_EOL, '</br>', $card_info['score_des']);
        $this->assign('card_info', $card_info);
        $this->display();
    }

    public function merchant_prepay()
    {
        //充值入口来源
        $source = $_GET['source'] ?? 'wap';
        //商家信息
        $now_merchant = $this->get_merchant_info($_GET['mer_id']);
		
		//商家会员卡
        $now_merchant_card = M(_view('Card_new'))->where(array('mer_id' => $now_merchant['mer_id']))->find();

        $recharge_rule = json_encode(unserialize($now_merchant_card['recharge_rule']));
        $now_merchant_card['recharge_rule'] = $recharge_rule != 'null' ? $recharge_rule: json_encode([]);
        $this->assign('now_merchant_card', $now_merchant_card);

		//用户卡信息
		$card_info = D('Card_new')->get_card_by_uid_and_mer_id($this->now_user['uid'], $_GET['mer_id']);
		$this->check_merchant_card($card_info, $now_merchant_card);
		if($card_info){
			$card_info['recharge_suggest_array'] = $card_info['recharge_suggest'] ? explode(',', $card_info['recharge_suggest']) : [];
            $card_info['recharge_suggest_array'] = array_filter($card_info['recharge_suggest_array']);
			$card_info['recharge_des'] = str_replace(PHP_EOL, '</br>', $card_info['recharge_des']);
		}
        $this->assign('card_info', $card_info);
//        $custom = $source=='pc' && $card_info['recharge_suggest_array'] ? false : true;
        $custom = isset($card_info['recharge_suggest_select']) && $card_info['recharge_suggest_select'] == 1 ? false : true;
        $this->assign('custom', $custom);//是否允许自定义充值金额

        if(C('config.open_merchant_meal_card')){
            $this->display('merchant_meal_card_prepay');
        }else{
            $this->display();
        }
    }

    //平台饭票充值
    public function plat_prepay(){
        $meal_card = D('Meal_card')->get_use_one($_GET['mer_id']);

            $agreement = C('config.meal_card_agreement');
            $this->assign('agreement',$agreement);

            $adver = D('Adver')->get_adver_by_key('meal_card_top');
            $this->assign('adver',$adver);
        $this->display();
    }

    public function meal_card_list(){
        $this->display();
    }
	
	//检查卡有效
	private function check_merchant_card($card_info, $now_card){
		if (empty($card_info['id'])) {
            if ($now_card['self_get'] || $now_card['auto_get']) {
                $result = D('Card_new')->auto_get($this->now_user['uid'], $now_card['mer_id']);
                if (!$result['error_code']) {
                    $card_info = D('Card_new')->get_card_by_uid_and_mer_id($this->now_user['uid'], $now_card['mer_id']);
                } else {
                    $this->error_tips($result['msg']);
                }
            } else {
                $this->error_tips('您没有会员卡，请联系商家开通！');
            }

        } elseif ($card_info['status'] == 0) {
            $this->error_tips('您的会员卡不能使用！');
        }
		
		return $card_info ? : [];
	}

    public function merchant_recharge()
    {
        //商家信息
        $now_merchant = $this->get_merchant_info($_GET['mer_id']);

        $money = floatval($_GET['money']);
        if (empty($money)) {
            $this->error_tips('请输入正确的充值金额');
        }

        $now_merchant_card = M('Card_new')->where(array('mer_id' => $now_merchant['mer_id']))->find();
        $now_merchant_card['recharge_rule'] =unserialize($now_merchant_card['recharge_rule']);
        $data_card_new_recharge_order['mer_id'] = $now_merchant['mer_id'];
        $data_card_new_recharge_order['uid'] = $this->user_session['uid'];
        $data_card_new_recharge_order['money'] = $money;
        $data_card_new_recharge_order['give_money']=  0;
        $data_card_new_recharge_order['give_score']=  0;
        $data_card_new_recharge_order['meal_card_id']=  intval($_GET['meal_card_id']);
        $data_card_new_recharge_order['store_id']=  intval($_GET['store_id']);
        if($now_merchant_card['recharge_rule']){
            foreach ($now_merchant_card['recharge_rule'] as $v) {
                if($v['count'] <= $money){
                    $data_card_new_recharge_order['give_money']  =$v['back_money'];
                    $data_card_new_recharge_order['give_score']  =$v['back_score'];
                }
            }
        }
//        if ($now_merchant_card['recharge_count'] <= $money && $now_merchant_card['recharge_back_money'] > 0) {
//            $data_card_new_recharge_order['give_money'] = intval($money / $now_merchant_card['recharge_count']) * $now_merchant_card['recharge_back_money'];
//        }
//        if ($now_merchant_card['recharge_count'] <= $money && $now_merchant_card['recharge_back_score'] > 0) {
//            $data_card_new_recharge_order['give_score'] = intval($money / $now_merchant_card['recharge_count']) * $now_merchant_card['recharge_back_score'];
//        }
        $data_card_new_recharge_order['add_time'] = $_SERVER['REQUEST_TIME'];
        if ($order_id = M('Card_new_recharge_order')->data($data_card_new_recharge_order)->add()) {
            $pay_order_param = array(
                'business_type' => 'card_new_recharge',
                'business_id' => $order_id,
                'order_name' => '充值商家[' . $now_merchant['name'] . ']的会员卡余额',
                'uid' => $this->user_session['uid'],
                'total_money' => $money,
                'wx_cheap' => 0,
            );
            $plat_order_result = D('Plat_order')->add_order($pay_order_param);
            if ($plat_order_result['error_code']) {
                $this->error_tips($plat_order_result['error_msg']);
            } else {
                if(IS_AJAX){
					$this->success(array('order_id' => $plat_order_result['order_id'], 'type' => 'plat'));
				}else{
					redirect(U('Pay/check', array('order_id' => $plat_order_result['order_id'], 'type' => 'plat')));
				}
            }
        } else {
            $this->error_tips('订单创建失败，请重试。');
        }
    }

    public function merchant_coupon()
    {
        redirect(get_base_url("pages/coupon/storeCoupon?mer_id=" . $_GET['mer_id']));

        //商家信息
        $now_merchant = $this->get_merchant_info($_GET['mer_id']);
        $coupon_list = D('Card_new_coupon')->get_user_coupon_list($this->now_user['uid'], $_GET['mer_id']);

        $tmp = array();
        foreach ($coupon_list as $key => $v) {
            if (!empty($tmp[$v['is_use']][$v['coupon_id']])) {
                $tmp[$v['is_use']][$v['coupon_id']]['get_num']++;
            } else {
                $tmp[$v['is_use']][$v['coupon_id']] = $v;
                $tmp[$v['is_use']][$v['coupon_id']]['get_num'] = 1;
//                switch($v['type']){
//                    case 'all':
//                        $url = $this->config['site_url'].'/wap.php';
//                        break;
//                    case 'group':
//                        $url = $this->config['site_url'].'/wap.php?g=Wap&c=Group&a=index';
//                        break;
//                    case 'meal':
//                        $url = $this->config['site_url'].'/wap.php?g=Wap&c=Meal_list&a=index';
//                        break;
//                    case 'appoint':
//                        $url = $this->config['site_url'].'/wap.php?g=Wap&c=Appoint&a=index';
//                        break;
//                    case 'shop':
//                        $url = $this->config['site_url'].'/wap.php?g=Wap&c=Shop&a=index';
//                        break;
//                }
//                 $coupon['url'] = $url;
            }
        }
        $this->assign('coupon_list', $tmp);
        $this->display();
    }

    public function merchant_personal()
    {
        //商家信息
        if (IS_POST) {
           // if (M('User')->where(array('uid' => $this->now_user['uid']))->save($_POST)) {
            if (isset($_POST['add_time'])) {
                unset($_POST['add_time']);
            }
            unset($_POST['birthday']);
            M('User')->where(array('uid' => $this->now_user['uid']))->save($_POST);
            $this->success_tips('保存成功',U('merchant_card',array('mer_id'=>$_POST['mer_id'])));
//            } else {
//                $this->error_tips('保存失败');
//            }
        } else {
            $now_merchant = $this->get_merchant_info($_GET['mer_id']);
            $this->display();
        }
    }

    public function merchant_boundcard()
    {
        $now_merchant = $this->get_merchant_info($_GET['mer_id']);
        $card_info = D('Card_new')->get_card_by_uid_and_mer_id($this->now_user['uid'], $_GET['mer_id']);
        $this->assign('card_info', $card_info);
        if (IS_POST) {
            if (!empty($card_info['physical_id'])) {
                $this->error_tips('您已经绑定过实体卡了!');
            }
            if(M('Card_userlist')->where(['physical_id'=>$_POST['cardid']])->find()){
                $this->error_tips('卡号已使用');
            }
            if( D('Card_new')->add_pythsical_id($_POST['cardid'], $this->now_user['uid'], $card_info['id'])){
                $this->success_tips('保存成功');
            } else {
                $this->error_tips('保存失败');
            }

        }
        $this->display();
    }

    public function merchant_transrecord()
    {
        //商家信息
        $now_merchant = $this->get_merchant_info($_GET['mer_id']);
        $card_info = D('Card_new')->get_card_by_uid_and_mer_id($this->now_user['uid'], $_GET['mer_id']);
        $record = D('Card_new')->card_use_record($card_info['id'], 'all');

        $this->assign('record', $record['record']);
        $this->display();
    }
	
	public function merchant_transrecord_json(){
		$card_id_arr = D('Card_new')->get_card_by_uid($this->now_user['uid']);
		$record = D('Card_new')->meal_card_record($card_id_arr);
		echo json_encode($record['record']);
	}

    public function coupon_list(){
        $ticket = I('ticket', false);
        $device_id    =   I('Device-Id',false);
        if($ticket && $device_id){
            $info = ticket::get($ticket, $device_id, true);
            $now_user = D('User')->get_user($info['uid']);
        }
        $now_mer = M('Merchant_store')->where(array('store_id'=>$_POST['store_id']))->find();
        //$coupont_list = D('Card_new_coupon')->get_coupon_list(array('is_shop'=>1,'mer_id'=>$now_mer['mer_id'],'send_type'=>array('neq',1)));
        $where = array('mer_id'=>$now_mer['mer_id'],'status'=>array('gt',0),'end_time'=>array('gt',time()),'is_shop'=>1,'mer_id'=>$now_mer['mer_id'],'send_type'=>array('neq',1));
        $coupont_list =  M('Card_new_coupon')->where($where)
            ->order('status ASC,allow_new DESC,discount DESC')
            ->field('coupon_id,name,img,had_pull,num,limit,des,cate_name,cate_id,discount,order_money,start_time,end_time,status,allow_new,store_id')->select();

        $category_platform = D('Card_new_coupon')->cate_platform();
        $category = $category_platform['category'];
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
            $v['start_time'] = date('Y-m-d',$v['start_time']);
            $v['end_time'] = date('Y-m-d',$v['end_time']);

            $v['platform'] = unserialize($v['platform']);
            if(!empty($v['platform'])&& !in_array('app',$v['platform'])){
                continue;
            }

            if(!empty($now_user)){
                $count = M('Card_new_coupon_hadpull')->where(array('coupon_id'=>$v['coupon_id'],'uid'=>$now_user['uid']))->count();
                if($count==0 && $v['status']!=1){
                    continue;
                }

                if($count == 0 && $v['allow_new'] && !D('User')->check_new($now_user['uid'],'shop')){
                    continue;
                }
                if($count>0) {
                    $v['limit'] = $count;
                    $old_num++;
                }else if($v['status']==1 && $count==0){
                    $is_new_coupon =  true;
                }

            }

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

    public function merchant_coupon_list()
    {
		//商家信息
        if ($_GET['all_mer'] == 1) {
			$couponUrl = C('config.site_url') . D('House_village')->base_url . 'pages/coupon/index';
			redirect($couponUrl);
			
            $coupon_list_mer = D('Card_new_coupon')->get_coupon_list_by_merid($_GET['mer_id']);
        } else {
			$couponUrl = C('config.site_url') . D('House_village')->base_url . 'pages/coupon/storeCoupon?mer_id=' . $_GET['mer_id'];
			redirect($couponUrl);
		
            $now_merchant = $this->get_merchant_info($_GET['mer_id']);
            $coupon_list_mer = D('Card_new_coupon')->get_coupon_list_by_merid($_GET['mer_id']);
        }

        //屏蔽掉发券功能新客优惠券
        $activity = (new SystemSendCouponActivityModel)->getNowActivity(0, 'all');
        $activityCoupon = $activity['coupon_lists'] ?? '';
        $filterSystemCouponId = $filterMerchantCouponId = [];
        foreach ($activityCoupon as $c) {
            if ($c['type'] == 'plat' && !in_array($c['coupon_id'], $filterSystemCouponId)) {
                $filterSystemCouponId[] = $c['coupon_id'];
            }
            if ($c['type'] == 'mer' && !in_array($c['coupon_id'], $filterMerchantCouponId)) {
                $filterMerchantCouponId[] = $c['coupon_id'];
            }
        }

        //增加商家优惠券标识
        foreach($coupon_list_mer as $key=>$value) {
            $coupon_list_mer[$key]['coupon_type'] = 'mer';
            $coupon_list_mer[$key]['coupon_type_name'] = '商家券';

            $store = explode(',', $value['store_id']);
            if(count($store) == 1) { //单店铺
                $coupon_list_mer[$key]['jump_url'] = $this->config['site_url'] . D('House_village')->base_url . 'pages/store/homePage?store_id='.$value['store_id'];
            } else { //多店铺
                $coupon_list_mer[$key]['jump_url'] = $this->config['site_url'] . D('House_village')->base_url . 'pages/store/storeList?coupon_id='.$value['coupon_id'];
            }
        }

        if (!empty($this->now_user)) {
            $now_user_coupon = D('Card_new_coupon')->get_coupon_category_by_uid($this->now_user['uid']);
            foreach ($now_user_coupon as $v) {
                if (!empty($coupon_list_mer[$v['coupon_id']])) {
                    $coupon_list_mer[$v['coupon_id']]['selected'] = 1;
                }
            }
        }

        //平台优惠券
        $coupon  = D('System_coupon');
        $get_where = ['rand_send'=>0,'is_privileged'=>0,'is_hide'=>0,'is_partition'=>0];
        if(!empty($this->user_session)) {
            $now_user = D('User')->get_user($this->user_session['uid']);
            $get_where['city_id'] = [['eq',0], ['eq',$now_user['city_id']], 'or'];
        }
        $coupon_list_system = $coupon->get_coupon_list($get_where);

        foreach($coupon_list_system as $key=>$value) {
            $coupon_list_system[$key]['coupon_type'] = 'system';
            $coupon_list_system[$key]['coupon_type_name'] = '平台券';

            if($value['use_type'] == 1) {
                $store_ids =  M('System_coupon_store')->field('store_id')->where(['coupon_id'=>$value['coupon_id']])->select();
                if(count($store_ids) == 1) { //单店铺
                    $coupon_list_system[$key]['jump_url'] = $this->config['site_url'] . D('House_village')->base_url . 'pages/store/homePage?store_id='.$store_ids[0]['store_id'];
                } else { //多店铺
                    $coupon_list_system[$key]['jump_url'] = $this->config['site_url'] . D('House_village')->base_url . 'pages/store/storeList?coupon_id='.$value['coupon_id'].'&coupon_type=system';
                }
            } else {
                if($value['cate_name'] == 'village_group') { //跳转 社区团购首页
                    $coupon_list_system[$key]['jump_url'] = $this->config['site_url'] . '/wap.php?g=Wap&c=Village_group&a=index';
                } else {
                    $coupon_list_system[$key]['jump_url'] = $this->config['site_url'] . D('House_village')->base_url . 'pages/store/storeList?coupon_id='.$value['coupon_id'].'&coupon_type=system';
                }
            }
        }

        if(!empty($this->user_session)) {
            $now_user_coupon  = $coupon->get_coupon_category_by_phone($this->user_session['uid']);
            foreach($now_user_coupon as $v){
                if(!empty($coupon_list_system[$v['coupon_id']])) {
                    $coupon_list_system[$v['coupon_id']]['selected'] = 1;
                }
            }
        }

//        var_dump($coupon_list);exit();
//        var_dump($coupon_list_system);exit();

        foreach($coupon_list_mer as $key => $value) {
            if(in_array($value['coupon_id'],$filterMerchantCouponId)){
                continue;
            }
            $coupon_list[] = $value;
        }
        foreach($coupon_list_system as $key => $value) {
            if (in_array($value['coupon_id'], $filterSystemCouponId)) {
                continue;
            }
            $coupon_list[] = $value;
        }

        $category_platform = D('Card_new_coupon')->cate_platform();
        $category = $category_platform['category'];

        if(empty($this->config['appoint_page_row'])){
            unset($category['appoint']);
        }
        $platform = $category_platform['platform'];


        //获取用户已经领取券的列表  平台优惠券
        $had_pull_obj = [];
        if($coupon_list){
            $had_pull_arr = M('System_coupon_hadpull')->field('`coupon_id`')->where(['uid'=>$this->user_session['uid'], 'coupon_id'=>['in', array_column($coupon_list, 'coupon_id')]])->select();
            foreach($had_pull_arr as $value){
                $had_pull_obj[$value['coupon_id']]++;
            }
        }
	
	//屏蔽掉发券功能新客优惠券
        $activity = (new SystemSendCouponActivityModel)->getNowActivity(0, 'all');
        $activityCoupon = $activity['coupon_lists'] ?? '';
        $couponids = [];
        foreach ($activityCoupon as $c) {
            $c['type'] == 'mer' && $couponids[] = $c['coupon_id'];
        }


        foreach ($coupon_list as $vv) {
            if($vv['coupon_type'] == 'system') {  //平台优惠券
                if($vv['status']==2 && $vv['last_time']<$_SERVER['REQUEST_TIME']-86400){
                    continue;
                }
                if ($couponids && in_array($vv['coupon_id'], $couponids)) {
                        continue;
                    }
		

                $had_pull = $had_pull_obj[$vv['coupon_id']];

                if($had_pull>=$vv['limit']){
                    $vv['status'] = 4;//超过限制
                }
                $vv['platform'] = unserialize($vv['platform']);
                $vv['has_get'] =$had_pull;

                if($vv['limit']-intval($had_pull)<=$vv['num']-$vv['had_pull']){
                    $vv['can_get_num'] = $vv['limit']-intval($had_pull);
                }else{
                    $vv['can_get_num'] = $vv['num']-$vv['had_pull'];
                }

                $vv['has_pull'] = $had_pull;
                $vv['can_get'] = unserialize($vv['platform']);
                $tmp_platform = '';
                foreach ($vv['platform'] as $vt) {
                    $tmp_platform .= $platform[$vt] . '/';
                }
                $vv['platform'] = substr($tmp_platform, 0, -1);

                //平台优惠券增加指定店铺提示
                $vv['designated_shop'] = '';
                if ($vv['use_type'] == 1) {
                    $fist_store_name = M('System_coupon_store')->alias('cs')
                        ->join('LEFT JOIN ' . C('DB_PREFIX') . 'merchant_store AS s ON s.store_id = cs.store_id')
                        ->where(['cs.coupon_id' => $vv['coupon_id']])
                        ->order('cs.id ASC')
                        ->getField('s.name');
                    $vv['designated_shop'] = $fist_store_name ?$fist_store_name: '';
                }
                if($vv['allow_new']==1){//这里硬加一个新人券类型
                    $tmp['new_user'][] = $vv;
                }
                $tmp[$vv['cate_name']][] = $vv;
            } elseif($vv['coupon_type'] == 'mer') { //商家优惠券
                if($vv['had_pull']==$vv['num'] && $vv['last_time']<$_SERVER['REQUEST_TIME']-86400){
                    continue;
                }
                $had_pull = M('Card_new_coupon_hadpull')->where(array('uid'=>$this->user_session['uid'],'coupon_id'=>$vv['coupon_id']))->count();
                if($had_pull==$vv['limit']){
                    $vv['status'] = 4;//超过限制
                }

                if($vv['limit']-intval($had_pull)<=$vv['num']-$vv['had_pull']){
                    $vv['can_get_num'] = $vv['limit']-intval($had_pull);
                }else{
                    $vv['can_get_num'] = $vv['num']-$vv['had_pull'];
                }

                $vv['platform'] = unserialize($vv['platform']);
                $vv['has_pull'] = $had_pull;
                $tmp_platform = '';
                foreach ($vv['platform'] as $vt) {
                    $tmp_platform .= $platform[$vt] . '/';
                }
                $vv['platform'] = substr($tmp_platform, 0, -1);
                $tmp[$vv['cate_name']][] = $vv;
            }

            foreach ($category as $k => $c) {
                if (empty($tmp[$k])) {
                    $tmp[$k] = array();
                    $category_tmp[$k]['count'] = 0;
                }else {
                    $category_tmp[$k]['count'] = count($tmp[$k]);
                }
            }
        }

        //全部
        foreach($tmp as $k => $v) {
            foreach($v as $vv) {
                $vv['use_categroy'] = $k;
                $vv['use_categroy_name'] = $category[$k];
                $tmp['allin'][] = $vv;

            }
        }
        $category_tmp['allin']['count'] = count($tmp['allin']);

        arsort($category_tmp);
        $category_tmp = $this->deal_category_data($category_tmp);//需求是必须将新人券放在第一位

        foreach($category_tmp as $key => $value) {
            if($value['count'] == 0) {
                unset($category[$key]);
            }
        }

        if(empty($tmp)) {
            $this->assign('empty_coupon', 1);
        }

        $this->assign('coupon_list', $tmp);
        $this->assign('category', $category);
        $max_category = array_keys($category_tmp);
        $this->assign('max_category', $max_category[0]);
        $this->assign('category_tmp', $category_tmp);
        $this->assign('isnew', D('User')->check_new($this->user_session['uid'],'all'));
        $this->display();
    }

    protected function deal_category_data($catrgory){
        $temp = $catrgory['allin'];
        unset($catrgory['allin']);
        return array_merge(array('allin'=>$temp),$catrgory);

    }

    public function link(){
        if($_GET['cate_name']=='all'){
            redirect($this->config['site_url'].'/wap.php');
        }else if($_GET['cate_name']=='shop'){
            redirect($this->config['site_url'].'/wap.php');
        }else if($_GET['cate_name']=='group'){
            redirect($this->config['site_url'].'/wap.php');
        }else if($_GET['cate_name']=='meal'){
            redirect($this->config['site_url'].'/wap.php');
        }else if($_GET['cate_name']=='store'){
            redirect($this->config['site_url'].'/wap.php?c=My&a=pay&mer_id='.$_GET['mer_id']);
        }else if($_GET['cate_name']=='appoint'){
            redirect($this->config['site_url'].'/wap.php');
        }else if($_GET['cate_name']=='mall'){
            redirect($this->config['site_url'].'/wap.php?c=Mall&a=index');
        } else {

        }
    }



    public function had_pull()
    {
        if($_POST['coupon_type'] == 'mer') { //领取商家券
            $coupon_id = $_POST['coupon_id'];
            $get_all = (int)$_POST['get_all'];
            $uid = $this->now_user['uid'];
            $model = D('Card_new_coupon');
            $has_get = $model->get_coupon_count_by_uid($coupon_id, $uid);
            $return['has_get'] = $has_get;
            if (empty($this->user_session)) {
                echo json_encode(array('error_code' => 6, 'msg' => '未登录'));
                die;
            }
            $result = $model->had_pull($coupon_id, $uid,'',false,false,false,$get_all);
            if ($result['error_code'] != 0) {
                switch ($result['error_code']) {
                    case '1':
                        $error_msg = '领取失败';
                        break;
                    case '2':
                        $error_msg = '优惠券已过期';
                        break;
                    case '3':
                        $error_msg = '优惠券已经领完了';
                        break;
                    case '4':
                        $error_msg = '只允许新用户领取';
                        break;
                    case '5':
                        $error_msg = '不能再领取了';
                        break;
                }
                echo json_encode(array('error_code' => $result['error_code'], 'msg' => $error_msg));
                die;
            }
            $reduce_num=$result['coupon']['reduce_num'];
            $reduce_num=$reduce_num>0?$reduce_num:1;
            $model->decrease_sku(0,$reduce_num,$coupon_id);//网页领取完，微信卡券库存需要同步减少

            if($result['coupon']['limit']-$result['coupon']['has_get']<=$result['coupon']['num']-$result['coupon']['had_pull']){
                $result['coupon']['can_get'] = $result['coupon']['limit']-$result['coupon']['has_get'];
//                var_dump('aaaa'.$result['coupon']['can_get']);exit();
            }else{
                $result['coupon']['can_get'] =$result['coupon']['num']-$result['coupon']['had_pull']-1;
//                var_dump('ccc'.$result['coupon']['can_get']);exit();
            }

            echo json_encode(array('error_code' => 0, 'msg' => '领取成功', 'coupon' => $result['coupon']));
            die;
        } else { //领取平台券 system
            $coupon_id = intval($_POST['coupon_id']);
            $uid = $this->user_session['uid'];
            if (empty($this->user_session)) {
                echo json_encode(array('error_code' => 6, 'msg' => L_('未登录')));
                die;
            }elseif(empty($this->user_session['phone'])){
                echo json_encode(array('error_code' => 7, 'msg' => L_('未绑定手机号码')));
            }
            //是否一次领取最多可领取数量
            $get_all=I('get_all',0,'intval');
            $model = D('Card_new_coupon');
            $has_get = $model->get_coupon_count_by_uid($coupon_id, $uid);
            //$return['has_get'] = $has_get;

            $result = $model->had_pull($coupon_id, $uid,'',$get_all);
            if ($result['error_code'] != 0) {
                switch ($result['error_code']) {
                    case '1':
                        $error_msg = L_('领取失败');
                        break;
                    case '2':
                        $error_msg = L_('优惠券已过期');
                        break;
                    case '3':
                        $error_msg = L_('优惠券已经领完了');
                        break;
                    case '4':
                        $error_msg = L_('只允许新用户领取');
                        break;
                    case '5':
                        $error_msg = L_('不能再领取了');
                        break;
                    case '6':
                        $error_msg = L_('您当前所在城市无法领取该优惠券');
                        break;
                }
                echo json_encode(array('error_code' => $result['error_code'], 'msg' => $error_msg));
                die;
            }
            $reduce_num=$result['coupon']['reduce_num'];
            $reduce_num=$reduce_num>0?$reduce_num:1;
            $model->decrease_sku(0,$reduce_num,$coupon_id);//网页领取完，微信卡券库存需要同步减少
            if($result['coupon']['limit']-$result['coupon']['has_get']<=$result['coupon']['num']-$result['coupon']['had_pull']){
                $result['coupon']['can_get'] = $result['coupon']['limit']-$result['coupon']['has_get'];
            }else{
                $result['coupon']['can_get'] =$result['coupon']['num']-$result['coupon']['had_pull'];
            }
            echo json_encode(array('error_code' => 0, 'msg' => L_('领取成功'), 'coupon' => $result['coupon']));
            die;
        }


    }
	
	public function merchant_shop_list(){
		redirect(U('merchant_store',array('shop'=>'1','mer_id'=>$_GET['mer_id'],'show_own'=>$_GET['show_own'])));
	}

    public function ajax_get_card(){
        if($_POST['store_id']){
            $store_id = $_POST['store_id'];
            $now_store = M('Merchant_store')->where(array('store_id'=>$store_id))->find();
            $mer_id = $now_store['mer_id'];
        }else if($_POST['mer_id']){
            $mer_id = $_POST['mer_id'];
        }
            $now_card = M(_view('Card_new'))->where(array('mer_id' => $mer_id))->find();
            $card_info = D('Card_new')->get_card_by_uid_and_mer_id($this->now_user['uid'],$now_store['mer_id']);

            if (empty($card_info['id'])) {
                if ($now_card['self_get']) {
                    $result = D('Card_new')->auto_get($this->now_user['uid'], $_GET['mer_id']);
                    if (!$result['error_code']) {
                        $card_info = D('Card_new')->get_card_by_uid_and_mer_id($this->now_user['uid'], $_GET['mer_id']);

                    } else {
                        $this->error_tips($result['msg']);
                    }
                } else {
                    $this->error_tips('该会员卡不能自助领卡，请联系商家');
                }

            } elseif ($card_info['status'] == 0) {
                $this->error_tips('您的会员卡不能使用！');
            }

        $this->success($card_info);



    }

    /**
     * 用户兑换卡密
     */
    public function ajax_exchangeCard(){
        if(IS_POST){
            $mer_id = $_POST['mer_id'];
            $cardmi = $_POST['cardmi'];
            $uid = $this->now_user['uid'];
            if(!$uid){
                exit(json_encode(array('error_code' => 2, 'msg' => '请先登录！')));
            }
            if(!$mer_id){
                exit(json_encode(array('error_code' => 3, 'msg' => '请选择要充值的商家！')));
            }
            if(!$cardmi){
                exit(json_encode(array('error_code' => 4, 'msg' => '请输入卡密！')));
            }
            //商家是否有会员卡
            $mer_card = M('Card_new')->where(['mer_id'=>$mer_id])->find();
            if(!$mer_card){
                exit(json_encode(array('error_code' => 5, 'msg' => '商家未开通会员卡！')));
            }
            //查询卡密是否存在
            $card_mi = D('Merchant_physical_card')->where(['mer_id'=>$mer_id,'carmi'=>$cardmi])->find();
            if(!$card_mi){
                exit(json_encode(array('error_code' => 6, 'msg' => '卡密不存在！')));
            }
            if($card_mi['uid']&&$card_mi['uid']!=$uid){
                exit(json_encode(array('error_code' => 7, 'msg' => '无法使用他人卡密充值！')));
            }
            D('Card_userlist')->where(['mer_id'=>$mer_id,'uid'=>$uid])->save(['card_id' => $mer_card['card_id']]);
            //查询是否有实体卡
            $user_card = D('Card_userlist')->where(['mer_id'=>$mer_id,'uid'=>$uid,'card_id'=>$mer_card['card_id']])->find();
            D()->startTrans();
            try {
                if(!$user_card){
                    //自动领取
                    $user_card_data = [
                        'card_id' => $mer_card['card_id'],
                        'mer_id' => $mer_id,
                        'uid' => $uid
                    ];
                    $card_user_id = D('Card_userlist')->add($user_card_data);
                }else{
                    $card_user_id = $user_card['id'];
                }
                $re = D('Card_userlist')->where(['id'=>$card_user_id])->setInc('card_money',($card_mi['money']-$card_mi['use_money']));
                if($re){
                    $res = D('Merchant_physical_card')->addPhysicalCardOrder(($card_mi['money']-$card_mi['use_money']),$card_mi['id'],'merchant_card',$mer_id,0,0,$uid);
                    $data_money['card_id'] = $card_user_id;
                    $data_money['type'] = 1;
                    $data_money['money_add'] = $card_mi['money']-$card_mi['use_money'];
                    $data_money['desc'] = L_('兑换卡密增加余额');
                    D('Card_new')->add_row($data_money);
                    D()->commit();
                    exit(json_encode(array('error_code' => 0, 'msg' => '兑换成功')));
                }else{
                    D()->rollback();
                    exit(json_encode(array('error_code' => 8, 'msg' => '兑换失败')));
                }
            }catch (\Exception $e){
                fdump_api($e->getMessage(),'merchant_physical_card');
                D()->rollback();
                exit(json_encode(array('error_code' => 1, 'msg' => $e->getMessage())));
            }
        }else{
            exit(json_encode(array('error_code' => 1, 'msg' => '请求错误！')));
        }
    }

}

?>